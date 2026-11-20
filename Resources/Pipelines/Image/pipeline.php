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
// / v4.3.0.
// / The Image pipeline's converter. pipelineCore.php loads it only to dispatch an Image conversion.
// / Error block 8000 through 8003 belongs to this pipeline.
// / The OCR pipeline raises 8001 for the same ImageMagick.
// / ImageMagick comes from verifiedToolPath().
// / This pipeline carries its own ImageMagick policy, in Policy/policy.xml beside this file.
// / Each conversion takes it into its sandbox, where it covers the host's.
// / Nothing is written into the host.
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
// / The binary is supplied by verifiedToolPath() rather than hardcoded, so the version
// / that was verified is provably the version that runs.
// / A dimension of zero is omitted from the geometry rather than written, so the other
// / dimension scales to it. ImageMagick treats WxH as a bounding box & preserves the
// / aspect ratio, so an exclamation mark is added when the user supplied both & therefore
// / asked for both exactly.
// / A file holding several images is read whole only into a format that holds several, from v4.2.8, & the output's format is named.
function convertImages($pathname, $newPathname, $extension, $height, $width, $rotate) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer, $DirSep, $EnableMemoryProtection;
  // / The six value pipeline contract. Success, errors, path, extension, filename & PID.
  // / The last two keep their defaults, & $WorkerPID stays 0 unless a detached process is supervised.
  $OutputFilename = basename($newPathname);
  $WorkerPID = 0;
  $ConversionSuccess = $ConversionErrors = $commandMayRun = FALSE;
  $imageBinary = FALSE;
  $returnData = $wh = $wxh = $bgSwitch = $outputExt = $inputExt = $inputFrame = $outputFormat = $magickCommand = $policyFolder = $iconLimit = $inputDecoder = '';
  $decoderIsNamed = FALSE;
  $policyVariables = array();
  // / The formats this pipeline writes that hold several images, as pages, frames or sizes.
  $multiImageOutputs = array('gif', 'tif', 'tiff', 'pdf', 'webp', 'ico');
  $stopper = 0;
  $sleepTime = $SleepTimer;
  // / Locate & verify ImageMagick. A path is returned only when both succeeded.
  $imageBinary = verifiedToolPath('ImageMagick');
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
    // / An icon holds nothing over 256 pixels a side, & ImageMagick leaves an empty file for a larger one.
    // / A larger image is shrunk to fit, after any size the user asked for.
    // / A smaller one is left as it is.
    if ($outputExt === 'ico') $iconLimit = '-resize '.escapeshellarg('256x256>').' ';
    // / A layered PSD, a PDF of several pages, an animated GIF or a TIFF of several pages holds several images. Read whole into a
    // / format that holds one, ImageMagick writes a numbered file for each & never the file asked for, so the conversion failed.
    // / Such an output is made from the first image. A PSD is always read as its first, which is the image its layers flatten to.
    // / The frame is escaped apart from the path, so the sandbox finds the path it rewrites & no shell reads the brackets as a pattern.
    $inputExt = strtolower(pathinfo($pathname, PATHINFO_EXTENSION));
    if ($inputExt === 'psd' or !in_array($outputExt, $multiImageOutputs, TRUE)) $inputFrame = escapeshellarg('[0]');
    // / The input is read with the decoder imageMagickDecoder() names, from v4.3.0, & with no other, whatever a policy says, so PostScript,
    // / an SVG or an MVG named as an image is handed to that image's decoder, which cannot read it. The decoder is escaped apart from the
    // / path, as the frame is, so the sandbox finds the path it rewrites.
    list ($decoderIsNamed, $inputDecoder) = imageMagickDecoder($pathname, $inputExt);
    // / The output is written in the format its extension names, & ImageMagick is told so. Asked for a format it cannot write, it
    // / otherwise warns & writes the format it read under the name asked for, so a host without an AVIF encoder handed out a PNG named avif.
    $outputFormat = escapeshellarg(strtoupper($outputExt).':');
    // / Build & sandbox the command once. It does not change between retries.
    // / The input comes FIRST. -alpha remove is an operation & needs an image already
    // / loaded, so a settings block placed before the input fails with no images found.
    $magickCommand = escapeshellarg($imageBinary).' '.escapeshellarg($inputDecoder.':').escapeshellarg($pathname).$inputFrame.' '.$bgSwitch.$wh.$rotate.$iconLimit.$outputFormat.escapeshellarg($newPathname);
    // / The policy folder is the sandbox's read only resource, where the profile's Overlays put its
    // / policy.xml in place of the host's, & it is named in MAGICK_CONFIGURE_PATH, which ImageMagick
    // / reads before its own configuration, so a host whose policy sits somewhere else still loads
    // / this one first.
    $policyFolder = dirname(__FILE__).$DirSep.'Policy';
    if (is_file($policyFolder.$DirSep.'policy.xml')) $policyVariables = array('MAGICK_CONFIGURE_PATH' => $policyFolder);
    else {
      $policyFolder = '';
      warningEntry('The ImageMagick policy the Image pipeline carries is missing, so this conversion runs under the host\'s own. Restore the pipeline from the release this installation came from.'); }
    // / The policy.xml the installed ImageMagick reads is covered too, wherever its build keeps it.
    if ($policyFolder !== '' && function_exists('coverResolvedImageMagickPolicy')) coverResolvedImageMagickPolicy('imagemagick');
    list ($commandMayRun, $magickCommand) = sandboxCommand($magickCommand, $pathname, $newPathname, FALSE, 'imagemagick', $policyFolder, $policyVariables);
    // / A file named pdf that holds no PDF is the one file no decoder is named for, because Ghostscript would read it as PostScript.
    if (!$decoderIsNamed) {
      $ConversionErrors = TRUE;
      errorEntry('A file named pdf that holds no PDF was refused, so ImageMagick never read it!', 8003, FALSE); }
    else if (!$commandMayRun) {
      $ConversionErrors = TRUE;
      errorEntry('Bubblewrap is missing or non functional, so this image conversion cannot be isolated!', 8002, FALSE); }
    else {
      if ($Verbose) logEntry('Converting image.');
      // / Attempt the conversion as many times as --File Operation Retry Count-- allows, & stop at the
      // / first attempt that leaves a file.
      while (pipelineAttemptIsDue($newPathname, $stopper)) {
        // / If the last conversion attempt failed, wait a moment before trying again.
        if ($stopper !== 0) sleep($sleepTime++);
        $returnData = (string)shell_exec($magickCommand.' 2>&1');
        // / Count the number of conversions to avoid infinite loops.
        $stopper++; }
      // / Log the output of the operation to the logfile, if it is not blank.
      if ($Verbose && trim($returnData) !== '') logEntry('ImageMagick returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
      // / Every attempt was made & none left a file.
      // / What the converter printed is logged above, with --Enhanced Logging Verbosity-- on.
      if (!file_exists($newPathname)) {
        $ConversionErrors = TRUE;
        errorEntry('The image converter produced no file after '.$stopper.' attempt(s)!', 8000, FALSE); }
      // / The output file is the only verdict on whether the conversion produced anything.
      // / This check must stay inside the gates, or a stale output file from an earlier
      // / attempt would report success for a conversion that was refused & never ran.
      if (file_exists($newPathname)) $ConversionSuccess = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $returnData, $stopper, $pathname, $height, $width, $wxh, $rotate, $wh, $sleepTime, $outputExt, $inputExt, $inputFrame, $outputFormat, $multiImageOutputs, $bgSwitch, $iconLimit, $imageBinary, $magickCommand, $commandMayRun, $policyFolder, $policyVariables, $decoderIsNamed, $inputDecoder);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $OutputFilename, $WorkerPID); }
// / -----------------------------------------------------------------------------------

?>
