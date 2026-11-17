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
// / v4.2.5.
// / The Data pipeline's converter. pipelineCore.php loads it only to dispatch a Data conversion.
// / Error block 44000 through 44007 belongs to this pipeline.
// / Dasel & yq come from verifiedToolPath().
// / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt for the contracts this file obeys.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline converter cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to name the format Dasel & yq know an extension by.
// / Accepts the extension. Returns the format's name, or an empty string for an extension this pipeline does not read or write.
// / yml is YAML by its other name.
function dataFormatName($extension) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FormatName = '';
  $formatNames = array('json' => 'json', 'yaml' => 'yaml', 'yml' => 'yaml', 'toml' => 'toml', 'xml' => 'xml', 'csv' => 'csv');
  $FormatName = (string)($formatNames[strtolower((string)$extension)] ?? '');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $formatNames, $extension);
  return $FormatName; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to put one tool command in its sandbox, with a time it may not run past.
// / Accepts the tool's command, the conversion's work folder & the sandbox profile, in that order.
// / Returns whether the command may run & the command to run, in that order.
// / Each tool reads its standard input & writes its standard output, which this pipeline opens, so it is handed no path.
// / The sandbox is built around the work folder, which is empty, so a tool exploited by what it reads finds nothing there to read or change.
// / A tool reading a hostile document is stopped after 300 seconds, because a document that never finishes parsing must not hold a request for ever.
function dataSandboxCommand($toolCommand, $workFolder, $sandboxProfile) {
  // / Set variables.
  global $DirSep, $EnableMemoryProtection;
  $CommandMayRun = FALSE;
  $SandboxedCommand = '';
  list ($CommandMayRun, $SandboxedCommand) = sandboxCommand('timeout 300 '.$toolCommand, $workFolder.$DirSep.'input', $workFolder.$DirSep.'output', FALSE, $sandboxProfile);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $toolCommand, $workFolder, $sandboxProfile);
  return array($CommandMayRun, $SandboxedCommand); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run one sandboxed tool with a file as its input & a file, or a little memory, as its output.
// / Accepts the command, the path read as standard input & the path written as standard output, in that order.
// / An empty output path keeps what the tool prints, up to 4096 bytes, rather than writing it anywhere.
// / Returns whether it ran, its exit status, what it printed where that was kept & what it said on standard error, in that order.
// / What it says on standard error is kept up to 65536 bytes, & read beside standard output so neither can fill & stall the tool.
// / The files are opened here rather than by a redirection in the command, so no path is ever written into a command a shell reads.
function dataRunCommand($commandToRun, $inputPath, $outputPath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $CommandRan = FALSE;
  $ExitStatus = -1;
  $PrintedText = $ErrorText = '';
  $commandProcess = $pipesReady = FALSE;
  $commandPipes = $openPipes = $readablePipes = array();
  $writablePipes = $exceptionalPipes = NULL;
  $outputTarget = array();
  $pipeChunk = '';
  $pipeNumber = 0;
  $outputTarget = ((string)$outputPath === '') ? array('pipe', 'w') : array('file', (string)$outputPath, 'w');
  $commandProcess = @proc_open($commandToRun, array(0 => array('file', (string)$inputPath, 'r'), 1 => $outputTarget, 2 => array('pipe', 'w')), $commandPipes);
  if (is_resource($commandProcess)) {
    $CommandRan = TRUE;
    // / Every pipe is read without blocking, so a pipe with nothing in it never holds up the other.
    foreach (array_keys($commandPipes) as $pipeNumber) {
      stream_set_blocking($commandPipes[$pipeNumber], FALSE);
      $openPipes[$pipeNumber] = $commandPipes[$pipeNumber]; }
    while (count($openPipes) > 0) {
      redeclare($readablePipes, array_values($openPipes));
      $pipesReady = @stream_select($readablePipes, $writablePipes, $exceptionalPipes, 5, 0);
      if ($pipesReady === FALSE) break;
      foreach (array_keys($openPipes) as $pipeNumber) {
        if (!in_array($openPipes[$pipeNumber], $readablePipes, TRUE)) continue;
        redeclare($pipeChunk, (string)fread($openPipes[$pipeNumber], 8192));
        if ($pipeNumber === 1 && strlen($PrintedText) < 4096) redeclare($PrintedText, substr($PrintedText.$pipeChunk, 0, 4096));
        if ($pipeNumber === 2 && strlen($ErrorText) < 65536) redeclare($ErrorText, substr($ErrorText.$pipeChunk, 0, 65536));
        if (feof($openPipes[$pipeNumber])) {
          fclose($openPipes[$pipeNumber]);
          unset($openPipes[$pipeNumber]); } } }
    // / A pipe still open after an interrupted wait is closed, so the tool can never block writing to it while it is waited for.
    foreach (array_keys($openPipes) as $pipeNumber) fclose($openPipes[$pipeNumber]);
    $ExitStatus = proc_close($commandProcess); }
  else warningEntry('A data conversion tool could not be started. PHP may have proc_open disabled.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $commandProcess, $commandPipes, $openPipes, $readablePipes, $writablePipes, $exceptionalPipes, $outputTarget, $pipeChunk, $pipeNumber, $pipesReady, $commandToRun, $inputPath, $outputPath);
  return array($CommandRan, $ExitStatus, $PrintedText, $ErrorText); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert data files between JSON, YAML, TOML, XML & CSV.
// / Dasel reads every format & writes all but XML. yq writes XML from the JSON Dasel writes, under one root, & reads it back
// / before it is offered, because a key that is no XML name writes something that is not XML. Dasel 3 cannot write XML itself.
// / TOML is a table at its top & CSV is a list of records, so Dasel is asked what a document is before either is written, & any other shape is refused.
// / Dasel 3 writes a list as TOML that is not TOML & reports success. It refuses CSV from anything but a list itself, & from a list whose records
// / do not share the same plain fields, which is reported as a file that was not made.
// / Each run reads from & writes to files this function opens, in a sandbox built around an empty folder of the conversion's own.
// / The function is longer than most, so it returns the six value contract, whose first value is its sanity check.
function convertDataFiles($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $DirSep, $SleepTimer, $EnableMemoryProtection;
  // / The six value pipeline contract. Success, errors, path, extension, filename & PID.
  $OutputFilename = basename($newPathname);
  $WorkerPID = 0;
  $ConversionSuccess = $ConversionErrors = $commandMayRun = $typeMayRun = $writeMayRun = $readMayRun = $folderWasMade = $commandRan = $structureIsRefused = FALSE;
  $daselBinary = $yqBinary = FALSE;
  $inputFormat = $outputFormat = $workFolder = $jsonPath = $daselCommand = $typeCommand = $writeCommand = $readCommand = $printedText = $errorText = $topType = $neededType = $toolStatus = $toolOutput = '';
  $exitStatus = -1;
  $stopper = 0;
  $sleepTime = $SleepTimer;
  // / yq is handed the same document whatever its shape, & decides the root.
  // / A list is wrapped as root with an item per entry, a table with one key that holds no list is its own root, & anything else is wrapped as root.
  $rootExpression = '(select(tag == "!!seq") | {"root": {"item": .}}) // (select(tag == "!!map" and length == 1 and ([.[]] | .[0] | tag) != "!!seq")) // {"root": .}';
  $yqFlags = '--security-disable-env-ops --security-disable-file-ops';
  $inputFormat = dataFormatName(pathinfo((string)$pathname, PATHINFO_EXTENSION));
  $outputFormat = dataFormatName($extension);
  if ($inputFormat === '' or $outputFormat === '') {
    $ConversionErrors = TRUE;
    errorEntry('The Data pipeline cannot read '.pathinfo((string)$pathname, PATHINFO_EXTENSION).' or cannot write '.$extension.'!', 44007, FALSE); }
  else {
    // / Locate & verify Dasel. A path is returned only when both succeeded.
    $daselBinary = verifiedToolPath('Dasel');
    if ($outputFormat === 'xml') $yqBinary = verifiedToolPath('yq'); }
  if (!$ConversionErrors && $daselBinary === FALSE) {
    $ConversionErrors = TRUE;
    list ($toolStatus, $toolOutput) = verifiedToolVerdict('Dasel');
    errorEntry('The installed Dasel version is missing, unidentifiable, or too old!'.($toolStatus !== '' ? ' It is '.$toolStatus.($toolOutput !== '' ? ': '.$toolOutput : '.') : ''), 44001, FALSE); }
  else if (!$ConversionErrors && $outputFormat === 'xml' && $yqBinary === FALSE) {
    $ConversionErrors = TRUE;
    list ($toolStatus, $toolOutput) = verifiedToolVerdict('yq');
    errorEntry('The installed yq version is missing, unidentifiable, or too old, so nothing can be written as XML!'.($toolStatus !== '' ? ' It is '.$toolStatus.($toolOutput !== '' ? ': '.$toolOutput : '.') : ''), 44004, FALSE); }
  if (!$ConversionErrors) {
    list ($folderWasMade, $workFolder) = makeWorkFolder($pathname, 'data');
    if (!$folderWasMade) {
      $ConversionErrors = TRUE;
      errorEntry('Could not make a work folder for the data conversion of '.basename((string)$pathname).'!', 44006, FALSE); } }
  if (!$ConversionErrors) {
    // / Build & sandbox every command once. None changes between retries.
    // / Dasel is handed /dev/null as its configuration, so it reads none, whatever the home of the account running it holds.
    // / XML is written from JSON Dasel writes into the work folder, & everything else straight to the output.
    $jsonPath = $workFolder.$DirSep.'document.json';
    list ($commandMayRun, $daselCommand) = dataSandboxCommand(escapeshellarg($daselBinary).' query -c /dev/null -i '.$inputFormat.' -o '.(($outputFormat === 'xml') ? 'json' : $outputFormat), $workFolder, 'dasel');
    if ($outputFormat === 'toml' or $outputFormat === 'csv') list ($typeMayRun, $typeCommand) = dataSandboxCommand(escapeshellarg($daselBinary).' query -c /dev/null -i '.$inputFormat.' -o json '.escapeshellarg('typeOf($this)'), $workFolder, 'dasel');
    if ($outputFormat === 'xml') {
      list ($writeMayRun, $writeCommand) = dataSandboxCommand(escapeshellarg($yqBinary).' '.$yqFlags.' -p json -o xml '.escapeshellarg($rootExpression), $workFolder, 'yq');
      list ($readMayRun, $readCommand) = dataSandboxCommand(escapeshellarg($yqBinary).' '.$yqFlags.' -p xml -o json .', $workFolder, 'yq'); }
    if (!$commandMayRun or (($outputFormat === 'toml' or $outputFormat === 'csv') && !$typeMayRun) or ($outputFormat === 'xml' && (!$writeMayRun or !$readMayRun))) {
      $ConversionErrors = TRUE;
      errorEntry('Bubblewrap is missing or non functional, so this data conversion cannot be isolated!', 44002, FALSE); }
    else {
      if ($Verbose) logEntry('Converting '.$inputFormat.' data to '.$outputFormat.'.');
      // / The shape is asked about once, because what the document is does not change between attempts.
      // / A document Dasel cannot read answers nothing here, & fails the attempts below, which say why.
      if ($outputFormat === 'toml' or $outputFormat === 'csv') {
        list ($commandRan, $exitStatus, $printedText, $errorText) = dataRunCommand($typeCommand, $pathname, '');
        $topType = trim(trim($printedText), '"');
        $neededType = ($outputFormat === 'toml') ? 'map' : 'array';
        if ($commandRan && $exitStatus === 0 && $topType !== $neededType) {
          $structureIsRefused = TRUE;
          $ConversionErrors = TRUE;
          errorEntry(strtoupper($outputFormat).' needs '.(($outputFormat === 'toml') ? 'a table' : 'a list of records').' at the top of a document, & '.basename((string)$pathname).' holds '.(($topType === 'array') ? 'a list' : (($topType === 'map') ? 'a table' : 'a single value')).' there, so it cannot be written as '.strtoupper($outputFormat).'!', 44003, FALSE); } }
      // / Attempt the conversion as many times as --File Operation Retry Count-- allows, & stop at the
      // / first attempt that leaves a file.
      while (!$structureIsRefused && pipelineAttemptIsDue($newPathname, $stopper)) {
        // / If the last conversion attempt failed, wait a moment before trying again.
        if ($stopper !== 0) sleep($sleepTime++);
        if ($outputFormat === 'xml') {
          list ($commandRan, $exitStatus, $printedText, $errorText) = dataRunCommand($daselCommand, $pathname, $jsonPath);
          if ($commandRan && $exitStatus === 0) list ($commandRan, $exitStatus, $printedText, $errorText) = dataRunCommand($writeCommand, $jsonPath, $newPathname);
          // / What yq wrote is read back as XML before it is offered, because a key that is no XML name writes something that is not XML.
          if ($commandRan && $exitStatus === 0) {
            list ($commandRan, $exitStatus, $printedText, $errorText) = dataRunCommand($readCommand, $newPathname, '/dev/null');
            if (!$commandRan or $exitStatus !== 0) {
              $structureIsRefused = TRUE;
              $ConversionErrors = TRUE;
              @unlink($newPathname);
              errorEntry('yq wrote XML that does not read back as XML, so it was removed! A key in '.basename((string)$pathname).' is probably not a name XML allows.', 44005, FALSE); } }
          @unlink($jsonPath); }
        else list ($commandRan, $exitStatus, $printedText, $errorText) = dataRunCommand($daselCommand, $pathname, $newPathname);
        // / The output was opened before the tool ran, so a failed attempt leaves an empty or partial file, which is removed so the next attempt is made.
        if (!$commandRan or $exitStatus !== 0) @unlink($newPathname);
        // / Log what the tool said to the logfile, if it is not blank.
        if ($Verbose && trim($errorText) !== '') logEntry('The data converter returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, trim($errorText))));
        // / Count the number of conversions to avoid infinite loops.
        $stopper++; }
      // / Every attempt was made & none left a file. A document refused for its shape is reported once, above.
      if (!$structureIsRefused && !file_exists($newPathname)) {
        $ConversionErrors = TRUE;
        errorEntry('The data converter produced no file after '.$stopper.' attempt(s)!', 44000, FALSE); }
      // / The output file is the only verdict on whether the conversion produced anything.
      if (file_exists($newPathname)) $ConversionSuccess = TRUE; }
    removeWorkFolder($workFolder); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $commandMayRun, $typeMayRun, $writeMayRun, $readMayRun, $folderWasMade, $commandRan, $structureIsRefused, $daselBinary, $yqBinary, $inputFormat, $outputFormat, $workFolder, $jsonPath, $daselCommand, $typeCommand, $writeCommand, $readCommand, $printedText, $errorText, $topType, $neededType, $toolStatus, $toolOutput, $exitStatus, $stopper, $sleepTime, $rootExpression, $yqFlags, $pathname);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $OutputFilename, $WorkerPID); }
// / -----------------------------------------------------------------------------------

?>
