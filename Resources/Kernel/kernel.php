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
// / command line, finds executables & loads components, the Engine among them.
// /
// / File Information ...
// / v4.1.2.
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
// /                            An application sets it itself, or decides the folder & the name
// /                            & has openLogFileForRoot or openLogFileByName open it & set it.
// /   $LogHandle               Optional. An open handle written through rather than by name.
// /                            Root holds one, standard error until it has a log folder of
// /                            its own, & nothing else needs to. openLogFileForRoot sets it.
// /   $SesHash3                Optional. A session label for every log line. Absent reads as
// /                            No Session, which is correct for an application without sessions.
// / The time globals are set by verifyTime. The log buffer & sequence are managed here.
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
$KernelVersion = 'v4.1.2';
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
        else error_log('WARNING!!! HRConvert2: '.$failureText.' Continuing...'); }
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
  global $Time, $LogFile, $LogHandle, $SesHash3, $ApplicationName, $RunningFromCLI, $LogBuffer, $LogEchoToConsole;
  // / The timestamp is recomputed here, at the moment the entry is written, rather than taken from $Time.
  // / verifyTime sets $Time once, which suits a short request & not a manager that runs for weeks.
  // / $Time itself is left alone, because a session directory is named from it & must not change underneath a request.
  // / An empty $Time means verifyTime has not run, so no timezone is set & no time is known.
  $timeLabel = (isset($Time) && $Time !== '') ? date('F j, Y, g:i a') : 'Unknown Time';
  $sessionLabel = (isset($SesHash3) && $SesHash3 !== '') ? (string)$SesHash3 : 'No Session';
  $applicationLabel = (isset($ApplicationName) && is_string($ApplicationName) && $ApplicationName !== '') ? $ApplicationName : 'HRConvert2';
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
  global $ApplicationName;
  $LogWritten = FALSE;
  $applicationLabel = $errorLabel = '';
  $applicationLabel = (isset($ApplicationName) && is_string($ApplicationName) && $ApplicationName !== '') ? $ApplicationName : 'HRConvert2';
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
