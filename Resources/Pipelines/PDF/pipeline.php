<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRConvert2, Copyright on 11/13/2026 by Justin Grimes, www.github.com/zelon88
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
// / v4.1.8.
// / The PDF pipeline. pipelineCore.php loads it only to dispatch a Merge or a Split operation.
// / Error block 42000 through 42019 belongs to this pipeline.
// / It is an operation pipeline, which merges a selection of PDFs into one, or makes a PDF of each page of every PDF it is handed.
// / It returns three values, the third being the name of every PDF it made, which the core prints for a button & a workflow passes on.
// / A pipeline never prints. See contract 6.
// / It is dispatched by runOperationPipeline('PDF'), never through convert().
// / PDFUnite merges, PDFSeparate splits & PDFInfo counts pages, each inside the sandbox, in a folder of its own inside the session.
// / A split is held to --Maximum Extracted Files-- & --Maximum Extracted Size--, as an extraction is, because each makes a great many files from one.
// / Files are scanned through runVirusScan(), verifyFile() & placeSessionFile() are the core's, & cleanFiles() is the Engine's.
// / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt for the contracts this file obeys.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline component cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to check one PDF a merge or a split was handed & stage the copy that is read.
// / Accepts the file. Returns whether it may be read, its name as sanitized & the path of the copy verifyFile() staged, in that order.
// / A name that will not sanitize, a file that is not a PDF & a file a scan finds infected are each refused, & named.
// / A file is scanned through runVirusScan() before it is read, when $VirusScan is set.
function verifyPageInput($fileName) {
  // / Set variables.
  global $Verbose, $VirusScan, $EnableMemoryProtection;
  $InputIsUsable = FALSE;
  $CleanName = $StagedCopy = '';
  $variableIsSanitized = $fileIsVerified = $scanComplete = $virusFound = FALSE;
  $oldPathname = $oldExtension = $newPathname = $resolvedFilename = $scannerUsed = '';
  $scanFindings = array();
  list ($CleanName, $variableIsSanitized) = sanitize($fileName, TRUE);
  if (!$variableIsSanitized or !is_string($CleanName) or $CleanName === '' or $CleanName === '.' or $CleanName === '..' or $CleanName === 'index.html') errorEntry('Could not sanitize the input file!', 42001, FALSE);
  else if (getExtension($CleanName) !== 'pdf') warningEntry('The file '.$CleanName.' is not a PDF, so its pages were left alone.');
  else {
    // / verifyFile() stages a copy in the temporary folder, & that copy is what a tool reads.
    list ($fileIsVerified, $StagedCopy, $oldPathname, $oldExtension, $newPathname, $resolvedFilename) = verifyFile($CleanName, '', '', TRUE, TRUE, FALSE);
    if (!$fileIsVerified) errorEntry('Could not verify the input file.', 42002, FALSE);
    else if ($VirusScan) {
      if ($Verbose) logEntry('Starting virus scan.');
      list ($scanComplete, $virusFound, $scanFindings, $scannerUsed) = runVirusScan(array($StagedCopy), '');
      if (!$scanComplete) errorEntry('Could not perform a virus scan of '.$CleanName.', so its pages were left alone!', 42003, FALSE);
      else if ($virusFound) warningEntry('A virus was found in '.$CleanName.', so its pages were left alone.');
      else $InputIsUsable = TRUE; }
    else $InputIsUsable = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $variableIsSanitized, $fileIsVerified, $scanComplete, $virusFound, $oldPathname, $oldExtension, $newPathname, $resolvedFilename, $scannerUsed, $scanFindings, $fileName);
  return array($InputIsUsable, $CleanName, $StagedCopy); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to make a folder of its own inside the session for one merge or one split.
// / Accepts nothing. Returns whether it was made & its path, in that order.
// / A tool writes only into this folder, so it never writes beside a file it did not make, & what it made is placed in the session from here.
function makePageFolder() {
  // / Set variables.
  global $ConvertDir, $EnableMemoryProtection;
  $FolderWasMade = FALSE;
  $FolderPath = '';
  $FolderPath = $ConvertDir.'.pages-'.bin2hex(random_bytes(8));
  if (@mkdir($FolderPath, 0700)) $FolderWasMade = TRUE;
  else errorEntry('A folder to merge or split PDFs in could not be made, so nothing was made!', 42004, FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection);
  return array($FolderWasMade, $FolderPath); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to take away the folder a merge or a split was made in.
// / Accepts the folder. Returns TRUE when it is gone.
// / It goes whatever happened, & the session is the root it may be removed from, so the folder itself goes too.
function removePageFolder($folderPath) {
  // / Set variables.
  global $ConvertDir, $EnableMemoryProtection;
  $FolderIsRemoved = FALSE;
  clearstatcache(TRUE, (string)$folderPath);
  $FolderIsRemoved = ((string)$folderPath === '' or !file_exists((string)$folderPath) or cleanFiles((string)$folderPath, array($ConvertDir)));
  if (!$FolderIsRemoved) warningEntry('A folder PDFs were merged or split in could not be removed. It is removed with the session.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderPath);
  return $FolderIsRemoved; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run a tool on PDFs inside the sandbox, in the folder it writes into.
// / Accepts the command, a file it reads, the file it writes & the folder, in that order. Returns whether it ran & what it printed, in that order.
// / A merge reads many PDFs, which are copied into the folder & named relative to it, because the sandbox rewrites only the one input it is given.
// / The folder is the working folder both inside the sandbox & outside it, so a relative name means the same file in either.
function runPageTool($toolCommand, $readPath, $writePath, $folderPath) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $EnableMemoryProtection;
  $ToolRan = FALSE;
  $ToolOutput = '';
  $sandboxIsAvailable = FALSE;
  $sandboxedCommand = '';
  list ($sandboxIsAvailable, $sandboxedCommand) = sandboxCommand((string)$toolCommand, (string)$readPath, (string)$writePath, FALSE, 'poppler');
  if (!$sandboxIsAvailable) errorEntry('Bubblewrap is missing or non functional, so PDFs cannot be merged or split in isolation!', 42005, FALSE);
  else {
    $ToolRan = TRUE;
    $ToolOutput = (string)shell_exec('cd '.escapeshellarg((string)$folderPath).' && '.$sandboxedCommand.' 2>&1');
    if ($Verbose && trim($ToolOutput) !== '') logEntry('A PDF tool returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, trim($ToolOutput)))); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $sandboxIsAvailable, $sandboxedCommand, $toolCommand, $readPath, $writePath, $folderPath);
  return array($ToolRan, $ToolOutput); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to merge a selection of PDFs into one.
// / Accepts the PDFs, in the order their pages are to follow one another, & the name to give what it makes, in that order.
// / Returns whether a PDF was made, whether anything went wrong & the name of the PDF it made, in that order.
// / Each PDF is copied into the folder as part-1.pdf, part-2.pdf & so on, so a tool never reads a name a user chose.
// / An empty name gives what it makes the name Merged, & a name that is taken is given the next free one.
// / A selection holding one PDF a scan finds infected is not merged at all, because what it made would hold the infected one.
function mergePdfFiles($FilesToMerge, $UserFilename) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $MergeComplete = $MergeErrors = FALSE;
  $MergedFiles = array();
  $file = $cleanName = $stagedCopy = $folderPath = $mergedPath = $mergeCommand = $toolOutput = $placedName = $mergedName = '';
  $inputIsUsable = $folderWasMade = $toolRan = $fileWasPlaced = $nameWasRefused = $folderIsRemoved = $anyInputRefused = FALSE;
  $pdfUniteBinary = FALSE;
  $partNames = array();
  $pdfUniteBinary = verifiedToolPath('PDFUnite');
  $mergedName = (((string)$UserFilename !== '') ? (string)$UserFilename : 'Merged').'.pdf';
  if ($pdfUniteBinary === FALSE) {
    $MergeErrors = TRUE;
    errorEntry('PDFUnite is missing, unidentifiable, or too old, so nothing was merged!', 42000, FALSE); }
  else list ($folderWasMade, $folderPath) = makePageFolder();
  if ($folderWasMade) {
    foreach ((array)$FilesToMerge as $file) {
      // / The values before them are shredded first, as redeclare() would, because a list() cannot pass through redeclare().
      purgeSensitiveMemory($EnableMemoryProtection, $inputIsUsable, $cleanName, $stagedCopy);
      list ($inputIsUsable, $cleanName, $stagedCopy) = verifyPageInput($file);
      if (!$inputIsUsable) $anyInputRefused = TRUE;
      else if (!@copy($stagedCopy, $folderPath.DIRECTORY_SEPARATOR.'part-'.(count($partNames) + 1).'.pdf')) $anyInputRefused = TRUE;
      else {
        $partNames[] = 'part-'.(count($partNames) + 1).'.pdf';
        if ($Verbose) logEntry('User selected to Merge file '.$cleanName.'.'); } }
    if ($anyInputRefused) {
      $MergeErrors = TRUE;
      warningEntry('A PDF in the selection could not be read, so nothing was merged.'); }
    else if (count($partNames) === 0) {
      $MergeErrors = TRUE;
      warningEntry('The selection held no PDF, so nothing was merged.'); }
    else {
      $mergedPath = $folderPath.DIRECTORY_SEPARATOR.'merged.pdf';
      $mergeCommand = escapeshellarg((string)$pdfUniteBinary).' '.implode(' ', array_map('escapeshellarg', $partNames)).' '.escapeshellarg('merged.pdf');
      list ($toolRan, $toolOutput) = runPageTool($mergeCommand, $folderPath.DIRECTORY_SEPARATOR.$partNames[0], $mergedPath, $folderPath);
      clearstatcache(TRUE, $mergedPath);
      // / The PDF PDFUnite wrote is the only verdict on whether it merged anything.
      if (!$toolRan or !is_file($mergedPath) or is_link($mergedPath) or (int)filesize($mergedPath) === 0) {
        $MergeErrors = TRUE;
        errorEntry('Could not merge the PDFs into '.$mergedName.'!', 42006, FALSE); }
      else {
        list ($fileWasPlaced, $placedName, $nameWasRefused) = placeSessionFile($mergedPath, $mergedName);
        if ($fileWasPlaced) {
          $MergedFiles[] = $placedName;
          if ($Verbose) logEntry('Merged '.count($partNames).' PDF(s) into '.$placedName.'.'); }
        else if ($nameWasRefused) {
          $MergeErrors = TRUE;
          warningEntry('The name '.$mergedName.' is not one this server accepts, so the merged PDF was not kept.'); }
        else {
          $MergeErrors = TRUE;
          errorEntry('Could not place '.$mergedName.' in the session!', 42008, FALSE); } } }
    $folderIsRemoved = removePageFolder($folderPath); }
  if (count($MergedFiles) > 0) $MergeComplete = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $file, $cleanName, $stagedCopy, $folderPath, $mergedPath, $mergeCommand, $toolOutput, $placedName, $mergedName, $inputIsUsable, $folderWasMade, $toolRan, $fileWasPlaced, $nameWasRefused, $folderIsRemoved, $anyInputRefused, $pdfUniteBinary, $partNames, $FilesToMerge, $UserFilename);
  return array($MergeComplete, $MergeErrors, $MergedFiles); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read how many pages a PDF has, inside the sandbox.
// / Accepts PDFInfo, the copy of the PDF & the folder the split is made in, in that order. Returns the number of pages, or 0 when it cannot be read.
// / A PDF that is damaged, or locked with a password, has no number of pages PDFInfo will read, & is never split.
function countPdfPages($pdfInfoBinary, $stagedCopy, $folderPath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $PageCount = 0;
  $toolRan = FALSE;
  $infoCommand = $toolOutput = '';
  $pageMatch = array();
  $infoCommand = escapeshellarg((string)$pdfInfoBinary).' '.escapeshellarg((string)$stagedCopy);
  list ($toolRan, $toolOutput) = runPageTool($infoCommand, $stagedCopy, $folderPath.DIRECTORY_SEPARATOR.'info.txt', $folderPath);
  if ($toolRan && preg_match('/^Pages:\s+([0-9]+)\s*$/m', $toolOutput, $pageMatch) === 1) $PageCount = (int)$pageMatch[1];
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $toolRan, $infoCommand, $toolOutput, $pageMatch, $pdfInfoBinary, $stagedCopy, $folderPath);
  return $PageCount; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to place the pages a split made in the session.
// / Accepts the folder, the number of pages & the name of the PDF they came from, in that order.
// / Returns whether every page was placed, whether anything went wrong & the name of every page placed, in that order.
// / Page 3 of report.pdf is placed as report-3.pdf, & the next free name when that is taken.
// / The pages are measured before any is placed, & a split whose pages hold more than --Maximum Extracted Size-- allows places none of them.
function placeSplitPages($folderPath, $pageCount, $sourceName) {
  // / Set variables.
  global $Verbose, $MaxExtractedSize, $EnableMemoryProtection;
  $PagesWerePlaced = $PlaceErrors = FALSE;
  $PlacedPages = array();
  $pageNumber = $pageBytes = 0;
  $pagePath = $placedName = $baseName = '';
  $fileWasPlaced = $nameWasRefused = FALSE;
  $baseName = (string)pathinfo((string)$sourceName, PATHINFO_FILENAME);
  for ($pageNumber = 1; $pageNumber <= (int)$pageCount; $pageNumber++) {
    redeclare($pagePath, $folderPath.DIRECTORY_SEPARATOR.'page-'.$pageNumber.'.pdf');
    clearstatcache(TRUE, $pagePath);
    if (is_file($pagePath) && !is_link($pagePath)) $pageBytes += (int)filesize($pagePath);
    else $PlaceErrors = TRUE; }
  if ($PlaceErrors) errorEntry('Could not split '.$sourceName.' into one PDF for each page!', 42007, FALSE);
  else if ((int)$MaxExtractedSize > 0 && $pageBytes > (int)$MaxExtractedSize * 1048576) {
    $PlaceErrors = TRUE;
    warningEntry('The pages of '.$sourceName.' hold '.describeByteCount($pageBytes).', & --Maximum Extracted Size-- allows '.(int)$MaxExtractedSize.' MB, so none of them was kept.'); }
  else for ($pageNumber = 1; $pageNumber <= (int)$pageCount; $pageNumber++) {
    redeclare($pagePath, $folderPath.DIRECTORY_SEPARATOR.'page-'.$pageNumber.'.pdf');
    // / The values before them are shredded first, as redeclare() would, because a list() cannot pass through redeclare().
    purgeSensitiveMemory($EnableMemoryProtection, $fileWasPlaced, $placedName, $nameWasRefused);
    list ($fileWasPlaced, $placedName, $nameWasRefused) = placeSessionFile($pagePath, $baseName.'-'.$pageNumber.'.pdf');
    if ($fileWasPlaced) $PlacedPages[] = $placedName;
    else {
      $PlaceErrors = TRUE;
      errorEntry('Could not place page '.$pageNumber.' of '.$sourceName.' in the session!', 42008, FALSE); } }
  if (!$PlaceErrors && count($PlacedPages) === (int)$pageCount) $PagesWerePlaced = TRUE;
  if ($Verbose && count($PlacedPages) > 0) logEntry('Split '.$sourceName.' into '.count($PlacedPages).' PDF(s).');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $pageNumber, $pageBytes, $pagePath, $placedName, $baseName, $fileWasPlaced, $nameWasRefused, $folderPath, $pageCount, $sourceName);
  return array($PagesWerePlaced, $PlaceErrors, $PlacedPages); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to split a selection of PDFs, making a PDF of each page of each one.
// / Accepts the PDFs. Returns whether any page was made, whether anything went wrong & the name of every page made, in that order.
// / The pages are counted first, & a PDF with more pages than --Maximum Extracted Files-- allows is refused before it is split, as an archive holding too many files is.
// / PDFSeparate reads the staged copy read only & writes page-1.pdf, page-2.pdf & so on into a folder of its own.
function splitPdfFiles($FilesToSplit) {
  // / Set variables.
  global $MaxExtractedFiles, $EnableMemoryProtection;
  $SplitComplete = $SplitErrors = FALSE;
  $SplitFiles = array();
  $file = $cleanName = $stagedCopy = $folderPath = $splitCommand = $toolOutput = '';
  $inputIsUsable = $folderWasMade = $toolRan = $pagesWerePlaced = $placeErrors = $folderIsRemoved = FALSE;
  $pdfSeparateBinary = $pdfInfoBinary = FALSE;
  $pageCount = 0;
  $placedPages = array();
  $pdfSeparateBinary = verifiedToolPath('PDFSeparate');
  $pdfInfoBinary = verifiedToolPath('PDFInfo');
  if ($pdfSeparateBinary === FALSE or $pdfInfoBinary === FALSE) {
    $SplitErrors = TRUE;
    $FilesToSplit = array();
    errorEntry('PDFSeparate or PDFInfo is missing, unidentifiable, or too old, so nothing was split!', 42000, FALSE); }
  foreach ((array)$FilesToSplit as $file) {
    redeclare($folderWasMade, FALSE);
    // / The values before them are shredded first, as redeclare() would, because a list() cannot pass through redeclare().
    purgeSensitiveMemory($EnableMemoryProtection, $inputIsUsable, $cleanName, $stagedCopy);
    list ($inputIsUsable, $cleanName, $stagedCopy) = verifyPageInput($file);
    if (!$inputIsUsable) {
      $SplitErrors = TRUE;
      continue; }
    purgeSensitiveMemory($EnableMemoryProtection, $folderPath);
    list ($folderWasMade, $folderPath) = makePageFolder();
    if (!$folderWasMade) $SplitErrors = TRUE;
    else {
      redeclare($pageCount, countPdfPages($pdfInfoBinary, $stagedCopy, $folderPath));
      if ($pageCount < 1) {
        $SplitErrors = TRUE;
        warningEntry('The pages of '.$cleanName.' could not be counted, so it was not split. It may be damaged or locked with a password.'); }
      else if ((int)$MaxExtractedFiles > 0 && $pageCount > (int)$MaxExtractedFiles) {
        $SplitErrors = TRUE;
        warningEntry('The PDF '.$cleanName.' has '.$pageCount.' pages, & --Maximum Extracted Files-- allows '.(int)$MaxExtractedFiles.', so it was not split.'); }
      else {
        redeclare($splitCommand, escapeshellarg((string)$pdfSeparateBinary).' '.escapeshellarg((string)$stagedCopy).' '.escapeshellarg($folderPath.DIRECTORY_SEPARATOR.'page-%d.pdf'));
        purgeSensitiveMemory($EnableMemoryProtection, $toolRan, $toolOutput);
        list ($toolRan, $toolOutput) = runPageTool($splitCommand, $stagedCopy, $folderPath.DIRECTORY_SEPARATOR.'page-%d.pdf', $folderPath);
        purgeSensitiveMemory($EnableMemoryProtection, $pagesWerePlaced, $placeErrors, $placedPages);
        list ($pagesWerePlaced, $placeErrors, $placedPages) = $toolRan ? placeSplitPages($folderPath, $pageCount, $cleanName) : array(FALSE, TRUE, array());
        if ($placeErrors or !$pagesWerePlaced) $SplitErrors = TRUE;
        redeclare($SplitFiles, array_merge($SplitFiles, $placedPages)); }
      redeclare($folderIsRemoved, removePageFolder($folderPath)); } }
  if (count($SplitFiles) > 0) $SplitComplete = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $file, $cleanName, $stagedCopy, $folderPath, $splitCommand, $toolOutput, $inputIsUsable, $folderWasMade, $toolRan, $pagesWerePlaced, $placeErrors, $folderIsRemoved, $pdfSeparateBinary, $pdfInfoBinary, $pageCount, $placedPages, $FilesToSplit);
  return array($SplitComplete, $SplitErrors, $SplitFiles); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The entry point the Pipeline Core calls.
// / Accepts the PDFs, the name to give what a merge makes, the extension, which is not read, & the method, merge or split, in that order.
// / Returns whether anything was made, whether anything went wrong & the name of every PDF made, in that order.
// / One entry point & a method, rather than one pipeline for each, because a merge & a split read the same files with tools from the same package.
function operateOnPages($FilesToProcess, $UserFilename, $UserExtension, $Method) {
  // / Set variables.
  global $EnableMemoryProtection;
  $PagesComplete = $PagesErrors = FALSE;
  $PageFiles = array();
  if (!is_array($FilesToProcess)) $FilesToProcess = array($FilesToProcess);
  if ((string)$Method === 'merge') list ($PagesComplete, $PagesErrors, $PageFiles) = mergePdfFiles($FilesToProcess, $UserFilename);
  else if ((string)$Method === 'split') list ($PagesComplete, $PagesErrors, $PageFiles) = splitPdfFiles($FilesToProcess);
  else {
    $PagesErrors = TRUE;
    warningEntry('The PDF pipeline was asked for a method it does not have, so nothing was made.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $FilesToProcess, $UserFilename, $UserExtension, $Method);
  return array($PagesComplete, $PagesErrors, $PageFiles); }
// / -----------------------------------------------------------------------------------
?>
