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
// / The Drawing pipeline's converter. pipelineCore.php loads it only to dispatch a Drawing conversion.
// / Error block 10000 through 10002 belongs to this pipeline.
// / Dia comes from verifiedToolPath().
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
// / Dia handles diagram formats such as dxf, fig, vdx & wpg.
// / convertSVG() handles SVG with Inkscape, because Dia exports SVG without truly rendering it.
// / The binary comes from verifiedToolPath(), so the version verified is the version that runs.
// / Dia is a GTK application.
// / A conversion that fails inside a working sandbox points first to a missing display.
// / The sandbox's private /tmp hides any X socket outside it.
function convertDrawings($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer, $EnableMemoryProtection;
  // / The six value pipeline contract. Success, errors, path, extension, filename & PID.
  // / The last two keep their defaults, & $WorkerPID stays 0 unless a detached process is supervised.
  $OutputFilename = basename($newPathname);
  $WorkerPID = 0;
  $ConversionSuccess = $ConversionErrors = $commandMayRun = FALSE;
  $drawingBinary = FALSE;
  $returnData = $diaCommand = '';
  $stopper = 0;
  $sleepTime = $SleepTimer;
  // / Locate & verify Dia. A path is returned only when both succeeded.
  $drawingBinary = verifiedToolPath('Dia');
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
      // / Attempt the conversion as many times as --File Operation Retry Count-- allows, & stop at the
      // / first attempt that leaves a file.
      while (pipelineAttemptIsDue($newPathname, $stopper)) {
        // / If the last conversion attempt failed, wait a moment before trying again.
        if ($stopper !== 0) sleep($sleepTime++);
        $returnData = (string)shell_exec($diaCommand.' 2>&1');
        // / Count the number of conversions to avoid infinite loops.
        $stopper++; }
      // / Log the output of the operation to the logfile, if it is not blank.
      if ($Verbose && trim($returnData) !== '') logEntry('Dia returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
      // / Every attempt was made & none left a file.
      // / What the converter printed is logged above, with --Enhanced Logging Verbosity-- on.
      if (!file_exists($newPathname)) {
        $ConversionErrors = TRUE;
        errorEntry('The drawing converter produced no file after '.$stopper.' attempt(s)!', 10000, FALSE); }
      // / The output file is the only verdict on whether the conversion produced anything.
      // / This check must stay inside the gates, or a stale output file from an earlier
      // / attempt would report success for a conversion that was refused & never ran.
      if (file_exists($newPathname)) $ConversionSuccess = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $returnData, $stopper, $pathname, $sleepTime, $diaCommand, $drawingBinary, $commandMayRun);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $OutputFilename, $WorkerPID); }
// / -----------------------------------------------------------------------------------

?>
