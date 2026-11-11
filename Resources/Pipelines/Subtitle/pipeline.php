<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRConvert2, Copyright on 9/22/2026 by Justin Grimes, www.github.com/zelon88
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
// / v4.0.8.
// / The Subtitle pipeline's converter. pipelineCore.php loads it only to dispatch a Subtitle conversion.
// / Error block 22000 through 22002 belongs to this pipeline.
// / Audio, Video, Subtitle & Stream share the FFMPEG subsystem.
// / Each asks verifiedToolPath(), so one minimum governs all four.
// / No pipeline carries its own version check, because two checks eventually disagree.
// / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt for the contracts this file obeys.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline converter cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to convert subtitle formats.
function convertSubtitles($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer, $EnableMemoryProtection;
  // / The six value pipeline contract. Success, errors, path, extension, filename & PID.
  // / The last two keep their defaults, & $WorkerPID stays 0 unless a detached process is supervised.
  $OutputFilename = basename($newPathname);
  $WorkerPID = 0;
  $ConversionSuccess = $ConversionErrors = $commandMayRun = FALSE;
  $ffmpegBinary = FALSE;
  $returnData = $ffmpegCommand = '';
  $stopper = 0;
  $sleepTime = $SleepTimer;
  // / Locate & verify FFMPEG. A path is returned only when both succeeded.
  $ffmpegBinary = verifiedToolPath('FFMPEG');
  if ($ffmpegBinary === FALSE) {
    $ConversionErrors = TRUE;
    errorEntry('The installed FFMPEG version is missing, unidentifiable, or too old!', 22002, FALSE); }
  else {
    // / Build & sandbox the command once. It does not change between retries.
    $ffmpegCommand = escapeshellarg($ffmpegBinary).' -y -i '.escapeshellarg($pathname).' '.escapeshellarg($newPathname);
    list ($commandMayRun, $ffmpegCommand) = sandboxCommand($ffmpegCommand, $pathname, $newPathname, FALSE, 'ffmpeg');
    if (!$commandMayRun) {
      $ConversionErrors = TRUE;
      errorEntry('Bubblewrap is missing or non functional, so this subtitle conversion cannot be isolated!', 22001, FALSE); }
    else {
      if ($Verbose) logEntry('Converting subtitle.');
      // / Attempt the conversion as many times as --File Operation Retry Count-- allows, & stop at the
      // / first attempt that leaves a file.
      while (pipelineAttemptIsDue($newPathname, $stopper)) {
        // / If the last conversion attempt failed, wait a moment before trying again.
        if ($stopper !== 0) sleep($sleepTime++);
        $returnData = (string)shell_exec($ffmpegCommand.' 2>&1');
        // / Count the number of conversions to avoid infinite loops.
        $stopper++; }
      // / Log the output of the operation to the logfile, if it is not blank.
      if ($Verbose && trim($returnData) !== '') logEntry('Ffmpeg returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
      // / Every attempt was made & none left a file.
      // / What the converter printed is logged above, with --Enhanced Logging Verbosity-- on.
      if (!file_exists($newPathname)) {
        $ConversionErrors = TRUE;
        errorEntry('The subtitle converter produced no file after '.$stopper.' attempt(s)!', 22000, FALSE); }
      // / The output file is the only verdict on whether the conversion produced anything.
      if (file_exists($newPathname)) $ConversionSuccess = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $returnData, $stopper, $pathname, $sleepTime, $ffmpegBinary, $ffmpegCommand, $commandMayRun);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $OutputFilename, $WorkerPID); }
// / -----------------------------------------------------------------------------------

?>
