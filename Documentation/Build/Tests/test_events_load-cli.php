<?php
// HRProprietary Engine v4.3.1. The unit suite of what engine.php does where the kernel will not load the Event Core, & of where it stands.
// HRProtect's v0.0.3 suite of the same name, for the four functions engine.php took from protectCore.php when the Event Core became
// the Engine's. What only HRProtect does, its two commands & the event of a scan that could not start, stays in HRProtect's suite.
// Parts 4 through 9 are new. They hold what the Engine's copy does that HRProtect's did not, & what HRProtect's suite did not ask.
//
//   sudo bash Documentation/Build/Tests/contained.sh php Documentation/Build/Tests/test_events_load-cli.php
// Run it from the root of the application under test. A tree named after it is tested instead, & must be that root.
//
// engine.php reads the version of the Event Core from its file, & the kernel reads the file again before it loads it.
// A file that changed between the two, as one does while an upgrade is being copied in, is ready the first time & refused the second.
// No suite can change a file at that one moment. So here the kernel's verifyCoreComponent() is a stand-in that answers what a
// part tells it to, & that is why this is a suite of its own: a suite never puts a function in the place of one it lifted.
// Everything else is lifted out of the tree exactly as the tree has it. The Event Core itself is never loaded here.
//
// It removes nothing. Each part makes a folder of its own beneath the scratch folder & leaves it there.

if (PHP_SAPI !== 'cli') { if (!headers_sent()) http_response_code(404); die(); }

if (!is_file('Documentation/Build/Tests/testlib-cli.php')) { fwrite(STDERR, 'Run this from the root of the application it tests. See Documentation/Build/Tests/README.txt.'.PHP_EOL); exit(64); }
require 'Documentation/Build/Tests/testlib-cli.php';

$tree = testTree(isset($argv[1]) ? $argv[1] : (string)getcwd());
requireContainment();

// What the kernel & the application would have set before any of these functions ran.
$EnableMemoryProtection = TRUE;
$Verbose = FALSE;
$LogFile = '';
$RunningAsRoot = TRUE;
$CoreLoaded = TRUE;
$Lol = PHP_EOL;
$InstLoc = $tree;
$EngineApplicationVersion = '4.3.1';
$RecordEvents = TRUE;
$RequiredEventCoreVersion = '';
$EventLoc = '';
$EventCoreState = $EventCoreNotice = '';
$EventRecordFailed = FALSE;

$kernelFile = $tree.'/Resources/Kernel/kernel.php';
$engineFile = $tree.'/Resources/Engine/engine.php';
$EntryPointName = testEntryPoint($tree);
$entryFile = $tree.'/'.$EntryPointName;
$coreFile = $tree.'/Resources/Engine/Cores/eventCore.php';
if (!is_file($engineFile)) testStop($engineFile.' does not exist, so there is nothing to test.');
if (!is_file($entryFile)) testStop($entryFile.' does not exist, so there is no pin to read.');
if (!is_file($coreFile)) testStop($coreFile.' does not exist, so there is no Event Core whose version could be read.');
liftFunctions($kernelFile, array('getChildFunction', 'purgeSensitiveMemory', 'pathIsTrustedByRoot', 'printableText', 'sanitizeString', 'sanitize', 'readComponentVersion'), array());
liftFunctions($engineFile, array('claimPrivateFolder', 'eventCoreStanding', 'loadEventCore', 'noteEvent'), array());


// The stand-in for the kernel's verifyCoreComponent(). It loads nothing, answers what a part told it to, & notes what it was asked.
$StubLoadAnswer = array(FALSE, '');
$StubLoadCalls = array();
$PurgeFailures = 0;

function verifyCoreComponent($componentName, $componentRelativePath, $versionVariableName, $requiredComponentVersion) {
  global $StubLoadAnswer, $StubLoadCalls;
  $StubLoadCalls[] = array($componentName, $componentRelativePath, $versionVariableName, $requiredComponentVersion);
  return $StubLoadAnswer; }

function engineApplicationEntryPoint() {
  global $EntryPointName;
  return $EntryPointName; }


// Begins what the application would call a new command: the Event Core is decided again, & nothing is in the log.
function newCommand() {
  global $EventCoreState, $EventCoreNotice, $EventRecordFailed, $TestLog, $StubLoadCalls, $PurgeFailures;
  $PurgeFailures = $PurgeFailures + count(preg_grep('/^ERROR!!! 40000/', $TestLog));
  $EventCoreState = $EventCoreNotice = '';
  $EventRecordFailed = FALSE;
  $TestLog = array();
  $StubLoadCalls = array(); }

function plainFacts() {
  return array('Source' => 'scan', 'Kind' => 'test.made', 'Category' => 1, 'Severity' => 'info', 'Summary' => 'A test made this event.'); }

function folderNames($folderPath) {
  $names = is_dir($folderPath) ? scandir($folderPath) : FALSE;
  return is_array($names) ? array_values(array_diff($names, array('.', '..'))) : array('<not a folder>'); }

function coreFunctionsDefined() {
  $defined = array();
  foreach (array('recordEvent', 'readEvents', 'readEventDay', 'findEvent', 'eventSeverityRank', 'eventIdentifierIsValid', 'eventLineOf') as $coreFunction) if (function_exists($coreFunction)) $defined[] = $coreFunction;
  return $defined; }

$corePath = 'Engine'.DIRECTORY_SEPARATOR.'Cores'.DIRECTORY_SEPARATOR.'eventCore.php';


// Part 0. What this suite stands on.
$pinFound = preg_match('/^\s*\$RequiredEventCoreVersion = \'v([0-9]+\.[0-9]+\.[0-9]+)\';$/m', (string)file_get_contents($entryFile), $pinMatch);
$RequiredEventCoreVersion = ($pinFound === 1) ? $pinMatch[1] : '';
check('the entry point pins the Event Core, & the tree\'s own core declares the version that is pinned', $pinFound === 1 && readComponentVersion($corePath, 'EventCoreVersion') === $RequiredEventCoreVersion, $RequiredEventCoreVersion);
check('no function of the Event Core is defined here, & none ever will be', coreFunctionsDefined() === array());


// Part 1. A core whose file says the version that is pinned, & that the kernel then refuses to load.
newCommand();
makeFolder(scratchPath('p01'), 0755);
$EventLoc = scratchPath('p01/Events');
$StubLoadAnswer = array(FALSE, '');
$refusedWarning = 'WARNING!!! The event record is neither written to nor read by this command, because the Event Core is refused. It could not be loaded.';
check('the core stands as ready, because its file says the version that is pinned', eventCoreStanding() === array('ready', $RequiredEventCoreVersion, ''), json_encode(eventCoreStanding()));
check('asking where it stands asked nothing of the kernel & logged nothing', $StubLoadCalls === array() && $TestLog === array());
check('a core the kernel refuses to load is refused, & says so', loadEventCore() === array(FALSE, 'refused', 'It could not be loaded.'), json_encode(array($EventCoreState, $EventCoreNotice)));
check('the kernel was asked once, for the Event Core by its file in the Engine, its variable & its pin', $StubLoadCalls === array(array('Event Core', $corePath, 'EventCoreVersion', $RequiredEventCoreVersion)), json_encode($StubLoadCalls));
check('no folder was made for a core that was not loaded', !file_exists($EventLoc) && folderNames(scratchPath('p01')) === array());
check('the command warned once', $TestLog === array($refusedWarning), json_encode($TestLog));
check('two events are not kept', noteEvent(plainFacts()) === FALSE && noteEvent(plainFacts()) === FALSE);
check('through all of that the kernel was asked once, the command warned once, & nothing was made', count($StubLoadCalls) === 1 && $TestLog === array($refusedWarning) && !file_exists($EventLoc), json_encode($TestLog));


// Part 2. The next command decides again.
newCommand();
check('the next command asks the kernel again, & warns once again', noteEvent(plainFacts()) === FALSE && noteEvent(plainFacts()) === FALSE && count($StubLoadCalls) === 1 && $TestLog === array($refusedWarning), json_encode($TestLog));


// Part 3. A core the kernel says it loaded, that defines none of its functions.
// The kernel pins a core by its version, so this is a file of the right version that is not the Event Core.
newCommand();
makeFolder(scratchPath('p03'), 0755);
$EventLoc = scratchPath('p03/Events');
$StubLoadAnswer = array(TRUE, 'v'.$RequiredEventCoreVersion);
check('a core the kernel says it loaded is ready, & its folder is made root\'s alone', loadEventCore() === array(TRUE, 'ready', '') && is_dir($EventLoc) && fileowner($EventLoc) === 0 && (fileperms($EventLoc) & 0777) === 0700, json_encode(array($EventCoreState, $EventCoreNotice)));
check('where that core defines no recordEvent() an event is not kept, nothing that is not there is called, & nothing is said', noteEvent(plainFacts()) === FALSE && $TestLog === array() && count($StubLoadCalls) === 1, json_encode($TestLog));


// Part 4. A record switched off. Nothing is asked of the kernel, nothing is made & nothing is said.
newCommand();
makeFolder(scratchPath('p04'), 0755);
$EventLoc = scratchPath('p04/Events');
$RecordEvents = FALSE;
check('a record switched off stands as off, & says which setting', eventCoreStanding() === array('off', $RequiredEventCoreVersion, '--Record Events-- is FALSE in config.php.'), json_encode(eventCoreStanding()));
check('loading a core switched off answers off without asking the kernel', loadEventCore() === array(FALSE, 'off', '--Record Events-- is FALSE in config.php.') && $StubLoadCalls === array(), json_encode($StubLoadCalls));
check('an event is not kept, nothing is made & nothing is said', noteEvent(plainFacts()) === FALSE && !file_exists($EventLoc) && $TestLog === array(), json_encode($TestLog));
$RecordEvents = TRUE;


// Part 5. An application that pins no version of the core is told so, & the kernel is not asked.
newCommand();
$pinnedHere = $RequiredEventCoreVersion;
$RequiredEventCoreVersion = '';
check('a core the application pins no version of is refused, & says so', eventCoreStanding() === array('refused', $pinnedHere, 'The application pins no version of it.'), json_encode(eventCoreStanding()));
check('loading it warns once with that reason & asks the kernel nothing', loadEventCore() === array(FALSE, 'refused', 'The application pins no version of it.') && $StubLoadCalls === array()
  && $TestLog === array('WARNING!!! The event record is neither written to nor read by this command, because the Event Core is refused. The application pins no version of it.'), json_encode($TestLog));


// Part 6. An application that pins another version is told which, with a v before each.
newCommand();
$RequiredEventCoreVersion = '4.3.0';
check('a core of another version is refused, & says what it reports & what is required', eventCoreStanding() === array('refused', $pinnedHere, 'It reports v'.$pinnedHere.' & the application requires v4.3.0.'), json_encode(eventCoreStanding()));
$RequiredEventCoreVersion = $pinnedHere;


// Part 7. A core that is not installed is detached, & names the file where the Engine keeps it.
newCommand();
makeFolder(scratchPath('p07/Resources'), 0755);
$InstLoc = scratchPath('p07');
check('a core that is not installed stands as detached, & names its file', eventCoreStanding() === array('detached', 'not installed', 'Resources/Engine/Cores/eventCore.php is not installed.'), json_encode(eventCoreStanding()));
check('loading it warns once & asks the kernel nothing', loadEventCore() === array(FALSE, 'detached', 'Resources/Engine/Cores/eventCore.php is not installed.') && $StubLoadCalls === array() && count($TestLog) === 1, json_encode($TestLog));
$InstLoc = $tree;


// Part 8. A command that is not root's finds the core unable, makes nothing & says so once.
newCommand();
makeFolder(scratchPath('p08'), 0755);
$EventLoc = scratchPath('p08/Events');
$RunningAsRoot = FALSE;
$StubLoadAnswer = array(TRUE, 'v'.$RequiredEventCoreVersion);
check('a command that is not root\'s finds the core unable, because only root keeps a folder to itself', loadEventCore() === array(FALSE, 'unable', 'Only root keeps a folder to itself.'), json_encode(array($EventCoreState, $EventCoreNotice)));
check('no folder was made, & the command warned once', !file_exists($EventLoc) && noteEvent(plainFacts()) === FALSE
  && $TestLog === array('WARNING!!! The event record is neither written to nor read by this command, because the Event Core is unable. Only root keeps a folder to itself.'), json_encode($TestLog));
$RunningAsRoot = TRUE;


// Part 9. Globals nothing set. The Engine sets its own the first time it is asked, as it does for the Authentication Core.
// The library stops the suite at the first warning PHP raises, so a global read before it was set would end the suite here.
newCommand();
unset($GLOBALS['EventCoreState'], $GLOBALS['EventCoreNotice'], $GLOBALS['EventRecordFailed']);
makeFolder(scratchPath('p09'), 0755);
$EventLoc = scratchPath('p09/Events');
$StubLoadAnswer = array(FALSE, '');
check('a command whose globals were never set still decides once, & warns once', noteEvent(plainFacts()) === FALSE && noteEvent(plainFacts()) === FALSE && count($StubLoadCalls) === 1 && $TestLog === array($refusedWarning), json_encode($TestLog));
check('it set each global it keeps, & set it to what it decided', isset($EventCoreState, $EventCoreNotice, $EventRecordFailed) && $EventCoreState === 'refused' && $EventCoreNotice === 'It could not be loaded.' && $EventRecordFailed === FALSE);


// Part 10. Nothing here failed to purge its memory, & the Event Core was never loaded.
newCommand();
check('no command in this suite raised error 40000, which is memory that could not be purged', $PurgeFailures === 0, (string)$PurgeFailures);
check('no function of the Event Core was ever defined', coreFunctionsDefined() === array(), implode(' ', coreFunctionsDefined()));

finish('test_events_load');
