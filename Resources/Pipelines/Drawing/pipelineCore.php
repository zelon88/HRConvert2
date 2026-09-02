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
// / v3.8.6.
// / This file is the converter for the Drawing pipeline. It is loaded by pipelineManager.php
// / ONLY when a Drawing conversion is about to be dispatched to it, so a request that
// / converts something else never parses a line of it.
// / Error block 10000 through 10002 belongs to this pipeline. Those numbers came with the code when it
// / moved out of convertCore.php & they did not change, because operators have read them.
// / This pipeline calls verifyDrawingVersion(), sandboxCommand() & locateDependency(), which
// / all remain in convertCore.php. A dependency verifier is still core owned.
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
// / A function to convert 2D vector drawing formats.
// / Dia handles diagram formats such as dxf, fig, vdx & wpg. SVG is handled by Inkscape in
// / convertSVG() instead, because Dia exports SVG without truly rendering it.
// / The binary is supplied by verifyDrawingVersion() rather than assumed, so the version
// / that was verified is provably the version that runs.
// / This function had NO version gate at all before this change. Dia was invoked blind, &
// / a missing binary produced a timeout rather than naming the cause.
// / Dia is a GTK application. If a conversion fails INSIDE a working sandbox rather than
// / being refused by the sandbox gate, a missing display is the first thing to suspect,
// / because the sandbox provides a private /tmp & an X socket outside it is not visible.
function convertDrawings($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer, $MinimumDiaVersion, $EnableMemoryProtection;
  // / The six value pipeline contract. Success, errors, path, extension, filename & PID.
  // / This converter produces neither of the last two itself, so it declares the defaults.
  // / $OutputFilename is the name the user is shown. $WorkerPID stays zero unless a
  // / detached process must be supervised after the connection to the user is closed.
  $OutputFilename = basename($newPathname);
  $WorkerPID = 0;
  $ConversionSuccess = $ConversionErrors = $commandMayRun = FALSE;
  $drawingBinary = FALSE;
  $returnData = $diaCommand = '';
  $stopper = 0;
  $sleepTime = $SleepTimer;
  // / Locate & verify Dia. A path is returned only when both succeeded.
  $drawingBinary = verifyDrawingVersion($MinimumDiaVersion);
  if ($drawingBinary === FALSE) {
    $ConversionErrors = TRUE;
    errorEntry('The installed Dia version is missing, unidentifiable, or too old!', 10002, FALSE); }
  else {
    // / Build & sandbox the command once. It does not change between retries.
    $diaCommand = escapeshellarg($drawingBinary).' '.escapeshellarg($pathname).' -e '.escapeshellarg($newPathname);
    list ($commandMayRun, $diaCommand) = sandboxCommand($diaCommand, $pathname, $newPathname, FALSE, 'dia');
    if (!$commandMayRun) {
      $ConversionErrors = TRUE;
      errorEntry('Bubblewrap is missing or non functional, so this drawing conversion cannot be isolated!', 10001, FALSE); }
    else {
      if ($Verbose) logEntry('Converting drawing.');
      // / This code will attempt the conversion up to $StopCounter number of times.
      while (!file_exists($newPathname) && $stopper <= $StopCounter) {
        // / If the last conversion attempt failed, wait a moment before trying again.
        if ($stopper !== 0) sleep($sleepTime++);
        $returnData = shell_exec($diaCommand);
        // / Count the number of conversions to avoid infinite loops.
        $stopper++;
        // / Stop attempting the conversion after $StopCounter number of attempts.
        if ($stopper === $StopCounter) {
          $ConversionErrors = TRUE;
          errorEntry('The drawing converter timed out!', 10000, FALSE); } }
      // / Log the output of the operation to the logfile, if it is not blank.
      if ($Verbose && trim($returnData) !== '') logEntry('Dia returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
      // / The output file is the only verdict on whether the conversion produced anything.
      // / This check must stay inside the gates, or a stale output file from an earlier
      // / attempt would report success for a conversion that was refused & never ran.
      if (file_exists($newPathname)) $ConversionSuccess = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $returnData, $stopper, $pathname, $sleepTime, $diaCommand, $drawingBinary, $commandMayRun);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $OutputFilename, $WorkerPID); }
// / -----------------------------------------------------------------------------------

?>
