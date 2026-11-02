<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRConvert2, Copyright on 9/22/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / File Information ...
// / v3.9.9.
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
// / A function to size ClamAV's limits for one path.
// / Accepts the path & the processor share the scan runs under, as a percentage of one
// / processor. Returns the size limit in megabytes, the most files one archive may hold &
// / the time limit in milliseconds, in that order.
// /
// / ClamAV's own defaults are 100M for one file, 400M for everything one file unpacks to,
// / 10000 files & 120 seconds. A file past any of them is skipped & reported clean, so
// / until v3.9.9 an upload over 100M was reported clean without ever being read.
// / The size limit is four times the file, because an archive expands, never below the
// / 400M default & never above 2047M, the largest file ClamAV can scan. It bounds one file &
// / everything unpacked from it alike. The file count & the time limit grow by the same
// / factor, so a file of 100M or less keeps the 400M, 10000 file & 120 second defaults, &
// / only the limit on one file inside it rises, from 100M to 400M.
// / The time limit also stretches by however far the processor share falls short of one
// / whole processor, because ClamAV's default assumes one, & a scan given 18% of one takes
// / more than five times as long. A share above one processor shortens nothing, because
// / ClamAV scans one file on one thread.
// / None of this decides how much memory ClamAV takes. Its signature database does, & the
// / Scan ceiling covers that. A large file adds little, because ClamAV reads it through a
// / mapping the kernel can reclaim & writes what it unpacks to the conversion location.
function clamAVLimitsFor($pathToScan, $processorShare) {
  // / Set variables.
  global $EnableMemoryProtection;
  $SizeLimit = 400;
  $FileLimit = 10000;
  $TimeLimit = 120000;
  $fileMegabytes = 0;
  $growthFactor = $shareFactor = 1;
  // / A directory has no one size to measure, so it is given the most ClamAV can scan.
  if (is_dir($pathToScan)) $SizeLimit = 2047;
  else {
    $fileMegabytes = (int)ceil((int)@filesize($pathToScan) / 1048576);
    $SizeLimit = min(2047, max(400, 4 * $fileMegabytes)); }
  $growthFactor = $SizeLimit / 400;
  if ((int)$processorShare > 0 && (int)$processorShare < 100) $shareFactor = 100 / (int)$processorShare;
  $FileLimit = (int)ceil(10000 * $growthFactor);
  $TimeLimit = (int)ceil(120000 * $growthFactor * $shareFactor);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $fileMegabytes, $growthFactor, $shareFactor, $pathToScan, $processorShare);
  return array($SizeLimit, $FileLimit, $TimeLimit); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read ClamAV's verdict from what it printed.
// / Accepts the lines ClamAV printed. Returns whether it found a threat, the limit it
// / reached, & the lines naming each threat, in that order.
// / The limit is ClamAV's own name for it, such as MaxScanSize, or empty when none was.
// /
// / A verdict is the text after the LAST colon & space on a line ending in FOUND.
// / Everything before it is the path, & the path is named by whoever uploaded the file.
// / Matching the whole line would let an upload called Heuristics.Limits.Exceeded.exe turn
// / its own detection into a limit, & a limit is not a verdict, so the file would survive.
// / A threat outranks a limit. In a scan of a folder one file can reach a limit while
// / another is infected, & that folder is infected.
function readClamAVVerdict($commandOutput) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ThreatWasFound = FALSE;
  $LimitReached = '';
  $ThreatLines = array();
  $outputLine = $verdictText = '';
  $verdictAt = FALSE;
  foreach ((array)$commandOutput as $outputLine) {
    $outputLine = rtrim((string)$outputLine);
    if (substr($outputLine, -6) !== ' FOUND') continue;
    $verdictAt = strrpos($outputLine, ': ');
    if ($verdictAt === FALSE) continue;
    $verdictText = substr($outputLine, $verdictAt + 2, -6);
    if (strpos($verdictText, 'Heuristics.Limits.Exceeded') === 0) {
      $LimitReached = ltrim(substr($verdictText, 26), '.');
      // / A limit ClamAV reports without naming is still a limit.
      if ($LimitReached === '') $LimitReached = 'Unnamed'; }
    else {
      $ThreatWasFound = TRUE;
      $ThreatLines[] = $outputLine; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $outputLine, $verdictText, $verdictAt, $commandOutput);
  return array($ThreatWasFound, $LimitReached, $ThreatLines); }
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
// / ClamAV is given the path, limits sized for it & a private folder for what it unpacks.
// / It is sandboxed like every other dependency, with the scanned path bound read only,
// / because a scanner reads & a namespace that cannot write is one less thing to reason
// / about. The only writable place it has is that folder.
// /
// / A FILE CLAMAV COULD NOT FINISH HAS NO VERDICT. It is never reported clean.
// / Without --alert-exceeds-max a file past any limit prints OK & exits 0, the same as a
// / clean file. With it ClamAV names the limit, & the scan is reported as incomplete.
function scanWithClamAV($pathsToScan) {
  // / Set variables.
  global $Verbose, $ConvertDir, $DirSep, $EnablePerConversionLimits, $EnableMemoryProtection;
  $ScanCompleted = FALSE;
  $ThreatWasFound = FALSE;
  $ScanFindings = array();
  $clamBinary = FALSE;
  $scanCommand = $pathToScan = $outputLine = $scratchDir = $scratchTemp = $skipReason = $limitReached = '';
  $commandOutput = $threatLines = $limitDescriptions = array();
  $commandExitCode = $processorShare = $sizeLimit = $fileLimit = $timeLimit = 0;
  $commandMayRun = $limitIsValid = $threatFound = FALSE;
  // / ClamAV's names for its limits, said the way an operator reads them. A name not listed
  // / here is reported as one of its limits rather than repeated, because ClamAV shares that
  // / naming with its signature database & a database is not this application's to quote.
  $limitDescriptions = array(
    'MaxFileSize' => 'its limit on the size of one file',
    'MaxScanSize' => 'its limit on how much one file may unpack to',
    'MaxFiles' => 'its limit on how many files one archive may hold',
    'MaxRecursion' => 'its limit on how deeply archives may nest',
    'MaxScanTime' => 'its time limit');
  if (!is_array($pathsToScan)) $pathsToScan = array($pathsToScan);
  $clamBinary = verifiedToolPath('ClamAV');
  if ($clamBinary === FALSE) {
    errorEntry('ClamAV is missing, too old, or unusable, so nothing was scanned!', 502, FALSE);
    $ScanFindings[] = 'ClamAV is not usable on this host. Nothing was scanned.'; }
  else {
    $ScanCompleted = TRUE;
    // / The processor share stretches the time limit. It is the same share the scan's scope
    // / is about to be given, resolved the same way, so the two cannot disagree.
    if ($EnablePerConversionLimits && function_exists('resolveConversionLimit')) list ($limitIsValid, $processorShare) = resolveConversionLimit('clamav');
    if (!$limitIsValid) $processorShare = 0;
    foreach ($pathsToScan as $pathToScan) {
      if (!file_exists($pathToScan)) continue;
      $commandOutput = $threatLines = array();
      $commandExitCode = 0;
      $scratchDir = $scratchTemp = $skipReason = $limitReached = '';
      $commandMayRun = $threatFound = FALSE;
      list ($sizeLimit, $fileLimit, $timeLimit) = clamAVLimitsFor($pathToScan, $processorShare);
      // / A file ClamAV cannot scan is refused here, before a signature database over a
      // / gigabyte is loaded only to report that.
      if (is_file($pathToScan) && (int)@filesize($pathToScan) > 2047 * 1048576) {
        $skipReason = 'ClamAV cannot scan a file as large as '.basename($pathToScan).'.';
        warningEntry('ClamAV cannot scan a file larger than 2047M, so '.basename($pathToScan).' was not scanned & has no verdict.'); }
      // / What ClamAV unpacks goes to a folder of its own in the session's conversion
      // / location, which is outside the web root & on whatever storage the administrator
      // / chose for conversions. Until v3.9.9 it went to the sandbox's /tmp, which is always
      // / memory, so a large archive was unpacked into the Scan ceiling & killed by it.
      // / The folder is also the sandbox's only writable mount. Until v3.9.9 that mount was
      // / the session's temporary location, which the web server serves to the browser.
      else if (!is_dir((string)$ConvertDir)) $skipReason = 'ClamAV had no temporary location, so '.basename($pathToScan).' was not scanned.';
      else {
        $scratchDir = rtrim((string)$ConvertDir, $DirSep).$DirSep.'clamav-'.bin2hex(random_bytes(6));
        $scratchTemp = $scratchDir.$DirSep.'tmp';
        if (!@mkdir($scratchTemp, 0700, TRUE)) $skipReason = 'ClamAV could not create a temporary folder, so '.basename($pathToScan).' was not scanned.'; }
      if ($skipReason === '') {
        // / --no-summary keeps the output to one line per file that matters.
        // / 2>&1 because a scanner reports trouble on stderr & a finding nobody sees is not
        // / a finding. Every pipeline in this application learned that the same way.
        // / --exclude-dir keeps a scan of a folder holding the temporary folder from reading
        // / ClamAV's own files while it writes & removes them.
        $scanCommand = escapeshellarg($clamBinary).' -r --no-summary --alert-exceeds-max=yes'
          .' --max-filesize='.$sizeLimit.'M --max-scansize='.$sizeLimit.'M'
          .' --max-files='.$fileLimit.' --max-scantime='.$timeLimit
          .' --tempdir='.escapeshellarg($scratchTemp)
          .' --exclude-dir='.escapeshellarg(basename($scratchDir))
          .' '.escapeshellarg($pathToScan);
        if ($Verbose) logEntry('ClamAV Limits: '.basename($pathToScan).', '.$sizeLimit.'M per file & unpacked, '.$fileLimit.' files, '.(int)ceil($timeLimit / 1000).' seconds'.(((int)$processorShare > 0 && (int)$processorShare < 100) ? ' at '.(int)$processorShare.'% of a processor' : '').'.');
        // / sandboxCommand returns whether the command may run & the command, in that order.
        // / Until v3.9.9 the pair was taken as the command, so the shell was asked to run a
        // / program called Array. It exited 127 & every file was reported clean.
        list ($commandMayRun, $scanCommand) = sandboxCommand($scanCommand, $pathToScan, $scratchTemp, FALSE, 'clamav');
        if (!$commandMayRun) $skipReason = 'ClamAV could not be sandboxed, so '.basename($pathToScan).' was not scanned.'; }
      if ($skipReason !== '') {
        $ScanCompleted = FALSE;
        $ScanFindings[] = $skipReason; }
      else {
        exec($scanCommand.' 2>&1', $commandOutput, $commandExitCode);
        list ($threatFound, $limitReached, $threatLines) = readClamAVVerdict($commandOutput);
        // / ClamAV exits 0 when it found nothing, 1 when it found something & 2 when it
        // / could not run. A status of 1 with no line to read is still a detection, because
        // / reading a verdict nobody could parse as clean is the one mistake a scanner must
        // / not make.
        if (!$threatFound && $limitReached === '' && $commandExitCode === 1) $threatFound = TRUE;
        if ($threatFound) {
          $ThreatWasFound = TRUE;
          foreach ($threatLines as $outputLine) $ScanFindings[] = trim($outputLine); }
        else if ($limitReached !== '') {
          $ScanCompleted = FALSE;
          $ScanFindings[] = 'ClamAV could not scan all of '.basename($pathToScan).' within its limits.';
          warningEntry('ClamAV reached '.(isset($limitDescriptions[$limitReached]) ? $limitDescriptions[$limitReached] : 'one of its limits').' scanning '.basename($pathToScan).', so it stopped early & reached no verdict. The limits were '.$sizeLimit.'M per file & unpacked, '.$fileLimit.' files & '.(int)ceil($timeLimit / 1000).' seconds.'.($limitReached === 'MaxScanTime' ? ' A larger processor share for Scan in --Maximum Per Conversion Resources-- gives a scan more time.' : '')); }
        // / Any other status is a scan that never finished, such as a missing program, a
        // / signal or a resource ceiling. Flattening it into a verdict loses the difference
        // / between a clean scan & a scan that never happened.
        else if ($commandExitCode !== 0) {
          $ScanCompleted = FALSE;
          $ScanFindings[] = 'ClamAV could not scan '.basename($pathToScan).'.';
          // / The status is the scanner's & names nothing a scanned file wrote, so the operator
          // / is told it. 137 is a kill, & a kill during a scan is almost always the memory
          // / ceiling, because the signature database alone is over a gigabyte once loaded.
          warningEntry('ClamAV exited '.$commandExitCode.' scanning '.basename($pathToScan).', which is not a verdict.'.($commandExitCode === 137 ? ' It was killed, most often by the memory ceiling. Raise the memory figure for Scan in --Maximum Per Conversion Resources--.' : '')); } }
      // / ClamAV removes what it unpacks when it finishes. A scan that was killed cannot, so
      // / the folder is emptied here whatever happened, & nothing a scan unpacked outlives it.
      if ($scratchDir !== '' && is_dir($scratchDir)) {
        if (function_exists('cleanFiles')) cleanFiles($scratchDir, array($scratchDir));
        @rmdir($scratchTemp);
        @rmdir($scratchDir); } }
    if ($Verbose) logEntry('ClamAV examined '.count($pathsToScan).' path(s) & reported '.count($ScanFindings).' finding(s).'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $clamBinary, $scanCommand, $pathToScan, $outputLine, $scratchDir, $scratchTemp, $skipReason, $limitReached, $commandOutput, $threatLines, $limitDescriptions, $commandExitCode, $processorShare, $sizeLimit, $fileLimit, $timeLimit, $commandMayRun, $limitIsValid, $threatFound, $pathsToScan);
  return array($ScanCompleted, $ThreatWasFound, $ScanFindings); }
// / -----------------------------------------------------------------------------------
?>
