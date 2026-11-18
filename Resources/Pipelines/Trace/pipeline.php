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
// / v4.2.7.
// / The Trace pipeline's converter. pipelineCore.php loads it only to dispatch a Trace conversion.
// / Error block 46000 through 46006 belongs to this pipeline.
// / Potrace & ImageMagick come from verifiedToolPath().
// / This pipeline carries its own ImageMagick policy, in Policy/policy.xml beside this file.
// / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt for the contracts this file obeys.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline converter cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to name what reads an image & what writes a traced one.
// / Accepts the extension of the image & the extension asked for, in that order.
// / Returns the ImageMagick decoder the image needs, the Potrace backend that writes the output, & whether Potrace reads the image itself, in that order.
// / The decoder is empty where Potrace reads the image itself, & both names are empty where nothing here reads or writes the format.
// / ImageMagick is told which decoder to use rather than left to guess from the contents, so a file named as an image is read as that image or not at all.
function traceFormatNames($inputExtension, $outputExtension) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ImageDecoder = $PotraceBackend = '';
  $PotraceReadsImage = FALSE;
  $nativeFormats = array('bmp', 'pbm', 'pgm', 'ppm', 'pnm');
  $imageDecoders = array('png' => 'png', 'jpg' => 'jpeg', 'jpeg' => 'jpeg', 'jpe' => 'jpeg', 'gif' => 'gif', 'tif' => 'tiff', 'tiff' => 'tiff', 'webp' => 'webp',
    'heic' => 'heic', 'heif' => 'heic', 'avif' => 'avif', 'jp2' => 'jp2', 'j2k' => 'j2k', 'ico' => 'ico', 'tga' => 'tga', 'pcx' => 'pcx', 'psd' => 'psd', 'dds' => 'dds', 'xpm' => 'xpm');
  $potraceBackends = array('svg' => 'svg', 'dxf' => 'dxf', 'pdf' => 'pdf', 'eps' => 'eps', 'ps' => 'ps');
  $inputExtension = strtolower((string)$inputExtension);
  $outputExtension = strtolower((string)$outputExtension);
  if (in_array($inputExtension, $nativeFormats, TRUE)) $PotraceReadsImage = TRUE;
  else if (isset($imageDecoders[$inputExtension])) $ImageDecoder = $imageDecoders[$inputExtension];
  if (isset($potraceBackends[$outputExtension])) $PotraceBackend = $potraceBackends[$outputExtension];
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $nativeFormats, $imageDecoders, $potraceBackends, $inputExtension, $outputExtension);
  return array($ImageDecoder, $PotraceBackend, $PotraceReadsImage); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to reduce an image Potrace cannot read to a grey bitmap it can.
// / Accepts the staged image, the decoder that reads it & the work folder, in that order.
// / Returns whether the bitmap was made & its path, in that order. The path is empty where none was made.
// / The image is copied into the work folder as source, & ImageMagick reads it there by the decoder named, takes its first frame, lays it on
// / white so a transparent part reads as background, & writes it as an 8 bit grey PGM. Both files are named by name alone, so nothing a user
// / named reaches the command, & the decoder comes from traceFormatNames(), never from the file.
// / The conversion runs under the policy this pipeline carries, exactly as an Image pipeline conversion runs under its own.
// / The function is longer than most, so its first return value is its sanity check.
function traceReduceImage($pathname, $imageDecoder, $workFolder) {
  // / Set variables.
  global $Verbose, $Lol, $DirSep, $SleepTimer, $EnableMemoryProtection;
  $BitmapWasMade = FALSE;
  $BitmapPath = '';
  $imageBinary = FALSE;
  $commandMayRun = FALSE;
  $sourcePath = $bitmapPath = $magickCommand = $policyFolder = $commandOutput = '';
  $policyVariables = array();
  $exitStatus = -1;
  $stopper = 0;
  $sleepTime = $SleepTimer;
  $sourcePath = $workFolder.$DirSep.'source';
  $bitmapPath = $workFolder.$DirSep.'bitmap.pgm';
  // / Locate & verify ImageMagick. A path is returned only when both succeeded.
  $imageBinary = verifiedToolPath('ImageMagick');
  if ($imageBinary === FALSE) errorEntry('The installed ImageMagick version is missing, unidentifiable, or too old, & Potrace reads only BMP & the PNM formats itself!', 46003, FALSE);
  else if (!@copy($pathname, $sourcePath)) errorEntry('Could not stage '.basename((string)$pathname).' for tracing!', 46005, FALSE);
  else {
    // / -delete 1--1 keeps the first frame of an animation or a multi page TIFF, & leaves an image of one frame as it is.
    $magickCommand = escapeshellarg($imageBinary).' '.escapeshellarg($imageDecoder.':source').' -delete '.escapeshellarg('1--1').' -background white -alpha remove -alpha off -colorspace Gray -depth 8 bitmap.pgm';
    // / The policy folder is the sandbox's read only resource, & is named in MAGICK_CONFIGURE_PATH, as the Image pipeline names its own.
    $policyFolder = dirname(__FILE__).$DirSep.'Policy';
    if (is_file($policyFolder.$DirSep.'policy.xml')) $policyVariables = array('MAGICK_CONFIGURE_PATH' => $policyFolder);
    else {
      $policyFolder = '';
      warningEntry('The ImageMagick policy the Trace pipeline carries is missing, so this image is reduced under the host\'s own. Restore the pipeline from the release this installation came from.'); }
    // / The policy.xml the installed ImageMagick reads is covered too, wherever its build keeps it.
    if ($policyFolder !== '' && function_exists('coverResolvedImageMagickPolicy')) coverResolvedImageMagickPolicy('imagemagick');
    list ($commandMayRun, $magickCommand) = sandboxCommand($magickCommand, $sourcePath, $bitmapPath, FALSE, 'imagemagick', $policyFolder, $policyVariables);
    if (!$commandMayRun) errorEntry('Bubblewrap is missing or non functional, so this image cannot be traced in isolation!', 46002, FALSE);
    else {
      if ($Verbose) logEntry('Reducing an image to a bitmap for tracing.');
      // / Attempt the reduction as many times as --File Operation Retry Count-- allows, & stop at the first attempt that leaves a bitmap.
      while (pipelineAttemptIsDue($bitmapPath, $stopper)) {
        // / If the last attempt failed, wait a moment before trying again.
        if ($stopper !== 0) sleep($sleepTime++);
        list ($exitStatus, $commandOutput) = runInWorkFolder($magickCommand, $workFolder);
        // / Count the number of attempts to avoid infinite loops.
        $stopper++; }
      if ($Verbose && trim($commandOutput) !== '') logEntry('ImageMagick returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', trim($commandOutput)));
      if (!file_exists($bitmapPath) or @filesize($bitmapPath) === 0) errorEntry('ImageMagick could not reduce '.basename((string)$pathname).' to a bitmap after '.$stopper.' attempt(s)!', 46004, FALSE);
      else {
        $BitmapWasMade = TRUE;
        $BitmapPath = $bitmapPath; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $imageBinary, $commandMayRun, $sourcePath, $bitmapPath, $magickCommand, $policyFolder, $commandOutput, $policyVariables, $exitStatus, $stopper, $sleepTime, $pathname, $imageDecoder, $workFolder);
  return array($BitmapWasMade, $BitmapPath); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to trace a bitmap into vector outlines with Potrace.
// / Potrace reads BMP & the PNM formats itself. Any other image is reduced to a grey bitmap by traceReduceImage() first, in a work folder of
// / the conversion's own, which is taken away afterwards.
// / Potrace draws the dark parts of the bitmap, darker than half way to white, as filled outlines the size of the image, in the format asked for.
// / The function is longer than most, so it returns the six value contract, whose first value is its sanity check.
function traceImages($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $Lol, $SleepTimer, $EnableMemoryProtection;
  // / The six value pipeline contract. Success, errors, path, extension, filename & PID.
  $OutputFilename = basename($newPathname);
  $WorkerPID = 0;
  $ConversionSuccess = $ConversionErrors = $commandMayRun = $folderWasMade = $bitmapWasMade = $potraceReadsImage = FALSE;
  $potraceBinary = FALSE;
  $imageDecoder = $potraceBackend = $workFolder = $bitmapPath = $potraceCommand = $returnData = '';
  $stopper = 0;
  $sleepTime = $SleepTimer;
  list ($imageDecoder, $potraceBackend, $potraceReadsImage) = traceFormatNames(pathinfo((string)$pathname, PATHINFO_EXTENSION), $extension);
  // / Locate & verify Potrace. A path is returned only when both succeeded.
  $potraceBinary = verifiedToolPath('Potrace');
  if ($potraceBinary === FALSE) {
    $ConversionErrors = TRUE;
    errorEntry('The installed Potrace version is missing, unidentifiable, or too old!', 46001, FALSE); }
  else if ($potraceBackend === '' or (!$potraceReadsImage && $imageDecoder === '')) {
    $ConversionErrors = TRUE;
    errorEntry('The Trace pipeline cannot read '.strtolower((string)pathinfo((string)$pathname, PATHINFO_EXTENSION)).' or cannot write '.strtolower((string)$extension).'!', 46006, FALSE); }
  else if ($potraceReadsImage) $bitmapPath = (string)$pathname;
  else {
    // / An image Potrace cannot read is reduced in a folder of the conversion's own, beside the staged image.
    list ($folderWasMade, $workFolder) = makeWorkFolder($pathname, 'trace');
    if (!$folderWasMade) errorEntry('Could not stage '.basename((string)$pathname).' for tracing!', 46005, FALSE);
    else list ($bitmapWasMade, $bitmapPath) = traceReduceImage($pathname, $imageDecoder, $workFolder);
    // / traceReduceImage() has logged why it made no bitmap.
    if (!$bitmapWasMade) $ConversionErrors = TRUE; }
  if (!$ConversionErrors) {
    // / Build & sandbox the command once. It does not change between retries.
    $potraceCommand = escapeshellarg($potraceBinary).' -b '.escapeshellarg($potraceBackend).' -o '.escapeshellarg($newPathname).' '.escapeshellarg($bitmapPath);
    list ($commandMayRun, $potraceCommand) = sandboxCommand($potraceCommand, $bitmapPath, $newPathname, FALSE, 'potrace');
    if (!$commandMayRun) {
      $ConversionErrors = TRUE;
      errorEntry('Bubblewrap is missing or non functional, so this image cannot be traced in isolation!', 46002, FALSE); }
    else {
      if ($Verbose) logEntry('Tracing an image.');
      // / Attempt the tracing as many times as --File Operation Retry Count-- allows, & stop at the first attempt that leaves a file.
      while (pipelineAttemptIsDue($newPathname, $stopper)) {
        // / If the last attempt failed, wait a moment before trying again.
        if ($stopper !== 0) sleep($sleepTime++);
        $returnData = (string)shell_exec($potraceCommand.' 2>&1');
        // / Count the number of attempts to avoid infinite loops.
        $stopper++; }
      // / Log the output of the operation to the logfile, if it is not blank.
      if ($Verbose && trim($returnData) !== '') logEntry('Potrace returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', trim($returnData)));
      // / Every attempt was made & none left a file.
      if (!file_exists($newPathname)) {
        $ConversionErrors = TRUE;
        errorEntry('The image tracer produced no file after '.$stopper.' attempt(s)!', 46000, FALSE); }
      // / The output file is the only verdict on whether the conversion produced anything.
      if (file_exists($newPathname)) $ConversionSuccess = TRUE; } }
  if ($folderWasMade) removeWorkFolder($workFolder);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $commandMayRun, $folderWasMade, $bitmapWasMade, $potraceReadsImage, $potraceBinary, $imageDecoder, $potraceBackend, $workFolder, $bitmapPath, $potraceCommand, $returnData, $stopper, $sleepTime, $pathname);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $OutputFilename, $WorkerPID); }
// / -----------------------------------------------------------------------------------

?>
