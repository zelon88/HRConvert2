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
// / v4.1.6.
// / The Scan pipeline. pipelineCore.php loads it only to dispatch a Scan operation.
// / Error blocks 16000 through 16003, 17000 & 17001, 19000 & 19001, & 20000 & 20001 belong to this pipeline.
// / Scan is an operation pipeline. It takes the files a user asked to have scanned & changes none of them.
// / It scans each one with ClamAV, ScanCore or both, through runVirusScan(), & writes the report a user downloads.
// / It returns three values, the third being whether any file was found infected & where the report is.
// / A pipeline never prints. See contract 6.
// / It is dispatched by runOperationPipeline('Scan'), never through convert().
// / Until v4.1.6 this was userVirusScan() & its helpers in convertCore.php, & every error number they raised came with them.
// / Each scanner keeps a log in the session's folder, & the report joins them in the session's temporary folder.
// / Their paths are worked out here from the session's folders, & each helper is handed the path it writes.
// / verifyFile() is the core's.
// / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt for the contracts this file obeys.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline component cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to start a fresh log for each scanner a scan will run.
// / Accepts the scan type, which is clamav, scancore or all, & the paths of the ClamAV & ScanCore logs, in that order.
// / Returns TRUE when every log the scan needs was started.
// / A scan with every scanner resets both logs, so its report carries no earlier scan's lines.
function verifyUserVirusLogs($type, $clamLogPath, $scanCoreLogPath) {
  // / Set variables.
  global $Verbose, $Time, $SesHash3, $Lol, $Append, $EnableMemoryProtection;
  $LogsExist = FALSE;
  // / Verify the User Clam Log File if needed.
  if ($type === 'clamav' or $type === 'all') {
    // / Remove the old User ClamAV Virus Log file if one already exists.
    if (file_exists($clamLogPath)) {
      if ($Verbose) logEntry('Deleting stale file '.$clamLogPath.'.');
      @unlink($clamLogPath); }
    // / Make sure that the stale file was deleted if required or creating a new one will cause problems.
    if (file_exists($clamLogPath)) errorEntry('Could not delete stale file '.$clamLogPath.'!', 16000, TRUE);
    else file_put_contents($clamLogPath, 'Op-Act, '.$Time.', '.$SesHash3.': Created a User Clam Log File.'.$Lol, $Append);
    // / Make sure that the file was successfully replaced.
    if (!file_exists($clamLogPath)) errorEntry('Could not create a file at '.$clamLogPath.'!', 16001, TRUE);
    else {
      $LogsExist = TRUE;
      if ($Verbose) logEntry('Created a file at '.$clamLogPath.'.'); } }
  // / Verify the User ScanCore Log File if needed.
  if ($type === 'scancore' or $type === 'all') {
    // / Remove the old User ScanCore Virus Log file if one already exists.
    if (file_exists($scanCoreLogPath)) {
      if ($Verbose) logEntry('Deleting stale file '.$scanCoreLogPath.'.');
      @unlink($scanCoreLogPath); }
    // / Make sure that the stale file was deleted if required or creating a new one will cause problems.
    if (file_exists($scanCoreLogPath)) errorEntry('Could not delete stale file '.$scanCoreLogPath.'!', 16002, TRUE);
    else file_put_contents($scanCoreLogPath, 'Op-Act, '.$Time.', '.$SesHash3.': Created a User ScanCore Log File.'.$Lol, $Append);
    // / Make sure that the file was successfully replaced.
    if (!file_exists($scanCoreLogPath)) errorEntry('Could not create a file at '.$scanCoreLogPath.'!', 16003, TRUE);
    else {
      $LogsExist = TRUE;
      if ($Verbose) logEntry('Created a file at '.$scanCoreLogPath.'.'); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $type, $clamLogPath, $scanCoreLogPath);
  return $LogsExist; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to format a log entry & write it to a scanner's log.
// / Accepts the entry & the path of the log it belongs in, in that order.
// / Returns TRUE when the entry was written.
function userVirusLogEntry($Entry, $logPath) {
  // / Set variables.
  global $Time, $SesHash3, $Lol, $Append, $EnableMemoryProtection;
  $LogWritten = FALSE;
  $bytesWritten = FALSE;
  // / Format the input string into a log entry & write it to the scanner's log.
  $bytesWritten = file_put_contents($logPath, 'Op-Act, '.$Time.', '.$SesHash3.': '.$Entry.$Lol, $Append);
  // / Check that a log entry was written.
  if ($bytesWritten !== FALSE) $LogWritten = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $bytesWritten, $logPath);
  return $LogWritten; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to scan user supplied files on demand with one scanner.
// / Accepts the files to scan, the scanner's name as runVirusScan knows it, the name a user reads
// / & the path of that scanner's log, in that order.
// / Returns whether any file was given a verdict, whether anything went wrong & whether any
// / file was found infected, in that order.
// / Every scan goes through runVirusScan, the dispatcher that uploads, fetched files & the OCR pipeline already use.
// / The user is told a verdict & nothing else, which is clean, infected or not scanned, & which of their own files.
// / What a scanner said is counted for the administrator's log & discarded.
// / Each file is judged on its own.
function userScanWithScanner($FilesToScan, $scannerName, $scannerLabel, $logPath) {
  // / Set variables.
  global $Verbose, $ConvertDir, $EnableMemoryProtection;
  $OperationSuccessful = $OperationErrors = $UserVirusFound = FALSE;
  $userFilename = $userExtension = $clean = $copy = $variableIsSanitized = $fileIsVerified = FALSE;
  $scanCompleted = $threatWasFound = FALSE;
  $skip = TRUE;
  $scanFindings = array();
  $file = $txt = $scannerUsed = $pathname = $oldPathname = $oldExtension = $newPathname = $verifiedFilename = '';
  $sanitizeError = $verifyError = 0;
  // / Each scanner keeps the error numbers it has always had, so a log still says which one.
  $sanitizeError = ($scannerName === 'clamav') ? 17000 : 19000;
  $verifyError = ($scannerName === 'clamav') ? 17001 : 19001;
  $txt = 'Initiating User Virus Scan with '.$scannerLabel.'.';
  userVirusLogEntry($txt, $logPath);
  if ($Verbose) logEntry($txt);
  if (!is_array($FilesToScan)) $FilesToScan = array($FilesToScan);
  foreach ($FilesToScan as $file) {
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $OperationErrors = TRUE;
      errorEntry('Could not sanitize the input file!', $sanitizeError, FALSE);
      continue; }
    if ($Verbose) logEntry('User selected to perform a '.$scannerLabel.' scan on file '.$file.'.');
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname, $verifiedFilename) = verifyFile($file, $userFilename, $userExtension, $clean, $copy, $skip);
    if (!$fileIsVerified) {
      $OperationErrors = TRUE;
      errorEntry('Could not verify the input file.', $verifyError, FALSE);
      continue; }
    $txt = 'Scanning '.$file.'.';
    if ($Verbose) logEntry($txt);
    userVirusLogEntry($txt, $logPath);
    list ($scanCompleted, $threatWasFound, $scanFindings, $scannerUsed) = runVirusScan(array($ConvertDir.$file), $scannerName);
    if ($Verbose) logEntry($scannerLabel.' reported '.count($scanFindings).' finding(s) for '.$file.'.');
    // / A scan that did not finish is not a clean result & is never reported as one.
    if (!$scanCompleted) {
      $OperationErrors = TRUE;
      warningEntry($scannerLabel.' could not scan '.$file.' for a user.');
      userVirusLogEntry($file.' could not be scanned. No verdict was reached.', $logPath); }
    else if ($threatWasFound) {
      $OperationSuccessful = $UserVirusFound = TRUE;
      $txt = 'Potentially infected file detected at '.$file.'!';
      warningEntry($txt);
      userVirusLogEntry($txt, $logPath); }
    else {
      $OperationSuccessful = TRUE;
      $txt = 'No infection detected in '.$file.'.';
      if ($Verbose) logEntry($txt);
      userVirusLogEntry($txt, $logPath); } }
  // / A scan that gave no file a verdict does not get to end with the word Complete.
  $txt = $OperationSuccessful ? $scannerLabel.' Virus Scan Complete.' : $scannerLabel.' Virus Scan DID NOT RUN.';
  if ($Verbose) logEntry($txt);
  userVirusLogEntry($txt, $logPath);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $userFilename, $userExtension, $clean, $copy, $variableIsSanitized, $fileIsVerified, $scanCompleted, $threatWasFound, $skip, $scanFindings, $file, $txt, $scannerUsed, $pathname, $oldPathname, $oldExtension, $newPathname, $verifiedFilename, $sanitizeError, $verifyError, $scannerName, $scannerLabel, $logPath);
  return array($OperationSuccessful, $OperationErrors, $UserVirusFound); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to process the results of a User Virus Scan & check for any failures or errors.
// / Type can be either 'clamav', 'scancore', or 'all'.
function checkUserVirusScanResults($type, $scan1Complete, $scan1Errors, $scan2Complete, $scan2Errors) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ScanComplete = $ScanErrors = FALSE;
  // / Check that all the input check results are valid.
  if (!is_bool($scan1Complete)) $scan1Complete = FALSE;
  if (!is_bool($scan1Errors)) $scan1Errors = FALSE;
  if (!is_bool($scan2Complete)) $scan2Complete = FALSE;
  if (!is_bool($scan2Errors)) $scan2Errors = FALSE;
  // / A scanner's report is always delivered, even one saying the scan did not run, so the operation is complete & the errors flag carries a failure.
  // / A scanner that gave no file a verdict has already set its own errors flag.
  // / Both scanners are held to that in 'all' mode too.
  if ($type == 'all') {
    $ScanComplete = TRUE;
    if ($scan1Errors or $scan2Errors) $ScanErrors = TRUE; }
  // / Set results using only ClamAV output.
  if ($type == 'clamav') {
    $ScanComplete = TRUE;
    if ($scan1Errors) $ScanErrors = TRUE; }
  // / Set results using only ScanCore output.
  if ($type == 'scancore') {
    $ScanComplete = TRUE;
    if ($scan2Errors) $ScanErrors = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $type, $scan1Complete, $scan1Errors, $scan2Complete, $scan2Errors);
  return array($ScanComplete, $ScanErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to start a fresh report.
// / Accepts the path the report is written to. Returns TRUE when an empty report is in place.
function verifyConsolidatedLogFile($reportPath) {
  // / Set variables.
  global $Verbose, $Append, $EnableMemoryProtection;
  $ConsolidatedLogsExist = FALSE;
  // / Remove the old Consolidated Virus Log file if one already exists.
  if (file_exists($reportPath)) {
    if ($Verbose) logEntry('Deleting stale consolidated log file.');
    @unlink($reportPath); }
  // / Make sure that the stale file was deleted if required or creating a new one will cause problems.
  if (file_exists($reportPath)) errorEntry('Could not delete stale file '.$reportPath.'!', 20000, TRUE);
  // / Attempt to create a new consolidated log if the previous one was successfully removed.
  else file_put_contents($reportPath, '', $Append);
  // / Make sure that the file was successfully replaced.
  if (!file_exists($reportPath)) errorEntry('Could not create a file at '.$reportPath.'!', 20001, TRUE);
  else {
    $ConsolidatedLogsExist = TRUE;
    if ($Verbose) logEntry('Created a file at '.$reportPath.'.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $reportPath);
  return $ConsolidatedLogsExist; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to gather each scanner's log into the one report a user reads.
// / Accepts the scan type, which is clamav, scancore or all, the paths of the ClamAV & ScanCore logs & the report's path, in that order.
// / Returns whether the report exists & whether anything went wrong writing it, in that order.
function consolidateLogs($type, $clamLogPath, $scanCoreLogPath, $reportPath) {
  // / Set variables.
  global $Lol, $Append, $EnableMemoryProtection;
  $ConsolidatedLogsExist = $ConsolidatedLogErrors = $logWrittenA = $logWrittenB = $logWrittenC = $logWrittenD = $logWrittenE = FALSE;
  $userClamLogData = $userScanCoreLogData = $txt = '';
  $spacer = '----------';
  $ConsolidatedLogsExist = verifyConsolidatedLogFile($reportPath);
  if ($type === 'clamav') {
    // / Load the User Clam Log File into memory.
    $userClamLogData = (string)@file_get_contents($clamLogPath);
    $logWrittenA = file_put_contents($reportPath, $userClamLogData.$Lol.$spacer.$Lol, $Append); }
  if ($type === 'scancore') {
    // / Load the User Scan Core Log File into memory.
    $userScanCoreLogData = (string)@file_get_contents($scanCoreLogPath);
    $logWrittenB = file_put_contents($reportPath, $userScanCoreLogData.$Lol.$spacer.$Lol, $Append); }
  if ($type === 'all') {
    // / Head the report, then append both scanners' logs beneath it.
    $txt = 'User selected to scan files with all available scanners.';
    $logWrittenC = file_put_contents($reportPath, $txt.$Lol.$spacer.$Lol, $Append);
    $userClamLogData = (string)@file_get_contents($clamLogPath);
    $logWrittenD = file_put_contents($reportPath, $userClamLogData.$Lol.$spacer.$Lol, $Append);
    $userScanCoreLogData = (string)@file_get_contents($scanCoreLogPath);
    $logWrittenE = file_put_contents($reportPath, $userScanCoreLogData.$Lol.$spacer.$Lol, $Append); }
  // / Check to be sure that the report was written.
  if ($type === 'clamav' && $logWrittenA === FALSE) $ConsolidatedLogErrors = TRUE;
  if ($type === 'scancore' && $logWrittenB === FALSE) $ConsolidatedLogErrors = TRUE;
  if ($type === 'all' && ($logWrittenC === FALSE or $logWrittenD === FALSE or $logWrittenE === FALSE)) $ConsolidatedLogErrors = TRUE;
  if (file_exists($reportPath)) $ConsolidatedLogsExist = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $type, $txt, $spacer, $logWrittenA, $logWrittenB, $logWrittenC, $logWrittenD, $logWrittenE, $userClamLogData, $userScanCoreLogData, $clamLogPath, $scanCoreLogPath, $reportPath);
  return array($ConsolidatedLogsExist, $ConsolidatedLogErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to scan the files a user selected with ClamAV, ScanCore or both, & write the report they read.
// / Accepts the files, the report's name, an extension, which is not read, & the scan type, which is all, clamav or scancore, in that order.
// / Returns whether a verdict was reached, whether anything went wrong & what the scan found, in that order.
// / What it found holds Threat, which is TRUE when any file was found infected, Report, the report's path, & ReportName, its name.
// / A detection from either scanner is a detection.
// / Each scanner reports into its own variable & the user is given the OR of them, because the two carry different definitions, which is the reason for offering both.
// / Each scanner is run once, over the whole list, because a virus scan draws on a resource budget & runs under a ceiling.
// / A report name that is not a plain name is replaced by the one HRConvert2 has always used, with a warning, rather than written somewhere else.
function scanSelection($FilesToScan, $UserFilename, $UserExtension, $Method) {
  // / Set variables.
  global $ConvertDir, $ConvertTempDir, $EnableMemoryProtection;
  $ScanComplete = $ScanErrors = FALSE;
  $ScanFindings = array('Threat' => FALSE, 'Report' => '', 'ReportName' => '');
  $scan1Complete = $scan1Errors = $scan2Complete = $scan2Errors = $logsExist = $reportExists = $reportErrors = $nameIsSanitized = FALSE;
  $clamVirusFound = $scanCoreVirusFound = $virusWasFound = FALSE;
  $type = $reportName = $clamLogPath = $scanCoreLogPath = $reportPath = '';
  $defaultReportName = 'User_Consolidated_Virus_Scan_Report.txt';
  // / Check that the scan type is valid.
  $type = strtolower((string)$Method);
  if ($type !== 'all' && $type !== 'clamav' && $type !== 'scancore') $type = 'all';
  // / The report is named by the caller, & a name that sanitize() changes is not a plain name.
  list ($reportName, $nameIsSanitized) = sanitize((string)$UserFilename, TRUE);
  if (!$nameIsSanitized or !is_string($reportName) or $reportName === '' or $reportName !== (string)$UserFilename or strtolower(substr($reportName, -4)) !== '.txt') {
    warningEntry('A scan was asked to write a report under a name that is not a plain text file\'s name, so it was written as '.$defaultReportName.'.');
    redeclare($reportName, $defaultReportName); }
  $clamLogPath = $ConvertDir.'User_ClamScan_Virus_Scan_Report.txt';
  $scanCoreLogPath = $ConvertDir.'User_ScanCore_Virus_Scan_Report.txt';
  $reportPath = $ConvertTempDir.$reportName;
  // / Make sure the input files are formatted into an array.
  if (!is_array($FilesToScan)) $FilesToScan = array($FilesToScan);
  $logsExist = verifyUserVirusLogs($type, $clamLogPath, $scanCoreLogPath);
  if ($type === 'clamav' or $type === 'all') {
    list ($scan1Complete, $scan1Errors, $clamVirusFound) = userScanWithScanner($FilesToScan, 'clamav', 'ClamAV', $clamLogPath);
    if ($clamVirusFound) $virusWasFound = TRUE; }
  if ($type === 'scancore' or $type === 'all') {
    list ($scan2Complete, $scan2Errors, $scanCoreVirusFound) = userScanWithScanner($FilesToScan, 'scancore', 'ScanCore', $scanCoreLogPath);
    if ($scanCoreVirusFound) $virusWasFound = TRUE; }
  // / Check the results of the virus scan for failures or errors.
  list ($ScanComplete, $ScanErrors) = checkUserVirusScanResults($type, $scan1Complete, $scan1Errors, $scan2Complete, $scan2Errors);
  // / Gather each scanner's log into the report the user reads.
  list ($reportExists, $reportErrors) = consolidateLogs($type, $clamLogPath, $scanCoreLogPath, $reportPath);
  // / Verify that all operations are complete.
  if ($ScanErrors or $reportErrors) $ScanErrors = TRUE;
  if (!$reportExists) $ScanComplete = FALSE;
  $ScanFindings = array('Threat' => $virusWasFound, 'Report' => $reportExists ? $reportPath : '', 'ReportName' => $reportExists ? $reportName : '');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $scan1Complete, $scan1Errors, $scan2Complete, $scan2Errors, $logsExist, $reportExists, $reportErrors, $nameIsSanitized, $clamVirusFound, $scanCoreVirusFound, $virusWasFound, $type, $reportName, $clamLogPath, $scanCoreLogPath, $reportPath, $defaultReportName, $FilesToScan, $UserFilename, $UserExtension, $Method);
  return array($ScanComplete, $ScanErrors, $ScanFindings); }
// / -----------------------------------------------------------------------------------
?>
