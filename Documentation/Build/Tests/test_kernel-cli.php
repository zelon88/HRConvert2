<?php
// The HRProprietary Engine v4.3.1. The unit suite of what HRProtect's v0.0.6 changed in the kernel & the Engine, which is the Engine's now:
// an application's argument, sanitizePath(), printableText(), a worker of the application's own & a file the kernel opens.
// It was HRProtect's own suite until v4.3.1, & every check is as HRProtect's v0.0.7 held it.
//
//   sudo bash Documentation/Build/Tests/contained.sh php Documentation/Build/Tests/test_kernel-cli.php [/path/to/the/tree /path/to/an/earlier/Engine]
// Run it from the root of the application under test. A tree named after it is tested instead, & must be that root.
//
// Named an earlier Engine's tree as well, it also checks that sanitize() & sanitizeString() are that Engine's, unchanged, & that the
// Workflow Core's printableWorkflowText() returns what that Engine's returned. That tree is read, & that one function of it is run.
// It starts short processes that end by themselves: workers of an entry point it writes into the scratch folder.
// It runs ls, to read which files a command this process runs is handed, & mv, to have another process change a name. It removes nothing.
// Every check but those marked From HRProtect's v0.0.6 is the one HRProtect's suite held before it was moved onto its testlib.php.
// Those hold what a change by rule to every line of the functions showed was not held. For some of them startApplicationWorker()
// is lifted a second time, into a namespace, where a check stands in for a function it calls. See testlib-cli.php.

if (PHP_SAPI !== 'cli') { if (!headers_sent()) http_response_code(404); die(); }

if (!is_file('Documentation/Build/Tests/testlib-cli.php')) { fwrite(STDERR, 'Run this from the root of the application it tests. See Documentation/Build/Tests/README.txt.'.PHP_EOL); exit(64); }
require 'Documentation/Build/Tests/testlib-cli.php';

$tree = testTree(isset($argv[1]) && $argv[1] !== '' ? $argv[1] : (string)getcwd());
$kit = (isset($argv[2]) && $argv[2] !== '') ? testTree($argv[2]) : '';
requireContainment();

// What the kernel would have set before any of these functions ran.
$EnableMemoryProtection = TRUE;
$Verbose = FALSE;
$LogFile = '';
$RunningAsRoot = TRUE;
$DirSep = DIRECTORY_SEPARATOR;
$Lol = PHP_EOL;

// Gives the text of one function of a file, or nothing where the file does not hold it.
function functionText($sourceFile, $functionName) {
  $found = preg_match('/^function '.preg_quote($functionName, '/').'\(.*?^\/\/ \/ -{83}$/ms', (string)@file_get_contents($sourceFile), $match);
  return ($found === 1) ? $match[0] : ''; }

// Lifts one function under another name, so that the tree's & the earlier Engine's can be asked the same thing. One that removes, renames or runs anything is not lifted.
function liftAs($sourceFile, $functionName, $newName) {
  $text = functionText($sourceFile, $functionName);
  if ($text === '') testStop($sourceFile.' defines no function '.$functionName.'.');
  $calls = forbiddenCallsIn(token_get_all('<?php '.$text), array());
  if (count($calls) > 0) testStop('The function '.$functionName.' in '.$sourceFile.' calls '.implode(', ', $calls).'. It was not lifted & nothing was run.');
  if (function_exists($newName)) testStop('The function '.$newName.' is already defined.');
  eval(str_replace('function '.$functionName.'(', 'function '.$newName.'(', $text)); }

liftFunctions($tree.'/Resources/Kernel/kernel.php', array('getChildFunction', 'purgeSensitiveMemory', 'runApplicationArgument', 'sanitizeString', 'sanitize', 'sanitizePath', 'printableText'), array());
$Seen = array();
$Return = NULL;
function testHandler($command, $target, $secondTarget) { global $Seen, $Return; $Seen = array($command, $target, $secondTarget); return $Return; }
function handlerWithoutReturn($command, $target, $secondTarget) { global $Seen; $Seen = array($command, $target, $secondTarget); }
function run($arguments, $return) { global $Return, $TestLog; $Return = $return; $TestLog = array(); ob_start(); $status = runApplicationArgument($arguments, strtolower(explode('=', $arguments[0])[0])); ob_end_clean(); return $status; }
$EngineApplicationArgumentHandler = 'testHandler';
check('a status the handler returns is the status', run(array('--app-x', 'A', 'B'), 3) === 3);
check('0 is a status', run(array('--app-x'), 0) === 0);
check('255 is a status', run(array('--app-x'), 255) === 255);
check('256 is not, & the command is reported as failed', run(array('--app-x'), 256) === 1 && count(preg_grep('/returned something other than an exit status/', $TestLog)) === 1);
check('a negative number is not', run(array('--app-x'), -1) === 1);
check('TRUE is not a status', run(array('--app-x'), TRUE) === 1);
check('text is not a status', run(array('--app-x'), '3') === 1);
$EngineApplicationArgumentHandler = 'handlerWithoutReturn';
check('a handler that returns nothing has succeeded', run(array('--app-x', 'A'), NULL) === 0 && count(preg_grep('/WARNING/', $TestLog)) === 0);
$EngineApplicationArgumentHandler = 'testHandler';
run(array('--app-x', '/A Path', 'Second'), 0);
check('two targets after the command', $Seen === array('--app-x', '/A Path', 'Second'), json_encode($Seen));
run(array('--app-x=/A Path', 'Second', 'Third'), 0);
check('the second target follows a first written after an equal sign', $Seen === array('--app-x', '/A Path', 'Second'), json_encode($Seen));
run(array('--app-x=/A=B'), 0);
check('only the first equal sign divides', $Seen === array('--app-x', '/A=B', ''), json_encode($Seen));
run(array('--app-x'), 0);
check('no targets are two empty targets', $Seen === array('--app-x', '', ''), json_encode($Seen));
$EngineApplicationArgumentHandler = '';
check('no handler is status 2', run(array('--app-x'), 0) === 2);
$EngineApplicationArgumentHandler = 'noSuchFunction';
check('a handler that does not exist is status 2', run(array('--app-x'), 0) === 2);
// From HRProtect's v0.0.6. One target alone, the two warnings as warnings, & a handler that is not text.
$EngineApplicationArgumentHandler = 'testHandler';
run(array('--app-x', 'Only'), 0);
check('one target after the command is the first target, & the second is empty', $Seen === array('--app-x', 'Only', ''), json_encode($Seen));
run(array('--app-x'), 256);
check('a status that is none is a warning in the log, & names the handler', in_array('WARNING!!! The handler testHandler returned something other than an exit status for --app-x, so the command is reported as failed.', $TestLog, TRUE), implode(' | ', $TestLog));
$EngineApplicationArgumentHandler = '';
run(array('--app-x'), 0);
check('no handler is a warning in the log', in_array('WARNING!!! The application argument --app-x was supplied & this application declares no handler.', $TestLog, TRUE), implode(' | ', $TestLog));
$EngineApplicationArgumentHandler = array('testHandler');
$Seen = array();
check('a handler that is not text is no handler, & nothing is called', run(array('--app-x'), 0) === 2 && $Seen === array());
$EngineApplicationArgumentHandler = 'testHandler';

// A path is accepted whole or refused whole.
function path($value, $strict) { list ($path, $accepted) = sanitizePath($value, $strict); return array($path, $accepted); }
foreach (array('/var/www/html/index.php', '/srv/a folder/[x] (1) {y}; $z & more.PHP', 'relative/../path', '.', '..', './a', "/caf\xc3\xa9/\xe6\x97\xa5\xe6\x9c\xac", "/latin\xe9/name", '/a:b/c', '/mirror/http://example.com/page', '~/notes', '-rf', '/a//b/', str_repeat('a', 4094)) as $good) check('accepted as given: '.substr($good, 0, 40), path($good, FALSE) === array($good, TRUE));
check('a number is a path', path(123, FALSE) === array('123', TRUE));
check('nothing is nothing, & is not a refusal', path('', FALSE) === array('', TRUE) && path('', TRUE) === array('', TRUE));
foreach (array("line\nbreak" => 'a line break', "nul\0byte" => 'a null byte', "tab\there" => 'a tab', "escape\x1b[2J" => 'an escape', "delete\x7f" => 'a delete', "c1\xc2\x9bcontrol" => 'a C1 control', 'phar:///srv/x.phar' => 'phar', 'PHP://filter/resource=/etc/passwd' => 'php in capitals', 'http://example.com/x' => 'http', 'file:///etc/passwd' => 'file', 'data:text/plain,hello' => 'data', 'compress.zlib://x.gz' => 'a wrapper with a dot', 'ssh2.sftp://host/x' => 'a wrapper with a digit', str_repeat('a', 4095) => 'a path longer than PHP opens') as $bad => $why) check('refused whole: '.$why, path($bad, FALSE) === array('', FALSE));
// What PHP takes for a stream is measured here, on the PHP that runs the test, & everything it takes for one must be refused.
class StreamProbe { public $context; public static $seen = 0;
  public function stream_open($path, $mode, $options, &$openedPath) { self::$seen++; return FALSE; }
  public function url_stat($path, $flags) { self::$seen++; return FALSE; } }
foreach (array('p', 'ab', '1ab', '9x', '+a', '-a', '.a', '..', 'a.b') as $scheme) stream_wrapper_register($scheme, 'StreamProbe');
$phpNotes = array();
function phpTakesForStream($candidate) {
  global $phpNotes;
  StreamProbe::$seen = 0; $phpNotes = array();
  set_error_handler(function ($number, $message) { global $phpNotes; $phpNotes[] = $message; return TRUE; });
  $handle = fopen($candidate, 'r');
  restore_error_handler();
  $isStream = (StreamProbe::$seen > 0 or count(preg_grep('/Unable to find the wrapper|phar error|operation failed|does not support|not supported/', $phpNotes)) > 0);
  if (is_resource($handle)) { $meta = stream_get_meta_data($handle); $isStream = ($meta['wrapper_type'] !== 'plainfile'); fclose($handle); }
  return $isStream; }
$streams = array('ab://x', 'AB://x', '1ab://x', '9x://x', '+a://x', '-a://x', '.a://x', '..://x', 'a.b://x', 'zz://x', 'phar://x', 'PhAr://x', 'data:text/plain,hi', 'data://text/plain,hi', 'DATA://text/plain,hi', 'file://x', 'glob://*', 'compress.zlib://x', 'php://memory');
foreach ($streams as $stream) check('PHP takes '.$stream.' for a stream, & it is refused', phpTakesForStream($stream) && path($stream, FALSE) === array('', FALSE));
$names = array('p://x', 'z://x', 'a_b://x', 'ab:/x', 'ab:x', './ab://x', '/ab://x', ' ab://x', './phar://x', './data:x', 'datax:foo', 'dat:foo', 'file:x', 'php:x', 'a b://x');
foreach ($names as $name) check('PHP takes '.$name.' for a file name, & it is accepted', !phpTakesForStream($name) && path($name, FALSE) === array($name, TRUE));
check('data: is refused in any case, though PHP reads only the lower case as a stream', !phpTakesForStream('Data:text/plain,hi') && path('Data:text/plain,hi', FALSE) === array('', FALSE) && path('DATA:x', FALSE) === array('', FALSE));
// How long a path PHP opens is measured the same way. It refuses one of PHP_MAXPATHLEN - 1 bytes & longer without asking the system.
function longPath($length) { $path = ''; while (strlen($path) < $length) $path .= '/'.str_repeat('a', min(200, $length - strlen($path) - 1)); return substr($path, 0, $length); }
function phpRefusesLength($length) {
  global $phpNotes;
  $phpNotes = array();
  set_error_handler(function ($number, $message) { global $phpNotes; $phpNotes[] = substr($message, -40); return TRUE; });
  fopen(longPath($length), 'r');
  restore_error_handler();
  return count(preg_grep('/Invalid argument/', $phpNotes)) > 0; }
check('PHP asks the system for a path of PHP_MAXPATHLEN - 2 bytes, & refuses one byte more itself', !phpRefusesLength(PHP_MAXPATHLEN - 2) && phpRefusesLength(PHP_MAXPATHLEN - 1) && phpRefusesLength(PHP_MAXPATHLEN));
check('so the longest path PHP opens is accepted', path(longPath(PHP_MAXPATHLEN - 2), TRUE) === array(longPath(PHP_MAXPATHLEN - 2), TRUE));
check('& one byte more is refused', path(longPath(PHP_MAXPATHLEN - 1), TRUE) === array('', FALSE) && path(longPath(PHP_MAXPATHLEN - 1), FALSE) === array('', FALSE) && path(longPath(PHP_MAXPATHLEN + 904), FALSE) === array('', FALSE));
foreach (array(array('/a'), NULL, TRUE, 1.5e400, new stdClass()) as $index => $notPath) check('not a path '.$index, path($notPath, FALSE) === array('', FALSE) or ($index === 3 && path($notPath, FALSE)[1] === TRUE));
foreach (array('/', '/a', '/a/b c/d.php', '/a/.hidden/b..c/...') as $plain) check('strict accepts '.$plain, path($plain, TRUE) === array($plain, TRUE));
foreach (array('a/b' => 'relative', './a' => 'a leading dot', '/a/../b' => 'two dots', '/a/./b' => 'a dot', '/a//b' => 'an empty part', '/a/b/' => 'a trailing separator', '..' => 'two dots alone', "/a\nb" => 'a control character', 'phar:///a' => 'a wrapper') as $bad => $why) check('strict refuses '.$why, path($bad, TRUE) === array('', FALSE));
check('strict is the default for anything that is not a boolean', path('a/b', 'no') === array('', FALSE) && path('a/b', 0) === array('', FALSE) && path('/a/b', NULL) === array('/a/b', TRUE));
check('sanitize() itself still removes what a name must not hold', sanitize('/a b/c.php', FALSE) === array('/a_b/c', TRUE) && sanitize('--app-scan', TRUE) === array('app-scan', TRUE) && sanitize('20261116-064500-0123456789ab', TRUE) === array('20261116-064500-0123456789ab', TRUE));
if ($kit !== '') {
  $kitKernel = $kit.'/Resources/Kernel/kernel.php';
  check('sanitize() is the earlier Engine\'s, unchanged', functionText($kitKernel, 'sanitize') !== '' && functionText($tree.'/Resources/Kernel/kernel.php', 'sanitize') === functionText($kitKernel, 'sanitize') && functionText($kitKernel, 'sanitizeString') !== '' && functionText($tree.'/Resources/Kernel/kernel.php', 'sanitizeString') === functionText($kitKernel, 'sanitizeString')); }

// Text nobody vetted is made printable by the kernel, from v4.2.5.
check('a control character is shown as a question mark', printableText("a\r\nb\x1b[0m\x00\x7f", TRUE) === 'a??b?[0m??' && printableText("tab\there", FALSE) === 'tab?here');
check('a C1 control character is replaced, & a letter beside it is kept', printableText("a\xc2\x9bb\xc3\xa9", TRUE) === "a?b\xc3\xa9" && printableText("\xc2\x80\xc2\x9f\xc2\xa0", TRUE) === "??\xc2\xa0");
check('text that is not UTF-8 has every byte outside printable ASCII replaced', printableText("bad\xff\xfe ok\x9b~", TRUE) === 'bad?? ok?~' && printableText("caf\xe9", FALSE) === 'caf?');
check('text that is UTF-8 is kept', printableText("caf\xc3\xa9 \xe6\x97\xa5\xe6\x9c\xac \xf0\x9f\x98\x80", TRUE) === "caf\xc3\xa9 \xe6\x97\xa5\xe6\x9c\xac \xf0\x9f\x98\x80");
check('strict cuts at 1000 bytes & says so', printableText(str_repeat('x', 1000), TRUE) === str_repeat('x', 1000) && printableText(str_repeat('x', 1001), TRUE) === str_repeat('x', 1000).'...' && strlen(printableText(str_repeat('x', 3000), TRUE)) === 1003);
check('without strict nothing is cut', printableText(str_repeat('x', 3000), FALSE) === str_repeat('x', 3000));
foreach (array(1 => 2, 2 => 2, 3 => 3, 4 => 3, 5 => 4, 6 => 4, 7 => 4) as $case => $width) {
  $character = array(2 => "\xc3\xa9", 3 => "\xe6\x97\xa5", 4 => "\xf0\x9f\x98\x80")[$width];
  $text = str_repeat('x', 1000 - $case % $width - ($case > 4 ? 1 : 0)).str_repeat($character, 40);
  $shown = printableText($text, TRUE);
  check('a cut never falls inside a character, case '.$case, substr($shown, -3) === '...' && preg_match('//u', $shown) === 1 && strlen($shown) <= 1003 && strlen($shown) > 1003 - $width && strpos($text, substr($shown, 0, -3)) === 0, strlen($shown).' bytes'); }
check('strict is the default for anything that is not a boolean', strlen(printableText(str_repeat('x', 3000), 'no')) === 1003 && strlen(printableText(str_repeat('x', 3000), NULL)) === 1003 && strlen(printableText(str_repeat('x', 3000), 0)) === 1003);
check('what is not text is shown as nothing, & raises nothing', printableText(array("a\n"), TRUE) === '' && printableText(new stdClass(), TRUE) === '' && printableText(NULL, TRUE) === '' && printableText(12.5, TRUE) === '12.5' && printableText(TRUE, FALSE) === '1' && printableText(FALSE, FALSE) === '');
// What each of HRProtect's four copies did until its v0.0.2, kept here to compare with. It needs mbstring, which the copies did.
function formerPrintable($untrustedText) {
  $text = (string)$untrustedText;
  if (preg_match('//u', $text) === 1) $text = (string)preg_replace('/[\x00-\x1F\x7F\x{80}-\x{9F}]/u', '?', $text);
  else $text = (string)preg_replace('/[^\x20-\x7E]/', '?', $text);
  if (strlen($text) > 1000) $text = mb_strcut($text, 0, 1000, 'UTF-8').'...';
  return $text; }
mt_srand(20261116);
$samples = array('', ' ', "\x00", str_repeat("\xf0\x9f\x98\x80", 400), str_repeat("\xc3\xa9", 499).'x'.str_repeat("\xe6\x97\xa5", 9), str_repeat("\xff", 2000), str_repeat('a', 999)."\xc3\xa9\xc3\xa9", str_repeat('a', 998)."\xe6\x97\xa5z");
$alphabet = array('a', 'Z', ' ', '/', '.', "\n", "\r", "\x1b", "\x7f", "\x00", "\xc3\xa9", "\xe6\x97\xa5", "\xf0\x9f\x98\x80", "\xc2\x9b", "\xc2\x85", "\xff", "\xc3", "\xa9", "\xe6\x97", "\xed\xa0\x80", "\xc0\xaf");
for ($i = 0; $i < 4000; $i++) { $sample = ''; $length = mt_rand(0, $i % 10 === 0 ? 1400 : 40); for ($j = 0; $j < $length; $j++) $sample .= $alphabet[mt_rand(0, count($alphabet) - 1)]; $samples[] = $sample; }
$differing = 0;
if (function_exists('mb_strcut')) foreach ($samples as $sample) if (printableText($sample, TRUE) !== formerPrintable($sample)) $differing++;
check('strict shows every one of '.count($samples).' samples as the four copies did', function_exists('mb_strcut') && $differing === 0, $differing.' differ');
if ($kit !== '' && is_file($kit.'/Resources/Engine/Cores/workflowCore.php')) {
  liftAs($kit.'/Resources/Engine/Cores/workflowCore.php', 'printableWorkflowText', 'kitPrintableWorkflowText');
  liftAs($tree.'/Resources/Engine/Cores/workflowCore.php', 'printableWorkflowText', 'treePrintableWorkflowText');
  $differing = 0;
  foreach ($samples as $sample) if (treePrintableWorkflowText($sample) !== kitPrintableWorkflowText($sample)) $differing++;
  check('the Workflow Core shows every one of '.count($samples).' samples as the earlier Engine\'s did', $differing === 0, $differing.' differ');
  check('& cuts nothing, as the earlier Engine\'s did not', treePrintableWorkflowText(str_repeat('x', 3000)) === str_repeat('x', 3000) && treePrintableWorkflowText(123) === kitPrintableWorkflowText(123) && treePrintableWorkflowText(NULL) === kitPrintableWorkflowText(NULL)); }
else if (is_file($tree.'/Resources/Engine/Cores/workflowCore.php')) {
  // Without an earlier Engine, the Workflow Core's own is still held to cutting nothing, which is what a mutation run needs.
  liftFunctions($tree.'/Resources/Engine/Cores/workflowCore.php', array('printableWorkflowText'), array());
  check('the Workflow Core cuts nothing', printableWorkflowText(str_repeat('x', 3000)) === str_repeat('x', 3000) && printableWorkflowText("a\x1b\xc2\x9b") === 'a??'); }

// The Engine starts a worker of the application's own, from v4.2.5. The entry point here is a script that writes down how it was started.
if (is_file($tree.'/Resources/Engine/engine.php')) {
  // The kernel finds a program by asking a shell for command -v, with the name quoted. Here it is asked for the programs a worker is started with.
  liftFunctions($tree.'/Resources/Kernel/kernel.php', array('locateDependency'), array('shell_exec'));
  // The Engine starts a worker with proc_open(), through setsid. That is what is under test here, & the worker is the entry point written below.
  liftFunctions($tree.'/Resources/Engine/engine.php', array('startApplicationWorker'), array('proc_open'));
  function engineApplicationEntryPoint() { return 'entry.php'; }
  $InstLoc = makeFolder(scratchPath('worker'), 0700);
  writeScratchFile($InstLoc.'/entry.php', '<?php if ($argv[2] === "job-1") usleep(1500000); file_put_contents(__DIR__."/started-".$argv[2].".json", json_encode(array("argv" => $argv, "pid" => getmypid(), "sid" => posix_getsid(0), "parent" => posix_getppid(), "stdin" => @readlink("/proc/self/fd/0"), "stdout" => @readlink("/proc/self/fd/1"), "cwd" => getcwd())));', 0600);
  function startedAs($value) { global $InstLoc; for ($i = 0; $i < 60 && !is_file($InstLoc.'/started-'.$value.'.json'); $i++) usleep(100000); return json_decode((string)@file_get_contents($InstLoc.'/started-'.$value.'.json'), TRUE); }
  $began = microtime(TRUE);
  list ($started, $notice) = startApplicationWorker('--app-test-worker', 'job-1');
  $returnedAfter = microtime(TRUE) - $began;
  $worker = startedAs('job-1');
  check('a worker is started, & the caller does not wait for it', $started === TRUE && $notice === '' && $returnedAfter < 1.0 && is_array($worker), 'returned after '.round($returnedAfter, 2).'s '.$notice);
  check('it is the entry point, handed the argument & the value & nothing else', is_array($worker) && array_slice($worker['argv'], 1) === array('--app-test-worker', 'job-1') && basename($worker['argv'][0]) === 'entry.php' && $worker['cwd'] === $InstLoc);
  check('it leads a session of its own, & this process is not its parent', is_array($worker) && $worker['sid'] === $worker['pid'] && $worker['sid'] !== posix_getsid(0) && $worker['parent'] !== getmypid());
  check('it has no terminal to read or to write', is_array($worker) && $worker['stdin'] === '/dev/null' && $worker['stdout'] === '/dev/null');
  list ($started, $notice) = startApplicationWorker('--APP-Test-Worker', 'Job-2');
  check('the argument is matched without regard to case, & the value is handed over as it is', $started === TRUE && array_slice((array)(startedAs('Job-2')['argv'] ?? array()), 1) === array('--app-test-worker', 'Job-2'));
  foreach (array('-fp', '--setup', '--run-workflow', 'app-scan', '--app scan', '--app-scan;id', '--app-scan --config', '') as $notOwn) { list ($started, $notice) = startApplicationWorker($notOwn, 'job-3'); check('nothing but an argument beginning --app is started: '.json_encode($notOwn), $started === FALSE && strpos($notice, 'Only an argument beginning --app') !== FALSE); }
  foreach (array('', ' ', 'a b', '../x', 'a/b', 'a;b', '$(id)', "a\nb", '-y', '--help', 'a.b', str_repeat('a', 65), "caf\xc3\xa9", "job-4\n", ' job-4', 'job-4 ', "job-4\0") as $notAValue) { list ($started, $notice) = startApplicationWorker('--app-test-worker', $notAValue); check('a value is letters, digits & dashes, & begins with neither a dash nor nothing: '.json_encode($notAValue), $started === FALSE && strpos($notice, 'letters, digits & dashes') !== FALSE); }
  list ($started, $notice) = startApplicationWorker('--app-test-worker', str_repeat('a', 64));
  check('a value of 64 characters is handed over', $started === TRUE && is_array(startedAs(str_repeat('a', 64))));
  usleep(600000);
  check('nothing that was refused was started', count(glob($InstLoc.'/started-*.json')) === 3, implode(' ', array_map('basename', glob($InstLoc.'/started-*.json'))));
  // From HRProtect's v0.0.6. The same function a second time, in a namespace, where a check stands in for what it calls.
  // proc_open() is handed its third argument by reference, so its stand-in is written out here. It keeps what it was handed & starts the process.
  $HandedToProcOpen = array();
  eval('namespace hooked; function proc_open($command, $descriptors, &$pipes, $folder) { global $HandedToProcOpen; $HandedToProcOpen = array($command, $descriptors, $folder); return \\proc_open($command, $descriptors, $pipes, $folder); }');
  standInFor('hooked', array('locateDependency', 'function_exists'));
  liftFunctionsInto('hooked', $tree.'/Resources/Engine/engine.php', array('startApplicationWorker'), array('proc_open'));
  list ($started, $notice) = \hooked\startApplicationWorker('--app-test-worker', 'job-5');
  check('a worker is handed nothing to read & nothing to write to, by number', $started === TRUE && is_array(startedAs('job-5')) && field($HandedToProcOpen, 1) === array(0 => array('file', '/dev/null', 'r'), 1 => array('file', '/dev/null', 'w'), 2 => array('file', '/dev/null', 'w')), json_encode(field($HandedToProcOpen, 1)));
  check('it is started through setsid -f, through no shell, in the folder of the installation', is_array(field($HandedToProcOpen, 0)) && basename((string)$HandedToProcOpen[0][0]) === 'setsid' && array_slice($HandedToProcOpen[0], 1) === array('-f', locateDependency('php'), $InstLoc.'/entry.php', '--app-test-worker', 'job-5') && field($HandedToProcOpen, 2) === $InstLoc, json_encode(field($HandedToProcOpen, 0)));
  foreach (array('setsid', 'php') as $missingProgram) {
    setHooks(array('locateDependency' => function ($arguments, $count) use ($missingProgram) { return ($arguments[0] === $missingProgram) ? array('answer', '') : NULL; }));
    $HandedToProcOpen = array();
    list ($started, $notice) = \hooked\startApplicationWorker('--app-test-worker', 'job-6');
    check('without '.$missingProgram.' no worker is started, & it says so', $started === FALSE && $notice === 'setsid or php could not be found, so the worker could not be started.' && $HandedToProcOpen === array(), $notice); }
  setHooks(array('function_exists' => function ($arguments, $count) { return ($arguments[0] === 'proc_open') ? array('answer', FALSE) : NULL; }));
  list ($started, $notice) = \hooked\startApplicationWorker('--app-test-worker', 'job-7');
  check('where PHP may not start a process no worker is started, & it says so', $started === FALSE && $notice === 'PHP has proc_open disabled, so the worker could not be started.' && $HandedToProcOpen === array(), $notice);
  setHooks(array());
  usleep(600000);
  check('& of those only the one that was started wrote that it was', count(glob($InstLoc.'/started-*.json')) === 4, implode(' ', array_map('basename', glob($InstLoc.'/started-*.json')))); }

// A file the kernel opens is not handed to a command this process runs, from v4.2.5. Root holds its logfile & a lock this way.
// The folder is one only root can change, which the kernel asks of a folder root opens a file in.
liftFunctions($tree.'/Resources/Kernel/kernel.php', array_values(array_filter(array('pathIsTrustedByRoot', 'findAccountDatabaseEntry', 'identifyRunningAccount', 'folderIsHeldForRoot', 'openFileWithoutFollowing'), function ($name) { return !function_exists($name); })), array());
$heldFolder = makeFolder(scratchPath('held'), 0700);
// ls is started without a shell, & lists what it was itself handed, which is what any command this process runs is handed.
function handedToACommand($path) { list ($status, $said) = runCommand(array('ls', '-l', '/proc/self/fd')); return strpos($said, $path) !== FALSE; }
list ($newHandle, $wasCreated, $openNotice) = openFileWithoutFollowing($heldFolder.'/created.txt', 0600);
check('a file the kernel creates is open here, & is not handed to a command', is_resource($newHandle) && $wasCreated === TRUE && fwrite($newHandle, 'written') === 7 && !handedToACommand($heldFolder.'/created.txt'), $openNotice);
if (is_resource($newHandle)) fclose($newHandle);
list ($oldHandle, $wasCreated, $openNotice) = openFileWithoutFollowing($heldFolder.'/created.txt', 0600);
check('a file the kernel opens is open here, & is not handed to a command either', is_resource($oldHandle) && $wasCreated === FALSE && fwrite($oldHandle, ' again') === 6 && !handedToACommand($heldFolder.'/created.txt') && file_get_contents($heldFolder.'/created.txt') === 'written again', $openNotice);
if (is_resource($oldHandle)) flock($oldHandle, LOCK_EX);
check('a lock taken through such a handle is still held here', is_resource($oldHandle) && !flock($probeHandle = fopen($heldFolder.'/created.txt', 'r'), LOCK_EX | LOCK_NB) && fclose($probeHandle));
if (is_resource($oldHandle)) fclose($oldHandle);
$plainHandle = fopen($heldFolder.'/plain.txt', 'x');
check('a handle opened any other way is handed over, which is what these checks look for', handedToACommand($heldFolder.'/plain.txt'));
fclose($plainHandle);

// From HRProtect's v0.0.6. What this PHP opens a file by is measured here as well, because reasons HRProtect's mutation runs gave rest on it, & one of them
// was wrong for as long as it was assumed. PHP keeps the path it last resolved a name to, for realpath_cache_ttl seconds. fopen(), file_get_contents()
// & hash_file() go where the name last led, & a file that fopen() or file_put_contents() makes by its whole path is made there. lstat(), stat(),
// scandir(), chdir(), link(), mkdir(), touch(), chmod(), lchown() & rename() go where the name leads now. So does unlink(), which was measured by hand,
// because a suite removes nothing. clearstatcache(TRUE) forgets every path. Forgetting one path forgets nothing above it or below it, &
// clearstatcache(FALSE) forgets none. The library's rememberElsewhere() makes a folder that was a link when PHP resolved it, & is another folder now.
// A PHP that is set to remember no path is held to that instead: there each of these goes where the name leads now.
$resolved = makeFolder(scratchPath('resolved'), 0700);
$firstFolder = makeFolder($resolved.'/first', 0700);
$nowFolder = makeFolder($resolved.'/name', 0700);
writeScratchFile($firstFolder.'/both', 'in the first', 0600);
writeScratchFile($nowFolder.'/both', 'in the second', 0600);
writeScratchFile($nowFolder.'/only-second', 'second', 0600);
// The library is asked first, because it sets a name aside the first time it is asked, & PHP forgets every path when it does.
$libraryRemembers = phpRemembersPaths();
$phpRemembers = rememberElsewhere($nowFolder, $firstFolder);
$went = $phpRemembers ? 'in the first' : 'in the second';
$landed = $phpRemembers ? $firstFolder : $nowFolder;
$openedHandle = fopen($nowFolder.'/both', 'rn');
$openedText = (string)fread($openedHandle, 32);
fclose($openedHandle);
$createdHandle = fopen(mustBeScratch($nowFolder.'/created'), 'x');
if (is_resource($createdHandle)) fclose($createdHandle);
file_put_contents(mustBeScratch($nowFolder.'/put'), 'put');
clearstatcache();
check('fopen(), file_get_contents() & hash_file() go where a name last led, for as long as this PHP remembers that', $libraryRemembers === $phpRemembers && $openedText === $went && file_get_contents($nowFolder.'/both') === $went && hash_file('sha256', $nowFolder.'/both') === hash('sha256', $went), json_encode(array($phpRemembers, $openedText)));
check('& a file fopen() or file_put_contents() makes by its whole path is made there', is_array(@lstat($landed.'/created')) && is_array(@lstat($landed.'/put')) && (!$phpRemembers or (!is_array(@lstat($nowFolder.'/created')) && !is_array(@lstat($nowFolder.'/put')))));
$workingFolder = (string)getcwd();
$enteredNow = (chdir($nowFolder) && field(stat('.'), 'ino') === field(lstat($nowFolder), 'ino'));
chdir($workingFolder !== '' ? $workingFolder : '/');
$goesNow = array(
  'lstat' => is_array(@lstat($nowFolder.'/only-second')),
  'stat' => is_array(@stat($nowFolder.'/only-second')),
  'scandir' => in_array('only-second', (array)scandir($nowFolder), TRUE),
  'chdir' => $enteredNow,
  'link' => (link($nowFolder.'/only-second', mustBeScratch($nowFolder.'/second-name')) && is_array(@lstat($nowFolder.'/second-name')) && !is_array(@lstat($firstFolder.'/second-name'))),
  'mkdir' => (mkdir(mustBeScratch($nowFolder.'/made'), 0700) && is_array(@lstat($nowFolder.'/made')) && !is_array(@lstat($firstFolder.'/made'))),
  'touch' => (touch(mustBeScratch($nowFolder.'/touched')) && is_array(@lstat($nowFolder.'/touched')) && !is_array(@lstat($firstFolder.'/touched'))),
  'chmod' => (@chmod($nowFolder.'/only-second', 0640) && (field(@lstat($nowFolder.'/only-second'), 'mode') & 07777) === 0640),
  'lchown' => (@lchown($nowFolder.'/only-second', 65534) && (clearstatcache() or TRUE) && field(@lstat($nowFolder.'/only-second'), 'uid') === 65534));
check('lstat(), stat(), scandir(), chdir(), link(), mkdir(), touch(), chmod() & lchown() go where a name leads now', !in_array(FALSE, $goesNow, TRUE), json_encode($goesNow));
clearstatcache(FALSE);
$afterFalse = file_get_contents($nowFolder.'/both');
clearstatcache(TRUE, $nowFolder.'/both');
$afterTheFile = file_get_contents($nowFolder.'/both');
clearstatcache(TRUE, $nowFolder);
$afterTheFolder = file_get_contents($nowFolder.'/both');
clearstatcache(TRUE);
$afterAll = file_get_contents($nowFolder.'/both');
check('clearstatcache(FALSE) forgets no path, forgetting the file alone or the folder above it alone still opens it where it last led, & clearstatcache(TRUE) forgets them all', $afterFalse === $went && $afterTheFile === $went && $afterTheFolder === $went && $afterAll === 'in the second', json_encode(array($afterFalse, $afterTheFile, $afterTheFolder, $afterAll)));
$againFolder = makeFolder($resolved.'/again', 0700);
writeScratchFile($againFolder.'/only-again', 'again', 0600);
$againRemembers = rememberElsewhere($againFolder, $firstFolder);
$heldBefore = isset(realpath_cache_get()[$againFolder]);
$wasMoved = @rename($againFolder.'/only-again', mustBeScratch($againFolder.'/moved'));
$heldAfter = isset(realpath_cache_get()[$againFolder]);
clearstatcache();
check('rename() goes where a name leads now as well, & PHP forgets every path when it renames anything, which is why another process changes the name for these checks', $wasMoved === TRUE && is_array(@lstat($againFolder.'/moved')) && !is_array(@lstat($firstFolder.'/moved')) && $againRemembers === $phpRemembers && $heldBefore === $phpRemembers && $heldAfter === FALSE, json_encode(array($wasMoved, $againRemembers, $heldBefore, $heldAfter)));
finish('test_kernel');
