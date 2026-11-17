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
// / v4.2.6.
// / The Graph pipeline's converter. pipelineCore.php loads it only to dispatch a Graph conversion.
// / Error block 49000 through 49004 belongs to this pipeline.
// / Graphviz comes from verifiedToolPath().
// / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt for the contracts this file obeys.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline converter cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to name the Graphviz output format for a conversion.
// / Accepts the extension of the graph & the extension asked for, in that order.
// / Returns the format Graphviz is asked to write, or an empty string where this pipeline does not read the graph or write that format.
// / Graphviz is always told which format to write, so it never guesses one from a file's name.
function graphFormatName($inputExtension, $outputExtension) {
  // / Set variables.
  global $EnableMemoryProtection;
  $GraphvizFormat = '';
  $graphvizFormats = array('svg' => 'svg', 'png' => 'png', 'pdf' => 'pdf', 'jpg' => 'jpg', 'gif' => 'gif', 'webp' => 'webp', 'eps' => 'eps', 'ps' => 'ps');
  $inputExtension = strtolower((string)$inputExtension);
  $outputExtension = strtolower((string)$outputExtension);
  if ($inputExtension === 'gv' && isset($graphvizFormats[$outputExtension])) $GraphvizFormat = $graphvizFormats[$outputExtension];
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $graphvizFormats, $inputExtension, $outputExtension);
  return $GraphvizFormat; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to find why Graphviz made no file, in what it printed.
// / Accepts the lines Graphviz printed. Returns the line that says why, cleaned to print, or an empty string where there is none.
// / A line Graphviz marked as an error is said first, because Graphviz stops at the first. Otherwise the last line that is not a warning is said.
// / What a graph says can be in that line, so a control character becomes a space & the line is cut at 200 characters before anything prints it.
function graphFirstError($outputLines) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FirstError = '';
  $outputLine = $graphError = $lastLine = '';
  foreach ((array)$outputLines as $outputLine) {
    $outputLine = trim((string)preg_replace('/[\x00-\x1F\x7F]+/', ' ', (string)$outputLine));
    if ($outputLine === '' or stripos($outputLine, 'Warning:') === 0) continue;
    if ($graphError === '' && stripos($outputLine, 'Error:') === 0) $graphError = $outputLine;
    $lastLine = $outputLine; }
  $FirstError = substr(($graphError !== '' ? $graphError : $lastLine), 0, 200);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $outputLine, $graphError, $lastLine, $outputLines);
  return $FirstError; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to draw a graph written in Graphviz's DOT language as a picture.
// / dot lays the graph out, with the engine the graph's own layout attribute names, or with its own where it names none.
// / A graph can name an image or a shape file for a node, which Graphviz would read from wherever the graph says. SERVER_NAME is set for it,
// / so it reads no file but the graph, as Graphviz does for a web server, & leaves out what was named, which is warned about.
// / The setting is passed with the command rather than kept in the graphviz sandbox profile, so it holds where no sandbox could be built too.
// / --Graph Conversion Timeout-- stops it, because a large graph can take a layout engine a very long time.
// / The function is longer than most, so it returns the six value contract, whose first value is its sanity check.
function convertGraph($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $Lol, $SleepTimer, $GraphConversionTimeout, $EnableMemoryProtection;
  // / The six value pipeline contract. Success, errors, path, extension, filename & PID.
  $OutputFilename = basename($newPathname);
  $WorkerPID = 0;
  $ConversionSuccess = $ConversionErrors = $commandMayRun = $timedOut = $fileWasRefused = FALSE;
  $graphvizBinary = FALSE;
  $graphvizFormat = $graphvizCommand = $firstError = $outputLine = '';
  $commandOutput = $graphvizEnvironment = array();
  $exitStatus = -1;
  $stopper = 0;
  $sleepTime = $SleepTimer;
  // / A local fallback configuration cannot overwrite, for a timeout that is missing or is not a whole number of seconds greater than 0.
  $defaultTimeout = 120;
  $timeoutSeconds = (is_numeric($GraphConversionTimeout) && (int)$GraphConversionTimeout > 0) ? (int)$GraphConversionTimeout : $defaultTimeout;
  if ($timeoutSeconds === $defaultTimeout && (string)$GraphConversionTimeout !== (string)$defaultTimeout) warningEntry('--Graph Conversion Timeout-- is not a whole number of seconds greater than 0, so '.$defaultTimeout.' is used.');
  $graphvizFormat = graphFormatName(pathinfo((string)$pathname, PATHINFO_EXTENSION), $extension);
  // / Locate & verify Graphviz. A path is returned only when both succeeded.
  $graphvizBinary = verifiedToolPath('Graphviz');
  if ($graphvizBinary === FALSE) {
    $ConversionErrors = TRUE;
    errorEntry('The installed Graphviz version is missing, unidentifiable, or too old!', 49001, FALSE); }
  else if ($graphvizFormat === '') {
    $ConversionErrors = TRUE;
    errorEntry('The Graph pipeline cannot read '.strtolower((string)pathinfo((string)$pathname, PATHINFO_EXTENSION)).' or cannot write '.strtolower((string)$extension).'!', 49003, FALSE); }
  if (!$ConversionErrors) {
    // / Build & sandbox the command once. It does not change between retries.
    $graphvizEnvironment = array('SERVER_NAME' => 'graph');
    $graphvizCommand = 'timeout '.$timeoutSeconds.' '.escapeshellarg($graphvizBinary).' -T'.escapeshellarg($graphvizFormat).' -o '.escapeshellarg($newPathname).' '.escapeshellarg($pathname);
    list ($commandMayRun, $graphvizCommand) = sandboxCommand($graphvizCommand, $pathname, $newPathname, FALSE, 'graphviz', '', $graphvizEnvironment);
    if (!$commandMayRun) {
      $ConversionErrors = TRUE;
      errorEntry('Bubblewrap is missing or non functional, so this graph cannot be isolated!', 49002, FALSE); }
    else {
      if ($Verbose) logEntry('Drawing a graph.');
      // / Attempt the conversion as many times as --File Operation Retry Count-- allows, & stop at the first attempt that leaves a file.
      // / A graph that ran out of time is not attempted again, because it would run out of time again.
      while (!$timedOut && pipelineAttemptIsDue($newPathname, $stopper)) {
        // / If the last conversion attempt failed, wait a moment before trying again.
        if ($stopper !== 0) sleep($sleepTime++);
        redeclare($commandOutput, array());
        exec($graphvizCommand.' 2>&1', $commandOutput, $exitStatus);
        // / Graphviz ends with a status other than 0 where it stopped, & a file it had begun is not the picture, so it is removed.
        // / An exit status of 124 is the timeout command reporting that it stopped Graphviz.
        if ($exitStatus !== 0) {
          @unlink($newPathname);
          if ($exitStatus === 124) $timedOut = TRUE; }
        // / Count the number of conversions to avoid infinite loops.
        $stopper++; }
      // / Log the output of the operation to the logfile, if it is not blank.
      if ($Verbose && trim(implode($Lol, $commandOutput)) !== '') logEntry('Graphviz returned the following: '.$Lol.'  '.implode($Lol.'  ', array_slice($commandOutput, 0, 40)));
      // / Graphviz says once that it read no file a graph named, so the administrator hears of it whether or not the picture was drawn.
      foreach ($commandOutput as $outputLine) if (strpos((string)$outputLine, 'file loading is disabled') !== FALSE) $fileWasRefused = TRUE;
      if ($fileWasRefused) warningEntry('A graph named an image or a shape file, which was left out, because the Graph pipeline reads no file but the graph.');
      if ($timedOut) {
        $ConversionErrors = TRUE;
        errorEntry('The graph converter was stopped after '.$timeoutSeconds.' seconds, as --Graph Conversion Timeout-- allows!', 49004, FALSE); }
      // / Every attempt was made & none left a file. Why Graphviz stopped is said with the failure.
      else if (!file_exists($newPathname)) {
        $ConversionErrors = TRUE;
        $firstError = graphFirstError($commandOutput);
        errorEntry('The graph converter produced no file after '.$stopper.' attempt(s)!'.($firstError !== '' ? ' '.$firstError : ''), 49000, FALSE); }
      // / The output file is the only verdict on whether the conversion produced anything.
      if (!$timedOut && file_exists($newPathname)) $ConversionSuccess = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $commandMayRun, $timedOut, $fileWasRefused, $graphvizBinary, $graphvizFormat, $graphvizCommand, $firstError, $outputLine, $commandOutput, $graphvizEnvironment, $exitStatus, $stopper, $sleepTime, $defaultTimeout, $timeoutSeconds, $pathname);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $OutputFilename, $WorkerPID); }
// / -----------------------------------------------------------------------------------

?>
