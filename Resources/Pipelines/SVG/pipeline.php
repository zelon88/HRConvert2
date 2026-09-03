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
// / This file is the converter for the SVG pipeline. It is loaded by pipelineCore.php
// / ONLY when a SVG conversion is about to be dispatched to it, so a request that
// / converts something else never parses a line of it.
// / Error block 25000 through 25002 belongs to this pipeline. Those numbers came with the code when it
// / moved out of convertCore.php & they did not change, because operators have read them.
// / This pipeline calls verifySVGVersion(), sandboxCommand() & locateDependency(), which all
// / remain in convertCore.php. A dependency verifier is still core owned.
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
// / A function to convert SVG vector drawing formats.
// / The binary is supplied by verifySVGVersion() rather than assumed.
// / A width & a height may both be supplied. Inkscape honours both independently, which
// / stretches the image, so the caller decides whether that is what the user asked for.
function convertSVG($pathname, $newPathname, $extension, $height, $width) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer, $MinimumInkscapeVersion, $EnableMemoryProtection;
  // / The six value pipeline contract. Success, errors, path, extension, filename & PID.
  // / This converter produces neither of the last two itself, so it declares the defaults.
  // / $OutputFilename is the name the user is shown. $WorkerPID stays zero unless a
  // / detached process must be supervised after the connection to the user is closed.
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
  $svgBinary = verifySVGVersion($MinimumInkscapeVersion);
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
      // / This code will attempt the conversion up to $StopCounter number of times.
      while (!file_exists($newPathname) && $stopper <= $StopCounter) {
        // / If the last conversion attempt failed, wait a moment before trying again.
        if ($stopper !== 0) sleep($sleepTime++);
        $returnData = shell_exec($inkscapeCommand);
        // / Count the number of conversions to avoid infinite loops.
        $stopper++;
        // / Stop attempting the conversion after $StopCounter number of attempts.
        if ($stopper === $StopCounter) {
          $ConversionErrors = TRUE;
          errorEntry('The SVG converter timed out!', 25000, FALSE); } }
      // / Log the output of the operation to the logfile, if it is not blank.
      if ($Verbose && trim($returnData) !== '') logEntry('Inkscape returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
      // / The output file is the only verdict on whether the conversion produced anything.
      if (file_exists($newPathname)) $ConversionSuccess = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $returnData, $stopper, $pathname, $height, $width, $sleepTime, $argEcho, $svgBinary, $inkscapeCommand, $commandMayRun);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $OutputFilename, $WorkerPID); }
// / -----------------------------------------------------------------------------------

?>
