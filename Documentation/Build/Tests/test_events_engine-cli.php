<?php
// HRProprietary Engine v4.3.1. The suite of the Event Core as the Engine loads it & keeps an event through it.
//
//   sudo bash Documentation/Build/Tests/contained.sh php Documentation/Build/Tests/test_events_engine-cli.php
// Run it from the root of the application under test. A tree named after it is tested instead, & must be that root.
//
// Nothing here stands in for anything. The kernel's own verifyCoreComponent() loads the tree's own Resources/Engine/Cores/eventCore.php,
// by the path engine.php names, & the four functions engine.php holds keep an event through it into a folder beneath the scratch folder.
// test_events-cli.php holds the core to every rule it has, & test_events_load-cli.php holds engine.php to what it does where the core is not ready.
// This suite holds the two together, which is what neither of them can see.
//
// It removes nothing. It makes one folder beneath the scratch folder & leaves it there.

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
$TestLog = array();

$kernelFile = $tree.'/Resources/Kernel/kernel.php';
$engineFile = $tree.'/Resources/Engine/engine.php';
$EntryPointName = testEntryPoint($tree);
$entryFile = $tree.'/'.$EntryPointName;
$coreFile = $tree.'/Resources/Engine/Cores/eventCore.php';
if (!is_file($coreFile)) testStop($coreFile.' does not exist, so there is no Event Core to load.');
$coreCalls = forbiddenCallsIn(token_get_all((string)file_get_contents($coreFile)), array());
if (count($coreCalls) > 0) testStop('The Event Core calls '.implode(', ', $coreCalls).', & this suite does not load a core that removes, renames or runs anything.');
liftFunctions($kernelFile, array('getChildFunction', 'purgeSensitiveMemory', 'pathIsTrustedByRoot', 'findAccountDatabaseEntry', 'identifyRunningAccount', 'folderIsHeldForRoot', 'openFileWithoutFollowing', 'printableText', 'sanitizeString', 'sanitize', 'readComponentVersion', 'verifyCoreComponent'), array());
liftFunctions($engineFile, array('claimPrivateFolder', 'eventCoreStanding', 'loadEventCore', 'noteEvent'), array());
$pinFound = preg_match('/^\s*\$RequiredEventCoreVersion = \'v([0-9]+\.[0-9]+\.[0-9]+(?:u?r)?)\';$/m', (string)file_get_contents($entryFile), $pinMatch);
$RequiredEventCoreVersion = ($pinFound === 1) ? $pinMatch[1] : '';

function plainFacts($eventSummary) {
  return array('Source' => 'engine', 'Kind' => 'test.kept', 'Category' => 1, 'Severity' => 'low', 'Summary' => $eventSummary,
    'Subject' => array('Path' => '/srv/www/a site/a file', 'PathEncoded' => base64_encode('/srv/www/a site/a file')), 'Detail' => array('Count' => 2)); }


// Part 0. What this suite stands on.
check('the Event Core calls nothing that removes, renames or runs a command', $coreCalls === array());
check('the entry point pins the Event Core', $pinFound === 1, $RequiredEventCoreVersion);
check('no function of the Event Core is defined before the Engine loads it', !function_exists('recordEvent') && !function_exists('readEvents'));


// Part 1. The Engine loads the core through the kernel, by the path engine.php names, & makes its folder root's alone.
makeFolder(scratchPath('engine'), 0755);
$EventLoc = scratchPath('engine/Events');
check('the core is ready, & nothing was said', loadEventCore() === array(TRUE, 'ready', '') && $TestLog === array(), json_encode($TestLog));
check('the kernel loaded it, & published the version it reports', function_exists('recordEvent') && function_exists('findEvent') && isset($GLOBALS['EventCoreVersion']) && $GLOBALS['EventCoreVersion'] === 'v'.$RequiredEventCoreVersion);
check('its folder was made, root\'s & 0700', is_dir($EventLoc) && fileowner($EventLoc) === 0 && (fileperms($EventLoc) & 0777) === 0700);
check('asking again loads nothing again & says nothing', loadEventCore() === array(TRUE, 'ready', '') && $TestLog === array(), json_encode($TestLog));


// Part 2. An event kept through noteEvent() is in the record, & reads back as it was handed over.
check('noteEvent() keeps an event', noteEvent(plainFacts('An event the Engine kept.')) === TRUE && $TestLog === array(), json_encode($TestLog));
$dayFile = $EventLoc.'/Events-'.gmdate('Ymd').'.jsonl';
check('the day\'s file is there, root\'s & 0600, & holds one line', is_file($dayFile) && fileowner($dayFile) === 0 && (fileperms($dayFile) & 0777) === 0600 && substr_count((string)file_get_contents($dayFile), "\n") === 1);
list ($allWasRead, $eventsRead, $readerNotes) = readEvents(time() - 3600, 'info', 10);
$keptEvent = (is_array($eventsRead) && count($eventsRead) === 1) ? $eventsRead[0] : array();
check('readEvents() gives it back, & nothing else', $allWasRead === TRUE && count($eventsRead) === 1 && $readerNotes === array(), json_encode($readerNotes));
check('it holds what was handed over, & the version of the application that kept it', field($keptEvent, 'Source') === 'engine' && field($keptEvent, 'Kind') === 'test.kept'
  && field($keptEvent, 'Severity') === 'low' && field($keptEvent, 'Summary') === 'An event the Engine kept.' && field($keptEvent, 'Version') === '4.3.1'
  && field(field($keptEvent, 'Subject'), 'PathEncoded') === base64_encode('/srv/www/a site/a file') && field(field($keptEvent, 'Detail'), 'Count') === 2, json_encode($keptEvent));
list ($eventWasFound, $foundEvent) = findEvent((string)field($keptEvent, 'Id'));
check('findEvent() finds it by its identifier', $eventWasFound === TRUE && $foundEvent === $keptEvent);


// Part 3. An event that is not well formed is not kept, & the command says so once for every one it cannot keep.
check('an event of category 9 is not kept', noteEvent(array('Source' => 'engine', 'Kind' => 'test.kept', 'Category' => 9, 'Severity' => 'low', 'Summary' => 'Never kept.')) === FALSE);
check('the command warned once, with the reason the core gave', count($TestLog) === 1 && strpos((string)$TestLog[0], 'WARNING!!! An event could not be written to the event record, & no other that cannot be is said by this command. An event was not kept, because its Category') === 0, json_encode($TestLog));
check('a second it cannot keep is passed over without a word, & one it can is still kept', noteEvent(array('Source' => 'engine', 'Kind' => 'test.kept', 'Category' => 1, 'Severity' => 'nothing', 'Summary' => 'Never kept.')) === FALSE
  && noteEvent(plainFacts('A second event the Engine kept.')) === TRUE && count($TestLog) === 1, json_encode($TestLog));
check('the record holds the two that were kept & nothing else', substr_count((string)file_get_contents($dayFile), "\n") === 2);


// Part 4. Nothing here failed to purge its memory.
check('no function here raised error 40000, which is memory that could not be purged', preg_grep('/^ERROR!!! 40000/', $TestLog) === array(), json_encode($TestLog));

finish('test_events_engine');
