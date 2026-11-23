<?php
// The HRProprietary Engine's tests. The library every suite loads first, from Documentation/Build/Tests.
// It was HRProtect's, from its v0.0.3, until the Engine gathered its tests here in v4.3.1.
//
// What it is for:
//   1. It refuses to let a suite run anywhere but inside contained.sh, & it finds out where it
//      is by looking, without writing anything to find out.
//   2. It stops a suite at the first warning or notice PHP raises. A variable that was never set
//      is a warning, so it can never go on to be used as a path.
//   3. It hands a suite every path the suite writes, & hands out none outside the two scratch folders.
//   4. It lifts real functions out of the tree under test, & refuses to lift one that removes, renames
//      or runs anything unless the suite names that call as one it expects.
//   5. It can lift them into a namespace, where a suite can stand in for a function the lifted code calls,
//      PHP's own included, for one check. It is how a suite makes happen, at the exact moment, what could
//      otherwise happen only by a race or a fault: a name swapped between two looks at it, a read that
//      stops short, an account that is not root. Not a byte of the lifted code is changed for it.
//   6. It can make PHP remember a path as leading somewhere else, from v0.0.6, with rememberElsewhere().
//      PHP keeps the path it last resolved a name to, & opens a file where the name last led.
//   7. It runs a suite inside contained-install.sh too, from the Engine's v4.3.2. There /DATA, /var/www/html & the folders PHP
//      reads its configuration from are memory made for the run, so a suite can install a whole application & run it. A suite
//      writes there only through mustBeInstallFolder(), & only inside contained-install.sh.
//
// What it does not have: a function that removes a file or a folder. No suite needs one. A suite makes
// new names & removes nothing, & what it made is memory that is gone when the contained run ends.
// Where a suite needs a name gone, or a folder begun again, setAside() moves what was there to another
// name on the same scratch filesystem. What was there is still there.
//
// A file of PHP in Documentation/Build is run by a web server that serves it, so this one, & every suite, refuses HTTP first.
// See --About Where They Live-- in Documentation/ABOUT_QUALITY_CONTROL.txt.

if (PHP_SAPI !== 'cli') { if (!headers_sent()) http_response_code(404); die(); }

error_reporting(E_ALL);
ini_set('display_errors', 'stderr');

$TestChecks = 0;
$TestFailures = 0;
$TestLog = array();
$TestScratch = '';
$TestOther = '';
$TestSetAside = 0;
$TestHooks = array();
$TestHookCalls = array();


// Stops the suite. Nothing after this line of a suite runs.
function testStop($reason) {
  fwrite(STDERR, 'STOPPED. '.$reason.PHP_EOL);
  exit(4); }

// A call written with @ is the code under test saying that call may fail. Anything else PHP raises stops the suite.
function testErrorHandler($errorNumber, $errorText, $errorFile, $errorLine) {
  if ((error_reporting() & $errorNumber) === 0) return TRUE;
  testStop('PHP raised "'.$errorText.'" at '.$errorFile.' line '.$errorLine.'. A suite never goes on past that.');
  return TRUE; }

function testExceptionHandler($thrown) {
  testStop('Nothing caught '.get_class($thrown).', "'.$thrown->getMessage().'", at '.$thrown->getFile().' line '.$thrown->getLine().'.'); }

set_error_handler('testErrorHandler');
set_exception_handler('testExceptionHandler');


// Says whether a path is named from the filesystem root in plain names, at least two of them.
// The filesystem root & a folder directly inside it are never a place a suite works in.
function testPathIsPlain($candidatePath) {
  return (is_string($candidatePath) && preg_match('#^(/[A-Za-z0-9][A-Za-z0-9._-]*){2,}$#D', $candidatePath) === 1); }

// Says whether a path is one of the folders named or lies inside one.
function testIsBeneath($candidatePath, $folders) {
  $isBeneath = FALSE;
  foreach ($folders as $folder) if ($candidatePath === $folder or strpos($candidatePath, $folder.'/') === 0) $isBeneath = TRUE;
  return $isBeneath; }

function testOctalToByte($found) {
  return chr((int)octdec($found[1])); }

// Reads the mounts this process can see, in the order the kernel lists them.
// Each is its mount point, its options & the kind of filesystem. Nothing is written to learn any of it.
function testMounts() {
  $mounts = array();
  $lines = @file('/proc/self/mountinfo', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  if (!is_array($lines) or count($lines) === 0) testStop('/proc/self/mountinfo could not be read, so nothing can be said about where this is running.');
  foreach ($lines as $line) {
    $halves = explode(' - ', $line, 2);
    $left = explode(' ', $halves[0]);
    $right = (count($halves) === 2) ? explode(' ', $halves[1]) : array();
    if (count($left) < 6 or count($right) < 1) testStop('A line of /proc/self/mountinfo is not laid out as expected: '.$line);
    $mounts[] = array('Point' => preg_replace_callback('/\\\\([0-7]{3})/', 'testOctalToByte', $left[4]), 'Options' => explode(',', $left[5]), 'Type' => $right[0]); }
  return $mounts; }

// Finds the mount a path is on. The longest mount point that holds the path wins, & of two alike the later one, which is the one on top.
function testMountOf($candidatePath) {
  $holding = array('Point' => '', 'Options' => array(), 'Type' => '');
  foreach (testMounts() as $mount) {
    if ($mount['Point'] !== '/' && !testIsBeneath($candidatePath, array($mount['Point']))) continue;
    if (strlen($mount['Point']) >= strlen($holding['Point'])) $holding = $mount; }
  return $holding; }

// Refuses to go on anywhere but inside contained.sh.
// It looks at every filesystem this process can see & asks the kernel whether it could be written.
// Asking changes nothing. Outside the contained run the filesystem root can be written, & that alone stops the suite.
function requireContainment() {
  global $TestScratch, $TestOther;
  $refusal = '';
  $ownFolders = array('/tmp', '/mnt', '/dev', '/proc', '/sys', '/run');
  $scratch = getenv('HRP_SCRATCH');
  $other = getenv('HRP_OTHER');
  if (getenv('HRP_CONTAINED') !== '1') $refusal = 'It was not started by contained.sh.';
  else if (!function_exists('posix_geteuid') or posix_geteuid() !== 0) $refusal = 'It is not running as root, & what it tests is root\'s to do.';
  else if (!testPathIsPlain($scratch) or !testPathIsPlain($other) or $scratch === $other) $refusal = 'contained.sh did not name two scratch folders.';
  else if (!testIsBeneath($scratch, array('/mnt')) or !testIsBeneath($other, array('/mnt'))) $refusal = 'The scratch folders are not beneath /mnt, where contained.sh puts them.';
  else if (is_writable('/')) $refusal = 'The filesystem root can be written here.';
  else {
    foreach (testMounts() as $mount) {
      if ($refusal !== '') break;
      // A mount point that is not a folder here is hidden beneath another, or is one file. Neither can be walked into.
      if (!is_dir($mount['Point']) or testIsBeneath($mount['Point'], $ownFolders)) continue;
      // Inside contained-install.sh, a folder an installation is made in is memory made for the run, & may be written.
      if (getenv('HRP_INSTALL') === '1' && $mount['Type'] === 'tmpfs' && testIsInstallFolder($mount['Point'])) continue;
      if (is_writable($mount['Point'])) $refusal = $mount['Point'].' can be written here.'; } }
  if ($refusal === '') {
    $rootStatus = @stat('/');
    $scratchStatus = @stat($scratch);
    $otherStatus = @stat($other);
    $scratchMount = testMountOf($scratch);
    $otherMount = testMountOf($other);
    if (!is_array($rootStatus) or !is_array($scratchStatus) or !is_array($otherStatus)) $refusal = 'A scratch folder does not exist.';
    else if (!is_dir($scratch) or is_link($scratch) or !is_dir($other) or is_link($other)) $refusal = 'A scratch folder is not a folder.';
    else if (!is_writable($scratch) or !is_writable($other)) $refusal = 'A scratch folder cannot be written.';
    else if ($scratchMount['Type'] !== 'tmpfs' or $otherMount['Type'] !== 'tmpfs' or $scratchMount['Point'] === '/' or $otherMount['Point'] === '/') $refusal = 'A scratch folder is not on a filesystem made for this run.';
    else if ($scratchStatus['dev'] === $rootStatus['dev'] or $otherStatus['dev'] === $rootStatus['dev']) $refusal = 'A scratch folder is on the filesystem the root is on.';
    else if ($scratchStatus['dev'] === $otherStatus['dev']) $refusal = 'The two scratch folders are on one filesystem, & a suite needs two.'; }
  if ($refusal !== '') testStop('This suite runs only inside contained.sh, where nothing but its own scratch folders can be written. '.$refusal.' Nothing was run. See Documentation/Build/Tests/README.txt.');
  $TestScratch = $scratch;
  $TestOther = $other;
  return $scratch; }

// Says whether a mount point is one contained-install.sh makes in memory for an installation: /DATA, /var/www/html, /var/lib/clamav,
// & each folder PHP reads its configuration from.
function testIsInstallFolder($mountPoint) {
  return in_array($mountPoint, array('/DATA', '/var/www/html', '/var/lib/clamav', '/usr/local/etc/php/conf.d'), TRUE) or preg_match('#^/etc/php/[^/]+/[^/]+/conf\.d$#D', $mountPoint) === 1; }

// Hands back a path only where it lies beneath /DATA or /var/www/html, each memory contained-install.sh made for the run, & only
// inside contained-install.sh. A suite that installs an application asks this before anything it runs writes there.
function mustBeInstallFolder($candidatePath) {
  $isInstall = (getenv('HRP_INSTALL') === '1' && is_string($candidatePath) && testIsBeneath($candidatePath, array('/DATA', '/var/www/html')) && !in_array($candidatePath, array('/DATA', '/var/www/html'), TRUE));
  if ($isInstall && (strpos($candidatePath, '/../') !== FALSE or substr($candidatePath, -3) === '/..' or strpos($candidatePath, '//') !== FALSE or strpos($candidatePath, "\0") !== FALSE)) $isInstall = FALSE;
  if ($isInstall && testMountOf($candidatePath)['Type'] !== 'tmpfs') $isInstall = FALSE;
  if (!$isInstall) testStop('A suite tried to write "'.(is_string($candidatePath) ? $candidatePath : gettype($candidatePath)).'", which is not beneath a folder contained-install.sh made for an installation.');
  return $candidatePath; }

// Checks the tree a suite was told to test, & refuses one the suite could change.
function testTree($treePath) {
  if (!testPathIsPlain($treePath)) testStop('Name the tree to test from the filesystem root, in plain names. See Documentation/Build/Tests/README.txt.');
  if (@realpath($treePath) !== $treePath) testStop($treePath.' is not where its name says. Name the tree by its own path, without a link in it.');
  if (!is_file($treePath.'/Resources/Kernel/kernel.php')) testStop($treePath.' holds no Resources/Kernel/kernel.php, so it is not a tree of an application built on the Engine.');
  if (is_writable($treePath) or is_writable($treePath.'/Resources/Kernel/kernel.php')) testStop($treePath.' can be written here, & a suite must not be able to change what it tests. See Documentation/Build/Tests/README.txt.');
  return $treePath; }

// Builds a path beneath a scratch folder out of plain names. A name that is not plain stops the suite.
function testPathBeneath($scratchFolder, $relativePath) {
  if (!is_string($scratchFolder) or $scratchFolder === '') testStop('A suite asked for a path before requireContainment() had named the scratch folders.');
  if (!is_string($relativePath) or preg_match('#^[A-Za-z0-9][A-Za-z0-9._-]*(/[A-Za-z0-9][A-Za-z0-9._-]*)*$#D', $relativePath) !== 1) testStop('A suite asked for the path "'.(is_string($relativePath) ? $relativePath : gettype($relativePath)).'", which is not plain names beneath a scratch folder.');
  return $scratchFolder.'/'.$relativePath; }

function scratchPath($relativePath) {
  global $TestScratch;
  return testPathBeneath($TestScratch, $relativePath); }

function otherPath($relativePath) {
  global $TestOther;
  return testPathBeneath($TestOther, $relativePath); }

// Hands back a path only where it lies beneath a scratch folder. Every helper that writes asks this first.
function mustBeScratch($candidatePath) {
  global $TestScratch, $TestOther;
  $isScratch = (is_string($candidatePath) && $TestScratch !== '' && $TestOther !== '' && testIsBeneath($candidatePath, array($TestScratch, $TestOther)) && $candidatePath !== $TestScratch && $candidatePath !== $TestOther);
  if ($isScratch && (strpos($candidatePath, '/../') !== FALSE or substr($candidatePath, -3) === '/..' or strpos($candidatePath, '//') !== FALSE or strpos($candidatePath, "\0") !== FALSE)) $isScratch = FALSE;
  if (!$isScratch) testStop('A suite tried to write "'.(is_string($candidatePath) ? $candidatePath : gettype($candidatePath)).'", which is not beneath its scratch folders.');
  return $candidatePath; }

// Makes a folder beneath a scratch folder, with the folders above it where they are missing, & gives it the mode asked for.
function makeFolder($folderPath, $folderMode) {
  mustBeScratch($folderPath);
  if (!is_dir($folderPath) && !mkdir($folderPath, 0755, TRUE)) testStop($folderPath.' could not be made.');
  if (!chmod($folderPath, $folderMode)) testStop($folderPath.' could not be given its mode.');
  clearstatcache();
  return $folderPath; }

// Writes a file beneath a scratch folder & gives it the mode asked for.
function writeScratchFile($filePath, $fileContents, $fileMode) {
  mustBeScratch($filePath);
  if (file_put_contents($filePath, $fileContents) !== strlen($fileContents)) testStop($filePath.' could not be written.');
  if (!chmod($filePath, $fileMode)) testStop($filePath.' could not be given its mode.');
  clearstatcache();
  return $filePath; }

// Moves a file, a link or a folder beneath a scratch folder to a new name in a folder of things set aside, on the same scratch filesystem.
// It is how a suite makes a name vanish or begins a folder again. Nothing is removed, & a link is moved & never followed.
function setAside($asidePath) {
  global $TestScratch, $TestOther, $TestSetAside;
  mustBeScratch($asidePath);
  $asideFolder = (testIsBeneath($asidePath, array($TestOther)) ? $TestOther : $TestScratch).'/set-aside';
  if (testIsBeneath($asidePath, array($asideFolder))) testStop('A suite tried to set aside "'.$asidePath.'", which was set aside already.');
  if (!is_dir($asideFolder) && !mkdir($asideFolder, 0700)) testStop($asideFolder.' could not be made.');
  $TestSetAside++;
  if (!rename($asidePath, $asideFolder.'/'.$TestSetAside)) testStop($asidePath.' could not be set aside.');
  clearstatcache();
  return $asideFolder.'/'.$TestSetAside; }

// Records one check. Only TRUE passes, so a check that is merely not empty does not pass by accident.
// A mutation run asks for a suite to end at its first failed check, with HRP_STOP_AT_FIRST_FAILURE, because one is enough to kill a mutant.
function check($label, $passed, $detail = '') {
  global $TestChecks, $TestFailures;
  $TestChecks++;
  if ($passed !== TRUE) {
    $TestFailures++;
    fwrite(STDOUT, 'FAIL  '.$label.($detail !== '' ? '  ['.$detail.']' : '').PHP_EOL);
    if (getenv('HRP_STOP_AT_FIRST_FAILURE') === '1') exit(1); } }

// Says how the suite went & ends it. The exit status is 0 only where every check passed.
function finish($suiteName) {
  global $TestChecks, $TestFailures;
  fwrite(STDOUT, $suiteName.': '.$TestChecks.' checks, '.($TestChecks - $TestFailures).' passed, '.$TestFailures.' failed.'.PHP_EOL);
  exit($TestFailures === 0 ? 0 : 1); }

// Gives a field of a list, or a word nothing else is, so a check can compare without PHP raising anything.
function field($list, $key) {
  return (is_array($list) && array_key_exists($key, $list)) ? $list[$key] : '<no such field>'; }


// The calls no lifted function may make unless a suite says it expects them.
function forbiddenCalls() {
  return array('unlink', 'rmdir', 'rename', 'exec', 'shell_exec', 'system', 'passthru', 'popen', 'proc_open', 'pcntl_exec', 'removetreewithoutfollowing', 'removefilewithoutfollowing'); }

// Names the forbidden calls a run of tokens makes. A name is a call where an opening bracket follows it.
function forbiddenCallsIn($tokens, $expectedCalls) {
  $found = array();
  $forbidden = array_diff(forbiddenCalls(), array_map('strtolower', $expectedCalls));
  $tokenCount = count($tokens);
  for ($index = 0; $index < $tokenCount; $index++) {
    $token = $tokens[$index];
    if ($token === '`') $found['a command between backticks'] = TRUE;
    if (is_array($token) && $token[0] === T_EVAL) $found['eval'] = TRUE;
    if (!is_array($token) or !in_array($token[0], array(T_STRING, T_NAME_FULLY_QUALIFIED, T_NAME_QUALIFIED), TRUE)) continue;
    $name = strtolower(ltrim($token[1], '\\'));
    if (!in_array($name, $forbidden, TRUE)) continue;
    $next = $index + 1;
    while ($next < $tokenCount && is_array($tokens[$next]) && in_array($tokens[$next][0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT), TRUE)) $next++;
    if ($next < $tokenCount && $tokens[$next] === '(') $found[$name] = TRUE; }
  return array_keys($found); }

// Lifts functions out of a file of the tree & defines them here, each exactly as the tree has it.
// A function that is already defined, one the file does not hold & one that makes a forbidden call each stop the suite.
function liftFunctions($sourceFile, $functionNames, $expectedCalls) {
  liftFunctionsInto('', $sourceFile, $functionNames, $expectedCalls); }

// Lifts functions as liftFunctions() does, into a namespace where one is named, & beside everything else where none is.
// PHP looks for a function that is called without a namespace in the caller's own namespace first, & only then among
// its own & the suite's. So a function lifted into a namespace calls a stand-in of that namespace wherever
// standInFor() made one, & the real function everywhere else. Each function is still exactly as the tree has it.
function liftFunctionsInto($namespaceName, $sourceFile, $functionNames, $expectedCalls) {
  if (!is_string($namespaceName) or ($namespaceName !== '' && preg_match('/^[A-Za-z][A-Za-z0-9]*$/D', $namespaceName) !== 1)) testStop('A suite named a namespace that is not one plain word.');
  $source = @file_get_contents($sourceFile);
  if (!is_string($source) or $source === '') testStop($sourceFile.' could not be read.');
  $tokens = token_get_all($source);
  $tokenCount = count($tokens);
  foreach ($functionNames as $functionName) {
    $definedName = ($namespaceName === '' ? '' : $namespaceName.'\\').$functionName;
    if (function_exists($definedName)) testStop('The function '.$definedName.' is already defined, & a suite never puts one in the place of another.');
    $start = -1;
    for ($index = 0; $index < $tokenCount && $start < 0; $index++) {
      if (!is_array($tokens[$index]) or $tokens[$index][0] !== T_FUNCTION) continue;
      $next = $index + 1;
      while ($next < $tokenCount && is_array($tokens[$next]) && $tokens[$next][0] === T_WHITESPACE) $next++;
      if ($next < $tokenCount && is_array($tokens[$next]) && $tokens[$next][0] === T_STRING && $tokens[$next][1] === $functionName) $start = $index; }
    if ($start < 0) testStop($sourceFile.' defines no function '.$functionName.'.');
    $depth = 0;
    $end = -1;
    for ($index = $start; $index < $tokenCount && $end < 0; $index++) {
      $token = $tokens[$index];
      if ($token === '{' or (is_array($token) && ($token[0] === T_CURLY_OPEN or $token[0] === T_DOLLAR_OPEN_CURLY_BRACES))) $depth++;
      else if ($token === '}') {
        $depth--;
        if ($depth === 0) $end = $index; } }
    if ($end < 0) testStop('The end of the function '.$functionName.' was not found in '.$sourceFile.'.');
    $piece = array_slice($tokens, $start, $end - $start + 1);
    $calls = forbiddenCallsIn($piece, $expectedCalls);
    if (count($calls) > 0) testStop('The function '.$functionName.' in '.$sourceFile.' calls '.implode(', ', $calls).', & this suite did not say it expects that. It was not lifted & nothing was run.');
    $text = '';
    foreach ($piece as $token) $text .= is_array($token) ? $token[1] : $token;
    eval(($namespaceName === '' ? '' : 'namespace '.$namespaceName.'; ').$text);
    if (!function_exists($definedName)) testStop('The function '.$functionName.' could not be lifted out of '.$sourceFile.'.'); } }

// Defines a stand-in, in a namespace, for each function named. A stand-in hands its call to hookedCall().
// A function that takes an argument by reference is not one to name here, because a stand-in is handed copies.
function standInFor($namespaceName, $functionNames) {
  if (!is_string($namespaceName) or preg_match('/^[A-Za-z][A-Za-z0-9]*$/D', $namespaceName) !== 1) testStop('A suite named a namespace that is not one plain word.');
  foreach ($functionNames as $functionName) {
    if (!is_string($functionName) or preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $functionName) !== 1 or !function_exists($functionName)) testStop('A suite asked for a stand-in for something that is not a function here.');
    if (function_exists($namespaceName.'\\'.$functionName)) testStop('The function '.$namespaceName.'\\'.$functionName.' is already defined, & a suite never puts one in the place of another.');
    eval('namespace '.$namespaceName.'; function '.$functionName.'(...$arguments) { return \\hookedCall(\''.$functionName.'\', $arguments); }'); } }

// What a stand-in does. It counts the call, asks the suite's hook for that function where there is one, & otherwise calls the real function.
// A hook is handed the arguments & how many times the function has been called since the hooks were last set.
// It answers nothing to let the real call go ahead, once it has done whatever the check needs done at that moment,
// or it answers array('answer', value) to have that value handed back in the place of the real call.
function hookedCall($functionName, $arguments) {
  global $TestHooks, $TestHookCalls;
  $TestHookCalls[$functionName] = (isset($TestHookCalls[$functionName]) ? $TestHookCalls[$functionName] : 0) + 1;
  if (isset($TestHooks[$functionName])) {
    $answer = call_user_func($TestHooks[$functionName], $arguments, $TestHookCalls[$functionName]);
    if (is_array($answer) && count($answer) === 2 && array_key_exists(0, $answer) && $answer[0] === 'answer') return $answer[1]; }
  return call_user_func_array($functionName, $arguments); }

// Sets the hooks for the checks that follow, & begins the count of calls again. An empty list takes every hook away.
function setHooks($hooks) {
  global $TestHooks, $TestHookCalls;
  $TestHooks = $hooks;
  $TestHookCalls = array();
  return TRUE; }


// Makes PHP remember a path as leading somewhere else, from HRProtect's v0.0.6. PHP keeps the path it last resolved a name to, & fopen(),
// file_get_contents() & hash_file() go where the name last led for as long as it does. test_kernel-cli.php measures that on the PHP that runs it.
// What is at the name is set aside, a link to somewhere else takes its place while PHP resolves it, & then the link is moved away & what was
// there is moved back. Another process does those two moves, with mv & without a shell, because PHP forgets every path when it renames
// anything itself. Nothing is removed. Returns whether PHP now takes the name to lead elsewhere, which it does unless it is set to remember no path.
function rememberElsewhere($path, $elsewhere) {
  global $TestSetAside;
  mustBeScratch($path);
  mustBeScratch($elsewhere);
  $keptAt = setAside($path);
  if (!symlink($elsewhere, $path)) testStop('A link could not be made at '.$path.'.');
  $resolvedTo = realpath($path);
  $TestSetAside++;
  list ($awayStatus, $awaySaid) = runCommand(array('mv', '-T', $path, dirname($keptAt).'/'.$TestSetAside));
  list ($backStatus, $backSaid) = runCommand(array('mv', '-T', $keptAt, $path));
  if ($resolvedTo !== $elsewhere or $awayStatus !== 0 or $backStatus !== 0) testStop('What was at '.$path.' could not be put back after PHP resolved a link there. '.$awaySaid.$backSaid);
  return (realpath($path) === $elsewhere); }

// Says whether this PHP remembers the paths it resolves, by trying it once. It does unless it is set to remember none,
// & a check that depends on it holds the PHP that runs it to whichever it is.
function phpRemembersPaths() {
  static $remembers = NULL;
  if ($remembers === NULL) {
    $folder = makeFolder(scratchPath('php-remembers'), 0700);
    $remembers = rememberElsewhere(writeScratchFile($folder.'/name', 'the name', 0600), writeScratchFile($folder.'/elsewhere', 'elsewhere', 0600)); }
  return $remembers; }


// What the kernel's log functions are here: a list a suite can read. Nothing is written anywhere.
function logEntry($entry) {
  global $TestLog;
  $TestLog[] = 'Op-Act: '.$entry;
  return TRUE; }

function warningEntry($entry) {
  global $TestLog;
  $TestLog[] = 'WARNING!!! '.$entry;
  return TRUE; }

// An error that would have ended the command ends the suite, unless the suite catches it. Nothing the code under test does after one ever runs.
function errorEntry($entry, $errorNumber, $die) {
  global $TestLog;
  $TestLog[] = 'ERROR!!! '.$errorNumber.', '.$entry;
  if ($die === TRUE) throw new RuntimeException('The code under test raised error '.$errorNumber.' as one that ends the command: '.$entry);
  return TRUE; }

function applicationLabel() {
  return 'Application'; }

// Names the application's entry point, as its contract declares it in $EngineEntryPoint, or app.php where it declares none, as the Engine does.
function testEntryPoint($treePath) {
  $contract = (string)@file_get_contents($treePath.'/Resources/Contract/app-engine-config.php');
  return (preg_match('/^\$EngineEntryPoint\s*=\s*\'([A-Za-z0-9._-]+\.php)\'\s*;/m', $contract, $match) === 1) ? $match[1] : 'app.php'; }


// Starts a command without a shell. Standard error joins standard output, so one pipe holds everything it says.
function startCommand($commandParts) {
  $pipes = array();
  $process = proc_open($commandParts, array(0 => array('file', '/dev/null', 'r'), 1 => array('pipe', 'w'), 2 => array('redirect', 1)), $pipes);
  if (!is_resource($process)) testStop('The command '.implode(' ', $commandParts).' could not be started.');
  return array($process, $pipes[1]); }

// Waits for a command startCommand() began. Returns its exit status & what it said, in that order.
function waitCommand($started) {
  $said = (string)stream_get_contents($started[1]);
  fclose($started[1]);
  $status = proc_close($started[0]);
  return array($status, $said); }

function runCommand($commandParts) {
  return waitCommand(startCommand($commandParts)); }
