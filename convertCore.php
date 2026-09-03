<?php if (php_sapi_name() !== 'cli') print('<!DOCTYPE HTML>'.PHP_EOL);
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRConvert2, Copyright on 8/21/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / Application Information ...
// / This application is designed to provide a web-interface for converting file formats
// / on a server for users of any web browser without authentication.
// /
// / Fileinformation ...
// / v3.8.7.
// / HRConvert2 Convert Core.
// / This file contains the core logic of the application.
// /
// / Hardware Requirements ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / Dependency Requirements ...
// / This application requires Debian Linux, Apache 2.4, PHP 8+, FFMPEG, Dia, LibreOffice, 
// / Mkisofs, 7zip, Unoconv, libgxps-utils, Tesseract, Unzip, OpenSCAD, Rar, Inkscape, Calibre,
// / Unrar, ClamAV, MeshLab, PopplerUtils, PDFTOTEXT, ImageMagick, bwrap Dia & xvfb-run.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to reset PHP's time limit for execution.
function setTimeLimit() {
  $TimeReset = set_time_limit(0);
  return $TimeReset; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to set the date & time for the session.
function verifyTime() {
  // / Set variables.
  global $TimeIsSet, $Date, $Time, $EpochTime, $EnableMemoryProtection;
  $TimeIsSet = FALSE;
  $tzAbbreviations = DateTimeZone::listAbbreviations();
  $tzList = array();
  // / Build a list of timezones supported by this PHP installation.
  foreach ($tzAbbreviations as $zone) foreach ($zone as $item) if (is_string($item['timezone_id']) && $item['timezone_id'] !== '') $tzList[] = $item['timezone_id'];
  $tzList = array_unique($tzList);
  $zoneList = array_values($tzList);
  // / Check that the currently set timezone is valid.
  if (in_array(@date_default_timezone_get(), $zoneList)) $TimeIsSet = TRUE;
  // / Try to set the time regardless of whether or not the timezone is correct.
  $EpochTime = time();
  $Date = date("m_d_y");
  $Time = date("F j, Y, g:i a");
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $tzAbbreviations, $tzList, $zoneList, $zone, $item);
  return array($TimeIsSet, $Date, $Time, $EpochTime); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to close the web server connection.
function closeHRC2Connection() {
  ignore_user_abort(TRUE);
  if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
  else {
    if (ob_get_level() > 0) ob_end_flush();
    flush(); } }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to sanitize input strings with varying degrees of tolerance.
// / Filters a given string of | \ ~ # [ ] ( ) { } ; : $ ! # ^ & % @ > * & < " / ' ` chr(9) chr(10) chr(13) chr(0)
// / This function will replace any of the above specified charcters with NOTHING. No character at all. An empty string.
// / This function will replace whitespace with the underscore character.
// / This function will remove leading and traling dashes.
// / Set $strict to TRUE to also filter out backslash characters as well. Example:  /
function sanitizeString($Variable, $strict) {
  // / Set variables.
  global $EnableMemoryProtection;
  // / Note that this function does not use the global $DangerousFiles. 
  // / Instead this function defines & destroys it's own array every time it is called.
  $dangerFiles = array('.js', '.php', '.html', '.css', '.phar', '..', 'index.php', 'index.html', '--');
  // / Check for dangerous files or escape conditions.
  foreach ($dangerFiles as $danFile) $Variable = str_replace($danFile, '', $Variable);
  if ($strict) $Variable = trim(trim(str_replace(' ', '_', str_replace('..', '', str_replace('//', '', str_replace(str_split(',|\\~#[](){};:$!#^&%@>*?<"\'/`'.chr(9).chr(10).chr(13).chr(0)), '', str_replace('http://', '', str_replace('https://', '', $Variable))))))), '-');
  if (!$strict) $Variable = trim(trim(str_replace(' ', '_', str_replace('..', '', str_replace('//', '', str_replace(str_split(',|\\~#[](){};:$!#^&%@>*?<"\'`'.chr(9).chr(10).chr(13).chr(0)), '', str_replace('http://', '', str_replace('https://', '', $Variable))))))), '-');
  // / Check for dangerous files or escape conditions one more time.
  foreach ($dangerFiles as $danFile) $Variable = str_replace($danFile, '', $Variable);
  // / Trim the variable one last time to avoid any crafted leading dashes or directory separators.
  $Variable = trim(trim(trim(trim($Variable, '-'), '.'), '-'), '.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $strict, $dangerFiles, $danFile);
  return $Variable; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to sanitize input strings or arrays with varying degrees of tolerance.
// / Filters a given string of | \ ~ # [ ] ( ) { } ; : $ ! # ^ & % @ > * < " / ' ` chr(9) chr(10) chr(13) chr(0)
// / This function will replace any of the above specified charcters with NOTHING. No character at all. An empty string.
// / This function will replace whitespace with the underscore character.
// / Set $strict to TRUE to also filter out backslash characters as well. Example:  /
function sanitize($Variable, $strict) {
  // / Set variables.
  global $EnableMemoryProtection;
  $VariableIsSanitized = FALSE;
  $var = '';
  $key = 0;
  if (!is_bool($strict)) $strict = TRUE;
  // / Only continue if the input variable is a type that we can properly sanitize.
  if (is_string($Variable) or is_numeric($Variable) or is_array($Variable)) {
    // / Sanitize array inputs.
    // / Note that when $strict is TRUE this also filters out backslashes.
    if (is_array($Variable)) foreach ($Variable as $key => $var) $Variable[$key] = sanitizeString($Variable[$key], $strict);
    // / Sanitize string & numeric inputs.
    // / Note that when $strict is TRUE this also filters out backslashes.
    if (is_string($Variable) or is_numeric($Variable)) $Variable = sanitizeString($Variable, $strict);
    // / Only set the Sanitized flag to TRUE if we have taken action on this variable.
    $VariableIsSanitized = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $strict, $key, $var);
  return array($Variable, $VariableIsSanitized); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to produce random number variables for truly unique per-session identifiers.
// / This function uses the built-in PHP "random_int" function to generate the random number.
// / This function does not generate it's own number. It produces a number from random_int().
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
  $requiredConfigVars = $configVersionParts = $requiredVersionParts = array();
  $requiredConfigVar = $detectedConfigVersion = $cleanConfigVersion = $cleanRequiredVersion = '';
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
    'Font', 'SpinnerStyle', 'SpinnerColor', 'MinimumCalibreVersion', 'UserEbookInputArray',
    'ShowGUI', 'ShowFinePrint', 'TOSURL', 'PPURL', 'AllowUserShare',
    'SupportedConversionTypes', 'RetryCount', 'DocumentEngineSleepTimer',
    'UsePatchedDocumentEngine', 'RARArchiveMethod', 'AllowBootableIsoImage',
    'DeleteBuildEnvironment', 'DeleteDevelopmentDocumentation', 'UserEbookOutputArray',
    'UserArchiveArray', 'UserDearchiveArray', 'UserDocumentArray', 'UserSpreadsheetArray',
    'UserPresentationInputArray', 'UserPresentationOutputArray',
    'UserXPSInputArray', 'UserXPSOutputArray', 'UserImageArray',
    'UserMediaInputArray', 'UserMediaOutputArray', 'UserBootableIsoArray',
    'UserVideoInputArray', 'UserVideoOutputArray', 'UserStreamArray',
    'UserDrawingArray', 'UserSVGInputArray', 'UserSVGOutputArray', 'UserModelArray', 
    'UserSCADArray', 'UserSubtitleInputArray', 'UserSubtitleOutputArray', 'UserPDFWorkArr',
    'AllowStreamOverHTTP', 'StreamWatchTimeout', 'StreamConnectionTimeout', 'MinimumIsoHybridVersion',
    'StreamInspectionLayers', 'StreamInspectionFilesPerLayer', 'MinimumPdftotextVersion',
    'DefaultStreamInspectionForfeitAction', 'MaxStreamInspectionFileSize', 'RequireSandboxOnDocker',
    'AllowSCADIncludeResolution', 'SCADConversionTimeout', 'RequireSandbox', 'ThrowSandboxWarning',
    'MinimumSCADVersion', 'MinimumFFMPEGVersion', 'MinimumStreamFFMPEGVersion', 'MinimumDiaVersion',
    'MinimumLibreOfficeVersion', 'MinimumInkscapeVersion', 'MinimumImageVersion', 'MinimumTesseractVersion',
    'MinimumAssimpVersion', 'MinimumMeshlabVersion', 'UsePyMeshLab', 'EnableAutoUpdates',
    'AutoUpdateTargetVersion', 'UpdateSourceRepository', 'MaxUpdatePackageSize', 'UpdateConnectionTimeout',
    'Minimum7zVersion', 'MinimumZipVersion', 'MinimumRarVersion', 'MinimumTarVersion', 'MinimumMkisofsVersion',
    'EnableResourceAwareness', 'RequireResourceAwareness', 'CoreManagerSubprocessPollInterval',
    'ResourcePollInterval', 'WorkerReapInterval', 'WorkerStaleGracePeriod', 'TotalResourceBudget',
    'ReserveResourcePercentage', 'MaxConcurrentWorkers', 'MaxExpectedRuntime', 'MaxRuntimeExtensions',
    'DefaultConversionCost', 'DefaultExpectedRuntime', 'MaintainHTAccess');
  // / Check that every required setting actually exists in the global scope.
  // / config.php is required at global scope, so every setting it defines lands in $GLOBALS.
  // / isset() is deliberately not used because a setting legitimately set to NULL still exists.
  foreach ($requiredConfigVars as $requiredConfigVar) {
    if (!array_key_exists($requiredConfigVar, $GLOBALS)) array_push($MissingConfigVars, $requiredConfigVar); }
  if (!empty($MissingConfigVars)) $ConfigIsValid = FALSE;
  // / Compare the config version stamp against the version this core was written for.
  // / This is advisory. It explains why settings are missing but never passes a config that is incomplete.
  $detectedConfigVersion = array_key_exists('ConfigVersion', $GLOBALS) ? $ConfigVersion : '';
  if ($detectedConfigVersion !== '') {
    $cleanConfigVersion = ltrim($detectedConfigVersion, 'vV');
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
  else $detectedConfigVersion = 'NONE';
  // / Sanity check that the config.php file is valid.
  // / $ConfigIsValid should already be false by now, but just in case.
  if (!$configVersionIsCurrent && !$ConfigIsValid) $ConfigIsValid = FALSE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $requiredConfigVars, $requiredConfigVar, $configVersionParts, $requiredVersionParts, $configVersionIsCurrent, $cleanConfigVersion, $cleanRequiredVersion, $RequiredConfigVersion);
  return array($ConfigIsValid, $MissingConfigVars, $detectedConfigVersion); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
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
// / A function to give a variable a second value without leaving the first one behind.
// / Call this as redeclare($targetVariable, $newValue) & do not assign the result back.
// / The target is taken by reference & is modified in place.
// / A form such as $target = redeclare($target, $new) would overwrite the target with a boolean.
// / The target is shredded before the new value is assigned, never after & never instead.
// / Returns TRUE when the old value was destroyed before the new one landed, per convention 8.
// / A FALSE return means the new value is in place but the old one may not be gone.
// / This is a deliberate exception to convention 6 & is not a licence to ignore it.
// / Convention 6 buys the shredding & the ability to read one name as holding one value.
// / This function preserves the first of those & gives up the second.
// / A loop body is the honest case for it, because a distinct name per value is impossible there.
// / Straight line code is not, because a second variable name costs nothing & documents itself.
// / An interned string cannot be shredded, so a value that came from a literal survives regardless.
// / A value built at runtime is not interned & shreds correctly.
// / A copy held by another variable cannot be reached, because PHP separates the buffer on write.
// / Each call costs one debug_backtrace() through the purge routine.
function redeclare(&$targetVariable, $newValue) {
  // / Set variables.
  global $EnableMemoryProtection;
  $VariableIsRedeclared = FALSE;
  $oldValueWasPurged = FALSE;
  // / Shred whatever the target holds before anything is allowed to overwrite it.
  // / The target is a reference to the caller's register, so this writes into the real buffer.
  $oldValueWasPurged = purgeSensitiveMemory($EnableMemoryProtection, $targetVariable);
  // / Assign the new value only once the old one has been dealt with.
  $targetVariable = $newValue;
  if ($oldValueWasPurged) $VariableIsRedeclared = TRUE;
  // / $newValue is a copy & the caller still holds the original, so shredding it achieves nothing.
  // / $targetVariable is not purged, because it is the value this function was asked to set.
  $oldValueWasPurged = NULL;
  unset($oldValueWasPurged);
  return $VariableIsRedeclared; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to determine what function called the function that called this function.
// / This function uses naieve memory cleanup routine deliberately out of neccesity.
// / Only a human brain could have written that last comment. Just sit with it. I promise it makes sense.
// / Accepts no arguments & returns the name of the first caller that is not a memory routine.
// / Frames belonging to the memory routines are skipped deliberately.
// / A caller that reached the purge through redeclare() still owns the data & is still the culprit.
// / Naming the wrapper would recreate the mystery this function exists to prevent.
// / Returns 'main' when the call came from main or from no function at all.
function getChildFunction() {
  // / Set variables.
  // / A depth of eight is generous, because the memory routines never stack three frames deep.
  $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 8);
  $memoryFunctions = array('getChildFunction', 'purgeSensitiveMemory', 'redeclare');
  $DirectCallingFunctionName = 'main';
  $traceKey = 1;
  // / Walk up the stack until a frame outside the memory routines is found.
  while (isset($trace[$traceKey]['function'])) {
    if (!in_array($trace[$traceKey]['function'], $memoryFunctions, TRUE)) {
      $DirectCallingFunctionName = $trace[$traceKey]['function'];
      break; }
    $traceKey++; }
  // / This cleanup is manual, because purgeSensitiveMemory() calls this function.
  $trace = $memoryFunctions = $traceKey = NULL;
  unset($trace, $memoryFunctions, $traceKey);
  return $DirectCallingFunctionName; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to destroy variables from the heap to reduce the risk of memory leakage.
// / String buffers are overwritten with null bytes before the reference is broken.
// / Call this as purgeSensitiveMemory($FailureIsFatal, $variableOne, $variableTwo).
// / Every target must be its own argument, because the argument list is taken by reference.
// / Collecting targets into an array copies their values & shreds only the copies.
// / The first argument decides the cost of a failure & nothing else decides it.
// / TRUE prints error 40000 to the page, closes the connection & halts.
// / FALSE records a warning & continues.
// / Late running callers pass $EnableMemoryProtection & early running callers pass a literal.
// / The reporting channel is decided by whether $LogFile is usable, not by the first argument.
// / config.php loads before verifyLogs() runs, so an available config does not mean available logging.
// / A fatal failure always reaches the page, whether or not it reached the logfile.
// / A non-fatal failure never reaches the page, because the user is still going to be served.
// / Returns TRUE when every target was destroyed, per convention 8.
// / This function does not purge its own variables, because that would loop forever.
// / This function does not free memory & a destroyed value still occupies its allocation.
// / PHP separates a shared string on first write, so a second variable holding the same value keeps it.
// / resolveSecretFile() relies on that behaviour to shred a key it is returning.
// / Check the log for the named function whenever error 40000 or a purge warning appears.
function purgeSensitiveMemory($FailureIsFatal, &...$variables) {
  // / Set variables.
  global $EnableMemoryProtection, $LogFile, $Verbose;
  // / These two survive recursion so an inner call still names the function that owns the data.
  // / Neither ever holds sensitive data & both are released when the outermost call unwinds.
  static $callerName = '';
  static $recursionDepth = 0;
  $MemoryIsPurged = TRUE;
  $variableIsDestroyed = $loggingIsReady = FALSE;
  $mpStatus = 'Disabled';
  $mpNote = $failureText = '';
  $length = $i = 0;
  // / Resolve the responsible function once, on the outermost call only.
  if ($recursionDepth === 0) $callerName = getChildFunction();
  $recursionDepth++;
  // / Report the behaviour this call was given, not the global it may not agree with.
  if ($FailureIsFatal) $mpStatus = 'Enabled';
  // / Say so when the supplied behaviour disagrees with config.php, so the log names the decider.
  if (isset($EnableMemoryProtection) && (bool)$EnableMemoryProtection !== (bool)$FailureIsFatal) $mpNote = ' This behaviour was supplied by the caller & does not match config.php.';
  // / Decide which reporting channel exists yet, because installation code runs before verifyLogs().
  if (isset($LogFile) && is_string($LogFile) && $LogFile !== '') $loggingIsReady = TRUE;
  // / Loop through every variable that was submitted for destruction.
  foreach ($variables as &$variable) {
    // / Reset the flag at the start of every iteration of the loop.
    $variableIsDestroyed = FALSE;
    // / A variable that already holds nothing has nothing to shred & is not a failure.
    if (is_null($variable)) $variableIsDestroyed = TRUE;
    // / Shred multi-dimensional arrays recursively using reference keys.
    if (is_array($variable) && !empty($variable)) {
      // / A failure at any depth invalidates the whole operation, so the result is carried up.
      foreach ($variable as $key => &$subValue) { if (!purgeSensitiveMemory($FailureIsFatal, $subValue)) $MemoryIsPurged = FALSE; }
      // / Clear the loop reference safely.
      unset($subValue);
      $variable = NULL;
      $variableIsDestroyed = TRUE; }
    // / Explicitly handle empty arrays.
    if (is_array($variable) && empty($variable)) {
      $variable = NULL;
      $variableIsDestroyed = TRUE; }
    // / Standard boolean resolution tracking.
    if (is_bool($variable)) {
      $variable = NULL;
      $variableIsDestroyed = TRUE; }
    // / Convert numeric variables to character buffers.
    if (is_numeric($variable)) $variable = (string)$variable;
    // / Physically overwrite strings with null bytes.
    if (is_string($variable)) {
      $length = strlen($variable);
      for ($i = 0; $i < $length; $i++) $variable[$i] = "\0";
      $variable = NULL;
      $variableIsDestroyed = TRUE; }
    // / An object or a resource has internal buffers this function cannot write into.
    // / Drop the reference. This is a permanent property of the type rather than an event,
    // / so it is recorded as normal activity & not as a warning.
    if (!$variableIsDestroyed) {
      if ($loggingIsReady && $Verbose) logEntry('Dropped unshreddable reference type '.gettype($variable).' for the '.$callerName.' function.');
      $variable = NULL;
      if (is_null($variable)) $variableIsDestroyed = TRUE; }
    // / Halt or alert only once the fallback above has also failed to release the variable.
    // / This branch maintains the integrity of the fallback contract.
    // / It should never be reachable, because assigning NULL cannot fail.
    if (!$variableIsDestroyed) {
      $MemoryIsPurged = FALSE;
      $failureText = 'Cannot purge sensitive memory for the '.$callerName.' function! Memory protection is '.$mpStatus.'.'.$mpNote;
      // / Record a non-fatal failure & continue without printing anything to the page.
      // / The error log is the only channel available before verifyLogs() has run.
      if (!$FailureIsFatal) {
        if ($loggingIsReady) warningEntry($failureText.' Continuing...');
        else error_log('WARNING!!! HRConvert2: '.$failureText.' Continuing...'); }
      // / Log a fatal failure where it can be logged, then always report it to the page.
      // / errorEntry is given a FALSE die flag so control returns here for the print & the close.
      // / Letting errorEntry die would drop the connection & would skip the print entirely.
      // / errorEntry records it wherever it can & hands the halt to quickDie, so this
      // / path no longer has to know whether a logfile exists.
      if ($FailureIsFatal) errorEntry($failureText.' Execution cannot continue!', 40000, TRUE); } }
  // / This function does not purge its own variables, because that would loop forever.
  // / It is deliberately careful about what memory it consumes instead.
  unset($variable, $subValue, $key, $mpStatus, $mpNote, $failureText, $variableIsDestroyed, $loggingIsReady, $i, $length);
  // / Release the recursion state once the outermost call has finished with it.
  $recursionDepth--;
  if ($recursionDepth < 1) {
    $recursionDepth = 0;
    $callerName = ''; }
  return $MemoryIsPurged; }
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
// / A function to load an install secret from a file, or to create one when none exists.
// / Accepts the absolute path of the secret file.
// / Returns a readiness boolean & the secret key, in that order.
// / A failure returns FALSE & an empty string, which the caller must treat as a failed installation.
// / The file is written with only the secret & is checked byte for byte afterwards.
// / Appending to an existing file would produce a file that loads & carries a key nobody generated.
// / A file that fails that check is deleted.
// / The file is chmod 0600 on every path, because a secret another account can read is not a secret.
// / This is called for the install wide secret & for a per user secret in a home directory.
// / The logic is identical & only the supplied path differs, so it lives here rather than twice.
// / $SecretKey is capitalized against convention 3 because the secret file defines it under that name.
// / It is a function local here regardless of its case & is shredded before this function returns.
function resolveSecretFile($secretFile, $requiredSecretVersion) {
  // / Set variables.
  global $LogFile, $RunningAsRoot, $ApacheUser, $CurrentUser, $EnableMemoryProtection, $CoreLoaded;
  $SecretIsReady = FALSE;
  $ResolvedSecretKey = $secret = $secretFileContent = '';
  $detectedSecretVersion = $cleanRequiredVersion = $rotationNotice = $strayOutput = '';
  $secretGenerated = $existingIsUsable = $loggingIsReady = $payloadIsWellFormed = $secretIsReadable = FALSE;
  $bytesWritten = 0;
  // / Both are only ever defined by the require below.
  // / They are initialized here so a path that never reaches the require leaves neither undefined.
  $SecretKey = '';
  $SecretVersion = '';
  $cleanRequiredVersion = ltrim(trim((string)$requiredSecretVersion), 'vV');
  if (isset($LogFile) && is_string($LogFile) && $LogFile !== '') $loggingIsReady = TRUE;
  // / Load an existing secret file & decide whether this core may still use it.
  if (file_exists($secretFile)) {
    @chmod($secretFile, 0600);
    // / A file this process cannot read must NEVER reach require. PHP reports that as a
    // / raw warning, which reaches the page & names an absolute path outside the web root.
    // / This happens when a root command line run created the file, because it is then
    // / owned by root & the chmod above fails silently for every other account.
    $secretIsReadable = is_readable($secretFile);
    if (!$secretIsReadable) {
      $rotationNotice = 'The secret file at '.$secretFile.' exists but cannot be read by '.$CurrentUser.'. It was almost certainly created by a different account. Run the -fp argument as root to correct its ownership.';
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
  // / Remove a secret file this core may not use, so a replacement can take its place.
  // / A file carrying no version predates version pinning & is always replaced.
  if (file_exists($secretFile) && !$existingIsUsable) {
    if ($rotationNotice === '') {
      $rotationNotice = 'The secret file at '.$secretFile.' reports version '.($detectedSecretVersion === '' ? 'none' : 'v'.$detectedSecretVersion).' & this core requires v'.$cleanRequiredVersion.'. It is being replaced & every session derived from it is now invalid.';
      if ($loggingIsReady) warningEntry($rotationNotice);
      else error_log('WARNING!!! HRConvert2: '.$rotationNotice); }
    @unlink($secretFile);
    // / A file that survives the unlink belongs to another account. Saying so once is far
    // / more useful than failing the same way on every request with no explanation.
    if (file_exists($secretFile)) {
      $rotationNotice = 'The secret file at '.$secretFile.' could not be removed by '.$CurrentUser.'. Correct its ownership with the -fp argument as root, or delete it by hand.';
      if ($loggingIsReady) errorEntry($rotationNotice, 30, FALSE);
      else error_log('ERROR!!! HRConvert2-30: '.$rotationNotice); }
    // / Discard the key that was loaded from the file being replaced.
    $SecretKey = ''; }
  // / Create a secret file whenever a usable one is not already in place.
  if (!$existingIsUsable && !file_exists($secretFile)) {
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
        $bytesWritten = file_put_contents($secretFile, $secretFileContent, LOCK_EX);
        // / Check that the secret, & only the secret, was written to the file.
        if ($bytesWritten !== strlen($secretFileContent)) {
          if (file_exists($secretFile)) @unlink($secretFile); }
        else {
          @chmod($secretFile, 0600);
          // / A secret created by a root command line run would otherwise be owned by root,
          // / & the web server user could never read it again. Hand it over immediately.
          if ($RunningAsRoot) {
            @chown($secretFile, $ApacheUser);
            @chgrp($secretFile, $ApacheUser);
            @chmod($secretFile, 0600); }
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
  if (!purgeSensitiveMemory($EnableMemoryProtection, $SecretKey, $SecretVersion, $secret, $secretFile, $secretFileContent, $bytesWritten, $secretGenerated, $existingIsUsable, $detectedSecretVersion, $cleanRequiredVersion, $rotationNotice, $strayOutput, $loggingIsReady, $payloadIsWellFormed, $secretIsReadable, $requiredSecretVersion)) {
    // / The failure is already logged under this function's name & is not logged again here.
    // / A secret that loaded correctly is still correct, so readiness is not withdrawn.
    $SecretIsReady = $SecretIsReady; }
  return array($SecretIsReady, $ResolvedSecretKey); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify that this installation is complete, current & usable.
// / Returns a verification boolean, the path of the config file & the version reported by
// / versionInfo.php, in that order.
// / Every failure other than the secret is fatal & dies here, because nothing downstream
// / can operate without a config file or against a mismatched core.
// / The secret file is only touched by an authorized combination of user & context.
// / root from the command line, or the web server user from a web request, gets the install
// / wide secret. Any other command line user gets a secret of their own in their home
// / directory, which lets them run diagnostics without ever reading the install wide one.
// / Any other combination gets no secret at all & fails verification.
function verifyInstallation() {
  // / Set variables.
  global $URL, $VirusScan, $AllowUserVirusScan, $InstLoc, $ServerRootDir, $ConvertLoc, $LogDir, $LogFile, $ApplicationName, $ApplicationTitle, $SupportedLanguages, $DefaultLanguage, $AllowUserSelectableLanguage, $SupportedGuis, $DefaultGui, $AllowUserSelectableGui, $DeleteThreshold, $Verbose, $MaxLogSize, $Font, $ButtonStyle, $SupportedColors, $AllowUserSelectableColor, $ColorToUse, $ShowGUI, $ShowFinePrint, $TOSURL, $PPURL, $ScanCoreMemoryLimit, $ScanCoreChunkSize, $ScanCoreDebug, $ScanCoreVerbose, $SpinnerStyle, $SpinnerColor, $AllowUserShare, $SupportedConversionTypes, $VersionInfoFile, $Version, $UserArchiveArray, $UserDearchiveArray, $UserDocumentArray, $UserSpreadsheetArray, $UserPresentationInputArray, $UserPresentationOutputArray, $UserXPSInputArray, $UserXPSOutputArray, $UserImageArray, $UserMediaInputArray, $UserMediaOutputArray, $UserVideoInputArray, $UserVideoOutputArray, $UserStreamArray, $UserDrawingArray, $UserSVGInputArray, $UserSVGOutputArray, $UserModelArray, $UserSubtitleInputArray, $UserSubtitleOutputArray, $UserPDFWorkArr, $RARArchiveMethod, $RetryCount, $DocumentEngineSleepTimer, $HomeLoc, $ProprietaryLoc, $UsePatchedDocumentEngine, $StreamWatchTimeout, $StreamConnectionTimeout, $AllowStreamOverHTTP, $StreamInspectionLayers, $StreamInspectionFilesPerLayer, $DefaultStreamInspectionForfeitAction, $MaxStreamInspectionFileSize, $UniqueDailyLogHash, $AppendLogHashToLogFiles, $SecretKey, $SecretFile, $RequiredSecretVersion, $MinimumSCADVersion, $AllowSCADIncludeResolution, $SCADConversionTimeout, $UserSCADArray, $MinimumFFMPEGVersion, $MinimumStreamFFMPEGVersion, $MinimumLibreOfficeVersion, $ConfigVersion, $HRConvertVersion, $DeleteBuildEnvironment, $DeleteDevelopmentDocumentation, $MinimumInkscapeVersion, $RequiredGuiVersion, $RequiredLanguageVersion, $MinimumImageVersion, $UsePyMeshLab, $MinimumMeshlabVersion, $MinimumAssimpVersion, $RequiredConfigVersion, $EnableAutoUpdates, $AutoUpdateTargetVersion, $UpdateSourceRepository, $MaxUpdatePackageSize, $UpdateConnectionTimeout, $BackupLoc, $RequireSandbox, $ThrowSandboxWarning, $RequireSandboxOnDocker, $Minimum7zVersion, $MinimumZipVersion, $MinimumRarVersion, $MinimumTarVersion, $MinimumMkisofsVersion, $MinimumDiaVersion, $MinimumTesseractVersion, $MinimumPdftotextVersion, $RunningFromCLI, $CurrentUser, $RunningAsRoot, $RunningInContainer, $ApacheUser, $PermissionLevels, $AllowBootableIsoImage, $UserBootableIsoArray, $MinimumIsoHybridVersion, $MinimumCalibreVersion, $UserEbookInputArray, $UserEbookOutputArray, $EnableMemoryProtection, $ResourceAwarenessActive, $EnableResourceAwareness, $RequireResourceAwareness, $ManagerSocketDir, $DirSep, $RequiredCoreManagerVersion, $CoreManagerVersion, $CoreManagerSubprocessPollInterval, $ResourcePollInterval, $WorkerReapInterval, $WorkerStaleGracePeriod, $TotalResourceBudget, $ReserveResourcePercentage, $MaxConcurrentWorkers, $MaxExpectedRuntime, $MaxRuntimeExtensions, $DefaultConversionCost, $DefaultExpectedRuntime, $CoreLoaded, $PrimaryConvertLoc, $AdditionalConvertLocs, $StorageCleanupInterval, $EnablePerConversionLimits, $MaximumPerConversionResources, $DefaultPerConversionResources, $MinimumPerConversionResources, $RequiredSetupCoreVersion, $RequiredConfigScript, $RequiredDependencyCoreVersion, $RequiredDependsVersion, $RequiredPipelineManagerVersion, $AllowUnprivilegedNamespaces, $MaintainHTAccess;
  putenv('HOME='.$HomeLoc);
  $CoreLoaded = TRUE;
  $InstallationIsVerified = $RunningFromCLI = $RunningAsRoot = $RunningInContainer = FALSE;
  $secretAuthorized = $userSecretAuthorized = $secretIsReady = $configIsValid = $componentIsAvailable = FALSE;
  $detectedCoreManagerVersion = '';
  $SecretKey = $CurrentUser = $detectedConfigVersion = $configFile = $secretFolder = '';
  $applicationSlug = $legacySecretFile = '';
  $SecretFile = '';
  $missingConfigVars = array();
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
  $PermissionLevels = 0755;
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
  // / A manager process is the web server user on the command line, which the rules above
  // / deliberately deny the install secret. It is granted here for the two internal entry
  // / points only, because a per user secret cannot validate a startup key the listener was
  // / launched with, nor decrypt a socket message from a web worker.
  // / argv is read directly because parseCommandLine() has not run yet.
  if ($RunningFromCLI && $CurrentUser === $ApacheUser && isset($_SERVER['argv'][1]) && in_array($_SERVER['argv'][1], array('--start-core-manager', '--start-manager', '--run-core-manager'), TRUE)) {
    // / --run-core-manager is how a service unit starts the listener. Leaving it out of
    // / this list sent the unit down the per user secret path, so every socket key it
    // / derived differed from the ones the web workers use & every message was discarded
    // / as undecryptable. The listener ran perfectly & answered nobody.
    $secretAuthorized = TRUE;
    $userSecretAuthorized = FALSE; }
  // / Define what version of HRConvert2 this core file represents.
  // / Note that this number does not have to match the version numbers of individual components listed below.
  // / The version of the core is typically several versions ahead of indidual component versions. This is normal.
  $HRConvertVersion = 'v3.8.7';
  $HRConvertVersion = ltrim($HRConvertVersion, 'vV');
  // / Define the minimum acceptable config.php version that this convertCore.php can accept.
  // / This is only raised when a release adds or removes a config setting.
  // / A release that changes no settings leaves this alone, so existing config files keep working.
  // / Any config.php version that is greater (newer) than the version listed below is considered acceptable.
  $RequiredConfigVersion = 'v3.8.6';
  $RequiredConfigVersion = ltrim($RequiredConfigVersion, 'vV');
  // / Define the minimum acceptable GUI version that this convertCore.php can accept.
  // / Note that this check looks for the component version to be identical to what is listed below.
  // / Gui version that do not exactly match the version listed below are not considered acceptable.
  // / This is because Guis are not always guaranteed to be forward or reverse compatible.
  $RequiredGuiVersion = 'v3.8.3';
  $RequiredGuiVersion = ltrim($RequiredGuiVersion, 'vV');
  // / Define the minimum acceptable Language Pack version that this convertCore.php can accept.
  // / Note that this check looks for the component version to be identical to what is listed below.
  // / Language version that do not exactly match the version listed below are not considered acceptable.
  // / This is because Language Packs are not always guaranteed to be forward or reverse compatible.
  $RequiredLanguageVersion = 'v3.8.3';
  $RequiredLanguageVersion = ltrim($RequiredLanguageVersion, 'vV');
  // / The Core Manager component version this core requires.
  // / This is an EXACT match. A component built for another core may not be called safely.
  $RequiredCoreManagerVersion = 'v3.8.6';
  $RequiredCoreManagerVersion = ltrim($RequiredCoreManagerVersion, 'vV');
  // / The Setup Core component version this core requires.
  // / This is an EXACT match. A component built for another core may not be called safely.
  // / Setup Core holds the configuration model, so this MUST be raised whenever
  // / $RequiredConfigVersion is raised. Forgetting does not break anything immediately.
  // / The utility reports a variable it does not know as unaccounted & carries on.
  $RequiredSetupCoreVersion = 'v3.8.6';
  $RequiredSetupCoreVersion = ltrim($RequiredSetupCoreVersion, 'vV');
  // / The Dependency Core component version this core requires.
  // / This is an EXACT match. A component built for another core may not be called safely.
  $RequiredDependencyCoreVersion = 'v3.8.6';
  $RequiredDependencyCoreVersion = ltrim($RequiredDependencyCoreVersion, 'vV');
  // / The dependency manifest version this core requires.
  // / Raise this whenever a dependency is added, removed, or its minimum version moves.
  // / A manifest from another release may name a package that no longer exists.
  $RequiredDependsVersion = 'v3.8.6';
  $RequiredDependsVersion = ltrim($RequiredDependsVersion, 'vV');
  // / The Pipeline Manager component version this core requires.
  // / This is an EXACT match. A component built for another core may declare an entry point
  // / whose arguments have moved, or capabilities this core cannot honour.
  // / Raise this whenever a pipeline is added, removed, or its own version pin moves.
  // / The manager carries the pin list for every pipeline it accepts.
  $RequiredPipelineManagerVersion = 'v3.8.6';
  $RequiredPipelineManagerVersion = ltrim($RequiredPipelineManagerVersion, 'vV');
  // / The bootstrap script version this core expects.
  // / A manager compares the script against this & disables the script when it differs.
  $RequiredConfigScript = 'v3.8.0';
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
  if (!file_exists($configFile)) quickDie('Could not process the HRConvert2 Configuration file (config.php)!', 0);
  else require_once ($configFile);
  $ConfigVersion = ltrim($ConfigVersion, 'vV');
  // / Perform a version integrity check.
  // / A core file that does not match versionInfo.php indicates a partial or interrupted update.
  if ($HRConvertVersion !== $Version) quickDie('The core file reports version v'.$HRConvertVersion.' but the version information file reports version v'.$Version.'. This installation is incomplete or was updated incorrectly.', 28001);
  // / Confirm the config file carries every setting this core requires.
  // / An undefined setting reads as NULL, which silently becomes FALSE or zero at every point of use.
  list ($configIsValid, $missingConfigVars, $detectedConfigVersion) = verifyConfigVersion($RequiredConfigVersion);
  if (!$configIsValid) quickDie('The config.php file is missing '.count($missingConfigVars).' required setting(s). Config version detected: v'.$detectedConfigVersion.'. Config version required: v'.$RequiredConfigVersion.'. Missing Variables: '.implode(', ', $missingConfigVars), 28000);
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
    list ($secretIsReady, $SecretKey) = resolveSecretFile($SecretFile, $RequiredSecretVersion);
    // / Remove the unnamed secret file left by a release before this one.
    // / It is removed only once a replacement is in place, so a failure above leaves the
    // / old file usable & the installation recoverable.
    if ($secretIsReady && file_exists($legacySecretFile)) {
      @unlink($legacySecretFile);
      if (isset($LogFile) && is_string($LogFile) && $LogFile !== '') warningEntry('The legacy secret file at '.$legacySecretFile.' was removed.');
      else error_log('WARNING!!! HRConvert2: The legacy secret file at '.$legacySecretFile.' was removed.'); } }
  // / A per user secret lives in the home directory of the user running the command.
  // / The directory is created if it does not exist, because this is the first thing that
  // / user has ever asked HRConvert2 to do.
  else if ($userSecretAuthorized) {
    $secretFolder = getenv('HOME');
    if ($secretFolder === FALSE or $secretFolder === '') $secretFolder = sys_get_temp_dir();
    $secretFolder = $secretFolder.DIRECTORY_SEPARATOR.'.HRConvert2';
    $LogDir = $secretFolder.DIRECTORY_SEPARATOR.'Logs'.DIRECTORY_SEPARATOR;
    if (!is_dir($secretFolder)) @mkdir($secretFolder, 0700, TRUE);
    if (is_dir($secretFolder)) {
      $SecretFile = $secretFolder.DIRECTORY_SEPARATOR.$applicationSlug.'-secret-'.sanitizeString($CurrentUser, TRUE).'.php';
      list ($secretIsReady, $SecretKey) = resolveSecretFile($SecretFile, $RequiredSecretVersion); } }
  // / Validate the AppArmor profile every sandboxed conversion depends on.
  // / This is not owned by any one conversion pipeline, so it is checked here rather than
  // / from a dependency check. A root run repairs it & every other context reports it.
  verifySandboxPolicy($RunningAsRoot);
  // / Resolve the Core Manager component. Resource awareness is unavailable without it.
  $ResourceAwarenessActive = FALSE;
  $ManagerSocketDir = $ConvertLoc.DIRECTORY_SEPARATOR.'Sockets';
  if ($secretIsReady && $EnableResourceAwareness) {
    list ($componentIsAvailable, $detectedCoreManagerVersion) = verifyCoreComponent('Core Manager', 'coreManager.php', 'CoreManagerVersion', $RequiredCoreManagerVersion);
    if ($componentIsAvailable) $ResourceAwarenessActive = TRUE;
    // / An administrator may refuse to run without resource awareness.
    else if ($RequireResourceAwareness) errorEntry('Resource awareness is required by config.php & the Core Manager component is unavailable!', 31010, TRUE); }
  else if ($RequireResourceAwareness) errorEntry('Resource awareness is required by config.php but is disabled or has no install secret!', 31013, TRUE);
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
  purgeSensitiveMemory($EnableMemoryProtection, $secretAuthorized, $userSecretAuthorized, $secretIsReady, $configIsValid, $missingConfigVars, $detectedConfigVersion, $secretFolder, $applicationSlug, $legacySecretFile, $componentIsAvailable, $detectedCoreManagerVersion);
  return array($InstallationIsVerified, $configFile, $Version, $CoreLoaded); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to detect the users IP & user agent.
// / NOTE: As of v3.5.x the IP is NO LONGER used to derive any session identifier.
// / HTTP_CLIENT_IP & HTTP_X_FORWARDED_FOR are attacker-supplied headers & cannot be trusted
// / as a binding factor. They also rotate legitimately on mobile networks, which broke sessions.
// / The token pair is the credential. The IP is retained for logging purposes only.
function verifySession() {
  // / Set variables.
  $IP = '';
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
// / The log hash suffix is assembled before any filename that uses it is built.
// / The ClamAV rotation loop rotates the ClamAV log instead of the application log.
// / The rotation condition compares the file size directly against the configured maximum.
function verifyLogs() {
  // / Set variables.
  global $LogDir, $LogFile, $MaxLogSize, $SesHash4, $DefaultLogDir, $DefaultLogSize, $Time, $Date, $LogInc, $LogInc2, $VirusScan, $ApplicationName, $ConvertLoc, $AppendLogHashToLogFiles, $ApacheUser, $EnableMemoryProtection, $RunningAsRoot;
  $LogExists = $logWritten = FALSE;
  $logHashAppend = '';
  $LogInc = $LogInc2 = 0;
  $DefaultLogDir = $ConvertLoc.'/Logs';
  $DefaultLogSize = 1048576;
  // / Build the hash suffix before it is used in any filename.
  if ($AppendLogHashToLogFiles) $logHashAppend = '_'.$SesHash4;
  $ClamLogFile = str_replace('..', '', $LogDir.'/ClamLog_'.$LogInc2.'_'.$Date.$logHashAppend.'.txt');
  $LogFile = str_replace('..', '', $LogDir.'/'.$ApplicationName.'_'.$LogInc.'_'.$Date.$logHashAppend.'.txt');
  if (!is_numeric($MaxLogSize)) $MaxLogSize = $DefaultLogSize;
  // / The permissions in this function are hard-coded deliberately.
  // / If running as root (such as after updating or installing fresh) the Log dir needs to be writable to www-data.
  // / If running as a regular user, the user will either possess or lack the permissions needed to leave the $LogDir in proper condition.
  // / So a regular user fails silent here (and is caught further down), but they also don't do anything to the filesystem on failure.
  // / A root user or a web user will always leave a valid $LogDir for a web user.
  if (!is_dir($LogDir)) {
    @mkdir($LogDir, 0755);
    @chown($LogDir, 'www-data');
    @chgrp($LogDir, 'www-data'); }
  // / If the $LogDir still doesn't exist after we tried to create it, fallback to trying to create one in the $ConvertLoc instead.
  if (!is_dir($LogDir)) {
    $LogDir = $DefaultLogDir;
    $ClamLogFile = str_replace('..', '', $LogDir.'/ClamLog_'.$LogInc2.'_'.$Date.$logHashAppend.'.txt');
    $LogFile = str_replace('..', '', $LogDir.'/'.$ApplicationName.'_'.$LogInc.'_'.$Date.$logHashAppend.'.txt'); }
  // / Check if the fallback $LogDir exists already before trying to create a new one.
  if (!is_dir($LogDir)) {
    @mkdir($LogDir, 0755);
    @chown($LogDir, 'www-data');
    @chgrp($LogDir, 'www-data'); }
  // / If the $LogDir still doesn't exist after we tried the fallback, consider the operation to have failed.
  // / The core will die when this function returns.
  // / An error will be printed to the screen for CLI users, or to the page for web users.
  if (!is_dir($LogDir)) $LogExists = FALSE;
  // / Regardless of whether or not a $LogDir exists, we will silently try to drop an index.html into it anyway as document root protection.
  if (!file_exists($LogDir.'/index.html')) @copy('index.html', $LogDir.'/index.html');
  // / Advance to a new log file whenever the current one has reached the maximum size.
  while (file_exists($LogFile) && filesize($LogFile) > $MaxLogSize) {
    $LogInc++;
    $LogFile = str_replace('..', '', $LogDir.'/'.$ApplicationName.'_'.$LogInc.'_'.$Date.$logHashAppend.'.txt'); }
    if (!file_exists($LogFile)) {
    $logWritten = file_put_contents($LogFile, 'OP-Act, '.$Time.': Logfile created using method 1.'.PHP_EOL, FILE_APPEND);
    // / A logfile created by root in a shared location must belong to the web server user,
    // / or every later web request & manager process is locked out of its own log.
    if ($RunningAsRoot) {
      @chown($LogFile, $ApacheUser);
      @chgrp($LogFile, $ApacheUser);
      @chmod($LogFile, 0664); } }
  if (file_exists($LogFile)) $LogExists = TRUE;
  // / Set a clamlog file depending on whether or not the max filesize has been reached.
  // / The ClamAV log file is not created here, only named.
  if ($VirusScan) {
    while (file_exists($ClamLogFile) && filesize($ClamLogFile) > $MaxLogSize) {
      $LogInc2++;
      $ClamLogFile = str_replace('..', '', $LogDir.'/ClamLog_'.$LogInc2.'_'.$Date.$logHashAppend.'.txt'); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $logWritten, $logHashAppend);
  return array($LogExists, $LogFile, $ClamLogFile); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report a fatal condition & halt, without needing a logging environment.
// / Accepts the message & the documented error number, in that order.
// / Never returns. Execution stops here.
// / This is the only place the application halts on an error.
// / Every die in the core routes through here so a halt is formatted the same way, closes
// / the connection the same way & is recorded the same way, whichever stage of boot it
// / happens in. Before verifyLogs() there is no logfile & before verifyGlobals() there is
// / no $Lol, so this function assumes nothing exists & tests everything it uses.
function quickDie($entry, $errorNumber) {
  // / Set variables.
  global $Time, $LogFile, $SesHash3, $ApplicationName, $RunningFromCLI;
  // / The timestamp is recomputed HERE, at the moment the entry is written, rather than
  // / taken from $Time. $Time is set once by verifyTime during boot, which is correct for a
  // / web request that lives half a second & wrong for a manager process that runs for
  // / weeks. A listener started at three in the morning stamped every entry it ever wrote
  // / with three in the morning, which reads as a clock fault in one process & is not.
  // / $Time itself is left alone, because a session directory is named from it & must not
  // / change underneath a request.
  // / An empty $Time means verifyTime has not run, so no timezone is set & no time is known.
  $timeLabel = (isset($Time) && $Time !== '') ? date('F j, Y, g:i a') : 'Unknown Time';
  $sessionLabel = (isset($SesHash3) && $SesHash3 !== '') ? (string)$SesHash3 : 'No Session';
  $applicationLabel = (isset($ApplicationName) && is_string($ApplicationName) && $ApplicationName !== '') ? $ApplicationName : 'HRConvert2';
  $errorLabel = is_numeric($errorNumber) ? $applicationLabel.'-'.$errorNumber : $applicationLabel.'-###';
  $formattedEntry = 'ERROR!!! '.$timeLabel.', '.$errorLabel.', '.$sessionLabel.': '.$entry;
  $logIsWritable = (isset($LogFile) && is_string($LogFile) && $LogFile !== '');
  // / Record it wherever it can be recorded. A halt with no record is a support ticket
  // / that can never be answered.
  if (!$logIsWritable) error_log($formattedEntry);
  // / A browser needs a line break to render the message. A terminal does not & would
  // / print the tag literally.
  if ($RunningFromCLI) print($formattedEntry.PHP_EOL);
  else print($formattedEntry.PHP_EOL.'<br />'.PHP_EOL);
  // / Close the connection deliberately rather than dropping it, so the message is
  // / delivered before the process ends. There is nothing to close on the command line.
  if (!$RunningFromCLI && function_exists('closeHRC2Connection')) closeHRC2Connection();
  die(); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write normal operational activity to the logfile.
// / Accepts the message.
// / Returns TRUE when the entry was recorded.
// / Suppressed by the caller when $Verbose is FALSE. This function does not test $Verbose,
// / because a caller that wants an entry recorded regardless must be able to say so.
// / An entry raised before verifyLogs() has no logfile to reach & goes to the server error
// / log instead. Writing to an unset path produces a raw PHP warning on the page.
function logEntry($entry) {
  // / Set variables.
  global $Time, $LogFile, $SesHash3;
  $LogWritten = FALSE;
  // / The timestamp is recomputed HERE, at the moment the entry is written, rather than
  // / taken from $Time. $Time is set once by verifyTime during boot, which is correct for a
  // / web request that lives half a second & wrong for a manager process that runs for
  // / weeks. A listener started at three in the morning stamped every entry it ever wrote
  // / with three in the morning, which reads as a clock fault in one process & is not.
  // / $Time itself is left alone, because a session directory is named from it & must not
  // / change underneath a request.
  // / An empty $Time means verifyTime has not run, so no timezone is set & no time is known.
  $timeLabel = (isset($Time) && $Time !== '') ? date('F j, Y, g:i a') : 'Unknown Time';
  $sessionLabel = (isset($SesHash3) && $SesHash3 !== '') ? (string)$SesHash3 : 'No Session';
  $formattedEntry = 'Op-Act, '.$timeLabel.', '.$sessionLabel.': '.$entry;
  $logIsWritable = (isset($LogFile) && is_string($LogFile) && $LogFile !== '');
  if ($logIsWritable) $LogWritten = (file_put_contents($LogFile, $formattedEntry.PHP_EOL, FILE_APPEND) !== FALSE);
  else error_log($formattedEntry);
  // / This cleanup is manual, because purgeSensitiveMemory() calls this function.
  $entry = $timeLabel = $sessionLabel = $formattedEntry = $logIsWritable = NULL;
  unset($entry, $timeLabel, $sessionLabel, $formattedEntry, $logIsWritable);
  return $LogWritten; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write a warning to the logfile.
// / Accepts the message.
// / Returns TRUE when the entry was recorded.
// / A warning is always written & carries no error number. It can never halt execution.
// / An entry raised before verifyLogs() goes to the server error log instead.
function warningEntry($entry) {
  // / Set variables.
  global $Time, $LogFile, $SesHash3;
  $LogWritten = FALSE;
  // / The timestamp is recomputed HERE, at the moment the entry is written, rather than
  // / taken from $Time. $Time is set once by verifyTime during boot, which is correct for a
  // / web request that lives half a second & wrong for a manager process that runs for
  // / weeks. A listener started at three in the morning stamped every entry it ever wrote
  // / with three in the morning, which reads as a clock fault in one process & is not.
  // / $Time itself is left alone, because a session directory is named from it & must not
  // / change underneath a request.
  // / An empty $Time means verifyTime has not run, so no timezone is set & no time is known.
  $timeLabel = (isset($Time) && $Time !== '') ? date('F j, Y, g:i a') : 'Unknown Time';
  $sessionLabel = (isset($SesHash3) && $SesHash3 !== '') ? (string)$SesHash3 : 'No Session';
  $formattedEntry = 'WARNING!!! '.$timeLabel.', '.$sessionLabel.': '.$entry;
  $logIsWritable = (isset($LogFile) && is_string($LogFile) && $LogFile !== '');
  if ($logIsWritable) $LogWritten = (file_put_contents($LogFile, $formattedEntry.PHP_EOL, FILE_APPEND) !== FALSE);
  else error_log($formattedEntry);
  // / This cleanup is manual, because purgeSensitiveMemory() calls this function.
  $entry = $timeLabel = $sessionLabel = $formattedEntry = $logIsWritable = NULL;
  unset($entry, $timeLabel, $sessionLabel, $formattedEntry, $logIsWritable);
  return $LogWritten; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write a numbered error to the logfile & optionally halt.
// / Accepts the message, the documented error number & a fatality boolean, in that order.
// / Returns TRUE when the entry was recorded. Returns nothing at all when it halts.
// / An entry raised before verifyLogs() goes to the server error log instead.
// / A fatal error hands off to quickDie so every halt in the core is identical.
function errorEntry($entry, $errorNumber, $die) {
  // / Set variables.
  global $Time, $LogFile, $SesHash3, $ApplicationName;
  $LogWritten = FALSE;
  // / The timestamp is recomputed HERE, at the moment the entry is written, rather than
  // / taken from $Time. $Time is set once by verifyTime during boot, which is correct for a
  // / web request that lives half a second & wrong for a manager process that runs for
  // / weeks. A listener started at three in the morning stamped every entry it ever wrote
  // / with three in the morning, which reads as a clock fault in one process & is not.
  // / $Time itself is left alone, because a session directory is named from it & must not
  // / change underneath a request.
  // / An empty $Time means verifyTime has not run, so no timezone is set & no time is known.
  $timeLabel = (isset($Time) && $Time !== '') ? date('F j, Y, g:i a') : 'Unknown Time';
  $sessionLabel = (isset($SesHash3) && $SesHash3 !== '') ? (string)$SesHash3 : 'No Session';
  $applicationLabel = (isset($ApplicationName) && is_string($ApplicationName) && $ApplicationName !== '') ? $ApplicationName : 'HRConvert2';
  $errorLabel = is_numeric($errorNumber) ? $applicationLabel.'-'.$errorNumber : $applicationLabel.'-###';
  $formattedEntry = 'ERROR!!! '.$timeLabel.', '.$errorLabel.', '.$sessionLabel.': '.$entry;
  $logIsWritable = (isset($LogFile) && is_string($LogFile) && $LogFile !== '');
  if ($logIsWritable) $LogWritten = (file_put_contents($LogFile, $formattedEntry.PHP_EOL, FILE_APPEND) !== FALSE);
  else error_log($formattedEntry);
  // / quickDie repeats none of the above. It reports to the page & closes the connection.
  if ($die) quickDie($entry, $errorNumber);
  // / This cleanup is manual, because purgeSensitiveMemory() calls this function.
  $entry = $errorNumber = $die = $timeLabel = $sessionLabel = $applicationLabel = $errorLabel = $formattedEntry = $logIsWritable = NULL;
  unset($entry, $errorNumber, $die, $timeLabel, $sessionLabel, $applicationLabel, $errorLabel, $formattedEntry, $logIsWritable);
  return $LogWritten; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to set an echo variable that adjusts printed URL's to https when SSL is enabled.
function verifyEncryption() {
  $EncryptionVerified = TRUE;
  // / Determine if the connection is encrypted and adjust the $URLEcho accordingly.
  if (!empty($_SERVER['HTTPS']) && $_SERVER['SERVER_PORT'] == 443) $URLEcho = 's';
  else $URLEcho = '';
  return array($EncryptionVerified, $URLEcho); }
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
  global $ShowGUI, $EnableMemoryProtection, $NoGui, $ShowFiles, $FileListOnly;
  $var = FALSE;
  $InputsAreVerified = TRUE;
  $GUI = $Color = $Language = $Token1 = $Token2 = $Height = $Width = $Rotate = $Bitrate = $Method = $Download = $UserFilename = $UserExtension = $Archive = $UserScanType = $ScanAll = $UserClamScan = $UserScanCoreScan = $var = '';
  $variableIsSanitized = $ConvertSelected = $PDFWorkSelected = $FilesToArchive = $FilesToScan = $FilesToDelete = array();
  $key = 0;
  $ScanType = 'all';
  // / Determine whether or not to display a full GUI or a minimalized GUI.
  // / The default action is set in config.php. The user can opt for less than the default, but never more.
  // / The user can only disable the full GUI and fall back to a minimal one. 
  // / The user can never force enable a full GUI if $ShowGUI is set to FALSE in config.php  
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
  if (isset($_POST['pdfextension'])) list ($UserExtension, $variableIsSanitized[$key++]) = sanitize($_POST['pdfextension'], TRUE);
  if (isset($_POST['userpdfconvertfilename'])) list ($UserFilename, $variableIsSanitized[$key++]) = sanitize($_POST['userpdfconvertfilename'], TRUE);
  if (isset($_POST['scanallbutton'])) list ($ScanAll, $variableIsSanitized[$key++]) = sanitize($_POST['scanallbutton'], TRUE);
  if (isset($_POST['scantype'])) list ($UserScanType, $variableIsSanitized[$key++]) = sanitize($_POST['scantype'], TRUE);
  if (isset($_POST['clamscanbutton'])) list ($UserClamScan, $variableIsSanitized[$key++]) = sanitize($_POST['clamscanbutton'], TRUE);
  if (isset($_POST['scancorebutton'])) list ($UserScanCoreScan, $variableIsSanitized[$key++]) = sanitize($_POST['scancorebutton'], TRUE);
  if (isset($_POST['filesToScan'])) list ($FilesToScan, $variableIsSanitized[$key++]) = sanitize($_POST['filesToScan'], TRUE);
  // / Handle when a user submits User Virus Scan options.
  if (isset($_POST['clamScanButton']) && isset($_POST['filesToScan'])) $ScanType = 'clamav';
  if (isset($_POST['scancorebutton']) && isset($_POST['filesToScan'])) $ScanType = 'scancore';
  if (isset($_POST['scanallbutton']) && isset($_POST['filesToScan'])) $ScanType = 'all';
  // / Check the list of error check results and see if any errors occured.
  foreach ($variableIsSanitized as $var) if (!$var) ($InputsAreVerified = FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $variableIsSanitized, $key, $var);
  return array($InputsAreVerified, $ShowGUI, $GUI, $Color, $Language, $Token1, $Token2, $Height, $Width, $Rotate, $Bitrate, $Method, $Download, $UserFilename, $UserExtension, $FilesToArchive, $PDFWorkSelected, $ConvertSelected, $FilesToScan, $FilesToDelete, $UserScanType); }
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
// / If allowed and if specified, detect the users specified color and set that as the color to use.
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
  global $GUI, $DefaultGui, $SupportedGuis, $AllowUserSelectableGui, $GuiFiles, $GuiDir, $GuiResourcesDir, $GuiImageDir, $GuiCSSDir, $GuiJSDir, $GuiHeaderFile, $GuiFooterFile, $GuiUI1File, $GuiUI2File, $GreenButtonCode, $BlueButtonCode, $RedButtonCode, $OrangeButtonCode, $PurpleButtonCode, $DarkButtonCode, $DefaultButtonCode, $Font, $GuiVersion, $RequiredGuiVersion, $EnableMemoryProtection, $CoreLoaded;
  $reqFile = $GuiIsSet = FALSE;
  $GuiToUse = $defaultGui = 'Default';
  $GuiFiles = $guiFiles = array();
  $defaultGuis = array('Default', 'Original', 'Wide');
  // / Make sure $SupportedGuis is valid.
  if (!isset($SupportedGuis) or !is_array($SupportedGuis)) $SupportedGuis = $defaultGuis;
  // / Make sure the Default GUI is valid.
  if (isset($DefaultGui)) if (in_array($DefaultGui, $SupportedGuis)) $GuiToUse = $DefaultGui;
  // / If allowed and if specified, detect the users specified GUI and set that as the GUI to use.
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
    $StyleCoreFile = $GuiDir.'styleCore.php';
    $GuiHeaderFile = $GuiDir.'header.php';
    $GuiFooterFile = $GuiDir.'footer.php';
    $GuiUI1File = $GuiDir.'convertGui1.php';
    $GuiUI2File = $GuiDir.'convertGui2.php';
    $GuiVersionFile = $GuiDir.'uiVersionInfo.php';
    $GuiResourcesDir = $GuiDir.'Resources/';
    $GuiImageDir = $GuiResourcesDir.'Image/';
    $GuiCSSDir = $GuiResourcesDir.'CSS/';
    $GuiJSDir = $GuiResourcesDir.'JS/';
    $guiFiles = array($GuiHeaderFile, $GuiFooterFile, $GuiUI1File, $GuiUI2File, $StyleCoreFile, $GuiVersionFile);
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
        require($GuiVersionFile);
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
    require_once($StyleCoreFile);
    $GreenButtonCode = $greenButtonCode;
    $BlueButtonCode = $blueButtonCode;
    $RedButtonCode = $redButtonCode;
    $OrangeButtonCode = $orangeButtonCode;
    $PurpleButtonCode = $purpleButtonCode;
    $DarkButtonCode = $darkButtonCode;
    $DefaultButtonCode = $defaultButtonCode; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
purgeSensitiveMemory($EnableMemoryProtection, $defaultGuis, $defaultGui, $candidateGuis, $candidateGui, $reqFile, $reqDir, $guiFiles, $guiDirs, $guiDirsExist, $StyleCoreFile, $GuiVersionFile, $greenButtonCode, $blueButtonCode, $redButtonCode, $orangeButtonCode, $purpleButtonCode, $darkButtonCode, $defaultButtonCode);
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
  // / If allowed and if specified, detect the users specified language and set that as the language to use.
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
// / A function to enumerate every data location this installation may use.
// / Accepts no arguments.
// / Returns an array of entries, each carrying a Path & a Type, with the primary first.
// / The primary is the --Convert Location-- declared in config.php & is always present.
// / An entry that is not an array of a path & a type is skipped & reported.
// / A duplicate path is skipped, because the same location twice would double its share.
function enumerateConvertLocs() {
  // / Set variables.
  global $PrimaryConvertLoc, $ConvertLoc, $AdditionalConvertLocs, $DirSep, $EnableMemoryProtection;
  $ConvertLocPool = array();
  $additionalEntry = $seenPaths = array();
  $entryPath = $entryType = $primaryPath = '';
  // / $PrimaryConvertLoc holds the configured value & is set once, before $ConvertLoc is
  // / narrowed to the location this session actually uses.
  $primaryPath = (isset($PrimaryConvertLoc) && is_string($PrimaryConvertLoc) && $PrimaryConvertLoc !== '') ? $PrimaryConvertLoc : (string)$ConvertLoc;
  $primaryPath = rtrim($primaryPath, $DirSep);
  $ConvertLocPool[] = array('Path' => $primaryPath, 'Type' => 'primary');
  $seenPaths[] = $primaryPath;
  if (is_array($AdditionalConvertLocs)) {
    foreach ($AdditionalConvertLocs as $additionalEntry) {
      if (!is_array($additionalEntry) or count($additionalEntry) < 2) warningEntry('An entry in Additional Data Locations is not an array of a path & a type. It was skipped.');
      else {
        $entryPath = rtrim(trim((string)$additionalEntry[0]), $DirSep);
        $entryType = strtolower(trim((string)$additionalEntry[1]));
        // / An unrecognized type becomes a standby rather than joining the distribution.
        // / A typo must not silently start sending user data somewhere unintended.
        if ($entryType !== 'roundrobin' && $entryType !== 'leastactive' && $entryType !== 'redundant') {
          warningEntry('The data location at '.$entryPath.' declares an unrecognized type of '.$entryType.'. It will be treated as redundant.');
          $entryType = 'redundant'; }
        if ($entryPath === '') warningEntry('An entry in Additional Data Locations has an empty path. It was skipped.');
        else if (in_array($entryPath, $seenPaths, TRUE)) warningEntry('The data location at '.$entryPath.' is listed more than once. The duplicate was skipped.');
        else {
          $ConvertLocPool[] = array('Path' => $entryPath, 'Type' => $entryType);
          $seenPaths[] = $entryPath; } } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $ConvertLocPool is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $additionalEntry, $seenPaths, $entryPath, $entryType, $primaryPath);
  return $ConvertLocPool; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report whether a path is one of the configured data locations.
// / Accepts the absolute path to test.
// / Returns TRUE only when the path appears in the configured set.
// / A scheduled sweep uses this so it can clean a location this worker is not using, while
// / an arbitrary path is still refused.
function convertLocIsConfigured($candidatePath) {
  // / Set variables.
  global $DirSep, $EnableMemoryProtection;
  $PathIsConfigured = FALSE;
  $convertLocPool = $poolEntry = array();
  $cleanCandidate = rtrim(trim((string)$candidatePath), $DirSep);
  $convertLocPool = enumerateConvertLocs();
  if ($cleanCandidate !== '') {
    foreach ($convertLocPool as $poolEntry) { if ($poolEntry['Path'] === $cleanCandidate) $PathIsConfigured = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $convertLocPool, $poolEntry, $cleanCandidate, $candidatePath);
  return $PathIsConfigured; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to count the sessions currently held in one data location.
// / Accepts the absolute path of the location.
// / Returns the number of session directories across every daily directory it holds.
// / This is the measure least active selection compares. It is only called when a session
// / is new, because an established session never chooses a location again.
function countConvertLocSessions($convertLocPath) {
  // / Set variables.
  global $ProtectedRootDirs, $DirSep, $EnableMemoryProtection;
  $SessionCount = 0;
  $dailyDirs = $sessionDirs = array();
  $dailyDir = $dailyPath = '';
  if (is_dir($convertLocPath)) {
    $dailyDirs = array_diff(scandir($convertLocPath), array('..', '.'));
    foreach ($dailyDirs as $dailyDir) {
      $dailyPath = $convertLocPath.$DirSep.$dailyDir;
      // / A protected root directory is not a daily directory & holds no sessions.
      if (!in_array($dailyDir, $ProtectedRootDirs, TRUE) && is_dir($dailyPath)) {
        $sessionDirs = array_diff(scandir($dailyPath), array('..', '.'));
        $SessionCount = $SessionCount + count($sessionDirs); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dailyDirs, $sessionDirs, $dailyDir, $dailyPath, $convertLocPath);
  return $SessionCount; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide which data location this session will use.
// / Accepts the daily hash & the session hash, in that order.
// / Returns an absolute path as a string, ALWAYS. Every failure falls back to the location
// / config.php declares, so a caller never has to test the result before using it.
// / AN ESTABLISHED SESSION IS DISCOVERED, NOT CHOSEN. A session that already holds a
// / directory somewhere keeps that location for its whole life. Choosing again on a later
// / request would send the worker looking for the user's files where they are not.
// / Only a session with no directory anywhere is distributed, & only then does type matter.
// / A location marked leastactive puts the whole pool into least active selection.
// / Otherwise the pool is distributed by session identifier, which needs no shared counter
// / & therefore works with several front ends that never speak to each other.
// / A location marked redundant is a standby. It takes a session only when every other
// / location is unusable.
function resolveConvertLoc($dailyHash, $sessionHash) {
  // / Set variables.
  global $PrimaryConvertLoc, $ConvertLoc, $DirSep, $Verbose, $EnableMemoryProtection;
  $ResolvedConvertLoc = '';
  $convertLocPool = $distributionPool = $redundantPool = $poolEntry = array();
  $selectionMode = $cleanDailyHash = $cleanSessionHash = '';
  $sessionCount = $lowestSessionCount = $selectionIndex = 0;
  $sessionIsDiscovered = FALSE;
  // / The fallback is established first, so every path below can only improve on it.
  $ResolvedConvertLoc = (isset($PrimaryConvertLoc) && is_string($PrimaryConvertLoc) && $PrimaryConvertLoc !== '') ? $PrimaryConvertLoc : (string)$ConvertLoc;
  $ResolvedConvertLoc = rtrim($ResolvedConvertLoc, $DirSep);
  $cleanDailyHash = preg_replace('/[^A-Za-z0-9]/', '', (string)$dailyHash);
  $cleanSessionHash = preg_replace('/[^A-Za-z0-9]/', '', (string)$sessionHash);
  $convertLocPool = enumerateConvertLocs();
  // / Discovery. An existing session directory decides the answer outright.
  if ($cleanDailyHash !== '' && $cleanSessionHash !== '') {
    foreach ($convertLocPool as $poolEntry) {
      if (!$sessionIsDiscovered && is_dir($poolEntry['Path'].$DirSep.$cleanDailyHash.$DirSep.$cleanSessionHash)) {
        $ResolvedConvertLoc = $poolEntry['Path'];
        $sessionIsDiscovered = TRUE; } } }
  // / A new session is distributed across whatever is usable right now.
  if (!$sessionIsDiscovered) {
    foreach ($convertLocPool as $poolEntry) {
      if (is_dir($poolEntry['Path']) && is_writable($poolEntry['Path'])) {
        if ($poolEntry['Type'] === 'redundant') $redundantPool[] = $poolEntry;
        else $distributionPool[] = $poolEntry;
        if ($poolEntry['Type'] === 'leastactive') $selectionMode = 'leastactive'; } }
    if ($selectionMode === '') $selectionMode = 'distributed';
    // / One usable candidate is not a distribution. It is the answer.
    if (count($distributionPool) === 1) $ResolvedConvertLoc = $distributionPool[0]['Path'];
    else if (count($distributionPool) > 1 && $selectionMode === 'leastactive') {
      $lowestSessionCount = -1;
      foreach ($distributionPool as $poolEntry) {
        $sessionCount = countConvertLocSessions($poolEntry['Path']);
        if ($lowestSessionCount < 0 or $sessionCount < $lowestSessionCount) {
          $lowestSessionCount = $sessionCount;
          $ResolvedConvertLoc = $poolEntry['Path']; } } }
    else if (count($distributionPool) > 1) {
      // / Derived from the session identifier rather than from a counter, so two front ends
      // / that share storage & never speak to each other still agree on the answer.
      $selectionIndex = abs((int)crc32($cleanSessionHash)) % count($distributionPool);
      $ResolvedConvertLoc = $distributionPool[$selectionIndex]['Path']; }
    // / Nothing in the distribution pool is usable, so a standby takes the session.
    else if (!empty($redundantPool)) {
      $ResolvedConvertLoc = $redundantPool[0]['Path'];
      warningEntry('Every distributed data location is unusable. The redundant location at '.$ResolvedConvertLoc.' has taken this session.'); }
    // / Nothing at all is usable. The configured location is returned & will fail loudly
    // / at directory verification, which is a better failure than a silent wrong path.
    else warningEntry('No configured data location is usable. Falling back to '.$ResolvedConvertLoc.'.'); }
  if ($Verbose) logEntry('Data Location: '.$ResolvedConvertLoc.', Pool: '.count($convertLocPool).', Session: '.($sessionIsDiscovered ? 'DISCOVERED' : 'NEW, selected by '.$selectionMode).'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $ResolvedConvertLoc is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $convertLocPool, $distributionPool, $redundantPool, $poolEntry, $selectionMode, $cleanDailyHash, $cleanSessionHash, $sessionCount, $lowestSessionCount, $selectionIndex, $sessionIsDiscovered, $dailyHash, $sessionHash);
  return $ResolvedConvertLoc; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to ask the listener which data location this session must use.
// / Accepts the daily hash & the session hash, in that order.
// / Returns an absolute path as a string, ALWAYS.
// / The managers hold the session map, so the answer is the same for every front end that
// / shares this secret. With no listener the configured location is used, which is exactly
// / how a standalone installation has always behaved.
// / A command line invocation never asks. Administrative work operates on the configured
// / location & must not stall waiting on a listener that may not be running.
function requestConvertLoc($dailyHash, $sessionHash) {
  // / Set variables.
  global $ResourceAwarenessActive, $RunningFromCLI, $ManagerSocketTimeout, $DirSep, $Verbose, $EnableMemoryProtection;
  $ResolvedConvertLoc = '';
  $requestPayload = $replyPayload = array();
  $messageWasDelivered = FALSE;
  $answerSource = 'config.php';
  // / The fallback is discovery, not the configured location.
  // / An earlier release fell back to whatever config.php named, which is correct for a
  // / session that does not exist yet & CATASTROPHIC for one that does. A session already
  // / holding files in a second data location was sent to the first, found an empty
  // / directory, & reported that the user had uploaded nothing. The files were never lost.
  // / They were simply no longer where anything was looking.
  // / resolveConvertLoc reads the pool from disk & returns the location that actually holds
  // / this session before it distributes anything, so an unanswered request degrades to the
  // / correct answer rather than to a plausible one.
  $ResolvedConvertLoc = rtrim(resolveConvertLoc($dailyHash, $sessionHash), $DirSep);
  if (!$ResourceAwarenessActive) $answerSource = 'discovery, no listener component';
  else if ($RunningFromCLI) $answerSource = 'discovery, command line context';
  else {
    $requestPayload = array('RequestType' => 'convertloc', 'DailyHash' => (string)$dailyHash, 'SessionHash' => (string)$sessionHash, 'WorkerPid' => getmypid());
    list ($messageWasDelivered, $replyPayload) = sendManagerMessage(buildManagerSocketPath('request-manager'), $requestPayload, 'worker', (int)$ManagerSocketTimeout * 3);
    // / An unanswered request is a listener that is slow or absent, not an instruction to
    // / move this session somewhere else. The configured location is the safe answer.
    // / A location discovered on disk is not a guess. It is where this session's files are.
    if (!$messageWasDelivered) { warningEntry('The Core Manager listener did not answer a data location request. Using '.$ResolvedConvertLoc.', located by searching the configured pool for this session.'); $answerSource = 'discovery, listener silent'; }
    else if (!isset($replyPayload['ConvertLoc']) or !is_string($replyPayload['ConvertLoc']) or trim($replyPayload['ConvertLoc']) === '') { warningEntry('The Core Manager listener returned no usable data location. Using '.$ResolvedConvertLoc.', located by searching the configured pool for this session.'); $answerSource = 'discovery, listener unusable'; }
    else {
      $ResolvedConvertLoc = rtrim(trim($replyPayload['ConvertLoc']), $DirSep);
      $answerSource = 'listener'; } }
  if ($Verbose) logEntry('Data Location: '.$ResolvedConvertLoc.', Source: '.$answerSource.'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $ResolvedConvertLoc is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $requestPayload, $replyPayload, $messageWasDelivered, $answerSource, $dailyHash, $sessionHash);
  return $ResolvedConvertLoc; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to set the global variables for the session.
// / The stream timeouts are left in the units config.php documents them in.
// / $StreamWatchTimeout is stated in minutes & $StreamConnectionTimeout is stated in seconds.
// / Each point of use converts once, where the required unit is actually known.
// / Converting here as well produced a fifteen hour watch timeout & a ten million second connect timeout.
function verifyGlobals() {
  // / Set global variables to be used through the entire application.
  global $URL, $URLEcho, $Date, $Time, $SesHash, $SesHash2, $SesHash3, $SesHash4, $CoreLoaded, $ConvertDir, $InstLoc, $ConvertTemp, $ConvertTempDir, $ConvertGuiCounter1, $DefaultApps, $RequiredDirs, $RequiredIndexes, $DangerousFiles, $Allowed, $ArchiveArray, $DearchiveArray, $DocumentArray, $SpreadsheetArray, $PresentationInputArray, $PresentationOutputArray, $XPSInputArray, $XPSOutputArray, $ImageArray, $MediaInputArray, $MediaOutputArray, $VideoInputArray, $VideoOutputArray, $StreamArray, $DrawingArray, $UserSVGInputArray, $SVGInputArray, $UserSVGOutputArray, $SVGOutputArray, $ModelArray, $SubtitleInputArray, $SubtitleOutputArray, $PDFWorkArr, $ConvertLoc, $DirSep, $SupportedConversionTypes, $Lol, $Lolol, $Append, $PathExt, $ConsolidatedLogFileName, $ConsolidatedLogFile, $Alert, $Alert1, $Alert2, $Alert3, $FCPlural, $FCPlural1, $FCPlural2, $FCPlural3, $UserClamLogFile, $UserClamLogFileName, $UserScanCoreLogFile, $UserScanCoreFileName, $SpinnerStyle, $SpinnerColor, $FullURL, $ServerRootDir, $StopCounter, $SleepTimer, $CurrentUser, $File, $HeaderDisplayed, $UIDisplayed, $FooterDisplayed, $LanguageStringsLoaded, $GUIDisplayed, $GUIDirection, $SupportedFormatCount, $GUIAlignment, $GreenButtonCode, $BlueButtonCode, $RedButtonCode, $PurpleButtonCode, $OrangeButtonCode, $DarkButtonCode, $DefaultButtonCode, $UserArchiveArray, $UserDearchiveArray, $UserDocumentArray, $UserSpreadsheetArray, $UserXPSInputArray, $UserXPSOutputArray, $UserPresentationInputArray, $UserPresentationOutputArray, $UserImageArray, $UserMediaInputArray, $UserMediaOutputArray, $UserVideoInputArray, $UserVideoOutputArray, $UserStreamArray, $UserDrawingArray, $UserModelArray, $UserSubtitleInputArray, $UserSubtitleOutputArray, $UserPDFWorkArr, $RetryCount, $DocumentEngineSleepTimer, $HomeLoc, $ProprietaryLoc, $RequiredCleanupFolders, $PathToUnoconv, $UsePatchedDocumentEngine, $StreamTemp, $StreamWatchTimeout, $StreamConnectionTimeout, $AllowStreamOverHTTP, $StreamInspectionLayers, $StreamInspectionFilesPerLayer, $DefaultStreamInspectionForfeitAction, $MaxStreamInspectionFileSize, $WaitForStream, $StreamPID, $StreamOutputPath, $LogDir, $StreamOutputArray, $ScadTemp, $AllowSCADIncludeResolution, $SCADConversionTimeout, $UserSCADArray, $SCADArray, $SCADOutputArray, $ProtectedRootDirs, $ResourcesDir, $BootloadersDir, $AllowBootableIsoImage, $UserBootableIsoArray, $BootableIsoArray, $MinimumCalibreVersion, $UserEbookInputArray, $UserEbookOutputArray, $EbookInputArray, $EbookOutputArray, $EnableMemoryProtection, $ManagerSocketDir, $ManagerSocketTimeout, $ManagerMessageBatchSize, $ManagerMessageSkew, $StartupKeyWindow, $ResourceAwarenessActive, $CoreManagerVersion, $RequiredCoreManagerVersion, $EnableResourceAwareness, $RequireResourceAwareness, $CoreManagerSubprocessPollInterval, $ResourcePollInterval, $WorkerReapInterval, $WorkerStaleGracePeriod, $TotalResourceBudget, $ReserveResourcePercentage, $MaxConcurrentWorkers, $MaxExpectedRuntime, $MaxRuntimeExtensions, $DefaultConversionCost, $DefaultExpectedRuntime, $PrimaryConvertLoc, $AdditionalConvertLocs, $StorageCleanupInterval, $EffectiveConversionLimits, $EnablePerConversionLimits, $MaximumPerConversionResources, $DefaultPerConversionResources, $MinimumPerConversionResources, $AllowUnprivilegedNamespaces, $PipelineManagerActive, $PipelinesAreEnumerated, $Pipelines, $PipelineCount;
  // / Application related variables.
  $GlobalsAreVerified = $sanitizeGlobalCheck = $sanitizeGlobalCheckA = $sanitizeGlobalCheckB = $sanitizeGlobalCheckC = $sanitizeGlobalCheckD = $sanitizeGlobalCheckE = FALSE;
  $SleepTimer = 0;
  $StopCounter = $RetryCount;
  // / Convinience variables.
  $DirSep = DIRECTORY_SEPARATOR;
  $Lol = PHP_EOL;
  $Lolol = PHP_EOL.PHP_EOL;
  $Append = FILE_APPEND;
  $PathExt = PATHINFO_EXTENSION;
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
  // / Pipeline component variables.
  // / Stated here rather than in the main logic, so that a CLI path which never reaches the
  // / Pipeline Manager still finds them defined instead of raising a notice on first read.
  // / The main logic overwrites all four the moment the component has been verified.
  $PipelineManagerActive = $PipelinesAreEnumerated = FALSE;
  $Pipelines = array();
  $PipelineCount = 0;
  // / URL related variables.
  $subDir = sanitizeString(str_replace($ServerRootDir.$DirSep, '', $InstLoc), FALSE);
  $partURL = sanitizeString($URL.'/'.$subDir, FALSE);
  $FullURL = 'http'.$URLEcho.'://'.$partURL;
  // / Directory related variables.
  $webRoot = $DirSep.'var'.$DirSep.'www';
  $ResourcesDir = $InstLoc.$DirSep.'Resources';
  $BootloadersDir = $ResourcesDir.$DirSep.'Bootloaders';
  // / Core Manager related variables.
  // / Capture the location config.php declared BEFORE anything narrows it.
  // / $ConvertLoc becomes the location THIS SESSION uses. $PrimaryConvertLoc stays the
  // / configured value & is what the pool is enumerated against.
  // / The listener fills this on a budget reply. Empty means the configured maxima apply.
  $EffectiveConversionLimits = array();
  $PrimaryConvertLoc = rtrim((string)$ConvertLoc, $DirSep);
  // / The socket directory is pinned to the configured location & never moves with a
  // / session, or a worker & a manager would look for each other in different places.
  // / It lives in the data location & is never inside the web root.
  $ManagerSocketDir = $PrimaryConvertLoc.$DirSep.'Sockets';
  $ManagerSocketTimeout = 2;
  $ManagerMessageBatchSize = 32;
  $ManagerMessageSkew = 30;
  $StartupKeyWindow = 10;
  // / Ask the listener which location this session uses. Sticky for the life of the
  // / session & decided once, when the session is first seen. With no listener this
  // / returns the configured location & the core behaves exactly as it always has.
  $ConvertLoc = requestConvertLoc($SesHash, $SesHash2);
  list ($convertDir0, $sanitizeGlobalCheckA) = sanitize($ConvertLoc.$DirSep.$SesHash, FALSE);
  list ($ConvertDir, $sanitizeGlobalCheckB) = sanitize($convertDir0.$DirSep.$SesHash2.$DirSep, FALSE);
  list ($ConvertTemp, $sanitizeGlobalCheckC) = sanitize($InstLoc.$DirSep.'DATA', FALSE);
  list ($convertTempDir0, $sanitizeGlobalCheckD) = sanitize($ConvertTemp.$DirSep.$SesHash, FALSE);
  list ($ConvertTempDir, $sanitizeGlobalCheckE) = sanitize($convertTempDir0.$DirSep.$SesHash2.$DirSep, FALSE);
  // / Check that all required directory variables were sanitized successfully.
  if (!$sanitizeGlobalCheckA or !$sanitizeGlobalCheckB or !$sanitizeGlobalCheckC or !$sanitizeGlobalCheckD or !$sanitizeGlobalCheckE) $sanitizeGlobalCheck = FALSE;
  else $sanitizeGlobalCheck = TRUE;
  $StreamTemp = $ConvertDir.'StreamTemp';
  $ScadTemp = $ConvertDir.'ScadTemp';
  $RequiredDirs = array($HomeLoc, $convertDir0, $ConvertDir, $ConvertTemp, $convertTempDir0, $ConvertTempDir, $StreamTemp, $ScadTemp, $LogDir);
  $RequiredIndexes = array($ConvertTemp, $convertTempDir0, $ConvertTempDir);
  // / Create a list of directories that will be emptied & remove if found.
  // / These folders are artifacts specifically from previous versions of HRConvert2 that are no longer required.
  $RequiredCleanupFolders = array($webRoot.$DirSep.'.cache', $webRoot.$DirSep.'.config', $InstLoc.$DirSep.'Logs', $InstLoc.$DirSep.'.cache', $InstLoc.$DirSep.'.config', $ProprietaryLoc.$DirSep.'.cache', $ProprietaryLoc.$DirSep.'.config', $InstLoc.$DirSep.'.github'.$DirSep.'workflows', $InstLoc.$DirSep.'.github', $InstLoc.$DirSep.'.git');
  $PathToUnoconv = $InstLoc.$DirSep.'Resources'.$DirSep.'Unoconv'.$DirSep.'unoconv';
  if (!$UsePatchedDocumentEngine) $PathToUnoconv = $DirSep.'usr'.$DirSep.'bin'.$DirSep.'unoconv';
  // / A/V related variables.
  $UserClamLogFileName = 'User_ClamScan_Virus_Scan_Report.txt';
  $UserClamLogFile = $ConvertDir.$UserClamLogFileName;
  $UserScanCoreFileName = 'User_ScanCore_Virus_Scan_Report.txt';
  $UserScanCoreLogFile = $ConvertDir.$UserScanCoreFileName;
  $ConsolidatedLogFileName = 'User_Consolidated_Virus_Scan_Report.txt';
  $ConsolidatedLogFile = $ConvertTempDir.$ConsolidatedLogFileName;
  // / Format related variables.
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
  if (in_array('Image', $SupportedConversionTypes)) $ImageArray = array_map('strtolower', $UserImageArray);
  if (in_array('Audio', $SupportedConversionTypes)) $MediaInputArray = array_map('strtolower', $UserMediaInputArray);
  if (in_array('Audio', $SupportedConversionTypes)) $MediaOutputArray = array_map('strtolower', $UserMediaOutputArray);
  if (in_array('Video', $SupportedConversionTypes)) $VideoInputArray = array_map('strtolower', $UserVideoInputArray);
  if (in_array('Video', $SupportedConversionTypes)) $VideoOutputArray = array_map('strtolower', $UserVideoOutputArray);
  if (in_array('Stream', $SupportedConversionTypes) && in_array('Audio', $SupportedConversionTypes) && in_array('Video', $SupportedConversionTypes)) $StreamArray = array_map('strtolower', $UserStreamArray);
  if (in_array('Stream', $SupportedConversionTypes) && in_array('Audio', $SupportedConversionTypes) && in_array('Video', $SupportedConversionTypes)) $StreamOutputArray = array_map('strtolower', array_merge($UserMediaOutputArray, $UserVideoOutputArray));
  if (in_array('Drawing', $SupportedConversionTypes)) $DrawingArray = array_map('strtolower', $UserDrawingArray);
  if (in_array('SVG', $SupportedConversionTypes)) $SVGInputArray = array_map('strtolower', $UserSVGInputArray);
  if (in_array('SVG', $SupportedConversionTypes)) $SVGOutputArray = array_map('strtolower', $UserSVGOutputArray);
  if (in_array('Model', $SupportedConversionTypes)) $ModelArray = array_map('strtolower', $UserModelArray);
  if (in_array('Scad', $SupportedConversionTypes)) $SCADArray = array_map('strtolower', $UserSCADArray);
  if (in_array('Scad', $SupportedConversionTypes)) $SCADOutputArray = array_map('strtolower', array_diff($SCADArray, array('scad')));
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
    $SVGOutputArray, $ModelArray, $SubtitleInputArray, $SubtitleOutputArray,
    $PDFWorkArr, $XPSInputArray, $XPSOutputArray, $SCADArray, $BootableIsoArray,
    $EbookInputArray, $EbookOutputArray);
  $Allowed = array_map('strtolower', array_unique(array_merge(...$allArrays)));
  $SupportedFormatCount = count($Allowed);
  // / Check that all sanitization checks passed.
  if ($sanitizeGlobalCheck) $GlobalsAreVerified = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $convertDir0, $convertTempDir0, $subDir, $partURL, $allArrays, $webRoot, $sanitizeGlobalCheck, $sanitizeGlobalCheckA, $sanitizeGlobalCheckB, $sanitizeGlobalCheckC, $sanitizeGlobalCheckD, $sanitizeGlobalCheckE);
  return array($GlobalsAreVerified); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to confirm the installed FFMPEG meets the minimum version required.
// / Accepts the minimum version as major.minor.
// / Returns the absolute path of the binary that was verified, or FALSE.
// / A path is returned ONLY when the binary was found & its version satisfies the minimum,
// / so a caller holding a path may use it without checking anything else.
// / The binary is located rather than assumed, & the located binary is the one whose
// / version is read, so the version verified is provably the version that will run.
// / TWO different minimums are enforced against this one binary & the caller supplies the
// / one it needs. An ordinary audio or video conversion reads a local file. A stream
// / conversion fetches remote content & needs a build carrying the protocol handling fixes,
// / so it passes a higher minimum than the others do.
// / A git build reports a hash rather than a version & is refused, because an unknown build
// / cannot be cleared against a minimum.
function verifyFFMPEGVersion($MinimumVersion) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $FFMPEGBinary = FALSE;
  $locatedBinary = $detectedVersion = '';
  $versionOutput = $versionMatches = $minimumParts = array();
  $versionExitCode = 1;
  $detectedMajor = $detectedMinor = $minimumMajor = $minimumMinor = 0;
  $locatedBinary = locateDependency('ffmpeg');
  if ($locatedBinary !== '') {
    exec(escapeshellarg($locatedBinary).' -version 2>&1', $versionOutput, $versionExitCode);
    if ($versionExitCode === 0 && !empty($versionOutput)) {
      // / Anchor on the product name. A git build reports a hash where the version belongs
      // / & will not match this pattern, which is the correct outcome.
      if (preg_match('/ffmpeg version\s+n?(\d+)\.(\d+)/i', implode(' ', $versionOutput), $versionMatches)) {
        $detectedMajor = (int)$versionMatches[1];
        $detectedMinor = (int)$versionMatches[2];
        $detectedVersion = $detectedMajor.'.'.$detectedMinor;
        $minimumParts = explode('.', $MinimumVersion);
        $minimumMajor = (int)($minimumParts[0] ?? 0);
        $minimumMinor = (int)($minimumParts[1] ?? 0);
        // / Compare numerically, never as strings. A string comparison ranks 6.1 below 5.9.
        if ($detectedMajor > $minimumMajor) $FFMPEGBinary = $locatedBinary;
        elseif ($detectedMajor === $minimumMajor && $detectedMinor >= $minimumMinor) $FFMPEGBinary = $locatedBinary; } } }
  if ($Verbose) logEntry('FFMPEG Version Check: '.($FFMPEGBinary === FALSE ? 'FAILED' : 'PASSED').', Detected: '.($detectedVersion === '' ? 'NONE' : $detectedVersion).', Required: '.$MinimumVersion.' or later'.($FFMPEGBinary === FALSE ? '' : ', Using: '.$FFMPEGBinary).'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $locatedBinary, $detectedVersion, $versionOutput, $versionMatches, $minimumParts, $versionExitCode, $detectedMajor, $detectedMinor, $minimumMajor, $minimumMinor, $MinimumVersion);
  return $FFMPEGBinary; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to confirm the installed LibreOffice meets a minimum version.
// / The minimum arrives as an argument so different operations can require different builds.
// / LibreOffice reports its version as "LibreOffice 7.4.7.2 40(Build:2)" on standard output.
// / LibreOffice changed versioning schemes in 2024, moving from 7.6 directly to 24.2.
// / The new scheme is year.month, so a major of 24 or higher is NEWER than a major of 7.
// / Comparing numerically rather than as strings is what makes that transition work correctly.
// / Some distributions ship only the soffice binary, so that name is tried as a fallback.
// / A build that reports no parseable version is refused, because an unknown build cannot be cleared.
// / LibreOffice requires a writable HOME directory & will fail to start without one.
// / The core sets HOME to the configured home location during verifyGlobals().
function verifyLibreOfficeVersion($MinimumVersion) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $LibreOfficeVersionIsValid = FALSE;
  $versionOutput = $versionMatches = $minimumParts = array();
  $versionExitCode = 1;
  $detectedVersion = '';
  $detectedMajor = $detectedMinor = $minimumMajor = $minimumMinor = 0;
  // / Try the primary binary name first.
  exec('libreoffice --version 2>&1', $versionOutput, $versionExitCode);
  // / Some distributions ship only soffice, so try that name when the first attempt fails.
  if ($versionExitCode !== 0) {
    $versionOutput = array();
    exec('soffice --version 2>&1', $versionOutput, $versionExitCode); }
  if ($versionExitCode === 0 && !empty($versionOutput)) {
    // / Match a major.minor pair immediately following the product name.
    // / Anchoring on the name prevents a match against the build number later in the banner.
    if (preg_match('/LibreOffice\s+(\d+)\.(\d+)/i', implode(' ', $versionOutput), $versionMatches)) {
      $detectedMajor = (int)$versionMatches[1];
      $detectedMinor = (int)$versionMatches[2];
      $detectedVersion = $detectedMajor.'.'.$detectedMinor;
      // / Split the supplied minimum into the same two parts.
      $minimumParts = explode('.', $MinimumVersion);
      $minimumMajor = (int)($minimumParts[0] ?? 0);
      $minimumMinor = (int)($minimumParts[1] ?? 0);
      // / Compare numerically, never as strings.
      // / A string comparison would rank version 24.2 below version 7.6.
      if ($detectedMajor > $minimumMajor) $LibreOfficeVersionIsValid = TRUE;
      elseif ($detectedMajor === $minimumMajor && $detectedMinor >= $minimumMinor) $LibreOfficeVersionIsValid = TRUE; } }
  if ($Verbose) logEntry('LibreOffice Version Check: '.($LibreOfficeVersionIsValid ? 'PASSED' : 'FAILED').', Detected: '.($detectedVersion === '' ? 'NONE' : $detectedVersion).', Required: '.$MinimumVersion.' or later.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $versionOutput, $versionMatches, $versionExitCode, $detectedVersion, $minimumParts, $detectedMajor, $detectedMinor, $minimumMajor, $minimumMinor, $MinimumVersion);
  return $LibreOfficeVersionIsValid; }
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
  global $ConvertLoc, $EnableMemoryProtection, $DirSep;
  $ProfileContents = '';
  $ProfileContents = '# Written by HRConvert2. Do not edit by hand.'.PHP_EOL
    .'# HRCONVERT2-POLICY-MARKER'.PHP_EOL
    .'# Permits a confined OpenSCAD to read & write the locations HRConvert2 binds for it.'.PHP_EOL
    .'# This file is a local include & is only consulted by an existing OpenSCAD profile.'.PHP_EOL
    .PHP_EOL
    .'  owner /tmp/** rwk,'.PHP_EOL
    .'  owner '.rtrim((string)$ConvertLoc, $DirSep).'/** rwk,'.PHP_EOL
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
// / The status is 'ok', 'installed', 'repaired', 'missing', 'foreign' or 'refused'.
// / A file this application wrote carries a marker. A file WITHOUT that marker belongs to
// / the distribution or to the administrator & is NEVER overwritten without a backup, &
// / never at all outside a repair. Somebody chose that file deliberately.
// / A write is only attempted while running as root. Every other context reports & moves on.
function verifyPolicyFile($policyName, $policyPath, $policyContents, $mayRepair) {
  // / Set variables.
  global $RunningAsRoot, $Verbose, $EnableMemoryProtection;
  $PolicyIsValid = FALSE;
  $PolicyStatus = 'missing';
  $existingContents = $policyDirectory = $backupPath = '';
  $bytesWritten = 0;
  $fileExists = $carriesMarker = $contentsMatch = FALSE;
  $policyDirectory = dirname($policyPath);
  $fileExists = file_exists($policyPath);
  if ($fileExists) {
    $existingContents = (string)@file_get_contents($policyPath);
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
    $PolicyStatus = ($fileExists ? ($carriesMarker ? 'foreign' : 'foreign') : 'missing');
    if ($fileExists && $carriesMarker) $PolicyStatus = 'outdated'; }
  else {
    if (!is_dir($policyDirectory)) @mkdir($policyDirectory, 0755, TRUE);
    if (!is_dir($policyDirectory)) warningEntry('The directory '.$policyDirectory.' does not exist & could not be created, so the '.$policyName.' policy was not written.');
    else {
      // / Never replace a file without keeping the original. An administrator who tuned a
      // / policy by hand must be able to put it back.
      if ($fileExists && !$carriesMarker) {
        $backupPath = $policyPath.'.hrconvert2-backup-'.date('Y-m-d-His');
        if (@copy($policyPath, $backupPath)) warningEntry('The existing '.$policyName.' policy was preserved at '.$backupPath.' before being replaced.');
        else warningEntry('The existing '.$policyName.' policy at '.$policyPath.' could not be backed up. It was left alone.'); }
      if ($fileExists && !$carriesMarker && $backupPath === '') $PolicyStatus = 'refused';
      else {
        $bytesWritten = @file_put_contents($policyPath, $policyContents);
        if ($bytesWritten !== strlen($policyContents)) warningEntry('The '.$policyName.' policy could not be written to '.$policyPath.'.');
        else {
          @chmod($policyPath, 0644);
          $PolicyIsValid = TRUE;
          $PolicyStatus = ($fileExists ? 'repaired' : 'installed');
          logEntry('The '.$policyName.' policy was '.$PolicyStatus.' at '.$policyPath.'.'); } } } }
  if ($Verbose) logEntry('Policy Check: '.$policyName.', Path: '.$policyPath.', Status: '.$PolicyStatus.'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $existingContents, $policyDirectory, $backupPath, $bytesWritten, $fileExists, $carriesMarker, $contentsMatch, $policyName, $policyPath, $policyContents, $mayRepair);
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
  $PolicyIsValid = FALSE;
  $PolicyStatus = 'unchecked';
  $policyPath = $configDirectory = '';
  if ($cachedValid !== NULL) {
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
    $cachedStatus = $PolicyStatus; }
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
  $PolicyIsValid = FALSE;
  $PolicyStatus = 'unchecked';
  $confiningProfile = $overridePath = '';
  $candidateProfiles = array('/etc/apparmor.d/usr.bin.openscad', '/etc/apparmor.d/openscad');
  $candidateProfile = '';
  if ($cachedValid !== NULL) {
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
    $cachedStatus = $PolicyStatus; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $confiningProfile, $overridePath, $candidateProfiles, $candidateProfile, $mayRepair);
  return array($PolicyIsValid, $PolicyStatus); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to supply the contents of the DATA directory protection file.
// / Accepts no arguments.
// / Returns the complete file contents, carrying the marker that identifies it as ours.
// /
// / $ConvertTempDir IS INSIDE THE WEB ROOT & HOLDS USER SUPPLIED BYTES.
// / $ConvertTemp is $InstLoc/DATA & every file a user uploads or produces is copied there
// / by verifyFile() so a browser can fetch it by URL. That makes the web server hand out
// / attacker chosen content from this application's own origin, & for any format a browser
// / treats as ACTIVE that content is code rather than data.
// / An SVG is the clear case. It is a document, not a picture; it is served as
// / image/svg+xml & a <script> element inside it executes.
// /
// / Content-Disposition is what closes this. The file is handed over as a download & is
// / never rendered as a document, so nothing inside it runs. The CSP is a second &
// / independent stop for the same thing, kept because one directive is one point of
// / failure & these two fail in different ways.
// /
// / X-Content-Type-Options DOES NOT CLOSE THIS & IS NOT WHY THIS WORKS.
// / An SVG here is served with a correct image/svg+xml type, so there is nothing to sniff
// / & nothing for nosniff to prevent. It is set because it does close the neighbouring case
// / of a file whose type is ambiguous. It must never be reported as the control that
// / stopped the script, because it did not.
function dataProtectionContents() {
  // / Set variables.
  global $EnableMemoryProtection;
  $ProtectionContents = '';
  $ProtectionContents = '# HRCONVERT2-POLICY-MARKER'."\n"
    .'# This file is written & maintained by HRConvert2. Do not edit it by hand.'."\n"
    .'# Run the -fp argument as root to reinstall or repair it.'."\n"
    .'#'."\n"
    .'# THIS TREE HOLDS USER SUPPLIED BYTES & IS SERVED DIRECTLY BY THE WEB SERVER.'."\n"
    .'# Content-Disposition hands every file over as a download so nothing inside it is'."\n"
    .'# ever rendered as a document. The CSP stops script execution independently, in case'."\n"
    .'# the disposition is stripped by a proxy or overridden further up the configuration.'."\n"
    .'#'."\n"
    .'# THIS FILE DOES NOTHING UNLESS AllowOverride IS ENABLED FOR THIS DIRECTORY.'."\n"
    .'# Debian, Ubuntu & the standard php:*-apache container all ship AllowOverride None'."\n"
    .'# for /var/www, & an ignored .htaccess produces no error, no log line & no warning of'."\n"
    .'# any kind. Prefer a <Directory> block in the server configuration, which the -fp'."\n"
    .'# argument writes & activates for you, & verify with -fp rather than assuming.'."\n"
    .'# configuration, & verify with the -fp argument rather than assuming.'."\n"
    .'# See Documentation/ABOUT_DATA_DIRECTORY_PROTECTION.txt.'."\n"
    .'#'."\n"
    .'# EVERY DIRECTIVE HERE IS LEGAL UNDER AllowOverride FileInfo & NOTHING ELSE IS.'."\n"
    .'# A directive an override level does not permit is not ignored, it is a hard 500 for'."\n"
    .'# this whole tree, which takes out every download & every share link on the server.'."\n"
    .'# An <IfModule> guard does NOT prevent that; it tests whether a module is loaded, not'."\n"
    .'# whether the directive is permitted, so a guarded php_flag still returns 500 under'."\n"
    .'# AllowOverride FileInfo. Options & php_flag therefore live in the server'."\n"
    .'# configuration only. Do not add them here.'."\n"
    ."\n"
    .'<IfModule mod_headers.c>'."\n"
    .'  # index.html is this application\'s own document root protection page & is the one'."\n"
    .'  # file here that is meant to render. It cannot be user supplied, because index.html'."\n"
    .'  # is a literal entry in $DangerousFiles & the core refuses to stage a file by that'."\n"
    .'  # name, so excluding it opens nothing.'."\n"
    .'  <FilesMatch "^(?!index\.html$).*$">'."\n"
    .'    Header always set Content-Disposition "attachment"'."\n"
    .'    Header always set X-Content-Type-Options "nosniff"'."\n"
    .'    Header always set Content-Security-Policy "default-src \'none\'; sandbox"'."\n"
    .'    Header always set Referrer-Policy "no-referrer"'."\n"
    .'  </FilesMatch>'."\n"
    .'</IfModule>'."\n"
    ."\n"
    .'# Nothing in this tree is ever a program. The core already refuses to stage a'."\n"
    .'# dangerous extension, so this is a second independent statement of the same rule.'."\n"
    .'# RemoveHandler is used rather than php_flag because php_flag needs AllowOverride'."\n"
    .'# Options & this file must never be the reason a server stops serving.'."\n"
    .'RemoveHandler .php .phtml .phps .php3 .php4 .php5 .php7 .php8 .phar .cgi .pl .py .sh'."\n";
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection);
  return $ProtectionContents; }
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
// / A function to fetch one URL & return its response headers.
// / Accepts the URL & a timeout in seconds, in that order.
// / Returns a reachability boolean & the raw response headers, in that order.
// / curl first, then PHP streams, then a refusal. Neither mechanism is guaranteed present;
// / a minimal container often has no curl, & allow_url_fopen is commonly disabled.
// / This is a request THIS SERVER makes TO ITSELF & is not a third party connection.
function fetchOwnResponseHeaders($requestURL, $timeoutSeconds) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RequestSucceeded = FALSE;
  $ResponseHeaders = '';
  $curlBinary = $curlCommand = '';
  $curlOutput = array();
  $curlExitCode = 1;
  $streamHeaders = array();
  $curlBinary = locateDependency('curl');
  if ($curlBinary !== '') {
    // / -s is quiet, -I is headers only, -L follows a redirect to where the file really is,
    // / & --max-time bounds a server that accepts the connection & then never answers.
    $curlCommand = escapeshellarg($curlBinary).' -s -I -L --max-time '.(int)$timeoutSeconds.' '.escapeshellarg($requestURL).' 2>/dev/null';
    exec($curlCommand, $curlOutput, $curlExitCode);
    if ($curlExitCode === 0 && !empty($curlOutput)) {
      $RequestSucceeded = TRUE;
      $ResponseHeaders = implode("\n", $curlOutput); } }
  // / No curl, or curl could not reach it. Try the interpreter's own client before giving up.
  if (!$RequestSucceeded && function_exists('get_headers') && (bool)ini_get('allow_url_fopen')) {
    @ini_set('default_socket_timeout', (int)$timeoutSeconds);
    $streamHeaders = @get_headers($requestURL, FALSE);
    if (is_array($streamHeaders) && !empty($streamHeaders)) {
      $RequestSucceeded = TRUE;
      $ResponseHeaders = implode("\n", $streamHeaders); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $curlBinary, $curlCommand, $curlOutput, $curlExitCode, $streamHeaders, $requestURL, $timeoutSeconds);
  return array($RequestSucceeded, $ResponseHeaders); }
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
function verifyDataExposure() {
  // / Set variables.
  global $ConvertTemp, $FullURL, $DirSep, $Verbose, $EnableMemoryProtection;
  $DataIsProtected = FALSE;
  $ExposureStatus = 'unverified';
  $ExposureDetail = '';
  $canaryName = $canaryPath = $canaryURL = $responseHeaders = '';
  $requestSucceeded = $carriesDisposition = $carriesSandbox = $canaryWasWritten = FALSE;
  $bytesWritten = 0;
  if ((string)$ConvertTemp === '' or (string)$FullURL === '') {
    $ExposureStatus = 'unverified';
    $ExposureDetail = 'The DATA location or the server URL is not set, so nothing could be tested.';
    warningEntry('The DATA directory exposure check could not run because the DATA location or the server URL is not set.'); }
  else {
    if (!is_dir($ConvertTemp)) @mkdir($ConvertTemp, 0755, TRUE);
    $canaryName = 'hrc2-protection-canary-'.substr(bin2hex(random_bytes(8)), 0, 16).'.svg';
    $canaryPath = $ConvertTemp.$DirSep.$canaryName;
    $canaryURL = rtrim((string)$FullURL, '/').'/DATA/'.$canaryName;
    // / The probe carries a script element so it has the same shape as a real upload. The
    // / script is empty, so a browser that did run it would do nothing.
    $bytesWritten = @file_put_contents($canaryPath, '<svg xmlns="http://www.w3.org/2000/svg"><script>/* inert probe */</script></svg>');
    $canaryWasWritten = ($bytesWritten !== FALSE && $bytesWritten > 0);
    if (!$canaryWasWritten) {
      $ExposureStatus = 'unverified';
      $ExposureDetail = 'A probe file could not be written into '.$ConvertTemp.', so nothing could be tested.';
      warningEntry('The DATA directory exposure check could not write its probe into '.$ConvertTemp.'.'); }
    else {
      @chmod($canaryPath, 0644);
      list ($requestSucceeded, $responseHeaders) = fetchOwnResponseHeaders($canaryURL, 5);
      // / Remove the probe whatever happened. It must never outlive the check that made it.
      @unlink($canaryPath);
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
  purgeSensitiveMemory($EnableMemoryProtection, $canaryName, $canaryPath, $canaryURL, $responseHeaders, $requestSucceeded, $carriesDisposition, $carriesSandbox, $canaryWasWritten, $bytesWritten);
  return array($DataIsProtected, $ExposureStatus, $ExposureDetail); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report whether systemd is actually running this host.
// / Accepts no arguments.
// / Returns a usability boolean & the reason, in that order.
// /
// / The binary being present proves nothing. This is the mistake this function exists for.
// / An earlier release decided systemd was available because loginctl was on the PATH.
// / Every official PHP container image ships the systemd client tools & does not run
// / systemd, so every check passed, a unit file was written, lingering was attempted, & the
// / listener was then handed to a service manager that was never going to answer. The
// / error that finally surfaced came from systemctl itself, which is the proof the binary
// / was there all along.
// /
// / /run/systemd/system EXISTS ONLY WHEN SYSTEMD IS PID 1.
// / This is the test the systemd documentation gives for exactly this question & is what
// / sd_booted does. It is a directory test, it costs nothing, & it is correct inside a
// / container, inside a chroot, & on a host running any other init.
function systemdIsUsable() {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  static $cachedUsable = NULL;
  static $cachedReason = '';
  $SystemdIsUsable = FALSE;
  $SystemdReason = '';
  if ($cachedUsable !== NULL) {
    $SystemdIsUsable = $cachedUsable;
    $SystemdReason = $cachedReason; }
  else {
    if (!is_dir('/run/systemd/system')) $SystemdReason = 'systemd is not running this host, whatever tools are installed.';
    else if (locateDependency('systemctl') === '') $SystemdReason = 'systemd is running but systemctl is not installed.';
    else {
      $SystemdIsUsable = TRUE;
      $SystemdReason = 'systemd is running & systemctl is available.'; }
    if ($Verbose) logEntry('Systemd Check: '.($SystemdIsUsable ? 'AVAILABLE' : 'UNAVAILABLE').', '.$SystemdReason);
    $cachedUsable = $SystemdIsUsable;
    $cachedReason = $SystemdReason; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / Neither value is purged, because both are return values.
  purgeSensitiveMemory($EnableMemoryProtection);
  return array($SystemdIsUsable, $SystemdReason); }
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
  // / This is the setting that refuses the namespace on ubuntu 23.10 & later.
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
  $PolicyIsValid = FALSE;
  $PolicyStatus = 'unchecked';
  $restrictionPath = '/proc/sys/kernel/apparmor_restrict_unprivileged_userns';
  $profilePath = '/etc/apparmor.d/hrconvert2-bwrap';
  $restrictionIsActive = $profileIsLoaded = FALSE;
  $profileLoadStatus = $distributionProfile = $localIncludePath = '';
  if ($cachedValid !== NULL) {
    $PolicyIsValid = $cachedValid;
    $PolicyStatus = $cachedStatus; }
  else {
    if (file_exists($restrictionPath)) $restrictionIsActive = (trim((string)@file_get_contents($restrictionPath)) === '1');
    if (!$restrictionIsActive) {
      // / Nothing is blocking a namespace, so no profile is required.
      $PolicyIsValid = TRUE;
      $PolicyStatus = 'unrestricted';
      if ($Verbose) logEntry('Policy Check: Sandbox AppArmor, Status: unrestricted, this kernel does not restrict unprivileged user namespaces.'); }
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
    $cachedStatus = $PolicyStatus; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $restrictionPath, $profilePath, $restrictionIsActive, $profileIsLoaded, $profileLoadStatus, $distributionProfile, $localIncludePath, $mayRepair);
  return array($PolicyIsValid, $PolicyStatus); }
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
// / A function to confirm the installed ImageMagick meets the minimum version required.
// / Accepts the minimum version as major.minor.
// / Returns the absolute path of the binary that was verified, or FALSE.
// / A path is returned ONLY when the binary was found & its version satisfies the minimum,
// / so a caller holding a path may use it without checking anything else.
// / The binary is located rather than assumed, & the located binary is the one whose
// / version is read, so the version verified is provably the version that will run.
// / ImageMagick v7 is required for the unified magick utility & its parameter ordering.
// / A v6 installation provides convert with different argument semantics & is refused
// / rather than accommodated, so the command built by the caller can be trusted as written.
// / A build that reports no parseable version is refused, because an unknown build cannot
// / be cleared against a minimum.
function verifyImageVersion($MinimumVersion) {
  // / Set variables.
  global $Verbose, $RunningAsRoot, $EnableMemoryProtection;
  $ImageBinary = FALSE;
  $locatedBinary = $detectedVersion = '';
  $versionOutput = $versionMatches = $minimumParts = array();
  $versionExitCode = 1;
  $detectedMajor = $detectedMinor = $minimumMajor = $minimumMinor = 0;
  $locatedBinary = locateDependency('magick');
  if ($locatedBinary !== '') {
    // / The stock Debian policy blocks every document coder this application converts.
    // / The policy is as much a dependency as the binary. A root run repairs it. Every
    // / other context reports it & carries on, because a policy is not ours to rewrite
    // / from a web request.
    verifyImageMagickPolicy($RunningAsRoot);
    exec(escapeshellarg($locatedBinary).' -version 2>&1', $versionOutput, $versionExitCode);
    if ($versionExitCode === 0 && !empty($versionOutput)) {
      // / Anchor on the product name. The banner also carries a build date & a URL.
      if (preg_match('/ImageMagick\s+(\d+)\.(\d+)/i', implode(' ', $versionOutput), $versionMatches)) {
        $detectedMajor = (int)$versionMatches[1];
        $detectedMinor = (int)$versionMatches[2];
        $detectedVersion = $detectedMajor.'.'.$detectedMinor;
        $minimumParts = explode('.', $MinimumVersion);
        $minimumMajor = (int)($minimumParts[0] ?? 0);
        $minimumMinor = (int)($minimumParts[1] ?? 0);
        // / Compare numerically, never as strings. A string comparison ranks 7.1 below 6.9.
        if ($detectedMajor > $minimumMajor) $ImageBinary = $locatedBinary;
        elseif ($detectedMajor === $minimumMajor && $detectedMinor >= $minimumMinor) $ImageBinary = $locatedBinary; } } }
  if ($Verbose) logEntry('ImageMagick Version Check: '.($ImageBinary === FALSE ? 'FAILED' : 'PASSED').', Detected: '.($detectedVersion === '' ? 'NONE' : $detectedVersion).', Required: '.$MinimumVersion.' or later'.($ImageBinary === FALSE ? '' : ', Using: '.$ImageBinary).'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $locatedBinary, $detectedVersion, $versionOutput, $versionMatches, $minimumParts, $versionExitCode, $detectedMajor, $detectedMinor, $minimumMajor, $minimumMinor, $MinimumVersion);
  return $ImageBinary; }
// / -----------------------------------------------------------------------------------

// / A function to verify both utilities a 3D model conversion depends on.
// / Accepts the minimum Assimp version & the minimum MeshLab version, in that order.
// / Returns an overall boolean, the Assimp path & the MeshLab path, in that order.
// / Each path is the absolute path of a verified binary, or FALSE.
// / The overall boolean reports whether the model SUBSYSTEM is functional, which is not
// / derivable from the two paths alone. MeshLab is only needed when the binary is the one
// / being used, so a server running PyMeshLab is fully functional without it.
// / Assimp is required by every route & MeshLab only by the mesh routes, so a caller taking
// / the scene route tests the Assimp path alone rather than the overall boolean.
// / Neither utility reports its version in the ordinary way.
// / Assimp uses a subcommand rather than a flag, & prints a banner with the version several
// / lines down, so the pattern anchors on the word Version rather than the first number.
// / meshlabserver is a Qt application & will not start without a display, even to print its
// / own version, so it runs under xvfb-run exactly as the conversion does.
// / Neither exit code is consulted, because both print a usable banner while exiting non zero.
// / MeshLab uses date style version numbers such as 2020.09, so the two parts are compared
// / numerically & a build reporting 2020.9 satisfies a minimum of 2020.09.
// / PyMeshLab is a bundled python module with no version to interrogate, so it is never
// / checked & its use is what makes a missing MeshLab binary acceptable.
function verifyModelVersions($MinimumAssimpVersion, $MinimumMeshlabVersion) {
  // / Set variables.
  global $Verbose, $UsePyMeshLab, $EnableMemoryProtection;
  $ModelsAreValid = FALSE;
  $AssimpBinary = $MeshlabBinary = FALSE;
  $locatedBinary = $detectedAssimp = $detectedMeshlab = '';
  $versionOutput = $versionMatches = $minimumParts = array();
  $versionExitCode = 1;
  $detectedMajor = $detectedMinor = $minimumMajor = $minimumMinor = 0;
  // / Assimp reports its version through the version subcommand, not through a flag.
  $locatedBinary = locateDependency('assimp');
  if ($locatedBinary !== '') {
    $versionOutput = array();
    exec(escapeshellarg($locatedBinary).' version 2>&1', $versionOutput, $versionExitCode);
    if (!empty($versionOutput)) {
      if (preg_match('/Version\s+(\d+)\.(\d+)/i', implode(' ', $versionOutput), $versionMatches)) {
        $detectedMajor = (int)$versionMatches[1];
        $detectedMinor = (int)$versionMatches[2];
        $detectedAssimp = $detectedMajor.'.'.$detectedMinor;
        $minimumParts = explode('.', $MinimumAssimpVersion);
        $minimumMajor = (int)($minimumParts[0] ?? 0);
        $minimumMinor = (int)($minimumParts[1] ?? 0);
        if ($detectedMajor > $minimumMajor) $AssimpBinary = $locatedBinary;
        elseif ($detectedMajor === $minimumMajor && $detectedMinor >= $minimumMinor) $AssimpBinary = $locatedBinary; } } }
  // / MeshLab is provided by the meshlabserver binary when PyMeshLab is not in use.
  $locatedBinary = locateDependency('meshlabserver');
  if ($locatedBinary !== '') {
    $versionOutput = array();
    exec('xvfb-run -a '.escapeshellarg($locatedBinary).' --version 2>&1', $versionOutput, $versionExitCode);
    if (!empty($versionOutput)) {
      if (preg_match('/(\d{4})\.(\d+)/', implode(' ', $versionOutput), $versionMatches)) {
        $detectedMajor = (int)$versionMatches[1];
        $detectedMinor = (int)$versionMatches[2];
        $detectedMeshlab = $detectedMajor.'.'.$versionMatches[2];
        $minimumParts = explode('.', $MinimumMeshlabVersion);
        $minimumMajor = (int)($minimumParts[0] ?? 0);
        $minimumMinor = (int)($minimumParts[1] ?? 0);
        // / A leading zero must not survive into the comparison. 2020.09 is month nine &
        // / (int)'09' is nine, so a build reporting 2020.9 satisfies it correctly.
        if ($detectedMajor > $minimumMajor) $MeshlabBinary = $locatedBinary;
        elseif ($detectedMajor === $minimumMajor && $detectedMinor >= $minimumMinor) $MeshlabBinary = $locatedBinary; } } }
  // / The subsystem is functional when Assimp works & the MeshLab requirement is satisfied
  // / either by the binary or by PyMeshLab standing in for it.
  if ($AssimpBinary !== FALSE && ($UsePyMeshLab or $MeshlabBinary !== FALSE)) $ModelsAreValid = TRUE;
  if ($Verbose) {
    logEntry('Assimp Version Check: '.($AssimpBinary === FALSE ? 'FAILED' : 'PASSED').', Detected: '.($detectedAssimp === '' ? 'NONE' : $detectedAssimp).', Required: '.$MinimumAssimpVersion.' or later'.($AssimpBinary === FALSE ? '' : ', Using: '.$AssimpBinary).'.');
    logEntry('MeshLab Version Check: '.($MeshlabBinary === FALSE ? 'FAILED' : 'PASSED').', Detected: '.($detectedMeshlab === '' ? 'NONE' : $detectedMeshlab).', Required: '.$MinimumMeshlabVersion.' or later'.($MeshlabBinary === FALSE ? '' : ', Using: '.$MeshlabBinary).($UsePyMeshLab ? ', PyMeshLab is in use & the binary is not required.' : '.')); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $locatedBinary, $detectedAssimp, $detectedMeshlab, $versionOutput, $versionMatches, $minimumParts, $versionExitCode, $detectedMajor, $detectedMinor, $minimumMajor, $minimumMinor, $MinimumAssimpVersion, $MinimumMeshlabVersion);
  return array($ModelsAreValid, $AssimpBinary, $MeshlabBinary); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to confirm the installed Dia meets the minimum version required.
// / Accepts the minimum version as major.minor.
// / Returns the absolute path of the binary that was verified, or FALSE.
// / A path is returned ONLY when the binary was found & its version satisfies the minimum,
// / so a caller holding a path may use it without checking anything else.
// / Dia reports its version as "Dia version 0.98.0" & is one of the few dependencies whose
// / major version is zero, so the comparison must not assume a non zero major.
// / Dia is a GTK application. It accepts --version without a display, but a CONVERSION may
// / still need one. If a drawing conversion fails inside a working sandbox rather than
// / being refused, a missing display is the first thing to suspect & xvfb-run is the answer.
// / A build that reports no parseable version is refused, because an unknown build cannot
// / be cleared against a minimum.
function verifyDrawingVersion($MinimumVersion) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $DrawingBinary = FALSE;
  $locatedBinary = $detectedVersion = '';
  $versionOutput = $versionMatches = $minimumParts = array();
  $versionExitCode = 1;
  $detectedMajor = $detectedMinor = $minimumMajor = $minimumMinor = 0;
  $locatedBinary = locateDependency('dia');
  if ($locatedBinary !== '') {
    exec(escapeshellarg($locatedBinary).' --version 2>&1', $versionOutput, $versionExitCode);
    if (!empty($versionOutput)) {
      // / Anchor on the product name so nothing else in the banner can be matched instead.
      if (preg_match('/Dia\s+version\s+(\d+)\.(\d+)/i', implode(' ', $versionOutput), $versionMatches)) {
        $detectedMajor = (int)$versionMatches[1];
        $detectedMinor = (int)$versionMatches[2];
        $detectedVersion = $detectedMajor.'.'.$versionMatches[2];
        $minimumParts = explode('.', $MinimumVersion);
        $minimumMajor = (int)($minimumParts[0] ?? 0);
        $minimumMinor = (int)($minimumParts[1] ?? 0);
        // / Compare numerically, never as strings. A string comparison ranks 0.98 below 0.9.
        if ($detectedMajor > $minimumMajor) $DrawingBinary = $locatedBinary;
        elseif ($detectedMajor === $minimumMajor && $detectedMinor >= $minimumMinor) $DrawingBinary = $locatedBinary; } } }
  if ($Verbose) logEntry('Dia Version Check: '.($DrawingBinary === FALSE ? 'FAILED' : 'PASSED').', Detected: '.($detectedVersion === '' ? 'NONE' : $detectedVersion).', Required: '.$MinimumVersion.' or later'.($DrawingBinary === FALSE ? '' : ', Using: '.$DrawingBinary).'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $locatedBinary, $detectedVersion, $versionOutput, $versionMatches, $minimumParts, $versionExitCode, $detectedMajor, $detectedMinor, $minimumMajor, $minimumMinor, $MinimumVersion);
  return $DrawingBinary; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify both utilities an OCR operation depends on.
// / Accepts the minimum Tesseract version & the minimum pdftotext version, in that order.
// / Returns an overall boolean, the Tesseract path & the pdftotext path, in that order.
// / Each path is the absolute path of a verified binary, or FALSE.
// / The overall boolean reports whether the OCR SUBSYSTEM is functional, which means both,
// / because the two routes through the OCR pipeline need different ones & a caller taking
// / a single route tests that route's path rather than the overall boolean.
// / This verifier stays in this file although ocrFiles() no longer does.
// / showVersionInfo() reports on it whether or not the OCR pipeline is installed.
// / Tesseract reads an image directly. pdftotext reads a PDF that already holds a text
// / layer. Neither substitutes for the other.
// / Tesseract prints a long banner listing every library it was built against, so the
// / pattern anchors on the product name to avoid matching a dependency version instead.
// / pdftotext is part of poppler-utils & reports a date style version such as 24.02.0,
// / where the major is the year & the minor is the month.
// / Neither exit code is consulted, because both print a usable banner while exiting non zero.
function verifyOCRVersions($MinimumTesseractVersion, $MinimumPdftotextVersion) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $OCRToolsAreValid = FALSE;
  $TesseractBinary = $PdftotextBinary = FALSE;
  $locatedBinary = $detectedTesseract = $detectedPdftotext = '';
  $versionOutput = $versionMatches = $minimumParts = array();
  $versionExitCode = 1;
  $detectedMajor = $detectedMinor = $minimumMajor = $minimumMinor = 0;
  // / Tesseract reads an image & produces text. It is the primary OCR engine.
  $locatedBinary = locateDependency('tesseract');
  if ($locatedBinary !== '') {
    $versionOutput = array();
    exec(escapeshellarg($locatedBinary).' --version 2>&1', $versionOutput, $versionExitCode);
    if (!empty($versionOutput)) {
      // / Anchor on the product name. The banner lists leptonica, libpng, zlib & a dozen
      // / other versions, & an unanchored pattern would match whichever came first.
      if (preg_match('/tesseract\s+(\d+)\.(\d+)/i', implode(' ', $versionOutput), $versionMatches)) {
        $detectedMajor = (int)$versionMatches[1];
        $detectedMinor = (int)$versionMatches[2];
        $detectedTesseract = $detectedMajor.'.'.$versionMatches[2];
        $minimumParts = explode('.', $MinimumTesseractVersion);
        $minimumMajor = (int)($minimumParts[0] ?? 0);
        $minimumMinor = (int)($minimumParts[1] ?? 0);
        if ($detectedMajor > $minimumMajor) $TesseractBinary = $locatedBinary;
        elseif ($detectedMajor === $minimumMajor && $detectedMinor >= $minimumMinor) $TesseractBinary = $locatedBinary; } } }
  // / pdftotext extracts an existing text layer & cannot read a scanned page.
  $locatedBinary = locateDependency('pdftotext');
  if ($locatedBinary !== '') {
    $versionOutput = array();
    exec(escapeshellarg($locatedBinary).' -v 2>&1', $versionOutput, $versionExitCode);
    if (!empty($versionOutput)) {
      if (preg_match('/pdftotext\s+version\s+(\d+)\.(\d+)/i', implode(' ', $versionOutput), $versionMatches)) {
        $detectedMajor = (int)$versionMatches[1];
        $detectedMinor = (int)$versionMatches[2];
        $detectedPdftotext = $detectedMajor.'.'.$versionMatches[2];
        $minimumParts = explode('.', $MinimumPdftotextVersion);
        $minimumMajor = (int)($minimumParts[0] ?? 0);
        $minimumMinor = (int)($minimumParts[1] ?? 0);
        // / The major is a YEAR & the minor is a MONTH, so a leading zero must not survive
        // / into the comparison. 24.02 is month two & (int)'02' is two.
        if ($detectedMajor > $minimumMajor) $PdftotextBinary = $locatedBinary;
        elseif ($detectedMajor === $minimumMajor && $detectedMinor >= $minimumMinor) $PdftotextBinary = $locatedBinary; } } }
  // / Both are required for the subsystem to be considered functional, because the two
  // / routes through an OCR operation use different ones & neither substitutes for the other.
  if ($TesseractBinary !== FALSE && $PdftotextBinary !== FALSE) $OCRToolsAreValid = TRUE;
  if ($Verbose) {
    logEntry('Tesseract Version Check: '.($TesseractBinary === FALSE ? 'FAILED' : 'PASSED').', Detected: '.($detectedTesseract === '' ? 'NONE' : $detectedTesseract).', Required: '.$MinimumTesseractVersion.' or later'.($TesseractBinary === FALSE ? '' : ', Using: '.$TesseractBinary).'.');
    logEntry('Pdftotext Version Check: '.($PdftotextBinary === FALSE ? 'FAILED' : 'PASSED').', Detected: '.($detectedPdftotext === '' ? 'NONE' : $detectedPdftotext).', Required: '.$MinimumPdftotextVersion.' or later'.($PdftotextBinary === FALSE ? '' : ', Using: '.$PdftotextBinary).'.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $locatedBinary, $detectedTesseract, $detectedPdftotext, $versionOutput, $versionMatches, $minimumParts, $versionExitCode, $detectedMajor, $detectedMinor, $minimumMajor, $minimumMinor, $MinimumTesseractVersion, $MinimumPdftotextVersion);
  return array($OCRToolsAreValid, $TesseractBinary, $PdftotextBinary); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to confirm the installed OpenSCAD meets the minimum version required.
// / Accepts the minimum version as YYYY.MM.
// / Returns the absolute path of the binary that was verified, or FALSE.
// / A path is returned ONLY when the binary was found & its version satisfies the minimum,
// / so a caller holding a path may use it without checking anything else.
// / OpenSCAD writes its version banner to standard ERROR rather than standard output.
// / Stderr is redirected into the captured output & the exit code cannot be relied upon.
// / OpenSCAD uses a year & month rather than a major & minor, so the comparison is on the
// / year first & the month second, both numerically.
// / A build that reports no parseable version is refused, because an unknown build cannot
// / be cleared against a minimum.
function verifySCADVersion($MinimumVersion) {
  // / Set variables.
  global $Verbose, $RunningAsRoot, $EnableMemoryProtection;
  $SCADBinary = FALSE;
  $locatedBinary = $detectedVersion = '';
  $versionOutput = $versionMatches = $minimumParts = array();
  $versionExitCode = 1;
  $detectedYear = $detectedMonth = $minimumYear = $minimumMonth = 0;
  $locatedBinary = locateDependency('openscad');
  if ($locatedBinary !== '') {
    // / A distribution that confines OpenSCAD stops it reading the directory the sandbox
    // / binds for it, which presents as a conversion that produces nothing & says nothing.
    verifyOpenScadPolicy($RunningAsRoot);
    exec(escapeshellarg($locatedBinary).' --version 2>&1', $versionOutput, $versionExitCode);
    if (!empty($versionOutput)) {
      if (preg_match('/OpenSCAD\s+version\s+(\d{4})\.(\d{2})/i', implode(' ', $versionOutput), $versionMatches)) {
        $detectedYear = (int)$versionMatches[1];
        $detectedMonth = (int)$versionMatches[2];
        $detectedVersion = $versionMatches[1].'.'.$versionMatches[2];
        $minimumParts = explode('.', $MinimumVersion);
        $minimumYear = (int)($minimumParts[0] ?? 0);
        $minimumMonth = (int)($minimumParts[1] ?? 0);
        // / Compare numerically. A string comparison ranks 2021.10 below 2021.09.
        if ($detectedYear > $minimumYear) $SCADBinary = $locatedBinary;
        elseif ($detectedYear === $minimumYear && $detectedMonth >= $minimumMonth) $SCADBinary = $locatedBinary; } } }
  if ($Verbose) logEntry('OpenSCAD Version Check: '.($SCADBinary === FALSE ? 'FAILED' : 'PASSED').', Detected: '.($detectedVersion === '' ? 'NONE' : $detectedVersion).', Required: '.$MinimumVersion.' or later'.($SCADBinary === FALSE ? '' : ', Using: '.$SCADBinary).'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $locatedBinary, $detectedVersion, $versionOutput, $versionMatches, $minimumParts, $versionExitCode, $detectedYear, $detectedMonth, $minimumYear, $minimumMonth, $MinimumVersion);
  return $SCADBinary; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to confirm the installed Inkscape meets the minimum version required.
// / Accepts the minimum version as major.minor.
// / Returns the absolute path of the binary that was verified, or FALSE.
// / A path is returned ONLY when the binary was found & its version satisfies the minimum,
// / so a caller holding a path may use it without checking anything else.
// / The binary is located rather than assumed, & the located binary is the one whose
// / version is read, so the version verified is provably the version that will run.
// / Inkscape replaced its entire command line interface at version 1.0. The 0.92 flags such
// / as --export-png were REMOVED rather than deprecated, so a command written for the
// / current interface fails outright on an older build.
// / Inkscape writes its version banner to standard output, unlike OpenSCAD.
// / Standard error is redirected anyway, because a headless launch emits harmless warnings.
// / A build that reports no parseable version is refused, because an unknown build cannot
// / be cleared against a minimum.
function verifySVGVersion($MinimumVersion) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $SVGBinary = FALSE;
  $locatedBinary = $detectedVersion = '';
  $versionOutput = $versionMatches = $minimumParts = array();
  $versionExitCode = 1;
  $detectedMajor = $detectedMinor = $minimumMajor = $minimumMinor = 0;
  $locatedBinary = locateDependency('inkscape');
  if ($locatedBinary !== '') {
    exec(escapeshellarg($locatedBinary).' --version 2>&1', $versionOutput, $versionExitCode);
    if ($versionExitCode === 0 && !empty($versionOutput)) {
      // / Anchor on the product name. The banner ends with a commit hash & a date, & an
      // / unanchored pattern would happily match the date instead.
      if (preg_match('/Inkscape\s+(\d+)\.(\d+)/i', implode(' ', $versionOutput), $versionMatches)) {
        $detectedMajor = (int)$versionMatches[1];
        $detectedMinor = (int)$versionMatches[2];
        $detectedVersion = $detectedMajor.'.'.$detectedMinor;
        $minimumParts = explode('.', $MinimumVersion);
        $minimumMajor = (int)($minimumParts[0] ?? 0);
        $minimumMinor = (int)($minimumParts[1] ?? 0);
        // / Compare numerically, never as strings. A string comparison ranks 1.10 below 1.2.
        if ($detectedMajor > $minimumMajor) $SVGBinary = $locatedBinary;
        elseif ($detectedMajor === $minimumMajor && $detectedMinor >= $minimumMinor) $SVGBinary = $locatedBinary; } } }
  if ($Verbose) logEntry('Inkscape Version Check: '.($SVGBinary === FALSE ? 'FAILED' : 'PASSED').', Detected: '.($detectedVersion === '' ? 'NONE' : $detectedVersion).', Required: '.$MinimumVersion.' or later'.($SVGBinary === FALSE ? '' : ', Using: '.$SVGBinary).'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $locatedBinary, $detectedVersion, $versionOutput, $versionMatches, $minimumParts, $versionExitCode, $detectedMajor, $detectedMinor, $minimumMajor, $minimumMinor, $MinimumVersion);
  return $SVGBinary; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to confirm the installed Calibre meets the minimum version required.
// / Accepts the minimum version as major.minor.
// / Returns the absolute path of the binary that was verified, or FALSE.
// / A path is returned ONLY when the binary was found & its version satisfies the minimum,
// / so a caller holding a path may use it without checking anything else.
// / The utility is named ebook-convert but the PRODUCT is Calibre, & the version banner
// / reports the product rather than the utility. It looks like this.
// /   ebook-convert (calibre 6.13)
// / The pattern therefore anchors on the word calibre rather than on the binary name, which
// / also prevents a match against the 2 in ebook-convert on a build with an odd banner.
// / Calibre is a large application bundling its own Python interpreter, & the utility is a
// / wrapper script rather than a compiled binary, so it may live outside the usual
// / locations on a source installation. locateDependency() handles that.
function verifyEbookVersion($MinimumVersion) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $EbookBinary = FALSE;
  $locatedBinary = $detectedVersion = '';
  $versionOutput = $versionMatches = $minimumParts = array();
  $versionExitCode = 1;
  $detectedMajor = $detectedMinor = $minimumMajor = $minimumMinor = 0;
  $locatedBinary = locateDependency('ebook-convert');
  if ($locatedBinary !== '') {
    exec(escapeshellarg($locatedBinary).' --version 2>&1', $versionOutput, $versionExitCode);
    if (!empty($versionOutput)) {
      // / Anchor on the product name. The banner leads with the utility name, which carries
      // / a digit of its own that an unanchored pattern would happily match instead.
      if (preg_match('/calibre\s+(\d+)\.(\d+)/i', implode(' ', $versionOutput), $versionMatches)) {
        $detectedMajor = (int)$versionMatches[1];
        $detectedMinor = (int)$versionMatches[2];
        $detectedVersion = $detectedMajor.'.'.$detectedMinor;
        $minimumParts = explode('.', $MinimumVersion);
        $minimumMajor = (int)($minimumParts[0] ?? 0);
        $minimumMinor = (int)($minimumParts[1] ?? 0);
        // / Compare numerically, never as strings. A string comparison ranks 6.13 below 6.9.
        if ($detectedMajor > $minimumMajor) $EbookBinary = $locatedBinary;
        elseif ($detectedMajor === $minimumMajor && $detectedMinor >= $minimumMinor) $EbookBinary = $locatedBinary; } } }
  if ($Verbose) logEntry('Calibre Version Check: '.($EbookBinary === FALSE ? 'FAILED' : 'PASSED').', Detected: '.($detectedVersion === '' ? 'NONE' : $detectedVersion).', Required: '.$MinimumVersion.' or later'.($EbookBinary === FALSE ? '' : ', Using: '.$EbookBinary).'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $locatedBinary, $detectedVersion, $versionOutput, $versionMatches, $minimumParts, $versionExitCode, $detectedMajor, $detectedMinor, $minimumMajor, $minimumMinor, $MinimumVersion);
  return $EbookBinary; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify the ClamAV scanner.
// / Accepts the minimum acceptable version, or an empty string to use the built-in floor.
// / Returns the absolute path of a verified clamscan, or FALSE.
// /
// / A scanner that is not there must not look like a scanner that found nothing.
// / clamscan was the one dependency in this application invoked as a bare command name &
// / never verified at all. That is worse here than it would be anywhere else. Every other
// / dependency announces its own absence, because a converter that cannot run produces no
// / output file & the caller notices. A scanner announces absence as SILENCE, & silence is
// / byte for byte what a clean scan looks like; the pipeline greps for FOUND, an absent
// / binary writes no FOUND, & the core reported 'No infection detected' for a file nothing
// / had ever opened. An administrator who enabled scanning was told their uploads were
// / clean by a server with no scanner installed on it.
// / Locating & verifying the binary up front is what converts that silence into a refusal.
// /
// / The version banner is 'ClamAV 1.0.3/27222/Tue Aug 22 08:29:16 2023', so the pattern
// / anchors on the product name & takes only the first two parts. The trailing figures are
// / the signature database revision & its build date, & both carry digits that an
// / unanchored pattern would match instead of the version.
function verifyClamVersion($MinimumVersion) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $ClamBinary = FALSE;
  $locatedBinary = $detectedVersion = '';
  $versionOutput = $versionMatches = $minimumParts = array();
  $versionExitCode = 1;
  $detectedMajor = $detectedMinor = $minimumMajor = $minimumMinor = 0;
  // / A local floor the configuration cannot remove by omission. config.php is accepted at
  // / or above a minimum version, so an installation can take this core & keep a
  // / configuration file written before --Minimum Clam Version-- existed. Refusing to scan
  // / because the administrator's file predates the setting would be a worse answer than
  // / scanning against a sensible floor. Any value they DO set wins.
  $minimumClamVersion = '0.103';
  if ((string)$MinimumVersion === '') $MinimumVersion = $minimumClamVersion;
  $locatedBinary = locateDependency('clamscan');
  if ($locatedBinary !== '') {
    exec(escapeshellarg($locatedBinary).' --version 2>&1', $versionOutput, $versionExitCode);
    if (!empty($versionOutput)) {
      if (preg_match('/ClamAV\s+(\d+)\.(\d+)/i', implode(' ', $versionOutput), $versionMatches)) {
        $detectedMajor = (int)$versionMatches[1];
        $detectedMinor = (int)$versionMatches[2];
        $detectedVersion = $detectedMajor.'.'.$detectedMinor;
        $minimumParts = explode('.', $MinimumVersion);
        $minimumMajor = (int)($minimumParts[0] ?? 0);
        $minimumMinor = (int)($minimumParts[1] ?? 0);
        // / Compare numerically, never as strings. A string comparison ranks 0.103 above
        // / 1.0 & ranks 1.10 below 1.9, & ClamAV has shipped every one of those numbers.
        if ($detectedMajor > $minimumMajor) $ClamBinary = $locatedBinary;
        elseif ($detectedMajor === $minimumMajor && $detectedMinor >= $minimumMinor) $ClamBinary = $locatedBinary; } } }
  if ($Verbose) logEntry('ClamAV Version Check: '.($ClamBinary === FALSE ? 'FAILED' : 'PASSED').', Detected: '.($detectedVersion === '' ? 'NONE' : $detectedVersion).', Required: '.$MinimumVersion.' or later'.($ClamBinary === FALSE ? '' : ', Using: '.$ClamBinary).'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $locatedBinary, $detectedVersion, $versionOutput, $versionMatches, $minimumParts, $versionExitCode, $detectedMajor, $detectedMinor, $minimumMajor, $minimumMinor, $minimumClamVersion, $MinimumVersion);
  return $ClamBinary; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify every archive utility HRConvert2 depends on.
// / Accepts the minimum version of each utility, in the order they are returned.
// / Returns an overall boolean followed by one path per utility, each being the absolute
// / path of a verified binary or FALSE.
// / 7-Zip is the ONLY extractor & is therefore required for every archive INPUT format.
// / rar, zip, tar & mkisofs are creators & each is required only for its own OUTPUT format,
// / so a caller creating a zip tests the zip path rather than the overall boolean.
// / rar is EXCLUDED from the overall boolean because it is optional for input. It is NOT
// / optional for rar output, because 7-Zip cannot create rar archives at all.
// / mkisofs may be provided by cdrtools or by genisoimage & either satisfies the check.
// / 7z & rar both print their banner when invoked with NO arguments & exit non zero doing
// / so, which is why the exit code is not consulted for either of them.
function verifyArchiveVersions($Minimum7zVersion, $MinimumRarVersion, $MinimumZipVersion, $MinimumTarVersion, $MinimumMkisofsVersion) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $ArchiveToolsAreValid = FALSE;
  $SevenZipBinary = $RarBinary = $ZipBinary = $TarBinary = $MkisofsBinary = FALSE;
  $locatedBinary = $detected7z = $detectedRar = $detectedZip = $detectedTar = $detectedMkisofs = '';
  $versionOutput = $versionMatches = $minimumParts = array();
  $versionExitCode = 1;
  $detectedMajor = $detectedMinor = $minimumMajor = $minimumMinor = 0;
  // / 7-Zip. The only extractor. Every archive input format depends on it.
  $locatedBinary = locateDependency('7z');
  if ($locatedBinary !== '') {
    $versionOutput = array();
    exec(escapeshellarg($locatedBinary).' 2>&1', $versionOutput, $versionExitCode);
    if (!empty($versionOutput)) {
      if (preg_match('/7-Zip[^\d]*(\d+)\.(\d+)/i', implode(' ', $versionOutput), $versionMatches)) {
        $detectedMajor = (int)$versionMatches[1];
        $detectedMinor = (int)$versionMatches[2];
        $detected7z = $detectedMajor.'.'.$versionMatches[2];
        $minimumParts = explode('.', $Minimum7zVersion);
        $minimumMajor = (int)($minimumParts[0] ?? 0);
        $minimumMinor = (int)($minimumParts[1] ?? 0);
        if ($detectedMajor > $minimumMajor) $SevenZipBinary = $locatedBinary;
        elseif ($detectedMajor === $minimumMajor && $detectedMinor >= $minimumMinor) $SevenZipBinary = $locatedBinary; } } }
  // / rar. Optional for input, mandatory for rar output.
  $locatedBinary = locateDependency('rar');
  if ($locatedBinary !== '') {
    $versionOutput = array();
    exec(escapeshellarg($locatedBinary).' 2>&1', $versionOutput, $versionExitCode);
    if (!empty($versionOutput)) {
      if (preg_match('/RAR\s+(\d+)\.(\d+)/i', implode(' ', $versionOutput), $versionMatches)) {
        $detectedMajor = (int)$versionMatches[1];
        $detectedMinor = (int)$versionMatches[2];
        $detectedRar = $detectedMajor.'.'.$versionMatches[2];
        $minimumParts = explode('.', $MinimumRarVersion);
        $minimumMajor = (int)($minimumParts[0] ?? 0);
        $minimumMinor = (int)($minimumParts[1] ?? 0);
        if ($detectedMajor > $minimumMajor) $RarBinary = $locatedBinary;
        elseif ($detectedMajor === $minimumMajor && $detectedMinor >= $minimumMinor) $RarBinary = $locatedBinary; } } }
  // / zip.
  $locatedBinary = locateDependency('zip');
  if ($locatedBinary !== '') {
    $versionOutput = array();
    exec(escapeshellarg($locatedBinary).' -v 2>&1', $versionOutput, $versionExitCode);
    if (!empty($versionOutput)) {
      if (preg_match('/Zip\s+(\d+)\.(\d+)/i', implode(' ', $versionOutput), $versionMatches)) {
        $detectedMajor = (int)$versionMatches[1];
        $detectedMinor = (int)$versionMatches[2];
        $detectedZip = $detectedMajor.'.'.$versionMatches[2];
        $minimumParts = explode('.', $MinimumZipVersion);
        $minimumMajor = (int)($minimumParts[0] ?? 0);
        $minimumMinor = (int)($minimumParts[1] ?? 0);
        if ($detectedMajor > $minimumMajor) $ZipBinary = $locatedBinary;
        elseif ($detectedMajor === $minimumMajor && $detectedMinor >= $minimumMinor) $ZipBinary = $locatedBinary; } } }
  // / tar.
  $locatedBinary = locateDependency('tar');
  if ($locatedBinary !== '') {
    $versionOutput = array();
    exec(escapeshellarg($locatedBinary).' --version 2>&1', $versionOutput, $versionExitCode);
    if ($versionExitCode === 0 && !empty($versionOutput)) {
      if (preg_match('/tar[^\d]*(\d+)\.(\d+)/i', implode(' ', $versionOutput), $versionMatches)) {
        $detectedMajor = (int)$versionMatches[1];
        $detectedMinor = (int)$versionMatches[2];
        $detectedTar = $detectedMajor.'.'.$versionMatches[2];
        $minimumParts = explode('.', $MinimumTarVersion);
        $minimumMajor = (int)($minimumParts[0] ?? 0);
        $minimumMinor = (int)($minimumParts[1] ?? 0);
        if ($detectedMajor > $minimumMajor) $TarBinary = $locatedBinary;
        elseif ($detectedMajor === $minimumMajor && $detectedMinor >= $minimumMinor) $TarBinary = $locatedBinary; } } }
  // / mkisofs. May be cdrtools or the genisoimage fork wearing the same name.
  $locatedBinary = locateDependency('mkisofs');
  if ($locatedBinary !== '') {
    $versionOutput = array();
    exec(escapeshellarg($locatedBinary).' --version 2>&1', $versionOutput, $versionExitCode);
    if (!empty($versionOutput)) {
      if (preg_match('/(?:mkisofs|genisoimage)[^\d]*(\d+)\.(\d+)/i', implode(' ', $versionOutput), $versionMatches)) {
        $detectedMajor = (int)$versionMatches[1];
        $detectedMinor = (int)$versionMatches[2];
        $detectedMkisofs = $detectedMajor.'.'.$versionMatches[2];
        $minimumParts = explode('.', $MinimumMkisofsVersion);
        $minimumMajor = (int)($minimumParts[0] ?? 0);
        $minimumMinor = (int)($minimumParts[1] ?? 0);
        if ($detectedMajor > $minimumMajor) $MkisofsBinary = $locatedBinary;
        elseif ($detectedMajor === $minimumMajor && $detectedMinor >= $minimumMinor) $MkisofsBinary = $locatedBinary; } } }
  // / rar is deliberately excluded. 7-Zip extracts rar archives, so a server without the
  // / rar utility can still read them & only loses the ability to CREATE them.
  if ($SevenZipBinary !== FALSE && $ZipBinary !== FALSE && $TarBinary !== FALSE && $MkisofsBinary !== FALSE) $ArchiveToolsAreValid = TRUE;
  if ($Verbose) {
    logEntry('7-Zip Check: '.($SevenZipBinary === FALSE ? 'FAILED' : 'PASSED').', Detected: '.($detected7z === '' ? 'NONE' : $detected7z).', Required: '.$Minimum7zVersion.' or later'.($SevenZipBinary === FALSE ? '' : ', Using: '.$SevenZipBinary).'.');
    logEntry('Rar Check: '.($RarBinary === FALSE ? 'FAILED' : 'PASSED').', Detected: '.($detectedRar === '' ? 'NONE' : $detectedRar).', Required: '.$MinimumRarVersion.' or later'.($RarBinary === FALSE ? '' : ', Using: '.$RarBinary).'.');
    logEntry('Zip Check: '.($ZipBinary === FALSE ? 'FAILED' : 'PASSED').', Detected: '.($detectedZip === '' ? 'NONE' : $detectedZip).', Required: '.$MinimumZipVersion.' or later'.($ZipBinary === FALSE ? '' : ', Using: '.$ZipBinary).'.');
    logEntry('Tar Check: '.($TarBinary === FALSE ? 'FAILED' : 'PASSED').', Detected: '.($detectedTar === '' ? 'NONE' : $detectedTar).', Required: '.$MinimumTarVersion.' or later'.($TarBinary === FALSE ? '' : ', Using: '.$TarBinary).'.');
    logEntry('Mkisofs Check: '.($MkisofsBinary === FALSE ? 'FAILED' : 'PASSED').', Detected: '.($detectedMkisofs === '' ? 'NONE' : $detectedMkisofs).', Required: '.$MinimumMkisofsVersion.' or later'.($MkisofsBinary === FALSE ? '' : ', Using: '.$MkisofsBinary).'.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $locatedBinary, $detected7z, $detectedRar, $detectedZip, $detectedTar, $detectedMkisofs, $versionOutput, $versionMatches, $minimumParts, $versionExitCode, $detectedMajor, $detectedMinor, $minimumMajor, $minimumMinor, $Minimum7zVersion, $MinimumRarVersion, $MinimumZipVersion, $MinimumTarVersion, $MinimumMkisofsVersion);
  return array($ArchiveToolsAreValid, $SevenZipBinary, $RarBinary, $ZipBinary, $TarBinary, $MkisofsBinary); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to confirm the installed isohybrid meets the minimum version required.
// / Accepts the minimum version as major.minor.
// / Returns the absolute path of the binary that was verified, or FALSE.
// / A path is returned ONLY when the binary was found & its version satisfies the minimum.
// / isohybrid is part of syslinux-utils rather than being a package of its own.
// / It post processes a finished ISO so one image boots from optical media, from a USB
// / stick presenting an MBR, & from UEFI firmware.
// / ONLY the generic hybrid image needs it. An architecture specific UEFI image is not MBR
// / bootable & has no isolinux record for an MBR to point at, so passing one through
// / isohybrid would produce an unbootable image rather than a more compatible one.
// / A server that never builds a hybrid image does not need this utility at all, which is
// / why its absence is fatal to exactly one format rather than to bootable images generally.
// / The version banner is the least certain thing here.
// / isohybrid is a perl script in most distributions & its version output has changed
// / between syslinux releases. The pattern anchors on a major.minor pair anywhere in the
// / output & is deliberately loose. Confirm the detected number against a real installation.
function verifyIsoHybridVersion($MinimumVersion) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $IsoHybridBinary = FALSE;
  $locatedBinary = $detectedVersion = '';
  $versionOutput = $versionMatches = $minimumParts = array();
  $versionExitCode = 1;
  $detectedMajor = $detectedMinor = $minimumMajor = $minimumMinor = 0;
  $locatedBinary = locateDependency('isohybrid');
  if ($locatedBinary !== '') {
    exec(escapeshellarg($locatedBinary).' --version 2>&1', $versionOutput, $versionExitCode);
    if (!empty($versionOutput)) {
      if (preg_match('/(\d+)\.(\d+)/', implode(' ', $versionOutput), $versionMatches)) {
        $detectedMajor = (int)$versionMatches[1];
        $detectedMinor = (int)$versionMatches[2];
        $detectedVersion = $detectedMajor.'.'.$detectedMinor;
        $minimumParts = explode('.', $MinimumVersion);
        $minimumMajor = (int)($minimumParts[0] ?? 0);
        $minimumMinor = (int)($minimumParts[1] ?? 0);
        // / Compare numerically, never as strings. A string comparison ranks 6.4 below 6.10.
        if ($detectedMajor > $minimumMajor) $IsoHybridBinary = $locatedBinary;
        elseif ($detectedMajor === $minimumMajor && $detectedMinor >= $minimumMinor) $IsoHybridBinary = $locatedBinary; } } }
  if ($Verbose) logEntry('Isohybrid Version Check: '.($IsoHybridBinary === FALSE ? 'FAILED' : 'PASSED').', Detected: '.($detectedVersion === '' ? 'NONE' : $detectedVersion).', Required: '.$MinimumVersion.' or later'.($IsoHybridBinary === FALSE ? '' : ', Using: '.$IsoHybridBinary).'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $locatedBinary, $detectedVersion, $versionOutput, $versionMatches, $minimumParts, $versionExitCode, $detectedMajor, $detectedMinor, $minimumMajor, $minimumMinor, $MinimumVersion);
  return $IsoHybridBinary; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to confirm this server can actually isolate a dependency invocation.
// / Returns the absolute path of a WORKING bubblewrap binary, or FALSE.
// / A path is returned ONLY when a real sandbox was launched successfully, so a caller
// / holding a path may build a command with it without checking anything else.
// / This is a CAPABILITY check rather than a version check & is the only one in the
// / application. Bubblewrap may be installed & still be non functional.
// / Unprivileged user namespaces can be disabled at the kernel level, restricted by an
// / AppArmor profile, or blocked by a container runtime. Testing that the binary exists
// / proves nothing at all, so a real minimal sandbox is launched here instead.
// / A dependency that cannot be isolated must be refused rather than run unprotected, so a
// / FALSE from this function is the strongest signal the application produces.
function verifyBwrap() {
  // / Set variables.
  global $Verbose, $RunningAsRoot, $CurrentUser, $ApacheUser, $EnableMemoryProtection;
  $BwrapBinary = FALSE;
  $bwrapReason = '';
  $locatedBinary = $bwrapCommand = '';
  $bwrapOutput = array();
  $bwrapExitCode = 1;
  $locatedBinary = locateDependency('bwrap');
  if ($locatedBinary !== '') {
    // / Launch the smallest possible sandbox & run a command that does nothing but exit.
    // / This proves the kernel will actually grant the namespaces a real render depends on.
    // / The ro-bind-try entries are optional & are skipped on systems that do not have them.
    $bwrapCommand = 'timeout 10 '.escapeshellarg($locatedBinary)
      .' --unshare-all'
      .' --die-with-parent'
      .' --ro-bind /usr /usr'
      .' --ro-bind-try /lib /lib'
      .' --ro-bind-try /lib64 /lib64'
      .' --ro-bind-try /bin /bin'
      .' --proc /proc'
      .' --dev /dev'
      .' --tmpfs /tmp'
      .' /usr/bin/true 2>&1';
    // / A probe run as root proves nothing. Root can always create a namespace, so a check
    // / made by -fp would report a working sandbox while every web request still failed.
    // / When this is running as root the probe is re-run as the account that will actually
    // / run conversions, which is the only answer worth reporting.
    if ($RunningAsRoot && $CurrentUser !== $ApacheUser) $bwrapCommand = 'su -s /bin/sh '.escapeshellarg($ApacheUser).' -c '.escapeshellarg($bwrapCommand).' 2>&1';
    // / stderr is KEPT. bwrap names the exact reason it could not build a namespace, &
    // / discarding it left an operator with an exit code & nothing to act on. The usual
    // / cause is a kernel restricting unprivileged user namespaces, which -fp corrects.
    exec($bwrapCommand, $bwrapOutput, $bwrapExitCode);
    if ($bwrapExitCode === 0) $BwrapBinary = $locatedBinary;
    else $bwrapReason = trim((string)(isset($bwrapOutput[0]) ? $bwrapOutput[0] : 'no reason was reported')); }
  if ($BwrapBinary === FALSE && $bwrapReason !== '') warningEntry('Bubblewrap could not build a sandbox'.(($RunningAsRoot && $CurrentUser !== $ApacheUser) ? ' as '.$ApacheUser : '').'. '.$bwrapReason.' Run the -fp argument as root to install the AppArmor profile an unprivileged user namespace needs.');
  if ($Verbose) logEntry('Bubblewrap Sandbox Check: '.($BwrapBinary === FALSE ? 'FAILED' : 'PASSED').', Exit code: '.$bwrapExitCode.($BwrapBinary === FALSE ? ', Reason: '.$bwrapReason : ', Using: '.$BwrapBinary).'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $locatedBinary, $bwrapCommand, $bwrapOutput, $bwrapExitCode, $bwrapReason);
  return $BwrapBinary; }
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
  global $SecretFile, $ManagerSocketDir, $InstLoc, $HRConvertVersion, $ConfigVersion, $RequiredConfigVersion, $RequiredGuiVersion, $RequiredLanguageVersion, $RequiredSetupCoreVersion, $RequiredDependencyCoreVersion, $RequiredDependsVersion, $RequiredPipelineManagerVersion, $PipelineManagerActive, $PipelineCount, $RequiredSecretVersion, $RequiredConfigScript, $ApplicationName, $SupportedConversionTypes, $SupportedGuis, $SupportedLanguages, $DirSep, $Lol, $UsePyMeshLab, $AllowBootableIsoImage, $RequireSandbox, $RequireSandboxOnDocker, $RunningInContainer, $MinimumFFMPEGVersion, $MinimumStreamFFMPEGVersion, $MinimumLibreOfficeVersion, $MinimumInkscapeVersion, $MinimumDiaVersion, $MinimumSCADVersion, $MinimumImageVersion, $MinimumAssimpVersion, $MinimumMeshlabVersion, $MinimumTesseractVersion, $MinimumPdftotextVersion, $Minimum7zVersion, $MinimumRarVersion, $MinimumZipVersion, $MinimumTarVersion, $MinimumMkisofsVersion, $MinimumIsoHybridVersion, $MinimumCalibreVersion, $RunningAsRoot, $CurrentUser, $EnableMemoryProtection, $EnableResourceAwareness, $RequireResourceAwareness, $ResourceAwarenessActive, $RequiredCoreManagerVersion, $CoreManagerVersion, $ManagerSocketDir, $TotalResourceBudget, $ReserveResourcePercentage, $MaxConcurrentWorkers, $MaxExpectedRuntime, $CoreManagerSubprocessPollInterval, $ResourcePollInterval, $WorkerReapInterval, $WorkerStaleGracePeriod, $ConvertTemp, $MaintainHTAccess;
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
  $componentName = $dependencyName = $subsystemName = '';
  $subsystemIsReady = FALSE;
  $failureCount = 0;
  $maintainHtaccess = $apacheConfigIsInstalled = $dataIsProtected = FALSE;
  $exposureStatus = $exposureDetail = $secretMode = $socketMode = '';
  // / Run every dependency check so this reports what WORKS, not what is configured.
  $ffmpegBinary = verifyFFMPEGVersion($MinimumFFMPEGVersion);
  $streamFfmpegBinary = verifyFFMPEGVersion($MinimumStreamFFMPEGVersion);
  $libreOfficeIsValid = verifyLibreOfficeVersion($MinimumLibreOfficeVersion);
  $inkscapeBinary = verifySVGVersion($MinimumInkscapeVersion);
  $diaBinary = verifyDrawingVersion($MinimumDiaVersion);
  $scadBinary = verifySCADVersion($MinimumSCADVersion);
  $imageBinary = verifyImageVersion($MinimumImageVersion);
  $ebookBinary = verifyEbookVersion($MinimumCalibreVersion);
  list ($modelsAreValid, $assimpBinary, $meshlabBinary) = verifyModelVersions($MinimumAssimpVersion, $MinimumMeshlabVersion);
  list ($ocrToolsAreValid, $tesseractBinary, $pdftotextBinary) = verifyOCRVersions($MinimumTesseractVersion, $MinimumPdftotextVersion);
  list ($archiveToolsAreValid, $sevenZipBinary, $rarBinary, $zipBinary, $tarBinary, $mkisofsBinary) = verifyArchiveVersions($Minimum7zVersion, $MinimumRarVersion, $MinimumZipVersion, $MinimumTarVersion, $MinimumMkisofsVersion);
  $isoHybridBinary = verifyIsoHybridVersion($MinimumIsoHybridVersion);
  $bwrapBinary = verifyBwrap();
  // / Report what is installed & what state it is in.
  // / Every line is a fact. An explanation belongs in Documentation, not in this output.
  $failureCount = 0;
  print($Lol.$ApplicationName.' '.$HRConvertVersion.$Lol);
  print('  Config     '.$ConfigVersion.', requires '.$RequiredConfigVersion.' or later'.$Lol);

  // / Detachable components. Each is an EXACT match & a mismatch removes what it provides.
  print($Lol.'Detachable components'.$Lol);
  $componentChecks = array(
    'Core Manager' => array(readComponentVersion('coreManager.php', 'CoreManagerVersion'), $RequiredCoreManagerVersion),
    'Setup Core' => array(readComponentVersion('SetupCore'.$DirSep.'setupCore.php', 'SetupCoreVersion'), $RequiredSetupCoreVersion),
    'Dependency Core' => array(readComponentVersion('DependencyCore'.$DirSep.'dependencyCore.php', 'DependencyCoreVersion'), $RequiredDependencyCoreVersion),
    'Dependency manifest' => array(readComponentVersion('depends.php', 'DependsVersion'), $RequiredDependsVersion),
    'Pipeline Manager' => array(readComponentVersion('Pipelines'.$DirSep.'pipelineManager.php', 'PipelineManagerVersion'), $RequiredPipelineManagerVersion));
  foreach ($componentChecks as $componentName => $componentPair) {
    if (ltrim((string)$componentPair[0], 'vV') === ltrim((string)$componentPair[1], 'vV')) print('  '.str_pad($componentName, 28).'OK, '.ltrim((string)$componentPair[0], 'vV').$Lol);
    else {
      $failureCount++;
      print('  '.str_pad($componentName, 28).'FAILED, reports '.($componentPair[0] === '' ? 'no version' : ltrim((string)$componentPair[0], 'vV')).', requires '.$componentPair[1].$Lol); } }
  if (!$PipelineManagerActive) print('  '.str_pad('Conversion pipelines', 28).'UNAVAILABLE, no conversion can run'.$Lol);
  else print('  '.str_pad('Conversion pipelines', 28).'OK, '.$PipelineCount.' verified'.$Lol);

  // / Dependencies. An optional one says so, because losing it costs a feature rather than
  // / a subsystem.
  print($Lol.'Dependencies'.$Lol);
  $dependencyChecks = array(
    'FFMPEG, audio & video' => array($ffmpegBinary !== FALSE, $MinimumFFMPEGVersion, TRUE),
    'FFMPEG, streams' => array($streamFfmpegBinary !== FALSE, $MinimumStreamFFMPEGVersion, TRUE),
    'LibreOffice, documents' => array($libreOfficeIsValid, $MinimumLibreOfficeVersion, TRUE),
    'ImageMagick, images' => array($imageBinary !== FALSE, $MinimumImageVersion, TRUE),
    'Inkscape, SVG' => array($inkscapeBinary !== FALSE, $MinimumInkscapeVersion, TRUE),
    'Dia, drawings' => array($diaBinary !== FALSE, $MinimumDiaVersion, TRUE),
    'OpenSCAD, scad' => array($scadBinary !== FALSE, $MinimumSCADVersion, TRUE),
    'Assimp, models' => array($assimpBinary !== FALSE, $MinimumAssimpVersion, TRUE),
    'Meshlab, models' => array($meshlabBinary !== FALSE, $MinimumMeshlabVersion, FALSE),
    'Tesseract, OCR' => array($tesseractBinary !== FALSE, $MinimumTesseractVersion, TRUE),
    'Pdftotext, OCR' => array($pdftotextBinary !== FALSE, $MinimumPdftotextVersion, TRUE),
    '7-Zip, all extraction' => array($sevenZipBinary !== FALSE, $Minimum7zVersion, TRUE),
    'Zip, archives' => array($zipBinary !== FALSE, $MinimumZipVersion, TRUE),
    'Tar, archives' => array($tarBinary !== FALSE, $MinimumTarVersion, TRUE),
    'Mkisofs, iso' => array($mkisofsBinary !== FALSE, $MinimumMkisofsVersion, TRUE),
    'Rar, archives' => array($rarBinary !== FALSE, $MinimumRarVersion, FALSE),
    'Isohybrid, iso' => array($isoHybridBinary !== FALSE, $MinimumIsoHybridVersion, FALSE),
    'Calibre, e-books' => array($ebookBinary !== FALSE, $MinimumCalibreVersion, TRUE));
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
    'Optical character recognition' => $ocrToolsAreValid,
    'Archives' => $archiveToolsAreValid);
  foreach ($subsystemChecks as $subsystemName => $subsystemIsReady) {
    if (!$subsystemIsReady) $failureCount++;
    print('  '.str_pad($subsystemName, 28).($subsystemIsReady ? 'READY' : 'NOT READY').$Lol); }
  if (!$AllowBootableIsoImage) print('  '.str_pad('Bootable disk images', 28).'DISABLED in config.php'.$Lol);
  else {
    if ($mkisofsBinary === FALSE) $failureCount++;
    print('  '.str_pad('Bootable disk images', 28).($mkisofsBinary === FALSE ? 'NOT READY' : 'READY').$Lol); }

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
  // / The install secret must be 0600 & the socket directory 0700, both owned by the web
  // / server user. A startup key is only worth anything because those two hold. Nothing
  // / checked them at startup, so an installation that never had -fp run as root, or one
  // / whose permissions were widened by a restore, lost the property silently.
  $secretMode = '';
  $socketMode = '';
  if (isset($SecretFile) && $SecretFile !== '' && file_exists($SecretFile)) $secretMode = substr(sprintf('%o', fileperms($SecretFile)), -4);
  if (isset($ManagerSocketDir) && $ManagerSocketDir !== '' && is_dir($ManagerSocketDir)) $socketMode = substr(sprintf('%o', fileperms($ManagerSocketDir)), -4);

  // / The environment every conversion runs in.
  print($Lol.'Environment'.$Lol);
  if ($secretMode === '') print('  '.str_pad('Install secret', 28).'NOT PRESENT, it is created on the first request'.$Lol);
  else if ($secretMode !== '0600') {
    $failureCount++;
    print('  '.str_pad('Install secret', 28).'FAILED, mode '.$secretMode.', expected 0600. Run -fp as root'.$Lol); }
  else print('  '.str_pad('Install secret', 28).'OK, 0600'.$Lol);
  if ($socketMode === '') print('  '.str_pad('Manager socket directory', 28).'NOT PRESENT, it is created with the listener'.$Lol);
  else if ($socketMode !== '0700') {
    $failureCount++;
    print('  '.str_pad('Manager socket directory', 28).'FAILED, mode '.$socketMode.', expected 0700. Run -fp as root'.$Lol); }
  else print('  '.str_pad('Manager socket directory', 28).'OK, 0700'.$Lol);
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
  print('  '.str_pad('Maintain .htaccess', 28).($maintainHtaccess ? 'YES' : 'NO, disabled in config.php').$Lol);
  print('  '.str_pad('Apache configuration', 28).($apacheConfigIsInstalled ? 'INSTALLED' : 'not installed, run -fp as root').$Lol);
  print('  '.str_pad('Live exposure', 28).strtoupper($exposureStatus).', '.$exposureDetail.$Lol);

  // / Resource awareness & the listener it depends on.
  print($Lol.'Resource awareness'.$Lol);
  if (!$EnableResourceAwareness) print('  '.str_pad('Configured', 28).'DISABLED in config.php'.$Lol);
  else if (!$ResourceAwarenessActive) print('  '.str_pad('Component', 28).'FAILED, requires '.$RequiredCoreManagerVersion.' exactly'.$Lol);
  else {
    list ($listenerIsRunning, $listenerStatus) = reportListenerStatus();
    print('  '.str_pad('Component', 28).'OK, '.ltrim((string)$CoreManagerVersion, 'vV').$Lol);
    print('  '.str_pad('Enforced', 28).($RequireResourceAwareness ? 'YES' : 'NO').$Lol);
    print('  '.str_pad('Total budget', 28).((int)$TotalResourceBudget < 1 ? 'AUTO, from the processor count' : (int)$TotalResourceBudget.' cost units').$Lol);
    print('  '.str_pad('Reserved share', 28).(int)$ReserveResourcePercentage.'%'.$Lol);
    print('  '.str_pad('Concurrent worker limit', 28).((int)$MaxConcurrentWorkers < 1 ? 'NONE, the budget decides' : (int)$MaxConcurrentWorkers).$Lol);
    print('  '.str_pad('Maximum runtime', 28).(int)$MaxExpectedRuntime.'s'.$Lol);
    if (!$listenerIsRunning) print('  '.str_pad('Listener', 28).'STOPPED, start it with -l'.$Lol);
    else {
      print('  '.str_pad('Listener', 28).'RUNNING as process '.$listenerStatus['CoreManagerPid'].$Lol);
      print('  '.str_pad('Subordinate managers', 28).count($listenerStatus['Subordinates']).' of 3'.$Lol);
      print('  '.str_pad('Tracked workers', 28).$listenerStatus['TrackedWorkers'].$Lol);
      if (isset($listenerStatus['Budget']['RemainingBudget'])) print('  '.str_pad('Remaining budget', 28).$listenerStatus['Budget']['RemainingBudget'].' of '.$listenerStatus['Budget']['TotalBudget'].$Lol); } }

  print($Lol.'Enabled conversion types'.$Lol);
  print('  '.implode(', ', $SupportedConversionTypes).$Lol);
  // / Only say what a failure means when there is one.
  if ($failureCount > 0) print($Lol.$failureCount.' check(s) failed. See Documentation/ERROR_DESCRIPTIONS.txt.'.$Lol);
  print($Lol);
  $VersionInfoDisplayed = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $modelsAreValid, $ocrToolsAreValid, $archiveToolsAreValid, $libreOfficeIsValid, $ffmpegBinary, $streamFfmpegBinary, $inkscapeBinary, $diaBinary, $scadBinary, $imageBinary, $assimpBinary, $meshlabBinary, $tesseractBinary, $pdftotextBinary, $sevenZipBinary, $rarBinary, $zipBinary, $tarBinary, $mkisofsBinary, $isoHybridBinary, $bwrapBinary, $installedGui, $installedLang, $installedEndonym, $checkDir, $checkFile, $foundVersion, $langLine, $guiMatches, $langMatches, $langOk, $langTotal, $ebookBinary, $listenerStatus, $listenerIsRunning, $secretMode, $socketMode, $componentChecks, $componentPair, $componentName, $dependencyChecks, $dependencyState, $dependencyName, $subsystemChecks, $subsystemIsReady, $subsystemName, $failureCount, $maintainHtaccess, $apacheConfigIsInstalled, $dataIsProtected, $exposureStatus, $exposureDetail);
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
  print('  --config                    Configure this installation without a text editor.'.$Lol);
  print('  --setup                     Install, check & audit dependencies.'.$Lol);
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
  print('  -c                          Sweep using the configured Delete Threshold.'.$Lol);
  print('  -c=<minutes>                Sweep sessions older than that many minutes.'.$Lol);
  print('  -c=now                      Sweep EVERY session, including sessions in use.'.$Lol);
  print('                              A user mid conversion loses their files.'.$Lol);
  print($Lol);
  print('Update targets'.$Lol);
  print('  -u                          Install the target set in config.php.'.$Lol);
  print('  -u=latest                   Install the newest tagged release.'.$Lol);
  print('  -u=edge                     Install the current state of the master branch.'.$Lol);
  print('  -u=v#.#.#                   Install exactly that tagged release.'.$Lol);
  print('                              Edge is not a release. It carries whatever version'.$Lol);
  print('                              stamp master holds & may match no release at all.'.$Lol);
  print('  -u=v3.6.8                   Install exactly that tag. Fails if it does not exist.'.$Lol);
  print('                              A partial version such as v3.6 is refused.'.$Lol);
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

function parseCommandLine() {
  // / Set variables.
  global $Lol, $DeleteThreshold, $ConvertLoc, $ConvertTempDir, $RunningFromCLI, $RunningAsRoot, $CurrentUser, $ApacheUser, $ResourceAwarenessActive, $RequiredSetupCoreVersion, $RequiredDependencyCoreVersion, $EnableMemoryProtection, $DirSep;
  $CommandLineHandled = $cliTempCleaned = $cliTempDeepCleaned = $cliDataCleaned = $cliDataDeepCleaned = FALSE;
  $UserType = 'web';
  $cliArgumentCount = $cliThreshold = $cliPathsCorrected = 0;
  $cliArguments = $cliParts = $cliStatus = $listenerCommands = $setupCommands = array();
  $cliCommand = $rawFirstArg = $cliTarget = $cliSecondTarget = $cliWhoami = $cliSetupVersion = '';
  $cliConfirmed = $cliListenerAuthorized = $cliActionConfirmed = $cliPermissionsFixed = $cliListenerRunning = $cliSetupIsAvailable = FALSE;
  $cliDependencyIsAvailable = $cliDependenciesReady = $cliSetupSucceeded = FALSE;
  $cliDependencyVersion = $cliSubsystem = $cliDependencyToken = '';
  // / A startup key that arrived in the environment rather than on the command line.
  $cliStartupKey = '';
  $cliDependencyFindings = $subOptionOwners = array();
  $cliOptionalProblems = 0;
  $cliSetupCount = 0;
  // / A web request has no command line & must return immediately.
  // / This is the ONLY path that returns FALSE. Every other path handles & stops.
  if (!$RunningFromCLI) $CommandLineHandled = FALSE;
  else {
    // / Gather the arguments. The first is always the script name & is discarded.
    $cliArguments = isset($_SERVER['argv']) ? $_SERVER['argv'] : array();
    array_shift($cliArguments);
    $cliArgumentCount = count($cliArguments);
    // / A trailing -y anywhere in the argument list pre confirms a destructive action.
    if (in_array('-y', $cliArguments, TRUE) or in_array('--yes', $cliArguments, TRUE)) $cliConfirmed = TRUE;
    // / Only the web server user & root may operate the listener.
    // / A standard user holds a per user secret, which cannot derive a valid startup key.
    if ($RunningAsRoot or $CurrentUser === $ApacheUser) $cliListenerAuthorized = TRUE;
    $cliWhoami = ($CurrentUser === '' ? 'an unidentified user' : $CurrentUser);
    // / Every command the listener owns. Recognized for all users & refused for the wrong one.
    $listenerCommands = array('-l', '--listen', '-k', '--kill', '--kill-all-workers', '--kill-every-worker');
    // / Every command Setup Core owns. The component decides what each one requires.
    $setupCommands = array('--config', '--setup');
    // / An option belongs to a command. Typed on its own it is not a command, & saying only
    // / that it is unrecognized sends an operator hunting for a typo that is not there.
    // / Naming the parent is the difference between a dead end & an answer.
    $subOptionOwners = array(
      '--check-depends' => '--setup', '--install-depends' => '--setup', '--install-deps' => '--setup',
      '--update-depends' => '--setup', '--uninstall-depends' => '--setup', '--output-supply-chain' => '--setup',
      '--install-complete' => '--setup', '--reinstall-existing' => '--setup', '--subsystem' => '--setup',
      '--install-service' => '--setup',
      '--reset-all-defaults' => '--config', '--reset-default-section' => '--config',
      '--reset-default-variable' => '--config', '--backup' => '--config', '--repair' => '--config',
      '--view' => '--config');
    // / A command line invocation with no argument is a request for help.
    if ($cliArgumentCount < 1) {
      logEntry('Command line invocation with no argument. Displaying help.');
      showHelpInfo();
      $CommandLineHandled = TRUE; }
    else {
      // / Extract the raw first argument to check for an inline equal sign assignment.
      $rawFirstArg = trim($cliArguments[0]);
      $cliParts = explode('=', $rawFirstArg, 2);
      $cliCommand = strtolower(trim($cliParts[0]));
      // / Resolve the target from an equal sign first & from the next argument second.
      if (isset($cliParts[1])) $cliTarget = strtolower(trim($cliParts[1]));
      else $cliTarget = isset($cliArguments[1]) ? trim($cliArguments[1]) : '';
      $cliSecondTarget = isset($cliArguments[2]) ? trim($cliArguments[2]) : '';
      // / Handle the -v or --version arguments.
      if ($cliCommand === '-v' or $cliCommand === '--version') {
        logEntry('Command line invocation. Displaying version information.');
        showVersionInfo();
        $CommandLineHandled = TRUE; }
      // / Handle the -h or --help arguments.
      else if ($cliCommand === '-h' or $cliCommand === '--help') {
        logEntry('Command line invocation. Displaying help.');
        showHelpInfo();
        $CommandLineHandled = TRUE; }
      // / Handle the internal Core Manager entry point.
      // / This is never typed by an administrator. It is invoked by the -l argument with a
      // / key derived from the install secret & is refused without one.
      else if ($cliCommand === '--start-core-manager') {
        if (!$ResourceAwarenessActive) errorEntry('A Core Manager start was requested but the component is unavailable!', 31009, TRUE);
        else {
          // / The key arrives in the environment. A key on the command line is still read,
          // / so a listener launched by the previous release can still start its children.
          $cliStartupKey = readTransportedStartupKey();
          if ($cliStartupKey === '') $cliStartupKey = (string)$cliTarget;
          dispatchManagerRole('core-manager', $cliStartupKey); }
        $CommandLineHandled = TRUE; }
      // / Handle the foreground listener entry point.
      // / A service manager runs this. It does not fork & does not return, so systemd can
      // / watch the real process, restart it when it dies & report its true state.
      // / It derives its own key rather than being handed one, because a unit file cannot
      // / compute a time bucketed HMAC. Authorization is unchanged. A standard user holds
      // / a per user secret & the key they derive will not validate.
      else if ($cliCommand === '--run-core-manager') {
        if (!$ResourceAwarenessActive) errorEntry('The foreground listener was requested but the Core Manager component is unavailable!', 31016, TRUE);
        else runCoreManagerForeground();
        $CommandLineHandled = TRUE; }
      // / Handle the internal subordinate manager entry point.
      else if ($cliCommand === '--start-manager') {
        if (!$ResourceAwarenessActive) errorEntry('A subordinate manager start was requested but the component is unavailable!', 31012, TRUE);
        else {
          // / The key arrives in the environment. A key on the command line is still
          // / accepted, because a listener spawned by the previous release is still running
          // / when this one is installed & its children must be able to start.
          $cliStartupKey = readTransportedStartupKey();
          if ($cliStartupKey === '') $cliStartupKey = (string)$cliSecondTarget;
          dispatchManagerRole($cliTarget, $cliStartupKey); }
        $CommandLineHandled = TRUE; }
      // / Handle the --status argument. Available to any user, reports what is running.
      // / The identity is reported because a standard user cannot read the socket directory
      // / & would otherwise be told the listener is stopped whether it is or not.
      else if ($cliCommand === '--status') {
        print($Lol.'Running as          '.$cliWhoami.($RunningAsRoot ? ' (root)' : '').$Lol);
        print('Listener commands   '.($cliListenerAuthorized ? 'AVAILABLE' : 'NOT AVAILABLE to this user').$Lol);
        if (!$ResourceAwarenessActive) print('Resource awareness  UNAVAILABLE, the Core Manager component is missing or does not match this core.'.$Lol);
        else {
          list ($cliListenerRunning, $cliStatus) = reportListenerStatus();
          print('Listener            '.($cliListenerRunning ? 'RUNNING as process '.$cliStatus['CoreManagerPid'] : 'STOPPED').$Lol);
          print('Subordinates        '.count($cliStatus['Subordinates']).$Lol);
          print('Tracked workers     '.$cliStatus['TrackedWorkers'].$Lol);
          if (isset($cliStatus['SessionLocations'])) print('Mapped sessions     '.$cliStatus['SessionLocations'].$Lol);
          if (isset($cliStatus['Budget']['RemainingBudget'])) print('Remaining budget    '.$cliStatus['Budget']['RemainingBudget'].' of '.$cliStatus['Budget']['TotalBudget'].$Lol);
          if (!$cliListenerRunning && !$cliListenerAuthorized) print($Lol.'STOPPED may be wrong. This user cannot read the socket directory.'.$Lol); }
        print($Lol);
        $CommandLineHandled = TRUE; }
      // / Handle every Setup Core & Dependency Core command through one gate.
      // / Each command loads only what it actually needs.
      // / --config needs Setup Core. --setup needs Dependency Core. An operator asking what
      // / is installed must not be stopped because a component they are not using is absent.
      else if (in_array($cliCommand, $setupCommands, TRUE)) {
        // / The configuration utility. Setup Core owns the model, so it is the only
        // / component this path requires.
        if ($cliCommand === '--config') {
          list ($cliSetupIsAvailable, $cliSetupVersion) = verifyCoreComponent('Setup Core', 'SetupCore'.$DirSep.'setupCore.php', 'SetupCoreVersion', $RequiredSetupCoreVersion);
          if (!$cliSetupIsAvailable) {
            print($Lol.'The Setup Core component is unavailable.'.$Lol);
            if ($cliSetupVersion === '') print('Resources/SetupCore/setupCore.php is missing, unreadable, or reports no version.'.$Lol.$Lol);
            else print('It reports v'.ltrim($cliSetupVersion, 'vV').' & this core requires v'.ltrim((string)$RequiredSetupCoreVersion, 'vV').'.'.$Lol.$Lol); }
          else {
            logEntry('Command line invocation. Running the configuration utility.');
            // / Every mode is resolved from the argument list, so the order they were typed
            // / in does not matter.
            if (in_array('--reset-all-defaults', $cliArguments, TRUE)) runConfigUtility('', 'reset-all', '', $cliConfirmed);
            else if (extractCliOption($cliArguments, '--reset-default-section') !== '') runConfigUtility('', 'reset-section', extractCliOption($cliArguments, '--reset-default-section'), $cliConfirmed);
            else if (extractCliOption($cliArguments, '--reset-default-variable') !== '') runConfigUtility('', 'reset-variable', extractCliOption($cliArguments, '--reset-default-variable'), $cliConfirmed);
            else if (extractCliOption($cliArguments, '--backup') !== '') runConfigUtility('', 'backup', extractCliOption($cliArguments, '--backup'), $cliConfirmed);
            else if (in_array('--repair', $cliArguments, TRUE)) runConfigUtility('', 'repair', '', $cliConfirmed);
            else if (in_array('--view', $cliArguments, TRUE)) runConfigUtility('', 'view', '', $cliConfirmed);
            else runConfigUtility('', 'interactive', '', $cliConfirmed); } }
        // / Everything --setup owns. Dependency Core is required. Setup Core is loaded only
        // / by the two options that also configure & repair.
        else {
          list ($cliDependencyIsAvailable, $cliDependencyVersion) = verifyCoreComponent('Dependency Core', 'DependencyCore'.$DirSep.'dependencyCore.php', 'DependencyCoreVersion', $RequiredDependencyCoreVersion);
          $cliSubsystem = extractCliOption($cliArguments, '--subsystem');
          if (!$cliDependencyIsAvailable) {
            print($Lol.'The Dependency Core component is unavailable.'.$Lol);
            if ($cliDependencyVersion === '') print('Resources/DependencyCore/dependencyCore.php is missing, unreadable, or reports no version.'.$Lol.$Lol);
            else print('It reports v'.ltrim($cliDependencyVersion, 'vV').' & this core requires v'.ltrim((string)$RequiredDependencyCoreVersion, 'vV').'.'.$Lol.$Lol); }
          // / Reading the machine needs no authorization at all.
          else if (in_array('--check-depends', $cliArguments, TRUE)) {
            logEntry('Command line invocation. Checking dependencies.');
            list ($cliDependenciesReady, $cliDependencyFindings, $cliOptionalProblems) = checkDepends($cliSubsystem);
            showDependencyFindings($cliDependencyFindings);
            print(($cliDependenciesReady ? 'Every REQUIRED dependency is present & current.' : 'One or more REQUIRED dependencies are missing or too old.').$Lol);
            // / An optional problem is still a problem. Reporting that every requirement is
            // / met & stopping there reads as though the failures printed above it do not
            // / matter, which is how two broken entries went unnoticed under a green summary.
            if ($cliOptionalProblems > 0) print($cliOptionalProblems.' OPTIONAL dependenc(ies) are missing or too old. The subsystems that use them will refuse.'.$Lol);
            print($Lol); }
          else if (in_array('--output-supply-chain', $cliArguments, TRUE) or extractCliOption($cliArguments, '--output-supply-chain') !== '') {
            logEntry('Command line invocation. Writing a supply chain report.');
            outputSupplyChain(extractCliOption($cliArguments, '--output-supply-chain')); }
          // / Everything below rewrites the machine & carries a token derived from the
          // / install secret. The token proves the request came through this core.
          else if (in_array('--install-depends', $cliArguments, TRUE) or in_array('--install-deps', $cliArguments, TRUE)) {
            logEntry('Command line invocation. Installing dependencies.');
            $cliDependencyToken = deriveStartupKey('dependency-write');
            list ($cliSetupSucceeded, $cliSetupCount) = installDepends($cliDependencyToken, $cliSubsystem, $cliConfirmed); }
          else if (in_array('--update-depends', $cliArguments, TRUE)) {
            logEntry('Command line invocation. Upgrading dependencies.');
            $cliDependencyToken = deriveStartupKey('dependency-write');
            list ($cliSetupSucceeded, $cliSetupCount) = updateDepends($cliDependencyToken, $cliSubsystem, $cliConfirmed); }
          else if (in_array('--uninstall-depends', $cliArguments, TRUE)) {
            warningEntry('Command line invocation. Removing dependencies.');
            $cliDependencyToken = deriveStartupKey('dependency-write');
            list ($cliSetupSucceeded, $cliSetupCount) = uninstallDepends($cliDependencyToken, $cliSubsystem, $cliConfirmed); }
          // / The listener service unit, generated from the live configuration.
          // / Setup Core owns it. Dependency Core is already loaded by the time we get here.
          else if (in_array('--install-service', $cliArguments, TRUE)) {
            list ($cliSetupIsAvailable, $cliSetupVersion) = verifyCoreComponent('Setup Core', 'SetupCore'.$DirSep.'setupCore.php', 'SetupCoreVersion', $RequiredSetupCoreVersion);
            if (!$cliSetupIsAvailable) print($Lol.'This operation needs the Setup Core component, which is unavailable.'.$Lol.$Lol);
            else if (!$RunningAsRoot) {
              warningEntry('A service unit installation was refused for an unauthorized user.');
              print($Lol.'Installing a service unit is only available to root.'.$Lol);
              print($Lol.'  sudo php convertCore.php --setup --install-service'.$Lol.$Lol); }
            else {
              logEntry('Command line invocation. Installing the listener service unit.');
              print($Lol.'Installing the listener service unit.'.$Lol);
              installListenerService(TRUE);
              print($Lol); } }
          // / These two install dependencies AND configure, so both components are needed.
          else if (in_array('--install-complete', $cliArguments, TRUE) or in_array('--reinstall-existing', $cliArguments, TRUE)) {
            list ($cliSetupIsAvailable, $cliSetupVersion) = verifyCoreComponent('Setup Core', 'SetupCore'.$DirSep.'setupCore.php', 'SetupCoreVersion', $RequiredSetupCoreVersion);
            if (!$cliSetupIsAvailable) {
              print($Lol.'This operation also needs the Setup Core component, which is unavailable.'.$Lol);
              print('Dependencies can still be managed with --setup --check-depends & --install-depends.'.$Lol.$Lol); }
            else {
              $cliDependencyToken = deriveStartupKey('dependency-write');
              if (in_array('--install-complete', $cliArguments, TRUE)) {
                logEntry('Command line invocation. Performing a complete installation.');
                runCompleteInstall($cliDependencyToken, $cliConfirmed); }
              else {
                logEntry('Command line invocation. Reinstalling in place.');
                runReinstallExisting($cliTarget, $cliConfirmed); } } }
          // / --setup with no option is a request to see what --setup can do.
          else {
            print($Lol.'Setup options'.$Lol);
            print('  --check-depends              Report the state of every dependency.'.$Lol);
            print('  --install-depends            Install everything absent or too old.'.$Lol);
            print('  --update-depends             Upgrade everything already installed.'.$Lol);
            print('  --uninstall-depends          Remove a subsystem. Requires --subsystem.'.$Lol);
            print('  --output-supply-chain[=path] Write a supply chain audit template.'.$Lol);
            print('  --install-service            Install the listener service unit from this configuration.'.$Lol);
            print('  --install-complete           Install HRConvert2 & every dependency.'.$Lol);
            print('  --reinstall-existing         Reinstall this installation in place.'.$Lol);
            print('  --subsystem=<Name>           Restrict the operation to one subsystem.'.$Lol);
            print('  -y, --yes                    Skip the confirmation prompt.'.$Lol);
            print($Lol.'Every option above is used WITH --setup, never on its own.'.$Lol);
            print('  sudo php convertCore.php --setup --check-depends'.$Lol.$Lol); } }
        $CommandLineHandled = TRUE; }

      // / Handle every listener command through one gate.
      // / Refusing here rather than falling through means a real command is never reported
      // / as an unrecognized one, which sent an authorized user hunting for a typo.
      else if (in_array($cliCommand, $listenerCommands, TRUE)) {
        if (!$cliListenerAuthorized) {
          warningEntry('A listener command was refused for an unauthorized user.');
          print($Lol.'The '.$cliCommand.' command is only available to root or '.$ApacheUser.'.'.$Lol);
          print('You are running as '.$cliWhoami.'.'.$Lol);
          print($Lol.'  sudo php convertCore.php '.$cliCommand.$Lol);
          print('  sudo -u '.$ApacheUser.' php convertCore.php '.$cliCommand.$Lol.$Lol);
          print('A standard user holds a per user secret, which cannot derive a valid'.$Lol);
          print('startup key, so a listener started that way could never be reached.'.$Lol.$Lol); }
        else if (!$ResourceAwarenessActive) print($Lol.'Resource awareness is unavailable. The Core Manager component is missing or does not match this core.'.$Lol.$Lol);
        // / Start the listener.
        else if ($cliCommand === '-l' or $cliCommand === '--listen') {
          logEntry('Command line invocation. Starting the Core Manager listener.');
          startCoreManagerListener(); }
        // / Stop the listener with no target, or end one worker with a target.
        else if ($cliCommand === '-k' or $cliCommand === '--kill') {
          if ($cliTarget === '') {
            logEntry('Command line invocation. Stopping the Core Manager listener.');
            stopCoreManagerListener(); }
          else {
            logEntry('Command line invocation. Terminating worker '.$cliTarget.'.');
            killTargetedWorker($cliTarget); } }
        // / End every TRACKED worker.
        else if ($cliCommand === '--kill-all-workers') {
          $cliActionConfirmed = confirmDestructiveAction('This ends every tracked conversion in progress. Users will lose work.', $cliConfirmed);
          if ($cliActionConfirmed) {
            warningEntry('Command line invocation. Terminating every tracked worker.');
            print($Lol.'Terminated '.killTrackedWorkers().' tracked worker(s).'.$Lol); } }
        // / End every PHP process owned by the web server user.
        // / This reaches unrelated applications sharing the host & says so before it runs.
        else if ($cliCommand === '--kill-every-worker') {
          $cliActionConfirmed = confirmDestructiveAction('This ends EVERY PHP process owned by '.$ApacheUser.' on this host. Other applications sharing this server, such as WordPress or OwnCloud, will lose every session in progress.', $cliConfirmed);
          if ($cliActionConfirmed) {
            warningEntry('Command line invocation. Terminating every process owned by the web server user.');
            print($Lol.'Terminated '.killEveryWorker().' process(es).'.$Lol); } }
        $CommandLineHandled = TRUE; }
      // / Gate root-only, filesystem breaking command line operations behind a security context awareness check.
      else if ($RunningAsRoot) {
        // / Handle the -fp or --fix-permissions arguments.
        if ($cliCommand === '-fp' or $cliCommand === '--fix-permissions') {
          logEntry('Command line invocation. Correcting managed permissions.');
          print($Lol.'Correcting permissions on managed paths.'.$Lol);
          list ($cliPermissionsFixed, $cliPathsCorrected) = fixManagedPermissions();
          print($Lol.($cliPermissionsFixed ? 'Corrected '.$cliPathsCorrected.' path(s).' : 'Permissions could not be corrected.').$Lol.$Lol);
          $CommandLineHandled = TRUE; }
        // / Handle the -u or --update arguments.
        else if ($cliCommand === '-u' or $cliCommand === '--update') {
          logEntry('Command line invocation. Performing an application update.');
          updateApplication(strtolower($cliTarget));
          $CommandLineHandled = TRUE; }
        // / Handle the -c or --clean arguments.
        else if ($cliCommand === '-c' or $cliCommand === '--clean') {
          logEntry('Command line invocation. Performing a manual clean.');
          $cliTarget = strtolower($cliTarget);
          $cliThreshold = $DeleteThreshold;
          // / A threshold of zero expires everything, because every session is older than nothing.
          if ($cliTarget === 'now') $cliThreshold = 0;
          // / ctype_digit rather than is_numeric, because is_numeric accepts a negative & a
          // / negative threshold would expire every session while looking like a normal number.
          else if ($cliTarget !== '' && ctype_digit($cliTarget)) $cliThreshold = (int)$cliTarget;
          else if ($cliTarget !== '') {
            warningEntry('An unrecognized clean threshold was supplied. Using the configured default.');
            print($Lol.'Unrecognized threshold. Supply a whole number of minutes, or now.'.$Lol); }
          print($Lol.'Cleaning sessions older than '.$cliThreshold.' minute(s).'.$Lol);
          logEntry('Manual clean requested. Threshold set to '.$cliThreshold.' minute(s).');
          list ($cliTempCleaned, $cliTempDeepCleaned) = cleanDataLoc($ConvertTempDir, 'ConvertTempDir', $cliThreshold);
          print('  Temporary location   '.($cliTempCleaned ? 'OK' : 'FAILED').($cliTempDeepCleaned ? ', removed expired sessions' : ', nothing was expired').$Lol);
          list ($cliDataCleaned, $cliDataDeepCleaned) = cleanDataLoc($ConvertLoc, 'ConvertLoc', $cliThreshold);
          print('  Data location        '.($cliDataCleaned ? 'OK' : 'FAILED').($cliDataDeepCleaned ? ', removed expired sessions' : ', nothing was expired').$Lol);
          if (!$cliTempCleaned or !$cliDataCleaned) print($Lol.'One or more locations could not be cleaned. See the log for the reason.'.$Lol);
          print($Lol);
          $CommandLineHandled = TRUE; }
        // / An unrecognized argument from root is still a mistake & must not fall through.
        else {
          warningEntry('Command line invocation with an unrecognized argument.');
          reportUnrecognizedArgument($cliCommand, $subOptionOwners);
          $CommandLineHandled = TRUE; } }
      // / A root only command issued by a standard user is named rather than dismissed.
      else if (in_array($cliCommand, array('-fp', '--fix-permissions', '-u', '--update', '-c', '--clean'), TRUE)) {
        warningEntry('A root only command was refused for an unauthorized user.');
        print($Lol.'The '.$cliCommand.' command is only available to root.'.$Lol);
        print('You are running as '.$cliWhoami.'.'.$Lol);
        print($Lol.'  sudo php convertCore.php '.$cliCommand.$Lol.$Lol);
        $CommandLineHandled = TRUE; }
      // / An unrecognized argument is a mistake, not a web request.
      else {
        warningEntry('Command line invocation with an unrecognized argument.');
        reportUnrecognizedArgument($cliCommand, $subOptionOwners);
        $CommandLineHandled = TRUE; } } }
  // / Determine if the user is using the application via command line (CLI) or Apache+PHP through a web browser.
  if ($CommandLineHandled === TRUE) $UserType = 'cli';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cliStartupKey, $cliArguments, $cliCommand, $cliArgumentCount, $rawFirstArg, $cliParts, $cliTarget, $cliSecondTarget, $cliThreshold, $cliTempCleaned, $cliTempDeepCleaned, $cliDataCleaned, $cliDataDeepCleaned, $cliConfirmed, $cliListenerAuthorized, $cliActionConfirmed, $cliPermissionsFixed, $cliListenerRunning, $cliPathsCorrected, $cliStatus, $cliWhoami, $cliSetupIsAvailable, $cliSetupVersion, $listenerCommands, $setupCommands, $cliDependencyIsAvailable, $cliDependencyVersion, $cliSubsystem, $cliDependencyToken, $cliDependencyFindings, $cliDependenciesReady, $cliSetupSucceeded, $cliSetupCount, $subOptionOwners, $cliOptionalProblems);
  return array($CommandLineHandled, $UserType); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the value of a --option=value argument.
// / Accepts the argument list & the option name, in that order.
// / Returns the value, or an empty string when the option was not supplied.
// / The value is returned exactly as typed. A path is case sensitive & a section name may
// / carry spaces, so nothing is lowercased or stripped here.
function extractCliOption($cliArguments, $optionName) {
  // / Set variables.
  global $EnableMemoryProtection;
  $OptionValue = '';
  $argumentText = '';
  $optionPrefix = trim((string)$optionName).'=';
  foreach ($cliArguments as $argumentText) {
    if (strpos((string)$argumentText, $optionPrefix) === 0) $OptionValue = substr((string)$argumentText, strlen($optionPrefix)); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $argumentText, $optionPrefix, $cliArguments, $optionName);
  return $OptionValue; }
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
// / This runs from the core after verifyGlobals() so every global it needs already exists.
// / It also runs after verifyLogs() so its failures can actually be written to the log.
// / These paths live under the application directory, not part of the regular cleanup routine.
function cleanBuildEnvironment() {
  // / Set variables.
  global $Verbose, $DeleteBuildEnvironment, $DeleteDevelopmentDocumentation, $DirSep, $EnableMemoryProtection;
  $BuildEnvCleaned = TRUE;
  $BuildEnvDeleted = $DevDocsDeleted = FALSE;
  $buildDirContents = array();
  $buildDirEntry = '';
  // / Define absolute paths for the files & folders that may need to be removed.
  $dockerFile = realpath(dirname(__FILE__).$DirSep.'Documentation'.$DirSep.'Build'.$DirSep.'Dockerfile');
  $changelogFile = realpath(dirname(__FILE__).$DirSep.'Documentation'.$DirSep.'CHANGELOG.txt');
  $readmeFile = realpath(dirname(__FILE__).$DirSep.'README.md');
  $buildDir = realpath(dirname(__DIR__).$DirSep.'Build');
  // / Delete the build environment if specified by config.php.
  if ($DeleteBuildEnvironment) {
    if ($Verbose) logEntry('Removing the build environment.');
    // / Remove the contents of the build directory one entry at a time.
    if ($buildDir !== FALSE && is_dir($buildDir)) {
      $buildDirContents = array_diff(scandir($buildDir), array('.', '..'));
      foreach ($buildDirContents as $buildDirEntry) {
        if (is_file($buildDir.$DirSep.$buildDirEntry)) @unlink($buildDir.$DirSep.$buildDirEntry); }
      @rmdir($buildDir); }
    if ($dockerFile !== FALSE && file_exists($dockerFile)) @unlink($dockerFile);
    // / Confirm the build environment is actually gone before reporting success.
    if (!file_exists($dockerFile) && !is_dir($buildDir)) $BuildEnvDeleted = TRUE;
    else {
      $BuildEnvCleaned = FALSE;
      errorEntry('Could not remove the build environment!', 26000, FALSE); }
    if ($Verbose && $BuildEnvDeleted) logEntry('Removed the build environment.'); }
  // / Delete the development documentation if specified by config.php.
  if ($DeleteDevelopmentDocumentation) {
    if ($Verbose) logEntry('Removing the development documentation.');
    if ($changelogFile !== FALSE && file_exists($changelogFile)) @unlink($changelogFile);
    if ($readmeFile !== FALSE && file_exists($readmeFile)) @unlink($readmeFile);
    // / Confirm the development documentation is actually gone before reporting success.
    if (!file_exists($changelogFile) && !file_exists($readmeFile)) $DevDocsDeleted = TRUE;
    else {
      $BuildEnvCleaned = FALSE;
      errorEntry('Could not remove the development documentation!', 26001, FALSE); }
    if ($Verbose && $DevDocsDeleted) logEntry('Removed the development documentation.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dockerFile, $changelogFile, $readmeFile, $buildDir, $buildDirContents, $buildDirEntry);
  return array($BuildEnvCleaned, $BuildEnvDeleted, $DevDocsDeleted); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to sanitize & return the extension to a specified file.
function getExtension($pathToFile) {
  // / Set variables.
  global $PathExt, $EnableMemoryProtection;
  $Pathinfo = '';
  $pathinfoCleaned = FALSE;
  list ($pathinfo, $pathinfoCleaned) = sanitize(pathinfo(strtolower($pathToFile), $PathExt), TRUE);
  if ($pathinfoCleaned) $Pathinfo = trim($pathinfo);
  else errorEntry('Could not process extension for file '.$pathToFile.'!', 300, FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $pathToFile, $pathinfoCleaned, $pathinfo);
  return $Pathinfo;  }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to return the filesize of a specified file.
function getFilesize($File) {
  // / Set variables.
  $Size = @filesize($File);
  // / Determine the most efficient unit of measure to represent the specified value in.
  if ($Size < 1024) $Size = $Size." Bytes";
  elseif (($Size < 1048576) && ($Size > 1023)) $Size = round($Size / 1024, 1)." KB";
  elseif (($Size < 1073741824) && ($Size > 1048575)) $Size = round($Size / 1048576, 1)." MB";
  else $Size = round($Size/1073741824, 1)." GB";
  return $Size; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to sanitize & verify an array of files.
function getFiles($pathToFiles) {
  // / Set variables.
  global $DangerousFiles, $DirSep, $EnableMemoryProtection;
  $Files = $dirtyFileArr = array();
  if (is_dir($pathToFiles)) $dirtyFileArr = @scandir($pathToFiles);
  // / Iterate through each detected file & make sure it's not dangerous before adding it to the output array.
  foreach ($dirtyFileArr as $dirtyFile) {
    $dirtyExt = getExtension($pathToFiles.$DirSep.$dirtyFile);
    // / This filter compared two different shapes & therefore never fired.
    // / getExtension() returns an extension with NO leading dot, & $DangerousFiles holds
    // / dotted extensions such as .html alongside bare filenames such as index.html. A
    // / dotless extension matches neither form, so every dangerous file this list exists to
    // / hide was handed to the caller anyway. The only entry it ever matched was NULL, which
    // / a file with no extension matches loosely, & that is why the fault was not obvious.
    // / convertGui2.php hid the result a second time by filtering on $Allowed, so the list
    // / looked correct on screen. Anything that trusted this function instead got index.html
    // / back & then tried to work with it.
    // / All three forms are tested now. The dotless form is kept so that a file with no
    // / extension is still excluded exactly as it was before.
    $dirtyIsDangerous = FALSE;
    if (in_array(strtolower($dirtyExt), $DangerousFiles)) $dirtyIsDangerous = TRUE;
    if (in_array('.'.strtolower($dirtyExt), $DangerousFiles)) $dirtyIsDangerous = TRUE;
    if (in_array(strtolower($dirtyFile), $DangerousFiles)) $dirtyIsDangerous = TRUE;
    // / Add the selected file to the array of clean files only if it is safe to handle.
    if (!$dirtyIsDangerous && !is_dir($pathToFiles.$DirSep.$dirtyFile)) array_push($Files, $dirtyFile);
    else if ($dirtyExt === '.' or $dirtyExt === '..') errorEntry('Could not display file '.$dirtyFile.'!', 400, FALSE); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dirtyFile, $pathToFiles, $dirtyFileArr, $dirtyExt, $dirtyIsDangerous);
  return $Files; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to return the file time of a specified symlink.
function symlinkmtime($symlinkPath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $Stat = @lstat($symlinkPath);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $symlinkPath);
  return isset($Stat['mtime']) ? $Stat['mtime'] : NULL; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to return the file time of a specified file.
// / Only returns a value if the specified file exists.
// / Returns FALSE when the path cannot be read.
function fileTime($filePath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $Stat = FALSE;
  if (file_exists($filePath)) $Stat = @filemtime($filePath);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $filePath);
  return $Stat; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to test if a folder is empty.
// / Returns TRUE only when the folder exists & holds nothing at all.
// / Returns FALSE when the folder holds anything, or when the path is not a folder.
// / Every directory contains a . and a .. entry, so both are discarded before testing.
function is_dir_empty($dir) {
  // / Set variables.
  global $EnableMemoryProtection;
  $Check = TRUE;
  $contents = array();
  // / Make sure the selected directory is actually a directory.
  if (is_dir($dir)) {
    // / Gather the contents of the directory, discarding the two entries every directory has.
    $contents = array_diff(scandir($dir), array('.', '..'));
    // / Anything left over means the directory holds something.
    if (!empty($contents)) $Check = FALSE; }
  // / A path that is not a directory at all must never be reported as an empty one.
  else $Check = FALSE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dir, $contents);
  return $Check; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to determine whether a folder holds nothing but protected file objects.
// / A hosted session directory always contains an index.html file for document root protection.
// / This overlooks the required files and only looks to see if any user requested files remain.
function isDirEmptyOfUserFiles($path) {
  // / Set variables.
  global $DefaultApps, $EnableMemoryProtection;
  $DirIsEmptyOfUserFiles = FALSE;
  $remaining = array();
  if (is_dir($path)) {
    $remaining = array_diff(scandir($path), array('..', '.'));
    // / Discard every protected file object. Whatever is left belongs to a user.
    $remaining = array_diff($remaining, $DefaultApps);
    if (empty($remaining)) $DirIsEmptyOfUserFiles = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $remaining, $path);
  return $DirIsEmptyOfUserFiles; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify how, or whether, a resource scope can be created.
// / Accepts no arguments.
// / Returns the mode, the systemd-run binary & the environment prefix, in that order.
// / The mode is 'user', 'system' or 'none'.
// / The answer is cached for the request, because the probe launches a process.
// /
// / A user scope is preferred & is tried first.
// / A user manager controls only its own cgroup subtree, which systemd delegates to it. It
// / cannot start a unit as another account, so nothing here can become a way to gain
// / privilege. It needs lingering enabled for the web server user, which -fp does as root.
// /
// / A system scope is accepted but is not recommended.
// / Creating a transient unit on the system bus requires manage-units, & an account holding
// / manage-units can start a transient service with User=root. Granting that to the account
// / which parses uploaded documents hands an attacker a direct route to root. It is
// / supported only because an operator may already have configured it deliberately.
function verifySystemdRun() {
  // / Set variables.
  global $ApacheUser, $Verbose, $EnableMemoryProtection;
  // / The probe result survives the call so it runs once per request rather than per file.
  static $probedMode = NULL;
  static $probedBinary = '';
  static $probedEnvironment = '';
  $ScopeMode = 'none';
  $ScopeBinary = $ScopeEnvironment = '';
  $locatedBinary = $probeCommand = $runtimeDirectory = '';
  $probeOutput = $uidOutput = array();
  $probeExitCode = 1;
  $accountUid = 0;
  if ($probedMode !== NULL) {
    $ScopeMode = $probedMode;
    $ScopeBinary = $probedBinary;
    $ScopeEnvironment = $probedEnvironment; }
  else {
    $locatedBinary = locateDependency('systemd-run');
    if ($locatedBinary === '') warningEntry('systemd-run is not installed. Per conversion limits fall back to scheduling priority only.');
    else {
      // / Build the environment a user manager needs. Without these systemd-run cannot find
      // / the bus for this account & falls straight through to the system bus.
      exec('id -u '.escapeshellarg($ApacheUser).' 2>/dev/null', $uidOutput, $probeExitCode);
      $accountUid = (int)trim(implode('', $uidOutput));
      if ($accountUid > 0) {
        $runtimeDirectory = '/run/user/'.$accountUid;
        $ScopeEnvironment = 'XDG_RUNTIME_DIR='.escapeshellarg($runtimeDirectory)
          .' DBUS_SESSION_BUS_ADDRESS='.escapeshellarg('unix:path='.$runtimeDirectory.'/bus').' '; }
      // / Try the user manager first. Create the smallest possible scope & throw it away.
      $probeCommand = $ScopeEnvironment.'timeout 10 '.escapeshellarg($locatedBinary)
        .' --user --scope --quiet --collect -p MemoryMax=64M -- /bin/true 2>&1';
      exec($probeCommand, $probeOutput, $probeExitCode);
      if ($probeExitCode === 0) {
        $ScopeMode = 'user';
        $ScopeBinary = $locatedBinary;
        if ($Verbose) logEntry('Verified systemd-run in user mode. Per conversion limits are available without any privileged permission.'); }
      else {
        // / Fall back to the system bus only if the operator has already permitted it.
        $probeOutput = array();
        $probeCommand = 'timeout 10 '.escapeshellarg($locatedBinary).' --scope --quiet --collect -p CPUQuota=100% -- /bin/true 2>&1';
        exec($probeCommand, $probeOutput, $probeExitCode);
        if ($probeExitCode === 0) {
          $ScopeMode = 'system';
          $ScopeBinary = $locatedBinary;
          $ScopeEnvironment = '';
          warningEntry('Per conversion limits are using a SYSTEM scope. This account holds systemd manage-units, which is enough to start a unit as root. Enable lingering for '.$ApacheUser.' with the -fp argument & remove that permission.'); }
        else warningEntry('A resource scope could not be created. Run the -fp argument as root to enable lingering for '.$ApacheUser.'. Per conversion limits fall back to scheduling priority only.'); } }
    $probedMode = $ScopeMode;
    $probedBinary = $ScopeBinary;
    $probedEnvironment = $ScopeEnvironment; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $locatedBinary, $probeCommand, $runtimeDirectory, $probeOutput, $uidOutput, $probeExitCode, $accountUid);
  return array($ScopeMode, $ScopeBinary, $ScopeEnvironment); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read a limit pair out of its configured string.
// / Accepts a string of a processor percentage & a memory ceiling in megabytes, comma separated.
// / Returns a validity boolean, the processor percentage & the memory megabytes, in that order.
// / A pair that cannot be read is refused rather than guessed at, because a guessed ceiling
// / is worse than no ceiling. The caller falls back to the configured default.
function parseConversionLimit($limitString) {
  // / Set variables.
  global $EnableMemoryProtection;
  $LimitIsValid = FALSE;
  $CpuPercentage = 0;
  $MemoryMegabytes = 0;
  $limitParts = array();
  $limitParts = explode(',', trim((string)$limitString));
  if (count($limitParts) === 2 && ctype_digit(trim($limitParts[0])) && ctype_digit(trim($limitParts[1]))) {
    $CpuPercentage = (int)trim($limitParts[0]);
    $MemoryMegabytes = (int)trim($limitParts[1]);
    // / A percentage of zero & a ceiling of zero both mean no limit, which is not a limit.
    if ($CpuPercentage > 0 && $MemoryMegabytes > 0) $LimitIsValid = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $limitParts, $limitString);
  return array($LimitIsValid, $CpuPercentage, $MemoryMegabytes); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the conversion type a sandbox profile belongs to.
// / Accepts the sandbox profile name.
// / Returns the conversion type key used by --Maximum Per Conversion Resources--.
// / The mapping is approximate where one tool serves several types. ffmpeg handles audio,
// / video, subtitles & streams, so it is charged at the heaviest of them.
function conversionTypeForProfile($sandboxProfile) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ConversionType = 'Default';
  $cleanProfile = preg_replace('/[^a-z]/', '', strtolower((string)$sandboxProfile));
  if ($cleanProfile === 'libreoffice') $ConversionType = 'Document';
  else if ($cleanProfile === 'imagemagick') $ConversionType = 'Image';
  else if ($cleanProfile === 'ffmpeg') $ConversionType = 'Video';
  else if ($cleanProfile === 'tesseract') $ConversionType = 'OCR';
  else if ($cleanProfile === 'inkscape') $ConversionType = 'SVG';
  else if ($cleanProfile === 'dia') $ConversionType = 'Drawing';
  else if ($cleanProfile === 'calibre') $ConversionType = 'Ebook';
  else if ($cleanProfile === 'meshlab') $ConversionType = 'Model';
  else if ($cleanProfile === 'openscad') $ConversionType = 'Scad';
  else if ($cleanProfile === 'archive') $ConversionType = 'Archive';
  else if ($cleanProfile === 'poppler') $ConversionType = 'Document';
  // / Both scanners answer to one type, the same way libreoffice & poppler both answer to
  // / Document. An administrator tunes what a scan may claim, not which scanner claimed it.
  else if ($cleanProfile === 'clamav') $ConversionType = 'Scan';
  else if ($cleanProfile === 'scancore') $ConversionType = 'Scan';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cleanProfile, $sandboxProfile);
  return $ConversionType; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide the limits one conversion runs under.
// / Accepts the sandbox profile about to run.
// / Returns a validity boolean, the processor percentage & the memory megabytes, in that order.
// / The listener supplies the table when one is running, because only it knows how loaded
// / the host is & how many conversions are already in flight. Its figures are already
// / scaled & floored. With no listener the configured maxima are used unchanged, which is
// / the same behaviour a standalone installation has always had.
function resolveConversionLimit($sandboxProfile) {
  // / Set variables.
  global $EffectiveConversionLimits, $MaximumPerConversionResources, $DefaultPerConversionResources, $Verbose, $EnableMemoryProtection;
  $LimitIsValid = FALSE;
  $CpuPercentage = 0;
  $MemoryMegabytes = 0;
  $conversionType = $limitString = $limitSource = '';
  // / A ceiling this core knows a type cannot run below, consulted only when the
  // / administrator has named no ceiling for that type at all. Anything they DO set wins.
  // /
  // / THIS EXISTS BECAUSE A CORE CAN BE UPDATED WITHOUT config.php BEING UPDATED.
  // / config.php is accepted at or above a minimum version, so an installation that takes a
  // / newer core keeps whatever configuration file it already had. A type this core has
  // / learned about since that file was written is therefore a type the file does not name,
  // / & the general default is what it would fall to.
  // / For Scan that default is fatal rather than merely tight. A ClamAV signature database
  // / is well over a gigabyte once it is loaded, so handing a scan the 512M general default
  // / does not slow it down, it has the kernel kill it, & every virus scan on that server
  // / fails from the moment the core is updated. A local fallback the configuration cannot
  // / lower by omission is what stops a version skew from turning a working scanner off.
  $builtInLimits = array('Scan' => '50,2048');
  $conversionType = conversionTypeForProfile($sandboxProfile);
  // / A table supplied by the listener wins, because it reflects the host right now.
  if (is_array($EffectiveConversionLimits) && isset($EffectiveConversionLimits[$conversionType])) {
    $limitString = (string)$EffectiveConversionLimits[$conversionType];
    $limitSource = 'listener'; }
  else if (is_array($MaximumPerConversionResources) && isset($MaximumPerConversionResources[$conversionType])) {
    $limitString = (string)$MaximumPerConversionResources[$conversionType];
    $limitSource = 'config.php'; }
  else if (isset($builtInLimits[$conversionType])) {
    $limitString = (string)$builtInLimits[$conversionType];
    $limitSource = 'core built-in';
    warningEntry('config.php names no per conversion ceiling for '.$conversionType.'. Using this core\'s built-in '.$limitString.' rather than the general default, which is too small for that type. Add a '.$conversionType.' entry to --Maximum Per Conversion Resources-- to set this yourself.'); }
  else {
    $limitString = (string)$DefaultPerConversionResources;
    $limitSource = 'config.php default'; }
  list ($LimitIsValid, $CpuPercentage, $MemoryMegabytes) = parseConversionLimit($limitString);
  // / An unreadable entry falls back to the default rather than running unlimited.
  if (!$LimitIsValid && $limitSource !== 'config.php default') {
    warningEntry('The per conversion limit for '.$conversionType.' could not be read from '.$limitSource.'. Using the configured default.');
    list ($LimitIsValid, $CpuPercentage, $MemoryMegabytes) = parseConversionLimit((string)$DefaultPerConversionResources);
    $limitSource = 'config.php default'; }
  if ($Verbose && $LimitIsValid) logEntry('Conversion Limit: '.$conversionType.', CPU '.$CpuPercentage.'%, Memory '.$MemoryMegabytes.'M, Source: '.$limitSource.'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $conversionType, $limitString, $limitSource, $builtInLimits, $sandboxProfile);
  return array($LimitIsValid, $CpuPercentage, $MemoryMegabytes); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to wrap a command in a transient systemd scope carrying its limits.
// / Accepts the command to wrap & the sandbox profile about to run, in that order.
// / Returns the wrapped command, or the command unchanged when limiting is unavailable.
// / The scope wraps the SANDBOX, not the tool inside it, so the ceiling covers bubblewrap
// / & every process it goes on to create.
// / CPUQuota is a percentage of ONE processor. 200% is two whole cores.
// / MemoryMax is a hard ceiling. A conversion that reaches it is killed by the kernel &
// / will report a conversion failure rather than a memory error, which is why the
// / configured ceilings should be generous.
// / With no scope mechanism the command is merely deprioritized, which needs no permission
// / at all & still keeps a conversion from starving the web server under contention.
// / This never refuses. A resource ceiling is a courtesy to the host & not a security
// / control, so an unavailable limiter runs the conversion anyway.
function limitCommand($command, $sandboxProfile) {
  // / Set variables.
  global $EnablePerConversionLimits, $Verbose, $EnableMemoryProtection;
  $LimitedCommand = (string)$command;
  $scopeMode = $scopeBinary = $scopeEnvironment = $scopePrefix = '';
  $limitIsValid = FALSE;
  $cpuPercentage = $memoryMegabytes = $nicePriority = 0;
  if (!$EnablePerConversionLimits) $LimitedCommand = (string)$command;
  else {
    list ($limitIsValid, $cpuPercentage, $memoryMegabytes) = resolveConversionLimit($sandboxProfile);
    if (!$limitIsValid) warningEntry('No usable per conversion limit could be resolved. This conversion runs unlimited.');
    else {
      list ($scopeMode, $scopeBinary, $scopeEnvironment) = verifySystemdRun();
      // / --quiet keeps the unit name off stderr, which several callers capture as output.
      // / --collect releases the unit when it exits, so a failed conversion leaves nothing.
      // / Swap is pinned to zero so a memory ceiling cannot be evaded by swapping.
      if ($scopeMode === 'user' or $scopeMode === 'system') {
        $scopePrefix = $scopeEnvironment.escapeshellarg($scopeBinary)
          .($scopeMode === 'user' ? ' --user' : '')
          .' --scope --quiet --collect'
          .' -p CPUQuota='.(int)$cpuPercentage.'%'
          .' -p MemoryMax='.(int)$memoryMegabytes.'M'
          .' -p MemorySwapMax=0'
          .' -- ';
        $LimitedCommand = $scopePrefix.$command; }
      else {
        // / No cgroup is available, so the ceiling cannot be enforced. The conversion is
        // / deprioritized instead, which is weaker but needs nothing from the operator.
        // / A smaller processor share becomes a larger niceness.
        $nicePriority = (int)round(19 - (($cpuPercentage / 100) * 19));
        if ($nicePriority < 0) $nicePriority = 0;
        if ($nicePriority > 19) $nicePriority = 19;
        $LimitedCommand = 'nice -n '.$nicePriority.' '.$command; }
      if ($Verbose) logEntry('Conversion Scope: '.$scopeMode.', CPU '.$cpuPercentage.'%, Memory '.$memoryMegabytes.'M'.($scopeMode === 'none' ? ', enforced as niceness '.$nicePriority.' only' : '').'.'); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $scopeMode, $scopeBinary, $scopeEnvironment, $scopePrefix, $limitIsValid, $cpuPercentage, $memoryMegabytes, $nicePriority, $command, $sandboxProfile);
  return $LimitedCommand; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to supply the sandbox flags one dependency needs & no other should have.
// / Accepts a profile name naming the dependency about to run.
// / Returns the extra bwrap flags for that profile, or an empty string for an unknown one.
// / The base sandbox carries only what every dependency needs. Anything specific to one
// / tool belongs here, so a converter is not handed mounts belonging to another converter.
// / An unrecognized profile gets nothing extra rather than everything, because a missing
// / mount fails loudly & a spare one fails silently.
function sandboxProfileFlags($sandboxProfile) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ProfileFlags = '';
  $cleanProfile = preg_replace('/[^a-z]/', '', strtolower((string)$sandboxProfile));
  // / LibreOffice keeps its bootstrap configuration outside the program directory on
  // / Debian & Ubuntu. sofficerc & bootstraprc under /usr/lib/libreoffice/program are
  // / symlinks into /etc/libreoffice, so binding only /usr leaves them dangling & the
  // / configuration backend aborts with Signal 6 before it opens a file.
  // / This single mount was proven sufficient by bisection. /var/lib/libreoffice was
  // / tested & is NOT required, so it is deliberately absent.
  // / /etc/java is not required either. It is bound so the Java backed filters work &
  // / so javaldx stops warning. Remove it if this installation has no use for Java.
  if ($cleanProfile === 'libreoffice') $ProfileFlags = ' --ro-bind-try /etc/libreoffice /etc/libreoffice'
    .' --ro-bind-try /etc/java /etc/java'
    .' --setenv SAL_USE_VCLPLUGIN svp'
    .' --setenv SAL_DISABLE_OPENCL 1';
  // / ImageMagick reads its policy & delegate configuration from a versioned directory.
  else if ($cleanProfile === 'imagemagick') $ProfileFlags = ' --ro-bind-try /etc/ImageMagick-7 /etc/ImageMagick-7'
    .' --ro-bind-try /etc/ImageMagick-6 /etc/ImageMagick-6'
    .' --ro-bind-try /usr/share/ImageMagick-7 /usr/share/ImageMagick-7'
    .' --ro-bind-try /usr/share/ImageMagick-6 /usr/share/ImageMagick-6';
  // / Tesseract needs its trained language data, which is large & belongs to nothing else.
  else if ($cleanProfile === 'tesseract') $ProfileFlags = ' --ro-bind-try /usr/share/tesseract-ocr /usr/share/tesseract-ocr'
    .' --ro-bind-try /usr/share/tessdata /usr/share/tessdata';
  // / Inkscape carries its own share tree & keeps preferences under the config home.
  else if ($cleanProfile === 'inkscape') $ProfileFlags = ' --ro-bind-try /usr/share/inkscape /usr/share/inkscape'
    .' --ro-bind-try /etc/inkscape /etc/inkscape';
  else if ($cleanProfile === 'dia') $ProfileFlags = ' --ro-bind-try /usr/share/dia /usr/share/dia';
  else if ($cleanProfile === 'calibre') $ProfileFlags = ' --ro-bind-try /usr/share/calibre /usr/share/calibre';
  else if ($cleanProfile === 'meshlab') $ProfileFlags = ' --ro-bind-try /usr/share/meshlab /usr/share/meshlab'
    .' --ro-bind-try /usr/share/pymeshlab /usr/share/pymeshlab';
  else if ($cleanProfile === 'openscad') $ProfileFlags = ' --ro-bind-try /usr/share/openscad /usr/share/openscad';
  // / ffmpeg, the archivers & poppler need nothing beyond the base sandbox.
  else if ($cleanProfile === 'ffmpeg' or $cleanProfile === 'archive' or $cleanProfile === 'poppler' or $cleanProfile === 'generic') $ProfileFlags = '';
  else warningEntry('An unrecognized sandbox profile named '.$cleanProfile.' was requested, so no dependency specific mounts were added.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $ProfileFlags is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $cleanProfile, $sandboxProfile);
  return $ProfileFlags; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to wrap a dependency invocation in a bubblewrap sandbox.
// / Accepts a finished command, the real path of the input, the real path of the output,
// / & a boolean granting network access.
// / Both paths must appear in the command exactly as escapeshellarg() rendered them,
// / because that is the form matched when they are rewritten for the namespace.
// / A path built any other way will not be matched & the command will refer to a location
// / that does not exist inside the sandbox.
// / Returns a permission boolean & a command string ready to run unmodified.
// / The boolean reports whether the command MAY RUN, not whether a sandbox was built.
// / A sandbox that was built returns TRUE with a bwrap command.
// / A sandbox that could not be built returns FALSE when sandboxing is required, so every
// / caller refuses without any caller needing to know why.
// / A sandbox that could not be built returns TRUE with the unwrapped command when
// / sandboxing is not required, & writes a warning naming the conversion as unprotected.
// / A container blocks the namespaces bubblewrap needs unless it was started with
// / --security-opt seccomp=unconfined, so a separate setting governs that case.
// / The directory holding the input is mounted read only at /in.
// / The directory holding the output is mounted writable at /out.
// / When both paths share a directory it is mounted once, writable, at /work.
// / Nothing else from the data location is visible inside the namespace.
// / The mounts are derived from the supplied paths, so the caller never names one.
// / Network access is unshared unless requested, which closes every URL handler at once.
// / OpenSCAD does NOT use this. It needs a whole directory visible to resolve includes.
function sandboxCommand($command, $inputPath, $outputPath, $allowNetwork, $sandboxProfile) {
  // / Set variables.
  global $Verbose, $RequireSandbox, $RequireSandboxOnDocker, $ThrowSandboxWarning, $RunningInContainer, $EnableMemoryProtection;
  $CommandMayRun = FALSE;
  $bwrapBinary = FALSE;
  // / This initializes TRUE rather than FALSE, because for this variable TRUE is the safe
  // / state. It is overwritten unconditionally below & the initial value is never read.
  $sandboxIsRequired = TRUE;
  $SandboxedCommand = $networkFlag = $mountFlags = $workingDir = $profileFlags = '';
  $inputDir = $outputDir = $sandboxInput = $sandboxOutput = '';
  $bwrapBinary = verifyBwrap();
  // / Collect the mounts this one dependency needs. Nothing else receives them.
  $profileFlags = sandboxProfileFlags($sandboxProfile);
  // / A container that CAN build a sandbox still gets one. This only decides what happens
  // / when it cannot.
  $sandboxIsRequired = $RunningInContainer ? $RequireSandboxOnDocker : $RequireSandbox;
  // / Resolve each path to the directory holding it.
  // / The output file does not exist yet, so its directory is used rather than the file.
  $inputDir = dirname($inputPath);
  $outputDir = dirname($outputPath);
  // / Build the mounts & the paths the two files carry inside the namespace.
  // / One directory means one writable mount, because the output has to be written somewhere.
  // / Two directories means a read only input & a writable output, so a dependency that is
  // / exploited while parsing a hostile file cannot modify the file it came from.
  if ($inputDir === $outputDir) {
    $workingDir = '/work';
    $mountFlags = ' --bind '.escapeshellarg($outputDir).' /work';
    $sandboxInput = escapeshellarg('/work/'.basename($inputPath));
    $sandboxOutput = escapeshellarg('/work/'.basename($outputPath)); }
  else {
    $workingDir = '/out';
    $mountFlags = ' --ro-bind '.escapeshellarg($inputDir).' /in'
      .' --bind '.escapeshellarg($outputDir).' /out';
    $sandboxInput = escapeshellarg('/in/'.basename($inputPath));
    $sandboxOutput = escapeshellarg('/out/'.basename($outputPath)); }
  // / A sandbox that could not be built is a policy decision rather than a technical one.
  // / An operator who has deliberately accepted the risk gets the command & a warning.
  // / An operator who has not gets a refusal, & every caller already handles that.
  if ($bwrapBinary === FALSE) {
    $SandboxedCommand = $command;
    if ($sandboxIsRequired) warningEntry('Bubblewrap is unavailable & sandboxing is required, so a conversion was refused. Install bubblewrap, or set '.($RunningInContainer ? '$RequireSandboxOnDocker' : '$RequireSandbox').' to FALSE in config.php to run conversions unprotected.');
    else {
      $CommandMayRun = TRUE;
      if ($ThrowSandboxWarning) warningEntry('Bubblewrap is unavailable & sandboxing is not required, so a conversion will run unprotected.'); } }
  else {
    $CommandMayRun = TRUE;
    // / --unshare-all removes every namespace the command has no business holding.
    // / --share-net gives back ONLY the network, for the one caller that needs it.
    if ($allowNetwork) $networkFlag = ' --share-net';
    // / The rewrite is an exact match on the escaped paths rather than a pattern, so nothing
    // / else in the command can be altered by accident. Neither escaped path can appear
    // / inside the other's replacement, so a single pass is safe.
    $SandboxedCommand = escapeshellarg($bwrapBinary)
      .' --unshare-all'.$networkFlag
      .' --die-with-parent'
      .' --new-session'
      .' --ro-bind /usr /usr'
      .' --ro-bind-try /lib /lib'
      .' --ro-bind-try /lib64 /lib64'
      .' --ro-bind-try /bin /bin'
      .' --ro-bind-try /sbin /sbin'
      .' --ro-bind-try /etc/alternatives /etc/alternatives'
      .' --ro-bind-try /etc/fonts /etc/fonts'
      .' --ro-bind-try /etc/ld.so.cache /etc/ld.so.cache'
      .' --ro-bind-try /etc/ssl/certs /etc/ssl/certs'
      .' --ro-bind-try /opt /opt'
      .' --proc /proc'
      .' --dev /dev'
      .' --tmpfs /tmp'
      .' --tmpfs /run'
      // / A dependency resolves the running user with getpwuid() during startup & throws
      // / out of its configuration backend when the lookup fails. --unshare-all unshares
      // / the user namespace, so without these the lookup has nothing to read & LibreOffice
      // / aborts with Signal 6 before it opens a file.
      .' --ro-bind-try /etc/passwd /etc/passwd'
      .' --ro-bind-try /etc/group /etc/group'
      .' --ro-bind-try /etc/machine-id /etc/machine-id'
      .' --ro-bind-try /etc/localtime /etc/localtime'
      // / --dev builds a minimal device tree with no /dev/shm, which several dependencies
      // / require for shared memory. A tmpfs is enough & stays inside the namespace.
      .' --tmpfs /dev/shm'
      .' --setenv HOME /tmp'
      // / Every writable location a dependency reaches for is pointed at the tmpfs.
      // / Nothing tries to create state outside the namespace & fail.
      .' --setenv XDG_RUNTIME_DIR /tmp'
      .' --setenv XDG_CONFIG_HOME /tmp/.config'
      .' --setenv XDG_CACHE_HOME /tmp/.cache'
      .' --setenv XDG_DATA_HOME /tmp/.local'
      // / Headless rendering with no display server & no OpenCL probing.
      .$profileFlags
      .$mountFlags
      .' --chdir '.$workingDir
      .' '
      .str_replace(
        array(escapeshellarg($inputPath), escapeshellarg($outputPath)),
        array($sandboxInput, $sandboxOutput),
        $command);
    if ($Verbose) logEntry('Sandbox prepared for a dependency invocation.'); }
  // / Wrap whatever was built in its resource ceiling. This is applied to the sandbox
  // / rather than to the tool, so the ceiling covers bubblewrap & everything under it.
  // / An unsandboxed command is still limited, because the host deserves protection even
  // / when the administrator has turned the sandbox off.
  if ($CommandMayRun) $SandboxedCommand = limitCommand($SandboxedCommand, $sandboxProfile);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $bwrapBinary, $sandboxIsRequired, $networkFlag, $mountFlags, $profileFlags, $workingDir, $inputDir, $outputDir, $sandboxInput, $sandboxOutput, $command, $inputPath, $outputPath, $allowNetwork, $sandboxProfile);
  return array($CommandMayRun, $SandboxedCommand); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to locate the absolute path of a dependency binary.
// / Accepts the name of the binary, without a path.
// / Returns the absolute path, or an empty string when the binary cannot be found.
// / A binary installed from a package lands in /usr/bin & one built from source lands in
// / /usr/local/bin, so hardcoding either one is wrong on half of all installations.
// / The web server user's PATH is not the administrator's PATH & often excludes
// / /usr/local/bin entirely, so a bare command name is not reliable either.
// / The candidate directories are searched in order before command -v is consulted, so a
// / source built binary is preferred over a packaged one when both exist & most lookups
// / never spawn a process at all.
// / command -v is used rather than which, because which is a separate package on a minimal
// / installation & reports failure inconsistently between implementations.
// / A name containing a path separator is refused, because this locates a binary by name &
// / a caller supplying a path has already decided where it is.
function locateDependency($binaryName) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $BinaryPath = '';
  $candidateDirs = array('/usr/local/bin', '/usr/local/sbin', '/usr/bin', '/usr/sbin', '/bin', '/sbin');
  $candidateDir = $candidatePath = $commandOutput = '';
  $binaryName = trim($binaryName);
  // / A name is a name. A caller supplying a path is not asking this function anything.
  if ($binaryName === '' or strpos($binaryName, '/') !== FALSE or strpos($binaryName, '\\') !== FALSE) $BinaryPath = '';
  else {
    // / Search the known locations directly before spawning anything.
    foreach ($candidateDirs as $candidateDir) {
      $candidatePath = $candidateDir.'/'.$binaryName;
      if (is_file($candidatePath) && is_executable($candidatePath)) {
        $BinaryPath = $candidatePath;
        break; } }
    // / Fall back to the shell only when the known locations did not have it.
    if ($BinaryPath === '') {
      $commandOutput = trim((string)@shell_exec('command -v '.escapeshellarg($binaryName).' 2>/dev/null'));
      if ($commandOutput !== '' && is_file($commandOutput) && is_executable($commandOutput)) $BinaryPath = $commandOutput; }
    if ($Verbose) logEntry('Dependency lookup: '.$binaryName.' '.($BinaryPath === '' ? 'NOT FOUND' : 'found at '.$BinaryPath).'.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $candidateDirs, $candidateDir, $candidatePath, $commandOutput, $binaryName);
  return $BinaryPath; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to scan an input file or folder for viruses with ClamAV.
function virusScan($path) {
  // / Set variables.
  global $ClamLogFile, $AllowUserVirusScan, $MinimumClamVersion, $Lol, $Lolol, $EnableMemoryProtection;
  $ScanComplete = FALSE;
  $VirusFound = FALSE;
  $returnData = $scanCommand = $clamLogFileDATA = '';
  $clamBinary = FALSE;
  // / Locate & verify the scanner before trusting anything this function is about to say.
  // / A scan that cannot run reports FAILURE, never a clean result. $ScanComplete therefore
  // / starts FALSE & is earned, where it used to start TRUE & be assumed. Every caller
  // / already answers a FALSE with a fatal 'Could not perform a virus scan!', so refusing
  // / here lands in handling that already exists & already behaves correctly.
  $clamBinary = verifyClamVersion(isset($MinimumClamVersion) ? (string)$MinimumClamVersion : '');
  if ($clamBinary === FALSE) errorEntry('ClamAV is missing, too old, or unusable, so '.$path.' was NOT scanned!', 502, FALSE);
  else {
    $ScanComplete = TRUE;
    // / Every argument is escaped. A filename carrying a shell metacharacter would otherwise
    // / be executed rather than scanned, & a filename is the one thing a user controls here.
    // / The binary is the verified path rather than a bare command name, so the clamscan
    // / whose version was checked is provably the clamscan that runs.
    // / The scan runs under the same resource ceiling every conversion runs under. A scan is
    // / one of the most expensive things this application does & it was the only expensive
    // / thing running with nothing above it.
    // / The ceiling wraps clamscan ALONE & not the pipeline. grep costs nothing worth
    // / measuring, & wrapping the pipe would put a shell inside the scope rather than the
    // / scanner, which measures the wrong process & keeps the unit alive for the wrong reason.
    $scanCommand = limitCommand(escapeshellarg($clamBinary).' -r '.escapeshellarg($path), 'clamav');
    $returnData = shell_exec($scanCommand.' | grep FOUND >> '.escapeshellarg($ClamLogFile));
    $clamLogFileDATA = @file_get_contents($ClamLogFile); }
  // / Check if ClamAV found an infection in the specified file.
  if (strpos($clamLogFileDATA, 'Virus Detected') !== FALSE or strpos($clamLogFileDATA, 'FOUND') !== FALSE) {
    // / $virusFound, lower case, was assigned here instead of the $VirusFound this function
    // / returns. A lower case name cannot leave the function it is written in, so the
    // / detection was recorded into a local that nothing ever read & the function reported
    // / VirusFound as FALSE for a file it had just found a virus in. Error 501 is fatal.
    // / Execution stopped anyway & the fault never surfaced, which is exactly how it
    // / survived. The caller's own 'Virus detected!' branch has never once run.
    $ScanComplete = $VirusFound = TRUE;
    // / If the specified file exists, is infected, is not a directory, & $AllowUserVirusScan is set to FALSE then delete the infected file. 
    if (file_exists($path)) if (is_file($path) && !is_dir($path) && !$AllowUserVirusScan) @unlink($path);
    errorEntry('There were potentially infected files detected at '.$path.'!', 500, FALSE);
    errorEntry('ClamAV output the following: '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))), 501, TRUE); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $returnData, $scanCommand, $clamBinary, $clamLogFileDATA, $path);
  return array($ScanComplete, $VirusFound); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove a session directory once nothing of the user's remains in it.
// / Protected file objects such as the enforced index.html are removed only at this point.
function removeEmptiedSessionDir($sessionPath) {
  // / Set variables.
  global $DirSep, $EnableMemoryProtection;
  $SessionDirRemoved = FALSE;
  $leftovers = array();
  $leftover = '';
  if (isDirEmptyOfUserFiles($sessionPath)) {
    $leftovers = array_diff(scandir($sessionPath), array('..', '.'));
    // / Only protected file objects can be left at this point. Remove them with the directory.
    foreach ($leftovers as $leftover) {
      if (is_file($sessionPath.$DirSep.$leftover)) @unlink($sessionPath.$DirSep.$leftover); }
    @rmdir($sessionPath);
    if (!is_dir($sessionPath)) $SessionDirRemoved = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $leftovers, $leftover, $sessionPath);
  return $SessionDirRemoved; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to clean a selection of files.
// / Recursively deletes files.
// / This function is extremely dangerous! Please handle with care.
// / This function refuses to operate on anything outside $ConvertLoc or $ConvertTemp. Both sides of the comparison are passed
// / through realpath() first, so a path containing .. cannot walk out of an approved root
// / while still matching it as a string prefix. If a future edit ever hands this function
// / the wrong variable, the result is a no-op & a FALSE return, not an incident.
function cleanFiles($path) {
  // / Set variables.
  global $ConvertLoc, $ConvertTemp, $DefaultApps, $DirSep, $RequiredCleanupFolders, $EnableMemoryProtection;
  $variableIsSanitized = $CleanSuccess = $pathCheck = $pathIsContained = FALSE;
  $loopCheck = TRUE;
  $dirContents = $allowedRoots = array();
  $dirEntry = $childPath = $realPath = $realRoot = $allowedRoot = '';
  list ($path, $variableIsSanitized) = sanitize($path, FALSE);
  // / Assemble every location this function is permitted to operate inside.
  // / $RequiredCleanupFolders holds the maintenance locations the core needs cleaned.
  $allowedRoots = array($ConvertLoc, $ConvertTemp);
  if (is_array($RequiredCleanupFolders)) $allowedRoots = array_merge($allowedRoots, $RequiredCleanupFolders);
  // / Resolve the supplied path to its true location before any comparison is made.
  // / realpath() returns FALSE for anything that does not exist, which fails the check below.
  $realPath = realpath($path);
  // / Confirm the resolved path sits inside an approved root & is not the root itself.
  // / The trailing separator on the root is required. Without it a sibling directory named
  // / like "ConvertLocEvil" would match "ConvertLoc" as a prefix & be accepted.
  if ($realPath !== FALSE) {
    foreach ($allowedRoots as $allowedRoot) {
      if (empty($allowedRoot)) continue;
      $realRoot = realpath($allowedRoot);
      if ($realRoot !== FALSE && strpos($realPath, $realRoot.$DirSep) === 0) {
        $pathIsContained = TRUE;
        break; } } }
  // / Make sure the selected directory is contained, sanitized, & actually a directory.
  if ($pathIsContained && $variableIsSanitized && is_dir($path)) {
    // / Iterate through each file object in the directory.
    $dirContents = array_diff(scandir($path), array('..', '.'));
    foreach ($dirContents as $dirEntry) {
      // / Build the full path to this child ONCE. Every check below refers to the CHILD,
      // / never to the parent. Testing the parent here would choose the wrong branch.
      $childPath = $path.$DirSep.$dirEntry;
      // / Protected file objects are never touched at any depth.
      if (in_array(basename($childPath), $DefaultApps)) continue;
      // / If the selected file object is a file, delete it.
      if (is_file($childPath)) @unlink($childPath);
      // / If the selected file object is an empty directory, remove it outright.
      elseif (is_dir($childPath) && is_dir_empty($childPath)) @rmdir($childPath);
      // / If the selected file object is a directory with contents, recurse into it.
      // / A failure anywhere below must propagate up, so $loopCheck is never reset to TRUE here.
      elseif (is_dir($childPath)) {
        if (!cleanFiles($childPath)) $loopCheck = FALSE; } }
    // / Once all file objects in the selected directory have been deleted, attempt to delete the selected directory.
    // / The containment check above already prevents reaching any approved root, but this
    // / explicit comparison is retained so the intent survives any future change to that check.
    if (!in_array($path, $allowedRoots, TRUE)) @rmdir($path); }
  // / Check if the function was successful. Note that approved root locations are never deleted.
  $pathCheck = is_dir($path);
  if ($pathCheck && is_dir_empty($path)) $CleanSuccess = TRUE;
  if (!$pathCheck) $CleanSuccess = TRUE;
  // / A failure in any recursive call invalidates the whole operation regardless of what is left here.
  if (!$loopCheck) $CleanSuccess = FALSE;
  // / An uncontained path is never a success. Nothing was cleaned & nothing should report otherwise.
  if (!$pathIsContained) $CleanSuccess = FALSE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $path, $dirContents, $dirEntry, $childPath, $realPath, $realRoot, $allowedRoot, $allowedRoots, $variableIsSanitized, $pathCheck, $pathIsContained, $loopCheck);
  return $CleanSuccess; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove expired session data from a data location.
// / A data location holds daily directories, & each daily directory holds session
// / directories. Only a session directory is ever swept, & only once it is older than the
// / delete threshold. A daily directory is removed once every session inside it is gone.
// / The location being cleaned is authorized in two independent ways before anything is
// / read. The name must be one this function recognizes, & the path must be the one that
// / name refers to. A caller that supplies a valid name with any other path is refused,
// / which is what stops a mistake elsewhere in the core from sweeping the wrong tree.
function cleanDataLoc($dataLoc, $locationName, $deleteThreshold) {
  // / Set variables.
  global $DefaultApps, $ProtectedRootDirs, $DirSep, $Verbose, $ConvertLoc, $ConvertTempDir, $EnableMemoryProtection;
  $LocationDeepCleaned = $cleanAuthorized = FALSE;
  $CleanedLocation = TRUE;
  $dailyDirs = $sessionDirs = array();
  $dailyDir = $sessionDir = $dailyPath = $sessionPath = '';
  $now = time();
  // / Determine whether this clean operation is being requested on a valid target.
  // / The name must be one of the two this function recognizes, & the path must be the one
  // / that name refers to. Both halves must hold or the operation is refused.
  if (($locationName === 'ConvertLoc' && $dataLoc === $ConvertLoc)
   or ($locationName === 'ConvertTempDir' && $dataLoc === $ConvertTempDir)) $cleanAuthorized = TRUE;
  // / A scheduled sweep targets locations this worker is not using, so it cannot match the
  // / two names above. The path must still be one config.php declared, which keeps an
  // / arbitrary path refused exactly as it was before.
  if ($locationName === 'ConvertLocPool' && convertLocIsConfigured($dataLoc)) $cleanAuthorized = TRUE;
  // / An unauthorized target is a failure of the caller & must be reported as one.
  // / Reporting success here would let the core log that a location was cleaned when it was
  // / refused, which is the one outcome this check exists to make visible.
  if (!$cleanAuthorized) {
    $CleanedLocation = FALSE;
    errorEntry('An invalid clean operation has been blocked!', 29, FALSE); }
  // / A location that does not exist is not an error. There is simply nothing to clean.
  else if (!file_exists($dataLoc)) warningEntry('The '.$locationName.' location does not exist at '.$dataLoc.'. Nothing to clean.');
  else {
    if ($Verbose) logEntry('The valid clean operation has been authorized.');
    $dailyDirs = array_diff(scandir($dataLoc), array('..', '.'));
    // / Iterate through each daily folder in the location.
    foreach ($dailyDirs as $dailyDir) {
      // / Validate the folder.
      if (in_array($dailyDir, $DefaultApps)) continue;
      // / A protected directory at this level is not a daily session parent & is never swept.
      // / The LibreOffice profile, the log directory & the update backup live at this level.
      if (in_array($dailyDir, $ProtectedRootDirs, TRUE)) continue;
      $dailyPath = $dataLoc.$DirSep.$dailyDir;
      // / Only directories hold sessions.
      // / Files at this level are left alone entirely.
      if (!is_dir($dailyPath)) continue;
      $sessionDirs = array_diff(scandir($dailyPath), array('..', '.'));
      // / Iterate through each session folder inside this day.
      foreach ($sessionDirs as $sessionDir) {
        if (in_array($sessionDir, $DefaultApps)) continue;
        $sessionPath = $dailyPath.$DirSep.$sessionDir;
        if (!is_dir($sessionPath)) continue;
        // / See if this individual session is due for deletion.
        // / A threshold of zero expires everything, because every session is older than nothing.
        if ($now - fileTime($sessionPath) > ($deleteThreshold * 60)) {
          $LocationDeepCleaned = TRUE;
          if (file_exists($sessionPath)) {
            @chmod($sessionPath, 0777);
            $directoryIterator = new RecursiveDirectoryIterator($sessionPath, RecursiveDirectoryIterator::SKIP_DOTS);
            $iterator = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($iterator as $fileObject) {
              $realPath = $fileObject->getRealPath();
              @chmod($realPath, 0777);
              // / Strip system restrictions (immutable/append-only flags) from every object universally.
              @shell_exec("chattr -i -a " . escapeshellarg($realPath) . " 2>&1");
              if ($fileObject->isDir()) @rmdir($realPath);
              else @unlink($realPath); } }
          // / Track failure if cleanFiles returns false.
          if (!cleanFiles($sessionPath)) {
            $CleanedLocation = FALSE; }
          // / Remove the session shell, including any protected file objects still in it.
          removeEmptiedSessionDir($sessionPath); } }
      // / Remove the daily parent only once every session inside it is gone.
      if (isDirEmptyOfUserFiles($dailyPath)) removeEmptiedSessionDir($dailyPath); }
    // / Log the result.
    if ($Verbose) logEntry('Cleaned the '.$locationName.' location. Removed Files: '.($LocationDeepCleaned ? 'TRUE' : 'FALSE').'.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dailyDirs, $dailyDir, $dailyPath, $sessionDirs, $sessionDir, $sessionPath, $now, $dataLoc, $locationName, $deleteThreshold, $cleanAuthorized, $directoryIterator, $iterator, $fileObject, $realPath);
  return array($CleanedLocation, $LocationDeepCleaned); }
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
  global $ConvertTemp, $DirSep, $MaintainHTAccess, $Verbose, $EnableMemoryProtection;
  $ProtectionIsCurrent = FALSE;
  $protectionPath = $existingContents = $desiredContents = '';
  $carriesMarker = $maintainHtaccess = FALSE;
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
    if (file_exists($protectionPath)) {
      $existingContents = (string)@file_get_contents($protectionPath);
      if (strpos($existingContents, 'HRCONVERT2-POLICY-MARKER') !== FALSE) {
        @unlink($protectionPath);
        if (!file_exists($protectionPath)) warningEntry('--Maintain HTAccess-- is disabled, so the DATA protection file this application wrote at '.$protectionPath.' was removed. The server configuration is now carrying these rules alone. Run the -fp argument & read the DATA exposure line to confirm the tree is still protected.'); } } }
  else {
    $protectionPath = $ConvertTemp.$DirSep.'.htaccess';
    $desiredContents = dataProtectionContents();
    if (file_exists($protectionPath)) {
      $existingContents = (string)@file_get_contents($protectionPath);
      $carriesMarker = (strpos($existingContents, 'HRCONVERT2-POLICY-MARKER') !== FALSE); }
    // / Already exactly right. This is the case on every request after the first.
    if ($existingContents === $desiredContents) $ProtectionIsCurrent = TRUE;
    // / Somebody else's file. Leave it completely alone & say nothing on every request.
    else if ($existingContents !== '' && !$carriesMarker) $ProtectionIsCurrent = TRUE;
    else {
      $bytesWritten = @file_put_contents($protectionPath, $desiredContents);
      if ($bytesWritten === strlen($desiredContents)) {
        @chmod($protectionPath, 0644);
        $ProtectionIsCurrent = TRUE;
        // / Worth a warning rather than a normal entry, because it means this installation
        // / was running with a protection file that did not match this release, & on the
        // / upgrade path from v3.8.6 that file was returning 500 for every download.
        warningEntry('The DATA protection file at '.$protectionPath.' did not match this release & was rewritten. If downloads or share links were returning a server error, that is now corrected.'); }
      // / A failed write is not fatal. The tree may be exposed or may be broken, & the
      // / exposure check is what establishes which, so this only records that it happened.
      else if ($Verbose) logEntry('The DATA protection file at '.$protectionPath.' could not be written.'); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $protectionPath, $existingContents, $desiredContents, $carriesMarker, $maintainHtaccess, $bytesWritten);
  return $ProtectionIsCurrent; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to create required directories if they do not exist.
// / Maintenance folders are emptied by passing their children to cleanFiles().
// / cleanFiles() refuses to operate on an approved root, so the root itself is never passed.
// / Ownership is corrected only when running as root, because a directory created by a
// / root command line invocation would otherwise be one the web server user can neither
// / write into nor sweep. chown fails silently for any other user, so the guard is what
// / keeps a normal web request from attempting something it cannot do.
function verifyRequiredDirs() {
  // /  Set variables.
  global $ConvertLoc, $RequiredDirs, $RequiredIndexes, $RequiredCleanupFolders, $Verbose, $PermissionLevels, $ApacheUser, $DirSep, $InstLoc, $RunningAsRoot, $EnableMemoryProtection;
  $RequiredDirsExist = TRUE;
  $cleanupContents = array();
  $requiredDir = $requiredIndex = $requiredCleanupFolder = $cleanupEntry = $cleanupPath = '';
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
      if ($RunningAsRoot) {
        @chmod($requiredDir, $PermissionLevels);
        @chown($requiredDir, $ApacheUser);
        @chgrp($requiredDir, $ApacheUser); }
      if ($Verbose) logEntry('Verified a directory at '.$requiredDir.'.'); }
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
    // / A folder owned by root cannot be emptied by anyone else, so ownership is corrected
    // / before the contents are removed rather than after.
    if ($RunningAsRoot) {
      @chmod($requiredCleanupFolder, $PermissionLevels);
      @chown($requiredCleanupFolder, $ApacheUser);
      @chgrp($requiredCleanupFolder, $ApacheUser); }
    foreach ($cleanupContents as $cleanupEntry) {
      $cleanupPath = $requiredCleanupFolder.$DirSep.$cleanupEntry;
      @chmod($cleanupPath, $PermissionLevels);
      if (is_file($cleanupPath)) @unlink($cleanupPath);
      elseif (is_dir($cleanupPath)) cleanFiles($cleanupPath); }
    // / The folder itself is removed only once its contents are gone.
    @rmdir($requiredCleanupFolder); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $requiredDir, $requiredIndex, $requiredCleanupFolder, $cleanupEntry, $cleanupPath, $cleanupContents);
  return array($RequiredDirsExist, $RequiredDirs); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to work out which release to fetch & the URL that will deliver it.
// / Three targets are supported & each resolves to a different kind of URL.
// / A version such as v3.6.8 resolves to that tag's tarball directly. A tag that does
// / not exist produces a 404 at download time, which is the correct failure.
// / latest asks the GitHub API which tag is newest, then resolves to that tag.
// / edge resolves to the current state of the master branch. A branch tarball carries
// / whatever version stamp master happens to hold, which may match no release at all.
// / The API call is the only network request this function makes & is only made for latest.
function resolveUpdateTarget($requestedVersion) {
  // / Set variables.
  global $Verbose, $UpdateSourceRepository, $EnableMemoryProtection;
  $TargetResolved = FALSE;
  $TargetVersion = $TargetURL = $apiURL = $apiResponse = $apiMatches = '';
  $curlCommand = $curlOutput = '';
  $curlExitCode = 1;
  $requestedVersion = strtolower(trim($requestedVersion));
  // / The current state of the master branch. No tag & no version guarantee.
  if ($requestedVersion === 'edge') {
    $TargetVersion = 'edge';
    $TargetURL = 'https://github.com/'.$UpdateSourceRepository.'/archive/refs/heads/master.tar.gz';
    $TargetResolved = TRUE; }
  // / The newest tag, whatever it currently is. One API call resolves the name.
  else if ($requestedVersion === 'latest') {
    $apiURL = 'https://api.github.com/repos/'.$UpdateSourceRepository.'/releases/latest';
    // / A user agent is mandatory. The GitHub API refuses a request without one.
    // / -L follows the redirect chain, which is what a plain wget could not do.
    $curlCommand = 'curl -L -s --max-time 30 --max-filesize 1048576'
      .' -H '.escapeshellarg('User-Agent: HRConvert2-Updater')
      .' -H '.escapeshellarg('Accept: application/vnd.github+json')
      .' '.escapeshellarg($apiURL).' 2>/dev/null';
    $apiResponse = shell_exec($curlCommand);
    // / Read the tag name out of the response without parsing the whole document.
    // / json_decode would also work. This avoids depending on the shape of the rest of it.
    if (is_string($apiResponse) && preg_match('/"tag_name"\s*:\s*"([^"]+)"/', $apiResponse, $apiMatches)) {
      $TargetVersion = $apiMatches[1];
      $TargetURL = 'https://github.com/'.$UpdateSourceRepository.'/archive/refs/tags/'.$TargetVersion.'.tar.gz';
      $TargetResolved = TRUE; }
    else errorEntry('Could not determine the latest release from the update source!', 29000, FALSE); }
  // / An explicit tag. Anything that is not edge or latest is treated as one.
  // / The tag is used in a URL, so it is checked against a strict pattern first.
  else {
    if (preg_match('/^v?[0-9]+\.[0-9]+\.[0-9]+$/', $requestedVersion)) {
      $TargetVersion = (strpos($requestedVersion, 'v') === 0) ? $requestedVersion : 'v'.$requestedVersion;
      $TargetURL = 'https://github.com/'.$UpdateSourceRepository.'/archive/refs/tags/'.$TargetVersion.'.tar.gz';
      $TargetResolved = TRUE; }
    else errorEntry('The requested update target is not a recognized version!', 29001, FALSE); }
  if ($Verbose && $TargetResolved) logEntry('Update target resolved to '.$TargetVersion.'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $apiURL, $apiResponse, $apiMatches, $curlCommand, $curlOutput, $curlExitCode, $requestedVersion);
  return array($TargetResolved, $TargetVersion, $TargetURL); }
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
  // / -L follows redirects. -f fails on an HTTP error rather than writing the error page.
  // / --max-filesize refuses a package larger than the configured ceiling.
  // / --max-time bounds the whole transfer, not just the connection.
  $curlCommand = 'curl -L -f -s'
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
  $oldValues = $newContents = $mergedContents = $matches = array();
  $settingName = $settingValue = $exportedValue = '';
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
  purgeSensitiveMemory($EnableMemoryProtection, $oldContents, $newContents, $mergedContents, $matches, $oldValues, $settingName, $settingValue, $exportedValue, $bytesWritten, $oldConfigPath, $newConfigPath);
  return array($ConfigMerged, $PreservedSettings, $ChangedArrays); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to prove that an installation actually runs.
// / Called after the swap. The new installation is asked to report its own version as a
// / separate process. A core that cannot parse, cannot load its config, & cannot reach
// / the command line branch will not answer, & that is the condition worth catching.
// / A file count or a directory listing proves nothing about whether the code executes.
function validateInstallation($installPath) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $InstallationIsValid = FALSE;
  $validateCommand = '';
  $validateOutput = array();
  $validateExitCode = 1;
  $validateCommand = 'php '.escapeshellarg($installPath.'/convertCore.php').' -v 2>&1';
  exec($validateCommand, $validateOutput, $validateExitCode);
  // / The exit code proves it ran. The marker proves it ran far enough to report.
  if ($validateExitCode === 0 && is_array($validateOutput)) {
    if (strpos(implode(' ', $validateOutput), 'Core version') !== FALSE) $InstallationIsValid = TRUE; }
  if ($Verbose) logEntry('Installation validation: '.($InstallationIsValid ? 'PASSED' : 'FAILED').', exit code '.$validateExitCode.'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $validateCommand, $validateOutput, $validateExitCode, $installPath);
  return $InstallationIsValid; }
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
function updateApplication($requestedVersion) {
  // / Set variables.
  global $InstLoc, $ProprietaryLoc, $DirSep, $HRConvertVersion, $AutoUpdateTargetVersion, $EnableAutoUpdates, $BackupLoc, $RunningAsRoot, $ApacheUser, $PermissionLevels, $ConvertDir, $Lol, $EnableMemoryProtection;
  $UpdateSucceeded = $targetResolved = $packageDownloaded = $configMerged = $installationIsValid = FALSE;
  $updatePermissionsFixed = FALSE;
  $updatePathsCorrected = 0;
  $swapCompleted = $rolledBack = FALSE;
  $targetVersion = $targetURL = $workDir = $downloadPath = $extractedDir = $stagedDir = $oldDir = $backupOutput = '';
  $preservedSettings = $changedArrays = $extractOutput = $extractedRoots = array();
  $extractExitCode = $backupExitCode = 1;
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
      logEntry('Update requested but '.$targetVersion.' is already installed.'); } }
  // / Download & stage the release.
  if ($targetResolved) {
    $workDir = $ConvertDir.$DirSep.'temp'.$DirSep.'hrc2update-'.bin2hex(random_bytes(8));
    $downloadPath = $workDir.$DirSep.'package.tar.gz';
    $stagedDir = $workDir.$DirSep.'staged';
    if (!@mkdir($workDir, $PermissionLevels, TRUE)) errorEntry('Could not create a working directory for the update!', 29010, FALSE);
    else {
      print($Lol.'Downloading '.$targetVersion.' ...'.$Lol);
      $packageDownloaded = downloadUpdatePackage($targetURL, $downloadPath); } }
  // / Extract the package & locate the single directory GitHub wraps every archive in.
  if ($packageDownloaded) {
    @mkdir($stagedDir, $PermissionLevels, TRUE);
    exec('tar -xzf '.escapeshellarg($downloadPath).' -C '.escapeshellarg($stagedDir).' 2>&1', $extractOutput, $extractExitCode);
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
    $stagedDir = $ProprietaryLoc.$DirSep.'HRConvert2.new';
    if (is_dir($stagedDir)) exec('rm -rf '.escapeshellarg($stagedDir).' 2>&1');
    if (!@rename($extractedDir, $stagedDir)) {
      // / rename() across filesystems fails, so fall back to a copy.
      exec('cp -a '.escapeshellarg($extractedDir).' '.escapeshellarg($stagedDir).' 2>&1'); }
    if (!is_dir($stagedDir)) errorEntry('Could not stage the update beside the installation!', 29014, FALSE);
    else {
      // / DATA holds live user sessions & is moved rather than copied.
      if (is_dir($InstLoc.$DirSep.'DATA')) @rename($InstLoc.$DirSep.'DATA', $stagedDir.$DirSep.'DATA');
      // / Match the ownership & permissions the installation is expected to carry.
      if ($RunningAsRoot) {
        exec('chown -R '.escapeshellarg($ApacheUser).':'.escapeshellarg($ApacheUser).' '.escapeshellarg($stagedDir).' 2>&1'); }
      exec('chmod -R 0755 '.escapeshellarg($stagedDir).' 2>&1');
      print('Swapping installation ...'.$Lol);
      // / Two atomic renames. There is no moment where the installation is incomplete.
      if (!@rename($InstLoc, $oldDir)) errorEntry('Could not move the existing installation aside!', 29015, FALSE);
      else if (!@rename($stagedDir, $InstLoc)) {
        // / The installation is currently absent. Put it back immediately.
        @rename($oldDir, $InstLoc);
        errorEntry('Could not move the update into place! The previous installation was restored.', 29016, FALSE); }
      else $swapCompleted = TRUE; } }
  // / Validate the swapped installation & roll back if it cannot run.
  if ($swapCompleted) {
    print('Validating ...'.$Lol);
    $installationIsValid = validateInstallation($InstLoc);
    if (!$installationIsValid) {
      warningEntry('The updated installation failed validation. Rolling back.');
      // / Return the data directory to the installation being restored.
      if (is_dir($InstLoc.$DirSep.'DATA')) @rename($InstLoc.$DirSep.'DATA', $oldDir.$DirSep.'DATA');
      exec('rm -rf '.escapeshellarg($InstLoc).' 2>&1');
      // / Rollback is a single rename because the previous installation never left the
      // / filesystem it lived on. This is the reason .old exists at all.
      if (@rename($oldDir, $InstLoc)) {
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
  if ($UpdateSucceeded && is_dir($oldDir)) {
    if (is_dir($BackupLoc)) exec('rm -rf '.escapeshellarg($BackupLoc).' 2>&1');
    exec('cp -a '.escapeshellarg($oldDir).' '.escapeshellarg($BackupLoc).' 2>&1', $backupOutput, $backupExitCode);
    if ($backupExitCode === 0 && is_dir($BackupLoc)) {
      if (is_dir($BackupLoc.$DirSep.'DATA')) exec('rm -rf '.escapeshellarg($BackupLoc.$DirSep.'DATA').' 2>&1');
      if ($RunningAsRoot) exec('chown -R '.escapeshellarg($ApacheUser).':'.escapeshellarg($ApacheUser).' '.escapeshellarg($BackupLoc).' 2>&1');
      logEntry('Previous installation preserved at '.$BackupLoc.'.'); }
    else warningEntry('Could not preserve the previous installation at '.$BackupLoc.'. It will be discarded.');
    // / The previous installation must not remain inside the web root under any outcome.
    exec('rm -rf '.escapeshellarg($oldDir).' 2>&1'); }
  // / Correct everything the new files arrived with. An update ships a fresh tree owned by
  // / whoever unpacked it, & a release that changes a policy, a service unit or a required
  // / version leaves the installed copies behind. Running the repair here is the difference
  // / between an update that works & one that needs an operator to know to run -fp next.
  if ($UpdateSucceeded) {
    print($Lol.'Correcting permissions, policies & the service unit for the new release.'.$Lol);
    list ($updatePermissionsFixed, $updatePathsCorrected) = fixManagedPermissions();
    if (!$updatePermissionsFixed) warningEntry('The update completed but permissions & policies could not be corrected. Run the -fp argument as root.'); }
  // / Report the outcome.
  if ($UpdateSucceeded) {
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
  purgeSensitiveMemory($EnableMemoryProtection, $targetResolved, $packageDownloaded, $configMerged, $installationIsValid, $swapCompleted, $rolledBack, $targetVersion, $targetURL, $workDir, $downloadPath, $extractedDir, $stagedDir, $oldDir, $preservedSettings, $changedArrays, $extractOutput, $extractedRoots, $extractExitCode, $requestedVersion, $Lol, $updatePermissionsFixed, $updatePathsCorrected);
  return $UpdateSucceeded; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to simply return whether or not an IP is private or public.
// / Used for server-side request forgery (SSRF) protection.
// / Returns TRUE only for publicly routable addresses. Anything private, reserved, loopback,
// / or link-local returns FALSE, as does any string that is not a valid IP at all.
// / This is the single source of truth for IP safety. Do not duplicate this logic.
function isPubliclyRoutableIP($ip) {
  // / Set variables.
  $Check = (bool)filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
  return $Check; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to perform DNS lookups without following redirects.
// / Also used to detect if a host resolves to a LAN segment or private IP range.
// / Note that this has an intentionally redundant check for whether or not the IP is private.
// / A private/reserved answer means this host is untrustworthy regardless of what else it returned.
// / $URLIP will return FALSE if the lookup failed or if a DNS rebind attack is suspected.
// / $StreamContainsLAN will return TRUE if the response contained a local or reserved IP address.
// / $LookupFailed will return TRUE if no DNS response was received or FALSE if DNS succeeded.
function dnsLookup($URLHost) {
  // / Set variables.
  global $EnableMemoryProtection;
  $records = $record = array();
  $urlIP = $URLIP = $LookupFailed = $StreamContainsLAN = $isPublic = FALSE;
  // / Perform the actual DNS lookup against the $URLHost.
  $records = @dns_get_record($URLHost, DNS_A + DNS_AAAA);
  // / Check that the records received from the DNS provider were formed properly.
  if (is_array($records) && !empty($records)) {
    foreach ($records as $record) {
      // / Parse the received DNS records.
      $urlIP = $record['ip'] ?? $record['ipv6'] ?? NULL;
      if ($urlIP === NULL) continue;
      $isPublic = isPubliclyRoutableIP($urlIP);
      if ($isPublic) $URLIP = $urlIP;
      else {
        // / A private/reserved answer means this host is untrustworthy regardless of what else it returned.
        // / Discard any safe IP already found. A host answering with both is a rebinding setup, not a partial success.
        $StreamContainsLAN = TRUE;
        $URLIP = FALSE;
        break; } } }
  // / Set a flag to tell if the lookup failed outright.
  else $LookupFailed = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $records, $record, $urlIP, $isPublic);
  return array($URLIP, $StreamContainsLAN, $LookupFailed); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to scan a stream URL received from a stream file such as .m3u8.
// / This function determines whether that URL is safe for FFMPEG to handle.
// / This function performs a DNS lookup on the provided URL but DOES NOT follow redirects.
// / Not following redirects is critical.
// / To perform adequate SSRF protection we must obtain a non-redirected lookup for each remote host.
// / The information obtained here binds downstream dependencies like CURL & FFMPEG to these locations.
function gatherRemoteHostInfo($StreamURL) {
  // / Set variables.
  global $Verbose, $AllowStreamOverHTTP, $EnableMemoryProtection;
  $LookupFailed = $InspectionFailed = TRUE;
  $URLIP = $URLHost = $URLPort = $URLScheme = $StreamContainsLAN = $StreamDNSContainsLAN = $StreamURLResolutionFailed = FALSE;
  $urlIsSanitized = $partsAreSanitized = $schemeIsSanitized = $hostIsSanitized = FALSE;
  $allowedSchemes = array('https');
  $urlParts = array();
  // / Sanitize the supplied URL before anything else looks at it.
  list ($StreamURL, $urlIsSanitized) = sanitize($StreamURL, TRUE);
  if ($Verbose) logEntry('Inspecting Stream URL: '.$StreamURL.'.');
  // / Check if plain http stream URLs are allowed by config.php.
  if ($AllowStreamOverHTTP) array_push($allowedSchemes, 'http');
  // / A URL that could not be sanitized is unresolvable before we even parse it.
  if (!$urlIsSanitized) $StreamURLResolutionFailed = TRUE;
  else {
    // / Parse the provided URL to gather DNS information.
    // / parse_url returns FALSE if the response was seriously malformed.
    $urlParts = parse_url($StreamURL);
    // / If the parse_url response is malformed, then consider the URL unresolvable.
    if (!$urlParts) $StreamURLResolutionFailed = TRUE;
    // / If the parse_url response makes sense, then keep going.
    else {
      list ($urlParts, $partsAreSanitized) = sanitize($urlParts, TRUE);
      // / If the parse_url response is incomplete, then consider the URL unresolvable.
      if (!$partsAreSanitized or empty($urlParts['scheme']) or empty($urlParts['host'])) $StreamURLResolutionFailed = TRUE;
      // / If the parse_url response makes sense, then keep going.
      else {
        // / If the scheme is not supported by config.php, then consider the URL unresolvable.
        list ($URLScheme, $schemeIsSanitized) = sanitize(strtolower($urlParts['scheme']), TRUE);
        if (!$schemeIsSanitized or !in_array($URLScheme, $allowedSchemes, TRUE)) $StreamURLResolutionFailed = TRUE;
        // / If the scheme is supported by config.php, then keep going.
        else {
          // / Detect the host.
          list ($URLHost, $hostIsSanitized) = sanitize(strtolower($urlParts['host']), TRUE);
          if (!$hostIsSanitized) $StreamURLResolutionFailed = TRUE;
          // / Detect the port where applicable.
          // / Be very careful making changes to this code.
          // / This uses the port supplied by the request when available.
          // / When no port was supplied it falls back to 443 for https & 80 for http.
          // / This code prevents dependencies from silently performing their own DNS lookups.
          // / If this code fails to produce a valid host or port, dependencies will ignore our binding.
          // / CURL & FFMPEG cannot be allowed to do lookups that could be spoofed into following redirects.
          else $URLPort = isset($urlParts['port']) ? (int)$urlParts['port'] : ($URLScheme === 'https' ? 443 : 80); } } } }
  // / If we have successfully obtained a host, port & scheme, then resolve the address.
  if (!$StreamURLResolutionFailed) {
    // / If the host is already a literal IP, validate it directly & skip DNS entirely.
    if (filter_var($URLHost, FILTER_VALIDATE_IP)) {
      if (isPubliclyRoutableIP($URLHost)) {
        $URLIP = $URLHost;
        $LookupFailed = FALSE; }
      else $StreamContainsLAN = TRUE; }
    // / Otherwise it is a hostname, so resolve it without following redirects.
    else list($URLIP, $StreamDNSContainsLAN, $LookupFailed) = dnsLookup($URLHost);
    // / A DNS result that was not publicly routable is treated as LAN & will be denied.
    // / This condenses the DNS finding into one flag because anything containing LAN is denied.
    if ($StreamDNSContainsLAN) $StreamContainsLAN = TRUE; }
  // / If any check failed or any required value is empty, then the whole inspection failed.
  if ($StreamURLResolutionFailed or $StreamContainsLAN or $LookupFailed or empty($URLHost) or empty($URLPort) or empty($URLScheme) or empty($URLIP)) $InspectionFailed = TRUE;
  // / If everything passed then consider the inspection to have passed.
  else $InspectionFailed = FALSE;
  // / Write the information obtained to the log file.
  if ($Verbose) logEntry('URL Inspection Result: '.($InspectionFailed ? 'FAILED' : 'PASSED').', Host: '.$URLHost.', Port: '.$URLPort.', Scheme: '.$URLScheme.', Contains LAN: '.($StreamContainsLAN ? 'TRUE' : 'FALSE').', URL Resolution Failed: '.($StreamURLResolutionFailed ? 'TRUE' : 'FALSE').', Lookup Failed: '.($LookupFailed ? 'TRUE' : 'FALSE').'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $allowedSchemes, $urlParts, $StreamDNSContainsLAN, $urlIsSanitized, $partsAreSanitized, $schemeIsSanitized, $hostIsSanitized);
  return array($InspectionFailed, $StreamURLResolutionFailed, $StreamContainsLAN, $LookupFailed, $URLHost, $URLPort, $URLScheme, $URLIP); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to turn a URI found inside a stream file into a complete, absolute URL.
// / Stream files routinely reference segments & nested playlists by relative path, which
// / inherit their missing parts from the URL the PARENT manifest was downloaded from.
// / $ParentURL is empty for the file the user uploaded, because nobody fetched it. A relative
// / URI at that layer therefore resolves against nothing & is refused rather than guessed at.
// / Returns an empty string when the URI cannot be honestly resolved. The caller must deny on empty.
function resolveRemoteURI($StreamURI, $ParentURL) {
  // / Set variables.
  global $EnableMemoryProtection;
  $AbsoluteURL = '';
  $parentParts = array();
  $parentScheme = $parentHost = $parentPort = $parentDir = '';
  $uriIsAbsolute = $parentIsUsable = FALSE;
  $StreamURI = trim($StreamURI);
  // / Case 1. The URI already carries its own scheme, so nothing is inherited & we are done.
  // / Note this deliberately matches ANY scheme, including file: and gopher:, so that
  // / gatherRemoteHostInfo() sees & rejects them rather than us silently mangling them.
  if ($StreamURI !== '' && preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*:#', $StreamURI)) {
    $uriIsAbsolute = TRUE;
    $AbsoluteURL = $StreamURI; }
  // / Everything below here is relative & needs a parent to inherit from.
  // / The user's uploaded file has no parent, so a relative URI at layer 0 is unresolvable.
  if (!$uriIsAbsolute && $StreamURI !== '' && $ParentURL !== '') {
    $parentParts = parse_url($ParentURL);
    if ($parentParts && !empty($parentParts['scheme']) && !empty($parentParts['host'])) $parentIsUsable = TRUE; }
  // / Inherit whatever the relative form is missing from the parent it was found in.
  if ($parentIsUsable) {
    $parentScheme = strtolower($parentParts['scheme']);
    $parentHost = strtolower($parentParts['host']);
    $parentPort = isset($parentParts['port']) ? ':'.(int)$parentParts['port'] : '';
    // / Case 2. Protocol-relative (//cdn.example.com/seg.ts). Inherits the scheme only.
    if (substr($StreamURI, 0, 2) === '//') $AbsoluteURL = $parentScheme.':'.$StreamURI;
    // / Case 3. Root-relative (/hls/seg.ts). Inherits scheme, host & port. The path is replaced outright.
    elseif ($StreamURI[0] === '/') $AbsoluteURL = $parentScheme.'://'.$parentHost.$parentPort.$StreamURI;
    // / Case 4. Plain relative (seg003.ts). Appended to the parent's DIRECTORY, never to its filename.
    else {
      $parentDir = rtrim(dirname($parentParts['path'] ?? '/'), '/');
      $AbsoluteURL = $parentScheme.'://'.$parentHost.$parentPort.$parentDir.'/'.$StreamURI; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $parentParts, $parentScheme, $parentHost, $parentPort, $parentDir, $uriIsAbsolute, $parentIsUsable);
  return $AbsoluteURL; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to gather & validate IPv4 & IPv6 addresses from ANY block of content.
// / Does not perform DNS or any remote validation.
// / This function only validates the syntactical form of IP addresses, and ensures they are not in a reserved range.
// /
// / This is SSRF machinery & it lives in the core on purpose.
// / It knows nothing about streams, playlists or any other format. It is handed bytes &
// / reports which addresses in them are safe to contact. A pipeline that needs to decide
// / whether a reference is safe to follow calls this rather than writing its own.
// / A PIPELINE MUST NEVER OWN SSRF PROTECTION. A community author writing a pipeline that
// / fetches something should get this for free & must not be able to opt out of it by
// / accident. That is the same reason verifyFile() & virusScan() stayed in the core.
function inspectContentForIPs($streamFileContents) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ipMatch = '';
  $IPCount = 0;
  $ipMatchesTemp = $ip4Temp = $ip6Temp = $IPMatches = array();
  $StreamContainsLAN = $StreamContainsIP = FALSE;
  // / Regex pattern to extract matching IPv4 formats.
  $ip4Pattern = '/\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b/';
  // / Regex pattern to extract matching IPv6 formats.
  // / Loose candidate matcher. Finds hex-and-colon runs that MIGHT be IPv6.
  // / Deliberately over-matches; filter_var() (called below) is the authority on validity.
  $ip6Pattern = '/(?<![0-9A-Fa-f:])(?:[0-9A-Fa-f]{0,4}:){2,7}[0-9A-Fa-f]{0,4}(?::[0-9]{1,3}(?:\.[0-9]{1,3}){3})?(?![0-9A-Fa-f:])/';
  // / Check if the $streamFile contains any IP addresses, if those addresses are valid, & if they fall into a private subnet.
  // / $ipMatchesTemp = flat array of every potential IP string matched by either pattern.
  // / $IPMatches = flat array of every valid, publicly routable IP confirmed by filter_var.
  preg_match_all($ip4Pattern, $streamFileContents, $ip4Temp);
  preg_match_all($ip6Pattern, $streamFileContents, $ip6Temp);
  $ipMatchesTemp = array_merge($ip4Temp[0], $ip6Temp[0]);
  if (!empty($ipMatchesTemp)) {
    foreach ($ipMatchesTemp as $ipMatch) {
      // / Strip brackets from URL-form IPv6 (https://[::1]/) before validating.
      $ipMatch = trim($ipMatch, '[]');
      // / Not a real IP at all. Probably a regex over-match. Discard silently, do NOT flag.
      if (!filter_var($ipMatch, FILTER_VALIDATE_IP)) continue;
      // / Set a flag if the entry that was found is a genuine raw IP address.
      $StreamContainsIP = TRUE;
      // / Check whether each extracted IP is on a local / private subnet, or something we should not be probing.
      if (isPubliclyRoutableIP($ipMatch)) array_push($IPMatches, $ipMatch);
      else {
        // / Set a flag if the IP address that was found appears to be on a local or private subnet.
        $StreamContainsLAN = TRUE;
        // / Stop as soon as we find a dangerous IP address. There is no need to continue validating a malicious file.
        break; } } }
  // / Count the number of publicly routable IPs found before we stopped.
  $IPCount = count($IPMatches);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $ipMatch, $ipMatchesTemp, $ip4Pattern, $ip6Pattern, $streamFileContents, $ip4Temp, $ip6Temp);
  return array($IPMatches, $IPCount, $StreamContainsLAN, $StreamContainsIP); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to gather & validate domain names from ANY block of content.
// / Does not perform DNS or any remote validation.
// / This function only validates the syntactical form of domain names.
// / Preserves http:// and https:// as the only allowed protocols.
// /
// / THIS IS SSRF MACHINERY & IT LIVES IN THE CORE ON PURPOSE. See inspectContentForIPs().
function inspectContentForDomains($streamFileContents) {
  // / Set variables.
  global $EnableMemoryProtection;
  $DomainCount = 0;
  $DomainNames = $domainMatches = array();
  $StreamContainsDomain = FALSE;
  // / Matches only absolute http/https URLs & captures the hostname portion in group 1.
  // / Skips any userinfo (user:pass@) so the REAL host is captured, not the decoy before the @.
  $domainPattern = '/\bhttps?:\/\/(?:[^\/?#@\s]*@)?([a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*\.[a-zA-Z]{2,63})(?=[:\/?#]|\s|$)/i';
  // / Check if the $streamFile contains any domain names, and whether those domain names are valid.
  if (preg_match_all($domainPattern, $streamFileContents, $domainMatches)) {
    // / Set a flag if the entry that was found appears to be a domain name.
    $StreamContainsDomain = TRUE;
    // / $domainMatches[1] holds the bare hostnames & is the only row safe to hand to DNS.
    $DomainNames = $domainMatches[1];
    // / $domainMatches[0] holds the FULL match, including scheme & any user:pass@ decoy.
    // / $domainMatches[0] is NOT safe to use for DNS. It is only referenced here for counting.
    $DomainCount = count($domainMatches[0]); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $domainPattern, $streamFileContents, $domainMatches);
  return array($DomainNames, $DomainCount, $StreamContainsDomain); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to download a single remote file to local disk for inspection.
// /
// / This is the only place in the application that fetches a remote file for a pipeline.
// / It pins the resolved IP, refuses redirects, refuses DNS rebinding, & reads a bounded
// / number of bytes. Every one of those is a thing a pipeline author writing their own curl
// / call would forget. It stays in the core so that they cannot.
// / A config note worth knowing before a second pipeline uses this.
// / $AllowStreamOverHTTP, $StreamConnectionTimeout, $MaxStreamInspectionFileSize &
// / $StreamTemp are all named for streaming because streaming was the only caller when they
// / were written. They govern EVERY caller of this function, not only the Stream pipeline.
// / They were not renamed because an administrator's config.php names them & a rename would
// / silently reset those settings on every installation. If a second pipeline starts
// / fetching remote files, that shared governance should be a deliberate decision rather
// / than something discovered afterwards.
// / Files are saved with a numeric name & no extension so nothing downstream is fooled by a filename.
// / The original URI is preserved in the stream record, not on disk.
// / This function does NOT follow redirects.
// / This function does NOT let CURL perform its own DNS lookup.
// / Only the first $MaxStreamInspectionFileSize bytes are fetched.
// / We are classifying files here, not streaming them.
// / $StreamConnectionTimeout is documented in seconds & is used directly.
// / $StreamWatchTimeout is documented in minutes & is converted once here.
function downloadRemoteFileForInspection($StreamURL, $URLHost, $URLPort, $URLIP, $URLScheme, $FileNumber) {
  // / Set variables.
  global $Verbose, $AllowStreamOverHTTP, $StreamConnectionTimeout, $StreamWatchTimeout, $DirSep, $MaxStreamInspectionFileSize, $StreamTemp, $EnableMemoryProtection;
  $DownloadFailed = $StreamFileTruncated = TRUE;
  $pinIsComplete = FALSE;
  $curlOutput = array();
  $curlCommand = '';
  $curlExitCode = 1;
  $downloadedBytes = 0;
  // / Sequential name for every file saved to StreamTemp.
  // / This number never resets during a walk.
  // / A layer 2 file must never overwrite a layer 1 file.
  $LocalStreamPath = $StreamTemp.$DirSep.$FileNumber;
  $protoString = 'https';
  // / Only widen to plain http when config allows it AND this specific URL actually uses it.
  if ($AllowStreamOverHTTP && $URLScheme === 'http') $protoString = 'http,https';
  // / Refuse outright if any component needed for the DNS pin is missing.
  // / An empty component produces a malformed --resolve entry which CURL silently ignores.
  // / CURL would then perform its own lookup & every rebinding protection would be lost.
  if (!empty($URLHost) && !empty($URLPort) && !empty($URLIP) && !empty($StreamURL)) $pinIsComplete = TRUE;
  else if ($Verbose) logEntry('Stream download refused: incomplete validation data for '.$StreamURL.'.');
  if ($pinIsComplete) {
    // / Build the command with every user influenced value escaped.
    // / There is no -L flag, so redirects are never followed.
    // / The -r flag requests only the first chunk of the file.
    // / The --max-filesize flag enforces the same ceiling when a host ignores -r.
    $curlCommand = 'curl'
      .' --resolve '.escapeshellarg($URLHost.':'.$URLPort.':'.$URLIP)
      .' --proto '.escapeshellarg('='.$protoString)
      .' --proto-redir '.escapeshellarg('='.$protoString)
      .' -r '.escapeshellarg('0-'.((int)$MaxStreamInspectionFileSize - 1))
      .' --max-filesize '.(int)$MaxStreamInspectionFileSize
      .' --connect-timeout '.(int)$StreamConnectionTimeout
      .' -m '.((int)$StreamWatchTimeout * 60)
      .' -sS -o '.escapeshellarg($LocalStreamPath)
      .' -- '.escapeshellarg($StreamURL).' 2>&1';
    exec($curlCommand, $curlOutput, $curlExitCode);
    // / A successful download requires exit code 0 AND a file that exists with content.
    // / Either one alone is not proof of success.
    if ($curlExitCode === 0 && file_exists($LocalStreamPath)) {
      $downloadedBytes = filesize($LocalStreamPath);
      if ($downloadedBytes > 0) $DownloadFailed = FALSE;
      // / A file that filled the entire budget was almost certainly cut short.
      // / We are only holding part of it, so we cannot claim to have inspected the whole thing.
      if ($downloadedBytes < (int)$MaxStreamInspectionFileSize) $StreamFileTruncated = FALSE; }
    else if ($Verbose) logEntry('Stream download failed for '.$StreamURL.'. CURL exit code: '.$curlExitCode.'.');
    // / Log the outcome of this single download.
    if ($Verbose) logEntry('Stream Download Result: '.($DownloadFailed ? 'FAILED' : 'SUCCESS').', File: '.$FileNumber.', Bytes: '.$downloadedBytes.', Truncated: '.($StreamFileTruncated ? 'TRUE' : 'FALSE').'.'); }
  // / Never report a path we did not successfully write to.
  // / The caller reads this file immediately after this function returns.
  if ($DownloadFailed) $LocalStreamPath = '';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $curlCommand, $curlOutput, $protoString, $curlExitCode, $downloadedBytes, $pinIsComplete);
  return array($DownloadFailed, $LocalStreamPath, $StreamFileTruncated); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to supervise a backgrounded FFMPEG stream conversion after the user has been served.
// / Polls the process & kills it once $StreamWatchTimeout minutes have elapsed.
// / This is the only thing preventing an abandoned stream from running until PHP or the OS intervenes.
function waitForStream($StreamPID, $newPathname) {
  // / Set variables.
  global $Verbose, $StreamWatchTimeout, $EnableMemoryProtection;
  $StreamCompleted = $StreamKilled = $pidIsUsable = FALSE;
  $psOutput = array();
  $ElapsedSeconds = 0;
  $pollInterval = 2;
  // / Config states this value in MINUTES. Convert once, here, so the loop reads in seconds.
  $timeoutSeconds = (int)$StreamWatchTimeout * 60;
  // / Nothing to supervise without a real process id.
  if ((int)$StreamPID > 0) $pidIsUsable = TRUE;
  if ($pidIsUsable) {
    if ($Verbose) logEntry('Supervising stream PID '.$StreamPID.' for up to '.$timeoutSeconds.' seconds.');
    while ($ElapsedSeconds < $timeoutSeconds) {
      $psOutput = array();
      // / A ps listing with only its header row means the process is gone.
      exec('ps -p '.(int)$StreamPID, $psOutput);
      if (count($psOutput) < 2) {
        $StreamCompleted = TRUE;
        break; }
      sleep($pollInterval);
      $ElapsedSeconds += $pollInterval; }
    // / Still running after the full watch window. Terminate it.
    if (!$StreamCompleted) {
      exec('kill -9 '.(int)$StreamPID);
      $StreamKilled = TRUE;
      if ($Verbose) logEntry('Stream PID '.$StreamPID.' exceeded the watch timeout & was terminated.'); }
    else if ($Verbose) logEntry('Stream PID '.$StreamPID.' finished after '.$ElapsedSeconds.' seconds. Output exists: '.(file_exists($newPathname) ? 'TRUE' : 'FALSE').'.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $psOutput, $pollInterval, $timeoutSeconds, $pidIsUsable);
  return array($StreamCompleted, $StreamKilled, $ElapsedSeconds); }
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
  global $SupportedConversionTypes, $PipelineManagerActive, $WaitForStream, $StreamPID, $StreamOutputPath, $Verbose, $EnableMemoryProtection;
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
    // / or version mismatched Pipeline Manager costs an installation nothing but a warning.
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
    if ($PipelineManagerActive && function_exists('familyHasPipeline')) $pipelineServesThisConversion = familyHasPipeline($type, $inputExtension, $extension);
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
function verifyFile($file, $UserFilename, $UserExtension, $clean, $copy, $skip) {
  global $DangerousFiles, $ConvertDir, $ConvertTempDir, $Allowed, $Verbose, $EnableMemoryProtection;
  $FileIsVerified = $Pathname = $OldPathname = $NewPathname = $variableIsSanitized = FALSE;
  // / If the $UserFilename is blank then use the original filename instead.
  $OldExtension = getExtension($file);
  if ($UserFilename === '') $UserFilename = trim(str_replace('.'.$OldExtension, '', $file), '.');
  // / Check to make sure all iteration specific required variables are properly sanitized.
  list ($file, $variableIsSanitized) = sanitize($file, FALSE);
  list ($Pathname, $variableIsSanitized) = sanitize($ConvertTempDir.$file, FALSE);
  list ($OldPathname, $variableIsSanitized) = sanitize($ConvertDir.$file, FALSE);
  // / Check if the selected file is safe to handle.
  if (in_array(strtolower($UserExtension), $Allowed) or $UserExtension == '') if (in_array(strtolower($OldExtension), $Allowed) && !in_array(strtolower($OldExtension), $DangerousFiles) && $file !== '.' && $file !== '..' && $file !== 'index.html') $FileIsVerified = TRUE;
  if (!$FileIsVerified) errorEntry('The file '.$file.' failed first stage validation!', 14000, TRUE);
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
    if (file_exists($Pathname)) if ($Verbose  && $copy) logEntry('Copied file '.$file.'.');
    // / If the $UserFilename & $UserExtension variables are valid we can prepare for a $NewPathname.
    if ($UserFilename && $UserExtension) {
      list($UserFilename, $variableIsSanitized) = sanitize($UserFilename.'.'.$UserExtension, TRUE);
      // / Define the $NewPathname if required.
      list ($NewPathname, $variableIsSanitized) = sanitize($ConvertDir.$UserFilename, FALSE);
      // / Make sure the $NewPathname is not a dangerous file.
      if (in_array(strtolower($UserExtension), $DangerousFiles) or !in_array(strtolower($UserExtension), $Allowed)) errorEntry('The file '.$file.' failed third stage validation!', 14003, TRUE);
      if ($Verbose && file_exists($NewPathname) && $clean) logEntry('Deleting stale file '.$Pathname.'.');
      // / Remove the $NewPathname file if it already exists.
      if (file_exists($NewPathname) && $clean) @unlink($NewPathname);
      // / Check to make sure that the stale file was deleted if required or creating a new one will cause problems.
      if (file_exists($NewPathname) && $clean) errorEntry('Could not delete stale file '.$NewPathname.'!', 14004, TRUE); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $file, $variableIsSanitized);
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
  global $GuiFiles, $LanguageFiles, $LanguageStringsFile, $LanguageBaselineFile, $GuiHeaderFile, $GuiFooterFile, $GuiUI1File, $GuiUI2File, $CoreLoaded, $ConvertDir, $ConvertTempDir, $Token1, $Token2, $SesHash, $SesHash2, $SesHash3, $SesHash4, $Date, $Time, $TOSURL, $PPURL, $ShowFinePrint, $PDFWorkArr, $ArchiveArray, $DearchiveArray, $DocumentArray, $SpreadsheetArray, $ImageArray, $ModelArray, $DrawingArray, $VideoInputArray, $VideoOutputArray, $SubtitleInputArray, $SubtitleOutputArray, $StreamArray, $MediaInputArray, $MediaOutputArray, $PresentationInputArray, $PresentationOutputArray, $XPSInputArray, $XPSOutputArray, $ConvertGuiCounter1, $ConsolidatedLogFileName, $Alert, $Alert1, $Alert2, $Alert3, $FCPlural, $FCPlural1, $FCPlural2, $FCPlural3, $File, $Files, $FileCount, $SpinnerStyle, $SpinnerColor, $PacmanLoc, $Allowed, $AllowUserVirusScan, $AllowUserShare, $SupportedConversionTypes, $FullURL, $LanguageDir, $FaviconPath, $DropzonePath, $DropzoneStylesheetPath, $StylesheetPath, $JsLibraryPath, $JqueryPath, $GUIDirection, $SupportedFormatCount, $GUIAlignment, $HeaderDisplayed, $UIDisplayed, $FooterDisplayed, $LanguageStringsLoaded, $GUIDisplayed, $GuiResourcesDir, $GuiImageDir, $GuiCSSDir, $GuiJSDir, $StreamOutputArray, $SCADArray, $SCADOutputArray, $AllowUserSelectableColor, $AllowUserSelectableGui, $AllowUserSelectableLanguage, $SupportedColors, $SupportedGuis, $SupportedLanguages, $ColorToUse, $GuiToUse, $LanguageToUse, $GuiDir, $SVGInputArray, $SVGOutputArray, $LanguageFlagFile, $LanguageVersion, $RequiredLanguageVersion, $DefaultLanguage, $BootableIsoArray, $AllowBootableIsoImage, $EbookInputArray, $EbookOutputArray, $EnableMemoryProtection, $NoGui, $ShowFiles, $FileListOnly, $Verbose;
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
  global $DangerousFiles, $VirusScan, $AllowUserVirusScan, $ConvertDir, $Verbose, $PermissionLevels, $Allowed, $EnableMemoryProtection;
  $UploadComplete = $UploadErrors = $virusFound = $variableIsSanitized = FALSE;
  $file = $f0 = $f1 = '';
  // / Make sure the input files are formatted into an array.
  if (!is_array($_FILES['file']['name'])) $_FILES['file']['name'] = array($_FILES['file']['name']);
  // / Iterate through the array of input files.
  foreach ($_FILES['file']['name'] as $file) {
    $UploadComplete = FALSE;
    // / Make sure the file is sanitized before processing it.
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $OperationErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 6000, FALSE); 
      continue; }
    if ($Verbose) logEntry('User selected to Upload file '.$file.'.');
    $f0 = getExtension($file);
    // / Make sure the file is not in the list of dangerous formats.
    if (in_array(strtolower($f0), $DangerousFiles) or !in_array(strtolower($f0), $Allowed)) {
      errorEntry('Unsupported file format, '.$f0.'!', 6001, FALSE);
      continue; }
    list ($f1, $variableIsSanitized) = sanitize($ConvertDir.pathinfo($file, PATHINFO_BASENAME), FALSE);
    // / Code to remove an output file that already exists.
    if (file_exists($f1)) @unlink($f1);
    @copy($_FILES['file']['tmp_name'], $f1);
    if (!file_exists($f1)) {
      $UploadErrors = TRUE;
      errorEntry('Could not upload file '.$file.' to '.$f1.'!', 6002, FALSE); }
    else {
      $UploadComplete = TRUE;
      if ($Verbose) logEntry('Uploaded file '.$file.' to '.$f1.'.'); }
    @chmod($f1, $PermissionLevels);
    // / Scan with ClamAV if $AllowUserVirusScan is set to FALSE in config.php.
    if (!$AllowUserVirusScan) {
      // / Scan with ClamAV if $VirusScan is set to TRUE in config.php.
      if ($VirusScan) {
        if ($Verbose) logEntry('Starting virus scan.');
        list ($scanComplete, $virusFound) = virusScan($f1);
        if (!$scanComplete) errorEntry('Could not perform a virus scan!', 6003, TRUE);
        if ($virusFound) errorEntry('Virus detected!', 6004, TRUE);
        if ($Verbose) logEntry('Virus scan complete.'); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $file, $f0, $f1, $variableIsSanitized, $scanComplete, $virusFound);
  return array($UploadComplete, $UploadErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to stage a selection of files for download.
function downloadFiles($Download) {
  // / Set variables.
  global $DangerousFiles, $Verbose, $ConsolidatedLogFileName, $Allowed, $EnableMemoryProtection;
  $DownloadComplete = $DownloadErrors = $clean = $copy = $skip = $variableIsSanitized = FALSE;
  $fileIsVerified = FALSE;
  $file = $f0 = $pathname = $oldPathname = $oldExtension = $newPathname = $UserFilename = '';
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
    $f0 = getExtension($file);
    // / Make sure the file is not in the list of dangerous formats.
    // / getExtension() returns an extension with no leading dot & $DangerousFiles holds
    // / dotted extensions alongside bare filenames, so the dangerous half of this test could
    // / never match. $Allowed happens to exclude every dangerous format today, which is the
    // / only reason this was not a hole. Testing both spellings removes the dependency on
    // / that coincidence surviving the next edit of the supported format list.
    if (in_array(strtolower($f0), $DangerousFiles) or in_array('.'.strtolower($f0), $DangerousFiles) or in_array(strtolower($file), $DangerousFiles) or !in_array(strtolower($f0), $Allowed)) {
      $DownloadErrors = TRUE;
      errorEntry('Unsupported file format, '.$f0.'!', 3004, FALSE);
      continue; }
    // / Past this point the file is one this function agreed to act on, so it counts.
    $filesAttempted++;
    // / Make sure all iteration specific required variables are properly sanitized.
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname, $UserFilename) = verifyFile($file, FALSE, FALSE, $clean, $copy, $skip);
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
  purgeSensitiveMemory($EnableMemoryProtection, $file, $f0, $clean, $copy, $skip, $variableIsSanitized, $fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname, $UserFilename, $filesAttempted, $filesStaged);
  return array($DownloadComplete, $DownloadErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to delete a selection of files.
// / Each location is now unlinked using its own variable.
function deleteFiles($FilesToDelete) {
  // / Set variables.
  global $DangerousFiles, $Verbose, $ConvertDir, $ConvertTempDir, $EnableMemoryProtection;
  $DeleteComplete = $DeleteErrors = $variableIsSanitized = FALSE;
  $file = $f0 = $f1 = '';
  // / The result describes the operation, not the last file in the list.
  // / $DeleteComplete was previously reset to FALSE at the top of every iteration & set
  // / TRUE only by a successful one, so the value that survived the loop was whatever
  // / happened to the LAST entry. A list whose final entry was refused reported total
  // / failure even though every other file had been deleted, & the caller answers a
  // / failure here with a fatal error 21 that prints ERROR!!! to the page. Deleting nine
  // / files out of ten looked to a user exactly like deleting none of them.
  // / Counting instead. An entry that is refused before it is attempted is recorded as an
  // / error for the caller but is not counted as an attempt, because refusing input the
  // / application was right to refuse is not a failure of the deletion.
  $filesAttempted = $filesDeleted = 0;
  list ($FilesToDelete, $variableIsSanitized) = sanitize($FilesToDelete, FALSE);
  // / Make sure the input files are formatted into an array.
  // / A single filename & a list of them are the same operation with a different count.
  if (!is_array($FilesToDelete)) $FilesToDelete = array($FilesToDelete);
  // / Iterate through the array of input files.
  foreach ($FilesToDelete as $file) {
    // / Make sure the file is sanitized before processing it.
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $DeleteErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 23000, FALSE);
      continue; }
    if ($Verbose) logEntry('User selected to Delete file '.$file.'.');
    $f0 = getExtension($file);
    // / Make sure the file is not in the list of dangerous formats.
    if (in_array(strtolower($f0), $DangerousFiles) or in_array('.'.strtolower($f0), $DangerousFiles) or in_array(strtolower($file), $DangerousFiles)) {
      $DeleteErrors = TRUE;
      errorEntry('Unsupported file format, '.$f0.'!', 23001, FALSE);
      continue; }
    // / Past this point the file is one this function agreed to act on, so it counts.
    $filesAttempted++;
    // / Remove the selected file from the hosted location.
    list ($f0, $variableIsSanitized) = sanitize($ConvertTempDir.pathinfo($file, PATHINFO_BASENAME), FALSE);
    if (file_exists($f0)) @unlink($f0);
    // / Remove the selected file from the working location.
    list ($f1, $variableIsSanitized) = sanitize($ConvertDir.pathinfo($file, PATHINFO_BASENAME), FALSE);
    if (file_exists($f1)) @unlink($f1);
    // / Check that the selected files were deleted.
    if (!file_exists($f0) && !file_exists($f1)) {
      if ($Verbose) logEntry('Deleted file '.$file.'.');
      $filesDeleted++; }
    else {
      $DeleteErrors = TRUE;
      errorEntry('Could not delete file '.$file.'!', 23002, FALSE); } }
  // / The operation is complete when every file it agreed to act on is gone.
  // / An empty list is complete because there was nothing to remove, & reporting a fatal
  // / failure for asking to delete nothing would be a worse answer than doing nothing.
  $DeleteComplete = ($filesDeleted === $filesAttempted);
  if ($Verbose) logEntry('Delete result: Attempted: '.$filesAttempted.', Deleted: '.$filesDeleted.', Errors: '.($DeleteErrors ? 'YES' : 'NO').'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $file, $f0, $f1, $variableIsSanitized, $filesAttempted, $filesDeleted);
  return array($DeleteComplete, $DeleteErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to archive a selection of files.
// / Each file is archived on its own, so one input produces one output every time.
// / Each output format gates on its own creator, so a missing mkisofs does not prevent a
// / zip archive from being created.
// / Every binary is supplied by verifyArchiveVersions() rather than assumed, so the version
// / that was verified is provably the version that runs.
// / 7-Zip cannot create rar archives. RAR compression is proprietary & 7-Zip reads the
// / format without being able to write it, so rar output has NO fallback.
function archiveFiles($FilesToArchive, $UserFilename, $UserExtension) {
  // / Set variables.
  global $Verbose, $VirusScan, $Lol, $Lolol, $Minimum7zVersion, $MinimumRarVersion, $MinimumZipVersion, $MinimumTarVersion, $MinimumMkisofsVersion, $EnableMemoryProtection;
  $ArchiveComplete = $ArchiveErrors = $virusFound = $skip = $variableIsSanitized = FALSE;
  $fileIsVerified = $scanComplete = $sandboxIsAvailable = $anyFileSucceeded = $loopCheck = FALSE;
  $archiveToolsAreValid = FALSE;
  $sevenZipBinary = $rarBinary = $zipBinary = $tarBinary = $mkisofsBinary = FALSE;
  $clean = $copy = TRUE;
  $returnData = $file = $pathname = $oldPathname = $oldExtension = $newPathname = $archiveCommand = '';
  $rararr = array('rar');
  $ziparr = array('zip');
  $tararr = array('7z', 'tar', 'tar.gz', 'tar.bz2');
  $isoarr = array('iso');
  // / Locate & verify every archive utility before anything is written.
  list ($archiveToolsAreValid, $sevenZipBinary, $rarBinary, $zipBinary, $tarBinary, $mkisofsBinary) = verifyArchiveVersions($Minimum7zVersion, $MinimumRarVersion, $MinimumZipVersion, $MinimumTarVersion, $MinimumMkisofsVersion);
  // / Make sure the input files are formatted into an array.
  if (!is_array($FilesToArchive)) $FilesToArchive = array($FilesToArchive);
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
    // / Set the $clean & $copy arguments for the verifyFiles() function as needed.
    if (count($FilesToArchive) > 1) $clean = FALSE;
    $copy = TRUE;
    if ($Verbose) logEntry('User selected to Archive file '.$file.'.');
    // / Verify the file before performing any operations on it.
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname, $UserFilename) = verifyFile($file, $UserFilename, $UserExtension, $clean, $copy, $skip);
    if (!$fileIsVerified) {
      $ArchiveErrors = TRUE;
      errorEntry('Could not verify the input file.', 4001, FALSE);
      continue; }
    else if ($Verbose) logEntry('Verified file '.$newPathname.'.');
    // / Scan with ClamAV if $VirusScan is set to TRUE in config.php.
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
      else $archiveCommand = escapeshellarg($rarBinary).' a -ep '.escapeshellarg($newPathname).' '.escapeshellarg($pathname); }
    // / Handle archiving of zip compatible files.
    else if (in_array($UserExtension, $ziparr)) {
      if ($zipBinary === FALSE) {
        $ArchiveErrors = TRUE;
        errorEntry('Zip is missing, unidentifiable, or too old!', 13010, FALSE); }
      else $archiveCommand = escapeshellarg($zipBinary).' -j '.escapeshellarg($newPathname).' '.escapeshellarg($pathname); }
    // / Handle archiving of 7-Zip compatible files.
    else if (in_array($UserExtension, $tararr)) {
      if ($sevenZipBinary === FALSE) {
        $ArchiveErrors = TRUE;
        errorEntry('The installed 7-Zip version is missing, unidentifiable, or too old!', 13008, FALSE); }
      else $archiveCommand = escapeshellarg($sevenZipBinary).' a '.escapeshellarg($newPathname).' '.escapeshellarg($pathname); }
    // / Handle archiving of mkisofs compatible files.
    else if (in_array($UserExtension, $isoarr)) {
      if ($mkisofsBinary === FALSE) {
        $ArchiveErrors = TRUE;
        errorEntry('Mkisofs is missing, unidentifiable, or too old!', 13009, FALSE); }
      else $archiveCommand = escapeshellarg($mkisofsBinary).' -o '.escapeshellarg($newPathname).' '.escapeshellarg($pathname); }
    // / Perform the archive operation inside a sandbox.
    // / An archiver reads a file the user supplied & writes a structure it controls, so it
    // / is isolated for the same reason every other dependency is.
    if ($archiveCommand !== '') {
      list ($sandboxIsAvailable, $archiveCommand) = sandboxCommand($archiveCommand, $pathname, $newPathname, FALSE, 'archive');
      if (!$sandboxIsAvailable) {
        $ArchiveErrors = TRUE;
        errorEntry('Bubblewrap is missing or non functional, so this archive operation cannot be isolated!', 13006, FALSE); }
      else $returnData = shell_exec($archiveCommand); }
    // / Log the output of the archive operation to the logfile, if it is not blank.
    if ($Verbose && trim($returnData) !== '') logEntry('The archiver returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
    // / The output file is the only verdict on whether the operation produced anything.
    if (!file_exists($newPathname)) {
      $ArchiveErrors = TRUE;
      errorEntry('Could not archive file '.$pathname.' to '.$newPathname.'!', 4004, FALSE); }
    else {
      $loopCheck = TRUE;
      // / Output the filename of the converted file to the UI so it can be given to the user.
      print($UserFilename.$Lol);
      if ($Verbose) logEntry('Archived file '.$pathname.' to '.$newPathname.'.'); }
    // / Record that at least one file in this request succeeded.
    // / $loopCheck is reset on every iteration, so without this the result would reflect
    // / only the LAST file rather than the whole request.
    if ($loopCheck) $anyFileSucceeded = TRUE; }
  if ($anyFileSucceeded) $ArchiveComplete = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $file, $rararr, $ziparr, $tararr, $isoarr, $pathname, $oldPathname, $scanComplete, $virusFound, $returnData, $variableIsSanitized, $fileIsVerified, $oldExtension, $clean, $copy, $skip, $loopCheck, $anyFileSucceeded, $archiveCommand, $sandboxIsAvailable, $archiveToolsAreValid, $sevenZipBinary, $rarBinary, $zipBinary, $tarBinary, $mkisofsBinary, $FilesToArchive, $UserFilename, $UserExtension);
  return array($ArchiveComplete, $ArchiveErrors, $newPathname); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert a selection of files.
// / A backgrounded successful stream has no output file when this function returns.
// / $WaitForStream tells us the conversion is still running & must not be judged by its output.
function convertFiles($ConvertSelected, $UserFilename, $UserExtension, $Height, $Width, $Rotate, $Bitrate) {
  // / Set variables.
  global $Verbose, $VirusScan, $SpreadsheetArray, $PresentationInputArray, $PresentationOutputArray, $XPSInputArray, $XPSOutputArray, $DocumentArray, $ImageArray, $ModelArray, $SCADArray, $DrawingArray, $SVGInputArray, $SVGOutputArray, $VideoInputArray, $VideoOutputArray, $SubtitleInputArray, $SubtitleOutputArray, $StreamArray, $StreamOutputArray, $MediaInputArray, $MediaOutputArray, $ArchiveArray, $EbookInputArray, $EbookOutputArray, $Lol, $WaitForStream, $EnableMemoryProtection;
  $MainConversionSuccess = $MainConversionErrors = $virusFound = $skip = $isExtensionSupported = $fileIsVerified = $variableIsSanitized = $outputExists = $ConversionSuccess = $ConversionErrors = $fileConversionSuccess = $anyStreamStarted = $scanComplete = FALSE;
  $clean = $copy = TRUE;
  $pathname = $oldPathname = $oldExtension = $newPathname = $file = $convertedFilename = $extension = '';
  $arrKey = 0;
  // / This is for input file filtering.
  $docarray = array_merge($DocumentArray, $SpreadsheetArray, $PresentationInputArray, $XPSInputArray);
  $imgarray = $ImageArray;
  $modelarray = $ModelArray;
  $scadarray = $SCADArray;
  $drawingarray = $DrawingArray;
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
  // /  This is for output file filtering.
  $docarrayout = array_merge($DocumentArray, $SpreadsheetArray, $PresentationOutputArray, $XPSOutputArray);
  $imgarrayout = $ImageArray;
  $modelarrayout = $ModelArray;
  $scadarrayout = $SCADArray;
  $drawingarrayout = $DrawingArray;
  $svgarrayout = $SVGOutputArray;
  $videoarrayout = $VideoOutputArray;
  $subtitleArrayout = $SubtitleOutputArray;
  $audioarrayout = $MediaOutputArray;
  $archarrayout = $ArchiveArray;
  $arrayArrayOut = array('Document' => $docarrayout, 'Image' => $imgarrayout, 'Model' => $modelarrayout, 'Scad' => $scadarrayout, 'SVG' => $svgarrayout, 'Video' => $videoarrayout, 'Subtitle' => $subtitleArrayout, 'Audio' => $audioarrayout, 'Archive' => $archarrayout, 'Drawing' => $drawingarrayout, 'Ebook' => $ebookarrayout, 'Stream' => $streamarray);
  $arrKey = 0;
  $file = '';
  // / Make sure the input files are formatted into an array.
  if (!is_array($ConvertSelected)) $ConvertSelected = array($ConvertSelected);
  // / Iterate through the array of input files.
  foreach ($ConvertSelected as $file) {
    $fileConversionSuccess = $isExtensionSupported = $ConversionSuccess = FALSE;
    // / Reset $WaitForStream for each file.
    // / It is a global set inside convert() & a stale TRUE would make the next file skip its output check.
    $WaitForStream = FALSE;
    // / Make sure the file is sanitized before processing it.
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $MainConversionErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 5000, FALSE);
      continue; }
    // / Set the $clean & $copy arguments for the verifyFiles() function as needed.
    if (count($ConvertSelected) > 1) $clean = FALSE;
    if (in_array($UserExtension, $archarray) or in_array($UserExtension, $docarray)) $clean = FALSE;
    if ($Verbose) logEntry('User selected to Convert file '.$file.'.');
    // / Verify the file before performing any operations on it.
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname, $UserFilename) = verifyFile($file, $UserFilename, $UserExtension, $clean, $copy, $skip);
    if (!$fileIsVerified) {
      $MainConversionErrors = TRUE;
      errorEntry('Could not verify the input file.', 5001, FALSE);
      continue; }
    else if ($Verbose) logEntry('Verified file '.$newPathname.'.');
    // / Scan with ClamAV if $VirusScan is set to TRUE in config.php.
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
      list ($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $convertedFilename) = convert($arrKey, $pathname, $newPathname, $UserExtension, $Height, $Width, $Rotate, $Bitrate);
      if ($ConversionErrors) {
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
    if (!$ConversionSuccess) {
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
  purgeSensitiveMemory($EnableMemoryProtection, $file, $pathname, $oldPathname, $oldExtension, $newPathname, $docarray, $imgarray, $audioarray, $videoarray, $subtitleArray, $modelarray, $scadarray, $drawingarray, $archarray, $streamarray, $arrayArray, $arrArray, $fileIsVerified, $scanComplete, $virusFound, $variableIsSanitized, $arrKey, $clean, $copy, $skip, $isExtensionSupported, $outputExists, $ConversionSuccess, $ConversionErrors, $fileConversionSuccess, $anyStreamStarted, $arrayArrayOut, $convertedFilename, $extension, $ebookarray, $ebookarrayout);
  return array($MainConversionSuccess, $MainConversionErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to create a user virus logfiles if required.
// / Type can be either 'clamav' or 'scancore'.
function verifyUserVirusLogs($type) {
  // / Set variables.
  global $Verbose, $Time, $UserClamLogFile, $UserScanCoreLogFile, $SesHash3, $Lol, $Append, $UserScanCoreFileName;
  $LogsExist = FALSE;
  $userClamLogFileName = $userScanCoreLogFileName = '';
  // / Verify the User Clam Log File if needed.
  if ($type === 'clamav') {
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
  if ($type === 'scancore') {
    // / Remove the old User ScanCore Virus Log file if one already exists.
    if (file_exists($UserScanCoreLogFile)) {
      if ($Verbose) logEntry('Deleting stale file '.$UserScanCoreLogFileName.'.');
      @unlink($UserScanCoreLogFile); }
    // / Make sure that the stale file was deleted if required or creating a new one will cause problems.
    if (file_exists($UserScanCoreLogFile)) errorEntry('Could not delete stale file '.$UserScanCoreFileName.'!', 16002, TRUE);
    else file_put_contents($UserScanCoreLogFile, 'Op-Act, '.$Time.', '.$SesHash3.': Created a User ScanCore Log File.'.$Lol, $Append);
    // / Make sure that the file was successfully replaced.
    if (!file_exists($UserScanCoreLogFile)) errorEntry('Could not create a file at '.$UserScanCoreLogFile.'!', 16003, TRUE);
    else {
      $LogsExist = TRUE;
      if ($Verbose) logEntry('Created a file at '.$UserScanCoreLogFile.'.'); } }
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
  purgeSensitiveMemory($EnableMemoryProtection, $logWrittenA, $logWrittenB);
  return $LogWritten; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to scan a user supplied file on-demand with ClamAV.
function userClamScan($FilesToScan) {
  // / Set variables.
  global $Verbose, $ConvertDir, $MinimumClamVersion, $Lol, $Lolol, $UserClamLogFile, $EnableMemoryProtection;
  $OperationSuccessful = $OperationErrors = $UserVirusFound = $userFilename = $userExtension = $clean = $copy = $userFilename = $userExtension = $variableIsSanitized = FALSE;
  $skip = TRUE;
  $returnData = $txt = $file = $clamLogFileDATA = $scanCommand = '';
  $clamBinary = FALSE;
  $txt = 'Initiating User Virus Scan with ClamAV.';
  userVirusLogEntry($txt, 'clamav');
  if ($Verbose) logEntry($txt);
  // / Locate & verify the scanner ONCE, before the loop rather than inside it.
  // / A user scanning twenty files wants one version check, not twenty.
  $clamBinary = verifyClamVersion(isset($MinimumClamVersion) ? (string)$MinimumClamVersion : '');
  // / A scan that cannot run reports failure, never a clean result.
  // / Without this the loop below ran a command that was not there, collected nothing, found
  // / no FOUND in nothing, & told the user every file was clean. The report went into the
  // / user's own downloadable scan log saying so. Refusing is the only honest answer, & the
  // / refusal is written to that same log so the user sees why rather than seeing nothing.
  if ($clamBinary === FALSE) {
    $OperationErrors = TRUE;
    $txt = 'ClamAV is missing, too old, or unusable. NO FILE WAS SCANNED.';
    userVirusLogEntry($txt, 'clamav');
    errorEntry('ClamAV is missing, too old, or unusable, so the user virus scan could not run!', 17002, FALSE);
    $FilesToScan = array(); }
  // / Make sure the input files are formatted into an array.
  if (!is_array($FilesToScan)) $FilesToScan = array($FilesToScan);
  // / Iterate through the array of input files.
  foreach ($FilesToScan as $file) {
    $UserVirusFound = FALSE;
    // / Make sure the file is sanitized before processing it.
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $OperationErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 17000, FALSE);
      continue; }
    if ($Verbose) logEntry('User selected to perform a Clam Scan on file '.$file.'.');
    // / Verify the file before performing any operations on it.
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname, $UserFilename) = verifyFile($file, $userFilename, $userExtension, $clean, $copy, $skip);
    if (!$fileIsVerified) {
      $OperationErrors = TRUE;
      errorEntry('Could not verify the input file.', 17001, FALSE);
      continue; }
    $OperationSuccessful = TRUE;
    $txt = 'Scanning '.$file.'.';
    if ($Verbose) logEntry($txt);
    userVirusLogEntry($txt, 'clamav');
    // / Scan the selected file with ClamAV.
    // / Every argument is escaped. $file is a user supplied name & must never be able to
    // / end the clamscan argument & start a command of its own.
    // / The scan runs under its resource ceiling, & the ceiling wraps clamscan rather than
    // / the pipeline, for the reasons given in virusScan().
    // / The verified path runs, not a bare command name, so the clamscan whose version was
    // / checked is provably the clamscan that scans.
    $scanCommand = limitCommand(escapeshellarg($clamBinary).' -r '.escapeshellarg($ConvertDir.$file), 'clamav');
    $returnData = shell_exec($scanCommand.' | grep FOUND >> '.escapeshellarg($UserClamLogFile));
    // / Write the full ClamAV output to the normal $LogFile.
    // / Normally we don't write dependency output if it is blank, but for virus scans we do. 
    // / Blank virus scan output means scanner malfunction or potential tampering of the results. 
    if ($Verbose) logEntry('The Virus Scanner returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
    // / Load the contents of the User Clam Log File for processing because it has been sanitized of unnecessary data & whitespace.
    $clamLogFileDATA = @file_get_contents($UserClamLogFile);
    // / Check the contents of the User Clam Log File for virus detections.
    if (strpos($clamLogFileDATA, 'FOUND') !== FALSE or strpos($clamLogFileDATA, 'FOUND') === TRUE) {
      $UserVirusFound = TRUE;
      $txt = 'Potentially infected file detected at '.$file.'!';
      warningEntry($txt);
      userVirusLogEntry($txt, 'clamav'); }
      // / Write the results of the scan to both log files.
    else {
      $txt = 'No infection detected in '.$file.'.';
      if ($Verbose) logEntry($txt);
      userVirusLogEntry($txt, 'clamav'); } }
  // / A refusal does not get to end with the word Complete. The user reads this log.
  $txt = ($clamBinary === FALSE) ? 'ClamAV Virus Scan DID NOT RUN.' : 'ClamAV Virus Scan Complete.';
  if ($Verbose) logEntry($txt);
  userVirusLogEntry($txt, 'clamav');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $variableIsSanitized, $clean, $copy, $skip, $returnData, $scanCommand, $clamBinary, $txt, $userFilename, $userExtension, $clamLogFileDATA);
  return array($OperationSuccessful, $OperationErrors, $UserVirusFound); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A fuction to prepare the execution environment for ScanCore.
function startScanCore($pathname, $UserScanCoreLogFile) {
  // / Set variables.
  global $InstLoc, $ConvertDir, $MaxLogSize, $ScanCoreMemoryLimit, $ScanCoreChunkSize, $ScanCoreDebug, $ScanCoreVerbose, $DirSep, $Date, $SesHash, $SesHash2, $EnableMemoryProtection;
  $ReturnData = $scVerbose = $scDebug = $scanCommand = '';
  $ScanCoreFile = $InstLoc.$DirSep.'Resources'.$DirSep.'ScanCore'.$DirSep.'ScanCore.php';
  $scInc = 0;
  if ($ScanCoreVerbose) $scVerbose = ' -v';
  if ($ScanCoreDebug) $scDebug = ' -d';
  // / Make sure that ScanCore is installed.
  if (!file_exists($ScanCoreFile)) errorEntry('Could not verify the ScanCore Virus Scanner!', 18000, TRUE);
  // / The filename for the ScanCore log file.
  $scLogFile = $ConvertDir.$DirSep.'ScanCore_'.$SesHash.'_'.$SesHash2.'_'.$Date.'_'.$scInc.'_Log.txt';
  while (file_exists($scLogFile)) $scLogFile = $ConvertDir.$DirSep.'ScanCore_'.$SesHash.'_'.$SesHash2.'_'.$Date.'_'.$scInc++.'_Log.txt';
  // / Run ScanCore with the information supplied.
  // / Every argument is escaped. $pathname is a user supplied path & the numeric settings
  // / are cast, so nothing in this command line can be turned into a second command.
  // /
  // / The scan runs under the same resource ceiling every conversion runs under.
  // / --Scan Core Memory Limit-- is NOT that ceiling & does not replace it. It is a PHP
  // / memory_limit handed to the child interpreter, which is a self imposed budget the
  // / child honours only while it is behaving. A cgroup ceiling is imposed from outside &
  // / holds whatever the child does, including the allocations PHP never counts against
  // / memory_limit in the first place. The two are complementary & this had only the
  // / weaker of them.
  $scanCommand = limitCommand('php '.escapeshellarg($ScanCoreFile)
    .' '.escapeshellarg($pathname)
    .' -m '.escapeshellarg((string)$ScanCoreMemoryLimit)
    .' -c '.escapeshellarg((string)$ScanCoreChunkSize)
    .' -lf '.escapeshellarg($scLogFile)
    .' -rf '.escapeshellarg($UserScanCoreLogFile)
    .' -ml '.escapeshellarg((string)$MaxLogSize)
    .' -r'.$scVerbose.$scDebug, 'scancore');
  $ReturnData = shell_exec($scanCommand);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $pathname, $scVerbose, $scDebug, $scLogFile, $scInc, $scanCommand);
  return $ReturnData; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to scan a user supplied file on-demand with ScanCore.
function userScanCoreScan($FilesToScan) {
  // / Set variables.
  global $Verbose, $ConvertDir, $Lol, $Lolol, $UserScanCoreLogFile, $EnableMemoryProtection;
  $OperationSuccessful = $OperationErrors = $UserVirusFound = $userFilename = $userExtension = $clean = $copy = $variableIsSanitized = FALSE;
  $skip = TRUE;
  $returnData = $txt = $file = $scanCoreLogFileDATA = '';
  $txt = 'Initiating User Virus Scan with ScanCore.';
  userVirusLogEntry($txt, 'scancore');
  if ($Verbose) logEntry($txt);
  // / Make sure the input files are formatted into an array.
  if (!is_array($FilesToScan)) $FilesToScan = array($FilesToScan);
  // / Iterate through the array of input files.
  foreach ($FilesToScan as $file) {
    $UserVirusFound = FALSE;
    // / Make sure the file is sanitized before processing it.
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $OperationErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 19000, FALSE);
      continue; }
    if ($Verbose) logEntry('User selected to perform a ScanCore Scan on file '.$file.'.');
    // / Verify the file before performing any operations on it.
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname, $UserFilename) = verifyFile($file, $userFilename, $userExtension, $clean, $copy, $skip);
    if (!$fileIsVerified) {
      $OperationErrors = TRUE;
      errorEntry('Could not verify the input file.', 19001, FALSE);
      continue; }
    $OperationSuccessful = TRUE;
    $txt = 'Scanning '.$file.'.';
    if ($Verbose) logEntry($txt);
    userVirusLogEntry($txt, 'scancore');
    // / Scan the selected file with ScanCore.
    $returnData = startScanCore($ConvertDir.$file, $UserScanCoreLogFile);
    // / Write the full ScanCore output to the normal $LogFile.
    // / Normally we don't write dependency output if it is blank, but for virus scans we do. 
    // / Blank virus scan output means scanner malfunction or potential tampering of the results. 
    if ($Verbose) logEntry('ScanCore returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
    // / Load the contents of the User ScanCore Log File for processing because it has been sanitized of unnecessary data & whitespace.
    $scanCoreLogFileDATA = @file_get_contents($UserScanCoreLogFile);
    // Check the contents of the User ScanCore Log File for virus detections.
    if (strpos($scanCoreLogFileDATA, 'Infected: ') !== FALSE) {
      $UserVirusFound = TRUE;
      $txt = 'Potentially infected file detected at '.$file.'!';
      warningEntry($txt);
      userVirusLogEntry($txt, 'scancore'); }
    // / Write the results of the scan to both log files.
    else {
      $txt = 'No infection detected in '.$file.'.';
      if ($Verbose) logEntry($txt);
      userVirusLogEntry($txt, 'scancore'); } }
  // / Write the completion of the scan to the log files.
  $txt = 'ScanCore Virus Scan Complete.';
  if ($Verbose) logEntry($txt);
  userVirusLogEntry($txt, 'scancore');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $variableIsSanitized, $clean, $copy, $skip, $returnData, $txt, $userFilename, $userExtension, $scanCoreLogFileDATA);
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
  // / Check if all required scan operations are complete & if any erros occured.
  if ($type == 'all') {
    if ($scan1Complete && $scan2Complete) $ScanComplete = TRUE;
    if ($scan1Errors or $scan2Errors) $ScanErrors = TRUE; }
  // / Set results using only ClamAV output.
  if ($type == 'clamav') {
    $ScanComplete = TRUE;
     if ($scan1Complete) $ScanComplete = TRUE;
     if ($scan1Errors) $ScanErrors = TRUE; }
  // / Set results using only ScanCore output.
  if ($type == 'scancore') {
    $ScanComplete = TRUE;
     if ($scan2Complete) $ScanComplete = TRUE;
     if ($scan2Errors) $ScanErrors = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $scan1Complete, $scan1Errors, $scan2Complete, $scan2Errors);
  return array($ScanComplete, $ScanErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to define & verify that a Consolidated User Virus Log File exists.
// / Type can be either 'clamav', 'scancore', or 'all'.
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
  // / Attempt to create a new consolidated log one if the previous one was successfully removed.
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
  global $Lol, $Append, $ConsolidatedLogFile, $UserClamLogFile, $UserScanCoreLogFile, $ConsolidatedLogFileName, $EnableMemoryProtection;
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
    // / Load the Consolidated Log File into memory.
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
  purgeSensitiveMemory($EnableMemoryProtection, $type, $txt, $spacer, $logWrittenA, $logWrittenB, $logWrittenC, $logWrittenD, $logWrittenE, $userClamLogData, $userScanCoreLogData);
  return array($ConsolidatedLogsExist, $ConsolidatedLogErrors, $ConsolidatedLogFile, $ConsolidatedLogFileName); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to scan an input file or folder for viruses with ClamAV.
// / Type can be either 'clamav', 'scancore', or 'all'.
function userVirusScan($FilesToScan, $type) {
  // / Set variables.
  global $UserClamLogFile, $UserScanCoreLogFile, $ConsolidatedLogFile, $ConsolidatedLogFileName, $EnableMemoryProtection;
  $ScanComplete = $ScanErrors = $UserVirusFound = $scan1Complete = $scan1Errors = $scan2Complete = $scan2Errors = $ConsolidatedLogsExist = $ConsolidatedLogErrors = FALSE;
  $clamVirusFound = $scanCoreVirusFound = FALSE;
  // / Check that the $type input variable is valid.
  if ($type !== 'all' && $type !== 'clamav' && $type !== 'scancore') $type = 'all';
  // / Make sure the input files are formatted into an array.
  if (!is_array($FilesToScan)) $FilesToScan = array($FilesToScan);
  list ($LogsExist, $UserClamLogFile, $UserScanCoreLogFile) = verifyUserVirusLogs($type);
  // / Each scanner is run once, over the whole list.
  // / This used to sit inside a foreach over $FilesToScan while handing each scanner the
  // / ENTIRE array every time, so a scan of five files ran five scans of five files. The
  // / loop variable was never read by anything inside it, which is what hid the fault; the
  // / results were identical & only the time & the load told the truth. Twenty files meant
  // / four hundred file scans, & a virus scan is now something that draws on a resource
  // / budget & runs under a ceiling, so the cost is no longer only this session's problem.
  //
  // / A detection from either scanner is a detection.
  // / Both scanners wrote their result into the SAME $UserVirusFound, so in 'all' mode
  // / ScanCore's answer landed on top of ClamAV's. A file ClamAV identified as infected was
  // / reported to the user as clean whenever ScanCore did not also recognise it, which is
  // / the normal case, because the two carry different definitions & that is the entire
  // / reason for offering both. Each scanner now reports into its own variable & the answer
  // / the user is given is the OR of them.
  if ($type === 'clamav' or $type === 'all') {
    // / Prepare to run a ClamAV Scan.
    list ($scan1Complete, $scan1Errors, $clamVirusFound) = userClamScan($FilesToScan);
    if ($clamVirusFound) $UserVirusFound = TRUE; }
  // / Perform a User Virus Scan using ScanCore if required.
  if ($type === 'scancore' or $type === 'all') {
    // / Prepare to run a ScanCore Scan.
    list ($scan2Complete, $scan2Errors, $scanCoreVirusFound) = userScanCoreScan($FilesToScan);
    if ($scanCoreVirusFound) $UserVirusFound = TRUE; }
  // / Check the results of the virus scan for failures or errors.
  list ($ScanComplete, $ScanErrors) = checkUserVirusScanResults($type, $scan1Complete, $scan1Errors, $scan2Complete, $scan2Errors);
  // / Consolidate the log files created during the scan into the $ConvertTempDir so the user can access them.
  list ($ConsolidatedLogsExist, $ConsolidatedLogErrors, $ConsolidatedLogFile, $ConsolidatedLogFileName) = consolidateLogs($type, $UserClamLogFile, $UserScanCoreLogFile);
  // / Verify that all operations are complete.
  if ($ScanErrors or $ConsolidatedLogErrors) $ScanErrors = TRUE;
  if (!$ConsolidatedLogsExist) $ScanComplete = FALSE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $clamVirusFound, $scanCoreVirusFound, $path, $type, $scan1Complete, $scan1Errors, $scan2Complete, $scan2Errors, $returnData);
  return array($ScanComplete, $ScanErrors, $UserVirusFound, $ConsolidatedLogFile, $ConsolidatedLogFileName); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to derive a time bucketed startup key for an internal core invocation.
// / Accepts the purpose the key authorizes.
// / Returns the key, or an empty string when no install secret is available.
// / The bucket bounds the window in which a captured key can be reused.
function deriveStartupKey($keyPurpose) {
  // / Set variables.
  global $SecretKey, $StartupKeyWindow, $EnableMemoryProtection;
  $StartupKey = '';
  $timeBucket = 0;
  $keyMaterial = '';
  if (is_string($SecretKey) && strlen($SecretKey) === 64) {
    $timeBucket = (int)floor(time() / max(1, (int)$StartupKeyWindow));
    $keyMaterial = 'startup|'.$keyPurpose.'|'.$timeBucket;
    $StartupKey = hash_hmac('sha256', $keyMaterial, $SecretKey); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $timeBucket, $keyMaterial, $keyPurpose);
  return $StartupKey; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read a startup key out of the environment & clear it immediately.
// / Accepts no arguments. Returns the key, or an empty string when none was supplied.
// / A startup key used to travel on the command line, & /proc/PID/cmdline is world
// / readable, so every local account could read it out of ps for the whole life of the
// / process. A manager listener runs for days, so the key sat in plain view long after the
// / ten second window that made it useful had closed. Anybody watching ps during a spawn
// / had the key. /proc/PID/environ is readable only by the owner of the process.
// / The variable is cleared the moment it is read, so it is not inherited by a converter
// / the manager launches later.
function readTransportedStartupKey() {
  // / Set variables.
  global $EnableMemoryProtection;
  $TransportedKey = '';
  $rawKey = '';
  $rawKey = (string)getenv('HRCONVERT2_STARTUP_KEY');
  if ($rawKey !== '') {
    $TransportedKey = preg_replace('/[^a-f0-9]/', '', strtolower($rawKey));
    putenv('HRCONVERT2_STARTUP_KEY');
    unset($_ENV['HRCONVERT2_STARTUP_KEY'], $_SERVER['HRCONVERT2_STARTUP_KEY']); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $rawKey);
  return $TransportedKey; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to spend a startup key so it can never be presented a second time.
// / Accepts the purpose & the key. Returns TRUE when the key had not been spent before.
// / Time alone cannot make a leaked key dead. The holder of a copy validates it against
// / the same clock we do, so the window only decides how long they have, never whether
// / they succeed. Spending the key is a property this application controls.
// / The ledger lives in the manager socket directory, which is already 0700, & it holds
// / hashes rather than keys so reading it grants nothing. Entries older than three windows
// / are dropped, because a key that old cannot validate anyway.
// /
// / What this defends against, & what it does not.
// / It defends against a key that leaked. Somebody who obtained one copy of a key cannot
// / spend it twice, & cannot spend it at all once the process it was minted for has used it.
// / It does NOT defend against an account that holds the install secret. Deleting this
// / ledger un-spends a captured key, & the two accounts that can delete it are root & the
// / web server user, both of which can already read the secret at 0600 & mint a valid key
// / for any purpose in any window. That is a shorter route to the same place, so losing the
// / ledger to either of them costs nothing that was not already lost.
// / The directory holding the ledger sits inside the data location at 0755 & owned by the
// / web server user, so an unprivileged local account cannot unlink it. That is the account
// / this control exists for, & the property that has to keep holding.
function consumeStartupKey($keyPurpose, $suppliedKey) {
  // / Set variables.
  global $ManagerSocketDir, $DirSep, $StartupKeyWindow, $EnableMemoryProtection;
  $KeyWasUnspent = FALSE;
  $ledgerPath = $ledgerContents = $keyFingerprint = $rebuiltLedger = $ledgerLine = '';
  $ledgerLines = array();
  $cutoffTime = 0;
  if (!is_dir((string)$ManagerSocketDir)) $KeyWasUnspent = TRUE;
  else {
    $ledgerPath = rtrim((string)$ManagerSocketDir, $DirSep).$DirSep.'startup-keys.ledger';
    $keyFingerprint = hash('sha256', (string)$keyPurpose.'|'.(string)$suppliedKey);
    $cutoffTime = time() - (max(1, (int)$StartupKeyWindow) * 3);
    $ledgerContents = (string)@file_get_contents($ledgerPath);
    $ledgerLines = ($ledgerContents === '' ? array() : explode(PHP_EOL, $ledgerContents));
    $KeyWasUnspent = TRUE;
    foreach ($ledgerLines as $ledgerLine) {
      if (trim($ledgerLine) === '') continue;
      // / A line is a timestamp & a fingerprint. Anything older than the cutoff is dropped
      // / rather than carried, so the ledger cannot grow without bound.
      if ((int)substr($ledgerLine, 0, strpos($ledgerLine, ' ')) < $cutoffTime) continue;
      if (substr($ledgerLine, strpos($ledgerLine, ' ') + 1) === $keyFingerprint) $KeyWasUnspent = FALSE;
      $rebuiltLedger = $rebuiltLedger.$ledgerLine.PHP_EOL; }
    if ($KeyWasUnspent) {
      $rebuiltLedger = $rebuiltLedger.time().' '.$keyFingerprint.PHP_EOL;
      @file_put_contents($ledgerPath, $rebuiltLedger, LOCK_EX);
      @chmod($ledgerPath, 0600); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $ledgerPath, $ledgerContents, $keyFingerprint, $rebuiltLedger, $ledgerLine, $ledgerLines, $cutoffTime, $keyPurpose, $suppliedKey);
  return $KeyWasUnspent; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to validate a startup key supplied on the command line.
// / Accepts the purpose the key must authorize & the key that was supplied.
// / Accepts a third argument requesting that the key be spent once it validates.
// / Returns TRUE when the key matches the current or the previous window.
// / The previous window is accepted because process launch is not instant.
// / A key that crossed a process boundary is spent, so presenting it again is refused
// / however soon it happens. A key derived & checked inside one process never leaves it &
// / is not spent, because there is nothing to replay & four such keys minted in the same
// / second would otherwise refuse each other.
// / The default is not to spend, so a component built against an older core still works.
function validateStartupKey($keyPurpose, $suppliedKey, $singleUse = FALSE) {
  // / Set variables.
  global $SecretKey, $StartupKeyWindow, $EnableMemoryProtection;
  $KeyIsValid = FALSE;
  $timeBucket = 0;
  $keyWasUnspent = TRUE;
  $currentKey = $previousKey = $cleanKey = '';
  $cleanKey = preg_replace('/[^a-f0-9]/', '', strtolower((string)$suppliedKey));
  if (is_string($SecretKey) && strlen($SecretKey) === 64 && strlen($cleanKey) === 64) {
    $timeBucket = (int)floor(time() / max(1, (int)$StartupKeyWindow));
    $currentKey = hash_hmac('sha256', 'startup|'.$keyPurpose.'|'.$timeBucket, $SecretKey);
    $previousKey = hash_hmac('sha256', 'startup|'.$keyPurpose.'|'.($timeBucket - 1), $SecretKey);
    if (hash_equals($currentKey, $cleanKey) or hash_equals($previousKey, $cleanKey)) $KeyIsValid = TRUE; }
  // / The key is only spent once it has proved genuine, so a wrong guess cannot fill the
  // / ledger & cannot deny the real one.
  if ($KeyIsValid && $singleUse) {
    $keyWasUnspent = consumeStartupKey($keyPurpose, $cleanKey);
    if (!$keyWasUnspent) {
      $KeyIsValid = FALSE;
      errorEntry('A startup key for '.$keyPurpose.' was presented a second time & refused as a replay!', 31017, FALSE); } }
  if (!$KeyIsValid && $keyWasUnspent) warningEntry('A startup key for '.$keyPurpose.' was refused.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $timeBucket, $currentKey, $previousKey, $cleanKey, $keyWasUnspent, $keyPurpose, $suppliedKey, $singleUse);
  return $KeyIsValid; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to compare two version numbers numerically & report a minimum match.
// / Accepts the detected version & the minimum version required, in that order.
// / Returns TRUE when the detected version is the same as, or newer than, the required one.
// / A leading v is stripped before comparison, because casting 'v3' to an integer yields 0
// / & silently reduces a three part comparison to a two part one.
// / Comparison is numeric part by part, because a string comparison ranks 24.2 below 7.6 &
// / ranks 3.10 below 3.9.
// / A version that cannot be parsed is REFUSED. An unknown build cannot be cleared.
function compareVersionMinimum($detectedVersion, $requiredVersion) {
  // / Set variables.
  global $EnableMemoryProtection;
  $VersionIsCurrent = FALSE;
  $cleanDetected = $cleanRequired = '';
  $detectedParts = $requiredParts = array();
  $detectedMajor = $detectedMinor = $detectedPatch = 0;
  $requiredMajor = $requiredMinor = $requiredPatch = 0;
  $cleanDetected = ltrim(trim((string)$detectedVersion), 'vV');
  $cleanRequired = ltrim(trim((string)$requiredVersion), 'vV');
  // / A blank requirement means any version will do.
  if ($cleanRequired === '') $VersionIsCurrent = TRUE;
  else if ($cleanDetected === '') $VersionIsCurrent = FALSE;
  else {
    $detectedParts = explode('.', $cleanDetected);
    $requiredParts = explode('.', $cleanRequired);
    // / A version whose leading part is not a number is not a version.
    if (!ctype_digit(trim($detectedParts[0]))) $VersionIsCurrent = FALSE;
    else {
      $detectedMajor = (int)$detectedParts[0];
      $detectedMinor = isset($detectedParts[1]) ? (int)$detectedParts[1] : 0;
      $detectedPatch = isset($detectedParts[2]) ? (int)$detectedParts[2] : 0;
      $requiredMajor = (int)$requiredParts[0];
      $requiredMinor = isset($requiredParts[1]) ? (int)$requiredParts[1] : 0;
      $requiredPatch = isset($requiredParts[2]) ? (int)$requiredParts[2] : 0;
      if ($detectedMajor > $requiredMajor) $VersionIsCurrent = TRUE;
      else if ($detectedMajor === $requiredMajor) {
        if ($detectedMinor > $requiredMinor) $VersionIsCurrent = TRUE;
        else if ($detectedMinor === $requiredMinor && $detectedPatch >= $requiredPatch) $VersionIsCurrent = TRUE; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cleanDetected, $cleanRequired, $detectedParts, $requiredParts, $detectedMajor, $detectedMinor, $detectedPatch, $requiredMajor, $requiredMinor, $requiredPatch, $detectedVersion, $requiredVersion);
  return $VersionIsCurrent; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the version a component declares, without loading it.
// / Accepts the path relative to Resources & the name of the version variable.
// / Returns the version, 'not installed' or 'no version', so a caller can print it.
// / verifyCoreComponent loads a component when it matches, which is right when something
// / is about to be called & wrong when a version report is all that was asked for.
function readComponentVersion($componentRelativePath, $versionVariableName) {
  // / Set variables.
  global $InstLoc, $EnableMemoryProtection;
  $DetectedVersion = 'not installed';
  $componentPath = $componentContents = '';
  $versionMatches = array();
  $componentPath = $InstLoc.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.$componentRelativePath;
  if (file_exists($componentPath)) {
    $componentContents = @file_get_contents($componentPath);
    $DetectedVersion = 'no version';
    if (is_string($componentContents) && preg_match('/\$'.preg_quote($versionVariableName, '/').'\s*=\s*\'([^\']+)\'/', $componentContents, $versionMatches)) $DetectedVersion = ltrim($versionMatches[1], 'vV'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $componentPath, $componentContents, $versionMatches, $componentRelativePath, $versionVariableName);
  return $DetectedVersion; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify & load one detachable component.
// / Accepts the component name, its path relative to Resources, the name of the variable
// / it declares its version in, & the version this core requires, in that order.
// / Returns an availability boolean & the detected version, in that order.
// / The version is read from the file WITHOUT executing it, because a mismatched component
// / defines functions this core may not be able to call safely.
// / This is an EXACT match, the same rule applied to a GUI or a language pack.
// / The detected version is published as a global under the name the component uses.
// / The -v argument can report it. A require inside a function lands its assignments in
// / that function, not in global scope, so a component version read this way would
// / otherwise be invisible to everything outside this call.
// / Every core lives in a folder of its own under Resources, the same way ScanCore does.
// / DIRECTORY_SEPARATOR is used here rather than $DirSep, & that is deliberate.
// / verifyInstallation() calls this function before verifyGlobals() has run, so $DirSep is
// / not set yet at that point & would concatenate as an empty string.
// / readComponentVersion() below keeps the constant for the same reason, so that the two
// / stay interchangeable if either one is ever called earlier than it is today.
function verifyCoreComponent($componentName, $componentRelativePath, $versionVariableName, $requiredComponentVersion) {
  // / Set variables.
  global $InstLoc, $CoreLoaded, $EnableMemoryProtection;
  $ComponentIsAvailable = FALSE;
  $DetectedComponentVersion = '';
  $componentPath = $componentContents = $cleanDetected = $cleanRequired = '';
  $versionMatches = array();
  $componentPath = $InstLoc.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.$componentRelativePath;
  if (!file_exists($componentPath)) warningEntry('The '.$componentName.' component is not installed at '.$componentPath.'.');
  else {
    $componentContents = @file_get_contents($componentPath);
    if (!is_string($componentContents) or $componentContents === '') warningEntry('The '.$componentName.' component at '.$componentPath.' could not be read.');
    else {
      if (preg_match('/\$'.preg_quote($versionVariableName, '/').'\s*=\s*\'([^\']+)\'/', $componentContents, $versionMatches)) $DetectedComponentVersion = $versionMatches[1];
      $cleanDetected = ltrim(trim($DetectedComponentVersion), 'vV');
      $cleanRequired = ltrim(trim((string)$requiredComponentVersion), 'vV');
      // / A component that reports no version is refused. An unknown build cannot be cleared.
      if ($cleanDetected === '') warningEntry('The '.$componentName.' component reports no version & was refused.');
      else if ($cleanDetected !== $cleanRequired) warningEntry('The '.$componentName.' component reports v'.$cleanDetected.' & this core requires v'.$cleanRequired.'. It was refused.');
      else {
        require_once ($componentPath);
        // / Publish the version globally under the name the component declares it with.
        $GLOBALS[$versionVariableName] = $DetectedComponentVersion;
        $ComponentIsAvailable = TRUE; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $componentPath, $componentContents, $cleanDetected, $cleanRequired, $versionMatches, $componentName, $componentRelativePath, $versionVariableName, $requiredComponentVersion);
  return array($ComponentIsAvailable, $DetectedComponentVersion); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to request permission to consume resources before a conversion begins.
// / Accepts the conversion cost & the expected runtime in seconds.
// / Returns an approval boolean & the issued budget token, in that order.
// / This FAILS OPEN. When resource awareness is unavailable the request is approved & the
// / core behaves exactly as it did before this component existed.
function requestConversionBudget($conversionCost, $expectedRuntime) {
  // / Set variables.
  global $ResourceAwarenessActive, $ManagerSocketTimeout, $EffectiveConversionLimits, $EnableMemoryProtection, $Verbose;
  $BudgetWasApproved = FALSE;
  $BudgetToken = '';
  $requestPayload = $replyPayload = array();
  $messageWasDelivered = FALSE;
  $requestSocket = '';
  if (!$ResourceAwarenessActive) $BudgetWasApproved = TRUE;
  else {
    $requestSocket = buildManagerSocketPath('request-manager');
    $requestPayload = array(
      'RequestType' => 'budget',
      'ConversionCost' => (int)$conversionCost,
      'ExpectedRuntime' => (int)$expectedRuntime,
      'WorkerPid' => getmypid());
    // / The worker waits longer than the chain it is waiting on. The request crosses three
    // / processes & each inner hop waits less than the one outside it, so a slow manager
    // / times out inside rather than leaving the worker with half an answer.
    list ($messageWasDelivered, $replyPayload) = sendManagerMessage($requestSocket, $requestPayload, 'worker', (int)$ManagerSocketTimeout * 3);
    // / A listener that cannot be reached must not stop a conversion that would have run.
    if (!$messageWasDelivered) {
      warningEntry('The Core Manager listener did not answer a budget request. Proceeding without resource awareness.');
      $BudgetWasApproved = TRUE; }
    // / A delivered message with no usable reply is a listener that is slow or broken rather
    // / than a budget that declined. Refusing here would stop a conversion nothing refused.
    // / Only an explicit answer is allowed to refuse a conversion.
    else if (!isset($replyPayload['Approved'])) {
      warningEntry('The Core Manager listener returned no usable answer to a budget request. Proceeding without resource awareness.');
      $BudgetWasApproved = TRUE; }
    else if ($replyPayload['Approved'] === TRUE) {
      $BudgetWasApproved = TRUE;
      $BudgetToken = isset($replyPayload['BudgetToken']) ? (string)$replyPayload['BudgetToken'] : '';
      // / The listener scales the configured maxima against current load & hands back the
      // / table this session converts under. It is used for every file in this request.
      if (isset($replyPayload['Limits']) && is_array($replyPayload['Limits'])) $EffectiveConversionLimits = $replyPayload['Limits']; 
      if ($Verbose) logEntry('Worker '.getmypid().' was granted budget token '.$BudgetToken.'.'); }
    else logEntry('A conversion was refused by the resource budget. '.(isset($replyPayload['Reason']) && $replyPayload['Reason'] !== '' ? $replyPayload['Reason'] : 'No reason was supplied.')); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $requestPayload, $replyPayload, $messageWasDelivered, $requestSocket, $conversionCost, $expectedRuntime);
  return array($BudgetWasApproved, $BudgetToken); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report that a conversion has finished & release its budget.
// / Accepts the budget token issued at approval.
// / Returns TRUE when the release was acknowledged, or when there was nothing to release.
function releaseConversionBudget($budgetToken) {
  // / Set variables.
  global $ResourceAwarenessActive, $ManagerSocketTimeout, $EnableMemoryProtection, $Verbose;
  $BudgetWasReleased = FALSE;
  $requestPayload = $replyPayload = array();
  $messageWasDelivered = FALSE;
  if (!$ResourceAwarenessActive or (string)$budgetToken === '') $BudgetWasReleased = TRUE;
  else {
    $requestPayload = array('RequestType' => 'release', 'BudgetToken' => (string)$budgetToken, 'WorkerPid' => getmypid());
    list ($messageWasDelivered, $replyPayload) = sendManagerMessage(buildManagerSocketPath('request-manager'), $requestPayload, 'worker', (int)$ManagerSocketTimeout * 3);
    if ($messageWasDelivered && isset($replyPayload['Approved']) && $replyPayload['Approved'] === TRUE) $BudgetWasReleased = TRUE;
    else warningEntry('A budget token could not be released. The reaper will reclaim it.'); 
    if ($Verbose && $BudgetWasReleased) logEntry('Worker '.getmypid().' released budget token '.(string)$budgetToken.'.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $requestPayload, $replyPayload, $messageWasDelivered, $budgetToken);
  return $BudgetWasReleased; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to return a conversion budget token however the request ends.
// / Registered as a shutdown handler the moment a budget is approved, & also called
// / directly on the normal completion path. The guard makes it safe to run twice.
// /
// / A conversion has three fatal exits between taking a budget & returning it.
// / Error 21 when the conversion itself fails, & errors 5002 & 5003 when the virus scan of
// / the result cannot run or finds something. Each calls errorEntry with a fatal flag,
// / which reaches quickDie & then die(), so the release that sits AFTER the conversion in
// / the request handler was simply never reached.
// / Nothing was lost permanently, because findStaleWorkers() tests whether the process is
// / still alive & reclaims a token whose worker has exited. But that happens on the next
// / sweep, so a failed conversion held its share of the budget for up to one
// / $WorkerReapInterval, & on a small machine that is enough to refuse the next
// / conversion that arrives. It also announced every ordinary failure as a warning about
// / a worker that had to be reaped, which is not what happened & not what an
// / administrator reading that warning should go looking for.
// / A conversion that fails is a normal outcome. It returns what it borrowed on the way
// / out, & the reaper goes back to being the fallback it was written to be.
// /
// / PHP runs a registered shutdown function after die(), so this is reached from a fatal
// / exit as well as from a clean one. It writes to the log & to the manager socket only.
// / Nothing here prints, because the connection to the user is already closed by then.
function releaseBudgetOnShutdown() {
  // / Set variables.
  global $BudgetToken, $BudgetTokenIsReleased;
  $BudgetWasReleased = TRUE;
  // / Already returned, or there was never one to return.
  if (!empty($BudgetTokenIsReleased)) return TRUE;
  if (!isset($BudgetToken) or (string)$BudgetToken === '') return TRUE;
  // / The flag is set BEFORE the attempt, not after. A release that fails has already
  // / warned & handed the token to the reaper, & a second attempt from the other caller
  // / would warn about the same token all over again.
  $BudgetTokenIsReleased = TRUE;
  $BudgetWasReleased = releaseConversionBudget($BudgetToken);
  return $BudgetWasReleased; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to take a resource budget for an expensive operation & arm its return.
// / Accepts a human readable operation name used only for logging.
// / Returns TRUE when the operation may proceed.
// /
// / Every expensive operation takes a budget, not only conversion.
// / Resource awareness existed to stop a machine from accepting more expensive work than it
// / can carry, but only convertFiles() ever asked permission. Archiving, OCR & the user
// / virus scan ran unmetered. That is not a small gap. Tesseract on a large PDF, 7-Zip on a
// / multi-gigabyte folder & a ClamAV scan are each as heavy as the conversions the budget
// / was written to hold back, & because they took nothing they also counted for nothing.
// / A machine saturated by them still reported itself idle & kept approving conversions on
// / top. The limiter was measuring a fraction of the load & guarding against a fraction of
// / the problem.
// /
// / All four operations take the same $DefaultConversionCost & $DefaultExpectedRuntime.
// / Weighting them separately would be more precise, but precision here would be invented;
// / there are no measurements behind a number that says an archive costs half of an OCR.
// / One unit of expensive work is a claim this code can actually support, & an administrator
// / who needs finer control has the per-conversion limit table already.
// /
// / This FAILS OPEN exactly as requestConversionBudget() does. When resource awareness is
// / unavailable the operation is approved & the core behaves as it did before.
function takeOperationBudget($operationName) {
  // / Set variables.
  global $BudgetToken, $BudgetTokenIsReleased, $DefaultConversionCost, $DefaultExpectedRuntime, $Verbose;
  $BudgetWasApproved = FALSE;
  list ($BudgetWasApproved, $BudgetToken) = requestConversionBudget($DefaultConversionCost, $DefaultExpectedRuntime);
  // / Every message names the operation the same way, so $operationName is a bare noun &
  // / the sentence supplies the rest. A name of 'OCR operation' rendered 'The OCR operation
  // / operation holds', & an article written into the sentence rendered 'A OCR'.
  if (!$BudgetWasApproved) warningEntry('The '.$operationName.' operation was refused because the server is at its resource budget.');
  else {
    // / The token is out from here. Register its return before anything can die.
    // / Every one of these operations has a fatal exit between taking a budget & returning
    // / it, so the release cannot live only at the bottom of the caller's block.
    $BudgetTokenIsReleased = FALSE;
    register_shutdown_function('releaseBudgetOnShutdown');
    if ($Verbose) logEntry('The '.$operationName.' operation holds budget token '.$BudgetToken.'.'); }
  return $BudgetWasApproved; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to return the resource budget an operation took.
// / Accepts a human readable operation name used only for logging.
// / Returns TRUE when the release was acknowledged, or when there was nothing to release.
// / Safe to call when the budget was refused & safe to call twice, because the guard inside
// / releaseBudgetOnShutdown() is what decides whether there is anything to do.
function giveBackOperationBudget($operationName) {
  // / Set variables.
  global $Verbose;
  $BudgetWasReleased = releaseBudgetOnShutdown();
  // / releaseConversionBudget() has already warned if it could not deliver, so a failure
  // / here is noted at the normal activity tier only.
  if ($Verbose && !$BudgetWasReleased) logEntry('The '.$operationName.' operation budget token was not confirmed as returned. The reaper remains as the fallback.');
  return $BudgetWasReleased; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to request more runtime for a conversion that is still working.
// / Accepts the budget token & the number of additional seconds required.
// / Returns TRUE when the extension was granted or was not needed.
function requestRuntimeExtension($budgetToken, $requestedSeconds) {
  // / Set variables.
  global $ResourceAwarenessActive, $ManagerSocketTimeout, $EnableMemoryProtection;
  $ExtensionWasGranted = FALSE;
  $requestPayload = $replyPayload = array();
  $messageWasDelivered = FALSE;
  if (!$ResourceAwarenessActive or (string)$budgetToken === '') $ExtensionWasGranted = TRUE;
  else {
    $requestPayload = array('RequestType' => 'extend', 'BudgetToken' => (string)$budgetToken, 'RequestedSeconds' => (int)$requestedSeconds, 'WorkerPid' => getmypid());
    list ($messageWasDelivered, $replyPayload) = sendManagerMessage(buildManagerSocketPath('request-manager'), $requestPayload, 'worker', (int)$ManagerSocketTimeout * 3);
    // / An extension that was never answered is granted, for the same reason a budget request is.
    if (!isset($replyPayload['Approved'])) $ExtensionWasGranted = TRUE;
    else if ($replyPayload['Approved'] === TRUE) $ExtensionWasGranted = TRUE;
    else warningEntry('A runtime extension was refused. This worker may be reaped.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $requestPayload, $replyPayload, $messageWasDelivered, $budgetToken, $requestedSeconds);
  return $ExtensionWasGranted; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to give the web server user its own systemd manager & cgroup controllers.
// / Accepts no arguments & must be run as root.
// / Returns a success boolean & the number of steps that succeeded, in that order.
// / This is the safe way to let an unprivileged account set resource limits. A user manager
// / governs only the cgroup subtree systemd delegated to it & cannot start a unit as any
// / other account, so nothing granted here can be turned into privilege.
// / The alternative, granting the web server user manage-units on the system bus, would let
// / that account start a transient service with User=root. Never do that on the account
// / which parses uploaded files.
// / Every step is idempotent & is safe to run again.
function enableConversionLimits() {
  // / Set variables.
  global $ApacheUser, $RunningAsRoot, $Lol, $EnableMemoryProtection;
  $LimitsWereEnabled = $limitsSystemdUsable = FALSE;
  $StepsCompleted = 0;
  $limitsSystemdReason = '';
  $dropInDirectory = $dropInFile = $dropInContents = '';
  $commandOutput = array();
  $commandExitCode = 1;
  $bytesWritten = 0;
  if (!$RunningAsRoot) errorEntry('Conversion limits can only be enabled while running as root!', 31011, FALSE);
  // / Ask whether systemd is RUNNING, not whether its tools are installed. A container
  // / ships the tools & runs something else as PID 1, & lingering there writes a file
  // / nothing will ever read.
  else if (!systemdIsUsable()[0]) {
    list ($limitsSystemdUsable, $limitsSystemdReason) = systemdIsUsable();
    print('  Skipped    '.$limitsSystemdReason.$Lol);
    print('             Per conversion limits fall back to scheduling priority.'.$Lol); }
  else {
    // / Lingering starts a user manager for the account at boot, with no login session.
    // / Without it there is no user bus for systemd-run --user to reach.
    exec('loginctl enable-linger '.escapeshellarg($ApacheUser).' 2>&1', $commandOutput, $commandExitCode);
    if ($commandExitCode !== 0) print('  FAILED     Could not enable lingering for '.$ApacheUser.'.'.$Lol);
    else {
      $StepsCompleted++;
      print('  Enabled    Lingering for '.$ApacheUser.$Lol); }
    // / A user manager is given the memory & pids controllers by default. The processor
    // / controller has to be delegated explicitly or CPUQuota is silently ignored.
    $dropInDirectory = '/etc/systemd/system/user@.service.d';
    $dropInFile = $dropInDirectory.'/hrconvert2-delegate.conf';
    $dropInContents = '[Service]'.PHP_EOL.'Delegate=cpu cpuset io memory pids'.PHP_EOL;
    if (!is_dir($dropInDirectory)) @mkdir($dropInDirectory, 0755, TRUE);
    if (!is_dir($dropInDirectory)) print('  FAILED     Could not create '.$dropInDirectory.'.'.$Lol);
    else {
      $bytesWritten = @file_put_contents($dropInFile, $dropInContents);
      if ($bytesWritten !== strlen($dropInContents)) print('  FAILED     Could not write '.$dropInFile.'.'.$Lol);
      else {
        @chmod($dropInFile, 0644);
        $StepsCompleted++;
        print('  Wrote      '.$dropInFile.$Lol);
        exec('systemctl daemon-reload 2>&1', $commandOutput, $commandExitCode);
        if ($commandExitCode === 0) {
          $StepsCompleted++;
          print('  Reloaded   systemd unit configuration'.$Lol); }
        else print('  FAILED     Could not reload systemd. Reload it by hand.'.$Lol); } }
    if ($StepsCompleted >= 2) {
      $LimitsWereEnabled = TRUE;
      logEntry('Conversion limits were enabled for '.$ApacheUser.'. '.$StepsCompleted.' step(s) completed.');
      print('  Note       A running user manager must be restarted before delegation applies.'.$Lol);
      print('             systemctl restart user@$(id -u '.$ApacheUser.').service'.$Lol); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dropInDirectory, $dropInFile, $dropInContents, $commandOutput, $commandExitCode, $bytesWritten, $limitsSystemdUsable, $limitsSystemdReason);
  return array($LimitsWereEnabled, $StepsCompleted); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write the PHP settings HRConvert2 cannot run without.
// / Accepts a boolean permitting a write.
// / Returns a success boolean & the number of configurations written, in that order.
// /
// / A DROP IN IS WRITTEN. php.ini ITSELF IS NEVER EDITED.
// / Every PHP build reads conf.d after php.ini, so a drop in overrides the distribution
// / value without touching a file the package manager owns. A php.ini edited in place is
// / reverted by the next PHP upgrade & leaves an administrator wondering why uploads
// / started failing months later.
// / EVERY SAPI IS WRITTEN. The web server & the command line read different directories,
// / & a limit set for one while the other keeps the default is how a conversion that works
// / from a terminal refuses from a browser.
// / max_execution_time is derived from --Stream Duration Timeout--, because HRConvert2
// / holds a PHP worker for the whole of a backgrounded stream. A limit below that leaves
// / FFMPEG running with nothing left to terminate it.
function verifyPhpConfiguration($mayRepair) {
  // / Set variables.
  global $StreamWatchTimeout, $RunningAsRoot, $Lol, $Verbose, $EnableMemoryProtection, $DirSep;
  $ConfigurationIsValid = TRUE;
  $ConfigurationsWritten = 0;
  $dropInContents = $dropInPath = $existingContents = $candidateDir = '';
  $candidateDirectories = $globResults = array();
  $executionTime = $bytesWritten = 0;
  // / Two minutes of headroom over the longest stream this installation permits.
  $executionTime = (int)$StreamWatchTimeout + 120;
  if ($executionTime < 1200) $executionTime = 1200;
  $dropInContents = '; / Written by HRConvert2. Do not edit by hand.'.PHP_EOL
    .'; / HRCONVERT2-POLICY-MARKER'.PHP_EOL
    .'; / Re-run  sudo php convertCore.php -fp  to rewrite this file.'.PHP_EOL
    .'; / php.ini itself is never touched, so a PHP upgrade cannot revert these.'.PHP_EOL
    .PHP_EOL
    .'; / A PHP worker is held for the whole of a backgrounded stream conversion.'.PHP_EOL
    .'max_execution_time = '.$executionTime.PHP_EOL
    .'max_input_time = 90'.PHP_EOL
    .'memory_limit = 512M'.PHP_EOL
    .PHP_EOL
    .'; / An upload larger than these is refused by PHP before HRConvert2 ever sees it.'.PHP_EOL
    .'post_max_size = 5000M'.PHP_EOL
    .'upload_max_filesize = 5000M'.PHP_EOL
    .'max_file_uploads = 100'.PHP_EOL
    .PHP_EOL
    .'; / An error printed into a response corrupts the filename list the interface parses.'.PHP_EOL
    .'display_errors = Off'.PHP_EOL
    .'log_errors = On'.PHP_EOL
    .PHP_EOL
    .'zlib.output_compression = On'.PHP_EOL;
  // / Every SAPI this host might be running. A packaged PHP splits them by version & by
  // / server, an official container image uses one shared directory.
  $globResults = glob('/etc/php/*/*/conf.d');
  if (is_array($globResults)) $candidateDirectories = $globResults;
  if (is_dir('/usr/local/etc/php/conf.d')) $candidateDirectories[] = '/usr/local/etc/php/conf.d';
  if (empty($candidateDirectories)) {
    $ConfigurationIsValid = FALSE;
    warningEntry('No PHP configuration directory was found, so the settings HRConvert2 needs could not be written. Set them in php.ini by hand.'); }
  else {
    foreach ($candidateDirectories as $candidateDir) {
      $dropInPath = $candidateDir.$DirSep.'99-hrconvert2.ini';
      $existingContents = file_exists($dropInPath) ? (string)@file_get_contents($dropInPath) : '';
      if ($existingContents === $dropInContents) print('  '.str_pad('Unchanged', 12).$dropInPath.$Lol);
      else if (!$mayRepair or !$RunningAsRoot) {
        $ConfigurationIsValid = FALSE;
        print('  '.str_pad('Missing', 12).$dropInPath.$Lol); }
      else {
        $bytesWritten = @file_put_contents($dropInPath, $dropInContents);
        if ($bytesWritten !== strlen($dropInContents)) {
          $ConfigurationIsValid = FALSE;
          print('  '.str_pad('FAILED', 12).$dropInPath.$Lol); }
        else {
          @chmod($dropInPath, 0644);
          $ConfigurationsWritten++;
          print('  '.str_pad(($existingContents === '' ? 'Written' : 'Updated'), 12).$dropInPath.$Lol); } } }
    if ($ConfigurationsWritten > 0) {
      logEntry('Wrote PHP configuration to '.$ConfigurationsWritten.' location(s). max_execution_time '.$executionTime.'.');
      print('  '.str_pad('Note', 12).'Restart the web server for these to take effect.'.$Lol); } }
  if ($Verbose) logEntry('PHP Configuration: '.count($candidateDirectories).' location(s) examined, '.$ConfigurationsWritten.' written.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dropInContents, $dropInPath, $existingContents, $candidateDir, $candidateDirectories, $globResults, $executionTime, $bytesWritten, $mayRepair);
  return array($ConfigurationIsValid, $ConfigurationsWritten); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to confirm an Apache module is loaded, & to enable it when it is not.
// / Accepts the module name as Apache reports it & a boolean permitting repair, in that order.
// / Returns a loaded boolean & the status word, in that order.
// /
// / A <Directory> BLOCK THAT NEEDS A MODULE IS WORTH NOTHING WITHOUT IT.
// / The DATA rules are Header directives wrapped in <IfModule mod_headers.c>. That guard is
// / there so the configuration still PARSES on a server without the module, which is what
// / stops a missing module from turning into a 500. It also means that on such a server the
// / rules are skipped entirely, in silence, while the configuration reports itself written,
// / tested, reloaded & correct. Measured on a host with headers disabled; four green lines &
// / a DATA tree serving executable SVGs.
// /
// / So the module is not assumed. It is checked, & where it can be enabled it is enabled,
// / because -fp is already root & already editing this server's configuration. Turning on
// / the module that configuration depends on is the same act, not a larger one.
// /
// / apachectl -M IS THE ONLY HONEST ANSWER TO WHETHER A MODULE IS LOADED.
// / A file existing under mods-enabled says an administrator intended it, not that the
// / running server has it; the symlink may postdate the last reload, or point at nothing.
// / Asking the binary reports what is actually in the server.
function verifyApacheModule($moduleName, $mayRepair) {
  // / Set variables.
  global $RunningAsRoot, $EnableMemoryProtection;
  $ModuleIsLoaded = FALSE;
  $ModuleStatus = 'unknown';
  $controlBinary = $loadPath = $enabledPath = $moduleSlug = '';
  $commandOutput = array();
  $commandExitCode = 1;
  $linkWasMade = FALSE;
  $controlBinary = locateDependency('apachectl');
  if ($controlBinary === '') $controlBinary = locateDependency('apache2ctl');
  if ($controlBinary === '') {
    $ModuleStatus = 'unknown';
    warningEntry('apachectl could not be found, so it was not possible to confirm whether the '.$moduleName.' Apache module is loaded. The DATA rules depend on it.'); }
  else {
    exec(escapeshellarg($controlBinary).' -M 2>&1', $commandOutput, $commandExitCode);
    if (apacheModuleIsListed($moduleName, $commandOutput)) {
      $ModuleIsLoaded = TRUE;
      $ModuleStatus = 'ok'; }
    else {
      // / Debian & Ubuntu keep every module available & enable it with a link, which is all
      // / a2enmod does. The link is made directly, because a2enmod is a distribution script
      // / that a minimal image may not carry.
      $moduleSlug = str_replace('_module', '', (string)$moduleName);
      $loadPath = '/etc/apache2/mods-available/'.$moduleSlug.'.load';
      $enabledPath = '/etc/apache2/mods-enabled/'.$moduleSlug.'.load';
      if (!$mayRepair or !$RunningAsRoot) {
        $ModuleStatus = 'missing';
        warningEntry('The '.$moduleName.' Apache module is not loaded, so the DATA rules are being skipped. Run the -fp argument as root to enable it.'); }
      else if (!file_exists($loadPath)) {
        // / Nothing to enable. On a layout that compiles modules in, or one where the module
        // / is genuinely not installed, this application does not go looking further.
        $ModuleStatus = 'missing';
        warningEntry('The '.$moduleName.' Apache module is not loaded & no '.$loadPath.' exists to enable, so the DATA rules are being skipped. Install the module & run the -fp argument again.'); }
      else {
        if (!file_exists($enabledPath)) $linkWasMade = @symlink('../mods-available/'.$moduleSlug.'.load', $enabledPath);
        else $linkWasMade = TRUE;
        // / A module enabled now is not in the running server until it reloads, & the reload
        // / belongs to the caller so that one reload covers the module & the configuration.
        if ($linkWasMade) {
          $ModuleIsLoaded = TRUE;
          $ModuleStatus = 'enabled'; }
        else {
          $ModuleStatus = 'missing';
          warningEntry('The '.$moduleName.' Apache module could not be enabled at '.$enabledPath.', so the DATA rules are being skipped.'); } } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $controlBinary, $loadPath, $enabledPath, $moduleSlug, $commandOutput, $commandExitCode, $linkWasMade, $moduleName, $mayRepair);
  return array($ModuleIsLoaded, $ModuleStatus); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to find a module name in the output of apachectl -M.
// / Accepts the module name & the captured output lines, in that order.
// / Returns TRUE when that module is listed.
// / The output is one module per line, indented, & suffixed with (shared) or (static). A
// / plain comparison against the whole line therefore never matches, & a substring search
// / for a short name would match a longer one that contains it.
function apacheModuleIsListed($moduleName, $commandOutput) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ModuleWasFound = FALSE;
  $outputLine = '';
  if (is_array($commandOutput)) {
    foreach ($commandOutput as $outputLine) {
      if (strtok(trim((string)$outputLine), ' ') === (string)$moduleName) $ModuleWasFound = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $outputLine, $commandOutput, $moduleName);
  return $ModuleWasFound; }
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
// / The configuration is tested before it is allowed to take effect.
// / This application has already shipped one protection file that returned 500 for an entire
// / directory tree, so writing web server configuration & walking away is not something it
// / gets to do twice. The file is written, apachectl configtest is run, & a configuration
// / that does not pass is REMOVED again before anything reloads. A failed write leaves the
// / server exactly as it was found.
function verifyApacheConfiguration($mayRepair) {
  // / Set variables.
  global $ConvertTemp, $InstLoc, $RunningAsRoot, $Lol, $Verbose, $EnableMemoryProtection, $DirSep;
  $ConfigurationIsValid = TRUE;
  $ConfigurationsWritten = 0;
  $dropInContents = $dropInPath = $existingContents = $candidateDir = $enabledDir = $enabledPath = '';
  $controlBinary = $configTestOutput = $reloadOutput = '';
  $candidateDirectories = array();
  $commandOutput = array();
  $commandExitCode = $bytesWritten = 0;
  $configTestPassed = $baselineTestPassed = $linkWasMade = $writtenThisRun = $moduleIsLoaded = FALSE;
  $moduleStatus = '';
  if ((string)$ConvertTemp === '') {
    $ConfigurationIsValid = FALSE;
    warningEntry('The DATA location is not set, so the Apache configuration could not be built.'); }
  else {
    $dropInContents = '# / Written by HRConvert2. Do not edit by hand.'.PHP_EOL
      .'# / HRCONVERT2-POLICY-MARKER'.PHP_EOL
      .'# / Re-run  sudo php convertCore.php -fp  to rewrite this file.'.PHP_EOL
      .'# / Paths are read from this installation, so moving it moves these rules with it.'.PHP_EOL
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
      .'  Options -Indexes -ExecCGI'.PHP_EOL
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
      .'  RemoveHandler .php .phtml .phps .php3 .php4 .php5 .php7 .php8 .phar .cgi .pl .py .sh'.PHP_EOL
      .'</Directory>'.PHP_EOL;
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
        $existingContents = file_exists($dropInPath) ? (string)@file_get_contents($dropInPath) : '';
        if ($existingContents === $dropInContents) print('  '.str_pad('Unchanged', 12).$dropInPath.$Lol);
        else if (!$mayRepair or !$RunningAsRoot) {
          $ConfigurationIsValid = FALSE;
          print('  '.str_pad('Missing', 12).$dropInPath.$Lol); }
        else {
          $bytesWritten = @file_put_contents($dropInPath, $dropInContents);
          if ($bytesWritten !== strlen($dropInContents)) {
            $ConfigurationIsValid = FALSE;
            print('  '.str_pad('FAILED', 12).$dropInPath.$Lol); }
          else {
            @chmod($dropInPath, 0644);
            $ConfigurationsWritten++;
            $writtenThisRun = TRUE;
            print('  '.str_pad(($existingContents === '' ? 'Written' : 'Updated'), 12).$dropInPath.$Lol); } }
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
            // / Remove what we wrote. The server is left exactly as it was found.
            foreach ($candidateDirectories as $candidateDir) @unlink($candidateDir.$DirSep.'hrconvert2.conf');
            @unlink('/etc/apache2/conf-enabled/hrconvert2.conf');
            $ConfigurationIsValid = FALSE;
            $ConfigurationsWritten = 0;
            print('  '.str_pad('REMOVED', 12).'The configuration did not pass a syntax test & was withdrawn.'.$Lol);
            print('  '.str_pad('', 12).trim($configTestOutput).$Lol);
            errorEntry('The Apache configuration HRConvert2 wrote did not pass apachectl configtest & was removed again, so the web server is unchanged. The test reported: '.trim($configTestOutput), 504, FALSE); }
          else {
            print('  '.str_pad('Tested', 12).'The configuration parses.'.$Lol);
            // / A graceful reload finishes the requests already in flight & then picks up
            // / the new configuration. It cannot drop a connection & it cannot fail closed,
            // / because the test above already proved the configuration parses.
            $commandOutput = array();
            exec(escapeshellarg($controlBinary).' -k graceful 2>&1', $commandOutput, $commandExitCode);
            $reloadOutput = implode(' ', $commandOutput);
            if ($commandExitCode === 0) print('  '.str_pad('Reloaded', 12).'The web server picked up the new configuration.'.$Lol);
            else {
              warningEntry('The Apache configuration was written & tested but the web server could not be reloaded. Reload it by hand for the DATA rules to take effect.');
              print('  '.str_pad('Note', 12).'Could not reload the web server. Reload it by hand.'.$Lol); } } } }
      if ($ConfigurationsWritten > 0) logEntry('Wrote Apache configuration to '.$ConfigurationsWritten.' location(s) for '.$ConvertTemp.'.'); } }
  if ($Verbose) logEntry('Apache Configuration: '.count($candidateDirectories).' location(s) examined, '.$ConfigurationsWritten.' written.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dropInContents, $dropInPath, $existingContents, $candidateDir, $enabledDir, $enabledPath, $controlBinary, $configTestOutput, $reloadOutput, $candidateDirectories, $commandOutput, $commandExitCode, $bytesWritten, $configTestPassed, $baselineTestPassed, $linkWasMade, $writtenThisRun, $moduleIsLoaded, $moduleStatus, $mayRepair);
  return array($ConfigurationIsValid, $ConfigurationsWritten); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to render a filesystem path safely inside an Apache configuration file.
// / Accepts the path.
// / Returns the path quoted, with any quote or backslash in it escaped.
// / A directory name is not necessarily a bare word.
// / Apache treats whitespace as an argument separator, so an installation under a path with
// / a space in it silently produces a <Directory> block governing the wrong directory. A
// / quoted path is correct in every case & costs nothing in the ordinary one.
function escapeApacheConfigPath($configPath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $EscapedPath = '';
  $EscapedPath = '"'.str_replace(array('\\', '"'), array('\\\\', '\\"'), (string)$configPath).'"';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $configPath);
  return $EscapedPath; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to turn an internal policy status into the word an operator should read.
// / Accepts the internal status word.
// / Returns 'ok' when nothing needs doing, or the status unchanged when something does.
// /
// / A STATUS COLUMN IS SCANNED, NOT READ. IT MUST ONLY SAY ok WHEN NOTHING IS WRONG.
// / unrestricted, unconfined & distribution all describe a host that is already correct,
// / & all three read like something is missing. An administrator skimming a wall of output
// / for problems should not have to know which of the unusual looking words are the good
// / ones. The word becomes ok & the sentence beside it explains why.
// /
// / A state that changed something keeps its own word.
// / installed, repaired & corrected are not problems, but they did alter this machine, &
// / an operator is entitled to see that at a glance rather than have it flattened into ok.
function policyDisplayStatus($policyStatus) {
  // / Set variables.
  global $EnableMemoryProtection;
  $DisplayStatus = (string)$policyStatus;
  $benignStatuses = array('ok', 'unchanged', 'unrestricted', 'unconfined', 'distribution', 'absent', 'n/a');
  if (in_array((string)$policyStatus, $benignStatuses, TRUE)) $DisplayStatus = 'ok';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $benignStatuses, $policyStatus);
  return $DisplayStatus; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to explain a policy status in a sentence.
// / Accepts the policy name & the status word, in that order.
// / Returns a sentence saying whether anything needs doing.
// / A bare status word is not a report.
// / unrestricted & unconfined & distribution all mean nothing needs doing, & all three read
// / like something is missing. An operator should never have to ask which of the words on
// / this screen are the bad ones.
function describePolicyStatus($policyName, $policyStatus) {
  // / Set variables.
  global $EnableMemoryProtection;
  $StatusDescription = '';
  if ($policyStatus === 'ok') $StatusDescription = 'Matches this release. Nothing to do.';
  else if ($policyStatus === 'installed') $StatusDescription = 'Written for the first time.';
  else if ($policyStatus === 'repaired') $StatusDescription = 'Rewritten to match this release.';
  else if ($policyStatus === 'unchanged') $StatusDescription = 'Already correct. Nothing to do.';
  else if ($policyStatus === 'unrestricted') $StatusDescription = 'This kernel does not restrict unprivileged namespaces, so no profile is needed. Nothing to do.';
  else if ($policyStatus === 'unconfined') $StatusDescription = 'Nothing on this host confines OpenSCAD, so no override is needed. The bubblewrap sandbox is what isolates it. Nothing to do.';
  else if ($policyStatus === 'distribution') $StatusDescription = 'The distribution profile governs this binary & has been extended through its own include. Nothing to do.';
  else if ($policyStatus === 'absent') $StatusDescription = 'Not installed on this host, so there is nothing to write a policy for.';
  else if ($policyStatus === 'foreign') $StatusDescription = 'Present but not written by this application. Review it, or re-run -fp to replace it.';
  else if ($policyStatus === 'outdated') $StatusDescription = 'Written by an older release. Re-run -fp as root to repair it.';
  else if ($policyStatus === 'not loaded') $StatusDescription = 'ON DISK BUT NOT IN FORCE. The kernel is not using it. Reload AppArmor, or reboot.';
  else if ($policyStatus === 'refused') $StatusDescription = 'REFUSED. The existing file could not be backed up, so it was left alone.';
  else if ($policyStatus === 'removed, distribution profile kept') $StatusDescription = 'Our competing profile was removed. The distribution profile governs this binary.';
  else $StatusDescription = 'Reported '.$policyStatus.'.';
  // / The DATA policy is the one whose presence proves nothing.
  // / Every other policy here is read by the kernel or by a converter the moment it exists.
  // / This one is a .htaccess, which Apache reads only where AllowOverride is enabled &
  // / permits it, so 'Matches this release. Nothing to do.' would be a lie on
  // / exactly the installations that are exposed. The exposure check further down is the
  // / answer & this line points at it rather than pretending to be it.
  if ($policyStatus === 'disabled') $StatusDescription = 'Not maintained, because --Maintain HTAccess-- is FALSE in config.php. The server configuration is carrying these rules alone.';
  else if ($policyName === 'DATA Directory' && ($policyStatus === 'ok' or $policyStatus === 'installed' or $policyStatus === 'repaired')) $StatusDescription = $StatusDescription.' This file is INERT unless AllowOverride is enabled. See the exposure check below.';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $policyName, $policyStatus);
  return $StatusDescription; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to check that this host can actually do the work, without changing it.
// / Accepts no arguments.
// / Returns a readiness boolean & an array of findings, in that order.
// / THIS NEVER REPAIRS ANYTHING. Repair needs root & the listener does not have it, so a
// / validator that tried would fail confusingly on every pass. It reports, & -fp fixes.
// / Readiness is FALSE only for a condition that stops conversions outright. A policy that
// / has drifted is reported & does not fail the check, because conversions still run.
function validateOperatingEnvironment() {
  // / Set variables.
  global $RequireSandbox, $RunningInContainer, $RequireSandboxOnDocker, $EnableMemoryProtection;
  $EnvironmentIsReady = TRUE;
  $EnvironmentFindings = array();
  $sandboxIsRequired = $policyIsValid = $kernelIsReady = $dataIsProtected = FALSE;
  $policyStatus = $exposureStatus = $exposureDetail = '';
  $kernelFindings = $kernelFinding = array();
  // / The sandbox is the one thing a conversion cannot proceed without when it is required.
  $sandboxIsRequired = (bool)$RequireSandbox;
  if ($RunningInContainer && !$RequireSandboxOnDocker) $sandboxIsRequired = FALSE;
  // / Report the kernel settings first. A sandbox failure is almost always one of these &
  // / naming the setting is more use than naming the symptom.
  list ($kernelIsReady, $kernelFindings) = verifySandboxKernel(FALSE);
  foreach ($kernelFindings as $kernelFinding) $EnvironmentFindings[] = $kernelFinding;
  if (!$kernelIsReady) $EnvironmentIsReady = FALSE;
  if (verifyBwrap() === FALSE) {
    $EnvironmentFindings[] = array('Check' => 'Sandbox', 'Status' => 'FAILED', 'Detail' => 'Bubblewrap cannot build a namespace.'.($sandboxIsRequired ? ' Every conversion will be refused.' : ' Conversions will run unprotected.'));
    if ($sandboxIsRequired) $EnvironmentIsReady = FALSE; }
  else $EnvironmentFindings[] = array('Check' => 'Sandbox', 'Status' => 'ok', 'Detail' => 'Bubblewrap can build a namespace.');
  // / Policies are validated & never repaired here. A drifted policy is reported.
  // / The status word is reported as it is, rather than flattened to ok, & the sentence
  // / beside it says whether anything needs doing. Flattening lost the difference between
  // / a policy that matches & a host that never needed one.
  list ($policyIsValid, $policyStatus) = verifySandboxPolicy(FALSE);
  $EnvironmentFindings[] = array('Check' => 'Sandbox AppArmor', 'Status' => policyDisplayStatus($policyStatus), 'Detail' => describePolicyStatus('Sandbox AppArmor', $policyStatus));
  list ($policyIsValid, $policyStatus) = verifyImageMagickPolicy(FALSE);
  $EnvironmentFindings[] = array('Check' => 'ImageMagick policy', 'Status' => policyDisplayStatus($policyStatus), 'Detail' => describePolicyStatus('ImageMagick', $policyStatus));
  list ($policyIsValid, $policyStatus) = verifyOpenScadPolicy(FALSE);
  $EnvironmentFindings[] = array('Check' => 'OpenSCAD AppArmor', 'Status' => policyDisplayStatus($policyStatus), 'Detail' => describePolicyStatus('OpenSCAD AppArmor', $policyStatus));
  // / The DATA tree is part of the environment & is counted with everything else.
  // / It was reported separately & AFTER the summary line, so a run could print that every
  // / check passed & then say the tree was exposed directly underneath it. A summary that
  // / does not cover a check is worse than no summary, because it is read instead of the
  // / thing it failed to include. It is a finding now, so the count is honest & an exposed
  // / tree appears in the problems list where an operator is already looking.
  list ($dataIsProtected, $exposureStatus, $exposureDetail) = verifyDataExposure();
  $EnvironmentFindings[] = array('Check' => 'DATA exposure', 'Status' => ($exposureStatus === 'protected' ? 'ok' : strtoupper($exposureStatus)), 'Detail' => $exposureDetail);
  // / An exposed or broken tree does not stop a conversion, so it does not make the
  // / environment unready. It is reported loudly & the operator decides.
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $sandboxIsRequired, $policyIsValid, $policyStatus, $kernelIsReady, $kernelFindings, $kernelFinding, $dataIsProtected, $exposureStatus, $exposureDetail);
  return array($EnvironmentIsReady, $EnvironmentFindings); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read one check's status back out of an environment report.
// / Accepts the findings array & the name of the check, in that order.
// / Returns the status word, or an empty string when that check is not in the report.
// / A caller that needs to say more about one finding than a single line allows should not
// / have to run the check a second time to find out what it said.
function environmentFindingStatus($environmentFindings, $checkName) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FindingStatus = '';
  $finding = array();
  if (is_array($environmentFindings)) {
    foreach ($environmentFindings as $finding) {
      if (isset($finding['Check']) && $finding['Check'] === $checkName && isset($finding['Status'])) $FindingStatus = (string)$finding['Status']; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $finding, $environmentFindings, $checkName);
  return $FindingStatus; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to print an environment report.
// / Accepts the findings array & a boolean limiting the report to problems, in that order.
// / Returns the number of checks that were not ok.
// /
// / The same check reported twice in one run is noise, not thoroughness.
// / The -fp argument repairs the policies & then revalidates to prove the repairs took. Both
// / steps produce the same rows, so an operator was reading the AppArmor, ImageMagick &
// / OpenSCAD lines twice in a single run & the kernel line twice on top of that. Printing a
// / clean check a second time tells nobody anything; printing a check that is STILL wrong
// / after a repair tells them the repair did not work, which is the entire point of the
// / second pass. So the confirmation pass reports only what is still a problem & says so in
// / one line when there is nothing left to report.
function showEnvironmentFindings($environmentFindings, $onlyProblems) {
  // / Set variables.
  global $Lol, $EnableMemoryProtection;
  $ProblemsFound = 0;
  $finding = array();
  $findingIsBenign = FALSE;
  foreach ($environmentFindings as $finding) {
    // / policyDisplayStatus has already reduced every benign policy state to ok, so the
    // / column can be trusted. The words left here are the ones that changed something or
    // / went wrong, & only the second kind is a problem.
    $findingIsBenign = in_array($finding['Status'], array('ok', 'installed', 'repaired', 'corrected', 'removed', 'disabled'), TRUE);
    if (!$findingIsBenign) $ProblemsFound++;
    if (!$onlyProblems or !$findingIsBenign) print('  '.str_pad($finding['Check'], 28).str_pad($finding['Status'], 14).$finding['Detail'].$Lol); }
  if ($onlyProblems && $ProblemsFound === 0) print('  '.str_pad('All checks', 28).str_pad('ok', 14).count($environmentFindings).' check(s) passed. Nothing above needs attention.'.$Lol);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $finding, $findingIsBenign, $onlyProblems, $environmentFindings);
  return $ProblemsFound; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to correct ownership & permissions on every managed path.
// / Accepts no arguments & must be run as root.
// / Returns a success boolean & the number of paths corrected, in that order.
function fixManagedPermissions() {
  // / Set variables.
  global $InstLoc, $ConvertLoc, $ConvertTemp, $LogDir, $HomeLoc, $ProprietaryLoc, $BackupLoc, $ManagerSocketDir, $ApacheUser, $SecretFile, $Lol, $RunningAsRoot, $EnablePerConversionLimits, $RequiredSetupCoreVersion, $EnableMemoryProtection, $DirSep;
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
  $managedPaths = array();
  $managedPath = '';
  $commandOutput = array();
  $commandExitCode = 1;
  if (!$RunningAsRoot) errorEntry('Permissions can only be corrected while running as root!', 31008, FALSE);
  else {
    $managedPaths = array($InstLoc, $ConvertLoc, $ConvertTemp, $LogDir, $HomeLoc, $ProprietaryLoc, $BackupLoc, $ManagerSocketDir);
    foreach ($managedPaths as $managedPath) {
      if ($managedPath !== '' && is_dir($managedPath)) {
        exec('chown -R '.escapeshellarg($ApacheUser).':'.escapeshellarg($ApacheUser).' '.escapeshellarg($managedPath).' 2>&1', $commandOutput, $commandExitCode);
        exec('chmod -R 0755 '.escapeshellarg($managedPath).' 2>&1', $commandOutput, $commandExitCode);
        $PathsCorrected++; } }
    // / The socket directory is never world readable, whatever the sweep above set.
    if (is_dir($ManagerSocketDir)) exec('chmod 0700 '.escapeshellarg($ManagerSocketDir).' 2>&1', $commandOutput, $commandExitCode);
    // / The secret is the one file that must not be group or world readable.
    if (isset($SecretFile) && $SecretFile !== '' && file_exists($SecretFile)) {
      exec('chown '.escapeshellarg($ApacheUser).':'.escapeshellarg($ApacheUser).' '.escapeshellarg($SecretFile).' 2>&1', $commandOutput, $commandExitCode);
      exec('chmod 0600 '.escapeshellarg($SecretFile).' 2>&1', $commandOutput, $commandExitCode);
      $PathsCorrected++; }
    // / Install or repair every policy file while we are still root. A conversion pipeline
    // / reports a broken policy on every request & can never fix one, because rewriting a
    // / system policy from a web request is not something this application should ever do.
    print($Lol.'PHP configuration'.$Lol);
    list ($phpConfigIsValid, $phpConfigsWritten) = verifyPhpConfiguration(TRUE);
    // / The DATA rules belong in server configuration rather than in a .htaccess, so they
    // / are written, tested & activated here. The .htaccess further down stays as the
    // / fallback for an installation this cannot configure.
    print($Lol.'Apache configuration'.$Lol);
    list ($apacheConfigIsValid, $apacheConfigsWritten) = verifyApacheConfiguration(TRUE);
    print($Lol.'Sandbox kernel'.$Lol);
    list ($kernelIsReady, $kernelFindings) = verifySandboxKernel(TRUE);
    showEnvironmentFindings($kernelFindings, FALSE);
    // / Policy files. A count when every one is in place, & a named line when one is not.
    // / Half of these status words read like a fault & are not one, so a line that does
    // / appear carries the explanation with it.
    $policiesOk = $policiesTotal = 0;
    $policyLines = array();
    $policyChecks = array('Sandbox AppArmor', 'ImageMagick', 'OpenSCAD AppArmor');
    foreach ($policyChecks as $policyName) {
      $policiesTotal++;
      if ($policyName === 'Sandbox AppArmor') list ($policyIsValid, $policyStatus) = verifySandboxPolicy(TRUE);
      else if ($policyName === 'ImageMagick') list ($policyIsValid, $policyStatus) = verifyImageMagickPolicy(TRUE);
      else list ($policyIsValid, $policyStatus) = verifyOpenScadPolicy(TRUE);
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
    if ($EnablePerConversionLimits) {
      print($Lol.'Per conversion resource limits'.$Lol);
      list ($limitsWereEnabled, $limitSteps) = enableConversionLimits();
      if (!$limitsWereEnabled) warningEntry('Per conversion resource limits could not be fully enabled. '.$limitSteps.' step(s) completed.'); }
    // / The listener service unit, generated from this configuration. Setup Core owns it,
    // / so it is loaded on demand. An installation without that component simply skips it.
    print($Lol.'Listener service'.$Lol);
    list ($setupIsAvailable, $setupVersion) = verifyCoreComponent('Setup Core', 'SetupCore'.$DirSep.'setupCore.php', 'SetupCoreVersion', $RequiredSetupCoreVersion);
    if (!$setupIsAvailable) print('  Skipped     The Setup Core component is unavailable.'.$Lol);
    else installListenerService(TRUE);
    // / Prove the repairs worked. Writing an AppArmor profile & never re-testing the
    // / sandbox is how an operator ends up with a green permissions run & a server that
    // / refuses every conversion.
    print($Lol.'Verifying the operating environment.'.$Lol);
    list ($environmentIsReady, $environmentFindings) = validateOperatingEnvironment();
    showEnvironmentFindings($environmentFindings, TRUE);
    if (!$environmentIsReady) print($Lol.'The sandbox is still unavailable. A profile written now may need the AppArmor'.$Lol.'service reloaded, or a reboot, before the kernel honours it.'.$Lol);
    // / Writing the protection file above proved nothing, & the check that proves it is
    // / Part of the environment report printed directly above this.
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
    $PermissionsWereFixed = TRUE;
    logEntry('Permissions were corrected on '.$PathsCorrected.' managed path(s).'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $managedPaths, $managedPath, $commandOutput, $commandExitCode, $limitsWereEnabled, $limitSteps, $policyIsValid, $policyStatus, $exposureStatus, $setupIsAvailable, $setupVersion, $environmentIsReady, $environmentFindings, $kernelIsReady, $kernelFindings, $phpConfigIsValid, $phpConfigsWritten, $apacheConfigIsValid, $apacheConfigsWritten, $policyLines, $policyChecks, $policyName, $policyLine, $policiesOk, $policiesTotal);
  return array($PermissionsWereFixed, $PathsCorrected); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to ask the listener to terminate one tracked worker.
// / Accepts the budget token or the process identifier of the worker.
// / Returns TRUE when the worker was terminated.
function killTargetedWorker($workerTarget) {
  // / Set variables.
  global $ResourceAwarenessActive, $ManagerSocketTimeout, $Lol, $EnableMemoryProtection;
  $WorkerWasKilled = FALSE;
  $requestPayload = $replyPayload = $workerRegistry = array();
  $messageWasDelivered = $registryWasRead = FALSE;
  $cleanTarget = trim((string)$workerTarget);
  $targetPid = 0;
  $targetToken = '';
  if (!$ResourceAwarenessActive) print($Lol.'Resource awareness is unavailable, so no worker is tracked.'.$Lol);
  else if ($cleanTarget === '') print($Lol.'Supply a worker identifier or process identifier.'.$Lol);
  else {
    // / A numeric target is a process identifier. Anything else is treated as a token.
    if (ctype_digit($cleanTarget)) $targetPid = (int)$cleanTarget;
    else {
      $targetToken = preg_replace('/[^a-f0-9]/', '', strtolower($cleanTarget));
      list ($registryWasRead, $workerRegistry) = readManagerState('workers');
      if (isset($workerRegistry[$targetToken])) $targetPid = (int)$workerRegistry[$targetToken]['WorkerPid']; }
    if ($targetPid < 2) print($Lol.'That worker is not tracked.'.$Lol);
    else {
      $requestPayload = array('RequestType' => 'kill', 'WorkerPid' => $targetPid, 'BudgetToken' => $targetToken);
      list ($messageWasDelivered, $replyPayload) = sendManagerMessage(buildManagerSocketPath('core-manager'), $requestPayload, 'core', (int)$ManagerSocketTimeout);
      if ($messageWasDelivered && isset($replyPayload['Approved']) && $replyPayload['Approved'] === TRUE) $WorkerWasKilled = TRUE;
      print($Lol.($WorkerWasKilled ? 'Worker '.$targetPid.' terminated.' : 'Worker '.$targetPid.' could not be terminated.').$Lol); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $requestPayload, $replyPayload, $workerRegistry, $messageWasDelivered, $registryWasRead, $cleanTarget, $targetPid, $targetToken, $workerTarget);
  return $WorkerWasKilled; }
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
// / A function to ask for confirmation on a destructive command line action.
// / Accepts the prompt text & a boolean indicating confirmation was already given.
// / Returns TRUE when the action may proceed.
function confirmDestructiveAction($promptText, $confirmationSupplied) {
  // / Set variables.
  global $Lol, $EnableMemoryProtection;
  $ActionIsConfirmed = FALSE;
  $inputHandle = FALSE;
  $typedAnswer = '';
  if ($confirmationSupplied) $ActionIsConfirmed = TRUE;
  else {
    print($Lol.$promptText.$Lol.'Type YES to continue. Anything else cancels. '.$Lol);
    $inputHandle = @fopen('php://stdin', 'r');
    if ($inputHandle !== FALSE) {
      $typedAnswer = trim((string)fgets($inputHandle));
      @fclose($inputHandle);
      if ($typedAnswer === 'YES') $ActionIsConfirmed = TRUE; }
    if (!$ActionIsConfirmed) print($Lol.'Cancelled.'.$Lol); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $typedAnswer, $promptText, $confirmationSupplied);
  return $ActionIsConfirmed; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The main logic of the program that makes use of the functions above.

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
list ($InputsAreVerified, $ShowGUI, $GUI, $Color, $Language, $Token1, $Token2, $Height, $Width, $Rotate, $Bitrate, $Method, $Download, $UserFilename, $UserExtension, $FilesToArchive, $PDFWorkSelected, $ConvertSelected, $FilesToScan, $FilesToDelete, $UserScanType) = verifyInputs();
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

// / The following code verifies the logging environment.
list ($LogFileExists, $LogFile, $ClamLogFile) = verifyLogs();
if (!$LogFileExists) quickDie('Could not verify logging environment!', 9);
if ($Verbose) logEntry('Verified logging environment.');

// / The following code verifies & sanitizes global variables for the session.
list ($GlobalsAreVerified) = verifyGlobals();
if (!$GlobalsAreVerified) errorEntry('Could not verify globals!', 11, TRUE);
else if ($Verbose) logEntry('Verified globals.');

// / The following code verifies the Pipeline Manager & reads what each pipeline declares.
// / THIS RUNS AFTER verifyLogs() DELIBERATELY, because enumeration warns about every
// / pipeline it refuses & those warnings are worthless if there is nowhere to write them.
// / NO PIPELINE CODE IS EXECUTED HERE. Only pipelineConfig.php declarations are read, & a
// / pipelineCore.php is loaded later, once a conversion is actually about to be dispatched.
// / A missing or mismatched component is a WARNING & never an error. convert() carries a
// / built in dispatcher that configuration cannot overwrite, so an installation with no
// / Pipeline Manager at all converts exactly as it did before this component existed.
// / These four are stated by verifyGlobals(), which has already run.
list ($PipelineManagerActive, $PipelineManagerVersion) = verifyCoreComponent('Pipeline Manager', 'Pipelines'.$DirSep.'pipelineManager.php', 'PipelineManagerVersion', $RequiredPipelineManagerVersion);
if ($PipelineManagerActive) {
  list ($PipelinesAreEnumerated, $Pipelines, $PipelineCount) = enumeratePipelines();
  // / A manager that verified nothing is worse than no manager, because it would send every
  // / conversion to a dispatcher with nothing behind it. Stand it down & use the fallback.
  if (!$PipelinesAreEnumerated) $PipelineManagerActive = FALSE; }
if (!$PipelineManagerActive) warningEntry('The Pipeline Manager is unavailable. No conversion can run until it is repaired.');
else if ($Verbose) logEntry('Verified the Pipeline Manager. '.$PipelineCount.' conversion pipeline(s) are available.');

// / The following code decides if the security context being attempted matches a valid CLI or web request.
// / Error 27 should not be possible & should never be able to fire. If it does something is seriously wrong.
list($CommandLineHandled, $UserType) = parseCommandLine();
if ($CommandLineHandled && $UserType === 'web') errorEntry('Could not verify user type!', 27, TRUE);

// / If this is a CLI operation log a warning that conversion operations will be disabled.
if ($CommandLineHandled && $UserType === 'cli') warningEntry('CLI user detected. Conversion operations are disabled.');

// / Only enable file operations for web users, when the script is being run with Apache+PHP.
if (!$CommandLineHandled && $UserType === 'web') {
  if ($Verbose) logEntry('Web user detected. Conversion operations are enabled.');

  // / The following code ensures that the application cannot accidentally or maliciously be run as the root or standard user beyond this point.
  if ($RunningAsRoot or $CurrentUser !== $ApacheUser) errorEntry('The application is being run in an unsupported security context!', 28, TRUE);
  else logEntry('Verified security context. Currently running as the '.$CurrentUser.' user.');

  // / Only enable file operations for web users if the current user is the expected www-data user.
  if ($CurrentUser === $ApacheUser) {

    // / The following code tries to verify that the session is encrypted, if possible.
    list ($EncryptionVerified, $URLEcho) = verifyEncryption();
    if (!$EncryptionVerified) errorEntry('Could not verify connection!', 10, TRUE);
    else if ($Verbose) logEntry('Verified inbound connection.');

    // / The following code verifies that required directories exist & creates them where needed.
    list ($RequiredDirsExist, $RequiredDirs) = verifyRequiredDirs();
    if (!$RequiredDirsExist) errorEntry('Could not verify required directories!', 12, TRUE);
    else if ($Verbose) logEntry('Verified required directories.');

    // / The following code removes the build & development environments if config.php asks for it.
    list ($BuildEnvCleaned, $BuildEnvDeleted, $DevDocsDeleted) = cleanBuildEnvironment();
    if (!$BuildEnvCleaned) errorEntry('Could not clean the build environment!', 26, TRUE);
    else if ($Verbose) logEntry('Verified the build environment.');

    // / The following code removes old files from the $ConvertTempDir.
    list ($CleanedTempLoc, $TempLocDeepCleaned) = cleanDataLoc($ConvertTempDir, 'ConvertTempDir', $DeleteThreshold);
    if (!$CleanedTempLoc) errorEntry('Could not clean the temporary location!', 13, TRUE);
    else if ($Verbose) logEntry('Cleaned temporary location.');

    // / The following code removes old files from the $ConvertLoc.
    list ($CleanedConvertLoc, $ConvertLocDeepCleaned) = cleanDataLoc($ConvertLoc, 'ConvertLoc', $DeleteThreshold);
    if (!$CleanedConvertLoc) errorEntry('Could not clean the convert location!', 14, TRUE);
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

    // / Only enable file related operations if valid tokens were been supplied by the user.
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
          logEntry('Initiating Converter.');
          // / OCR is an operation pipeline & is dispatched from here rather than through
          // / convert(). It takes a selection of files rather than one file, it decides its
          // / own route per file, & it returns two values rather than the six a conversion
          // / pipeline returns. Forcing it into the conversion contract would have meant
          // / describing a batch as a single file & discarding most of what it reports.
          // / There is NO built in fallback left for OCR. Its body has moved out of this
          // / file, so a missing or mismatched OCR pipeline is reported rather than being
          // / quietly treated as an OCR run that produced nothing.
          if ($PipelineManagerActive && function_exists('runOcrOperation')) {
            list ($ConversionComplete, $ConversionErrors, $OcrOutputFilenames) = runOcrOperation($PDFWorkSelected, $UserFilename, $UserExtension, $Method);
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
          if ($Verbose) logEntry('Conversion Complete.'); }
        $BudgetWasReleased = giveBackOperationBudget('OCR'); }

      // / The following code is performed when a user performs a virus scan on a selection of files.
      if (isset($_POST['filesToScan']) && $AllowUserVirusScan) {
        if (!takeOperationBudget('user virus scan')) print($Alert3.$Lol);
        else {
          logEntry('Initiating User Virus Scannner.');
          list ($ScanComplete, $ScanErrors, $UserVirusFound) = userVirusScan($FilesToScan, $UserScanType);
          if (!$ScanComplete) errorEntry('User Virus Scan Failed!', 23, TRUE);
          if ($UserVirusFound) logEntry('The User Virus Scan detected infected files.');
          if (!$UserVirusFound) logEntry('The User Virus Scan did not detect any infected files.');
          if ($ScanErrors) logEntry('User Virus Scan finished with errors.');
          if ($Verbose) logEntry('User Virus Scan Complete.'); }
        $BudgetWasReleased = giveBackOperationBudget('user virus scan'); }

      // / Close the web server connection after all required content has been served.
      if ($Verbose) logEntry('Closing connection.');
      closeHRC2Connection();

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

// / Stop execution of the application.
die();
// / -----------------------------------------------------------------------------------
?>