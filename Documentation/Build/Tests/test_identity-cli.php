<?php
// HRProprietary Engine v4.3.3. The suite of how the kernel names the account running it, & of what decides about root by that.
//
//   sudo bash Documentation/Build/Tests/contained.sh php Documentation/Build/Tests/test_identity-cli.php
// Run it from the root of the application under test. A tree named after it is tested instead, & must be that root.
//
// identifyRunningAccount() & findAccountDatabaseEntry() are lifted out of the tree's own Resources/Kernel/kernel.php, exactly as the
// tree has it, & so is each function that decides something about root by them. Root is the account whose ID is 0, & an account
// whose ID cannot be read halts with 28010. Where a part needs another account, or one that cannot be identified, a stand-in for
// identifyRunningAccount() answers in a namespace. Where it needs PHP without its posix functions, or unable to read /proc, a second
// PHP is started without a shell, with those functions in disable_functions, & with /proc & /etc left out of open_basedir. The second
// PHP runs a script this suite writes beneath the scratch folder, which lifts what it needs from the tree & prints what happened.
// A whoami, an id & a getent that would leave a mark are first in PATH for every second PHP, & none may run.
// The tree's entry point & the loader -fp writes are run as well, as root, without posix & without /proc.
//
// It removes nothing of its own. The sweep it tests removes the expired session this suite made for it beneath the scratch folder.

if (PHP_SAPI !== 'cli') { if (!headers_sent()) http_response_code(404); die(); }

if (!is_file('Documentation/Build/Tests/testlib-cli.php')) { fwrite(STDERR, 'Run this from the root of the application it tests. See Documentation/Build/Tests/README.txt.'.PHP_EOL); exit(64); }
require 'Documentation/Build/Tests/testlib-cli.php';

$tree = testTree(isset($argv[1]) ? $argv[1] : (string)getcwd());
requireContainment();

// What the kernel would have set before any function here ran.
$EnableMemoryProtection = TRUE;
$Verbose = FALSE;
$DirSep = DIRECTORY_SEPARATOR;
$Lol = PHP_EOL;
$ApacheUser = 'www-data';
$kernelFile = $tree.'/Resources/Kernel/kernel.php';
$engineFile = $tree.'/Resources/Engine/engine.php';
$setupFile = $tree.'/Resources/Engine/Cores/setupCore.php';
$entryName = testEntryPoint($tree);
$homeBefore = (string)getenv('HOME');

// A halt ends the command it is raised in. Here it ends the function under test, & the check that called it catches it.
function quickDie($entry, $errorNumber) {
  global $TestLog;
  $TestLog[] = 'ERROR!!! '.$errorNumber.', '.$entry;
  throw new RuntimeException('HALT '.$errorNumber.' '.$entry); }

// What the Engine would answer for an application named Identity, whose entry point is app.php.
function engineApplicationName() { return 'Identity'; }
function engineApplicationEntryPoint() { return 'app.php'; }
function engineApplicationSlug() { return 'identity'; }

// The sweep hands each expired session to cleanFiles() before it removes the folder. Nothing in the sessions here needs it.
function cleanFiles($sessionPath, $authorizedPaths) { return TRUE; }

liftFunctions($kernelFile, array('getChildFunction', 'purgeSensitiveMemory', 'findAccountDatabaseEntry', 'identifyRunningAccount', 'webServerAccountNumbers', 'pathIsTrustedByRoot', 'treeIsTrustedByRoot', 'parseArgumentVector', 'verifyContainerEnvironment'), array());
liftFunctions($engineFile, array('fileTime', 'removeEmptiedSessionDir', 'isDirEmptyOfUserFiles'), array('unlink', 'rmdir'));
liftFunctions($setupFile, array('applicationLoaderContents'), array());
// The functions that decide about root, a second time, in a namespace, where a stand-in says who is running.
standInFor('who', array('identifyRunningAccount', 'treeIsTrustedByRoot', 'parseArgumentVector'));
liftFunctionsInto('who', $kernelFile, array('verifyCodeIsTrustedByRoot', 'verifyRunningContext', 'establishDataLocations', 'folderIsHeldForRoot'), array());
liftFunctionsInto('who', $engineFile, array('cleanDataLocation'), array('shell_exec', 'unlink', 'rmdir'));
// PHP looks for a class only in the namespace it is named in, so the sweep's two are named there as well.
class_alias('RecursiveDirectoryIterator', 'who\\RecursiveDirectoryIterator');
class_alias('RecursiveIteratorIterator', 'who\\RecursiveIteratorIterator');

// Hooks that make the stand-in answer for the account given.
function asAccount($accountId, $accountName, $accountHome) {
  return array('identifyRunningAccount' => function ($arguments, $count) use ($accountId, $accountName, $accountHome) { return array('answer', array($accountId, $accountName, $accountHome)); }); }

// Runs a function. Gives back how it halted, or an empty string where it did not.
function halted($functionName, $arguments) {
  try {
    call_user_func_array($functionName, $arguments);
    return ''; }
  catch (RuntimeException $halt) { return $halt->getMessage(); } }

// How many times the tree was read since the hooks were last set.
function treeReads() {
  global $TestHookCalls;
  return isset($TestHookCalls['treeIsTrustedByRoot']) ? $TestHookCalls['treeIsTrustedByRoot'] : 0; }

// A whoami, an id & a getent that write a mark where they run, first in PATH for every second PHP.
$fakeBin = makeFolder(scratchPath('bin'), 0755);
$programMark = scratchPath('a-program-ran');
foreach (array('whoami', 'id', 'getent') as $programName) writeScratchFile($fakeBin.'/'.$programName, "#!/bin/sh\necho ".$programName." >> ".$programMark."\necho 0\n", 0755);
$withoutPosix = 'posix_geteuid,posix_getpwuid,posix_getpwnam,posix_getgrnam,posix_getgrgid';
$withoutPrograms = 'exec,shell_exec,system,passthru,popen,proc_open,pcntl_exec';

// Starts a second PHP without a shell. Gives back its exit status, what it said & the JSON on its last line, in that order.
function secondPhp($disabled, $basedir, $settings, $arguments) {
  global $fakeBin;
  $parts = array('env', 'PATH='.$fakeBin.':/usr/bin:/bin', PHP_BINARY, '-d', 'disable_functions='.$disabled);
  if ($basedir !== '') array_push($parts, '-d', 'open_basedir='.$basedir);
  foreach ($settings as $setting) array_push($parts, '-d', $setting);
  list ($status, $said) = runCommand(array_merge($parts, $arguments));
  $lines = explode("\n", trim($said));
  $decoded = json_decode((string)end($lines), TRUE);
  return array($status, $said, is_array($decoded) ? $decoded : array()); }

// The script a second PHP runs. It lifts from the tree it is handed, & prints what the part it is asked for did.
$childSource = <<<'CHILD'
<?php
// Written by test_identity-cli.php beneath its scratch folder, & run by a second PHP it starts.
if (PHP_SAPI !== 'cli') { if (!headers_sent()) http_response_code(404); die(); }
$tree = (string)$argv[1];
$part = (string)$argv[2];
$argument = isset($argv[3]) ? (string)$argv[3] : '';
require $tree.'/Documentation/Build/Tests/testlib-cli.php';
$EnableMemoryProtection = TRUE;
$Verbose = FALSE;
$DirSep = DIRECTORY_SEPARATOR;
$ApacheUser = 'www-data';
function quickDie($entry, $errorNumber) { print(json_encode(array('Halt' => $errorNumber, 'Said' => $entry)).PHP_EOL); exit(3); }
function engineApplicationName() { return 'Identity'; }
liftFunctions($tree.'/Resources/Kernel/kernel.php', array('getChildFunction', 'purgeSensitiveMemory', 'findAccountDatabaseEntry', 'identifyRunningAccount', 'verifyContainerEnvironment', 'verifyRunningContext', 'establishDataLocations', 'webServerAccountNumbers'), array());
liftFunctions($tree.'/Resources/Engine/Cores/dependencyCore.php', array('folderIsPrivateToAccount', 'resolveProbeScratchFolder'), array());
$said = array('Posix' => function_exists('posix_geteuid'));
if ($part === 'identify') $said['Account'] = identifyRunningAccount();
else if ($part === 'context') {
  $said['Named'] = verifyRunningContext();
  $said['Root'] = $RunningAsRoot;
  $said['User'] = $CurrentUser;
  $said['Home'] = getenv('HOME'); }
else if ($part === 'accounts') $said['Numbers'] = webServerAccountNumbers();
else if ($part === 'locations' or $part === 'locations-dropped') {
  if ($part === 'locations-dropped' && (!posix_setgid(33) or !posix_setuid(33))) testStop('The second PHP could not become the account numbered 33.');
  $ConvertLoc = $argument;
  $HomeLoc = '';
  putenv('HOME=/identity-sentinel');
  $said['Derived'] = establishDataLocations();
  $said['Home'] = getenv('HOME'); }
else if ($part === 'probe') {
  $CurrentUser = 'root';
  $said['Probe'] = resolveProbeScratchFolder();
  $said['Log'] = $TestLog; }
else testStop('The second PHP was asked for a part it does not have.');
print(json_encode($said).PHP_EOL);
CHILD;
$childScript = writeScratchFile(scratchPath('identity-child.php'), $childSource, 0644);


// Part 1. findAccountDatabaseEntry() reads a database as the C library does, & finds the first entry that matches.
$database = writeScratchFile(scratchPath('accounts.txt'), "first:x:10:10::/home/first:/bin/sh\nsecond:x:20:20::/home/second:/bin/sh\r\nfirst:x:30:30::/home/again:/bin/sh\nshort:x\n:x:40:40::/home/blank:/bin/sh\n", 0644);
check('the first entry that matches is the one found', field(findAccountDatabaseEntry($database, 0, 'first'), 2) === '10');
check('an entry is found by any field, & a carriage return is not part of its last', field(findAccountDatabaseEntry($database, 2, '20'), 0) === 'second' && field(findAccountDatabaseEntry($database, 2, '20'), 6) === '/bin/sh');
check('an entry too short to hold the field asked for is passed over', findAccountDatabaseEntry($database, 2, 'x') === array());
check('a value no entry holds finds nothing', findAccountDatabaseEntry($database, 0, 'missing') === array());
check('an empty value finds nothing, & not the entry whose name is empty', findAccountDatabaseEntry($database, 0, '') === array());
check('a file that cannot be read finds nothing', findAccountDatabaseEntry(scratchPath('no-such-file'), 0, 'first') === array());
check('root is in /etc/passwd with the ID 0', field(findAccountDatabaseEntry('/etc/passwd', 0, 'root'), 2) === '0');

// Part 2. With posix, identifyRunningAccount() gives what posix gives.
$posixRecord = posix_getpwuid(posix_geteuid());
check('identifyRunningAccount gives the ID, the name & the home posix gives', identifyRunningAccount() === array(posix_geteuid(), (string)field($posixRecord, 'name'), (string)field($posixRecord, 'dir')));
check('this suite runs as root', field(identifyRunningAccount(), 0) === 0);

// Part 3. verifyCodeIsTrustedByRoot() reads the tree for root, by ID, & refuses an account it cannot identify before it reads anything.
setHooks(asAccount(-1, '', ''));
$halt = halted('who\verifyCodeIsTrustedByRoot', array($tree, $entryName));
check('an account that cannot be identified halts with 28010, & the tree is never read', strpos($halt, 'HALT 28010 The account running this process could not be identified, because posix_geteuid is unavailable & /proc/self/status could not be read.') === 0 && treeReads() === 0, $halt);
setHooks(asAccount(33, 'www-data', '/var/www') + array('treeIsTrustedByRoot' => function ($arguments, $count) { return array('answer', array(FALSE, 'A stand-in.')); }));
check('an account that is not root is not held to the tree', who\verifyCodeIsTrustedByRoot($tree, $entryName) === TRUE && treeReads() === 0);
setHooks(asAccount(0, 'toor', '/root') + array('treeIsTrustedByRoot' => function ($arguments, $count) { return array('answer', array(FALSE, 'A planted file does not belong to root.')); }, 'parseArgumentVector' => function ($arguments, $count) { return array('answer', array(array(), 1, '-v', '', '', FALSE, TRUE)); }));
$halt = halted('who\verifyCodeIsTrustedByRoot', array($tree, $entryName));
check('the account whose ID is 0 is held to the tree, whatever it is called', strpos($halt, 'HALT 28008 Root refused to run the installation at '.$tree) === 0 && treeReads() === 1, $halt);
setHooks(asAccount(0, 'root', '/root') + array('treeIsTrustedByRoot' => function ($arguments, $count) { return array('answer', array(FALSE, 'A planted file does not belong to root.')); }, 'parseArgumentVector' => function ($arguments, $count) { return array('answer', array(array(), 1, '-fp', '', '', FALSE, TRUE)); }));
ob_start();
$repairHalt = halted('who\verifyCodeIsTrustedByRoot', array($tree, $entryName));
$repairSaid = (string)ob_get_clean();
check('a repair still runs as root on a tree another account could change, & says so', $repairHalt === '' && strpos($repairSaid, 'WARNING. Root is running code another account could have changed.') !== FALSE);

// Part 4. verifyRunningContext() makes root the account whose ID is 0, & gives it its own home.
setHooks(asAccount(-1, '', ''));
$halt = halted('who\verifyRunningContext', array());
check('the context halts with 28010 for an account that cannot be identified', strpos($halt, 'HALT 28010 ') === 0, $halt);
setHooks(asAccount(0, 'toor', '/home/toor'));
$named = who\verifyRunningContext();
check('the account whose ID is 0 is root, whatever it is called, & keeps its own home', $named === TRUE && $RunningAsRoot === TRUE && $CurrentUser === 'toor' && getenv('HOME') === '/home/toor');
setHooks(asAccount(0, 'root', ''));
who\verifyRunningContext();
check('root with no home in the account database is given /root', $RunningAsRoot === TRUE && getenv('HOME') === '/root');
putenv('HOME=/identity-sentinel');
setHooks(asAccount(33, 'root', '/var/www'));
who\verifyRunningContext();
check('an account called root whose ID is not 0 is not root, & its home is left alone', $RunningAsRoot === FALSE && $CurrentUser === 'root' && getenv('HOME') === '/identity-sentinel');
setHooks(asAccount(1000, '', ''));
check('an account with an ID & no name runs, & says it has no name', who\verifyRunningContext() === FALSE && $RunningAsRoot === FALSE && $CurrentUser === '');
putenv('HOME='.$homeBefore);

// Part 5. establishDataLocations() gives the cache folder as home to every account but root, & not to one it cannot identify.
$dataFolder = makeFolder(scratchPath('data'), 0755);
$ConvertLoc = $dataFolder;
$HomeLoc = '';
foreach (array(array(0, 'root', 'root keeps the home it has'), array(-1, '', 'an account that cannot be identified keeps the home it started with')) as $case) {
  putenv('HOME=/identity-sentinel');
  setHooks(asAccount($case[0], $case[1], ''));
  check($case[2], who\establishDataLocations() === TRUE && getenv('HOME') === '/identity-sentinel'); }
putenv('HOME=/identity-sentinel');
setHooks(asAccount(33, 'www-data', '/var/www'));
check('any other account takes the cache folder as its home', who\establishDataLocations() === TRUE && getenv('HOME') === $dataFolder.'/Cache');
putenv('HOME='.$homeBefore);

// Part 6. folderIsHeldForRoot() judges an account it cannot identify as root.
$openFolder = makeFolder(scratchPath('open'), 0777);
$heldTarget = makeFolder($openFolder.'/target', 0755);
setHooks(asAccount(-1, '', ''));
list ($unknownHeld, $unknownNotice) = who\folderIsHeldForRoot($heldTarget);
check('a folder another account could replace is not held for an account that cannot be identified', $unknownHeld === FALSE && strpos($unknownNotice, 'Another account could replace '.$heldTarget) === 0, $unknownNotice);
setHooks(asAccount(0, 'root', '/root'));
list ($rootHeld) = who\folderIsHeldForRoot($heldTarget);
check('nor for root', $rootHeld === FALSE);
setHooks(asAccount(33, 'www-data', '/var/www'));
list ($otherHeld) = who\folderIsHeldForRoot($heldTarget);
check('& another account is not judged, because it reaches nothing through a link it could not reach without one', $otherHeld === TRUE);

// Part 7. The sweep removes only what its account owns, so an account that cannot be identified sweeps nothing.
$sweepFolder = makeFolder(scratchPath('sweep'), 0755);
$rootSession = makeFolder($sweepFolder.'/day/root-session', 0755);
writeScratchFile($rootSession.'/file.txt', 'expired', 0644);
$otherSession = makeFolder($sweepFolder.'/day/other-session', 0755);
chown($otherSession, 33);
touch($rootSession, time() - 3600);
touch($otherSession, time() - 3600);
clearstatcache();
$TestLog = array();
setHooks(asAccount(-1, '', ''));
list ($unknownCleaned, $unknownDeep) = who\cleanDataLocation($sweepFolder, 'Sweep test', 0, array(), array($sweepFolder));
clearstatcache();
check('a sweep by an account that cannot be identified sweeps nothing, & fails', $unknownCleaned === FALSE && $unknownDeep === FALSE && is_dir($rootSession) && is_dir($otherSession));
check('& says why', in_array('WARNING!!! The Sweep test location at '.$sweepFolder.' was not swept, because the account sweeping could not be identified.', $TestLog, TRUE), implode(' | ', $TestLog));
setHooks(asAccount(0, 'root', '/root'));
list ($rootCleaned, $rootDeep) = who\cleanDataLocation($sweepFolder, 'Sweep test', 0, array(), array($sweepFolder));
clearstatcache();
check('root sweeps the expired session it owns, & leaves the one another account made', $rootCleaned === TRUE && $rootDeep === TRUE && !file_exists($rootSession) && is_dir($otherSession));
setHooks(array());

// Part 8. Without posix, a second PHP reads the account from /proc/self/status & /etc/passwd, & halts where it can read neither.
$withoutProc = $tree.':'.$TestScratch;
list ($status, $said, $answer) = secondPhp($withoutPosix, '', array(), array($childScript, $tree, 'identify'));
check('the second PHP has no posix_geteuid', $status === 0 && field($answer, 'Posix') === FALSE, $said);
check('without posix the ID is read from /proc/self/status, & the name & the home from /etc/passwd', field($answer, 'Account') === array(0, 'root', '/root'), $said);
list ($status, $said, $answer) = secondPhp($withoutPosix, $withoutProc, array(), array($childScript, $tree, 'identify'));
check('without posix or /proc the ID cannot be read, & is -1', $status === 0 && field($answer, 'Account') === array(-1, '', ''), $said);
list ($status, $said, $answer) = secondPhp($withoutPosix.','.$withoutPrograms, '', array(), array($childScript, $tree, 'context'));
check('without posix, & with no program it could run, root is root & keeps its own home', $status === 0 && field($answer, 'Root') === TRUE && field($answer, 'User') === 'root' && field($answer, 'Home') === '/root', $said);
list ($status, $said, $answer) = secondPhp($withoutPosix, $withoutProc, array(), array($childScript, $tree, 'context'));
check('without posix or /proc the context halts with 28010', $status === 3 && field($answer, 'Halt') === 28010, $said);
list ($status, $said, $answer) = secondPhp($withoutPosix, '', array(), array($childScript, $tree, 'accounts'));
check('without posix the web server account & its group are read from /etc/passwd & /etc/group, as posix reads them', $status === 0 && field($answer, 'Numbers') === webServerAccountNumbers() && field(webServerAccountNumbers(), 0) > 0 && field(webServerAccountNumbers(), 1) > 0, $said);
$childData = makeFolder(scratchPath('child-data'), 0755);
list ($status, $said, $answer) = secondPhp($withoutPosix, '', array(), array($childScript, $tree, 'locations', $childData));
check('without posix root keeps the home it has', $status === 0 && field($answer, 'Derived') === TRUE && field($answer, 'Home') === '/identity-sentinel', $said);
list ($status, $said, $answer) = secondPhp($withoutPosix, '', array(), array($childScript, $tree, 'locations-dropped', $childData));
check('without posix another account takes the cache folder as its home', $status === 0 && field($answer, 'Home') === $childData.'/Cache', $said);
$probeTemp = makeFolder(scratchPath('probe-temp'), 0755);
list ($status, $said, $answer) = secondPhp($withoutPosix.','.$withoutPrograms, '', array('sys_temp_dir='.$probeTemp), array($childScript, $tree, 'probe'));
check('without posix the Dependency Core makes root a scratch folder of its own', $status === 0 && field($answer, 'Probe') === array(TRUE, $probeTemp.'/Identity-probe-root') && fileowner($probeTemp.'/Identity-probe-root') === 0, $said);
list ($status, $said, $answer) = secondPhp($withoutPosix, $withoutProc, array('sys_temp_dir='.$probeTemp), array($childScript, $tree, 'probe'));
check('& without /proc it makes none, & runs nothing', $status === 0 && field(field($answer, 'Probe'), 0) === FALSE && in_array('WARNING!!! No private scratch folder could be made for a dependency command, so it was not run.', (array)field($answer, 'Log'), TRUE), $said);

// Part 9. The tree's own entry point, run as root without posix, holds root to the tree, & halts where it cannot read the ID.
// Each copy holds the entry point & the kernel, & nothing else, so nothing past the kernel's first checks can run.
function entryCopy($folderName) {
  global $tree, $entryName;
  $folder = makeFolder(scratchPath($folderName.'/Resources/Kernel'), 0755);
  $copy = dirname(dirname($folder));
  chmod($copy, 0755);
  chmod($copy.'/Resources', 0755);
  writeScratchFile($copy.'/'.$entryName, (string)file_get_contents($tree.'/'.$entryName), 0644);
  writeScratchFile($folder.'/kernel.php', (string)file_get_contents($tree.'/Resources/Kernel/kernel.php'), 0644);
  return $copy; }
$plantedCopy = entryCopy('entry-planted');
chown(writeScratchFile($plantedCopy.'/planted.txt', 'planted', 0644), 33);
$cleanCopy = entryCopy('entry-clean');
list ($status, $said) = secondPhp($withoutPosix, '', array(), array($plantedCopy.'/'.$entryName, '-v'));
check('without posix root is refused a tree with a file another account owns', $status !== 0 && strpos($said, '-28008, ') !== FALSE && strpos($said, $plantedCopy.'/planted.txt') !== FALSE, $said);
list ($status, $said) = secondPhp('', '', array(), array($plantedCopy.'/'.$entryName, '-v'));
check('as it is with posix', $status !== 0 && strpos($said, '-28008, ') !== FALSE, $said);
list ($status, $said) = secondPhp($withoutPosix.','.$withoutPrograms, '', array(), array($cleanCopy.'/'.$entryName, '-v'));
check('without posix & with no program it could run, root passes both checks & reaches the version file', $status !== 0 && strpos($said, '-24000, ') !== FALSE && strpos($said, '-28008') === FALSE && strpos($said, '-28010') === FALSE && strpos($said, 'Fatal') === FALSE, $said);
list ($status, $said) = secondPhp($withoutPosix, $cleanCopy, array(), array($cleanCopy.'/'.$entryName, '-v'));
check('without posix or /proc the entry point halts with 28010 before anything else runs', $status !== 0 && strpos($said, '-28010, ') !== FALSE && strpos($said, 'The account running this process could not be identified') !== FALSE && strpos($said, 'Warning') === FALSE, $said);

// Part 10. The loader -fp writes holds root to the installation by ID, refuses an account it cannot identify, & leaves the application nothing.
$loaderFolder = makeFolder(scratchPath('loader'), 0755);
$installedFolder = makeFolder($loaderFolder.'/Application', 0755);
$installedEntry = writeScratchFile($installedFolder.'/app.php', "<?php\nprint('ENTRY RAN '.((isset(\$LoaderAccountId) or isset(\$LoaderStatusMatch)) ? 'LEAKED' : 'CLEAN').PHP_EOL);\n", 0644);
$loader = writeScratchFile($loaderFolder.'/app.php', applicationLoaderContents($installedEntry), 0644);
list ($status, $said) = runCommand(array(PHP_BINARY, '-l', $loader));
check('the loader is PHP', $status === 0 && strpos($said, 'No syntax errors') !== FALSE, $said);
list ($status, $said) = secondPhp($withoutPosix, '', array(), array($loader));
check('without posix root runs an installation only root can change, & the application inherits nothing of the loader', $status === 0 && trim($said) === 'ENTRY RAN CLEAN', $said);
chown($installedEntry, 33);
list ($status, $said) = secondPhp($withoutPosix, '', array(), array($loader));
check('without posix root refuses an entry point another account owns', $status === 1 && strpos($said, 'Identity-28008, Root refused to run '.$installedEntry) !== FALSE && strpos($said, 'ENTRY RAN') === FALSE, $said);
list ($status, $said) = secondPhp('', '', array(), array($loader));
check('as it does with posix', $status === 1 && strpos($said, 'Identity-28008, ') !== FALSE && strpos($said, 'ENTRY RAN') === FALSE, $said);
chown($installedEntry, 0);
list ($status, $said) = secondPhp($withoutPosix, $loaderFolder, array(), array($loader));
check('without posix or /proc the loader refuses with 28010 & runs nothing', $status === 1 && strpos($said, 'Identity-28010, The account running this loader could not be identified, so '.$installedEntry.' was not run.') !== FALSE && strpos($said, 'ENTRY RAN') === FALSE, $said);

// Part 11. Nothing in the kernel or the Engine asks a program who is running, asks posix alone whether it is root, or lets a .curlrc change a request.
$engineSources = array_merge(array($kernelFile, $engineFile), glob($tree.'/Resources/Engine/Cores/*.php'), glob($tree.'/Resources/Engine/Managers/*.php'));
$programsAsked = $rootTests = $curlsWithoutQ = array();
$curlsFound = 0;
foreach ($engineSources as $sourceFile) {
  $source = (string)file_get_contents($sourceFile);
  $tokens = token_get_all($source);
  $tokenCount = count($tokens);
  $code = '';
  for ($index = 0; $index < $tokenCount; $index++) {
    $token = $tokens[$index];
    if (is_array($token) && in_array($token[0], array(T_COMMENT, T_DOC_COMMENT), TRUE)) continue;
    $code .= (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING) ? "''" : (is_array($token) ? $token[1] : $token);
    if (!is_array($token) or $token[0] !== T_STRING or !in_array(strtolower($token[1]), array('exec', 'shell_exec', 'system', 'passthru', 'popen', 'proc_open'), TRUE)) continue;
    $next = $index + 1;
    while ($next < $tokenCount && is_array($tokens[$next]) && $tokens[$next][0] === T_WHITESPACE) $next++;
    if ($next >= $tokenCount or $tokens[$next] !== '(') continue;
    $next++;
    while ($next < $tokenCount && is_array($tokens[$next]) && $tokens[$next][0] === T_WHITESPACE) $next++;
    if ($next < $tokenCount && is_array($tokens[$next]) && $tokens[$next][0] === T_CONSTANT_ENCAPSED_STRING && preg_match('/^[\'"](whoami|id|getent)[\s\'"]/', $tokens[$next][1]) === 1) $programsAsked[] = basename($sourceFile).':'.$tokens[$next][2]; }
  if (preg_match('/posix_geteuid\s*\(\s*\)\s*[!=]==?\s*0\b/', $code) === 1) $rootTests[] = basename($sourceFile);
  $curlsFound += preg_match_all('/\$curlCommand\s*=\s*(\'curl\b|escapeshellarg\(\$curlBinary\)\.\')/', $source);
  if (preg_match_all('/\$curlCommand\s*=\s*(\'curl\b|escapeshellarg\(\$curlBinary\)\.\')(?! -q\b)/', $source, $unguarded) > 0) $curlsWithoutQ[] = basename($sourceFile).' '.count($unguarded[0]); }
check('no whoami, id or getent is asked by the kernel or the Engine', $programsAsked === array(), implode(' ', $programsAsked));
check('nothing in the kernel or the Engine asks posix alone whether it is root', $rootTests === array(), implode(' ', $rootTests));
check('every curl command the kernel or the Engine builds begins with -q', $curlsFound >= 3 && $curlsWithoutQ === array(), $curlsFound.' found. '.implode(' ', $curlsWithoutQ));
check('& no whoami, id or getent ran in any second PHP', !file_exists($programMark), (string)@file_get_contents($programMark));

finish('test_identity');
