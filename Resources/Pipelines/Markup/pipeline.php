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
// / The Markup pipeline's converter. pipelineCore.php loads it only to dispatch a Markup conversion.
// / Error block 48000 through 48005 belongs to this pipeline.
// / Pandoc & XeLaTeX come from verifiedToolPath().
// / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt for the contracts this file obeys.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline converter cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to name the Pandoc reader & writer for a conversion.
// / Accepts the extension of the document & the extension asked for, in that order.
// / Returns the reader & the writer, in that order. Either is empty where Pandoc is not asked to read or write that format here.
// / Pandoc is always told which reader & which writer to use, so it never guesses a format from a file's name or its contents.
// / md & markdown are read as Pandoc's own Markdown, which reads nearly every flavor, & written as GitHub Flavored Markdown, which the most programs read.
// / A PDF is written by the LaTeX writer & typeset by XeLaTeX.
// / HTML is neither read nor written, as pipelineConfig.php says why.
function markupFormatNames($inputExtension, $outputExtension) {
  // / Set variables.
  global $EnableMemoryProtection;
  $PandocReader = $PandocWriter = '';
  $pandocReaders = array('md' => 'markdown', 'markdown' => 'markdown', 'rst' => 'rst', 'adoc' => 'asciidoc', 'asciidoc' => 'asciidoc', 'jira' => 'jira', 'textile' => 'textile',
    'org' => 'org', 'mediawiki' => 'mediawiki', 'dokuwiki' => 'dokuwiki', 'creole' => 'creole', 'muse' => 'muse', 't2t' => 't2t', 'djot' => 'djot', 'typ' => 'typst',
    'tex' => 'latex', 'latex' => 'latex', 'docx' => 'docx', 'odt' => 'odt', 'epub' => 'epub', 'rtf' => 'rtf', 'ipynb' => 'ipynb',
    'opml' => 'opml', 'fb2' => 'fb2', 'dbk' => 'docbook', 'pod' => 'pod', 'csv' => 'csv', 'tsv' => 'tsv', 'pptx' => 'pptx', 'xlsx' => 'xlsx');
  $pandocWriters = array('md' => 'gfm', 'markdown' => 'gfm', 'rst' => 'rst', 'adoc' => 'asciidoc', 'asciidoc' => 'asciidoc', 'jira' => 'jira', 'textile' => 'textile',
    'org' => 'org', 'mediawiki' => 'mediawiki', 'dokuwiki' => 'dokuwiki', 'muse' => 'muse', 't2t' => 't2t', 'djot' => 'djot', 'typ' => 'typst',
    'tex' => 'latex', 'latex' => 'latex', 'docx' => 'docx', 'odt' => 'odt', 'epub' => 'epub3', 'rtf' => 'rtf', 'ipynb' => 'ipynb',
    'opml' => 'opml', 'fb2' => 'fb2', 'dbk' => 'docbook5', 'txt' => 'plain', 'pdf' => 'latex', 'pptx' => 'pptx', 'texi' => 'texinfo', 'icml' => 'icml');
  $inputExtension = strtolower((string)$inputExtension);
  $outputExtension = strtolower((string)$outputExtension);
  if (isset($pandocReaders[$inputExtension])) $PandocReader = $pandocReaders[$inputExtension];
  if (isset($pandocWriters[$outputExtension])) $PandocWriter = $pandocWriters[$outputExtension];
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $pandocReaders, $pandocWriters, $inputExtension, $outputExtension);
  return array($PandocReader, $PandocWriter); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to find why Pandoc made no file, in what it printed.
// / Accepts the lines Pandoc printed. Returns the line that says why, cleaned to print, or an empty string where there is none.
// / A line TeX marked as an error is said first, because a PDF fails on the first one. Otherwise the last line that is not a warning is said,
// / because Pandoc says why it stopped last. A warning, such as an image the sandbox would not let Pandoc read, is not why it stopped.
// / What a document says can be in that line, so a control character becomes a space & the line is cut at 200 characters before anything prints it.
function markupFirstError($outputLines) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FirstError = '';
  $outputLine = $texError = $lastLine = '';
  foreach ((array)$outputLines as $outputLine) {
    $outputLine = trim((string)preg_replace('/[\x00-\x1F\x7F]+/', ' ', (string)$outputLine));
    if ($outputLine === '' or strpos($outputLine, '[WARNING]') === 0 or strpos($outputLine, 'Replacing image') === 0) continue;
    if ($texError === '' && strpos($outputLine, '!') === 0) $texError = $outputLine;
    $lastLine = $outputLine; }
  $FirstError = substr(($texError !== '' ? $texError : $lastLine), 0, 200);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $outputLine, $texError, $lastLine, $outputLines);
  return $FirstError; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert a document between markup & document formats with Pandoc.
// / Pandoc runs with --sandbox, so it reads the document it is handed & nothing else, not an image, an include or a page off the network.
// / Its heap is held to 512 MiB, & --Markup Conversion Timeout-- stops it, because the Pandoc manual says a reader can take pathologically long
// / over some documents, & recommends both for a document a stranger wrote.
// / A PDF is typeset by XeLaTeX, which Pandoc runs in a folder of its own on the tmpfs. LaTeX in a document reaches it, so TeX is set paranoid
// / & refused a shell, exactly as the LaTeX pipeline sets it, with the command rather than the sandbox, so it holds where no sandbox could be built too.
// / The function is longer than most, so it returns the six value contract, whose first value is its sanity check.
function convertMarkup($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $Lol, $SleepTimer, $MarkupConversionTimeout, $EnableMemoryProtection;
  // / The six value pipeline contract. Success, errors, path, extension, filename & PID.
  $OutputFilename = basename($newPathname);
  $WorkerPID = 0;
  $ConversionSuccess = $ConversionErrors = $commandMayRun = $timedOut = FALSE;
  $pandocBinary = $latexBinary = FALSE;
  $pandocReader = $pandocWriter = $writerOptions = $pandocCommand = $firstError = '';
  $commandOutput = $pandocEnvironment = array();
  $exitStatus = -1;
  $stopper = 0;
  $sleepTime = $SleepTimer;
  // / A local fallback configuration cannot overwrite, for a timeout that is missing or is not a whole number of seconds greater than 0.
  $defaultTimeout = 120;
  $timeoutSeconds = (is_numeric($MarkupConversionTimeout) && (int)$MarkupConversionTimeout > 0) ? (int)$MarkupConversionTimeout : $defaultTimeout;
  if ($timeoutSeconds === $defaultTimeout && (string)$MarkupConversionTimeout !== (string)$defaultTimeout) warningEntry('--Markup Conversion Timeout-- is not a whole number of seconds greater than 0, so '.$defaultTimeout.' is used.');
  list ($pandocReader, $pandocWriter) = markupFormatNames(pathinfo((string)$pathname, PATHINFO_EXTENSION), $extension);
  // / Locate & verify Pandoc. A path is returned only when both succeeded.
  $pandocBinary = verifiedToolPath('Pandoc');
  if ($pandocBinary === FALSE) {
    $ConversionErrors = TRUE;
    errorEntry('The installed Pandoc version is missing, unidentifiable, or too old!', 48001, FALSE); }
  else if ($pandocReader === '' or $pandocWriter === '') {
    $ConversionErrors = TRUE;
    errorEntry('The Markup pipeline cannot read '.strtolower((string)pathinfo((string)$pathname, PATHINFO_EXTENSION)).' or cannot write '.strtolower((string)$extension).'!', 48003, FALSE); }
  else if (strtolower((string)$extension) === 'pdf') {
    // / A PDF needs XeLaTeX as well, which the LaTeX subsystem installs.
    $latexBinary = verifiedToolPath('XeLaTeX');
    if ($latexBinary === FALSE) {
      $ConversionErrors = TRUE;
      errorEntry('The installed XeLaTeX version is missing, unidentifiable, or too old, & Pandoc typesets a PDF with it!', 48004, FALSE); }
    else $writerOptions = ' --pdf-engine='.escapeshellarg($latexBinary); }
  if (!$ConversionErrors) {
    // / Build & sandbox the command once. It does not change between retries.
    // / --sandbox keeps Pandoc to the one document, -s writes a whole document rather than a fragment, & +RTS -M512M -RTS holds its heap.
    $pandocEnvironment = array('openin_any' => 'p', 'openout_any' => 'p', 'shell_escape' => 'f');
    $pandocCommand = 'timeout '.$timeoutSeconds.' '.escapeshellarg($pandocBinary).' +RTS -M512M -RTS --sandbox -s -f '.escapeshellarg($pandocReader).' -t '.escapeshellarg($pandocWriter).$writerOptions.' -o '.escapeshellarg($newPathname).' '.escapeshellarg($pathname);
    list ($commandMayRun, $pandocCommand) = sandboxCommand($pandocCommand, $pathname, $newPathname, FALSE, 'pandoc', '', $pandocEnvironment);
    if (!$commandMayRun) {
      $ConversionErrors = TRUE;
      errorEntry('Bubblewrap is missing or non functional, so this markup conversion cannot be isolated!', 48002, FALSE); }
    else {
      if ($Verbose) logEntry('Converting a markup document.');
      // / Attempt the conversion as many times as --File Operation Retry Count-- allows, & stop at the first attempt that leaves a file.
      // / A document that ran out of time is not attempted again, because it would run out of time again.
      while (!$timedOut && pipelineAttemptIsDue($newPathname, $stopper)) {
        // / If the last conversion attempt failed, wait a moment before trying again.
        if ($stopper !== 0) sleep($sleepTime++);
        redeclare($commandOutput, array());
        exec($pandocCommand.' 2>&1', $commandOutput, $exitStatus);
        // / Pandoc ends with a status other than 0 where it stopped, & a file it had begun is not the document, so it is removed.
        // / An exit status of 124 is the timeout command reporting that it stopped Pandoc.
        if ($exitStatus !== 0) {
          @unlink($newPathname);
          if ($exitStatus === 124) $timedOut = TRUE; }
        // / Count the number of conversions to avoid infinite loops.
        $stopper++; }
      // / Log the output of the operation to the logfile, if it is not blank. A warning that an image was left out is said here.
      if ($Verbose && trim(implode($Lol, $commandOutput)) !== '') logEntry('Pandoc returned the following: '.$Lol.'  '.implode($Lol.'  ', array_slice($commandOutput, 0, 40)));
      if ($timedOut) {
        $ConversionErrors = TRUE;
        errorEntry('The markup converter was stopped after '.$timeoutSeconds.' seconds, as --Markup Conversion Timeout-- allows!', 48005, FALSE); }
      // / Every attempt was made & none left a file. Why Pandoc stopped is said with the failure.
      else if (!file_exists($newPathname)) {
        $ConversionErrors = TRUE;
        $firstError = markupFirstError($commandOutput);
        errorEntry('The markup converter produced no file after '.$stopper.' attempt(s)!'.($firstError !== '' ? ' '.$firstError : ''), 48000, FALSE); }
      // / The output file is the only verdict on whether the conversion produced anything.
      if (!$timedOut && file_exists($newPathname)) $ConversionSuccess = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $commandMayRun, $timedOut, $pandocBinary, $latexBinary, $pandocReader, $pandocWriter, $writerOptions, $pandocCommand, $firstError, $commandOutput, $pandocEnvironment, $exitStatus, $stopper, $sleepTime, $defaultTimeout, $timeoutSeconds, $pathname);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $OutputFilename, $WorkerPID); }
// / -----------------------------------------------------------------------------------

?>
