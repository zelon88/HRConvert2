<?php
// / -----------------------------------------------------------------------------------
// / Copyright information ...
// / HRConvert2, Copyright on 8/28/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / Application information ...
// / This application is designed to provide a web-interface for converting file formats
// / on a server for users of any web browser without authentication.
// /
// / Fileinformation ...
// / v3.8.6.
// / HRConvert2 Setup Core.
// / A detachable installation & configuration component. convertCore.php runs without it.
// / This file defines functions only. convertCore.php dispatches into them.
// / Everything that installs, configures, updates or repairs an installation belongs here.
// /
// / Hardware requirements ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / Dependency requirements ...
// / This application requires Debian Linux, Apache 2.4, PHP 8+, FFMPEG, Dia, LibreOffice, 
// / Mkisofs, 7zip, Unoconv, libgxps-utils, Tesseract, Unzip, OpenSCAD, Rar, Inkscape, Calibre,
// / Unrar, ClamAV, MeshLab, PopplerUtils, PDFTOTEXT, ImageMagick, bwrap Dia & xvfb-run.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Refuse direct execution. This file is a component & has no standalone context.
// / This halt cannot use quickDie. Reaching this line means convertCore.php was never
// / loaded, so quickDie is not defined & calling it would replace a clear refusal with an
// / undefined function error.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-2: This file cannot process your request! Please submit your file to convertCore.php instead!'.PHP_EOL);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The component version. convertCore.php reads this without executing the file.
$SetupCoreVersion = 'v3.9.3';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to read a configuration file into sections, variables & comment blocks.
// / Accepts the absolute path of the file to read.
// / Returns a parse boolean & an array of sections, in that order.
// /
// / A section header is three dashes either side of the name.
// /   // / ---General Information---
// / A variable label is TWO dashes, indented by two spaces.
// /   // /  --Application Name--
// / Those two are unambiguous & are the only forms this relies on.
// /
// / A third form is ambiguous & is resolved against the model.
// / Two dashes indented by ONE space appears in config.php as both. It is the section
// / header for Supported File Format Information, & it is also how two variable labels
// / were typed by mistake. A name the model recognizes is a section. Anything else is a
// / label, which folds those two mistakes into the section they belong to rather than
// / fragmenting the parse around them.
// /
// / THE FILE HEADER IS NOT A SECTION. Lines such as COPYRIGHT INFORMATION ... end in an
// / ellipsis & were once matched as headers, which put every real section behind a
// / fictional one & reported all of them missing.
// /
// / A variable assignment may span several lines. Reading continues until a line ends with
// / a semicolon, so a multi line array is captured whole rather than truncated.
function parseConfigFile($configPath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ParseSucceeded = FALSE;
  $DetectedSections = array();
  $configLines = $headerMatch = $variableMatch = $knownSections = array();
  $currentSection = $currentComment = $lineText = $assignmentBuffer = $variableName = $candidateName = '';
  $lineIndex = $lineCount = $assignmentStart = 0;
  $insideAssignment = FALSE;
  // / The model decides what an ambiguous marker means, so the two are never out of step.
  $knownSections = array_keys(collectConfigModel());
  if (!file_exists($configPath) or !is_readable($configPath)) warningEntry('The configuration file at '.$configPath.' could not be read.');
  else {
    $configLines = file($configPath, FILE_IGNORE_NEW_LINES);
    if (!is_array($configLines) or count($configLines) < 1) warningEntry('The configuration file at '.$configPath.' is empty.');
    else {
      $lineCount = count($configLines);
      $currentSection = 'Unsectioned';
      $DetectedSections[$currentSection] = array('Variables' => array(), 'FirstLine' => 0);
      while ($lineIndex < $lineCount) {
        $lineText = $configLines[$lineIndex];
        // / An unambiguous section header. Three dashes either side.
        // / A separator line is all dashes & would otherwise match, so a section name is
        // / required to begin with a letter & to carry no dashes of its own.
        if (!$insideAssignment && preg_match('/^\/\/ \/ ---([A-Za-z][A-Za-z0-9 &]*)---\s*$/', $lineText, $headerMatch)) {
          $currentSection = trim($headerMatch[1]);
          if (!isset($DetectedSections[$currentSection])) $DetectedSections[$currentSection] = array('Variables' => array(), 'FirstLine' => $lineIndex + 1);
          $currentComment = ''; }
        // / The ambiguous form. A name the model knows is a section, anything else a label.
        else if (!$insideAssignment && preg_match('/^\/\/ \/ --([A-Za-z][A-Za-z0-9 &]*)--\s*$/', $lineText, $headerMatch)) {
          $candidateName = trim($headerMatch[1]);
          if (in_array($candidateName, $knownSections, TRUE)) {
            $currentSection = $candidateName;
            if (!isset($DetectedSections[$currentSection])) $DetectedSections[$currentSection] = array('Variables' => array(), 'FirstLine' => $lineIndex + 1);
            $currentComment = ''; }
          else $currentComment = $candidateName; }
        // / A comment line belonging to whatever variable comes next.
        else if (!$insideAssignment && preg_match('/^\/\/ \/(.*)$/', $lineText, $headerMatch)) {
          if (trim($headerMatch[1]) === '' or strpos($headerMatch[1], '-----') !== FALSE) $currentComment = '';
          else $currentComment = ($currentComment === '' ? trim($headerMatch[1]) : $currentComment.PHP_EOL.trim($headerMatch[1])); }
        // / The first line of a variable assignment.
        else if (!$insideAssignment && preg_match('/^\$([A-Za-z_]\w*)\s*=\s*(.*)$/', $lineText, $variableMatch)) {
          $variableName = $variableMatch[1];
          $assignmentBuffer = $lineText;
          $assignmentStart = $lineIndex;
          if (preg_match('/;\s*$/', $lineText)) {
            $DetectedSections[$currentSection]['Variables'][$variableName] = array(
              'Value' => rtrim(trim($variableMatch[2]), ';'),
              'Raw' => $assignmentBuffer,
              'Comment' => $currentComment,
              'StartLine' => $assignmentStart,
              'EndLine' => $lineIndex);
            $currentComment = ''; }
          else $insideAssignment = TRUE; }
        // / A continuation of a multi line assignment.
        else if ($insideAssignment) {
          $assignmentBuffer = $assignmentBuffer.PHP_EOL.$lineText;
          if (preg_match('/;\s*$/', $lineText)) {
            $DetectedSections[$currentSection]['Variables'][$variableName] = array(
              'Value' => trim(substr($assignmentBuffer, strpos($assignmentBuffer, '=') + 1), " \t\n\r;"),
              'Raw' => $assignmentBuffer,
              'Comment' => $currentComment,
              'StartLine' => $assignmentStart,
              'EndLine' => $lineIndex);
            $currentComment = '';
            $insideAssignment = FALSE; } }
        $lineIndex++; }
      // / An unterminated assignment means the file is not shaped the way this expects.
      if ($insideAssignment) warningEntry('The configuration file at '.$configPath.' ends inside an unterminated assignment.');
      else $ParseSucceeded = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $configLines, $headerMatch, $variableMatch, $knownSections, $currentSection, $currentComment, $lineText, $assignmentBuffer, $variableName, $candidateName, $lineIndex, $lineCount, $assignmentStart, $insideAssignment, $configPath);
  return array($ParseSucceeded, $DetectedSections); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether a parsed configuration file can be trusted.
// / Accepts the model & the detected sections, in that order.
// / Returns a trust boolean & an array of findings, in that order.
// / A section holding fewer than half, or more than one and a half times, the variables
// / this utility expects is treated as a parse failure rather than as a configuration
// / problem. The tolerance is deliberately wide, so a release that adds a few settings
// / does not stop the utility working before anybody updates the model.
// / NOTHING IS WRITTEN ANYWHERE IF ANY SECTION FAILS. A file that cannot be read
// / correctly cannot be edited safely, & a partial edit is worse than no edit.
function validateConfigSections($configModel, $detectedSections) {
  // / Set variables.
  global $EnableMemoryProtection;
  $SectionsAreTrusted = TRUE;
  $ValidationFindings = array();
  $sectionName = $variableName = '';
  $sectionModel = $sectionDetected = array();
  $expectedCount = $detectedCount = 0;
  $lowerBound = $upperBound = 0.0;
  foreach ($configModel as $sectionName => $sectionModel) {
    if (!isset($detectedSections[$sectionName])) {
      $ValidationFindings[] = array('Section' => $sectionName, 'Status' => 'MISSING', 'Detail' => 'This section is not present in the configuration file.');
      continue; }
    $expectedCount = count($sectionModel['Variables']);
    $detectedCount = count($detectedSections[$sectionName]['Variables']);
    // / A section this utility knows nothing about cannot be range checked.
    if ($expectedCount < 1) $ValidationFindings[] = array('Section' => $sectionName, 'Status' => 'UNMODELLED', 'Detail' => $detectedCount.' variable(s) present. This utility holds no model for them.');
    else {
      $lowerBound = $expectedCount * 0.5;
      $upperBound = $expectedCount * 1.5;
      if ($detectedCount < $lowerBound or $detectedCount > $upperBound) {
        $SectionsAreTrusted = FALSE;
        $ValidationFindings[] = array('Section' => $sectionName, 'Status' => 'FAILED', 'Detail' => 'Expected about '.$expectedCount.' variable(s) & found '.$detectedCount.'. Parsing is not trustworthy.'); }
      else $ValidationFindings[] = array('Section' => $sectionName, 'Status' => 'OK', 'Detail' => $detectedCount.' of about '.$expectedCount.' variable(s) present.'); } }
  // / Report a section in the file that this utility has never heard of.
  foreach ($detectedSections as $sectionName => $sectionDetected) {
    if ($sectionName !== 'Unsectioned' && !isset($configModel[$sectionName])) $ValidationFindings[] = array('Section' => $sectionName, 'Status' => 'UNACCOUNTED', 'Detail' => count($sectionDetected['Variables']).' variable(s) present. This section is newer than this utility.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $sectionName, $variableName, $sectionModel, $sectionDetected, $expectedCount, $detectedCount, $lowerBound, $upperBound, $configModel, $detectedSections);
  return array($SectionsAreTrusted, $ValidationFindings); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to check that dependent variables agree with one another.
// / Accepts the model & the detected sections, in that order.
// / Returns a coherence boolean & an array of findings, in that order.
// / A setting that is meaningless without another is reported when the other is disabled.
// / This never blocks anything. It tells an administrator that a value they set is being
// / ignored, which is otherwise invisible & is the single most common configuration
// / mistake there is.
function validateConfigDependencies($configModel, $detectedSections) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ConfigIsCoherent = TRUE;
  $DependencyFindings = array();
  $sectionName = $variableName = $dependsOn = $dependsValue = '';
  $sectionModel = $variableModel = array();
  $flatValues = array();
  foreach ($detectedSections as $sectionName => $sectionModel) {
    foreach ($sectionModel['Variables'] as $variableName => $variableModel) $flatValues[$variableName] = $variableModel['Value']; }
  foreach ($configModel as $sectionName => $sectionModel) {
    foreach ($sectionModel['Variables'] as $variableName => $variableModel) {
      $dependsOn = (string)$variableModel['Depends'];
      if ($dependsOn === '' or !isset($flatValues[$variableName]) or !isset($flatValues[$dependsOn])) continue;
      $dependsValue = strtoupper(trim((string)$flatValues[$dependsOn], " '\""));
      if ($dependsValue === 'FALSE' or $dependsValue === '0') {
        $ConfigIsCoherent = FALSE;
        $DependencyFindings[] = array('Variable' => $variableName, 'Depends' => $dependsOn, 'Detail' => '$'.$variableName.' is set but $'.$dependsOn.' is FALSE, so it has no effect.'); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $sectionName, $variableName, $dependsOn, $dependsValue, $sectionModel, $variableModel, $flatValues, $configModel, $detectedSections);
  return array($ConfigIsCoherent, $DependencyFindings); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to copy a configuration file somewhere safe.
// / Accepts the source path & the destination path, in that order.
// / Returns TRUE when the copy exists & matches the source byte for byte.
// / A backup runs as whoever invoked this utility, not as the web server user, because the
// / destination belongs to the administrator taking the backup.
function backupConfigFile($configPath, $backupPath) {
  // / Set variables.
  global $EnableMemoryProtection, $RunningAsRoot, $ApacheUser;
  $BackupSucceeded = FALSE;
  $backupDirectory = '';
  $backupDirectory = dirname($backupPath);
  if (!file_exists($configPath)) errorEntry('A configuration backup was requested but the source does not exist!', 32000, FALSE);
  else if (!is_dir($backupDirectory) or !is_writable($backupDirectory)) errorEntry('A configuration backup was requested but '.$backupDirectory.' is not writable by this account!', 32001, FALSE);
  else if (file_exists($backupPath)) errorEntry('A configuration backup was requested but '.$backupPath.' already exists. Nothing was overwritten!', 32002, FALSE);
  else {
    @copy($configPath, $backupPath);
    // / A backup taken while running as root is created owned by root, & it lands in a
    // / directory the permissions pass manages. Correcting it here rather than leaving it
    // / for that pass means a backup taken by hand, outside an install, is also correct.
    if ($RunningAsRoot && $ApacheUser !== '' && file_exists($backupPath)) @chown($backupPath, $ApacheUser);
    if (file_exists($backupPath)) @chmod($backupPath, 0640);
    if (file_exists($backupPath) && filesize($backupPath) === filesize($configPath)) {
      $BackupSucceeded = TRUE;
      logEntry('The configuration at '.$configPath.' was backed up to '.$backupPath.'.'); }
    else errorEntry('A configuration backup to '.$backupPath.' did not complete!', 32003, FALSE); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $backupDirectory, $configPath, $backupPath);
  return $BackupSucceeded; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write a configuration file safely.
// / Accepts the destination path & the complete new contents, in that order.
// / Returns TRUE when the file was replaced & the replacement parses as PHP.
// / The new contents are written beside the original, checked, & only then moved into
// / place. A configuration that does not parse is never allowed to reach the installation.
// / While running as root the write is performed as the web server user, so an
// / administrator repairing a configuration cannot leave a file its own server cannot read.
function writeConfigFile($configPath, $configContents) {
  // / Set variables.
  global $ApacheUser, $RunningAsRoot, $EnableMemoryProtection;
  $WriteSucceeded = FALSE;
  $temporaryPath = $writeCommand = '';
  $lintOutput = array();
  $lintExitCode = 1;
  $bytesWritten = 0;
  $temporaryPath = $configPath.'.hrconvert2-pending';
  $bytesWritten = @file_put_contents($temporaryPath, $configContents);
  if ($bytesWritten !== strlen($configContents)) errorEntry('A configuration could not be staged at '.$temporaryPath.'!', 32004, FALSE);
  else {
    // / Refuse to install a configuration that will not parse. There is no recovering from
    // / a syntax error in config.php from a web request.
    // / This runs unsandboxed & convention 22 requires that to be said.
    // / php -l is the interpreter already running this code rather than a managed
    // / dependency, & the only path it is given was written by the line above from a model
    // / this file owns. No part of it comes from a user.
    // / Sandboxing it would also defeat it. The point is to ask THIS interpreter, with the
    // / extensions & version this installation actually runs, whether the file it is about
    // / to load will parse. An answer from a different environment would not be the answer.
    exec('php -l '.escapeshellarg($temporaryPath).' 2>&1', $lintOutput, $lintExitCode);
    if ($lintExitCode !== 0) {
      @unlink($temporaryPath);
      errorEntry('The staged configuration does not parse & was discarded. '.implode(' ', $lintOutput), 32005, FALSE); }
    else {
      // / Hand the file to the account that has to read it before it is moved into place.
      if ($RunningAsRoot) {
        @chown($temporaryPath, $ApacheUser);
        @chgrp($temporaryPath, $ApacheUser); }
      @chmod($temporaryPath, 0644);
      if (@rename($temporaryPath, $configPath)) {
        $WriteSucceeded = TRUE;
        logEntry('The configuration at '.$configPath.' was rewritten.'); }
      else {
        @unlink($temporaryPath);
        errorEntry('A staged configuration could not be moved into place at '.$configPath.'!', 32006, FALSE); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $temporaryPath, $writeCommand, $lintOutput, $lintExitCode, $bytesWritten, $configPath, $configContents);
  return $WriteSucceeded; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to ADD a setting that the configuration file does not contain.
// / Accepts the file lines by reference, the setting name, its formatted value & the name
// / of the section it belongs to. Returns whether the line was added.
// /
// / replaceConfigAssignment() rewrites a line that exists. This writes one that does not,
// / & the difference is what makes a configuration self healing rather than merely
// / correctable.
// / A release that adds a required setting leaves every existing file short of it. Without
// / this, the only remedies were editing by hand or adopting the shipped file & losing
// / every local value, which is not a remedy an automated tool can use.
// /
// / The setting is placed at the END of the section it belongs to, so a file keeps the
// / shape its author gave it. A section that is not in the file gets one, appended at the
// / end with its own header, because a setting written into no section reads as though it
// / belongs to whichever section happens to precede it.
// / The default is written with a comment saying it was added & when. A value that appeared
// / on its own with no explanation is the thing an administrator finds a year later &
// / cannot account for.
function appendConfigAssignment(&$configLines, $variableName, $newValue, $sectionName, $detectedSections) {
  // / Set variables.
  global $EnableMemoryProtection;
  $AssignmentWasAdded = FALSE;
  $insertIndex = $lineIndex = 0;
  $variableRecord = $newLines = array();
  $sectionExists = FALSE;
  $sectionExists = isset($detectedSections[$sectionName]) && isset($detectedSections[$sectionName]['Variables']);
  if ($sectionExists) {
    // / The end of the section is the last line of its last setting.
    foreach ($detectedSections[$sectionName]['Variables'] as $variableRecord) {
      if (isset($variableRecord['EndLine']) && (int)$variableRecord['EndLine'] > $insertIndex) $insertIndex = (int)$variableRecord['EndLine']; } }
  // / A section with no settings, or no section at all, means the end of the file. The end
  // / of the FILE is not the end of the php block, & a setting written after the closing
  // / tag is text the interpreter never reads.
  // / An earlier version appended past it & produced a file that looked repaired & was not.
  if ($insertIndex === 0) {
    $insertIndex = count($configLines) - 1;
    for ($lineIndex = count($configLines) - 1; $lineIndex >= 0; $lineIndex--) {
      if (strpos((string)$configLines[$lineIndex], '?>') !== FALSE) {
        $insertIndex = $lineIndex - 1;
        break; } } }
  $newLines[] = '';
  if (!$sectionExists) {
    $newLines[] = '// / -----------------------------------------------------------------------------------';
    $newLines[] = '// /  --'.$sectionName.'--'; }
  $newLines[] = '// /   Added automatically on '.date('F j, Y').' because this release requires it.';
  $newLines[] = '// /   The value below is the shipped default. Read the documentation for this setting';
  $newLines[] = '// /   before relying on it.';
  $newLines[] = '$'.$variableName.' = '.$newValue.';';
  // / Splice rather than append, so the setting lands inside its own section.
  array_splice($configLines, $insertIndex + 1, 0, $newLines);
  $AssignmentWasAdded = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $insertIndex, $lineIndex, $variableRecord, $newLines, $sectionExists, $variableName, $newValue, $sectionName, $detectedSections);
  return $AssignmentWasAdded; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to replace the value of one variable in a configuration file.
// / Accepts the file lines by reference, the detected variable record & the new value.
// / Returns TRUE when the assignment was replaced.
// / The comment block above the variable is never touched. Only the assignment changes.
// / A multi line assignment is replaced whole & collapses to a single line.
function replaceConfigAssignment(&$configLines, $variableRecord, $variableName, $newValue) {
  // / Set variables.
  global $EnableMemoryProtection;
  $AssignmentWasReplaced = FALSE;
  $replacementLine = $removalIndex = '';
  $lineOffset = 0;
  if (isset($variableRecord['StartLine']) && isset($configLines[$variableRecord['StartLine']])) {
    $replacementLine = '$'.$variableName.' = '.$newValue.';';
    $configLines[$variableRecord['StartLine']] = $replacementLine;
    // / Blank every continuation line of a multi line assignment rather than deleting it,
    // / so every later line number in the parse remains correct.
    $lineOffset = (int)$variableRecord['StartLine'] + 1;
    while ($lineOffset <= (int)$variableRecord['EndLine']) {
      $configLines[$lineOffset] = NULL;
      $lineOffset++; }
    $AssignmentWasReplaced = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $replacementLine, $removalIndex, $lineOffset, $variableRecord, $variableName, $newValue);
  return $AssignmentWasReplaced; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to escape a value for a single quoted PHP string.
// / Accepts the raw value. Returns the escaped value, without its surrounding quotes.
// / A single quoted PHP string recognizes two escapes & they must be applied in order.
// / A backslash is escaped FIRST & the quote second. Escaping only the quote left a value
// / ending in a backslash producing a trailing pair that PHP reads as an escaped quote, so
// / the string never closed. A Windows style path typed by an administrator was enough to
// / do it, & the result is a config.php that fails to parse on the very next request.
// / The same omission let a crafted value close the string & append its own statements to
// / the file the application requires on every request.
function escapeConfigString($rawValue) {
  // / Set variables.
  global $EnableMemoryProtection;
  $EscapedValue = '';
  $EscapedValue = str_replace(array('\\', "'"), array('\\\\', "\\'"), (string)$rawValue);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $rawValue);
  return $EscapedValue; }
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to format a value for writing into a configuration file.
// / Accepts the declared type & the value, in that order.
// / Returns a validity boolean & the formatted value, in that order.
// / A value that does not suit its declared type is refused rather than written badly.
function formatConfigValue($valueType, $rawValue) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ValueIsValid = FALSE;
  $FormattedValue = '';
  $cleanValue = trim((string)$rawValue);
  if ($valueType === 'bool') {
    if (strtoupper($cleanValue) === 'TRUE' or $cleanValue === '1') { $FormattedValue = 'TRUE'; $ValueIsValid = TRUE; }
    else if (strtoupper($cleanValue) === 'FALSE' or $cleanValue === '0') { $FormattedValue = 'FALSE'; $ValueIsValid = TRUE; } }
  else if ($valueType === 'int') {
    if (ctype_digit(ltrim($cleanValue, '-')) && $cleanValue !== '' && $cleanValue !== '-') { $FormattedValue = (string)(int)$cleanValue; $ValueIsValid = TRUE; } }
  else if ($valueType === 'array') {
    // / An array is written exactly as typed, because this utility cannot know its shape.
    if (strpos($cleanValue, 'array(') === 0 or strpos($cleanValue, '[') === 0) { $FormattedValue = $cleanValue; $ValueIsValid = TRUE; } }
  else if ($valueType === 'path') {
    if ($cleanValue !== '' && strpos($cleanValue, "\n") === FALSE) { $FormattedValue = "'".escapeConfigString(rtrim($cleanValue, '/'))."'"; $ValueIsValid = TRUE; } }
  else {
    // / string, csv & version are all written as quoted strings.
    if (strpos($cleanValue, "\n") === FALSE) { $FormattedValue = "'".escapeConfigString($cleanValue)."'"; $ValueIsValid = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cleanValue, $valueType, $rawValue);
  return array($ValueIsValid, $FormattedValue); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to display one section & everything known about it.
// / Accepts the section name, the model for that section & the detected variables.
// / Returns the number of variables displayed.
// / A variable this utility has no default for is still shown & is still editable. It is
// / labelled so the operator knows a reset cannot restore it.
function showConfigSection($sectionName, $sectionModel, $detectedVariables) {
  // / Set variables.
  global $Lol, $EnableMemoryProtection;
  $VariablesDisplayed = 0;
  $variableName = $currentValue = $defaultValue = $dependsNote = $statusNote = '';
  $variableModel = array();
  print($Lol.'== '.$sectionName.' =='.$Lol);
  if (!$sectionModel['Writable']) print('   This section is displayed only. It is never written by this utility.'.$Lol);
  foreach ($detectedVariables as $variableName => $variableRecord) {
    $currentValue = (string)$variableRecord['Value'];
    if (strlen($currentValue) > 58) $currentValue = substr($currentValue, 0, 55).'...';
    $defaultValue = 'none known';
    $dependsNote = '';
    $statusNote = '  UNACCOUNTED';
    if (isset($sectionModel['Variables'][$variableName])) {
      $variableModel = $sectionModel['Variables'][$variableName];
      $statusNote = '';
      if ((string)$variableModel['Default'] !== '') $defaultValue = (string)$variableModel['Default'];
      if ((string)$variableModel['Depends'] !== '') $dependsNote = ' [needs $'.$variableModel['Depends'].']'; }
    print('   $'.str_pad($variableName, 36).$currentValue.$statusNote.$Lol);
    print('   '.str_pad('', 36).'default: '.$defaultValue.$dependsNote.$Lol);
    $VariablesDisplayed++; }
  // / A variable this utility expects & the file does not carry is worth naming.
  foreach ($sectionModel['Variables'] as $variableName => $variableModel) {
    if (!isset($detectedVariables[$variableName])) print('   $'.str_pad($variableName, 36).'ABSENT FROM THIS FILE'.$Lol); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $variableRecord, $variableName, $currentValue, $defaultValue, $dependsNote, $statusNote, $variableModel, $sectionName, $sectionModel, $detectedVariables);
  return $VariablesDisplayed; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to walk one section with the operator & collect their changes.
// / Accepts the section name, its model, its detected variables & the pending change set.
// / Returns the number of changes added to the pending set.
// / Nothing is written here. Every change is collected & applied in one write at the end,
// / so an operator who changes their mind halfway has changed nothing.
function updateConfigSection($sectionName, $sectionModel, $detectedVariables, &$pendingChanges) {
  // / Set variables.
  global $Lol, $EnableMemoryProtection;
  $ChangesCollected = 0;
  $operatorChoice = $variableName = $newValue = $formattedValue = '';
  $variableModel = $variableRecord = array();
  $valueIsValid = FALSE;
  showConfigSection($sectionName, $sectionModel, $detectedVariables);
  if (!$sectionModel['Writable']) print($Lol.'   Nothing in this section can be changed here. Edit config.php directly.'.$Lol);
  else {
    print($Lol.'   show info                    Display the comment block for every variable.'.$Lol);
    print('   show comment block <Var>     Display the comment block for one variable.'.$Lol);
    print('   reset all                    Reset every variable in this section to its default.'.$Lol);
    print('   reset <Variable>             Reset one variable to its default.'.$Lol);
    print('   set <Variable>               Enter a new value for one variable.'.$Lol);
    print('   done                         Keep everything as it is & move on.'.$Lol);
    $operatorChoice = 'start';
    while ($operatorChoice !== 'done' && $operatorChoice !== 'exit') {
      $operatorChoice = strtolower(promptOperator($Lol.'   '.$sectionName.' > '));
      if ($operatorChoice === '') $operatorChoice = 'done';
      // / One variable, in full. Tested before show info so the longer command wins, &
      // / before reset & set so a name beginning with either word is not mistaken for one.
      else if (strpos($operatorChoice, 'show comment block ') === 0 or (strpos($operatorChoice, 'show ') === 0 && $operatorChoice !== 'show info')) {
        $variableName = trim(substr($operatorChoice, (strpos($operatorChoice, 'show comment block ') === 0 ? 19 : 5)));
        $variableName = configVariableCase($variableName, $sectionModel, $detectedVariables);
        if ($variableName === '' or !isset($detectedVariables[$variableName])) print('   $'.$variableName.' is not present in this configuration file.'.$Lol);
        else {
          print($Lol.'   $'.$variableName.$Lol);
          if (trim((string)$detectedVariables[$variableName]['Comment']) !== '') print('     '.str_replace(PHP_EOL, PHP_EOL.'     ', trim((string)$detectedVariables[$variableName]['Comment'])).$Lol);
          else print('     No comment block was found above this variable.'.$Lol);
          print('     current: '.$detectedVariables[$variableName]['Value'].$Lol);
          // / What this utility knows is worth saying beside what the file says, because a
          // / variable it holds no model for cannot be reset & the operator should know.
          if (!isset($sectionModel['Variables'][$variableName])) print('     This utility holds no model for this variable. It can be set but not reset.'.$Lol);
          else {
            print('     type:    '.$sectionModel['Variables'][$variableName]['Type'].$Lol);
            print('     default: '.((string)$sectionModel['Variables'][$variableName]['Default'] === '' ? 'none known' : (string)$sectionModel['Variables'][$variableName]['Default']).$Lol);
            if ((string)$sectionModel['Variables'][$variableName]['Depends'] !== '') print('     needs:   $'.$sectionModel['Variables'][$variableName]['Depends'].' to be TRUE'.$Lol); } } }
      else if ($operatorChoice === 'show info') {
        foreach ($detectedVariables as $variableName => $variableRecord) {
          print($Lol.'   $'.$variableName.$Lol);
          if (trim((string)$variableRecord['Comment']) !== '') print('     '.str_replace(PHP_EOL, PHP_EOL.'     ', trim((string)$variableRecord['Comment'])).$Lol);
          else print('     No comment block was found above this variable.'.$Lol); } }
      else if ($operatorChoice === 'reset all') {
        foreach ($sectionModel['Variables'] as $variableName => $variableModel) {
          if ((string)$variableModel['Default'] === '' or !isset($detectedVariables[$variableName])) continue;
          list ($valueIsValid, $formattedValue) = formatConfigValue($variableModel['Type'], $variableModel['Default']);
          if ($valueIsValid) {
            $pendingChanges[$variableName] = array('Section' => $sectionName, 'Value' => $formattedValue, 'Record' => $detectedVariables[$variableName]);
            $ChangesCollected++; } }
        print('   Queued '.$ChangesCollected.' reset(s). Nothing is written until you finish.'.$Lol); }
      else if (strpos($operatorChoice, 'reset ') === 0) {
        $variableName = trim(substr($operatorChoice, 6));
        $variableName = configVariableCase($variableName, $sectionModel, $detectedVariables);
        if (!isset($sectionModel['Variables'][$variableName])) print('   $'.$variableName.' has no default known to this utility. Use set instead.'.$Lol);
        else if (!isset($detectedVariables[$variableName])) print('   $'.$variableName.' is not present in this configuration file.'.$Lol);
        else {
          list ($valueIsValid, $formattedValue) = formatConfigValue($sectionModel['Variables'][$variableName]['Type'], $sectionModel['Variables'][$variableName]['Default']);
          if (!$valueIsValid) print('   The default for $'.$variableName.' could not be formatted. Nothing queued.'.$Lol);
          else {
            $pendingChanges[$variableName] = array('Section' => $sectionName, 'Value' => $formattedValue, 'Record' => $detectedVariables[$variableName]);
            $ChangesCollected++;
            print('   Queued $'.$variableName.' = '.$formattedValue.$Lol); } } }
      else if (strpos($operatorChoice, 'set ') === 0) {
        $variableName = trim(substr($operatorChoice, 4));
        $variableName = configVariableCase($variableName, $sectionModel, $detectedVariables);
        if (!isset($detectedVariables[$variableName])) print('   $'.$variableName.' is not present in this configuration file.'.$Lol);
        else {
          if (trim((string)$detectedVariables[$variableName]['Comment']) !== '') print('     '.str_replace(PHP_EOL, PHP_EOL.'     ', trim((string)$detectedVariables[$variableName]['Comment'])).$Lol);
          print('     current: '.$detectedVariables[$variableName]['Value'].$Lol);
          if (isset($sectionModel['Variables'][$variableName])) $newValue = promptOperator('     new value ('.$sectionModel['Variables'][$variableName]['Type'].') > ');
          else {
            print('     This utility holds no model for this variable, so it cannot be validated.'.$Lol);
            $newValue = promptOperator('     new value, written exactly as typed > '); }
          if ($newValue === '') print('     Nothing entered. Left alone.'.$Lol);
          else {
            if (isset($sectionModel['Variables'][$variableName])) list ($valueIsValid, $formattedValue) = formatConfigValue($sectionModel['Variables'][$variableName]['Type'], $newValue);
            else { $valueIsValid = TRUE; $formattedValue = $newValue; }
            if (!$valueIsValid) print('     That value does not suit the declared type. Nothing queued.'.$Lol);
            else {
              $pendingChanges[$variableName] = array('Section' => $sectionName, 'Value' => $formattedValue, 'Record' => $detectedVariables[$variableName]);
              $ChangesCollected++;
              print('     Queued $'.$variableName.' = '.$formattedValue.$Lol); } } } }
      else if ($operatorChoice !== 'done' && $operatorChoice !== 'exit') print('   Unrecognized. Type done to move on.'.$Lol); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $operatorChoice, $variableName, $newValue, $formattedValue, $variableModel, $variableRecord, $valueIsValid, $sectionName, $sectionModel, $detectedVariables);
  return $ChangesCollected; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to resolve a variable name an operator typed to the one that exists.
// / Accepts the typed name, the section model & the detected variables, in that order.
// / Returns the correctly cased name, or the typed name when nothing matches.
// / An operator typing a name at a prompt should not have to match capitalization, & a
// / leading dollar sign is accepted because everybody types one.
function configVariableCase($typedName, $sectionModel, $detectedVariables) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ResolvedName = ltrim(trim((string)$typedName), '$');
  $candidateName = '';
  $variableRecord = array();
  foreach ($detectedVariables as $candidateName => $variableRecord) {
    if (strtolower($candidateName) === strtolower($ResolvedName)) $ResolvedName = $candidateName; }
  foreach ($sectionModel['Variables'] as $candidateName => $variableRecord) {
    if (strtolower($candidateName) === strtolower($ResolvedName)) $ResolvedName = $candidateName; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $candidateName, $variableRecord, $typedName, $sectionModel, $detectedVariables);
  return $ResolvedName; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to apply a collected change set to a configuration file.
// / Accepts the file path, the detected sections & the pending change set, in that order.
// / Returns a success boolean & the number of assignments replaced, in that order.
// / A backup is taken first, always, without being asked for. An operator who wanted this
// / to be reversible & did not think to ask is the operator who most needs it to be.
function applyConfigChanges($configPath, $detectedSections, $pendingChanges) {
  // / Set variables.
  global $Lol, $EnableMemoryProtection;
  $ChangesWereApplied = FALSE;
  $ChangesApplied = 0;
  $configLines = array();
  $variableName = $backupPath = $newContents = '';
  $changeRecord = array();
  $backupSucceeded = FALSE;
  if (empty($pendingChanges)) print($Lol.'No changes were queued. Nothing was written.'.$Lol);
  else {
    $backupPath = $configPath.'.hrconvert2-'.date('Y-m-d-His').'.bak';
    $backupSucceeded = backupConfigFile($configPath, $backupPath);
    if (!$backupSucceeded) print($Lol.'A backup could not be taken, so nothing was written.'.$Lol);
    else {
      $configLines = file($configPath, FILE_IGNORE_NEW_LINES);
      foreach ($pendingChanges as $variableName => $changeRecord) {
        // / A change with no record names a setting the file does not contain, & it is
        // / ADDED rather than skipped. Skipping it is what made a repair do nothing.
        if (!isset($changeRecord['Record']) or !isset($changeRecord['Record']['StartLine'])) {
          if (appendConfigAssignment($configLines, $variableName, $changeRecord['Value'], $changeRecord['Section'], $detectedSections)) $ChangesApplied++; }
        else if (replaceConfigAssignment($configLines, $changeRecord['Record'], $variableName, $changeRecord['Value'])) $ChangesApplied++; }
      // / A blanked continuation line is dropped here rather than during the replacement,
      // / so every line number stayed correct while the replacements were being made.
      $newContents = implode(PHP_EOL, array_filter($configLines, function($lineText) { return $lineText !== NULL; })).PHP_EOL;
      if (writeConfigFile($configPath, $newContents)) {
        $ChangesWereApplied = TRUE;
        print($Lol.'Wrote '.$ChangesApplied.' change(s) to '.$configPath.'.'.$Lol);
        print('The previous configuration is at '.$backupPath.'.'.$Lol); }
      else print($Lol.'The configuration could not be written. The original is untouched.'.$Lol); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $configLines, $variableName, $backupPath, $newContents, $changeRecord, $backupSucceeded, $configPath, $detectedSections, $pendingChanges);
  return array($ChangesWereApplied, $ChangesApplied); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to locate the configuration file this utility will operate on.
// / Accepts a path supplied on the command line, which may be empty.
// / Returns a resolution boolean & the absolute path, in that order.
// / A path supplied outright is used outright, so an administrator can stage an upgrade
// / outside the installation & configure it before it goes live.
// / With nothing supplied, the configuration belonging to this installation is used.
// / A call arriving from convertCore.php never supplies a path, so it can only ever
// / operate on the configuration that core is responsible for.
function resolveConfigTarget($suppliedPath) {
  // / Set variables.
  global $InstLoc, $DirSep, $EnableMemoryProtection;
  $TargetWasResolved = FALSE;
  $ConfigTarget = '';
  $candidatePath = '';
  if (trim((string)$suppliedPath) !== '') {
    $candidatePath = realpath(trim((string)$suppliedPath));
    if ($candidatePath === FALSE) errorEntry('The configuration file supplied on the command line does not exist!', 32007, FALSE);
    else if (!is_file($candidatePath)) errorEntry('The configuration path supplied on the command line is not a file!', 32008, FALSE);
    else { $ConfigTarget = $candidatePath; $TargetWasResolved = TRUE; } }
  else {
    $candidatePath = realpath($InstLoc.$DirSep.'Resources'.$DirSep.'config.php');
    if ($candidatePath === FALSE) errorEntry('This installation has no configuration file to operate on!', 32009, FALSE);
    else { $ConfigTarget = $candidatePath; $TargetWasResolved = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $candidatePath, $suppliedPath);
  return array($TargetWasResolved, $ConfigTarget); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to confirm a configuration file is one of ours before touching it.
// / Accepts the detected sections.
// / Returns an authenticity boolean & the version the file reports, in that order.
// / Authenticity is the presence of $ConfigVersion & nothing else. The version is READ &
// / REPORTED, never compared here. A file that is out of date is still a file this
// / utility can help with, & refusing to open it would strand the administrator who most
// / needs it.
function verifyConfigAuthenticity($detectedSections) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ConfigIsAuthentic = FALSE;
  $DetectedConfigVersion = '';
  $sectionName = '';
  $sectionRecord = array();
  foreach ($detectedSections as $sectionName => $sectionRecord) {
    if (isset($sectionRecord['Variables']['ConfigVersion'])) {
      $DetectedConfigVersion = trim((string)$sectionRecord['Variables']['ConfigVersion']['Value'], " '\"");
      $ConfigIsAuthentic = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $sectionName, $sectionRecord, $detectedSections);
  return array($ConfigIsAuthentic, $DetectedConfigVersion); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write a complete configuration file from the model.
// / Accepts the path to write. Returns whether it was written & how many settings it holds.
// /
// / THE MODEL IS THE CONFIGURATION & THIS WRITES IT OUT. An installation that has lost its
// / config.php, or has never had one, gets a working default here rather than a download.
// /
// / It is deliberately php rather than a build script in another language. A configuration
// / that can only be produced by a tool an operator does not have is a configuration they
// / cannot recover. Deleting config.php & running --config --repair has to be enough.
// /
// / IT WRITES DEFAULTS & KNOWS NOTHING OF ANY EXISTING FILE. Merging what an administrator
// / had set is the caller's job & is what repair already does for a file that exists. This
// / is only reached when there is nothing to merge with.
// /
// / A setting of type expression is emitted as SOURCE rather than as a value, because
// / $LogDir is $ConvertLoc with a suffix & flattening that into a fixed path would silently
// / decouple two settings an administrator expects to move together.
function generateConfigFile($configPath) {
  // / Set variables.
  global $EngineConfigTemplate, $InstLoc, $DirSep, $EnableMemoryProtection;
  $FileWasWritten = FALSE;
  $SettingsWritten = 0;
  $templatePath = $templateText = '';
  $markerPosition = FALSE;
  $matchedSettings = array();
  // / THE TEMPLATE IS COPIED, not rebuilt from a description of itself.
  // / It is already a real config.php at defaults, with every section, every setting & every
  // / line of prose in the shape a live configuration has. Rebuilding that from a model was
  // / a translation between two representations of the same thing, & a translation is a
  // / place the two can disagree.
  // / The application names the file in $EngineConfigTemplate. An application that names
  // / none cannot have a configuration written for it, which is reported rather than
  // / guessed at.
  $templatePath = (isset($EngineConfigTemplate) && trim((string)$EngineConfigTemplate) !== '') ? trim((string)$EngineConfigTemplate) : '';
  if ($templatePath === '') {
    errorEntry('This application names no configuration template, so none can be written!', 28003, FALSE);
    $templateText = ''; }
  else {
    if (strpos($templatePath, $DirSep) !== 0) $templatePath = $InstLoc.$DirSep.'Resources'.$DirSep.$templatePath;
    $templateText = (string)@file_get_contents($templatePath);
    if ($templateText === '') errorEntry('The configuration template at '.$templatePath.' is missing or unreadable!', 28004, FALSE);
    else {
      // / The template explains ITSELF at the top & a generated configuration is not a
      // / template, so that explanation is not copied. Everything from the marker down is.
      // / A template with no marker is copied whole, which is what an application that has
      // / not adopted the marker gets & is a reasonable answer.
      $markerPosition = strpos($templateText, '// / Copy from here down.');
      if ($markerPosition !== FALSE) $templateText = '<?php'.PHP_EOL.substr($templateText, $markerPosition + strlen('// / Copy from here down.') + 1); } }
  if ($templateText !== '') {
    preg_match_all('/^\\$([A-Za-z_][A-Za-z0-9_]*)\\s*=/m', $templateText, $matchedSettings);
    $SettingsWritten = isset($matchedSettings[1]) ? count($matchedSettings[1]) : 0;
    // / The same staged write every configuration change uses, so a template that would not
    // / parse never reaches the path an installation loads.
    $FileWasWritten = writeConfigFile($configPath, $templateText);
    if ($FileWasWritten) logEntry('A configuration holding '.$SettingsWritten.' setting(s) was written from '.$templatePath.'.');
    else errorEntry('A configuration file could not be written to '.$configPath.'!', 28002, FALSE); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $markerPosition, $templatePath, $templateText, $matchedSettings, $configPath);
  return array($FileWasWritten, $SettingsWritten); }
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to run the configuration utility.
// / Accepts the supplied path, the mode, the target of that mode & a confirmation boolean.
// / Returns TRUE when the utility completed without refusing.
// / Modes are interactive, view, reset-all, reset-section, reset-variable, repair & backup.
// / EVERY MODE EXCEPT BACKUP REQUIRES ROOT. A backup is permitted to any account whose
// / destination it can write, because taking a copy damages nothing.
// / NOTHING IS WRITTEN IF ANY SECTION FAILED VALIDATION. A file that cannot be parsed
// / correctly cannot be edited safely.
function runConfigUtility($suppliedPath, $utilityMode, $modeTarget, $operatorConfirmed) {
  // / Set variables.
  global $RunningAsRoot, $CurrentUser, $Lol, $RequiredConfigVersion, $EnableMemoryProtection, $InstLoc, $DirSep;
  $generatedOk = FALSE;
  $generatedCount = 0;
  $UtilityCompleted = FALSE;
  $configTarget = $detectedConfigVersion = $sectionName = $variableName = $operatorChoice = '';
  $configModel = $detectedSections = $validationFindings = $dependencyFindings = $pendingChanges = array();
  $sectionModel = $variableModel = $finding = array();
  $targetWasResolved = $parseSucceeded = $configIsAuthentic = $sectionsAreTrusted = $configIsCoherent = FALSE;
  $valueIsValid = $changesWereApplied = FALSE;
  $formattedValue = '';
  $changesApplied = 0;
  list ($targetWasResolved, $configTarget) = resolveConfigTarget($suppliedPath);
  // / A MISSING FILE IS EVERY SETTING MISSING, so repair is the mode that answers it.
  // / An installation that has lost its configuration, or has never had one, gets a
  // / working default here. Any other mode has nothing to work on & says so.
  // / This is why the generator is php beside the model rather than a build script. An
  // / operator who has deleted config.php has to be able to get it back with the tool
  // / they already have.
  if (!$targetWasResolved && $utilityMode === 'repair' && $RunningAsRoot) {
    print($Lol.'No configuration file was found. Writing a default one from the template.'.$Lol);
    list ($generatedOk, $generatedCount) = generateConfigFile($suppliedPath !== '' ? $suppliedPath : $InstLoc.$DirSep.'Resources'.$DirSep.'config.php');
    if ($generatedOk) {
      $UtilityCompleted = TRUE;
      print($Lol.'Wrote '.$generatedCount.' setting(s). Every value is a default & is yours to change.'.$Lol.$Lol); }
    else print($Lol.'A configuration file could not be written. See the log.'.$Lol.$Lol); }
  else if (!$targetWasResolved) print($Lol.'No configuration file could be resolved. Nothing was done.'.$Lol.$Lol);
  // / A backup damages nothing, so it is the one mode a standard user may run.
  else if ($utilityMode === 'backup') {
    if (backupConfigFile($configTarget, $modeTarget)) {
      $UtilityCompleted = TRUE;
      print($Lol.'Backed up '.$configTarget.$Lol.'        to '.$modeTarget.$Lol.$Lol); }
    else print($Lol.'The backup did not complete. See the log.'.$Lol.$Lol); }
  else if (!$RunningAsRoot) {
    print($Lol.'The configuration utility must be run as root.'.$Lol);
    print('You are running as '.($CurrentUser === '' ? 'an unidentified user' : $CurrentUser).'.'.$Lol);
    print($Lol.'  sudo php convertCore.php --config'.$Lol);
    print($Lol.'A backup may be taken by any account that can write the destination.'.$Lol);
    print('  php convertCore.php --config --backup=/tmp/config.php.bak'.$Lol.$Lol); }
  else {
    $configModel = collectConfigModel();
    list ($parseSucceeded, $detectedSections) = parseConfigFile($configTarget);
    if (!$parseSucceeded) print($Lol.'The configuration file could not be parsed. Nothing was done.'.$Lol.$Lol);
    else {
      list ($configIsAuthentic, $detectedConfigVersion) = verifyConfigAuthenticity($detectedSections);
      if (!$configIsAuthentic) print($Lol.$configTarget.$Lol.'does not declare a $ConfigVersion & is not an HRConvert2 configuration file.'.$Lol.'Nothing was done.'.$Lol.$Lol);
      else {
        print($Lol.'HRConvert2 Configuration Utility'.$Lol);
        print('File      '.$configTarget.$Lol);
        print('Reports   v'.ltrim($detectedConfigVersion, 'vV').$Lol);
        print('Core needs v'.ltrim((string)$RequiredConfigVersion, 'vV').$Lol);
        // / The version is reported & never enforced. An out of date file is exactly the
        // / file an administrator has opened this utility to fix.
        if (ltrim($detectedConfigVersion, 'vV') !== ltrim((string)$RequiredConfigVersion, 'vV')) print('WARNING   This configuration does not match what the core requires.'.$Lol);
        list ($sectionsAreTrusted, $validationFindings) = validateConfigSections($configModel, $detectedSections);
        print($Lol.'Sections'.$Lol);
        foreach ($validationFindings as $finding) print('  '.str_pad($finding['Status'], 13).str_pad($finding['Section'], 36).$finding['Detail'].$Lol);
        list ($configIsCoherent, $dependencyFindings) = validateConfigDependencies($configModel, $detectedSections);
        if (!$configIsCoherent) {
          print($Lol.'Settings with no effect'.$Lol);
          foreach ($dependencyFindings as $finding) print('  '.$finding['Detail'].$Lol); }
        if (!$sectionsAreTrusted) {
          print($Lol.'ONE OR MORE SECTIONS FAILED VALIDATION.'.$Lol);
          print('This file does not have the shape this utility expects, so it will not be'.$Lol);
          print('written to. Nothing has been changed. Edit it by hand, or restore a backup.'.$Lol.$Lol); }
        else {
          // / Every mode below writes. Each one collects a change set & applies it once.
          if ($utilityMode === 'view') {
            foreach ($configModel as $sectionName => $sectionModel) {
              if (isset($detectedSections[$sectionName])) showConfigSection($sectionName, $sectionModel, $detectedSections[$sectionName]['Variables']); }
            print($Lol);
            $UtilityCompleted = TRUE; }
          else if ($utilityMode === 'reset-all' or $utilityMode === 'reset-section' or $utilityMode === 'repair') {
            foreach ($configModel as $sectionName => $sectionModel) {
              if (!$sectionModel['Writable'] or !isset($detectedSections[$sectionName])) continue;
              if ($utilityMode === 'reset-section' && strtolower($sectionName) !== strtolower((string)$modeTarget)) continue;
              foreach ($sectionModel['Variables'] as $variableName => $variableModel) {
                if ((string)$variableModel['Default'] === '') continue;
                // / A repair only touches what is absent or unreadable. A reset touches everything.
                // / A REPAIR takes what is ABSENT. A RESET takes what is PRESENT.
                // / Both lines below used to skip during a repair, one for a setting that
                // / was there & one for a setting that was not. A repair therefore queued
                // / nothing & reported success, & a self healing configuration healed
                // / nothing at all.
                // / Adding what is missing is the whole point of the mode. It is what lets
                // / an operator or an automated tool normalize a coherent file across a
                // / release that added a required setting, without touching one existing
                // / value.
                // / Presence is looked for across the WHOLE FILE rather than in the section
                // / the model happens to name. A setting an administrator moved, or one filed
                // / under a different heading by an earlier release, is present either way &
                // / appending a second copy of it is not a repair.
                list ($foundSection, $foundRecord) = findConfigVariable($detectedSections, $variableName);
                if ($utilityMode === 'repair' && $foundSection !== '') continue;
                if ($utilityMode !== 'repair' && $foundSection === '') continue;
                list ($valueIsValid, $formattedValue) = formatConfigValue($variableModel['Type'], $variableModel['Default']);
                // / A repair reaches here for a setting the file does NOT contain, so there
                // / is no record to attach & an empty one is passed instead.
                // / Reading it unconditionally raised an undefined key warning naming the
                // / very setting that was missing, which read like a fault in the utility
                // / rather than the thing it had correctly found.
                // / applyConfigChanges treats a record with no StartLine as one to ADD.
                if ($valueIsValid) $pendingChanges[$variableName] = array('Section' => $sectionName, 'Value' => $formattedValue, 'Record' => $foundRecord); } }
            if ($utilityMode === 'reset-section' && empty($pendingChanges)) print($Lol.'No writable section named '.$modeTarget.' was found. Nothing was done.'.$Lol.$Lol);
            else if (!$operatorConfirmed) {
              // / A repair ADDS & a reset REWRITES, & the prompt says which. An operator asked to
              // / confirm a rewrite of a setting that is not in the file has been told the wrong
              // / thing about what is going to happen to their configuration.
              // / The names are listed, because a count on its own does not let anybody decide.
              print($Lol.'This will '.($utilityMode === 'repair' ? 'ADD ' : 'rewrite ').count($pendingChanges).' setting(s) in '.$configTarget.'.'.$Lol);
              foreach ($pendingChanges as $changeName => $changeRecord) print('  $'.$changeName.' = '.$changeRecord['Value'].$Lol);
              print($Lol.($utilityMode === 'repair' ? 'No existing value is changed. A backup is written first.' : 'A backup is written first.').$Lol);
              $operatorChoice = promptOperator('Type YES to continue. Anything else cancels. ');
              if ($operatorChoice !== 'YES') print($Lol.'Cancelled. Nothing was written.'.$Lol.$Lol);
              else { list ($changesWereApplied, $changesApplied) = applyConfigChanges($configTarget, $detectedSections, $pendingChanges); $UtilityCompleted = $changesWereApplied; } }
            else { list ($changesWereApplied, $changesApplied) = applyConfigChanges($configTarget, $detectedSections, $pendingChanges); $UtilityCompleted = $changesWereApplied; } }
          else if ($utilityMode === 'reset-variable') {
            $variableName = ltrim(trim((string)$modeTarget), '$');
            foreach ($configModel as $sectionName => $sectionModel) {
              if (!$sectionModel['Writable'] or !isset($sectionModel['Variables'][$variableName]) or !isset($detectedSections[$sectionName]['Variables'][$variableName])) continue;
              if ((string)$sectionModel['Variables'][$variableName]['Default'] === '') continue;
              list ($valueIsValid, $formattedValue) = formatConfigValue($sectionModel['Variables'][$variableName]['Type'], $sectionModel['Variables'][$variableName]['Default']);
              if ($valueIsValid) $pendingChanges[$variableName] = array('Section' => $sectionName, 'Value' => $formattedValue, 'Record' => $detectedSections[$sectionName]['Variables'][$variableName]); }
            if (empty($pendingChanges)) print($Lol.'$'.$variableName.' is unknown to this utility, has no default, or is not in this file.'.$Lol.'Nothing was done.'.$Lol.$Lol);
            else { list ($changesWereApplied, $changesApplied) = applyConfigChanges($configTarget, $detectedSections, $pendingChanges); $UtilityCompleted = $changesWereApplied; } }
          else {
            // / Interactive. Walk every section, collect changes, apply once at the end.
            print($Lol.'Walk each section & queue changes. Nothing is written until the end.'.$Lol);
            foreach ($configModel as $sectionName => $sectionModel) {
              if (!isset($detectedSections[$sectionName])) continue;
              updateConfigSection($sectionName, $sectionModel, $detectedSections[$sectionName]['Variables'], $pendingChanges); }
            if (empty($pendingChanges)) { print($Lol.'No changes were queued. Nothing was written.'.$Lol.$Lol); $UtilityCompleted = TRUE; }
            else {
              print($Lol.count($pendingChanges).' change(s) are queued.'.$Lol);
              foreach ($pendingChanges as $variableName => $sectionModel) print('  $'.str_pad($variableName, 36).$sectionModel['Value'].$Lol);
              $operatorChoice = promptOperator($Lol.'Type YES to write them. Anything else cancels. ');
              if ($operatorChoice !== 'YES') print($Lol.'Cancelled. Nothing was written.'.$Lol.$Lol);
              else { list ($changesWereApplied, $changesApplied) = applyConfigChanges($configTarget, $detectedSections, $pendingChanges); $UtilityCompleted = $changesWereApplied; } } } } } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $generatedOk, $generatedCount, $foundSection, $foundRecord, $changeName, $changeRecord, $configTarget, $detectedConfigVersion, $sectionName, $variableName, $operatorChoice, $configModel, $detectedSections, $validationFindings, $dependencyFindings, $pendingChanges, $sectionModel, $variableModel, $finding, $targetWasResolved, $parseSucceeded, $configIsAuthentic, $sectionsAreTrusted, $configIsCoherent, $valueIsValid, $changesWereApplied, $formattedValue, $changesApplied, $suppliedPath, $utilityMode, $modeTarget, $operatorConfirmed);
  return $UtilityCompleted; }
// / -----------------------------------------------------------------------------------
// / A function to find a setting anywhere in a configuration file.
// / Accepts the detected sections & the setting name. Returns the section it was found in
// / & its record, in that order. The section is an empty string when it is not there.
// /
// / Presence is a property of the FILE rather than of a section.
// / A repair used to ask whether a setting was in the section the MODEL puts it in. An
// / administrator who moved a setting, or a release that filed one under a different
// / heading, therefore looked absent & a second copy was appended.
// / A duplicate assignment is not harmless. PHP takes the last one, so the file says one
// / thing at the top & another at the bottom & the reader has to know which wins.
function findConfigVariable($detectedSections, $variableName) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FoundSection = '';
  $FoundRecord = array();
  $sectionName = '';
  $sectionRecord = array();
  foreach ($detectedSections as $sectionName => $sectionRecord) {
    if (!isset($sectionRecord['Variables'][$variableName])) continue;
    $FoundSection = $sectionName;
    $FoundRecord = $sectionRecord['Variables'][$variableName];
    break; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $sectionName, $sectionRecord, $detectedSections, $variableName);
  return array($FoundSection, $FoundRecord); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to create the data locations an installation cannot start without.
// / Accepts no arguments & must be run as root.
// / Returns a success boolean & the number of directories created, in that order.
// / verifyRequiredDirs REFUSES to create the data location itself & raises error 1000 when
// / it is absent. That is correct at runtime, because a data location that vanished is a
// / mount that failed & creating an empty one on top of it would hide the fault. At
// / install time there is nothing to hide, so this creates it once & then gets out of the
// / way. Everything below the data location is created by verifyRequiredDirs as normal.
function prepareDataLocations() {
  // / Set variables.
  global $ConvertLoc, $PrimaryConvertLoc, $AdditionalConvertLocs, $LogDir, $BackupLoc, $HomeLoc, $ManagerSocketDir, $ConvertTemp, $ApacheUser, $RunningAsRoot, $Lol, $EnableMemoryProtection;
  $LocationsAreReady = TRUE;
  $LocationsCreated = 0;
  $requiredLocations = $additionalEntry = array();
  $requiredLocation = $entryPath = '';
  $dataPolicyIsValid = $dataIsProtected = FALSE;
  $dataPolicyStatus = $exposureStatus = $exposureDetail = '';
  if (!$RunningAsRoot) errorEntry('Data locations can only be created while running as root!', 32010, FALSE);
  else {
    // / The primary data location, the log directory, the backup location & the home
    // / directory the converters need. Everything else hangs off these.
    $requiredLocations[] = (string)$PrimaryConvertLoc !== '' ? (string)$PrimaryConvertLoc : (string)$ConvertLoc;
    $requiredLocations[] = (string)$LogDir;
    $requiredLocations[] = (string)$HomeLoc;
    if ((string)$BackupLoc !== '') $requiredLocations[] = (string)$BackupLoc;
    // / Every additional data location declared in config.php.
    if (is_array($AdditionalConvertLocs)) {
      foreach ($AdditionalConvertLocs as $additionalEntry) {
        if (is_array($additionalEntry) && isset($additionalEntry[0]) && trim((string)$additionalEntry[0]) !== '') $requiredLocations[] = trim((string)$additionalEntry[0]); } }
    foreach ($requiredLocations as $requiredLocation) {
      if ($requiredLocation === '') continue;
      if (is_dir($requiredLocation)) print('  '.str_pad('Present', 12).$requiredLocation.$Lol);
      else {
        @mkdir($requiredLocation, 0755, TRUE);
        if (!is_dir($requiredLocation)) {
          $LocationsAreReady = FALSE;
          print('  '.str_pad('FAILED', 12).$requiredLocation.$Lol);
          errorEntry('A required data location could not be created at '.$requiredLocation.'!', 32011, FALSE); }
        else {
          $LocationsCreated++;
          print('  '.str_pad('Created', 12).$requiredLocation.$Lol); } }
      // / Hand every location to the account that has to write into it, whether this run
      // / created it or found it. A location created by hand as root is the most common
      // / reason a fresh installation cannot write its first session.
      if (is_dir($requiredLocation)) {
        @chown($requiredLocation, $ApacheUser);
        @chgrp($requiredLocation, $ApacheUser);
        @chmod($requiredLocation, 0755); } }
    // / The socket directory is never world readable, whatever the sweep above set.
    if ((string)$ManagerSocketDir !== '' && !is_dir($ManagerSocketDir)) @mkdir($ManagerSocketDir, 0700, TRUE);
    if (is_dir($ManagerSocketDir)) {
      @chown($ManagerSocketDir, $ApacheUser);
      @chgrp($ManagerSocketDir, $ApacheUser);
      @chmod($ManagerSocketDir, 0700); }
    // / The DATA tree is the one location that lives inside the web root.
    // / Every location above is outside it & is reachable only by this application. $ConvertTemp
    // / is $InstLoc/DATA & is served straight to browsers, because that is how a user fetches a
    // / converted file. It therefore needs a protection file that none of the others do, & it
    // / has to exist before the first upload rather than after somebody notices.
    if ((string)$ConvertTemp !== '') {
      if (!is_dir($ConvertTemp)) {
        @mkdir($ConvertTemp, 0755, TRUE);
        if (is_dir($ConvertTemp)) {
          $LocationsCreated++;
          print('  '.str_pad('Created', 12).$ConvertTemp.$Lol); } }
      else print('  '.str_pad('Present', 12).$ConvertTemp.$Lol);
      if (!is_dir($ConvertTemp)) {
        $LocationsAreReady = FALSE;
        print('  '.str_pad('FAILED', 12).$ConvertTemp.$Lol);
        errorEntry('The web served DATA location could not be created at '.$ConvertTemp.'!', 32014, FALSE); }
      else {
        @chown($ConvertTemp, $ApacheUser);
        @chgrp($ConvertTemp, $ApacheUser);
        @chmod($ConvertTemp, 0755);
        print($Lol.'DATA directory protection.'.$Lol);
        // / What this application says about its own data exposure, printed as given.
        // / Whether a data tree is reachable by a web server is a question about an
        // / application that HAS one. The rows below name Apache, nginx & an uploaded SVG,
        // / none of which an engine can know about, so the application produces them & this
        // / prints them without understanding any of it.
        $policyFindings = collectDataPolicyFindings();
        foreach ($policyFindings as $policyFinding) {
          if (isset($policyFinding['Label'])) print('  '.str_pad((string)$policyFinding['Label'], 22).str_pad((string)$policyFinding['Status'], 14).(string)$policyFinding['Detail'].$Lol);
          else print((string)$policyFinding['Text'].$Lol); } } }
    if ($LocationsAreReady) logEntry('Prepared '.count($requiredLocations).' data location(s). '.$LocationsCreated.' created.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $policyFindings, $policyFinding, $requiredLocations, $additionalEntry, $requiredLocation, $entryPath, $dataPolicyIsValid, $dataPolicyStatus, $dataIsProtected, $exposureStatus, $exposureDetail);
  return array($LocationsAreReady, $LocationsCreated); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to build a service unit from the live configuration.
// / Accepts no arguments.
// / Returns the complete unit file contents.
// / Every path in the unit is read from this installation, never typed.
// / systemd cannot read config.php, so a unit written by hand duplicates the data location,
// / the installation path & the web server user, & nothing checks that the copies still
// / agree. Generating it here means moving --Convert Location-- moves the unit with it.
function generateListenerService() {
  // / Set variables.
  global $InstLoc, $PrimaryConvertLoc, $ConvertLoc, $ApacheUser, $HRConvertVersion, $DirSep, $EnableMemoryProtection;
  $ServiceContents = '';
  $dataLocation = $corePath = $documentationPath = $webServerUnit = '';
  $dataLocation = (string)$PrimaryConvertLoc !== '' ? (string)$PrimaryConvertLoc : (string)$ConvertLoc;
  $corePath = $InstLoc.$DirSep.'convertCore.php';
  $documentationPath = $InstLoc.$DirSep.'Documentation'.$DirSep.'ABOUT_RESOURCE_RATE_LIMITING.txt';
  // / Order after whichever web server is actually installed. Both are Wants rather than
  // / Requires, because HRConvert2 fails open & a listener is useful without a web server.
  $webServerUnit = 'apache2.service';
  if (locateDependency('apache2') === '' && locateDependency('httpd') !== '') $webServerUnit = 'httpd.service';
  $ServiceContents = '# / Written by HRConvert2 v'.ltrim((string)$HRConvertVersion, 'vV').'. Do not edit by hand.'.PHP_EOL
    .'# / Every path here was read from this installation. Re-run the following to rewrite it'.PHP_EOL
    .'# / after changing --Convert Location-- or moving the installation.'.PHP_EOL
    .'# /   sudo php '.$corePath.' --setup --install-service'.PHP_EOL
    .'# /'.PHP_EOL
    .'# / --run-core-manager does NOT fork, so systemd watches the real Core Manager process,'.PHP_EOL
    .'# / restarts it when it dies & reports its true state. Core Manager supervises its three'.PHP_EOL
    .'# / subordinates itself, so one restart restores a whole listener.'.PHP_EOL
    .'# /'.PHP_EOL
    .'# / Once enabled this unit OWNS the listener. The -l argument detects it & starts the'.PHP_EOL
    .'# / listener through systemctl rather than beside it, so there is only ever one.'.PHP_EOL
    .PHP_EOL
    .'[Unit]'.PHP_EOL
    .'Description=HRConvert2 Resource Listener'.PHP_EOL
    .'Documentation=file:'.$documentationPath.PHP_EOL
    .'# / The data location must be mounted before a socket can be bound inside it.'.PHP_EOL
    .'RequiresMountsFor='.$dataLocation.PHP_EOL
    .'After=network.target local-fs.target '.$webServerUnit.PHP_EOL
    .'Wants='.$webServerUnit.PHP_EOL
    .PHP_EOL
    .'[Service]'.PHP_EOL
    .'# / The process stays in the foreground, so systemd owns the lifecycle properly.'.PHP_EOL
    .'Type=simple'.PHP_EOL
    .'# / Every socket must be owned by the account the workers run as, or no conversion can'.PHP_EOL
    .'# / reach the listener. Running this unit as root breaks it.'.PHP_EOL
    .'User='.$ApacheUser.PHP_EOL
    .'Group='.$ApacheUser.PHP_EOL
    .'ExecStart='.locateDependency('php').' '.$corePath.' --run-core-manager'.PHP_EOL
    .'# / A listener that dies takes resource awareness with it & every conversion then runs'.PHP_EOL
    .'# / unmetered. Bring it back rather than leaving the server unprotected.'.PHP_EOL
    .'Restart=on-failure'.PHP_EOL
    .'RestartSec=10'.PHP_EOL
    .'StartLimitBurst=5'.PHP_EOL
    .'StartLimitIntervalSec=120'.PHP_EOL
    .'# / Core Manager stops its subordinates when told to, so give it room to unwind.'.PHP_EOL
    .'KillMode=mixed'.PHP_EOL
    .'TimeoutStopSec=30'.PHP_EOL
    .'ProtectSystem=full'.PHP_EOL
    .'ProtectHome=yes'.PHP_EOL
    .'NoNewPrivileges=yes'.PHP_EOL
    .'# / PrivateTmp must stay off. A conversion sandbox binds a real path into its namespace.'.PHP_EOL
    .'PrivateTmp=no'.PHP_EOL
    .PHP_EOL
    .'[Install]'.PHP_EOL
    .'WantedBy=multi-user.target'.PHP_EOL;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dataLocation, $corePath, $documentationPath, $webServerUnit);
  return $ServiceContents; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to build the Environment Manager timer & the service the timer starts.
// / Accepts nothing. Returns the service text & the timer text, in that order.
// /
// / TWO units, because systemd separates what runs from when it runs. The service is
// / Type=oneshot & is never enabled on its own. The timer is what an administrator enables
// / & it is the only thing that starts the service.
// /
// / This unit runs as ROOT & the listener unit deliberately does not. That is not an
// / inconsistency. The listener owns sockets that the web server account must reach, & root
// / would break it. This one reads AppArmor policies & kernel settings that only root can
// / read, & anything less cannot do the job at all.
// / Because it is root it is confined harder than the listener is. It gets a private tmp,
// / no new privileges, and a read only view of everything it does not need to write.
function generateEnvironmentTimer() {
  // / Set variables.
  global $InstLoc, $DirSep, $EnableMemoryProtection;
  $ServiceContents = $TimerContents = '';
  $corePath = $documentationPath = '';
  $corePath = $InstLoc.$DirSep.'convertCore.php';
  $documentationPath = $InstLoc.$DirSep.'Documentation'.$DirSep.'ABOUT_ENVIRONMENT_MANAGER.txt';
  $ServiceContents = '# / -----------------------------------------------------------------------------------'.PHP_EOL
    .'# / HRConvert2 Environment Manager. Written by --setup --install-service.'.PHP_EOL
    .'# / Do not edit this by hand. Re-run the command above to rewrite it.'.PHP_EOL
    .'# /'.PHP_EOL
    .'# / This runs as root & is started ONLY by hrconvert2-environment.timer. It looks at the'.PHP_EOL
    .'# / host, records what it found & exits. It opens no socket & accepts nothing from'.PHP_EOL
    .'# / anywhere, so there is no way to ask it to do something.'.PHP_EOL
    .'# /'.PHP_EOL
    .'# / It reports & does not repair, unless --Environment Manager May Repair-- is TRUE in'.PHP_EOL
    .'# / config.php. Read what it reports before turning that on.'.PHP_EOL
    .PHP_EOL
    .'[Unit]'.PHP_EOL
    .'Description=HRConvert2 Environment Manager'.PHP_EOL
    .'Documentation=file:'.$documentationPath.PHP_EOL
    .'After=local-fs.target'.PHP_EOL
    .PHP_EOL
    .'[Service]'.PHP_EOL
    .'# / It wakes, works & exits. Nothing stays resident.'.PHP_EOL
    .'Type=oneshot'.PHP_EOL
    .'# / Root is required & is the whole reason this unit exists apart from the listener.'.PHP_EOL
    .'User=root'.PHP_EOL
    .'Group=root'.PHP_EOL
    .'ExecStart='.locateDependency('php').' '.$corePath.' --run-environment-manager'.PHP_EOL
    .'# / A pass that hangs must not hold a root process open until the next one starts.'.PHP_EOL
    .'TimeoutStartSec=300'.PHP_EOL
    .'# / A root process gets confined harder than the listener, not less.'.PHP_EOL
    .'NoNewPrivileges=yes'.PHP_EOL
    .'PrivateTmp=yes'.PHP_EOL
    .'ProtectHome=yes'.PHP_EOL
    .'ProtectKernelTunables=no'.PHP_EOL
    .'RestrictSUIDSGID=yes'.PHP_EOL
    .'# / ProtectSystem is deliberately NOT full. A permitted repair writes to /etc, & a'.PHP_EOL
    .'# / read only /etc would make every repair fail in a way nobody could diagnose.'.PHP_EOL
    .'ProtectSystem=no'.PHP_EOL
    .PHP_EOL
    .'[Install]'.PHP_EOL
    .'# / Deliberately empty of WantedBy. The TIMER is what an administrator enables.'.PHP_EOL
    .'# / Enabling this service on its own would run one pass at boot & never again.'.PHP_EOL;
  $TimerContents = '# / -----------------------------------------------------------------------------------'.PHP_EOL
    .'# / HRConvert2 Environment Manager timer. Written by --setup --install-service.'.PHP_EOL
    .'# / Do not edit this by hand. Re-run the command above to rewrite it.'.PHP_EOL
    .'# /'.PHP_EOL
    .'# / Enable it with:  sudo systemctl enable --now hrconvert2-environment.timer'.PHP_EOL
    .'# / See what it last found with:  sudo systemctl status hrconvert2-environment'.PHP_EOL
    .PHP_EOL
    .'[Unit]'.PHP_EOL
    .'Description=HRConvert2 Environment Manager schedule'.PHP_EOL
    .'Documentation=file:'.$documentationPath.PHP_EOL
    .PHP_EOL
    .'[Timer]'.PHP_EOL
    .'# / Hourly is often enough to catch drift & rare enough that a root process is not a'.PHP_EOL
    .'# / constant presence. A distribution upgrade that replaces a policy is found within'.PHP_EOL
    .'# / the hour rather than at the next failed conversion.'.PHP_EOL
    .'OnCalendar=hourly'.PHP_EOL
    .'# / A host that was off runs the pass it missed rather than skipping it silently.'.PHP_EOL
    .'Persistent=true'.PHP_EOL
    .'# / Every installation on a fleet would otherwise wake on the same second.'.PHP_EOL
    .'RandomizedDelaySec=300'.PHP_EOL
    .'Unit=hrconvert2-environment.service'.PHP_EOL
    .PHP_EOL
    .'[Install]'.PHP_EOL
    .'WantedBy=timers.target'.PHP_EOL;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $corePath, $documentationPath);
  return array($ServiceContents, $TimerContents); }
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to install the listener service unit.
// / Accepts a boolean permitting the unit to be enabled & started.
// / Returns a success boolean & a status word, in that order.
// / The status is 'installed', 'unchanged', 'skipped' or 'failed'.
// / A unit that already matches what would be written is left completely alone, so this is
// / safe to run on every update & will not restart a healthy listener for no reason.
// / NOTHING IS INSTALLED WHEN RESOURCE AWARENESS IS DISABLED. A unit that starts a listener
// / the configuration does not want is a process running for no reason.
// / $startIfStopped decides whether a listener that is DOWN gets started.
// / It is separate from $enableService because those are two different questions.
// / Enabling writes the boot state. Starting it now overrides whatever state an
// / administrator has the service in RIGHT NOW, & one who stopped it has a reason.
// / Setup & -fp pass TRUE, because an installation with no listener running is not
// / installed. The Environment Manager passes FALSE, so a stopped listener stays stopped &
// / is reported rather than restarted underneath somebody who is looking at it.
function installListenerService($enableService, $startIfStopped = FALSE) {
  // / Set variables.
  global $EnableResourceAwareness, $RunningAsRoot, $Lol, $EnableMemoryProtection;
  $ServiceWasInstalled = $serviceSystemdUsable = FALSE;
  $ServiceStatus = 'skipped';
  $serviceSystemdReason = '';
  $unitPath = $unitContents = $existingContents = '';
  $commandOutput = array();
  $commandExitCode = 1;
  $bytesWritten = 0;
  $unitPath = '/etc/systemd/system/hrconvert2-listener.service';
  if (!$RunningAsRoot) {
    $ServiceStatus = 'failed';
    errorEntry('A service unit can only be installed while running as root!', 32012, FALSE); }
  else if (!$EnableResourceAwareness) print('  '.str_pad('Skipped', 12).'Resource awareness is disabled, so no listener service is needed.'.$Lol);
  // / A unit file written where systemd is not running is a file nothing reads, & worse,
  // / it makes the listener defer to a service manager that will never answer. Ask whether
  // / systemd is RUNNING rather than whether its tools happen to be installed.
  else if (!systemdIsUsable()[0]) {
    list ($serviceSystemdUsable, $serviceSystemdReason) = systemdIsUsable();
    print('  '.str_pad('Skipped', 12).$serviceSystemdReason.$Lol);
    print('  '.str_pad('', 12).'The listener is started directly instead. Nothing else is needed.'.$Lol); }
  else if (!is_dir('/etc/systemd/system')) print('  '.str_pad('Skipped', 12).'/etc/systemd/system does not exist.'.$Lol);
  else {
    $unitContents = generateListenerService();
    if (file_exists($unitPath)) $existingContents = (string)@file_get_contents($unitPath);
    // / An identical unit is left alone. Rewriting it would reload systemd & bounce a
    // / listener that is working perfectly well.
    if ($existingContents === $unitContents) {
      $ServiceWasInstalled = TRUE;
      $ServiceStatus = 'unchanged';
      print('  '.str_pad('Unchanged', 12).$unitPath.$Lol); }
    else {
      $bytesWritten = @file_put_contents($unitPath, $unitContents);
      if ($bytesWritten !== strlen($unitContents)) {
        $ServiceStatus = 'failed';
        errorEntry('The listener service unit could not be written to '.$unitPath.'!', 32013, FALSE); }
      else {
        @chmod($unitPath, 0644);
        // / These two run unsandboxed & convention 22 requires that to be said.
        // / systemctl changes the state of the host, which is the whole purpose of running
        // / it, & a namespace that isolated it from the host would leave it nothing to do.
        // / Neither command takes an argument that came from anywhere but this file.
        exec('systemctl daemon-reload 2>&1', $commandOutput, $commandExitCode);
        print('  '.str_pad(($existingContents === '' ? 'Installed' : 'Updated'), 12).$unitPath.$Lol);
        if ($enableService) {
          $commandOutput = array();
          // / --now is what starts it, & it is passed only when starting was asked for.
          // / enable alone writes the boot state & leaves a stopped service stopped.
          exec('systemctl enable '.($startIfStopped ? '--now ' : '').'hrconvert2-listener 2>&1', $commandOutput, $commandExitCode);
          if ($commandExitCode === 0) {
            $ServiceWasInstalled = TRUE;
            $ServiceStatus = 'installed';
            print('  '.str_pad('Enabled', 12).'hrconvert2-listener, starting at boot & running now'.$Lol);
            logEntry('The listener service unit was installed & enabled.'); }
          else {
            $ServiceStatus = 'failed';
            print('  '.str_pad('FAILED', 12).'systemctl enable --now hrconvert2-listener'.$Lol);
            print('  '.str_pad('', 12).implode($Lol.'  '.str_pad('', 12), $commandOutput).$Lol);
            warningEntry('The listener service unit was written but could not be enabled.'); } }
        else {
          $ServiceWasInstalled = TRUE;
          $ServiceStatus = 'installed';
          print('  '.str_pad('Ready', 12).'systemctl enable --now hrconvert2-listener'.$Lol); } } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $startIfStopped, $unitPath, $unitContents, $existingContents, $commandOutput, $commandExitCode, $bytesWritten, $serviceSystemdUsable, $serviceSystemdReason, $enableService);
  return array($ServiceWasInstalled, $ServiceStatus); }
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to install the Environment Manager timer & the service it starts.
// / Accepts whether the timer should be enabled. Returns whether it was installed & a
// / status word, in that order.
// /
// / The timer is written & NOT enabled unless asked. An administrator should read what a
// / pass reports before putting a root process on a schedule, & this is the last moment
// / anybody is looking.
// / systemd is required. A host without it gets nothing, & that is not a failure. The
// / manager still runs by hand, which is the documented alternative.
function installEnvironmentTimer($enableTimer) {
  // / Set variables.
  global $RunningAsRoot, $Lol, $EnableMemoryProtection;
  $TimerWasInstalled = FALSE;
  $TimerStatus = 'skipped';
  $timerSystemdUsable = FALSE;
  $timerSystemdReason = $servicePath = $timerPath = $serviceContents = $timerContents = '';
  $existingService = $existingTimer = '';
  $commandOutput = array();
  $commandExitCode = 1;
  $servicePath = '/etc/systemd/system/hrconvert2-environment.service';
  $timerPath = '/etc/systemd/system/hrconvert2-environment.timer';
  if (!$RunningAsRoot) {
    $TimerStatus = 'failed';
    errorEntry('The Environment Manager timer can only be installed while running as root!', 32014, FALSE); }
  else if (!systemdIsUsable()[0]) {
    list ($timerSystemdUsable, $timerSystemdReason) = systemdIsUsable();
    print('  '.str_pad('Skipped', 12).$timerSystemdReason.$Lol);
    print('  '.str_pad('', 12).'Run the manager by hand instead. See ABOUT_ENVIRONMENT_MANAGER.txt.'.$Lol); }
  else if (!is_dir('/etc/systemd/system')) print('  '.str_pad('Skipped', 12).'/etc/systemd/system does not exist.'.$Lol);
  else {
    list ($serviceContents, $timerContents) = generateEnvironmentTimer();
    if (file_exists($servicePath)) $existingService = (string)@file_get_contents($servicePath);
    if (file_exists($timerPath)) $existingTimer = (string)@file_get_contents($timerPath);
    if ($existingService === $serviceContents && $existingTimer === $timerContents) {
      $TimerWasInstalled = TRUE;
      $TimerStatus = 'unchanged';
      print('  '.str_pad('Unchanged', 12).$timerPath.$Lol); }
    else if (@file_put_contents($servicePath, $serviceContents) !== strlen($serviceContents) or @file_put_contents($timerPath, $timerContents) !== strlen($timerContents)) {
      $TimerStatus = 'failed';
      errorEntry('The Environment Manager timer could not be written to '.$timerPath.'!', 32015, FALSE); }
    else {
      @chmod($servicePath, 0644);
      @chmod($timerPath, 0644);
      exec('systemctl daemon-reload 2>&1', $commandOutput, $commandExitCode);
      $TimerWasInstalled = TRUE;
      $TimerStatus = 'installed';
      print('  '.str_pad(($existingTimer === '' ? 'Installed' : 'Updated'), 12).$timerPath.$Lol);
      logEntry('The Environment Manager timer was written to '.$timerPath.'.'); } }
  // / Enabling is separate & is never implied by writing the file.
  if ($TimerWasInstalled && $enableTimer) {
    $commandOutput = array();
    exec('systemctl enable --now hrconvert2-environment.timer 2>&1', $commandOutput, $commandExitCode);
    if ($commandExitCode === 0) {
      $TimerStatus = 'enabled';
      print('  '.str_pad('Enabled', 12).'hrconvert2-environment.timer, hourly from now'.$Lol);
      warningEntry('The Environment Manager timer was enabled. A root process now runs hourly.'); }
    else {
      $TimerStatus = 'failed';
      errorEntry('The Environment Manager timer was written & could not be enabled!', 32016, FALSE); } }
  else if ($TimerWasInstalled && $TimerStatus !== 'unchanged') {
    print('  '.str_pad('', 12).'The timer is NOT enabled. Read one pass before scheduling root.'.$Lol);
    print('  '.str_pad('', 12).'  sudo php convertCore.php --run-environment-manager'.$Lol);
    print('  '.str_pad('', 12).'  sudo systemctl enable --now hrconvert2-environment.timer'.$Lol); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $timerSystemdUsable, $timerSystemdReason, $servicePath, $timerPath, $serviceContents, $timerContents, $existingService, $existingTimer, $commandOutput, $commandExitCode, $enableTimer);
  return array($TimerWasInstalled, $TimerStatus); }
// / -----------------------------------------------------------------------------------
// / A function to perform a complete installation.
// / Accepts the dependency authorization token & a confirmation boolean, in that order.
// / Returns a completion boolean & the number of stages that succeeded, in that order.
// / Six stages, in the order an installation actually depends on.
// /   1  Dependencies. Nothing below can be verified until the tools exist.
// /   2  Data locations. A policy cannot be written into a directory that is not there.
// /   3  Permissions & policies. A converter cannot run until its policy permits it.
// /   4  Configuration. Written as a file the web server user has to read.
// /   5  Service unit. Generated from the configuration stage four just settled.
// /   6  Verification. Report what actually works rather than what was attempted.
// / A stage that fails stops the ones after it. Continuing past a failed dependency install
// / produces an installation that looks finished & is not.
// / WITH -y NOTHING PROMPTS. Stage four keeps whatever config.php already declares, which
// / for a fresh clone is the shipped defaults. Run --config afterwards to change them.
function runCompleteInstall($authorizationToken, $operatorConfirmed) {
  // / Set variables.
  global $InstLoc, $DirSep, $Lol, $EnableMemoryProtection;
  $InstallCompleted = FALSE;
  $StagesCompleted = 0;
  $stageSucceeded = $permissionsWereFixed = $locationsAreReady = $serviceWasInstalled = FALSE;
  $dependenciesReady = FALSE;
  // / A missing Dependency Core used to leave this function through an early return.
  // / Every other function in this component has one exit & this one now does too.
  $componentIsAvailable = TRUE;
  $stageCount = $pathsCorrected = $locationsCreated = $optionalProblems = 0;
  $operatorChoice = $serviceStatus = '';
  $dependencyFindings = array();
  print($Lol.'HRConvert2 Complete Installation'.$Lol);
  print('Seven stages. Dependencies, locations, permissions, configuration, service,'.$Lol);
  print('permissions again, checks.'.$Lol);
  // / Stage one lives in Dependency Core. The --setup gate loads it before this is reached,
  // / so this is insurance rather than a check that is expected to fire. A missing function
  // / is a fatal error with a stack trace, & a component that is simply absent should be
  // / reported the same way every other absent component is.
  if (!function_exists('installDepends')) {
    print($Lol.'The Dependency Core component is unavailable, so dependencies cannot be installed.'.$Lol);
    print('Install it, or run the stages by hand with -fp & --config.'.$Lol.$Lol);
    warningEntry('A complete installation was requested without the Dependency Core component.');
    $componentIsAvailable = FALSE; }
  if ($componentIsAvailable && !$operatorConfirmed) {
    print($Lol.'This installs packages, creates directories, writes system policy files,'.$Lol);
    print('rewrites config.php & installs a service unit.'.$Lol);
    $operatorChoice = promptOperator($Lol.'Type YES to continue. Anything else cancels. ');
    if ($operatorChoice !== 'YES') print($Lol.'Cancelled. Nothing was done.'.$Lol.$Lol); }
  if ($componentIsAvailable && ($operatorConfirmed or $operatorChoice === 'YES')) {
    // / Stage one. Dependencies, in manifest order.
    print($Lol.'Stage 1 of 7. Dependencies.'.$Lol);
    list ($stageSucceeded, $stageCount) = installDepends($authorizationToken, '', TRUE);
    if (!$stageSucceeded) print($Lol.'Dependency installation did not complete. Stopping here.'.$Lol.'Run --setup --check-depends to see what is missing.'.$Lol.$Lol);
    else {
      $StagesCompleted++;
      // / Stage two. The data locations everything below writes into.
      print($Lol.'Stage 2 of 7. Data locations.'.$Lol);
      list ($locationsAreReady, $locationsCreated) = prepareDataLocations();
      if (!$locationsAreReady) print($Lol.'One or more data locations could not be created. Stopping here.'.$Lol.$Lol);
      else {
        $StagesCompleted++;
        // / Stage three. Ownership, permissions & the policy files those packages need.
        print($Lol.'Stage 3 of 7. Permissions & policies.'.$Lol);
        list ($permissionsWereFixed, $pathsCorrected) = runApplicationRepair();
        if (!$permissionsWereFixed) print($Lol.'Permissions could not be corrected. Stopping here.'.$Lol.$Lol);
        else {
          $StagesCompleted++;
          // / Stage four. Configuration. Interactive unless the operator pre confirmed,
          // / because an unattended install must not stop for a question.
          print($Lol.'Stage 4 of 7. Configuration.'.$Lol);
          if ($operatorConfirmed) {
            $StagesCompleted++;
            print('  Keeping the configuration this installation already declares.'.$Lol);
            print('  Run the following to change anything.'.$Lol);
            print('    sudo php '.$InstLoc.$DirSep.'convertCore.php --config'.$Lol); }
          else if (runConfigUtility('', 'interactive', '', FALSE)) $StagesCompleted++;
          else print('  Configuration was cancelled. The shipped defaults remain in place.'.$Lol);
          // / Stage five. The service unit, generated from the configuration above.
          print($Lol.'Stage 5 of 7. Listener service.'.$Lol);
          // / TRUE, TRUE. An installation with no listener running is not installed yet.
          list ($serviceWasInstalled, $serviceStatus) = installListenerService(TRUE, TRUE);
          if ($serviceWasInstalled or $serviceStatus === 'skipped') $StagesCompleted++;
          // / The Environment Manager timer is written here & is deliberately NOT enabled.
          // / The listener is enabled because nothing works without it. This one puts a ROOT
          // / process on a schedule, & that is a decision an administrator makes after reading
          // / what one pass reports rather than something a setup wizard does on their behalf.
          print($Lol.'Environment Manager schedule.'.$Lol);
          installEnvironmentTimer(FALSE);
          // / Stage six. Report what actually works. An installation that says it finished
          // / & then refuses every conversion has helped nobody.
          // / Stage six. Permissions again, because stages four & five both wrote files.
          // / Stage three cannot be the last word on ownership when work happens after it.
          // / writeConfigFile() chowns & chmods the file it renames into place, so config.php
          // / itself is correct. backupConfigFile() uses a plain copy with neither, so a
          // / backup taken during stage four is owned by root & lands inside a path this
          // / pass manages. Left alone it stays root owned until somebody runs -fp by hand.
          // / The pass is idempotent, so running it twice costs a directory walk & nothing
          // / else, & it is cheap insurance against any future stage that writes something.
          // / It also re-tightens the two paths the whole manager security model rests on.
          // / The install secret is returned to 0600 & the socket directory to 0700, after
          // / the recursive sweep above them has finished.
          print($Lol.'Stage 6 of 7. Permissions, second pass.'.$Lol);
          list ($permissionsWereFixed, $pathsCorrected) = runApplicationRepair();
          if ($permissionsWereFixed) $StagesCompleted++;
          else warningEntry('The second permissions pass did not complete. Files written by the configuration & service stages may be owned by root.');
          print($Lol.'Stage 7 of 7. Verification.'.$Lol);
          list ($dependenciesReady, $dependencyFindings, $optionalProblems) = checkDepends('');
          showDependencyFindings($dependencyFindings);
          $StagesCompleted++;
          $InstallCompleted = TRUE;
          logEntry('A complete installation finished '.$StagesCompleted.' of 6 stages.');
          print($Lol.'Installation complete. '.$StagesCompleted.' of 6 stages finished.'.$Lol);
          if (!$dependenciesReady) print($Lol.'One or more REQUIRED dependencies are still missing. Conversions using them will refuse.'.$Lol);
          if ($optionalProblems > 0) print($Lol.$optionalProblems.' OPTIONAL dependenc(ies) are missing. Those subsystems will refuse until they are installed.'.$Lol);
          print($Lol.'  php '.$InstLoc.$DirSep.'convertCore.php -v          Confirm every dependency & subsystem.'.$Lol);
          print('  php '.$InstLoc.$DirSep.'convertCore.php --status    Confirm the listener.'.$Lol);
          print('  sudo php '.$InstLoc.$DirSep.'convertCore.php --config    Change any setting.'.$Lol);
          print($Lol.'Two things this cannot decide for you.'.$Lol);
          print('  The public URL, if share links are to work. Set it with --config.'.$Lol);
          print('  A web server virtual host, if the installation is not already served.'.$Lol.$Lol); } } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $componentIsAvailable, $stageSucceeded, $permissionsWereFixed, $locationsAreReady, $serviceWasInstalled, $dependenciesReady, $stageCount, $pathsCorrected, $locationsCreated, $optionalProblems, $operatorChoice, $serviceStatus, $dependencyFindings, $authorizationToken, $operatorConfirmed);
  return array($InstallCompleted, $StagesCompleted); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to install or repair the dependencies HRConvert2 converts with.
// / Accepts a confirmation boolean.
// / Returns TRUE when every dependency was installed.
// / NOT YET IMPLEMENTED. Declared now so the argument exists & refuses cleanly.
function runDependencyInstall($operatorConfirmed) {
  // / Set variables.
  global $Lol, $EnableMemoryProtection;
  $DependenciesInstalled = FALSE;
  print($Lol.'Dependency installation is not yet implemented in this release.'.$Lol);
  print('Run the -v argument to see which dependencies are missing or too old.'.$Lol.$Lol);
  warningEntry('A dependency installation was requested & is not implemented in this release.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $operatorConfirmed);
  return $DependenciesInstalled; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to reinstall an existing installation in place.
// / Accepts the target version & a confirmation boolean, in that order.
// / Returns TRUE when the reinstallation completed.
// / NOT YET IMPLEMENTED. This becomes updateApplication once that function & its five
// / helpers are moved out of convertCore.php, which is a migration of its own.
function runReinstallExisting($targetVersion, $operatorConfirmed) {
  // / Set variables.
  global $Lol, $EnableMemoryProtection;
  $ReinstallCompleted = FALSE;
  print($Lol.'Reinstallation is not yet implemented in this release.'.$Lol);
  print('Use the -u argument, which performs the same operation today.'.$Lol.$Lol);
  warningEntry('A reinstallation was requested & is not implemented in this release.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $targetVersion, $operatorConfirmed);
  return $ReinstallCompleted; }
// / -----------------------------------------------------------------------------------