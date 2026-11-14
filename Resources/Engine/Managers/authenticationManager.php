<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRProprietary Engine, Copyright on 11/13/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / File Information ...
// / v4.2.0.
// / This file is the Authentication Manager. It looks after accounts over time, where the Authentication Core answers a request.
// /
// / It is not a manager in the sense the four the listener dispatches are. It opens no socket & runs as no role of its own.
// / The Resource Manager loads it to answer a quota, & sweeps accounts with it on the listener's storage schedule.
// / A web request loads it to sweep where no listener runs, at most once in ten minutes.
// / The Engine loads it for --account, the administrator's command for accounts.
// / Everything it reads & writes is the web server account's, below Accounts & Users, so root hands --account to that account.
// / It relies on the Authentication Core for every record, & is never loaded where that core is not.
// / See Documentation/ABOUT_AUTHENTICATION.txt.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by an application.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! Engine-35000, A manager subcomponent cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The version of this manager. Read by the Engine WITHOUT executing this file.
$ManagerVersion = 'v4.2.0';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to add up the bytes a folder holds.
// / Accepts the folder. Returns whether it was measured & the bytes, in that order.
// / A link is counted as the link & never followed, so a link cannot charge a user for what is not theirs or walk out of their folder.
// / A folder holding more than 200,000 entries is not measured, because a quota is not worth a walk that long.
function measureFolderBytes($folderPath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FolderWasMeasured = FALSE;
  $FolderBytes = 0;
  $folderWalker = $folderEntry = $walkError = NULL;
  $entriesCounted = 0;
  $walkWasCut = FALSE;
  if ((string)$folderPath !== '' && is_dir($folderPath) && !is_link($folderPath)) {
    try {
      $folderWalker = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folderPath, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
      foreach ($folderWalker as $folderEntry) {
        $entriesCounted++;
        if ($entriesCounted > 200000) {
          $walkWasCut = TRUE;
          break; }
        if ($folderEntry->isLink()) $FolderBytes += (int)@lstat($folderEntry->getPathname())['size'];
        else if ($folderEntry->isFile()) $FolderBytes += (int)$folderEntry->getSize(); }
      $FolderWasMeasured = !$walkWasCut; }
    catch (Throwable $walkError) {
      $FolderWasMeasured = FALSE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderWalker, $folderEntry, $walkError, $entriesCounted, $walkWasCut, $folderPath);
  return array($FolderWasMeasured, $FolderBytes); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether a signed in user's quota allows a write.
// / Accepts the owner, which is the account's record name, & the bytes the write would add. Returns whether it may & the reason it may not, in that order.
// / The quota is --User Storage Quota--, & 0 keeps none. What the user holds is measured from their folder, so it is never out of date.
// / A folder that cannot be measured allows the write, as a disk that cannot be measured does, & is a warning.
// / An owner with no account allows nothing, because only this core names owners & one it named that is gone has been removed.
function evaluateAccountQuota($quotaOwner, $requestedBytes) {
  // / Set variables.
  global $EnableMemoryProtection;
  $QuotaAllows = FALSE;
  $QuotaReason = '';
  $authenticationSettings = $accountRecord = array();
  $accountWasRead = $folderIsReady = $folderWasMeasured = FALSE;
  $folderPath = '';
  $quotaBytes = $heldBytes = $writeBytes = 0;
  $authenticationSettings = authenticationSettings();
  $quotaBytes = (int)$authenticationSettings['QuotaMegabytes'] * 1048576;
  $writeBytes = max(0, (int)$requestedBytes);
  list ($accountWasRead, $accountRecord) = readAuthenticationRecord(authenticationRecordPath('Users', (string)$quotaOwner));
  if (!$accountWasRead) $QuotaReason = 'The account this write is charged to no longer exists.';
  else if ($quotaBytes <= 0) $QuotaAllows = TRUE;
  else {
    list ($folderIsReady, $folderPath) = accountFolderPath($accountRecord);
    if ($folderIsReady) list ($folderWasMeasured, $heldBytes) = measureFolderBytes(rtrim($folderPath, DIRECTORY_SEPARATOR));
    if (!$folderWasMeasured) {
      $QuotaAllows = TRUE;
      warningEntry('The files of '.(string)($accountRecord['Username'] ?? '').' could not be measured against --User Storage Quota--, so a write of '.$writeBytes.' byte(s) was allowed.'); }
    else if ($heldBytes + $writeBytes > $quotaBytes) $QuotaReason = 'The user holds '.$heldBytes.' byte(s), & writing '.$writeBytes.' more would pass their quota of '.$quotaBytes.'.';
    else $QuotaAllows = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $authenticationSettings, $accountRecord, $accountWasRead, $folderIsReady, $folderWasMeasured, $folderPath, $quotaBytes, $heldBytes, $writeBytes, $quotaOwner, $requestedBytes);
  return array($QuotaAllows, $QuotaReason); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to list the records of one kind.
// / Accepts the kind. Returns the record names & their paths, keyed by name.
// / Only a file named as this core names a record is listed, so nothing else in the folder is ever touched.
function listAuthenticationRecords($recordKind) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RecordPaths = array();
  $folderIsReady = FALSE;
  $folderPath = $entryName = '';
  $folderEntries = array();
  list ($folderIsReady, $folderPath) = authenticationFolder($recordKind);
  if ($folderIsReady) $folderEntries = @scandir($folderPath);
  if (is_array($folderEntries)) foreach ($folderEntries as $entryName) if (preg_match('/^([a-f0-9]{32,64})\.php$/', (string)$entryName) === 1 && !is_link($folderPath.DIRECTORY_SEPARATOR.$entryName)) $RecordPaths[substr($entryName, 0, -4)] = $folderPath.DIRECTORY_SEPARATOR.$entryName;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderIsReady, $folderPath, $entryName, $folderEntries, $recordKind);
  return $RecordPaths; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove every account, code, sign in & count of failures that is over.
// / Accepts nothing. Returns whether the sweep finished & how many records it removed, in that order.
// / A sign in is over once its key outlived its life & its grace, its longest life passed, or its account moved on or is gone.
// / An account that never confirmed its address within --Unconfirmed Account Lifetime-- is removed with its empty folder.
// / A code is over once it expires, & a count of failures once its newest failure is older than the window.
// / Each is removed under its own lock, & one another request is holding is left for the next sweep.
function sweepAuthentication() {
  // / Set variables.
  global $EnableMemoryProtection;
  $SweepCompleted = TRUE;
  $RecordsRemoved = 0;
  $authenticationSettings = $recordPaths = $lockedRecord = $accountRecord = $newestFailures = array();
  $recordName = $recordPath = $folderPath = $recordKind = '';
  $recordHandle = NULL;
  $recordWasRead = $recordIsOver = $signInIsLive = $folderIsReady = FALSE;
  $authenticationSettings = authenticationSettings();
  foreach (array('SignIns', 'Codes', 'Limits', 'Users') as $recordKind) {
    $recordPaths = listAuthenticationRecords($recordKind);
    foreach ($recordPaths as $recordName => $recordPath) {
      list ($recordHandle, $recordWasRead, $lockedRecord) = lockAuthenticationRecord($recordPath, FALSE);
      if (!is_resource($recordHandle)) continue;
      $recordIsOver = !$recordWasRead;
      if ($recordWasRead && $recordKind === 'SignIns') {
        list ($signInIsLive, $accountRecord) = signInIsLive($lockedRecord, (string)$recordName);
        $recordIsOver = (!$signInIsLive or ((int)($lockedRecord['CurrentIssued'] ?? 0) + $authenticationSettings['KeyLifetime'] + $authenticationSettings['KeyGrace'] < time())); }
      else if ($recordWasRead && $recordKind === 'Codes') $recordIsOver = ((int)($lockedRecord['Expires'] ?? 0) < time());
      else if ($recordWasRead && $recordKind === 'Limits') {
        $newestFailures = (isset($lockedRecord['Failures']) && is_array($lockedRecord['Failures'])) ? $lockedRecord['Failures'] : array();
        $recordIsOver = (empty($newestFailures) or (int)max($newestFailures) < time() - $authenticationSettings['AttemptWindow']); }
      else if ($recordWasRead && $recordKind === 'Users') {
        $recordIsOver = (empty($lockedRecord['Confirmed']) && (int)($lockedRecord['Created'] ?? 0) + $authenticationSettings['UnconfirmedLifetime'] < time());
        if ($recordIsOver) {
          list ($folderIsReady, $folderPath) = accountFolderPath($lockedRecord);
          if ($folderIsReady) @rmdir(rtrim($folderPath, DIRECTORY_SEPARATOR));
          logEntry('The account '.(string)($lockedRecord['Username'] ?? '').' never confirmed its address & was removed.'); } }
      if ($recordIsOver && removeAuthenticationRecord($recordHandle, $recordPath)) $RecordsRemoved++;
      else if ($recordIsOver) $SweepCompleted = FALSE;
      else releaseAuthenticationRecord($recordHandle); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $authenticationSettings, $recordPaths, $lockedRecord, $accountRecord, $newestFailures, $recordName, $recordPath, $folderPath, $recordHandle, $recordWasRead, $recordIsOver, $signInIsLive, $folderIsReady, $recordKind);
  return array($SweepCompleted, $RecordsRemoved); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to sweep accounts when the last sweep is long enough ago.
// / Accepts the fewest seconds between two sweeps. Returns whether a sweep ran & how many records it removed, in that order.
// / The time of the last sweep is a marker file's, & it is claimed with a lock that is never waited for, so one request sweeps & the rest go on.
function sweepAuthenticationIfDue($sweepInterval) {
  // / Set variables.
  global $EnableMemoryProtection;
  $SweepRan = FALSE;
  $RecordsRemoved = 0;
  $authenticationSettings = array();
  $markerPath = '';
  $markerHandle = NULL;
  $sweepCompleted = FALSE;
  $authenticationSettings = authenticationSettings();
  if ($authenticationSettings['AccountLoc'] !== '' && is_dir($authenticationSettings['AccountLoc']) && !is_link($authenticationSettings['AccountLoc'])) {
    $markerPath = $authenticationSettings['AccountLoc'].DIRECTORY_SEPARATOR.'swept';
    clearstatcache(TRUE, $markerPath);
    if (!is_link($markerPath) && (!file_exists($markerPath) or (int)@filemtime($markerPath) + (int)$sweepInterval <= time())) $markerHandle = @fopen($markerPath, 'c');
    if (is_resource($markerHandle)) {
      if (flock($markerHandle, LOCK_EX | LOCK_NB)) {
        @touch($markerPath);
        list ($sweepCompleted, $RecordsRemoved) = sweepAuthentication();
        $SweepRan = TRUE;
        if (!$sweepCompleted) warningEntry('An account sweep did not finish, & what it left is swept next time.');
        flock($markerHandle, LOCK_UN); }
      fclose($markerHandle); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $authenticationSettings, $markerPath, $markerHandle, $sweepCompleted, $sweepInterval);
  return array($SweepRan, $RecordsRemoved); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to make a password an administrator hands to a user.
// / Accepts nothing. Returns 20 characters from 55 that cannot be mistaken for each other, which is more than 115 bits.
function generateAccountPassword() {
  // / Set variables.
  global $EnableMemoryProtection;
  $GeneratedPassword = '';
  $passwordAlphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
  $characterIndex = 0;
  for ($characterIndex = 0; $characterIndex < 20; $characterIndex++) $GeneratedPassword .= $passwordAlphabet[random_int(0, strlen($passwordAlphabet) - 1)];
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $passwordAlphabet, $characterIndex);
  return $GeneratedPassword; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to change one account on an administrator's word.
// / Accepts the username & the change, which is confirm, disable, enable, admin, user or password. Returns whether it was made & a new password, in that order.
// / A disabled account signs nothing in from the next request, & a new password ends every sign in made before it.
// / A new password is made here & handed back once, & is never written anywhere but as its hash.
function changeAccountByAdministrator($accountUsername, $accountChange) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ChangeWasMade = FALSE;
  $NewPassword = '';
  $accountRecord = $lockedRecord = array();
  $accountWasFound = $recordWasRead = FALSE;
  $accountName = $accountPath = $passwordHash = $passwordScheme = '';
  $accountHandle = NULL;
  list ($accountWasFound, $accountRecord, $accountName, $accountPath) = accountByUsername($accountUsername);
  if ($accountWasFound) list ($accountHandle, $recordWasRead, $lockedRecord) = lockAuthenticationRecord($accountPath, FALSE);
  if ($recordWasRead) {
    if ($accountChange === 'confirm') $lockedRecord['Confirmed'] = TRUE;
    else if ($accountChange === 'disable') $lockedRecord['Disabled'] = TRUE;
    else if ($accountChange === 'enable') $lockedRecord['Disabled'] = FALSE;
    else if ($accountChange === 'admin' or $accountChange === 'user') $lockedRecord['Role'] = $accountChange;
    else if ($accountChange === 'password') {
      $NewPassword = generateAccountPassword();
      list ($passwordHash, $passwordScheme) = hashAccountPassword($NewPassword);
      list ($lockedRecord['PasswordHash'], $lockedRecord['PasswordScheme']) = array($passwordHash, $passwordScheme);
      $lockedRecord['Generation'] = (int)($lockedRecord['Generation'] ?? 1) + 1; }
    $ChangeWasMade = saveAuthenticationRecord($accountHandle, $lockedRecord);
    if ($ChangeWasMade) warningEntry('Command line invocation. The account '.(string)$accountUsername.' was changed by an administrator: '.$accountChange.'.'); }
  else releaseAuthenticationRecord($accountHandle);
  if (!$ChangeWasMade) $NewPassword = '';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $accountRecord, $lockedRecord, $accountWasFound, $recordWasRead, $accountName, $accountPath, $passwordHash, $passwordScheme, $accountHandle, $accountUsername, $accountChange);
  return array($ChangeWasMade, $NewPassword); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to add an account on an administrator's word.
// / Accepts the username & the address. Returns whether it was added & its first password, in that order.
// / It is confirmed already, because an administrator vouched for the address, & its password is made here & handed back once.
function addAccountByAdministrator($accountUsername, $emailAddress) {
  // / Set variables.
  global $EnableMemoryProtection;
  $AccountWasAdded = FALSE;
  $FirstPassword = '';
  $saltIsReady = FALSE;
  $accountsSalt = $accountPath = $passwordHash = $passwordScheme = '';
  list ($saltIsReady, $accountsSalt) = accountsSalt();
  if ($saltIsReady) {
    $FirstPassword = generateAccountPassword();
    list ($passwordHash, $passwordScheme) = hashAccountPassword($FirstPassword);
    $accountPath = authenticationRecordPath('Users', accountRecordName($accountsSalt, 'account', $accountUsername));
    if ($accountPath !== '' && $passwordHash !== '') $AccountWasAdded = createAuthenticationRecord($accountPath, array('Username' => (string)$accountUsername, 'Email' => (string)$emailAddress,
      'PasswordHash' => $passwordHash, 'PasswordScheme' => $passwordScheme, 'Role' => 'user', 'Confirmed' => TRUE, 'Disabled' => FALSE, 'Created' => time(), 'LastSignIn' => 0,
      'Folder' => bin2hex(random_bytes(9)), 'Generation' => 1)); }
  if ($AccountWasAdded) warningEntry('Command line invocation. The account '.(string)$accountUsername.' was added by an administrator.');
  else $FirstPassword = '';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $saltIsReady, $accountsSalt, $accountPath, $passwordHash, $passwordScheme, $accountUsername, $emailAddress);
  return array($AccountWasAdded, $FirstPassword); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove an account & the files its user kept, on an administrator's word.
// / Accepts the username. Returns TRUE when the account is gone.
// / Its sign ins end with it, because a sign in whose account is gone signs nothing in. Its codes are left to expire & be swept.
// / The files are removed through cleanFiles(), which refuses anything outside the users folder.
function removeAccountByAdministrator($accountUsername) {
  // / Set variables.
  global $EnableMemoryProtection;
  $AccountWasRemoved = FALSE;
  $authenticationSettings = $accountRecord = $lockedRecord = array();
  $accountWasFound = $recordWasRead = $folderIsReady = FALSE;
  $accountName = $accountPath = $folderPath = '';
  $accountHandle = NULL;
  $authenticationSettings = authenticationSettings();
  list ($accountWasFound, $accountRecord, $accountName, $accountPath) = accountByUsername($accountUsername);
  if ($accountWasFound) list ($accountHandle, $recordWasRead, $lockedRecord) = lockAuthenticationRecord($accountPath, FALSE);
  if ($recordWasRead) {
    list ($folderIsReady, $folderPath) = accountFolderPath($lockedRecord);
    $AccountWasRemoved = removeAuthenticationRecord($accountHandle, $accountPath);
    if ($AccountWasRemoved && $folderIsReady && function_exists('cleanFiles')) cleanFiles(rtrim($folderPath, DIRECTORY_SEPARATOR), array($authenticationSettings['UserLoc']));
    if ($AccountWasRemoved) warningEntry('Command line invocation. The account '.(string)$accountUsername.' & its files were removed by an administrator.'); }
  else releaseAuthenticationRecord($accountHandle);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $authenticationSettings, $accountRecord, $lockedRecord, $accountWasFound, $recordWasRead, $folderIsReady, $accountName, $accountPath, $folderPath, $accountHandle, $accountUsername);
  return $AccountWasRemoved; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to print every account, for an administrator.
// / Accepts nothing. Returns how many accounts were printed.
// / A password's hash is never printed, & neither is any sign in's key.
function printAccounts() {
  // / Set variables.
  global $Lol, $EnableMemoryProtection;
  $AccountsPrinted = 0;
  $recordPaths = $accountRecord = $accountLines = array();
  $recordName = $recordPath = '';
  $accountWasRead = FALSE;
  $recordPaths = listAuthenticationRecords('Users');
  foreach ($recordPaths as $recordName => $recordPath) {
    list ($accountWasRead, $accountRecord) = readAuthenticationRecord($recordPath);
    if (!$accountWasRead) continue;
    $accountLines[strtolower((string)($accountRecord['Username'] ?? ''))] = '  '.str_pad((string)($accountRecord['Username'] ?? ''), 24).str_pad(((string)($accountRecord['Role'] ?? '') === 'admin' ? 'admin' : 'user'), 7)
      .str_pad((!empty($accountRecord['Disabled']) ? 'disabled' : (!empty($accountRecord['Confirmed']) ? 'confirmed' : 'unconfirmed')), 13)
      .((int)($accountRecord['LastSignIn'] ?? 0) > 0 ? 'last signed in '.date('Y-m-d H:i', (int)$accountRecord['LastSignIn']) : 'never signed in').'  '.(string)($accountRecord['Email'] ?? '');
    $AccountsPrinted++; }
  ksort($accountLines);
  print($Lol.$AccountsPrinted.' account(s).'.$Lol.(empty($accountLines) ? '' : implode($Lol, $accountLines).$Lol).$Lol);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $recordPaths, $accountRecord, $accountLines, $recordName, $recordPath, $accountWasRead);
  return $AccountsPrinted; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say how --account is written.
// / Accepts the action to describe, or an empty string for every one. Returns the lines, each beginning with two spaces.
// / Every refusal --account prints ends with these, so an administrator is shown the form to type rather than told a rule.
function accountCommandUsage($accountAction) {
  // / Set variables.
  global $Lol, $EnableMemoryProtection;
  $UsageText = '';
  $usageForms = array();
  $formAction = $formText = '';
  $usageForms = array('--list' => '--account --list', '--add' => '--account --add <username> <address>', '--confirm' => '--account --confirm <username>',
    '--disable' => '--account --disable <username>', '--enable' => '--account --enable <username>', '--admin' => '--account --admin <username>',
    '--user' => '--account --user <username>', '--password' => '--account --password <username>', '--remove' => '--account --remove <username>          Asks first. -y answers yes.');
  foreach ($usageForms as $formAction => $formText) if ((string)$accountAction === '' or (string)$accountAction === $formAction) $UsageText .= '  '.$formText.$Lol;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $usageForms, $formAction, $formText, $accountAction);
  return $UsageText; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run --account, the administrator's command for accounts.
// / Accepts the argument list, the action & whether the operator confirmed in advance, in that order. Returns the exit status.
// / The actions are --list, --add with a username & an address, --confirm, --disable, --enable, --admin, --user, --password & --remove, each with a username.
// / The username & the address are read from the argument list as they were typed, because the parsed targets are lower cased.
// / A username & an address are checked as a page's are, & a username the command line could not have meant is refused before anything is read.
// / --remove asks first, because the user's files go with the account.
// / A refusal names what is missing & shows the form to type. An action written with one dash is named with its two, & is not run.
function runAccountCommand($cliArguments, $accountAction, $cliConfirmed) {
  // / Set variables.
  global $Lol, $EnableMemoryProtection;
  $CommandStatus = 1;
  $accountUsername = $emailAddress = $newPassword = $changeName = '';
  $changeWasMade = $accountWasAdded = $actionIsConfirmed = $accountWasFound = FALSE;
  $accountRecord = array();
  $accountName = $accountPath = $actionNamed = '';
  $changeNames = array('--confirm' => 'confirm', '--disable' => 'disable', '--enable' => 'enable', '--admin' => 'admin', '--user' => 'user', '--password' => 'password');
  $accountUsername = (is_array($cliArguments) && isset($cliArguments[2]) && is_string($cliArguments[2])) ? trim($cliArguments[2]) : '';
  $emailAddress = (is_array($cliArguments) && isset($cliArguments[3]) && is_string($cliArguments[3])) ? trim($cliArguments[3]) : '';
  // / An option, such as -y, where a username belongs names no username, because no username begins with a dash.
  if (strpos($accountUsername, '-') === 0) $accountUsername = '';
  if ($accountAction === '--list') {
    printAccounts();
    $CommandStatus = 0; }
  else if (!in_array($accountAction, array('--add', '--remove'), TRUE) && !isset($changeNames[$accountAction])) {
    // / An action typed with one dash names the action it would be with two, so the refusal can say which was meant.
    $actionNamed = '--'.ltrim((string)$accountAction, '-');
    if ((string)$accountAction !== '' && $actionNamed !== (string)$accountAction && ($actionNamed === '--list' or in_array($actionNamed, array('--add', '--remove'), TRUE) or isset($changeNames[$actionNamed]))) print($Lol.(string)$accountAction.' is written with two dashes, as '.$actionNamed.'.'.$Lol.$Lol.accountCommandUsage($actionNamed).$Lol);
    else print($Lol.'--account takes one of these actions. Each but --list names an account by its username.'.$Lol.$Lol.accountCommandUsage('').$Lol); }
  else if ($accountUsername === '') print($Lol.'--account '.$accountAction.' takes the username of an account.'.$Lol.$Lol.accountCommandUsage($accountAction).$Lol);
  else if (!accountUsernameIsUsable($accountUsername)) print($Lol.'A username is 3 to 64 letters, digits, dots, dashes & underscores, & begins with a letter or a digit.'.$Lol.$Lol.accountCommandUsage($accountAction).$Lol);
  else if ($accountAction === '--add' && !accountAddressIsUsable($emailAddress)) print($Lol.'--account --add takes a username & then an email address.'.$Lol.$Lol.accountCommandUsage('--add').$Lol);
  else if ($accountAction === '--add') {
    list ($accountWasAdded, $newPassword) = addAccountByAdministrator($accountUsername, $emailAddress);
    if (!$accountWasAdded) print($Lol.'The account '.$accountUsername.' was not added. The username may be taken, or Accounts may be missing. Run -fp as root.'.$Lol.$Lol);
    else {
      print($Lol.'The account '.$accountUsername.' was added, confirmed. Its password is shown this once:'.$Lol.$Lol.'  '.$newPassword.$Lol.$Lol);
      $CommandStatus = 0; } }
  else {
    list ($accountWasFound, $accountRecord, $accountName, $accountPath) = accountByUsername($accountUsername);
    if (!$accountWasFound) print($Lol.'There is no account named '.$accountUsername.'.'.$Lol.$Lol);
    else if ($accountAction === '--remove') {
      $actionIsConfirmed = confirmDestructiveAction('This removes the account '.$accountUsername.' & every file its user keeps.', $cliConfirmed);
      if ($actionIsConfirmed && removeAccountByAdministrator($accountUsername)) {
        print($Lol.'The account '.$accountUsername.' & its files were removed.'.$Lol.$Lol);
        $CommandStatus = 0; }
      else if ($actionIsConfirmed) print($Lol.'The account '.$accountUsername.' could not be removed.'.$Lol.$Lol); }
    else {
      $changeName = $changeNames[$accountAction];
      list ($changeWasMade, $newPassword) = changeAccountByAdministrator($accountUsername, $changeName);
      if (!$changeWasMade) print($Lol.'The account '.$accountUsername.' could not be changed.'.$Lol.$Lol);
      else {
        print($Lol.'The account '.$accountUsername.' was changed: '.$changeName.'.'.$Lol.($newPassword !== '' ? 'Its new password is shown this once, & every sign in made before it has ended:'.$Lol.$Lol.'  '.$newPassword.$Lol : '').$Lol);
        $CommandStatus = 0; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $accountUsername, $emailAddress, $newPassword, $changeName, $changeWasMade, $accountWasAdded, $actionIsConfirmed, $accountWasFound, $accountRecord, $accountName, $accountPath, $actionNamed, $changeNames, $cliArguments, $accountAction, $cliConfirmed);
  return $CommandStatus; }
// / -----------------------------------------------------------------------------------
?>
