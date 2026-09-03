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
// / This file is the converter for the Ebook pipeline. It is loaded by pipelineCore.php
// / ONLY when a Ebook conversion is about to be dispatched to it, so a request that
// / converts something else never parses a line of it.
// / Error block 30000 through 30005 belongs to this pipeline. Those numbers came with the code when it
// / moved out of convertCore.php & they did not change, because operators have read them.
// / This pipeline calls verifyEbookVersion(), sandboxCommand() & locateDependency(), which all
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
// / A function to convert e-book formats.
// / Calibre is one utility covering every e-book format in both directions, so this is a
// / single stage pipeline with no intermediate file & no second dependency.
// / ebook-convert INFERS THE OUTPUT FORMAT FROM THE OUTPUT FILE EXTENSION rather than from
// / a flag. The extension therefore has to survive the trip into the sandbox, & it does,
// / because sandboxCommand() rewrites the directory & keeps the basename intact.
// / Calibre insists on a writable HOME & builds a configuration directory on first run.
// / The sandbox sets HOME to a tmpfs, so that directory is built fresh inside the namespace
// / on every conversion & is discarded with it. That costs a little time per conversion &
// / buys a guarantee that nothing a hostile book does to Calibre's configuration survives.
// / Calibre also reaches the network for metadata & update checks when it is allowed to.
// / The sandbox unshares the network, which closes that off without needing a flag.
// / --no-default-epub-cover is deliberately NOT passed. A generated cover is expected
// / behaviour & suppressing it surprises users who converted a book that had no cover.
function convertEbooks($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer, $MinimumCalibreVersion, $EnableMemoryProtection;
  // / The six value pipeline contract. Success, errors, path, extension, filename & PID.
  // / This converter produces neither of the last two itself, so it declares the defaults.
  // / $OutputFilename is the name the user is shown. $WorkerPID stays zero unless a
  // / detached process must be supervised after the connection to the user is closed.
  $OutputFilename = basename($newPathname);
  $WorkerPID = 0;
  $ConversionSuccess = $ConversionErrors = $commandMayRun = FALSE;
  $ebookBinary = FALSE;
  $returnData = $ebookCommand = '';
  $stopper = 0;
  $sleepTime = $SleepTimer;
  // / Locate & verify Calibre. A path is returned only when both succeeded.
  $ebookBinary = verifyEbookVersion($MinimumCalibreVersion);
  if ($ebookBinary === FALSE) {
    $ConversionErrors = TRUE;
    errorEntry('The installed Calibre version is missing, unidentifiable, or too old!', 30001, FALSE); }
  else {
    // / Build & sandbox the command once. It does not change between retries.
    // / The output extension IS the format selector, so no format flag is passed.
    $ebookCommand = escapeshellarg($ebookBinary).' '.escapeshellarg($pathname).' '.escapeshellarg($newPathname);
    list ($commandMayRun, $ebookCommand) = sandboxCommand($ebookCommand, $pathname, $newPathname, FALSE, 'calibre');
    if (!$commandMayRun) {
      $ConversionErrors = TRUE;
      errorEntry('Bubblewrap is missing or non functional, so this e-book conversion cannot be isolated!', 30002, FALSE); }
    else {
      if ($Verbose) logEntry('Converting e-book to '.$extension.'.');
      // / This code will attempt the conversion up to $StopCounter number of times.
      while (!file_exists($newPathname) && $stopper <= $StopCounter) {
        // / If the last conversion attempt failed, wait a moment before trying again.
        if ($stopper !== 0) sleep($sleepTime++);
        $returnData = shell_exec($ebookCommand);
        // / Count the number of conversions to avoid infinite loops.
        $stopper++;
        // / Stop attempting the conversion after $StopCounter number of attempts.
        if ($stopper === $StopCounter) {
          $ConversionErrors = TRUE;
          errorEntry('The e-book converter timed out!', 30000, FALSE); } }
      // / Log the output of the operation to the logfile, if it is not blank.
      // / Calibre is verbose by default & reports its whole conversion pipeline, which is
      // / genuinely useful when a book converts but comes out wrong.
      if ($Verbose && trim($returnData) !== '') logEntry('Calibre returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
      // / The output file is the only verdict on whether the conversion produced anything.
      // / This check must stay inside the gates, or a stale output file from an earlier
      // / attempt would report success for a conversion that was refused & never ran.
      if (file_exists($newPathname)) $ConversionSuccess = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $returnData, $stopper, $pathname, $sleepTime, $ebookBinary, $ebookCommand, $commandMayRun);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $OutputFilename, $WorkerPID); }
// / -----------------------------------------------------------------------------------

?>
