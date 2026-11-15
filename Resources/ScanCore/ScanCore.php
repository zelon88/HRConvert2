<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / ScanCore, Copyright on 3/31/2024 by Justin Grimes, www.github.com/zelon88
// /
// / LICENSE INFORMATION ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / BSD or MIT licensing is available. Reach out to @zelon88 for more information.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / APPLICATION INFORMATION ...
// / This application is designed to scan files & folders for viruses.
// /
// / FILE INFORMATION ...
// / v1.9.
// / This file contains the core logic of the ScanCore application.
// /
// / HARDWARE REQUIREMENTS ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / DEPENDENCY REQUIREMENTS ...
// / This application should run on Linux or Windows systems with PHP 8.0 (or later).
// / Git is preferred for performing automatic update operations, but not required.
// /
// / VALID SWITCHES / ARGUMENTS / USAGE ...
// / Quick Start Example:
// /  C:\Path-To-PHP-Binary.exe C:\Path-To-ScanCore.php C:\Path-To-Scan\ -m [integer] -c [integer] -v -d
// /
// / Start by opening a command-prompt.
// / Type the absolute path to a portable PHP 8.0+ binary. Don't press enter just yet.
// / Now type the absolute path to this PHP file as the only argument for the PHP binary.
// / Everything after the path to this script will be passed to this file as an argument.
// / The first Argument Must be a valid absolute path to the file or folder being scanned.
// / Optional arguments can be specified after the scan path. Separate them with spaces.
// /
// / Required Arguments Include:
// /
// /   File or folder to scan:                 /path/to/scan
// /
// / Optional Arguments Include:
// /
// /   Show version information:               -version
// /                                           -ver
// /
// /   Show help information:                  -help
// /                                           -h
// /
// /   Force recursion:                        -recursion
// /                                           -r
// /
// /   Force no recursion:                     -norecursion
// /                                           -nr
// /
// /   Specify memory limit (in bytes):        -memorylimit ####
// /                                           -m ####
// /
// /   Specify chunk size (in bytes):          -chunksize ####
// /                                           -c ####
// /
// /   Enable "debug" mode (more logging):     -debug
// /                                           -d
// /
// /   Enable "verbose" mode (more console):   -verbose
// /                                           -v
// /
// /   Force a specific report file:           -reportfile /path/to/file
// /                                           -rf path/to/file
// /                                           -logfile /path/to/file
// /                                           -lf path/to/file
// /
// /   Force a specific configuration file:    -configfile /path/to/file
// /                                           -cf path/to/file
// /
// /   Force a specific definitions file:      -defsfile /path/to/file
// /                                           -df path/to/file
// /
// /   Force maximum log size (in bytes):      -maxlogsize ###
// /                                           -ml ###
// /
// /   Force maximum scan depth (in folders):  -maxdepth ###
// /                                           -md ###
// /
// /   Follow symbolic links while scanning:   -followsymlinks
// /                                           -fs
// /
// /   Perform definition update:              -updatedefinitions
// /                                           -ud
// /
// /   Perform application update:             -updateapplication
// /                                           -ua
// /
// / EXIT STATUS ...
// / A status of 0 is returned when the requested operation completed.
// / A status of 1 is returned when a fatal error stopped the requested operation.
// / A clean scan & an infected scan both return 0, exactly as previous versions did.
// /
// / NAMING CONVENTIONS ...
// / Upper case first letter variables are global in scope.
// / Lower case first letter variables never leave the function that initialized them.
// / The only documented exception is $argv, which PHP itself defines for us.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to release sensitive values from memory as soon as they stop being needed.
// / Every target must be passed as its own argument so no copy of the value is created.
// / Collecting targets into an array before the call would copy each value first.
function purgeSensitiveMemory(&...$variables) {
  // / Set variables.
  $MemoryPurged = FALSE;
  $purgeCount = 0;
  // / Overwrite each supplied reference so the caller's value is released immediately.
  foreach ($variables as $purgeKey => $purgeValue) {
    $variables[$purgeKey] = NULL;
    $purgeCount++; }
  if ($purgeCount > 0) $MemoryPurged = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $purgeKey = $purgeValue = $purgeCount = NULL;
  unset($purgeKey, $purgeValue, $purgeCount);
  return $MemoryPurged; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to reset a narrow scope variable to a known value inside a loop body.
// / Assigning NULL before the new value releases the previous value straight away.
// / Nothing is purged here. The target is the caller's own variable, & the new value is a copy the caller still holds.
function redeclare(&$targetVariable, $newValue = NULL) {
  // / Set variables.
  $VariableIsRedeclared = FALSE;
  $targetVariable = NULL;
  $targetVariable = $newValue;
  $VariableIsRedeclared = TRUE;
  return $VariableIsRedeclared; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to sanitize any supplied input before that input gets used.
// / Arrays are walked recursively so a nested value cannot slip past unsanitized.
// / Strings lose null bytes & control characters, which are never valid in our inputs.
// / Other scalar types are already safe & are returned unaltered.
function sanitize($input) {
  // / Set variables.
  $SanitizedInput = $input;
  // / Walk arrays so every member is sanitized individually.
  if (is_array($input)) {
    $SanitizedInput = array();
    foreach ($input as $inputKey => $inputValue) $SanitizedInput[sanitize($inputKey)] = sanitize($inputValue); }
  // / Strip characters from strings that no legitimate argument would ever carry.
  if (is_string($input)) {
    $SanitizedInput = str_replace(chr(0), '', $input);
    $SanitizedInput = preg_replace('/[\x01-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $SanitizedInput);
    $SanitizedInput = trim($SanitizedInput); }
  // / Objects & resources are never expected here, so they are reduced to an empty string.
  if (is_object($input) or is_resource($input)) $SanitizedInput = '';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($inputKey, $inputValue, $input);
  return $SanitizedInput; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to normalize a command line switch before it gets compared.
// / A switch is a leading dash followed by letters & nothing else.
// / Any other argument, such as a path, normalizes to an empty string.
function normalizeSwitch($argument) {
  // / Set variables.
  $NormalizedSwitch = '';
  $workingArgument = sanitize($argument);
  if (is_string($workingArgument)) if (preg_match('/^-[A-Za-z]+$/', $workingArgument)) $NormalizedSwitch = strtolower($workingArgument);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($workingArgument, $argument);
  return $NormalizedSwitch; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to compare two version numbers numerically rather than as strings.
// / A string comparison ranks 24.2 below 7.6 & ranks 3.10 below 3.9, so we never use one.
// / A leading v is stripped first because casting 'v3' to an integer yields 0.
// / The supplied version passes when it is equal to or newer than the required version.
function compareVersions($requiredVersion, $suppliedVersion) {
  // / Set variables.
  $VersionsCompared = $SuppliedIsAcceptable = FALSE;
  $requiredParts = $suppliedParts = array();
  $requiredClean = strtolower(trim(sanitize($requiredVersion)));
  $suppliedClean = strtolower(trim(sanitize($suppliedVersion)));
  $requiredClean = ltrim($requiredClean, 'v');
  $suppliedClean = ltrim($suppliedClean, 'v');
  // / A build that reports no parseable version cannot be cleared, so it is refused.
  if (preg_match('/^[0-9]+(\.[0-9]+)*$/', $requiredClean) && preg_match('/^[0-9]+(\.[0-9]+)*$/', $suppliedClean)) {
    $VersionsCompared = TRUE;
    $requiredParts = explode('.', $requiredClean);
    $suppliedParts = explode('.', $suppliedClean);
    // / Pad the shorter version so a two part version compares fairly against a three part one.
    while (count($requiredParts) < count($suppliedParts)) $requiredParts[] = '0';
    while (count($suppliedParts) < count($requiredParts)) $suppliedParts[] = '0';
    $SuppliedIsAcceptable = TRUE;
    $partIndex = 0;
    $partTop = count($requiredParts);
    while ($partIndex < $partTop) {
      if (intval($suppliedParts[$partIndex]) > intval($requiredParts[$partIndex])) $partIndex = $partTop;
      else {
        if (intval($suppliedParts[$partIndex]) < intval($requiredParts[$partIndex])) {
          $SuppliedIsAcceptable = FALSE;
          $partIndex = $partTop; }
        else $partIndex++; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($requiredParts, $suppliedParts, $requiredClean, $suppliedClean, $partIndex, $partTop, $requiredVersion, $suppliedVersion);
  return array($VersionsCompared, $SuppliedIsAcceptable); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to resolve a path against the installation directory when it is relative.
// / A microservice gets invoked from whatever directory the caller happens to occupy.
// / Resolving against the installation directory keeps logs & definitions where expected.
function resolvePath($path, $baseDirectory) {
  // / Set variables.
  global $SEP;
  $ResolvedPath = sanitize($path);
  $pathIsAbsolute = FALSE;
  // / Detect a POSIX absolute path, a Windows drive letter path & a Windows network path.
  if (strlen($ResolvedPath) > 0) {
    if (substr($ResolvedPath, 0, 1) === '/' or substr($ResolvedPath, 0, 1) === '\\') $pathIsAbsolute = TRUE;
    if (preg_match('/^[A-Za-z]:[\\\\\/]/', $ResolvedPath)) $pathIsAbsolute = TRUE; }
  if (!$pathIsAbsolute && strlen($ResolvedPath) > 0) $ResolvedPath = $baseDirectory.$SEP.$ResolvedPath;
  // / Collapse any doubled separator that the concatenation may have introduced.
  $ResolvedPath = str_replace($SEP.$SEP, $SEP, $ResolvedPath);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($pathIsAbsolute, $path, $baseDirectory);
  return $ResolvedPath; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify that this installation can actually run before anything starts.
// / Previous versions declared the installation verified without inspecting anything.
function verifyInstallation() {
  global $Date, $Time, $Version, $InstallationVerified, $FileCount, $DirCount, $Infected, $Suspicious, $DetectionCount, $HandlerFileCount, $CodeFileCount, $IncompleteCount, $MemberContext, $EOL, $SEP, $RP, $CoreFile, $DefaultConfigFile, $LogAvailable, $LogBytesWritten, $VerificationFault;
  // / Time related variables.
  $Date = date("m_d_y");
  $Time = date("F j, Y, g:i a");
  // / Application related variables.
  $Version = 'v1.9';
  $DefaultConfigFile = 'ScanCore_Config.php';
  $FileCount = $DirCount = $Infected = $Suspicious = $DetectionCount = $LogBytesWritten = 0;
  $HandlerFileCount = $CodeFileCount = $IncompleteCount = 0;
  $MemberContext = '';
  $EOL = PHP_EOL;
  $SEP = DIRECTORY_SEPARATOR;
  $RP = realpath(dirname(__FILE__));
  $CoreFile = 'ScanCore.php';
  $LogAvailable = TRUE;
  $InstallationVerified = TRUE;
  $VerificationFault = '';
  $requiredFunctions = array('hash_file', 'hash_init', 'hash_update', 'hash_final', 'scandir', 'fopen', 'fread', 'file_put_contents', 'file_get_contents');
  // / Refuse to run anywhere except a command line, because there is no web interface here.
  if (PHP_SAPI !== 'cli') {
    $InstallationVerified = FALSE;
    $VerificationFault = 'ScanCore only runs from a command line interpreter'; }
  // / Refuse to run on an interpreter older than the documented dependency requirement.
  if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    $InstallationVerified = FALSE;
    $VerificationFault = 'ScanCore requires PHP 8.0 or later, found '.PHP_VERSION; }
  // / Refuse to run when a required function has been disabled by the interpreter.
  foreach ($requiredFunctions as $requiredFunction) if (!function_exists($requiredFunction)) {
    $InstallationVerified = FALSE;
    $VerificationFault = 'A required PHP function is unavailable:  '.$requiredFunction; }
  // / Refuse to run when the installation directory cannot be resolved.
  if ($RP === FALSE or $RP === '') {
    $InstallationVerified = FALSE;
    $VerificationFault = 'Cannot resolve the ScanCore installation directory'; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($requiredFunctions, $requiredFunction);
  return array($InstallationVerified, $Version, $VerificationFault); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to apply a hardcoded default whenever a configuration entry is unusable.
// / A subsystem that can fall back must fall back before it errors.
// / Faults are collected rather than logged because logging is not available this early.
// / The type may be boolean, string, integer, array or list.
function applyConfigFallback(&$setting, $defaultValue, $settingName, $settingType) {
  global $ConfigFaults;
  // / Set variables.
  $SettingAccepted = TRUE;
  // / Detect a setting that was never defined by the configuration file at all.
  if (!isset($setting)) $SettingAccepted = FALSE;
  else {
    if ($settingType === 'boolean' && !is_bool($setting)) $SettingAccepted = FALSE;
    if ($settingType === 'string' && (!is_string($setting) or $setting === '')) $SettingAccepted = FALSE;
    if ($settingType === 'array' && (!is_array($setting) or count($setting) === 0)) $SettingAccepted = FALSE;
    // / A list differs from an array in that an empty list is a real answer, not a mistake.
    // / Naming no classifiers means wanting none of them, which is not the same as
    // / forgetting to name any, so an empty list is accepted exactly as it was written.
    if ($settingType === 'list' && !is_array($setting)) $SettingAccepted = FALSE;
    if ($settingType === 'integer' && (!is_numeric($setting) or intval($setting) < 1)) $SettingAccepted = FALSE; }
  // / Fall back to the hardcoded default & record why, so the administrator can see it.
  if (!$SettingAccepted) {
    $setting = $defaultValue;
    $ConfigFaults[] = 'Configuration entry $'.$settingName.' is missing or invalid, using the built in default instead.'; }
  if ($SettingAccepted && $settingType === 'integer') $setting = intval($setting);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($defaultValue, $settingName, $settingType);
  return $SettingAccepted; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to load a specified configuration file.
// / If neither the -configfile nor the -cf argument is set, ScanCore_Config.php is used.
// / If neither the -defsfile nor the -df argument is set, the configured file is used.
// / The core version & the arguments arrive as arguments & are never pulled back out of global scope.
function loadConfig($coreVersion, $argumentList) {
  global $ConfigFilePath, $RP, $SEP, $EOL, $ConfigFile, $ScanLoc, $DefsFile, $ConfigVersion, $ConfigLoaded, $DefsExist, $ReportFile, $ReportDir, $ReportFileName, $RequiredDirs, $InstallDir, $MaxLogSize, $MemoryLimit, $ChunkSize, $DefaultMemoryLimit, $DefaultChunkSize, $DefaultMaxLogSize, $DefinitionRepositoryName, $DefinitionUpdates, $DefinitionUpdateDomain, $DefinitionUpdateURL, $DefInstallDir, $DefGitDir, $ApplicationRepositoryName, $ApplicationUpdates, $ApplicationUpdateDomain, $ApplicationUpdateURL, $AppInstallDir, $AppGitDir, $DefinitionsUpdateSubscriptions, $DefsFileName, $Verbose, $Debug, $UpdateMethod, $DefinitionBranchName, $ApplicationBranchName, $ApplicationUpdateSubscriptions, $VersionsMatch, $ConfigFaults, $MaxScanDepth, $FollowSymlinks, $ConnectionTimeout, $MinimumDataSignatureLength, $ClassifyContent, $ClassifierFileName, $ClassifierFile, $MinimumLanguageSignatures, $EnabledClassifiers, $InspectArchivedContent, $MaxArchiveEntries, $MaxArchiveEntrySize, $MaxArchiveTotalSize, $MaxArchiveCompressionRatio, $ReportSuspicious, $PreserveOwnership, $OwnerUser, $OwnerGroup, $FilePermissions, $DirectoryPermissions, $UseHashIndex, $HashIndexThreshold, $InspectImages, $MaxImagePixels, $MaxImageChunkSize, $MaxArchiveDepth;
  // / Set variables.
  $ConfigLoaded = $DefsExist = $VersionsMatch = FALSE;
  $ConfigFaults = array();
  $ConfigFile = 'ScanCore_Config.php';
  $ConfigFilePath = $RP.$SEP.$ConfigFile;
  $requireResult = FALSE;
  $overrideDefsFile = '';
  $createBlankDefs = FALSE;
  // / Initialize an empty array if no arguments are set.
  if (!is_array($argumentList)) $argumentList = array();
  // / Briefly iterate the arguments just to see if a special configuration file is wanted.
  foreach ($argumentList as $configKey => $configArg) if (normalizeSwitch($configArg) === '-configfile' or normalizeSwitch($configArg) === '-cf') {
    if (isset($argumentList[$configKey + 1])) $ConfigFilePath = resolvePath(sanitize($argumentList[$configKey + 1]), $RP);
    else $ConfigFaults[] = 'The -configfile argument was supplied without a path, using the default instead.'; }
  // / Load the configuration file located at $ConfigFilePath.
  if (file_exists($ConfigFilePath) && !is_dir($ConfigFilePath)) {
    $requireResult = require_once ($ConfigFilePath);
    if ($requireResult !== FALSE) $ConfigLoaded = TRUE; }
  // / Record the configuration file that actually loaded so later functions can find it.
  if ($ConfigLoaded) $ConfigFile = $ConfigFilePath;
  // / Validate every configuration entry & fall back to a working default when needed.
  if ($ConfigLoaded) list($VersionsMatch) = validateConfig($coreVersion);
  // / Briefly iterate the arguments to see if a special definitions file is wanted.
  foreach ($argumentList as $defsKey => $defsArg) if (normalizeSwitch($defsArg) === '-defsfile' or normalizeSwitch($defsArg) === '-df') {
    if (isset($argumentList[$defsKey + 1])) $overrideDefsFile = resolvePath(sanitize($argumentList[$defsKey + 1]), $RP);
    else $ConfigFaults[] = 'The -defsfile argument was supplied without a path, using the configured file instead.'; }
  if ($overrideDefsFile !== '') $DefsFile = $overrideDefsFile;
  // / Create a blank definitions file when an update was requested against a missing one.
  foreach ($argumentList as $updateKey => $updateArg) if (normalizeSwitch($updateArg) === '-updatedefinitions' or normalizeSwitch($updateArg) === '-ud') $createBlankDefs = TRUE;
  if ($createBlankDefs && is_string($DefsFile) && $DefsFile !== '' && !file_exists($DefsFile)) @file_put_contents($DefsFile, '');
  // / Check whether the definitions file is present & readable.
  if (is_string($DefsFile) && $DefsFile !== '') if (file_exists($DefsFile) && !is_dir($DefsFile)) $DefsExist = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($configKey, $configArg, $defsKey, $defsArg, $updateKey, $updateArg, $requireResult, $overrideDefsFile, $createBlankDefs, $coreVersion, $argumentList);
  return array($ConfigLoaded, $DefsExist, $ConfigFilePath, $VersionsMatch); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to validate configuration entries & derive the paths that depend on them.
// / The configuration file is a minimum match, so a newer configuration is acceptable.
// / A configuration that is older than the core is refused, because settings may be absent.
function validateConfig($coreVersion) {
  global $RP, $SEP, $ConfigVersion, $ScanLoc, $DefsFile, $DefsFileName, $ReportFile, $ReportDir, $ReportFileName, $RequiredDirs, $InstallDir, $MaxLogSize, $MemoryLimit, $ChunkSize, $DefaultMemoryLimit, $DefaultChunkSize, $DefaultMaxLogSize, $DefinitionRepositoryName, $DefinitionUpdates, $DefinitionUpdateDomain, $DefinitionUpdateURL, $DefInstallDir, $DefGitDir, $ApplicationRepositoryName, $ApplicationUpdates, $ApplicationUpdateDomain, $ApplicationUpdateURL, $AppInstallDir, $AppGitDir, $DefinitionsUpdateSubscriptions, $Verbose, $Debug, $UpdateMethod, $DefinitionBranchName, $ApplicationBranchName, $ApplicationUpdateSubscriptions, $ConfigFaults, $MaxScanDepth, $FollowSymlinks, $ConnectionTimeout, $MinimumDataSignatureLength, $ClassifyContent, $ClassifierFileName, $ClassifierFile, $MinimumLanguageSignatures, $EnabledClassifiers, $InspectArchivedContent, $MaxArchiveEntries, $MaxArchiveEntrySize, $MaxArchiveTotalSize, $MaxArchiveCompressionRatio, $ReportSuspicious, $PreserveOwnership, $OwnerUser, $OwnerGroup, $FilePermissions, $DirectoryPermissions, $UseHashIndex, $HashIndexThreshold, $InspectImages, $MaxImagePixels, $MaxImageChunkSize, $MaxArchiveDepth;
  // / Set variables.
  $VersionsMatch = $versionsCompared = FALSE;
  // / Compare the configuration version numerically against the core version.
  list($versionsCompared, $VersionsMatch) = compareVersions($coreVersion, isset($ConfigVersion) ? $ConfigVersion : '');
  if (!$versionsCompared) $ConfigFaults[] = 'The configuration file reports no parseable version number.';
  // / Validate the entries that control update behaviour.
  applyConfigFallback($ApplicationUpdates, TRUE, 'ApplicationUpdates', 'boolean');
  applyConfigFallback($ApplicationUpdateURL, 'https://github.com/zelon88/ScanCore', 'ApplicationUpdateURL', 'string');
  applyConfigFallback($ApplicationUpdateDomain, 'github.com', 'ApplicationUpdateDomain', 'string');
  applyConfigFallback($ApplicationRepositoryName, 'ScanCore', 'ApplicationRepositoryName', 'string');
  applyConfigFallback($ApplicationBranchName, 'master', 'ApplicationBranchName', 'string');
  applyConfigFallback($ApplicationUpdateSubscriptions, array('README.md', 'ScanCore.php', 'ScanCore_Config.php', 'index.html', 'ScanCore_Classifiers.def', 'Documentation/CHANGELOG.txt', 'Documentation/ERROR_DESCRIPTIONS.txt', 'Documentation/DEVELOPMENT_NOTES.txt', 'Documentation/index.html'), 'ApplicationUpdateSubscriptions', 'array');
  applyConfigFallback($DefinitionUpdates, TRUE, 'DefinitionUpdates', 'boolean');
  applyConfigFallback($DefinitionUpdateURL, 'https://github.com/zelon88/ScanCore_Definitions', 'DefinitionUpdateURL', 'string');
  applyConfigFallback($DefinitionUpdateDomain, 'github.com', 'DefinitionUpdateDomain', 'string');
  applyConfigFallback($DefinitionRepositoryName, 'ScanCore_Definitions', 'DefinitionRepositoryName', 'string');
  applyConfigFallback($DefinitionBranchName, 'main', 'DefinitionBranchName', 'string');
  applyConfigFallback($DefinitionsUpdateSubscriptions, array('Virus', 'Malware', 'PUP'), 'DefinitionsUpdateSubscriptions', 'array');
  applyConfigFallback($UpdateMethod, 'git', 'UpdateMethod', 'string');
  // / Validate the entries that control scanning behaviour.
  applyConfigFallback($DefaultMaxLogSize, 1024*1024*32, 'DefaultMaxLogSize', 'integer');
  applyConfigFallback($DefaultMemoryLimit, 1024*1024*512, 'DefaultMemoryLimit', 'integer');
  applyConfigFallback($DefaultChunkSize, 1024*1024*8, 'DefaultChunkSize', 'integer');
  applyConfigFallback($MaxScanDepth, 64, 'MaxScanDepth', 'integer');
  applyConfigFallback($ConnectionTimeout, 15, 'ConnectionTimeout', 'integer');
  applyConfigFallback($MinimumDataSignatureLength, 4, 'MinimumDataSignatureLength', 'integer');
  applyConfigFallback($FollowSymlinks, FALSE, 'FollowSymlinks', 'boolean');
  applyConfigFallback($ClassifyContent, TRUE, 'ClassifyContent', 'boolean');
  applyConfigFallback($EnabledClassifiers, array('Language', 'SCAD', 'PDF', 'Document', 'Spreadsheet', 'Presentation', 'XPS', 'SVG', 'Markup', 'Stream', 'Transport', 'Model', 'Drawing', 'Ebook', 'Subtitle'), 'EnabledClassifiers', 'list');
  applyConfigFallback($MinimumLanguageSignatures, 2, 'MinimumLanguageSignatures', 'integer');
  applyConfigFallback($InspectArchivedContent, TRUE, 'InspectArchivedContent', 'boolean');
  applyConfigFallback($MaxArchiveEntries, 4096, 'MaxArchiveEntries', 'integer');
  applyConfigFallback($MaxArchiveDepth, 5, 'MaxArchiveDepth', 'integer');
  applyConfigFallback($InspectImages, TRUE, 'InspectImages', 'boolean');
  applyConfigFallback($MaxImagePixels, 16384*16384, 'MaxImagePixels', 'integer');
  applyConfigFallback($MaxImageChunkSize, 1024*1024*8, 'MaxImageChunkSize', 'integer');
  applyConfigFallback($MaxArchiveEntrySize, 1024*1024*8, 'MaxArchiveEntrySize', 'integer');
  applyConfigFallback($MaxArchiveTotalSize, 1024*1024*64, 'MaxArchiveTotalSize', 'integer');
  applyConfigFallback($MaxArchiveCompressionRatio, 200, 'MaxArchiveCompressionRatio', 'integer');
  applyConfigFallback($ReportSuspicious, TRUE, 'ReportSuspicious', 'boolean');
  applyConfigFallback($UseHashIndex, TRUE, 'UseHashIndex', 'boolean');
  applyConfigFallback($HashIndexThreshold, 10000, 'HashIndexThreshold', 'integer');
  applyConfigFallback($PreserveOwnership, TRUE, 'PreserveOwnership', 'boolean');
  applyConfigFallback($FilePermissions, 0644, 'FilePermissions', 'integer');
  applyConfigFallback($DirectoryPermissions, 0755, 'DirectoryPermissions', 'integer');
  if (!isset($OwnerUser) or !is_string($OwnerUser)) $OwnerUser = '';
  if (!isset($OwnerGroup) or !is_string($OwnerGroup)) $OwnerGroup = '';
  applyConfigFallback($ClassifierFileName, 'ScanCore_Classifiers.def', 'ClassifierFileName', 'string');
  applyConfigFallback($Debug, FALSE, 'Debug', 'boolean');
  applyConfigFallback($Verbose, FALSE, 'Verbose', 'boolean');
  // / Validate the entries that describe where things live on disk.
  applyConfigFallback($ReportDir, 'Logs', 'ReportDir', 'string');
  applyConfigFallback($ReportFileName, 'ScanCore_Report.txt', 'ReportFileName', 'string');
  applyConfigFallback($DefsFileName, 'ScanCore_Combined_Definitions.def', 'DefsFileName', 'string');
  applyConfigFallback($InstallDir, $RP, 'InstallDir', 'string');
  if (!isset($ScanLoc) or !is_string($ScanLoc)) $ScanLoc = '';
  // / Derive every path from the installation directory so the working directory never matters.
  $InstallDir = resolvePath($InstallDir, $RP);
  // / A configuration file loaded from elsewhere reports its own folder as the installation.
  // / Fall back to the folder holding this core file whenever the core is not where it says.
  if (!file_exists($InstallDir.$SEP.'ScanCore.php')) {
    $ConfigFaults[] = 'Configuration entry $InstallDir does not contain ScanCore.php, using '.$RP.' instead.';
    $InstallDir = $RP; }
  $ReportDir = resolvePath($ReportDir, $InstallDir);
  $ReportFile = $ReportDir.$SEP.basename($ReportFileName);
  $RequiredDirs = array($ReportDir);
  $UpdateMethod = strtolower($UpdateMethod);
  // / Fall back to the git update method when the configured method is not recognized.
  if ($UpdateMethod !== 'git' && $UpdateMethod !== 'raw') {
    $ConfigFaults[] = 'Configuration entry $UpdateMethod is not git or raw, using git instead.';
    $UpdateMethod = 'git'; }
  $MaxLogSize = $DefaultMaxLogSize;
  $MemoryLimit = $DefaultMemoryLimit;
  $ChunkSize = $DefaultChunkSize;
  // / Resolve the definitions file only when the configuration did not already do it.
  if (!isset($DefsFile) or !is_string($DefsFile) or $DefsFile === '') $DefsFile = $InstallDir.$SEP.$DefsFileName;
  $DefsFile = resolvePath($DefsFile, $InstallDir);
  // / Fall back to the definitions beside the core when the configured file is not there.
  if (!file_exists($DefsFile)) if (file_exists($InstallDir.$SEP.$DefsFileName)) {
    $ConfigFaults[] = 'Configuration entry $DefsFile does not exist, using the file beside the core instead.';
    $DefsFile = $InstallDir.$SEP.$DefsFileName; }
  // / The classifier definitions ship with the application, beside the core.
  $ClassifierFile = $InstallDir.$SEP.basename($ClassifierFileName);
  // / Build the temporary update directories from the repository names.
  // / An empty repository name would aim the cleaner at the installation directory itself.
  $DefInstallDir = $InstallDir.$SEP.basename($DefinitionRepositoryName);
  $AppInstallDir = $InstallDir.$SEP.basename($ApplicationRepositoryName);
  $DefGitDir = $DefInstallDir.$SEP.'.git';
  $AppGitDir = $AppInstallDir.$SEP.'.git';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($versionsCompared, $coreVersion);
  return array($VersionsMatch); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to work out which user & group should own anything ScanCore creates.
// / An administrator running an update under sudo would otherwise leave every file owned
// / by root, & the web server user that runs ScanCore the rest of the time could no
// / longer write its own report file or install its own definition update.
// / The installation directory is the reference, because whoever owns that is whoever
// / this installation belongs to.
function resolveOwnership($announce) {
  global $InstallDir, $OwnerUser, $OwnerGroup, $PreserveOwnership, $OwnershipUid, $OwnershipGid, $RunningAsRoot;
  // / Set variables.
  $OwnershipResolved = FALSE;
  $OwnershipUid = $OwnershipGid = -1;
  $RunningAsRoot = FALSE;
  $referenceStat = array();
  $passwordEntry = $groupEntry = array();
  if (function_exists('posix_geteuid')) if (posix_geteuid() === 0) $RunningAsRoot = TRUE;
  if ($RunningAsRoot && $PreserveOwnership) {
    // / Inherit from the installation directory unless the configuration names an owner.
    $referenceStat = @stat($InstallDir);
    if (is_array($referenceStat)) {
      $OwnershipUid = intval($referenceStat['uid']);
      $OwnershipGid = intval($referenceStat['gid']);
      $OwnershipResolved = TRUE; }
    if ($OwnerUser !== '' && function_exists('posix_getpwnam')) {
      $passwordEntry = @posix_getpwnam($OwnerUser);
      if (is_array($passwordEntry)) {
        $OwnershipUid = intval($passwordEntry['uid']);
        $OwnershipResolved = TRUE; }
      else { if ($announce) processOutput('Cannot resolve the configured OwnerUser, falling back to the installation directory owner:  '.$OwnerUser, 'warning', 0, TRUE, FALSE, FALSE); } }
    if ($OwnerGroup !== '' && function_exists('posix_getgrnam')) {
      $groupEntry = @posix_getgrnam($OwnerGroup);
      if (is_array($groupEntry)) {
        $OwnershipGid = intval($groupEntry['gid']);
        $OwnershipResolved = TRUE; }
      else { if ($announce) processOutput('Cannot resolve the configured OwnerGroup, falling back to the installation directory group:  '.$OwnerGroup, 'warning', 0, TRUE, FALSE, FALSE); } }
    if ($announce) {
      if ($OwnershipResolved) processOutput('Running as root. Anything created will be handed to uid '.$OwnershipUid.' & gid '.$OwnershipGid.'.', 'log', 0, TRUE, FALSE, FALSE);
      else processOutput('Running as root & cannot work out who should own new files, so they will stay owned by root.', 'warning', 0, TRUE, FALSE, FALSE); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($referenceStat, $passwordEntry, $groupEntry, $announce);
  return $OwnershipResolved; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to tell whether root may change a path by its name.
// / A folder may be changed, & so may a file with no other name. A link is not, because changing it
// / by name would change whatever it points at. A file with a second hard link is not either, because
// / that other name could be anywhere on the system, & so could the file another account linked here.
function pathIsPlainForRoot($path) {
  // / Set variables.
  $PathIsPlain = FALSE;
  $pathStat = @lstat($path);
  $pathType = 0;
  if (is_array($pathStat)) {
    $pathType = intval($pathStat['mode']) & 0170000;
    if ($pathType === 0040000 or ($pathType === 0100000 && intval($pathStat['nlink']) === 1)) $PathIsPlain = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($pathStat, $pathType, $path);
  return $PathIsPlain; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to hand a path to an owner & group without following a link.
// / lchown & lchgrp change the name itself, so a link put where the path was is never followed.
// / Returns TRUE when both the owner & the group were changed.
function changeOwnerWithoutFollowing($path, $ownerUid, $ownerGid) {
  // / Set variables.
  $OwnerChanged = FALSE;
  if (@lchown($path, $ownerUid) && @lchgrp($path, $ownerGid)) $OwnerChanged = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($path, $ownerUid, $ownerGid);
  return $OwnerChanged; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to hand a newly created path to the owner this installation belongs to.
// / Does nothing at all unless we are root, because nobody else may give a file away.
// / A link or a file with a second hard link is left as it is, because root would change what it leads to.
function applyOwnership($path, $isDirectory) {
  global $RunningAsRoot, $PreserveOwnership, $OwnershipUid, $OwnershipGid, $FilePermissions, $DirectoryPermissions;
  // / Set variables.
  $OwnershipApplied = FALSE;
  if ($RunningAsRoot && $PreserveOwnership && $OwnershipUid >= 0 && file_exists($path) && pathIsPlainForRoot($path)) {
    changeOwnerWithoutFollowing($path, $OwnershipUid, $OwnershipGid);
    if ($isDirectory) @chmod($path, $DirectoryPermissions);
    else @chmod($path, $FilePermissions);
    $OwnershipApplied = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($path, $isDirectory);
  return $OwnershipApplied; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to record who owns a file & what its mode is, before it gets replaced.
// / An update must not quietly change either. A file that was group writable so the web
// / server could update it has to still be group writable afterwards.
function captureFileIdentity($path) {
  // / Set variables.
  $FileIdentity = array('exists' => FALSE, 'uid' => -1, 'gid' => -1, 'mode' => -1);
  $pathStat = array();
  if (file_exists($path)) {
    $pathStat = @stat($path);
    if (is_array($pathStat)) $FileIdentity = array('exists' => TRUE, 'uid' => intval($pathStat['uid']), 'gid' => intval($pathStat['gid']), 'mode' => intval($pathStat['mode']) & 0777); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($pathStat, $path);
  return $FileIdentity; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to put a recorded owner & mode back onto a replaced file.
// / The mode is restored whoever we are, because the owner of a file may always set it.
// / The owner is only restored when we are root, because nobody else may give a file away.
// / A link or a file with a second hard link is left as it is, because the change would reach what it leads to.
function restoreFileIdentity($path, $fileIdentity) {
  global $RunningAsRoot, $PreserveOwnership;
  // / Set variables.
  $IdentityRestored = FALSE;
  if ($fileIdentity['exists'] && file_exists($path) && pathIsPlainForRoot($path)) {
    if ($fileIdentity['mode'] >= 0) @chmod($path, $fileIdentity['mode']);
    if ($RunningAsRoot && $PreserveOwnership && $fileIdentity['uid'] >= 0) changeOwnerWithoutFollowing($path, $fileIdentity['uid'], $fileIdentity['gid']);
    $IdentityRestored = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($path, $fileIdentity);
  return $IdentityRestored; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to create required directories when they do not already exist.
function createDirs($RequiredDirs) {
  global $RP, $SEP;
  // / Set variables.
  $RequiredDirsExist = TRUE;
  // / Iterate through each required directory.
  foreach ($RequiredDirs as $reqdDir) {
    // / Detect if the directory already exists & create it if required.
    if (!file_exists($reqdDir)) {
      @mkdir($reqdDir, 0755, TRUE);
      applyOwnership($reqdDir, TRUE); }
    // / Copy an index.html file into the new directory as document root protection.
    if (!file_exists($reqdDir.$SEP.'index.html')) if (file_exists($RP.$SEP.'index.html')) {
      @copy($RP.$SEP.'index.html', $reqdDir.$SEP.'index.html');
      applyOwnership($reqdDir.$SEP.'index.html', FALSE); }
    if (!is_dir($reqdDir)) $RequiredDirsExist = FALSE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($reqdDir, $RequiredDirs);
  return array($RequiredDirsExist); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to start a new report file once the current one reaches the maximum size.
// / Previous versions accepted a maximum log size but never acted upon it.
function rotateLog($pendingBytes) {
  global $ReportFile, $MaxLogSize, $Date, $LogBytesWritten;
  // / Set variables.
  $LogRotated = FALSE;
  $rotationIndex = 0;
  $rotatedFile = '';
  $currentSize = 0;
  // / Measure the existing report file only once per session, then track our own writes.
  if ($LogBytesWritten === 0) if (file_exists($ReportFile)) $currentSize = intval(@filesize($ReportFile));
  if ($currentSize > 0) $LogBytesWritten = $currentSize;
  // / Rename the current report file out of the way once it would exceed the maximum size.
  if (($LogBytesWritten + $pendingBytes) > $MaxLogSize) if (file_exists($ReportFile)) {
    $rotatedFile = $ReportFile.'_'.$Date.'_'.$rotationIndex.'.old';
    while (file_exists($rotatedFile)) {
      $rotationIndex++;
      redeclare($rotatedFile, $ReportFile.'_'.$Date.'_'.$rotationIndex.'.old'); }
    $LogRotated = @rename($ReportFile, $rotatedFile);
    if ($LogRotated) {
      applyOwnership($rotatedFile, FALSE);
      $LogBytesWritten = 0; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($rotationIndex, $rotatedFile, $currentSize, $pendingBytes);
  return $LogRotated; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to add an entry to the logs.
// / The level must be one of log, warning or error.
// / A log entry records normal activity & carries no number.
// / A warning tells the administrator something happened that did not stop the work.
// / An error records a failure that altered behaviour & always carries a documented number.
function addLogEntry($entry, $level, $errorNumber) {
  global $ReportFile, $EOL, $LogAvailable, $LogBytesWritten;
  // / Set variables.
  $LogCreated = FALSE;
  $preText = '';
  $entryTime = date("F j, Y, g:i a");
  $writeResult = FALSE;
  $reportExisted = FALSE;
  if (!is_numeric($errorNumber)) $errorNumber = 0;
  if ($level === 'error') $preText = 'ERROR!!! ScanCore-'.intval($errorNumber).' on '.$entryTime.', ';
  if ($level === 'warning') $preText = 'WARNING!!! ScanCore on '.$entryTime.', ';
  if ($level !== 'error' && $level !== 'warning') $preText = $entryTime.', ';
  // / Write the entry only while the report file remains usable.
  if ($LogAvailable) {
    rotateLog(strlen($preText.$entry.$EOL));
    $reportExisted = file_exists($ReportFile);
    $writeResult = @file_put_contents($ReportFile, $preText.$entry.$EOL, FILE_APPEND);
    // / A report created while running as root would otherwise lock out the service user.
    if (!$reportExisted && $writeResult !== FALSE) applyOwnership($ReportFile, FALSE);
    if ($writeResult === FALSE) {
      // / A scanner that cannot write a log must still scan, so logging is simply disabled.
      $LogAvailable = FALSE;
      echo 'WARNING!!! ScanCore-910, Cannot write to the report file:  '.$ReportFile.$EOL; }
    else {
      $LogBytesWritten = $LogBytesWritten + intval($writeResult);
      $LogCreated = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($preText, $entryTime, $writeResult, $reportExisted, $entry, $level, $errorNumber);
  return array($LogCreated); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to handle important messages to the console & log file.
// / The level argument replaces the boolean that previous versions used.
// / Passing a boolean still works, so no call site can silently change meaning.
function processOutput($txt, $level, $errorNumber, $requiredLog, $requiredConsole, $fatal) {
  global $EOL, $Debug, $Verbose;
  // / Set variables.
  $OutputProcessed = FALSE;
  $entryLevel = 'log';
  // / Verify that all inputs are of the correct type.
  if (!is_string($txt)) $txt = '';
  if (!is_numeric($errorNumber)) $errorNumber = 0;
  // / Translate the level argument, accepting the boolean that older call sites passed.
  if ($level === TRUE or $level === 'error') $entryLevel = 'error';
  if ($level === 'warning') $entryLevel = 'warning';
  // / An error without a documented number is really a warning, so it gets recorded as one.
  if ($entryLevel === 'error' && intval($errorNumber) === 0) $entryLevel = 'warning';
  // / Log the provided text if the $Debug variable (-d switch) is set.
  if ($Debug or $requiredLog) list ($OutputProcessed) = addLogEntry($txt, $entryLevel, $errorNumber);
  // / Output the summary text to the terminal if the $Verbose (-v switch) variable is set.
  if ($Verbose or $requiredConsole) echo $txt.$EOL;
  // / Stop execution as needed, reporting a failing status to whatever invoked us.
  if ($fatal) exit(1);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($txt, $entryLevel, $level, $errorNumber, $requiredLog, $requiredConsole, $fatal);
  return array($OutputProcessed); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to render a subscription list as readable text.
// / Both the updaters & the version screen need this, so it lives in one place.
function buildSubscriptionText($subscriptions) {
  // / Set variables.
  $SubscriptionText = '';
  if (is_array($subscriptions)) foreach ($subscriptions as $subscriptionName) $SubscriptionText = $SubscriptionText.' '.$subscriptionName.',';
  $SubscriptionText = trim(trim($SubscriptionText, ','), ' ');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($subscriptionName, $subscriptions);
  return $SubscriptionText; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to reliably build help & version information.
// / Every path is absolute so this works no matter which directory invoked ScanCore.
function buildHelpInformation() {
  global $DefinitionsUpdateSubscriptions, $SubText, $VersionText, $HelpText, $ApplicationUpdateURL, $DefinitionUpdateURL, $DefsFile, $ConfigFile, $Version, $EOL;
  // / Set variables.
  $InformationBuilt = FALSE;
  $SubText = $VersionText = $HelpText = '';
  $coreFilePath = realpath(__FILE__);
  $configFilePath = realpath($ConfigFile);
  $defsFilePath = realpath($DefsFile);
  $SubText = buildSubscriptionText($DefinitionsUpdateSubscriptions);
  // / Report an unresolved path plainly rather than printing the word false to the console.
  if ($configFilePath === FALSE) $configFilePath = 'Not found:  '.$ConfigFile;
  if ($defsFilePath === FALSE) $defsFilePath = 'Not found:  '.$DefsFile;
  // / The core file is the running script, so it is always present by definition.
  if ($coreFilePath !== FALSE) {
    $InformationBuilt = TRUE;
    $originalRepo = 'https://github.com/zelon88/ScanCore';
    $licenseText = 'GPLv3';
    $verText1 = 'ScanCore '.$Version.' by Justin Grimes (@zelon88), licensed under '.$licenseText.'.'.$EOL;
    $verText2 = 'The original source code for this application can be found at:  '.$originalRepo.$EOL;
    $verText3 = 'This installation is located at:  '.$coreFilePath.$EOL;
    $verText4 = 'This installation is using a definitions file located at:  '.$defsFilePath.$EOL;
    $verText5 = 'This installation is using a configuration file located at:  '.$configFilePath.$EOL;
    $verText6 = 'This installation downloads Application updates from:  '.$ApplicationUpdateURL.$EOL;
    $verText7 = 'This installation downloads Definition updates from:  '.$DefinitionUpdateURL.$EOL;
    $verText8 = 'Configuration file was last updated on:  '.date("F d Y H:i:s.", intval(@filectime($ConfigFile))).$EOL;
    $verText9 = 'Application update was last installed on:  '.date("F d Y H:i:s.", intval(@filectime($coreFilePath))).$EOL;
    $verText10 = 'Definition update was last installed on:  '.date("F d Y H:i:s.", intval(@filectime($DefsFile))).$EOL;
    $verText11 = 'This installation has the following Definition Subscriptions:  '.$SubText;
    // / The switch table is the one place a switch is described, so adding a switch
    // / means adding a row rather than another numbered variable in a chain of forty.
    $switchTable = array(
      array('Show version information', '-version', '-ver'),
      array('Show help information', '-help', '-h'),
      array('Force recursion', '-recursion', '-r'),
      array('Force no recursion', '-norecursion', '-nr'),
      array('Follow symbolic links while scanning', '-followsymlinks', '-fs'),
      array('Do not follow symbolic links', '-nofollowsymlinks', '-nfs'),
      array('Specify memory limit (in bytes)', '-memorylimit ####', '-m ####'),
      array('Specify chunk size (in bytes)', '-chunksize ####', '-c ####'),
      array('Force maximum scan depth (in folders)', '-maxdepth ###', '-md ###'),
      array('Enable "debug" mode (more logging)', '-debug', '-d'),
      array('Enable "verbose" mode (more console)', '-verbose', '-v'),
      array('Force a specific report file', '-reportfile /path/to/file', '-rf /path/to/file'),
      array('Force a specific configuration file', '-configfile /path/to/file', '-cf /path/to/file'),
      array('Force a specific definitions file', '-defsfile /path/to/file', '-df /path/to/file'),
      array('Force maximum log size (in bytes)', '-maxlogsize ###', '-ml ###'),
      array('Report low confidence matches', '-suspicious', '-sus'),
      array('Report confirmed matches only', '-nosuspicious', '-nsus'),
      array('Enable content classification', '-classify', '-cy'),
      array('Disable content classification', '-noclassify', '-ncy'),
      array('Choose which classifiers run', '-classifiers scad,pdf', '-cl scad,pdf'),
      array('Inspect inside containers', '-inspectarchives', '-ia'),
      array('Do not open containers', '-noinspectarchives', '-nia'),
      array('Container nesting depth limit', '-maxarchivedepth ###', '-mad ###'),
      array('Inspect the structure of images', '-inspectimages', '-ii'),
      array('Do not inspect images', '-noinspectimages', '-nii'),
      array('Largest image accepted (in pixels)', '-maximagepixels ###', '-mip ###'),
      array('Largest image text chunk (in bytes)', '-maximagechunksize ###', '-mics ###'),
      array('Container entry count budget', '-maxarchiveentries ###', '-mae ###'),
      array('Container entry size budget (bytes)', '-maxarchiveentrysize ###', '-maes ###'),
      array('Container total size budget (bytes)', '-maxarchivetotalsize ###', '-mats ###'),
      array('Container expansion ratio limit', '-maxarchiveratio ###', '-mar ###'),
      array('Language signatures needed to decide', '-minlanguagesignatures ###', '-mls ###'),
      array('Shortest usable data signature', '-mindatasignature ###', '-mds ###'),
      array('Own created files as this user', '-owner www-data', '-ow www-data'),
      array('Own created files as this group', '-group www-data', '-gr www-data'),
      array('Leave created files owned by root', '-nopreserveownership', '-npo'),
      array('Do not use a packed hash index', '-nohashindex', '-nhi'),
      array('Rebuild the packed hash index', '-rebuildindex', '-ri'),
      array('Hashes needed before indexing', '-hashindexthreshold ###', '-hit ###'),
      array('Seconds to wait for an update host', '-connectiontimeout ###', '-ct ###'),
      array('Choose the update method', '-updatemethod git', '-um raw'),
      array('Perform definition update', '-updatedefinitions', '-ud'),
      array('Perform application update', '-updateapplication', '-ua'));
    $switchText = '';
    foreach ($switchTable as $switchRow) {
      $switchText = $switchText.$EOL.sprintf('  %-38s %s', $switchRow[0].':', $switchRow[1]).$EOL;
      if ($switchRow[2] !== '') $switchText = $switchText.sprintf('  %-38s %s', '', $switchRow[2]).$EOL; }
    $qsText1 = $EOL.'Quick Start Example:'.$EOL;
    $qsText2 = $EOL.'  C:\Path-To-PHP-Binary.exe C:\Path-To-ScanCore.php C:\Path-To-Scan\ -m [integer] -c [integer] -v -d'.$EOL;
    $HelpText = $verText1.$qsText1.$qsText2.$EOL.'Required Arguments Include:'.$EOL
      .$EOL.'  File or folder to scan:                /path/to/scan'.$EOL
      .$EOL.'Optional Arguments Include:'.$EOL.$switchText;
    $VersionText = $verText1.$verText2.$verText3.$verText4.$verText5.$verText6.$verText7.$verText8.$verText9.$verText10.$verText11; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($coreFilePath, $configFilePath, $defsFilePath, $originalRepo, $licenseText, $switchTable, $switchRow, $switchText, $qsText1, $qsText2, $verText1, $verText2, $verText3, $verText4, $verText5, $verText6, $verText7, $verText8, $verText9, $verText10, $verText11);
  return array($InformationBuilt, $SubText, $VersionText, $HelpText); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the value that follows a command line switch.
// / Previous versions read the next element without checking that it existed.
// / Reading past the end of the argument list produced an undefined array key warning.
function readArgumentValue($argumentList, $switchKey, $switchName) {
  // / Set variables.
  $ValueFound = FALSE;
  $ArgumentValue = '';
  if (isset($argumentList[$switchKey + 1])) {
    $ArgumentValue = sanitize($argumentList[$switchKey + 1]);
    if ($ArgumentValue !== '') $ValueFound = TRUE; }
  // / Warn rather than error, because the configured default remains perfectly usable.
  if (!$ValueFound) processOutput('The '.$switchName.' argument was supplied without a value, using the configured value instead.', 'warning', 0, TRUE, FALSE, FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($argumentList, $switchKey, $switchName);
  return array($ValueFound, $ArgumentValue); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to validate a numeric setting & clamp it into a workable range.
// / A chunk size of zero would make the file reader loop without ever advancing.
function validateNumericSetting($candidate, $defaultValue, $minimumValue, $settingName) {
  // / Set variables.
  $ValidatedSetting = intval($defaultValue);
  $candidateIsUsable = FALSE;
  if (is_numeric($candidate)) if (intval($candidate) >= $minimumValue) $candidateIsUsable = TRUE;
  if ($candidateIsUsable) $ValidatedSetting = intval($candidate);
  else processOutput('The '.$settingName.' value is missing or below the minimum of '.$minimumValue.', using '.intval($defaultValue).' instead.', 'warning', 0, TRUE, FALSE, FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($candidateIsUsable, $candidate, $defaultValue, $minimumValue, $settingName);
  return $ValidatedSetting; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to parse supplied command-line arguments.
// / The -configfile & -cf arguments are processed by the loadConfig() function.
// / The -defsfile & -df arguments are processed by the loadConfig() function.
function parseArgs($argumentList) {
  global $ArgsParsed, $ReportFile, $ReportDir, $MaxLogSize, $Debug, $Verbose, $ChunkSize, $MemoryLimit, $DefaultMemoryLimit, $DefaultChunkSize, $DefaultMaxLogSize, $PerformDefUpdate, $PerformAppUpdate, $VersionText, $HelpText, $ConfigFilePath, $PerformScan, $ScanLoc, $InstallDir, $MaxScanDepth, $FollowSymlinks, $RP;
  global $ClassifyContent, $EnabledClassifiers, $MinimumLanguageSignatures, $MinimumDataSignatureLength, $InspectArchivedContent;
  global $MaxArchiveEntries, $MaxArchiveEntrySize, $MaxArchiveTotalSize, $MaxArchiveCompressionRatio, $ConnectionTimeout, $UpdateMethod;
  global $ReportSuspicious, $PreserveOwnership, $OwnerUser, $OwnerGroup, $UseHashIndex, $HashIndexThreshold, $RebuildHashIndex;
  global $InspectImages, $MaxImagePixels, $MaxImageChunkSize, $MaxArchiveDepth;
  // / Set variables.
  $ArgsParsed = $PerformScan = $Recursion = FALSE;
  $RebuildHashIndex = FALSE;
  $PerformDefUpdate = $PerformAppUpdate = $showVersion = $showHelp = FALSE;
  $PathToScan = '';
  $valueFound = FALSE;
  $argumentValue = $currentSwitch = '';
  $scanPathSupplied = FALSE;
  // / Iterate the arguments once, recording every switch that was supplied.
  foreach ($argumentList as $argKey => $argValue) {
    redeclare($currentSwitch, normalizeSwitch($argValue));
    if ($currentSwitch === '-version' or $currentSwitch === '-ver') $showVersion = TRUE;
    if ($currentSwitch === '-h' or $currentSwitch === '-help') $showHelp = TRUE;
    if ($currentSwitch === '-debug' or $currentSwitch === '-d') $Debug = TRUE;
    if ($currentSwitch === '-verbose' or $currentSwitch === '-v') $Verbose = TRUE;
    if ($currentSwitch === '-recursion' or $currentSwitch === '-r') $Recursion = TRUE;
    if ($currentSwitch === '-norecursion' or $currentSwitch === '-nr') $Recursion = FALSE;
    if ($currentSwitch === '-followsymlinks' or $currentSwitch === '-fs') $FollowSymlinks = TRUE;
    if ($currentSwitch === '-nofollowsymlinks' or $currentSwitch === '-nfs') $FollowSymlinks = FALSE;
    if ($currentSwitch === '-classify' or $currentSwitch === '-cy') $ClassifyContent = TRUE;
    if ($currentSwitch === '-noclassify' or $currentSwitch === '-ncy') $ClassifyContent = FALSE;
    if ($currentSwitch === '-inspectarchives' or $currentSwitch === '-ia') $InspectArchivedContent = TRUE;
    if ($currentSwitch === '-noinspectarchives' or $currentSwitch === '-nia') $InspectArchivedContent = FALSE;
    if ($currentSwitch === '-inspectimages' or $currentSwitch === '-ii') $InspectImages = TRUE;
    if ($currentSwitch === '-noinspectimages' or $currentSwitch === '-nii') $InspectImages = FALSE;
    if ($currentSwitch === '-maximagepixels' or $currentSwitch === '-mip') {
      list($valueFound, $argumentValue) = readArgumentValue($argumentList, $argKey, $currentSwitch);
      if ($valueFound) $MaxImagePixels = $argumentValue; }
    if ($currentSwitch === '-maximagechunksize' or $currentSwitch === '-mics') {
      list($valueFound, $argumentValue) = readArgumentValue($argumentList, $argKey, $currentSwitch);
      if ($valueFound) $MaxImageChunkSize = $argumentValue; }
    if ($currentSwitch === '-maxarchivedepth' or $currentSwitch === '-mad') {
      list($valueFound, $argumentValue) = readArgumentValue($argumentList, $argKey, $currentSwitch);
      if ($valueFound) $MaxArchiveDepth = $argumentValue; }
    if ($currentSwitch === '-suspicious' or $currentSwitch === '-sus') $ReportSuspicious = TRUE;
    if ($currentSwitch === '-nosuspicious' or $currentSwitch === '-nsus') $ReportSuspicious = FALSE;
    if ($currentSwitch === '-nopreserveownership' or $currentSwitch === '-npo') $PreserveOwnership = FALSE;
    if ($currentSwitch === '-nohashindex' or $currentSwitch === '-nhi') $UseHashIndex = FALSE;
    if ($currentSwitch === '-rebuildindex' or $currentSwitch === '-ri') $RebuildHashIndex = TRUE;
    if ($currentSwitch === '-hashindexthreshold' or $currentSwitch === '-hit') {
      list($valueFound, $argumentValue) = readArgumentValue($argumentList, $argKey, $currentSwitch);
      if ($valueFound) $HashIndexThreshold = $argumentValue; }
    if ($currentSwitch === '-classifiers' or $currentSwitch === '-cl') {
      list($valueFound, $argumentValue) = readArgumentValue($argumentList, $argKey, $currentSwitch);
      // / An empty list is a real answer here, so a lone comma switches everything off.
      if ($valueFound) $EnabledClassifiers = array_filter(array_map('trim', explode(',', $argumentValue)), 'strlen'); }
    if ($currentSwitch === '-owner' or $currentSwitch === '-ow') {
      list($valueFound, $argumentValue) = readArgumentValue($argumentList, $argKey, $currentSwitch);
      if ($valueFound) $OwnerUser = $argumentValue; }
    if ($currentSwitch === '-group' or $currentSwitch === '-gr') {
      list($valueFound, $argumentValue) = readArgumentValue($argumentList, $argKey, $currentSwitch);
      if ($valueFound) $OwnerGroup = $argumentValue; }
    if ($currentSwitch === '-updatemethod' or $currentSwitch === '-um') {
      list($valueFound, $argumentValue) = readArgumentValue($argumentList, $argKey, $currentSwitch);
      if ($valueFound) $UpdateMethod = strtolower($argumentValue); }
    if ($currentSwitch === '-minlanguagesignatures' or $currentSwitch === '-mls') {
      list($valueFound, $argumentValue) = readArgumentValue($argumentList, $argKey, $currentSwitch);
      if ($valueFound) $MinimumLanguageSignatures = $argumentValue; }
    if ($currentSwitch === '-mindatasignature' or $currentSwitch === '-mds') {
      list($valueFound, $argumentValue) = readArgumentValue($argumentList, $argKey, $currentSwitch);
      if ($valueFound) $MinimumDataSignatureLength = $argumentValue; }
    if ($currentSwitch === '-maxarchiveentries' or $currentSwitch === '-mae') {
      list($valueFound, $argumentValue) = readArgumentValue($argumentList, $argKey, $currentSwitch);
      if ($valueFound) $MaxArchiveEntries = $argumentValue; }
    if ($currentSwitch === '-maxarchiveentrysize' or $currentSwitch === '-maes') {
      list($valueFound, $argumentValue) = readArgumentValue($argumentList, $argKey, $currentSwitch);
      if ($valueFound) $MaxArchiveEntrySize = $argumentValue; }
    if ($currentSwitch === '-maxarchivetotalsize' or $currentSwitch === '-mats') {
      list($valueFound, $argumentValue) = readArgumentValue($argumentList, $argKey, $currentSwitch);
      if ($valueFound) $MaxArchiveTotalSize = $argumentValue; }
    if ($currentSwitch === '-maxarchiveratio' or $currentSwitch === '-mar') {
      list($valueFound, $argumentValue) = readArgumentValue($argumentList, $argKey, $currentSwitch);
      if ($valueFound) $MaxArchiveCompressionRatio = $argumentValue; }
    if ($currentSwitch === '-connectiontimeout' or $currentSwitch === '-ct') {
      list($valueFound, $argumentValue) = readArgumentValue($argumentList, $argKey, $currentSwitch);
      if ($valueFound) $ConnectionTimeout = $argumentValue; }
    if ($currentSwitch === '-updatedefinitions' or $currentSwitch === '-ud') $PerformDefUpdate = TRUE;
    if ($currentSwitch === '-updateapplication' or $currentSwitch === '-ua') $PerformAppUpdate = TRUE;
    if ($currentSwitch === '-memorylimit' or $currentSwitch === '-m') {
      list($valueFound, $argumentValue) = readArgumentValue($argumentList, $argKey, $currentSwitch);
      if ($valueFound) $MemoryLimit = $argumentValue; }
    if ($currentSwitch === '-chunksize' or $currentSwitch === '-c') {
      list($valueFound, $argumentValue) = readArgumentValue($argumentList, $argKey, $currentSwitch);
      if ($valueFound) $ChunkSize = $argumentValue; }
    if ($currentSwitch === '-maxlogsize' or $currentSwitch === '-ml') {
      list($valueFound, $argumentValue) = readArgumentValue($argumentList, $argKey, $currentSwitch);
      if ($valueFound) $MaxLogSize = $argumentValue; }
    if ($currentSwitch === '-maxdepth' or $currentSwitch === '-md') {
      list($valueFound, $argumentValue) = readArgumentValue($argumentList, $argKey, $currentSwitch);
      if ($valueFound) $MaxScanDepth = $argumentValue; }
    if ($currentSwitch === '-reportfile' or $currentSwitch === '-rf' or $currentSwitch === '-logfile' or $currentSwitch === '-lf') {
      list($valueFound, $argumentValue) = readArgumentValue($argumentList, $argKey, $currentSwitch);
      if ($valueFound) $ReportFile = resolvePath($argumentValue, $ReportDir); } }
  // / Validate every numeric setting & clamp it into a range the scanner can actually use.
  $MemoryLimit = validateNumericSetting($MemoryLimit, $DefaultMemoryLimit, 65536, 'MemoryLimit');
  $ChunkSize = validateNumericSetting($ChunkSize, $DefaultChunkSize, 4096, 'ChunkSize');
  $MaxLogSize = validateNumericSetting($MaxLogSize, $DefaultMaxLogSize, 4096, 'MaxLogSize');
  $MaxScanDepth = validateNumericSetting($MaxScanDepth, 64, 1, 'MaxScanDepth');
  $HashIndexThreshold = validateNumericSetting($HashIndexThreshold, 10000, 1, 'HashIndexThreshold');
  // / Validate every override the same way, so a bad argument falls back rather than breaks.
  $MinimumLanguageSignatures = validateNumericSetting($MinimumLanguageSignatures, 2, 1, 'MinimumLanguageSignatures');
  $MinimumDataSignatureLength = validateNumericSetting($MinimumDataSignatureLength, 4, 1, 'MinimumDataSignatureLength');
  $MaxArchiveEntries = validateNumericSetting($MaxArchiveEntries, 4096, 1, 'MaxArchiveEntries');
  $MaxArchiveDepth = validateNumericSetting($MaxArchiveDepth, 5, 1, 'MaxArchiveDepth');
  $MaxImagePixels = validateNumericSetting($MaxImagePixels, 16384*16384, 1, 'MaxImagePixels');
  $MaxImageChunkSize = validateNumericSetting($MaxImageChunkSize, 1024*1024*8, 1024, 'MaxImageChunkSize');
  $MaxArchiveEntrySize = validateNumericSetting($MaxArchiveEntrySize, 1024*1024*8, 1024, 'MaxArchiveEntrySize');
  $MaxArchiveTotalSize = validateNumericSetting($MaxArchiveTotalSize, 1024*1024*64, 1024, 'MaxArchiveTotalSize');
  $MaxArchiveCompressionRatio = validateNumericSetting($MaxArchiveCompressionRatio, 200, 2, 'MaxArchiveCompressionRatio');
  $ConnectionTimeout = validateNumericSetting($ConnectionTimeout, 15, 1, 'ConnectionTimeout');
  // / Fall back to the git update method when an override names something else.
  if ($UpdateMethod !== 'git' && $UpdateMethod !== 'raw') {
    processOutput('The UpdateMethod override is not git or raw, using git instead.', 'warning', 0, TRUE, FALSE, FALSE);
    $UpdateMethod = 'git'; }
  // / A chunk larger than the memory limit would defeat the point of chunking at all.
  if ($ChunkSize > $MemoryLimit) {
    processOutput('The ChunkSize exceeds the MemoryLimit, reducing the ChunkSize to match.', 'warning', 0, TRUE, FALSE, FALSE);
    $ChunkSize = $MemoryLimit; }
  // / Detect if version or help information is being requested.
  if ($showVersion or $showHelp) {
    list ($InformationBuilt, $SubText, $VersionText, $HelpText) = buildHelpInformation();
    if ($InformationBuilt) processOutput('Built version information.', 'log', 0, FALSE, FALSE, FALSE);
    else processOutput('Cannot build version information!', 'warning', 0, TRUE, TRUE, FALSE);
    if ($showVersion) processOutput($VersionText, 'log', 0, TRUE, TRUE, FALSE);
    if ($showHelp) processOutput($HelpText, 'log', 0, TRUE, TRUE, FALSE);
    $ArgsParsed = TRUE; }
  // / Detect if an update is being requested.
  if ($PerformDefUpdate or $PerformAppUpdate) {
    processOutput('Starting ScanCore updater!', 'log', 0, TRUE, TRUE, FALSE);
    $ArgsParsed = TRUE; }
  // / Work out what should be scanned when no other operation was requested.
  if (!$PerformDefUpdate && !$PerformAppUpdate && !$showVersion && !$showHelp) {
    if (isset($argumentList[1])) {
      $PathToScan = sanitize($argumentList[1]);
      $scanPathSupplied = TRUE; }
    // / Fall back to the configured scan location before reporting that nothing was supplied.
    if (!$scanPathSupplied && is_string($ScanLoc) && $ScanLoc !== '') {
      $PathToScan = resolvePath($ScanLoc, $InstallDir);
      $scanPathSupplied = TRUE;
      processOutput('No scan path was supplied, falling back to the configured ScanLoc.', 'warning', 0, TRUE, FALSE, FALSE); }
    if (!$scanPathSupplied) processOutput('There were no arguments set!', 'error', 100, TRUE, TRUE, FALSE);
    else {
      // / Detect if a valid path to scan was supplied.
      if (!file_exists($PathToScan)) processOutput('The specified file was not found! The first argument must be a valid file or directory path!', 'error', 300, TRUE, TRUE, FALSE);
      else {
        if (!is_readable($PathToScan)) processOutput('The specified path cannot be read:  '.$PathToScan, 'error', 310, TRUE, TRUE, FALSE);
        else {
          // / Output status information.
          processOutput('Starting ScanCore!', 'log', 0, TRUE, TRUE, FALSE);
          processOutput('Loaded configuration file:  '.$ConfigFilePath, 'log', 0, TRUE, FALSE, FALSE);
          processOutput('The ChunkSize is:  '.$ChunkSize, 'log', 0, FALSE, FALSE, FALSE);
          processOutput('The MemoryLimit is:  '.$MemoryLimit, 'log', 0, FALSE, FALSE, FALSE);
          $ArgsParsed = $PerformScan = TRUE; } } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($argKey, $argValue, $currentSwitch, $showVersion, $showHelp, $valueFound, $argumentValue, $scanPathSupplied, $InformationBuilt, $SubText, $argumentList);
  return array($ArgsParsed, $PerformScan, $PathToScan, $MemoryLimit, $ChunkSize, $Debug, $Verbose, $Recursion, $ReportFile, $MaxLogSize, $PerformDefUpdate, $PerformAppUpdate); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to confirm that a path is safe to delete before anything gets deleted.
// / The cleaner is only ever aimed at the temporary update directories.
// / A blank or misconfigured repository name must never point it somewhere else.
function cleanTargetIsSafe($location) {
  global $InstallDir, $SEP;
  // / Set variables.
  $TargetIsSafe = FALSE;
  $resolvedLocation = '';
  $resolvedInstall = realpath($InstallDir);
  if (is_string($location) && $location !== '') $resolvedLocation = realpath($location);
  // / A path that does not exist is safe, because there is nothing there to remove.
  if ($resolvedLocation === FALSE) $TargetIsSafe = TRUE;
  if (is_string($resolvedLocation) && $resolvedLocation !== '' && $resolvedInstall !== FALSE) {
    // / The target must sit underneath the installation directory & must not be it.
    if (str_starts_with($resolvedLocation.$SEP, $resolvedInstall.$SEP)) if ($resolvedLocation !== $resolvedInstall) $TargetIsSafe = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($resolvedLocation, $resolvedInstall, $location);
  return $TargetIsSafe; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove files & folders.
// / A symbolic link is removed as a link so the cleaner never walks outside its target.
function clean($Location) {
  global $SEP;
  // / Set variables.
  $LocationCleaned = FALSE;
  $entryName = '';
  $entryList = array();
  $scanResult = FALSE;
  // / Refuse to touch anything outside the installation directory.
  if (!cleanTargetIsSafe($Location)) processOutput('Refused to clean a path outside the installation directory:  '.$Location, 'warning', 0, TRUE, FALSE, FALSE);
  else {
    // / Detect if the location is a folder that is not merely a link to one.
    if (is_dir($Location) && !is_link($Location)) {
      $scanResult = @scandir($Location);
      // / Scan the folder for contents, tolerating a folder we are not allowed to read.
      if (is_array($scanResult)) {
        $entryList = array_diff($scanResult, array('..', '.'));
        // / Iterate through the contents of the folder.
        foreach ($entryList as $entryName) {
          // / If this object is a folder, run this function on it.
          if (is_dir($Location.$SEP.$entryName) && !is_link($Location.$SEP.$entryName)) clean($Location.$SEP.$entryName);
          // / If this object is a file or a link, delete it.
          else @unlink($Location.$SEP.$entryName); } }
      // / Try to delete the folder now that we've deleted the contents.
      if (is_dir($Location)) @rmdir($Location); }
    // / If the location is a file or a link, delete it.
    if (file_exists($Location) && !is_dir($Location)) @unlink($Location);
    if (is_link($Location)) @unlink($Location);
    // / Check if the location was deleted.
    if (!is_dir($Location) && !file_exists($Location)) $LocationCleaned = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($entryName, $entryList, $scanResult);
  return array($LocationCleaned); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify internet connectivity before attempting to perform update operations.
// / Type must be either 'application' or 'definition'.
// / This function is limited to the two domains defined in the configuration file.
// / A scheme or a trailing path is stripped, because a socket only wants a host name.
function connectionSuccess($type) {
  global $ApplicationUpdateDomain, $DefinitionUpdateDomain, $ConnectionTimeout;
  // / Set variables.
  $ConnectionResult = FALSE;
  $connection = FALSE;
  $urlToCheck = '';
  $errorNumber = 0;
  $errorText = '';
  if ($type === 'application') $urlToCheck = $ApplicationUpdateDomain;
  if ($type === 'definition') $urlToCheck = $DefinitionUpdateDomain;
  // / Reduce whatever the administrator wrote down to a bare host name.
  $urlToCheck = preg_replace('#^[a-zA-Z][a-zA-Z0-9+.\-]*://#', '', trim(sanitize($urlToCheck)));
  $urlToCheck = explode('/', $urlToCheck)[0];
  $urlToCheck = explode(':', $urlToCheck)[0];
  if ($urlToCheck !== '') $connection = @fsockopen($urlToCheck, 443, $errorNumber, $errorText, $ConnectionTimeout);
  if ($connection !== FALSE) {
    fclose($connection);
    $ConnectionResult = TRUE; }
  // / The caller raises the numbered error, so an unreachable host is only a warning here.
  if (!$ConnectionResult) processOutput('Cannot reach the configured update host:  '.$urlToCheck, 'warning', 0, TRUE, FALSE, FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($connection, $urlToCheck, $errorNumber, $errorText, $type);
  return $ConnectionResult; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to download a single file from a remote repository.
// / Previous versions tested a remote address with file_exists(), which never succeeds.
// / The built in downloader is tried first & cURL is used as the fallback.
function fetchRemoteFile($sourceUrl, $destinationFile) {
  global $ConnectionTimeout;
  // / Set variables.
  $FileFetched = FALSE;
  $fetchedData = FALSE;
  $streamContext = NULL;
  $writeResult = FALSE;
  $shellResult = '';
  // / Try the built in downloader first, which needs no external binary.
  if (ini_get('allow_url_fopen')) {
    $streamContext = stream_context_create(array('http' => array('timeout' => $ConnectionTimeout, 'follow_location' => 1, 'user_agent' => 'ScanCore')));
    $fetchedData = @file_get_contents($sourceUrl, FALSE, $streamContext); }
  // / A repository that answers with its own not found page is treated as a failed download.
  if (is_string($fetchedData)) if (trim($fetchedData) === '' or trim($fetchedData) === '404: Not Found') $fetchedData = FALSE;
  if (is_string($fetchedData)) {
    $writeResult = @file_put_contents($destinationFile, $fetchedData);
    if ($writeResult !== FALSE && $writeResult > 0) $FileFetched = TRUE; }
  // / Fall back to cURL when the built in downloader is unavailable or came back empty.
  if (!$FileFetched) {
    processOutput('Attempting download with cURL against URL:  '.$sourceUrl, 'log', 0, FALSE, FALSE, FALSE);
    $shellResult = shell_exec('curl -Ls --max-time '.intval($ConnectionTimeout).' '.escapeshellarg($sourceUrl).' --output '.escapeshellarg($destinationFile).' 2>&1');
    if (file_exists($destinationFile)) if (filesize($destinationFile) > 0) {
      // / Confirm that cURL did not simply save the repository not found page to disk.
      redeclare($fetchedData, @file_get_contents($destinationFile));
      if (is_string($fetchedData)) if (trim($fetchedData) !== '404: Not Found') $FileFetched = TRUE; } }
  if (!$FileFetched) if (file_exists($destinationFile)) @unlink($destinationFile);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($fetchedData, $streamContext, $writeResult, $shellResult, $sourceUrl, $destinationFile);
  return $FileFetched; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to clone an update repository into a named destination directory.
// / Previous versions cloned into the working directory & ignored the configured branch.
// / Every value reaching the shell is escaped, because they arrive from a text file.
function cloneRepository($repositoryUrl, $branchName, $destinationDir) {
  // / Set variables.
  $RepositoryCloned = FALSE;
  $shellResult = '';
  $gitAvailable = '';
  // / Confirm that git is actually installed before relying upon it.
  $gitAvailable = shell_exec('git --version 2>&1');
  if (is_string($gitAvailable) && stripos($gitAvailable, 'git version') !== FALSE) {
    $shellResult = shell_exec('git clone --depth 1 --branch '.escapeshellarg($branchName).' -- '.escapeshellarg($repositoryUrl).' '.escapeshellarg($destinationDir).' 2>&1');
    if (is_dir($destinationDir)) $RepositoryCloned = TRUE;
    if (!$RepositoryCloned) processOutput('The git clone operation did not produce a directory:  '.trim(strval($shellResult)), 'warning', 0, TRUE, FALSE, FALSE); }
  else processOutput('Cannot find a usable git binary, consider setting $UpdateMethod to raw.', 'warning', 0, TRUE, FALSE, FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($shellResult, $gitAvailable, $repositoryUrl, $branchName, $destinationDir);
  return $RepositoryCloned; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run a syntax check against a PHP file before it is trusted.
// / A downloaded core that does not parse would take the installation down with it.
// / A server with no shell cannot lint, so the check reports that it was not performed
// / rather than reporting a failure it never actually observed.
function lintPhpFile($phpFile) {
  // / Set variables.
  $LintPerformed = $LintPassed = FALSE;
  $LintNote = '';
  $shellResult = '';
  if (!function_exists('shell_exec')) $LintNote = 'no shell is available to lint with';
  else {
    $shellResult = shell_exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($phpFile).' 2>&1');
    if (!is_string($shellResult)) $LintNote = 'the lint command returned nothing';
    else {
      $LintPerformed = TRUE;
      if (stripos($shellResult, 'No syntax errors detected') !== FALSE) $LintPassed = TRUE;
      else $LintNote = trim($shellResult); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($shellResult, $phpFile);
  return array($LintPerformed, $LintPassed, $LintNote); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to count the definitions in a file that could actually match something.
// / Used to verify a staged definitions file before it replaces a working one.
function countUsableDefinitions($definitionsFile) {
  // / Set variables.
  $UsableDefinitions = 0;
  $rawLines = array();
  $lineFields = array();
  $fieldIndex = 0;
  $lineIsUsable = FALSE;
  if (file_exists($definitionsFile)) {
    $rawLines = file($definitionsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($rawLines)) $rawLines = array();
    foreach ($rawLines as $rawLine) {
      redeclare($lineIsUsable, FALSE);
      if (trim($rawLine) !== '' && substr(trim($rawLine), 0, 1) !== '#') {
        list($lineIsUsable, $lineFields) = parseDefinitionRecord(trim($rawLine)); }
      if ($lineIsUsable) $UsableDefinitions++; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($rawLines, $rawLine, $lineFields, $fieldIndex, $lineIsUsable, $definitionsFile);
  return $UsableDefinitions; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to place a downloaded file beside its target as a staged replacement.
// / Nothing that is already installed is touched at this point.
function stageUpdateFile($sourceFile, $targetFile) {
  // / Set variables.
  $FileStaged = FALSE;
  $stagedFile = $targetFile.'.new';
  if (file_exists($stagedFile)) @unlink($stagedFile);
  if (!is_dir(dirname($targetFile))) @mkdir(dirname($targetFile), 0755, TRUE);
  if (file_exists($sourceFile)) $FileStaged = @copy($sourceFile, $stagedFile);
  if ($FileStaged) applyOwnership($stagedFile, FALSE);
  if (!$FileStaged) processOutput('Cannot stage a downloaded file at:  '.$stagedFile, 'warning', 0, TRUE, FALSE, FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($stagedFile, $sourceFile, $targetFile);
  return $FileStaged; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify a staged file before anything installed gets moved aside.
// / A file that fails here costs nothing, because the installation is still untouched.
function verifyStagedFile($targetFile) {
  // / Set variables.
  $FileVerified = FALSE;
  $VerificationNote = '';
  $stagedFile = $targetFile.'.new';
  $stagedSize = 0;
  $stagedHead = '';
  $lintPerformed = $lintPassed = FALSE;
  $lintNote = '';
  $fileExtension = fileExtensionOf($targetFile);
  if (!file_exists($stagedFile)) $VerificationNote = 'the staged file is missing';
  else {
    $stagedSize = intval(@filesize($stagedFile));
    if ($stagedSize < 1) $VerificationNote = 'the staged file is empty';
    else {
      $FileVerified = TRUE;
      // / A repository that answered with its own not found page is not an update.
      $stagedHead = trim(strval(@file_get_contents($stagedFile, FALSE, NULL, 0, 512)));
      if ($stagedHead === '404: Not Found' or $stagedHead === '') {
        $FileVerified = FALSE;
        $VerificationNote = 'the staged file carries a repository not found page'; }
      // / A PHP file has to parse before it is allowed anywhere near the installation.
      if ($FileVerified && $fileExtension === 'php') {
        list($lintPerformed, $lintPassed, $lintNote) = lintPhpFile($stagedFile);
        if (!$lintPerformed) processOutput('Cannot syntax check a staged file, continuing without that check:  '.$lintNote, 'warning', 0, TRUE, FALSE, FALSE);
        else {
          if (!$lintPassed) {
            $FileVerified = FALSE;
            $VerificationNote = 'the staged file does not parse:  '.$lintNote; } } }
      // / A definitions file has to yield something matchable before it replaces one.
      if ($FileVerified && $fileExtension === 'def') if (countUsableDefinitions($stagedFile) < 1) if (basename($targetFile) !== 'ScanCore_Classifiers.def') {
        $FileVerified = FALSE;
        $VerificationNote = 'the staged definitions file contains nothing matchable'; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($stagedFile, $stagedSize, $stagedHead, $lintPerformed, $lintPassed, $lintNote, $fileExtension, $targetFile);
  return array($FileVerified, $VerificationNote); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to move a verified staged file into place, keeping the old one aside.
// / The old file is renamed rather than deleted, so a failed test can put it back.
function swapStagedFile($targetFile) {
  // / Set variables.
  $FileSwapped = FALSE;
  $stagedFile = $targetFile.'.new';
  $retiredFile = $targetFile.'.old';
  $oldWasMoved = TRUE;
  // / Record who owned the installed file & what its mode was, before it moves aside.
  $targetIdentity = captureFileIdentity($targetFile);
  if (file_exists($retiredFile)) @unlink($retiredFile);
  // / A target that does not exist yet is a new file & has nothing to retire.
  if (file_exists($targetFile)) $oldWasMoved = @rename($targetFile, $retiredFile);
  if (!$oldWasMoved) processOutput('Cannot move the installed file aside:  '.$targetFile, 'warning', 0, TRUE, FALSE, FALSE);
  else {
    $FileSwapped = @rename($stagedFile, $targetFile);
    // / Give the replacement the same owner & mode the file it replaced had. An update
    // / that quietly made a file root owned would stop the service updating ever again.
    if ($FileSwapped) {
      if ($targetIdentity['exists']) restoreFileIdentity($targetFile, $targetIdentity);
      else applyOwnership($targetFile, FALSE); }
    // / Put the old file straight back if the staged file would not move into place.
    if (!$FileSwapped) if (file_exists($retiredFile)) @rename($retiredFile, $targetFile); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($stagedFile, $retiredFile, $oldWasMoved, $targetIdentity, $targetFile);
  return $FileSwapped; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to put the retired file back over a target that failed its test.
function rollbackStagedFile($targetFile) {
  // / Set variables.
  $FileRestored = FALSE;
  $retiredFile = $targetFile.'.old';
  if (!file_exists($retiredFile)) $FileRestored = TRUE;
  else {
    if (file_exists($targetFile)) @unlink($targetFile);
    $FileRestored = @rename($retiredFile, $targetFile);
    if (!$FileRestored) processOutput('Cannot restore the previous file, it remains at:  '.$retiredFile, 'error', 760, TRUE, TRUE, FALSE); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($retiredFile, $targetFile);
  return $FileRestored; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove the retired file once the new one has proven itself.
function commitStagedFile($targetFile) {
  // / Set variables.
  $FileCommitted = TRUE;
  $retiredFile = $targetFile.'.old';
  if (file_exists($retiredFile)) $FileCommitted = @unlink($retiredFile);
  if (!$FileCommitted) processOutput('Cannot remove the retired file, it remains at:  '.$retiredFile, 'warning', 0, TRUE, FALSE, FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($retiredFile, $targetFile);
  return $FileCommitted; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove a staged file that will never be installed.
function discardStagedFile($targetFile) {
  // / Set variables.
  $FileDiscarded = TRUE;
  $stagedFile = $targetFile.'.new';
  if (file_exists($stagedFile)) $FileDiscarded = @unlink($stagedFile);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($stagedFile, $targetFile);
  return $FileDiscarded; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to prove that the installation still runs after an update was applied.
// / The freshly installed core is asked for its version in a separate process, because
// / the running process still holds the code it was started with.
function testInstalledApplication() {
  // / Set variables.
  $TestPerformed = $TestPassed = FALSE;
  $TestNote = '';
  global $InstallDir, $SEP;
  $coreFile = $InstallDir.$SEP.'ScanCore.php';
  $shellResult = '';
  if (!function_exists('shell_exec')) $TestNote = 'no shell is available to test with';
  else {
    if (!file_exists($coreFile)) $TestNote = 'the installed core is missing';
    else {
      $shellResult = shell_exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($coreFile).' -version 2>&1');
      if (!is_string($shellResult)) $TestNote = 'the installed core returned nothing';
      else {
        $TestPerformed = TRUE;
        // / A working core opens its version screen with its own name & version number.
        if (stripos($shellResult, 'ScanCore v') !== FALSE) $TestPassed = TRUE;
        else $TestNote = trim($shellResult); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($coreFile, $shellResult);
  return array($TestPerformed, $TestPassed, $TestNote); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to install definition updates.
// / The new definitions are staged beside the working ones, verified, then swapped in.
// / The previous file is retired rather than deleted, so a failed test can restore it.
// / Nothing installed is touched until every staged file has passed verification.
function updateDefinitions() {
  global $DefinitionUpdates, $DefinitionUpdateURL, $DefinitionsUpdateSubscriptions, $DefsFile, $DefInstallDir, $DefGitDir, $UpdateMethod, $SEP, $EOL, $RP, $DefinitionBranchName;
  // / Set variables.
  $UpdateDefinitionsComplete = $UpdateDefinitionsErrors = FALSE;
  $subData = $subText = $verificationNote = '';
  $subCount = $subTotal = $installedCount = 0;
  $connectionResult = $sourceReady = $stagingReady = $fileVerified = $fileSwapped = FALSE;
  $defSubs = $defSubFile = $rawDefURL = $subData1 = '';
  $subTotal = count($DefinitionsUpdateSubscriptions);
  $subText = buildSubscriptionText($DefinitionsUpdateSubscriptions);
  // / Only perform definition updates if they are enabled in the configuration file.
  if (!$DefinitionUpdates) processOutput('Definition updates are disabled in the configuration file.', 'warning', 0, TRUE, TRUE, FALSE);
  else {
    processOutput('Starting definition update. Update method is:  '.$UpdateMethod, 'log', 0, TRUE, FALSE, FALSE);
    processOutput('Preparing to install the following Definition Subscriptions:  '.$subText, 'log', 0, FALSE, FALSE, FALSE);
    processOutput('Cleaning update environment.', 'log', 0, TRUE, FALSE, FALSE);
    // / Remove anything a previous update may have left behind before starting a new one.
    clean($DefGitDir);
    clean($DefInstallDir);
    discardStagedFile($DefsFile);
    processOutput('Verifying network connectivity.', 'log', 0, FALSE, FALSE, FALSE);
    $connectionResult = connectionSuccess('definition');
    if (!$connectionResult) processOutput('Cannot verify network connectivity for definition updates!', 'error', 400, TRUE, TRUE, FALSE);
    else {
      processOutput('Verified network connectivity.', 'log', 0, FALSE, FALSE, FALSE);
      // / Download the latest definitions from the configured repository.
      if ($UpdateMethod === 'raw') {
        processOutput('Creating a folder at:  '.$DefInstallDir, 'log', 0, FALSE, FALSE, FALSE);
        $sourceReady = @mkdir($DefInstallDir, 0755, TRUE);
        if ($sourceReady) foreach ($DefinitionsUpdateSubscriptions as $defSubs) {
          redeclare($defSubFile, $DefInstallDir.$SEP.'ScanCore_'.basename(strval($defSubs)).'.def');
          redeclare($rawDefURL, $DefinitionUpdateURL.'/raw/'.$DefinitionBranchName.'/ScanCore_'.strval($defSubs).'.def');
          processOutput('Attempting download against URL:  '.$rawDefURL, 'log', 0, FALSE, FALSE, FALSE);
          if (!fetchRemoteFile($rawDefURL, $defSubFile)) processOutput('Cannot download the definition subscription:  '.$defSubs, 'error', 410, TRUE, FALSE, FALSE); } }
      // / Perform the definition update using git, when that is the configured method.
      if ($UpdateMethod === 'git') $sourceReady = cloneRepository($DefinitionUpdateURL, $DefinitionBranchName, $DefInstallDir);
      // / Only continue with the update if the previous operation produced a folder.
      if (is_dir($DefInstallDir)) {
        // / Copy an index.html file into the new folder as document root protection.
        if (file_exists($RP.$SEP.'index.html')) @copy($RP.$SEP.'index.html', $DefInstallDir.$SEP.'index.html');
        // / Remove the git metadata directory, which we never need to keep around.
        if (is_dir($DefGitDir)) clean($DefGitDir);
        // / Combine every subscribed definition file into one body of text.
        foreach ($DefinitionsUpdateSubscriptions as $defSubs) {
          redeclare($defSubFile, $DefInstallDir.$SEP.'ScanCore_'.basename(strval($defSubs)).'.def');
          if (file_exists($defSubFile)) {
            processOutput('Loading Definition Subscription file:  '.$defSubFile, 'log', 0, FALSE, FALSE, FALSE);
            $subCount++;
            redeclare($subData1, @file_get_contents($defSubFile));
            if (is_string($subData1)) $subData = $subData.$EOL.$subData1; } }
        // / Stage the combined definitions beside the working file, which stays untouched.
        processOutput('Staging Combined Definitions file:  '.$DefsFile.'.new', 'log', 0, FALSE, FALSE, FALSE);
        if (trim($subData) === '') processOutput('The combined definition data was empty, the existing definitions were kept!', 'error', 420, TRUE, TRUE, FALSE);
        else {
          $stagingReady = (@file_put_contents($DefsFile.'.new', $subData) !== FALSE);
          if ($stagingReady) applyOwnership($DefsFile.'.new', FALSE);
          if (!$stagingReady) processOutput('Cannot write the staged definitions file:  '.$DefsFile.'.new', 'error', 430, TRUE, TRUE, FALSE);
          else {
            // / Verify the staged file before the working definitions are moved aside.
            list($fileVerified, $verificationNote) = verifyStagedFile($DefsFile);
            if (!$fileVerified) {
              discardStagedFile($DefsFile);
              processOutput('The staged definitions failed verification, the existing definitions were kept because '.$verificationNote.'!', 'error', 440, TRUE, TRUE, FALSE); }
            else {
              processOutput('Verified the staged definitions file.', 'log', 0, TRUE, FALSE, FALSE);
              // / Retire the working file & move the staged file into its place.
              $fileSwapped = swapStagedFile($DefsFile);
              if (!$fileSwapped) processOutput('Cannot move the staged definitions into place:  '.$DefsFile, 'error', 430, TRUE, TRUE, FALSE);
              else {
                // / Test what is now installed, rather than what we believed we installed.
                if (countUsableDefinitions($DefsFile) < 1) {
                  rollbackStagedFile($DefsFile);
                  processOutput('The installed definitions failed their test & the previous definitions were restored!', 'error', 450, TRUE, TRUE, FALSE); }
                else {
                  // / Remove the retired file only once the new one has proven itself.
                  commitStagedFile($DefsFile);
                  $installedCount = $subCount;
                  $UpdateDefinitionsComplete = TRUE; } } } } } } }
    // / Remove the temporary update directory whether or not the update succeeded.
    clean($DefGitDir);
    clean($DefInstallDir); }
  // / Report that some subscribed definition files never arrived, even on a good update.
  if ($subCount !== $subTotal) {
    $UpdateDefinitionsErrors = TRUE;
    processOutput('Only '.countWithNoun($subCount, 'file', 'files').' of '.$subTotal.' subscribed definition files were installed.', 'warning', 0, TRUE, TRUE, FALSE); }
  discardStagedFile($DefsFile);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($defSubs, $defSubFile, $subData, $subData1, $subCount, $subTotal, $installedCount, $rawDefURL, $subText, $connectionResult, $sourceReady, $stagingReady, $fileVerified, $fileSwapped, $verificationNote);
  return array($UpdateDefinitionsComplete, $UpdateDefinitionsErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to backup the configuration file prior to commencing application updates.
function backupConfig() {
  global $ConfigFile;
  // / Set variables.
  $ConfigCopied = FALSE;
  $configInc = 0;
  $backupConfigFile = $ConfigFile.'_Backup_'.$configInc.'.php';
  // / Find an unused name so an earlier backup never gets written over.
  while (file_exists($backupConfigFile)) {
    $configInc++;
    redeclare($backupConfigFile, $ConfigFile.'_Backup_'.$configInc.'.php'); }
  // / Copy the configuration file to a backup.
  processOutput('Backing up the existing configuration file to:  '.$backupConfigFile, 'log', 0, FALSE, FALSE, FALSE);
  if (file_exists($ConfigFile)) $ConfigCopied = @copy($ConfigFile, $backupConfigFile);
  if ($ConfigCopied) applyOwnership($backupConfigFile, FALSE);
  // / Only proceed if the configuration file was backed up.
  if (!$ConfigCopied) processOutput('Cannot backup the existing configuration file to:  '.$backupConfigFile, 'error', 800, TRUE, TRUE, FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($configInc, $backupConfigFile);
  return array($ConfigCopied); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to install application updates.
// / Every subscribed file is downloaded, staged beside its target & verified first.
// / Nothing installed is moved until the whole set has passed, so a single bad file
// / cannot leave half an update in place.
// / The installation is then tested & the retired files are restored if that test fails.
function updateApplication() {
  global $ApplicationUpdates, $ApplicationUpdateURL, $ApplicationUpdateSubscriptions, $InstallDir, $AppInstallDir, $AppGitDir, $UpdateMethod, $SEP, $RP, $ApplicationBranchName;
  // / Set variables.
  $UpdateApplicationComplete = $UpdateApplicationErrors = FALSE;
  $connectionResult = $sourceReady = $configCopied = $stagingFailed = $verificationFailed = FALSE;
  $swapFailed = $testPerformed = $testPassed = FALSE;
  $appSubs = $appSubFile = $rawAppURL = $relativeName = $targetFile = $verificationNote = $testNote = '';
  $stagedTargets = array();
  $subCount = $installedCount = 0;
  $subTotal = count($ApplicationUpdateSubscriptions);
  // / Only perform application updates if they are enabled in the configuration file.
  if (!$ApplicationUpdates) processOutput('Application updates are disabled in the configuration file.', 'warning', 0, TRUE, TRUE, FALSE);
  else {
    processOutput('Starting application update. Update method is:  '.$UpdateMethod, 'log', 0, TRUE, FALSE, FALSE);
    list($configCopied) = backupConfig();
    if (!$configCopied) processOutput('Application update was aborted because the existing configuration file could not be backed up!', 'error', 900, TRUE, TRUE, FALSE);
    else {
      processOutput('Cleaning update environment.', 'log', 0, TRUE, FALSE, FALSE);
      // / Remove anything a previous update may have left behind before starting a new one.
      clean($AppGitDir);
      clean($AppInstallDir);
      foreach ($ApplicationUpdateSubscriptions as $appSubs) {
        redeclare($relativeName, str_replace('/', $SEP, strval($appSubs)));
        if (!str_contains($relativeName, '..')) discardStagedFile($InstallDir.$SEP.$relativeName); }
      processOutput('Verifying network connectivity.', 'log', 0, FALSE, FALSE, FALSE);
      $connectionResult = connectionSuccess('application');
      if (!$connectionResult) processOutput('Cannot verify network connectivity for application updates!', 'error', 700, TRUE, TRUE, FALSE);
      else {
        processOutput('Verified network connectivity.', 'log', 0, FALSE, FALSE, FALSE);
        // / Download the latest application files from the configured repository.
        if ($UpdateMethod === 'raw') {
          processOutput('Creating a folder at:  '.$AppInstallDir, 'log', 0, FALSE, FALSE, FALSE);
          $sourceReady = @mkdir($AppInstallDir, 0755, TRUE);
          if ($sourceReady) foreach ($ApplicationUpdateSubscriptions as $appSubs) {
            redeclare($relativeName, str_replace('/', $SEP, strval($appSubs)));
            redeclare($appSubFile, $AppInstallDir.$SEP.$relativeName);
            redeclare($rawAppURL, $ApplicationUpdateURL.'/raw/'.$ApplicationBranchName.'/'.strval($appSubs));
            if (!is_dir(dirname($appSubFile))) @mkdir(dirname($appSubFile), 0755, TRUE);
            processOutput('Attempting download against URL:  '.$rawAppURL, 'log', 0, FALSE, FALSE, FALSE);
            if (!fetchRemoteFile($rawAppURL, $appSubFile)) processOutput('Cannot download the application file:  '.$appSubs, 'error', 710, TRUE, FALSE, FALSE); } }
        // / Perform the application update using git, when that is the configured method.
        if ($UpdateMethod === 'git') $sourceReady = cloneRepository($ApplicationUpdateURL, $ApplicationBranchName, $AppInstallDir);
        // / Only continue with the update if the previous operation produced a folder.
        if (is_dir($AppInstallDir)) {
          // / Copy an index.html file into the new folder as document root protection.
          if (file_exists($RP.$SEP.'index.html')) @copy($RP.$SEP.'index.html', $AppInstallDir.$SEP.'index.html');
          // / Remove the git metadata directory, which we never need to keep around.
          if (is_dir($AppGitDir)) clean($AppGitDir);
          // / Stage every subscribed file beside its target. Nothing installed moves yet.
          foreach ($ApplicationUpdateSubscriptions as $appSubs) {
            redeclare($relativeName, str_replace('/', $SEP, strval($appSubs)));
            redeclare($appSubFile, $AppInstallDir.$SEP.$relativeName);
            redeclare($targetFile, $InstallDir.$SEP.$relativeName);
            // / Refuse a subscription name that would write outside the installation directory.
            if (str_contains($relativeName, '..')) {
              $stagingFailed = TRUE;
              processOutput('Refused an application subscription that points outside the installation directory:  '.$appSubs, 'error', 730, TRUE, TRUE, FALSE); }
            else {
              if (!file_exists($appSubFile)) $stagingFailed = TRUE;
              else {
                $subCount++;
                processOutput('Staging Application file:  '.$targetFile.'.new', 'log', 0, FALSE, FALSE, FALSE);
                if (!stageUpdateFile($appSubFile, $targetFile)) $stagingFailed = TRUE;
                else $stagedTargets[] = $targetFile; } } }
          // / Verify every staged file before a single installed file is moved aside.
          foreach ($stagedTargets as $stagedTarget) {
            list($fileVerified, $verificationNote) = verifyStagedFile($stagedTarget);
            if (!$fileVerified) {
              $verificationFailed = TRUE;
              processOutput('A staged application file failed verification because '.$verificationNote.':  '.$stagedTarget, 'error', 740, TRUE, TRUE, FALSE); } }
          if ($subCount !== $subTotal) $stagingFailed = TRUE;
          // / Abandon the whole update if anything at all is wrong with the staged set.
          if ($stagingFailed or $verificationFailed) {
            foreach ($stagedTargets as $stagedTarget) discardStagedFile($stagedTarget);
            processOutput('The application update was abandoned before any installed file was touched.', 'warning', 0, TRUE, TRUE, FALSE); }
          else {
            processOutput('Verified '.countWithNoun(count($stagedTargets), 'staged file', 'staged files').'.', 'log', 0, TRUE, FALSE, FALSE);
            // / Retire each installed file & move its staged replacement into place.
            foreach ($stagedTargets as $stagedTarget) {
              processOutput('Installing Application file:  '.$stagedTarget, 'log', 0, FALSE, FALSE, FALSE);
              if (!swapStagedFile($stagedTarget)) {
                $swapFailed = TRUE;
                processOutput('Cannot install the application file:  '.$stagedTarget, 'error', 720, TRUE, TRUE, FALSE); }
              else $installedCount++; }
            // / Test what is now installed by asking the new core for its version.
            list($testPerformed, $testPassed, $testNote) = testInstalledApplication();
            if (!$testPerformed) processOutput('Cannot test the installed application, continuing without that check:  '.$testNote, 'warning', 0, TRUE, FALSE, FALSE);
            // / Put every retired file back if the installation no longer runs.
            if ($swapFailed or ($testPerformed && !$testPassed)) {
              foreach ($stagedTargets as $stagedTarget) {
                rollbackStagedFile($stagedTarget);
                discardStagedFile($stagedTarget); }
              $installedCount = 0;
              processOutput('The updated application failed its test & the previous application was restored:  '.$testNote, 'error', 750, TRUE, TRUE, FALSE); }
            else {
              // / Remove the retired files only once the new application has proven itself.
              foreach ($stagedTargets as $stagedTarget) commitStagedFile($stagedTarget);
              if ($testPassed) processOutput('Tested the installed application.', 'log', 0, TRUE, FALSE, FALSE);
              $UpdateApplicationComplete = TRUE; } } } }
      // / Remove the temporary update directory whether or not the update succeeded.
      clean($AppGitDir);
      clean($AppInstallDir); } }
  // / Report a partial result, even when the files that did arrive installed cleanly.
  if ($installedCount !== $subTotal) {
    $UpdateApplicationErrors = TRUE;
    processOutput('Only '.countWithNoun($installedCount, 'file', 'files').' of '.$subTotal.' subscribed application files were installed.', 'warning', 0, TRUE, TRUE, FALSE); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($appSubs, $appSubFile, $rawAppURL, $relativeName, $targetFile, $stagedTargets, $stagedTarget, $subCount, $subTotal, $installedCount, $connectionResult, $sourceReady, $configCopied, $stagingFailed, $verificationFailed, $swapFailed, $testPerformed, $testPassed, $testNote, $verificationNote, $fileVerified);
  return array($UpdateApplicationComplete, $UpdateApplicationErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to render a count alongside a noun that agrees with it.
// / A summary that reads 1 files undermines confidence in everything above it.
function countWithNoun($itemCount, $singularNoun, $pluralNoun) {
  // / Set variables.
  $CountedPhrase = intval($itemCount).' '.$pluralNoun;
  if (intval($itemCount) === 1) $CountedPhrase = intval($itemCount).' '.$singularNoun;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($itemCount, $singularNoun, $pluralNoun);
  return $CountedPhrase; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether a named classifier is switched on in the configuration.
// / The configuration names a classifier the way a person would write it, such as SCAD
// / or Document, while a definition record names its type in lower case. The comparison
// / is therefore case insensitive so neither side has to know about the other.
function classifierIsEnabled($classifierName) {
  global $EnabledClassifiers;
  // / Set variables.
  $ClassifierEnabled = FALSE;
  $wantedName = strtolower(trim(strval($classifierName)));
  if (is_array($EnabledClassifiers)) foreach ($EnabledClassifiers as $enabledEntry) {
    if (strtolower(trim(strval($enabledEntry))) === $wantedName) $ClassifierEnabled = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($wantedName, $enabledEntry, $classifierName);
  return $ClassifierEnabled; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the lower case extension from a file name.
function fileExtensionOf($file) {
  // / Set variables.
  $FileExtension = '';
  $baseName = basename(strval($file));
  $dotPosition = strrpos($baseName, '.');
  if ($dotPosition !== FALSE) $FileExtension = strtolower(substr($baseName, $dotPosition + 1));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($baseName, $dotPosition, $file);
  return $FileExtension; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to split a comma separated list while honouring quoted members.
// / A quoted member keeps its leading & trailing whitespace & may contain a comma.
// / Returns each member alongside a flag saying whether it arrived quoted.
function splitDefinitionList($listText) {
  // / Set variables.
  $ListMembers = array();
  $characterIndex = 0;
  $characterTop = strlen($listText);
  $currentMember = '';
  $insideQuotes = FALSE;
  $memberWasQuoted = FALSE;
  $currentCharacter = '';
  while ($characterIndex < $characterTop) {
    redeclare($currentCharacter, substr($listText, $characterIndex, 1));
    // / A double quote opens or closes a literal member.
    if ($currentCharacter === '"') {
      $insideQuotes = !$insideQuotes;
      if ($insideQuotes) {
        $memberWasQuoted = TRUE;
        // / Whitespace sitting between the comma & the quote is separator, not content.
        // / A quoted member is never trimmed later, so it has to be dropped here instead.
        if (trim($currentMember) === '') redeclare($currentMember, ''); } }
    else {
      // / A comma outside quotes ends the current member.
      if ($currentCharacter === ',' && !$insideQuotes) {
        if (trim($currentMember) !== '' or $memberWasQuoted) $ListMembers[] = array('text' => ($memberWasQuoted ? $currentMember : trim($currentMember)), 'quoted' => $memberWasQuoted);
        redeclare($currentMember, '');
        $memberWasQuoted = FALSE; }
      else $currentMember = $currentMember.$currentCharacter; }
    $characterIndex++; }
  if (trim($currentMember) !== '' or $memberWasQuoted) $ListMembers[] = array('text' => ($memberWasQuoted ? $currentMember : trim($currentMember)), 'quoted' => $memberWasQuoted);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($characterIndex, $characterTop, $currentMember, $insideQuotes, $memberWasQuoted, $currentCharacter, $listText);
  return $ListMembers; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the classifier definitions file into a usable structure.
// / A classifier describes what a file is & what it is able to reach out to.
// / It never describes a threat, so nothing here is ever reported as an infection.
function loadClassifiers($ClassifierFile) {
  global $EnabledClassifiers;
  // / Set variables.
  $ClassifiersLoaded = FALSE;
  $Classifiers = array('types' => array(), 'languages' => array(), 'handlers' => array(), 'magic' => array(), 'embeds' => array());
  $ClassifierLongestToken = 0;
  $rawLines = array();
  $recordCount = $malformedCount = $skippedCount = 0;
  $keywordEnd = $equalsPosition = $colonPosition = $bracketPosition = 0;
  $recordBody = $typeName = $languageName = $extensionText = $signatureText = '';
  $handlerMembers = $signatureMembers = $extensionList = array();
  $handlerToken = $handlerCapabilities = $signaturePair = $magicBytes = '';
  $signatureList = $embedParts = array();
  $magicOffset = 0;
  $embedName = $embedConfidence = '';
  if (!file_exists($ClassifierFile)) processOutput('Cannot load the classifier definitions file, content classification is disabled:  '.$ClassifierFile, 'error', 520, TRUE, FALSE, FALSE);
  else {
    $rawLines = file($ClassifierFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($rawLines)) $rawLines = array();
    foreach ($rawLines as $rawLine) {
      redeclare($recordBody, trim($rawLine));
      // / An embed record declares code that has no business inside an image.
      if (substr($recordBody, 0, 6) === 'embed ') {
        redeclare($equalsPosition, strpos($recordBody, '='));
        if ($equalsPosition === FALSE) $malformedCount++;
        else {
          redeclare($embedName, trim(substr($recordBody, 6, $equalsPosition - 6)));
          redeclare($embedConfidence, 'medium');
          if (preg_match('/^(.+)\((high|medium|low)\)$/i', $embedName, $embedParts)) {
            $embedName = trim($embedParts[1]);
            $embedConfidence = strtolower($embedParts[2]); }
          redeclare($signatureList, array());
          foreach (splitDefinitionList(trim(substr($recordBody, $equalsPosition + 1))) as $signatureMember) if ($signatureMember['text'] !== '') $signatureList[] = decodeDefinitionValue($signatureMember['text']);
          if ($embedName === '' or count($signatureList) === 0) $malformedCount++;
          else {
            $Classifiers['embeds'][] = array('name' => $embedName, 'confidence' => $embedConfidence, 'signatures' => $signatureList);
            $recordCount++; } }
        // / The record is finished with, so nothing below may read it as a handler.
        redeclare($recordBody, ''); }
      // / Skip a blank line & skip a comment line, neither of which declares anything.
      if ($recordBody !== '' && substr($recordBody, 0, 1) !== '#') {
        // / A type record maps a type name onto the extensions that type covers.
        if (substr($recordBody, 0, 5) === 'type ') {
          redeclare($equalsPosition, strpos($recordBody, '='));
          if ($equalsPosition === FALSE) $malformedCount++;
          else {
            redeclare($typeName, strtolower(trim(substr($recordBody, 5, $equalsPosition - 5))));
            redeclare($extensionText, trim(substr($recordBody, $equalsPosition + 1)));
            redeclare($extensionList, array());
            foreach (explode(',', $extensionText) as $extensionEntry) if (trim($extensionEntry) !== '') $extensionList[] = strtolower(trim($extensionEntry));
            if ($typeName === '' or count($extensionList) === 0) $malformedCount++;
            else {
              $Classifiers['types'][$typeName] = $extensionList;
              $recordCount++; } } }
        else {
          // / A magic record declares the bytes that identify a type whatever its name.
          if (substr($recordBody, 0, 6) === 'magic ') {
            redeclare($equalsPosition, strpos($recordBody, '='));
            if ($equalsPosition === FALSE) $malformedCount++;
            else {
              redeclare($typeName, strtolower(trim(substr($recordBody, 6, $equalsPosition - 6))));
              redeclare($signatureList, array());
              foreach (explode(',', trim(substr($recordBody, $equalsPosition + 1))) as $signaturePair) {
                redeclare($colonPosition, strpos($signaturePair, ':'));
                if ($colonPosition !== FALSE) {
                  redeclare($magicOffset, intval(trim(substr($signaturePair, 0, $colonPosition))));
                  redeclare($magicBytes, strtolower(trim(substr($signaturePair, $colonPosition + 1))));
                  // / A signature has to be whole bytes, so an odd number of digits is refused.
                  if ($magicBytes !== '' && strlen($magicBytes) % 2 === 0 && preg_match('/^[0-9A-Fa-f]+$/', $magicBytes) === 1) $signatureList[] = array('offset' => $magicOffset, 'bytes' => $magicBytes); } }
              if ($typeName === '' or count($signatureList) === 0) $malformedCount++;
              else {
                if (!classifierIsEnabled($typeName)) $skippedCount++;
                else {
                  $Classifiers['magic'][$typeName] = $signatureList;
                  $recordCount++; } } } }
          else {
          // / A language record declares an executable language & how to recognize it.
          if (substr($recordBody, 0, 5) === 'lang ') {
            redeclare($equalsPosition, strpos($recordBody, '='));
            redeclare($colonPosition, strpos($recordBody, ':', $equalsPosition === FALSE ? 0 : $equalsPosition));
            if ($equalsPosition === FALSE) $malformedCount++;
            else {
              redeclare($languageName, trim(substr($recordBody, 5, $equalsPosition - 5)));
              if ($colonPosition === FALSE) {
                redeclare($extensionText, trim(substr($recordBody, $equalsPosition + 1)));
                redeclare($signatureText, ''); }
              else {
                redeclare($extensionText, trim(substr($recordBody, $equalsPosition + 1, $colonPosition - $equalsPosition - 1)));
                redeclare($signatureText, trim(substr($recordBody, $colonPosition + 1))); }
              redeclare($extensionList, array());
              foreach (explode(',', $extensionText) as $extensionEntry) if (trim($extensionEntry) !== '') $extensionList[] = strtolower(trim($extensionEntry));
              redeclare($signatureMembers, splitDefinitionList($signatureText));
              if ($languageName === '') $malformedCount++;
              else {
                // / A disabled classifier is never loaded, so it costs nothing to scan for.
                if (!classifierIsEnabled('Language')) $skippedCount++;
                else {
                $Classifiers['languages'][] = array('name' => $languageName, 'extensions' => $extensionList, 'signatures' => $signatureMembers);
                foreach ($signatureMembers as $signatureMember) if (strlen($signatureMember['text']) > $ClassifierLongestToken) $ClassifierLongestToken = strlen($signatureMember['text']);
                $recordCount++; } } } }
          else {
            // / Anything else is a handler record, which opens with the type it applies to.
            redeclare($keywordEnd, strpos($recordBody, ' '));
            if ($keywordEnd === FALSE) $malformedCount++;
            else {
              redeclare($typeName, strtolower(trim(substr($recordBody, 0, $keywordEnd))));
              redeclare($handlerMembers, splitDefinitionList(trim(substr($recordBody, $keywordEnd + 1))));
              if ($typeName === '' or count($handlerMembers) === 0) $malformedCount++;
              else {
                // / A disabled classifier is never loaded, so it costs nothing to scan for.
                if (!classifierIsEnabled($typeName)) $skippedCount++;
                else {
                if (!isset($Classifiers['handlers'][$typeName])) $Classifiers['handlers'][$typeName] = array();
                foreach ($handlerMembers as $handlerMember) {
                  // / Split the trailing capability list away from the handler token itself.
                  redeclare($bracketPosition, strrpos($handlerMember['text'], '('));
                  redeclare($handlerToken, '');
                  redeclare($handlerCapabilities, '');
                  if ($bracketPosition === FALSE) $malformedCount++;
                  else {
                    $handlerToken = rtrim(substr($handlerMember['text'], 0, $bracketPosition));
                    $handlerCapabilities = trim(rtrim(trim(substr($handlerMember['text'], $bracketPosition + 1)), ')'));
                    // / A quoted token keeps whatever whitespace & punctuation it arrived with.
                    if (!$handlerMember['quoted']) $handlerToken = trim($handlerToken);
                    else $handlerToken = trim($handlerToken, '"');
                    if ($handlerToken === '' or $handlerCapabilities === '') $malformedCount++;
                    else {
                      $Classifiers['handlers'][$typeName][] = array('token' => $handlerToken, 'capabilities' => $handlerCapabilities, 'literal' => $handlerMember['quoted']);
                      if (strlen($handlerToken) > $ClassifierLongestToken) $ClassifierLongestToken = strlen($handlerToken);
                      $recordCount++; } } } } } } } } } } }
    processOutput('Loaded '.$recordCount.' classifier records.', 'log', 0, FALSE, FALSE, FALSE);
    // / Say plainly how much was left out, so a quiet classifier is never a mystery.
    if ($skippedCount > 0) processOutput('Skipped '.$skippedCount.' classifier records belonging to classifiers that are not enabled.', 'log', 0, TRUE, FALSE, FALSE);
    if ($malformedCount > 0) processOutput('Ignored '.$malformedCount.' malformed classifier records.', 'warning', 0, TRUE, FALSE, FALSE);
    // / Report a configured classifier that this definitions file knows nothing about.
    foreach ($EnabledClassifiers as $enabledName) if (strtolower(trim(strval($enabledName))) !== 'language') {
      if (!isset($Classifiers['handlers'][strtolower(trim(strval($enabledName)))])) processOutput('The enabled classifier named '.$enabledName.' has no handlers declared in the classifier definitions file.', 'warning', 0, TRUE, FALSE, FALSE); }
    if ($recordCount < 1) processOutput('The classifier definitions file contains no usable records:  '.$ClassifierFile, 'error', 530, TRUE, FALSE, FALSE);
    else $ClassifiersLoaded = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($rawLines, $rawLine, $recordBody, $typeName, $languageName, $extensionText, $signatureText, $handlerMembers, $signatureMembers, $extensionList, $extensionEntry, $handlerMember, $signatureMember, $handlerToken, $handlerCapabilities, $recordCount, $malformedCount, $skippedCount, $keywordEnd, $equalsPosition, $colonPosition, $bracketPosition, $signatureList, $signaturePair, $magicOffset, $magicBytes, $embedParts, $embedName, $embedConfidence, $enabledName);
  return array($ClassifiersLoaded, $Classifiers, $ClassifierLongestToken); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to step past whitespace & comments while looking ahead from a token.
// / OpenSCAD treats a comment as a token separator, so surface/**/("file") is a call.
// / A lookahead that stopped at the first comment character missed exactly that form.
function skipToMeaningfulCharacter($haystack, $startPosition, $lookaheadLimit) {
  // / Set variables.
  $MeaningfulPosition = $startPosition;
  $haystackTop = strlen($haystack);
  $stopPosition = $startPosition + $lookaheadLimit;
  $keepSkipping = TRUE;
  $currentCharacter = $nextCharacter = '';
  $commentEnd = 0;
  if ($stopPosition > $haystackTop) $stopPosition = $haystackTop;
  while ($keepSkipping && $MeaningfulPosition < $stopPosition) {
    redeclare($currentCharacter, substr($haystack, $MeaningfulPosition, 1));
    redeclare($nextCharacter, substr($haystack, $MeaningfulPosition + 1, 1));
    // / Whitespace & newlines never end a call, so a call may span any number of lines.
    if ($currentCharacter === ' ' or $currentCharacter === "\t" or $currentCharacter === "\r" or $currentCharacter === "\n") $MeaningfulPosition++;
    else {
      // / A block comment separates a keyword from its bracket without ending the call.
      if ($currentCharacter === '/' && $nextCharacter === '*') {
        redeclare($commentEnd, strpos($haystack, '*/', $MeaningfulPosition + 2));
        if ($commentEnd === FALSE) {
          $MeaningfulPosition = $stopPosition;
          $keepSkipping = FALSE; }
        else $MeaningfulPosition = $commentEnd + 2; }
      else {
        // / A line comment runs to the end of its line & then stops separating.
        if ($currentCharacter === '/' && $nextCharacter === '/') {
          redeclare($commentEnd, strpos($haystack, "\n", $MeaningfulPosition + 2));
          if ($commentEnd === FALSE) {
            $MeaningfulPosition = $stopPosition;
            $keepSkipping = FALSE; }
          else $MeaningfulPosition = $commentEnd + 1; }
        else $keepSkipping = FALSE; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($haystackTop, $stopPosition, $keepSkipping, $currentCharacter, $nextCharacter, $commentEnd, $haystack, $startPosition, $lookaheadLimit);
  return $MeaningfulPosition; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether a handler token really appears in a block of data.
// / A literal token is a plain case insensitive search & carries no context rule.
// / A bare token must sit on an identifier boundary & must be followed by a bracket.
// / Those two rules together are what stop include_lid being read as an include call.
function handlerTokenAppears($haystack, $handlerToken, $isLiteral) {
  // / Set variables.
  $TokenAppears = FALSE;
  $searchOffset = 0;
  $foundPosition = 0;
  $characterBefore = $followingCharacter = '';
  $lookaheadPosition = 0;
  $keepSearching = TRUE;
  if ($handlerToken === '') $keepSearching = FALSE;
  // / A literal token is reported the moment it is seen anywhere in the data.
  if ($keepSearching && $isLiteral) {
    if (stripos($haystack, $handlerToken) !== FALSE) $TokenAppears = TRUE;
    $keepSearching = FALSE; }
  // / A bare token is inspected at every position it occupies until one qualifies.
  while ($keepSearching) {
    redeclare($foundPosition, stripos($haystack, $handlerToken, $searchOffset));
    if ($foundPosition === FALSE) $keepSearching = FALSE;
    else {
      redeclare($characterBefore, $foundPosition > 0 ? substr($haystack, $foundPosition - 1, 1) : '');
      // / The character before a call is never part of an identifier.
      if ($characterBefore === '' or preg_match('/[^A-Za-z0-9_]/', $characterBefore)) {
        redeclare($lookaheadPosition, skipToMeaningfulCharacter($haystack, $foundPosition + strlen($handlerToken), 256));
        redeclare($followingCharacter, substr($haystack, $lookaheadPosition, 1));
        // / The next meaningful character decides whether this is a call or a name.
        if ($followingCharacter === '(' or $followingCharacter === '<' or $followingCharacter === '=' or $followingCharacter === ':') {
          $TokenAppears = TRUE;
          $keepSearching = FALSE; } }
      if ($keepSearching) $searchOffset = $foundPosition + 1; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($searchOffset, $foundPosition, $characterBefore, $followingCharacter, $lookaheadPosition, $keepSearching, $haystack, $handlerToken, $isLiteral);
  return $TokenAppears; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to test a block of data against the handlers of the candidate types.
// / A hit is remembered by type & token so a repeated match reports only once.
function matchHandlerTokens($haystack, $Classifiers, $candidateTypes, &$handlerHits, $containerEntry = '') {
  // / Set variables.
  $MatchesFound = 0;
  $hitKey = '';
  foreach ($candidateTypes as $candidateType) if (isset($Classifiers['handlers'][$candidateType])) {
    foreach ($Classifiers['handlers'][$candidateType] as $handlerEntry) {
      redeclare($hitKey, $candidateType.'|'.$handlerEntry['token']);
      if (!isset($handlerHits[$hitKey])) if (handlerTokenAppears($haystack, $handlerEntry['token'], $handlerEntry['literal'])) {
        $handlerHits[$hitKey] = array('type' => $candidateType, 'token' => $handlerEntry['token'], 'capabilities' => $handlerEntry['capabilities'], 'entry' => $containerEntry);
        $MatchesFound++; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($candidateType, $handlerEntry, $hitKey, $haystack, $candidateTypes, $containerEntry);
  return $MatchesFound; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to test a block of data against every declared language signature.
// / A signature is a plain literal, so nothing here needs an identifier boundary.
function matchLanguageSignatures($haystack, $Classifiers, &$languageHits) {
  // / Set variables.
  $MatchesFound = 0;
  $hitKey = '';
  foreach ($Classifiers['languages'] as $languageIndex => $languageEntry) {
    foreach ($languageEntry['signatures'] as $signatureIndex => $signatureEntry) {
      redeclare($hitKey, $languageIndex.'|'.$signatureIndex);
      if (!isset($languageHits[$hitKey])) if ($signatureEntry['text'] !== '') if (stripos($haystack, $signatureEntry['text']) !== FALSE) {
        $languageHits[$hitKey] = $languageEntry['name'];
        $MatchesFound++; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($languageIndex, $languageEntry, $signatureIndex, $signatureEntry, $hitKey, $haystack);
  return $MatchesFound; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the language an extension belongs to, when one claims it.
function languageForExtension($Classifiers, $fileExtension) {
  // / Set variables.
  $LanguageName = '';
  if ($fileExtension !== '') foreach ($Classifiers['languages'] as $languageEntry) if ($LanguageName === '') {
    if (in_array($fileExtension, $languageEntry['extensions'], TRUE)) $LanguageName = $languageEntry['name']; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($languageEntry, $fileExtension);
  return $LanguageName; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to list the classifier types that claim a given file extension.
// / A type with no declared extension list claims the extension named after it.
function typesForExtension($Classifiers, $fileExtension) {
  // / Set variables.
  $CandidateTypes = array();
  if ($fileExtension !== '') foreach ($Classifiers['handlers'] as $typeName => $handlerList) {
    if (isset($Classifiers['types'][$typeName])) {
      if (in_array($fileExtension, $Classifiers['types'][$typeName], TRUE)) $CandidateTypes[] = $typeName; }
    else {
      if ($typeName === $fileExtension) $CandidateTypes[] = $typeName; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($typeName, $handlerList, $fileExtension);
  return $CandidateTypes; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide which language the collected content signatures point at.
// / A single generic signature is never enough, because prose contains those too.
function resolveContentLanguage($languageHits, $Classifiers) {
  // / Set variables.
  global $MinimumLanguageSignatures;
  $ResolvedLanguage = '';
  $hitTally = array();
  $bestScore = 0;
  foreach ($languageHits as $hitName) {
    if (!isset($hitTally[$hitName])) $hitTally[$hitName] = 0;
    $hitTally[$hitName] = $hitTally[$hitName] + 1; }
  foreach ($hitTally as $tallyName => $tallyCount) if ($tallyCount >= $MinimumLanguageSignatures) if ($tallyCount > $bestScore) {
    $bestScore = $tallyCount;
    $ResolvedLanguage = $tallyName; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($hitTally, $hitName, $tallyName, $tallyCount, $bestScore, $languageHits);
  return $ResolvedLanguage; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to render the collected handler hits as one readable sentence.
function describeHandlerHits($handlerHits) {
  // / Set variables.
  $HandlerDescription = '';
  $typeList = array();
  $tokenList = array();
  $tokenText = '';
  foreach ($handlerHits as $hitEntry) {
    if (!in_array($hitEntry['type'], $typeList, TRUE)) $typeList[] = $hitEntry['type'];
    // / Name the container entry a handler was found in, because a document keeps its
    // / interesting part in a compressed member rather than in the container itself.
    if (isset($hitEntry['entry']) && $hitEntry['entry'] !== '') redeclare($tokenText, $hitEntry['token'].'('.$hitEntry['capabilities'].') in '.$hitEntry['entry']);
    else redeclare($tokenText, $hitEntry['token'].'('.$hitEntry['capabilities'].')');
    // / Two types may declare the same handler, so report the handler once.
    if (!in_array($tokenText, $tokenList, TRUE)) $tokenList[] = $tokenText; }
  $HandlerDescription = 'Type: '.implode('/', $typeList).', Handlers: '.implode(', ', $tokenList);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($typeList, $tokenList, $tokenText, $hitEntry, $handlerHits);
  return $HandlerDescription; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the opening bytes of a file, used for magic identification.
function readFileHeader($file, $headerLength) {
  // / Set variables.
  $FileHeader = '';
  $handle = @fopen($file, 'rb');
  if ($handle !== FALSE) {
    $FileHeader = strval(@fread($handle, $headerLength));
    fclose($handle); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($handle, $file, $headerLength);
  return $FileHeader; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the type whose magic signature the supplied header carries.
// / Every declared offset must match, because a single common byte identifies nothing.
// / This is what tells an MPEG transport stream apart from a TypeScript source, since
// / both are named .ts & only one of them is a stream.
function magicTypeFor($Classifiers, $fileHeader) {
  // / Set variables.
  $MagicType = '';
  $signatureMatches = FALSE;
  $expectedBytes = '';
  $actualBytes = '';
  foreach ($Classifiers['magic'] as $typeName => $signatureList) if ($MagicType === '') {
    redeclare($signatureMatches, TRUE);
    foreach ($signatureList as $signatureEntry) if ($signatureMatches) {
      redeclare($expectedBytes, $signatureEntry['bytes']);
      redeclare($actualBytes, strtolower(bin2hex(substr($fileHeader, $signatureEntry['offset'], strlen($expectedBytes) / 2))));
      if ($actualBytes !== $expectedBytes) $signatureMatches = FALSE; }
    if ($signatureMatches) $MagicType = $typeName; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($typeName, $signatureList, $signatureEntry, $signatureMatches, $expectedBytes, $actualBytes, $fileHeader);
  return $MagicType; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether a container member is worth classifying for handlers.
// / A handler lives in markup, never in a picture or a font.
function archiveEntryIsInteresting($entryName) {
  // / Set variables.
  $EntryIsInteresting = FALSE;
  $entryExtension = fileExtensionOf($entryName);
  $readableExtensions = array('xml', 'rels', 'txt', 'htm', 'html', 'xhtml', 'opf', 'ncx', 'css', 'js', 'json', 'vtt', 'svg', 'fodt', 'plist', 'model', 'config', 'scad', 'mtl', 'obj', 'dae');
  // / A directory entry holds no content of its own.
  if (substr($entryName, -1) !== '/') {
    if (in_array($entryExtension, $readableExtensions, TRUE)) $EntryIsInteresting = TRUE;
    // / An entry with no extension at all is usually a manifest, so it is worth reading.
    if ($entryExtension === '') $EntryIsInteresting = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($entryExtension, $readableExtensions, $entryName);
  return $EntryIsInteresting; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to render a file or member name safely into a report line.
// / Control characters are escaped so a hostile name cannot forge a line of its own.
// / A very long name is shortened so one member cannot flood the report.
function printableName($name) {
  // / Set variables.
  $PrintableName = strval($name);
  if (strlen($PrintableName) > 255) $PrintableName = substr($PrintableName, 0, 252).'...';
  if (preg_match('/[\x00-\x1F\x7F]/', $PrintableName)) $PrintableName = preg_replace_callback('/[\x00-\x1F\x7F]/', function($byteMatch) { return sprintf('\\x%02x', ord($byteMatch[0])); }, $PrintableName);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($name);
  return $PrintableName; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the container format a file header describes.
// / A tar names itself at offset 257, so the header must be at least 263 bytes long.
function containerFormatOf($fileHeader) {
  // / Set variables.
  $ContainerFormat = '';
  if (substr($fileHeader, 0, 4) === "PK\x03\x04" or substr($fileHeader, 0, 4) === "PK\x05\x06") $ContainerFormat = 'zip';
  if (substr($fileHeader, 0, 3) === "\x1f\x8b\x08") $ContainerFormat = 'gzip';
  if (substr($fileHeader, 0, 3) === 'BZh') $ContainerFormat = 'bzip2';
  if (substr($fileHeader, 257, 5) === 'ustar') $ContainerFormat = 'tar';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($fileHeader);
  return $ContainerFormat; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the image format a file header describes.
function imageFormatOf($fileHeader) {
  // / Set variables.
  $ImageFormat = '';
  if (substr($fileHeader, 0, 8) === "\x89PNG\r\n\x1a\n") $ImageFormat = 'png';
  if (substr($fileHeader, 0, 3) === "\xFF\xD8\xFF") $ImageFormat = 'jpeg';
  if (substr($fileHeader, 0, 6) === 'GIF87a' or substr($fileHeader, 0, 6) === 'GIF89a') $ImageFormat = 'gif';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($fileHeader);
  return $ImageFormat; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to start the record that one file inspection keeps as it descends.
// / The budgets are shared by every nested container beneath one scanned file.
function newInspectionState($candidateTypes) {
  // / Set variables.
  $InspectionState = array('bytes' => 0, 'entries' => 0, 'halted' => FALSE, 'incomplete' => array(), 'types' => $candidateTypes);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($candidateTypes);
  return $InspectionState; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to record a reason one file could not be inspected completely.
// / Each reason is kept once & the list is capped so the report line stays readable.
function noteIncomplete(&$inspection, $reason) {
  // / Set variables.
  $ReasonNoted = FALSE;
  if (!in_array($reason, $inspection['incomplete'], TRUE) && count($inspection['incomplete']) < 6) {
    $inspection['incomplete'][] = $reason;
    $ReasonNoted = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($reason);
  return $ReasonNoted; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to copy a block of data into a memory stream that can be sought & read.
// / Nested containers & embedded images are inspected through the same stream readers.
function openMemoryStream($streamData) {
  // / Set variables.
  $MemoryHandle = @fopen('php://memory', 'w+b');
  if ($MemoryHandle !== FALSE) {
    fwrite($MemoryHandle, $streamData);
    rewind($MemoryHandle); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($streamData);
  return $MemoryHandle; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to inflate a compressed block held in memory, up to an output limit.
// / The input is fed in small pieces so one call can never produce an unbounded result.
// / The stream ended flag is FALSE when the limit stopped the work before the data did.
function inflateBounded($compressedData, $zlibEncoding, $outputLimit) {
  // / Set variables.
  $InflateWorked = FALSE;
  $InflatedData = '';
  $StreamEnded = FALSE;
  $inflateContext = FALSE;
  $inputOffset = 0;
  $inputLength = strlen($compressedData);
  $outputPiece = '';
  if (function_exists('inflate_init')) $inflateContext = @inflate_init($zlibEncoding);
  if ($inflateContext !== FALSE) {
    $InflateWorked = TRUE;
    while ($InflateWorked && !$StreamEnded && $inputOffset < $inputLength && strlen($InflatedData) <= $outputLimit) {
      redeclare($outputPiece, @inflate_add($inflateContext, substr($compressedData, $inputOffset, 4096)));
      $inputOffset = $inputOffset + 4096;
      if ($outputPiece === FALSE) $InflateWorked = FALSE;
      else {
        $InflatedData = $InflatedData.$outputPiece;
        if (inflate_get_status($inflateContext) === ZLIB_STREAM_END) $StreamEnded = TRUE; } }
    if (strlen($InflatedData) > $outputLimit) {
      $InflatedData = substr($InflatedData, 0, $outputLimit);
      $StreamEnded = FALSE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($inflateContext, $inputOffset, $inputLength, $outputPiece, $compressedData, $zlibEncoding, $outputLimit);
  return array($InflateWorked, $InflatedData, $StreamEnded); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to test a block of data against the embedded code signatures.
// / A signature shorter than six bytes is only tested against decoded text, because a
// / short run of bytes turns up by chance inside compressed image data.
function matchEmbedSignatures($file, $haystack, $isDecodedText, &$fileDetections) {
  global $Classifiers;
  // / Set variables.
  $MatchesFound = 0;
  $signatureMatched = FALSE;
  if (isset($Classifiers['embeds'])) foreach ($Classifiers['embeds'] as $embedEntry) {
    // / Decoded text does not repeat what the raw bytes of the file already reported.
    redeclare($signatureMatched, ($isDecodedText && isset($fileDetections['Embedded.'.$embedEntry['name'].'|Structure Match|'])));
    foreach ($embedEntry['signatures'] as $signatureText) if (!$signatureMatched) {
      if ($isDecodedText or strlen($signatureText) >= 6) if (stripos($haystack, $signatureText) !== FALSE) {
        $signatureMatched = TRUE;
        recordDetection($file, 'Embedded.'.$embedEntry['name'], 'Structure Match', $signatureText, $embedEntry['confidence'], $fileDetections);
        $MatchesFound++; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($embedEntry, $signatureText, $signatureMatched, $haystack, $isDecodedText, $file);
  return $MatchesFound; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to work out how many bytes the image data of a png must inflate to.
// / Every scanline carries one filter byte. An interlaced image is seven smaller images.
function pngDecodedSize($imageWidth, $imageHeight, $channelCount, $bitDepth, $interlaceMethod) {
  // / Set variables.
  $DecodedSize = 0;
  $bitsPerPixel = $channelCount * $bitDepth;
  $passIndex = 0;
  $passWidth = $passHeight = 0;
  $startX = array(0, 4, 0, 2, 0, 1, 0);
  $startY = array(0, 0, 4, 0, 2, 0, 1);
  $stepX = array(8, 8, 4, 4, 2, 2, 1);
  $stepY = array(8, 8, 8, 4, 4, 2, 2);
  if ($interlaceMethod !== 1) $DecodedSize = (intdiv(($imageWidth * $bitsPerPixel) + 7, 8) + 1) * $imageHeight;
  else {
    while ($passIndex < 7) {
      $passWidth = intdiv($imageWidth - $startX[$passIndex] + $stepX[$passIndex] - 1, $stepX[$passIndex]);
      $passHeight = intdiv($imageHeight - $startY[$passIndex] + $stepY[$passIndex] - 1, $stepY[$passIndex]);
      if ($passWidth > 0 && $passHeight > 0) $DecodedSize = $DecodedSize + ((intdiv(($passWidth * $bitsPerPixel) + 7, 8) + 1) * $passHeight);
      $passIndex++; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($bitsPerPixel, $passIndex, $passWidth, $passHeight, $startX, $startY, $stepX, $stepY, $imageWidth, $imageHeight, $channelCount, $bitDepth, $interlaceMethod);
  return $DecodedSize; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to inspect the text a png chunk carries, inflating it first when needed.
// / A compressed chunk is inflated up to the chunk limit & no further.
function inspectPngTextChunk($file, $chunkType, $chunkData, &$fileDetections) {
  global $MaxImageChunkSize, $MemberContext;
  // / Set variables.
  $ChunkInspected = FALSE;
  $outerContext = $MemberContext;
  $separatorPosition = strpos($chunkData, "\0");
  $chunkText = $compressedPart = '';
  $isCompressed = FALSE;
  $inflateWorked = $streamEnded = FALSE;
  $fieldEnd = 0;
  $MemberContext = ($outerContext !== '' ? $outerContext.' > ' : '').$chunkType.' chunk';
  if ($separatorPosition !== FALSE) {
    $ChunkInspected = TRUE;
    if ($chunkType === 'tEXt') $chunkText = substr($chunkData, $separatorPosition + 1);
    if ($chunkType === 'zTXt' or $chunkType === 'iCCP') {
      $isCompressed = TRUE;
      $compressedPart = substr($chunkData, $separatorPosition + 2); }
    if ($chunkType === 'iTXt') {
      // / The text follows a compression flag, a method, a language tag & a translated keyword.
      $isCompressed = (substr($chunkData, $separatorPosition + 1, 1) === "\x01");
      $fieldEnd = strpos($chunkData, "\0", $separatorPosition + 3);
      if ($fieldEnd !== FALSE) $fieldEnd = strpos($chunkData, "\0", $fieldEnd + 1);
      if ($fieldEnd === FALSE) $ChunkInspected = FALSE;
      else {
        if ($isCompressed) $compressedPart = substr($chunkData, $fieldEnd + 1);
        else $chunkText = substr($chunkData, $fieldEnd + 1); } }
    if ($ChunkInspected && $isCompressed) {
      list($inflateWorked, $chunkText, $streamEnded) = inflateBounded($compressedPart, ZLIB_ENCODING_DEFLATE, $MaxImageChunkSize);
      // / A chunk still producing output at the limit is a decompression bomb.
      if ($inflateWorked && !$streamEnded && strlen($chunkText) >= $MaxImageChunkSize) recordDetection($file, 'Bomb.PNG.ChunkExpansion', 'Structure Match', 'expands past '.$MaxImageChunkSize.' bytes from '.strlen($compressedPart), 'high', $fileDetections); }
    // / A colour profile is binary, so only text chunks are searched for code.
    if ($ChunkInspected && $chunkType !== 'iCCP' && $chunkText !== '') matchEmbedSignatures($file, $chunkText, TRUE, $fileDetections); }
  $MemberContext = $outerContext;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($outerContext, $separatorPosition, $chunkText, $compressedPart, $isCompressed, $inflateWorked, $streamEnded, $fieldEnd, $chunkType, $chunkData, $file);
  return $ChunkInspected; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to walk the chunks of a png & report what is wrong with its structure.
// / The header decides how large the decoded image may be. The image data is inflated
// / & counted, never kept, so the walk costs no more memory than one small buffer.
function inspectPngStream($file, $handle, $sourceSize, &$fileDetections) {
  global $MaxImagePixels, $MaxImageChunkSize;
  // / Set variables.
  $PngInspected = FALSE;
  $chunkLimit = 1048576;
  $chunkCount = 0;
  $position = 8;
  $keepWalking = TRUE;
  $sawHeader = $sawData = $sawEnd = $dataEnded = $overrunReported = FALSE;
  $malformedReason = '';
  $chunkHeader = $chunkType = $chunkData = $dataPiece = $storedCrc = $inflatedPiece = '';
  $chunkLength = $bytesLeft = $pieceLength = 0;
  $chunkFields = $headerFields = array();
  $crcContext = NULL;
  $inflateContext = FALSE;
  $keepData = FALSE;
  $imageWidth = $imageHeight = $decodedSize = $inflatedTotal = 0;
  $channelCounts = array(0 => 1, 2 => 3, 3 => 1, 4 => 2, 6 => 4);
  $validDepths = array(0 => array(1, 2, 4, 8, 16), 2 => array(8, 16), 3 => array(1, 2, 4, 8), 4 => array(8, 16), 6 => array(8, 16));
  $textChunks = array('tEXt', 'zTXt', 'iTXt', 'iCCP');
  $criticalChunks = array('IHDR', 'PLTE', 'IDAT', 'IEND');
  if ($handle !== FALSE && @fseek($handle, 8) === 0) $PngInspected = TRUE;
  while ($PngInspected && $keepWalking) {
    redeclare($chunkHeader, strval(fread($handle, 8)));
    if (strlen($chunkHeader) < 8) $keepWalking = FALSE;
    else {
      $chunkCount++;
      redeclare($chunkFields, unpack('Nlength', $chunkHeader));
      redeclare($chunkLength, intval($chunkFields['length']));
      redeclare($chunkType, substr($chunkHeader, 4, 4));
      $position = $position + 8;
      // / A chunk that claims more bytes than the file holds ends the walk.
      if (!preg_match('/^[A-Za-z]{4}$/', $chunkType) or ($position + $chunkLength + 4) > $sourceSize) {
        if ($malformedReason === '') $malformedReason = 'a chunk runs past the end of the file';
        $keepWalking = FALSE; }
      if ($keepWalking && $chunkCount > $chunkLimit) {
        if ($malformedReason === '') $malformedReason = 'more than '.$chunkLimit.' chunks';
        $keepWalking = FALSE; }
      if ($keepWalking) {
        if ($chunkCount === 1 && $chunkType !== 'IHDR' && $malformedReason === '') $malformedReason = 'the first chunk is not the header';
        if ($chunkType === 'IHDR' && ($sawHeader or $chunkLength !== 13) && $malformedReason === '') $malformedReason = 'a damaged or repeated header chunk';
        // / A decoder must refuse a critical chunk it does not know.
        if (preg_match('/^[A-Z]/', $chunkType) === 1 && !in_array($chunkType, $criticalChunks, TRUE) && $malformedReason === '') $malformedReason = 'an unknown critical chunk named '.$chunkType;
        redeclare($keepData, ($chunkType === 'IHDR' or in_array($chunkType, $textChunks, TRUE)) && $chunkLength <= $MaxImageChunkSize);
        if (in_array($chunkType, $textChunks, TRUE) && !$keepData && $malformedReason === '') $malformedReason = 'an oversized '.$chunkType.' chunk of '.$chunkLength.' bytes';
        redeclare($chunkData, '');
        redeclare($crcContext, hash_init('crc32b'));
        hash_update($crcContext, $chunkType);
        redeclare($bytesLeft, $chunkLength);
        while ($bytesLeft > 0) {
          redeclare($pieceLength, min($bytesLeft, 8192));
          redeclare($dataPiece, strval(fread($handle, $pieceLength)));
          if (strlen($dataPiece) < 1) break;
          $bytesLeft = $bytesLeft - strlen($dataPiece);
          hash_update($crcContext, $dataPiece);
          if ($keepData) $chunkData = $chunkData.$dataPiece;
          // / Inflate the image data only to count it, & stop counting once it has overrun.
          if ($chunkType === 'IDAT' && $inflateContext !== FALSE && !$dataEnded && !$overrunReported) {
            redeclare($inflatedPiece, @inflate_add($inflateContext, $dataPiece));
            if ($inflatedPiece === FALSE) $dataEnded = TRUE;
            else {
              $inflatedTotal = $inflatedTotal + strlen($inflatedPiece);
              if (inflate_get_status($inflateContext) === ZLIB_STREAM_END) $dataEnded = TRUE;
              if ($inflatedTotal > ($decodedSize + $MaxImageChunkSize)) {
                $overrunReported = TRUE;
                recordDetection($file, 'Bomb.PNG.DataOverrun', 'Structure Match', 'the image data inflates past '.$inflatedTotal.' bytes & the header declares '.$decodedSize, 'high', $fileDetections); } } } }
        redeclare($storedCrc, strval(fread($handle, 4)));
        $position = $position + $chunkLength + 4;
        if (($bytesLeft > 0 or bin2hex($storedCrc) !== hash_final($crcContext)) && $malformedReason === '') $malformedReason = 'the '.$chunkType.' chunk fails its checksum';
        if ($chunkType === 'IHDR' && !$sawHeader && strlen($chunkData) === 13) {
          $sawHeader = TRUE;
          $headerFields = unpack('Nwidth/Nheight/Cdepth/Ccolour/Ccompression/Cfilter/Cinterlace', $chunkData);
          $imageWidth = intval($headerFields['width']);
          $imageHeight = intval($headerFields['height']);
          if ($imageWidth < 1 or $imageHeight < 1 or !isset($validDepths[$headerFields['colour']]) or !in_array($headerFields['depth'], $validDepths[$headerFields['colour']], TRUE) or $headerFields['interlace'] > 1) {
            if ($malformedReason === '') $malformedReason = 'the header declares an impossible image'; }
          else {
            $decodedSize = pngDecodedSize($imageWidth, $imageHeight, $channelCounts[$headerFields['colour']], $headerFields['depth'], $headerFields['interlace']);
            // / The pixel limit is the policy. An image past it is refused whatever it holds.
            if (($imageWidth * $imageHeight) > $MaxImagePixels) recordDetection($file, 'Bomb.PNG.PixelFlood', 'Structure Match', $imageWidth.' by '.$imageHeight.' pixels decodes to '.$decodedSize.' bytes from a file of '.$sourceSize, 'high', $fileDetections);
            else { if (function_exists('inflate_init')) $inflateContext = @inflate_init(ZLIB_ENCODING_DEFLATE); } } }
        if ($chunkType === 'IDAT') $sawData = TRUE;
        if ($keepData && $chunkType !== 'IHDR') inspectPngTextChunk($file, $chunkType, $chunkData, $fileDetections);
        if ($chunkType === 'IEND') {
          $sawEnd = TRUE;
          $keepWalking = FALSE; } } } }
  if ($PngInspected) {
    if ($malformedReason === '' && (!$sawHeader or !$sawData or !$sawEnd)) $malformedReason = 'the header, the image data or the end chunk is missing';
    // / Image data that overruns its header by a little is damage rather than a bomb.
    if ($malformedReason === '' && !$overrunReported && $sawHeader && $inflatedTotal > $decodedSize) $malformedReason = 'the image data holds '.($inflatedTotal - $decodedSize).' bytes more than the header declares';
    if ($malformedReason !== '') recordDetection($file, 'Malformed.PNG', 'Structure Match', $malformedReason, 'medium', $fileDetections);
    // / Anything after the end chunk is invisible to an image viewer & readable by everything else.
    if ($sawEnd && $position < $sourceSize) recordDetection($file, 'Polyglot.PNG.TrailingData', 'Structure Match', ($sourceSize - $position).' bytes follow the end of the image', 'medium', $fileDetections); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($chunkLimit, $chunkCount, $position, $keepWalking, $sawHeader, $sawData, $sawEnd, $dataEnded, $overrunReported, $malformedReason, $chunkHeader, $chunkType, $chunkData, $dataPiece, $storedCrc, $inflatedPiece, $chunkLength, $bytesLeft, $pieceLength, $chunkFields, $headerFields, $crcContext, $inflateContext, $keepData, $imageWidth, $imageHeight, $decodedSize, $inflatedTotal, $channelCounts, $validDepths, $textChunks, $criticalChunks, $file, $handle, $sourceSize);
  return $PngInspected; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to locate the central directory of a zip archive.
// / The directory record sits at the end of the file, behind an optional comment.
// / The base is the number of bytes sitting in front of the archive, which is not zero
// / when an archive has been appended to another file.
function findZipDirectory($handle, $sourceSize) {
  // / Set variables.
  $Directory = array('found' => FALSE, 'entries' => 0, 'offset' => 0, 'size' => 0, 'base' => 0);
  $tailLength = min($sourceSize, 65557);
  $tailStart = $sourceSize - $tailLength;
  $tailData = $locatorData = $wideData = '';
  $recordPosition = FALSE;
  $recordFields = $wideFields = array();
  $attemptsLeft = 16;
  $recordStart = $directoryEnd = 0;
  if ($tailLength >= 22 && @fseek($handle, $tailStart) === 0) {
    $tailData = strval(fread($handle, $tailLength));
    $recordPosition = strrpos($tailData, "PK\x05\x06");
    // / A comment may carry a false record, so each candidate must fit the file it is in.
    while ($recordPosition !== FALSE && !$Directory['found'] && $attemptsLeft > 0) {
      $attemptsLeft--;
      if (($recordPosition + 22) <= $tailLength) {
        redeclare($recordFields, unpack('vdisk/vstartDisk/vdiskEntries/ventries/Vsize/Voffset/vcomment', substr($tailData, $recordPosition + 4, 18)));
        redeclare($recordStart, $tailStart + $recordPosition);
        $Directory['entries'] = intval($recordFields['entries']);
        $Directory['size'] = intval($recordFields['size']);
        $Directory['offset'] = intval($recordFields['offset']);
        redeclare($directoryEnd, $recordStart);
        // / A saturated field means the real values sit in the zip64 record instead.
        if ($recordFields['entries'] === 0xFFFF or $recordFields['size'] === 0xFFFFFFFF or $recordFields['offset'] === 0xFFFFFFFF) {
          if ($recordStart >= 20 && @fseek($handle, $recordStart - 20) === 0) {
            redeclare($locatorData, strval(fread($handle, 20)));
            if (substr($locatorData, 0, 4) === "PK\x06\x07") {
              redeclare($wideFields, unpack('Pposition', substr($locatorData, 8, 8)));
              if ($wideFields['position'] >= 0 && $wideFields['position'] < $sourceSize && @fseek($handle, $wideFields['position']) === 0) {
                redeclare($wideData, strval(fread($handle, 56)));
                if (strlen($wideData) === 56 && substr($wideData, 0, 4) === "PK\x06\x06") {
                  redeclare($directoryEnd, intval($wideFields['position']));
                  redeclare($wideFields, unpack('Pentries/Psize/Poffset', substr($wideData, 32, 24)));
                  $Directory['entries'] = intval($wideFields['entries']);
                  $Directory['size'] = intval($wideFields['size']);
                  $Directory['offset'] = intval($wideFields['offset']); } } } } }
        $Directory['base'] = $directoryEnd - $Directory['size'] - $Directory['offset'];
        if ($Directory['entries'] >= 0 && $Directory['size'] >= 0 && $Directory['offset'] >= 0 && $Directory['base'] >= 0) {
          // / An empty archive has no directory to check. Any other must open with an entry.
          if ($Directory['entries'] === 0) $Directory['found'] = ($Directory['size'] === 0);
          else {
            if (@fseek($handle, $Directory['base'] + $Directory['offset']) === 0) if (strval(fread($handle, 4)) === "PK\x01\x02") $Directory['found'] = TRUE; } } }
      if (!$Directory['found']) $recordPosition = ($recordPosition > 0) ? strrpos(substr($tailData, 0, $recordPosition), "PK\x05\x06") : FALSE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($tailLength, $tailStart, $tailData, $locatorData, $wideData, $recordPosition, $recordFields, $wideFields, $attemptsLeft, $recordStart, $directoryEnd, $handle, $sourceSize);
  return $Directory; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the entries a zip central directory lists.
// / Reading stops at the entry budget, so the list may be shorter than the directory.
function readZipDirectory($handle, $directory) {
  global $MaxArchiveEntries;
  // / Set variables.
  $DirectoryRead = FALSE;
  $EntryList = array();
  $entryHeader = $entryName = $extraData = '';
  $entryFields = $wideFields = array();
  $entryIndex = $extraOffset = $extraId = $extraLength = $wideOffset = 0;
  $entryRecord = array();
  if (@fseek($handle, $directory['base'] + $directory['offset']) === 0) $DirectoryRead = TRUE;
  while ($DirectoryRead && $entryIndex < $directory['entries'] && count($EntryList) < $MaxArchiveEntries) {
    redeclare($entryHeader, strval(fread($handle, 46)));
    if (strlen($entryHeader) < 46 or substr($entryHeader, 0, 4) !== "PK\x01\x02") $DirectoryRead = FALSE;
    else {
      redeclare($entryFields, unpack('vmade/vneeded/vflags/vmethod/vtime/vdate/Vcrc/Vcompressed/Vsize/vnameLength/vextraLength/vcommentLength/vdisk/vinternal/Vexternal/Voffset', substr($entryHeader, 4)));
      redeclare($entryName, $entryFields['nameLength'] > 0 ? strval(fread($handle, $entryFields['nameLength'])) : '');
      redeclare($extraData, $entryFields['extraLength'] > 0 ? strval(fread($handle, $entryFields['extraLength'])) : '');
      if ($entryFields['commentLength'] > 0) fread($handle, $entryFields['commentLength']);
      redeclare($entryRecord, array('name' => $entryName, 'flags' => $entryFields['flags'], 'method' => $entryFields['method'], 'compressed' => $entryFields['compressed'], 'size' => $entryFields['size'], 'offset' => $entryFields['offset']));
      // / A saturated field is carried in the zip64 extra field, in a fixed order.
      redeclare($extraOffset, 0);
      while (($extraOffset + 4) <= strlen($extraData)) {
        redeclare($wideFields, unpack('vid/vlength', substr($extraData, $extraOffset, 4)));
        redeclare($extraId, $wideFields['id']);
        redeclare($extraLength, $wideFields['length']);
        if ($extraId === 1) {
          redeclare($wideOffset, $extraOffset + 4);
          foreach (array('size', 'compressed', 'offset') as $wideName) if ($entryRecord[$wideName] === 0xFFFFFFFF && ($wideOffset + 8) <= strlen($extraData)) {
            $entryRecord[$wideName] = intval(unpack('Pvalue', substr($extraData, $wideOffset, 8))['value']);
            $wideOffset = $wideOffset + 8; } }
        $extraOffset = $extraOffset + 4 + $extraLength; }
      $entryRecord['offset'] = $entryRecord['offset'] + $directory['base'];
      $EntryList[] = $entryRecord;
      $entryIndex++; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($entryHeader, $entryName, $extraData, $entryFields, $wideFields, $entryIndex, $extraOffset, $extraId, $extraLength, $wideOffset, $entryRecord, $wideName, $directory, $handle);
  return array($DirectoryRead, $EntryList); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read one member of a zip archive, up to a limit.
// / The limit bounds the output whatever the archive declares, so a dishonest header
// / cannot cause a large read. The note names why a member could not be read at all.
function readZipMember($handle, $sourceSize, $entryRecord, $readLimit) {
  // / Set variables.
  $MemberData = '';
  $MemberComplete = FALSE;
  $MemberNote = '';
  $ConsumedBytes = 0;
  $localHeader = $localName = $dataPiece = $outputPiece = '';
  $localFields = array();
  $bytesLeft = 0;
  $inflateContext = FALSE;
  $keepReading = TRUE;
  if ($entryRecord['offset'] < 0 or ($entryRecord['offset'] + 30) > $sourceSize or @fseek($handle, $entryRecord['offset']) !== 0) $MemberNote = 'damaged';
  else {
    $localHeader = strval(fread($handle, 30));
    if (strlen($localHeader) < 30 or substr($localHeader, 0, 4) !== "PK\x03\x04") $MemberNote = 'damaged';
    else {
      $localFields = unpack('vneeded/vflags/vmethod/vtime/vdate/Vcrc/Vcompressed/Vsize/vnameLength/vextraLength', substr($localHeader, 4));
      $localName = $localFields['nameLength'] > 0 ? strval(fread($handle, $localFields['nameLength'])) : '';
      if ($localFields['extraLength'] > 0) fread($handle, $localFields['extraLength']);
      // / The two headers describing one member must agree, or two readers see two files.
      if ($localName !== $entryRecord['name'] or $localFields['method'] !== $entryRecord['method']) $MemberNote = 'mismatch';
      if (($entryRecord['flags'] & 1) === 1) $MemberNote = 'encrypted';
      $bytesLeft = min($entryRecord['compressed'], max(0, $sourceSize - ftell($handle)));
      if ($MemberNote !== 'encrypted' && $entryRecord['method'] === 0) {
        if ($bytesLeft > 0) $MemberData = strval(fread($handle, min($bytesLeft, $readLimit)));
        $ConsumedBytes = strlen($MemberData);
        $MemberComplete = ($bytesLeft <= $readLimit); }
      if ($MemberNote !== 'encrypted' && $entryRecord['method'] === 8) {
        if (function_exists('inflate_init')) $inflateContext = @inflate_init(ZLIB_ENCODING_RAW);
        if ($inflateContext === FALSE) $MemberNote = 'unsupported';
        while ($inflateContext !== FALSE && $keepReading && $bytesLeft > 0) {
          redeclare($dataPiece, strval(fread($handle, min($bytesLeft, 8192))));
          if ($dataPiece === '') $keepReading = FALSE;
          else {
            $bytesLeft = $bytesLeft - strlen($dataPiece);
            $ConsumedBytes = $ConsumedBytes + strlen($dataPiece);
            redeclare($outputPiece, @inflate_add($inflateContext, $dataPiece));
            if ($outputPiece === FALSE) {
              $MemberNote = 'damaged';
              $keepReading = FALSE; }
            else {
              $MemberData = $MemberData.$outputPiece;
              if (inflate_get_status($inflateContext) === ZLIB_STREAM_END) {
                $MemberComplete = TRUE;
                $keepReading = FALSE; }
              if (strlen($MemberData) > $readLimit) {
                $MemberData = substr($MemberData, 0, $readLimit);
                $MemberComplete = FALSE;
                $keepReading = FALSE; } } } } }
      if ($MemberNote !== 'encrypted' && $entryRecord['method'] !== 0 && $entryRecord['method'] !== 8) $MemberNote = 'unsupported'; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($localHeader, $localName, $dataPiece, $outputPiece, $localFields, $bytesLeft, $inflateContext, $keepReading, $entryRecord, $readLimit, $handle, $sourceSize);
  return array($MemberData, $MemberComplete, $MemberNote, $ConsumedBytes); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to claim the budget for reading one more container member.
// / Returns the number of bytes that may be read, or zero once a budget is spent.
function claimMemberBudget(&$inspection) {
  global $MaxArchiveEntries, $MaxArchiveEntrySize, $MaxArchiveTotalSize;
  // / Set variables.
  $ReadLimit = 0;
  $inspection['entries']++;
  if ($inspection['entries'] > $MaxArchiveEntries) {
    noteIncomplete($inspection, 'the entry budget of '.$MaxArchiveEntries.' was reached');
    $inspection['halted'] = TRUE; }
  else {
    $ReadLimit = min($MaxArchiveEntrySize, $MaxArchiveTotalSize - $inspection['bytes']);
    if ($ReadLimit < 1) {
      $ReadLimit = 0;
      noteIncomplete($inspection, 'the total decompression budget of '.$MaxArchiveTotalSize.' bytes was reached');
      $inspection['halted'] = TRUE; } }
  return $ReadLimit; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to inspect one member taken out of a container.
// / The member is matched against the definitions, classified, checked as an image &
// / opened in turn when it is itself a container.
function inspectMemberData($file, $memberPath, $memberName, $memberData, $memberIsComplete, $depth, &$fileDetections, &$handlerHits, &$inspection) {
  global $Defs, $Classifiers, $ClassifiersLoaded, $ClassifyContent, $InspectImages, $MaxArchiveDepth, $MemberContext;
  // / Set variables.
  $MemberInspected = TRUE;
  $outerContext = $MemberContext;
  $memberHeader = substr($memberData, 0, 512);
  $containerFormat = containerFormatOf($memberHeader);
  $imageFormat = imageFormatOf($memberHeader);
  $scopeTypes = array();
  $memoryHandle = FALSE;
  $MemberContext = $memberPath;
  if ($ClassifiersLoaded) $scopeTypes = typesForExtension($Classifiers, fileExtensionOf($memberName));
  // / A hash only means something when the whole member was read.
  if ($memberIsComplete) matchHashSignatures($file, hash('md5', $memberData), hash('sha1', $memberData), hash('sha256', $memberData), $Defs, $fileDetections);
  matchContentIndicators($file, $memberData, $Defs, $scopeTypes, $fileDetections);
  foreach ($Defs['name'] as $definitionRecord) if (stripos($memberName, $definitionRecord['value']) !== FALSE) recordDetection($file, $definitionRecord['name'], 'Name Match', $definitionRecord['value'], $definitionRecord['confidence'], $fileDetections);
  // / A member that is a container reports its handlers through its own members instead.
  if ($ClassifyContent && $ClassifiersLoaded && $containerFormat === '' && archiveEntryIsInteresting($memberName)) matchHandlerTokens($memberData, $Classifiers, $inspection['types'], $handlerHits, printableName($memberPath));
  if ($InspectImages && $imageFormat !== '') {
    matchEmbedSignatures($file, $memberData, FALSE, $fileDetections);
    // / A member that was cut short would only report the cut as damage.
    if ($imageFormat === 'png' && $memberIsComplete) {
      $memoryHandle = openMemoryStream($memberData);
      if ($memoryHandle !== FALSE) {
        inspectPngStream($file, $memoryHandle, strlen($memberData), $fileDetections);
        fclose($memoryHandle); } } }
  if ($containerFormat !== '') {
    if (($depth + 1) > $MaxArchiveDepth) {
      recordDetection($file, 'Bomb.Archive.NestingDepth', 'Structure Match', 'containers are nested more than '.$MaxArchiveDepth.' deep', 'high', $fileDetections);
      $inspection['halted'] = TRUE; }
    else {
      if (!$memberIsComplete) noteIncomplete($inspection, 'a nested archive larger than the entry budget was not opened');
      else {
        redeclare($memoryHandle, openMemoryStream($memberData));
        if ($memoryHandle !== FALSE) {
          inspectContainerHandle($file, $memoryHandle, strlen($memberData), $containerFormat, $memberPath, $depth + 1, $fileDetections, $handlerHits, $inspection);
          fclose($memoryHandle); } } } }
  $MemberContext = $outerContext;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($outerContext, $memberHeader, $containerFormat, $imageFormat, $scopeTypes, $memoryHandle, $definitionRecord, $memberPath, $memberName, $memberData, $memberIsComplete, $depth, $file);
  return $MemberInspected; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to inspect a zip archive through a stream handle.
// / The directory is judged before any member is read. Members that share stored bytes
// / & a directory that declares an absurd total are both bombs & stop the inspection.
function inspectZipHandle($file, $handle, $sourceSize, $containerPath, $depth, &$fileDetections, &$handlerHits, &$inspection) {
  global $MaxArchiveEntrySize, $MaxArchiveTotalSize, $MaxArchiveCompressionRatio, $MemberContext;
  // / Set variables.
  $ZipInspected = FALSE;
  $outerContext = $MemberContext;
  $directory = findZipDirectory($handle, $sourceSize);
  $directoryRead = FALSE;
  $entryList = $extentList = array();
  $declaredTotal = $previousEnd = $readLimit = $consumedBytes = 0;
  $entriesOverlap = $memberComplete = FALSE;
  $memberData = $memberNote = $memberPath = '';
  $MemberContext = $containerPath;
  if (!$directory['found']) noteIncomplete($inspection, 'a zip directory could not be read');
  else {
    list($directoryRead, $entryList) = readZipDirectory($handle, $directory);
    $ZipInspected = TRUE;
    if (!$directoryRead) noteIncomplete($inspection, 'a zip directory is damaged');
    if ($directory['entries'] > count($entryList) && $directoryRead) noteIncomplete($inspection, 'an archive lists '.$directory['entries'].' entries & '.count($entryList).' were inspected');
    foreach ($entryList as $entryRecord) {
      $declaredTotal = $declaredTotal + $entryRecord['size'];
      if ($entryRecord['compressed'] > 0) $extentList[] = array($entryRecord['offset'], $entryRecord['offset'] + 30 + strlen($entryRecord['name']) + $entryRecord['compressed']); }
    usort($extentList, function($firstExtent, $secondExtent) { return $firstExtent[0] <=> $secondExtent[0]; });
    foreach ($extentList as $extentRecord) {
      if ($extentRecord[0] < $previousEnd) $entriesOverlap = TRUE;
      $previousEnd = max($previousEnd, $extentRecord[1]); }
    if ($entriesOverlap) {
      recordDetection($file, 'Bomb.Archive.OverlappingEntries', 'Structure Match', 'members of the archive share the same stored bytes', 'high', $fileDetections);
      $inspection['halted'] = TRUE; }
    if (!$inspection['halted'] && $declaredTotal > $MaxArchiveTotalSize && $sourceSize > 0 && intdiv($declaredTotal, $sourceSize) > $MaxArchiveCompressionRatio) {
      recordDetection($file, 'Bomb.Archive.TotalExpansion', 'Structure Match', countWithNoun(count($entryList), 'member declares', 'members declare').' '.$declaredTotal.' bytes from an archive of '.$sourceSize, 'high', $fileDetections);
      $inspection['halted'] = TRUE; }
    foreach ($entryList as $entryRecord) if (!$inspection['halted'] && substr($entryRecord['name'], -1) !== '/') {
      redeclare($memberPath, ($containerPath !== '' ? $containerPath.' > ' : '').$entryRecord['name']);
      $MemberContext = $memberPath;
      // / A member that declares a huge expansion is refused before any of it is read.
      if ($entryRecord['compressed'] > 0 && $entryRecord['size'] > $MaxArchiveEntrySize && intdiv($entryRecord['size'], $entryRecord['compressed']) > $MaxArchiveCompressionRatio) {
        recordDetection($file, 'Bomb.Archive.CompressionRatio', 'Structure Match', 'declares '.$entryRecord['size'].' bytes from '.$entryRecord['compressed'], 'high', $fileDetections);
        $inspection['halted'] = TRUE; }
      else {
        redeclare($readLimit, claimMemberBudget($inspection));
        if ($readLimit > 0) {
          list($memberData, $memberComplete, $memberNote, $consumedBytes) = readZipMember($handle, $sourceSize, $entryRecord, $readLimit);
          if ($memberNote === 'encrypted') noteIncomplete($inspection, 'an encrypted member cannot be inspected');
          if ($memberNote === 'unsupported') noteIncomplete($inspection, 'a member uses a compression method that is not read');
          if ($memberNote === 'damaged') noteIncomplete($inspection, 'a damaged member could not be read');
          if ($memberNote === 'mismatch') recordDetection($file, 'Malformed.Archive.HeaderMismatch', 'Structure Match', 'the two headers describing the member disagree', 'medium', $fileDetections);
          if ($memberData !== '' && !$memberComplete) {
            // / A member that reaches the limit from very little input lied about its size.
            if (strlen($memberData) >= $readLimit && $consumedBytes > 0 && intdiv(strlen($memberData), $consumedBytes) > $MaxArchiveCompressionRatio) {
              recordDetection($file, 'Bomb.Archive.CompressionRatio', 'Structure Match', 'expands past '.strlen($memberData).' bytes from '.$consumedBytes, 'high', $fileDetections);
              $inspection['halted'] = TRUE; }
            else noteIncomplete($inspection, 'a member larger than the entry budget was inspected in part'); }
          $inspection['bytes'] = $inspection['bytes'] + strlen($memberData);
          if ($memberData !== '' && !$inspection['halted']) inspectMemberData($file, $memberPath, basename(str_replace('\\', '/', $entryRecord['name'])), $memberData, $memberComplete, $depth, $fileDetections, $handlerHits, $inspection); } } } }
  $MemberContext = $outerContext;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($outerContext, $directory, $directoryRead, $entryList, $extentList, $entryRecord, $extentRecord, $declaredTotal, $previousEnd, $readLimit, $consumedBytes, $entriesOverlap, $memberComplete, $memberData, $memberNote, $memberPath, $file, $handle, $sourceSize, $containerPath, $depth);
  return $ZipInspected; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to inspect a gzip stream through a stream handle.
// / The stream is inflated up to the member budget, then inspected as one member.
function inspectGzipHandle($file, $handle, $sourceSize, $containerPath, $depth, &$fileDetections, &$handlerHits, &$inspection) {
  global $MaxArchiveCompressionRatio, $MemberContext;
  // / Set variables.
  $GzipInspected = FALSE;
  $outerContext = $MemberContext;
  $inflateContext = FALSE;
  $streamData = $dataPiece = $outputPiece = '';
  $streamComplete = FALSE;
  $keepReading = TRUE;
  $consumedBytes = 0;
  $readLimit = claimMemberBudget($inspection);
  $streamPath = ($containerPath !== '' ? $containerPath.' > ' : '').'gzip stream';
  $MemberContext = $streamPath;
  if (function_exists('inflate_init')) $inflateContext = @inflate_init(ZLIB_ENCODING_GZIP);
  if ($inflateContext === FALSE) noteIncomplete($inspection, 'a gzip stream cannot be read without the zlib extension');
  if ($inflateContext !== FALSE && $readLimit > 0 && @fseek($handle, 0) === 0) {
    $GzipInspected = TRUE;
    while ($keepReading && !feof($handle)) {
      redeclare($dataPiece, strval(fread($handle, 8192)));
      if ($dataPiece === '') $keepReading = FALSE;
      else {
        $consumedBytes = $consumedBytes + strlen($dataPiece);
        redeclare($outputPiece, @inflate_add($inflateContext, $dataPiece));
        if ($outputPiece === FALSE) $keepReading = FALSE;
        else {
          $streamData = $streamData.$outputPiece;
          if (inflate_get_status($inflateContext) === ZLIB_STREAM_END) {
            $streamComplete = TRUE;
            $keepReading = FALSE; }
          if (strlen($streamData) > $readLimit) {
            $streamData = substr($streamData, 0, $readLimit);
            $streamComplete = FALSE;
            $keepReading = FALSE; } } } }
    if ($streamData !== '' && !$streamComplete) {
      if (strlen($streamData) >= $readLimit && intdiv(strlen($streamData), $consumedBytes) > $MaxArchiveCompressionRatio) {
        recordDetection($file, 'Bomb.Archive.CompressionRatio', 'Structure Match', 'expands past '.strlen($streamData).' bytes from '.$consumedBytes, 'high', $fileDetections);
        $inspection['halted'] = TRUE; }
      else noteIncomplete($inspection, 'a compressed stream larger than the entry budget was inspected in part'); }
    $inspection['bytes'] = $inspection['bytes'] + strlen($streamData);
    if ($streamData !== '' && !$inspection['halted']) inspectMemberData($file, $streamPath, 'gzip stream', $streamData, $streamComplete, $depth, $fileDetections, $handlerHits, $inspection); }
  $MemberContext = $outerContext;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($outerContext, $inflateContext, $streamData, $dataPiece, $outputPiece, $streamComplete, $keepReading, $consumedBytes, $readLimit, $streamPath, $file, $handle, $sourceSize, $containerPath, $depth);
  return $GzipInspected; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to inspect a tar archive through a stream handle.
// / A tar is a run of 512 byte headers, each followed by its member padded to a block.
function inspectTarHandle($file, $handle, $sourceSize, $containerPath, $depth, &$fileDetections, &$handlerHits, &$inspection) {
  // / Set variables.
  $TarInspected = FALSE;
  $position = 0;
  $keepWalking = TRUE;
  $tarHeader = $memberName = $namePrefix = $longName = $sizeField = $memberType = $memberData = $memberPath = '';
  $memberSize = $readLimit = 0;
  $memberComplete = FALSE;
  $paxParts = array();
  while ($keepWalking && !$inspection['halted'] && ($position + 512) <= $sourceSize && @fseek($handle, $position) === 0) {
    redeclare($tarHeader, strval(fread($handle, 512)));
    redeclare($memberName, rtrim(substr($tarHeader, 0, 100), "\0"));
    // / A block of nothing is how a tar says it has finished.
    if (strlen($tarHeader) < 512 or $memberName === '') $keepWalking = FALSE;
    else {
      $TarInspected = TRUE;
      redeclare($namePrefix, rtrim(substr($tarHeader, 345, 155), "\0"));
      if (substr($tarHeader, 257, 5) === 'ustar' && $namePrefix !== '') $memberName = $namePrefix.'/'.$memberName;
      redeclare($sizeField, substr($tarHeader, 124, 12));
      // / A size is written in octal, or in binary when the leading bit is set.
      if ((ord($sizeField[0]) & 0x80) !== 0) redeclare($memberSize, intval(unpack('Jvalue', substr($sizeField, 4, 8))['value']));
      else redeclare($memberSize, intval(octdec(preg_replace('/[^0-7]/', '', $sizeField))));
      redeclare($memberType, substr($tarHeader, 156, 1));
      $position = $position + 512;
      if ($memberSize < 0 or ($position + $memberSize) > $sourceSize) {
        noteIncomplete($inspection, 'a tar archive ends before its last member does');
        $memberSize = max(0, $sourceSize - $position);
        $keepWalking = FALSE; }
      // / A long name is stored as a member of its own, ahead of the member it names.
      if ($memberType === 'L') redeclare($longName, rtrim(strval(fread($handle, min($memberSize, 4096))), "\0"));
      // / A pax header does the same job & writes the name as a path record.
      if ($memberType === 'x' && preg_match('/^\d+ path=([^\n]*)$/m', strval(fread($handle, min($memberSize, 8192))), $paxParts)) redeclare($longName, $paxParts[1]);
      if (($memberType === '0' or $memberType === "\0" or $memberType === '7') && $memberSize > 0) {
        if ($longName !== '') {
          $memberName = $longName;
          $longName = ''; }
        redeclare($readLimit, claimMemberBudget($inspection));
        if ($readLimit > 0) {
          redeclare($memberData, strval(fread($handle, min($memberSize, $readLimit))));
          redeclare($memberComplete, $memberSize <= $readLimit && $keepWalking);
          if (!$memberComplete && $keepWalking) noteIncomplete($inspection, 'a member larger than the entry budget was inspected in part');
          $inspection['bytes'] = $inspection['bytes'] + strlen($memberData);
          redeclare($memberPath, ($containerPath !== '' ? $containerPath.' > ' : '').$memberName);
          if ($memberData !== '') inspectMemberData($file, $memberPath, basename($memberName), $memberData, $memberComplete, $depth, $fileDetections, $handlerHits, $inspection); } }
      $position = $position + (intdiv($memberSize + 511, 512) * 512); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($position, $keepWalking, $tarHeader, $memberName, $namePrefix, $longName, $sizeField, $memberType, $memberData, $memberPath, $memberSize, $readLimit, $memberComplete, $paxParts, $file, $handle, $sourceSize, $containerPath, $depth);
  return $TarInspected; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to inspect a container of any supported format through a stream handle.
// / The depth counts how many containers sit above this one.
function inspectContainerHandle($file, $handle, $sourceSize, $containerFormat, $containerPath, $depth, &$fileDetections, &$handlerHits, &$inspection) {
  // / Set variables.
  $ContainerInspected = FALSE;
  if ($containerFormat === 'zip') $ContainerInspected = inspectZipHandle($file, $handle, $sourceSize, $containerPath, $depth, $fileDetections, $handlerHits, $inspection);
  if ($containerFormat === 'gzip') $ContainerInspected = inspectGzipHandle($file, $handle, $sourceSize, $containerPath, $depth, $fileDetections, $handlerHits, $inspection);
  if ($containerFormat === 'tar') $ContainerInspected = inspectTarHandle($file, $handle, $sourceSize, $containerPath, $depth, $fileDetections, $handlerHits, $inspection);
  if ($containerFormat === 'bzip2') noteIncomplete($inspection, 'a bzip2 stream is not read');
  processOutput('Inspected a '.$containerFormat.' container at depth '.$depth.'.', 'log', 0, FALSE, FALSE, FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($containerFormat, $containerPath, $depth, $sourceSize, $file, $handle);
  return $ContainerInspected; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to render a value safely into a report line.
// / A byte signature is raw bytes. Writing those into the report would put control
// / characters & broken encoding into a text file that other tools read, so any byte
// / without a printable form is written back as the escape that names it.
function printableValue($rawValue) {
  // / Set variables.
  $PrintableValue = $rawValue;
  if (preg_match('/[^\x20-\x7E]/', $rawValue)) $PrintableValue = preg_replace_callback('/[^\x20-\x7E]/', function($byteMatch) { return sprintf('\\x%02x', ord($byteMatch[0])); }, $rawValue);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($rawValue);
  return $PrintableValue; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decode the byte escapes a definition value may carry.
// / A signature is often a run of bytes that has no printable form, such as the serial
// / number of a code signing certificate as it is stored inside a signed executable.
// / Those are written as \x60\x4a and are decoded here into the bytes they name.
function decodeDefinitionValue($rawValue) {
  // / Set variables.
  $DecodedValue = $rawValue;
  if (strpos($rawValue, '\\x') !== FALSE) $DecodedValue = preg_replace_callback('/\\\\x([0-9A-Fa-f]{2})/', function($escapeMatch) { return chr(hexdec($escapeMatch[1])); }, $rawValue);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($rawValue);
  return $DecodedValue; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the index file that belongs to a definitions file.
function hashIndexPath($DefsFile) {
  // / Set variables.
  $IndexPath = $DefsFile.'.idx';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($DefsFile);
  return $IndexPath; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to pack hash records into sorted sections that can be searched by halving.
function packHashSections($hashRecords) {
  // / Set variables.
  $PackedSections = array('md5' => '', 'sha1' => '', 'sha256' => '', 'counts' => array('md5' => 0, 'sha1' => 0, 'sha256' => 0), 'strings' => array());
  foreach (array('md5', 'sha1', 'sha256') as $hashKind) if (isset($hashRecords[$hashKind])) {
    sort($hashRecords[$hashKind], SORT_STRING);
    $PackedSections['counts'][$hashKind] = count($hashRecords[$hashKind]);
    $PackedSections[$hashKind] = implode('', $hashRecords[$hashKind]); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($hashKind, $hashRecords);
  return $PackedSections; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to build a packed index beside a definitions file.
// / A hash held in a PHP array costs far more than the hash itself, because the array
// / holds a whole record for every one. A million samples that way needs a gigabyte of
// / memory & eleven seconds to parse. The same million as raw bytes needs thirty four
// / megabytes & fifteen milliseconds, because it is one string read straight off disk.
// / Each record is the hash itself followed by the offset of its line in the definitions
// / file, so the name & the confidence are fetched from disk only when something matches.
// / The string indicators are kept in the index too, so a valid index means the
// / definitions file is never read at all until a hash actually hits.
function buildHashIndex($DefsFile, $indexFile, $hashRecords, $stringIndicators, $sourceHash) {
  // / Set variables.
  $IndexBuilt = FALSE;
  $indexBody = '';
  $sectionText = '';
  $stringBlob = '';
  $headerText = '';
  $hashKind = '';
  $sectionWidths = array('md5' => 20, 'sha1' => 24, 'sha256' => 36);
  $packedSections = packHashSections($hashRecords);
  $sectionCounts = $packedSections['counts'];
  $indexBody = $packedSections['md5'].$packedSections['sha1'].$packedSections['sha256'];
  $stringBlob = serialize($stringIndicators);
  // / The header carries the hash of the definitions file this index was built from, so
  // / an index left behind by an older definitions file is recognised rather than trusted.
  $headerText = 'SCIDX1'."\n".$sourceHash."\n".$sectionCounts['md5'].' '.$sectionCounts['sha1'].' '.$sectionCounts['sha256'].' '.strlen($stringBlob)."\n";
  if (@file_put_contents($indexFile, $headerText.$indexBody.$stringBlob) !== FALSE) {
    applyOwnership($indexFile, FALSE);
    $IndexBuilt = TRUE; }
  else processOutput('Cannot write the definitions index, so the definitions will be parsed on every scan:  '.$indexFile, 'error', 550, TRUE, FALSE, FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($indexBody, $sectionText, $stringBlob, $headerText, $hashKind, $sectionWidths, $sectionCounts, $packedSections, $hashRecords, $stringIndicators, $indexFile, $sourceHash);
  return $IndexBuilt; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to load a packed index, when one exists & matches the definitions file.
// / The whole index arrives as one string, which is one allocation rather than a million.
function loadHashIndex($indexFile, $sourceHash) {
  // / Set variables.
  $IndexLoaded = FALSE;
  $Index = array('md5' => '', 'sha1' => '', 'sha256' => '', 'counts' => array('md5' => 0, 'sha1' => 0, 'sha256' => 0), 'strings' => array());
  $indexHandle = FALSE;
  $headerLine = array('', '', '');
  $sectionCounts = array();
  $stringLength = 0;
  $stringBlob = '';
  $sectionsRead = FALSE;
  // / Each section is read straight from the file rather than sliced out of one big
  // / string. Slicing would hold the whole index & the copy at the same time, which
  // / doubles the peak for no reason on the one path that exists to save memory.
  if (file_exists($indexFile)) {
    $indexHandle = @fopen($indexFile, 'rb');
    if ($indexHandle !== FALSE) {
      $headerLine[0] = trim(strval(fgets($indexHandle)));
      $headerLine[1] = trim(strval(fgets($indexHandle)));
      $headerLine[2] = trim(strval(fgets($indexHandle)));
      if ($headerLine[0] === 'SCIDX1' && $headerLine[1] === $sourceHash) {
        $sectionCounts = explode(' ', $headerLine[2]);
        if (count($sectionCounts) === 4) {
          $Index['counts']['md5'] = intval($sectionCounts[0]);
          $Index['counts']['sha1'] = intval($sectionCounts[1]);
          $Index['counts']['sha256'] = intval($sectionCounts[2]);
          $stringLength = intval($sectionCounts[3]);
          $sectionsRead = TRUE;
          if ($Index['counts']['md5'] > 0) $Index['md5'] = strval(fread($indexHandle, $Index['counts']['md5'] * 20));
          if ($Index['counts']['sha1'] > 0) $Index['sha1'] = strval(fread($indexHandle, $Index['counts']['sha1'] * 24));
          if ($Index['counts']['sha256'] > 0) $Index['sha256'] = strval(fread($indexHandle, $Index['counts']['sha256'] * 36));
          // / A truncated section would silently answer no to every hash beyond where it
          // / stops, so a short read is a refusal rather than a smaller index.
          if (strlen($Index['md5']) !== $Index['counts']['md5'] * 20) $sectionsRead = FALSE;
          if (strlen($Index['sha1']) !== $Index['counts']['sha1'] * 24) $sectionsRead = FALSE;
          if (strlen($Index['sha256']) !== $Index['counts']['sha256'] * 36) $sectionsRead = FALSE;
          if ($sectionsRead) {
            $stringBlob = $stringLength > 0 ? strval(fread($indexHandle, $stringLength)) : serialize(array());
            $Index['strings'] = @unserialize($stringBlob, array('allowed_classes' => FALSE));
            if (is_array($Index['strings'])) $IndexLoaded = TRUE; } } }
      fclose($indexHandle); } }
  if (!$IndexLoaded) $Index = array('md5' => '', 'sha1' => '', 'sha256' => '', 'counts' => array('md5' => 0, 'sha1' => 0, 'sha256' => 0), 'strings' => array());
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($indexHandle, $headerLine, $sectionCounts, $stringLength, $stringBlob, $sectionsRead, $indexFile, $sourceHash);
  return array($IndexLoaded, $Index); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to find a hash inside a packed index section, by halving the range.
// / Returns the offset of the matching line in the definitions file, or minus one.
function lookupPackedHash($sectionBlob, $sectionCount, $hashWidth, $rawHash) {
  // / Set variables.
  $MatchOffset = -1;
  $lowIndex = 0;
  $highIndex = $sectionCount - 1;
  $middleIndex = 0;
  $recordWidth = $hashWidth + 4;
  $comparison = 0;
  $offsetBytes = array();
  while ($lowIndex <= $highIndex && $MatchOffset < 0) {
    $middleIndex = intdiv($lowIndex + $highIndex, 2);
    $comparison = strcmp(substr($sectionBlob, $middleIndex * $recordWidth, $hashWidth), $rawHash);
    if ($comparison === 0) {
      $offsetBytes = unpack('V', substr($sectionBlob, ($middleIndex * $recordWidth) + $hashWidth, 4));
      $MatchOffset = intval($offsetBytes[1]); }
    else {
      if ($comparison < 0) $lowIndex = $middleIndex + 1;
      else $highIndex = $middleIndex - 1; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($lowIndex, $highIndex, $middleIndex, $recordWidth, $comparison, $offsetBytes, $sectionBlob, $rawHash, $sectionCount, $hashWidth);
  return $MatchOffset; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read one definition line from the definitions file, at a known offset.
// / This only happens when a hash has already matched, which is rare, so the cost of
// / going back to disk for the name & the confidence is paid almost never.
function readDefinitionAtOffset($DefsFile, $lineOffset) {
  // / Set variables.
  $RecordFound = FALSE;
  $Record = array('kind' => '', 'name' => '', 'value' => '', 'confidence' => '', 'scope' => '', 'src' => '');
  $handle = @fopen($DefsFile, 'rb');
  $lineText = '';
  if ($handle !== FALSE) {
    if (@fseek($handle, $lineOffset) === 0) {
      $lineText = fgets($handle);
      if (is_string($lineText)) list($RecordFound, $Record) = parseDefinitionRecord(trim($lineText)); }
    fclose($handle); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($handle, $lineText, $DefsFile, $lineOffset);
  return array($RecordFound, $Record); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read one definition record in the ScanCore definition format.
// / A record is  <kind> <name> = <value> [attribute=value ...]  & nothing is positional,
// / because the retired tab delimited format shipped 49 hashes in the wrong column & no
// / maintainer could tell by looking that anything was wrong.
function parseDefinitionRecord($recordBody) {
  // / Set variables.
  $RecordParsed = FALSE;
  $Record = array('kind' => '', 'name' => '', 'value' => '', 'confidence' => '', 'scope' => '', 'src' => '');
  $keywordEnd = $equalsPosition = $closingQuote = 0;
  $remainder = $attributeText = $attributePair = '';
  $attributeKey = $attributeValue = '';
  $validKinds = array('md5', 'sha1', 'sha256', 'name', 'data', 'host', 'url');
  $keywordEnd = strpos($recordBody, ' ');
  $equalsPosition = strpos($recordBody, '=');
  if ($keywordEnd !== FALSE && $equalsPosition !== FALSE && $keywordEnd < $equalsPosition) {
    $Record['kind'] = strtolower(trim(substr($recordBody, 0, $keywordEnd)));
    $Record['name'] = trim(substr($recordBody, $keywordEnd + 1, $equalsPosition - $keywordEnd - 1));
    $remainder = ltrim(substr($recordBody, $equalsPosition + 1));
    // / A quoted value keeps whatever whitespace it arrived with & may contain a space.
    if (substr($remainder, 0, 1) === '"') {
      // / A quote written after a backslash belongs to the value & does not close it.
      $closingQuote = 0;
      do $closingQuote = strpos($remainder, '"', $closingQuote + 1);
      while ($closingQuote !== FALSE && substr($remainder, $closingQuote - 1, 1) === '\\');
      if ($closingQuote !== FALSE) {
        $Record['value'] = decodeDefinitionValue(str_replace('\\"', '"', substr($remainder, 1, $closingQuote - 1)));
        $attributeText = trim(substr($remainder, $closingQuote + 1));
        $RecordParsed = TRUE; } }
    else {
      // / A hash needs no quoting, so the first word is the value & the rest are attributes.
      $Record['value'] = trim(explode(' ', $remainder)[0]);
      $attributeText = trim(substr($remainder, strlen($Record['value'])));
      if ($Record['value'] !== '') $RecordParsed = TRUE; }
    // / Read the trailing attributes, each of which is a plain key & value pair.
    if ($RecordParsed) foreach (explode(' ', $attributeText) as $attributePair) if (trim($attributePair) !== '') {
      redeclare($attributeKey, strtolower(trim(explode('=', $attributePair)[0])));
      redeclare($attributeValue, isset(explode('=', $attributePair)[1]) ? trim(explode('=', $attributePair)[1]) : '');
      if (isset($Record[$attributeKey])) $Record[$attributeKey] = $attributeValue; }
    if (!in_array($Record['kind'], $validKinds, TRUE)) $RecordParsed = FALSE;
    if ($Record['name'] === '' or $Record['value'] === '') $RecordParsed = FALSE; }
  // / A hash is an exact match & is trusted. Everything else is a heuristic & is not.
  if ($RecordParsed && $Record['confidence'] === '') {
    if ($Record['kind'] === 'md5' or $Record['kind'] === 'sha1' or $Record['kind'] === 'sha256') $Record['confidence'] = 'high';
    else $Record['confidence'] = 'medium'; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($keywordEnd, $equalsPosition, $closingQuote, $remainder, $attributeText, $attributePair, $attributeKey, $attributeValue, $validKinds, $recordBody);
  return array($RecordParsed, $Record); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the definitions file into a usable structure.
// / Hashes are stored under their own value as the array key, so looking one up costs
// / nothing regardless of how many there are. The retired format walked every definition
// / for every file, which is why it could never carry a large hash set.
function loadDefs($DefsFile) {
  global $UseHashIndex, $HashIndexThreshold, $RebuildHashIndex, $HashIndex, $HashIndexActive;
  // / Set variables.
  $DefsLoaded = FALSE;
  $Defs = array('md5' => array(), 'sha1' => array(), 'sha256' => array(), 'name' => array(), 'data' => array(), 'host' => array(), 'url' => array());
  $HashIndex = array('md5' => '', 'sha1' => '', 'sha256' => '', 'counts' => array('md5' => 0, 'sha1' => 0, 'sha256' => 0), 'strings' => array());
  $HashIndexActive = FALSE;
  $indexFile = hashIndexPath($DefsFile);
  $indexLoaded = FALSE;
  $hashRecords = array('md5' => array(), 'sha1' => array(), 'sha256' => array());
  $lineOffset = $hashTotal = $hashCount = 0;
  $DefData = '';
  $DefsLongestSignature = 0;
  $defsHandle = FALSE;
  $rawLine = '';
  $recordStart = 0;
  $recordParsed = FALSE;
  $definitionRecord = array();
  $recordBody = '';
  $usableCount = $malformedCount = $retiredFormatCount = 0;
  if (!file_exists($DefsFile)) processOutput('Cannot load the definitions file:  '.$DefsFile, 'error', 500, TRUE, TRUE, TRUE);
  else {
    processOutput('Loaded the definitions file:  '.$DefsFile, 'log', 0, FALSE, FALSE, FALSE);
    $DefData = hash_file('sha256', $DefsFile);
    // / A valid index means the definitions file is never read at all, which is the
    // / whole point. A million hashes cost fifteen milliseconds instead of eleven seconds.
    if ($UseHashIndex && !$RebuildHashIndex) list($indexLoaded, $HashIndex) = loadHashIndex($indexFile, $DefData);
    if ($indexLoaded) {
      $HashIndexActive = TRUE;
      $Defs['name'] = isset($HashIndex['strings']['name']) ? $HashIndex['strings']['name'] : array();
      $Defs['data'] = isset($HashIndex['strings']['data']) ? $HashIndex['strings']['data'] : array();
      $Defs['host'] = isset($HashIndex['strings']['host']) ? $HashIndex['strings']['host'] : array();
      $Defs['url'] = isset($HashIndex['strings']['url']) ? $HashIndex['strings']['url'] : array();
      $DefsLongestSignature = isset($HashIndex['strings']['longest']) ? intval($HashIndex['strings']['longest']) : 0;
      $usableCount = $HashIndex['counts']['md5'] + $HashIndex['counts']['sha1'] + $HashIndex['counts']['sha256'] + count($Defs['name']) + count($Defs['data']) + count($Defs['host']) + count($Defs['url']);
      processOutput('Loaded '.$usableCount.' definitions from the packed index.', 'log', 0, FALSE, FALSE, FALSE);
      $DefsLoaded = TRUE; }
    else {
    // / Read the file a line at a time. Loading every line into an array first costs
    // / more than the definitions themselves & is what made a large set impossible.
    $defsHandle = @fopen($DefsFile, 'rb');
    if ($defsHandle === FALSE) processOutput('Cannot open the definitions file for reading:  '.$DefsFile, 'error', 500, TRUE, TRUE, TRUE);
    while ($defsHandle !== FALSE && ($rawLine = fgets($defsHandle)) !== FALSE) {
      // / Remember where each line starts, so a matching hash can find its own record.
      $recordStart = $lineOffset;
      $lineOffset = $lineOffset + strlen($rawLine);
      redeclare($recordBody, trim($rawLine));
      if ($recordBody !== '' && substr($recordBody, 0, 1) !== '#') {
        // / Recognize the retired tab delimited format & say so, rather than reading nothing.
        if (strpos($recordBody, "\t") !== FALSE && strpos($recordBody, '=') === FALSE) $retiredFormatCount++;
        else {
          list($recordParsed, $definitionRecord) = parseDefinitionRecord($recordBody);
          if (!$recordParsed) $malformedCount++;
          else {
            // / A hash, a host & a url are all looked up by their own value, so the cost of
            // / holding one does not grow with how many of them there are.
            if ($definitionRecord['kind'] === 'md5' or $definitionRecord['kind'] === 'sha1' or $definitionRecord['kind'] === 'sha256') {
              // / Hold the whole record only while the set is small enough not to be indexed.
              // / Above that the packed record below is the only copy that is kept.
              if (!$UseHashIndex or $hashCount < $HashIndexThreshold) $Defs[$definitionRecord['kind']][strtolower($definitionRecord['value'])] = $definitionRecord;
              $hashCount++;
              // / Pack the hash with the offset of its own line, ready to be indexed.
              $hashRecords[$definitionRecord['kind']][] = hex2bin(strtolower($definitionRecord['value'])).pack('V', $recordStart); }
            else {
              if ($definitionRecord['kind'] === 'host' or $definitionRecord['kind'] === 'url') $Defs[$definitionRecord['kind']][strtolower(trim($definitionRecord['value']))] = $definitionRecord;
              else {
                // / A data or name indicator has no shape, so it is searched for directly.
                $Defs[$definitionRecord['kind']][] = $definitionRecord;
                if (strlen($definitionRecord['value']) > $DefsLongestSignature) $DefsLongestSignature = strlen($definitionRecord['value']); } }
            $usableCount++; } } } }
    if ($defsHandle !== FALSE) fclose($defsHandle);
    processOutput('Found '.$usableCount.' definitions.', 'log', 0, FALSE, FALSE, FALSE);
    processOutput('Definitions by kind:  '.count($Defs['md5']).' md5, '.count($Defs['sha1']).' sha1, '.count($Defs['sha256']).' sha256, '.count($Defs['name']).' name, '.count($Defs['data']).' data, '.count($Defs['host']).' host, '.count($Defs['url']).' url.', 'log', 0, FALSE, FALSE, FALSE);
    if ($malformedCount > 0) processOutput('Ignored '.$malformedCount.' malformed definition records.', 'warning', 0, TRUE, FALSE, FALSE);
    // / A whole file in the retired format is an upgrade that never finished, not a typo.
    if ($retiredFormatCount > 0) processOutput('The definitions file holds '.$retiredFormatCount.' records in the retired tab delimited format, which is no longer read. Rebuild it with the ScanCore_Definitions builder.', 'error', 540, TRUE, TRUE, FALSE);
    if ($usableCount < 1) processOutput('The definitions file contains no usable definitions:  '.$DefsFile, 'error', 510, TRUE, TRUE, FALSE);
    else $DefsLoaded = TRUE;
    // / Build an index only when there are enough hashes for it to be worth the file.
    // / A small set is cheaper to parse than to index, & needs no second file on disk.
    $hashTotal = count($hashRecords['md5']) + count($hashRecords['sha1']) + count($hashRecords['sha256']);
    if ($DefsLoaded && $UseHashIndex && $hashTotal >= $HashIndexThreshold) {
      // / Search the packed records from this run onward, whether or not the file gets written.
      $HashIndex = packHashSections($hashRecords);
      $HashIndexActive = TRUE;
      $Defs['md5'] = $Defs['sha1'] = $Defs['sha256'] = array();
      processOutput('Building a packed index for '.$hashTotal.' hashes.', 'log', 0, TRUE, FALSE, FALSE);
      buildHashIndex($DefsFile, $indexFile, $hashRecords, array('name' => $Defs['name'], 'data' => $Defs['data'], 'host' => $Defs['host'], 'url' => $Defs['url'], 'longest' => $DefsLongestSignature), $DefData); }
    // / Remove a stale index when this set is too small to keep one.
    if ($DefsLoaded && $hashTotal < $HashIndexThreshold) if (file_exists($indexFile)) @unlink($indexFile); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($defsHandle, $rawLine, $recordBody, $recordStart, $recordParsed, $definitionRecord, $usableCount, $malformedCount, $retiredFormatCount, $indexFile, $indexLoaded, $hashRecords, $lineOffset, $hashTotal, $hashCount);
  return array($DefsLoaded, $Defs, $DefData, $DefsLongestSignature); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to record a single detection against a file.
// / Confidence decides which word opens the line. A whole file hash is an exact match &
// / opens with Infected. A heuristic opens with Suspicious, so a caller testing for
// / Infected is never handed a filename pattern with the weight of a confirmed hash.
// / A detection made inside a container names the member it was made in.
function recordDetection($file, $definitionName, $matchType, $matchValue, $confidence, &$fileDetections) {
  global $DetectionCount, $ReportSuspicious, $MemberContext;
  // / Set variables.
  $DetectionRecorded = FALSE;
  $detectionKey = $definitionName.'|'.$matchType.'|'.$MemberContext;
  $memberText = ($MemberContext !== '') ? ' in '.printableName($MemberContext) : '';
  $openingWord = 'Suspicious: ';
  if ($confidence === 'high') $openingWord = 'Infected: ';
  // / A signature that spans a chunk boundary appears twice, so report it only once.
  if (!isset($fileDetections[$detectionKey])) {
    if ($openingWord === 'Infected: ' or $ReportSuspicious) {
      $fileDetections[$detectionKey] = $confidence;
      $DetectionCount++;
      $DetectionRecorded = TRUE;
      processOutput($openingWord.printableName($file).' ('.$definitionName.', '.$matchType.': '.printableValue($matchValue).$memberText.')', 'log', 0, TRUE, TRUE, FALSE); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($detectionKey, $memberText, $openingWord, $file, $definitionName, $matchType, $matchValue, $confidence);
  return $DetectionRecorded; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether a definition applies to the file being scanned.
// / A definition with no scope applies everywhere. A scoped definition applies only to
// / the classifier types the file was identified as, which is how a signature meant for
// / a PDF stops being tested against every other file on the disk.
function definitionIsInScope($definitionRecord, $candidateTypes) {
  // / Set variables.
  $DefinitionIsInScope = FALSE;
  if ($definitionRecord['scope'] === '' or strtolower($definitionRecord['scope']) === 'any') $DefinitionIsInScope = TRUE;
  else {
    if (is_array($candidateTypes)) foreach ($candidateTypes as $candidateType) if (strtolower($definitionRecord['scope']) === strtolower($candidateType)) $DefinitionIsInScope = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($candidateType, $definitionRecord, $candidateTypes);
  return $DefinitionIsInScope; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to pull every host shaped & url shaped run of text out of a block of data.
// / This exists so a host indicator costs nothing to add. Searching the data once for
// / each indicator means the cost grows with the size of the feed, & a feed of fifty
// / thousand hosts takes twenty minutes where four hundred takes eight seconds.
// / Extracting once & looking up what was found is the same trick that makes a hash
// / indicator free, & it holds however large the feed becomes.
function extractNetworkCandidates($haystack) {
  // / Set variables.
  $Candidates = array('hosts' => array(), 'urls' => array());
  $hostMatches = $urlMatches = array();
  // / A url is anything with a scheme, stopping at whitespace or a quoting character.
  preg_match_all('#[a-z][a-z0-9+.\-]{1,15}://[^\s"\'<>()\[\]\\\\]{4,2048}#i', $haystack, $urlMatches);
  // / A host is a run of name characters holding at least one dot, which covers a dotted
  // / quad as well. The first label & the rest of the run are each taken in one step
  // / with nothing given back, so the search is linear however long the run is.
  $hostsSearched = preg_match_all('#(?<![a-z0-9\-])[a-z0-9\-]++\.[a-z0-9.\-]++#i', $haystack, $hostMatches);
  // / A search the pattern engine gave up on would otherwise look like a clean block.
  if ($hostsSearched === FALSE) processOutput('Cannot search a block of data for host names, host indicators in it were not matched.', 'warning', 0, TRUE, TRUE, FALSE);
  if (isset($urlMatches[0])) $Candidates['urls'] = $urlMatches[0];
  if (isset($hostMatches[0])) $Candidates['hosts'] = $hostMatches[0];
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($hostMatches, $urlMatches, $haystack, $hostsSearched);
  return $Candidates; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to look a host candidate up, walking up its labels as it goes.
// / An indicator naming evil.example has to catch www.evil.example, so each candidate is
// / tried whole & then with its leftmost label removed, until the labels run out.
function lookupHostIndicator($hostCandidate, $hostIndex) {
  // / Set variables.
  $MatchedKey = '';
  // / A name is never longer than 253 characters, so only the end of a longer run counts.
  $workingHost = strtolower(trim(substr($hostCandidate, -253), '.-'));
  $dotPosition = 0;
  while ($workingHost !== '' && $MatchedKey === '') {
    if (isset($hostIndex[$workingHost])) $MatchedKey = $workingHost;
    else {
      $dotPosition = strpos($workingHost, '.');
      if ($dotPosition === FALSE) redeclare($workingHost, '');
      else redeclare($workingHost, substr($workingHost, $dotPosition + 1)); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($workingHost, $dotPosition, $hostCandidate, $hostIndex);
  return $MatchedKey; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to test a block of file content against the content indicators.
// / A data indicator is an arbitrary run of bytes with no shape, so it still needs a
// / search of its own. A host or url has a shape, so the data is read once & whatever
// / was found is looked up. That is why a url feed may be large & a data feed may not.
// / None of these are ever tested against the file path.
function matchContentIndicators($file, $haystack, $Defs, $candidateTypes, &$fileDetections) {
  // / Set variables.
  $MatchesFound = 0;
  $networkCandidates = array();
  $matchedKey = '';
  foreach ($Defs['data'] as $definitionRecord) if (definitionIsInScope($definitionRecord, $candidateTypes)) {
    if (stripos($haystack, $definitionRecord['value']) !== FALSE) {
      recordDetection($file, $definitionRecord['name'], 'Data Match', $definitionRecord['value'], $definitionRecord['confidence'], $fileDetections);
      $MatchesFound++; } }
  // / Read the data once for anything network shaped, but only when something is looking.
  if (count($Defs['host']) > 0 or count($Defs['url']) > 0) {
    $networkCandidates = extractNetworkCandidates($haystack);
    foreach ($networkCandidates['urls'] as $urlCandidate) {
      redeclare($matchedKey, strtolower(rtrim($urlCandidate, '.,;:)]}\'"')));
      if (isset($Defs['url'][$matchedKey])) if (definitionIsInScope($Defs['url'][$matchedKey], $candidateTypes)) {
        recordDetection($file, $Defs['url'][$matchedKey]['name'], 'URL Match', $Defs['url'][$matchedKey]['value'], $Defs['url'][$matchedKey]['confidence'], $fileDetections);
        $MatchesFound++; } }
    foreach ($networkCandidates['hosts'] as $hostCandidate) {
      redeclare($matchedKey, lookupHostIndicator($hostCandidate, $Defs['host']));
      if ($matchedKey !== '') if (definitionIsInScope($Defs['host'][$matchedKey], $candidateTypes)) {
        recordDetection($file, $Defs['host'][$matchedKey]['name'], 'Host Match', $Defs['host'][$matchedKey]['value'], $Defs['host'][$matchedKey]['confidence'], $fileDetections);
        $MatchesFound++; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($definitionRecord, $networkCandidates, $urlCandidate, $hostCandidate, $matchedKey, $haystack, $candidateTypes, $file);
  return $MatchesFound; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to test the name of a file against the name indicators.
// / Only the file name is tested. The directories above it belong to whoever runs the
// / scan & have nothing to do with what the file is.
function matchNameIndicators($file, $Defs, &$fileDetections) {
  // / Set variables.
  $MatchesFound = 0;
  $fileName = basename($file);
  foreach ($Defs['name'] as $definitionRecord) {
    if (stripos($fileName, $definitionRecord['value']) !== FALSE) {
      recordDetection($file, $definitionRecord['name'], 'Name Match', $definitionRecord['value'], $definitionRecord['confidence'], $fileDetections);
      $MatchesFound++; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($definitionRecord, $fileName, $file);
  return $MatchesFound; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to test the computed hashes of a file against the hash indicators.
// / Each hash is one array lookup, so the cost does not grow with the size of the set.
function matchHashSignatures($file, $md5Hash, $sha1Hash, $sha256Hash, $Defs, &$fileDetections) {
  global $HashIndex, $HashIndexActive, $DefsFile;
  // / Set variables.
  $MatchesFound = 0;
  $indexOffset = 0;
  $indexRecord = array();
  $recordFound = FALSE;
  $hashWidths = array('md5' => 16, 'sha1' => 20, 'sha256' => 32);
  $computedHashes = array('md5' => $md5Hash, 'sha1' => $sha1Hash, 'sha256' => $sha256Hash);
  // / A packed index answers the same question without holding a record per hash.
  if ($HashIndexActive) {
    foreach ($hashWidths as $hashKind => $hashWidth) if ($HashIndex['counts'][$hashKind] > 0) {
      redeclare($indexOffset, lookupPackedHash($HashIndex[$hashKind], $HashIndex['counts'][$hashKind], $hashWidth, hex2bin($computedHashes[$hashKind])));
      if ($indexOffset >= 0) {
        // / Only now, with a match in hand, is the definitions file read for the name.
        list($recordFound, $indexRecord) = readDefinitionAtOffset($DefsFile, $indexOffset);
        if ($recordFound) {
          recordDetection($file, $indexRecord['name'], strtoupper($hashKind).' Hash Match', $computedHashes[$hashKind], $indexRecord['confidence'], $fileDetections);
          $MatchesFound++; } } } }
  else {
  if (isset($Defs['md5'][$md5Hash])) {
    recordDetection($file, $Defs['md5'][$md5Hash]['name'], 'MD5 Hash Match', $md5Hash, $Defs['md5'][$md5Hash]['confidence'], $fileDetections);
    $MatchesFound++; }
  if (isset($Defs['sha1'][$sha1Hash])) {
    recordDetection($file, $Defs['sha1'][$sha1Hash]['name'], 'SHA1 Hash Match', $sha1Hash, $Defs['sha1'][$sha1Hash]['confidence'], $fileDetections);
    $MatchesFound++; }
  if (isset($Defs['sha256'][$sha256Hash])) {
    recordDetection($file, $Defs['sha256'][$sha256Hash]['name'], 'SHA256 Hash Match', $sha256Hash, $Defs['sha256'][$sha256Hash]['confidence'], $fileDetections);
    $MatchesFound++; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($indexOffset, $indexRecord, $recordFound, $hashWidths, $computedHashes, $md5Hash, $sha1Hash, $sha256Hash, $file, $hashKind, $hashWidth);
  return $MatchesFound; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to hash a file & check it for viruses against the static virus definitions.
// / The file is read once, in chunks, while all three hashes are computed alongside.
// / An overlap is carried between chunks so a signature spanning a boundary still matches.
// / The structure of an image or a container is inspected afterwards, whether or not
// / classification is switched on.
function virusCheck($file, $Defs, $DefData, $MemoryLimit, $ChunkSize, $DefsLongestSignature) {
  global $Infected, $Suspicious, $FileCount, $DefsRealPath, $ReportRealPath, $IncompleteCount, $MemberContext;
  global $Classifiers, $ClassifiersLoaded, $ClassifyContent, $ClassifierLongestToken, $HandlerFileCount, $CodeFileCount, $InspectArchivedContent, $InspectImages;
  // / Set variables.
  $CheckComplete = FALSE;
  $fileDetections = array();
  $fileIsScannable = TRUE;
  $targetPath = realpath($file);
  $displayFile = printableName($file);
  $fileSize = 0;
  $readSize = $overlapSize = 0;
  $handle = $structureHandle = FALSE;
  $buffer = $overlap = $window = '';
  $md5Context = $sha1Context = $sha256Context = NULL;
  $md5Hash = $sha1Hash = $sha256Hash = '';
  $readFailed = FALSE;
  $handlerHits = $languageHits = $candidateTypes = $scopeTypes = $inspection = $hiddenDirectory = array();
  $fileExtension = $extensionLanguage = $contentLanguage = $classifyMode = $magicType = $fileHeader = $containerFormat = $imageFormat = '';
  $classifyActive = ($ClassifyContent && $ClassifiersLoaded);
  $containerIsWalked = FALSE;
  $MemberContext = '';
  // / Never scan our own definitions file, which is full of signatures by design.
  if ($targetPath !== FALSE && $targetPath === $DefsRealPath) $fileIsScannable = FALSE;
  // / Never scan our own report file, which quotes a signature every time one matches.
  if ($targetPath !== FALSE && $targetPath === $ReportRealPath) $fileIsScannable = FALSE;
  if ($targetPath === FALSE or !is_file($file)) $fileIsScannable = FALSE;
  if ($fileIsScannable && !is_readable($file)) {
    $fileIsScannable = FALSE;
    processOutput('Cannot open a file for reading, it was skipped:  '.$displayFile, 'error', 610, TRUE, FALSE, FALSE); }
  if ($fileIsScannable) {
    processOutput('Scanning file:  '.$displayFile, 'log', 0, TRUE, FALSE, FALSE);
    $FileCount++;
    $fileSize = intval(@filesize($file));
    // / Read the opening bytes once, to identify an image or a container by its content.
    $fileHeader = readFileHeader($file, 512);
    $fileExtension = fileExtensionOf($file);
    $containerFormat = containerFormatOf($fileHeader);
    $imageFormat = imageFormatOf($fileHeader);
    $containerIsWalked = ($containerFormat !== '' && $InspectArchivedContent);
    // / Decide up front what this file needs, so the reader only searches what matters.
    // / A file whose extension already names a language needs no content search at all.
    if ($classifyActive) {
      $magicType = magicTypeFor($Classifiers, $fileHeader);
      $extensionLanguage = languageForExtension($Classifiers, $fileExtension);
      $candidateTypes = typesForExtension($Classifiers, $fileExtension);
      // / Remember what this file honestly claims to be, separately from what gets
      // / searched. A definition scope is a statement about the file & must never be
      // / satisfied by the fallback that searches an unknown file against every type.
      $scopeTypes = $candidateTypes;
      // / A magic signature outranks an extension, because the bytes cannot be renamed.
      if ($magicType !== '') {
        $classifyMode = 'handlers';
        $candidateTypes = array($magicType);
        $scopeTypes = array($magicType); }
      else {
        if ($extensionLanguage !== '') $classifyMode = 'language';
        else {
          if (count($candidateTypes) > 0) $classifyMode = 'handlers';
          else {
            // / An extension nothing claims is searched against every declared type.
            $classifyMode = 'unknown';
            $candidateTypes = array_keys($Classifiers['handlers']);
            // / An image is the exception. Compressed pixels hold every short token by chance.
            // / Code inside an image is reported by the embedded code signatures instead.
            if ($imageFormat !== '') $classifyMode = ''; } } } }
    // / Announce a file large enough to need more than a single read.
    if ($fileSize >= $MemoryLimit) processOutput('Chunking file:  '.$displayFile, 'log', 0, TRUE, FALSE, FALSE);
    $handle = @fopen($file, 'rb');
    // / A file that cannot be opened is skipped. It does not stop the rest of the scan.
    if ($handle === FALSE) {
      $fileIsScannable = FALSE;
      processOutput('Cannot open a file for reading, it was skipped:  '.$displayFile, 'error', 610, TRUE, FALSE, FALSE); }
    else {
      $md5Context = hash_init('md5');
      $sha1Context = hash_init('sha1');
      $sha256Context = hash_init('sha256');
      // / Read no more than one chunk at a time so a huge file cannot exhaust memory.
      $readSize = $ChunkSize;
      if ($fileSize > 0 && $fileSize < $readSize) $readSize = $fileSize;
      // / Carry enough of the previous chunk to catch a signature that straddles the join.
      $overlapSize = $DefsLongestSignature - 1;
      // / A handler match looks ahead past its token, so the tail must carry that too.
      if ($classifyMode !== '' && ($ClassifierLongestToken + 256) > $overlapSize) $overlapSize = $ClassifierLongestToken + 256;
      if ($imageFormat !== '' && $overlapSize < 64) $overlapSize = 64;
      if ($overlapSize < 0) $overlapSize = 0;
      if ($overlapSize > intdiv($readSize, 2)) $overlapSize = intdiv($readSize, 2);
      while ($fileSize > 0 && !feof($handle)) {
        redeclare($buffer, fread($handle, $readSize));
        if ($buffer === FALSE) {
          $readFailed = TRUE;
          break; }
        if ($buffer !== '') {
          hash_update($md5Context, $buffer);
          hash_update($sha1Context, $buffer);
          hash_update($sha256Context, $buffer);
          processOutput('Scanning chunk.', 'log', 0, FALSE, FALSE, FALSE);
          // / Scan the current chunk, prefixed by the tail of the chunk before it.
          redeclare($window, $overlap.$buffer);
          matchContentIndicators($file, $window, $Defs, $scopeTypes, $fileDetections);
          // / Code has no business inside an image, wherever in the file it sits.
          if ($InspectImages && $imageFormat !== '') matchEmbedSignatures($file, $window, FALSE, $fileDetections);
          // / Classify the same block while it is already in memory.
          if ($classifyMode === 'unknown') matchLanguageSignatures($window, $Classifiers, $languageHits);
          // / A container that will be walked reports its handlers by member instead.
          if (($classifyMode === 'handlers' or $classifyMode === 'unknown') && !$containerIsWalked) matchHandlerTokens($window, $Classifiers, $candidateTypes, $handlerHits);
          redeclare($overlap, substr($buffer, 0 - $overlapSize));
          if ($overlapSize < 1) redeclare($overlap, ''); } }
      fclose($handle);
      if ($readFailed) processOutput('Cannot read the whole of a file, results may be incomplete:  '.$displayFile, 'error', 600, TRUE, TRUE, FALSE);
      $md5Hash = hash_final($md5Context);
      $sha1Hash = hash_final($sha1Context);
      $sha256Hash = hash_final($sha256Context);
      // / Double check that the file we scanned is not a copy of the loaded definitions.
      if ($sha256Hash === $DefData) $fileDetections = array();
      else {
        // / Match the file name, which is a different question from its content.
        matchNameIndicators($file, $Defs, $fileDetections);
        matchHashSignatures($file, $md5Hash, $sha1Hash, $sha256Hash, $Defs, $fileDetections);
        $inspection = newInspectionState($candidateTypes);
        $structureHandle = ($fileSize > 0) ? @fopen($file, 'rb') : FALSE;
        if ($structureHandle !== FALSE) {
          if ($InspectImages) {
            if ($imageFormat === 'png') inspectPngStream($file, $structureHandle, $fileSize, $fileDetections);
            // / A file named as a png that is not one is dressed as something it is not.
            if (($fileExtension === 'png' or $fileExtension === 'apng') && $imageFormat !== 'png') recordDetection($file, 'Masquerade.PNG', 'Structure Match', 'the file is named as a png & does not begin with a png signature', 'medium', $fileDetections); }
          if ($InspectArchivedContent) {
            if ($containerFormat !== '') inspectContainerHandle($file, $structureHandle, $fileSize, $containerFormat, '', 0, $fileDetections, $handlerHits, $inspection);
            else {
              // / A zip is read from its end, so one can hide behind the header of another format.
              $hiddenDirectory = findZipDirectory($structureHandle, $fileSize);
              if ($hiddenDirectory['found'] && $hiddenDirectory['entries'] > 0) {
                if ($imageFormat !== '') recordDetection($file, 'Polyglot.Archive.Hidden', 'Structure Match', 'a zip archive of '.countWithNoun($hiddenDirectory['entries'], 'entry', 'entries').' sits behind a '.$imageFormat.' header', 'medium', $fileDetections);
                inspectContainerHandle($file, $structureHandle, $fileSize, 'zip', 'appended archive', 0, $fileDetections, $handlerHits, $inspection); } } }
          fclose($structureHandle); }
        $MemberContext = '';
        // / Say plainly when part of a file could not be looked at, & why.
        if (count($inspection['incomplete']) > 0) {
          $IncompleteCount++;
          processOutput('Incomplete: '.$displayFile.' ('.implode('; ', $inspection['incomplete']).')', 'log', 0, TRUE, TRUE, FALSE); }
        // / Count the file once, under the strongest verdict any of its matches carried.
        if (count($fileDetections) > 0) {
          if (in_array('high', $fileDetections, TRUE)) $Infected++;
          else $Suspicious++; }
        // / An extension that names a language settles the question by itself.
        if ($classifyMode === 'language') $contentLanguage = $extensionLanguage;
        // / An unclaimed extension is decided by how much of a language it carries.
        if ($classifyMode === 'unknown') $contentLanguage = resolveContentLanguage($languageHits, $Classifiers);
        // / Executable code is reported as code & is never classified for handlers.
        if ($contentLanguage !== '') {
          $CodeFileCount++;
          processOutput('Classified: '.$displayFile.' (File may contain executable code - Detected Language: '.$contentLanguage.')', 'log', 0, TRUE, TRUE, FALSE); }
        else {
          if (count($handlerHits) > 0) {
            $HandlerFileCount++;
            processOutput('Classified: '.$displayFile.' (File may contain file or URL handlers - '.describeHandlerHits($handlerHits).')', 'log', 0, TRUE, TRUE, FALSE); } } }
      $CheckComplete = TRUE; } }
  // / A file we deliberately skipped is not a failure, so the check still reports complete.
  if (!$fileIsScannable) $CheckComplete = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($buffer, $overlap, $window, $handle, $structureHandle, $md5Context, $sha1Context, $sha256Context, $md5Hash, $sha1Hash, $sha256Hash, $fileDetections, $targetPath, $displayFile, $fileSize, $readSize, $overlapSize, $readFailed, $fileIsScannable, $file, $DefData, $handlerHits, $languageHits, $candidateTypes, $fileExtension, $extensionLanguage, $contentLanguage, $classifyMode, $magicType, $fileHeader, $containerFormat, $imageFormat, $scopeTypes, $inspection, $hiddenDirectory, $classifyActive, $containerIsWalked);
  return array($CheckComplete, $Infected, $FileCount, $Suspicious); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to hunt files & folders recursively for scannable items.
// / A depth limit & a symbolic link rule together stop a linked folder looping forever.
function fileScan($folder, $Defs, $DefData, $MemoryLimit, $ChunkSize, $Recursion, $DefsLongestSignature, $currentDepth) {
  global $SEP, $FileCount, $DirCount, $Infected, $Suspicious, $MaxScanDepth, $FollowSymlinks;
  // / Set variables.
  $ScanComplete = FALSE;
  $files = array();
  $entry = '';
  $scanResult = FALSE;
  $checkComplete = $childComplete = FALSE;
  $entryIsLink = FALSE;
  if (!file_exists($folder)) processOutput('The requested scan path no longer exists:  '.$folder, 'error', 300, TRUE, TRUE, FALSE);
  else {
    // / Walk a folder, or hand a single file straight to the checker.
    if (is_dir($folder)) {
      $DirCount++;
      $ScanComplete = TRUE;
      processOutput('Scanning folder:  '.$folder, 'log', 0, TRUE, FALSE, FALSE);
      $scanResult = @scandir($folder);
      // / A folder we are not allowed to read is a warning, not a reason to stop scanning.
      if (!is_array($scanResult)) processOutput('Cannot read the contents of a folder, it was skipped:  '.$folder, 'warning', 0, TRUE, FALSE, FALSE);
      else {
        $files = $scanResult;
        foreach ($files as $file) {
          if ($file === '' or $file === '.' or $file === '..') continue;
          redeclare($entry, str_replace($SEP.$SEP, $SEP, $folder.$SEP.$file));
          redeclare($entryIsLink, is_link($entry));
          // / Skip a symbolic link unless the administrator asked for links to be followed.
          if ($entryIsLink && !$FollowSymlinks) processOutput('Skipping a symbolic link:  '.$entry, 'log', 0, FALSE, FALSE, FALSE);
          else {
            if (!is_dir($entry)) {
              list($checkComplete, $Infected, $FileCount, $Suspicious) = virusCheck($entry, $Defs, $DefData, $MemoryLimit, $ChunkSize, $DefsLongestSignature);
              if (!$checkComplete) $ScanComplete = FALSE; }
            else {
              if ($Recursion) {
                // / Refuse to descend any further once the configured depth has been reached.
                if ($currentDepth >= $MaxScanDepth) processOutput('Reached the maximum scan depth, this folder was not opened:  '.$entry, 'warning', 0, TRUE, FALSE, FALSE);
                else {
                  list($childComplete, $DirCount, $FileCount, $Infected, $Suspicious) = fileScan($entry, $Defs, $DefData, $MemoryLimit, $ChunkSize, $Recursion, $DefsLongestSignature, $currentDepth + 1);
                  if (!$childComplete) $ScanComplete = FALSE; } } } } } } }
    else {
      list($checkComplete, $Infected, $FileCount, $Suspicious) = virusCheck($folder, $Defs, $DefData, $MemoryLimit, $ChunkSize, $DefsLongestSignature);
      $ScanComplete = $checkComplete; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($files, $file, $entry, $scanResult, $checkComplete, $childComplete, $entryIsLink, $folder, $DefData, $currentDepth);
  return array($ScanComplete, $DirCount, $FileCount, $Infected, $Suspicious); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The main logic of the program.

// / Verify the installation.
list($InstallationVerified, $Version, $VerificationFault) = verifyInstallation();
if (!$InstallationVerified) {
  echo 'ERROR!!! ScanCore-1, Cannot verify the ScanCore installation!  '.$VerificationFault.PHP_EOL;
  exit(1); }

// / Load the configuration file.
list ($ConfigLoaded, $DefsExist, $ConfigFilePath, $VersionsMatch) = loadConfig($Version, isset($argv) ? $argv : array());
if (!$ConfigLoaded) {
  echo 'ERROR!!! ScanCore-2, Cannot load the configuration file located at:  '.$ConfigFilePath.$EOL;
  exit(1); }
if (!$VersionsMatch) {
  echo 'ERROR!!! ScanCore-4, Cannot verify the configuration file version located at:  '.$ConfigFilePath.$EOL;
  exit(1); }
if (!$DefsExist) {
  echo 'ERROR!!! ScanCore-3, Cannot verify the definitions file located at:  '.$DefsFile.$EOL;
  exit(1); }

// / Work out who should own new files before the first one gets created. Silently,
// / because there is nowhere to log it until the report directory exists.
resolveOwnership(FALSE);

// / Create required directories if they don't already exist.
list($RequiredDirsExist) = createDirs($RequiredDirs);
if (!$RequiredDirsExist) {
  echo 'ERROR!!! ScanCore-5, Cannot create required directories!'.$EOL;
  exit(1); }

// / Report anything the configuration file got wrong, now that the report file exists.
foreach ($ConfigFaults as $ConfigFault) processOutput($ConfigFault, 'warning', 0, TRUE, FALSE, FALSE);

// / Process supplied command-line arguments.
// / Example:  C:\Path-To-PHP-Binary.exe C:\Path-To-ScanCore.php C:\Path-To-Scan\ -m [integer] -c [integer] -v -d
list($ArgsParsed, $PerformScan, $PathToScan, $MemoryLimit, $ChunkSize, $Debug, $Verbose, $Recursion, $ReportFile, $MaxLogSize, $PerformDefUpdate, $PerformAppUpdate) = parseArgs($argv);
if (!$ArgsParsed) processOutput('Cannot verify supplied arguments!', 'error', 6, TRUE, TRUE, TRUE);
else processOutput('Verified supplied arguments.', 'log', 0, TRUE, FALSE, FALSE);

// / Resolve ownership again, now the command line overrides are known, & say what it is.
resolveOwnership(TRUE);

// / Give the interpreter the memory budget the administrator asked us to work within.
// / A file smaller than this budget is still read in chunks, so this is a ceiling only.
if (!@ini_set('memory_limit', strval($MemoryLimit))) processOutput('Cannot apply the configured MemoryLimit to the interpreter.', 'warning', 0, TRUE, FALSE, FALSE);

// / Perform a definition update, when required.
if ($PerformDefUpdate) {
  list($UpdateDefinitionsComplete, $UpdateDefinitionsErrors) = updateDefinitions();
  if (!$UpdateDefinitionsComplete) processOutput('Cannot install definition update!', 'error', 7, TRUE, TRUE, TRUE);
  else processOutput('Installed definition update.', 'log', 0, TRUE, TRUE, FALSE); }

// / Perform an application update, when required.
if ($PerformAppUpdate) {
  list($UpdateApplicationComplete, $UpdateApplicationErrors) = updateApplication();
  if (!$UpdateApplicationComplete) processOutput('Cannot install application update!', 'error', 8, TRUE, TRUE, TRUE);
  else processOutput('Installed application update. Please open '.$DefaultConfigFile.' & validate configuration entries.', 'log', 0, TRUE, TRUE, FALSE); }

// / Perform scanning operations, when required.
if ($PerformScan) {
  // / Load the virus definitions into memory & hash the file to avoid self-detection.
  list($DefsLoaded, $Defs, $DefData, $DefsLongestSignature) = loadDefs($DefsFile);
  if (!$DefsLoaded) processOutput('Cannot load definitions!', 'error', 9, TRUE, TRUE, TRUE);
  else processOutput('Loaded definitions.', 'log', 0, TRUE, FALSE, FALSE);

  // / Load the classifier definitions, which describe handlers rather than threats.
  // / Classification is optional, so a missing file disables it rather than stopping.
  $ClassifiersLoaded = FALSE;
  $Classifiers = array('types' => array(), 'languages' => array(), 'handlers' => array(), 'magic' => array(), 'embeds' => array());
  $ClassifierLongestToken = 0;
  // / The same file carries the embedded code signatures that image inspection uses.
  if ($ClassifyContent or $InspectImages) list($ClassifiersLoaded, $Classifiers, $ClassifierLongestToken) = loadClassifiers($ClassifierFile);
  if ($ClassifyContent && !$ClassifiersLoaded) processOutput('Content classification is disabled because no classifier definitions could be loaded.', 'warning', 0, TRUE, FALSE, FALSE);

  // / Resolve the files the scanner must never inspect, so the walker can recognize them.
  $DefsRealPath = realpath($DefsFile);
  $ReportRealPath = realpath($ReportFile);

  // / Start the scanner!
  list($ScanComplete, $DirCount, $FileCount, $Infected, $Suspicious) = fileScan($PathToScan, $Defs, $DefData, $MemoryLimit, $ChunkSize, $Recursion, $DefsLongestSignature, 0);
  if (!$ScanComplete) processOutput('Cannot not complete requested scan!', 'error', 10, TRUE, TRUE, TRUE);
  else {
    // / One finding per line, & each line is one complete sentence.
    // / The order runs from what was looked at, through what was merely noticed, to the
    // / verdict. The infected count is last because it is the answer, & an answer that
    // / opens the summary is read before the reader knows what was scanned.
    processOutput('Scanned '.countWithNoun($FileCount, 'file', 'files').' in '.countWithNoun($DirCount, 'folder', 'folders').'.', 'log', 0, TRUE, TRUE, FALSE);
    if ($ClassifyContent && $ClassifiersLoaded) {
      processOutput('Found '.countWithNoun($HandlerFileCount, 'file', 'files').' that may contain file or URL handlers.', 'log', 0, TRUE, TRUE, FALSE);
      processOutput('Found '.countWithNoun($CodeFileCount, 'file', 'files').' that may contain executable code.', 'log', 0, TRUE, TRUE, FALSE); }
    processOutput('Found '.countWithNoun($IncompleteCount, 'file', 'files').' that could not be fully inspected.', 'log', 0, TRUE, TRUE, FALSE);
    if ($ReportSuspicious) processOutput('Found '.countWithNoun($Suspicious, 'suspicious file', 'suspicious files').'.', 'log', 0, TRUE, TRUE, FALSE);
    processOutput('Found '.countWithNoun($Infected, 'infected file', 'infected files').'.', 'log', 0, TRUE, TRUE, FALSE); } }
// / -----------------------------------------------------------------------------------
