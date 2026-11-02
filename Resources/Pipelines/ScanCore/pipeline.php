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
// / v3.9.9.
// / This file scans files with ScanCore & reports what it found.
// /
// / IT CHANGES NOTHING. It does not delete an infected file, quarantine it, or write to a
// / user facing log. It returns findings & the caller decides what they mean.
// /
// / ScanCore writes its own log files & that is not the same thing as writing to this
// / application's logs. It is given a scratch log of its own, that log is read back for
// / findings, & the caller decides what reaches the worker log & what reaches the user.
// / An earlier version had ScanCore write straight into the user facing report, which meant
// / the scanner decided what a user saw & the application could not filter, order or
// / consolidate any of it.
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
// / ScanCore sets its interpreter's memory limit to the figure it is given & reads a file
// / larger than one chunk a chunk at a time to stay inside it. It holds about three chunks
// / at once, the chunk it is searching, that chunk joined to the tail of the last one, &
// / the next read, so a chunk over about a third of the limit exhausts it on the first file
// / larger than one chunk. Until v3.9.9 the configuration shipped a 256M limit with a 128M
// / chunk, & every file over 128M ended in a PHP fatal error rather than a verdict.
// / The chunk is held to a quarter of the limit, which leaves room for the definitions.
// / The limit is held inside the Scan ceiling, less 64M for the interpreter itself, so
// / ScanCore stops at a limit it knows about rather than being killed by one it does not.
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
// / A function to scan paths with ScanCore.
// / Accepts a list of paths. Returns whether the scan completed, whether anything was
// / found, & the findings, in that order.
// /
// / ScanCore ships inside this application rather than being installed on the host, so it
// / is present wherever HRConvert2 is & needs no dependency check beyond the file existing.
// / It is still run through the sandbox, because a scanner reading a file somebody else
// / supplied is exactly the case a sandbox exists for.
function scanWithScanCore($pathsToScan) {
  // / Set variables.
  global $InstLoc, $DirSep, $ConvertTempDir;
  global $ScanCoreVerbose, $ScanCoreDebug, $MaxLogSize, $Verbose, $EnableMemoryProtection;
  $ScanCompleted = FALSE;
  $ThreatWasFound = FALSE;
  $ScanFindings = array();
  $scanCoreFile = $scanCoreDir = $scanCommand = $pathToScan = $scratchLog = $logLine = '';
  $scanVerbose = $scanDebug = '';
  $commandOutput = $logLines = array();
  $commandExitCode = $memoryLimit = $chunkSize = 0;
  $commandMayRun = FALSE;
  $verdictAt = FALSE;
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
      // / ScanCore lives inside this application, which the sandbox does not bind, so its
      // / directory is handed over as the read only resource. The escaped directory is what
      // / sandboxCommand rewrites, so the file name is appended outside it & survives the
      // / rewrite as /res/ScanCore.php. Until v3.9.9 the sandbox was given no such thing &
      // / php inside it said Could not open input file.
      $scanCommand = 'php '.escapeshellarg($scanCoreDir).$DirSep.'ScanCore.php'
        .' '.escapeshellarg($pathToScan)
        .' -m '.escapeshellarg((string)$memoryLimit)
        .' -c '.escapeshellarg((string)$chunkSize)
        .' -lf '.escapeshellarg($scratchLog)
        .' -ml '.escapeshellarg((string)$MaxLogSize)
        .' -r'.$scanVerbose.$scanDebug;
      // / sandboxCommand returns whether the command may run & the command, in that order.
      // / Until v3.9.9 the pair was taken as the command, so the shell was asked to run a
      // / program called Array, said Array: not found, & the word found in that was read
      // / as a detection. Every file was reported infected.
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
      // / ScanCore opens a line with Infected for an exact match & with Suspicious for a
      // / heuristic, & a line in its log carries a timestamp first. Only Infected is a
      // / threat, which is what ScanCore intends. Its summary, such as Found 0 infected
      // / files, is neither. Until v3.9.9 any line containing found was a detection, which
      // / would have flagged every file had the command ever run.
      foreach ($logLines as $logLine) {
        $logLine = trim((string)$logLine);
        $verdictAt = strpos($logLine, 'Infected: ');
        if ($verdictAt === FALSE) $verdictAt = strpos($logLine, 'Suspicious: ');
        if ($verdictAt === FALSE) continue;
        $logLine = substr($logLine, $verdictAt);
        if (strpos($logLine, 'Infected: ') === 0) $ThreatWasFound = TRUE;
        // / The same detection arrives on the console & in the log, & is kept once.
        if (!in_array($logLine, $ScanFindings, TRUE)) $ScanFindings[] = $logLine; } }
    if ($Verbose) logEntry('ScanCore examined '.count($pathsToScan).' path(s) & reported '.count($ScanFindings).' finding(s).'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $scanCoreFile, $scanCoreDir, $scanCommand, $pathToScan, $scratchLog, $logLine, $scanVerbose, $scanDebug, $commandOutput, $logLines, $commandExitCode, $commandMayRun, $memoryLimit, $chunkSize, $verdictAt, $pathsToScan);
  return array($ScanCompleted, $ThreatWasFound, $ScanFindings); }
// / -----------------------------------------------------------------------------------
?>
