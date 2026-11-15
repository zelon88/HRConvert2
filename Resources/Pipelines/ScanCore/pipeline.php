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
// / v4.2.3.
// / This file scans files with ScanCore & reports what it found.
// /
// / It changes nothing.
// / It does not delete, quarantine or write to a user facing log.
// / It returns findings, & the caller decides what they mean.
// / ScanCore writes a scratch log of its own, which is read back for findings.
// / The caller decides what reaches the worker log & what reaches the user.
// / From v4.2.3 it returns a verdict for every path, infected, suspicious, incomplete or clean, with what ScanCore said about it,
// / each line with the scanned file's own name & no control character, so a caller may hand the lines to a user.
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
function scanCoreFootprint() {
  // / Set variables.
  global $ScanCoreMemoryLimit, $ScanCoreChunkSize, $EnablePerConversionLimits, $Verbose, $EnableMemoryProtection;
  $MemoryLimit = max(65536, (int)$ScanCoreMemoryLimit);
  $ChunkSize = (int)$ScanCoreChunkSize;
  $limitIsValid = FALSE;
  $processorShare = $memoryMegabytes = $ceilingLimit = 0;
  // / The configured pair is checked before the ceiling is applied, because only the pair
  // / is the administrator's to correct.
  if ($ChunkSize > intdiv($MemoryLimit, 4)) warningEntry('--User Virus Scanning ScanCore Chunk Size-- is more than a quarter of --User Virus Scanning ScanCore Memory Limit--. ScanCore holds about three chunks at once, so a file larger than one chunk would exhaust its memory. A quarter is used instead. Set the chunk size to a quarter of the memory limit or less in config.php.');
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
// / A function to make a line ScanCore wrote safe to hand a user, from v4.2.3.
// / Accepts the line. Returns it with every control character shown as a question mark, & every byte outside printable ASCII too where it is not UTF-8.
// / A Classified line quotes the name of a member of a zip the scanned file holds, which its sender chose, so a line break in one could forge a line of a report.
// / C1 control characters are replaced as well as C0 ones, because some terminals obey them as the start of a sequence. A line longer than 1000 bytes is cut where a character ends.
function printableScanCoreLine($untrustedLine) {
  // / Set variables.
  global $EnableMemoryProtection;
  $PrintableLine = '';
  $PrintableLine = (string)$untrustedLine;
  if (preg_match('//u', $PrintableLine) === 1) $PrintableLine = (string)preg_replace('/[\x00-\x1F\x7F\x{80}-\x{9F}]/u', '?', $PrintableLine);
  else $PrintableLine = (string)preg_replace('/[^\x20-\x7E]/', '?', $PrintableLine);
  if (strlen($PrintableLine) > 1000) $PrintableLine = mb_strcut($PrintableLine, 0, 1000, 'UTF-8').'...';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $untrustedLine);
  return $PrintableLine; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read what ScanCore said about one path, from v4.2.3.
// / Accepts the lines ScanCore wrote & the path it was handed. Returns the verdict & the lines a user may read, in that order.
// / The verdict is infected for an Infected line, which a hash, a definition marked high confidence or a decompression bomb earns,
// / suspicious for a Suspicious line, which any other match earns, incomplete for an Incomplete line, & clean otherwise.
// / ScanCore v1.9 writes Incomplete where part of a file could not be read, such as an encrypted member, & a file it could not read
// / in full is not known to be clean. Its errors 600 & 610 are read as Incomplete too, because a read that failed partway & a file
// / skipped as unreadable were not scanned in full either. Each is logged as ERROR!!! ScanCore-600 or ScanCore-610 & names the path last,
// / so neither is handed on, & the report says the file was not scanned in full. A Classified line names what a file may contain,
// / executable code or a file or URL handler, & leaves the verdict as it was. Infected outranks Suspicious, & both outrank Incomplete.
// / A line's word is the first of the six it holds, & only before the first slash, because ScanCore writes it before the path, & a word
// / in a path or in a member's name, which both follow it, opens nothing. Until v4.2.3 a Classified line naming a member called Infected:
// / read as a detection, & so would a file of that name in the line that names each file scanned.
// / ScanCore names the path it was handed, as the sandbox shows it, /in or /work & the name, or as it is where nothing sandboxes it.
// / That path becomes the scanned file's own name. A line naming any other path is not handed on, because it names nothing a user handed over,
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
    foreach ($seenPaths as $seenPath) if ($userLine === '' && $seenPath !== '' && strpos($lineRemainder, $seenPath) === 0 && in_array(substr($lineRemainder, strlen($seenPath), 1), array(' ', '/'), TRUE)) redeclare($userLine, printableScanCoreLine($lineWord.$pathName.substr($lineRemainder, strlen($seenPath))));
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
// / infected, suspicious, incomplete or clean, & Lines, every Infected, Suspicious, Classified & Incomplete line about it, from v4.2.3.
// /
// / ScanCore ships inside this application, so it needs no dependency check beyond the file existing.
// / It is still sandboxed, because it reads a file somebody else supplied.
function scanWithScanCore($pathsToScan) {
  // / Set variables.
  global $InstLoc, $DirSep, $ConvertTempDir;
  global $ScanCoreVerbose, $ScanCoreDebug, $MaxLogSize, $Verbose, $EnableMemoryProtection;
  $ScanCompleted = FALSE;
  $ThreatWasFound = FALSE;
  $ScanFindings = $ScanVerdicts = array();
  $scanCoreFile = $scanCoreDir = $scanCommand = $pathToScan = $scratchLog = $userLine = $pathVerdict = '';
  $scanVerbose = $scanDebug = '';
  $commandOutput = $logLines = $userLines = array();
  $commandExitCode = $memoryLimit = $chunkSize = 0;
  $commandMayRun = FALSE;
  if (!is_array($pathsToScan)) $pathsToScan = array($pathsToScan);
  $scanCoreDir = $InstLoc.$DirSep.'Resources'.$DirSep.'ScanCore';
  $scanCoreFile = $scanCoreDir.$DirSep.'ScanCore.php';
  if (!file_exists($scanCoreFile)) {
    errorEntry('ScanCore is not installed at '.$scanCoreFile.', so nothing was scanned!', 18000, FALSE);
    $ScanFindings[] = 'ScanCore is not installed. Nothing was scanned.'; }
  else {
    $ScanCompleted = TRUE;
    $scanVerbose = (isset($ScanCoreVerbose) && $ScanCoreVerbose) ? 'v' : '';
    $scanDebug = (isset($ScanCoreDebug) && $ScanCoreDebug) ? 'd' : '';
    // / Sized once for every path, because every path runs under the same Scan ceiling.
    list ($memoryLimit, $chunkSize) = scanCoreFootprint();
    foreach ($pathsToScan as $pathToScan) {
      if (!file_exists($pathToScan)) continue;
      // / A scratch log of ScanCore's own, read back & then removed. The findings this
      // / function returns are what the caller presents, & nothing ScanCore writes reaches
      // / a user without passing through here first.
      // / Without a temporary location nothing is scanned, rather than binding whatever an
      // / empty path resolves to as the writable half of the sandbox.
      if (!is_dir((string)$ConvertTempDir)) {
        $ScanCompleted = FALSE;
        $ScanFindings[] = 'ScanCore had no temporary location, so '.basename($pathToScan).' was not scanned.';
        continue; }
      $scratchLog = rtrim((string)$ConvertTempDir, $DirSep).$DirSep.'scancore-'.bin2hex(random_bytes(6)).'.log';
      $commandOutput = array();
      $commandExitCode = 0;
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
      if (!$commandMayRun) {
        $ScanCompleted = FALSE;
        $ScanFindings[] = 'ScanCore could not be sandboxed, so '.basename($pathToScan).' was not scanned.';
        continue; }
      exec($scanCommand.' 2>&1', $commandOutput, $commandExitCode);
      // / ScanCore exits 0 whether or not it found anything, & 1 when it could not scan. A
      // / scan that did not finish is not a verdict, & what it printed is an error rather
      // / than a finding, so nothing below reads it.
      if ($commandExitCode !== 0) {
        $ScanCompleted = FALSE;
        $ScanFindings[] = 'ScanCore could not scan '.basename($pathToScan).'.';
        // / The status is the scanner's & names nothing a scanned file wrote, so the operator
        // / is told it.
        warningEntry('ScanCore exited '.$commandExitCode.' scanning '.basename($pathToScan).', which is not a verdict.'.($commandExitCode === 137 ? ' It was killed, most often by the memory ceiling. Raise the memory figure for Scan in --Maximum Per Conversion Resources--.' : ''));
        if (file_exists($scratchLog)) @unlink($scratchLog);
        continue; }
      // / What ScanCore said on the console, then what it wrote to its own log.
      // / Both are read, because a scanner that cannot open its log still reports on stdout
      // / & a finding nobody reads is not a finding.
      $logLines = $commandOutput;
      if (file_exists($scratchLog)) {
        $logLines = array_merge($logLines, (array)@file($scratchLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        @unlink($scratchLog); }
      // / ScanCore opens a line with a timestamp, then Infected for an exact match, Suspicious for a heuristic, Classified for what a file may contain
      // / or Incomplete for what it could not read. Only Infected is a threat, as ScanCore intends. Its summary, such as Found 0 infected files, is none of them.
      list ($pathVerdict, $userLines) = readScanCoreLines($logLines, $pathToScan);
      $ScanVerdicts[$pathToScan] = array('Verdict' => $pathVerdict, 'Lines' => $userLines);
      if ($pathVerdict === 'infected') $ThreatWasFound = TRUE;
      foreach ($userLines as $userLine) if (strpos($userLine, 'Classified: ') !== 0 && !in_array($userLine, $ScanFindings, TRUE)) $ScanFindings[] = $userLine; }
    if ($Verbose) logEntry('ScanCore examined '.count($pathsToScan).' path(s) & reported '.count($ScanFindings).' finding(s).'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $scanCoreFile, $scanCoreDir, $scanCommand, $pathToScan, $scratchLog, $userLine, $pathVerdict, $scanVerbose, $scanDebug, $commandOutput, $logLines, $userLines, $commandExitCode, $commandMayRun, $memoryLimit, $chunkSize, $pathsToScan);
  return array($ScanCompleted, $ThreatWasFound, $ScanFindings, $ScanVerdicts); }
// / -----------------------------------------------------------------------------------
?>
