<?php
// HRProprietary Engine v4.3.2. The unit suite of a command an application puts on a schedule.
// It is the Engine's half of HRProtect's tests/test_schedule.php, its sections 1 to 8, from HRProtect's v0.0.7: what a timer may be
// named, what a record is held to, the two units written for one, how they are installed & written again, & what is asked of systemd
// about one. The application is a stand-in named HRProtect, whose timer these were first, with one entry point, one folder & one record.
//
//   sudo bash Documentation/Build/Tests/contained.sh php Documentation/Build/Tests/test_schedule-cli.php
// Run it from the root of the application under test. A tree named after it is tested instead, & must be that root.
//
// systemd is not running where this runs, & nothing here asks it anything. exec() is a stand-in in the namespace the Engine's
// functions are lifted into. It answers what a check set, & keeps what it was asked. It runs one command for real, & only where
// a check says it may: systemd-analyze calendar, which reads a calendar & changes nothing. Any other command stops the suite.
// The folder units are written into is a folder of the suite's own, beneath its scratch folder, which systemdUnitFolder() is
// stood in for to name. No test writes to /etc, which is read only here, & no test has had systemd start a unit.
// The suite removes nothing. The kernel's writeFileWithoutFollowing() renames a file it finished & removes one it could not finish,
// which is what is under test, & every file it is handed is beneath the scratch folder.
// Section 9 has PHP remember a unit as leading to another file, as testlib-cli.php says how, which starts mv, without a shell,
// on two names beneath the scratch folder. It makes links beneath the scratch folder too, to /dev/null & to units kept in another
// folder of the suite's, to stand for units that were masked or linked.

if (PHP_SAPI !== 'cli') { if (!headers_sent()) http_response_code(404); die(); }

if (!is_file('Documentation/Build/Tests/testlib-cli.php')) { fwrite(STDERR, 'Run this from the root of the application it tests. See Documentation/Build/Tests/README.txt.'.PHP_EOL); exit(64); }
require 'Documentation/Build/Tests/testlib-cli.php';

$tree = testTree(isset($argv[1]) ? $argv[1] : (string)getcwd());
requireContainment();

// What the kernel & the Engine would have set before any of these functions ran.
$EnableMemoryProtection = TRUE;
$Verbose = FALSE;
$LogFile = '';
$RunningAsRoot = TRUE;
$DirSep = DIRECTORY_SEPARATOR;
$Lol = PHP_EOL;
$base = makeFolder(scratchPath('schedule'), 0755);

// The stand-ins for the rest of the Engine & for the application. Each answers what a check set, & keeps what it was asked.
$Located = array(); $LocateCalls = array();
function locateDependency($binaryName) { global $Located, $LocateCalls; $LocateCalls[] = $binaryName; return isset($Located[$binaryName]) ? $Located[$binaryName] : ''; }
function engineApplicationSlug() { return 'hrprotect'; }
function engineApplicationName() { return 'HRProtect'; }
function engineApplicationEntryPoint() { return 'protectCore.php'; }
$EntryFolder = '/DATA/HRProtect/Application';
function serviceEntryFolder() { global $EntryFolder; return $EntryFolder; }
$Systemd = array(TRUE, 'systemd is running & systemctl is available.');
function systemdIsUsable() { global $Systemd; return $Systemd; }

// What exec() is where the Engine's functions are lifted. A check sets what a command answers, by a part of the command.
// One command may be run for real, systemd-analyze calendar, & only where a check names the program. Nothing else is ever run.
$ExecCalls = array(); $ExecAnswers = array(); $ExecMayRun = '';
function hookedExec($command, &$output, &$resultCode) {
  global $ExecCalls, $ExecAnswers, $ExecMayRun;
  $ExecCalls[] = $command;
  foreach ($ExecAnswers as $part => $answer) {
    if (strpos($command, $part) === FALSE) continue;
    if (!is_array($output)) $output = array();
    foreach ($answer[0] as $line) $output[] = $line;
    $resultCode = $answer[1];
    return count($answer[0]) > 0 ? $answer[0][count($answer[0]) - 1] : ''; }
  if ($ExecMayRun !== '' && strpos($command, escapeshellarg($ExecMayRun).' calendar ') === 0) return \exec($command, $output, $resultCode);
  testStop('The code under test ran "'.$command.'", & this suite runs no such command.'); }
eval('namespace hooked; function exec($command, &$output = NULL, &$resultCode = NULL) { return \hookedExec($command, $output, $resultCode); }');

liftFunctions($tree.'/Resources/Kernel/kernel.php', array('getChildFunction', 'purgeSensitiveMemory', 'redeclare', 'printableText', 'sanitizePath', 'pathIsTrustedByRoot', 'findAccountDatabaseEntry', 'identifyRunningAccount', 'folderIsHeldForRoot', 'writeFileWithoutFollowing', 'readFileWithoutFollowing'), array('rename', 'unlink'));
liftFunctions($tree.'/Resources/Engine/engine.php', array('systemdUnitFolder', 'applicationTimerNameIsValid'), array());
liftFunctionsInto('hooked', $tree.'/Resources/Engine/engine.php', array('applicationTimerState'), array('exec'));
liftFunctionsInto('hooked', $tree.'/Resources/Engine/Cores/setupCore.php', array('applicationCalendarIsReadable', 'examineApplicationTimer', 'readApplicationTimers', 'generateApplicationTimer', 'writeApplicationTimer', 'installApplicationTimers'), array('exec'));
standInFor('hooked', array('systemdUnitFolder', 'writeFileWithoutFollowing'));

// Where units are written here, which a check may change. Every check sets its hooks through this, so the folder is always the suite's own.
$UnitFolder = makeFolder($base.'/units', 0755);
function hooks($more = array()) { return setHooks(array_merge(array('systemdUnitFolder' => function ($arguments, $count) { global $UnitFolder; return array('answer', $UnitFolder); }), $more)); }
// Begins the list of what was run, located & logged again.
function quietly() { global $TestLog, $ExecCalls, $LocateCalls; $TestLog = $ExecCalls = $LocateCalls = array(); }
// Runs a function & hands back what it returned & what it printed.
function printing($callable) { $arguments = func_get_args(); array_shift($arguments); ob_start(); $returned = call_user_func_array($callable, $arguments); $printed = (string)ob_get_clean(); return array($returned, $printed); }
// A line of what an installer prints: a word in a column of twelve, & what it says.
function line($word, $text) { return '  '.str_pad($word, 12).$text.PHP_EOL; }
// A record a provider would return, with one field as a check asks.
function record($changes = array()) { return array_merge(array('Description' => 'Scheduled scan', 'Argument' => '--app-scan-scheduled', 'Calendar' => 'daily', 'Document' => 'ABOUT_SCANNING.txt', 'Reach' => 'host', 'Success' => array(1)), $changes); }
function without($field) { $record = record(); unset($record[$field]); return $record; }
$accepted = record();
hooks();


// 1. What a timer may be named. Every function that puts a name into a path or a unit asks this.
check('a name is lower case letters, digits & dashes, & begins with a letter', applicationTimerNameIsValid('scan') && applicationTimerNameIsValid('s') && applicationTimerNameIsValid('scan-2-nightly') && applicationTimerNameIsValid(str_repeat('a', 32)));
check('one that begins with a digit or a dash, holds a capital, a dot, a slash or a space, or is empty is not one', !applicationTimerNameIsValid('2scan') && !applicationTimerNameIsValid('-scan') && !applicationTimerNameIsValid('Scan') && !applicationTimerNameIsValid('scan.timer') && !applicationTimerNameIsValid('../scan') && !applicationTimerNameIsValid('scan now') && !applicationTimerNameIsValid(''));
check('neither is one of 33 characters, or one that ends in a line break', !applicationTimerNameIsValid(str_repeat('a', 33)) && !applicationTimerNameIsValid("scan\n"));
check('the Engine\'s own two names are no application\'s', !applicationTimerNameIsValid('environment') && !applicationTimerNameIsValid('listener') && applicationTimerNameIsValid('environments') && applicationTimerNameIsValid('listen'));
check('what is not text is no name', !applicationTimerNameIsValid(0) && !applicationTimerNameIsValid(NULL) && !applicationTimerNameIsValid(array('scan')) && !applicationTimerNameIsValid(TRUE));
check('units are kept where an administrator\'s own are', systemdUnitFolder() === '/etc/systemd/system');


// 2. A calendar. It is held to the characters one is made of, & systemd-analyze is asked where it is installed.
quietly();
$readable = array('daily', 'hourly', 'weekly', 'Sun 03:00', 'Mon *-*-* 02:30:00', '*-*-* 03:00', '*:0/15', 'Mon..Fri 22:30', 'Sat,Sun 20:00', '2026-11-20 03:00 UTC', '*-*~1 03:00', '03:00 America/New_York', str_repeat('1', 64));
$allRead = TRUE; foreach ($readable as $calendar) if (\hooked\applicationCalendarIsReadable($calendar) !== TRUE) $allRead = FALSE;
check('where systemd-analyze is not installed, what is made of the characters of a calendar is believed', $allRead === TRUE && $ExecCalls === array() && $LocateCalls === array_fill(0, count($readable), 'systemd-analyze'), json_encode($ExecCalls));
quietly();
$unreadable = array('', ' daily', "daily\nExecStart=/bin/sh", 'daily%H', '$daily', 'daily;hourly', 'daily=1', 'daily"', "daily'", 'daily\\', '-1', '/daily', str_repeat('1', 65), "daily\n", NULL, 7, array('daily'), TRUE);
$noneRead = TRUE; foreach ($unreadable as $calendar) if (\hooked\applicationCalendarIsReadable($calendar) !== FALSE) $noneRead = FALSE;
check('what holds anything else, begins with a space, a dash or a slash, is longer than 64 characters or is not text is not read', $noneRead === TRUE);
check('& nothing is looked for or asked about such a calendar', $ExecCalls === array() && $LocateCalls === array(), json_encode(array($ExecCalls, $LocateCalls)));
$Located = array('systemd-analyze' => '/usr/bin/systemd-analyze');
quietly(); $ExecAnswers = array(' calendar ' => array(array('Normalized form: *-*-* 00:00:00'), 0));
check('where it is installed it is asked, & a calendar it reads is read', \hooked\applicationCalendarIsReadable('daily') === TRUE && $ExecCalls === array("'/usr/bin/systemd-analyze' calendar 'daily' 2>&1"), json_encode($ExecCalls));
quietly(); $ExecAnswers = array(' calendar ' => array(array('Failed to parse calendar specification'), 1));
check('one it does not read is not, whatever it is made of', \hooked\applicationCalendarIsReadable('every day') === FALSE && $ExecCalls === array("'/usr/bin/systemd-analyze' calendar 'every day' 2>&1"), json_encode($ExecCalls));
quietly(); $ExecAnswers = array(' calendar ' => array(array(), 2));
check('any status but 0 is a calendar that was not read', \hooked\applicationCalendarIsReadable('daily') === FALSE);
quietly(); $ExecAnswers = array(' calendar ' => array(array(), 0));
check('a calendar that is not made of the characters of one is still never handed over', \hooked\applicationCalendarIsReadable('daily;id') === FALSE && $ExecCalls === array());
// The real program, where this machine has it. It reads a calendar & changes nothing.
$analyze = (is_file('/usr/bin/systemd-analyze') && is_executable('/usr/bin/systemd-analyze')) ? '/usr/bin/systemd-analyze' : '';
$ExecAnswers = array();
if ($analyze === '') print('  SKIPPED  what systemd-analyze itself makes of a calendar, because it is not installed here'.PHP_EOL);
else {
  $Located = array('systemd-analyze' => $analyze); $ExecMayRun = $analyze; quietly();
  check('systemd-analyze itself reads daily & a day with a time', \hooked\applicationCalendarIsReadable('daily') === TRUE && \hooked\applicationCalendarIsReadable('Sun 03:00') === TRUE && \hooked\applicationCalendarIsReadable('Mon *-*-* 02:30:00') === TRUE && count($ExecCalls) === 3, json_encode($ExecCalls));
  check('& does not read words that are no calendar, or an hour there is none of', \hooked\applicationCalendarIsReadable('every day') === FALSE && \hooked\applicationCalendarIsReadable('25:00') === FALSE);
  $ExecMayRun = ''; }
$Located = array();


// 3. One record. It is accepted whole or refused whole, & what is said of it is a warning as well.
quietly();
check('a record that is fit is accepted as it will be written, with nothing to say', \hooked\examineApplicationTimer('scan', record()) === array(TRUE, $accepted, '') && $TestLog === array(), json_encode(\hooked\examineApplicationTimer('scan', record())));
check('a document & the statuses that are a success may be left out', \hooked\examineApplicationTimer('scan', without('Document')) === array(TRUE, record(array('Document' => '')), '') && \hooked\examineApplicationTimer('scan', without('Success')) === array(TRUE, record(array('Success' => array())), '') && \hooked\examineApplicationTimer('scan', record(array('Document' => ''))) === array(TRUE, record(array('Document' => '')), ''));
check('the statuses are kept once each & in order', \hooked\examineApplicationTimer('scan', record(array('Success' => array(3, 1, 3, 255, 1))))[1]['Success'] === array(1, 3, 255) && \hooked\examineApplicationTimer('scan', record(array('Success' => array(8, 7, 6, 5, 4, 3, 2, 1))))[1]['Success'] === array(1, 2, 3, 4, 5, 6, 7, 8));
check('a calendar keeps what is inside the spaces around it, & a reach may be confined', \hooked\examineApplicationTimer('scan', record(array('Calendar' => '  Sun 03:00 ', 'Reach' => 'confined'))) === array(TRUE, record(array('Calendar' => 'Sun 03:00', 'Reach' => 'confined')), ''));
check('a field the Engine does not know is not carried into what is written', \hooked\examineApplicationTimer('scan', record(array('ExecStartPre' => '/bin/sh')))[1] === $accepted);
// Refused, one field at a time. Each says which field, & is a warning.
$refusals = array(
  'its name is not one a timer of an application may have' => array(array('Scan', record()), array('environment', record()), array('listener', record()), array(0, record()), array('scan/../x', record())),
  'what it was given is no record' => array(array('scan', 'daily'), array('scan', NULL), array('scan', 7)),
  'its Description is not one a unit may carry' => array(array('scan', without('Description')), array('scan', record(array('Description' => ''))), array('scan', record(array('Description' => "Scan\nExecStart=/bin/sh"))), array('scan', record(array('Description' => 'Scan %H'))), array('scan', record(array('Description' => ' Scan'))), array('scan', record(array('Description' => str_repeat('a', 81)))), array('scan', record(array('Description' => array('Scan'))))),
  'its Argument is not one argument beginning --app-' => array(array('scan', without('Argument')), array('scan', record(array('Argument' => '-fp'))), array('scan', record(array('Argument' => '--app-scan /etc'))), array('scan', record(array('Argument' => '--app-'))), array('scan', record(array('Argument' => '--APP-SCAN'))), array('scan', record(array('Argument' => '--app-scan;id'))), array('scan', record(array('Argument' => '--app-'.str_repeat('a', 49)))), array('scan', record(array('Argument' => "--app-scan\n")))),
  'its Document is not the name of a file' => array(array('scan', record(array('Document' => '../../etc/passwd'))), array('scan', record(array('Document' => 'A B.txt'))), array('scan', record(array('Document' => '.hidden'))), array('scan', record(array('Document' => 7))), array('scan', record(array('Document' => str_repeat('a', 65))))),
  'its Reach is neither confined nor host' => array(array('scan', without('Reach')), array('scan', record(array('Reach' => 'Host'))), array('scan', record(array('Reach' => ''))), array('scan', record(array('Reach' => TRUE))), array('scan', record(array('Reach' => 'none')))),
  'its Success is not a list of exit statuses from 1 to 255, eight at most' => array(array('scan', record(array('Success' => 1))), array('scan', record(array('Success' => array(0)))), array('scan', record(array('Success' => array(256)))), array('scan', record(array('Success' => array('1')))), array('scan', record(array('Success' => array(1.0)))), array('scan', record(array('Success' => array(1, 2, 3, 4, 5, 6, 7, 8, 9)))), array('scan', record(array('Success' => array(1, 1, 1, 1, 1, 1, 1, 1, 1)))), array('scan', record(array('Success' => array(1, -1))))));
foreach ($refusals as $reason => $cases) {
  $allRefused = TRUE; $wrong = '';
  foreach ($cases as $case) {
    quietly();
    $shown = is_scalar($case[0]) ? (string)$case[0] : '';
    $notice = 'The timer '.$shown.' was refused, because '.$reason.'.';
    if (\hooked\examineApplicationTimer($case[0], $case[1]) !== array(FALSE, array(), $notice) or $TestLog !== array('WARNING!!! '.$notice)) { $allRefused = FALSE; $wrong = json_encode($case); } }
  check('a timer is refused where '.$reason, $allRefused === TRUE, $wrong); }
check('the Description may be 80 characters, the Argument 48 after --app-, the Document 64 & the statuses eight', \hooked\examineApplicationTimer('scan', record(array('Description' => str_repeat('a', 80), 'Argument' => '--app-'.str_repeat('a', 48), 'Document' => str_repeat('a', 64), 'Success' => array(1, 2, 3, 4, 5, 6, 7, 8))))[0] === TRUE && \hooked\examineApplicationTimer('scan', record(array('Success' => array(255))))[0] === TRUE);
quietly();
check('a name nobody could print is shown as it can be', \hooked\examineApplicationTimer("sc\x1b[2Jan", record())[2] === 'The timer sc?[2Jan was refused, because its name is not one a timer of an application may have.' && \hooked\examineApplicationTimer(array('scan'), record())[2] === 'The timer  was refused, because its name is not one a timer of an application may have.');
// The calendar is the one field that falls back.
foreach (array(array('every;day', 'every;day'), array('', 'which is empty'), array(7, 'which is empty'), array("daily\nExecStart=/bin/sh", 'daily?ExecStart=/bin/sh')) as $case) {
  quietly();
  $notice = 'The calendar of the timer scan, '.$case[1].', is not one systemd reads, so the timer runs daily.';
  check('a calendar that is '.json_encode($case[0]).' falls back to daily, the timer is accepted & a warning says so', \hooked\examineApplicationTimer('scan', record(array('Calendar' => $case[0]))) === array(TRUE, record(array('Calendar' => 'daily')), $notice) && $TestLog === array('WARNING!!! '.$notice), json_encode($TestLog)); }
quietly();
check('a record with no calendar at all falls back as well', \hooked\examineApplicationTimer('scan', without('Calendar'))[1]['Calendar'] === 'daily' && count($TestLog) === 1);
$Located = array('systemd-analyze' => '/usr/bin/systemd-analyze'); $ExecAnswers = array(' calendar ' => array(array(), 1)); quietly();
check('so does one made of the right characters that systemd-analyze does not read', \hooked\examineApplicationTimer('scan', record(array('Calendar' => 'every day')))[1]['Calendar'] === 'daily' && $TestLog === array('WARNING!!! The calendar of the timer scan, every day, is not one systemd reads, so the timer runs daily.'));
$Located = array(); $ExecAnswers = array();


// 4. The provider. It is named in the contract, called once, & each record it returns is examined.
function noTimers() { global $ProviderCalls; $ProviderCalls++; return array(); }
function oneTimer() { global $ProviderCalls; $ProviderCalls++; return array('scan' => record()); }
function mixedTimers() { return array('scan' => record(), 'Bad' => record(), 'probe' => record(array('Argument' => '--app-coverage', 'Calendar' => 'every;day', 'Reach' => 'confined', 'Success' => array())), 'worse' => record(array('Reach' => 'everything'))); }
function noList() { return 'scan'; }
function manyTimers() { $timers = array(); for ($number = 1; $number <= 18; $number++) $timers['t'.$number] = record(); return $timers; }
$ProviderCalls = 0;
foreach (array('', '   ', NULL, 7, array('oneTimer')) as $unnamed) {
  $EngineTimerProvider = $unnamed; quietly();
  check('a contract that names '.json_encode($unnamed).' names no provider, & nothing is said', \hooked\readApplicationTimers() === array(FALSE, array(), array()) && $TestLog === array() && $ProviderCalls === 0); }
unset($EngineTimerProvider); quietly();
check('neither does a contract that never set the name', \hooked\readApplicationTimers() === array(FALSE, array(), array()) && $TestLog === array());
$EngineTimerProvider = 'oneTimer'; quietly();
check('a provider is called once & its timer comes back as it will be written', \hooked\readApplicationTimers() === array(TRUE, array('scan' => $accepted), array()) && $ProviderCalls === 1 && $TestLog === array());
$EngineTimerProvider = '  oneTimer  '; quietly();
check('the spaces around its name are not part of it', \hooked\readApplicationTimers() === array(TRUE, array('scan' => $accepted), array()));
$EngineTimerProvider = 'noTimers'; quietly();
check('a provider with nothing to schedule is named & has no timers', \hooked\readApplicationTimers() === array(TRUE, array(), array()) && $TestLog === array());
$EngineTimerProvider = 'thereIsNoSuchFunction'; quietly();
$missing = 'A timer provider named thereIsNoSuchFunction was declared & does not exist. The application puts nothing on a schedule.';
check('a provider that is named & not defined is a warning, & there are no timers', \hooked\readApplicationTimers() === array(TRUE, array(), array($missing)) && $TestLog === array('WARNING!!! '.$missing), json_encode($TestLog));
$EngineTimerProvider = 'noList'; quietly();
$shape = 'The timer provider named noList answered with no list. The application puts nothing on a schedule.';
check('so is one that answers with something that is no list', \hooked\readApplicationTimers() === array(TRUE, array(), array($shape)) && $TestLog === array('WARNING!!! '.$shape), json_encode($TestLog));
$EngineTimerProvider = 'mixedTimers'; quietly();
list ($named, $timers, $notices) = \hooked\readApplicationTimers();
$expectedNotices = array('The timer Bad was refused, because its name is not one a timer of an application may have.', 'The calendar of the timer probe, every;day, is not one systemd reads, so the timer runs daily.', 'The timer worse was refused, because its Reach is neither confined nor host.');
check('the timers that are fit are kept, in their order & under their names, & the others are left out', $named === TRUE && $timers === array('scan' => $accepted, 'probe' => record(array('Argument' => '--app-coverage', 'Calendar' => 'daily', 'Reach' => 'confined', 'Success' => array()))), json_encode($timers));
check('what was said of each is handed back, & each was logged once', $notices === $expectedNotices && $TestLog === array('WARNING!!! '.$expectedNotices[0], 'WARNING!!! '.$expectedNotices[1], 'WARNING!!! '.$expectedNotices[2]), json_encode($TestLog));
$EngineTimerProvider = 'manyTimers'; quietly();
list ($named, $timers, $notices) = \hooked\readApplicationTimers();
$tooMany = 'An application asks for sixteen timers at most, so every one after the sixteenth was refused.';
check('sixteen timers are examined & no more, & that is said once', array_keys($timers) === array('t1', 't2', 't3', 't4', 't5', 't6', 't7', 't8', 't9', 't10', 't11', 't12', 't13', 't14', 't15', 't16') && $notices === array($tooMany) && $TestLog === array('WARNING!!! '.$tooMany), json_encode(array_keys($timers)));


// 5. The two units, to the letter.
$Located = array('php' => '/usr/bin/php');
$serviceHead = '# / -----------------------------------------------------------------------------------'.PHP_EOL
  .'# / HRProtect Scheduled scan. Written by --setup --install-service.'.PHP_EOL
  .'# / Do not edit this by hand. Re-run the command above, or -fp, to rewrite it.'.PHP_EOL
  .'# /'.PHP_EOL
  .'# / This runs as root & is started ONLY by hrprotect-scan.timer. It runs one command'.PHP_EOL
  .'# / of HRProtect\'s own & exits. It opens no socket & accepts nothing from anywhere.'.PHP_EOL
  .PHP_EOL
  .'[Unit]'.PHP_EOL
  .'Description=HRProtect Scheduled scan'.PHP_EOL;
$serviceDocument = 'Documentation=file:/DATA/HRProtect/Application/Documentation/ABOUT_SCANNING.txt'.PHP_EOL;
$serviceMiddle = 'After=local-fs.target'.PHP_EOL
  .PHP_EOL
  .'[Service]'.PHP_EOL
  .'# / It wakes, works & exits. Nothing stays resident.'.PHP_EOL
  .'Type=oneshot'.PHP_EOL
  .'User=root'.PHP_EOL
  .'Group=root'.PHP_EOL
  .'ExecStart=/usr/bin/php /DATA/HRProtect/Application/protectCore.php --app-scan-scheduled'.PHP_EOL
  .'# / The command runs for as long as its work takes. Nothing here ends it but the command.'.PHP_EOL
  .'TimeoutStartSec=infinity'.PHP_EOL;
$serviceSuccess = '# / Each of these exit statuses means the command did its work, as 0 does.'.PHP_EOL
  .'SuccessExitStatus=1'.PHP_EOL;
$serviceHost = '# / This command reaches the whole host, as its application asked. Nothing is hidden'.PHP_EOL
  .'# / from it & nothing is taken from it, so it runs as it does for root at a terminal.'.PHP_EOL;
$serviceConfined = '# / A root process is confined, as the Environment Manager\'s is.'.PHP_EOL
  .'NoNewPrivileges=yes'.PHP_EOL
  .'PrivateTmp=yes'.PHP_EOL
  .'ProtectHome=yes'.PHP_EOL
  .'RestrictSUIDSGID=yes'.PHP_EOL;
$serviceTail = PHP_EOL
  .'[Install]'.PHP_EOL
  .'# / Deliberately empty of WantedBy. The TIMER is what an administrator enables.'.PHP_EOL
  .'# / Enabling this service on its own would run the command once at boot & never again.'.PHP_EOL;
$timerHead = '# / -----------------------------------------------------------------------------------'.PHP_EOL
  .'# / HRProtect Scheduled scan timer. Written by --setup --install-service.'.PHP_EOL
  .'# / Do not edit this by hand. Re-run the command above, or -fp, to rewrite it.'.PHP_EOL
  .'# /'.PHP_EOL
  .'# / Enable it with:  sudo systemctl enable --now hrprotect-scan.timer'.PHP_EOL
  .'# / See when it runs next with:  systemctl list-timers hrprotect-scan.timer'.PHP_EOL
  .'# / See what its last run said with:  sudo journalctl -u hrprotect-scan.service'.PHP_EOL
  .PHP_EOL
  .'[Unit]'.PHP_EOL
  .'Description=HRProtect Scheduled scan timer'.PHP_EOL;
function timerTail($calendar) { return PHP_EOL
  .'[Timer]'.PHP_EOL
  .'OnCalendar='.$calendar.PHP_EOL
  .'# / A host that was off runs the pass it missed rather than skipping it silently.'.PHP_EOL
  .'Persistent=true'.PHP_EOL
  .'# / Every installation on a fleet would otherwise wake on the same second.'.PHP_EOL
  .'RandomizedDelaySec=300'.PHP_EOL
  .'Unit=hrprotect-scan.service'.PHP_EOL
  .PHP_EOL
  .'[Install]'.PHP_EOL
  .'WantedBy=timers.target'.PHP_EOL; }
$hostService = $serviceHead.$serviceDocument.$serviceMiddle.$serviceSuccess.$serviceHost.$serviceTail;
$hostTimer = $timerHead.$serviceDocument.timerTail('daily');
quietly();
list ($built, $service, $timer, $notice) = \hooked\generateApplicationTimer('scan', $accepted);
check('the service of a host unit is this, to the letter', $built === TRUE && $service === $hostService && $notice === '' && $TestLog === array(), $service);
check('& its timer is this', $timer === $hostTimer, $timer);
check('a host unit carries no directive that hides anything or takes anything away', preg_match('/^(NoNewPrivileges|PrivateTmp|ProtectHome|ProtectSystem|RestrictSUIDSGID|PrivateDevices|ReadOnlyPaths|InaccessiblePaths|CapabilityBoundingSet)=/m', $service) === 0);
list ($built, $service, $timer, $notice) = \hooked\generateApplicationTimer('scan', record(array('Reach' => 'confined', 'Document' => '', 'Success' => array(), 'Calendar' => 'Sun 03:00')));
check('a confined unit carries the four the Environment Manager\'s does, & one with no document & no statuses names neither', $service === $serviceHead.$serviceMiddle.$serviceConfined.$serviceTail && $timer === $timerHead.timerTail('Sun 03:00'), $service.$timer);
list ($built, $service, $timer, $notice) = \hooked\generateApplicationTimer('scan', record(array('Success' => array(1, 3, 75))));
check('several statuses are written on one line, with a space between them', strpos($service, PHP_EOL.'SuccessExitStatus=1 3 75'.PHP_EOL) !== FALSE);
list ($built, $service, $timer, $notice) = \hooked\generateApplicationTimer('probe-2', record(array('Description' => 'Coverage, each night & day.', 'Argument' => '--app-coverage')));
check('the units are named for the application & the timer, & run the one argument', strpos($service, 'started ONLY by hrprotect-probe-2.timer') !== FALSE && strpos($service, PHP_EOL.'Description=HRProtect Coverage, each night & day.'.PHP_EOL) !== FALSE && strpos($service, PHP_EOL.'ExecStart=/usr/bin/php /DATA/HRProtect/Application/protectCore.php --app-coverage'.PHP_EOL) !== FALSE && strpos($timer, PHP_EOL.'Unit=hrprotect-probe-2.service'.PHP_EOL) !== FALSE && strpos($timer, 'enable --now hrprotect-probe-2.timer') !== FALSE);
check('neither unit names a version, so a release that changes nothing leaves both alone', strpos($hostService.$hostTimer, '0.0.') === FALSE && strpos($hostService.$hostTimer, '4.2.') === FALSE && strpos($hostService.$hostTimer, '4.3.') === FALSE);
$Located = array(); quietly();
list ($built, $service, $timer, $notice) = \hooked\generateApplicationTimer('scan', $accepted);
check('where PHP cannot be found by name the one that is running is written', $built === TRUE && strpos($service, PHP_EOL.'ExecStart='.PHP_BINARY.' /DATA/HRProtect/Application/protectCore.php --app-scan-scheduled'.PHP_EOL) !== FALSE && $LocateCalls === array('php'), $service);
// What is not built.
foreach (array('/usr/bin/php 8', '/opt/php%h/bin/php', "/usr/bin/php\n", 'php', '/usr/bin/$php') as $badBinary) {
  $Located = array('php' => $badBinary); quietly();
  $said = 'The timer scan was not built, because the path to PHP, '.printableText($badBinary, TRUE).', holds something a unit cannot carry.';
  check('nothing is built where the path to PHP is '.json_encode($badBinary), \hooked\generateApplicationTimer('scan', $accepted) === array(FALSE, '', '', $said) && $TestLog === array('WARNING!!! '.$said), json_encode($TestLog)); }
$Located = array('php' => '/usr/bin/php');
foreach (array('/DATA/HR Protect/Application', '/DATA/HRProtect%i/Application', 'DATA/HRProtect', '/DATA/$HOME') as $badFolder) {
  $EntryFolder = $badFolder; quietly();
  $said = 'The timer scan was not built, because the path to the application, '.$badFolder.'/protectCore.php, holds something a unit cannot carry.';
  check('nothing is built where the application is in '.json_encode($badFolder), \hooked\generateApplicationTimer('scan', $accepted) === array(FALSE, '', '', $said) && $TestLog === array('WARNING!!! '.$said), json_encode($TestLog)); }
$EntryFolder = '/DATA/HRProtect/Application';
foreach (array('Description', 'Argument', 'Calendar', 'Document', 'Reach', 'Success') as $field) {
  quietly();
  check('nothing is built from a record without its '.$field, \hooked\generateApplicationTimer('scan', without($field)) === array(FALSE, '', '', 'The timer scan was not built, because its record is not whole.') && $TestLog === array('WARNING!!! The timer scan was not built, because its record is not whole.')); }
quietly();
check('or from what is no record, or one whose statuses are no list', \hooked\generateApplicationTimer('scan', 'daily')[0] === FALSE && \hooked\generateApplicationTimer('scan', record(array('Success' => 1)))[0] === FALSE && \hooked\generateApplicationTimer('scan', NULL)[3] === 'The timer scan was not built, because its record is not whole.');
quietly();
check('or for a name a timer may not have, whoever asks', \hooked\generateApplicationTimer('../x', $accepted) === array(FALSE, '', '', 'The timer ../x was not built, because its name is not one a timer of an application may have.') && \hooked\generateApplicationTimer(array('scan'), $accepted)[3] === 'The timer  was not built, because its name is not one a timer of an application may have.' && \hooked\generateApplicationTimer('environment', $accepted)[0] === FALSE && count($TestLog) === 3, json_encode($TestLog));


// 6. Writing the two. They are written where they differ from what is there, whole, by the kernel.
$UnitFolder = makeFolder($base.'/units-written', 0755); hooks(); quietly();
$servicePath = $UnitFolder.'/hrprotect-scan.service'; $timerPath = $UnitFolder.'/hrprotect-scan.timer';
check('units that are not there are installed', \hooked\writeApplicationTimer('scan', $hostService, $hostTimer) === array(TRUE, 'installed', $timerPath) && file_get_contents($servicePath) === $hostService && file_get_contents($timerPath) === $hostTimer, json_encode($TestLog));
clearstatcache();
check('each is root\'s, 0644 & a plain file, & nothing else is left in the folder', fileowner($servicePath) === 0 && fileowner($timerPath) === 0 && (fileperms($servicePath) & 07777) === 0644 && (fileperms($timerPath) & 07777) === 0644 && array_values(array_diff(scandir($UnitFolder), array('.', '..'))) === array('hrprotect-scan.service', 'hrprotect-scan.timer'), json_encode(scandir($UnitFolder)));
check('& that it was written is an ordinary entry in the log', $TestLog === array('Op-Act: The timer scan was written to '.$timerPath.'.'), json_encode($TestLog));
$before = array(fileinode($servicePath), fileinode($timerPath)); quietly();
check('units that are already as they should be are left alone, & nothing is said', \hooked\writeApplicationTimer('scan', $hostService, $hostTimer) === array(TRUE, 'unchanged', $timerPath) && array(fileinode($servicePath), fileinode($timerPath)) === $before && $TestLog === array());
quietly();
check('a timer that differs is written again, & so is its service', \hooked\writeApplicationTimer('scan', $hostService, $timerHead.timerTail('hourly')) === array(TRUE, 'updated', $timerPath) && file_get_contents($timerPath) === $timerHead.timerTail('hourly') && file_get_contents($servicePath) === $hostService && $TestLog === array('Op-Act: The timer scan was written to '.$timerPath.'.'));
check('a service that differs is written again as well', \hooked\writeApplicationTimer('scan', $hostService.'# / More.'.PHP_EOL, $timerHead.timerTail('hourly')) === array(TRUE, 'updated', $timerPath) && file_get_contents($servicePath) === $hostService.'# / More.'.PHP_EOL);
$UnitFolder = makeFolder($base.'/units-half', 0755); hooks(); writeScratchFile($UnitFolder.'/hrprotect-scan.service', $hostService, 0644);
check('a service with no timer beside it is an installation that was not finished, & is installed', \hooked\writeApplicationTimer('scan', $hostService, $hostTimer) === array(TRUE, 'installed', $UnitFolder.'/hrprotect-scan.timer') && file_get_contents($UnitFolder.'/hrprotect-scan.timer') === $hostTimer);
// What is not written.
$UnitFolder = makeFolder($base.'/units-refused', 0755); hooks();
foreach (array(array('', $hostTimer), array($hostService, ''), array(NULL, $hostTimer), array($hostService, array('x'))) as $case) {
  quietly();
  check('nothing is written where '.json_encode(array(is_string($case[0]) ? strlen($case[0]) : $case[0], is_string($case[1]) ? strlen($case[1]) : $case[1])).' is all there is to write', \hooked\writeApplicationTimer('scan', $case[0], $case[1]) === array(FALSE, 'failed', $UnitFolder.'/hrprotect-scan.timer') && $TestLog === array('WARNING!!! The timer scan could not be written to '.$UnitFolder.'/hrprotect-scan.timer. There was nothing to write.') && array_diff(scandir($UnitFolder), array('.', '..')) === array(), json_encode($TestLog)); }
foreach (array('../escape', 'Scan', 'environment', array('scan'), "scan\n") as $badName) {
  quietly();
  $shown = is_scalar($badName) ? printableText((string)$badName, TRUE) : '';
  check('nothing is written for the name '.json_encode($badName).', & only the folder is named', \hooked\writeApplicationTimer($badName, $hostService, $hostTimer) === array(FALSE, 'failed', $UnitFolder) && $TestLog === array('WARNING!!! The timer '.$shown.' could not be written to '.$UnitFolder.'. Its name is not one a timer of an application may have.') && array_diff(scandir($UnitFolder), array('.', '..')) === array() && !file_exists($base.'/hrprotect-escape.service'), json_encode($TestLog)); }
// A folder a link stands in for is one root does not write into by name.
makeFolder($base.'/units-real', 0755); if (!symlink(mustBeScratch($base.'/units-real'), mustBeScratch($base.'/units-link'))) testStop('A link could not be made at '.$base.'/units-link.'); $UnitFolder = $base.'/units-link'; hooks(); quietly();
list ($current, $status, $path) = \hooked\writeApplicationTimer('scan', $hostService, $hostTimer);
check('a folder that is a link is not written through, & the reason is a warning', $current === FALSE && $status === 'failed' && $path === $UnitFolder.'/hrprotect-scan.timer' && count($TestLog) === 1 && strpos($TestLog[0], 'WARNING!!! The timer scan could not be written to '.$UnitFolder.'/hrprotect-scan.timer. '.$UnitFolder.'/hrprotect-scan.service was not written. ') === 0 && array_diff(scandir($base.'/units-real'), array('.', '..')) === array(), json_encode($TestLog));
// The timer is not written after a service that could not be.
$UnitFolder = makeFolder($base.'/units-stopped', 0755); quietly();
hooks(array('writeFileWithoutFollowing' => function ($arguments, $count) { return array('answer', array(FALSE, 'The disk is full.')); }));
check('where the service cannot be written the timer is not tried', \hooked\writeApplicationTimer('scan', $hostService, $hostTimer) === array(FALSE, 'failed', $UnitFolder.'/hrprotect-scan.timer') && $TestHookCalls['writeFileWithoutFollowing'] === 1 && $TestLog === array('WARNING!!! The timer scan could not be written to '.$UnitFolder.'/hrprotect-scan.timer. The disk is full.'), json_encode($TestLog));
quietly();
hooks(array('writeFileWithoutFollowing' => function ($arguments, $count) { return $count === 2 ? array('answer', array(FALSE, 'The disk filled.')) : NULL; }));
check('where the timer cannot be written after it, that is a failure & says why', \hooked\writeApplicationTimer('scan', $hostService, $hostTimer) === array(FALSE, 'failed', $UnitFolder.'/hrprotect-scan.timer') && $TestHookCalls['writeFileWithoutFollowing'] === 2 && file_get_contents($UnitFolder.'/hrprotect-scan.service') === $hostService && !file_exists($UnitFolder.'/hrprotect-scan.timer') && $TestLog === array('WARNING!!! The timer scan could not be written to '.$UnitFolder.'/hrprotect-scan.timer. The disk filled.'), json_encode($TestLog));
hooks(array('writeFileWithoutFollowing' => function ($arguments, $count) { global $Handed; $Handed[] = array($arguments[0], $arguments[2]); return NULL; }));
$Handed = array(); $UnitFolder = makeFolder($base.'/units-modes', 0755);
check('the kernel is handed the service first, then the timer, each to be made 0644', \hooked\writeApplicationTimer('scan', $hostService, $hostTimer)[1] === 'installed' && $Handed === array(array($UnitFolder.'/hrprotect-scan.service', 0644), array($UnitFolder.'/hrprotect-scan.timer', 0644)), json_encode($Handed));
hooks();


// 7. Installing them, & writing them again. Nothing is ever enabled.
$Located = array('php' => '/usr/bin/php'); $EngineTimerProvider = 'oneTimer'; $ExecAnswers = array('daemon-reload' => array(array(), 0));
$UnitFolder = makeFolder($base.'/units-installed', 0755); hooks(); quietly();
$timerPath = $UnitFolder.'/hrprotect-scan.timer';
list ($returned, $printed) = printing('hooked\installApplicationTimers', TRUE);
$installedText = PHP_EOL.'HRProtect schedules'.PHP_EOL
  .'  Installed   '.$timerPath.PHP_EOL
  .'              Scheduled scan. Calendar: daily'.PHP_EOL
  .'              The timer is NOT enabled. Run the command by hand before scheduling root.'.PHP_EOL
  .'                sudo php protectCore.php --app-scan-scheduled'.PHP_EOL
  .'                sudo systemctl enable --now hrprotect-scan.timer'.PHP_EOL;
check('a timer that is not installed is installed, & what enables it is printed', $returned === array(TRUE, array('scan' => 'installed')) && $printed === $installedText, $printed);
check('both units are written as section 5 has them', file_get_contents($UnitFolder.'/hrprotect-scan.service') === $hostService && file_get_contents($timerPath) === $hostTimer);
check('systemd is told to read its units again, once, & is told nothing else', $ExecCalls === array('systemctl daemon-reload 2>&1'), json_encode($ExecCalls));
check('nothing is enabled, started or restarted', count(preg_grep('/enable|start|stop|restart/', $ExecCalls)) === 0 && $TestLog === array('Op-Act: The timer scan was written to '.$timerPath.'.'), json_encode($TestLog));
quietly();
list ($returned, $printed) = printing('hooked\installApplicationTimers', TRUE);
check('asked again, it is left alone, that is said, & systemd is told nothing', $returned === array(TRUE, array('scan' => 'unchanged')) && $printed === PHP_EOL.'HRProtect schedules'.PHP_EOL.line('Unchanged', $timerPath).line('', 'Scheduled scan. Calendar: daily') && $ExecCalls === array() && $TestLog === array(), $printed);
// -fp writes again what is installed, & installs nothing.
quietly();
list ($returned, $printed) = printing('hooked\installApplicationTimers', FALSE);
check('a repair says the same of a timer that is installed & as it should be', $returned === array(TRUE, array('scan' => 'unchanged')) && $printed === PHP_EOL.'HRProtect schedules'.PHP_EOL.line('Unchanged', $timerPath).line('', 'Scheduled scan. Calendar: daily') && $ExecCalls === array());
function hourlyTimer() { return array('scan' => record(array('Calendar' => 'hourly'))); }
$EngineTimerProvider = 'hourlyTimer'; quietly();
list ($returned, $printed) = printing('hooked\installApplicationTimers', FALSE);
$updatedText = PHP_EOL.'HRProtect schedules'.PHP_EOL
  .'  Updated     '.$timerPath.PHP_EOL
  .'              Scheduled scan. Calendar: hourly'.PHP_EOL
  .'              Whether it is enabled is unchanged. To be sure a running timer holds a new calendar:'.PHP_EOL
  .'                sudo systemctl restart hrprotect-scan.timer'.PHP_EOL
  .'              systemd may start the command at once, where the new calendar names a time since its last run.'.PHP_EOL;
check('a repair writes a timer again where its calendar changed, & prints the restart that makes sure of it', $returned === array(TRUE, array('scan' => 'updated')) && $printed === $updatedText && file_get_contents($timerPath) === $timerHead.$serviceDocument.timerTail('hourly'), $printed);
check('& tells systemd to read its units again, & restarts nothing itself', $ExecCalls === array('systemctl daemon-reload 2>&1'), json_encode($ExecCalls));
$UnitFolder = makeFolder($base.'/units-none', 0755); hooks(); $EngineTimerProvider = 'oneTimer'; quietly();
list ($returned, $printed) = printing('hooked\installApplicationTimers', FALSE);
check('a repair installs nothing that is not installed, & says nothing', $returned === array(TRUE, array('scan' => 'not installed')) && $printed === '' && $ExecCalls === array() && $TestLog === array() && array_diff(scandir($UnitFolder), array('.', '..')) === array(), $printed);
foreach (array(1, 'TRUE', 'yes', NULL) as $notTrue) {
  quietly();
  list ($returned, $printed) = printing('hooked\installApplicationTimers', $notTrue);
  check('nothing is installed for '.json_encode($notTrue).', which is not TRUE', $returned === array(TRUE, array('scan' => 'not installed')) && $printed === '' && array_diff(scandir($UnitFolder), array('.', '..')) === array()); }
// An application with nothing to schedule.
foreach (array('', 'noTimers') as $provider) {
  $EngineTimerProvider = $provider;
  foreach (array(TRUE, FALSE) as $installMissing) {
    quietly();
    list ($returned, $printed) = printing('hooked\installApplicationTimers', $installMissing);
    check('with '.($provider === '' ? 'no provider' : 'a provider that returns no timer').' & '.json_encode($installMissing).' nothing is written, nothing is printed & nothing fails', $returned === array(TRUE, array()) && $printed === '' && $ExecCalls === array() && $TestLog === array(), $printed); } }
// What was refused is printed whichever command asked.
$EngineTimerProvider = 'mixedTimers'; $UnitFolder = makeFolder($base.'/units-mixed', 0755); hooks(); quietly();
list ($returned, $printed) = printing('hooked\installApplicationTimers', FALSE);
check('a repair prints what was refused, & the calendar that fell back, though it installs nothing', $returned === array(TRUE, array('scan' => 'not installed', 'probe' => 'not installed')) && $printed === PHP_EOL.'HRProtect schedules'.PHP_EOL.line('Note', $expectedNotices[0]).line('Note', $expectedNotices[1]).line('Note', $expectedNotices[2]), $printed);
quietly();
list ($returned, $printed) = printing('hooked\installApplicationTimers', TRUE);
check('an installation installs the two that are fit, one after the other, & reloads once', $returned === array(TRUE, array('scan' => 'installed', 'probe' => 'installed')) && is_file($UnitFolder.'/hrprotect-probe.timer') && is_file($UnitFolder.'/hrprotect-probe.service') && !file_exists($UnitFolder.'/hrprotect-worse.timer') && !file_exists($UnitFolder.'/hrprotect-Bad.timer') && $ExecCalls === array('systemctl daemon-reload 2>&1') && substr_count($printed, 'HRProtect schedules') === 1 && strpos($printed, line('', 'Scheduled scan. Calendar: daily').line('', 'The timer is NOT enabled. Run the command by hand before scheduling root.').line('', '  sudo php protectCore.php --app-coverage').line('', '  sudo systemctl enable --now hrprotect-probe.timer')) !== FALSE, $printed);
check('the confined one is written confined, with the calendar it fell back to', strpos(file_get_contents($UnitFolder.'/hrprotect-probe.service'), 'ProtectHome=yes') !== FALSE && strpos(file_get_contents($UnitFolder.'/hrprotect-probe.timer'), PHP_EOL.'OnCalendar=daily'.PHP_EOL) !== FALSE);
// Where systemd is not running, or keeps no units where they are expected.
$EngineTimerProvider = 'oneTimer'; $Systemd = array(FALSE, 'systemd is not running.'); $UnitFolder = makeFolder($base.'/units-no-systemd', 0755); hooks(); quietly();
list ($returned, $printed) = printing('hooked\installApplicationTimers', TRUE);
check('a host without systemd is written no unit, is told what then runs nothing, & that is no failure', $returned === array(TRUE, array('scan' => 'skipped')) && $printed === PHP_EOL.'HRProtect schedules'.PHP_EOL.line('Skipped', 'systemd is not running.').line('', 'Nothing here runs  protectCore.php --app-scan-scheduled  on a schedule.') && $ExecCalls === array() && $TestLog === array() && array_diff(scandir($UnitFolder), array('.', '..')) === array(), $printed);
quietly();
list ($returned, $printed) = printing('hooked\installApplicationTimers', FALSE);
check('a repair there says nothing at all', $returned === array(TRUE, array('scan' => 'skipped')) && $printed === '' && $ExecCalls === array());
$Systemd = array(TRUE, 'systemd is running & systemctl is available.'); $UnitFolder = $base.'/units-that-are-not-there'; hooks(); quietly();
list ($returned, $printed) = printing('hooked\installApplicationTimers', TRUE);
check('neither is a host whose unit folder is not there', $returned === array(TRUE, array('scan' => 'skipped')) && $printed === PHP_EOL.'HRProtect schedules'.PHP_EOL.line('Skipped', $UnitFolder.' does not exist.').line('', 'Nothing here runs  protectCore.php --app-scan-scheduled  on a schedule.') && !file_exists($UnitFolder), $printed);
// Only root writes a unit.
$UnitFolder = makeFolder($base.'/units-not-root', 0755); hooks(); $RunningAsRoot = FALSE; quietly();
list ($returned, $printed) = printing('hooked\installApplicationTimers', TRUE);
check('an account that is not root is refused, with a warning, & nothing is written', $returned === array(FALSE, array()) && $printed === PHP_EOL.'HRProtect schedules'.PHP_EOL.line('FAILED', 'A timer can only be written by root.') && $TestLog === array('WARNING!!! The timers of HRProtect can only be written while running as root.') && array_diff(scandir($UnitFolder), array('.', '..')) === array() && $ExecCalls === array(), $printed);
quietly();
list ($returned, $printed) = printing('hooked\installApplicationTimers', FALSE);
check('a repair that is not root\'s writes nothing & says nothing', $returned === array(TRUE, array()) && $printed === '' && $TestLog === array());
$EngineTimerProvider = 'noTimers'; quietly();
list ($returned, $printed) = printing('hooked\installApplicationTimers', TRUE);
check('an account that is not root is refused nothing where there is nothing to write', $returned === array(TRUE, array()) && $TestLog === array());
$RunningAsRoot = TRUE; $EngineTimerProvider = 'oneTimer';
// What fails.
$EntryFolder = '/DATA/HR Protect/Application'; $UnitFolder = makeFolder($base.'/units-unbuilt', 0755); hooks(); quietly();
list ($returned, $printed) = printing('hooked\installApplicationTimers', TRUE);
$unbuilt = 'The timer scan was not built, because the path to the application, /DATA/HR Protect/Application/protectCore.php, holds something a unit cannot carry.';
check('a timer that cannot be built fails the installation, is printed & is a warning once', $returned === array(FALSE, array('scan' => 'refused')) && $printed === PHP_EOL.'HRProtect schedules'.PHP_EOL.line('FAILED', $unbuilt) && $TestLog === array('WARNING!!! '.$unbuilt) && $ExecCalls === array() && array_diff(scandir($UnitFolder), array('.', '..')) === array(), $printed);
$EntryFolder = '/DATA/HRProtect/Application'; quietly();
hooks(array('writeFileWithoutFollowing' => function ($arguments, $count) { return array('answer', array(FALSE, 'The disk is full.')); }));
list ($returned, $printed) = printing('hooked\installApplicationTimers', TRUE);
check('one that cannot be written fails it as well, & systemd is told nothing', $returned === array(FALSE, array('scan' => 'failed')) && $printed === PHP_EOL.'HRProtect schedules'.PHP_EOL.line('FAILED', $UnitFolder.'/hrprotect-scan.timer could not be written. The log says why.') && $ExecCalls === array() && $TestLog === array('WARNING!!! The timer scan could not be written to '.$UnitFolder.'/hrprotect-scan.timer. The disk is full.'), $printed);
function twoTimers() { return array('first' => record(), 'second' => record(array('Argument' => '--app-coverage'))); }
$EngineTimerProvider = 'twoTimers'; $UnitFolder = makeFolder($base.'/units-one-fails', 0755); quietly();
hooks(array('writeFileWithoutFollowing' => function ($arguments, $count) { return $count === 1 ? array('answer', array(FALSE, 'The disk is full.')) : NULL; }));
list ($returned, $printed) = printing('hooked\installApplicationTimers', TRUE);
check('a timer that fails does not stop the next, which is written & reloaded for', $returned === array(FALSE, array('first' => 'failed', 'second' => 'installed')) && is_file($UnitFolder.'/hrprotect-second.timer') && !file_exists($UnitFolder.'/hrprotect-first.timer') && $ExecCalls === array('systemctl daemon-reload 2>&1'), $printed);
hooks(); $EngineTimerProvider = 'oneTimer'; $UnitFolder = makeFolder($base.'/units-no-reload', 0755); hooks(); quietly();
$ExecAnswers = array('daemon-reload' => array(array('Failed to connect to bus:', "No such file\x1b[2J"), 1));
list ($returned, $printed) = printing('hooked\installApplicationTimers', TRUE);
check('units systemd could not be told of are still installed, & a warning says what it answered', $returned === array(TRUE, array('scan' => 'installed')) && $TestLog === array('Op-Act: The timer scan was written to '.$UnitFolder.'/hrprotect-scan.timer.', 'WARNING!!! The timers of HRProtect were written & systemd could not be told to read them again. Failed to connect to bus: No such file?[2J'), json_encode($TestLog));
$ExecAnswers = array();


// 8. Asking systemd where a timer stands. Three commands, each of which only reads.
$Located = array('systemctl' => '/usr/bin/systemctl'); $UnitFolder = makeFolder($base.'/units-asked', 0755); hooks();
$none = array('Installed' => FALSE, 'Enabled' => FALSE, 'Active' => FALSE, 'Next' => '');
function answers($enabled, $active, $next) { global $ExecAnswers; $ExecAnswers = array(' is-enabled ' => $enabled, ' is-active ' => $active, ' show ' => $next); }
answers(array(array('enabled'), 0), array(array('active'), 0), array(array('NextElapseUSecRealtime=Fri 2026-11-20 00:03:12 EST'), 0)); quietly();
check('a timer that is not a file is not installed, whatever systemd says of it', \hooked\applicationTimerState('scan') === array(TRUE, array('Installed' => FALSE, 'Enabled' => TRUE, 'Active' => TRUE, 'Next' => 'Fri 2026-11-20 00:03:12 EST')), json_encode(\hooked\applicationTimerState('scan')));
quietly(); $state = \hooked\applicationTimerState('scan');
check('it is asked three things, of the timer & nothing else, & each is escaped', $ExecCalls === array("'/usr/bin/systemctl' is-enabled 'hrprotect-scan.timer' 2>/dev/null", "'/usr/bin/systemctl' is-active 'hrprotect-scan.timer' 2>/dev/null", "'/usr/bin/systemctl' show --property=NextElapseUSecRealtime 'hrprotect-scan.timer' 2>/dev/null"), json_encode($ExecCalls));
writeScratchFile($UnitFolder.'/hrprotect-scan.timer', $hostTimer, 0644);
check('a timer that is a file is installed', \hooked\applicationTimerState('scan')[1]['Installed'] === TRUE);
makeFolder($UnitFolder.'/hrprotect-folder.timer', 0755);
check('a folder of that name is not', \hooked\applicationTimerState('folder')[1]['Installed'] === FALSE);
foreach (array(array(array('enabled-runtime'), 0, TRUE), array(array(' enabled '), 0, TRUE), array(array('static'), 0, FALSE), array(array('alias'), 0, FALSE), array(array('disabled'), 1, FALSE), array(array('enabled'), 1, FALSE), array(array(), 0, FALSE), array(array('masked'), 1, FALSE), array(array('not-found'), 4, FALSE)) as $case) {
  answers(array($case[0], $case[1]), array(array('active'), 0), array(array(), 0));
  check('it is '.($case[2] ? '' : 'not ').'enabled where systemctl says '.json_encode($case[0]).' with status '.$case[1], \hooked\applicationTimerState('scan')[1]['Enabled'] === $case[2]); }
foreach (array(array(array('active'), 0, TRUE), array(array('inactive'), 3, FALSE), array(array('failed'), 3, FALSE), array(array('active'), 3, FALSE), array(array('activating'), 0, FALSE), array(array(), 0, FALSE)) as $case) {
  answers(array(array('enabled'), 0), array($case[0], $case[1]), array(array(), 0));
  check('it is '.($case[2] ? '' : 'not ').'running where systemctl says '.json_encode($case[0]).' with status '.$case[1], \hooked\applicationTimerState('scan')[1]['Active'] === $case[2]); }
foreach (array(array(array('NextElapseUSecRealtime=n/a'), 0, ''), array(array('NextElapseUSecRealtime=infinity'), 0, ''), array(array('NextElapseUSecRealtime=0'), 0, ''), array(array('NextElapseUSecRealtime='), 0, ''), array(array(), 0, ''), array(array('Fri 2026-11-20 00:03:12 EST'), 0, ''), array(array('NextElapseUSecRealtime=Fri 2026-11-20 00:03:12 EST'), 1, ''), array(array('NextElapseUSecRealtime='.str_repeat('9', 65)), 0, ''), array(array('NextElapseUSecRealtime='.str_repeat('9', 64)), 0, str_repeat('9', 64)), array(array('Other=1', 'NextElapseUSecRealtime=  Sat 2026-11-21 00:00:00 EST  ', 'NextElapseUSecRealtime=later'), 0, 'Sat 2026-11-21 00:00:00 EST'), array(array("NextElapseUSecRealtime=Sat\x1b[2J"), 0, 'Sat?[2J')) as $case) {
  answers(array(array('enabled'), 0), array(array('active'), 0), array($case[0], $case[1]));
  check('its next run is '.json_encode($case[2]).' where systemctl says '.json_encode($case[0]).' with status '.$case[1], \hooked\applicationTimerState('scan')[1]['Next'] === $case[2], json_encode(\hooked\applicationTimerState('scan')[1]['Next'])); }
// What is asked about nowhere.
answers(array(array('enabled'), 0), array(array('active'), 0), array(array('NextElapseUSecRealtime=soon'), 0));
foreach (array('Scan', '../scan', 'environment', 'listener', '', array('scan'), NULL, "scan\n") as $badName) {
  quietly();
  check('nothing is asked about the name '.json_encode($badName), \hooked\applicationTimerState($badName) === array(FALSE, $none) && $ExecCalls === array() && $LocateCalls === array()); }
$Systemd = array(FALSE, 'systemd is not running.'); quietly();
check('where systemd is not running nothing is asked, & whether the timer is a file is still said', \hooked\applicationTimerState('scan') === array(FALSE, array('Installed' => TRUE, 'Enabled' => FALSE, 'Active' => FALSE, 'Next' => '')) && $ExecCalls === array() && $LocateCalls === array());
$Systemd = array(TRUE, 'systemd is running & systemctl is available.'); $Located = array(); quietly();
check('nor is anything asked where systemctl cannot be found', \hooked\applicationTimerState('scan') === array(FALSE, array('Installed' => TRUE, 'Enabled' => FALSE, 'Active' => FALSE, 'Next' => '')) && $ExecCalls === array() && $LocateCalls === array('systemctl'));
$ExecAnswers = array();


// 9. The checks that changing each line of these functions, by rule, showed to be missing. HRProtect's tests/mutate.py made the
// changes, & each survived until its check was here. Its checks of its own scan on a schedule stay HRProtect's.
// Text that is longer than 1000 bytes is cut where it is said, so that one mistake in config.php or in a provider does not fill a log or a terminal.
$long = str_repeat('a', 1500); $cut = str_repeat('a', 1000).'...';
$Located = array(); $ExecAnswers = array(); $Systemd = array(TRUE, 'systemd is running & systemctl is available.'); $RunningAsRoot = TRUE; $EntryFolder = '/DATA/HRProtect/Application'; hooks(); quietly();
$said = 'The timer '.$cut.' was refused, because its name is not one a timer of an application may have.';
check('a name of 1500 characters is refused, & is said with its first 1000', \hooked\examineApplicationTimer($long, record()) === array(FALSE, array(), $said) && $TestLog === array('WARNING!!! '.$said), json_encode($TestLog));
quietly();
$said = 'The calendar of the timer scan, '.$cut.', is not one systemd reads, so the timer runs daily.';
check('a calendar of 1500 characters falls back, & is said with its first 1000', \hooked\examineApplicationTimer('scan', record(array('Calendar' => $long))) === array(TRUE, record(array('Calendar' => 'daily')), $said) && $TestLog === array('WARNING!!! '.$said), json_encode($TestLog));
$EngineTimerProvider = str_repeat('p', 1500); quietly();
$said = 'A timer provider named '.str_repeat('p', 1000).'... was declared & does not exist. The application puts nothing on a schedule.';
check('the name of a provider that does not exist is said with its first 1000 characters', \hooked\readApplicationTimers() === array(TRUE, array(), array($said)) && $TestLog === array('WARNING!!! '.$said), json_encode($TestLog));
$longProvider = str_repeat('q', 1100); eval('function '.$longProvider.'() { return 7; }');
$EngineTimerProvider = $longProvider; quietly();
$said = 'The timer provider named '.str_repeat('q', 1000).'... answered with no list. The application puts nothing on a schedule.';
check('so is the name of one that answers with no list', function_exists($longProvider) && \hooked\readApplicationTimers() === array(TRUE, array(), array($said)) && $TestLog === array('WARNING!!! '.$said), json_encode($TestLog));
$Located = array('php' => '/'.$long.' x'); quietly();
$said = 'The timer scan was not built, because the path to PHP, /'.str_repeat('a', 999).'..., holds something a unit cannot carry.';
check('a path to PHP of 1500 characters that a unit cannot carry is said with its first 1000', \hooked\generateApplicationTimer('scan', $accepted) === array(FALSE, '', '', $said) && $TestLog === array('WARNING!!! '.$said), json_encode($TestLog));
$Located = array('php' => '/usr/bin/php'); $EntryFolder = '/'.$long.' x'; quietly();
$said = 'The timer scan was not built, because the path to the application, /'.str_repeat('a', 999).'..., holds something a unit cannot carry.';
check('so is a path to the application', \hooked\generateApplicationTimer('scan', $accepted) === array(FALSE, '', '', $said) && $TestLog === array('WARNING!!! '.$said), json_encode($TestLog));
$EntryFolder = '/DATA/HRProtect/Application'; quietly();
$said = 'The timer '.$cut.' was not built, because its name is not one a timer of an application may have.';
check('so is the name of a timer that is not built', \hooked\generateApplicationTimer($long, $accepted) === array(FALSE, '', '', $said) && $TestLog === array('WARNING!!! '.$said), json_encode($TestLog));
$UnitFolder = makeFolder($base.'/units-long-name', 0755); hooks(); quietly();
check('& the name of one that is not written', \hooked\writeApplicationTimer($long, $hostService, $hostTimer) === array(FALSE, 'failed', $UnitFolder) && $TestLog === array('WARNING!!! The timer '.$cut.' could not be written to '.$UnitFolder.'. Its name is not one a timer of an application may have.') && array_diff(scandir($UnitFolder), array('.', '..')) === array(), json_encode($TestLog));
$EngineTimerProvider = 'oneTimer'; $UnitFolder = makeFolder($base.'/units-long-reload', 0755); hooks(); quietly();
$ExecAnswers = array('daemon-reload' => array(array(str_repeat('z', 1500)), 1));
list ($returned, $printed) = printing('hooked\installApplicationTimers', TRUE);
check('what systemd answered is logged with its first 1000 bytes', $returned === array(TRUE, array('scan' => 'installed')) && $TestLog === array('Op-Act: The timer scan was written to '.$UnitFolder.'/hrprotect-scan.timer.', 'WARNING!!! The timers of HRProtect were written & systemd could not be told to read them again. '.str_repeat('z', 1000).'...'), json_encode($TestLog));
$ExecAnswers = array('daemon-reload' => array(array(), 0));

// What systemd says of a next run is held to the words it uses. Only its three words for no next run are read as none.
$Located = array('systemctl' => '/usr/bin/systemctl'); $UnitFolder = $base.'/units-asked'; hooks();
answers(array(array('enabled'), 0), array(array('active'), 0), array(array('NextElapseUSecRealtime=00'), 0));
$doubleZero = \hooked\applicationTimerState('scan')[1]['Next'];
answers(array(array('enabled'), 0), array(array('active'), 0), array(array('NextElapseUSecRealtime=0.0'), 0));
check('00 & 0.0 are not the 0 systemd writes for no next run, & are kept as they came', $doubleZero === '00' && \hooked\applicationTimerState('scan')[1]['Next'] === '0.0', json_encode($doubleZero));
if (!symlink('/dev/null', mustBeScratch($UnitFolder.'/hrprotect-masked.timer'))) testStop('A link could not be made.');
check('a timer that was masked is a link to nothing that is a file, & is not installed', \hooked\applicationTimerState('masked')[1]['Installed'] === FALSE && is_link($UnitFolder.'/hrprotect-masked.timer'));
$ExecAnswers = array(); $Located = array('php' => '/usr/bin/php');

// PHP opens a file by the path it last resolved its name to, for as long as it remembers one. A unit is read as it is now.
$UnitFolder = makeFolder($base.'/units-remembered', 0755); hooks();
$servicePath = $UnitFolder.'/hrprotect-scan.service'; $timerPath = $UnitFolder.'/hrprotect-scan.timer';
writeScratchFile($servicePath, $hostService.'# / Older.'.PHP_EOL, 0644); writeScratchFile($timerPath, $hostTimer, 0644);
$elsewhereUnit = writeScratchFile($base.'/elsewhere.service', $hostService, 0644);
$phpRemembers = phpRemembersPaths();
$ledElsewhere = rememberElsewhere($servicePath, $elsewhereUnit);
quietly();
$written = \hooked\writeApplicationTimer('scan', $hostService, $hostTimer);
clearstatcache(TRUE);
check('a service PHP remembers as leading to another file is read as the file it is, & so is written again', $ledElsewhere === $phpRemembers && $written === array(TRUE, 'updated', $timerPath) && file_get_contents($servicePath) === $hostService && !is_link($servicePath), json_encode(array($ledElsewhere, $written)));

// A unit that is a link is an administrator's, as a unit that was masked is. The kernel would put a new unit in its place, so nothing is written.
$maskNotice = 'One of its two units is a link, as a unit an administrator masked is, so neither was written.';
$UnitFolder = makeFolder($base.'/units-masked-timer', 0755); hooks(); quietly();
$servicePath = $UnitFolder.'/hrprotect-scan.service'; $timerPath = $UnitFolder.'/hrprotect-scan.timer';
if (!symlink('/dev/null', mustBeScratch($timerPath))) testStop('A link could not be made.');
$written = \hooked\writeApplicationTimer('scan', $hostService, $hostTimer);
clearstatcache(TRUE);
check('a timer that was masked is not written over, & its service is not written beside it', $written === array(FALSE, 'failed', $timerPath) && is_link($timerPath) && readlink($timerPath) === '/dev/null' && !file_exists($servicePath) && !is_link($servicePath) && $TestLog === array('WARNING!!! The timer scan could not be written to '.$timerPath.'. '.$maskNotice), json_encode($TestLog));
$EngineTimerProvider = 'oneTimer'; quietly();
list ($returned, $printed) = printing('hooked\installApplicationTimers', TRUE);
clearstatcache(TRUE);
check('an installation says that it failed, fails, & tells systemd nothing', $returned === array(FALSE, array('scan' => 'failed')) && $printed === PHP_EOL.'HRProtect schedules'.PHP_EOL.line('FAILED', $timerPath.' could not be written. The log says why.') && $ExecCalls === array() && is_link($timerPath) && readlink($timerPath) === '/dev/null', $printed);
quietly();
list ($returned, $printed) = printing('hooked\installApplicationTimers', FALSE);
clearstatcache(TRUE);
check('a repair takes a timer that was masked for one that is not installed, & leaves it', $returned === array(TRUE, array('scan' => 'not installed')) && $printed === '' && $TestLog === array() && readlink($timerPath) === '/dev/null' && !file_exists($servicePath));
$UnitFolder = makeFolder($base.'/units-masked-service', 0755); hooks(); quietly();
$servicePath = $UnitFolder.'/hrprotect-scan.service'; $timerPath = $UnitFolder.'/hrprotect-scan.timer';
writeScratchFile($timerPath, $hostTimer, 0644); if (!symlink('/dev/null', mustBeScratch($servicePath))) testStop('A link could not be made.');
$inodeBefore = fileinode($timerPath);
$written = \hooked\writeApplicationTimer('scan', $hostService, $hostTimer);
clearstatcache(TRUE);
check('nor is a service that was masked, though its timer is as it should be', $written === array(FALSE, 'failed', $timerPath) && is_link($servicePath) && readlink($servicePath) === '/dev/null' && file_get_contents($timerPath) === $hostTimer && fileinode($timerPath) === $inodeBefore && $TestLog === array('WARNING!!! The timer scan could not be written to '.$timerPath.'. '.$maskNotice), json_encode($TestLog));
quietly();
list ($returned, $printed) = printing('hooked\installApplicationTimers', FALSE);
check('a repair says so each time, beneath the heading, & fails nothing else', $returned === array(FALSE, array('scan' => 'failed')) && $printed === PHP_EOL.'HRProtect schedules'.PHP_EOL.line('FAILED', $timerPath.' could not be written. The log says why.') && $ExecCalls === array(), $printed);
// Links that lead to units which are already as they should be need nothing written, & are left as they are.
$keptUnits = makeFolder($base.'/units-kept-elsewhere', 0755); writeScratchFile($keptUnits.'/service', $hostService, 0644); writeScratchFile($keptUnits.'/timer', $hostTimer, 0644);
$UnitFolder = makeFolder($base.'/units-linked', 0755); hooks(); quietly();
$servicePath = $UnitFolder.'/hrprotect-scan.service'; $timerPath = $UnitFolder.'/hrprotect-scan.timer';
if (!symlink($keptUnits.'/service', mustBeScratch($servicePath)) or !symlink($keptUnits.'/timer', mustBeScratch($timerPath))) testStop('A link could not be made.');
$written = \hooked\writeApplicationTimer('scan', $hostService, $hostTimer);
clearstatcache(TRUE);
check('links that lead to units which are as they should be are unchanged, & nothing is said', $written === array(TRUE, 'unchanged', $timerPath) && is_link($servicePath) && is_link($timerPath) && $TestLog === array());
quietly();
$written = \hooked\writeApplicationTimer('scan', $hostService, $timerHead.$serviceDocument.timerTail('hourly'));
clearstatcache(TRUE);
check('links that lead to units which differ are not written over, & what they lead to is not written either', $written === array(FALSE, 'failed', $timerPath) && is_link($servicePath) && is_link($timerPath) && file_get_contents($keptUnits.'/timer') === $hostTimer && file_get_contents($keptUnits.'/service') === $hostService && $TestLog === array('WARNING!!! The timer scan could not be written to '.$timerPath.'. '.$maskNotice), json_encode($TestLog));

// What a repair prints. The heading comes once, above whatever is said first.
function badCalendarTimer() { return array('scan' => record(array('Calendar' => 'every;day'))); }
$EngineTimerProvider = 'badCalendarTimer'; $UnitFolder = makeFolder($base.'/units-one-notice', 0755); hooks(); quietly();
list ($returned, $printed) = printing('hooked\installApplicationTimers', FALSE);
check('a repair with one thing to say says it beneath the heading', $returned === array(TRUE, array('scan' => 'not installed')) && $printed === PHP_EOL.'HRProtect schedules'.PHP_EOL.line('Note', 'The calendar of the timer scan, every;day, is not one systemd reads, so the timer runs daily.'), $printed);
$EngineTimerProvider = 'twoTimers'; $UnitFolder = makeFolder($base.'/units-two-installed', 0755); hooks(); quietly();
$ExecAnswers = array('daemon-reload' => array(array(), 0));
list ($returned, $printed) = printing('hooked\installApplicationTimers', TRUE);
quietly();
list ($returned, $printed) = printing('hooked\installApplicationTimers', FALSE);
check('a repair of two timers that are installed prints the heading once, above the first', $returned === array(TRUE, array('first' => 'unchanged', 'second' => 'unchanged')) && $printed === PHP_EOL.'HRProtect schedules'.PHP_EOL.line('Unchanged', $UnitFolder.'/hrprotect-first.timer').line('', 'Scheduled scan. Calendar: daily').line('Unchanged', $UnitFolder.'/hrprotect-second.timer').line('', 'Scheduled scan. Calendar: daily') && $ExecCalls === array(), $printed);
$EngineTimerProvider = 'oneTimer'; $ExecAnswers = array(); hooks();

finish('test_schedule');
