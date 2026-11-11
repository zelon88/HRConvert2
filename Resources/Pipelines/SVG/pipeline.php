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
// / The SVG pipeline's converter. pipelineCore.php loads it only to dispatch an SVG conversion.
// / Error block 25000 through 25002 belongs to this pipeline.
// / Inkscape comes from verifiedToolPath().
// / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt for the contracts this file obeys.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline converter cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to convert SVG vector drawing formats.
// / The binary is supplied by verifiedToolPath() rather than assumed.
// / A width & a height may both be supplied. Inkscape honours both independently, which
// / stretches the image, so the caller decides whether that is what the user asked for.
function convertSVG($pathname, $newPathname, $extension, $height, $width) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer, $EnableMemoryProtection;
  // / The six value pipeline contract. Success, errors, path, extension, filename & PID.
  // / The last two keep their defaults, & $WorkerPID stays 0 unless a detached process is supervised.
  $OutputFilename = basename($newPathname);
  $WorkerPID = 0;
  $ConversionSuccess = $ConversionErrors = $commandMayRun = FALSE;
  $svgBinary = FALSE;
  $returnData = $argEcho = $inkscapeCommand = '';
  $stopper = 0;
  $sleepTime = $SleepTimer;
  // / Build the export sizing arguments. A dimension of zero is not passed at all.
  if (!empty($width) && $width > 0) $argEcho = '--export-width='.escapeshellarg($width);
  if (!empty($height) && $height > 0) $argEcho = trim($argEcho.' --export-height='.escapeshellarg($height));
  if ($argEcho !== '') $argEcho = $argEcho.' ';
  // / Locate & verify Inkscape. A path is returned only when both succeeded.
  $svgBinary = verifiedToolPath('Inkscape');
  if ($svgBinary === FALSE) {
    $ConversionErrors = TRUE;
    errorEntry('The installed Inkscape version is missing, unidentifiable, or too old!', 25001, FALSE); }
  else {
    // / Build & sandbox the command once. It does not change between retries.
    $inkscapeCommand = escapeshellarg($svgBinary).' '.$argEcho.'--export-filename='.escapeshellarg($newPathname).' '.escapeshellarg($pathname);
    list ($commandMayRun, $inkscapeCommand) = sandboxCommand($inkscapeCommand, $pathname, $newPathname, FALSE, 'inkscape');
    if (!$commandMayRun) {
      $ConversionErrors = TRUE;
      errorEntry('Bubblewrap is missing or non functional, so this SVG conversion cannot be isolated!', 25002, FALSE); }
    else {
      if ($Verbose) logEntry('Converting SVG.');
      // / Attempt the conversion as many times as --File Operation Retry Count-- allows, & stop at the
      // / first attempt that leaves a file.
      while (pipelineAttemptIsDue($newPathname, $stopper)) {
        // / If the last conversion attempt failed, wait a moment before trying again.
        if ($stopper !== 0) sleep($sleepTime++);
        $returnData = (string)shell_exec($inkscapeCommand.' 2>&1');
        // / Count the number of conversions to avoid infinite loops.
        $stopper++; }
      // / Log the output of the operation to the logfile, if it is not blank.
      if ($Verbose && trim($returnData) !== '') logEntry('Inkscape returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
      // / Every attempt was made & none left a file.
      // / What the converter printed is logged above, with --Enhanced Logging Verbosity-- on.
      if (!file_exists($newPathname)) {
        $ConversionErrors = TRUE;
        errorEntry('The SVG converter produced no file after '.$stopper.' attempt(s)!', 25000, FALSE); }
      // / The output file is the only verdict on whether the conversion produced anything.
      if (file_exists($newPathname)) $ConversionSuccess = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $returnData, $stopper, $pathname, $height, $width, $sleepTime, $argEcho, $svgBinary, $inkscapeCommand, $commandMayRun);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $OutputFilename, $WorkerPID); }
// / -----------------------------------------------------------------------------------

?>
