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
// / This file is the converter for the Image pipeline. It is loaded by pipelineManager.php
// / ONLY when a Image conversion is about to be dispatched to it, so a request that
// / converts something else never parses a line of it.
// / Error block 5000 through 5006 belongs to this pipeline. Those numbers came with the code when it
// / moved out of convertCore.php & they did not change, because operators have read them.
// / This pipeline calls verifyImageVersion() & sandboxCommand(), which remain in
// / convertCore.php. verifyImageVersion() is also called by showVersionInfo(), by
// / updateApplication() & by the OCR pipeline, so it is core owned & stays that way.
// / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt for the contracts this file obeys.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline converter cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to convert image formats.
// / The binary is supplied by verifyImageVersion() rather than hardcoded, so the version
// / that was verified is provably the version that runs.
// / A dimension of zero is omitted from the geometry rather than written, so the other
// / dimension scales to it. ImageMagick treats WxH as a bounding box & preserves the
// / aspect ratio, so an exclamation mark is added when the user supplied both & therefore
// / asked for both exactly.
function convertImages($pathname, $newPathname, $extension, $height, $width, $rotate) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer, $MinimumImageVersion, $EnableMemoryProtection;
  // / The six value pipeline contract. Success, errors, path, extension, filename & PID.
  // / This converter produces neither of the last two itself, so it declares the defaults.
  // / $OutputFilename is the name the user is shown. $WorkerPID stays zero unless a
  // / detached process must be supervised after the connection to the user is closed.
  $OutputFilename = basename($newPathname);
  $WorkerPID = 0;
  $ConversionSuccess = $ConversionErrors = $commandMayRun = FALSE;
  $imageBinary = FALSE;
  $returnData = $wh = $wxh = $bgSwitch = $outputExt = $magickCommand = '';
  $stopper = 0;
  $sleepTime = $SleepTimer;
  // / Locate & verify ImageMagick. A path is returned only when both succeeded.
  $imageBinary = verifyImageVersion($MinimumImageVersion);
  if ($imageBinary === FALSE) {
    $ConversionErrors = TRUE;
    errorEntry('The installed ImageMagick version is missing, unidentifiable, or too old!', 8001, FALSE); }
  else {
    // / Validate the height, width & rotate arguments.
    if (!is_numeric($height) or $height === FALSE) $height = 0;
    if (!is_numeric($width) or $width === FALSE) $width = 0;
    if (!is_numeric($rotate) or (int)$rotate === 0) $rotate = '';
    else $rotate = '-rotate '.escapeshellarg($rotate).' ';
    // / An omitted dimension is unconstrained & the other scales to fit it.
    // / An exclamation mark demands exact dimensions & accepts the distortion, which is
    // / only correct when the user supplied both & therefore asked for both.
    $wxh = (($width > 0) ? $width : '').'x'.(($height > 0) ? $height : '');
    if ($width > 0 && $height > 0) $wxh = $wxh.'!';
    if ($wxh === 'x') $wh = '';
    else $wh = '-resize '.escapeshellarg($wxh).' ';
    // / Isolate the output extension to determine if it lacks native alpha channel support.
    $outputExt = strtolower(pathinfo($newPathname, PATHINFO_EXTENSION));
    // / Flatten transparent pixels against white when exporting to a format with no alpha.
    // / Without this a transparent PNG becomes a black JPEG rather than a white one.
    if ($outputExt === 'jpg' or $outputExt === 'jpeg') $bgSwitch = '-background white -alpha remove ';
    else $bgSwitch = '-background none ';
    // / Build & sandbox the command once. It does not change between retries.
    // / The input comes FIRST. -alpha remove is an operation & needs an image already
    // / loaded, so a settings block placed before the input fails with no images found.
    $magickCommand = escapeshellarg($imageBinary).' '.escapeshellarg($pathname).' '.$bgSwitch.$wh.$rotate.escapeshellarg($newPathname);
    list ($commandMayRun, $magickCommand) = sandboxCommand($magickCommand, $pathname, $newPathname, FALSE, 'imagemagick');
    if (!$commandMayRun) {
      $ConversionErrors = TRUE;
      errorEntry('Bubblewrap is missing or non functional, so this image conversion cannot be isolated!', 8002, FALSE); }
    else {
      if ($Verbose) logEntry('Converting image.');
      // / This code will attempt the conversion up to $StopCounter number of times.
      while (!file_exists($newPathname) && $stopper <= $StopCounter) {
        // / If the last conversion attempt failed, wait a moment before trying again.
        if ($stopper !== 0) sleep($sleepTime++);
        $returnData = shell_exec($magickCommand);
        // / Count the number of conversions to avoid infinite loops.
        $stopper++;
        // / Stop attempting the conversion after $StopCounter number of attempts.
        if ($stopper === $StopCounter) {
          $ConversionErrors = TRUE;
          errorEntry('The image converter timed out!', 8000, FALSE); } }
      // / Log the output of the operation to the logfile, if it is not blank.
      if ($Verbose && trim($returnData) !== '') logEntry('ImageMagick returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
      // / The output file is the only verdict on whether the conversion produced anything.
      // / This check must stay inside the gates, or a stale output file from an earlier
      // / attempt would report success for a conversion that was refused & never ran.
      if (file_exists($newPathname)) $ConversionSuccess = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $returnData, $stopper, $pathname, $height, $width, $wxh, $rotate, $wh, $sleepTime, $outputExt, $bgSwitch, $imageBinary, $magickCommand, $commandMayRun);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $OutputFilename, $WorkerPID); }
// / -----------------------------------------------------------------------------------

?>
