<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRConvert2, Copyright on 11/12/2026 by Justin Grimes, www.github.com/zelon88
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
// / The Extractor pipeline. pipelineCore.php loads it only to dispatch an Extract operation.
// / Error block 39100 through 39104 belongs to this pipeline.
// / It also raises 39000 through 39003, which belong to the archiveExtraction.php shared module it uses.
// / The Extractor is an operation pipeline, which takes a selection of archives & puts every file each one holds into the session beside it.
// / It returns three values, the third being the name of every file it placed, which the core prints for a button & a workflow passes on.
// / A pipeline never prints. See contract 6.
// / It is dispatched by runOperationPipeline('Extract'), never through convert().
// / The extraction is extractArchiveContained(), in the shared module, & it is held to --Maximum Extracted Size-- & --Maximum Extracted Files--.
// / What a session may hold is this application's policy, so every file is placed by the core's placeSessionFile(), which holds it to the names & formats an upload is.
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
// / A function to gather the files an extraction made, & take away everything else it made.
// / Accepts the folder an archive was extracted into.
// / Returns whether the whole folder was read, the files found & how many other things were taken away, in that order.
// / Each file found is a record of its Path & its Name, in the order a person would sort their paths, so one archive always gives up its files in one order.
// / Only a plain file is gathered. A link is removed rather than followed, because what it names is not in the archive, & so is anything that is neither a file nor a folder.
// / Every folder is given to its owner to read, write & enter, & every file to its owner to read & write.
// / So nothing an archive said about them keeps this application from moving a file, or a scanner from reading one.
function collectExtractedFiles($contentsFolder) {
  // / Set variables.
  global $EnableMemoryProtection;
  $WalkCompleted = TRUE;
  $FileRecords = array();
  $ItemsTakenAway = 0;
  $pendingFolders = $folderListing = $entryStatus = $foundRecords = array();
  $rootPath = $currentFolder = $entryName = $entryPath = '';
  $entryType = 0;
  $rootPath = rtrim((string)$contentsFolder, DIRECTORY_SEPARATOR);
  $pendingFolders = array($rootPath);
  while ($WalkCompleted && count($pendingFolders) > 0) {
    redeclare($currentFolder, (string)array_pop($pendingFolders));
    redeclare($folderListing, @scandir($currentFolder));
    if (!is_array($folderListing)) $WalkCompleted = FALSE;
    else foreach ($folderListing as $entryName) {
      if ($entryName === '.' or $entryName === '..') continue;
      redeclare($entryPath, $currentFolder.DIRECTORY_SEPARATOR.$entryName);
      redeclare($entryStatus, @lstat($entryPath));
      if (!is_array($entryStatus)) continue;
      redeclare($entryType, (int)$entryStatus['mode'] & 0170000);
      if ($entryType === 0040000) {
        @chmod($entryPath, 0700);
        $pendingFolders[] = $entryPath; }
      else if ($entryType === 0100000) {
        @chmod($entryPath, 0600);
        $foundRecords[substr($entryPath, strlen($rootPath) + 1)] = array('Path' => $entryPath, 'Name' => $entryName); }
      else {
        @unlink($entryPath);
        $ItemsTakenAway++; } } }
  uksort($foundRecords, 'strnatcasecmp');
  $FileRecords = array_values($foundRecords);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $pendingFolders, $folderListing, $entryStatus, $foundRecords, $rootPath, $currentFolder, $entryName, $entryPath, $entryType, $contentsFolder);
  return array($WalkCompleted, $FileRecords, $ItemsTakenAway); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to extract a selection of archives into the session.
// / Accepts the archives, a name, an extension & a method, in that order. The last three are not read, because each file is named as its archive names it.
// / Returns whether any file was extracted, whether anything went wrong & the name of every file it placed, in that order.
// / Each archive is extracted into a folder of its own inside the session, so moving a file out of it is a rename & never a copy.
// / That folder's name begins with a dot & holds a random part, so it is named like nothing a user has, & a page lists no folder.
// / What the archive held is gathered flat. A file in a folder in the archive joins the session under its own name, & the folder is not kept.
// / An archive is scanned before it is opened & what it held is scanned before any of it is placed, when $VirusScan is set.
// / A threat in what it held refuses the whole archive, so nothing from it reaches the session.
// / An infected archive is refused here, not deleted. Deleting is the application's policy, set by $AllowUserVirusScan, which a pipeline cannot read.
// / The folder an archive was extracted into is removed whatever happened, & a file left in it is a file that was refused.
function extractSelection($FilesToExtract, $UserFilename, $UserExtension, $Method) {
  // / Set variables.
  global $Verbose, $VirusScan, $ConvertDir, $DearchiveArray, $EnableMemoryProtection;
  $ExtractComplete = $ExtractErrors = FALSE;
  $ExtractedFiles = array();
  $file = $fileExtension = $pathname = $oldPathname = $oldExtension = $newPathname = $resolvedFilename = '';
  $stagingFolder = $contentsFolder = $extractionRefusal = $placedName = $scannerUsed = '';
  $variableIsSanitized = $fileIsVerified = $limitsAreConfigured = $extractionSucceeded = $walkCompleted = $scanComplete = $virusFound = FALSE;
  $stagingIsRemoved = $fileWasPlaced = $nameWasRefused = $archiveGaveFiles = $contentsAreClean = FALSE;
  $byteLimit = $entryLimit = $extractedBytes = $extractedEntries = $itemsTakenAway = $namesRefused = 0;
  $fileRecords = $fileRecord = $scanFindings = array();
  // / Make sure the input files are formatted into an array.
  if (!is_array($FilesToExtract)) $FilesToExtract = array($FilesToExtract);
  list ($limitsAreConfigured, $byteLimit, $entryLimit) = extractionLimits();
  foreach ($FilesToExtract as $file) {
    // / Each archive decides these for itself, so one archive never speaks for the next.
    redeclare($archiveGaveFiles, FALSE);
    redeclare($contentsAreClean, FALSE);
    redeclare($namesRefused, 0);
    // / Make sure the file is sanitized before processing it.
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $ExtractErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 39100, FALSE);
      continue; }
    if ($Verbose) logEntry('User selected to Extract file '.$file.'.');
    // / Only an archive config.php lists as one this server reads is opened, so a request a page did not send is refused before anything is staged.
    redeclare($fileExtension, getExtension($file));
    if (!in_array($fileExtension, (array)$DearchiveArray, TRUE)) {
      $ExtractErrors = TRUE;
      warningEntry('The file '.$file.' is not an archive this server extracts, so nothing was extracted from it.');
      continue; }
    // / verifyFile() stages a copy in the temporary folder, & the copy is what 7-Zip reads.
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname, $resolvedFilename) = verifyFile($file, '', '', TRUE, TRUE, FALSE);
    if (!$fileIsVerified) {
      $ExtractErrors = TRUE;
      errorEntry('Could not verify the input file.', 39101, FALSE);
      continue; }
    // / Scan the archive with the default scanner if $VirusScan is set to TRUE in config.php.
    if ($VirusScan) {
      if ($Verbose) logEntry('Starting virus scan.');
      list ($scanComplete, $virusFound, $scanFindings, $scannerUsed) = runVirusScan(array($pathname), '');
      if (!$scanComplete) {
        $ExtractErrors = TRUE;
        errorEntry('Could not perform a virus scan of '.$file.', so nothing was extracted from it!', 39102, FALSE);
        continue; }
      if ($virusFound) {
        $ExtractErrors = TRUE;
        warningEntry('A virus was found in '.$file.', so nothing was extracted from it.');
        continue; } }
    redeclare($stagingFolder, $ConvertDir.'.extract-'.bin2hex(random_bytes(8)));
    redeclare($contentsFolder, $stagingFolder.DIRECTORY_SEPARATOR.'contents');
    if (!@mkdir($contentsFolder, 0700, TRUE)) {
      $ExtractErrors = TRUE;
      errorEntry('A folder to extract '.$file.' into could not be made, so nothing was extracted from it!', 39103, FALSE); }
    else {
      list ($extractionSucceeded, $extractionRefusal, $extractedBytes, $extractedEntries) = extractArchiveContained($pathname, $contentsFolder, $stagingFolder.DIRECTORY_SEPARATOR.'unwrap', $byteLimit, $entryLimit);
      // / A refusal was logged where it was decided, with its reason.
      if (!$extractionSucceeded) $ExtractErrors = TRUE;
      else {
        if ($Verbose) logEntry('Extracted '.$extractedEntries.' file(s) & folder(s) holding '.describeByteCount($extractedBytes).' from '.$file.'.');
        list ($walkCompleted, $fileRecords, $itemsTakenAway) = collectExtractedFiles($contentsFolder);
        if ($itemsTakenAway > 0) warningEntry('Left out '.$itemsTakenAway.' link(s) or special file(s) '.$file.' held, because only files are extracted.');
        if (!$walkCompleted) {
          $ExtractErrors = TRUE;
          warningEntry('What was extracted from '.$file.' could not all be read back, so none of it was kept.'); }
        else if (count($fileRecords) === 0) {
          $ExtractErrors = TRUE;
          warningEntry('The archive '.$file.' held no files, so nothing was extracted from it.'); }
        // / What the archive held is scanned as one folder, so a scanner starts once rather than once for every file.
        else if ($VirusScan) {
          if ($Verbose) logEntry('Starting virus scan.');
          list ($scanComplete, $virusFound, $scanFindings, $scannerUsed) = runVirusScan(array($contentsFolder), '');
          if (!$scanComplete) {
            $ExtractErrors = TRUE;
            errorEntry('Could not perform a virus scan of what '.$file.' held, so nothing was extracted from it!', 39102, FALSE); }
          else if ($virusFound) {
            $ExtractErrors = TRUE;
            warningEntry('A virus was found in what '.$file.' held, so nothing was extracted from it.'); }
          else $contentsAreClean = TRUE; }
        else $contentsAreClean = TRUE;
        if ($contentsAreClean) {
          foreach ($fileRecords as $fileRecord) {
            list ($fileWasPlaced, $placedName, $nameWasRefused) = placeSessionFile($fileRecord['Path'], $fileRecord['Name']);
            if ($fileWasPlaced) {
              $ExtractedFiles[] = $placedName;
              $archiveGaveFiles = TRUE;
              if ($Verbose) logEntry('Extracted file '.$placedName.' from '.$file.'.'); }
            else if ($nameWasRefused) $namesRefused++;
            else {
              $ExtractErrors = TRUE;
              errorEntry('Could not place '.$fileRecord['Name'].' from '.$file.' in the session!', 39104, FALSE); } }
          if ($namesRefused > 0) warningEntry('Left out '.$namesRefused.' file(s) '.$file.' held, whose names or formats this server does not accept.');
          if (!$archiveGaveFiles) $ExtractErrors = TRUE; } } }
    // / The folder goes whatever happened, & the session is the root it may be removed from, so the folder itself goes too.
    // / A folder that was never made has nothing to remove.
    clearstatcache(TRUE, $stagingFolder);
    redeclare($stagingIsRemoved, (!file_exists($stagingFolder) or cleanFiles($stagingFolder, array($ConvertDir))));
    if (!$stagingIsRemoved) warningEntry('The folder '.$file.' was extracted into could not be removed. It is removed with the session.'); }
  if (count($ExtractedFiles) > 0) $ExtractComplete = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $file, $fileExtension, $pathname, $oldPathname, $oldExtension, $newPathname, $resolvedFilename, $stagingFolder, $contentsFolder, $extractionRefusal, $placedName, $scannerUsed, $variableIsSanitized, $fileIsVerified, $limitsAreConfigured, $extractionSucceeded, $walkCompleted, $scanComplete, $virusFound, $stagingIsRemoved, $fileWasPlaced, $nameWasRefused, $archiveGaveFiles, $contentsAreClean, $byteLimit, $entryLimit, $extractedBytes, $extractedEntries, $itemsTakenAway, $namesRefused, $fileRecords, $fileRecord, $scanFindings, $FilesToExtract, $UserFilename, $UserExtension, $Method);
  return array($ExtractComplete, $ExtractErrors, $ExtractedFiles); }
// / -----------------------------------------------------------------------------------
?>
