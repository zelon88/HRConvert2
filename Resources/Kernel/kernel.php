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
// / v3.9.9.
// / This file is the kernel. An application's entry point requires it directly as the first
// / thing it does, & pins it EXACTLY via $RequiredKernelVersion.
// / Error 35000 is the direct load guard every component shares.
// /
// / Why it is a file of its own.
// / Until v3.9.9 the kernel lived inside convertCore.php, interleaved with sixty three
// / application functions. Every application must run byte identical kernel code, & a
// / second application could only have obtained it by copying functions out by hand. It now
// / ships as Resources/Kernel beside Resources/Engine, so two applications trade it as a unit
// / & compare it by version.
// /
// / Why it is not inside the Engine.
// / verifyCoreComponent loads the Engine, so it cannot live in the Engine. Logging must survive
// / an Engine that failed to load, because that is the moment most worth reporting. The kernel
// / sits beside the Engine rather than in it, so the rule that the Engine cannot provide what
// / is needed to load the Engine holds for the directories as well as for the files.
// /
// / The rules, enforced by check nineteen of Documentation/Build/hrconvert2-check.py.
// /   The kernel calls nothing an application defines, so it is identical in every one.
// /   The kernel calls nothing the Engine defines without a function_exists guard, because it
// /   runs before the Engine loads & must keep running when the Engine cannot.
// /   Nothing here is guarded against redefinition. This is the definition, & an application
// /   that quietly replaced a kernel function would no longer be running the kernel.
// /
// / What an application provides.
// /   $EnableMemoryProtection  Whether variables are shredded at every return.
// /   $Verbose                 Whether ordinary activity is logged.
// /   $InstLoc                 The application root. Components are found beneath it.
// /   $ApplicationName         The name an operator sees in warning & error lines.
// /   $RunningFromCLI          Whether there is a terminal rather than a web client.
// /   $CoreLoaded              Set TRUE by the application before it loads any component.
// /   $LogFile                 The logfile. Until it exists, lines are buffered rather than lost.
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
$KernelVersion = 'v3.9.9';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to give the absolute path of one file in the application's contract.
// / Accepts the file's name. Returns its absolute path, whether or not it exists.
// / The contract is what an application tells the shared layers about itself, which is its
// / identity, its dependency manifest & its configuration template. It is the application's
// / own data, so it lives beside the Kernel & the Engine at Resources/Contract rather than
// / inside either. It lived inside the Engine until v3.9.9, so copying the Engine to another
// / application copied this one's identity & dependency list with it.
// / Every reader asks here, so the location is written once. The kernel is the one layer every
// / reader can reach, -v with no Engine included. The name is reduced to its last part, so a
// / caller cannot be talked into reading anything outside the contract.
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
  // / The TARGET is shredded, never $newValue. $newValue is what the caller asked to store,
  // / & purgeSensitiveMemory writes NULL through its reference, so purging it here assigns
  // / nothing to the target & silently empties the variable this function was asked to set.
  // / That happened. Every redeclare() in convertGui2.php wiped the URL it was building, &
  // / the settings links rendered as convertCore.php with no path & no parameters.
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
// / THIS STAYS IN THE APPLICATION & the reason is worth reading before moving it again.
// / purgeSensitiveMemory calls it, & purgeSensitiveMemory is called by EVERYTHING,
// / including code that runs long before the Engine is loaded. It was moved to the
// / Engine once & took the whole application down at line 57.
// / The pre Engine check did not catch it because it looks for TOP LEVEL calls, & this
// / is reached from inside a function that everything calls. A caller that ubiquitous
// / has no single call site to find.
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
      // / A RESOURCE is expected to be unshreddable & saying so is noise rather than news.
      // / A socket handle holds no bytes this process can overwrite, so dropping the
      // / reference is the only thing available & is exactly the right outcome.
      // / Every manager pass closes several sockets & logging each one buried the entries
      // / an operator actually needs to see.
      // / Any OTHER type reaching here is still reported. An object that cannot be
      // / shredded is worth knowing about, because it may hold a value in a property this
      // / function never reached.
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
// / A function to close the web server connection to the client.
// / Accepts nothing. Returns nothing, & it is exempt from the single exit rule for that reason.
// / Named for what it does. It was called closeHRC2Connection until v3.9.9, which put one
// / application's name inside the kernel, so a second application would have had to define a
// / function named after this one for its own quickDie to halt.
function closeClientConnection() {
  ignore_user_abort(TRUE);
  if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
  else {
    if (ob_get_level() > 0) ob_end_flush();
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
// / A function to record one finished line, or to hold it until a logfile exists.
// / Accepts the finished line. Returns TRUE when it reached the logfile.
// / An entry raised before verifyLogs has nowhere to go & used to reach the server error
// / log, where it was separated from everything around it & usually never found.
// / It is held in a buffer instead & written in order the moment a logfile exists.
// / Nothing before the logfile is therefore lost, & nothing is written out of order.
// / LOCK_EX is used because several processes append to one file.
// / An append under the pipe buffer size is atomic without it, & a stream inspection dump
// / or a compiler log is not. Those are exactly the entries worth not corrupting.
// / This function does NOT call purgeSensitiveMemory, for the reason given above.
function emitLogEntry($composedEntry) {
  // / Set variables.
  global $LogFile, $LogBuffer, $LogBufferOverflowed;
  $EntryWasWritten = $logIsUsable = FALSE;
  $bufferedEntry = '';
  if (!isset($LogBuffer) or !is_array($LogBuffer)) $LogBuffer = array();
  $logIsUsable = (isset($LogFile) && is_string($LogFile) && $LogFile !== '' && file_exists($LogFile));
  if (!$logIsUsable) {
    // / The buffer is bounded. A process that never reaches a logfile must not grow until
    // / it is killed for memory, & the entries that explain a boot failure are the early
    // / ones rather than the thousandth.
    if (count($LogBuffer) < 512) array_push($LogBuffer, $composedEntry);
    else $LogBufferOverflowed = TRUE; }
  else {
    // / Anything held before the logfile existed is written first & in order.
    if (count($LogBuffer) > 0) {
      foreach ($LogBuffer as $bufferedEntry) @file_put_contents($LogFile, $bufferedEntry.PHP_EOL, FILE_APPEND | LOCK_EX);
      $LogBuffer = array();
      if (isset($LogBufferOverflowed) && $LogBufferOverflowed) {
        @file_put_contents($LogFile, 'WARNING!!! The log buffer filled before a logfile existed & older entries were discarded.'.PHP_EOL, FILE_APPEND | LOCK_EX);
        $LogBufferOverflowed = FALSE; } }
    $EntryWasWritten = (@file_put_contents($LogFile, $composedEntry.PHP_EOL, FILE_APPEND | LOCK_EX) !== FALSE); }
  // / This cleanup is manual, because purgeSensitiveMemory calls into this path.
  $composedEntry = $logIsUsable = $bufferedEntry = NULL;
  unset($composedEntry, $logIsUsable, $bufferedEntry);
  return $EntryWasWritten; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write everything held in the buffer, now that a logfile exists.
// / Accepts nothing. Returns the number of entries written.
// / verifyLogs calls this the moment it has a usable file, so a boot that logged before
// / the logfile existed still lands in the logfile rather than in the server error log.
function flushLogBuffer() {
  // / Set variables.
  global $LogBuffer;
  $EntriesFlushed = 0;
  if (isset($LogBuffer) && is_array($LogBuffer)) $EntriesFlushed = count($LogBuffer);
  if ($EntriesFlushed > 0) emitLogEntry(composeLogEntry('Op-Act, ', 'Log buffer flushed. '.$EntriesFlushed.' entry or entries were held before a logfile existed.', ''));
  return $EntriesFlushed; }
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
  global $Time, $LogFile, $SesHash3, $ApplicationName, $RunningFromCLI, $LogBuffer;
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
  $bufferedEntry = '';
  $errorLabel = is_numeric($errorNumber) ? $applicationLabel.'-'.$errorNumber : $applicationLabel.'-###';
  // / A halt is numbered like everything else, so the sequence has no hole at the end.
  $formattedEntry = composeLogEntry('ERROR!!! ', $entry, $errorLabel);
  $logIsWritable = (isset($LogFile) && is_string($LogFile) && $LogFile !== '');
  // / Record it wherever it can be recorded. A halt with no record is a support ticket
  // / that can never be answered.
  if (!$logIsWritable) error_log($formattedEntry);
  // / Everything held before a logfile existed is reported here rather than discarded.
  // / A halt during boot is exactly the case where the entries leading up to it explain the
  // / halt, & exactly the case where there was nowhere to have written them.
  if (isset($LogBuffer) && is_array($LogBuffer) && count($LogBuffer) > 0) {
    foreach ($LogBuffer as $bufferedEntry) {
      if ($logIsWritable) @file_put_contents($LogFile, $bufferedEntry.PHP_EOL, FILE_APPEND | LOCK_EX);
      else error_log($bufferedEntry);
      if ($RunningFromCLI) print($bufferedEntry.PHP_EOL);
      else print($bufferedEntry.PHP_EOL.'<br />'.PHP_EOL); }
    $LogBuffer = array(); }
  // / A browser needs a line break to render the message. A terminal does not & would
  // / print the tag literally.
  if ($RunningFromCLI) print($formattedEntry.PHP_EOL);
  else print($formattedEntry.PHP_EOL.'<br />'.PHP_EOL);
  // / Close the connection deliberately rather than dropping it, so the message is
  // / delivered before the process ends. There is nothing to close on the command line.
  if (!$RunningFromCLI && function_exists('closeClientConnection')) closeClientConnection();
  // / A halt is a failure, so the process says so. This was a bare die until v3.9.9, which
  // / exits with status zero, & every fatal error this application has ever raised reported
  // / success to whatever was watching it. systemd runs the listener with Restart=on-failure,
  // / so a listener that died here was never restarted. The Dockerfile follows its setup step
  // / with a fallback for a failure that could not happen. A web request is unaffected,
  // / because nothing sees the status & shutdown functions run either way.
  exit(1); }
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
  // / The formatting & the writing both live elsewhere now, so this function holds nothing.
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
// / An entry raised before verifyLogs() goes to the server error log instead.
function warningEntry($entry) {
  // / Set variables.
  // / The formatting & the writing both live elsewhere now, so this function holds nothing.
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
// / An entry raised before verifyLogs() goes to the server error log instead.
// / A fatal error hands off to quickDie so every halt in the core is identical.
function errorEntry($entry, $errorNumber, $die) {
  // / Set variables.
  global $ApplicationName;
  $LogWritten = FALSE;
  $applicationLabel = $errorLabel = '';
  $applicationLabel = (isset($ApplicationName) && is_string($ApplicationName) && $ApplicationName !== '') ? $ApplicationName : 'HRConvert2';
  $errorLabel = is_numeric($errorNumber) ? $applicationLabel.'-'.$errorNumber : $applicationLabel.'-###';
  $LogWritten = emitLogEntry(composeLogEntry('ERROR!!! ', $entry, $errorLabel));
  // / A fatal error halts here & quickDie repeats none of the above.
  // / It reports to the page, empties the log buffer & closes the connection.
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
// / Returns the argument list with the script name removed, the argument count, the command,
// / the first target, the second target, a pre confirmed boolean & a sanity check, in that
// / order.
// /
// / This parses & it does not dispatch. It holds no list of commands & no opinion about
// / what any of them mean, which is why it can live here. The vocabulary belongs to whatever
// / calls it & every application has a different one.
// /
// / The script name is discarded & the count is taken afterwards. A count taken before the
// / shift reports one argument for an invocation that supplied none, & the caller's no
// / argument help path then never runs.
// /
// / A target may arrive two ways & both are accepted. A command of the form --config=/path
// / & one of the form --config /path mean the same thing. The equal sign form is resolved
// / first because the value is part of the same token, & the following argument is read only
// / when that token carried no value of its own.
// /
// / Every argument is lowercased, so a command line is case insensitive throughout.
// / An earlier version lowercased a target that arrived after an equal sign & left one that
// / arrived as a separate argument alone, so --update=NOW & --update NOW were two different
// / strings. Both are now the same.
// /
// / A target is not a path & that is why this is safe. Every path & every section name
// / reaches its consumer through extractCliOption, which lowercases nothing & says so. What
// / arrives here is a sub command, a role name, a version, a count, the word now, or a
// / startup key, & a startup key is a sha256 HMAC, which is lowercase hexadecimal already.
// / Adding a target that IS case sensitive means reading it with extractCliOption instead.
// / THAT LIST MISSED TWO, & both were found by running the commands rather than by this
// / argument. A pipeline name is a target, so its consumer matches it without regard to
// / case. An --app argument's values belong to another application & may be paths, so its
// / caller reads them again from the raw vector, which this returns first & unchanged.
// / A consumer of a target that is a name compares without regard to case. One that may
// / receive a path reads the raw vector.
// /
// / A -y or --yes anywhere in the vector pre confirms, rather than only in last position.
// / An operator who types it before the target means what one who types it after means.
// / It is matched case insensitively like everything else here, so -Y confirms too. An
// / earlier version lowercased the command & compared the confirmation flag exactly, so
// / --clean now -Y asked the operator a question they had already answered.
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
