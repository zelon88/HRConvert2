<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRProprietary Engine, Copyright on 11/16/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / File Information ...
// / v4.3.1r.
// / The Event Core, one of the Engine's cores, from v4.3.1. It was HRProtect's own until then, at Resources/Cores/eventCore.php.
// / It keeps the record of what an application did & found, one event to a line, & reads that record back.
// / It adds a capability & is never load bearing. An application does its work & reports it without it.
// / The application pins it exactly via $RequiredEventCoreVersion, & the Engine's loadEventCore() loads it through the kernel's verifyCoreComponent().
// / It decides nothing about what is an event. The application hands it the facts of one through the Engine's noteEvent(), & it keeps them.
// / It never changes an event & never removes one. It removes nothing at all.
// / It logs nothing itself. Each function returns the reason it could not do what was asked, & the Engine decides what to say.
// / Engine-35000 is raised where it is loaded directly, as it is for every Engine core, & no other error is raised here.
// / See Documentation/ABOUT_EVENTS.txt.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by an application.
// / quickDie is not available here, because a component loaded directly has no core to
// / provide it. This is the one documented exception to quickDie being the only halt.
// / It names the Engine, as every other Engine core does.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! Engine-35000, A core component cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The version of this component. Read by the application without executing this file.
$EventCoreVersion = 'v4.3.1r';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to rank a severity.
// / Accepts the name of the severity. Returns 0 for info, 1 for low, 2 for medium, 3 for high & 4 for critical.
// / Returns -1 for anything else, so a caller can refuse a severity this core does not know.
// / The names are lower case & exact, because the record is read by programs as well as by people.
function eventSeverityRank($severityName) {
  // / Set variables.
  global $EnableMemoryProtection;
  $SeverityRank = -1;
  $severityRanks = array('info' => 0, 'low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4);
  if (is_string($severityName) && isset($severityRanks[$severityName])) $SeverityRank = $severityRanks[$severityName];
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $severityRanks, $severityName);
  return $SeverityRank; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether a string may name the part of the application an event came from.
// / Accepts the name. Returns TRUE when it may.
// / A source is a lower case word, such as scan or quarantine, of at most 32 characters.
// / The same rule is asked when an event is kept & when one is read back, so the two cannot drift apart.
function eventSourceIsValid($eventSource) {
  // / Set variables.
  global $EnableMemoryProtection;
  $SourceIsValid = FALSE;
  if (is_string($eventSource) && preg_match('/^[a-z][a-z0-9-]{0,31}$/D', $eventSource) === 1) $SourceIsValid = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $eventSource);
  return $SourceIsValid; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether a string may name what kind of thing happened.
// / Accepts the name. Returns TRUE when it may.
// / A kind is two to four lower case words joined by dots, such as scan.started or file.infected.
// / The first word says what the event is about & the rest say what happened to it.
// / The same rule is asked when an event is kept & when one is read back, so the two cannot drift apart.
function eventKindIsValid($eventKind) {
  // / Set variables.
  global $EnableMemoryProtection;
  $KindIsValid = FALSE;
  if (is_string($eventKind) && preg_match('/^[a-z][a-z0-9]{0,31}(\.[a-z0-9]{1,32}){1,3}$/D', $eventKind) === 1) $KindIsValid = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $eventKind);
  return $KindIsValid; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether a string is an identifier this core gives an event.
// / Accepts the identifier. Returns TRUE when it is.
// / An identifier is the letter E, the day & the time the event was kept in UTC, & twelve hexadecimal digits.
// / It is checked rather than sanitized, because sanitize() changes what it is given & a changed identifier names another event.
function eventIdentifierIsValid($eventIdentifier) {
  // / Set variables.
  global $EnableMemoryProtection;
  $IdentifierIsValid = FALSE;
  if (is_string($eventIdentifier) && preg_match('/^E[0-9]{8}-[0-9]{6}-[a-f0-9]{12}$/D', $eventIdentifier) === 1) $IdentifierIsValid = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $eventIdentifier);
  return $IdentifierIsValid; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say where the event record is kept.
// / Accepts nothing. Returns the folder, or empty text where $EventLoc names none this core will use.
// / A folder that is not named from the filesystem root is not used, so the record is never kept wherever a command happened to be started.
// / An empty folder is not used either, because empty text followed by a file name is a name in the filesystem root.
// / This core makes no folder. The kernel names it, the Engine's loadEventCore() makes it & this core only uses it.
function eventFolderPath() {
  // / Set variables.
  global $EventLoc, $EnableMemoryProtection;
  $EventFolder = '';
  $candidateFolder = '';
  if (isset($EventLoc) && is_string($EventLoc)) $candidateFolder = rtrim($EventLoc, DIRECTORY_SEPARATOR);
  if ($candidateFolder !== '' && substr($candidateFolder, 0, 1) === DIRECTORY_SEPARATOR && strpos($candidateFolder, "\0") === FALSE) $EventFolder = $candidateFolder;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $candidateFolder);
  return $EventFolder; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the file that holds the events of one day.
// / Accepts the day as eight digits, the year first. Returns the path, or empty text where there is none.
// / A day is a day in UTC, so the name of a file does not depend on where the host says it is.
// / Anything but eight digits names no file, so nothing a caller hands over can leave the folder.
function eventDayPath($eventDay) {
  // / Set variables.
  global $EnableMemoryProtection;
  $DayPath = '';
  $eventFolder = '';
  $eventFolder = eventFolderPath();
  if ($eventFolder !== '' && is_string($eventDay) && preg_match('/^[0-9]{8}$/D', $eventDay) === 1) $DayPath = $eventFolder.DIRECTORY_SEPARATOR.'Events-'.$eventDay.'.jsonl';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $eventFolder, $eventDay);
  return $DayPath; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the days from one time to another, oldest first.
// / Accepts the time to begin at & the time to end at, in that order. Returns each day as eight digits, the year first.
// / A day begins at midnight in UTC, which is a whole number of days since the first, so the day a time falls on is found by dividing.
// / The day each time falls on is named, so the events of today are read however early today it is.
// / A time before 1970 is taken as the first day there was, because no event is older.
// / No more than 3661 days are ever named, & a time to end at that comes before the time to begin at names none.
// / It is a function of its own so that which days are read can be tested without waiting for a day to end.
function eventDaysBetween($firstStamp, $lastStamp) {
  // / Set variables.
  global $EnableMemoryProtection;
  $EventDays = array();
  $dayStamp = 0;
  $dayStamp = intdiv(max(0, (int)$firstStamp), 86400) * 86400;
  while ($dayStamp <= (int)$lastStamp && count($EventDays) < 3661) {
    $EventDays[] = gmdate('Ymd', $dayStamp);
    $dayStamp = $dayStamp + 86400; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dayStamp, $firstStamp, $lastStamp);
  return $EventDays; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to make the end of an identifier where the host has no random bytes to give.
// / Accepts nothing. Returns twelve hexadecimal digits.
// / random_bytes() throws rather than returning a poor result, & an event is still kept then.
// / An identifier need only be unlike the others & is no secret, so a lesser one is made of the process & the time.
// / It is a function of its own so that it can be tested on a host that has random bytes to give.
function lesserEventToken() {
  // / Set variables.
  $LesserToken = '';
  $LesserToken = substr(hash('sha256', uniqid((string)getmypid(), TRUE)), 0, 12);
  return $LesserToken; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether an open handle is of the plain file that was examined before it was opened.
// / Accepts what fstat() said of the handle & what lstat() said of the path, in that order. Returns TRUE where it is.
// / A file is the same file where its device & its inode are the same. A name swapped in between the two is another inode.
// / What either call could not say is no file, & neither is a handle of anything but a plain file.
// / It is a function of its own so that it can be tested without a name being swapped at the one moment that matters.
function eventHandleIsOfPath($handleStatus, $pathStatus) {
  // / Set variables.
  global $EnableMemoryProtection;
  $HandleIsOfPath = FALSE;
  if (is_array($handleStatus) && is_array($pathStatus) && isset($handleStatus['mode'], $handleStatus['dev'], $handleStatus['ino'], $pathStatus['dev'], $pathStatus['ino'])) {
    if (((int)$handleStatus['mode'] & 0170000) === 0100000 && $handleStatus['dev'] === $pathStatus['dev'] && $handleStatus['ino'] === $pathStatus['ino']) $HandleIsOfPath = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $handleStatus, $pathStatus);
  return $HandleIsOfPath; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to make one value of an event fit to keep.
// / Accepts the value, the key it is kept under & how many more levels of list may sit inside it, in that order.
// / Returns the value as it will be kept.
// / A whole number & a boolean are kept as they are.
// / Text, & a number that is not whole, is kept as the kernel's printableText() makes it, strictly.
// / A control character is kept as a question mark, as is every byte outside printable ASCII in text that is not UTF-8, & text is cut at 1000 bytes.
// / A file name is whatever bytes somebody chose, & a record a person reads must not carry them as they stand.
// / A value whose key ends in Encoded is base64. It is kept whole or not at all, because a cleaned or shortened encoding decodes to something else.
// / A list keeps what is fit among its first 64 entries. A key is a whole number or a plain name, & an entry with any other key is not kept.
// / A list deeper than allowed, & anything that is none of these, is kept as empty text.
// / So the shape of a record never depends on what a caller handed over.
function cleanEventValue($eventValue, $valueKey, $depthLeft) {
  // / Set variables.
  global $EnableMemoryProtection;
  $CleanValue = '';
  $entryKey = $entryValue = '';
  $entriesSeen = 0;
  if (is_int($eventValue) or is_bool($eventValue)) $CleanValue = $eventValue;
  else if (is_string($eventValue) && is_string($valueKey) && substr($valueKey, -7) === 'Encoded') {
    if (strlen($eventValue) <= 8192 && strlen($eventValue) % 4 === 0 && preg_match('/^[A-Za-z0-9+\/]*={0,2}$/D', $eventValue) === 1) $CleanValue = $eventValue; }
  else if (is_string($eventValue) or is_float($eventValue)) $CleanValue = printableText((string)$eventValue, TRUE);
  else if (is_array($eventValue) && (int)$depthLeft > 0) {
    $CleanValue = array();
    foreach ($eventValue as $entryKey => $entryValue) {
      $entriesSeen++;
      if ($entriesSeen > 64) break;
      if (is_int($entryKey) or preg_match('/^[A-Za-z][A-Za-z0-9]{0,31}$/D', (string)$entryKey) === 1) $CleanValue[$entryKey] = cleanEventValue($entryValue, $entryKey, (int)$depthLeft - 1); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $entryKey, $entryValue, $entriesSeen, $eventValue, $valueKey, $depthLeft);
  return $CleanValue; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether a value read from the record is one this core could have written.
// / Accepts the value & how many more levels of list may sit inside it, in that order. Returns TRUE when it is.
// / It is the other half of cleanEventValue(). That function decides what is written, & this one refuses to read anything else.
// / Text is read only where printableText() would leave it as it is, so nothing read from the record can move a cursor or forge a line on a terminal,
// / whoever wrote the file. The same function decides what is written & what is read, so the two cannot drift apart.
// / A number that is not whole & an empty value are never written, so neither is read.
// / Text is read whatever its length. An encoding is longer than any other text that is kept, & a value does not say what key it was read under.
// / No line of more than 65536 bytes is read at all, so no text is longer than that.
function eventValueIsClean($eventValue, $depthLeft) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ValueIsClean = FALSE;
  $entryKey = $entryValue = '';
  if (is_int($eventValue) or is_bool($eventValue)) $ValueIsClean = TRUE;
  else if (is_string($eventValue)) $ValueIsClean = (printableText($eventValue, FALSE) === $eventValue);
  else if (is_array($eventValue) && (int)$depthLeft > 0 && count($eventValue) <= 64) {
    $ValueIsClean = TRUE;
    foreach ($eventValue as $entryKey => $entryValue) {
      if (!is_int($entryKey) && preg_match('/^[A-Za-z][A-Za-z0-9]{0,31}$/D', (string)$entryKey) !== 1) $ValueIsClean = FALSE;
      else if (!eventValueIsClean($entryValue, (int)$depthLeft - 1)) $ValueIsClean = FALSE;
      if (!$ValueIsClean) break; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $entryKey, $entryValue, $eventValue, $depthLeft);
  return $ValueIsClean; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether a decoded line of the record is an event.
// / Accepts what the line decoded to. Returns TRUE when it is an event this core could have written.
// / An event is exactly thirteen fields, & Record says which thirteen. Record is 1 for the fields below.
// / A later version that keeps another field writes another number, & this version does not read it as something it is not.
// / recordEvent() asks this before it writes, so nothing is written that would not be read back.
function eventRecordIsValid($eventRecord) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RecordIsValid = FALSE;
  $fieldName = '';
  $textFields = array('Id', 'Time', 'Host', 'Version', 'Source', 'Kind', 'Severity', 'Summary');
  if (is_array($eventRecord) && count($eventRecord) === 13 && isset($eventRecord['Record']) && $eventRecord['Record'] === 1) {
    $RecordIsValid = TRUE;
    foreach ($textFields as $fieldName) if (!isset($eventRecord[$fieldName]) or !is_string($eventRecord[$fieldName]) or !eventValueIsClean($eventRecord[$fieldName], 0)) $RecordIsValid = FALSE;
    if ($RecordIsValid && (!eventIdentifierIsValid($eventRecord['Id']) or preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z$/D', $eventRecord['Time']) !== 1)) $RecordIsValid = FALSE;
    if ($RecordIsValid && (!isset($eventRecord['Stamp']) or !is_int($eventRecord['Stamp']) or $eventRecord['Stamp'] < 0)) $RecordIsValid = FALSE;
    if ($RecordIsValid && (!eventSourceIsValid($eventRecord['Source']) or !eventKindIsValid($eventRecord['Kind']) or eventSeverityRank($eventRecord['Severity']) < 0 or trim($eventRecord['Summary']) === '')) $RecordIsValid = FALSE;
    if ($RecordIsValid && (!isset($eventRecord['Category']) or !is_int($eventRecord['Category']) or $eventRecord['Category'] < 1 or $eventRecord['Category'] > 4)) $RecordIsValid = FALSE;
    if ($RecordIsValid && (!isset($eventRecord['Subject']) or !is_array($eventRecord['Subject']) or !eventValueIsClean($eventRecord['Subject'], 4))) $RecordIsValid = FALSE;
    if ($RecordIsValid && (!isset($eventRecord['Detail']) or !is_array($eventRecord['Detail']) or !eventValueIsClean($eventRecord['Detail'], 4))) $RecordIsValid = FALSE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $fieldName, $textFields, $eventRecord);
  return $RecordIsValid; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write an event as the line it is kept as.
// / Accepts the record of the event & whether to spread it over lines for a person to read, in that order.
// / Returns the line without its line break, or empty text where the record cannot be written as one.
// / Subject & Detail are always written as JSON objects, an empty one included.
// / PHP writes an empty list as [] & a list keyed by name as {}, & a program that reads the record should not have to expect both.
// / The objects are never held in a variable, so there is nothing here for the purge to drop a reference to.
function eventLineOf($eventRecord, $spreadOverLines) {
  // / Set variables.
  global $EnableMemoryProtection;
  $EventLine = '';
  $encodingFlags = JSON_UNESCAPED_SLASHES;
  if ($spreadOverLines === TRUE) $encodingFlags = JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
  if (is_array($eventRecord) && isset($eventRecord['Subject']) && isset($eventRecord['Detail']) && is_array($eventRecord['Subject']) && is_array($eventRecord['Detail'])) $EventLine = (string)@json_encode(array_merge($eventRecord, array('Subject' => (object)$eventRecord['Subject'], 'Detail' => (object)$eventRecord['Detail'])), $encodingFlags);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $encodingFlags, $eventRecord, $spreadOverLines);
  return $EventLine; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to keep one event.
// / Accepts the facts of the event as a list keyed by name: its Source, Kind, Category, Severity & Summary, & optionally its Subject & Detail.
// / Returns whether the event was kept, its identifier & the reason it was not kept, in that order.
// / An identifier is returned only for an event that is in the record.
// /
// / This core writes the identifier, the time, the host & the version itself, so a caller cannot get them wrong.
// / An event is one line of JSON at the end of the file of its day, & a line is at most 65536 bytes with its line break.
// / JSON writes every character outside ASCII as an escape, so the file itself is ASCII whatever an event names.
// / A Detail that would make the line longer is replaced by a note saying so, & an event still too long is not kept.
// / The file is opened by the kernel's openFileWithoutFollowing(), so a link or a second name is refused & never written through.
// / It must be root's & nobody else's to read, because an event names files & says what was found in them.
// / The lock is held for one line. The end is found again once it is held, because another process may have written since the file was opened.
// / A line break is written first where the last line has none, so an event cut short by a full disk cannot swallow the next one.
// / A line that could only be written in part is taken back out, & the caller is told.
function recordEvent($eventFacts) {
  // / Set variables.
  global $EngineApplicationVersion, $EnableMemoryProtection;
  $EventWasRecorded = FALSE;
  $EventIdentifier = $RecordNotice = '';
  $fileWasCreated = $lockIsHeld = FALSE;
  $recordHandle = $randomError = FALSE;
  $eventSummary = $randomPart = $wholeLine = $eventLine = $lineBreakFirst = $dayPath = $openNotice = $lastByte = '';
  $eventRecord = $handleStatus = $lockedStatus = array();
  $eventStamp = $sizeBefore = $bytesWritten = 0;
  $mostLineBytes = 65536;
  if (is_array($eventFacts) && isset($eventFacts['Summary']) && is_string($eventFacts['Summary'])) $eventSummary = trim(printableText($eventFacts['Summary'], TRUE));
  if (!is_array($eventFacts)) $RecordNotice = 'An event was not kept, because what was handed over is not a list of facts.';
  else if (!isset($eventFacts['Source']) or !eventSourceIsValid($eventFacts['Source'])) $RecordNotice = 'An event was not kept, because its Source is not a name the Event Core accepts.';
  else if (!isset($eventFacts['Kind']) or !eventKindIsValid($eventFacts['Kind'])) $RecordNotice = 'An event was not kept, because its Kind is not a name the Event Core accepts.';
  else if (!isset($eventFacts['Category']) or !is_int($eventFacts['Category']) or $eventFacts['Category'] < 1 or $eventFacts['Category'] > 4) $RecordNotice = 'An event was not kept, because its Category is not a whole number from 1 to 4.';
  else if (!isset($eventFacts['Severity']) or eventSeverityRank($eventFacts['Severity']) < 0) $RecordNotice = 'An event was not kept, because its Severity is not info, low, medium, high or critical.';
  else if ($eventSummary === '') $RecordNotice = 'An event was not kept, because it has no Summary.';
  else if ((isset($eventFacts['Subject']) && !is_array($eventFacts['Subject'])) or (isset($eventFacts['Detail']) && !is_array($eventFacts['Detail']))) $RecordNotice = 'An event was not kept, because its Subject or its Detail is not a list.';
  else {
    $eventStamp = time();
    // / random_bytes() throws rather than returning a poor result. An identifier need only be unlike the others, so a lesser one is made.
    try { $randomPart = bin2hex(random_bytes(6)); }
    catch (Throwable $randomError) { $randomPart = lesserEventToken(); }
    $EventIdentifier = 'E'.gmdate('Ymd-His', $eventStamp).'-'.$randomPart;
    $eventRecord = array(
      'Record' => 1,
      'Id' => $EventIdentifier,
      'Time' => gmdate('Y-m-d\TH:i:s\Z', $eventStamp),
      'Stamp' => $eventStamp,
      'Host' => cleanEventValue((string)@gethostname(), 'Host', 0),
      'Version' => cleanEventValue(isset($EngineApplicationVersion) ? (string)$EngineApplicationVersion : '', 'Version', 0),
      'Source' => $eventFacts['Source'],
      'Kind' => $eventFacts['Kind'],
      'Category' => $eventFacts['Category'],
      'Severity' => $eventFacts['Severity'],
      'Summary' => $eventSummary,
      'Subject' => isset($eventFacts['Subject']) ? cleanEventValue($eventFacts['Subject'], 'Subject', 4) : array(),
      'Detail' => isset($eventFacts['Detail']) ? cleanEventValue($eventFacts['Detail'], 'Detail', 4) : array());
    $wholeLine = eventLineOf($eventRecord, FALSE);
    // / The line that is kept is the whole one where it fits, & the one with a note in place of its Detail where it does not.
    if ($wholeLine !== '' && strlen($wholeLine) + 1 <= $mostLineBytes) $eventLine = $wholeLine;
    else {
      $eventRecord['Detail'] = array('Note' => 'The detail of this event was too large to keep.');
      $eventLine = eventLineOf($eventRecord, FALSE); }
    $dayPath = eventDayPath(gmdate('Ymd', $eventStamp));
    // / What would not be read back is refused before its size is, so a record that is wrong is never called large.
    if (!eventRecordIsValid($eventRecord)) $RecordNotice = 'An event was not kept, because the record made of it is not one the Event Core would read back.';
    else if ($eventLine === '' or strlen($eventLine) + 1 > $mostLineBytes) $RecordNotice = 'An event was not kept, because it is too large for one line of the event record.';
    else if ($dayPath === '') $RecordNotice = 'An event was not kept, because the event record has no folder.';
    else {
      list ($recordHandle, $fileWasCreated, $openNotice) = openFileWithoutFollowing($dayPath, 0600);
      if ($recordHandle === FALSE) $RecordNotice = 'An event was not kept. '.$openNotice;
      else {
        $handleStatus = @fstat($recordHandle);
        if (!is_array($handleStatus) or (int)$handleStatus['uid'] !== 0 or ((int)$handleStatus['mode'] & 0077) !== 0) $RecordNotice = 'An event was not kept, because '.$dayPath.' is not root\'s alone.';
        else if (!@flock($recordHandle, LOCK_EX)) $RecordNotice = 'An event was not kept, because '.$dayPath.' could not be locked.';
        else {
          $lockIsHeld = TRUE;
          $lockedStatus = @fstat($recordHandle);
          $sizeBefore = is_array($lockedStatus) ? (int)$lockedStatus['size'] : 0;
          if ($sizeBefore > 0) {
            // / A handle that created the file was opened for writing only & cannot read the last byte.
            // / It writes the line break regardless, & a line that holds nothing is not read as anything.
            if (!$fileWasCreated && @fseek($recordHandle, -1, SEEK_END) === 0) $lastByte = (string)@fread($recordHandle, 1);
            if ($lastByte !== "\n") $lineBreakFirst = "\n"; }
          @fseek($recordHandle, 0, SEEK_END);
          $bytesWritten = (int)@fwrite($recordHandle, $lineBreakFirst.$eventLine."\n");
          if ($bytesWritten === strlen($lineBreakFirst) + strlen($eventLine) + 1 && @fflush($recordHandle)) $EventWasRecorded = TRUE;
          else {
            @ftruncate($recordHandle, $sizeBefore);
            $RecordNotice = 'The event record at '.$dayPath.' took only part of an event. The disk may be full.'; } }
        if ($lockIsHeld) @flock($recordHandle, LOCK_UN);
        @fclose($recordHandle); } } }
  if (!$EventWasRecorded) $EventIdentifier = '';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $fileWasCreated, $lockIsHeld, $recordHandle, $randomError, $eventSummary, $randomPart, $wholeLine, $eventLine, $lineBreakFirst, $dayPath, $openNotice, $lastByte, $eventRecord, $handleStatus, $lockedStatus, $eventStamp, $sizeBefore, $bytesWritten, $mostLineBytes, $eventFacts);
  return array($EventWasRecorded, $EventIdentifier, $RecordNotice); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the events of one day.
// / Accepts the day as eight digits, what to look for & the most events to give back, in that order.
// / What to look for is a list that may hold Since, a time no event may be older than, Rank, the least severity as eventSeverityRank() ranks it, & Id, one identifier.
// / Returns whether the day was read, its events oldest first, how many matched & how many lines were not read, in that order.
// / The newest events are given where more matched than were asked for.
// /
// / A day with no file is a day on which nothing happened, & that is a day that was read.
// / The file is read only where it is a plain file with one name that is root's alone, in a folder only root could replace.
// / The open handle is compared with what was examined, by eventHandleIsOfPath(), so a name swapped in between is refused unread.
// / A line longer than any this core writes, a line that is not JSON & a line that is not an event are each counted & passed over.
// / A last line with no line break is still being written, or was cut short, & is neither read nor counted.
// / Nothing is locked, because a reader that held the file would hold up every command that had something to say.
function readEventDay($eventDay, $eventFilter, $mostEvents) {
  // / Set variables.
  global $EnableMemoryProtection;
  $DayWasRead = FALSE;
  $DayEvents = array();
  $EventsMatched = $LinesUnread = 0;
  $folderIsHeld = $lineIsWhole = FALSE;
  $dayHandle = FALSE;
  $dayPath = $holdNotice = $recordLine = $restOfLine = $wantedIdentifier = '';
  $pathStatus = $handleStatus = $eventRecord = array();
  $keepAtMost = $sinceStamp = $leastRank = 0;
  $keepAtMost = max(1, min(10000, (int)$mostEvents));
  if (is_array($eventFilter)) {
    if (isset($eventFilter['Since']) && is_int($eventFilter['Since'])) $sinceStamp = $eventFilter['Since'];
    if (isset($eventFilter['Rank']) && is_int($eventFilter['Rank'])) $leastRank = $eventFilter['Rank'];
    if (isset($eventFilter['Id']) && is_string($eventFilter['Id'])) $wantedIdentifier = $eventFilter['Id']; }
  $dayPath = eventDayPath($eventDay);
  if ($dayPath !== '') {
    list ($folderIsHeld, $holdNotice) = folderIsHeldForRoot(dirname($dayPath));
    clearstatcache(TRUE, $dayPath);
    $pathStatus = @lstat($dayPath);
    if ($folderIsHeld && !is_array($pathStatus)) $DayWasRead = TRUE;
    else if ($folderIsHeld && ((int)$pathStatus['mode'] & 0170000) === 0100000 && (int)$pathStatus['nlink'] === 1 && (int)$pathStatus['uid'] === 0 && ((int)$pathStatus['mode'] & 0077) === 0) {
      $dayHandle = @fopen($dayPath, 'rn');
      if ($dayHandle !== FALSE) {
        $handleStatus = @fstat($dayHandle);
        if (eventHandleIsOfPath($handleStatus, $pathStatus)) {
          while (($recordLine = @fgets($dayHandle, 131073)) !== FALSE) {
            $lineIsWhole = (substr($recordLine, -1) === "\n");
            if (!$lineIsWhole) {
              // / What is left of a line too long to be one of this core's is read past, & the line is counted once.
              // / Where the file ends first there was no line break at all, & what was read is the unfinished last line.
              while (!$lineIsWhole && ($restOfLine = @fgets($dayHandle, 131073)) !== FALSE) $lineIsWhole = (substr($restOfLine, -1) === "\n");
              if ($lineIsWhole) $LinesUnread++;
              continue; }
            if (strlen($recordLine) > 65536) {
              $LinesUnread++;
              continue; }
            if (trim($recordLine) === '') continue;
            $eventRecord = @json_decode($recordLine, TRUE, 8);
            if (!eventRecordIsValid($eventRecord)) {
              $LinesUnread++;
              continue; }
            if ($eventRecord['Stamp'] >= $sinceStamp && eventSeverityRank($eventRecord['Severity']) >= $leastRank && ($wantedIdentifier === '' or $eventRecord['Id'] === $wantedIdentifier)) {
              $EventsMatched++;
              $DayEvents[] = $eventRecord;
              // / Only the newest are wanted, so the oldest are let go before the list grows past twice what was asked for.
              if (count($DayEvents) >= 2 * $keepAtMost) $DayEvents = array_slice($DayEvents, -$keepAtMost); } }
          // / A read that stopped anywhere but the end of the file is a day that was not read.
          $DayWasRead = @feof($dayHandle); }
        @fclose($dayHandle); } } }
  if (count($DayEvents) > $keepAtMost) $DayEvents = array_slice($DayEvents, -$keepAtMost);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderIsHeld, $lineIsWhole, $dayHandle, $dayPath, $holdNotice, $recordLine, $restOfLine, $wantedIdentifier, $pathStatus, $handleStatus, $eventRecord, $keepAtMost, $sinceStamp, $leastRank, $eventDay, $eventFilter, $mostEvents);
  return array($DayWasRead, $DayEvents, $EventsMatched, $LinesUnread); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the events kept since a time.
// / Accepts the time no event may be older than, the least severity by name & the most events to give back, in that order.
// / Returns whether everything asked for was read, the events oldest first & what a reader should be told, in that order.
// / The newest events are given where more matched than were asked for, & at most 10000 are ever given.
// / No more than 3660 days are looked back over, so a time of nothing at all does not walk back to 1970.
// / What a reader should be told is a list of sentences: a day that could not be read, lines that were not events, & events that matched & were not given.
// / Whether the folder is one only root could replace is asked here first, so a record that cannot be read at all is said once & not once for every day.
function readEvents($sinceTime, $minimumSeverity, $mostEvents) {
  // / Set variables.
  global $EnableMemoryProtection;
  $EventsWereRead = FALSE;
  $Events = $EventNotes = array();
  $folderIsHeld = $dayWasRead = FALSE;
  $eventFolder = $holdNotice = $dayName = '';
  $dayEvents = array();
  $nowStamp = $sinceStamp = $leastRank = $keepAtMost = $eventsMatched = $dayMatched = $linesUnread = $daysUnread = 0;
  $nowStamp = time();
  $sinceStamp = max($nowStamp - (3660 * 86400), min($nowStamp, (int)$sinceTime));
  $leastRank = eventSeverityRank($minimumSeverity);
  $keepAtMost = max(1, min(10000, (int)$mostEvents));
  $eventFolder = eventFolderPath();
  if ($eventFolder !== '') list ($folderIsHeld, $holdNotice) = folderIsHeldForRoot($eventFolder);
  if ($leastRank < 0) $EventNotes[] = 'No event was read, because the severity asked for is not info, low, medium, high or critical.';
  else if ($eventFolder === '') $EventNotes[] = 'No event was read, because the event record has no folder.';
  else if (!$folderIsHeld) $EventNotes[] = 'No event was read. '.$holdNotice;
  else {
    $EventsWereRead = TRUE;
    foreach (eventDaysBetween($sinceStamp, $nowStamp) as $dayName) {
      list ($dayWasRead, $dayEvents, $dayMatched, $linesUnread) = readEventDay($dayName, array('Since' => $sinceStamp, 'Rank' => $leastRank), $keepAtMost);
      if (!$dayWasRead) {
        $EventsWereRead = FALSE;
        $daysUnread++;
        if ($daysUnread <= 8) $EventNotes[] = 'The record of '.$dayName.' could not be read.'; }
      if ($linesUnread > 0) $EventNotes[] = $linesUnread.' line(s) of the record of '.$dayName.' are not events & were not read.';
      $eventsMatched = $eventsMatched + $dayMatched;
      if (!empty($dayEvents)) $Events = array_slice(array_merge($Events, $dayEvents), -$keepAtMost); }
    if ($daysUnread > 8) $EventNotes[] = 'The record of '.($daysUnread - 8).' more day(s) could not be read.';
    if ($eventsMatched > count($Events)) $EventNotes[] = $eventsMatched.' event(s) matched & the newest '.count($Events).' are given.'; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderIsHeld, $dayWasRead, $eventFolder, $holdNotice, $dayName, $dayEvents, $nowStamp, $sinceStamp, $leastRank, $keepAtMost, $eventsMatched, $dayMatched, $linesUnread, $daysUnread, $sinceTime, $minimumSeverity, $mostEvents);
  return array($EventsWereRead, $Events, $EventNotes); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to find one event by its identifier.
// / Accepts the identifier. Returns whether the event was found & the event, in that order.
// / An identifier begins with the day its event was kept, so one file is read & no other is opened.
// / An identifier this core would not have given is not looked for.
function findEvent($eventIdentifier) {
  // / Set variables.
  global $EnableMemoryProtection;
  $EventWasFound = FALSE;
  $EventRecord = array();
  $dayWasRead = FALSE;
  $dayEvents = array();
  $eventsMatched = $linesUnread = 0;
  if (eventIdentifierIsValid($eventIdentifier)) {
    list ($dayWasRead, $dayEvents, $eventsMatched, $linesUnread) = readEventDay(substr($eventIdentifier, 1, 8), array('Id' => $eventIdentifier), 1);
    if ($dayWasRead && isset($dayEvents[0]) && is_array($dayEvents[0])) {
      $EventWasFound = TRUE;
      $EventRecord = $dayEvents[0]; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dayWasRead, $dayEvents, $eventsMatched, $linesUnread, $eventIdentifier);
  return array($EventWasFound, $EventRecord); }
// / -----------------------------------------------------------------------------------
