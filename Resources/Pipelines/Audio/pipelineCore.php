<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRConvert2, Copyright on 8/17/2026 by Justin Grimes, www.github.com/zelon88
// /
// / LICENSE INFORMATION ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / APPLICATION INFORMATION ...
// / This application is designed to provide a web-interface for converting file formats on
// / a server for users of any web browser without authentication.
// /
// / FILE INFORMATION ...
// / v3.8.5.
// / This file is the converter for the Audio pipeline. It is loaded by pipelineManager.php
// / ONLY when a Audio conversion is about to be dispatched to it, so a request that
// / converts something else never parses a line of it.
// / Error block 1000 through 1002 belongs to this pipeline. Those numbers came with the code when it
// / moved out of convertCore.php & they did not change, because operators have read them.
// / This pipeline calls verifyFFMPEGVersion() & sandboxCommand(), which remain in
// / convertCore.php. FOUR FAMILIES NAME THE FFMPEG SUBSYSTEM. Audio, Video & Subtitle are
// / pipelines now & Stream is still built into the core, so one installation reaches the
// / same binary from two places. That is safe because verifyFFMPEGVersion() is the single
// / core owned gate all four pass through.
// / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt for the contracts this file obeys.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline converter cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to convert audio formats.
// / The general minimum is enforced rather than the stream minimum, because an audio
// / conversion reads a local file & never fetches anything remote.
function convertAudio($pathname, $newPathname, $extension, $bitrate) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer, $MinimumFFMPEGVersion, $EnableMemoryProtection;
  // / The six value pipeline contract. Success, errors, path, extension, filename & PID.
  // / This converter produces neither of the last two itself, so it declares the defaults.
  // / $OutputFilename is the name the user is shown. $WorkerPID stays zero unless a
  // / detached process must be supervised after the connection to the user is closed.
  $OutputFilename = basename($newPathname);
  $WorkerPID = 0;
  $ConversionSuccess = $ConversionErrors = $sandboxIsAvailable = FALSE;
  $ffmpegBinary = FALSE;
  $returnData = $ffmpegCommand = '';
  $br = ' ';
  $stopper = 0;
  $sleepTime = $SleepTimer;
  // / Determine if the bitrate is being set.
  if (empty($bitrate) or $bitrate === '') $bitrate = 'auto';
  if ($bitrate === 'auto') $br = ' ';
  else $br = ' -b:a '.escapeshellarg($bitrate).' ';
  // / Locate & verify FFMPEG. A path is returned only when both succeeded.
  $ffmpegBinary = verifyFFMPEGVersion($MinimumFFMPEGVersion);
  if ($ffmpegBinary === FALSE) {
    $ConversionErrors = TRUE;
    errorEntry('The installed FFMPEG version is missing, unidentifiable, or too old!', 12001, FALSE); }
  else {
    // / Build & sandbox the command once. It does not change between retries.
    $ffmpegCommand = escapeshellarg($ffmpegBinary).' -y -vn -i '.escapeshellarg($pathname).$br.escapeshellarg($newPathname);
    list ($sandboxIsAvailable, $ffmpegCommand) = sandboxCommand($ffmpegCommand, $pathname, $newPathname, FALSE, 'ffmpeg');
    if (!$sandboxIsAvailable) {
      $ConversionErrors = TRUE;
      errorEntry('Bubblewrap is missing or non functional, so this audio conversion cannot be isolated!', 12002, FALSE); }
    else {
      if ($Verbose) logEntry('Converting audio.');
      // / This code will attempt the conversion up to $StopCounter number of times.
      while (!file_exists($newPathname) && $stopper <= $StopCounter) {
        // / If the last conversion attempt failed, wait a moment before trying again.
        if ($stopper !== 0) sleep($sleepTime++);
        $returnData = shell_exec($ffmpegCommand);
        // / Count the number of conversions to avoid infinite loops.
        $stopper++;
        // / Stop attempting the conversion after $StopCounter number of attempts.
        if ($stopper === $StopCounter) {
          $ConversionErrors = TRUE;
          errorEntry('The audio converter timed out!', 12000, FALSE); } }
      // / Log the output of the operation to the logfile, if it is not blank.
      if ($Verbose && trim($returnData) !== '') logEntry('Ffmpeg returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
      // / The output file is the only verdict on whether the conversion produced anything.
      // / This check must stay inside the gates, or a stale output file from an earlier
      // / attempt would report success for a conversion that was refused & never ran.
      if (file_exists($newPathname)) $ConversionSuccess = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $returnData, $stopper, $pathname, $br, $bitrate, $sleepTime, $ffmpegBinary, $ffmpegCommand, $sandboxIsAvailable);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $OutputFilename, $WorkerPID); }
// / -----------------------------------------------------------------------------------

?>
