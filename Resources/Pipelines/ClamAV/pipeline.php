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
// / This file scans files with ClamAV & reports what it found.
// /
// / IT CHANGES NOTHING. It does not delete an infected file, quarantine it, or write to any
// / log. It returns findings & the caller decides what they mean.
// / That division matters. Deleting somebody's upload is an application policy & two
// / installations can reasonably disagree about it, so a scanner that deleted files would
// / be making a decision that was never its to make.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline cannot be loaded directly!');
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to scan paths with ClamAV.
// / Accepts a list of paths. Returns whether the scan completed, whether anything was
// / found, & the findings, in that order.
// /
// / The findings are one string per line an operator should read. The caller writes them
// / to the worker log & consolidates them for the user, so nothing here knows or cares how
// / this application presents anything.
// /
// / ClamAV is given the path & nothing else. It is sandboxed like every other dependency,
// / with the scanned path bound read only, because a scanner reads & a namespace that
// / cannot write is one less thing to reason about.
function scanWithClamAV($pathsToScan) {
  // / Set variables.
  global $MinimumClamVersion, $Verbose, $EnableMemoryProtection;
  $ScanCompleted = FALSE;
  $ThreatWasFound = FALSE;
  $ScanFindings = array();
  $clamBinary = FALSE;
  $scanCommand = $pathToScan = $outputLine = '';
  $commandOutput = array();
  $commandExitCode = 0;
  if (!is_array($pathsToScan)) $pathsToScan = array($pathsToScan);
  $clamBinary = verifyClamVersion(isset($MinimumClamVersion) ? (string)$MinimumClamVersion : '');
  if ($clamBinary === FALSE) {
    errorEntry('ClamAV is missing, too old, or unusable, so nothing was scanned!', 502, FALSE);
    $ScanFindings[] = 'ClamAV is not usable on this host. Nothing was scanned.'; }
  else {
    $ScanCompleted = TRUE;
    foreach ($pathsToScan as $pathToScan) {
      if (!file_exists($pathToScan)) continue;
      $commandOutput = array();
      $commandExitCode = 0;
      // / --no-summary keeps the output to one line per file that matters.
      // / 2>&1 because a scanner reports trouble on stderr & a finding nobody sees is not a
      // / finding. Every pipeline in this application learned that the same way.
      $scanCommand = escapeshellarg($clamBinary).' -r --no-summary '.escapeshellarg($pathToScan);
      $scanCommand = sandboxCommand($scanCommand, $pathToScan, $pathToScan, FALSE, 'clamav');
      exec($scanCommand.' 2>&1', $commandOutput, $commandExitCode);
      // / ClamAV exits 1 when it found something & 2 when it could not run. Those are
      // / different outcomes & flattening them loses the difference between a clean scan
      // / & a scan that never happened.
      if ($commandExitCode === 2) {
        $ScanCompleted = FALSE;
        $ScanFindings[] = 'ClamAV could not scan '.basename($pathToScan).'.'; }
      if ($commandExitCode === 1) $ThreatWasFound = TRUE;
      foreach ($commandOutput as $outputLine) {
        if (strpos($outputLine, 'FOUND') === FALSE) continue;
        $ScanFindings[] = trim($outputLine); } }
    if ($Verbose) logEntry('ClamAV examined '.count($pathsToScan).' path(s) & reported '.count($ScanFindings).' finding(s).'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $clamBinary, $scanCommand, $pathToScan, $outputLine, $commandOutput, $commandExitCode, $pathsToScan);
  return array($ScanCompleted, $ThreatWasFound, $ScanFindings); }
// / -----------------------------------------------------------------------------------
?>
