<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRConvert2, Copyright on 9/7/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / File Information ...
// / v4.3.1.
// / This file scans files with ScanCore & reports what it found.
// /
// / It changes nothing.
// / It does not delete, quarantine or log a finding.
// / It returns findings & a verdict for every path, infected, suspicious, incomplete or clean, with what ScanCore said about it,
// / each line with the scanned file's own name & no control character, so a caller may hand the lines on.
// / ScanCore writes a scratch log of its own, which is read back for findings.
// / It names no application. What it needs is read through the kernel's applicationSetting(), as ScannerScratchFolder,
// / ScanCoreMemoryLimit, ScanCoreChunkSize, ScanCoreVerbose & ScanCoreDebug.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline cannot be loaded directly!');
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to size ScanCore's memory for the ceiling it is about to run under.
// / Accepts nothing. Returns the memory limit & the chunk size, in bytes, in that order.
// /
// / ScanCore sets its interpreter's memory limit to the figure it is given.
// / It reads a file larger than one chunk a chunk at a time, & holds about three chunks at once.
// / The chunk is held to a quarter of the limit, which leaves room for the definitions.
// / The limit is held inside the Scan ceiling, less 64M for the interpreter.
// / ScanCore therefore stops at a limit it knows about, rather than being killed by one it does not.
// / A file smaller than a chunk is read whole & needs no more than its own size.
// / The limit & the chunk size are the application's, read as ScanCoreMemoryLimit & ScanCoreChunkSize, & 256M & 64M where it maps neither.
function scanCoreFootprint() {
  // / Set variables.
  global $EnablePerConversionLimits, $Verbose, $EnableMemoryProtection;
  $MemoryLimit = max(65536, (int)applicationSetting('ScanCoreMemoryLimit', 268435456));
  $ChunkSize = (int)applicationSetting('ScanCoreChunkSize', 67108864);
  $limitIsValid = FALSE;
  $processorShare = $memoryMegabytes = $ceilingLimit = 0;
  // / The configured pair is checked before the ceiling is applied, because only the pair
  // / is the administrator's to correct.
  if ($ChunkSize > intdiv($MemoryLimit, 4)) warningEntry('The chunk size configured for ScanCore is more than a quarter of its memory limit. ScanCore holds about three chunks at once, so a file larger than one chunk would exhaust its memory. A quarter is used instead. Set the chunk size to a quarter of the memory limit or less in config.php.');
  if ($EnablePerConversionLimits && function_exists('resolveConversionLimit')) list ($limitIsValid, $processorShare, $memoryMegabytes) = resolveConversionLimit('scancore');
  if ($limitIsValid && (int)$memoryMegabytes > 0) {
    $ceilingLimit = max(65536, ((int)$memoryMegabytes - 64) * 1048576);
    if ($ceilingLimit < $MemoryLimit) $MemoryLimit = $ceilingLimit;
    else $ceilingLimit = 0; }
  if ($ChunkSize > intdiv($MemoryLimit, 4)) $ChunkSize = intdiv($MemoryLimit, 4);
  if ($ChunkSize < 4096) $ChunkSize = 4096;
  if ($Verbose) logEntry('ScanCore Memory: '.$MemoryLimit.' bytes in chunks of '.$ChunkSize.($ceilingLimit > 0 ? ', lowered to fit the '.(int)$memoryMegabytes.'M ceiling for Scan' : '').'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $limitIsValid, $processorShare, $memoryMegabytes, $ceilingLimit);
  return array($MemoryLimit, $ChunkSize); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read what ScanCore said about one path.
// / Accepts the lines ScanCore wrote & the path it was handed. Returns the verdict & the lines a user may read, in that order.
// / The verdict is infected for an Infected line, which a hash, a definition marked high confidence or a decompression bomb earns,
// / suspicious for a Suspicious line, which any other match earns, incomplete for an Incomplete line, & clean otherwise.
// / ScanCore writes Incomplete where part of a file could not be read, such as an encrypted member, & a file it could not read
// / in full is not known to be clean. Its errors 600 & 610 are read as Incomplete too, because a read that failed partway & a file
// / skipped as unreadable were not scanned in full either. Each is logged as ERROR!!! ScanCore-600 or ScanCore-610 & names the path last,
// / so neither is handed on, & the verdict says the file was not scanned in full. A Classified line names what a file may contain,
// / executable code or a file or URL handler, & leaves the verdict as it was. Infected outranks Suspicious, & both outrank Incomplete.
// / A line's word is the first of the six it holds, & only before the first slash, because ScanCore writes it before the path, & a word
// / in a path or in a member's name, which both follow it, opens nothing.
// / ScanCore names the path it was handed, as the sandbox shows it, /in or /work & the name, or as it is where nothing sandboxes it.
// / That path becomes the scanned file's own name. A line naming any other path is not handed on, because it names nothing the caller handed over,
// / & a detection in it still counts, because ScanCore was handed this path alone.
// / The same line arrives on the console & in ScanCore's log & is kept once, & no more than 100 lines are kept for a path.
function readScanCoreLines($logLines, $pathToScan) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ScanVerdict = 'clean';
  $UserLines = array();
  $logLine = $lineWord = $candidateWord = $seenPath = $lineRemainder = $userLine = $pathName = '';
  $wordAt = $firstAt = $slashAt = FALSE;
  $seenPaths = array();
  $pathName = basename(rtrim((string)$pathToScan, '/'));
  $seenPaths = array(rtrim((string)$pathToScan, '/'), '/in/'.$pathName, '/work/'.$pathName);
  foreach ((array)$logLines as $logLine) {
    redeclare($lineWord, '');
    redeclare($firstAt, FALSE);
    foreach (array('Infected: ', 'Suspicious: ', 'Classified: ', 'Incomplete: ', 'ScanCore-600 ', 'ScanCore-610 ') as $candidateWord) {
      redeclare($wordAt, strpos((string)$logLine, $candidateWord));
      if ($wordAt !== FALSE && ($firstAt === FALSE or $wordAt < $firstAt)) list ($firstAt, $lineWord) = array($wordAt, $candidateWord); }
    redeclare($slashAt, strpos((string)$logLine, '/'));
    if ($lineWord === '' or ($slashAt !== FALSE && $slashAt < $firstAt)) continue;
    if ($lineWord === 'Infected: ') $ScanVerdict = 'infected';
    else if ($lineWord === 'Suspicious: ' && $ScanVerdict !== 'infected') $ScanVerdict = 'suspicious';
    else if (in_array($lineWord, array('Incomplete: ', 'ScanCore-600 ', 'ScanCore-610 '), TRUE) && $ScanVerdict === 'clean') $ScanVerdict = 'incomplete';
    redeclare($lineRemainder, rtrim(substr((string)$logLine, (int)$firstAt + strlen($lineWord))));
    redeclare($userLine, '');
    foreach ($seenPaths as $seenPath) if ($userLine === '' && $seenPath !== '' && strpos($lineRemainder, $seenPath) === 0 && in_array(substr($lineRemainder, strlen($seenPath), 1), array(' ', '/'), TRUE)) redeclare($userLine, printableText($lineWord.$pathName.substr($lineRemainder, strlen($seenPath)), TRUE));
    if ($userLine !== '' && !in_array($userLine, $UserLines, TRUE) && count($UserLines) < 100) $UserLines[] = $userLine; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $logLine, $lineWord, $candidateWord, $seenPath, $lineRemainder, $userLine, $pathName, $wordAt, $firstAt, $slashAt, $seenPaths, $logLines, $pathToScan);
  return array($ScanVerdict, $UserLines); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to scan paths with ScanCore.
// / Accepts a list of paths. Returns whether the scan completed, whether anything was
// / found, the findings & a verdict for each path, in that order.
// / The findings are the Infected, Suspicious & Incomplete lines, as an operator reads them. Each verdict is keyed by its path & holds Verdict,
// / infected, suspicious, incomplete or clean, & Lines, every Infected, Suspicious, Classified & Incomplete line about it.
// / A path ScanCore could not be run against is incomplete, because a path nothing scanned is not known to be clean.
// / A path that no longer exists is passed over & given no verdict, because there is nothing to say about it.
// /
// / ScanCore ships inside the application, so it needs no dependency check beyond the file existing.
// / It is still sandboxed, because it reads a file somebody else supplied.
// / Each switch is handed to ScanCore on its own, because ScanCore reads a switch whole & matches -rv to nothing.
function scanWithScanCore($pathsToScan) {
  // / Set variables.
  global $InstLoc, $DirSep, $MaxLogSize, $Verbose, $EnableMemoryProtection;
  $ScanCompleted = FALSE;
  $ThreatWasFound = FALSE;
  $ScanFindings = $ScanVerdicts = array();
  $scanCoreFile = $scanCoreDir = $scanCommand = $pathToScan = $scratchFolder = $scratchLog = $userLine = $pathVerdict = $skipReason = '';
  $scanVerbose = $scanDebug = '';
  $commandOutput = $logLines = $userLines = array();
  $commandExitCode = $memoryLimit = $chunkSize = 0;
  $commandMayRun = FALSE;
  if (!is_array($pathsToScan)) $pathsToScan = array($pathsToScan);
  $scanCoreDir = $InstLoc.$DirSep.'Resources'.$DirSep.'ScanCore';
  $scanCoreFile = $scanCoreDir.$DirSep.'ScanCore.php';
  $scratchFolder = (string)applicationSetting('ScannerScratchFolder', '');
  if (!file_exists($scanCoreFile)) {
    errorEntry('ScanCore is not installed at '.$scanCoreFile.', so nothing was scanned!', 18000, FALSE);
    $ScanFindings[] = 'ScanCore is not installed. Nothing was scanned.'; }
  else {
    $ScanCompleted = TRUE;
    $scanVerbose = applicationSetting('ScanCoreVerbose', FALSE) ? ' -v' : '';
    $scanDebug = applicationSetting('ScanCoreDebug', FALSE) ? ' -d' : '';
    // / Sized once for every path, because every path runs under the same Scan ceiling.
    list ($memoryLimit, $chunkSize) = scanCoreFootprint();
    foreach ($pathsToScan as $pathToScan) {
      if (!file_exists($pathToScan)) continue;
      $commandOutput = $logLines = $userLines = array();
      $commandExitCode = 0;
      $skipReason = $scratchLog = '';
      $pathVerdict = 'clean';
      // / A scratch log of ScanCore's own, read back & then removed. The findings this
      // / function returns are what the caller presents, & nothing ScanCore writes reaches
      // / anybody without passing through here first.
      // / Without a temporary location nothing is scanned, rather than binding whatever an
      // / empty path resolves to as the writable half of the sandbox.
      if ($scratchFolder === '' or !is_dir($scratchFolder)) $skipReason = 'ScanCore had no temporary location, so '.printableText(basename($pathToScan), TRUE).' was not scanned.';
      else {
        $scratchLog = rtrim($scratchFolder, $DirSep).$DirSep.'scancore-'.bin2hex(random_bytes(6)).'.log';
        // / ScanCore's directory is handed over as the read only resource, because the sandbox does not bind it.
        // / The escaped directory is what sandboxCommand rewrites, so the file name is appended outside it.
        // / It survives the rewrite as /res/ScanCore.php.
        $scanCommand = 'php '.escapeshellarg($scanCoreDir).$DirSep.'ScanCore.php'
          .' '.escapeshellarg($pathToScan)
          .' -m '.escapeshellarg((string)$memoryLimit)
          .' -c '.escapeshellarg((string)$chunkSize)
          .' -lf '.escapeshellarg($scratchLog)
          .' -ml '.escapeshellarg((string)$MaxLogSize)
          .' -r'.$scanVerbose.$scanDebug;
        // / sandboxCommand returns whether the command may run & the command, in that order.
        list ($commandMayRun, $scanCommand) = sandboxCommand($scanCommand, $pathToScan, $scratchLog, FALSE, 'scancore', $scanCoreDir);
        if (!$commandMayRun) $skipReason = 'ScanCore could not be sandboxed, so '.printableText(basename($pathToScan), TRUE).' was not scanned.'; }
      if ($skipReason === '') {
        // / The scanner reads nothing from whoever started the scan. At a terminal that is the terminal, which a program that
        // / parses hostile files could otherwise read from & write to.
        exec($scanCommand.' </dev/null 2>&1', $commandOutput, $commandExitCode);
        // / ScanCore exits 0 whether or not it found anything, & 1 when it could not scan. A
        // / scan that did not finish is not a verdict, & what it printed is an error rather
        // / than a finding, so nothing below reads it.
        if ($commandExitCode !== 0) {
          $skipReason = 'ScanCore could not scan '.printableText(basename($pathToScan), TRUE).'.';
          // / The status is the scanner's & names nothing a scanned file wrote, so the operator is told it.
          warningEntry('ScanCore exited '.$commandExitCode.' scanning '.printableText(basename($pathToScan), TRUE).', which is not a verdict.'.($commandExitCode === 137 ? ' It was killed, most often by the memory ceiling. Raise the memory figure for Scan in --Maximum Per Conversion Resources--.' : '')); } }
      if ($skipReason !== '') {
        $ScanCompleted = FALSE;
        $pathVerdict = 'incomplete';
        $userLines[] = $skipReason;
        $ScanFindings[] = $skipReason; }
      else {
        // / What ScanCore said on the console, then what it wrote to its own log.
        // / Both are read, because a scanner that cannot open its log still reports on stdout
        // / & a finding nobody reads is not a finding.
        $logLines = $commandOutput;
        if (file_exists($scratchLog)) $logLines = array_merge($logLines, (array)@file($scratchLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        // / ScanCore opens a line with a timestamp, then Infected for an exact match, Suspicious for a heuristic, Classified for what a file may contain
        // / or Incomplete for what it could not read. Only Infected is a threat, as ScanCore intends. Its summary, such as Found 0 infected files, is none of them.
        list ($pathVerdict, $userLines) = readScanCoreLines($logLines, $pathToScan);
        if ($pathVerdict === 'infected') $ThreatWasFound = TRUE;
        foreach ($userLines as $userLine) if (strpos($userLine, 'Classified: ') !== 0 && !in_array($userLine, $ScanFindings, TRUE)) $ScanFindings[] = $userLine; }
      if ($scratchLog !== '' && file_exists($scratchLog)) @unlink($scratchLog);
      $ScanVerdicts[$pathToScan] = array('Verdict' => $pathVerdict, 'Lines' => $userLines); }
    if ($Verbose) logEntry('ScanCore examined '.count($pathsToScan).' path(s) & reported '.count($ScanFindings).' finding(s).'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $scanCoreFile, $scanCoreDir, $scanCommand, $pathToScan, $scratchFolder, $scratchLog, $userLine, $pathVerdict, $skipReason, $scanVerbose, $scanDebug, $commandOutput, $logLines, $userLines, $commandExitCode, $commandMayRun, $memoryLimit, $chunkSize, $pathsToScan);
  return array($ScanCompleted, $ThreatWasFound, $ScanFindings, $ScanVerdicts); }
// / -----------------------------------------------------------------------------------
?>
