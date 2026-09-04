<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRConvert2, Copyright on 8/17/2026 by Justin Grimes, www.github.com/zelon88
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
// / v3.8.8.
// / This file is the converter for the Video pipeline. It is loaded by pipelineManager.php
// / ONLY when a Video conversion is about to be dispatched to it, so a request that
// / converts something else never parses a line of it.
// / Error block 11000 through 11002 belongs to this pipeline.
// / Those numbers came with the code when it moved out of convertCore.php.
// / They did not change, because operators have already read them.
// /
// / This is one of four families sharing the FFMPEG subsystem.
// / Audio & Stream are still built into convertCore.php & Video & Subtitle are not, so an
// / installation now runs FFMPEG from two places at once. That is expected & is safe,
// / because verifyFFMPEGVersion() remains core owned & is the single gate all four pass
// / through. A pipeline that carried its own copy of that verifier would be the bug.
// / This pipeline calls verifyFFMPEGVersion(), sandboxCommand() & locateDependency(), all
// / of which remain in convertCore.php. A dependency verifier is still core owned.
// / showVersionInfo() reports on it whether or not this pipeline is installed.
// / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt for the contracts this file obeys.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline converter cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to convert video formats.
// / The general minimum is enforced rather than the stream minimum, because a video
// / conversion reads a local file & never fetches anything remote.
function convertVideos($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer, $MinimumFFMPEGVersion, $EnableMemoryProtection;
  // / The six value pipeline contract. Success, errors, path, extension, filename & PID.
  // / This converter produces neither of the last two itself, so it declares the defaults.
  // / $OutputFilename is the name the user is shown. $WorkerPID stays zero unless a
  // / detached process must be supervised after the connection to the user is closed.
  $OutputFilename = basename($newPathname);
  $WorkerPID = 0;
  $ConversionSuccess = $ConversionErrors = $commandMayRun = FALSE;
  $ffmpegBinary = FALSE;
  $returnData = $ffmpegCommand = $outputExtension = $videoCodec = '';
  $stopper = 0;
  $sleepTime = $SleepTimer;
  // / Locate & verify FFMPEG. A path is returned only when both succeeded.
  $ffmpegBinary = verifyFFMPEGVersion($MinimumFFMPEGVersion);
  if ($ffmpegBinary === FALSE) {
    $ConversionErrors = TRUE;
    errorEntry('The installed FFMPEG version is missing, unidentifiable, or too old!', 11001, FALSE); }
  else {
    // / The codec follows the container rather than being the same one every time.
    // / An earlier version asked for libx264 whatever the target was.
    // / A WebM container holds VP8, VP9 or AV1 & rejects H.264 outright.
    // / An Ogg container expects Theora & rejects it for the same reason.
    // / Both formats are offered by this pipeline & neither could ever have worked.
    // / An empty codec lets FFMPEG choose the default encoder for the container it is
    // / writing, which is correct for every other format this pipeline offers.
    $outputExtension = strtolower(trim((string)$extension, '.'));
    if ($outputExtension === 'webm') $videoCodec = ' -c:v libvpx-vp9 -c:a libopus';
    else if ($outputExtension === 'ogv') $videoCodec = ' -c:v libtheora -c:a libvorbis';
    else $videoCodec = ' -c:v libx264';
    // / Standard error is captured, & for FFMPEG that is not a detail.
    // / FFMPEG writes everything it has to say to standard error, including its failures.
    // / Without this the return data was always empty, the log line that reports it never
    // / fired, & a failed conversion produced a timeout & no explanation whatsoever.
    $ffmpegCommand = escapeshellarg($ffmpegBinary).' -y -i '.escapeshellarg($pathname).$videoCodec.' '.escapeshellarg($newPathname).' 2>&1';
    list ($commandMayRun, $ffmpegCommand) = sandboxCommand($ffmpegCommand, $pathname, $newPathname, FALSE, 'ffmpeg');
    if (!$commandMayRun) {
      $ConversionErrors = TRUE;
      errorEntry('Bubblewrap is missing or non functional, so this video conversion cannot be isolated!', 11002, FALSE); }
    else {
      if ($Verbose) logEntry('Converting video.');
      // / This code will attempt the conversion up to $StopCounter number of times.
      while (!file_exists($newPathname) && $stopper <= $StopCounter) {
        // / If the last conversion attempt failed, wait a moment before trying again.
        if ($stopper !== 0) sleep($sleepTime++);
        $returnData = shell_exec($ffmpegCommand);
        // / Count the number of conversions to avoid infinite loops.
        $stopper++;
        // / Stop attempting the conversion after $StopCounter number of attempts.
        // / The loop is left immediately, because an attempt already counted as the last
        // / one must not run again after the timeout has been reported.
        if ($stopper >= $StopCounter) {
          $ConversionErrors = TRUE;
          errorEntry('The video converter timed out!', 11000, FALSE);
          // / A timeout on its own says only that something was tried repeatedly.
          // / The command is what was tried & is the only way to reproduce the failure.
          warningEntry('The command that timed out was: '.trim($ffmpegCommand));
          break; } }
      // / A converter that produced nothing at all is worth a line of its own.
      // / Testing for output & staying silent when there is none meant the one case that
      // / needed explaining was the one case that explained nothing.
      if (!file_exists($newPathname) && trim($returnData) === '') warningEntry('FFMPEG produced no output at all. The command was found & run, & it printed nothing.');
      // / Log the output of the operation to the logfile, if it is not blank.
      if ($Verbose && trim($returnData) !== '') logEntry('Ffmpeg returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
      // / The output file is the only verdict on whether the conversion produced anything.
      if (file_exists($newPathname)) $ConversionSuccess = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $outputExtension, $videoCodec, $returnData, $stopper, $pathname, $sleepTime, $ffmpegBinary, $ffmpegCommand, $commandMayRun);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $OutputFilename, $WorkerPID); }
// / -----------------------------------------------------------------------------------

?>
