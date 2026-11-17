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
// / The LaTeX pipeline's converter. pipelineCore.php loads it only to dispatch a LaTeX conversion.
// / Error block 45000 through 45005 belongs to this pipeline.
// / XeLaTeX comes from verifiedToolPath().
// / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt for the contracts this file obeys.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline converter cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to read why TeX stopped, from the log it wrote.
// / Accepts the path of the log. Returns the first line TeX marked as an error, cleaned to print, or an empty string where there is none.
// / TeX marks an error with an exclamation mark at the start of a line, or, with -file-line-error, with the file & the line it stopped at.
// / A document that typeset to nothing logs that it had no pages, which is said in the same way, because it is why there is no PDF.
// / What a document says is in that line, so a control character becomes a space & the line is cut at 200 characters before anything prints it.
function latexFirstError($logPath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FirstError = '';
  $logText = $logLine = '';
  $logLines = array();
  $logText = (string)@file_get_contents((string)$logPath, FALSE, NULL, 0, 1048576);
  $logLines = preg_split('/\R/', $logText);
  foreach ($logLines as $logLine) {
    if (preg_match('/^(!|[^\s:]+:[0-9]+: |No pages of output\.)/', $logLine) === 1) {
      $FirstError = substr(trim((string)preg_replace('/[\x00-\x1F\x7F]+/', ' ', $logLine)), 0, 200);
      break; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $logText, $logLine, $logLines, $logPath);
  return $FirstError; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether a typeset document needs a second pass.
// / Accepts the work folder. Returns TRUE where the log asks for one, or a table of contents or a list of figures or tables was written.
// / A cross reference, a table of contents & hyperref's bookmarks are read from the auxiliary files the pass before wrote, so the first pass leaves them empty.
// / Two passes settle almost every document, so a second asks for no third.
function latexNeedsRerun($workFolder) {
  // / Set variables.
  global $DirSep, $EnableMemoryProtection;
  $RerunIsNeeded = FALSE;
  $logText = $listName = '';
  $logText = (string)@file_get_contents($workFolder.$DirSep.'document.log', FALSE, NULL, 0, 4194304);
  if (preg_match('/Rerun to get|Label\(s\) may have changed|Warning: File `[^\']*\' has changed/', $logText) === 1) $RerunIsNeeded = TRUE;
  foreach (array('toc', 'lof', 'lot') as $listName) if (@filesize($workFolder.$DirSep.'document.'.$listName) > 0) $RerunIsNeeded = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $logText, $listName, $workFolder);
  return $RerunIsNeeded; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to typeset a LaTeX document into a PDF with XeLaTeX.
// / The document is copied into a folder of the conversion's own & typeset there, so the auxiliary files & the log TeX writes beside the PDF
// / never reach a user's folder, & only the PDF is moved out. runInWorkFolder() runs each pass there, & names the document by its name alone,
// / because paranoid TeX refuses a document named by an absolute path. TeX writes all it has to say to its log, so what it prints is not kept.
// / TeX is a programming language, so a document is a program. It runs in the sandbox, & is set paranoid, with openin_any & openout_any, so it
// / refuses a file named by an absolute path, a hidden file & a path above its folder, for reading & for writing, & with shell_escape off &
// / -no-shell-escape, so it runs no command. The settings are passed with the command rather than kept in the xelatex sandbox profile, because
// / they must hold where no sandbox could be built too. --LaTeX Conversion Timeout-- stops it, because a document can loop for ever.
// / A document is typeset alone, so one that includes another file, an image or a bibliography fails, & the error says the file was not found.
// / The function is longer than most, so it returns the six value contract, whose first value is its sanity check.
function typesetLaTeX($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $Lol, $DirSep, $SleepTimer, $LaTeXConversionTimeout, $EnableMemoryProtection;
  // / The six value pipeline contract. Success, errors, path, extension, filename & PID.
  $OutputFilename = basename($newPathname);
  $WorkerPID = 0;
  $ConversionSuccess = $ConversionErrors = $commandMayRun = $folderWasMade = $pdfWasPlaced = $timedOut = $texFinished = FALSE;
  $latexBinary = FALSE;
  $workFolder = $texPath = $pdfPath = $latexCommand = $toolStatus = $toolOutput = $firstError = $passOutput = '';
  $latexEnvironment = array();
  $exitStatus = -1;
  $stopper = $passNumber = 0;
  $sleepTime = $SleepTimer;
  // / A local fallback configuration cannot overwrite, for a timeout that is missing or is not a whole number of seconds greater than 0.
  $defaultTimeout = 120;
  $timeoutSeconds = (is_numeric($LaTeXConversionTimeout) && (int)$LaTeXConversionTimeout > 0) ? (int)$LaTeXConversionTimeout : $defaultTimeout;
  if ($timeoutSeconds === $defaultTimeout && (string)$LaTeXConversionTimeout !== (string)$defaultTimeout) warningEntry('--LaTeX Conversion Timeout-- is not a whole number of seconds greater than 0, so '.$defaultTimeout.' is used.');
  // / Locate & verify XeLaTeX. A path is returned only when both succeeded.
  $latexBinary = verifiedToolPath('XeLaTeX');
  if ($latexBinary === FALSE) {
    $ConversionErrors = TRUE;
    list ($toolStatus, $toolOutput) = verifiedToolVerdict('XeLaTeX');
    errorEntry('The installed XeLaTeX version is missing, unidentifiable, or too old!'.($toolStatus !== '' ? ' It is '.$toolStatus.($toolOutput !== '' ? ': '.$toolOutput : '.') : ''), 45001, FALSE); }
  else {
    // / The document is typeset as document.tex, so what TeX writes is named document & nothing a user named reaches a command.
    list ($folderWasMade, $workFolder) = makeWorkFolder($pathname, 'latex');
    $texPath = $workFolder.$DirSep.'document.tex';
    $pdfPath = $workFolder.$DirSep.'document.pdf';
    if (!$folderWasMade or !@copy($pathname, $texPath)) {
      $ConversionErrors = TRUE;
      errorEntry('Could not stage '.basename((string)$pathname).' for typesetting!', 45004, FALSE); } }
  if (!$ConversionErrors) {
    // / Build & sandbox the command once. It does not change between passes or retries.
    // / The document & the PDF share the work folder, so the sandbox mounts it once, writable, as the folder TeX runs in.
    // / The document is named by its name alone, which is all paranoid TeX opens, so the sandbox has no path to rewrite.
    $latexEnvironment = array('openin_any' => 'p', 'openout_any' => 'p', 'shell_escape' => 'f');
    $latexCommand = 'timeout '.$timeoutSeconds.' '.escapeshellarg($latexBinary).' -no-shell-escape -interaction=nonstopmode -halt-on-error -file-line-error document.tex';
    list ($commandMayRun, $latexCommand) = sandboxCommand($latexCommand, $texPath, $pdfPath, FALSE, 'xelatex', '', $latexEnvironment);
    if (!$commandMayRun) {
      $ConversionErrors = TRUE;
      errorEntry('Bubblewrap is missing or non functional, so this LaTeX document cannot be typeset in isolation!', 45002, FALSE); }
    else {
      if ($Verbose) logEntry('Typesetting a LaTeX document.');
      // / Attempt the typesetting as many times as --File Operation Retry Count-- allows, & stop at the first attempt that leaves a PDF.
      // / A document that ran out of time is not attempted again, because it would run out of time again, & one TeX finished with an
      // / error or with no pages is not attempted again either, because TeX would stop at the same line.
      while (!$timedOut && !$texFinished && pipelineAttemptIsDue($pdfPath, $stopper)) {
        // / If the last attempt failed, wait a moment before trying again.
        if ($stopper !== 0) sleep($sleepTime++);
        // / The first pass always runs, & the second only where the first wrote a PDF & asks for one.
        for ($passNumber = 1; $passNumber <= 2; $passNumber++) {
          if ($passNumber === 2 && (!file_exists($pdfPath) or !latexNeedsRerun($workFolder))) break;
          list ($exitStatus, $passOutput) = runInWorkFolder($latexCommand, $workFolder);
          // / TeX ends with a status other than 0 where it stopped, & a PDF it had begun is not the document, so it is removed.
          // / An exit status of 124 is the timeout command reporting that it stopped TeX.
          if ($exitStatus !== 0) {
            @unlink($pdfPath);
            if ($exitStatus === 124) $timedOut = TRUE;
            break; } }
        // / The first error TeX logged names the cause, such as a package this server does not have, so it is said with the failure.
        if (!$timedOut && !file_exists($pdfPath)) {
          redeclare($firstError, latexFirstError($workFolder.$DirSep.'document.log'));
          if ($firstError !== '') $texFinished = TRUE; }
        // / Count the number of attempts to avoid infinite loops.
        $stopper++; }
      if ($Verbose && $firstError !== '') logEntry('XeLaTeX stopped at the following: '.$Lol.'  '.$firstError);
      if ($timedOut) {
        $ConversionErrors = TRUE;
        errorEntry('The LaTeX typesetter was stopped after '.$timeoutSeconds.' seconds, as --LaTeX Conversion Timeout-- allows!', 45005, FALSE); }
      else if (!file_exists($pdfPath)) {
        $ConversionErrors = TRUE;
        errorEntry('The LaTeX typesetter produced no PDF after '.$stopper.' attempt(s)!'.($firstError !== '' ? ' '.$firstError : ''), 45000, FALSE); }
      // / Only the PDF leaves the work folder. It is renamed where the folders share a filesystem, & copied where they do not.
      else {
        $pdfWasPlaced = (@rename($pdfPath, $newPathname) or (@copy($pdfPath, $newPathname) && @unlink($pdfPath)));
        if (!$pdfWasPlaced) {
          $ConversionErrors = TRUE;
          @unlink($newPathname);
          errorEntry('Could not move the typeset PDF to '.basename((string)$newPathname).'!', 45003, FALSE); } }
      // / The output file is the only verdict on whether the conversion produced anything.
      if ($pdfWasPlaced && file_exists($newPathname)) $ConversionSuccess = TRUE; } }
  if ($folderWasMade) removeWorkFolder($workFolder);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $commandMayRun, $folderWasMade, $pdfWasPlaced, $timedOut, $texFinished, $latexBinary, $workFolder, $texPath, $pdfPath, $latexCommand, $toolStatus, $toolOutput, $firstError, $passOutput, $latexEnvironment, $exitStatus, $stopper, $passNumber, $sleepTime, $defaultTimeout, $timeoutSeconds, $pathname);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $OutputFilename, $WorkerPID); }
// / -----------------------------------------------------------------------------------

?>
