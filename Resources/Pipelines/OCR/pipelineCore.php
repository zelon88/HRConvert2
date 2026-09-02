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
// / v3.8.6.
// / This file is the converter for the OCR pipeline. It is loaded by pipelineManager.php
// / ONLY when a OCR operation is about to be dispatched to it, so a request that does
// / something else never parses a line of it.
// / Error block 15000 through 15014 belongs to this pipeline. Those numbers came with the code when it
// / moved out of convertCore.php & they did not change, because operators have read them.
// / OCR is an operation pipeline rather than a conversion pipeline.
// / It takes a SELECTION of files, decides a route per file, & returns three values rather
// / than the six a conversion pipeline returns. The third is the list of filenames it
// / produced, which the CORE prints once this function has returned. A pipeline never
// / prints. See contract 6. It is dispatched by runOcrOperation() from
// / the point in the core where an OCR request arrives, never through convert().
// / This pipeline declares the LibreOffice shared module, which the Document pipeline also
// / declares. Both in one request load it once between them.
// / verifyOCRVersions(), verifyImageVersion(), sandboxCommand(), verifyFile(), virusScan()
// / & locateDependency() all remain in convertCore.php.
// / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt for the contracts this file obeys.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline component cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to OCR a selection of files.
// / Three input families are handled & each takes a different route.
// / A PDF is read directly by pdftotext, or rasterized & read page by page by Tesseract.
// / A document is converted to PDF by the document conversion engine.
// / An image is read directly by Tesseract, or converted to PDF & read by pdftotext.
// / Every route produces a text file which is then converted to the requested output.
// / Every binary is located & verified rather than assumed, so the version that was
// / verified is provably the version that runs.
// / Tesseract, pdftotext & ImageMagick are sandboxed. The document conversion engine is
// / not, because it is a persistent listener rather than a process launched per conversion.
// / An operation that cannot be isolated is refused, except for the ImageMagick page split,
// / which has policy.xml as a native control & is therefore only downgraded rather than
// / left with no boundary at all.
function ocrFiles($PDFWorkSelected, $UserFilename, $UserExtension, $Method) {
  // / Set variables.
  global $Verbose, $VirusScan, $ConvertTempDir, $Lol, $Lolol, $Append, $MinimumTesseractVersion, $MinimumPdftotextVersion, $MinimumImageVersion, $EnableMemoryProtection;
  $documentConverted = $OperationSuccessful = $OperationErrors = $multiple = $virusFound = $skip = $variableIsSanitized = FALSE;
  $fileIsVerified = $scanComplete = $documentEngineStarted = $commandMayRun = $anyFileSucceeded = $loopCheck = FALSE;
  $ocrToolsAreValid = FALSE;
  $tesseractBinary = $pdftotextBinary = $imageBinary = FALSE;
  $clean = $copy = TRUE;
  $returnData = $file = $pathname = $oldPathname = $oldExtension = $newPathname = '';
  $pathnameTEMP = $pathnameTEMP0 = $pathnameTEMP1 = $pathnameTEMP3 = $pathnameTEMPTesseract = '';
  $filename = $cleanFilname = $pageNumber = $pagedFile = $readPageData = '';
  $ocrCommand = '';
  $documentEnginePID = $writePageData = 0;
  $pagedFilesArrRAW = array();
  // / The names of every file this operation produced, in the order they were produced.
  // / The core prints these once this function has returned. See contract 6.
  $OutputFilenames = array();
  $doc1array = array('txt', 'pages', 'doc', 'xls', 'xlsx', 'docx', 'rtf', 'odt', 'ods');
  $img1array = array('jpg', 'jpeg', 'bmp', 'webp', 'png', 'gif');
  $pdf1array = array('pdf');
  $allowedOCR = array('txt', 'doc', 'docx', 'rtf', 'xls', 'xlsx', 'ods', 'odt', 'jpg', 'jpeg', 'bmp', 'webp', 'png', 'gif', 'pdf', 'abw');
  // / Locate & verify every OCR utility before anything is read.
  // / Each route gates on the specific tool it uses rather than on the overall verdict.
  // / A missing pdftotext does not prevent an image from being read by Tesseract.
  list ($ocrToolsAreValid, $tesseractBinary, $pdftotextBinary) = verifyOCRVersions($MinimumTesseractVersion, $MinimumPdftotextVersion);
  // / ImageMagick rasterizes a PDF page for the advanced route & is verified separately.
  $imageBinary = verifyImageVersion($MinimumImageVersion);
  // / Make sure the input files are formatted into an array.
  if (!is_array($PDFWorkSelected)) $PDFWorkSelected = array($PDFWorkSelected);
  // / Iterate through the array of input files.
  foreach ($PDFWorkSelected as $file) {
    $loopCheck = $multiple = FALSE;
    // / Make sure the file is sanitized before processing it.
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $OperationErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 15000, FALSE);
      continue; }
    if ($Verbose) logEntry('User selected to perform OCR on file '.$file.'.');
    // / Verify the file before performing any operations on it.
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname, $UserFilename) = verifyFile($file, $UserFilename, $UserExtension, $clean, $copy, $skip);
    if (!$fileIsVerified) {
      $OperationErrors = TRUE;
      errorEntry('Could not verify the input file.', 15001, FALSE);
      continue; }
    else if ($Verbose) logEntry('Verified file '.$newPathname.'.');
    $pathnameTEMP = str_replace('..', '', str_replace('.'.$oldExtension, '.txt', $pathname));
    // / Scan with ClamAV if $VirusScan is set to TRUE in config.php.
    if ($VirusScan) {
      if ($Verbose) logEntry('Starting virus scan.');
      list ($scanComplete, $virusFound) = virusScan($newPathname);
      if (!$scanComplete) errorEntry('Could not perform a virus scan!', 15002, TRUE);
      if ($virusFound) errorEntry('Virus detected!', 15003, TRUE);
      if ($Verbose) logEntry('Virus scan complete.'); }
    // / Only an input format this function knows how to read is attempted.
    if (in_array(strtolower($oldExtension), $allowedOCR)) {
      // / Code to convert a PDF to a document.
      if (in_array(strtolower($oldExtension), $pdf1array)) {
        if (in_array($UserExtension, $doc1array)) {
          // / Method 0 is the automatic choice. It attempts the simple route first &
          // / falls back to the advanced one only if the simple route produces nothing.
          if ($Method === 0 or $Method === '0' or $Method === '') $Method = 1;
          // / Method 1 is the simple route. Pdftotext reads a PDF that already holds text.
          // / It is fast & exact, & produces nothing at all on a scanned page.
          if ($Method === 1 or $Method === '1') {
            if ($pdftotextBinary === FALSE) {
              $OperationErrors = TRUE;
              errorEntry('The installed pdftotext version is missing, unidentifiable, or too old!', 15014, FALSE);
              $Method = 2; }
            else {
              if ($Verbose) logEntry('Performing OCR using method 1.');
              // / Perform the conversion using PDFTOTEXT.
              $ocrCommand = escapeshellarg($pdftotextBinary).' -layout '.escapeshellarg($pathname).' '.escapeshellarg($pathnameTEMP);
              list ($commandMayRun, $ocrCommand) = sandboxCommand($ocrCommand, $pathname, $pathnameTEMP, FALSE, 'tesseract');
              // / pdftotext has no native control of its own, so an unavailable sandbox leaves
              // / no boundary at all & the operation is refused rather than run without one.
              if (!$commandMayRun) {
                $OperationErrors = TRUE;
                errorEntry('Bubblewrap is missing or non functional, so this OCR operation cannot be isolated!', 15012, FALSE); }
              else $returnData = shell_exec($ocrCommand);
              // / Log the output of the operation to the logfile, if it is not blank.
              if ($Verbose && trim($returnData) !== '') logEntry('The converter (PTT-1) returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
              // / Check if the conversion was successful and retry with method 2 if required.
              if (!file_exists($pathnameTEMP)) {
                errorEntry('Could not complete the conversion using method 1. Reattempting using method 2.', 15004, FALSE);
                $Method = 2; }
              else if ($Verbose) logEntry('A file was created at '.$pathnameTEMP); } }
          // / Method 2 is the advanced route. Each page is rasterized & read by Tesseract.
          // / It reads a scanned page that holds no text layer, & is considerably slower.
          if ($Method === 2 or $Method === '2') {
            if ($imageBinary === FALSE) {
              $OperationErrors = TRUE;
              errorEntry('The installed ImageMagick version is missing, unidentifiable, or too old!', 8001, FALSE); }
            else if ($tesseractBinary === FALSE) {
              $OperationErrors = TRUE;
              errorEntry('The installed Tesseract version is missing, unidentifiable, or too old!', 15013, FALSE); }
            else {
              $pathnameTEMP1 = str_replace('..', '', str_replace('.'.$oldExtension, '.jpg', $pathname));
              if ($Verbose) logEntry('Performing OCR intermediate operation using method 2.');
              // / Perform the conversion using ImageMagick.
              $ocrCommand = escapeshellarg($imageBinary).' '.escapeshellarg($pathname).' '.escapeshellarg($pathnameTEMP1);
              list ($commandMayRun, $ocrCommand) = sandboxCommand($ocrCommand, $pathname, $pathnameTEMP1, FALSE, 'tesseract');
              // / ImageMagick has policy.xml, so an unavailable sandbox is a downgrade to a
              // / weaker control rather than to no control at all. The operation continues.
              if (!$commandMayRun) warningEntry('Bubblewrap is unavailable. This OCR page split will run unsandboxed & is protected only by policy.xml.');
              $returnData = shell_exec($ocrCommand);
              // / Log the output of the operation to the logfile, if it is not blank.
              if ($Verbose && trim($returnData) !== '') logEntry('The converter (IM-1) returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
              // / If a file doesn't exist there is a good chance it is because ImageMagick has split the pages up.
              if (!file_exists($pathnameTEMP1)) {
                // / Scan the current directory for files matching the filename.
                $pagedFilesArrRAW = scandir($ConvertTempDir);
                foreach ($pagedFilesArrRAW as $pagedFile) {
                  $filename = pathinfo($pathname, PATHINFO_FILENAME);
                  // / Look for files with the same filename but in .jpg format. Skip the rest.
                  if (stripos($pagedFile, $filename) === FALSE) continue;
                  if (stripos($pagedFile, '.jpg') === FALSE) continue;
                  if ($pagedFile === '.' or $pagedFile === '..' or $pagedFile === '.AppData' or $pagedFile === 'index.html') continue;
                  // / Set page specific variables.
                  $pathnameTEMP1 = str_replace('..', '', str_replace('.'.$oldExtension, '.jpg', $pathname));
                  $cleanFilname = str_replace('..', '', str_replace($oldExtension, '', $filename));
                  $pageNumber = str_replace('..', '', str_replace('-', '', str_replace($cleanFilname, '', str_replace('.jpg', '', $pagedFile))));
                  $pathnameTEMP1 = str_replace('..', '', str_replace('.jpg', '-'.$pageNumber.'.jpg', $pathnameTEMP1));
                  $pathnameTEMP = str_replace('..', '', str_replace('.'.$oldExtension, '-'.$pageNumber.'.txt', $pathname));
                  $pathnameTEMPTesseract = str_replace('..', '', str_replace('.'.$oldExtension, '-'.$pageNumber, $pathname));
                  $pathnameTEMP0 = str_replace('..', '', str_replace('-'.$pageNumber.'.txt', '.txt', $pathnameTEMP));
                  if ($Verbose) logEntry('Performing OCR final operation using method 2.');
                  // / Perform the conversion using Tesseract.
                  // / Tesseract appends .txt to the output argument, so what is passed is a
                  // / prefix rather than a filename. The sandbox mounts its directory either way.
                  $ocrCommand = escapeshellarg($tesseractBinary).' '.escapeshellarg($pathnameTEMP1).' '.escapeshellarg($pathnameTEMPTesseract);
                  list ($commandMayRun, $ocrCommand) = sandboxCommand($ocrCommand, $pathnameTEMP1, $pathnameTEMPTesseract, FALSE, 'tesseract');
                  // / Tesseract has no native control of its own, so an unavailable sandbox
                  // / leaves no boundary & the operation is refused rather than run without one.
                  if (!$commandMayRun) {
                    $OperationErrors = TRUE;
                    errorEntry('Bubblewrap is missing or non functional, so this OCR operation cannot be isolated!', 15012, FALSE);
                    continue; }
                  $returnData = shell_exec($ocrCommand);
                  // / Log the output of the operation to the logfile, if it is not blank.
                  if ($Verbose && trim($returnData) !== '') logEntry('The converter (T-1) returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
                  // / The text file is the verdict on this page, not the image it was read from.
                  if (!file_exists($pathnameTEMP)) errorEntry('Could not complete the conversion using method 2.', 15005, FALSE);
                  else if ($Verbose) logEntry('A file was created at '.$pathnameTEMP);
                  // / Recompile all of the text files into one big text file.
                  $readPageData = file_get_contents($pathnameTEMP);
                  $writePageData = file_put_contents($pathnameTEMP0, $readPageData.$Lol, $Append);
                  $multiple = TRUE;
                  if (!file_exists($pathnameTEMP0)) errorEntry('Could not OCR file!', 15006, FALSE);
                  else if ($Verbose) logEntry('A file was created at '.$pathnameTEMP0); } }
              if ($Verbose) logEntry('Converted file '.$pathnameTEMP1.' to '.$pathnameTEMP.'.');
              // / A single page PDF produces one image rather than a numbered set.
              if (!$multiple) {
                $pathnameTEMPTesseract = str_replace('..', '', str_replace('.txt', '', $pathnameTEMP));
                if ($Verbose) logEntry('Performing OCR final operation using method 2.');
                $ocrCommand = escapeshellarg($tesseractBinary).' '.escapeshellarg($pathnameTEMP1).' '.escapeshellarg($pathnameTEMPTesseract);
                list ($commandMayRun, $ocrCommand) = sandboxCommand($ocrCommand, $pathnameTEMP1, $pathnameTEMPTesseract, FALSE, 'tesseract');
                if (!$commandMayRun) {
                  $OperationErrors = TRUE;
                  errorEntry('Bubblewrap is missing or non functional, so this OCR operation cannot be isolated!', 15012, FALSE); }
                else $returnData = shell_exec($ocrCommand);
                // / Log the output of the operation to the logfile, if it is not blank.
                if ($Verbose && trim($returnData) !== '') logEntry('The converter (T-2) returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData))))); } } } } }
      // / Code to convert a document to a PDF.
      if (in_array(strtolower($oldExtension), $doc1array)) {
        if (in_array($UserExtension, $pdf1array)) {
          // / The following code verifies that the Document Conversion Engine is installed & running.
          list ($documentEngineStarted, $documentEnginePID) = verifyDocumentConversionEngine();
          if (!$documentEngineStarted) {
            $OperationErrors = TRUE;
            errorEntry('Could not verify the Document Conversion Engine!', 15007, FALSE); }
          else {
            // / Perform the conversion with LibreOffice, sandboxed, one process per file.
            // / The document conversion engine is a persistent listener rather than a process
            // / launched per conversion, so it cannot be sandboxed the way the others are.
            list ($documentConverted, $returnData) = convertWithLibreOffice($pathname, $newPathname, 'pdf');
            // / Log the output of the operation to the logfile, if it is not blank.
            if ($Verbose && trim($returnData) !== '') logEntry('The converter (U-1) returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData))))); } } }
      // / Code to convert an image to text.
      if (in_array(strtolower($oldExtension), $img1array)) {
        if ($tesseractBinary === FALSE) {
          $OperationErrors = TRUE;
          errorEntry('The installed Tesseract version is missing, unidentifiable, or too old!', 15013, FALSE); }
        else {
          $pathnameTEMPTesseract = str_replace('..', '', str_replace('.'.$oldExtension, '', $pathname));
          if ($Verbose) logEntry('Reading the image with Tesseract.');
          // / Perform the conversion using Tesseract.
          $ocrCommand = escapeshellarg($tesseractBinary).' '.escapeshellarg($pathname).' '.escapeshellarg($pathnameTEMPTesseract);
          list ($commandMayRun, $ocrCommand) = sandboxCommand($ocrCommand, $pathname, $pathnameTEMPTesseract, FALSE, 'tesseract');
          if (!$commandMayRun) {
            $OperationErrors = TRUE;
            errorEntry('Bubblewrap is missing or non functional, so this OCR operation cannot be isolated!', 15012, FALSE); }
          else $returnData = shell_exec($ocrCommand);
          // / Log the output of the operation to the logfile, if it is not blank.
          if ($Verbose && trim($returnData) !== '') logEntry('The converter (T-3) returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData))))); }
        // / An image Tesseract could not read is converted to PDF & read by pdftotext instead.
        if (!file_exists($pathnameTEMP)) {
          $pathnameTEMP3 = str_replace('..', '', str_replace('.'.$oldExtension, '.pdf', $pathname));
          // / The following code verifies that the Document Conversion Engine is installed & running.
          list ($documentEngineStarted, $documentEnginePID) = verifyDocumentConversionEngine();
          if (!$documentEngineStarted) {
            $OperationErrors = TRUE;
            errorEntry('Could not verify the Document Conversion Engine!', 15008, FALSE); }
          else if ($pdftotextBinary === FALSE) {
            $OperationErrors = TRUE;
            errorEntry('The installed pdftotext version is missing, unidentifiable, or too old!', 15014, FALSE); }
          else {
            if ($Verbose) logEntry('Tesseract produced nothing. Converting the image to PDF instead.');
            // / Perform the conversion with LibreOffice, sandboxed, one process per file.
            list ($documentConverted, $returnData) = convertWithLibreOffice($pathname, $pathnameTEMP3, 'pdf');
            // / Log the output of the operation to the logfile, if it is not blank.
            if ($Verbose && trim($returnData) !== '') logEntry('The converter (U-2) returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
            // / Perform the conversion using PDFTOTEXT.
            $ocrCommand = escapeshellarg($pdftotextBinary).' -layout '.escapeshellarg($pathnameTEMP3).' '.escapeshellarg($pathnameTEMP);
            list ($commandMayRun, $ocrCommand) = sandboxCommand($ocrCommand, $pathnameTEMP3, $pathnameTEMP, FALSE, 'tesseract');
            if (!$commandMayRun) {
              $OperationErrors = TRUE;
              errorEntry('Bubblewrap is missing or non functional, so this OCR operation cannot be isolated!', 15012, FALSE); }
            else $returnData = shell_exec($ocrCommand);
            // / Log the output of the operation to the logfile, if it is not blank.
            if ($Verbose && trim($returnData) !== '') logEntry('The converter (PTT-2) returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData))))); } }
        if ($Verbose && file_exists($pathnameTEMP)) logEntry('Created an intermediate file at '.$pathnameTEMP.'.');
        if (!file_exists($pathnameTEMP)) {
          $OperationErrors = TRUE;
          errorEntry('Could not create an intermediate file at '.$pathnameTEMP.'!', 15009, FALSE); } }
      // / If the output file is a txt file we leave it as-is.
      if ($UserExtension === 'txt') {
        if (file_exists($pathnameTEMP)) {
          rename($pathnameTEMP, $newPathname);
          if ($Verbose) logEntry('Renamed file '.$pathnameTEMP.' to '.$newPathname.'.'); } }
      // / If the output file is not a txt file we convert it with LibreOffice.
      else {
        // / The following code verifies that the Document Conversion Engine is installed & running.
        list ($documentEngineStarted, $documentEnginePID) = verifyDocumentConversionEngine();
        if (!$documentEngineStarted) {
          $OperationErrors = TRUE;
          errorEntry('Could not verify the Document Conversion Engine!', 15010, FALSE); }
        else {
          // / Perform the conversion with LibreOffice, sandboxed, one process per file.
          list ($documentConverted, $returnData) = convertWithLibreOffice($pathnameTEMP, $newPathname, $UserExtension);
          // / Log the output of the operation to the logfile, if it is not blank.
          if ($Verbose && trim($returnData) !== '') logEntry('The converter (U-3) returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData))))); } }
      // / Error handler for if the output file does not exist.
      if (file_exists($newPathname)) {
        $loopCheck = TRUE;
        // / A pipeline never prints. It collects & returns, & the core decides.
        // / This line used to print the filename straight into the AJAX response, which it
        // / could do safely while it lived in convertCore.php. It is a component now, & a
        // / component writing into a reply the interface parses line by line is the exact
        // / failure contract 6 exists to prevent. One undefined variable in here would
        // / become a PHP warning that the interface reads as another output filename.
        array_push($OutputFilenames, $UserFilename); }
      else errorEntry('Could not create a file at '.$newPathname.'!', 15011, FALSE); }
    // / Record that at least one file in this request succeeded.
    // / $loopCheck is reset on every iteration, so without this the result would reflect
    // / only the LAST file rather than the whole request.
    if ($loopCheck) $anyFileSucceeded = TRUE; }
  // / Error handler for if any failures happened during file loops.
  if ($anyFileSucceeded) $OperationSuccessful = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $file, $pathname, $oldPathname, $filename, $oldExtension, $newPathname, $doc1array, $img1array, $pdf1array, $pathnameTEMP, $pathnameTEMP0, $pathnameTEMP1, $pathnameTEMP3, $pagedFilesArrRAW, $pagedFile, $cleanFilname, $pageNumber, $readPageData, $writePageData, $multiple, $pathnameTEMPTesseract, $clean, $copy, $skip, $allowedOCR, $variableIsSanitized, $loopCheck, $anyFileSucceeded, $ocrCommand, $commandMayRun, $fileIsVerified, $scanComplete, $virusFound, $documentEngineStarted, $documentEnginePID, $returnData, $ocrToolsAreValid, $tesseractBinary, $pdftotextBinary, $imageBinary, $PDFWorkSelected, $UserFilename, $UserExtension, $Method, $documentConverted);
  return array($OperationSuccessful, $OperationErrors, $OutputFilenames); }
// / -----------------------------------------------------------------------------------

?>
