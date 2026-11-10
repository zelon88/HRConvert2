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
// / v4.0.6.
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
$SetupCoreVersion = 'v4.0.6';
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
  if (!$sectionModel['Writable']) print('   Display only. This utility never writes this section.'.$Lol);
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
  $LimitsWereEnabled = $limitsSystemdUsable = $dropInIsUnchanged = FALSE;
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
    // / systemd keeps a file for each account that lingers, so one that already does is left as it is.
    // / Until v4.0.6 every -fp enabled it again.
    if (is_file('/var/lib/systemd/linger/'.$ApacheUser)) {
      $StepsCompleted++;
      print('  Present    Lingering for '.$ApacheUser.$Lol); }
    else {
      exec('loginctl enable-linger '.escapeshellarg($ApacheUser).' 2>&1', $commandOutput, $commandExitCode);
      if ($commandExitCode !== 0) print('  FAILED     Could not enable lingering for '.$ApacheUser.'.'.$Lol);
      else {
        $StepsCompleted++;
        print('  Enabled    Lingering for '.$ApacheUser.$Lol); } }
    // / Lingering is enabled & that is not the same as a limit being possible.
    // / A kernel that delegates no cgroup controllers cannot hold a per conversion limit no
    // / matter how the accounts are configured, & many NAS & appliance kernels are built
    // / that way deliberately.
    // / Saying so HERE, while an administrator is watching a repair run, is worth more than
    // / saying it in a log they read after a conversion behaved oddly.
    if (function_exists('cgroupDelegationIsAvailable') && !cgroupDelegationIsAvailable()) {
      print('  Note       No cgroup controllers are delegated. Per conversion limits cannot'.$Lol);
      print('             apply. Conversions are bounded by scheduling priority.'.$Lol);
      warningEntry('This kernel delegates no cgroup controllers, so per conversion limits fall back to scheduling priority.'); }
    // / A user manager is given the memory & pids controllers by default. The processor
    // / controller has to be delegated explicitly or CPUQuota is silently ignored.
    $dropInDirectory = '/etc/systemd/system/user@.service.d';
    $dropInFile = $dropInDirectory.'/'.engineApplicationSlug().'-delegate.conf';
    $dropInContents = '[Service]'.PHP_EOL.'Delegate=cpu cpuset io memory pids'.PHP_EOL;
    if (!is_dir($dropInDirectory)) @mkdir($dropInDirectory, 0755, TRUE);
    if (!is_dir($dropInDirectory)) print('  FAILED     Could not create '.$dropInDirectory.'.'.$Lol);
    // / A drop in that already says this is left alone, as the listener unit is. Until v4.0.5 it
    // / was written on every -fp, systemd reloaded every unit on the host for it, & the operator was
    // / told every time to restart a user manager that already had the delegation.
    else if (is_file($dropInFile) && !is_link($dropInFile) && (string)@file_get_contents($dropInFile) === $dropInContents) {
      $StepsCompleted++;
      $dropInIsUnchanged = TRUE;
      print('  Unchanged  '.$dropInFile.$Lol); }
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
      // / Only a drop in written just now needs a running user manager restarted.
      if (!$dropInIsUnchanged) {
        print('  Note       A running user manager must be restarted before delegation applies.'.$Lol);
        print('             systemctl restart user@$(id -u '.$ApacheUser.').service'.$Lol); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dropInDirectory, $dropInFile, $dropInContents, $dropInIsUnchanged, $commandOutput, $commandExitCode, $bytesWritten, $limitsSystemdUsable, $limitsSystemdReason);
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
// / A function to list, inside one installation, the paths the contract says no release ships.
// / Accepts the installation. Returns each path in full.
// / The contract names each in $EngineRetiredPaths, by its path below the installation. One that
// / is not a string, or is empty, is not a path. One that is absolute, or has a part that is
// / empty, . or .., could reach outside the installation. Each is refused & warned about, &
// / nothing is done for it.
function retiredPathsIn($treePath) {
  // / Set variables.
  global $EngineRetiredPaths, $EnableMemoryProtection;
  $RetiredPaths = array();
  $retiredPath = $pathPart = '';
  $pathParts = array();
  $pathIsValid = FALSE;
  $treePath = rtrim((string)$treePath, DIRECTORY_SEPARATOR);
  foreach (((isset($EngineRetiredPaths) && is_array($EngineRetiredPaths)) ? $EngineRetiredPaths : array()) as $retiredPath) {
    $pathIsValid = (is_string($retiredPath) && $retiredPath !== '' && substr($retiredPath, 0, 1) !== '/' && strpos($retiredPath, "\0") === FALSE && $treePath !== '');
    $pathParts = $pathIsValid ? explode('/', $retiredPath) : array();
    foreach ($pathParts as $pathPart) if ($pathPart === '' or $pathPart === '.' or $pathPart === '..') $pathIsValid = FALSE;
    if ($pathIsValid) $RetiredPaths[] = $treePath.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $pathParts);
    else warningEntry('The contract names '.(is_string($retiredPath) ? $retiredPath : gettype($retiredPath)).' among the paths no release ships, & it is not a path inside the installation, so nothing was done for it.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $RetiredPaths is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $retiredPath, $pathPart, $pathParts, $pathIsValid, $treePath);
  return $RetiredPaths; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to take away, from an installation, the paths no release of this application ships.
// / Accepts the paths in full, as retiredPathsIn() lists them. Returns a sanity boolean, which is
// / TRUE when none of them is left.
// / Each is removed by removeTreeWithoutFollowing() in the Kernel, which follows nothing in it &
// / makes every folder in it root's alone before listing it. That needs the folder holding each
// / path to be root's alone already, so correctManagedLocations() calls this once applyCodeRole()
// / has given the rest of the installation to root, & leaves these paths out of that walk. Each
// / one taken away is printed, & each one left is warned about with what to do.
function removeRetiredPaths($retiredPaths) {
  // / Set variables.
  global $Lol, $EnableMemoryProtection;
  $RetiredPathsAreGone = TRUE;
  $retiredPath = $removalNotice = '';
  $pathIsRemoved = FALSE;
  foreach ($retiredPaths as $retiredPath) {
    clearstatcache(TRUE, $retiredPath);
    if (!file_exists($retiredPath) && !is_link($retiredPath)) continue;
    list ($pathIsRemoved, $removalNotice) = removeTreeWithoutFollowing($retiredPath);
    if ($pathIsRemoved) {
      print('  '.str_pad('REMOVED', 12).$retiredPath.', which no release of '.engineApplicationName().' ships.'.$Lol);
      logEntry('Removed '.$retiredPath.' from the installation, because no release ships it.'); }
    else {
      $RetiredPathsAreGone = FALSE;
      print('  '.str_pad('NOT REMOVED', 12).$retiredPath.'. '.$removalNotice.$Lol);
      warningEntry($retiredPath.' is in the installation, no release ships it, & it could not be taken away. '.$removalNotice.' Remove it & run -fp again.'); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $RetiredPathsAreGone is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $retiredPath, $removalNotice, $pathIsRemoved, $retiredPaths);
  return $RetiredPathsAreGone; }
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
// / A function to give the folder an application keeps its secrets in to root, so that only the
// / web server account's group may read what is in it.
// / Accepts the folder & the web server account. Returns TRUE when the folder has its role.
// / ROOT OWNS IT & DOES NOT WALK IT. The folder is root's, its group is the web server account's
// / & it is 0750, so that account may read what root keeps there & can change none of it, & no
// / other account may even list it. Root writes each secret in it through the kernel, as a new
// / file with the mode that secret needs, so only the folder itself is corrected here. The owner
// / & group are changed by changeOwnerWithoutFollowing, which never follows a link. The mode is
// / set by name, which is safe because the folder above it must be one only root can change, as
// / the data root is, so the folder cannot be renamed aside between the owner change & this.
// / A folder whose owner & group cannot both be given is left without its role & its mode is not
// / touched, & the application never reads a secret first from a folder without its role, so a
// / secret that account cannot read is never the one it relies on.
function applySecretRole($folderPath, $webAccount) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RoleWasApplied = $parentIsTrusted = $ownerWasChanged = $modeWasChanged = $folderIsTrusted = FALSE;
  $folderStatus = array();
  $ownerNotice = $resolvedParent = $resolvedFolder = $trustNotice = '';
  clearstatcache(TRUE, $folderPath);
  $folderStatus = @lstat($folderPath);
  list ($parentIsTrusted, $resolvedParent, $trustNotice) = pathIsTrustedByRoot(dirname($folderPath));
  if (!is_array($folderStatus) or ((int)$folderStatus['mode'] & 0170000) !== 0040000) warningEntry('The secrets folder at '.$folderPath.' is not a folder, & a link is never followed, so it was left alone.');
  // / The folder above is checked before anything is changed, so a folder refused for sitting
  // / where another account could replace it is left exactly as it was.
  else if (!$parentIsTrusted) warningEntry('The secrets folder at '.$folderPath.' sits where another account could replace it, so it was left alone. '.$trustNotice);
  else {
    list ($ownerWasChanged, $ownerNotice) = changeOwnerWithoutFollowing($folderPath, 'root', (string)$webAccount);
    if (!$ownerWasChanged) warningEntry($ownerNotice.' The web server account cannot read the secrets in it until it is.');
    else $modeWasChanged = @chmod($folderPath, 0750);
    if ($ownerWasChanged && !$modeWasChanged) warningEntry('The secrets folder at '.$folderPath.' belongs to root, & its mode could not be set to 0750.');
    list ($folderIsTrusted, $resolvedFolder, $trustNotice) = pathIsTrustedByRoot($folderPath);
    if ($ownerWasChanged && $modeWasChanged && $folderIsTrusted) $RoleWasApplied = TRUE;
    else if ($ownerWasChanged && $modeWasChanged) warningEntry('The secrets folder at '.$folderPath.' still cannot be trusted. '.$trustNotice); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderStatus, $parentIsTrusted, $ownerWasChanged, $modeWasChanged, $folderIsTrusted, $ownerNotice, $resolvedParent, $resolvedFolder, $trustNotice, $folderPath, $webAccount);
  return $RoleWasApplied; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether a location differs from what its role makes it.
// / Accepts the location, its role, the web server account & the paths a walk leaves out, in that
// / order. Returns TRUE when anything the role would change is not so already.
// / -fp counts the locations it changed by this, so a run that changes nothing says so. Until v4.0.6
// / it counted every location it gave a role, & every run said it had corrected all of them.
// / A tree a role walks is read by GNU find, which changes nothing, never follows a link & never
// / leaves the filesystem it started on, & stops at the first name that differs. A folder a role
// / does not walk is judged by itself. A role this does not know is taken to differ.
function locationDiffersFromRole($locationPath, $roleName, $webAccount, $prunedPaths) {
  // / Set variables.
  global $EnableMemoryProtection;
  $LocationDiffers = TRUE;
  $folderStatus = $webRecord = $groupRecord = $commandOutput = array();
  $pruneExpression = $prunedPath = $findPrefix = $folderMode = $fileMode = '';
  $commandExitCode = 1;
  $webUid = $webGid = -1;
  $folderPermissions = 0;
  if (function_exists('posix_getpwnam')) $webRecord = @posix_getpwnam((string)$webAccount);
  if (function_exists('posix_getgrnam')) $groupRecord = @posix_getgrnam((string)$webAccount);
  if (is_array($webRecord) && isset($webRecord['uid'])) $webUid = (int)$webRecord['uid'];
  if (is_array($groupRecord) && isset($groupRecord['gid'])) $webGid = (int)$groupRecord['gid'];
  else if (is_array($webRecord) && isset($webRecord['gid'])) $webGid = (int)$webRecord['gid'];
  clearstatcache(TRUE, (string)$locationPath);
  $folderStatus = @lstat((string)$locationPath);
  if (is_array($folderStatus)) $folderPermissions = ((int)$folderStatus['mode'] & 07777);
  if (!is_array($folderStatus)) $LocationDiffers = TRUE;
  else if ($roleName === 'code' or $roleName === 'root-only') {
    $folderMode = ($roleName === 'code') ? '0755' : '0700';
    $fileMode = ($roleName === 'code') ? '0644' : '0600';
    $findPrefix = 'PATH=/usr/sbin:/usr/bin:/sbin:/bin find '.escapeshellarg((string)$locationPath).' -xdev';
    // / -path reads a pattern, so a bracket, star or question mark in a real path is escaped.
    foreach ((array)$prunedPaths as $prunedPath) $pruneExpression .= ' -path '.escapeshellarg(addcslashes((string)$prunedPath, '*?[]\\')).' -prune -o';
    // / The walk gives every name but a file with more than one name to root, & sets the mode of
    // / every folder & of every file with one name, so those are what is compared.
    exec($findPrefix.$pruneExpression.' \( \( ! \( -type f -links +1 \) \( ! -user 0 -o ! -group 0 \) \) -o \( -type d ! -perm '.$folderMode.' \) -o \( -type f -links 1 ! -perm '.$fileMode.' \) \) -print -quit 2>/dev/null', $commandOutput, $commandExitCode);
    $LocationDiffers = ($commandExitCode !== 0 or count($commandOutput) > 0); }
  else if ($roleName === 'data-root') $LocationDiffers = ((int)$folderStatus['uid'] !== 0 or (int)$folderStatus['gid'] !== 0 or $folderPermissions !== 0755);
  else if ($roleName === 'secret') $LocationDiffers = ((int)$folderStatus['uid'] !== 0 or (int)$folderStatus['gid'] !== $webGid or $folderPermissions !== 0750);
  else if ($roleName === 'web' or $roleName === 'served') $LocationDiffers = ((int)$folderStatus['uid'] !== $webUid or (int)$folderStatus['gid'] !== $webGid or $folderPermissions !== 0700);
  // / A shared folder is changed only where the web server account could change it.
  else if ($roleName === 'shared') $LocationDiffers = ((int)$folderStatus['uid'] === $webUid or ($folderPermissions & 0022) !== 0);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderStatus, $webRecord, $groupRecord, $commandOutput, $pruneExpression, $prunedPath, $findPrefix, $folderMode, $fileMode, $commandExitCode, $webUid, $webGid, $folderPermissions, $locationPath, $roleName, $webAccount, $prunedPaths);
  return $LocationDiffers; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to give every location the application declares the owner & mode its role needs.
// / Accepts the web server account. Must be run as root.
// / Returns whether the pass ran, how many locations it changed & how many were refused. A location
// / that already had what its role gives it is not counted. Until v4.0.6 every one given a role was.
// /
// / THE CONTRACT SAYS WHAT A LOCATION IS, & this decides what that means.
// / An application names each location by the setting that holds it & gives it a role in
// / $EngineManagedLocations. Every owner & mode is fixed here & nowhere else, so an
// / application cannot ask for one that undoes the rest of this.
// /   code       The installation. root, 0755 & 0644. The web server account reads it.
// /   data-root  The data location itself. root, 0755, & never walked.
// /   secret     The folder secrets are kept in. root, the web server account's group,
// /              0750, & never walked.
// /   web        One folder the web server account owns & writes. 0700, & never walked.
// /   served     A web folder the web server serves to browsers. Its owner & mode are the
// /              web role's, & verifyApacheConfiguration() gives it the rules that hand
// /              every file in it over as an inert download. One that is not inside the
// /              hosted location is refused.
// /   root-only  root alone, 0700 & 0600. The previous installation kept for an operator.
// /   shared     A folder other software shares. Checked, never walked.
// / The data root comes before the installation, which sits inside it as Application, & before
// / the secret role, because the folder above each must be root's before root walks it or sets
// / its mode by name. Until v4.0.4 the installation came first, so -fp over a data location an
// / old chown -R had given away refused the installation, & had to be run a second time.
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
// / above one. A location with another role inside it is left out of the walk, & so is a path
// / the contract names in $EngineRetiredPaths, which is taken away once the rest is root's.
function correctManagedLocations($webAccount) {
  // / Set variables.
  global $EngineManagedLocations, $RunningAsRoot, $Lol, $EnableMemoryProtection;
  $CorrectionRan = FALSE;
  $LocationsCorrected = $LocationsRefused = 0;
  $roleOrder = array('shared', 'data-root', 'code', 'secret', 'root-only', 'web', 'served');
  $locationRoles = $resolvedLocations = $prunedPaths = $sharedPaths = $otherLocation = $seenLocations = $servedPaths = $retiredPaths = array();
  $settingName = $locationRole = $roleName = $locationPath = $runningInstall = $configuredPath = $holdNotice = $retiredPath = '';
  $roleWasApplied = $treeIsHeld = $retiredPathsAreGone = $locationDiffered = FALSE;
  if (!$RunningAsRoot) warningEntry('Managed locations can only be corrected by root, so nothing was changed.');
  else if (!isset($EngineManagedLocations) or !is_array($EngineManagedLocations)) warningEntry('The contract declares no managed locations in $EngineManagedLocations, so nothing was corrected.');
  else {
    $CorrectionRan = TRUE;
    $runningInstall = (string)@realpath(dirname(__DIR__, 3));
    // / A served location is given its role only where it is served. classifyServedLocations() says where.
    $servedPaths = servedLocations();
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
        if ($roleName === 'served' && !in_array($locationPath, $servedPaths, TRUE)) {
          print('  '.str_pad('REFUSED', 12).$locationPath.' has the served role & is not inside the hosted location, so it is not served.'.$Lol);
          warningEntry('The managed location '.$locationRoles[2].' at '.$locationPath.' has the served role & is not inside the hosted location, so it was refused. Nothing is written for it & its exposure is not tested.');
          $LocationsRefused++;
          continue; }
        if ($roleName === 'code' && $locationPath !== $runningInstall) {
          print('  '.str_pad('REFUSED', 12).$locationPath.' is not the installation that is running, '.$runningInstall.'.'.$Lol);
          warningEntry('The managed location '.$locationRoles[2].' is '.$locationPath.', which is not the installation that is running, '.$runningInstall.'. It was refused.');
          $LocationsRefused++;
          continue; }
        // / Root walks a code or root-only tree, so the tree itself must be one nothing else can
        // / replace partway through, which means every folder above it is root's. The shared &
        // / data-root roles run first, so an installation an earlier release or an old chown -R gave
        // / away has its folder back by now.
        if ($roleName === 'code' or $roleName === 'root-only') list ($treeIsHeld, $holdNotice) = folderIsHeldForRoot($locationPath);
        // / The backup sits in the data location by default, which the web server account owns, &
        // / every release before v4.0.1 left one there that the account owns too. Root keeps
        // / nothing there & walks nothing there, so it is skipped & left exactly as it is. That is
        // / not a fault in any location, so --setup goes on, & the updater keeps no backup there.
        if ($roleName === 'root-only' && !$treeIsHeld) {
          print('  '.str_pad('SKIPPED', 12).$locationPath.' could be replaced by another account. Left as it is. '.$holdNotice.$Lol);
          warningEntry('The managed location '.$locationRoles[2].' at '.$locationPath.' could be replaced by another account, so root did not walk it & keeps nothing there. '.$holdNotice);
          continue; }
        if ($roleName === 'code' && !$treeIsHeld) {
          print('  '.str_pad('REFUSED', 12).$locationPath.' could be replaced by another account & was not walked. '.$holdNotice.$Lol);
          warningEntry('The managed location '.$locationRoles[2].' at '.$locationPath.' could be replaced by another account, so root did not walk it. '.$holdNotice);
          $LocationsRefused++;
          continue; }
        // / Whether anything is about to change is read first, because once the role is given it is too late.
        $locationDiffered = ($roleName === 'code') ? FALSE : locationDiffersFromRole($locationPath, $roleName, $webAccount, array());
        if ($roleName === 'shared') $roleWasApplied = applySharedRole($locationPath, $webAccount);
        else if ($roleName === 'data-root') $roleWasApplied = applyDataRootRole($locationPath);
        else if ($roleName === 'secret') $roleWasApplied = applySecretRole($locationPath, $webAccount);
        else if ($roleName === 'code') {
          // / Every other declared location inside the installation keeps its own role.
          $prunedPaths = array();
          foreach ($resolvedLocations as $otherLocation) {
            if ($otherLocation[0] !== $locationPath && strpos($otherLocation[0].DIRECTORY_SEPARATOR, $locationPath.DIRECTORY_SEPARATOR) === 0) $prunedPaths[] = $otherLocation[0]; }
          // / WHAT NO RELEASE SHIPS IS LEFT OUT OF THE WALK. It is taken away once the rest is root's.
          // / Walking it first would spend the time on a tree about to go, & a tree an earlier build
          // / left can hold links & files with more than one name, which the walk cannot put right.
          // / Until v4.0.6 nothing took one away, so an installation holding one stayed refused.
          $retiredPaths = retiredPathsIn($locationPath);
          foreach ($retiredPaths as $retiredPath) $prunedPaths[] = $retiredPath;
          // / A path no release ships is about to be taken away, which is a change too.
          $locationDiffered = locationDiffersFromRole($locationPath, $roleName, $webAccount, $prunedPaths);
          foreach ($retiredPaths as $retiredPath) if (file_exists($retiredPath) or is_link($retiredPath)) $locationDiffered = TRUE;
          $roleWasApplied = applyCodeRole($locationPath, $prunedPaths);
          $retiredPathsAreGone = removeRetiredPaths($retiredPaths);
          // / An installation still holding one is not root's alone, so the location is incomplete.
          if (!$retiredPathsAreGone) $roleWasApplied = FALSE; }
        else if ($roleName === 'root-only') $roleWasApplied = applyRootOnlyRole($locationPath);
        // / A served location is owned & moded exactly as a web one. What makes it served is the
        // / Apache configuration, which is written from the same contract.
        else $roleWasApplied = applyWebRole($locationPath, $webAccount);
        if (!$roleWasApplied) $LocationsRefused++;
        else if ($locationDiffered) $LocationsCorrected++; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $roleOrder, $locationRoles, $resolvedLocations, $prunedPaths, $sharedPaths, $otherLocation, $seenLocations, $servedPaths, $retiredPaths, $settingName, $locationRole, $roleName, $locationPath, $runningInstall, $configuredPath, $holdNotice, $retiredPath, $roleWasApplied, $treeIsHeld, $retiredPathsAreGone, $locationDiffered, $webAccount);
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
    print($Lol.'No config.php was found. Writing the defaults from the template.'.$Lol);
    list ($generatedOk, $generatedCount) = generateConfigFile($suppliedPath !== '' ? $suppliedPath : $InstLoc.$DirSep.'Resources'.$DirSep.'config.php');
    if ($generatedOk) {
      $UtilityCompleted = TRUE;
      print($Lol.'Wrote '.$generatedCount.' setting(s), all defaults. Change them with --config.'.$Lol.$Lol); }
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
          print('This file does not have the expected shape. Nothing was written.'.$Lol);
          print('Edit it by hand, or restore a backup.'.$Lol.$Lol); }
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
// / MORE THAN ONE FOLDER IS MADE ONLY IN A FOLDER ROOT ALONE CAN WRITE. The ancestor may belong to
// / another account, which is safe for the one name made in it, but the folders between are made
// / one after another, & that account could swap each for a link before the next is made in it.
// / A deeper folder below one another account can write, as /var/log is for syslog on Ubuntu, is
// / made by hand. Only root is judged, as folderIsHeldForRoot() judges only root.
function createHeldFolder($folderPath) {
  // / Set variables.
  global $DirSep, $RunningAsRoot, $EnableMemoryProtection;
  $FolderExists = $ancestorIsHeld = $ancestorIsTrusted = FALSE;
  $existingAncestor = $holdNotice = $resolvedAncestor = $trustNotice = '';
  if ((string)$folderPath === '' or is_dir($folderPath)) $FolderExists = (is_dir($folderPath));
  else {
    $existingAncestor = dirname($folderPath);
    while ($existingAncestor !== $DirSep && $existingAncestor !== '.' && !file_exists($existingAncestor) && !is_link($existingAncestor)) $existingAncestor = dirname($existingAncestor);
    list ($ancestorIsHeld, $holdNotice) = folderIsHeldForRoot($existingAncestor);
    if ($ancestorIsHeld && $RunningAsRoot && $existingAncestor !== dirname($folderPath)) {
      list ($ancestorIsTrusted, $resolvedAncestor, $trustNotice) = pathIsTrustedByRoot($existingAncestor);
      if (!$ancestorIsTrusted) {
        $ancestorIsHeld = FALSE;
        $holdNotice = 'More than one folder would be made below '.$existingAncestor.', which another account could change. Make it by hand. '.$trustNotice; }
      else if ($resolvedAncestor !== $existingAncestor) {
        $ancestorIsHeld = FALSE;
        $holdNotice = 'More than one folder would be made below '.$existingAncestor.', which leads to '.$resolvedAncestor.'. Name the real path, or make it by hand.'; } }
    if ($ancestorIsHeld) {
      @mkdir(dirname($folderPath), 0755, TRUE);
      @mkdir($folderPath, 0700); }
    if (is_dir($folderPath)) $FolderExists = TRUE;
    else warningEntry('The folder at '.$folderPath.' could not be created. '.($ancestorIsHeld ? '' : $holdNotice)); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $ancestorIsHeld, $ancestorIsTrusted, $existingAncestor, $holdNotice, $resolvedAncestor, $trustNotice, $folderPath);
  return $FolderExists; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name some of a list of names in one sentence.
// / Accepts the names. Returns a sentence naming up to ten of them, or nothing for none.
// / A folder can hold thousands of names, & a log line that lists them all is never read.
function namesToList($entryNames) {
  // / Set variables.
  global $EnableMemoryProtection;
  $NameSentence = '';
  $shownNames = array();
  if (is_array($entryNames) && count($entryNames) > 0) {
    $shownNames = array_slice(array_values($entryNames), 0, 10);
    $NameSentence = (count($entryNames) > count($shownNames) ? 'The first '.count($shownNames).' are ' : 'They are ').implode(', ', $shownNames).'.'; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $NameSentence is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $shownNames, $entryNames);
  return $NameSentence; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to create the data root's folders & move an older layout's sessions into them.
// / Accepts the web server account. Must run as root. Returns whether the primary folders are
// / present & how many session directories were moved, in that order.
// / THE DATA ROOT IS A ROOT ROOT OWNS, & each kind of file lives in a folder of its own below.
// / This creates the primary folders — Sessions, Cache, Logs, Engine, Secrets & the socket
// / folder — & their roles are set by correctManagedLocations, which runs straight after this
// / in both --setup & -fp. Each additional data location is a root of its own the contract does
// / not name, so its root & its Sessions folder are created & given their roles here.
// / Secrets is created here, given its role by correctManagedLocations, & nothing is put in it.
// / The application keeps its own secrets there, & copies an older release's key in from the top
// / of the root itself, because only the application knows what one is & how to read it back.
// / AN OLDER RELEASE KEPT EACH SESSION DIRECTLY IN A DATA LOCATION. Every session directory
// / found at the top of a data root is moved into that root's Sessions folder, so no open
// / session is lost, which is why -fp after an upgrade keeps visitors signed in. Each moved
// / directory keeps the web server account's ownership it already had. Only a folder that is a
// / session is moved, & every other is named, as is anything in Sessions the sweep cannot remove.
// / The capability cache an older release left at the primary root is moved into the cache
// / folder with it, & one left anywhere else is removed.
// / EACH ROOT IS GIVEN BACK TO ROOT BEFORE ANYTHING IS MOVED BELOW IT. A rename follows a link
// / in the folder it moves into, & until v4.0.4 the primary root was given back only by
// / correctManagedLocations, after this, & Sessions & Cache were judged with is_dir, which follows
// / a link. In a data location the web server account still owned, as v4.0.0 & an old chown -R
// / leave one, that account could swap either for a link, & root moved a folder or file of that
// / account's wherever the link led. Nothing is moved through a link now. A move is made only into
// / a folder folderIsHeldForRoot holds, which is a real folder in a root no other account can
// / change, & one that is not is named & nothing is moved into it.
function provisionAndMigrateDataRoot($webAccount) {
  // / Set variables.
  global $DataRootLoc, $SessionLoc, $CacheLoc, $LogDir, $EngineDir, $SecretLoc, $ManagerSocketDir, $AdditionalConvertLocs, $DirSep, $Lol, $RunningAsRoot, $EnableMemoryProtection;
  $FoldersAreReady = TRUE;
  $MovedCount = 0;
  $primaryFolders = $additionalEntry = $additionalRoots = $rootEntries = $sessionEntries = $cacheStatus = $foreignEntries = $sessionNames = $strangerEntries = $entryStatus = $webRecord = array();
  $folderPath = $additionalRoot = $rootToMigrate = $sessionTarget = $rootEntry = $entryPath = $holdNotice = $cacheSource = $cacheTarget = $sessionName = $removalNotice = '';
  $targetIsHeld = $cacheIsHeld = $rootWasTaken = $cacheWasRemoved = FALSE;
  $webUid = -1;
  $reservedNames = array('.', '..', 'Sessions', 'Cache', 'Logs', 'Sockets', 'Engine', 'Secrets', 'Last-Installed-Version', 'Application', 'lost+found', '.cache', '.config');
  if (!$RunningAsRoot) { warningEntry('The data root can only be provisioned while running as root, so nothing was created or moved.'); $FoldersAreReady = FALSE; }
  else {
    // / Create the primary root & give it back to root first, so every folder below it is
    // / created, & every session moved, beneath a folder no other account can change.
    if ((string)$DataRootLoc !== '' && createHeldFolder($DataRootLoc)) $rootWasTaken = applyDataRootRole($DataRootLoc);
    $primaryFolders = array($DataRootLoc, $SessionLoc, $CacheLoc, $LogDir, $EngineDir, $SecretLoc, $ManagerSocketDir);
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
      if (createHeldFolder($additionalRoot) && applyDataRootRole($additionalRoot) && createHeldFolder($additionalRoot.$DirSep.'Sessions')) applyWebRole($additionalRoot.$DirSep.'Sessions', $webAccount); }
    // / Move an older layout's sessions into each root's Sessions folder. The primary root &
    // / every additional root is swept once. A session is moved by rename, which never crosses a
    // / filesystem & never copies, so a large session moves in an instant & intact.
    if (function_exists('posix_getpwnam')) $webRecord = @posix_getpwnam((string)$webAccount);
    if (is_array($webRecord) && isset($webRecord['uid'])) $webUid = (int)$webRecord['uid'];
    foreach (array_merge(array($DataRootLoc), $additionalRoots) as $rootToMigrate) {
      $sessionTarget = $rootToMigrate.$DirSep.'Sessions';
      $rootEntries = @scandir($rootToMigrate);
      if (!is_array($rootEntries)) continue;
      // / The installation is a folder at the root since v4.0.1, & a name beginning with a dot
      // / is never a session, which is how a staged installation an install left is passed over.
      // / A link is never followed into & is left exactly where it is, as is any plain file,
      // / which at the root is the Environment Manager's state or, from an older release, the
      // / install secret or the capability cache.
      // / A SESSION IS A FOLDER THE WEB SERVER ACCOUNT MADE, named as the core names a day, with twelve
      // / or eighteen hexadecimal digits. Until v4.0.6 any other folder was moved too, so a release
      // / unpacked into a data root had its folders moved into Sessions as root's, where the sweep,
      // / which runs as the web server account, could not remove them & failed every pass. Every
      // / other folder is named & left where it is.
      $sessionEntries = $foreignEntries = array();
      foreach ($rootEntries as $rootEntry) {
        if (in_array($rootEntry, $reservedNames, TRUE) or strpos((string)$rootEntry, '.') === 0) continue;
        $entryPath = $rootToMigrate.$DirSep.$rootEntry;
        clearstatcache(TRUE, $entryPath);
        $entryStatus = @lstat($entryPath);
        if (!is_array($entryStatus) or ((int)$entryStatus['mode'] & 0170000) !== 0040000) continue;
        if ($webUid >= 0 && (int)$entryStatus['uid'] === $webUid && preg_match('/^([0-9a-f]{12}|[0-9a-f]{18})$/', (string)$rootEntry) === 1) $sessionEntries[] = $rootEntry;
        else $foreignEntries[] = $rootEntry; }
      if (count($foreignEntries) > 0) warningEntry(count($foreignEntries).' folder(s) at the top of '.$rootToMigrate.' are not sessions, so they were left where they are. '.namesToList($foreignEntries).' Move them out of the data location.');
      // / Sessions must be a real folder in a root only root can change, or nothing is moved. A
      // / primary root that could not be given back is never moved into, & applyDataRootRole has
      // / already said why.
      list ($targetIsHeld, $holdNotice) = folderIsHeldForRoot($sessionTarget);
      $targetIsHeld = ($targetIsHeld && ($rootToMigrate !== $DataRootLoc or $rootWasTaken));
      if (count($sessionEntries) > 0 && !$targetIsHeld) warningEntry(count($sessionEntries).' older session directory or directories at the top of '.$rootToMigrate.' were not moved into Sessions, because root moves nothing into a folder another account could replace. '.$holdNotice);
      else foreach ($sessionEntries as $rootEntry) {
        $entryPath = $rootToMigrate.$DirSep.$rootEntry;
        if (@rename($entryPath, $sessionTarget.$DirSep.$rootEntry)) $MovedCount++;
        else warningEntry('A session directory at '.$entryPath.' could not be moved into '.$sessionTarget.'. It was left where it is.'); }
      // / ANYTHING IN Sessions THE WEB SERVER ACCOUNT DOES NOT OWN is beyond the sweep, which runs as that
      // / account & leaves it. So it is named here, where root sees it. An earlier release moved some.
      // / Names are read & nothing is changed, because the folder is that account's.
      $sessionNames = @scandir($sessionTarget);
      $strangerEntries = array();
      if (is_array($sessionNames) && $webUid >= 0) foreach ($sessionNames as $sessionName) {
        if ($sessionName === '.' or $sessionName === '..') continue;
        $entryStatus = @lstat($sessionTarget.$DirSep.$sessionName);
        if (is_array($entryStatus) && (int)$entryStatus['uid'] !== $webUid) $strangerEntries[] = (string)$sessionName; }
      if (count($strangerEntries) > 0) warningEntry(count($strangerEntries).' name(s) in '.$sessionTarget.' are not '.$webAccount.'\'s, so the sweep leaves them. '.namesToList($strangerEntries).' Remove them.');
      // / The capability cache an older release wrote at the root moves into the cache folder,
      // / which is where this release reads & writes it. It moves only when it is a plain file &
      // / the cache folder is held, for the same reason. One at the top of any other root, or one
      // / the cache folder already holds a cache for, is a copy nothing reads, & is removed. Until
      // / v4.0.6 only the primary root's was looked at.
      $cacheSource = $rootToMigrate.$DirSep.'capability-cache.php';
      $cacheTarget = $CacheLoc.$DirSep.'capability-cache.php';
      clearstatcache(TRUE, $cacheSource);
      $cacheStatus = @lstat($cacheSource);
      if (is_array($cacheStatus) && ((int)$cacheStatus['mode'] & 0170000) === 0100000) {
        if ($rootToMigrate === $DataRootLoc && !file_exists($cacheTarget) && !is_link($cacheTarget)) {
          list ($cacheIsHeld, $holdNotice) = folderIsHeldForRoot($CacheLoc);
          if ($cacheIsHeld) @rename($cacheSource, $cacheTarget);
          else warningEntry('The capability cache at '.$cacheSource.' was not moved into '.$CacheLoc.', because root moves nothing into a folder another account could replace. '.$holdNotice); }
        else {
          list ($cacheWasRemoved, $removalNotice) = removeFileWithoutFollowing($cacheSource);
          if ($cacheWasRemoved) logEntry('Removed the capability cache an older release left at '.$cacheSource.'. Nothing reads it.');
          else warningEntry('The capability cache an older release left at '.$cacheSource.' could not be removed. '.$removalNotice); } } }
    if ($MovedCount > 0) logEntry($MovedCount.' session directory or directories were moved into a Sessions folder during the data root migration.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $primaryFolders, $additionalEntry, $additionalRoots, $rootEntries, $sessionEntries, $cacheStatus, $foreignEntries, $sessionNames, $strangerEntries, $entryStatus, $webRecord, $folderPath, $additionalRoot, $rootToMigrate, $sessionTarget, $rootEntry, $entryPath, $holdNotice, $cacheSource, $cacheTarget, $sessionName, $removalNotice, $targetIsHeld, $cacheIsHeld, $rootWasTaken, $cacheWasRemoved, $webUid, $reservedNames, $webAccount);
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
  global $ApacheUser, $RunningAsRoot, $Lol, $EnableMemoryProtection;
  $LocationsAreReady = TRUE;
  $LocationsCreated = 0;
  $foldersAreReady = $locationPassRan = FALSE;
  $servedAreReady = TRUE;
  $policyFindings = $policyFinding = $servedPaths = array();
  $servedPath = '';
  $locationsCreated = $locationsCorrected = $locationsRefused = 0;
  if (!$RunningAsRoot) errorEntry('Data locations can only be created while running as root!', 32010, FALSE);
  else {
    // / The data root & the folders below it — Sessions, Cache, Logs, Engine, Secrets & the socket
    // / folder — are created here, & an older layout's sessions are moved into Sessions, by the one
    // / function -fp uses for the same, so a fresh install & an upgrade reach the same shape.
    // / Each location's owner & mode come from its role, through correctManagedLocations below,
    // / which is the one place a primary location gets either. The backup is not made here. It
    // / is root-only & the updater makes it when it keeps one, beneath a folder root holds.
    list ($foldersAreReady, $locationsCreated) = provisionAndMigrateDataRoot($ApacheUser);
    if (!$foldersAreReady) {
      $LocationsAreReady = FALSE;
      errorEntry('A required data location could not be created!', 32011, FALSE); }
    $LocationsCreated = $locationsCreated;
    // / A served location is the one kind that lives inside the web root.
    // / Every location above is outside it & is reachable only by this application. A served
    // / location is served straight to browsers, because that is how a user fetches a file. It
    // / therefore needs a protection file that none of the others do, & it has to exist before
    // / the first upload rather than after somebody notices. HRConvert2 serves one, DATA in its
    // / hosted location. Until v4.0.5 this named $ConvertTemp rather than asking the contract.
    // / Root makes one only beneath a folder no other account can change. createHeldFolder says why.
    $servedPaths = servedLocations();
    foreach ($servedPaths as $servedPath) {
      if (!is_dir($servedPath)) {
        if (createHeldFolder($servedPath)) {
          $LocationsCreated++;
          print('  '.str_pad('Created', 12).$servedPath.$Lol); } }
      else print('  '.str_pad('Present', 12).$servedPath.$Lol);
      if (!is_dir($servedPath)) {
        $LocationsAreReady = $servedAreReady = FALSE;
        print('  '.str_pad('FAILED', 12).$servedPath.$Lol);
        errorEntry('The web served '.basename($servedPath).' location could not be created at '.$servedPath.'!', 32014, FALSE); } }
    if ($servedAreReady) {
      // / Every location gets its owner & mode from its role before the protection below is
      // / written, because that protection is written into the served locations. An application
      // / that serves nothing still has its other locations given their roles here.
      list ($locationPassRan, $locationsCorrected, $locationsRefused) = correctManagedLocations($ApacheUser);
      if (!$locationPassRan or $locationsRefused > 0) $LocationsAreReady = FALSE;
      // / The protection file & the live exposure of each served location. The Engine has known
      // / what it serves since v4.0.5. Until then the application produced these rows through a
      // / data policy provider & this printed them without understanding any of it.
      if (count($servedPaths) > 0) {
        print($Lol.servedLocationNames().' directory protection.'.$Lol);
        $policyFindings = collectDataPolicyFindings();
        foreach ($policyFindings as $policyFinding) {
          if (isset($policyFinding['Label'])) print('  '.str_pad((string)$policyFinding['Label'], 22).str_pad((string)$policyFinding['Status'], 14).(string)$policyFinding['Detail'].$Lol);
          else print((string)$policyFinding['Text'].$Lol); } } }
    if ($LocationsAreReady) logEntry('Prepared the data root & its folders. '.$LocationsCreated.' session directory or directories were moved from an older layout.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $policyFindings, $policyFinding, $servedPaths, $servedPath, $servedAreReady, $foldersAreReady, $locationPassRan, $locationsCreated, $locationsCorrected, $locationsRefused);
  return array($LocationsAreReady, $LocationsCreated); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the copy of the application a service unit runs.
// / Accepts nothing. Returns the folder of the installed copy when there is one, & the folder this
// / command runs from when there is not.
// / A UNIT NAMES THE INSTALLED COPY, NOT WHEREVER -fp HAPPENED TO BE RUN FROM. A release unpacked
// / somewhere to be installed is a copy -fp installs & leaves behind, & until v4.0.5 a unit written
// / from one ran that copy until the installed copy's own -fp wrote the unit again. A copy unpacked
// / in a folder the web server account cannot enter left the listener failing in between, & one
// / removed afterwards left the units naming nothing. The installed copy is what every later run
// / uses, so it is what each unit names from the start. A tree with no installed copy yet, which
// / is the first -fp of a release older than v4.0.1, is named itself, & the copy -fp installs as it
// / ends writes the units again.
function serviceEntryFolder() {
  // / Set variables.
  global $InstLoc, $ApplicationLoc, $DirSep, $EnableMemoryProtection;
  $EntryFolder = '';
  $EntryFolder = rtrim((string)$InstLoc, $DirSep);
  if (trim((string)$ApplicationLoc) !== '' && is_file(rtrim((string)$ApplicationLoc, $DirSep).$DirSep.engineApplicationEntryPoint())) $EntryFolder = rtrim((string)$ApplicationLoc, $DirSep);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection);
  return $EntryFolder; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to build a service unit from the live configuration.
// / Accepts no arguments.
// / Returns the complete unit file contents.
// / Every path in the unit is read from this installation, never typed.
// / systemd cannot read config.php, so a unit written by hand duplicates the data location,
// / the installation path & the web server user, & nothing checks that the copies still
// / agree. Generating it here means moving the data location moves the unit with it.
// / The application it runs is the installed copy, which serviceEntryFolder() names.
function generateListenerService() {
  // / Set variables.
  global $DataRootLoc, $ConvertLoc, $ApacheUser, $DirSep, $EnableMemoryProtection;
  $ServiceContents = '';
  $dataLocation = $entryFolder = $corePath = $documentationPath = $webServerUnit = '';
  // / The data root, not a session area, because the socket folder the listener binds hangs off
  // / the root. $PrimaryConvertLoc is the session area now, so the root is read directly.
  $dataLocation = (string)$DataRootLoc !== '' ? (string)$DataRootLoc : (string)$ConvertLoc;
  $entryFolder = serviceEntryFolder();
  $corePath = $entryFolder.$DirSep.engineApplicationEntryPoint();
  $documentationPath = $entryFolder.$DirSep.'Documentation'.$DirSep.'ABOUT_RESOURCE_RATE_LIMITING.txt';
  // / Order after whichever web server is actually installed. Both are Wants rather than
  // / Requires, because HRConvert2 fails open & a listener is useful without a web server.
  $webServerUnit = 'apache2.service';
  if (locateDependency('apache2') === '' && locateDependency('httpd') !== '') $webServerUnit = 'httpd.service';
  $ServiceContents = '# / Written by '.engineApplicationName().' v'.ltrim(engineApplicationVersion(), 'vV').'. Do not edit by hand.'.PHP_EOL
    .'# / Every path here was read from this installation. Re-run the following to rewrite it'.PHP_EOL
    .'# / after moving the data location or the installation.'.PHP_EOL
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
  purgeSensitiveMemory($EnableMemoryProtection, $dataLocation, $entryFolder, $corePath, $documentationPath, $webServerUnit);
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
// / The application it runs is the installed copy, which serviceEntryFolder() names.
function generateEnvironmentTimer() {
  // / Set variables.
  global $DirSep, $EnableMemoryProtection;
  $ServiceContents = $TimerContents = '';
  $entryFolder = $corePath = $documentationPath = '';
  $entryFolder = serviceEntryFolder();
  $corePath = $entryFolder.$DirSep.engineApplicationEntryPoint();
  $documentationPath = $entryFolder.$DirSep.'Documentation'.$DirSep.'ABOUT_ENVIRONMENT_MANAGER.txt';
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
  purgeSensitiveMemory($EnableMemoryProtection, $entryFolder, $corePath, $documentationPath);
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
  else if (!$EnableResourceAwareness) print('  '.str_pad('Skipped', 12).'Resource awareness is disabled.'.$Lol);
  // / A unit file written where systemd is not running is a file nothing reads, & worse,
  // / it makes the listener defer to a service manager that will never answer. Ask whether
  // / systemd is RUNNING rather than whether its tools happen to be installed.
  else if (!systemdIsUsable()[0]) {
    list ($serviceSystemdUsable, $serviceSystemdReason) = systemdIsUsable();
    print('  '.str_pad('Skipped', 12).$serviceSystemdReason.$Lol);
    print('  '.str_pad('', 12).'No unit is written. -l starts the listener without one.'.$Lol); }
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
            if ($existingContents !== '') print('  '.str_pad('Note', 12).'A listener already running keeps the old unit until restarted.'.$Lol.'  '.str_pad('', 12).'systemctl restart '.engineApplicationSlug().'-listener'.$Lol);
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
      if (!$locationsAreReady) print($Lol.'One or more data locations could not be created or given their roles. Stopping here.'.$Lol.$Lol);
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
          if ($optionalProblems > 0) print($Lol.$optionalProblems.' OPTIONAL dependenc(ies) are missing. Their subsystems refuse until installed.'.$Lol);
          print($Lol.'  php '.$InstLoc.$DirSep.engineApplicationEntryPoint().' -v          Confirm every dependency & subsystem.'.$Lol);
          print('  php '.$InstLoc.$DirSep.engineApplicationEntryPoint().' --status    Confirm the listener.'.$Lol);
          print('  sudo php '.$InstLoc.$DirSep.engineApplicationEntryPoint().' --config    Change any setting.'.$Lol);
          print($Lol.'Not set by the installer.'.$Lol);
          print('  The public URL, for share links. Set it with --config.'.$Lol);
          print('  A web server virtual host, if the installation is not served yet.'.$Lol.$Lol); } } } }
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
// / NOT YET IMPLEMENTED. updateApplication & its helpers are in this core from v4.0.3, & this
// / still waits, because updateApplication refuses the version that is already installed.
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
// / A function to list the code an installation holds that a tree about to replace it lacks.
// / Accepts the installed tree & the tree about to be installed.
// / Returns whether the installed tree was read whole & the paths the other tree lacks, each
// / relative to the tree, in that order.
// /
// / A DROP-IN IS NOT A RELEASE. An update that carries only the files that changed is a tree
// / that runs nothing on its own, & unpacked into the web root, where every release before
// / v4.0.1 was unpacked, -fp would install it in place of the whole application. So every PHP
// / file the installation holds in Resources & UI must be in the tree that replaces it.
// / Documentation is not compared, because a release may drop a document. A release that drops
// / code on purpose installs once the installation it replaces has been moved aside by hand.
// / The installed tree is root's & is walked with lstat, so no link is followed.
function codeMissingFromTree($installedTree, $candidateTree) {
  // / Set variables.
  global $EnableMemoryProtection;
  $TreeWasRead = TRUE;
  $MissingCode = array();
  $installedPath = $candidatePath = $topFolder = $currentFolder = $folderEntry = $entryPath = $relativePath = '';
  $pendingFolders = $folderEntries = $entryStatus = array();
  $installedPath = rtrim((string)$installedTree, DIRECTORY_SEPARATOR);
  $candidatePath = rtrim((string)$candidateTree, DIRECTORY_SEPARATOR);
  foreach (array('Resources', 'UI') as $topFolder) {
    $entryStatus = @lstat($installedPath.DIRECTORY_SEPARATOR.$topFolder);
    if (is_array($entryStatus) && ((int)$entryStatus['mode'] & 0170000) === 0040000) $pendingFolders[] = $installedPath.DIRECTORY_SEPARATOR.$topFolder; }
  while (count($pendingFolders) > 0) {
    $currentFolder = (string)array_pop($pendingFolders);
    $folderEntries = @scandir($currentFolder);
    if (!is_array($folderEntries)) $TreeWasRead = FALSE;
    else foreach ($folderEntries as $folderEntry) {
      if ($folderEntry === '.' or $folderEntry === '..') continue;
      $entryPath = $currentFolder.DIRECTORY_SEPARATOR.$folderEntry;
      $entryStatus = @lstat($entryPath);
      if (!is_array($entryStatus)) $TreeWasRead = FALSE;
      else if (((int)$entryStatus['mode'] & 0170000) === 0040000) $pendingFolders[] = $entryPath;
      else if (((int)$entryStatus['mode'] & 0170000) === 0100000 && strtolower(substr($folderEntry, -4)) === '.php') {
        $relativePath = substr($entryPath, strlen($installedPath) + 1);
        if (!is_file($candidatePath.DIRECTORY_SEPARATOR.$relativePath)) $MissingCode[] = $relativePath; } } }
  sort($MissingCode);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $TreeWasRead & $MissingCode are not purged, because they are the return values.
  purgeSensitiveMemory($EnableMemoryProtection, $installedPath, $candidatePath, $topFolder, $currentFolder, $folderEntry, $entryPath, $relativePath, $pendingFolders, $folderEntries, $entryStatus, $installedTree, $candidateTree);
  return array($TreeWasRead, $MissingCode); }
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
  $layoutEntries = $missingCode = array();
  $installedWasRead = FALSE;
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
    // / A tree that lacks code the installation holds is not installed in its place, so a drop-in
    // / unpacked where a release used to go cannot replace the whole application.
    if ($parentIsTrusted && $treeIsTrusted && is_dir($applicationPath) && !is_link($applicationPath)) {
      list ($installedWasRead, $missingCode) = codeMissingFromTree($applicationPath, $sourcePath);
      if (!$installedWasRead) warningEntry('Part of the installation at '.$applicationPath.' could not be read, so the tree at '.$sourcePath.' was compared with the rest of it.'); }
    if (!$parentIsTrusted or $resolvedParent !== $applicationParent) $InstallNotice = 'The folder the application lives in, '.$applicationParent.', can be changed by an account other than root, so nothing was installed. '.$trustNotice;
    else if (!$treeIsTrusted) $InstallNotice = 'The running tree at '.$sourcePath.' can be changed by an account other than root, so it was not installed. '.$treeNotice;
    else if (count($missingCode) > 0) $InstallNotice = 'The tree at '.$sourcePath.' lacks '.count($missingCode).' file(s) of code the installation at '.$applicationPath.' holds, '.$missingCode[0].' among them, so it was not installed. A drop-in holds only the files that changed. Unpack it into '.$applicationPath.' & run -fp there.';
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
  purgeSensitiveMemory($EnableMemoryProtection, $installedWasRead, $missingCode, $sourcePath, $applicationPath, $applicationParent, $resolvedParent, $trustNotice, $treeNotice, $stagedPath, $layoutEntry, $entryNotice, $retireNotice, $parentIsTrusted, $treeIsTrusted, $entryWasCopied, $treeWasKept, $filesCopied, $entryFiles, $layoutEntries, $sourceTree, $applicationLoc, $backupLoc);
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
// / WHAT A RELEASE LEFT IN IT OR BESIDE IT IS NAMED, & never removed. A release unpacked or cloned
// / into the hosted location brings .git & .github. Until v4.0.1 the updater moved the previous
// / installation aside beside it as .old, with every PHP file it held, & an update that failed
// / left it there. The Apache configuration refuses all three, & a location otherwise in step is
// / kept, which is benign, with them named.
function publishHostedLocation($hostedLoc, $applicationLoc, $mayRepair) {
  // / Set variables.
  global $EngineApplicationLayout, $RunningAsRoot, $EnableMemoryProtection;
  $PublishStatus = 'ok';
  $PublishDetail = '';
  $NamesWritten = $NamesRemoved = 0;
  $hostedPath = $applicationPath = $entryName = $hostedEntry = $applicationEntry = $loaderContents = $plannedPath = $sourcePath = $plannedFolder = $holdNotice = $writeNotice = $readNotice = $wantedContents = $hostedContents = $layoutEntry = $leftoverPath = '';
  $plannedFiles = $plannedFolders = $fileStatus = $layoutEntries = $leftoverPaths = array();
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
        $PublishDetail = 'Wrote '.$NamesWritten.' & removed '.$NamesRemoved.' name(s). Serves the loader & '.(count($plannedFiles) - 1).' other file(s).'; }
      else $PublishDetail = 'Serves the loader & '.(count($plannedFiles) - 1).' other file(s) from '.$applicationPath.'.'; } }
  if ($PublishStatus === 'ok' or $PublishStatus === 'repaired') {
    foreach (array($hostedPath.DIRECTORY_SEPARATOR.'.git', $hostedPath.DIRECTORY_SEPARATOR.'.github', $hostedPath.'.old') as $leftoverPath) if (file_exists($leftoverPath) or is_link($leftoverPath)) $leftoverPaths[] = $leftoverPath;
    if (count($leftoverPaths) > 0) {
      if ($PublishStatus === 'ok') $PublishStatus = 'kept';
      $PublishDetail = $PublishDetail.' Move '.implode(' & ', $leftoverPaths).' out of the web root. The Apache configuration refuses '.(count($leftoverPaths) === 1 ? 'it' : 'them').'.'; } }
  if (in_array($PublishStatus, array('FAILED', 'REFUSED', 'UNINSTALLED', 'STRAY'), TRUE) && $repairIsAllowed) warningEntry('The hosted location reported '.$PublishStatus.'. '.$PublishDetail);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $hostedPath, $applicationPath, $entryName, $hostedEntry, $applicationEntry, $loaderContents, $plannedPath, $sourcePath, $plannedFolder, $holdNotice, $writeNotice, $readNotice, $wantedContents, $hostedContents, $layoutEntry, $leftoverPath, $leftoverPaths, $plannedFiles, $plannedFolders, $fileStatus, $layoutEntries, $repairIsAllowed, $locationsOverlap, $hostedIsHeld, $entryIsOurs, $folderIsHeld, $folderChanged, $fileWasWritten, $wantedWasRead, $hostedWasRead, $namesDiffering, $namesLeft, $namesFailed, $entryRemoved, $entryLeft, $assetsRemoved, $assetsStale, $hostedLoc, $applicationLoc, $mayRepair);
  return array($PublishStatus, $PublishDetail, $NamesWritten, $NamesRemoved); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / MOVED FROM THE APPLICATION AT v4.0.5. The Apache configuration that makes the hosted
// / location run only the loader, every served location hand out inert downloads, & every data
// / location refuse every request. Only root writes it, so it lives here beside the rest of
// / the repair. The rules come from what the contract says each location IS, the same way the
// / owners & modes do, & from the hosted location the contract maps, so the contract can name a
// / served location & can never write a rule. Rules are written only for a served location inside
// / the hosted location, & classifyServedLocations() says why.
// / The checks that prove the rules are in force are in Environment Core, which every request loads.
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to build the rules that refuse every data location outright.
// / Accepts nothing. Returns the rules & the data locations that could not be refused, in that order.
// /
// / THE DATA LOCATION IS NEVER SERVED. Its sessions, caches & logs belong to the web server
// / account, which is the account that serves files, so a data location inside the document
// / root hands them to anybody who guesses a name. One outside it is still granted wherever the
// / host grants a folder above it, as Debian grants all of /var/www, & is one Alias or one wider
// / document root away from being served. So each one is refused outright, as it is written &
// / as it resolves, & no .htaccess inside it is read.
// / A data location that holds the hosted location is not refused, because refusing it would
// / refuse the application as well. It is returned, so the caller can say so. Every other one is
// / refused, because a data location is never served. A served location below one keeps the grant
// / its own deeper block gives it, & one that is a data location is refused with it, which the data
// / location's own MISPLACED line explains. The installation itself is inside the data location
// / since v4.0.1, & is refused with it, because nothing in it is ever meant to be fetched.
function apacheDataLocationRules() {
  // / Set variables.
  global $EnableMemoryProtection;
  $LocationRules = '';
  $UnrefusedRoots = array();
  $dataRoot = $rootForm = $hostedLocation = '';
  // / The hosted location is the application's, read through the map as HostedLocation.
  $hostedLocation = (string)applicationSetting('HostedLocation', '');
  foreach (dataLocationRoots() as $dataRoot) {
    if (pathHoldsPath($dataRoot, $hostedLocation)) $UnrefusedRoots[] = $dataRoot;
    else foreach (pathForms($dataRoot) as $rootForm) $LocationRules = $LocationRules.'<Directory '.escapeApacheConfigPath($rootForm).'>'.PHP_EOL
      .'  Require all denied'.PHP_EOL
      .'  AllowOverride None'.PHP_EOL
      .'  Options None'.PHP_EOL
      .'</Directory>'.PHP_EOL; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / Neither $LocationRules nor $UnrefusedRoots is purged, because both are return values.
  purgeSensitiveMemory($EnableMemoryProtection, $dataRoot, $rootForm, $hostedLocation);
  return array($LocationRules, $UnrefusedRoots); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether the web server is running.
// / Accepts the Apache control program. Returns running, stopped or unknown.
// /
// / apachectl -k graceful STARTS A SERVER THAT IS NOT RUNNING. Measured; it says httpd not
// / running, trying to start, & starts it. Until v4.0.1 -fp reported that as a reload, & an
// / unattended Environment Manager pass permitted to rewrite configuration would have started
// / a web server somebody had stopped, after saying it starts nothing. So a reload is asked for
// / only while the server is running, & a stopped one reads the configuration when it starts.
// / The server names its own pid file in its run configuration. The process that file names
// / must still be a web server, because a pid file outlives a crash & its number is used again.
function apacheIsRunning($controlBinary) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ServerState = 'unknown';
  $commandOutput = $pathMatch = array();
  $commandExitCode = 1;
  $processId = 0;
  $outputLine = $serverRoot = $pidPath = $pidFolder = $pidText = $processName = $readNotice = '';
  $pidWasRead = FALSE;
  exec(escapeshellarg((string)$controlBinary).' -t -D DUMP_RUN_CFG 2>/dev/null', $commandOutput, $commandExitCode);
  foreach ($commandOutput as $outputLine) {
    if (preg_match('/^ServerRoot: "(.+)"$/', trim((string)$outputLine), $pathMatch) === 1) $serverRoot = $pathMatch[1];
    if (preg_match('/^PidFile: "(.+)"$/', trim((string)$outputLine), $pathMatch) === 1) $pidPath = $pathMatch[1]; }
  // / A relative pid file is relative to the server root.
  if ($pidPath !== '' && $pidPath[0] !== DIRECTORY_SEPARATOR) $pidPath = rtrim($serverRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$pidPath;
  // / /var/run leads to /run almost everywhere & the kernel reads nothing through a link, so the
  // / folder is resolved first. A server that is not running leaves no pid file behind.
  if ($commandExitCode === 0 && $pidPath !== '') {
    $pidFolder = (string)@realpath(dirname($pidPath));
    if ($pidFolder === '') $ServerState = 'stopped';
    else {
      list ($pidWasRead, $pidText, $readNotice) = readFileWithoutFollowing($pidFolder.DIRECTORY_SEPARATOR.basename($pidPath), 64);
      if (!$pidWasRead && !is_array(@lstat($pidFolder.DIRECTORY_SEPARATOR.basename($pidPath)))) $ServerState = 'stopped';
      else if ($pidWasRead) {
        $processId = (int)trim($pidText);
        if ($processId > 1) $processName = trim((string)@file_get_contents('/proc/'.$processId.'/comm'));
        $ServerState = (preg_match('/^(apache2|httpd)/', $processName) === 1) ? 'running' : 'stopped'; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $commandOutput, $pathMatch, $commandExitCode, $processId, $outputLine, $serverRoot, $pidPath, $pidFolder, $pidText, $processName, $readNotice, $pidWasRead, $controlBinary);
  return $ServerState; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write & activate the Apache configuration the application needs.
// / Accepts a boolean permitting repair.
// / Returns a validity boolean & the number of locations written, in that order.
// /
// / THIS IS WHERE THE SERVED LOCATION RULES BELONG. THE .htaccess IS THE FALLBACK, NOT THE
// / ANSWER. A .htaccess is read only where AllowOverride permits it, & Debian, Ubuntu & the
// / standard php:*-apache container all ship AllowOverride None for /var/www. It is also
// / constrained; a directive the override level does not allow is not ignored, it is a hard 500
// / for the whole tree. Server configuration has neither problem. Nothing is silently dropped &
// / nothing is refused, so the full rule set can live here & be relied upon.
// /
// / Every path in this block is read from this installation, never typed.
// / The same reasoning as generateListenerService(). An administrator who moves the
// / installation moves this with it, & a block naming a directory that no longer exists
// / protects nothing while looking like it does.
// /
// / The data locations are refused in the same file. apacheDataLocationRules() says why.
// /
// / The configuration is tested before it is allowed to take effect.
// / This application has already shipped one protection file that returned 500 for an entire
// / directory tree, so writing web server configuration & walking away is not something it
// / gets to do twice. The file is written, apachectl configtest is run, & a configuration
// / that does not pass is WITHDRAWN before anything reloads. What was there before is put back
// / exactly, & the test is run again to prove it. Until v4.0.1 a file this replaced was deleted
// / rather than put back, & a link an administrator had made was removed with it.
// /
// / Root reads & writes this file through the kernel, as it does every other file it writes.
// / The write is atomic, so the web server never starts against half a file, & nothing is
// / written through a link at the name. A name the kernel cannot read as a plain file is left
// / as it is & reported, because a file that was never examined cannot be put back.
// / Only a running server is reloaded. apacheIsRunning() says why.
// / It moved from the application at v4.0.5. The file, the loader it lets run & the words it
// / prints are named for the application, so HRConvert2's are exactly what they were.
function verifyApacheConfiguration($mayRepair) {
  // / Set variables.
  global $RunningAsRoot, $Lol, $Verbose, $EnableMemoryProtection, $DirSep;
  $ConfigurationIsValid = TRUE;
  $ConfigurationsWritten = 0;
  $dropInContents = $dropInPath = $existingContents = $candidateDir = $enabledDir = $enabledPath = $configName = $servedNames = $rulesName = $servedPath = $servedRules = '';
  $controlBinary = $configTestOutput = $reloadOutput = $locationRules = $unrefusedRoot = $readNotice = $writeNotice = $restoreNotice = $serverState = '';
  $candidateDirectories = $unrefusedRoots = $previousContents = $servedPaths = array();
  $commandOutput = array();
  $commandExitCode = 0;
  $configTestPassed = $baselineTestPassed = $linkWasMade = $writtenThisRun = $moduleIsLoaded = FALSE;
  $dropInExists = $existingWasRead = $dropInWasWritten = $restoreWorked = FALSE;
  $moduleStatus = $hostedLocation = '';
  $configName = engineApplicationSlug().'.conf';
  // / The hosted location is the application's, read through the map as HostedLocation.
  $hostedLocation = (string)applicationSetting('HostedLocation', '');
  $servedPaths = servedLocations();
  $servedNames = servedLocationNames();
  // / What the rules are called in what this prints. HRConvert2's are the DATA rules, as they always
  // / were. An application that serves no location still has its loader & its data locations to
  // / guard, & its rules are only the Apache rules.
  $rulesName = ($servedNames === '') ? 'Apache' : $servedNames;
  // / An application with no hosted location is not served by a web server, so there is nothing to write.
  if ($hostedLocation === '') {
    $ConfigurationIsValid = FALSE;
    warningEntry('No hosted location is set, so the Apache configuration could not be built.'); }
  else {
    list ($locationRules, $unrefusedRoots) = apacheDataLocationRules();
    // / One block for each served location, each holding the same rules.
    foreach ($servedPaths as $servedPath) $servedRules = $servedRules.PHP_EOL
      .'# / THIS TREE HOLDS USER SUPPLIED BYTES & IS SERVED DIRECTLY BY THE WEB SERVER.'.PHP_EOL
      .'# / Every file a user uploads or converts is staged here so a browser can fetch it,'.PHP_EOL
      .'# / which means the server hands out attacker chosen content from this origin. For any'.PHP_EOL
      .'# / format a browser treats as ACTIVE that content is code; an SVG is served as'.PHP_EOL
      .'# / image/svg+xml & a <script> inside it executes.'.PHP_EOL
      .'# /'.PHP_EOL
      .'# / Content-Disposition closes that by handing the file over as a download, so it is'.PHP_EOL
      .'# / never rendered as a document. The CSP closes it a second time & independently.'.PHP_EOL
      .'# / X-Content-Type-Options does NOT close it; the type here is already correct, so'.PHP_EOL
      .'# / there is nothing to sniff. It is set for the neighbouring ambiguous type case only.'.PHP_EOL
      .'<Directory '.escapeApacheConfigPath($servedPath).'>'.PHP_EOL
      .'  # / A .htaccess in this tree cannot loosen what is set here.'.PHP_EOL
      .'  AllowOverride None'.PHP_EOL
      .'  Require all granted'.PHP_EOL
      .'  # / No listing, & nothing here is ever a program.'.PHP_EOL
      .'  # / SymLinksIfOwnerMatch rather than FollowSymLinks. A link is followed only when'.PHP_EOL
      .'  # / the link & its target have the same owner, so a link written by an uploader'.PHP_EOL
      .'  # / cannot reach a file owned by somebody else.'.PHP_EOL
      .'  # / Nothing here creates a link into a session today. This is set now because the'.PHP_EOL
      .'  # / default is FollowSymLinks on most builds, & the first time somebody serves a'.PHP_EOL
      .'  # / stored file through a link is not the moment to discover that.'.PHP_EOL
      .'  Options -Indexes -ExecCGI -FollowSymLinks +SymLinksIfOwnerMatch'.PHP_EOL
      .'  # / index.html is the ONLY index. Without this, a request for the directory'.PHP_EOL
      .'  # / falls to whatever the server was configured to prefer, which on a default'.PHP_EOL
      .'  # / installation is index.php first. That name is refused by this application'.PHP_EOL
      .'  # / & cannot be staged, so the effect today is a 403 rather than a listing.'.PHP_EOL
      .'  # / Naming it explicitly serves the protection page instead of the 403, & stops'.PHP_EOL
      .'  # / this depending on what a server happens to prefer.'.PHP_EOL
      .'  # / A directive whose module is not loaded does not parse, & then this whole file is'.PHP_EOL
      .'  # / withdrawn, Header rules included. So each one that needs a module is guarded by it.'.PHP_EOL
      .'  <IfModule mod_dir.c>'.PHP_EOL
      .'    DirectoryIndex index.html'.PHP_EOL
      .'  </IfModule>'.PHP_EOL
      .'  <IfModule mod_headers.c>'.PHP_EOL
      .'    # / index.html is the document root protection page & is the one file here meant'.PHP_EOL
      .'    # / to render. It cannot be user supplied, because the application refuses to'.PHP_EOL
      .'    # / stage a file by that name.'.PHP_EOL
      .'    <FilesMatch "^(?!index\.html$).*$">'.PHP_EOL
      .'      Header always set Content-Disposition "attachment"'.PHP_EOL
      .'      Header always set X-Content-Type-Options "nosniff"'.PHP_EOL
      .'      Header always set Content-Security-Policy "default-src \'none\'; sandbox"'.PHP_EOL
      .'      Header always set Referrer-Policy "no-referrer"'.PHP_EOL
      .'    </FilesMatch>'.PHP_EOL
      .'  </IfModule>'.PHP_EOL
      .'  # / php_flag is legal here because server configuration is not subject to an'.PHP_EOL
      .'  # / override level. It must NEVER be written into a .htaccess, where it returns 500'.PHP_EOL
      .'  # / wherever AllowOverride does not grant Options.'.PHP_EOL
      .'  <IfModule mod_php.c>'.PHP_EOL
      .'    php_flag engine off'.PHP_EOL
      .'  </IfModule>'.PHP_EOL
      .'  <IfModule mod_mime.c>'.PHP_EOL
      .'    RemoveHandler .php .phtml .phps .php3 .php4 .php5 .php7 .php8 .phar .cgi .pl .py .sh'.PHP_EOL
      .'  </IfModule>'.PHP_EOL
      .'</Directory>'.PHP_EOL;
    $dropInContents = '# / Written by '.engineApplicationName().'. Do not edit by hand.'.PHP_EOL
      .'# / '.enginePolicyMarker().PHP_EOL
      .'# / Re-run  sudo php '.engineApplicationEntryPoint().' -fp  to rewrite this file.'.PHP_EOL
      .'# / Paths are read from this installation, so moving it moves these rules with it.'.PHP_EOL
      .PHP_EOL
      .'# / THE HOSTED LOCATION RUNS ONE PHP FILE, THE LOADER. '.engineApplicationName().' is installed outside the'.PHP_EOL
      .'# / web root, & everything else the web server serves from here is a static file, so a'.PHP_EOL
      .'# / PHP file anybody leaves here is refused rather than run.'.PHP_EOL
      .'<Directory '.escapeApacheConfigPath($hostedLocation).'>'.PHP_EOL
      .'  Options -Indexes'.PHP_EOL
      .'  <FilesMatch "\.(?i:php[0-9]?|phtml|phps|phar)$">'.PHP_EOL
      .'    Require all denied'.PHP_EOL
      .'  </FilesMatch>'.PHP_EOL
      .'  <Files "'.engineApplicationEntryPoint().'">'.PHP_EOL
      .'    Require all granted'.PHP_EOL
      .'  </Files>'.PHP_EOL
      .'</Directory>'.PHP_EOL
      .PHP_EOL
      .'# / NOTHING A RELEASE LEFT IN OR BESIDE THE HOSTED LOCATION IS SERVED. A release unpacked or cloned'.PHP_EOL
      .'# / there brings .git & .github. Until v4.0.1 the updater moved the previous installation'.PHP_EOL
      .'# / aside beside it as .old, PHP files & all, & an update that failed left it there.'.PHP_EOL
      .'# / -fp names each one it finds, & removes none of them.'.PHP_EOL
      .'<Directory '.escapeApacheConfigPath(rtrim($hostedLocation, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'.git').'>'.PHP_EOL
      .'  Require all denied'.PHP_EOL
      .'</Directory>'.PHP_EOL
      .'<Directory '.escapeApacheConfigPath(rtrim($hostedLocation, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'.github').'>'.PHP_EOL
      .'  Require all denied'.PHP_EOL
      .'</Directory>'.PHP_EOL
      .'<Directory '.escapeApacheConfigPath(rtrim($hostedLocation, DIRECTORY_SEPARATOR).'.old').'>'.PHP_EOL
      .'  Require all denied'.PHP_EOL
      .'</Directory>'.PHP_EOL
      .$servedRules
      .($locationRules === '' ? '' : PHP_EOL
      .'# / THE DATA LOCATION IS NEVER SERVED, WHEREVER IT IS. Its sessions, caches & logs belong'.PHP_EOL
      .'# / to the account that serves files, so each data location is refused outright.'.PHP_EOL
      .$locationRules);
    // / Every layout this host might be running. Debian & Ubuntu separate available from
    // / enabled & need the link made; every other layout includes its directory outright.
    if (is_dir('/etc/apache2/conf-available')) $candidateDirectories[] = '/etc/apache2/conf-available';
    else if (is_dir('/etc/apache2/conf.d')) $candidateDirectories[] = '/etc/apache2/conf.d';
    if (is_dir('/etc/httpd/conf.d')) $candidateDirectories[] = '/etc/httpd/conf.d';
    if (empty($candidateDirectories)) {
      // / Not a failure. An installation may be behind a web server this application does
      // / not configure, & the exposure check is what establishes whether that matters.
      warningEntry('No Apache configuration directory was found, so the '.$rulesName.' rules were not written there.'.(count($servedPaths) === 0 ? '' : ' The '.$servedNames.'/.htaccess is the fallback & the exposure check reports whether it is in force.'));
      print('  '.str_pad('Skipped', 12).'No Apache configuration directory on this host.'.$Lol); }
    else {
      // / A data location that holds the application cannot be refused without refusing it too.
      foreach ($unrefusedRoots as $unrefusedRoot) {
        print('  '.str_pad('Skipped', 12).$unrefusedRoot.' holds what the web server serves. Not refused.'.$Lol);
        warningEntry('The data location '.$unrefusedRoot.' holds the hosted location, so the Apache configuration cannot refuse it without refusing '.engineApplicationName().' as well. Move the data location outside the web root.'); }
      // / The rules for a served location are Header directives, so the module they need is
      // / confirmed & enabled before the configuration that depends on it is written. Enabling it
      // / here means the single reload at the end of this function covers both. An application
      // / that serves no location writes no Header directive & needs no module.
      if (count($servedPaths) > 0) {
        list ($moduleIsLoaded, $moduleStatus) = verifyApacheModule('headers_module', $mayRepair);
        print('  '.str_pad(($moduleStatus === 'enabled' ? 'Enabled' : ($moduleStatus === 'ok' ? 'Present' : 'MISSING')), 12).'headers_module'.($moduleStatus === 'missing' ? ', the '.$servedNames.' rules will be skipped without it' : '').$Lol); }
      // / Establish whether this host's configuration parsed BEFORE we touched it, so a
      // / pre-existing fault is never mistaken for one of ours.
      $controlBinary = locateDependency('apachectl');
      if ($controlBinary === '') $controlBinary = locateDependency('apache2ctl');
      if ($controlBinary !== '') {
        exec(escapeshellarg($controlBinary).' configtest 2>&1', $commandOutput, $commandExitCode);
        $baselineTestPassed = ($commandExitCode === 0);
        $commandOutput = array(); }
      foreach ($candidateDirectories as $candidateDir) {
        $dropInPath = $candidateDir.$DirSep.$configName;
        clearstatcache(TRUE, $dropInPath);
        $dropInExists = is_array(@lstat($dropInPath));
        $existingWasRead = FALSE;
        $existingContents = $readNotice = '';
        if ($dropInExists) list ($existingWasRead, $existingContents, $readNotice) = readFileWithoutFollowing($dropInPath, 1048576);
        // / Nothing the application writes comes near a megabyte, & a file cut short cannot be put back.
        if ($existingWasRead && strlen($existingContents) >= 1048576) {
          $existingWasRead = FALSE;
          $readNotice = $dropInPath.' is far larger than anything '.engineApplicationName().' writes.'; }
        if ($dropInExists && !$existingWasRead) {
          $ConfigurationIsValid = FALSE;
          print('  '.str_pad('FAILED', 12).$dropInPath.' could not be examined & was left as it is.'.$Lol);
          warningEntry('The Apache configuration at '.$dropInPath.' could not be examined, so it was left as it is. Remove it & run the -fp argument again. '.$readNotice); }
        else if ($existingContents === $dropInContents) print('  '.str_pad('Unchanged', 12).$dropInPath.$Lol);
        else if (!$mayRepair or !$RunningAsRoot) {
          $ConfigurationIsValid = FALSE;
          print('  '.str_pad(($dropInExists ? 'Differs' : 'Missing'), 12).$dropInPath.$Lol); }
        else {
          list ($dropInWasWritten, $writeNotice) = writeFileWithoutFollowing($dropInPath, $dropInContents, 0644);
          if (!$dropInWasWritten) {
            $ConfigurationIsValid = FALSE;
            print('  '.str_pad('FAILED', 12).$dropInPath.$Lol);
            warningEntry('The Apache configuration could not be written. '.$writeNotice); }
          else {
            // / What was there is kept, so a configuration that fails its test can be put back exactly.
            $previousContents[$dropInPath] = ($dropInExists ? $existingContents : NULL);
            $ConfigurationsWritten++;
            $writtenThisRun = TRUE;
            print('  '.str_pad(($dropInExists ? 'Updated' : 'Written'), 12).$dropInPath.$Lol); } }
        // / Debian keeps enabled configuration as links. Made directly rather than through
        // / a2enconf, because a2enconf is a Debian script & this has to work without it.
        $enabledDir = '/etc/apache2/conf-enabled';
        if ($candidateDir === '/etc/apache2/conf-available' && is_dir($enabledDir)) {
          $enabledPath = $enabledDir.$DirSep.$configName;
          if (!file_exists($enabledPath) && $mayRepair && $RunningAsRoot) {
            $linkWasMade = @symlink('../conf-available/'.$configName, $enabledPath);
            if ($linkWasMade) print('  '.str_pad('Enabled', 12).$enabledPath.$Lol);
            else {
              $ConfigurationIsValid = FALSE;
              print('  '.str_pad('FAILED', 12).$enabledPath.$Lol);
              warningEntry('The '.engineApplicationName().' Apache configuration was written but could not be enabled at '.$enabledPath.'.'); } }
          else if (file_exists($enabledPath)) print('  '.str_pad('Enabled', 12).$enabledPath.$Lol); } }
      // / Prove it parses before anything reloads, & undo it if it does not.
      // / A configuration that does not parse takes the whole server down on the next
      // / restart, which would be a far worse outcome than the exposure this closes.
      if ($writtenThisRun or $moduleStatus === 'enabled') {
        $controlBinary = locateDependency('apachectl');
        if ($controlBinary === '') $controlBinary = locateDependency('apache2ctl');
        if ($controlBinary === '') {
          warningEntry('The Apache configuration was written but apachectl could not be found, so it was not tested. Run a configuration test & reload the web server by hand.');
          print('  '.str_pad('Note', 12).'apachectl not found. Test & reload the web server by hand.'.$Lol); }
        else {
          exec(escapeshellarg($controlBinary).' configtest 2>&1', $commandOutput, $commandExitCode);
          $configTestOutput = implode(' ', $commandOutput);
          $configTestPassed = ($commandExitCode === 0);
          // / Only withdraw for a fault that is ours.
          // / A host whose configuration ALREADY did not parse before this ran would
          // / otherwise have a perfectly good file removed & be told, wrongly, that
          // / the application wrote something invalid. The baseline was captured before the
          // / write for exactly this reason; a test that was already failing stays the
          // / administrator's problem & is reported as theirs.
          if (!$configTestPassed && !$baselineTestPassed) {
            warningEntry('The Apache configuration on this host did not parse BEFORE '.engineApplicationName().' wrote anything, so its file was kept & the pre-existing fault was left for the administrator. apachectl reported: '.trim($configTestOutput));
            print('  '.str_pad('Note', 12).'The Apache configuration did not parse before this ran.'.$Lol);
            print('  '.str_pad('', 12).'The '.engineApplicationName().' file was kept. Fix the existing fault & reload.'.$Lol); }
          else if (!$configTestPassed) {
            // / Put back exactly what was found. A file this run created is removed, a file it
            // / replaced is written back as it was, & the link goes only when this run made it.
            foreach ($previousContents as $dropInPath => $existingContents) {
              if ($existingContents === NULL) list ($restoreWorked, $restoreNotice) = removeFileWithoutFollowing($dropInPath);
              else list ($restoreWorked, $restoreNotice) = writeFileWithoutFollowing($dropInPath, $existingContents, 0644);
              if (!$restoreWorked) warningEntry('The Apache configuration at '.$dropInPath.' could not be put back as it was. '.$restoreNotice); }
            if ($linkWasMade) {
              list ($restoreWorked, $restoreNotice) = removeFileWithoutFollowing('/etc/apache2/conf-enabled/'.$configName);
              if (!$restoreWorked) warningEntry('The link that enabled the '.engineApplicationName().' Apache configuration could not be removed. '.$restoreNotice); }
            $ConfigurationIsValid = FALSE;
            $ConfigurationsWritten = 0;
            print('  '.str_pad('WITHDRAWN', 12).'Failed the syntax test. The previous configuration is back.'.$Lol);
            print('  '.str_pad('', 12).trim($configTestOutput).$Lol);
            errorEntry('The Apache configuration '.engineApplicationName().' wrote did not pass apachectl configtest & was withdrawn. The test reported: '.trim($configTestOutput), 504, FALSE);
            // / The test passed before anything was written, so it must pass again now. It is run
            // / again to prove that rather than to assume it.
            $commandOutput = array();
            exec(escapeshellarg($controlBinary).' configtest 2>&1', $commandOutput, $commandExitCode);
            if ($commandExitCode === 0) print('  '.str_pad('Tested', 12).'The previous configuration parses.'.$Lol);
            else {
              warningEntry('The Apache configuration still does not parse after '.engineApplicationName().' withdrew its file. Fix what apachectl configtest reports before the web server next starts. It reported: '.trim(implode(' ', $commandOutput)));
              print('  '.str_pad('Note', 12).'The configuration still does not parse. See the log.'.$Lol); } }
          else {
            print('  '.str_pad('Tested', 12).'The configuration parses.'.$Lol);
            // / A graceful reload finishes the requests already in flight & then picks up
            // / the new configuration. It cannot drop a connection & it cannot fail closed,
            // / because the test above already proved the configuration parses.
            $serverState = apacheIsRunning($controlBinary);
            if ($serverState === 'stopped') print('  '.str_pad('Not running', 12).'The web server was left stopped. It reads this configuration when it starts.'.$Lol);
            else if ($serverState !== 'running') {
              warningEntry('The Apache configuration was written & tested, but whether the web server is running could not be told, so it was not reloaded. Reload it by hand for the '.$rulesName.' rules to take effect.');
              print('  '.str_pad('Note', 12).'Could not tell whether the web server is running. Reload it by hand.'.$Lol); }
            else {
              $commandOutput = array();
              exec(escapeshellarg($controlBinary).' -k graceful 2>&1', $commandOutput, $commandExitCode);
              $reloadOutput = implode(' ', $commandOutput);
              if ($commandExitCode === 0) print('  '.str_pad('Reloaded', 12).'The web server picked up the new configuration.'.$Lol);
              else {
                warningEntry('The Apache configuration was written & tested but the web server could not be reloaded. Reload it by hand for the '.$rulesName.' rules to take effect.');
                print('  '.str_pad('Note', 12).'Could not reload the web server. Reload it by hand.'.$Lol); } } } } }
      if ($ConfigurationsWritten > 0) logEntry('Wrote Apache configuration to '.$ConfigurationsWritten.' location(s) for '.(count($servedPaths) === 0 ? $hostedLocation : implode(' & ', $servedPaths)).'.'); } }
  if ($Verbose) logEntry('Apache Configuration: '.count($candidateDirectories).' location(s) examined, '.$ConfigurationsWritten.' written.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dropInContents, $dropInPath, $existingContents, $candidateDir, $enabledDir, $enabledPath, $configName, $servedNames, $rulesName, $servedPath, $servedRules, $controlBinary, $configTestOutput, $reloadOutput, $locationRules, $unrefusedRoot, $readNotice, $writeNotice, $restoreNotice, $serverState, $candidateDirectories, $unrefusedRoots, $previousContents, $servedPaths, $commandOutput, $commandExitCode, $configTestPassed, $baselineTestPassed, $linkWasMade, $writtenThisRun, $moduleIsLoaded, $dropInExists, $existingWasRead, $dropInWasWritten, $restoreWorked, $moduleStatus, $hostedLocation, $mayRepair);
  return array($ConfigurationIsValid, $ConfigurationsWritten); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to download an update package to a temporary location.
// / curl is used rather than wget because GitHub redirects a tarball request twice, first
// / to codeload.github.com & then to object storage. curl -L follows the chain. A wget
// / based updater was attempted previously & was defeated by exactly this.
// / The download is bounded by size & by time so a hostile or broken source cannot fill
// / the disk or hold the process open indefinitely.
function downloadUpdatePackage($targetURL, $downloadPath) {
  // / Set variables.
  global $Verbose, $MaxUpdatePackageSize, $UpdateConnectionTimeout, $EnableMemoryProtection;
  $PackageDownloaded = FALSE;
  $curlCommand = '';
  $curlOutput = array();
  $curlExitCode = 1;
  if ($Verbose) logEntry('Downloading update package.');
  // / -q reads no .curlrc & has to come first, so no configuration file changes this request.
  // / -L follows redirects. -f fails on an HTTP error rather than writing the error page.
  // / --max-filesize refuses a package larger than the configured ceiling.
  // / --max-time bounds the whole transfer, not just the connection.
  $curlCommand = 'curl -q -L -f -s'
    .' --max-time '.(int)$UpdateConnectionTimeout
    .' --max-filesize '.(int)$MaxUpdatePackageSize
    .' -H '.escapeshellarg('User-Agent: '.engineApplicationName().'-Updater')
    .' -o '.escapeshellarg($downloadPath)
    .' '.escapeshellarg($targetURL).' 2>/dev/null';
  exec($curlCommand, $curlOutput, $curlExitCode);
  // / curl exits 22 on an HTTP error, which for a tag means the tag does not exist.
  if ($curlExitCode === 22) errorEntry('The requested update version does not exist at the update source!', 29002, FALSE);
  else if ($curlExitCode === 63) errorEntry('The update package is larger than the configured maximum!', 29003, FALSE);
  else if ($curlExitCode !== 0) errorEntry('Could not download the update package! Curl exited with code '.$curlExitCode.'.', 29004, FALSE);
  else if (!file_exists($downloadPath) or filesize($downloadPath) < 1024) errorEntry('The downloaded update package is empty or too small to be valid!', 29005, FALSE);
  else $PackageDownloaded = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $curlCommand, $curlOutput, $curlExitCode, $targetURL, $downloadPath);
  return $PackageDownloaded; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to merge the live configuration into a freshly downloaded one.
// / The NEW file provides the structure, the comments & the set of settings that exist.
// / The OLD file provides the values, for every setting that exists in both.
// / A setting that is new in this release keeps the new default.
// / A setting that was removed in this release is discarded with the old file.
// / A setting whose name was corrected is treated as a removal & an addition. No attempt
// / is made to guess that two differently spelled names mean the same thing.
// /
// / ARRAYS ARE NOT MERGED. The new default is kept & the difference is reported.
// / A scalar can be substituted into the new file safely. An array cannot be rewritten
// / without reformatting it, which would destroy the alignment & comments in the new file,
// / & a partial merge of a two hundred element format list is worse than either version.
// / The administrator is told which arrays differed so an intentional change can be
// / reapplied by hand.
function mergeConfigFile($oldConfigPath, $newConfigPath) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $ConfigMerged = FALSE;
  $ChangedArrays = $PreservedSettings = array();
  $oldValues = $matches = $match = array();
  $oldContents = $newContents = $mergedContents = $settingName = $settingValue = $exportedValue = $newValue = $oldValue = '';
  $bytesWritten = 0;
  // / Read every scalar assignment out of the OLD file without executing it.
  // / The old file is trusted PHP, but reading it by pattern avoids any side effect &
  // / avoids polluting this scope with seventy variables.
  $oldContents = @file_get_contents($oldConfigPath);
  $newContents = @file_get_contents($newConfigPath);
  if ($oldContents === FALSE or $newContents === FALSE) errorEntry('Could not read a configuration file during the merge!', 29006, FALSE);
  else {
    // / Collect every assignment in the old file. Arrays are captured but not substituted.
    if (preg_match_all('/^\$([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.+?);\s*$/m', $oldContents, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $match) $oldValues[$match[1]] = trim($match[2]); }
    // / Walk the NEW file & substitute the old value wherever both files define a setting.
    $mergedContents = preg_replace_callback(
      '/^\$([A-Za-z_][A-Za-z0-9_]*)(\s*=\s*)(.+?);\s*$/m',
      function ($match) use ($oldValues, &$ChangedArrays, &$PreservedSettings) {
        $settingName = $match[1];
        $newValue = trim($match[3]);
        // / A setting that does not exist in the old file keeps the new default.
        if (!isset($oldValues[$settingName])) return $match[0];
        $oldValue = $oldValues[$settingName];
        // / An array keeps the new default. Report it only if the old value differed.
        if (stripos($newValue, 'array(') === 0 or stripos($oldValue, 'array(') === 0 or strpos($newValue, '[') === 0) {
          if ($oldValue !== $newValue) array_push($ChangedArrays, $settingName);
          return $match[0]; }
        // / A scalar takes the old value. Nothing else on the line is touched, so every
        // / comment & every alignment in the new file survives.
        if ($oldValue !== $newValue) array_push($PreservedSettings, $settingName);
        return '$'.$settingName.$match[2].$oldValue.';'; },
      $newContents);
    if (!is_string($mergedContents)) errorEntry('Could not merge the configuration file!', 29007, FALSE);
    else {
      $bytesWritten = @file_put_contents($newConfigPath, $mergedContents, LOCK_EX);
      if ($bytesWritten === strlen($mergedContents)) $ConfigMerged = TRUE;
      else errorEntry('Could not write the merged configuration file!', 29008, FALSE); } }
  if ($Verbose && $ConfigMerged) logEntry('Configuration merged. '.count($PreservedSettings).' setting(s) carried over. '.count($ChangedArrays).' array(s) reset to the new default.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $match, $newValue, $oldValue, $oldContents, $newContents, $mergedContents, $matches, $oldValues, $settingName, $settingValue, $exportedValue, $bytesWritten, $oldConfigPath, $newConfigPath);
  return array($ConfigMerged, $PreservedSettings, $ChangedArrays); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to update the application in place.
// / Called by the -u & --update command line arguments. NEVER reachable from the web.
// / Replacing application code requires write access to the installation directory, which
// / is the correct authorization for this operation & is what shell access already implies.
// / An HTTP endpoint protected by a secret would turn that into one guessable string.
// /
// / The sequence is stage, swap, validate, rollback on failure.
// / Nothing touches the live installation until a complete & correct replacement exists.
// / The previous installation is retained until the NEXT update runs, so an administrator
// / who discovers a problem an hour later still has something to restore by hand.
// /
// / NOTHING THE UPDATE TOUCHES IS EVER THE WEB SERVER ACCOUNT'S. Until v4.0.1 the release was
// / downloaded & unpacked inside a session folder that account owns, & the staged copy was
// / handed to it with chown -R before root ran it to validate it, so the web server account
// / could edit code root was about to run. The work now happens beside the installation, in a
// / folder only root can change, & the update refuses to start when that folder is not one.
// / The staged copy is root's & is 0700 until it is in place, so nothing serves it early.
// / The archive's own owners are ignored, so a release cannot hand a file to another account.
function updateApplication($requestedVersion) {
  // / Set variables.
  global $InstLoc, $ProprietaryLoc, $DirSep, $EngineApplicationVersion, $AutoUpdateTargetVersion, $EnableAutoUpdates, $BackupLoc, $PermissionLevels, $Lol, $EnableMemoryProtection;
  $UpdateSucceeded = $targetResolved = $packageDownloaded = $configMerged = $installationIsValid = FALSE;
  $updatePermissionsFixed = $alreadyInstalled = $binaryIsReady = FALSE;
  $binaryStatus = $binaryDetail = '';
  $updateRepairExitCode = 1;
  $swapCompleted = $rolledBack = FALSE;
  $targetVersion = $targetURL = $workDir = $downloadPath = $extractedDir = $stagedDir = $oldDir = $backupOutput = '';
  $preservedSettings = $changedArrays = $extractOutput = $extractedRoots = array();
  $extractExitCode = $backupExitCode = 1;
  $parentIsTrusted = $backupIsHeld = FALSE;
  $resolvedParent = $trustNotice = $resolvedBackupParent = $backupNotice = '';
  $backupStatus = array();
  // / An update must be explicitly enabled. A server that does not want this cannot get it.
  if (!$EnableAutoUpdates) {
    errorEntry('Automatic updates are disabled in config.php!', 29009, FALSE);
    print($Lol.'Automatic updates are disabled. Set $EnableAutoUpdates to TRUE in config.php.'.$Lol.$Lol); }
  else {
    // / The command line argument overrides the configured default when one is supplied.
    if ($requestedVersion === '') $requestedVersion = $AutoUpdateTargetVersion;
    list ($targetResolved, $targetVersion, $targetURL) = resolveUpdateTarget($requestedVersion);
    // / Refuse to reinstall the version that is already running.
    // / edge is always fetched, because master moves without the version stamp changing.
    if ($targetResolved && $targetVersion !== 'edge' && ltrim($targetVersion, 'vV') === ltrim($EngineApplicationVersion, 'vV')) {
      $targetResolved = FALSE;
      print($Lol.'Version '.$targetVersion.' is already installed. Nothing to do.'.$Lol.$Lol);
      logEntry('Update requested but '.$targetVersion.' is already installed.');
      // / The requested version is installed when this returns, which is what success means.
      // / It returned FALSE here until v3.9.9, which read as a failure to anything checking.
      // / NOTHING WAS INSTALLED, SO NOTHING BELOW MAY RUN. Every step after this one is gated
      // / on success & assumes a new release arrived, so each is gated on this as well.
      // / Without it a no-op would repair permissions, report an update & name a backup.
      $UpdateSucceeded = $alreadyInstalled = TRUE; } }
  // / The folder the swap happens in must be one only root can change, or the web server
  // / account could rename the installation aside & put its own in its place.
  if ($targetResolved && !$alreadyInstalled) {
    list ($parentIsTrusted, $resolvedParent, $trustNotice) = pathIsTrustedByRoot(dirname($InstLoc));
    if ($parentIsTrusted && dirname($InstLoc) !== rtrim((string)$ProprietaryLoc, $DirSep)) list ($parentIsTrusted, $resolvedParent, $trustNotice) = pathIsTrustedByRoot($ProprietaryLoc);
    if (!$parentIsTrusted) {
      $targetResolved = FALSE;
      errorEntry('The update was refused, because the folder it would happen in can be changed by an account other than root. '.$trustNotice, 31023, FALSE);
      print($Lol.'The update was refused. '.$trustNotice.$Lol.'Run  sudo php '.engineApplicationEntryPoint().' -fp  & try again. A folder above '.$ProprietaryLoc.' is not'.$Lol.'this application\'s & -fp leaves it alone. See error 31023 in Documentation/ERROR_DESCRIPTIONS.txt.'.$Lol.$Lol); } }
  // / Download & stage the release.
  if ($targetResolved && !$alreadyInstalled) {
    $workDir = dirname($InstLoc).$DirSep.'.'.basename($InstLoc).'-update-'.bin2hex(random_bytes(8));
    $downloadPath = $workDir.$DirSep.'package.tar.gz';
    $stagedDir = $workDir.$DirSep.'staged';
    if (!@mkdir($workDir, $PermissionLevels, TRUE)) errorEntry('Could not create a working directory for the update!', 29010, FALSE);
    else {
      print($Lol.'Downloading '.$targetVersion.' ...'.$Lol);
      $packageDownloaded = downloadUpdatePackage($targetURL, $downloadPath); } }
  // / Extract the package & locate the single directory GitHub wraps every archive in.
  // / GITHUB'S ARCHIVES CARRY 0664 & 0775, because git archive applies umask 002. Extracted as
  // / they are, every file in the new release could be written by root's group, root would
  // / refuse to run it, & every update would fail validation & roll back. The modes are taken
  // / through umask 022 instead, which is what a release built by hand already has.
  if ($packageDownloaded) {
    @mkdir($stagedDir, $PermissionLevels, TRUE);
    exec('umask 022 && tar --no-same-owner --no-same-permissions -xzf '.escapeshellarg($downloadPath).' -C '.escapeshellarg($stagedDir).' 2>&1', $extractOutput, $extractExitCode);
    if ($extractExitCode !== 0) errorEntry('Could not extract the update package!', 29011, FALSE);
    else {
      // / A GitHub tarball contains exactly one top level directory named for the ref.
      $extractedRoots = array_diff(scandir($stagedDir), array('.', '..'));
      if (count($extractedRoots) !== 1) errorEntry('The update package does not have the expected structure!', 29012, FALSE);
      else {
        $extractedDir = $stagedDir.$DirSep.reset($extractedRoots);
        // / A release that does not contain a core is not a release.
        if (!file_exists($extractedDir.$DirSep.engineApplicationEntryPoint())) errorEntry('The update package does not contain '.engineApplicationEntryPoint().'!', 29013, FALSE);
        else {
          print('Merging configuration ...'.$Lol);
          list ($configMerged, $preservedSettings, $changedArrays) = mergeConfigFile(
            $InstLoc.$DirSep.'Resources'.$DirSep.'config.php',
            $extractedDir.$DirSep.'Resources'.$DirSep.'config.php'); } } } }
  // / Carry the live data directory across & perform the swap.
  if ($configMerged) {
    $oldDir = $InstLoc.'.old';
    // / A previous update should not have left this behind, but a crashed one might have.
    if (is_dir($oldDir)) exec('rm -rf '.escapeshellarg($oldDir).' 2>&1');
    // / The staged copy must be on the same filesystem as the installation, or rename()
    // / is not atomic & may not work at all. It is moved beside the installation first.
    // / Beside it by name too, so the work folder, the staged copy & .old share one folder.
    $stagedDir = $InstLoc.'.new';
    if (is_dir($stagedDir)) exec('rm -rf '.escapeshellarg($stagedDir).' 2>&1');
    if (!@rename($extractedDir, $stagedDir)) {
      // / rename() across filesystems fails, so fall back to a copy.
      exec('cp -a '.escapeshellarg($extractedDir).' '.escapeshellarg($stagedDir).' 2>&1'); }
    if (!is_dir($stagedDir)) errorEntry('Could not stage the update beside the installation!', 29014, FALSE);
    else {
      // / Nothing serves the staged copy before it is in place.
      @chmod($stagedDir, 0700);
      // / DATA holds live user sessions & is moved rather than copied. It stays the web server
      // / account's. Everything else in the new release is root's, & its own -fp sets its modes.
      // / THE RELEASE SHIPS A DATA FOLDER OF ITS OWN, holding only its placeholder page, & a
      // / folder cannot be renamed over one that is not empty. Until v4.0.1 that rename failed
      // / in silence, the live DATA left with the old installation, & was deleted with it, so a
      // / successful update deleted every session. The release's own copy is removed first, &
      // / an update whose DATA cannot be moved stops here rather than going on without it.
      // / An installation in Application holds no DATA, because DATA stays in the hosted location,
      // / so from v4.0.1 on there is nothing to move & only the release's own copy is removed.
      if (is_dir($stagedDir.$DirSep.'DATA')) exec('rm -rf '.escapeshellarg($stagedDir.$DirSep.'DATA').' 2>&1');
      if (is_dir($InstLoc.$DirSep.'DATA') && !@rename($InstLoc.$DirSep.'DATA', $stagedDir.$DirSep.'DATA')) errorEntry('Could not move the DATA folder into the update, so the update was stopped & nothing was changed!', 29019, FALSE);
      else print('Swapping installation ...'.$Lol);
      // / Two atomic renames. There is no moment where the installation is incomplete.
      if (is_dir($InstLoc.$DirSep.'DATA')) $swapCompleted = FALSE;
      else if (!@rename($InstLoc, $oldDir)) {
        // / The live DATA is back where it came from, so an update that stops here loses nothing.
        @rename($stagedDir.$DirSep.'DATA', $InstLoc.$DirSep.'DATA');
        errorEntry('Could not move the existing installation aside!', 29015, FALSE); }
      else if (!@rename($stagedDir, $InstLoc)) {
        // / The installation is currently absent. Put it back immediately, & its DATA with it.
        @rename($oldDir, $InstLoc);
        @chmod($InstLoc, 0755);
        @rename($stagedDir.$DirSep.'DATA', $InstLoc.$DirSep.'DATA');
        errorEntry('Could not move the update into place! The previous installation was restored.', 29016, FALSE); }
      else {
        // / The new installation is served from here on & the old one never again.
        @chmod($InstLoc, 0755);
        @chmod($oldDir, 0700);
        $swapCompleted = TRUE; } } }
  // / Validate the swapped installation & roll back if it cannot run.
  if ($swapCompleted) {
    print('Validating ...'.$Lol);
    $installationIsValid = validateInstallation($InstLoc);
    if (!$installationIsValid) {
      warningEntry('The updated installation failed validation. Rolling back.');
      // / Return the data directory to the installation being restored.
      if (is_dir($InstLoc.$DirSep.'DATA')) @rename($InstLoc.$DirSep.'DATA', $oldDir.$DirSep.'DATA');
      // / The installation is about to be REPLACED, so this deletes it. That is the single
      // / most destructive line in this application & it runs as root.
      // / A configuration pointing $InstLoc at a system directory would delete the operating
      // / system instead. The guard resolves symlinks & .. before judging.
      if (!pathIsSafeToModifyRecursively($InstLoc)) errorEntry('The installation location resolved to a system directory & was NOT deleted. Check $InstLoc in config.php. Path: '.$InstLoc.'.', 31018, TRUE);
      exec('rm -rf '.escapeshellarg($InstLoc).' 2>&1');
      // / Rollback is a single rename because the previous installation never left the
      // / filesystem it lived on. This is the reason .old exists at all.
      if (@rename($oldDir, $InstLoc)) {
        // / The old installation was closed to the web server when it was moved aside.
        @chmod($InstLoc, 0755);
        $rolledBack = TRUE;
        errorEntry('The updated installation failed validation & was rolled back!', 29017, FALSE); }
      else errorEntry('The updated installation failed validation & COULD NOT be rolled back!', 29018, TRUE); }
    else $UpdateSucceeded = TRUE; }
  // / Preserve the previous installation outside the web root, then remove it from inside it.
  // / .old sits in $ProprietaryLoc because rename() is only atomic within one filesystem,
  // / & $ProprietaryLoc is served by Apache. A stale convertCore.php left there would be
  // / a second, older copy of the application answering requests over HTTP.
  // / The backup location is inside the non hosted data location & is never served.
  // / DATA is excluded. It holds live user sessions, it has already been moved into the
  // / new installation, & a copy of it would be swept on the delete threshold anyway.
  if ($UpdateSucceeded && !$alreadyInstalled && is_dir($oldDir)) {
    // / ROOT KEEPS A BACKUP ONLY WHERE NOTHING ELSE CAN REPLACE IT. Emptying, copying into &
    // / pruning the backup all work by name, so every folder above it must be root's & the
    // / backup itself must not be a link. It sits in the data location by default, which the
    // / web server account owns, so by default it is skipped & a WARNING says so. Until v4.0.1
    // / it was written there regardless. A later release gives it a folder of its own.
    list ($backupIsHeld, $resolvedBackupParent, $backupNotice) = pathIsTrustedByRoot(dirname((string)$BackupLoc));
    if ($backupIsHeld && $resolvedBackupParent !== dirname((string)$BackupLoc)) $backupIsHeld = FALSE;
    clearstatcache(TRUE, (string)$BackupLoc);
    $backupStatus = @lstat((string)$BackupLoc);
    if ($backupIsHeld && is_array($backupStatus) && ((int)$backupStatus['mode'] & 0170000) !== 0040000) {
      $backupIsHeld = FALSE;
      $backupNotice = $BackupLoc.' is not a folder.'; }
    if (!$backupIsHeld) warningEntry('The previous installation was not preserved, because the backup location at '.$BackupLoc.' sits where another account could replace it. '.$backupNotice.' The update itself is unaffected.');
    // / The backup location is emptied before a new backup is written into it.
    // / A misconfigured $BackupLoc would empty whatever it points at instead.
    else if (is_dir($BackupLoc) && !pathIsSafeToModifyRecursively($BackupLoc)) errorEntry('The backup location resolved to a system directory & was NOT emptied. Check $BackupLoc in config.php. Path: '.$BackupLoc.'.', 31019, TRUE);
    else {
      if (is_dir($BackupLoc)) exec('rm -rf '.escapeshellarg($BackupLoc).' 2>&1');
      exec('cp -a '.escapeshellarg($oldDir).' '.escapeshellarg($BackupLoc).' 2>&1', $backupOutput, $backupExitCode);
      if ($backupExitCode === 0 && is_dir($BackupLoc)) {
        if (is_dir($BackupLoc.$DirSep.'DATA')) exec('rm -rf '.escapeshellarg($BackupLoc.$DirSep.'DATA').' 2>&1');
        // / The backup is root's alone. -fp below gives it its role, root-only, & until v4.0.1
        // / it was handed to the web server account, so a restore could reinstall its edits.
        logEntry('Previous installation preserved at '.$BackupLoc.'.'); }
      else warningEntry('Could not preserve the previous installation at '.$BackupLoc.'. It will be discarded.'); }
    // / The previous installation must not remain inside the web root under any outcome.
    exec('rm -rf '.escapeshellarg($oldDir).' 2>&1'); }
  // / Correct everything the new files arrived with. An update ships a fresh tree owned by
  // / whoever unpacked it, & a release that changes a policy, a service unit or a required
  // / version leaves the installed copies behind. Running the repair here is the difference
  // / between an update that works & one that needs an operator to know to run -fp next.
  // / THE NEW RELEASE REPAIRS ITSELF, as its own process. This process is the old release, &
  // / its exact pins refuse the new release's Setup Core, so until v4.0.1 the repair here was
  // / the old one, could not load Setup Core, & corrected nothing. The new release knows its
  // / own locations & roles, which is also the only way a release can change them.
  if ($UpdateSucceeded && !$alreadyInstalled) {
    print($Lol.'Correcting permissions, policies & the service unit for the new release.'.$Lol);
    // / AN UPDATE IS ONE OF THE TWO TIMES THIS APPLICATION'S OWN COPY OF BUBBLEWRAP CHANGES, so it is
    // / brought up to date before the new release's -fp, which installs only the profile it carries.
    // / This process is the old release, so an update from a release older than v4.0.5, which has
    // / no copy to bring up to date, leaves it to --setup --update-depends once the update is done.
    // / The answer is not acted on. installSandboxBinary() prints the copy's line & warns where it
    // / is not ready, & the -fp that follows reports what that leaves.
    if (function_exists('installSandboxBinary')) list ($binaryIsReady, $binaryStatus, $binaryDetail) = installSandboxBinary(12);
    passthru('php '.escapeshellarg($InstLoc.$DirSep.engineApplicationEntryPoint()).' -fp', $updateRepairExitCode);
    $updatePermissionsFixed = ($updateRepairExitCode === 0);
    if (!$updatePermissionsFixed) warningEntry('The update completed but the new release could not correct its permissions & policies. Run  sudo php '.engineApplicationEntryPoint().' -fp  by hand.'); }
  // / Report the outcome.
  if ($UpdateSucceeded && !$alreadyInstalled) {
    logEntry('Application updated to '.$targetVersion.' from '.$EngineApplicationVersion.'.');
    print($Lol.'Updated to '.$targetVersion.'.'.$Lol);
    print('The previous installation is preserved at '.$BackupLoc.'.'.$Lol);
    if (count($preservedSettings) > 0) print($Lol.count($preservedSettings).' configuration setting(s) were carried over.'.$Lol);
    if (count($changedArrays) > 0) {
      print($Lol.'The following array settings were RESET to the new defaults.'.$Lol);
      print('Reapply any intentional changes by hand.'.$Lol);
      foreach ($changedArrays as $changedArray) print('  $'.$changedArray.$Lol); }
    print($Lol.'Run  php '.engineApplicationEntryPoint().' -v  to confirm the new installation.'.$Lol.$Lol); }
  else if ($rolledBack) print($Lol.'The update failed validation & was rolled back. The previous version is running.'.$Lol.$Lol);
  // / Remove the temporary working directory whatever the outcome.
  if ($workDir !== '' && is_dir($workDir)) exec('rm -rf '.escapeshellarg($workDir).' 2>&1');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $backupExitCode, $backupOutput, $changedArray, $targetResolved, $packageDownloaded, $configMerged, $installationIsValid, $swapCompleted, $rolledBack, $targetVersion, $targetURL, $workDir, $downloadPath, $extractedDir, $stagedDir, $oldDir, $preservedSettings, $changedArrays, $extractOutput, $extractedRoots, $extractExitCode, $requestedVersion, $updatePermissionsFixed, $updateRepairExitCode, $alreadyInstalled, $binaryIsReady, $binaryStatus, $binaryDetail, $parentIsTrusted, $resolvedParent, $trustNotice, $backupIsHeld, $resolvedBackupParent, $backupNotice, $backupStatus);
  return $UpdateSucceeded; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove the build & development environments when config.php asks for it.
// / Accepts nothing & is run by root from the -fp argument.
// / Returns a success boolean, then whether the build environment & the development
// / documentation are gone, in that order. Gone means gone, whoever removed them.
// / These paths live under the application directory, not part of the regular cleanup routine.
// /
// / UNTIL v4.0.1 EVERY WEB REQUEST RAN THIS AS THE WEB SERVER ACCOUNT. That only worked while
// / the account could write to the installation, & it no longer can, because the code is
// / root's. Root removes by name here, so every name goes through removeFileWithoutFollowing()
// / & the Build folder is not entered until root trusts it. Before -fp first gives the code to
// / root, the web server account can put a link to /etc where the Build folder belongs.
function cleanBuildEnvironment() {
  // / Set variables.
  global $Verbose, $DeleteBuildEnvironment, $DeleteDevelopmentDocumentation, $DirSep, $EnableMemoryProtection, $RunningAsRoot;
  $BuildEnvCleaned = TRUE;
  $BuildEnvDeleted = $DevDocsDeleted = $buildDirIsTrusted = $entryWasRemoved = FALSE;
  $removalTargets = array();
  $buildDirContents = FALSE;
  $buildDirEntry = $removalTarget = $removalNotice = $resolvedBuildDir = $trustNotice = '';
  // / The installation this file belongs to, three folders above it, as correctManagedLocations()
  // / finds it. PHP resolves every link in __DIR__, so none of the names below is reached through
  // / one. This read dirname(__FILE__) from v4.0.3, when it moved here from convertCore.php, which
  // / is this file's own folder in the Engine. Nothing was ever found there, so nothing was ever
  // / removed, & each was reported NOT PRESENT.
  $installDir = dirname(__DIR__, 3);
  $dockerFile = $installDir.$DirSep.'Documentation'.$DirSep.'Build'.$DirSep.'Dockerfile';
  $changelogFile = $installDir.$DirSep.'Documentation'.$DirSep.'CHANGELOG.txt';
  $readmeFile = $installDir.$DirSep.'README.md';
  // / The Build folder in the installation directory, as config.php describes it. This read
  // / dirname(__DIR__) until v4.0.0, which is the folder ABOVE the installation, so it never
  // / found its own Build folder & would have emptied a Build folder belonging to a neighbour.
  $buildDir = $installDir.$DirSep.'Build';
  clearstatcache();
  $buildDirStatus = @lstat($buildDir);
  if (!$RunningAsRoot && ($DeleteBuildEnvironment or $DeleteDevelopmentDocumentation)) {
    $BuildEnvCleaned = FALSE;
    warningEntry('The build environment was not removed. Only root removes it, from the -fp argument.'); }
  // / Delete the build environment if specified by config.php.
  else if ($DeleteBuildEnvironment) {
    if ($Verbose) logEntry('Removing the build environment.');
    $removalTargets = array($dockerFile);
    // / A Build that is not a folder, a link included, is one name & is removed as one.
    if (is_array($buildDirStatus) && ((int)$buildDirStatus['mode'] & 0170000) !== 0040000) $removalTargets[] = $buildDir;
    // / A Build folder is emptied one name at a time, & only once root trusts it.
    else if (is_array($buildDirStatus)) {
      list ($buildDirIsTrusted, $resolvedBuildDir, $trustNotice) = pathIsTrustedByRoot($buildDir);
      if ($buildDirIsTrusted && $resolvedBuildDir === $buildDir) $buildDirContents = @scandir($buildDir);
      else warningEntry('The Build folder was left as it is, because root does not trust it. '.($trustNotice !== '' ? $trustNotice : $buildDir.' leads to '.$resolvedBuildDir.'.'));
      if (is_array($buildDirContents)) foreach (array_diff($buildDirContents, array('.', '..')) as $buildDirEntry) $removalTargets[] = $buildDir.$DirSep.$buildDirEntry; }
    foreach ($removalTargets as $removalTarget) {
      list ($entryWasRemoved, $removalNotice) = removeFileWithoutFollowing($removalTarget);
      if (!$entryWasRemoved) warningEntry($removalNotice); }
    // / A folder left inside Build keeps it in place, which is the documented behaviour.
    if (is_array($buildDirContents)) @rmdir($buildDir);
    // / Confirm the build environment is actually gone before reporting success.
    clearstatcache();
    if (@lstat($dockerFile) === FALSE && @lstat($buildDir) === FALSE) $BuildEnvDeleted = TRUE;
    else {
      $BuildEnvCleaned = FALSE;
      errorEntry('Could not remove the build environment!', 26000, FALSE); }
    if ($Verbose && $BuildEnvDeleted) logEntry('Removed the build environment.'); }
  // / Delete the development documentation if specified by config.php.
  if ($RunningAsRoot && $DeleteDevelopmentDocumentation) {
    if ($Verbose) logEntry('Removing the development documentation.');
    $removalTargets = array($changelogFile, $readmeFile);
    foreach ($removalTargets as $removalTarget) {
      list ($entryWasRemoved, $removalNotice) = removeFileWithoutFollowing($removalTarget);
      if (!$entryWasRemoved) warningEntry($removalNotice); }
    // / Confirm the development documentation is actually gone before reporting success.
    clearstatcache();
    if (@lstat($changelogFile) === FALSE && @lstat($readmeFile) === FALSE) $DevDocsDeleted = TRUE;
    else {
      $BuildEnvCleaned = FALSE;
      errorEntry('Could not remove the development documentation!', 26001, FALSE); }
    if ($Verbose && $DevDocsDeleted) logEntry('Removed the development documentation.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $installDir, $dockerFile, $changelogFile, $readmeFile, $buildDir, $buildDirStatus, $buildDirContents, $buildDirEntry, $buildDirIsTrusted, $resolvedBuildDir, $trustNotice, $removalTargets, $removalTarget, $removalNotice, $entryWasRemoved);
  return array($BuildEnvCleaned, $BuildEnvDeleted, $DevDocsDeleted); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write the Environment Manager's units again, wherever they are installed.
// / Accepts nothing. Returns 'not installed', 'unchanged', 'updated' or 'failed'.
// / --setup --install-complete installs them, & may run from a copy it then installs, so until
// / v4.0.5 they named that copy for as long as they were installed. -fp calls this, so the installed
// / copy's own -fp writes them for the installed copy, as it writes the listener's. Units that are
// / not installed are not installed here, & nothing is enabled, started or said to be disabled,
// / because whether the timer runs is the administrator's & is left exactly as it was.
function refreshEnvironmentTimer() {
  // / Set variables.
  global $RunningAsRoot, $Lol, $EnableMemoryProtection;
  $TimerStatus = 'not installed';
  $servicePath = $timerPath = $serviceContents = $timerContents = '';
  $commandOutput = array();
  $commandExitCode = 1;
  $servicePath = '/etc/systemd/system/'.engineApplicationSlug().'-environment.service';
  $timerPath = '/etc/systemd/system/'.engineApplicationSlug().'-environment.timer';
  if ($RunningAsRoot && is_file($timerPath)) {
    print($Lol.'Environment Manager schedule'.$Lol);
    list ($serviceContents, $timerContents) = generateEnvironmentTimer();
    if ((string)@file_get_contents($servicePath) === $serviceContents && (string)@file_get_contents($timerPath) === $timerContents) {
      $TimerStatus = 'unchanged';
      print('  '.str_pad('Unchanged', 12).$timerPath.$Lol); }
    else if (@file_put_contents($servicePath, $serviceContents) !== strlen($serviceContents) or @file_put_contents($timerPath, $timerContents) !== strlen($timerContents)) {
      $TimerStatus = 'failed';
      errorEntry('The Environment Manager timer could not be written to '.$timerPath.'!', 32015, FALSE); }
    else {
      @chmod($servicePath, 0644);
      @chmod($timerPath, 0644);
      // / This runs unsandboxed, as every systemctl call does, because reloading the host's service
      // / manager is its whole purpose. It takes no argument from anywhere.
      exec('systemctl daemon-reload 2>&1', $commandOutput, $commandExitCode);
      $TimerStatus = 'updated';
      print('  '.str_pad('Updated', 12).$timerPath.'. Runs '.serviceEntryFolder().'. Enabled state unchanged.'.$Lol);
      logEntry('The Environment Manager timer was written again to '.$timerPath.'.'); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $servicePath, $timerPath, $serviceContents, $timerContents, $commandOutput, $commandExitCode);
  return $TimerStatus; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to install the tree a root command ran from, & hand the rest to the installed copy.
// / Accepts the exit status the command earned. Returns the exit status to leave with.
// /
// / -fp & --setup --install-complete install the application when they run from a tree that is
// / not the one in Application in the data location, which is how every release before v4.0.1
// / runs & how a release unpacked anywhere runs. It happens as the command ENDS & never part way
// / through, because the hosted location the command may have started from is about to hold only
// / the loader, & nothing may still be running from it when it does. By then the command has
// / given the tree to root & made the data location root's, which installing needs.
// / The installed copy then runs -fp, which serves it from the hosted location & does every other
// / repair again from the installed tree, & its exit status is the one this leaves with. A tree
// / that cannot be installed leaves with status one, because what -fp is for is not done, & the
// / application goes on running from where it is.
// / A LISTENER RUNNING AS THE TREE IS INSTALLED STILL RUNS THE RELEASE IT STARTED WITH, & is said to.
// / The installed copy's -fp says so only when it changes the unit, & the unit names the installed
// / copy either way, so it is said here when the unit did not change. It is not restarted, for the
// / reason installListenerService() gives.
function handOverToApplication($commandStatus) {
  // / Set variables.
  global $RunningAsRoot, $RunningFromCLI, $InstLoc, $ApplicationLoc, $BackupLoc, $Lol, $EnableMemoryProtection;
  $ExitStatus = (int)$commandStatus;
  $cliArguments = $commandOutput = array();
  $cliArgumentCount = $childStatus = 0;
  $commandExitCode = 1;
  $cliCommand = $cliTarget = $cliSecondTarget = $installNotice = $phpBinary = $listenerUnitPath = $listenerUnitBefore = $binaryStatus = $binaryDetail = '';
  $cliConfirmed = $cliVectorParsed = $commandInstalls = $treeWasInstalled = $listenerWasRunning = $binaryIsReady = FALSE;
  if ($RunningAsRoot && $RunningFromCLI) {
    list ($cliArguments, $cliArgumentCount, $cliCommand, $cliTarget, $cliSecondTarget, $cliConfirmed, $cliVectorParsed) = parseArgumentVector(isset($_SERVER['argv']) ? $_SERVER['argv'] : array());
    $commandInstalls = ($cliCommand === '-fp' or $cliCommand === '--fix-permissions' or ($cliCommand === '--setup' && $cliTarget === '--install-complete')); }
  if ($commandInstalls && (string)@realpath($InstLoc) !== (string)@realpath($ApplicationLoc) && function_exists('installApplicationTree')) {
    print($Lol.'Installation'.$Lol);
    list ($treeWasInstalled, $installNotice) = installApplicationTree($InstLoc, $ApplicationLoc, $BackupLoc);
    print('  '.str_pad(($treeWasInstalled ? 'Installed' : 'NOT DONE'), 12).$installNotice.$Lol);
    if (!$treeWasInstalled) $ExitStatus = 1;
    else {
      $phpBinary = ((string)PHP_BINARY !== '') ? (string)PHP_BINARY : 'php';
      $listenerUnitPath = '/etc/systemd/system/'.engineApplicationSlug().'-listener.service';
      $listenerUnitBefore = is_file($listenerUnitPath) ? (string)@file_get_contents($listenerUnitPath) : '';
      // / This runs unsandboxed, as every systemctl call does, because it reads the host's own
      // / service state. It takes no argument but this application's own unit name.
      if ($listenerUnitBefore !== '' && systemdIsUsable()[0]) {
        exec('systemctl is-active --quiet '.escapeshellarg(engineApplicationSlug().'-listener').' 2>&1', $commandOutput, $commandExitCode);
        $listenerWasRunning = ($commandExitCode === 0); }
      // / INSTALLING THIS TREE IS AN APPLICATION UPDATE, which is one of the two times this
      // / application's own copy of bubblewrap changes, & a dependency update is the other. So the
      // / copy is brought up to date here, by this release, before the installed copy's -fp, which
      // / installs only the profile it carries. The answer is not acted on. installSandboxBinary()
      // / prints the copy's line & warns where it is not ready, & the installed copy's -fp reports
      // / what that leaves, so the install goes on without it.
      if (function_exists('installSandboxBinary')) list ($binaryIsReady, $binaryStatus, $binaryDetail) = installSandboxBinary(12);
      print('  '.str_pad('Running', 12).$ApplicationLoc.DIRECTORY_SEPARATOR.engineApplicationEntryPoint().' -fp'.$Lol);
      passthru(escapeshellarg($phpBinary).' '.escapeshellarg($ApplicationLoc.DIRECTORY_SEPARATOR.engineApplicationEntryPoint()).' -fp', $childStatus);
      $ExitStatus = (int)$childStatus;
      if ($listenerWasRunning && (string)@file_get_contents($listenerUnitPath) === $listenerUnitBefore) print($Lol.'  '.str_pad('Note', 12).'The listener still runs the release it started with. Restart it.'.$Lol.'  '.str_pad('', 12).'systemctl restart '.engineApplicationSlug().'-listener'.$Lol); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cliArguments, $commandOutput, $cliArgumentCount, $childStatus, $commandExitCode, $cliCommand, $cliTarget, $cliSecondTarget, $installNotice, $phpBinary, $listenerUnitPath, $listenerUnitBefore, $binaryStatus, $binaryDetail, $cliConfirmed, $cliVectorParsed, $commandInstalls, $treeWasInstalled, $listenerWasRunning, $binaryIsReady, $commandStatus);
  return $ExitStatus; }
// / -----------------------------------------------------------------------------------
