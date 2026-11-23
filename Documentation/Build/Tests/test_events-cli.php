<?php
// HRProprietary Engine v4.3.1. The unit suite of the Event Core, which is the Engine's from v4.3.1.
// HRProtect's v0.0.6 suite, with the core's new path, its version & the guard every Engine core has. Every other check is HRProtect's.
//
//   sudo bash Documentation/Build/Tests/contained.sh php Documentation/Build/Tests/test_events-cli.php
// Run it from the root of the application under test. A tree named after it is tested instead, & must be that root.
//
// It loads the tree's own Resources/Engine/Cores/eventCore.php, & lifts the kernel functions that core calls
// out of the tree's own Resources/Kernel/kernel.php. Nothing of either is copied into this file.
//
// It removes nothing. Each part makes a folder of its own beneath the scratch folder & leaves it there.
// It never names a path by joining text to a variable it was not handed by scratchPath() or by the
// Event Core's own folder, & the library stops it where a variable was never set.
//
// Part 14 mounts a tmpfs of 64 kilobytes beneath the scratch folder & unmounts it.
// What printableText() does to text is written out here byte for byte, so a change to the kernel's function shows as a failed check.
// Parts 11 & 15 start short processes of this same file. None of them runs a shell.
// One check of part 11 is from HRProtect's v0.0.6. For it the library has another process move a name, with mv. Every other check is as
// HRProtect's v0.0.3 had it.
// Part 20 lowers the number of files this process may hold open, takes every one that is left by opening /dev/null, & puts the number back.

if (PHP_SAPI !== 'cli') { if (!headers_sent()) http_response_code(404); die(); }

if (!is_file('Documentation/Build/Tests/testlib-cli.php')) { fwrite(STDERR, 'Run this from the root of the application it tests. See Documentation/Build/Tests/README.txt.'.PHP_EOL); exit(64); }
require 'Documentation/Build/Tests/testlib-cli.php';

$childMode = (isset($argv[1]) && ($argv[1] === '--child-write' or $argv[1] === '--child-hold'));
$tree = testTree($childMode ? (isset($argv[2]) ? $argv[2] : '') : (isset($argv[1]) ? $argv[1] : (string)getcwd()));
requireContainment();

// What the kernel & the application would have set before the Event Core was loaded.
$EnableMemoryProtection = TRUE;
$Verbose = FALSE;
$LogFile = '';
$RunningAsRoot = TRUE;
$InstLoc = $tree;
$CoreLoaded = TRUE;
$EngineApplicationVersion = '0.0.3';
$EventLoc = '';

$kernelFile = $tree.'/Resources/Kernel/kernel.php';
$coreFile = $tree.'/Resources/Engine/Cores/eventCore.php';
if (!is_file($coreFile)) testStop($coreFile.' does not exist, so there is no Event Core to test.');
liftFunctions($kernelFile, array('getChildFunction', 'purgeSensitiveMemory', 'pathIsTrustedByRoot', 'findAccountDatabaseEntry', 'identifyRunningAccount', 'folderIsHeldForRoot', 'openFileWithoutFollowing', 'printableText'), array());
$coreCalls = forbiddenCallsIn(token_get_all((string)file_get_contents($coreFile)), array());
if (count($coreCalls) > 0) testStop('The Event Core calls '.implode(', ', $coreCalls).', & this suite does not load a core that removes, renames or runs anything.');
require $coreFile;


// A process of this file that keeps events, for the part that has several writers at once.
if ($childMode && $argv[1] === '--child-write') {
  $EventLoc = mustBeScratch(isset($argv[3]) ? $argv[3] : '');
  $childCount = isset($argv[4]) ? (int)$argv[4] : 0;
  $childTag = isset($argv[5]) ? (string)preg_replace('/[^a-z0-9]/', '', $argv[5]) : '';
  $childKept = 0;
  for ($number = 1; $number <= $childCount; $number++) {
    list ($kept, $identifier, $notice) = recordEvent(array('Source' => 'scan', 'Kind' => 'test.written', 'Category' => 4, 'Severity' => 'info', 'Summary' => 'Writer '.$childTag.' kept event '.$number.'.', 'Subject' => array('Writer' => $childTag, 'Number' => $number)));
    if ($kept === TRUE) $childKept++; }
  fwrite(STDOUT, 'kept '.$childKept.PHP_EOL);
  exit($childKept === $childCount ? 0 : 1); }

// A process of this file that holds a file locked for a few seconds, for the part about who waits for whom.
if ($childMode && $argv[1] === '--child-hold') {
  $heldPath = mustBeScratch(isset($argv[3]) ? $argv[3] : '');
  $heldSeconds = isset($argv[4]) ? max(1, min(10, (int)$argv[4])) : 1;
  $heldHandle = @fopen($heldPath, 'r+');
  if ($heldHandle === FALSE or !flock($heldHandle, LOCK_EX)) {
    fwrite(STDOUT, 'not held'.PHP_EOL);
    exit(1); }
  fwrite(STDOUT, 'held'.PHP_EOL);
  fflush(STDOUT);
  sleep($heldSeconds);
  flock($heldHandle, LOCK_UN);
  fclose($heldHandle);
  exit(0); }


// Makes a folder for one part of the suite, with an Events folder inside it, & points the Event Core at that.
function eventFolder($partName) {
  global $EventLoc;
  makeFolder(scratchPath($partName), 0755);
  $EventLoc = makeFolder(scratchPath($partName.'/Events'), 0700);
  return $EventLoc; }

// The facts of an ordinary event, with whatever a check wants different.
function facts($changes) {
  return array_merge(array('Source' => 'scan', 'Kind' => 'test.made', 'Category' => 1, 'Severity' => 'info', 'Summary' => 'A test made this event.'), $changes); }

// The file the Event Core writes today, worked out here & not by the core.
function todayFile() {
  global $EventLoc;
  return $EventLoc.'/Events-'.gmdate('Ymd').'.jsonl'; }

function dayFile($day) {
  global $EventLoc;
  return $EventLoc.'/Events-'.$day.'.jsonl'; }

// A day in UTC ends at midnight, & a part that makes today's file by hand must not straddle it.
function awayFromMidnight() {
  if ((int)gmdate('His') >= 235945) sleep(20); }

// A record as the Event Core would write it, made here so that a part can put any day & any time in a file.
function fixture($stamp, $changes) {
  static $made = 0;
  $made++;
  return array_merge(array('Record' => 1, 'Id' => 'E'.gmdate('Ymd-His', $stamp).'-'.sprintf('%012x', $made), 'Time' => gmdate('Y-m-d\TH:i:s\Z', $stamp), 'Stamp' => $stamp, 'Host' => 'testhost', 'Version' => '0.0.3', 'Source' => 'scan', 'Kind' => 'test.made', 'Category' => 1, 'Severity' => 'info', 'Summary' => 'A fixture.', 'Subject' => array(), 'Detail' => array()), $changes); }

// The line of a record, worked out here & not by the core. Subject & Detail are objects, as the core writes them.
function lineOf($record) {
  return json_encode(array_merge($record, array('Subject' => (object)$record['Subject'], 'Detail' => (object)$record['Detail'])), JSON_UNESCAPED_SLASHES)."\n"; }

// Adds the line of a fixture to the file of the day its time falls on.
function keepFixture($record) {
  $path = dayFile(gmdate('Ymd', $record['Stamp']));
  $before = is_file($path) ? (string)file_get_contents($path) : '';
  writeScratchFile($path, $before.lineOf($record), 0600);
  return $record; }

function fileLines($filePath) {
  $lines = is_file($filePath) ? file($filePath, FILE_IGNORE_NEW_LINES) : array();
  return is_array($lines) ? $lines : array(); }

function isPrintable($text) {
  return (is_string($text) && preg_match('//u', $text) === 1 && preg_match('/[\x00-\x1F\x7F\x{80}-\x{9F}]/u', $text) === 0); }

function folderNames($folderPath) {
  $names = is_dir($folderPath) ? scandir($folderPath) : FALSE;
  return is_array($names) ? array_values(array_diff($names, array('.', '..'))) : array('<not a folder>'); }

// Says whether text ends in an ending, without anybody counting characters.
function endsWith($text, $ending) {
  return (is_string($text) && strlen($text) >= strlen($ending) && substr($text, -strlen($ending)) === $ending); }

function summaries($events) {
  $list = array();
  foreach ($events as $event) $list[] = field($event, 'Summary');
  return $list; }


// Part 0. What this suite stands on.
check('the Event Core calls nothing that removes, renames or runs a command', $coreCalls === array());
check('the Event Core says it is v4.3.1, with or without a lineage mark', isset($EventCoreVersion) && preg_match('/^v4\.3\.1(u?r)?$/', $EventCoreVersion) === 1);
foreach (array('eventSeverityRank', 'eventSourceIsValid', 'eventKindIsValid', 'eventIdentifierIsValid', 'eventFolderPath', 'eventDayPath', 'cleanEventValue', 'eventValueIsClean', 'eventRecordIsValid', 'eventLineOf', 'recordEvent', 'readEventDay', 'readEvents', 'findEvent', 'eventDaysBetween', 'lesserEventToken', 'eventHandleIsOfPath') as $coreFunction) check('the Event Core defines '.$coreFunction.'()', function_exists($coreFunction));
list ($directStatus, $directSaid) = runCommand(array(PHP_BINARY, $coreFile));
check('the Event Core refuses to be loaded directly, with error Engine-35000', $directSaid === 'ERROR!!! Engine-35000, A core component cannot be loaded directly!'.PHP_EOL, trim($directSaid));


// Part 1. Names.
foreach (array('info' => 0, 'low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4) as $severityName => $severityRank) check('the severity '.$severityName.' ranks '.$severityRank, eventSeverityRank($severityName) === $severityRank);
foreach (array('a capital' => 'Info', 'a space before' => ' info', 'a line break after' => "info\n", 'nothing' => '', 'a word that is no severity' => 'urgent', 'a number' => 3, 'no value' => NULL, 'a list' => array('info'), 'TRUE' => TRUE) as $why => $notSeverity) check('a severity that is '.$why.' ranks -1', eventSeverityRank($notSeverity) === -1);
foreach (array('scan', 'quarantine', 'sensor-web', 'a', 'a2', str_repeat('s', 32)) as $goodSource) check('the source '.$goodSource.' is accepted', eventSourceIsValid($goodSource) === TRUE);
foreach (array('a capital' => 'Scan', 'a digit first' => '9scan', 'a dash first' => '-scan', 'a space' => 'sc an', 'a dot' => 'scan.x', 'a line break after' => "scan\n", '33 characters' => str_repeat('s', 33), 'nothing' => '', 'no value' => NULL, 'a number' => 5, 'a list' => array('scan')) as $why => $badSource) check('a source with '.$why.' is refused', eventSourceIsValid($badSource) === FALSE);
foreach (array('scan.started', 'file.infected', 'quarantine.restored', 'a.b', 'a.b.c.d', 'scan.1', 'a.'.str_repeat('k', 32), str_repeat('k', 32).'.b') as $goodKind) check('the kind '.$goodKind.' is accepted', eventKindIsValid($goodKind) === TRUE);
foreach (array('one word' => 'scan', 'five words' => 'a.b.c.d.e', 'a dot at the end' => 'scan.', 'a dot at the start' => '.started', 'two dots together' => 'scan..started', 'a capital first' => 'Scan.started', 'a capital later' => 'scan.Started', 'a digit first' => '1scan.started', 'a space' => 'scan.star ted', 'a dash' => 'scan.star-ted', 'a line break after' => "scan.started\n", 'a first word of 33' => str_repeat('k', 33).'.b', 'a later word of 33' => 'a.'.str_repeat('k', 33), 'nothing' => '', 'no value' => NULL, 'a list' => array('scan.started')) as $why => $badKind) check('a kind with '.$why.' is refused', eventKindIsValid($badKind) === FALSE);
check('an identifier as the core gives it is accepted', eventIdentifierIsValid('E20261116-153000-0123456789ab') === TRUE);
foreach (array('a small e' => 'e20261116-153000-0123456789ab', 'capital digits' => 'E20261116-153000-0123456789AB', 'eleven digits at the end' => 'E20261116-153000-0123456789a', 'thirteen digits at the end' => 'E20261116-153000-0123456789abc', 'seven digits of day' => 'E2026111-153000-0123456789ab', 'a line break after' => "E20261116-153000-0123456789ab\n", 'a space before' => ' E20261116-153000-0123456789ab', 'a path in it' => 'E20261116-153000-../../../etc', 'nothing' => '', 'no value' => NULL, 'a number' => 20261116, 'a list' => array('E20261116-153000-0123456789ab')) as $why => $badIdentifier) check('an identifier with '.$why.' is refused', eventIdentifierIsValid($badIdentifier) === FALSE);


// Part 2. Where the record is kept.
$EventLoc = '/mnt/scratch/somewhere/Events';
check('the folder the application named is the folder used', eventFolderPath() === '/mnt/scratch/somewhere/Events');
check('a day of eight digits names one file in it', eventDayPath('20261116') === '/mnt/scratch/somewhere/Events/Events-20261116.jsonl');
$EventLoc = '/mnt/scratch/somewhere/Events///';
check('separators after the folder are not part of it', eventFolderPath() === '/mnt/scratch/somewhere/Events' && eventDayPath('20261116') === '/mnt/scratch/somewhere/Events/Events-20261116.jsonl');
foreach (array('nothing' => '', 'the filesystem root' => '/', 'several separators & nothing else' => '///', 'a path not named from the root' => 'Events', 'a path beginning where the command was started' => './Events', 'no value' => NULL, 'a list' => array('/mnt/scratch/x'), 'a number' => 7, 'a path with a byte of nothing in it' => "/mnt/scratch/x\0y") as $why => $badFolder) {
  $EventLoc = $badFolder;
  check('a folder that is '.$why.' is not used', eventFolderPath() === '');
  check('& no day has a file there', eventDayPath('20261116') === ''); }
$EventLoc = '/mnt/scratch/somewhere/Events';
foreach (array('seven digits' => '2026111', 'nine digits' => '202611160', 'dashes' => '2026-11-16', 'a path' => '../../etc/x', 'a line break after' => "20261116\n", 'a whole number' => 20261116, 'nothing' => '', 'no value' => NULL, 'a list' => array('20261116'), 'digits that are not ASCII' => "\xEF\xBC\x92\xEF\xBC\x90\xEF\xBC\x92\xEF\xBC\x96\xEF\xBC\x91\xEF\xBC\x91\xEF\xBC\x91\xEF\xBC\x96") as $why => $badDay) check('a day given as '.$why.' names no file', eventDayPath($badDay) === '');
$EventLoc = '';


// Part 3. What a value is kept as.
foreach (array(7, -3, 0, PHP_INT_MAX) as $wholeNumber) check('the whole number '.$wholeNumber.' is kept as it is', cleanEventValue($wholeNumber, 'Count', 0) === $wholeNumber);
check('TRUE & FALSE are kept as they are', cleanEventValue(TRUE, 'Flag', 0) === TRUE && cleanEventValue(FALSE, 'Flag', 0) === FALSE);
check('plain text is kept as it is', cleanEventValue('A plain sentence, with punctuation: yes.', 'Text', 0) === 'A plain sentence, with punctuation: yes.');
check('a number that is not whole is kept as text', cleanEventValue(1.5, 'Ratio', 0) === '1.5');
$textsKept = array(
  'an escape sequence' => array("before\x1B[2Jafter", 'before?[2Jafter'),
  'a byte of nothing' => array("be\0fore", 'be?fore'),
  'a bell' => array("be\x07fore", 'be?fore'),
  'a carriage return' => array("before\rafter", 'before?after'),
  'a line break' => array("before\nafter", 'before?after'),
  'a tab' => array("before\tafter", 'before?after'),
  'a delete' => array("before\x7Fafter", 'before?after'),
  'a control character of the second set' => array("before\xC2\x85after", 'before?after'),
  'bytes that are not UTF-8' => array("before\xFF\xFEafter", 'before??after'),
  'a letter outside ASCII in text that is not UTF-8' => array("caf\xE9", 'caf?'),
  'a letter outside ASCII' => array("caf\xC3\xA9", "caf\xC3\xA9"),
  'a name in another script' => array("\xE6\x97\xA5\xE6\x9C\xAC.txt", "\xE6\x97\xA5\xE6\x9C\xAC.txt"),
  '1000 bytes' => array(str_repeat('a', 1000), str_repeat('a', 1000)),
  '1001 bytes' => array(str_repeat('a', 1001), str_repeat('a', 1000).'...'),
  '1500 bytes' => array(str_repeat('a', 1500), str_repeat('a', 1000).'...'),
  'a character across the thousandth byte' => array(str_repeat('a', 999)."\xC3\xA9".'bbb', str_repeat('a', 999).'...'));
foreach ($textsKept as $why => $textPair) {
  $keptText = cleanEventValue($textPair[0], 'Text', 0);
  check('text with '.$why.' is kept as printableText() makes it, strictly', $keptText === $textPair[1], is_string($keptText) ? bin2hex(substr($keptText, 0, 40)).' of '.strlen($keptText) : gettype($keptText)); }
// A mark that turns text around is no control character, & the kernel's function lets it through. ABOUT_EVENTS.txt says so.
check('text with a mark that turns text around is kept as it is, which is a limit & is documented', cleanEventValue("exe\xE2\x80\xAEtxt.doc", 'Text', 0) === "exe\xE2\x80\xAEtxt.doc");
foreach (array('no value' => NULL, 'an object' => new stdClass(), 'a list where none may sit' => array('a')) as $why => $notKept) check($why.' is kept as empty text', cleanEventValue($notKept, 'Thing', 0) === '');
$everyByte = '';
for ($byte = 0; $byte < 256; $byte++) $everyByte .= chr($byte);
check('an encoding is kept whole', cleanEventValue(base64_encode($everyByte), 'PathEncoded', 0) === base64_encode($everyByte));
check('an encoding of nothing is kept as nothing', cleanEventValue('', 'PathEncoded', 0) === '');
check('an encoding of 8192 characters is kept', cleanEventValue(str_repeat('QUJD', 2048), 'PathEncoded', 0) === str_repeat('QUJD', 2048));
foreach (array('longer than 8192 characters' => str_repeat('QUJD', 2049), 'not a multiple of four long' => 'QUJ', 'holding a character base64 has none of' => 'QU!D', 'holding a space' => 'QU D', 'padded in the middle' => 'QU=D', 'padded three times' => 'Q===', 'holding a line break' => "QUJD\n") as $why => $badEncoding) check('an encoding '.$why.' is kept as empty text & not as something else', cleanEventValue($badEncoding, 'PathEncoded', 0) === '');
check('a key that only holds the word is not an encoding', cleanEventValue("a\x01b", 'EncodedPath', 0) === 'a?b');
$keptList = cleanEventValue(array('Name' => 'value', 3 => 'three', 'two words' => 'x', '' => 'x', 'with-dash' => 'x', '9lead' => 'x', 'Path' => "a\x01b", 'Count' => 5, 'Inner' => array('Deep' => array('Deeper' => 'x'))), 'Subject', 2);
check('a list keeps whole numbers & plain names as keys & no other', is_array($keptList) && array_keys($keptList) === array('Name', 3, 'Path', 'Count', 'Inner'), json_encode($keptList));
check('what a list holds is kept as any value is', field($keptList, 'Name') === 'value' && field($keptList, 3) === 'three' && field($keptList, 'Count') === 5 && field($keptList, 'Path') === 'a?b');
check('a list deeper than allowed is kept as empty text', field($keptList, 'Inner') === array('Deep' => ''));
$longList = array();
for ($entry = 1; $entry <= 70; $entry++) $longList[] = $entry;
check('a list keeps its first 64 entries', cleanEventValue($longList, 'Detail', 1) === array_slice($longList, 0, 64));
$mixedList = array();
for ($entry = 1; $entry <= 10; $entry++) $mixedList['bad key '.$entry] = $entry;
for ($entry = 11; $entry <= 70; $entry++) $mixedList['Key'.$entry] = $entry;
$keptMixed = cleanEventValue($mixedList, 'Detail', 1);
check('an entry with a key that is not kept still counts among the first 64', is_array($keptMixed) && count($keptMixed) === 54 && array_keys($keptMixed) === array_slice(array_keys($mixedList), 10, 54));
check('four levels of list are kept & a fifth is not', cleanEventValue(array('A' => array('B' => array('C' => array('D' => array('E' => 'x'))))), 'Detail', 4) === array('A' => array('B' => array('C' => array('D' => '')))));


// Part 4. What is read back as a value.
check('a whole number, TRUE & FALSE are read', eventValueIsClean(5, 0) === TRUE && eventValueIsClean(-5, 0) === TRUE && eventValueIsClean(TRUE, 0) === TRUE && eventValueIsClean(FALSE, 0) === TRUE);
check('printable text & empty text are read', eventValueIsClean('A plain sentence, with ~ & \\ in it.', 0) === TRUE && eventValueIsClean('', 0) === TRUE);
foreach (array('a line break' => "a\nb", 'a tab' => "a\tb", 'a delete' => "a\x7Fb", 'an escape' => "a\x1Bb", 'a byte of nothing' => "a\0b", 'a control character of the second set' => "a\xC2\x85b", 'a byte that is not UTF-8' => "caf\xE9", 'a line break at the end' => "ab\n") as $why => $uncleanText) check('text with '.$why.' is not read', eventValueIsClean($uncleanText, 0) === FALSE);
check('text with a letter outside ASCII is read, & so is a name in another script', eventValueIsClean("caf\xC3\xA9", 0) === TRUE && eventValueIsClean("\xE6\x97\xA5\xE6\x9C\xAC.txt", 0) === TRUE);
check('text is read whatever its length, because an encoding is longer than any text that is kept', eventValueIsClean(str_repeat('a', 8192), 0) === TRUE);
check('a number that is not whole is not read', eventValueIsClean(1.5, 4) === FALSE);
check('no value is not read', eventValueIsClean(NULL, 4) === FALSE);
check('a list is read where one may sit', eventValueIsClean(array('Name' => 'value', 3 => TRUE, 'Inner' => array(1, 2)), 2) === TRUE && eventValueIsClean(array(), 1) === TRUE);
check('a list is not read where none may sit', eventValueIsClean(array(), 0) === FALSE && eventValueIsClean(array('Inner' => array('Deep' => array())), 2) === FALSE);
check('a list of 64 entries is read & one of 65 is not', eventValueIsClean(array_slice($longList, 0, 64), 1) === TRUE && eventValueIsClean(array_slice($longList, 0, 65), 1) === FALSE);
foreach (array('two words' => 'two words', 'nothing' => '', 'a dash' => 'with-dash', 'a digit first' => '9lead', 'a line break after' => "Name\n", '33 characters' => str_repeat('k', 33)) as $why => $badKey) check('a list with a key of '.$why.' is not read', eventValueIsClean(array($badKey => 'x'), 1) === FALSE);
check('a list holding text that is not printable is not read', eventValueIsClean(array('Name' => "a\nb"), 1) === FALSE && eventValueIsClean(array('Inner' => array('Name' => "a\nb")), 2) === FALSE);
check('a list is not read where an entry after one that is fit is not fit', eventValueIsClean(array('First' => 'fine', 'Second' => "a\nb"), 1) === FALSE && eventValueIsClean(array('First' => 'fine', 'two words' => 'x'), 1) === FALSE && eventValueIsClean(array('fine', array('fine'), "a\x1Bb"), 2) === FALSE && eventValueIsClean(array('fine', array('fine', array())), 2) === FALSE);


// Part 5. What is read back as an event.
$goodRecord = fixture(1794000000, array('Subject' => array('Path' => '/home/a file', 'PathEncoded' => base64_encode('/home/a file')), 'Detail' => array('Scanner' => 'ScanCore', 'Names' => array('one', 'two'))));
check('a record as the core writes it is an event', eventRecordIsValid($goodRecord) === TRUE);
foreach (array_keys($goodRecord) as $fieldName) {
  $lackingRecord = $goodRecord;
  unset($lackingRecord[$fieldName]);
  check('a record without '.$fieldName.' is not an event', eventRecordIsValid($lackingRecord) === FALSE); }
$wrongRecords = array(
  'a fourteenth field' => array_merge($goodRecord, array('Extra' => 'x')),
  'a Record of 2' => array_merge($goodRecord, array('Record' => 2)),
  'a Record that is text' => array_merge($goodRecord, array('Record' => '1')),
  'an Id the core would not give' => array_merge($goodRecord, array('Id' => 'E20261116-153000-XYZ')),
  'a Time laid out another way' => array_merge($goodRecord, array('Time' => '2026-11-16 15:30:00')),
  'a Stamp that is text' => array_merge($goodRecord, array('Stamp' => '1794000000')),
  'a Stamp below nothing' => array_merge($goodRecord, array('Stamp' => -1)),
  'a Stamp that is not whole' => array_merge($goodRecord, array('Stamp' => 1794000000.5)),
  'a Source that is no name' => array_merge($goodRecord, array('Source' => 'Scan')),
  'a Kind of one word' => array_merge($goodRecord, array('Kind' => 'scan')),
  'a Severity nobody ranks' => array_merge($goodRecord, array('Severity' => 'urgent')),
  'an empty Summary' => array_merge($goodRecord, array('Summary' => '')),
  'a Summary of spaces' => array_merge($goodRecord, array('Summary' => '   ')),
  'a Summary with a line break' => array_merge($goodRecord, array('Summary' => "one\ntwo")),
  'a Category of 0' => array_merge($goodRecord, array('Category' => 0)),
  'a Category of 5' => array_merge($goodRecord, array('Category' => 5)),
  'a Category that is text' => array_merge($goodRecord, array('Category' => '1')),
  'a Host with an escape in it' => array_merge($goodRecord, array('Host' => "host\x1B[2J")),
  'a Host that is a number' => array_merge($goodRecord, array('Host' => 5)),
  'a Version that is a list' => array_merge($goodRecord, array('Version' => array('0.0.3'))),
  'a Subject that is text' => array_merge($goodRecord, array('Subject' => 'a file')),
  'a Detail that is a number' => array_merge($goodRecord, array('Detail' => 5)),
  'a Subject holding text that is not printable' => array_merge($goodRecord, array('Subject' => array('Path' => "a\x01b"))),
  'a Detail five lists deep' => array_merge($goodRecord, array('Detail' => array('A' => array('B' => array('C' => array('D' => array('E' => 'x'))))))),
  'a Subject five lists deep' => array_merge($goodRecord, array('Subject' => array('A' => array('B' => array('C' => array('D' => array('E' => 'x'))))))),
  'a Detail holding a number that is not whole' => array_merge($goodRecord, array('Detail' => array('Ratio' => 1.5))));
foreach ($wrongRecords as $why => $wrongRecord) check('a record with '.$why.' is not an event', eventRecordIsValid($wrongRecord) === FALSE);
foreach (array('text' => 'an event', 'no value' => NULL, 'a number' => 1, 'an empty list' => array()) as $why => $notRecord) check('what is '.$why.' is not an event', eventRecordIsValid($notRecord) === FALSE);
$fourDeep = array('A' => array('B' => array('C' => array('D' => 'x'))));
check('a record whose Subject & Detail are each four lists deep is an event', eventRecordIsValid(array_merge($goodRecord, array('Subject' => $fourDeep, 'Detail' => $fourDeep))) === TRUE);
check('a record with a Stamp of nothing, which is the first second there was, is an event', eventRecordIsValid(array_merge($goodRecord, array('Stamp' => 0))) === TRUE);
$bareRecord = fixture(1794000000, array());
check('a record with nothing in its Subject or its Detail is written with an empty object for each, & not an empty list', endsWith(eventLineOf($bareRecord, FALSE), '"Subject":{},"Detail":{}}') === TRUE, eventLineOf($bareRecord, FALSE));
check('a Subject that is a plain list is written as an object too, & a list inside it stays a list', strpos(eventLineOf(array_merge($bareRecord, array('Subject' => array('first', 'second'), 'Detail' => array('Names' => array('one', 'two')))), FALSE), '"Subject":{"0":"first","1":"second"},"Detail":{"Names":["one","two"]}}') !== FALSE);
check('the line holds no line break & reads back as the record it was made of', strpos(eventLineOf($goodRecord, FALSE), "\n") === FALSE && json_decode(eventLineOf($goodRecord, FALSE), TRUE) === $goodRecord);
check('what has no Subject or no Detail that is a list is written as nothing', eventLineOf(array_diff_key($bareRecord, array('Subject' => 0)), FALSE) === '' && eventLineOf(array_merge($bareRecord, array('Detail' => 'text')), FALSE) === '' && eventLineOf('text', FALSE) === '' && eventLineOf(NULL, FALSE) === '' && eventLineOf(NULL, TRUE) === '');
check('a record spread over lines for a person holds line breaks & reads back as the same record', strpos(eventLineOf($goodRecord, TRUE), "\n") !== FALSE && json_decode(eventLineOf($goodRecord, TRUE), TRUE) === $goodRecord && strpos(eventLineOf($bareRecord, TRUE), '"Subject": {}') !== FALSE);
check('only TRUE spreads a record over lines', eventLineOf($goodRecord, 1) === eventLineOf($goodRecord, FALSE) && eventLineOf($goodRecord, 'yes') === eventLineOf($goodRecord, FALSE));
$accentRecord = array_merge($bareRecord, array('Summary' => "caf\xC3\xA9 \xE6\x97\xA5"));
check('a line is ASCII whatever the record names, & reads back as the record', preg_match('/^[\x20-\x7E]+$/D', eventLineOf($accentRecord, FALSE)) === 1 && preg_match('/^[\x20-\x7E\n]+$/D', eventLineOf($accentRecord, TRUE)) === 1 && json_decode(eventLineOf($accentRecord, FALSE), TRUE) === $accentRecord && eventRecordIsValid($accentRecord) === TRUE);


// Part 6. Keeping an event.
eventFolder('p06');
awayFromMidnight();
$beforeStamp = time();
list ($kept, $identifier, $notice) = recordEvent(facts(array()));
$afterStamp = time();
$keptPath = dayFile(substr((string)$identifier, 1, 8));
$keptLines = fileLines($keptPath);
$keptRecord = (count($keptLines) === 1) ? json_decode($keptLines[0], TRUE) : NULL;
check('an event with every fact it needs is kept, & says so', $kept === TRUE && eventIdentifierIsValid($identifier) === TRUE && $notice === '', json_encode(array($kept, $identifier, $notice)));
check('it is kept in the file of its day & in no other', folderNames($EventLoc) === array('Events-'.substr((string)$identifier, 1, 8).'.jsonl') && $keptPath === todayFile());
check('the file is root\'s, & nobody else can read it', is_file($keptPath) && fileowner($keptPath) === 0 && (fileperms($keptPath) & 0777) === 0600);
check('the event is one line that ends in a line break', count($keptLines) === 1 && substr((string)file_get_contents($keptPath), -1) === "\n" && substr_count((string)file_get_contents($keptPath), "\n") === 1);
check('the line is the thirteen fields, in the order the core writes them', is_array($keptRecord) && array_keys($keptRecord) === array('Record', 'Id', 'Time', 'Stamp', 'Host', 'Version', 'Source', 'Kind', 'Category', 'Severity', 'Summary', 'Subject', 'Detail'));
check('the core wrote the identifier it gave back', field($keptRecord, 'Record') === 1 && field($keptRecord, 'Id') === $identifier);
check('the core wrote the time itself, twice & alike', is_int(field($keptRecord, 'Stamp')) && field($keptRecord, 'Stamp') >= $beforeStamp && field($keptRecord, 'Stamp') <= $afterStamp && field($keptRecord, 'Time') === gmdate('Y-m-d\TH:i:s\Z', (int)field($keptRecord, 'Stamp')) && substr((string)$identifier, 1, 15) === gmdate('Ymd-His', (int)field($keptRecord, 'Stamp')));
check('the core wrote the host & the version itself', field($keptRecord, 'Host') === printableText((string)gethostname(), TRUE) && field($keptRecord, 'Version') === '0.0.3');
check('the facts are kept as they were handed over', field($keptRecord, 'Source') === 'scan' && field($keptRecord, 'Kind') === 'test.made' && field($keptRecord, 'Category') === 1 && field($keptRecord, 'Severity') === 'info' && field($keptRecord, 'Summary') === 'A test made this event.');
check('an event with no Subject & no Detail keeps an empty one of each, written as an object', field($keptRecord, 'Subject') === array() && field($keptRecord, 'Detail') === array() && endsWith(field($keptLines, 0), '"Subject":{},"Detail":{}}') === TRUE);
check('what was kept is what the core reads back as an event', eventRecordIsValid($keptRecord) === TRUE);
$firstBytes = (string)file_get_contents($keptPath);
$rawPath = "/home/user/a \x1B[2J file\n.txt";
list ($keptTwo, $identifierTwo, $noticeTwo) = recordEvent(facts(array('Kind' => 'file.infected', 'Severity' => 'high', 'Category' => 2, 'Summary' => '  A file was found.  ', 'Subject' => array('Path' => $rawPath, 'PathEncoded' => base64_encode($rawPath), 'ScanId' => '20261116-000000-aaaaaaaaaaaa'), 'Detail' => array('Scanner' => 'ScanCore', 'Names' => array('one', 'two'), 'Count' => 2, 'Whole' => TRUE))));
$bothBytes = (string)file_get_contents($keptPath);
$bothLines = fileLines($keptPath);
$recordTwo = (count($bothLines) === 2) ? json_decode($bothLines[1], TRUE) : NULL;
check('a second event is a second line, & the first is as it was', $keptTwo === TRUE && count($bothLines) === 2 && substr($bothBytes, 0, strlen($firstBytes)) === $firstBytes && $identifierTwo !== $identifier);
check('a Summary loses the spaces around it', field($recordTwo, 'Summary') === 'A file was found.');
check('a Subject keeps its encoding whole & its text printable', is_array(field($recordTwo, 'Subject')) && base64_decode((string)field(field($recordTwo, 'Subject'), 'PathEncoded'), TRUE) === $rawPath && field(field($recordTwo, 'Subject'), 'Path') === '/home/user/a ?[2J file?.txt' && field(field($recordTwo, 'Subject'), 'ScanId') === '20261116-000000-aaaaaaaaaaaa');
check('a Detail keeps text, lists, whole numbers & booleans', field($recordTwo, 'Detail') === array('Scanner' => 'ScanCore', 'Names' => array('one', 'two'), 'Count' => 2, 'Whole' => TRUE) && endsWith(field($bothLines, 1), '"Detail":{"Scanner":"ScanCore","Names":["one","two"],"Count":2,"Whole":true}}') === TRUE);
list ($keptListed, $identifierListed, $noticeListed) = recordEvent(facts(array('Subject' => array('first', 'second'))));
list ($foundListed, $recordListed) = findEvent($identifierListed);
check('a Subject handed over as a plain list reads back as it was handed over', $keptListed === TRUE && $foundListed === TRUE && field($recordListed, 'Subject') === array('first', 'second'));
list ($keptLong, $identifierLong, $noticeLong) = recordEvent(facts(array('Summary' => str_repeat('a', 3000))));
list ($foundLong, $recordLong) = findEvent($identifierLong);
check('a Summary is cut at 1000 bytes, & three dots say so', $keptLong === TRUE && $foundLong === TRUE && field($recordLong, 'Summary') === str_repeat('a', 1000).'...');
list ($keptOdd, $identifierOdd, $noticeOdd) = recordEvent(facts(array('Summary' => "A summary\x1B[31m in red\r\nover two lines.")));
list ($foundOdd, $recordOdd) = findEvent($identifierOdd);
check('a Summary is kept as characters a terminal prints', $keptOdd === TRUE && $foundOdd === TRUE && field($recordOdd, 'Summary') === 'A summary?[31m in red??over two lines.');
list ($keptAccent, $identifierAccent, $noticeAccent) = recordEvent(facts(array('Summary' => "A file named caf\xC3\xA9.txt was found.", 'Subject' => array('Path' => "/srv/caf\xC3\xA9.txt"))));
list ($foundAccent, $recordAccent) = findEvent($identifierAccent);
check('an event naming a file with a letter outside ASCII is kept & read back with the letter', $keptAccent === TRUE && $foundAccent === TRUE && field($recordAccent, 'Summary') === "A file named caf\xC3\xA9.txt was found." && field(field($recordAccent, 'Subject'), 'Path') === "/srv/caf\xC3\xA9.txt", $noticeAccent);
$EngineApplicationVersion = NULL;
list ($keptBare, $identifierBare, $noticeBare) = recordEvent(facts(array()));
list ($foundBare, $recordBare) = findEvent($identifierBare);
$EngineApplicationVersion = '0.0.3';
check('an event is kept where the application gave no version, with none', $keptBare === TRUE && $foundBare === TRUE && field($recordBare, 'Version') === '');
$fiveDeep = array('A' => array('B' => array('C' => array('D' => array('E' => 'x')))));
list ($keptDeep, $identifierDeep, $noticeDeep) = recordEvent(facts(array('Subject' => $fiveDeep, 'Detail' => $fiveDeep)));
list ($foundDeep, $recordDeep) = findEvent($identifierDeep);
check('an event whose Subject & Detail are five lists deep is kept, with the fifth list of each as empty text', $keptDeep === TRUE && $foundDeep === TRUE && field($recordDeep, 'Subject') === array('A' => array('B' => array('C' => array('D' => '')))) && field($recordDeep, 'Detail') === array('A' => array('B' => array('C' => array('D' => '')))), $noticeDeep);


// Part 7. Facts that are refused.
eventFolder('p07');
$refusals = array(
  'what is not a list of facts' => array('text', 'An event was not kept, because what was handed over is not a list of facts.'),
  'no value at all' => array(NULL, 'An event was not kept, because what was handed over is not a list of facts.'),
  'no Source' => array(array_diff_key(facts(array()), array('Source' => 0)), 'An event was not kept, because its Source is not a name the Event Core accepts.'),
  'a Source that is no name' => array(facts(array('Source' => 'Scan Core')), 'An event was not kept, because its Source is not a name the Event Core accepts.'),
  'no Kind' => array(array_diff_key(facts(array()), array('Kind' => 0)), 'An event was not kept, because its Kind is not a name the Event Core accepts.'),
  'a Kind of one word' => array(facts(array('Kind' => 'started')), 'An event was not kept, because its Kind is not a name the Event Core accepts.'),
  'no Category' => array(array_diff_key(facts(array()), array('Category' => 0)), 'An event was not kept, because its Category is not a whole number from 1 to 4.'),
  'a Category of 0' => array(facts(array('Category' => 0)), 'An event was not kept, because its Category is not a whole number from 1 to 4.'),
  'a Category of 5' => array(facts(array('Category' => 5)), 'An event was not kept, because its Category is not a whole number from 1 to 4.'),
  'a Category that is text' => array(facts(array('Category' => '1')), 'An event was not kept, because its Category is not a whole number from 1 to 4.'),
  'a Category that is not whole' => array(facts(array('Category' => 1.0)), 'An event was not kept, because its Category is not a whole number from 1 to 4.'),
  'no Severity' => array(array_diff_key(facts(array()), array('Severity' => 0)), 'An event was not kept, because its Severity is not info, low, medium, high or critical.'),
  'a Severity nobody ranks' => array(facts(array('Severity' => 'urgent')), 'An event was not kept, because its Severity is not info, low, medium, high or critical.'),
  'a Severity given as its rank' => array(facts(array('Severity' => 3)), 'An event was not kept, because its Severity is not info, low, medium, high or critical.'),
  'no Summary' => array(array_diff_key(facts(array()), array('Summary' => 0)), 'An event was not kept, because it has no Summary.'),
  'an empty Summary' => array(facts(array('Summary' => '')), 'An event was not kept, because it has no Summary.'),
  'a Summary of spaces' => array(facts(array('Summary' => '    ')), 'An event was not kept, because it has no Summary.'),
  'a Summary that is a list' => array(facts(array('Summary' => array('a summary'))), 'An event was not kept, because it has no Summary.'),
  'a Subject that is text' => array(facts(array('Subject' => '/home/a file')), 'An event was not kept, because its Subject or its Detail is not a list.'),
  'a Detail that is a number' => array(facts(array('Detail' => 5)), 'An event was not kept, because its Subject or its Detail is not a list.'));
foreach ($refusals as $why => $refusal) {
  list ($keptRefused, $identifierRefused, $noticeRefused) = recordEvent($refusal[0]);
  check('an event with '.$why.' is not kept, gets no identifier & says why', $keptRefused === FALSE && $identifierRefused === '' && $noticeRefused === $refusal[1], json_encode(array($keptRefused, $identifierRefused, $noticeRefused))); }
check('not one of them left a file behind', folderNames($EventLoc) === array());


// Part 8. An event too large for a line.
// Text is cut at 1000 bytes, so a line is filled with lists of text of 1000 bytes, & measured before it is filled to the byte.
eventFolder('p08');
awayFromMidnight();
$fullList = array_fill(0, 64, str_repeat('a', 1000));
list ($keptPad, $identifierPad, $noticePad) = recordEvent(facts(array('Detail' => array('A' => $fullList, 'B' => array(), 'C' => ''))));
$padLines = fileLines(todayFile());
$measuredLength = (count($padLines) === 1) ? strlen($padLines[0]) : 0;
// A text in the list B costs its length & two quotation marks, & the text C costs its length.
$bytesNeeded = 65535 - $measuredLength;
$bytesInB = max(0, min(1000, $bytesNeeded - 2));
$bytesInC = max(0, $bytesNeeded - 2 - $bytesInB);
check('an event with a full list is kept, to measure a line by, & leaves room to fill', $keptPad === TRUE && $measuredLength > 64000 && $bytesNeeded >= 3 && $bytesNeeded <= 2001 && $bytesInC < 1000, $measuredLength.' '.$noticePad);
$edgeDetail = array('A' => $fullList, 'B' => array(str_repeat('b', $bytesInB)), 'C' => str_repeat('c', $bytesInC));
list ($keptEdge, $identifierEdge, $noticeEdge) = recordEvent(facts(array('Detail' => $edgeDetail)));
$edgeLines = fileLines(todayFile());
list ($foundEdge, $recordEdge) = findEvent($identifierEdge);
check('a line of exactly 65536 bytes with its line break is kept whole', $keptEdge === TRUE && count($edgeLines) === 2 && strlen((string)field($edgeLines, 1)) === 65535 && $foundEdge === TRUE && field($recordEdge, 'Detail') === $edgeDetail, strlen((string)field($edgeLines, 1)).' '.$noticeEdge);
list ($keptOver, $identifierOver, $noticeOver) = recordEvent(facts(array('Detail' => array('A' => $fullList, 'B' => array(str_repeat('b', $bytesInB)), 'C' => str_repeat('c', $bytesInC + 1)))));
list ($foundOver, $recordOver) = findEvent($identifierOver);
check('a Detail that makes the line one byte longer is replaced by a note, & the event is kept', $keptOver === TRUE && $noticeOver === '' && $foundOver === TRUE && field($recordOver, 'Detail') === array('Note' => 'The detail of this event was too large to keep.') && field($recordOver, 'Summary') === 'A test made this event.');
$linesBeforeHuge = count(fileLines(todayFile()));
$hugeSubject = array('First' => array_fill(0, 64, str_repeat('a', 1000)), 'Second' => array_fill(0, 64, str_repeat('b', 1000)));
list ($keptHuge, $identifierHuge, $noticeHuge) = recordEvent(facts(array('Subject' => $hugeSubject)));
check('an event too large without its Detail is not kept, & says why', $keptHuge === FALSE && $identifierHuge === '' && $noticeHuge === 'An event was not kept, because it is too large for one line of the event record.' && count(fileLines(todayFile())) === $linesBeforeHuge);
list ($keptCut, $identifierCut, $noticeCut) = recordEvent(facts(array('Subject' => array('Path' => str_repeat('a', 70000)))));
list ($foundCut, $recordCut) = findEvent($identifierCut);
check('one text of 70000 bytes is cut & does not make an event too large', $keptCut === TRUE && $foundCut === TRUE && field(field($recordCut, 'Subject'), 'Path') === str_repeat('a', 1000).'...');
// The note that stands in for a Detail has a length of its own, so a line that holds it is measured & filled the same way, by its Subject.
list ($keptBase, $identifierBase, $noticeBase) = recordEvent(facts(array('Subject' => array('A' => $fullList, 'B' => array(), 'C' => ''))));
$baseLines = fileLines(todayFile());
$baseLength = ($keptBase === TRUE && count($baseLines) > 0) ? strlen($baseLines[count($baseLines) - 1]) : 0;
$noteJson = '{"Note":"The detail of this event was too large to keep."}';
// The line that was measured holds {} for its Detail, & the line that will be kept holds the note there.
$noteBytesNeeded = 65535 - ($baseLength - 2 + strlen($noteJson));
$noteBytesInB = max(0, min(1000, $noteBytesNeeded - 2));
$noteBytesInC = max(0, $noteBytesNeeded - 2 - $noteBytesInB);
check('an event with a full list in its Subject is kept, to measure a line by, & leaves room to fill', $keptBase === TRUE && $baseLength > 64000 && $noteBytesNeeded >= 3 && $noteBytesNeeded <= 2001 && $noteBytesInC < 1000, $baseLength.' '.$noticeBase);
$largeDetail = array('Pad' => array_fill(0, 8, str_repeat('d', 1000)));
$noteSubject = array('A' => $fullList, 'B' => array(str_repeat('b', $noteBytesInB)), 'C' => str_repeat('c', $noteBytesInC));
list ($keptNote, $identifierNote, $noticeNote) = recordEvent(facts(array('Subject' => $noteSubject, 'Detail' => $largeDetail)));
$noteLines = fileLines(todayFile());
$noteLength = strlen((string)field($noteLines, count($noteLines) - 1));
list ($foundNote, $recordNote) = findEvent($identifierNote);
check('a line of exactly 65536 bytes with its line break is kept where a note stands in for the Detail', $keptNote === TRUE && $noticeNote === '' && $noteLength === 65535 && $foundNote === TRUE && field($recordNote, 'Subject') === $noteSubject && field($recordNote, 'Detail') === array('Note' => 'The detail of this event was too large to keep.'), $noteLength.' '.$noticeNote);
$linesBeforeLonger = count(fileLines(todayFile()));
list ($keptLonger, $identifierLonger, $noticeLonger) = recordEvent(facts(array('Subject' => array('A' => $fullList, 'B' => array(str_repeat('b', $noteBytesInB)), 'C' => str_repeat('c', $noteBytesInC + 1)), 'Detail' => $largeDetail)));
check('one byte more & the event is not kept, so no line of 65537 bytes is ever written', $keptLonger === FALSE && $identifierLonger === '' && $noticeLonger === 'An event was not kept, because it is too large for one line of the event record.' && count(fileLines(todayFile())) === $linesBeforeLonger, $noticeLonger);
list ($edgeRead, $edgeEvents, $edgeMatched, $edgeUnread) = readEventDay(gmdate('Ymd'), array(), 100);
check('every line the core wrote here is read back, the longest included', $edgeRead === TRUE && count($edgeEvents) === 6 && $edgeMatched === 6 && $edgeUnread === 0, count($edgeEvents).' '.$edgeUnread);


// Part 9. A file or a folder that is not root's alone.
eventFolder('p09a');
awayFromMidnight();
$linkTarget = writeScratchFile(scratchPath('p09a/target.txt'), "kept as it was\n", 0600);
symlink($linkTarget, mustBeScratch(todayFile()));
list ($keptLink, $identifierLink, $noticeLink) = recordEvent(facts(array()));
check('a link where today\'s file should be is not written through', $keptLink === FALSE && $identifierLink === '' && $noticeLink === 'An event was not kept. '.todayFile().' is not a plain file & was not opened.' && file_get_contents($linkTarget) === "kept as it was\n", $noticeLink);

eventFolder('p09b');
awayFromMidnight();
writeScratchFile(todayFile(), "one line of something\n", 0600);
link(todayFile(), mustBeScratch(scratchPath('p09b/second-name')));
list ($keptTwin, $identifierTwin, $noticeTwin) = recordEvent(facts(array()));
check('a file with a second name is not written', $keptTwin === FALSE && $noticeTwin === 'An event was not kept. '.todayFile().' has more than one name & was not opened.' && file_get_contents(todayFile()) === "one line of something\n", $noticeTwin);

eventFolder('p09c');
awayFromMidnight();
writeScratchFile(todayFile(), "one line of something\n", 0600);
chown(todayFile(), 65534);
clearstatcache();
list ($keptOwned, $identifierOwned, $noticeOwned) = recordEvent(facts(array()));
check('a file that belongs to another account is not written', $keptOwned === FALSE && $noticeOwned === 'An event was not kept, because '.todayFile().' is not root\'s alone.' && file_get_contents(todayFile()) === "one line of something\n", $noticeOwned);

foreach (array('p09d1' => 0640, 'p09d2' => 0604, 'p09d3' => 0660, 'p09d4' => 0666) as $partName => $looseMode) {
  eventFolder($partName);
  awayFromMidnight();
  writeScratchFile(todayFile(), "one line of something\n", $looseMode);
  list ($keptLoose, $identifierLoose, $noticeLoose) = recordEvent(facts(array()));
  check('a file of mode '.decoct($looseMode).' is not written', $keptLoose === FALSE && $noticeLoose === 'An event was not kept, because '.todayFile().' is not root\'s alone.' && file_get_contents(todayFile()) === "one line of something\n", $noticeLoose); }

eventFolder('p09d5');
awayFromMidnight();
writeScratchFile(todayFile(), '', 0600);
list ($keptTight, $identifierTight, $noticeTight) = recordEvent(facts(array()));
check('a file of mode 600 that is root\'s is written', $keptTight === TRUE && count(fileLines(todayFile())) === 1, $noticeTight);

eventFolder('p09e');
awayFromMidnight();
chmod(scratchPath('p09e'), 0775);
clearstatcache();
list ($keptLooseFolder, $identifierLooseFolder, $noticeLooseFolder) = recordEvent(facts(array()));
check('nothing is written in a folder another account could replace', $keptLooseFolder === FALSE && $noticeLooseFolder === 'An event was not kept. '.todayFile().' was not opened. Another account could replace '.$EventLoc.'. '.scratchPath('p09e').' can be written by an account other than root.' && folderNames($EventLoc) === array(), $noticeLooseFolder);

makeFolder(scratchPath('p09f'), 0755);
$EventLoc = scratchPath('p09f/Events');
list ($keptNoFolder, $identifierNoFolder, $noticeNoFolder) = recordEvent(facts(array()));
check('the core makes no folder, & keeps nothing where there is none', $keptNoFolder === FALSE && $noticeNoFolder === 'An event was not kept. '.todayFile().' was not opened. '.$EventLoc.' does not exist.' && !file_exists($EventLoc) && folderNames(scratchPath('p09f')) === array(), $noticeNoFolder);

foreach (array('nothing' => '', 'the filesystem root' => '/', 'a path not named from the root' => 'Events') as $why => $badFolder) {
  $EventLoc = $badFolder;
  list ($keptNowhere, $identifierNowhere, $noticeNowhere) = recordEvent(facts(array()));
  check('an event is not kept where the folder is '.$why, $keptNowhere === FALSE && $identifierNowhere === '' && $noticeNowhere === 'An event was not kept, because the event record has no folder.', $noticeNowhere); }

eventFolder('p09h');
awayFromMidnight();
posix_mkfifo(mustBeScratch(todayFile()), 0600);
clearstatcache();
$beganAt = microtime(TRUE);
list ($keptPipe, $identifierPipe, $noticePipe) = recordEvent(facts(array()));
check('a pipe where today\'s file should be is not opened, & nothing waits on it', $keptPipe === FALSE && $noticePipe === 'An event was not kept. '.todayFile().' is not a plain file & was not opened.' && (microtime(TRUE) - $beganAt) < 2.0, $noticePipe);

eventFolder('p09i');
awayFromMidnight();
makeFolder(todayFile(), 0700);
list ($keptFolderThere, $identifierFolderThere, $noticeFolderThere) = recordEvent(facts(array()));
check('a folder where today\'s file should be is not written', $keptFolderThere === FALSE && $noticeFolderThere === 'An event was not kept. '.todayFile().' is not a plain file & was not opened.' && folderNames(todayFile()) === array(), $noticeFolderThere);

makeFolder(scratchPath('p09j'), 0755);
makeFolder(scratchPath('p09j/Real'), 0700);
symlink(scratchPath('p09j/Real'), mustBeScratch(scratchPath('p09j/Events')));
$EventLoc = scratchPath('p09j/Events');
list ($keptLinkedFolder, $identifierLinkedFolder, $noticeLinkedFolder) = recordEvent(facts(array()));
check('a folder that is a link is not written through', $keptLinkedFolder === FALSE && $noticeLinkedFolder === 'An event was not kept. '.todayFile().' was not opened. '.$EventLoc.' is a link.' && folderNames(scratchPath('p09j/Real')) === array(), $noticeLinkedFolder);


// Part 10. A line that was cut short.
eventFolder('p10');
awayFromMidnight();
$wholeLine = lineOf(fixture(time() - 30, array('Summary' => 'A whole line.')));
writeScratchFile(todayFile(), $wholeLine.'{"Record":1,"Id":"E2026', 0600);
list ($cutRead, $cutEvents, $cutMatched, $cutUnread) = readEventDay(gmdate('Ymd'), array(), 100);
check('a last line with no line break is neither read nor counted', $cutRead === TRUE && summaries($cutEvents) === array('A whole line.') && $cutMatched === 1 && $cutUnread === 0);
list ($keptAfterCut, $identifierAfterCut, $noticeAfterCut) = recordEvent(facts(array('Summary' => 'The line after the cut.')));
$cutLines = fileLines(todayFile());
check('an event kept after it begins on a line of its own', $keptAfterCut === TRUE && count($cutLines) === 3 && $cutLines[0]."\n" === $wholeLine && $cutLines[1] === '{"Record":1,"Id":"E2026' && eventRecordIsValid(json_decode((string)field($cutLines, 2), TRUE)) === TRUE, $noticeAfterCut);
list ($cutRead, $cutEvents, $cutMatched, $cutUnread) = readEventDay(gmdate('Ymd'), array(), 100);
check('the line that was cut short is then counted once, & the events on either side of it are read', $cutRead === TRUE && summaries($cutEvents) === array('A whole line.', 'The line after the cut.') && $cutUnread === 1);

eventFolder('p10b');
awayFromMidnight();
writeScratchFile(todayFile(), 'x', 0600);
list ($keptAfterByte, $identifierAfterByte, $noticeAfterByte) = recordEvent(facts(array('Summary' => 'The line after one byte.')));
$bytesAfterByte = (string)file_get_contents(todayFile());
list ($foundAfterByte, $recordAfterByte) = findEvent($identifierAfterByte);
check('an event kept in a file of one byte begins on a line of its own, & is read back', $keptAfterByte === TRUE && substr($bytesAfterByte, 0, 2) === "x\n" && substr_count($bytesAfterByte, "\n") === 2 && $foundAfterByte === TRUE && field($recordAfterByte, 'Summary') === 'The line after one byte.', $noticeAfterByte);


// Part 11. Reading one day.
eventFolder('p11');
$dayStart = gmmktime(0, 0, 0, 1, 1, 2020);
$dayRecords = array();
foreach (array('info', 'low', 'medium', 'high', 'critical') as $position => $severityName) $dayRecords[] = fixture($dayStart + 3600 * ($position + 1), array('Severity' => $severityName, 'Summary' => 'The '.$severityName.' one.'));
$dayText = lineOf($dayRecords[0])."not json at all\n".lineOf($dayRecords[1]).lineOf(array_merge($dayRecords[1], array('Record' => 2)))."[]\n".'"text"'."\n".lineOf($dayRecords[2])."\n   \n".'{"Pad":"'.str_repeat('a', 70000).'"}'."\n".lineOf($dayRecords[3]).'{"Pad":"'.str_repeat('b', 200000).'"}'."\n".lineOf($dayRecords[4]);
writeScratchFile(dayFile('20200101'), $dayText, 0600);
list ($dayRead, $dayEvents, $dayMatched, $dayUnread) = readEventDay('20200101', array(), 100);
check('the events of a day are read, oldest first', $dayRead === TRUE && summaries($dayEvents) === array('The info one.', 'The low one.', 'The medium one.', 'The high one.', 'The critical one.') && $dayMatched === 5);
check('each event read is the record that was kept', $dayEvents === $dayRecords);
check('a line that is not JSON, one that is not an event & one too long are each counted, & a line of nothing is not', $dayUnread === 6, (string)$dayUnread);
list ($dayRead, $dayEvents, $dayMatched, $dayUnread) = readEventDay('20200101', array('Since' => $dayStart + 3600 * 3), 100);
check('a time leaves out what is older', summaries($dayEvents) === array('The medium one.', 'The high one.', 'The critical one.') && $dayMatched === 3);
list ($dayRead, $dayEvents, $dayMatched, $dayUnread) = readEventDay('20200101', array('Rank' => 3), 100);
check('a rank leaves out what is less severe', summaries($dayEvents) === array('The high one.', 'The critical one.') && $dayMatched === 2);
list ($dayRead, $dayEvents, $dayMatched, $dayUnread) = readEventDay('20200101', array('Id' => $dayRecords[1]['Id']), 100);
check('an identifier leaves out every other event', summaries($dayEvents) === array('The low one.') && $dayMatched === 1);
list ($dayRead, $dayEvents, $dayMatched, $dayUnread) = readEventDay('20200101', array('Since' => $dayStart + 3600 * 2, 'Rank' => 2, 'Id' => $dayRecords[0]['Id']), 100);
check('everything asked for must hold at once', $dayRead === TRUE && $dayEvents === array() && $dayMatched === 0);
list ($dayRead, $dayEvents, $dayMatched, $dayUnread) = readEventDay('20200101', array(), 2);
check('the newest are given where more matched than were asked for', summaries($dayEvents) === array('The high one.', 'The critical one.') && $dayMatched === 5);
foreach (array('none' => 0, 'fewer than none' => -5, 'a word' => 'many') as $why => $fewEvents) {
  list ($dayRead, $dayEvents, $dayMatched, $dayUnread) = readEventDay('20200101', array(), $fewEvents);
  check('asking for '.$why.' gives the newest one', summaries($dayEvents) === array('The critical one.') && $dayMatched === 5); }
foreach (array('no value' => NULL, 'text' => 'everything', 'a time that is text' => array('Since' => (string)($dayStart + 3600 * 3)), 'a rank that is text' => array('Rank' => '3'), 'an identifier that is a number' => array('Id' => 5)) as $why => $looseFilter) {
  list ($dayRead, $dayEvents, $dayMatched, $dayUnread) = readEventDay('20200101', $looseFilter, 100);
  check('what to look for given as '.$why.' leaves nothing out', $dayRead === TRUE && count($dayEvents) === 5); }
$manyText = '';
for ($number = 1; $number <= 2500; $number++) $manyText .= lineOf(fixture($dayStart + 86400 + $number, array('Summary' => 'Number '.$number.'.')));
writeScratchFile(dayFile('20200102'), $manyText, 0600);
list ($dayRead, $dayEvents, $dayMatched, $dayUnread) = readEventDay('20200102', array(), 100);
check('of 2500 events the newest 100 are given, in order', $dayRead === TRUE && count($dayEvents) === 100 && $dayMatched === 2500 && field(field($dayEvents, 0), 'Summary') === 'Number 2401.' && field(field($dayEvents, 99), 'Summary') === 'Number 2500.');
list ($dayRead, $dayEvents, $dayMatched, $dayUnread) = readEventDay('20200102', array(), 20000);
check('all of them are given where more were asked for than there are', count($dayEvents) === 2500 && $dayMatched === 2500);
$crowdText = '';
for ($number = 1; $number <= 10001; $number++) $crowdText .= lineOf(fixture($dayStart + 12 * 86400 + $number, array('Summary' => 'Number '.$number.'.')));
writeScratchFile(dayFile('20200113'), $crowdText, 0600);
list ($dayRead, $dayEvents, $dayMatched, $dayUnread) = readEventDay('20200113', array(), 20000);
check('no more than 10000 are ever given, & of 10001 they are the newest', $dayRead === TRUE && count($dayEvents) === 10000 && $dayMatched === 10001 && field(field($dayEvents, 0), 'Summary') === 'Number 2.' && field(field($dayEvents, 9999), 'Summary') === 'Number 10001.', count($dayEvents).' '.$dayMatched);
$afterLong = fixture($dayStart + 20 * 86400 + 120, array('Summary' => 'After the long lines.'));
writeScratchFile(dayFile('20200121'), str_repeat('x', 131072).lineOf(fixture($dayStart + 20 * 86400 + 60, array('Summary' => 'At the end of a long line.'))).str_repeat('y', 131072)."\n".lineOf($afterLong), 0600);
list ($dayRead, $dayEvents, $dayMatched, $dayUnread) = readEventDay('20200121', array(), 100);
check('an event at the end of a line too long to be one is part of that line & is not read, & each long line is counted once', $dayRead === TRUE && $dayEvents === array($afterLong) && $dayUnread === 2, json_encode(array(summaries($dayEvents), $dayUnread)));
$padBase = strlen(lineOf(fixture($dayStart + 21 * 86400 + 60, array('Detail' => array('Pad' => ''))))) - 1;
$fitsRecord = fixture($dayStart + 21 * 86400 + 60, array('Detail' => array('Pad' => str_repeat('p', 65535 - $padBase))));
$tooLongRecord = fixture($dayStart + 21 * 86400 + 120, array('Detail' => array('Pad' => str_repeat('p', 65536 - $padBase))));
writeScratchFile(dayFile('20200122'), lineOf($tooLongRecord).lineOf($fitsRecord), 0600);
list ($dayRead, $dayEvents, $dayMatched, $dayUnread) = readEventDay('20200122', array(), 100);
check('a line of 65536 bytes with its line break is read, & one of 65537 is counted & passed over, whoever wrote it', strlen(lineOf($fitsRecord)) === 65536 && strlen(lineOf($tooLongRecord)) === 65537 && $dayRead === TRUE && $dayEvents === array($fitsRecord) && $dayUnread === 1, json_encode(array(count($dayEvents), $dayUnread)));
check('a day with no file was read, & nothing happened on it', readEventDay('20200103', array(), 100) === array(TRUE, array(), 0, 0));
check('a day that is not eight digits was not read', readEventDay('2020-01-03', array(), 100) === array(FALSE, array(), 0, 0));
writeScratchFile(dayFile('20200104'), '', 0600);
check('a file with nothing in it was read', readEventDay('20200104', array(), 100) === array(TRUE, array(), 0, 0));
writeScratchFile(dayFile('20200105'), lineOf($dayRecords[0]).rtrim(lineOf($dayRecords[1]), "\n"), 0600);
list ($dayRead, $dayEvents, $dayMatched, $dayUnread) = readEventDay('20200105', array(), 100);
check('a whole event with no line break after it is still being written, & is not read', $dayRead === TRUE && summaries($dayEvents) === array('The info one.') && $dayUnread === 0);
$readTarget = writeScratchFile(scratchPath('p11/target.jsonl'), lineOf($dayRecords[0]), 0600);
symlink($readTarget, mustBeScratch(dayFile('20200106')));
check('a link is not read through', readEventDay('20200106', array(), 100) === array(FALSE, array(), 0, 0));
// From HRProtect's v0.0.6. PHP opens a file by the path it last resolved its name to, for as long as it remembers one. A day is read as it is now.
// HRProtect's mutation notes held, from its v0.0.3, that nothing here resolved the path of a day. fopen() does, & this is the check that was missing.
$elsewhereDay = writeScratchFile(scratchPath('p11/elsewhere.jsonl'), lineOf($dayRecords[4]), 0600);
writeScratchFile(dayFile('20200112'), lineOf($dayRecords[0]), 0600);
$phpRemembers = phpRemembersPaths();
$ledElsewhere = rememberElsewhere(dayFile('20200112'), $elsewhereDay);
list ($dayRead, $dayEvents, $dayMatched, $dayUnread) = readEventDay('20200112', array(), 100);
check('a day PHP remembers as leading to another file is read as the file it is', $ledElsewhere === $phpRemembers && $dayRead === TRUE && summaries($dayEvents) === array('The info one.') && $dayUnread === 0, json_encode(array($ledElsewhere, $dayRead, summaries($dayEvents))));
writeScratchFile(dayFile('20200107'), lineOf($dayRecords[0]), 0600);
link(dayFile('20200107'), mustBeScratch(scratchPath('p11/second-name')));
check('a file with a second name is not read', readEventDay('20200107', array(), 100) === array(FALSE, array(), 0, 0));
writeScratchFile(dayFile('20200108'), lineOf($dayRecords[0]), 0600);
chown(dayFile('20200108'), 65534);
clearstatcache();
check('a file that belongs to another account is not read', readEventDay('20200108', array(), 100) === array(FALSE, array(), 0, 0));
writeScratchFile(dayFile('20200109'), lineOf($dayRecords[0]), 0640);
check('a file others can read is not read', readEventDay('20200109', array(), 100) === array(FALSE, array(), 0, 0));
posix_mkfifo(mustBeScratch(dayFile('20200110')), 0600);
clearstatcache();
$beganAt = microtime(TRUE);
check('a pipe is not read, & nothing waits on it', readEventDay('20200110', array(), 100) === array(FALSE, array(), 0, 0) && (microtime(TRUE) - $beganAt) < 2.0);
makeFolder(dayFile('20200111'), 0700);
check('a folder is not read', readEventDay('20200111', array(), 100) === array(FALSE, array(), 0, 0));
eventFolder('p11e');
writeScratchFile(dayFile('20200101'), lineOf($dayRecords[0]), 0600);
chmod(scratchPath('p11e'), 0775);
clearstatcache();
check('nothing is read in a folder another account could replace', readEventDay('20200101', array(), 100) === array(FALSE, array(), 0, 0));

eventFolder('p11w');
awayFromMidnight();
list ($keptBeforeLock, $identifierBeforeLock, $noticeBeforeLock) = recordEvent(facts(array('Summary' => 'Before the file was held.')));
$holder = startCommand(array(PHP_BINARY, __FILE__, '--child-hold', $tree, todayFile(), '3'));
$holderSaid = trim((string)fgets($holder[1]));
$beganAt = microtime(TRUE);
list ($heldRead, $heldEvents, $heldMatched, $heldUnread) = readEventDay(gmdate('Ymd'), array(), 10);
$readTook = microtime(TRUE) - $beganAt;
check('a reader is not held up by a process that holds the file', $holderSaid === 'held' && $heldRead === TRUE && summaries($heldEvents) === array('Before the file was held.') && $readTook < 1.0, $holderSaid.' '.round($readTook, 2));
$beganAt = microtime(TRUE);
list ($keptAfterLock, $identifierAfterLock, $noticeAfterLock) = recordEvent(facts(array('Summary' => 'After the file was let go.')));
$writeTook = microtime(TRUE) - $beganAt;
check('a writer waits for the process that holds the file, & then keeps its event', $keptAfterLock === TRUE && $writeTook > 1.0 && $writeTook < 10.0 && count(fileLines(todayFile())) === 2, $noticeAfterLock.' '.round($writeTook, 2));
list ($holderStatus, $holderRest) = waitCommand($holder);
check('the process that held the file ended as it should', $holderStatus === 0, (string)$holderStatus);


// Part 12. Reading since a time.
eventFolder('p12');
$now = time();
$old = keepFixture(fixture($now - 4000 * 86400, array('Summary' => 'Four thousand days ago.')));
$tenDays = keepFixture(fixture($now - 10 * 86400, array('Summary' => 'Ten days ago.')));
$threeDays = keepFixture(fixture($now - 3 * 86400, array('Summary' => 'Three days ago.', 'Severity' => 'medium')));
$dayLow = keepFixture(fixture($now - 86400, array('Summary' => 'A day ago, low.', 'Severity' => 'low')));
$dayHigh = keepFixture(fixture($now - 86400 + 5, array('Summary' => 'A day ago, high.', 'Severity' => 'high')));
$hourAgo = keepFixture(fixture($now - 3600, array('Summary' => 'An hour ago.', 'Severity' => 'critical')));
$minuteAgo = keepFixture(fixture($now - 60, array('Summary' => 'A minute ago.')));
// 3660 days is as far back as is ever looked. One event is kept half an hour past that & one half an hour short of it.
$pastTheLimit = keepFixture(fixture($now - 3660 * 86400 - 1800, array('Summary' => 'Half an hour past 3660 days ago.')));
$shortOfTheLimit = keepFixture(fixture($now - 3660 * 86400 + 1800, array('Summary' => 'Half an hour short of 3660 days ago.')));
list ($sinceRead, $sinceEvents, $sinceNotes) = readEvents($now - 2 * 86400, 'info', 100);
check('events are read across days, oldest first, & none older than asked for', $sinceRead === TRUE && summaries($sinceEvents) === array('A day ago, low.', 'A day ago, high.', 'An hour ago.', 'A minute ago.') && $sinceNotes === array(), json_encode(array(summaries($sinceEvents), $sinceNotes)));
list ($sinceRead, $sinceEvents, $sinceNotes) = readEvents($now - 2 * 86400, 'high', 100);
check('a severity leaves out what is less severe', $sinceRead === TRUE && summaries($sinceEvents) === array('A day ago, high.', 'An hour ago.') && $sinceNotes === array());
list ($sinceRead, $sinceEvents, $sinceNotes) = readEvents($now - 86400 + 5, 'info', 100);
check('an event kept at the very time asked for is read, & one five seconds older is not', summaries($sinceEvents) === array('A day ago, high.', 'An hour ago.', 'A minute ago.'));
foreach (array('nothing at all' => 0, 'a time before 1970' => -500, 'a word' => 'always') as $why => $earlyTime) {
  list ($sinceRead, $sinceEvents, $sinceNotes) = readEvents($earlyTime, 'info', 100);
  check('a time of '.$why.' looks back 3660 days & not half an hour further', $sinceRead === TRUE && summaries($sinceEvents) === array('Half an hour short of 3660 days ago.', 'Ten days ago.', 'Three days ago.', 'A day ago, low.', 'A day ago, high.', 'An hour ago.', 'A minute ago.'), json_encode(summaries($sinceEvents))); }
list ($sinceRead, $sinceEvents, $sinceNotes) = readEvents($now + 86400, 'info', 100);
check('a time that has not come yet reads nothing', $sinceRead === TRUE && $sinceEvents === array() && $sinceNotes === array());
list ($sinceRead, $sinceEvents, $sinceNotes) = readEvents(0, 'info', 2);
check('the newest are given where more matched, & a reader is told', $sinceRead === TRUE && summaries($sinceEvents) === array('An hour ago.', 'A minute ago.') && $sinceNotes === array('7 event(s) matched & the newest 2 are given.'), json_encode($sinceNotes));
foreach (array('one' => 1, 'none' => 0, 'fewer than none' => -5, 'a word' => 'many') as $why => $fewEvents) {
  list ($sinceRead, $sinceEvents, $sinceNotes) = readEvents(0, 'info', $fewEvents);
  check('asking for '.$why.' gives the newest one, & a reader is told', $sinceRead === TRUE && summaries($sinceEvents) === array('A minute ago.') && $sinceNotes === array('7 event(s) matched & the newest 1 are given.'), json_encode(array(summaries($sinceEvents), $sinceNotes))); }
foreach (array('a word that is no severity' => 'urgent', 'a capital' => 'High', 'a rank' => 3, 'no value' => NULL) as $why => $badSeverity) check('a severity given as '.$why.' reads nothing & says why', readEvents(0, $badSeverity, 100) === array(FALSE, array(), array('No event was read, because the severity asked for is not info, low, medium, high or critical.')));
$junkDay = gmdate('Ymd', $now - 86400);
writeScratchFile(dayFile($junkDay), (string)file_get_contents(dayFile($junkDay))."not an event\nnor is this\n", 0600);
list ($sinceRead, $sinceEvents, $sinceNotes) = readEvents($now - 2 * 86400, 'info', 100);
check('lines that are not events are told, & the events beside them are still read', $sinceRead === TRUE && count($sinceEvents) === 4 && $sinceNotes === array('2 line(s) of the record of '.$junkDay.' are not events & were not read.'), json_encode($sinceNotes));
$oneJunkDay = gmdate('Ymd', $now - 3 * 86400);
writeScratchFile(dayFile($oneJunkDay), (string)file_get_contents(dayFile($oneJunkDay))."not an event\n", 0600);
list ($sinceRead, $sinceEvents, $sinceNotes) = readEvents($now - 5 * 86400, 'info', 100);
check('one line that is not an event is told as one, & each day is told by itself', $sinceRead === TRUE && count($sinceEvents) === 5 && $sinceNotes === array('1 line(s) of the record of '.$oneJunkDay.' are not events & were not read.', '2 line(s) of the record of '.$junkDay.' are not events & were not read.'), json_encode($sinceNotes));
// The events are spread over two days, because one day alone never gives more than 10000 whatever is asked of it.
$crowdNow = time();
$olderText = $newerText = '';
for ($number = 1; $number <= 5001; $number++) $olderText .= lineOf(fixture($crowdNow - 86400 - 120, array('Summary' => 'Number '.$number.'.')));
for ($number = 5002; $number <= 10001; $number++) $newerText .= lineOf(fixture($crowdNow - 120, array('Summary' => 'Number '.$number.'.')));
eventFolder('p12a');
writeScratchFile(dayFile(gmdate('Ymd', $crowdNow - 86400 - 120)), $olderText, 0600);
writeScratchFile(dayFile(gmdate('Ymd', $crowdNow - 120)), $newerText, 0600);
list ($sinceRead, $sinceEvents, $sinceNotes) = readEvents($crowdNow - 2 * 86400, 'info', 20000);
check('no more than 10000 are ever given since a time, across days, & a reader is told how many matched', $sinceRead === TRUE && count($sinceEvents) === 10000 && field(field($sinceEvents, 0), 'Summary') === 'Number 2.' && field(field($sinceEvents, 9999), 'Summary') === 'Number 10001.' && $sinceNotes === array('10001 event(s) matched & the newest 10000 are given.'), count($sinceEvents).' '.json_encode($sinceNotes));
foreach (array('nothing' => '', 'the filesystem root' => '/', 'a path not named from the root' => 'Events') as $why => $badFolder) {
  $EventLoc = $badFolder;
  check('nothing is read where the folder is '.$why, readEvents(0, 'info', 100) === array(FALSE, array(), array('No event was read, because the event record has no folder.'))); }
makeFolder(scratchPath('p12n'), 0755);
$EventLoc = scratchPath('p12n/Events');
check('nothing is read where the folder does not exist, & it is said once', readEvents(0, 'info', 100) === array(FALSE, array(), array('No event was read. '.$EventLoc.' does not exist.')));

eventFolder('p12b');
keepFixture(fixture($now - 60, array('Summary' => 'A minute ago.')));
$linkedDay = gmdate('Ymd', $now - 2 * 86400);
$linkedTarget = writeScratchFile(scratchPath('p12b/target.jsonl'), lineOf(fixture($now - 2 * 86400, array('Summary' => 'Behind a link.'))), 0600);
symlink($linkedTarget, mustBeScratch(dayFile($linkedDay)));
list ($sinceRead, $sinceEvents, $sinceNotes) = readEvents($now - 5 * 86400, 'info', 100);
check('a day that could not be read is told, & the other days are still read', $sinceRead === FALSE && summaries($sinceEvents) === array('A minute ago.') && $sinceNotes === array('The record of '.$linkedDay.' could not be read.'), json_encode($sinceNotes));

eventFolder('p12c');
for ($daysBack = 1; $daysBack <= 10; $daysBack++) writeScratchFile(dayFile(gmdate('Ymd', $now - $daysBack * 86400)), lineOf(fixture($now - $daysBack * 86400, array())), 0640);
list ($sinceRead, $sinceEvents, $sinceNotes) = readEvents($now - 20 * 86400, 'info', 100);
check('ten days that could not be read are told as eight & two more', $sinceRead === FALSE && $sinceEvents === array() && count($sinceNotes) === 9 && field($sinceNotes, 8) === 'The record of 2 more day(s) could not be read.', json_encode($sinceNotes));

eventFolder('p12d');
for ($daysBack = 1; $daysBack <= 9; $daysBack++) writeScratchFile(dayFile(gmdate('Ymd', $now - $daysBack * 86400)), lineOf(fixture($now - $daysBack * 86400, array())), 0640);
list ($sinceRead, $sinceEvents, $sinceNotes) = readEvents($now - 20 * 86400, 'info', 100);
check('nine days that could not be read are told as eight & one more', $sinceRead === FALSE && count($sinceNotes) === 9 && field($sinceNotes, 0) === 'The record of '.gmdate('Ymd', $now - 9 * 86400).' could not be read.' && field($sinceNotes, 7) === 'The record of '.gmdate('Ymd', $now - 2 * 86400).' could not be read.' && field($sinceNotes, 8) === 'The record of 1 more day(s) could not be read.', json_encode($sinceNotes));

eventFolder('p12e');
for ($daysBack = 1; $daysBack <= 8; $daysBack++) writeScratchFile(dayFile(gmdate('Ymd', $now - $daysBack * 86400)), lineOf(fixture($now - $daysBack * 86400, array())), 0640);
list ($sinceRead, $sinceEvents, $sinceNotes) = readEvents($now - 20 * 86400, 'info', 100);
check('eight days that could not be read are each told, & nothing is said of more', $sinceRead === FALSE && count($sinceNotes) === 8 && field($sinceNotes, 7) === 'The record of '.gmdate('Ymd', $now - 86400).' could not be read.', json_encode($sinceNotes));


// Part 13. Finding one event.
eventFolder('p13');
awayFromMidnight();
list ($keptFirst, $identifierFirst, $noticeFirst) = recordEvent(facts(array('Summary' => 'The first.')));
list ($keptSecond, $identifierSecond, $noticeSecond) = recordEvent(facts(array('Summary' => 'The second.', 'Severity' => 'high')));
list ($keptThird, $identifierThird, $noticeThird) = recordEvent(facts(array('Summary' => 'The third.')));
list ($found, $foundRecord) = findEvent($identifierSecond);
check('an event is found by its identifier', $found === TRUE && field($foundRecord, 'Id') === $identifierSecond && field($foundRecord, 'Summary') === 'The second.' && field($foundRecord, 'Severity') === 'high');
check('an identifier nothing was given is not found', findEvent('E'.gmdate('Ymd-His').'-ffffffffffff') === array(FALSE, array()));
check('an identifier of a day with no file is not found', findEvent('E20200101-000000-0123456789ab') === array(FALSE, array()));
foreach (array('a word' => 'second', 'an identifier with a path in it' => 'E20200101-000000-../../../etc', 'no value' => NULL, 'a list' => array($identifierSecond)) as $why => $badIdentifier) check('what is '.$why.' is not looked for', findEvent($badIdentifier) === array(FALSE, array()));
$hiddenRecord = fixture(gmmktime(12, 0, 0, 2, 2, 2020), array('Summary' => 'Behind a link.'));
$hiddenTarget = writeScratchFile(scratchPath('p13/target.jsonl'), lineOf($hiddenRecord), 0600);
symlink($hiddenTarget, mustBeScratch(dayFile('20200202')));
check('an event behind a link is not found', findEvent($hiddenRecord['Id']) === array(FALSE, array()));
$earlierOfTwo = fixture(gmmktime(12, 0, 0, 3, 3, 2020), array('Summary' => 'The earlier of two.'));
$laterOfTwo = array_merge($earlierOfTwo, array('Summary' => 'The later of two.'));
writeScratchFile(dayFile('20200303'), lineOf($earlierOfTwo).lineOf($laterOfTwo), 0600);
list ($foundTwice, $recordTwice) = findEvent($earlierOfTwo['Id']);
check('where two lines hold one identifier, which the core never writes, the later is the one found', $foundTwice === TRUE && $recordTwice === $laterOfTwo);


// Part 14. A disk with no room.
makeFolder(scratchPath('p14'), 0755);
$tinyFolder = makeFolder(scratchPath('p14/tiny'), 0755);
list ($mountStatus, $mountSaid) = runCommand(array('mount', '-t', 'tmpfs', '-o', 'size=64k,mode=0755', 'tmpfs', $tinyFolder));
check('a filesystem of 64 kilobytes could be mounted for this part', $mountStatus === 0, trim($mountSaid));
if ($mountStatus === 0) {
  $EventLoc = makeFolder(scratchPath('p14/tiny/Events'), 0700);
  awayFromMidnight();
  list ($keptRoom, $identifierRoom, $noticeRoom) = recordEvent(facts(array('Summary' => 'Kept while there was room.')));
  $fillerPath = mustBeScratch(scratchPath('p14/tiny/filler'));
  $fillerHandle = fopen($fillerPath, 'w');
  $fillerChunk = str_repeat('f', 4096);
  $fillerWrote = 4096;
  for ($round = 0; $round < 64 && $fillerWrote === 4096; $round++) $fillerWrote = (int)@fwrite($fillerHandle, $fillerChunk);
  fclose($fillerHandle);
  $bytesBeforeFull = (string)file_get_contents(todayFile());
  list ($keptFull, $identifierFull, $noticeFull) = recordEvent(facts(array('Summary' => 'Too long for what is left.', 'Detail' => array('Pad' => array_fill(0, 8, str_repeat('b', 1000))))));
  check('the filesystem was filled', $keptRoom === TRUE && $fillerWrote !== 4096);
  check('an event the disk has no room for is not kept, gets no identifier & says why', $keptFull === FALSE && $identifierFull === '' && $noticeFull === 'The event record at '.todayFile().' took only part of an event. The disk may be full.', $noticeFull);
  check('the part of it that was written is taken back out', file_get_contents(todayFile()) === $bytesBeforeFull);
  list ($fullRead, $fullEvents, $fullMatched, $fullUnread) = readEventDay(gmdate('Ymd'), array(), 100);
  check('what was kept before is still read, & nothing else is there', $fullRead === TRUE && summaries($fullEvents) === array('Kept while there was room.') && $fullUnread === 0);
  list ($keptSmall, $identifierSmall, $noticeSmall) = recordEvent(facts(array('Summary' => 'Short enough for what is left.')));
  check('an event short enough for what is left is kept', $keptSmall === TRUE, $noticeSmall);
  fclose(fopen($fillerPath, 'w'));
  list ($keptLater, $identifierLater, $noticeLater) = recordEvent(facts(array('Summary' => 'Kept once there was room again.', 'Detail' => array('Pad' => array_fill(0, 8, str_repeat('b', 1000))))));
  list ($fullRead, $fullEvents, $fullMatched, $fullUnread) = readEventDay(gmdate('Ymd'), array(), 100);
  check('an event is kept again once there is room', $keptLater === TRUE && summaries($fullEvents) === array('Kept while there was room.', 'Short enough for what is left.', 'Kept once there was room again.') && $fullUnread === 0, $noticeLater);
  $EventLoc = '';
  list ($unmountStatus, $unmountSaid) = runCommand(array('umount', $tinyFolder));
  check('the filesystem of 64 kilobytes was unmounted', $unmountStatus === 0, trim($unmountSaid)); }


// Part 15. Several writers at once.
$writersFolder = eventFolder('p15');
awayFromMidnight();
$writers = array();
foreach (array('a', 'b', 'c', 'd') as $writerTag) $writers[$writerTag] = startCommand(array(PHP_BINARY, __FILE__, '--child-write', $tree, $writersFolder, '250', $writerTag));
$writersEnded = TRUE;
foreach ($writers as $writerTag => $writer) {
  list ($writerStatus, $writerSaid) = waitCommand($writer);
  if ($writerStatus !== 0 or trim($writerSaid) !== 'kept 250') $writersEnded = FALSE; }
check('four processes each kept 250 events & said so', $writersEnded === TRUE);
$writtenLines = array();
foreach (folderNames($writersFolder) as $writtenName) $writtenLines = array_merge($writtenLines, fileLines($writersFolder.'/'.$writtenName));
$everyLineWhole = TRUE;
$writtenIdentifiers = array();
$lastNumber = array('a' => 0, 'b' => 0, 'c' => 0, 'd' => 0);
$inOrder = TRUE;
foreach ($writtenLines as $writtenLine) {
  $writtenRecord = json_decode($writtenLine, TRUE);
  if (eventRecordIsValid($writtenRecord) !== TRUE) {
    $everyLineWhole = FALSE;
    continue; }
  $writtenIdentifiers[$writtenRecord['Id']] = TRUE;
  $writerTag = field($writtenRecord['Subject'], 'Writer');
  $writerNumber = field($writtenRecord['Subject'], 'Number');
  if (!is_string($writerTag) or !isset($lastNumber[$writerTag]) or $writerNumber !== $lastNumber[$writerTag] + 1) $inOrder = FALSE;
  else $lastNumber[$writerTag] = $writerNumber; }
check('the record holds 1000 lines & every one is a whole event', count($writtenLines) === 1000 && $everyLineWhole === TRUE, (string)count($writtenLines));
check('no two events were given one identifier', count($writtenIdentifiers) === 1000, (string)count($writtenIdentifiers));
check('each writer\'s events are in the order it kept them, with none missing', $inOrder === TRUE && $lastNumber === array('a' => 250, 'b' => 250, 'c' => 250, 'd' => 250), json_encode($lastNumber));


// Part 16. What was kept is what is read.
eventFolder('p16');
awayFromMidnight();
foreach (array('every byte there is' => $everyByte, 'a name of 4096 bytes no terminal prints' => str_repeat("\x01", 4096), 'a name that is not UTF-8' => "/srv/\xFF\xFE\xFD/file", 'a name with a line break & an escape in it' => "/srv/one\nERROR!!! forged line\x1B[2K") as $why => $rawName) {
  list ($keptRaw, $identifierRaw, $noticeRaw) = recordEvent(facts(array('Kind' => 'file.suspicious', 'Severity' => 'medium', 'Summary' => 'A file with an awkward name was found.', 'Subject' => array('Path' => $rawName, 'PathEncoded' => base64_encode($rawName)))));
  list ($foundRaw, $recordRaw) = findEvent($identifierRaw);
  check('an event naming '.$why.' is kept & read back', $keptRaw === TRUE && $foundRaw === TRUE, $noticeRaw);
  check('& its encoding gives the very bytes while its text is printable', base64_decode((string)field(field($recordRaw, 'Subject'), 'PathEncoded'), TRUE) === $rawName && isPrintable(field(field($recordRaw, 'Subject'), 'Path')) && field(field($recordRaw, 'Subject'), 'Path') === printableText($rawName, TRUE)); }
$scriptName = "/srv/caf\xC3\xA9/\xE6\x97\xA5\xE6\x9C\xAC.txt";
list ($keptScript, $identifierScript, $noticeScript) = recordEvent(facts(array('Kind' => 'file.suspicious', 'Severity' => 'medium', 'Summary' => 'A file with a name in another script was found.', 'Subject' => array('Path' => $scriptName, 'PathEncoded' => base64_encode($scriptName)))));
list ($foundScript, $recordScript) = findEvent($identifierScript);
check('a name in another script is kept as it is, & read back as it is', $keptScript === TRUE && $foundScript === TRUE && field(field($recordScript, 'Subject'), 'Path') === $scriptName && base64_decode((string)field(field($recordScript, 'Subject'), 'PathEncoded'), TRUE) === $scriptName, $noticeScript);
check('the file holds one line for each, & is ASCII whatever was named', count(fileLines(todayFile())) === 5 && preg_match('/^[\x20-\x7E\n]*$/D', (string)file_get_contents(todayFile())) === 1);


// Part 17. Nothing is changed & nothing is removed.
eventFolder('p17');
awayFromMidnight();
for ($number = 1; $number <= 5; $number++) recordEvent(facts(array('Summary' => 'Number '.$number.'.')));
$bytesBefore = (string)file_get_contents(todayFile());
$namesBefore = folderNames($EventLoc);
readEventDay(gmdate('Ymd'), array(), 100);
readEvents(0, 'info', 100);
readEvents(time() - 60, 'critical', 1);
list ($keptThere, $identifierThere, $noticeThere) = recordEvent(facts(array('Summary' => 'Number 6.')));
findEvent($identifierThere);
findEvent('E20200101-000000-0123456789ab');
recordEvent(facts(array('Severity' => 'urgent')));
recordEvent(facts(array('Subject' => $hugeSubject)));
$bytesAfter = (string)file_get_contents(todayFile());
check('reading changes nothing, & a later event leaves every earlier byte as it was', $keptThere === TRUE && substr($bytesAfter, 0, strlen($bytesBefore)) === $bytesBefore && count(fileLines(todayFile())) === 6);
check('nothing but the file of the day is in the folder, before or after', $namesBefore === array('Events-'.gmdate('Ymd').'.jsonl') && folderNames($EventLoc) === $namesBefore);


// Part 18. Which days a time names.
$someDay = gmmktime(0, 0, 0, 2, 28, 2024);
check('one time names the day it falls on', eventDaysBetween($someDay + 5, $someDay + 5) === array('20240228'));
check('the first second of a day names that day, & so does the last', eventDaysBetween($someDay, $someDay) === array('20240228') && eventDaysBetween($someDay + 86399, $someDay + 86399) === array('20240228'));
check('from the last second of one day to the first second of the next but one is three days, oldest first', eventDaysBetween($someDay + 86399, $someDay + 2 * 86400) === array('20240228', '20240229', '20240301'));
check('a day is named however early in it the time to end at falls', eventDaysBetween($someDay, $someDay + 2 * 86400 + 1) === array('20240228', '20240229', '20240301') && eventDaysBetween($someDay, $someDay + 86400) === array('20240228', '20240229'));
check('a time to end at that comes first names no day', eventDaysBetween($someDay + 86400, $someDay + 86399) === array() && eventDaysBetween($someDay, $someDay - 1) === array());
check('a time before 1970 is taken as the first day there was', eventDaysBetween(-500, 86400) === array('19700101', '19700102') && eventDaysBetween(-500, -400) === array());
check('what is no time at all is taken as the first day there was', eventDaysBetween('always', 'never') === array('19700101') && eventDaysBetween(NULL, 10) === array('19700101'));
$manyDays = eventDaysBetween($someDay + 100, PHP_INT_MAX);
check('no more than 3661 days are ever named, & they are the first 3661', count($manyDays) === 3661 && field($manyDays, 0) === '20240228' && field($manyDays, 3660) === gmdate('Ymd', $someDay + 3660 * 86400) && count(array_unique($manyDays)) === 3661);
$tenYears = eventDaysBetween($someDay + 43200, $someDay + 43200 + 3660 * 86400);
check('3660 days back from a time is 3661 days named, the day of each end among them', count($tenYears) === 3661 && field($tenYears, 0) === '20240228' && field($tenYears, 3660) === gmdate('Ymd', $someDay + 3660 * 86400));


// Part 19. A handle is of the file that was examined, or the file is not read. An identifier made where the host has no random bytes.
makeFolder(scratchPath('p19'), 0755);
$examinedPath = writeScratchFile(scratchPath('p19/examined.txt'), "one\n", 0600);
$anotherPath = writeScratchFile(scratchPath('p19/another.txt'), "two\n", 0600);
$elsewherePath = writeScratchFile(otherPath('p19-elsewhere.txt'), "three\n", 0600);
$examinedStatus = lstat($examinedPath);
$examinedHandle = fopen($examinedPath, 'r');
$anotherHandle = fopen($anotherPath, 'r');
$elsewhereHandle = fopen($elsewherePath, 'r');
check('the handle of a file is of that file as it was examined', eventHandleIsOfPath(fstat($examinedHandle), $examinedStatus) === TRUE);
check('the handle of another file in the same folder is not, & neither is one of a file on another filesystem', eventHandleIsOfPath(fstat($anotherHandle), $examinedStatus) === FALSE && eventHandleIsOfPath(fstat($elsewhereHandle), $examinedStatus) === FALSE);
fclose($examinedHandle);
fclose($anotherHandle);
fclose($elsewhereHandle);
$plainStatus = array('mode' => 0100600, 'dev' => 40, 'ino' => 7);
check('a plain file is the same file where its device & its inode are the same, whatever its permissions', eventHandleIsOfPath($plainStatus, $plainStatus) === TRUE && eventHandleIsOfPath(array('mode' => 0100777, 'dev' => 40, 'ino' => 7), $plainStatus) === TRUE && eventHandleIsOfPath(array('mode' => 0100001, 'dev' => 40, 'ino' => 7), $plainStatus) === TRUE);
check('another inode on the same device is another file', eventHandleIsOfPath(array('mode' => 0100600, 'dev' => 40, 'ino' => 8), $plainStatus) === FALSE);
check('the same inode on another device is another file', eventHandleIsOfPath(array('mode' => 0100600, 'dev' => 41, 'ino' => 7), $plainStatus) === FALSE);
foreach (array('a folder' => 0040700, 'a pipe' => 0010600, 'a link' => 0120777, 'a device' => 0020600, 'a socket' => 0140600) as $why => $otherMode) check('a handle of '.$why.' is not of a plain file, the same device & inode or not', eventHandleIsOfPath(array('mode' => $otherMode, 'dev' => 40, 'ino' => 7), $plainStatus) === FALSE);
check('what either call could not say is no file', eventHandleIsOfPath(FALSE, $plainStatus) === FALSE && eventHandleIsOfPath($plainStatus, FALSE) === FALSE && eventHandleIsOfPath(NULL, NULL) === FALSE && eventHandleIsOfPath(array(), array()) === FALSE);
check('& neither is one that lacks its device, its inode or what kind of thing it is', eventHandleIsOfPath($plainStatus, array('dev' => 40)) === FALSE && eventHandleIsOfPath($plainStatus, array('ino' => 7)) === FALSE && eventHandleIsOfPath(array('dev' => 40, 'ino' => 7), $plainStatus) === FALSE && eventHandleIsOfPath(array('mode' => 0100600, 'ino' => 7), $plainStatus) === FALSE && eventHandleIsOfPath(array('mode' => 0100600, 'dev' => 40), $plainStatus) === FALSE);
$lesserOne = lesserEventToken();
$lesserTwo = lesserEventToken();
check('an identifier made without random bytes ends in twelve hexadecimal digits, & no two are alike', preg_match('/^[a-f0-9]{12}$/D', $lesserOne) === 1 && preg_match('/^[a-f0-9]{12}$/D', $lesserTwo) === 1 && $lesserOne !== $lesserTwo, $lesserOne.' '.$lesserTwo);
check('& it is an identifier the core reads back', eventIdentifierIsValid('E'.gmdate('Ymd-His').'-'.$lesserOne) === TRUE);


// Part 20. A process with no file handle left. An event is not kept & a day is not read, each says so, & nothing else fails.
// Nothing is checked while no handle is left. What each call gave back is kept, the handles are let go, & then it is checked.
eventFolder('p20');
awayFromMidnight();
$today = gmdate('Ymd');
$heldFolder = $EventLoc;
list ($keptBeforeNone, $identifierBeforeNone, $noticeBeforeNone) = recordEvent(facts(array('Summary' => 'Kept while a handle was free.')));
makeFolder(scratchPath('p20b'), 0755);
$bareFolder = makeFolder(scratchPath('p20b/Events'), 0700);
$limits = function_exists('posix_getrlimit') ? posix_getrlimit() : array();
$softBefore = is_int(field($limits, 'soft openfiles')) ? field($limits, 'soft openfiles') : POSIX_RLIMIT_INFINITY;
$hardBefore = is_int(field($limits, 'hard openfiles')) ? field($limits, 'hard openfiles') : POSIX_RLIMIT_INFINITY;
$limitLowered = (function_exists('posix_setrlimit') && posix_setrlimit(POSIX_RLIMIT_NOFILE, 64, $hardBefore) === TRUE);
check('the number of files this process may hold open could be lowered for this part', $limitLowered === TRUE, json_encode($limits));
if ($limitLowered === TRUE) {
  $heldHandles = array();
  while (count($heldHandles) < 200 && ($heldHandle = @fopen('/dev/null', 'r')) !== FALSE) $heldHandles[] = $heldHandle;
  $noneLeft = (@fopen('/dev/null', 'r') === FALSE);
  $openedResult = recordEvent(facts(array('Summary' => 'Not kept, for want of a handle.')));
  $dayResult = readEventDay($today, array(), 100);
  $sinceResult = readEvents(time() - 60, 'info', 100);
  $foundResult = findEvent($identifierBeforeNone);
  $EventLoc = $bareFolder;
  $createdResult = recordEvent(facts(array('Summary' => 'Not kept either.')));
  $EventLoc = $heldFolder;
  foreach ($heldHandles as $heldHandle) fclose($heldHandle);
  $limitRestored = posix_setrlimit(POSIX_RLIMIT_NOFILE, $softBefore, $hardBefore);
  check('every handle this process may hold was taken, & the number was put back afterwards', $noneLeft === TRUE && count($heldHandles) > 0 && count($heldHandles) < 200 && $limitRestored === TRUE, (string)count($heldHandles));
  check('an event is not kept in a file that cannot be opened, & says why', $openedResult === array(FALSE, '', 'An event was not kept. '.dayFile($today).' could not be opened.'), json_encode($openedResult));
  check('nor in a file that cannot be created, & no file is left behind', $createdResult === array(FALSE, '', 'An event was not kept. '.$bareFolder.'/Events-'.$today.'.jsonl could not be created.') && folderNames($bareFolder) === array(), json_encode($createdResult));
  check('a day whose file cannot be opened was not read', $dayResult === array(FALSE, array(), 0, 0), json_encode($dayResult));
  check('reading since a time says which day could not be read', $sinceResult === array(FALSE, array(), array('The record of '.$today.' could not be read.')), json_encode($sinceResult));
  check('& an event in that day is not found', $foundResult === array(FALSE, array()));
  list ($keptAfterNone, $identifierAfterNone, $noticeAfterNone) = recordEvent(facts(array('Summary' => 'Kept once a handle was free again.')));
  list ($noneRead, $noneEvents, $noneMatched, $noneUnread) = readEventDay($today, array(), 100);
  check('once a handle is free an event is kept again, & everything that was kept is read', $keptBeforeNone === TRUE && $keptAfterNone === TRUE && $noneRead === TRUE && summaries($noneEvents) === array('Kept while a handle was free.', 'Kept once a handle was free again.') && $noneUnread === 0, $noticeAfterNone); }


// Part 21. What was logged.
check('the Event Core & what it calls logged nothing, a failed purge least of all', $TestLog === array(), json_encode(array_slice($TestLog, 0, 3)));

finish('test_events');
