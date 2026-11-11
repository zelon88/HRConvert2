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
// / v4.1.1.
// / The OCR pipeline. pipelineCore.php loads it only to dispatch an OCR operation.
// / Error block 15000 through 15014 belongs to this pipeline.
// / It also raises 8001 for ImageMagick.
// / OCR is an operation pipeline, which takes a selection of files & decides a route per file.
// / It returns three values, the third being the filenames it produced, which the core prints.
// / A pipeline never prints. See contract 6.
// / It is dispatched by runOperationPipeline('OCR'), never through convert().
// / It declares the LibreOffice shared module, as the Document pipeline does, & one request loads it once.
// / Tesseract, Poppler Utils & ImageMagick come from verifiedToolPath().
// / Files are scanned through runVirusScan(), & verifyFile() is the core's.
// / This pipeline carries its own ImageMagick policy, in Policy/policy.xml beside this file.
// / It covers the page split that hands Tesseract an image, & nothing is written into the host.
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
// / A PDF is read directly by pdftotext, or rasterized & read page by page by Tesseract.
// / A document is converted to PDF by LibreOffice, through the shared module.
// / An image is read directly by Tesseract, or converted to PDF & read by pdftotext.
// / Every route produces a text file, which is then converted to the requested output.
// / Every binary comes from verifiedToolPath(), so the version verified is the version that runs.
// / Every tool is sandboxed, & an operation that cannot be isolated is refused.
// / The ImageMagick page split is the exception, because policy.xml is a native control it keeps.
function ocrFiles($PDFWorkSelected, $UserFilename, $UserExtension, $Method) {
  // / Set variables.
  global $Verbose, $VirusScan, $ConvertTempDir, $Lol, $Lolol, $Append, $DirSep, $EnableMemoryProtection;
  $documentConverted = $OperationSuccessful = $OperationErrors = $multiple = $virusFound = $skip = $variableIsSanitized = FALSE;
  $fileIsVerified = $scanComplete = $documentEngineStarted = $commandMayRun = $anyFileSucceeded = $loopCheck = FALSE;
  $ocrToolsAreValid = FALSE;
  $tesseractBinary = $pdftotextBinary = $imageBinary = FALSE;
  $clean = $copy = TRUE;
  $returnData = $file = $pathname = $oldPathname = $oldExtension = $newPathname = $verifiedFilename = '';
  $pathnameTEMP = $pathnameTEMP0 = $pathnameTEMP1 = $pathnameTEMP3 = $pathnameTEMPTesseract = '';
  $filename = $cleanFilname = $pageNumber = $pagedFile = $readPageData = '';
  $ocrCommand = $policyFolder = '';
  $policyVariables = array();
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
  // / Each tool by its manifest name. Both are required, matching the check it replaces.
  $tesseractBinary = verifiedToolPath('Tesseract');
  $pdftotextBinary = verifiedToolPath('Poppler Utils');
  $ocrToolsAreValid = ($tesseractBinary !== FALSE && $pdftotextBinary !== FALSE);
  // / ImageMagick rasterizes a PDF page for the advanced route & is verified separately.
  $imageBinary = verifiedToolPath('ImageMagick');
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
    // / The name verifyFile returns belongs to this file alone, so the next file is still handed the name the user asked for.
    // / The name the file before it was given is shredded first, as redeclare() would, because a list() cannot pass through redeclare().
    purgeSensitiveMemory($EnableMemoryProtection, $verifiedFilename);
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname, $verifiedFilename) = verifyFile($file, $UserFilename, $UserExtension, $clean, $copy, $skip);
    if (!$fileIsVerified) {
      $OperationErrors = TRUE;
      errorEntry('Could not verify the input file.', 15001, FALSE);
      continue; }
    else if ($Verbose) logEntry('Verified file '.$newPathname.'.');
    $pathnameTEMP = str_replace('..', '', str_replace('.'.$oldExtension, '.txt', $pathname));
      // / The file is scanned through runVirusScan() before any OCR tool reads it, when $VirusScan is set.
      // / An infected file is refused here, not deleted.
      // / Deleting is the application's policy, set by $AllowUserVirusScan, which a pipeline cannot read.
      // / Every file is also scanned when it lands, which is where the application deletes one.
    if ($VirusScan) {
      if ($Verbose) logEntry('Starting virus scan.');
      list ($scanComplete, $virusFound) = runVirusScan(array($pathname), '');
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
              // / The policy this pipeline carries goes with the command. In the sandbox it is the only
              // / ImageMagick configuration there is, & outside one MAGICK_CONFIGURE_PATH has it read first.
              $policyFolder = dirname(__FILE__).$DirSep.'Policy';
              if (is_file($policyFolder.$DirSep.'policy.xml')) $policyVariables = array('MAGICK_CONFIGURE_PATH' => $policyFolder);
              else {
                $policyFolder = '';
                warningEntry('The ImageMagick policy the OCR pipeline carries is missing, so this page split runs under the host\'s own. Restore the pipeline from the release this installation came from.'); }
              // / The policy.xml the installed ImageMagick reads is covered too, wherever its build keeps it.
              if ($policyFolder !== '' && function_exists('coverResolvedImageMagickPolicy')) coverResolvedImageMagickPolicy('tesseract');
              list ($commandMayRun, $ocrCommand) = sandboxCommand($ocrCommand, $pathname, $pathnameTEMP1, FALSE, 'tesseract', $policyFolder, $policyVariables);
              // / ImageMagick has a policy, so an unavailable sandbox is a downgrade to a
              // / weaker control rather than to no control at all. The operation continues.
              if (!$commandMayRun) warningEntry('Bubblewrap is unavailable. This OCR page split will run unsandboxed under the ImageMagick policy this pipeline carries, which is read first, so the host\'s own policy still decides any coder it names.');
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
          // / The following code verifies that the Document Conversion Engine is installed & usable.
          list ($documentEngineStarted, $documentEnginePID) = verifyDocumentConversionEngine();
          if (!$documentEngineStarted) {
            $OperationErrors = TRUE;
            errorEntry('Could not verify the Document Conversion Engine!', 15007, FALSE); }
          else {
            // / Perform the conversion with LibreOffice, sandboxed, one process per file.
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
          // / The following code verifies that the Document Conversion Engine is installed & usable.
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
        // / The following code verifies that the Document Conversion Engine is installed & usable.
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
        // / A pipeline never prints, because the core decides what the interface receives.
        // / A stray warning printed here would be read by the interface as another output filename.
        array_push($OutputFilenames, $verifiedFilename); }
      else errorEntry('Could not create a file at '.$newPathname.'!', 15011, FALSE); }
    // / Record that at least one file in this request succeeded.
    // / $loopCheck is reset on every iteration, so without this the result would reflect
    // / only the LAST file rather than the whole request.
    if ($loopCheck) $anyFileSucceeded = TRUE; }
  // / Error handler for if any failures happened during file loops.
  if ($anyFileSucceeded) $OperationSuccessful = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $file, $pathname, $oldPathname, $filename, $oldExtension, $newPathname, $doc1array, $img1array, $pdf1array, $pathnameTEMP, $pathnameTEMP0, $pathnameTEMP1, $pathnameTEMP3, $pagedFilesArrRAW, $pagedFile, $cleanFilname, $pageNumber, $readPageData, $writePageData, $multiple, $pathnameTEMPTesseract, $clean, $copy, $skip, $allowedOCR, $variableIsSanitized, $loopCheck, $anyFileSucceeded, $ocrCommand, $commandMayRun, $fileIsVerified, $scanComplete, $virusFound, $documentEngineStarted, $documentEnginePID, $returnData, $ocrToolsAreValid, $tesseractBinary, $pdftotextBinary, $imageBinary, $policyFolder, $policyVariables, $PDFWorkSelected, $UserFilename, $UserExtension, $Method, $documentConverted, $verifiedFilename);
  return array($OperationSuccessful, $OperationErrors, $OutputFilenames); }
// / -----------------------------------------------------------------------------------

?>
