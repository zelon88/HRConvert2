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
// / v4.1.6.
// / The Archiver pipeline. pipelineCore.php loads it only to dispatch an Archive operation.
// / Error block 4000 through 4006 belongs to this pipeline, & so does 13012.
// / It also raises 13006, 13008, 13009 & 13010, which it shares with the Archive pipeline.
// / The Archiver is an operation pipeline, which takes a selection of files & puts them into archives.
// / It returns three values, the third being the name of every archive it made, which the core prints for a button & a workflow passes on.
// / A pipeline never prints. See contract 6.
// / It is dispatched by runOperationPipeline('Archive'), never through convert().
// / Until v4.1.6 this was archiveFiles() in convertCore.php, & every error number it raised came with it.
// / Its tools come from verifiedToolPath(), files are scanned through runVirusScan(), & verifyFile() is the core's.
// / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt for the contracts this file obeys.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline component cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to archive a selection of files.
// / Accepts the files, the name of one archive for all of them, the archive format & a method, which is not read, in that order.
// / Returns whether any file was archived, whether anything went wrong & the name of every archive it made, in that order.
// / With a name, the first file starts an archive of that name & every file after it adds to that archive.
// / Without a name, each file is archived on its own, under its own name, so one input produces one output every time.
// / Each output format gates on its own archiver, so a missing mkisofs does not prevent a zip archive from being created.
// / Every binary is supplied by verifiedToolPath(), which asks the manifest, rather than assumed, so
// / the version that was verified is provably the version that runs.
// / 7-Zip cannot create rar archives. RAR compression is proprietary & 7-Zip reads the
// / format without being able to write it, so rar output has NO fallback.
// / A file is scanned through runVirusScan() before it is archived, when $VirusScan is set.
// / An infected file is refused here, not deleted. Deleting is the application's policy, set by
// / $AllowUserVirusScan, which a pipeline cannot read. Every file is also scanned when it lands,
// / which is where the application deletes one.
function archiveSelection($FilesToArchive, $UserFilename, $UserExtension, $Method) {
  // / Set variables.
  global $Verbose, $VirusScan, $Lol, $Lolol, $EnableMemoryProtection;
  $ArchiveComplete = $ArchiveErrors = $virusFound = $skip = $variableIsSanitized = FALSE;
  $fileIsVerified = $scanComplete = $sandboxIsAvailable = $anyFileSucceeded = $loopCheck = FALSE;
  $archiveWasStarted = FALSE;
  $sevenZipBinary = $rarBinary = $zipBinary = $tarBinary = $mkisofsBinary = FALSE;
  $clean = $copy = TRUE;
  $returnData = $file = $pathname = $oldPathname = $oldExtension = $newPathname = $archiveCommand = $resolvedFilename = '';
  $scanFindings = array();
  $scannerUsed = '';
  $ArchivedFiles = array();
  // / The name every file is archived under, once the first file has settled it.
  $UserFilename = (string)$UserFilename;
  $UserExtension = strtolower((string)$UserExtension);
  $archiveName = $UserFilename;
  $rararr = array('rar');
  $ziparr = array('zip');
  $tararr = array('7z', 'tar', 'tar.gz', 'tar.bz2');
  $isoarr = array('iso');
  // / Locate & verify every archive utility before anything is written.
  // / Each one is asked for on its own, & a missing one costs only the formats it writes.
  $sevenZipBinary = verifiedToolPath('7-Zip');
  $rarBinary = verifiedToolPath('RAR');
  $zipBinary = verifiedToolPath('Zip');
  $tarBinary = verifiedToolPath('Tar');
  $mkisofsBinary = verifiedToolPath('Genisoimage');
  // / Make sure the input files are formatted into an array.
  if (!is_array($FilesToArchive)) $FilesToArchive = array($FilesToArchive);
  // / A format no archiver here writes is refused before any file is touched, & named, rather than failing every file in turn.
  // / The core offers only what this pipeline declares it writes, so this is a request a page did not send, such as a bootable disk image.
  if (!in_array($UserExtension, array_merge($rararr, $ziparr, $tararr, $isoarr), TRUE)) {
    $ArchiveErrors = TRUE;
    $FilesToArchive = array();
    errorEntry('No archiver here writes '.$UserExtension.', so nothing was archived!', 4006, FALSE); }
  // / Every other archiver here adds a file to an archive that already exists, which is how a
  // / selection of files becomes one archive. Mkisofs replaces the image it is given instead,
  // / so a second file would leave an image holding only the last one. That is refused rather
  // / than written, because an image quietly missing most of its files is worse than none.
  if (count($FilesToArchive) > 1 && in_array($UserExtension, $isoarr)) {
    $ArchiveErrors = TRUE;
    $FilesToArchive = array();
    errorEntry('An iso image holds one file at a time, so a selection of files cannot be archived into one!', 4005, FALSE); }
  // / Iterate through the array of input files.
  foreach ($FilesToArchive as $file) {
    $loopCheck = FALSE;
    $archiveCommand = '';
    // / Make sure the file is sanitized before processing it.
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $ArchiveErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 4000, FALSE);
      continue; }
    // / Set the $clean & $copy arguments for the verifyFile() function as needed.
    // / The first file asks for a clean output, so an archive name that is taken is given the next free one.
    // / Every file after it adds to the archive the first file started, so it keeps that archive's name.
    // / Without a name, each file is archived under its own, so each asks for a clean output.
    $clean = ($UserFilename === '' or $archiveWasStarted === FALSE);
    $copy = TRUE;
    if ($Verbose) logEntry('User selected to Archive file '.$file.'.');
    // / Verify the file before performing any operations on it.
    // / verifyFile returns the name with the extension appended, so the name carried to the next file has it taken off again.
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname, $resolvedFilename) = verifyFile($file, $archiveName, $UserExtension, $clean, $copy, $skip);
    if (!$fileIsVerified) {
      $ArchiveErrors = TRUE;
      errorEntry('Could not verify the input file.', 4001, FALSE);
      continue; }
    else if ($Verbose) logEntry('Verified file '.$newPathname.'.');
    // / redeclare() replaces the name, because a file that failed before the archive was started leaves the next one to settle it again.
    if ($clean && $UserFilename !== '') redeclare($archiveName, preg_replace('/\.'.preg_quote($UserExtension, '/').'$/', '', (string)$resolvedFilename));
    // / Scan with the default scanner if $VirusScan is set to TRUE in config.php.
    if ($VirusScan) {
      if ($Verbose) logEntry('Starting virus scan.');
      list ($scanComplete, $virusFound, $scanFindings, $scannerUsed) = runVirusScan(array($pathname), '');
      if (!$scanComplete) errorEntry('Could not perform a virus scan!', 4002, TRUE);
      if ($virusFound) errorEntry('Virus detected!', 4003, TRUE);
      if ($Verbose) logEntry('Virus scan complete.'); }
    // / Select the archiver for the requested output format & confirm it is usable.
    // / Handle archiving of rar compatible files.
    if (in_array($UserExtension, $rararr)) {
      if ($rarBinary === FALSE) {
        $ArchiveErrors = TRUE;
        errorEntry('Rar output requires the rar utility, which is missing or too old!', 13012, FALSE); }
      else $archiveCommand = escapeshellarg($rarBinary).' a -ep '.escapeshellarg($newPathname).' '.escapeshellarg($pathname); }
    // / Handle archiving of zip compatible files.
    else if (in_array($UserExtension, $ziparr)) {
      if ($zipBinary === FALSE) {
        $ArchiveErrors = TRUE;
        errorEntry('Zip is missing, unidentifiable, or too old!', 13010, FALSE); }
      else $archiveCommand = escapeshellarg($zipBinary).' -j '.escapeshellarg($newPathname).' '.escapeshellarg($pathname); }
    // / Handle archiving of 7-Zip compatible files.
    else if (in_array($UserExtension, $tararr)) {
      if ($sevenZipBinary === FALSE) {
        $ArchiveErrors = TRUE;
        errorEntry('The installed 7-Zip version is missing, unidentifiable, or too old!', 13008, FALSE); }
      else $archiveCommand = escapeshellarg($sevenZipBinary).' a '.escapeshellarg($newPathname).' '.escapeshellarg($pathname); }
    // / Handle archiving of mkisofs compatible files.
    else if (in_array($UserExtension, $isoarr)) {
      if ($mkisofsBinary === FALSE) {
        $ArchiveErrors = TRUE;
        errorEntry('Mkisofs is missing, unidentifiable, or too old!', 13009, FALSE); }
      else $archiveCommand = escapeshellarg($mkisofsBinary).' -o '.escapeshellarg($newPathname).' '.escapeshellarg($pathname); }
    // / Perform the archive operation inside a sandbox.
    // / An archiver reads a file the user supplied & writes a structure it controls, so it
    // / is isolated for the same reason every other dependency is.
    if ($archiveCommand !== '') {
      list ($sandboxIsAvailable, $archiveCommand) = sandboxCommand($archiveCommand, $pathname, $newPathname, FALSE, 'archive');
      if (!$sandboxIsAvailable) {
        $ArchiveErrors = TRUE;
        errorEntry('Bubblewrap is missing or non functional, so this archive operation cannot be isolated!', 13006, FALSE); }
      else $returnData = (string)shell_exec($archiveCommand); }
    // / Log the output of the archive operation to the logfile, if it is not blank.
    if ($Verbose && trim($returnData) !== '') logEntry('The archiver returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
    // / The output file is the only verdict on whether the operation produced anything.
    if (!file_exists($newPathname)) {
      $ArchiveErrors = TRUE;
      errorEntry('Could not archive file '.$pathname.' to '.$newPathname.'!', 4004, FALSE); }
    else {
      $loopCheck = TRUE;
      $archiveWasStarted = TRUE;
      // / An archive several files went into is named once.
      if (!in_array($resolvedFilename, $ArchivedFiles, TRUE)) $ArchivedFiles[] = $resolvedFilename;
      if ($Verbose) logEntry('Archived file '.$pathname.' to '.$newPathname.'.'); }
    // / Record that at least one file in this request succeeded.
    // / $loopCheck is reset on every iteration, so without this the result would reflect
    // / only the LAST file rather than the whole request.
    if ($loopCheck) $anyFileSucceeded = TRUE; }
  if ($anyFileSucceeded) $ArchiveComplete = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $archiveWasStarted, $archiveName, $resolvedFilename, $file, $rararr, $ziparr, $tararr, $isoarr, $pathname, $oldPathname, $newPathname, $scanComplete, $virusFound, $scanFindings, $scannerUsed, $returnData, $variableIsSanitized, $fileIsVerified, $oldExtension, $clean, $copy, $skip, $loopCheck, $anyFileSucceeded, $archiveCommand, $sandboxIsAvailable, $sevenZipBinary, $rarBinary, $zipBinary, $tarBinary, $mkisofsBinary, $FilesToArchive, $UserFilename, $UserExtension, $Method);
  return array($ArchiveComplete, $ArchiveErrors, $ArchivedFiles); }
// / -----------------------------------------------------------------------------------
?>
