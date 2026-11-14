<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRProprietary Kernel, Copyright on 10/31/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / Application Information ...
// / The HRProprietary Kernel is the code every application built on the HRProprietary Engine
// / runs before anything else. It logs, halts, shreds memory, sanitizes input, reads the
// / command line, finds executables & loads components, the Engine among them. It keeps the
// / install secret & derives a visitor's session from it.
// /
// / File Information ...
// / v4.2.0.
// / The kernel. An application's entry point requires it first & pins it exactly via $RequiredKernelVersion.
// / Error 35000 is the direct load guard every component shares.
// / It is a file of its own, so every application runs identical kernel code & compares it by version.
// / It sits beside the Engine, not inside it, because it loads the Engine & must log when the Engine fails.
// / Check nineteen of Documentation/Build/hrconvert2-check.py enforces three rules.
// /   The kernel calls nothing an application defines, so it is identical in every one.
// /   The kernel calls nothing the Engine defines without a function_exists guard, because it runs first.
// /   Nothing here is guarded against redefinition, because a replaced kernel function is no longer the kernel.
// / What an application provides.
// /   $EnableMemoryProtection  Whether variables are shredded at every return.
// /   $Verbose                 Whether ordinary activity is logged.
// /   $InstLoc                 The application root. Components are found beneath it.
// /   $ApplicationName         The name an operator sees in warning & error lines.
// /   $RunningFromCLI          Whether there is a terminal rather than a web client.
// /   $RunningAsRoot           Whether this process is root. Only root prepares a root folder.
// /   $CoreLoaded              Set TRUE by the application before it loads any component.
// /   $LogFile                 The logfile. Until it exists, lines are buffered rather than lost.
// /                            An application has verifyLogs decide & open it, sets it itself, or
// /                            decides the folder & the name & has openLogFileForRoot or
// /                            openLogFileByName open it & set it.
// /   $LogHandle               Optional. An open handle written through rather than by name.
// /                            Root holds one, standard error until it has a log folder of
// /                            its own, & nothing else needs to. openLogFileForRoot sets it.
// /   $SesHash3                Optional. A session label for every log line. Absent reads as
// /                            No Session, which is correct for an application without sessions.
// / What the install secret reads besides, from v4.1.9. establishInstallSecret says how each is used.
// /   $CurrentUser             The account this process runs as.
// /   $ApacheUser              The web server account. It reads the install secret & changes none of it.
// /   $DataRootLoc             The data location. The install secret is kept below it.
// /   $SecretLoc               Secrets, the folder in the data location the install secret is kept in.
// /   $HostedLoc               The folder the web server serves.
// /   $ProprietaryLoc          The HRProprietary folder, when the hosted location is inside it.
// /   $AdditionalConvertLocs   The additional data locations.
// /   $RequiredSecretVersion   The version of secret file the application accepts. An exact match.
// /   $SecretFile              The file the secret this process holds was read from.
// /                            The application sets it from what establishInstallSecret returns.
// /   $SecretKey               The secret this process holds, set the same way.
// / What a session reads besides.
// /   $Date                    The date verifyTime sets. A session belongs to one day.
// /   $UniqueDailyLogHash      Whether the logfile suffix changes every day.
// / What verifyLogs reads besides, to decide the logfile.
// /   $ConvertLoc              The data location. Logs inside it is used when $LogDir cannot be made.
// /   $LogDir                  The folder an account other than root logs to. It is set to the folder in use.
// /   $MaxLogSize              The largest size a logfile may reach. 1048576 when it is not a number.
// /   $AppendLogHashToLogFiles Whether $SesHash4 ends every logfile's name.
// /   $SesHash4                The suffix that setting appends.
// /   $EngineDir               The folder root logs to, which only root can change.
// /   $LogRole                 Optional. The role a logfile is named for. Decided from the command when empty.
// /   $LogWorkerIdentifier     Optional. Names a worker's logfile when workers have their own.
// /   $EnginePerManagerLogFiles & $EnginePerWorkerLogFiles come from the Engine configuration.
// /                            Each gives a manager or a worker a logfile of its own.
// / The time globals are set by verifyTime, & $DefaultLogDir & $DefaultLogSize by verifyLogs.
// / The log buffer & sequence are managed here, & so is $SecretPlacesWereOpen, which the install secret's functions set & read.
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A kernel may only be loaded by an application.
// / A direct request for this file over the web is the only way it can be the first file
// / included, so counting included files tells the two apart without the kernel needing to
// / know any application's name, & without a shared flag for every application to set.
if (count(get_included_files()) < 2) die('ERROR!!! Kernel-35000, The kernel cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The version of this kernel.
// / The application reads it with a pattern BEFORE requiring this file, so a mismatched
// / kernel is never executed. It cannot use readComponentVersion for that, which is below.
$KernelVersion = 'v4.2.0';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to give the absolute path of one file in the application's contract.
// / Accepts the file's name. Returns its absolute path, whether or not it exists.
// / The contract is the application's identity, dependency manifest & configuration template.
// / It lives at Resources/Contract, beside the Kernel & the Engine, because it is the application's own data.
// / Every reader asks here, & the kernel is the one layer every reader can reach, -v with no Engine included.
// / The name is reduced to its last part, so a caller cannot read anything outside the contract.
function contractPath($contractFileName) {
  // / Set variables.
  global $InstLoc, $EnableMemoryProtection;
  $ContractPath = rtrim((string)$InstLoc, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'Contract'.DIRECTORY_SEPARATOR.basename((string)$contractFileName);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $contractFileName);
  return $ContractPath; }
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
// / A function to sanitize input strings with varying degrees of tolerance.
// / It removes , | \ ~ # [ ] ( ) { } ; : $ ! ^ & % @ > * ? < " ' ` tabs, line breaks & NUL characters.
// / It also removes http://, https://, // & .., & replaces each space with an underscore.
// / Dashes & dots are trimmed from both ends.
// / Set $strict to TRUE to remove forward slashes as well.
function sanitizeString($Variable, $strict) {
  // / Set variables.
  global $EnableMemoryProtection;
  // / It defines & destroys its own pattern on every call, rather than using the global $DangerousFiles.
  // / A dangerous extension is removed as a whole part of a name, wherever in the name it sits.
  // / A part begins with a dot & ends at the next dot or the end, so a.php.txt loses .php & a.json keeps .json.
  // / Case is ignored, so a.PHP loses its extension too.
  // / Every extension a web server may hand to PHP is one, php5, phps & phtml among them.
  // / Dashes inside a name are left, & dashes at either end are trimmed below.
  $dangerParts = '/\.(?:js|php[0-9]*|phps|phtml|phar|html|css)(?=\.|$)/i';
  $previousValue = '';
  // / Check for dangerous files or escape conditions, until removing one leaves none behind.
  do {
    $previousValue = (string)$Variable;
    $Variable = preg_replace($dangerParts, '', str_replace('..', '', (string)$Variable)); }
  while ((string)$Variable !== $previousValue);
  if ($strict) $Variable = trim(trim(str_replace(' ', '_', str_replace('..', '', str_replace('//', '', str_replace(str_split(',|\\~#[](){};:$!#^&%@>*?<"\'/`'.chr(9).chr(10).chr(13).chr(0)), '', str_replace('http://', '', str_replace('https://', '', $Variable))))))), '-');
  if (!$strict) $Variable = trim(trim(str_replace(' ', '_', str_replace('..', '', str_replace('//', '', str_replace(str_split(',|\\~#[](){};:$!#^&%@>*?<"\'`'.chr(9).chr(10).chr(13).chr(0)), '', str_replace('http://', '', str_replace('https://', '', $Variable))))))), '-');
  // / Removing a character can join up a dangerous extension, & so can trimming, as a.php-. becomes a.php.
  // / So the checks & the trimming of dashes & dots from both ends repeat until neither changes anything.
  // / A name then begins with neither a dash, which a program reads as an option, nor a dot.
  do {
    $previousValue = (string)$Variable;
    $Variable = trim((string)preg_replace($dangerParts, '', str_replace('..', '', (string)$Variable)), '-.'); }
  while ((string)$Variable !== $previousValue);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $strict, $dangerParts, $previousValue);
  return $Variable; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to sanitize input strings or arrays with varying degrees of tolerance.
// / It hands each string, number or array element to sanitizeString(), which says what is removed.
// / Set $strict to TRUE to remove forward slashes as well.
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
    // / When $strict is TRUE this also removes forward slashes.
    if (is_array($Variable)) foreach ($Variable as $key => $var) $Variable[$key] = sanitizeString($Variable[$key], $strict);
    // / Sanitize string & numeric inputs.
    // / When $strict is TRUE this also removes forward slashes.
    if (is_string($Variable) or is_numeric($Variable)) $Variable = sanitizeString($Variable, $strict);
    // / Only set the Sanitized flag to TRUE if we have taken action on this variable.
    $VariableIsSanitized = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $strict, $key, $var);
  return array($Variable, $VariableIsSanitized); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to give a variable a second value without leaving the first one behind.
// / Call this as redeclare($targetVariable, $newValue), & never assign the result back over the target.
// / The target is taken by reference, shredded, & only then given the new value.
// / Returns TRUE when the old value was destroyed before the new one landed, per convention 8.
// / A FALSE return means the new value is in place but the old one may not be gone.
// / It is a deliberate exception to convention 6, for a loop body, where a distinct name per value is impossible.
// / Straight line code uses a second variable name instead, because that costs nothing & documents itself.
// / An interned string, such as a literal, cannot be shredded, while a value built at runtime can.
// / A copy held by another variable is not reached, because PHP separates the buffer on write.
// / Each call costs one debug_backtrace() through the purge routine.
function redeclare(&$targetVariable, $newValue) {
  // / Set variables.
  global $EnableMemoryProtection;
  $VariableIsRedeclared = FALSE;
  $oldValueWasPurged = FALSE;
  // / Shred whatever the target holds before anything overwrites it.
  // / The target is a reference to the caller's variable, so this writes into the real buffer.
  // / The target is shredded, never $newValue, because purging $newValue would empty the variable being set.
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
// / It belongs to the kernel, because purgeSensitiveMemory() calls it & runs before the Engine loads.
// / It cleans up after itself by hand, because purgeSensitiveMemory() would call it again.
// / Accepts no arguments & returns the name of the first caller that is not a memory routine.
// / Frames belonging to the memory routines are skipped deliberately.
// / A caller that reached the purge through redeclare() still owns the data & is the one named.
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
      // / A resource cannot be shredded, so dropping the reference is the right outcome & is not reported.
      // / A socket handle holds no bytes this process can overwrite, & every manager pass closes several.
      // / Any other type reaching here is reported, because an object may hold a value in a property this never reached.
      if ($loggingIsReady && $Verbose && strpos(gettype($variable), 'resource') === FALSE) logEntry('Dropped unshreddable reference type '.gettype($variable).' for the '.$callerName.' function.');
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
        else error_log('WARNING!!! '.applicationLabel().': '.$failureText.' Continuing...'); }
      // / Log a fatal failure where it can be logged, then always report it to the page.
      // / errorEntry() is given a FALSE die flag, so control returns here for the print & the close.
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
// / A function to close the web server connection to the client.
// / Accepts nothing. Returns nothing, & it is exempt from the single exit rule for that reason.
// / It is named for what it does, so no application's name sits inside the kernel.
// / A buffer zlib.output_compression has begun to compress can be flushed but not removed, so it is flushed.
function closeClientConnection() {
  ignore_user_abort(TRUE);
  if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
  else {
    if (ob_get_level() > 0 && (ob_get_status()['flags'] & PHP_OUTPUT_HANDLER_REMOVABLE)) ob_end_flush();
    else if (ob_get_level() > 0 && (ob_get_status()['flags'] & PHP_OUTPUT_HANDLER_FLUSHABLE)) ob_flush();
    flush(); } }
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
// / A function to build one formatted log line & give it a sequence number.
// / Accepts the tier label, the message & an error label which may be empty.
// / Returns the finished line.
// / The sequence number starts at zero when the process starts & counts every entry.
// / A gap in that sequence is the only evidence that an entry was lost.
// / Two entries stamped with the same second are otherwise impossible to order.
// / This function does NOT call purgeSensitiveMemory, & neither may its callers.
// / purgeSensitiveMemory calls logEntry, so a purge here would recurse forever.
function composeLogEntry($tierLabel, $entry, $errorLabel) {
  // / Set variables.
  global $Time, $SesHash3, $LogSequence;
  $ComposedEntry = $timeLabel = $sessionLabel = $sequenceLabel = '';
  // / The timestamp is recomputed here rather than taken from $Time.
  // / $Time is set once during boot, which is right for a request that lives half a second
  // / & wrong for a manager process that runs for weeks.
  $timeLabel = (isset($Time) && $Time !== '') ? date('F j, Y, g:i a') : 'Unknown Time';
  $sessionLabel = (isset($SesHash3) && $SesHash3 !== '') ? (string)$SesHash3 : 'No Session';
  if (!isset($LogSequence) or !is_numeric($LogSequence)) $LogSequence = 0;
  $sequenceLabel = str_pad((string)(int)$LogSequence, 5, '0', STR_PAD_LEFT);
  $LogSequence = (int)$LogSequence + 1;
  // / The tier arrives carrying the separator it has always used & none is added here.
  // / Op-Act is followed by a comma & the two loud tiers are followed by a space.
  // / Adding one regardless produced a line beginning  Op-Act, , 00000  which is a comma
  // / separating a label from nothing at all.
  if ((string)$errorLabel === '') $ComposedEntry = $tierLabel.$sequenceLabel.', '.$timeLabel.', '.$sessionLabel.': '.$entry;
  else $ComposedEntry = $tierLabel.$sequenceLabel.', '.$timeLabel.', '.$errorLabel.', '.$sessionLabel.': '.$entry;
  // / This cleanup is manual, because purgeSensitiveMemory calls into this path.
  $tierLabel = $entry = $errorLabel = $timeLabel = $sessionLabel = $sequenceLabel = NULL;
  unset($tierLabel, $entry, $errorLabel, $timeLabel, $sessionLabel, $sequenceLabel);
  return $ComposedEntry; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to append text to the logfile, through the held handle when there is one.
// / Accepts the text, which already ends in a line break. Returns TRUE when all of it was written.
// / emitLogEntry & quickDie both write here, so a process holding $LogHandle never writes
// / its logfile by name from either. Like emitLogEntry it does NOT call purgeSensitiveMemory,
// / because purgeSensitiveMemory logs through this path.
// / A logfile is locked & written at its end, because several processes append to one file.
// / A handle that is not a plain file is written without a lock, because a lock on a socket always fails.
// / Root's handle is standard error until it has a log folder, & under systemd standard error is a socket.
function appendLogText($logText) {
  // / Set variables.
  global $LogFile, $LogHandle;
  $TextWasWritten = $handleIsFile = FALSE;
  $handleStatus = array();
  if (isset($LogHandle) && is_resource($LogHandle)) {
    $handleStatus = @fstat($LogHandle);
    $handleIsFile = (is_array($handleStatus) && ((int)$handleStatus['mode'] & 0170000) === 0100000);
    if (!$handleIsFile) {
      $TextWasWritten = (@fwrite($LogHandle, (string)$logText) === strlen((string)$logText));
      @fflush($LogHandle); }
    else if (@flock($LogHandle, LOCK_EX)) {
      @fseek($LogHandle, 0, SEEK_END);
      $TextWasWritten = (@fwrite($LogHandle, (string)$logText) === strlen((string)$logText));
      @fflush($LogHandle);
      @flock($LogHandle, LOCK_UN); } }
  else if (isset($LogFile) && is_string($LogFile) && $LogFile !== '') $TextWasWritten = (@file_put_contents($LogFile, (string)$logText, FILE_APPEND | LOCK_EX) !== FALSE);
  // / This cleanup is manual, because purgeSensitiveMemory calls into this path.
  $logText = $handleStatus = $handleIsFile = NULL;
  unset($logText, $handleStatus, $handleIsFile);
  return $TextWasWritten; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to repeat a warning or an error on the terminal of an administrator running root.
// / Accepts one finished line. Returns TRUE when the line was repeated.
// / Root logs to a file in Engine, so an administrator running it by hand would otherwise see
// / none of its warnings until the file was opened. openLogFileForRoot sets $LogEchoToConsole only
// / for root writing that file with standard error on a terminal, so a timer, a pipe, a redirect & a
// / build repeat nothing & every other account is untouched. Op-Act lines are never repeated.
// / Like emitLogEntry it does NOT call purgeSensitiveMemory, because purgeSensitiveMemory logs
// / through this path.
function echoLogEntryToConsole($composedEntry) {
  // / Set variables.
  global $LogEchoToConsole;
  $EntryWasEchoed = $entryIsLoud = FALSE;
  if (isset($LogEchoToConsole) && $LogEchoToConsole === TRUE && defined('STDERR')) {
    $entryIsLoud = (strpos((string)$composedEntry, 'WARNING!!! ') === 0 or strpos((string)$composedEntry, 'ERROR!!! ') === 0);
    if ($entryIsLoud) $EntryWasEchoed = (@fwrite(STDERR, (string)$composedEntry.PHP_EOL) !== FALSE); }
  // / This cleanup is manual, because purgeSensitiveMemory calls into this path.
  $composedEntry = $entryIsLoud = NULL;
  unset($composedEntry, $entryIsLoud);
  return $EntryWasEchoed; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to record one finished line, or to hold it until a logfile exists.
// / Accepts the finished line. Returns TRUE when it reached the logfile.
// / An entry raised before verifyLogs is held in a buffer & written in order the moment a logfile exists.
// / Nothing before the logfile is therefore lost, & nothing is written out of order.
// / LOCK_EX is used, because several processes append to one file.
// / An append under the pipe buffer size is atomic without it, but a stream inspection dump or a compiler log is not.
// / This function does not call purgeSensitiveMemory, for the reason given above.
// /
// / A process holding $LogHandle writes through it & never by name.
// / A logfile in a folder the web server account owns can be swapped for a link, & a write by name follows it.
// / Root therefore writes nothing in that folder, & its handle is standard error until it has a log folder.
// / A handle to a logfile is locked & moved to the end before every entry, so lines land whole & in order.
function emitLogEntry($composedEntry) {
  // / Set variables.
  global $LogFile, $LogHandle, $LogBuffer, $LogBufferOverflowed;
  $EntryWasWritten = $logIsUsable = $handleIsUsable = FALSE;
  $bufferedEntry = $pendingText = $overflowNotice = '';
  if (!isset($LogBuffer) or !is_array($LogBuffer)) $LogBuffer = array();
  $handleIsUsable = (isset($LogHandle) && is_resource($LogHandle));
  $logIsUsable = ($handleIsUsable or (isset($LogFile) && is_string($LogFile) && $LogFile !== '' && file_exists($LogFile)));
  if (!$logIsUsable) {
    // / The buffer is bounded. A process that never reaches a logfile must not grow until
    // / it is killed for memory, & the entries that explain a boot failure are the early
    // / ones rather than the thousandth.
    if (count($LogBuffer) < 512) array_push($LogBuffer, $composedEntry);
    else $LogBufferOverflowed = TRUE; }
  else {
    // / Anything held before the logfile existed is written first & in order.
    // / A full buffer keeps its earliest entries & drops later ones, so the notice says later entries were discarded.
    foreach ($LogBuffer as $bufferedEntry) $pendingText .= $bufferedEntry.PHP_EOL;
    if (isset($LogBufferOverflowed) && $LogBufferOverflowed) {
      $overflowNotice = 'WARNING!!! The log buffer filled before a logfile existed & later entries were discarded.';
      $pendingText .= $overflowNotice.PHP_EOL; }
    $pendingText .= $composedEntry.PHP_EOL;
    $EntryWasWritten = appendLogText($pendingText);
    // / The warnings & errors among them are repeated on the terminal of an administrator
    // / running root by hand. echoLogEntryToConsole decides that, & repeats nothing otherwise.
    foreach ($LogBuffer as $bufferedEntry) echoLogEntryToConsole($bufferedEntry);
    if ($overflowNotice !== '') echoLogEntryToConsole($overflowNotice);
    echoLogEntryToConsole($composedEntry);
    $LogBuffer = array();
    $LogBufferOverflowed = FALSE; }
  // / This cleanup is manual, because purgeSensitiveMemory calls into this path.
  $composedEntry = $logIsUsable = $handleIsUsable = $bufferedEntry = $pendingText = $overflowNotice = NULL;
  unset($composedEntry, $logIsUsable, $handleIsUsable, $bufferedEntry, $pendingText, $overflowNotice);
  return $EntryWasWritten; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write everything held in the buffer, now that a logfile exists.
// / Accepts nothing. Returns the number of entries written.
// / openLogFileForRoot & openLogFileByName call this the moment there is a usable file, so a
// / boot that logged before the logfile existed still lands in the logfile rather than in the
// / server error log.
function flushLogBuffer() {
  // / Set variables.
  global $LogBuffer;
  $EntriesFlushed = 0;
  if (isset($LogBuffer) && is_array($LogBuffer)) $EntriesFlushed = count($LogBuffer);
  if ($EntriesFlushed > 0) emitLogEntry(composeLogEntry('Op-Act, ', 'Log buffer flushed. '.$EntriesFlushed.' entry or entries were held before a logfile existed.', ''));
  return $EntriesFlushed; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to find the logfile a process should be writing now, in one folder.
// / Accepts the folder, the part of a logfile's name before its count, the part after it & the largest size a logfile may reach.
// / Returns the logfile's path.
// / A logfile is named prefix_count_suffix.txt, & the count starts at zero.
// / A logfile past the largest size is left behind & the next count is tried, so a day's log grows by files rather than without limit.
// / Every logfile name is built here & nowhere else.
function findCurrentLogFile($logFolder, $namePrefix, $nameSuffix, $maximumSize) {
  // / Set variables.
  global $EnableMemoryProtection;
  $LogPath = '';
  $logIncrement = -1;
  // / Each name tried replaces the last through redeclare(), so the name of a full logfile is shredded rather than left behind.
  do {
    $logIncrement++;
    redeclare($LogPath, str_replace('..', '', $logFolder.'/'.$namePrefix.'_'.$logIncrement.'_'.$nameSuffix.'.txt')); }
  while (is_file($LogPath) && filesize($LogPath) > $maximumSize);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $logIncrement, $logFolder, $namePrefix, $nameSuffix, $maximumSize);
  return $LogPath; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to open the logfile root writes, or standard error when that cannot be opened.
// / Accepts the folder root logs to, the part of a logfile's name before its count, the part after it & the largest size a logfile may reach.
// / Returns whether root has somewhere to log & where that is, in that order.
// / It sets $LogHandle & $LogFile, which every entry is written through, & $LogEchoToConsole.
// / The folder must be one only root can change, because a folder another account can change can be swapped for a link.
// / The logfile is opened through openFileWithoutFollowing(), which follows no link of either kind, & is created 0600.
// / Until the folder exists, before -fp has run after an upgrade, root writes to standard error, which a terminal shows & systemd journals.
// / A folder that exists with a logfile in it that cannot be opened is reported, because that is not the upgrade case.
// / Warnings & errors are repeated on standard error only when it is a terminal, so a timer, a pipe, a redirect & a build record them in the file alone.
// / Standard error as the log is never repeated to, because it is the terminal already.
function openLogFileForRoot($rootLogFolder, $namePrefix, $nameSuffix, $maximumSize) {
  // / Set variables.
  global $LogFile, $LogHandle, $LogEchoToConsole, $EnableMemoryProtection;
  $LogIsOpen = $fileWasCreated = FALSE;
  $LogPath = $candidatePath = $openNotice = '';
  $fileHandle = $errorHandle = FALSE;
  $LogEchoToConsole = FALSE;
  if (is_string($rootLogFolder) && $rootLogFolder !== '' && is_dir($rootLogFolder)) {
    $candidatePath = findCurrentLogFile($rootLogFolder, $namePrefix, $nameSuffix, $maximumSize);
    list ($fileHandle, $fileWasCreated, $openNotice) = openFileWithoutFollowing($candidatePath, 0600);
    if (!is_resource($fileHandle)) warningEntry('Root could not open its logfile, so it logs to standard error instead. '.$openNotice);
    else {
      $LogHandle = $fileHandle;
      $LogPath = $candidatePath;
      $LogIsOpen = TRUE;
      $LogEchoToConsole = (defined('STDERR') && function_exists('stream_isatty') && @stream_isatty(STDERR)); } }
  if (!$LogIsOpen) {
    $errorHandle = defined('STDERR') ? STDERR : @fopen('php://stderr', 'w');
    if (is_resource($errorHandle)) {
      $LogHandle = $errorHandle;
      $LogPath = 'php://stderr';
      $LogIsOpen = TRUE; } }
  $LogFile = $LogPath;
  // / Anything logged before this point was held rather than written, so it is written now.
  if ($LogIsOpen) flushLogBuffer();
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $fileHandle & $errorHandle are second names for $LogHandle, so clearing them leaves the global open.
  purgeSensitiveMemory($EnableMemoryProtection, $fileWasCreated, $candidatePath, $openNotice, $fileHandle, $errorHandle, $rootLogFolder, $namePrefix, $nameSuffix, $maximumSize);
  return array($LogIsOpen, $LogPath); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to open the logfile an account other than root writes, by name, in a folder of its own.
// / Accepts the folders it may log to in order of preference, a file to place in the folder it uses, the part of a logfile's name before its count, the part after it & the largest size a logfile may reach.
// / Returns whether there is a logfile, its path & the folder it is in, in that order.
// / It sets $LogFile, which every entry is written to, & clears $LogEchoToConsole.
// / A missing folder is made 0700 & a new logfile 0600, so only the account that writes them can read them.
// / A folder that cannot be made is passed over for the next one, & that is reported once a logfile exists.
// / The file placed in the folder keeps a web server from listing it, & an empty name places nothing.
// / Root never calls this, because root does not open a file by name in a folder another account can change.
function openLogFileByName($logFolders, $guardFile, $namePrefix, $nameSuffix, $maximumSize) {
  // / Set variables.
  global $LogFile, $LogEchoToConsole, $Time, $EnableMemoryProtection;
  $LogIsOpen = $headerWasWritten = FALSE;
  $LogPath = $LogFolder = $candidateFolder = $guardName = '';
  $LogEchoToConsole = FALSE;
  // / A folder is tried only until one is usable, so the first that is usable is the one used.
  foreach ((array)$logFolders as $candidateFolder) {
    if ($LogFolder !== '' or !is_string($candidateFolder) or $candidateFolder === '') continue;
    if (!is_dir($candidateFolder)) @mkdir($candidateFolder, 0700);
    if (is_dir($candidateFolder)) $LogFolder = $candidateFolder;
    else warningEntry('The log folder '.$candidateFolder.' could not be made, so the next one is tried.'); }
  if ($LogFolder !== '') {
    // / The file is placed only where it is missing.
    $guardName = basename((string)$guardFile);
    if ($guardName !== '' && !file_exists($LogFolder.'/'.$guardName)) @copy($guardFile, $LogFolder.'/'.$guardName);
    $LogPath = findCurrentLogFile($LogFolder, $namePrefix, $nameSuffix, $maximumSize);
    if (!file_exists($LogPath)) {
      $headerWasWritten = (file_put_contents($LogPath, 'Op-Act, '.$Time.': Logfile created using method 1.'.PHP_EOL, FILE_APPEND) !== FALSE);
      if ($headerWasWritten) @chmod($LogPath, 0600); }
    $LogIsOpen = file_exists($LogPath);
    if (!$LogIsOpen) warningEntry('The logfile '.$LogPath.' could not be created.'); }
  $LogFile = $LogPath;
  // / Anything logged before this point was held rather than written, so it is written now.
  if ($LogIsOpen) flushLogBuffer();
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $headerWasWritten, $candidateFolder, $guardName, $logFolders, $guardFile, $namePrefix, $nameSuffix, $maximumSize);
  return array($LogIsOpen, $LogPath, $LogFolder); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to open the logfile this process writes.
// / Accepts nothing. Returns whether a logfile is in use & its path.
// / It decides what the logfile is, which is the role it is named for, the folder it is in & the rest of its name.
// / openLogFileForRoot() or openLogFileByName() opens it, & findCurrentLogFile() names it in turn.
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
  // / The commands that name a role are the Engine's, which every application on the Engine shares.
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
// / A function to give the name every warning & error line is labelled with.
// / Accepts nothing. Returns the application's name, or HRConvert2 before a configuration names one.
// / The name is the branding an operator may rename, & it is what a person reads.
// / A line raised before any configuration is read is labelled HRConvert2, the name the kernel was written for.
// / Like the functions that log, it does NOT call purgeSensitiveMemory, because purgeSensitiveMemory reaches it.
function applicationLabel() {
  // / Set variables.
  global $ApplicationName;
  $ApplicationLabel = (isset($ApplicationName) && is_string($ApplicationName) && $ApplicationName !== '') ? $ApplicationName : 'HRConvert2';
  return $ApplicationLabel; }
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
  global $Time, $LogFile, $LogHandle, $SesHash3, $RunningFromCLI, $LogBuffer, $LogEchoToConsole;
  // / The timestamp is recomputed here, at the moment the entry is written, rather than taken from $Time.
  // / verifyTime sets $Time once, which suits a short request & not a manager that runs for weeks.
  // / $Time itself is left alone, because a session directory is named from it & must not change underneath a request.
  // / An empty $Time means verifyTime has not run, so no timezone is set & no time is known.
  $timeLabel = (isset($Time) && $Time !== '') ? date('F j, Y, g:i a') : 'Unknown Time';
  $sessionLabel = (isset($SesHash3) && $SesHash3 !== '') ? (string)$SesHash3 : 'No Session';
  $applicationLabel = applicationLabel();
  $bufferedEntry = '';
  $errorLabel = is_numeric($errorNumber) ? $applicationLabel.'-'.$errorNumber : $applicationLabel.'-###';
  // / A halt is numbered like everything else, so the sequence has no hole at the end.
  $formattedEntry = composeLogEntry('ERROR!!! ', $entry, $errorLabel);
  // / A log is usable on the terms emitLogEntry uses, a held handle or a logfile that exists.
  $logIsWritable = ((isset($LogHandle) && is_resource($LogHandle)) or (isset($LogFile) && is_string($LogFile) && $LogFile !== '' && file_exists($LogFile)));
  // / Root's log on the command line is the console itself, so whatever reaches that log has
  // / already reached the terminal. Printed as well, every line here appeared twice.
  $logIsConsole = (isset($LogFile) && $LogFile === 'php://stderr');
  // / Root's logfile repeats its warnings & errors on the terminal of an administrator running it
  // / by hand, so a halt is repeated there once rather than printed beside the repeat.
  $logIsEchoed = (isset($LogEchoToConsole) && $LogEchoToConsole === TRUE);
  // / On the command line PHP's own error log is standard error unless php.ini names somewhere else.
  // / An entry sent there would reach the terminal beside the copy printed below, so it is printed once instead.
  $errorLogIsConsole = ($RunningFromCLI && in_array(trim((string)ini_get('error_log')), array('', 'php://stderr', '/dev/stderr'), TRUE));
  // / Everything held before a logfile existed is reported here rather than discarded.
  // / A halt during boot is exactly the case where the entries leading up to it explain the
  // / halt, & exactly the case where there was nowhere to have written them.
  if (isset($LogBuffer) && is_array($LogBuffer) && count($LogBuffer) > 0) {
    foreach ($LogBuffer as $bufferedEntry) {
      if ($logIsWritable) appendLogText($bufferedEntry.PHP_EOL);
      else if (!$errorLogIsConsole) error_log($bufferedEntry);
      if (!$RunningFromCLI) print($bufferedEntry.PHP_EOL.'<br />'.PHP_EOL);
      else if ($logIsEchoed) echoLogEntryToConsole($bufferedEntry);
      else if (!$logIsConsole) print($bufferedEntry.PHP_EOL); }
    $LogBuffer = array(); }
  // / Record the halt wherever it can be recorded, because a halt with no record cannot be diagnosed.
  // / errorEntry leaves a fatal error to this function, so this is the one place a halt is recorded.
  if ($logIsWritable) appendLogText($formattedEntry.PHP_EOL);
  else if (!$errorLogIsConsole) error_log($formattedEntry);
  // / A browser needs a line break to render the message. A terminal does not & would
  // / print the tag literally. A log that is the console has shown it already.
  if (!$RunningFromCLI) print($formattedEntry.PHP_EOL.'<br />'.PHP_EOL);
  else if ($logIsEchoed) echoLogEntryToConsole($formattedEntry);
  else if (!$logIsConsole) print($formattedEntry.PHP_EOL);
  // / Close the connection deliberately rather than dropping it, so the message is
  // / delivered before the process ends. There is nothing to close on the command line.
  if (!$RunningFromCLI && function_exists('closeClientConnection')) closeClientConnection();
  // / A halt is a failure, so the process exits with status one.
  // / systemd restarts the listener only on a failure, & a die with a message exits with status zero.
  // / A web request is unaffected, because nothing sees the status & shutdown functions run either way.
  exit(1); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write normal operational activity to the logfile.
// / Accepts the message.
// / Returns TRUE when the entry was recorded.
// / Suppressed by the caller when $Verbose is FALSE. This function does not test $Verbose,
// / because a caller that wants an entry recorded regardless must be able to say so.
// / An entry raised before verifyLogs() is held until a logfile exists.
function logEntry($entry) {
  // / Set variables.
  // / The formatting & the writing live elsewhere, so this function holds nothing.
  $LogWritten = FALSE;
  $LogWritten = emitLogEntry(composeLogEntry('Op-Act, ', $entry, ''));
  // / This cleanup is manual, because purgeSensitiveMemory() calls this function.
  $entry = NULL;
  unset($entry);
  return $LogWritten; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write a warning to the logfile.
// / Accepts the message.
// / Returns TRUE when the entry was recorded.
// / A warning is always written & carries no error number. It can never halt execution.
// / An entry raised before verifyLogs() is held until a logfile exists.
function warningEntry($entry) {
  // / Set variables.
  // / The formatting & the writing live elsewhere, so this function holds nothing.
  $LogWritten = FALSE;
  $LogWritten = emitLogEntry(composeLogEntry('WARNING!!! ', $entry, ''));
  // / This cleanup is manual, because purgeSensitiveMemory() may call this function.
  $entry = NULL;
  unset($entry);
  return $LogWritten; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write a numbered error to the logfile & optionally halt.
// / Accepts the message, the documented error number & a fatality boolean, in that order.
// / Returns TRUE when the entry was recorded. Returns nothing at all when it halts.
// / An entry raised before verifyLogs() is held until a logfile exists.
// / A fatal error hands off to quickDie so every halt in the core is identical.
function errorEntry($entry, $errorNumber, $die) {
  // / Set variables.
  $LogWritten = FALSE;
  $applicationLabel = $errorLabel = '';
  $applicationLabel = applicationLabel();
  $errorLabel = is_numeric($errorNumber) ? $applicationLabel.'-'.$errorNumber : $applicationLabel.'-###';
  // / A fatal error is recorded, numbered & shown once, by quickDie, so it is not written here.
  if (!$die) $LogWritten = emitLogEntry(composeLogEntry('ERROR!!! ', $entry, $errorLabel));
  // / A fatal error halts here.
  // / quickDie records it, reports to the page or the console, empties the log buffer & closes
  // / the connection.
  if ($die) quickDie($entry, $errorNumber);
  // / This cleanup is manual, because purgeSensitiveMemory() may call this function.
  $entry = $errorNumber = $die = $applicationLabel = $errorLabel = NULL;
  unset($entry, $errorNumber, $die, $applicationLabel, $errorLabel);
  return $LogWritten; }
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
// / A function to read a command line argument vector into the parts a dispatcher needs.
// / Accepts the raw argument vector exactly as the interpreter supplied it, script name first.
// / Returns seven values in order: the arguments, their count, the command, two targets, a pre confirmed boolean & a sanity check.
// / The arguments are returned without the script name, & the count is taken after it is removed.
// / It parses & does not dispatch, so it holds no list of commands, which is why it can live here.
// / A target may follow an equal sign or arrive as the next argument, & both mean the same thing.
// / Every argument is lowercased, so a command line is case insensitive throughout.
// / A target is never a path, because every path & section name is read with extractCliOption, which lowercases nothing.
// / A consumer of a target that is a name compares without regard to case.
// / A consumer that may receive a path reads the raw vector, which this returns first & unchanged.
// / A -y or --yes anywhere in the vector pre confirms, & it is matched without regard to case.
function parseArgumentVector($argumentVector) {
  // / Set variables.
  global $EnableMemoryProtection;
  $VectorWasParsed = $ActionIsConfirmed = FALSE;
  $ParsedCommand = $ParsedTarget = $ParsedSecondTarget = '';
  $RemainingArguments = array();
  $ArgumentCount = 0;
  $firstArgument = '';
  $commandParts = array();
  $confirmationCandidate = '';
  // / An absent or malformed vector is not an error here. The caller decides what to do with
  // / nothing, because a web request legitimately has no command line at all.
  if (is_array($argumentVector)) {
    $RemainingArguments = $argumentVector;
    array_shift($RemainingArguments);
    $ArgumentCount = count($RemainingArguments);
    foreach ($RemainingArguments as $confirmationCandidate) {
      $confirmationCandidate = strtolower(trim((string)$confirmationCandidate));
      if ($confirmationCandidate === '-y' or $confirmationCandidate === '--yes') $ActionIsConfirmed = TRUE; }
    if ($ArgumentCount > 0) {
      $firstArgument = trim((string)$RemainingArguments[0]);
      $commandParts = explode('=', $firstArgument, 2);
      $ParsedCommand = strtolower(trim($commandParts[0]));
      if (isset($commandParts[1])) $ParsedTarget = strtolower(trim($commandParts[1]));
      else $ParsedTarget = isset($RemainingArguments[1]) ? strtolower(trim((string)$RemainingArguments[1])) : '';
      $ParsedSecondTarget = isset($RemainingArguments[2]) ? strtolower(trim((string)$RemainingArguments[2])) : ''; }
    $VectorWasParsed = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $firstArgument, $commandParts, $confirmationCandidate, $argumentVector);
  return array($RemainingArguments, $ArgumentCount, $ParsedCommand, $ParsedTarget, $ParsedSecondTarget, $ActionIsConfirmed, $VectorWasParsed); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read one of the application's own settings by the name a shared layer
// / knows it by.
// / Accepts the shared name & the value to use when the application maps nothing to it.
// / Returns the application's value, or the default.
// / The map is $EngineSettingNames in the application's contract. It names, for each shared
// / name, the setting the application keeps the value in, so a shared layer never has to
// / know what one application calls its settings & an operator's setting keeps its name.
// / It lives in the kernel because the Pipeline Core reads it, & the Pipeline Core still
// / answers -v when the Engine cannot load.
function applicationSetting($sharedName, $defaultValue) {
  // / Set variables.
  global $EngineSettingNames, $EnableMemoryProtection;
  $SettingValue = $defaultValue;
  $settingName = '';
  if (is_array($EngineSettingNames) && isset($EngineSettingNames[$sharedName]) && is_string($EngineSettingNames[$sharedName])) $settingName = $EngineSettingNames[$sharedName];
  if ($settingName !== '' && array_key_exists($settingName, $GLOBALS)) $SettingValue = $GLOBALS[$settingName];
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $settingName, $sharedName, $defaultValue);
  return $SettingValue; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to sanitize & return the extension to a specified file.
function getExtension($pathToFile) {
  // / Set variables.
  global $EnableMemoryProtection;
  $Pathinfo = '';
  $pathinfoCleaned = FALSE;
  list ($pathinfo, $pathinfoCleaned) = sanitize(pathinfo(strtolower($pathToFile), PATHINFO_EXTENSION), TRUE);
  if ($pathinfoCleaned) $Pathinfo = trim($pathinfo);
  else errorEntry('Could not process extension for file '.$pathToFile.'!', 300, FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $pathToFile, $pathinfoCleaned, $pathinfo);
  return $Pathinfo;  }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to describe a number of bytes the way a person reads one.
// / Accepts the number of bytes. Returns the description, such as 1.2 MB.
// / A kilobyte here is 1024 bytes, which is how most file managers count.
// / One decimal place is kept below ten of a unit & none above, because a glance needs no more.
// / The unit is worked out with a logarithm rather than by dividing in a loop, so no value is replaced while it is worked out.
// / The units are symbols rather than words, so every interface shows them as they are.
function describeByteCount($byteCount) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ByteDescription = '';
  $unitNames = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
  $wholeCount = $unitIndex = 0;
  $scaledCount = 0.0;
  $wholeCount = max(0, (int)$byteCount);
  // / A little is added before rounding down, because a logarithm can land a hair below a whole number & would report 1024 KB for a megabyte.
  if ($wholeCount >= 1024) $unitIndex = min(count($unitNames) - 1, (int)floor(log($wholeCount, 1024) + 0.000000001));
  $scaledCount = $wholeCount / pow(1024, $unitIndex);
  if ($unitIndex === 0) $ByteDescription = $wholeCount.' '.$unitNames[0];
  else $ByteDescription = number_format($scaledCount, ($scaledCount < 10) ? 1 : 0, '.', '').' '.$unitNames[$unitIndex];
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $unitNames, $wholeCount, $unitIndex, $scaledCount, $byteCount);
  return $ByteDescription; }
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
// / A function to decide whether root may trust a path.
// / Accepts the path. Returns a trust boolean, the resolved path & the reason it is not
// / trusted, in that order.
// /
// / Root must not run, read or write through anything another account can change.
// / An account that can write a folder can rename anything in it, a folder root owns included, & put something else in its place.
// / So every folder from the filesystem root down, & the path itself, must belong to root & be writable by nobody else.
// / There is no exception for a data folder, because a root owned folder inside one can be renamed aside & its name taken.
// /
// / The path is resolved first & the resolved path is the one judged & returned. A caller
// / uses the returned path rather than the one it supplied, so a link that changes after the
// / check cannot redirect what is done next. Every component is examined with lstat, so a
// / link that appeared after resolving is caught rather than followed.
function pathIsTrustedByRoot($candidatePath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $PathIsTrusted = FALSE;
  $ResolvedPath = $TrustNotice = '';
  $walkedPath = $pathComponent = '';
  $pathComponents = $componentStatus = array();
  if (trim((string)$candidatePath) !== '') $ResolvedPath = (string)@realpath((string)$candidatePath);
  if ($ResolvedPath === '') $TrustNotice = (string)$candidatePath.' does not exist.';
  else {
    $PathIsTrusted = TRUE;
    // / The empty first component is the filesystem root, which is judged like any other.
    $pathComponents = array_merge(array(''), array_values(array_filter(explode(DIRECTORY_SEPARATOR, $ResolvedPath), 'strlen')));
    foreach ($pathComponents as $pathComponent) {
      $walkedPath = rtrim($walkedPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$pathComponent;
      clearstatcache(TRUE, $walkedPath);
      $componentStatus = @lstat($walkedPath);
      if (!is_array($componentStatus)) $TrustNotice = $walkedPath.' could not be examined.';
      else if (((int)$componentStatus['mode'] & 0170000) === 0120000) $TrustNotice = $walkedPath.' is a link.';
      else if ((int)$componentStatus['uid'] !== 0) $TrustNotice = $walkedPath.' does not belong to root.';
      else if (((int)$componentStatus['mode'] & 0022) !== 0) $TrustNotice = $walkedPath.' can be written by an account other than root.';
      if ($TrustNotice !== '') {
        $PathIsTrusted = FALSE;
        break; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $walkedPath, $pathComponent, $pathComponents, $componentStatus, $candidatePath);
  return array($PathIsTrusted, $ResolvedPath, $TrustNotice); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether root may run code from a whole tree.
// / Accepts the tree & the folders inside it that hold no code, which are never entered.
// / Returns a trust boolean & the reason it is not trusted, in that order.
// /
// / A tree is only as trustworthy as the weakest name in it.
// / Root loads cores & pipelines on demand & runs build scripts from the tree, so one name another account can change runs its code.
// / Every folder from the filesystem root down to the tree must pass pathIsTrustedByRoot, & the tree must not be a link's target.
// / Every name inside it must belong to root & be writable by nobody else.
// / No name may be a link, because a link can lead out of the tree, & the walk uses lstat & never follows anything.
function treeIsTrustedByRoot($treePath, $excludedPaths) {
  // / Set variables.
  global $EnableMemoryProtection;
  $TreeIsTrusted = FALSE;
  $TrustNotice = $resolvedTree = $currentFolder = $folderEntry = $entryPath = '';
  $pendingFolders = $folderEntries = $entryStatus = array();
  $treePath = rtrim((string)$treePath, DIRECTORY_SEPARATOR);
  list ($TreeIsTrusted, $resolvedTree, $TrustNotice) = pathIsTrustedByRoot($treePath);
  if ($TreeIsTrusted && $resolvedTree !== $treePath) {
    $TreeIsTrusted = FALSE;
    $TrustNotice = $treePath.' leads to '.$resolvedTree.'.'; }
  if ($TreeIsTrusted) $pendingFolders = array($treePath);
  while ($TreeIsTrusted && count($pendingFolders) > 0) {
    $currentFolder = (string)array_pop($pendingFolders);
    $folderEntries = @scandir($currentFolder);
    if (!is_array($folderEntries)) $TrustNotice = $currentFolder.' could not be read.';
    else foreach ($folderEntries as $folderEntry) {
      if ($folderEntry === '.' or $folderEntry === '..') continue;
      $entryPath = $currentFolder.DIRECTORY_SEPARATOR.$folderEntry;
      if (in_array($entryPath, $excludedPaths, TRUE)) continue;
      $entryStatus = @lstat($entryPath);
      if (!is_array($entryStatus)) $TrustNotice = $entryPath.' could not be examined.';
      else if (((int)$entryStatus['mode'] & 0170000) === 0120000) $TrustNotice = $entryPath.' is a link.';
      else if ((int)$entryStatus['uid'] !== 0) $TrustNotice = $entryPath.' does not belong to root.';
      else if (((int)$entryStatus['mode'] & 0022) !== 0) $TrustNotice = $entryPath.' can be written by an account other than root.';
      else if (((int)$entryStatus['mode'] & 0170000) === 0040000) $pendingFolders[] = $entryPath;
      if ($TrustNotice !== '') break; }
    if ($TrustNotice !== '') $TreeIsTrusted = FALSE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $resolvedTree, $currentFolder, $folderEntry, $entryPath, $pendingFolders, $folderEntries, $entryStatus, $treePath, $excludedPaths);
  return array($TreeIsTrusted, $TrustNotice); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether only root could replace the folder a name is in.
// / Accepts the folder. Returns whether it is held & the reason it is not, in that order.
// /
// / A folder is only as still as the folder above it.
// / The functions below guard the name they are given, but reach it through its folder, which is looked up every time.
// / An account that can write the folder above can rename this one aside & put a link in its place.
// / So the folder must not be a link, & every folder above it must pass pathIsTrustedByRoot.
// / The folder itself may belong to another account, because the functions below guard every name inside it.
// / Only root is judged, because another account reaches nothing through a link it could not reach without one.
function folderIsHeldForRoot($folderPath) {
  // / Set variables.
  global $RunningAsRoot, $EnableMemoryProtection;
  $FolderIsHeld = FALSE;
  $HoldNotice = '';
  $callerIsRoot = $parentIsTrusted = FALSE;
  $parentPath = $resolvedParent = $trustNotice = '';
  $folderStatus = array();
  // / A process that cannot tell who it is is judged as root, because that is the safe guess.
  $callerIsRoot = function_exists('posix_geteuid') ? (posix_geteuid() === 0) : (!isset($RunningAsRoot) or $RunningAsRoot !== FALSE);
  $folderPath = rtrim((string)$folderPath, DIRECTORY_SEPARATOR);
  if ($folderPath === '') $folderPath = DIRECTORY_SEPARATOR;
  if (!$callerIsRoot) $FolderIsHeld = TRUE;
  else {
    // / The folder above is judged first. Once only root can change it, nothing else can swap
    // / the folder beneath it, so what lstat reports next stays true.
    $parentPath = dirname($folderPath);
    list ($parentIsTrusted, $resolvedParent, $trustNotice) = pathIsTrustedByRoot($parentPath);
    clearstatcache(TRUE, $folderPath);
    $folderStatus = @lstat($folderPath);
    if (!$parentIsTrusted) $HoldNotice = 'Another account could replace '.$folderPath.'. '.$trustNotice;
    else if ($resolvedParent !== $parentPath) $HoldNotice = $parentPath.' leads to '.$resolvedParent.'.';
    else if (!is_array($folderStatus)) $HoldNotice = $folderPath.' does not exist.';
    else if (((int)$folderStatus['mode'] & 0170000) === 0120000) $HoldNotice = $folderPath.' is a link.';
    else if (((int)$folderStatus['mode'] & 0170000) !== 0040000) $HoldNotice = $folderPath.' is not a folder.';
    else $FolderIsHeld = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $callerIsRoot, $parentIsTrusted, $parentPath, $resolvedParent, $trustNotice, $folderStatus, $folderPath);
  return array($FolderIsHeld, $HoldNotice); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write a file without following anything another account planted.
// / Accepts the destination path, the contents & the mode the file is created with.
// / Returns a success boolean & the reason for a failure, in that order.
// /
// / A plain write follows a link, & so does a chmod by path.
// / So the contents go into a new file opened with x, which fails on any name that already exists, a link included.
// / The mode is set by the umask as the file is created, so nothing is changed by path afterwards.
// / The new file is then renamed over the destination, which replaces a link rather than writing through it.
// / A reader sees the whole old file or the whole new one.
// / The temporary name is random, so it cannot be predicted & occupied first.
// / Root writes only in a folder folderIsHeldForRoot holds, because all of this guards the name & not the folder.
function writeFileWithoutFollowing($targetPath, $fileContents, $fileMode) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FileWasWritten = $folderIsHeld = FALSE;
  $WriteNotice = $holdNotice = '';
  $temporaryPath = $randomPart = '';
  $fileHandle = $error = FALSE;
  $previousMask = $bytesWritten = 0;
  list ($folderIsHeld, $holdNotice) = folderIsHeldForRoot(dirname((string)$targetPath));
  try { $randomPart = bin2hex(random_bytes(6)); }
  catch (Throwable $error) { $randomPart = str_replace('.', '', uniqid('', TRUE)); }
  $temporaryPath = dirname((string)$targetPath).DIRECTORY_SEPARATOR.'.'.basename((string)$targetPath).'.'.$randomPart.'.tmp';
  if ($folderIsHeld) {
    $previousMask = umask(0777 & ~((int)$fileMode));
    $fileHandle = @fopen($temporaryPath, 'x');
    umask($previousMask); }
  if (!$folderIsHeld) $WriteNotice = (string)$targetPath.' was not written. '.$holdNotice;
  else if ($fileHandle === FALSE) $WriteNotice = 'A new file could not be created beside '.$targetPath.'.';
  else {
    $bytesWritten = @fwrite($fileHandle, (string)$fileContents);
    @fflush($fileHandle);
    @fclose($fileHandle);
    if ($bytesWritten !== strlen((string)$fileContents)) $WriteNotice = 'Only part of '.$targetPath.' could be written.';
    else if (!@rename($temporaryPath, $targetPath)) $WriteNotice = 'The new copy of '.$targetPath.' could not be moved into place.';
    else $FileWasWritten = TRUE;
    // / A copy that was not moved into place is removed. Removing a name never follows it.
    if (!$FileWasWritten) @unlink($temporaryPath); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderIsHeld, $holdNotice, $temporaryPath, $randomPart, $fileHandle, $previousMask, $bytesWritten, $error, $targetPath, $fileContents, $fileMode);
  return array($FileWasWritten, $WriteNotice); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to open a file for appending without following anything another account planted.
// / Accepts the path & the mode a new file is created with.
// / Returns the open handle or FALSE, whether the file was created, & the reason for a
// / failure, in that order.
// /
// / A new file is created with x, which refuses every name that already exists, a link included.
// / An existing file is opened only when it is a plain file with exactly one name.
// / It is opened with r+ rather than a, because a creates a file wherever a link points & r+ creates nothing.
// / The open handle is compared with what was examined, by device & inode, so a link swapped in between is caught.
// / The caller writes through the handle for as long as it runs, so the name is never resolved again.
// / Root opens only in a folder folderIsHeldForRoot holds, for the reason given there.
function openFileWithoutFollowing($filePath, $fileMode) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FileHandle = FALSE;
  $FileWasCreated = $folderIsHeld = FALSE;
  $OpenNotice = $holdNotice = '';
  $pathStatus = $handleStatus = array();
  $previousMask = 0;
  list ($folderIsHeld, $holdNotice) = folderIsHeldForRoot(dirname((string)$filePath));
  clearstatcache(TRUE, (string)$filePath);
  $pathStatus = @lstat((string)$filePath);
  if (!$folderIsHeld) $OpenNotice = (string)$filePath.' was not opened. '.$holdNotice;
  else if (!is_array($pathStatus)) {
    $previousMask = umask(0777 & ~((int)$fileMode));
    $FileHandle = @fopen((string)$filePath, 'x');
    umask($previousMask);
    if ($FileHandle === FALSE) $OpenNotice = (string)$filePath.' could not be created.';
    else $FileWasCreated = TRUE; }
  else if (((int)$pathStatus['mode'] & 0170000) !== 0100000) $OpenNotice = (string)$filePath.' is not a plain file & was not opened.';
  else if ((int)$pathStatus['nlink'] !== 1) $OpenNotice = (string)$filePath.' has more than one name & was not opened.';
  else {
    $FileHandle = @fopen((string)$filePath, 'r+');
    if ($FileHandle === FALSE) $OpenNotice = (string)$filePath.' could not be opened.';
    else {
      $handleStatus = @fstat($FileHandle);
      if (!is_array($handleStatus) or $handleStatus['dev'] !== $pathStatus['dev'] or $handleStatus['ino'] !== $pathStatus['ino']) {
        @fclose($FileHandle);
        $FileHandle = FALSE;
        $OpenNotice = (string)$filePath.' changed while it was being opened & was not written.'; }
      else @fseek($FileHandle, 0, SEEK_END); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderIsHeld, $holdNotice, $pathStatus, $handleStatus, $previousMask, $filePath, $fileMode);
  return array($FileHandle, $FileWasCreated, $OpenNotice); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read a small file without following anything another account planted.
// / Accepts the path & the most bytes to read. Returns whether it was read, the contents & the
// / reason it was not, in that order.
// /
// / A read by name follows a link, & opening a FIFO waits forever.
// / Root reads files in folders the web server account may write, such as a PID file & the DATA protection file.
// / A FIFO swapped in there would hold every command root runs, -fp included, at its first read.
// / So the name must be a plain file with one name, & it is opened without blocking.
// / The open handle is compared with what was examined, by device & inode, so a name swapped in between is refused unread.
// / No more than the bytes asked for are read, so a file that never ends cannot fill memory.
// / Root reads only in a folder folderIsHeldForRoot holds, for the reason given there.
function readFileWithoutFollowing($filePath, $maximumBytes) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FileWasRead = $folderIsHeld = FALSE;
  $FileContents = $ReadNotice = $holdNotice = '';
  $pathStatus = $handleStatus = array();
  $fileHandle = FALSE;
  list ($folderIsHeld, $holdNotice) = folderIsHeldForRoot(dirname((string)$filePath));
  clearstatcache(TRUE, (string)$filePath);
  $pathStatus = @lstat((string)$filePath);
  if (!$folderIsHeld) $ReadNotice = (string)$filePath.' was not read. '.$holdNotice;
  else if (!is_array($pathStatus)) $ReadNotice = (string)$filePath.' does not exist.';
  else if (((int)$pathStatus['mode'] & 0170000) !== 0100000) $ReadNotice = (string)$filePath.' is not a plain file & was not read.';
  else if ((int)$pathStatus['nlink'] !== 1) $ReadNotice = (string)$filePath.' has more than one name & was not read.';
  else {
    $fileHandle = @fopen((string)$filePath, 'rn');
    if ($fileHandle === FALSE) $ReadNotice = (string)$filePath.' could not be opened.';
    else {
      $handleStatus = @fstat($fileHandle);
      if (!is_array($handleStatus) or ((int)$handleStatus['mode'] & 0170000) !== 0100000 or $handleStatus['dev'] !== $pathStatus['dev'] or $handleStatus['ino'] !== $pathStatus['ino']) $ReadNotice = (string)$filePath.' changed while it was being opened & was not read.';
      else {
        $FileContents = (string)@stream_get_contents($fileHandle, max(0, (int)$maximumBytes));
        $FileWasRead = TRUE; }
      @fclose($fileHandle); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $FileContents is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $folderIsHeld, $holdNotice, $pathStatus, $handleStatus, $fileHandle, $filePath, $maximumBytes);
  return array($FileWasRead, $FileContents, $ReadNotice); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove a file by name without following anything another account planted.
// / Accepts the path of the file to remove.
// / Returns a removal boolean & the reason for a failure, in that order. A file that is
// / already absent counts as removed.
// /
// / Removing a name never follows a link at that name, but every folder above it is looked up again.
// / A folder swapped for a link to /etc between a check & the removal would have root remove a file from /etc.
// / So the folder holding the file must be one folderIsHeldForRoot holds, which nothing but root can replace.
// / The folder itself may be another account's, because a name that account swaps in is only ever its own.
// / A link at the name itself is removed as a link.
// / A folder is never removed, because nothing inside it has been examined.
function removeFileWithoutFollowing($targetPath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FileWasRemoved = $folderIsHeld = FALSE;
  $RemovalNotice = $holdNotice = '';
  $targetStatus = array();
  $targetFolder = dirname((string)$targetPath);
  clearstatcache(TRUE, (string)$targetPath);
  $targetStatus = @lstat((string)$targetPath);
  if (!is_array($targetStatus)) $FileWasRemoved = TRUE;
  else if (((int)$targetStatus['mode'] & 0170000) === 0040000) $RemovalNotice = (string)$targetPath.' is a folder & was left where it is.';
  else {
    list ($folderIsHeld, $holdNotice) = folderIsHeldForRoot($targetFolder);
    if (!$folderIsHeld) $RemovalNotice = (string)$targetPath.' was not removed. '.$holdNotice;
    else if (!@unlink((string)$targetPath)) $RemovalNotice = (string)$targetPath.' could not be removed.';
    else $FileWasRemoved = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $targetFolder, $targetStatus, $folderIsHeld, $holdNotice, $targetPath);
  return array($FileWasRemoved, $RemovalNotice); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to change the owner & group of a name without following anything planted.
// / Accepts the path, the owning account & the group, by name. An empty owner or group is
// / left as it is. Returns a success boolean & the reason for a failure, in that order.
// /
// / PHP has no fchown, so root can only change an owner by name.
// / lchown & lchgrp change the name itself & never what a link points at, which closes a symbolic link.
// / A hard link swapped in between the look & the act would have root hand a root file to another account.
// / So a name is changed only in a folder root trusts, or when fs.protected_hardlinks is on.
// / With that on, an account can only link a file it already owns or can already read & write, so a swap gains nothing.
// / systemd turns it on at boot, & a host that turned it off is told & the name is left.
// / lchown follows every folder on the way to the name, so the folder must be one folderIsHeldForRoot holds as well.
function changeOwnerWithoutFollowing($targetPath, $ownerAccount, $groupAccount) {
  // / Set variables.
  global $EnableMemoryProtection;
  $OwnerWasChanged = $folderIsTrusted = $nameIsSafe = $folderIsHeld = FALSE;
  $OwnerNotice = $resolvedFolder = $trustNotice = $hardlinkSetting = $holdNotice = '';
  $targetFolder = dirname((string)$targetPath);
  list ($folderIsHeld, $holdNotice) = folderIsHeldForRoot($targetFolder);
  list ($folderIsTrusted, $resolvedFolder, $trustNotice) = pathIsTrustedByRoot($targetFolder);
  $nameIsSafe = ($folderIsTrusted && $resolvedFolder === $targetFolder);
  if (!$nameIsSafe) {
    $hardlinkSetting = trim((string)@file_get_contents('/proc/sys/fs/protected_hardlinks'));
    $nameIsSafe = ($hardlinkSetting === '1'); }
  if (!$folderIsHeld) $OwnerNotice = (string)$targetPath.' was left as it is. '.$holdNotice;
  else if (!$nameIsSafe) $OwnerNotice = (string)$targetPath.' was left as it is, because another account can change '.$targetFolder.' & fs.protected_hardlinks is not on. Run  sysctl -w fs.protected_hardlinks=1  & keep it on in /etc/sysctl.d.';
  else {
    $OwnerWasChanged = TRUE;
    if ((string)$ownerAccount !== '' && !@lchown((string)$targetPath, (string)$ownerAccount)) $OwnerWasChanged = FALSE;
    if ((string)$groupAccount !== '' && !@lchgrp((string)$targetPath, (string)$groupAccount)) $OwnerWasChanged = FALSE;
    if (!$OwnerWasChanged) $OwnerNotice = (string)$targetPath.' could not be given to the account or group asked for.'; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderIsTrusted, $nameIsSafe, $folderIsHeld, $resolvedFolder, $trustNotice, $hardlinkSetting, $holdNotice, $targetFolder, $targetPath, $ownerAccount, $groupAccount);
  return array($OwnerWasChanged, $OwnerNotice); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove a tree as root without following anything in it.
// / Accepts the absolute path of the tree. Returns a removal boolean & the reason for a failure,
// / in that order. A tree that is already absent counts as removed.
// /
// / The folder holding the tree must be root's alone, so nothing but root can put a link where the tree is.
// / Every name in it is examined with lstat, & a link or a file is removed as a name & never followed.
// / A folder is emptied before it is removed.
// / Every folder is made root's alone before anything in it is listed.
// / Otherwise another account could swap a folder for a link between the listing & the emptying.
// / A folder that cannot be made root's alone stops the removal, & a warning counts the folders taken back.
// / It never leaves the filesystem the folder holding the tree is on, because something mounted is not the tree's.
// / A tree that is itself something mounted is left as it is.
function removeTreeWithoutFollowing($treePath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $TreeWasRemoved = $parentIsTrusted = $walkIsHeld = $ownerWasChanged = FALSE;
  $RemovalNotice = $resolvedParent = $trustNotice = $currentPath = $folderEntry = $folderPath = $treeName = $ownerNotice = '';
  $pendingPaths = $foldersToRemove = $entryStatus = $heldStatus = $parentStatus = $folderEntries = array();
  $foldersTakenBack = $foldersElsewhere = 0;
  $treeDevice = -1;
  $treePath = rtrim((string)$treePath, DIRECTORY_SEPARATOR);
  $treeName = basename($treePath);
  clearstatcache(TRUE, $treePath);
  if (!file_exists($treePath) && !is_link($treePath)) $TreeWasRemoved = TRUE;
  else if ($treeName === '' or $treeName === '.' or $treeName === '..' or substr($treePath, 0, 1) !== DIRECTORY_SEPARATOR) $RemovalNotice = $treePath.' is not a tree that can be removed.';
  else {
    list ($parentIsTrusted, $resolvedParent, $trustNotice) = pathIsTrustedByRoot(dirname($treePath));
    if (!$parentIsTrusted or $resolvedParent !== dirname($treePath)) $RemovalNotice = $treePath.' was left as it is, because the folder holding it is not root\'s alone. '.($trustNotice !== '' ? $trustNotice : dirname($treePath).' leads to '.$resolvedParent.'.');
    else {
      $parentStatus = @lstat(dirname($treePath));
      if (is_array($parentStatus)) $treeDevice = $parentStatus['dev'];
      $walkIsHeld = TRUE;
      $pendingPaths = array($treePath);
      while ($walkIsHeld && !empty($pendingPaths)) {
        $currentPath = (string)array_pop($pendingPaths);
        clearstatcache(TRUE, $currentPath);
        $entryStatus = @lstat($currentPath);
        if (!is_array($entryStatus)) continue;
        // / Anything but a folder, a link included, is removed.
        if (((int)$entryStatus['mode'] & 0170000) !== 0040000) {
          @unlink($currentPath);
          continue; }
        if ($entryStatus['dev'] !== $treeDevice) {
          $foldersElsewhere++;
          continue; }
        // / A folder another account could change is given to root, & loses its group & other
        // / write, before it is listed. The same folder must be found afterwards, root's alone.
        if ((int)$entryStatus['uid'] !== 0 or ((int)$entryStatus['mode'] & 0022) !== 0) {
          $foldersTakenBack++;
          if ((int)$entryStatus['uid'] !== 0) list ($ownerWasChanged, $ownerNotice) = changeOwnerWithoutFollowing($currentPath, 'root', '');
          @chmod($currentPath, (int)$entryStatus['mode'] & 07755);
          clearstatcache(TRUE, $currentPath);
          $heldStatus = @lstat($currentPath);
          $walkIsHeld = (is_array($heldStatus) && ((int)$heldStatus['mode'] & 0170000) === 0040000 && $heldStatus['dev'] === $entryStatus['dev'] && $heldStatus['ino'] === $entryStatus['ino'] && (int)$heldStatus['uid'] === 0 && ((int)$heldStatus['mode'] & 0022) === 0); }
        // / A folder is listed only once it is root's alone, & removed once it is empty.
        if ($walkIsHeld) {
          $foldersToRemove[] = $currentPath;
          $folderEntries = @scandir($currentPath);
          foreach ((is_array($folderEntries) ? $folderEntries : array()) as $folderEntry) {
            if ($folderEntry !== '.' && $folderEntry !== '..') $pendingPaths[] = $currentPath.DIRECTORY_SEPARATOR.$folderEntry; } } }
      if ($walkIsHeld) foreach (array_reverse($foldersToRemove) as $folderPath) @rmdir($folderPath);
      if (!$walkIsHeld) $RemovalNotice = $currentPath.' could not be made root\'s alone, so what is left of '.$treePath.' was not removed. '.$ownerNotice;
      else if ($foldersElsewhere > 0) $RemovalNotice = $treePath.' holds '.$foldersElsewhere.' folder(s) on another filesystem, which were left as they are, & so was each folder holding one.';
      if ($walkIsHeld && $foldersTakenBack > 0) warningEntry($treePath.' held '.$foldersTakenBack.' folder(s) an account other than root could change. Each was made root\'s alone before anything in it was removed.');
      clearstatcache(TRUE, $treePath);
      $TreeWasRemoved = (!file_exists($treePath) && !is_link($treePath));
      if (!$TreeWasRemoved && $RemovalNotice === '') $RemovalNotice = $treePath.' could not be removed completely.'; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $parentIsTrusted, $walkIsHeld, $ownerWasChanged, $resolvedParent, $trustNotice, $currentPath, $folderEntry, $folderPath, $treeName, $ownerNotice, $pendingPaths, $foldersToRemove, $entryStatus, $heldStatus, $parentStatus, $folderEntries, $foldersTakenBack, $foldersElsewhere, $treeDevice, $treePath);
  return array($TreeWasRemoved, $RemovalNotice); }
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
// / Accepts the secret file, the version the application requires & whether a new key was asked for.
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
      // / That account uses the first copy it can read & use, in the order root looked, & passes over one it cannot read & one not in the shape the kernel reads.
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
// / establishInstallSecret names a per user secret with its account after that, & it is the only place either name is built.
function resolveSecretFile($secretFile, $requiredSecretVersion) {
  // / Set variables.
  global $LogFile, $ApacheUser, $CurrentUser, $EnableMemoryProtection, $CoreLoaded;
  $SecretIsReady = FALSE;
  $ResolvedSecretKey = $secret = $secretFileContent = '';
  $detectedSecretVersion = $cleanRequiredVersion = $rotationNotice = $strayOutput = $entryName = '';
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
  // / Load an existing secret file & decide whether it may still be used.
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
      else error_log('WARNING!!! '.applicationLabel().': '.$rotationNotice); }
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
    // / The command names the file this process was started through, which is the application's entry point.
    $entryName = basename((string)(isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : ''));
    $rotationNotice = 'The install secret cannot be used by '.$CurrentUser.' & is not replaced from a web request. '.($entryName === '' ? 'Run -fp as root to replace it.' : 'Run  sudo php '.$entryName.' -fp  to replace it.');
    if ($loggingIsReady) warningEntry($rotationNotice);
    else error_log('WARNING!!! '.applicationLabel().': '.$rotationNotice);
    $SecretKey = ''; }
  // / Remove a secret file that may not be used, so a replacement can take its place.
  // / A file carrying no version predates version pinning & is always replaced.
  if (file_exists($secretFile) && !$existingIsUsable && $creationIsPermitted) {
    if ($rotationNotice === '') {
      $rotationNotice = 'The secret file at '.$secretFile.' reports version '.($detectedSecretVersion === '' ? 'none' : 'v'.$detectedSecretVersion).' & this core requires v'.$cleanRequiredVersion.'. It is being replaced & every session derived from it is now invalid.';
      if ($loggingIsReady) warningEntry($rotationNotice);
      else error_log('WARNING!!! '.applicationLabel().': '.$rotationNotice); }
    @unlink($secretFile);
    // / A file that survives the unlink belongs to another account, & saying so once beats failing the same way on every request.
    // / Only a standard user reaches this, for a secret of its own in its own folder, which -fp does not manage.
    if (file_exists($secretFile)) {
      $rotationNotice = 'The secret file at '.$secretFile.' could not be removed by '.$CurrentUser.'. Delete it as root & run the command again.';
      if ($loggingIsReady) errorEntry($rotationNotice, 30, FALSE);
      else error_log('ERROR!!! '.applicationLabel().'-30: '.$rotationNotice); }
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
        else error_log('WARNING!!! '.applicationLabel().': '.$rotationNotice); }
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
          else error_log('WARNING!!! '.applicationLabel().': '.$rotationNotice);
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
            else error_log('WARNING!!! '.applicationLabel().': '.$rotationNotice); } } } } }
  // / Adopt the key from a file that was already usable.
  if ($existingIsUsable) {
    $ResolvedSecretKey = $SecretKey;
    $SecretIsReady = TRUE; }
  // / Manually clean up sensitive memory. Every target is passed directly by reference.
  // / $ResolvedSecretKey is not purged, because it is the return value.
  // / PHP separates the shared buffer when $SecretKey is shredded, so the return survives intact.
  if (!purgeSensitiveMemory($EnableMemoryProtection, $SecretKey, $SecretVersion, $secret, $secretFile, $secretFileContent, $bytesWritten, $secretGenerated, $existingIsUsable, $detectedSecretVersion, $cleanRequiredVersion, $rotationNotice, $strayOutput, $loggingIsReady, $payloadIsWellFormed, $secretIsReadable, $creationIsPermitted, $isInstallSecret, $secretWasRead, $entryName, $requiredSecretVersion)) {
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
    else error_log('WARNING!!! '.applicationLabel().': '.$folderNotice); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $candidateFolder, $folderNotice, $loggingIsReady, $applicationSlug, $accountSlug);
  return array($FolderIsReady, $UserSecretFolder); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide which secret this process may hold, & to establish it.
// / Accepts the application's slug, already sanitized, which names its secret files.
// / Returns a readiness boolean, the key & the file it was read from, in that order.
// / A failure returns FALSE & two empty strings, which the application must treat as a failed installation.
// / The application sets $SecretKey & $SecretFile from what this returns.
// / The application calls this once its configuration is read, because the data location the secret is kept in comes from it.
// /
// / A secret is only touched by an authorized combination of account & context.
// / Root from the command line, or the web server account from a web request, holds the install
// / wide secret, & so does the web server account on the command line for the commands that
// / operate the listener. Any other command line account holds a secret of its own in a folder
// / of its own in the temporary directory, which lets it run diagnostics without ever reading
// / the install wide one.
// / Any other combination holds no secret at all.
function establishInstallSecret($applicationSlug) {
  // / Set variables.
  global $RunningFromCLI, $RunningAsRoot, $CurrentUser, $ApacheUser, $ConvertLoc, $DataRootLoc, $SecretLoc, $HostedLoc, $ProprietaryLoc, $AdditionalConvertLocs, $LogDir, $LogFile, $RequiredSecretVersion, $SecretPlacesWereOpen, $EnableMemoryProtection;
  $SecretIsReady = FALSE;
  $SecretKey = $SecretFile = '';
  $secretAuthorized = $userSecretAuthorized = $copyIsTrusted = $dataLocationIsTrusted = $secretFolderIsReady = $legacyWasRemoved = $blockingWereJudged = $limitingWereJudged = FALSE;
  $secretFolder = $legacySecretFile = $olderSecretFile = $installSecretFile = $webCandidate = $trustNotice = $accountSlug = $listenerCommand = $legacyNotice = '';
  $webSecretsState = $webSecretsMode = $webSecretsOwnership = $resolvedDataLocation = $dataLocationNotice = '';
  $webCandidates = $webTried = $listenerArguments = $blockingFolders = $limitingFolders = $limitingLocations = $additionalLocation = array();
  $webCandidateIndex = $listenerArgumentCount = 0;
  // / Set by prepareDataLocationAsRoot or resolveInstallSecretAsRoot when a place the secret is kept
  // / could be written by an account other than root & the web server account. See
  // / installSecretCopyIsTrusted.
  $SecretPlacesWereOpen = FALSE;
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
  // / --account is granted it, from v4.2.0, because root hands every account command to the web server user, & what that user changes
  // / in an account is logged where a web request logs, beside the sign ins it changes.
  // / The command is read with the kernel's parser, because the application has not read its command line yet & two places deciding what the first argument means come to disagree.
  if ($RunningFromCLI && $CurrentUser === $ApacheUser) {
    list ($listenerArguments, $listenerArgumentCount, $listenerCommand) = parseArgumentVector(isset($_SERVER['argv']) ? $_SERVER['argv'] : array());
    if (in_array($listenerCommand, array('--start-core-manager', '--start-manager', '--run-core-manager', '-l', '--listen', '-k', '--kill', '--kill-all-workers', '--kill-every-worker', '--status', '-c', '--clean', '--run-workflow', '--account'), TRUE)) {
      $secretAuthorized = TRUE;
      $userSecretAuthorized = FALSE; } }
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
        if (prepareDataLocationAsRoot($ConvertLoc)) list ($SecretIsReady, $SecretKey) = resolveInstallSecretAsRoot($SecretFile, $RequiredSecretVersion, FALSE); } }
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
        if ($SecretIsReady or !file_exists($webCandidate) or ($webCandidateIndex === 2 && !is_readable($webCandidate))) continue;
        list ($copyIsTrusted, $trustNotice) = installSecretCopyIsTrusted($webCandidate);
        if ($copyIsTrusted) list ($SecretIsReady, $SecretKey) = resolveSecretFile($webCandidate, $RequiredSecretVersion);
        else if (!isset($webTried[$webCandidate])) warningEntry('A copy of the install secret is not used, because another account could have written it. Run -fp as root, which names it & why.');
        $webTried[$webCandidate] = TRUE; }
      if (!$SecretIsReady && !file_exists($SecretFile) && !file_exists($olderSecretFile)) errorEntry('There is no install secret & the web server account does not create one. Run -fp as root to create it.', 31021, FALSE); }
    // / Remove the unnamed secret file left by a release before this one.
    // / It is removed only once a replacement is in place, so a failure above leaves the
    // / old file usable & the installation recoverable.
    // / The folder is the web server account's, so root removes it with removeFileWithoutFollowing.
    if ($SecretIsReady && (file_exists($legacySecretFile) or is_link($legacySecretFile))) {
      list ($legacyWasRemoved, $legacyNotice) = removeFileWithoutFollowing($legacySecretFile);
      $legacyNotice = $legacyWasRemoved ? 'The legacy secret file at '.$legacySecretFile.' was removed.' : 'The legacy secret file at '.$legacySecretFile.' could not be removed. '.$legacyNotice;
      if (isset($LogFile) && is_string($LogFile) && $LogFile !== '') warningEntry($legacyNotice);
      else error_log('WARNING!!! '.applicationLabel().': '.$legacyNotice); } }
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
      list ($SecretIsReady, $SecretKey) = resolveSecretFile($SecretFile, $RequiredSecretVersion); } }
  // / A secret that is not ready hands back no key & no file, so nothing reads a half established one.
  if (!$SecretIsReady) $SecretKey = $SecretFile = '';
  // / Manually clean up sensitive memory. Every target is passed directly by reference.
  // / $SecretIsReady, $SecretKey & $SecretFile are not purged, because they are the return values.
  purgeSensitiveMemory($EnableMemoryProtection, $secretAuthorized, $userSecretAuthorized, $copyIsTrusted, $dataLocationIsTrusted, $secretFolderIsReady, $legacyWasRemoved, $blockingWereJudged, $limitingWereJudged, $secretFolder, $legacySecretFile, $olderSecretFile, $installSecretFile, $webCandidate, $trustNotice, $accountSlug, $listenerCommand, $legacyNotice, $webSecretsState, $webSecretsMode, $webSecretsOwnership, $resolvedDataLocation, $dataLocationNotice, $webCandidates, $webTried, $listenerArguments, $blockingFolders, $limitingFolders, $limitingLocations, $additionalLocation, $webCandidateIndex, $listenerArgumentCount, $applicationSlug);
  return array($SecretIsReady, $SecretKey, $SecretFile); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to settle the install secret this process holds as root, or to rotate it.
// / Accepts whether a new key was asked for. Returns a readiness boolean & the key, in that order.
// / It settles the file establishInstallSecret read, at the version the application requires, so a caller names neither.
// / -fp settles it once Secrets has its role, so the copy there is written with the key in use.
// / The Engine rotates it, because a rotation stops & starts the listener, which is the Engine's.
// / The caller sets $SecretKey from what this returns, when it is ready.
function settleInstallSecretAsRoot($rotationRequested) {
  // / Set variables.
  global $SecretFile, $RequiredSecretVersion, $EnableMemoryProtection;
  $SecretIsReady = FALSE;
  $SettledSecretKey = '';
  if (isset($SecretFile) && is_string($SecretFile) && $SecretFile !== '') list ($SecretIsReady, $SettledSecretKey) = resolveInstallSecretAsRoot($SecretFile, $RequiredSecretVersion, $rotationRequested);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $SecretIsReady & $SettledSecretKey are not purged, because they are the return values.
  purgeSensitiveMemory($EnableMemoryProtection, $rotationRequested);
  return array($SecretIsReady, $SettledSecretKey); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to judge the secret this process holds & the folder the install secret is kept in, for a report.
// / Accepts nothing. Returns whether the web server account's group could be looked up & the rows, in that order.
// / Each row is an array of its Label, its Text & whether it Failed, & the secret's row comes first.
// / Without the group, the group of neither the secret nor its folder is judged, & the rows say so.
// /
// / The install secret is root's & 0640, so the web server account's group reads it, nothing else can, & only root can change it.
// / A standard user's own secret is that user's & 0600.
// / The install secret is kept in Secrets, which is root's, the web server account's group & 0750, so no other account can list it.
// / Until -fp moves it there, the copy an older release kept at the top of the data location is the one in use, & the one judged here.
// / The row judges the secret this command holds by its own role.
// / The install secret is the one name ending in -secret.php, & a per user secret carries its account after that, as resolveSecretFile says.
// / The web server account on the command line holds a per user secret for -v, so the expectation follows the name rather than the account.
// / The row is named for the secret it judges, because an account that is neither root nor the web server's holds a secret of its own.
function describeInstallSecret() {
  // / Set variables.
  global $SecretFile, $SecretLoc, $ApacheUser, $EnableMemoryProtection;
  $GroupWasFound = TRUE;
  $SecretRows = array();
  $secretShown = $secretExpected = $olderSecretFile = $secretMode = $topCopyMode = $secretsState = $secretsMode = $secretsOwnership = $secretLabel = $secretText = $folderText = '';
  $secretOwner = $secretGroup = $topCopyOwner = $topCopyGroup = $secretGroupExpected = $webAccountNumber = -1;
  $secretPasses = $secretFailed = $folderFailed = FALSE;
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
  $secretLabel = ($secretExpected === '0640') ? 'Install secret' : 'Per user secret';
  if ($secretMode === '') $secretText = 'NOT PRESENT, '.($secretExpected === '0640' ? 'created by root\'s first command' : 'created by this account\'s first command');
  else if (!$secretPasses) {
    $secretFailed = TRUE;
    $secretText = 'FAILED, '.($secretShown === $olderSecretFile ? 'the copy at the top of the data location, ' : '').'mode '.$secretMode.($secretOwner === 0 ? ', root' : ', not root').(($secretExpected === '0640' && $secretGroupExpected >= 0) ? ($secretGroup === $secretGroupExpected ? ', group '.$ApacheUser : ', group not '.$ApacheUser) : '').', expected '.$secretExpected.($secretExpected === '0640' ? ', root'.($secretGroupExpected >= 0 ? ' & group '.$ApacheUser : '') : '').'. Run -fp as root'; }
  else $secretText = 'OK, '.$secretExpected.($secretExpected === '0640' ? ', root' : '').($secretShown === $olderSecretFile ? ', at the top of the data location until -fp copies it into Secrets' : '');
  if ($secretsState === 'missing') $folderText = 'NOT PRESENT, -fp creates it';
  else if ($secretsState === 'not a folder') $folderText = 'FAILED, not a folder & never followed. Remove it & run -fp as root';
  else if ($secretsState === 'unjudged') $folderText = 'FAILED, '.$secretsOwnership.' '.$secretsMode.', & the group '.$ApacheUser.' could not be looked up';
  else if ($secretsState !== 'ok') $folderText = 'FAILED, '.$secretsOwnership.' '.$secretsMode.', expected root:'.$ApacheUser.' 0750. Run -fp as root';
  else $folderText = 'OK, 0750, root:'.$ApacheUser;
  $folderFailed = (strpos($folderText, 'FAILED') === 0);
  $SecretRows = array(array('Label' => $secretLabel, 'Text' => $secretText, 'Failed' => $secretFailed), array('Label' => 'Secrets folder', 'Text' => $folderText, 'Failed' => $folderFailed));
  // / The group is looked up only for the install secret, & a folder's group that cannot be judged is said in its row.
  $GroupWasFound = !(($secretExpected === '0640' && $secretGroupExpected < 0) or $secretsState === 'unjudged');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $GroupWasFound & $SecretRows are not purged, because they are the return values.
  purgeSensitiveMemory($EnableMemoryProtection, $secretShown, $secretExpected, $olderSecretFile, $secretMode, $topCopyMode, $secretsState, $secretsMode, $secretsOwnership, $secretLabel, $secretText, $folderText, $secretOwner, $secretGroup, $topCopyOwner, $topCopyGroup, $secretGroupExpected, $webAccountNumber, $secretPasses, $secretFailed, $folderFailed);
  return array($GroupWasFound, $SecretRows); }
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
    // / HRC2 names these derivations rather than any one application. Every application derives with it,
    // / because changing it would end every session & rename every logfile at the next update.
    $dailyContext = 'HRC2-DAILY|'.$Date.'|'.($_SERVER['SERVER_NAME'] ?? '');
    // / Per-server-per-day parent directory.
    $SesHash = substr(hash_hmac('sha256', $dailyContext, $SecretKey), -18);
    // / Per-session directory. Bound to the daily parent so a token cannot be replayed into a different day's directory tree.
    $SesHash2 = substr(hash_hmac('sha256', 'HRC2-SESSION|'.$SesHash.'|'.(string)$Token1, $SecretKey), -18);
    // / The combined relative path the application uses everywhere else.
    // / DIRECTORY_SEPARATOR rather than $DirSep, because this runs before the application
    // / sets that global.
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
  // / A Token1 that ARRIVED and did not verify is different. Something sent a token that
  // / does not verify, & that is worth recording once, at the tier that means
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
