<?php
// / -----------------------------------------------------------------------------------
// / Copyright information ...
// / HRConvert2, Copyright on 9/22/2026 by Justin Grimes, www.github.com/zelon88
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
// / v4.0.1.
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
// / The authoritative list of dependencies is Resources/Contract/depends.php & nothing else.
// / Run  php convertCore.php -v  to see what this installation has.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Refuse direct execution. This file is a component & has no standalone context.
// / This halt cannot use quickDie. Reaching this line means convertCore.php was never
// / loaded, so quickDie is not defined & calling it would replace a clear refusal with an
// / undefined function error.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! Engine-35000, A core component cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The component version. convertCore.php reads this without executing the file.
$SetupCoreVersion = 'v4.0.1';
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
  global $EnableMemoryProtection;
  $BackupSucceeded = FALSE;
  $backupDirectory = '';
  $backupDirectory = dirname($backupPath);
  if (!file_exists($configPath)) errorEntry('A configuration backup was requested but the source does not exist!', 32000, FALSE);
  else if (!is_dir($backupDirectory) or !is_writable($backupDirectory)) errorEntry('A configuration backup was requested but '.$backupDirectory.' is not writable by this account!', 32001, FALSE);
  else if (file_exists($backupPath)) errorEntry('A configuration backup was requested but '.$backupPath.' already exists. Nothing was overwritten!', 32002, FALSE);
  else {
    @copy($configPath, $backupPath);
    // / A backup keeps the owner of whoever took it, & one root takes is root's. Until v4.0.1
    // / it was handed to the web server account, as config.php itself was.
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
// / config.php is code root runs, so the file is root's & 0644. The web server account reads
// / it & cannot change it. Until v4.0.1 a configuration written as root was handed to the web
// / server account, which could then edit what root required on its next command.
function writeConfigFile($configPath, $configContents) {
  // / Set variables.
  global $EnableMemoryProtection;
  $WriteSucceeded = FALSE;
  $temporaryPath = $writeCommand = '';
  $lintOutput = array();
  $lintExitCode = 1;
  $bytesWritten = 0;
  $temporaryPath = $configPath.'.'.engineApplicationSlug().'-pending';
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
    $backupPath = $configPath.'.'.engineApplicationSlug().'-'.date('Y-m-d-His').'.bak';
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
// / MOVED FROM THE APPLICATION AT v3.9.3. Despite the name it knows nothing about a
// / conversion. It writes a systemd drop in & reloads the daemon, which is host setup &
// / is what Setup Core is for. The application decides WHETHER to ask for it.
// / A function to give the web server user its own systemd manager & cgroup controllers.
// / Accepts no arguments & must be run as root.
// / Returns a success boolean & the number of steps that succeeded, in that order.
// / This is the safe way to let an unprivileged account set resource limits. A user manager
// / governs only the cgroup subtree systemd delegated to it & cannot start a unit as any
// / other account, so nothing granted here can be turned into privilege.
// / The alternative, granting the web server user manage-units on the system bus, would let
// / that account start a transient service with User=root. Never do that on the account
// / which parses uploaded files.
// / Every step is idempotent & is safe to run again.
function enableConversionLimits() {
  // / Set variables.
  global $ApacheUser, $RunningAsRoot, $Lol, $EnableMemoryProtection;
  $LimitsWereEnabled = $limitsSystemdUsable = FALSE;
  $StepsCompleted = 0;
  $limitsSystemdReason = '';
  $dropInDirectory = $dropInFile = $dropInContents = '';
  $commandOutput = array();
  $commandExitCode = 1;
  $bytesWritten = 0;
  if (!$RunningAsRoot) errorEntry('Conversion limits can only be enabled while running as root!', 31011, FALSE);
  // / Ask whether systemd is RUNNING, not whether its tools are installed. A container
  // / ships the tools & runs something else as PID 1, & lingering there writes a file
  // / nothing will ever read.
  else if (!systemdIsUsable()[0]) {
    list ($limitsSystemdUsable, $limitsSystemdReason) = systemdIsUsable();
    print('  Skipped    '.$limitsSystemdReason.$Lol);
    print('             Per conversion limits fall back to scheduling priority.'.$Lol); }
  else {
    // / Lingering starts a user manager for the account at boot, with no login session.
    // / Without it there is no user bus for systemd-run --user to reach.
    exec('loginctl enable-linger '.escapeshellarg($ApacheUser).' 2>&1', $commandOutput, $commandExitCode);
    if ($commandExitCode !== 0) print('  FAILED     Could not enable lingering for '.$ApacheUser.'.'.$Lol);
    else {
      $StepsCompleted++;
      print('  Enabled    Lingering for '.$ApacheUser.$Lol); }
    // / Lingering is enabled & that is not the same as a limit being possible.
    // / A kernel that delegates no cgroup controllers cannot hold a per conversion limit no
    // / matter how the accounts are configured, & many NAS & appliance kernels are built
    // / that way deliberately.
    // / Saying so HERE, while an administrator is watching a repair run, is worth more than
    // / saying it in a log they read after a conversion behaved oddly.
    if (function_exists('cgroupDelegationIsAvailable') && !cgroupDelegationIsAvailable()) {
      print('  Note       This kernel delegates no cgroup controllers, so a per conversion'.$Lol);
      print('             limit cannot be held here whatever is configured. Conversions are'.$Lol);
      print('             still bounded by scheduling priority. This is a kernel decision'.$Lol);
      print('             & is normal on a NAS or appliance.'.$Lol);
      warningEntry('This kernel delegates no cgroup controllers, so per conversion limits fall back to scheduling priority.'); }
    // / A user manager is given the memory & pids controllers by default. The processor
    // / controller has to be delegated explicitly or CPUQuota is silently ignored.
    $dropInDirectory = '/etc/systemd/system/user@.service.d';
    $dropInFile = $dropInDirectory.'/'.engineApplicationSlug().'-delegate.conf';
    $dropInContents = '[Service]'.PHP_EOL.'Delegate=cpu cpuset io memory pids'.PHP_EOL;
    if (!is_dir($dropInDirectory)) @mkdir($dropInDirectory, 0755, TRUE);
    if (!is_dir($dropInDirectory)) print('  FAILED     Could not create '.$dropInDirectory.'.'.$Lol);
    else {
      $bytesWritten = @file_put_contents($dropInFile, $dropInContents);
      if ($bytesWritten !== strlen($dropInContents)) print('  FAILED     Could not write '.$dropInFile.'.'.$Lol);
      else {
        @chmod($dropInFile, 0644);
        $StepsCompleted++;
        print('  Wrote      '.$dropInFile.$Lol);
        exec('systemctl daemon-reload 2>&1', $commandOutput, $commandExitCode);
        if ($commandExitCode === 0) {
          $StepsCompleted++;
          print('  Reloaded   systemd unit configuration'.$Lol); }
        else print('  FAILED     Could not reload systemd. Reload it by hand.'.$Lol); } }
    if ($StepsCompleted >= 2) {
      $LimitsWereEnabled = TRUE;
      logEntry('Conversion limits were enabled for '.$ApacheUser.'. '.$StepsCompleted.' step(s) completed.');
      print('  Note       A running user manager must be restarted before delegation applies.'.$Lol);
      print('             systemctl restart user@$(id -u '.$ApacheUser.').service'.$Lol); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dropInDirectory, $dropInFile, $dropInContents, $commandOutput, $commandExitCode, $bytesWritten, $limitsSystemdUsable, $limitsSystemdReason);
  return array($LimitsWereEnabled, $StepsCompleted); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to give one installation tree to root, with folders 0755 & files 0644.
// / Accepts the tree & the paths inside it that hold another role. Returns a success boolean.
// / The tree is walked by GNU find, which never follows a link, never leaves the filesystem
// / it started on, & with -execdir hands each command a name relative to a folder it already
// / holds open, so a folder renamed during the walk cannot redirect a change. Root's group
// / is used, so the web server account reads the installation as everybody else does & can
// / write none of it. Nothing in an installation needs an execute bit, because every script
// / it ships is run through its interpreter.
// / The caller has already refused a tree that another account could replace.
// /
// / THE MODES ARE SET TOP DOWN, ONE NAME AT A TIME. The owner pass leaves every folder root's,
// / but a folder another account made writable to everybody stays writable until its mode is
// / set, & chmod follows a link. With one command per name, find sets a folder's mode before
// / it reads what is inside, so every name is reached through a folder only root can change.
// / Until v4.0.1 the commands were batched & ran after the walk had moved on.
// / A FILE WITH MORE THAN ONE NAME IS LEFT ALONE. Its other name may be anywhere on the same
// / filesystem, & changing the owner or the mode of one name changes every other. It is named
// / in a warning, & the trust check that ends -fp refuses the tree until it is removed.
function applyCodeRole($treePath, $prunedPaths) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RoleWasApplied = FALSE;
  $pruneExpression = $prunedPath = $findPrefix = '';
  $commandOutput = $linkedFiles = array();
  $ownerExitCode = $modeExitCode = $linkedExitCode = 1;
  // / A fixed PATH, because find refuses -execdir when PATH holds a relative entry.
  $findPrefix = 'PATH=/usr/sbin:/usr/bin:/sbin:/bin find '.escapeshellarg($treePath).' -xdev';
  // / -path reads a pattern, so a bracket, star or question mark in a real path is escaped.
  foreach ($prunedPaths as $prunedPath) $pruneExpression .= ' -path '.escapeshellarg(addcslashes((string)$prunedPath, '*?[]\\')).' -prune -o';
  exec($findPrefix.$pruneExpression.' -type f -links +1 -print 2>&1', $linkedFiles, $linkedExitCode);
  if (count($linkedFiles) > 0) warningEntry(count($linkedFiles).' file(s) in the installation at '.$treePath.' have more than one name & were left alone, because changing one name changes every other. Remove them & run -fp again. The first is '.$linkedFiles[0].'.');
  exec($findPrefix.$pruneExpression.' ! \( -type f -links +1 \) -execdir chown -h root:root {} + 2>&1', $commandOutput, $ownerExitCode);
  $commandOutput = array();
  exec($findPrefix.$pruneExpression.' -type d -execdir chmod 0755 {} \; -o -type f -links 1 -execdir chmod 0644 {} \; 2>&1', $commandOutput, $modeExitCode);
  if ($ownerExitCode === 0 && $modeExitCode === 0) $RoleWasApplied = TRUE;
  else warningEntry('The installation at '.$treePath.' could not be given to root completely. '.implode(' ', array_slice($commandOutput, 0, 3)));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $pruneExpression, $prunedPath, $findPrefix, $commandOutput, $linkedFiles, $ownerExitCode, $modeExitCode, $linkedExitCode, $treePath, $prunedPaths);
  return $RoleWasApplied; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to give one tree to root alone, with folders 0700 & files 0600.
// / Accepts the tree. Returns a success boolean.
// / The previous installation is kept here for an operator to restore by hand. Nothing else
// / needs to read it, so nothing else can. It is walked the same way as an installation, top
// / down & one name at a time, & a file with more than one name is left alone, for the reasons
// / given at applyCodeRole. The caller has already refused a tree another account could replace.
function applyRootOnlyRole($treePath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RoleWasApplied = FALSE;
  $findPrefix = '';
  $commandOutput = $linkedFiles = array();
  $ownerExitCode = $modeExitCode = $linkedExitCode = 1;
  $findPrefix = 'PATH=/usr/sbin:/usr/bin:/sbin:/bin find '.escapeshellarg($treePath).' -xdev';
  exec($findPrefix.' -type f -links +1 -print 2>&1', $linkedFiles, $linkedExitCode);
  if (count($linkedFiles) > 0) warningEntry(count($linkedFiles).' file(s) at '.$treePath.' have more than one name & were left alone, because changing one name changes every other. The first is '.$linkedFiles[0].'.');
  exec($findPrefix.' ! \( -type f -links +1 \) -execdir chown -h root:root {} + 2>&1', $commandOutput, $ownerExitCode);
  $commandOutput = array();
  exec($findPrefix.' -type d -execdir chmod 0700 {} \; -o -type f -links 1 -execdir chmod 0600 {} \; 2>&1', $commandOutput, $modeExitCode);
  if ($ownerExitCode === 0 && $modeExitCode === 0) $RoleWasApplied = TRUE;
  else warningEntry('The location at '.$treePath.' could not be given to root alone completely. '.implode(' ', array_slice($commandOutput, 0, 3)));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $findPrefix, $commandOutput, $linkedFiles, $ownerExitCode, $modeExitCode, $linkedExitCode, $treePath);
  return $RoleWasApplied; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to give one folder to the web server account, & only the folder.
// / Accepts the folder & the account. Returns a success boolean.
// / ROOT DOES NOT WALK IT & DOES NOT CHANGE ITS MODE BY NAME. The folder & everything in it is
// / the web server account's, which can swap any name in it at any moment. The owner is changed
// / by changeOwnerWithoutFollowing, which changes the name itself, never what a link points at,
// / & only when a hard link to a root file cannot be swapped in. The mode is set by the web
// / server account, through runAsAccount, so a link swapped in can only send the change to a
// / file that account could already change. Every session, upload & log inside is left exactly
// / as it is, which is what ABOUT_ENVIRONMENT_MANAGER.txt has always said a repair does.
function applyWebRole($folderPath, $ownerAccount) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RoleWasApplied = $ownerWasChanged = FALSE;
  $folderStatus = $commandOutput = array();
  $modeExitCode = 1;
  $ownerNotice = '';
  clearstatcache(TRUE, $folderPath);
  $folderStatus = @lstat($folderPath);
  if (!is_array($folderStatus) or ((int)$folderStatus['mode'] & 0170000) !== 0040000) warningEntry('The location at '.$folderPath.' is not a folder, & a link is never followed, so it was left alone.');
  else {
    list ($ownerWasChanged, $ownerNotice) = changeOwnerWithoutFollowing($folderPath, (string)$ownerAccount, (string)$ownerAccount);
    if (!$ownerWasChanged) warningEntry($ownerNotice);
    // / The account sets its own mode, so a folder that is still root's is reported here too.
    else list ($commandOutput, $modeExitCode) = runAsAccount('chmod 0700 -- '.escapeshellarg($folderPath).' 2>&1', (string)$ownerAccount);
    if ($modeExitCode === 0) $RoleWasApplied = TRUE;
    else if ($ownerWasChanged) warningEntry('The location at '.$folderPath.' could not be made private to '.$ownerAccount.'. '.implode(' ', $commandOutput)); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderStatus, $commandOutput, $modeExitCode, $ownerWasChanged, $ownerNotice, $folderPath, $ownerAccount);
  return $RoleWasApplied; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to check a folder other software shares, & correct only the folder itself.
// / Accepts the folder & the web server account. Returns TRUE when nothing else can change it.
// / The HRProprietary folder holds every HRProprietary application & whatever else an operator
// / keeps there. Until v4.0.1 -fp walked it, so every application in it was handed to the web
// / server account & made world readable & executable, whether it was this one or not.
// / It is never walked now. It is corrected only when the web server account could change it,
// / which only an earlier release or a hand made mistake causes, & which lets that account
// / rename this installation aside & put its own in its place. Only the folder's own owner &
// / write bits change, & only when the folder above it is one only root can change.
function applySharedRole($folderPath, $webAccount) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FolderIsSafe = FALSE;
  $folderStatus = $webRecord = array();
  $parentIsTrusted = $folderIsTrusted = $webCanWrite = $ownerWasChanged = $modeWasChanged = FALSE;
  $resolvedParent = $resolvedFolder = $trustNotice = $ownerNotice = '';
  clearstatcache(TRUE, $folderPath);
  $folderStatus = @lstat($folderPath);
  if (function_exists('posix_getpwnam')) $webRecord = @posix_getpwnam((string)$webAccount);
  if (!is_array($folderStatus) or ((int)$folderStatus['mode'] & 0170000) !== 0040000) warningEntry('The shared folder at '.$folderPath.' is not a folder & was left alone.');
  else {
    $webCanWrite = ((is_array($webRecord) && (int)$folderStatus['uid'] === (int)$webRecord['uid']) or ((int)$folderStatus['mode'] & 0022) !== 0);
    list ($parentIsTrusted, $resolvedParent, $trustNotice) = pathIsTrustedByRoot(dirname($folderPath));
    if ($webCanWrite && !$parentIsTrusted) warningEntry('The shared folder at '.$folderPath.' can be changed by another account & the folder above it cannot be trusted, so it was left alone. '.$trustNotice);
    // / The mode is changed only once the folder is root's, & only root can rename it, because
    // / the folder above it is root's. Each step is reported as it actually went. Until v4.0.1
    // / the chmod went unchecked & the folder was announced as root's even when it was not.
    else if ($webCanWrite) {
      list ($ownerWasChanged, $ownerNotice) = changeOwnerWithoutFollowing($folderPath, 'root', '');
      if (!$ownerWasChanged) warningEntry($ownerNotice);
      else $modeWasChanged = @chmod($folderPath, (((int)$folderStatus['mode'] & 07777) & ~0022));
      if ($ownerWasChanged && $modeWasChanged) warningEntry('The shared folder at '.$folderPath.' could be changed by another account. It now belongs to root & only root can write it. Nothing inside it was touched.');
      else if ($ownerWasChanged) warningEntry('The shared folder at '.$folderPath.' now belongs to root, & its group & other write permission could not be removed.'); }
    list ($folderIsTrusted, $resolvedFolder, $trustNotice) = pathIsTrustedByRoot($folderPath);
    if ($folderIsTrusted) $FolderIsSafe = TRUE;
    else warningEntry('The shared folder at '.$folderPath.' still cannot be trusted. '.$trustNotice); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderStatus, $webRecord, $parentIsTrusted, $folderIsTrusted, $webCanWrite, $ownerWasChanged, $modeWasChanged, $ownerNotice, $resolvedParent, $resolvedFolder, $trustNotice, $folderPath, $webAccount);
  return $FolderIsSafe; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to give the data root to root, as the folder every other data location hangs
// / off. Accepts the folder. Returns TRUE when it belongs to root & nothing else can write it.
// / ROOT OWNS IT & DOES NOT WALK IT. The data root is root:root & 0755, so every account may
// / traverse it to reach a folder of its own below, & none but root may change what is in it.
// / The session, cache, log & socket folders below are the web server account's, root's secret
// / & backup folders below are root's, & each is corrected by its own role. This corrects only
// / the root itself. The owner is changed by changeOwnerWithoutFollowing, which never follows a
// / link & refuses unless the folder above the root is one only root can change, so a v4.0.0
// / data location the web server account still owns is taken only where root could hold it.
function applyDataRootRole($folderPath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RoleWasApplied = $parentIsTrusted = $ownerWasChanged = $modeWasChanged = $folderIsTrusted = FALSE;
  $folderStatus = array();
  $ownerNotice = $resolvedParent = $resolvedFolder = $trustNotice = '';
  clearstatcache(TRUE, $folderPath);
  $folderStatus = @lstat($folderPath);
  list ($parentIsTrusted, $resolvedParent, $trustNotice) = pathIsTrustedByRoot(dirname($folderPath));
  if (!is_array($folderStatus) or ((int)$folderStatus['mode'] & 0170000) !== 0040000) warningEntry('The data root at '.$folderPath.' is not a folder, & a link is never followed, so it was left alone.');
  // / The folder above the root is checked before anything is changed, so a root refused for
  // / sitting where another account could replace it is left exactly as it was.
  else if (!$parentIsTrusted) warningEntry('The data root at '.$folderPath.' sits where another account could replace it, so it was left alone. '.$trustNotice);
  else {
    list ($ownerWasChanged, $ownerNotice) = changeOwnerWithoutFollowing($folderPath, 'root', 'root');
    if (!$ownerWasChanged) warningEntry($ownerNotice);
    // / The mode is set by name, which is safe because the folder above the root is one only
    // / root can change, so the root cannot be renamed aside between the owner change & this.
    else $modeWasChanged = @chmod($folderPath, 0755);
    if ($ownerWasChanged && !$modeWasChanged) warningEntry('The data root at '.$folderPath.' belongs to root, & its mode could not be set to 0755.');
    list ($folderIsTrusted, $resolvedFolder, $trustNotice) = pathIsTrustedByRoot($folderPath);
    if ($folderIsTrusted) $RoleWasApplied = TRUE;
    else warningEntry('The data root at '.$folderPath.' still cannot be trusted. '.$trustNotice); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderStatus, $parentIsTrusted, $ownerWasChanged, $modeWasChanged, $folderIsTrusted, $ownerNotice, $resolvedParent, $resolvedFolder, $trustNotice, $folderPath);
  return $RoleWasApplied; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to give every location the application declares the owner & mode its role needs.
// / Accepts the web server account. Must be run as root.
// / Returns whether the pass ran, how many locations were corrected & how many were refused.
// /
// / THE CONTRACT SAYS WHAT A LOCATION IS, & this decides what that means.
// / An application names each location by the setting that holds it & gives it a role in
// / $EngineManagedLocations. Every owner & mode is fixed here & nowhere else, so an
// / application cannot ask for one that undoes the rest of this.
// /   code       The installation. root, 0755 & 0644. The web server account reads it.
// /   web        One folder the web server account owns & writes. 0700, & never walked.
// /   root-only  root alone, 0700 & 0600. The previous installation kept for an operator.
// /   shared     A folder other software shares. Checked, never walked.
// /
// / THE TWO RULES IT KEEPS. Root runs nothing the web server account can change, & root never
// / works by name inside a folder the web server account can change. Until v4.0.1 this was
// / chown -R to the web server account & chmod -R 0755 over the installation, the data
// / location, every session in it & the HRProprietary folder, so root then ran code the web
// / server account owned on its next command. See Documentation/ABOUT_MANAGED_LOCATIONS.txt.
// /
// / THE INSTALLATION IS THE ONE THAT IS RUNNING. A code location is accepted only when it is
// / the folder this Engine is running from, so a wrong setting cannot hand some other tree
// / to root. It is never the filesystem root, a system folder, a shared folder or the folder
// / above one. A location with another role inside it is left out of the walk.
function correctManagedLocations($webAccount) {
  // / Set variables.
  global $EngineManagedLocations, $RunningAsRoot, $Lol, $EnableMemoryProtection;
  $CorrectionRan = FALSE;
  $LocationsCorrected = $LocationsRefused = 0;
  $roleOrder = array('shared', 'code', 'data-root', 'root-only', 'web');
  $locationRoles = $resolvedLocations = $prunedPaths = $sharedPaths = $otherLocation = $seenLocations = array();
  $settingName = $locationRole = $roleName = $locationPath = $runningInstall = $configuredPath = $holdNotice = '';
  $roleWasApplied = $treeIsHeld = FALSE;
  if (!$RunningAsRoot) warningEntry('Managed locations can only be corrected by root, so nothing was changed.');
  else if (!isset($EngineManagedLocations) or !is_array($EngineManagedLocations)) warningEntry('The contract declares no managed locations in $EngineManagedLocations, so nothing was corrected.');
  else {
    $CorrectionRan = TRUE;
    $runningInstall = (string)@realpath(dirname(__DIR__, 3));
    // / Every declared location is resolved once. A setting that is empty or names nothing
    // / that exists is skipped in silence, because a location may not be created yet.
    // / A LOCATION IS ACTED ON BY THE PATH CONFIGURED, NEVER BY WHERE A LINK LEADS. Several of
    // / them sit inside the data location, which the web server account owns, so it can put a
    // / link where one of them was. Until v4.0.1 each was resolved & its role applied to the
    // / resolved path, so a link sent the role to whatever folder it pointed at. A location whose
    // / path leads anywhere but itself is refused now, & named.
    foreach ($EngineManagedLocations as $settingName => $locationRole) {
      if (!in_array($locationRole, $roleOrder, TRUE)) {
        warningEntry('The managed location '.$settingName.' declares the role '.$locationRole.', which the Engine does not have. It was left alone.');
        $LocationsRefused++;
        continue; }
      $configuredPath = (isset($GLOBALS[$settingName]) && is_string($GLOBALS[$settingName])) ? rtrim((string)$GLOBALS[$settingName], DIRECTORY_SEPARATOR) : '';
      $locationPath = ($configuredPath === '') ? '' : (string)@realpath($configuredPath);
      // / Two settings that name the same folder with the same role, as $HomeLoc & $ConvertLoc
      // / do by default, are the same location & are corrected once.
      if ($locationPath === '' or in_array($locationPath.'|'.$locationRole, $seenLocations, TRUE)) continue;
      $seenLocations[] = $locationPath.'|'.$locationRole;
      if ($locationPath !== $configuredPath) {
        print('  '.str_pad('REFUSED', 12).$configuredPath.' leads to '.$locationPath.'. Set '.$settingName.' to the real path.'.$Lol);
        warningEntry('The managed location '.$settingName.' is '.$configuredPath.', which leads to '.$locationPath.'. A link is never followed, so it was refused.');
        $LocationsRefused++;
        continue; }
      $resolvedLocations[] = array($locationPath, $locationRole, $settingName); }
    // / A shared folder & the folder above it are never walked by root, whatever else names them.
    foreach ($resolvedLocations as $locationRoles) {
      if ($locationRoles[1] !== 'shared') continue;
      $sharedPaths[] = $locationRoles[0];
      $sharedPaths[] = dirname($locationRoles[0]); }
    foreach ($roleOrder as $roleName) {
      foreach ($resolvedLocations as $locationRoles) {
        if ($locationRoles[1] !== $roleName) continue;
        $locationPath = $locationRoles[0];
        $roleWasApplied = FALSE;
        // / Nothing walked by root may resolve to a system folder or a folder other software shares.
        if (($roleName === 'code' or $roleName === 'root-only') && (!pathIsSafeToModifyRecursively($locationPath) or in_array($locationPath, $sharedPaths, TRUE))) {
          print('  '.str_pad('REFUSED', 12).$locationPath.' is a system or shared folder & will not be changed recursively.'.$Lol);
          warningEntry('The managed location '.$locationRoles[2].' resolved to a system or shared folder & was refused: '.$locationPath);
          $LocationsRefused++;
          continue; }
        if ($roleName === 'code' && $locationPath !== $runningInstall) {
          print('  '.str_pad('REFUSED', 12).$locationPath.' is not the installation that is running, '.$runningInstall.'.'.$Lol);
          warningEntry('The managed location '.$locationRoles[2].' is '.$locationPath.', which is not the installation that is running, '.$runningInstall.'. It was refused.');
          $LocationsRefused++;
          continue; }
        // / Root walks a code or root-only tree, so the tree itself must be one nothing else can
        // / replace partway through, which means every folder above it is root's. The shared role
        // / runs first, so an installation an earlier release gave away has its folder back by now.
        if ($roleName === 'code' or $roleName === 'root-only') list ($treeIsHeld, $holdNotice) = folderIsHeldForRoot($locationPath);
        // / The backup sits in the data location by default, which the web server account owns, &
        // / every release before v4.0.1 left one there that the account owns too. Root keeps
        // / nothing there & walks nothing there, so it is skipped & left exactly as it is. That is
        // / not a fault in any location, so --setup goes on, & the updater keeps no backup there.
        if ($roleName === 'root-only' && !$treeIsHeld) {
          print('  '.str_pad('SKIPPED', 12).$locationPath.' could be replaced by another account, so root keeps nothing there & left it as it is. '.$holdNotice.$Lol);
          warningEntry('The managed location '.$locationRoles[2].' at '.$locationPath.' could be replaced by another account, so root did not walk it & keeps nothing there. '.$holdNotice);
          continue; }
        if ($roleName === 'code' && !$treeIsHeld) {
          print('  '.str_pad('REFUSED', 12).$locationPath.' could be replaced by another account & was not walked. '.$holdNotice.$Lol);
          warningEntry('The managed location '.$locationRoles[2].' at '.$locationPath.' could be replaced by another account, so root did not walk it. '.$holdNotice);
          $LocationsRefused++;
          continue; }
        if ($roleName === 'shared') $roleWasApplied = applySharedRole($locationPath, $webAccount);
        else if ($roleName === 'data-root') $roleWasApplied = applyDataRootRole($locationPath);
        else if ($roleName === 'code') {
          // / Every other declared location inside the installation keeps its own role.
          $prunedPaths = array();
          foreach ($resolvedLocations as $otherLocation) {
            if ($otherLocation[0] !== $locationPath && strpos($otherLocation[0].DIRECTORY_SEPARATOR, $locationPath.DIRECTORY_SEPARATOR) === 0) $prunedPaths[] = $otherLocation[0]; }
          $roleWasApplied = applyCodeRole($locationPath, $prunedPaths); }
        else if ($roleName === 'root-only') $roleWasApplied = applyRootOnlyRole($locationPath);
        else $roleWasApplied = applyWebRole($locationPath, $webAccount);
        if ($roleWasApplied) $LocationsCorrected++;
        else $LocationsRefused++; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $roleOrder, $locationRoles, $resolvedLocations, $prunedPaths, $sharedPaths, $otherLocation, $seenLocations, $settingName, $locationRole, $roleName, $locationPath, $runningInstall, $configuredPath, $holdNotice, $roleWasApplied, $treeIsHeld, $webAccount);
  return array($CorrectionRan, $LocationsCorrected, $LocationsRefused); }
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
    print($Lol.'  sudo php '.engineApplicationEntryPoint().' --config'.$Lol);
    print($Lol.'A backup may be taken by any account that can write the destination.'.$Lol);
    print('  php '.engineApplicationEntryPoint().' --config --backup=/tmp/config.php.bak'.$Lol.$Lol); }
  else {
    $configModel = collectConfigModel();
    list ($parseSucceeded, $detectedSections) = parseConfigFile($configTarget);
    if (!$parseSucceeded) print($Lol.'The configuration file could not be parsed. Nothing was done.'.$Lol.$Lol);
    else {
      list ($configIsAuthentic, $detectedConfigVersion) = verifyConfigAuthenticity($detectedSections);
      if (!$configIsAuthentic) print($Lol.$configTarget.$Lol.'does not declare a $ConfigVersion & is not an '.engineApplicationName().' configuration file.'.$Lol.'Nothing was done.'.$Lol.$Lol);
      else {
        print($Lol.engineApplicationName().' Configuration Utility'.$Lol);
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
// / A function to create one folder beneath a folder only root can replace.
// / Accepts the folder. Returns TRUE when it exists afterwards.
// / mkdir never follows the name it creates, but it does follow every folder above it, so the
// / first existing ancestor must be one nothing but root can change before a folder is created
// / below it. The folders above a missing one are created 0755 so every account may traverse
// / them, & the folder itself 0700. correctManagedLocations gives it its owner & final mode.
function createHeldFolder($folderPath) {
  // / Set variables.
  global $DirSep, $EnableMemoryProtection;
  $FolderExists = $ancestorIsHeld = FALSE;
  $existingAncestor = $holdNotice = '';
  if ((string)$folderPath === '' or is_dir($folderPath)) $FolderExists = (is_dir($folderPath));
  else {
    $existingAncestor = dirname($folderPath);
    while ($existingAncestor !== $DirSep && $existingAncestor !== '.' && !file_exists($existingAncestor) && !is_link($existingAncestor)) $existingAncestor = dirname($existingAncestor);
    list ($ancestorIsHeld, $holdNotice) = folderIsHeldForRoot($existingAncestor);
    if ($ancestorIsHeld) {
      @mkdir(dirname($folderPath), 0755, TRUE);
      @mkdir($folderPath, 0700); }
    if (is_dir($folderPath)) $FolderExists = TRUE;
    else warningEntry('The folder at '.$folderPath.' could not be created. '.($ancestorIsHeld ? '' : $holdNotice)); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $ancestorIsHeld, $existingAncestor, $holdNotice, $folderPath);
  return $FolderExists; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to create the data root's folders & move an older layout's sessions into them.
// / Accepts the web server account. Must run as root. Returns whether the primary folders are
// / present & how many session directories were moved, in that order.
// / THE DATA ROOT IS A ROOT ROOT OWNS, & each kind of file lives in a folder of its own below.
// / This creates the primary folders — Sessions, Cache, Logs, Engine & the socket folder — &
// / their roles are set by correctManagedLocations, which runs straight after this in both
// / --setup & -fp. Each additional data location is a root of its own the contract does not
// / name, so its root & its Sessions folder are created & given their roles here.
// / AN OLDER RELEASE KEPT EACH SESSION DIRECTLY IN A DATA LOCATION. Every session directory
// / found at the top of a data root is moved into that root's Sessions folder, so no open
// / session is lost, which is why -fp after an upgrade keeps visitors signed in. The move
// / happens before correctManagedLocations re-owns the root, while root can still rename inside
// / it, & each moved directory keeps the web server account's ownership it already had. The
// / capability cache an older release left at the root is moved into the cache folder with it.
function provisionAndMigrateDataRoot($webAccount) {
  // / Set variables.
  global $DataRootLoc, $SessionLoc, $CacheLoc, $LogDir, $EngineDir, $ManagerSocketDir, $AdditionalConvertLocs, $DirSep, $Lol, $RunningAsRoot, $EnableMemoryProtection;
  $FoldersAreReady = TRUE;
  $MovedCount = 0;
  $primaryFolders = $additionalEntry = $additionalRoots = $rootEntries = array();
  $folderPath = $additionalRoot = $rootToMigrate = $sessionTarget = $rootEntry = $entryPath = '';
  $reservedNames = array('.', '..', 'Sessions', 'Cache', 'Logs', 'Sockets', 'Engine', 'Last-Installed-Version', 'Application', 'lost+found', '.cache', '.config');
  if (!$RunningAsRoot) { warningEntry('The data root can only be provisioned while running as root, so nothing was created or moved.'); $FoldersAreReady = FALSE; }
  else {
    // / Create the primary folders. The root comes first, so every folder below it is created
    // / beneath a folder root already holds.
    $primaryFolders = array($DataRootLoc, $SessionLoc, $CacheLoc, $LogDir, $EngineDir, $ManagerSocketDir);
    foreach ($primaryFolders as $folderPath) {
      if ((string)$folderPath === '') continue;
      if (!createHeldFolder($folderPath)) $FoldersAreReady = FALSE; }
    // / Every additional data location is a root of its own. Its root & its Sessions folder are
    // / created & given their roles here, because the contract names only the primary.
    // / An additional data location sits on a mount the operator provides, such as a second
    // / disk. Its own folder & its Sessions folder are created & given their roles here only
    // / when that mount already exists, never created on top of a mount that failed, for the same
    // / reason the primary refuses to create itself on a missing one. The pool path carries the
    // / Sessions suffix, so the root is one level up & the mount is one above that.
    $additionalRoots = array();
    if (is_array($AdditionalConvertLocs)) {
      foreach ($AdditionalConvertLocs as $additionalEntry) {
        if (is_array($additionalEntry) && isset($additionalEntry[0]) && trim((string)$additionalEntry[0]) !== '') {
          $additionalRoot = rtrim(dirname(rtrim(trim((string)$additionalEntry[0]), $DirSep)), $DirSep);
          if ($additionalRoot !== '' && !in_array($additionalRoot, $additionalRoots, TRUE) && is_dir(dirname($additionalRoot))) $additionalRoots[] = $additionalRoot; } } }
    foreach ($additionalRoots as $additionalRoot) {
      if (createHeldFolder($additionalRoot) && createHeldFolder($additionalRoot.$DirSep.'Sessions')) {
        applyDataRootRole($additionalRoot);
        applyWebRole($additionalRoot.$DirSep.'Sessions', $webAccount); } }
    // / Move an older layout's sessions into each root's Sessions folder. The primary root &
    // / every additional root is swept once. A session is any directory at the top of a root
    // / that is not one of the folders this layout keeps, & it is moved by rename, which never
    // / crosses a filesystem & never copies, so a large session moves in an instant & intact.
    foreach (array_merge(array($DataRootLoc), $additionalRoots) as $rootToMigrate) {
      $sessionTarget = $rootToMigrate.$DirSep.'Sessions';
      if (!is_dir($rootToMigrate) or !is_dir($sessionTarget)) continue;
      $rootEntries = @scandir($rootToMigrate);
      if (!is_array($rootEntries)) continue;
      foreach ($rootEntries as $rootEntry) {
        // / The installation is a folder at the root since v4.0.1, & a name beginning with a dot
        // / is never a session, which is how a staged installation an install left is passed over.
        if (in_array($rootEntry, $reservedNames, TRUE) or strpos((string)$rootEntry, '.') === 0) continue;
        $entryPath = $rootToMigrate.$DirSep.$rootEntry;
        // / A link is never followed into & is left exactly where it is, as is any plain file,
        // / which at the root is the install secret or, from an older release, the capability
        // / cache. Only a real session directory is moved.
        if (is_link($entryPath)) continue;
        if (is_dir($entryPath)) {
          if (@rename($entryPath, $sessionTarget.$DirSep.$rootEntry)) $MovedCount++;
          else warningEntry('A session directory at '.$entryPath.' could not be moved into '.$sessionTarget.'. It was left where it is.'); } }
      // / The capability cache an older release wrote at the root moves into the cache folder,
      // / which is where this release reads & writes it. Only the primary root ever held one.
      if ($rootToMigrate === $DataRootLoc && is_file($rootToMigrate.$DirSep.'capability-cache.php') && is_dir($CacheLoc) && !is_file($CacheLoc.$DirSep.'capability-cache.php'))
        @rename($rootToMigrate.$DirSep.'capability-cache.php', $CacheLoc.$DirSep.'capability-cache.php'); }
    if ($MovedCount > 0) logEntry($MovedCount.' session directory or directories were moved into a Sessions folder during the data root migration.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $primaryFolders, $additionalEntry, $additionalRoots, $rootEntries, $folderPath, $additionalRoot, $rootToMigrate, $sessionTarget, $rootEntry, $entryPath, $reservedNames, $webAccount);
  return array($FoldersAreReady, $MovedCount); }
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
// / A location is only CREATED here. Its owner & mode come from its role, through
// / correctManagedLocations at the end, which is the one place a location gets either. A
// / created folder is 0700 & the folders created above it are 0755, so the web server account
// / can reach it. Until v4.0.1 each location was changed here by name with chown & chmod,
// / which follow a link, & the backup & log folders were handed to the web server account.
function prepareDataLocations() {
  // / Set variables.
  global $ConvertTemp, $ApacheUser, $RunningAsRoot, $Lol, $EnableMemoryProtection;
  $LocationsAreReady = TRUE;
  $LocationsCreated = 0;
  $foldersAreReady = $dataPolicyIsValid = $dataIsProtected = $locationPassRan = FALSE;
  $policyFindings = $policyFinding = array();
  $dataPolicyStatus = $exposureStatus = $exposureDetail = '';
  $locationsCreated = $locationsCorrected = $locationsRefused = 0;
  if (!$RunningAsRoot) errorEntry('Data locations can only be created while running as root!', 32010, FALSE);
  else {
    // / The data root & the folders below it — Sessions, Cache, Logs, Engine & the socket folder
    // / — are created here, & an older layout's sessions are moved into Sessions, by the one
    // / function -fp uses for the same, so a fresh install & an upgrade reach the same shape.
    // / Each location's owner & mode come from its role, through correctManagedLocations below,
    // / which is the one place a primary location gets either. The backup is not made here. It
    // / is root-only & the updater makes it when it keeps one, beneath a folder root holds.
    list ($foldersAreReady, $locationsCreated) = provisionAndMigrateDataRoot($ApacheUser);
    if (!$foldersAreReady) {
      $LocationsAreReady = FALSE;
      errorEntry('A required data location could not be created!', 32011, FALSE); }
    $LocationsCreated = $locationsCreated;
    // / The DATA tree is the one location that lives inside the web root.
    // / Every location above is outside it & is reachable only by this application. $ConvertTemp
    // / is DATA in the hosted location & is served straight to browsers, because that is how a
    // / user fetches a converted file. It therefore needs a protection file that none of the others do, & it
    // / has to exist before the first upload rather than after somebody notices.
    if ((string)$ConvertTemp !== '') {
      if (!is_dir($ConvertTemp)) {
        @mkdir($ConvertTemp, 0700, TRUE);
        if (is_dir($ConvertTemp)) {
          $LocationsCreated++;
          print('  '.str_pad('Created', 12).$ConvertTemp.$Lol); } }
      else print('  '.str_pad('Present', 12).$ConvertTemp.$Lol);
      if (!is_dir($ConvertTemp)) {
        $LocationsAreReady = FALSE;
        print('  '.str_pad('FAILED', 12).$ConvertTemp.$Lol);
        errorEntry('The web served DATA location could not be created at '.$ConvertTemp.'!', 32014, FALSE); }
      else {
        // / Every location gets its owner & mode from its role before the DATA protection
        // / below is written, because that protection is written into one of them.
        list ($locationPassRan, $locationsCorrected, $locationsRefused) = correctManagedLocations($ApacheUser);
        if (!$locationPassRan or $locationsRefused > 0) $LocationsAreReady = FALSE;
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
    if ($LocationsAreReady) logEntry('Prepared the data root & its folders. '.$LocationsCreated.' session directory or directories were moved from an older layout.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $policyFindings, $policyFinding, $foldersAreReady, $dataPolicyIsValid, $dataPolicyStatus, $dataIsProtected, $exposureStatus, $exposureDetail, $locationPassRan, $locationsCreated, $locationsCorrected, $locationsRefused);
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
  global $InstLoc, $DataRootLoc, $ConvertLoc, $ApacheUser, $DirSep, $EnableMemoryProtection;
  $ServiceContents = '';
  $dataLocation = $corePath = $documentationPath = $webServerUnit = '';
  // / The data root, not a session area, because the socket folder the listener binds hangs off
  // / the root. $PrimaryConvertLoc is the session area now, so the root is read directly.
  $dataLocation = (string)$DataRootLoc !== '' ? (string)$DataRootLoc : (string)$ConvertLoc;
  $corePath = $InstLoc.$DirSep.engineApplicationEntryPoint();
  $documentationPath = $InstLoc.$DirSep.'Documentation'.$DirSep.'ABOUT_RESOURCE_RATE_LIMITING.txt';
  // / Order after whichever web server is actually installed. Both are Wants rather than
  // / Requires, because HRConvert2 fails open & a listener is useful without a web server.
  $webServerUnit = 'apache2.service';
  if (locateDependency('apache2') === '' && locateDependency('httpd') !== '') $webServerUnit = 'httpd.service';
  $ServiceContents = '# / Written by '.engineApplicationName().' v'.ltrim(engineApplicationVersion(), 'vV').'. Do not edit by hand.'.PHP_EOL
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
    .'Description='.engineApplicationName().' Resource Listener'.PHP_EOL
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
  $corePath = $InstLoc.$DirSep.engineApplicationEntryPoint();
  $documentationPath = $InstLoc.$DirSep.'Documentation'.$DirSep.'ABOUT_ENVIRONMENT_MANAGER.txt';
  $ServiceContents = '# / -----------------------------------------------------------------------------------'.PHP_EOL
    .'# / '.engineApplicationName().' Environment Manager. Written by --setup --install-service.'.PHP_EOL
    .'# / Do not edit this by hand. Re-run the command above to rewrite it.'.PHP_EOL
    .'# /'.PHP_EOL
    .'# / This runs as root & is started ONLY by '.engineApplicationSlug().'-environment.timer. It looks at the'.PHP_EOL
    .'# / host, records what it found & exits. It opens no socket & accepts nothing from'.PHP_EOL
    .'# / anywhere, so there is no way to ask it to do something.'.PHP_EOL
    .'# /'.PHP_EOL
    .'# / It reports & does not repair, unless --Environment Manager May Repair-- is TRUE in'.PHP_EOL
    .'# / config.php. Read what it reports before turning that on.'.PHP_EOL
    .PHP_EOL
    .'[Unit]'.PHP_EOL
    .'Description='.engineApplicationName().' Environment Manager'.PHP_EOL
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
    .'# / '.engineApplicationName().' Environment Manager timer. Written by --setup --install-service.'.PHP_EOL
    .'# / Do not edit this by hand. Re-run the command above to rewrite it.'.PHP_EOL
    .'# /'.PHP_EOL
    .'# / Enable it with:  sudo systemctl enable --now '.engineApplicationSlug().'-environment.timer'.PHP_EOL
    .'# / See what it last found with:  sudo systemctl status '.engineApplicationSlug().'-environment'.PHP_EOL
    .PHP_EOL
    .'[Unit]'.PHP_EOL
    .'Description='.engineApplicationName().' Environment Manager schedule'.PHP_EOL
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
    .'Unit='.engineApplicationSlug().'-environment.service'.PHP_EOL
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
  $unitPath = '/etc/systemd/system/'.engineApplicationSlug().'-listener.service';
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
          exec('systemctl enable '.($startIfStopped ? '--now ' : '').engineApplicationSlug().'-listener 2>&1', $commandOutput, $commandExitCode);
          if ($commandExitCode === 0) {
            $ServiceWasInstalled = TRUE;
            $ServiceStatus = 'installed';
            print('  '.str_pad('Enabled', 12).engineApplicationSlug().'-listener, starting at boot & running now'.$Lol);
            // / enable --now starts a stopped listener & leaves a running one as it is, & a running
            // / one keeps the unit & the release it started with. It is not restarted here, because
            // / a listener started again within thirty seconds mints the startup keys it just spent
            // / & refuses them as replays, so the administrator is told instead.
            if ($existingContents !== '') print('  '.str_pad('Note', 12).'A listener that was already running still runs the old unit. Restart it.'.$Lol.'  '.str_pad('', 12).'systemctl restart '.engineApplicationSlug().'-listener'.$Lol);
            logEntry('The listener service unit was installed & enabled.'); }
          else {
            $ServiceStatus = 'failed';
            print('  '.str_pad('FAILED', 12).'systemctl enable --now '.engineApplicationSlug().'-listener'.$Lol);
            print('  '.str_pad('', 12).implode($Lol.'  '.str_pad('', 12), $commandOutput).$Lol);
            warningEntry('The listener service unit was written but could not be enabled.'); } }
        else {
          $ServiceWasInstalled = TRUE;
          $ServiceStatus = 'installed';
          print('  '.str_pad('Ready', 12).'systemctl enable --now '.engineApplicationSlug().'-listener'.$Lol); } } } }
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
  $servicePath = '/etc/systemd/system/'.engineApplicationSlug().'-environment.service';
  $timerPath = '/etc/systemd/system/'.engineApplicationSlug().'-environment.timer';
  if (!$RunningAsRoot) {
    $TimerStatus = 'failed';
    errorEntry('The Environment Manager timer can only be installed while running as root!', 32017, FALSE); }
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
    exec('systemctl enable --now '.engineApplicationSlug().'-environment.timer 2>&1', $commandOutput, $commandExitCode);
    if ($commandExitCode === 0) {
      $TimerStatus = 'enabled';
      print('  '.str_pad('Enabled', 12).engineApplicationSlug().'-environment.timer, hourly from now'.$Lol);
      warningEntry('The Environment Manager timer was enabled. A root process now runs hourly.'); }
    else {
      $TimerStatus = 'failed';
      errorEntry('The Environment Manager timer was written & could not be enabled!', 32016, FALSE); } }
  else if ($TimerWasInstalled && $TimerStatus !== 'unchanged') {
    print('  '.str_pad('', 12).'The timer is NOT enabled. Read one pass before scheduling root.'.$Lol);
    print('  '.str_pad('', 12).'  sudo php '.engineApplicationEntryPoint().' --run-environment-manager'.$Lol);
    print('  '.str_pad('', 12).'  sudo systemctl enable --now '.engineApplicationSlug().'-environment.timer'.$Lol); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $timerSystemdUsable, $timerSystemdReason, $servicePath, $timerPath, $serviceContents, $timerContents, $existingService, $existingTimer, $commandOutput, $commandExitCode, $enableTimer);
  return array($TimerWasInstalled, $TimerStatus); }
// / -----------------------------------------------------------------------------------
// / A function to perform a complete installation.
// / Accepts the dependency authorization token & a confirmation boolean, in that order.
// / Returns a completion boolean & the number of stages that succeeded, in that order.
// / Seven stages, in the order an installation actually depends on.
// /   1  Dependencies. Nothing below can be verified until the tools exist.
// /   2  Data locations. A policy cannot be written into a directory that is not there.
// /   3  Permissions & policies. A converter cannot run until its policy permits it.
// /   4  Configuration. Written as a file the web server user has to read.
// /   5  Service unit. Generated from the configuration stage four just settled.
// /   6  Permissions again. Stages four & five both write files.
// /   7  Verification. Report what actually works rather than what was attempted.
// / The total is one local, $stageTotal, & every line that states it reads that local.
// / The second permissions pass was added without its summary line, which went on saying
// / six while every stage heading said seven.
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
  $stageTotal = 7;
  $operatorChoice = $serviceStatus = '';
  $dependencyFindings = array();
  print($Lol.engineApplicationName().' Complete Installation'.$Lol);
  print($stageTotal.' stages. Dependencies, locations, permissions, configuration, service,'.$Lol);
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
    print($Lol.'Stage 1 of '.$stageTotal.'. Dependencies.'.$Lol);
    list ($stageSucceeded, $stageCount) = installDepends($authorizationToken, '', TRUE);
    if (!$stageSucceeded) print($Lol.'Dependency installation did not complete. Stopping here.'.$Lol.'Run --setup --check-depends to see what is missing.'.$Lol.$Lol);
    else {
      $StagesCompleted++;
      // / Stage two. The data locations everything below writes into.
      print($Lol.'Stage 2 of '.$stageTotal.'. Data locations.'.$Lol);
      list ($locationsAreReady, $locationsCreated) = prepareDataLocations();
      if (!$locationsAreReady) print($Lol.'One or more data locations could not be created. Stopping here.'.$Lol.$Lol);
      else {
        $StagesCompleted++;
        // / Stage three. Ownership, permissions & the policy files those packages need.
        print($Lol.'Stage 3 of '.$stageTotal.'. Permissions & policies.'.$Lol);
        list ($permissionsWereFixed, $pathsCorrected) = runApplicationRepair();
        if (!$permissionsWereFixed) print($Lol.'Permissions could not be corrected. Stopping here.'.$Lol.$Lol);
        else {
          $StagesCompleted++;
          // / Stage four. Configuration. Interactive unless the operator pre confirmed,
          // / because an unattended install must not stop for a question.
          print($Lol.'Stage 4 of '.$stageTotal.'. Configuration.'.$Lol);
          if ($operatorConfirmed) {
            $StagesCompleted++;
            print('  Keeping the configuration this installation already declares.'.$Lol);
            print('  Run the following to change anything.'.$Lol);
            print('    sudo php '.$InstLoc.$DirSep.engineApplicationEntryPoint().' --config'.$Lol); }
          else if (runConfigUtility('', 'interactive', '', FALSE)) $StagesCompleted++;
          else print('  Configuration was cancelled. The shipped defaults remain in place.'.$Lol);
          // / Stage five. The service unit, generated from the configuration above.
          print($Lol.'Stage 5 of '.$stageTotal.'. Listener service.'.$Lol);
          // / TRUE, TRUE. An installation with no listener running is not installed yet.
          list ($serviceWasInstalled, $serviceStatus) = installListenerService(TRUE, TRUE);
          if ($serviceWasInstalled or $serviceStatus === 'skipped') $StagesCompleted++;
          // / The Environment Manager timer is written here & is deliberately NOT enabled.
          // / The listener is enabled because nothing works without it. This one puts a ROOT
          // / process on a schedule, & that is a decision an administrator makes after reading
          // / what one pass reports rather than something a setup wizard does on their behalf.
          print($Lol.'Environment Manager schedule.'.$Lol);
          installEnvironmentTimer(FALSE);
          // / Stage six. Permissions again, because stages four & five both wrote files.
          // / Stage three cannot be the last word on ownership when work happens after it.
          // / writeConfigFile() leaves config.php root's & 0644 as it renames it into place, &
          // / backupConfigFile() keeps a copy that is root's & 0640. This pass applies every role again,
          // / so anything those stages left is put right by the same rules -fp uses. It is
          // / idempotent, so running it twice costs a walk of the installation & nothing else.
          print($Lol.'Stage 6 of '.$stageTotal.'. Permissions, second pass.'.$Lol);
          list ($permissionsWereFixed, $pathsCorrected) = runApplicationRepair();
          if ($permissionsWereFixed) $StagesCompleted++;
          else warningEntry('The second permissions pass did not complete. Files written by the configuration & service stages may be owned by root.');
          // / Stage seven. Report what actually works. An installation that says it finished
          // / & then refuses every conversion has helped nobody.
          print($Lol.'Stage 7 of '.$stageTotal.'. Verification.'.$Lol);
          list ($dependenciesReady, $dependencyFindings, $optionalProblems) = checkDepends('');
          showDependencyFindings($dependencyFindings);
          $StagesCompleted++;
          $InstallCompleted = TRUE;
          logEntry('A complete installation finished '.$StagesCompleted.' of '.$stageTotal.' stages.');
          print($Lol.'Installation complete. '.$StagesCompleted.' of '.$stageTotal.' stages finished.'.$Lol);
          if (!$dependenciesReady) print($Lol.'One or more REQUIRED dependencies are still missing. Conversions using them will refuse.'.$Lol);
          if ($optionalProblems > 0) print($Lol.$optionalProblems.' OPTIONAL dependenc(ies) are missing. Those subsystems will refuse until they are installed.'.$Lol);
          print($Lol.'  php '.$InstLoc.$DirSep.engineApplicationEntryPoint().' -v          Confirm every dependency & subsystem.'.$Lol);
          print('  php '.$InstLoc.$DirSep.engineApplicationEntryPoint().' --status    Confirm the listener.'.$Lol);
          print('  sudo php '.$InstLoc.$DirSep.engineApplicationEntryPoint().' --config    Change any setting.'.$Lol);
          print($Lol.'Two things this cannot decide for you.'.$Lol);
          print('  The public URL, if share links are to work. Set it with --config.'.$Lol);
          print('  A web server virtual host, if the installation is not already served.'.$Lol.$Lol); } } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $componentIsAvailable, $stageSucceeded, $permissionsWereFixed, $locationsAreReady, $serviceWasInstalled, $dependenciesReady, $stageCount, $pathsCorrected, $locationsCreated, $optionalProblems, $operatorChoice, $serviceStatus, $dependencyFindings, $authorizationToken, $operatorConfirmed, $stageTotal);
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

// / -----------------------------------------------------------------------------------
// / A function to write the loader the web server runs in place of the application.
// / Accepts the path of the application's entry point in its installed folder.
// / Returns the contents of the loader.
// /
// / THE APPLICATION LIVES OUTSIDE THE WEB ROOT & THIS IS THE ONLY CODE LEFT INSIDE IT. It carries
// / the entry point's name, so every URL, form, bookmark & command an operator knows reaches the
// / same file, & it does one thing, which is hand the request to the installed application. The
// / path in it is written from the installation, never typed. Root refuses to go on when the
// / entry point or its folder could have been changed by another account, because the entry point
// / runs before anything in it can check the tree it is in. A missing application is refused with
// / a plain message & exit status one. A browser is told only that the application is
// / unavailable, because the path would tell a stranger where the data location is.
function applicationLoaderContents($applicationEntry) {
  // / Set variables.
  global $EnableMemoryProtection;
  $LoaderContents = '';
  $quotedEntry = $quotedFolder = $applicationName = $entryName = '';
  $quotedEntry = var_export((string)$applicationEntry, TRUE);
  $quotedFolder = var_export(dirname((string)$applicationEntry), TRUE);
  $applicationName = engineApplicationName();
  $entryName = engineApplicationEntryPoint();
  $LoaderContents = '<?php'.PHP_EOL
    .'// / -----------------------------------------------------------------------------------'.PHP_EOL
    .'// / The '.$applicationName.' loader. '.strtoupper(engineApplicationSlug()).'-LOADER-MARKER'.PHP_EOL
    .'// / Written by '.$entryName.' -fp as root, which writes it again whenever it differs.'.PHP_EOL
    .'// / '.$applicationName.' is installed outside the web root, in the folder named below. This file'.PHP_EOL
    .'// / hands every request & every command to it, & is the only PHP the web server runs here.'.PHP_EOL
    .'// / -----------------------------------------------------------------------------------'.PHP_EOL
    .'if (!is_file('.$quotedEntry.')) {'.PHP_EOL
    .'  if (PHP_SAPI === \'cli\') print(\''.$applicationName.' is not installed at \'.'.$quotedEntry.'.\', so this loader has nothing to run. Unpack a release beside this file & run  sudo php '.$entryName.' -fp  to install it.\'.PHP_EOL);'.PHP_EOL
    .'  else {'.PHP_EOL
    .'    http_response_code(503);'.PHP_EOL
    .'    print(\''.$applicationName.' is unavailable.\'); }'.PHP_EOL
    .'  exit(1); }'.PHP_EOL
    .'if (function_exists(\'posix_geteuid\') && posix_geteuid() === 0 && (@fileowner('.$quotedEntry.') !== 0 or (@fileperms('.$quotedEntry.') & 0022) !== 0 or @fileowner('.$quotedFolder.') !== 0 or (@fileperms('.$quotedFolder.') & 0022) !== 0)) {'.PHP_EOL
    .'  print(\'ERROR!!! '.$applicationName.'-28008, Root refused to run \'.'.$quotedEntry.'.\', because an account other than root could have changed it. Run  sudo php \'.'.$quotedEntry.'.\' -fp  to give it back to root.\'.PHP_EOL);'.PHP_EOL
    .'  exit(1); }'.PHP_EOL
    .'require '.$quotedEntry.';'.PHP_EOL;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $LoaderContents is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $quotedEntry, $quotedFolder, $applicationName, $entryName, $applicationEntry);
  return $LoaderContents; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether a file is a loader this Engine wrote.
// / Accepts the path. Returns TRUE when the file carries the loader's marker.
// / The marker is the application's own name, so another application's loader is not this one.
function fileIsApplicationLoader($filePath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FileIsLoader = $fileWasRead = FALSE;
  $fileContents = $readNotice = '';
  list ($fileWasRead, $fileContents, $readNotice) = readFileWithoutFollowing($filePath, 65536);
  if ($fileWasRead && strpos($fileContents, strtoupper(engineApplicationSlug()).'-LOADER-MARKER') !== FALSE) $FileIsLoader = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $fileWasRead, $fileContents, $readNotice, $filePath);
  return $FileIsLoader; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to copy one entry of an application tree, & everything inside it.
// / Accepts the source & the destination. Returns whether it was copied, how many files were
// / copied & the reason it was not, in that order.
// / THE DESTINATION IS INSIDE A FOLDER ONLY ROOT CAN ENTER, which installApplicationTree creates
// / 0700 beside the installation, so nothing can be swapped in while this writes, & the source
// / is a tree root already trusts. A link anywhere in it stops the copy, because an installation
// / that leads somewhere else is not one tree. Folders are 0755 & files 0644, as the code role
// / leaves them, because nothing in an installation is run except through its interpreter.
function copyApplicationEntry($sourcePath, $targetPath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $EntryWasCopied = FALSE;
  $FilesCopied = 0;
  $CopyNotice = '';
  $entryStatus = $childNames = array();
  $entryType = $childFiles = 0;
  $childName = $childNotice = '';
  $childWasCopied = FALSE;
  clearstatcache(TRUE, (string)$sourcePath);
  $entryStatus = @lstat((string)$sourcePath);
  if (is_array($entryStatus)) $entryType = ((int)$entryStatus['mode'] & 0170000);
  if (!is_array($entryStatus)) $CopyNotice = $sourcePath.' does not exist.';
  else if ($entryType === 0120000) $CopyNotice = $sourcePath.' is a link, & an installation is copied only as plain files & folders.';
  else if ($entryType === 0100000) {
    if (@copy((string)$sourcePath, (string)$targetPath) && @chmod((string)$targetPath, 0644)) {
      $EntryWasCopied = TRUE;
      $FilesCopied = 1; }
    else $CopyNotice = $sourcePath.' could not be copied.'; }
  else if ($entryType !== 0040000) $CopyNotice = $sourcePath.' is neither a file nor a folder.';
  else if (!@mkdir((string)$targetPath, 0755) or !@chmod((string)$targetPath, 0755)) $CopyNotice = $targetPath.' could not be created.';
  else {
    $childNames = @scandir((string)$sourcePath);
    if (!is_array($childNames)) $CopyNotice = $sourcePath.' could not be read.';
    else {
      $EntryWasCopied = TRUE;
      foreach (array_diff($childNames, array('.', '..')) as $childName) {
        list ($childWasCopied, $childFiles, $childNotice) = copyApplicationEntry($sourcePath.DIRECTORY_SEPARATOR.$childName, $targetPath.DIRECTORY_SEPARATOR.$childName);
        $FilesCopied = $FilesCopied + (int)$childFiles;
        if (!$childWasCopied) {
          $EntryWasCopied = FALSE;
          $CopyNotice = $childNotice;
          break; } } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $entryStatus, $childNames, $entryType, $childFiles, $childName, $childNotice, $childWasCopied, $sourcePath, $targetPath);
  return array($EntryWasCopied, $FilesCopied, $CopyNotice); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to keep the installation an application folder replaces, or to remove it.
// / Accepts the application folder & the backup location. Returns whether it was kept & a
// / sentence saying what happened, in that order.
// / It is kept as the backup when the folder the backup lives in is one only root can change &
// / the backup is not a system folder, which is the same test an update applies to its own
// / backup. Otherwise it is removed & that is said, because the new installation must go in.
function retireApplicationTree($applicationPath, $backupLoc) {
  // / Set variables.
  global $EnableMemoryProtection;
  $TreeWasKept = FALSE;
  $RetireNotice = '';
  $backupPath = $resolvedParent = $trustNotice = '';
  $parentIsTrusted = $backupIsUsable = FALSE;
  $backupPath = rtrim((string)$backupLoc, DIRECTORY_SEPARATOR);
  if ($backupPath !== '') list ($parentIsTrusted, $resolvedParent, $trustNotice) = pathIsTrustedByRoot(dirname($backupPath));
  $backupIsUsable = ($parentIsTrusted && $resolvedParent === dirname($backupPath));
  // / The backup before it is removed first. A link there is removed as a link, & a folder only
  // / when it is not a system folder, because a misconfigured backup would empty that instead.
  if ($backupIsUsable && (is_link($backupPath) or (file_exists($backupPath) && pathIsSafeToModifyRecursively($backupPath)))) exec('rm -rf '.escapeshellarg($backupPath).' 2>&1');
  if ($backupIsUsable && !file_exists($backupPath) && !is_link($backupPath) && @rename((string)$applicationPath, $backupPath)) {
    $TreeWasKept = TRUE;
    $RetireNotice = 'The installation it replaced is kept at '.$backupPath.'.'; }
  if (!$TreeWasKept) {
    if (pathIsSafeToModifyRecursively((string)$applicationPath)) exec('rm -rf '.escapeshellarg((string)$applicationPath).' 2>&1');
    $RetireNotice = 'The installation it replaced was not kept'.($backupPath === '' ? ', because no backup location is configured.' : ', because '.$backupPath.' could not hold it.'.($trustNotice === '' ? '' : ' '.$trustNotice)); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $backupPath, $resolvedParent, $trustNotice, $parentIsTrusted, $backupIsUsable, $applicationPath, $backupLoc);
  return array($TreeWasKept, $RetireNotice); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to install the tree that is running into the application folder.
// / Accepts the running tree, the application folder & the backup location, in that order.
// / Returns whether it was installed & a sentence saying what happened, in that order.
// /
// / THE APPLICATION LIVES IN THE DATA LOCATION, OUTSIDE THE WEB ROOT, & IS ROOT'S. Only the
// / entries the contract names in $EngineApplicationLayout are copied, so whatever else shares
// / the folder a release was unpacked into is never taken along, & the DATA tree stays where the
// / web server serves it. The copy is made in a folder only root can enter, beside the
// / application folder, & renamed into place, so the application folder holds the whole old
// / installation or the whole new one. The one it replaces is kept as the backup, as an update
// / keeps one. The running tree must be one root trusts, & the folder the application lives in
// / must be one only root can change, or nothing is copied at all.
function installApplicationTree($sourceTree, $applicationLoc, $backupLoc) {
  // / Set variables.
  global $EngineApplicationLayout, $RunningAsRoot, $EnableMemoryProtection;
  $TreeWasInstalled = FALSE;
  $InstallNotice = '';
  $sourcePath = $applicationPath = $applicationParent = $resolvedParent = $trustNotice = $treeNotice = $stagedPath = $layoutEntry = $entryNotice = $retireNotice = '';
  $parentIsTrusted = $treeIsTrusted = $entryWasCopied = $treeWasKept = FALSE;
  $filesCopied = $entryFiles = 0;
  $layoutEntries = array();
  $sourcePath = rtrim((string)$sourceTree, DIRECTORY_SEPARATOR);
  $applicationPath = rtrim((string)$applicationLoc, DIRECTORY_SEPARATOR);
  $applicationParent = dirname($applicationPath);
  if (isset($EngineApplicationLayout) && is_array($EngineApplicationLayout)) $layoutEntries = $EngineApplicationLayout;
  if (!$RunningAsRoot) $InstallNotice = 'Only root installs the application.';
  else if (count($layoutEntries) === 0) $InstallNotice = 'The contract names no application layout in $EngineApplicationLayout, so nothing was installed.';
  else if ($sourcePath === '' or $applicationPath === '' or (string)@realpath($sourcePath) === $applicationPath) $InstallNotice = 'The running tree is the installed one, so there is nothing to install.';
  else {
    list ($parentIsTrusted, $resolvedParent, $trustNotice) = pathIsTrustedByRoot($applicationParent);
    if ($parentIsTrusted && $resolvedParent !== $applicationParent) $trustNotice = $applicationParent.' leads to '.$resolvedParent.'.';
    list ($treeIsTrusted, $treeNotice) = treeIsTrustedByRoot($sourcePath, array($sourcePath.DIRECTORY_SEPARATOR.'DATA'));
    if (!$parentIsTrusted or $resolvedParent !== $applicationParent) $InstallNotice = 'The folder the application lives in, '.$applicationParent.', can be changed by an account other than root, so nothing was installed. '.$trustNotice;
    else if (!$treeIsTrusted) $InstallNotice = 'The running tree at '.$sourcePath.' can be changed by an account other than root, so it was not installed. '.$treeNotice;
    else if (is_link($applicationPath) or (file_exists($applicationPath) && !is_dir($applicationPath))) $InstallNotice = $applicationPath.' is not a folder, so nothing was installed.';
    else {
      $stagedPath = $applicationParent.DIRECTORY_SEPARATOR.'.'.basename($applicationPath).'-install-'.bin2hex(random_bytes(8));
      if (!@mkdir($stagedPath, 0700) or !@chmod($stagedPath, 0700)) $InstallNotice = 'A folder to stage the installation in could not be created beside '.$applicationPath.'.';
      else {
        // / Each entry is a name at the top of the tree. A name that walks out of it is ignored,
        // / & an entry a release does not carry is skipped rather than refused.
        foreach ($layoutEntries as $layoutEntry) {
          $layoutEntry = basename((string)$layoutEntry);
          if ($layoutEntry === '' or $layoutEntry === '.' or $layoutEntry === '..' or (!file_exists($sourcePath.DIRECTORY_SEPARATOR.$layoutEntry) && !is_link($sourcePath.DIRECTORY_SEPARATOR.$layoutEntry))) continue;
          list ($entryWasCopied, $entryFiles, $entryNotice) = copyApplicationEntry($sourcePath.DIRECTORY_SEPARATOR.$layoutEntry, $stagedPath.DIRECTORY_SEPARATOR.$layoutEntry);
          $filesCopied = $filesCopied + (int)$entryFiles;
          if (!$entryWasCopied) {
            $InstallNotice = 'The running tree was not installed. '.$entryNotice;
            break; } }
        if ($InstallNotice === '' && !is_file($stagedPath.DIRECTORY_SEPARATOR.engineApplicationEntryPoint())) $InstallNotice = 'The running tree holds no '.engineApplicationEntryPoint().', so it was not installed.';
        if ($InstallNotice === '' && is_dir($applicationPath)) list ($treeWasKept, $retireNotice) = retireApplicationTree($applicationPath, $backupLoc);
        if ($InstallNotice === '' && file_exists($applicationPath)) $InstallNotice = 'The installation at '.$applicationPath.' could not be moved aside, so the new one was not put in its place.';
        else if ($InstallNotice === '' && @rename($stagedPath, $applicationPath) && @chmod($applicationPath, 0755)) {
          $TreeWasInstalled = TRUE;
          $InstallNotice = 'Installed '.$filesCopied.' file(s) from '.$sourcePath.' into '.$applicationPath.'.'.($retireNotice === '' ? '' : ' '.$retireNotice); }
        else if ($InstallNotice === '') $InstallNotice = 'The staged installation could not be moved into '.$applicationPath.'.';
        // / A staged copy that was not moved into place is removed. It is root's & only root can enter it.
        if (!$TreeWasInstalled && is_dir($stagedPath)) exec('rm -rf '.escapeshellarg($stagedPath).' 2>&1'); } } }
  if (!$TreeWasInstalled && $RunningAsRoot && count($layoutEntries) > 0 && $sourcePath !== '' && (string)@realpath($sourcePath) !== $applicationPath) warningEntry($InstallNotice);
  else if ($TreeWasInstalled) logEntry($InstallNotice);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $sourcePath, $applicationPath, $applicationParent, $resolvedParent, $trustNotice, $treeNotice, $stagedPath, $layoutEntry, $entryNotice, $retireNotice, $parentIsTrusted, $treeIsTrusted, $entryWasCopied, $treeWasKept, $filesCopied, $entryFiles, $layoutEntries, $sourceTree, $applicationLoc, $backupLoc);
  return array($TreeWasInstalled, $InstallNotice); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to list what the hosted location should hold, apart from DATA.
// / Accepts the application folder. Returns the files by their path below the hosted location,
// / each with the path it is copied from or an empty string for the loader, & the folders they
// / need, parents first, in that order.
// / A browser fetches the static files of the folders the contract names in
// / $EngineHostedAssetFolders, so each file of a type $EngineHostedAssetTypes names is copied.
// / Nothing else is. The application is read & never written, & a link in it is passed over.
function hostedLocationPlan($applicationLoc) {
  // / Set variables.
  global $EngineHostedAssetFolders, $EngineHostedAssetTypes, $EnableMemoryProtection;
  $PlannedFiles = $PlannedFolders = array();
  $applicationPath = $assetFolder = $currentFolder = $childName = $childPath = $relativePath = '';
  $pendingFolders = $childNames = $childStatus = $assetTypes = array();
  $applicationPath = rtrim((string)$applicationLoc, DIRECTORY_SEPARATOR);
  $PlannedFiles[engineApplicationEntryPoint()] = '';
  if (is_file($applicationPath.DIRECTORY_SEPARATOR.'index.html') && !is_link($applicationPath.DIRECTORY_SEPARATOR.'index.html')) $PlannedFiles['index.html'] = $applicationPath.DIRECTORY_SEPARATOR.'index.html';
  if (isset($EngineHostedAssetTypes) && is_array($EngineHostedAssetTypes)) $assetTypes = array_map('strtolower', $EngineHostedAssetTypes);
  if (isset($EngineHostedAssetFolders) && is_array($EngineHostedAssetFolders)) {
    foreach ($EngineHostedAssetFolders as $assetFolder) {
      $assetFolder = basename((string)$assetFolder);
      if ($assetFolder === '' or $assetFolder === '.' or $assetFolder === '..') continue;
      $pendingFolders = array($assetFolder);
      while (count($pendingFolders) > 0) {
        $currentFolder = array_shift($pendingFolders);
        clearstatcache(TRUE, $applicationPath.DIRECTORY_SEPARATOR.$currentFolder);
        $childStatus = @lstat($applicationPath.DIRECTORY_SEPARATOR.$currentFolder);
        if (!is_array($childStatus) or ((int)$childStatus['mode'] & 0170000) !== 0040000) continue;
        $childNames = @scandir($applicationPath.DIRECTORY_SEPARATOR.$currentFolder);
        if (!is_array($childNames)) continue;
        foreach (array_diff($childNames, array('.', '..')) as $childName) {
          $relativePath = $currentFolder.DIRECTORY_SEPARATOR.$childName;
          $childPath = $applicationPath.DIRECTORY_SEPARATOR.$relativePath;
          clearstatcache(TRUE, $childPath);
          $childStatus = @lstat($childPath);
          if (!is_array($childStatus)) continue;
          if (((int)$childStatus['mode'] & 0170000) === 0040000) $pendingFolders[] = $relativePath;
          else if (((int)$childStatus['mode'] & 0170000) === 0100000 && in_array(strtolower(pathinfo($childName, PATHINFO_EXTENSION)), $assetTypes, TRUE)) $PlannedFiles[$relativePath] = $childPath; } } } }
  // / The folders are the ones the files need & no others, each listed before what it holds.
  foreach (array_keys($PlannedFiles) as $relativePath) {
    for ($currentFolder = dirname($relativePath); $currentFolder !== '.' && $currentFolder !== DIRECTORY_SEPARATOR && $currentFolder !== ''; $currentFolder = dirname($currentFolder)) $PlannedFolders[] = $currentFolder; }
  $PlannedFolders = array_values(array_unique($PlannedFolders));
  usort($PlannedFolders, function($firstFolder, $secondFolder) { return substr_count($firstFolder, DIRECTORY_SEPARATOR) - substr_count($secondFolder, DIRECTORY_SEPARATOR); });
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / Neither $PlannedFiles nor $PlannedFolders is purged, because both are return values.
  purgeSensitiveMemory($EnableMemoryProtection, $applicationPath, $assetFolder, $currentFolder, $childName, $childPath, $relativePath, $pendingFolders, $childNames, $childStatus, $assetTypes, $applicationLoc);
  return array($PlannedFiles, $PlannedFolders); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether two files hold the same bytes.
// / Accepts both paths. Returns TRUE when both are plain files & their contents match.
// / Both are read through the kernel, so a link or a FIFO in either place is never followed.
function filesAreIdentical($firstPath, $secondPath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FilesMatch = $firstWasRead = $secondWasRead = FALSE;
  $firstContents = $secondContents = $readNotice = '';
  if (@filesize((string)$firstPath) === @filesize((string)$secondPath)) {
    list ($firstWasRead, $firstContents, $readNotice) = readFileWithoutFollowing($firstPath, 268435456);
    if ($firstWasRead) list ($secondWasRead, $secondContents, $readNotice) = readFileWithoutFollowing($secondPath, 268435456);
    $FilesMatch = ($firstWasRead && $secondWasRead && $firstContents === $secondContents); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $firstWasRead, $secondWasRead, $firstContents, $secondContents, $readNotice, $firstPath, $secondPath);
  return $FilesMatch; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove from the hosted location what is only a copy of the installed application.
// / Accepts the hosted path, the matching path in the application, the path below the hosted
// / location, the files the hosted location keeps & a boolean permitting removal, in that order.
// / Returns how many names were removed, & how many were left because they differ, in that order.
// /
// / A NAME IS REMOVED ONLY WHEN THE APPLICATION HOLDS THE SAME BYTES UNDER THE SAME NAME. What an
// / administrator keeps there, & a release that was unpacked there & is not installed, is never
// / removed, because neither has a copy anywhere else. A folder is entered only when root alone
// / can change it, so nothing can be swapped in while this removes, & is removed only once it is
// / empty. The hosted location's own files are kept.
function removeHostedCopies($hostedPath, $applicationPath, $relativePath, $keptFiles, $mayRepair) {
  // / Set variables.
  global $EnableMemoryProtection;
  $NamesRemoved = $NamesLeft = 0;
  $entryStatus = $childNames = array();
  $childName = $resolvedPath = $trustNotice = $removalNotice = $keptPath = '';
  $childRemoved = $childLeft = 0;
  $folderIsTrusted = $nameWasRemoved = $folderIsNeeded = FALSE;
  clearstatcache(TRUE, (string)$hostedPath);
  $entryStatus = @lstat((string)$hostedPath);
  if (!is_array($entryStatus) or array_key_exists((string)$relativePath, $keptFiles)) $NamesLeft = 0;
  else if (((int)$entryStatus['mode'] & 0170000) === 0040000) {
    list ($folderIsTrusted, $resolvedPath, $trustNotice) = pathIsTrustedByRoot((string)$hostedPath);
    $childNames = $folderIsTrusted ? @scandir((string)$hostedPath) : FALSE;
    if (!is_array($childNames)) $NamesLeft = 1;
    else {
      foreach (array_diff($childNames, array('.', '..')) as $childName) {
        list ($childRemoved, $childLeft) = removeHostedCopies($hostedPath.DIRECTORY_SEPARATOR.$childName, $applicationPath.DIRECTORY_SEPARATOR.$childName, $relativePath.DIRECTORY_SEPARATOR.$childName, $keptFiles, $mayRepair);
        $NamesRemoved = $NamesRemoved + $childRemoved;
        $NamesLeft = $NamesLeft + $childLeft; }
      // / A folder the hosted location still needs stays, even emptied, & so does one with
      // / anything left in it. rmdir refuses a folder that is not empty.
      foreach (array_keys($keptFiles) as $keptPath) if (strpos((string)$keptPath, $relativePath.DIRECTORY_SEPARATOR) === 0) $folderIsNeeded = TRUE;
      if ($mayRepair && !$folderIsNeeded && count(array_diff((array)@scandir((string)$hostedPath), array('.', '..'))) === 0 && @rmdir((string)$hostedPath)) $NamesRemoved++; } }
  else if (((int)$entryStatus['mode'] & 0170000) === 0100000 && filesAreIdentical($hostedPath, $applicationPath)) {
    if ($mayRepair) list ($nameWasRemoved, $removalNotice) = removeFileWithoutFollowing((string)$hostedPath);
    if ($nameWasRemoved) $NamesRemoved = 1;
    else $NamesLeft = 1; }
  else $NamesLeft = 1;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $entryStatus, $childNames, $childName, $resolvedPath, $trustNotice, $removalNotice, $keptPath, $childRemoved, $childLeft, $folderIsTrusted, $nameWasRemoved, $folderIsNeeded, $hostedPath, $applicationPath, $relativePath, $keptFiles, $mayRepair);
  return array($NamesRemoved, $NamesLeft); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove static files the hosted location no longer needs.
// / Accepts the hosted location, the application folder, the files the hosted location keeps,
// / the folders they need & a boolean permitting removal, in that order.
// / Returns how many names were removed & how many would have been, in that order.
// / A static file in one of those folders that the application no longer has at all was copied
// / there by an earlier release, so it is this application's to remove. Anything else is left.
function removeStaleHostedAssets($hostedLoc, $applicationLoc, $plannedFiles, $plannedFolders, $mayRepair) {
  // / Set variables.
  global $EngineHostedAssetTypes, $EnableMemoryProtection;
  $AssetsRemoved = $AssetsStale = 0;
  $plannedFolder = $childName = $relativePath = $removalNotice = $resolvedPath = $trustNotice = '';
  $childNames = $childStatus = $assetTypes = array();
  $folderIsTrusted = $nameWasRemoved = FALSE;
  if (isset($EngineHostedAssetTypes) && is_array($EngineHostedAssetTypes)) $assetTypes = array_map('strtolower', $EngineHostedAssetTypes);
  foreach ($plannedFolders as $plannedFolder) {
    list ($folderIsTrusted, $resolvedPath, $trustNotice) = pathIsTrustedByRoot($hostedLoc.DIRECTORY_SEPARATOR.$plannedFolder);
    $childNames = $folderIsTrusted ? @scandir($hostedLoc.DIRECTORY_SEPARATOR.$plannedFolder) : FALSE;
    if (!is_array($childNames)) continue;
    foreach (array_diff($childNames, array('.', '..')) as $childName) {
      $relativePath = $plannedFolder.DIRECTORY_SEPARATOR.$childName;
      clearstatcache(TRUE, $hostedLoc.DIRECTORY_SEPARATOR.$relativePath);
      $childStatus = @lstat($hostedLoc.DIRECTORY_SEPARATOR.$relativePath);
      if (!is_array($childStatus) or ((int)$childStatus['mode'] & 0170000) !== 0100000 or array_key_exists($relativePath, $plannedFiles)) continue;
      if (!in_array(strtolower(pathinfo($childName, PATHINFO_EXTENSION)), $assetTypes, TRUE) or file_exists($applicationLoc.DIRECTORY_SEPARATOR.$relativePath) or is_link($applicationLoc.DIRECTORY_SEPARATOR.$relativePath)) continue;
      $AssetsStale++;
      $nameWasRemoved = FALSE;
      if ($mayRepair) list ($nameWasRemoved, $removalNotice) = removeFileWithoutFollowing($hostedLoc.DIRECTORY_SEPARATOR.$relativePath);
      if ($nameWasRemoved) $AssetsRemoved++; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $plannedFolder, $childName, $relativePath, $removalNotice, $resolvedPath, $trustNotice, $childNames, $childStatus, $assetTypes, $folderIsTrusted, $nameWasRemoved, $hostedLoc, $applicationLoc, $plannedFiles, $plannedFolders, $mayRepair);
  return array($AssetsRemoved, $AssetsStale); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to give one hosted folder to root, & only the folder.
// / Accepts the folder & a boolean permitting repair. Returns whether it is root's & 0755 now,
// / & whether anything had to change, in that order.
// / The folder above it is already root's, because the hosted location is put right from the
// / top down, so the name cannot be swapped while its owner & mode are set. A name that is not
// / a folder where a folder belongs is removed first, & the folder is made.
function holdHostedFolder($folderPath, $mayRepair) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FolderIsHeld = $FolderChanged = FALSE;
  $folderStatus = array();
  $folderType = 0;
  $ownerNotice = $removalNotice = '';
  $ownerWasChanged = $nameWasRemoved = FALSE;
  clearstatcache(TRUE, (string)$folderPath);
  $folderStatus = @lstat((string)$folderPath);
  if (is_array($folderStatus)) $folderType = ((int)$folderStatus['mode'] & 0170000);
  if (is_array($folderStatus) && $folderType === 0040000 && (int)$folderStatus['uid'] === 0 && (int)$folderStatus['gid'] === 0 && ((int)$folderStatus['mode'] & 07777) === 0755) $FolderIsHeld = TRUE;
  else if ($mayRepair) {
    $FolderChanged = TRUE;
    if (is_array($folderStatus) && $folderType !== 0040000) list ($nameWasRemoved, $removalNotice) = removeFileWithoutFollowing((string)$folderPath);
    if (!is_array($folderStatus) or $nameWasRemoved) @mkdir((string)$folderPath, 0755);
    else if ($folderType === 0040000 && ((int)$folderStatus['uid'] !== 0 or (int)$folderStatus['gid'] !== 0)) list ($ownerWasChanged, $ownerNotice) = changeOwnerWithoutFollowing((string)$folderPath, 'root', 'root');
    clearstatcache(TRUE, (string)$folderPath);
    $folderStatus = @lstat((string)$folderPath);
    if (is_array($folderStatus) && ((int)$folderStatus['mode'] & 0170000) === 0040000 && (int)$folderStatus['uid'] === 0) $FolderIsHeld = @chmod((string)$folderPath, 0755); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderStatus, $folderType, $ownerNotice, $removalNotice, $ownerWasChanged, $nameWasRemoved, $folderPath, $mayRepair);
  return array($FolderIsHeld, $FolderChanged); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to keep the hosted location in step with the installed application.
// / Accepts the hosted location, the application folder & a boolean permitting repair.
// / Returns the status word, a sentence explaining it, & how many names were written & removed,
// / in that order.
// /
// / THE HOSTED LOCATION IS WHAT THE WEB SERVER SERVES, & IT HOLDS NOTHING BUT WHAT IT MUST. That
// / is the loader in place of the entry point, the protection page, the static files a browser
// / fetches, & DATA, which is the web server account's & is never entered here. Every file is
// / written by root through the kernel, so each is root's & 0644, & every folder the files need
// / is root's & 0755. IT IS CHANGED ONLY WHEN IT IS RECOGNIZABLY THIS APPLICATION'S. Its entry
// / point must be the loader, or the installed application's own entry point, as it is straight
// / after an install, or absent. Any other entry point is a release unpacked there & not
// / installed yet, & it is reported & left exactly as it is, because running -fp from it is what
// / installs it. Nothing is removed unless the application holds the same bytes under the same
// / name, so whatever else an administrator keeps there stays. With repair forbidden it only
// / reports, which is what the listener & -v ask for.
function publishHostedLocation($hostedLoc, $applicationLoc, $mayRepair) {
  // / Set variables.
  global $EngineApplicationLayout, $RunningAsRoot, $EnableMemoryProtection;
  $PublishStatus = 'ok';
  $PublishDetail = '';
  $NamesWritten = $NamesRemoved = 0;
  $hostedPath = $applicationPath = $entryName = $hostedEntry = $applicationEntry = $loaderContents = $plannedPath = $sourcePath = $plannedFolder = $holdNotice = $writeNotice = $readNotice = $wantedContents = $hostedContents = $layoutEntry = '';
  $plannedFiles = $plannedFolders = $fileStatus = $layoutEntries = array();
  $repairIsAllowed = $locationsOverlap = $hostedIsHeld = $entryIsOurs = $folderIsHeld = $folderChanged = $fileWasWritten = $wantedWasRead = $hostedWasRead = FALSE;
  $namesDiffering = $namesLeft = $namesFailed = $entryRemoved = $entryLeft = $assetsRemoved = $assetsStale = 0;
  $hostedPath = rtrim((string)$hostedLoc, DIRECTORY_SEPARATOR);
  $applicationPath = rtrim((string)$applicationLoc, DIRECTORY_SEPARATOR);
  $entryName = engineApplicationEntryPoint();
  $hostedEntry = $hostedPath.DIRECTORY_SEPARATOR.$entryName;
  $applicationEntry = $applicationPath.DIRECTORY_SEPARATOR.$entryName;
  $repairIsAllowed = ($mayRepair && $RunningAsRoot);
  $locationsOverlap = ($hostedPath === $applicationPath or strpos($hostedPath.DIRECTORY_SEPARATOR, $applicationPath.DIRECTORY_SEPARATOR) === 0 or strpos($applicationPath.DIRECTORY_SEPARATOR, $hostedPath.DIRECTORY_SEPARATOR) === 0);
  if (isset($EngineApplicationLayout) && is_array($EngineApplicationLayout)) $layoutEntries = $EngineApplicationLayout;
  // / The hosted location is made when it is missing, with any folder missing above it, below the
  // / first folder that exists & only root can change. createHeldFolder makes it 0700 & it is
  // / opened to 0755 at once, because the web server serves it.
  if ($hostedPath !== '' && $repairIsAllowed && !$locationsOverlap && is_file($applicationEntry) && !file_exists($hostedPath) && !is_link($hostedPath) && createHeldFolder($hostedPath) && @chmod($hostedPath, 0755)) $NamesWritten++;
  if ($hostedPath !== '') list ($hostedIsHeld, $holdNotice) = folderIsHeldForRoot($hostedPath);
  if ($hostedPath === '') {
    $PublishStatus = 'UNCONFIGURED';
    $PublishDetail = 'No hosted location is configured.'; }
  else if (!is_file($applicationEntry)) {
    $PublishStatus = 'NOT INSTALLED';
    $PublishDetail = 'Nothing is installed at '.$applicationPath.' yet, so there is nothing to serve.'; }
  else if ($locationsOverlap) {
    $PublishStatus = 'REFUSED';
    $PublishDetail = 'The hosted location '.$hostedPath.' & the installation '.$applicationPath.' overlap, so neither was changed.'; }
  else if (is_link($hostedPath)) {
    $PublishStatus = 'REFUSED';
    $PublishDetail = $hostedPath.' is a link, so it was left as it is.'; }
  else if (!is_dir($hostedPath)) {
    $PublishStatus = 'MISSING';
    $PublishDetail = $hostedPath.' does not exist, so the web server has nothing to serve. Run -fp as root to make it.'; }
  else if (!$hostedIsHeld or !pathIsSafeToModifyRecursively($hostedPath)) {
    $PublishStatus = 'REFUSED';
    $PublishDetail = $hostedPath.' is a system folder or could be replaced by another account, so it was left as it is. '.$holdNotice; }
  else {
    $entryIsOurs = ((!file_exists($hostedEntry) && !is_link($hostedEntry)) or fileIsApplicationLoader($hostedEntry) or filesAreIdentical($hostedEntry, $applicationEntry));
    if (!$entryIsOurs) {
      $PublishStatus = 'UNINSTALLED';
      $PublishDetail = $hostedEntry.' belongs to a release that is not installed, so the hosted location was left as it is. Run  sudo php '.$hostedEntry.' -fp  to install it.'; }
    else {
      list ($plannedFiles, $plannedFolders) = hostedLocationPlan($applicationPath);
      $loaderContents = applicationLoaderContents($applicationEntry);
      // / The hosted location itself first, then every folder the files need, parents first, so
      // / each is root's before anything inside it is touched. Then the static files a release no
      // / longer ships are removed, then every copy of the application, & then the files are written.
      list ($folderIsHeld, $folderChanged) = holdHostedFolder($hostedPath, $repairIsAllowed);
      if ($folderChanged && !$folderIsHeld) $namesFailed++;
      else if (!$folderIsHeld) $namesDiffering++;
      foreach ($plannedFolders as $plannedFolder) {
        list ($folderIsHeld, $folderChanged) = holdHostedFolder($hostedPath.DIRECTORY_SEPARATOR.$plannedFolder, $repairIsAllowed);
        if ($folderChanged && !$folderIsHeld) $namesFailed++;
        else if (!$folderIsHeld) $namesDiffering++; }
      list ($assetsRemoved, $assetsStale) = removeStaleHostedAssets($hostedPath, $applicationPath, $plannedFiles, $plannedFolders, $repairIsAllowed);
      $NamesRemoved = $NamesRemoved + $assetsRemoved;
      foreach ($layoutEntries as $layoutEntry) {
        $layoutEntry = basename((string)$layoutEntry);
        if ($layoutEntry === '' or $layoutEntry === '.' or $layoutEntry === '..') continue;
        list ($entryRemoved, $entryLeft) = removeHostedCopies($hostedPath.DIRECTORY_SEPARATOR.$layoutEntry, $applicationPath.DIRECTORY_SEPARATOR.$layoutEntry, $layoutEntry, $plannedFiles, $repairIsAllowed);
        $NamesRemoved = $NamesRemoved + $entryRemoved;
        $namesLeft = $namesLeft + $entryLeft; }
      foreach ($plannedFiles as $plannedPath => $sourcePath) {
        $wantedWasRead = $hostedWasRead = $fileWasWritten = FALSE;
        $wantedContents = $hostedContents = '';
        if ($sourcePath === '') {
          $wantedWasRead = TRUE;
          $wantedContents = $loaderContents; }
        else list ($wantedWasRead, $wantedContents, $readNotice) = readFileWithoutFollowing($sourcePath, 268435456);
        clearstatcache(TRUE, $hostedPath.DIRECTORY_SEPARATOR.$plannedPath);
        $fileStatus = @lstat($hostedPath.DIRECTORY_SEPARATOR.$plannedPath);
        if (is_array($fileStatus) && ((int)$fileStatus['mode'] & 0170000) === 0100000 && (int)$fileStatus['nlink'] === 1 && (int)$fileStatus['uid'] === 0 && ((int)$fileStatus['mode'] & 07777) === 0644) list ($hostedWasRead, $hostedContents, $readNotice) = readFileWithoutFollowing($hostedPath.DIRECTORY_SEPARATOR.$plannedPath, 268435456);
        if (!$wantedWasRead) $namesFailed++;
        else if ($hostedWasRead && $hostedContents === $wantedContents) continue;
        else if (!$repairIsAllowed) $namesDiffering++;
        else {
          list ($fileWasWritten, $writeNotice) = writeFileWithoutFollowing($hostedPath.DIRECTORY_SEPARATOR.$plannedPath, $wantedContents, 0644);
          if ($fileWasWritten) $NamesWritten++;
          else $namesFailed++; } }
      // / Without repair a stale static file is counted among what was left, & with it the file is
      // / gone before anything was counted.
      if (!$repairIsAllowed) $namesDiffering = $namesDiffering + $namesLeft;
      if ($namesFailed > 0) {
        $PublishStatus = 'FAILED';
        $PublishDetail = $namesFailed.' name(s) in '.$hostedPath.' could not be written. The log says which.'; }
      else if ($namesDiffering > 0) {
        $PublishStatus = 'DIFFERS';
        $PublishDetail = $namesDiffering.' name(s) in '.$hostedPath.' differ from what the installation at '.$applicationPath.' serves. Run -fp as root.'; }
      else if ($namesLeft > 0) {
        $PublishStatus = 'STRAY';
        $PublishDetail = $namesLeft.' name(s) in '.$hostedPath.' are not part of the installation & differ from it, so they were left. Remove them by hand.'; }
      else if ($NamesWritten > 0 or $NamesRemoved > 0) {
        $PublishStatus = 'repaired';
        $PublishDetail = 'Wrote '.$NamesWritten.' & removed '.$NamesRemoved.' name(s). '.$hostedPath.' serves the loader & '.(count($plannedFiles) - 1).' other file(s).'; }
      else $PublishDetail = 'Serves the loader & '.(count($plannedFiles) - 1).' other file(s) from '.$applicationPath.'.'; } }
  if (in_array($PublishStatus, array('FAILED', 'REFUSED', 'UNINSTALLED', 'STRAY'), TRUE) && $repairIsAllowed) warningEntry('The hosted location reported '.$PublishStatus.'. '.$PublishDetail);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $hostedPath, $applicationPath, $entryName, $hostedEntry, $applicationEntry, $loaderContents, $plannedPath, $sourcePath, $plannedFolder, $holdNotice, $writeNotice, $readNotice, $wantedContents, $hostedContents, $layoutEntry, $plannedFiles, $plannedFolders, $fileStatus, $layoutEntries, $repairIsAllowed, $locationsOverlap, $hostedIsHeld, $entryIsOurs, $folderIsHeld, $folderChanged, $fileWasWritten, $wantedWasRead, $hostedWasRead, $namesDiffering, $namesLeft, $namesFailed, $entryRemoved, $entryLeft, $assetsRemoved, $assetsStale, $hostedLoc, $applicationLoc, $mayRepair);
  return array($PublishStatus, $PublishDetail, $NamesWritten, $NamesRemoved); }
// / -----------------------------------------------------------------------------------
