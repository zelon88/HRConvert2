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
// / v4.0.1.
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
    'ShowGUI', 'ShowFinePrint', 'TOSURL', 'PPURL', 'LogoURL', 'AllowUserShare',
    // / DocumentEngineSleepTimer was required & is read NOWHERE. It was removed from the
    // / list rather than added to the configuration, because a required setting nothing
    // / reads is a setting every installation must carry for no reason.
    // / UsePatchedDocumentEngine chose a bundled unoconv that no longer exists. LibreOffice
    // / converts every document itself now, so it was removed for the same reason.
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
    'DefaultConversionCost', 'DefaultExpectedRuntime', 'MaintainHTAccess');
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
// / BOOT FUNCTIONS THAT MUST NOT LIVE IN THE ENGINE. Do not move them there.
// / Each is reached before the Engine loads: generateInstallSecret through verifyInstallation
// / & resolveSecretFile, & the two AppArmor functions through verifySandboxPolicy, which
// / verifyInstallation calls. Those three stay in the application.
// / The fourth, closeClientConnection, is reached through quickDie & is therefore KERNEL. It
// / moved to Resources/Kernel/kernel.php at v3.9.9, which is beside the Engine & not in it.
// / All four were moved to the Engine at v3.9.3 & it went unnoticed, because each is only
// / reached on a first run, an error exit or a sandbox check. An existing install has a
// / secret, so generateInstallSecret was never called & nothing failed. A FRESH install has
// / none, so every fresh install & every fresh container fatally errored on its first boot.
// / The check that would have caught it was written after the move & was only ever run
// / against functions still in this file. Re-run a corrected check against everything it
// / passed before, not just what is left.
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
// / A function to report whether an AppArmor profile is loaded into the kernel.
// / Accepts the profile name as it is declared inside the profile file.
// / Returns a loaded boolean & a status word, in that order.
// / A profile on disk is not a profile in force.
// / Writing one & running apparmor_parser only at the moment it is written means a load
// / that failed, or a host that rebooted before AppArmor read it, leaves a file that
// / matches perfectly & is enforcing nothing. The check reported ok & the sandbox stayed
// / broken, which is the worst combination a diagnostic can produce.
// / The loaded set is read from securityfs, which is what the kernel is actually using.
function apparmorProfileIsLoaded($profileName) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ProfileIsLoaded = FALSE;
  $ProfileStatus = 'unknown';
  $profilesPath = '/sys/kernel/security/apparmor/profiles';
  $loadedProfiles = '';
  if (!file_exists($profilesPath)) $ProfileStatus = 'apparmor not active';
  else if (!is_readable($profilesPath)) $ProfileStatus = 'not readable by this account';
  else {
    $loadedProfiles = (string)@file_get_contents($profilesPath);
    if (strpos($loadedProfiles, (string)$profileName) !== FALSE) {
      $ProfileIsLoaded = TRUE;
      $ProfileStatus = 'loaded'; }
    else $ProfileStatus = 'NOT LOADED'; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $profilesPath, $loadedProfiles, $profileName);
  return array($ProfileIsLoaded, $ProfileStatus); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to load an AppArmor profile that has just been written.
// / Accepts the absolute path of the profile.
// / Returns TRUE when the parser accepted it.
// / A profile that is written but never loaded changes nothing until the next reboot, which
// / makes a repair look like it failed.
function reloadApparmorProfile($profilePath) {
  // / Set variables.
  global $RunningAsRoot, $EnableMemoryProtection;
  $ProfileWasLoaded = FALSE;
  $parserBinary = '';
  $parserOutput = array();
  $parserExitCode = 1;
  $parserBinary = locateDependency('apparmor_parser');
  if (!$RunningAsRoot) warningEntry('An AppArmor profile was written but could not be loaded, because loading one requires root.');
  else if ($parserBinary === '') warningEntry('An AppArmor profile was written but apparmor_parser is not installed, so it was not loaded.');
  else {
    exec(escapeshellarg($parserBinary).' -r '.escapeshellarg($profilePath).' 2>&1', $parserOutput, $parserExitCode);
    if ($parserExitCode === 0) {
      $ProfileWasLoaded = TRUE;
      logEntry('The AppArmor profile at '.$profilePath.' was loaded.'); }
    else warningEntry('apparmor_parser refused the profile at '.$profilePath.'. '.implode(' ', $parserOutput)); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $parserBinary, $parserOutput, $parserExitCode, $profilePath);
  return $ProfileWasLoaded; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read an install secret without running it.
// / Accepts the path of the secret file.
// / Returns a success boolean, the key & the version it declares, in that order.
// /
// / ROOT NEVER REQUIRES A SECRET FILE. The install wide secret lives in the data location,
// / which belongs to the web server account, & until v4.0.1 every command line run as root
// / required it. The web server account could add a line to the file it owned, & the next
// / sudo php convertCore.php -v ran that line as root. That was demonstrated, not assumed.
// /
// / So root reads the file as text with PHP's own tokenizer, which parses without executing.
// / The file must have exactly the shape addSillyString writes. That is an opening tag, the
// / version & one live key, with every decoy inside a comment. Anything else in it, a call, a
// / second assignment or a stray line, means it is not a file this application wrote, & it is
// / refused rather than partly believed. A refused file is replaced, which is what happens to
// / a secret of the wrong version, & every session it derived is invalid.
// / It is read with readFileWithoutFollowing, because the folder it is in belongs to the web
// / server account. A link, a FIFO or a file with two names there is refused unread & replaced.
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
// / A function to create the data location as root when it does not exist yet.
// / Accepts the data location. Returns TRUE when it exists afterwards.
// / The installer is documented as creating it, & it never could, because the install secret
// / is written into it at boot & a missing location stopped every root command with error 5
// / before the installer ran. A fresh install that followed INSTALLATION_INSTRUCTIONS.txt to
// / the letter failed on its first command. That was measured on v4.0.0.
// / The location is created only beneath a folder root can trust, never beneath a system
// / folder, & its parents are 0755 so the web server account can reach it. The location
// / itself belongs to the web server account, as it always has.
function prepareDataLocationAsRoot($dataLocation) {
  // / Set variables.
  global $EnableMemoryProtection;
  $LocationExists = FALSE;
  $existingAncestor = $systemFolder = $trustNotice = $resolvedAncestor = $ownerNotice = '';
  $ancestorIsTrusted = $ownerWasChanged = FALSE;
  $systemFolders = array('/bin', '/boot', '/dev', '/etc', '/lib', '/lib32', '/lib64', '/proc', '/root', '/run', '/sbin', '/sys', '/usr');
  $dataLocation = rtrim((string)$dataLocation, DIRECTORY_SEPARATOR);
  if ($dataLocation === '' or is_dir($dataLocation)) $LocationExists = ($dataLocation !== '');
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
      // / THE DATA LOCATION IS A ROOT ROOT OWNS, 0755, so every account may traverse it to reach
      // / a folder of its own below, & none but root may change what is in it. The install secret
      // / lives here & is root's, so a root owned root is what keeps the web server account from
      // / replacing it. The session, cache & log folders below are the web server account's & are
      // / created with their roles by --setup or -fp. Until v4.0.1 this folder was the web server
      // / account's, because sessions were kept directly in it.
      if (@mkdir($dataLocation, 0755) && is_dir($dataLocation)) {
        // / The location was created a moment ago in a folder only root can change, so the
        // / name cannot have been swapped, & changeOwnerWithoutFollowing confirms that first.
        list ($ownerWasChanged, $ownerNotice) = changeOwnerWithoutFollowing($dataLocation, 'root', 'root');
        if (!$ownerWasChanged) warningEntry($ownerNotice);
        $LocationExists = TRUE;
        warningEntry('The data location did not exist & was created at '.$dataLocation.'.'); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $existingAncestor, $systemFolder, $trustNotice, $resolvedAncestor, $ancestorIsTrusted, $ownerWasChanged, $ownerNotice, $systemFolders, $dataLocation);
  return $LocationExists; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to find the folders above the given locations that root cannot trust.
// / Accepts the locations, each the topmost folder -fp corrects on its path.
// / Returns whether every location could be judged & the folders that fail, in that order.
// / Each folder is keyed by its path & carries its owner, group & mode, & what it needs.
// /
// / -fp CORRECTS THE LOCATIONS IT MANAGES & NEVER A FOLDER ABOVE THEM. Root will not read, write
// / or run anything beneath a folder another account can change, because that account could
// / rename what is inside it aside & put its own in its place. A folder above a managed location
// / is the administrator's, so it is named rather than changed. Releases before v4.0.1 asked for
// / the very state that fails here, because v3.4.1 had /var/www given to the web server account
// / & earlier guides gave it /DATA, so an upgraded host is where this is seen.
// / Folders are judged as pathIsTrustedByRoot judges them, on the resolved path, & every one that
// / fails is named rather than the first. Only a folder that exists is judged, because a missing
// / one is created beneath a trusted folder when it is needed. Only a folder that root or the
// / web server account owns is to be given to root. A folder another account owns, a home folder
// / for instance, & a folder every account shares by design, as /tmp is, are left to the
// / administrator to move away from, because changing either decides something for somebody else.
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
// / A function to load, create or rotate the install wide secret as root.
// / Accepts the secret file, the version this core requires & whether a new key was asked for.
// / Returns a readiness boolean & the key, in that order.
// /
// / Root reads the secret with readSecretWithoutExecuting & writes it with
// / writeFileWithoutFollowing, because the file sits in the data location & the web server
// / account owns that folder. Nothing here requires the file, follows a link, or changes a
// / mode by name. The group is changed by changeOwnerWithoutFollowing, which changes the name
// / itself, never what a link points at, & only when a hard link cannot be swapped in.
// /
// / A file that is already correct is left exactly as it is. A file this core can use that the
// / web server account owns, which is how every release before v4.0.1 left it, is written
// / again with the SAME key, so moving it to root costs no session anything. The new copy is
// / root's & only the web server account's group can read it. A file that cannot be used is
// / replaced with a new key, & so is every file when a rotation is asked for.
function resolveInstallSecretAsRoot($secretFile, $requiredSecretVersion, $rotationRequested) {
  // / Set variables.
  global $ApacheUser, $EnableMemoryProtection;
  $SecretIsReady = FALSE;
  $ResolvedSecretKey = '';
  $existingWasRead = $keyWasGenerated = $secretWasWritten = $readBackSucceeded = $fileIsCurrent = $groupWasChanged = FALSE;
  $existingKey = $existingVersion = $candidateKey = $cleanRequiredVersion = $secretFileContent = $writeNotice = $readBackKey = $readBackVersion = $groupName = $groupNotice = '';
  $secretStatus = $groupRecord = array();
  $cleanRequiredVersion = ltrim(trim((string)$requiredSecretVersion), 'vV');
  if (!$rotationRequested && (file_exists($secretFile) or is_link($secretFile))) {
    list ($existingWasRead, $existingKey, $existingVersion) = readSecretWithoutExecuting($secretFile);
    if ($existingWasRead && ltrim($existingVersion, 'vV') === $cleanRequiredVersion) $candidateKey = $existingKey;
    else warningEntry('The secret file at '.$secretFile.' is not one this core can use, or it carries something this application did not write. It is being replaced & every session derived from it is now invalid.'); }
  // / A usable file is current when root owns it, it is 0640 & its group is the web server's.
  if ($candidateKey !== '') {
    clearstatcache(TRUE, $secretFile);
    $secretStatus = @lstat($secretFile);
    if (is_array($secretStatus) && function_exists('posix_getgrgid')) $groupRecord = @posix_getgrgid((int)$secretStatus['gid']);
    if (is_array($groupRecord) && isset($groupRecord['name'])) $groupName = (string)$groupRecord['name'];
    $fileIsCurrent = (is_array($secretStatus) && ((int)$secretStatus['mode'] & 0170000) === 0100000 && (int)$secretStatus['uid'] === 0 && ((int)$secretStatus['mode'] & 0777) === 0640 && $groupName === (string)$ApacheUser); }
  if ($candidateKey === '') list ($candidateKey, $keyWasGenerated) = generateInstallSecret();
  if (!is_string($candidateKey) or $candidateKey === '') errorEntry('An install secret could not be generated, because this host could not supply random bytes!', 31020, FALSE);
  else if ($fileIsCurrent) {
    $ResolvedSecretKey = $candidateKey;
    $SecretIsReady = TRUE; }
  else {
    $secretFileContent = addSillyString($candidateKey, $cleanRequiredVersion);
    list ($secretWasWritten, $writeNotice) = writeFileWithoutFollowing($secretFile, $secretFileContent, 0640);
    if (!$secretWasWritten) errorEntry('The install secret could not be written to '.$secretFile.'! '.$writeNotice, 31020, FALSE);
    else {
      // / The owner stays root. Only the group becomes the web server's, so it can read the key.
      list ($groupWasChanged, $groupNotice) = changeOwnerWithoutFollowing($secretFile, '', (string)$ApacheUser);
      if (!$groupWasChanged) warningEntry($groupNotice.' The web server account cannot read the install secret until it is.');
      // / Read the new file back exactly as root will read it next time.
      list ($readBackSucceeded, $readBackKey, $readBackVersion) = readSecretWithoutExecuting($secretFile);
      if (!$readBackSucceeded or $readBackKey !== $candidateKey) errorEntry('The install secret written to '.$secretFile.' did not read back correctly!', 31020, FALSE);
      else {
        $ResolvedSecretKey = $candidateKey;
        $SecretIsReady = TRUE;
        if ($rotationRequested) warningEntry('The install secret was rotated. Every session derived from the old one is now invalid.');
        else if (!$keyWasGenerated) warningEntry('The install secret at '.$secretFile.' was rewritten as root with its existing key. Every session is unaffected.');
        else warningEntry('A new install secret was written to '.$secretFile.'.'); } } }
  // / Manually clean up sensitive memory. Every target is passed directly by reference.
  // / $ResolvedSecretKey is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $existingWasRead, $keyWasGenerated, $secretWasWritten, $readBackSucceeded, $fileIsCurrent, $groupWasChanged, $groupNotice, $existingKey, $existingVersion, $candidateKey, $cleanRequiredVersion, $secretFileContent, $writeNotice, $readBackKey, $readBackVersion, $groupName, $secretStatus, $groupRecord, $secretFile, $requiredSecretVersion, $rotationRequested);
  return array($SecretIsReady, $ResolvedSecretKey); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to load an install secret from a file, or to create one when none exists.
// / Accepts the absolute path of the secret file.
// / Returns a readiness boolean & the secret key, in that order.
// / A failure returns FALSE & an empty string, which the caller must treat as a failed installation.
// / The file is written with only the secret & is checked byte for byte afterwards.
// / Appending to an existing file would produce a file that loads & carries a key nobody generated.
// / A file that fails that check is deleted.
// / A per user secret is chmod 0600 on every path, because a secret another account can read is
// / not a secret. The install wide secret is root's, so the same chmod changes nothing on it.
// / This is called for the install wide secret & for a per user secret in a folder of its own.
// / The logic is identical & only the supplied path differs, so it lives here rather than twice.
// / $SecretKey is capitalized against convention 3 because the secret file defines it under that name.
// / It is a function local here regardless of its case & is shredded before this function returns.
// /
// / ROOT DOES NOT COME HERE, & THE WEB SERVER ACCOUNT ONLY READS. Root reads the install wide
// / secret through resolveInstallSecretAsRoot, which never requires it. The web server account
// / requires the file root wrote, & it neither removes nor creates that one, because a secret
// / the web server account could write is a secret root would one day have to read. A file it
// / cannot use is reported, & -fp as root replaces it. A per user secret is still created here,
// / in a folder only its account can reach, & that includes the web server account's own. Until
// / v4.0.1 the rule caught that one too, so -v run as the web server account stopped with error 5
// / on any host where it had not made one before.
// / The install wide secret is the one name ending in -secret.php. verifyInstallation names a
// / per user secret with its account after that, & it is the only place either name is built.
function resolveSecretFile($secretFile, $requiredSecretVersion) {
  // / Set variables.
  global $LogFile, $ApacheUser, $CurrentUser, $EnableMemoryProtection, $CoreLoaded;
  $SecretIsReady = FALSE;
  $ResolvedSecretKey = $secret = $secretFileContent = '';
  $detectedSecretVersion = $cleanRequiredVersion = $rotationNotice = $strayOutput = '';
  $secretGenerated = $existingIsUsable = $loggingIsReady = $payloadIsWellFormed = $secretIsReadable = $creationIsPermitted = $isInstallSecret = FALSE;
  $bytesWritten = 0;
  // / Both are only ever defined by the require below.
  // / They are initialized here so a path that never reaches the require leaves neither undefined.
  $SecretKey = '';
  $SecretVersion = '';
  $cleanRequiredVersion = ltrim(trim((string)$requiredSecretVersion), 'vV');
  if (isset($LogFile) && is_string($LogFile) && $LogFile !== '') $loggingIsReady = TRUE;
  $isInstallSecret = (substr(basename((string)$secretFile), -11) === '-secret.php');
  $creationIsPermitted = ((string)$CurrentUser !== (string)$ApacheUser or !$isInstallSecret);
  // / Load an existing secret file & decide whether this core may still use it.
  if (file_exists($secretFile)) {
    @chmod($secretFile, 0600);
    // / A file this process cannot read must NEVER reach require. PHP reports that as a
    // / raw warning, which reaches the page & names an absolute path outside the web root.
    // / This happens when a root command line run created the file, because it is then
    // / owned by root & the chmod above fails silently for every other account.
    $secretIsReadable = is_readable($secretFile);
    if (!$secretIsReadable) {
      $rotationNotice = 'The secret file at '.$secretFile.' exists but cannot be read by '.$CurrentUser.'. It was almost certainly created by a different account. '.($creationIsPermitted ? 'Delete it as root & run the command again.' : 'Run the -fp argument as root to replace it.');
      if ($loggingIsReady) warningEntry($rotationNotice);
      else error_log('WARNING!!! HRConvert2: '.$rotationNotice); }
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
    // / A file that survives the unlink belongs to another account. Saying so once is far
    // / more useful than failing the same way on every request with no explanation.
    // / Only a standard user reaches this, for a secret of its own in its own folder, which
    // / -fp does not manage. Until v4.0.1 this sent that user to -fp, which changed nothing.
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
  if (!purgeSensitiveMemory($EnableMemoryProtection, $SecretKey, $SecretVersion, $secret, $secretFile, $secretFileContent, $bytesWritten, $secretGenerated, $existingIsUsable, $detectedSecretVersion, $cleanRequiredVersion, $rotationNotice, $strayOutput, $loggingIsReady, $payloadIsWellFormed, $secretIsReadable, $creationIsPermitted, $isInstallSecret, $requiredSecretVersion)) {
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
// / Every account gets a folder named for itself in the temporary directory. Until v3.9.9
// / every command line account but root shared one, /tmp/.HRConvert2, & whichever ran first
// / created it 0700 & locked every other account out with error 5, -v included.
// / The home directory is not used, because verifyInstallation has already set HOME from a
// / configuration it has not read yet, which leaves it empty.
// / The temporary directory is shared, so another account can create the name first, as a
// / folder of its own, a folder anybody may write or a link. A folder is used only when this
// / account can write it, it is not a link & its mode is exactly 0700, & each refusal says
// / why, because a secret kept where another account can reach it is not a secret.
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
  // / Under cgroup version two this often reads 0::/ and names nothing at all.
  // / That is why this can no longer be required. An earlier release demanded that this
  // / signal AND the file above both agree, which every modern Docker host fails, so a
  // / container was never once detected & --Require Sandbox On Docker-- never applied.
  $cgroupContents = @file_get_contents('/proc/1/cgroup');
  if (is_string($cgroupContents)) {
    if (strpos($cgroupContents, 'docker') !== FALSE or strpos($cgroupContents, 'containerd') !== FALSE or strpos($cgroupContents, 'kubepods') !== FALSE or strpos($cgroupContents, 'lxc') !== FALSE) $cgroupIndicatesContainer = TRUE; }
  // / ANY ONE OF THESE IS CONCLUSIVE. None of them is true on a host that is not a
  // / container, so requiring agreement between them only produced false negatives.
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
// / ROOT RUNS NOTHING THE WEB SERVER ACCOUNT CAN CHANGE. -fp gives the installation to root, &
// / this is what holds it to that afterwards. A command run as root reads the whole tree with
// / treeIsTrustedByRoot before anything in it but the kernel has run. A tree that fails stops
// / the command, names the first thing that failed & says how to give it back to root.
// / Every user but root is unaffected, because nothing they run is root's to protect.
// /
// / THE REPAIR STILL RUNS ON A TREE THAT FAILS, & SAYS SO FIRST. -fp & --setup are what give a
// / tree to root. A fresh clone belongs to whoever cloned it, & a v4.0.0 installation belongs
// / to the web server account, so neither is root's until one of them has run. Refusing them
// / would leave nothing to run. An account that could have changed the tree could also have
// / changed this file, so refusing them would protect nothing. --config --repair is the third,
// / because neither of the others can run without a configuration to read.
// /
// / THE KERNEL IS LOADED FIRST, because this check is part of it. A kernel another account can
// / change inside a tree that root otherwise owns is the one case seen after it has run, & it
// / is still refused before anything else is loaded.
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
      if (!$repairRequested) quickDie('Root refused to run the installation at '.$treePath.', because an account other than root could have changed it. '.$trustNotice.' Run  sudo php convertCore.php -fp  to give it back to root. Any other account may still run -v in the meantime.', 28008);
      warningEntry('Root is running code an account other than root could have changed, because '.$cliCommand.' is what gives the installation at '.$treePath.' back to root. '.$trustNotice);
      print(PHP_EOL.'WARNING. Root is running code an account other than root could have changed.'.PHP_EOL.'  '.$trustNotice.PHP_EOL.'  '.$cliCommand.' gives the installation back to root.'.PHP_EOL); } }
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
  global $URL, $VirusScan, $AllowUserVirusScan, $InstLoc, $ServerRootDir, $ConvertLoc, $LogDir, $LogFile, $ApplicationName, $ApplicationTitle, $SupportedLanguages, $DefaultLanguage, $AllowUserSelectableLanguage, $SupportedGuis, $DefaultGui, $AllowUserSelectableGui, $DeleteThreshold, $Verbose, $MaxLogSize, $Font, $ButtonStyle, $SupportedColors, $AllowUserSelectableColor, $ColorToUse, $ShowGUI, $ShowFinePrint, $TOSURL, $PPURL, $ScanCoreMemoryLimit, $ScanCoreChunkSize, $ScanCoreDebug, $ScanCoreVerbose, $SpinnerStyle, $SpinnerColor, $AllowUserShare, $SupportedConversionTypes, $VersionInfoFile, $Version, $UserArchiveArray, $UserDearchiveArray, $UserDocumentArray, $UserSpreadsheetArray, $UserPresentationInputArray, $UserPresentationOutputArray, $UserXPSInputArray, $UserXPSOutputArray, $UserImageArray, $UserMediaInputArray, $UserMediaOutputArray, $UserVideoInputArray, $UserVideoOutputArray, $UserStreamArray, $UserDrawingArray, $UserSVGInputArray, $UserSVGOutputArray, $UserModelArray, $UserSubtitleInputArray, $UserSubtitleOutputArray, $UserPDFWorkArr, $RARArchiveMethod, $RetryCount, $HomeLoc, $ProprietaryLoc, $StreamWatchTimeout, $StreamConnectionTimeout, $AllowStreamOverHTTP, $StreamInspectionLayers, $StreamInspectionFilesPerLayer, $DefaultStreamInspectionForfeitAction, $MaxStreamInspectionFileSize, $UniqueDailyLogHash, $AppendLogHashToLogFiles, $SecretKey, $SecretFile, $RequiredSecretVersion, $AllowSCADIncludeResolution, $SCADConversionTimeout, $UserSCADArray, $ConfigVersion, $HRConvertVersion, $DeleteBuildEnvironment, $DeleteDevelopmentDocumentation, $RequiredGuiVersion, $RequiredLanguageVersion, $UsePyMeshLab, $RequiredConfigVersion, $EnableAutoUpdates, $AutoUpdateTargetVersion, $UpdateSourceRepository, $MaxUpdatePackageSize, $UpdateConnectionTimeout, $BackupLoc, $RequireSandbox, $ThrowSandboxWarning, $RequireSandboxOnDocker, $RunningFromCLI, $CurrentUser, $RunningAsRoot, $RunningInContainer, $ApacheUser, $PermissionLevels, $AllowBootableIsoImage, $UserBootableIsoArray, $UserEbookInputArray, $UserEbookOutputArray, $EnableMemoryProtection, $ResourceAwarenessActive, $EnableResourceAwareness, $RequireResourceAwareness, $ManagerSocketDir, $DirSep, $CoreManagerVersion, $CoreManagerSubprocessPollInterval, $ResourcePollInterval, $WorkerReapInterval, $WorkerStaleGracePeriod, $TotalResourceBudget, $ReserveResourcePercentage, $MaxConcurrentWorkers, $MaxExpectedRuntime, $MaxRuntimeExtensions, $DefaultConversionCost, $DefaultExpectedRuntime, $CoreLoaded, $PrimaryConvertLoc, $AdditionalConvertLocs, $StorageCleanupInterval, $EnablePerConversionLimits, $MaximumPerConversionResources, $DefaultPerConversionResources, $MinimumPerConversionResources, $RequiredSetupCoreVersion, $RequiredConfigScript, $RequiredDependencyCoreVersion, $RequiredDependsVersion, $RequiredPipelineCoreVersion, $RequiredEngineVersion, $AllowUnprivilegedNamespaces, $MaintainHTAccess, $DataRootLoc, $SessionLoc, $CacheLoc, $EngineDir,
    $UserModelInputArray, $UserModelOutputArray, $UserDrawingInputArray, $UserDrawingOutputArray, $UserImageInputArray, $UserImageOutputArray, $UserSCADInputArray, $UserSCADOutputArray, $EnvironmentManagerMayRepair, $EnvironmentManagerMayRewriteConfigs, $AllowUserURLDownload, $GuiMaxWidth, $DefaultVirusScanner, $URLDownloadMaximumBytes, $URLDownloadsPerSession, $ScanURLDownloads, $LogoURL, $ResolvedLogoURL, $PermittedPrivateRanges, $EngineApplicationVersion, $CodeIsTrusted, $HostedLoc, $ApplicationLoc;
  $rootRecord = array();
  $legacyWasRemoved = FALSE;
  $legacyNotice = $homeTarget = '';
  // / ROOT KEEPS ITS OWN HOME. Every tool a command runs reads its configuration from HOME, curl
  // / its .curlrc & pip its pip.conf among them. $HomeLoc is the data location by default,
  // / which the web server account owns, so root must never take it. Root uses its own home
  // / from the password database, set here before config.php is loaded, because it needs no
  // / configuration to find it. Every other account's HOME is the cache folder & is set once
  // / config.php has been read, further down, because the cache folder is derived from the
  // / configured data location. Until v4.0.1 that HOME was set here too, before config.php was
  // / loaded, so it was empty for the whole process.
  if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
    if (function_exists('posix_getpwuid')) $rootRecord = @posix_getpwuid(0);
    putenv('HOME='.((is_array($rootRecord) && isset($rootRecord['dir']) && (string)$rootRecord['dir'] !== '') ? (string)$rootRecord['dir'] : '/root')); }
  $CoreLoaded = TRUE;
  $InstallationIsVerified = $RunningFromCLI = $RunningAsRoot = $RunningInContainer = FALSE;
  $secretAuthorized = $userSecretAuthorized = $secretIsReady = $configIsValid = $componentIsAvailable = FALSE;
  $SecretKey = $CurrentUser = $detectedConfigVersion = $configFile = $secretFolder = '';
  $applicationSlug = $legacySecretFile = $accountSlug = $listenerCommand = '';
  $secretFolderIsReady = $configRepairRequested = $sandboxPolicyIsValid = $bootstrapWritten = FALSE;
  $bootstrapSetup = $bootstrapEngine = $sandboxPolicyStatus = '';
  $listenerArguments = $bootArguments = array();
  $listenerArgumentCount = $bootstrapCount = $bootArgumentCount = 0;
  $bootCommand = $entryScript = '';
  $runningIsInstalled = FALSE;
  $SecretFile = '';
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
  // / Every folder the web server account creates is its own & nobody else's. It was 0755
  // / until v4.0.1, so every upload & every conversion was readable by every local account.
  // / Apache, PHP, the sandbox & every converter run as the web server account, which owns
  // / the folder, so nothing it serves or converts needs the group or the world to read it.
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
  // / The web server user on the command line is denied the install secret by the rules
  // / above, & is granted it here for the commands that operate the listener. Each of them
  // / derives a startup key or a socket key from the install secret, & a per user secret
  // / derives keys no manager accepts. Until v3.9.9 only the three manager entry points were
  // / listed, so -l run as the web server user started a Core Manager that refused its key
  // / with error 31006, & --status could not ask a running listener anything.
  // / The Engine refuses the listener commands to every account but root & the web server
  // / user before any key is derived. This list names the same commands as its dispatcher.
  // / The web server user's group can read the secret file, so this grants it nothing new.
  // / --run-core-manager is how a service unit starts the listener. Leaving it out of this
  // / list once sent the unit down the per user secret path, so every socket key it derived
  // / differed from the ones the web workers use, & the listener ran & answered nobody.
  // / -c is granted it too, from v4.0.1, because root hands every sweep to the web server user.
  // / The sweep finds its data location the way a web request does, by asking the listener.
  // / The command is read with the kernel's parser. parseCommandLine has not run yet, & two
  // / places deciding what the first argument means is how they come to disagree.
  if ($RunningFromCLI && $CurrentUser === $ApacheUser) {
    list ($listenerArguments, $listenerArgumentCount, $listenerCommand) = parseArgumentVector(isset($_SERVER['argv']) ? $_SERVER['argv'] : array());
    if (in_array($listenerCommand, array('--start-core-manager', '--start-manager', '--run-core-manager', '-l', '--listen', '-k', '--kill', '--kill-all-workers', '--kill-every-worker', '--status', '-c', '--clean'), TRUE)) {
      $secretAuthorized = TRUE;
      $userSecretAuthorized = FALSE; } }
  // / Define what version of HRConvert2 this core file represents.
  // / Note that this number does not have to match the version numbers of individual components listed below.
  // / The version of the core is typically several versions ahead of individual component versions. This is normal.
  $HRConvertVersion = 'v4.0.1';
  $HRConvertVersion = ltrim($HRConvertVersion, 'vV');
  // / Handed to the Engine here, where it is defined, so there is only ever one copy.
  $EngineApplicationVersion = $HRConvertVersion;
  // / Define the minimum acceptable config.php version that this convertCore.php can accept.
  // / This is only raised when a release adds or removes a config setting.
  // / A release that changes no settings leaves this alone, so existing config files keep working.
  // / Any config.php version that is greater (newer) than the version listed below is considered acceptable.
  $RequiredConfigVersion = 'v3.9.6';
  $RequiredConfigVersion = ltrim($RequiredConfigVersion, 'vV');
  // / Define the GUI version that this convertCore.php can accept.
  // / Note that this check looks for the component version to be identical to what is listed below.
  // / A GUI version that does not exactly match the version listed below is not considered acceptable.
  // / This is because Guis are not always guaranteed to be forward or reverse compatible.
  $RequiredGuiVersion = 'v4.0.0';
  $RequiredGuiVersion = ltrim($RequiredGuiVersion, 'vV');
  // / Define the Language Pack version that this convertCore.php can accept.
  // / Note that this check looks for the component version to be identical to what is listed below.
  // / A Language Pack version that does not exactly match the version listed below is not considered acceptable.
  // / This is because Language Packs are not always guaranteed to be forward or reverse compatible.
  $RequiredLanguageVersion = 'v3.9.3';
  $RequiredLanguageVersion = ltrim($RequiredLanguageVersion, 'vV');
  // / No Core Manager version is pinned here. Every manager role is a subcomponent of the
  // / Engine & getAcceptedManagers() inside the Engine pins each one exactly.
  // / The Setup Core component version this core requires.
  // / This is an EXACT match. A component built for another core may not be called safely.
  // / Setup Core holds the configuration model, so this MUST be raised whenever
  // / $RequiredConfigVersion is raised. Forgetting does not break anything immediately.
  // / The utility reports a variable it does not know as unaccounted & carries on.
  $RequiredSetupCoreVersion = 'v4.0.1';
  $RequiredSetupCoreVersion = ltrim($RequiredSetupCoreVersion, 'vV');
  // / The Dependency Core component version this core requires.
  // / This is an EXACT match. A component built for another core may not be called safely.
  $RequiredDependencyCoreVersion = 'v4.0.1';
  $RequiredDependencyCoreVersion = ltrim($RequiredDependencyCoreVersion, 'vV');
  // / The dependency manifest version this core requires.
  // / Raise this whenever a dependency is added, removed, or its minimum version moves.
  // / A manifest from another release may name a package that no longer exists.
  $RequiredDependsVersion = 'v3.9.3';
  $RequiredDependsVersion = ltrim($RequiredDependsVersion, 'vV');
  // / The Pipeline Core component version this core requires.
  // / This is an EXACT match. A component built for another core may declare an entry point
  // / whose arguments have moved, or capabilities this core cannot honour.
  // / Raise this when the Pipeline Core itself changes & for nothing else. It is an Engine
  // / core as of v3.9.9, at Resources/Engine/Cores/pipelineCore.php. The pipelines this
  // / application accepts are listed in its contract, in app-engine-config.php, so adding a
  // / pipeline or moving one to a new version raises nothing here.
  $RequiredPipelineCoreVersion = 'v3.9.9';
  $RequiredPipelineCoreVersion = ltrim($RequiredPipelineCoreVersion, 'vV');
  // / The Engine version this application requires.
  // / This is an EXACT match & it is free, because the Engine is bundled with this release
  // / rather than installed beside it. The updater replaces both together, so the pin & the
  // / file it points at cannot drift apart on an installation nobody has edited by hand.
  // / An administrator who edits the Engine by hand loses the edit at the next update. That
  // / is the same bargain every bundled component makes.
  // / --build cannot write this pin, because it runs inside the Engine. Move it by hand.
  $RequiredEngineVersion = 'v4.0.1';
  $RequiredEngineVersion = ltrim($RequiredEngineVersion, 'vV');
  // / The bootstrap script version this core expects. This is an EXACT match.
  // / The Engine compares the script against this, & -fp disables it as root when it differs.
  // / It said v3.8.0 from v3.9.3 onward, so every sweep tried to disable the shipped script.
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
    print(PHP_EOL.'No configuration file was found. Writing a default one from the template.'.PHP_EOL);
    list ($bootstrapWritten, $bootstrapCount) = generateConfigFile(dirname(__FILE__).DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'config.php');
    if ($bootstrapWritten) print('Wrote '.$bootstrapCount.' setting(s). Every value is a default & is yours to change.'.PHP_EOL.PHP_EOL);
    else print('A configuration file could not be written. Check that Resources is writable by root.'.PHP_EOL.PHP_EOL);
    exit($bootstrapWritten ? 0 : 1); }
  else require_once ($configFile);
  // / The configured installation directory is checked against reality before anything is
  // / derived from it, & this is why config.php may ship with an absolute path at all.
  // / Shipping one is deliberate. An operator who copies this tree onto a LAMP stack & reads
  // / no instructions gets a running application rather than a blank screen, which is worth
  // / more than a tidy default. It only holds if a wrong path cannot be believed, because
  // / every location this core uses is built from this one value & a stale path reads
  // / somebody else's tree or nothing at all.
  // / The directory this file is actually in is the one fact that cannot be wrong, so that is
  // / the fallback. The warning names the setting, so an operator deploying properly is told
  // / once where to fix it rather than left to wonder why their path is ignored.
  // / THE CONFIGURED INSTALLATION DIRECTORY IS THE HOSTED LOCATION. The code is what is running.
  // / Since v4.0.1 the application is installed in Application in the data location, outside the
  // / web root, & the folder the web server serves holds a loader named convertCore.php, the
  // / static files a browser fetches & DATA. $HostedLoc is that folder & $InstLoc is the tree this
  // / file is in, which is where every component is loaded from. Before the first -fp of v4.0.1
  // / they are the same folder, as they were in every earlier release. The installed copy keeps
  // / the configured folder even while its loader is missing, because -fp writes the loader again.
  $ApplicationLoc = rtrim((string)$ConvertLoc, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'Application';
  $runningIsInstalled = (trim((string)$ConvertLoc) !== '' && (string)@realpath(dirname(__FILE__)) === (string)@realpath($ApplicationLoc));
  if (!is_string($InstLoc) or trim($InstLoc) === '' or (!$runningIsInstalled && !is_file(rtrim(trim($InstLoc), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'convertCore.php'))) {
    warningEntry('The configured installation directory does not contain convertCore.php, so '.dirname(__FILE__).' is being used instead. Set --Installation Directory-- in Resources/config.php to the real path.');
    $HostedLoc = dirname(__FILE__); }
  else $HostedLoc = rtrim(trim($InstLoc), DIRECTORY_SEPARATOR);
  $InstLoc = dirname(__FILE__);
  // / A loader ran first when this file was required rather than run, & root ran that as well.
  // / Its tree is read now, before anything in this one is. Until v4.0.1 a configured tree other
  // / than this one was read here, because components could be loaded from it, & none are now.
  // / The result is consumed the same way as the first, for the same reason.
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
  // / A CONFIGURATION MISSING A SETTING STILL LETS --config --repair RUN, & it has to.
  // / Repair is the tool that ADDS a missing setting. Refusing to start because a setting
  // / is missing means the only way to fix it is by hand, which is the loop this whole
  // / mechanism exists to remove.
  // / The same exception already covers a configuration that is missing ENTIRELY. This is
  // / the same argument applied to a configuration that is merely incomplete.
  // / Every other command still refuses, because every other command reads settings & a
  // / setting read as NULL is a silently changed behaviour rather than an error.
  if (!$configIsValid && !$configRepairRequested) quickDie('The config.php file is missing '.count($missingConfigVars).' required setting(s). Config version detected: v'.$detectedConfigVersion.'. Config version required: v'.$RequiredConfigVersion.'. Missing Variables: '.implode(', ', $missingConfigVars), 28000);
  else if (!$configIsValid) warningEntry('The configuration is missing '.count($missingConfigVars).' required setting(s). Only --config --repair can run.');
  // / The data location is a root root owns, & each kind of file lives in a folder of its own
  // / below it. Sessions & the web server account's cache are that account's, root's log folder
  // / is root's, & the install secret, the log folder & the socket folder keep the names they
  // / have always had. Each path is derived once, here, from the configured data location, so a
  // / web request, a command line run & the listener all build the same path. The data location
  // / itself is still $ConvertLoc & still holds the install secret, which is root's & which the
  // / web server account's group reads, so a root owned data location is enough to keep that
  // / account from replacing it & no folder of its own is needed. verifyGlobals narrows
  // / $ConvertLoc to a pooled session area later, so these are taken now, before it does.
  // / See Documentation/ABOUT_MANAGED_LOCATIONS.txt.
  $DataRootLoc = rtrim((string)$ConvertLoc, DIRECTORY_SEPARATOR);
  $SessionLoc = $DataRootLoc.DIRECTORY_SEPARATOR.'Sessions';
  $CacheLoc = $DataRootLoc.DIRECTORY_SEPARATOR.'Cache';
  $EngineDir = $DataRootLoc.DIRECTORY_SEPARATOR.'Engine';
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
  // / The install wide secret lives in the data location, outside the web root.
  if ($secretAuthorized) {
    $SecretFile = $ConvertLoc.DIRECTORY_SEPARATOR.$applicationSlug.'-secret.php';
    $legacySecretFile = $ConvertLoc.DIRECTORY_SEPARATOR.'secret.php';
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
    // / The message names no path, because a halt at boot shows it to the visitor.
    else if (!file_exists($SecretFile)) errorEntry('There is no install secret & the web server account does not create one. Run -fp as root to create it.', 31021, FALSE);
    else list ($secretIsReady, $SecretKey) = resolveSecretFile($SecretFile, $RequiredSecretVersion);
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
  // / Validate the AppArmor profile every sandboxed conversion depends on.
  // / This is not owned by any one conversion pipeline, so it is checked here rather than
  // / from a dependency check. A root run repairs it & every other context reports it.
  // / The check is consumed & not acted on here, because the callee has already logged
  // / whatever it found & convention 9 says the caller need not log it twice.
  // / AN ENVIRONMENT MANAGER PASS ONLY CHECKS IT HERE, although it runs as root. A pass watches
  // / unless its own repair is permitted & due, & that repair is -fp's, which repairs this profile
  // / itself. Repaired here, a pass told only to watch would rewrite a drifted profile every hour
  // / & then report it as fine. The command is read with the kernel's parser, as above.
  if ($RunningAsRoot && $RunningFromCLI) list ($bootArguments, $bootArgumentCount, $bootCommand) = parseArgumentVector(isset($_SERVER['argv']) ? $_SERVER['argv'] : array());
  list ($sandboxPolicyIsValid, $sandboxPolicyStatus) = verifySandboxPolicy($RunningAsRoot && $bootCommand !== '--run-environment-manager');
  // / Resource awareness is no longer decided here & the reason is ordering.
  // / It used to depend on a Core Manager component this function resolved. Every manager
  // / role now lives inside the Engine, & the Engine is verified after this function runs,
  // / because a failure to verify it is worth a warning that has somewhere to be written.
  // / The socket directory is still named here, because the name comes from configuration
  // / rather than from anything the Engine provides.
  // / The main logic decides resource awareness the moment the Engine has loaded.
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
  purgeSensitiveMemory($EnableMemoryProtection, $entryScript, $runningIsInstalled, $bootstrapEngine, $configRepairRequested, $bootstrapSetup, $bootstrapWritten, $bootstrapCount, $sandboxPolicyIsValid, $sandboxPolicyStatus, $secretAuthorized, $userSecretAuthorized, $secretIsReady, $configIsValid, $missingConfigVars, $detectedConfigVersion, $secretFolder, $applicationSlug, $legacySecretFile, $componentIsAvailable, $accountSlug, $secretFolderIsReady, $listenerArguments, $listenerArgumentCount, $listenerCommand, $bootArguments, $bootArgumentCount, $bootCommand, $rootRecord, $legacyWasRemoved, $legacyNotice, $homeTarget, $blockingWereJudged, $limitingWereJudged, $blockingFolders, $limitingFolders, $limitingLocations, $additionalLocation);
  return array($InstallationIsVerified, $configFile, $Version, $CoreLoaded); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to detect the user's IP & user agent.
// / NOTE: As of v3.5.x the IP is NO LONGER used to derive any session identifier.
// / HTTP_CLIENT_IP & HTTP_X_FORWARDED_FOR are attacker-supplied headers & cannot be trusted
// / as a binding factor. They also rotate legitimately on mobile networks, which broke sessions.
// / The token pair is the credential. The IP is retained for logging purposes only.
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
// / A function to create a logfile if one does not exist.
// / Root's logfile is in Engine, & root writes to standard error until that folder exists, for
// / the reason given below. On a terminal root also repeats its warnings & errors there.
// / The log hash suffix is assembled before any filename that uses it is built.
// / The rotation condition compares the file size directly against the configured maximum.
// / It named & rotated a ClamAV log as well until v4.0.0. Nothing had written that file
// / since ClamAV moved into its pipeline, so it was a name & a loop guarding nothing.
function verifyLogs() {
  // / Set variables.
  global $EnginePerManagerLogFiles, $EnginePerWorkerLogFiles, $LogWorkerIdentifier, $LogRole, $RunningFromCLI, $LogDir, $LogFile, $LogHandle, $LogEchoToConsole, $MaxLogSize, $SesHash4, $DefaultLogDir, $DefaultLogSize, $Time, $Date, $LogInc, $ApplicationName, $ConvertLoc, $EngineDir, $AppendLogHashToLogFiles, $EnableMemoryProtection, $RunningAsRoot;
  $LogExists = $logWritten = $LogEchoToConsole = FALSE;
  $logHashAppend = $roleArgument = $workerArgument = $openNotice = '';
  $logHandle = FALSE;
  $LogInc = 0;
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
  if (!isset($LogRole) or (string)$LogRole === '') {
    $LogRole = 'Web';
    if (isset($RunningFromCLI) && $RunningFromCLI) {
      $LogRole = 'Cli';
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
  $LogFile = str_replace('..', '', $LogDir.'/'.$ApplicationName.'_'.$LogRole.'_'.$LogInc.'_'.$Date.$logHashAppend.'.txt');
  if (!is_numeric($MaxLogSize)) $MaxLogSize = $DefaultLogSize;
  // / ROOT LOGS TO A FOLDER OF ITS OWN, Engine/, inside the data root. The web server account's
  // / log folder is that account's & sits inside the data root, so root will not write there,
  // / because that account can swap the whole folder for a link as easily as a name inside it.
  // / Engine/ is root:root inside a data root root owns, so no other account can swap it, & root
  // / opens its logfile there through the kernel, which follows neither a symbolic nor a hard
  // / link. Until the data root is root's — before -fp has run after an upgrade — Engine/ does
  // / not exist, so root falls back to standard error, which a terminal shows & systemd journals.
  // / Until v4.0.1 root created its logfile in the web server account's folder by name & then
  // / chowned it to that account, so a link at that name handed any file on the host over.
  if ($RunningAsRoot) {
    if (isset($EngineDir) && is_string($EngineDir) && is_dir($EngineDir)) {
      $LogFile = str_replace('..', '', $EngineDir.'/'.$ApplicationName.'_'.$LogRole.'_'.$LogInc.'_'.$Date.$logHashAppend.'.txt');
      // / Advance past a logfile that has reached the maximum size, as the web branch does below.
      while (is_file($LogFile) && filesize($LogFile) > $MaxLogSize) {
        $LogInc++;
        $LogFile = str_replace('..', '', $EngineDir.'/'.$ApplicationName.'_'.$LogRole.'_'.$LogInc.'_'.$Date.$logHashAppend.'.txt'); }
      list ($logHandle, $logWritten, $openNotice) = openFileWithoutFollowing($LogFile, 0600);
      if (is_resource($logHandle)) {
        $LogHandle = $logHandle;
        $LogExists = TRUE;
        // / An administrator running root by hand still sees each warning & error as it happens.
        // / They are repeated only when standard error is a terminal, so a timer, a pipe, a
        // / redirect & a build record them in this file alone. The fallback below never repeats,
        // / because there the log is standard error already.
        $LogEchoToConsole = (defined('STDERR') && function_exists('stream_isatty') && @stream_isatty(STDERR)); } }
    // / Fall back to standard error whenever the log folder is not there yet, or a logfile in it
    // / could not be opened. Root that cannot write its own folder still has to be able to speak.
    if (!$LogExists) {
      $logHandle = defined('STDERR') ? STDERR : @fopen('php://stderr', 'w');
      if (is_resource($logHandle)) {
        $LogHandle = $logHandle;
        $LogFile = 'php://stderr';
        $LogExists = TRUE; } } }
  // / The permissions in this function are hard-coded deliberately.
  // / If running as a regular user, the user will either possess or lack the permissions needed to leave the $LogDir in proper condition.
  // / So a regular user fails silently here (and is caught further down), but they also don't do anything to the filesystem on failure.
  // / A web user will always leave a valid $LogDir for a web user.
  else {
    if (!is_dir($LogDir)) @mkdir($LogDir, 0700);
    // / If the $LogDir still doesn't exist after we tried to create it, fall back to trying to create one in the $ConvertLoc instead.
    if (!is_dir($LogDir)) {
      $LogDir = $DefaultLogDir;
      $LogFile = str_replace('..', '', $LogDir.'/'.$ApplicationName.'_'.$LogRole.'_'.$LogInc.'_'.$Date.$logHashAppend.'.txt'); }
    // / Check if the fallback $LogDir exists already before trying to create a new one.
    if (!is_dir($LogDir)) @mkdir($LogDir, 0700);
    // / Regardless of whether or not a $LogDir exists, we will silently try to drop an index.html into it anyway as document root protection.
    if (!file_exists($LogDir.'/index.html')) @copy('index.html', $LogDir.'/index.html');
    // / If the $LogDir still doesn't exist, consider the operation to have failed.
    // / The core will die when this function returns.
    // / An error will be printed to the screen for CLI users, or to the page for web users.
    if (is_dir($LogDir)) {
      // / Advance to a new log file whenever the current one has reached the maximum size.
      while (file_exists($LogFile) && filesize($LogFile) > $MaxLogSize) {
        $LogInc++;
        $LogFile = str_replace('..', '', $LogDir.'/'.$ApplicationName.'_'.$LogRole.'_'.$LogInc.'_'.$Date.$logHashAppend.'.txt'); }
      if (!file_exists($LogFile)) $logWritten = file_put_contents($LogFile, 'Op-Act, '.$Time.': Logfile created using method 1.'.PHP_EOL, FILE_APPEND);
      if (file_exists($LogFile)) $LogExists = TRUE; } }
  // / Anything logged before this point was held rather than written, so it is written now.
  // / An entry raised during boot used to reach the server error log, where it sat apart
  // / from everything around it & was almost never found.
  if ($LogExists) flushLogBuffer();
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $logHandle is a second name for $LogHandle, so clearing it leaves the global open.
  purgeSensitiveMemory($EnableMemoryProtection, $logWritten, $logHashAppend, $roleArgument, $workerArgument, $openNotice, $logHandle);
  return array($LogExists, $LogFile); }
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
  else if ($issueNewSession && $secretIsUsable && $Verbose) logEntry('No session token arrived, so a new session was issued. This is a first visit.');
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
  $GUI = $Color = $Language = $Token1 = $Token2 = $Height = $Width = $Rotate = $Bitrate = $Method = $Download = $UserFilename = $UserExtension = $Archive = $UserScanType = $var = '';
  $variableIsSanitized = $ConvertSelected = $PDFWorkSelected = $FilesToArchive = $FilesToScan = $FilesToDelete = $logoParts = $logoQuery = array();
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
    if (!empty($logoQuery)) $ResolvedLogoURL = 'convertCore.php?'.implode('&', $logoQuery); }
  // / The stream base address is NOT passed through sanitize() & that is deliberate.
  // / sanitize() removes the characters a filename must never hold, which are the ones a
  // / URL is made of. It would return a filename rather than an address.
  // / normalizeStreamBaseURL() in the Engine rebuilds it from parsed components instead &
  // / applies sanitize() to the host, which is the one part that is filename shaped.
  // / It is stored raw here & normalized at the point of use, so the reason a bad one was
  // / refused can be reported to the operator who typed it.
  if (isset($_POST['streamBaseURL'])) $StreamBaseURL = (string)$_POST['streamBaseURL'];
  // / The download address is read raw for the same reason the stream base is. A URL that
  // / has been through sanitize() is a filename & is no longer an address.
  // / fetchUserSuppliedURL rebuilds it & every check a playlist URL receives is applied to
  // / it before anything is fetched.
  if (isset($_POST['userURLDownload'])) $UserURLDownload = (string)$_POST['userURLDownload'];
  if (isset($_POST['pdfextension'])) list ($UserExtension, $variableIsSanitized[$key++]) = sanitize($_POST['pdfextension'], TRUE);
  if (isset($_POST['userpdfconvertfilename'])) list ($UserFilename, $variableIsSanitized[$key++]) = sanitize($_POST['userpdfconvertfilename'], TRUE);
  if (isset($_POST['scantype'])) list ($UserScanType, $variableIsSanitized[$key++]) = sanitize($_POST['scantype'], TRUE);
  if (isset($_POST['filesToScan'])) list ($FilesToScan, $variableIsSanitized[$key++]) = sanitize($_POST['filesToScan'], TRUE);
  // / Check the list of error check results and see if any errors occurred.
  foreach ($variableIsSanitized as $var) if (!$var) ($InputsAreVerified = FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $logoParts, $logoQuery, $variableIsSanitized, $key, $var);
  return array($InputsAreVerified, $ShowGUI, $GUI, $Color, $Language, $Token1, $Token2, $Height, $Width, $Rotate, $Bitrate, $Method, $Download, $UserFilename, $UserExtension, $FilesToArchive, $PDFWorkSelected, $ConvertSelected, $FilesToScan, $FilesToDelete, $UserScanType, $UserURLDownload); }
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
  // / Wide was removed at v3.9.1 & is not a fallback either. A fallback naming an
  // / interface that is not installed is the failure this local list exists to prevent.
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
    // / The file assigns these lowercase names into this scope, which is legitimate & is
    // / invisible to anybody reading this function, because nothing here appears to create
    // / them. Initializing them says where they come from & gives each one a fallback
    // / configuration cannot overwrite.
    // / A style file that omits a colour previously copied nothing into the global for it,
    // / which produced a warning & then a button with no styling at all.
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
  // / A LANGUAGE LISTED IN config.php IS NOT A PROMISE THAT EVERY GUI CARRIES IT.
  // / config.php lists the languages the INSTALLATION offers. A language pack lives inside
  // / a GUI, so whether any given GUI has one is a separate question & the answer is
  // / legitimately no. A GUI author naturalizes the packs they care about & is not obliged
  // / to ship all of them.
  // / This function used to answer that no by returning FALSE, & the caller answers FALSE
  // / with a fatal error 16. Adding a language to config.php therefore broke every GUI
  // / that did not happen to carry it, the moment a user selected it. A feature meant to
  // / be additive was load bearing for every interface at once.
  // / It falls back the way verifyGui() does instead. The selection, then the configured
  // / default, then English, & the first one actually present wins. A user cannot choose
  // / their way out of a missing pack, so the fallback is silent to them & noisy here.
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
    $UserModelInputArray, $UserModelOutputArray, $UserDrawingInputArray, $UserDrawingOutputArray, $UserImageInputArray, $UserImageOutputArray, $UserSCADInputArray, $UserSCADOutputArray, $EnvironmentManagerMayRepair, $EnvironmentManagerMayRewriteConfigs, $AllowUserURLDownload, $GuiMaxWidth, $DefaultVirusScanner, $HostArchitecture, $HostMachineString, $URLDownloadMaximumBytes, $URLDownloadsPerSession, $ScanURLDownloads, $LogoURL, $ResolvedLogoURL, $PermittedPrivateRanges, $DataRootLoc, $SessionLoc, $CacheLoc, $EngineDir, $HostedLoc, $ApplicationLoc;
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
  // / Detached worker variables. Formerly the stream variables & still named for streaming.
  // / A pipeline that launches a process outliving the request reports its PID through the
  // / sixth value of the pipeline contract. convert() records it in these three, & the main
  // / logic reaps it after the connection to the user has closed. Streaming was the first
  // / user of this path & is no longer the only one permitted.
  // / The two stream timeouts are deliberately left in their documented units here.
  // / Do not convert them in this function.
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
  // / The hosted location's path below the document root is the URL path, removed as a PREFIX of
  // / whole folders. It is the hosted location rather than the installation, because since v4.0.1
  // / the installation is outside the web root & only the hosted location is served. A hosted
  // / location outside the document root is taken to be served at the root of --Server URL--,
  // / which is right when a virtual host serves it directly, & the exposure check says so plainly
  // / when it is not.
  // / The host & the path are joined with exactly one separator. sanitizeString deletes every //,
  // / & until v4.0.1 str_replace removed the document root from anywhere in the path & left an
  // / installation outside it a leading slash, so the separator between the host & the path was
  // / deleted with it. The exposure check & every share link then asked a host named like
  // / localhostopt for the file.
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
  // / Capture the data location config.php declared BEFORE anything narrows it, & derive the
  // / folders that hang off it the same way verifyInstallation did, so the web path has them
  // / whichever function set them last. The data location is a root root owns, & sessions live
  // / in Sessions below it. THE POOL IS THE SESSION AREAS, NOT THE ROOTS. $PrimaryConvertLoc is
  // / the primary session area & $AdditionalConvertLocs is rewritten to each additional root's
  // / session area, so a worker & the Resource Manager place & sweep sessions in Sessions & root
  // / keeps the rest of each root to itself. $ConvertLoc becomes the session area THIS SESSION
  // / uses. The socket folder & everything else root owns hang off the root, not off Sessions.
  $EffectiveConversionLimits = array();
  $additionalIndex = 0;
  $additionalEntry = array();
  $DataRootLoc = rtrim((string)$ConvertLoc, $DirSep);
  $ApplicationLoc = $DataRootLoc.$DirSep.'Application';
  $SessionLoc = $DataRootLoc.$DirSep.'Sessions';
  $CacheLoc = $DataRootLoc.$DirSep.'Cache';
  $EngineDir = $DataRootLoc.$DirSep.'Engine';
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
  // / Create a list of directories that will be emptied & remove if found.
  // / These folders are artifacts specifically from previous versions of HRConvert2 that are no longer required.
  // / The folders an older layout left inside the installation are looked for in the hosted
  // / location as well, because that is where the installation was until v4.0.1.
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
  // / The Model lists are read directly, because both are in the required configuration
  // / list & a configuration missing either one never reaches this line.
  // / An earlier version read them with a fallback & left them out of that list, so an
  // / installation would keep starting on an old config. That was wrong twice over.
  // / The required list is what the startup check reads, so a setting absent from it is
  // / never verified & a fallback for it would become permanent.
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
  // / The output list is read rather than derived. It used to be $UserSCADArray with scad
  // / removed, which meant the array had to carry scad first & every other entry had to be
  // / an output. That is a convention held in a comment rather than a declaration, & the
  // / same array was handed to the interface as the upload list, so scad was the only one
  // / of the six that a user could actually upload.
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
// / A function to hold the ImageMagick policy this application requires.
// / Accepts no arguments.
// / Returns the complete contents of a policy.xml file.
// / The stock Debian policy disables the document coders outright, which stops every PDF,
// / PostScript & XPS conversion this application offers. This policy re-enables exactly
// / those & leaves every coder that reaches the network or the filesystem disabled.
// / Every conversion also runs inside a sandbox with no network, so the network coders are
// / already unreachable. They are disabled here as well because a policy that depends on
// / another control being present is not a policy.
function imageMagickPolicyContents() {
  // / Set variables.
  global $EnableMemoryProtection;
  $PolicyContents = '';
  $PolicyContents = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL
    .'<!DOCTYPE policymap ['.PHP_EOL
    .'  <!ELEMENT policymap (policy)*>'.PHP_EOL
    .'  <!ATTLIST policymap xmlns CDATA #FIXED "">'.PHP_EOL
    .'  <!ELEMENT policy EMPTY>'.PHP_EOL
    .'  <!ATTLIST policy xmlns CDATA #FIXED "" domain NMTOKEN #REQUIRED'.PHP_EOL
    .'    name NMTOKEN #IMPLIED pattern CDATA #IMPLIED rights NMTOKEN #IMPLIED'.PHP_EOL
    .'    stealth NMTOKEN #IMPLIED value CDATA #IMPLIED>'.PHP_EOL
    .']>'.PHP_EOL
    .'<!-- Written by HRConvert2. Do not edit by hand. -->'.PHP_EOL
    .'<!-- HRCONVERT2-POLICY-MARKER -->'.PHP_EOL
    .'<policymap>'.PHP_EOL
    .'  <!-- Resource ceilings. A malformed image must not exhaust the host. -->'.PHP_EOL
    .'  <policy domain="resource" name="temporary-path" value="/tmp"/>'.PHP_EOL
    .'  <policy domain="resource" name="memory" value="512MiB"/>'.PHP_EOL
    .'  <policy domain="resource" name="map" value="1GiB"/>'.PHP_EOL
    .'  <policy domain="resource" name="width" value="32KP"/>'.PHP_EOL
    .'  <policy domain="resource" name="height" value="32KP"/>'.PHP_EOL
    .'  <policy domain="resource" name="area" value="512MP"/>'.PHP_EOL
    .'  <policy domain="resource" name="disk" value="4GiB"/>'.PHP_EOL
    .'  <policy domain="resource" name="file" value="768"/>'.PHP_EOL
    .'  <policy domain="resource" name="thread" value="2"/>'.PHP_EOL
    .'  <policy domain="resource" name="time" value="300"/>'.PHP_EOL
    .'  <policy domain="resource" name="list-length" value="128"/>'.PHP_EOL
    .'  <!-- Coders that reach off the machine. A conversion never needs these. -->'.PHP_EOL
    .'  <policy domain="coder" rights="none" pattern="URL"/>'.PHP_EOL
    .'  <policy domain="coder" rights="none" pattern="HTTPS"/>'.PHP_EOL
    .'  <policy domain="coder" rights="none" pattern="HTTP"/>'.PHP_EOL
    .'  <policy domain="coder" rights="none" pattern="FTP"/>'.PHP_EOL
    .'  <policy domain="coder" rights="none" pattern="MSL"/>'.PHP_EOL
    .'  <policy domain="coder" rights="none" pattern="MVG"/>'.PHP_EOL
    .'  <policy domain="coder" rights="none" pattern="EPHEMERAL"/>'.PHP_EOL
    .'  <policy domain="coder" rights="none" pattern="SHOW"/>'.PHP_EOL
    .'  <policy domain="coder" rights="none" pattern="WIN"/>'.PHP_EOL
    .'  <policy domain="coder" rights="none" pattern="PLT"/>'.PHP_EOL
    .'  <policy domain="coder" rights="none" pattern="TEXT"/>'.PHP_EOL
    .'  <policy domain="coder" rights="none" pattern="LABEL"/>'.PHP_EOL
    .'  <!-- Indirect reads. An @ prefixed argument reads a path off the filesystem. -->'.PHP_EOL
    .'  <policy domain="path" rights="none" pattern="@*"/>'.PHP_EOL
    .'  <!-- Delegates are disabled. Ghostscript is reached through the coders below. -->'.PHP_EOL
    .'  <policy domain="delegate" rights="none" pattern="HTTPS"/>'.PHP_EOL
    .'  <policy domain="delegate" rights="none" pattern="HTTP"/>'.PHP_EOL
    .'  <!-- Document coders. HRConvert2 converts these & the stock policy blocks them. -->'.PHP_EOL
    .'  <policy domain="module" rights="read|write" pattern="{PDF,PS,PS2,PS3,EPS,XPS,EPI,EPT}"/>'.PHP_EOL
    .'  <policy domain="coder" rights="read|write" pattern="PDF"/>'.PHP_EOL
    .'  <policy domain="coder" rights="read|write" pattern="PS"/>'.PHP_EOL
    .'  <policy domain="coder" rights="read|write" pattern="PS2"/>'.PHP_EOL
    .'  <policy domain="coder" rights="read|write" pattern="PS3"/>'.PHP_EOL
    .'  <policy domain="coder" rights="read|write" pattern="EPS"/>'.PHP_EOL
    .'  <policy domain="coder" rights="read|write" pattern="XPS"/>'.PHP_EOL
    .'  <!-- Cache & system policies are fixed once written. -->'.PHP_EOL
    .'  <policy domain="cache" name="shared-secret" value="passphrase" stealth="true"/>'.PHP_EOL
    .'  <policy domain="system" name="shred" value="1"/>'.PHP_EOL
    .'  <policy domain="system" name="max-memory-request" value="256MiB"/>'.PHP_EOL
    .'</policymap>'.PHP_EOL;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection);
  return $PolicyContents; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to hold the additions a distribution profile needs to run our sandbox.
// / Accepts no arguments.
// / Returns the contents of an AppArmor local include.
// / A distribution profile is extended, never competed with.
// / Ubuntu ships /etc/apparmor.d/bwrap ending in an include of <local/bwrap>. That include
// / is the supported extension point. Writing there means our additions are pulled into
// / THEIR profile, one attachment governs the binary, & a package update that replaces
// / their file leaves ours untouched because it lives in a different directory.
// / Installing a second profile for the same executable, which an earlier release did,
// / leaves two attachments for one path & no defined outcome.
function sandboxApparmorLocalContents() {
  // / Set variables.
  global $EnableMemoryProtection;
  $LocalContents = '';
  $LocalContents = '# / Written by HRConvert2. Do not edit by hand.'.PHP_EOL
    .'# / HRCONVERT2-POLICY-MARKER'.PHP_EOL
    .'# / Included by /etc/apparmor.d/bwrap through its local include.'.PHP_EOL
    .'# / A package update replaces that profile & never touches this file, so these'.PHP_EOL
    .'# / additions survive an upgrade of the distribution profile.'.PHP_EOL
    .PHP_EOL
    .'  # / Permit the unprivileged user namespace every sandboxed conversion is built in.'.PHP_EOL
    .'  userns,'.PHP_EOL
    .PHP_EOL
    .'  # / Permit the network namespace & the loopback interface set up inside it.'.PHP_EOL
    .'  # / Without these a namespace is created & then fails configuring its own loopback.'.PHP_EOL
    .'  capability net_admin,'.PHP_EOL
    .'  capability sys_admin,'.PHP_EOL
    .'  capability setuid,'.PHP_EOL
    .'  capability setgid,'.PHP_EOL
    .'  network netlink raw,'.PHP_EOL;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection);
  return $LocalContents; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to hold the AppArmor profile that lets bubblewrap create a namespace.
// / Accepts no arguments.
// / Returns the complete contents of an AppArmor profile.
// / Ubuntu 23.10 and later restrict unprivileged user namespaces, which stops bubblewrap
// / dead & takes every sandboxed conversion with it. The supported remedy is a profile that
// / names the binary & grants userns, which is what this is.
// / flags=(unconfined) means this profile adds the userns permission & confines nothing
// / else. The confinement HRConvert2 relies on is the sandbox, not AppArmor.
function sandboxApparmorContents() {
  // / Set variables.
  global $EnableMemoryProtection;
  $ProfileContents = '';
  $ProfileContents = 'abi <abi/4.0>,'.PHP_EOL
    .'include <tunables/global>'.PHP_EOL
    .PHP_EOL
    .'# Written by HRConvert2. Do not edit by hand.'.PHP_EOL
    .'# HRCONVERT2-POLICY-MARKER'.PHP_EOL
    .'# Grants bubblewrap permission to create an unprivileged user namespace.'.PHP_EOL
    .'# Without this, kernel.apparmor_restrict_unprivileged_userns blocks every sandbox.'.PHP_EOL
    .PHP_EOL
    .'profile hrconvert2-bwrap /usr/bin/bwrap flags=(unconfined) {'.PHP_EOL
    .'  userns,'.PHP_EOL
    .'  include if exists <local/hrconvert2-bwrap>'.PHP_EOL
    .'}'.PHP_EOL;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection);
  return $ProfileContents; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to hold a permissive local override for a confined OpenSCAD.
// / Accepts no arguments.
// / Returns the complete contents of an AppArmor local include.
// / This is only written when a distribution already ships an AppArmor profile for OpenSCAD.
// / A confined OpenSCAD cannot read the temporary directory the sandbox binds for it, which
// / presents as a conversion that produces nothing & reports no reason.
function openScadApparmorContents() {
  // / Set variables.
  global $SessionLoc, $EnableMemoryProtection, $DirSep;
  $ProfileContents = '';
  // / The grant is the session area, not the whole data location. Sessions sit in Sessions
  // / below the data location, & that is the only part a conversion reads or writes. The data
  // / location itself is root's & holds the install secret & root's own folders, so a grant on
  // / the whole of it would hand a confined OpenSCAD write access to the secret. Until v4.0.1
  // / the grant was the whole data location, which was the web server account's then.
  $ProfileContents = '# Written by HRConvert2. Do not edit by hand.'.PHP_EOL
    .'# HRCONVERT2-POLICY-MARKER'.PHP_EOL
    .'# Permits a confined OpenSCAD to read & write the locations HRConvert2 binds for it.'.PHP_EOL
    .'# This file is a local include & is only consulted by an existing OpenSCAD profile.'.PHP_EOL
    .PHP_EOL
    .'  owner /tmp/** rwk,'.PHP_EOL
    .'  owner '.rtrim((string)$SessionLoc, $DirSep).'/** rwk,'.PHP_EOL
    .'  /usr/share/openscad/** r,'.PHP_EOL;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection);
  return $ProfileContents; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to validate, install or repair one policy file.
// / Accepts a human readable name, the absolute path, the contents this application
// / requires & a boolean permitting a write, in that order.
// / Returns a validity boolean & a status word, in that order.
// / The status is 'ok', 'installed', 'repaired', 'missing', 'outdated', 'foreign' or 'refused'.
// / A file this application wrote carries a marker. A file WITHOUT that marker belongs to
// / the distribution or to the administrator & is NEVER overwritten without a backup, &
// / never at all outside a repair. Somebody chose that file deliberately.
// / A write is only attempted while running as root. Every other context reports & moves on.
// /
// / A POLICY IN A FOLDER ANOTHER ACCOUNT CAN CHANGE is read & written without following anything.
// / The DATA protection file lives in the DATA staging folder, which the web server account
// / owns. Until v4.0.1 a link planted where that file belongs made one -fp copy the link's
// / target into a backup the web server account could read, overwrite the target, & leave it
// / world readable. That was demonstrated with a stand in for /etc/shadow. In such a folder
// / only a plain file with one name is read as the policy, through readFileWithoutFollowing,
// / so a link or a FIFO there is never followed or waited on. Anything else at the name is not
// / a policy anybody wrote, & is replaced with writeFileWithoutFollowing, which never opens what
// / was there. A file without our marker there is left alone & reported, because no backup of
// / it can be kept safely where another account can change the folder. Until v4.0.1 it was
// / replaced without one. A folder only root can change keeps the behaviour it always had,
// / backup included, & is worked in by the resolved path its trust check judged.
function verifyPolicyFile($policyName, $policyPath, $policyContents, $mayRepair) {
  // / Set variables.
  global $RunningAsRoot, $Verbose, $EnableMemoryProtection;
  $PolicyIsValid = FALSE;
  $PolicyStatus = 'missing';
  $existingContents = $policyDirectory = $backupPath = $readNotice = '';
  $bytesWritten = 0;
  $fileExists = $carriesMarker = $contentsMatch = $directoryIsTrusted = $policyWasWritten = FALSE;
  $resolvedDirectory = $trustNotice = $writeNotice = $trustedPath = '';
  $policyDirectory = dirname($policyPath);
  if (is_dir($policyDirectory)) list ($directoryIsTrusted, $resolvedDirectory, $trustNotice) = pathIsTrustedByRoot($policyDirectory);
  // / A folder root trusts is worked in by the path that was judged, which is the resolved one.
  // / A link on the way there sits in a folder that was never judged, & could be pointed at some
  // / other folder between the check & the write. Until v4.0.1 the name as given was used.
  if ($directoryIsTrusted) $trustedPath = $resolvedDirectory.DIRECTORY_SEPARATOR.basename((string)$policyPath);
  if ($directoryIsTrusted) {
    $fileExists = file_exists($trustedPath);
    if ($fileExists) $existingContents = (string)@file_get_contents($trustedPath); }
  // / In a folder another account can change, only a plain file with one name is a policy.
  else if (is_dir($policyDirectory)) list ($fileExists, $existingContents, $readNotice) = readFileWithoutFollowing($policyPath, 1048576);
  if ($fileExists) {
    $carriesMarker = (strpos($existingContents, 'HRCONVERT2-POLICY-MARKER') !== FALSE);
    $contentsMatch = ($existingContents === $policyContents); }
  // / A file this application wrote, byte for byte as it wants it, needs nothing.
  if ($fileExists && $contentsMatch) {
    $PolicyIsValid = TRUE;
    $PolicyStatus = 'ok'; }
  // / Everything below here is a change to the filesystem & needs root & permission.
  else if (!$mayRepair or !$RunningAsRoot) {
    if (!$fileExists) warningEntry('The '.$policyName.' policy is missing from '.$policyPath.'. Run the -fp argument as root to install it.');
    else if ($carriesMarker) warningEntry('The '.$policyName.' policy at '.$policyPath.' was written by an older release & no longer matches. Run the -fp argument as root to repair it.');
    else warningEntry('The '.$policyName.' policy at '.$policyPath.' was not written by this application & may not permit what HRConvert2 needs. Review it, or run the -fp argument as root to replace it.');
    $PolicyStatus = ($fileExists ? 'foreign' : 'missing');
    if ($fileExists && $carriesMarker) $PolicyStatus = 'outdated'; }
  else {
    if (!is_dir($policyDirectory)) @mkdir($policyDirectory, 0755, TRUE);
    // / The folder's trust is decided again here, because it may have just been created.
    if (is_dir($policyDirectory)) list ($directoryIsTrusted, $resolvedDirectory, $trustNotice) = pathIsTrustedByRoot($policyDirectory);
    if ($directoryIsTrusted) $trustedPath = $resolvedDirectory.DIRECTORY_SEPARATOR.basename((string)$policyPath);
    if (!is_dir($policyDirectory)) warningEntry('The directory '.$policyDirectory.' does not exist & could not be created, so the '.$policyName.' policy was not written.');
    // / A folder another account can change. Nothing is backed up & nothing is followed, so a
    // / file somebody else wrote there is left exactly as it is.
    else if (!$directoryIsTrusted && $fileExists && !$carriesMarker) {
      $PolicyStatus = 'refused';
      warningEntry('The '.$policyName.' policy at '.$policyPath.' was not written by this application & sits in a folder another account can change, where no backup of it can be kept safely, so it was left alone. Move it aside & run -fp again to install this one.'); }
    else if (!$directoryIsTrusted) {
      list ($policyWasWritten, $writeNotice) = writeFileWithoutFollowing($policyPath, $policyContents, 0644);
      if (!$policyWasWritten) warningEntry('The '.$policyName.' policy could not be written to '.$policyPath.'. '.$writeNotice);
      else {
        $PolicyIsValid = TRUE;
        $PolicyStatus = ($fileExists ? 'repaired' : 'installed');
        logEntry('The '.$policyName.' policy was '.$PolicyStatus.' at '.$policyPath.' without following anything already there.'); } }
    else {
      // / Never replace a file without keeping the original. An administrator who tuned a
      // / policy by hand must be able to put it back.
      if ($fileExists && !$carriesMarker) {
        $backupPath = $trustedPath.'.hrconvert2-backup-'.date('Y-m-d-His');
        if (@copy($trustedPath, $backupPath)) warningEntry('The existing '.$policyName.' policy was preserved at '.$backupPath.' before being replaced.');
        // / A backup that failed leaves the file alone, as the warning says. Until v4.0.1 the
        // / name was set before the copy & never cleared, so the file was replaced regardless.
        else {
          $backupPath = '';
          warningEntry('The existing '.$policyName.' policy at '.$policyPath.' could not be backed up. It was left alone.'); } }
      if ($fileExists && !$carriesMarker && $backupPath === '') $PolicyStatus = 'refused';
      else {
        $bytesWritten = @file_put_contents($trustedPath, $policyContents);
        if ($bytesWritten !== strlen($policyContents)) warningEntry('The '.$policyName.' policy could not be written to '.$policyPath.'.');
        else {
          @chmod($trustedPath, 0644);
          $PolicyIsValid = TRUE;
          $PolicyStatus = ($fileExists ? 'repaired' : 'installed');
          logEntry('The '.$policyName.' policy was '.$PolicyStatus.' at '.$policyPath.'.'); } } } }
  if ($Verbose) logEntry('Policy Check: '.$policyName.', Path: '.$policyPath.', Status: '.$PolicyStatus.'.'.($readNotice !== '' ? ' '.$readNotice : ''));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $existingContents, $policyDirectory, $backupPath, $readNotice, $bytesWritten, $fileExists, $carriesMarker, $contentsMatch, $directoryIsTrusted, $policyWasWritten, $resolvedDirectory, $trustNotice, $writeNotice, $trustedPath, $policyName, $policyPath, $policyContents, $mayRepair);
  return array($PolicyIsValid, $PolicyStatus); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to ask ImageMagick where its configuration actually lives.
// / Accepts no arguments.
// / Returns the absolute configuration directory, or an empty string when none is found.
// / Guessing this was wrong & silently broke the policy.
// / A distribution package puts policy.xml in /etc/ImageMagick-7. A build from source puts
// / it under its prefix, usually /usr/local/etc/ImageMagick-7. Picking the first guessed
// / path that happened to exist wrote a version 7 policy into a version 6 directory that a
// / source built version 7 never reads, so the policy did nothing & the stock policy that
// / actually was being read went on refusing every document coder.
// / ImageMagick reports its own configuration path, so it is asked rather than guessed.
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
// / A function to validate or repair the ImageMagick policy.
// / Accepts a boolean permitting a write.
// / Returns a validity boolean & a status word, in that order.
// / The directory is resolved from the binary rather than guessed, so a build from source
// / is corrected where it actually reads rather than somewhere it never looks.
// / An installation with no configuration directory is left alone, because writing a policy
// / for a version that is not installed helps nobody.
// / The result is cached for the request. This is called from the image pipeline, which
// / runs once per file, & the policy cannot change underneath a running request.
function verifyImageMagickPolicy($mayRepair) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection, $DirSep;
  static $cachedValid = NULL;
  static $cachedStatus = 'unchecked';
  static $cachedMayRepair = FALSE;
  $PolicyIsValid = FALSE;
  $PolicyStatus = 'unchecked';
  $policyPath = $configDirectory = '';
  // / A cached result answers every later call but one. A failure found without permission to
  // / repair is checked again when a repair is permitted, because an Environment Manager pass
  // / checks before it repairs, & the cached check would answer the repair with the failure it
  // / was asked to fix.
  if ($cachedValid !== NULL && ($cachedValid or $cachedMayRepair or !$mayRepair)) {
    $PolicyIsValid = $cachedValid;
    $PolicyStatus = $cachedStatus; }
  else {
    $configDirectory = resolveImageMagickConfigDir();
    if ($configDirectory === '') {
      $PolicyStatus = 'absent';
      if ($Verbose) logEntry('Policy Check: ImageMagick, Status: absent, no configuration directory is installed.'); }
    else {
      $policyPath = $configDirectory.$DirSep.'policy.xml';
      list ($PolicyIsValid, $PolicyStatus) = verifyPolicyFile('ImageMagick', $policyPath, imageMagickPolicyContents(), $mayRepair); }
    $cachedValid = $PolicyIsValid;
    $cachedStatus = $PolicyStatus;
    $cachedMayRepair = (bool)$mayRepair; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $policyPath, $configDirectory, $mayRepair);
  return array($PolicyIsValid, $PolicyStatus); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to validate or repair the OpenSCAD AppArmor override.
// / Accepts a boolean permitting a write.
// / Returns a validity boolean & a status word, in that order.
// / Nothing is written unless a confining profile already exists.
// / Most distributions do not confine OpenSCAD at all. Installing an override for a profile
// / that is not there would leave a file nothing reads & imply a problem that is not present.
// / The result is cached for the request.
function verifyOpenScadPolicy($mayRepair) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  static $cachedValid = NULL;
  static $cachedStatus = 'unchecked';
  static $cachedMayRepair = FALSE;
  $PolicyIsValid = FALSE;
  $PolicyStatus = 'unchecked';
  $confiningProfile = $overridePath = '';
  $candidateProfiles = array('/etc/apparmor.d/usr.bin.openscad', '/etc/apparmor.d/openscad');
  $candidateProfile = '';
  // / A cached result answers every later call but one. A failure found without permission to
  // / repair is checked again when a repair is permitted, because an Environment Manager pass
  // / checks before it repairs, & the cached check would answer the repair with the failure it
  // / was asked to fix.
  if ($cachedValid !== NULL && ($cachedValid or $cachedMayRepair or !$mayRepair)) {
    $PolicyIsValid = $cachedValid;
    $PolicyStatus = $cachedStatus; }
  else {
    foreach ($candidateProfiles as $candidateProfile) {
      if ($confiningProfile === '' && file_exists($candidateProfile)) $confiningProfile = $candidateProfile; }
    if ($confiningProfile === '') {
      // / No profile confines OpenSCAD, so there is nothing to override & nothing is wrong.
      $PolicyIsValid = TRUE;
      $PolicyStatus = 'unconfined';
      // / Logged once per request rather than per conversion. A host where nothing confines
      // / OpenSCAD reports this on every single model, which is noise about a non event.
      // / The static cache above already prevents the check running twice, but the entry was
      // / written before the cache was consulted.
      if ($Verbose) logEntry('Policy Check: OpenSCAD, Status: unconfined, nothing confines it & the sandbox is what isolates it.'); }
    else {
      $overridePath = '/etc/apparmor.d/local/'.basename($confiningProfile);
      list ($PolicyIsValid, $PolicyStatus) = verifyPolicyFile('OpenSCAD AppArmor', $overridePath, openScadApparmorContents(), $mayRepair);
      if ($PolicyStatus === 'installed' or $PolicyStatus === 'repaired') reloadApparmorProfile($confiningProfile); }
    $cachedValid = $PolicyIsValid;
    $cachedStatus = $PolicyStatus;
    $cachedMayRepair = (bool)$mayRepair; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $confiningProfile, $overridePath, $candidateProfiles, $candidateProfile, $mayRepair);
  return array($PolicyIsValid, $PolicyStatus); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to install or repair the DATA directory protection file.
// / Accepts a boolean permitting repair.
// / Returns a validity boolean & the status word, in that order.
// / This is the same shape as every other policy this application maintains, so it backs up
// / a file it did not write before replacing it & refuses rather than clobbering one it
// / cannot back up.
// / INSTALLING THIS FILE IS NOT THE SAME AS PROVING IT WORKS. verifyDataExposure() is what
// / proves it, & this function must never be reported as if it had.
function verifyDataProtectionPolicy($mayRepair) {
  // / Set variables.
  global $ConvertTemp, $DirSep, $MaintainHTAccess, $EnableMemoryProtection;
  $PolicyIsValid = FALSE;
  $PolicyStatus = 'unchecked';
  $protectionPath = '';
  // / A local fallback the configuration cannot remove by omission, on the doctrine every
  // / other setting here follows. --Maintain HTAccess-- is a required setting, so a config
  // / lacking it is refused long before this runs, but a read that assumes a global exists
  // / is exactly the assumption this codebase does not make.
  $maintainHtaccess = TRUE;
  if (isset($MaintainHTAccess)) $maintainHtaccess = (bool)$MaintainHTAccess;
  if ((string)$ConvertTemp === '') warningEntry('The DATA location is not set, so its protection file could not be checked.');
  else if (!$maintainHtaccess) {
    // / The administrator has said not to maintain this file, so it is not maintained & the
    // / server configuration is carrying the rules alone. This is a valid configuration &
    // / is reported as a deliberate state rather than as a missing one.
    // / The removal of a file this application previously wrote lives in
    // / maintainDataProtection() & is called rather than repeated, so -fp does it now
    // / instead of leaving it for whichever web request happens to arrive next.
    maintainDataProtection();
    $PolicyIsValid = TRUE;
    $PolicyStatus = 'disabled'; }
  else {
    $protectionPath = $ConvertTemp.$DirSep.'.htaccess';
    list ($PolicyIsValid, $PolicyStatus) = verifyPolicyFile('DATA Directory', $protectionPath, dataProtectionContents(), $mayRepair); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $protectionPath, $maintainHtaccess, $mayRepair);
  return array($PolicyIsValid, $PolicyStatus); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to list the forms a path takes, as it is written & as it resolves.
// / Accepts the path. Returns each form once, without a trailing separator, with the top of the
// / filesystem as a lone separator. An empty path has no forms.
function pathForms($pathText) {
  // / Set variables.
  global $EnableMemoryProtection;
  $PathForms = array();
  $pathForm = $resolvedPath = '';
  $pathForm = rtrim((string)$pathText, DIRECTORY_SEPARATOR);
  if ($pathForm === '' && (string)$pathText !== '') $pathForm = DIRECTORY_SEPARATOR;
  // / An empty path is never resolved, because realpath answers an empty path with the current
  // / folder & the comparison would then be against wherever the command happened to be run.
  if ($pathForm !== '') {
    $PathForms[] = $pathForm;
    $resolvedPath = (string)@realpath($pathForm);
    if ($resolvedPath !== '' && $resolvedPath !== $pathForm) $PathForms[] = $resolvedPath; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $PathForms is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $pathForm, $resolvedPath, $pathText);
  return $PathForms; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether one path holds another.
// / Accepts the outer path & the inner path, in that order.
// / Returns TRUE when the inner path is the outer path or lies anywhere below it.
// / The comparison is by whole folders, so /var/www/html2 is not inside /var/www/html.
// / Each path is judged as it is written & as it resolves, & either way is enough. The web
// / server walks a path as it is written, so a location written inside the document root is
// / served even when a link there leads somewhere else, & a link can lead back in from outside.
function pathHoldsPath($outerPath, $innerPath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $PathIsHeld = FALSE;
  $outerForm = $innerForm = '';
  foreach (pathForms($outerPath) as $outerForm) foreach (pathForms($innerPath) as $innerForm) if ($innerForm === $outerForm or strpos($innerForm.DIRECTORY_SEPARATOR, rtrim($outerForm, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR) === 0) $PathIsHeld = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $outerForm, $innerForm, $outerPath, $innerPath);
  return $PathIsHeld; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to list the root of every data location, the primary first & then each pool member.
// / Accepts nothing. Returns the list, without repeats.
// / A pool member is declared by its root & verifyGlobals rewrites it to the Sessions folder
// / below that root, so the root is that folder's parent. Either form is understood here.
function dataLocationRoots() {
  // / Set variables.
  global $DataRootLoc, $AdditionalConvertLocs, $EnableMemoryProtection;
  $DataRoots = array();
  $additionalEntry = array();
  $additionalPath = '';
  if (isset($DataRootLoc) && is_string($DataRootLoc) && trim($DataRootLoc) !== '') $DataRoots[] = rtrim($DataRootLoc, DIRECTORY_SEPARATOR);
  if (isset($AdditionalConvertLocs) && is_array($AdditionalConvertLocs)) {
    foreach ($AdditionalConvertLocs as $additionalEntry) {
      if (!is_array($additionalEntry) or !isset($additionalEntry[0]) or !is_string($additionalEntry[0]) or trim($additionalEntry[0]) === '') continue;
      $additionalPath = rtrim($additionalEntry[0], DIRECTORY_SEPARATOR);
      $DataRoots[] = (basename($additionalPath) === 'Sessions') ? dirname($additionalPath) : $additionalPath; } }
  $DataRoots = array_values(array_unique($DataRoots));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $DataRoots is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $additionalEntry, $additionalPath);
  return $DataRoots; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to list every data location inside the web server's document root.
// / Accepts nothing. Returns the roots of those locations, or nothing when none is inside.
// / The data location is never meant to be served. Inside the document root its Sessions, Cache
// / & Logs folders could be fetched by anybody who guessed a name, because the web server account
// / that serves them can read them. Only the DATA tree inside the installation is served.
// / Nothing checked where the data location was until v4.0.1.
function misplacedDataRoots() {
  // / Set variables.
  global $ServerRootDir, $EnableMemoryProtection;
  $MisplacedRoots = array();
  $dataRoot = '';
  // / With no document root configured there is nothing to be inside.
  if (isset($ServerRootDir) && is_string($ServerRootDir) && trim($ServerRootDir) !== '') foreach (dataLocationRoots() as $dataRoot) if (pathHoldsPath($ServerRootDir, $dataRoot)) $MisplacedRoots[] = $dataRoot;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $MisplacedRoots is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $dataRoot);
  return $MisplacedRoots; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to prove whether the DATA tree serves user content as inert downloads.
// / Accepts no arguments.
// / Returns a protection boolean, a status word & a sentence explaining it, in that order.
// /
// / This asks the server. It does not read a file off the disk & assume.
// / That distinction is the entire reason this function exists. A .htaccess is only read
// / when AllowOverride is enabled for its directory; when it is not, Apache ignores it
// / silently, with no error, no log line & no warning. Debian, Ubuntu & the standard
// / php:*-apache container all ship AllowOverride None for /var/www.
// / So the presence of the file proves nothing whatsoever about
// / whether its rules are in force, & a check that tested for the file would report success
// / on exactly the installations that are exposed.
// /
// / A canary is written into the DATA tree, fetched over HTTP & removed again. The canary
// / carries a script element so the probe has the same shape as the real thing, but the
// / script does nothing at all.
// /
// / A failure to reach the server is not a pass & is not a failure.
// / $URL may be an external name this host cannot resolve from inside itself, & during a
// / --setup run the web server may not be listening yet. Either way the honest answer is
// / that this was not established, which is a warning & a distinct status word, never a
// / silent success.
// /
// / The probe is written with writeFileWithoutFollowing & is never changed by name afterwards.
// / The DATA tree belongs to the web server account & this runs as root from -v, -fp & the
// / Environment Manager. A probe written by name & then chmodded could be swapped for a link in
// / between, & the chmod would land on whatever the link pointed at.
function verifyDataExposure() {
  // / Set variables.
  global $ConvertTemp, $FullURL, $DirSep, $Verbose, $RunningAsRoot, $ApacheUser, $EnableMemoryProtection;
  $DataIsProtected = FALSE;
  $ExposureStatus = 'unverified';
  $ExposureDetail = '';
  $canaryName = $canaryPath = $canaryURL = $responseHeaders = '';
  $requestSucceeded = $carriesDisposition = $carriesSandbox = $canaryWasWritten = $ownerWasChanged = $canaryWasRemoved = FALSE;
  $writeNotice = $ownerNotice = $removalNotice = '';
  if ((string)$ConvertTemp === '' or (string)$FullURL === '') {
    $ExposureStatus = 'unverified';
    $ExposureDetail = 'The DATA location or the server URL is not set, so nothing could be tested.';
    warningEntry('The DATA directory exposure check could not run because the DATA location or the server URL is not set.'); }
  else {
    // / A DATA folder root has to create belongs to the web server account, like every other.
    if (!is_dir($ConvertTemp) && @mkdir($ConvertTemp, 0700, TRUE) && $RunningAsRoot) {
      list ($ownerWasChanged, $ownerNotice) = changeOwnerWithoutFollowing($ConvertTemp, $ApacheUser, $ApacheUser);
      if (!$ownerWasChanged) warningEntry($ownerNotice); }
    $canaryName = 'hrc2-protection-canary-'.substr(bin2hex(random_bytes(8)), 0, 16).'.svg';
    $canaryPath = $ConvertTemp.$DirSep.$canaryName;
    $canaryURL = rtrim((string)$FullURL, '/').'/DATA/'.$canaryName;
    // / The probe carries a script element so it has the same shape as a real upload. The
    // / script is empty, so a browser that did run it would do nothing.
    list ($canaryWasWritten, $writeNotice) = writeFileWithoutFollowing($canaryPath, '<svg xmlns="http://www.w3.org/2000/svg"><script>/* inert probe */</script></svg>', 0644);
    if (!$canaryWasWritten) {
      $ExposureStatus = 'unverified';
      $ExposureDetail = 'A probe file could not be written into '.$ConvertTemp.', so nothing could be tested.';
      warningEntry('The DATA directory exposure check could not write its probe into '.$ConvertTemp.'. '.$writeNotice); }
    else {
      // / The self request lives in the Engine. Without one there is nothing to test with, &
      // / the existing unreachable wording is already the right answer: exposure is neither
      // / confirmed nor cleared & the operator is told to check by hand. The warning names the
      // / Engine so the log says WHY it could not be established rather than only that it was not.
      if (function_exists('fetchOwnResponseHeaders')) list ($requestSucceeded, $responseHeaders) = fetchOwnResponseHeaders($canaryURL, 5);
      else warningEntry('The DATA directory exposure check needs the Engine to request a URL from this server & the Engine is unavailable. The exposure of this installation was NOT established.');
      // / Remove the probe whatever happened. It must never outlive the check that made it.
      // / The DATA folder is the web server account's, so root removes it through the kernel.
      list ($canaryWasRemoved, $removalNotice) = removeFileWithoutFollowing($canaryPath);
      if (!$canaryWasRemoved) warningEntry('The DATA directory exposure probe could not be removed. '.$removalNotice);
      if (!$requestSucceeded) {
        $ExposureStatus = 'unreachable';
        $ExposureDetail = 'This server could not fetch '.$canaryURL.' from itself, so its exposure was not established. Check it by hand from a browser.';
        warningEntry('The DATA directory exposure check could not reach '.$canaryURL.'. The exposure of this installation was NOT established. Verify it by hand.'); }
      // / A 5xx is NOT exposure & must never be reported as one. It means this tree is
      // / serving nothing at all, so every download & every share link on this server is
      // / broken right now. That is a worse outage than the exposure & it needs its own
      // / word, its own explanation & the first place to look.
      // / The overwhelmingly common cause is a directive in the protection file that the
      // / directory's AllowOverride level does not permit. Apache does not ignore such a
      // / directive, it refuses the request. An <IfModule> guard does not help, because it
      // / tests whether a module is loaded rather than whether the directive is allowed.
      else if (stripos($responseHeaders, ' 500') !== FALSE or stripos($responseHeaders, ' 503') !== FALSE) {
        $ExposureStatus = 'broken';
        $ExposureDetail = 'The server returned an error for '.$canaryURL.'. NO download & NO share link works right now. Check the web server error log for a line naming DATA/.htaccess.';
        errorEntry('The DATA directory is returning a server error, so no download & no share link can work. The usual cause is a directive in DATA/.htaccess that this directory\'s AllowOverride level does not permit; the web server error log names it. Delete that file to restore service, then apply the rules in the server configuration instead.', 503, FALSE); }
      else if (stripos($responseHeaders, '404 ') !== FALSE or stripos($responseHeaders, '403 ') !== FALSE) {
        $ExposureStatus = 'unreachable';
        $ExposureDetail = 'The server refused or did not find '.$canaryURL.'. Either --Server URL-- does not describe this installation, or the DATA tree is not served at that path.';
        warningEntry('The DATA directory exposure check received a refusal for '.$canaryURL.'. The --Server URL-- setting may not describe this installation.'); }
      else {
        $carriesDisposition = (stripos($responseHeaders, 'content-disposition:') !== FALSE && stripos($responseHeaders, 'attachment') !== FALSE);
        $carriesSandbox = (stripos($responseHeaders, 'content-security-policy:') !== FALSE && stripos($responseHeaders, 'sandbox') !== FALSE);
        // / Either one closes it. Both are set together, so one missing is worth saying.
        if ($carriesDisposition or $carriesSandbox) {
          $DataIsProtected = TRUE;
          $ExposureStatus = 'protected';
          $ExposureDetail = 'The DATA tree is served as inert content.';
          if (!$carriesDisposition) $ExposureDetail = $ExposureDetail.' Content-Disposition is absent & the CSP is carrying this alone.';
          if (!$carriesSandbox) $ExposureDetail = $ExposureDetail.' The CSP is absent & Content-Disposition is carrying this alone.';
          if ($Verbose) logEntry('DATA Exposure Check: protected. '.($carriesDisposition ? 'Content-Disposition present. ' : '').($carriesSandbox ? 'CSP sandbox present.' : '')); }
        else {
          $ExposureStatus = 'exposed';
          $ExposureDetail = 'The DATA tree serves user supplied files as renderable documents. An uploaded SVG will execute its own script in this origin.';
          // / This is a warning rather than an error because the application still works &
          // / an administrator can still use it. It is loud because nothing else will say so.
          warningEntry('THE DATA DIRECTORY IS EXPOSED. User supplied files at '.rtrim((string)$FullURL, '/').'/DATA/ are served as renderable documents rather than as downloads, so an uploaded SVG will execute its own script in this origin. If you installed the DATA/.htaccess, it is being ignored; Apache reads one only where AllowOverride is enabled for that directory. Run the -fp argument as root to write & activate the rules in the server configuration instead. See Documentation/ABOUT_DATA_DIRECTORY_PROTECTION.txt.'); } } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $canaryWasRemoved, $removalNotice, $canaryName, $canaryPath, $canaryURL, $responseHeaders, $requestSucceeded, $carriesDisposition, $carriesSandbox, $canaryWasWritten, $writeNotice, $ownerWasChanged, $ownerNotice);
  return array($DataIsProtected, $ExposureStatus, $ExposureDetail); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to check, & optionally correct, the kernel settings a sandbox needs.
// / Accepts a boolean permitting a write.
// / Returns a readiness boolean & an array of findings, in that order.
// /
// / THREE SEPARATE THINGS BLOCK AN UNPRIVILEGED USER NAMESPACE & ONLY ONE IS AppArmor.
// / An AppArmor profile granting userns covers Ubuntu 23.10 & later. It does nothing on a
// / Debian host, where the blocker is kernel.unprivileged_userns_clone reading zero, & it
// / does nothing where user.max_user_namespaces has been set to zero.
// /
// / A host that blocks the namespace makes bubblewrap fall back to its setuid path, where
// / it can unshare the network but has already dropped the privilege needed to configure
// / loopback inside it. That surfaces as a loopback error rather than a namespace error,
// / which points an operator at networking when the cause is the namespace.
// / A correction is written to /etc/sysctl.d so it survives a reboot.
function verifySandboxKernel($mayRepair) {
  // / Set variables.
  global $RunningAsRoot, $AllowUnprivilegedNamespaces, $RunningInContainer, $Verbose, $EnableMemoryProtection;
  $KernelIsReady = TRUE;
  $KernelFindings = array();
  $sysctlPath = $sysctlValue = $persistPath = $persistContents = $bwrapBinary = '';
  $commandOutput = array();
  $commandExitCode = 0;
  $bytesWritten = 0;
  $profileIsLoaded = FALSE;
  $profileLoadStatus = '';
  // / A container inherits these from its host & cannot change them.
  // / /proc/sys is mounted read only in every unprivileged container, so a repair here can
  // / only ever fail. Reporting FAILED for something that was never this machine's to fix
  // / sends an operator hunting inside the container for a setting that lives outside it.
  // / The values are still READ & reported, because they are exactly what decides whether
  // / the sandbox works, & knowing them is what tells an operator to go and fix the host.
  if ($RunningInContainer) $mayRepair = FALSE;
  // / Debian gates unprivileged namespaces behind this. Ubuntu does not ship it at all.
  $sysctlPath = '/proc/sys/kernel/unprivileged_userns_clone';
  if (file_exists($sysctlPath)) {
    $sysctlValue = trim((string)@file_get_contents($sysctlPath));
    if ($sysctlValue === '1') $KernelFindings[] = array('Check' => 'unprivileged_userns_clone', 'Status' => 'ok', 'Detail' => 'Unprivileged user namespaces are permitted.');
    else if (!$mayRepair or !$RunningAsRoot) {
      $KernelIsReady = FALSE;
      $KernelFindings[] = array('Check' => 'unprivileged_userns_clone', 'Status' => 'BLOCKED', 'Detail' => 'Reads '.$sysctlValue.'. Bubblewrap cannot build a namespace. '.($RunningInContainer ? 'This is a HOST setting. Correct it on the host, not in this container.' : 'Run -fp as root.')); }
    else {
      @file_put_contents($sysctlPath, '1');
      $sysctlValue = trim((string)@file_get_contents($sysctlPath));
      if ($sysctlValue !== '1') {
        $KernelIsReady = FALSE;
        $KernelFindings[] = array('Check' => 'unprivileged_userns_clone', 'Status' => 'FAILED', 'Detail' => 'Could not be set to 1.'); }
      else $KernelFindings[] = array('Check' => 'unprivileged_userns_clone', 'Status' => 'corrected', 'Detail' => 'Set to 1 & persisted.'); } }
  // / A namespace budget of zero refuses every namespace whatever else is permitted.
  $sysctlPath = '/proc/sys/user/max_user_namespaces';
  if (file_exists($sysctlPath)) {
    $sysctlValue = trim((string)@file_get_contents($sysctlPath));
    if ((int)$sysctlValue > 0) $KernelFindings[] = array('Check' => 'max_user_namespaces', 'Status' => 'ok', 'Detail' => $sysctlValue.' namespaces permitted.');
    else if (!$mayRepair or !$RunningAsRoot) {
      $KernelIsReady = FALSE;
      $KernelFindings[] = array('Check' => 'max_user_namespaces', 'Status' => 'BLOCKED', 'Detail' => 'Reads 0. No namespace can be created. '.($RunningInContainer ? 'This is a HOST setting. Correct it on the host, not in this container.' : 'Run -fp as root.')); }
    else {
      @file_put_contents($sysctlPath, '15000');
      if ((int)trim((string)@file_get_contents($sysctlPath)) < 1) {
        $KernelIsReady = FALSE;
        $KernelFindings[] = array('Check' => 'max_user_namespaces', 'Status' => 'FAILED', 'Detail' => 'Could not be raised.'); }
      else $KernelFindings[] = array('Check' => 'max_user_namespaces', 'Status' => 'corrected', 'Detail' => 'Raised & persisted.'); } }
  // / Ubuntu gates the namespace behind AppArmor rather than behind a clone sysctl, so the
  // / two checks above can both read fine while this one is what refuses.
  $sysctlPath = '/proc/sys/kernel/apparmor_restrict_unprivileged_userns';
  if (file_exists($sysctlPath)) {
    $sysctlValue = trim((string)@file_get_contents($sysctlPath));
    if ($sysctlValue !== '1') $KernelFindings[] = array('Check' => 'apparmor userns restrict', 'Status' => 'ok', 'Detail' => 'Not restricting unprivileged namespaces.');
    else {
      // / Restricted. A namespace is only permitted to a binary carrying a profile that
      // / grants userns, so whether ours is LOADED is the whole question.
      list ($profileIsLoaded, $profileLoadStatus) = apparmorProfileIsLoaded('hrconvert2-bwrap');
      if ($profileIsLoaded) $KernelFindings[] = array('Check' => 'apparmor userns restrict', 'Status' => 'ok', 'Detail' => 'Restricted, & the hrconvert2-bwrap profile is loaded.');
      else {
        $KernelIsReady = FALSE;
        $KernelFindings[] = array('Check' => 'apparmor userns restrict', 'Status' => 'BLOCKED', 'Detail' => 'Restricted & the hrconvert2-bwrap profile is '.$profileLoadStatus.'. Run -fp as root.'); } } }
  // / A competing profile for the same binary is undefined behaviour. Name it rather than
  // / adding a second one beside it & hoping the right one wins.
  // / A profile loaded with no file behind it is an orphan from an earlier release. It is
  // / still attached to the binary & is invisible to anything that only lists /etc.
  list ($profileIsLoaded, $profileLoadStatus) = apparmorProfileIsLoaded('hrconvert2-bwrap');
  if ($profileIsLoaded && !file_exists('/etc/apparmor.d/hrconvert2-bwrap')) {
    if ($mayRepair && $RunningAsRoot) {
      unloadApparmorProfile('/etc/apparmor.d/hrconvert2-bwrap', 'hrconvert2-bwrap');
      list ($profileIsLoaded, $profileLoadStatus) = apparmorProfileIsLoaded('hrconvert2-bwrap');
      $KernelFindings[] = array('Check' => 'orphaned profile', 'Status' => ($profileIsLoaded ? 'STILL LOADED' : 'removed'), 'Detail' => ($profileIsLoaded ? 'hrconvert2-bwrap is loaded with no file behind it & would not unload. Reboot to clear it.' : 'An orphaned hrconvert2-bwrap profile was unloaded.')); }
    else $KernelFindings[] = array('Check' => 'orphaned profile', 'Status' => 'WARNING', 'Detail' => 'hrconvert2-bwrap is loaded with no file behind it. Run -fp as root to unload it.'); }
  // / This is the setting that refuses the namespace on Ubuntu 23.10 & later.
  // / With it at 1 the kernel demands an AppArmor profile granting userns before an
  // / unprivileged process may create one, & bubblewrap fails writing its uid map long
  // / before it reaches anything to do with networking.
  // /
  // / HRConvert2 SETS THIS TO ZERO, DELIBERATELY, & THAT IS A NET SECURITY GAIN.
  // / The restriction protects a general purpose desktop from a hostile local user. This
  // / machine is a dedicated conversion appliance whose local users are the administrator
  // / & the web server. Leaving it at 1 does not make that machine safer, it disables the
  // / sandbox that isolates every hostile FILE the machine exists to process, which is the
  // / threat that actually arrives here. A hardening measure that switches off the primary
  // / isolation mechanism is a loss, not a gain.
  // / Set --Allow Unprivileged Namespaces-- to FALSE in config.php to leave it alone.
  $sysctlPath = '/proc/sys/kernel/apparmor_restrict_unprivileged_userns';
  if (file_exists($sysctlPath)) {
    $sysctlValue = trim((string)@file_get_contents($sysctlPath));
    if ($sysctlValue === '0') $KernelFindings[] = array('Check' => 'apparmor userns restrict', 'Status' => 'ok', 'Detail' => 'Not restricting. Bubblewrap can create a namespace.');
    else if (isset($AllowUnprivilegedNamespaces) && !$AllowUnprivilegedNamespaces) {
      $KernelIsReady = FALSE;
      $KernelFindings[] = array('Check' => 'apparmor userns restrict', 'Status' => 'BLOCKED', 'Detail' => 'Restricted & config.php forbids changing it. The sandbox cannot work.'); }
    else if (!$mayRepair or !$RunningAsRoot) {
      $KernelIsReady = FALSE;
      $KernelFindings[] = array('Check' => 'apparmor userns restrict', 'Status' => 'BLOCKED', 'Detail' => 'Reads 1. Bubblewrap cannot write a uid map. '.($RunningInContainer ? 'This is a HOST setting. Correct it on the host, or run this container with --cap-add SYS_ADMIN.' : 'Run -fp as root.')); }
    else {
      @file_put_contents($sysctlPath, '0');
      $sysctlValue = trim((string)@file_get_contents($sysctlPath));
      if ($sysctlValue !== '0') {
        $KernelIsReady = FALSE;
        $KernelFindings[] = array('Check' => 'apparmor userns restrict', 'Status' => 'FAILED', 'Detail' => 'Could not be set to 0.'); }
      else $KernelFindings[] = array('Check' => 'apparmor userns restrict', 'Status' => 'corrected', 'Detail' => 'Set to 0 & persisted. The sandbox can now build a namespace.'); } }
  // / A setuid bubblewrap is a symptom rather than a cause. It is how a distribution shipped
  // / a working sandbox on a host that forbids the namespace, & it is the configuration that
  // / produces the loopback error. Report it so the cause is not mistaken for networking.
  $bwrapBinary = locateDependency('bwrap');
  if ($bwrapBinary !== '' && file_exists($bwrapBinary) && (fileperms($bwrapBinary) & 0x800)) $KernelFindings[] = array('Check' => 'bubblewrap mode', 'Status' => 'setuid', 'Detail' => 'Running setuid, which cannot configure loopback in a new network namespace. Permitting unprivileged namespaces lets it stop.');
  // / Persist whatever was corrected, so a reboot does not undo it.
  if ($mayRepair && $RunningAsRoot && is_dir('/etc/sysctl.d')) {
    $persistPath = '/etc/sysctl.d/99-hrconvert2-userns.conf';
    $persistContents = '# / Written by HRConvert2. Permits the unprivileged user namespaces bubblewrap needs.'.PHP_EOL
      .'# / Remove this file & reboot to restore the distribution default.'.PHP_EOL
      .'kernel.unprivileged_userns_clone = 1'.PHP_EOL
      .'user.max_user_namespaces = 15000'.PHP_EOL
      .'kernel.apparmor_restrict_unprivileged_userns = 0'.PHP_EOL;
    if ((string)@file_get_contents($persistPath) !== $persistContents) {
      $bytesWritten = @file_put_contents($persistPath, $persistContents);
      if ($bytesWritten === strlen($persistContents)) {
        @chmod($persistPath, 0644);
        logEntry('Wrote '.$persistPath.' so the sandbox settings survive a reboot.'); } } }
  if ($Verbose) logEntry('Sandbox kernel check completed across '.count($KernelFindings).' setting(s). Ready: '.($KernelIsReady ? 'YES' : 'NO').'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $sysctlPath, $sysctlValue, $persistPath, $persistContents, $bwrapBinary, $commandOutput, $commandExitCode, $bytesWritten, $profileIsLoaded, $profileLoadStatus, $mayRepair);
  return array($KernelIsReady, $KernelFindings); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to validate or repair the AppArmor profile bubblewrap needs.
// / Accepts a boolean permitting a write.
// / Returns a validity boolean & a status word, in that order.
// / This is not tied to one conversion pipeline. Every sandboxed conversion depends on it,
// / so it is checked from verifyInstallation rather than from a dependency check.
// / A kernel that does not restrict unprivileged user namespaces needs no profile at all.
function verifySandboxPolicy($mayRepair) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection, $RunningAsRoot;
  static $cachedValid = NULL;
  static $cachedStatus = 'unchecked';
  static $cachedMayRepair = FALSE;
  $PolicyIsValid = FALSE;
  $PolicyStatus = 'unchecked';
  $restrictionPath = '/proc/sys/kernel/apparmor_restrict_unprivileged_userns';
  $profilePath = '/etc/apparmor.d/hrconvert2-bwrap';
  $restrictionIsActive = $profileIsLoaded = FALSE;
  $profileLoadStatus = $distributionProfile = $localIncludePath = '';
  // / A cached result answers every later call but one. A failure found without permission to
  // / repair is checked again when a repair is permitted, because an Environment Manager pass
  // / checks before it repairs, & the cached check would answer the repair with the failure it
  // / was asked to fix.
  if ($cachedValid !== NULL && ($cachedValid or $cachedMayRepair or !$mayRepair)) {
    $PolicyIsValid = $cachedValid;
    $PolicyStatus = $cachedStatus; }
  else {
    if (file_exists($restrictionPath)) $restrictionIsActive = (trim((string)@file_get_contents($restrictionPath)) === '1');
    if (!$restrictionIsActive) {
      // / Nothing is blocking a namespace, so no profile is required.
      $PolicyIsValid = TRUE;
      $PolicyStatus = 'unrestricted';
      // / Nothing is logged here & that is deliberate.
      // / An unrestricted kernel is the state this sandbox needs rather than a finding.
      // / Reporting it announced normal operation on every run, before a session existed,
      // / as the first line of every logfile.
      // / The -v argument reports the sandbox state for an operator who wants it.
      $restrictionIsActive = FALSE; }
    // / A distribution profile for the same binary is not something to compete with.
    // / Ubuntu ships /etc/apparmor.d/bwrap granting userns, which is the whole reason the
    // / restriction is survivable there. Adding a second profile attached to the same
    // / executable leaves two attachments for one path, which AppArmor does not define an
    // / outcome for. Defer to theirs & remove ours if an earlier release installed one.
    else if (file_exists('/etc/apparmor.d/bwrap') && strpos((string)@file_get_contents('/etc/apparmor.d/bwrap'), '/usr/bin/bwrap') !== FALSE) {
      // / Extend the distribution profile through its own include rather than beside it.
      $distributionProfile = (string)@file_get_contents('/etc/apparmor.d/bwrap');
      $localIncludePath = '/etc/apparmor.d/local/bwrap';
      if (strpos($distributionProfile, 'local/bwrap') === FALSE) warningEntry('The distribution profile at /etc/apparmor.d/bwrap does not include <local/bwrap>, so HRConvert2 cannot extend it. Add the include by hand, or remove the profile & let -fp manage its own.');
      else {
        if (!is_dir('/etc/apparmor.d/local')) @mkdir('/etc/apparmor.d/local', 0755, TRUE);
        list ($PolicyIsValid, $PolicyStatus) = verifyPolicyFile('Sandbox AppArmor local', $localIncludePath, sandboxApparmorLocalContents(), $mayRepair);
        // / Reload THEIR profile, because that is the one the include belongs to.
        if ($PolicyStatus === 'installed' or $PolicyStatus === 'repaired') reloadApparmorProfile('/etc/apparmor.d/bwrap'); }
      $PolicyIsValid = TRUE;
      $PolicyStatus = 'distribution';
      if (file_exists($profilePath) && strpos((string)@file_get_contents($profilePath), 'HRCONVERT2-POLICY-MARKER') !== FALSE) {
        if (!$mayRepair or !$RunningAsRoot) warningEntry('This installation added '.$profilePath.' beside the distribution profile at /etc/apparmor.d/bwrap. Two profiles for one binary is undefined. Run -fp as root to remove ours.');
        else {
          // / UNLOAD BEFORE DELETING. Reloading AppArmor loads what is on disk & never
          // / unloads what is not. Deleting the file first leaves the profile resident in
          // / the kernel until a reboot, still attached to the binary, still competing
          // / with the distribution profile & invisible to anything that only lists files.
          unloadApparmorProfile($profilePath, 'hrconvert2-bwrap');
          @unlink($profilePath);
          if (!file_exists($profilePath)) {
            $PolicyStatus = 'removed, distribution profile kept';
            warningEntry('Removed '.$profilePath.'. The distribution already ships a profile for this binary. Two attachments for one path is undefined.'); }
          else warningEntry('Could not remove '.$profilePath.'. Delete it by hand, then run apparmor_parser -R against it.'); } }
      if ($Verbose) logEntry('Policy Check: Sandbox AppArmor, Status: '.$PolicyStatus.', the distribution profile at /etc/apparmor.d/bwrap governs this binary.'); }
    else if (!is_dir('/etc/apparmor.d')) {
      $PolicyStatus = 'absent';
      warningEntry('This kernel restricts unprivileged user namespaces & AppArmor is not installed, so bubblewrap cannot build a sandbox.'); }
    else {
      list ($PolicyIsValid, $PolicyStatus) = verifyPolicyFile('Sandbox AppArmor', $profilePath, sandboxApparmorContents(), $mayRepair);
      // / A matching file that is not loaded is enforcing nothing. Load it whatever the
      // / file status was, because on disk & in force are different questions.
      list ($profileIsLoaded, $profileLoadStatus) = apparmorProfileIsLoaded('hrconvert2-bwrap');
      if ($PolicyStatus === 'installed' or $PolicyStatus === 'repaired' or !$profileIsLoaded) {
        if (!$profileIsLoaded && $PolicyStatus === 'ok') warningEntry('The sandbox AppArmor profile matches this release but is '.$profileLoadStatus.'. Loading it now.');
        reloadApparmorProfile($profilePath);
        list ($profileIsLoaded, $profileLoadStatus) = apparmorProfileIsLoaded('hrconvert2-bwrap'); }
      // / A profile that will not load is reported as a failure rather than as ok.
      if (!$profileIsLoaded && $profileLoadStatus === 'NOT LOADED') {
        $PolicyIsValid = FALSE;
        $PolicyStatus = 'not loaded'; } }
    $cachedValid = $PolicyIsValid;
    $cachedStatus = $PolicyStatus;
    $cachedMayRepair = (bool)$mayRepair; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $restrictionPath, $profilePath, $restrictionIsActive, $profileIsLoaded, $profileLoadStatus, $distributionProfile, $localIncludePath, $mayRepair);
  return array($PolicyIsValid, $PolicyStatus); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to unload an AppArmor profile from the kernel.
// / Accepts the profile file path & the profile name it declares, in that order.
// / Returns TRUE when the profile is no longer loaded.
// / A profile is unloaded from its FILE, so this must run BEFORE the file is deleted.
// / Reloading AppArmor loads what is on disk & never unloads what is not, so deleting
// / first strands the profile in the kernel until a reboot, still attached to the binary.
// / aa-remove-unknown is the fallback for a profile whose file has already gone.
function unloadApparmorProfile($profilePath, $profileName) {
  // / Set variables.
  global $RunningAsRoot, $EnableMemoryProtection;
  $ProfileWasUnloaded = FALSE;
  $parserBinary = $removeBinary = '';
  $commandOutput = array();
  $commandExitCode = 1;
  $profileIsLoaded = FALSE;
  $profileLoadStatus = '';
  $parserBinary = locateDependency('apparmor_parser');
  if (!$RunningAsRoot) warningEntry('An AppArmor profile could not be unloaded, because unloading one requires root.');
  else if ($parserBinary === '') warningEntry('An AppArmor profile could not be unloaded, because apparmor_parser is not installed.');
  else {
    // / -R removes the profile the named file declares.
    if (file_exists($profilePath)) exec(escapeshellarg($parserBinary).' -R '.escapeshellarg($profilePath).' 2>&1', $commandOutput, $commandExitCode);
    list ($profileIsLoaded, $profileLoadStatus) = apparmorProfileIsLoaded($profileName);
    // / The file was already gone, so the parser had nothing to read. Sweep the orphan.
    if ($profileIsLoaded) {
      $removeBinary = locateDependency('aa-remove-unknown');
      if ($removeBinary !== '') {
        $commandOutput = array();
        exec(escapeshellarg($removeBinary).' 2>&1', $commandOutput, $commandExitCode);
        list ($profileIsLoaded, $profileLoadStatus) = apparmorProfileIsLoaded($profileName); } }
    if (!$profileIsLoaded) {
      $ProfileWasUnloaded = TRUE;
      logEntry('The AppArmor profile '.$profileName.' was unloaded.'); }
    else warningEntry('The AppArmor profile '.$profileName.' is still loaded & its file is gone. Run aa-remove-unknown as root, or reboot.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $parserBinary, $removeBinary, $commandOutput, $commandExitCode, $profileIsLoaded, $profileLoadStatus, $profilePath, $profileName);
  return $ProfileWasUnloaded; }
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
  global $KernelVersion, $ApacheUser, $RequiredKernelVersion, $RequiredEngineVersion, $SecretFile, $ManagerSocketDir, $InstLoc, $HostedLoc, $ApplicationLoc, $ServerRootDir, $HRConvertVersion, $ConfigVersion, $RequiredConfigVersion, $RequiredGuiVersion, $RequiredLanguageVersion, $RequiredSetupCoreVersion, $RequiredDependencyCoreVersion, $RequiredDependsVersion, $RequiredPipelineCoreVersion, $PipelineCoreActive, $PipelineCount, $RequiredSecretVersion, $RequiredConfigScript, $ApplicationName, $SupportedConversionTypes, $SupportedGuis, $SupportedLanguages, $DirSep, $Lol, $UsePyMeshLab, $AllowBootableIsoImage, $RequireSandbox, $RequireSandboxOnDocker, $RunningInContainer, $RunningAsRoot, $CurrentUser, $EnableMemoryProtection, $EnableResourceAwareness, $RequireResourceAwareness, $ResourceAwarenessActive, $CoreManagerVersion, $TotalResourceBudget, $ReserveResourcePercentage, $MaxConcurrentWorkers, $MaxExpectedRuntime, $CoreManagerSubprocessPollInterval, $ResourcePollInterval, $WorkerReapInterval, $WorkerStaleGracePeriod, $ConvertTemp, $MaintainHTAccess, $EngineVersion, $HostArchitecture, $HostMachineString, $AllowUserURLDownload, $AllowUserVirusScan, $VirusScan, $AllowUserShare, $EnvironmentManagerMayRepair, $EnablePerConversionLimits, $Pipelines;
  $VersionInfoDisplayed = $modelsAreValid = $ocrToolsAreValid = $archiveToolsAreValid = $libreOfficeIsValid = FALSE;
  $ffmpegBinary = $streamFfmpegBinary = $inkscapeBinary = $diaBinary = $scadBinary = $imageBinary = $ebookBinary = FALSE;
  $assimpBinary = $meshlabBinary = $tesseractBinary = $pdftotextBinary = FALSE;
  $sevenZipBinary = $rarBinary = $zipBinary = $tarBinary = $mkisofsBinary = $isoHybridBinary = FALSE;
  $listenerIsRunning = $bwrapBinary = FALSE;
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
  $setupIsAvailable = FALSE;
  $subsystemIsReady = $scriptIsValid = FALSE;
  $failureCount = 0;
  $maintainHtaccess = $apacheConfigIsInstalled = $dataIsProtected = FALSE;
  $exposureStatus = $exposureDetail = $secretMode = $socketMode = $manifestVersion = $pipelineKind = $secretExpected = '';
  $secretOwner = -1;
  $pipelineKindCounts = $pipelineKindLabels = $pipelineRecord = $disabledFeatures = $disabledFeature = array();
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
  // / The sandbox probe lives in the Engine. With no Engine there is nothing to probe with,
  // / & not functional is the honest answer rather than a guess.
  $bwrapBinary = (function_exists('verifyBwrap') ? verifyBwrap() : FALSE);
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
    'Pipeline Core' => array(readComponentVersion('Engine'.$DirSep.'Cores'.$DirSep.'pipelineCore.php', 'PipelineCoreVersion'), $RequiredPipelineCoreVersion));
  foreach ($componentChecks as $componentName => $componentPair) {
    if (ltrim((string)$componentPair[0], 'vV') === ltrim((string)$componentPair[1], 'vV')) print('  '.str_pad($componentName, 28).'OK, '.ltrim((string)$componentPair[0], 'vV').$Lol);
    else {
      $failureCount++;
      print('  '.str_pad($componentName, 28).'FAILED, reports '.($componentPair[0] === '' ? 'no version' : ltrim((string)$componentPair[0], 'vV')).', requires '.$componentPair[1].$Lol); } }
  // / The bootstrap script is a component too, pinned exactly, though it is bash & the Engine
  // / judges it rather than reading it like a core. A script that needs attention is a failure.
  // / One disabled because it does not match is the safe state & is not. It was not reported
  // / here at all until v4.0.1.
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
    'Calibre, e-books' => array($ebookBinary !== FALSE, toolRequirement('Calibre'), TRUE));
  foreach ($dependencyChecks as $dependencyName => $dependencyState) {
    if ($dependencyState[0]) print('  '.str_pad($dependencyName, 28).'OK'.($dependencyState[2] ? '' : ', optional').$Lol);
    else {
      $failureCount++;
      print('  '.str_pad($dependencyName, 28).'FAILED, requires '.$dependencyState[1].' or later'.($dependencyState[2] ? '' : ', optional').$Lol); } }
  print('  '.str_pad(($RunningInContainer ? 'Sandbox, Docker' : 'Sandbox, Bubblewrap'), 28).($bwrapBinary === FALSE ? 'FAILED, not functional' : 'OK').$Lol);
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
  if (!$AllowUserVirusScan) $disabledFeatures[] = array('User virus scanning', 'Allow User Virus Scan');
  if (!$VirusScan) $disabledFeatures[] = array('Virus scanning', 'Virus Scan');
  if (!$AllowUserShare) $disabledFeatures[] = array('Share a file', 'Allow User Share');
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

  // / The two paths the manager security model rests on.
  // / The install secret is root's & 0640, so the web server account's group reads it, nothing
  // / else can, & only root can change it. It was the web server account's & 0600 until
  // / v4.0.1, which let that account rewrite a file root required. A standard user's own
  // / secret is that user's & 0600. The socket directory is the web server account's & 0700.
  // / A startup key is only worth anything because those hold. Nothing checked them at
  // / startup, so an installation that never had -fp run as root, or one whose permissions
  // / were widened by a restore, lost the property silently.
  $secretExpected = ($RunningAsRoot or (string)$CurrentUser === (string)$ApacheUser) ? '0640' : '0600';
  if (isset($SecretFile) && $SecretFile !== '' && file_exists($SecretFile)) {
    $secretMode = substr(sprintf('%o', fileperms($SecretFile)), -4);
    $secretOwner = (int)fileowner($SecretFile); }
  if (isset($ManagerSocketDir) && $ManagerSocketDir !== '' && is_dir($ManagerSocketDir)) $socketMode = substr(sprintf('%o', fileperms($ManagerSocketDir)), -4);

  // / The environment every conversion runs in.
  print($Lol.'Environment'.$Lol);
  if ($secretMode === '') print('  '.str_pad('Install secret', 28).'NOT PRESENT, it is created by the first command run as root'.$Lol);
  else if ($secretMode !== $secretExpected or ($secretExpected === '0640' && $secretOwner !== 0)) {
    $failureCount++;
    print('  '.str_pad('Install secret', 28).'FAILED, mode '.$secretMode.($secretOwner === 0 ? ', root' : ', not root').', expected '.$secretExpected.($secretExpected === '0640' ? ' & root' : '').'. Run -fp as root'.$Lol); }
  else print('  '.str_pad('Install secret', 28).'OK, '.$secretExpected.($secretExpected === '0640' ? ', root' : '').$Lol);
  if ($socketMode === '') print('  '.str_pad('Manager socket directory', 28).'NOT PRESENT, it is created with the listener'.$Lol);
  else if ($socketMode !== '0700') {
    $failureCount++;
    print('  '.str_pad('Manager socket directory', 28).'FAILED, mode '.$socketMode.', expected 0700. Run -fp as root'.$Lol); }
  else print('  '.str_pad('Manager socket directory', 28).'OK, 0700'.$Lol);
  // / Where the application runs from & what the web server serves. Since v4.0.1 the application
  // / is installed outside the web root & the hosted location holds its loader.
  if ((string)@realpath($InstLoc) === (string)@realpath($ApplicationLoc)) print('  '.str_pad('Installation', 28).'OK, '.$InstLoc.$Lol);
  else {
    $failureCount++;
    print('  '.str_pad('Installation', 28).'NOT MOVED, runs from '.$InstLoc.', which the web server serves. Run -fp as root'.$Lol); }
  if ((string)@realpath($InstLoc) === (string)@realpath($ApplicationLoc)) {
    list ($setupIsAvailable, $setupVersion) = verifyCoreComponent('Setup Core', 'Engine'.$DirSep.'Cores'.$DirSep.'setupCore.php', 'SetupCoreVersion', $RequiredSetupCoreVersion);
    if ($setupIsAvailable && function_exists('publishHostedLocation')) list ($hostedStatus, $hostedDetail, $hostedWritten, $hostedRemoved) = publishHostedLocation($HostedLoc, $InstLoc, FALSE);
    else {
      $hostedStatus = 'UNKNOWN';
      $hostedDetail = 'The Setup Core is unavailable, so it could not be checked'; }
    if ($hostedStatus !== 'ok') $failureCount++;
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
  list ($dataIsProtected, $exposureStatus, $exposureDetail) = verifyDataExposure();
  print($Lol.'Data directory'.$Lol);
  print('  '.str_pad('Location', 28).$ConvertTemp.$Lol);
  // / The data location itself is never served. Inside the document root its sessions, caches &
  // / logs could be fetched, so that is a failure. It was not checked here until v4.0.1.
  $misplacedRoots = misplacedDataRoots();
  if (count($misplacedRoots) > 0) $failureCount++;
  print('  '.str_pad('Data location', 28).(count($misplacedRoots) === 0 ? 'OK, outside the document root' : 'MISPLACED, '.implode(' & ', $misplacedRoots).' inside '.$ServerRootDir.'. Move '.(count($misplacedRoots) === 1 ? 'it' : 'them').' outside the document root').$Lol);
  print('  '.str_pad('Maintain .htaccess', 28).($maintainHtaccess ? 'YES' : 'NO, disabled in config.php').$Lol);
  print('  '.str_pad('Apache configuration', 28).($apacheConfigIsInstalled ? 'INSTALLED' : 'not installed, run -fp as root').$Lol);
  print('  '.str_pad('Live exposure', 28).strtoupper($exposureStatus).', '.$exposureDetail.$Lol);

  // / Resource awareness & the listener it depends on.
  print($Lol.'Resource awareness'.$Lol);
  if (!$EnableResourceAwareness) print('  '.str_pad('Configured', 28).'DISABLED in config.php'.$Lol);
  else if (!$ResourceAwarenessActive) print('  '.str_pad('Component', 28).'DISABLED, the Engine is unavailable'.$Lol);
  else {
    // / The listener lives in the Engine & reporting on it needs the Engine to ask. Root does not
    // / read the state the web server account writes, so root does not ask & names --status, which
    // / asks that account. Asking anyway read nothing & reported a running listener as STOPPED.
    if ($RunningAsRoot or !function_exists('reportListenerStatus')) { $listenerIsRunning = FALSE; $listenerStatus = array(); }
    else list ($listenerIsRunning, $listenerStatus) = reportListenerStatus();
    print('  '.str_pad('Component', 28).'OK, supplied by Engine '.ltrim((string)$EngineVersion, 'vV').$Lol);
    print('  '.str_pad('Enforced', 28).($RequireResourceAwareness ? 'YES' : 'NO').$Lol);
    print('  '.str_pad('Total budget', 28).((int)$TotalResourceBudget < 1 ? 'AUTO, from the processor count' : (int)$TotalResourceBudget.' cost units').$Lol);
    print('  '.str_pad('Reserved share', 28).(int)$ReserveResourcePercentage.'%'.$Lol);
    print('  '.str_pad('Concurrent worker limit', 28).((int)$MaxConcurrentWorkers < 1 ? 'NONE, the budget decides' : (int)$MaxConcurrentWorkers).$Lol);
    print('  '.str_pad('Maximum runtime', 28).(int)$MaxExpectedRuntime.'s'.$Lol);
    if ($RunningAsRoot) print('  '.str_pad('Listener', 28).'NOT ASKED, root does not read what '.$ApacheUser.' writes. --status asks '.$ApacheUser.'.'.$Lol);
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
  // / The updater in every release from v3.9.9 to v4.0.0 accepts a new installation only when
  // / its -v prints the words Core version, & nothing had printed them since. This line is what
  // / lets an installation of those releases update to this one with -u.
  print($Lol.'Core version '.$HRConvertVersion.' reported the above.'.$Lol);
  print($Lol);
  $VersionInfoDisplayed = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $pipelineKindCounts, $pipelineKindLabels, $pipelineKind, $pipelineKindCount, $pipelineRecord, $manifestVersion, $disabledFeatures, $disabledFeature, $misplacedRoots, $hostedStatus, $hostedDetail, $setupVersion, $hostedWritten, $hostedRemoved, $setupIsAvailable, $scriptIsValid, $scriptStatus, $scriptDisplayStatus, $scriptDetail, $modelsAreValid, $ocrToolsAreValid, $archiveToolsAreValid, $libreOfficeIsValid, $ffmpegBinary, $streamFfmpegBinary, $inkscapeBinary, $diaBinary, $scadBinary, $imageBinary, $assimpBinary, $meshlabBinary, $tesseractBinary, $pdftotextBinary, $sevenZipBinary, $rarBinary, $zipBinary, $tarBinary, $mkisofsBinary, $isoHybridBinary, $bwrapBinary, $installedGui, $installedLang, $installedEndonym, $checkDir, $checkFile, $foundVersion, $langLine, $guiMatches, $langMatches, $langOk, $langTotal, $ebookBinary, $listenerStatus, $listenerIsRunning, $secretMode, $secretOwner, $secretExpected, $socketMode, $componentChecks, $componentPair, $componentName, $dependencyChecks, $dependencyState, $dependencyName, $subsystemChecks, $subsystemIsReady, $subsystemName, $failureCount, $maintainHtaccess, $apacheConfigIsInstalled, $dataIsProtected, $exposureStatus, $exposureDetail);
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
  print('  --build --pins              Check every version pin against what ships.'.$Lol);
  print('  --build --pins --write      Rewrite every pin that differs. A developer step.'.$Lol);
  print($Lol);
  print('Listener targets'.$Lol);
  print('  -k                          Stop the listener & every manager it started.'.$Lol);
  print('  -k <worker-id>              End one worker by budget token or process id.'.$Lol);
  print('  --kill-all-workers          End EVERY tracked conversion in progress.'.$Lol);
  print('                              Every user mid conversion loses their files.'.$Lol);
  print('  --kill-every-worker         End EVERY PHP process owned by the web server user.'.$Lol);
  print('                              This reaches other applications on this host, such'.$Lol);
  print('                              as WordPress or OwnCloud. They lose every session.'.$Lol);
  print('  -y, --yes                   Skip the confirmation prompt on the two above.'.$Lol);
  print($Lol);
  print('Listener notes'.$Lol);
  print('  Listener commands require root or the web server user.'.$Lol);
  print('  A standard user holds a per user secret, which cannot derive a valid startup'.$Lol);
  print('  key, so a listener started that way could never be reached by a worker.'.$Lol);
  print('  Resource awareness must be enabled in config.php before -l will do anything.'.$Lol);
  print('  With no listener running the core converts exactly as it did before, unchecked.'.$Lol);
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
  print('                              Edge is not a release. It carries whatever version'.$Lol);
  print('                              stamp master holds & may match no release at all.'.$Lol);
  print('  -u=v#.#.#                   Install exactly that tagged release, such as v3.6.8.'.$Lol);
  print('                              Fails if it does not exist. A partial version such'.$Lol);
  print('                              as v3.6 is refused.'.$Lol);
  print($Lol);
  print('Notes'.$Lol);
  print('  Command line & web requests are mutually exclusive.'.$Lol);
  print('  An argument supplied on the command line prevents the web interface entirely.'.$Lol);
  print('  No session is created & no user data is touched by a command line invocation.'.$Lol);
  print('  Updates must be enabled in config.php before -u will do anything.'.$Lol);
  print('  The previous installation is preserved & is restored automatically if the new'.$Lol);
  print('  one cannot report its own version.'.$Lol);
  print($Lol);
  print('Documentation'.$Lol);
  print('  Documentation/INSTALLATION_INSTRUCTIONS.txt  Installing & configuring a server.'.$Lol);
  print('  Documentation/ERROR_DESCRIPTIONS.txt         Every numbered error, its cause & its fix.'.$Lol);
  print('  Documentation/CREATING_GUIS.txt              Building & installing an interface.'.$Lol);
  print('  Documentation/CREATING_LANGUAGE_PACKS.txt    Translating the interface.'.$Lol);
  print('  Documentation/CODING_CONVENTIONS.txt         Conventions this codebase follows.'.$Lol);
  print('  Documentation/DOCKER_BUILD_INSTRUCTIONS.txt  Building the container image.'.$Lol);
  print('  Documentation/CHANGELOG.txt                  What changed & when.'.$Lol);
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
// / The status follows the common command line convention, so a script, cron or systemd can
// / act on it. 0 is success. 1 is a command that failed or was refused. 2 is a command used
// / wrongly, an argument this application does not recognize. A report such as -v exits 0
// / when the report was produced, because validateInstallation runs -v & requires 0, & an
// / optional missing dependency must not fail an update.
// / Success means the requested state holds afterwards, rather than that work was done.
// / Stopping a listener that was not running is success, as it is for systemctl stop.
// / This application recognizes its own arguments here, which are -v, -h, -u & anything
// / beginning --app. Every other command belongs to the Engine & is recognized by
// / dispatchEngineCommand, which this asks before deciding an argument is a mistake. Until
// / v3.9.9 all of them were branches here.
// / A handled command line ends the request. An unhandled one falls through to the web
// / interface, which is what a request with no arguments is.
function parseCommandLine() {
  // / Set variables.
  global $EngineActive, $Lol, $RunningFromCLI, $RunningAsRoot, $CurrentUser, $EnableMemoryProtection, $EngineApplicationArgumentHandler;
  $CommandLineHandled = FALSE;
  $CommandLineStatus = 0;
  $UserType = 'web';
  $cliArgumentCount = 0;
  $cliArguments = $appArgumentParts = array();
  $cliCommand = $cliTarget = $cliSecondTarget = $cliWhoami = $applicationHandler = $appTarget = $appSecondTarget = '';
  $cliConfirmed = $cliVectorParsed = $engineCommandHandled = FALSE;
  $engineCommandStatus = 0;
  // / A web request has no command line & must return immediately.
  // / This is the ONLY path that returns FALSE. Every other path handles & stops.
  if (!$RunningFromCLI) $CommandLineHandled = FALSE;
  else {
    // / The vector is read into its parts by parseArgumentVector, which is KERNEL rather than
    // / Engine. It is called directly & unguarded, because the kernel is loaded by definition
    // / whenever this file is running. An earlier version of this release put the parser in the
    // / Engine & kept a duplicate here for when the Engine was missing. The duplicate was the
    // / evidence that the parser had been put in the wrong place.
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
      print($Lol.'This command needs the Engine & the Engine could not be loaded.'.$Lol);
      print('Run  php convertCore.php -v  to see which component failed & what version it reports.'.$Lol);
      // / The remedy is named because there is no command that applies it. Every repair path
      // / reaches into the Engine as well, so an operator who goes looking for one is hunting
      // / something that does not exist. Measured rather than assumed: with a mismatched Engine,
      // / --config --repair, --setup --install-complete & --update all fail on an Engine call of
      // / their own. Telling them to run -v & stopping there sent them in a circle.
      print('An Engine reporting the wrong version is a partial update. Replace'.$Lol);
      print('Resources/Engine/engine.php with the copy from the release this core came from.'.$Lol);
      print('No command can do this for you, because every repair path needs the Engine too.'.$Lol.$Lol);
      $CommandLineHandled = TRUE;
      $UserType = 'cli'; }
    else {
      // / The command & both targets were resolved by the parser above. Nothing is re-read
      // / from the vector here, because two places deciding what the first argument means is
      // / how they come to disagree.
      // / THE APPLICATION'S NAMESPACE IS CHECKED FIRST, before any engine argument.
      // / Everything beginning --app belongs to the application. Checking it first means
      // / the engine never has to know what the application accepts, & an application
      // / argument can never collide with an engine one however many are added later.
      // / It is checked HERE, ahead of the Engine's dispatcher, so that no command the
      // / Engine adds later can claim it. A branch for it once sat at the end of a chain
      // / that an earlier chain had already answered, & it linted, ran & never fired.
      // / A PREFIX RATHER THAN A REGISTRY OF NAMES. A registry means asking the
      // / application what it accepts before an unknown argument can be reported, & an
      // / application that answers badly makes the core report nonsense.
      // / THE HANDLER IS NAMED BY $EngineApplicationArgumentHandler, which the Engine
      // / configuration & the contract both set. Until v3.9.9 this read a global named
      // / $ApplicationArgumentHandler that nothing sets, so a declared handler was never
      // / called & every --app argument was refused.
      // / ITS VALUES ARE HANDED OVER AS TYPED. parseArgumentVector lowercases every target,
      // / which is safe for this application's commands & not for an application's own,
      // / which may be paths. They are read again from the raw vector. The command stays
      // / lowercased, because it is a name & is matched the way every command is.
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
      // / THIS IS AN else if, SO AN --app ARGUMENT ENDS THE CHAIN. It was a separate if until
      // / v3.9.9, so an --app argument reached the unrecognized argument branch at the bottom
      // / as well, printed the whole help text under its own answer & exited 2.
      else if ($cliCommand === '-v' or $cliCommand === '--version') {
        logEntry('Command line invocation. Displaying version information.');
        showVersionInfo();
        $CommandLineHandled = TRUE; }
      // / Handle the -h or --help arguments.
      else if ($cliCommand === '-h' or $cliCommand === '--help') {
        logEntry('Command line invocation. Displaying help.');
        showHelpInfo();
        $CommandLineHandled = TRUE; }
      // / Every other command is offered to the Engine first. The listener, the managers,
      // / setup, configuration, the pipelines, repair & cleaning are the Engine's as of v3.9.9,
      // / & every application of this shape needs them. Before that each was a branch here.
      // / What comes back unhandled is this application's own, which is -u, or a mistake.
      // / The gate above already refused all of this when there is no Engine to ask.
      else {
        list ($engineCommandHandled, $engineCommandStatus) = dispatchEngineCommand($cliArguments, $cliCommand, $cliTarget, $cliSecondTarget, $cliConfirmed);
        if ($engineCommandHandled) $CommandLineStatus = (int)$engineCommandStatus;
        // / Self update stays here, because updateApplication & its helpers are this
        // / application's & moving them is a migration of its own. It is root only.
        else if (($cliCommand === '-u' or $cliCommand === '--update') && !$RunningAsRoot) $CommandLineStatus = refuseCommandForUser($cliCommand, $cliWhoami, 'A root only command was refused for an unauthorized user.');
        else if ($cliCommand === '-u' or $cliCommand === '--update') {
          logEntry('Command line invocation. Performing an application update.');
          if (!updateApplication(strtolower($cliTarget))) $CommandLineStatus = 1; }
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
  purgeSensitiveMemory($EnableMemoryProtection, $cliArgumentCount, $cliArguments, $appArgumentParts, $cliCommand, $cliTarget, $cliSecondTarget, $cliWhoami, $applicationHandler, $appTarget, $appSecondTarget, $cliConfirmed, $cliVectorParsed, $engineCommandHandled, $engineCommandStatus);
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
// / A function to remove the build & development environments when config.php asks for it.
// / Accepts nothing & is run by root from the -fp argument.
// / Returns a success boolean, then whether the build environment & the development
// / documentation are gone, in that order. Gone means gone, whoever removed them.
// / These paths live under the application directory, not part of the regular cleanup routine.
// /
// / UNTIL v4.0.1 EVERY WEB REQUEST RAN THIS AS THE WEB SERVER ACCOUNT. That only worked while
// / the account could write to the installation, & it no longer can, because the code is
// / root's. Root removes by name here, so every name goes through removeFileWithoutFollowing()
// / & the Build folder is not entered until root trusts it. Before -fp first gives the code to
// / root, the web server account can put a link to /etc where the Build folder belongs.
function cleanBuildEnvironment() {
  // / Set variables.
  global $Verbose, $DeleteBuildEnvironment, $DeleteDevelopmentDocumentation, $DirSep, $EnableMemoryProtection, $RunningAsRoot;
  $BuildEnvCleaned = TRUE;
  $BuildEnvDeleted = $DevDocsDeleted = $buildDirIsTrusted = $entryWasRemoved = FALSE;
  $removalTargets = array();
  $buildDirContents = FALSE;
  $buildDirEntry = $removalTarget = $removalNotice = $resolvedBuildDir = $trustNotice = '';
  // / The installation this file belongs to. PHP resolves every link in __FILE__, so none of
  // / the names below is reached through one.
  $installDir = dirname(__FILE__);
  $dockerFile = $installDir.$DirSep.'Documentation'.$DirSep.'Build'.$DirSep.'Dockerfile';
  $changelogFile = $installDir.$DirSep.'Documentation'.$DirSep.'CHANGELOG.txt';
  $readmeFile = $installDir.$DirSep.'README.md';
  // / The Build folder in the installation directory, as config.php describes it. This read
  // / dirname(__DIR__) until v4.0.0, which is the folder ABOVE the installation, so it never
  // / found its own Build folder & would have emptied a Build folder belonging to a neighbour.
  $buildDir = $installDir.$DirSep.'Build';
  clearstatcache();
  $buildDirStatus = @lstat($buildDir);
  if (!$RunningAsRoot && ($DeleteBuildEnvironment or $DeleteDevelopmentDocumentation)) {
    $BuildEnvCleaned = FALSE;
    warningEntry('The build environment was not removed. Only root removes it, from the -fp argument.'); }
  // / Delete the build environment if specified by config.php.
  else if ($DeleteBuildEnvironment) {
    if ($Verbose) logEntry('Removing the build environment.');
    $removalTargets = array($dockerFile);
    // / A Build that is not a folder, a link included, is one name & is removed as one.
    if (is_array($buildDirStatus) && ((int)$buildDirStatus['mode'] & 0170000) !== 0040000) $removalTargets[] = $buildDir;
    // / A Build folder is emptied one name at a time, & only once root trusts it.
    else if (is_array($buildDirStatus)) {
      list ($buildDirIsTrusted, $resolvedBuildDir, $trustNotice) = pathIsTrustedByRoot($buildDir);
      if ($buildDirIsTrusted && $resolvedBuildDir === $buildDir) $buildDirContents = @scandir($buildDir);
      else warningEntry('The Build folder was left as it is, because root does not trust it. '.($trustNotice !== '' ? $trustNotice : $buildDir.' leads to '.$resolvedBuildDir.'.'));
      if (is_array($buildDirContents)) foreach (array_diff($buildDirContents, array('.', '..')) as $buildDirEntry) $removalTargets[] = $buildDir.$DirSep.$buildDirEntry; }
    foreach ($removalTargets as $removalTarget) {
      list ($entryWasRemoved, $removalNotice) = removeFileWithoutFollowing($removalTarget);
      if (!$entryWasRemoved) warningEntry($removalNotice); }
    // / A folder left inside Build keeps it in place, which is the documented behaviour.
    if (is_array($buildDirContents)) @rmdir($buildDir);
    // / Confirm the build environment is actually gone before reporting success.
    clearstatcache();
    if (@lstat($dockerFile) === FALSE && @lstat($buildDir) === FALSE) $BuildEnvDeleted = TRUE;
    else {
      $BuildEnvCleaned = FALSE;
      errorEntry('Could not remove the build environment!', 26000, FALSE); }
    if ($Verbose && $BuildEnvDeleted) logEntry('Removed the build environment.'); }
  // / Delete the development documentation if specified by config.php.
  if ($RunningAsRoot && $DeleteDevelopmentDocumentation) {
    if ($Verbose) logEntry('Removing the development documentation.');
    $removalTargets = array($changelogFile, $readmeFile);
    foreach ($removalTargets as $removalTarget) {
      list ($entryWasRemoved, $removalNotice) = removeFileWithoutFollowing($removalTarget);
      if (!$entryWasRemoved) warningEntry($removalNotice); }
    // / Confirm the development documentation is actually gone before reporting success.
    clearstatcache();
    if (@lstat($changelogFile) === FALSE && @lstat($readmeFile) === FALSE) $DevDocsDeleted = TRUE;
    else {
      $BuildEnvCleaned = FALSE;
      errorEntry('Could not remove the development documentation!', 26001, FALSE); }
    if ($Verbose && $DevDocsDeleted) logEntry('Removed the development documentation.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $installDir, $dockerFile, $changelogFile, $readmeFile, $buildDir, $buildDirStatus, $buildDirContents, $buildDirEntry, $buildDirIsTrusted, $resolvedBuildDir, $trustNotice, $removalTargets, $removalTarget, $removalNotice, $entryWasRemoved);
  return array($BuildEnvCleaned, $BuildEnvDeleted, $DevDocsDeleted); }
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
  // / Listing is the COMPONENT & filtering is the POLICY.
  // / The Files pipeline reads the directory & returns plain names. Which of those names
  // / this application is willing to show is $DangerousFiles, which no other application
  // / has & an engine cannot guess.
  // / The pipeline omits directories & the dot entries, so nothing here has to.
  // / A pipeline's code is loaded ON DEMAND & is not loaded by enumeration.
  // / Enumeration reads pipelineConfig.php, which declares what a pipeline IS. The functions
  // / live in pipeline.php & are required by loadPipelineCore, which until now only ran when
  // / a conversion dispatched.
  // / Checking function_exists without loading first is therefore always FALSE on a page
  // / render, which is exactly what happened: an upload landed & the file list came back
  // / empty because the pipeline that lists it had never been loaded.
  // / loadPipelineCore is idempotent, it requires once, so asking every time costs nothing.
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

    // / Both major versions are bound, because a host may carry either & the policy file
    // / this application writes lives beside whichever one is installed.
    'imagemagick' => array(
      'OperationType' => 'Image',
      'ReadOnlyPaths' => array('/etc/ImageMagick-7', '/etc/ImageMagick-6', '/usr/share/ImageMagick-7', '/usr/share/ImageMagick-6'),
      'Reason' => 'The policy file & the delegate list are read from these directories.'),

    // / Language data lives in one of two places depending on how it was installed.
    'tesseract' => array(
      'OperationType' => 'OCR',
      'ReadOnlyPaths' => array('/usr/share/tesseract-ocr', '/usr/share/tessdata'),
      'Reason' => 'Recognition fails with no language data & the failure names no cause.'),

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

    // / clamscan cannot start without its signature database, & ClamAV 1.5 & later also
    // / refuses without the certificates it verifies that database against. Both are read
    // / only & both are where every Debian & Ubuntu package puts them.
    // / Neither was here until v3.9.9, & nothing noticed, because the ClamAV pipeline had
    // / never managed to run its command in the sandbox at all.
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
// / MOVED OUT OF THE ENGINE AT v3.9.2 & renamed. It was 1272 lines of THIS application
// / written as data, sitting inside a component meant to serve any application.
// / The Engine reads it because engineConfig.php names it in $EngineConfigModelProvider.
// / A function to hold this utility's understanding of config.php.
// / Accepts no arguments.
// / Returns a multidimensional array of sections, each holding its variables.
// /
// / THIS ARRAY IS THE ONLY BLOCK THAT CHANGES WHEN config.php CHANGES.
// / Update it whenever $ConfigVersion is bumped. Update it WITHOUT FAIL whenever
// / $RequiredConfigVersion is bumped. Forgetting does not break this utility. A variable
// / it does not know about is reported as unaccounted, can still be edited by hand, &
// / simply cannot be reset or repaired, because nothing here knows what it should be.
// /
// / THE SECTION NAMES MUST MATCH config.php EXACTLY, INCLUDING ANY TYPO.
// / A section header in config.php is written as three dashes either side of the name.
// / Security Informations is spelled that way in the file & is spelled that way here.
// / Correcting it here without correcting the file would orphan forty one settings.
// /
// / Every default below was read from the configuration this release ships, so a reset
// / restores exactly what a fresh installation would have had.
// /
// / Each variable carries the following.
// /   Type          bool, int, string, array, path or version.
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
    // / section. It is modelled here as read only. config.php says not to change it by
    // / hand & replacing the file with a newer one is the only correct way to move it.
    'Unsectioned' => array('Writable' => FALSE, 'Variables' => array(
      'ConfigVersion' => array('Type' => 'version', 'Depends' => '', 'Description' => 'Config Version. Read only. Replaced by an update.'))),
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
      'AdditionalConvertLocs' => array('Type' => 'array', 'Default' => 'array( array(\'/DATA2/HRConvert2\', \'roundrobin\'), array(\'/DATA3/HRConvert2\', \'leastactive\'), array(\'/DATA4/HRConvert2\', \'redundant\'))', 'Depends' => '', 'Description' => 'Additional Data Locations'),
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
      // / The Environment Manager settings. Both belong to the model as well as to
      // / config.php, because a repair can only add a setting this utility knows about.
      // / They were added to config.php alone at first, & --config --repair then reported
      // / that it would add nothing while the file was demonstrably short of them.
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
      'DefaultExpectedRuntime' => array('Type' => 'int', 'Depends' => '', 'Description' => 'Default Expected Runtime'))),
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
      'UserEbookOutputArray' => array('Type' => 'array', 'Default' => 'array(\'epub\', \'mobi\', \'azw3\', \'fb2\', \'lit\', \'lrf\', \'pdb\', \'pml\', \'rb\', \'snb\', \'tcr\', \'txt\', \'txtz\', \'rtf\', \'oeb\', \'docx\', \'pdf\')', 'Depends' => '', 'Description' => 'Supported Ebook Output Formats'))));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection);
  return $ConfigModel; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report whether this application's data tree is exposed by a web server.
// / Accepts nothing. Returns display rows.
// /
// / The Engine calls this because engineConfig.php names it in $EngineDataPolicyProvider,
// / & not because the Engine knows anything about web servers.
// / It USED to be four calls made directly from Setup Core, which meant Setup Core knew
// / this application serves files over http, that Apache reads a protection file only where
// / AllowOverride is enabled, that nginx never reads one, & that an uploaded SVG runs script
// / in its own origin. None of that is true of an engine.
// /
// / A row with a Label is a status line. A row with only Text is a paragraph. The Engine
// / prints both without understanding either.
function applicationDataPolicyFindings() {
  // / Set variables.
  global $EnableMemoryProtection;
  $PolicyFindings = array();
  $dataPolicyIsValid = $dataIsProtected = FALSE;
  $dataPolicyStatus = $exposureStatus = $exposureDetail = '';
  list ($dataPolicyIsValid, $dataPolicyStatus) = verifyDataProtectionPolicy(TRUE);
  $PolicyFindings[] = array('Label' => 'Protection file', 'Status' => policyDisplayStatus($dataPolicyStatus), 'Detail' => describePolicyStatus('DATA Directory', $dataPolicyStatus));
  list ($dataIsProtected, $exposureStatus, $exposureDetail) = verifyDataExposure();
  $PolicyFindings[] = array('Label' => 'Live exposure', 'Status' => strtoupper($exposureStatus), 'Detail' => $exposureDetail);
  if ($exposureStatus === 'exposed') {
    $PolicyFindings[] = array('Text' => '');
    $PolicyFindings[] = array('Text' => '  THE DATA DIRECTORY IS EXPOSED. A file a user uploads is served back as a');
    $PolicyFindings[] = array('Text' => '  document rather than as a download, so an uploaded SVG runs its own script');
    $PolicyFindings[] = array('Text' => '  in this origin. The protection file is being ignored; Apache reads one only');
    $PolicyFindings[] = array('Text' => '  where AllowOverride is enabled & nginx never reads one at all. Put the rules');
    $PolicyFindings[] = array('Text' => '  in the server configuration. See Documentation/ABOUT_DATA_DIRECTORY_PROTECTION.txt.'); }
  else if ($exposureStatus !== 'protected') {
    $PolicyFindings[] = array('Text' => '');
    $PolicyFindings[] = array('Text' => '  Exposure was NOT established, which is not a pass. If the web server is not');
    $PolicyFindings[] = array('Text' => '  running yet this is expected; re-run the -fp argument once it is.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dataPolicyIsValid, $dataIsProtected, $dataPolicyStatus, $exposureStatus, $exposureDetail);
  return $PolicyFindings; }
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
// / Every check below is specific to this application. The kernel namespace settings, the
// / three AppArmor policies & the DATA exposure test are HRConvert2's business, & another
// / application on the same Engine has no ImageMagick policy to check.
// /
// / NOTHING HERE REPAIRS ANYTHING. Repair needs root & the listener does not have it, so a
// / validator that tried would fail confusingly on every pass. It reports, & -fp fixes.
// / A drifted policy is reported & does not fail readiness, because conversions still run.
function applicationEnvironmentFindings() {
  // / Set variables.
  global $ServerRootDir, $InstLoc, $HostedLoc, $ApplicationLoc, $DirSep, $RequiredSetupCoreVersion, $EnableMemoryProtection;
  $ApplicationIsReady = TRUE;
  $ApplicationFindings = array();
  $policyIsValid = $kernelIsReady = $dataIsProtected = $setupIsAvailable = FALSE;
  $policyStatus = $exposureStatus = $exposureDetail = $hostedStatus = $hostedDetail = $setupVersion = '';
  $hostedWritten = $hostedRemoved = 0;
  $kernelFindings = $kernelFinding = $misplacedRoots = array();
  // / Report the kernel settings first. A sandbox failure is almost always one of these &
  // / naming the setting is more use than naming the symptom.
  list ($kernelIsReady, $kernelFindings) = verifySandboxKernel(FALSE);
  foreach ($kernelFindings as $kernelFinding) $ApplicationFindings[] = $kernelFinding;
  if (!$kernelIsReady) $ApplicationIsReady = FALSE;
  // / Policies are validated & never repaired here. A drifted policy is reported.
  // / The status word is reported as it is, rather than flattened to ok, & the sentence
  // / beside it says whether anything needs doing. Flattening lost the difference between
  // / a policy that matches & a host that never needed one.
  list ($policyIsValid, $policyStatus) = verifySandboxPolicy(FALSE);
  $ApplicationFindings[] = array('Check' => 'Sandbox AppArmor', 'Status' => policyDisplayStatus($policyStatus), 'Detail' => describePolicyStatus('Sandbox AppArmor', $policyStatus));
  list ($policyIsValid, $policyStatus) = verifyImageMagickPolicy(FALSE);
  $ApplicationFindings[] = array('Check' => 'ImageMagick policy', 'Status' => policyDisplayStatus($policyStatus), 'Detail' => describePolicyStatus('ImageMagick', $policyStatus));
  list ($policyIsValid, $policyStatus) = verifyOpenScadPolicy(FALSE);
  $ApplicationFindings[] = array('Check' => 'OpenSCAD AppArmor', 'Status' => policyDisplayStatus($policyStatus), 'Detail' => describePolicyStatus('OpenSCAD AppArmor', $policyStatus));
  // / The DATA tree is part of the environment & is counted with everything else.
  // / It was reported separately & AFTER the summary line, so a run could print that every
  // / check passed & then say the tree was exposed directly underneath it. A summary that
  // / does not cover a check is worse than no summary, because it is read instead of the
  // / thing it failed to include.
  // / An exposed tree does not stop a conversion, so it does not make the environment
  // / unready. It is reported loudly & the operator decides.
  list ($dataIsProtected, $exposureStatus, $exposureDetail) = verifyDataExposure();
  $ApplicationFindings[] = array('Check' => 'DATA exposure', 'Status' => ($exposureStatus === 'protected' ? 'ok' : strtoupper($exposureStatus)), 'Detail' => $exposureDetail);
  // / The data location itself is never served, & is named here when it sits inside the document
  // / root, where its sessions, caches & logs could be fetched. It does not stop a conversion, so
  // / it does not make the environment unready. It was not checked at all until v4.0.1.
  $misplacedRoots = misplacedDataRoots();
  if (count($misplacedRoots) === 0) $ApplicationFindings[] = array('Check' => 'Data location', 'Status' => 'ok', 'Detail' => 'Outside the document root.');
  else $ApplicationFindings[] = array('Check' => 'Data location', 'Status' => 'MISPLACED', 'Detail' => implode(' & ', $misplacedRoots).' '.(count($misplacedRoots) === 1 ? 'is' : 'are').' inside the document root '.$ServerRootDir.', so sessions, caches & logs could be fetched. Move '.(count($misplacedRoots) === 1 ? 'it' : 'them').' outside it.');
  // / The application runs from Application in the data location & the web server serves only the
  // / hosted location, which holds the loader. Both are reported & neither is repaired here. An
  // / application still running from inside the web root is NOT MOVED, & -fp moves it.
  if ((string)@realpath($InstLoc) !== (string)@realpath($ApplicationLoc)) $ApplicationFindings[] = array('Check' => 'Hosted location', 'Status' => 'NOT MOVED', 'Detail' => 'The application runs from '.$InstLoc.', which the web server serves. Run -fp as root to install it into '.$ApplicationLoc.'.');
  else {
    list ($setupIsAvailable, $setupVersion) = verifyCoreComponent('Setup Core', 'Engine'.$DirSep.'Cores'.$DirSep.'setupCore.php', 'SetupCoreVersion', $RequiredSetupCoreVersion);
    if ($setupIsAvailable && function_exists('publishHostedLocation')) list ($hostedStatus, $hostedDetail, $hostedWritten, $hostedRemoved) = publishHostedLocation($HostedLoc, $InstLoc, FALSE);
    else {
      $hostedStatus = 'UNKNOWN';
      $hostedDetail = 'The Setup Core is unavailable, so the hosted location could not be checked.'; }
    $ApplicationFindings[] = array('Check' => 'Hosted location', 'Status' => $hostedStatus, 'Detail' => $hostedDetail); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $policyIsValid, $policyStatus, $kernelIsReady, $kernelFindings, $kernelFinding, $dataIsProtected, $exposureStatus, $exposureDetail, $misplacedRoots, $setupIsAvailable, $setupVersion, $hostedStatus, $hostedDetail, $hostedWritten, $hostedRemoved);
  return array($ApplicationIsReady, $ApplicationFindings); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to scan a path & act on the verdict.
// / Accepts the path. Returns whether the scan ran & whether a threat was found.
// /
// / IT DISPATCHES TO A SCANNER PIPELINE & does not know which scanner ran.
// / This used to shell out to ClamAV directly, which meant the two scanner pipelines were
// / pinned, installed, verified & never actually used by the application that shipped them.
// / A scanner is chosen by NAME through $DefaultVirusScanner, so an installation can run
// / ScanCore where ClamAV is not wanted & this function does not change.
// /
// / THE SCANNER REPORTS & THE CALLER DECIDES. A pipeline never deletes anything. Removing an
// / infected file is a policy this application holds & another application may not want.
// /
// / NOTHING THE SCANNER SAID IS REPEATED ANYWHERE. Not to the user & not to the log. The
// / findings are read for a verdict & discarded. A signature name comes from a database this
// / application does not control & is chosen by whoever wrote it.
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
// / A function to keep the DATA protection file current without waiting for -fp.
// / Accepts no arguments.
// / Returns TRUE when the file is present & correct, or when it belongs to somebody else.
// /
// / A protection file this application wrote must never be the reason the server stops.
// / An earlier release wrote Options & php_flag into this file. Both need AllowOverride
// / Options, & where that is not granted Apache does not ignore them, it REFUSES the
// / request, so the whole DATA tree returned 500 & every download & every share link on the
// / server stopped working. Replacing the PHP does not fix that, because the bad file is
// / already on disk & nothing rewrites it until an administrator happens to run -fp. An
// / installation could therefore be upgraded to a fixed release & stay broken indefinitely.
// /
// / So the file is brought up to date here instead, next to the index.html files, on the
// / same schedule & by the same request. An upgrade repairs itself the first time anybody
// / loads a page & no operator has to know that -fp was the cure.
// /
// / Only a file carrying our own marker is ever touched.
// / An administrator who wrote their own .htaccess here gets to keep it, exactly as
// / verifyPolicyFile() treats every other policy. Theirs is left alone & -fp is where the
// / offer to replace it lives, because that is an interactive, root, deliberate act.
// /
// / This is a read & a comparison on a small file, & it writes only when the contents
// / actually differ, which after the first request is never.
function maintainDataProtection() {
  // / Set variables.
  global $ConvertTemp, $DirSep, $MaintainHTAccess, $Verbose, $RunningAsRoot, $EnableMemoryProtection;
  $ProtectionIsCurrent = FALSE;
  $protectionPath = $existingContents = $desiredContents = $readNotice = $removalNotice = $writeNotice = '';
  $carriesMarker = $fileWasRead = $fileWasRemoved = $fileWasWritten = FALSE;
  $bytesWritten = 0;
  $maintainHtaccess = TRUE;
  if (isset($MaintainHTAccess)) $maintainHtaccess = (bool)$MaintainHTAccess;
  if ((string)$ConvertTemp === '' or !is_dir($ConvertTemp)) $ProtectionIsCurrent = FALSE;
  // / Turning the setting off has to actually take effect.
  // / Leaving a file behind that this application wrote, & then declining to maintain it,
  // / would give an administrator a stale copy of a rule set that no longer matches the
  // / release & no way to notice. So a file carrying OUR marker is removed. A file the
  // / administrator wrote is left exactly where it is, because it was never ours to delete.
  else if (!$maintainHtaccess) {
    $protectionPath = $ConvertTemp.$DirSep.'.htaccess';
    $ProtectionIsCurrent = TRUE;
    // / Root reaches this through -fp, & the DATA folder is the web server account's, so the file
    // / is read & removed through the kernel. A link or a FIFO there is never followed or waited on.
    list ($fileWasRead, $existingContents, $readNotice) = readFileWithoutFollowing($protectionPath, 1048576);
    if ($fileWasRead && strpos($existingContents, 'HRCONVERT2-POLICY-MARKER') !== FALSE) {
      list ($fileWasRemoved, $removalNotice) = removeFileWithoutFollowing($protectionPath);
      if ($fileWasRemoved) warningEntry('--Maintain HTAccess-- is disabled, so the DATA protection file this application wrote at '.$protectionPath.' was removed. The server configuration is now carrying these rules alone. Run the -fp argument & read the DATA exposure line to confirm the tree is still protected.');
      else warningEntry('--Maintain HTAccess-- is disabled & the DATA protection file this application wrote could not be removed. '.$removalNotice); } }
  else {
    $protectionPath = $ConvertTemp.$DirSep.'.htaccess';
    $desiredContents = dataProtectionContents();
    list ($fileWasRead, $existingContents, $readNotice) = readFileWithoutFollowing($protectionPath, 1048576);
    if ($fileWasRead) $carriesMarker = (strpos($existingContents, 'HRCONVERT2-POLICY-MARKER') !== FALSE);
    // / Already exactly right. This is the case on every request after the first.
    if ($existingContents === $desiredContents) $ProtectionIsCurrent = TRUE;
    // / Somebody else's file. Leave it completely alone & say nothing on every request.
    else if ($existingContents !== '' && !$carriesMarker) $ProtectionIsCurrent = TRUE;
    else {
      // / A web request writes its own file in its own folder. Root never writes by name there.
      if ($RunningAsRoot) list ($fileWasWritten, $writeNotice) = writeFileWithoutFollowing($protectionPath, $desiredContents, 0644);
      else {
        $bytesWritten = @file_put_contents($protectionPath, $desiredContents);
        $fileWasWritten = ($bytesWritten === strlen($desiredContents));
        if ($fileWasWritten) @chmod($protectionPath, 0644); }
      if ($fileWasWritten) {
        $ProtectionIsCurrent = TRUE;
        // / Worth a warning rather than a normal entry, because it means this installation
        // / was running with a protection file that did not match this release, & on the
        // / upgrade path from v3.8.6 that file was returning 500 for every download.
        warningEntry('The DATA protection file at '.$protectionPath.' did not match this release & was rewritten. If downloads or share links were returning a server error, that is now corrected.'); }
      // / A failed write is not fatal. The tree may be exposed or may be broken, & the
      // / exposure check is what establishes which, so this only records that it happened.
      else if ($Verbose) logEntry('The DATA protection file at '.$protectionPath.' could not be written. '.$writeNotice); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $protectionPath, $existingContents, $desiredContents, $readNotice, $removalNotice, $writeNotice, $carriesMarker, $fileWasRead, $fileWasRemoved, $fileWasWritten, $maintainHtaccess, $bytesWritten);
  return $ProtectionIsCurrent; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to create required directories if they do not exist.
// / Maintenance folders are emptied by passing their children to cleanFiles().
// / cleanFiles() refuses to operate on an approved root, so the root itself is never passed.
// / ROOT NEVER REACHES THIS, & so nothing here changes an owner. Only a web request calls it,
// / after the security context check has refused root with error 28. Until v4.0.1 it still
// / carried a branch for root that chmodded & chowned each folder by name, from a time when a
// / root command line run reached it, & that branch could only ever have been dangerous.
function verifyRequiredDirs() {
  // / Set variables.
  global $ConvertLoc, $RequiredDirs, $RequiredIndexes, $RequiredCleanupFolders, $Verbose, $PermissionLevels, $DirSep, $InstLoc, $EnableMemoryProtection;
  $DirectoriesVerified = $directoriesProtected = 0;
  $locationIsProtected = $protectionWasWritten = FALSE;
  $RequiredDirsExist = TRUE;
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
  maintainDataProtection();
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
  purgeSensitiveMemory($EnableMemoryProtection, $directoriesProtected, $locationIsProtected, $protectionWasWritten, $allowedCleanupRoots, $requiredDir, $requiredIndex, $requiredCleanupFolder, $cleanupEntry, $cleanupPath, $cleanupContents);
  return array($RequiredDirsExist, $RequiredDirs, $DirectoriesVerified); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to download an update package to a temporary location.
// / curl is used rather than wget because GitHub redirects a tarball request twice, first
// / to codeload.github.com & then to object storage. curl -L follows the chain. A wget
// / based updater was attempted previously & was defeated by exactly this.
// / The download is bounded by size & by time so a hostile or broken source cannot fill
// / the disk or hold the process open indefinitely.
function downloadUpdatePackage($targetURL, $downloadPath) {
  // / Set variables.
  global $Verbose, $MaxUpdatePackageSize, $UpdateConnectionTimeout, $EnableMemoryProtection;
  $PackageDownloaded = FALSE;
  $curlCommand = '';
  $curlOutput = array();
  $curlExitCode = 1;
  if ($Verbose) logEntry('Downloading update package.');
  // / -q reads no .curlrc & has to come first, so no configuration file changes this request.
  // / -L follows redirects. -f fails on an HTTP error rather than writing the error page.
  // / --max-filesize refuses a package larger than the configured ceiling.
  // / --max-time bounds the whole transfer, not just the connection.
  $curlCommand = 'curl -q -L -f -s'
    .' --max-time '.(int)$UpdateConnectionTimeout
    .' --max-filesize '.(int)$MaxUpdatePackageSize
    .' -H '.escapeshellarg('User-Agent: HRConvert2-Updater')
    .' -o '.escapeshellarg($downloadPath)
    .' '.escapeshellarg($targetURL).' 2>/dev/null';
  exec($curlCommand, $curlOutput, $curlExitCode);
  // / curl exits 22 on an HTTP error, which for a tag means the tag does not exist.
  if ($curlExitCode === 22) errorEntry('The requested update version does not exist at the update source!', 29002, FALSE);
  else if ($curlExitCode === 63) errorEntry('The update package is larger than the configured maximum!', 29003, FALSE);
  else if ($curlExitCode !== 0) errorEntry('Could not download the update package! Curl exited with code '.$curlExitCode.'.', 29004, FALSE);
  else if (!file_exists($downloadPath) or filesize($downloadPath) < 1024) errorEntry('The downloaded update package is empty or too small to be valid!', 29005, FALSE);
  else $PackageDownloaded = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $curlCommand, $curlOutput, $curlExitCode, $targetURL, $downloadPath);
  return $PackageDownloaded; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to merge the live configuration into a freshly downloaded one.
// / The NEW file provides the structure, the comments & the set of settings that exist.
// / The OLD file provides the values, for every setting that exists in both.
// / A setting that is new in this release keeps the new default.
// / A setting that was removed in this release is discarded with the old file.
// / A setting whose name was corrected is treated as a removal & an addition. No attempt
// / is made to guess that two differently spelled names mean the same thing.
// /
// / ARRAYS ARE NOT MERGED. The new default is kept & the difference is reported.
// / A scalar can be substituted into the new file safely. An array cannot be rewritten
// / without reformatting it, which would destroy the alignment & comments in the new file,
// / & a partial merge of a two hundred element format list is worse than either version.
// / The administrator is told which arrays differed so an intentional change can be
// / reapplied by hand.
function mergeConfigFile($oldConfigPath, $newConfigPath) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $ConfigMerged = FALSE;
  $ChangedArrays = $PreservedSettings = array();
  $oldValues = $matches = $match = array();
  $oldContents = $newContents = $mergedContents = $settingName = $settingValue = $exportedValue = $newValue = $oldValue = '';
  $bytesWritten = 0;
  // / Read every scalar assignment out of the OLD file without executing it.
  // / The old file is trusted PHP, but reading it by pattern avoids any side effect &
  // / avoids polluting this scope with seventy variables.
  $oldContents = @file_get_contents($oldConfigPath);
  $newContents = @file_get_contents($newConfigPath);
  if ($oldContents === FALSE or $newContents === FALSE) errorEntry('Could not read a configuration file during the merge!', 29006, FALSE);
  else {
    // / Collect every assignment in the old file. Arrays are captured but not substituted.
    if (preg_match_all('/^\$([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.+?);\s*$/m', $oldContents, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $match) $oldValues[$match[1]] = trim($match[2]); }
    // / Walk the NEW file & substitute the old value wherever both files define a setting.
    $mergedContents = preg_replace_callback(
      '/^\$([A-Za-z_][A-Za-z0-9_]*)(\s*=\s*)(.+?);\s*$/m',
      function ($match) use ($oldValues, &$ChangedArrays, &$PreservedSettings) {
        $settingName = $match[1];
        $newValue = trim($match[3]);
        // / A setting that does not exist in the old file keeps the new default.
        if (!isset($oldValues[$settingName])) return $match[0];
        $oldValue = $oldValues[$settingName];
        // / An array keeps the new default. Report it only if the old value differed.
        if (stripos($newValue, 'array(') === 0 or stripos($oldValue, 'array(') === 0 or strpos($newValue, '[') === 0) {
          if ($oldValue !== $newValue) array_push($ChangedArrays, $settingName);
          return $match[0]; }
        // / A scalar takes the old value. Nothing else on the line is touched, so every
        // / comment & every alignment in the new file survives.
        if ($oldValue !== $newValue) array_push($PreservedSettings, $settingName);
        return '$'.$settingName.$match[2].$oldValue.';'; },
      $newContents);
    if (!is_string($mergedContents)) errorEntry('Could not merge the configuration file!', 29007, FALSE);
    else {
      $bytesWritten = @file_put_contents($newConfigPath, $mergedContents, LOCK_EX);
      if ($bytesWritten === strlen($mergedContents)) $ConfigMerged = TRUE;
      else errorEntry('Could not write the merged configuration file!', 29008, FALSE); } }
  if ($Verbose && $ConfigMerged) logEntry('Configuration merged. '.count($PreservedSettings).' setting(s) carried over. '.count($ChangedArrays).' array(s) reset to the new default.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $match, $newValue, $oldValue, $oldContents, $newContents, $mergedContents, $matches, $oldValues, $settingName, $settingValue, $exportedValue, $bytesWritten, $oldConfigPath, $newConfigPath);
  return array($ConfigMerged, $PreservedSettings, $ChangedArrays); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to update the application in place.
// / Called by the -u & --update command line arguments. NEVER reachable from the web.
// / Replacing application code requires write access to the installation directory, which
// / is the correct authorization for this operation & is what shell access already implies.
// / An HTTP endpoint protected by a secret would turn that into one guessable string.
// /
// / The sequence is stage, swap, validate, rollback on failure.
// / Nothing touches the live installation until a complete & correct replacement exists.
// / The previous installation is retained until the NEXT update runs, so an administrator
// / who discovers a problem an hour later still has something to restore by hand.
// /
// / NOTHING THE UPDATE TOUCHES IS EVER THE WEB SERVER ACCOUNT'S. Until v4.0.1 the release was
// / downloaded & unpacked inside a session folder that account owns, & the staged copy was
// / handed to it with chown -R before root ran it to validate it, so the web server account
// / could edit code root was about to run. The work now happens beside the installation, in a
// / folder only root can change, & the update refuses to start when that folder is not one.
// / The staged copy is root's & is 0700 until it is in place, so nothing serves it early.
// / The archive's own owners are ignored, so a release cannot hand a file to another account.
function updateApplication($requestedVersion) {
  // / Set variables.
  global $InstLoc, $ProprietaryLoc, $DirSep, $HRConvertVersion, $AutoUpdateTargetVersion, $EnableAutoUpdates, $BackupLoc, $PermissionLevels, $Lol, $EnableMemoryProtection;
  $UpdateSucceeded = $targetResolved = $packageDownloaded = $configMerged = $installationIsValid = FALSE;
  $updatePermissionsFixed = $alreadyInstalled = FALSE;
  $updateRepairExitCode = 1;
  $swapCompleted = $rolledBack = FALSE;
  $targetVersion = $targetURL = $workDir = $downloadPath = $extractedDir = $stagedDir = $oldDir = $backupOutput = '';
  $preservedSettings = $changedArrays = $extractOutput = $extractedRoots = array();
  $extractExitCode = $backupExitCode = 1;
  $parentIsTrusted = $backupIsHeld = FALSE;
  $resolvedParent = $trustNotice = $resolvedBackupParent = $backupNotice = '';
  $backupStatus = array();
  // / An update must be explicitly enabled. A server that does not want this cannot get it.
  if (!$EnableAutoUpdates) {
    errorEntry('Automatic updates are disabled in config.php!', 29009, FALSE);
    print($Lol.'Automatic updates are disabled. Set $EnableAutoUpdates to TRUE in config.php.'.$Lol.$Lol); }
  else {
    // / The command line argument overrides the configured default when one is supplied.
    if ($requestedVersion === '') $requestedVersion = $AutoUpdateTargetVersion;
    list ($targetResolved, $targetVersion, $targetURL) = resolveUpdateTarget($requestedVersion);
    // / Refuse to reinstall the version that is already running.
    // / edge is always fetched, because master moves without the version stamp changing.
    if ($targetResolved && $targetVersion !== 'edge' && ltrim($targetVersion, 'vV') === ltrim($HRConvertVersion, 'vV')) {
      $targetResolved = FALSE;
      print($Lol.'Version '.$targetVersion.' is already installed. Nothing to do.'.$Lol.$Lol);
      logEntry('Update requested but '.$targetVersion.' is already installed.');
      // / The requested version is installed when this returns, which is what success means.
      // / It returned FALSE here until v3.9.9, which read as a failure to anything checking.
      // / NOTHING WAS INSTALLED, SO NOTHING BELOW MAY RUN. Every step after this one is gated
      // / on success & assumes a new release arrived, so each is gated on this as well.
      // / Without it a no-op would repair permissions, report an update & name a backup.
      $UpdateSucceeded = $alreadyInstalled = TRUE; } }
  // / The folder the swap happens in must be one only root can change, or the web server
  // / account could rename the installation aside & put its own in its place.
  if ($targetResolved && !$alreadyInstalled) {
    list ($parentIsTrusted, $resolvedParent, $trustNotice) = pathIsTrustedByRoot(dirname($InstLoc));
    if ($parentIsTrusted && dirname($InstLoc) !== rtrim((string)$ProprietaryLoc, $DirSep)) list ($parentIsTrusted, $resolvedParent, $trustNotice) = pathIsTrustedByRoot($ProprietaryLoc);
    if (!$parentIsTrusted) {
      $targetResolved = FALSE;
      errorEntry('The update was refused, because the folder it would happen in can be changed by an account other than root. '.$trustNotice, 31023, FALSE);
      print($Lol.'The update was refused. '.$trustNotice.$Lol.'Run  sudo php convertCore.php -fp  & try again. A folder above '.$ProprietaryLoc.' is not'.$Lol.'this application\'s & -fp leaves it alone. See error 31023 in Documentation/ERROR_DESCRIPTIONS.txt.'.$Lol.$Lol); } }
  // / Download & stage the release.
  if ($targetResolved && !$alreadyInstalled) {
    $workDir = dirname($InstLoc).$DirSep.'.'.basename($InstLoc).'-update-'.bin2hex(random_bytes(8));
    $downloadPath = $workDir.$DirSep.'package.tar.gz';
    $stagedDir = $workDir.$DirSep.'staged';
    if (!@mkdir($workDir, $PermissionLevels, TRUE)) errorEntry('Could not create a working directory for the update!', 29010, FALSE);
    else {
      print($Lol.'Downloading '.$targetVersion.' ...'.$Lol);
      $packageDownloaded = downloadUpdatePackage($targetURL, $downloadPath); } }
  // / Extract the package & locate the single directory GitHub wraps every archive in.
  // / GITHUB'S ARCHIVES CARRY 0664 & 0775, because git archive applies umask 002. Extracted as
  // / they are, every file in the new release could be written by root's group, root would
  // / refuse to run it, & every update would fail validation & roll back. The modes are taken
  // / through umask 022 instead, which is what a release built by hand already has.
  if ($packageDownloaded) {
    @mkdir($stagedDir, $PermissionLevels, TRUE);
    exec('umask 022 && tar --no-same-owner --no-same-permissions -xzf '.escapeshellarg($downloadPath).' -C '.escapeshellarg($stagedDir).' 2>&1', $extractOutput, $extractExitCode);
    if ($extractExitCode !== 0) errorEntry('Could not extract the update package!', 29011, FALSE);
    else {
      // / A GitHub tarball contains exactly one top level directory named for the ref.
      $extractedRoots = array_diff(scandir($stagedDir), array('.', '..'));
      if (count($extractedRoots) !== 1) errorEntry('The update package does not have the expected structure!', 29012, FALSE);
      else {
        $extractedDir = $stagedDir.$DirSep.reset($extractedRoots);
        // / A release that does not contain a core is not a release.
        if (!file_exists($extractedDir.$DirSep.'convertCore.php')) errorEntry('The update package does not contain convertCore.php!', 29013, FALSE);
        else {
          print('Merging configuration ...'.$Lol);
          list ($configMerged, $preservedSettings, $changedArrays) = mergeConfigFile(
            $InstLoc.$DirSep.'Resources'.$DirSep.'config.php',
            $extractedDir.$DirSep.'Resources'.$DirSep.'config.php'); } } } }
  // / Carry the live data directory across & perform the swap.
  if ($configMerged) {
    $oldDir = $InstLoc.'.old';
    // / A previous update should not have left this behind, but a crashed one might have.
    if (is_dir($oldDir)) exec('rm -rf '.escapeshellarg($oldDir).' 2>&1');
    // / The staged copy must be on the same filesystem as the installation, or rename()
    // / is not atomic & may not work at all. It is moved beside the installation first.
    // / Beside it by name too, so the work folder, the staged copy & .old share one folder.
    $stagedDir = $InstLoc.'.new';
    if (is_dir($stagedDir)) exec('rm -rf '.escapeshellarg($stagedDir).' 2>&1');
    if (!@rename($extractedDir, $stagedDir)) {
      // / rename() across filesystems fails, so fall back to a copy.
      exec('cp -a '.escapeshellarg($extractedDir).' '.escapeshellarg($stagedDir).' 2>&1'); }
    if (!is_dir($stagedDir)) errorEntry('Could not stage the update beside the installation!', 29014, FALSE);
    else {
      // / Nothing serves the staged copy before it is in place.
      @chmod($stagedDir, 0700);
      // / DATA holds live user sessions & is moved rather than copied. It stays the web server
      // / account's. Everything else in the new release is root's, & its own -fp sets its modes.
      // / THE RELEASE SHIPS A DATA FOLDER OF ITS OWN, holding only its placeholder page, & a
      // / folder cannot be renamed over one that is not empty. Until v4.0.1 that rename failed
      // / in silence, the live DATA left with the old installation, & was deleted with it, so a
      // / successful update deleted every session. The release's own copy is removed first, &
      // / an update whose DATA cannot be moved stops here rather than going on without it.
      // / An installation in Application holds no DATA, because DATA stays in the hosted location,
      // / so from v4.0.1 on there is nothing to move & only the release's own copy is removed.
      if (is_dir($stagedDir.$DirSep.'DATA')) exec('rm -rf '.escapeshellarg($stagedDir.$DirSep.'DATA').' 2>&1');
      if (is_dir($InstLoc.$DirSep.'DATA') && !@rename($InstLoc.$DirSep.'DATA', $stagedDir.$DirSep.'DATA')) errorEntry('Could not move the DATA folder into the update, so the update was stopped & nothing was changed!', 29019, FALSE);
      else print('Swapping installation ...'.$Lol);
      // / Two atomic renames. There is no moment where the installation is incomplete.
      if (is_dir($InstLoc.$DirSep.'DATA')) $swapCompleted = FALSE;
      else if (!@rename($InstLoc, $oldDir)) {
        // / The live DATA is back where it came from, so an update that stops here loses nothing.
        @rename($stagedDir.$DirSep.'DATA', $InstLoc.$DirSep.'DATA');
        errorEntry('Could not move the existing installation aside!', 29015, FALSE); }
      else if (!@rename($stagedDir, $InstLoc)) {
        // / The installation is currently absent. Put it back immediately, & its DATA with it.
        @rename($oldDir, $InstLoc);
        @chmod($InstLoc, 0755);
        @rename($stagedDir.$DirSep.'DATA', $InstLoc.$DirSep.'DATA');
        errorEntry('Could not move the update into place! The previous installation was restored.', 29016, FALSE); }
      else {
        // / The new installation is served from here on & the old one never again.
        @chmod($InstLoc, 0755);
        @chmod($oldDir, 0700);
        $swapCompleted = TRUE; } } }
  // / Validate the swapped installation & roll back if it cannot run.
  if ($swapCompleted) {
    print('Validating ...'.$Lol);
    $installationIsValid = validateInstallation($InstLoc);
    if (!$installationIsValid) {
      warningEntry('The updated installation failed validation. Rolling back.');
      // / Return the data directory to the installation being restored.
      if (is_dir($InstLoc.$DirSep.'DATA')) @rename($InstLoc.$DirSep.'DATA', $oldDir.$DirSep.'DATA');
      // / The installation is about to be REPLACED, so this deletes it. That is the single
      // / most destructive line in this application & it runs as root.
      // / A configuration pointing $InstLoc at a system directory would delete the operating
      // / system instead. The guard resolves symlinks & .. before judging.
      if (!pathIsSafeToModifyRecursively($InstLoc)) errorEntry('The installation location resolved to a system directory & was NOT deleted. Check $InstLoc in config.php. Path: '.$InstLoc.'.', 31018, TRUE);
      exec('rm -rf '.escapeshellarg($InstLoc).' 2>&1');
      // / Rollback is a single rename because the previous installation never left the
      // / filesystem it lived on. This is the reason .old exists at all.
      if (@rename($oldDir, $InstLoc)) {
        // / The old installation was closed to the web server when it was moved aside.
        @chmod($InstLoc, 0755);
        $rolledBack = TRUE;
        errorEntry('The updated installation failed validation & was rolled back!', 29017, FALSE); }
      else errorEntry('The updated installation failed validation & COULD NOT be rolled back!', 29018, TRUE); }
    else $UpdateSucceeded = TRUE; }
  // / Preserve the previous installation outside the web root, then remove it from inside it.
  // / .old sits in $ProprietaryLoc because rename() is only atomic within one filesystem,
  // / & $ProprietaryLoc is served by Apache. A stale convertCore.php left there would be
  // / a second, older copy of the application answering requests over HTTP.
  // / The backup location is inside the non hosted data location & is never served.
  // / DATA is excluded. It holds live user sessions, it has already been moved into the
  // / new installation, & a copy of it would be swept on the delete threshold anyway.
  if ($UpdateSucceeded && !$alreadyInstalled && is_dir($oldDir)) {
    // / ROOT KEEPS A BACKUP ONLY WHERE NOTHING ELSE CAN REPLACE IT. Emptying, copying into &
    // / pruning the backup all work by name, so every folder above it must be root's & the
    // / backup itself must not be a link. It sits in the data location by default, which the
    // / web server account owns, so by default it is skipped & a WARNING says so. Until v4.0.1
    // / it was written there regardless. A later release gives it a folder of its own.
    list ($backupIsHeld, $resolvedBackupParent, $backupNotice) = pathIsTrustedByRoot(dirname((string)$BackupLoc));
    if ($backupIsHeld && $resolvedBackupParent !== dirname((string)$BackupLoc)) $backupIsHeld = FALSE;
    clearstatcache(TRUE, (string)$BackupLoc);
    $backupStatus = @lstat((string)$BackupLoc);
    if ($backupIsHeld && is_array($backupStatus) && ((int)$backupStatus['mode'] & 0170000) !== 0040000) {
      $backupIsHeld = FALSE;
      $backupNotice = $BackupLoc.' is not a folder.'; }
    if (!$backupIsHeld) warningEntry('The previous installation was not preserved, because the backup location at '.$BackupLoc.' sits where another account could replace it. '.$backupNotice.' The update itself is unaffected.');
    // / The backup location is emptied before a new backup is written into it.
    // / A misconfigured $BackupLoc would empty whatever it points at instead.
    else if (is_dir($BackupLoc) && !pathIsSafeToModifyRecursively($BackupLoc)) errorEntry('The backup location resolved to a system directory & was NOT emptied. Check $BackupLoc in config.php. Path: '.$BackupLoc.'.', 31019, TRUE);
    else {
      if (is_dir($BackupLoc)) exec('rm -rf '.escapeshellarg($BackupLoc).' 2>&1');
      exec('cp -a '.escapeshellarg($oldDir).' '.escapeshellarg($BackupLoc).' 2>&1', $backupOutput, $backupExitCode);
      if ($backupExitCode === 0 && is_dir($BackupLoc)) {
        if (is_dir($BackupLoc.$DirSep.'DATA')) exec('rm -rf '.escapeshellarg($BackupLoc.$DirSep.'DATA').' 2>&1');
        // / The backup is root's alone. -fp below gives it its role, root-only, & until v4.0.1
        // / it was handed to the web server account, so a restore could reinstall its edits.
        logEntry('Previous installation preserved at '.$BackupLoc.'.'); }
      else warningEntry('Could not preserve the previous installation at '.$BackupLoc.'. It will be discarded.'); }
    // / The previous installation must not remain inside the web root under any outcome.
    exec('rm -rf '.escapeshellarg($oldDir).' 2>&1'); }
  // / Correct everything the new files arrived with. An update ships a fresh tree owned by
  // / whoever unpacked it, & a release that changes a policy, a service unit or a required
  // / version leaves the installed copies behind. Running the repair here is the difference
  // / between an update that works & one that needs an operator to know to run -fp next.
  // / THE NEW RELEASE REPAIRS ITSELF, as its own process. This process is the old release, &
  // / its exact pins refuse the new release's Setup Core, so until v4.0.1 the repair here was
  // / the old one, could not load Setup Core, & corrected nothing. The new release knows its
  // / own locations & roles, which is also the only way a release can change them.
  if ($UpdateSucceeded && !$alreadyInstalled) {
    print($Lol.'Correcting permissions, policies & the service unit for the new release.'.$Lol);
    passthru('php '.escapeshellarg($InstLoc.$DirSep.engineApplicationEntryPoint()).' -fp', $updateRepairExitCode);
    $updatePermissionsFixed = ($updateRepairExitCode === 0);
    if (!$updatePermissionsFixed) warningEntry('The update completed but the new release could not correct its permissions & policies. Run  sudo php convertCore.php -fp  by hand.'); }
  // / Report the outcome.
  if ($UpdateSucceeded && !$alreadyInstalled) {
    logEntry('Application updated to '.$targetVersion.' from '.$HRConvertVersion.'.');
    print($Lol.'Updated to '.$targetVersion.'.'.$Lol);
    print('The previous installation is preserved at '.$BackupLoc.'.'.$Lol);
    if (count($preservedSettings) > 0) print($Lol.count($preservedSettings).' configuration setting(s) were carried over.'.$Lol);
    if (count($changedArrays) > 0) {
      print($Lol.'The following array settings were RESET to the new defaults.'.$Lol);
      print('Reapply any intentional changes by hand.'.$Lol);
      foreach ($changedArrays as $changedArray) print('  $'.$changedArray.$Lol); }
    print($Lol.'Run  php convertCore.php -v  to confirm the new installation.'.$Lol.$Lol); }
  else if ($rolledBack) print($Lol.'The update failed validation & was rolled back. The previous version is running.'.$Lol.$Lol);
  // / Remove the temporary working directory whatever the outcome.
  if ($workDir !== '' && is_dir($workDir)) exec('rm -rf '.escapeshellarg($workDir).' 2>&1');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $backupExitCode, $backupOutput, $changedArray, $targetResolved, $packageDownloaded, $configMerged, $installationIsValid, $swapCompleted, $rolledBack, $targetVersion, $targetURL, $workDir, $downloadPath, $extractedDir, $stagedDir, $oldDir, $preservedSettings, $changedArrays, $extractOutput, $extractedRoots, $extractExitCode, $requestedVersion, $updatePermissionsFixed, $updateRepairExitCode, $alreadyInstalled, $parentIsTrusted, $resolvedParent, $trustNotice, $backupIsHeld, $resolvedBackupParent, $backupNotice, $backupStatus);
  return $UpdateSucceeded; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to replace the install wide secret with a new one.
// / Accepts whether the action was confirmed in advance with -y. Returns a success boolean.
// / Called by --rotate-secret. NEVER reachable from the web, & root only.
// /
// / EVERY SESSION ENDS. Every session folder, token & startup key derives from the secret, so
// / a visitor is given a new session on their next visit & cannot reach their earlier files.
// / Those folders stay where they are & expire on the delete threshold like any other. That
// / is what rotating a secret is for, so the operator is asked first, or answers with -y.
// /
// / The listener holds keys derived from the old secret, so one that is running must be stopped
// / & started again, & root never does either through the web server account's files.
// /   Ask whether a listener is running & stop it, BEFORE the secret is rewritten. A service
// /   unit that owns the listener is asked through systemctl. Otherwise the web server account
// /   is asked, & it answers & stops with the OLD key, which is still the one on disk.
// /   A listener that was running & could not be stopped would keep answering with the old key
// /   while every worker derives the new one, so nothing is rotated & the operator is told.
// /   Rewrite the secret as root, which is the one part that is root's.
// /   Start it again through startCoreManagerListener(), which uses the unit when one owns the
// /   listener & hands the start to the web server account when none does.
// / A listener that was not running is left stopped, exactly as before.
// / Until v4.0.1 the restart was always handed to the web server account, which cannot start a
// / unit, & a failed stop was rotated past.
// / A container from an image built before v4.0.1 carries the secret that image was built
// / with, & every container started from it shares it. Running this once in each ends that.
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
    // / Every conversion family has migrated. This file converts nothing on its own.
    // / There is no built in dispatcher left to fall back to, so a family with no installed
    // / pipeline is reported here rather than failing silently. That was the whole purpose
    // / of $builtInDispatcherFamilies while the migration was under way, & the list is gone
    // / now that it would be empty.
    // / Restoring a converter to this file is not the fix for this error.
    // / Repair the pipeline folder. Run with -v & read the warnings above this line, which
    // / name the folder that was refused & the reason.
    else {
      $ConversionErrors = TRUE;
      errorEntry('The '.$type.' conversion family has no installed pipeline!', 34005, FALSE); }
    // / A non zero worker PID means the core must stay alive after serving the user.
    // / Streaming is the reference case & is no longer the only one permitted. Any pipeline
    // / whose dependency outlives the request reports its process here & is supervised for
    // / up to $StreamWatchTimeout minutes by the same code that already supervises FFMPEG.
    // / These are set INSIDE this branch. An earlier version set the output path outside it
    // / & therefore overwrote it on every conversion of every other type.
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
// / A function to verify files before performing operations on them.
// / Accepts the file, the output name & extension the user asked for, & three switches.
// / $clean removes stale copies, $copy stages the file in the temporary location & $skip
// / tolerates a file that was not staged.
// / Returns a verification boolean, the staged path, the original path, the original
// / extension, the output path & the output name, in that order.
// / A file this refuses is skipped rather than halted on. Every caller reports a file that
// / failed verification & moves on, so a bad name costs that file & nothing else.
function verifyFile($file, $UserFilename, $UserExtension, $clean, $copy, $skip) {
  // / Set variables.
  global $ConvertDir, $ConvertTempDir, $Allowed, $Verbose, $EnableMemoryProtection;
  $FileIsVerified = $Pathname = $OldPathname = $NewPathname = $variableIsSanitized = FALSE;
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
      list ($UserFilename, $variableIsSanitized) = sanitize($UserFilename.'.'.$UserExtension, TRUE);
      // / Define the $NewPathname if required.
      list ($NewPathname, $variableIsSanitized) = sanitize($ConvertDir.$UserFilename, FALSE);
      // / Make sure the $NewPathname is not a dangerous file.
      if (extensionIsDangerous($UserExtension) or !in_array(strtolower($UserExtension), $Allowed)) errorEntry('The file '.$file.' failed third stage validation!', 14003, TRUE);
      // / An output that IS the original is refused. Removing it as a stale output would
      // / delete the file being converted, & the conversion would then write over the only copy.
      if ($NewPathname === $OldPathname) {
        $FileIsVerified = FALSE;
        errorEntry('The output for '.$file.' would replace the original, so it was refused!', 14005, FALSE); }
      if ($FileIsVerified) {
        if ($Verbose && file_exists($NewPathname) && $clean) logEntry('Deleting stale file '.$NewPathname.'.');
        // / Remove the $NewPathname file if it already exists.
        if (file_exists($NewPathname) && $clean) @unlink($NewPathname);
        // / Check to make sure that the stale file was deleted if required or creating a new one will cause problems.
        if (file_exists($NewPathname) && $clean) errorEntry('Could not delete stale file '.$NewPathname.'!', 14004, TRUE); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $clean, $copy, $skip, $file, $variableIsSanitized);
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
  global $GuiFiles, $LanguageFiles, $LanguageStringsFile, $LanguageBaselineFile, $GuiHeaderFile, $GuiFooterFile, $GuiUI1File, $GuiUI2File, $CoreLoaded, $ConvertDir, $ConvertTempDir, $Token1, $Token2, $SesHash, $SesHash2, $SesHash3, $SesHash4, $Date, $Time, $TOSURL, $PPURL, $ShowFinePrint, $PDFWorkArr, $ArchiveArray, $DearchiveArray, $DocumentArray, $SpreadsheetArray, $ImageArray, $ImageOutputArray, $ModelArray, $DrawingArray, $VideoInputArray, $VideoOutputArray, $SubtitleInputArray, $SubtitleOutputArray, $StreamArray, $MediaInputArray, $MediaOutputArray, $PresentationInputArray, $PresentationOutputArray, $XPSInputArray, $XPSOutputArray, $ConvertGuiCounter1, $ConsolidatedLogFileName, $Alert, $Alert1, $Alert2, $Alert3, $FCPlural, $FCPlural1, $FCPlural2, $FCPlural3, $File, $Files, $FileCount, $SpinnerStyle, $SpinnerColor, $PacmanLoc, $Allowed, $AllowUserVirusScan, $AllowUserShare, $SupportedConversionTypes, $FullURL, $LanguageDir, $FaviconPath, $DropzonePath, $DropzoneStylesheetPath, $StylesheetPath, $JsLibraryPath, $JqueryPath, $GUIDirection, $SupportedFormatCount, $GUIAlignment, $HeaderDisplayed, $UIDisplayed, $FooterDisplayed, $LanguageStringsLoaded, $GUIDisplayed, $GuiResourcesDir, $GuiImageDir, $GuiCSSDir, $GuiJSDir, $StreamOutputArray, $SCADArray, $SCADOutputArray, $AllowUserSelectableColor, $AllowUserSelectableGui, $AllowUserSelectableLanguage, $SupportedColors, $SupportedGuis, $SupportedLanguages, $ColorToUse, $GuiToUse, $LanguageToUse, $GuiDir, $SVGInputArray, $SVGOutputArray, $LanguageFlagFile, $LanguageVersion, $RequiredLanguageVersion, $DefaultLanguage, $BootableIsoArray, $AllowBootableIsoImage, $EbookInputArray, $EbookOutputArray, $EnableMemoryProtection, $NoGui, $ShowFiles, $FileListOnly, $Verbose, $AllowUserURLDownload, $GuiMaxWidth, $GuiWidth, $LogoURL, $ResolvedLogoURL;
  $GUIDisplayed = FALSE;
  $guiUIFile = $GuiUI1File;
  $Files = array();
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
  $Files = getFiles($ConvertDir);
  $FileCount = count($Files);
  // / An interface that renders an empty list has either been handed the wrong directory
  // / or a directory with nothing in it, & those are different faults with different
  // / fixes. Saying which directory was read, & whether this was the framed request, is
  // / the difference between diagnosing it & guessing at it.
  if ($Verbose) logEntry('GUI '.$guiType.' file list: Directory: '.$ConvertDir.', Files: '.$FileCount.', List only: '.($FileListOnly ? 'YES' : 'NO').', Session: '.$SesHash.'/'.$SesHash2.'.');
  // / The baseline loads first. The selected pack loads over the top.
  // / A language pack is nothing but variable assignments, so loading two of them in order
  // / leaves every string set to the later value & every string the later pack does not
  // / mention set to the earlier one. That is the whole fallback, & it is per string rather
  // / than per pack.
  // / This used to run the other way round. The selected pack loaded, its version was
  // / tested, & on a mismatch the default was loaded over the top of it. That threw the
  // / user's language away wholesale to fix strings it might only have been missing one of,
  // / & it could not help at all when the pack was merely incomplete rather than
  // / mis-versioned, because nothing tested for that. A translator who had done ninety nine
  // / strings out of a hundred either lied about the version or watched their whole pack
  // / get discarded.
  // / Loading the baseline underneath means an incomplete pack shows the strings it HAS in
  // / the user's language & falls through to the baseline only for the ones it lacks. It
  // / also means no interface can print an undefined variable, whatever a pack omits.
  if ($LanguageBaselineFile !== '' && file_exists($LanguageBaselineFile)) require_once($LanguageBaselineFile);
  // / Load language specific GUI elements, if there are any.
  if (in_array($LanguageStringsFile, $LanguageFiles)) require_once($LanguageStringsFile);
  // / A version mismatch is now a WARNING & not a refusal.
  // / The version still says whether a pack was written against this core, & an
  // / administrator should know when it was not. It no longer decides whether the page can
  // / be built, because the baseline underneath has already guaranteed that every string
  // / the interface reads has a value.
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
    // / THE TEMPORARY NAME IS INDEXED. It was read unindexed inside this loop, so a multiple
    // / file upload handed copy() an array & landed nothing. A single file upload, which is
    // / what the interface sends, worked & hid it.
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
    // / Every iteration decides these for itself. They previously carried over from the
    // / previous file, so one log file in a list left every later file flagged to skip.
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
      errorEntry('Unsupported file format, '.$fileExtension.'!', 3004, FALSE);
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
  // / WHAT MAY BE DELETED IS THIS APPLICATION'S DECISION. Doing the deleting is not.
  // / $DangerousFiles is a list of formats HRConvert2 refuses to touch. No other
  // / application has that list & an engine cannot guess it, so the policy stays here.
  // / The removal itself is the Files pipeline, which is handed a directory it may operate
  // / in & refuses anything resolving outside it.
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
function archiveFiles($FilesToArchive, $UserFilename, $UserExtension) {
  // / Set variables.
  global $Verbose, $VirusScan, $Lol, $Lolol, $EnableMemoryProtection;
  $ArchiveComplete = $ArchiveErrors = $virusFound = $skip = $variableIsSanitized = FALSE;
  $fileIsVerified = $scanComplete = $sandboxIsAvailable = $anyFileSucceeded = $loopCheck = FALSE;
  $archiveToolsAreValid = $archiveWasStarted = FALSE;
  $sevenZipBinary = $rarBinary = $zipBinary = $tarBinary = $mkisofsBinary = FALSE;
  $clean = $copy = TRUE;
  $returnData = $file = $pathname = $oldPathname = $oldExtension = $NewPathname = $archiveCommand = $resolvedFilename = '';
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
    // / The first file clears any archive of this name left by an earlier run. Every file
    // / after it adds to the archive that first file created, so the clean must not run again
    // / or each file would delete the archive holding the files before it.
    $clean = ($archiveWasStarted === FALSE);
    $copy = TRUE;
    if ($Verbose) logEntry('User selected to Archive file '.$file.'.');
    // / Verify the file before performing any operations on it.
    // / verifyFile returns the name with the extension appended. Assigning that back to
    // / $UserFilename handed the next file a name that already carried one, so the second file
    // / became name.zip.zip & the third name.zip.zip.zip, each writing an archive of its own.
    // / Only the first was ever offered for download, so nine files were delivered as one.
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $NewPathname, $resolvedFilename) = verifyFile($file, $UserFilename, $UserExtension, $clean, $copy, $skip);
    if (!$fileIsVerified) {
      $ArchiveErrors = TRUE;
      errorEntry('Could not verify the input file.', 4001, FALSE);
      continue; }
    else if ($Verbose) logEntry('Verified file '.$NewPathname.'.');
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
      // / Output the filename of the converted file to the UI so it can be given to the user.
      print($resolvedFilename.$Lol);
      if ($Verbose) logEntry('Archived file '.$pathname.' to '.$NewPathname.'.'); }
    // / Record that at least one file in this request succeeded.
    // / $loopCheck is reset on every iteration, so without this the result would reflect
    // / only the LAST file rather than the whole request.
    if ($loopCheck) $anyFileSucceeded = TRUE; }
  if ($anyFileSucceeded) $ArchiveComplete = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $archiveWasStarted, $resolvedFilename, $file, $rararr, $ziparr, $tararr, $isoarr, $pathname, $oldPathname, $scanComplete, $virusFound, $returnData, $variableIsSanitized, $fileIsVerified, $oldExtension, $clean, $copy, $skip, $loopCheck, $anyFileSucceeded, $archiveCommand, $sandboxIsAvailable, $archiveToolsAreValid, $sevenZipBinary, $rarBinary, $zipBinary, $tarBinary, $mkisofsBinary, $FilesToArchive, $UserFilename, $UserExtension);
  return array($ArchiveComplete, $ArchiveErrors, $NewPathname); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert a selection of files.
// / A backgrounded successful stream has no output file when this function returns.
// / $WaitForStream tells us the conversion is still running & must not be judged by its output.
function convertFiles($ConvertSelected, $UserFilename, $UserExtension, $Height, $Width, $Rotate, $Bitrate) {
  // / Set variables.
  global $Verbose, $VirusScan, $SpreadsheetArray, $PresentationInputArray, $PresentationOutputArray, $XPSInputArray, $XPSOutputArray, $DocumentArray, $ImageArray, $ModelArray, $SCADArray, $DrawingArray, $SVGInputArray, $SVGOutputArray, $VideoInputArray, $VideoOutputArray, $SubtitleInputArray, $SubtitleOutputArray, $StreamArray, $StreamOutputArray, $MediaInputArray, $MediaOutputArray, $ArchiveArray, $EbookInputArray, $EbookOutputArray, $Lol, $WaitForStream, $EnableMemoryProtection,
    $ModelInputArray, $ModelOutputArray, $ImageInputArray, $ImageOutputArray, $SCADInputArray, $SCADOutputArray, $DrawingInputArray, $DrawingOutputArray;
  $MainConversionSuccess = $MainConversionErrors = $virusFound = $skip = $isExtensionSupported = $fileIsVerified = $variableIsSanitized = $outputExists = $conversionSuccess = $conversionErrors = $fileConversionSuccess = $anyStreamStarted = $scanComplete = FALSE;
  $clean = $copy = TRUE;
  $pathname = $oldPathname = $oldExtension = $newPathname = $file = $convertedFilename = $extension = $verifiedFilename = '';
  $arrArray = array();
  $arrKey = 0;
  // / This is for input file filtering.
  $docarray = array_merge($DocumentArray, $SpreadsheetArray, $PresentationInputArray, $XPSInputArray);
  $imgarray = $ImageInputArray;
  // / The GUI variable names are unchanged. Only what feeds them has changed.
  $modelarray = $ModelInputArray;
  $scadarray = $SCADInputArray;
  $drawingarray = $DrawingInputArray;
  $svgarray = $SVGInputArray;
  $videoarray = $VideoInputArray;
  $subtitleArray = $SubtitleInputArray;
  $audioarray = $MediaInputArray;
  $archarray = $ArchiveArray;
  $ebookarray = $EbookInputArray;
  $ebookarrayout = $EbookOutputArray;
  // / A stream family needs both halves together, because a family matches only when the
  // / input & the output extension are both in it. The two halves are kept apart everywhere
  // / else so that detecting an m3u8 does not also claim every ordinary audio & video file.
  $streamarray = array_merge($StreamArray, $StreamOutputArray);
  // / The format family map. ORDER IS SIGNIFICANT & Stream MUST remain last.
  // / Every stream output format is also an ordinary audio or video format, so an mp3 to
  // / aac conversion belongs to Audio & to Stream both. The first family to match wins,
  // / so Stream sits last & only ever claims a file no earlier family recognized.
  $arrayArray = array('Document' => $docarray, 'Image' => $imgarray, 'Model' => $modelarray, 'Scad' => $scadarray, 'SVG' => $svgarray, 'Video' => $videoarray, 'Subtitle' => $subtitleArray, 'Audio' => $audioarray, 'Archive' => $archarray, 'Drawing' => $drawingarray, 'Ebook' => $ebookarray, 'Stream' => $streamarray);
  // / This is for output file filtering.
  $docarrayout = array_merge($DocumentArray, $SpreadsheetArray, $PresentationOutputArray, $XPSOutputArray);
  $imgarrayout = $ImageOutputArray;
  $modelarrayout = $ModelOutputArray;
  $scadarrayout = $SCADOutputArray;
  $drawingarrayout = $DrawingOutputArray;
  $svgarrayout = $SVGOutputArray;
  $videoarrayout = $VideoOutputArray;
  $subtitleArrayout = $SubtitleOutputArray;
  $audioarrayout = $MediaOutputArray;
  $archarrayout = $ArchiveArray;
  $arrayArrayOut = array('Document' => $docarrayout, 'Image' => $imgarrayout, 'Model' => $modelarrayout, 'Scad' => $scadarrayout, 'SVG' => $svgarrayout, 'Video' => $videoarrayout, 'Subtitle' => $subtitleArrayout, 'Audio' => $audioarrayout, 'Archive' => $archarrayout, 'Drawing' => $drawingarrayout, 'Ebook' => $ebookarrayout, 'Stream' => $streamarray);
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
    // / Set the $clean & $copy arguments for the verifyFile() function as needed.
    if (count($ConvertSelected) > 1) $clean = FALSE;
    if (in_array($UserExtension, $archarray) or in_array($UserExtension, $docarray)) $clean = FALSE;
    if ($Verbose) logEntry('User selected to Convert file '.$file.'.');
    // / Verify the file before performing any operations on it.
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname, $verifiedFilename) = verifyFile($file, $UserFilename, $UserExtension, $clean, $copy, $skip);
    if (!$fileIsVerified) {
      $MainConversionErrors = TRUE;
      errorEntry('Could not verify the input file.', 5001, FALSE);
      continue; }
    else if ($Verbose) logEntry('Verified file '.$newPathname.'.');
    // / Scan with the default scanner if $VirusScan is set to TRUE in config.php.
    if ($VirusScan) {
      if ($Verbose) logEntry('Starting virus scan.');
      list ($scanComplete, $virusFound) = virusScan($newPathname);
      if (!$scanComplete) errorEntry('Could not perform a virus scan!', 5002, TRUE);
      if ($virusFound) errorEntry('Virus detected!', 5003, TRUE);
      if ($Verbose) logEntry('Virus scan complete.'); }
    // / Loop through the array of supported formats & only call the converter if both input & output files are supported.
    // / This loop resolves. It does not enumerate.
    // / An interface offers every family that claims the input extension, which is why an
    // / rtf file shows a Document menu & an E-Book menu. By the time a request arrives the
    // / user has already chosen an output format from one of those menus, so the input &
    // / output pair together name one family & the first match is the right match.
    foreach ($arrayArray as $arrKey => $arrArray) {
      if (!is_array($arrArray) or !is_array($arrayArrayOut[$arrKey] ?? NULL)) continue;
      if (!in_array(strtolower($oldExtension), $arrArray)) continue;
      if (!in_array(strtolower($UserExtension), $arrayArrayOut[$arrKey])) continue;
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
      // / Output the filename of the converted file to the UI so it can be given to the user.
      print($convertedFilename.$Lol);
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
  purgeSensitiveMemory($EnableMemoryProtection, $archarrayout, $audioarrayout, $docarrayout, $drawingarrayout, $imgarrayout, $modelarrayout, $scadarrayout, $subtitleArrayout, $svgarray, $svgarrayout, $videoarrayout, $file, $pathname, $oldPathname, $oldExtension, $newPathname, $docarray, $imgarray, $audioarray, $videoarray, $subtitleArray, $modelarray, $scadarray, $drawingarray, $archarray, $streamarray, $arrayArray, $arrArray, $fileIsVerified, $scanComplete, $virusFound, $variableIsSanitized, $arrKey, $clean, $copy, $skip, $isExtensionSupported, $outputExists, $conversionSuccess, $conversionErrors, $fileConversionSuccess, $anyStreamStarted, $arrayArrayOut, $convertedFilename, $extension, $ebookarray, $ebookarrayout, $verifiedFilename);
  return array($MainConversionSuccess, $MainConversionErrors); }
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
  // / A scan with every scanner needs both logs. Until v3.9.9 an all scan reset neither, so
  // / its report carried every earlier scan's lines, verdicts included.
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
// / Every scan goes through runVirusScan, the dispatcher that uploads, fetched files & the
// / OCR pipeline already use. Until v3.9.9 this application ran clamscan & ScanCore itself,
// / in about two hundred lines beside a dispatcher that did the same job.
// / THE USER IS TOLD A VERDICT & NOTHING ELSE. Clean, infected or not scanned, & which of
// / their own files. What a scanner said is counted for the administrator's log & discarded.
// / ScanCore's own report used to be written where the user downloads it, naming the
// / installation's paths, the account it ran as & the signature that matched, while the
// / ClamAV half threw its scanner's output away for exactly that reason. One rule now.
// / Each file is judged on its own. Until v3.9.9 the verdict returned was the last file's
// / alone, & a ScanCore report that already held one detection made every later file in
// / the same scan read as infected.
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
  // / A scanner's report is always delivered, even one saying the scan did not run, so the
  // / operation is complete & the errors flag is what carries a failure. A scanner that gave
  // / no file a verdict has already set its own errors flag.
  // / Both scanners are held to that in 'all' mode too. Until v4.0.0 one scanner that did not
  // / run made the whole request fatal there, error 23, so the user received no report at
  // / all where either single scanner mode would have told them which file went unscanned.
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
  // / Each scanner is run once, over the whole list.
  // / This used to sit inside a foreach over $FilesToScan while handing each scanner the
  // / ENTIRE array every time, so a scan of five files ran five scans of five files. The
  // / loop variable was never read by anything inside it, which is what hid the fault; the
  // / results were identical & only the time & the load told the truth. Twenty files meant
  // / four hundred file scans, & a virus scan is now something that draws on a resource
  // / budget & runs under a ceiling, so the cost is no longer only this session's problem.
  // /
  // / A detection from either scanner is a detection.
  // / Both scanners wrote their result into the SAME $UserVirusFound, so in 'all' mode
  // / ScanCore's answer landed on top of ClamAV's. A file ClamAV identified as infected was
  // / reported to the user as clean whenever ScanCore did not also recognise it, which is
  // / the normal case, because the two carry different definitions & that is the entire
  // / reason for offering both. Each scanner now reports into its own variable & the answer
  // / the user is given is the OR of them.
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
// / A function to build the Apache rules that refuse every data location.
// / Accepts nothing. Returns the rules & the roots they could not refuse, in that order.
// /
// / A DATA LOCATION IS NEVER SERVED, WHEREVER IT IS. Its Sessions, Cache & Logs folders belong
// / to the web server account, which is the account that serves files, so a data location
// / inside the document root hands them to anybody who guesses a name. One outside it is still
// / granted wherever the host grants a folder above it, as Debian grants all of /var/www, & is
// / one Alias or one wider document root away from being served. So each one is refused
// / outright, as it is written & as it resolves, & no .htaccess inside it is read.
// / A location that holds the hosted location or its DATA tree is not refused, because refusing
// / it would refuse HRConvert2 as well. It is returned, so the caller can say so. The installation
// / itself is inside the data location since v4.0.1, & is refused with it, because nothing in it
// / is ever meant to be fetched.
function apacheDataLocationRules() {
  // / Set variables.
  global $HostedLoc, $ConvertTemp, $EnableMemoryProtection;
  $LocationRules = '';
  $UnrefusedRoots = array();
  $dataRoot = $rootForm = '';
  foreach (dataLocationRoots() as $dataRoot) {
    if (pathHoldsPath($dataRoot, $HostedLoc) or pathHoldsPath($dataRoot, $ConvertTemp)) $UnrefusedRoots[] = $dataRoot;
    else foreach (pathForms($dataRoot) as $rootForm) $LocationRules = $LocationRules.'<Directory '.escapeApacheConfigPath($rootForm).'>'.PHP_EOL
      .'  Require all denied'.PHP_EOL
      .'  AllowOverride None'.PHP_EOL
      .'  Options None'.PHP_EOL
      .'</Directory>'.PHP_EOL; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / Neither $LocationRules nor $UnrefusedRoots is purged, because both are return values.
  purgeSensitiveMemory($EnableMemoryProtection, $dataRoot, $rootForm);
  return array($LocationRules, $UnrefusedRoots); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether the web server is running.
// / Accepts the Apache control program. Returns running, stopped or unknown.
// /
// / apachectl -k graceful STARTS A SERVER THAT IS NOT RUNNING. Measured; it says httpd not
// / running, trying to start, & starts it. Until v4.0.1 -fp reported that as a reload, & an
// / unattended Environment Manager pass permitted to rewrite configuration would have started
// / a web server somebody had stopped, after saying it starts nothing. So a reload is asked for
// / only while the server is running, & a stopped one reads the configuration when it starts.
// / The server names its own pid file in its run configuration. The process that file names
// / must still be a web server, because a pid file outlives a crash & its number is used again.
function apacheIsRunning($controlBinary) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ServerState = 'unknown';
  $commandOutput = $pathMatch = array();
  $commandExitCode = 1;
  $processId = 0;
  $outputLine = $serverRoot = $pidPath = $pidFolder = $pidText = $processName = $readNotice = '';
  $pidWasRead = FALSE;
  exec(escapeshellarg((string)$controlBinary).' -t -D DUMP_RUN_CFG 2>/dev/null', $commandOutput, $commandExitCode);
  foreach ($commandOutput as $outputLine) {
    if (preg_match('/^ServerRoot: "(.+)"$/', trim((string)$outputLine), $pathMatch) === 1) $serverRoot = $pathMatch[1];
    if (preg_match('/^PidFile: "(.+)"$/', trim((string)$outputLine), $pathMatch) === 1) $pidPath = $pathMatch[1]; }
  // / A relative pid file is relative to the server root.
  if ($pidPath !== '' && $pidPath[0] !== DIRECTORY_SEPARATOR) $pidPath = rtrim($serverRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$pidPath;
  // / /var/run leads to /run almost everywhere & the kernel reads nothing through a link, so the
  // / folder is resolved first. A server that is not running leaves no pid file behind.
  if ($commandExitCode === 0 && $pidPath !== '') {
    $pidFolder = (string)@realpath(dirname($pidPath));
    if ($pidFolder === '') $ServerState = 'stopped';
    else {
      list ($pidWasRead, $pidText, $readNotice) = readFileWithoutFollowing($pidFolder.DIRECTORY_SEPARATOR.basename($pidPath), 64);
      if (!$pidWasRead && !is_array(@lstat($pidFolder.DIRECTORY_SEPARATOR.basename($pidPath)))) $ServerState = 'stopped';
      else if ($pidWasRead) {
        $processId = (int)trim($pidText);
        if ($processId > 1) $processName = trim((string)@file_get_contents('/proc/'.$processId.'/comm'));
        $ServerState = (preg_match('/^(apache2|httpd)/', $processName) === 1) ? 'running' : 'stopped'; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $commandOutput, $pathMatch, $commandExitCode, $processId, $outputLine, $serverRoot, $pidPath, $pidFolder, $pidText, $processName, $readNotice, $pidWasRead, $controlBinary);
  return $ServerState; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write & activate the Apache configuration HRConvert2 needs.
// / Accepts a boolean permitting repair.
// / Returns a validity boolean & the number of locations written, in that order.
// /
// / THIS IS WHERE THE DATA RULES BELONG. THE .htaccess IS THE FALLBACK, NOT THE ANSWER.
// / A .htaccess is read only where AllowOverride permits it, & Debian, Ubuntu & the standard
// / php:*-apache container all ship AllowOverride None for /var/www. It is also constrained;
// / a directive the override level does not allow is not ignored, it is a hard 500 for the
// / whole tree. Server configuration has neither problem. Nothing is silently dropped &
// / nothing is refused, so the full rule set can live here & be relied upon.
// /
// / Every path in this block is read from this installation, never typed.
// / The same reasoning as generateListenerService(). An administrator who moves the
// / installation moves this with it, & a block naming a directory that no longer exists
// / protects nothing while looking like it does.
// /
// / The data locations are refused in the same file. apacheDataLocationRules() says why.
// /
// / The configuration is tested before it is allowed to take effect.
// / This application has already shipped one protection file that returned 500 for an entire
// / directory tree, so writing web server configuration & walking away is not something it
// / gets to do twice. The file is written, apachectl configtest is run, & a configuration
// / that does not pass is WITHDRAWN before anything reloads. What was there before is put back
// / exactly, & the test is run again to prove it. Until v4.0.1 a file this replaced was deleted
// / rather than put back, & a link an administrator had made was removed with it.
// /
// / Root reads & writes this file through the kernel, as it does every other file it writes.
// / The write is atomic, so the web server never starts against half a file, & nothing is
// / written through a link at the name. A name the kernel cannot read as a plain file is left
// / as it is & reported, because a file that was never examined cannot be put back.
// / Only a running server is reloaded. apacheIsRunning() says why.
function verifyApacheConfiguration($mayRepair) {
  // / Set variables.
  global $ConvertTemp, $InstLoc, $HostedLoc, $RunningAsRoot, $Lol, $Verbose, $EnableMemoryProtection, $DirSep;
  $ConfigurationIsValid = TRUE;
  $ConfigurationsWritten = 0;
  $dropInContents = $dropInPath = $existingContents = $candidateDir = $enabledDir = $enabledPath = '';
  $controlBinary = $configTestOutput = $reloadOutput = $locationRules = $unrefusedRoot = $readNotice = $writeNotice = $restoreNotice = $serverState = '';
  $candidateDirectories = $unrefusedRoots = $previousContents = array();
  $commandOutput = array();
  $commandExitCode = 0;
  $configTestPassed = $baselineTestPassed = $linkWasMade = $writtenThisRun = $moduleIsLoaded = FALSE;
  $dropInExists = $existingWasRead = $dropInWasWritten = $restoreWorked = FALSE;
  $moduleStatus = '';
  if ((string)$ConvertTemp === '') {
    $ConfigurationIsValid = FALSE;
    warningEntry('The DATA location is not set, so the Apache configuration could not be built.'); }
  else {
    list ($locationRules, $unrefusedRoots) = apacheDataLocationRules();
    $dropInContents = '# / Written by HRConvert2. Do not edit by hand.'.PHP_EOL
      .'# / HRCONVERT2-POLICY-MARKER'.PHP_EOL
      .'# / Re-run  sudo php convertCore.php -fp  to rewrite this file.'.PHP_EOL
      .'# / Paths are read from this installation, so moving it moves these rules with it.'.PHP_EOL
      .PHP_EOL
      .'# / THE HOSTED LOCATION RUNS ONE PHP FILE, THE LOADER. HRConvert2 is installed outside the'.PHP_EOL
      .'# / web root, & everything else the web server serves from here is a static file, so a'.PHP_EOL
      .'# / PHP file anybody leaves here is refused rather than run.'.PHP_EOL
      .'<Directory '.escapeApacheConfigPath($HostedLoc).'>'.PHP_EOL
      .'  Options -Indexes'.PHP_EOL
      .'  <FilesMatch "\.(?i:php[0-9]?|phtml|phps|phar)$">'.PHP_EOL
      .'    Require all denied'.PHP_EOL
      .'  </FilesMatch>'.PHP_EOL
      .'  <Files "convertCore.php">'.PHP_EOL
      .'    Require all granted'.PHP_EOL
      .'  </Files>'.PHP_EOL
      .'</Directory>'.PHP_EOL
      .PHP_EOL
      .'# / THIS TREE HOLDS USER SUPPLIED BYTES & IS SERVED DIRECTLY BY THE WEB SERVER.'.PHP_EOL
      .'# / Every file a user uploads or converts is staged here so a browser can fetch it,'.PHP_EOL
      .'# / which means the server hands out attacker chosen content from this origin. For any'.PHP_EOL
      .'# / format a browser treats as ACTIVE that content is code; an SVG is served as'.PHP_EOL
      .'# / image/svg+xml & a <script> inside it executes.'.PHP_EOL
      .'# /'.PHP_EOL
      .'# / Content-Disposition closes that by handing the file over as a download, so it is'.PHP_EOL
      .'# / never rendered as a document. The CSP closes it a second time & independently.'.PHP_EOL
      .'# / X-Content-Type-Options does NOT close it; the type here is already correct, so'.PHP_EOL
      .'# / there is nothing to sniff. It is set for the neighbouring ambiguous type case only.'.PHP_EOL
      .'<Directory '.escapeApacheConfigPath($ConvertTemp).'>'.PHP_EOL
      .'  # / A .htaccess in this tree cannot loosen what is set here.'.PHP_EOL
      .'  AllowOverride None'.PHP_EOL
      .'  Require all granted'.PHP_EOL
      .'  # / No listing, & nothing here is ever a program.'.PHP_EOL
      .'  # / SymLinksIfOwnerMatch rather than FollowSymLinks. A link is followed only when'.PHP_EOL
      .'  # / the link & its target have the same owner, so a link written by an uploader'.PHP_EOL
      .'  # / cannot reach a file owned by somebody else.'.PHP_EOL
      .'  # / Nothing here creates a link into a session today. This is set now because the'.PHP_EOL
      .'  # / default is FollowSymLinks on most builds, & the first time somebody serves a'.PHP_EOL
      .'  # / stored file through a link is not the moment to discover that.'.PHP_EOL
      .'  Options -Indexes -ExecCGI -FollowSymLinks +SymLinksIfOwnerMatch'.PHP_EOL
      .'  # / index.html is the ONLY index. Without this, a request for the directory'.PHP_EOL
      .'  # / falls to whatever the server was configured to prefer, which on a default'.PHP_EOL
      .'  # / installation is index.php first. That name is refused by this application'.PHP_EOL
      .'  # / & cannot be staged, so the effect today is a 403 rather than a listing.'.PHP_EOL
      .'  # / Naming it explicitly serves the protection page instead of the 403, & stops'.PHP_EOL
      .'  # / this depending on what a server happens to prefer.'.PHP_EOL
      .'  # / A directive whose module is not loaded does not parse, & then this whole file is'.PHP_EOL
      .'  # / withdrawn, Header rules included. So each one that needs a module is guarded by it.'.PHP_EOL
      .'  <IfModule mod_dir.c>'.PHP_EOL
      .'    DirectoryIndex index.html'.PHP_EOL
      .'  </IfModule>'.PHP_EOL
      .'  <IfModule mod_headers.c>'.PHP_EOL
      .'    # / index.html is the document root protection page & is the one file here meant'.PHP_EOL
      .'    # / to render. It cannot be user supplied; index.html is a literal entry in'.PHP_EOL
      .'    # / $DangerousFiles & the core refuses to stage a file by that name.'.PHP_EOL
      .'    <FilesMatch "^(?!index\.html$).*$">'.PHP_EOL
      .'      Header always set Content-Disposition "attachment"'.PHP_EOL
      .'      Header always set X-Content-Type-Options "nosniff"'.PHP_EOL
      .'      Header always set Content-Security-Policy "default-src \'none\'; sandbox"'.PHP_EOL
      .'      Header always set Referrer-Policy "no-referrer"'.PHP_EOL
      .'    </FilesMatch>'.PHP_EOL
      .'  </IfModule>'.PHP_EOL
      .'  # / php_flag is legal here because server configuration is not subject to an'.PHP_EOL
      .'  # / override level. It must NEVER be written into a .htaccess, where it returns 500'.PHP_EOL
      .'  # / wherever AllowOverride does not grant Options.'.PHP_EOL
      .'  <IfModule mod_php.c>'.PHP_EOL
      .'    php_flag engine off'.PHP_EOL
      .'  </IfModule>'.PHP_EOL
      .'  <IfModule mod_mime.c>'.PHP_EOL
      .'    RemoveHandler .php .phtml .phps .php3 .php4 .php5 .php7 .php8 .phar .cgi .pl .py .sh'.PHP_EOL
      .'  </IfModule>'.PHP_EOL
      .'</Directory>'.PHP_EOL
      .($locationRules === '' ? '' : PHP_EOL
      .'# / THE DATA LOCATION IS NEVER SERVED, WHEREVER IT IS. Its sessions, caches & logs belong'.PHP_EOL
      .'# / to the account that serves files, so each data location is refused outright.'.PHP_EOL
      .$locationRules);
    // / Every layout this host might be running. Debian & Ubuntu separate available from
    // / enabled & need the link made; every other layout includes its directory outright.
    if (is_dir('/etc/apache2/conf-available')) $candidateDirectories[] = '/etc/apache2/conf-available';
    else if (is_dir('/etc/apache2/conf.d')) $candidateDirectories[] = '/etc/apache2/conf.d';
    if (is_dir('/etc/httpd/conf.d')) $candidateDirectories[] = '/etc/httpd/conf.d';
    if (empty($candidateDirectories)) {
      // / Not a failure. An installation may be behind a web server this application does
      // / not configure, & the exposure check is what establishes whether that matters.
      warningEntry('No Apache configuration directory was found, so the DATA rules were not written there. The DATA/.htaccess is the fallback & the exposure check reports whether it is in force.');
      print('  '.str_pad('Skipped', 12).'No Apache configuration directory on this host.'.$Lol); }
    else {
      // / A data location that holds HRConvert2 cannot be refused without refusing HRConvert2.
      foreach ($unrefusedRoots as $unrefusedRoot) {
        print('  '.str_pad('Skipped', 12).$unrefusedRoot.' holds what the web server serves, so it is not refused.'.$Lol);
        warningEntry('The data location '.$unrefusedRoot.' holds the hosted location or its DATA tree, so the Apache configuration cannot refuse it without refusing HRConvert2 as well. Move the data location outside the web root.'); }
      // / The rules are Header directives, so the module they need is confirmed & enabled
      // / before the configuration that depends on it is written. Enabling it here means the
      // / single reload at the end of this function covers both.
      list ($moduleIsLoaded, $moduleStatus) = verifyApacheModule('headers_module', $mayRepair);
      print('  '.str_pad(($moduleStatus === 'enabled' ? 'Enabled' : ($moduleStatus === 'ok' ? 'Present' : 'MISSING')), 12).'headers_module'.($moduleStatus === 'missing' ? ', the DATA rules will be skipped without it' : '').$Lol);
      // / Establish whether this host's configuration parsed BEFORE we touched it, so a
      // / pre-existing fault is never mistaken for one of ours.
      $controlBinary = locateDependency('apachectl');
      if ($controlBinary === '') $controlBinary = locateDependency('apache2ctl');
      if ($controlBinary !== '') {
        exec(escapeshellarg($controlBinary).' configtest 2>&1', $commandOutput, $commandExitCode);
        $baselineTestPassed = ($commandExitCode === 0);
        $commandOutput = array(); }
      foreach ($candidateDirectories as $candidateDir) {
        $dropInPath = $candidateDir.$DirSep.'hrconvert2.conf';
        clearstatcache(TRUE, $dropInPath);
        $dropInExists = is_array(@lstat($dropInPath));
        $existingWasRead = FALSE;
        $existingContents = $readNotice = '';
        if ($dropInExists) list ($existingWasRead, $existingContents, $readNotice) = readFileWithoutFollowing($dropInPath, 1048576);
        // / Nothing HRConvert2 writes comes near a megabyte, & a file cut short cannot be put back.
        if ($existingWasRead && strlen($existingContents) >= 1048576) {
          $existingWasRead = FALSE;
          $readNotice = $dropInPath.' is far larger than anything HRConvert2 writes.'; }
        if ($dropInExists && !$existingWasRead) {
          $ConfigurationIsValid = FALSE;
          print('  '.str_pad('FAILED', 12).$dropInPath.' could not be examined & was left as it is.'.$Lol);
          warningEntry('The Apache configuration at '.$dropInPath.' could not be examined, so it was left as it is. Remove it & run the -fp argument again. '.$readNotice); }
        else if ($existingContents === $dropInContents) print('  '.str_pad('Unchanged', 12).$dropInPath.$Lol);
        else if (!$mayRepair or !$RunningAsRoot) {
          $ConfigurationIsValid = FALSE;
          print('  '.str_pad(($dropInExists ? 'Differs' : 'Missing'), 12).$dropInPath.$Lol); }
        else {
          list ($dropInWasWritten, $writeNotice) = writeFileWithoutFollowing($dropInPath, $dropInContents, 0644);
          if (!$dropInWasWritten) {
            $ConfigurationIsValid = FALSE;
            print('  '.str_pad('FAILED', 12).$dropInPath.$Lol);
            warningEntry('The Apache configuration could not be written. '.$writeNotice); }
          else {
            // / What was there is kept, so a configuration that fails its test can be put back exactly.
            $previousContents[$dropInPath] = ($dropInExists ? $existingContents : NULL);
            $ConfigurationsWritten++;
            $writtenThisRun = TRUE;
            print('  '.str_pad(($dropInExists ? 'Updated' : 'Written'), 12).$dropInPath.$Lol); } }
        // / Debian keeps enabled configuration as links. Made directly rather than through
        // / a2enconf, because a2enconf is a Debian script & this has to work without it.
        $enabledDir = '/etc/apache2/conf-enabled';
        if ($candidateDir === '/etc/apache2/conf-available' && is_dir($enabledDir)) {
          $enabledPath = $enabledDir.$DirSep.'hrconvert2.conf';
          if (!file_exists($enabledPath) && $mayRepair && $RunningAsRoot) {
            $linkWasMade = @symlink('../conf-available/hrconvert2.conf', $enabledPath);
            if ($linkWasMade) print('  '.str_pad('Enabled', 12).$enabledPath.$Lol);
            else {
              $ConfigurationIsValid = FALSE;
              print('  '.str_pad('FAILED', 12).$enabledPath.$Lol);
              warningEntry('The HRConvert2 Apache configuration was written but could not be enabled at '.$enabledPath.'.'); } }
          else if (file_exists($enabledPath)) print('  '.str_pad('Enabled', 12).$enabledPath.$Lol); } }
      // / Prove it parses before anything reloads, & undo it if it does not.
      // / A configuration that does not parse takes the whole server down on the next
      // / restart, which would be a far worse outcome than the exposure this closes.
      if ($writtenThisRun or $moduleStatus === 'enabled') {
        $controlBinary = locateDependency('apachectl');
        if ($controlBinary === '') $controlBinary = locateDependency('apache2ctl');
        if ($controlBinary === '') {
          warningEntry('The Apache configuration was written but apachectl could not be found, so it was not tested. Run a configuration test & reload the web server by hand.');
          print('  '.str_pad('Note', 12).'apachectl not found. Test & reload the web server by hand.'.$Lol); }
        else {
          exec(escapeshellarg($controlBinary).' configtest 2>&1', $commandOutput, $commandExitCode);
          $configTestOutput = implode(' ', $commandOutput);
          $configTestPassed = ($commandExitCode === 0);
          // / Only withdraw for a fault that is ours.
          // / A host whose configuration ALREADY did not parse before this ran would
          // / otherwise have a perfectly good file removed & be told, wrongly, that
          // / HRConvert2 wrote something invalid. The baseline was captured before the write
          // / for exactly this reason; a test that was already failing stays the
          // / administrator's problem & is reported as theirs.
          if (!$configTestPassed && !$baselineTestPassed) {
            warningEntry('The Apache configuration on this host did not parse BEFORE HRConvert2 wrote anything, so its file was kept & the pre-existing fault was left for the administrator. apachectl reported: '.trim($configTestOutput));
            print('  '.str_pad('Note', 12).'This host\'s Apache configuration already did not parse before this ran.'.$Lol);
            print('  '.str_pad('', 12).'The HRConvert2 file was kept. Fix the pre-existing fault & reload.'.$Lol); }
          else if (!$configTestPassed) {
            // / Put back exactly what was found. A file this run created is removed, a file it
            // / replaced is written back as it was, & the link goes only when this run made it.
            foreach ($previousContents as $dropInPath => $existingContents) {
              if ($existingContents === NULL) list ($restoreWorked, $restoreNotice) = removeFileWithoutFollowing($dropInPath);
              else list ($restoreWorked, $restoreNotice) = writeFileWithoutFollowing($dropInPath, $existingContents, 0644);
              if (!$restoreWorked) warningEntry('The Apache configuration at '.$dropInPath.' could not be put back as it was. '.$restoreNotice); }
            if ($linkWasMade) {
              list ($restoreWorked, $restoreNotice) = removeFileWithoutFollowing('/etc/apache2/conf-enabled/hrconvert2.conf');
              if (!$restoreWorked) warningEntry('The link that enabled the HRConvert2 Apache configuration could not be removed. '.$restoreNotice); }
            $ConfigurationIsValid = FALSE;
            $ConfigurationsWritten = 0;
            print('  '.str_pad('WITHDRAWN', 12).'The configuration did not pass a syntax test, & what was there before is back.'.$Lol);
            print('  '.str_pad('', 12).trim($configTestOutput).$Lol);
            errorEntry('The Apache configuration HRConvert2 wrote did not pass apachectl configtest & was withdrawn. The test reported: '.trim($configTestOutput), 504, FALSE);
            // / The test passed before anything was written, so it must pass again now. It is run
            // / again to prove that rather than to assume it.
            $commandOutput = array();
            exec(escapeshellarg($controlBinary).' configtest 2>&1', $commandOutput, $commandExitCode);
            if ($commandExitCode === 0) print('  '.str_pad('Tested', 12).'The configuration parses as it did before.'.$Lol);
            else {
              warningEntry('The Apache configuration still does not parse after HRConvert2 withdrew its file. Fix what apachectl configtest reports before the web server next starts. It reported: '.trim(implode(' ', $commandOutput)));
              print('  '.str_pad('Note', 12).'The configuration still does not parse. See the log.'.$Lol); } }
          else {
            print('  '.str_pad('Tested', 12).'The configuration parses.'.$Lol);
            // / A graceful reload finishes the requests already in flight & then picks up
            // / the new configuration. It cannot drop a connection & it cannot fail closed,
            // / because the test above already proved the configuration parses.
            $serverState = apacheIsRunning($controlBinary);
            if ($serverState === 'stopped') print('  '.str_pad('Not running', 12).'The web server was left stopped. It reads this configuration when it starts.'.$Lol);
            else if ($serverState !== 'running') {
              warningEntry('The Apache configuration was written & tested, but whether the web server is running could not be told, so it was not reloaded. Reload it by hand for the DATA rules to take effect.');
              print('  '.str_pad('Note', 12).'Could not tell whether the web server is running. Reload it by hand.'.$Lol); }
            else {
              $commandOutput = array();
              exec(escapeshellarg($controlBinary).' -k graceful 2>&1', $commandOutput, $commandExitCode);
              $reloadOutput = implode(' ', $commandOutput);
              if ($commandExitCode === 0) print('  '.str_pad('Reloaded', 12).'The web server picked up the new configuration.'.$Lol);
              else {
                warningEntry('The Apache configuration was written & tested but the web server could not be reloaded. Reload it by hand for the DATA rules to take effect.');
                print('  '.str_pad('Note', 12).'Could not reload the web server. Reload it by hand.'.$Lol); } } } } }
      if ($ConfigurationsWritten > 0) logEntry('Wrote Apache configuration to '.$ConfigurationsWritten.' location(s) for '.$ConvertTemp.'.'); } }
  if ($Verbose) logEntry('Apache Configuration: '.count($candidateDirectories).' location(s) examined, '.$ConfigurationsWritten.' written.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dropInContents, $dropInPath, $existingContents, $candidateDir, $enabledDir, $enabledPath, $controlBinary, $configTestOutput, $reloadOutput, $locationRules, $unrefusedRoot, $readNotice, $writeNotice, $restoreNotice, $serverState, $candidateDirectories, $unrefusedRoots, $previousContents, $commandOutput, $commandExitCode, $configTestPassed, $baselineTestPassed, $linkWasMade, $writtenThisRun, $moduleIsLoaded, $dropInExists, $existingWasRead, $dropInWasWritten, $restoreWorked, $moduleStatus, $mayRepair);
  return array($ConfigurationIsValid, $ConfigurationsWritten); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to correct ownership & permissions on every managed path.
// / Accepts no arguments & must be run as root.
// / Returns a success boolean & the number of paths corrected, in that order.
function fixManagedPermissions() {
  // / Set variables.
  global $ApacheUser, $Lol, $RunningAsRoot, $EnablePerConversionLimits, $RequiredSetupCoreVersion, $EnableMemoryProtection, $DirSep, $DeleteBuildEnvironment, $DeleteDevelopmentDocumentation, $InstLoc, $EngineRepairIsUnattended, $EnvironmentManagerMayRewriteConfigs, $HostedLoc, $ApplicationLoc, $ConvertTemp;
  $buildEnvCleaned = $buildEnvDeleted = $devDocsDeleted = $codeIsTrustedNow = $repairIsUnattended = $configsMayChange = $scriptIsValid = FALSE;
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
    // / AN UNATTENDED REPAIR IS THE ENVIRONMENT MANAGER'S, & nobody is watching it. The Engine says
    // / so through $EngineRepairIsUnattended. Such a repair starts & enables no service, because an
    // / administrator who stopped one has a reason, & it rewrites the host configuration files this
    // / application owns only when --Environment Manager May Rewrite Configs-- allows. Those are the
    // / PHP drop in, the Apache configuration, the sandbox kernel settings & the three policies.
    // / Everything else is exactly what -fp does, so there is still one definition of a repair.
    $repairIsUnattended = (isset($EngineRepairIsUnattended) && $EngineRepairIsUnattended === TRUE);
    $configsMayChange = (!$repairIsUnattended or (isset($EnvironmentManagerMayRewriteConfigs) && $EnvironmentManagerMayRewriteConfigs === TRUE));
    if ($repairIsUnattended) print($Lol.'Unattended repair. No service is started or enabled.'.($configsMayChange ? '' : ' Host configuration files are checked & left as they are.').$Lol);
    // / SETUP CORE IS LOADED BEFORE ANYTHING ASKS IT FOR SOMETHING. Until v4.0.1 it was loaded
    // / sixty lines further down, so -fp run on its own never corrected a single location. It
    // / logged that Setup Core was unavailable & printed Corrected 1 path(s), which was the
    // / secret, & every location stayed as it was. Only --setup, which loads it first, worked.
    list ($setupIsAvailable, $setupVersion) = verifyCoreComponent('Setup Core', 'Engine'.$DirSep.'Cores'.$DirSep.'setupCore.php', 'SetupCoreVersion', $RequiredSetupCoreVersion);
    // / Which locations this application keeps & what each one is are in its contract, as
    // / $EngineManagedLocations. What a role means for an owner & a mode is Setup Core's.
    // / The install secret is not one of them. Root's boot has already read it & rewritten it
    // / without following anything, before this function was ever called.
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
    // / Whether root may run the installation now, read exactly as every command run as root
    // / reads it before anything is loaded. A tree that still fails is named here, once.
    list ($codeIsTrustedNow, $codeTrustNotice) = treeIsTrustedByRoot($InstLoc, array($InstLoc.$DirSep.'DATA'));
    if ($codeIsTrustedNow) print('  The installation is root\'s, & root may run it.'.$Lol);
    else {
      warningEntry('-fp did not leave the installation one root may run. '.$codeTrustNotice);
      print('  Root may still not run the installation. '.$codeTrustNotice.$Lol); }
    // / The build environment & the development documentation go here, when config.php asks
    // / for them to go. Every web request removed them until v4.0.1, & now only root can,
    // / because the code is root's. Straight after the code was given to root is the moment.
    if ($DeleteBuildEnvironment or $DeleteDevelopmentDocumentation) {
      print($Lol.'Build environment'.$Lol);
      list ($buildEnvCleaned, $buildEnvDeleted, $devDocsDeleted) = cleanBuildEnvironment();
      if (!$buildEnvCleaned) errorEntry('Could not clean the build environment!', 26, FALSE);
      if ($DeleteBuildEnvironment) print('  '.str_pad('Build & Dockerfile', 28).($buildEnvDeleted ? 'NOT PRESENT' : 'FAILED, see the log').$Lol);
      if ($DeleteDevelopmentDocumentation) print('  '.str_pad('README.md & CHANGELOG.txt', 28).($devDocsDeleted ? 'NOT PRESENT' : 'FAILED, see the log').$Lol); }
    // / The bootstrap script, which the Engine pins exactly. One from another release is disabled
    // / where it stands & one that matches again is enabled. It lives inside the installation, so
    // / this waits until root may run the installation, & it is not host configuration, so an
    // / unattended repair does it too. Until v4.0.1 the listener did this as the web server
    // / account, which can no longer write to the installation.
    print($Lol.'Bootstrap script'.$Lol);
    if (!$codeIsTrustedNow) print('  Skipped     Root may not change the installation yet. See the lines above.'.$Lol);
    else if (!function_exists('repairBootstrapScript')) print('  Skipped     The Engine is unavailable, so the bootstrap script was not checked.'.$Lol);
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
    else if (!function_exists('publishHostedLocation')) print('  Skipped     The Setup Core is unavailable, so the hosted location was not checked.'.$Lol);
    else if ((string)@realpath($InstLoc) !== (string)@realpath($ApplicationLoc)) print('  '.str_pad('Not yet', 12).$InstLoc.' is not installed. -fp installs it into '.$ApplicationLoc.' as it ends.'.$Lol);
    else {
      list ($hostedStatus, $hostedDetail, $hostedWritten, $hostedRemoved) = publishHostedLocation($HostedLoc, $InstLoc, TRUE);
      print('  '.str_pad(($hostedStatus === 'repaired' ? 'Published' : $hostedStatus), 12).$hostedDetail.$Lol);
      // / DATA is the one name in the hosted location the web server account owns. A hosted location
      // / made just now has none, & every role was given before it existed, so DATA is made & given
      // / its role here, before anything else writes into it as root.
      if (!is_dir($ConvertTemp) && is_dir($HostedLoc) && function_exists('applyWebRole') && createHeldFolder($ConvertTemp) && applyWebRole($ConvertTemp, $ApacheUser)) print('  '.str_pad('Created', 12).$ConvertTemp.', the web server account\'s.'.$Lol); }
    // / Install or repair every policy file while we are still root. A conversion pipeline
    // / reports a broken policy on every request & can never fix one, because rewriting a
    // / system policy from a web request is not something this application should ever do.
    print($Lol.'PHP configuration'.$Lol);
    list ($phpConfigIsValid, $phpConfigsWritten) = verifyPhpConfiguration($configsMayChange);
    // / The DATA rules belong in server configuration rather than in a .htaccess, so they
    // / are written, tested & activated here. The .htaccess further down stays as the
    // / fallback for an installation this cannot configure.
    print($Lol.'Apache configuration'.$Lol);
    list ($apacheConfigIsValid, $apacheConfigsWritten) = verifyApacheConfiguration($configsMayChange);
    print($Lol.'Sandbox kernel'.$Lol);
    list ($kernelIsReady, $kernelFindings) = verifySandboxKernel($configsMayChange);
    showEnvironmentFindings($kernelFindings, FALSE);
    // / Policy files. A count when every one is in place, & a named line when one is not.
    // / Half of these status words read like a fault & are not one, so a line that does
    // / appear carries the explanation with it.
    $policyChecks = array('Sandbox AppArmor', 'ImageMagick', 'OpenSCAD AppArmor');
    foreach ($policyChecks as $policyName) {
      $policiesTotal++;
      if ($policyName === 'Sandbox AppArmor') list ($policyIsValid, $policyStatus) = verifySandboxPolicy($configsMayChange);
      else if ($policyName === 'ImageMagick') list ($policyIsValid, $policyStatus) = verifyImageMagickPolicy($configsMayChange);
      else list ($policyIsValid, $policyStatus) = verifyOpenScadPolicy($configsMayChange);
      if ($policyIsValid) $policiesOk++;
      else array_push($policyLines, '  '.str_pad($policyName, 22).str_pad(policyDisplayStatus($policyStatus), 14).describePolicyStatus($policyName, $policyStatus)); }
    print($Lol.'Policy files     '.$policiesOk.' of '.$policiesTotal.' OK'.$Lol);
    foreach ($policyLines as $policyLine) print($policyLine.$Lol);
    // / The DATA tree protection file. This is the only policy this application maintains
    // / that governs its OWN web root rather than a converter, & it is the only one whose
    // / installation proves nothing on its own, so it is verified separately below.
    list ($policyIsValid, $policyStatus) = verifyDataProtectionPolicy(TRUE);
    print('  '.str_pad('DATA Directory', 22).str_pad(policyDisplayStatus($policyStatus), 14).describePolicyStatus('DATA Directory', $policyStatus).$Lol);
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
    // / Prove the repairs worked. Writing an AppArmor profile & never re-testing the
    // / sandbox is how an operator ends up with a green permissions run & a server that
    // / refuses every conversion.
    print($Lol.'Verifying the operating environment.'.$Lol);
    list ($environmentIsReady, $environmentFindings) = validateOperatingEnvironment();
    showEnvironmentFindings($environmentFindings, TRUE);
    if (!$environmentIsReady) print($Lol.'The sandbox is still unavailable. A profile written now may need the AppArmor'.$Lol.'service reloaded, or a reboot, before the kernel honours it.'.$Lol);
    // / Writing the protection file above proved nothing, & the check that proves it is
    // / part of the environment report printed directly above this.
    // / A .htaccess is read only where AllowOverride is enabled, & where it is not, Apache
    // / ignores it in complete silence, so the tree is asked over HTTP with a canary rather
    // / than believed. The status is read back here only to decide whether an operator needs
    // / the longer explanation, which does not belong in a one line finding.
    $exposureStatus = environmentFindingStatus($environmentFindings, 'DATA exposure');
    if ($exposureStatus === 'BROKEN') {
      print($Lol.'  THE DATA DIRECTORY IS RETURNING A SERVER ERROR.'.$Lol);
      print('  No download & no share link works right now. This is an outage, not exposure.'.$Lol);
      print('  The usual cause is a directive in DATA/.htaccess that this directory\'s'.$Lol);
      print('  AllowOverride level does not permit. Apache refuses such a request rather than'.$Lol);
      print('  ignoring the directive, & an <IfModule> guard does not prevent it.'.$Lol);
      print('  The web server error log names the offending line. Deleting DATA/.htaccess'.$Lol);
      print('  restores service immediately; put the rules in the server configuration instead.'.$Lol); }
    else if ($exposureStatus === 'EXPOSED') {
      print($Lol.'  THE DATA DIRECTORY IS EXPOSED.'.$Lol);
      print('  A file a user uploads is served back as a document rather than as a download,'.$Lol);
      print('  so an uploaded SVG runs its own script in this origin.'.$Lol);
      print('  The .htaccess written above is being ignored. Apache reads one only where'.$Lol);
      print('  AllowOverride is enabled for that directory.'.$Lol);
      print('  Put the rules in the server configuration instead. See'.$Lol);
      print('  Documentation/ABOUT_DATA_DIRECTORY_PROTECTION.txt.'.$Lol); }
    else if ($exposureStatus === 'UNREACHABLE' or $exposureStatus === 'UNVERIFIED') {
      print($Lol.'  THE EXPOSURE OF THIS INSTALLATION WAS NOT ESTABLISHED.'.$Lol);
      print('  This is not a pass. Open the DATA URL of any uploaded file in a browser &'.$Lol);
      print('  confirm it downloads rather than renders.'.$Lol); }
    // / A repair that leaves an installation root may not run has not finished its job, so it
    // / does not report success. Everything else above it still ran.
    $PermissionsWereFixed = $codeIsTrustedNow;
    logEntry('Permissions were corrected on '.$PathsCorrected.' managed path(s).'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $hostedStatus, $hostedDetail, $hostedWritten, $hostedRemoved, $buildEnvCleaned, $buildEnvDeleted, $devDocsDeleted, $codeIsTrustedNow, $repairIsUnattended, $configsMayChange, $scriptIsValid, $scriptStatus, $scriptDisplayStatus, $scriptDetail, $codeTrustNotice, $pathCorrectionRan, $pathsRefused, $provisioningReady, $sessionsMoved, $limitsWereEnabled, $limitSteps, $policyIsValid, $policyStatus, $exposureStatus, $setupIsAvailable, $setupVersion, $environmentIsReady, $environmentFindings, $kernelIsReady, $kernelFindings, $phpConfigIsValid, $phpConfigsWritten, $apacheConfigIsValid, $apacheConfigsWritten, $policyLines, $policyChecks, $policyName, $policyLine, $policiesOk, $policiesTotal);
  return array($PermissionsWereFixed, $PathsCorrected); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to install the tree a root command ran from, & hand the rest to the installed copy.
// / Accepts the exit status the command earned. Returns the exit status to leave with.
// /
// / -fp & --setup --install-complete install the application when they run from a tree that is
// / not the one in Application in the data location, which is how every release before v4.0.1
// / runs & how a release unpacked anywhere runs. It happens as the command ENDS & never part way
// / through, because the hosted location the command may have started from is about to hold only
// / the loader, & nothing may still be running from it when it does. By then the command has
// / given the tree to root & made the data location root's, which installing needs.
// / The installed copy then runs -fp, which serves it from the hosted location & does every other
// / repair again from the installed tree, & its exit status is the one this leaves with. A tree
// / that cannot be installed leaves with status one, because what -fp is for is not done, & the
// / application goes on running from where it is.
function handOverToApplication($commandStatus) {
  // / Set variables.
  global $RunningAsRoot, $RunningFromCLI, $InstLoc, $ApplicationLoc, $BackupLoc, $Lol, $EnableMemoryProtection;
  $ExitStatus = (int)$commandStatus;
  $cliArguments = array();
  $cliArgumentCount = $childStatus = 0;
  $cliCommand = $cliTarget = $cliSecondTarget = $installNotice = $phpBinary = '';
  $cliConfirmed = $cliVectorParsed = $commandInstalls = $treeWasInstalled = FALSE;
  if ($RunningAsRoot && $RunningFromCLI) {
    list ($cliArguments, $cliArgumentCount, $cliCommand, $cliTarget, $cliSecondTarget, $cliConfirmed, $cliVectorParsed) = parseArgumentVector(isset($_SERVER['argv']) ? $_SERVER['argv'] : array());
    $commandInstalls = ($cliCommand === '-fp' or $cliCommand === '--fix-permissions' or ($cliCommand === '--setup' && $cliTarget === '--install-complete')); }
  if ($commandInstalls && (string)@realpath($InstLoc) !== (string)@realpath($ApplicationLoc) && function_exists('installApplicationTree')) {
    print($Lol.'Installation'.$Lol);
    list ($treeWasInstalled, $installNotice) = installApplicationTree($InstLoc, $ApplicationLoc, $BackupLoc);
    print('  '.str_pad(($treeWasInstalled ? 'Installed' : 'NOT DONE'), 12).$installNotice.$Lol);
    if (!$treeWasInstalled) $ExitStatus = 1;
    else {
      $phpBinary = ((string)PHP_BINARY !== '') ? (string)PHP_BINARY : 'php';
      print('  '.str_pad('Running', 12).$ApplicationLoc.DIRECTORY_SEPARATOR.'convertCore.php -fp, which serves the installed copy.'.$Lol);
      passthru(escapeshellarg($phpBinary).' '.escapeshellarg($ApplicationLoc.DIRECTORY_SEPARATOR.'convertCore.php').' -fp', $childStatus);
      $ExitStatus = (int)$childStatus; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cliArguments, $cliArgumentCount, $childStatus, $cliCommand, $cliTarget, $cliSecondTarget, $installNotice, $phpBinary, $cliConfirmed, $cliVectorParsed, $commandInstalls, $treeWasInstalled, $commandStatus);
  return $ExitStatus; }
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
$RequiredKernelVersion = 'v4.0.1';
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
list ($InputsAreVerified, $ShowGUI, $GUI, $Color, $Language, $Token1, $Token2, $Height, $Width, $Rotate, $Bitrate, $Method, $Download, $UserFilename, $UserExtension, $FilesToArchive, $PDFWorkSelected, $ConvertSelected, $FilesToScan, $FilesToDelete, $UserScanType, $UserURLDownload) = verifyInputs();
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

// / The following code verifies & loads the Engine, which provides the environment this
// / application runs in rather than anything it does.
// / It is loaded BEFORE verifyGlobals & that position is not negotiable.
// / verifyGlobals calls requestConvertLoc, which calls resolveDataLocation, which lives in
// / the Engine. An Engine loaded after verifyGlobals is an Engine that does not exist yet
// / when the first thing needing it runs, & the result is a fatal on the first request.
// / An earlier version loaded it later & an audit of the call sites missed this, because
// / the audit attributed a call to the line its enclosing function was DEFINED on rather
// / than to the moment that function runs.
// / The path uses DIRECTORY_SEPARATOR rather than $DirSep, because verifyGlobals is what
// / sets $DirSep & it has not run yet.
// / A failure to load is still a warning with somewhere to go, because the log buffer holds
// / anything raised before the logfile exists.
// / The kernel is what loaded it, which is why the kernel is a file of its own rather than
// / part of the Engine. quickDie, purgeSensitiveMemory, redeclare, readComponentVersion &
// / verifyCoreComponent cannot live in a file that one of them is responsible for loading.
// / An Engine that is missing or mismatched is a WARNING. What is lost is named rather than
// / guessed at, & everything that does not depend on it carries on.
// / The capability cache is read once per request & carried, so a conversion of ten files
// / does not read the same file ten times. Pipeline Core fills these on first use.
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
  // / Resource awareness belongs to the Engine now that every manager role lives in it.
  if ($EnableResourceAwareness) $ResourceAwarenessActive = TRUE;
  if (!isset($EngineConfigVersion)) warningEntry('The Engine loaded & its configuration is missing. Every capability it gates is treated as enabled & every logging option it carries is treated as disabled.');
  if ($Verbose && (!isset($EngineReportsItsOwnLoad) or $EngineReportsItsOwnLoad)) logEntry('Engine '.ltrim((string)$EngineVersion, 'vV').' loaded for '.(isset($EngineApplicationName) ? $EngineApplicationName : $ApplicationName).'.'); }

// / An administrator may refuse to run rather than accept work they cannot account for.
// / This is tested here rather than in verifyInstallation, because what it tests for does
// / not exist until the Engine has been verified.
if ($RequireResourceAwareness && !$ResourceAwarenessActive) errorEntry('Resource awareness is required by config.php & the Engine is unavailable!', 31010, TRUE);

// / The following code verifies & sanitizes global variables for the session.
list ($GlobalsAreVerified) = verifyGlobals();
if (!$GlobalsAreVerified) errorEntry('Could not verify globals!', 11, TRUE);
else if ($Verbose) logEntry('Verified globals.');

// / The following code verifies the Pipeline Core & reads what each pipeline declares.
// / THIS RUNS AFTER verifyLogs() DELIBERATELY, because enumeration warns about every
// / pipeline it refuses & those warnings are worthless if there is nowhere to write them.
// / NO PIPELINE CODE IS EXECUTED HERE. Only pipelineConfig.php declarations are read, & a
// / pipeline's pipeline.php is loaded later, once a conversion is about to be dispatched.
// / A missing or mismatched Pipeline Core is a warning rather than an error, because the
// / interface, the command line & every report still work without it. Nothing converts
// / without it. This said convert() carried a built in dispatcher behind it, which stopped
// / being true when the last family left this file.
// / It is an Engine core as of v3.9.9 & it is still loaded here, by the application, rather
// / than by engine.php. A broken engine.php must not take the pipeline list away from -v.
// / These four are stated by verifyGlobals(), which has already run.
list ($PipelineCoreActive, $PipelineCoreVersion) = verifyCoreComponent('Pipeline Core', 'Engine'.$DirSep.'Cores'.$DirSep.'pipelineCore.php', 'PipelineCoreVersion', $RequiredPipelineCoreVersion);
if ($PipelineCoreActive) {
  list ($PipelinesAreEnumerated, $Pipelines, $PipelineCount) = enumeratePipelines();
  // / A manager that verified nothing is worse than no manager, because it would send every
  // / conversion to a dispatcher with nothing behind it. Stand it down & use the fallback.
  if (!$PipelinesAreEnumerated) $PipelineCoreActive = FALSE; }
if (!$PipelineCoreActive) warningEntry('The Pipeline Core is unavailable. No conversion can run until it is repaired.');
else if ($Verbose) logEntry('Verified the Pipeline Core. '.$PipelineCount.' conversion pipeline(s) are available.');

// / The following code decides if the security context being attempted matches a valid CLI or web request.
// / Error 27 should not be possible & should never be able to fire. If it does something is seriously wrong.
list ($CommandLineHandled, $UserType, $CommandLineStatus) = parseCommandLine();
// / A root command that installs, run from a tree that is not installed, installs it as it ends
// / & hands over to the installed copy. handOverToApplication says why it waits until now.
if ($CommandLineHandled) $CommandLineStatus = handOverToApplication($CommandLineStatus);
// / The application tells the Engine how it was reached, once it knows.
// / A CLI invocation & an HTTP request reach the same file, so the Engine cannot work
// / this out & is told. A pipeline declaring a surface it does not permit is refused.
$EngineActiveSurface = $UserType;
if ($CommandLineHandled && $UserType === 'web') errorEntry('Could not verify user type!', 27, TRUE);

// / If this is a CLI operation log a warning that conversion operations will be disabled.
if ($CommandLineHandled && $UserType === 'cli') warningEntry('CLI user detected. Conversion operations are disabled.');

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

    // / The build environment is no longer removed here. Every request tried until v4.0.1, as
    // / the web server account, & that account can no longer write to the installation,
    // / because the code is root's. -fp removes it as root, & an update runs -fp as it ends.

    // / The following code removes old files from the $ConvertTempDir.
    // / The whole DATA folder, from v4.0.1. Until then this was handed the session folder of the
    // / request making it, so no expired session in DATA was ever removed.
    list ($CleanedTempLoc, $TempLocDeepCleaned) = cleanDataLocation($ConvertTemp, 'Temporary files', $DeleteThreshold, $ProtectedRootDirs, array($ConvertTemp));
    if (!$CleanedTempLoc) warningEntry('Could not fully clean the temporary location. Some expired sessions may remain & will be retried next request. Serving this request anyway.');
    else if ($Verbose) logEntry('Cleaned temporary location.');

    // / The following code removes old files from the $ConvertLoc.
    list ($CleanedConvertLoc, $ConvertLocDeepCleaned) = cleanDataLocation($ConvertLoc, 'Data location', $DeleteThreshold, $ProtectedRootDirs, array($ConvertLoc));
    if (!$CleanedConvertLoc) warningEntry('Could not fully clean the data location. Some expired sessions may remain & will be retried next request. Serving this request anyway.');
    else if ($Verbose) logEntry('Cleaned convert location.');

    // / The following code displays the appropriate GUI for the session.
    // / EVERY FILE OPERATION ANSWERS WITH A TERSE REPLY. filesToDelete WAS MISSING.
    // / Archive, convert, OCR, download, upload & scan all suppress the interface here so
    // / their caller receives a short answer it can read. A delete did not, so it was
    // / answered with a whole rendered page.
    // / That is not merely wasteful. A caller has no way to tell a failure from a success
    // / except by reading the body, & a body containing an entire interface contains all of
    // / that interface's SCRIPT, including whatever literal that script uses to recognise a
    // / core error. The reply therefore matched the very marker the caller was searching
    // / for & every delete reported failure, no matter how well it had gone.
    // / A file operation returns a filename or an error. It does not return a page.
    if (!isset($_POST['filesToArchive']) && !isset($_POST['convertSelected']) && !isset($_POST['pdfworkSelected']) && !isset($_POST['download']) && !isset($_POST['upload']) && !isset($_POST['filesToScan']) && !isset($_POST['filesToDelete'])) {

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

      // / The following code actually builds & renders the GUI.
      $GUIDisplayed = showGUI($ShowGUI, $ButtonCode);
      if (!$GUIDisplayed) errorEntry('Could not display GUI!', 17, TRUE);
      else if ($Verbose) logEntry('Displaying the GUI.'); }

    // / If this is an API call with a simple output, continue without having displayed any GUI at all.
    else if ($Verbose) logEntry('Skipping display GUI procedure.');

    // / Check if we're providing the user with tokens generated during this session.
    // / In no other code path do we generate a new token that gets provided to the user.
    if (!$TokensAreValid && $Verbose) logEntry('Providing user with tokens: '.$Token1.' Token2: '.$Token2.'.');

    // / Only enable file related operations if valid tokens were supplied by the user.
    if ($TokensAreValid) {
      if ($Verbose) logEntry('Verified supplied tokens. Token1: '.$Token1.' Token2: '.$Token2.'.');

      // / The following code is performed when a user initiates a file upload.
      if (!empty($_FILES)) {
        logEntry('Initiating Uploader.');
        list ($UploadComplete, $UploadErrors) = uploadFiles();
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
          list ($ArchiveComplete, $ArchiveErrors) = archiveFiles($FilesToArchive, $UserFilename, $UserExtension);
          if (!$ArchiveComplete) errorEntry('Archive Failed!', 20, TRUE);
          if ($ArchiveErrors) logEntry('Archive finished with errors.');
          if ($Verbose) logEntry('Archive Complete.'); }
        $BudgetWasReleased = giveBackOperationBudget('archive'); }

      // / The following code is performed when a user converts a selection of files.
      if (isset($_POST['convertSelected'])) {
        if (!takeOperationBudget('conversion')) print($Alert3.$Lol);
        else {
          logEntry('Initiating Converter.');
          list ($ConversionComplete, $ConversionErrors) = convertFiles($ConvertSelected, $UserFilename, $UserExtension, $Height, $Width, $Rotate, $Bitrate);
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
          // / There is NO built in fallback left for OCR. Its body has moved out of this
          // / file, so a missing or mismatched OCR pipeline is reported rather than being
          // / quietly treated as an OCR run that produced nothing.
          if ($PipelineCoreActive && function_exists('runOperationPipeline')) {
            list ($ConversionComplete, $ConversionErrors, $OcrOutputFilenames) = runOperationPipeline('OCR', $PDFWorkSelected, $UserFilename, $UserExtension, $Method);
            // / THE CORE PRINTS. THE PIPELINE RETURNED. See contract 6.
            // / The interface reads this reply one line per produced file, exactly as it
            // / reads the reply from a conversion. The OCR pipeline used to print these
            // / itself, which was safe while it lived in this file & is not safe from a
            // / component whose output shares a stream with a PHP warning.
            if (is_array($OcrOutputFilenames)) foreach ($OcrOutputFilenames as $OcrOutputFilename) print($OcrOutputFilename.$Lol); }
          else {
            $ConversionErrors = TRUE;
            errorEntry('OCR was requested but no OCR pipeline is installed & this core has no built in OCR!', 34006, FALSE); }
          if (!$ConversionComplete) errorEntry('OCR Operation Failed!', 22, TRUE);
          if ($ConversionErrors) logEntry('OCR Operation finished with errors.');
          if ($Verbose) logEntry('OCR Operation Complete.'); }
        $BudgetWasReleased = giveBackOperationBudget('OCR'); }

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
// / A served web request never sees a status, so it exits 0 as it always has. A handled
// / command line exits with the status its command set, which is 0, 1 or 2. This was a bare
// / die until v3.9.9, so every command reported success whatever it had done.
exit($CommandLineHandled ? (int)$CommandLineStatus : 0);
// / -----------------------------------------------------------------------------------
?>