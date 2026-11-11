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
// / v4.0.8.
// / The Document pipeline's converter. pipelineCore.php loads it only to dispatch a Document conversion.
// / Error block 2005, 7000 through 7001 belongs to this pipeline.
// / It declares the LibreOffice shared module, which Pipeline Core loads before it.
// / The XPS branch runs xpstopdf by name, not through a located binary.
// / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt for the contracts this file obeys.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline component cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to convert document formats.
function convertDocuments($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer, $EnableMemoryProtection;
  // / The six value pipeline contract. Success, errors, path, extension, filename & PID.
  // / The last two keep their defaults, & $WorkerPID stays 0 unless a detached process is supervised.
  $OutputFilename = basename($newPathname);
  $WorkerPID = 0;
  $ConversionSuccess = $ConversionErrors = $documentEngineStarted = $conversionCompleted = $commandMayRun = FALSE;
  $returnData = $xpsCommand = $sandboxedCommand = '';
  $stopper = $documentEnginePID = 0;
  $sleepTime = $SleepTimer;
  $arrayxpsi = array('xps', 'oxps');
  $oldExtension = getExtension($pathname);
  // / The following code verifies that the Document Conversion Engine is installed & usable.
  list ($documentEngineStarted, $documentEnginePID) = verifyDocumentConversionEngine();
  if (!$documentEngineStarted) {
    $ConversionErrors = TRUE;
    errorEntry('Could not verify the Document Conversion Engine!', 7000, FALSE); }
  else if ($Verbose) logEntry('Verified the Document Conversion Engine.');
  // / The following code performs the actual document conversion.
  if ($documentEngineStarted) {
    if ($Verbose) logEntry('Converting document.');
    // / Attempt the conversion as many times as --File Operation Retry Count-- allows, & stop at the
    // / first attempt that leaves a file.
    while (pipelineAttemptIsDue($newPathname, $stopper)) {
      // / If the last conversion attempt failed, wait a moment before trying again.
      if ($stopper !== 0) sleep($sleepTime++);
      // / An XPS file is handled by xpstopdf, which is also sandboxed & also has no network.
      // / Both arguments are escaped. An unescaped filename here was a command injection.
      if (in_array(strtolower($oldExtension), $arrayxpsi)) {
        $xpsCommand = 'xpstopdf '.escapeshellarg($pathname).' '.escapeshellarg($newPathname);
        list ($commandMayRun, $sandboxedCommand) = sandboxCommand($xpsCommand, $pathname, $newPathname, FALSE, 'poppler');
        if (!$commandMayRun) errorEntry('An XPS conversion was refused because no sandbox could be built!', 2005, FALSE);
        else $returnData = (string)shell_exec($sandboxedCommand.' 2>&1'); }
      // / Everything else goes to LibreOffice, one sandboxed process per conversion.
      else list ($conversionCompleted, $returnData) = convertWithLibreOffice($pathname, $newPathname, $extension);
      // / Count the number of conversions to avoid infinite loops.
      $stopper++; }
    // / Log the output of the operation to the logfile, if it is not blank.
    if ($Verbose && trim((string)$returnData) !== '') logEntry('The document converter returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim((string)$returnData)))));
    // / Every attempt was made & none left a file.
    // / What the converter printed is logged above, with --Enhanced Logging Verbosity-- on.
    // / A LibreOffice its ceiling killed is named in a warning above, by the shared module.
    if (!file_exists($newPathname)) {
      $ConversionErrors = TRUE;
      errorEntry('The document converter produced no file after '.$stopper.' attempt(s)!', 7001, FALSE); } }
  if (file_exists($newPathname)) $ConversionSuccess = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $stopper, $pathname, $returnData, $documentEngineStarted, $documentEnginePID, $conversionCompleted, $commandMayRun, $xpsCommand, $sandboxedCommand, $sleepTime, $oldExtension, $arrayxpsi);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $OutputFilename, $WorkerPID); }
// / -----------------------------------------------------------------------------------

?>
