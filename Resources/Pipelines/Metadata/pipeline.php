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
// / v4.3.1.
// / The Metadata pipeline. pipelineCore.php loads it only to dispatch a Strip Metadata operation.
// / Error block 41000 through 41019 belongs to this pipeline.
// / It declares the FFMPEG demuxer shared module, which Pipeline Core loads before it.
// / It is an operation pipeline, which takes a selection of files & makes a copy of each one, without the metadata it carries, beside it in the session.
// / It returns three values, the third being the name of every copy it made, which the core prints for a button & a workflow passes on.
// / A pipeline never prints. See contract 6.
// / It is dispatched by runOperationPipeline('Metadata'), never through convert().
// / mat2 strips the metadata, in its lightweight mode, inside the sandbox. A photo is not encoded again & a PDF keeps its text.
// / FFMPEG strips a video's, a WAV's & an AIFF's, from v4.3.1, with the command mat2 ran for each, inside the sandbox FFMPEG is never run without.
// / mat2 is started by verifiedToolCommand(), as the Dependency Core verified it, which is by the distribution's own Python.
// / Files are scanned through runVirusScan(), verifyFile(), metadataOperationFormats() & placeSessionFile() are the core's, & cleanFiles() is the Engine's.
// / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt for the contracts this file obeys.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline component cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to say how mat2 is kept from starting a sandbox of its own.
// / Accepts mat2's path. Returns the argument that keeps it from starting one, which is an empty string for every release but one.
// / mat2 cannot start a sandbox inside the one it runs in here, because that one allows no namespace inside it, & it fails when it tries.
// / Its --no-sandbox argument has meant three things. Before 0.13.5 it turned mat2's sandbox on, & mat2 started none without it.
// / In 0.13.5 it turned the sandbox off, & mat2 started one without it. From 0.14 mat2 has no sandbox, & the argument does nothing.
// / So it is given only where mat2's own program, which is text, declares that it turns the sandbox off, & it is read from the file that runs.
function mat2SandboxFlag($mat2Binary) {
  // / Set variables.
  global $EnableMemoryProtection;
  $SandboxFlag = '';
  $programText = '';
  $programText = (string)@file_get_contents((string)$mat2Binary, FALSE, NULL, 0, 262144);
  if (preg_match("/'--no-sandbox',\s*dest='sandbox',\s*action='store_false'/", $programText) === 1) $SandboxFlag = ' --no-sandbox';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $programText, $mat2Binary);
  return $SandboxFlag; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the formats whose metadata FFMPEG strips, & the one demuxer that reads each, from v4.3.1.
// / Accepts nothing. Returns each extension with the demuxer that reads it.
// / mat2 strips a video, a WAV & an AIFF by running FFMPEG on it, with no demuxer named, & outside the sandbox FFMPEG is never run
// / without where the settings allowed. FFMPEG chooses a demuxer by what a file holds, so a DASH manifest named mp4 or wav was read by
// / the demuxer that reads manifests, which opened each sound or video it named by its full path & wrote it into the clean copy.
// / So FFMPEG strips those three kinds itself here. A clean copy keeps the format of its file, so a file is read only by the demuxer
// / its extension names.
function metadataDemuxers() {
  // / Set variables.
  $StrippedDemuxers = array();
  $StrippedDemuxers = array('mp4' => 'mov', 'avi' => 'avi', 'wmv' => 'asf', 'wav' => 'wav', 'aiff' => 'aiff');
  return $StrippedDemuxers; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to build the command FFMPEG strips the metadata of a video, a WAV or an AIFF with, from v4.3.1.
// / Accepts the path of FFMPEG, the staged copy, the path of the clean copy & the extension of both, in that order.
// / Returns the command, or an empty string where no demuxer is allowed to read a file of that extension.
// / It is the command mat2 0.13 runs, which copies every stream as it is & writes no metadata, no chapter & no disposition.
// / It differs in four ways. FFMPEG is the program the Dependency Core verified rather than the first one on the path, it reads the
// / file only with the demuxer metadataDemuxers() names, & only where ffmpegDemuxerWhitelist() names that demuxer too, it opens
// / nothing but a file, & it reports an error where mat2 asked it to report nothing.
function ffmpegStripCommand($ffmpegBinary, $stagedInput, $cleanedOutput, $fileExtension) {
  // / Set variables.
  global $EnableMemoryProtection;
  $StripCommand = '';
  $strippedDemuxers = $allowedDemuxers = $namedDemuxer = array();
  $strippedDemuxers = metadataDemuxers();
  if (isset($strippedDemuxers[(string)$fileExtension])) $namedDemuxer = array($strippedDemuxers[(string)$fileExtension]);
  $allowedDemuxers = array_values(array_intersect($namedDemuxer, explode(',', ffmpegDemuxerWhitelist(FALSE))));
  if (is_string($ffmpegBinary) && $ffmpegBinary !== '' && count($allowedDemuxers) === 1) $StripCommand = escapeshellarg($ffmpegBinary).' -nostdin -y -hide_banner -loglevel error'
    .' -protocol_whitelist file -format_whitelist '.escapeshellarg((string)$allowedDemuxers[0]).' -i '.escapeshellarg((string)$stagedInput)
    .' -map 0 -codec copy -map_metadata -1 -map_chapters -1 -disposition 0 -fflags +bitexact -flags:v +bitexact -flags:a +bitexact '.escapeshellarg((string)$cleanedOutput);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $StripCommand is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $strippedDemuxers, $allowedDemuxers, $namedDemuxer, $ffmpegBinary, $stagedInput, $cleanedOutput, $fileExtension);
  return $StripCommand; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to strip the metadata from one file, in a folder of its own, & place the clean copy in the session.
// / Accepts mat2 with the arguments that keep it from starting a sandbox of its own, the verified copy of the file, the file's extension & the name the clean copy is given, in that order.
// / Returns whether a clean copy was placed, the name it was placed under & whether anything went wrong, in that order.
// / The file is copied into the folder as input & its extension, so no tool reads a name a user chose or writes beside a file it did not make.
// / mat2 writes the clean copy beside what it read, as input.cleaned & the extension, & that copy is what is placed. FFMPEG is told to write it there.
// / Lightweight mode removes what ExifTool, Mutagen & mat2's own readers can see, & leaves what a file shows as it was.
// / Its other mode turns every page of a PDF into a picture & encodes a photo again, so it is never used here.
// / A video, a WAV & an AIFF are stripped by FFMPEG, from v4.3.1, under the ffmpeg profile, as metadataDemuxers() says, & mat2 strips everything else.
// / Neither profile is run without a sandbox, so neither tool reads a file outside the sandbox. FFMPEG is asked for as it is verified.
// / The folder goes whatever happened, & the session is the root it may be removed from.
function stripOneFile($mat2Command, $verifiedCopy, $fileExtension, $copyName) {
  // / Set variables.
  global $Verbose, $ConvertDir, $Lol, $Lolol, $EnableMemoryProtection;
  $CopyWasPlaced = $StripErrors = FALSE;
  $PlacedName = '';
  $stagingFolder = $stagedInput = $cleanedOutput = $stripCommand = $returnData = $toolSaid = $stripTool = $stripProfile = '';
  $sandboxIsAvailable = $nameWasRefused = $stagingIsRemoved = $ffmpegStrips = $commandWasBuilt = FALSE;
  $outputLines = array();
  $ffmpegStrips = array_key_exists((string)$fileExtension, metadataDemuxers());
  $stripTool = ($ffmpegStrips ? 'FFMPEG' : 'mat2');
  $stripProfile = ($ffmpegStrips ? 'ffmpeg' : 'mat2');
  $stagingFolder = $ConvertDir.'.metadata-'.bin2hex(random_bytes(8));
  $stagedInput = $stagingFolder.DIRECTORY_SEPARATOR.'input.'.$fileExtension;
  $cleanedOutput = $stagingFolder.DIRECTORY_SEPARATOR.'input.cleaned.'.$fileExtension;
  if (!@mkdir($stagingFolder, 0700) or !@copy((string)$verifiedCopy, $stagedInput)) {
    $StripErrors = TRUE;
    errorEntry('A folder to strip the metadata of '.$copyName.' in could not be made, so no clean copy was made!', 41005, FALSE); }
  else {
    if ($ffmpegStrips) $stripCommand = ffmpegStripCommand(verifiedToolPath('FFMPEG'), $stagedInput, $cleanedOutput, $fileExtension);
    else $stripCommand = (string)$mat2Command.' --lightweight '.escapeshellarg($stagedInput);
    // / Whether a command was built is kept apart from what sandboxCommand() returns, so a refusal is never reported as no command.
    $commandWasBuilt = ($stripCommand !== '');
    if ($commandWasBuilt) list ($sandboxIsAvailable, $stripCommand) = sandboxCommand($stripCommand, $stagedInput, $cleanedOutput, FALSE, $stripProfile);
    if (!$commandWasBuilt) {
      $StripErrors = TRUE;
      errorEntry('No command could strip the metadata of '.$copyName.', because FFMPEG is missing or no demuxer it may use reads a '.$fileExtension.' file!', 41009, FALSE); }
    else if (!$sandboxIsAvailable) {
      $StripErrors = TRUE;
      errorEntry('Bubblewrap is missing or non functional, so metadata cannot be stripped in isolation!', 41006, FALSE); }
    else {
      $returnData = (string)shell_exec($stripCommand.' 2>&1');
      if ($Verbose && trim($returnData) !== '') logEntry($stripTool.' returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, trim($returnData))));
      clearstatcache(TRUE, $cleanedOutput);
      // / The copy the tool wrote is the only verdict on whether it stripped anything, because mat2 warns without failing.
      // / The error names the last line the tool printed, which is its reason, or a Python traceback's last line, which names what mat2 could not load.
      if (!is_file($cleanedOutput) or is_link($cleanedOutput) or (int)filesize($cleanedOutput) === 0) {
        $StripErrors = TRUE;
        $outputLines = array_values(array_filter(array_map('trim', explode("\n", $returnData)), 'strlen'));
        $toolSaid = (count($outputLines) > 0) ? (string)$outputLines[count($outputLines) - 1] : '';
        errorEntry('Could not strip the metadata from '.$copyName.'!'.($toolSaid !== '' ? ' '.$stripTool.' said: '.$toolSaid : ' '.$stripTool.' said nothing.'), 41007, FALSE); }
      else {
        list ($CopyWasPlaced, $PlacedName, $nameWasRefused) = placeSessionFile($cleanedOutput, $copyName);
        if ($nameWasRefused) {
          $StripErrors = TRUE;
          warningEntry('The name '.$copyName.' is not one this server accepts, so the clean copy made of it was not kept.'); }
        else if (!$CopyWasPlaced) {
          $StripErrors = TRUE;
          errorEntry('Could not place the clean copy of '.$copyName.' in the session!', 41008, FALSE); } } } }
  clearstatcache(TRUE, $stagingFolder);
  $stagingIsRemoved = (!file_exists($stagingFolder) or cleanFiles($stagingFolder, array($ConvertDir)));
  if (!$stagingIsRemoved) warningEntry('The folder the metadata of '.$copyName.' was stripped in could not be removed. It is removed with the session.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $stagingFolder, $stagedInput, $cleanedOutput, $stripCommand, $returnData, $toolSaid, $stripTool, $stripProfile, $sandboxIsAvailable, $nameWasRefused, $stagingIsRemoved, $ffmpegStrips, $commandWasBuilt, $outputLines, $mat2Command, $verifiedCopy, $fileExtension, $copyName);
  return array($CopyWasPlaced, $PlacedName, $StripErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to strip the metadata from a selection of files, making a clean copy of each beside it.
// / Accepts the files, the name to give each copy, the extension, which is not read, & a method, which is not read, in that order.
// / Returns whether any clean copy was made, whether anything went wrong & the name of every copy it made, in that order.
// / A copy is given the name asked for, or the file's own, with the file's extension, & the next free name when that is taken, so the file itself is never written over.
// / Only a format metadataOperationFormats() lists is read, so a request a page did not send is refused before anything is staged.
// / A file is scanned through runVirusScan() before it is read, when $VirusScan is set.
function stripMetadata($FilesToStrip, $UserFilename, $UserExtension, $Method) {
  // / Set variables.
  global $Verbose, $VirusScan, $EnableMemoryProtection;
  $StripComplete = $StripErrors = FALSE;
  $StrippedFiles = array();
  $file = $fileExtension = $pathname = $oldPathname = $oldExtension = $newPathname = $resolvedFilename = $copyName = $placedName = $scannerUsed = $mat2Command = $mat2Status = $mat2Output = '';
  $variableIsSanitized = $fileIsVerified = $scanComplete = $virusFound = $copyWasPlaced = $copyErrors = FALSE;
  $mat2Binary = $ffmpegBinary = FALSE;
  $scanFindings = $acceptedFormats = array();
  $ffmpegFormats = array_keys(metadataDemuxers());
  $UserFilename = (string)$UserFilename;
  $acceptedFormats = metadataOperationFormats();
  // / Every binary is supplied by verifiedToolPath(), which asks the manifest, so the version that was verified is the version that runs.
  $mat2Binary = verifiedToolPath('MAT2');
  $ffmpegBinary = verifiedToolPath('FFMPEG');
  // / Make sure the input files are formatted into an array.
  if (!is_array($FilesToStrip)) $FilesToStrip = array($FilesToStrip);
  // / A refusal names the status the Dependency Core gave mat2 & the line of its output that says the most, so the error says what to fix.
  if ($mat2Binary === FALSE) {
    $StripErrors = TRUE;
    $FilesToStrip = array();
    list ($mat2Status, $mat2Output) = verifiedToolVerdict('MAT2');
    errorEntry('mat2 is missing, unidentifiable, or too old, so no metadata was stripped!'.($mat2Status !== '' ? ' It is '.$mat2Status.($mat2Output !== '' ? ': '.$mat2Output : '.') : ''), 41000, FALSE); }
  else $mat2Command = verifiedToolCommand('MAT2', (string)$mat2Binary).mat2SandboxFlag($mat2Binary);
  foreach ($FilesToStrip as $file) {
    // / Make sure the file is sanitized before processing it.
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $StripErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 41001, FALSE);
      continue; }
    if ($Verbose) logEntry('User selected to Strip Metadata from file '.$file.'.');
    redeclare($fileExtension, getExtension($file));
    if (!in_array($fileExtension, $acceptedFormats, TRUE)) {
      $StripErrors = TRUE;
      warningEntry('The file '.$file.' is not a format metadata is stripped from here, so no clean copy was made.');
      continue; }
    if (in_array($fileExtension, $ffmpegFormats, TRUE) && $ffmpegBinary === FALSE) {
      $StripErrors = TRUE;
      errorEntry('The metadata of a video, a WAV or an AIFF is stripped with FFMPEG, which is missing, unidentifiable, or too old!', 41002, FALSE);
      continue; }
    // / verifyFile() stages a copy in the temporary folder, & that copy is what is stripped.
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname, $resolvedFilename) = verifyFile($file, '', '', TRUE, TRUE, FALSE);
    if (!$fileIsVerified) {
      $StripErrors = TRUE;
      errorEntry('Could not verify the input file.', 41003, FALSE);
      continue; }
    // / Scan the file with the default scanner if $VirusScan is set to TRUE in config.php.
    if ($VirusScan) {
      if ($Verbose) logEntry('Starting virus scan.');
      list ($scanComplete, $virusFound, $scanFindings, $scannerUsed) = runVirusScan(array($pathname), '');
      if (!$scanComplete) {
        $StripErrors = TRUE;
        errorEntry('Could not perform a virus scan of '.$file.', so no clean copy was made!', 41004, FALSE);
        continue; }
      if ($virusFound) {
        $StripErrors = TRUE;
        warningEntry('A virus was found in '.$file.', so no clean copy was made.');
        continue; } }
    // / A copy keeps the extension of its file, spelt as it was.
    redeclare($copyName, (($UserFilename !== '') ? $UserFilename : (string)pathinfo($file, PATHINFO_FILENAME)).'.'.(string)pathinfo($file, PATHINFO_EXTENSION));
    // / The values before them are shredded first, as redeclare() would, because a list() cannot pass through redeclare().
    purgeSensitiveMemory($EnableMemoryProtection, $copyWasPlaced, $placedName, $copyErrors);
    list ($copyWasPlaced, $placedName, $copyErrors) = stripOneFile($mat2Command, $pathname, $fileExtension, $copyName);
    if ($copyErrors) $StripErrors = TRUE;
    if ($copyWasPlaced) {
      $StrippedFiles[] = $placedName;
      if ($Verbose) logEntry('Stripped the metadata from '.$file.' into '.$placedName.'.'); } }
  if (count($StrippedFiles) > 0) $StripComplete = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $file, $fileExtension, $pathname, $oldPathname, $oldExtension, $newPathname, $resolvedFilename, $copyName, $placedName, $scannerUsed, $mat2Status, $mat2Output, $variableIsSanitized, $fileIsVerified, $scanComplete, $virusFound, $copyWasPlaced, $copyErrors, $mat2Binary, $mat2Command, $ffmpegBinary, $scanFindings, $acceptedFormats, $ffmpegFormats, $FilesToStrip, $UserFilename, $UserExtension, $Method);
  return array($StripComplete, $StripErrors, $StrippedFiles); }
// / -----------------------------------------------------------------------------------
?>
