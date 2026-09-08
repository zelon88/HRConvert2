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
// / v3.9.3.
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
  global $InstLoc, $DirSep, $ConvertTempDir, $ScanCoreMemoryLimit, $ScanCoreChunkSize;
  global $ScanCoreVerbose, $ScanCoreDebug, $MaxLogSize, $Verbose, $EnableMemoryProtection;
  $ScanCompleted = FALSE;
  $ThreatWasFound = FALSE;
  $ScanFindings = array();
  $scanCoreFile = $scanCommand = $pathToScan = $scratchLog = $logLine = '';
  $scanVerbose = $scanDebug = '';
  $commandOutput = $logLines = array();
  $commandExitCode = 0;
  if (!is_array($pathsToScan)) $pathsToScan = array($pathsToScan);
  $scanCoreFile = $InstLoc.$DirSep.'Resources'.$DirSep.'ScanCore'.$DirSep.'ScanCore.php';
  if (!file_exists($scanCoreFile)) {
    errorEntry('ScanCore is not installed at '.$scanCoreFile.', so nothing was scanned!', 18000, FALSE);
    $ScanFindings[] = 'ScanCore is not installed. Nothing was scanned.'; }
  else {
    $ScanCompleted = TRUE;
    $scanVerbose = (isset($ScanCoreVerbose) && $ScanCoreVerbose) ? 'v' : '';
    $scanDebug = (isset($ScanCoreDebug) && $ScanCoreDebug) ? 'd' : '';
    foreach ($pathsToScan as $pathToScan) {
      if (!file_exists($pathToScan)) continue;
      // / A scratch log of ScanCore's own, read back & then removed. The findings this
      // / function returns are what the caller presents, & nothing ScanCore writes reaches
      // / a user without passing through here first.
      $scratchLog = rtrim((string)$ConvertTempDir, $DirSep).$DirSep.'scancore-'.bin2hex(random_bytes(6)).'.log';
      $commandOutput = array();
      $commandExitCode = 0;
      $scanCommand = 'php '.escapeshellarg($scanCoreFile)
        .' '.escapeshellarg($pathToScan)
        .' -m '.escapeshellarg((string)$ScanCoreMemoryLimit)
        .' -c '.escapeshellarg((string)$ScanCoreChunkSize)
        .' -lf '.escapeshellarg($scratchLog)
        .' -ml '.escapeshellarg((string)$MaxLogSize)
        .' -r'.$scanVerbose.$scanDebug;
      $scanCommand = sandboxCommand($scanCommand, $pathToScan, $scratchLog, FALSE, 'scancore');
      exec($scanCommand.' 2>&1', $commandOutput, $commandExitCode);
      // / What ScanCore said on the console, then what it wrote to its own log.
      // / Both are read, because a scanner that cannot open its log still reports on stdout
      // / & a finding nobody reads is not a finding.
      $logLines = $commandOutput;
      if (file_exists($scratchLog)) {
        $logLines = array_merge($logLines, (array)@file($scratchLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        @unlink($scratchLog); }
      foreach ($logLines as $logLine) {
        $logLine = trim((string)$logLine);
        if ($logLine === '') continue;
        if (stripos($logLine, 'INFECTED') === FALSE && stripos($logLine, 'DETECTED') === FALSE && stripos($logLine, 'FOUND') === FALSE) continue;
        $ThreatWasFound = TRUE;
        $ScanFindings[] = $logLine; } }
    if ($Verbose) logEntry('ScanCore examined '.count($pathsToScan).' path(s) & reported '.count($ScanFindings).' finding(s).'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $scanCoreFile, $scanCommand, $pathToScan, $scratchLog, $logLine, $scanVerbose, $scanDebug, $commandOutput, $logLines, $commandExitCode, $pathsToScan);
  return array($ScanCompleted, $ThreatWasFound, $ScanFindings); }
// / -----------------------------------------------------------------------------------
?>
