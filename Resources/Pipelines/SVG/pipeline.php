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
// / v4.2.8.
// / The SVG pipeline's converter. pipelineCore.php loads it only to dispatch an SVG conversion.
// / Error block 25000 through 25002 belongs to this pipeline.
// / Inkscape & Resvg come from verifiedToolPath().
// / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt for the contracts this file obeys.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline converter cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to render an SVG drawing to a PNG with Resvg, before Inkscape is asked, from v4.2.5.
// / Accepts the drawing, the PNG to write, the height & the width, in that order. Returns whether Resvg wrote the PNG.
// / Where it did not, Inkscape renders the drawing, as it did before v4.2.5.
// / Resvg keeps a drawing's proportions & fits it to the one dimension given, as Inkscape does with one. Inkscape stretches a drawing to a
// / width & a height given together, so convertSVG() leaves a drawing given both to Inkscape, & it renders as it always has.
// / Resvg finds fonts itself & makes none of the substitutions fontconfig makes for Inkscape, so the generic families are named for it, &
// / a family the server does not have is drawn in DejaVu Sans, which is what fontconfig gives Inkscape for a family it knows nothing of.
// / Where a family still finds no font, Resvg leaves the text out & says so, & the PNG is put aside for Inkscape's, because a PNG missing
// / its text is not the drawing.
// / The function is longer than most, so its return value is its sanity check.
function svgRenderWithResvg($pathname, $newPathname, $height, $width) {
  // / Set variables.
  global $Verbose, $Lol, $SleepTimer, $EnableMemoryProtection;
  $PngWasRendered = FALSE;
  $resvgBinary = FALSE;
  $commandMayRun = $textWasLost = FALSE;
  $sizeArgument = $resvgCommand = $outputLine = '';
  $commandOutput = array();
  $exitStatus = -1;
  $stopper = 0;
  $sleepTime = $SleepTimer;
  // / Locate & verify Resvg. A path is returned only when both succeeded. It is optional, so a server without it renders with Inkscape.
  $resvgBinary = verifiedToolPath('Resvg');
  if ($resvgBinary === FALSE) {
    if ($Verbose) logEntry('Resvg is not installed, so Inkscape renders this SVG.'); }
  else {
    if (!empty($width) && is_numeric($width) && $width > 0) $sizeArgument = ' --width '.escapeshellarg((string)(int)$width);
    else if (!empty($height) && is_numeric($height) && $height > 0) $sizeArgument = ' --height '.escapeshellarg((string)(int)$height);
    // / Build & sandbox the command once. It does not change between retries. A drawing that never finishes is stopped after 120 seconds.
    $resvgCommand = 'timeout 120 '.escapeshellarg($resvgBinary).' --sans-serif-family '.escapeshellarg('DejaVu Sans').' --serif-family '.escapeshellarg('DejaVu Serif')
      .' --monospace-family '.escapeshellarg('DejaVu Sans Mono').' --font-family '.escapeshellarg('DejaVu Sans').$sizeArgument.' '.escapeshellarg($pathname).' '.escapeshellarg($newPathname);
    list ($commandMayRun, $resvgCommand) = sandboxCommand($resvgCommand, $pathname, $newPathname, FALSE, 'resvg');
    // / Where no sandbox may be built, Inkscape is asked, & refuses with this pipeline's own error.
    if ($commandMayRun) {
      if ($Verbose) logEntry('Rendering SVG with Resvg.');
      // / Attempt the render as many times as --File Operation Retry Count-- allows, & stop at the first attempt that leaves a file.
      while (pipelineAttemptIsDue($newPathname, $stopper)) {
        // / If the last attempt failed, wait a moment before trying again.
        if ($stopper !== 0) sleep($sleepTime++);
        redeclare($commandOutput, array());
        exec($resvgCommand.' 2>&1', $commandOutput, $exitStatus);
        // / Resvg ends with a status other than 0 where it failed, & a file it had begun is not the drawing, so it is removed.
        if ($exitStatus !== 0) @unlink($newPathname);
        // / Count the number of attempts to avoid infinite loops.
        $stopper++; }
      // / Log the output of the operation to the logfile, if it is not blank.
      if ($Verbose && trim(implode($Lol, $commandOutput)) !== '') logEntry('Resvg returned the following: '.$Lol.'  '.implode($Lol.'  ', array_slice($commandOutput, 0, 40)));
      foreach ($commandOutput as $outputLine) if (strpos((string)$outputLine, 'No match for') !== FALSE) $textWasLost = TRUE;
      if (file_exists($newPathname) && $textWasLost) {
        @unlink($newPathname);
        warningEntry('Resvg found no font for some text in '.basename((string)$newPathname).', so Inkscape renders it. Installing the DejaVu fonts lets Resvg draw the text.'); }
      else if (!file_exists($newPathname)) warningEntry('Resvg could not render '.basename((string)$newPathname).', so Inkscape renders it.');
      if (file_exists($newPathname)) $PngWasRendered = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $resvgBinary, $commandMayRun, $textWasLost, $sizeArgument, $resvgCommand, $outputLine, $commandOutput, $exitStatus, $stopper, $sleepTime, $pathname, $newPathname, $height, $width);
  return $PngWasRendered; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert SVG vector drawing formats.
// / The binary is supplied by verifiedToolPath() rather than assumed.
// / A width & a height may both be supplied. Inkscape honours both independently, which
// / stretches the image, so the caller decides whether that is what the user asked for.
// / An SVG or an SVGZ becoming a PNG, with no size or one dimension, is rendered by Resvg first, from v4.2.5, & by Inkscape where Resvg did not.
function convertSVG($pathname, $newPathname, $extension, $height, $width) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer, $EnableMemoryProtection;
  // / The six value pipeline contract. Success, errors, path, extension, filename & PID.
  // / The last two keep their defaults, & $WorkerPID stays 0 unless a detached process is supervised.
  $OutputFilename = basename($newPathname);
  $WorkerPID = 0;
  $ConversionSuccess = $ConversionErrors = $commandMayRun = $renderedByResvg = FALSE;
  $svgBinary = FALSE;
  $returnData = $argEcho = $inkscapeCommand = $inputExtension = '';
  $stopper = 0;
  $sleepTime = $SleepTimer;
  // / Build the export sizing arguments. A dimension of zero is not passed at all.
  if (!empty($width) && $width > 0) $argEcho = '--export-width='.escapeshellarg($width);
  if (!empty($height) && $height > 0) $argEcho = trim($argEcho.' --export-height='.escapeshellarg($height));
  if ($argEcho !== '') $argEcho = $argEcho.' ';
  // / Resvg is asked first where it can render what was asked for. Two dimensions are left to Inkscape, because only Inkscape stretches.
  $inputExtension = strtolower((string)pathinfo((string)$pathname, PATHINFO_EXTENSION));
  if (strtolower((string)$extension) === 'png' && ($inputExtension === 'svg' or $inputExtension === 'svgz') && !(!empty($width) && $width > 0 && !empty($height) && $height > 0)) $renderedByResvg = svgRenderWithResvg($pathname, $newPathname, $height, $width);
  // / Locate & verify Inkscape, unless Resvg rendered the drawing. A path is returned only when both succeeded.
  if (!$renderedByResvg) $svgBinary = verifiedToolPath('Inkscape');
  if ($renderedByResvg) $ConversionSuccess = TRUE;
  else if ($svgBinary === FALSE) {
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
  purgeSensitiveMemory($EnableMemoryProtection, $returnData, $stopper, $pathname, $height, $width, $sleepTime, $argEcho, $svgBinary, $inkscapeCommand, $commandMayRun, $renderedByResvg, $inputExtension);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $OutputFilename, $WorkerPID); }
// / -----------------------------------------------------------------------------------

?>
