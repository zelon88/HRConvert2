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
// / The Authentication Core, one of the Engine's cores.
// / It keeps accounts, signs users in & out, confirms an address, recovers an account & limits how often each may be tried.
// / A signed in page holds a key in its memory & nowhere a browser keeps for a site, so no cookie is ever set.
// / The page presents its key with every request, & exchanges it on a timer for a new one. A key is spent by the exchange.
// / Every account, sign in, code & count of failures is a file below Accounts in the data location, outside the web root.
// / Each is read as text & never executed, & each is changed under a lock on the file itself.
// / A signed in user's files are kept in a folder of their own below Users, which no sweep removes.
// / The Email Core sends each code. Without it, signing in with valid credentials still works, & every email that would
// / have been sent & every incorrect password is a warning in the log.
// / The application pins it exactly via $RequiredAuthenticationCoreVersion, & the Engine loads it where authentication is on.
// / Error block 43000 through 43019 is reserved for the Authentication Core.
// / See Documentation/ABOUT_AUTHENTICATION.txt.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by an application.
// / quickDie is not available here, because a component loaded directly has no core to
// / provide it. This is the one documented exception to quickDie being the only halt.
// / It names the Engine, as every other Engine core does.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! Engine-35000, A core component cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The version of this component. Read by the application without executing this file.
$AuthenticationCoreVersion = 'v4.2.0';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to read what the application & its administrator say about accounts.
// / Accepts nothing. Returns the settings, keyed by what this core calls them.
// / Each is read through applicationSetting(), so this core never knows what the application calls a setting.
// / Authentication is on only where the Engine's capability is on & the administrator set it to TRUE, as authenticationIsOn() decides.
// / The address an email names is the server's full address where the application has worked it out, & the server's name before it has.
// / Each number is held to a floor, so a value too small to work cannot lock every user out.
// / A key lives at least ten seconds & is exchanged at least twice in its life, & the grace for the key before it is a third of its life.
function authenticationSettings() {
  // / Set variables.
  global $EnableMemoryProtection;
  $AuthenticationSettings = array();
  $keyLifetime = $exchangeInterval = 0;
  $serverAddress = '';
  $serverAddress = (string)applicationSetting('ServerURL', '');
  if (trim($serverAddress) === '') $serverAddress = (string)applicationSetting('ServerName', '');
  $keyLifetime = max(10, (int)applicationSetting('SignInKeyLifetime', 30));
  $exchangeInterval = min(max(2, (int)applicationSetting('SignInExchangeInterval', 6)), (int)floor($keyLifetime / 2));
  $AuthenticationSettings = array(
    'Enabled' => authenticationIsOn(),
    'AllowAnonymous' => (applicationSetting('AllowAnonymousVisitors', TRUE) === TRUE),
    'AllowRegistration' => (applicationSetting('AllowRegistration', TRUE) === TRUE),
    'RequireConfirmation' => (applicationSetting('RequireEmailConfirmation', TRUE) !== FALSE),
    'QuotaMegabytes' => max(0, (int)applicationSetting('UserStorageQuota', 1024)),
    'KeyLifetime' => $keyLifetime,
    'ExchangeInterval' => $exchangeInterval,
    'KeyGrace' => max(3, (int)floor($keyLifetime / 3)),
    'MaximumLifetime' => max(0, (int)applicationSetting('SignInMaximumLifetime', 43200)),
    'MinimumPasswordLength' => max(8, (int)applicationSetting('MinimumPasswordLength', 12)),
    'AttemptLimit' => max(1, (int)applicationSetting('SignInAttemptLimit', 5)),
    'AttemptWindow' => max(60, (int)applicationSetting('SignInAttemptWindow', 900)),
    'UnconfirmedLifetime' => max(600, (int)applicationSetting('UnconfirmedAccountLifetime', 86400)),
    'RecoveryLifetime' => max(300, (int)applicationSetting('RecoveryCodeLifetime', 1800)),
    'AccountLoc' => rtrim((string)applicationSetting('AccountLocation', ''), DIRECTORY_SEPARATOR),
    'UserLoc' => rtrim((string)applicationSetting('UserLocation', ''), DIRECTORY_SEPARATOR),
    'Title' => (string)applicationSetting('ApplicationTitle', engineApplicationName()),
    'Address' => $serverAddress);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $keyLifetime, $exchangeInterval, $serverAddress);
  return $AuthenticationSettings; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read one of this core's own fields from a request.
// / Accepts the field's name & its kind. Returns the value & whether it is one this core may use, in that order.
// / A field that is missing, is not a string or is longer than any field this core takes is not usable.
// / A username, a code, a key & an action are sanitized, & refused when sanitizing changed them, so a value is never quietly altered.
// / An address is checked rather than sanitized, because sanitize() removes the @ every address has.
// / A password is the one field that is neither. It is checked for its length & its encoding, hashed at once, & never written,
// / logged or shown. sanitize() removes characters, & two different passwords it reduced to one would both open the account.
function authenticationInput($fieldName, $fieldKind) {
  // / Set variables.
  global $EnableMemoryProtection;
  $InputValue = '';
  $InputIsUsable = FALSE;
  $rawValue = $cleanValue = '';
  $valueWasCleaned = FALSE;
  if (isset($_POST[$fieldName]) && is_string($_POST[$fieldName]) && strlen($_POST[$fieldName]) <= 2048) $rawValue = (string)$_POST[$fieldName];
  if ($rawValue !== '') {
    if ($fieldKind === 'password') {
      if (strlen($rawValue) <= 1024 && mb_check_encoding($rawValue, 'UTF-8') && strpos($rawValue, "\0") === FALSE) list ($InputValue, $InputIsUsable) = array($rawValue, TRUE); }
    else if ($fieldKind === 'email') {
      if (accountAddressIsUsable($rawValue)) list ($InputValue, $InputIsUsable) = array($rawValue, TRUE); }
    else {
      list ($cleanValue, $valueWasCleaned) = sanitize($rawValue, TRUE);
      if ($valueWasCleaned && $cleanValue === $rawValue) {
        if ($fieldKind === 'username' && accountUsernameIsUsable($cleanValue)) list ($InputValue, $InputIsUsable) = array($cleanValue, TRUE);
        else if ($fieldKind === 'code' && preg_match('/^[A-HJ-NP-Z2-9]{10}$/', strtoupper($cleanValue)) === 1) list ($InputValue, $InputIsUsable) = array(strtoupper($cleanValue), TRUE);
        else if ($fieldKind === 'key' && preg_match('/^[a-f0-9]{96}$/', $cleanValue) === 1) list ($InputValue, $InputIsUsable) = array($cleanValue, TRUE);
        else if ($fieldKind === 'action' && in_array($cleanValue, array('signin', 'signout', 'register', 'confirm', 'resend', 'recover', 'reset', 'exchange', 'page'), TRUE)) list ($InputValue, $InputIsUsable) = array($cleanValue, TRUE);
        else if ($fieldKind === 'page' && in_array($cleanValue, array('signin', 'register', 'confirm', 'recover', 'reset'), TRUE)) list ($InputValue, $InputIsUsable) = array($cleanValue, TRUE); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $rawValue, $cleanValue, $valueWasCleaned, $fieldName, $fieldKind);
  return array($InputValue, $InputIsUsable); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether a string may be a username.
// / Accepts the username. Returns TRUE when it may.
// / A username is 3 to 64 letters, digits, dots, dashes & underscores, & begins with a letter or a digit.
// / It is sanitized too, & refused when sanitizing would change it, so a username is never quietly altered.
function accountUsernameIsUsable($accountUsername) {
  // / Set variables.
  global $EnableMemoryProtection;
  $UsernameIsUsable = FALSE;
  $cleanUsername = '';
  $usernameWasCleaned = FALSE;
  if (is_string($accountUsername) && preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{2,63}$/', $accountUsername) === 1) {
    list ($cleanUsername, $usernameWasCleaned) = sanitize($accountUsername, TRUE);
    $UsernameIsUsable = ($usernameWasCleaned && $cleanUsername === $accountUsername); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cleanUsername, $usernameWasCleaned, $accountUsername);
  return $UsernameIsUsable; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether a string may be an account's email address.
// / Accepts the address. Returns TRUE when it may.
// / Only the plain form is accepted, with no display name, no comment, no quotes & nothing that could end a line of an email.
// / The Email Core checks an address again before it sends to it, because it may be loaded where this core is not.
function accountAddressIsUsable($emailAddress) {
  // / Set variables.
  global $EnableMemoryProtection;
  $AddressIsUsable = FALSE;
  $candidateAddress = is_string($emailAddress) ? $emailAddress : '';
  if (strlen($candidateAddress) >= 6 && strlen($candidateAddress) <= 254 && preg_match('/^[A-Za-z0-9._%+-]{1,64}@[A-Za-z0-9-]+(\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,63}$/', $candidateAddress) === 1 && filter_var($candidateAddress, FILTER_VALIDATE_EMAIL) !== FALSE) $AddressIsUsable = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $candidateAddress, $emailAddress);
  return $AddressIsUsable; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the address a request came from, for counting failures.
// / Accepts nothing. Returns the address, or the word unknown.
// / It is the address the web server saw, & a forwarded header is never trusted, because anybody can send one.
function requestAddress() {
  // / Set variables.
  global $EnableMemoryProtection;
  $RequestAddress = 'unknown';
  $remoteAddress = '';
  $remoteAddress = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '';
  if (filter_var($remoteAddress, FILTER_VALIDATE_IP) !== FALSE) $RequestAddress = $remoteAddress;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $remoteAddress);
  return $RequestAddress; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the folder one kind of record is kept in, & to make it when it is missing.
// / Accepts the kind, which is Users, Codes, SignIns or Limits. Returns whether the folder is there & its path, in that order.
// / Accounts itself is made by -fp as root & given to the web server account, & this makes only the folders below it.
// / A folder is the web server account's & 0700, & one that is a link is never used.
function authenticationFolder($recordKind) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FolderIsReady = FALSE;
  $FolderPath = '';
  $authenticationSettings = array();
  $authenticationSettings = authenticationSettings();
  if ($authenticationSettings['AccountLoc'] !== '' && in_array($recordKind, array('Users', 'Codes', 'SignIns', 'Limits'), TRUE) && is_dir($authenticationSettings['AccountLoc']) && !is_link($authenticationSettings['AccountLoc'])) {
    $FolderPath = $authenticationSettings['AccountLoc'].DIRECTORY_SEPARATOR.$recordKind;
    if (!file_exists($FolderPath) && !is_link($FolderPath)) @mkdir($FolderPath, 0700);
    clearstatcache(TRUE, $FolderPath);
    $FolderIsReady = (is_dir($FolderPath) && !is_link($FolderPath) && is_writable($FolderPath)); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $authenticationSettings, $recordKind);
  return array($FolderIsReady, $FolderPath); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to turn a record into the text of its file.
// / Accepts the record. Returns the text.
// / The first line ends any attempt to run the file as PHP, & the second holds the record as JSON.
// / A record is never included, & the first line is there for a mistake elsewhere rather than for this core.
function authenticationRecordText($authenticationRecord) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RecordText = '';
  $RecordText = '<?php die(\'ERROR!!! Engine-35000, A record cannot be loaded directly!\'.PHP_EOL); ?>'."\n".(string)json_encode($authenticationRecord, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE)."\n";
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $authenticationRecord);
  return $RecordText; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read a record from the text of its file.
// / Accepts the text. Returns whether it held a record & the record, in that order.
// / The text must begin with the line authenticationRecordText() writes, so a file that is anything else is never read as a record.
function parseAuthenticationRecord($recordText) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RecordWasParsed = FALSE;
  $AuthenticationRecord = array();
  $headerLine = $recordBody = '';
  $decodedRecord = NULL;
  $lineBreak = 0;
  $headerLine = '<?php die(\'ERROR!!! Engine-35000, A record cannot be loaded directly!\'.PHP_EOL); ?>';
  $lineBreak = strpos((string)$recordText, "\n");
  if ($lineBreak !== FALSE && substr((string)$recordText, 0, $lineBreak) === $headerLine) {
    $recordBody = (string)substr((string)$recordText, $lineBreak + 1);
    $decodedRecord = json_decode($recordBody, TRUE, 16);
    if (is_array($decodedRecord)) list ($RecordWasParsed, $AuthenticationRecord) = array(TRUE, $decodedRecord); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $headerLine, $recordBody, $decodedRecord, $lineBreak, $recordText);
  return array($RecordWasParsed, $AuthenticationRecord); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the file a record is kept in.
// / Accepts the kind of record & its name. Returns the path, or an empty string when either cannot be used.
// / A name is only ever hexadecimal, so no name a user typed can reach a path.
function authenticationRecordPath($recordKind, $recordName) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RecordPath = '';
  $folderIsReady = FALSE;
  $folderPath = '';
  if (preg_match('/^[a-f0-9]{32,64}$/', (string)$recordName) === 1) {
    list ($folderIsReady, $folderPath) = authenticationFolder($recordKind);
    if ($folderIsReady) $RecordPath = $folderPath.DIRECTORY_SEPARATOR.$recordName.'.php'; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderIsReady, $folderPath, $recordKind, $recordName);
  return $RecordPath; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read a record without changing it.
// / Accepts the path. Returns whether it held a record & the record, in that order.
// / It reads under a shared lock, so it never sees a record half written.
// / A record that is a link, or is larger than any record this core writes, is not read.
function readAuthenticationRecord($recordPath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RecordWasRead = FALSE;
  $AuthenticationRecord = array();
  $recordHandle = NULL;
  $recordText = '';
  clearstatcache(TRUE, (string)$recordPath);
  if ((string)$recordPath !== '' && is_file($recordPath) && !is_link($recordPath) && (int)@filesize($recordPath) <= 65536) $recordHandle = @fopen($recordPath, 'rb');
  if (is_resource($recordHandle)) {
    if (flock($recordHandle, LOCK_SH)) {
      $recordText = (string)stream_get_contents($recordHandle, 65536);
      flock($recordHandle, LOCK_UN); }
    fclose($recordHandle);
    list ($RecordWasRead, $AuthenticationRecord) = parseAuthenticationRecord($recordText); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $recordHandle, $recordText, $recordPath);
  return array($RecordWasRead, $AuthenticationRecord); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write a record that must not exist yet.
// / Accepts the path & the record. Returns TRUE when this call made it.
// / The file is created exclusively, so two requests making the same record cannot both succeed.
// / That is what keeps a username unique, without reading a list of every account first.
// / It is written under a lock taken before anything else could open it, so a reader waits for the whole record.
function createAuthenticationRecord($recordPath, $authenticationRecord) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RecordWasCreated = FALSE;
  $recordHandle = NULL;
  $recordText = '';
  $bytesWritten = 0;
  if ((string)$recordPath !== '') $recordHandle = @fopen($recordPath, 'xb');
  if (is_resource($recordHandle)) {
    @chmod($recordPath, 0600);
    $recordText = authenticationRecordText($authenticationRecord);
    if (flock($recordHandle, LOCK_EX)) {
      $bytesWritten = (int)fwrite($recordHandle, $recordText);
      fflush($recordHandle);
      flock($recordHandle, LOCK_UN); }
    fclose($recordHandle);
    $RecordWasCreated = ($bytesWritten === strlen($recordText));
    // / A record that could not be written whole is removed, so nothing reads half of one.
    if (!$RecordWasCreated) @unlink($recordPath); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $recordHandle, $recordText, $bytesWritten, $recordPath, $authenticationRecord);
  return $RecordWasCreated; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to open a record for changing, & hold it until it is saved or released.
// / Accepts the path & whether a missing record may be started empty. Returns the handle, whether a record was read & the record, in that order.
// / The lock is exclusive & on the file itself, so a change is never lost to another made at the same moment.
// / A record removed while this waited for the lock reads as empty, & so reads as missing.
// / A handle that is not a resource means the record could not be opened, & there is nothing to release.
function lockAuthenticationRecord($recordPath, $startMissing) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RecordHandle = NULL;
  $RecordWasRead = FALSE;
  $AuthenticationRecord = array();
  $recordText = '';
  clearstatcache(TRUE, (string)$recordPath);
  if ((string)$recordPath !== '' && !is_link($recordPath) && ($startMissing or is_file($recordPath))) $RecordHandle = @fopen($recordPath, ($startMissing ? 'c+b' : 'r+b'));
  if (is_resource($RecordHandle)) {
    if ($startMissing) @chmod($recordPath, 0600);
    if (!flock($RecordHandle, LOCK_EX)) {
      fclose($RecordHandle);
      $RecordHandle = NULL; }
    else {
      $recordText = (string)stream_get_contents($RecordHandle, 65536);
      list ($RecordWasRead, $AuthenticationRecord) = parseAuthenticationRecord($recordText); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $recordText, $recordPath, $startMissing);
  return array($RecordHandle, $RecordWasRead, $AuthenticationRecord); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to save a record opened by lockAuthenticationRecord(), & release it.
// / Accepts the handle & the record. Returns TRUE when the whole record was written.
// / The file is emptied & written from its start, while the lock is still held.
function saveAuthenticationRecord($recordHandle, $authenticationRecord) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RecordWasSaved = FALSE;
  $recordText = '';
  $bytesWritten = 0;
  if (is_resource($recordHandle)) {
    $recordText = authenticationRecordText($authenticationRecord);
    if (ftruncate($recordHandle, 0) && rewind($recordHandle)) $bytesWritten = (int)fwrite($recordHandle, $recordText);
    fflush($recordHandle);
    $RecordWasSaved = ($bytesWritten === strlen($recordText));
    flock($recordHandle, LOCK_UN);
    fclose($recordHandle); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $recordText, $bytesWritten, $recordHandle, $authenticationRecord);
  return $RecordWasSaved; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to release a record opened by lockAuthenticationRecord() without changing it.
// / Accepts the handle. Returns TRUE when there was a record to release.
function releaseAuthenticationRecord($recordHandle) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RecordWasReleased = FALSE;
  if (is_resource($recordHandle)) {
    flock($recordHandle, LOCK_UN);
    fclose($recordHandle);
    $RecordWasReleased = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $recordHandle);
  return $RecordWasReleased; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove a record opened by lockAuthenticationRecord(), & release it.
// / Accepts the handle & the path. Returns TRUE when the record is gone.
// / It is emptied before its name is removed, so a request that was waiting for the lock reads nothing rather than what was removed.
function removeAuthenticationRecord($recordHandle, $recordPath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RecordWasRemoved = FALSE;
  if (is_resource($recordHandle)) {
    ftruncate($recordHandle, 0);
    fflush($recordHandle);
    $RecordWasRemoved = (@unlink($recordPath) or !file_exists($recordPath));
    flock($recordHandle, LOCK_UN);
    fclose($recordHandle); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $recordHandle, $recordPath);
  return $RecordWasRemoved; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read this installation's accounts salt, & to make it the first time it is asked for.
// / Accepts nothing. Returns whether the salt is ready & the salt, in that order.
// / Every record's name is a hash of the salt & what it is about, so a folder listing names no account & no address.
// / It is this installation's own & is never the install secret, so a rotation of the secret keeps every account.
// / It is made exclusively & under a lock, so two first requests cannot make two salts.
function accountsSalt() {
  // / Set variables.
  global $EnableMemoryProtection;
  $SaltIsReady = FALSE;
  $AccountsSalt = '';
  $authenticationSettings = array();
  $saltPath = $newSalt = '';
  $saltWasRead = FALSE;
  $saltRecord = array();
  $authenticationSettings = authenticationSettings();
  if ($authenticationSettings['AccountLoc'] !== '' && is_dir($authenticationSettings['AccountLoc']) && !is_link($authenticationSettings['AccountLoc'])) {
    $saltPath = $authenticationSettings['AccountLoc'].DIRECTORY_SEPARATOR.'salt.php';
    if (!file_exists($saltPath) && !is_link($saltPath)) {
      $newSalt = bin2hex(random_bytes(32));
      if (createAuthenticationRecord($saltPath, array('Salt' => $newSalt))) logEntry('The accounts salt was made at '.$saltPath.'.'); }
    list ($saltWasRead, $saltRecord) = readAuthenticationRecord($saltPath);
    if ($saltWasRead && isset($saltRecord['Salt']) && preg_match('/^[a-f0-9]{64}$/', (string)$saltRecord['Salt']) === 1) list ($SaltIsReady, $AccountsSalt) = array(TRUE, (string)$saltRecord['Salt']);
    else warningEntry('The accounts salt at '.$saltPath.' could not be read or made, so no account can be found.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $authenticationSettings, $saltPath, $newSalt, $saltWasRead, $saltRecord);
  return array($SaltIsReady, $AccountsSalt); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name a record from what it is about.
// / Accepts the salt, the kind of name & its subject. Returns 64 hexadecimal digits.
// / A username & an address are lower cased first, so one name cannot be taken twice by its case.
function accountRecordName($accountsSalt, $nameKind, $nameSubject) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RecordName = '';
  $RecordName = hash('sha256', (string)$accountsSalt.'|'.(string)$nameKind.'|'.strtolower((string)$nameSubject));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $accountsSalt, $nameKind, $nameSubject);
  return $RecordName; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to hash a password for keeping.
// / Accepts the password. Returns the hash & the scheme that made it, in that order.
// / Argon2id is used where PHP has it. Otherwise bcrypt is used over an HMAC of the password, because bcrypt reads
// / only the first 72 bytes of what it is given, & two passwords that differ after them would otherwise both open the account.
function hashAccountPassword($accountPassword) {
  // / Set variables.
  global $EnableMemoryProtection;
  $PasswordHash = $PasswordScheme = '';
  $hashedValue = '';
  if (defined('PASSWORD_ARGON2ID')) list ($hashedValue, $PasswordScheme) = array(password_hash((string)$accountPassword, PASSWORD_ARGON2ID), 'argon2id');
  else list ($hashedValue, $PasswordScheme) = array(password_hash(base64_encode(hash_hmac('sha256', (string)$accountPassword, 'HRC2-PASSWORD', TRUE)), PASSWORD_BCRYPT), 'bcrypt-hmac');
  $PasswordHash = is_string($hashedValue) ? $hashedValue : '';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $hashedValue, $accountPassword);
  return array($PasswordHash, $PasswordScheme); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to check a password against the hash kept for it.
// / Accepts the password, the hash & its scheme. Returns TRUE when the password is the one the hash was made from.
// / A missing account is checked against a hash made for nothing, so a wrong username takes as long as a wrong password.
function verifyAccountPassword($accountPassword, $passwordHash, $passwordScheme) {
  // / Set variables.
  global $EnableMemoryProtection;
  $PasswordIsCorrect = FALSE;
  $comparedHash = $comparedScheme = '';
  list ($comparedHash, $comparedScheme) = ((string)$passwordHash === '') ? hashAccountPassword(bin2hex(random_bytes(16))) : array((string)$passwordHash, (string)$passwordScheme);
  if ($comparedScheme === 'bcrypt-hmac') $PasswordIsCorrect = password_verify(base64_encode(hash_hmac('sha256', (string)$accountPassword, 'HRC2-PASSWORD', TRUE)), $comparedHash);
  else $PasswordIsCorrect = password_verify((string)$accountPassword, $comparedHash);
  if ((string)$passwordHash === '') $PasswordIsCorrect = FALSE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $comparedHash, $comparedScheme, $accountPassword, $passwordHash, $passwordScheme);
  return $PasswordIsCorrect; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether a kind of attempt may be made again, from one subject.
// / Accepts the kind & the subject. Returns whether it may & the failures counted, in that order.
// / Failures older than the window are forgotten, & a subject at the limit waits until its oldest failure is.
// / The subject is an account's record name or a request's address, & is hashed with the salt before it names a file.
function attemptIsAllowed($attemptKind, $attemptSubject) {
  // / Set variables.
  global $EnableMemoryProtection;
  $AttemptIsAllowed = FALSE;
  $FailuresCounted = 0;
  $authenticationSettings = $limitRecord = array();
  $saltIsReady = $limitWasRead = FALSE;
  $accountsSalt = $limitPath = '';
  $failureTime = 0;
  $authenticationSettings = authenticationSettings();
  list ($saltIsReady, $accountsSalt) = accountsSalt();
  if ($saltIsReady) {
    $limitPath = authenticationRecordPath('Limits', accountRecordName($accountsSalt, 'limit|'.$attemptKind, $attemptSubject));
    list ($limitWasRead, $limitRecord) = readAuthenticationRecord($limitPath);
    if ($limitWasRead && isset($limitRecord['Failures']) && is_array($limitRecord['Failures'])) foreach ($limitRecord['Failures'] as $failureTime) if ((int)$failureTime > time() - $authenticationSettings['AttemptWindow']) $FailuresCounted++;
    $AttemptIsAllowed = ($FailuresCounted < $authenticationSettings['AttemptLimit']); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $authenticationSettings, $limitRecord, $saltIsReady, $limitWasRead, $accountsSalt, $limitPath, $failureTime, $attemptKind, $attemptSubject);
  return array($AttemptIsAllowed, $FailuresCounted); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to count one failed attempt against a subject.
// / Accepts the kind & the subject. Returns the failures now counted inside the window.
// / The record keeps only the failures inside the window, so it never grows past the limit by much.
// / A subject that reaches the limit is a warning, because it is what an attack that guesses looks like.
function recordFailedAttempt($attemptKind, $attemptSubject) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FailuresCounted = 0;
  $authenticationSettings = $limitRecord = $keptFailures = array();
  $saltIsReady = $limitWasRead = FALSE;
  $accountsSalt = $limitPath = '';
  $limitHandle = NULL;
  $failureTime = 0;
  $authenticationSettings = authenticationSettings();
  list ($saltIsReady, $accountsSalt) = accountsSalt();
  if ($saltIsReady) {
    $limitPath = authenticationRecordPath('Limits', accountRecordName($accountsSalt, 'limit|'.$attemptKind, $attemptSubject));
    list ($limitHandle, $limitWasRead, $limitRecord) = lockAuthenticationRecord($limitPath, TRUE);
    if (is_resource($limitHandle)) {
      if ($limitWasRead && isset($limitRecord['Failures']) && is_array($limitRecord['Failures'])) foreach ($limitRecord['Failures'] as $failureTime) if ((int)$failureTime > time() - $authenticationSettings['AttemptWindow']) $keptFailures[] = (int)$failureTime;
      $keptFailures[] = time();
      $keptFailures = array_slice($keptFailures, -1 * ($authenticationSettings['AttemptLimit'] + 1));
      $FailuresCounted = count($keptFailures);
      saveAuthenticationRecord($limitHandle, array('Kind' => (string)$attemptKind, 'Failures' => $keptFailures));
      if ($FailuresCounted === $authenticationSettings['AttemptLimit']) warningEntry('Attempts of the kind '.$attemptKind.' reached the limit of '.$authenticationSettings['AttemptLimit'].' in '.$authenticationSettings['AttemptWindow'].' seconds, & are refused until the oldest is that old.'); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $authenticationSettings, $limitRecord, $keptFailures, $saltIsReady, $limitWasRead, $accountsSalt, $limitPath, $limitHandle, $failureTime, $attemptKind, $attemptSubject);
  return $FailuresCounted; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to forget the failed attempts counted against a subject.
// / Accepts the kind & the subject. Returns TRUE when nothing is counted against it afterwards.
// / A user who signs in forgets their account's failures, & a request's address keeps its own, so one success does not clear an attack.
function forgetFailedAttempts($attemptKind, $attemptSubject) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FailuresWereForgotten = FALSE;
  $saltIsReady = $limitWasRead = FALSE;
  $accountsSalt = $limitPath = '';
  $limitHandle = NULL;
  $limitRecord = array();
  list ($saltIsReady, $accountsSalt) = accountsSalt();
  if ($saltIsReady) {
    $limitPath = authenticationRecordPath('Limits', accountRecordName($accountsSalt, 'limit|'.$attemptKind, $attemptSubject));
    if ($limitPath !== '' && !file_exists($limitPath)) $FailuresWereForgotten = TRUE;
    else {
      list ($limitHandle, $limitWasRead, $limitRecord) = lockAuthenticationRecord($limitPath, FALSE);
      $FailuresWereForgotten = removeAuthenticationRecord($limitHandle, $limitPath); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $saltIsReady, $limitWasRead, $accountsSalt, $limitPath, $limitHandle, $limitRecord, $attemptKind, $attemptSubject);
  return $FailuresWereForgotten; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether the Email Core is loaded, so a code can be sent.
// / Accepts nothing. Returns TRUE when it is.
function emailCoreIsLoaded() {
  // / Set variables.
  $EmailCoreIsLoaded = function_exists('sendEngineEmail');
  return $EmailCoreIsLoaded; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to find an account by its username.
// / Accepts the username. Returns whether it was found, the account, its record name & the path to its record, in that order.
// / The record name is returned even for an account that does not exist, so a caller can count failures against it all the same.
function accountByUsername($accountUsername) {
  // / Set variables.
  global $EnableMemoryProtection;
  $AccountWasFound = FALSE;
  $AccountRecord = array();
  $AccountName = $AccountPath = '';
  $saltIsReady = FALSE;
  $accountsSalt = '';
  list ($saltIsReady, $accountsSalt) = accountsSalt();
  if ($saltIsReady && (string)$accountUsername !== '') {
    $AccountName = accountRecordName($accountsSalt, 'account', $accountUsername);
    $AccountPath = authenticationRecordPath('Users', $AccountName);
    list ($AccountWasFound, $AccountRecord) = readAuthenticationRecord($AccountPath);
    if ($AccountWasFound && (!isset($AccountRecord['Username']) or strtolower((string)$AccountRecord['Username']) !== strtolower((string)$accountUsername))) list ($AccountWasFound, $AccountRecord) = array(FALSE, array()); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $saltIsReady, $accountsSalt, $accountUsername);
  return array($AccountWasFound, $AccountRecord, $AccountName, $AccountPath); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to issue a code for an account, & keep only its hash.
// / Accepts the account's record name, the purpose & how many seconds it may be used for. Returns whether it was kept & the code, in that order.
// / The purpose is confirm or recover, & a new code replaces the account's last one for the same purpose.
// / A code is ten characters from 32 that cannot be mistaken for each other, so it carries 50 bits & is easy to type.
function issueAccountCode($accountName, $codePurpose, $codeLifetime) {
  // / Set variables.
  global $EnableMemoryProtection;
  $CodeWasKept = FALSE;
  $AccountCode = '';
  $codeAlphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  $saltIsReady = $codeWasRead = FALSE;
  $accountsSalt = $codePath = '';
  $codeHandle = NULL;
  $codeRecord = array();
  $characterIndex = 0;
  list ($saltIsReady, $accountsSalt) = accountsSalt();
  if ($saltIsReady && in_array($codePurpose, array('confirm', 'recover'), TRUE)) {
    for ($characterIndex = 0; $characterIndex < 10; $characterIndex++) $AccountCode .= $codeAlphabet[random_int(0, 31)];
    $codePath = authenticationRecordPath('Codes', accountRecordName($accountsSalt, 'code|'.$codePurpose, $accountName));
    list ($codeHandle, $codeWasRead, $codeRecord) = lockAuthenticationRecord($codePath, TRUE);
    if (is_resource($codeHandle)) $CodeWasKept = saveAuthenticationRecord($codeHandle, array('Purpose' => $codePurpose, 'Account' => (string)$accountName, 'CodeHash' => hash('sha256', $accountsSalt.'|code|'.$AccountCode), 'Expires' => time() + (int)$codeLifetime, 'Tries' => 0)); }
  if (!$CodeWasKept) $AccountCode = '';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $codeAlphabet, $saltIsReady, $codeWasRead, $accountsSalt, $codePath, $codeHandle, $codeRecord, $characterIndex, $accountName, $codePurpose, $codeLifetime);
  return array($CodeWasKept, $AccountCode); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to check a code a user typed against the one kept for their account.
// / Accepts the account's record name, the purpose & the code. Returns whether it was right & what was found, in that order.
// / What was found is correct, wrong, expired or missing. A code is spent when it is right, & removed once it expires or has been tried five times.
// / The fifth wrong try is answered as expired, because the code can no longer be used & the user must ask for another.
// / Five tries of 50 bits each is a chance too small to matter, & the count of a request's failures stops anybody trying more codes.
function checkAccountCode($accountName, $codePurpose, $accountCode) {
  // / Set variables.
  global $EnableMemoryProtection;
  $CodeIsCorrect = FALSE;
  $CodeFinding = 'missing';
  $saltIsReady = $codeWasRead = FALSE;
  $accountsSalt = $codePath = '';
  $codeHandle = NULL;
  $codeRecord = array();
  list ($saltIsReady, $accountsSalt) = accountsSalt();
  if ($saltIsReady) {
    $codePath = authenticationRecordPath('Codes', accountRecordName($accountsSalt, 'code|'.$codePurpose, $accountName));
    list ($codeHandle, $codeWasRead, $codeRecord) = lockAuthenticationRecord($codePath, FALSE);
    if (!is_resource($codeHandle)) $CodeFinding = 'missing';
    else if (!$codeWasRead or !isset($codeRecord['CodeHash'], $codeRecord['Expires'], $codeRecord['Tries']) or (string)($codeRecord['Purpose'] ?? '') !== (string)$codePurpose) {
      removeAuthenticationRecord($codeHandle, $codePath);
      $CodeFinding = 'missing'; }
    else if ((int)$codeRecord['Expires'] < time() or (int)$codeRecord['Tries'] >= 5) {
      removeAuthenticationRecord($codeHandle, $codePath);
      $CodeFinding = 'expired'; }
    else if (hash_equals((string)$codeRecord['CodeHash'], hash('sha256', $accountsSalt.'|code|'.strtoupper((string)$accountCode)))) {
      removeAuthenticationRecord($codeHandle, $codePath);
      list ($CodeIsCorrect, $CodeFinding) = array(TRUE, 'correct'); }
    else {
      $codeRecord['Tries'] = (int)$codeRecord['Tries'] + 1;
      if ($codeRecord['Tries'] >= 5) list ($CodeFinding, $codeWasRead) = array('expired', removeAuthenticationRecord($codeHandle, $codePath));
      else list ($CodeFinding, $codeWasRead) = array('wrong', saveAuthenticationRecord($codeHandle, $codeRecord)); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $saltIsReady, $codeWasRead, $accountsSalt, $codePath, $codeHandle, $codeRecord, $accountName, $codePurpose, $accountCode);
  return array($CodeIsCorrect, $CodeFinding); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to send a code to the address an account keeps.
// / Accepts the account, the purpose, the code & how many seconds it may be used for. Returns TRUE when it was handed to the mail system.
// / Without the Email Core nothing can be sent, & that is a warning every time, because a user is now waiting for a code that will not come.
function sendAccountCode($accountRecord, $codePurpose, $accountCode, $codeLifetime) {
  // / Set variables.
  global $EnableMemoryProtection;
  $CodeWasSent = FALSE;
  $authenticationSettings = array();
  $sendReason = $accountUsername = '';
  $authenticationSettings = authenticationSettings();
  $accountUsername = (string)($accountRecord['Username'] ?? '');
  if (!emailCoreIsLoaded()) warningEntry('The '.$codePurpose.' email for '.$accountUsername.' was not sent, because the Email Core is not loaded. Confirming an address & recovering an account are impossible until it is restored.');
  else list ($CodeWasSent, $sendReason) = sendEngineEmail((string)($accountRecord['Email'] ?? ''), $codePurpose, array('APPLICATION' => $authenticationSettings['Title'], 'USERNAME' => $accountUsername, 'CODE' => $accountCode, 'MINUTES' => (string)max(1, (int)floor((int)$codeLifetime / 60)), 'ADDRESS' => $authenticationSettings['Address']));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $authenticationSettings, $sendReason, $accountUsername, $accountRecord, $codePurpose, $accountCode, $codeLifetime);
  return $CodeWasSent; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to register a new account.
// / Accepts the username, the address & the password. Returns what happened, as one of the outcomes the page names.
// / registered-confirm means a code was sent, registered-unsent that it could not be, & registered-ready that no code is needed.
// / Every attempt is counted against the request's address, so one address cannot make accounts or test usernames without end.
// / A username another account holds is said to be taken, because the user must choose another. An address is never said to be in use,
// / because an address may hold several accounts, so registering tells nobody whether an address is known.
// / The account is made exclusively, so two requests for one username cannot both have it.
function registerAccount($accountUsername, $emailAddress, $accountPassword) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RegistrationOutcome = 'unavailable';
  $authenticationSettings = $accountRecord = $existingRecord = array();
  $attemptIsAllowed = $saltIsReady = $accountWasMade = $codeWasKept = $codeWasSent = $accountExists = FALSE;
  $accountsSalt = $accountName = $accountPath = $passwordHash = $passwordScheme = $accountCode = $existingName = $existingPath = '';
  $failuresCounted = 0;
  $authenticationSettings = authenticationSettings();
  list ($attemptIsAllowed, $failuresCounted) = attemptIsAllowed('register', requestAddress());
  list ($saltIsReady, $accountsSalt) = accountsSalt();
  if (!$authenticationSettings['AllowRegistration']) $RegistrationOutcome = 'registration-closed';
  else if (!$attemptIsAllowed) $RegistrationOutcome = 'too-many';
  else if (mb_strlen((string)$accountPassword, 'UTF-8') < $authenticationSettings['MinimumPasswordLength']) $RegistrationOutcome = 'password-short';
  else if ($saltIsReady) {
    recordFailedAttempt('register', requestAddress());
    $accountName = accountRecordName($accountsSalt, 'account', $accountUsername);
    $accountPath = authenticationRecordPath('Users', $accountName);
    list ($passwordHash, $passwordScheme) = hashAccountPassword($accountPassword);
    $accountRecord = array('Username' => (string)$accountUsername, 'Email' => (string)$emailAddress, 'PasswordHash' => $passwordHash, 'PasswordScheme' => $passwordScheme,
      'Role' => 'user', 'Confirmed' => !$authenticationSettings['RequireConfirmation'], 'Disabled' => FALSE, 'Created' => time(), 'LastSignIn' => 0,
      'Folder' => bin2hex(random_bytes(9)), 'Generation' => 1);
    if ($accountPath !== '' && $passwordHash !== '') $accountWasMade = createAuthenticationRecord($accountPath, $accountRecord);
    list ($accountExists, $existingRecord, $existingName, $existingPath) = accountByUsername($accountUsername);
    if (!$accountWasMade) $RegistrationOutcome = $accountExists ? 'username-taken' : 'unavailable';
    else if (!$authenticationSettings['RequireConfirmation']) $RegistrationOutcome = 'registered-ready';
    else {
      list ($codeWasKept, $accountCode) = issueAccountCode($accountName, 'confirm', $authenticationSettings['UnconfirmedLifetime']);
      if ($codeWasKept) $codeWasSent = sendAccountCode($accountRecord, 'confirm', $accountCode, $authenticationSettings['UnconfirmedLifetime']);
      $RegistrationOutcome = $codeWasSent ? 'registered-confirm' : 'registered-unsent'; }
    if ($accountWasMade) logEntry('An account was registered for '.$accountUsername.'. Outcome: '.$RegistrationOutcome.'.'); }
  if ($RegistrationOutcome === 'unavailable') warningEntry('An account could not be registered for '.(string)$accountUsername.', because the accounts folder could not be written.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $authenticationSettings, $accountRecord, $existingRecord, $attemptIsAllowed, $saltIsReady, $accountWasMade, $codeWasKept, $codeWasSent, $accountExists, $accountsSalt, $accountName, $accountPath, $passwordHash, $passwordScheme, $accountCode, $existingName, $existingPath, $failuresCounted, $accountUsername, $emailAddress, $accountPassword);
  return $RegistrationOutcome; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to confirm a new account's address with the code sent to it.
// / Accepts the username & the code. Returns what happened, as one of the outcomes the page names.
// / A username that has no account is answered as a wrong code is, so confirming tells nobody which usernames exist.
// / Every wrong code is counted against the request's address.
function confirmAccount($accountUsername, $accountCode) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ConfirmationOutcome = 'code-wrong';
  $accountRecord = $lockedRecord = array();
  $attemptIsAllowed = $accountWasFound = $codeIsCorrect = $recordWasRead = FALSE;
  $accountName = $accountPath = $codeFinding = '';
  $accountHandle = NULL;
  $failuresCounted = 0;
  list ($attemptIsAllowed, $failuresCounted) = attemptIsAllowed('code', requestAddress());
  if (!$attemptIsAllowed) $ConfirmationOutcome = 'too-many';
  else {
    list ($accountWasFound, $accountRecord, $accountName, $accountPath) = accountByUsername($accountUsername);
    if ($accountWasFound && !empty($accountRecord['Confirmed'])) $ConfirmationOutcome = 'confirmed';
    else if ($accountWasFound) list ($codeIsCorrect, $codeFinding) = checkAccountCode($accountName, 'confirm', $accountCode);
    if ($codeIsCorrect) {
      list ($accountHandle, $recordWasRead, $lockedRecord) = lockAuthenticationRecord($accountPath, FALSE);
      if ($recordWasRead) {
        $lockedRecord['Confirmed'] = TRUE;
        if (saveAuthenticationRecord($accountHandle, $lockedRecord)) $ConfirmationOutcome = 'confirmed';
        logEntry('The account '.$accountUsername.' confirmed its address.'); }
      else releaseAuthenticationRecord($accountHandle); }
    else if ($codeFinding === 'expired') $ConfirmationOutcome = 'code-expired';
    if ($ConfirmationOutcome === 'code-wrong' or $ConfirmationOutcome === 'code-expired') recordFailedAttempt('code', requestAddress()); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $accountRecord, $lockedRecord, $attemptIsAllowed, $accountWasFound, $codeIsCorrect, $recordWasRead, $accountName, $accountPath, $codeFinding, $accountHandle, $failuresCounted, $accountUsername, $accountCode);
  return $ConfirmationOutcome; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to send a new confirmation code to an account that has not confirmed its address.
// / Accepts the username. Returns resent, or too-many when the request's address has asked too often.
// / The answer is the same whether or not the account exists, so asking tells nobody which usernames exist.
function resendAccountCode($accountUsername) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ResendOutcome = 'resent';
  $authenticationSettings = $accountRecord = array();
  $attemptIsAllowed = $accountWasFound = $codeWasKept = FALSE;
  $accountName = $accountPath = $accountCode = '';
  $failuresCounted = 0;
  $authenticationSettings = authenticationSettings();
  list ($attemptIsAllowed, $failuresCounted) = attemptIsAllowed('resend', requestAddress());
  if (!$attemptIsAllowed) $ResendOutcome = 'too-many';
  else {
    recordFailedAttempt('resend', requestAddress());
    list ($accountWasFound, $accountRecord, $accountName, $accountPath) = accountByUsername($accountUsername);
    if ($accountWasFound && empty($accountRecord['Confirmed']) && empty($accountRecord['Disabled'])) {
      list ($codeWasKept, $accountCode) = issueAccountCode($accountName, 'confirm', $authenticationSettings['UnconfirmedLifetime']);
      if ($codeWasKept) sendAccountCode($accountRecord, 'confirm', $accountCode, $authenticationSettings['UnconfirmedLifetime']); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $authenticationSettings, $accountRecord, $attemptIsAllowed, $accountWasFound, $codeWasKept, $accountName, $accountPath, $accountCode, $failuresCounted, $accountUsername);
  return $ResendOutcome; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to send a recovery code to the address an account keeps.
// / Accepts the username. Returns recovery-sent, or too-many when the request's address has asked too often.
// / The answer is the same whether or not the account exists, so asking tells nobody which usernames exist.
// / Only a confirmed account that may sign in is sent a code, because an unconfirmed address may not be its owner's.
function requestAccountRecovery($accountUsername) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RecoveryOutcome = 'recovery-sent';
  $authenticationSettings = $accountRecord = array();
  $attemptIsAllowed = $accountWasFound = $codeWasKept = FALSE;
  $accountName = $accountPath = $accountCode = '';
  $failuresCounted = 0;
  $authenticationSettings = authenticationSettings();
  list ($attemptIsAllowed, $failuresCounted) = attemptIsAllowed('recover', requestAddress());
  if (!$attemptIsAllowed) $RecoveryOutcome = 'too-many';
  else {
    recordFailedAttempt('recover', requestAddress());
    list ($accountWasFound, $accountRecord, $accountName, $accountPath) = accountByUsername($accountUsername);
    if ($accountWasFound && !empty($accountRecord['Confirmed']) && empty($accountRecord['Disabled'])) {
      list ($codeWasKept, $accountCode) = issueAccountCode($accountName, 'recover', $authenticationSettings['RecoveryLifetime']);
      if ($codeWasKept) sendAccountCode($accountRecord, 'recover', $accountCode, $authenticationSettings['RecoveryLifetime']); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $authenticationSettings, $accountRecord, $attemptIsAllowed, $accountWasFound, $codeWasKept, $accountName, $accountPath, $accountCode, $failuresCounted, $accountUsername);
  return $RecoveryOutcome; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to give an account a new password with the recovery code sent to it.
// / Accepts the username, the code & the new password. Returns what happened, as one of the outcomes the page names.
// / A new password ends every sign in made with the old one, because the account's generation moves on.
// / A username that has no account is answered as a wrong code is, & every wrong code is counted against the request's address.
function resetAccountPassword($accountUsername, $accountCode, $accountPassword) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ResetOutcome = 'code-wrong';
  $authenticationSettings = $accountRecord = $lockedRecord = array();
  $attemptIsAllowed = $accountWasFound = $codeIsCorrect = $recordWasRead = FALSE;
  $accountName = $accountPath = $codeFinding = $passwordHash = $passwordScheme = '';
  $accountHandle = NULL;
  $failuresCounted = 0;
  $authenticationSettings = authenticationSettings();
  list ($attemptIsAllowed, $failuresCounted) = attemptIsAllowed('code', requestAddress());
  if (!$attemptIsAllowed) $ResetOutcome = 'too-many';
  else if (mb_strlen((string)$accountPassword, 'UTF-8') < $authenticationSettings['MinimumPasswordLength']) $ResetOutcome = 'password-short';
  else {
    list ($accountWasFound, $accountRecord, $accountName, $accountPath) = accountByUsername($accountUsername);
    if ($accountWasFound) list ($codeIsCorrect, $codeFinding) = checkAccountCode($accountName, 'recover', $accountCode);
    if ($codeIsCorrect) {
      list ($passwordHash, $passwordScheme) = hashAccountPassword($accountPassword);
      list ($accountHandle, $recordWasRead, $lockedRecord) = lockAuthenticationRecord($accountPath, FALSE);
      if ($recordWasRead && $passwordHash !== '') {
        $lockedRecord['PasswordHash'] = $passwordHash;
        $lockedRecord['PasswordScheme'] = $passwordScheme;
        $lockedRecord['Generation'] = (int)($lockedRecord['Generation'] ?? 1) + 1;
        if (saveAuthenticationRecord($accountHandle, $lockedRecord)) $ResetOutcome = 'reset-done';
        forgetFailedAttempts('signin', $accountName);
        warningEntry('The account '.$accountUsername.' was given a new password with a recovery code, & every sign in made before it was ended.'); }
      else releaseAuthenticationRecord($accountHandle); }
    else if ($codeFinding === 'expired') $ResetOutcome = 'code-expired';
    if ($ResetOutcome === 'code-wrong' or $ResetOutcome === 'code-expired') recordFailedAttempt('code', requestAddress()); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $authenticationSettings, $accountRecord, $lockedRecord, $attemptIsAllowed, $accountWasFound, $codeIsCorrect, $recordWasRead, $accountName, $accountPath, $codeFinding, $passwordHash, $passwordScheme, $accountHandle, $failuresCounted, $accountUsername, $accountCode, $accountPassword);
  return $ResetOutcome; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to bind a sign in to the install secret in use.
// / Accepts the sign in's identifier. Returns an HMAC of it under the install secret.
// / A rotation of the install secret changes every fingerprint, so it ends every sign in & keeps every account.
function signInFingerprint($signInId) {
  // / Set variables.
  global $SecretKey, $EnableMemoryProtection;
  $SignInFingerprint = '';
  $SignInFingerprint = hash_hmac('sha256', 'HRC2-SIGNIN|'.(string)$signInId, (string)$SecretKey);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $signInId);
  return $SignInFingerprint; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to start a sign in for an account, & hand back its first key.
// / Accepts the account's record name & the account. Returns whether it was started & the key, in that order.
// / A key is the sign in's identifier, 32 hexadecimal digits, followed by its secret, 64 more.
// / Only the hash of the secret is kept, so a copy of the folder holds no key that works.
function startSignIn($accountName, $accountRecord) {
  // / Set variables.
  global $EnableMemoryProtection;
  $SignInWasStarted = FALSE;
  $SignInKey = '';
  $authenticationSettings = array();
  $signInId = $keySecret = $signInPath = '';
  $authenticationSettings = authenticationSettings();
  $signInId = bin2hex(random_bytes(16));
  $keySecret = bin2hex(random_bytes(32));
  $signInPath = authenticationRecordPath('SignIns', $signInId);
  if ($signInPath !== '') $SignInWasStarted = createAuthenticationRecord($signInPath, array('Account' => (string)$accountName, 'Generation' => (int)($accountRecord['Generation'] ?? 1),
    'Fingerprint' => signInFingerprint($signInId), 'Created' => time(), 'Until' => ($authenticationSettings['MaximumLifetime'] > 0 ? time() + $authenticationSettings['MaximumLifetime'] : 0),
    'CurrentHash' => hash('sha256', $keySecret), 'CurrentIssued' => time(), 'PreviousHash' => '', 'PreviousRetired' => 0, 'Spent' => array()));
  if ($SignInWasStarted) $SignInKey = $signInId.$keySecret;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $authenticationSettings, $signInId, $keySecret, $signInPath, $accountName, $accountRecord);
  return array($SignInWasStarted, $SignInKey); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to sign a user in with a username & a password.
// / Accepts the username & the password. Returns what happened & the first key of the sign in, in that order.
// / Failures are counted against the account & against the request's address, & either at its limit refuses the attempt before the password is read.
// / A username with no account is checked against a hash made for nothing & counted the same, so a guess learns nothing from the time or the answer.
// / Only a correct password learns that an account is unconfirmed or disabled.
// / Without the Email Core an account cannot be recovered, so every incorrect password is a warning, because its owner has no way back in but the administrator.
function signInAccount($accountUsername, $accountPassword) {
  // / Set variables.
  global $EnableMemoryProtection;
  $SignInOutcome = 'credentials-wrong';
  $SignInKey = '';
  $accountRecord = $lockedRecord = array();
  $accountAllowed = $addressAllowed = $accountWasFound = $passwordIsCorrect = $signInWasStarted = $recordWasRead = FALSE;
  $accountName = $accountPath = $newHash = $newScheme = '';
  $accountHandle = NULL;
  $accountFailures = $addressFailures = 0;
  list ($accountWasFound, $accountRecord, $accountName, $accountPath) = accountByUsername($accountUsername);
  list ($accountAllowed, $accountFailures) = attemptIsAllowed('signin', ($accountName === '' ? 'unknown' : $accountName));
  list ($addressAllowed, $addressFailures) = attemptIsAllowed('signin', requestAddress());
  if ($accountName === '') $SignInOutcome = 'unavailable';
  else if (!$accountAllowed or !$addressAllowed) $SignInOutcome = 'too-many';
  else {
    $passwordIsCorrect = verifyAccountPassword($accountPassword, ($accountWasFound ? (string)($accountRecord['PasswordHash'] ?? '') : ''), (string)($accountRecord['PasswordScheme'] ?? ''));
    if (!$passwordIsCorrect) {
      recordFailedAttempt('signin', $accountName);
      recordFailedAttempt('signin', requestAddress());
      if (!emailCoreIsLoaded()) warningEntry('An incorrect password was entered for '.(string)$accountUsername.'. The account cannot be recovered, because the Email Core is not loaded.');
      else logEntry('An incorrect password was entered for '.(string)$accountUsername.'.'); }
    else if (empty($accountRecord['Confirmed'])) $SignInOutcome = 'unconfirmed';
    else if (!empty($accountRecord['Disabled'])) $SignInOutcome = 'disabled';
    else {
      list ($signInWasStarted, $SignInKey) = startSignIn($accountName, $accountRecord);
      $SignInOutcome = $signInWasStarted ? 'signed-in' : 'unavailable';
      forgetFailedAttempts('signin', $accountName);
      // / A hash made by a scheme this host would no longer choose is replaced while the password is known.
      if ((string)($accountRecord['PasswordScheme'] ?? '') === 'bcrypt-hmac' && defined('PASSWORD_ARGON2ID')) list ($newHash, $newScheme) = hashAccountPassword($accountPassword);
      else if ((string)($accountRecord['PasswordScheme'] ?? '') === 'argon2id' && password_needs_rehash((string)$accountRecord['PasswordHash'], PASSWORD_ARGON2ID)) list ($newHash, $newScheme) = hashAccountPassword($accountPassword);
      list ($accountHandle, $recordWasRead, $lockedRecord) = lockAuthenticationRecord($accountPath, FALSE);
      if ($recordWasRead) {
        $lockedRecord['LastSignIn'] = time();
        if ($newHash !== '') list ($lockedRecord['PasswordHash'], $lockedRecord['PasswordScheme']) = array($newHash, $newScheme);
        saveAuthenticationRecord($accountHandle, $lockedRecord); }
      else releaseAuthenticationRecord($accountHandle);
      logEntry('The account '.(string)$accountUsername.' signed in.'); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $accountRecord, $lockedRecord, $accountAllowed, $addressAllowed, $accountWasFound, $passwordIsCorrect, $signInWasStarted, $recordWasRead, $accountName, $accountPath, $newHash, $newScheme, $accountHandle, $accountFailures, $addressFailures, $accountUsername, $accountPassword);
  return array($SignInOutcome, $SignInKey); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to judge whether a sign in may go on, apart from the key it was presented with.
// / Accepts the sign in. Returns whether it may, the account & the account's record name, in that order.
// / It may not once the install secret has rotated, its longest life has passed, or its account is missing, unconfirmed, disabled or
// / has moved to a new generation with a new password.
function signInIsLive($signInRecord, $signInId) {
  // / Set variables.
  global $EnableMemoryProtection;
  $SignInIsLive = FALSE;
  $AccountRecord = array();
  $AccountName = '';
  $accountPath = '';
  $accountWasRead = FALSE;
  $AccountName = (string)($signInRecord['Account'] ?? '');
  $accountPath = authenticationRecordPath('Users', $AccountName);
  list ($accountWasRead, $AccountRecord) = readAuthenticationRecord($accountPath);
  if ($accountWasRead && hash_equals((string)($signInRecord['Fingerprint'] ?? ''), signInFingerprint($signInId)) && ((int)($signInRecord['Until'] ?? 0) === 0 or (int)$signInRecord['Until'] > time())
    && !empty($AccountRecord['Confirmed']) && empty($AccountRecord['Disabled']) && (int)($AccountRecord['Generation'] ?? 1) === (int)($signInRecord['Generation'] ?? 0)) $SignInIsLive = TRUE;
  if (!$SignInIsLive) $AccountRecord = array();
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $accountPath, $accountWasRead, $signInRecord, $signInId);
  return array($SignInIsLive, $AccountRecord, $AccountName); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to judge a key presented with an ordinary request, without spending it.
// / Accepts the key. Returns whether it signs the request in, the account, its record name & the sign in's identifier, in that order.
// / The current key is good until its life is over, & the key before it for a short grace after it was exchanged, so a request sent
// / just before an exchange is still signed in when it arrives.
function presentSignInKey($signInKey) {
  // / Set variables.
  global $EnableMemoryProtection;
  $KeyIsValid = FALSE;
  $AccountRecord = array();
  $AccountName = $SignInId = '';
  $authenticationSettings = $signInRecord = array();
  $signInPath = $secretHash = '';
  $signInWasRead = $signInIsLive = FALSE;
  $authenticationSettings = authenticationSettings();
  if (preg_match('/^[a-f0-9]{96}$/', (string)$signInKey) === 1) {
    $SignInId = substr($signInKey, 0, 32);
    $secretHash = hash('sha256', substr($signInKey, 32));
    $signInPath = authenticationRecordPath('SignIns', $SignInId);
    list ($signInWasRead, $signInRecord) = readAuthenticationRecord($signInPath);
    if ($signInWasRead) list ($signInIsLive, $AccountRecord, $AccountName) = signInIsLive($signInRecord, $SignInId);
    if ($signInIsLive && hash_equals((string)($signInRecord['CurrentHash'] ?? ''), $secretHash) && (int)($signInRecord['CurrentIssued'] ?? 0) + $authenticationSettings['KeyLifetime'] >= time()) $KeyIsValid = TRUE;
    else if ($signInIsLive && (string)($signInRecord['PreviousHash'] ?? '') !== '' && hash_equals((string)$signInRecord['PreviousHash'], $secretHash) && (int)($signInRecord['PreviousRetired'] ?? 0) + $authenticationSettings['KeyGrace'] >= time()) $KeyIsValid = TRUE; }
  if (!$KeyIsValid) list ($AccountRecord, $AccountName, $SignInId) = array(array(), '', '');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $authenticationSettings, $signInRecord, $signInPath, $secretHash, $signInWasRead, $signInIsLive, $signInKey);
  return array($KeyIsValid, $AccountRecord, $AccountName, $SignInId); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to exchange a page's key for a new one, which spends it.
// / Accepts the key. Returns what happened & the new key, in that order.
// / exchanged hands back a new key. signed-out means the key does not sign anything in, & the page should say so.
// / ended means the key had already been spent. Only a copy of it could be presented now, so the sign in is ended for every holder,
// / the page included, & that is a warning, because somebody else may have held the key.
// / The sign in is changed under its lock, so two exchanges of one key cannot both succeed.
function exchangeSignInKey($signInKey) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ExchangeOutcome = 'signed-out';
  $NewSignInKey = '';
  $authenticationSettings = $signInRecord = $accountRecord = $spentHashes = array();
  $signInId = $secretHash = $signInPath = $newSecret = $accountName = '';
  $signInHandle = NULL;
  $signInWasRead = $signInIsLive = FALSE;
  $authenticationSettings = authenticationSettings();
  if (preg_match('/^[a-f0-9]{96}$/', (string)$signInKey) === 1) {
    $signInId = substr($signInKey, 0, 32);
    $secretHash = hash('sha256', substr($signInKey, 32));
    $signInPath = authenticationRecordPath('SignIns', $signInId);
    list ($signInHandle, $signInWasRead, $signInRecord) = lockAuthenticationRecord($signInPath, FALSE);
    if ($signInWasRead) list ($signInIsLive, $accountRecord, $accountName) = signInIsLive($signInRecord, $signInId);
    $spentHashes = ($signInWasRead && isset($signInRecord['Spent']) && is_array($signInRecord['Spent'])) ? $signInRecord['Spent'] : array();
    if (!is_resource($signInHandle)) $ExchangeOutcome = 'signed-out';
    else if (!$signInWasRead or !$signInIsLive) {
      removeAuthenticationRecord($signInHandle, $signInPath);
      $ExchangeOutcome = 'signed-out'; }
    else if (hash_equals((string)($signInRecord['CurrentHash'] ?? ''), $secretHash) && (int)($signInRecord['CurrentIssued'] ?? 0) + $authenticationSettings['KeyLifetime'] >= time()) {
      $newSecret = bin2hex(random_bytes(32));
      if ((string)($signInRecord['PreviousHash'] ?? '') !== '') $spentHashes[] = (string)$signInRecord['PreviousHash'];
      $signInRecord['Spent'] = array_slice($spentHashes, -8);
      list ($signInRecord['PreviousHash'], $signInRecord['PreviousRetired']) = array((string)$signInRecord['CurrentHash'], time());
      list ($signInRecord['CurrentHash'], $signInRecord['CurrentIssued']) = array(hash('sha256', $newSecret), time());
      if (saveAuthenticationRecord($signInHandle, $signInRecord)) list ($ExchangeOutcome, $NewSignInKey) = array('exchanged', $signInId.$newSecret); }
    else if (hash_equals((string)($signInRecord['PreviousHash'] ?? ''), $secretHash) or in_array($secretHash, $spentHashes, TRUE)) {
      removeAuthenticationRecord($signInHandle, $signInPath);
      $ExchangeOutcome = 'ended';
      warningEntry('A spent sign in key for '.(string)($accountRecord['Username'] ?? 'an account').' was presented to be exchanged again, so the sign in was ended. Somebody else may have held a copy of the key.'); }
    else if ((int)($signInRecord['CurrentIssued'] ?? 0) + $authenticationSettings['KeyLifetime'] < time()) {
      removeAuthenticationRecord($signInHandle, $signInPath);
      $ExchangeOutcome = 'signed-out'; }
    else releaseAuthenticationRecord($signInHandle); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $authenticationSettings, $signInRecord, $accountRecord, $spentHashes, $signInId, $secretHash, $signInPath, $newSecret, $accountName, $signInHandle, $signInWasRead, $signInIsLive, $signInKey);
  return array($ExchangeOutcome, $NewSignInKey); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to sign a page out, which ends its sign in.
// / Accepts the key. Returns TRUE when the sign in it named is over, whether or not it had been already.
// / Only a key that signs a request in can end a sign in, so a spent copy cannot sign anybody out.
function signOutKey($signInKey) {
  // / Set variables.
  global $EnableMemoryProtection;
  $SignInWasEnded = FALSE;
  $accountRecord = $signInRecord = array();
  $keyIsValid = $signInWasRead = FALSE;
  $accountName = $signInId = $signInPath = '';
  $signInHandle = NULL;
  list ($keyIsValid, $accountRecord, $accountName, $signInId) = presentSignInKey($signInKey);
  if (!$keyIsValid) $SignInWasEnded = TRUE;
  else {
    $signInPath = authenticationRecordPath('SignIns', $signInId);
    list ($signInHandle, $signInWasRead, $signInRecord) = lockAuthenticationRecord($signInPath, FALSE);
    $SignInWasEnded = removeAuthenticationRecord($signInHandle, $signInPath);
    logEntry('The account '.(string)($accountRecord['Username'] ?? '').' signed out.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $accountRecord, $signInRecord, $keyIsValid, $signInWasRead, $accountName, $signInId, $signInPath, $signInHandle, $signInKey);
  return $SignInWasEnded; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the folder a signed in user's files are kept in, & to make it the first time it is needed.
// / Accepts the account. Returns whether the folder is there & its path with a separator at its end, in that order.
// / The folder is named for the account's own random identifier, never for its username, so a path names nobody.
function accountFolderPath($accountRecord) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FolderIsReady = FALSE;
  $FolderPath = '';
  $authenticationSettings = array();
  $folderName = '';
  $authenticationSettings = authenticationSettings();
  $folderName = (string)($accountRecord['Folder'] ?? '');
  if ($authenticationSettings['UserLoc'] !== '' && preg_match('/^[a-f0-9]{18}$/', $folderName) === 1 && is_dir($authenticationSettings['UserLoc']) && !is_link($authenticationSettings['UserLoc'])) {
    $FolderPath = $authenticationSettings['UserLoc'].DIRECTORY_SEPARATOR.$folderName;
    if (!file_exists($FolderPath) && !is_link($FolderPath)) @mkdir($FolderPath, 0700);
    clearstatcache(TRUE, $FolderPath);
    $FolderIsReady = (is_dir($FolderPath) && !is_link($FolderPath) && is_writable($FolderPath));
    $FolderPath .= DIRECTORY_SEPARATOR; }
  if (!$FolderIsReady) $FolderPath = '';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $authenticationSettings, $folderName, $accountRecord);
  return array($FolderIsReady, $FolderPath); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to carry out one of the actions a page asks this core for.
// / Accepts the action. Returns the reply, which names the outcome & carries a key, its life & how often to exchange it where there is one.
// / A field the action needs that cannot be used is answered as the matching failure, without anything being tried.
// / An action that would tell a stranger whether a username exists answers the same either way.
function answerAuthenticationAction($authenticationAction) {
  // / Set variables.
  global $EnableMemoryProtection;
  $AuthenticationReply = array();
  $authenticationSettings = array();
  $actionOutcome = $signInKey = '';
  $accountUsername = $emailAddress = $accountPassword = $accountCode = $presentedKey = '';
  $usernameIsUsable = $addressIsUsable = $passwordIsUsable = $codeIsUsable = $keyIsUsable = FALSE;
  $authenticationSettings = authenticationSettings();
  list ($accountUsername, $usernameIsUsable) = authenticationInput('SignInUsername', 'username');
  list ($emailAddress, $addressIsUsable) = authenticationInput('SignInEmail', 'email');
  list ($accountPassword, $passwordIsUsable) = authenticationInput('SignInPassword', 'password');
  list ($accountCode, $codeIsUsable) = authenticationInput('SignInCode', 'code');
  list ($presentedKey, $keyIsUsable) = authenticationInput('SignInKey', 'key');
  if ($authenticationAction === 'signin') list ($actionOutcome, $signInKey) = ($usernameIsUsable && $passwordIsUsable) ? signInAccount($accountUsername, $accountPassword) : array('credentials-wrong', '');
  else if ($authenticationAction === 'exchange') list ($actionOutcome, $signInKey) = $keyIsUsable ? exchangeSignInKey($presentedKey) : array('signed-out', '');
  else if ($authenticationAction === 'signout') {
    // / A page that signs out is answered signed out whatever its key was, because it is signed out either way.
    if ($keyIsUsable) signOutKey($presentedKey);
    $actionOutcome = 'signed-out'; }
  else if ($authenticationAction === 'register') {
    if (!$usernameIsUsable) $actionOutcome = 'username-invalid';
    else if (!$addressIsUsable) $actionOutcome = 'email-invalid';
    else if (!$passwordIsUsable) $actionOutcome = 'password-short';
    else $actionOutcome = registerAccount($accountUsername, $emailAddress, $accountPassword); }
  else if ($authenticationAction === 'confirm') $actionOutcome = ($usernameIsUsable && $codeIsUsable) ? confirmAccount($accountUsername, $accountCode) : 'code-wrong';
  else if ($authenticationAction === 'resend') $actionOutcome = $usernameIsUsable ? resendAccountCode($accountUsername) : 'resent';
  else if ($authenticationAction === 'recover') $actionOutcome = $usernameIsUsable ? requestAccountRecovery($accountUsername) : 'recovery-sent';
  else if ($authenticationAction === 'reset') {
    if (!$passwordIsUsable) $actionOutcome = 'password-short';
    else $actionOutcome = ($usernameIsUsable && $codeIsUsable) ? resetAccountPassword($accountUsername, $accountCode, $accountPassword) : 'code-wrong'; }
  else $actionOutcome = 'unavailable';
  $AuthenticationReply = array('Outcome' => $actionOutcome, 'Key' => $signInKey, 'Life' => $authenticationSettings['KeyLifetime'], 'Interval' => $authenticationSettings['ExchangeInterval']);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $authenticationSettings, $actionOutcome, $signInKey, $accountUsername, $emailAddress, $accountPassword, $accountCode, $presentedKey, $usernameIsUsable, $addressIsUsable, $passwordIsUsable, $codeIsUsable, $keyIsUsable, $authenticationAction);
  return $AuthenticationReply; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write the one line a page reads as this core's answer.
// / Accepts the reply. Returns TRUE when it was written.
// / It is one line of JSON under the key SignIn, as the Workflow Core answers under Workflow, & the page reads the last such line.
// / The characters HTML gives meaning to are escaped, so the line is inert if anything shows it as a page.
function printAuthenticationReply($authenticationReply) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ReplyWasPrinted = FALSE;
  $encodedReply = '';
  $encodedReply = (string)json_encode(array('SignIn' => $authenticationReply), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);
  if ($encodedReply !== '') {
    if (!headers_sent()) header('Cache-Control: no-store');
    print($encodedReply.PHP_EOL);
    $ReplyWasPrinted = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $encodedReply, $authenticationReply);
  return $ReplyWasPrinted; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide who a request is from, & whether this core answers it.
// / Accepts nothing. Returns how the request is answered: an empty string for the application, page to draw a page of this core's, or answered.
// / answered means the request was one of this core's actions & its reply was written, so the application draws nothing & runs nothing.
// / page means the request may not reach the application, because a visitor must sign in first or asked for one of this core's pages.
// / A request presenting a key that signs it in names its account in $SignedInAccount, & its files are kept in $SignedInFolder.
// / $EnginePageState holds everything a page may show about it, & nothing a page could use to sign in.
// / A visitor who must sign in where nobody can, because the accounts folder is missing, is error 43000. The page still says signing in is unavailable.
// / Only a web request served as the web server account is judged. The command line signs nobody in.
function adoptAuthenticationIdentity() {
  // / Set variables.
  global $RunningFromCLI, $CurrentUser, $ApacheUser, $SignedInAccount, $SignedInFolder, $EnginePageState, $EnableMemoryProtection;
  $EngineAnswer = '';
  $authenticationSettings = $authenticationReply = $accountRecord = array();
  $authenticationAction = $presentedKey = $requestedPage = $accountName = $signInId = $folderPath = $emailReason = '';
  $actionIsUsable = $keyIsUsable = $pageIsUsable = $keyIsValid = $folderIsReady = $saltIsReady = $accountsAreAvailable = $emailIsAvailable = FALSE;
  $accountsSalt = '';
  $authenticationSettings = authenticationSettings();
  $SignedInAccount = array();
  $SignedInFolder = '';
  if (function_exists('emailIsAvailable')) list ($emailIsAvailable, $emailReason) = emailIsAvailable();
  $EnginePageState = array('Enabled' => $authenticationSettings['Enabled'], 'SignedIn' => FALSE, 'Username' => '', 'Role' => '', 'Page' => '', 'Available' => FALSE,
    'AllowAnonymous' => $authenticationSettings['AllowAnonymous'], 'AllowRegistration' => $authenticationSettings['AllowRegistration'], 'RequireConfirmation' => $authenticationSettings['RequireConfirmation'],
    'EmailAvailable' => (emailCoreIsLoaded() && $emailIsAvailable), 'MinimumPasswordLength' => $authenticationSettings['MinimumPasswordLength'],
    'KeyLifetime' => $authenticationSettings['KeyLifetime'], 'ExchangeInterval' => $authenticationSettings['ExchangeInterval']);
  if ($authenticationSettings['Enabled'] && !$RunningFromCLI && (string)$CurrentUser === (string)$ApacheUser) {
    list ($saltIsReady, $accountsSalt) = accountsSalt();
    $accountsAreAvailable = ($saltIsReady && $authenticationSettings['UserLoc'] !== '' && is_dir($authenticationSettings['UserLoc']) && !is_link($authenticationSettings['UserLoc']) && is_writable($authenticationSettings['UserLoc']));
    $EnginePageState['Available'] = $accountsAreAvailable;
    list ($authenticationAction, $actionIsUsable) = authenticationInput('SignInAction', 'action');
    list ($presentedKey, $keyIsUsable) = authenticationInput('SignInKey', 'key');
    list ($requestedPage, $pageIsUsable) = authenticationInput('SignInPage', 'page');
    if ($actionIsUsable && $authenticationAction !== 'page') {
      $authenticationReply = $accountsAreAvailable ? answerAuthenticationAction($authenticationAction) : array('Outcome' => 'unavailable', 'Key' => '', 'Life' => $authenticationSettings['KeyLifetime'], 'Interval' => $authenticationSettings['ExchangeInterval']);
      if (printAuthenticationReply($authenticationReply)) $EngineAnswer = 'answered'; }
    else {
      if ($accountsAreAvailable && $keyIsUsable) list ($keyIsValid, $accountRecord, $accountName, $signInId) = presentSignInKey($presentedKey);
      if ($keyIsValid) list ($folderIsReady, $folderPath) = accountFolderPath($accountRecord);
      if ($keyIsValid && !$folderIsReady) errorEntry('The folder for the files of '.(string)($accountRecord['Username'] ?? '').' could not be made in '.$authenticationSettings['UserLoc'].', so they were not signed in!', 43001, FALSE);
      else if ($keyIsValid) {
        $SignedInAccount = array('Name' => $accountName, 'Username' => (string)($accountRecord['Username'] ?? ''), 'Role' => (((string)($accountRecord['Role'] ?? '') === 'admin') ? 'admin' : 'user'), 'Folder' => (string)($accountRecord['Folder'] ?? ''), 'SignIn' => $signInId);
        $SignedInFolder = $folderPath;
        list ($EnginePageState['SignedIn'], $EnginePageState['Username'], $EnginePageState['Role']) = array(TRUE, $SignedInAccount['Username'], $SignedInAccount['Role']); }
      if (!$EnginePageState['SignedIn'] && $actionIsUsable && $pageIsUsable) list ($EnginePageState['Page'], $EngineAnswer) = array($requestedPage, 'page');
      else if (!$EnginePageState['SignedIn'] && !$authenticationSettings['AllowAnonymous']) list ($EnginePageState['Page'], $EngineAnswer) = array('signin', 'page');
      if (!$accountsAreAvailable && !$authenticationSettings['AllowAnonymous']) errorEntry('A visitor must sign in & nobody can, because the accounts folder or the users folder is missing or cannot be written. Run -fp as root!', 43000, FALSE); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $authenticationSettings, $authenticationReply, $accountRecord, $authenticationAction, $presentedKey, $requestedPage, $accountName, $signInId, $folderPath, $emailReason, $actionIsUsable, $keyIsUsable, $pageIsUsable, $keyIsValid, $folderIsReady, $saltIsReady, $accountsAreAvailable, $emailIsAvailable, $accountsSalt);
  return $EngineAnswer; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to continue as the signed in user a workflow worker was started for.
// / Accepts the name of the account's record, as the Engine handed it to the worker. Returns TRUE when the worker acts for that user.
// / Only a worker running as the web server account adopts one, & nothing is trusted yet. The Workflow Core checks the worker's startup key,
// / which is bound to the account as well as to the session & the job, before anything is acted on.
// / The request that started the job was signed in, so no sign in is needed here, & a user who signs out does not stop a workflow they started.
// / An account that is gone or disabled, or whose folder cannot be made, leaves the worker acting for nobody, so it finds no job & stops.
function adoptWorkerIdentity($accountName) {
  // / Set variables.
  global $RunningFromCLI, $RunningAsRoot, $CurrentUser, $ApacheUser, $SignedInAccount, $SignedInFolder, $EnableMemoryProtection;
  $IdentityWasAdopted = FALSE;
  $authenticationSettings = $accountRecord = array();
  $accountWasRead = $folderIsReady = FALSE;
  $folderPath = '';
  $authenticationSettings = authenticationSettings();
  if ($authenticationSettings['Enabled'] && $RunningFromCLI === TRUE && $RunningAsRoot !== TRUE && (string)$CurrentUser === (string)$ApacheUser && preg_match('/^[a-f0-9]{64}$/', (string)$accountName) === 1) {
    list ($accountWasRead, $accountRecord) = readAuthenticationRecord(authenticationRecordPath('Users', (string)$accountName));
    if ($accountWasRead && empty($accountRecord['Disabled'])) list ($folderIsReady, $folderPath) = accountFolderPath($accountRecord);
    if (!$folderIsReady) warningEntry('A workflow worker was handed an account that is gone or disabled or has no folder, so it acts for nobody.');
    else {
      $SignedInAccount = array('Name' => (string)$accountName, 'Username' => (string)($accountRecord['Username'] ?? ''), 'Role' => (((string)($accountRecord['Role'] ?? '') === 'admin') ? 'admin' : 'user'), 'Folder' => (string)($accountRecord['Folder'] ?? ''), 'SignIn' => '');
      $SignedInFolder = $folderPath;
      $IdentityWasAdopted = TRUE;
      logEntry('A workflow worker acts for '.$SignedInAccount['Username'].'.'); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $authenticationSettings, $accountRecord, $accountWasRead, $folderIsReady, $folderPath, $accountName);
  return $IdentityWasAdopted; }
// / -----------------------------------------------------------------------------------
?>
