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
// / v4.0.6.
// / This file is the LibreOffice shared module. It is NOT a pipeline & it converts nothing
// / on its own. It holds the code that the Document pipeline & the OCR pipeline both need.
// /
// / A shared module exists because two pipelines needed the same code & neither could own it.
// / convertWithLibreOffice() has callers in both. Leaving it in convertCore.php would have
// / kept document conversion logic in the file this work exists to get it out of. Putting
// / it in the Document pipeline would have made OCR depend on another pipeline being
// / installed, verified & loaded first, which is a coupling no pipeline author should have
// / to reason about. So it lives here, is version pinned exactly as a pipeline is, by the
// / table in Resources/Contract/app-engine-config.php, & is loaded once for whichever
// / pipelines declare they need it.
// /
// / A module is loaded once per request, before the pipeline that declared it.
// / Document & OCR in the same request load this file once between them.
// /
// / Error block 2000 through 2004 belongs to this module, & so does 2006. Those numbers came
// / with the code when it moved out of convertCore.php & they did not change. 2005 stayed
// / with the Document pipeline, because the XPS branch that raises it never moved here.
// / The LibreOffice version check asks the manifest through verifiedToolPath, as every tool
// / does. The manifest is the only owner of a tool's minimum, so there is no second check here
// / to disagree with it.
// / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt for the contracts this file obeys.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline component cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The version of this shared module. Read WITHOUT executing this file, then matched
// / EXACTLY against the pin in getAcceptedSharedModules().
$SharedModuleVersion = 'v4.0.6';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to neutralize external references inside an uploaded document.
// / Accepts the absolute path of the document to sanitize.
// / Returns a sanitization boolean & the number of references neutralized, in that order.
// / LibreOffice resolves a linked image, an INCLUDEPICTURE field & an external relationship
// / target by fetching the URL, which turns a document upload into a server side request.
// / The sandbox is what actually stops that fetch. This runs in front of it so an operator
// / who has disabled sandboxing is not left completely unprotected.
// / A reference is blanked rather than removed, because deleting the element that carries it
// / leaves a dangling identifier & some readers refuse the whole file.
// / A format this function does not recognize is reported as unsanitized rather than clean.
function sanitizeDocumentLinks($documentPath) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $DocumentIsSanitized = FALSE;
  $ReferencesRemoved = 0;
  $documentExtension = $entryName = $entryContents = $cleanContents = $flatContents = '';
  $zipArchive = FALSE;
  $entryIndex = $entryCount = $replacementCount = 0;
  $packagedFormats = array('docx', 'docm', 'dotx', 'dotm', 'xlsx', 'xlsm', 'xltx', 'xltm', 'pptx', 'pptm', 'potx', 'potm', 'odt', 'ods', 'odp', 'odg', 'odf', 'ott', 'ots', 'otp');
  $flatFormats = array('fodt', 'fods', 'fodp', 'xml', 'rtf');
  $documentExtension = strtolower((string)pathinfo($documentPath, PATHINFO_EXTENSION));
  // / A packaged format is a zip. Every part that can carry a reference is rewritten in place.
  if (in_array($documentExtension, $packagedFormats, TRUE)) {
    if (!class_exists('ZipArchive')) warningEntry('The PHP zip extension is unavailable, so '.basename($documentPath).' could not be checked for external references. The sandbox remains the only protection.');
    else {
      $zipArchive = new ZipArchive();
      if ($zipArchive->open($documentPath) !== TRUE) warningEntry('Could not open '.basename($documentPath).' to check it for external references.');
      else {
        $entryCount = $zipArchive->numFiles;
        while ($entryIndex < $entryCount) {
          $entryName = (string)$zipArchive->getNameIndex($entryIndex);
          // / Only the XML parts carry references. Media & binary parts are left untouched.
          if (substr($entryName, -4) === '.xml' or substr($entryName, -5) === '.rels') {
            $entryContents = (string)$zipArchive->getFromIndex($entryIndex);
            $cleanContents = neutralizeDocumentReferences($entryContents, $replacementCount);
            if ($cleanContents !== $entryContents) {
              $zipArchive->addFromString($entryName, $cleanContents);
              $ReferencesRemoved = $ReferencesRemoved + $replacementCount; } }
          $entryIndex++; }
        $zipArchive->close();
        $DocumentIsSanitized = TRUE; } } }
  // / A flat format is a single file. The same rewrite applies to the whole of it.
  else if (in_array($documentExtension, $flatFormats, TRUE)) {
    $flatContents = (string)@file_get_contents($documentPath);
    if ($flatContents === '') warningEntry('Could not read '.basename($documentPath).' to check it for external references.');
    else {
      $cleanContents = neutralizeDocumentReferences($flatContents, $replacementCount);
      if ($cleanContents !== $flatContents) {
        @file_put_contents($documentPath, $cleanContents, LOCK_EX);
        $ReferencesRemoved = $replacementCount; }
      $DocumentIsSanitized = TRUE; } }
  // / Every other format reaches the converter unexamined & relies on the sandbox alone.
  else $DocumentIsSanitized = FALSE;
  if ($Verbose) logEntry('Document Sanitization: '.basename($documentPath).', Format: '.($documentExtension === '' ? 'NONE' : $documentExtension).', Examined: '.($DocumentIsSanitized ? 'YES' : 'NO').', References Neutralized: '.$ReferencesRemoved.'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $zipArchive, $documentExtension, $entryName, $entryContents, $cleanContents, $flatContents, $entryIndex, $entryCount, $replacementCount, $packagedFormats, $flatFormats, $documentPath);
  return array($DocumentIsSanitized, $ReferencesRemoved); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to blank every external reference in one XML or flat document part.
// / Accepts the part contents & a counter that receives the number of replacements made.
// / Returns the rewritten contents.
// / Three carriers are handled. An OOXML relationship marked external, a field instruction
// / that names a fetching command & an ODF or SVG style link attribute.
// / A relative target is left alone, because it cannot leave the document package.
function neutralizeDocumentReferences($partContents, &$replacementCount) {
  // / Set variables.
  global $EnableMemoryProtection;
  $CleanContents = (string)$partContents;
  $runningCount = $stepCount = 0;
  $remoteSchemes = 'https?|ftps?|file|smb|ldap|gopher|dict|jar|mailto';
  // / An OOXML relationship that declares itself external. The target is emptied in place.
  $CleanContents = preg_replace('/(<Relationship\b(?=[^>]*TargetMode\s*=\s*"External")[^>]*?\sTarget\s*=\s*")[^"]*(")/i', '$1$2', $CleanContents, -1, $stepCount);
  $runningCount = $runningCount + (int)$stepCount;
  // / A relationship target carrying a remote scheme, whether or not it declares itself external.
  $CleanContents = preg_replace('/(<Relationship\b[^>]*?\sTarget\s*=\s*")(?:'.$remoteSchemes.'):[^"]*(")/i', '$1$2', $CleanContents, -1, $stepCount);
  $runningCount = $runningCount + (int)$stepCount;
  // / A field instruction that tells the renderer to go and fetch something.
  // / The instruction text is emptied & the element is left in place so the run stays valid.
  $CleanContents = preg_replace('/(<w:instrText\b[^>]*>)[^<]*\b(?:INCLUDEPICTURE|INCLUDETEXT|IMPORT|LINK|DDEAUTO|DDE|HYPERLINK)\b[^<]*(<\/w:instrText>)/i', '$1$2', $CleanContents, -1, $stepCount);
  $runningCount = $runningCount + (int)$stepCount;
  // / The same instruction expressed as an attribute rather than as element text.
  $CleanContents = preg_replace('/(\sw:instr\s*=\s*")[^"]*\b(?:INCLUDEPICTURE|INCLUDETEXT|IMPORT|LINK|DDEAUTO|DDE)\b[^"]*(")/i', '$1$2', $CleanContents, -1, $stepCount);
  $runningCount = $runningCount + (int)$stepCount;
  // / An ODF, SVG or generic xlink reference pointing off the machine.
  $CleanContents = preg_replace('/(\sxlink:href\s*=\s*")(?:'.$remoteSchemes.'):[^"]*(")/i', '$1$2', $CleanContents, -1, $stepCount);
  $runningCount = $runningCount + (int)$stepCount;
  // / A spreadsheet external workbook reference.
  $CleanContents = preg_replace('/(<externalReference\b[^>]*\sr:id\s*=\s*")[^"]*(")/i', '$1$2', $CleanContents, -1, $stepCount);
  $runningCount = $runningCount + (int)$stepCount;
  $replacementCount = $runningCount;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $CleanContents is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $runningCount, $stepCount, $remoteSchemes, $partContents);
  return $CleanContents; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify that the Document Conversion Engine is installed & running.
// / LibreOffice is version checked here, because every document conversion depends on it.
// / LibreOffice itself is version checked here, because every document conversion depends on it.
// / The listener is only started once the installation & the version have both been cleared.
function verifyDocumentConversionEngine() {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $DocEnginePID = 0;
  $DocumentEngineStarted = $libreOfficeVersionIsValid = FALSE;
  $sofficeBinary = '';
  // / LibreOffice is the engine behind every document, spreadsheet & presentation conversion.
  $libreOfficeVersionIsValid = (verifiedToolPath('LibreOffice') !== FALSE);
  if (!$libreOfficeVersionIsValid) errorEntry('The installed LibreOffice version is missing, unidentifiable, or too old!', 2001, TRUE);
  else {
    $sofficeBinary = locateDependency('soffice');
    if ($sofficeBinary === '') $sofficeBinary = locateDependency('libreoffice');
    if ($sofficeBinary === '') errorEntry('Could not locate the LibreOffice binary!', 2000, TRUE);
    else $DocumentEngineStarted = TRUE; }
  // / Report a leftover listener. It is an open parsing surface that nothing needs any more.
  $DocEnginePID = (int)trim((string)shell_exec('pgrep soffice.bin | head -n 1'));
  if ($DocEnginePID > 0) warningEntry('A persistent LibreOffice listener is running as process '.$DocEnginePID.'. It is no longer used & is an unsandboxed parsing surface. Remove it from rc.local or the container entrypoint.');
  if ($Verbose && $DocumentEngineStarted) logEntry('Verified the Document Conversion Engine. Conversions run sandboxed, one process each.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $libreOfficeVersionIsValid, $sofficeBinary);
  return array($DocumentEngineStarted, $DocEnginePID); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to make a private LibreOffice profile folder for one unsandboxed conversion.
// / Accepts nothing. Returns a readiness boolean & the folder's path, in that order.
// / Inside the sandbox the profile is written to a tmpfs of the conversion's own. Without
// / one, a fixed path in the shared temporary directory could be made first by any account
// / on the host, & a LibreOffice profile can carry macros. So each unsandboxed conversion
// / gets a folder whose name nobody can predict, made by this process with mode 0700, &
// / used only when it is a real folder rather than a link.
// / mkdir() fails on a name that already exists, so a planted folder is never adopted.
function makeLibreOfficeProfile() {
  // / Set variables.
  global $EnableMemoryProtection;
  $ProfileIsReady = FALSE;
  $ProfileFolder = '';
  $attempt = 0;
  while (!$ProfileIsReady && $attempt < 3) {
    $attempt++;
    $ProfileFolder = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'hrc2-libreoffice-'.bin2hex(random_bytes(16));
    if (@mkdir($ProfileFolder, 0700) && is_dir($ProfileFolder) && !is_link($ProfileFolder)) $ProfileIsReady = @chmod($ProfileFolder, 0700); }
  if (!$ProfileIsReady) $ProfileFolder = '';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $attempt);
  return array($ProfileIsReady, $ProfileFolder); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove a profile folder that makeLibreOfficeProfile() made.
// / Accepts the folder's path. Returns TRUE when nothing of it is left.
// / A link inside the profile is removed as a link & never followed, so removing a profile
// / can only ever delete what is inside it.
function removeLibreOfficeProfile($profileFolder) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ProfileIsRemoved = FALSE;
  $profileEntries = $profileEntry = NULL;
  if (is_string($profileFolder) && $profileFolder !== '' && is_dir($profileFolder) && !is_link($profileFolder)) {
    $profileEntries = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($profileFolder, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($profileEntries as $profileEntry) {
      if ($profileEntry->isDir() && !$profileEntry->isLink()) @rmdir($profileEntry->getPathname());
      else @unlink($profileEntry->getPathname()); }
    @rmdir($profileFolder); }
  $ProfileIsRemoved = !file_exists($profileFolder);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $profileFolder, $profileEntries, $profileEntry);
  return $ProfileIsRemoved; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert one document with LibreOffice inside a sandbox.
// / Accepts the input path, the intended output path & the target extension, in that order.
// / Returns a completion boolean & whatever the converter printed, in that order.
// / This replaces the persistent unoconv listener for every document conversion.
// / The listener parsed hostile documents in a long lived process with full network access,
// / so sandboxing the client that talked to it protected nothing. A per conversion process
// / inside sandboxCommand has no network at all, which closes every URL handler at once.
// / LibreOffice names its own output from the input basename, so the result is renamed when
// / the caller asked for something else.
// / The output directory is a bare dot, because sandboxCommand changes into the writable
// / mount before running. When no sandbox could be built the directory is named outright.
function convertWithLibreOffice($inputPath, $outputPath, $targetExtension) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection, $DirSep;
  $ConversionCompleted = FALSE;
  $ReturnData = '';
  $sofficeBinary = $sofficeFlags = $sofficeCommand = $sandboxedCommand = $producedPath = $cleanExtension = $profileFolder = '';
  $commandMayRun = $commandIsSandboxed = $documentIsSanitized = $profileIsReady = $profileIsRemoved = FALSE;
  $commandOutput = array();
  $referencesRemoved = $commandExitCode = 0;
  // / A filter may be appended to a format with a colon, so a colon is permitted here.
  $cleanExtension = preg_replace('/[^A-Za-z0-9_:.\-]/', '', (string)$targetExtension);
  $sofficeBinary = locateDependency('soffice');
  if ($sofficeBinary === '') $sofficeBinary = locateDependency('libreoffice');
  if ($sofficeBinary === '') errorEntry('LibreOffice could not be located for a document conversion!', 2002, FALSE);
  else if ($cleanExtension === '') errorEntry('A document conversion was requested with an unusable target format!', 2003, FALSE);
  else {
    // / Neutralize external references before the converter ever opens the file.
    list ($documentIsSanitized, $referencesRemoved) = sanitizeDocumentLinks($inputPath);
    if ($referencesRemoved > 0) warningEntry('Document Sanitization neutralized '.$referencesRemoved.' external reference(s) in '.basename($inputPath).' before conversion. A document requesting a remote resource is worth investigating.');
    // / Every flag here suppresses a prompt, a lock file or a recovery dialog that would
    // / otherwise hang a headless process forever.
    // / The user profile is written inside the sandbox tmpfs, so no state survives the run &
    // / no two conversions can ever share a profile. Without a sandbox that path would be the
    // / host's, shared by every conversion & by any account that made it first, so a private
    // / folder is made for the one conversion & removed after it. Until v4.0.0 it was not.
    $sofficeFlags = ' --headless --norestore --invisible --nolockcheck --nodefault --nofirststartwizard --nologo';
    $sofficeCommand = escapeshellarg($sofficeBinary).$sofficeFlags
      .' -env:UserInstallation=file:///tmp/hrc2-libreoffice'
      .' --convert-to '.escapeshellarg($cleanExtension)
      .' --outdir .'
      .' '.escapeshellarg($inputPath);
    list ($commandMayRun, $sandboxedCommand) = sandboxCommand($sofficeCommand, $inputPath, $outputPath, FALSE, 'libreoffice');
    // / sandboxCommand returns the command untouched when no sandbox could be built.
    $commandIsSandboxed = ($sandboxedCommand !== $sofficeCommand);
    if (!$commandMayRun) errorEntry('A document conversion was refused because no sandbox could be built!', 2004, FALSE);
    else if (!$commandIsSandboxed) {
      // / Without a sandbox the command is built again rather than edited, so neither the shared
      // / profile path nor a bare output directory can survive into it by a missed match.
      // / A bare output directory only works because the sandbox changes into it, so it is
      // / named outright here rather than writing wherever PHP happens to be.
      list ($profileIsReady, $profileFolder) = makeLibreOfficeProfile();
      if ($profileIsReady) $sandboxedCommand = escapeshellarg($sofficeBinary).$sofficeFlags
        .' '.escapeshellarg('-env:UserInstallation=file://'.$profileFolder)
        .' --convert-to '.escapeshellarg($cleanExtension)
        .' --outdir '.escapeshellarg(dirname($outputPath))
        .' '.escapeshellarg($inputPath);
      // / A conversion is never run on the shared path. A refusal costs this one document.
      else {
        $commandMayRun = FALSE;
        errorEntry('A document conversion was refused because no private LibreOffice profile could be made!', 2006, FALSE); } }
    if ($commandMayRun) {
      exec('LANG=C.UTF-8 LC_ALL=C.UTF-8 '.$sandboxedCommand.' 2>&1', $commandOutput, $commandExitCode);
      $ReturnData = implode(PHP_EOL, $commandOutput);
      // / A LibreOffice that was killed exits 137, which is 128 & the number of the signal that kills.
      // / Inside a per conversion ceiling that is almost always its memory. Until v4.0.6 a kill reached
      // / the log only as the document converter timing out, once every retry had been killed the same way.
      if ($commandExitCode === 137) warningEntry('LibreOffice was killed while it converted '.basename($inputPath).'. One killed inside a per conversion ceiling has almost always run out of the memory it was given. With --Enhanced Logging Verbosity-- on, the Conversion Limit entry names that ceiling, & --Maximum Per Conversion Resources-- in config.php sets the most each type may be given.');
      // / LibreOffice derives the output name from the input, so rename when it differs.
      $producedPath = dirname($outputPath).$DirSep.pathinfo($inputPath, PATHINFO_FILENAME).'.'.$cleanExtension;
      if ($producedPath !== $outputPath && file_exists($producedPath) && !file_exists($outputPath)) @rename($producedPath, $outputPath);
      if (file_exists($outputPath)) $ConversionCompleted = TRUE; }
    if ($profileFolder !== '') $profileIsRemoved = removeLibreOfficeProfile($profileFolder);
    if ($profileFolder !== '' && !$profileIsRemoved) warningEntry('A private LibreOffice profile could not be removed from '.$profileFolder.'.'); }
  // / The answer is read from the flag. Comparing the two commands said YES for every
  // / unsandboxed conversion, because the output directory had been edited into one of them.
  if ($Verbose) logEntry('LibreOffice Conversion: '.basename($inputPath).' to '.$cleanExtension.', Sandboxed: '.($commandIsSandboxed ? 'YES' : 'NO').', Result: '.($ConversionCompleted ? 'OK' : 'FAILED').'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $ReturnData is not purged, because it is a return value.
  purgeSensitiveMemory($EnableMemoryProtection, $sofficeBinary, $sofficeCommand, $sandboxedCommand, $producedPath, $cleanExtension, $commandMayRun, $documentIsSanitized, $referencesRemoved, $inputPath, $outputPath, $targetExtension, $profileFolder, $profileIsReady, $profileIsRemoved, $sofficeFlags, $commandIsSandboxed, $commandOutput, $commandExitCode);
  return array($ConversionCompleted, $ReturnData); }
// / -----------------------------------------------------------------------------------

?>
