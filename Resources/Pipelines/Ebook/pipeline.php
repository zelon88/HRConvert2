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
// / The Ebook pipeline's converter. pipelineCore.php loads it only to dispatch an Ebook conversion.
// / Error block 30000 through 30002 belongs to this pipeline.
// / Calibre comes from verifiedToolPath().
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
// / Calibre covers every e-book format in both directions, so this is one stage with one dependency.
// / ebook-convert infers the output format from the output file extension, which sandboxCommand() keeps.
// / Calibre builds its configuration under HOME, which the sandbox makes a tmpfs.
// / Nothing a book does to that configuration survives the conversion.
// / The sandbox unshares the network, which stops Calibre's metadata & update checks.
// / --no-default-epub-cover is not passed, because a generated cover is expected behaviour.
function convertEbooks($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer, $EnableMemoryProtection;
  // / The six value pipeline contract. Success, errors, path, extension, filename & PID.
  // / The last two keep their defaults, & $WorkerPID stays 0 unless a detached process is supervised.
  $OutputFilename = basename($newPathname);
  $WorkerPID = 0;
  $ConversionSuccess = $ConversionErrors = $commandMayRun = FALSE;
  $ebookBinary = FALSE;
  $returnData = $ebookCommand = '';
  $stopper = 0;
  $sleepTime = $SleepTimer;
  // / Locate & verify Calibre. A path is returned only when both succeeded.
  $ebookBinary = verifiedToolPath('Calibre');
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
      // / Attempt the conversion as many times as --File Operation Retry Count-- allows, & stop at the
      // / first attempt that leaves a file.
      while (pipelineAttemptIsDue($newPathname, $stopper)) {
        // / If the last conversion attempt failed, wait a moment before trying again.
        if ($stopper !== 0) sleep($sleepTime++);
        $returnData = (string)shell_exec($ebookCommand.' 2>&1');
        // / Count the number of conversions to avoid infinite loops.
        $stopper++; }
      // / Log the output of the operation to the logfile, if it is not blank.
      // / Calibre is verbose by default & reports its whole conversion pipeline, which is
      // / genuinely useful when a book converts but comes out wrong.
      if ($Verbose && trim($returnData) !== '') logEntry('Calibre returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
      // / Every attempt was made & none left a file.
      // / What the converter printed is logged above, with --Enhanced Logging Verbosity-- on.
      if (!file_exists($newPathname)) {
        $ConversionErrors = TRUE;
        errorEntry('The e-book converter produced no file after '.$stopper.' attempt(s)!', 30000, FALSE); }
      // / The output file is the only verdict on whether the conversion produced anything.
      // / This check must stay inside the gates, or a stale output file from an earlier
      // / attempt would report success for a conversion that was refused & never ran.
      if (file_exists($newPathname)) $ConversionSuccess = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $returnData, $stopper, $pathname, $sleepTime, $ebookBinary, $ebookCommand, $commandMayRun);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $OutputFilename, $WorkerPID); }
// / -----------------------------------------------------------------------------------

?>
