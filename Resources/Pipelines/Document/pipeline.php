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
// / This file is the converter for the Document pipeline. It is loaded by pipelineCore.php
// / ONLY when a Document operation is about to be dispatched to it, so a request that does
// / something else never parses a line of it.
// / Error block 7000 through 7001, plus 2005 belongs to this pipeline. Those numbers came with the code when it
// / moved out of convertCore.php & they did not change, because operators have read them.
// / This pipeline declares the LibreOffice shared module & the manager loads it first.
// / convertWithLibreOffice(), sanitizeDocumentLinks() & verifyDocumentConversionEngine()
// / are all available by the time this file runs. It also calls sandboxCommand(),
// / locateDependency() & getExtension(), which remain in convertCore.php.
// / The XPS branch invokes xpstopdf by name rather than through a located binary, which is
// / how it arrived from convertCore.php & is not something this move changed.
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
  // / This converter produces neither of the last two itself, so it declares the defaults.
  // / $OutputFilename is the name the user is shown. $WorkerPID stays zero unless a
  // / detached process must be supervised after the connection to the user is closed.
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
    // / This code will attempt the conversion up to $StopCounter number of times.
    while (!file_exists($newPathname) && $stopper <= $StopCounter) {
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
      $stopper++;
      // / Stop attempting the conversion after $StopCounter number of attempts.
      if ($stopper === $StopCounter) {
        $ConversionErrors = TRUE;
        errorEntry('The document converter timed out!', 7001, FALSE); } }
    // / Log the output of the operation to the logfile, if it is not blank.
    if ($Verbose && trim($returnData) !== '') logEntry('The document converter returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData))))); }
  if (file_exists($newPathname)) $ConversionSuccess = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $stopper, $pathname, $returnData, $documentEngineStarted, $documentEnginePID, $conversionCompleted, $commandMayRun, $xpsCommand, $sandboxedCommand, $sleepTime, $oldExtension, $arrayxpsi);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $OutputFilename, $WorkerPID); }
// / -----------------------------------------------------------------------------------

?>
