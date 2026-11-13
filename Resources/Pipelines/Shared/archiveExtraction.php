<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRConvert2, Copyright on 11/12/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / Application Information ...
// / This application is designed to provide a web-interface for converting file formats on
// / a server for users of any web browser without authentication.
// /
// / File Information ...
// / v4.1.7.
// / The archive extraction shared module, which is not a pipeline & extracts nothing on its own.
// / It holds the extraction the Archive & Extractor pipelines both need, so neither depends on the other.
// / The Archive pipeline extracts an archive to put what it holds into another, & the Extractor extracts one to give a user what it holds.
// / It is version pinned exactly as a pipeline is, by the table in Resources/Contract/app-engine-config.php.
// / It is loaded once per request, before the first pipeline that declared it.
// / Error block 39000 through 39003 belongs to this module.
// / 7-Zip comes from verifiedToolPath(), so the manifest alone owns its minimum.
// / Every extraction is listed before it is written, held to --Maximum Extracted Size-- & --Maximum Extracted Files-- while it is written, & measured once it is done.
// / A list can claim anything, so it is never the only check. What 7-Zip actually writes is measured ten times a second, & an extraction past either limit is stopped.
// / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt for the contracts this file obeys.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline component cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The version of this shared module. Read without executing this file, then matched
// / exactly against the pin in getAcceptedSharedModules().
$SharedModuleVersion = 'v4.1.7';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to read the limits every extraction is held to.
// / Accepts nothing. Returns whether config.php's values were usable, the most bytes & the most files & folders, in that order.
// / --Maximum Extracted Size-- is in megabytes & --Maximum Extracted Files-- counts files & folders together.
// / 0 means no limit for either one.
// / A setting that is missing, or is not a whole number of 0 or more, falls back to the default config.php ships, with a warning.
// / It does not fall back to no limit, because an archive extracted without limits is what these settings exist to prevent.
function extractionLimits() {
  // / Set variables.
  global $MaxExtractedSize, $MaxExtractedFiles, $EnableMemoryProtection;
  $LimitsAreConfigured = TRUE;
  $ByteLimit = $EntryLimit = 0;
  // / The defaults config.php ships, held here where configuration cannot overwrite them.
  $defaultMegabytes = 1024;
  $defaultEntries = 1000;
  $configuredMegabytes = $configuredEntries = FALSE;
  // / The largest number of megabytes a whole number of bytes can hold.
  $megabyteCeiling = intdiv(PHP_INT_MAX, 1048576);
  if (isset($MaxExtractedSize)) $configuredMegabytes = filter_var($MaxExtractedSize, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0, 'max_range' => $megabyteCeiling)));
  if (isset($MaxExtractedFiles)) $configuredEntries = filter_var($MaxExtractedFiles, FILTER_VALIDATE_INT, array('options' => array('min_range' => 0)));
  if ($configuredMegabytes === FALSE) {
    $LimitsAreConfigured = FALSE;
    $configuredMegabytes = $defaultMegabytes;
    warningEntry('--Maximum Extracted Size-- in config.php is missing or is not a whole number of 0 or more, so an extraction is held to '.$defaultMegabytes.' megabytes.'); }
  if ($configuredEntries === FALSE) {
    $LimitsAreConfigured = FALSE;
    $configuredEntries = $defaultEntries;
    warningEntry('--Maximum Extracted Files-- in config.php is missing or is not a whole number of 0 or more, so an extraction is held to '.$defaultEntries.' files & folders.'); }
  $ByteLimit = (int)$configuredMegabytes * 1048576;
  $EntryLimit = (int)$configuredEntries;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $defaultMegabytes, $defaultEntries, $configuredMegabytes, $configuredEntries, $megabyteCeiling);
  return array($LimitsAreConfigured, $ByteLimit, $EntryLimit); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to measure what an extraction has written.
// / Accepts the folder & the most files & folders it may hold, or 0 for no limit, in that order.
// / Returns whether the whole folder was read, the bytes its files hold & how many files & folders it holds, in that order.
// / Nothing is followed. A link counts as an entry & holds no bytes, because what it names is not in the folder.
// / The walk stops once the folder holds more entries than the limit, because the answer is known by then.
// / A folder it cannot read leaves the walk incomplete, & a caller treats an incomplete walk as one it cannot trust.
// / It runs ten times a second while an archive is extracted, so each entry's path, size & type are assigned rather than passed through redeclare().
// / A thousand entries cost a few milliseconds that way & ten times as many the other, & the last of each is still purged as the walk ends.
function measureExtractedFolder($folderPath, $entryLimit) {
  // / Set variables.
  global $EnableMemoryProtection;
  $WalkCompleted = TRUE;
  $FolderBytes = $FolderEntries = 0;
  $pendingFolders = $folderListing = $entryStatus = array();
  $currentFolder = $entryName = $entryPath = '';
  $entryType = 0;
  $pendingFolders = array(rtrim((string)$folderPath, DIRECTORY_SEPARATOR));
  while ($WalkCompleted && count($pendingFolders) > 0 && ((int)$entryLimit <= 0 or $FolderEntries <= (int)$entryLimit)) {
    redeclare($currentFolder, (string)array_pop($pendingFolders));
    redeclare($folderListing, @scandir($currentFolder));
    if (!is_array($folderListing)) $WalkCompleted = FALSE;
    else foreach ($folderListing as $entryName) {
      if ($entryName === '.' or $entryName === '..') continue;
      $entryPath = $currentFolder.DIRECTORY_SEPARATOR.$entryName;
      $entryStatus = @lstat($entryPath);
      // / An entry that went between the listing & the look at it is not counted, because it is not there.
      if (!is_array($entryStatus)) continue;
      $FolderEntries++;
      $entryType = (int)$entryStatus['mode'] & 0170000;
      if ($entryType === 0040000) $pendingFolders[] = $entryPath;
      else if ($entryType === 0100000) $FolderBytes = $FolderBytes + (int)$entryStatus['size'];
      if ((int)$entryLimit > 0 && $FolderEntries > (int)$entryLimit) break; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $pendingFolders, $folderListing, $entryStatus, $currentFolder, $entryName, $entryPath, $entryType, $folderPath, $entryLimit);
  return array($WalkCompleted, $FolderBytes, $FolderEntries); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to stop a command this module started, & every process it started.
// / Accepts the process proc_open() returned. Returns how many processes were signalled.
// / proc_open() runs a command through a shell, & the shell this host has does not replace itself with the command.
// / Killing the shell alone leaves the command running, adopted by the system, long after it was meant to stop.
// / Every process below the shell is found through /proc & killed, the deepest first so none is left to carry on, & then the shell is.
// / A sandboxed command dies with its parent in any case, & an unsandboxed one, which an administrator may allow, would not.
function stopArchiveToolTree($toolProcess) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ProcessesSignalled = 0;
  $processStatus = $childrenOf = $statusMatches = $pendingPids = $treePids = array();
  $statPath = $statContents = '';
  $rootPid = $currentPid = $processId = 0;
  $processStatus = is_resource($toolProcess) ? proc_get_status($toolProcess) : array();
  $rootPid = (int)($processStatus['pid'] ?? 0);
  if ($rootPid > 1 && !empty($processStatus['running'])) {
    foreach ((array)glob('/proc/[0-9]*/stat') as $statPath) {
      $statContents = (string)@file_get_contents($statPath);
      // / A process's name sits in brackets & may hold anything, so its parent is read after the last bracket.
      if (preg_match('/^([0-9]+) \(.*\) \S+ ([0-9]+) /s', $statContents, $statusMatches) === 1) $childrenOf[(int)$statusMatches[2]][] = (int)$statusMatches[1]; }
    $pendingPids = array($rootPid);
    while (count($pendingPids) > 0) {
      $currentPid = (int)array_shift($pendingPids);
      $treePids[] = $currentPid;
      if (isset($childrenOf[$currentPid])) foreach ($childrenOf[$currentPid] as $processId) $pendingPids[] = $processId; }
    foreach (array_reverse($treePids) as $processId) {
      if ($processId <= 1 or $processId === getmypid()) continue;
      if (function_exists('posix_kill')) @posix_kill($processId, 9);
      else exec('kill -9 '.(int)$processId.' 2>/dev/null');
      $ProcessesSignalled++; } }
  // / The shell is signalled through proc_terminate() too, which is all it needs if it already ended.
  if (is_resource($toolProcess)) proc_terminate($toolProcess, 9);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $processStatus, $childrenOf, $statusMatches, $pendingPids, $treePids, $statPath, $statContents, $rootPid, $currentPid, $processId, $toolProcess);
  return $ProcessesSignalled; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run 7-Zip's listing of an archive & read what it prints, within a size & a time.
// / Accepts the command, the most bytes to read & the most seconds to wait, in that order.
// / Returns whether it ran, what it printed, why it was cut short & its exit status, in that order.
// / It is cut short as size when it prints more than the most bytes, & as time when it outlasts the most seconds, & is killed either way.
// / Standard error is read with standard output, because 7-Zip names a password it needs there.
// / Nothing is written to standard input, so a question 7-Zip asks reads an empty answer rather than waiting.
function runArchiveListing($commandToRun, $outputLimit, $secondsLimit) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ListingRan = FALSE;
  $ListingOutput = $CutReason = '';
  $ExitStatus = -1;
  $listingProcess = FALSE;
  $listingPipes = $readablePipes = array();
  $writablePipes = $exceptionalPipes = NULL;
  $outputChunk = '';
  $deadline = 0.0;
  $pipesReady = 0;
  // / Standard error is joined to standard output by the descriptor, so it covers the whole command whatever its shape.
  $listingProcess = @proc_open($commandToRun, array(0 => array('file', '/dev/null', 'r'), 1 => array('pipe', 'w'), 2 => array('redirect', 1)), $listingPipes);
  if (is_resource($listingProcess) && isset($listingPipes[1])) {
    $ListingRan = TRUE;
    stream_set_blocking($listingPipes[1], FALSE);
    $deadline = microtime(TRUE) + (float)$secondsLimit;
    while ($CutReason === '' && !feof($listingPipes[1])) {
      redeclare($readablePipes, array($listingPipes[1]));
      $pipesReady = (int)@stream_select($readablePipes, $writablePipes, $exceptionalPipes, 1, 0);
      if ($pipesReady > 0) {
        redeclare($outputChunk, (string)fread($listingPipes[1], 65536));
        redeclare($ListingOutput, $ListingOutput.$outputChunk); }
      if (strlen($ListingOutput) > (int)$outputLimit) $CutReason = 'size';
      else if (microtime(TRUE) > $deadline) $CutReason = 'time'; }
    // / 7-Zip & everything it started is stopped, not only the shell that started it.
    if ($CutReason !== '') stopArchiveToolTree($listingProcess);
    fclose($listingPipes[1]);
    $ExitStatus = proc_close($listingProcess); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $listingProcess, $listingPipes, $readablePipes, $writablePipes, $exceptionalPipes, $outputChunk, $deadline, $pipesReady, $commandToRun, $outputLimit, $secondsLimit);
  return array($ListingRan, $ListingOutput, $CutReason, $ExitStatus); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run 7-Zip while it extracts, & stop it the moment it writes more than it may.
// / Accepts the command, the file its standard output is written to or an empty path, the folder it extracts into or an empty path,
// / the most bytes, the most files & folders & the most seconds, in that order. A limit of 0 is no limit.
// / Returns whether it ran, its exit status, why it was stopped & what it printed, in that order.
// / It is stopped as bytes, entries or time when it passes that limit, & as folder when a file it is written to cannot be.
// / With a file named, 7-Zip writes one archived file to its standard output, & every byte is counted here as it is written to that file.
// / Without one, 7-Zip writes into the folder, & the folder is measured ten times a second.
// / A stopped extraction leaves part of what it wrote behind, & the caller removes it.
// / What 7-Zip prints is kept to 64 KiB, because it repeats names an archive chose & an archive may choose a great many.
function watchArchiveExtraction($commandToRun, $streamPath, $watchFolder, $byteLimit, $entryLimit, $secondsLimit) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ToolRan = FALSE;
  $ExitStatus = -1;
  $StopReason = $ToolMessages = '';
  $toolProcess = $streamHandle = $pipesReady = FALSE;
  $toolPipes = $readablePipes = array();
  $writablePipes = $exceptionalPipes = $readyPipe = NULL;
  $outputChunk = '';
  $streamedBytes = $folderBytes = $folderEntries = 0;
  $deadline = $nextMeasure = 0.0;
  $streamIsWanted = $walkCompleted = FALSE;
  $messageLimit = 65536;
  $streamIsWanted = ((string)$streamPath !== '');
  // / The file a stream is written to must be new, so a name taken in the meantime is refused rather than written over.
  if ($streamIsWanted) $streamHandle = @fopen((string)$streamPath, 'xb');
  if ($streamIsWanted && $streamHandle === FALSE) $StopReason = 'folder';
  else {
    $toolProcess = @proc_open($commandToRun, array(0 => array('file', '/dev/null', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $toolPipes);
    if (!is_resource($toolProcess) or !isset($toolPipes[1], $toolPipes[2])) $StopReason = 'process';
    else {
      $ToolRan = TRUE;
      stream_set_blocking($toolPipes[1], FALSE);
      stream_set_blocking($toolPipes[2], FALSE);
      $deadline = microtime(TRUE) + (float)$secondsLimit;
      while ($StopReason === '' && (!feof($toolPipes[1]) or !feof($toolPipes[2]))) {
        redeclare($readablePipes, array());
        if (!feof($toolPipes[1])) $readablePipes[] = $toolPipes[1];
        if (!feof($toolPipes[2])) $readablePipes[] = $toolPipes[2];
        // / A tenth of a second is the longest anything waits, so the folder is measured on time whether 7-Zip prints or not.
        $pipesReady = @stream_select($readablePipes, $writablePipes, $exceptionalPipes, 0, 100000);
        if ($pipesReady === FALSE) usleep(100000);
        else if ($pipesReady > 0) foreach ($readablePipes as $readyPipe) {
          redeclare($outputChunk, (string)fread($readyPipe, 65536));
          if ($streamIsWanted && $readyPipe === $toolPipes[1]) {
            $streamedBytes = $streamedBytes + strlen($outputChunk);
            if ((int)$byteLimit > 0 && $streamedBytes > (int)$byteLimit) $StopReason = 'bytes';
            else if ($outputChunk !== '' && @fwrite($streamHandle, $outputChunk) !== strlen($outputChunk)) $StopReason = 'folder'; }
          else if (strlen($ToolMessages) < $messageLimit) redeclare($ToolMessages, $ToolMessages.substr($outputChunk, 0, $messageLimit - strlen($ToolMessages)));
          if ($StopReason !== '') break; }
        if ($StopReason === '' && !$streamIsWanted && (string)$watchFolder !== '' && microtime(TRUE) >= $nextMeasure) {
          list ($walkCompleted, $folderBytes, $folderEntries) = measureExtractedFolder($watchFolder, $entryLimit);
          if ((int)$entryLimit > 0 && $folderEntries > (int)$entryLimit) $StopReason = 'entries';
          else if ((int)$byteLimit > 0 && $folderBytes > (int)$byteLimit) $StopReason = 'bytes';
          $nextMeasure = microtime(TRUE) + 0.1; }
        if ($StopReason === '' && microtime(TRUE) > $deadline) $StopReason = 'time'; }
      // / 7-Zip & everything it started is stopped, not only the shell that started it.
      if ($StopReason !== '') stopArchiveToolTree($toolProcess);
      fclose($toolPipes[1]);
      fclose($toolPipes[2]);
      $ExitStatus = proc_close($toolProcess); } }
  if ($streamHandle !== FALSE) fclose($streamHandle);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $toolProcess, $streamHandle, $pipesReady, $toolPipes, $readablePipes, $writablePipes, $exceptionalPipes, $readyPipe, $outputChunk, $streamedBytes, $folderBytes, $folderEntries, $deadline, $nextMeasure, $streamIsWanted, $walkCompleted, $messageLimit, $commandToRun, $streamPath, $watchFolder, $byteLimit, $entryLimit, $secondsLimit);
  return array($ToolRan, $ExitStatus, $StopReason, $ToolMessages); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read what 7-Zip's technical listing says about an archive.
// / Accepts what 7-Zip printed for l -slt. Returns whether it reads as a listing & what it says, in that order.
// / What it says is Type, Files, Folders, Entries, DeclaredBytes, SizesAreKnown & Encrypted.
// / Type is the archive's format in lower case, as 7-Zip names it, such as zip, tar or gzip.
// / Every folder a file sits in is a folder an extraction makes, so Folders counts each one once whether the archive lists it or not.
// / Entries is Files & Folders together, which is what --Maximum Extracted Files-- counts.
// / DeclaredBytes is what the archive says its files hold, & SizesAreKnown is FALSE when any file says nothing, as bzip2 never does.
// / A name in an archive may hold anything, so nothing read here is trusted to be the whole truth, & every limit is enforced again as the archive is written.
function parseArchiveListing($listingText) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ListingIsUsable = FALSE;
  $ArchiveFacts = array('Type' => '', 'Files' => 0, 'Folders' => 0, 'Entries' => 0, 'DeclaredBytes' => 0, 'SizesAreKnown' => TRUE, 'Encrypted' => FALSE);
  $listingLines = $entryFields = $folderSet = $pathParts = $lineMatches = array();
  $listingLine = $pathPart = $folderPath = '';
  $separatorWasSeen = $entryIsFolder = FALSE;
  $partIndex = $partsToCount = $entrySize = 0;
  $listingLines = preg_split('/\r?\n/', (string)$listingText);
  if (!is_array($listingLines)) $listingLines = array();
  // / A blank line is added at the end, so the last entry is recorded as every other one is, at the blank line that ends it.
  $listingLines[] = '';
  foreach ($listingLines as $listingLine) {
    // / A line of ten dashes parts what 7-Zip says about the archive from what it says about each entry in it.
    if (!$separatorWasSeen) {
      if ($ArchiveFacts['Type'] === '' && preg_match('/^Type = (.+)$/', $listingLine, $lineMatches) === 1) $ArchiveFacts['Type'] = strtolower(trim($lineMatches[1]));
      if (trim($listingLine) === '----------') $separatorWasSeen = TRUE;
      continue; }
    if (trim($listingLine) !== '') {
      if (preg_match('/^([A-Za-z][A-Za-z0-9 ]*?) = ?(.*)$/', $listingLine, $lineMatches) === 1) $entryFields[$lineMatches[1]] = $lineMatches[2];
      continue; }
    // / A blank line ends an entry, & the entry is counted there.
    if (empty($entryFields)) continue;
    $entryIsFolder = (isset($entryFields['Folder']) && trim((string)$entryFields['Folder']) === '+');
    redeclare($pathParts, array());
    foreach (explode('/', str_replace('\\', '/', (string)($entryFields['Path'] ?? ''))) as $pathPart) if ($pathPart !== '' && $pathPart !== '.' && $pathPart !== '..') $pathParts[] = $pathPart;
    // / A folder's own path is a folder, & a file's path names the folders it sits in & then the file.
    redeclare($partsToCount, $entryIsFolder ? count($pathParts) : count($pathParts) - 1);
    redeclare($folderPath, '');
    for ($partIndex = 0; $partIndex < $partsToCount; $partIndex++) {
      redeclare($folderPath, $folderPath.'/'.$pathParts[$partIndex]);
      $folderSet[$folderPath] = TRUE; }
    if (!$entryIsFolder) {
      $ArchiveFacts['Files']++;
      // / A size too large to hold reads as the largest that can be held, so a sum of them stays a whole number that is too large.
      if (isset($entryFields['Size']) && preg_match('/^[0-9]+$/', trim((string)$entryFields['Size'])) === 1) {
        redeclare($entrySize, (int)trim((string)$entryFields['Size']));
        $ArchiveFacts['DeclaredBytes'] = ($ArchiveFacts['DeclaredBytes'] > PHP_INT_MAX - $entrySize) ? PHP_INT_MAX : $ArchiveFacts['DeclaredBytes'] + $entrySize; }
      else $ArchiveFacts['SizesAreKnown'] = FALSE; }
    if (isset($entryFields['Encrypted']) && trim((string)$entryFields['Encrypted']) === '+') $ArchiveFacts['Encrypted'] = TRUE;
    redeclare($entryFields, array()); }
  $ArchiveFacts['Folders'] = count($folderSet);
  $ArchiveFacts['Entries'] = $ArchiveFacts['Files'] + $ArchiveFacts['Folders'];
  $ListingIsUsable = ($separatorWasSeen && $ArchiveFacts['Type'] !== '');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $listingLines, $entryFields, $folderSet, $pathParts, $lineMatches, $listingLine, $pathPart, $folderPath, $separatorWasSeen, $entryIsFolder, $partIndex, $partsToCount, $entrySize, $listingText);
  return array($ListingIsUsable, $ArchiveFacts); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to ask 7-Zip what an archive holds, inside the sandbox.
// / Accepts the 7-Zip binary, the archive & an empty folder the sandbox may mount for it, in that order.
// / Returns whether the archive was listed, what the listing says & why it was not, in that order.
// / Why it was not is sandbox, process, listing, time, encrypted or unreadable. Nothing is logged here, so a caller asking whether a file is an archive at all is not warned about one that is not.
// / A password is never asked for. -p- gives 7-Zip one that is wrong, so an archive whose names are encrypted fails to list rather than waiting.
// / A listing longer than 16 MiB, or one taking longer than five minutes, is not read.
function listArchiveContents($sevenZipBinary, $archivePath, $scratchFolder) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ListingWasRead = FALSE;
  $ArchiveFacts = array();
  $ListingRefusal = '';
  $listCommand = $listingOutput = $cutReason = '';
  $commandMayRun = $listingRan = $listingIsUsable = FALSE;
  $exitStatus = -1;
  $listCommand = escapeshellarg((string)$sevenZipBinary).' l -slt -p- '.escapeshellarg((string)$archivePath);
  // / The folder holding the archive is mounted read only, & the scratch folder is the one place 7-Zip could write, which a listing never does.
  list ($commandMayRun, $listCommand) = sandboxCommand($listCommand, (string)$archivePath, rtrim((string)$scratchFolder, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'.', FALSE, 'archive');
  if (!$commandMayRun) $ListingRefusal = 'sandbox';
  else {
    list ($listingRan, $listingOutput, $cutReason, $exitStatus) = runArchiveListing($listCommand, 16777216, 300);
    if (!$listingRan) $ListingRefusal = 'process';
    else if ($cutReason === 'size') $ListingRefusal = 'listing';
    else if ($cutReason === 'time') $ListingRefusal = 'time';
    else {
      list ($listingIsUsable, $ArchiveFacts) = parseArchiveListing($listingOutput);
      // / An archive whose names are encrypted cannot be listed, & 7-Zip says so in words.
      if ($exitStatus !== 0 && stripos($listingOutput, 'encrypted') !== FALSE) $ListingRefusal = 'encrypted';
      else if ($exitStatus !== 0 or !$listingIsUsable) $ListingRefusal = 'unreadable';
      else $ListingWasRead = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $listCommand, $listingOutput, $cutReason, $commandMayRun, $listingRan, $listingIsUsable, $exitStatus, $sevenZipBinary, $archivePath, $scratchFolder);
  return array($ListingWasRead, $ArchiveFacts, $ListingRefusal); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether what an archive says it holds may be extracted.
// / Accepts the archive's name, what its listing says, the most bytes & the most files & folders, in that order.
// / Returns whether it may & why it may not, which is encrypted, empty, entries or bytes, in that order.
// / A refusal is a warning, because the archive was handled exactly as it should be.
// / Sizes an archive does not declare are not judged here, & are held to the limit as they are written.
function judgeArchiveFacts($archiveName, $archiveFacts, $byteLimit, $entryLimit) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FactsAreAcceptable = FALSE;
  $RefusalReason = '';
  if (!empty($archiveFacts['Encrypted'])) {
    $RefusalReason = 'encrypted';
    warningEntry('The archive '.$archiveName.' is protected by a password, so nothing was extracted from it.'); }
  else if ((int)($archiveFacts['Files'] ?? 0) === 0) {
    $RefusalReason = 'empty';
    warningEntry('The archive '.$archiveName.' holds no files, so nothing was extracted from it.'); }
  else if ((int)$entryLimit > 0 && (int)$archiveFacts['Entries'] > (int)$entryLimit) {
    $RefusalReason = 'entries';
    warningEntry('The archive '.$archiveName.' holds '.(int)$archiveFacts['Entries'].' files & folders, more than the '.(int)$entryLimit.' --Maximum Extracted Files-- allows, so nothing was extracted from it.'); }
  else if ((int)$byteLimit > 0 && !empty($archiveFacts['SizesAreKnown']) && $archiveFacts['DeclaredBytes'] > (int)$byteLimit) {
    $RefusalReason = 'bytes';
    warningEntry('The archive '.$archiveName.' holds '.describeByteCount($archiveFacts['DeclaredBytes']).', more than the '.describeByteCount($byteLimit).' --Maximum Extracted Size-- allows, so nothing was extracted from it.'); }
  else $FactsAreAcceptable = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $archiveName, $archiveFacts, $byteLimit, $entryLimit);
  return array($FactsAreAcceptable, $RefusalReason); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to ask whether the disk holding this session can take what an archive says it holds.
// / Accepts the archive's name & what its listing says, in that order.
// / Returns whether it can & why it cannot, which is storage, in that order.
// / An extraction writes as an upload does, so it asks the listener as an upload does, & is refused when the disk would be left within --Storage Reserve--.
// / What an archive does not declare is asked for as nothing, so a disk already within its reserve still refuses it.
// / With no Engine there is no listener to ask, & with no listener the request fails open, as requestStorageSpace() says.
function requestExtractionSpace($archiveName, $archiveFacts) {
  // / Set variables.
  global $ConvertLoc, $EnableMemoryProtection;
  $StorageWasGranted = TRUE;
  $RefusalReason = $storageReason = '';
  $requestedBytes = 0;
  if (!empty($archiveFacts['SizesAreKnown'])) $requestedBytes = max(0, (int)$archiveFacts['DeclaredBytes']);
  if (function_exists('requestStorageSpace')) list ($StorageWasGranted, $storageReason) = requestStorageSpace((string)$ConvertLoc, $requestedBytes);
  if (!$StorageWasGranted) {
    $RefusalReason = 'storage';
    warningEntry('The archive '.$archiveName.' was not extracted, because the disk holding this session cannot take it. '.$storageReason); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $storageReason, $requestedBytes, $archiveName, $archiveFacts);
  return array($StorageWasGranted, $RefusalReason); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to warn that an extraction was stopped, & say why in words an administrator can act on.
// / Accepts the archive's name, why it stopped, the most bytes & the most files & folders, in that order.
// / Returns TRUE when it wrote the warning, as warningEntry() does. It is the one place these words are written, so every extraction that stops says the same thing.
// / Why it stopped is bytes, entries, time or failed, & any other reason is the caller's to report, so FALSE means it wrote nothing.
function warnExtractionStopped($archiveName, $stopReason, $byteLimit, $entryLimit) {
  // / Set variables.
  global $EnableMemoryProtection;
  $WarningWasWritten = FALSE;
  if ($stopReason === 'bytes') $WarningWasWritten = warningEntry('The archive '.$archiveName.' grew past the '.describeByteCount($byteLimit).' --Maximum Extracted Size-- allows as it was extracted, so it was stopped & nothing it wrote was kept.');
  else if ($stopReason === 'entries') $WarningWasWritten = warningEntry('The archive '.$archiveName.' made more than the '.(int)$entryLimit.' files & folders --Maximum Extracted Files-- allows as it was extracted, so it was stopped & nothing it wrote was kept.');
  else if ($stopReason === 'time') $WarningWasWritten = warningEntry('The archive '.$archiveName.' was still being extracted after an hour, so it was stopped & nothing it wrote was kept.');
  else if ($stopReason === 'failed') $WarningWasWritten = warningEntry('7-Zip reported a problem with the archive '.$archiveName.', such as a damaged file or a link leading out of it, so nothing it wrote was kept.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $archiveName, $stopReason, $byteLimit, $entryLimit);
  return $WarningWasWritten; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to extract every file an archive holds into a folder, inside the sandbox, within the limits.
// / Accepts the 7-Zip binary, the archive, the folder to extract into, the most bytes & the most files & folders, in that order.
// / Returns whether everything was extracted & why it was not, in that order.
// / Why it was not is sandbox, process, bytes, entries, time or failed, & each is reported here.
// / Only the folder is mounted writable, & the folder holding the archive is mounted read only.
// / The folder is named followed by a dot, so the sandbox mounts that folder & not the one holding it.
// / A name taken twice in the archive is extracted twice, the second under a new name, because -aou keeps both.
// / -p- gives 7-Zip a wrong password, so an encrypted file fails rather than waiting for somebody to type one.
// / Anything but a clean exit is a refusal. 7-Zip exits 2 for a damaged file, a wrong password & a link leading out of the archive alike.
function extractArchiveItems($sevenZipBinary, $archivePath, $contentsFolder, $byteLimit, $entryLimit) {
  // / Set variables.
  global $Verbose, $Lol, $EnableMemoryProtection;
  $ItemsWereExtracted = FALSE;
  $RefusalReason = '';
  $extractTarget = $extractCommand = $stopReason = $toolMessages = '';
  $commandMayRun = $toolRan = FALSE;
  $exitStatus = -1;
  $extractTarget = rtrim((string)$contentsFolder, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'.';
  $extractCommand = escapeshellarg((string)$sevenZipBinary).' x -aou -p- -y -bd '.escapeshellarg((string)$archivePath).' -o'.escapeshellarg($extractTarget);
  list ($commandMayRun, $extractCommand) = sandboxCommand($extractCommand, (string)$archivePath, $extractTarget, FALSE, 'archive');
  if (!$commandMayRun) {
    $RefusalReason = 'sandbox';
    errorEntry('Bubblewrap is missing or non functional, so '.basename((string)$archivePath).' could not be extracted in isolation!', 39001, FALSE); }
  else {
    list ($toolRan, $exitStatus, $stopReason, $toolMessages) = watchArchiveExtraction($extractCommand, '', (string)$contentsFolder, $byteLimit, $entryLimit, 3600);
    if ($Verbose && trim($toolMessages) !== '') logEntry('7-Zip returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', trim((string)preg_replace('/[\x00-\x09\x0B-\x1F\x7F]+/', ' ', $toolMessages))));
    if (!$toolRan) {
      $RefusalReason = 'process';
      errorEntry('7-Zip could not be started to extract '.basename((string)$archivePath).'. PHP may have proc_open disabled!', 39002, FALSE); }
    else if ($stopReason !== '') $RefusalReason = $stopReason;
    else if ($exitStatus !== 0) $RefusalReason = 'failed';
    else $ItemsWereExtracted = TRUE;
    if ($RefusalReason !== '' && $RefusalReason !== 'process') warnExtractionStopped(basename((string)$archivePath), $RefusalReason, $byteLimit, $entryLimit); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $extractTarget, $extractCommand, $stopReason, $toolMessages, $commandMayRun, $toolRan, $exitStatus, $sevenZipBinary, $archivePath, $contentsFolder, $byteLimit, $entryLimit);
  return array($ItemsWereExtracted, $RefusalReason); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decompress an archive that holds one stream, such as a gzip, into a file, inside the sandbox.
// / Accepts the 7-Zip binary, the archive, the file to write & the most bytes it may hold, in that order.
// / Returns whether it was decompressed & why it was not, in that order.
// / Why it was not is sandbox, process, folder, bytes, time or failed, & each is reported here.
// / 7-Zip writes the stream to its standard output, & this application writes it to the file & counts every byte as it does.
// / A gzip names its size in four bytes that wrap at four gigabytes, & a bzip2 names none, so the count here is the only one that can be trusted.
// / The folder the file is written in is mounted for 7-Zip & never written by it.
function streamArchiveMember($sevenZipBinary, $archivePath, $streamPath, $byteLimit) {
  // / Set variables.
  global $Verbose, $Lol, $EnableMemoryProtection;
  $MemberWasStreamed = FALSE;
  $RefusalReason = '';
  $streamCommand = $stopReason = $toolMessages = '';
  $commandMayRun = $toolRan = FALSE;
  $exitStatus = -1;
  $streamCommand = escapeshellarg((string)$sevenZipBinary).' x -so -p- -bd '.escapeshellarg((string)$archivePath);
  list ($commandMayRun, $streamCommand) = sandboxCommand($streamCommand, (string)$archivePath, dirname((string)$streamPath).DIRECTORY_SEPARATOR.'.', FALSE, 'archive');
  if (!$commandMayRun) {
    $RefusalReason = 'sandbox';
    errorEntry('Bubblewrap is missing or non functional, so '.basename((string)$archivePath).' could not be extracted in isolation!', 39001, FALSE); }
  else {
    list ($toolRan, $exitStatus, $stopReason, $toolMessages) = watchArchiveExtraction($streamCommand, (string)$streamPath, '', $byteLimit, 0, 3600);
    if ($Verbose && trim($toolMessages) !== '') logEntry('7-Zip returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', trim((string)preg_replace('/[\x00-\x09\x0B-\x1F\x7F]+/', ' ', $toolMessages))));
    if ($stopReason === 'folder') {
      $RefusalReason = 'folder';
      errorEntry('A file could not be written while '.basename((string)$archivePath).' was extracted, so nothing was extracted from it!', 39003, FALSE); }
    else if (!$toolRan) {
      $RefusalReason = 'process';
      errorEntry('7-Zip could not be started to extract '.basename((string)$archivePath).'. PHP may have proc_open disabled!', 39002, FALSE); }
    else {
      if ($stopReason !== '') $RefusalReason = $stopReason;
      else if ($exitStatus !== 0) $RefusalReason = 'failed';
      else $MemberWasStreamed = TRUE;
      if ($RefusalReason !== '') warnExtractionStopped(basename((string)$archivePath), $RefusalReason, $byteLimit, 0); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $streamCommand, $stopReason, $toolMessages, $commandMayRun, $toolRan, $exitStatus, $sevenZipBinary, $archivePath, $streamPath, $byteLimit);
  return array($MemberWasStreamed, $RefusalReason); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to extract an archive into a folder, within the limits, inside the sandbox.
// / Accepts the archive, the empty folder its contents go into, a folder this may make to unwrap a compressed tar,
// / the most bytes & the most files & folders, in that order. extractionLimits() supplies the last two, & 0 is no limit.
// / Returns whether the archive was extracted, why it was not, the bytes its contents hold & how many files & folders they are, in that order.
// / Why it was not is tool, folder, sandbox, process, listing, time, encrypted, unreadable, empty, entries, bytes, storage or failed, & every one is logged.
// / The archive is listed first & refused before anything is written when it is encrypted, empty, or says it holds more than the limits allow.
// / A format holding one compressed stream, such as gzip, is decompressed into the unwrap folder, & a tar it held is extracted from there.
// / A .tar.gz therefore gives up the files the tar holds, not the tar.
// / Anything else compressed alone is the one file the archive holds, named as the archive is without its last extension.
// / Every other format is extracted into the folder directly, while the folder is measured.
// / The contents are measured once more at the end, & held to the same limits.
// / The unwrap folder is removed before this returns. The contents folder is the caller's, & is left for the caller to read & remove, whatever happened.
function extractArchiveContained($archivePath, $contentsFolder, $unwrapFolder, $byteLimit, $entryLimit) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ExtractionSucceeded = FALSE;
  $RefusalReason = '';
  $ExtractedBytes = $ExtractedEntries = 0;
  $sevenZipBinary = FALSE;
  $archiveName = $streamName = $streamPath = $probeRefusal = '';
  $archiveFacts = $streamFacts = array();
  $listingWasRead = $factsAreAcceptable = $storageWasGranted = $memberWasStreamed = $streamWasListed = $itemsWereExtracted = $walkCompleted = FALSE;
  // / The formats that hold one compressed stream & no names, as 7-Zip names them.
  $singleStreamTypes = array('gzip', 'bzip2', 'xz', 'lzma', 'lzma86', 'zstd', 'z', 'lz', 'lz4', 'lzip');
  $archiveName = basename((string)$archivePath);
  $sevenZipBinary = verifiedToolPath('7-Zip');
  if ($sevenZipBinary === FALSE) {
    $RefusalReason = 'tool';
    errorEntry('The installed 7-Zip version is missing, unidentifiable, or too old, so '.$archiveName.' was not extracted!', 39000, FALSE); }
  else if (!is_dir((string)$contentsFolder) or is_link((string)$contentsFolder) or !is_dir_empty((string)$contentsFolder) or file_exists((string)$unwrapFolder) or is_link((string)$unwrapFolder)) {
    $RefusalReason = 'folder';
    errorEntry('The folder '.$archiveName.' was to be extracted into was not an empty folder of its own, so nothing was extracted!', 39003, FALSE); }
  else {
    list ($listingWasRead, $archiveFacts, $RefusalReason) = listArchiveContents($sevenZipBinary, (string)$archivePath, (string)$contentsFolder);
    if ($RefusalReason === 'sandbox') errorEntry('Bubblewrap is missing or non functional, so '.$archiveName.' could not be extracted in isolation!', 39001, FALSE);
    else if ($RefusalReason === 'process') errorEntry('7-Zip could not be started to extract '.$archiveName.'. PHP may have proc_open disabled!', 39002, FALSE);
    else if ($RefusalReason === 'encrypted') warningEntry('The archive '.$archiveName.' is protected by a password, so nothing was extracted from it.');
    else if ($RefusalReason === 'listing') warningEntry('The archive '.$archiveName.' lists more than this server reads, so nothing was extracted from it.');
    else if ($RefusalReason === 'time') warningEntry('The archive '.$archiveName.' could not be listed within five minutes, so nothing was extracted from it.');
    else if ($RefusalReason !== '') warningEntry('7-Zip could not read '.$archiveName.' as an archive, so nothing was extracted from it.');
    if ($listingWasRead) list ($factsAreAcceptable, $RefusalReason) = judgeArchiveFacts($archiveName, $archiveFacts, $byteLimit, $entryLimit);
    if ($factsAreAcceptable) list ($storageWasGranted, $RefusalReason) = requestExtractionSpace($archiveName, $archiveFacts);
    // / A compressed stream is decompressed whole before anything is read from it.
    if ($storageWasGranted && in_array((string)$archiveFacts['Type'], $singleStreamTypes, TRUE)) {
      $streamName = sanitizeString((string)pathinfo($archiveName, PATHINFO_FILENAME), TRUE);
      if ($streamName === '') $streamName = 'extracted';
      if (!@mkdir((string)$unwrapFolder, 0700)) {
        $RefusalReason = 'folder';
        errorEntry('A folder could not be made to decompress '.$archiveName.', so nothing was extracted from it!', 39003, FALSE); }
      else {
        $streamPath = rtrim((string)$unwrapFolder, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$streamName;
        list ($memberWasStreamed, $RefusalReason) = streamArchiveMember($sevenZipBinary, (string)$archivePath, $streamPath, $byteLimit);
        // / What came out is asked whether it is a tar. A file that is not an archive at all simply fails to list.
        if ($memberWasStreamed) list ($streamWasListed, $streamFacts, $probeRefusal) = listArchiveContents($sevenZipBinary, $streamPath, (string)$contentsFolder);
        if ($memberWasStreamed && $streamWasListed && (string)$streamFacts['Type'] === 'tar') {
          list ($factsAreAcceptable, $RefusalReason) = judgeArchiveFacts($streamName, $streamFacts, $byteLimit, $entryLimit);
          $storageWasGranted = FALSE;
          if ($factsAreAcceptable) list ($storageWasGranted, $RefusalReason) = requestExtractionSpace($streamName, $streamFacts);
          if ($storageWasGranted) list ($itemsWereExtracted, $RefusalReason) = extractArchiveItems($sevenZipBinary, $streamPath, (string)$contentsFolder, $byteLimit, $entryLimit); }
        // / Anything else compressed alone is the one file the archive holds.
        else if ($memberWasStreamed && @rename($streamPath, rtrim((string)$contentsFolder, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$streamName)) $itemsWereExtracted = TRUE;
        else if ($memberWasStreamed) {
          $RefusalReason = 'folder';
          errorEntry('The file decompressed from '.$archiveName.' could not be moved beside what else it held, so nothing was extracted from it!', 39003, FALSE); }
        // / What was decompressed goes now, whatever became of it, because the tar a .tar.gz held is as large as everything it holds.
        clearstatcache(TRUE, $streamPath);
        if (file_exists($streamPath) or is_link($streamPath)) @unlink($streamPath);
        @rmdir((string)$unwrapFolder); } }
    else if ($storageWasGranted) list ($itemsWereExtracted, $RefusalReason) = extractArchiveItems($sevenZipBinary, (string)$archivePath, (string)$contentsFolder, $byteLimit, $entryLimit);
    // / What was extracted is measured once more, because a list can claim anything & the watch looks only ten times a second.
    if ($itemsWereExtracted) {
      list ($walkCompleted, $ExtractedBytes, $ExtractedEntries) = measureExtractedFolder((string)$contentsFolder, 0);
      if (!$walkCompleted) {
        $RefusalReason = 'unreadable';
        warningEntry('What 7-Zip extracted from '.$archiveName.' could not all be read back, so none of it was kept.'); }
      else if ((int)$entryLimit > 0 && $ExtractedEntries > (int)$entryLimit) {
        $RefusalReason = 'entries';
        warnExtractionStopped($archiveName, 'entries', $byteLimit, $entryLimit); }
      else if ((int)$byteLimit > 0 && $ExtractedBytes > (int)$byteLimit) {
        $RefusalReason = 'bytes';
        warnExtractionStopped($archiveName, 'bytes', $byteLimit, $entryLimit); }
      else if ($ExtractedEntries === 0) {
        $RefusalReason = 'empty';
        warningEntry('The archive '.$archiveName.' held nothing 7-Zip could extract, so nothing was extracted from it.'); }
      else $ExtractionSucceeded = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $sevenZipBinary, $archiveName, $streamName, $streamPath, $probeRefusal, $archiveFacts, $streamFacts, $listingWasRead, $factsAreAcceptable, $storageWasGranted, $memberWasStreamed, $streamWasListed, $itemsWereExtracted, $walkCompleted, $singleStreamTypes, $archivePath, $contentsFolder, $unwrapFolder, $byteLimit, $entryLimit);
  return array($ExtractionSucceeded, $RefusalReason, $ExtractedBytes, $ExtractedEntries); }
// / -----------------------------------------------------------------------------------
?>
