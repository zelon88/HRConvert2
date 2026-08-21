<?php if (php_sapi_name() !== 'cli') print('<!DOCTYPE HTML>'.PHP_EOL);
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRConvert2, Copyright on 8/19/2026 by Justin Grimes, www.github.com/zelon88
// /
// / LICENSE INFORMATION ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / APPLICATION INFORMATION ...
// / This application is designed to provide a web-interface for converting file formats
// / on a server for users of any web browser without authentication.
// /
// / FILEINFORMATION ...
// / v3.7..
// / This file contains the core logic of the application.
// /
// / HARDWARE REQUIREMENTS ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / DEPENDENCY REQUIREMENTS ...
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
    'DefaultConversionCost', 'DefaultExpectedRuntime');
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
  // / Docker creates this file in every container it starts.
  if (file_exists('/.dockerenv')) $dockerEnvExists = TRUE;
  // / The init process of a container reports a container runtime in its cgroup path.
  $cgroupContents = @file_get_contents('/proc/1/cgroup');
  if (is_string($cgroupContents)) {
    if (strpos($cgroupContents, 'docker') !== FALSE or strpos($cgroupContents, 'containerd') !== FALSE or strpos($cgroupContents, 'kubepods') !== FALSE) $cgroupIndicatesContainer = TRUE; }
  // / Both signals must agree. Either one alone is not enough to relax a security control.
  if ($dockerEnvExists && $cgroupIndicatesContainer) $RunningInContainer = TRUE;
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
      if ($FailureIsFatal) {
        if ($loggingIsReady) errorEntry($failureText.' Execution cannot continue!', 40000, FALSE);
        print('ERROR!!! HRConvert2-40000: '.$failureText.' Execution cannot continue!'.PHP_EOL);
        if (function_exists('closeHRC2Connection')) closeHRC2Connection();
        die(); } } }
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
  global $LogFile, $EnableMemoryProtection;
  $SecretIsReady = FALSE;
  $ResolvedSecretKey = $secret = $secretFileContent = '';
  $detectedSecretVersion = $cleanRequiredVersion = $rotationNotice = $strayOutput = '';
  $secretGenerated = $existingIsUsable = $loggingIsReady = $payloadIsWellFormed = FALSE;
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
    // / require rather than require_once, because a second file may be resolved in the same request.
    // / The buffer is what stops a malformed file printing the key into the response.
    ob_start();
    require ($secretFile);
    $strayOutput = ob_get_clean();
    $detectedSecretVersion = ltrim(trim((string)$SecretVersion), 'vV');
    // / A file that emitted anything did not parse as PHP & cannot be trusted or reused.
    if ($strayOutput !== '') {
      $SecretKey = '';
      $rotationNotice = 'The secret file at '.$secretFile.' emitted '.strlen($strayOutput).' byte(s) instead of parsing as PHP. It is malformed & is being replaced.'; }
    else if (!empty($SecretKey) && strlen($SecretKey) === 64 && $detectedSecretVersion !== '' && $detectedSecretVersion === $cleanRequiredVersion) $existingIsUsable = TRUE; }
  // / Remove a secret file this core may not use, so a replacement can take its place.
  // / A file carrying no version predates version pinning & is always replaced.
  if (file_exists($secretFile) && !$existingIsUsable) {
    if ($rotationNotice === '') $rotationNotice = 'The secret file at '.$secretFile.' reports version '.($detectedSecretVersion === '' ? 'none' : 'v'.$detectedSecretVersion).' & this core requires v'.$cleanRequiredVersion.'. It is being replaced & every session derived from it is now invalid.';
    if ($loggingIsReady) warningEntry($rotationNotice);
    else error_log('WARNING!!! HRConvert2: '.$rotationNotice);
    @unlink($secretFile);
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
  if (!purgeSensitiveMemory($EnableMemoryProtection, $SecretKey, $SecretVersion, $secret, $secretFile, $secretFileContent, $bytesWritten, $secretGenerated, $existingIsUsable, $detectedSecretVersion, $cleanRequiredVersion, $rotationNotice, $strayOutput, $loggingIsReady, $payloadIsWellFormed, $requiredSecretVersion)) {
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
// / THE SECRET FILE IS ONLY TOUCHED BY AN AUTHORIZED COMBINATION OF USER & CONTEXT.
// / root from the command line, or the web server user from a web request, gets the install
// / wide secret. Any other command line user gets a secret of their own in their home
// / directory, which lets them run diagnostics without ever reading the install wide one.
// / Any other combination gets no secret at all & fails verification.
function verifyInstallation() {
  // / Set variables.
  global $URL, $VirusScan, $AllowUserVirusScan, $InstLoc, $ServerRootDir, $ConvertLoc, $LogDir, $LogFile, $ApplicationName, $ApplicationTitle, $SupportedLanguages, $DefaultLanguage, $AllowUserSelectableLanguage, $SupportedGuis, $DefaultGui, $AllowUserSelectableGui, $DeleteThreshold, $Verbose, $MaxLogSize, $Font, $ButtonStyle, $SupportedColors, $AllowUserSelectableColor, $ColorToUse, $ShowGUI, $ShowFinePrint, $TOSURL, $PPURL, $ScanCoreMemoryLimit, $ScanCoreChunkSize, $ScanCoreDebug, $ScanCoreVerbose, $SpinnerStyle, $SpinnerColor, $AllowUserShare, $SupportedConversionTypes, $VersionInfoFile, $Version, $UserArchiveArray, $UserDearchiveArray, $UserDocumentArray, $UserSpreadsheetArray, $UserPresentationInputArray, $UserPresentationOutputArray, $UserXPSInputArray, $UserXPSOutputArray, $UserImageArray, $UserMediaInputArray, $UserMediaOutputArray, $UserVideoInputArray, $UserVideoOutputArray, $UserStreamArray, $UserDrawingArray, $UserSVGInputArray, $UserSVGOutputArray, $UserModelArray, $UserSubtitleInputArray, $UserSubtitleOutputArray, $UserPDFWorkArr, $RARArchiveMethod, $RetryCount, $DocumentEngineSleepTimer, $HomeLoc, $ProprietaryLoc, $UsePatchedDocumentEngine, $StreamWatchTimeout, $StreamConnectionTimeout, $AllowStreamOverHTTP, $StreamInspectionLayers, $StreamInspectionFilesPerLayer, $DefaultStreamInspectionForfeitAction, $MaxStreamInspectionFileSize, $UniqueDailyLogHash, $AppendLogHashToLogFiles, $SecretKey, $SecretFile, $RequiredSecretVersion, $MinimumSCADVersion, $AllowSCADIncludeResolution, $SCADConversionTimeout, $UserSCADArray, $MinimumFFMPEGVersion, $MinimumStreamFFMPEGVersion, $MinimumLibreOfficeVersion, $ConfigVersion, $HRConvertVersion, $DeleteBuildEnvironment, $DeleteDevelopmentDocumentation, $MinimumInkscapeVersion, $RequiredGuiVersion, $RequiredLanguageVersion, $MinimumImageVersion, $UsePyMeshLab, $MinimumMeshlabVersion, $MinimumAssimpVersion, $RequiredConfigVersion, $EnableAutoUpdates, $AutoUpdateTargetVersion, $UpdateSourceRepository, $MaxUpdatePackageSize, $UpdateConnectionTimeout, $BackupLoc, $RequireSandbox, $ThrowSandboxWarning, $RequireSandboxOnDocker, $Minimum7zVersion, $MinimumZipVersion, $MinimumRarVersion, $MinimumTarVersion, $MinimumMkisofsVersion, $MinimumDiaVersion, $MinimumTesseractVersion, $MinimumPdftotextVersion, $RunningFromCLI, $CurrentUser, $RunningAsRoot, $RunningInContainer, $ApacheUser, $PermissionLevels, $AllowBootableIsoImage, $UserBootableIsoArray, $MinimumIsoHybridVersion, $MinimumCalibreVersion, $UserEbookInputArray, $UserEbookOutputArray, $EnableMemoryProtection, $ResourceAwarenessActive, $EnableResourceAwareness, $RequireResourceAwareness, $ManagerSocketDir, $DirSep, $RequiredCoreManagerVersion, $CoreManagerVersion, $CoreManagerSubprocessPollInterval, $ResourcePollInterval, $WorkerReapInterval, $WorkerStaleGracePeriod, $TotalResourceBudget, $ReserveResourcePercentage, $MaxConcurrentWorkers, $MaxExpectedRuntime, $MaxRuntimeExtensions, $DefaultConversionCost, $DefaultExpectedRuntime, $CoreLoaded;
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
  if ($RunningFromCLI && $CurrentUser === $ApacheUser && isset($_SERVER['argv'][1]) && in_array($_SERVER['argv'][1], array('--start-core-manager', '--start-manager'), TRUE)) {
    $secretAuthorized = TRUE;
    $userSecretAuthorized = FALSE; }
  // / Define what version of HRConvert2 this core file represents.
  // / Note that this number does not have to match the version numbers of individual components listed below.
  // / The version of the core is typically several versions ahead of indidual component versions. This is normal.
  $HRConvertVersion = 'v3.7.7';
  $HRConvertVersion = ltrim($HRConvertVersion, 'vV');
  // / Define the minimum acceptable config.php version that this convertCore.php can accept.
  // / This is only raised when a release adds or removes a config setting.
  // / A release that changes no settings leaves this alone, so existing config files keep working.
  // / Any config.php version that is greater (newer) than the version listed below is considered acceptable.
  $RequiredConfigVersion = 'v3.7.7';
  $RequiredConfigVersion = ltrim($RequiredConfigVersion, 'vV');
  // / Define the minimum acceptable GUI version that this convertCore.php can accept.
  // / Note that this check looks for the component version to be identical to what is listed below.
  // / Gui version that do not exactly match the version listed below are not considered acceptable.
  // / This is because Guis are not always guaranteed to be forward or reverse compatible.
  $RequiredGuiVersion = 'v3.7.4';
  $RequiredGuiVersion = ltrim($RequiredGuiVersion, 'vV');
  // / Define the minimum acceptable Language Pack version that this convertCore.php can accept.
  // / Note that this check looks for the component version to be identical to what is listed below.
  // / Language version that do not exactly match the version listed below are not considered acceptable.
  // / This is because Language Packs are not always guaranteed to be forward or reverse compatible.
  $RequiredLanguageVersion = 'v3.7.4';
  $RequiredLanguageVersion = ltrim($RequiredLanguageVersion, 'vV');
  // / The Core Manager component version this core requires.
  // / This is an EXACT match. A component built for another core may not be called safely.
  $RequiredCoreManagerVersion = 'v3.7.7';
  $RequiredCoreManagerVersion = ltrim($RequiredCoreManagerVersion, 'vV');
  // / The secret file version this core requires.
  // / This is an EXACT match. A secret file reporting anything else is deleted & rewritten.
  // / Raise this to force every installation in the wild off an exposed or outdated secret.
  // / Raising it invalidates every active session on the next request. That is the point.
  $RequiredSecretVersion = 'v3.7.y';
  $RequiredSecretVersion = ltrim($RequiredSecretVersion, 'vV');
  // / Define absolute paths for files that we only have relative paths for.
  $configFile = realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'config.php');
  $VersionInfoFile = realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'versionInfo.php');
  // / Check for required files & stop execution if they are missing.
  if (!file_exists($VersionInfoFile)) die ('ERROR!!! HRConvert2-24000: Could not process the HRConvert2 Version Information file (versionInfo.php)!'.PHP_EOL.'<br />');
  else require_once ($VersionInfoFile);
  if (!file_exists($configFile)) die ('ERROR!!! HRConvert2-0: Could not process the HRConvert2 Configuration file (config.php)!'.PHP_EOL.'<br />');
  else require_once ($configFile);
  $ConfigVersion = ltrim($ConfigVersion, 'vV');
  // / Perform a version integrity check.
  // / A core file that does not match versionInfo.php indicates a partial or interrupted update.
  if ($HRConvertVersion !== $Version) die ('ERROR!!! HRConvert2-28001: The core file reports version v'.$HRConvertVersion.' but the version information file reports version v'.$Version.'. This installation is incomplete or was updated incorrectly.'.PHP_EOL.'<br />');
  // / Confirm the config file carries every setting this core requires.
  // / An undefined setting reads as NULL, which silently becomes FALSE or zero at every point of use.
  list ($configIsValid, $missingConfigVars, $detectedConfigVersion) = verifyConfigVersion($RequiredConfigVersion);
  if (!$configIsValid) die ('ERROR!!! HRConvert2-28000: The config.php file is missing '.count($missingConfigVars).' required setting(s). Config version detected: v'.$detectedConfigVersion.'. Config version required: v'.$RequiredConfigVersion.'. Missing Variables: '.implode(', ', $missingConfigVars).PHP_EOL.'<br />');
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
  // / Resolve the Core Manager component. Resource awareness is unavailable without it.
  $ResourceAwarenessActive = FALSE;
  $ManagerSocketDir = $ConvertLoc.DIRECTORY_SEPARATOR.'Sockets';
  if ($secretIsReady && $EnableResourceAwareness) {
    list ($componentIsAvailable, $detectedCoreManagerVersion) = verifyCoreManagerComponent($RequiredCoreManagerVersion);
    if ($componentIsAvailable) $ResourceAwarenessActive = TRUE;
    // / An administrator may refuse to run without resource awareness.
    else if ($RequireResourceAwareness) errorEntry('Resource awareness is required by config.php & the Core Manager component is unavailable!', 31010, TRUE); }
  else if ($RequireResourceAwareness) errorEntry('Resource awareness is required by config.php but is disabled or has no secret!', 31010, TRUE);
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
  global $LogDir, $LogFile, $MaxLogSize, $InstLoc, $SesHash, $SesHash4, $DefaultLogDir, $DefaultLogSize, $Time, $Date, $LogInc, $LogInc2, $VirusScan, $ApplicationName, $ConvertLoc, $AppendLogHashToLogFiles, $ApacheUser, $PermissionLevels, $EnableMemoryProtection, $RunningAsRoot;
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
// / A function to format a log entry & write it to the logfile.
// / This function uses naieve memory cleanup routine deliberately out of neccesity.
// / A "Log Entry" starts with the word "OP-Act" which is intended to represent "Operational Activity".
// / A log entry is considered something that the indicates specific execution paths are taking place within the core.
// / A log entry represents normal operational activity of HRConvert2.
// / A log entry carries no error number because it does not represent a failure of HRConvert2.
// / A log can never halt execution of anything.
// / Logs occur normally.
// / With the Enhanced Logging Verbosity setting disabled you get a reasonable amount of log entries.
// / With Enhanced Logging Verbosityenabled you get, arguably, way too log entries.
// / Enhanced Logging Verbosity is probably not neccesary for most production or personal-use environments.
// / When searching logs using find/replace or grep, use this guide to make life easier;
// /   Op-Act - Reports normal activity. Will never halt execution. 
// /   WARNING!!! - Reports administrator discretion advised. Will never halt execution.
// /   ERROR!!! - Reports numbered errors. Calling code has the option to halt HRConvert2 execution.
function logEntry($entry) {
  // / Set variables.
  global $Time, $LogFile, $SesHash3;
  $LogWritten = FALSE;
  // / Format the input string into a log entry & write it to the $LogFile.
  $LogWritten = file_put_contents($LogFile, 'Op-Act, '.$Time.', '.$SesHash3.': '.$entry.PHP_EOL, FILE_APPEND);
  // / This cleanup is manual, because purgeSensitiveMemory() calls this function.
  $entry = NULL;
  unset($entry);
  return $LogWritten; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to format a warning entry & write it to the logfile.
// / This function uses naieve memory cleanup routine deliberately out of neccesity.
// / A "Warning Entry" starts with the word "WARNING!!!".
// / A warning entry is written regardless of the Enhanced Logging Verbosity setting.
// / A warning entry is considered something that the administrator should know is happening.
// / A warning entry does not necessarily mean something needs to change.
// / A warning entry carries no error number because it does not represent a failure of HRConvert2.
// / A warning entry can never halt execution of anything.
// / Warnings occur normally, but represent sensitive operations that should always be visible.
// / A warning message means administrator discretion is advised.
// / When searching logs using find/replace or grep, use this guide to make life easier;
// /   Op-Act - Reports normal activity. Will never halt execution. 
// /   WARNING!!! - Reports administrator discretion advised. Will never halt execution.
// /   ERROR!!! - Reports numbered errors. Calling code has the option to halt HRConvert2 execution.
function warningEntry($entry) {
  // / Set variables.
  global $Time, $LogFile, $SesHash3;
  $LogWritten = FALSE;
  // / Format the input string into a log entry & write it to the $LogFile.
  $LogWritten = file_put_contents($LogFile, 'WARNING!!! '.$Time.', '.$SesHash3.': '.$entry.PHP_EOL, FILE_APPEND);
  // / This cleanup is manual, because purgeSensitiveMemory() calls this function.
  $entry = NULL;
  unset($entry);
  return $LogWritten; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to format an error entry & write it to the logfile.
// / This function uses naieve memory cleanup routine deliberately out of neccesity.
// / A "Error Entry" starts with the word "ERROR!!!".
// / An error entry is written regardless of the Enhanced Logging Verbosity setting.
// / An error entry is considered something that the administrator should investigate. Maybe take action.
// / An error entry does means that HRConvert2 or one of it's operations outright failed.
// / An error entry carries a documented error number because it represents a failure of HRConvert2.
// / An error entry CAN HALT execution of HRConvert2, if the calling code requests it.
// / Errors are not normal. Something is wrong that needs attention. HRConvert2 may be inoperable.
// / All errors that are thrown will provide a unique error number.
// / Each unique error that HRConvert2 produces is documented in the "ERROR_DESCRIPTIONS.txt" file.
// / When searching logs using find/replace or grep, use this guide to make life easier;
// /   Op-Act - Reports normal activity. Will never halt execution. 
// /   WARNING!!! - Reports administrator discretion advised. Will never halt execution.
// /   ERROR!!! - Reports numbered errors. Calling code has the option to halt HRConvert2 execution.
function errorEntry($entry, $errorNumber, $die) {
  // / Set variables.
  global $Time, $LogFile, $SesHash3, $ApplicationName;
  $LogWritten = FALSE;
  // / Format the error number into a unique error identifier.
  if (!is_numeric($errorNumber)) $errorNumber = $ApplicationName.'-###';
  else $errorNumber = $ApplicationName.'-'.$errorNumber;
  // / Format the input string into a log entry with the error number & write it to the $LogFile.
  $LogWritten = file_put_contents($LogFile, 'ERROR!!! '.$Time.', '.$errorNumber.', '.$SesHash3.': '.$entry.PHP_EOL, FILE_APPEND);
  if ($die) die('ERROR!!! '.$Time.' '.$errorNumber.': '.$entry.PHP_EOL);
  // / This cleanup is manual, because purgeSensitiveMemory() calls this function.
  $entry = $errorNumber = $die = NULL;
  unset($entry, $errorNumber, $die);
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
  global $SecretKey, $EnableMemoryProtection;
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
  global $ShowGUI, $EnableMemoryProtection;
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
  global $GUI, $DefaultGui, $SupportedGuis, $AllowUserSelectableGui, $GuiFiles, $GuiDir, $GuiResourcesDir, $GuiImageDir, $GuiCSSDir, $GuiJSDir, $GuiHeaderFile, $GuiFooterFile, $GuiUI1File, $GuiUI2File, $GreenButtonCode, $BlueButtonCode, $RedButtonCode, $OrangeButtonCode, $PurpleButtonCode, $DarkButtonCode, $DefaultButtonCode, $Font, $GuiVersion, $RequiredGuiVersion, $EnableMemoryProtection;
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
    $GuiFiles = array();
    // / Verify that the required GUI folder & every required file exists.
    if (is_dir($GuiDir)) {
      foreach ($guiFiles as $reqFile) if (file_exists($reqFile)) array_push($GuiFiles, $reqFile);
      // / uiVersionInfo.php only assigns variables, so a failed GUI can still be replaced here.
      // / Nothing has printed yet, unlike the language pack case in buildGUI().
      if (count($guiFiles) === count($GuiFiles)) {
        require($GuiVersionFile);
        if ($GuiVersion === $RequiredGuiVersion) $GuiIsSet = TRUE;
        else warningEntry('GUI '.$GuiToUse.' reports version '.$GuiVersion.' but this core requires '.$RequiredGuiVersion.'.'); }
      else warningEntry('GUI '.$GuiToUse.' is missing one or more required files.'); }
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
purgeSensitiveMemory($EnableMemoryProtection, $defaultGuis, $defaultGui, $candidateGuis, $candidateGui, $reqFile, $guiFiles, $StyleCoreFile, $GuiVersionFile, $greenButtonCode, $blueButtonCode, $redButtonCode, $orangeButtonCode, $purpleButtonCode, $darkButtonCode, $defaultButtonCode);
  return array($GuiIsSet, $GuiToUse, $GuiDir, $GuiFiles); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to set the language to use for the session.
function verifyLanguage() {
  // / Set variables.
  global $Language, $DefaultLanguage, $SupportedLanguages, $AllowUserSelectableLanguage, $LanguageFiles, $GuiDir, $LanguageDir, $LanguageStringsFile, $LanguageFlagFile, $EnableMemoryProtection;
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
  // / Set the $Language variable to whatever the current language is so the next page will use the same one.
  $_GET['language'] = $LanguageToUse;
  // / Set the variables to required UI files.
  $LanguageDir = $GuiDir.'Languages/'.$LanguageToUse.'/';
  $LanguageStringsFile = $LanguageDir.'languageStrings.php';
  $LanguageFlagFile = $LanguageDir.'flag.png';
  $languageFiles = array($LanguageStringsFile, $LanguageFlagFile);
  // / Verify that the required langauge folder exists.
  if (!is_dir($LanguageDir)) $LanguageIsSet = FALSE;
  else {
    // / Verify that required language files exist.
    if (file_exists($LanguageStringsFile)) {
      foreach ($languageFiles as $reqFile) if (file_exists($reqFile)) array_push($LanguageFiles, $reqFile);
      if (count($LanguageFiles) > 0) if (count($languageFiles) === count($LanguageFiles)) $LanguageIsSet = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $defaultLanguages, $reqFile, $languageFiles, $defaultLanguage);
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
  global $URL, $URLEcho, $Date, $Time, $SesHash, $SesHash2, $SesHash3, $SesHash4, $CoreLoaded, $ConvertDir, $InstLoc, $ConvertTemp, $ConvertTempDir, $ConvertGuiCounter1, $DefaultApps, $RequiredDirs, $RequiredIndexes, $DangerousFiles, $Allowed, $ArchiveArray, $DearchiveArray, $DocumentArray, $SpreadsheetArray, $PresentationInputArray, $PresentationOutputArray, $XPSInputArray, $XPSOutputArray, $ImageArray, $MediaInputArray, $MediaOutputArray, $VideoInputArray, $VideoOutputArray, $StreamArray, $DrawingArray, $UserSVGInputArray, $SVGInputArray, $UserSVGOutputArray, $SVGOutputArray, $ModelArray, $SubtitleInputArray, $SubtitleOutputArray, $PDFWorkArr, $ConvertLoc, $DirSep, $SupportedConversionTypes, $Lol, $Lolol, $Append, $PathExt, $ConsolidatedLogFileName, $ConsolidatedLogFile, $Alert, $Alert1, $Alert2, $Alert3, $FCPlural, $FCPlural1, $FCPlural2, $FCPlural3, $UserClamLogFile, $UserClamLogFileName, $UserScanCoreLogFile, $UserScanCoreFileName, $SpinnerStyle, $SpinnerColor, $FullURL, $ServerRootDir, $StopCounter, $SleepTimer, $CurrentUser, $File, $HeaderDisplayed, $UIDisplayed, $FooterDisplayed, $LanguageStringsLoaded, $GUIDisplayed, $GUIDirection, $SupportedFormatCount, $GUIAlignment, $GreenButtonCode, $BlueButtonCode, $RedButtonCode, $PurpleButtonCode, $OrangeButtonCode, $DarkButtonCode, $DefaultButtonCode, $UserArchiveArray, $UserDearchiveArray, $UserDocumentArray, $UserSpreadsheetArray, $UserXPSInputArray, $UserXPSOutputArray, $UserPresentationInputArray, $UserPresentationOutputArray, $UserImageArray, $UserMediaInputArray, $UserMediaOutputArray, $UserVideoInputArray, $UserVideoOutputArray, $UserStreamArray, $UserDrawingArray, $UserModelArray, $UserSubtitleInputArray, $UserSubtitleOutputArray, $UserPDFWorkArr, $RetryCount, $DocumentEngineSleepTimer, $HomeLoc, $ProprietaryLoc, $RequiredCleanupFolders, $PathToUnoconv, $UsePatchedDocumentEngine, $StreamTemp, $StreamWatchTimeout, $StreamConnectionTimeout, $AllowStreamOverHTTP, $StreamInspectionLayers, $StreamInspectionFilesPerLayer, $DefaultStreamInspectionForfeitAction, $MaxStreamInspectionFileSize, $WaitForStream, $StreamPID, $StreamOutputPath, $LogDir, $StreamOutputArray, $ScadTemp, $AllowSCADIncludeResolution, $SCADConversionTimeout, $UserSCADArray, $SCADArray, $SCADOutputArray, $ProtectedRootDirs, $ResourcesDir, $BootloadersDir, $AllowBootableIsoImage, $UserBootableIsoArray, $BootableIsoArray, $MinimumCalibreVersion, $UserEbookInputArray, $UserEbookOutputArray, $EbookInputArray, $EbookOutputArray, $EnableMemoryProtection, $ManagerSocketDir, $ManagerSocketTimeout, $ManagerMessageBatchSize, $ManagerMessageSkew, $StartupKeyWindow, $ResourceAwarenessActive, $CoreManagerVersion, $RequiredCoreManagerVersion, $EnableResourceAwareness, $RequireResourceAwareness, $CoreManagerSubprocessPollInterval, $ResourcePollInterval, $WorkerReapInterval, $WorkerStaleGracePeriod, $TotalResourceBudget, $ReserveResourcePercentage, $MaxConcurrentWorkers, $MaxExpectedRuntime, $MaxRuntimeExtensions, $DefaultConversionCost, $DefaultExpectedRuntime;
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
  // / Stream related variables.
  // / The two stream timeouts are deliberately left in their documented units here.
  // / Do not convert them in this function.
  $WaitForStream = FALSE;
  $StreamPID = 0;
  $StreamOutputPath = '';
  // / URL related variables.
  $subDir = sanitizeString(str_replace($ServerRootDir.$DirSep, '', $InstLoc), FALSE);
  $partURL = sanitizeString($URL.'/'.$subDir, FALSE);
  $FullURL = 'http'.$URLEcho.'://'.$partURL;
  // / Directory related variables.
  $webRoot = $DirSep.'var'.$DirSep.'www';
  $ResourcesDir = $InstLoc.$DirSep.'Resources';
  $BootloadersDir = $ResourcesDir.$DirSep.'Bootloaders';
  // / Core Manager related variables.
  // / The socket directory lives in the data location & is never inside the web root.
  $ManagerSocketDir = $ConvertLoc.$DirSep.'Sockets';
  $ManagerSocketTimeout = 2;
  $ManagerMessageBatchSize = 32;
  $ManagerMessageSkew = 30;
  $StartupKeyWindow = 10;
  // / Core Manager related variables.
  // / The socket directory lives in the data location & is never inside the web root.
  $ManagerSocketDir = $ConvertLoc.$DirSep.'Sockets';
  $ManagerSocketTimeout = 2;
  $ManagerMessageBatchSize = 32;
  $ManagerMessageSkew = 30;
  $StartupKeyWindow = 10;
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
  $PathToUnoconv = $InstLoc.$DirSep.'Resources'.$DirSep.'Unoconv';
  // / Create a list of directories that will be emptied & remove if found.
  // / These folders are artifacts specifically from previous versions of HRConvert2 that are no longer required.
  $RequiredCleanupFolders = array($PathToUnoconv, $webRoot.$DirSep.'.cache', $webRoot.$DirSep.'.config', $InstLoc.$DirSep.'Logs', $InstLoc.$DirSep.'.cache', $InstLoc.$DirSep.'.config', $ProprietaryLoc.$DirSep.'.cache', $ProprietaryLoc.$DirSep.'.config', $InstLoc.$DirSep.'.github'.$DirSep.'workflows', $InstLoc.$DirSep.'.github', $InstLoc.$DirSep.'.git');
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
  global $Verbose, $EnableMemoryProtection;
  $ImageBinary = FALSE;
  $locatedBinary = $detectedVersion = '';
  $versionOutput = $versionMatches = $minimumParts = array();
  $versionExitCode = 1;
  $detectedMajor = $detectedMinor = $minimumMajor = $minimumMinor = 0;
  $locatedBinary = locateDependency('magick');
  if ($locatedBinary !== '') {
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
// / because the two routes through ocrFiles() need different ones & a caller taking a
// / single route tests that route's path rather than the overall boolean.
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
// / OpenSCAD writes its version banner to standard ERROR rather than standard output, so
// / stderr is redirected into the captured output & the exit code cannot be relied upon.
// / OpenSCAD uses a year & month rather than a major & minor, so the comparison is on the
// / year first & the month second, both numerically.
// / A build that reports no parseable version is refused, because an unknown build cannot
// / be cleared against a minimum.
function verifySCADVersion($MinimumVersion) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $SCADBinary = FALSE;
  $locatedBinary = $detectedVersion = '';
  $versionOutput = $versionMatches = $minimumParts = array();
  $versionExitCode = 1;
  $detectedYear = $detectedMonth = $minimumYear = $minimumMonth = 0;
  $locatedBinary = locateDependency('openscad');
  if ($locatedBinary !== '') {
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
// / THE VERSION BANNER IS THE LEAST CERTAIN THING HERE.
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
// / application. Bubblewrap may be installed & still be non functional, because
// / unprivileged user namespaces can be disabled at the kernel level, restricted by an
// / AppArmor profile, or blocked by a container runtime. Testing that the binary exists
// / proves nothing at all, so a real minimal sandbox is launched here instead.
// / A dependency that cannot be isolated must be refused rather than run unprotected, so a
// / FALSE from this function is the strongest signal the application produces.
function verifyBwrap() {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $BwrapBinary = FALSE;
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
      .' /usr/bin/true > /dev/null 2>&1';
    exec($bwrapCommand, $bwrapOutput, $bwrapExitCode);
    if ($bwrapExitCode === 0) $BwrapBinary = $locatedBinary; }
  if ($Verbose) logEntry('Bubblewrap Sandbox Check: '.($BwrapBinary === FALSE ? 'FAILED' : 'PASSED').', Exit code: '.$bwrapExitCode.($BwrapBinary === FALSE ? '' : ', Using: '.$BwrapBinary).'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $locatedBinary, $bwrapCommand, $bwrapOutput, $bwrapExitCode);
  return $BwrapBinary; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to display version information about this installation.
// / Called by the -v & --version command line arguments.
// / Reports the version of every component that carries one, & the state of every
// / dependency HRConvert2 enforces a minimum version against.
// / Every check performed here is the IDENTICAL check the matching converter performs, so
// / this answers whether an installation will actually work rather than what is configured.
// / A dependency reported here as OK is one whose located binary satisfied its minimum, &
// / the path that was verified is the path the converter will run.
// / Bubblewrap is the exception. It is a capability check rather than a version check,
// / because a working binary is not the same thing as a working sandbox.
// / An interface or language pack version is read by PATTERN rather than by loading the
// / file, because loading twenty version files would overwrite the variable each time.
function showVersionInfo() {
  // / Set variables.
  global $InstLoc, $HRConvertVersion, $ConfigVersion, $RequiredConfigVersion, $RequiredGuiVersion, $RequiredLanguageVersion, $ApplicationName, $SupportedConversionTypes, $SupportedGuis, $SupportedLanguages, $DirSep, $Lol, $UsePyMeshLab, $AllowBootableIsoImage, $RequireSandbox, $RequireSandboxOnDocker, $RunningInContainer, $MinimumFFMPEGVersion, $MinimumStreamFFMPEGVersion, $MinimumLibreOfficeVersion, $MinimumInkscapeVersion, $MinimumDiaVersion, $MinimumSCADVersion, $MinimumImageVersion, $MinimumAssimpVersion, $MinimumMeshlabVersion, $MinimumTesseractVersion, $MinimumPdftotextVersion, $Minimum7zVersion, $MinimumRarVersion, $MinimumZipVersion, $MinimumTarVersion, $MinimumMkisofsVersion, $MinimumIsoHybridVersion, $MinimumCalibreVersion, $RunningAsRoot, $RunningFromCLI, $CurrentUser, $EnableMemoryProtection, $EnableResourceAwareness, $RequireResourceAwareness, $ResourceAwarenessActive, $RequiredCoreManagerVersion, $CoreManagerVersion, $ManagerSocketDir, $TotalResourceBudget, $ReserveResourcePercentage, $MaxConcurrentWorkers, $MaxExpectedRuntime, $CoreManagerSubprocessPollInterval, $ResourcePollInterval, $WorkerReapInterval, $WorkerStaleGracePeriod;
  $VersionInfoDisplayed = $modelsAreValid = $ocrToolsAreValid = $archiveToolsAreValid = $libreOfficeIsValid = FALSE;
  $ffmpegBinary = $streamFfmpegBinary = $inkscapeBinary = $diaBinary = $scadBinary = $imageBinary = $ebookBinary = FALSE;
  $assimpBinary = $meshlabBinary = $tesseractBinary = $pdftotextBinary = FALSE;
  $sevenZipBinary = $rarBinary = $zipBinary = $tarBinary = $mkisofsBinary = $isoHybridBinary = FALSE;
  $listenerIsRunning = $bwrapBinary = FALSE;
  $listenerStatus = array();
  $installedGui = $installedLang = $installedEndonym = $checkDir = $checkFile = $foundVersion = $langLine = '';
  $guiMatches = $langMatches = array();
  $langOk = $langTotal = 0;
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
  // / Report the versions of every component that carries one.
  print($Lol);
  print($ApplicationName.$Lol);
  print('  Core version                '.$HRConvertVersion.$Lol);
  print('  Config version              '.$ConfigVersion.$Lol);
  print('  Config version required     '.$RequiredConfigVersion.' or later'.$Lol);
  print('  GUI version required        '.$RequiredGuiVersion.' exactly'.$Lol);
  print('  Language version required   '.$RequiredLanguageVersion.' exactly'.$Lol);
  print($Lol);
  // / Report the environment this installation is running in.
  print('Environment'.$Lol);
  print('  Container detected          '.($RunningInContainer ? 'YES' : 'NO').$Lol);
  print('  Sandbox required            '.(($RunningInContainer ? $RequireSandboxOnDocker : $RequireSandbox) ? 'YES' : 'NO').$Lol);  
  print('  Running as root             '.($RunningAsRoot ? 'YES' : 'NO').$Lol);
  print('  Current user                '.$CurrentUser.$Lol);    
  print($Lol);
  // / Report the state of every dependency HRConvert2 enforces a minimum against.
  print('Dependencies'.$Lol);
  print('  FFMPEG, audio & video       '.($ffmpegBinary === FALSE ? 'FAILED' : 'OK').', requires '.$MinimumFFMPEGVersion.' or later'.$Lol);
  print('  FFMPEG, streams             '.($streamFfmpegBinary === FALSE ? 'FAILED' : 'OK').', requires '.$MinimumStreamFFMPEGVersion.' or later'.$Lol);
  print('  LibreOffice, documents      '.($libreOfficeIsValid ? 'OK' : 'FAILED').', requires '.$MinimumLibreOfficeVersion.' or later'.$Lol);
  print('  ImageMagick, images         '.($imageBinary === FALSE ? 'FAILED' : 'OK').', requires '.$MinimumImageVersion.' or later'.$Lol);
  print('  Inkscape, SVG               '.($inkscapeBinary === FALSE ? 'FAILED' : 'OK').', requires '.$MinimumInkscapeVersion.' or later'.$Lol);
  print('  Dia, drawings               '.($diaBinary === FALSE ? 'FAILED' : 'OK').', requires '.$MinimumDiaVersion.' or later'.$Lol);
  print('  OpenSCAD, scad              '.($scadBinary === FALSE ? 'FAILED' : 'OK').', requires '.$MinimumSCADVersion.' or later'.$Lol);
  print('  Assimp, models              '.($assimpBinary === FALSE ? 'FAILED' : 'OK').', requires '.$MinimumAssimpVersion.' or later'.$Lol);
  print('  Meshlab, models, optional   '.($meshlabBinary === FALSE ? 'FAILED' : 'OK').', requires '.$MinimumMeshlabVersion.' or later'.$Lol);
  print('  PyMeshLab, models           '.($UsePyMeshLab === FALSE ? 'PyMeshLab is enabled, Meshlab not required' : 'PyMeshLab is disabled, Meshlab required').$Lol);
  print('  Tesseract, OCR              '.($tesseractBinary === FALSE ? 'FAILED' : 'OK').', requires '.$MinimumTesseractVersion.' or later'.$Lol);
  print('  Pdftotext, OCR              '.($pdftotextBinary === FALSE ? 'FAILED' : 'OK').', requires '.$MinimumPdftotextVersion.' or later'.$Lol);
  print('  7-Zip, ALL extraction       '.($sevenZipBinary === FALSE ? 'FAILED' : 'OK').', requires '.$Minimum7zVersion.' or later'.$Lol);
  print('  Zip, archives               '.($zipBinary === FALSE ? 'FAILED' : 'OK').', requires '.$MinimumZipVersion.' or later'.$Lol);
  print('  Tar, archives               '.($tarBinary === FALSE ? 'FAILED' : 'OK').', requires '.$MinimumTarVersion.' or later'.$Lol);
  print('  Mkisofs, iso                '.($mkisofsBinary === FALSE ? 'FAILED' : 'OK').', requires '.$MinimumMkisofsVersion.' or later'.$Lol);
  print('  Rar, archives, optional     '.($rarBinary === FALSE ? 'FAILED' : 'OK').', requires '.$MinimumRarVersion.' or later'.$Lol);
  print('  Isohybrid, iso, optional    '.($isoHybridBinary === FALSE ? 'FAILED' : 'OK').', requires '.$MinimumIsoHybridVersion.' or later'.$Lol);
  print('  Calibre, e-books            '.($ebookBinary === FALSE ? 'FAILED' : 'OK').', requires '.$MinimumCalibreVersion.' or later'.$Lol);
  if ($RunningInContainer) print('  Sandbox:  Docker            '.($bwrapBinary === FALSE ? 'FAILED' : 'OK').', '.($bwrapBinary === FALSE ? 'NOT functional' : 'functional').$Lol);
  else print('  Sandbox:  Bubblewrap        '.($bwrapBinary === FALSE ? 'FAILED' : 'OK').', '.($bwrapBinary === FALSE ? 'NOT functional' : 'functional').$Lol);
  print($Lol);
  // / A subsystem is only functional when every dependency it needs is.
  // / A single dependency reporting OK does not mean the operations that use it will run.
  print('Subsystem readiness'.$Lol);
  print('  3D models                   '.($modelsAreValid ? 'READY' : 'NOT READY').$Lol);
  print('  Optical character recognition '.($ocrToolsAreValid ? 'READY' : 'NOT READY').$Lol);
  print('  Archives                    '.($archiveToolsAreValid ? 'READY' : 'NOT READY').$Lol);
  print('  Bootable disk images        '.($AllowBootableIsoImage ? ($mkisofsBinary === FALSE ? 'NOT READY' : 'READY') : 'DISABLED in config.php').$Lol);
  print($Lol);
  // / Report every installed GUI & whether it matches the version the core requires.
  print('Installed interfaces'.$Lol);
  foreach ($SupportedGuis as $installedGui) {
    $checkDir = $InstLoc.$DirSep.'UI'.$DirSep.$installedGui;
    $checkFile = $checkDir.$DirSep.'uiVersionInfo.php';
    $foundVersion = '';
    if (!is_dir($checkDir)) print('  '.str_pad($installedGui, 28).'MISSING, no folder at '.$checkDir.$Lol);
    else if (!file_exists($checkFile)) print('  '.str_pad($installedGui, 28).'FAILED, no uiVersionInfo.php'.$Lol);
    else {
      // / The literal is read & then stripped of its leading v, because the file strips it
      // / too. Comparing the raw literal against the stripped requirement never matches.
      if (preg_match_all('/\$GuiVersion\s*=\s*[\'"]([^\'"]+)[\'"]/', (string)@file_get_contents($checkFile), $guiMatches)) $foundVersion = ltrim(end($guiMatches[1]), 'vV');
      if ($foundVersion === '') print('  '.str_pad($installedGui, 28).'FAILED, no version declared'.$Lol);
      else if ($foundVersion === $RequiredGuiVersion) print('  '.str_pad($installedGui, 28).'OK, '.$foundVersion.$Lol);
      else print('  '.str_pad($installedGui, 28).'FAILED, reports '.$foundVersion.$Lol); } }
  print($Lol);
  // / Report language pack coverage per interface.
  // / Twenty six packs times three interfaces is too many lines to list individually, so
  // / only the count & the failures are shown. A pack that fails is named.
  print('Installed language packs'.$Lol);
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
    print('  '.str_pad($installedGui, 28).$langOk.' of '.$langTotal.' OK'.($langLine === '' ? '' : ', failed:'.$langLine).$Lol); }
  print($Lol);
  // / Report the resource awareness component & the listener it depends on.
  print('Resource awareness'.$Lol);
  if (!$EnableResourceAwareness) print('  '.str_pad('Configured', 28).'DISABLED in config.php'.$Lol);
  else if (!$ResourceAwarenessActive) print('  '.str_pad('Component', 28).'FAILED, requires '.$RequiredCoreManagerVersion.' exactly'.$Lol);
  else {
    print('  '.str_pad('Component', 28).'OK, '.ltrim((string)$CoreManagerVersion, 'vV').', requires '.$RequiredCoreManagerVersion.' exactly'.$Lol);
    print('  '.str_pad('Enforced', 28).($RequireResourceAwareness ? 'YES, the core refuses to run without it' : 'NO, the core falls back to no resource checking').$Lol);
    print('  '.str_pad('Socket directory', 28).$ManagerSocketDir.$Lol);
    print('  '.str_pad('Total budget', 28).((int)$TotalResourceBudget < 1 ? 'AUTO, derived from the processor count' : (int)$TotalResourceBudget.' cost units').$Lol);
    print('  '.str_pad('Reserved share', 28).(int)$ReserveResourcePercentage.'%'.$Lol);
    print('  '.str_pad('Concurrent worker limit', 28).((int)$MaxConcurrentWorkers < 1 ? 'NONE, the budget decides alone' : (int)$MaxConcurrentWorkers).$Lol);
    print('  '.str_pad('Maximum runtime', 28).(int)$MaxExpectedRuntime.' second(s)'.$Lol);
    print('  '.str_pad('Supervisor interval', 28).(int)$CoreManagerSubprocessPollInterval.'s, resource '.(int)$ResourcePollInterval.'s, reap '.(int)$WorkerReapInterval.'s'.$Lol);
    print('  '.str_pad('Stale grace period', 28).(int)$WorkerStaleGracePeriod.' second(s)'.$Lol);
    list ($listenerIsRunning, $listenerStatus) = reportListenerStatus();
    print('  '.str_pad('Listener', 28).($listenerIsRunning ? 'RUNNING as process '.$listenerStatus['CoreManagerPid'] : 'STOPPED').$Lol);
    if ($listenerIsRunning) {
      print('  '.str_pad('Subordinate managers', 28).count($listenerStatus['Subordinates']).' of 3'.$Lol);
      print('  '.str_pad('Tracked workers', 28).$listenerStatus['TrackedWorkers'].$Lol);
      if (isset($listenerStatus['Budget']['RemainingBudget'])) print('  '.str_pad('Remaining budget', 28).$listenerStatus['Budget']['RemainingBudget'].' of '.$listenerStatus['Budget']['TotalBudget'].$Lol); }
    else print('  '.str_pad('', 28).'Start it with -l. Conversions run unchecked until it is up.'.$Lol); }
  print($Lol);

  // / Report which conversion types config.php has enabled.
  print('Enabled conversion types'.$Lol);
  print('  '.implode(', ', $SupportedConversionTypes).$Lol);
  print($Lol);
  // / Explain what a failure here actually means for the running application.
  print('A dependency that reports FAILED is missing, unidentifiable, or older than the minimum.'.$Lol);
  print('The conversion types which rely on it will refuse rather than fail unpredictably.'.$Lol);
  print('An interface or language pack that reports FAILED is not loaded & falls back to the default.'.$Lol);
  print('A sandbox that reports FAILED refuses every conversion unless config.php says otherwise.'.$Lol);
  print('See Documentation/ERROR_DESCRIPTIONS.txt for the error each one produces.'.$Lol);
  print($Lol);
  $VersionInfoDisplayed = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $modelsAreValid, $ocrToolsAreValid, $archiveToolsAreValid, $libreOfficeIsValid, $ffmpegBinary, $streamFfmpegBinary, $inkscapeBinary, $diaBinary, $scadBinary, $imageBinary, $assimpBinary, $meshlabBinary, $tesseractBinary, $pdftotextBinary, $sevenZipBinary, $rarBinary, $zipBinary, $tarBinary, $mkisofsBinary, $isoHybridBinary, $bwrapBinary, $installedGui, $installedLang, $installedEndonym, $checkDir, $checkFile, $foundVersion, $langLine, $guiMatches, $langMatches, $langOk, $langTotal, $ebookBinary, $listenerStatus, $listenerIsRunning);
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
  print('  -k, --kill                  Stop the resource listener.'.$Lol);
  print('  --status                    Report listener & resource budget state.'.$Lol);
  print('  -fp, --fix-permissions      Correct ownership & permissions on managed paths.'.$Lol);
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
  global $Verbose, $Lol, $DeleteThreshold, $ConvertLoc, $ConvertTempDir, $RunningFromCLI, $RunningAsRoot, $CurrentUser, $ApacheUser, $ResourceAwarenessActive, $EnableMemoryProtection;
  $CommandLineHandled = $cliTempCleaned = $cliTempDeepCleaned = $cliDataCleaned = $cliDataDeepCleaned = FALSE;
  $UserType = 'web';
  $cliArgumentCount = $cliThreshold = $cliPathsCorrected = 0;
  $cliArguments = $cliParts = $cliStatus = array();
  $cliCommand = $rawFirstArg = $cliTarget = $cliSecondTarget = '';
  $cliConfirmed = $cliListenerAuthorized = $cliActionConfirmed = $cliPermissionsFixed = $cliListenerRunning = FALSE;
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
        else dispatchManagerRole('core-manager', $cliTarget);
        $CommandLineHandled = TRUE; }
      // / Handle the internal subordinate manager entry point.
      else if ($cliCommand === '--start-manager') {
        if (!$ResourceAwarenessActive) errorEntry('A manager start was requested but the component is unavailable!', 31009, TRUE);
        else dispatchManagerRole($cliTarget, $cliSecondTarget);
        $CommandLineHandled = TRUE; }
      // / Handle the --status argument. Available to any user, reports what is running.
      else if ($cliCommand === '--status') {
        if (!$ResourceAwarenessActive) print($Lol.'Resource awareness is unavailable. The Core Manager component is missing or does not match this core.'.$Lol);
        else {
          list ($cliListenerRunning, $cliStatus) = reportListenerStatus();
          print($Lol.'Listener            '.($cliListenerRunning ? 'RUNNING as process '.$cliStatus['CoreManagerPid'] : 'STOPPED').$Lol);
          print('Subordinates        '.count($cliStatus['Subordinates']).$Lol);
          print('Tracked workers     '.$cliStatus['TrackedWorkers'].$Lol);
          if (isset($cliStatus['Budget']['RemainingBudget'])) print('Remaining budget    '.$cliStatus['Budget']['RemainingBudget'].' of '.$cliStatus['Budget']['TotalBudget'].$Lol);
          print($Lol); }
        $CommandLineHandled = TRUE; }
      // / Gate listener operation behind the authorized user check.
      else if ($cliListenerAuthorized && ($cliCommand === '-l' or $cliCommand === '--listen')) {
        if (!$ResourceAwarenessActive) print($Lol.'Resource awareness is unavailable. The Core Manager component is missing or does not match this core.'.$Lol);
        else {
          logEntry('Command line invocation. Starting the Core Manager listener.');
          startCoreManagerListener(); }
        $CommandLineHandled = TRUE; }
      // / Handle -k with no target as a listener stop & with a target as a worker kill.
      else if ($cliListenerAuthorized && ($cliCommand === '-k' or $cliCommand === '--kill')) {
        if (!$ResourceAwarenessActive) print($Lol.'Resource awareness is unavailable. There is no listener to stop.'.$Lol);
        else if ($cliTarget === '') {
          logEntry('Command line invocation. Stopping the Core Manager listener.');
          stopCoreManagerListener(); }
        else {
          logEntry('Command line invocation. Terminating worker '.$cliTarget.'.');
          killTargetedWorker($cliTarget); }
        $CommandLineHandled = TRUE; }
      // / Handle --kill-all-workers. Ends every TRACKED worker.
      else if ($cliListenerAuthorized && $cliCommand === '--kill-all-workers') {
        if (!$ResourceAwarenessActive) print($Lol.'Resource awareness is unavailable, so no worker is tracked.'.$Lol);
        else {
          $cliActionConfirmed = confirmDestructiveAction('This ends every tracked conversion in progress. Users will lose work.', $cliConfirmed);
          if ($cliActionConfirmed) {
            warningEntry('Command line invocation. Terminating every tracked worker.');
            print($Lol.'Terminated '.killTrackedWorkers().' tracked worker(s).'.$Lol); } }
        $CommandLineHandled = TRUE; }
      // / Handle --kill-every-worker. Ends every PHP process owned by the web server user.
      // / This reaches unrelated applications sharing the host & says so before it runs.
      else if ($cliListenerAuthorized && $cliCommand === '--kill-every-worker') {
        if (!$ResourceAwarenessActive) print($Lol.'Resource awareness is unavailable, so no worker registry exists.'.$Lol);
        else {
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
          print($Lol.'Unrecognized argument.'.$Lol);
          showHelpInfo();
          $CommandLineHandled = TRUE; } }
      // / An unrecognized argument is a mistake, not a web request.
      else {
        warningEntry('Command line invocation with an unrecognized argument.');
        print($Lol.'Unrecognized argument.'.$Lol);
        showHelpInfo();
        $CommandLineHandled = TRUE; } } }
  // / Determine if the user is using the application via command line (CLI) or Apache+PHP through a web browser.
  if ($CommandLineHandled === TRUE) $UserType = 'cli';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cliArguments, $cliCommand, $cliArgumentCount, $rawFirstArg, $cliParts, $cliTarget, $cliSecondTarget, $cliThreshold, $cliTempCleaned, $cliTempDeepCleaned, $cliDataCleaned, $cliDataDeepCleaned, $cliConfirmed, $cliListenerAuthorized, $cliActionConfirmed, $cliPermissionsFixed, $cliListenerRunning, $cliPathsCorrected, $cliStatus);
  return array($CommandLineHandled, $UserType); }
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
    // / Add the selected file to the array of clean files only if it is safe to handle.
    if (!in_array(strtolower($dirtyExt), $DangerousFiles) && !is_dir($pathToFiles.$DirSep.$dirtyFile)) array_push($Files, $dirtyFile);
    else if ($dirtyExt === '.' or $dirtyExt === '..') errorEntry('Could not display file '.$dirtyFile.'!', 400, FALSE); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dirtyFile, $pathToFiles, $dirtyFileArr, $dirtyExt);
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
      // / Every writable location a dependency reaches for is pointed at the tmpfs, so
      // / nothing tries to create state outside the namespace & fail.
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
  global $Verbose, $ClamLogFile, $AllowUserVirusScan, $Lol, $Lolol, $ApplicationName, $EnableMemoryProtection;
  $ScanComplete = TRUE;
  $VirusFound = FALSE;
  $returnData = '';
  $returnData = shell_exec(str_replace('  ', ' ', str_replace('  ', ' ', 'clamscan -r '.$path.' | grep FOUND >> '.$ClamLogFile)));
  $clamLogFileDATA = @file_get_contents($ClamLogFile);
  // / Check if ClamAV found an infection in the specified file.
  if (strpos($clamLogFileDATA, 'Virus Detected') !== FALSE or strpos($clamLogFileDATA, 'FOUND') !== FALSE) {
    $ScanComplete = $virusFound = TRUE;
    // / If the specified file exists, is infected, is not a directory, & $AllowUserVirusScan is set to FALSE then delete the infected file. 
    if (file_exists($path)) if (is_file($path) && !is_dir($path) && !$AllowUserVirusScan) @unlink($path);
    errorEntry('There were potentially infected files detected at '.$path.'!', 500, FALSE);
    errorEntry('ClamAV output the following: '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))), 501, TRUE); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $returnData, $clamLogFileDATA, $path);
  return array($ScanComplete, $VirusFound); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove a session directory once nothing of the user's remains in it.
// / Protected file objects such as the enforced index.html are removed only at this point.
function removeEmptiedSessionDir($sessionPath) {
  // / Set variables.
  global $DefaultApps, $DirSep, $EnableMemoryProtection;
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
  global $DefaultApps, $ProtectedRootDirs, $DirSep, $PermissionLevels, $Verbose, $ConvertLoc, $ConvertTempDir, $EnableMemoryProtection;
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
// / THE SEQUENCE IS STAGE, SWAP, VALIDATE, ROLLBACK ON FAILURE.
// / Nothing touches the live installation until a complete & correct replacement exists.
// / The previous installation is retained until the NEXT update runs, so an administrator
// / who discovers a problem an hour later still has something to restore by hand.
function updateApplication($requestedVersion) {
  // / Set variables.
  global $Verbose, $InstLoc, $ProprietaryLoc, $DirSep, $HRConvertVersion, $AutoUpdateTargetVersion, $EnableAutoUpdates, $BackupLoc, $RunningAsRoot, $ApacheUser, $PermissionLevels, $ConvertDir, $Lol, $EnableMemoryProtection;
  $UpdateSucceeded = $targetResolved = $packageDownloaded = $configMerged = $installationIsValid = FALSE;
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
  purgeSensitiveMemory($EnableMemoryProtection, $targetResolved, $packageDownloaded, $configMerged, $installationIsValid, $swapCompleted, $rolledBack, $targetVersion, $targetURL, $workDir, $downloadPath, $extractedDir, $stagedDir, $oldDir, $preservedSettings, $changedArrays, $extractOutput, $extractedRoots, $extractExitCode, $requestedVersion, $Lol);
  return $UpdateSucceeded; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to neutralize external references inside an uploaded document.
// / Accepts the absolute path of the document to sanitize.
// / Returns a sanitization boolean & the number of references neutralized, in that order.
// / LibreOffice resolves a linked image, an INCLUDEPICTURE field & an external relationship
// / target by fetching the URL, which turns a document upload into a server side request.
// / The sandbox is what actually stops that fetch. This runs in front of it so an operator
// / who has disabled sandboxing is not left completely unprotected.
// / A reference is blanked rather than removed, because deleting the element that carries it
// / leaves a dangling identifier & some readers refuse the whole file.
// / A format this function does not recognize is reported as unsanitized rather than clean.
function sanitizeDocumentLinks($documentPath) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $DocumentIsSanitized = FALSE;
  $ReferencesRemoved = 0;
  $documentExtension = $entryName = $entryContents = $cleanContents = $flatContents = '';
  $zipArchive = FALSE;
  $entryIndex = $entryCount = $replacementCount = 0;
  $packagedFormats = array('docx', 'docm', 'dotx', 'dotm', 'xlsx', 'xlsm', 'xltx', 'xltm', 'pptx', 'pptm', 'potx', 'potm', 'odt', 'ods', 'odp', 'odg', 'odf', 'ott', 'ots', 'otp');
  $flatFormats = array('fodt', 'fods', 'fodp', 'xml', 'rtf');
  $documentExtension = strtolower((string)pathinfo($documentPath, PATHINFO_EXTENSION));
  // / A packaged format is a zip. Every part that can carry a reference is rewritten in place.
  if (in_array($documentExtension, $packagedFormats, TRUE)) {
    if (!class_exists('ZipArchive')) warningEntry('The PHP zip extension is unavailable, so '.basename($documentPath).' could not be checked for external references. The sandbox remains the only protection.');
    else {
      $zipArchive = new ZipArchive();
      if ($zipArchive->open($documentPath) !== TRUE) warningEntry('Could not open '.basename($documentPath).' to check it for external references.');
      else {
        $entryCount = $zipArchive->numFiles;
        while ($entryIndex < $entryCount) {
          $entryName = (string)$zipArchive->getNameIndex($entryIndex);
          // / Only the XML parts carry references. Media & binary parts are left untouched.
          if (substr($entryName, -4) === '.xml' or substr($entryName, -5) === '.rels') {
            $entryContents = (string)$zipArchive->getFromIndex($entryIndex);
            $cleanContents = neutralizeDocumentReferences($entryContents, $replacementCount);
            if ($cleanContents !== $entryContents) {
              $zipArchive->addFromString($entryName, $cleanContents);
              $ReferencesRemoved = $ReferencesRemoved + $replacementCount; } }
          $entryIndex++; }
        $zipArchive->close();
        $DocumentIsSanitized = TRUE; } } }
  // / A flat format is a single file. The same rewrite applies to the whole of it.
  else if (in_array($documentExtension, $flatFormats, TRUE)) {
    $flatContents = (string)@file_get_contents($documentPath);
    if ($flatContents === '') warningEntry('Could not read '.basename($documentPath).' to check it for external references.');
    else {
      $cleanContents = neutralizeDocumentReferences($flatContents, $replacementCount);
      if ($cleanContents !== $flatContents) {
        @file_put_contents($documentPath, $cleanContents, LOCK_EX);
        $ReferencesRemoved = $replacementCount; }
      $DocumentIsSanitized = TRUE; } }
  // / Every other format reaches the converter unexamined & relies on the sandbox alone.
  else $DocumentIsSanitized = FALSE;
  if ($Verbose) logEntry('Document Sanitization: '.basename($documentPath).', Format: '.($documentExtension === '' ? 'NONE' : $documentExtension).', Examined: '.($DocumentIsSanitized ? 'YES' : 'NO').', References Neutralized: '.$ReferencesRemoved.'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $documentExtension, $entryName, $entryContents, $cleanContents, $flatContents, $entryIndex, $entryCount, $replacementCount, $packagedFormats, $flatFormats, $documentPath);
  return array($DocumentIsSanitized, $ReferencesRemoved); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to blank every external reference in one XML or flat document part.
// / Accepts the part contents & a counter that receives the number of replacements made.
// / Returns the rewritten contents.
// / Three carriers are handled. An OOXML relationship marked external, a field instruction
// / that names a fetching command & an ODF or SVG style link attribute.
// / A relative target is left alone, because it cannot leave the document package.
function neutralizeDocumentReferences($partContents, &$replacementCount) {
  // / Set variables.
  global $EnableMemoryProtection;
  $CleanContents = (string)$partContents;
  $runningCount = $stepCount = 0;
  $remoteSchemes = 'https?|ftps?|file|smb|ldap|gopher|dict|jar|mailto';
  // / An OOXML relationship that declares itself external. The target is emptied in place.
  $CleanContents = preg_replace('/(<Relationship\b(?=[^>]*TargetMode\s*=\s*"External")[^>]*?\sTarget\s*=\s*")[^"]*(")/i', '$1$2', $CleanContents, -1, $stepCount);
  $runningCount = $runningCount + (int)$stepCount;
  // / A relationship target carrying a remote scheme, whether or not it declares itself external.
  $CleanContents = preg_replace('/(<Relationship\b[^>]*?\sTarget\s*=\s*")(?:'.$remoteSchemes.'):[^"]*(")/i', '$1$2', $CleanContents, -1, $stepCount);
  $runningCount = $runningCount + (int)$stepCount;
  // / A field instruction that tells the renderer to go and fetch something.
  // / The instruction text is emptied & the element is left in place so the run stays valid.
  $CleanContents = preg_replace('/(<w:instrText\b[^>]*>)[^<]*\b(?:INCLUDEPICTURE|INCLUDETEXT|IMPORT|LINK|DDEAUTO|DDE|HYPERLINK)\b[^<]*(<\/w:instrText>)/i', '$1$2', $CleanContents, -1, $stepCount);
  $runningCount = $runningCount + (int)$stepCount;
  // / The same instruction expressed as an attribute rather than as element text.
  $CleanContents = preg_replace('/(\sw:instr\s*=\s*")[^"]*\b(?:INCLUDEPICTURE|INCLUDETEXT|IMPORT|LINK|DDEAUTO|DDE)\b[^"]*(")/i', '$1$2', $CleanContents, -1, $stepCount);
  $runningCount = $runningCount + (int)$stepCount;
  // / An ODF, SVG or generic xlink reference pointing off the machine.
  $CleanContents = preg_replace('/(\sxlink:href\s*=\s*")(?:'.$remoteSchemes.'):[^"]*(")/i', '$1$2', $CleanContents, -1, $stepCount);
  $runningCount = $runningCount + (int)$stepCount;
  // / A spreadsheet external workbook reference.
  $CleanContents = preg_replace('/(<externalReference\b[^>]*\sr:id\s*=\s*")[^"]*(")/i', '$1$2', $CleanContents, -1, $stepCount);
  $runningCount = $runningCount + (int)$stepCount;
  $replacementCount = $runningCount;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $CleanContents is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $runningCount, $stepCount, $remoteSchemes, $partContents);
  return $CleanContents; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert one document with LibreOffice inside a sandbox.
// / Accepts the input path, the intended output path & the target extension, in that order.
// / Returns a completion boolean & whatever the converter printed, in that order.
// / This replaces the persistent unoconv listener for every document conversion.
// / The listener parsed hostile documents in a long lived process with full network access,
// / so sandboxing the client that talked to it protected nothing. A per conversion process
// / inside sandboxCommand has no network at all, which closes every URL handler at once.
// / LibreOffice names its own output from the input basename, so the result is renamed when
// / the caller asked for something else.
// / The output directory is a bare dot, because sandboxCommand changes into the writable
// / mount before running. When no sandbox could be built the directory is named outright.
function convertWithLibreOffice($inputPath, $outputPath, $targetExtension) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $ConversionCompleted = FALSE;
  $ReturnData = '';
  $sofficeBinary = $sofficeCommand = $sandboxedCommand = $producedPath = $cleanExtension = '';
  $commandMayRun = $documentIsSanitized = FALSE;
  $referencesRemoved = 0;
  // / A filter may be appended to a format with a colon, so a colon is permitted here.
  $cleanExtension = preg_replace('/[^A-Za-z0-9_:.\-]/', '', (string)$targetExtension);
  $sofficeBinary = locateDependency('soffice');
  if ($sofficeBinary === '') $sofficeBinary = locateDependency('libreoffice');
  if ($sofficeBinary === '') errorEntry('LibreOffice could not be located for a document conversion!', 2002, FALSE);
  else if ($cleanExtension === '') errorEntry('A document conversion was requested with an unusable target format!', 2003, FALSE);
  else {
    // / Neutralize external references before the converter ever opens the file.
    list ($documentIsSanitized, $referencesRemoved) = sanitizeDocumentLinks($inputPath);
    if ($referencesRemoved > 0) warningEntry('Document Sanitization neutralized '.$referencesRemoved.' external reference(s) in '.basename($inputPath).' before conversion. A document requesting a remote resource is worth investigating.');
    // / Every flag here suppresses a prompt, a lock file or a recovery dialog that would
    // / otherwise hang a headless process forever.
    // / The user profile is written inside the sandbox tmpfs, so no state survives the run &
    // / no two conversions can ever share a profile.
    $sofficeCommand = escapeshellarg($sofficeBinary)
      .' --headless --norestore --invisible --nolockcheck --nodefault --nofirststartwizard --nologo'
      .' -env:UserInstallation=file:///tmp/hrc2-libreoffice'
      .' --convert-to '.escapeshellarg($cleanExtension)
      .' --outdir .'
      .' '.escapeshellarg($inputPath);
    list ($commandMayRun, $sandboxedCommand) = sandboxCommand($sofficeCommand, $inputPath, $outputPath, FALSE, 'libreoffice');
    // / sandboxCommand returns the command untouched when no sandbox could be built.
    // / A bare output directory only works because the sandbox changes into it, so it is
    // / named outright on the unsandboxed path rather than writing wherever PHP happens to be.
    if ($sandboxedCommand === $sofficeCommand) $sandboxedCommand = str_replace(' --outdir . ', ' --outdir '.escapeshellarg(dirname($outputPath)).' ', $sandboxedCommand);
    if (!$commandMayRun) errorEntry('A document conversion was refused because no sandbox could be built!', 2004, FALSE);
    else {
      $ReturnData = (string)shell_exec('LANG=C.UTF-8 LC_ALL=C.UTF-8 '.$sandboxedCommand.' 2>&1');
      // / LibreOffice derives the output name from the input, so rename when it differs.
      $producedPath = dirname($outputPath).DIRECTORY_SEPARATOR.pathinfo($inputPath, PATHINFO_FILENAME).'.'.$cleanExtension;
      if ($producedPath !== $outputPath && file_exists($producedPath) && !file_exists($outputPath)) @rename($producedPath, $outputPath);
      if (file_exists($outputPath)) $ConversionCompleted = TRUE; } }
  if ($Verbose) logEntry('LibreOffice Conversion: '.basename($inputPath).' to '.$cleanExtension.', Sandboxed: '.($sandboxedCommand === $sofficeCommand ? 'NO' : 'YES').', Result: '.($ConversionCompleted ? 'OK' : 'FAILED').'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $ReturnData is not purged, because it is a return value.
  purgeSensitiveMemory($EnableMemoryProtection, $sofficeBinary, $sofficeCommand, $sandboxedCommand, $producedPath, $cleanExtension, $commandMayRun, $documentIsSanitized, $referencesRemoved, $inputPath, $outputPath, $targetExtension);
  return array($ConversionCompleted, $ReturnData); }
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to verify that the Document Conversion Engine is installed & running.
// / LibreOffice is version checked here, because every document conversion depends on it.
// / LibreOffice itself is version checked here, because every document conversion depends on it.
// / The listener is only started once the installation & the version have both been cleared.
function verifyDocumentConversionEngine() {
  // / Set variables.
  global $Verbose, $MinimumLibreOfficeVersion, $EnableMemoryProtection;
  $DocEnginePID = 0;
  $DocumentEngineStarted = $libreOfficeVersionIsValid = FALSE;
  $sofficeBinary = '';
  // / LibreOffice is the engine behind every document, spreadsheet & presentation conversion.
  $libreOfficeVersionIsValid = verifyLibreOfficeVersion($MinimumLibreOfficeVersion);
  if (!$libreOfficeVersionIsValid) errorEntry('The installed LibreOffice version is missing, unidentifiable, or too old!', 2001, TRUE);
  else {
    $sofficeBinary = locateDependency('soffice');
    if ($sofficeBinary === '') $sofficeBinary = locateDependency('libreoffice');
    if ($sofficeBinary === '') errorEntry('Could not locate the LibreOffice binary!', 2000, TRUE);
    else $DocumentEngineStarted = TRUE; }
  // / Report a leftover listener. It is an open parsing surface that nothing needs any more.
  $DocEnginePID = (int)trim((string)shell_exec('pgrep soffice.bin | head -n 1'));
  if ($DocEnginePID > 0) warningEntry('A persistent LibreOffice listener is running as process '.$DocEnginePID.'. It is no longer used & is an unsandboxed parsing surface. Remove it from rc.local or the container entrypoint.');
  if ($Verbose && $DocumentEngineStarted) logEntry('Verified the Document Conversion Engine. Conversions run sandboxed, one process each.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $libreOfficeVersionIsValid, $sofficeBinary);
  return array($DocumentEngineStarted, $DocEnginePID); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert document formats.
function convertDocuments($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer, $XPSInputArray, $EnableMemoryProtection;
  $ConversionSuccess = $ConversionErrors = $documentEngineStarted = $conversionCompleted = $commandMayRun = FALSE;
  $returnData = $xpsCommand = $sandboxedCommand = '';
  $stopper = $documentEnginePID = 0;
  $sleepTime = $SleepTimer;
  $arrayxpsi = array('xps', 'oxps');
  $oldExtension = getExtension($pathname);
  // / The following code verifies that the Document Conversion Engine is installed & usable.
  list ($documentEngineStarted, $documentEnginePID) = verifyDocumentConversionEngine();
  if (!$documentEngineStarted) {
    $ConversionErrors = TRUE;
    errorEntry('Could not verify the Document Conversion Engine!', 7000, FALSE); }
  else if ($Verbose) logEntry('Verified the Document Conversion Engine.');
  // / The following code performs the actual document conversion.
  if ($documentEngineStarted) {
    if ($Verbose) logEntry('Converting document.');
    // / This code will attempt the conversion up to $StopCounter number of times.
    while (!file_exists($newPathname) && $stopper <= $StopCounter) {
      // / If the last conversion attempt failed, wait a moment before trying again.
      if ($stopper !== 0) sleep($sleepTime++);
      // / An XPS file is handled by xpstopdf, which is also sandboxed & also has no network.
      // / Both arguments are escaped. An unescaped filename here was a command injection.
      if (in_array(strtolower($oldExtension), $arrayxpsi)) {
        $xpsCommand = 'xpstopdf '.escapeshellarg($pathname).' '.escapeshellarg($newPathname);
        list ($commandMayRun, $sandboxedCommand) = sandboxCommand($xpsCommand, $pathname, $newPathname, FALSE, 'poppler');
        if (!$commandMayRun) errorEntry('An XPS conversion was refused because no sandbox could be built!', 2004, FALSE);
        else $returnData = (string)shell_exec($sandboxedCommand.' 2>&1'); }
      // / Everything else goes to LibreOffice, one sandboxed process per conversion.
      else list ($conversionCompleted, $returnData) = convertWithLibreOffice($pathname, $newPathname, $extension);
      // / Count the number of conversions to avoid infinite loops.
      $stopper++;
      // / Stop attempting the conversion after $StopCounter number of attempts.
      if ($stopper === $StopCounter) {
        $ConversionErrors = TRUE;
        errorEntry('The document converter timed out!', 7001, FALSE); } }
    // / Log the output of the operation to the logfile, if it is not blank.
    if ($Verbose && trim($returnData) !== '') logEntry('The document converter returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData))))); }
  if (file_exists($newPathname)) $ConversionSuccess = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $stopper, $pathname, $returnData, $documentEngineStarted, $documentEnginePID, $conversionCompleted, $commandMayRun, $xpsCommand, $sandboxedCommand, $sleepTime, $oldExtension, $arrayxpsi);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert image formats.
// / The binary is supplied by verifyImageVersion() rather than hardcoded, so the version
// / that was verified is provably the version that runs.
// / A dimension of zero is omitted from the geometry rather than written, so the other
// / dimension scales to it. ImageMagick treats WxH as a bounding box & preserves the
// / aspect ratio, so an exclamation mark is added when the user supplied both & therefore
// / asked for both exactly.
function convertImages($pathname, $newPathname, $extension, $height, $width, $rotate) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer, $MinimumImageVersion, $EnableMemoryProtection;
  $ConversionSuccess = $ConversionErrors = $sandboxIsAvailable = FALSE;
  $imageBinary = FALSE;
  $returnData = $wh = $wxh = $bgSwitch = $outputExt = $magickCommand = '';
  $stopper = 0;
  $sleepTime = $SleepTimer;
  // / Locate & verify ImageMagick. A path is returned only when both succeeded.
  $imageBinary = verifyImageVersion($MinimumImageVersion);
  if ($imageBinary === FALSE) {
    $ConversionErrors = TRUE;
    errorEntry('The installed ImageMagick version is missing, unidentifiable, or too old!', 8001, FALSE); }
  else {
    // / Validate the height, width & rotate arguments.
    if (!is_numeric($height) or $height === FALSE) $height = 0;
    if (!is_numeric($width) or $width === FALSE) $width = 0;
    if (!is_numeric($rotate) or (int)$rotate === 0) $rotate = '';
    else $rotate = '-rotate '.escapeshellarg($rotate).' ';
    // / An omitted dimension is unconstrained & the other scales to fit it.
    // / An exclamation mark demands exact dimensions & accepts the distortion, which is
    // / only correct when the user supplied both & therefore asked for both.
    $wxh = (($width > 0) ? $width : '').'x'.(($height > 0) ? $height : '');
    if ($width > 0 && $height > 0) $wxh = $wxh.'!';
    if ($wxh === 'x') $wh = '';
    else $wh = '-resize '.escapeshellarg($wxh).' ';
    // / Isolate the output extension to determine if it lacks native alpha channel support.
    $outputExt = strtolower(pathinfo($newPathname, PATHINFO_EXTENSION));
    // / Flatten transparent pixels against white when exporting to a format with no alpha.
    // / Without this a transparent PNG becomes a black JPEG rather than a white one.
    if ($outputExt === 'jpg' or $outputExt === 'jpeg') $bgSwitch = '-background white -alpha remove ';
    else $bgSwitch = '-background none ';
    // / Build & sandbox the command once. It does not change between retries.
    // / The input comes FIRST. -alpha remove is an operation & needs an image already
    // / loaded, so a settings block placed before the input fails with no images found.
    $magickCommand = escapeshellarg($imageBinary).' '.escapeshellarg($pathname).' '.$bgSwitch.$wh.$rotate.escapeshellarg($newPathname);
    list ($sandboxIsAvailable, $magickCommand) = sandboxCommand($magickCommand, $pathname, $newPathname, FALSE, 'imagemagick');
    if (!$sandboxIsAvailable) {
      $ConversionErrors = TRUE;
      errorEntry('Bubblewrap is missing or non functional, so this image conversion cannot be isolated!', 8002, FALSE); }
    else {
      if ($Verbose) logEntry('Converting image.');
      // / This code will attempt the conversion up to $StopCounter number of times.
      while (!file_exists($newPathname) && $stopper <= $StopCounter) {
        // / If the last conversion attempt failed, wait a moment before trying again.
        if ($stopper !== 0) sleep($sleepTime++);
        $returnData = shell_exec($magickCommand);
        // / Count the number of conversions to avoid infinite loops.
        $stopper++;
        // / Stop attempting the conversion after $StopCounter number of attempts.
        if ($stopper === $StopCounter) {
          $ConversionErrors = TRUE;
          errorEntry('The image converter timed out!', 8000, FALSE); } }
      // / Log the output of the operation to the logfile, if it is not blank.
      if ($Verbose && trim($returnData) !== '') logEntry('ImageMagick returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
      // / The output file is the only verdict on whether the conversion produced anything.
      // / This check must stay inside the gates, or a stale output file from an earlier
      // / attempt would report success for a conversion that was refused & never ran.
      if (file_exists($newPathname)) $ConversionSuccess = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $returnData, $stopper, $pathname, $height, $width, $wxh, $rotate, $wh, $sleepTime, $outputExt, $bgSwitch, $imageBinary, $magickCommand, $sandboxIsAvailable);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert 3D model formats.
// / Two utilities cover this between them & neither covers it alone.
// / MeshLab performs triangulation & manifold normalization on engineering formats.
// / Assimp handles scene graphs, rigs & the web asset formats MeshLab cannot write.
// / A mesh format is therefore routed through MeshLab first & Assimp second, & a scene
// / format goes straight to Assimp.
// / MeshLab is reachable two ways. The bundled PyMeshLab module needs no display server.
// / The meshlabserver binary does, so it is run under xvfb-run.
// / PyMeshLab bypasses the meshlabserver binary entirely, so its version cannot be read &
// / is not checked. Assimp is checked on every path, because every path uses it.
function convertModels($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer, $MinimumAssimpVersion, $MinimumMeshlabVersion, $UsePyMeshLab, $InstLoc, $DirSep, $EnableMemoryProtection;
  $ConversionSuccess = $ConversionErrors = $modelsValid =  $meshlabBinary = $assimpBinary = $readyToConvert = $meshlabCommand = $assimpCommand = $sandboxIsAvailable = FALSE;
  $returnData = $assimpData = $inputExt = $pyMeshLabDir = $intermediatePathname = $assimpInput = '';
  $meshlabOnly = $assimpSupported = array();
  $stopper = 0;
  $sleepTime = $SleepTimer;
  // / Detect the installed versions of Assimp & MeshLab.
  list ($modelsValid, $assimpBinary, $meshlabBinary) = verifyModelVersions($MinimumAssimpVersion, $MinimumMeshlabVersion);
  // / Assimp is used by every route, so it is required unconditionally.
  if ($assimpBinary === FALSE) {
    $ConversionErrors = TRUE;
    errorEntry('The installed Assimp version is missing, unidentifiable, or too old!', 9001, FALSE); }
  // / MeshLab is only required when the binary is the one being used.
  else if (!$UsePyMeshLab && $meshlabBinary === FALSE) {
    $ConversionErrors = TRUE;
    errorEntry('The installed MeshLab version is missing, unidentifiable, or too old!', 9002, FALSE); }
  else $readyToConvert = TRUE;
  if ($readyToConvert) {
    if ($Verbose) logEntry('Converting model.');
    // / Isolate the input extension to route the model through the proper utility.
    $inputExt = strtolower(pathinfo($pathname, PATHINFO_EXTENSION));
    // / Engineering & CAD formats that need triangulation or manifold normalization first.
    $meshlabOnly = array('stl', 'ply', 'off', '3ds');
    // / Scene graphs, rigs & web assets that Assimp reads directly.
    $assimpSupported = array('fbx', 'gltf', 'glb', 'obj', 'dae', '3mf', 'x3d', 'dxf', 'bvh', 'ase');
    // / The bundled PyMeshLab workspace, used when the binary is not.
    $pyMeshLabDir = $InstLoc.$DirSep.'Resources'.$DirSep.'PyMeshLab';
    // / An intermediate file bridges the two utilities on the two stage route.
    $intermediatePathname = dirname($newPathname).$DirSep.'rectified_'.basename($newPathname).'.obj';
    // / This code will attempt the conversion up to $StopCounter number of times.
    while (!file_exists($newPathname) && $stopper <= $StopCounter) {
      // / If the last conversion attempt failed, wait a moment before trying again.
      if ($stopper !== 0) sleep($sleepTime++);
      // / Route 1. A mesh format is normalized by MeshLab before Assimp writes the output.
      if (in_array($inputExt, $meshlabOnly)) {
        if ($UsePyMeshLab) $meshlabCommand = 'python3 -c "import sys; sys.path.insert(0, '.escapeshellarg($pyMeshLabDir).'); import pymeshlab; ms = pymeshlab.MeshSet(); ms.load_new_mesh('.escapeshellarg($pathname).'); ms.save_current_mesh('.escapeshellarg($intermediatePathname).');"';
        else $meshlabCommand = 'xvfb-run -a /usr/bin/meshlabserver -i '.escapeshellarg($pathname).' -o '.escapeshellarg($intermediatePathname);
        list ($sandboxIsAvailable, $meshlabCommand) = sandboxCommand($meshlabCommand, $pathname, $intermediatePathname, FALSE, 'meshlab');
        if (!$sandboxIsAvailable) warningEntry('Bubblewrap is unavailable. A model conversion ran unsandboxed.');
        $returnData = shell_exec($meshlabCommand);
        // / If the first stage produced nothing, hand Assimp the original rather than nothing.
        $assimpInput = file_exists($intermediatePathname) ? $intermediatePathname : $pathname;
        $assimpCommand = escapeshellarg($assimpBinary).' export '.escapeshellarg($assimpInput).' '.escapeshellarg($newPathname);
        list ($sandboxIsAvailable, $assimpCommand) = sandboxCommand($assimpCommand, $assimpInput, $newPathname, FALSE, 'meshlab');
        $assimpData = shell_exec($assimpCommand); }
      // / Route 2. A scene format goes straight to Assimp & bypasses MeshLab entirely.
      else if (in_array($inputExt, $assimpSupported)) {
        $assimpCommand = escapeshellarg($assimpBinary).' export '.escapeshellarg($pathname).' '.escapeshellarg($newPathname);
        list ($sandboxIsAvailable, $assimpCommand) = sandboxCommand($assimpCommand, $pathname, $newPathname, FALSE, 'meshlab');
        if (!$sandboxIsAvailable) warningEntry('Bubblewrap is unavailable. A model conversion ran unsandboxed.');
        $assimpData = shell_exec($assimpCommand); }
      // / Route 3. An unrecognized extension is attempted with MeshLab alone.
      else {
        if ($UsePyMeshLab) $meshlabCommand = 'python3 -c "import sys; sys.path.insert(0, '.escapeshellarg($pyMeshLabDir).'); import pymeshlab; ms = pymeshlab.MeshSet(); ms.load_new_mesh('.escapeshellarg($pathname).'); ms.save_current_mesh('.escapeshellarg($newPathname).');"';
        else $meshlabCommand = 'xvfb-run -a '.escapeshellarg($meshlabBinary).' -i '.escapeshellarg($pathname).' -o '.escapeshellarg($newPathname);
        list ($sandboxIsAvailable, $meshlabCommand) = sandboxCommand($meshlabCommand, $pathname, $newPathname, FALSE, 'meshlab');
        if (!$sandboxIsAvailable) warningEntry('Bubblewrap is unavailable. A model conversion ran unsandboxed.');
        $returnData = shell_exec($meshlabCommand); }
      // / Count the number of conversions to avoid infinite loops.
      $stopper++;
      // / Stop attempting the conversion after $StopCounter number of attempts.
      if ($stopper === $StopCounter) {
        $ConversionErrors = TRUE;
        errorEntry('The model converter timed out!', 9000, FALSE); } }
    // / Log the output of each utility to the logfile, if it is not blank.
    if ($Verbose && trim($returnData) !== '') logEntry('Meshlab processing engine returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
    if ($Verbose && trim($assimpData) !== '') logEntry('Assimp returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($assimpData)))));
    // / Erase the intermediate file so a two stage conversion leaves nothing behind.
    if (file_exists($intermediatePathname)) @unlink($intermediatePathname);
    // / The output file is the only verdict on whether the conversion produced anything.
    if (file_exists($newPathname)) $ConversionSuccess = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $returnData, $assimpData, $stopper, $pathname, $intermediatePathname, $assimpInput, $inputExt, $meshlabOnly, $assimpSupported, $pyMeshLabDir, $sleepTime, $modelsValid, $readyToConvert, $meshlabCommand, $assimpCommand, $sandboxIsAvailable, $meshlabBinary, $assimpBinary);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to resolve a single OpenSCAD file reference against the users uploaded files.
// / OpenSCAD references frequently carry a directory structure that does not exist here.
// / A reference like <../lib/threads.scad> is matched on its basename alone, threads.scad.
// / Only files the user actually uploaded to this session are eligible for a match.
// / The path returned points at the SANITIZED copy in ScadTemp, never at the users original.
// / OpenSCAD resolves a relative include against the directory of the file it is reading.
// / The sanitized copy lives in ScadTemp, so every reference it holds must point there too.
// / Returns an empty string when nothing matched, & the caller must comment the reference out.
function resolveSCADInclude($scadReference, $sessionFiles) {
  // / Set variables.
  global $ScadTemp, $DirSep, $EnableMemoryProtection;
  $ResolvedFile = $sessionFile = '';
  $referenceIsUsable = FALSE;
  $referenceBase = strtolower(trim(basename(str_replace('\\', '/', trim($scadReference)))));
  // / A reference with no usable filename can never resolve to anything.
  // / Only .scad sources may ever be resolved as an include or a use.
  if ($referenceBase !== '' && $referenceBase !== '.' && $referenceBase !== '..' && getExtension($referenceBase) === 'scad') $referenceIsUsable = TRUE;
  if ($referenceIsUsable) {
    foreach ($sessionFiles as $sessionFile) {
      if (strtolower(basename($sessionFile)) === $referenceBase) {
        $ResolvedFile = $ScadTemp.$DirSep.basename($sessionFile);
        break; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $referenceBase, $sessionFile, $referenceIsUsable);
  return $ResolvedFile; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to find every file reading call in a block of OpenSCAD source.
// / The source is walked once, character by character, carrying comment & string state.
// / One pass is required rather than two, because whichever delimiter appears first wins.
// / A line comment containing the characters that open a block comment does NOT open one.
// / A block comment containing the characters that open a line comment does NOT open one.
// / Stripping one kind before the other gets both of those backwards & swallows real code.
// / Comments & strings are removed because a keyword inside either is not a call.
// / An angle bracket path is removed for the same reason. A directory name is not a call.
// / A keyword is only counted as a call when BOTH of the following hold.
// / The character before it must not be a letter, a digit or an underscore.
// / That is what stops house_width & diffuser_height from counting as a use.
// / The next meaningful character must be an opening bracket or an angle bracket.
// / That is what stops include_lid from counting as an include.
// / A comment is a token separator in OpenSCAD. Not a terminator.
// / The main loop below treats a comment as the end of live code, which is correct there.
// / The lookahead treats a comment as whitespace, which is correct there & is NOT the same rule.
// / An earlier version applied the main loop rule in the lookahead & concluded that
// / surface/**/("file") was not a call. OpenSCAD reads that file. It was reported as a bypass.
// / Whitespace & comments are therefore both skipped while looking for the bracket, & again
// / while reading the reference, because a comment may also sit between the bracket & the quote.
// / The boundary is the operating system sandbox in convertSCAD().
// / This function exists so ordinary files render predictably.
// / Returns one record per call found, carrying the keyword, the line it started on & the
// / raw text of the reference where one could be read.
function sanitizeSCAD($scadContents) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ScadCalls = array();
  $keywords = array();
  $keyword = $currentChar = $nextChar = $priorChar = $lookaheadChar = $peekChar = $referenceText = '';
  $sourceLength = $charIndex = $lineNumber = $keywordLength = $lookaheadIndex = $referenceStart = 0;
  $inLineComment = $inBlockComment = $inString = $isCall = FALSE;
  // / Every keyword that causes OpenSCAD to read a file.
  // / The longest forms are listed first so a longer match is preferred over a shorter one.
  // / import_stl would otherwise match as import & leave stl looking like an identifier.
  $keywords = array('dxf_linear_extrude', 'dxf_rotate_extrude', 'import_stl', 'import_dxf', 'import_off', 'dxf_cross', 'surface', 'include', 'import', 'dxf_dim', 'use');
  $sourceLength = strlen($scadContents);
  $lineNumber = 1;
  for ($charIndex = 0; $charIndex < $sourceLength; $charIndex++) {
    $currentChar = $scadContents[$charIndex];
    $nextChar = ($charIndex + 1 < $sourceLength) ? $scadContents[$charIndex + 1] : '';
    // / Track the line number for every newline, wherever it appears.
    // / The user has to be told which line a reference was on & a stream has no lines.
    if ($currentChar === "\n") $lineNumber++;
    // / A line comment runs to the end of the line & nothing inside it is code.
    if ($inLineComment) {
      if ($currentChar === "\n") $inLineComment = FALSE;
      continue; }
    // / A block comment runs to the first closing sequence & does not nest.
    // / The character after that sequence is live code again, even on the same line.
    // / That single property is what the reported block comment bypass depended on.
    if ($inBlockComment) {
      if ($currentChar === '*' && $nextChar === '/') {
        $inBlockComment = FALSE;
        $charIndex++; }
      continue; }
    // / A string literal may contain anything, including text that reads like a call.
    if ($inString) {
      if ($currentChar === '\\') {
        $charIndex++;
        continue; }
      if ($currentChar === '"') $inString = FALSE;
      continue; }
    // / Not inside anything, so check whether this character opens something.
    if ($currentChar === '/' && $nextChar === '/') {
      $inLineComment = TRUE;
      $charIndex++;
      continue; }
    if ($currentChar === '/' && $nextChar === '*') {
      $inBlockComment = TRUE;
      $charIndex++;
      continue; }
    if ($currentChar === '"') {
      $inString = TRUE;
      continue; }
    // / Live code. Test every keyword at this position.
    foreach ($keywords as $keyword) {
      $keywordLength = strlen($keyword);
      if (strtolower(substr($scadContents, $charIndex, $keywordLength)) !== $keyword) continue;
      // / The character before the keyword must not make this part of a longer identifier.
      $priorChar = ($charIndex > 0) ? $scadContents[$charIndex - 1] : ' ';
      if (ctype_alnum($priorChar) or $priorChar === '_') continue;
      // / Look for the bracket that would make this a call.
      // / Whitespace is skipped. Comments are skipped, because OpenSCAD separates tokens with both.
      // / A newline is whitespace here, so a call split across any number of lines is still found.
      $isCall = FALSE;
      $referenceText = '';
      for ($lookaheadIndex = $charIndex + $keywordLength; $lookaheadIndex < $sourceLength; $lookaheadIndex++) {
        $lookaheadChar = $scadContents[$lookaheadIndex];
        if ($lookaheadChar === ' ' or $lookaheadChar === "\t" or $lookaheadChar === "\n" or $lookaheadChar === "\r") continue;
        // / A comment sitting between the keyword & its bracket separates tokens.
        // / It does not end the statement & must not end this search.
        if ($lookaheadChar === '/' && ($lookaheadIndex + 1) < $sourceLength) {
          $peekChar = $scadContents[$lookaheadIndex + 1];
          if ($peekChar === '/') {
            $lookaheadIndex = $lookaheadIndex + 2;
            while ($lookaheadIndex < $sourceLength && $scadContents[$lookaheadIndex] !== "\n") $lookaheadIndex++;
            continue; }
          if ($peekChar === '*') {
            $lookaheadIndex = $lookaheadIndex + 2;
            while (($lookaheadIndex + 1) < $sourceLength && !($scadContents[$lookaheadIndex] === '*' && $scadContents[$lookaheadIndex + 1] === '/')) $lookaheadIndex++;
            $lookaheadIndex = $lookaheadIndex + 1;
            continue; } }
        if ($lookaheadChar === '(' or $lookaheadChar === '<') $isCall = TRUE;
        break; }
      if (!$isCall) continue;
      // / Read the reference itself so the caller can resolve or report it.
      // / An angle bracket form runs to the closing bracket & cannot contain a comment.
      // / OpenSCAD throws a parser error on include/**/<file>, so that form is a dead end.
      $referenceStart = $lookaheadIndex;
      if ($scadContents[$referenceStart] === '<') {
        $lookaheadIndex++;
        while ($lookaheadIndex < $sourceLength && $scadContents[$lookaheadIndex] !== '>') {
          $referenceText .= $scadContents[$lookaheadIndex];
          $lookaheadIndex++; } }
      // / A bracket form may carry a comment between the bracket & the quote.
      // / surface(/*x*/"file") is a live call & the quote must still be found.
      else {
        $lookaheadIndex++;
        while ($lookaheadIndex < $sourceLength && $scadContents[$lookaheadIndex] !== '"' && $scadContents[$lookaheadIndex] !== ')') {
          if ($scadContents[$lookaheadIndex] === '/' && ($lookaheadIndex + 1) < $sourceLength) {
            $peekChar = $scadContents[$lookaheadIndex + 1];
            if ($peekChar === '/') {
              $lookaheadIndex = $lookaheadIndex + 2;
              while ($lookaheadIndex < $sourceLength && $scadContents[$lookaheadIndex] !== "\n") $lookaheadIndex++;
              continue; }
            if ($peekChar === '*') {
              $lookaheadIndex = $lookaheadIndex + 2;
              while (($lookaheadIndex + 1) < $sourceLength && !($scadContents[$lookaheadIndex] === '*' && $scadContents[$lookaheadIndex + 1] === '/')) $lookaheadIndex++;
              $lookaheadIndex = $lookaheadIndex + 2;
              continue; } }
          $lookaheadIndex++; }
        if ($lookaheadIndex < $sourceLength && $scadContents[$lookaheadIndex] === '"') {
          $lookaheadIndex++;
          while ($lookaheadIndex < $sourceLength && $scadContents[$lookaheadIndex] !== '"') {
            $referenceText .= $scadContents[$lookaheadIndex];
            $lookaheadIndex++; } } }
      // / One record per call found. The line number is where the KEYWORD started.
      array_push($ScadCalls, array(
        'Keyword'     => $keyword,
        'Line'        => $lineNumber,
        'Reference'   => trim($referenceText),
        'IsAngleForm' => ($scadContents[$referenceStart] === '<')));
      // / Advance past the keyword so its own text cannot match a shorter keyword inside it.
      $charIndex = $charIndex + $keywordLength - 1;
      break; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $scadContents, $keywords, $keyword, $currentChar, $nextChar, $priorChar, $lookaheadChar, $peekChar, $referenceText, $sourceLength, $charIndex, $lineNumber, $keywordLength, $lookaheadIndex, $referenceStart, $inLineComment, $inBlockComment, $inString, $isCall);
  return $ScadCalls; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to rewrite OpenSCAD source using what the Sanitizer found.
// / The Sanitizer works on a stream & reports the line each call started on.
// / This function works on lines, because the user has to read the result & understand it.
// /
// / A line carrying a call is commented out whole rather than edited in place.
// / Editing in place would require reproducing OpenSCAD's idea of where a statement ends,
// / which is the class of problem this design exists to avoid.
// / Commenting the whole line is coarse & occasionally removes more than strictly necessary.
// / It is also impossible to get subtly wrong, which is worth more than being precise here.
// / A call split across several lines has every line it touches commented out.
// /
// / An include or use may instead be rewritten to point at another source the same user
// / uploaded, when config.php enables that. Everything else is always removed.
// / Geometry & heightmap reads are never resolved & never rewritten.
function rectifySCAD($scadContents, $sessionFiles, $resolveIncludes) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RectifiedSCAD = '';
  $ReferencesFound = $ReferencesResolved = $ReferencesRemoved = 0;
  $scadCalls = $scadLines = $linesToComment = $callsByLine = array();
  $scadCall = $scadLine = $resolvedPath = $marker = '';
  $lineIndex = $lineNumber = $callLine = $callEndLine = 0;
  // / Find every call in the source before touching a single line.
  $scadCalls = sanitizeSCAD($scadContents);
  $ReferencesFound = count($scadCalls);
  $scadLines = preg_split('/\R/', $scadContents);
  // / Decide what happens to each call & record which line carries the result.
  foreach ($scadCalls as $scadCall) {
    $callLine = (int)$scadCall['Line'];
    $resolvedPath = '';
    // / Only an angle bracket include or use is ever eligible for resolution.
    // / A geometry or heightmap read is always removed, whatever config.php says.
    if ($resolveIncludes && $scadCall['IsAngleForm'] && ($scadCall['Keyword'] === 'include' or $scadCall['Keyword'] === 'use')) $resolvedPath = resolveSCADInclude($scadCall['Reference'], $sessionFiles);
    if ($resolvedPath !== '') {
      $ReferencesResolved++;
      $callsByLine[$callLine] = array('Action' => 'RESOLVE', 'Keyword' => $scadCall['Keyword'], 'Path' => $resolvedPath); }
    else {
      $ReferencesRemoved++;
      $callsByLine[$callLine] = array('Action' => 'REMOVE', 'Keyword' => $scadCall['Keyword'], 'Path' => ''); } }
  // / Rebuild the source one line at a time.
  foreach ($scadLines as $lineIndex => $scadLine) {
    $lineNumber = $lineIndex + 1;
    // / A line with no call on it passes through completely untouched.
    if (!array_key_exists($lineNumber, $callsByLine)) {
      $RectifiedSCAD .= $scadLine.PHP_EOL;
      continue; }
    // / A resolved include is replaced outright with a reference to the staged copy.
    // / The original line is preserved as a comment so the user can see what it was.
    // / The reference is written as a BARE FILENAME rather than as the absolute path that
    // / resolveSCADInclude() returned. Every sanitized copy lives in one directory, & the
    // / render runs with that directory as its working directory, so a bare name resolves.
    // / An absolute path would not, because inside the sandbox that directory is mounted
    // / somewhere else entirely & the real path does not exist at all.
    if ($callsByLine[$lineNumber]['Action'] === 'RESOLVE') {
      $RectifiedSCAD .= '// HRC2-RESOLVED-FROM: '.$scadLine.PHP_EOL;
      $RectifiedSCAD .= $callsByLine[$lineNumber]['Keyword'].' <'.basename($callsByLine[$lineNumber]['Path']).'>'.PHP_EOL;
      continue; }
    // / Everything else is commented out whole & labelled with what it was.
    $marker = '// HRC2-REMOVED-'.strtoupper($callsByLine[$lineNumber]['Keyword']).': ';
    $RectifiedSCAD .= $marker.$scadLine.PHP_EOL; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $scadContents, $sessionFiles, $resolveIncludes, $scadCalls, $scadCall, $scadLines, $scadLine, $linesToComment, $callsByLine, $resolvedPath, $marker, $lineIndex, $lineNumber, $callLine, $callEndLine);
  return array($RectifiedSCAD, $ReferencesFound, $ReferencesResolved, $ReferencesRemoved); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to sanitize EVERY OpenSCAD source the user uploaded, in one flat pass.
// / Nothing here follows a reference from one file to another.
// / Following references would require cycle detection, because a.scad may include b.scad
// / while b.scad includes a.scad, & both are legitimate files the user uploaded.
// / Sanitizing the whole upload set instead means every file OpenSCAD can possibly reach
// / has already been through the filter, & no traversal is ever performed.
// / The set is closed & bounded by whatever the user was willing to upload, so no depth
// / or width budget is required the way the stream inspector needs one.
// / Every sanitized copy is written to ScadTemp & the users originals are never modified.
function sanitizeAllSCADUploads() {
  // / Set variables.
  global $Verbose, $ConvertDir, $ScadTemp, $DirSep, $AllowSCADIncludeResolution, $EnableMemoryProtection;
  $AllSanitized = TRUE;
  $FilesSanitized = $ReferencesFound = $ReferencesResolved = $ReferencesRemoved = 0;
  $fileFound = $fileResolved = $fileRemoved = $bytesWritten = 0;
  $sessionFiles = array();
  $sessionFile = $scadContents = $sanitizedSCAD = $sanitizedPath = '';
  // / Gather every file the user uploaded to this session.
  $sessionFiles = getFiles($ConvertDir);
  foreach ($sessionFiles as $sessionFile) {
    // / Only OpenSCAD sources are sanitized. Everything else is left alone entirely.
    if (getExtension($sessionFile) !== 'scad') continue;
    $scadContents = @file_get_contents($ConvertDir.$sessionFile);
    if ($scadContents === FALSE) {
      $AllSanitized = FALSE;
      errorEntry('Could not read the OpenSCAD source file '.$sessionFile.'!', 27006, FALSE);
      continue; }
    // / Neutralize every file reading primitive in this source.
    list ($sanitizedSCAD, $fileFound, $fileResolved, $fileRemoved) = rectifySCAD($scadContents, $sessionFiles, $AllowSCADIncludeResolution);
    $ReferencesFound = $ReferencesFound + $fileFound;
    $ReferencesResolved = $ReferencesResolved + $fileResolved;
    $ReferencesRemoved = $ReferencesRemoved + $fileRemoved;
    // / Write the sanitized copy under the same basename so a resolved reference finds it.
    $sanitizedPath = $ScadTemp.$DirSep.basename($sessionFile);
    $bytesWritten = file_put_contents($sanitizedPath, $sanitizedSCAD, LOCK_EX);
    if ($bytesWritten === strlen($sanitizedSCAD)) $FilesSanitized++;
    else {
      $AllSanitized = FALSE;
      errorEntry('Could not stage the sanitized OpenSCAD source '.$sessionFile.'!', 27001, FALSE); } }
  // / A reference removed from an uploaded source is worth an operator seeing at any verbosity.
  if ($ReferencesRemoved > 0) warningEntry('OpenSCAD Sanitization removed '.$ReferencesRemoved.' file reference(s) across '.$FilesSanitized.' uploaded source file(s) in this session. Resolved: '.$ReferencesResolved.'.');
  else if ($Verbose) logEntry('OpenSCAD Sanitization Result: Files Sanitized: '.$FilesSanitized.', References Found: '.$ReferencesFound.', Resolved: '.$ReferencesResolved.', Removed: '.$ReferencesRemoved.', Resolution Enabled: '.($AllowSCADIncludeResolution ? 'TRUE' : 'FALSE').'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $sessionFiles, $sessionFile, $scadContents, $sanitizedSCAD, $sanitizedPath, $fileFound, $fileResolved, $fileRemoved, $bytesWritten);
  return array($AllSanitized, $FilesSanitized, $ReferencesFound, $ReferencesResolved, $ReferencesRemoved); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert OpenSCAD source files into a supported export format.
// / The users uploaded .scad is never modified & never replaced.
// / Every uploaded source is sanitized into ScadTemp before OpenSCAD is allowed to run.
// / The whole upload set is sanitized rather than just the requested file, because a
// / resolved include would otherwise hand OpenSCAD a source that was never filtered.
// / Sanitized copies are never retained. If they are needed again they are regenerated.
// /
// / SANITIZATION IS NOT THE SECURITY BOUNDARY & MUST NEVER BE TREATED AS ONE.
// / A filter over a closed character set can be a boundary, because that question has a
// / complete answer. A filter that must interpret a grammar cannot be, because it can only
// / approximate another program's parser & every disagreement is a bypass.
// / The SCAD scanner is the second kind. Four bypasses were reported against the line
// / oriented version, & a fifth against the stateful rewrite that replaced it.
// / The boundary is the operating system sandbox below & nothing else.
// /
// / This function does NOT use sandboxCommand(). It needs a WHOLE DIRECTORY visible so that
// / include statements resolve, where every other converter needs exactly one input file.
// / Both binaries are located rather than assumed, so the OpenSCAD whose version was
// / verified & the bubblewrap that was proven functional are the ones that actually run.
// /
// / OpenSCAD does NOT fail on a missing include. It warns & renders what it can, so a model
// / can come out geometrically incomplete while reporting success. The COUNT of such
// / warnings is logged & the text is never written, because a parse warning quotes the
// / offending source line & that would turn the log into an exfiltration channel.
function convertSCAD($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $DirSep, $SCADConversionTimeout, $ScadTemp, $MinimumSCADVersion, $EnableMemoryProtection;
  $ConversionSuccess = $ConversionErrors = FALSE;
  $allSanitized = $readyToRender = FALSE;
  $scadBinary = $bwrapBinary = FALSE;
  $filesSanitized = $referencesFound = $referencesResolved = $referencesRemoved = 0;
  $openscadExitCode = $missingIncludes = 0;
  $sanitizedPath = $openscadCommand = $sandboxOutputName = $sandboxOutputPath = '';
  $openscadOutput = array();
  // / Confirm this server can isolate a render before anything else happens.
  // / A server that cannot isolate a render must refuse to render at all.
  $bwrapBinary = verifyBwrap();
  if ($bwrapBinary === FALSE) {
    $ConversionErrors = TRUE;
    errorEntry('Bubblewrap is missing or non functional, so OpenSCAD renders cannot be isolated!', 27007, FALSE); }
  else {
    // / Locate & verify OpenSCAD. A path is returned only when both succeeded.
    $scadBinary = verifySCADVersion($MinimumSCADVersion);
    if ($scadBinary === FALSE) {
      $ConversionErrors = TRUE;
      errorEntry('The installed OpenSCAD version is missing or too old!', 27005, FALSE); }
    else {
      // / Sanitize every uploaded source, not just this one.
      // / A resolved include points at a sanitized copy, so every copy must already exist.
      list ($allSanitized, $filesSanitized, $referencesFound, $referencesResolved, $referencesRemoved) = sanitizeAllSCADUploads();
      // / The sanitized copy of the requested file carries the same basename as the original.
      $sanitizedPath = $ScadTemp.$DirSep.basename($pathname);
      if ($allSanitized && file_exists($sanitizedPath)) $readyToRender = TRUE;
      else {
        $ConversionErrors = TRUE;
        errorEntry('Could not prepare the OpenSCAD sources for rendering!', 27000, FALSE); } } }
  // / Render only from the sanitized copy, & only from inside the sandbox.
  // / The users original is never handed to OpenSCAD.
  if ($readyToRender) {
    if ($Verbose) logEntry('Converting OpenSCAD model to '.$extension.'.');
    // / The sandbox cannot see the real output location, so the render writes inside it.
    // / The finished model is moved out afterwards, from outside the namespace.
    $sandboxOutputName = basename($newPathname);
    $sandboxOutputPath = $ScadTemp.$DirSep.$sandboxOutputName;
    // / --unshare-all removes every namespace this render has no business holding.
    // / That includes the network, which closes any OpenSCAD build whose import() takes a URL.
    // / --die-with-parent guarantees the render cannot outlive the PHP process that started it.
    // / --new-session prevents the render from injecting into the controlling terminal.
    // / /work is the ONLY writable path & the ONLY path from the data location that exists.
    // / --chdir /work is what makes a resolved include work. rectifySCAD() rewrites an
    // / include to a bare filename, & every sanitized copy lives in this one directory.
    // / nice yields the render to everything else on the server.
    // / timeout enforces a wall clock limit because OpenSCAD will not stop on its own.
    $openscadCommand = 'nice -n 19 timeout '.(int)$SCADConversionTimeout
      .' '.escapeshellarg($bwrapBinary)
      .' --unshare-all'
      .' --die-with-parent'
      .' --new-session'
      .' --ro-bind /usr /usr'
      .' --ro-bind-try /lib /lib'
      .' --ro-bind-try /lib64 /lib64'
      .' --ro-bind-try /bin /bin'
      .' --ro-bind-try /etc/fonts /etc/fonts'
      .' --ro-bind-try /etc/ld.so.cache /etc/ld.so.cache'
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
      // / Every writable location a dependency reaches for is pointed at the tmpfs, so
      // / nothing tries to create state outside the namespace & fail.
      .' --setenv XDG_RUNTIME_DIR /tmp'
      .' --setenv XDG_CONFIG_HOME /tmp/.config'
      .' --setenv XDG_CACHE_HOME /tmp/.cache'
      .' --setenv XDG_DATA_HOME /tmp/.local'
      .' --bind '.escapeshellarg($ScadTemp).' /work'
      .' --chdir /work'
      .' '.escapeshellarg($scadBinary).' -o '.escapeshellarg('/work/'.$sandboxOutputName)
      .' '.escapeshellarg('/work/'.basename($sanitizedPath))
      .' 2>&1';
    exec($openscadCommand, $openscadOutput, $openscadExitCode);
    // / An exit code of 124 is the timeout command reporting that it killed the render.
    if ($openscadExitCode === 124) {
      $ConversionErrors = TRUE;
      errorEntry('The OpenSCAD converter timed out after '.(int)$SCADConversionTimeout.' seconds!', 27002, FALSE); }
    else if ($openscadExitCode !== 0) {
      $ConversionErrors = TRUE;
      errorEntry('The OpenSCAD converter failed with exit code '.$openscadExitCode.'!', 27003, FALSE); }
    // / Count the includes OpenSCAD could not open. The COUNT is reported & the text is not.
    // / A render that silently drops geometry reports success without this.
    if (is_array($openscadOutput)) $missingIncludes = count(preg_grep('/Can\'t open include file/i', $openscadOutput));
    if ($missingIncludes > 0) warningEntry('The OpenSCAD render could not open '.$missingIncludes.' include file(s). The model may be incomplete.');
    // / Move the rendered model out of the sandbox directory to where the core expects it.
    // / This happens BEFORE the cleanup, or the cleanup would delete the model.
    if (file_exists($sandboxOutputPath)) {
      if (!@rename($sandboxOutputPath, $newPathname)) {
        $ConversionErrors = TRUE;
        errorEntry('Could not move the rendered OpenSCAD model out of the sandbox!', 27008, FALSE); } }
    // / Remove every sanitized copy immediately. None of them are retained for any reason.
    // / cleanFiles() removes the emptied directory itself, so its absence is the success case.
    // / verifyRequiredDirs() recreates it at the start of the next request.
    cleanFiles($ScadTemp);
    if (is_dir($ScadTemp) && !is_dir_empty($ScadTemp)) errorEntry('Could not remove the sanitized OpenSCAD sources!', 27004, FALSE); }
  // / The output file is the only verdict on whether the render actually produced anything.
  if (file_exists($newPathname)) $ConversionSuccess = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $openscadOutput may hold quoted source lines & is cleared like everything else.
  purgeSensitiveMemory($EnableMemoryProtection, $sanitizedPath, $openscadCommand, $openscadOutput, $openscadExitCode, $missingIncludes, $readyToRender, $scadBinary, $bwrapBinary, $allSanitized, $filesSanitized, $referencesFound, $referencesResolved, $referencesRemoved, $sandboxOutputName, $sandboxOutputPath, $pathname);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert 2D vector drawing formats.
// / Dia handles diagram formats such as dxf, fig, vdx & wpg. SVG is handled by Inkscape in
// / convertSVG() instead, because Dia exports SVG without truly rendering it.
// / The binary is supplied by verifyDrawingVersion() rather than assumed, so the version
// / that was verified is provably the version that runs.
// / This function had NO version gate at all before this change. Dia was invoked blind, &
// / a missing binary produced a timeout rather than naming the cause.
// / Dia is a GTK application. If a conversion fails INSIDE a working sandbox rather than
// / being refused by the sandbox gate, a missing display is the first thing to suspect,
// / because the sandbox provides a private /tmp & an X socket outside it is not visible.
function convertDrawings($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer, $MinimumDiaVersion, $EnableMemoryProtection;
  $ConversionSuccess = $ConversionErrors = $sandboxIsAvailable = FALSE;
  $drawingBinary = FALSE;
  $returnData = $diaCommand = '';
  $stopper = 0;
  $sleepTime = $SleepTimer;
  // / Locate & verify Dia. A path is returned only when both succeeded.
  $drawingBinary = verifyDrawingVersion($MinimumDiaVersion);
  if ($drawingBinary === FALSE) {
    $ConversionErrors = TRUE;
    errorEntry('The installed Dia version is missing, unidentifiable, or too old!', 10002, FALSE); }
  else {
    // / Build & sandbox the command once. It does not change between retries.
    $diaCommand = escapeshellarg($drawingBinary).' '.escapeshellarg($pathname).' -e '.escapeshellarg($newPathname);
    list ($sandboxIsAvailable, $diaCommand) = sandboxCommand($diaCommand, $pathname, $newPathname, FALSE, 'dia');
    if (!$sandboxIsAvailable) {
      $ConversionErrors = TRUE;
      errorEntry('Bubblewrap is missing or non functional, so this drawing conversion cannot be isolated!', 10001, FALSE); }
    else {
      if ($Verbose) logEntry('Converting drawing.');
      // / This code will attempt the conversion up to $StopCounter number of times.
      while (!file_exists($newPathname) && $stopper <= $StopCounter) {
        // / If the last conversion attempt failed, wait a moment before trying again.
        if ($stopper !== 0) sleep($sleepTime++);
        $returnData = shell_exec($diaCommand);
        // / Count the number of conversions to avoid infinite loops.
        $stopper++;
        // / Stop attempting the conversion after $StopCounter number of attempts.
        if ($stopper === $StopCounter) {
          $ConversionErrors = TRUE;
          errorEntry('The drawing converter timed out!', 10000, FALSE); } }
      // / Log the output of the operation to the logfile, if it is not blank.
      if ($Verbose && trim($returnData) !== '') logEntry('Dia returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
      // / The output file is the only verdict on whether the conversion produced anything.
      // / This check must stay inside the gates, or a stale output file from an earlier
      // / attempt would report success for a conversion that was refused & never ran.
      if (file_exists($newPathname)) $ConversionSuccess = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $returnData, $stopper, $pathname, $sleepTime, $diaCommand, $drawingBinary, $sandboxIsAvailable);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert SVG vector drawing formats.
// / The binary is supplied by verifySVGVersion() rather than assumed.
// / A width & a height may both be supplied. Inkscape honours both independently, which
// / stretches the image, so the caller decides whether that is what the user asked for.
function convertSVG($pathname, $newPathname, $extension, $height, $width) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer, $MinimumInkscapeVersion, $EnableMemoryProtection;
  $ConversionSuccess = $ConversionErrors = $sandboxIsAvailable = FALSE;
  $svgBinary = FALSE;
  $returnData = $argEcho = $inkscapeCommand = '';
  $stopper = 0;
  $sleepTime = $SleepTimer;
  // / Build the export sizing arguments. A dimension of zero is not passed at all.
  if (!empty($width) && $width > 0) $argEcho = '--export-width='.escapeshellarg($width);
  if (!empty($height) && $height > 0) $argEcho = trim($argEcho.' --export-height='.escapeshellarg($height));
  if ($argEcho !== '') $argEcho = $argEcho.' ';
  // / Locate & verify Inkscape. A path is returned only when both succeeded.
  $svgBinary = verifySVGVersion($MinimumInkscapeVersion);
  if ($svgBinary === FALSE) {
    $ConversionErrors = TRUE;
    errorEntry('The installed Inkscape version is missing, unidentifiable, or too old!', 25001, FALSE); }
  else {
    // / Build & sandbox the command once. It does not change between retries.
    $inkscapeCommand = escapeshellarg($svgBinary).' '.$argEcho.'--export-filename='.escapeshellarg($newPathname).' '.escapeshellarg($pathname);
    list ($sandboxIsAvailable, $inkscapeCommand) = sandboxCommand($inkscapeCommand, $pathname, $newPathname, FALSE, 'inkscape');
    if (!$sandboxIsAvailable) {
      $ConversionErrors = TRUE;
      errorEntry('Bubblewrap is missing or non functional, so this SVG conversion cannot be isolated!', 25002, FALSE); }
    else {
      if ($Verbose) logEntry('Converting SVG.');
      // / This code will attempt the conversion up to $StopCounter number of times.
      while (!file_exists($newPathname) && $stopper <= $StopCounter) {
        // / If the last conversion attempt failed, wait a moment before trying again.
        if ($stopper !== 0) sleep($sleepTime++);
        $returnData = shell_exec($inkscapeCommand);
        // / Count the number of conversions to avoid infinite loops.
        $stopper++;
        // / Stop attempting the conversion after $StopCounter number of attempts.
        if ($stopper === $StopCounter) {
          $ConversionErrors = TRUE;
          errorEntry('The SVG converter timed out!', 25000, FALSE); } }
      // / Log the output of the operation to the logfile, if it is not blank.
      if ($Verbose && trim($returnData) !== '') logEntry('Inkscape returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
      // / The output file is the only verdict on whether the conversion produced anything.
      if (file_exists($newPathname)) $ConversionSuccess = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $returnData, $stopper, $pathname, $height, $width, $sleepTime, $argEcho, $svgBinary, $inkscapeCommand, $sandboxIsAvailable);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert e-book formats.
// / Calibre is one utility covering every e-book format in both directions, so this is a
// / single stage pipeline with no intermediate file & no second dependency.
// / ebook-convert INFERS THE OUTPUT FORMAT FROM THE OUTPUT FILE EXTENSION rather than from
// / a flag. The extension therefore has to survive the trip into the sandbox, & it does,
// / because sandboxCommand() rewrites the directory & keeps the basename intact.
// / Calibre insists on a writable HOME & builds a configuration directory on first run.
// / The sandbox sets HOME to a tmpfs, so that directory is built fresh inside the namespace
// / on every conversion & is discarded with it. That costs a little time per conversion &
// / buys a guarantee that nothing a hostile book does to Calibre's configuration survives.
// / Calibre also reaches the network for metadata & update checks when it is allowed to.
// / The sandbox unshares the network, which closes that off without needing a flag.
// / --no-default-epub-cover is deliberately NOT passed. A generated cover is expected
// / behaviour & suppressing it surprises users who converted a book that had no cover.
function convertEbooks($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer, $MinimumCalibreVersion, $EnableMemoryProtection;
  $ConversionSuccess = $ConversionErrors = $sandboxIsAvailable = FALSE;
  $ebookBinary = FALSE;
  $returnData = $ebookCommand = '';
  $stopper = 0;
  $sleepTime = $SleepTimer;
  // / Locate & verify Calibre. A path is returned only when both succeeded.
  $ebookBinary = verifyEbookVersion($MinimumCalibreVersion);
  if ($ebookBinary === FALSE) {
    $ConversionErrors = TRUE;
    errorEntry('The installed Calibre version is missing, unidentifiable, or too old!', 30001, FALSE); }
  else {
    // / Build & sandbox the command once. It does not change between retries.
    // / The output extension IS the format selector, so no format flag is passed.
    $ebookCommand = escapeshellarg($ebookBinary).' '.escapeshellarg($pathname).' '.escapeshellarg($newPathname);
    list ($sandboxIsAvailable, $ebookCommand) = sandboxCommand($ebookCommand, $pathname, $newPathname, FALSE, 'calibre');
    if (!$sandboxIsAvailable) {
      $ConversionErrors = TRUE;
      errorEntry('Bubblewrap is missing or non functional, so this e-book conversion cannot be isolated!', 30002, FALSE); }
    else {
      if ($Verbose) logEntry('Converting e-book to '.$extension.'.');
      // / This code will attempt the conversion up to $StopCounter number of times.
      while (!file_exists($newPathname) && $stopper <= $StopCounter) {
        // / If the last conversion attempt failed, wait a moment before trying again.
        if ($stopper !== 0) sleep($sleepTime++);
        $returnData = shell_exec($ebookCommand);
        // / Count the number of conversions to avoid infinite loops.
        $stopper++;
        // / Stop attempting the conversion after $StopCounter number of attempts.
        if ($stopper === $StopCounter) {
          $ConversionErrors = TRUE;
          errorEntry('The e-book converter timed out!', 30000, FALSE); } }
      // / Log the output of the operation to the logfile, if it is not blank.
      // / Calibre is verbose by default & reports its whole conversion pipeline, which is
      // / genuinely useful when a book converts but comes out wrong.
      if ($Verbose && trim($returnData) !== '') logEntry('Calibre returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
      // / The output file is the only verdict on whether the conversion produced anything.
      // / This check must stay inside the gates, or a stale output file from an earlier
      // / attempt would report success for a conversion that was refused & never ran.
      if (file_exists($newPathname)) $ConversionSuccess = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $returnData, $stopper, $pathname, $sleepTime, $ebookBinary, $ebookCommand, $sandboxIsAvailable);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert video formats.
// / The general minimum is enforced rather than the stream minimum, because a video
// / conversion reads a local file & never fetches anything remote.
function convertVideos($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer, $MinimumFFMPEGVersion, $EnableMemoryProtection;
  $ConversionSuccess = $ConversionErrors = $sandboxIsAvailable = FALSE;
  $ffmpegBinary = FALSE;
  $returnData = $ffmpegCommand = '';
  $stopper = 0;
  $sleepTime = $SleepTimer;
  // / Locate & verify FFMPEG. A path is returned only when both succeeded.
  $ffmpegBinary = verifyFFMPEGVersion($MinimumFFMPEGVersion);
  if ($ffmpegBinary === FALSE) {
    $ConversionErrors = TRUE;
    errorEntry('The installed FFMPEG version is missing, unidentifiable, or too old!', 11001, FALSE); }
  else {
    // / Build & sandbox the command once. It does not change between retries.
    $ffmpegCommand = escapeshellarg($ffmpegBinary).' -y -i '.escapeshellarg($pathname).' -c:v libx264 '.escapeshellarg($newPathname);
    list ($sandboxIsAvailable, $ffmpegCommand) = sandboxCommand($ffmpegCommand, $pathname, $newPathname, FALSE, 'ffmpeg');
    if (!$sandboxIsAvailable) {
      $ConversionErrors = TRUE;
      errorEntry('Bubblewrap is missing or non functional, so this video conversion cannot be isolated!', 11002, FALSE); }
    else {
      if ($Verbose) logEntry('Converting video.');
      // / This code will attempt the conversion up to $StopCounter number of times.
      while (!file_exists($newPathname) && $stopper <= $StopCounter) {
        // / If the last conversion attempt failed, wait a moment before trying again.
        if ($stopper !== 0) sleep($sleepTime++);
        $returnData = shell_exec($ffmpegCommand);
        // / Count the number of conversions to avoid infinite loops.
        $stopper++;
        // / Stop attempting the conversion after $StopCounter number of attempts.
        if ($stopper === $StopCounter) {
          $ConversionErrors = TRUE;
          errorEntry('The video converter timed out!', 11000, FALSE); } }
      // / Log the output of the operation to the logfile, if it is not blank.
      if ($Verbose && trim($returnData) !== '') logEntry('Ffmpeg returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
      // / The output file is the only verdict on whether the conversion produced anything.
      if (file_exists($newPathname)) $ConversionSuccess = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $returnData, $stopper, $pathname, $sleepTime, $ffmpegBinary, $ffmpegCommand, $sandboxIsAvailable);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert subtitle formats.
// / The general minimum is enforced rather than the stream minimum, because a subtitle
// / conversion reads a local file & never fetches anything remote.
// / This function had NO version gate at all before this change. FFMPEG was invoked blind.
function convertSubtitles($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer, $MinimumFFMPEGVersion, $EnableMemoryProtection;
  $ConversionSuccess = $ConversionErrors = $sandboxIsAvailable = FALSE;
  $ffmpegBinary = FALSE;
  $returnData = $ffmpegCommand = '';
  $stopper = 0;
  $sleepTime = $SleepTimer;
  // / Locate & verify FFMPEG. A path is returned only when both succeeded.
  $ffmpegBinary = verifyFFMPEGVersion($MinimumFFMPEGVersion);
  if ($ffmpegBinary === FALSE) {
    $ConversionErrors = TRUE;
    errorEntry('The installed FFMPEG version is missing, unidentifiable, or too old!', 22002, FALSE); }
  else {
    // / Build & sandbox the command once. It does not change between retries.
    $ffmpegCommand = escapeshellarg($ffmpegBinary).' -y -i '.escapeshellarg($pathname).' '.escapeshellarg($newPathname);
    list ($sandboxIsAvailable, $ffmpegCommand) = sandboxCommand($ffmpegCommand, $pathname, $newPathname, FALSE, 'ffmpeg');
    if (!$sandboxIsAvailable) {
      $ConversionErrors = TRUE;
      errorEntry('Bubblewrap is missing or non functional, so this subtitle conversion cannot be isolated!', 22001, FALSE); }
    else {
      if ($Verbose) logEntry('Converting subtitle.');
      // / This code will attempt the conversion up to $StopCounter number of times.
      while (!file_exists($newPathname) && $stopper <= $StopCounter) {
        // / If the last conversion attempt failed, wait a moment before trying again.
        if ($stopper !== 0) sleep($sleepTime++);
        $returnData = shell_exec($ffmpegCommand);
        // / Count the number of conversions to avoid infinite loops.
        $stopper++;
        // / Stop attempting the conversion after $StopCounter number of attempts.
        if ($stopper === $StopCounter) {
          $ConversionErrors = TRUE;
          errorEntry('The subtitle converter timed out!', 22000, FALSE); } }
      // / Log the output of the operation to the logfile, if it is not blank.
      if ($Verbose && trim($returnData) !== '') logEntry('Ffmpeg returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
      // / The output file is the only verdict on whether the conversion produced anything.
      if (file_exists($newPathname)) $ConversionSuccess = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $returnData, $stopper, $pathname, $sleepTime, $ffmpegBinary, $ffmpegCommand, $sandboxIsAvailable);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension); }
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
function gatherRemoteStreamHostInfo($StreamURL) {
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
function resolveStreamURI($StreamURI, $ParentURL) {
  // / Set variables.
  global $EnableMemoryProtection;
  $AbsoluteURL = '';
  $parentParts = array();
  $parentScheme = $parentHost = $parentPort = $parentDir = '';
  $uriIsAbsolute = $parentIsUsable = FALSE;
  $StreamURI = trim($StreamURI);
  // / Case 1. The URI already carries its own scheme, so nothing is inherited & we are done.
  // / Note this deliberately matches ANY scheme, including file: and gopher:, so that
  // / gatherRemoteStreamHostInfo() sees & rejects them rather than us silently mangling them.
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
// / A function to gather and validate IPv4 & IPv6 addresses from stream files.
// / Does not perform DNS or any remote validation.
// / This function only validates the syntactical form of IP addresses, and ensures they are not in a reserved range.
function inspectStreamIP($streamFileContents) {
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
// / A function to gather and validate domain names from stream files.
// / Does not perform DNS or any remote validation.
// / This function only validates the syntactical form of domain names.
// / Preserves http:// and https:// as the only allowed protocols.
function inspectStreamDomain($streamFileContents) {
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
// / A function to confirm a downloaded file is genuinely MPEG-TS & not something disguised as one.
// / MPEG-TS is a fixed-size packet format: every packet is 188 bytes & begins with sync byte 0x47.
// / Requiring the sync byte at EVERY expected boundary makes a coincidental match effectively impossible.
// / Does NOT check the file extension. FFMPEG dispatches on content, so we must too.
// / Note that $MaxStreamInspectionFileSize must stay above ($packetSize * $packetsToCheck).
// / Otherwise every genuine segment will fail this check for the wrong reason.
function inspectTSFile($fileContents) {
  // / Set variables.
  global $EnableMemoryProtection;
  $packetSize = 188;
  $packetsToCheck = 5;
  $syncByte = "\x47";
  $offset = 0;
  $bytesRequired = 0;
  $Check = FALSE;
  $bytesRequired = $packetSize * $packetsToCheck;
  // / A file too short to hold the packets we intend to check cannot be validated, so reject it.
  // / This gate also prevents the loop below from reading past the end of the string.
  if (strlen($fileContents) >= $bytesRequired) {
    // / Assume success, then let any single missing sync byte disprove it & stop immediately.
    $Check = TRUE;
    // / Walk the expected packet boundaries & confirm the sync byte appears at every one.
    for ($offset = 0; $offset < $bytesRequired; $offset += $packetSize) {
      if ($fileContents[$offset] !== $syncByte) {
        $Check = FALSE;
        break; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $packetSize, $packetsToCheck, $syncByte, $offset, $bytesRequired, $fileContents);
  return $Check; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to determine what a stream file actually IS, based only on its content.
// / The filename & extension are deliberately ignored. FFMPEG dispatches on content markers,
// / so a .ts file whose bytes begin with #EXTM3U will be treated by FFMPEG as a playlist.
// / This is the single source of truth for stream file classification. Do not duplicate this logic.
function classifyStreamContent($streamContents) {
  // / Set variables.
  global $EnableMemoryProtection;
  $IsPlaylist = $IsSegment = FALSE;
  // / A playlist must open with the #EXTM3U tag. ltrim handles a BOM or leading whitespace.
  if (strncmp(ltrim($streamContents), '#EXTM3U', 7) === 0) $IsPlaylist = TRUE;
  // / Only check for MPEG-TS if it is not already a playlist. Nothing can legitimately be both.
  else $IsSegment = inspectTSFile($streamContents);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $streamContents);
  return array($IsPlaylist, $IsSegment); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to download a single remote stream file to local disk for inspection.
// / Files are saved with a numeric name & no extension so nothing downstream is fooled by a filename.
// / The original URI is preserved in the stream record, not on disk.
// / This function does NOT follow redirects.
// / This function does NOT let CURL perform its own DNS lookup.
// / Only the first $MaxStreamInspectionFileSize bytes are fetched.
// / We are classifying files here, not streaming them.
// / $StreamConnectionTimeout is documented in seconds & is used directly.
// / $StreamWatchTimeout is documented in minutes & is converted once here.
function downloadStreamFile($StreamURL, $URLHost, $URLPort, $URLIP, $URLScheme, $FileNumber) {
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
// / A function to scan a single local stream file & determine if it is safe for FFMPEG to handle.
// / $CurrentLayer 0 is the file the user uploaded. Layers 1+ are files HRConvert2 downloaded for inspection.
// / Downloaded files are saved with a numeric name & NO extension on purpose, so extension-based
// / checks apply to layer 0 only. From layer 1 onward, content is the only authority.
// / This function inspects. It does not connect to anything & it does not decide the fate of the walk.
function inspectStreamFile($StreamFile, $ParentURL, $CurrentLayer) {
  // / Set variables.
  global $Verbose, $AllowStreamOverHTTP, $SupportedConversionTypes, $StreamArray, $EnableMemoryProtection;
  $StreamContainsIP = $StreamContainsLAN = $StreamContainsHTTP = $StreamContainsDomain = FALSE;
  $streamFileExtension = $RawURI = $streamFileContents = '';
  $StreamURIs = $DomainMatches = $IPMatches = $streamLineMatches = array();
  $DomainCount = $IPCount = 0;
  $looksLikePlaylist = $looksLikeSegment = $ContentMismatch = $ContentUnknown = $InspectionFailed = FALSE;
  $extensionAllowed = TRUE;
  // / Get the file extension of the $StreamFile.
  $streamFileExtension = getExtension($StreamFile);
  // / Log the start of stream file inspection.
  if ($Verbose) logEntry('Inspecting Stream File '.$StreamFile.' at layer '.$CurrentLayer.' for security risks.');
  // / For sanity, double check that Stream & Audio operations are even allowed in config.php.
  // / Extra precaution is required due to the cost, sensitivity, & potential consequences of this function being abused.
  // / The supported-format check applies to layer 0 only. Files we downloaded have no extension by design.
  if ($CurrentLayer === 0 && !in_array($streamFileExtension, $StreamArray)) $extensionAllowed = FALSE;
  if (in_array('Stream', $SupportedConversionTypes) && in_array('Audio', $SupportedConversionTypes) && $extensionAllowed) {
    // / Read the contents of the stream file.
    $streamFileContents = file_get_contents($StreamFile);
    // / Classify by content. This is the ONLY place that decision is made. See classifyStreamContent().
    list ($looksLikePlaylist, $looksLikeSegment) = classifyStreamContent($streamFileContents);
    // / Determine if the content of the file matches its file extension.
    // / Layer 0 only. A name that disagrees with the content is not a quirk to work around, it is the attack.
    if ($CurrentLayer === 0) {
      if ($looksLikePlaylist && $streamFileExtension !== 'm3u8') $ContentMismatch = TRUE;
      if ($looksLikeSegment && $streamFileExtension !== 'ts') $ContentMismatch = TRUE; }
    // / Neither format. FFMPEG would probe this & pick some demuxer we never anticipated.
    if (!$looksLikePlaylist && !$looksLikeSegment) $ContentUnknown = TRUE;
    // / Any single failure condition ends this inspection immediately.
    if ($ContentMismatch or $ContentUnknown) $InspectionFailed = TRUE;
    // / If the file passed classification, then continue.
    if (!$InspectionFailed) {
      // / Set a flag if the file references any plain-http address. Controlled by config.php.
      if (stripos($streamFileContents, 'http://') !== FALSE) $StreamContainsHTTP = TRUE;
      // / Check the stream file contents for domain names and assemble them into an array.
      list ($DomainMatches, $DomainCount, $StreamContainsDomain) = inspectStreamDomain($streamFileContents);
      // / Check the stream file contents for raw IP addresses and assemble them into an array.
      list ($IPMatches, $IPCount, $StreamContainsLAN, $StreamContainsIP) = inspectStreamIP($streamFileContents);
      // / Iterate through the $streamFileContents & extract the complete URI from each URI line.
      // / These URIs are raw & completely unvalidated. The walker validates them one at a time.
      foreach (preg_split('/\R/', $streamFileContents) as $streamLine) {
        $streamLine = trim($streamLine);
        // / Skip empty lines in the playlist file.
        if ($streamLine === '') continue;
        // / Non-# lines are URIs. # lines may still carry a URI="" attribute.
        // / #EXT-X-KEY, #EXT-X-MAP & #EXT-X-MEDIA all reference fetchable URIs this way.
        if ($streamLine[0] !== '#') $RawURI = $streamLine;
        elseif (preg_match('/URI="([^"]*)"/i', $streamLine, $streamLineMatches)) $RawURI = $streamLineMatches[1];
        // / A # line with no URI attribute is just a tag. Nothing to inspect.
        else continue;
        // / One record per URI found. Only RawURI, ParentURL & Layer are knowable here.
        // / Everything else is filled in later by the walker as each stage completes.
        $StreamURIs[] = array(
          'RawURI'      => $RawURI,
          'AbsoluteURL' => '',
          'ParentURL'   => $ParentURL,
          'Layer'       => $CurrentLayer,
          'URLHost'     => '',
          'URLPort'     => '',
          'URLScheme'   => '',
          'URLIP'       => '',
          'LocalPath'   => '',
          'IsPlaylist'  => FALSE,
          'IsSegment'   => FALSE,
          'Inspected'   => FALSE,
          'Failed'      => FALSE,
          'FailReason'  => ''); } } }
  // / If the operation is not permitted by config.php, or the extension is not supported, then fail.
  else $InspectionFailed = TRUE;
  // / Any single failure condition fails this file. Flags are one-way & nothing can clear them.
  if ($StreamContainsLAN or $ContentMismatch or $ContentUnknown) $InspectionFailed = TRUE;
  // / A plain-http reference is only fatal when config.php has disabled it.
  if ($StreamContainsHTTP && !$AllowStreamOverHTTP) $InspectionFailed = TRUE;
  // / A LAN reference in an uploaded stream file is always worth an operator seeing.
  if ($StreamContainsLAN) warningEntry('Stream File '.$StreamFile.' references a private, reserved or loopback address.');
  // / Content that disagrees with its own extension is the shape of a disguised file.
  if ($ContentMismatch) warningEntry('Stream File '.$StreamFile.' content does not match its file extension.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $streamFileContents, $DomainMatches, $IPMatches, $streamLineMatches, $RawURI, $extensionAllowed);
  return array($InspectionFailed, $StreamURIs, $StreamContainsLAN, $StreamContainsIP, $StreamContainsHTTP, $looksLikePlaylist, $looksLikeSegment); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The walker. Owns the queue, the seen-set, the depth counter & every budget.
// / Every other stream function answers a question. This one is the only thing that decides to continue.
// / $FileBudget resets each layer because it bounds the width of one layer.
// / $TotalBudget never resets because it bounds the entire tree regardless of shape.
// / $Halt is one-way. Once anything sets it, nothing may clear it.
function streamFileWalker($StreamFile) {
  global $Verbose, $StreamInspectionLayers, $StreamInspectionFilesPerLayer, $DefaultStreamInspectionForfeitAction, $EnableMemoryProtection;
  // / Set variables.
  $Halt = $StreamBudgetExhausted = $InspectionFailed = $DownloadFailed = $StreamFileTruncated = FALSE;
  $looksLikePlaylist = $looksLikeSegment = $StreamContainsLAN = $StreamContainsIP = $StreamContainsHTTP = FALSE;
  $StreamURLResolutionFailed = $LookupFailed = FALSE;
  $urlHost = $urlPort = $urlScheme = $urlIP = FALSE;
  $SeenURLs = $AllStreamURIs = array();
  $currentLayerFiles = $nextLayerFiles = $streamURIs = $layerFile = $uriRecord = array();
  $currentLayer = $FileNumber = $FileBudget = $index = 0;
  $HaltReason = '';
  $LayerBudget = $StreamInspectionLayers;
  // / Hard ceiling on total connections for the entire walk, regardless of tree shape.
  // / Per-layer budgets bound each file individually. This bounds the whole tree.
  $TotalBudget = $StreamInspectionLayers * $StreamInspectionFilesPerLayer;
  // / Layer 0 is the user's uploaded file. It has no SourceURL because nobody fetched it.
  $currentLayerFiles[] = array('LocalPath' => $StreamFile, 'SourceURL' => '');
  // / A denied walk is always visible. A clean walk is only visible under verbose logging.
  // / An operator needs to know a stream file was refused without turning verbose logging on.
  // / The refusal is not a failure of HRConvert2 & may be an ordinary file with relative URIs.
  if ($InspectionFailed) warningEntry('Stream Walk Result: DENIED, Layers Walked: '.$currentLayer.', Files Downloaded: '.$FileNumber.', URIs Examined: '.count($AllStreamURIs).', Unique URLs Seen: '.count($SeenURLs).', Budget Exhausted: '.($StreamBudgetExhausted ? 'TRUE' : 'FALSE').', Reason: '.($HaltReason === '' ? 'NONE' : $HaltReason).'.');
  else if ($Verbose) logEntry('Stream Walk Result: ALLOWED, Layers Walked: '.$currentLayer.', Files Downloaded: '.$FileNumber.', URIs Examined: '.count($AllStreamURIs).', Unique URLs Seen: '.count($SeenURLs).'.');
  // / Walk one whole layer at a time until we run out of layers, work, or patience.
  while (!$Halt && !empty($currentLayerFiles) && $LayerBudget > 0) {
    $FileBudget = $StreamInspectionFilesPerLayer;
    $nextLayerFiles = array();
    foreach ($currentLayerFiles as $layerFile) {
      if ($Halt) break;
      // / Inspect one local file. Returns the URIs it references & its own local flags.
      list ($InspectionFailed, $streamURIs, $StreamContainsLAN, $StreamContainsIP, $StreamContainsHTTP, $looksLikePlaylist, $looksLikeSegment) = inspectStreamFile($layerFile['LocalPath'], $layerFile['SourceURL'], $currentLayer);
      if ($InspectionFailed) {
        $Halt = TRUE;
        $HaltReason = 'File inspection failed at layer '.$currentLayer.' on '.$layerFile['LocalPath'];
        break; }
      // / A genuine segment is a leaf. It has no URIs & nothing to recurse into, so this branch is done.
      if ($looksLikeSegment) continue;
      // / A file that is neither playlist nor segment should never have passed inspection.
      // / If it somehow did, refuse rather than guessing what FFMPEG would make of it.
      if (!$looksLikePlaylist) {
        $Halt = TRUE;
        $InspectionFailed = TRUE;
        $HaltReason = 'Unclassifiable file passed inspection at layer '.$currentLayer;
        break; }
      // / Early exit. If this file alone exceeds the per-layer budget, the walk can never complete.
      // / Deny now, before spending a single connection on a file that was never going to pass.
      if (count($streamURIs) > $FileBudget) {
        $StreamBudgetExhausted = TRUE;
        $Halt = TRUE;
        $HaltReason = 'File references '.count($streamURIs).' URIs, exceeding the per-layer budget of '.$FileBudget;
        break; }
      foreach ($streamURIs as $index => $uriRecord) {
        // / Resolve relative URIs against the URL this manifest came from.
        $streamURIs[$index]['AbsoluteURL'] = resolveStreamURI($uriRecord['RawURI'], $uriRecord['ParentURL']);
        // / An empty result means the URI was relative with no parent to inherit from,
        // / or the parent URL itself was unusable. Never guess. Refuse.
        if ($streamURIs[$index]['AbsoluteURL'] === '') {
          $streamURIs[$index]['Failed'] = TRUE;
          $streamURIs[$index]['FailReason'] = 'Unresolvable URI';
          $Halt = TRUE;
          $InspectionFailed = TRUE;
          $HaltReason = 'Unresolvable URI "'.$uriRecord['RawURI'].'" at layer '.$currentLayer;
          break; }
        // / Skip anything already seen. Prevents cycles from burning the budget.
        // / No budget is refunded here because none was ever spent on this URL.
        if (isset($SeenURLs[$streamURIs[$index]['AbsoluteURL']])) continue;
        $SeenURLs[$streamURIs[$index]['AbsoluteURL']] = TRUE;
        // / Validate scheme, host & DNS. Nothing connects until this passes.
        list ($InspectionFailed, $StreamURLResolutionFailed, $StreamContainsLAN, $LookupFailed, $urlHost, $urlPort, $urlScheme, $urlIP) = gatherRemoteStreamHostInfo($streamURIs[$index]['AbsoluteURL']);
        if ($InspectionFailed) {
          $streamURIs[$index]['Failed'] = TRUE;
          $streamURIs[$index]['FailReason'] = 'Host validation failed';
          $Halt = TRUE;
          $HaltReason = 'Host validation failed for '.$streamURIs[$index]['AbsoluteURL'].' (LAN: '.($StreamContainsLAN ? 'TRUE' : 'FALSE').', Lookup Failed: '.($LookupFailed ? 'TRUE' : 'FALSE').', URL Resolution Failed: '.($StreamURLResolutionFailed ? 'TRUE' : 'FALSE').')';
          break; }
        $streamURIs[$index]['URLHost'] = $urlHost;
        $streamURIs[$index]['URLPort'] = $urlPort;
        $streamURIs[$index]['URLScheme'] = $urlScheme;
        $streamURIs[$index]['URLIP'] = $urlIP;
        // / Spend budget BEFORE connecting, never after.
        if ($FileBudget < 1 or $TotalBudget < 1) {
          $StreamBudgetExhausted = TRUE;
          $Halt = TRUE;
          $HaltReason = 'Budget exhausted before connecting (files remaining this layer: '.$FileBudget.', total remaining: '.$TotalBudget.')';
          break; }
        $FileBudget--;
        $TotalBudget--;
        // / Increment before the call so a failed download still burns its number.
        // / Reusing a number could leave a partial file where the next fetch expects to write.
        $FileNumber++;
        // / Fetch with the pinned IP so CURL cannot re-resolve or follow a redirect we did not validate.
        list ($DownloadFailed, $streamURIs[$index]['LocalPath'], $StreamFileTruncated) = downloadStreamFile($streamURIs[$index]['AbsoluteURL'], $urlHost, $urlPort, $urlIP, $urlScheme, $FileNumber);
        if ($DownloadFailed) {
          $streamURIs[$index]['Failed'] = TRUE;
          $streamURIs[$index]['FailReason'] = 'Download failed';
          $Halt = TRUE;
          $InspectionFailed = TRUE;
          $HaltReason = 'Download failed for '.$streamURIs[$index]['AbsoluteURL'];
          break; }
        // / Classify what actually came back. The URI's extension is irrelevant & untrustworthy.
        $downloadedContents = file_get_contents($streamURIs[$index]['LocalPath']);
        list ($streamURIs[$index]['IsPlaylist'], $streamURIs[$index]['IsSegment']) = classifyStreamContent($downloadedContents);
        $downloadedContents = NULL;
        unset($downloadedContents);
        // / Neither format. FFMPEG would probe this & pick some demuxer we never anticipated.
        if (!$streamURIs[$index]['IsPlaylist'] && !$streamURIs[$index]['IsSegment']) {
          $streamURIs[$index]['Failed'] = TRUE;
          $streamURIs[$index]['FailReason'] = 'Unrecognized content';
          $Halt = TRUE;
          $InspectionFailed = TRUE;
          $HaltReason = 'Unrecognized content returned by '.$streamURIs[$index]['AbsoluteURL'];
          break; }
        // / A playlist we only half-read cannot be honestly called inspected. A truncated segment is fine,
        // / because a segment only ever needed its first few packets to be identified.
        if ($streamURIs[$index]['IsPlaylist'] && $StreamFileTruncated) {
          $streamURIs[$index]['Failed'] = TRUE;
          $streamURIs[$index]['FailReason'] = 'Playlist exceeded inspection size limit';
          $Halt = TRUE;
          $InspectionFailed = TRUE;
          $HaltReason = 'Playlist at '.$streamURIs[$index]['AbsoluteURL'].' exceeded the inspection size limit & could only be partially read';
          break; }
        $streamURIs[$index]['Inspected'] = TRUE;
        // / Only playlists have children. Segments are leaves & cost nothing further.
        // / Queue playlists with the URL THEY were fetched from, because that is what
        // / their own relative URIs must resolve against.
        if ($streamURIs[$index]['IsPlaylist']) $nextLayerFiles[] = array(
          'LocalPath' => $streamURIs[$index]['LocalPath'],
          'SourceURL' => $streamURIs[$index]['AbsoluteURL']); }
      // / Preserve this file's records before $streamURIs is overwritten by the next file in the layer.
      $AllStreamURIs = array_merge($AllStreamURIs, $streamURIs); }
    // / This layer is finished. Advance to whatever it queued up.
    $currentLayerFiles = $nextLayerFiles;
    $currentLayer++;
    $LayerBudget--; }
  // / Ran out of layers with work still pending. The inspection is incomplete either way.
  if (!empty($currentLayerFiles) && $LayerBudget < 1) {
    $StreamBudgetExhausted = TRUE;
    if ($HaltReason === '') $HaltReason = 'Layer budget exhausted with '.count($currentLayerFiles).' file(s) still uninspected'; }
  // / Apply the configured forfeit action to any incomplete inspection.
  if ($StreamBudgetExhausted && $DefaultStreamInspectionForfeitAction === 'DENY') $InspectionFailed = TRUE;
  // / Log the outcome of the entire walk.
  if ($Verbose) logEntry('Stream Walk Result: '.($InspectionFailed ? 'DENIED' : 'ALLOWED').', Layers Walked: '.$currentLayer.', Files Downloaded: '.$FileNumber.', URIs Examined: '.count($AllStreamURIs).', Unique URLs Seen: '.count($SeenURLs).', Budget Exhausted: '.($StreamBudgetExhausted ? 'TRUE' : 'FALSE').', Reason: '.($HaltReason === '' ? 'NONE' : $HaltReason).'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $layerFile & $uriRecord hold whole records including validated IPs & local paths, so they matter most here.
  purgeSensitiveMemory($EnableMemoryProtection, $currentLayerFiles, $nextLayerFiles, $streamURIs, $layerFile, $uriRecord, $currentLayer, $index, $urlHost, $urlPort, $urlScheme, $urlIP);
  return array($InspectionFailed, $StreamBudgetExhausted, $HaltReason, $AllStreamURIs, $SeenURLs); }
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
// / A function to convert stream formats.
// / The stream file is fully inspected before FFMPEG is allowed anywhere near it.
// / FFMPEG is launched in the background so the user can be served immediately.
// / The installed FFMPEG is verified against the stream minimum, not the general minimum.
// / Builds from v2.0 through v6.0 apply their own protocol whitelist to nested playlist segments.
// / Stream inspection cannot protect an affected build, so those builds are refused outright.
function convertStreams($pathname, $newPathname) {
  // / Set variables.
  global $Verbose, $StreamConnectionTimeout, $AllowStreamOverHTTP, $MinimumStreamFFMPEGVersion, $EnableMemoryProtection;
  $ConversionSuccess = $ConversionErrors = $WaitForStream = FALSE;
  $ffmpegVersionIsValid = $inspectionFailed = $streamBudgetExhausted = FALSE;
  $allStreamURIs = $seenURLs = array();
  $haltReason = $httpString = $returnData = $ffmpegCommand = '';
  $StreamPID = 0;
  if ($Verbose) logEntry('Beginning stream conversion for '.$pathname.'.');
  // / Confirm the installed FFMPEG is not one of the builds that ignores our protocol whitelist.
  $ffmpegVersionIsValid = verifyFFMPEGVersion($MinimumStreamFFMPEGVersion);
  if (!$ffmpegVersionIsValid) {
    $ConversionErrors = TRUE;
    errorEntry('The installed FFMPEG version is missing, unidentifiable, or vulnerable to stream playlist protocol bypass!', 21002, FALSE); }
  else {
    // / Inspect the entire stream tree BEFORE FFMPEG is permitted to touch it.
    // / Nothing below this point runs unless the walk returned a clean verdict.
    list ($inspectionFailed, $streamBudgetExhausted, $haltReason, $allStreamURIs, $seenURLs) = streamFileWalker($pathname);
    if ($inspectionFailed) {
      $ConversionErrors = TRUE;
      errorEntry('Stream inspection denied this file. '.$haltReason.'.', 21001, FALSE); }
    // / The inspection returned a clean verdict, so FFMPEG may now be permitted to run.
    else {
      // / Only widen the protocol whitelist to plain http when config.php explicitly allows it.
      if ($AllowStreamOverHTTP) $httpString = ',http';
      // / Launch FFMPEG in the background & capture its PID so waitForStream() can reap it later.
      // / -rw_timeout is an INPUT option & must appear before -i to have any effect.
      // / The connection timeout is documented in seconds & is converted to microseconds here.
      $ffmpegCommand = 'ffmpeg -protocol_whitelist '.escapeshellarg('file,https,tcp,tls,crypto'.$httpString)
        .' -rw_timeout '.((int)$StreamConnectionTimeout * 1000000)
        .' -i '.escapeshellarg($pathname)
        .' -c copy '.escapeshellarg($newPathname)
        .' > /dev/null 2>&1 & echo $!';
      $returnData = shell_exec($ffmpegCommand);
      $StreamPID = (int)trim($returnData);
      // / A PID of 0 means the process never started at all.
      if ($StreamPID > 0) {
        $ConversionSuccess = TRUE;
        $WaitForStream = TRUE;
        if ($Verbose) logEntry('FFMPEG launched in background as PID '.$StreamPID.' for '.$newPathname.'.'); }
      else {
        $ConversionErrors = TRUE;
        errorEntry('The stream converter failed to launch!', 21000, FALSE); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $returnData, $ffmpegCommand, $httpString, $allStreamURIs, $seenURLs, $haltReason, $streamBudgetExhausted, $inspectionFailed, $ffmpegVersionIsValid, $pathname, $newPathname);
  return array($ConversionSuccess, $ConversionErrors, $WaitForStream, $StreamPID); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert audio formats.
// / The general minimum is enforced rather than the stream minimum, because an audio
// / conversion reads a local file & never fetches anything remote.
function convertAudio($pathname, $newPathname, $extension, $bitrate) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer, $MinimumFFMPEGVersion, $EnableMemoryProtection;
  $ConversionSuccess = $ConversionErrors = $sandboxIsAvailable = FALSE;
  $ffmpegBinary = FALSE;
  $returnData = $ffmpegCommand = '';
  $br = ' ';
  $stopper = 0;
  $sleepTime = $SleepTimer;
  // / Determine if the bitrate is being set.
  if (empty($bitrate) or $bitrate === '') $bitrate = 'auto';
  if ($bitrate === 'auto') $br = ' ';
  else $br = ' -b:a '.escapeshellarg($bitrate).' ';
  // / Locate & verify FFMPEG. A path is returned only when both succeeded.
  $ffmpegBinary = verifyFFMPEGVersion($MinimumFFMPEGVersion);
  if ($ffmpegBinary === FALSE) {
    $ConversionErrors = TRUE;
    errorEntry('The installed FFMPEG version is missing, unidentifiable, or too old!', 12001, FALSE); }
  else {
    // / Build & sandbox the command once. It does not change between retries.
    $ffmpegCommand = escapeshellarg($ffmpegBinary).' -y -vn -i '.escapeshellarg($pathname).$br.escapeshellarg($newPathname);
    list ($sandboxIsAvailable, $ffmpegCommand) = sandboxCommand($ffmpegCommand, $pathname, $newPathname, FALSE, 'ffmpeg');
    if (!$sandboxIsAvailable) {
      $ConversionErrors = TRUE;
      errorEntry('Bubblewrap is missing or non functional, so this audio conversion cannot be isolated!', 12002, FALSE); }
    else {
      if ($Verbose) logEntry('Converting audio.');
      // / This code will attempt the conversion up to $StopCounter number of times.
      while (!file_exists($newPathname) && $stopper <= $StopCounter) {
        // / If the last conversion attempt failed, wait a moment before trying again.
        if ($stopper !== 0) sleep($sleepTime++);
        $returnData = shell_exec($ffmpegCommand);
        // / Count the number of conversions to avoid infinite loops.
        $stopper++;
        // / Stop attempting the conversion after $StopCounter number of attempts.
        if ($stopper === $StopCounter) {
          $ConversionErrors = TRUE;
          errorEntry('The audio converter timed out!', 12000, FALSE); } }
      // / Log the output of the operation to the logfile, if it is not blank.
      if ($Verbose && trim($returnData) !== '') logEntry('Ffmpeg returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
      // / The output file is the only verdict on whether the conversion produced anything.
      // / This check must stay inside the gates, or a stale output file from an earlier
      // / attempt would report success for a conversion that was refused & never ran.
      if (file_exists($newPathname)) $ConversionSuccess = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $returnData, $stopper, $pathname, $br, $bitrate, $sleepTime, $ffmpegBinary, $ffmpegCommand, $sandboxIsAvailable);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to cryptographically validate the integrity of the bundled boot assets.
// / Accepts the requested bootable extension & the staging folder the image is built from.
// / Returns TRUE when every asset this image needs is present & matches its pinned hash.
// / Returns TRUE without checking anything when the user supplied their own boot files,
// / because a user supplied bootloader is the users own business & is not ours to pin.
// / Returns FALSE when a bundled asset is missing, corrupt, or when the requested extension
// / is not one this function knows how to validate.
// /
// / EVERY BOOTABLE FORMAT IS VALIDATED, INCLUDING THE LEGACY MBR IMAGE.
// / An earlier version skipped iso_mbr-boot entirely & returned TRUE for it without looking
// / at anything. isolinux.bin is a bootloader copied into a user facing image, so it is
// / exactly as worth pinning as the UEFI images are & is now checked the same way.
// /
// / A HYBRID IMAGE NEEDS TWO ASSETS RATHER THAN ONE.
// / It carries both an isolinux record & an EFI record, so isolinux.bin & the blank EFI
// / image are BOTH validated & both must pass. Every other format needs exactly one.
// / ldlinux.c32 is deliberately NOT pinned. It is optional, its absence is handled by the
// / builder, & a missing optional file must not fail a validation that would otherwise pass.
// /
// / A hash of all zeroes in the map means the asset has not been pinned yet & the image
// / cannot be built. That is deliberate. An unpinned bootloader is worse than no bootloader.
function verifyIsoBootloaders($extension, $safedir2) {
  // / Set variables.
  global $DirSep, $BootloadersDir, $EnableMemoryProtection;
  $BootloadersOK = FALSE;
  $userPathIsPresent = $allAssetsValid = FALSE;
  $userEfiPath = $bundledPath = $computedHash = $requiredAsset = '';
  $assetMap = $requiredAssets = array();
  // / Every bundled asset & the hash it must carry.
  // / isolinux.bin lives in its own subdirectory alongside the extra folder it ships with.
  $assetMap = array(
    'isolinux'   => array('asset' => 'isolinux'.$DirSep.'isolinux.bin', 'hash' => 'f0f645e52bbe18bf7a4ac07be9afa985e21d8eb8c9c57938bdaf5b868a0e0e7f'),
    'blank_efi'  => array('asset' => 'blank_efi_2880.img',              'hash' => 'fd2b9e5cd6323786193088344e4d02754431c5fdcda6e396145e42686d1f10a2'),
    'uefi_ia32'  => array('asset' => 'uefi_ia32.img',                   'hash' => 'bc6e4399eab62623c7b200f2422618d3ff3ec32f613bffbcec4ebfb07a8540cc'),
    'uefi_x64'   => array('asset' => 'uefi_x64.img',                    'hash' => 'b74ab9b632ca5d33694befc2646eaba202c0a5a8b268784bad20f1e8e1952cd2'),
    'uefi_arm'   => array('asset' => 'uefi_arm.img',                    'hash' => 'f2b5fcfac8f1bff9f58228fc449c4d795ed8c213e02346620873acc8a21abd94'),
    'uefi_arm64' => array('asset' => 'uefi_arm64.img',                  'hash' => '925ebcf62a396f6c737269fe1c5f74d03e528a5ff7ecedb1be51699a5f71581c'));
  // / Which assets each bootable format actually requires.
  // / A hybrid image needs both halves. Everything else needs exactly one.
  if ($extension === 'iso_mbr-boot') $requiredAssets = array('isolinux');
  else if ($extension === 'iso_gpt-boot') $requiredAssets = array('isolinux', 'blank_efi');
  else if ($extension === 'iso_gpt-boot-x86') $requiredAssets = array('uefi_ia32');
  else if ($extension === 'iso_gpt-boot-x86-64') $requiredAssets = array('uefi_x64');
  else if ($extension === 'iso_gpt-boot-arm32') $requiredAssets = array('uefi_arm');
  else if ($extension === 'iso_gpt-boot-arm64') $requiredAssets = array('uefi_arm64');
  // / An extension this function does not recognize is refused rather than assumed safe.
  if (empty($requiredAssets)) $BootloadersOK = FALSE;
  else {
    // / A user who supplied their own boot files is using theirs rather than ours, so there
    // / is nothing of ours to validate. The builder makes the same decision from the same
    // / path, so both functions agree on which set is in play.
    $userEfiPath = $safedir2.$DirSep.'boot'.$DirSep.'grub'.$DirSep.'efi.img';
    if ($extension !== 'iso_mbr-boot' && file_exists($userEfiPath)) $userPathIsPresent = TRUE;
    if ($extension === 'iso_mbr-boot' && file_exists($safedir2.$DirSep.'isolinux'.$DirSep.'isolinux.bin')) $userPathIsPresent = TRUE;
    if ($userPathIsPresent) $BootloadersOK = TRUE;
    else {
      // / Validate every bundled asset this format needs. All of them must pass.
      $allAssetsValid = TRUE;
      foreach ($requiredAssets as $requiredAsset) {
        $bundledPath = $BootloadersDir.$DirSep.$assetMap[$requiredAsset]['asset'];
        if (!file_exists($bundledPath)) {
          $allAssetsValid = FALSE;
          continue; }
        $computedHash = hash_file('sha256', $bundledPath);
        // / An empty or unpinned hash fails. An asset nobody has pinned is not validated.
        if (empty($assetMap[$requiredAsset]['hash'])) $allAssetsValid = FALSE;
        else if ($computedHash !== $assetMap[$requiredAsset]['hash']) $allAssetsValid = FALSE; }
      $BootloadersOK = $allAssetsValid; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $extension, $safedir2, $userEfiPath, $bundledPath, $computedHash, $requiredAsset, $assetMap, $requiredAssets, $userPathIsPresent, $allAssetsValid);
  return $BootloadersOK; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to generate a bootable ISO image configuration command.
// / Accepts the requested bootable extension, the intended output path, the staging folder
// / & the two binaries the operation may need.
// / Returns the mkisofs command, the normalized extension, the corrected output path & a
// / SEPARATE isohybrid command which is an empty string for every image that does not need one.
// / Returns FALSE as the first value when no usable boot configuration could be assembled.
// /
// / THE TWO COMMANDS ARE RETURNED SEPARATELY & MUST NOT BE JOINED WITH &&.
// / shell_exec() runs its argument through a shell, but bwrap execs a single command & does
// / not. A compound statement handed to the sandbox therefore has its && parsed by the
// / OUTER shell, which would run isohybrid outside the namespace against a path that only
// / exists inside it. Each command is sandboxed on its own by the caller.
// /
// / The required boot files are injected into the staging folder in place, so the image is
// / built from a directory that already contains everything it needs.
// / A user who supplied their own boot files keeps them. Nothing bundled overwrites them.
// / The intent suffix is stripped from the returned path, so everything downstream sees an
// / ordinary iso rather than an architecture flag it does not understand.
function generateBootableIsoCommand($extension, $newPathname, $safedir2, $mkisofsBinary, $isoHybridBinary) {
  // / Set variables.
  global $DirSep, $BootloadersDir, $EnableMemoryProtection;
  $bootData = FALSE;
  $CleanNewPathname = $HybridCommand = $baseFlags = $bootFlags = $command = $userBin = $targetIsoLinuxDir = '';
  $bundledBin = $bundledC32 = $isoRelativeEfi = $bundledImg = $targetEfiName = $targetEfiPath = '';
  $selectedGpt = $gptMap = array();
  // / Basic validation checks to ensure filesystems and binaries exist.
  if ($mkisofsBinary !== FALSE && file_exists($mkisofsBinary) && is_dir($safedir2) && !empty($BootloadersDir) && is_dir($BootloadersDir)) {
    // / Normalize the target filename right away to end in a standard .iso extension.
    $CleanNewPathname = str_replace($extension, 'iso', $newPathname);
    // / Base compatibility flags for modern cross platform systems.
    $baseFlags = '-R -J -joliet-long';
    // / Handle the Legacy MBR Bootstrap implementation.
    if ($extension === 'iso_mbr-boot') {
      $userBin = 'isolinux'.$DirSep.'isolinux.bin';
      if (file_exists($safedir2.$DirSep.$userBin)) {
        $bootFlags = '-b '.escapeshellarg('isolinux/isolinux.bin').' -c '.escapeshellarg('isolinux/boot.cat').' -no-emul-boot -boot-load-size 4 -boot-info-table'; }
      else {
        $targetIsoLinuxDir = $safedir2.$DirSep.'isolinux';
        if (!is_dir($targetIsoLinuxDir)) @mkdir($targetIsoLinuxDir, 0755, TRUE);
        $bundledBin = $BootloadersDir.$DirSep.'isolinux'.$DirSep.'isolinux.bin';
        $bundledC32 = $BootloadersDir.$DirSep.'isolinux'.$DirSep.'extra'.$DirSep.'ldlinux.c32';
        if (file_exists($bundledBin)) {
          @copy($bundledBin, $targetIsoLinuxDir.$DirSep.'isolinux.bin');
          if (file_exists($bundledC32)) @copy($bundledC32, $targetIsoLinuxDir.$DirSep.'ldlinux.c32');
          $bootFlags = '-b '.escapeshellarg('isolinux/isolinux.bin').' -c '.escapeshellarg('isolinux/boot.cat').' -no-emul-boot -boot-load-size 4 -boot-info-table'; } } }
    // / Handle the Universal Multi-Boot Hybrid ISO implementation.
    // / The bundled assets live in the isolinux subdirectory, exactly as the MBR branch
    // / expects them. An earlier version read them from the top of $BootloadersDir.
    else if ($extension === 'iso_gpt-boot') {
      $targetIsoLinuxDir = $safedir2.$DirSep.'isolinux';
      if (!is_dir($targetIsoLinuxDir)) @mkdir($targetIsoLinuxDir, 0755, TRUE);
      $bundledBin = $BootloadersDir.$DirSep.'isolinux'.$DirSep.'isolinux.bin';
      $bundledC32 = $BootloadersDir.$DirSep.'isolinux'.$DirSep.'extra'.$DirSep.'ldlinux.c32';
      $bundledImg = $BootloadersDir.$DirSep.'blank_efi_2880.img';
      $targetEfiName = 'efi.img';
      $targetEfiPath = $safedir2.$DirSep.$targetEfiName;
      if (file_exists($bundledBin) && file_exists($bundledImg)) {
        @copy($bundledBin, $targetIsoLinuxDir.$DirSep.'isolinux.bin');
        if (file_exists($bundledC32)) @copy($bundledC32, $targetIsoLinuxDir.$DirSep.'ldlinux.c32');
        @copy($bundledImg, $targetEfiPath);
        // / Map BOTH el torito records simultaneously so the image presents a valid hybrid topology.
        $bootFlags = '-b '.escapeshellarg('isolinux/isolinux.bin').' -c '.escapeshellarg('isolinux/boot.cat').' -no-emul-boot -boot-load-size 4 -boot-info-table -eltorito-alt-boot -e '.escapeshellarg($targetEfiName).' -no-emul-boot'; } }
    // / Handle the Explicit Target Architecture UEFI Bootstrap implementations.
    else {
      $gptMap = array(
        'iso_gpt-boot-x86'    => array('asset' => 'uefi_ia32.img',  'user_path' => 'boot'.$DirSep.'grub'.$DirSep.'efi.img'),
        'iso_gpt-boot-x86-64' => array('asset' => 'uefi_x64.img',   'user_path' => 'boot'.$DirSep.'grub'.$DirSep.'efi.img'),
        'iso_gpt-boot-arm32'  => array('asset' => 'uefi_arm.img',   'user_path' => 'boot'.$DirSep.'grub'.$DirSep.'efi.img'),
        'iso_gpt-boot-arm64'  => array('asset' => 'uefi_arm64.img', 'user_path' => 'boot'.$DirSep.'grub'.$DirSep.'efi.img') );
      if (isset($gptMap[$extension])) {
        $selectedGpt = $gptMap[$extension];
        if (file_exists($safedir2.$DirSep.$selectedGpt['user_path'])) {
          $isoRelativeEfi = str_replace($DirSep, '/', $selectedGpt['user_path']);
          $bootFlags = '-eltorito-alt-boot -e '.escapeshellarg($isoRelativeEfi).' -no-emul-boot'; }
        else {
          $bundledImg = $BootloadersDir.$DirSep.$selectedGpt['asset'];
          $targetEfiName = 'efi.img';
          $targetEfiPath = $safedir2.$DirSep.$targetEfiName;
          if (file_exists($bundledImg)) {
            @copy($bundledImg, $targetEfiPath);
            $bootFlags = '-eltorito-alt-boot -e '.escapeshellarg($targetEfiName).' -no-emul-boot'; } } } }
    // / Build the resulting instruction string only when valid parameters were injected.
    if ($bootFlags !== '') {
      $command = escapeshellarg($mkisofsBinary).' '.$baseFlags.' '.$bootFlags.' -o '.escapeshellarg($CleanNewPathname).' '.escapeshellarg($safedir2);
      $bootData = $command;
      // / Only the generic hybrid image is post processed.
      // / It carries BOTH an isolinux record & an EFI record, & isohybrid writes the MBR
      // / that points at the first of those. An architecture specific UEFI image has no
      // / isolinux record to point at, so it is never passed through isohybrid.
      // / This is tested BEFORE the extension is normalized below. An earlier version
      // / tested it afterwards, by which point the extension was always iso & the isohybrid
      // / step could never fire at all.
      if ($extension === 'iso_gpt-boot' && $isoHybridBinary !== FALSE) $HybridCommand = escapeshellarg($isoHybridBinary).' --uefi '.escapeshellarg($CleanNewPathname); } }
  // / Reset the extension to iso without the bootable architecture flag so the rest of the
  // / core will use the correct filename.
  $extension = 'iso';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $newPathname, $safedir2, $mkisofsBinary, $isoHybridBinary, $baseFlags, $bootFlags, $command, $userBin, $targetIsoLinuxDir, $bundledBin, $bundledC32, $gptMap, $selectedGpt, $isoRelativeEfi, $bundledImg, $targetEfiName, $targetEfiPath);
  return array($bootData, $extension, $CleanNewPathname, $HybridCommand); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert archive & disk image formats.
// / The source is extracted into a staging folder & the staging folder is re-archived.
// / 7-Zip is the only extractor, so every INPUT format depends on it & nothing works
// / without it. Each OUTPUT format has its own creator & gates on that creator alone, so a
// / missing mkisofs does not prevent a zip conversion.
// / An extraction that produces nothing is refused rather than re-archived, because the
// / archiver would package the empty staging folder & the result would exist, which is the
// / only test the success path performs.
// / A bootable ISO carries its target architecture in the requested extension, such as
// / iso_gpt-boot-x86-64. generateBootableIsoCommand() normalizes that back to iso & returns
// / a corrected output path, so both are reassigned from what it hands back.
// / A generic hybrid image is built in TWO sandboxed steps rather than one. mkisofs writes
// / the image & isohybrid then rewrites its MBR in place.
function convertArchives($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $ConvertDir, $Lol, $Lolol, $StopCounter, $SleepTimer, $PermissionLevels, $Minimum7zVersion, $MinimumRarVersion, $MinimumZipVersion, $MinimumTarVersion, $MinimumMkisofsVersion, $MinimumIsoHybridVersion, $AllowBootableIsoImage, $BootableIsoArray, $EnableMemoryProtection;
  $ConversionSuccess = $ConversionErrors = $sandboxIsAvailable = FALSE;
  $archiveToolsAreValid = $bootloadersAreValid = FALSE;
  $sevenZipBinary = $rarBinary = $zipBinary = $tarBinary = $mkisofsBinary = $isoHybridBinary = FALSE;
  $returnData = $extractCommand = $archiveCommand = $hybridCommand = $UserFilename = '';
  $stopper = 0;
  $sleepTime = $SleepTimer;
  $oldExtension = getExtension($pathname);
  $archiveError = 13000;
  $filename = pathinfo($pathname, PATHINFO_FILENAME);
  $safedir2 = $ConvertDir.$filename;
  $array7zo = array('7z', 'cbz', 'cbr');
  $arrayzipo = array('zip');
  $array7zo2 = array('vhd', 'vdi', 'iso');
  $arraytaro = array('tar.gz', 'tar.bz2', 'tar');
  $arrayraro = array('rar');
  $array7zo3 = array();
  // / Only populate the list of supported bootable iso formats if config.php enables it.
  if ($AllowBootableIsoImage) $array7zo3 = $BootableIsoArray;
  // / Verify every archive utility before anything is read or written.
  list ($archiveToolsAreValid, $sevenZipBinary, $rarBinary, $zipBinary, $tarBinary, $mkisofsBinary) = verifyArchiveVersions($Minimum7zVersion, $MinimumRarVersion, $MinimumZipVersion, $MinimumTarVersion, $MinimumMkisofsVersion);
  if ($sevenZipBinary === FALSE) {
    $ConversionErrors = TRUE;
    errorEntry('The installed 7-Zip version is missing, unidentifiable, or too old!', 13008, FALSE); }
  else {
    // / Create a folder to contain extracted files.
    @mkdir($safedir2, $PermissionLevels);
    if (!is_dir($safedir2)) $ConversionErrors = TRUE;
    // / Extract the source archive into the staging folder.
    if ($Verbose) logEntry('Extracting file '.$pathname.' to '.$safedir2.'.');
    if (in_array(strtolower($oldExtension), $array7zo2)) $extractCommand = escapeshellarg($sevenZipBinary).' x -y '.escapeshellarg($pathname).' -o'.escapeshellarg($safedir2);
    else if (in_array(strtolower($oldExtension), $arrayzipo) or in_array(strtolower($oldExtension), $array7zo) or in_array(strtolower($oldExtension), $arrayraro) or in_array(strtolower($oldExtension), $arraytaro)) $extractCommand = escapeshellarg($sevenZipBinary).' x -aoa '.escapeshellarg($pathname).' -o'.escapeshellarg($safedir2);
    if ($extractCommand !== '') {
      list ($sandboxIsAvailable, $extractCommand) = sandboxCommand($extractCommand, $pathname, $safedir2, FALSE, 'archive');
      if (!$sandboxIsAvailable) {
        $ConversionErrors = TRUE;
        errorEntry('Bubblewrap is missing or non functional, so this archive extraction cannot be isolated!', 13006, FALSE); }
      else $returnData = shell_exec($extractCommand); }
    // / Log the output of the extract operation to the logfile, if it is not blank.
    if ($Verbose && trim($returnData) !== '') logEntry('The extractor returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
    // / An extraction that produced nothing must not be re-archived.
    if ($extractCommand !== '' && is_dir_empty($safedir2)) {
      $ConversionErrors = TRUE;
      errorEntry('The extractor produced no files from the source archive!', 13007, FALSE); }
    else {
      if ($Verbose) logEntry('Archiving file '.$safedir2.' to '.$newPathname.'.');
      // / Select the archiver for the requested output format & confirm it is usable.
      if (in_array($extension, $array7zo)) {
        $archiveCommand = escapeshellarg($sevenZipBinary).' a -t'.escapeshellarg($extension).' '.escapeshellarg($newPathname).' '.escapeshellarg($safedir2);
        $archiveError = 13001; }
      // / A bootable disk image. The requested extension names the target architecture.
      else if (in_array($extension, $array7zo3)) {
        $archiveError = 13100;
        // / isohybrid is only needed by the generic hybrid image, so its absence is fatal
        // / to that one format & irrelevant to every other bootable one.
        $isoHybridBinary = verifyIsoHybridVersion($MinimumIsoHybridVersion);
        if ($mkisofsBinary === FALSE) errorEntry('Mkisofs is missing, unidentifiable, or too old!', 13009, FALSE);
        else if ($extension === 'iso_gpt-boot' && $isoHybridBinary === FALSE) errorEntry('A hybrid bootable image requires the isohybrid utility from syslinux-utils, which is missing or too old!', 13107, FALSE);
        else {
          // / Confirm the bundled boot assets are intact before any of them are copied.
          $bootloadersAreValid = verifyIsoBootloaders($extension, $safedir2);
          if (!$bootloadersAreValid) errorEntry('Cryptographic validation failed for internal boot assets! Files are missing or corrupted.', 13105, FALSE);
          else {
            // / The requested extension is normalized back to iso here & the output path is
            // / corrected to match, so everything downstream sees an ordinary iso.
            list ($archiveCommand, $extension, $newPathname, $hybridCommand) = generateBootableIsoCommand($extension, $newPathname, $safedir2, $mkisofsBinary, $isoHybridBinary);
            if ($archiveCommand === FALSE) {
              $archiveCommand = '';
              errorEntry('Could not generate required bootloader assets!', 13106, FALSE); }
            else $UserFilename = str_replace($ConvertDir, '', $newPathname); } } }
      else if (in_array($extension, $array7zo2)) {
        if ($mkisofsBinary === FALSE) errorEntry('Mkisofs is missing, unidentifiable, or too old!', 13009, FALSE);
        else $archiveCommand = escapeshellarg($mkisofsBinary).' -o '.escapeshellarg($newPathname).' '.escapeshellarg($safedir2);
        $archiveError = 13002; }
      else if (in_array($extension, $arrayzipo)) {
        if ($zipBinary === FALSE) errorEntry('Zip is missing, unidentifiable, or too old!', 13010, FALSE);
        else $archiveCommand = escapeshellarg($zipBinary).' -r -j '.escapeshellarg($newPathname).' '.escapeshellarg($safedir2);
        $archiveError = 13003; }
      else if (in_array($extension, $arraytaro)) {
        if ($tarBinary === FALSE) errorEntry('Tar is missing, unidentifiable, or too old!', 13011, FALSE);
        else $archiveCommand = escapeshellarg($tarBinary).' -cjf '.escapeshellarg($newPathname).' -C '.escapeshellarg($safedir2).' .';
        $archiveError = 13004; }
      else if (in_array($extension, $arrayraro)) {
        // / 7-Zip cannot create rar archives. RAR compression is proprietary & 7-Zip reads
        // / the format without being able to write it, so there is NO fallback here.
        if ($rarBinary === FALSE) errorEntry('Rar output requires the rar utility, which is missing or too old!', 13013, FALSE);
        else $archiveCommand = escapeshellarg($rarBinary).' a -ep1 -r '.escapeshellarg($newPathname).' '.escapeshellarg($safedir2);
        $archiveError = 13005; }
      // / Perform the archive operation, retrying up to $StopCounter times.
      // / The loop exits as soon as the output exists. Without that test it always ran the
      // / full count & always reported a timeout, even on a conversion that succeeded.
      if ($archiveCommand !== '') {
        list ($sandboxIsAvailable, $archiveCommand) = sandboxCommand($archiveCommand, $safedir2, $newPathname, FALSE, 'archive');
        if (!$sandboxIsAvailable) {
          $ConversionErrors = TRUE;
          errorEntry('Bubblewrap is missing or non functional, so this archive operation cannot be isolated!', 13006, FALSE); }
        else {
          while (!file_exists($newPathname) && $stopper <= $StopCounter) {
            // / If the last attempt failed, wait a moment before trying again.
            if ($stopper !== 0) sleep($sleepTime++);
            $returnData = shell_exec($archiveCommand);
            // / Log the output of the archive operation to the logfile, if it is not blank.
            if ($Verbose && trim($returnData) !== '') logEntry('The archiver returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
            // / Count the number of attempts to avoid infinite loops.
            $stopper++;
            // / Stop attempting the archive operation after $StopCounter number of attempts.
            if ($stopper === $StopCounter) {
              $ConversionErrors = TRUE;
              errorEntry('The archiver timed out!', $archiveError, FALSE); } }
          // / Post process a hybrid image so it also boots from a USB stick presenting an MBR.
          // / This is a SEPARATE sandboxed invocation rather than a compound statement joined
          // / to the mkisofs command with &&. bwrap execs one command & does not run a shell,
          // / so an && would be parsed by the outer shell & isohybrid would run OUTSIDE the
          // / namespace against a path that only exists inside it.
          // / isohybrid modifies the image IN PLACE, so the input & the output are the same
          // / file & sandboxCommand() mounts their shared directory once at /work.
          if ($hybridCommand !== '' && file_exists($newPathname)) {
            if ($Verbose) logEntry('Post processing the hybrid image with isohybrid.');
            list ($sandboxIsAvailable, $hybridCommand) = sandboxCommand($hybridCommand, $newPathname, $newPathname, FALSE, 'archive');
            if (!$sandboxIsAvailable) {
              $ConversionErrors = TRUE;
              errorEntry('Bubblewrap is missing or non functional, so this hybrid image cannot be isolated!', 13006, FALSE); }
            else {
              $returnData = shell_exec($hybridCommand);
              // / Log the output of the operation to the logfile, if it is not blank.
              if ($Verbose && trim($returnData) !== '') logEntry('Isohybrid returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData))))); } } } } } }
  // / The output file is the only verdict on whether the conversion produced anything.
  if (!file_exists($newPathname)) {
    $ConversionErrors = TRUE;
    errorEntry('The archiver failed to produce an archive!', 13000, FALSE); }
  else $ConversionSuccess = TRUE;
  // / An ordinary archive keeps whatever filename the caller already worked out.
  // / Only a bootable image rewrites it, because only a bootable image renames the output.
  if ($UserFilename === '') $UserFilename = basename($newPathname);
  // / Code to clean up temporary files & directories.
  cleanFiles($safedir2);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $filename, $safedir2, $oldExtension, $returnData, $pathname, $array7zo, $arrayzipo, $array7zo2, $array7zo3, $arraytaro, $arrayraro, $sleepTime, $stopper, $extractCommand, $archiveCommand, $hybridCommand, $archiveError, $sandboxIsAvailable, $archiveToolsAreValid, $bootloadersAreValid, $sevenZipBinary, $rarBinary, $zipBinary, $tarBinary, $mkisofsBinary, $isoHybridBinary);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $UserFilename); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert a file based on a pre-determined input type & return the results.
// / Streams are the only conversion that keeps running after the user has been served.
// / The stream supervision variables are globals so the core can reach them after this returns.
// / Every converter returns the output path & the extension it actually produced, because a
// / bootable disk image rewrites both. Everything else hands back what it was given.
// / $UserFilename is initialized here rather than only inside the Archive branch, because
// / an undefined return value is a warning & a warning corrupts an AJAX response.
function convert($type, $pathname, $newPathname, $extension, $height, $width, $rotate, $bitrate) {
  // / Set variables.
  global $Verbose, $SupportedConversionTypes, $WaitForStream, $StreamPID, $StreamOutputPath, $EnableMemoryProtection;
  $ConversionSuccess = $ConversionErrors = FALSE;
  $UserFilename = basename($newPathname);
  // / Check that the required conversion type is allowed.
  if (in_array($type, $SupportedConversionTypes)) {
    if ($type === 'Document') list ($ConversionSuccess, $ConversionErrors, $newPathname, $extension) = convertDocuments($pathname, $newPathname, $extension);
    if ($type === 'Image') list ($ConversionSuccess, $ConversionErrors, $newPathname, $extension) = convertImages($pathname, $newPathname, $extension, $height, $width, $rotate);
    if ($type === 'Model') list ($ConversionSuccess, $ConversionErrors, $newPathname, $extension) = convertModels($pathname, $newPathname, $extension);
    if ($type === 'Scad') list ($ConversionSuccess, $ConversionErrors, $newPathname, $extension) = convertSCAD($pathname, $newPathname, $extension);
    if ($type === 'Drawing') list ($ConversionSuccess, $ConversionErrors, $newPathname, $extension) = convertDrawings($pathname, $newPathname, $extension);
    if ($type === 'SVG') list ($ConversionSuccess, $ConversionErrors, $newPathname, $extension) = convertSVG($pathname, $newPathname, $extension, $height, $width);
    if ($type === 'Video') list ($ConversionSuccess, $ConversionErrors, $newPathname, $extension) = convertVideos($pathname, $newPathname, $extension);
    if ($type === 'Subtitle') list ($ConversionSuccess, $ConversionErrors, $newPathname, $extension) = convertSubtitles($pathname, $newPathname, $extension);
    if ($type === 'Audio') list ($ConversionSuccess, $ConversionErrors, $newPathname, $extension) = convertAudio($pathname, $newPathname, $extension, $bitrate);
    if ($type === 'Archive') list ($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $UserFilename) = convertArchives($pathname, $newPathname, $extension);
    if ($type === 'Ebook') list ($ConversionSuccess, $ConversionErrors, $newPathname, $extension) = convertEbooks($pathname, $newPathname, $extension);
    // / A stream is launched rather than completed, so it reports a PID instead of a path.
    // / $StreamOutputPath is set INSIDE this branch. An earlier version set it outside &
    // / therefore overwrote it on every conversion of every other type.
    if ($type === 'Stream') {
      list ($ConversionSuccess, $ConversionErrors, $WaitForStream, $StreamPID) = convertStreams($pathname, $newPathname);
      $StreamOutputPath = $newPathname; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $type, $pathname, $height, $width, $rotate, $bitrate);
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
  global $GuiFiles, $LanguageFiles, $LanguageStringsFile, $GuiHeaderFile, $GuiFooterFile, $GuiUI1File, $GuiUI2File, $CoreLoaded, $ConvertDir, $ConvertTempDir, $Token1, $Token2, $SesHash, $SesHash2, $SesHash3, $SesHash4, $Date, $Time, $TOSURL, $PPURL, $ShowFinePrint, $PDFWorkArr, $ArchiveArray, $DearchiveArray, $DocumentArray, $SpreadsheetArray, $ImageArray, $ModelArray, $DrawingArray, $VideoInputArray, $VideoOutputArray, $SubtitleInputArray, $SubtitleOutputArray, $StreamArray, $MediaInputArray, $MediaOutputArray, $PresentationInputArray, $PresentationOutputArray, $XPSInputArray, $XPSOutputArray, $ConvertGuiCounter1, $ConsolidatedLogFileName, $Alert, $Alert1, $Alert2, $Alert3, $FCPlural, $FCPlural1, $FCPlural2, $FCPlural3, $File, $Files, $FileCount, $SpinnerStyle, $SpinnerColor, $PacmanLoc, $Allowed, $AllowUserVirusScan, $AllowUserShare, $SupportedConversionTypes, $FullURL, $LanguageDir, $FaviconPath, $DropzonePath, $DropzoneStylesheetPath, $StylesheetPath, $JsLibraryPath, $JqueryPath, $GUIDirection, $SupportedFormatCount, $GUIAlignment, $HeaderDisplayed, $UIDisplayed, $FooterDisplayed, $LanguageStringsLoaded, $GUIDisplayed, $GuiResourcesDir, $GuiImageDir, $GuiCSSDir, $GuiJSDir, $StreamOutputArray, $SCADArray, $SCADOutputArray, $AllowUserSelectableColor, $AllowUserSelectableGui, $AllowUserSelectableLanguage, $SupportedColors, $SupportedGuis, $SupportedLanguages, $ColorToUse, $GuiToUse, $LanguageToUse, $GuiDir, $SVGInputArray, $SVGOutputArray, $LanguageFlagFile, $LanguageVersion, $RequiredLanguageVersion, $DefaultLanguage, $BootableIsoArray, $AllowBootableIsoImage, $EbookInputArray, $EbookOutputArray, $EnableMemoryProtection;
  $GUIDisplayed = FALSE;
  $guiUIFile = $GuiUI1File;
  $fallbackStringsFile = '';
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
  if ($guiType === 2) {
    $Files = getFiles($ConvertDir);
    $FileCount = count($Files); }
  // / Load language specific GUI elements, if there are any.
  if (in_array($LanguageStringsFile, $LanguageFiles)) require_once($LanguageStringsFile);
  // / Check to ensure that the selected language is compatible with the rest of the GUI.
  // / The version lives inside the pack, so the pack has already been loaded by the time
  // / this can be tested. A mismatched pack cannot be trusted to define every string the
  // / UI reads, so the default pack is loaded over the top of it instead of failing.
  // / A language pack is only variable assignments, so a second load simply overwrites
  // / whatever the first one set, & that includes $LanguageVersion itself.
  // / A user who cannot read the page cannot report the problem, so this warns & continues.
  if ($LanguageVersion !== $RequiredLanguageVersion) {
    warningEntry('Language pack '.$LanguageToUse.' reports version '.$LanguageVersion.' but this core requires '.$RequiredLanguageVersion.'.');
    // / Falling back to a pack that is already loaded would achieve nothing.
    if ($LanguageToUse !== $DefaultLanguage) {
      $fallbackStringsFile = $GuiDir.'Languages/'.$DefaultLanguage.'/languageStrings.php';
      if (file_exists($fallbackStringsFile)) {
        warningEntry('Falling back to language pack '.$DefaultLanguage.'.');
        $LanguageToUse = $DefaultLanguage;
        $LanguageDir = $GuiDir.'Languages/'.$DefaultLanguage.'/';
        $LanguageStringsFile = $fallbackStringsFile;
        $LanguageFlagFile = $LanguageDir.'flag.png';
        // / require rather than require_once, because this second load is deliberate.
        require($fallbackStringsFile); } } }
  // / Only build the GUI once a compatible language pack is loaded.
  // / If the default pack is ALSO out of date then nothing is left to fall back to, & the
  // / GUI is deliberately not built. The warnings above name both packs that failed.
  if ($LanguageVersion === $RequiredLanguageVersion) {
    // / Load the header.
    if (in_array($GuiHeaderFile, $GuiFiles)) require_once($GuiHeaderFile);
    // / Build and define the different GUI types that are available.
    if ($guiType === 1) $guiUIFile = $GuiUI1File;
    if ($guiType === 2) $guiUIFile = $GuiUI2File;
    // / Build the specified GUI.
    if (in_array($guiUIFile, $GuiFiles)) require_once($guiUIFile);
    // / Load the footer.
    if (in_array($GuiFooterFile, $GuiFiles)) require_once($GuiFooterFile);
    // / Check if the required GUI elements were loaded.
    if ($HeaderDisplayed && $UIDisplayed && $FooterDisplayed && $LanguageStringsLoaded) $GUIDisplayed = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $guiType, $guiUIFile, $fallbackStringsFile, $ButtonCode);
  return $GUIDisplayed; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to display the GUI.
function showGUI($ShowGUI, $ButtonCode) {
  // / Set variables.
  $GUIDisplayed = FALSE;
  // / Call the GUI from the selected language pack after files have been uploaded.
  if (isset($_GET['showFiles'])) $GUIDisplayed = buildGUI(2, $ShowGUI, $ButtonCode);
  // / Call the GUI from the selected language pack before files have been uploaded.
  if (!isset($_GET['showFiles'])) $GUIDisplayed = buildGUI(1, $ShowGUI, $ButtonCode);
  return $GUIDisplayed; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to upload a selection of files.
function uploadFiles() {
  // / Set variables.
  global $DangerousFiles, $VirusScan, $AllowUserVirusScan, $ConvertDir, $LogFile, $Verbose, $PermissionLevels, $Allowed, $EnableMemoryProtection;
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
// / A function to upload a selection of files.
function downloadFiles($Download) {
  // / Set variables.
  global $DangerousFiles, $Verbose, $ConvertDir, $ConsolidatedLogFileName, $Allowed, $EnableMemoryProtection;
  $DownloadComplete = $DownloadErrors = $clean = $copy = $skip = $variableIsSanitized = FALSE;
  $file = $f0 = '';
  list ($Download, $variableIsSanitized) = sanitize($Download, FALSE);
  // / Make sure the input files are formatted into an array.
  if (!is_array($Download)) $Download = array($Download);
  // / Iterate through the array of input files.
  foreach ($Download as $file) {
    $DownloadComplete = FALSE;
    // / Make sure the file is sanitized before processing it.
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $OperationErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 3000, FALSE); 
      continue; }
    if ($Verbose) logEntry('User selected to Download file '.$file.'.');
    if ($file === $ConsolidatedLogFileName) $skip = TRUE;
    else $clean = $copy = TRUE;
    $f0 = getExtension($file);
    // / Make sure the file is not in the list of dangerous formats.
    if (in_array(strtolower($f0), $DangerousFiles) or !in_array(strtolower($f0), $Allowed)) {
      errorEntry('Unsupported file format, '.$f0.'!', 3004, FALSE);
      continue; }
    // / Make sure all iteration specific required variables are properly sanitized.
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname, $UserFilename) = verifyFile($file, FALSE, FALSE, $clean, $copy, $skip);
    if (!$fileIsVerified) {
      $ArchiveErrors = TRUE;
      errorEntry('Could not verify the input file.', 3001, FALSE);
      continue; }
    // / Make sure that the file exists.
    if (!file_exists($oldPathname) && !$skip) {
      $DownloadErrors = TRUE;
      errorEntry('File '.$file.' does not exist!', 3002, FALSE);
      continue; }
    if (!file_exists($pathname)) errorEntry('Could not verify the input file.', 3003, FALSE);
    else {
      if (!$DownloadErrors) $DownloadComplete = TRUE;
      if ($Verbose) logEntry('Verified file'.$newPathname.'.'); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $file, $f0, $clean, $copy, $skip, $variableIsSanitized);
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
  list ($FilesToDelete, $variableIsSanitized) = sanitize($FilesToDelete, FALSE);
  // / Make sure the input files are formatted into an array.
  if (!is_array($FilesToDelete)) $FilesToDelete = array($FilesToDelete);
  // / Iterate through the array of input files.
  foreach ($FilesToDelete as $file) {
    $DeleteComplete = FALSE;
    // / Make sure the file is sanitized before processing it.
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $DeleteErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 23000, FALSE);
      continue; }
    if ($Verbose) logEntry('User selected to Delete file '.$file.'.');
    $f0 = getExtension($file);
    // / Make sure the file is not in the list of dangerous formats.
    if (in_array(strtolower($f0), $DangerousFiles)) {
      errorEntry('Unsupported file format, '.$f0.'!', 23001, FALSE);
      continue; }
    // / Remove the selected file from the hosted location.
    list ($f0, $variableIsSanitized) = sanitize($ConvertTempDir.pathinfo($file, PATHINFO_BASENAME), FALSE);
    if (file_exists($f0)) @unlink($f0);
    // / Remove the selected file from the working location.
    list ($f1, $variableIsSanitized) = sanitize($ConvertDir.pathinfo($file, PATHINFO_BASENAME), FALSE);
    if (file_exists($f1)) @unlink($f1);
    // / Check that the selected files were deleted.
    if (!file_exists($f0) && !file_exists($f1)) {
      if ($Verbose) logEntry('Deleted file '.$file.'.');
      $DeleteComplete = TRUE; }
    else {
      $DeleteErrors = TRUE;
      errorEntry('Could not delete file '.$file.'!', 23002, FALSE); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $file, $f0, $f1, $variableIsSanitized);
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
  global $Verbose, $VirusScan, $ConvertTempDir, $Lol, $Lolol, $Minimum7zVersion, $MinimumRarVersion, $MinimumZipVersion, $MinimumTarVersion, $MinimumMkisofsVersion, $EnableMemoryProtection;
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
  global $Verbose, $VirusScan, $SpreadsheetArray, $PresentationInputArray, $PresentationOutputArray, $XPSInputArray, $XPSOutputArray, $DocumentArray, $ImageArray, $ModelArray, $SCADArray, $DrawingArray, $SVGInputArray, $SVGOutputArray, $VideoInputArray, $VideoOutputArray, $SubtitleInputArray, $SubtitleOutputArray, $StreamArray, $StreamOutputArray, $MediaInputArray, $MediaOutputArray, $ArchiveArray, $BootableIsoArray, $EbookInputArray, $EbookOutputArray, $SupportedConversionTypes, $Lol, $WaitForStream, $EnableMemoryProtection;
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
// / A function to OCR a selection of files.
// / Three input families are handled & each takes a different route.
// / A PDF is read directly by pdftotext, or rasterized & read page by page by Tesseract.
// / A document is converted to PDF by the document conversion engine.
// / An image is read directly by Tesseract, or converted to PDF & read by pdftotext.
// / Every route produces a text file which is then converted to the requested output.
// / Every binary is located & verified rather than assumed, so the version that was
// / verified is provably the version that runs.
// / Tesseract, pdftotext & ImageMagick are sandboxed. The document conversion engine is
// / not, because it is a persistent listener rather than a process launched per conversion.
// / An operation that cannot be isolated is refused, except for the ImageMagick page split,
// / which has policy.xml as a native control & is therefore only downgraded rather than
// / left with no boundary at all.
function ocrFiles($PDFWorkSelected, $UserFilename, $UserExtension, $Method) {
  // / Set variables.
  global $Verbose, $VirusScan, $ConvertTempDir, $ConvertDir, $Lol, $Lolol, $Append, $MinimumTesseractVersion, $MinimumPdftotextVersion, $MinimumImageVersion, $EnableMemoryProtection;
  $documentConverted = $OperationSuccessful = $OperationErrors = $multiple = $virusFound = $skip = $variableIsSanitized = FALSE;
  $fileIsVerified = $scanComplete = $documentEngineStarted = $sandboxIsAvailable = $anyFileSucceeded = $loopCheck = FALSE;
  $ocrToolsAreValid = FALSE;
  $tesseractBinary = $pdftotextBinary = $imageBinary = FALSE;
  $clean = $copy = TRUE;
  $returnData = $file = $pathname = $oldPathname = $oldExtension = $newPathname = '';
  $pathnameTEMP = $pathnameTEMP0 = $pathnameTEMP1 = $pathnameTEMP3 = $pathnameTEMPTesseract = '';
  $filename = $cleanFilname = $pageNumber = $pagedFile = $readPageData = '';
  $ocrCommand = '';
  $documentEnginePID = $writePageData = 0;
  $pagedFilesArrRAW = array();
  $doc1array = array('txt', 'pages', 'doc', 'xls', 'xlsx', 'docx', 'rtf', 'odt', 'ods');
  $img1array = array('jpg', 'jpeg', 'bmp', 'webp', 'png', 'gif');
  $pdf1array = array('pdf');
  $allowedOCR = array('txt', 'doc', 'docx', 'rtf', 'xls', 'xlsx', 'ods', 'odt', 'jpg', 'jpeg', 'bmp', 'webp', 'png', 'gif', 'pdf', 'abw');
  // / Locate & verify every OCR utility before anything is read.
  // / Each route gates on the specific tool it uses rather than on the overall verdict, so
  // / a missing pdftotext does not prevent an image from being read by Tesseract.
  list ($ocrToolsAreValid, $tesseractBinary, $pdftotextBinary) = verifyOCRVersions($MinimumTesseractVersion, $MinimumPdftotextVersion);
  // / ImageMagick rasterizes a PDF page for the advanced route & is verified separately.
  $imageBinary = verifyImageVersion($MinimumImageVersion);
  // / Make sure the input files are formatted into an array.
  if (!is_array($PDFWorkSelected)) $PDFWorkSelected = array($PDFWorkSelected);
  // / Iterate through the array of input files.
  foreach ($PDFWorkSelected as $file) {
    $loopCheck = $multiple = FALSE;
    // / Make sure the file is sanitized before processing it.
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $OperationErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 15000, FALSE);
      continue; }
    if ($Verbose) logEntry('User selected to perform OCR on file '.$file.'.');
    // / Verify the file before performing any operations on it.
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname, $UserFilename) = verifyFile($file, $UserFilename, $UserExtension, $clean, $copy, $skip);
    if (!$fileIsVerified) {
      $OperationErrors = TRUE;
      errorEntry('Could not verify the input file.', 15001, FALSE);
      continue; }
    else if ($Verbose) logEntry('Verified file '.$newPathname.'.');
    $pathnameTEMP = str_replace('..', '', str_replace('.'.$oldExtension, '.txt', $pathname));
    // / Scan with ClamAV if $VirusScan is set to TRUE in config.php.
    if ($VirusScan) {
      if ($Verbose) logEntry('Starting virus scan.');
      list ($scanComplete, $virusFound) = virusScan($newPathname);
      if (!$scanComplete) errorEntry('Could not perform a virus scan!', 15002, TRUE);
      if ($virusFound) errorEntry('Virus detected!', 15003, TRUE);
      if ($Verbose) logEntry('Virus scan complete.'); }
    // / Only an input format this function knows how to read is attempted.
    if (in_array(strtolower($oldExtension), $allowedOCR)) {
      // / Code to convert a PDF to a document.
      if (in_array(strtolower($oldExtension), $pdf1array)) {
        if (in_array($UserExtension, $doc1array)) {
          // / Method 0 is the automatic choice. It attempts the simple route first &
          // / falls back to the advanced one only if the simple route produces nothing.
          if ($Method === 0 or $Method === '0' or $Method === '') $Method = 1;
          // / Method 1 is the simple route. pdftotext reads a PDF that already holds text.
          // / It is fast & exact, & produces nothing at all on a scanned page.
          if ($Method === 1 or $Method === '1') {
            if ($pdftotextBinary === FALSE) {
              $OperationErrors = TRUE;
              errorEntry('The installed pdftotext version is missing, unidentifiable, or too old!', 15014, FALSE);
              $Method = 2; }
            else {
              if ($Verbose) logEntry('Performing OCR using method 1.');
              // / Perform the conversion using PDFTOTEXT.
              $ocrCommand = escapeshellarg($pdftotextBinary).' -layout '.escapeshellarg($pathname).' '.escapeshellarg($pathnameTEMP);
              list ($sandboxIsAvailable, $ocrCommand) = sandboxCommand($ocrCommand, $pathname, $pathnameTEMP, FALSE, 'tesseract');
              // / pdftotext has no native control of its own, so an unavailable sandbox leaves
              // / no boundary at all & the operation is refused rather than run without one.
              if (!$sandboxIsAvailable) {
                $OperationErrors = TRUE;
                errorEntry('Bubblewrap is missing or non functional, so this OCR operation cannot be isolated!', 15012, FALSE); }
              else $returnData = shell_exec($ocrCommand);
              // / Log the output of the operation to the logfile, if it is not blank.
              if ($Verbose && trim($returnData) !== '') logEntry('The converter (PTT-1) returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
              // / Check if the conversion was successful and retry with method 2 if required.
              if (!file_exists($pathnameTEMP)) {
                errorEntry('Could not complete the conversion using method 1. Reattempting using method 2.', 15004, FALSE);
                $Method = 2; }
              else if ($Verbose) logEntry('A file was created at '.$pathnameTEMP); } }
          // / Method 2 is the advanced route. Each page is rasterized & read by Tesseract.
          // / It reads a scanned page that holds no text layer, & is considerably slower.
          if ($Method === 2 or $Method === '2') {
            if ($imageBinary === FALSE) {
              $OperationErrors = TRUE;
              errorEntry('The installed ImageMagick version is missing, unidentifiable, or too old!', 8001, FALSE); }
            else if ($tesseractBinary === FALSE) {
              $OperationErrors = TRUE;
              errorEntry('The installed Tesseract version is missing, unidentifiable, or too old!', 15013, FALSE); }
            else {
              $pathnameTEMP1 = str_replace('..', '', str_replace('.'.$oldExtension, '.jpg', $pathname));
              if ($Verbose) logEntry('Performing OCR intermediate operation using method 2.');
              // / Perform the conversion using ImageMagick.
              $ocrCommand = escapeshellarg($imageBinary).' '.escapeshellarg($pathname).' '.escapeshellarg($pathnameTEMP1);
              list ($sandboxIsAvailable, $ocrCommand) = sandboxCommand($ocrCommand, $pathname, $pathnameTEMP1, FALSE, 'tesseract');
              // / ImageMagick has policy.xml, so an unavailable sandbox is a downgrade to a
              // / weaker control rather than to no control at all. The operation continues.
              if (!$sandboxIsAvailable) warningEntry('Bubblewrap is unavailable. This OCR page split will run unsandboxed & is protected only by policy.xml.');
              $returnData = shell_exec($ocrCommand);
              // / Log the output of the operation to the logfile, if it is not blank.
              if ($Verbose && trim($returnData) !== '') logEntry('The converter (IM-1) returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
              // / If a file doesn't exist there is a good chance it is because ImageMagick has split the pages up.
              if (!file_exists($pathnameTEMP1)) {
                // / Scan the current directory for files matching the filename.
                $pagedFilesArrRAW = scandir($ConvertTempDir);
                foreach ($pagedFilesArrRAW as $pagedFile) {
                  $filename = pathinfo($pathname, PATHINFO_FILENAME);
                  // / Look for files with the same filename but in .jpg format. Skip the rest.
                  if (stripos($pagedFile, $filename) === FALSE) continue;
                  if (stripos($pagedFile, '.jpg') === FALSE) continue;
                  if ($pagedFile === '.' or $pagedFile === '..' or $pagedFile === '.AppData' or $pagedFile === 'index.html') continue;
                  // / Set page specific variables.
                  $pathnameTEMP1 = str_replace('..', '', str_replace('.'.$oldExtension, '.jpg', $pathname));
                  $cleanFilname = str_replace('..', '', str_replace($oldExtension, '', $filename));
                  $pageNumber = str_replace('..', '', str_replace('-', '', str_replace($cleanFilname, '', str_replace('.jpg', '', $pagedFile))));
                  $pathnameTEMP1 = str_replace('..', '', str_replace('.jpg', '-'.$pageNumber.'.jpg', $pathnameTEMP1));
                  $pathnameTEMP = str_replace('..', '', str_replace('.'.$oldExtension, '-'.$pageNumber.'.txt', $pathname));
                  $pathnameTEMPTesseract = str_replace('..', '', str_replace('.'.$oldExtension, '-'.$pageNumber, $pathname));
                  $pathnameTEMP0 = str_replace('..', '', str_replace('-'.$pageNumber.'.txt', '.txt', $pathnameTEMP));
                  if ($Verbose) logEntry('Performing OCR final operation using method 2.');
                  // / Perform the conversion using Tesseract.
                  // / Tesseract appends .txt to the output argument, so what is passed is a
                  // / prefix rather than a filename. The sandbox mounts its directory either way.
                  $ocrCommand = escapeshellarg($tesseractBinary).' '.escapeshellarg($pathnameTEMP1).' '.escapeshellarg($pathnameTEMPTesseract);
                  list ($sandboxIsAvailable, $ocrCommand) = sandboxCommand($ocrCommand, $pathnameTEMP1, $pathnameTEMPTesseract, FALSE, 'tesseract');
                  // / Tesseract has no native control of its own, so an unavailable sandbox
                  // / leaves no boundary & the operation is refused rather than run without one.
                  if (!$sandboxIsAvailable) {
                    $OperationErrors = TRUE;
                    errorEntry('Bubblewrap is missing or non functional, so this OCR operation cannot be isolated!', 15012, FALSE);
                    continue; }
                  $returnData = shell_exec($ocrCommand);
                  // / Log the output of the operation to the logfile, if it is not blank.
                  if ($Verbose && trim($returnData) !== '') logEntry('The converter (T-1) returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
                  // / The text file is the verdict on this page, not the image it was read from.
                  if (!file_exists($pathnameTEMP)) errorEntry('Could not complete the conversion using method 2.', 15005, FALSE);
                  else if ($Verbose) logEntry('A file was created at '.$pathnameTEMP);
                  // / Recompile all of the text files into one big text file.
                  $readPageData = file_get_contents($pathnameTEMP);
                  $writePageData = file_put_contents($pathnameTEMP0, $readPageData.$Lol, $Append);
                  $multiple = TRUE;
                  if (!file_exists($pathnameTEMP0)) errorEntry('Could not OCR file!', 15006, FALSE);
                  else if ($Verbose) logEntry('A file was created at '.$pathnameTEMP0); } }
              if ($Verbose) logEntry('Converted file '.$pathnameTEMP1.' to '.$pathnameTEMP.'.');
              // / A single page PDF produces one image rather than a numbered set.
              if (!$multiple) {
                $pathnameTEMPTesseract = str_replace('..', '', str_replace('.txt', '', $pathnameTEMP));
                if ($Verbose) logEntry('Performing OCR final operation using method 2.');
                $ocrCommand = escapeshellarg($tesseractBinary).' '.escapeshellarg($pathnameTEMP1).' '.escapeshellarg($pathnameTEMPTesseract);
                list ($sandboxIsAvailable, $ocrCommand) = sandboxCommand($ocrCommand, $pathnameTEMP1, $pathnameTEMPTesseract, FALSE, 'tesseract');
                if (!$sandboxIsAvailable) {
                  $OperationErrors = TRUE;
                  errorEntry('Bubblewrap is missing or non functional, so this OCR operation cannot be isolated!', 15012, FALSE); }
                else $returnData = shell_exec($ocrCommand);
                // / Log the output of the operation to the logfile, if it is not blank.
                if ($Verbose && trim($returnData) !== '') logEntry('The converter (T-2) returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData))))); } } } } }
      // / Code to convert a document to a PDF.
      if (in_array(strtolower($oldExtension), $doc1array)) {
        if (in_array($UserExtension, $pdf1array)) {
          // / The following code verifies that the Document Conversion Engine is installed & running.
          list ($documentEngineStarted, $documentEnginePID) = verifyDocumentConversionEngine();
          if (!$documentEngineStarted) {
            $OperationErrors = TRUE;
            errorEntry('Could not verify the Document Conversion Engine!', 15007, FALSE); }
          else {
            // / Perform the conversion with LibreOffice, sandboxed, one process per file.
            // / The document conversion engine is a persistent listener rather than a process
            // / launched per conversion, so it cannot be sandboxed the way the others are.
            list ($documentConverted, $returnData) = convertWithLibreOffice($pathname, $newPathname, 'pdf');
            // / Log the output of the operation to the logfile, if it is not blank.
            if ($Verbose && trim($returnData) !== '') logEntry('The converter (U-1) returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData))))); } } }
      // / Code to convert an image to text.
      if (in_array(strtolower($oldExtension), $img1array)) {
        if ($tesseractBinary === FALSE) {
          $OperationErrors = TRUE;
          errorEntry('The installed Tesseract version is missing, unidentifiable, or too old!', 15013, FALSE); }
        else {
          $pathnameTEMPTesseract = str_replace('..', '', str_replace('.'.$oldExtension, '', $pathname));
          if ($Verbose) logEntry('Reading the image with Tesseract.');
          // / Perform the conversion using Tesseract.
          $ocrCommand = escapeshellarg($tesseractBinary).' '.escapeshellarg($pathname).' '.escapeshellarg($pathnameTEMPTesseract);
          list ($sandboxIsAvailable, $ocrCommand) = sandboxCommand($ocrCommand, $pathname, $pathnameTEMPTesseract, FALSE, 'tesseract');
          if (!$sandboxIsAvailable) {
            $OperationErrors = TRUE;
            errorEntry('Bubblewrap is missing or non functional, so this OCR operation cannot be isolated!', 15012, FALSE); }
          else $returnData = shell_exec($ocrCommand);
          // / Log the output of the operation to the logfile, if it is not blank.
          if ($Verbose && trim($returnData) !== '') logEntry('The converter (T-3) returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData))))); }
        // / An image Tesseract could not read is converted to PDF & read by pdftotext instead.
        if (!file_exists($pathnameTEMP)) {
          $pathnameTEMP3 = str_replace('..', '', str_replace('.'.$oldExtension, '.pdf', $pathname));
          // / The following code verifies that the Document Conversion Engine is installed & running.
          list ($documentEngineStarted, $documentEnginePID) = verifyDocumentConversionEngine();
          if (!$documentEngineStarted) {
            $OperationErrors = TRUE;
            errorEntry('Could not verify the Document Conversion Engine!', 15008, FALSE); }
          else if ($pdftotextBinary === FALSE) {
            $OperationErrors = TRUE;
            errorEntry('The installed pdftotext version is missing, unidentifiable, or too old!', 15014, FALSE); }
          else {
            if ($Verbose) logEntry('Tesseract produced nothing. Converting the image to PDF instead.');
            // / Perform the conversion with LibreOffice, sandboxed, one process per file.
            list ($documentConverted, $returnData) = convertWithLibreOffice($pathname, $pathnameTEMP3, 'pdf');
            // / Log the output of the operation to the logfile, if it is not blank.
            if ($Verbose && trim($returnData) !== '') logEntry('The converter (U-2) returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
            // / Perform the conversion using PDFTOTEXT.
            $ocrCommand = escapeshellarg($pdftotextBinary).' -layout '.escapeshellarg($pathnameTEMP3).' '.escapeshellarg($pathnameTEMP);
            list ($sandboxIsAvailable, $ocrCommand) = sandboxCommand($ocrCommand, $pathnameTEMP3, $pathnameTEMP, FALSE, 'tesseract');
            if (!$sandboxIsAvailable) {
              $OperationErrors = TRUE;
              errorEntry('Bubblewrap is missing or non functional, so this OCR operation cannot be isolated!', 15012, FALSE); }
            else $returnData = shell_exec($ocrCommand);
            // / Log the output of the operation to the logfile, if it is not blank.
            if ($Verbose && trim($returnData) !== '') logEntry('The converter (PTT-2) returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData))))); } }
        if ($Verbose && file_exists($pathnameTEMP)) logEntry('Created an intermediate file at '.$pathnameTEMP.'.');
        if (!file_exists($pathnameTEMP)) {
          $OperationErrors = TRUE;
          errorEntry('Could not create an intermediate file at '.$pathnameTEMP.'!', 15009, FALSE); } }
      // / If the output file is a txt file we leave it as-is.
      if ($UserExtension === 'txt') {
        if (file_exists($pathnameTEMP)) {
          rename($pathnameTEMP, $newPathname);
          if ($Verbose) logEntry('Renamed file '.$pathnameTEMP.' to '.$newPathname.'.'); } }
      // / If the output file is not a txt file we convert it with LibreOffice.
      else {
        // / The following code verifies that the Document Conversion Engine is installed & running.
        list ($documentEngineStarted, $documentEnginePID) = verifyDocumentConversionEngine();
        if (!$documentEngineStarted) {
          $OperationErrors = TRUE;
          errorEntry('Could not verify the Document Conversion Engine!', 15010, FALSE); }
        else {
          // / Perform the conversion with LibreOffice, sandboxed, one process per file.
          list ($documentConverted, $returnData) = convertWithLibreOffice($pathnameTEMP, $newPathname, $UserExtension);
          // / Log the output of the operation to the logfile, if it is not blank.
          if ($Verbose && trim($returnData) !== '') logEntry('The converter (U-3) returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData))))); } }
      // / Error handler for if the output file does not exist.
      if (file_exists($newPathname)) {
        $loopCheck = TRUE;
        print($UserFilename.$Lol); }
      else errorEntry('Could not create a file at '.$newPathname.'!', 15011, FALSE); }
    // / Record that at least one file in this request succeeded.
    // / $loopCheck is reset on every iteration, so without this the result would reflect
    // / only the LAST file rather than the whole request.
    if ($loopCheck) $anyFileSucceeded = TRUE; }
  // / Error handler for if any failures happened during file loops.
  if ($anyFileSucceeded) $OperationSuccessful = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $file, $pathname, $oldPathname, $filename, $oldExtension, $newPathname, $doc1array, $img1array, $pdf1array, $pathnameTEMP, $pathnameTEMP0, $pathnameTEMP1, $pathnameTEMP3, $pagedFilesArrRAW, $pagedFile, $cleanFilname, $pageNumber, $readPageData, $writePageData, $multiple, $pathnameTEMPTesseract, $clean, $copy, $skip, $allowedOCR, $variableIsSanitized, $loopCheck, $anyFileSucceeded, $ocrCommand, $sandboxIsAvailable, $fileIsVerified, $scanComplete, $virusFound, $documentEngineStarted, $documentEnginePID, $returnData, $ocrToolsAreValid, $tesseractBinary, $pdftotextBinary, $imageBinary, $PDFWorkSelected, $UserFilename, $UserExtension, $Method, $documentConverted);
  return array($OperationSuccessful, $OperationErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to create a user virus logfiles if required.
// / Type can be either 'clamav' or 'scancore'.
function verifyUserVirusLogs($type) {
  // / Set variables.
  global $Verbose, $Time, $ConvertDir, $ConvertTempDir, $UserClamLogFile, $UserScanCoreLogFile, $SesHash3, $Lol, $Append, $UserScanCoreFileName;
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
  global $Verbose, $ConvertDir, $Lol, $Lolol, $UserClamLogFile, $EnableMemoryProtection;
  $OperationSuccessful = $OperationErrors = $UserVirusFound = $userFilename = $userExtension = $clean = $copy = $userFilename = $userExtension = $variableIsSanitized = FALSE;
  $skip = TRUE;
  $returnData = $txt = $file = $clamLogFileDATA = '';
  $txt = 'Initiating User Virus Scan with ClamAV.';
  userVirusLogEntry($txt, 'clamav');
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
    $returnData = shell_exec(str_replace('  ', ' ', str_replace('   ', ' ', 'clamscan -r '.$ConvertDir.$file.' | grep FOUND >> '.$UserClamLogFile)));
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
  $txt = 'ClamAV Virus Scan Complete.';
  if ($Verbose) logEntry($txt);
  userVirusLogEntry($txt, 'clamav');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $variableIsSanitized, $clean, $copy, $skip, $returnData, $txt, $userFilename, $userExtension, $clamLogFileDATA);
  return array($OperationSuccessful, $OperationErrors, $UserVirusFound); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A fuction to prepare the execution environment for ScanCore.
function startScanCore($pathname, $UserScanCoreLogFile) {
  // / Set variables.
  global $InstLoc, $ConvertDir, $MaxLogSize, $ScanCoreMemoryLimit, $ScanCoreChunkSize, $ScanCoreDebug, $ScanCoreVerbose, $DirSep, $Date, $SesHash, $SesHash2, $EnableMemoryProtection;
  $ReturnData = $scVerbose = $scDebug = '';
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
  $ReturnData = shell_exec('php '.$ScanCoreFile.' '.$pathname.' -m '.$ScanCoreMemoryLimit.' -c '.$ScanCoreChunkSize.' -lf '.$scLogFile.' -rf '.$UserScanCoreLogFile.' -ml '.$MaxLogSize.' -r'.$scVerbose.$scDebug);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $pathname, $scVerbose, $scDebug, $scLogFile, $scInc);
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
  global $Verbose, $Lol, $Append, $ConsolidatedLogFile, $UserClamLogFile, $UserScanCoreLogFile, $ConsolidatedLogFileName, $EnableMemoryProtection;
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
  global $Verbose, $Lol, $Lolol, $ApplicationName, $UserClamLogFile, $UserScanCoreLogFile, $ConsolidatedLogFile, $ConsolidatedLogFileName, $EnableMemoryProtection;
  $ScanComplete = $ScanErrors = $UserVirusFound = $scan1Complete = $scan1Errors = $scan2Complete = $scan2Errors = $ConsolidatedLogsExist = $ConsolidatedLogErrors = FALSE;
  $fileToScan = '';
  // / Check that the $type input variable is valid.
  if ($type !== 'all' && $type !== 'clamav' && $type !== 'scancore') $type = 'all';
  // / Make sure the input files are formatted into an array.
  if (!is_array($FilesToScan)) $FilesToScan = array($FilesToScan);
  list ($LogsExist, $UserClamLogFile, $UserScanCoreLogFile) = verifyUserVirusLogs($type);
  // / Iterate through the array of input files.
  foreach ($FilesToScan as $fileToScan) {
    $ScanComplete = $scan1Complete = $scan2Complete = FALSE;
    // / Perform a User Virus Scan using ClamAV if required.
    if ($type === 'clamav' or $type === 'all') {
      // / Prepare to run a ClamAV Scan.
      list ($scan1Complete, $scan1Errors, $UserVirusFound) = userClamScan($FilesToScan); }
    // / Perform a User Virus Scan using ScanCore if required.
    if ($type === 'scancore' or $type === 'all') {
      // / Prepare to run a ScanCore Scan.
      list ($scan2Complete, $scan2Errors, $UserVirusFound) = userScanCoreScan($FilesToScan); } }
  // / Check the results of the virus scan for failures or errors.
  list ($ScanComplete, $ScanErrors) = checkUserVirusScanResults($type, $scan1Complete, $scan1Errors, $scan2Complete, $scan2Errors);
  // / Consolidate the log files created during the scan into the $ConvertTempDir so the user can access them.
  list ($ConsolidatedLogsExist, $ConsolidatedLogErrors, $ConsolidatedLogFile, $ConsolidatedLogFileName) = consolidateLogs($type, $UserClamLogFile, $UserScanCoreLogFile);
  // / Verify that all operations are complete.
  if ($ScanErrors or $ConsolidatedLogErrors) $ScanErrors = TRUE;
  if (!$ConsolidatedLogsExist) $ScanComplete = FALSE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $fileToScan, $path, $type, $scan1Complete, $scan1Errors, $scan2Complete, $scan2Errors, $returnData);
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
  purgeSensitiveMemory($EnableMemoryProtection, $timeBucket, $keyMaterial, $keyPurpose);
  return $StartupKey; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to validate a startup key supplied on the command line.
// / Accepts the purpose the key must authorize & the key that was supplied.
// / Returns TRUE when the key matches the current or the previous window.
// / The previous window is accepted because process launch is not instant.
function validateStartupKey($keyPurpose, $suppliedKey) {
  // / Set variables.
  global $SecretKey, $StartupKeyWindow, $EnableMemoryProtection;
  $KeyIsValid = FALSE;
  $timeBucket = 0;
  $currentKey = $previousKey = $cleanKey = '';
  $cleanKey = preg_replace('/[^a-f0-9]/', '', strtolower((string)$suppliedKey));
  if (is_string($SecretKey) && strlen($SecretKey) === 64 && strlen($cleanKey) === 64) {
    $timeBucket = (int)floor(time() / max(1, (int)$StartupKeyWindow));
    $currentKey = hash_hmac('sha256', 'startup|'.$keyPurpose.'|'.$timeBucket, $SecretKey);
    $previousKey = hash_hmac('sha256', 'startup|'.$keyPurpose.'|'.($timeBucket - 1), $SecretKey);
    if (hash_equals($currentKey, $cleanKey) or hash_equals($previousKey, $cleanKey)) $KeyIsValid = TRUE; }
  if (!$KeyIsValid) warningEntry('A startup key for '.$keyPurpose.' was refused.');
  purgeSensitiveMemory($EnableMemoryProtection, $timeBucket, $currentKey, $previousKey, $cleanKey, $keyPurpose, $suppliedKey);
  return $KeyIsValid; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify & load the Core Manager component.
// / Accepts the component version this core requires.
// / Returns an availability boolean & the detected version, in that order.
// / The version is read from the file WITHOUT executing it, because a mismatched component
// / defines functions this core may not be able to call safely.
// / This is an EXACT match, the same rule applied to a GUI or a language pack.
function verifyCoreManagerComponent($requiredCoreManagerVersion) {
  // / Set variables.
  global $InstLoc, $EnableMemoryProtection, $CoreLoaded;
  $ComponentIsAvailable = FALSE;
  $DetectedCoreManagerVersion = '';
  $componentPath = $componentContents = $cleanDetected = $cleanRequired = '';
  $versionMatches = array();
  $componentPath = $InstLoc.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'coreManager.php';
  if (!file_exists($componentPath)) warningEntry('The Core Manager component is not installed. Resource awareness is unavailable.');
  else {
    $componentContents = @file_get_contents($componentPath);
    if (!is_string($componentContents) or $componentContents === '') warningEntry('The Core Manager component could not be read. Resource awareness is unavailable.');
    else {
      if (preg_match('/\$CoreManagerVersion\s*=\s*\'([^\']+)\'/', $componentContents, $versionMatches)) $DetectedCoreManagerVersion = $versionMatches[1];
      $cleanDetected = ltrim(trim($DetectedCoreManagerVersion), 'vV');
      $cleanRequired = ltrim(trim((string)$requiredCoreManagerVersion), 'vV');
      // / A component that reports no version is refused. An unknown build cannot be cleared.
      if ($cleanDetected === '') warningEntry('The Core Manager component reports no version. Resource awareness is unavailable.');
      else if ($cleanDetected !== $cleanRequired) warningEntry('The Core Manager component reports v'.$cleanDetected.' & this core requires v'.$cleanRequired.'. Resource awareness is unavailable.');
      else {
        require ($componentPath);
        $ComponentIsAvailable = TRUE; } } }
  purgeSensitiveMemory($EnableMemoryProtection, $componentPath, $componentContents, $cleanDetected, $cleanRequired, $versionMatches, $requiredCoreManagerVersion);
  return array($ComponentIsAvailable, $DetectedCoreManagerVersion); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to request permission to consume resources before a conversion begins.
// / Accepts the conversion cost & the expected runtime in seconds.
// / Returns an approval boolean & the issued budget token, in that order.
// / This FAILS OPEN. When resource awareness is unavailable the request is approved & the
// / core behaves exactly as it did before this component existed.
function requestConversionBudget($conversionCost, $expectedRuntime) {
  // / Set variables.
  global $ResourceAwarenessActive, $ManagerSocketTimeout, $EnableMemoryProtection, $Verbose;
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
      if ($Verbose) logEntry('Worker '.getmypid().' was granted budget token '.$BudgetToken.'.'); }
    else logEntry('A conversion was refused by the resource budget. '.(isset($replyPayload['Reason']) && $replyPayload['Reason'] !== '' ? $replyPayload['Reason'] : 'No reason was supplied.')); }
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
  purgeSensitiveMemory($EnableMemoryProtection, $requestPayload, $replyPayload, $messageWasDelivered, $budgetToken);
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
  purgeSensitiveMemory($EnableMemoryProtection, $requestPayload, $replyPayload, $messageWasDelivered, $budgetToken, $requestedSeconds);
  return $ExtensionWasGranted; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to correct ownership & permissions on every managed path.
// / Accepts no arguments & must be run as root.
// / Returns a success boolean & the number of paths corrected, in that order.
function fixManagedPermissions() {
  // / Set variables.
  global $InstLoc, $ConvertLoc, $ConvertTemp, $LogDir, $HomeLoc, $ProprietaryLoc, $BackupLoc, $ManagerSocketDir, $ApacheUser, $SecretFile, $DirSep, $Lol, $RunningAsRoot, $EnableMemoryProtection;
  $PermissionsWereFixed = FALSE;
  $PathsCorrected = 0;
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
        $PathsCorrected++;
        print('  Corrected  '.$managedPath.$Lol); } }
    // / The socket directory is never world readable, whatever the sweep above set.
    if (is_dir($ManagerSocketDir)) exec('chmod 0700 '.escapeshellarg($ManagerSocketDir).' 2>&1', $commandOutput, $commandExitCode);
    // / The secret is the one file that must not be group or world readable.
    if (isset($SecretFile) && $SecretFile !== '' && file_exists($SecretFile)) {
      exec('chown '.escapeshellarg($ApacheUser).':'.escapeshellarg($ApacheUser).' '.escapeshellarg($SecretFile).' 2>&1', $commandOutput, $commandExitCode);
      exec('chmod 0600 '.escapeshellarg($SecretFile).' 2>&1', $commandOutput, $commandExitCode);
      $PathsCorrected++;
      print('  Corrected  '.$SecretFile.' (0600)'.$Lol); }
    $PermissionsWereFixed = TRUE;
    logEntry('Permissions were corrected on '.$PathsCorrected.' managed path(s).'); }
  purgeSensitiveMemory($EnableMemoryProtection, $managedPaths, $managedPath, $commandOutput, $commandExitCode);
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
  purgeSensitiveMemory($EnableMemoryProtection, $requestPayload, $replyPayload, $workerRegistry, $messageWasDelivered, $registryWasRead, $cleanTarget, $targetPid, $targetToken, $workerTarget);
  return $WorkerWasKilled; }
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
  purgeSensitiveMemory($EnableMemoryProtection, $typedAnswer, $promptText, $confirmationSupplied);
  return $ActionIsConfirmed; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The main logic of the program that makes use of the functions above.

// / The following code resets PHP's time limit for execution.
$TimeReset = setTimeLimit();
if (!$TimeReset) die('ERROR!!! HRConvert2-3: Could not set the execution timer!');

// / The following code sets date & time related variables.
list ($TimeIsSet, $Date, $Time, $EpochTime) = verifyTime();
if (!$TimeIsSet or !$Date or !$Time) die('ERROR!!! HRConvert2-4: Could not verify timezone!');

// / The following code verifies that the installation is valid.
list ($InstallationIsVerified, $ConfigFile, $Version, $CoreLoaded) = verifyInstallation();
if (!$InstallationIsVerified) die('ERROR!!! '.$Time.', HRConvert2-5: Could not verify installation!');

// / The following code verifies that string inputs to the core are properly sanitized.
list ($InputsAreVerified, $ShowGUI, $GUI, $Color, $Language, $Token1, $Token2, $Height, $Width, $Rotate, $Bitrate, $Method, $Download, $UserFilename, $UserExtension, $FilesToArchive, $PDFWorkSelected, $ConvertSelected, $FilesToScan, $FilesToDelete, $UserScanType) = verifyInputs();
if (!$InputsAreVerified) die('ERROR!!! '.$Time.', '.$ApplicationName.'-6: Could not verify inputs!');

// / The following code verifies enough user information to generate a unique session identifier.
list ($SessionIsVerified, $IP, $HashedUserAgent) = verifySession();
if (!$SessionIsVerified) die('ERROR!!! '.$Time.', '.$ApplicationName.'-7: Could not verify session!');

// / The following code verifies the tokens supplied by the user, if any.
// / If no tokens were supplied by the user, generate new ones.
list ($TokensAreValid, $Token1, $Token2) = verifyTokens($Token1, $Token2);

// / The following code generates a series of unique session identifiers.
list ($SesHashIsVerified, $SesHash, $SesHash2, $SesHash3, $SesHash4) = verifySesHash($Token1);
if (!$SesHashIsVerified) die('ERROR!!! '.$Time.': '.$ApplicationName.'-8: Could not verify unique session identifier!');

// / The following code verifies the logging environment.
list ($LogFileExists, $LogFile, $ClamLogFile) = verifyLogs();
if (!$LogFileExists) die('ERROR!!! '.$Time.', '.$ApplicationName.'-9, '.$SesHash3.': Could not verify logging environment!');
if ($Verbose) logEntry('Verified logging environment.');

// / The following code verifies & sanitizes global variables for the session.
list ($GlobalsAreVerified) = verifyGlobals();
if (!$GlobalsAreVerified) errorEntry('Could not verify globals!', 11, TRUE);
else if ($Verbose) logEntry('Verified globals.');

// / The following code decides if the security context being attempted matches a valid CLI or web request.
// / Error 27 should not be possible & should never be able to fire. If it does something is seriously wrong.
list($CommandLineHandled, $UserType) = parseCommandLine();
if ($CommandLineHandled && $UserType === 'web') errorEntry('Could not verify user type!', 27, TRUE);
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
    if (!isset($_POST['filesToArchive']) && !isset($_POST['convertSelected']) && !isset($_POST['pdfworkSelected']) && !isset($_POST['download']) && !isset($_POST['upload']) && !isset($_POST['filesToScan'])) {

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
      if (isset($_POST['filesToArchive'])) {
        logEntry('Initiating Archiver.');
        list ($ArchiveComplete, $ArchiveErrors) = archiveFiles($FilesToArchive, $UserFilename, $UserExtension);
        if (!$ArchiveComplete) errorEntry('Archive Failed!', 20, TRUE);
        if ($ArchiveErrors) logEntry('Archive finished with errors.');
        if ($Verbose) logEntry('Archive Complete.'); }

      // / The following code is performed when a user converts a selection of files.
      if (isset($_POST['convertSelected'])) {
        list ($BudgetWasApproved, $BudgetToken) = requestConversionBudget($DefaultConversionCost, $DefaultExpectedRuntime);
        if (!$BudgetWasApproved) {
          warningEntry('A conversion was refused because the server is at its resource budget.');
          print($Alert3.$Lol); }
        else {
          logEntry('Initiating Converter.');
          list ($ConversionComplete, $ConversionErrors) = convertFiles($ConvertSelected, $UserFilename, $UserExtension, $Height, $Width, $Rotate, $Bitrate);
          if (!$ConversionComplete) errorEntry('Conversion Failed!', 21, TRUE);
          if ($ConversionErrors) logEntry('Conversion finished with errors.');
          if ($Verbose) logEntry('Conversion Complete.'); }
        releaseConversionBudget($BudgetToken); }

      // / The following code is performed when a user performs OCR on a selection of files.
      if (isset($_POST['pdfworkSelected'])) {
        logEntry('Initiating Converter.');
        list ($ConversionComplete, $ConversionErrors) = ocrFiles($PDFWorkSelected, $UserFilename, $UserExtension, $Method);
        if (!$ConversionComplete) errorEntry('OCR Operation Failed!', 22, TRUE);
        if ($ConversionErrors) logEntry('OCR Operation finished with errors.');
        if ($Verbose) logEntry('Conversion Complete.'); }

      // / The following code is performed when a user performs a virus scan on a selection of files.
      if (isset($_POST['filesToScan']) && $AllowUserVirusScan) {
        logEntry('Initiating User Virus Scannner.');
        list ($ScanComplete, $ScanErrors, $UserVirusFound) = userVirusScan($FilesToScan, $UserScanType);
        if (!$ScanComplete) errorEntry('User Virus Scan Failed!', 23, TRUE);
        if ($UserVirusFound) logEntry('The User Virus Scan detected infected files.');
        if (!$UserVirusFound) logEntry('The User Virus Scan did not detect any infected files.');
        if ($ScanErrors) logEntry('User Virus Scan finished with errors.');
        if ($Verbose) logEntry('User Virus Scan Complete.'); }

      // / Close the web server connection after all required content has been served.
      if ($Verbose) logEntry('Closing connection.');
      closeHRC2Connection();

      // / Nothing below this point may produce any output.
      // / The user has already been served & the connection is already closed.
      // / If a user still has a pending stream open, keep running to monitor the FFMPEG process.
      if ($WaitForStream && $StreamPID > 0) {
        logEntry('Waiting up to '.$StreamWatchTimeout.' minutes for the user to watch the stream.');
        list ($StreamCompleted, $StreamKilled, $ElapsedSeconds) = waitForStream($StreamPID, $StreamOutputPath);
        if ($StreamKilled) logEntry('The users stream was killed.');
        if ($StreamCompleted) logEntry('The users stream has completed after '.$ElapsedSeconds.' seconds.'); } } } }

// / Stop execution of the application.
die();
// / -----------------------------------------------------------------------------------
?>