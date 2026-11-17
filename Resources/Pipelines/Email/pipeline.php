<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRConvert2, Copyright on 11/16/2026 by Justin Grimes, www.github.com/zelon88
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
// / v4.2.5.
// / The Email pipeline's converter. pipelineCore.php loads it only to dispatch an Email conversion.
// / Error block 47000 through 47004 belongs to this pipeline.
// / msgconvert comes from verifiedToolPath().
// / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt for the contracts this file obeys.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline converter cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to convert an Outlook message into a standard email with msgconvert.
// / msgconvert writes the message as an EML file with --outfile, or delivers it into an mbox mailbox with --mbox. The output is always a new
// / file, so the mailbox holds the one message.
// / msgconvert is a Perl program reading a file format with tables that point at each other, so a message that loops is stopped after 120 seconds.
// / msgconvert asks for the server's full name while it writes, & says on standard error that the sandbox has none. That changes nothing it writes.
// / The function is longer than most, so it returns the six value contract, whose first value is its sanity check.
function convertEmails($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $Lol, $SleepTimer, $EnableMemoryProtection;
  // / The six value pipeline contract. Success, errors, path, extension, filename & PID.
  $OutputFilename = basename($newPathname);
  $WorkerPID = 0;
  $ConversionSuccess = $ConversionErrors = $commandMayRun = $timedOut = FALSE;
  $emailBinary = FALSE;
  $outputOption = $emailCommand = '';
  $commandOutput = array();
  $outputOptions = array('eml' => '--outfile', 'mbox' => '--mbox');
  $exitStatus = -1;
  $stopper = 0;
  $sleepTime = $SleepTimer;
  if (isset($outputOptions[strtolower((string)$extension)])) $outputOption = $outputOptions[strtolower((string)$extension)];
  // / Locate & verify msgconvert. A path is returned only when both succeeded.
  $emailBinary = verifiedToolPath('msgconvert');
  if ($emailBinary === FALSE) {
    $ConversionErrors = TRUE;
    errorEntry('The installed msgconvert version is missing, unidentifiable, or too old!', 47001, FALSE); }
  else if ($outputOption === '') {
    $ConversionErrors = TRUE;
    errorEntry('The Email pipeline cannot write '.strtolower((string)$extension).'!', 47003, FALSE); }
  else {
    // / Build & sandbox the command once. It does not change between retries.
    $emailCommand = 'timeout 120 '.escapeshellarg($emailBinary).' '.$outputOption.' '.escapeshellarg($newPathname).' '.escapeshellarg($pathname);
    list ($commandMayRun, $emailCommand) = sandboxCommand($emailCommand, $pathname, $newPathname, FALSE, 'msgconvert');
    if (!$commandMayRun) {
      $ConversionErrors = TRUE;
      errorEntry('Bubblewrap is missing or non functional, so this email conversion cannot be isolated!', 47002, FALSE); }
    else {
      if ($Verbose) logEntry('Converting an email.');
      // / Attempt the conversion as many times as --File Operation Retry Count-- allows, & stop at the first attempt that leaves a file.
      // / A message that ran out of time is not attempted again, because it would run out of time again.
      while (!$timedOut && pipelineAttemptIsDue($newPathname, $stopper)) {
        // / If the last conversion attempt failed, wait a moment before trying again.
        if ($stopper !== 0) sleep($sleepTime++);
        redeclare($commandOutput, array());
        exec($emailCommand.' 2>&1', $commandOutput, $exitStatus);
        // / msgconvert ends with a status other than 0 where it stopped, & a file it had begun is not the message, so it is removed.
        // / An exit status of 124 is the timeout command reporting that it stopped msgconvert.
        if ($exitStatus !== 0) {
          @unlink($newPathname);
          if ($exitStatus === 124) $timedOut = TRUE; }
        // / Count the number of conversions to avoid infinite loops.
        $stopper++; }
      // / Log the output of the operation to the logfile, if it is not blank.
      if ($Verbose && trim(implode($Lol, $commandOutput)) !== '') logEntry('msgconvert returned the following: '.$Lol.'  '.implode($Lol.'  ', array_slice($commandOutput, 0, 40)));
      if ($timedOut) {
        $ConversionErrors = TRUE;
        errorEntry('The email converter was stopped after 120 seconds!', 47004, FALSE); }
      // / Every attempt was made & none left a file.
      else if (!file_exists($newPathname)) {
        $ConversionErrors = TRUE;
        errorEntry('The email converter produced no file after '.$stopper.' attempt(s)!', 47000, FALSE); }
      // / The output file is the only verdict on whether the conversion produced anything.
      if (!$timedOut && file_exists($newPathname)) $ConversionSuccess = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $commandMayRun, $timedOut, $emailBinary, $outputOption, $emailCommand, $commandOutput, $outputOptions, $exitStatus, $stopper, $sleepTime, $pathname);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $OutputFilename, $WorkerPID); }
// / -----------------------------------------------------------------------------------

?>
