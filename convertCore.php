<?php if (php_sapi_name() !== 'cli') print('<!DOCTYPE HTML>'.PHP_EOL);
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRConvert2, Copyright on 9/22/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / Application Information ...
// / This application is designed to provide a web-interface for converting file formats
// / on a server for users of any web browser without authentication.
// /
// / File Information ...
// / v4.1.5.
// / HRConvert2 Convert Core.
// / This file contains the core logic of the application.
// /
// / Hardware Requirements ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / Dependency Requirements ...
// / The authoritative list of dependencies is Resources/Contract/depends.php & nothing else.
// / Run  php convertCore.php -v  to see what this installation has.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to produce random number variables for truly unique per-session identifiers.
// / This function uses the built-in PHP "random_int" function to generate the random number.
// / This function does not generate its own number. It produces a number from random_int().
// / Random number range is hardcoded to 100000000000000000 to 999999999999999999 for consistency.
// / Returns FALSE in $RandomNumberCheck if the system entropy source was unavailable.
// / The caller MUST check $RandomNumberCheck. A predictable identifier is worse than none.
function generateRandomNumber() {
  // / Set variables.
  global $EnableMemoryProtection;
  $RandomNumber = FALSE;
  $RandomNumberCheck = TRUE;
  // / random_int() throws rather than returning a poor result when entropy is unavailable.
  try { $RandomNumber = random_int(100000000000000000, 999999999999999999); }
  catch (Throwable $error) { $RandomNumberCheck = FALSE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $error);
  return array($RandomNumber, $RandomNumberCheck); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to confirm the config.php file carries every setting this core requires.
// / A config file from an older release will be missing settings the core now depends on.
// / An undefined setting evaluates as NULL, which silently reads as FALSE or zero.
// / A missing timeout would become zero & a missing whitelist would become empty, without warning.
// / Detecting the absence outright is the only way an administrator learns what actually happened.
// / $ConfigVersion is advisory & explains WHY settings are missing.
// / The variable list is authoritative & reports WHICH settings are missing.
// / A config file predating the version stamp will report no version rather than a wrong one.
// / Version stamps carry a leading v which must be stripped before any numeric comparison.
// / Casting 'v3' to an integer yields 0, which would silently reduce this to a minor & patch check.
function verifyConfigVersion($RequiredConfigVersion) {
  // / Set variables.
  global $ConfigVersion, $EnableMemoryProtection;
  $ConfigIsValid = TRUE;
  $MissingConfigVars = array();
  $DetectedConfigVersion = '';
  $requiredConfigVars = $configVersionParts = $requiredVersionParts = array();
  $requiredConfigVar = $cleanConfigVersion = $cleanRequiredVersion = '';
  $configVersionIsCurrent = FALSE;
  // / Every setting the core reads from config.php.
  // / A setting added to config.php must be added here or this check stops covering it.
  $requiredConfigVars = array(
    'ConfigVersion',
    'URL', 'InstLoc', 'ServerRootDir', 'ConvertLoc', 'LogDir', 'HomeLoc', 'ProprietaryLoc',
    'ApplicationName', 'ApplicationTitle', 'Verbose', 'MaxLogSize', 'DeleteThreshold',
    'BackupLoc', 'UniqueDailyLogHash', 'AppendLogHashToLogFiles',
    'VirusScan', 'AllowUserVirusScan', 'ScanCoreMemoryLimit', 'ScanCoreChunkSize',
    'ScanCoreDebug', 'ScanCoreVerbose', 'EnableMemoryProtection',
    'SupportedLanguages', 'DefaultLanguage', 'AllowUserSelectableLanguage',
    'SupportedGuis', 'DefaultGui', 'AllowUserSelectableGui',
    'SupportedColors', 'AllowUserSelectableColor', 'ButtonStyle',
    'Font', 'SpinnerStyle', 'SpinnerColor', 'UserEbookInputArray',
    'ShowGUI', 'ShowFinePrint', 'TOSURL', 'PPURL', 'LogoURL', 'AllowUserShare', 'AllowUserIdentify', 'EnableWorkflows',
    // / A setting nothing reads is not required, because every installation would carry it for no reason.
    'SupportedConversionTypes', 'RetryCount',
    'RARArchiveMethod', 'AllowBootableIsoImage',
    'DeleteBuildEnvironment', 'DeleteDevelopmentDocumentation', 'UserEbookOutputArray',
    'UserArchiveArray', 'UserDearchiveArray', 'UserDocumentArray', 'UserSpreadsheetArray',
    'UserPresentationInputArray', 'UserPresentationOutputArray',
    'UserXPSInputArray', 'UserXPSOutputArray', 'UserImageArray',
    'UserMediaInputArray', 'UserMediaOutputArray', 'UserBootableIsoArray',
    'UserVideoInputArray', 'UserVideoOutputArray', 'UserStreamArray',
    'UserDrawingArray', 'UserSVGInputArray', 'UserSVGOutputArray', 'UserModelArray',
    'UserModelInputArray', 'UserModelOutputArray', 'EnvironmentManagerMayRepair', 'AllowUserURLDownload', 'DefaultVirusScanner',
    'URLDownloadMaximumBytes', 'URLDownloadsPerSession', 'ScanURLDownloads', 'PermittedPrivateRanges', 'GuiMaxWidth',
    'EnvironmentManagerMayRewriteConfigs',
    'UserDrawingInputArray', 'UserDrawingOutputArray', 'UserImageInputArray',
    'UserImageOutputArray', 'UserSCADInputArray', 'UserSCADOutputArray',
    'UserSCADArray', 'UserSubtitleInputArray', 'UserSubtitleOutputArray', 'UserPDFWorkArr',
    'AllowStreamOverHTTP', 'StreamWatchTimeout', 'StreamConnectionTimeout', 'StreamInspectionLayers', 'StreamInspectionFilesPerLayer', 'DefaultStreamInspectionForfeitAction', 'MaxStreamInspectionFileSize', 'RequireSandboxOnDocker',
    'AllowSCADIncludeResolution', 'SCADConversionTimeout', 'RequireSandbox', 'ThrowSandboxWarning',
    'UsePyMeshLab', 'EnableAutoUpdates',
    'AutoUpdateTargetVersion', 'UpdateSourceRepository', 'MaxUpdatePackageSize', 'UpdateConnectionTimeout',
    'EnableResourceAwareness', 'RequireResourceAwareness', 'CoreManagerSubprocessPollInterval',
    'ResourcePollInterval', 'WorkerReapInterval', 'WorkerStaleGracePeriod', 'TotalResourceBudget',
    'ReserveResourcePercentage', 'MaxConcurrentWorkers', 'MaxExpectedRuntime', 'MaxRuntimeExtensions',
    'DefaultConversionCost', 'DefaultExpectedRuntime', 'LowStorageWarning', 'StorageReserve', 'MaintainHTAccess',
    'MaxWorkflowSteps', 'MaxWorkflowFiles', 'MaxWorkflowRuntime', 'MaxConcurrentWorkflows', 'EnableWorkflowBuilder', 'EnableWorkflowImportExport');
  // / Check that every required setting actually exists in the global scope.
  // / config.php is required at global scope, so every setting it defines lands in $GLOBALS.
  foreach ($requiredConfigVars as $requiredConfigVar) {
    // / isset RATHER THAN array_key_exists, & the difference is the whole check.
    // / A global declaration anywhere in this file creates the key in $GLOBALS with a NULL
    // / value, before config.php has assigned anything. array_key_exists then answers yes
    // / for a setting the configuration never mentioned, so the check passed for every
    // / required setting that some function had merely declared global.
    // / Two settings were absent from config.php & from the template for an entire release
    // / & this reported a valid configuration.
    // / isset is FALSE for NULL & TRUE for FALSE, 0 & the empty string, which is exactly
    // / the distinction wanted. A setting legitimately set to FALSE still passes.
    if (!isset($GLOBALS[$requiredConfigVar])) array_push($MissingConfigVars, $requiredConfigVar); }
  if (!empty($MissingConfigVars)) $ConfigIsValid = FALSE;
  // / Compare the config version stamp against the version this core was written for.
  // / This is advisory. It explains why settings are missing but never passes a config that is incomplete.
  // / isset for the reason given above. The global declaration at the top of this function
  // / creates the key, so array_key_exists always answered yes & NONE was never reported.
  $DetectedConfigVersion = isset($GLOBALS['ConfigVersion']) ? (string)$ConfigVersion : '';
  if ($DetectedConfigVersion !== '') {
    $cleanConfigVersion = ltrim($DetectedConfigVersion, 'vV');
    $cleanRequiredVersion = ltrim($RequiredConfigVersion, 'vV');
    $configVersionParts = explode('.', $cleanConfigVersion);
    $requiredVersionParts = explode('.', $cleanRequiredVersion);
    // / Compare numerically, never as strings.
    // / A string comparison would rank version 3.10 below version 3.9.
    if ((int)($configVersionParts[0] ?? 0) > (int)($requiredVersionParts[0] ?? 0)) $configVersionIsCurrent = TRUE;
    elseif ((int)($configVersionParts[0] ?? 0) === (int)($requiredVersionParts[0] ?? 0)) {
      if ((int)($configVersionParts[1] ?? 0) > (int)($requiredVersionParts[1] ?? 0)) $configVersionIsCurrent = TRUE;
      elseif ((int)($configVersionParts[1] ?? 0) === (int)($requiredVersionParts[1] ?? 0) && (int)($configVersionParts[2] ?? 0) >= (int)($requiredVersionParts[2] ?? 0)) $configVersionIsCurrent = TRUE; } }
  // / A config file with no version stamp at all predates this check entirely.
  else $DetectedConfigVersion = 'NONE';
  // / Sanity check that the config.php file is valid.
  // / $ConfigIsValid should already be false by now, but just in case.
  if (!$configVersionIsCurrent && !$ConfigIsValid) $ConfigIsValid = FALSE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $requiredConfigVars, $requiredConfigVar, $configVersionParts, $requiredVersionParts, $configVersionIsCurrent, $cleanConfigVersion, $cleanRequiredVersion, $RequiredConfigVersion);
  return array($ConfigIsValid, $MissingConfigVars, $DetectedConfigVersion); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to obfuscate the secret file contents.
// / Decoy secrets give a grep & regex scan seven plausible keys instead of one.
// / This raises the cost of an attack rather than preventing one.
// / A root or www-data command line user can still read the real key from this function.
// / The time an adversary spends adjusting an exploit chain is the product being built here.
// / Adversaries reading this are invited to star the repository & bring it to their next conference.
function addSillyString($Secret, $secretVersion) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FinalOutput = '';
  // / A local separator that cannot be empty. An empty one removes the whitespace after
  // / the opening tag, which stops PHP recognizing the file as PHP at all.
  $newLine = PHP_EOL;
  // / Match the newline count on either side of every secret, decoy & real alike.
  $lolol2 = $newLine.$newLine.$newLine.$newLine;
  // / Seven is the optimal number for the decoy loop.
  $numberOfDecoys = 7;
  // / Select the iteration of the decoy loop that will carry the real secret.
  $randomNumber = random_int(1, $numberOfDecoys);
  // / Buffer strings into an array before a single compilation step.
  $parts = array();
  // / Strip anything that is not a version character before it is written into PHP source.
  $cleanVersion = preg_replace('/[^0-9A-Za-z.\-]/', '', (string)$secretVersion);
  // / Open the PHP region & pin the version, then open the comment fence.
  // / The newline directly after the opening tag is what makes this a PHP file.
  $parts[] = '<?php'.$newLine.'$SecretVersion = \''.$cleanVersion.'\';'.$newLine.'/* ';
  // / Loop to create the desired number of decoy secrets.
  while ($numberOfDecoys > 0) {
    // / Every decoy is drawn from the same source as the real secret, so no table of
    // / candidate decoys can be precomputed to eliminate them.
    if ($numberOfDecoys !== $randomNumber) $parts[] = $lolol2.'$SecretKey = \''.bin2hex(random_bytes(32)).'\';'.$lolol2;
    // / Close the comment fence for the real secret & reopen it immediately afterwards.
    if ($numberOfDecoys === $randomNumber) $parts[] = ' */'.$lolol2.'$SecretKey = \''.$Secret.'\';'.$lolol2.'/* ';
    $numberOfDecoys--; }
  // / Close the trailing comment fence, deliberately without a closing PHP tag.
  $parts[] = $newLine.' */'.$newLine;
  // / Compile the final output string.
  $FinalOutput = implode('', $parts);
  // / Manually clean up sensitive memory. Every target is passed directly by reference.
  // / $FinalOutput is not purged, because it is the return value.
  if (!purgeSensitiveMemory($EnableMemoryProtection, $Secret, $numberOfDecoys, $randomNumber, $lolol2, $newLine, $parts, $cleanVersion, $secretVersion)) {
    // / The failure is already logged under this function's name & is not logged again here.
    // / An unpurged buffer does not make the payload wrong, so the payload is still returned.
    $FinalOutput = $FinalOutput; }
  return $FinalOutput; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / generateInstallSecret must not live in the Engine, because verifyInstallation & resolveSecretFile reach it before the Engine loads.
// / closeClientConnection is reached through quickDie, so it lives in the kernel beside the Engine.
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to generate the per-install secret used to derive session identifiers.
// / 32 bytes gives 256 bits of entropy & returns as a 64 hexadecimal character string.
function generateInstallSecret() {
  // / Set variables.
  global $EnableMemoryProtection;
  $InstallSecret = FALSE;
  $InstallSecretCheck = TRUE;
  // / random_bytes() throws rather than returning a poor result when entropy is unavailable.
  // / Fail closed. A predictable secret is worse than no installation at all.
  try { $InstallSecret = bin2hex(random_bytes(32)); }
  catch (Throwable $error) { $InstallSecretCheck = FALSE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $error);
  return array($InstallSecret, $InstallSecretCheck); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read an install secret without running it.
// / Accepts the path of the secret file.
// / Returns a success boolean, the key & the version it declares, in that order.
// /
// / Root never requires a secret file.
// / An account that can write the file could add a line, & root would run it.
// / So root reads the file as text with PHP's own tokenizer, which parses without executing.
// / The file must have exactly the shape addSillyString writes, which is an opening tag, the version & one live key, with every decoy inside a comment.
// / Anything else in it, a call, a second assignment or a stray line, means it is not a file this application wrote, & it is refused rather than partly believed.
// / A refused file is replaced, as a secret of the wrong version is, & every session it derived is invalid.
// / It is read with readFileWithoutFollowing, because its folder may belong to the web server account.
// / A link, a FIFO or a file with two names there is refused unread & replaced.
function readSecretWithoutExecuting($secretFile) {
  // / Set variables.
  global $EnableMemoryProtection;
  $SecretWasRead = $secretWasOpened = FALSE;
  $ReadSecretKey = $ReadSecretVersion = '';
  $secretContents = $readNotice = '';
  $secretTokens = $liveTokens = array();
  $secretToken = NULL;
  list ($secretWasOpened, $secretContents, $readNotice) = readFileWithoutFollowing($secretFile, 65536);
  if ($secretWasOpened && $secretContents !== '') {
    $secretTokens = token_get_all($secretContents);
    foreach ($secretTokens as $secretToken) {
      if (is_array($secretToken) && in_array($secretToken[0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT), TRUE)) continue;
      $liveTokens[] = is_array($secretToken) ? array($secretToken[0], $secretToken[1]) : array(0, $secretToken); }
    // / Nine live tokens & nothing else. An opening tag, the version's assignment & the key's.
    if (count($liveTokens) === 9 && $liveTokens[0][0] === T_OPEN_TAG && $liveTokens[1] === array(T_VARIABLE, '$SecretVersion') && $liveTokens[2] === array(0, '=') && $liveTokens[3][0] === T_CONSTANT_ENCAPSED_STRING && $liveTokens[4] === array(0, ';') && $liveTokens[5] === array(T_VARIABLE, '$SecretKey') && $liveTokens[6] === array(0, '=') && $liveTokens[7][0] === T_CONSTANT_ENCAPSED_STRING && $liveTokens[8] === array(0, ';')) {
      $ReadSecretVersion = substr((string)$liveTokens[3][1], 1, -1);
      $ReadSecretKey = substr((string)$liveTokens[7][1], 1, -1);
      if (preg_match('/^[0-9a-f]{64}$/', $ReadSecretKey) === 1 && preg_match('/^[0-9A-Za-z.\-]+$/', $ReadSecretVersion) === 1) $SecretWasRead = TRUE; } }
  if (!$SecretWasRead) $ReadSecretKey = $ReadSecretVersion = '';
  // / Manually clean up sensitive memory. Every target is passed directly by reference.
  // / $ReadSecretKey is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $secretWasOpened, $secretContents, $readNotice, $secretTokens, $liveTokens, $secretToken, $secretFile);
  return array($SecretWasRead, $ReadSecretKey, $ReadSecretVersion); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to create the data location as root when it does not exist yet, & to give it back
// / to root when another account holds it.
// / Accepts the data location. Returns TRUE when it exists afterwards.
// / The install secret is written into the data location at boot, so a missing location would stop every root command before the installer could create it.
// / The location is created only beneath a folder root can trust, never beneath a system folder, & its parents are 0755 so the web server account can reach it.
// / The location itself is root's & 0755, & each folder below it has its own role.
// / Root takes the data location back when another account holds it, during a repair & whenever Secrets is in it.
// / Root reads & writes the install secret only where no account but root & the web server account could replace it, & in Secrets only below a folder only root can change.
// / A data location another account owns or can write, as an old chown -R repair, a restored backup or a bind mount leaves one, would put every copy out of reach.
// / -fp would then stop before its data-root role could give it back, so it is given back here first, root:root 0755, as that role gives it.
// / Any other command leaves a data location with no Secrets in it alone, because an older release may still keep its sessions directly in it.
// / A data location an account other than root & the web server account could write sets $SecretPlacesWereOpen before it is taken back.
// / So only a copy of the secret root owns counts for the rest of the command.
function prepareDataLocationAsRoot($dataLocation) {
  // / Set variables.
  global $SecretPlacesWereOpen, $EnableMemoryProtection;
  $LocationExists = FALSE;
  $existingAncestor = $systemFolder = $trustNotice = $resolvedAncestor = $ownerNotice = $resolvedLocation = $resolvedParent = $parentNotice = $cliCommand = $cliTarget = $cliSecondTarget = $openNotice = $resolvedOpen = '';
  $ancestorIsTrusted = $ownerWasChanged = $locationIsTrusted = $modeWasChanged = $parentIsTrusted = $repairRequested = $cliConfirmed = $cliVectorParsed = $locationWasClosed = FALSE;
  $cliArguments = array();
  $cliArgumentCount = 0;
  $systemFolders = array('/bin', '/boot', '/dev', '/etc', '/lib', '/lib32', '/lib64', '/proc', '/root', '/run', '/sbin', '/sys', '/usr');
  $dataLocation = rtrim((string)$dataLocation, DIRECTORY_SEPARATOR);
  if ($dataLocation === '' or is_dir($dataLocation)) {
    $LocationExists = ($dataLocation !== '');
    // / A repair is what the code gate lets through, & is told apart from the arguments the same way.
    list ($cliArguments, $cliArgumentCount, $cliCommand, $cliTarget, $cliSecondTarget, $cliConfirmed, $cliVectorParsed) = parseArgumentVector(isset($_SERVER['argv']) ? $_SERVER['argv'] : array());
    $repairRequested = (in_array($cliCommand, array('-fp', '--fix-permissions', '--setup'), TRUE) or ($cliCommand === '--config' && $cliTarget === '--repair'));
    if ($LocationExists && ($repairRequested or is_dir($dataLocation.DIRECTORY_SEPARATOR.'Secrets'))) {
      list ($locationIsTrusted, $resolvedLocation, $trustNotice) = pathIsTrustedByRoot($dataLocation);
      list ($parentIsTrusted, $resolvedParent, $parentNotice) = pathIsTrustedByRoot(dirname($dataLocation));
      list ($locationWasClosed, $resolvedOpen, $openNotice) = secretFolderIsTrusted($dataLocation);
      if (!$locationIsTrusted && !$locationWasClosed) $SecretPlacesWereOpen = TRUE; }
    // / Only the location itself is taken, by the path configured & never through a link, & only
    // / beneath a folder only root can change, so nothing can swap it between the owner change &
    // / the mode set by name next. Every command run as root stops with ERROR 31024 before this when
    // / a folder above it is not one, & this checks again rather than rely on that.
    if ($LocationExists && $resolvedLocation === $dataLocation && !$locationIsTrusted) {
      if ($parentIsTrusted && $resolvedParent === dirname($dataLocation)) list ($ownerWasChanged, $ownerNotice) = changeOwnerWithoutFollowing($dataLocation, 'root', 'root');
      else $ownerNotice = $parentNotice;
      if ($ownerWasChanged) $modeWasChanged = @chmod($dataLocation, 0755);
      if ($ownerWasChanged && $modeWasChanged) warningEntry('The data location at '.$dataLocation.' could be changed by another account, so root took it back, root:root 0755, as -fp does. '.$trustNotice);
      else warningEntry('The data location at '.$dataLocation.' could be changed by another account & could not be given back to root, so the install secret below it is out of root\'s reach. '.$trustNotice.' '.$ownerNotice); } }
  else {
    $existingAncestor = dirname($dataLocation);
    while ($existingAncestor !== DIRECTORY_SEPARATOR && !file_exists($existingAncestor)) $existingAncestor = dirname($existingAncestor);
    list ($ancestorIsTrusted, $resolvedAncestor, $trustNotice) = pathIsTrustedByRoot($existingAncestor);
    // / The folders are made by the path configured, so that path must be the one judged. A link
    // / on the way could be pointed somewhere else between the check & the mkdir, by whoever owns
    // / the folder the link sits in, & that folder is not on the path that was judged.
    if ($ancestorIsTrusted && $resolvedAncestor !== $existingAncestor) {
      $ancestorIsTrusted = FALSE;
      $trustNotice = $existingAncestor.' leads to '.$resolvedAncestor.'.'; }
    foreach ($systemFolders as $systemFolder) {
      if ($dataLocation === $systemFolder or strpos($dataLocation.DIRECTORY_SEPARATOR, $systemFolder.DIRECTORY_SEPARATOR) === 0) $ancestorIsTrusted = FALSE; }
    if (!$ancestorIsTrusted) warningEntry('The data location '.$dataLocation.' does not exist & was not created, because it is beneath a system folder, a folder another account can change or a link. '.$trustNotice);
    else {
      @mkdir(dirname($dataLocation), 0755, TRUE);
      // / The data location is owned by root & 0755, so every account may traverse it to a folder of its own below, & none but root may change what is in it.
      // / The install secret is kept below it, in Secrets, & a root owned data location is what keeps another account from replacing that folder.
      // / The session, cache & log folders below are the web server account's, & --setup or -fp creates them with their roles, as it does Secrets.
      if (@mkdir($dataLocation, 0755) && is_dir($dataLocation)) {
        // / The location was created a moment ago in a folder only root can change, so the
        // / name cannot have been swapped, & changeOwnerWithoutFollowing confirms that first.
        list ($ownerWasChanged, $ownerNotice) = changeOwnerWithoutFollowing($dataLocation, 'root', 'root');
        if (!$ownerWasChanged) warningEntry($ownerNotice);
        $LocationExists = TRUE;
        warningEntry('The data location did not exist & was created at '.$dataLocation.'.'); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $existingAncestor, $systemFolder, $trustNotice, $resolvedAncestor, $resolvedLocation, $resolvedParent, $parentNotice, $cliCommand, $cliTarget, $cliSecondTarget, $openNotice, $resolvedOpen, $ancestorIsTrusted, $ownerWasChanged, $locationIsTrusted, $modeWasChanged, $parentIsTrusted, $repairRequested, $cliConfirmed, $cliVectorParsed, $locationWasClosed, $cliArguments, $cliArgumentCount, $ownerNotice, $systemFolders, $dataLocation);
  return $LocationExists; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to find the folders above the given locations that root cannot trust.
// / Accepts the locations, each the topmost folder -fp corrects on its path.
// / Returns whether every location could be judged & the folders that fail, in that order.
// / Each folder is keyed by its path & carries its owner, group & mode, & what it needs.
// /
// / -fp corrects the locations it manages & never a folder above them.
// / Root will not read, write or run anything beneath a folder another account can change, because that account could rename what is inside it aside & put its own in its place.
// / A folder above a managed location is the administrator's, so it is named rather than changed.
// / An upgraded host is where this is seen, because older install guides gave /var/www or /DATA to the web server account.
// / Folders are judged as pathIsTrustedByRoot judges them, on the resolved path, & every one that fails is named rather than the first.
// / Only a folder that exists is judged, because a missing one is created beneath a trusted folder when it is needed.
// / Only a folder that root or the web server account owns is to be given to root.
// / A folder another account owns, such as a home folder, & a folder every account shares by design, such as /tmp, are left to the administrator.
// / Changing either would decide something for somebody else.
function foldersRootCannotTrust($managedLocations) {
  // / Set variables.
  global $ApacheUser, $EnableMemoryProtection;
  $FoldersWereJudged = TRUE;
  $UntrustedFolders = array();
  $managedLocation = $existingFolder = $resolvedFolder = $walkedPath = $pathComponent = $ownerName = $groupName = $folderAdvice = '';
  $pathComponents = $componentStatus = $ownerRecord = $groupRecord = array();
  $folderIsPlain = FALSE;
  foreach ((array)$managedLocations as $managedLocation) {
    $managedLocation = rtrim(trim((string)$managedLocation), DIRECTORY_SEPARATOR);
    if ($managedLocation === '' or strpos($managedLocation, DIRECTORY_SEPARATOR) !== 0) continue;
    $existingFolder = dirname($managedLocation);
    while ($existingFolder !== DIRECTORY_SEPARATOR && !file_exists($existingFolder)) $existingFolder = dirname($existingFolder);
    $resolvedFolder = (string)@realpath($existingFolder);
    if ($resolvedFolder === '') $FoldersWereJudged = FALSE;
    else {
      $walkedPath = '';
      $pathComponents = array_merge(array(''), array_values(array_filter(explode(DIRECTORY_SEPARATOR, $resolvedFolder), 'strlen')));
      foreach ($pathComponents as $pathComponent) {
        $walkedPath = rtrim($walkedPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$pathComponent;
        if (isset($UntrustedFolders[$walkedPath])) continue;
        clearstatcache(TRUE, $walkedPath);
        $componentStatus = @lstat($walkedPath);
        if (!is_array($componentStatus)) {
          $FoldersWereJudged = FALSE;
          break; }
        $folderIsPlain = (((int)$componentStatus['mode'] & 0170000) === 0040000);
        if ($folderIsPlain && (int)$componentStatus['uid'] === 0 && ((int)$componentStatus['mode'] & 0022) === 0) continue;
        $ownerRecord = function_exists('posix_getpwuid') ? @posix_getpwuid((int)$componentStatus['uid']) : array();
        $groupRecord = function_exists('posix_getgrgid') ? @posix_getgrgid((int)$componentStatus['gid']) : array();
        $ownerName = (is_array($ownerRecord) && isset($ownerRecord['name'])) ? (string)$ownerRecord['name'] : (string)$componentStatus['uid'];
        $groupName = (is_array($groupRecord) && isset($groupRecord['name'])) ? (string)$groupRecord['name'] : (string)$componentStatus['gid'];
        $folderAdvice = 'Give it to root:root, mode 755.';
        if (!$folderIsPlain) $folderAdvice = 'Is not a folder. Point the location somewhere else.';
        else if (((int)$componentStatus['mode'] & 01000) !== 0) $folderAdvice = 'Is shared by every account by design. Move the location out of it.';
        else if ((int)$componentStatus['uid'] !== 0 && $ownerName !== (string)$ApacheUser) $folderAdvice = 'Belongs to '.$ownerName.'. Move the location out of it rather than taking it.';
        $UntrustedFolders[$walkedPath] = array('Owner' => $ownerName, 'Group' => $groupName, 'Mode' => sprintf('%o', (int)$componentStatus['mode'] & 07777), 'Advice' => $folderAdvice); } } }
  ksort($UntrustedFolders);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $FoldersWereJudged & $UntrustedFolders are not purged, because they are the return values.
  purgeSensitiveMemory($EnableMemoryProtection, $managedLocation, $existingFolder, $resolvedFolder, $walkedPath, $pathComponent, $ownerName, $groupName, $folderAdvice, $pathComponents, $componentStatus, $ownerRecord, $groupRecord, $folderIsPlain, $managedLocations);
  return array($FoldersWereJudged, $UntrustedFolders); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the folders root cannot trust & what each needs.
// / Accepts the folders foldersRootCannotTrust found & whether root must stop without them.
// / Returns the number of folders named.
// / Only each folder itself changes, & nothing inside it. Mode 755 keeps every account able to
// / reach the folders below, which the web server account needs. The commands are in the
// / description of error 31024 rather than printed, as every instruction to change an owner is.
// / Root stops when the install secret is beneath one of them, & otherwise goes on & corrects
// / nothing beneath them.
function reportFoldersRootCannotTrust($untrustedFolders, $rootMustStop) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FoldersNamed = 0;
  $folderPath = $folderLines = $reportText = '';
  $folderDetail = array();
  foreach ((array)$untrustedFolders as $folderPath => $folderDetail) {
    $FoldersNamed++;
    $folderLines = $folderLines.'  '.str_pad((string)$folderPath, 24).' '.str_pad($folderDetail['Owner'].':'.$folderDetail['Group'].' '.$folderDetail['Mode'], 24).' '.$folderDetail['Advice'].PHP_EOL; }
  $reportText = PHP_EOL.'Root will not work beneath a folder another account can change, & these can be changed.'.PHP_EOL.$folderLines
    .'-fp corrects the locations it manages & never a folder above them. Change each folder itself'.PHP_EOL
    .'& nothing inside it, then run the command again. Error 31024 in'.PHP_EOL
    .'Documentation/ERROR_DESCRIPTIONS.txt shows how. Releases before v4.0.1 gave /var/www & /DATA'.PHP_EOL
    .'to the web server account, & nothing needs that now.'.PHP_EOL
    .($rootMustStop ? 'The install secret is beneath them, so root stopped.' : 'Root went on & corrected nothing beneath them.').PHP_EOL.PHP_EOL;
  print($reportText);
  if ($rootMustStop) errorEntry('Root will not work beneath '.implode(', ', array_keys((array)$untrustedFolders)).', because another account can change them. Give each back to root & run the command again.', 31024, TRUE);
  else warningEntry('Root corrects nothing beneath '.implode(', ', array_keys((array)$untrustedFolders)).', because another account can change them.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $FoldersNamed is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $folderPath, $folderLines, $reportText, $folderDetail, $untrustedFolders, $rootMustStop);
  return $FoldersNamed; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the copy of the install secret at the top of the data location.
// / Accepts the install secret's file in Secrets.
// / Returns the same name at the top of the data location.
// / A release older than v4.0.4 keeps the secret there alone, & the same key is kept there & in Secrets.
// / Until Secrets has its role the copy there is the one the web server account reads first.
// / This is the one place the name is built.
function olderInstallSecretFile($secretFile) {
  // / Set variables.
  global $DataRootLoc, $EnableMemoryProtection;
  $OlderSecretFile = rtrim((string)$DataRootLoc, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.basename((string)$secretFile);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $OlderSecretFile is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $secretFile);
  return $OlderSecretFile; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to find the number of the web server account & of the group named after it.
// / Accepts nothing. Returns the account's number & the group's number, in that order, each -1
// / when it cannot be found.
// / The group is the one root gives the install secret & Secrets. Some systems do not have the
// / posix functions, so id & getent are asked instead, as elsewhere in this application. The
// / answer is kept for the rest of the process, because neither changes while it runs.
function webServerAccountNumbers() {
  // / Set variables.
  global $ApacheUser, $EnableMemoryProtection;
  // / The answer is kept for the life of the process. It holds two numbers & nothing secret.
  static $knownNumbers = NULL;
  $WebAccountNumber = $WebGroupNumber = -1;
  $accountRecord = $groupRecord = $commandOutput = $groupFields = array();
  $commandStatus = 1;
  $commandAnswer = '';
  if (is_array($knownNumbers)) list ($WebAccountNumber, $WebGroupNumber) = $knownNumbers;
  else if (trim((string)$ApacheUser) !== '') {
    if (function_exists('posix_getpwnam')) $accountRecord = @posix_getpwnam((string)$ApacheUser);
    else exec('id -u '.escapeshellarg((string)$ApacheUser).' 2>/dev/null', $commandOutput, $commandStatus);
    $commandAnswer = trim(implode('', $commandOutput));
    if (is_array($accountRecord) && isset($accountRecord['uid'])) $WebAccountNumber = (int)$accountRecord['uid'];
    else if ($commandStatus === 0 && preg_match('/^[0-9]+$/', $commandAnswer) === 1) $WebAccountNumber = (int)$commandAnswer;
    $commandOutput = array();
    $commandStatus = 1;
    if (function_exists('posix_getgrnam')) $groupRecord = @posix_getgrnam((string)$ApacheUser);
    else exec('getent group '.escapeshellarg((string)$ApacheUser).' 2>/dev/null', $commandOutput, $commandStatus);
    $groupFields = explode(':', trim(implode('', $commandOutput)));
    if (is_array($groupRecord) && isset($groupRecord['gid'])) $WebGroupNumber = (int)$groupRecord['gid'];
    else if ($commandStatus === 0 && isset($groupFields[2]) && preg_match('/^[0-9]+$/', $groupFields[2]) === 1) $WebGroupNumber = (int)$groupFields[2];
    $knownNumbers = array($WebAccountNumber, $WebGroupNumber); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $WebAccountNumber & $WebGroupNumber are not purged, because they are the return values.
  purgeSensitiveMemory($EnableMemoryProtection, $accountRecord, $groupRecord, $commandOutput, $groupFields, $commandStatus, $commandAnswer);
  return array($WebAccountNumber, $WebGroupNumber); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether only root or the web server account could change a folder of
// / the install secret's, or any folder above it.
// / Accepts the folder. Returns whether that holds, the folder it leads to & the reason it does
// / not, in that order.
// / Every folder from the filesystem root down must belong to root or the web server account, &
// / be writable by no account but its owner. An account that can write any of them could put a
// / file of its own at a name below it between any look & any read. A folder its group may write
// / is refused whoever the group is, because under an access control list the group bits are
// / only the list's mask, & cannot say which other accounts the list lets write. A folder reached
// / through a link is refused too, as folderIsHeldForRoot refuses one for root, because whoever
// / can change the link could point it somewhere else between the look & the read. Root writes
// / no copy of the secret where this does not hold, because the web server account would refuse
// / it there.
function secretFolderIsTrusted($secretFolder) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FolderIsTrusted = FALSE;
  $ResolvedFolder = $TrustNotice = '';
  $walkedPath = $pathComponent = '';
  $pathComponents = $componentStatus = $trustedOwners = array();
  $webAccountNumber = -1;
  list ($webAccountNumber) = webServerAccountNumbers();
  $trustedOwners = array(0);
  if ($webAccountNumber >= 0) $trustedOwners[] = $webAccountNumber;
  if (trim((string)$secretFolder) !== '') $ResolvedFolder = (string)@realpath((string)$secretFolder);
  if ($ResolvedFolder === '') $TrustNotice = (string)$secretFolder.' does not exist.';
  else if ($ResolvedFolder !== rtrim((string)$secretFolder, DIRECTORY_SEPARATOR)) $TrustNotice = (string)$secretFolder.' leads to '.$ResolvedFolder.'.';
  else {
    // / The empty first component is the filesystem root, which is judged like any other.
    $pathComponents = array_merge(array(''), array_values(array_filter(explode(DIRECTORY_SEPARATOR, $ResolvedFolder), 'strlen')));
    foreach ($pathComponents as $pathComponent) {
      $walkedPath = rtrim($walkedPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$pathComponent;
      clearstatcache(TRUE, $walkedPath);
      $componentStatus = @lstat($walkedPath);
      if (!is_array($componentStatus)) $TrustNotice = $walkedPath.' could not be examined.';
      else if (((int)$componentStatus['mode'] & 0170000) !== 0040000) $TrustNotice = $walkedPath.' is not a folder.';
      else if (!in_array((int)$componentStatus['uid'], $trustedOwners, TRUE)) $TrustNotice = $walkedPath.' belongs to an account other than root & the web server account.';
      else if (((int)$componentStatus['mode'] & 0002) !== 0) $TrustNotice = $walkedPath.' can be written by every account.';
      else if (((int)$componentStatus['mode'] & 0020) !== 0) $TrustNotice = $walkedPath.' can be written by its group.';
      if ($TrustNotice !== '') break; }
    if ($TrustNotice === '') $FolderIsTrusted = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $FolderIsTrusted, $ResolvedFolder & $TrustNotice are not purged, because they are the return values.
  purgeSensitiveMemory($EnableMemoryProtection, $walkedPath, $pathComponent, $pathComponents, $componentStatus, $trustedOwners, $webAccountNumber, $secretFolder);
  return array($FolderIsTrusted, $ResolvedFolder, $TrustNotice); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether a copy of the install secret could only have been written by
// / root or the web server account.
// / Accepts the copy. Returns whether it may be used & the reason it may not, in that order.
// / A key another account could have written is a key that account may know.
// / The copy must be a plain file with one name in a folder secretFolderIsTrusted holds, belong to root or the web server account, & be writable by no account but its owner.
// / Then nothing but root or the web server account can change what is read, & both know the key anyway, so a copy is judged before it is read.
// / Root & the web server account judge every copy this way, so neither uses one the other would refuse.
// / The kernel reads no file with a second name, which fs.protected_hardlinks keeps any other account from making.
// / Once a command run as root has found a place the secret is kept open to another account, only a copy root owns counts for the rest of it.
// / $SecretPlacesWereOpen says so.
// / Root takes such a place back before it reads, but the account that could write there could have put a file the web server account owns at the name.
// / Every such file is a key that account chose.
// / The web server account never sets it.
function installSecretCopyIsTrusted($secretFile) {
  // / Set variables.
  global $SecretPlacesWereOpen, $EnableMemoryProtection;
  $CopyIsTrusted = FALSE;
  $TrustNotice = '';
  $folderIsTrusted = FALSE;
  $resolvedFolder = $judgedCopy = '';
  $copyStatus = $trustedOwners = array();
  $webAccountNumber = -1;
  list ($folderIsTrusted, $resolvedFolder, $TrustNotice) = secretFolderIsTrusted(dirname((string)$secretFile));
  if ($folderIsTrusted) {
    list ($webAccountNumber) = webServerAccountNumbers();
    $trustedOwners = array(0);
    if ($webAccountNumber >= 0 && (!isset($SecretPlacesWereOpen) or $SecretPlacesWereOpen !== TRUE)) $trustedOwners[] = $webAccountNumber;
    $judgedCopy = rtrim($resolvedFolder, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.basename((string)$secretFile);
    clearstatcache(TRUE, $judgedCopy);
    $copyStatus = @lstat($judgedCopy);
    if (!is_array($copyStatus)) $TrustNotice = $judgedCopy.' could not be examined.';
    else if (((int)$copyStatus['mode'] & 0170000) !== 0100000) $TrustNotice = $judgedCopy.' is not a plain file.';
    else if ((int)$copyStatus['nlink'] !== 1) $TrustNotice = $judgedCopy.' has more than one name.';
    else if (!in_array((int)$copyStatus['uid'], $trustedOwners, TRUE) && $webAccountNumber >= 0 && (int)$copyStatus['uid'] === $webAccountNumber) $TrustNotice = $judgedCopy.' belongs to the web server account, & only a copy root owns counts once a place the secret is kept was found open to another account.';
    else if (!in_array((int)$copyStatus['uid'], $trustedOwners, TRUE) && $webAccountNumber < 0) $TrustNotice = $judgedCopy.' does not belong to root, & the web server account could not be looked up.';
    else if (!in_array((int)$copyStatus['uid'], $trustedOwners, TRUE)) $TrustNotice = $judgedCopy.' belongs to an account other than root & the web server account.';
    else if (((int)$copyStatus['mode'] & 0002) !== 0) $TrustNotice = $judgedCopy.' can be written by every account.';
    else if (((int)$copyStatus['mode'] & 0020) !== 0) $TrustNotice = $judgedCopy.' can be written by its group.';
    else $CopyIsTrusted = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $CopyIsTrusted & $TrustNotice are not purged, because they are the return values.
  purgeSensitiveMemory($EnableMemoryProtection, $folderIsTrusted, $resolvedFolder, $judgedCopy, $copyStatus, $trustedOwners, $webAccountNumber, $secretFile);
  return array($CopyIsTrusted, $TrustNotice); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether the copy of the install secret at the top of the data location
// / was written after the copy in Secrets.
// / Accepts the install secret's file in Secrets. Returns TRUE when both copies are plain files,
// / the one at the top is root's & no other account can write it, & it was written later.
// / A release older than v4.0.4 knows only the copy at the top.
// / Going back to one & rotating the key there, or letting it make a new one, leaves the copy in Secrets carrying the key that was replaced.
// / The copy written last carries the key in use, so when this holds root & the web server account both read the copy at the top first, & root then writes its key into Secrets.
// / Only a copy root owns is put first this way, so a copy another account owns never outranks one root wrote.
// / This release writes one key into both copies, so the order changes nothing until they disagree.
// / The times are whole seconds of the host's clock, so two copies written in the same second, or across a clock set back, keep the usual order.
function topSecretCopyIsNewer($secretFile) {
  // / Set variables.
  global $EnableMemoryProtection;
  $TopIsNewer = FALSE;
  $olderSecretFile = '';
  $topStatus = $secretsStatus = array();
  $olderSecretFile = olderInstallSecretFile($secretFile);
  if ($olderSecretFile !== (string)$secretFile) {
    clearstatcache(TRUE, $olderSecretFile);
    clearstatcache(TRUE, (string)$secretFile);
    $topStatus = @lstat($olderSecretFile);
    $secretsStatus = @lstat((string)$secretFile);
    $TopIsNewer = (is_array($topStatus) && is_array($secretsStatus) && ((int)$topStatus['mode'] & 0170000) === 0100000 && ((int)$secretsStatus['mode'] & 0170000) === 0100000 && (int)$topStatus['uid'] === 0 && ((int)$topStatus['mode'] & 0022) === 0 && (int)$topStatus['mtime'] > (int)$secretsStatus['mtime']); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $TopIsNewer is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $olderSecretFile, $topStatus, $secretsStatus, $secretFile);
  return $TopIsNewer; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to examine the folder the install secret is kept in, without following anything.
// / Accepts the folder. Returns its state, its mode & its owner & group, in that order.
// / The state is ok, missing, not a folder, wrong or unjudged. The mode & the owner are empty
// / unless a folder is there. A folder is unjudged when the group named after the web server
// / account cannot be looked up, because then nothing can say whether its group is that one.
// / The group is compared by number, so a host without the posix functions judges it the same.
// / Setup Core gives the folder the secret role, which is root's, the web server account's group
// / & 0750, so that account may read the secret & change nothing, & no other account may list
// / the folder. The copy in a folder in any other state is never the one read first, because the
// / web server account might not be able to read it there, & every request would stop.
function secretFolderStatus($secretFolder) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FolderState = 'missing';
  $FolderMode = $FolderOwnership = '';
  $folderStatus = $ownerRecord = $groupRecord = array();
  $ownerName = $groupName = '';
  $webAccountNumber = $webGroupNumber = -1;
  clearstatcache(TRUE, (string)$secretFolder);
  if ((string)$secretFolder !== '') $folderStatus = @lstat((string)$secretFolder);
  if (is_array($folderStatus) && ((int)$folderStatus['mode'] & 0170000) !== 0040000) $FolderState = 'not a folder';
  else if (is_array($folderStatus)) {
    if (function_exists('posix_getpwuid')) $ownerRecord = @posix_getpwuid((int)$folderStatus['uid']);
    if (function_exists('posix_getgrgid')) $groupRecord = @posix_getgrgid((int)$folderStatus['gid']);
    $ownerName = (is_array($ownerRecord) && isset($ownerRecord['name'])) ? (string)$ownerRecord['name'] : (string)$folderStatus['uid'];
    $groupName = (is_array($groupRecord) && isset($groupRecord['name'])) ? (string)$groupRecord['name'] : (string)$folderStatus['gid'];
    $FolderMode = sprintf('%04o', (int)$folderStatus['mode'] & 07777);
    $FolderOwnership = $ownerName.':'.$groupName;
    list ($webAccountNumber, $webGroupNumber) = webServerAccountNumbers();
    if ($webGroupNumber < 0) $FolderState = 'unjudged';
    else $FolderState = ((int)$folderStatus['uid'] === 0 && $FolderMode === '0750' && (int)$folderStatus['gid'] === $webGroupNumber) ? 'ok' : 'wrong'; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $FolderState, $FolderMode & $FolderOwnership are not purged, because they are the return values.
  purgeSensitiveMemory($EnableMemoryProtection, $folderStatus, $ownerRecord, $groupRecord, $ownerName, $groupName, $webAccountNumber, $webGroupNumber, $secretFolder);
  return array($FolderState, $FolderMode, $FolderOwnership); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write the install wide secret as root & read it back.
// / Accepts the file, the key & the version the file declares.
// / Returns whether the file holds the key, whether the web server account's group can read it,
// / & the reason the file does not hold the key, in that order.
// / The new copy is finished before it takes the name.
// / It is made beside the name as a staged copy, under a name of its own for every write, through the kernel.
// / So nothing planted at either name is followed, & nothing left at one can stop the next.
// / It is made empty, given the web server account's group & its mode, & judged, & only then is the key written into it.
// / The key is written through a handle the kernel compares with what was judged.
// / Then it is renamed over the name, so the name always holds a whole copy that account can read whenever its group could be given.
// / The owner stays root, & the file is read back exactly as root reads it next time.
// / A default access control list decides a new file's mode in place of the umask, & could leave the secret readable by every account, so the mode is set again by name.
// / That is done only in a folder root owns that no other account can write, because only there can nothing swap the staged name between the write & the change.
// / Every folder -fp gives a copy is one, & anywhere else a copy wider than 0640 is given no key & never takes the name.
// / A list that names another account can still let that account read a copy, which no mode shows.
// / A group that could not be changed is warned about & is not a failure to write, because root still reads the key.
// / The exception is a name holding a copy the web server account reads now, which is never replaced by one it could not read.
// / The caller decides the rest.
function writeInstallSecretAsRoot($secretFile, $secretKey, $secretVersion) {
  // / Set variables.
  global $ApacheUser, $EnableMemoryProtection;
  $SecretWasWritten = $GroupWasChanged = FALSE;
  $WriteFailure = '';
  $fileWasWritten = $readBackSucceeded = $folderIsRootsAlone = $modeWasSet = $stagedWasRemoved = $stagedWasCreated = $placeIsReadable = $handleIsJudged = FALSE;
  $secretFileContent = $writeNotice = $groupNotice = $readBackKey = $readBackVersion = $stagedFile = $removalNotice = $randomPart = $openNotice = '';
  $folderStatus = $stagedStatus = $handleStatus = array();
  $randomError = NULL;
  $stagedHandle = FALSE;
  $bytesWritten = 0;
  $secretFileContent = addSillyString($secretKey, $secretVersion);
  try { $randomPart = bin2hex(random_bytes(6)); }
  catch (Throwable $randomError) { $randomPart = str_replace('.', '', uniqid('', TRUE)); }
  $stagedFile = dirname((string)$secretFile).DIRECTORY_SEPARATOR.'.'.basename((string)$secretFile).'.'.$randomPart.'.staged';
  list ($fileWasWritten, $writeNotice) = writeFileWithoutFollowing($stagedFile, '', 0640);
  if (!$fileWasWritten) $WriteFailure = 'The install secret could not be written to '.$secretFile.'! '.$writeNotice;
  else {
    // / The owner stays root. Only the group becomes the web server's, so it can read the key.
    list ($GroupWasChanged, $groupNotice) = changeOwnerWithoutFollowing($stagedFile, '', (string)$ApacheUser);
    if (!$GroupWasChanged) warningEntry($groupNotice.' The web server account cannot read the install secret until it is.');
    // / A copy that account reads is never replaced by one it cannot.
    $placeIsReadable = (!$GroupWasChanged && webServerCanReadSecretCopy($secretFile));
    clearstatcache(TRUE, dirname((string)$secretFile));
    $folderStatus = @lstat(dirname((string)$secretFile));
    $folderIsRootsAlone = (is_array($folderStatus) && ((int)$folderStatus['mode'] & 0170000) === 0040000 && (int)$folderStatus['uid'] === 0 && ((int)$folderStatus['mode'] & 0022) === 0);
    if ($folderIsRootsAlone) $modeWasSet = @chmod($stagedFile, 0640);
    clearstatcache(TRUE, $stagedFile);
    $stagedStatus = @lstat($stagedFile);
    if ($placeIsReadable) $WriteFailure = 'The install secret could not be written to '.$secretFile.'! Its group could not be given, & the copy there now is one the web server account reads.';
    else if ($folderIsRootsAlone && !$modeWasSet) $WriteFailure = 'The install secret could not be written to '.$secretFile.'! Its mode could not be set.';
    else if (!is_array($stagedStatus) or ((int)$stagedStatus['mode'] & 0037) !== 0) $WriteFailure = 'The install secret could not be written to '.$secretFile.'! The new copy was made wider than 0640, as a default access control list makes it.';
    else {
      // / Only now is the key written into it, & only into the file judged above, by device, inode,
      // / owner, size & mode, so a file swapped in at the staged name since is never filled, even
      // / one given the same inode number again.
      list ($stagedHandle, $stagedWasCreated, $openNotice) = openFileWithoutFollowing($stagedFile, 0640);
      if (is_resource($stagedHandle)) $handleStatus = @fstat($stagedHandle);
      $handleIsJudged = (is_resource($stagedHandle) && !$stagedWasCreated && is_array($handleStatus) && $handleStatus['dev'] === $stagedStatus['dev'] && $handleStatus['ino'] === $stagedStatus['ino'] && (int)$handleStatus['uid'] === 0 && (int)$handleStatus['size'] === 0 && ((int)$handleStatus['mode'] & 0037) === 0);
      if ($handleIsJudged) {
        $bytesWritten = (int)@fwrite($stagedHandle, $secretFileContent);
        @fflush($stagedHandle); }
      if (is_resource($stagedHandle)) @fclose($stagedHandle);
      if (!$handleIsJudged or $bytesWritten !== strlen($secretFileContent)) $WriteFailure = 'The install secret could not be written to '.$secretFile.'! The new copy could not be filled. '.$openNotice;
      else if (!@rename($stagedFile, (string)$secretFile)) $WriteFailure = 'The install secret could not be written to '.$secretFile.'! The new copy could not be moved into place.';
      else {
        // / Read the new file back exactly as root will read it next time.
        list ($readBackSucceeded, $readBackKey, $readBackVersion) = readSecretWithoutExecuting($secretFile);
        if (!$readBackSucceeded or $readBackKey !== $secretKey) $WriteFailure = 'The install secret written to '.$secretFile.' did not read back correctly!';
        else $SecretWasWritten = TRUE; } }
    // / A staged copy that did not take the name is removed. Removing a name never follows it.
    if (file_exists($stagedFile) or is_link($stagedFile)) list ($stagedWasRemoved, $removalNotice) = removeFileWithoutFollowing($stagedFile);
    if (file_exists($stagedFile) or is_link($stagedFile)) warningEntry('The staged copy of the install secret at '.$stagedFile.' could not be removed. '.$removalNotice); }
  // / Manually clean up sensitive memory. Every target is passed directly by reference.
  // / $SecretWasWritten, $GroupWasChanged & $WriteFailure are not purged, because they are the return values.
  purgeSensitiveMemory($EnableMemoryProtection, $fileWasWritten, $readBackSucceeded, $folderIsRootsAlone, $modeWasSet, $stagedWasRemoved, $stagedWasCreated, $placeIsReadable, $handleIsJudged, $handleStatus, $secretFileContent, $writeNotice, $groupNotice, $readBackKey, $readBackVersion, $stagedFile, $removalNotice, $randomPart, $openNotice, $folderStatus, $stagedStatus, $randomError, $stagedHandle, $bytesWritten, $secretFile, $secretKey, $secretVersion);
  return array($SecretWasWritten, $GroupWasChanged, $WriteFailure); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether the web server account would read a copy of the install secret.
// / Accepts the copy. Returns TRUE when that account would judge it as it judges every copy, & could
// / open it.
// / Root cannot open a file as another account, so this is judged from the owner, the group & the
// / mode of the copy & of the folder it is in, the way the kernel would judge that account's open.
// / The account judges a copy without the closer rule a command run as root takes on once it finds
// / a place the secret is kept open, so a copy it owns counts here. When the account or its group
// / cannot be looked up, nothing can be judged, & a copy is taken to be readable, so a host where
// / they cannot be is not stopped by a check it cannot make, & root says so elsewhere.
function webServerCanReadSecretCopy($secretFile) {
  // / Set variables.
  global $EnableMemoryProtection;
  $CopyIsReadable = $folderIsTrusted = FALSE;
  $resolvedFolder = $trustNotice = '';
  $copyStatus = $folderStatus = array();
  $webAccountNumber = $webGroupNumber = -1;
  $readBits = $searchBits = 0;
  list ($webAccountNumber, $webGroupNumber) = webServerAccountNumbers();
  list ($folderIsTrusted, $resolvedFolder, $trustNotice) = secretFolderIsTrusted(dirname((string)$secretFile));
  clearstatcache(TRUE, (string)$secretFile);
  clearstatcache(TRUE, dirname((string)$secretFile));
  $copyStatus = @lstat((string)$secretFile);
  $folderStatus = @lstat(dirname((string)$secretFile));
  if ($folderIsTrusted && is_array($copyStatus) && is_array($folderStatus) && ((int)$copyStatus['mode'] & 0170000) === 0100000 && (int)$copyStatus['nlink'] === 1 && ((int)$copyStatus['mode'] & 0022) === 0) {
    if ($webAccountNumber < 0 or $webGroupNumber < 0) $CopyIsReadable = TRUE;
    else if (in_array((int)$copyStatus['uid'], array(0, $webAccountNumber), TRUE)) {
      // / The owner's bits apply to the owner, the group's to a member, & the rest to anybody else.
      $readBits = ((int)$copyStatus['uid'] === $webAccountNumber) ? 0400 : (((int)$copyStatus['gid'] === $webGroupNumber) ? 0040 : 0004);
      $searchBits = ((int)$folderStatus['uid'] === $webAccountNumber) ? 0100 : (((int)$folderStatus['gid'] === $webGroupNumber) ? 0010 : 0001);
      $CopyIsReadable = (((int)$copyStatus['mode'] & $readBits) !== 0 && ((int)$folderStatus['mode'] & $searchBits) !== 0); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $CopyIsReadable is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $folderIsTrusted, $resolvedFolder, $trustNotice, $copyStatus, $folderStatus, $webAccountNumber, $webGroupNumber, $readBits, $searchBits, $secretFile);
  return $CopyIsReadable; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to load, create or rotate the install wide secret as root.
// / Accepts the secret file, the version this core requires & whether a new key was asked for.
// / Returns a readiness boolean & the key, in that order.
// /
// / Root reads the secret with readSecretWithoutExecuting & writes it through writeInstallSecretAsRoot.
// / Nothing here requires the file or follows a link.
// /
// / Two copies carry one key, & neither is removed while it is known to carry it.
// / The secret is kept in Secrets once Setup Core has given the folder its role.
// / The copy at the top of the data location, where older releases read it, keeps the same key.
// / Every command run as root writes the key it holds into each copy that does not already carry it exactly.
// / So a web server running this release, an older one, or one not installed yet always finds the key, & going back to an older release costs no session.
// / The web server account reads the copy in Secrets first once the folder has its role & the data location is root's, & the copy at the top first until then.
// / topSecretCopyIsNewer puts the copy at the top first when root wrote it after the copy in Secrets.
// / A copy known to carry the key is never removed, because a web server that can no longer read its copy is a site that stops.
// / A release that reads only the top, or a hosted tree never installed, cannot be told apart from here.
// / The one copy removed is one not known to carry the key, that could not be written, & only once the web server account reads the key in the copy it reads first.
// /
// / The first usable copy wins.
// / The copy the web server account reads first is looked in, then the other, then the first again.
// / A copy that cannot be used is passed over & named, & is written again with the key in use.
// / A copy that vanishes or changes while it is read was moved or replaced by an older release running as root, & a later look finds it.
// /
// / A copy counts only when installSecretCopyIsTrusted says so, before it is read.
// / That is how the web server account judges the copy it reads too, so neither uses a copy the other refuses.
// / A copy the web server account owns is taken, because older releases & an old chown -R leave it so, & that account reads the key whoever owns the file.
// / It is written again as root's with its same key.
// /
// / A new key is written only when no usable copy is found, & never in place of a copy root cannot reach.
// / Root does not read in Secrets while another account could replace the folder, so it stops with error 31020 & names why rather than replace a copy that may be the good one.
// / prepareDataLocationAsRoot gives the data location back to root first, which is the usual cause.
// / A rotation writes its new key into every copy, & is refused with error 31020 while a copy in Secrets is out of reach, because that copy would keep the old key.
// / No copy is written where another account could replace it.
// /
// / The key is in use only when the web server account reads it.
// / Once the copies are settled, webServerCanReadSecretCopy judges which copy that account would read first, & the key is ready only when that copy carries it.
// / A rotation is done once the copy read first carries the new key.
// /
// / One command run as root settles the secret at a time.
// / A lock in Engine, root's own folder, opened through the kernel, is held while this runs, so a command that read the old key never writes it back over a new rotation.
// / It is taken only in an Engine that is root's own, because a lock another account could hold could stall every repair.
// / A command that has waited fifteen seconds goes on without it & says so.
// / A command that finds no Engine, as before the first -fp, goes on without it, because nothing else can be settling a secret there yet.
function resolveInstallSecretAsRoot($secretFile, $requiredSecretVersion, $rotationRequested) {
  // / Set variables.
  global $ApacheUser, $EngineDir, $SecretPlacesWereOpen, $EnableMemoryProtection;
  $SecretIsReady = FALSE;
  $ResolvedSecretKey = '';
  $existingWasRead = $keyWasGenerated = $copyWasWritten = $groupWasChanged = $copyIsRight = $folderIsHeld = $folderIsReady = $secretIsUnreachable = $copyIsTrusted = $lockIsHeld = $lockWasCreated = $placeIsTrusted = $engineIsTrusted = $lockWouldBlock = $preferredIsRight = $keptWasRead = $keptIsTrusted = $preferredIsReadable = $staleWasRemoved = $judgedWasRead = $webReadsKey = FALSE;
  $existingKey = $existingVersion = $candidateKey = $cleanRequiredVersion = $writeFailure = $trustNotice = $holdNotice = $lockNotice = $folderProblem = $refusalNotice = $resolvedPlace = $placeNotice = $resolvedEngine = $engineNotice = $keptKey = $keptVersion = $keptNotice = $removalNotice = '';
  $secretFolder = $olderSecretFile = $preferredCopy = $otherCopy = $keptCopy = $sourceFile = $candidateFile = $refusedFile = $folderState = $folderMode = $folderOwnership = $lockPath = $writtenCopies = '';
  $judgedCopy = $judgedKey = $judgedVersion = $webCopy = '';
  $secretStatus = $candidateFiles = $refusedCopies = $keptCopies = array();
  $webAccountNumber = $webGroupNumber = -1;
  $lockDeadline = 0;
  $lockHandle = FALSE;
  $cleanRequiredVersion = ltrim(trim((string)$requiredSecretVersion), 'vV');
  $secretFolder = dirname((string)$secretFile);
  $olderSecretFile = olderInstallSecretFile($secretFile);
  list ($webAccountNumber, $webGroupNumber) = webServerAccountNumbers();
  if ($webAccountNumber < 0 or $webGroupNumber < 0) warningEntry('The web server account '.$ApacheUser.' or the group of the same name could not be looked up, so only a copy of the install secret root owns counts, & Secrets cannot be judged.');
  // / The lock is taken before anything is looked at, & only in an Engine that is root's own. A
  // / lock file another command run as root created a moment ago is opened again, once.
  if (isset($EngineDir) && is_string($EngineDir) && $EngineDir !== '' && is_dir($EngineDir)) {
    list ($engineIsTrusted, $resolvedEngine, $engineNotice) = pathIsTrustedByRoot($EngineDir);
    if (!$engineIsTrusted or $resolvedEngine !== rtrim($EngineDir, DIRECTORY_SEPARATOR)) $lockNotice = 'Engine is not root\'s own yet. '.$engineNotice;
    else {
      $lockPath = $resolvedEngine.DIRECTORY_SEPARATOR.'secret.lock';
      list ($lockHandle, $lockWasCreated, $lockNotice) = openFileWithoutFollowing($lockPath, 0600);
      if (!is_resource($lockHandle)) list ($lockHandle, $lockWasCreated, $lockNotice) = openFileWithoutFollowing($lockPath, 0600);
      $lockDeadline = hrtime(TRUE) + 15000000000;
      while (is_resource($lockHandle) && !$lockIsHeld) {
        $lockIsHeld = @flock($lockHandle, LOCK_EX | LOCK_NB, $lockWouldBlock);
        if ($lockIsHeld or !$lockWouldBlock or hrtime(TRUE) >= $lockDeadline) break;
        usleep(100000); }
      if (is_resource($lockHandle) && !$lockIsHeld) $lockNotice = $lockWouldBlock ? 'Another command run as root held the lock for fifteen seconds.' : 'This filesystem cannot lock it.'; }
    if (!$lockIsHeld) warningEntry('The install secret was settled without its lock, so another command run as root could have been settling it at the same moment. '.$lockNotice); }
  // / The web server account reads the copy in Secrets first once the folder has its role & only
  // / root could replace it, unless the copy at the top is root's & was written after it, & the
  // / copy at the top first until then. Root looks in the same order.
  list ($folderState, $folderMode, $folderOwnership) = secretFolderStatus($secretFolder);
  list ($folderIsHeld, $holdNotice) = folderIsHeldForRoot($secretFolder);
  $folderIsReady = ($folderState === 'ok' && $folderIsHeld && $olderSecretFile !== (string)$secretFile);
  $preferredCopy = ($folderIsReady && !topSecretCopyIsNewer($secretFile)) ? (string)$secretFile : $olderSecretFile;
  $otherCopy = ($preferredCopy === $olderSecretFile) ? (string)$secretFile : $olderSecretFile;
  // / Root neither reads nor writes in Secrets while another account could replace the folder.
  $secretIsUnreachable = (!$folderIsHeld && $olderSecretFile !== (string)$secretFile && (file_exists($secretFile) or is_link($secretFile)));
  // / A place another account could write makes every copy there one only root's own may stand for.
  list ($placeIsTrusted, $resolvedPlace, $placeNotice) = secretFolderIsTrusted($secretFolder);
  if ($folderIsHeld && !$placeIsTrusted && file_exists($secretFolder)) $SecretPlacesWereOpen = TRUE;
  // / The copies root keeps. The top always. Secrets once the folder has its role, & any copy root
  // / can reach there before then, so it carries the same key.
  $keptCopies = array($preferredCopy);
  if ($otherCopy === $olderSecretFile or ($folderIsHeld && (file_exists($otherCopy) or is_link($otherCopy)))) $keptCopies[] = $otherCopy;
  // / Find the first usable key, unless a new one was asked for.
  if (!$rotationRequested) {
    $candidateFiles = array($preferredCopy, $otherCopy, $preferredCopy);
    foreach ($candidateFiles as $candidateFile) {
      if (!file_exists($candidateFile) && !is_link($candidateFile)) continue;
      if ($candidateFile === (string)$secretFile && !$folderIsHeld) continue;
      $existingWasRead = FALSE;
      $existingKey = $existingVersion = '';
      list ($copyIsTrusted, $trustNotice) = installSecretCopyIsTrusted($candidateFile);
      if ($copyIsTrusted) list ($existingWasRead, $existingKey, $existingVersion) = readSecretWithoutExecuting($candidateFile);
      if ($copyIsTrusted && $existingWasRead && ltrim($existingVersion, 'vV') === $cleanRequiredVersion) {
        $sourceFile = $candidateFile;
        $candidateKey = $existingKey;
        unset($refusedCopies[$candidateFile]);
        break; }
      // / A copy that is there & cannot be used is named once, with why, & the looks go on.
      if (!isset($refusedCopies[$candidateFile]) && (file_exists($candidateFile) or is_link($candidateFile))) $refusedCopies[$candidateFile] = $copyIsTrusted ? 'It is not one this core can use, or carries something this application did not write.' : $trustNotice; }
    foreach ($refusedCopies as $refusedFile => $refusalNotice) warningEntry('The copy of the install secret at '.$refusedFile.' is not used. '.$refusalNotice); }
  // / A copy in Secrets root cannot reach may be the good one, so nothing is written in its place.
  if ($rotationRequested && $secretIsUnreachable) errorEntry('The install secret was not rotated, because another account could replace '.$secretFolder.', & the copy there would keep the old key! '.$holdNotice, 31020, FALSE);
  else if ($candidateKey === '' && $secretIsUnreachable) errorEntry('The install secret at '.$secretFile.' could not be reached, because another account could replace '.$secretFolder.', so nothing was replaced! '.$holdNotice, 31020, FALSE);
  else {
    if ($candidateKey === '') list ($candidateKey, $keyWasGenerated) = generateInstallSecret();
    if (!is_string($candidateKey) or $candidateKey === '') errorEntry('An install secret could not be generated, because this host could not supply random bytes!', 31020, FALSE);
    else {
      // / Every copy root keeps is made to carry the key exactly, the one read first first. A copy
      // / is right when it is root's, 0640, its group is the web server account's & it already
      // / carries the key. No copy is written where another account could replace it.
      foreach ($keptCopies as $keptCopy) {
        $copyIsRight = $keptWasRead = $keptIsTrusted = $copyWasWritten = $groupWasChanged = FALSE;
        $keptKey = $keptVersion = $writeFailure = $keptNotice = '';
        $secretStatus = array();
        if (!$keyWasGenerated && $keptCopy === $sourceFile) $keptKey = $candidateKey;
        else if (!$keyWasGenerated && (file_exists($keptCopy) or is_link($keptCopy))) {
          list ($keptIsTrusted, $keptNotice) = installSecretCopyIsTrusted($keptCopy);
          if ($keptIsTrusted) list ($keptWasRead, $keptKey, $keptVersion) = readSecretWithoutExecuting($keptCopy); }
        clearstatcache(TRUE, $keptCopy);
        $secretStatus = @lstat($keptCopy);
        // / When the group cannot be looked up, no copy's group can be judged, so a copy is not
        // / written again on every command for a group nobody here can name.
        $copyIsRight = ($keptKey === $candidateKey && is_array($secretStatus) && ((int)$secretStatus['mode'] & 0170000) === 0100000 && (int)$secretStatus['uid'] === 0 && ((int)$secretStatus['mode'] & 0777) === 0640 && ($webGroupNumber < 0 or (int)$secretStatus['gid'] === $webGroupNumber));
        if (!$copyIsRight) {
          list ($placeIsTrusted, $resolvedPlace, $placeNotice) = secretFolderIsTrusted(dirname($keptCopy));
          if ($placeIsTrusted) list ($copyWasWritten, $groupWasChanged, $writeFailure) = writeInstallSecretAsRoot($keptCopy, $candidateKey, $cleanRequiredVersion);
          else $writeFailure = 'The install secret was not written to '.$keptCopy.', because another account could replace it there. '.$placeNotice;
          // / A copy written whose group could not be given is warned about by the writer. A copy
          // / read first that could not be written is an error when the key is new or a rotation's.
          // / Otherwise the key was found in a copy, which still carries it, so it is only warned
          // / about, & whether the web server account reads the key is judged once every copy is
          // / settled, below.
          if (!$copyWasWritten && $keptCopy === $preferredCopy && ($sourceFile === '' or $rotationRequested or $keyWasGenerated)) errorEntry($writeFailure, 31020, FALSE);
          else if (!$copyWasWritten && $keptCopy === $preferredCopy) warningEntry($writeFailure.' The copy at '.$sourceFile.' still carries the key, & the next command run as root tries again.');
          // / A copy that is not there carries nothing & is only named.
          else if (!$copyWasWritten && !file_exists($keptCopy) && !is_link($keptCopy)) warningEntry($writeFailure);
          // / Another copy not known to carry the key, because it carries another, none, or could not
          // / be trusted to be read, & that could not be written goes, once the web server account
          // / can read the key in the copy it reads first, so no other key can ever be found there.
          // / One known to carry the key is left, because it is right but for its owner or mode.
          else if (!$copyWasWritten && $preferredIsReadable && $keptKey !== $candidateKey) {
            list ($staleWasRemoved, $removalNotice) = removeFileWithoutFollowing($keptCopy);
            if ($staleWasRemoved) warningEntry($writeFailure.' It was not known to carry the key in use, so it was removed. The copy at '.$preferredCopy.' carries the key.');
            else errorEntry($writeFailure.' It is not known to carry the key in use & could not be removed either. '.$removalNotice, 31020, FALSE); }
          // / While the copy read first is not right, the web server account may read this one, so
          // / one not known to carry the key is left, & the judgment below says what it means.
          else if (!$copyWasWritten && $keptKey !== $candidateKey && !$rotationRequested) warningEntry($writeFailure.' It is not known to carry the key in use, & is left while the copy at '.$preferredCopy.' is not right.');
          else if (!$copyWasWritten && $rotationRequested) errorEntry($writeFailure.' It still carries the old key until the next command run as root writes the new one there.', 31020, FALSE);
          else if (!$copyWasWritten) warningEntry($writeFailure.' It carries the key in use, & the next command run as root tries again.');
          else if (!$keyWasGenerated && !$rotationRequested && ($keptCopy === $sourceFile or $keptKey === $candidateKey)) warningEntry('The install secret at '.$keptCopy.' was rewritten as root with its existing key. Every session is unaffected.');
          else if (!$keyWasGenerated && !$rotationRequested && $keptWasRead && $keptKey !== '' && $keptKey !== $candidateKey && ltrim($keptVersion, 'vV') === $cleanRequiredVersion) warningEntry('The copy of the install secret at '.$keptCopy.' carried another key, & now carries the one at '.$sourceFile.'. Every session derived from its key is now invalid.');
          else if (!$keyWasGenerated && !$rotationRequested) warningEntry('The install secret at '.$sourceFile.' was copied to '.$keptCopy.' with its existing key. Every session is unaffected.');
          if ($copyWasWritten) $writtenCopies .= ($writtenCopies === '' ? '' : ' & ').$keptCopy; }
        if ($keptCopy === $preferredCopy) {
          $preferredIsRight = ($copyIsRight or $copyWasWritten);
          $preferredIsReadable = ($copyIsRight or ($copyWasWritten && $groupWasChanged)); }
        // / A rotation stops at the first copy it could not write, so no later copy gets a key the
        // / earlier one does not carry.
        if ($rotationRequested && !$copyIsRight && !$copyWasWritten) break; }
      // / The key is in use only when the web server account reads it.
      // / That account uses the first copy it can read & use, in the order root looked, & passes over one it cannot read & one not in the shape this core reads.
      // / So each copy is judged & read the same way, & the key root holds is in use only when the copy that account would use carries it.
      // / A rotation is done once the copy read first carries the new key, & is named here too when that account would not read it.
      foreach (array($preferredCopy, $otherCopy) as $judgedCopy) {
        if ($webCopy !== '' or !webServerCanReadSecretCopy($judgedCopy)) continue;
        list ($judgedWasRead, $judgedKey, $judgedVersion) = readSecretWithoutExecuting($judgedCopy);
        if ($judgedWasRead && ltrim($judgedVersion, 'vV') === $cleanRequiredVersion) {
          $webCopy = $judgedCopy;
          $webReadsKey = ($judgedKey === $candidateKey); } }
      if (!$webReadsKey) errorEntry('The web server account would not read the install secret root holds! '.($webCopy !== '' ? 'The first copy it would use, at '.$webCopy.', carries another key.' : 'It can use neither copy.'), 31020, FALSE);
      if (($rotationRequested && $preferredIsRight) or (!$rotationRequested && $webReadsKey)) {
        $SecretIsReady = TRUE;
        $ResolvedSecretKey = $candidateKey;
        if ($rotationRequested) warningEntry('The install secret was rotated. Every session derived from the old one is now invalid.');
        else if ($keyWasGenerated) warningEntry('A new install secret was written to '.$writtenCopies.'.'); } } }
  // / A folder that is there without its role is what keeps the copy there from being read first,
  // / so it is named, whether or not a key was put in place.
  if (!$folderIsReady && $folderState !== 'missing' && $olderSecretFile !== (string)$secretFile) {
    if ($folderState === 'not a folder') $folderProblem = $secretFolder.' is not a folder & is never followed. Remove it & run -fp as root.';
    else if ($folderState === 'unjudged') $folderProblem = $secretFolder.' has a group that cannot be judged, because the group '.$ApacheUser.' could not be looked up.';
    else if ($folderState === 'wrong') $folderProblem = $secretFolder.' is '.$folderOwnership.' '.$folderMode.' & needs root:'.$ApacheUser.' 0750. -fp gives the folder its role & copies the secret in.';
    else $folderProblem = 'another account could replace '.$secretFolder.'. '.$holdNotice;
    warningEntry('The web server account reads the install secret at '.$olderSecretFile.' first, because '.$folderProblem); }
  // / The lock is released & closed whatever happened above.
  if (is_resource($lockHandle)) {
    if ($lockIsHeld) @flock($lockHandle, LOCK_UN);
    @fclose($lockHandle); }
  // / Manually clean up sensitive memory. Every target is passed directly by reference.
  // / $ResolvedSecretKey is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $existingWasRead, $keyWasGenerated, $copyWasWritten, $groupWasChanged, $copyIsRight, $folderIsHeld, $folderIsReady, $secretIsUnreachable, $copyIsTrusted, $lockIsHeld, $lockWasCreated, $placeIsTrusted, $engineIsTrusted, $lockWouldBlock, $preferredIsRight, $keptWasRead, $keptIsTrusted, $preferredIsReadable, $staleWasRemoved, $judgedWasRead, $webReadsKey, $judgedKey, $judgedVersion, $judgedCopy, $webCopy, $existingKey, $existingVersion, $candidateKey, $cleanRequiredVersion, $writeFailure, $trustNotice, $holdNotice, $lockNotice, $folderProblem, $refusalNotice, $resolvedPlace, $placeNotice, $resolvedEngine, $engineNotice, $keptKey, $keptVersion, $keptNotice, $removalNotice, $secretFolder, $olderSecretFile, $preferredCopy, $otherCopy, $keptCopy, $sourceFile, $candidateFile, $refusedFile, $folderState, $folderMode, $folderOwnership, $lockPath, $writtenCopies, $secretStatus, $candidateFiles, $refusedCopies, $keptCopies, $webAccountNumber, $webGroupNumber, $lockDeadline, $lockHandle, $secretFile, $requiredSecretVersion, $rotationRequested);
  return array($SecretIsReady, $ResolvedSecretKey); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to load an install secret from a file, or to create one when none exists.
// / Accepts the absolute path of the secret file.
// / Returns a readiness boolean & the secret key, in that order.
// / A failure returns FALSE & an empty string, which the caller must treat as a failed installation.
// / The file is written with only the secret & is checked byte for byte afterwards, & a file that fails that check is deleted.
// / Appending to an existing file would produce a file that loads & carries a key nobody generated.
// / A per user secret is chmod 0600 on every path, because a secret another account can read is not a secret.
// / The install wide secret is never changed here, because the web server account only reads it.
// / This is called for the install wide secret & for a per user secret in a folder of its own, so it lives here rather than twice.
// / $SecretKey is capitalized against convention 3 because the secret file defines it under that name.
// / It is a function local here regardless of its case & is shredded before this function returns.
// /
// / Root does not come here, & the web server account only reads.
// / Root reads the install wide secret through resolveInstallSecretAsRoot, which never requires it.
// / The web server account neither removes nor creates the file root wrote, because a secret it could write is a secret root would one day have to read.
// / A file it cannot use is reported, & -fp as root replaces it.
// / The web server account never runs the install secret, & reads it with readSecretWithoutExecuting, as root does.
// / A per user secret is its own account's file, & is required & created by that account alone, in a folder only it can reach.
// / That includes the web server account's own per user secret.
// / The install wide secret is the one name ending in -secret.php.
// / verifyInstallation names a per user secret with its account after that, & it is the only place either name is built.
function resolveSecretFile($secretFile, $requiredSecretVersion) {
  // / Set variables.
  global $LogFile, $ApacheUser, $CurrentUser, $EnableMemoryProtection, $CoreLoaded;
  $SecretIsReady = FALSE;
  $ResolvedSecretKey = $secret = $secretFileContent = '';
  $detectedSecretVersion = $cleanRequiredVersion = $rotationNotice = $strayOutput = '';
  $secretGenerated = $existingIsUsable = $loggingIsReady = $payloadIsWellFormed = $secretIsReadable = $creationIsPermitted = $isInstallSecret = $secretWasRead = FALSE;
  $bytesWritten = 0;
  // / Both are only ever defined by the require or the read below.
  // / They are initialized here so a path that reaches neither leaves neither undefined.
  $SecretKey = '';
  $SecretVersion = '';
  $cleanRequiredVersion = ltrim(trim((string)$requiredSecretVersion), 'vV');
  if (isset($LogFile) && is_string($LogFile) && $LogFile !== '') $loggingIsReady = TRUE;
  $isInstallSecret = (substr(basename((string)$secretFile), -11) === '-secret.php');
  $creationIsPermitted = ((string)$CurrentUser !== (string)$ApacheUser or !$isInstallSecret);
  // / Load an existing secret file & decide whether this core may still use it.
  if (file_exists($secretFile)) {
    if ($creationIsPermitted) @chmod($secretFile, 0600);
    // / A file this process cannot read must NEVER reach require. PHP reports that as a
    // / raw warning, which reaches the page & names an absolute path outside the web root.
    // / This happens when a root command line run created the file, because it is then
    // / owned by root & the chmod above fails silently for every other account.
    $secretIsReadable = is_readable($secretFile);
    if (!$secretIsReadable) {
      $rotationNotice = 'The secret file at '.$secretFile.' exists but cannot be read by '.$CurrentUser.'. It was almost certainly created by a different account. '.($creationIsPermitted ? 'Delete it as root & run the command again.' : 'Run the -fp argument as root to replace it.');
      if ($loggingIsReady) warningEntry($rotationNotice);
      else error_log('WARNING!!! HRConvert2: '.$rotationNotice); }
    // / The install secret, read by the web server account, is read & never run.
    else if (!$creationIsPermitted) {
      list ($secretWasRead, $SecretKey, $SecretVersion) = readSecretWithoutExecuting($secretFile);
      $detectedSecretVersion = ltrim(trim((string)$SecretVersion), 'vV'); }
    else {
      // / require rather than require_once, because a second file may be resolved in the same request.
      // / The buffer is what stops a malformed file printing the key into the response.
      ob_start();
      require ($secretFile);
      $strayOutput = ob_get_clean();
      $detectedSecretVersion = ltrim(trim((string)$SecretVersion), 'vV'); }
    // / A file that emitted anything did not parse as PHP & cannot be trusted or reused.
    if ($strayOutput !== '') {
      $SecretKey = '';
      $rotationNotice = 'The secret file at '.$secretFile.' emitted '.strlen($strayOutput).' byte(s) instead of parsing as PHP. It is malformed & is being replaced.'; }
    else if (!empty($SecretKey) && strlen($SecretKey) === 64 && $detectedSecretVersion !== '' && $detectedSecretVersion === $cleanRequiredVersion) $existingIsUsable = TRUE; }
  // / The web server account reports a file it cannot use & leaves it for root to replace.
  if (file_exists($secretFile) && !$existingIsUsable && !$creationIsPermitted) {
    $rotationNotice = 'The install secret cannot be used by '.$CurrentUser.' & is not replaced from a web request. Run  sudo php convertCore.php -fp  to replace it.';
    if ($loggingIsReady) warningEntry($rotationNotice);
    else error_log('WARNING!!! HRConvert2: '.$rotationNotice);
    $SecretKey = ''; }
  // / Remove a secret file this core may not use, so a replacement can take its place.
  // / A file carrying no version predates version pinning & is always replaced.
  if (file_exists($secretFile) && !$existingIsUsable && $creationIsPermitted) {
    if ($rotationNotice === '') {
      $rotationNotice = 'The secret file at '.$secretFile.' reports version '.($detectedSecretVersion === '' ? 'none' : 'v'.$detectedSecretVersion).' & this core requires v'.$cleanRequiredVersion.'. It is being replaced & every session derived from it is now invalid.';
      if ($loggingIsReady) warningEntry($rotationNotice);
      else error_log('WARNING!!! HRConvert2: '.$rotationNotice); }
    @unlink($secretFile);
    // / A file that survives the unlink belongs to another account, & saying so once beats failing the same way on every request.
    // / Only a standard user reaches this, for a secret of its own in its own folder, which -fp does not manage.
    if (file_exists($secretFile)) {
      $rotationNotice = 'The secret file at '.$secretFile.' could not be removed by '.$CurrentUser.'. Delete it as root & run the command again.';
      if ($loggingIsReady) errorEntry($rotationNotice, 30, FALSE);
      else error_log('ERROR!!! HRConvert2-30: '.$rotationNotice); }
    // / Discard the key that was loaded from the file being replaced.
    $SecretKey = ''; }
  // / Create a secret file whenever a usable one is not already in place.
  if (!$existingIsUsable && !file_exists($secretFile) && $creationIsPermitted) {
    list ($secret, $secretGenerated) = generateInstallSecret();
    if ($secretGenerated) {
      // / Build the obfuscated payload out of transient buffers.
      $secretFileContent = addSillyString($secret, $cleanRequiredVersion);
      // / Refuse to write a payload that would not parse.
      // / The opening tag must be followed by whitespace & the file must carry no closing tag.
      if (preg_match('/^<\?php\s/', $secretFileContent) === 1 && strpos($secretFileContent, '?>') === FALSE) $payloadIsWellFormed = TRUE;
      if (!$payloadIsWellFormed) {
        $rotationNotice = 'The generated secret file payload is malformed & was not written to '.$secretFile.'.';
        if ($loggingIsReady) warningEntry($rotationNotice);
        else error_log('WARNING!!! HRConvert2: '.$rotationNotice); }
      else {
        // / Commit the buffer to disk under an immediate exclusive lock.
        // / The write is suppressed & then checked, rather than left to print a raw PHP warning.
        // / A failure here was already handled below & never reported, while PHP printed its own
        // / message first. A standard user whose secret lives in a directory another user created
        // / hits exactly that, because the directory is private to whoever made it first.
        $bytesWritten = @file_put_contents($secretFile, $secretFileContent, LOCK_EX);
        // / Check that the secret, & only the secret, was written to the file.
        if ($bytesWritten !== strlen($secretFileContent)) {
          $rotationNotice = 'The install secret could not be written to '.$secretFile.'. The directory may belong to another user, or be read only.';
          if ($loggingIsReady) warningEntry($rotationNotice);
          else error_log('WARNING!!! HRConvert2: '.$rotationNotice);
          if (file_exists($secretFile)) @unlink($secretFile); }
        else {
          @chmod($secretFile, 0600);
          // / Read the new file back exactly as every later request will read it.
          // / A file that does not return the key it was built from is deleted, not kept.
          $SecretKey = '';
          $SecretVersion = '';
          ob_start();
          require ($secretFile);
          $strayOutput = ob_get_clean();
          if ($strayOutput === '' && $SecretKey === $secret) {
            $ResolvedSecretKey = $secret;
            $SecretIsReady = TRUE; }
          else {
            @unlink($secretFile);
            $rotationNotice = 'The secret file written to '.$secretFile.' did not read back correctly & was removed.';
            if ($loggingIsReady) warningEntry($rotationNotice);
            else error_log('WARNING!!! HRConvert2: '.$rotationNotice); } } } } }
  // / Adopt the key from a file that was already usable.
  if ($existingIsUsable) {
    $ResolvedSecretKey = $SecretKey;
    $SecretIsReady = TRUE; }
  // / Manually clean up sensitive memory. Every target is passed directly by reference.
  // / $ResolvedSecretKey is not purged, because it is the return value.
  // / PHP separates the shared buffer when $SecretKey is shredded, so the return survives intact.
  if (!purgeSensitiveMemory($EnableMemoryProtection, $SecretKey, $SecretVersion, $secret, $secretFile, $secretFileContent, $bytesWritten, $secretGenerated, $existingIsUsable, $detectedSecretVersion, $cleanRequiredVersion, $rotationNotice, $strayOutput, $loggingIsReady, $payloadIsWellFormed, $secretIsReadable, $creationIsPermitted, $isInstallSecret, $secretWasRead, $requiredSecretVersion)) {
    // / The failure is already logged under this function's name & is not logged again here.
    // / A secret that loaded correctly is still correct, so readiness is not withdrawn.
    $SecretIsReady = $SecretIsReady; }
  return array($SecretIsReady, $ResolvedSecretKey); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to find a folder for the per user secret of a command line account.
// / Accepts the application's slug & the account's slug, both already sanitized.
// / Returns a readiness boolean & the folder, in that order. A failure returns FALSE & an
// / empty string, which the caller must treat as no secret at all.
// / Every account gets a folder named for itself in the temporary directory.
// / The home directory is not used, because HOME is the cache folder for every account but root.
// / The temporary directory is shared, so another account can create the name first, as a folder of its own, a folder anybody may write or a link.
// / A folder is used only when this account can write it, it is not a link & its mode is exactly 0700.
// / Each refusal says why, because a secret kept where another account can reach it is not a secret.
function resolveUserSecretFolder($applicationSlug, $accountSlug) {
  // / Set variables.
  global $LogFile, $EnableMemoryProtection;
  $FolderIsReady = FALSE;
  $UserSecretFolder = '';
  $candidateFolder = $folderNotice = '';
  $loggingIsReady = FALSE;
  if (isset($LogFile) && is_string($LogFile) && $LogFile !== '') $loggingIsReady = TRUE;
  $candidateFolder = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'.'.$applicationSlug.'-'.$accountSlug;
  if (!file_exists($candidateFolder) && !is_link($candidateFolder)) @mkdir($candidateFolder, 0700, TRUE);
  // / A folder this account owns can only have lost bits to the umask, so it is put back to
  // / 0700. The chmod fails silently on a folder another account owns, & the checks refuse it.
  if (is_dir($candidateFolder) && !is_link($candidateFolder)) @chmod($candidateFolder, 0700);
  clearstatcache(TRUE, $candidateFolder);
  if (is_link($candidateFolder)) $folderNotice = 'The per user secret folder at '.$candidateFolder.' is a link & was not used. Delete it as root & run the command again.';
  else if (!is_dir($candidateFolder)) $folderNotice = 'The per user secret folder at '.$candidateFolder.' could not be created.';
  else if (!is_writable($candidateFolder)) $folderNotice = 'The per user secret folder at '.$candidateFolder.' belongs to another account & was not used. Delete it as root & run the command again.';
  else if ((fileperms($candidateFolder) & 0777) !== 0700) $folderNotice = 'The per user secret folder at '.$candidateFolder.' can be reached by other accounts & was not used. Delete it as root & run the command again.';
  else {
    $FolderIsReady = TRUE;
    $UserSecretFolder = $candidateFolder; }
  if (!$FolderIsReady) {
    if ($loggingIsReady) warningEntry($folderNotice);
    else error_log('WARNING!!! HRConvert2: '.$folderNotice); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $candidateFolder, $folderNotice, $loggingIsReady, $applicationSlug, $accountSlug);
  return array($FolderIsReady, $UserSecretFolder); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / THIS STAYS. verifyInstallation calls it & verifyInstallation runs BEFORE the Engine
// / is loaded, so an application needs its own. It was moved once & took -v down.
// / The top level check does not catch this: verifyInstallation is what runs at top
// / level, & this is one call deeper. Check the callers of the callers too.
// / A function to detect whether this installation is running inside a container.
// / Returns TRUE only when two independent signals agree, because a false positive would
// / silently relax the sandbox requirement on a bare metal server.
// / A false negative is preferable. It refuses conversions in a container, which is
// / visible & correctable, rather than running unprotected on hardware, which is neither.
function verifyContainerEnvironment() {
  // / Set variables.
  global $EnableMemoryProtection;
  $RunningInContainer = FALSE;
  $dockerEnvExists = $cgroupIndicatesContainer = FALSE;
  $cgroupContents = '';
  // / Docker creates this file in every container it starts. Podman creates its own.
  // / Neither file exists anywhere else, so either one on its own is conclusive.
  if (file_exists('/.dockerenv') or file_exists('/run/.containerenv')) $dockerEnvExists = TRUE;
  // / Several runtimes announce themselves in the environment.
  if (getenv('container') !== FALSE && trim((string)getenv('container')) !== '') $dockerEnvExists = TRUE;
  // / The init process of a container reports a container runtime in its cgroup path.
  // / Under cgroup version two this often reads 0::/ & names nothing, so it cannot be required.
  $cgroupContents = @file_get_contents('/proc/1/cgroup');
  if (is_string($cgroupContents)) {
    if (strpos($cgroupContents, 'docker') !== FALSE or strpos($cgroupContents, 'containerd') !== FALSE or strpos($cgroupContents, 'kubepods') !== FALSE or strpos($cgroupContents, 'lxc') !== FALSE) $cgroupIndicatesContainer = TRUE; }
  // / Any one of these is conclusive, because none is true on a host that is not a container.
  if ($dockerEnvExists or $cgroupIndicatesContainer) $RunningInContainer = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dockerEnvExists, $cgroupIndicatesContainer, $cgroupContents);
  return $RunningInContainer; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to stop root running code another account could have changed.
// / Accepts the installation tree. Returns whether root may trust it.
// / Halts with error 28008 when root may not, unless the command is a repair.
// /
// / Root runs nothing the web server account can change.
// / -fp gives the installation to root, & this holds it to that afterwards.
// / A command run as root reads the whole tree with treeIsTrustedByRoot before anything in it but the kernel has run.
// / A tree that fails stops the command, names the first thing that failed & says how to give it back to root.
// / Every user but root is unaffected, because nothing they run is root's to protect.
// /
// / The repair still runs on a tree that fails, & says so first.
// / -fp & --setup are what give a tree to root, & a fresh clone or an older installation is not root's until one of them has run.
// / Refusing them would leave nothing to run, & would protect nothing, because an account that could change the tree could change this file.
// / --config --repair is the third, because neither of the others can run without a configuration to read.
// /
// / The kernel is loaded first, because this check is part of it.
// / A kernel another account can change inside a tree root otherwise owns is the one case seen after it has run, & it is still refused before anything else loads.
function verifyCodeIsTrustedByRoot($treePath) {
  // / Set variables.
  global $EnableMemoryProtection, $RunningFromCLI;
  $CodeIsTrusted = TRUE;
  $trustNotice = $cliCommand = $cliTarget = $cliSecondTarget = '';
  $cliArguments = array();
  $cliArgumentCount = 0;
  $repairRequested = $cliConfirmed = $cliVectorParsed = FALSE;
  // / quickDie writes for a terminal only once it knows it has one, & verifyInstallation, which
  // / decides that, has not run yet. It decides the same way again when it does.
  if (!isset($RunningFromCLI)) $RunningFromCLI = (PHP_SAPI === 'cli');
  if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
    list ($CodeIsTrusted, $trustNotice) = treeIsTrustedByRoot($treePath, array(rtrim((string)$treePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'DATA'));
    if (!$CodeIsTrusted) {
      list ($cliArguments, $cliArgumentCount, $cliCommand, $cliTarget, $cliSecondTarget, $cliConfirmed, $cliVectorParsed) = parseArgumentVector(isset($_SERVER['argv']) ? $_SERVER['argv'] : array());
      $repairRequested = (in_array($cliCommand, array('-fp', '--fix-permissions', '--setup'), TRUE) or ($cliCommand === '--config' && $cliTarget === '--repair'));
      if (!$repairRequested) quickDie('Root refused to run the installation at '.$treePath.', because an account other than root could have changed it. '.$trustNotice.' Run  sudo php convertCore.php -fp  to give it back to root. Where -fp says root still may not run it, remove or put right what it names. Any other account may still run -v in the meantime.', 28008);
      warningEntry('Root is running code an account other than root could have changed, because '.$cliCommand.' is what gives the installation at '.$treePath.' back to root. '.$trustNotice);
      print(PHP_EOL.'WARNING. Root is running code another account could have changed.'.PHP_EOL.'  '.$trustNotice.PHP_EOL.'  '.$cliCommand.' gives the installation back to root.'.PHP_EOL); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $trustNotice, $cliCommand, $cliTarget, $cliSecondTarget, $cliArguments, $cliArgumentCount, $repairRequested, $cliConfirmed, $cliVectorParsed, $treePath);
  return $CodeIsTrusted; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify that this installation is complete, current & usable.
// / Returns a verification boolean, the path of the config file, the version reported by
// / versionInfo.php & the core loaded flag, in that order.
// / Every failure other than the secret is fatal & dies here, because nothing downstream
// / can operate without a config file or against a mismatched core.
// / The secret file is only touched by an authorized combination of user & context.
// / root from the command line, or the web server user from a web request, gets the install
// / wide secret, & so does the web server user on the command line for the commands that
// / operate the listener. Any other command line user gets a secret of their own in a folder
// / of their own in the temporary directory, which lets them run diagnostics without ever
// / reading the install wide one.
// / Any other combination gets no secret at all & fails verification.
function verifyInstallation() {
  // / Set variables.
  global $URL, $VirusScan, $AllowUserVirusScan, $InstLoc, $ServerRootDir, $ConvertLoc, $LogDir, $LogFile, $ApplicationName, $ApplicationTitle, $SupportedLanguages, $DefaultLanguage, $AllowUserSelectableLanguage, $SupportedGuis, $DefaultGui, $AllowUserSelectableGui, $DeleteThreshold, $Verbose, $MaxLogSize, $Font, $ButtonStyle, $SupportedColors, $AllowUserSelectableColor, $ColorToUse, $ShowGUI, $ShowFinePrint, $TOSURL, $PPURL, $ScanCoreMemoryLimit, $ScanCoreChunkSize, $ScanCoreDebug, $ScanCoreVerbose, $SpinnerStyle, $SpinnerColor, $AllowUserShare, $AllowUserIdentify, $SupportedConversionTypes, $VersionInfoFile, $Version, $UserArchiveArray, $UserDearchiveArray, $UserDocumentArray, $UserSpreadsheetArray, $UserPresentationInputArray, $UserPresentationOutputArray, $UserXPSInputArray, $UserXPSOutputArray, $UserImageArray, $UserMediaInputArray, $UserMediaOutputArray, $UserVideoInputArray, $UserVideoOutputArray, $UserStreamArray, $UserDrawingArray, $UserSVGInputArray, $UserSVGOutputArray, $UserModelArray, $UserSubtitleInputArray, $UserSubtitleOutputArray, $UserPDFWorkArr, $RARArchiveMethod, $RetryCount, $HomeLoc, $ProprietaryLoc, $StreamWatchTimeout, $StreamConnectionTimeout, $AllowStreamOverHTTP, $StreamInspectionLayers, $StreamInspectionFilesPerLayer, $DefaultStreamInspectionForfeitAction, $MaxStreamInspectionFileSize, $UniqueDailyLogHash, $AppendLogHashToLogFiles, $SecretKey, $SecretFile, $RequiredSecretVersion, $AllowSCADIncludeResolution, $SCADConversionTimeout, $UserSCADArray, $ConfigVersion, $HRConvertVersion, $DeleteBuildEnvironment, $DeleteDevelopmentDocumentation, $RequiredGuiVersion, $RequiredLanguageVersion, $UsePyMeshLab, $RequiredConfigVersion, $EnableAutoUpdates, $AutoUpdateTargetVersion, $UpdateSourceRepository, $MaxUpdatePackageSize, $UpdateConnectionTimeout, $BackupLoc, $RequireSandbox, $ThrowSandboxWarning, $RequireSandboxOnDocker, $RunningFromCLI, $CurrentUser, $RunningAsRoot, $RunningInContainer, $ApacheUser, $PermissionLevels, $AllowBootableIsoImage, $UserBootableIsoArray, $UserEbookInputArray, $UserEbookOutputArray, $EnableMemoryProtection, $ResourceAwarenessActive, $EnableResourceAwareness, $RequireResourceAwareness, $ManagerSocketDir, $DirSep, $CoreManagerVersion, $CoreManagerSubprocessPollInterval, $ResourcePollInterval, $WorkerReapInterval, $WorkerStaleGracePeriod, $TotalResourceBudget, $ReserveResourcePercentage, $MaxConcurrentWorkers, $MaxExpectedRuntime, $MaxRuntimeExtensions, $DefaultConversionCost, $DefaultExpectedRuntime, $LowStorageWarning, $StorageReserve, $CoreLoaded, $PrimaryConvertLoc, $AdditionalConvertLocs, $StorageCleanupInterval, $EnablePerConversionLimits, $MaximumPerConversionResources, $DefaultPerConversionResources, $MinimumPerConversionResources, $RequiredSetupCoreVersion, $RequiredConfigScript, $RequiredDependencyCoreVersion, $RequiredDependsVersion, $RequiredPipelineCoreVersion, $RequiredEngineVersion, $AllowUnprivilegedNamespaces, $MaintainHTAccess, $DataRootLoc, $SessionLoc, $CacheLoc, $EngineDir, $SecretLoc, $WorkflowLoc, $EnableWorkflows, $EnableWorkflowBuilder, $EnableWorkflowImportExport, $MaxWorkflowSteps, $MaxWorkflowFiles, $MaxWorkflowRuntime, $MaxConcurrentWorkflows, $RequiredWorkflowCoreVersion,
    $UserModelInputArray, $UserModelOutputArray, $UserDrawingInputArray, $UserDrawingOutputArray, $UserImageInputArray, $UserImageOutputArray, $UserSCADInputArray, $UserSCADOutputArray, $EnvironmentManagerMayRepair, $EnvironmentManagerMayRewriteConfigs, $AllowUserURLDownload, $GuiMaxWidth, $DefaultVirusScanner, $URLDownloadMaximumBytes, $URLDownloadsPerSession, $ScanURLDownloads, $LogoURL, $ResolvedLogoURL, $PermittedPrivateRanges, $EngineApplicationVersion, $CodeIsTrusted, $HostedLoc, $ApplicationLoc, $SecretPlacesWereOpen;
  $rootRecord = array();
  $legacyWasRemoved = FALSE;
  $legacyNotice = $homeTarget = '';
  // / Root keeps its own home.
  // / Every tool a command runs reads its configuration from HOME, curl its .curlrc & pip its pip.conf among them.
  // / $HomeLoc is the cache folder, which the web server account owns, so root never takes it.
  // / Root uses its own home from the password database, set here before config.php is loaded, because it needs no configuration to find it.
  // / Every other account's HOME is set once config.php has been read, further down, because the cache folder is derived from the configured data location.
  if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
    if (function_exists('posix_getpwuid')) $rootRecord = @posix_getpwuid(0);
    putenv('HOME='.((is_array($rootRecord) && isset($rootRecord['dir']) && (string)$rootRecord['dir'] !== '') ? (string)$rootRecord['dir'] : '/root')); }
  $CoreLoaded = TRUE;
  $InstallationIsVerified = $RunningFromCLI = $RunningAsRoot = $RunningInContainer = FALSE;
  $secretAuthorized = $userSecretAuthorized = $secretIsReady = $configIsValid = $componentIsAvailable = FALSE;
  $SecretKey = $CurrentUser = $detectedConfigVersion = $configFile = $secretFolder = '';
  $applicationSlug = $legacySecretFile = $olderSecretFile = $installSecretFile = $webCandidate = $trustNotice = $accountSlug = $listenerCommand = '';
  $webSecretsState = $webSecretsMode = $webSecretsOwnership = $resolvedDataLocation = $dataLocationNotice = '';
  $webCandidates = $webTried = array();
  $webCandidateIndex = 0;
  $copyIsTrusted = $dataLocationIsTrusted = FALSE;
  $secretFolderIsReady = $configRepairRequested = $bootstrapWritten = FALSE;
  $bootstrapSetup = $bootstrapEngine = '';
  $listenerArguments = array();
  $listenerArgumentCount = $bootstrapCount = 0;
  $entryScript = '';
  $runningIsInstalled = FALSE;
  $SecretFile = '';
  // / Set by prepareDataLocationAsRoot or resolveInstallSecretAsRoot when a place the secret is kept
  // / could be written by an account other than root & the web server account. See
  // / installSecretCopyIsTrusted.
  $SecretPlacesWereOpen = FALSE;
  $missingConfigVars = array();
  $blockingWereJudged = $limitingWereJudged = FALSE;
  $blockingFolders = $limitingFolders = $limitingLocations = $additionalLocation = array();
  // / Detect if the application is being run from the command line.
  if (php_sapi_name() === 'cli') $RunningFromCLI = TRUE;
  // / Detect the current user account that is running the application.
  // / The application inherits the execution context of this user.
  // / This must happen for EVERY context, not only for a web request. A command line
  // / invocation that skipped this left the user unknown, which made root unrecognizable &
  // / sent every command line user down the per user secret path including root.
  // / Some systems do not have posix extensions, so shell_exec is used instead.
  $CurrentUser = trim((string)shell_exec('whoami'));
  if ($CurrentUser === 'root') $RunningAsRoot = TRUE;
  $ApacheUser = 'www-data';
  // / Every folder the web server account creates is its own & nobody else's.
  // / Apache, PHP, the sandbox & every converter run as the web server account, which owns the folder, so nothing it serves or converts needs the group or the world to read it.
  $PermissionLevels = 0700;
  // / Detect if the application is being run inside a container.
  $RunningInContainer = verifyContainerEnvironment();
  // / Decide which secret, if any, this combination of user & context may have.
  // / The install wide secret derives every web session, so only the two contexts that
  // / legitimately serve or maintain those sessions may read it.
  if ($RunningFromCLI && $RunningAsRoot) $secretAuthorized = TRUE;
  if (!$RunningFromCLI && $CurrentUser === $ApacheUser) $secretAuthorized = TRUE;
  // / An administrator on the command line who is neither root nor the web server user is
  // / given a secret of their own. It lets them run diagnostics without ever reading the
  // / install wide secret, & it cannot be used to derive or forge a web session.
  if ($RunningFromCLI && !$secretAuthorized) $userSecretAuthorized = TRUE;
  // / The web server user on the command line is denied the install secret by the rules above, & is granted it here for the commands that operate the listener.
  // / Each of them derives a startup key or a socket key from the install secret, & a per user secret derives keys no manager accepts.
  // / The Engine refuses the listener commands to every account but root & the web server user before any key is derived.
  // / This list names the same commands as its dispatcher.
  // / The web server user's group can read the secret file, so this grants it nothing new.
  // / --run-core-manager is how a service unit starts the listener, & without it every socket key the unit derived would differ from the web workers'.
  // / -c is granted it too, because root hands every sweep to the web server user, & the sweep finds its data location by asking the listener.
  // / --run-workflow is granted it, because a workflow's worker continues a web session & checks a startup key a web request made with the install secret.
  // / It logs where a web request logs, for the same reason.
  // / The command is read with the kernel's parser, because parseCommandLine has not run yet & two places deciding what the first argument means come to disagree.
  if ($RunningFromCLI && $CurrentUser === $ApacheUser) {
    list ($listenerArguments, $listenerArgumentCount, $listenerCommand) = parseArgumentVector(isset($_SERVER['argv']) ? $_SERVER['argv'] : array());
    if (in_array($listenerCommand, array('--start-core-manager', '--start-manager', '--run-core-manager', '-l', '--listen', '-k', '--kill', '--kill-all-workers', '--kill-every-worker', '--status', '-c', '--clean', '--run-workflow'), TRUE)) {
      $secretAuthorized = TRUE;
      $userSecretAuthorized = FALSE; } }
  // / Define what version of HRConvert2 this core file represents.
  // / Note that this number does not have to match the version numbers of individual components listed below.
  // / The version of the core is typically several versions ahead of individual component versions. This is normal.
  $HRConvertVersion = 'v4.1.5';
  $HRConvertVersion = ltrim($HRConvertVersion, 'vV');
  // / Handed to the Engine here, where it is defined, so there is only ever one copy.
  $EngineApplicationVersion = $HRConvertVersion;
  // / Define the minimum acceptable config.php version that this convertCore.php can accept.
  // / This is only raised when a release adds or removes a config setting.
  // / A release that changes no settings leaves this alone, so existing config files keep working.
  // / Any config.php version that is greater (newer) than the version listed below is considered acceptable.
  $RequiredConfigVersion = 'v4.1.5';
  $RequiredConfigVersion = ltrim($RequiredConfigVersion, 'vV');
  // / Define the GUI version that this convertCore.php can accept.
  // / Note that this check looks for the component version to be identical to what is listed below.
  // / A GUI version that does not exactly match the version listed below is not considered acceptable.
  // / This is because Guis are not always guaranteed to be forward or reverse compatible.
  $RequiredGuiVersion = 'v4.1.5';
  $RequiredGuiVersion = ltrim($RequiredGuiVersion, 'vV');
  // / Define the Language Pack version that this convertCore.php can accept.
  // / Note that this check looks for the component version to be identical to what is listed below.
  // / A Language Pack version that does not exactly match the version listed below is not considered acceptable.
  // / This is because Language Packs are not always guaranteed to be forward or reverse compatible.
  $RequiredLanguageVersion = 'v4.1.5';
  $RequiredLanguageVersion = ltrim($RequiredLanguageVersion, 'vV');
  // / No Core Manager version is pinned here. Every manager role is a subcomponent of the
  // / Engine & getAcceptedManagers() inside the Engine pins each one exactly.
  // / The Setup Core component version this core requires.
  // / This is an EXACT match. A component built for another core may not be called safely.
  // / Setup Core holds the configuration model, so this MUST be raised whenever
  // / $RequiredConfigVersion is raised. Forgetting does not break anything immediately.
  // / The utility reports a variable it does not know as unaccounted & carries on.
  $RequiredSetupCoreVersion = 'v4.1.5';
  $RequiredSetupCoreVersion = ltrim($RequiredSetupCoreVersion, 'vV');
  // / The Dependency Core component version this core requires.
  // / This is an EXACT match. A component built for another core may not be called safely.
  $RequiredDependencyCoreVersion = 'v4.0.8';
  $RequiredDependencyCoreVersion = ltrim($RequiredDependencyCoreVersion, 'vV');
  // / The dependency manifest version this core requires.
  // / Raise this whenever a dependency is added, removed, or its minimum version moves.
  // / A manifest from another release may name a package that no longer exists.
  $RequiredDependsVersion = 'v4.1.2';
  $RequiredDependsVersion = ltrim($RequiredDependsVersion, 'vV');
  // / The Pipeline Core component version this core requires.
  // / This is an exact match, because a component built for another core may declare an entry point whose arguments have moved, or capabilities this core cannot honour.
  // / Raise this when the Pipeline Core itself changes & for nothing else.
  // / It is an Engine core, at Resources/Engine/Cores/pipelineCore.php.
  // / The pipelines this application accepts are listed in its contract, in app-engine-config.php, so adding a pipeline or moving one to a new version raises nothing here.
  $RequiredPipelineCoreVersion = 'v4.1.2';
  $RequiredPipelineCoreVersion = ltrim($RequiredPipelineCoreVersion, 'vV');
  // / The Workflow Core component version this core requires.
  // / This is an exact match, because a component built for another core may call an operation with arguments that have moved.
  // / Raise this when the Workflow Core itself changes & for nothing else. It is an Engine core, at Resources/Engine/Cores/workflowCore.php.
  $RequiredWorkflowCoreVersion = 'v4.1.5';
  $RequiredWorkflowCoreVersion = ltrim($RequiredWorkflowCoreVersion, 'vV');
  // / The Engine version this application requires.
  // / This is an EXACT match & it is free, because the Engine is bundled with this release
  // / rather than installed beside it. The updater replaces both together, so the pin & the
  // / file it points at cannot drift apart on an installation nobody has edited by hand.
  // / An administrator who edits the Engine by hand loses the edit at the next update. That
  // / is the same bargain every bundled component makes.
  // / --build cannot write this pin, because it runs inside the Engine. Move it by hand.
  $RequiredEngineVersion = 'v4.1.5';
  $RequiredEngineVersion = ltrim($RequiredEngineVersion, 'vV');
  // / The bootstrap script version this core expects, as an exact match.
  // / The Engine compares the script against this, & -fp disables it as root when it differs.
  $RequiredConfigScript = 'v4.0.1';
  $RequiredConfigScript = ltrim($RequiredConfigScript, 'vV');
  // / The secret file version this core requires.
  // / This is an EXACT match. A secret file reporting anything else is deleted & rewritten.
  // / Raise this to force every installation in the wild off an exposed or outdated secret.
  // / Raising it invalidates every active session on the next request. That is the point.
  $RequiredSecretVersion = 'v3.7.7';
  $RequiredSecretVersion = ltrim($RequiredSecretVersion, 'vV');
  // / Define absolute paths for files that we only have relative paths for.
  // / Every path below uses DIRECTORY_SEPARATOR rather than $DirSep, & that is deliberate.
  // / This function runs before verifyGlobals(), which is where $DirSep is set, so the
  // / global is still an empty string here & would silently concatenate two path segments
  // / into one. The globals line declares it because later code in this function reads it,
  // / not because it may be used for a path.
  $configFile = realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'config.php');
  $VersionInfoFile = realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'versionInfo.php');
  // / Check for required files & stop execution if they are missing.
  if (!file_exists($VersionInfoFile)) quickDie('Could not process the HRConvert2 Version Information file (versionInfo.php)!', 24000);
  else require_once ($VersionInfoFile);
  // / A missing configuration is fatal EXCEPT to the one command that repairs it.
  // / Every other path needs settings & cannot invent them, so dying here is right.
  // / --config --repair exists to write a configuration & requiring one to run it is a
  // / loop with no way in. An installation that has lost config.php could only be fixed
  // / by copying a file from somewhere else, which is the situation this removes.
  // / The check is on the RAW arguments because nothing has been parsed yet. Both words
  // / are required, so a bare --config still dies rather than running against nothing.
  if (PHP_SAPI === 'cli' && isset($_SERVER['argv']) && is_array($_SERVER['argv'])) $configRepairRequested = in_array('--config', $_SERVER['argv'], TRUE) && in_array('--repair', $_SERVER['argv'], TRUE);
  if (!file_exists($configFile) && !$configRepairRequested) quickDie('Could not process the HRConvert2 Configuration file (config.php)!', 0);
  // / Generate & STOP. The boot sequence below this point reads settings at every step,
  // / so there is nothing to be gained by continuing without them & a great deal to go
  // / wrong. An operator runs the command again once the file exists.
  else if (!file_exists($configFile)) {
    $bootstrapSetup = realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'Engine'.DIRECTORY_SEPARATOR.'Cores'.DIRECTORY_SEPARATOR.'setupCore.php');
    if ($bootstrapSetup === FALSE) quickDie('No configuration file & no Setup Core to write one!', 0);
    // / $InstLoc & $DirSep normally come FROM config.php, which is the file this branch
    // / exists because it is missing. Without them a relative template path resolves from
    // / the filesystem root & the template is reported missing when it is sitting right
    // / there. That is what happened.
    // / Everything the Engine reads on this path is set here, because nothing else will.
    global $InstLoc, $DirSep, $ApplicationName, $Lol, $EnableMemoryProtection, $Verbose;
    $InstLoc = dirname(__FILE__);
    $DirSep = DIRECTORY_SEPARATOR;
    $ApplicationName = 'HRConvert2';
    $Lol = PHP_EOL;
    $EnableMemoryProtection = FALSE;
    $Verbose = TRUE;
    // / The Engine reads the model by NAME & engineConfig.php is not loaded on this path,
    // / so the name is supplied here. Without it the generator has no model & writes
    // / nothing, which is a silent failure rather than a loud one.
    // / GLOBAL, because the Engine reads it as one. Assigning it here without declaring it
    // / makes a local that looks right, & collectConfigModel then finds nothing & returns an
    // / empty model, & the generator writes a file with zero settings & reports success.
    // / Convention three, caught by the thing it exists to prevent.
    global $EngineConfigModelProvider, $EngineConfigTemplate;
    $EngineConfigModelProvider = 'applicationConfigModel';
    // / The bootstrap names the template too. engineConfig.php is not loaded on this path,
    // / so nothing else would.
    $EngineConfigTemplate = 'Contract/config-template.php';
    // / Setup Core reaches the model through the Engine, so the Engine loads first.
    // / A bootstrap that loads half the components it calls into is a bootstrap that fails
    // / on the line where the missing half is first needed rather than on the line that
    // / forgot to load it.
    $bootstrapEngine = realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'Engine'.DIRECTORY_SEPARATOR.'engine.php');
    if ($bootstrapEngine === FALSE) quickDie('No configuration file & no Engine to write one!', 0);
    require_once($bootstrapEngine);
    require_once($bootstrapSetup);
    print(PHP_EOL.'No config.php was found. Writing the defaults from the template.'.PHP_EOL);
    list ($bootstrapWritten, $bootstrapCount) = generateConfigFile(dirname(__FILE__).DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'config.php');
    if ($bootstrapWritten) print('Wrote '.$bootstrapCount.' setting(s), all defaults. Change them with --config.'.PHP_EOL.PHP_EOL);
    else print('A configuration file could not be written. Check that Resources is writable by root.'.PHP_EOL.PHP_EOL);
    exit($bootstrapWritten ? 0 : 1); }
  else require_once ($configFile);
  // / The configured installation directory is checked against reality before anything is derived from it.
  // / config.php ships an absolute path on purpose, so an operator who copies this tree onto a LAMP stack & reads no instructions gets a running application.
  // / That only holds if a wrong path cannot be believed, because every location this core uses is built from this one value.
  // / The directory this file is actually in is the one fact that cannot be wrong, so that is the fallback.
  // / The warning names the setting, so an operator deploying properly is told once where to fix it.
  // / The configured installation directory is the hosted location, & the code is what is running.
  // / The application is installed in Application in the data location, outside the web root.
  // / The folder the web server serves holds a loader named convertCore.php, the static files a browser fetches & DATA.
  // / $HostedLoc is that folder, & $InstLoc is the tree this file is in, which is where every component is loaded from.
  // / Before the first -fp that installs it they are the same folder.
  // / The installed copy keeps the configured folder even while its loader is missing, because -fp writes the loader again.
  $ApplicationLoc = rtrim((string)$ConvertLoc, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'Application';
  $runningIsInstalled = (trim((string)$ConvertLoc) !== '' && (string)@realpath(dirname(__FILE__)) === (string)@realpath($ApplicationLoc));
  if (!is_string($InstLoc) or trim($InstLoc) === '' or (!$runningIsInstalled && !is_file(rtrim(trim($InstLoc), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'convertCore.php'))) {
    warningEntry('The configured installation directory does not contain convertCore.php, so '.dirname(__FILE__).' is being used instead. Set --Installation Directory-- in Resources/config.php to the real path.');
    $HostedLoc = dirname(__FILE__); }
  else $HostedLoc = rtrim(trim($InstLoc), DIRECTORY_SEPARATOR);
  $InstLoc = dirname(__FILE__);
  // / A loader ran first when this file was required rather than run, & root ran that as well.
  // / Its tree is read now, before anything in this one is, & the result is consumed the same way as the first, for the same reason.
  $entryScript = (string)@realpath(isset($_SERVER['SCRIPT_FILENAME']) ? (string)$_SERVER['SCRIPT_FILENAME'] : '');
  if ($entryScript !== '' && $entryScript !== (string)@realpath(__FILE__) && !verifyCodeIsTrustedByRoot(dirname($entryScript))) $CodeIsTrusted = FALSE;
  // / A web request works in the folder the application is in, as it did while that folder was the
  // / one the web server served. Every interface & language file is named relative to it, & the
  // / browser fetches the same names from the hosted location, where -fp keeps copies of them.
  // / A command line keeps the folder it was started in, because an operator names files from it.
  if (PHP_SAPI !== 'cli') @chdir($InstLoc);
  // / A configuration with no version stamp is left without one, so the check below reports
  // / the setting missing rather than reading an empty string as a version.
  if (isset($ConfigVersion)) $ConfigVersion = ltrim((string)$ConfigVersion, 'vV');
  // / Perform a version integrity check.
  // / A core file that does not match versionInfo.php indicates a partial or interrupted update.
  if ($HRConvertVersion !== $Version) quickDie('The core file reports version v'.$HRConvertVersion.' but the version information file reports version v'.$Version.'. This installation is incomplete or was updated incorrectly.', 28001);
  // / Confirm the config file carries every setting this core requires.
  // / An undefined setting reads as NULL, which silently becomes FALSE or zero at every point of use.
  list ($configIsValid, $missingConfigVars, $detectedConfigVersion) = verifyConfigVersion($RequiredConfigVersion);
  // / A configuration missing a setting still lets --config --repair run, because repair is the tool that adds a missing setting.
  // / Refusing to start would leave fixing it by hand as the only way, which is the loop this mechanism exists to remove.
  // / The same exception covers a configuration that is missing entirely.
  // / Every other command still refuses, because a setting read as NULL is a silently changed behaviour rather than an error.
  if (!$configIsValid && !$configRepairRequested) quickDie('The config.php file is missing '.count($missingConfigVars).' required setting(s). Config version detected: v'.$detectedConfigVersion.'. Config version required: v'.$RequiredConfigVersion.'. Missing Variables: '.implode(', ', $missingConfigVars), 28000);
  else if (!$configIsValid) warningEntry('The configuration is missing '.count($missingConfigVars).' required setting(s). Only --config --repair can run.');
  // / The data location is owned by root, & each kind of file lives in a folder of its own below it.
  // / Sessions & the web server account's cache are that account's, & root's log folder is root's.
  // / Each path is derived once, here, from the configured data location, so a web request, a command line run & the listener all build the same path.
  // / The data location itself is $ConvertLoc.
  // / The install secret is kept in Secrets below it, a folder that is root's & that only the web server account's group may enter.
  // / verifyGlobals narrows $ConvertLoc to a pooled session area later, so these are taken now, before it does.
  // / See Documentation/ABOUT_MANAGED_LOCATIONS.txt.
  $DataRootLoc = rtrim((string)$ConvertLoc, DIRECTORY_SEPARATOR);
  $SessionLoc = $DataRootLoc.DIRECTORY_SEPARATOR.'Sessions';
  $CacheLoc = $DataRootLoc.DIRECTORY_SEPARATOR.'Cache';
  $EngineDir = $DataRootLoc.DIRECTORY_SEPARATOR.'Engine';
  $SecretLoc = $DataRootLoc.DIRECTORY_SEPARATOR.'Secrets';
  $WorkflowLoc = $DataRootLoc.DIRECTORY_SEPARATOR.'Workflows';
  // / Every account but root runs its tools with its home in the cache folder, set now that
  // / config.php has been read. A $HomeLoc that still names the data location, as an older
  // / config.php does, is the data location root now owns, so the cache folder is used instead
  // / & a warning says so. A $HomeLoc an operator pointed somewhere of their own is kept.
  if (!(function_exists('posix_geteuid') && posix_geteuid() === 0)) {
    $homeTarget = (isset($HomeLoc) && is_string($HomeLoc) && trim($HomeLoc) !== '') ? rtrim((string)$HomeLoc, DIRECTORY_SEPARATOR) : '';
    if ($homeTarget === '' or $homeTarget === rtrim((string)$ConvertLoc, DIRECTORY_SEPARATOR)) {
      if ($homeTarget !== '') warningEntry('The configured home directory is the data location, which root owns & the web server account cannot write. The cache folder '.$CacheLoc.' is used instead.');
      $homeTarget = $CacheLoc; }
    putenv('HOME='.$homeTarget); }
  // / Derive a filesystem safe token from the application name.
  // / The name comes from config.php, so it is sanitized before it is used to build a path.
  // / A name that sanitizes to nothing falls back to the project name rather than to an
  // / empty string, which would produce a hidden file named -secret.php.
  $applicationSlug = sanitizeString($ApplicationName, TRUE);
  if ($applicationSlug === '') $applicationSlug = 'HRConvert2';
  // / Establish a secret. The path differs by authorization & nothing else does.
  // / The install wide secret lives in Secrets in the data location, outside the web root.
  if ($secretAuthorized) {
    $SecretFile = $SecretLoc.DIRECTORY_SEPARATOR.$applicationSlug.'-secret.php';
    $olderSecretFile = olderInstallSecretFile($SecretFile);
    $legacySecretFile = $DataRootLoc.DIRECTORY_SEPARATOR.'secret.php';
    // / Root reads & writes the install wide secret without ever running it, & creates the
    // / data location first when an installation has none yet.
    // / The secret is beneath the data location, so a folder above it that another account can
    // / change stops root here, -fp included, with every such folder named & the commands that
    // / give each back to root. Otherwise root would find the secret unreadable, call it unusable
    // / & stop with error 5, & say nothing of the folder. The folders above the hosted location &
    // / above each additional data location only limit what -fp corrects there, so they are named
    // / & root goes on. The topmost folder -fp corrects on the hosted location's path is
    // / HRProprietary, when the hosted location is inside it.
    if ($RunningAsRoot) {
      $limitingLocations = array((string)$HostedLoc);
      if (trim((string)$ProprietaryLoc) !== '' && strpos(rtrim((string)$HostedLoc, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR, rtrim((string)$ProprietaryLoc, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR) === 0) $limitingLocations = array((string)$ProprietaryLoc);
      if (isset($AdditionalConvertLocs) && is_array($AdditionalConvertLocs)) foreach ($AdditionalConvertLocs as $additionalLocation) if (is_array($additionalLocation) && isset($additionalLocation[0])) $limitingLocations[] = (string)$additionalLocation[0];
      list ($blockingWereJudged, $blockingFolders) = foldersRootCannotTrust(array($ConvertLoc));
      if (!$blockingWereJudged) warningEntry('A folder above the data location '.$ConvertLoc.' could not be examined.');
      list ($limitingWereJudged, $limitingFolders) = foldersRootCannotTrust($limitingLocations);
      if (!$limitingWereJudged) warningEntry('A folder above the hosted location or an additional data location could not be examined.');
      if (count($blockingFolders) > 0) reportFoldersRootCannotTrust(array_merge($limitingFolders, $blockingFolders), TRUE);
      else {
        if (count($limitingFolders) > 0) reportFoldersRootCannotTrust($limitingFolders, FALSE);
        if (prepareDataLocationAsRoot($ConvertLoc)) list ($secretIsReady, $SecretKey) = resolveInstallSecretAsRoot($SecretFile, $RequiredSecretVersion, FALSE); } }
    // / The web server account only ever reads it, & never writes one of its own.
    // / It reads first the copy root reads first, by the same rule.
    // / That is the copy in Secrets once the folder has its role & the data location is root's, unless the copy at the top is root's & was written after it.
    // / Otherwise it is the copy at the top of the data location, where older releases kept it.
    // / The other copy is tried when the first cannot be used, & the first again after it, because a copy root replaced at that moment is whole a moment later.
    // / Root writes the same key into both, & removes only a copy not known to carry it, so either carries the key in use.
    // / A copy is judged before it is read, by installSecretCopyIsTrusted, exactly as root judges it, so a copy another account could have written is never used.
    // / The messages name no path, because a halt at boot shows them to the visitor.
    else {
      list ($webSecretsState, $webSecretsMode, $webSecretsOwnership) = secretFolderStatus($SecretLoc);
      list ($dataLocationIsTrusted, $resolvedDataLocation, $dataLocationNotice) = pathIsTrustedByRoot($DataRootLoc);
      $installSecretFile = ($webSecretsState === 'ok' && $dataLocationIsTrusted && $resolvedDataLocation === rtrim((string)$DataRootLoc, DIRECTORY_SEPARATOR) && !topSecretCopyIsNewer($SecretFile)) ? $SecretFile : $olderSecretFile;
      $webCandidates = array($installSecretFile, ($installSecretFile === $SecretFile ? $olderSecretFile : $SecretFile), $installSecretFile);
      // / A copy refused once is named once. The third look reads the first copy again only when this
      // / account can open it, because one it cannot open was not replaced in the moment between.
      foreach ($webCandidates as $webCandidateIndex => $webCandidate) {
        if ($secretIsReady or !file_exists($webCandidate) or ($webCandidateIndex === 2 && !is_readable($webCandidate))) continue;
        list ($copyIsTrusted, $trustNotice) = installSecretCopyIsTrusted($webCandidate);
        if ($copyIsTrusted) list ($secretIsReady, $SecretKey) = resolveSecretFile($webCandidate, $RequiredSecretVersion);
        else if (!isset($webTried[$webCandidate])) warningEntry('A copy of the install secret is not used, because another account could have written it. Run -fp as root, which names it & why.');
        $webTried[$webCandidate] = TRUE; }
      if (!$secretIsReady && !file_exists($SecretFile) && !file_exists($olderSecretFile)) errorEntry('There is no install secret & the web server account does not create one. Run -fp as root to create it.', 31021, FALSE); }
    // / Remove the unnamed secret file left by a release before this one.
    // / It is removed only once a replacement is in place, so a failure above leaves the
    // / old file usable & the installation recoverable.
    // / The folder is the web server account's, so root removes it through the kernel.
    if ($secretIsReady && (file_exists($legacySecretFile) or is_link($legacySecretFile))) {
      list ($legacyWasRemoved, $legacyNotice) = removeFileWithoutFollowing($legacySecretFile);
      $legacyNotice = $legacyWasRemoved ? 'The legacy secret file at '.$legacySecretFile.' was removed.' : 'The legacy secret file at '.$legacySecretFile.' could not be removed. '.$legacyNotice;
      if (isset($LogFile) && is_string($LogFile) && $LogFile !== '') warningEntry($legacyNotice);
      else error_log('WARNING!!! HRConvert2: '.$legacyNotice); } }
  // / A per user secret lives in a folder of its own for each account, with that account's
  // / logs beside it. The folder is created if it does not exist, because this may be the
  // / first thing the account has ever asked this application to do. A folder that cannot
  // / be used is refused with the reason, & the installation then fails to verify.
  else if ($userSecretAuthorized) {
    $accountSlug = sanitizeString($CurrentUser, TRUE);
    list ($secretFolderIsReady, $secretFolder) = resolveUserSecretFolder($applicationSlug, $accountSlug);
    if ($secretFolderIsReady) {
      $LogDir = $secretFolder.DIRECTORY_SEPARATOR.'Logs'.DIRECTORY_SEPARATOR;
      $SecretFile = $secretFolder.DIRECTORY_SEPARATOR.$applicationSlug.'-secret-'.$accountSlug.'.php';
      list ($secretIsReady, $SecretKey) = resolveSecretFile($SecretFile, $RequiredSecretVersion); } }
  // / The AppArmor profile every sandboxed conversion depends on is validated as soon as the Engine has loaded, by verifySandboxPolicyAtStart(), which says why.
  // / Resource awareness is decided by the main logic the moment the Engine has loaded, because the Engine is verified after this function runs.
  // / A failure to verify it is worth a warning that has somewhere to be written.
  // / The socket directory is still named here, because the name comes from configuration rather than from anything the Engine provides.
  $ResourceAwarenessActive = FALSE;
  $ManagerSocketDir = $ConvertLoc.DIRECTORY_SEPARATOR.'Sockets';
  if (!$secretIsReady && $RequireResourceAwareness) errorEntry('Resource awareness is required by config.php but there is no install secret!', 31013, TRUE);
  // / Perform a check to see if all required tests passed.
  // / The version check & the config check both die on failure, so reaching this line means
  // / both passed & only the secret remains in question.
  // / A secret is ready when it was CREATED or when it was LOADED. Requiring both, as an
  // / earlier version did, could never be satisfied. A first run only ever creates & every
  // / run after that only ever loads, so the two conditions are mutually exclusive.
  if ($secretIsReady) $InstallationIsVerified = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $SecretKey is deliberately NOT cleared here because the rest of the core needs it.
  // / $SecretFile is deliberately NOT cleared here because it is a global the core reads later.
  purgeSensitiveMemory($EnableMemoryProtection, $entryScript, $runningIsInstalled, $bootstrapEngine, $configRepairRequested, $bootstrapSetup, $bootstrapWritten, $bootstrapCount, $secretAuthorized, $userSecretAuthorized, $secretIsReady, $configIsValid, $missingConfigVars, $detectedConfigVersion, $secretFolder, $applicationSlug, $legacySecretFile, $componentIsAvailable, $accountSlug, $secretFolderIsReady, $listenerArguments, $listenerArgumentCount, $listenerCommand, $rootRecord, $legacyWasRemoved, $legacyNotice, $olderSecretFile, $installSecretFile, $webCandidates, $webCandidate, $webCandidateIndex, $webTried, $copyIsTrusted, $trustNotice, $webSecretsState, $webSecretsMode, $webSecretsOwnership, $dataLocationIsTrusted, $resolvedDataLocation, $dataLocationNotice, $homeTarget, $blockingWereJudged, $limitingWereJudged, $blockingFolders, $limitingFolders, $limitingLocations, $additionalLocation);
  return array($InstallationIsVerified, $configFile, $Version, $CoreLoaded); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to detect the user's IP & user agent.
// / The IP is not used to derive any session identifier.
// / HTTP_CLIENT_IP & HTTP_X_FORWARDED_FOR are attacker supplied headers that cannot be trusted as a binding factor, & they rotate legitimately on mobile networks.
// / The token pair is the credential, & the IP is kept for logging only.
function verifySession() {
  // / Set variables.
  $IP = $HashedUserAgent = '';
  $SessionIsVerified = FALSE;
  list ($HashedUserAgent, $SessionIsVerified) = sanitize(hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? ''), TRUE);
  // / Detect an IP for logging. Header-supplied values are recorded but never trusted.
  if (!empty($_SERVER['HTTP_CLIENT_IP'])) list ($IP, $SessionIsVerified) = sanitize($_SERVER['HTTP_CLIENT_IP'], TRUE);
  elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) list ($IP, $SessionIsVerified) = sanitize($_SERVER['HTTP_X_FORWARDED_FOR'], TRUE);
  else list ($IP, $SessionIsVerified) = sanitize($_SERVER['REMOTE_ADDR'] ?? '', TRUE);
  return array($SessionIsVerified, $IP, $HashedUserAgent); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to define the $SesHash related variables for the session.
// / $SesHash  = per-server-per-day. The parent directory holding every session for today.
// / $SesHash2 = per-session. The individual user directory inside today's parent.
// / $SesHash3 = the combined relative path, "[server-daily]/[session]".
// / $SesHash4 = per-server-per-day log file identifier. Never tied to any individual session.
// / Every one of these is derived with HMAC keyed on the per-install $SecretKey.
// / Nothing here is derivable from the server name, the date, the version file, or a default
// / config.php. An attacker who knows the machine still cannot compute any of these values.
function verifySesHash($Token1) {
  // / Set variables.
  global $Date, $SecretKey, $UniqueDailyLogHash, $EnableMemoryProtection;
  $SesHashIsVerified = $inputsAreUsable = FALSE;
  $SesHash = $SesHash2 = $SesHash3 = $SesHash4 = FALSE;
  $dailyContext = '';
  // / Both the install secret & a well formed Token1 are required before anything can be derived.
  if (!empty($SecretKey) && strlen($SecretKey) === 64 && !empty($Token1) && ctype_digit((string)$Token1) && strlen((string)$Token1) === 18) $inputsAreUsable = TRUE;
  if ($inputsAreUsable) {
    // / The daily context. Server name is included only for domain separation between vhosts
    // / sharing one installation. It contributes no secrecy & is not relied on for entropy.
    $dailyContext = 'HRC2-DAILY|'.$Date.'|'.($_SERVER['SERVER_NAME'] ?? '');
    // / Per-server-per-day parent directory.
    $SesHash = substr(hash_hmac('sha256', $dailyContext, $SecretKey), -18);
    // / Per-session directory. Bound to the daily parent so a token cannot be replayed into a different day's directory tree.
    $SesHash2 = substr(hash_hmac('sha256', 'HRC2-SESSION|'.$SesHash.'|'.(string)$Token1, $SecretKey), -18);
    // / The combined relative path used everywhere else in the core.
    // / DIRECTORY_SEPARATOR rather than $DirSep, because verifySesHash() runs before
    // / verifyGlobals() sets that global.
    $SesHash3 = $SesHash.DIRECTORY_SEPARATOR.$SesHash2;
    // / The log file identifier. Distinct context string so it can never collide with $SesHash.
    // / Derived only from daily context so it is never tied to an individual user.
    if ($UniqueDailyLogHash) $SesHash4 = substr(hash_hmac('sha256', 'HRC2-LOG|'.$dailyContext, $SecretKey), -18);
    else $SesHash4 = substr(hash_hmac('sha256', 'HRC2-LOG|'.($_SERVER['SERVER_NAME'] ?? ''), $SecretKey), -18);
    // / Confirm every identifier came out the expected length before anything trusts them.
    if (strlen($SesHash) === 18 && strlen($SesHash2) === 18 && strlen($SesHash4) === 18 && !empty($SesHash3)) $SesHashIsVerified = TRUE; }
  // / Any failure at any point invalidates all four. Never hand back a partial set.
  if (!$SesHashIsVerified) $SesHash = $SesHash2 = $SesHash3 = $SesHash4 = FALSE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dailyContext, $inputsAreUsable);
  return array($SesHashIsVerified, $SesHash, $SesHash2, $SesHash3, $SesHash4); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to open the logfile this process writes.
// / Accepts nothing. Returns whether a logfile is in use & its path.
// / It decides what the logfile is, which is the role it is named for, the folder it is in & the rest of its name.
// / The kernel opens it, because how a logfile is opened, named in turn & protected is the same in every application.
// / Root's logfile is in Engine, & root writes to standard error until that folder exists, for the reason given below.
// / On a terminal root also repeats its warnings & errors there.
// / The log hash suffix is assembled before any filename that uses it is built.
function verifyLogs() {
  // / Set variables.
  global $EnginePerManagerLogFiles, $EnginePerWorkerLogFiles, $LogWorkerIdentifier, $LogRole, $RunningFromCLI, $LogDir, $LogFile, $MaxLogSize, $SesHash4, $DefaultLogDir, $DefaultLogSize, $Date, $ApplicationName, $ConvertLoc, $EngineDir, $AppendLogHashToLogFiles, $EnableMemoryProtection, $RunningAsRoot;
  $LogExists = FALSE;
  $logHashAppend = $roleArgument = $workerArgument = $namePrefix = $nameSuffix = $rootLogFolder = $logPath = $logFolder = '';
  // / The role is part of the filename & that is the main defence against interleaving.
  // / Several processes append to one logfile. A web request, four manager processes & any
  // / worker they supervise all write to the same place at the same time.
  // / An append shorter than the pipe buffer is atomic & most entries are.
  // / A stream inspection dump, a compiler log or an FFMPEG failure is not, & those are the
  // / entries worth not corrupting.
  // / LOCK_EX in emitLogEntry closes the remaining gap & costs nothing at this volume.
  // / Separating by role also makes a log readable. A manager writing every few seconds
  // / for a week buried every web request that happened alongside it.
  // / Web is the default, because a request is what this application mostly is.
  // / The role is decided here rather than in the kernel, because the kernel holds no list of commands.
  if (!isset($LogRole) or (string)$LogRole === '') {
    $LogRole = 'Web';
    if (isset($RunningFromCLI) && $RunningFromCLI) {
      $LogRole = 'Cli';
      // / This list names the same commands as the Engine's dispatcher, which is not loaded yet.
      // / A workflow's worker writes a log of its own, so what it did is read apart from every request.
      if (isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] === '--run-workflow') $LogRole = 'Workflow';
      if (isset($_SERVER['argv'][1]) && in_array($_SERVER['argv'][1], array('--start-core-manager', '--start-manager', '--run-core-manager'), TRUE)) {
        $LogRole = 'Manager';
        // / A manager may have a file of its own when the Engine configuration asks for it.
        // / The role it is running under is the second argument & is already sanitized by
        // / the dispatcher that reads it, so only the characters a filename may carry are
        // / kept here rather than trusting that.
        if (isset($EnginePerManagerLogFiles) && $EnginePerManagerLogFiles) {
          $roleArgument = isset($_SERVER['argv'][2]) ? preg_replace('/[^A-Za-z0-9-]/', '', (string)$_SERVER['argv'][2]) : '';
          if ($roleArgument !== '') $LogRole = $roleArgument;
          else $LogRole = 'core-manager'; } } }
    // / A worker may have a file of its own for the same reason.
    // / The identifier is supplied by the application before this function runs, because
    // / only the application knows what a unit of work is called.
    // / An application that enables this & supplies nothing keeps the role it had, rather
    // / than writing every worker to a file called nothing.
    if (isset($EnginePerWorkerLogFiles) && $EnginePerWorkerLogFiles && isset($LogWorkerIdentifier)) {
      $workerArgument = preg_replace('/[^A-Za-z0-9-]/', '', (string)$LogWorkerIdentifier);
      if ($workerArgument !== '') $LogRole = 'Worker-'.$workerArgument; } }
  $DefaultLogDir = $ConvertLoc.'/Logs';
  $DefaultLogSize = 1048576;
  // / Build the hash suffix before it is used in any filename.
  if ($AppendLogHashToLogFiles) $logHashAppend = '_'.$SesHash4;
  if (!is_numeric($MaxLogSize)) $MaxLogSize = $DefaultLogSize;
  // / A logfile is named for the application & the role, then its count, then the date & the hash suffix.
  $namePrefix = $ApplicationName.'_'.$LogRole;
  $nameSuffix = $Date.$logHashAppend;
  // / Root logs to a folder of its own, Engine, inside the data root.
  // / The web server account's log folder is that account's, so root will not write there, because that account can swap the whole folder for a link.
  // / Engine is root:root inside a data root owned by root, so no other account can swap it.
  if ($RunningAsRoot) {
    if (isset($EngineDir) && is_string($EngineDir)) $rootLogFolder = $EngineDir;
    list ($LogExists, $logPath) = openLogFileForRoot($rootLogFolder, $namePrefix, $nameSuffix, $MaxLogSize); }
  // / Every other account logs to the folder config.php names, & to Logs in the data location when that one cannot be made.
  // / An index.html is placed in the folder used as document root protection.
  else {
    list ($LogExists, $logPath, $logFolder) = openLogFileByName(array($LogDir, $DefaultLogDir), 'index.html', $namePrefix, $nameSuffix, $MaxLogSize);
    // / The folder in use is the one the rest of the application reads, such as for a dependency report.
    if ($logFolder !== '') $LogDir = $logFolder; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $logHashAppend, $roleArgument, $workerArgument, $namePrefix, $nameSuffix, $rootLogFolder, $logPath, $logFolder);
  return array($LogExists, $LogFile); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to move a process that runs for days on to the logfile it should be writing now.
// / Accepts nothing. Returns whether a logfile is in use & its path, as verifyLogs() does.
// / The Engine calls this from every manager loop, through $EngineLogProvider, once a minute.
// / A logfile is named for the date & moves on once it reaches --Maximum Log File Size--, & a request names its logfile as it starts.
// / Root runs nothing for days & writes through a handle it already holds, so root is left as it is.
// / What PHP remembers of a file's size is forgotten first, because a process that has run for days would otherwise measure the logfile it opened.
function refreshLogFile() {
  // / Set variables.
  global $RunningAsRoot, $LogFile, $EnableMemoryProtection;
  $LogExists = FALSE;
  $LogPath = '';
  $timeIsSet = FALSE;
  $refreshedDate = $refreshedTime = '';
  $refreshedEpoch = 0;
  // / Each branch sets the path once, so the one it held is never written over.
  if ($RunningAsRoot) {
    $LogPath = (string)$LogFile;
    $LogExists = ($LogPath !== ''); }
  else {
    clearstatcache();
    list ($timeIsSet, $refreshedDate, $refreshedTime, $refreshedEpoch) = verifyTime();
    list ($LogExists, $LogPath) = verifyLogs(); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $timeIsSet, $refreshedDate, $refreshedTime, $refreshedEpoch);
  return array($LogExists, $LogPath); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to set or validate the token pair used to identify a session.
// / Token1 is the session credential & carries the entropy. It is an 18 digit random number.
// / Token2 is an HMAC signature over Token1, keyed with the per-install $SecretKey.
// / An attacker who does not hold $SecretKey cannot produce a valid Token2 for any Token1.
// / This validation touches no files & depends on no directory state.
// / This validation runs BEFORE the session hashes exist & introduces no circular dependency.
// / This validation is arithmetic only.
function verifyTokens($Token1, $Token2) {
  // / Set variables.
  global $SecretKey, $EnableMemoryProtection, $Verbose;
  $TokensAreValid = $randomCheck = $secretIsUsable = $issueNewSession = FALSE;
  $expectedToken2 = '';
  // / Without the install secret nothing can be validated or signed.
  if (!empty($SecretKey) && strlen($SecretKey) === 64) $secretIsUsable = TRUE;
  // / An absent or malformed Token1 means there is no session to validate, so issue a new one.
  // / Note this deliberately discards any Token2 that arrived with it. A Token2 signed
  // / over a token we are about to replace is meaningless.
  if (empty($Token1) or !ctype_digit((string)$Token1) or strlen((string)$Token1) !== 18) $issueNewSession = TRUE;
  // / Token1 is well formed, so recompute what Token2 SHOULD be & compare.
  // / hash_equals() compares in constant time so the comparison itself leaks nothing.
  elseif ($secretIsUsable) {
    $expectedToken2 = hash_hmac('sha256', (string)$Token1, $SecretKey);
    if (!empty($Token2) && hash_equals($expectedToken2, (string)$Token2)) $TokensAreValid = TRUE;
    // / The pair did not verify. Do not trust either half. Issue an entirely new session.
    else $issueNewSession = TRUE; }
  // / A new session is issued silently, which makes a lost one invisible.
  // / A request that meant to continue an existing session & arrived without a usable
  // / Token1 is handed a fresh one, lands in a directory it just created, & reports that
  // / the user uploaded nothing. Saying which of the two happened is the difference between
  // / diagnosing that in one line & inferring it from a changing session hash.
  // / A first visit has no token & that is not a warning.
  // / Every new visitor arrives without one, so warning on an absent Token1 would put a
  // / WARNING in the log for ordinary traffic, which is how a log stops being read.
  // / A Token1 that ARRIVED and did not verify is different. Something sent a token this
  // / core would not accept, & that is worth recording once, at the tier that means
  // / somebody should know rather than the tier that means something is broken.
  if ($issueNewSession && $secretIsUsable && !empty($Token1)) warningEntry('A session token arrived & did not verify, so a new session was issued. The token was '.strlen((string)$Token1).' characters. A stale bookmark, a rotated install secret, or a request built by something other than this interface will all do this.');
  else if ($issueNewSession && $secretIsUsable && $Verbose && PHP_SAPI !== 'cli') logEntry('No session token arrived, so a new session was issued. This is a first visit.');
  // / Issue a fresh token pair. This is the only place tokens are ever generated.
  if ($issueNewSession && $secretIsUsable) {
    list ($Token1, $randomCheck) = generateRandomNumber();
    if ($randomCheck) {
      $Token2 = hash_hmac('sha256', (string)$Token1, $SecretKey);
      $TokensAreValid = TRUE; } }
  // / Any failure at any point invalidates both halves. Never hand back a partial pair.
  if (!$TokensAreValid) $Token1 = $Token2 = FALSE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $expectedToken2, $randomCheck, $secretIsUsable, $issueNewSession);
  return array($TokensAreValid, $Token1, $Token2); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify that all required POST & GET inputs are properly sanitized.
function verifyInputs() {
  // / Set variables.
  global $ShowGUI, $EnableMemoryProtection, $NoGui, $ShowFiles, $FileListOnly, $StreamBaseURL, $UserURLDownload, $LogoURL, $ResolvedLogoURL, $SupportedLanguages, $SupportedColors, $SupportedGuis;
  $InputsAreVerified = TRUE;
  $GUI = $Color = $Language = $Token1 = $Token2 = $Height = $Width = $Rotate = $Bitrate = $Method = $Download = $UserFilename = $UserExtension = $Archive = $UserScanType = $IdentifyReport = $IdentifyFilename = $Sort = $WorkflowName = $WorkflowDocument = $WorkflowCheck = $WorkflowExport = $var = $rawInput = '';
  $variableIsSanitized = $ConvertSelected = $PDFWorkSelected = $FilesToArchive = $FilesToScan = $FilesToDelete = $FilesToIdentify = $WorkflowFiles = $logoParts = $logoQuery = array();
  $key = 0;
  // / Determine whether or not to display a full GUI or a minimalized GUI.
  // / The default action is set in config.php. The user can opt for less than the default, but never more.
  // / The user can only disable the full GUI and fall back to a minimal one.
  // / The user can never force enable a full GUI if $ShowGUI is set to FALSE in config.php.
  if (isset($_GET['noGui'])) $ShowGUI = FALSE;
  if (!$ShowGUI) $_GET['noGui'] = TRUE;
  // / Every superglobal this application reads is read here & nowhere else.
  // / An interface that reads $_GET defines API surface, & an interface is not entitled to
  // / define API surface. These three carry no data, only presence, so the value is
  // / discarded & a boolean is handed on. Nothing downstream needs to sanitize a boolean.
  // / A GUI receives these through buildGUI & must never look at $_GET for itself.
  $NoGui = isset($_GET['noGui']);
  $ShowFiles = isset($_GET['showFiles']);
  $FileListOnly = isset($_GET['fileListOnly']);
  // / Sanitize each variable as needed & build a list of error check results.
  if (isset($_POST['filesToDelete'])) list ($FilesToDelete, $variableIsSanitized[$key++]) = sanitize($_POST['filesToDelete'], TRUE);
  if (isset($_POST['language'])) list ($Language, $variableIsSanitized[$key++]) = sanitize($_POST['language'], TRUE);
  if (isset($_GET['language'])) list ($_GET['language'], $variableIsSanitized[$key++]) = sanitize($_GET['language'], TRUE);
  if (isset($_GET['language'])) list ($Language, $variableIsSanitized[$key++]) = sanitize($_GET['language'], TRUE);
  if (isset($_POST['color'])) list ($Color, $variableIsSanitized[$key++]) = sanitize($_POST['color'], TRUE);
  if (isset($_GET['color'])) list ($_GET['color'], $variableIsSanitized[$key++]) = sanitize($_GET['color'], TRUE);
  if (isset($_GET['color'])) list ($Color, $variableIsSanitized[$key++]) = sanitize($_GET['color'], TRUE);
  if (isset($_POST['gui'])) list ($GUI, $variableIsSanitized[$key++]) = sanitize($_POST['gui'], TRUE);
  if (isset($_GET['gui'])) list ($_GET['gui'], $variableIsSanitized[$key++]) = sanitize($_GET['gui'], TRUE);
  if (isset($_GET['gui'])) list ($GUI, $variableIsSanitized[$key++]) = sanitize($_GET['gui'], TRUE);
  // / The sort order rides in the address, as the language, the colour & the interface do, & is read from nowhere else.
  if (isset($_GET['sort'])) list ($Sort, $variableIsSanitized[$key++]) = sanitize($_GET['sort'], TRUE);
  if (isset($_POST['Token1'])) list ($Token1, $variableIsSanitized[$key++]) = sanitize($_POST['Token1'], TRUE);
  if (isset($_POST['Token2'])) list ($Token2, $variableIsSanitized[$key++]) = sanitize($_POST['Token2'], TRUE);
  if (isset($_POST['height'])) list ($Height, $variableIsSanitized[$key++]) = sanitize($_POST['height'], TRUE);
  if (isset($_POST['width'])) list ($Width, $variableIsSanitized[$key++]) = sanitize($_POST['width'], TRUE);
  // / An empty base is the normal case & means the playlist must carry full URLs.
  $StreamBaseURL = $UserURLDownload = '';
  if (isset($_POST['rotate'])) list ($Rotate, $variableIsSanitized[$key++]) = sanitize($_POST['rotate'], TRUE);
  if (isset($_POST['bitrate'])) list ($Bitrate, $variableIsSanitized[$key++]) = sanitize($_POST['bitrate'], TRUE);
  if (isset($_POST['method'])) list ($Method, $variableIsSanitized[$key++]) = sanitize($_POST['method'], TRUE);
  if (isset($_POST['download'])) list ($Download, $variableIsSanitized[$key++]) = sanitize($_POST['download'], TRUE);
  if (isset($_POST['archive'])) list ($Archive, $variableIsSanitized[$key++]) = sanitize($_POST['archive'], TRUE);
  if (isset($_POST['extension'])) list ($UserExtension, $variableIsSanitized[$key++]) = sanitize($_POST['extension'], TRUE);
  if (isset($_POST['filesToArchive'])) list ($FilesToArchive, $variableIsSanitized[$key++]) = sanitize($_POST['filesToArchive'], TRUE);
  if (isset($_POST['archextension'])) list ($UserExtension, $variableIsSanitized[$key++]) = sanitize($_POST['archextension'], TRUE);
  if (isset($_POST['userfilename'])) list ($UserFilename, $variableIsSanitized[$key++]) = sanitize($_POST['userfilename'], TRUE);
  if (isset($_POST['userconvertfilename'])) list ($UserFilename, $variableIsSanitized[$key++]) = sanitize($_POST['userconvertfilename'], TRUE);
  if (isset($_POST['pdfworkSelected'])) list ($PDFWorkSelected, $variableIsSanitized[$key++]) = sanitize($_POST['pdfworkSelected'], TRUE);
  if (isset($_POST['convertSelected'])) list ($ConvertSelected, $variableIsSanitized[$key++]) = sanitize($_POST['convertSelected'], TRUE);
  // / The logo destination is computed here, where the session values already are.
  // /
  // / 'preserve-session' is not an address & is never emitted as one. It means build a link
  // / back to this page carrying what a user has already chosen, so clicking the logo
  // / returns them to a clean page rather than out of the application. This server has no
  // / navigation, so a logo that leaves takes the session with it & the files with that.
  // /
  // / The three carried values are language, colour & interface. Each has ALREADY been
  // / through sanitize() above, & each is checked against the list of what this
  // / installation supports before it is put in a URL. A value that is not in the list is
  // / left out rather than corrected, because a link is not the place to argue with input.
  // /
  // / The session tokens are deliberately NOT carried. A session belongs to the browser
  // / that holds it & putting one in a link makes it something that can be copied out of a
  // / history, a referrer header or somebody's shoulder.
  // /
  // / A configured address is used as written & is required to be http or https. Anything
  // / else falls back to preserving the session, because a logo pointing at javascript: or
  // / data: is a way to run something in a visitor's browser.
  $ResolvedLogoURL = 'convertCore.php';
  if (isset($LogoURL) && strtolower(trim((string)$LogoURL)) !== 'preserve-session') {
    $logoParts = @parse_url(trim((string)$LogoURL));
    if (is_array($logoParts) && isset($logoParts['scheme']) && in_array(strtolower($logoParts['scheme']), array('http', 'https'), TRUE)) $ResolvedLogoURL = trim((string)$LogoURL);
    else warningEntry('The configured Logo URL is not an http or https address & was refused. The logo will return to this page instead.'); }
  else {
    $logoQuery = array();
    if (isset($Language) && isset($SupportedLanguages) && is_array($SupportedLanguages) && array_key_exists($Language, $SupportedLanguages)) $logoQuery[] = 'language='.rawurlencode($Language);
    if (isset($Color) && isset($SupportedColors) && is_array($SupportedColors) && in_array($Color, $SupportedColors, TRUE)) $logoQuery[] = 'color='.rawurlencode($Color);
    if (isset($GUI) && isset($SupportedGuis) && is_array($SupportedGuis) && in_array($GUI, $SupportedGuis, TRUE)) $logoQuery[] = 'gui='.rawurlencode($GUI);
    if (sortCodeIsValid($Sort)) $logoQuery[] = 'sort='.rawurlencode($Sort);
    if (!empty($logoQuery)) $ResolvedLogoURL = 'convertCore.php?'.implode('&', $logoQuery); }
  // / The logo carries the sort order too, because the order a user chose is one of the choices it keeps.
  // / The stream base address is NOT passed through sanitize() & that is deliberate.
  // / sanitize() removes the characters a filename must never hold, which are the ones a
  // / URL is made of. It would return a filename rather than an address.
  // / normalizeStreamBaseURL() in the Engine rebuilds it from parsed components instead &
  // / applies sanitize() to the host, which is the one part that is filename shaped.
  // / It is stored raw here & normalized at the point of use, so the reason a bad one was
  // / refused can be reported to the operator who typed it.
  if (isset($_POST['streamBaseURL']) && is_string($_POST['streamBaseURL'])) $StreamBaseURL = $_POST['streamBaseURL'];
  // / The download address is read raw for the same reason the stream base is. A URL that
  // / has been through sanitize() is a filename & is no longer an address.
  // / fetchUserSuppliedURL rebuilds it & every check a playlist URL receives is applied to
  // / it before anything is fetched.
  if (isset($_POST['userURLDownload']) && is_string($_POST['userURLDownload'])) $UserURLDownload = $_POST['userURLDownload'];
  // / A workflow a page sends is read raw for the same reason, & that is deliberate.
  // / It is JSON, & sanitize() removes the quotes, braces & slashes JSON is written with, so a sanitized workflow is no longer one.
  // / The Workflow Core reads it only as data, & checks every key & value it holds before anything acts on it, more strictly than sanitize() would.
  // / A name it gives what an operation makes is sanitized where the operation uses it, as a name a user types for a button is.
  if (isset($_POST['workflowDocument']) && is_string($_POST['workflowDocument'])) $WorkflowDocument = $_POST['workflowDocument'];
  if (isset($_POST['workflowCheck']) && is_string($_POST['workflowCheck'])) $WorkflowCheck = $_POST['workflowCheck'];
  // / A raw input is read only when it is text. Anything else is refused & named, because casting a list to text is a PHP warning, & no page sends one.
  foreach (array('streamBaseURL', 'userURLDownload', 'workflowDocument', 'workflowCheck') as $rawInput) if (isset($_POST[$rawInput]) && !is_string($_POST[$rawInput])) warningEntry('A request carried '.$rawInput.' as something other than text, & it was refused.');
  if (isset($_POST['pdfextension'])) list ($UserExtension, $variableIsSanitized[$key++]) = sanitize($_POST['pdfextension'], TRUE);
  if (isset($_POST['userpdfconvertfilename'])) list ($UserFilename, $variableIsSanitized[$key++]) = sanitize($_POST['userpdfconvertfilename'], TRUE);
  if (isset($_POST['scantype'])) list ($UserScanType, $variableIsSanitized[$key++]) = sanitize($_POST['scantype'], TRUE);
  if (isset($_POST['filesToScan'])) list ($FilesToScan, $variableIsSanitized[$key++]) = sanitize($_POST['filesToScan'], TRUE);
  // / Identify reads the files to identify, whether to write a report, & the name to give it.
  if (isset($_POST['filesToIdentify'])) list ($FilesToIdentify, $variableIsSanitized[$key++]) = sanitize($_POST['filesToIdentify'], TRUE);
  if (isset($_POST['identifyreport'])) list ($IdentifyReport, $variableIsSanitized[$key++]) = sanitize($_POST['identifyreport'], TRUE);
  if (isset($_POST['identifyfilename'])) list ($IdentifyFilename, $variableIsSanitized[$key++]) = sanitize($_POST['identifyfilename'], TRUE);
  // / A workflow run reads the workflow to run & the files, in the order a user selected them.
  if (isset($_POST['workflowRun'])) list ($WorkflowName, $variableIsSanitized[$key++]) = sanitize($_POST['workflowRun'], TRUE);
  if (isset($_POST['workflowFiles'])) list ($WorkflowFiles, $variableIsSanitized[$key++]) = sanitize($_POST['workflowFiles'], TRUE);
  // / An export names a workflow in the library by its identifier.
  if (isset($_POST['workflowExport'])) list ($WorkflowExport, $variableIsSanitized[$key++]) = sanitize($_POST['workflowExport'], TRUE);
  // / Check the list of error check results and see if any errors occurred.
  foreach ($variableIsSanitized as $var) if (!$var) ($InputsAreVerified = FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $logoParts, $logoQuery, $variableIsSanitized, $key, $var, $rawInput);
  return array($InputsAreVerified, $ShowGUI, $GUI, $Color, $Language, $Token1, $Token2, $Height, $Width, $Rotate, $Bitrate, $Method, $Download, $UserFilename, $UserExtension, $FilesToArchive, $PDFWorkSelected, $ConvertSelected, $FilesToScan, $FilesToDelete, $UserScanType, $UserURLDownload, $FilesToIdentify, $IdentifyReport, $IdentifyFilename, $Sort, $WorkflowName, $WorkflowFiles, $WorkflowDocument, $WorkflowCheck, $WorkflowExport); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to set the styles to use for the session.
function verifyColors($ButtonStyle) {
  // / Set variables.
  global $Color, $SupportedColors, $AllowUserSelectableColor, $ColorToUse, $GreenButtonCode, $BlueButtonCode, $RedButtonCode, $OrangeButtonCode, $PurpleButtonCode, $DarkButtonCode, $DefaultButtonCode, $EnableMemoryProtection;
  $ColorsAreSet = FALSE;
  $ColorToUse = 'blue';
  $ButtonStyle = strtolower($ButtonStyle);
  $ButtonCode = $DefaultButtonCode;
  $validColors = array('green', 'blue', 'red', 'grey', 'orange', 'purple', 'dark');
  // / Make sure $SupportedColors is valid.
  if (!isset($SupportedColors) or !is_array($SupportedColors)) $SupportedColors = $validColors;
  // / Make sure the Default Color is valid.
  if (isset($ButtonStyle)) if (in_array($ButtonStyle, $SupportedColors)) $ColorToUse = $ButtonStyle;
  // / If allowed and if specified, detect the user's specified color and set that as the color to use.
  if (isset($AllowUserSelectableColor)) {
    if ($AllowUserSelectableColor) if (isset($Color)) if (in_array($Color, $SupportedColors)) {
      $ColorToUse = $Color; } }
  // / Set the $Color variable to whatever the current color is so the next page will use the same one.
  $_GET['color'] = $ColorToUse;
  // / Validate the desired color and set it as the color to use if possible.
  if (in_array($ColorToUse, $validColors)) {
    $ColorsAreSet = TRUE;
    if ($ColorToUse === 'green') $ButtonCode = $GreenButtonCode;
    if ($ColorToUse === 'blue') $ButtonCode = $BlueButtonCode;
    if ($ColorToUse === 'red') $ButtonCode = $RedButtonCode;
    if ($ColorToUse === 'orange') $ButtonCode = $OrangeButtonCode;
    if ($ColorToUse === 'purple') $ButtonCode = $PurpleButtonCode;
    if ($ColorToUse === 'dark') $ButtonCode = $DarkButtonCode;
    if ($ColorToUse === 'grey') $ButtonCode = $DefaultButtonCode; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $validColors);
  return array($ColorsAreSet, $ButtonCode); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to set the GUI to use for the session.
function verifyGui() {
  // / Set variables.
  global $GUI, $DefaultGui, $SupportedGuis, $AllowUserSelectableGui, $GuiFiles, $GuiDir, $GuiResourcesDir, $GuiImageDir, $GuiCSSDir, $GuiJSDir, $GuiHeaderFile, $GuiFooterFile, $GuiUI1File, $GuiUI2File, $GreenButtonCode, $BlueButtonCode, $RedButtonCode, $OrangeButtonCode, $PurpleButtonCode, $DarkButtonCode, $DefaultButtonCode, $Font, $GuiVersion, $RequiredGuiVersion, $EnableMemoryProtection, $CoreLoaded, $GuiWidth, $GuiMaxWidth, $GUIAlignment;
  // / The interface width is decided ONCE, here, before the stylesheet that uses it loads.
  // / $GuiWidth is FLUID rather than fixed. min() takes whichever is smaller, so a phone is
  // / bounded by its own viewport & a wide monitor by the configured ceiling.
  // / A fixed pixel width does one of two wrong things. Too small & a large display wastes
  // / most of itself. Too large & a phone scrolls sideways, which is the most irritating
  // / thing an interface can do on a device held in one hand.
  // / 96vw rather than 100vw leaves room for a scrollbar. A page sized to the whole viewport
  // / gains a horizontal scrollbar the moment it gains a vertical one.
  // / A value outside the sane range is a typo rather than a preference & the default is
  // / used instead of trusting it.
  // / This is what makes the separate Wide interface unnecessary. It differed from Original
  // / by one number hardcoded in four files, & that number is a setting now.
  $GuiWidth = 'min('.((isset($GuiMaxWidth) && (int)$GuiMaxWidth >= 600 && (int)$GuiMaxWidth <= 3840) ? (int)$GuiMaxWidth : 1400).'px, 96vw)';
  $reqFile = $GuiIsSet = FALSE;
  $GuiToUse = $defaultGui = 'Default';
  $styleCoreFile = $guiVersionFile = '';
  $GuiFiles = $guiFiles = array();
  // / Only installed interfaces are listed, because a fallback naming one that is not installed is the failure this list exists to prevent.
  $defaultGuis = array('Default', 'Original');
  // / Make sure $SupportedGuis is valid.
  if (!isset($SupportedGuis) or !is_array($SupportedGuis)) $SupportedGuis = $defaultGuis;
  // / Make sure the Default GUI is valid.
  if (isset($DefaultGui)) if (in_array($DefaultGui, $SupportedGuis)) $GuiToUse = $DefaultGui;
  // / If allowed and if specified, detect the user's specified GUI and set that as the GUI to use.
  if (isset($AllowUserSelectableGui)) {
    if ($AllowUserSelectableGui) if (isset($GUI)) if (in_array($GUI, $SupportedGuis)) {
      $GuiToUse = $GUI; }
    if (!$AllowUserSelectableGui) {
      $GuiToUse = $defaultGui;
      if (in_array($DefaultGui, $SupportedGuis)) $GuiToUse = $DefaultGui; } }
  // / Build the list of candidate GUIs to try, in order of preference.
  // / The default is appended so a broken or version incompatible GUI has somewhere to fall
  // / back to. A user cannot choose their way out of a broken GUI, so the fallback is silent
  // / to them & noisy in the log.
  $candidateGuis = array($GuiToUse);
  if ($GuiToUse !== $defaultGui) array_push($candidateGuis, $defaultGui);
  foreach ($candidateGuis as $candidateGui) {
    // / Set the variables to a URL safe relative path to required UI files.
    $GuiToUse = $candidateGui;
    $GuiDir = 'UI/'.$GuiToUse.'/';
    $styleCoreFile = $GuiDir.'styleCore.php';
    $GuiHeaderFile = $GuiDir.'header.php';
    $GuiFooterFile = $GuiDir.'footer.php';
    $GuiUI1File = $GuiDir.'convertGui1.php';
    $GuiUI2File = $GuiDir.'convertGui2.php';
    $guiVersionFile = $GuiDir.'uiVersionInfo.php';
    $GuiResourcesDir = $GuiDir.'Resources/';
    $GuiImageDir = $GuiResourcesDir.'Image/';
    $GuiCSSDir = $GuiResourcesDir.'CSS/';
    $GuiJSDir = $GuiResourcesDir.'JS/';
    $guiFiles = array($GuiHeaderFile, $GuiFooterFile, $GuiUI1File, $GuiUI2File, $styleCoreFile, $guiVersionFile);
    // / The resources folder is part of a GUI, not an optional extra.
    // / It began as an optional convenience & the documentation still called it optional,
    // / but the core had quietly come to depend on it. header.php builds five asset paths
    // / out of these directories on every render & never asks whether they are there, so a
    // / GUI without them did not fail verification. It rendered, with no stylesheet, no
    // / script library & a broken favicon, & the first sign of trouble was an interface
    // / that did nothing when clicked.
    // / A GUI that cannot be styled or scripted is not a working GUI. Checking here means
    // / such a GUI is refused while there is still a Default to fall back to, & the log
    // / says which folder was missing instead of leaving an administrator to infer it from
    // / a blank looking page.
    $guiDirs = array($GuiResourcesDir, $GuiImageDir, $GuiCSSDir, $GuiJSDir);
    $GuiFiles = array();
    $guiDirsExist = TRUE;
    // / Verify that the required GUI folder & every required file exists.
    if (is_dir($GuiDir)) {
      foreach ($guiDirs as $reqDir) if (!is_dir($reqDir)) {
        $guiDirsExist = FALSE;
        warningEntry('GUI '.$GuiToUse.' is missing its '.$reqDir.' folder.'); }
      foreach ($guiFiles as $reqFile) if (file_exists($reqFile)) array_push($GuiFiles, $reqFile);
      // / uiVersionInfo.php only assigns variables, so a failed GUI can still be replaced here.
      // / Nothing has printed yet, unlike the language pack case in buildGUI().
      if (count($guiFiles) === count($GuiFiles) && $guiDirsExist) {
        require($guiVersionFile);
        if ($GuiVersion === $RequiredGuiVersion) $GuiIsSet = TRUE;
        else warningEntry('GUI '.$GuiToUse.' reports version '.$GuiVersion.' but this core requires '.$RequiredGuiVersion.'.'); }
      else if (count($guiFiles) !== count($GuiFiles)) warningEntry('GUI '.$GuiToUse.' is missing one or more required files.'); }
    else warningEntry('GUI '.$GuiToUse.' does not exist at '.$GuiDir.'.');
    // / A usable GUI has been found, so stop looking.
    if ($GuiIsSet) break; }
  // / Set the $GUI variable to whatever the current GUI is so the next page will use the same one.
  $_GET['gui'] = $GuiToUse;
  // / Load the style data once a compatible GUI has been settled on.
  if ($GuiIsSet) {
    // / Every button code is initialized before the style file is read.
    // / The file assigns these lowercase names into this scope, which nothing here shows, so initializing them says where they come from.
    // / It also gives each one a fallback configuration cannot overwrite.
    $greenButtonCode = $blueButtonCode = $redButtonCode = $orangeButtonCode = '';
    $purpleButtonCode = $darkButtonCode = $defaultButtonCode = '';
    require_once($styleCoreFile);
    $GreenButtonCode = $greenButtonCode;
    $BlueButtonCode = $blueButtonCode;
    $RedButtonCode = $redButtonCode;
    $OrangeButtonCode = $orangeButtonCode;
    $PurpleButtonCode = $purpleButtonCode;
    $DarkButtonCode = $darkButtonCode;
    $DefaultButtonCode = $defaultButtonCode;
    // / A style file that supplied nothing usable is worth saying so, because every button
    // / on the page will render without styling & the page will otherwise look merely odd.
    if ($DefaultButtonCode === '') warningEntry('The style file for GUI '.$GuiToUse.' supplied no default button code. Buttons will render unstyled.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $defaultGuis, $defaultGui, $candidateGuis, $candidateGui, $reqFile, $reqDir, $guiFiles, $guiDirs, $guiDirsExist, $styleCoreFile, $guiVersionFile, $greenButtonCode, $blueButtonCode, $redButtonCode, $orangeButtonCode, $purpleButtonCode, $darkButtonCode, $defaultButtonCode);
  return array($GuiIsSet, $GuiToUse, $GuiDir, $GuiFiles); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to set the language to use for the session.
function verifyLanguage() {
  // / Set variables.
  global $Language, $DefaultLanguage, $SupportedLanguages, $AllowUserSelectableLanguage, $LanguageFiles, $GuiDir, $LanguageDir, $LanguageStringsFile, $LanguageFlagFile, $LanguageBaselineFile, $EnableMemoryProtection;
  $reqFile = $LanguageIsSet = FALSE;
  $LanguageToUse = $defaultLanguage = 'en';
  $LanguageFiles = $languageFiles = array();
  $defaultLanguages = array(
  'en' => 'English',   'fr' => 'Français',    'es' => 'Español',
  'zh' => '中文',      'hi' => 'हिन्दी',        'ar' => 'العربية',
  'ru' => 'Русский',   'uk' => 'Українська',  'bn' => 'বাংলা',
  'de' => 'Deutsch',   'ko' => '한국어',        'it' => 'Italiano',
  'pt' => 'Português', 'vi' => 'Tiếng Việt',  'tr' => 'Türkçe',
  'ja' => '日本語',     'id' => 'Bahasa Indonesia',
  'pl' => 'Polski',    'nl' => 'Nederlands',  'sw' => 'Kiswahili',
  'my' => 'မြန်မာ',      'ur' => 'اردو',         'fa' => 'فارسی',
  'he' => 'עברית',     'aii' => 'ܣܘܪܝܝܐ',     'arc' => 'ܐܪܡܝܐ');
  // / Make sure $SupportedLanguages is valid.
  if (!isset($SupportedLanguages) or !is_array($SupportedLanguages)) $SupportedLanguages = $defaultLanguages;
  // / Make sure the Default Language is valid.
  // / The language code is a KEY in this array & the endonym is the value, so membership
  // / is tested with array_key_exists() rather than with in_array().
  if (isset($DefaultLanguage)) if (array_key_exists($DefaultLanguage, $SupportedLanguages)) $LanguageToUse = $DefaultLanguage;
  // / If allowed and if specified, detect the user's specified language and set that as the language to use.
  if (isset($AllowUserSelectableLanguage)) {
    if ($AllowUserSelectableLanguage) if (isset($Language)) if (array_key_exists($Language, $SupportedLanguages)) {
      $LanguageToUse = $Language; }
    if (!$AllowUserSelectableLanguage) {
      $LanguageToUse = $defaultLanguage;
      if (array_key_exists($DefaultLanguage, $SupportedLanguages)) $LanguageToUse = $DefaultLanguage; } }
  // / A language listed in config.php is not a promise that every GUI carries it.
  // / config.php lists the languages the installation offers, & a language pack lives inside a GUI, whose author ships the packs they care about.
  // / So this falls back the way verifyGui() does, through the selection, then the configured default, then English, & the first one present wins.
  // / A user cannot choose their way out of a missing pack, so the fallback is silent to them & noisy here.
  $candidateLanguages = array($LanguageToUse);
  if (isset($DefaultLanguage) && !in_array($DefaultLanguage, $candidateLanguages)) array_push($candidateLanguages, $DefaultLanguage);
  if (!in_array($defaultLanguage, $candidateLanguages)) array_push($candidateLanguages, $defaultLanguage);
  foreach ($candidateLanguages as $candidateLanguage) {
    $LanguageFiles = array();
    $LanguageDir = $GuiDir.'Languages/'.$candidateLanguage.'/';
    $LanguageStringsFile = $LanguageDir.'languageStrings.php';
    $LanguageFlagFile = $LanguageDir.'flag.png';
    $languageFiles = array($LanguageStringsFile, $LanguageFlagFile);
    // / Verify that the language folder & every required file within it exists.
    if (is_dir($LanguageDir)) {
      foreach ($languageFiles as $reqFile) if (file_exists($reqFile)) array_push($LanguageFiles, $reqFile);
      if (count($languageFiles) === count($LanguageFiles)) $LanguageIsSet = TRUE;
      else warningEntry('Language pack '.$candidateLanguage.' at '.$LanguageDir.' is missing one or more required files.'); }
    else warningEntry('The GUI at '.$GuiDir.' does not carry a '.$candidateLanguage.' language pack.');
    // / A usable pack has been found, so stop looking.
    if ($LanguageIsSet) {
      $LanguageToUse = $candidateLanguage;
      break; } }
  // / The baseline is what makes a partial pack usable.
  // / buildGUI loads this FIRST & then loads the selected pack over the top, so a pack that
  // / is missing a string inherits it instead of leaving an undefined variable for an
  // / interface to print. It is only worth loading when it is a different file to the one
  // / that was selected.
  $LanguageBaselineFile = '';
  foreach ($candidateLanguages as $candidateLanguage) {
    $baselineFile = $GuiDir.'Languages/'.$candidateLanguage.'/languageStrings.php';
    if ($candidateLanguage !== $LanguageToUse && file_exists($baselineFile)) {
      $LanguageBaselineFile = $baselineFile;
      break; } }
  // / Set the $Language variable to whatever the current language is so the next page will use the same one.
  $_GET['language'] = $LanguageToUse;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $defaultLanguages, $reqFile, $languageFiles, $defaultLanguage, $candidateLanguages, $candidateLanguage, $baselineFile);
  return array($LanguageIsSet, $LanguageToUse, $LanguageDir, $LanguageFiles); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to set the global variables for the session.
// / The stream timeouts are left in the units config.php documents them in.
// / $StreamWatchTimeout is stated in minutes & $StreamConnectionTimeout is stated in seconds.
// / Each point of use converts once, where the required unit is actually known.
// / Converting here as well produced a fifteen hour watch timeout & a ten million second connect timeout.
function verifyGlobals() {
  // / Set global variables to be used through the entire application.
  global $URL, $URLEcho, $Date, $Time, $SesHash, $SesHash2, $SesHash3, $SesHash4, $CoreLoaded, $ConvertDir, $InstLoc, $ConvertTemp, $ConvertTempDir, $ConvertGuiCounter1, $DefaultApps, $RequiredDirs, $RequiredIndexes, $DangerousFiles, $Allowed, $ArchiveArray, $DearchiveArray, $DocumentArray, $SpreadsheetArray, $PresentationInputArray, $PresentationOutputArray, $XPSInputArray, $XPSOutputArray, $ImageArray, $MediaInputArray, $MediaOutputArray, $VideoInputArray, $VideoOutputArray, $StreamArray, $DrawingArray, $UserSVGInputArray, $SVGInputArray, $UserSVGOutputArray, $SVGOutputArray, $ModelArray, $SubtitleInputArray, $SubtitleOutputArray, $PDFWorkArr, $ConvertLoc, $DirSep, $SupportedConversionTypes, $Lol, $Lolol, $Append, $ConsolidatedLogFileName, $ConsolidatedLogFile, $Alert, $Alert1, $Alert2, $Alert3, $FCPlural, $FCPlural1, $FCPlural2, $FCPlural3, $UserClamLogFile, $UserClamLogFileName, $UserScanCoreLogFile, $UserScanCoreFileName, $SpinnerStyle, $SpinnerColor, $FullURL, $ServerRootDir, $StopCounter, $SleepTimer, $CurrentUser, $File, $HeaderDisplayed, $UIDisplayed, $FooterDisplayed, $LanguageStringsLoaded, $GUIDisplayed, $GUIDirection, $SupportedFormatCount, $GUIAlignment, $GreenButtonCode, $BlueButtonCode, $RedButtonCode, $PurpleButtonCode, $OrangeButtonCode, $DarkButtonCode, $DefaultButtonCode, $UserArchiveArray, $UserDearchiveArray, $UserDocumentArray, $UserSpreadsheetArray, $UserXPSInputArray, $UserXPSOutputArray, $UserPresentationInputArray, $UserPresentationOutputArray, $UserImageArray, $UserMediaInputArray, $UserMediaOutputArray, $UserVideoInputArray, $UserVideoOutputArray, $UserStreamArray, $UserDrawingArray, $UserModelArray, $UserSubtitleInputArray, $UserSubtitleOutputArray, $UserPDFWorkArr, $RetryCount, $HomeLoc, $ProprietaryLoc, $RequiredCleanupFolders, $StreamTemp, $StreamWatchTimeout, $StreamConnectionTimeout, $AllowStreamOverHTTP, $StreamInspectionLayers, $StreamInspectionFilesPerLayer, $DefaultStreamInspectionForfeitAction, $MaxStreamInspectionFileSize, $WaitForStream, $StreamPID, $StreamOutputPath, $LogDir, $StreamOutputArray, $ScadTemp, $AllowSCADIncludeResolution, $SCADConversionTimeout, $UserSCADArray, $SCADArray, $SCADOutputArray, $ProtectedRootDirs, $ResourcesDir, $BootloadersDir, $AllowBootableIsoImage, $UserBootableIsoArray, $BootableIsoArray, $UserEbookInputArray, $UserEbookOutputArray, $EbookInputArray, $EbookOutputArray, $EnableMemoryProtection, $ManagerSocketDir, $ManagerSocketTimeout, $ManagerMessageBatchSize, $ManagerMessageSkew, $StartupKeyWindow, $ResourceAwarenessActive, $CoreManagerVersion, $EnableResourceAwareness, $RequireResourceAwareness, $CoreManagerSubprocessPollInterval, $ResourcePollInterval, $WorkerReapInterval, $WorkerStaleGracePeriod, $TotalResourceBudget, $ReserveResourcePercentage, $MaxConcurrentWorkers, $MaxExpectedRuntime, $MaxRuntimeExtensions, $DefaultConversionCost, $DefaultExpectedRuntime, $PrimaryConvertLoc, $AdditionalConvertLocs, $StorageCleanupInterval, $EffectiveConversionLimits, $EnablePerConversionLimits, $MaximumPerConversionResources, $DefaultPerConversionResources, $MinimumPerConversionResources, $AllowUnprivilegedNamespaces, $PipelineCoreActive, $PipelinesAreEnumerated, $Pipelines, $PipelineCount, $LogSequence, $LogBuffer, $LogBufferOverflowed, $LogRole, $EngineSandboxProfiles, $ModelInputArray, $ModelOutputArray, $ImageInputArray, $ImageOutputArray, $SCADInputArray, $DrawingInputArray, $DrawingOutputArray,
    $UserModelInputArray, $UserModelOutputArray, $UserDrawingInputArray, $UserDrawingOutputArray, $UserImageInputArray, $UserImageOutputArray, $UserSCADInputArray, $UserSCADOutputArray, $EnvironmentManagerMayRepair, $EnvironmentManagerMayRewriteConfigs, $AllowUserURLDownload, $GuiMaxWidth, $DefaultVirusScanner, $HostArchitecture, $HostMachineString, $URLDownloadMaximumBytes, $URLDownloadsPerSession, $ScanURLDownloads, $LogoURL, $ResolvedLogoURL, $PermittedPrivateRanges, $DataRootLoc, $SessionLoc, $CacheLoc, $EngineDir, $SecretLoc, $HostedLoc, $ApplicationLoc, $WorkflowLoc;
  // / Application related variables.
  $GlobalsAreVerified = $sanitizeGlobalCheck = $sanitizeGlobalCheckA = $sanitizeGlobalCheckB = $sanitizeGlobalCheckC = $sanitizeGlobalCheckD = $sanitizeGlobalCheckE = FALSE;
  $SleepTimer = 0;
  $StopCounter = $RetryCount;
  // / Convenience variables.
  $DirSep = DIRECTORY_SEPARATOR;
  $Lol = PHP_EOL;
  $Lolol = PHP_EOL.PHP_EOL;
  $Append = FILE_APPEND;
  // / UI Related variables.
  $ConvertGuiCounter1 = 0;
  $File = $FCPlural = $FCPlural1 = $FCPlural2 = $FCPlural3 = $GreenButtonCode = $BlueButtonCode = $RedButtonCode = $PurpleButtonCode = $OrangeButtonCode = $DarkButtonCode = $DefaultButtonCode = '';
  $HeaderDisplayed = $UIDisplayed = $FooterDisplayed = $LanguageStringsLoaded = $GUIDisplayed = FALSE;
  $GUIDirection = 'ltr';
  $GUIAlignment = 'left';
  $Alert = 'Cannot convert this file! Try changing the name.';
  $Alert1 = 'Cannot perform a virus scan on this file!';
  $Alert2 = 'File Link Copied to Clipboard!';
  $Alert3 = 'Operation Failed!';
  // / Security related variables.
  $DefaultApps = array('.', '..');
  $DangerousFiles = array(NULL, '.js', '.php', '.html', '.css', '.phar', '..', 'index.php', 'index.html', '--');
  // / Directories at the root of a data location that are never sessions & must never be swept.
  // / The LibreOffice profile lives here because HOME resolves to the data location.
  // / Rebuilding that profile is expensive, so it is deliberately preserved between requests.
  $ProtectedRootDirs = array('.cache', '.config', 'Logs', 'ScadTemp', 'StreamTemp', 'lost+found', 'Last-Installed-Version', 'Sockets', $ManagerSocketDir);
  // / Detached worker variables, still named for streaming.
  // / A pipeline that launches a process outliving the request reports its PID through the sixth value of the pipeline contract.
  // / convert() records it in these three, & the main logic reaps it after the connection to the user has closed.
  // / The two stream timeouts are left in their documented units here, & are not converted in this function.
  $WaitForStream = FALSE;
  $StreamPID = 0;
  $StreamOutputPath = '';
  // / Logging variables that exist before a logfile does.
  // / $LogSequence numbers every entry from zero so a gap proves one was lost.
  // / $LogBuffer holds entries raised before verifyLogs & is emptied by it.
  // / $LogRole names the process kind & becomes part of the logfile name.
  if (!isset($LogSequence)) $LogSequence = 0;
  if (!isset($LogBuffer) or !is_array($LogBuffer)) $LogBuffer = array();
  if (!isset($LogBufferOverflowed)) $LogBufferOverflowed = FALSE;
  if (!isset($LogRole)) $LogRole = '';
  // / The sandbox profiles this application declares, handed to the Engine at boot.
  if (!isset($EngineSandboxProfiles) or !is_array($EngineSandboxProfiles)) $EngineSandboxProfiles = array();
  // / Pipeline component variables.
  // / Stated here rather than in the main logic, so that a CLI path which never reaches the
  // / Pipeline Core still finds them defined instead of raising a notice on first read.
  // / The main logic overwrites all four the moment the component has been verified.
  $PipelineCoreActive = $PipelinesAreEnumerated = FALSE;
  $Pipelines = array();
  $PipelineCount = 0;
  // / URL related variables.
  // / The hosted location's path below the document root is the URL path, removed as a prefix of whole folders.
  // / It is the hosted location rather than the installation, because the installation is outside the web root & only the hosted location is served.
  // / A hosted location outside the document root is taken to be served at the root of --Server URL--, which is right when a virtual host serves it directly.
  // / The exposure check says so plainly when it is not.
  // / The host & the path are joined with exactly one separator, because sanitizeString deletes every //.
  $serverRootPrefix = rtrim((string)$ServerRootDir, $DirSep).$DirSep;
  $subDir = (strpos(rtrim((string)$HostedLoc, $DirSep).$DirSep, $serverRootPrefix) === 0) ? (string)substr(rtrim((string)$HostedLoc, $DirSep), strlen($serverRootPrefix)) : '';
  $subDir = trim(sanitizeString($subDir, FALSE), '/');
  $partURL = rtrim(sanitizeString($URL, FALSE), '/').($subDir === '' ? '' : '/'.$subDir);
  $FullURL = 'http'.$URLEcho.'://'.$partURL;
  // / Directory related variables.
  $webRoot = $DirSep.'var'.$DirSep.'www';
  $ResourcesDir = $InstLoc.$DirSep.'Resources';
  $BootloadersDir = $ResourcesDir.$DirSep.'Bootloaders';
  // / Core Manager related variables.
  // / The data location config.php declared is captured before anything narrows it, & the folders below it are derived as verifyInstallation derives them.
  // / So the web path has them whichever function set them last.
  // / The data location is owned by root, & sessions live in Sessions below it.
  // / The pool is the session areas, not the roots.
  // / $PrimaryConvertLoc is the primary session area, & $AdditionalConvertLocs is rewritten to each additional root's session area.
  // / So a worker & the Resource Manager place & sweep sessions in Sessions, & root keeps the rest of each root to itself.
  // / $ConvertLoc becomes the session area this session uses.
  // / The socket folder & everything else root owns hang off the root, not off Sessions.
  $EffectiveConversionLimits = array();
  $additionalIndex = 0;
  $additionalEntry = array();
  $DataRootLoc = rtrim((string)$ConvertLoc, $DirSep);
  $ApplicationLoc = $DataRootLoc.$DirSep.'Application';
  $SessionLoc = $DataRootLoc.$DirSep.'Sessions';
  $CacheLoc = $DataRootLoc.$DirSep.'Cache';
  $EngineDir = $DataRootLoc.$DirSep.'Engine';
  $SecretLoc = $DataRootLoc.$DirSep.'Secrets';
  $WorkflowLoc = $DataRootLoc.$DirSep.'Workflows';
  $PrimaryConvertLoc = $SessionLoc;
  // / Each additional data location is a root too, so its session area is the pool member.
  if (is_array($AdditionalConvertLocs)) {
    foreach ($AdditionalConvertLocs as $additionalIndex => $additionalEntry) {
      if (is_array($additionalEntry) && isset($additionalEntry[0]) && trim((string)$additionalEntry[0]) !== '')
        $AdditionalConvertLocs[$additionalIndex][0] = rtrim(trim((string)$additionalEntry[0]), $DirSep).$DirSep.'Sessions'; } }
  // / The socket directory is pinned to the configured data location & never moves with a
  // / session, or a worker & a manager would look for each other in different places.
  // / It hangs off the root, not off Sessions, & is never inside the web root.
  $ManagerSocketDir = $DataRootLoc.$DirSep.'Sockets';
  $ManagerSocketTimeout = 2;
  $ManagerMessageBatchSize = 32;
  $ManagerMessageSkew = 30;
  $StartupKeyWindow = 10;
  // / Ask the listener which location this session uses. Sticky for the life of the
  // / session & decided once, when the session is first seen. With no listener this
  // / returns the configured location & the core behaves exactly as it always has.
  // / The Engine may not be here at all & that must not be fatal. An application that cannot
  // / bootstrap its Engine still has a configured data location, which is the answer the
  // / Engine itself returns when no listener is running. Using it directly is the same
  // / result by a shorter route, & it keeps a repairable installation reachable.
  if (function_exists('requestConvertLoc')) $ConvertLoc = requestConvertLoc($SesHash, $SesHash2);
  else {
    warningEntry('The Engine is unavailable, so the configured data location is used without asking a listener. Pooled locations & per session placement are unavailable until it is repaired.');
    $ConvertLoc = $PrimaryConvertLoc; }
  list ($convertDir0, $sanitizeGlobalCheckA) = sanitize($ConvertLoc.$DirSep.$SesHash, FALSE);
  list ($ConvertDir, $sanitizeGlobalCheckB) = sanitize($convertDir0.$DirSep.$SesHash2.$DirSep, FALSE);
  list ($ConvertTemp, $sanitizeGlobalCheckC) = sanitize($HostedLoc.$DirSep.'DATA', FALSE);
  list ($convertTempDir0, $sanitizeGlobalCheckD) = sanitize($ConvertTemp.$DirSep.$SesHash, FALSE);
  list ($ConvertTempDir, $sanitizeGlobalCheckE) = sanitize($convertTempDir0.$DirSep.$SesHash2.$DirSep, FALSE);
  // / Check that all required directory variables were sanitized successfully.
  if (!$sanitizeGlobalCheckA or !$sanitizeGlobalCheckB or !$sanitizeGlobalCheckC or !$sanitizeGlobalCheckD or !$sanitizeGlobalCheckE) $sanitizeGlobalCheck = FALSE;
  else $sanitizeGlobalCheck = TRUE;
  $StreamTemp = $ConvertDir.'StreamTemp';
  $ScadTemp = $ConvertDir.'ScadTemp';
  // / The cache folder is the web server account's home & holds the capability cache, so it is
  // / confirmed here. It & the log folder hang off the root, which root provisions, so a web
  // / request only confirms they exist rather than creating them. The session & temporary dirs
  // / below them are the ones a request creates, inside folders this account owns.
  $RequiredDirs = array($CacheLoc, $convertDir0, $ConvertDir, $ConvertTemp, $convertTempDir0, $ConvertTempDir, $StreamTemp, $ScadTemp, $LogDir);
  $RequiredIndexes = array($ConvertTemp, $convertTempDir0, $ConvertTempDir);
  // / Create a list of directories that will be emptied & removed if found.
  // / These folders are artifacts of older versions of HRConvert2 that are no longer required.
  // / The folders an older layout left inside the installation are looked for in the hosted location as well, because older releases were installed there.
  $RequiredCleanupFolders = array_values(array_unique(array($webRoot.$DirSep.'.cache', $webRoot.$DirSep.'.config', $InstLoc.$DirSep.'Logs', $InstLoc.$DirSep.'.cache', $InstLoc.$DirSep.'.config', $ProprietaryLoc.$DirSep.'.cache', $ProprietaryLoc.$DirSep.'.config', $InstLoc.$DirSep.'.github'.$DirSep.'workflows', $InstLoc.$DirSep.'.github', $InstLoc.$DirSep.'.git', $HostedLoc.$DirSep.'Logs', $HostedLoc.$DirSep.'.cache', $HostedLoc.$DirSep.'.config', $HostedLoc.$DirSep.'.github'.$DirSep.'workflows', $HostedLoc.$DirSep.'.github', $HostedLoc.$DirSep.'.git')));
  // / A/V related variables.
  $UserClamLogFileName = 'User_ClamScan_Virus_Scan_Report.txt';
  $UserClamLogFile = $ConvertDir.$UserClamLogFileName;
  $UserScanCoreFileName = 'User_ScanCore_Virus_Scan_Report.txt';
  $UserScanCoreLogFile = $ConvertDir.$UserScanCoreFileName;
  $ConsolidatedLogFileName = 'User_Consolidated_Virus_Scan_Report.txt';
  $ConsolidatedLogFile = $ConvertTempDir.$ConsolidatedLogFileName;
  // / Format related variables.
  $ImageInputArray = $ImageOutputArray = $SCADInputArray = $SCADOutputArray = array();
  $DrawingInputArray = $DrawingOutputArray = array();
  $ModelInputArray = $ModelOutputArray = array();
  $ArchiveArray = $DearchiveArray = $DocumentArray = $SpreadsheetArray = $PresentationInputArray = $PresentationOutputArray = $XPSInputArray = $XPSOutputArray = $ImageArray = $MediaInputArray = $MediaOutputArray = $VideoInputArray = $VideoOutputArray = $StreamArray = $DrawingArray = $ModelArray = $SubtitleInputArray = $SubtitleOutputArray = $PDFWorkArr = $StreamOutputArray = $SCADArray = $SCADOutputArray = $SVGInputArray = $SVGOutputArray = $EbookInputArray = $EbookOutputArray = $BootableIsoArray = $allArrays = array();
  if (in_array('Archive', $SupportedConversionTypes)) $ArchiveArray = array_map('strtolower', $UserArchiveArray);
  if (in_array('Archive', $SupportedConversionTypes)) $DearchiveArray = array_map('strtolower', $UserDearchiveArray);
  if (in_array('Archive', $SupportedConversionTypes) && $AllowBootableIsoImage) {
    $BootableIsoArray = array_map('strtolower', $UserBootableIsoArray);
    $ArchiveArray = array_map('strtolower', array_merge($ArchiveArray, $BootableIsoArray)); }
  if (in_array('Document', $SupportedConversionTypes)) $DocumentArray = array_map('strtolower', $UserDocumentArray);
  if (in_array('Document', $SupportedConversionTypes)) $SpreadsheetArray = array_map('strtolower', $UserSpreadsheetArray);
  if (in_array('Document', $SupportedConversionTypes)) $XPSInputArray = array_map('strtolower', $UserXPSInputArray);
  if (in_array('Document', $SupportedConversionTypes)) $XPSOutputArray = array_map('strtolower', $UserXPSOutputArray);
  if (in_array('Document', $SupportedConversionTypes)) $PresentationInputArray = array_map('strtolower', $UserPresentationInputArray);
  if (in_array('Document', $SupportedConversionTypes)) $PresentationOutputArray = array_map('strtolower', $UserPresentationOutputArray);
  if (in_array('Image', $SupportedConversionTypes)) {
    $ImageArray = array_map('strtolower', $UserImageArray);
    $ImageInputArray = array_map('strtolower', $UserImageInputArray);
    $ImageOutputArray = array_map('strtolower', $UserImageOutputArray); }
  if (in_array('Audio', $SupportedConversionTypes)) $MediaInputArray = array_map('strtolower', $UserMediaInputArray);
  if (in_array('Audio', $SupportedConversionTypes)) $MediaOutputArray = array_map('strtolower', $UserMediaOutputArray);
  if (in_array('Video', $SupportedConversionTypes)) $VideoInputArray = array_map('strtolower', $UserVideoInputArray);
  if (in_array('Video', $SupportedConversionTypes)) $VideoOutputArray = array_map('strtolower', $UserVideoOutputArray);
  // / Stream is already split & is not given an input & output pair like the others.
  // / $StreamArray is what may be fetched & $StreamOutputArray is derived from the audio
  // / and video lists, because a stream is written as a media file rather than as another
  // / stream. A configured output list here would be a third place to keep in step with
  // / those two & would be wrong the first time either changed.
  if (in_array('Stream', $SupportedConversionTypes) && in_array('Audio', $SupportedConversionTypes) && in_array('Video', $SupportedConversionTypes)) $StreamArray = array_map('strtolower', $UserStreamArray);
  if (in_array('Stream', $SupportedConversionTypes) && in_array('Audio', $SupportedConversionTypes) && in_array('Video', $SupportedConversionTypes)) $StreamOutputArray = array_map('strtolower', array_merge($UserMediaOutputArray, $UserVideoOutputArray));
  if (in_array('Drawing', $SupportedConversionTypes)) {
    $DrawingArray = array_map('strtolower', $UserDrawingArray);
    $DrawingInputArray = array_map('strtolower', $UserDrawingInputArray);
    $DrawingOutputArray = array_map('strtolower', $UserDrawingOutputArray); }
  if (in_array('SVG', $SupportedConversionTypes)) $SVGInputArray = array_map('strtolower', $UserSVGInputArray);
  if (in_array('SVG', $SupportedConversionTypes)) $SVGOutputArray = array_map('strtolower', $UserSVGOutputArray);
  // / The Model lists are read directly, because both are in the required configuration list & a configuration missing either one never reaches this line.
  // / The required list is what the startup check reads, so a setting absent from it is never verified & a fallback for it would become permanent.
  // / It is also how the core decides a configuration is valid at all.
  // / $UserModelArray remains required & still feeds $ModelArray, which other code reads.
  if (in_array('Model', $SupportedConversionTypes)) {
    $ModelArray = array_map('strtolower', $UserModelArray);
    $ModelInputArray = array_map('strtolower', $UserModelInputArray);
    $ModelOutputArray = array_map('strtolower', $UserModelOutputArray); }
  if (in_array('Scad', $SupportedConversionTypes)) {
    $SCADArray = array_map('strtolower', $UserSCADArray);
    $SCADInputArray = array_map('strtolower', $UserSCADInputArray);
    $SCADOutputArray = array_map('strtolower', $UserSCADOutputArray); }
  // / The SCAD output list is read rather than derived, so the upload list & the output list are each declared.
  if (in_array('Subtitle', $SupportedConversionTypes)) $SubtitleInputArray = $UserSubtitleInputArray;
  if (in_array('Subtitle', $SupportedConversionTypes)) $SubtitleOutputArray = array_map('strtolower', $UserSubtitleOutputArray);
  if (in_array('OCR', $SupportedConversionTypes) && in_array('Document', $SupportedConversionTypes)) $PDFWorkArr = array_map('strtolower', $UserPDFWorkArr);
  if (in_array('Ebook', $SupportedConversionTypes)) $EbookInputArray = array_map('strtolower', $UserEbookInputArray);
  if (in_array('Ebook', $SupportedConversionTypes)) $EbookOutputArray = array_map('strtolower', $UserEbookOutputArray);
  $allArrays = array(
    $ArchiveArray, $DearchiveArray, $DocumentArray, $SpreadsheetArray,
    $PresentationInputArray, $PresentationOutputArray, $ImageArray,
    $MediaInputArray, $MediaOutputArray, $VideoInputArray, $VideoOutputArray,
    $StreamArray, $StreamOutputArray, $DrawingArray, $SVGInputArray,
    $SVGOutputArray, $ModelArray, $ModelInputArray, $ModelOutputArray,
    $ImageInputArray, $ImageOutputArray, $SCADInputArray, $SCADOutputArray,
    $DrawingInputArray, $DrawingOutputArray, $SubtitleInputArray, $SubtitleOutputArray,
    $PDFWorkArr, $XPSInputArray, $XPSOutputArray, $SCADArray, $BootableIsoArray,
    $EbookInputArray, $EbookOutputArray);
  $Allowed = array_map('strtolower', array_unique(array_merge(...$allArrays)));
  $SupportedFormatCount = count($Allowed);
  // / Check that all sanitization checks passed.
  if ($sanitizeGlobalCheck) $GlobalsAreVerified = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $convertDir0, $convertTempDir0, $subDir, $partURL, $serverRootPrefix, $allArrays, $webRoot, $additionalIndex, $additionalEntry, $sanitizeGlobalCheck, $sanitizeGlobalCheckA, $sanitizeGlobalCheckB, $sanitizeGlobalCheckC, $sanitizeGlobalCheckD, $sanitizeGlobalCheckE);
  return array($GlobalsAreVerified); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to ask ImageMagick where its configuration actually lives.
// / Accepts no arguments.
// / Returns the absolute configuration directory, or an empty string when none is found.
// / A distribution package puts policy.xml in /etc/ImageMagick-7, & a build from source puts it under its prefix, usually /usr/local/etc/ImageMagick-7.
// / ImageMagick reports its own configuration path, so it is asked rather than guessed.
// / Nothing is written there, & it is asked so the policy an earlier release wrote there is found & put back.
function resolveImageMagickConfigDir() {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  static $cachedDirectory = NULL;
  $ConfigDirectory = '';
  $magickBinary = $outputLine = '';
  $commandOutput = $pathMatches = $candidateDirectories = array();
  $commandExitCode = 1;
  $candidateDirectory = '';
  if ($cachedDirectory !== NULL) $ConfigDirectory = $cachedDirectory;
  else {
    $magickBinary = locateDependency('magick');
    if ($magickBinary === '') $magickBinary = locateDependency('convert');
    // / -list configure prints CONFIGURE_PATH, which is where this build reads policy.xml.
    if ($magickBinary !== '') {
      exec(escapeshellarg($magickBinary).' -list configure 2>&1', $commandOutput, $commandExitCode);
      if ($commandExitCode === 0) {
        foreach ($commandOutput as $outputLine) {
          if ($ConfigDirectory === '' && preg_match('/^CONFIGURE_PATH\s+(\S.*?)\s*$/', (string)$outputLine, $pathMatches)) $ConfigDirectory = rtrim(trim($pathMatches[1]), DIRECTORY_SEPARATOR); } } }
    // / A build that will not say is looked for in the usual places, newest first.
    if ($ConfigDirectory === '') {
      $candidateDirectories = array('/usr/local/etc/ImageMagick-7', '/etc/ImageMagick-7', '/usr/local/etc/ImageMagick-6', '/etc/ImageMagick-6');
      foreach ($candidateDirectories as $candidateDirectory) {
        if ($ConfigDirectory === '' && is_dir($candidateDirectory)) $ConfigDirectory = $candidateDirectory; }
      if ($ConfigDirectory !== '') warningEntry('ImageMagick would not report its configuration path, so '.$ConfigDirectory.' was located by search. Confirm it is the directory this build actually reads.'); }
    if ($Verbose) logEntry('ImageMagick configuration path: '.($ConfigDirectory === '' ? 'NOT FOUND' : $ConfigDirectory).'.');
    $cachedDirectory = $ConfigDirectory; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $magickBinary, $outputLine, $commandOutput, $pathMatches, $candidateDirectories, $commandExitCode, $candidateDirectory);
  return $ConfigDirectory; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to cover the policy.xml the installed ImageMagick reads, inside one sandbox profile.
// / Accepts the sandbox profile a conversion is about to run under.
// / Returns TRUE when that profile now covers the file, & FALSE when ImageMagick named no folder.
// / The profiles name where a package or a build from source keeps the policy. A build with another
// / prefix keeps it somewhere else, so ImageMagick is asked, as it is everywhere else, & the answer
// / joins the profile's Overlays. It is asked as a conversion that needs the answer is about to run,
// / because asking as every request starts would run ImageMagick for requests that convert nothing.
function coverResolvedImageMagickPolicy($sandboxProfile) {
  // / Set variables.
  global $EngineSandboxProfiles, $DirSep, $EnableMemoryProtection;
  $PolicyIsCovered = FALSE;
  $configDirectory = $policyPath = '';
  $configDirectory = resolveImageMagickConfigDir();
  if ($configDirectory !== '' && is_array($EngineSandboxProfiles) && isset($EngineSandboxProfiles[$sandboxProfile]) && is_array($EngineSandboxProfiles[$sandboxProfile])) {
    $policyPath = $configDirectory.$DirSep.'policy.xml';
    if (!isset($EngineSandboxProfiles[$sandboxProfile]['Overlays']) or !is_array($EngineSandboxProfiles[$sandboxProfile]['Overlays'])) $EngineSandboxProfiles[$sandboxProfile]['Overlays'] = array();
    if (!isset($EngineSandboxProfiles[$sandboxProfile]['Overlays'][$policyPath])) $EngineSandboxProfiles[$sandboxProfile]['Overlays'][$policyPath] = 'policy.xml';
    $PolicyIsCovered = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $configDirectory, $policyPath, $sandboxProfile);
  return $PolicyIsCovered; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to check the ImageMagick policy each conversion carries, & to take back the one an
// / earlier release wrote into the host.
// / Accepts a boolean permitting a change.
// / Returns a validity boolean & a status word, in that order.
// / Each pipeline that runs ImageMagick carries its own policy, in a Policy folder beside its converter, & takes it into every conversion's sandbox.
// / Nothing is written into the host.
// / A copy an earlier release wrote over the host's policy.xml is put back from the copy verifyPolicyFile() kept, so the directory is resolved from the binary rather than guessed.
// / A pipeline that is installed & is missing its policy is reported, because its conversions then run under the host's own.
// / A copy of this application's that kept no backup of what it replaced is left in place & called kept, because removing it would leave ImageMagick with no policy at all.
// / Every ImageMagick on the host is looked at, not only the one the conversions run, because a host with a package & a build keeps a policy for each.
// / Where several say different things, the status that most needs saying is the one reported.
// / The result is cached for the request, because the policy cannot change underneath a running request.
function verifyImageMagickPolicy($mayRepair) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection, $DirSep, $InstLoc;
  static $cachedValid = NULL;
  static $cachedStatus = 'unchecked';
  static $cachedMayRepair = FALSE;
  $PolicyIsValid = FALSE;
  $PolicyStatus = 'unchecked';
  $configDirectory = $retireStatus = $pipelineName = $pipelineFolder = $policyPath = $copyStatus = '';
  $hostCopyIsRetired = $policyIsCarried = FALSE;
  $policyPaths = $foundPaths = array();
  $statusRanks = array('none' => 0, 'retired' => 1, 'kept' => 2, 'outdated' => 3, 'refused' => 4);
  // / A cached result answers every later call but one. A failure found without permission to
  // / repair is checked again when a repair is permitted, because an Environment Manager pass
  // / checks before it repairs, & the cached check would answer the repair with the failure it
  // / was asked to fix.
  if ($cachedValid !== NULL && ($cachedValid or $cachedMayRepair or !$mayRepair)) {
    $PolicyIsValid = $cachedValid;
    $PolicyStatus = $cachedStatus; }
  else {
    $configDirectory = resolveImageMagickConfigDir();
    $retireStatus = 'none';
    if ($configDirectory !== '') $policyPaths[] = $configDirectory.$DirSep.'policy.xml';
    $foundPaths = array_merge((array)glob('/etc/ImageMagick-*/policy.xml'), (array)glob('/usr/local/etc/ImageMagick-*/policy.xml'));
    foreach ($foundPaths as $policyPath) if (is_string($policyPath) && !in_array($policyPath, $policyPaths, TRUE)) $policyPaths[] = $policyPath;
    foreach ($policyPaths as $policyPath) {
      list ($hostCopyIsRetired, $copyStatus) = retirePolicyFile('ImageMagick', $policyPath, $mayRepair, FALSE);
      if (isset($statusRanks[$copyStatus]) && $statusRanks[$copyStatus] > $statusRanks[$retireStatus]) $retireStatus = $copyStatus; }
    // / Every pipeline here that runs ImageMagick carries a policy, & is judged only if it is installed.
    $policyIsCarried = TRUE;
    foreach (array('Image', 'OCR', 'Identify') as $pipelineName) {
      $pipelineFolder = $InstLoc.$DirSep.'Resources'.$DirSep.'Pipelines'.$DirSep.$pipelineName;
      if (is_file($pipelineFolder.$DirSep.'pipeline.php') && !is_file($pipelineFolder.$DirSep.'Policy'.$DirSep.'policy.xml')) $policyIsCarried = FALSE; }
    if (!$policyIsCarried) $PolicyStatus = 'not carried';
    else if ($retireStatus === 'outdated' or $retireStatus === 'refused') $PolicyStatus = $retireStatus;
    else if ($retireStatus === 'retired' or $retireStatus === 'kept') $PolicyStatus = $retireStatus;
    else if ($configDirectory === '') $PolicyStatus = 'absent';
    else $PolicyStatus = 'carried';
    $PolicyIsValid = ($PolicyStatus === 'carried' or $PolicyStatus === 'retired' or $PolicyStatus === 'kept' or $PolicyStatus === 'absent');
    if ($Verbose) logEntry('Policy Check: ImageMagick, Status: '.$PolicyStatus.'.');
    $cachedValid = $PolicyIsValid;
    $cachedStatus = $PolicyStatus;
    $cachedMayRepair = (bool)$mayRepair; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $configDirectory, $retireStatus, $pipelineName, $pipelineFolder, $policyPath, $copyStatus, $hostCopyIsRetired, $policyIsCarried, $policyPaths, $foundPaths, $statusRanks, $mayRepair);
  return array($PolicyIsValid, $PolicyStatus); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to check what confines OpenSCAD on this host, & to take back the override an
// / earlier release added to it.
// / Accepts a boolean permitting a change.
// / Returns a validity boolean & a status word, in that order.
// / Nothing is added to a profile that confines OpenSCAD.
// / A render runs in its sandbox, where the session area is /work, so a grant by host path never reaches one.
// / An override an earlier release added is taken back here, & a profile that confines OpenSCAD is reported, because it is what governs a render.
// / Most distributions do not confine OpenSCAD at all, & then there is nothing to report.
// / The result is cached for the request.
function verifyOpenScadPolicy($mayRepair) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  static $cachedValid = NULL;
  static $cachedStatus = 'unchecked';
  static $cachedMayRepair = FALSE;
  $PolicyIsValid = FALSE;
  $PolicyStatus = 'unchecked';
  $confiningProfile = $overridePath = $retireStatus = $candidateProfile = '';
  $candidateProfiles = array('/etc/apparmor.d/usr.bin.openscad', '/etc/apparmor.d/openscad');
  $overrideIsRetired = FALSE;
  // / A cached result answers every later call but one, as verifyImageMagickPolicy() says.
  if ($cachedValid !== NULL && ($cachedValid or $cachedMayRepair or !$mayRepair)) {
    $PolicyIsValid = $cachedValid;
    $PolicyStatus = $cachedStatus; }
  else {
    foreach ($candidateProfiles as $candidateProfile) {
      if ($confiningProfile === '' && file_exists($candidateProfile)) $confiningProfile = $candidateProfile; }
    if ($confiningProfile === '') {
      // / No profile confines OpenSCAD, so there is nothing to report & nothing is wrong.
      $PolicyIsValid = TRUE;
      $PolicyStatus = 'unconfined';
      // / Logged once per request rather than per conversion. A host where nothing confines
      // / OpenSCAD reports this on every single model, which is noise about a non event.
      if ($Verbose) logEntry('Policy Check: OpenSCAD, Status: unconfined, nothing confines it & the sandbox is what isolates it.'); }
    else {
      $overridePath = '/etc/apparmor.d/local/'.basename($confiningProfile);
      list ($overrideIsRetired, $retireStatus) = retirePolicyFile('OpenSCAD AppArmor', $overridePath, $mayRepair, TRUE);
      // / The profile is loaded again without what was added to it.
      if ($retireStatus === 'retired') reloadApparmorProfile($confiningProfile);
      $PolicyStatus = ($retireStatus === 'none' ? 'confined' : $retireStatus);
      $PolicyIsValid = ($PolicyStatus === 'confined' or $PolicyStatus === 'retired');
      if ($Verbose) logEntry('Policy Check: OpenSCAD, Status: '.$PolicyStatus.', confined by '.$confiningProfile.'.'); }
    $cachedValid = $PolicyIsValid;
    $cachedStatus = $PolicyStatus;
    $cachedMayRepair = (bool)$mayRepair; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $confiningProfile, $overridePath, $retireStatus, $candidateProfile, $candidateProfiles, $overrideIsRetired, $mayRepair);
  return array($PolicyIsValid, $PolicyStatus); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to check the AppArmor profile bubblewrap needs, as a request or a command starts.
// / Accepts nothing. Returns the validity verifySandboxPolicy() reported, which no caller acts
// / on, because the callee has already logged whatever it found & convention 9 says the caller
// / need not log it twice.
// / Every command only checks it here, root's included.
// / Only -fp writes the host's files, & -fp repairs this profile itself, as does an Environment Manager pass whose repair is permitted & due, because that repair is -fp's.
// / It runs once the Engine has loaded, & not at all without one, because without an Engine there is no sandbox for the profile to serve.
// / When the check runs is still the application's, because it is the application that knows when a request starts.
// / The check lives in the Environment Core, which the Engine runs on without & warns about, so a missing one skips the check.
// / Until v4.1.2 it was called regardless, so a drop-in carrying the Engine without its Environment Core, unpacked over the
// / web root by mistake, stopped with a PHP fatal error before -fp could say where the drop-in belongs.
function verifySandboxPolicyAtStart() {
  // / Set variables.
  global $EnableMemoryProtection;
  $PolicyIsValid = FALSE;
  $policyStatus = '';
  if (function_exists('verifySandboxPolicy')) list ($PolicyIsValid, $policyStatus) = verifySandboxPolicy(FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $policyStatus);
  return $PolicyIsValid; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to look up a tool's verified path by its manifest name.
// / Accepts the manifest name. Returns the path, or FALSE when the tool is unusable.
// / The manifest is the only owner of a tool's minimum. This asks it through verifiedToolPath,
// / so -v, archiving & scanning get the same answer the pipelines & --check-depends get.
// / verifiedToolPath lives in the Pipeline Core, which may have failed to load. -v is what an
// / operator runs to find out why, so this must not fatal: a missing Pipeline Core reads as every
// / tool unusable, & -v's own component check reports the Pipeline Core itself.
// / The Engine is checked as well as the Pipeline Core, & testing only the function in front of
// / us was not enough. verifiedToolPath asks the Pipeline Core, which asks Dependency Core,
// / which asks the Engine for the application name. Every one of those exists while the Engine
// / is loaded, so the guard looked complete & -v died four frames deeper than it was reading.
function toolPath($manifestName) {
  // / Set variables.
  global $EnableMemoryProtection, $EngineActive;
  $ToolPath = FALSE;
  if (isset($EngineActive) && $EngineActive && function_exists('verifiedToolPath')) $ToolPath = verifiedToolPath($manifestName);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $manifestName);
  return $ToolPath; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the minimum version the manifest requires of a tool.
// / Accepts the manifest name. Returns the minimum, or the word unknown when it cannot be read.
// / This is the sibling of toolPath & exists for the same reason. toolMinimum lives in the
// / Engine, the manifest is its only owner, & -v prints the minimum beside every dependency
// / that failed. An operator running -v because the Engine is broken must still get the report.
// / The word unknown is deliberate. An empty string would print as "requires  or later".
function toolRequirement($manifestName) {
  // / Set variables.
  global $EnableMemoryProtection, $EngineActive;
  $ToolRequirement = 'unknown';
  if (isset($EngineActive) && $EngineActive && function_exists('toolMinimum')) $ToolRequirement = toolMinimum($manifestName);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $manifestName);
  return $ToolRequirement; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to display version information about this installation.
// / Called by the -v & --version command line arguments.
// / Reports the version of every component that carries one, & the state of every
// / dependency HRConvert2 enforces a minimum version against.
// / Every check performed here is the IDENTICAL check the matching converter performs.
// / This answers whether an installation will actually work rather than what is configured.
// / A dependency reported here as OK is one whose located binary satisfied its minimum, &
// / the path that was verified is the path the converter will run.
// / Bubblewrap is the exception. It is a capability check rather than a version check,
// / because a working binary is not the same thing as a working sandbox.
// / An interface or language pack version is read by PATTERN rather than by loading the
// / file, because loading twenty version files would overwrite the variable each time.
function showVersionInfo() {
  // / Set variables.
  global $EngineActive, $DependsManifest, $KernelVersion, $ApacheUser, $RequiredKernelVersion, $RequiredEngineVersion, $SecretFile, $SecretLoc, $ManagerSocketDir, $InstLoc, $HostedLoc, $ApplicationLoc, $ServerRootDir, $HRConvertVersion, $ConfigVersion, $RequiredConfigVersion, $RequiredGuiVersion, $RequiredLanguageVersion, $RequiredSetupCoreVersion, $RequiredDependencyCoreVersion, $RequiredDependsVersion, $RequiredPipelineCoreVersion, $PipelineCoreActive, $PipelineCount, $RequiredSecretVersion, $RequiredConfigScript, $ApplicationName, $SupportedConversionTypes, $SupportedGuis, $SupportedLanguages, $DirSep, $Lol, $UsePyMeshLab, $AllowBootableIsoImage, $RequireSandbox, $RequireSandboxOnDocker, $RunningInContainer, $RunningAsRoot, $CurrentUser, $EnableMemoryProtection, $EnableResourceAwareness, $RequireResourceAwareness, $ResourceAwarenessActive, $CoreManagerVersion, $TotalResourceBudget, $ReserveResourcePercentage, $MaxConcurrentWorkers, $MaxExpectedRuntime, $CoreManagerSubprocessPollInterval, $ResourcePollInterval, $WorkerReapInterval, $WorkerStaleGracePeriod, $ConvertTemp, $MaintainHTAccess, $EngineVersion, $HostArchitecture, $HostMachineString, $AllowUserURLDownload, $AllowUserVirusScan, $VirusScan, $AllowUserShare, $AllowUserIdentify, $EnvironmentManagerMayRepair, $EnablePerConversionLimits, $Pipelines, $RequiredWorkflowCoreVersion, $EnableWorkflows, $EnableWorkflowBuilder, $EnableWorkflowImportExport;
  $VersionInfoDisplayed = $modelsAreValid = $ocrToolsAreValid = $archiveToolsAreValid = $libreOfficeIsValid = FALSE;
  $ffmpegBinary = $streamFfmpegBinary = $inkscapeBinary = $diaBinary = $scadBinary = $imageBinary = $ebookBinary = FALSE;
  $assimpBinary = $meshlabBinary = $tesseractBinary = $pdftotextBinary = FALSE;
  $sevenZipBinary = $rarBinary = $zipBinary = $tarBinary = $mkisofsBinary = $isoHybridBinary = FALSE;
  $fileBinary = $probeBinary = $pdfInfoBinary = FALSE;
  $listenerIsRunning = $bwrapBinary = $listenerIsAsked = FALSE;
  $sandboxLauncher = $secretLabel = '';
  $listenerStatus = array();
  $installedGui = $installedLang = $installedEndonym = $checkDir = $checkFile = $foundVersion = $langLine = '';
  $guiMatches = $langMatches = array();
  $langOk = $langTotal = 0;
  // / The report collapses each group to a count & names only what failed.
  $componentChecks = $dependencyChecks = $subsystemChecks = array();
  $componentPair = $dependencyState = array();
  $componentName = $dependencyName = $subsystemName = $scriptStatus = $scriptDisplayStatus = $scriptDetail = $hostedStatus = $hostedDetail = $setupVersion = '';
  $misplacedRoots = array();
  $hostedWritten = $hostedRemoved = 0;
  $setupIsAvailable = $treeIsServed = FALSE;
  $subsystemIsReady = $scriptIsValid = FALSE;
  $failureCount = 0;
  $maintainHtaccess = $apacheConfigIsInstalled = $dataIsProtected = FALSE;
  $exposureStatus = $exposureDetail = $secretMode = $socketMode = $manifestVersion = $pipelineKind = $secretExpected = '';
  $secretShown = $olderSecretFile = $secretsState = $secretsMode = $secretsOwnership = $topCopyMode = '';
  $secretOwner = $topCopyOwner = $secretGroup = $topCopyGroup = $secretGroupExpected = $webAccountNumber = -1;
  $secretPasses = FALSE;
  $pipelineKindCounts = $pipelineKindLabels = $pipelineRecord = $disabledFeatures = $disabledFeature = $manifestEntry = $serviceEntry = array();
  $servicePath = $choiceVersion = $choiceStatus = $choiceOutput = $choicePath = $choiceOrigin = '';
  $choiceIsPresent = FALSE;
  $pipelineKindCount = 0;
  // / Run every dependency check so this reports what WORKS, not what is configured.
  $ffmpegBinary = toolPath('FFMPEG');
  $libreOfficeIsValid = (toolPath('LibreOffice') !== FALSE);
  $inkscapeBinary = toolPath('Inkscape');
  $diaBinary = toolPath('Dia');
  $scadBinary = toolPath('OpenSCAD');
  $imageBinary = toolPath('ImageMagick');
  $ebookBinary = toolPath('Calibre');
  $assimpBinary = toolPath('Assimp');
  $meshlabBinary = toolPath('MeshLab');
  $modelsAreValid = ($assimpBinary !== FALSE && ($UsePyMeshLab or $meshlabBinary !== FALSE));
  $tesseractBinary = toolPath('Tesseract');
  $pdftotextBinary = toolPath('Poppler Utils');
  $ocrToolsAreValid = ($tesseractBinary !== FALSE && $pdftotextBinary !== FALSE);
  $sevenZipBinary = toolPath('7-Zip');
  $rarBinary = toolPath('RAR');
  $zipBinary = toolPath('Zip');
  $tarBinary = toolPath('Tar');
  $mkisofsBinary = toolPath('Genisoimage');
  $archiveToolsAreValid = ($sevenZipBinary !== FALSE && $zipBinary !== FALSE && $tarBinary !== FALSE && $mkisofsBinary !== FALSE);
  $isoHybridBinary = toolPath('Syslinux Utils');
  // / Identify reads a file with each of these where it is installed, & hashes it whether or not they are.
  // / ImageMagick & 7-Zip are listed already, for the conversions they serve.
  $fileBinary = toolPath('File');
  $probeBinary = toolPath('FFprobe');
  $pdfInfoBinary = toolPath('PDFInfo');
  // / The sandbox probe lives in the Engine, so with no Engine the honest answer is not functional.
  // / The launcher says whether a sandbox runs the application's own copy of bubblewrap, which is how a kernel restricting unprivileged namespaces is kept restricted.
  if (function_exists('verifySandboxLauncher')) list ($bwrapBinary, $sandboxLauncher) = verifySandboxLauncher();
  // / Report what is installed & what state it is in.
  // / Every line is a fact. An explanation belongs in Documentation, not in this output.
  print($Lol.$ApplicationName.' '.$HRConvertVersion.$Lol);
  // / The Engine sits with the application rather than under detachable components.
  // / It is what this application RUNS ON. Listing it beside the things that can be
  // / removed suggested it was one of them.
  // / The kernel line reports the version that is RUNNING, from the variable the kernel set
  // / when it loaded, rather than reading a file. $InstLoc may name another installation, & the
  // / kernel is always the one beside this file, so reading through $InstLoc could report a
  // / kernel that is not the one in use.
  print('  Kernel     '.ltrim((string)$KernelVersion, 'vV').', requires '.ltrim((string)$RequiredKernelVersion, 'vV').$Lol);
  print('  Engine     '.ltrim((string)readComponentVersion('Engine'.$DirSep.'engine.php', 'EngineVersion'), 'vV').', requires '.ltrim((string)$RequiredEngineVersion, 'vV').$Lol);
  print('  Config     '.$ConfigVersion.', requires '.$RequiredConfigVersion.' or later'.$Lol);
  // / The machine, because a dependency that is unsupported here is unsupported because
  // / of this line & an operator reading a report needs to see it before the reasons.
  print('  Machine    '.$HostArchitecture.($HostMachineString !== '' && $HostMachineString !== $HostArchitecture ? ' ('.$HostMachineString.')' : '').$Lol);

  // / Detachable components. Each is an EXACT match & a mismatch removes what it provides.
  print($Lol.'Detachable components'.$Lol);
  $componentChecks = array(
    'Setup Core' => array(readComponentVersion('Engine'.$DirSep.'Cores'.$DirSep.'setupCore.php', 'SetupCoreVersion'), $RequiredSetupCoreVersion),
    'Dependency Core' => array(readComponentVersion('Engine'.$DirSep.'Cores'.$DirSep.'dependencyCore.php', 'DependencyCoreVersion'), $RequiredDependencyCoreVersion),
    'Pipeline Core' => array(readComponentVersion('Engine'.$DirSep.'Cores'.$DirSep.'pipelineCore.php', 'PipelineCoreVersion'), $RequiredPipelineCoreVersion),
    'Workflow Core' => array(readComponentVersion('Engine'.$DirSep.'Cores'.$DirSep.'workflowCore.php', 'WorkflowCoreVersion'), $RequiredWorkflowCoreVersion));
  foreach ($componentChecks as $componentName => $componentPair) {
    if (ltrim((string)$componentPair[0], 'vV') === ltrim((string)$componentPair[1], 'vV')) print('  '.str_pad($componentName, 28).'OK, '.ltrim((string)$componentPair[0], 'vV').$Lol);
    else {
      $failureCount++;
      print('  '.str_pad($componentName, 28).'FAILED, reports '.($componentPair[0] === '' ? 'no version' : ltrim((string)$componentPair[0], 'vV')).', requires '.$componentPair[1].$Lol); } }
  // / The bootstrap script is a component too, pinned exactly, though it is bash & the Engine judges it rather than reading it like a core.
  // / A script that needs attention is a failure, & one disabled because it does not match is the safe state & is not.
  if (!function_exists('verifyBootstrapScript') or !function_exists('describeBootstrapStatus') or !function_exists('findingIsBenign')) {
    $failureCount++;
    print('  '.str_pad('Bootstrap script', 28).'UNKNOWN, the Engine is unavailable'.$Lol); }
  else {
    list ($scriptIsValid, $scriptStatus) = verifyBootstrapScript();
    list ($scriptDisplayStatus, $scriptDetail) = describeBootstrapStatus($scriptStatus);
    if (!findingIsBenign($scriptDisplayStatus)) $failureCount++;
    print('  '.str_pad('Bootstrap script', 28).strtoupper($scriptDisplayStatus).', '.$scriptDetail.$Lol); }
  // / Pipelines are listed as a SECTION with a line per kind, the same shape the language
  // / packs use. One line saying thirteen verified hid that two of them scan rather than
  // / convert, & a scanner missing is a different problem from a converter missing.
  // / The kinds are counted from what enumeratePipelines already returned rather than
  // / counted again here, so this cannot disagree with what was loaded.
  print($Lol.'Installed pipelines'.$Lol);
  if (!$PipelineCoreActive) print('  '.str_pad('Conversion pipelines', 28).'UNAVAILABLE, no conversion can run'.$Lol);
  else {
    // / A line per KIND, & a kind with none installed is not shown at all rather than shown
    // / as zero. An application with no scanners should not be told it has none.
    $pipelineKindCounts = array('conversion' => 0, 'file' => 0, 'operation' => 0, 'scanner' => 0);
    if (is_array($Pipelines)) foreach ($Pipelines as $pipelineRecord) {
      if (isset($pipelineKindCounts[$pipelineRecord['Kind']])) $pipelineKindCounts[$pipelineRecord['Kind']]++;
      else $pipelineKindCounts['conversion']++; }
    $pipelineKindLabels = array('conversion' => 'Conversion pipelines', 'file' => 'File pipelines',
      'operation' => 'Operation pipelines', 'scanner' => 'Security pipelines');
    foreach ($pipelineKindCounts as $pipelineKind => $pipelineKindCount) {
      if ($pipelineKindCount === 0) continue;
      print('  '.str_pad($pipelineKindLabels[$pipelineKind], 28).$pipelineKindCount.' of '.$pipelineKindCount.' OK'.$Lol); } }

  // / Dependencies. An optional one says so, because losing it costs a feature rather than
  // / a subsystem.
  print($Lol.'Dependencies'.$Lol);
  // / The manifest is the first thing listed because everything below it is read FROM it.
  // / A manifest that failed its version check makes every line under it unreliable.
  // / Read relative to Resources, as readComponentVersion requires. contractPath is the location's
  // / one definition & this is the one place it cannot be used, because it returns an absolute path.
  $manifestVersion = readComponentVersion('Contract'.$DirSep.'depends.php', 'DependsVersion');
  if (ltrim((string)$manifestVersion, 'vV') === ltrim((string)$RequiredDependsVersion, 'vV')) print('  '.str_pad('Manifest', 28).'OK, '.ltrim((string)$manifestVersion, 'vV').$Lol);
  else { $failureCount++; print('  '.str_pad('Manifest', 28).'FAILED, reports '.($manifestVersion === '' ? 'no version' : ltrim((string)$manifestVersion, 'vV')).' & this core requires '.ltrim((string)$RequiredDependsVersion, 'vV').$Lol); }
  $dependencyChecks = array(
    'FFMPEG, media & streams' => array($ffmpegBinary !== FALSE, toolRequirement('FFMPEG'), TRUE),
    'LibreOffice, documents' => array($libreOfficeIsValid, toolRequirement('LibreOffice'), TRUE),
    'ImageMagick, images' => array($imageBinary !== FALSE, toolRequirement('ImageMagick'), TRUE),
    'Inkscape, SVG' => array($inkscapeBinary !== FALSE, toolRequirement('Inkscape'), TRUE),
    'Dia, drawings' => array($diaBinary !== FALSE, toolRequirement('Dia'), TRUE),
    'OpenSCAD, scad' => array($scadBinary !== FALSE, toolRequirement('OpenSCAD'), TRUE),
    'Assimp, models' => array($assimpBinary !== FALSE, toolRequirement('Assimp'), TRUE),
    'Meshlab, models' => array($meshlabBinary !== FALSE, toolRequirement('MeshLab'), FALSE),
    'Tesseract, OCR' => array($tesseractBinary !== FALSE, toolRequirement('Tesseract'), TRUE),
    'Pdftotext, OCR' => array($pdftotextBinary !== FALSE, toolRequirement('Poppler Utils'), TRUE),
    '7-Zip, all extraction' => array($sevenZipBinary !== FALSE, toolRequirement('7-Zip'), TRUE),
    'Zip, archives' => array($zipBinary !== FALSE, toolRequirement('Zip'), TRUE),
    'Tar, archives' => array($tarBinary !== FALSE, toolRequirement('Tar'), TRUE),
    'Mkisofs, iso' => array($mkisofsBinary !== FALSE, toolRequirement('Genisoimage'), TRUE),
    'Rar, archives' => array($rarBinary !== FALSE, toolRequirement('RAR'), FALSE),
    'Isohybrid, iso' => array($isoHybridBinary !== FALSE, toolRequirement('Syslinux Utils'), FALSE),
    'Calibre, e-books' => array($ebookBinary !== FALSE, toolRequirement('Calibre'), TRUE),
    'File, identification' => array($fileBinary !== FALSE, toolRequirement('File'), FALSE),
    'FFprobe, identification' => array($probeBinary !== FALSE, toolRequirement('FFprobe'), FALSE),
    'PDFInfo, identification' => array($pdfInfoBinary !== FALSE, toolRequirement('PDFInfo'), FALSE));
  foreach ($dependencyChecks as $dependencyName => $dependencyState) {
    if ($dependencyState[0]) print('  '.str_pad($dependencyName, 28).'OK'.($dependencyState[2] ? '' : ', optional').$Lol);
    else {
      $failureCount++;
      print('  '.str_pad($dependencyName, 28).'FAILED, requires '.$dependencyState[1].' or later'.($dependencyState[2] ? '' : ', optional').$Lol); } }
  // / A program this application built for itself, because the package manager's falls short on this
  // / host, is named with where it is, so it is never taken for the copy the host keeps current. The
  // / manifest is the one verifiedToolPath() read for the checks above. It is named only while it is the
  // / program a conversion runs, which resolveDependencyChoice() decides, & --setup --check-depends says
  // / why one that is there is not.
  if (function_exists('ownBuildInService') && function_exists('resolveDependencyChoice') && isset($DependsManifest) && is_array($DependsManifest)) {
    foreach ($DependsManifest as $manifestEntry) {
      list ($serviceEntry, $servicePath) = ownBuildInService($manifestEntry);
      if ($servicePath !== '') list ($choiceIsPresent, $choiceVersion, $choiceStatus, $choiceOutput, $choicePath, $choiceOrigin) = resolveDependencyChoice($manifestEntry);
      if ($servicePath !== '' && $choiceOrigin === 'own' && $choiceStatus === 'ok') print('  '.str_pad((string)$manifestEntry['Name'].', own build', 28).$servicePath.$Lol); } }
  print('  '.str_pad(($RunningInContainer ? 'Sandbox, Docker' : 'Sandbox, Bubblewrap'), 28).($bwrapBinary === FALSE ? 'FAILED, not functional' : 'OK'.((function_exists('privateBwrapPath') && $bwrapBinary === privateBwrapPath()) ? ', '.privateBwrapPath().', AppArmor profile '.sandboxApparmorProfileName() : '')).$Lol);
  if ($bwrapBinary === FALSE) $failureCount++;
  print('  '.str_pad('PyMeshLab, models', 28).($UsePyMeshLab ? 'enabled, Meshlab not required' : 'disabled, Meshlab required').$Lol);

  // / A subsystem is ready only when every dependency it needs is.
  print($Lol.'Subsystem readiness'.$Lol);
  $subsystemChecks = array(
    '3D models' => $modelsAreValid,
    'OCR' => $ocrToolsAreValid,
    'Archives' => $archiveToolsAreValid);
  foreach ($subsystemChecks as $subsystemName => $subsystemIsReady) {
    if (!$subsystemIsReady) $failureCount++;
    print('  '.str_pad($subsystemName, 28).($subsystemIsReady ? 'READY' : 'NOT READY').$Lol); }
  if (!$AllowBootableIsoImage) print('  '.str_pad('Bootable disk images', 28).'DISABLED in config.php'.$Lol);
  else {
    if ($mkisofsBinary === FALSE) $failureCount++;
    print('  '.str_pad('Bootable disk images', 28).($mkisofsBinary === FALSE ? 'NOT READY' : 'READY').$Lol); }

  // / Features that are installed & switched off.
  // / A gated feature is invisible when it is off, so nothing tells an operator it exists.
  // / Only the ones that are OFF are listed. A feature doing its job needs no announcement,
  // / & a list of everything that is on is a list nobody reads.
  $disabledFeatures = array();
  if (!$AllowUserURLDownload) $disabledFeatures[] = array('Fetch from a URL', 'Allow User URL Download');
  if (!$AllowUserVirusScan) $disabledFeatures[] = array('User virus scanning', 'User Virus Scanning');
  if (!$VirusScan) $disabledFeatures[] = array('Virus scanning', 'Virus Scanning');
  if (!$AllowUserShare) $disabledFeatures[] = array('Share a file', 'User Shareable File Links');
  if (!$AllowUserIdentify) $disabledFeatures[] = array('Identify a file', 'User File Identification');
  if (!$EnableWorkflows) $disabledFeatures[] = array('Workflows', 'Enable Workflows');
  // / The builder & import & export are parts of workflows, so neither is named while workflows themselves are off.
  if ($EnableWorkflows && !$EnableWorkflowBuilder) $disabledFeatures[] = array('Workflow builder', 'Enable Workflow Builder');
  if ($EnableWorkflows && !$EnableWorkflowImportExport) $disabledFeatures[] = array('Workflow import & export', 'Enable Workflow Import & Export');
  if (!$EnvironmentManagerMayRepair) $disabledFeatures[] = array('Environment repair', 'Environment Manager May Repair');
  if (!$EnablePerConversionLimits) $disabledFeatures[] = array('Per conversion limits', 'Enable Per Conversion Limits');
  if (!empty($disabledFeatures)) {
    print($Lol.'Available & switched off'.$Lol);
    foreach ($disabledFeatures as $disabledFeature) print('  '.str_pad($disabledFeature[0], 28).'--'.$disabledFeature[1].'-- in config.php'.$Lol); }

  // / Every installed interface, named, & whether it matches the version the core requires.
  print($Lol.'Installed interfaces'.$Lol);
  foreach ($SupportedGuis as $installedGui) {
    $checkDir = $InstLoc.$DirSep.'UI'.$DirSep.$installedGui;
    $checkFile = $checkDir.$DirSep.'uiVersionInfo.php';
    $foundVersion = '';
    if (!is_dir($checkDir)) {
      $failureCount++;
      print('  '.str_pad($installedGui, 28).'MISSING, no folder'.$Lol); }
    else if (!file_exists($checkFile)) {
      $failureCount++;
      print('  '.str_pad($installedGui, 28).'FAILED, no uiVersionInfo.php'.$Lol); }
    else {
      // / The literal is read & then stripped of its leading v, because the file strips it
      // / too. Comparing the raw literal against the stripped requirement never matches.
      if (preg_match_all('/\$GuiVersion\s*=\s*[\'"]([^\'"]+)[\'"]/', (string)@file_get_contents($checkFile), $guiMatches)) $foundVersion = ltrim(end($guiMatches[1]), 'vV');
      if ($foundVersion === $RequiredGuiVersion) print('  '.str_pad($installedGui, 28).'OK, '.$foundVersion.$Lol);
      else {
        $failureCount++;
        print('  '.str_pad($installedGui, 28).'FAILED, reports '.($foundVersion === '' ? 'no version' : $foundVersion).$Lol); } } }

  // / Language pack coverage, per interface. A pack that fails is named.
  print($Lol.'Installed language packs'.$Lol);
  foreach ($SupportedGuis as $installedGui) {
    $langOk = $langTotal = 0;
    $langLine = '';
    foreach ($SupportedLanguages as $installedLang => $installedEndonym) {
      $langTotal++;
      $checkFile = $InstLoc.$DirSep.'UI'.$DirSep.$installedGui.$DirSep.'Languages'.$DirSep.$installedLang.$DirSep.'languageStrings.php';
      $foundVersion = '';
      if (file_exists($checkFile)) {
        if (preg_match('/\$LanguageVersion\s*=\s*[\'"]([^\'"]+)[\'"]/', (string)@file_get_contents($checkFile), $langMatches)) $foundVersion = ltrim($langMatches[1], 'vV'); }
      if ($foundVersion === $RequiredLanguageVersion) $langOk++;
      else $langLine .= ' '.$installedLang; }
    if ($langLine !== '') $failureCount++;
    print('  '.str_pad($installedGui, 28).$langOk.' of '.$langTotal.' OK'.($langLine === '' ? '' : ', failed:'.$langLine).$Lol); }

  // / The three paths the manager security model rests on.
  // / The install secret is root's & 0640, so the web server account's group reads it, nothing else can, & only root can change it.
  // / A standard user's own secret is that user's & 0600.
  // / The socket directory is the web server account's & 0700.
  // / The install secret is kept in Secrets, which is root's, the web server account's group & 0750, so no other account can list it.
  // / Until -fp moves it there, the copy an older release kept at the top of the data location is the one in use, & the one judged here.
  // / A startup key is only worth anything because those hold, & an installation never repaired as root, or widened by a restore, loses that silently.
  // / The row judges the secret this command holds by its own role.
  // / The install secret is the one name ending in -secret.php, & a per user secret carries its account after that, as resolveSecretFile says.
  // / The web server account on the command line holds a per user secret for -v, so the expectation follows the name rather than the account.
  $secretShown = isset($SecretFile) ? (string)$SecretFile : '';
  $secretExpected = (substr(basename($secretShown), -11) === '-secret.php') ? '0640' : '0600';
  // / The install secret's group is judged too, by number, because a copy that is root:root 0640 is one the web server account cannot read.
  // / It is not judged when the group cannot be looked up.
  if ($secretExpected === '0640') {
    $olderSecretFile = olderInstallSecretFile($secretShown);
    list ($webAccountNumber, $secretGroupExpected) = webServerAccountNumbers(); }
  if ($olderSecretFile !== '' && !file_exists($secretShown) && file_exists($olderSecretFile)) $secretShown = $olderSecretFile;
  if ($secretShown !== '' && file_exists($secretShown)) {
    $secretMode = substr(sprintf('%o', fileperms($secretShown)), -4);
    $secretOwner = (int)fileowner($secretShown);
    $secretGroup = (int)filegroup($secretShown); }
  $secretPasses = ($secretMode === $secretExpected && ($secretExpected !== '0640' or ($secretOwner === 0 && ($secretGroupExpected < 0 or $secretGroup === $secretGroupExpected))));
  // / The copy at the top carries the same key as the copy in Secrets, so a copy there another
  // / account may read gives the key away just the same, & it is judged too when that one passes.
  if ($olderSecretFile !== '' && $secretShown !== $olderSecretFile && $secretPasses && file_exists($olderSecretFile)) {
    $topCopyMode = substr(sprintf('%o', fileperms($olderSecretFile)), -4);
    $topCopyOwner = (int)fileowner($olderSecretFile);
    $topCopyGroup = (int)filegroup($olderSecretFile);
    if ($topCopyMode !== $secretExpected or $topCopyOwner !== 0 or ($secretGroupExpected >= 0 && $topCopyGroup !== $secretGroupExpected)) {
      $secretShown = $olderSecretFile;
      $secretMode = $topCopyMode;
      $secretOwner = $topCopyOwner;
      $secretGroup = $topCopyGroup;
      $secretPasses = FALSE; } }
  list ($secretsState, $secretsMode, $secretsOwnership) = secretFolderStatus(isset($SecretLoc) ? (string)$SecretLoc : '');
  if (isset($ManagerSocketDir) && $ManagerSocketDir !== '' && is_dir($ManagerSocketDir)) $socketMode = substr(sprintf('%o', fileperms($ManagerSocketDir)), -4);

  // / The environment every conversion runs in.
  // / The row is named for the secret it judges, because an account that is neither root nor the web server's holds a secret of its own.
  $secretLabel = ($secretExpected === '0640') ? 'Install secret' : 'Per user secret';
  print($Lol.'Environment'.$Lol);
  if ($secretMode === '') print('  '.str_pad($secretLabel, 28).'NOT PRESENT, '.($secretExpected === '0640' ? 'created by root\'s first command' : 'created by this account\'s first command').$Lol);
  else if (!$secretPasses) {
    $failureCount++;
    print('  '.str_pad($secretLabel, 28).'FAILED, '.($secretShown === $olderSecretFile ? 'the copy at the top of the data location, ' : '').'mode '.$secretMode.($secretOwner === 0 ? ', root' : ', not root').(($secretExpected === '0640' && $secretGroupExpected >= 0) ? ($secretGroup === $secretGroupExpected ? ', group '.$ApacheUser : ', group not '.$ApacheUser) : '').', expected '.$secretExpected.($secretExpected === '0640' ? ', root'.($secretGroupExpected >= 0 ? ' & group '.$ApacheUser : '') : '').'. Run -fp as root'.$Lol); }
  else print('  '.str_pad($secretLabel, 28).'OK, '.$secretExpected.($secretExpected === '0640' ? ', root' : '').($secretShown === $olderSecretFile ? ', at the top of the data location until -fp copies it into Secrets' : '').$Lol);
  if ($secretsState === 'missing') print('  '.str_pad('Secrets folder', 28).'NOT PRESENT, -fp creates it'.$Lol);
  else if ($secretsState === 'not a folder') {
    $failureCount++;
    print('  '.str_pad('Secrets folder', 28).'FAILED, not a folder & never followed. Remove it & run -fp as root'.$Lol); }
  else if ($secretsState === 'unjudged') {
    $failureCount++;
    print('  '.str_pad('Secrets folder', 28).'FAILED, '.$secretsOwnership.' '.$secretsMode.', & the group '.$ApacheUser.' could not be looked up'.$Lol); }
  else if ($secretsState !== 'ok') {
    $failureCount++;
    print('  '.str_pad('Secrets folder', 28).'FAILED, '.$secretsOwnership.' '.$secretsMode.', expected root:'.$ApacheUser.' 0750. Run -fp as root'.$Lol); }
  else print('  '.str_pad('Secrets folder', 28).'OK, 0750, root:'.$ApacheUser.$Lol);
  if ($socketMode === '') print('  '.str_pad('Manager socket directory', 28).'NOT PRESENT, created with the listener'.$Lol);
  else if ($socketMode !== '0700') {
    $failureCount++;
    print('  '.str_pad('Manager socket directory', 28).'FAILED, mode '.$socketMode.', expected 0700. Run -fp as root'.$Lol); }
  else print('  '.str_pad('Manager socket directory', 28).'OK, 0700'.$Lol);
  // / Where the application runs from & what the web server serves.
  // / The application is installed outside the web root, & the hosted location holds its loader.
  // / A copy running from outside the web root is a release unpacked to be installed, & is not served.
  // / The Engine judges that when it has loaded, & a copy without one is judged against the hosted location alone.
  if ((string)@realpath($InstLoc) === (string)@realpath($ApplicationLoc)) print('  '.str_pad('Installation', 28).'OK, '.$InstLoc.$Lol);
  else {
    $failureCount++;
    $treeIsServed = (function_exists('pathHoldsPath') ? (pathHoldsPath($HostedLoc, $InstLoc) or (trim((string)$ServerRootDir) !== '' && pathHoldsPath($ServerRootDir, $InstLoc))) : ((string)@realpath($InstLoc) === (string)@realpath($HostedLoc)));
    if ($treeIsServed) print('  '.str_pad('Installation', 28).'NOT MOVED, runs from '.$InstLoc.', which the web server serves. Run -fp as root'.$Lol);
    else print('  '.str_pad('Installation', 28).'NOT INSTALLED, runs from '.$InstLoc.', which is not the installed copy. Run -fp as root'.$Lol); }
  // / Setup Core is an Engine core & calls the Engine's own functions, so without an Engine it is not loaded at all.
  if ((string)@realpath($InstLoc) === (string)@realpath($ApplicationLoc)) {
    if (isset($EngineActive) && $EngineActive) list ($setupIsAvailable, $setupVersion) = verifyCoreComponent('Setup Core', 'Engine'.$DirSep.'Cores'.$DirSep.'setupCore.php', 'SetupCoreVersion', $RequiredSetupCoreVersion);
    if ($setupIsAvailable && function_exists('publishHostedLocation')) list ($hostedStatus, $hostedDetail, $hostedWritten, $hostedRemoved) = publishHostedLocation($HostedLoc, $InstLoc, FALSE);
    else {
      $hostedStatus = 'UNKNOWN';
      $hostedDetail = ((isset($EngineActive) && $EngineActive) ? 'The Setup Core is unavailable' : 'The Engine is unavailable').'.'; }
    // / A location in step that names what a release left beside it is kept, which is not a failure.
    if ($hostedStatus !== 'ok' && $hostedStatus !== 'kept') $failureCount++;
    print('  '.str_pad('Hosted location', 28).strtoupper($hostedStatus).', '.$HostedLoc.'. '.$hostedDetail.$Lol); }
  print('  '.str_pad('Current user', 28).$CurrentUser.($RunningAsRoot ? ', running as root' : '').$Lol);
  print('  '.str_pad('Container detected', 28).($RunningInContainer ? 'YES' : 'NO').$Lol);
  print('  '.str_pad('Sandbox required', 28).(($RunningInContainer ? $RequireSandboxOnDocker : $RequireSandbox) ? 'YES' : 'NO').$Lol);

  // / The data directory. The exposure line is the only one here that is evidence.
  // / A protection file being present proves nothing, because a .htaccess is read only where
  // / AllowOverride permits it & is ignored in silence where it does not. The exposure
  // / result comes from asking the web server for a canary, not from reading the disk.
  $maintainHtaccess = TRUE;
  if (isset($MaintainHTAccess)) $maintainHtaccess = (bool)$MaintainHTAccess;
  $apacheConfigIsInstalled = (file_exists('/etc/apache2/conf-enabled/hrconvert2.conf') or file_exists('/etc/apache2/conf.d/hrconvert2.conf') or file_exists('/etc/httpd/conf.d/hrconvert2.conf'));
  // / Both checks are the Engine's, so without an Engine neither is made & each says so.
  if (function_exists('verifyDataExposure')) list ($dataIsProtected, $exposureStatus, $exposureDetail) = verifyDataExposure();
  else {
    $exposureStatus = 'unverified';
    $exposureDetail = 'The Engine is unavailable.'; }
  print($Lol.'Data directory'.$Lol);
  print('  '.str_pad('Location', 28).$ConvertTemp.$Lol);
  // / The data location itself is never served, because inside the document root its sessions, caches & logs could be fetched, so that is a failure.
  if (function_exists('misplacedDataRoots')) {
    $misplacedRoots = misplacedDataRoots();
    if (count($misplacedRoots) > 0) $failureCount++;
    print('  '.str_pad('Data location', 28).(count($misplacedRoots) === 0 ? 'OK, outside the document root' : 'MISPLACED, '.implode(' & ', $misplacedRoots).' inside '.$ServerRootDir.'. Move '.(count($misplacedRoots) === 1 ? 'it' : 'them').' outside the document root').$Lol); }
  else print('  '.str_pad('Data location', 28).'UNKNOWN, the Engine is unavailable'.$Lol);
  print('  '.str_pad('Maintain .htaccess', 28).($maintainHtaccess ? 'YES' : 'NO, disabled in config.php').$Lol);
  print('  '.str_pad('Apache configuration', 28).($apacheConfigIsInstalled ? 'INSTALLED' : 'NOT INSTALLED, run -fp as root').$Lol);
  print('  '.str_pad('Live exposure', 28).strtoupper($exposureStatus).', '.$exposureDetail.$Lol);

  // / Resource awareness & the listener it depends on.
  print($Lol.'Resource awareness'.$Lol);
  if (!$EnableResourceAwareness) print('  '.str_pad('Configured', 28).'DISABLED in config.php'.$Lol);
  else if (!$ResourceAwarenessActive) print('  '.str_pad('Component', 28).'DISABLED, the Engine is unavailable'.$Lol);
  else {
    // / The listener lives in the Engine, & reporting on it needs the Engine to ask.
    // / Only the web server account may read its socket folder & the state it writes, so every other account, root included, names --status rather than asking.
    // / Asking anyway reads nothing & reports a running listener as STOPPED.
    $listenerIsAsked = (!$RunningAsRoot && (string)$CurrentUser === (string)$ApacheUser);
    if (!$listenerIsAsked or !function_exists('reportListenerStatus')) { $listenerIsRunning = FALSE; $listenerStatus = array(); }
    else list ($listenerIsRunning, $listenerStatus) = reportListenerStatus();
    print('  '.str_pad('Component', 28).'OK, supplied by Engine '.ltrim((string)$EngineVersion, 'vV').$Lol);
    print('  '.str_pad('Enforced', 28).($RequireResourceAwareness ? 'YES' : 'NO').$Lol);
    print('  '.str_pad('Total budget', 28).((int)$TotalResourceBudget < 1 ? 'AUTO, from the processor count' : (int)$TotalResourceBudget.' cost units').$Lol);
    print('  '.str_pad('Reserved share', 28).(int)$ReserveResourcePercentage.'%'.$Lol);
    print('  '.str_pad('Concurrent worker limit', 28).((int)$MaxConcurrentWorkers < 1 ? 'NONE, the budget decides' : (int)$MaxConcurrentWorkers).$Lol);
    print('  '.str_pad('Maximum runtime', 28).(int)$MaxExpectedRuntime.'s'.$Lol);
    if ($RunningAsRoot) print('  '.str_pad('Listener', 28).'NOT ASKED, root does not read the listener state. --status asks '.$ApacheUser.'.'.$Lol);
    else if (!$listenerIsAsked) print('  '.str_pad('Listener', 28).'NOT ASKED, only '.$ApacheUser.' may read the listener state. Run --status as root.'.$Lol);
    else if (!function_exists('reportListenerStatus')) print('  '.str_pad('Listener', 28).'UNKNOWN, the Engine is unavailable'.$Lol);
    else if (!$listenerIsRunning) print('  '.str_pad('Listener', 28).'STOPPED, start it with -l'.$Lol);
    else {
      print('  '.str_pad('Listener', 28).'RUNNING as process '.$listenerStatus['CoreManagerPid'].$Lol);
      print('  '.str_pad('Subordinate managers', 28).count($listenerStatus['Subordinates']).' of 3'.$Lol);
      print('  '.str_pad('Tracked workers', 28).$listenerStatus['TrackedWorkers'].$Lol);
      if (isset($listenerStatus['Budget']['RemainingBudget'])) print('  '.str_pad('Remaining budget', 28).$listenerStatus['Budget']['RemainingBudget'].' of '.$listenerStatus['Budget']['TotalBudget'].$Lol); } }

  print($Lol.'Enabled conversion types'.$Lol);
  print('  '.implode(', ', $SupportedConversionTypes).$Lol);
  // / Only say what a failure means when there is one.
  if ($failureCount > 0) print($Lol.$failureCount.' check(s) failed. See Documentation/ERROR_DESCRIPTIONS.txt.'.$Lol);
  // / The updater in every release from v3.9.9 to v4.0.0 accepts a new installation only when its -v prints the words Core version.
  // / This line is what lets an installation of those releases update to this one with -u.
  print($Lol.'Core version '.$HRConvertVersion.' reported the above.'.$Lol);
  print($Lol);
  $VersionInfoDisplayed = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $listenerIsAsked, $secretLabel, $manifestEntry, $serviceEntry, $servicePath, $choiceIsPresent, $choiceVersion, $choiceStatus, $choiceOutput, $choicePath, $choiceOrigin, $sandboxLauncher, $treeIsServed, $secretShown, $olderSecretFile, $topCopyMode, $topCopyOwner, $secretGroup, $topCopyGroup, $secretGroupExpected, $webAccountNumber, $secretPasses, $secretsState, $secretsMode, $secretsOwnership, $pipelineKindCounts, $pipelineKindLabels, $pipelineKind, $pipelineKindCount, $pipelineRecord, $manifestVersion, $disabledFeatures, $disabledFeature, $misplacedRoots, $hostedStatus, $hostedDetail, $setupVersion, $hostedWritten, $hostedRemoved, $setupIsAvailable, $scriptIsValid, $scriptStatus, $scriptDisplayStatus, $scriptDetail, $modelsAreValid, $ocrToolsAreValid, $archiveToolsAreValid, $libreOfficeIsValid, $ffmpegBinary, $streamFfmpegBinary, $inkscapeBinary, $diaBinary, $scadBinary, $imageBinary, $assimpBinary, $meshlabBinary, $tesseractBinary, $pdftotextBinary, $sevenZipBinary, $rarBinary, $zipBinary, $tarBinary, $mkisofsBinary, $isoHybridBinary, $fileBinary, $probeBinary, $pdfInfoBinary, $bwrapBinary, $installedGui, $installedLang, $installedEndonym, $checkDir, $checkFile, $foundVersion, $langLine, $guiMatches, $langMatches, $langOk, $langTotal, $ebookBinary, $listenerStatus, $listenerIsRunning, $secretMode, $secretOwner, $secretExpected, $socketMode, $componentChecks, $componentPair, $componentName, $dependencyChecks, $dependencyState, $dependencyName, $subsystemChecks, $subsystemIsReady, $subsystemName, $failureCount, $maintainHtaccess, $apacheConfigIsInstalled, $dataIsProtected, $exposureStatus, $exposureDetail);
  return $VersionInfoDisplayed; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to display the list of supported command line arguments.
// / Called by the -h & --help command line arguments, & by any unrecognized argument.
// / This is a summary & deliberately not a manual. Everything HRConvert2 does is
// / documented at length in the Documentation folder, & this points there rather than
// / attempting to reproduce it.
function showHelpInfo() {
  // / Set variables.
  global $ApplicationName, $HRConvertVersion, $Lol;
  $HelpInfoDisplayed = FALSE;
  print($Lol);
  print($ApplicationName.' '.$HRConvertVersion.$Lol);
  print('A self hosted file conversion server.'.$Lol);
  print($Lol);
  print('Usage'.$Lol);
  print('  php convertCore.php [argument]'.$Lol);
  print($Lol);
  print('Arguments'.$Lol);
  print('  -v, --version               Display version & dependency information.'.$Lol);
  print('  -h, --help                  Display this message.'.$Lol);
  print('  -c, --clean                 Sweep expired sessions from both data locations.'.$Lol);
  print('  -u, --update                Update the application from the configured source.'.$Lol);
  print('  -l, --listen                Start the resource listener.'.$Lol);
  print('  --run-core-manager          Run the listener in the foreground. For a service unit.'.$Lol);
  print('  -k, --kill                  Stop the resource listener.'.$Lol);
  print('  --status                    Report listener & resource budget state.'.$Lol);
  print('  -fp, --fix-permissions      Correct ownership, permissions & policy files.'.$Lol);
  print('  --run-environment-manager   Run one Environment Manager pass. Root only.'.$Lol);
  print('                              It watches, & repairs only when config.php allows.'.$Lol);
  print('  --rotate-secret             Replace the install secret. Every session ends.'.$Lol);
  print('                              Asks first. -y answers in advance. Root only.'.$Lol);
  print('  --config                    Configure this installation without a text editor.'.$Lol);
  print('  --setup                     Install, check & audit dependencies.'.$Lol);
  print('  --workflow                  List the workflow library & what is wrong with any of it.'.$Lol);
  print('  --workflow --check=FILE     Check a workflow file against this installation.'.$Lol);
  print('  --workflow --export=ID      Print a workflow from the library as a file a page can import.'.$Lol);
  print('  --workflow --install=FILE   Check a workflow & install it. Root only.'.$Lol);
  print('  --workflow --remove=ID      Remove an installed workflow. Root only.'.$Lol);
  print('  --build --pins              Check every version pin against what ships.'.$Lol);
  print('  --build --pins --write      Rewrite every pin that differs. A developer step.'.$Lol);
  print($Lol);
  print('Listener targets'.$Lol);
  print('  -k                          Stop the listener & every manager it started.'.$Lol);
  print('  -k <worker-id>              End one worker by budget token or process id.'.$Lol);
  print('  --kill-all-workers          End EVERY tracked conversion in progress.'.$Lol);
  print('                              Every user mid conversion loses their files.'.$Lol);
  print('  --kill-every-worker         End EVERY PHP process owned by the web server user.'.$Lol);
  print('                              Other applications on this host lose every session.'.$Lol);
  print('  -y, --yes                   Skip the confirmation prompt on the two above.'.$Lol);
  print($Lol);
  print('Listener notes'.$Lol);
  print('  Listener commands require root or the web server user.'.$Lol);
  print('  -l requires --Enable Resource Awareness-- in config.php.'.$Lol);
  print('  Without a listener, conversions run without a resource budget.'.$Lol);
  print('  --Require Resource Awareness-- refuses them instead.'.$Lol);
  print($Lol);
  print('Clean targets'.$Lol);
  print('  Root or the web server user may sweep. Root hands the sweep to that user.'.$Lol);
  print('  -c                          Sweep using the configured Delete Threshold.'.$Lol);
  print('  -c=<minutes>                Sweep sessions older than that many minutes.'.$Lol);
  print('  -c=now                      Sweep EVERY session, including sessions in use.'.$Lol);
  print('                              A user mid conversion loses their files.'.$Lol);
  print($Lol);
  print('Update targets'.$Lol);
  print('  -u                          Install the target set in config.php.'.$Lol);
  print('  -u=latest                   Install the newest tagged release.'.$Lol);
  print('  -u=edge                     Install the current state of the master branch.'.$Lol);
  print('                              Not a release. Its version may match no release.'.$Lol);
  print('  -u=v#.#.#                   Install that tagged release, such as v3.6.8.'.$Lol);
  print('                              A partial version, such as v3.6, is refused.'.$Lol);
  print($Lol);
  print('Notes'.$Lol);
  print('  Command line & web requests are mutually exclusive.'.$Lol);
  print('  A command line run starts no session.'.$Lol);
  print('  -u requires --Enable Automatic Updates-- in config.php.'.$Lol);
  print('  -u keeps the previous installation & restores it if the new one fails validation.'.$Lol);
  print($Lol);
  print('Documentation'.$Lol);
  print('  Documentation/INSTALLATION_INSTRUCTIONS.txt  Installing & configuring a server.'.$Lol);
  print('  Documentation/ERROR_DESCRIPTIONS.txt         Every numbered error, its cause & its fix.'.$Lol);
  print('  Documentation/CREATING_GUIS.txt              Building & installing an interface.'.$Lol);
  print('  Documentation/CREATING_LANGUAGE_PACKS.txt    Translating the interface.'.$Lol);
  print('  Documentation/CODING_CONVENTIONS.txt         Conventions this codebase follows.'.$Lol);
  print('  Documentation/DOCKER_BUILD_INSTRUCTIONS.txt  Building the container image.'.$Lol);
  print('  Documentation/CHANGELOG.txt                  What changed & when.'.$Lol);
  print('  Documentation/DEVELOPMENT_NOTES.txt          Why each subsystem is built the way it is.'.$Lol);
  print($Lol);
  print('  https://github.com/zelon88/HRConvert2'.$Lol);
  print($Lol);
  $HelpInfoDisplayed = TRUE;
  return $HelpInfoDisplayed; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the command line & dispatch whatever it asks for.
// / Accepts nothing. Returns whether the command line was handled, the user type & the exit
// / status the command earned, in that order.
// / The status follows the common command line convention, so a script, cron or systemd can act on it.
// / 0 is success, 1 is a command that failed or was refused, & 2 is a command used wrongly, such as an argument this application does not recognize.
// / A report such as -v exits 0 when the report was produced, because validateInstallation runs -v & requires 0, & an optional missing dependency must not fail an update.
// / Success means the requested state holds afterwards, rather than that work was done.
// / Stopping a listener that was not running is success, as it is for systemctl stop.
// / This application recognizes its own arguments here, which are -v, -h, -u & anything beginning --app.
// / Every other command belongs to the Engine & is recognized by dispatchEngineCommand, which this asks before deciding an argument is a mistake.
// / A handled command line ends the request, & an unhandled one falls through to the web interface, as a request with no arguments does.
function parseCommandLine() {
  // / Set variables.
  global $EngineActive, $Lol, $RunningFromCLI, $RunningAsRoot, $CurrentUser, $EnableMemoryProtection, $EngineApplicationArgumentHandler, $DirSep, $RequiredSetupCoreVersion, $InstLoc, $ApplicationLoc;
  $CommandLineHandled = FALSE;
  $CommandLineStatus = 0;
  $UserType = 'web';
  $cliArgumentCount = 0;
  $cliArguments = $appArgumentParts = array();
  $cliCommand = $cliTarget = $cliSecondTarget = $cliWhoami = $applicationHandler = $appTarget = $appSecondTarget = '';
  $cliConfirmed = $cliVectorParsed = $engineCommandHandled = $cliSetupIsAvailable = $cliEngineIsMissing = $cliIsElsewhere = FALSE;
  $engineCommandStatus = 0;
  $cliSetupVersion = '';
  // / A web request has no command line & must return immediately.
  // / This is the ONLY path that returns FALSE. Every other path handles & stops.
  if (!$RunningFromCLI) $CommandLineHandled = FALSE;
  else {
    // / The vector is read into its parts by parseArgumentVector, which is kernel rather than Engine.
    // / It is called directly & unguarded, because the kernel is loaded whenever this file is running.
    // / Everything below this point is dispatch, which is this application's own vocabulary.
    list ($cliArguments, $cliArgumentCount, $cliCommand, $cliTarget, $cliSecondTarget, $cliConfirmed, $cliVectorParsed) = parseArgumentVector(isset($_SERVER['argv']) ? $_SERVER['argv'] : array());
    // / Consume the check. A vector that could not be read at all is not a command line, &
    // / the no argument help path below is what an operator sees instead.
    if (!$cliVectorParsed) warningEntry('The command line argument vector could not be read & no command was dispatched.');
    // / The name of whoever ran this, for the one refusal this application still words, -u.
    $cliWhoami = ($CurrentUser === '' ? 'an unidentified user' : $CurrentUser);
    // / A command line invocation with no argument is a request for help.
    if ($cliArgumentCount < 1) {
      logEntry('Command line invocation with no argument. Displaying help.');
      showHelpInfo();
      $CommandLineHandled = TRUE; }
    // / A command line served without an Engine serves only what needs no Engine.
    // / Every command the Engine owns, & -u, reach into the Engine for managers,
    // / dependencies, configuration or cleaning. Calling into a component that is not loaded produces an
    // / undefined function, which tells an operator nothing & leaves no log line behind.
    // / Refusing here produces a sentence they can act on instead, & the refusal is logged.
    // / -v & -h are still served, deliberately. -v is the thing that REPORTS a broken Engine
    // / & an operator who cannot run it has no way to find out what is wrong. An argument
    // / beginning --app is served too, because whether it needs the Engine is the
    // / application's business rather than this core's.
    else if (!$EngineActive and $cliCommand !== '-v' and $cliCommand !== '--version' and $cliCommand !== '-h' and $cliCommand !== '--help' and strpos($cliCommand, '--app') !== 0) {
      warningEntry('The command '.$cliCommand.' was refused because the Engine is unavailable.');
      $CommandLineStatus = 1;
      print($Lol.'This command needs the Engine, which could not be loaded.'.$Lol);
      // / A drop-in unpacked beside the loader holds no Engine where its release left the Engine as it was.
      // / A drop-in carries only the files that changed, & once the application is installed the folder the web server serves holds the loader & little else.
      // / The remedy is to unpack it where the installation is, & a new Engine would only lead to -fp saying so.
      $cliEngineIsMissing = !is_file($InstLoc.$DirSep.'Resources'.$DirSep.'Engine'.$DirSep.'engine.php');
      $cliIsElsewhere = ((string)@realpath($InstLoc) !== (string)@realpath((string)$ApplicationLoc) && is_file((string)$ApplicationLoc.$DirSep.'convertCore.php'));
      if ($cliEngineIsMissing && $cliIsElsewhere) {
        print('This folder holds no Engine. The installation is '.$ApplicationLoc.'.'.$Lol);
        print('Unpack a drop-in into '.$ApplicationLoc.' & run -fp there.'.$Lol.$Lol); }
      else {
        print('Run  php convertCore.php -v  to see which component failed.'.$Lol);
        // / The remedy is named because there is no command that applies it. Every repair path
        // / reaches into the Engine as well, so an operator who goes looking for one is hunting
        // / something that does not exist. Measured rather than assumed: with a mismatched Engine,
        // / --config --repair, --setup --install-complete & --update all fail on an Engine call of
        // / their own. Telling them to run -v & stopping there sent them in a circle.
        print('A mismatched Engine is a partial update. Replace Resources/Engine/engine.php'.$Lol);
        print('with the copy from this core\'s release. Every repair command needs the Engine.'.$Lol.$Lol); }
      $CommandLineHandled = TRUE;
      $UserType = 'cli'; }
    else {
      // / The command & both targets were resolved by the parser above, & are not resolved again here, because two places deciding what the first argument means come to disagree.
      // / The application's namespace is checked first, before any engine argument.
      // / Everything beginning --app belongs to the application, so the Engine never has to know what the application accepts & no argument of either can collide.
      // / It is checked here, ahead of the Engine's dispatcher, so that no command the Engine adds later can claim it.
      // / It is a prefix rather than a registry of names, because a registry means asking the application what it accepts before an unknown argument can be reported.
      // / The handler is named by $EngineApplicationArgumentHandler, which the Engine configuration & the contract both set.
      // / Its values are handed over as typed, because parseArgumentVector lowercases every target, & an application's own may be paths.
      // / They are read again from the raw vector, & the command stays lowercased, because it is a name matched the way every command is.
      if (strpos($cliCommand, '--app') === 0) {
        $applicationHandler = (isset($EngineApplicationArgumentHandler) && is_string($EngineApplicationArgumentHandler)) ? trim($EngineApplicationArgumentHandler) : '';
        if ($applicationHandler !== '' && function_exists($applicationHandler)) {
          logEntry('The application argument '.$cliCommand.' was handed to '.$applicationHandler.'.');
          $appArgumentParts = explode('=', trim((string)$cliArguments[0]), 2);
          $appTarget = isset($appArgumentParts[1]) ? trim($appArgumentParts[1]) : (isset($cliArguments[1]) ? trim((string)$cliArguments[1]) : '');
          $appSecondTarget = isset($cliArguments[2]) ? trim((string)$cliArguments[2]) : '';
          $applicationHandler($cliCommand, $appTarget, $appSecondTarget); }
        else {
          warningEntry('The application argument '.$cliCommand.' was supplied & this application declares no handler.');
          $CommandLineStatus = 2;
          print($Lol.'This application accepts no '.$cliCommand.' command.'.$Lol);
          print('Arguments beginning --app belong to the application & this one declares none.'.$Lol.$Lol); }
        $CommandLineHandled = TRUE;
        $UserType = 'cli'; }
      // / Handle the -v or --version arguments.
      // / This is an else if, so an --app argument ends the chain.
      else if ($cliCommand === '-v' or $cliCommand === '--version') {
        logEntry('Command line invocation. Displaying version information.');
        showVersionInfo();
        $CommandLineHandled = TRUE; }
      // / Handle the -h or --help arguments.
      else if ($cliCommand === '-h' or $cliCommand === '--help') {
        logEntry('Command line invocation. Displaying help.');
        showHelpInfo();
        $CommandLineHandled = TRUE; }
      // / Every other command is offered to the Engine first.
      // / The listener, the managers, setup, configuration, the pipelines, repair & cleaning are the Engine's, & every application of this shape needs them.
      // / What comes back unhandled is this application's own, which is -u, or a mistake.
      // / The gate above already refused all of this when there is no Engine to ask.
      else {
        list ($engineCommandHandled, $engineCommandStatus) = dispatchEngineCommand($cliArguments, $cliCommand, $cliTarget, $cliSecondTarget, $cliConfirmed);
        if ($engineCommandHandled) $CommandLineStatus = (int)$engineCommandStatus;
        // / -u is still answered here, & it is root only.
        // / The updater itself is Setup Core's, loaded only for the command that needs it, as -fp & --setup load it.
        else if (($cliCommand === '-u' or $cliCommand === '--update') && !$RunningAsRoot) $CommandLineStatus = refuseCommandForUser($cliCommand, $cliWhoami, 'A root only command was refused for an unauthorized user.');
        else if ($cliCommand === '-u' or $cliCommand === '--update') {
          logEntry('Command line invocation. Performing an application update.');
          list ($cliSetupIsAvailable, $cliSetupVersion) = verifyCoreComponent('Setup Core', 'Engine'.$DirSep.'Cores'.$DirSep.'setupCore.php', 'SetupCoreVersion', $RequiredSetupCoreVersion);
          if (!$cliSetupIsAvailable or !function_exists('updateApplication')) {
            $CommandLineStatus = 1;
            warningEntry('The Setup Core is unavailable, so no update was attempted.');
            print($Lol.'The Setup Core is unavailable, so no update was attempted. Run -v to see why.'.$Lol.$Lol); }
          else if (!updateApplication(strtolower($cliTarget))) $CommandLineStatus = 1; }
        // / The install secret is this application's until an authentication manager owns it,
        // / so rotating it is this application's command. It is root only, like -u.
        else if ($cliCommand === '--rotate-secret' && !$RunningAsRoot) $CommandLineStatus = refuseCommandForUser($cliCommand, $cliWhoami, 'A root only command was refused for an unauthorized user.');
        else if ($cliCommand === '--rotate-secret') {
          warningEntry('Command line invocation. Rotating the install secret.');
          if (!rotateInstallSecret($cliConfirmed)) $CommandLineStatus = 1; }
        // / An unrecognized argument is a mistake, not a web request.
        // / An option belonging to one of the Engine's commands is named with its owner, from
        // / the Engine's own map, because an option typed alone is the commonest mistake there is.
        else {
          warningEntry('Command line invocation with an unrecognized argument.');
          $CommandLineStatus = 2;
          reportUnrecognizedArgument($cliCommand, engineSubOptionOwners()); }
        $CommandLineHandled = TRUE; } } }
  // / Determine if the user is using the application via command line (CLI) or Apache+PHP through a web browser.
  if ($CommandLineHandled === TRUE) $UserType = 'cli';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cliArgumentCount, $cliArguments, $appArgumentParts, $cliCommand, $cliTarget, $cliSecondTarget, $cliWhoami, $applicationHandler, $appTarget, $appSecondTarget, $cliConfirmed, $cliVectorParsed, $engineCommandHandled, $engineCommandStatus, $cliSetupIsAvailable, $cliSetupVersion, $cliEngineIsMissing, $cliIsElsewhere);
  return array($CommandLineHandled, $UserType, $CommandLineStatus); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to explain an argument this core does not recognize.
// / Accepts the argument that was typed & the map of options to their owning command.
// / Returns TRUE when the argument was an option belonging to a command.
// / An option typed on its own is the most common command line mistake there is, & telling
// / an operator only that it is unrecognized sends them looking for a typo that is not
// / there. Naming the command it belongs to answers the question instead of restating it.
function reportUnrecognizedArgument($cliCommand, $subOptionOwners) {
  // / Set variables.
  global $Lol, $EnableMemoryProtection;
  $ArgumentWasAnOption = FALSE;
  $owningCommand = '';
  if (isset($subOptionOwners[$cliCommand])) {
    $owningCommand = (string)$subOptionOwners[$cliCommand];
    $ArgumentWasAnOption = TRUE;
    print($Lol.$cliCommand.' is an option of '.$owningCommand.', not a command of its own.'.$Lol);
    print($Lol.'  sudo php convertCore.php '.$owningCommand.' '.$cliCommand.$Lol);
    print($Lol.'Run '.$owningCommand.' on its own to list every option it accepts.'.$Lol.$Lol); }
  else {
    print($Lol.'Unrecognized argument.'.$Lol);
    showHelpInfo(); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $owningCommand, $cliCommand, $subOptionOwners);
  return $ArgumentWasAnOption; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to load the Files pipeline, which does the work on disk for an upload, a
// / delete & a file listing.
// / Accepts nothing. Returns a readiness boolean & the entry point name, in that order.
// / The Pipeline Core is what loads a pipeline, & loadPipelineCore does not exist at all when
// / the Pipeline Core is absent or mismatched. Calling it regardless was a fatal error on
// / every page, because the page lists files, & an installation without the component must
// / still be served. Every caller already reports a Files pipeline that is not ready.
function loadFilesPipeline() {
  // / Set variables.
  $FilesPipelineIsReady = FALSE;
  $FilesEntryPoint = '';
  if (function_exists('loadPipelineCore')) list ($FilesPipelineIsReady, $FilesEntryPoint) = loadPipelineCore('Files');
  return array($FilesPipelineIsReady, $FilesEntryPoint); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether an extension is one this application refuses to touch.
// / Accepts an extension as getExtension() returns it. Returns TRUE when it is dangerous.
// / getExtension() returns an extension with no leading dot & $DangerousFiles holds dotted
// / extensions alongside bare filenames, so a test of one spelling never matched the other.
// / $Allowed happens to exclude every dangerous format, which was the only reason that was
// / not a hole. Both spellings are tested here, once, for every caller.
// / An empty extension matches the NULL entry, so a file with no extension is dangerous.
function extensionIsDangerous($extension) {
  // / Set variables.
  global $DangerousFiles, $EnableMemoryProtection;
  $ExtensionIsDangerous = FALSE;
  $lowercaseExtension = strtolower((string)$extension);
  if (in_array($lowercaseExtension, $DangerousFiles) or in_array('.'.$lowercaseExtension, $DangerousFiles)) $ExtensionIsDangerous = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $extension, $lowercaseExtension);
  return $ExtensionIsDangerous; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to list the files in a directory that this application is willing to show.
// / Accepts the directory. Returns the names of the files, leaving out every dangerous one.
function getFiles($pathToFiles) {
  // / Set variables.
  global $DangerousFiles, $Pipelines, $EnableMemoryProtection;
  $Files = array();
  $listedNames = array();
  $directoryWasRead = FALSE;
  $candidateName = $candidateExtension = '';
  $candidateIsDangerous = FALSE;
  $filesPipelineIsReady = FALSE;
  $filesEntryPoint = '';
  // / Listing is the component & filtering is the policy.
  // / The Files pipeline reads the directory & returns plain names, & which names this application shows is $DangerousFiles, which an engine cannot guess.
  // / The pipeline omits directories & the dot entries, so nothing here has to.
  // / A pipeline's code is loaded on demand & is not loaded by enumeration.
  // / Enumeration reads pipelineConfig.php, & the functions live in pipeline.php, which loadPipelineCore requires.
  // / So function_exists is asked only after loading, because without that a page render finds no lister & the file list comes back empty.
  // / loadPipelineCore requires once, so asking every time costs nothing.
  list ($filesPipelineIsReady, $filesEntryPoint) = loadFilesPipeline();
  if ($filesPipelineIsReady && function_exists('listFilesInDirectory')) list ($directoryWasRead, $listedNames) = listFilesInDirectory($pathToFiles);
  else warningEntry('The Files pipeline could not be loaded, so no file list could be built.');
  if ($directoryWasRead) foreach ($listedNames as $candidateName) {
    $candidateExtension = getExtension($candidateName);
    $candidateIsDangerous = FALSE;
    if (extensionIsDangerous($candidateExtension)) $candidateIsDangerous = TRUE;
    if (in_array(strtolower($candidateName), $DangerousFiles)) $candidateIsDangerous = TRUE;
    if (!$candidateIsDangerous) array_push($Files, $candidateName); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $filesPipelineIsReady, $filesEntryPoint, $pathToFiles, $listedNames, $directoryWasRead, $candidateName, $candidateExtension, $candidateIsDangerous);
  return $Files; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether a sort code is one this application understands.
// / Accepts the code. Returns TRUE when it is.
// / A code is two digits. The first says what to sort by, 1 the name, 2 the size, 3 the time it changed & 4 the type.
// / The second says which way, 1 ascending & 2 descending.
// / It is a number rather than a word, because it rides in every link an interface draws & says nothing to anybody else.
// / Nothing about it is secret, so it is checked against what it may be rather than encrypted.
function sortCodeIsValid($sortCode) {
  // / Set variables.
  global $EnableMemoryProtection;
  $CodeIsValid = FALSE;
  $CodeIsValid = ((is_string($sortCode) or is_int($sortCode)) && preg_match('/^[1-4][12]$/', (string)$sortCode) === 1);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $sortCode);
  return $CodeIsValid; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to set the order the session's files are listed in.
// / Accepts the sort code asked for. Returns the code in force.
// / No code asked for is the default, by name ascending.
// / A code this application does not understand falls back to the default & says so.
// / The default is a local, so nothing a request carries can leave the list without an order.
function verifySort($requestedSort) {
  // / Set variables.
  global $EnableMemoryProtection;
  $SortToUse = '';
  $defaultSort = '11';
  if ((string)$requestedSort === '') $SortToUse = $defaultSort;
  else if (sortCodeIsValid($requestedSort)) $SortToUse = (string)$requestedSort;
  else {
    $SortToUse = $defaultSort;
    warningEntry('A sort order this application does not understand was asked for, so the files are listed by name.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $defaultSort, $requestedSort);
  return $SortToUse; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to put the session's files in the order asked for & describe each one.
// / Accepts the session folder, the names of the files in it & the sort code, in that order.
// / Returns the names in order & a record for each, keyed by name, in that order.
// / A record holds Size in bytes, SizeText, Modified as a Unix time, ModifiedText, Type, which is the extension, & Kinds.
// / Kinds lists the kinds of file applicationFileTypes() says it is, which is how an interface ticks the files of one kind.
// / A file can be more than one kind, as an rtf file is a document & an e-book.
// / The Files pipeline reads the sizes & the times, because it is what touches the session folder.
// / A file it could not describe keeps its place in the list, sorted as if it were empty & oldest, & shows no size or time.
// / Ties are broken by name, so the order never depends on the order the folder was read in.
function arrangeFiles($sessionFolder, $fileNames, $sortCode) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ArrangedFiles = $FileDetails = array();
  $describedFiles = $sortKeys = $fileTypes = $fileKinds = $typeExtensions = array();
  $descriptionsWereRead = $fileWasDescribed = FALSE;
  $fileName = $sortField = $typeName = '';
  $sortDirection = SORT_ASC;
  $sortFlags = SORT_NATURAL | SORT_FLAG_CASE;
  $fileSize = $fileTime = 0;
  if (function_exists('describeFilesInDirectory')) list ($descriptionsWereRead, $describedFiles) = describeFilesInDirectory($sessionFolder, $fileNames);
  if (!$descriptionsWereRead && !empty($fileNames)) warningEntry('The files in this session could not be described, so the list shows no sizes or times.');
  $sortField = sortCodeIsValid($sortCode) ? substr((string)$sortCode, 0, 1) : '1';
  if (sortCodeIsValid($sortCode) && substr((string)$sortCode, 1, 1) === '2') $sortDirection = SORT_DESC;
  if ($sortField === '2' or $sortField === '3') $sortFlags = SORT_NUMERIC;
  if ($sortField === '4') $sortFlags = SORT_STRING | SORT_FLAG_CASE;
  $fileTypes = applicationFileTypes();
  foreach ((array)$fileNames as $fileName) {
    redeclare($fileKinds, array());
    foreach ($fileTypes as $typeName => $typeExtensions) if (in_array(strtolower(getExtension($fileName)), $typeExtensions, TRUE)) $fileKinds[] = $typeName;
    $fileWasDescribed = isset($describedFiles[$fileName]);
    $fileSize = $fileWasDescribed ? (int)$describedFiles[$fileName]['Size'] : 0;
    $fileTime = $fileWasDescribed ? (int)$describedFiles[$fileName]['Modified'] : 0;
    $FileDetails[$fileName] = array('Size' => $fileSize, 'SizeText' => $fileWasDescribed ? describeByteCount($fileSize) : '', 'Modified' => $fileTime, 'ModifiedText' => $fileWasDescribed ? date('Y-m-d H:i', $fileTime) : '', 'Type' => getExtension($fileName), 'Kinds' => $fileKinds);
    $ArrangedFiles[] = $fileName;
    if ($sortField === '2') $sortKeys[] = $fileSize;
    else if ($sortField === '3') $sortKeys[] = $fileTime;
    else if ($sortField === '4') $sortKeys[] = $FileDetails[$fileName]['Type'];
    else $sortKeys[] = $fileName; }
  // / The keys are sorted in the direction asked for, & the names ascending within a tie, & the names follow both.
  if (!empty($ArrangedFiles)) array_multisort($sortKeys, $sortDirection, $sortFlags, $ArrangedFiles, SORT_ASC, SORT_NATURAL | SORT_FLAG_CASE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $describedFiles, $sortKeys, $fileTypes, $fileKinds, $typeExtensions, $descriptionsWereRead, $fileWasDescribed, $fileName, $sortField, $typeName, $sortDirection, $sortFlags, $fileSize, $fileTime, $sessionFolder, $fileNames, $sortCode);
  return array($ArrangedFiles, $FileDetails); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write what an Identify operation produced as the reply an interface reads.
// / Accepts the operation boolean, the errors boolean & what the operation produced, in that order.
// / Returns one line of JSON holding Complete, Errors, Report & Records, under the key Identify.
// / Every value a file or a tool supplied is plain text by now, & the encoding still escapes every character that means something in HTML.
// / An exclamation mark is escaped too, so a title a file carries can never spell the tag an interface reads as an error.
// / Invalid UTF-8 is substituted rather than refused, because a reply that will not encode would tell the user nothing.
function encodeIdentifyReply($identifyComplete, $identifyErrors, $identifyResult) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ReplyLine = '';
  $replyData = array();
  $encodedReply = '';
  $replyData = array('Identify' => array('Complete' => (bool)$identifyComplete, 'Errors' => (bool)$identifyErrors,
    'Report' => (is_array($identifyResult) && isset($identifyResult['Report'])) ? (string)$identifyResult['Report'] : '',
    'Records' => (is_array($identifyResult) && isset($identifyResult['Records']) && is_array($identifyResult['Records'])) ? array_values($identifyResult['Records']) : array()));
  $encodedReply = (string)json_encode($replyData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);
  $ReplyLine = str_replace('!', '\u0021', $encodedReply);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $replyData, $encodedReply, $identifyComplete, $identifyErrors, $identifyResult);
  return $ReplyLine; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the conversion families & the extensions each reads & writes.
// / Accepts nothing. Returns each family, keyed by name, as the list it reads & the list it writes, in that order.
// / The order is significant & Stream must remain last.
// / Every stream output format is also an ordinary audio or video format, so an mp3 to aac conversion belongs to Audio & to Stream both.
// / The first family to match wins, so Stream sits last & only ever claims a file no earlier family recognized.
// / A stream family needs both halves together, because a family matches only when the input & the output extension are both in it.
// / The two halves are kept apart everywhere else, so that detecting an m3u8 does not also claim every ordinary audio & video file.
// / convertFiles() resolves a conversion with it, & a workflow asks it which files a conversion reads, so the two cannot disagree.
function applicationConversionFamilies() {
  // / Set variables.
  global $DocumentArray, $SpreadsheetArray, $PresentationInputArray, $PresentationOutputArray, $XPSInputArray, $XPSOutputArray, $ImageInputArray, $ImageOutputArray, $ModelInputArray, $ModelOutputArray, $SCADInputArray, $SCADOutputArray, $SVGInputArray, $SVGOutputArray, $VideoInputArray, $VideoOutputArray, $SubtitleInputArray, $SubtitleOutputArray, $MediaInputArray, $MediaOutputArray, $ArchiveArray, $DrawingInputArray, $DrawingOutputArray, $EbookInputArray, $EbookOutputArray, $StreamArray, $StreamOutputArray;
  $ConversionFamilies = array(
    'Document' => array(array_merge($DocumentArray, $SpreadsheetArray, $PresentationInputArray, $XPSInputArray), array_merge($DocumentArray, $SpreadsheetArray, $PresentationOutputArray, $XPSOutputArray)),
    'Image' => array($ImageInputArray, $ImageOutputArray),
    'Model' => array($ModelInputArray, $ModelOutputArray),
    'Scad' => array($SCADInputArray, $SCADOutputArray),
    'SVG' => array($SVGInputArray, $SVGOutputArray),
    'Video' => array($VideoInputArray, $VideoOutputArray),
    'Subtitle' => array($SubtitleInputArray, $SubtitleOutputArray),
    'Audio' => array($MediaInputArray, $MediaOutputArray),
    'Archive' => array($ArchiveArray, $ArchiveArray),
    'Drawing' => array($DrawingInputArray, $DrawingOutputArray),
    'Ebook' => array($EbookInputArray, $EbookOutputArray),
    'Stream' => array(array_merge($StreamArray, $StreamOutputArray), array_merge($StreamArray, $StreamOutputArray)));
  return $ConversionFamilies; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the kinds of file this application reads, by the lists config.php keeps.
// / Accepts nothing. Returns each kind's name & the extensions it reads.
// / A workflow's filter names a kind, & an interface's select by type lists them.
// / A kind whose list is empty is left out, because its family is switched off.
function applicationFileTypes() {
  // / Set variables.
  global $DocumentArray, $XPSInputArray, $SpreadsheetArray, $PresentationInputArray, $ImageInputArray, $MediaInputArray, $VideoInputArray, $DearchiveArray, $EbookInputArray, $ModelInputArray, $DrawingInputArray, $SVGInputArray, $SubtitleInputArray, $SCADInputArray, $StreamArray, $EnableMemoryProtection;
  $FileTypes = array();
  $typeName = '';
  $typeExtensions = $typeLists = array();
  $typeLists = array('Document' => array_merge((array)$DocumentArray, (array)$XPSInputArray), 'Spreadsheet' => $SpreadsheetArray, 'Presentation' => $PresentationInputArray, 'Image' => $ImageInputArray,
    'Audio' => $MediaInputArray, 'Video' => $VideoInputArray, 'Archive' => $DearchiveArray, 'Ebook' => $EbookInputArray, 'Model' => $ModelInputArray, 'Drawing' => $DrawingInputArray,
    'Vector' => $SVGInputArray, 'Subtitle' => $SubtitleInputArray, 'Scad' => $SCADInputArray, 'Stream' => $StreamArray);
  foreach ($typeLists as $typeName => $typeExtensions) if (is_array($typeExtensions) && count($typeExtensions) > 0) $FileTypes[$typeName] = array_values(array_unique(array_map('strtolower', $typeExtensions)));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $typeName, $typeExtensions, $typeLists);
  return $FileTypes; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to declare the operations a workflow may run, & the kinds of file a step may name.
// / Accepts nothing. Returns an array holding Operations & FileTypes. It is $EngineOperationProvider.
// / Each operation names the function that runs it, the budget it takes, whether this server allows it, what it reads, its modes & its options.
// / Every one runs the code a button in an interface runs, so a workflow does nothing a user could not do by hand.
// / Convert decides what it reads from the extension it is asked to write, because a conversion to jpg reads an image & not a spreadsheet.
// / Archive's Filename names the one archive every file goes into, & an empty one archives each file under its own name.
// / SessionFiles names getFiles(), which lists a session's files as a page lists them, so the Workflow Core's Select step finds only what a user could see & tick.
// / See Documentation/ABOUT_WORKFLOWS.txt.
function applicationOperations() {
  // / Set variables.
  global $SupportedConversionTypes, $AllowUserIdentify, $AllowUserVirusScan, $ArchiveArray, $DocumentArray, $PDFWorkArr, $EnableMemoryProtection;
  $OperationRegistry = array();
  $convertOutputs = $archiveFormats = $ocrFormats = $familyFormats = array();
  $familyName = '';
  foreach (applicationConversionFamilies() as $familyName => $familyFormats) if (is_array($familyFormats[1])) redeclare($convertOutputs, array_merge($convertOutputs, $familyFormats[1]));
  redeclare($convertOutputs, array_values(array_unique(array_map('strtolower', $convertOutputs))));
  $archiveFormats = array_values(array_unique(array_merge(array('zip'), (array)$ArchiveArray)));
  $ocrFormats = array_values(array_unique(array_merge(array('pdf'), (array)$DocumentArray)));
  $OperationRegistry = array(
    'Operations' => array(
      'Convert' => array('Invoke' => 'invokeConvertOperation', 'Budget' => 'conversion', 'Enabled' => (count($convertOutputs) > 0), 'Accepts' => 'convertOperationAccepts', 'Modes' => array('each'),
        'Options' => array('Extension' => array('Type' => 'choice', 'Required' => TRUE, 'Choices' => $convertOutputs), 'Filename' => array('Type' => 'name', 'Default' => ''),
          'Height' => array('Type' => 'integer', 'Default' => 0, 'Minimum' => 0, 'Maximum' => 65535), 'Width' => array('Type' => 'integer', 'Default' => 0, 'Minimum' => 0, 'Maximum' => 65535),
          'Rotate' => array('Type' => 'integer', 'Default' => 0, 'Minimum' => -360, 'Maximum' => 360), 'Bitrate' => array('Type' => 'integer', 'Default' => 0, 'Minimum' => 0, 'Maximum' => 100000))),
      'Archive' => array('Invoke' => 'invokeArchiveOperation', 'Budget' => 'archive', 'Enabled' => in_array('Archive', (array)$SupportedConversionTypes, TRUE), 'Inputs' => array(), 'Modes' => array('together', 'each'),
        'Options' => array('Extension' => array('Type' => 'choice', 'Default' => 'zip', 'Choices' => $archiveFormats), 'Filename' => array('Type' => 'name', 'Default' => 'Workflow_Files'))),
      'OCR' => array('Invoke' => 'invokeOcrOperation', 'Budget' => 'OCR', 'Enabled' => (in_array('OCR', (array)$SupportedConversionTypes, TRUE) && in_array('Document', (array)$SupportedConversionTypes, TRUE)), 'Inputs' => (array)$PDFWorkArr, 'Modes' => array('each'),
        'Options' => array('Extension' => array('Type' => 'choice', 'Default' => 'pdf', 'Choices' => $ocrFormats), 'Method' => array('Type' => 'choice', 'Default' => '0', 'Choices' => array('0', '1', '2')), 'Filename' => array('Type' => 'name', 'Default' => ''))),
      'Identify' => array('Invoke' => 'invokeIdentifyOperation', 'Budget' => 'Identify', 'Enabled' => ($AllowUserIdentify === TRUE), 'Inputs' => array(), 'Modes' => array('together'),
        'Options' => array('Report' => array('Type' => 'boolean', 'Default' => TRUE), 'Filename' => array('Type' => 'name', 'Default' => 'User_Identify_Report'))),
      'Scan' => array('Invoke' => 'invokeScanOperation', 'Budget' => 'user virus scan', 'Enabled' => ($AllowUserVirusScan === TRUE), 'Inputs' => array(), 'Modes' => array('together'),
        'Options' => array('Scanner' => array('Type' => 'choice', 'Default' => 'all', 'Choices' => array('all', 'clamav', 'scancore'))))),
    'FileTypes' => applicationFileTypes(),
    'SessionFiles' => 'getFiles');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $convertOutputs, $archiveFormats, $ocrFormats, $familyFormats, $familyName);
  return $OperationRegistry; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether Convert reads a file, given the extension it is asked to write.
// / Accepts the file's extension & the step's options. Returns TRUE when a family reads the one & writes the other.
// / A file that is already the format asked for is not read, because there is nothing to convert.
// / It asks the lists convertFiles() asks, so a workflow leaves out a file the converter would refuse.
function convertOperationAccepts($fileExtension, $operationOptions) {
  // / Set variables.
  global $EnableMemoryProtection;
  $OperationAccepts = FALSE;
  $outputExtension = $inputExtension = $familyName = '';
  $familyFormats = array();
  $inputExtension = strtolower((string)$fileExtension);
  $outputExtension = (is_array($operationOptions) && isset($operationOptions['Extension'])) ? strtolower((string)$operationOptions['Extension']) : '';
  foreach (applicationConversionFamilies() as $familyName => $familyFormats) if (is_array($familyFormats[0]) && is_array($familyFormats[1]) && in_array($inputExtension, $familyFormats[0]) && in_array($outputExtension, $familyFormats[1])) $OperationAccepts = TRUE;
  if ($inputExtension === $outputExtension) $OperationAccepts = FALSE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $outputExtension, $inputExtension, $familyName, $familyFormats, $fileExtension, $operationOptions);
  return $OperationAccepts; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run Convert, as a button & a workflow both run it.
// / Accepts the files & the options, which are Extension, Filename, Height, Width, Rotate & Bitrate.
// / Returns whether any file converted, whether anything went wrong, the names it made & what it found, which is nothing, in that order.
// / A size, a rotation or a bitrate of 0 is passed on as none, which is what a page sends when a user leaves one alone.
function invokeConvertOperation($selectedFiles, $operationOptions) {
  // / Set variables.
  global $EnableMemoryProtection;
  $OperationSucceeded = $OperationErrors = FALSE;
  $ProducedFiles = $OperationFindings = array();
  $optionValues = array();
  $numericName = '';
  $optionValues = array('Filename' => (string)($operationOptions['Filename'] ?? ''), 'Extension' => strtolower((string)($operationOptions['Extension'] ?? '')));
  foreach (array('Height', 'Width', 'Rotate', 'Bitrate') as $numericName) $optionValues[$numericName] = (isset($operationOptions[$numericName]) && (string)$operationOptions[$numericName] !== '0') ? (string)$operationOptions[$numericName] : '';
  list ($OperationSucceeded, $OperationErrors, $ProducedFiles) = convertFiles($selectedFiles, $optionValues['Filename'], $optionValues['Extension'], $optionValues['Height'], $optionValues['Width'], $optionValues['Rotate'], $optionValues['Bitrate']);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $optionValues, $numericName, $selectedFiles, $operationOptions);
  return array($OperationSucceeded, $OperationErrors, $ProducedFiles, $OperationFindings); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run Archive, as a button & a workflow both run it.
// / Accepts the files & the options, which are Extension & Filename.
// / Returns whether any file was archived, whether anything went wrong, the archives it made & what it found, which is nothing, in that order.
// / A Filename puts every file into one archive of that name, & an empty one archives each file under its own name.
function invokeArchiveOperation($selectedFiles, $operationOptions) {
  // / Set variables.
  global $EnableMemoryProtection;
  $OperationSucceeded = $OperationErrors = FALSE;
  $ProducedFiles = $OperationFindings = array();
  $archivePath = '';
  list ($OperationSucceeded, $OperationErrors, $archivePath, $ProducedFiles) = archiveFiles($selectedFiles, (string)($operationOptions['Filename'] ?? ''), strtolower((string)($operationOptions['Extension'] ?? 'zip')));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $archivePath, $selectedFiles, $operationOptions);
  return array($OperationSucceeded, $OperationErrors, $ProducedFiles, $OperationFindings); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run OCR, as a button & a workflow both run it.
// / Accepts the files & the options, which are Extension, Method & Filename.
// / Returns whether any file was read, whether anything went wrong, the names it made & what it found, which is nothing, in that order.
// / OCR is an operation pipeline, & there is no built in fallback for it, so a missing OCR pipeline is reported rather than read as an OCR run that produced nothing.
function invokeOcrOperation($selectedFiles, $operationOptions) {
  // / Set variables.
  global $PipelineCoreActive, $EnableMemoryProtection;
  $OperationSucceeded = $OperationErrors = FALSE;
  $ProducedFiles = $OperationFindings = array();
  $operationOutput = array();
  if (!$PipelineCoreActive or !function_exists('runOperationPipeline')) {
    $OperationErrors = TRUE;
    errorEntry('OCR was requested but no OCR pipeline is installed & this core has no built in OCR!', 34006, FALSE); }
  else {
    list ($OperationSucceeded, $OperationErrors, $operationOutput) = runOperationPipeline('OCR', $selectedFiles, (string)($operationOptions['Filename'] ?? ''), strtolower((string)($operationOptions['Extension'] ?? 'pdf')), (string)($operationOptions['Method'] ?? '0'));
    if (is_array($operationOutput)) $ProducedFiles = array_values($operationOutput); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $operationOutput, $selectedFiles, $operationOptions);
  return array($OperationSucceeded, $OperationErrors, $ProducedFiles, $OperationFindings); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run Identify, as a button & a workflow both run it.
// / Accepts the files & the options, which are Report & Filename. Report is a boolean, or 1 as a page sends it.
// / Returns whether any file was identified, whether anything went wrong, the report it wrote if any & what it found, in that order.
// / What it found is Identify's result, holding Records & Report, which a button shows & a workflow passes over.
function invokeIdentifyOperation($selectedFiles, $operationOptions) {
  // / Set variables.
  global $PipelineCoreActive, $EnableMemoryProtection;
  $OperationSucceeded = $OperationErrors = FALSE;
  $ProducedFiles = $OperationFindings = array();
  if (!$PipelineCoreActive or !function_exists('runOperationPipeline')) {
    $OperationErrors = TRUE;
    errorEntry('Identify was requested but no Identify pipeline is installed!', 34006, FALSE); }
  else {
    list ($OperationSucceeded, $OperationErrors, $OperationFindings) = runOperationPipeline('Identify', $selectedFiles, (string)($operationOptions['Filename'] ?? ''), 'txt', $operationOptions['Report'] ?? FALSE);
    if (!is_array($OperationFindings)) $OperationFindings = array();
    if (isset($OperationFindings['Report']) && is_string($OperationFindings['Report']) && $OperationFindings['Report'] !== '') $ProducedFiles[] = $OperationFindings['Report']; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $selectedFiles, $operationOptions);
  return array($OperationSucceeded, $OperationErrors, $ProducedFiles, $OperationFindings); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to scan files for a workflow, & keep the report beside them.
// / Accepts the files & the options, which hold Scanner, all, clamav or scancore.
// / Returns whether a verdict was reached, whether anything went wrong, the report it kept & what it found, in that order.
// / What it found holds Halt as threat when any file is found infected, which ends a workflow, so nothing is made from an infected file.
// / A button's scan hands its report straight to the browser, & a workflow keeps it in the session instead, beside what it scanned.
// / The report is named User_Virus_Scan_Report.txt, or the next free name when that is taken.
function invokeScanOperation($selectedFiles, $operationOptions) {
  // / Set variables.
  global $ConvertDir, $EnableMemoryProtection;
  $OperationSucceeded = $OperationErrors = FALSE;
  $ProducedFiles = $OperationFindings = array();
  $threatWasFound = $nameWasFound = FALSE;
  $reportPath = $reportName = $keptPath = $keptName = '';
  list ($OperationSucceeded, $OperationErrors, $threatWasFound, $reportPath, $reportName) = userVirusScan($selectedFiles, strtolower((string)($operationOptions['Scanner'] ?? 'all')));
  if ($threatWasFound) $OperationFindings['Halt'] = 'threat';
  clearstatcache(TRUE, (string)$reportPath);
  if (is_file((string)$reportPath)) {
    list ($nameWasFound, $keptPath, $keptName) = array(TRUE, $ConvertDir.'User_Virus_Scan_Report.txt', 'User_Virus_Scan_Report.txt');
    if (file_exists($keptPath) or is_link($keptPath)) list ($nameWasFound, $keptPath, $keptName) = findFreeOutputName($ConvertDir, 'User_Virus_Scan_Report', 'txt');
    if ($nameWasFound && @copy((string)$reportPath, $keptPath)) $ProducedFiles[] = $keptName;
    else {
      $OperationErrors = TRUE;
      warningEntry('A workflow\'s virus scan report could not be kept beside the files it scanned.'); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $threatWasFound, $nameWasFound, $reportPath, $reportName, $keptPath, $keptName, $selectedFiles, $operationOptions);
  return array($OperationSucceeded, $OperationErrors, $ProducedFiles, $OperationFindings); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to adopt the session a workflow worker was handed.
// / Accepts nothing. Returns TRUE when a session was handed over & adopted.
// / A session is handed over as the two halves $SesHash3 joins, the day's folder & the session's, each 18 hexadecimal digits.
// / Anything else is refused, & the worker is left in the empty session it made for itself, so it finds no job & stops.
// / Nothing is trusted yet. The Workflow Core checks the worker's startup key, which is bound to this session & its job, before anything is acted on.
function adoptWorkflowSession() {
  // / Set variables.
  global $SesHash, $SesHash2, $SesHash3, $EnableMemoryProtection;
  $SessionWasAdopted = FALSE;
  $workerContext = '';
  $contextParts = array();
  if (function_exists('readTransportedWorkerContext')) $workerContext = readTransportedWorkerContext();
  if (preg_match('/^([a-f0-9]{18})\/([a-f0-9]{18})$/', $workerContext, $contextParts) === 1) {
    $SesHash = $contextParts[1];
    $SesHash2 = $contextParts[2];
    $SesHash3 = $SesHash.DIRECTORY_SEPARATOR.$SesHash2;
    $SessionWasAdopted = TRUE; }
  else warningEntry('A workflow worker was started without a session it can continue.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $workerContext, $contextParts);
  return $SessionWasAdopted; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to declare every sandbox profile this application uses.
// / Accepts nothing. Returns an array of profile name to a record describing it.
// /
// / This is CODE rather than configuration & it is never shown to an administrator.
// / Every entry below was arrived at by bisection rather than by reading documentation.
// / Documentation/Build/bisect.sh removes one permission at a time until a conversion
// / stops working, & what is left is the least permissive profile that still runs.
// / An administrator has nothing to decide here. A looser profile is less safe & a tighter
// / one does not work, so there is no third option worth offering anybody.
// /
// / The Engine reads this array & assembles the sandbox from it. It knows nothing about
// / LibreOffice or MeshLab & it does not need to, because a profile is data.
// / An application with different tools declares different profiles & the Engine is
// / unchanged. That is the whole reason this is an array rather than a run of branches.
// /
// / Every field is optional except OperationType. A profile that needs nothing declares
// / nothing, which is why ffmpeg, archive & poppler are one line each.
// /
// /   OperationType   What the per operation resource limit is looked up under.
// /   ReadOnlyPaths   Bound read only if present. A missing path is skipped rather than
// /                   refused, because a tool may be installed without its data.
// /   Tmpfs           An empty writable directory created inside the namespace.
// /   Environment     Set inside the namespace. NEVER prefixed onto the command, because
// /                   bwrap execs without a shell & would read NAME=value as a program.
// /   MapToRoot       Maps this process to root INSIDE the namespace & nowhere else.
// /                   An unprivileged user namespace, so it confers nothing on the host.
// /   Overlays        Host files a calling pipeline's own file covers inside the namespace,
// /                   keyed by the host path & naming the file within the read only resource
// /                   the call carries. The host file is never touched. A host path that is
// /                   not a plain file here, or that is reached through a link, is skipped.
// /   Reason          Why this profile is not empty. Read this before changing anything.
// /
// / Adding a profile.
// / Run bisect.sh against the conversion, add the entry, write the Reason & stop.
// / Nothing else in this application needs to know the profile exists.
function getSandboxProfiles() {
  // / Set variables.
  global $EnableMemoryProtection;
  $SandboxProfiles = array();
  $SandboxProfiles = array(

    // / LibreOffice starts a display server & probes OpenCL unless it is told not to.
    // / Both fail inside a namespace & both are slow before they fail.
    'libreoffice' => array(
      'OperationType' => 'Document',
      'ReadOnlyPaths' => array('/etc/libreoffice', '/etc/java'),
      'Environment' => array('SAL_USE_VCLPLUGIN' => 'svp', 'SAL_DISABLE_OPENCL' => '1'),
      'Reason' => 'Headless rendering with no display server & no OpenCL probing.'),

    // / Both major versions are bound, because a host may carry either.
    // / The delegate list & the rest of the configuration are the host's, & the policy is the calling pipeline's own, which covers the host's wherever a package or a build keeps one.
    'imagemagick' => array(
      'OperationType' => 'Image',
      'ReadOnlyPaths' => array('/etc/ImageMagick-7', '/etc/ImageMagick-6', '/usr/share/ImageMagick-7', '/usr/share/ImageMagick-6'),
      'Overlays' => array(
        '/etc/ImageMagick-7/policy.xml' => 'policy.xml',
        '/etc/ImageMagick-6/policy.xml' => 'policy.xml',
        '/usr/local/etc/ImageMagick-7/policy.xml' => 'policy.xml',
        '/usr/local/etc/ImageMagick-6/policy.xml' => 'policy.xml'),
      'Reason' => 'The delegate list is read from these directories, & the calling pipeline\'s policy covers the host\'s.'),

    // / Language data lives in one of two places depending on how it was installed.
    // / The OCR page split runs ImageMagick under this profile too, & carries its own policy. A
    // / build from source keeps its policy below /usr, which every sandbox shows, so the carried one
    // / covers each place a policy is kept, as it does for the imagemagick profile.
    'tesseract' => array(
      'OperationType' => 'OCR',
      'ReadOnlyPaths' => array('/usr/share/tesseract-ocr', '/usr/share/tessdata'),
      'Overlays' => array(
        '/etc/ImageMagick-7/policy.xml' => 'policy.xml',
        '/etc/ImageMagick-6/policy.xml' => 'policy.xml',
        '/usr/local/etc/ImageMagick-7/policy.xml' => 'policy.xml',
        '/usr/local/etc/ImageMagick-6/policy.xml' => 'policy.xml'),
      'Reason' => 'Recognition fails with no language data & the failure names no cause. The page split\'s policy covers the host\'s.'),

    'inkscape' => array(
      'OperationType' => 'SVG',
      'ReadOnlyPaths' => array('/usr/share/inkscape', '/etc/inkscape'),
      'Reason' => 'Templates & the default preferences are read from these directories.'),

    'dia' => array(
      'OperationType' => 'Drawing',
      'ReadOnlyPaths' => array('/usr/share/dia'),
      'Reason' => 'Shape libraries are read from this directory.'),

    'calibre' => array(
      'OperationType' => 'Ebook',
      'ReadOnlyPaths' => array('/usr/share/calibre'),
      'Reason' => 'Conversion recipes are read from this directory.'),

    // / This profile is the most complicated one here & every line of it was earned.
    // / meshlabserver links Qt & initializes GLEW whatever it has been asked to do, so a
    // / format conversion that renders nothing still requires an OpenGL context.
    // / The offscreen platform plugin starts Qt & supplies no GL, so an X server is needed.
    // / Xvfb will only create /tmp/.X11-unix as root & then checks that it is root owned,
    // / which is what MapToRoot & the Tmpfs below are for.
    // / The vendor file keeps glvnd away from a GPU driver that crashes while enumerating
    // / devices this namespace deliberately does not expose.
    'meshlab' => array(
      'OperationType' => 'Model',
      'ReadOnlyPaths' => array('/usr/share/meshlab', '/usr/share/pymeshlab'),
      'Tmpfs' => array('/tmp/.X11-unix'),
      'MapToRoot' => TRUE,
      'Environment' => array(
        'LIBGL_ALWAYS_SOFTWARE' => '1',
        'GALLIUM_DRIVER' => 'llvmpipe',
        '__EGL_VENDOR_LIBRARY_FILENAMES' => '/usr/share/glvnd/egl_vendor.d/50_mesa.json'),
      'Reason' => 'An X server inside the namespace, rendering in software, with no GPU driver loaded.'),

    'openscad' => array(
      'OperationType' => 'Scad',
      'ReadOnlyPaths' => array('/usr/share/openscad'),
      'Reason' => 'The standard library is read from this directory.'),

    // / clamscan cannot start without its signature database, & ClamAV 1.5 & later also refuses without the certificates it verifies that database against.
    // / Both are read only, & both are where every Debian & Ubuntu package puts them.
    'clamav' => array(
      'OperationType' => 'Scan',
      'ReadOnlyPaths' => array('/var/lib/clamav', '/etc/clamav'),
      'Reason' => 'The signature database & the certificates that verify it are read from these directories.'),

    // / ScanCore ships inside this application, so its directory is handed to the sandbox by
    // / the pipeline as a read only resource rather than named here.
    // / ScanCore is a PHP program, & PHP loads its extensions from the files under /etc/php.
    // / Without them ctype is missing & ScanCore dies loading its classifiers.
    'scancore' => array(
      'OperationType' => 'Scan',
      'ReadOnlyPaths' => array('/etc/php'),
      'Reason' => 'PHP reads the configuration that loads its extensions from this directory.'),

    // / Everything below needs nothing beyond the base sandbox.
    // / A profile that declares only its operation type is not an oversight.
    'ffmpeg' => array('OperationType' => 'Video'),
    'archive' => array('OperationType' => 'Archive'),
    'poppler' => array('OperationType' => 'Document'),
    'generic' => array('OperationType' => 'Generic'));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $SandboxProfiles is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection);
  return $SandboxProfiles; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to hold this utility's understanding of config.php.
// / Accepts no arguments.
// / Returns a multidimensional array of sections, each holding its variables.
// / The Engine reads it because engineConfig.php names it in $EngineConfigModelProvider.
// /
// / This array is the only block that changes when config.php changes.
// / Update it whenever $ConfigVersion is bumped, & without fail whenever $RequiredConfigVersion is bumped.
// / A variable it does not know is reported as unaccounted & can still be edited by hand, but cannot be reset or repaired.
// /
// / The section names must match config.php exactly, including any typo.
// / A section header in config.php is written as three dashes either side of the name.
// / Security Informations is spelled that way in the file & here, & correcting it here alone would orphan every setting in that section.
// /
// / Every default below was read from the configuration this release ships, so a reset restores exactly what a fresh installation would have had.
// /
// / Each variable carries the following.
// /   Type          bool, int, string, array, path, expression or version.
// /   Default       The value a reset writes. An empty string means there is no default.
// /   Depends       Another variable this one is meaningless without. Empty when free standing.
// /   Description   The label config.php gives it. The comment block is shown alongside.
// /
// / A section that is not writable is shown & never touched, because its contents are
// / order dependent & are not administrator tunable.
function applicationConfigModel() {
  // / Set variables.
  global $EnableMemoryProtection;
  $ConfigModel = array();
  $ConfigModel = array(
    // / $ConfigVersion is declared above the first section header, so it belongs to no
    // / section. It is modelled here as read only, so no reset & no edit touches it.
    // / An update's merge takes the new file's stamp, & a repair that completes the file raises it.
    'Unsectioned' => array('Writable' => FALSE, 'Variables' => array(
      'ConfigVersion' => array('Type' => 'version', 'Depends' => '', 'Description' => 'Config Version. Read only. Raised by an update & by a repair.'))),
    'General Information' => array('Writable' => TRUE, 'Variables' => array(
      'ApplicationName' => array('Type' => 'string', 'Depends' => '', 'Description' => 'Application Name String'),
      'ApplicationTitle' => array('Type' => 'string', 'Depends' => '', 'Description' => 'Application Title String'),
      'EnableAutoUpdates' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Enable Automatic Updates'),
      'AutoUpdateTargetVersion' => array('Type' => 'string', 'Depends' => 'EnableAutoUpdates', 'Description' => 'Automatic Update Target Version'),
      'UpdateSourceRepository' => array('Type' => 'string', 'Depends' => 'EnableAutoUpdates', 'Description' => 'Update Source Repository'),
      'MaxUpdatePackageSize' => array('Type' => 'int', 'Depends' => 'EnableAutoUpdates', 'Description' => 'Maximum Update Package Size'),
      'UpdateConnectionTimeout' => array('Type' => 'int', 'Depends' => 'EnableAutoUpdates', 'Description' => 'Update Connection Timeout'),
      'SupportedGuis' => array('Type' => 'array', 'Default' => 'array(\'Default\', \'Original\')', 'Depends' => '', 'Description' => 'Supported GUIs'),
      'DefaultGui' => array('Type' => 'string', 'Depends' => '', 'Description' => 'Default GUI'),
      'AllowUserSelectableGui' => array('Type' => 'bool', 'Depends' => 'SupportedGuis', 'Description' => 'Allow User Selectable GUI'),
      'SupportedLanguages' => array('Type' => 'array', 'Default' => 'array( \'en\' => \'English\',   \'fr\' => \'Français\',    \'es\' => \'Español\', \'zh\' => \'中文\',      \'hi\' => \'हिन्दी\',        \'ar\' => \'العربية\', \'ru\' => \'Русский\',   \'uk\' => \'Українська\',  \'bn\' => \'বাংলা\', \'de\' => \'Deutsch\',   \'ko\' => \'한국어\',        \'it\' => \'Italiano\', \'pt\' => \'Português\', \'vi\' => \'Tiếng Việt\',  \'tr\' => \'Türkçe\', \'ja\' => \'日本語\',     \'id\' => \'Bahasa Indonesia\', \'pl\' => \'Polski\',    \'nl\' => \'Nederlands\',  \'sw\' => \'Kiswahili\', \'my\' => \'မြန်မာ\',      \'ur\' => \'اردو\',         \'fa\' => \'فارسی\', \'he\' => \'עברית\',     \'aii\' => \'ܣܘܪܝܝܐ\',     \'arc\' => \'ܐܪܡܝܐ\')', 'Depends' => '', 'Description' => 'Supported Languages'),
      'DefaultLanguage' => array('Type' => 'string', 'Depends' => '', 'Description' => 'Default Language'),
      'AllowUserSelectableLanguage' => array('Type' => 'bool', 'Depends' => 'SupportedLanguages', 'Description' => 'Allow User Selectable Language'),
      'AllowUserShare' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'User Shareable File Links'),
      'AllowUserIdentify' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'User File Identification'),
      'EnableWorkflows' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Enable Workflows'),
      'EnableWorkflowBuilder' => array('Type' => 'bool', 'Depends' => 'EnableWorkflows', 'Description' => 'Enable Workflow Builder'),
      'EnableWorkflowImportExport' => array('Type' => 'bool', 'Depends' => 'EnableWorkflows', 'Description' => 'Enable Workflow Import & Export'),
      'SupportedConversionTypes' => array('Type' => 'array', 'Default' => 'array(\'Document\', \'Image\', \'Model\', \'Scad\', \'Drawing\', \'SVG\', \'Video\', \'Subtitle\', \'Audio\', \'Archive\', \'Stream\', \'OCR\', \'Ebook\')', 'Depends' => '', 'Description' => 'Allowed Conversion Types'),
      'AllowBootableIsoImage' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Allow Creation Of Bootable ISO Images'),
      'DeleteThreshold' => array('Type' => 'int', 'Depends' => '', 'Description' => 'File Deletion Age Threshold'),
      'Verbose' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Enhanced Logging Verbosity'),
      'MaxLogSize' => array('Type' => 'int', 'Depends' => '', 'Description' => 'Maximum Log File Size'),
      'Font' => array('Type' => 'string', 'Depends' => '', 'Description' => 'UI Element Font'),
      'AllowUserSelectableColor' => array('Type' => 'bool', 'Depends' => 'SupportedColors', 'Description' => 'Allow User Selectable Colors'),
      'SupportedColors' => array('Type' => 'array', 'Default' => 'array(\'red\', \'green\', \'blue\', \'grey\', \'orange\', \'purple\', \'dark\')', 'Depends' => '', 'Description' => 'Supported Colors'),
      'ButtonStyle' => array('Type' => 'string', 'Depends' => '', 'Description' => 'Button Color'),
      'SpinnerStyle' => array('Type' => 'int', 'Depends' => '', 'Description' => 'Spinner Style'),
      'SpinnerColor' => array('Type' => 'expression', 'Depends' => '', 'Description' => 'Spinner Color'),
      'ShowGUI' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Show Full GUI'),
      'ShowFinePrint' => array('Type' => 'bool', 'Depends' => 'ShowGUI', 'Description' => 'Show Fine Print'),
      'TOSURL' => array('Type' => 'string', 'Depends' => 'ShowFinePrint', 'Description' => 'Terms of Service URL'),
      'PPURL' => array('Type' => 'string', 'Depends' => 'ShowFinePrint', 'Description' => 'Privacy Policy URL'),
      'RARArchiveMethod' => array('Type' => 'string', 'Depends' => '', 'Description' => 'RAR Archive Method'),
      'RetryCount' => array('Type' => 'int', 'Depends' => '', 'Description' => 'File Operation Retry Count'),
      'GuiMaxWidth' => array('Type' => 'int', 'Depends' => '', 'Description' => 'GUI Maximum Width'),
      'LogoURL' => array('Type' => 'string', 'Depends' => '', 'Description' => 'Logo URL'),
      'SupportedFormatDetectionType' => array('Type' => 'string', 'Depends' => '', 'Description' => 'Supported Format Detection Type'),
      'WarnOnCapabilityMismatch' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Warn On Capability Mismatch'))),
    'Directory Information' => array('Writable' => TRUE, 'Variables' => array(
      'InstLoc' => array('Type' => 'path', 'Depends' => '', 'Description' => 'Installation Directory'),
      'ProprietaryLoc' => array('Type' => 'path', 'Depends' => '', 'Description' => 'Proprietary Directory'),
      'ServerRootDir' => array('Type' => 'path', 'Depends' => '', 'Description' => 'Server Root Directory'),
      'ConvertLoc' => array('Type' => 'path', 'Depends' => '', 'Description' => 'Data Storage Directory'),
      'LogDir' => array('Type' => 'expression', 'Default' => '$ConvertLoc.\'/Logs\'', 'Depends' => '', 'Description' => 'Log Storage Directory'),
      'HomeLoc' => array('Type' => 'expression', 'Default' => '$ConvertLoc.\'/Cache\'', 'Depends' => '', 'Description' => 'Home Directory'),
      'BackupLoc' => array('Type' => 'expression', 'Default' => '$ConvertLoc.\'/Last-Installed-Version\'', 'Depends' => 'EnableAutoUpdates', 'Description' => 'Backup Location'),
      'AppendLogHashToLogFiles' => array('Type' => 'bool', 'Depends' => 'UniqueDailyLogHash', 'Description' => 'Append Log Hash To Log Files'),
      'UniqueDailyLogHash' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Unique Daily Log Hash Rotation'),
      'AdditionalConvertLocs' => array('Type' => 'array', 'Default' => 'array()', 'Depends' => '', 'Description' => 'Additional Data Locations'),
      'StorageCleanupInterval' => array('Type' => 'int', 'Depends' => '', 'Description' => 'Storage Cleanup Interval'))),
    'Security Informations' => array('Writable' => TRUE, 'Variables' => array(
      'MaintainHTAccess' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Maintain HTAccess'),
      'URL' => array('Type' => 'string', 'Depends' => '', 'Description' => 'Server URL'),
      'EnableMemoryProtection' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Enable Memory Protection'),
      'VirusScan' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Virus Scanning'),
      'AllowUserVirusScan' => array('Type' => 'bool', 'Depends' => 'VirusScan', 'Description' => 'User Virus Scanning'),
      'ScanCoreMemoryLimit' => array('Type' => 'int', 'Depends' => '', 'Description' => 'User Virus Scanning ScanCore Memory Limit'),
      'ScanCoreChunkSize' => array('Type' => 'int', 'Depends' => '', 'Description' => 'User Virus Scanning ScanCore Chunk Size'),
      'ScanCoreDebug' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'User Virus Scanning ScanCore Debug Mode'),
      'ScanCoreVerbose' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'User Virus Scanning ScanCore Enhanced Verbosity'),
      'DeleteBuildEnvironment' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Delete Build Environment'),
      'DeleteDevelopmentDocumentation' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Delete Development Documentation'),
      'AllowUnprivilegedNamespaces' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Allow Unprivileged Namespaces'),
      'RequireSandbox' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Require Sandbox'),
      'RequireSandboxOnDocker' => array('Type' => 'bool', 'Depends' => 'RequireSandbox', 'Description' => 'Require Sandbox On Docker'),
      // / The Environment Manager settings belong to the model as well as to config.php, because a repair can only add a setting this utility knows about.
      'EnvironmentManagerMayRepair' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Environment Manager May Repair'),
      'EnvironmentManagerMayRewriteConfigs' => array('Type' => 'bool', 'Depends' => 'EnvironmentManagerMayRepair', 'Description' => 'Environment Manager May Rewrite Configs'),
      'ThrowSandboxWarning' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Throw Sandbox Warning'),
      'StreamWatchTimeout' => array('Type' => 'int', 'Depends' => '', 'Description' => 'Stream Duration Timeout'),
      'StreamConnectionTimeout' => array('Type' => 'int', 'Depends' => '', 'Description' => 'Stream Connection Timeout'),
      'AllowStreamOverHTTP' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Allow Streams Over HTTP'),
      'StreamInspectionLayers' => array('Type' => 'int', 'Depends' => '', 'Description' => 'Stream Inspection Layers'),
      'StreamInspectionFilesPerLayer' => array('Type' => 'int', 'Depends' => 'StreamInspectionLayers', 'Description' => 'Stream Inspection Files Per Layer'),
      'DefaultStreamInspectionForfeitAction' => array('Type' => 'string', 'Depends' => 'StreamInspectionLayers', 'Description' => 'Default Stream Inspection Forfeit Action'),
      'MaxStreamInspectionFileSize' => array('Type' => 'int', 'Depends' => 'StreamInspectionLayers', 'Description' => 'Maximum Stream Inspection Size'),
      'AllowSCADIncludeResolution' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Allow SCAD Include Resolution'),
      'SCADConversionTimeout' => array('Type' => 'int', 'Depends' => '', 'Description' => 'SCAD Conversion Timeout'),
      'UsePyMeshLab' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Use PyMeshLab Python Bindings'),
      'AllowUserURLDownload' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Allow User URL Download'),
      'ScanURLDownloads' => array('Type' => 'bool', 'Depends' => 'AllowUserURLDownload', 'Description' => 'Scan URL Downloads'),
      'URLDownloadMaximumBytes' => array('Type' => 'int', 'Depends' => 'AllowUserURLDownload', 'Description' => 'URL Download Maximum Bytes'),
      'URLDownloadsPerSession' => array('Type' => 'int', 'Depends' => 'AllowUserURLDownload', 'Description' => 'URL Downloads Per Session'),
      'DefaultVirusScanner' => array('Type' => 'string', 'Depends' => '', 'Description' => 'Default Virus Scanner'),
      'PermittedPrivateRanges' => array('Type' => 'array', 'Depends' => '', 'Description' => 'Permitted Private Ranges'))),
    'Resource Management Information' => array('Writable' => TRUE, 'Variables' => array(
      'EnablePerConversionLimits' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Enable Per Conversion Limits'),
      'MaximumPerConversionResources' => array('Type' => 'array', 'Default' => 'array( \'Document\'     => \'50,512\', \'Spreadsheet\'  => \'50,512\', \'Presentation\' => \'50,768\', \'Image\'        => \'75,1024\', \'Video\'        => \'90,2048\', \'Audio\'        => \'50,512\', \'Archive\'      => \'50,512\', \'Model\'        => \'75,2048\', \'Scad\'         => \'75,1024\', \'Drawing\'      => \'50,512\', \'SVG\'          => \'50,512\', \'Subtitle\'     => \'25,256\', \'Stream\'       => \'90,2048\', \'OCR\'          => \'75,1024\', \'Ebook\'        => \'50,768\')', 'Depends' => 'EnablePerConversionLimits', 'Description' => 'Maximum Per Conversion Resources'),
      'DefaultPerConversionResources' => array('Type' => 'string', 'Depends' => 'EnablePerConversionLimits', 'Description' => 'Default Per Conversion Resources'),
      'MinimumPerConversionResources' => array('Type' => 'string', 'Depends' => 'EnablePerConversionLimits', 'Description' => 'Minimum Per Conversion Resources'),
      'EnableResourceAwareness' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Enable Resource Awareness'),
      'RequireResourceAwareness' => array('Type' => 'bool', 'Depends' => 'EnableResourceAwareness', 'Description' => 'Require Resource Awareness'),
      'CoreManagerSubprocessPollInterval' => array('Type' => 'int', 'Depends' => '', 'Description' => 'Core Manager Subprocess Poll Interval'),
      'ResourcePollInterval' => array('Type' => 'int', 'Depends' => '', 'Description' => 'Resource Poll Interval'),
      'WorkerReapInterval' => array('Type' => 'int', 'Depends' => '', 'Description' => 'Worker Reap Interval'),
      'WorkerStaleGracePeriod' => array('Type' => 'int', 'Depends' => '', 'Description' => 'Worker Stale Grace Period'),
      'TotalResourceBudget' => array('Type' => 'int', 'Depends' => '', 'Description' => 'Total Resource Budget'),
      'ReserveResourcePercentage' => array('Type' => 'int', 'Depends' => '', 'Description' => 'Reserve Resource Percentage'),
      'MaxConcurrentWorkers' => array('Type' => 'int', 'Depends' => '', 'Description' => 'Maximum Concurrent Workers'),
      'MaxExpectedRuntime' => array('Type' => 'int', 'Depends' => '', 'Description' => 'Maximum Expected Runtime'),
      'MaxRuntimeExtensions' => array('Type' => 'int', 'Depends' => '', 'Description' => 'Maximum Runtime Extensions'),
      'DefaultConversionCost' => array('Type' => 'int', 'Depends' => '', 'Description' => 'Default Conversion Cost'),
      'DefaultExpectedRuntime' => array('Type' => 'int', 'Depends' => '', 'Description' => 'Default Expected Runtime'),
      'LowStorageWarning' => array('Type' => 'int', 'Depends' => 'EnableResourceAwareness', 'Description' => 'Low Storage Warning'),
      'StorageReserve' => array('Type' => 'int', 'Depends' => 'EnableResourceAwareness', 'Description' => 'Storage Reserve'),
      'MaxWorkflowSteps' => array('Type' => 'int', 'Depends' => 'EnableWorkflows', 'Description' => 'Maximum Workflow Steps'),
      'MaxWorkflowFiles' => array('Type' => 'int', 'Depends' => 'EnableWorkflows', 'Description' => 'Maximum Workflow Files'),
      'MaxWorkflowRuntime' => array('Type' => 'int', 'Depends' => 'EnableWorkflows', 'Description' => 'Maximum Workflow Runtime'),
      'MaxConcurrentWorkflows' => array('Type' => 'int', 'Depends' => 'EnableWorkflows', 'Description' => 'Maximum Concurrent Workflows'))),
    // / The format lists ARE modelled & the section stays Writable FALSE.
    // / Those answer different questions. Modelling them lets the utility say whether a
    // / list is present & report the section honestly instead of as an unknown. Writable
    // / FALSE keeps the utility from ever writing one, because a format list is a thing
    // / an administrator curates & a tool that normalizes it would undo that work.
    // / The defaults below are what this application ships. They are here so a generated
    // / configuration can emit a list that is absent, & for nothing else.
    'Supported File Format Information' => array('Writable' => FALSE, 'Variables' => array(
      'UserArchiveArray' => array('Type' => 'array', 'Default' => 'array(\'zip\', \'rar\', \'tar\', \'7z\', \'iso\')', 'Depends' => '', 'Description' => 'Supported Archive Formats'),
      'UserBootableIsoArray' => array('Type' => 'array', 'Default' => 'array(\'iso_mbr-boot\', \'iso_gpt-boot\', \'iso_gpt-boot-x86\', \'iso_gpt-boot-x86-64\', \'iso_gpt-boot-arm32\', \'iso_gpt-boot-arm64\')', 'Depends' => '', 'Description' => 'Supported Bootable ISO Output Formats'),
      'UserDearchiveArray' => array('Type' => 'array', 'Default' => 'array(\'zip\', \'rar\', \'tar\', \'bz\', \'gz\', \'bz2\', \'7z\', \'iso\', \'vhd\', \'vdi\', \'tar.bz2\', \'tar.gz\', \'cbr\', \'cbz\')', 'Depends' => '', 'Description' => 'Supported Dearchive Formats'),
      'UserDocumentArray' => array('Type' => 'array', 'Default' => 'array(\'txt\', \'doc\', \'docx\', \'rtf\', \'odt\', \'pdf\')', 'Depends' => '', 'Description' => 'Supported Document Formats'),
      'UserSpreadsheetArray' => array('Type' => 'array', 'Default' => 'array(\'csv\', \'xls\', \'xlsx\', \'ods\')', 'Depends' => '', 'Description' => 'Supported Spreadsheet Formats'),
      'UserXPSInputArray' => array('Type' => 'array', 'Default' => 'array(\'xps\', \'oxps\')', 'Depends' => '', 'Description' => 'Supported XPS Input Formats'),
      'UserXPSOutputArray' => array('Type' => 'array', 'Default' => 'array(\'pdf\')', 'Depends' => '', 'Description' => 'Supported XPS Output Formats'),
      'UserPresentationInputArray' => array('Type' => 'array', 'Default' => 'array(\'pptx\', \'ppt\', \'potx\', \'potm\', \'pot\', \'ppa\', \'odp\')', 'Depends' => '', 'Description' => 'Supported Presentation Input Formats'),
      'UserPresentationOutputArray' => array('Type' => 'array', 'Default' => 'array(\'pptx\', \'ppt\', \'potx\', \'potm\', \'pot\', \'ppa\', \'odp\', \'pdf\')', 'Depends' => '', 'Description' => 'Supported Presentation Output Formats'),
      'UserImageArray' => array('Type' => 'array', 'Default' => 'array(\'jpeg\', \'jpg\', \'jpe\', \'png\', \'bmp\', \'gif\', \'webp\', \'cin\', \'dds\', \'dib\', \'flif\', \'avif\', \'gplt\', \'sct\', \'xcf\', \'heic\', \'ico\', \'tiff\', \'tif\', \'heif\', \'jp2\', \'j2k\', \'jxl\')', 'Depends' => '', 'Description' => 'Supported Image Formats'),
      'UserImageInputArray' => array('Type' => 'array', 'Default' => 'array(\'jpeg\', \'jpg\', \'png\', \'bmp\', \'gif\', \'webp\', \'dds\', \'avif\', \'heic\', \'ico\', \'tiff\', \'tif\', \'jp2\')', 'Depends' => '', 'Description' => 'Supported Image Input Formats'),
      'UserImageOutputArray' => array('Type' => 'array', 'Default' => 'array(\'jpeg\', \'jpg\', \'png\', \'bmp\', \'gif\', \'webp\', \'dds\', \'avif\', \'ico\', \'tiff\', \'tif\', \'jp2\')', 'Depends' => '', 'Description' => 'Supported Image Output Formats'),
      'UserMediaInputArray' => array('Type' => 'array', 'Default' => 'array(\'sox\', \'spdif\', \'spx\', \'tta\', \'u16be\', \'u16le\', \'u24be\', \'u24le\', \'u32be\', \'u32le\', \'u8\', \'voc\', \'wav\', \'wv\', \'wsaud\', \'mulaw\', \'mxf\', \'mxf_d10\', \'mxf_opatom\', \'oga\', \'ogg\', \'opus\', \'oss\', \'psp\', \'rawvideo\', \'s16be\', \'s16le\', \'s24be\', \'s24le\', \'s32be\', \'s32le\', \'s8\', \'sbc\', \'ilbc\', \'ircam\', \'latm\', \'lrc\', \'mp2\', \'mp3\', \'mlp\', \'flac\', \'g722\', \'g723_1\', \'g726\', \'g726le\', \'gsm\', \'caf\', \'daud\', \'dts\', \'eac3\', \'f32be\', \'f32le\', \'f64be\', \'f64le\', \'ac3\', \'ac4\', \'adts\', \'aiff\', \'alaw\', \'amr\', \'aptx\', \'aptx_hd\', \'argo_asf\', \'argo_cvg\', \'ast\', \'au\', \'a64\', \'aa\', \'aac\', \'aax\', \'acm\', \'act\', \'adp\', \'adx\', \'aea\', \'afc\', \'aix\', \'alp\', \'amrnb\', \'amrwb\', \'apac\', \'apc\', \'ape\', \'apm\', \'argo_asf\', \'binka\', \'bit\', \'boa\', \'bonk\', \'brstm\', \'dfpwm\', \'dsf\', \'dss\', \'epaf\', \'fsb\', \'fwse\', \'g729\', \'hca\', \'idf\', \'kux\', \'kvag\', \'laf\', \'lavfi\', \'loas\', \'luodat\', \'lvf\', \'lxf\', \'mca\', \'mcc\', \'megsts\', \'mlv\', \'mmf\', \'mods\', \'moflex\', \'mpc8\', \'msf\', \'msnwctcp\', \'mtaf\', \'musx\', \'nc\', \'nistsphere\', \'nsp\', \'paf\', \'pam_pipe\', \'pbm_pipe\', \'pfm_pipe\', \'pp_bnk\', \'psxstr\', \'pva\', \'pvf\', \'qcp\', \'rka\', \'rl2\', \'rpl\', \'rso\', \'s337m\', \'sap\', \'sbg\', \'scd\', \'sdns\', \'sdp\', \'sds\', \'sdx\', \'siff\', \'simbiosis_imx\', \'sln\', \'smk\', \'smush\', \'sol\', \'svag\', \'svs\', \'tak\', \'thp\', \'tierexseq\', \'tty\', \'ty\', \'usm\', \'vag\', \'vidc\', \'vpk\', \'vqf\', \'w64\', \'wady\', \'wavarc\', \'wsd\', \'wsvqa\', \'wve\', \'xa\', \'xbin\', \'xbm_pipe\', \'xmd\', \'xpm_pipe\', \'xwma\', \'yop\', \'wma\', \'m4a\')', 'Depends' => '', 'Description' => 'Supported Audio Input Formats'),
      'UserMediaOutputArray' => array('Type' => 'array', 'Default' => 'array(\'mp3\', \'aac\', \'ogg\', \'wma\', \'mp2\', \'flac\', \'m4a\')', 'Depends' => '', 'Description' => 'Supported Audio Output Formats'),
      'UserVideoInputArray' => array('Type' => 'array', 'Default' => 'array(\'smoothstreaming\', \'svcd\', \'swf\', \'truehd\', \'vc1\', \'vc1test\', \'vcd\', \'vob\', \'vvc\', \'webm\', \'yuv4mpegpipe\', \'mpjpeg\', \'mxf\', \'mxf_d10\', \'mxf_opatom\', \'nut\', \'obu\', \'ogv\', \'psp\', \'rawvideo\', \'rm\', \'roq\', \'rtp_mpegts\', \'smjpeg\', \'hevc\', \'hls\', \'image2\', \'image2pipe\', \'ipod\', \'ismv\', \'m4v\', \'matroska\', \'mjpeg\', \'mkvtimestamp_v2\', \'mov\', \'mp4\', \'mpeg\', \'mpeg1video\', \'mpeg2video\', \'mpegts\', \'mpegtsraw\', \'mpegvideo\', \'fbdev\', \'film_cpk\', \'filmstrip\', \'gxf\', \'h261\', \'h263\', \'h264\', \'hds\', \'avs2\', \'avs3\', \'cavsvideo\', \'cavs\', \'dirac\', \'dnxhd\', \'dv\', \'dvd\', \'evc\', \'3g2\', \'3gp\', \'apng\', \'argo_asf\', \'argo_cvg\', \'asf\', \'asf_stream\', \'avi\', \'avif\', \'avm2\', \'3dostr\', \'4xm\', \'adf\', \'ads\', \'alias_pix\', \'anm\', \'argo_brp\', \'asf_o\', \'av1\', \'avs\', \'bethsoftvid\', \'bfi\', \'bink\', \'bmv\', \'brender_pix\', \'brender\', \'cdg\', \'cdxl\', \'cine\', \'concat\', \'cri\', \'dcstr\', \'derf\', \'dfa\', \'dhav\', \'dsicin\', \'dtshd\', \'dxa\', \'ea\', \'exr\', \'fits\', \'flic\', \'frm\', \'gdv\', \'genh\', \'gif\', \'idcin\', \'iff\', \'ifv\', \'ingenient\', \'ipmovie\', \'iss\', \'iv8\', \'ivf\', \'ivr\', \'j2k\', \'jp2\', \'jv\', \'live_flv\', \'lmlm4\', \'mtv\', \'mv\', \'mvi\', \'mxg\', \'nsv\', \'nuv\', \'osq\', \'pcx_pipe\', \'pdv\', \'pgm_pipe\', \'pgmuv_pipe\', \'pgx_pipe\', \'phm_pipe\', \'protocol_pipe\', \'pictor_pipe\', \'png_pipe\', \'ppm_pipe\', \'psd_pipe\', \'qdraw_pipe\', \'qoi_pipe\', \'r3d\', \'redspark\', \'rroq\', \'rsd\', \'rtsp\', \'sdr2\', \'ser\', \'sga\', \'sgi_pipe\', \'shn\', \'sunrast_pipe\', \'svg_pipe\', \'tiff_pipe\', \'tmv\', \'v210\', \'v210x\', \'vbn_pipe\', \'video4linux2\', \'v4l2\', \'vividas\', \'vivo\', \'vmd\', \'wc3movie\', \'webm_dash_manifest\', \'webp_pipe\', \'wtv\', \'xmv\', \'xvag\', \'xwd_pipe\', \'mkv\', \'wmv\')', 'Depends' => '', 'Description' => 'Supported Video Input Formats'),
      'UserVideoOutputArray' => array('Type' => 'array', 'Default' => 'array(\'3gp\', \'mkv\', \'avi\', \'mp4\', \'mpeg\', \'wmv\', \'mov\', \'m4v\')', 'Depends' => '', 'Description' => 'Supported Video Output Formats'),
      'UserStreamArray' => array('Type' => 'array', 'Default' => 'array(\'m3u8\', \'ts\')', 'Depends' => '', 'Description' => 'Supported Stream Formats'),
      'UserDrawingArray' => array('Type' => 'array', 'Default' => 'array(\'dxf\', \'vdx\', \'fig\', \'dia\', \'wpg\')', 'Depends' => '', 'Description' => 'Supported Drawing Formats'),
      'UserDrawingInputArray' => array('Type' => 'array', 'Default' => 'array(\'dxf\', \'vdx\', \'fig\', \'dia\', \'wpg\')', 'Depends' => '', 'Description' => 'Supported Drawing Input Formats'),
      'UserDrawingOutputArray' => array('Type' => 'array', 'Default' => 'array(\'dxf\', \'vdx\', \'fig\', \'dia\', \'wpg\')', 'Depends' => '', 'Description' => 'Supported Drawing Output Formats'),
      'UserSVGInputArray' => array('Type' => 'array', 'Default' => 'array(\'svg\', \'plain-svg\')', 'Depends' => '', 'Description' => 'Supported SVG Input Formats'),
      'UserSVGOutputArray' => array('Type' => 'array', 'Default' => 'array(\'png\', \'pdf\', \'ps\', \'eps\', \'emf\', \'wmf\')', 'Depends' => '', 'Description' => 'Supported SVG Output Formats'),
      'UserModelArray' => array('Type' => 'array', 'Default' => 'array(\'stl\', \'ply\', \'off\', \'3ds\', \'fbx\', \'dae\', \'gltf\', \'glb\', \'obj\', \'3mf\', \'x3d\', \'dxf\', \'x\', \'ctm\')', 'Depends' => '', 'Description' => 'Supported Model Formats'),
      'UserModelInputArray' => array('Type' => 'array', 'Default' => 'array(\'stl\', \'ply\', \'off\', \'3ds\', \'fbx\', \'dae\', \'gltf\', \'glb\', \'obj\', \'3mf\', \'x3d\', \'dxf\', \'x\', \'ctm\')', 'Depends' => '', 'Description' => 'Supported Model Input Formats'),
      'UserModelOutputArray' => array('Type' => 'array', 'Default' => 'array(\'stl\', \'ply\', \'off\', \'3ds\', \'fbx\', \'dae\', \'gltf\', \'glb\', \'obj\', \'3mf\', \'x3d\', \'dxf\', \'x\', \'ctm\', \'wrl\', \'assbin\', \'json\')', 'Depends' => '', 'Description' => 'Supported Model Output Formats'),
      'UserSCADArray' => array('Type' => 'array', 'Default' => 'array(\'scad\', \'stl\', \'off\', \'amf\', \'3mf\', \'csg\')', 'Depends' => '', 'Description' => 'Supported OpenSCAD Formats'),
      'UserSCADInputArray' => array('Type' => 'array', 'Default' => 'array(\'scad\')', 'Depends' => '', 'Description' => 'Supported SCAD Input Formats'),
      'UserSCADOutputArray' => array('Type' => 'array', 'Default' => 'array(\'stl\', \'off\', \'amf\', \'3mf\', \'csg\')', 'Depends' => '', 'Description' => 'Supported SCAD Output Formats'),
      'UserSubtitleInputArray' => array('Type' => 'array', 'Default' => 'array(\'sub\', \'sbv\', \'srt\', \'stream_segment\', \'ssegment\', \'streamhash\', \'sup\', \'subtitles\', \'ttml\', \'uncodedframecrc\', \'webvtt\', \'wtv\', \'oma\', \'rso\', \'rtp\', \'rtsp\', \'scc\', \'sdl\', \'sdl2\', \'segment\', \'sap\', \'jacosub\', \'kvag\', \'microdvd\', \'ffmetadata\', \'fifo\', \'fifo_test\', \'fits\', \'framecrc\', \'framehash\', \'framemd5\', \'dash\', \'crc\', \'dvbsub\', \'dvbtxt\', \'gsm\', \'ass\', \'vobsub\', \'mpl2\', \'mpsub\', \'pjs\', \'realtext\', \'sami\', \'stl\', \'subviewer\', \'subviewer1\', \'tedcaptions\', \'txd\', \'vtt\', \'ssa\', \'dvb\', \'vplayer\')', 'Depends' => '', 'Description' => 'Supported Subtitle Input Formats'),
      'UserSubtitleOutputArray' => array('Type' => 'array', 'Default' => 'array(\'vtt\', \'ssa\', \'ass\', \'srt\')', 'Depends' => '', 'Description' => 'Supported Subtitle Output Formats'),
      'UserPDFWorkArr' => array('Type' => 'array', 'Default' => 'array(\'pdf\', \'jpg\', \'jpeg\', \'png\', \'bmp\', \'webp\', \'gif\')', 'Depends' => '', 'Description' => 'Supported OCR Formats'),
      'UserEbookInputArray' => array('Type' => 'array', 'Default' => 'array(\'epub\', \'mobi\', \'azw\', \'azw3\', \'azw4\', \'fb2\', \'fbz\', \'lit\', \'lrf\', \'pdb\', \'pml\', \'rb\', \'snb\', \'tcr\', \'txt\', \'txtz\', \'rtf\', \'odt\', \'docx\', \'pdf\', \'chm\', \'cbz\', \'cbr\', \'cbc\', \'prc\', \'opf\', \'recipe\')', 'Depends' => '', 'Description' => 'Supported Ebook Input Formats'),
      'UserEbookOutputArray' => array('Type' => 'array', 'Default' => 'array(\'epub\', \'mobi\', \'azw3\', \'fb2\', \'lit\', \'lrf\', \'pdb\', \'pmlz\', \'rb\', \'snb\', \'tcr\', \'txt\', \'txtz\', \'rtf\', \'docx\', \'pdf\')', 'Depends' => '', 'Description' => 'Supported Ebook Output Formats'))));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection);
  return $ConfigModel; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report on the parts of the environment that belong to HRConvert2.
// / Accepts nothing. Returns a readiness boolean & a list of findings, in that order.
// / Each finding is an array of Check, Status & Detail, which is the shape the Engine
// / appends to its own findings.
// /
// / The Engine calls this because engineConfig.php names it in $EngineEnvironmentProvider,
// / & not because the Engine knows anything about it. It is handed over the same way
// / getSandboxProfiles() above is, & for the same reason.
// / Every check below is specific to this application, because another application on the same Engine has no ImageMagick policy to check.
// / The data exposure test, the data location & the hosted location are the Engine's, because what the web server serves comes from the roles in the contract.
// / The Engine reports them directly after these.
// / So are the kernel settings & the sandbox profile, because every sandbox the Engine builds needs them, & the Engine reports them directly before these.
// /
// / Nothing here repairs anything, because repair needs root & the listener does not have it.
// / It reports, & -fp fixes.
// / A drifted policy is reported & does not fail readiness, because conversions still run.
function applicationEnvironmentFindings() {
  // / Set variables.
  global $EnableMemoryProtection;
  $ApplicationIsReady = TRUE;
  $ApplicationFindings = array();
  $policyIsValid = FALSE;
  $policyStatus = '';
  // / Policies are validated & never repaired here. A drifted policy is reported.
  // / The status word is reported as it is, rather than flattened to ok, & the sentence
  // / beside it says whether anything needs doing. Flattening lost the difference between
  // / a policy that matches & a host that never needed one.
  list ($policyIsValid, $policyStatus) = verifyImageMagickPolicy(FALSE);
  $ApplicationFindings[] = array('Check' => 'ImageMagick policy', 'Status' => policyDisplayStatus($policyStatus), 'Detail' => describePolicyStatus('ImageMagick', $policyStatus));
  list ($policyIsValid, $policyStatus) = verifyOpenScadPolicy(FALSE);
  $ApplicationFindings[] = array('Check' => 'OpenSCAD AppArmor', 'Status' => policyDisplayStatus($policyStatus), 'Detail' => describePolicyStatus('OpenSCAD AppArmor', $policyStatus));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $policyIsValid, $policyStatus);
  return array($ApplicationIsReady, $ApplicationFindings); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to scan a path & act on the verdict.
// / Accepts the path. Returns whether the scan ran & whether a threat was found.
// /
// / It dispatches to a scanner pipeline & does not know which scanner ran.
// / A scanner is chosen by name through $DefaultVirusScanner, so an installation can run ScanCore where ClamAV is not wanted.
// /
// / The scanner reports & the caller decides.
// / A pipeline never deletes anything, because removing an infected file is a policy this application holds & another application may not want.
// /
// / Nothing the scanner said is repeated, to the user or to the log.
// / The findings are read for a verdict & discarded, because a signature name comes from a database this application does not control.
function virusScan($path) {
  // / Set variables.
  global $AllowUserVirusScan, $DefaultVirusScanner, $Verbose, $EnableMemoryProtection;
  $ScanComplete = FALSE;
  $VirusFound = FALSE;
  $scanFindings = array();
  $scannerUsed = '';
  if (!function_exists('runVirusScan')) errorEntry('The Pipeline Core is unavailable, so '.$path.' was not scanned!', 506, FALSE);
  else {
    list ($ScanComplete, $VirusFound, $scanFindings, $scannerUsed) = runVirusScan(array($path), isset($DefaultVirusScanner) ? (string)$DefaultVirusScanner : '');
    if (!$ScanComplete) errorEntry('A virus scan could not be completed for '.$path.'!', 505, FALSE);
    else if ($Verbose) logEntry('Scanned '.$path.' with '.($scannerUsed !== '' ? $scannerUsed : 'the default scanner').'. Findings: '.count($scanFindings).'.');
    if ($VirusFound) {
      // / The file goes unless the operator asked to keep it for their own inspection.
      if (!$AllowUserVirusScan && is_file($path) && !is_dir($path)) @unlink($path);
      errorEntry('There were potentially infected files detected at '.$path.'!', 500, FALSE); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $scanFindings, $scannerUsed, $path);
  return array($ScanComplete, $VirusFound); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to create required directories if they do not exist.
// / Maintenance folders are emptied by passing their children to cleanFiles().
// / cleanFiles() refuses to operate on an approved root, so the root itself is never passed.
// / Root never reaches this, so nothing here changes an owner.
// / Only a web request calls it, after the security context check has refused root with error 28.
function verifyRequiredDirs() {
  // / Set variables.
  global $ConvertLoc, $RequiredDirs, $RequiredIndexes, $RequiredCleanupFolders, $Verbose, $PermissionLevels, $DirSep, $InstLoc, $EnableMemoryProtection;
  $DirectoriesVerified = $directoriesProtected = 0;
  $locationIsProtected = $protectionWasWritten = FALSE;
  $protectionIsCurrent = $RequiredDirsExist = TRUE;
  $cleanupContents = array();
  $requiredDir = $requiredIndex = $requiredCleanupFolder = $cleanupEntry = $cleanupPath = '';
  // / The roots this sweep may clean under, named rather than assumed.
  // / cleanFiles refuses anything outside them, so this list is the whole of its authority
  // / & an empty list would refuse everything rather than allow everything.
  $allowedCleanupRoots = array($ConvertLoc);
  if (is_array($RequiredCleanupFolders)) $allowedCleanupRoots = array_merge($allowedCleanupRoots, $RequiredCleanupFolders);
  // / If the $ConvertLoc does not exist we stop execution rather than create one.
  if (!is_dir($ConvertLoc)) errorEntry('The specified Data Storage Directory does not exist at '.$ConvertLoc.'!', 1000, TRUE);
  // / Iterate through the array of required directories.
  foreach ($RequiredDirs as $requiredDir) {
    // / Try to create the currently selected directory if it does not already exist.
    if (!is_dir($requiredDir)) {
      @mkdir($requiredDir, $PermissionLevels);
      if ($Verbose) logEntry('Created a directory at '.$requiredDir.'.'); }
    // / Re-check to see if our attempt to create the directory was successful.
    if (is_dir($requiredDir)) {
      // / A directory that was ALREADY there is not news. Ten of these on every request
      // / buries the one line that matters, which is the summary at the end saying every
      // / required directory is present.
      // / A directory that had to be CREATED is still logged, because that is a change.
      $DirectoriesVerified++;
      // / Every directory this application creates is treated as HOSTED.
      // / Deciding which of them a web server can actually reach means knowing that
      // / server's configuration, which changes without this application being told.
      // / Protecting all of them costs one small file each & removes the question.
      // / The Engine writes it & the setting decides whether it does. Only a directory
      // / without an index is touched, so an application with a real one keeps it.
      if (function_exists('protectHostedLocation')) {
        list ($locationIsProtected, $protectionWasWritten) = protectHostedLocation($requiredDir);
        if ($protectionWasWritten) $directoriesProtected++;
        if (!$locationIsProtected) warningEntry('A required directory has no document root protection: '.$requiredDir); } }
    // / A single missing directory invalidates the whole check.
    else {
      $RequiredDirsExist = FALSE;
      errorEntry('Could not create a directory at '.$requiredDir.'!', 1001, TRUE); } }
  // / Make sure that each required directory has an index.html file for document root protection.
  foreach ($RequiredIndexes as $requiredIndex) @copy($InstLoc.$DirSep.'index.html', $requiredIndex.$DirSep.'index.html');
  // / Keep the DATA protection file correct on every request, beside the index files.
  // / It is the Engine's, & a request without an Engine was refused long before this line.
  // / A file that is not current does not stop a request, because the Engine has said why & the exposure check establishes whether it matters.
  if (function_exists('maintainDataProtection')) $protectionIsCurrent = maintainDataProtection();
  if (!$protectionIsCurrent && $Verbose) logEntry('The DATA protection file is not current. Run the -fp argument as root & read what it reports for DATA.');
  // / Clear the contents of each maintenance folder, then delete the folder itself.
  foreach ($RequiredCleanupFolders as $requiredCleanupFolder) {
    if (!is_dir($requiredCleanupFolder)) continue;
    $cleanupContents = array_diff(scandir($requiredCleanupFolder), array('.', '..'));
    foreach ($cleanupContents as $cleanupEntry) {
      $cleanupPath = $requiredCleanupFolder.$DirSep.$cleanupEntry;
      @chmod($cleanupPath, $PermissionLevels);
      if (is_file($cleanupPath)) @unlink($cleanupPath);
      elseif (is_dir($cleanupPath)) cleanFiles($cleanupPath, $allowedCleanupRoots); }
    // / The folder itself is removed only once its contents are gone.
    @rmdir($requiredCleanupFolder); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $directoriesProtected, $locationIsProtected, $protectionWasWritten, $protectionIsCurrent, $allowedCleanupRoots, $requiredDir, $requiredIndex, $requiredCleanupFolder, $cleanupEntry, $cleanupPath, $cleanupContents);
  return array($RequiredDirsExist, $RequiredDirs, $DirectoriesVerified); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to replace the install wide secret with a new one.
// / Accepts whether the action was confirmed in advance with -y. Returns a success boolean.
// / Called by --rotate-secret, never reachable from the web, & root only.
// /
// / Every session ends.
// / Every session folder, token & startup key derives from the secret, so a visitor is given a new session on their next visit & cannot reach their earlier files.
// / Those folders stay where they are & expire on the delete threshold like any other.
// / That is what rotating a secret is for, so the operator is asked first, or answers with -y.
// /
// / The listener holds keys derived from the old secret, so one that is running must be stopped & started again, & root never does either through the web server account's files.
// /   Ask whether a listener is running & stop it before the secret is rewritten.
// /   A service unit that owns the listener is asked through systemctl.
// /   Otherwise the web server account is asked, & it answers & stops with the old key, which is still the one on disk.
// /   A listener that could not be stopped would keep answering with the old key, so nothing is rotated & the operator is told.
// /   Rewrite the secret as root, which is the one part that is root's.
// /   Start it again through startCoreManagerListener(), which uses the unit when one owns the listener & hands the start to the web server account when none does.
// / A listener that was not running is left stopped.
// / A container from an image built before v4.0.1 carries the secret that image was built with, & every container started from it shares it.
// / Running this once in each ends that.
function rotateInstallSecret($rotationConfirmed) {
  // / Set variables.
  global $InstLoc, $SecretFile, $SecretKey, $RequiredSecretVersion, $ResourceAwarenessActive, $ApacheUser, $Lol, $EnableMemoryProtection;
  $SecretWasRotated = FALSE;
  $actionIsConfirmed = $listenerWasRunning = $listenerWasStopped = $listenerWasStarted = $secretIsReady = $unitOwnsListener = FALSE;
  $newSecretKey = $entryPath = $unitName = '';
  $statusOutput = $stopOutput = array();
  $statusExit = $stopExit = 1;
  $actionIsConfirmed = confirmDestructiveAction('Rotating the install secret ends every session. Visitors are given a new one & cannot reach their earlier files.', $rotationConfirmed);
  if ($actionIsConfirmed) {
    $entryPath = $InstLoc.DIRECTORY_SEPARATOR.engineApplicationEntryPoint();
    $unitName = engineApplicationSlug().'-listener';
    if ($ResourceAwarenessActive && function_exists('listenerUnitIsInstalled')) $unitOwnsListener = listenerUnitIsInstalled();
    // / Learn whether a listener is running, & stop it, BEFORE the secret is rewritten.
    if ($ResourceAwarenessActive && $unitOwnsListener) {
      exec('systemctl is-active --quiet '.escapeshellarg($unitName).' 2>&1', $statusOutput, $statusExit);
      $listenerWasRunning = ($statusExit === 0);
      if ($listenerWasRunning) {
        exec('systemctl stop '.escapeshellarg($unitName).' 2>&1', $stopOutput, $stopExit);
        $listenerWasStopped = ($stopExit === 0); } }
    else if ($ResourceAwarenessActive && function_exists('runAsAccount')) {
      list ($statusOutput, $statusExit) = runAsAccount('php '.escapeshellarg($entryPath).' --status 2>&1', (string)$ApacheUser);
      $listenerWasRunning = (stripos(implode(' ', $statusOutput), 'RUNNING as process') !== FALSE);
      if ($listenerWasRunning) {
        list ($stopOutput, $stopExit) = runAsAccount('php '.escapeshellarg($entryPath).' -k 2>&1', (string)$ApacheUser);
        $listenerWasStopped = ($stopExit === 0); } }
    if ($listenerWasRunning && !$listenerWasStopped) {
      warningEntry('The install secret was not rotated, because the running listener could not be stopped first. It would have kept answering with the old key.');
      errorEntry('The install secret could not be rotated! The secret in place is unchanged.', 31022, FALSE);
      print($Lol.'The listener is running & could not be stopped, so the install secret was NOT rotated.'.$Lol);
      print('Stop it with  sudo php '.engineApplicationEntryPoint().' -k  & run this again.'.$Lol); }
    else {
      list ($secretIsReady, $newSecretKey) = resolveInstallSecretAsRoot($SecretFile, $RequiredSecretVersion, TRUE);
      if (!$secretIsReady) {
        errorEntry('The install secret could not be rotated! The secret in place is unchanged.', 31022, FALSE);
        print($Lol.'The install secret could not be rotated. The secret in place is unchanged.'.$Lol); }
      else {
        $SecretWasRotated = TRUE;
        print($Lol.'The install secret was rotated. Every earlier session has ended.'.$Lol);
        // / $SecretKey is the documented exception to convention 3 & is assigned here because
        // / nothing in this process derives a key again, but a reader that did would get the new one.
        $SecretKey = $newSecretKey; }
      // / A listener that was running is started again whether or not the rotation took, because
      // / it was stopped above & the secret on disk is whichever one is in place now.
      if ($listenerWasRunning) $listenerWasStarted = startCoreManagerListener();
      if ($listenerWasRunning && !$listenerWasStarted) print('The listener could not be restarted. Run  sudo php '.engineApplicationEntryPoint().' -l'.$Lol); }
    print($Lol); }
  // / Manually clean up sensitive memory. Every target is passed directly by reference.
  purgeSensitiveMemory($EnableMemoryProtection, $actionIsConfirmed, $listenerWasRunning, $listenerWasStopped, $listenerWasStarted, $secretIsReady, $unitOwnsListener, $newSecretKey, $entryPath, $unitName, $statusOutput, $stopOutput, $statusExit, $stopExit, $rotationConfirmed);
  return $SecretWasRotated; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert a file based on a pre-determined input type & return the results.
// / Streams are the only conversion that keeps running after the user has been served.
// / The stream supervision variables are globals so the core can reach them after this returns.
// / Every converter returns the output path & the extension it actually produced, because a
// / bootable disk image rewrites both. Everything else hands back what it was given.
// / $UserFilename is initialized here rather than only inside the Archive branch.
// / An undefined return value is a warning & a warning corrupts an AJAX response.
function convert($type, $pathname, $newPathname, $extension, $height, $width, $rotate, $bitrate) {
  // / Set variables.
  global $SupportedConversionTypes, $PipelineCoreActive, $WaitForStream, $StreamPID, $StreamOutputPath, $Verbose, $EnableMemoryProtection;
  $ConversionSuccess = $ConversionErrors = FALSE;
  $UserFilename = basename($newPathname);
  $WorkerPID = 0;
  $pipelineServesThisConversion = FALSE;
  $inputExtension = getExtension($pathname);
  // / Check that the required conversion type is allowed.
  if (in_array($type, $SupportedConversionTypes)) {
    // / Try the pipeline manager first, then fall back to the built in routing.
    // / A subsystem that can fall back must fall back before it errors. The routing below
    // / is the local fallback that configuration cannot overwrite, so a missing, unreadable
    // / or version mismatched Pipeline Core costs an installation nothing but a warning.
    // /
    // / The question is whether a pipeline serves this conversion, not whether the manager
    // / LOADED. Those are different questions & asking the wrong one is how a working
    // / installation loses a conversion it has always been able to perform.
    // / A manager that verified an Ebook pipeline is ACTIVE, & it has nothing whatsoever to
    // / say about txt to rtf. Handing that conversion to it anyway produced a warning about
    // / no Document pipeline claiming the pair, followed by error 21, on an installation
    // / whose built in Document converter was sitting right there the entire time.
    // /
    // / So the family, the input extension & the output extension are ALL asked about
    // / before dispatch. A family that is not served falls through to the routing below &
    // / converts exactly as it did before this component existed.
    if ($PipelineCoreActive && function_exists('familyHasPipeline')) $pipelineServesThisConversion = familyHasPipeline($type, $inputExtension, $extension);
    if ($pipelineServesThisConversion) list ($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $UserFilename, $WorkerPID) = runConversion($type, $pathname, $newPathname, $extension, $height, $width, $rotate, $bitrate);
    // / Every conversion family has a pipeline, & this file converts nothing on its own.
    // / There is no built in dispatcher to fall back to, so a family with no installed pipeline is reported here rather than failing silently.
    // / Restoring a converter to this file is not the fix for this error.
    // / Repair the pipeline folder, & run with -v to read the warnings above this line, which name the folder that was refused & the reason.
    // / A request for the format a file already has is refused for what it is, because no pipeline claims one.
    else if (strtolower((string)$inputExtension) === strtolower((string)$extension)) {
      $ConversionErrors = TRUE;
      errorEntry('The file is already '.strtolower((string)$extension).', so there is nothing to convert!', 34009, FALSE); }
    else {
      $ConversionErrors = TRUE;
      errorEntry('The '.$type.' conversion family has no installed pipeline!', 34005, FALSE); }
    // / A non zero worker PID means the core must stay alive after serving the user.
    // / Any pipeline whose dependency outlives the request reports its process here, & is supervised for up to $StreamWatchTimeout minutes by the code that supervises FFMPEG.
    // / These are set inside this branch, so a conversion of any other type never overwrites them.
    if ($WorkerPID > 0) {
      $WaitForStream = TRUE;
      $StreamPID = $WorkerPID;
      $StreamOutputPath = $newPathname; } }
  // / An entry point that returned nothing usable in the filename slot still has to hand the
  // / core a name, because an undefined value becomes a warning inside an AJAX response.
  if (!is_string($UserFilename) or $UserFilename === '') $UserFilename = basename($newPathname);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $pipelineServesThisConversion, $inputExtension, $WorkerPID, $type, $pathname, $height, $width, $rotate, $bitrate);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $UserFilename); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to find a name for an output that nothing in its folder already has.
// / Accepts the folder, the name the output was asked for without its extension, & the extension.
// / A taken name is given _1 before its extension, then _2, & so on.
// / The suffix is an underscore & a number, because sanitize() turns a space & brackets into exactly that.
// / Returns whether a free name was found, the free path & the free name, in that order.
function findFreeOutputName($directory, $baseName, $extension) {
  // / Set variables.
  global $EnableMemoryProtection;
  $NameWasFound = FALSE;
  $FreePathname = $FreeName = $candidateName = $candidatePathname = '';
  $suffixNumber = 0;
  // / A session never holds this many copies of one name, so reaching the limit means something is wrong.
  $suffixLimit = 1000;
  for ($suffixNumber = 1; $suffixNumber <= $suffixLimit && !$NameWasFound; $suffixNumber++) {
    // / Each candidate replaces the one before it through redeclare(), so a name that was taken is shredded rather than left behind.
    // / sanitizeString() is what sanitize() runs on one string, & it returns the string alone, which redeclare() can take.
    redeclare($candidateName, sanitizeString($baseName.'_'.$suffixNumber.'.'.$extension, TRUE));
    redeclare($candidatePathname, sanitizeString($directory.$candidateName, FALSE));
    clearstatcache(TRUE, $candidatePathname);
    // / A link is a taken name even when it resolves nowhere, because writing to it would follow it.
    if (!file_exists($candidatePathname) && !is_link($candidatePathname)) {
      $NameWasFound = TRUE;
      $FreeName = $candidateName;
      $FreePathname = $candidatePathname; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $directory, $baseName, $extension, $candidateName, $candidatePathname, $suffixNumber, $suffixLimit);
  return array($NameWasFound, $FreePathname, $FreeName); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify files before performing operations on them.
// / Accepts the file, the output name & extension the user asked for, & three switches.
// / $clean removes a stale staged copy & gives an output whose name is taken the next free one.
// / $copy stages the file in the temporary location & $skip tolerates a file that was not staged.
// / A caller that adds to an output it already named passes $clean as FALSE, so the name stays.
// / Returns a verification boolean, the staged path, the original path, the original
// / extension, the output path & the output name, in that order.
// / A file this refuses is skipped rather than halted on. Every caller reports a file that
// / failed verification & moves on, so a bad name costs that file & nothing else.
function verifyFile($file, $UserFilename, $UserExtension, $clean, $copy, $skip) {
  // / Set variables.
  global $ConvertDir, $ConvertTempDir, $Allowed, $Verbose, $EnableMemoryProtection;
  $FileIsVerified = $Pathname = $OldPathname = $NewPathname = $variableIsSanitized = $freeNameWasFound = FALSE;
  $outputBaseName = $requestedName = $requestedPathname = $freePathname = $freeName = '';
  // / If the $UserFilename is blank then use the original filename instead.
  $OldExtension = getExtension($file);
  if ($UserFilename === '') $UserFilename = trim(str_replace('.'.$OldExtension, '', $file), '.');
  // / Check to make sure all iteration specific required variables are properly sanitized.
  list ($file, $variableIsSanitized) = sanitize($file, FALSE);
  list ($Pathname, $variableIsSanitized) = sanitize($ConvertTempDir.$file, FALSE);
  list ($OldPathname, $variableIsSanitized) = sanitize($ConvertDir.$file, FALSE);
  // / Check if the selected file is safe to handle.
  if (in_array(strtolower($UserExtension), $Allowed) or $UserExtension == '') if (in_array(strtolower($OldExtension), $Allowed) && !extensionIsDangerous($OldExtension) && $file !== '.' && $file !== '..' && $file !== 'index.html') $FileIsVerified = TRUE;
  // / A file refused here is a request this application handled correctly, so it is a
  // / warning rather than error 14000, which halted every request that carried one.
  if (!$FileIsVerified) warningEntry('The file '.$file.' failed first stage validation & was refused.');
  if ($FileIsVerified) {
    if ($Verbose && file_exists($Pathname) && $clean) logEntry('Deleting stale file '.$Pathname.'.');
    // / Remove the temp file if one already exists.
    if (file_exists($Pathname) && $clean) @unlink($Pathname);
    // / Check to make sure that the stale file was deleted if required or creating a new one will cause problems.
    if (file_exists($Pathname) && $clean) errorEntry('Could not delete stale file '.$Pathname.'!', 14001, TRUE);
    if ($Verbose && file_exists($OldPathname) && $copy) logEntry('Copying file '.$file.' to '.$Pathname.'.');
    // / Copy the file to the working directory.
    if (file_exists($OldPathname) && $copy) @copy($OldPathname, $Pathname);
    // / Check to make sure the temporary file was created.
    if (!$skip) if (!file_exists($Pathname)) errorEntry('The file '.$Pathname.' failed second stage validation!', 14002, TRUE);
    if (file_exists($Pathname)) if ($Verbose && $copy) logEntry('Copied file '.$file.'.');
    // / If the $UserFilename & $UserExtension variables are valid we can prepare for a $NewPathname.
    if ($UserFilename && $UserExtension) {
      $outputBaseName = $UserFilename;
      list ($requestedName, $variableIsSanitized) = sanitize($UserFilename.'.'.$UserExtension, TRUE);
      // / Define the path the output asked for.
      list ($requestedPathname, $variableIsSanitized) = sanitize($ConvertDir.$requestedName, FALSE);
      // / Make sure the output is not a dangerous file.
      if (extensionIsDangerous($UserExtension) or !in_array(strtolower($UserExtension), $Allowed)) errorEntry('The file '.$file.' failed third stage validation!', 14003, TRUE);
      // / An output whose name is taken is given the next free one, so nothing a user already has is replaced.
      // / A converter skips an output it finds, so a taken name would otherwise hand the user the old file unchanged.
      // / The original holds its own name, so this also keeps an output from ever being the file it was made from.
      clearstatcache(TRUE, $requestedPathname);
      if ($clean && (file_exists($requestedPathname) or is_link($requestedPathname))) {
        list ($freeNameWasFound, $freePathname, $freeName) = findFreeOutputName($ConvertDir, $outputBaseName, $UserExtension);
        if ($freeNameWasFound && $Verbose) logEntry('The name '.$requestedName.' is taken, so the output will be '.$freeName.'.');
        if (!$freeNameWasFound) {
          $FileIsVerified = FALSE;
          errorEntry('No free name was found for the output of '.$file.', so it was refused!', 14006, FALSE); } }
      // / The name asked for & the free one are held under names of their own, so the output is named once.
      $NewPathname = ($freeNameWasFound ? $freePathname : $requestedPathname);
      $UserFilename = ($freeNameWasFound ? $freeName : $requestedName);
      // / An output that IS the original is refused, because writing it would write over the only copy.
      // / A caller adding to an output it already named keeps that name, so this is still checked.
      if ($FileIsVerified && $NewPathname === $OldPathname) {
        $FileIsVerified = FALSE;
        errorEntry('The output for '.$file.' would replace the original, so it was refused!', 14005, FALSE); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $clean, $copy, $skip, $file, $variableIsSanitized, $outputBaseName, $requestedName, $requestedPathname, $freeNameWasFound, $freePathname, $freeName);
  return array($FileIsVerified, $Pathname, $OldPathname, $OldExtension, $NewPathname, $UserFilename); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to build & display the GUI.
// / The variables declared here will be usable in GUI elements.
// / Files like header, footer, styleCore, convertGui1 & convertGui2 have access to them.
// / Every language string is a function local rather than a global, because the language
// / pack is required from inside this function. Nothing outside this call can read them.
function buildGUI($guiType, $ShowGUI, $ButtonCode) {
  // / Set variables.
  global $GuiFiles, $LanguageFiles, $LanguageStringsFile, $LanguageBaselineFile, $GuiHeaderFile, $GuiFooterFile, $GuiUI1File, $GuiUI2File, $CoreLoaded, $ConvertDir, $ConvertTempDir, $Token1, $Token2, $SesHash, $SesHash2, $SesHash3, $SesHash4, $Date, $Time, $TOSURL, $PPURL, $ShowFinePrint, $PDFWorkArr, $ArchiveArray, $DearchiveArray, $DocumentArray, $SpreadsheetArray, $ImageArray, $ImageOutputArray, $ModelArray, $DrawingArray, $VideoInputArray, $VideoOutputArray, $SubtitleInputArray, $SubtitleOutputArray, $StreamArray, $MediaInputArray, $MediaOutputArray, $PresentationInputArray, $PresentationOutputArray, $XPSInputArray, $XPSOutputArray, $ConvertGuiCounter1, $ConsolidatedLogFileName, $Alert, $Alert1, $Alert2, $Alert3, $FCPlural, $FCPlural1, $FCPlural2, $FCPlural3, $File, $Files, $FileCount, $SpinnerStyle, $SpinnerColor, $PacmanLoc, $Allowed, $AllowUserVirusScan, $AllowUserShare, $SupportedConversionTypes, $FullURL, $LanguageDir, $FaviconPath, $DropzonePath, $DropzoneStylesheetPath, $StylesheetPath, $JsLibraryPath, $WorkflowsJsPath, $JqueryPath, $GUIDirection, $SupportedFormatCount, $GUIAlignment, $HeaderDisplayed, $UIDisplayed, $FooterDisplayed, $LanguageStringsLoaded, $GUIDisplayed, $GuiResourcesDir, $GuiImageDir, $GuiCSSDir, $GuiJSDir, $StreamOutputArray, $SCADArray, $SCADOutputArray, $AllowUserSelectableColor, $AllowUserSelectableGui, $AllowUserSelectableLanguage, $SupportedColors, $SupportedGuis, $SupportedLanguages, $ColorToUse, $GuiToUse, $LanguageToUse, $GuiDir, $SVGInputArray, $SVGOutputArray, $LanguageFlagFile, $LanguageVersion, $RequiredLanguageVersion, $DefaultLanguage, $BootableIsoArray, $AllowBootableIsoImage, $EbookInputArray, $EbookOutputArray, $EnableMemoryProtection, $NoGui, $ShowFiles, $FileListOnly, $Verbose, $AllowUserURLDownload, $GuiMaxWidth, $GuiWidth, $LogoURL, $ResolvedLogoURL, $AllowUserIdentify, $SortToUse, $FileDetails, $EnableWorkflows, $EnableWorkflowBuilder, $EnableWorkflowImportExport;
  $GUIDisplayed = FALSE;
  $guiUIFile = $GuiUI1File;
  $Files = $FileDetails = array();
  $FileCount = 0;
  // / Make sure the $guiType is valid.
  // / A non numeric type cannot be compared against a range, so it is replaced outright.
  // / Only two GUI types exist, so anything outside that range is clamped into it.
  if (!is_numeric($guiType)) $guiType = 1;
  $guiType = (int)$guiType;
  if ($guiType < 1) $guiType = 1;
  if ($guiType > 2) $guiType = 2;
  // / Determine which loading indicator to use.
  $PacmanLoc = $GuiImageDir.'pacman'.$SpinnerStyle.strtolower($SpinnerColor).'.gif';
  if (!file_exists($PacmanLoc)) $PacmanLoc = $GuiImageDir.'pacman1grey.gif';
  // / Gather a list of files.
  // / Both interfaces get the file list, not only the second.
  // / CREATING_GUIS.txt documents $Files & $FileCount as available to a GUI & does not
  // / qualify which one, so populating them for only one interface contradicted the
  // / contract every interface is written against. An upload page that cannot see whether
  // / the session already holds files cannot offer to clear them, & cannot tell a first
  // / visit apart from a return visit.
  // / getFiles() is a filtered scandir with no side effects, so this costs the upload page
  // / one directory listing & nothing else.
  // / The files arrive in the order the user chose, each with its size & the time it changed.
  list ($Files, $FileDetails) = arrangeFiles($ConvertDir, getFiles($ConvertDir), $SortToUse);
  $FileCount = count($Files);
  // / An interface that renders an empty list has either been handed the wrong directory
  // / or a directory with nothing in it, & those are different faults with different
  // / fixes. Saying which directory was read, & whether this was the framed request, is
  // / the difference between diagnosing it & guessing at it.
  if ($Verbose) logEntry('GUI '.$guiType.' file list: Directory: '.$ConvertDir.', Files: '.$FileCount.', List only: '.($FileListOnly ? 'YES' : 'NO').', Session: '.$SesHash.'/'.$SesHash2.'.');
  // / The baseline loads first, & the selected pack loads over the top.
  // / A language pack is nothing but variable assignments, so every string ends set to the later pack's value, or to the baseline's where the later pack does not mention it.
  // / That is the whole fallback, & it is per string rather than per pack.
  // / An incomplete pack shows the strings it has in the user's language, & no interface can print an undefined variable, whatever a pack omits.
  if ($LanguageBaselineFile !== '' && file_exists($LanguageBaselineFile)) require_once($LanguageBaselineFile);
  // / Load language specific GUI elements, if there are any.
  if (in_array($LanguageStringsFile, $LanguageFiles)) require_once($LanguageStringsFile);
  // / A version mismatch is a warning, not a refusal.
  // / The version says whether a pack was written against this core, & an administrator should know when it was not.
  // / It does not decide whether the page can be built, because the baseline underneath guarantees every string the interface reads has a value.
  // / A user who cannot read the page cannot report the problem. A page in the wrong
  // / language beats no page at all, & a page that is right except for one new button beats
  // / both.
  if ($LanguageVersion !== $RequiredLanguageVersion) warningEntry('Language pack '.$LanguageToUse.' reports version '.$LanguageVersion.' but this core requires '.$RequiredLanguageVersion.'. Any string it does not define is being taken from '.($LanguageBaselineFile === '' ? 'nowhere, because no baseline pack was available' : $LanguageBaselineFile).'.');
  // / Build the GUI as long as a pack actually loaded. A pack sets this flag itself, so it
  // / is TRUE only if one of the two requires above really ran.
  if ($LanguageStringsLoaded) {
    // / A fragment request gets a fragment. No header & no footer.
    // / --File List Only-- asks for a piece of a page that is about to be placed INSIDE a
    // / page that already exists. Answering it with a whole document sends a second copy of
    // / everything the header carries, which is every script this interface defines, & a
    // / second copy of the footer, which is why the privacy notice appeared twice.
    // / Worse, the duplicated script redefined the helper & re-ran its ready handler, which
    // / fetched the fragment again, which redefined the helper again. That is a request
    // / loop several times a second, & it destroyed every click handler on each pass, which
    // / is why nothing in the list could be clicked.
    // / A fragment is not a document & must not be dressed as one.
    if (!$FileListOnly && in_array($GuiHeaderFile, $GuiFiles)) require_once($GuiHeaderFile);
    // / Build and define the different GUI types that are available.
    if ($guiType === 1) $guiUIFile = $GuiUI1File;
    if ($guiType === 2) $guiUIFile = $GuiUI2File;
    // / Build the specified GUI.
    if (in_array($guiUIFile, $GuiFiles)) require_once($guiUIFile);
    // / Load the footer.
    if (!$FileListOnly && in_array($GuiFooterFile, $GuiFiles)) require_once($GuiFooterFile);
    // / A fragment never loads the header or the footer, so it cannot be judged on whether
    // / they displayed. It is complete when the interface itself rendered.
    if ($FileListOnly) { if ($UIDisplayed && $LanguageStringsLoaded) $GUIDisplayed = TRUE; }
    else if ($HeaderDisplayed && $UIDisplayed && $FooterDisplayed && $LanguageStringsLoaded) $GUIDisplayed = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $guiType, $guiUIFile, $ButtonCode);
  return $GUIDisplayed; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to display the GUI.
function showGUI($ShowGUI, $ButtonCode) {
  // / Set variables.
  global $ShowFiles;
  $GUIDisplayed = FALSE;
  // / $ShowFiles is read from the superglobal once, in verifyGlobals, & everything after
  // / that reads the variable. Reading $_GET again here meant the same question was asked
  // / of two different places, which is how they end up disagreeing.
  // / Call the GUI from the selected language pack after files have been uploaded.
  if ($ShowFiles) $GUIDisplayed = buildGUI(2, $ShowGUI, $ButtonCode);
  // / Call the GUI from the selected language pack before files have been uploaded.
  else $GUIDisplayed = buildGUI(1, $ShowGUI, $ButtonCode);
  return $GUIDisplayed; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to add up the size of everything in an upload.
// / Accepts no arguments.
// / Returns the bytes uploaded.
// / PHP has received every file by the time this runs, & reports each one's size itself.
// / A file that did not arrive is counted as nothing.
function measureUploadSize() {
  // / Set variables.
  global $EnableMemoryProtection;
  $UploadBytes = 0;
  $uploadSizes = array();
  $uploadSize = 0;
  if (isset($_FILES['file']['size'])) $uploadSizes = is_array($_FILES['file']['size']) ? $_FILES['file']['size'] : array($_FILES['file']['size']);
  foreach ($uploadSizes as $uploadSize) $UploadBytes = $UploadBytes + max(0, (int)$uploadSize);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $uploadSizes, $uploadSize);
  return $UploadBytes; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to upload a selection of files.
function uploadFiles() {
  // / Set variables.
  global $VirusScan, $AllowUserVirusScan, $ConvertDir, $Verbose, $PermissionLevels, $Allowed, $OperationErrors, $Pipelines, $EnableMemoryProtection;
  $UploadComplete = $UploadErrors = $virusFound = $variableIsSanitized = FALSE;
  $file = $fileExtension = $placedName = $sourcePath = '';
  $uploadIndex = 0;
  $scanComplete = $fileWasPlaced = FALSE;
  $filesPipelineIsReady = FALSE;
  $filesEntryPoint = '';
  // / What may be uploaded is this application's policy. Putting the bytes somewhere is not.
  // / $Allowed & $DangerousFiles are HRConvert2's lists & stay here. The placement is the
  // / Files pipeline, which sanitizes the destination name, resolves it & refuses anything
  // / landing outside the directory it was permitted.
  if (!is_array($_FILES['file']['name'])) $_FILES['file']['name'] = array($_FILES['file']['name']);
  foreach ($_FILES['file']['name'] as $uploadIndex => $file) {
    $UploadComplete = FALSE;
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $OperationErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 6000, FALSE);
      continue; }
    if ($Verbose) logEntry('User selected to Upload file '.$file.'.');
    $fileExtension = getExtension($file);
    if (extensionIsDangerous($fileExtension) or !in_array(strtolower($fileExtension), $Allowed)) {
      errorEntry('Unsupported file format, '.$fileExtension.'!', 6001, FALSE);
      continue; }
    // / The temporary name is indexed, so a multiple file upload hands copy() each file rather than an array.
    $sourcePath = is_array($_FILES['file']['tmp_name']) ? (isset($_FILES['file']['tmp_name'][$uploadIndex]) ? $_FILES['file']['tmp_name'][$uploadIndex] : '') : $_FILES['file']['tmp_name'];
    // / Loaded on demand. See the note in getFiles for why enumeration is not enough.
    list ($filesPipelineIsReady, $filesEntryPoint) = loadFilesPipeline();
    if ($filesPipelineIsReady && function_exists('placeFileInDirectory')) list ($fileWasPlaced, $placedName) = placeFileInDirectory($ConvertDir, $sourcePath, pathinfo($file, PATHINFO_BASENAME), $PermissionLevels);
    else {
      $fileWasPlaced = FALSE;
      warningEntry('The Files pipeline is not available, so nothing was uploaded.'); }
    if (!$fileWasPlaced) {
      $UploadErrors = TRUE;
      errorEntry('Could not upload file '.$file.'!', 6002, FALSE); }
    else {
      $UploadComplete = TRUE;
      if ($Verbose) logEntry('Uploaded file '.$file.'.'); }
    // / The scan runs on what LANDED rather than on what was offered, & only when something
    // / did. Scanning a file that was refused reports on nothing.
    if ($fileWasPlaced && !$AllowUserVirusScan && $VirusScan) {
      if ($Verbose) logEntry('Starting virus scan.');
      list ($scanComplete, $virusFound) = virusScan($ConvertDir.$placedName);
      if (!$scanComplete) errorEntry('Could not perform a virus scan!', 6003, TRUE);
      if ($virusFound) errorEntry('Virus detected!', 6004, TRUE);
      if ($Verbose) logEntry('Virus scan complete.'); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $filesPipelineIsReady, $filesEntryPoint, $file, $fileExtension, $placedName, $sourcePath, $uploadIndex, $variableIsSanitized, $scanComplete, $virusFound, $fileWasPlaced);
  return array($UploadComplete, $UploadErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to stage a selection of files for download.
function downloadFiles($Download) {
  // / Set variables.
  global $DangerousFiles, $Verbose, $ConsolidatedLogFileName, $Allowed, $EnableMemoryProtection;
  $DownloadComplete = $DownloadErrors = $clean = $copy = $skip = $variableIsSanitized = FALSE;
  $fileIsVerified = FALSE;
  $file = $fileExtension = $pathname = $oldPathname = $oldExtension = $newPathname = $resolvedFilename = '';
  // / The result describes the operation, not the last file in the list.
  // / This carried the same fault deleteFiles() carried. $DownloadComplete was reset to
  // / FALSE at the top of every iteration & set TRUE only by a successful one, so the value
  // / that survived the loop described whatever happened to the LAST entry. Nine files
  // / staged out of ten reported total failure if the tenth was refused, & the caller
  // / answers a failure here with a fatal error 19 that prints ERROR!!! to the page.
  // / Counting instead, on the same terms as deleteFiles(). An entry refused before it is
  // / attempted is recorded as an error for the caller but is not counted as an attempt,
  // / because refusing input the application was right to refuse is not a failure of the
  // / download.
  $filesAttempted = $filesStaged = 0;
  list ($Download, $variableIsSanitized) = sanitize($Download, FALSE);
  // / Make sure the input files are formatted into an array.
  // / A single filename & a list of them are the same operation with a different count.
  if (!is_array($Download)) $Download = array($Download);
  // / Iterate through the array of input files.
  foreach ($Download as $file) {
    // / Every iteration decides these for itself, so one file in a list never flags a later one.
    $clean = $copy = $skip = FALSE;
    // / Make sure the file is sanitized before processing it.
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      // / This wrote to $OperationErrors, which this function does not declare, does not
      // / read & does not return. A refused filename therefore reported no error at all.
      $DownloadErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 3000, FALSE);
      continue; }
    if ($Verbose) logEntry('User selected to Download file '.$file.'.');
    if ($file === $ConsolidatedLogFileName) $skip = TRUE;
    else $clean = $copy = TRUE;
    $fileExtension = getExtension($file);
    // / Make sure the file is not in the list of dangerous formats.
    if (extensionIsDangerous($fileExtension) or in_array(strtolower($file), $DangerousFiles) or !in_array(strtolower($fileExtension), $Allowed)) {
      $DownloadErrors = TRUE;
      // / A name with no extension is named as one, because a blank here read as a missing word.
      errorEntry('Unsupported file format, '.($fileExtension === '' ? 'no extension' : $fileExtension).'!', 3004, FALSE);
      continue; }
    // / Past this point the file is one this function agreed to act on, so it counts.
    $filesAttempted++;
    // / Make sure all iteration specific required variables are properly sanitized.
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname, $resolvedFilename) = verifyFile($file, FALSE, FALSE, $clean, $copy, $skip);
    if (!$fileIsVerified) {
      // / This wrote to $ArchiveErrors, which belongs to archiveFiles(). A file that failed
      // / verification here reported success to the caller.
      $DownloadErrors = TRUE;
      errorEntry('Could not verify the input file.', 3001, FALSE);
      continue; }
    // / Make sure that the file exists.
    if (!file_exists($oldPathname) && !$skip) {
      $DownloadErrors = TRUE;
      errorEntry('File '.$file.' does not exist!', 3002, FALSE);
      continue; }
    // / A staged file that is not where it was staged to is a failure of this operation.
    // / This logged error 3003 & then fell through to report the file as staged anyway.
    if (!file_exists($pathname)) {
      $DownloadErrors = TRUE;
      errorEntry('Could not verify the input file.', 3003, FALSE);
      continue; }
    $filesStaged++;
    // / Report the path that was actually staged, which is $pathname.
    // / This reported $newPathname & printed 'Verified file .' on every download.
    // / verifyFile() initialises $NewPathname to FALSE & only fills it inside its own
    // / if ($UserFilename && $UserExtension) branch, which builds the RENAMED destination an
    // / operation is about to write to. A download renames nothing, so this is the one caller
    // / that passes FALSE for both of those, the branch never runs, & FALSE concatenates into
    // / a log line as an empty string rather than as anything a reader would notice.
    // / The archive, conversion & OCR callers all pass a real filename & extension, so the
    // / same line reads correctly for them & the fault looked like it belonged to the log
    // / rather than to this one call.
    if ($Verbose) logEntry('Verified file '.$pathname.'.'); }
  // / The operation is complete when every file it agreed to act on was staged.
  // / An empty list is complete because there was nothing to stage, & reporting a fatal
  // / failure for asking to download nothing would be a worse answer than doing nothing.
  $DownloadComplete = ($filesStaged === $filesAttempted);
  if ($Verbose) logEntry('Download result: Attempted: '.$filesAttempted.', Staged: '.$filesStaged.', Errors: '.($DownloadErrors ? 'YES' : 'NO').'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $file, $fileExtension, $clean, $copy, $skip, $variableIsSanitized, $fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname, $resolvedFilename, $filesAttempted, $filesStaged);
  return array($DownloadComplete, $DownloadErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to delete a selection of files.
// / Each name is removed from the session directory & from the temporary one, by the Files
// / pipeline, which refuses any name that resolves outside the directory it was handed.
function deleteFiles($FilesToDelete) {
  // / Set variables.
  global $Verbose, $ConvertDir, $ConvertTempDir, $Pipelines, $EnableMemoryProtection;
  $DeleteComplete = $DeleteErrors = $variableIsSanitized = FALSE;
  $file = $fileExtension = '';
  $filesAttempted = $filesDeleted = 0;
  $permittedNames = array();
  $permittedDirectory = '';
  $filesPipelineIsReady = FALSE;
  $filesEntryPoint = '';
  $removalSucceeded = FALSE;
  $namesAttempted = $namesRemoved = 0;
  // / What may be deleted is this application's decision, & doing the deleting is not.
  // / $DangerousFiles is a list of formats HRConvert2 refuses to touch, which no other application has & an engine cannot guess, so the policy stays here.
  // / The removal itself is the Files pipeline, which is handed a directory it may operate in & refuses anything resolving outside it.
  // / The result describes the operation, not the last file in the list.
  list ($FilesToDelete, $variableIsSanitized) = sanitize($FilesToDelete, FALSE);
  if (!is_array($FilesToDelete)) $FilesToDelete = array($FilesToDelete);
  foreach ($FilesToDelete as $file) {
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $DeleteErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 23000, FALSE);
      continue; }
    if ($Verbose) logEntry('User selected to Delete file '.$file.'.');
    $fileExtension = getExtension($file);
    if (extensionIsDangerous($fileExtension) or $fileExtension === '') {
      $DeleteErrors = TRUE;
      errorEntry('Unsupported file format, '.$fileExtension.'!', 23001, FALSE);
      continue; }
    $filesAttempted++;
    $permittedNames[] = pathinfo($file, PATHINFO_BASENAME); }
  // / BOTH DIRECTORIES, because a file exists in the session directory & may also exist in
  // / the temporary one, & leaving either behind is a file a user asked to be gone.
  // / A name absent from one of them is not an error. It is a file that was never there.
  if (!empty($permittedNames)) {
    // / Loaded on demand. See the note in getFiles for why enumeration is not enough.
    list ($filesPipelineIsReady, $filesEntryPoint) = loadFilesPipeline();
    if ($filesPipelineIsReady && function_exists('removeFilesFromDirectory')) {
      foreach (array($ConvertTempDir, $ConvertDir) as $permittedDirectory) {
        list ($removalSucceeded, $namesAttempted, $namesRemoved) = removeFilesFromDirectory($permittedDirectory, $permittedNames);
        $filesDeleted += $namesRemoved; }
      // / Counted across both directories, so a file removed from each counts twice. What
      // / matters is that every name asked for is gone from everywhere it could have been.
      $DeleteComplete = ($filesDeleted > 0);
      if (!$DeleteComplete) {
        $DeleteErrors = TRUE;
        errorEntry('Could not delete the requested file(s)!', 23002, FALSE); } }
    else {
      $DeleteErrors = TRUE;
      errorEntry('The Files pipeline is not available, so nothing was deleted!', 23003, FALSE); } }
  if ($Verbose) logEntry('Delete result: Attempted: '.$filesAttempted.', Deleted: '.$filesDeleted.', Errors: '.($DeleteErrors ? 'yes' : 'no').'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $filesPipelineIsReady, $filesEntryPoint, $permittedDirectory, $file, $fileExtension, $variableIsSanitized, $filesAttempted, $filesDeleted, $permittedNames, $removalSucceeded, $namesAttempted, $namesRemoved);
  return array($DeleteComplete, $DeleteErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to archive a selection of files.
// / Each file is archived on its own, so one input produces one output every time.
// / Each output format gates on its own creator, so a missing mkisofs does not prevent a
// / zip archive from being created.
// / Every binary is supplied by toolPath(), which asks the manifest, rather than assumed, so
// / the version that was verified is provably the version that runs.
// / 7-Zip cannot create rar archives. RAR compression is proprietary & 7-Zip reads the
// / format without being able to write it, so rar output has NO fallback.
// / Returns whether any file was archived, whether anything went wrong, the last archive's path & the name of every archive it made, in that order.
// / It prints nothing. The core prints what it made for a button, & a workflow passes it to the next step.
function archiveFiles($FilesToArchive, $UserFilename, $UserExtension) {
  // / Set variables.
  global $Verbose, $VirusScan, $Lol, $Lolol, $EnableMemoryProtection;
  $ArchiveComplete = $ArchiveErrors = $virusFound = $skip = $variableIsSanitized = FALSE;
  $fileIsVerified = $scanComplete = $sandboxIsAvailable = $anyFileSucceeded = $loopCheck = FALSE;
  $archiveToolsAreValid = $archiveWasStarted = FALSE;
  $sevenZipBinary = $rarBinary = $zipBinary = $tarBinary = $mkisofsBinary = FALSE;
  $clean = $copy = TRUE;
  $returnData = $file = $pathname = $oldPathname = $oldExtension = $NewPathname = $archiveCommand = $resolvedFilename = '';
  $ArchivedFiles = array();
  // / The name every file is archived under, once the first file has settled it.
  $archiveName = $UserFilename;
  $rararr = array('rar');
  $ziparr = array('zip');
  $tararr = array('7z', 'tar', 'tar.gz', 'tar.bz2');
  $isoarr = array('iso');
  // / Locate & verify every archive utility before anything is written.
  $sevenZipBinary = toolPath('7-Zip');
  $rarBinary = toolPath('RAR');
  $zipBinary = toolPath('Zip');
  $tarBinary = toolPath('Tar');
  $mkisofsBinary = toolPath('Genisoimage');
  $archiveToolsAreValid = ($sevenZipBinary !== FALSE && $zipBinary !== FALSE && $tarBinary !== FALSE && $mkisofsBinary !== FALSE);
  // / Make sure the input files are formatted into an array.
  if (!is_array($FilesToArchive)) $FilesToArchive = array($FilesToArchive);
  // / Every other archiver here adds a file to an archive that already exists, which is how a
  // / selection of files becomes one archive. Mkisofs replaces the image it is given instead,
  // / so a second file would leave an image holding only the last one. That is refused rather
  // / than written, because an image quietly missing most of its files is worse than none.
  if (count($FilesToArchive) > 1 && in_array($UserExtension, $isoarr)) {
    $ArchiveErrors = TRUE;
    $FilesToArchive = array();
    errorEntry('An iso image holds one file at a time, so a selection of files cannot be archived into one!', 4005, FALSE); }
  // / Iterate through the array of input files.
  foreach ($FilesToArchive as $file) {
    $loopCheck = FALSE;
    $archiveCommand = '';
    // / Make sure the file is sanitized before processing it.
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $ArchiveErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 4000, FALSE);
      continue; }
    // / Set the $clean & $copy arguments for the verifyFile() function as needed.
    // / The first file asks for a clean output, so an archive name that is taken is given the next free one.
    // / Every file after it adds to the archive the first file started, so it keeps that archive's name.
    // / Without a name, each file is archived under its own, so each asks for a clean output.
    $clean = ($UserFilename === '' or $archiveWasStarted === FALSE);
    $copy = TRUE;
    if ($Verbose) logEntry('User selected to Archive file '.$file.'.');
    // / Verify the file before performing any operations on it.
    // / verifyFile returns the name with the extension appended, so the name carried to the next file has it taken off again.
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $NewPathname, $resolvedFilename) = verifyFile($file, $archiveName, $UserExtension, $clean, $copy, $skip);
    if (!$fileIsVerified) {
      $ArchiveErrors = TRUE;
      errorEntry('Could not verify the input file.', 4001, FALSE);
      continue; }
    else if ($Verbose) logEntry('Verified file '.$NewPathname.'.');
    // / redeclare() replaces the name, because a file that failed before the archive was started leaves the next one to settle it again.
    if ($clean && $UserFilename !== '') redeclare($archiveName, preg_replace('/\.'.preg_quote((string)$UserExtension, '/').'$/', '', (string)$resolvedFilename));
    // / Scan with the default scanner if $VirusScan is set to TRUE in config.php.
    if ($VirusScan) {
      if ($Verbose) logEntry('Starting virus scan.');
      list ($scanComplete, $virusFound) = virusScan($pathname);
      if (!$scanComplete) errorEntry('Could not perform a virus scan!', 4002, TRUE);
      if ($virusFound) errorEntry('Virus detected!', 4003, TRUE);
      if ($Verbose) logEntry('Virus scan complete.'); }
    // / Select the archiver for the requested output format & confirm it is usable.
    // / Handle archiving of rar compatible files.
    if (in_array($UserExtension, $rararr)) {
      if ($rarBinary === FALSE) {
        $ArchiveErrors = TRUE;
        errorEntry('Rar output requires the rar utility, which is missing or too old!', 13012, FALSE); }
      else $archiveCommand = escapeshellarg($rarBinary).' a -ep '.escapeshellarg($NewPathname).' '.escapeshellarg($pathname); }
    // / Handle archiving of zip compatible files.
    else if (in_array($UserExtension, $ziparr)) {
      if ($zipBinary === FALSE) {
        $ArchiveErrors = TRUE;
        errorEntry('Zip is missing, unidentifiable, or too old!', 13010, FALSE); }
      else $archiveCommand = escapeshellarg($zipBinary).' -j '.escapeshellarg($NewPathname).' '.escapeshellarg($pathname); }
    // / Handle archiving of 7-Zip compatible files.
    else if (in_array($UserExtension, $tararr)) {
      if ($sevenZipBinary === FALSE) {
        $ArchiveErrors = TRUE;
        errorEntry('The installed 7-Zip version is missing, unidentifiable, or too old!', 13008, FALSE); }
      else $archiveCommand = escapeshellarg($sevenZipBinary).' a '.escapeshellarg($NewPathname).' '.escapeshellarg($pathname); }
    // / Handle archiving of mkisofs compatible files.
    else if (in_array($UserExtension, $isoarr)) {
      if ($mkisofsBinary === FALSE) {
        $ArchiveErrors = TRUE;
        errorEntry('Mkisofs is missing, unidentifiable, or too old!', 13009, FALSE); }
      else $archiveCommand = escapeshellarg($mkisofsBinary).' -o '.escapeshellarg($NewPathname).' '.escapeshellarg($pathname); }
    // / Perform the archive operation inside a sandbox.
    // / An archiver reads a file the user supplied & writes a structure it controls, so it
    // / is isolated for the same reason every other dependency is.
    if ($archiveCommand !== '') {
      list ($sandboxIsAvailable, $archiveCommand) = sandboxCommand($archiveCommand, $pathname, $NewPathname, FALSE, 'archive');
      if (!$sandboxIsAvailable) {
        $ArchiveErrors = TRUE;
        errorEntry('Bubblewrap is missing or non functional, so this archive operation cannot be isolated!', 13006, FALSE); }
      else $returnData = shell_exec($archiveCommand); }
    // / Log the output of the archive operation to the logfile, if it is not blank.
    if ($Verbose && trim($returnData) !== '') logEntry('The archiver returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
    // / The output file is the only verdict on whether the operation produced anything.
    if (!file_exists($NewPathname)) {
      $ArchiveErrors = TRUE;
      errorEntry('Could not archive file '.$pathname.' to '.$NewPathname.'!', 4004, FALSE); }
    else {
      $loopCheck = TRUE;
      $archiveWasStarted = TRUE;
      // / An archive several files went into is named once.
      if (!in_array($resolvedFilename, $ArchivedFiles, TRUE)) $ArchivedFiles[] = $resolvedFilename;
      if ($Verbose) logEntry('Archived file '.$pathname.' to '.$NewPathname.'.'); }
    // / Record that at least one file in this request succeeded.
    // / $loopCheck is reset on every iteration, so without this the result would reflect
    // / only the LAST file rather than the whole request.
    if ($loopCheck) $anyFileSucceeded = TRUE; }
  if ($anyFileSucceeded) $ArchiveComplete = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $archiveWasStarted, $archiveName, $resolvedFilename, $file, $rararr, $ziparr, $tararr, $isoarr, $pathname, $oldPathname, $scanComplete, $virusFound, $returnData, $variableIsSanitized, $fileIsVerified, $oldExtension, $clean, $copy, $skip, $loopCheck, $anyFileSucceeded, $archiveCommand, $sandboxIsAvailable, $archiveToolsAreValid, $sevenZipBinary, $rarBinary, $zipBinary, $tarBinary, $mkisofsBinary, $FilesToArchive, $UserFilename, $UserExtension);
  return array($ArchiveComplete, $ArchiveErrors, $NewPathname, $ArchivedFiles); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert a selection of files.
// / Returns whether any file converted, whether anything went wrong & the name of every file it made, in that order.
// / It prints nothing. The core prints what it made for a button, & a workflow passes it to the next step.
// / A backgrounded successful stream has no output file when this function returns.
// / $WaitForStream tells us the conversion is still running & must not be judged by its output.
// / A family is resolved by applicationConversionFamilies(), so a workflow asks the same lists this does.
function convertFiles($ConvertSelected, $UserFilename, $UserExtension, $Height, $Width, $Rotate, $Bitrate) {
  // / Set variables.
  global $Verbose, $VirusScan, $WaitForStream, $EnableMemoryProtection;
  $MainConversionSuccess = $MainConversionErrors = $virusFound = $skip = $isExtensionSupported = $fileIsVerified = $variableIsSanitized = $outputExists = $conversionSuccess = $conversionErrors = $fileConversionSuccess = $anyStreamStarted = $scanComplete = FALSE;
  $clean = $copy = TRUE;
  $pathname = $oldPathname = $oldExtension = $newPathname = $file = $convertedFilename = $extension = $verifiedFilename = '';
  $ConvertedFiles = $familyFormats = $conversionFamilies = array();
  $arrKey = '';
  $conversionFamilies = applicationConversionFamilies();
  // / Make sure the input files are formatted into an array.
  if (!is_array($ConvertSelected)) $ConvertSelected = array($ConvertSelected);
  // / Iterate through the array of input files.
  foreach ($ConvertSelected as $file) {
    $fileConversionSuccess = $isExtensionSupported = $conversionSuccess = FALSE;
    // / Reset $WaitForStream for each file.
    // / It is a global set inside convert() & a stale TRUE would make the next file skip its output check.
    $WaitForStream = FALSE;
    // / Make sure the file is sanitized before processing it.
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $MainConversionErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 5000, FALSE);
      continue; }
    // / Every file asks verifyFile() for a clean output, so an output whose name is taken is given the next free one.
    // / An archive is no exception, so a conversion never adds to an archive that already has the name.
    if ($Verbose) logEntry('User selected to Convert file '.$file.'.');
    // / Verify the file before performing any operations on it.
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname, $verifiedFilename) = verifyFile($file, $UserFilename, $UserExtension, $clean, $copy, $skip);
    if (!$fileIsVerified) {
      $MainConversionErrors = TRUE;
      errorEntry('Could not verify the input file.', 5001, FALSE);
      continue; }
    else if ($Verbose) logEntry('Verified file '.$newPathname.'.');
    // / Scan with the default scanner if $VirusScan is set to TRUE in config.php.
    // / The staged input is scanned, because it is what the converter reads & the output does not exist yet.
    if ($VirusScan) {
      if ($Verbose) logEntry('Starting virus scan.');
      list ($scanComplete, $virusFound) = virusScan($pathname);
      if (!$scanComplete) errorEntry('Could not perform a virus scan!', 5002, TRUE);
      if ($virusFound) errorEntry('Virus detected!', 5003, TRUE);
      if ($Verbose) logEntry('Virus scan complete.'); }
    // / Loop through the families & only call the converter if both input & output files are supported.
    // / This loop resolves. It does not enumerate.
    // / An interface offers every family that claims the input extension, which is why an
    // / rtf file shows a Document menu & an E-Book menu. By the time a request arrives the
    // / user has already chosen an output format from one of those menus, so the input &
    // / output pair together name one family & the first match is the right match.
    foreach ($conversionFamilies as $arrKey => $familyFormats) {
      if (!is_array($familyFormats[0]) or !is_array($familyFormats[1])) continue;
      if (!in_array(strtolower($oldExtension), $familyFormats[0])) continue;
      if (!in_array(strtolower($UserExtension), $familyFormats[1])) continue;
      $isExtensionSupported = TRUE;
      list ($conversionSuccess, $conversionErrors, $newPathname, $extension, $convertedFilename) = convert($arrKey, $pathname, $newPathname, $UserExtension, $Height, $Width, $Rotate, $Bitrate);
      if ($conversionErrors) {
        $MainConversionErrors = TRUE;
        logEntry($arrKey.' conversion finished with errors.'); }
      if ($Verbose) logEntry($arrKey.' Conversion Complete.');
      break; }
    // / An unsupported combination never reached a converter at all.
    if (!$isExtensionSupported) {
      $MainConversionErrors = TRUE;
      errorEntry('The conversion '.$oldExtension.' to '.$UserExtension.' is not supported!', 5006, FALSE);
      continue; }
    // / Record that at least one file in this request launched a stream.
    // / $WaitForStream is reset on every iteration, so without this the core would only
    // / ever see the value belonging to the LAST file & would abandon an earlier stream.
    if ($WaitForStream) $anyStreamStarted = TRUE;
    // / The converter already failed & already logged the reason it failed.
    // / Checking for an output file here would only report the same failure a second time.
    if (!$conversionSuccess) {
      $MainConversionErrors = TRUE;
      continue; }
    // / A backgrounded stream is judged by its launch, everything else by its output file.
    $outputExists = $WaitForStream ? TRUE : file_exists($newPathname);
    if ($outputExists) {
      $fileConversionSuccess = TRUE;
      // / The name is returned, so the caller decides what is done with it.
      $ConvertedFiles[] = $convertedFilename;
      if ($Verbose) logEntry('Created a file at '.$newPathname.'.'); }
    else {
      $MainConversionErrors = TRUE;
      errorEntry('Could not create '.$newPathname.' from '.$oldPathname.'!', 5005, FALSE); }
    // / Record that at least one file in this request converted successfully.
    // / This is aggregated for the same reason $anyStreamStarted is.
    if ($fileConversionSuccess) $MainConversionSuccess = TRUE; }
  // / Restore the aggregate so the core can supervise a stream started by ANY file.
  $WaitForStream = $anyStreamStarted;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $familyFormats, $conversionFamilies, $file, $pathname, $oldPathname, $oldExtension, $newPathname, $fileIsVerified, $scanComplete, $virusFound, $variableIsSanitized, $arrKey, $clean, $copy, $skip, $isExtensionSupported, $outputExists, $conversionSuccess, $conversionErrors, $fileConversionSuccess, $anyStreamStarted, $convertedFilename, $extension, $verifiedFilename);
  return array($MainConversionSuccess, $MainConversionErrors, $ConvertedFiles); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to create the user virus logfiles if required.
// / Type can be 'clamav', 'scancore' or 'all'.
function verifyUserVirusLogs($type) {
  // / Set variables.
  global $Verbose, $Time, $UserClamLogFile, $UserScanCoreLogFile, $SesHash3, $Lol, $Append, $UserScanCoreFileName, $EnableMemoryProtection;
  $LogsExist = FALSE;
  $userClamLogFileName = $userScanCoreLogFileName = '';
  // / Verify the User Clam Log File if needed.
  // / A scan with every scanner resets both logs, so its report carries no earlier scan's lines.
  if ($type === 'clamav' or $type === 'all') {
    // / Remove the old User ClamAV Virus Log file if one already exists.
    if (file_exists($UserClamLogFile)) {
      if ($Verbose) logEntry('Deleting stale file '.$UserClamLogFile.'.');
      @unlink($UserClamLogFile); }
    // / Make sure that the stale file was deleted if required or creating a new one will cause problems.
    if (file_exists($UserClamLogFile)) errorEntry('Could not delete stale file '.$UserClamLogFile.'!', 16000, TRUE);
    else file_put_contents($UserClamLogFile, 'Op-Act, '.$Time.', '.$SesHash3.': Created a User Clam Log File.'.$Lol, $Append);
    // / Make sure that the file was successfully replaced.
    if (!file_exists($UserClamLogFile)) errorEntry('Could not create a file at '.$UserClamLogFile.'!', 16001, TRUE);
    else {
      $LogsExist = TRUE;
      if ($Verbose) logEntry('Created a file at '.$UserClamLogFile.'.'); } }
  // / Verify the User ScanCore Log File if needed.
  if ($type === 'scancore' or $type === 'all') {
    // / Remove the old User ScanCore Virus Log file if one already exists.
    if (file_exists($UserScanCoreLogFile)) {
      if ($Verbose) logEntry('Deleting stale file '.$UserScanCoreLogFile.'.');
      @unlink($UserScanCoreLogFile); }
    // / Make sure that the stale file was deleted if required or creating a new one will cause problems.
    if (file_exists($UserScanCoreLogFile)) errorEntry('Could not delete stale file '.$UserScanCoreLogFile.'!', 16002, TRUE);
    else file_put_contents($UserScanCoreLogFile, 'Op-Act, '.$Time.', '.$SesHash3.': Created a User ScanCore Log File.'.$Lol, $Append);
    // / Make sure that the file was successfully replaced.
    if (!file_exists($UserScanCoreLogFile)) errorEntry('Could not create a file at '.$UserScanCoreLogFile.'!', 16003, TRUE);
    else {
      $LogsExist = TRUE;
      if ($Verbose) logEntry('Created a file at '.$UserScanCoreLogFile.'.'); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $type, $userClamLogFileName, $userScanCoreLogFileName);
  return array($LogsExist, $UserClamLogFile, $UserScanCoreLogFile); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to format a log entry & write it to the logfile.
// / Type can be either 'clamav' or 'scancore'.
function userVirusLogEntry($Entry, $type) {
  // / Set variables.
  global $Time, $UserClamLogFile, $UserScanCoreLogFile, $SesHash3, $Lol, $Append, $EnableMemoryProtection;
  $LogWritten = $logWrittenA = $logWrittenB = FALSE;
  // / Format the input string into a log entry & write it to the $UserClamLogFile.
  if ($type === 'clamav') $logWrittenA = file_put_contents($UserClamLogFile, 'Op-Act, '.$Time.', '.$SesHash3.': '.$Entry.$Lol, $Append);
  // / Format the input string into a log entry & write it to the $UserScanCoreLogFile.
  if ($type === 'scancore') $logWrittenB = file_put_contents($UserScanCoreLogFile, 'Op-Act, '.$Time.', '.$SesHash3.': '.$Entry.$Lol, $Append);
  // / Check that a log entry was written.
  if ($type === 'clamav') if ($logWrittenA) $LogWritten = TRUE;
  if ($type === 'scancore') if ($logWrittenB) $LogWritten = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $type, $logWrittenA, $logWrittenB);
  return $LogWritten; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to scan user supplied files on demand with one scanner.
// / Accepts the files to scan, the scanner's name as runVirusScan knows it & the name a
// / user reads. Returns whether any file was given a verdict, whether anything went wrong
// / & whether any file was found infected, in that order.
// / Every scan goes through runVirusScan, the dispatcher that uploads, fetched files & the OCR pipeline already use.
// / The user is told a verdict & nothing else, which is clean, infected or not scanned, & which of their own files.
// / What a scanner said is counted for the administrator's log & discarded.
// / Each file is judged on its own.
function userScanWithScanner($FilesToScan, $scannerName, $scannerLabel) {
  // / Set variables.
  global $Verbose, $ConvertDir, $EnableMemoryProtection;
  $OperationSuccessful = $OperationErrors = $UserVirusFound = FALSE;
  $userFilename = $userExtension = $clean = $copy = $variableIsSanitized = $fileIsVerified = FALSE;
  $scanCompleted = $threatWasFound = FALSE;
  $skip = TRUE;
  $scanFindings = array();
  $file = $txt = $scannerUsed = $pathname = $oldPathname = $oldExtension = $newPathname = $verifiedFilename = '';
  $sanitizeError = $verifyError = 0;
  // / Each scanner keeps the error numbers it has always had, so a log still says which one.
  $sanitizeError = ($scannerName === 'clamav') ? 17000 : 19000;
  $verifyError = ($scannerName === 'clamav') ? 17001 : 19001;
  $txt = 'Initiating User Virus Scan with '.$scannerLabel.'.';
  userVirusLogEntry($txt, $scannerName);
  if ($Verbose) logEntry($txt);
  if (!is_array($FilesToScan)) $FilesToScan = array($FilesToScan);
  // / Without the Pipeline Core there is no scanner to ask, & nothing is reported as clean.
  if (!function_exists('runVirusScan')) {
    $OperationErrors = TRUE;
    errorEntry('The Pipeline Core is unavailable, so the user virus scan could not run!', 506, FALSE);
    userVirusLogEntry('No scanner could be reached. NO FILE WAS SCANNED.', $scannerName);
    $FilesToScan = array(); }
  foreach ($FilesToScan as $file) {
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $OperationErrors = TRUE;
      errorEntry('Could not sanitize the input file!', $sanitizeError, FALSE);
      continue; }
    if ($Verbose) logEntry('User selected to perform a '.$scannerLabel.' scan on file '.$file.'.');
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname, $verifiedFilename) = verifyFile($file, $userFilename, $userExtension, $clean, $copy, $skip);
    if (!$fileIsVerified) {
      $OperationErrors = TRUE;
      errorEntry('Could not verify the input file.', $verifyError, FALSE);
      continue; }
    $txt = 'Scanning '.$file.'.';
    if ($Verbose) logEntry($txt);
    userVirusLogEntry($txt, $scannerName);
    list ($scanCompleted, $threatWasFound, $scanFindings, $scannerUsed) = runVirusScan(array($ConvertDir.$file), $scannerName);
    if ($Verbose) logEntry($scannerLabel.' reported '.count($scanFindings).' finding(s) for '.$file.'.');
    // / A scan that did not finish is not a clean result & is never reported as one.
    if (!$scanCompleted) {
      $OperationErrors = TRUE;
      warningEntry($scannerLabel.' could not scan '.$file.' for a user.');
      userVirusLogEntry($file.' could not be scanned. No verdict was reached.', $scannerName); }
    else if ($threatWasFound) {
      $OperationSuccessful = $UserVirusFound = TRUE;
      $txt = 'Potentially infected file detected at '.$file.'!';
      warningEntry($txt);
      userVirusLogEntry($txt, $scannerName); }
    else {
      $OperationSuccessful = TRUE;
      $txt = 'No infection detected in '.$file.'.';
      if ($Verbose) logEntry($txt);
      userVirusLogEntry($txt, $scannerName); } }
  // / A scan that gave no file a verdict does not get to end with the word Complete.
  $txt = $OperationSuccessful ? $scannerLabel.' Virus Scan Complete.' : $scannerLabel.' Virus Scan DID NOT RUN.';
  if ($Verbose) logEntry($txt);
  userVirusLogEntry($txt, $scannerName);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $userFilename, $userExtension, $clean, $copy, $variableIsSanitized, $fileIsVerified, $scanCompleted, $threatWasFound, $skip, $scanFindings, $file, $txt, $scannerUsed, $pathname, $oldPathname, $oldExtension, $newPathname, $verifiedFilename, $sanitizeError, $verifyError, $scannerName, $scannerLabel);
  return array($OperationSuccessful, $OperationErrors, $UserVirusFound); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to process the results of a User Virus Scan & check for any failures or errors.
// / Type can be either 'clamav', 'scancore', or 'all'.
function checkUserVirusScanResults($type, $scan1Complete, $scan1Errors, $scan2Complete, $scan2Errors) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ScanComplete = $ScanErrors = FALSE;
  // / Check that all the input check results are valid.
  if (!is_bool($scan1Complete)) $scan1Complete = FALSE;
  if (!is_bool($scan1Errors)) $scan1Errors = FALSE;
  if (!is_bool($scan2Complete)) $scan2Complete = FALSE;
  if (!is_bool($scan2Errors)) $scan2Errors = FALSE;
  // / A scanner's report is always delivered, even one saying the scan did not run, so the operation is complete & the errors flag carries a failure.
  // / A scanner that gave no file a verdict has already set its own errors flag.
  // / Both scanners are held to that in 'all' mode too.
  if ($type == 'all') {
    $ScanComplete = TRUE;
    if ($scan1Errors or $scan2Errors) $ScanErrors = TRUE; }
  // / Set results using only ClamAV output.
  // / A test of the scan's own completion sat here after the line that had already set it &
  // / decided nothing.
  if ($type == 'clamav') {
    $ScanComplete = TRUE;
    if ($scan1Errors) $ScanErrors = TRUE; }
  // / Set results using only ScanCore output.
  if ($type == 'scancore') {
    $ScanComplete = TRUE;
    if ($scan2Errors) $ScanErrors = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $type, $scan1Complete, $scan1Errors, $scan2Complete, $scan2Errors);
  return array($ScanComplete, $ScanErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to define & verify that a Consolidated User Virus Log File exists.
function verifyConsolidatedLogFile() {
  // / Set variables.
  global $Verbose, $ConsolidatedLogFile, $Append;
  $ConsolidatedLogsExist = FALSE;
  // / Remove the old Consolidated Virus Log file if one already exists.
  if (file_exists($ConsolidatedLogFile)) {
    if ($Verbose) logEntry('Deleting stale consolidated log file.');
    @unlink($ConsolidatedLogFile); }
  // / Make sure that the stale file was deleted if required or creating a new one will cause problems.
  if (file_exists($ConsolidatedLogFile)) errorEntry('Could not delete stale file '.$ConsolidatedLogFile.'!', 20000, TRUE);
  // / Attempt to create a new consolidated log if the previous one was successfully removed.
  else file_put_contents($ConsolidatedLogFile, '', $Append);
  // / Make sure that the file was successfully replaced.
  if (!file_exists($ConsolidatedLogFile)) errorEntry('Could not create a file at '.$ConsolidatedLogFile.'!', 20001, TRUE);
  else {
    $ConsolidatedLogsExist = TRUE;
    if ($Verbose) logEntry('Created a file at '.$ConsolidatedLogFile.'.'); }
  return array($ConsolidatedLogsExist, $ConsolidatedLogFile); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to consolidate User Virus Scan log files generated via various methods into one meaningful report.
// / Type can be either 'clamav', 'scancore', or 'all'.
function consolidateLogs($type, $UserClamLogFile, $UserScanCoreLogFile) {
  // / Set variables.
  // / The two user logs are the arguments & are not declared global. Declaring them global
  // / as well replaced the arguments with the globals, so whatever a caller passed was ignored.
  global $Lol, $Append, $ConsolidatedLogFile, $ConsolidatedLogFileName, $EnableMemoryProtection;
  $ConsolidatedLogsExist = $ConsolidatedLogErrors = $logWrittenA = $logWrittenB = $logWrittenC = $logWrittenD = $logWrittenE = FALSE;
  $userClamLogData = $userScanCoreData = $consolidatedLogData = $txt = $userScanCoreLogData = '';
  $spacer = '----------';
  list ($ConsolidatedLogsExist, $ConsolidatedLogFile) = verifyConsolidatedLogFile();
  if ($type === 'clamav') {
    // / Load the User Clam Log File into memory.
    $userClamLogData = file_get_contents($UserClamLogFile);
    $logWrittenA = file_put_contents($ConsolidatedLogFile, $userClamLogData.$Lol.$spacer.$Lol, $Append); }
  if ($type === 'scancore') {
    // / Load the User Scan Core Log File into memory.
    $userScanCoreLogData = file_get_contents($UserScanCoreLogFile);
    $logWrittenB = file_put_contents($ConsolidatedLogFile, $userScanCoreLogData.$Lol.$spacer.$Lol, $Append); }
  if ($type === 'all') {
    // / Head the report, then append both scanners' logs beneath it.
    $txt = 'User selected to scan files with all available scanners.';
    $logWrittenC = file_put_contents($ConsolidatedLogFile, $txt.$Lol.$spacer.$Lol, $Append);
    $userClamLogData = file_get_contents($UserClamLogFile);
    $logWrittenD = file_put_contents($ConsolidatedLogFile, $userClamLogData.$Lol.$spacer.$Lol, $Append);
    $userScanCoreLogData = file_get_contents($UserScanCoreLogFile);
    $logWrittenE = file_put_contents($ConsolidatedLogFile, $userScanCoreLogData.$Lol.$spacer.$Lol, $Append); }
  // / Check to be sure that the $ConsolidatedLogFile exists.
  if ($type === 'clamav' && !$logWrittenA) $ConsolidatedLogErrors = TRUE;
  if ($type === 'scancore' && !$logWrittenB) $ConsolidatedLogErrors = TRUE;
  if ($type === 'all') if (!$logWrittenC or !$logWrittenD or !$logWrittenE) $ConsolidatedLogErrors = TRUE;
  if (file_exists($ConsolidatedLogFile)) $ConsolidatedLogsExist = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $consolidatedLogData, $userScanCoreData, $type, $txt, $spacer, $logWrittenA, $logWrittenB, $logWrittenC, $logWrittenD, $logWrittenE, $userClamLogData, $userScanCoreLogData);
  return array($ConsolidatedLogsExist, $ConsolidatedLogErrors, $ConsolidatedLogFile, $ConsolidatedLogFileName); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to scan the files a user selected with ClamAV, ScanCore or both, & build the
// / report they download.
// / Type can be either 'clamav', 'scancore', or 'all'.
function userVirusScan($FilesToScan, $type) {
  // / Set variables.
  global $UserClamLogFile, $UserScanCoreLogFile, $ConsolidatedLogFile, $ConsolidatedLogFileName, $EnableMemoryProtection;
  $ScanComplete = $ScanErrors = $UserVirusFound = $scan1Complete = $scan1Errors = $scan2Complete = $scan2Errors = $ConsolidatedLogsExist = $ConsolidatedLogErrors = FALSE;
  $clamVirusFound = $scanCoreVirusFound = $logsExist = FALSE;
  // / Check that the $type input variable is valid.
  if ($type !== 'all' && $type !== 'clamav' && $type !== 'scancore') $type = 'all';
  // / Make sure the input files are formatted into an array.
  if (!is_array($FilesToScan)) $FilesToScan = array($FilesToScan);
  list ($logsExist, $UserClamLogFile, $UserScanCoreLogFile) = verifyUserVirusLogs($type);
  // / Each scanner is run once, over the whole list, because a virus scan draws on a resource budget & runs under a ceiling.
  // /
  // / A detection from either scanner is a detection.
  // / Each scanner reports into its own variable & the user is given the OR of them, because the two carry different definitions, which is the reason for offering both.
  if ($type === 'clamav' or $type === 'all') {
    // / Prepare to run a ClamAV Scan.
    list ($scan1Complete, $scan1Errors, $clamVirusFound) = userScanWithScanner($FilesToScan, 'clamav', 'ClamAV');
    if ($clamVirusFound) $UserVirusFound = TRUE; }
  // / Perform a User Virus Scan using ScanCore if required.
  if ($type === 'scancore' or $type === 'all') {
    // / Prepare to run a ScanCore Scan.
    list ($scan2Complete, $scan2Errors, $scanCoreVirusFound) = userScanWithScanner($FilesToScan, 'scancore', 'ScanCore');
    if ($scanCoreVirusFound) $UserVirusFound = TRUE; }
  // / Check the results of the virus scan for failures or errors.
  list ($ScanComplete, $ScanErrors) = checkUserVirusScanResults($type, $scan1Complete, $scan1Errors, $scan2Complete, $scan2Errors);
  // / Consolidate the log files created during the scan into the $ConvertTempDir so the user can access them.
  list ($ConsolidatedLogsExist, $ConsolidatedLogErrors, $ConsolidatedLogFile, $ConsolidatedLogFileName) = consolidateLogs($type, $UserClamLogFile, $UserScanCoreLogFile);
  // / Verify that all operations are complete.
  if ($ScanErrors or $ConsolidatedLogErrors) $ScanErrors = TRUE;
  if (!$ConsolidatedLogsExist) $ScanComplete = FALSE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $logsExist, $clamVirusFound, $scanCoreVirusFound, $type, $scan1Complete, $scan1Errors, $scan2Complete, $scan2Errors);
  return array($ScanComplete, $ScanErrors, $UserVirusFound, $ConsolidatedLogFile, $ConsolidatedLogFileName); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to correct ownership & permissions on every managed path.
// / Accepts no arguments & must be run as root.
// / Returns a success boolean & the number of paths corrected, in that order.
function fixManagedPermissions() {
  // / Set variables.
  global $ApacheUser, $Lol, $RunningAsRoot, $EnablePerConversionLimits, $RequiredSetupCoreVersion, $EnableMemoryProtection, $DirSep, $DeleteBuildEnvironment, $DeleteDevelopmentDocumentation, $InstLoc, $EngineRepairIsUnattended, $EnvironmentManagerMayRewriteConfigs, $HostedLoc, $ApplicationLoc, $ConvertTemp, $SecretFile, $SecretKey, $SecretLoc, $RequiredSecretVersion;
  $buildEnvCleaned = $buildEnvDeleted = $devDocsDeleted = $codeIsTrustedNow = $repairIsUnattended = $configsMayChange = $scriptIsValid = $secretIsReady = FALSE;
  $placedSecretKey = '';
  $codeTrustNotice = $scriptStatus = $scriptDisplayStatus = $scriptDetail = $hostedStatus = $hostedDetail = '';
  $hostedWritten = $hostedRemoved = 0;
  // / The policy sweep collapses to a count & names only what is not in place.
  $policyLines = $policyChecks = array();
  $policyName = $policyLine = '';
  $policiesOk = $policiesTotal = 0;
  $PermissionsWereFixed = $limitsWereEnabled = $policyIsValid = $setupIsAvailable = $environmentIsReady = FALSE;
  $exposureStatus = '';
  $setupVersion = '';
  $environmentFindings = $kernelFindings = array();
  $kernelIsReady = $phpConfigIsValid = $apacheConfigIsValid = FALSE;
  $phpConfigsWritten = $apacheConfigsWritten = 0;
  $PathsCorrected = $limitSteps = 0;
  $policyStatus = '';
  $pathCorrectionRan = $provisioningReady = FALSE;
  $pathsRefused = $sessionsMoved = 0;
  if (!$RunningAsRoot) errorEntry('Permissions can only be corrected while running as root!', 31008, FALSE);
  else {
    // / An unattended repair is the Environment Manager's, & nobody is watching it.
    // / The Engine says so through $EngineRepairIsUnattended.
    // / Such a repair starts & enables no service, because an administrator who stopped one has a reason.
    // / It rewrites the host configuration files this application owns only when --Environment Manager May Rewrite Configs-- allows.
    // / Those are the PHP drop in, the Apache configuration, the sandbox kernel settings & the three policies.
    // / Everything else is exactly what -fp does, so there is still one definition of a repair.
    $repairIsUnattended = (isset($EngineRepairIsUnattended) && $EngineRepairIsUnattended === TRUE);
    $configsMayChange = (!$repairIsUnattended or (isset($EnvironmentManagerMayRewriteConfigs) && $EnvironmentManagerMayRewriteConfigs === TRUE));
    if ($repairIsUnattended) print($Lol.'Unattended repair. No service is started or enabled.'.($configsMayChange ? '' : ' Host configuration files are checked & left as they are.').$Lol);
    // / Setup Core is loaded before anything asks it for something.
    list ($setupIsAvailable, $setupVersion) = verifyCoreComponent('Setup Core', 'Engine'.$DirSep.'Cores'.$DirSep.'setupCore.php', 'SetupCoreVersion', $RequiredSetupCoreVersion);
    // / Which locations this application keeps & what each one is are in its contract, as
    // / $EngineManagedLocations. What a role means for an owner & a mode is Setup Core's.
    // / The install secret is not one of them. Root's boot has already read it & rewritten it
    // / without following anything, before this function was ever called. The folder it is kept
    // / in, Secrets, is one, & the secret is copied into it below once the folder has its role.
    print($Lol.'Managed locations'.$Lol);
    // / Create the data root's folders & move an older layout's sessions into Sessions before
    // / the roles are applied, so -fp after an upgrade reaches the same shape as a fresh install
    // / & keeps every open session. This is the one creating & migrating step; correctManagedLocations
    // / only corrects what exists. It is skipped in silence when Setup Core is unavailable, which
    // / the location pass reports on its own line below.
    if ($setupIsAvailable && function_exists('provisionAndMigrateDataRoot')) list ($provisioningReady, $sessionsMoved) = provisionAndMigrateDataRoot($ApacheUser);
    if ($setupIsAvailable && function_exists('correctManagedLocations')) list ($pathCorrectionRan, $PathsCorrected, $pathsRefused) = correctManagedLocations($ApacheUser);
    // / correctManagedLocations names its own reason when it runs & refuses, so only the case
    // / where it never ran is reported here.
    if (!$setupIsAvailable or !function_exists('correctManagedLocations')) warningEntry('Setup Core is unavailable, so no managed location could be corrected.');
    print('  '.$PathsCorrected.' location(s) corrected'.($pathsRefused > 0 ? ', '.$pathsRefused.' refused or incomplete. See the lines above.' : '.').$Lol);
    // / The install secret is copied into Secrets with its key, now that the folder has its role.
    // / Root's boot read it where an older release kept it, before this command made the folder, so an upgrade finishes in this one run.
    // / The copy at the top is kept with the same key, & a copy that is already right is left as it is.
    // / $SecretKey is the documented exception to convention 3, & is assigned here because the key root holds must be the one on disk.
    if ($setupIsAvailable) {
      list ($secretIsReady, $placedSecretKey) = resolveInstallSecretAsRoot($SecretFile, $RequiredSecretVersion, FALSE);
      if ($secretIsReady) $SecretKey = $placedSecretKey;
      if ($secretIsReady && is_file($SecretFile)) print('  Install secret in '.$SecretLoc.'. Group '.$ApacheUser.' may read it.'.$Lol);
      else print('  Install secret NOT in '.$SecretLoc.' yet. See the lines above & root\'s log.'.$Lol); }
    // / Whether root may run the installation now, read exactly as every command run as root reads it before anything is loaded.
    // / A tree that still fails is named here, once.
    // / -fp gives every name root's owner & mode, & leaves a link or a file with more than one name where it is, so what fails now is something to remove.
    list ($codeIsTrustedNow, $codeTrustNotice) = treeIsTrustedByRoot($InstLoc, array($InstLoc.$DirSep.'DATA'));
    if ($codeIsTrustedNow) print('  Installation is root\'s. Root may run it.'.$Lol);
    else {
      warningEntry('-fp did not leave the installation one root may run. '.$codeTrustNotice.' -fp leaves links, files with more than one name & other filesystems as they are. Remove or put right what is named & run -fp again.');
      print('  Root may NOT run the installation yet. '.$codeTrustNotice.$Lol.'  -fp leaves links, hard links & other filesystems as they are.'.$Lol.'  Remove or correct what is named & run -fp again.'.$Lol); }
    // / The build environment & the development documentation go here, when config.php asks for them to go.
    // / Only root can remove them, because the code is root's, & straight after the code was given to root is the moment.
    if ($DeleteBuildEnvironment or $DeleteDevelopmentDocumentation) {
      print($Lol.'Build environment'.$Lol);
      // / The cleanup is Setup Core's, loaded at the top of this function.
      if ($setupIsAvailable && function_exists('cleanBuildEnvironment')) list ($buildEnvCleaned, $buildEnvDeleted, $devDocsDeleted) = cleanBuildEnvironment();
      else warningEntry('Setup Core is unavailable, so the build environment was not cleaned.');
      if (!$buildEnvCleaned) errorEntry('Could not clean the build environment!', 26, FALSE);
      if ($DeleteBuildEnvironment) print('  '.str_pad('Build & Dockerfile', 28).($buildEnvDeleted ? 'NOT PRESENT' : 'FAILED, see the log').$Lol);
      if ($DeleteDevelopmentDocumentation) print('  '.str_pad('README.md & CHANGELOG.txt', 28).($devDocsDeleted ? 'NOT PRESENT' : 'FAILED, see the log').$Lol); }
    // / The bootstrap script, which the Engine pins exactly.
    // / One from another release is disabled where it stands, & one that matches again is enabled.
    // / It lives inside the installation, so this waits until root may run the installation.
    // / It is not host configuration, so an unattended repair does it too.
    print($Lol.'Bootstrap script'.$Lol);
    if (!$codeIsTrustedNow) print('  Skipped     Root may not change the installation yet. See the lines above.'.$Lol);
    else if (!function_exists('repairBootstrapScript')) print('  Skipped     The Engine is unavailable.'.$Lol);
    else {
      list ($scriptIsValid, $scriptStatus) = repairBootstrapScript();
      list ($scriptDisplayStatus, $scriptDetail) = describeBootstrapStatus($scriptStatus);
      print('  '.str_pad($scriptDisplayStatus, 12).$scriptDetail.$Lol); }
    // / The hosted location is what the web server serves. Once the application runs from its
    // / folder in the data location, the loader, the protection page & the static files there are
    // / put in step, & every copy of the application left there is removed. A tree that is not
    // / installed yet is installed as this command ends, & the installed copy does this then. It
    // / is this application's own files rather than host configuration, so an unattended repair
    // / does it too, as it keeps the DATA protection file.
    print($Lol.'Hosted location'.$Lol);
    if (!$codeIsTrustedNow) print('  Skipped     Root may not change the installation yet. See the lines above.'.$Lol);
    else if (!function_exists('publishHostedLocation')) print('  Skipped     The Setup Core is unavailable.'.$Lol);
    else if ((string)@realpath($InstLoc) !== (string)@realpath($ApplicationLoc)) print('  '.str_pad('Not yet', 12).'Installed into '.$ApplicationLoc.' as -fp ends.'.$Lol);
    else {
      list ($hostedStatus, $hostedDetail, $hostedWritten, $hostedRemoved) = publishHostedLocation($HostedLoc, $InstLoc, TRUE);
      print('  '.str_pad(($hostedStatus === 'repaired' ? 'Published' : $hostedStatus), 12).$hostedDetail.$Lol);
      // / DATA is the one name in the hosted location the web server account owns. A hosted location
      // / made just now has none, & every role was given before it existed, so DATA is made & given
      // / its role here, before anything else writes into it as root.
      if (!is_dir($ConvertTemp) && is_dir($HostedLoc) && function_exists('applyWebRole') && createHeldFolder($ConvertTemp) && applyWebRole($ConvertTemp, $ApacheUser)) print('  '.str_pad('Created', 12).$ConvertTemp.', owned by '.$ApacheUser.'.'.$Lol); }
    // / Install or repair every policy file while we are still root. A conversion pipeline
    // / reports a broken policy on every request & can never fix one, because rewriting a
    // / system policy from a web request is not something this application should ever do.
    print($Lol.'PHP configuration'.$Lol);
    list ($phpConfigIsValid, $phpConfigsWritten) = verifyPhpConfiguration($configsMayChange);
    // / The DATA rules belong in server configuration rather than in a .htaccess, so they
    // / are written, tested & activated here. The .htaccess further down stays as the
    // / fallback for an installation this cannot configure.
    print($Lol.'Apache configuration'.$Lol);
    // / The configuration is Setup Core's, loaded at the top of this function.
    if ($setupIsAvailable && function_exists('verifyApacheConfiguration')) list ($apacheConfigIsValid, $apacheConfigsWritten) = verifyApacheConfiguration($configsMayChange);
    else print('  Skipped     The Setup Core is unavailable.'.$Lol);
    print($Lol.'Sandbox kernel'.$Lol);
    list ($kernelIsReady, $kernelFindings) = verifySandboxKernel($configsMayChange);
    showEnvironmentFindings($kernelFindings, FALSE);
    // / Policy files, as a count when every one is in place & a named line when one is not.
    // / Half of these status words read like a fault & are not one, so a line that does appear carries the explanation with it.
    // / A policy this run changed on the host is named too, including one an earlier release wrote & this one put back.
    // / The operator is owed that whether or not a terminal shows the warning that says it.
    $policyChecks = array('Sandbox AppArmor', 'ImageMagick', 'OpenSCAD AppArmor');
    foreach ($policyChecks as $policyName) {
      $policiesTotal++;
      if ($policyName === 'Sandbox AppArmor') list ($policyIsValid, $policyStatus) = verifySandboxPolicy($configsMayChange);
      else if ($policyName === 'ImageMagick') list ($policyIsValid, $policyStatus) = verifyImageMagickPolicy($configsMayChange);
      else list ($policyIsValid, $policyStatus) = verifyOpenScadPolicy($configsMayChange);
      if ($policyIsValid) $policiesOk++;
      if (!$policyIsValid or in_array($policyStatus, array('installed', 'repaired', 'retired', 'kept'), TRUE)) array_push($policyLines, '  '.str_pad($policyName, 22).str_pad(policyDisplayStatus($policyStatus), 14).describePolicyStatus($policyName, $policyStatus)); }
    print($Lol.'Policy files     '.$policiesOk.' of '.$policiesTotal.' OK'.$Lol);
    foreach ($policyLines as $policyLine) print($policyLine.$Lol);
    // / The DATA tree protection file.
    // / It is the only policy this application maintains that governs its own web root rather than a converter.
    // / It is also the only one whose installation proves nothing on its own, so it is verified separately below.
    // / The protection file is the Engine's, as policyDisplayStatus & describePolicyStatus are.
    if (function_exists('verifyDataProtectionPolicy')) {
      list ($policyIsValid, $policyStatus) = verifyDataProtectionPolicy(TRUE);
      print('  '.str_pad('DATA Directory', 22).str_pad(policyDisplayStatus($policyStatus), 14).describePolicyStatus('DATA Directory', $policyStatus).$Lol); }
    // / Give the web server user its own systemd manager while we are still root.
    // / This is the only moment the application legitimately holds the privilege needed,
    // / & it is what lets an unprivileged account set a resource ceiling later.
    // / An unattended repair skips this, because lingering starts the web server account's own
    // / service manager, & an administrator who stopped it has a reason.
    if ($EnablePerConversionLimits) {
      print($Lol.'Per conversion resource limits'.$Lol);
      // / Setup Core owns the limits work now. It was loaded at the top of this function.
      if ($repairIsUnattended) print('  Skipped     An unattended repair starts nothing. Run -fp to set these up.'.$Lol);
      else if (!$setupIsAvailable) print('  Skipped     The Setup Core component is unavailable.'.$Lol);
      else list ($limitsWereEnabled, $limitSteps) = enableConversionLimits();
      if (!$limitsWereEnabled && !$repairIsUnattended) warningEntry('Per conversion resource limits could not be fully enabled. '.$limitSteps.' step(s) completed.'); }
    // / The listener service unit, generated from this configuration. Setup Core owns it,
    // / so it is loaded on demand. An installation without that component simply skips it.
    // / An unattended repair writes the unit & neither enables nor starts it, so a listener an
    // / administrator stopped or disabled stays that way & is reported rather than restarted.
    print($Lol.'Listener service'.$Lol);
    if (!$setupIsAvailable) print('  Skipped     The Setup Core component is unavailable.'.$Lol);
    else installListenerService(!$repairIsUnattended, !$repairIsUnattended);
    // / The Environment Manager's units are written by --setup --install-complete, which may run from
    // / a copy it then installs, so they are written again wherever they are installed & name the
    // / installed copy, as the listener's does. Nothing enables or starts them here.
    if ($setupIsAvailable && function_exists('refreshEnvironmentTimer')) refreshEnvironmentTimer();
    // / Prove the repairs worked. Writing an AppArmor profile & never re-testing the
    // / sandbox is how an operator ends up with a green permissions run & a server that
    // / refuses every conversion.
    print($Lol.'Verifying the operating environment.'.$Lol);
    list ($environmentIsReady, $environmentFindings) = validateOperatingEnvironment();
    showEnvironmentFindings($environmentFindings, TRUE);
    if (!$environmentIsReady) print($Lol.'The sandbox is still unavailable. A new AppArmor profile may need an AppArmor'.$Lol.'reload or a reboot.'.$Lol);
    // / Writing the protection file above proved nothing, & the check that proves it is
    // / part of the environment report printed directly above this.
    // / A .htaccess is read only where AllowOverride is enabled, & where it is not, Apache
    // / ignores it in complete silence, so the tree is asked over HTTP with a canary rather
    // / than believed. The status is read back here only to decide whether an operator needs
    // / the longer explanation, which does not belong in a one line finding.
    $exposureStatus = environmentFindingStatus($environmentFindings, 'DATA exposure');
    if ($exposureStatus === 'BROKEN') {
      print($Lol.'  THE DATA DIRECTORY IS RETURNING A SERVER ERROR.'.$Lol);
      print('  Downloads & share links are down. This is an outage, not exposure.'.$Lol);
      print('  Likely cause: a directive in DATA/.htaccess that AllowOverride does not permit.'.$Lol);
      print('  The web server error log names the line. Removing DATA/.htaccess restores'.$Lol);
      print('  service. Put the rules in the server configuration instead.'.$Lol); }
    else if ($exposureStatus === 'EXPOSED') {
      print($Lol.'  THE DATA DIRECTORY IS EXPOSED.'.$Lol);
      print('  Uploads are served as documents, not downloads. An uploaded SVG runs script'.$Lol);
      print('  in this origin. DATA/.htaccess is ignored where AllowOverride does not permit it.'.$Lol);
      print('  Put the rules in the server configuration.'.$Lol);
      print('  See Documentation/ABOUT_DATA_DIRECTORY_PROTECTION.txt.'.$Lol); }
    else if ($exposureStatus === 'UNREACHABLE' or $exposureStatus === 'UNVERIFIED') {
      print($Lol.'  THE EXPOSURE OF THIS INSTALLATION WAS NOT ESTABLISHED.'.$Lol);
      print('  This is not a pass. Open the DATA URL of an uploaded file in a browser.'.$Lol);
      print('  It must download, not render.'.$Lol); }
    // / A repair that leaves an installation root may not run has not finished its job, so it
    // / does not report success. Everything else above it still ran.
    $PermissionsWereFixed = $codeIsTrustedNow;
    logEntry('Permissions were corrected on '.$PathsCorrected.' managed path(s).'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $secretIsReady, $placedSecretKey, $hostedStatus, $hostedDetail, $hostedWritten, $hostedRemoved, $buildEnvCleaned, $buildEnvDeleted, $devDocsDeleted, $codeIsTrustedNow, $repairIsUnattended, $configsMayChange, $scriptIsValid, $scriptStatus, $scriptDisplayStatus, $scriptDetail, $codeTrustNotice, $pathCorrectionRan, $pathsRefused, $provisioningReady, $sessionsMoved, $limitsWereEnabled, $limitSteps, $policyIsValid, $policyStatus, $exposureStatus, $setupIsAvailable, $setupVersion, $environmentIsReady, $environmentFindings, $kernelIsReady, $kernelFindings, $phpConfigIsValid, $phpConfigsWritten, $apacheConfigIsValid, $apacheConfigsWritten, $policyLines, $policyChecks, $policyName, $policyLine, $policiesOk, $policiesTotal);
  return array($PermissionsWereFixed, $PathsCorrected); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read one line of input from the operator.
// / Accepts the prompt to display.
// / Returns the trimmed response, or an empty string when input is unavailable.
// / This lives in the core rather than in a component, because more than one component
// / PROMPTS. It began in Setup Core, & Dependency Core calling it meant every dependency
// / install fataled on an undefined function, because --setup loads Dependency Core & does
// / not load Setup Core. A helper two components need belongs to neither of them.
function askOperator($promptText) {
  // / Set variables.
  global $EnableMemoryProtection;
  $OperatorResponse = '';
  $inputHandle = FALSE;
  print($promptText);
  $inputHandle = @fopen('php://stdin', 'r');
  if ($inputHandle !== FALSE) {
    $OperatorResponse = trim((string)fgets($inputHandle));
    @fclose($inputHandle); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $inputHandle, $promptText);
  return $OperatorResponse; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The main logic of the program that makes use of the functions above.

// / The kernel is checked, then loaded, before anything else runs.
// / Everything below calls into it, quickDie included, so this is the one place in the
// / application allowed to halt without quickDie, because a missing kernel takes quickDie with
// / it. Each halt prints its message & exits with status one. A plain die with a message exits
// / with status zero, which would report a missing kernel as a success to anything watching.
// / It is found beside THIS file rather than through $InstLoc, which comes from a
// / configuration that has not been read yet & may name a different installation entirely.
// / The kernel that belongs to this entry point is the one that shipped beside it.
// / The version is read with the same pattern readComponentVersion uses, because that
// / function lives in the kernel & so cannot be asked before it is loaded. A mismatched
// / kernel is never executed, the same guarantee a mismatched Engine gets.
// / The build cannot write this pin or the Engine's, because it runs inside both. Move them
// / by hand, with the version each file declares.
$RequiredKernelVersion = 'v4.1.2';
$KernelFile = dirname(__FILE__).DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'Kernel'.DIRECTORY_SEPARATOR.'kernel.php';
$KernelVersionMatch = array();
if (!is_readable($KernelFile)) {
  print('ERROR!!! HRConvert2-28006, The kernel is missing or unreadable at '.$KernelFile.'! Nothing can be loaded or logged without it.'.PHP_EOL);
  exit(1); }
if (!preg_match('/\$KernelVersion\s*=\s*\'([^\']+)\'/', (string)@file_get_contents($KernelFile), $KernelVersionMatch) or ltrim($KernelVersionMatch[1], 'vV') !== ltrim($RequiredKernelVersion, 'vV')) {
  print('ERROR!!! HRConvert2-28007, The kernel at '.$KernelFile.' reports '.(isset($KernelVersionMatch[1]) ? $KernelVersionMatch[1] : 'no version').' & this application requires '.$RequiredKernelVersion.'!'.PHP_EOL);
  exit(1); }
$KernelVersionMatch = array();
require_once ($KernelFile);

// / Root runs nothing an account other than root could have changed. The tree this file is in
// / is read as soon as the kernel that reads it is loaded, & before anything else in it runs.
// / The check is consumed & not acted on further here. verifyCodeIsTrustedByRoot halts every
// / command but a repair itself, says so, & a repair reads the tree again as it finishes, so
// / FALSE reaching this line means a repair that has already been warned about is running.
$CodeIsTrusted = verifyCodeIsTrustedByRoot(dirname(__FILE__));

// / The following code resets PHP's time limit for execution.
$TimeReset = setTimeLimit();
if (!$TimeReset) quickDie('Could not set the execution timer!', 3);

// / The following code sets date & time related variables.
list ($TimeIsSet, $Date, $Time, $EpochTime) = verifyTime();
if (!$TimeIsSet or !$Date or !$Time) quickDie('Could not verify timezone!', 4);

// / The following code verifies that the installation is valid.
list ($InstallationIsVerified, $ConfigFile, $Version, $CoreLoaded) = verifyInstallation();
if (!$InstallationIsVerified) quickDie('Could not verify installation!', 5);

// / The following code verifies that string inputs to the core are properly sanitized.
list ($InputsAreVerified, $ShowGUI, $GUI, $Color, $Language, $Token1, $Token2, $Height, $Width, $Rotate, $Bitrate, $Method, $Download, $UserFilename, $UserExtension, $FilesToArchive, $PDFWorkSelected, $ConvertSelected, $FilesToScan, $FilesToDelete, $UserScanType, $UserURLDownload, $FilesToIdentify, $IdentifyReport, $IdentifyFilename, $Sort, $WorkflowName, $WorkflowFiles, $WorkflowDocument, $WorkflowCheck, $WorkflowExport) = verifyInputs();
if (!$InputsAreVerified) quickDie('Could not verify inputs!', 6);

// / The following code verifies enough user information to generate a unique session identifier.
list ($SessionIsVerified, $IP, $HashedUserAgent) = verifySession();
if (!$SessionIsVerified) quickDie('Could not verify session!', 7);

// / The following code verifies the tokens supplied by the user, if any.
// / If no tokens were supplied by the user, generate new ones.
list ($TokensAreValid, $Token1, $Token2) = verifyTokens($Token1, $Token2);

// / The following code generates a series of unique session identifiers.
list ($SesHashIsVerified, $SesHash, $SesHash2, $SesHash3, $SesHash4) = verifySesHash($Token1);
if (!$SesHashIsVerified) quickDie('Could not verify unique session identifier!', 8);

// / The following code reads the Engine configuration, which is data rather than code.
// / It is read HERE, before the logging environment, & the Engine itself is verified &
// / loaded much later. Those are two different things & they are needed at two different
// / times. verifyLogs() decides the name of the logfile & some of that decision belongs to
// / the Engine, so the settings have to exist before it runs. The Engine's own functions
// / are not needed until a conversion or a command line report asks for one.
// / An absent configuration is not an error. Every setting it carries has a local fallback
// / & the Engine reports its own absence when it is verified.
$EngineConfigPath = $InstLoc.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'Engine'.DIRECTORY_SEPARATOR.'engineConfig.php';
if (file_exists($EngineConfigPath)) require_once ($EngineConfigPath);

// / The following code verifies the logging environment.
list ($LogFileExists, $LogFile) = verifyLogs();
if (!$LogFileExists) quickDie('Could not verify logging environment!', 9);
if ($Verbose) logEntry('Verified logging environment.');

// / The following code verifies & loads the Engine, which provides the environment this application runs in rather than anything it does.
// / It is loaded before verifyGlobals, & that position is not negotiable.
// / verifyGlobals calls requestConvertLoc, which calls resolveDataLocation, which lives in the Engine, so an Engine loaded later is a fatal on the first request.
// / The path uses DIRECTORY_SEPARATOR rather than $DirSep, because verifyGlobals sets $DirSep & has not run yet.
// / A failure to load is still a warning with somewhere to go, because the log buffer holds anything raised before the logfile exists.
// / The kernel is what loaded it, which is why the kernel is a file of its own rather than part of the Engine.
// / quickDie, purgeSensitiveMemory, redeclare, readComponentVersion & verifyCoreComponent cannot live in a file that one of them is responsible for loading.
// / An Engine that is missing or mismatched is a warning, & what is lost is named while everything that does not depend on it carries on.
// / The capability cache is read once per request & carried, so a conversion of ten files does not read the same file ten times.
// / Pipeline Core fills these on first use.
$CapabilityCacheWasRead = FALSE;
$CapabilityRecords = array();
$EngineActive = FALSE;
$EngineVersion = $EngineConfigPath = '';
list ($EngineActive, $EngineVersion) = verifyCoreComponent('Engine', 'Engine'.DIRECTORY_SEPARATOR.'engine.php', 'EngineVersion', $RequiredEngineVersion);
// / What kind of machine this is, decided once & read everywhere.
// / The dependency manifest uses it to know that a bundled x86 binary cannot run here, &
// / that a package has a different name or a different download on arm.
// / It is read AFTER the Engine loads, because the Engine is what knows how to ask, & the
// / answer is needed before any dependency is probed.
$HostArchitecture = 'unknown';
$HostMachineString = '';
if ($EngineActive && function_exists('detectHostArchitecture')) list ($HostArchitecture, $HostMachineString) = detectHostArchitecture();
if (!$EngineActive) warningEntry('The Engine is unavailable. Dependency location, startup keys, environment validation & per operation resource limits are unavailable until it is repaired.');
else {
  // / The configuration was read before verifyLogs & is already in scope.
  // / Its absence is reported here rather than there, because there is nowhere to write a
  // / warning until the logging environment exists.
  // / The sandbox profiles are handed to the Engine here.
  // / The Engine assembles a sandbox from them & knows nothing about any tool named in
  // / them. This is the one line that connects what this application knows to what the
  // / Engine does with it.
  $EngineSandboxProfiles = getSandboxProfiles();
  // / The AppArmor profile those profiles depend on is checked as soon as the Engine has loaded.
  // / verifySandboxPolicyAtStart() says why it waits for the Engine, & why nothing acts on this.
  $SandboxProfileIsValid = verifySandboxPolicyAtStart();
  // / Resource awareness belongs to the Engine, where every manager role lives.
  if ($EnableResourceAwareness) $ResourceAwarenessActive = TRUE;
  if (!isset($EngineConfigVersion)) warningEntry('The Engine loaded & its configuration is missing. Every capability it gates is treated as enabled & every logging option it carries is treated as disabled.');
  // / The contract is this application's own, & without it the Engine knows no location, no served
  // / folder & no mapped setting, so it protects & checks nothing that depends on one. That is said
  // / here, on every request, rather than read as an application that serves nothing. The Engine's
  // / configuration is what loads the contract, so a missing configuration is said above instead.
  if (isset($EngineConfigVersion) && (!isset($EngineManagedLocations) or !is_array($EngineManagedLocations))) warningEntry('The application contract at Resources/Contract/app-engine-config.php was not loaded, so the Engine knows no managed location, no served folder & no mapped setting. Restore it from the release this core came from.');
  if ($Verbose && (!isset($EngineReportsItsOwnLoad) or $EngineReportsItsOwnLoad)) logEntry('Engine '.ltrim((string)$EngineVersion, 'vV').' loaded for '.(isset($EngineApplicationName) ? $EngineApplicationName : $ApplicationName).'.'); }

// / An administrator may refuse to run rather than accept work they cannot account for.
// / This is tested here rather than in verifyInstallation, because what it tests for does
// / not exist until the Engine has been verified.
if ($RequireResourceAwareness && !$ResourceAwarenessActive) errorEntry('Resource awareness is required by config.php & the Engine is unavailable!', 31010, TRUE);

// / A workflow's worker continues a session a request handed it, rather than one a browser presented.
// / It is adopted here, after the Engine has loaded & before the globals that hang off a session are built.
$WorkflowSessionAdopted = FALSE;
if ($RunningFromCLI && $EngineActive && isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] === '--run-workflow') $WorkflowSessionAdopted = adoptWorkflowSession();

// / The following code verifies & sanitizes global variables for the session.
list ($GlobalsAreVerified) = verifyGlobals();
if (!$GlobalsAreVerified) errorEntry('Could not verify globals!', 11, TRUE);
else if ($Verbose) logEntry('Verified globals.');

// / The following code verifies the Pipeline Core & reads what each pipeline declares.
// / This runs after verifyLogs(), because enumeration warns about every pipeline it refuses & those warnings need somewhere to be written.
// / No pipeline code is executed here, & a pipeline's pipeline.php is loaded once a conversion is about to be dispatched.
// / A missing or mismatched Pipeline Core is a warning rather than an error, because the interface, the command line & every report still work without it.
// / Nothing converts without it.
// / It is an Engine core & is still loaded here, by the application, rather than by engine.php, because a broken engine.php must not take the pipeline list away from -v.
// / These four are stated by verifyGlobals(), which has already run.
list ($PipelineCoreActive, $PipelineCoreVersion) = verifyCoreComponent('Pipeline Core', 'Engine'.$DirSep.'Cores'.$DirSep.'pipelineCore.php', 'PipelineCoreVersion', $RequiredPipelineCoreVersion);
if ($PipelineCoreActive) {
  list ($PipelinesAreEnumerated, $Pipelines, $PipelineCount) = enumeratePipelines();
  // / A manager that verified nothing is worse than no manager, because it would send every
  // / conversion to a dispatcher with nothing behind it. Stand it down & use the fallback.
  if (!$PipelinesAreEnumerated) $PipelineCoreActive = FALSE; }
if (!$PipelineCoreActive) warningEntry('The Pipeline Core is unavailable. No conversion can run until it is repaired.');
else if ($Verbose) logEntry('Verified the Pipeline Core. '.$PipelineCount.' conversion pipeline(s) are available.');

// / The following code verifies the Workflow Core, which reads, checks & runs workflows.
// / It is loaded where workflows are enabled, & for --workflow, so an administrator can check a workflow before enabling them.
// / A missing or mismatched Workflow Core costs workflows & nothing else, so it is a warning.
$WorkflowCoreActive = FALSE;
$WorkflowCoreVersion = '';
if ($EnableWorkflows or ($RunningFromCLI && isset($_SERVER['argv'][1]) && $_SERVER['argv'][1] === '--workflow')) list ($WorkflowCoreActive, $WorkflowCoreVersion) = verifyCoreComponent('Workflow Core', 'Engine'.$DirSep.'Cores'.$DirSep.'workflowCore.php', 'WorkflowCoreVersion', $RequiredWorkflowCoreVersion);
if ($EnableWorkflows && !$WorkflowCoreActive) warningEntry('The Workflow Core is unavailable. No workflow can run until it is repaired.');
else if ($WorkflowCoreActive && $Verbose) logEntry('Verified the Workflow Core.');

// / The following code decides if the security context being attempted matches a valid CLI or web request.
// / Error 27 should not be possible & should never be able to fire. If it does something is seriously wrong.
list ($CommandLineHandled, $UserType, $CommandLineStatus) = parseCommandLine();
// / A root command that installs, run from a tree that is not installed, installs it as it ends
// / & hands over to the installed copy. handOverToApplication says why it waits until now. It is
// / Setup Core's, & every command it acts on has loaded Setup Core by the time it returns here.
if ($CommandLineHandled && function_exists('handOverToApplication')) $CommandLineStatus = handOverToApplication($CommandLineStatus);
// / The application tells the Engine how it was reached, once it knows.
// / A CLI invocation & an HTTP request reach the same file, so the Engine cannot work
// / this out & is told. A pipeline declaring a surface it does not permit is refused.
$EngineActiveSurface = $UserType;
if ($CommandLineHandled && $UserType === 'web') errorEntry('Could not verify user type!', 27, TRUE);

// / If this is a CLI operation log a warning that conversion operations will be disabled.
// / A workflow's worker is the one command line that converts, so it is not warned about.
if ($CommandLineHandled && $UserType === 'cli' && !$WorkflowSessionAdopted) warningEntry('CLI user detected. Conversion operations are disabled.');

// / Only enable file operations for web users, when the script is being run with Apache+PHP.
if (!$CommandLineHandled && $UserType === 'web') {
  if ($Verbose) logEntry('Web user detected. Conversion operations are enabled.');

  // / The following code ensures that the application cannot accidentally or maliciously be run as the root or standard user beyond this point.
  if ($RunningAsRoot or $CurrentUser !== $ApacheUser) errorEntry('The application is being run in an unsupported security context!', 28, TRUE);
  else logEntry('Verified security context. Currently running as the '.$CurrentUser.' user.');

  // / A web request cannot be served without an Engine, & this is where that is decided.
  // / Encryption verification, budgeting, cleaning, URL fetching & stream waiting all live in
  // / the Engine. Eighteen call sites below reach into it with no guard, so an absent Engine
  // / produces an undefined function on the first of them & a blank page for the visitor.
  // / A visitor who gets a blank page cannot report anything useful & an administrator reading
  // / the log finds a fatal rather than a reason. A documented halt gives both a sentence.
  // / The command line is exempt & is handled in parseCommandLine, because -v & -h are how an
  // / operator finds out what is wrong & must keep working when nothing else does.
  if (!$EngineActive and !$RunningFromCLI) quickDie('The Engine is unavailable, so this request cannot be served. Run  php convertCore.php -v  on the server to see which component failed.', 28005);

  // / Only enable file operations for web users if the current user is the expected www-data user.
  if ($CurrentUser === $ApacheUser) {

    // / The following code tries to verify that the session is encrypted, if possible.
    list ($EncryptionVerified, $URLEcho) = verifyEncryption();
    if (!$EncryptionVerified) errorEntry('Could not verify connection!', 10, TRUE);
    else if ($Verbose) logEntry('Verified inbound connection.');

    // / The following code verifies that required directories exist & creates them where needed.
    list ($RequiredDirsExist, $RequiredDirs, $RequiredDirsVerified) = verifyRequiredDirs();
    if (!$RequiredDirsExist) errorEntry('Could not verify required directories!', 12, TRUE);
    // / One line rather than one per directory. It says how many, so a count that drops is
    // / visible without every path being printed on every request.
    else if ($Verbose) logEntry('Verified '.$RequiredDirsVerified.' required directories.');

    // / The build environment is removed by -fp as root, & an update runs -fp as it ends.

    // / The following code removes old files from the whole DATA folder.
    list ($CleanedTempLoc, $TempLocDeepCleaned) = cleanDataLocation($ConvertTemp, 'Temporary files', $DeleteThreshold, $ProtectedRootDirs, array($ConvertTemp));
    if (!$CleanedTempLoc) warningEntry('Could not fully clean the temporary location. Some expired sessions may remain & will be retried next request. Serving this request anyway.');
    else if ($Verbose) logEntry('Cleaned temporary location.');

    // / The following code removes old files from the $ConvertLoc.
    list ($CleanedConvertLoc, $ConvertLocDeepCleaned) = cleanDataLocation($ConvertLoc, 'Data location', $DeleteThreshold, $ProtectedRootDirs, array($ConvertLoc));
    if (!$CleanedConvertLoc) warningEntry('Could not fully clean the data location. Some expired sessions may remain & will be retried next request. Serving this request anyway.');
    else if ($Verbose) logEntry('Cleaned convert location.');

    // / The following code displays the appropriate GUI for the session.
    // / Every file operation answers with a terse reply, & suppresses the interface here so its caller receives a short answer it can read.
    // / A caller can tell a failure from a success only by reading the body, & a whole page carries the script literal that recognises a core error.
    // / A file operation returns a filename or an error, & never a page.
    // / An upload is known by its files, because Dropzone sends the file & the two tokens & no upload field.
    if (!isset($_POST['filesToArchive']) && !isset($_POST['convertSelected']) && !isset($_POST['pdfworkSelected']) && !isset($_POST['download']) && !isset($_POST['upload']) && empty($_FILES) && !isset($_POST['filesToScan']) && !isset($_POST['filesToDelete']) && !isset($_POST['filesToIdentify']) && !isset($_POST['workflowLibrary']) && !isset($_POST['workflowRun']) && !isset($_POST['workflowDocument']) && !isset($_POST['workflowStatus']) && !isset($_POST['workflowStop']) && !isset($_POST['workflowOperations']) && !isset($_POST['workflowCheck']) && !isset($_POST['workflowExport'])) {

      // / The following code sets the GUI for the session.
      list ($GuiIsSet, $GuiToUse, $GuiDir, $GuiFiles) = verifyGui();
      if (!$GuiIsSet) errorEntry('Could not verify GUI! GUI set to '.$GuiToUse.'!', 25, TRUE);
      else if ($Verbose) logEntry('Verified GUI. GUI set to '.$GuiToUse.'.');

      // / The following code sets the color scheme for the session.
      list ($ColorsAreSet, $ButtonCode) = verifyColors($ButtonStyle);
      if (!$ColorsAreSet) errorEntry('Could not verify color scheme! Color set to '.$ButtonStyle.'!', 15, TRUE);
      else if ($Verbose) logEntry('Verified color scheme. Color set to '.$ButtonStyle.'.');

      // / The following code sets the language for the session.
      list ($LanguageIsSet, $LanguageToUse, $LanguageDir, $LanguageFiles) = verifyLanguage();
      if (!$LanguageIsSet) errorEntry('Could not verify language! Language set to '.$LanguageToUse.'!', 16, TRUE);
      else if ($Verbose) logEntry('Verified language. Language set to '.$LanguageToUse.'.');

      // / The following code sets the order the session's files are listed in.
      // / It cannot fail, because a code it does not understand falls back to listing by name.
      $SortToUse = verifySort($Sort);
      if ($Verbose) logEntry('Verified sort order. Sort set to '.$SortToUse.'.');

      // / The following code actually builds & renders the GUI.
      $GUIDisplayed = showGUI($ShowGUI, $ButtonCode);
      if (!$GUIDisplayed) errorEntry('Could not display GUI!', 17, TRUE);
      else if ($Verbose) logEntry('Displaying the GUI.'); }

    // / If this is an API call with a simple output, continue without having displayed any GUI at all.
    else if ($Verbose) logEntry('Skipping display GUI procedure.');

    // / Check if we're providing the user with tokens generated during this session.
    // / In no other code path do we generate a new token that gets provided to the user.
    // / The tokens are the session, so neither is ever written to a log.
    // / The session's folder names it in every entry, as the session label.
    if (!$TokensAreValid && $Verbose) logEntry('Providing the user with a new pair of tokens.');

    // / Only enable file related operations if valid tokens were supplied by the user.
    if ($TokensAreValid) {
      if ($Verbose) logEntry('Verified supplied tokens.');

      // / The following code is performed when a user initiates a file upload.
      if (!empty($_FILES)) {
        logEntry('Initiating Uploader.');
        // / An upload takes no budget, so it asks the listener whether the disk holding this session can take it.
        // / A refusal fails the upload as any other failure does, & the warning says why.
        // / With no Engine there is no listener to ask, so the upload goes ahead as it always has.
        if (function_exists('requestStorageSpace')) list ($UploadIsPermitted, $UploadRefusal) = requestStorageSpace($ConvertLoc, measureUploadSize());
        else list ($UploadIsPermitted, $UploadRefusal) = array(TRUE, '');
        if (!$UploadIsPermitted) {
          warningEntry('An upload was refused. '.$UploadRefusal);
          list ($UploadComplete, $UploadErrors) = array(FALSE, TRUE); }
        else list ($UploadComplete, $UploadErrors) = uploadFiles();
        if (!$UploadComplete) errorEntry('Upload Failed!', 18, TRUE);
        if ($UploadErrors) logEntry('Upload finished with errors.');
        if ($Verbose) logEntry('Upload Complete.'); }

      // / The following code is performed when a user downloads a selection of files.
      if (isset($_POST['download'])) {
        logEntry('Initiating Downloader.');
        list ($DownloadComplete, $DownloadErrors) = downloadFiles($Download);
        if (!$DownloadComplete) errorEntry('Download Failed!', 19, TRUE);
        if ($DownloadErrors) logEntry('Download finished with errors.');
        if ($Verbose) logEntry('Download Complete.'); }

      // / The following code is performed when a user deletes a selection of files.
      if (isset($_POST['filesToDelete'])) {
        logEntry('Initiating Deletor.');
        list ($DeleteComplete, $DeleteErrors) = deleteFiles($FilesToDelete);
        if (!$DeleteComplete) errorEntry('Delete Failed!', 24, TRUE);
        if ($DeleteErrors) logEntry('Delete finished with errors.');
        if ($Verbose) logEntry('Delete Complete.'); }

      // / The following code is performed when a user archives a selection of files.
      // / A refusal prints the same alert a refused conversion prints. It carries no
      // / ERROR!!! tag because nothing failed, so an interface must recognise it by the
      // / alert string. That is what the failureStrings list in the GUI is for.
      if (isset($_POST['filesToArchive'])) {
        if (!takeOperationBudget('archive')) print($Alert3.$Lol);
        else {
          logEntry('Initiating Archiver.');
          list ($ArchiveComplete, $ArchiveErrors, $ArchivedFiles, $ArchiveFindings) = invokeArchiveOperation($FilesToArchive, array('Filename' => $UserFilename, 'Extension' => $UserExtension));
          // / The core prints, & the operation returned, as contract 6 says. The interface reads one line per file.
          foreach ($ArchivedFiles as $ArchivedFile) print($ArchivedFile.$Lol);
          if (!$ArchiveComplete) errorEntry('Archive Failed!', 20, TRUE);
          if ($ArchiveErrors) logEntry('Archive finished with errors.');
          if ($Verbose) logEntry('Archive Complete.'); }
        $BudgetWasReleased = giveBackOperationBudget('archive'); }

      // / The following code is performed when a user converts a selection of files.
      if (isset($_POST['convertSelected'])) {
        if (!takeOperationBudget('conversion')) print($Alert3.$Lol);
        else {
          logEntry('Initiating Converter.');
          list ($ConversionComplete, $ConversionErrors, $ConvertedFiles, $ConversionFindings) = invokeConvertOperation($ConvertSelected, array('Filename' => $UserFilename, 'Extension' => $UserExtension, 'Height' => $Height, 'Width' => $Width, 'Rotate' => $Rotate, 'Bitrate' => $Bitrate));
          // / The core prints, & the operation returned, as contract 6 says. The interface reads one line per file.
          foreach ($ConvertedFiles as $ConvertedFile) print($ConvertedFile.$Lol);
          if (!$ConversionComplete) errorEntry('Conversion Failed!', 21, TRUE);
          if ($ConversionErrors) logEntry('Conversion finished with errors.');
          if ($Verbose) logEntry('Conversion Complete.'); }
        $BudgetWasReleased = giveBackOperationBudget('conversion'); }

      // / The following code is performed when a user performs OCR on a selection of files.
      if (isset($_POST['pdfworkSelected'])) {
        if (!takeOperationBudget('OCR')) print($Alert3.$Lol);
        else {
          logEntry('Initiating OCR.');
          // / OCR is an operation pipeline & is dispatched from here rather than through
          // / convert(). It takes a selection of files rather than one file, it decides its
          // / own route per file, & it returns two values rather than the six a conversion
          // / pipeline returns. Forcing it into the conversion contract would have meant
          // / describing a batch as a single file & discarding most of what it reports.
          // / A missing or mismatched OCR pipeline is reported by the adapter rather than read as an OCR run that produced nothing.
          list ($ConversionComplete, $ConversionErrors, $OcrOutputFilenames, $OcrFindings) = invokeOcrOperation($PDFWorkSelected, array('Filename' => $UserFilename, 'Extension' => $UserExtension, 'Method' => $Method));
          // / The core prints, & the pipeline returned, as contract 6 says.
          // / The interface reads this reply one line per produced file, exactly as it reads the reply from a conversion.
          // / A pipeline never prints these itself, because its output shares a stream with any PHP warning.
          foreach ($OcrOutputFilenames as $OcrOutputFilename) print($OcrOutputFilename.$Lol);
          if (!$ConversionComplete) errorEntry('OCR Operation Failed!', 22, TRUE);
          if ($ConversionErrors) logEntry('OCR Operation finished with errors.');
          if ($Verbose) logEntry('OCR Operation Complete.'); }
        $BudgetWasReleased = giveBackOperationBudget('OCR'); }

      // / The following code is performed when a user identifies a selection of files.
      // / Identify is an operation pipeline & is dispatched as OCR is. It changes no file & may write a report.
      // / Its reply is one line of JSON, because an interface fills in what it shows rather than downloading a file.
      // / A request when the feature is off is refused in words, as a URL download is, because an API caller deserves to know why.
      if (isset($_POST['filesToIdentify'])) {
        if (!$AllowUserIdentify) {
          warningEntry('An identification was requested & the feature is disabled.');
          print('Identifying files is disabled on this server.'.$Lol); }
        else if (!takeOperationBudget('Identify')) print($Alert3.$Lol);
        else {
          logEntry('Initiating Identify.');
          list ($IdentifyComplete, $IdentifyErrors, $IdentifyFiles, $IdentifyResult) = invokeIdentifyOperation($FilesToIdentify, array('Report' => $IdentifyReport, 'Filename' => $IdentifyFilename));
          // / The core prints, & the pipeline returned, as contract 6 says.
          print(encodeIdentifyReply($IdentifyComplete, $IdentifyErrors, $IdentifyResult).$Lol);
          if (!$IdentifyComplete) errorEntry('Identify Failed!', 31, TRUE);
          if ($IdentifyErrors) logEntry('Identify finished with errors.');
          if ($Verbose) logEntry('Identify Complete.'); }
        $BudgetWasReleased = giveBackOperationBudget('Identify'); }

      // / The following code is performed when a user asks the server to fetch a URL.
      // / A URL the operator typed is fetched into their session, & only when an
      // / administrator has turned it on.
      // / This server has no authentication, so an open fetcher is an open proxy & is off by
      // / default. See --Allow User URL Download-- in config.php.
      // / It takes an operation budget like every other unit of work, because a fetch costs
      // / time & bandwidth & a queue of them is a queue.
      if (isset($_POST['userURLDownload']) && trim($UserURLDownload) !== '') {
        if (!$AllowUserURLDownload) {
          warningEntry('A URL download was requested & the feature is disabled.');
          print('Downloading from a URL is disabled on this server.'.$Lol); }
        // / One visitor may fetch a limited number of files per session.
        // / A fetch costs bandwidth & disk whether or not anything is ever converted, so
        // / without a count one visitor can queue as many as they can type.
        // / The tally is a file in the session directory rather than anything held in
        // / memory, because each request is a separate process & a counter that does not
        // / survive the request counts to one forever.
        // / A session ending takes the tally with it, which is the reset.
        else if ((int)$URLDownloadsPerSession > 0 && countSessionURLDownloads($ConvertDir) >= (int)$URLDownloadsPerSession) {
          warningEntry('A session reached its URL download limit of '.(int)$URLDownloadsPerSession.'.');
          print('This session has fetched '.(int)$URLDownloadsPerSession.' file(s), which is the limit.'.$Lol); }
        else if (!takeOperationBudget('user url download')) print($Alert3.$Lol);
        else {
          logEntry('Initiating a user requested download.');
          list ($URLFetchSucceeded, $URLFetchedPath, $URLFetchReason) = fetchUserSuppliedURL($UserURLDownload, $ConvertDir);
          // / Recorded only on success. A refused fetch cost bandwidth & should not also
          // / cost somebody one of their attempts, or a user with a typo runs out of tries
          // / without ever having fetched anything.
          if ($URLFetchSucceeded) countSessionURLDownloads($ConvertDir, TRUE);
          if ($URLFetchSucceeded) logEntry('A user requested download completed. '.$URLFetchReason);
          else warningEntry('A user requested download did not complete. '.$URLFetchReason);
          print($URLFetchReason.$Lol);
          giveBackOperationBudget('user url download'); } }

      // / The following code is performed when a user performs a virus scan on a selection of files.
      if (isset($_POST['filesToScan']) && $AllowUserVirusScan) {
        if (!takeOperationBudget('user virus scan')) print($Alert3.$Lol);
        else {
          logEntry('Initiating User Virus Scanner.');
          list ($ScanComplete, $ScanErrors, $UserVirusFound) = userVirusScan($FilesToScan, $UserScanType);
          if (!$ScanComplete) errorEntry('User Virus Scan Failed!', 23, TRUE);
          if ($UserVirusFound) logEntry('The User Virus Scan detected infected files.');
          if (!$UserVirusFound) logEntry('The User Virus Scan did not detect any infected files.');
          if ($ScanErrors) logEntry('User Virus Scan finished with errors.');
          if ($Verbose) logEntry('User Virus Scan Complete.'); }
        $BudgetWasReleased = giveBackOperationBudget('user virus scan'); }

      // / The following code is performed when a page or a caller of the API asks about workflows.
      // / Each answer is one line of JSON under the key Workflow, as Identify answers under its own.
      // / The work is the Workflow Core's. A workflow runs in a worker of its own, so a request here lists, starts, reads or stops one.
      // / A page's builder asks what it may build from, has a workflow checked or a library's workflow given as a document, & runs what it built.
      // / Which files a user may name is this application's policy, so a run is handed only files this session lists.
      // / A server with workflows off, or without the Workflow Core, answers that none are available, & says why in its log.
      if (isset($_POST['workflowLibrary']) or isset($_POST['workflowRun']) or isset($_POST['workflowDocument']) or isset($_POST['workflowStatus']) or isset($_POST['workflowStop']) or isset($_POST['workflowOperations']) or isset($_POST['workflowCheck']) or isset($_POST['workflowExport'])) {
        if (!$EnableWorkflows or !$WorkflowCoreActive or !function_exists('encodeWorkflowReply')) {
          warningEntry('A workflow was requested & '.($EnableWorkflows ? 'the Workflow Core is unavailable.' : 'workflows are disabled.'));
          print('{"Workflow":{"Available":false}}'.$Lol); }
        else if (isset($_POST['workflowRun'])) print(encodeWorkflowReply(startWorkflowRun($WorkflowName, array_values(array_intersect((array)$WorkflowFiles, getFiles($ConvertDir))))).$Lol);
        else if (isset($_POST['workflowDocument'])) print(encodeWorkflowReply(startWorkflowDocumentRun($WorkflowDocument, array_values(array_intersect((array)$WorkflowFiles, getFiles($ConvertDir))))).$Lol);
        else if (isset($_POST['workflowStop'])) print(encodeWorkflowReply(stopWorkflowRun()).$Lol);
        else if (isset($_POST['workflowStatus'])) print(encodeWorkflowReply(reportWorkflowStatus()).$Lol);
        else if (isset($_POST['workflowCheck'])) print(encodeWorkflowReply(checkWorkflowDocument($WorkflowCheck)).$Lol);
        else if (isset($_POST['workflowExport'])) print(encodeWorkflowReply(exportWorkflowDocument($WorkflowExport)).$Lol);
        else if (isset($_POST['workflowOperations'])) print(encodeWorkflowReply(describeWorkflowOperations()).$Lol);
        else print(encodeWorkflowReply(describeWorkflowLibrary()).$Lol); }

      // / Close the web server connection after all required content has been served.
      if ($Verbose) logEntry('Closing connection.');
      closeClientConnection();

      // / Nothing below this point may produce any output.
      // / The user has already been served & the connection is already closed.
      // /
      // / This is the supervision half of the detached worker passthrough.
      // / A pipeline that launched a process which outlives the request reported its PID
      // / through the sixth value of the pipeline contract, & convert() recorded it here.
      // / This block reaps it. It is entirely generic. It knows a process ID & an output
      // / path & nothing else, so it works for ANY pipeline that hands back a worker, not
      // / only for streaming.
      // / The names below are historical. Streaming was the first & for a long time the only
      // / user of this path, so the variables are still called $WaitForStream, $StreamPID &
      // / $StreamOutputPath, & the timeout is still $StreamWatchTimeout. They were not
      // / renamed because an administrator's config.php names that timeout & a rename would
      // / silently reset it on every installation in the world.
      // / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt, contract 5, before writing a
      // / pipeline that uses this. Doing it by hand instead is how a process gets orphaned.
      if ($WaitForStream && $StreamPID > 0) {
        logEntry('Waiting up to '.$StreamWatchTimeout.' minutes for detached worker '.$StreamPID.' to finish.');
        list ($StreamCompleted, $StreamKilled, $ElapsedSeconds) = waitForStream($StreamPID, $StreamOutputPath);
        if ($StreamKilled) logEntry('Detached worker '.$StreamPID.' was killed.');
        if ($StreamCompleted) logEntry('Detached worker '.$StreamPID.' completed after '.$ElapsedSeconds.' seconds.'); } } } }

// / Stop execution of the application, with the status a command line earned.
// / A served web request never sees a status, so it exits 0.
// / A handled command line exits with the status its command set, which is 0, 1 or 2.
exit($CommandLineHandled ? (int)$CommandLineStatus : 0);
// / -----------------------------------------------------------------------------------
?>