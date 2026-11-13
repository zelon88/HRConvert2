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
// / The Workflow Core, one of the Engine's cores.
// / A workflow is a list of steps, & each step runs one of the application's operations on files a user selected or an earlier step made.
// / This file reads, checks & runs workflows, & keeps the status a page reads while one runs.
// / A workflow comes from a library, or is built in a page & handed over as a document, which is checked exactly as a library's is.
// / Which operations exist is the application's, read through $EngineOperationProvider, so this file names none of them.
// / Select is this file's own step. It reads the session's files again through the lister the application names, & runs nothing.
// / The application pins it exactly via $RequiredWorkflowCoreVersion & loads it only when a workflow is asked for.
// / Error block 38000 through 38019 is reserved for workflows, & 38000 through 38006 are raised here.
// / See Documentation/ABOUT_WORKFLOWS.txt for the format & the contract this file enforces.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by an application.
// / quickDie is not available here, because a component loaded directly has no core to
// / provide it. This is the one documented exception to quickDie being the only halt.
// / It names the Engine, as every other Engine core does.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! Engine-35000, A core component cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The version of this component. Read by the application without executing this file.
$WorkflowCoreVersion = 'v4.1.6';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to read what the application says about workflows.
// / Accepts nothing. Returns the settings, keyed by what this core calls them.
// / Each is read through applicationSetting(), so this core never knows what the application calls a setting.
// / Builder lets a page run a workflow it built, & ImportExport lets a page export a workflow & import one into its builder.
// / A limit below one is raised to one, & the runtime is returned in seconds.
// / The shipped library is named by $EngineShippedWorkflows relative to the installation, & the installed one by the WorkflowLibrary setting.
// / The run folder is where every running worker is counted, below the socket folder, which every request & worker of this application can write.
function workflowSettings() {
  // / Set variables.
  global $InstLoc, $DirSep, $ManagerSocketDir, $EngineShippedWorkflows, $EnableMemoryProtection;
  $WorkflowSettings = array();
  $shippedFolder = $runFolder = '';
  if (isset($EngineShippedWorkflows) && is_string($EngineShippedWorkflows) && trim($EngineShippedWorkflows) !== '') $shippedFolder = rtrim((string)$InstLoc, $DirSep).$DirSep.trim(trim($EngineShippedWorkflows), $DirSep);
  if (isset($ManagerSocketDir) && (string)$ManagerSocketDir !== '') $runFolder = rtrim((string)$ManagerSocketDir, $DirSep).$DirSep.'Workflows';
  $WorkflowSettings = array(
    'Enabled' => ((bool)applicationSetting('EnableWorkflows', FALSE) === TRUE),
    'Builder' => ((bool)applicationSetting('EnableWorkflowBuilder', FALSE) === TRUE),
    'ImportExport' => ((bool)applicationSetting('EnableWorkflowImportExport', FALSE) === TRUE),
    'MaxSteps' => max(1, (int)applicationSetting('MaxWorkflowSteps', 16)),
    'MaxFiles' => max(1, (int)applicationSetting('MaxWorkflowFiles', 100)),
    'MaxRuntime' => max(1, (int)applicationSetting('MaxWorkflowRuntime', 30)) * 60,
    'MaxConcurrent' => max(1, (int)applicationSetting('MaxConcurrentWorkflows', 4)),
    'SessionFolder' => rtrim((string)applicationSetting('SessionFolder', ''), $DirSep),
    'SessionContext' => (string)applicationSetting('SessionContext', ''),
    'InstalledFolder' => rtrim((string)applicationSetting('WorkflowLibrary', ''), $DirSep),
    'ShippedFolder' => $shippedFolder,
    'RunFolder' => $runFolder);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $shippedFolder, $runFolder);
  return $WorkflowSettings; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to check one operation the application declared.
// / Accepts the operation's name & its entry. Returns whether it is usable & the entry as this core reads it, in that order.
// / An operation names the function that runs it, the budget it takes, whether it is enabled, the extensions it reads, the modes it runs in & its options.
// / An empty list of extensions reads every file. A function named by Accepts decides for each file, given the step's options.
// / The first mode is the one a step that names none runs in.
function validateOperationEntry($operationName, $operationEntry) {
  // / Set variables.
  global $EnableMemoryProtection;
  $EntryIsUsable = FALSE;
  $CleanEntry = array();
  $modeName = $optionName = $optionType = $acceptsFunction = '';
  $cleanModes = $cleanOptions = $optionSpec = array();
  if (!is_string($operationName) or preg_match('/^[A-Za-z][A-Za-z0-9]{0,31}$/', $operationName) !== 1) warningEntry('The operation provider declared an operation whose name is not a plain word. It was left out.');
  else if (!is_array($operationEntry) or !isset($operationEntry['Invoke']) or !is_string($operationEntry['Invoke']) or !function_exists($operationEntry['Invoke'])) warningEntry('The '.$operationName.' operation names no function that exists. It was left out.');
  else if (!isset($operationEntry['Budget']) or !is_string($operationEntry['Budget']) or trim($operationEntry['Budget']) === '') warningEntry('The '.$operationName.' operation names no budget. It was left out.');
  else {
    if (isset($operationEntry['Accepts']) && is_string($operationEntry['Accepts'])) $acceptsFunction = $operationEntry['Accepts'];
    if (isset($operationEntry['Modes']) && is_array($operationEntry['Modes'])) foreach ($operationEntry['Modes'] as $modeName) if (in_array($modeName, array('each', 'together'), TRUE) && !in_array($modeName, $cleanModes, TRUE)) $cleanModes[] = $modeName;
    if (isset($operationEntry['Options']) && is_array($operationEntry['Options'])) foreach ($operationEntry['Options'] as $optionName => $optionSpec) {
      redeclare($optionType, (is_array($optionSpec) && isset($optionSpec['Type']) && is_string($optionSpec['Type'])) ? $optionSpec['Type'] : '');
      if (is_string($optionName) && preg_match('/^[A-Za-z][A-Za-z0-9]{0,31}$/', $optionName) === 1 && in_array($optionType, array('choice', 'name', 'integer', 'boolean'), TRUE)) $cleanOptions[$optionName] = $optionSpec;
      else warningEntry('The '.$operationName.' operation declares an option that is not usable. It was left out of the operation.'); }
    if (empty($cleanModes)) warningEntry('The '.$operationName.' operation declares no mode it runs in. It was left out.');
    else if ($acceptsFunction !== '' && !function_exists($acceptsFunction)) warningEntry('The '.$operationName.' operation names '.$acceptsFunction.' to decide which files it reads, & no such function exists. It was left out.');
    else {
      $CleanEntry = array('Name' => $operationName, 'Invoke' => $operationEntry['Invoke'], 'Budget' => trim($operationEntry['Budget']), 'Accepts' => $acceptsFunction,
        'Enabled' => (isset($operationEntry['Enabled']) && $operationEntry['Enabled'] === TRUE),
        'Inputs' => (isset($operationEntry['Inputs']) && is_array($operationEntry['Inputs'])) ? array_values(array_unique(array_map('strtolower', array_filter($operationEntry['Inputs'], 'is_string')))) : array(),
        'Modes' => $cleanModes, 'Options' => $cleanOptions);
      $EntryIsUsable = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $modeName, $optionName, $optionType, $acceptsFunction, $cleanModes, $cleanOptions, $optionSpec, $operationName, $operationEntry);
  return array($EntryIsUsable, $CleanEntry); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the operations the application lets a workflow run, & the kinds of file it names.
// / Accepts nothing. Returns whether the application supplied a usable list, the operations & the kinds of file, in that order.
// / The provider is named by $EngineOperationProvider in the application's contract, & returns an array holding Operations & FileTypes.
// / An entry that is not usable is left out & named, so one bad entry costs that operation & nothing else.
// / A kind of file is a word naming a list of extensions, such as Image, & a step's filter may name it.
// / A provider that also names SessionFiles, a function listing a session folder's files as its pages list them, is given the Select step.
// / Select is this core's own, marked BuiltIn, & carries that function as its Lister. It is added after the application's, so it is never theirs.
function readOperationRegistry() {
  // / Set variables.
  global $EngineOperationProvider, $EnableMemoryProtection;
  $RegistryIsUsable = FALSE;
  $Operations = $FileTypes = array();
  $providerName = $operationName = $typeName = $listerName = '';
  $providerReply = $operationEntry = $typeExtensions = $cleanEntry = array();
  $entryIsUsable = FALSE;
  $providerName = (isset($EngineOperationProvider) && is_string($EngineOperationProvider)) ? trim($EngineOperationProvider) : '';
  if ($providerName === '' or !function_exists($providerName)) errorEntry('The application names no operation provider, so no workflow can run. It is named by $EngineOperationProvider in '.contractPath('app-engine-config.php').'.', 38000, FALSE);
  else {
    $providerReply = $providerName();
    if (!is_array($providerReply) or !isset($providerReply['Operations']) or !is_array($providerReply['Operations'])) errorEntry('The operation provider '.$providerName.' returned no list of operations, so no workflow can run.', 38001, FALSE);
    else {
      foreach ($providerReply['Operations'] as $operationName => $operationEntry) {
        list ($entryIsUsable, $cleanEntry) = validateOperationEntry($operationName, $operationEntry);
        if ($entryIsUsable && (string)$operationName === 'Select') warningEntry('The operation provider '.$providerName.' declared Select, which is the Workflow Core\'s own step. It was left out.');
        else if ($entryIsUsable) $Operations[(string)$operationName] = $cleanEntry; }
      if (isset($providerReply['FileTypes']) && is_array($providerReply['FileTypes'])) foreach ($providerReply['FileTypes'] as $typeName => $typeExtensions) {
        if (is_string($typeName) && preg_match('/^[A-Za-z][A-Za-z0-9]{0,31}$/', $typeName) === 1 && is_array($typeExtensions)) $FileTypes[$typeName] = array_values(array_unique(array_map('strtolower', array_filter($typeExtensions, 'is_string')))); }
      $RegistryIsUsable = (count($Operations) > 0);
      if (!$RegistryIsUsable) warningEntry('The operation provider '.$providerName.' returned no usable operation, so no workflow can run.');
      $listerName = (isset($providerReply['SessionFiles']) && is_string($providerReply['SessionFiles'])) ? trim($providerReply['SessionFiles']) : '';
      if ($listerName !== '' && !function_exists($listerName)) warningEntry('The operation provider '.$providerName.' names '.$listerName.' to list a session\'s files, & no such function exists. Select is not offered.');
      else if ($RegistryIsUsable && $listerName !== '') $Operations['Select'] = array('Name' => 'Select', 'BuiltIn' => TRUE, 'Lister' => $listerName, 'Invoke' => '', 'Budget' => '', 'Accepts' => '', 'Enabled' => TRUE, 'Inputs' => array(), 'Modes' => array('together'), 'Options' => array()); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $providerName, $operationName, $typeName, $listerName, $providerReply, $operationEntry, $typeExtensions, $cleanEntry, $entryIsUsable);
  return array($RegistryIsUsable, $Operations, $FileTypes); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the text of a workflow into a document.
// / Accepts the text. Returns whether it reads as a workflow, the document & the problems found, in that order.
// / The text is JSON of up to 64 KiB, & must say it is an HRProprietary Workflow of version 1.
// / Nothing here checks what the steps ask for, which validateWorkflow() does once the operations are known.
function parseWorkflowText($workflowText) {
  // / Set variables.
  global $EnableMemoryProtection;
  $WorkflowIsReadable = FALSE;
  $WorkflowDocument = $WorkflowProblems = array();
  $decodedText = NULL;
  if (!is_string($workflowText) or trim($workflowText) === '') $WorkflowProblems[] = 'The workflow is empty.';
  else if (strlen($workflowText) > 65536) $WorkflowProblems[] = 'The workflow is larger than 64 KiB.';
  else {
    $decodedText = json_decode($workflowText, TRUE, 16);
    if (json_last_error() !== JSON_ERROR_NONE or !is_array($decodedText)) $WorkflowProblems[] = 'The workflow is not a JSON object. '.json_last_error_msg().'.';
    else if (!isset($decodedText['Format']) or $decodedText['Format'] !== 'HRProprietary Workflow') $WorkflowProblems[] = 'The workflow does not say it is an HRProprietary Workflow in its Format.';
    else if (!isset($decodedText['Version']) or $decodedText['Version'] !== 1) $WorkflowProblems[] = 'The workflow is not Version 1, which is the only version this core reads.';
    else {
      $WorkflowDocument = $decodedText;
      $WorkflowIsReadable = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $decodedText, $workflowText);
  return array($WorkflowIsReadable, $WorkflowDocument, $WorkflowProblems); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read which files a step takes.
// / Accepts the step's number, counted from one, & what it named as its Input.
// / Returns whether it is usable, the references & the problem, in that order.
// / An Input is selection, previous or step N, or a list of up to 8 of them, & N must be a step before this one.
// / previous is the step before, & for the first step it is the selection.
// / Each reference is kept as selection or as step N, so a reference names the same thing wherever it is read.
function normalizeStepInputs($stepNumber, $inputEntry) {
  // / Set variables.
  global $EnableMemoryProtection;
  $InputsAreValid = TRUE;
  $InputReferences = array();
  $InputProblem = '';
  $inputList = $referenceMatch = array();
  $inputItem = NULL;
  $inputList = is_array($inputEntry) ? $inputEntry : array($inputEntry);
  if (count($inputList) < 1 or count($inputList) > 8) $InputsAreValid = FALSE;
  foreach ($inputList as $inputItem) {
    if (!is_string($inputItem)) $InputsAreValid = FALSE;
    else if ($inputItem === 'selection' or ($inputItem === 'previous' && (int)$stepNumber === 1)) $InputReferences[] = 'selection';
    else if ($inputItem === 'previous') $InputReferences[] = 'step '.((int)$stepNumber - 1);
    else if (preg_match('/^step ([1-9][0-9]?)$/', $inputItem, $referenceMatch) === 1 && (int)$referenceMatch[1] < (int)$stepNumber) $InputReferences[] = 'step '.(int)$referenceMatch[1];
    else $InputsAreValid = FALSE; }
  if (!$InputsAreValid) {
    $InputReferences = array();
    $InputProblem = 'Step '.$stepNumber.' has an Input that is not selection, previous or step N, where N is a step before it, or a list of up to 8 of them.'; }
  else $InputReferences = array_values(array_unique($InputReferences));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $inputList, $referenceMatch, $inputItem, $stepNumber, $inputEntry);
  return array($InputsAreValid, $InputReferences, $InputProblem); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read a size a filter names.
// / Accepts the size, as a whole number of bytes or as text of a number followed by K, M or G, with an optional B.
// / Returns whether it is a size & the bytes it names, in that order. K is 1024 bytes, M is 1024 K & G is 1024 M.
function parseByteSize($sizeValue) {
  // / Set variables.
  global $EnableMemoryProtection;
  $SizeIsValid = FALSE;
  $SizeBytes = 0;
  $sizeMatch = array();
  $sizeMultipliers = array('' => 1, 'K' => 1024, 'M' => 1048576, 'G' => 1073741824);
  if (is_int($sizeValue) && $sizeValue >= 0) {
    $SizeBytes = $sizeValue;
    $SizeIsValid = TRUE; }
  else if (is_string($sizeValue) && preg_match('/^\s*([0-9]{1,12})\s*([KMG]?)B?\s*$/i', $sizeValue, $sizeMatch) === 1) {
    $SizeBytes = (int)$sizeMatch[1] * $sizeMultipliers[strtoupper($sizeMatch[2])];
    $SizeIsValid = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $sizeMatch, $sizeMultipliers, $sizeValue);
  return array($SizeIsValid, $SizeBytes); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the filter a step narrows its files with.
// / Accepts the step's number, its Filter & the kinds of file, in that order.
// / Returns whether it is usable, the filter as the resolver reads it & the problems found, in that order.
// / Glob is a shell pattern & Regex a delimited PCRE pattern, & both are matched against the file's name.
// / Types names kinds of file the application declares, & Extensions names extensions.
// / MinimumSize & MaximumSize are bytes, or a number followed by K, M or G.
// / A file must pass every test the filter names, & a filter that names none passes every file.
function normalizeStepFilter($stepNumber, $filterEntry, $fileTypes) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FilterIsValid = FALSE;
  $NormalizedFilter = array('Glob' => '', 'Regex' => '', 'Types' => array(), 'Extensions' => array(), 'MinimumSize' => -1, 'MaximumSize' => -1);
  $FilterProblems = array();
  $filterKey = '';
  $typeName = $extensionName = NULL;
  $sizeIsValid = FALSE;
  $sizeBytes = 0;
  if (!is_array($filterEntry) or (count($filterEntry) > 0 && array_keys($filterEntry) === range(0, count($filterEntry) - 1))) $FilterProblems[] = 'Step '.$stepNumber.' has a Filter that is not an object.';
  else {
    foreach (array_keys($filterEntry) as $filterKey) if (!in_array($filterKey, array('Glob', 'Regex', 'Types', 'Extensions', 'MinimumSize', 'MaximumSize'), TRUE)) $FilterProblems[] = 'Step '.$stepNumber.' has a Filter key this format does not know, '.(string)$filterKey.'.';
    if (isset($filterEntry['Glob'])) {
      if (is_string($filterEntry['Glob']) && $filterEntry['Glob'] !== '' && strlen($filterEntry['Glob']) <= 128 && strpos($filterEntry['Glob'], '/') === FALSE) $NormalizedFilter['Glob'] = $filterEntry['Glob'];
      else $FilterProblems[] = 'Step '.$stepNumber.' has a Glob that is not a pattern of 1 to 128 characters without a slash.'; }
    if (isset($filterEntry['Regex'])) {
      if (is_string($filterEntry['Regex']) && $filterEntry['Regex'] !== '' && strlen($filterEntry['Regex']) <= 256 && @preg_match($filterEntry['Regex'], '') !== FALSE) $NormalizedFilter['Regex'] = $filterEntry['Regex'];
      else $FilterProblems[] = 'Step '.$stepNumber.' has a Regex that is not a delimited PCRE pattern of up to 256 characters.'; }
    if (isset($filterEntry['Types'])) {
      if (!is_array($filterEntry['Types']) or count($filterEntry['Types']) === 0) $FilterProblems[] = 'Step '.$stepNumber.' has Types that is not a list of kinds of file.';
      else foreach ($filterEntry['Types'] as $typeName) {
        if (is_string($typeName) && isset($fileTypes[$typeName])) $NormalizedFilter['Types'][] = $typeName;
        else $FilterProblems[] = 'Step '.$stepNumber.' names a kind of file this application does not declare. It declares '.implode(', ', array_keys($fileTypes)).'.'; } }
    if (isset($filterEntry['Extensions'])) {
      if (!is_array($filterEntry['Extensions']) or count($filterEntry['Extensions']) === 0) $FilterProblems[] = 'Step '.$stepNumber.' has Extensions that is not a list of extensions.';
      else foreach ($filterEntry['Extensions'] as $extensionName) {
        if (is_string($extensionName) && preg_match('/^[A-Za-z0-9]{1,16}$/', $extensionName) === 1) $NormalizedFilter['Extensions'][] = strtolower($extensionName);
        else $FilterProblems[] = 'Step '.$stepNumber.' names an extension that is not 1 to 16 letters & digits.'; } }
    foreach (array('MinimumSize', 'MaximumSize') as $filterKey) if (isset($filterEntry[$filterKey])) {
      list ($sizeIsValid, $sizeBytes) = parseByteSize($filterEntry[$filterKey]);
      if ($sizeIsValid) $NormalizedFilter[$filterKey] = $sizeBytes;
      else $FilterProblems[] = 'Step '.$stepNumber.' has a '.$filterKey.' that is not a number of bytes, or a number followed by K, M or G.'; }
    if ($NormalizedFilter['MinimumSize'] >= 0 && $NormalizedFilter['MaximumSize'] >= 0 && $NormalizedFilter['MinimumSize'] > $NormalizedFilter['MaximumSize']) $FilterProblems[] = 'Step '.$stepNumber.' has a MinimumSize larger than its MaximumSize.'; }
  $FilterIsValid = empty($FilterProblems);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $filterKey, $typeName, $extensionName, $sizeIsValid, $sizeBytes, $stepNumber, $filterEntry, $fileTypes);
  return array($FilterIsValid, $NormalizedFilter, $FilterProblems); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to check the options a step gives its operation, & fill in what it leaves out.
// / Accepts the step's number, the operation's name, the options given & the options the operation declares, in that order.
// / Returns whether they are usable, every option the operation declares with its value & the problems found, in that order.
// / A choice is one of the values the operation lists, compared without regard to case.
// / A name is text of up to 64 characters with no slash, no backslash & no leading dot, & the operation makes it safe for a filename.
// / An integer is a whole number within the bounds the operation declares, & a boolean is true or false.
// / An option left out takes its default, & one that is required & has no default is a problem.
function normalizeStepOptions($stepNumber, $operationName, $optionEntries, $optionSchema) {
  // / Set variables.
  global $EnableMemoryProtection;
  $OptionsAreValid = FALSE;
  $NormalizedOptions = $OptionProblems = array();
  $optionName = $optionType = $choiceValue = $choiceHint = '';
  $optionSpec = $lowerChoices = array();
  $optionValue = NULL;
  $valueIsValid = FALSE;
  if (!is_array($optionEntries) or (count($optionEntries) > 0 && array_keys($optionEntries) === range(0, count($optionEntries) - 1))) $OptionProblems[] = 'Step '.$stepNumber.' has Options that is not an object.';
  else {
    foreach (array_keys($optionEntries) as $optionName) if (!isset($optionSchema[$optionName])) $OptionProblems[] = 'Step '.$stepNumber.' gives the '.$operationName.' operation an option it does not have, '.(string)$optionName.'.';
    foreach ($optionSchema as $optionName => $optionSpec) {
      redeclare($optionType, (string)$optionSpec['Type']);
      $valueIsValid = FALSE;
      if (!array_key_exists($optionName, $optionEntries)) {
        if (array_key_exists('Default', $optionSpec)) $NormalizedOptions[$optionName] = $optionSpec['Default'];
        else if (!empty($optionSpec['Required'])) $OptionProblems[] = 'Step '.$stepNumber.' leaves out '.$optionName.', which the '.$operationName.' operation needs.';
        continue; }
      $optionValue = $optionEntries[$optionName];
      if ($optionType === 'choice' && is_string($optionValue)) {
        redeclare($lowerChoices, array_map('strtolower', (isset($optionSpec['Choices']) && is_array($optionSpec['Choices'])) ? array_map('strval', $optionSpec['Choices']) : array()));
        redeclare($choiceValue, strtolower(trim($optionValue)));
        if (in_array($choiceValue, $lowerChoices, TRUE)) {
          $valueIsValid = TRUE;
          $optionValue = $choiceValue; } }
      else if ($optionType === 'name' && is_string($optionValue)) $valueIsValid = (strlen($optionValue) <= 64 && strpos($optionValue, '/') === FALSE && strpos($optionValue, '\\') === FALSE && strpos($optionValue, '.') !== 0 && preg_match('/[\x00-\x1F\x7F]/', $optionValue) !== 1);
      else if ($optionType === 'integer' && is_int($optionValue)) $valueIsValid = ((!isset($optionSpec['Minimum']) or $optionValue >= (int)$optionSpec['Minimum']) && (!isset($optionSpec['Maximum']) or $optionValue <= (int)$optionSpec['Maximum']));
      else if ($optionType === 'boolean' && is_bool($optionValue)) $valueIsValid = TRUE;
      if ($valueIsValid) $NormalizedOptions[$optionName] = $optionValue;
      else {
        redeclare($choiceHint, ($optionType === 'choice' && isset($optionSpec['Choices']) && is_array($optionSpec['Choices']) && count($optionSpec['Choices']) <= 20) ? ' It accepts '.implode(', ', array_map('strval', $optionSpec['Choices'])).'.' : '');
        $OptionProblems[] = 'Step '.$stepNumber.' gives '.$optionName.' a value the '.$operationName.' operation does not accept.'.$choiceHint; } } }
  $OptionsAreValid = empty($OptionProblems);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $optionName, $optionType, $choiceValue, $choiceHint, $optionSpec, $lowerChoices, $optionValue, $valueIsValid, $stepNumber, $operationName, $optionEntries, $optionSchema);
  return array($OptionsAreValid, $NormalizedOptions, $OptionProblems); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to check one step & put it in the form the runner reads.
// / Accepts the step's number, counted from one, the step, the operations & the kinds of file, in that order.
// / Returns whether it is valid, the normalized step & the problems found, in that order.
// / A step names its Operation, & may name its Input, a Filter, a Mode, an Order & Options.
// / Input defaults to previous, Mode to the operation's first mode & Order to selection.
// / Order is selection, the order the files arrived in, or name.
// / A Select step reads every file in the session when it starts, so it takes no Input, & its Input is kept as session.
function normalizeWorkflowStep($stepNumber, $stepEntry, $operations, $fileTypes) {
  // / Set variables.
  global $EnableMemoryProtection;
  $StepIsValid = FALSE;
  $NormalizedStep = $StepProblems = array();
  $stepKey = $operationName = $inputProblem = '';
  $stepMode = $stepOrder = NULL;
  $inputReferences = $normalizedFilter = $filterProblems = $normalizedOptions = $optionProblems = $operationEntry = array();
  $inputsAreValid = $filterIsValid = $optionsAreValid = FALSE;
  if (!is_array($stepEntry) or count($stepEntry) === 0 or array_keys($stepEntry) === range(0, count($stepEntry) - 1)) $StepProblems[] = 'Step '.$stepNumber.' is not an object.';
  else {
    foreach (array_keys($stepEntry) as $stepKey) if (!in_array($stepKey, array('Operation', 'Input', 'Filter', 'Mode', 'Order', 'Options'), TRUE)) $StepProblems[] = 'Step '.$stepNumber.' has a key this format does not know, '.(string)$stepKey.'.';
    $operationName = (isset($stepEntry['Operation']) && is_string($stepEntry['Operation'])) ? $stepEntry['Operation'] : '';
    if ($operationName === '' or !isset($operations[$operationName])) $StepProblems[] = 'Step '.$stepNumber.' names an operation this application does not offer. It offers '.implode(', ', array_keys($operations)).'.';
    else {
      $operationEntry = $operations[$operationName];
      if (empty($operationEntry['BuiltIn'])) list ($inputsAreValid, $inputReferences, $inputProblem) = normalizeStepInputs($stepNumber, isset($stepEntry['Input']) ? $stepEntry['Input'] : 'previous');
      else if (isset($stepEntry['Input'])) list ($inputsAreValid, $inputReferences, $inputProblem) = array(FALSE, array(), 'Step '.$stepNumber.' is a Select step, which reads every file in the session, so it takes no Input.');
      else list ($inputsAreValid, $inputReferences, $inputProblem) = array(TRUE, array('session'), '');
      if (!$inputsAreValid) $StepProblems[] = $inputProblem;
      list ($filterIsValid, $normalizedFilter, $filterProblems) = normalizeStepFilter($stepNumber, isset($stepEntry['Filter']) ? $stepEntry['Filter'] : array(), $fileTypes);
      if (!$filterIsValid) redeclare($StepProblems, array_merge($StepProblems, $filterProblems));
      $stepMode = isset($stepEntry['Mode']) ? $stepEntry['Mode'] : $operationEntry['Modes'][0];
      if (!is_string($stepMode) or !in_array($stepMode, $operationEntry['Modes'], TRUE)) $StepProblems[] = 'Step '.$stepNumber.' asks for a Mode the '.$operationName.' operation does not run in. It runs in '.implode(' or ', $operationEntry['Modes']).'.';
      $stepOrder = isset($stepEntry['Order']) ? $stepEntry['Order'] : 'selection';
      if (!is_string($stepOrder) or !in_array($stepOrder, array('selection', 'name'), TRUE)) $StepProblems[] = 'Step '.$stepNumber.' asks for an Order that is neither selection nor name.';
      list ($optionsAreValid, $normalizedOptions, $optionProblems) = normalizeStepOptions($stepNumber, $operationName, isset($stepEntry['Options']) ? $stepEntry['Options'] : array(), $operationEntry['Options']);
      if (!$optionsAreValid) redeclare($StepProblems, array_merge($StepProblems, $optionProblems)); } }
  if (empty($StepProblems)) {
    $NormalizedStep = array('Operation' => $operationName, 'Input' => $inputReferences, 'Filter' => $normalizedFilter, 'Mode' => $stepMode, 'Order' => $stepOrder, 'Options' => $normalizedOptions);
    $StepIsValid = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $stepKey, $operationName, $inputProblem, $stepMode, $stepOrder, $inputReferences, $normalizedFilter, $filterProblems, $normalizedOptions, $optionProblems, $operationEntry, $inputsAreValid, $filterIsValid, $optionsAreValid, $stepNumber, $stepEntry, $operations, $fileTypes);
  return array($StepIsValid, $NormalizedStep, $StepProblems); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to check a workflow against the operations this application offers & the limits it sets.
// / Accepts the document, the operations, the kinds of file & the settings, in that order.
// / Returns whether it is valid, the workflow as the runner reads it & every problem found, in that order.
// / Every step is checked, rather than stopping at the first problem, so one reading names everything to fix.
// / A name is plain text of 1 to 80 characters, & a description is up to 500.
// / A key this format does not know is a problem, because a misspelt key would otherwise be a setting silently ignored.
// / Whether each operation is enabled is not checked here, because a valid workflow stays valid while an administrator turns one off.
function validateWorkflow($workflowDocument, $operations, $fileTypes, $workflowSettings) {
  // / Set variables.
  global $EnableMemoryProtection;
  $WorkflowIsValid = FALSE;
  $NormalizedWorkflow = $WorkflowProblems = array();
  $documentKey = $workflowName = $workflowDescription = '';
  $stepIndex = 0;
  $stepEntry = $normalizedStep = $stepProblems = $normalizedSteps = array();
  $stepIsValid = FALSE;
  foreach (array_keys((array)$workflowDocument) as $documentKey) if (!in_array($documentKey, array('Format', 'Version', 'Name', 'Description', 'Steps'), TRUE)) $WorkflowProblems[] = 'The workflow has a key this format does not know, '.(string)$documentKey.'.';
  $workflowName = (isset($workflowDocument['Name']) && is_string($workflowDocument['Name'])) ? trim($workflowDocument['Name']) : '';
  $workflowDescription = (isset($workflowDocument['Description']) && is_string($workflowDocument['Description'])) ? trim($workflowDocument['Description']) : '';
  if (preg_match('/^[^\x00-\x1F\x7F]{1,80}$/u', $workflowName) !== 1) $WorkflowProblems[] = 'The workflow needs a Name of 1 to 80 characters, with no control characters.';
  if (isset($workflowDocument['Description']) && (!is_string($workflowDocument['Description']) or preg_match('/^[^\x00-\x08\x0B-\x1F\x7F]{0,500}$/u', $workflowDescription) !== 1)) $WorkflowProblems[] = 'The workflow has a Description that is not text of up to 500 characters.';
  if (!isset($workflowDocument['Steps']) or !is_array($workflowDocument['Steps']) or count($workflowDocument['Steps']) === 0 or array_keys($workflowDocument['Steps']) !== range(0, count($workflowDocument['Steps']) - 1)) $WorkflowProblems[] = 'The workflow needs Steps, a list holding at least one step.';
  else if (count($workflowDocument['Steps']) > (int)$workflowSettings['MaxSteps']) $WorkflowProblems[] = 'The workflow has '.count($workflowDocument['Steps']).' steps, & this server runs at most '.(int)$workflowSettings['MaxSteps'].'.';
  else foreach ($workflowDocument['Steps'] as $stepIndex => $stepEntry) {
    list ($stepIsValid, $normalizedStep, $stepProblems) = normalizeWorkflowStep($stepIndex + 1, $stepEntry, $operations, $fileTypes);
    if ($stepIsValid) $normalizedSteps[] = $normalizedStep;
    else redeclare($WorkflowProblems, array_merge($WorkflowProblems, $stepProblems)); }
  if (empty($WorkflowProblems)) {
    $NormalizedWorkflow = array('Name' => $workflowName, 'Description' => $workflowDescription, 'Steps' => $normalizedSteps);
    $WorkflowIsValid = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $documentKey, $workflowName, $workflowDescription, $stepIndex, $stepEntry, $normalizedStep, $stepProblems, $normalizedSteps, $stepIsValid, $workflowDocument, $operations, $fileTypes, $workflowSettings);
  return array($WorkflowIsValid, $NormalizedWorkflow, $WorkflowProblems); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read a workflow's text & check it against this application, in one reading.
// / Accepts the text, the operations, the kinds of file & the settings, in that order.
// / Returns whether it is valid, the workflow as the runner reads it, every problem found & whether it is usable, in that order.
// / A workflow is usable when it is valid & every operation it names is enabled. Each step naming one that is not is a problem of its own.
// / A library's file, a file the command line names & a workflow a page sends are all read here, so each is held to the same rules.
function checkWorkflowText($workflowText, $operations, $fileTypes, $workflowSettings) {
  // / Set variables.
  global $EnableMemoryProtection;
  $WorkflowIsValid = $WorkflowIsUsable = FALSE;
  $NormalizedWorkflow = $WorkflowProblems = array();
  $workflowDocument = $parseProblems = $validationProblems = $normalizedStep = array();
  $workflowIsReadable = FALSE;
  $stepIndex = 0;
  list ($workflowIsReadable, $workflowDocument, $parseProblems) = parseWorkflowText($workflowText);
  if ($workflowIsReadable) list ($WorkflowIsValid, $NormalizedWorkflow, $validationProblems) = validateWorkflow($workflowDocument, $operations, $fileTypes, $workflowSettings);
  $WorkflowProblems = array_merge($parseProblems, $validationProblems);
  if ($WorkflowIsValid) {
    $WorkflowIsUsable = TRUE;
    foreach ($NormalizedWorkflow['Steps'] as $stepIndex => $normalizedStep) if (empty($operations[$normalizedStep['Operation']]['Enabled'])) {
      $WorkflowIsUsable = FALSE;
      $WorkflowProblems[] = 'Step '.($stepIndex + 1).' uses '.$normalizedStep['Operation'].', which is turned off on this server.'; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $workflowDocument, $parseProblems, $validationProblems, $normalizedStep, $workflowIsReadable, $stepIndex, $workflowText, $operations, $fileTypes, $workflowSettings);
  return array($WorkflowIsValid, $NormalizedWorkflow, $WorkflowProblems, $WorkflowIsUsable); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether one file passes a step's filter.
// / Accepts the file's name, its size in bytes, the filter & the kinds of file, in that order. Returns TRUE when it passes.
// / A Glob is matched without regard to case, because a user does not choose the case of a name a camera or a scanner gives.
// / A Regex is matched as written, so a pattern that should ignore case says so with i.
function fileMatchesStepFilter($fileName, $fileSize, $normalizedFilter, $fileTypes) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FileMatches = TRUE;
  $fileExtension = $typeName = '';
  $typeMatched = FALSE;
  $fileExtension = strtolower((string)pathinfo((string)$fileName, PATHINFO_EXTENSION));
  if ($normalizedFilter['Glob'] !== '' && !fnmatch(strtolower($normalizedFilter['Glob']), strtolower((string)$fileName))) $FileMatches = FALSE;
  if ($FileMatches && $normalizedFilter['Regex'] !== '' && preg_match($normalizedFilter['Regex'], (string)$fileName) !== 1) $FileMatches = FALSE;
  if ($FileMatches && !empty($normalizedFilter['Extensions']) && !in_array($fileExtension, $normalizedFilter['Extensions'], TRUE)) $FileMatches = FALSE;
  if ($FileMatches && !empty($normalizedFilter['Types'])) {
    foreach ($normalizedFilter['Types'] as $typeName) if (isset($fileTypes[$typeName]) && in_array($fileExtension, $fileTypes[$typeName], TRUE)) $typeMatched = TRUE;
    if (!$typeMatched) $FileMatches = FALSE; }
  if ($FileMatches && $normalizedFilter['MinimumSize'] >= 0 && (int)$fileSize < $normalizedFilter['MinimumSize']) $FileMatches = FALSE;
  if ($FileMatches && $normalizedFilter['MaximumSize'] >= 0 && (int)$fileSize > $normalizedFilter['MaximumSize']) $FileMatches = FALSE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $fileExtension, $typeName, $typeMatched, $fileName, $fileSize, $normalizedFilter, $fileTypes);
  return $FileMatches; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether an operation reads a file, before any filter is applied.
// / Accepts the operation, the file's extension & the step's options, in that order. Returns TRUE when it does.
// / An operation that names a function in Accepts asks that function, because what it reads can depend on its options.
// / A conversion to jpg reads an image & not a spreadsheet, though both are files it converts.
// / Otherwise an empty list of extensions reads every file, & any other list reads what it names.
function operationReadsExtension($operationEntry, $fileExtension, $stepOptions) {
  // / Set variables.
  global $EnableMemoryProtection;
  $OperationReads = FALSE;
  $acceptsFunction = '';
  $acceptsFunction = (isset($operationEntry['Accepts']) && is_string($operationEntry['Accepts'])) ? $operationEntry['Accepts'] : '';
  if ($acceptsFunction !== '' && function_exists($acceptsFunction)) $OperationReads = ($acceptsFunction((string)$fileExtension, (array)$stepOptions) === TRUE);
  else if (empty($operationEntry['Inputs'])) $OperationReads = TRUE;
  else $OperationReads = in_array(strtolower((string)$fileExtension), $operationEntry['Inputs'], TRUE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $acceptsFunction, $operationEntry, $fileExtension, $stepOptions);
  return $OperationReads; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to list the files one step runs on.
// / Accepts the step, the files the user selected, what each earlier step produced, the session folder, the operations & the kinds of file, in that order.
// / Returns whether every reference could be resolved, the files in the order the step asks for & how many were left out, in that order.
// / A file named twice is taken once. A file that is no longer a plain file in the session folder is left out & counted.
// / A file the operation does not read, & a file the filter refuses, are left out & counted.
// / Selection order is the order the user chose the files in, & each step's output follows the order it was made.
// / session is every file the application's lister finds in the session folder now, in the order it lists them.
// / The lister is the one a page's file list comes from, so a Select step finds only what a user could see & tick.
function resolveStepInputs($normalizedStep, $selectedFiles, $stepOutputs, $sessionFolder, $operations, $fileTypes) {
  // / Set variables.
  global $DirSep, $EnableMemoryProtection;
  $InputsWereResolved = TRUE;
  $InputFiles = array();
  $SkippedCount = 0;
  $candidateFiles = $fileStatus = $operationEntry = $referenceMatch = $listedFiles = array();
  $inputReference = $candidateName = $candidatePath = $fileExtension = '';
  $operationEntry = isset($operations[$normalizedStep['Operation']]) ? $operations[$normalizedStep['Operation']] : array('Inputs' => array(), 'Accepts' => '');
  foreach ($normalizedStep['Input'] as $inputReference) {
    if ($inputReference === 'selection') redeclare($candidateFiles, array_merge($candidateFiles, array_values((array)$selectedFiles)));
    else if (preg_match('/^step ([0-9]+)$/', (string)$inputReference, $referenceMatch) === 1 && isset($stepOutputs[(int)$referenceMatch[1] - 1])) redeclare($candidateFiles, array_merge($candidateFiles, array_values((array)$stepOutputs[(int)$referenceMatch[1] - 1])));
    else if ($inputReference === 'session' && isset($operationEntry['Lister']) && is_string($operationEntry['Lister']) && function_exists($operationEntry['Lister'])) {
      $listedFiles = call_user_func($operationEntry['Lister'], (string)$sessionFolder);
      if (is_array($listedFiles)) redeclare($candidateFiles, array_merge($candidateFiles, array_values(array_filter($listedFiles, 'is_string'))));
      else $InputsWereResolved = FALSE; }
    else $InputsWereResolved = FALSE; }
  foreach (array_values(array_unique(array_map('strval', $candidateFiles))) as $candidateName) {
    redeclare($candidatePath, rtrim((string)$sessionFolder, $DirSep).$DirSep.basename($candidateName));
    redeclare($fileExtension, strtolower((string)pathinfo($candidateName, PATHINFO_EXTENSION)));
    clearstatcache(TRUE, $candidatePath);
    $fileStatus = @lstat($candidatePath);
    if ($candidateName === '' or basename($candidateName) !== $candidateName or !is_array($fileStatus) or ((int)$fileStatus['mode'] & 0170000) !== 0100000) $SkippedCount++;
    else if (!operationReadsExtension($operationEntry, $fileExtension, $normalizedStep['Options'])) $SkippedCount++;
    else if (!fileMatchesStepFilter($candidateName, (int)$fileStatus['size'], $normalizedStep['Filter'], $fileTypes)) $SkippedCount++;
    else $InputFiles[] = $candidateName; }
  if ($normalizedStep['Order'] === 'name') natcasesort($InputFiles);
  $InputFiles = array_values($InputFiles);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $candidateFiles, $fileStatus, $operationEntry, $referenceMatch, $listedFiles, $inputReference, $candidateName, $candidatePath, $fileExtension, $normalizedStep, $selectedFiles, $stepOutputs, $sessionFolder, $operations, $fileTypes);
  return array($InputsWereResolved, $InputFiles, $SkippedCount); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read a workflow from a file an administrator names on the command line.
// / Accepts the path. Returns whether it was read, the text & the reason it was not, in that order.
// / The text is only ever parsed as data & checked in memory, & what is installed is that checked text, so the file is never trusted after it is read.
// / It must be a plain file with one name & no larger than 64 KiB, & it is opened without blocking, so a FIFO swapped in cannot hold the command.
// / Unlike a read of the library, the folder it is in need not be held for root, because nothing here runs or writes through that folder.
function readWorkflowSourceFile($sourcePath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $SourceWasRead = FALSE;
  $SourceText = $SourceRefusal = '';
  $pathStatus = $handleStatus = array();
  $fileHandle = FALSE;
  clearstatcache(TRUE, (string)$sourcePath);
  $pathStatus = @lstat((string)$sourcePath);
  if ((string)$sourcePath === '') $SourceRefusal = 'No file was named.';
  else if (!is_array($pathStatus)) $SourceRefusal = (string)$sourcePath.' does not exist, or this account cannot reach it.';
  else if (((int)$pathStatus['mode'] & 0170000) !== 0100000 or (int)$pathStatus['nlink'] !== 1) $SourceRefusal = (string)$sourcePath.' is not a plain file with one name.';
  else if ((int)$pathStatus['size'] > 65536) $SourceRefusal = (string)$sourcePath.' is larger than 64 KiB.';
  else {
    $fileHandle = @fopen((string)$sourcePath, 'rn');
    if ($fileHandle === FALSE) $SourceRefusal = (string)$sourcePath.' could not be opened.';
    else {
      $handleStatus = @fstat($fileHandle);
      if (!is_array($handleStatus) or ((int)$handleStatus['mode'] & 0170000) !== 0100000 or $handleStatus['dev'] !== $pathStatus['dev'] or $handleStatus['ino'] !== $pathStatus['ino']) $SourceRefusal = (string)$sourcePath.' changed while it was being opened.';
      else {
        $SourceText = (string)@stream_get_contents($fileHandle, 65537);
        $SourceWasRead = TRUE; }
      @fclose($fileHandle); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $pathStatus, $handleStatus, $fileHandle, $sourcePath);
  return array($SourceWasRead, $SourceText, $SourceRefusal); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read every workflow in one library folder.
// / Accepts the folder, the name of the library it is, the operations, the kinds of file & the settings, in that order.
// / Returns whether the folder could be read & the workflows in it, keyed by identifier, in that order.
// / A workflow's file is named for its identifier followed by -workflow.json, & an identifier is 1 to 64 letters, digits, dashes & underscores.
// / Every file is read with readFileWithoutFollowing(), so a library is read only from a folder no account but root can change.
// / A workflow that does not read or does not validate is kept with its problems, so the command line can name them, & is never offered.
// / A workflow is usable when it is valid & every operation it names is enabled.
function readWorkflowFolder($folderPath, $libraryName, $operations, $fileTypes, $workflowSettings) {
  // / Set variables.
  global $DirSep, $EnableMemoryProtection;
  $FolderWasRead = FALSE;
  $FolderWorkflows = array();
  $folderEntries = $nameMatch = $normalizedWorkflow = $workflowProblems = array();
  $folderEntry = $entryPath = $workflowText = $workflowId = $readNotice = '';
  $workflowIsValid = $workflowIsUsable = $textWasRead = FALSE;
  if ((string)$folderPath !== '' && is_dir($folderPath) && !is_link($folderPath)) {
    $folderEntries = @scandir($folderPath);
    if (is_array($folderEntries)) {
      $FolderWasRead = TRUE;
      foreach ($folderEntries as $folderEntry) {
        if (preg_match('/^([A-Za-z0-9][A-Za-z0-9_-]{0,63})-workflow\.json$/', (string)$folderEntry, $nameMatch) !== 1) continue;
        redeclare($entryPath, $folderPath.$DirSep.$folderEntry);
        redeclare($workflowId, (string)$nameMatch[1]);
        list ($textWasRead, $workflowText, $readNotice) = readFileWithoutFollowing($entryPath, 65537);
        if (!$textWasRead) list ($workflowIsValid, $normalizedWorkflow, $workflowProblems, $workflowIsUsable) = array(FALSE, array(), array('The file could not be read. '.$readNotice), FALSE);
        else list ($workflowIsValid, $normalizedWorkflow, $workflowProblems, $workflowIsUsable) = checkWorkflowText($workflowText, $operations, $fileTypes, $workflowSettings);
        $FolderWorkflows[$workflowId] = array('Id' => $workflowId, 'Library' => $libraryName, 'File' => $folderEntry, 'Valid' => $workflowIsValid, 'Usable' => $workflowIsUsable, 'Workflow' => $normalizedWorkflow, 'Problems' => $workflowProblems);
        if (!$workflowIsValid) warningEntry('The '.$libraryName.' workflow '.$folderEntry.' is not valid & is not offered. Run --workflow --list to read why.'); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderEntries, $nameMatch, $normalizedWorkflow, $workflowProblems, $folderEntry, $entryPath, $workflowText, $workflowId, $readNotice, $workflowIsValid, $workflowIsUsable, $textWasRead, $folderPath, $libraryName, $operations, $fileTypes, $workflowSettings);
  return array($FolderWasRead, $FolderWorkflows); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the shipped library & the installed one.
// / Accepts the settings. Returns whether either could be read, every workflow found keyed by identifier, the operations & the kinds of file, in that order.
// / The shipped library is part of the release, & the installed one is where an administrator adds workflows with --workflow --install.
// / An installed workflow with the identifier of a shipped one takes its place, so an administrator can replace one.
// / The operations are returned too, because every caller needs them & the provider is asked once.
function readWorkflowLibrary($workflowSettings) {
  // / Set variables.
  global $EnableMemoryProtection;
  $LibraryWasRead = FALSE;
  $WorkflowLibrary = $Operations = $FileTypes = array();
  $registryIsUsable = $shippedWasRead = $installedWasRead = FALSE;
  $shippedWorkflows = $installedWorkflows = array();
  list ($registryIsUsable, $Operations, $FileTypes) = readOperationRegistry();
  if ($registryIsUsable) {
    list ($shippedWasRead, $shippedWorkflows) = readWorkflowFolder($workflowSettings['ShippedFolder'], 'shipped', $Operations, $FileTypes, $workflowSettings);
    list ($installedWasRead, $installedWorkflows) = readWorkflowFolder($workflowSettings['InstalledFolder'], 'installed', $Operations, $FileTypes, $workflowSettings);
    // / A union rather than array_merge, because array_merge renumbers an identifier made of digits.
    $WorkflowLibrary = $installedWorkflows + $shippedWorkflows;
    uksort($WorkflowLibrary, 'strnatcasecmp');
    $LibraryWasRead = ($shippedWasRead or $installedWasRead); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $registryIsUsable, $shippedWasRead, $installedWasRead, $shippedWorkflows, $installedWorkflows, $workflowSettings);
  return array($LibraryWasRead, $WorkflowLibrary, $Operations, $FileTypes); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to list the extensions a step reads from the files a user selects, for an interface.
// / Accepts the step, the operations & the kinds of file, in that order.
// / Returns whether it reads every file & the extensions it reads when it does not, in that order.
// / It is a hint for a page that says which selected files a workflow fits. Glob, Regex & sizes are not considered, & the runner decides.
function describeStepAccepts($normalizedStep, $operations, $fileTypes) {
  // / Set variables.
  global $EnableMemoryProtection;
  $AcceptsEveryFile = FALSE;
  $AcceptedExtensions = array();
  $operationEntry = $candidateExtensions = $typeExtensions = array();
  $candidateExtension = '';
  $operationEntry = isset($operations[$normalizedStep['Operation']]) ? $operations[$normalizedStep['Operation']] : array('Inputs' => array(), 'Accepts' => '');
  if (empty($normalizedStep['Filter']['Types']) && empty($normalizedStep['Filter']['Extensions']) && empty($operationEntry['Inputs']) && $operationEntry['Accepts'] === '') $AcceptsEveryFile = TRUE;
  else {
    foreach ($fileTypes as $typeExtensions) redeclare($candidateExtensions, array_merge($candidateExtensions, $typeExtensions));
    redeclare($candidateExtensions, array_values(array_unique(array_merge($candidateExtensions, $operationEntry['Inputs'], $normalizedStep['Filter']['Extensions']))));
    foreach ($candidateExtensions as $candidateExtension) if (operationReadsExtension($operationEntry, $candidateExtension, $normalizedStep['Options']) && fileMatchesStepFilter('file.'.$candidateExtension, 0, array_merge($normalizedStep['Filter'], array('Glob' => '', 'Regex' => '', 'MinimumSize' => -1, 'MaximumSize' => -1)), $fileTypes)) $AcceptedExtensions[] = $candidateExtension;
    sort($AcceptedExtensions); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $operationEntry, $candidateExtensions, $typeExtensions, $candidateExtension, $normalizedStep, $operations, $fileTypes);
  return array($AcceptsEveryFile, $AcceptedExtensions); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to describe the workflows a user may run, for an interface or a caller of the API.
// / Accepts nothing. Returns the reply, holding Available, & Library when workflows are available.
// / Only a usable workflow is described, as its identifier, name, description & steps, & what its first step reads.
// / AcceptsAll is true when the first step reads every file, & Accepts otherwise lists the extensions it reads.
// / NeedsSelection is false when a Select step finds files of its own, so the workflow may be run with no file ticked.
// / Nothing is described that a user does not need, so no path & no problem with the library is ever sent to a browser.
function describeWorkflowLibrary() {
  // / Set variables.
  global $EnableMemoryProtection;
  $LibraryReply = array('Available' => FALSE, 'Library' => array());
  $workflowSettings = $workflowLibrary = $operations = $fileTypes = $workflowRecord = $normalizedStep = $stepSummaries = $acceptedExtensions = array();
  $libraryWasRead = $acceptsEveryFile = $needsSelection = FALSE;
  $workflowId = '';
  $workflowSettings = workflowSettings();
  if ($workflowSettings['Enabled']) {
    list ($libraryWasRead, $workflowLibrary, $operations, $fileTypes) = readWorkflowLibrary($workflowSettings);
    $LibraryReply['Available'] = TRUE;
    foreach ($workflowLibrary as $workflowId => $workflowRecord) {
      if (!$workflowRecord['Usable']) continue;
      redeclare($stepSummaries, array());
      $needsSelection = TRUE;
      foreach ($workflowRecord['Workflow']['Steps'] as $normalizedStep) {
        $stepSummaries[] = array('Operation' => $normalizedStep['Operation'], 'Mode' => $normalizedStep['Mode'], 'Options' => $normalizedStep['Options']);
        if (in_array('session', $normalizedStep['Input'], TRUE)) $needsSelection = FALSE; }
      list ($acceptsEveryFile, $acceptedExtensions) = describeStepAccepts($workflowRecord['Workflow']['Steps'][0], $operations, $fileTypes);
      $LibraryReply['Library'][] = array('Id' => (string)$workflowId, 'Name' => $workflowRecord['Workflow']['Name'], 'Description' => $workflowRecord['Workflow']['Description'], 'Steps' => $stepSummaries, 'AcceptsAll' => $acceptsEveryFile, 'Accepts' => $acceptedExtensions, 'NeedsSelection' => $needsSelection); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $workflowSettings, $workflowLibrary, $operations, $fileTypes, $workflowRecord, $normalizedStep, $stepSummaries, $acceptedExtensions, $libraryWasRead, $acceptsEveryFile, $needsSelection, $workflowId);
  return $LibraryReply; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the folder a session's workflow files are kept in.
// / Accepts the session folder. Returns the folder, Workflow inside the session folder.
// / A file listing leaves folders out, so nothing kept here is ever offered to a user as one of their files.
function workflowFolderPath($sessionFolder) {
  // / Set variables.
  global $DirSep, $EnableMemoryProtection;
  $WorkflowFolder = rtrim((string)$sessionFolder, $DirSep).$DirSep.'Workflow';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $sessionFolder);
  return $WorkflowFolder; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to make sure a session's workflow folder exists & is a folder.
// / Accepts the session folder. Returns whether it is ready & where it is, in that order.
// / It is created 0700 inside a session folder that exists, & a link at its name is refused rather than followed.
function prepareWorkflowFolder($sessionFolder) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FolderIsReady = FALSE;
  $WorkflowFolder = '';
  $folderStatus = array();
  $WorkflowFolder = workflowFolderPath($sessionFolder);
  if ((string)$sessionFolder !== '' && is_dir($sessionFolder) && !file_exists($WorkflowFolder) && !is_link($WorkflowFolder)) @mkdir($WorkflowFolder, 0700);
  clearstatcache(TRUE, $WorkflowFolder);
  $folderStatus = @lstat($WorkflowFolder);
  if (is_array($folderStatus) && ((int)$folderStatus['mode'] & 0170000) === 0040000 && is_writable($WorkflowFolder)) $FolderIsReady = TRUE;
  else warningEntry('A session\'s workflow folder could not be made ready, so no workflow can run in it.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderStatus, $sessionFolder);
  return array($FolderIsReady, $WorkflowFolder); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write one of a workflow's files.
// / Accepts the path, the contents & whether the file must not exist yet. Returns TRUE when the contents are in place.
// / A file that must be new is created exclusively, so it is a claim a second writer cannot also make.
// / Any other file is written beside itself & renamed into place, so a reader never sees half of one, & a rename replaces a link rather than writing through it.
// / Each is readable only by the account that runs this application.
function writeWorkflowFile($filePath, $fileContents, $mustBeNew) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FileWasWritten = FALSE;
  $fileHandle = FALSE;
  $temporaryPath = '';
  $bytesWritten = 0;
  if (!is_string($fileContents)) $FileWasWritten = FALSE;
  else if ($mustBeNew) {
    $fileHandle = @fopen((string)$filePath, 'x');
    if ($fileHandle !== FALSE) {
      $bytesWritten = (int)@fwrite($fileHandle, $fileContents);
      @fclose($fileHandle);
      @chmod((string)$filePath, 0600);
      $FileWasWritten = ($bytesWritten === strlen($fileContents)); } }
  else {
    $temporaryPath = (string)$filePath.'.'.getmypid().'.tmp';
    if (@file_put_contents($temporaryPath, $fileContents, LOCK_EX) === strlen($fileContents)) {
      @chmod($temporaryPath, 0600);
      $FileWasWritten = @rename($temporaryPath, (string)$filePath); }
    if (!$FileWasWritten && file_exists($temporaryPath)) @unlink($temporaryPath); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $fileHandle, $temporaryPath, $bytesWritten, $filePath, $fileContents, $mustBeNew);
  return $FileWasWritten; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read one of a workflow's files.
// / Accepts the path. Returns whether it held a JSON object & the object, in that order.
// / A file larger than 1 MiB is not read, because nothing this core writes is that large.
function readWorkflowRecord($filePath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RecordWasRead = FALSE;
  $RecordData = array();
  $recordText = '';
  $decodedRecord = NULL;
  clearstatcache(TRUE, (string)$filePath);
  if (is_file((string)$filePath) && !is_link((string)$filePath) && (int)@filesize((string)$filePath) <= 1048576) {
    $recordText = (string)@file_get_contents((string)$filePath);
    $decodedRecord = json_decode($recordText, TRUE, 32);
    if (is_array($decodedRecord)) {
      $RecordData = $decodedRecord;
      $RecordWasRead = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $recordText, $decodedRecord, $filePath);
  return array($RecordWasRead, $RecordData); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write a session's workflow status.
// / Accepts the session folder, the status & whether a status must already be there, in that order. Returns TRUE when it was written.
// / A worker writes only over a status that is there, because a status that is gone was cleared, & a cleared session is not given one back.
// / A name that is not valid UTF-8 is written with a replacement character, because the status is only ever read to show what happened.
function writeWorkflowStatus($sessionFolder, $workflowStatus, $mustExist) {
  // / Set variables.
  global $EnableMemoryProtection;
  $StatusWasWritten = FALSE;
  $statusPath = $statusText = '';
  $statusPath = workflowFolderPath($sessionFolder).DIRECTORY_SEPARATOR.'status.json';
  $statusText = (string)json_encode($workflowStatus, JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_SLASHES);
  clearstatcache(TRUE, $statusPath);
  if (!$mustExist or is_file($statusPath)) $StatusWasWritten = writeWorkflowFile($statusPath, $statusText, FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $statusPath, $statusText, $sessionFolder, $workflowStatus, $mustExist);
  return $StatusWasWritten; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether the worker a status names is still running.
// / Accepts the status. Returns TRUE when its process exists & started when the worker said it did.
// / A process identifier is reused once its process ends, so the start time it recorded is compared too.
// / A worker that was killed keeps an entry under /proc until something collects it, so the Engine's test, which reads a zombie as ended, is asked first.
function workflowWorkerIsAlive($workflowStatus) {
  // / Set variables.
  global $EnableMemoryProtection;
  $WorkerIsAlive = $processExists = FALSE;
  $workerPid = $workerStart = 0;
  $workerPid = isset($workflowStatus['WorkerPid']) ? (int)$workflowStatus['WorkerPid'] : 0;
  $workerStart = isset($workflowStatus['WorkerStart']) ? (int)$workflowStatus['WorkerStart'] : 0;
  if ($workerPid > 1) $processExists = function_exists('managerProcessIsAlive') ? managerProcessIsAlive($workerPid) : file_exists('/proc/'.$workerPid);
  if ($processExists) $WorkerIsAlive = ($workerStart === 0 or !function_exists('readProcessStartTime') or readProcessStartTime($workerPid) === $workerStart);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $processExists, $workerPid, $workerStart, $workflowStatus);
  return $WorkerIsAlive; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say what state a workflow is in, from its status & whether its worker is alive.
// / Accepts the status. Returns the state & the reason for it, in that order.
// / A finished, stopped or failed workflow is in the state it wrote.
// / A workflow still starting after a minute never started, & one running with no worker was interrupted, as a restart of the web server does.
// / A status that cannot be read is treated as interrupted, so it can be cleared.
function deriveWorkflowState($workflowStatus) {
  // / Set variables.
  global $EnableMemoryProtection;
  $DerivedState = 'interrupted';
  $DerivedReason = 'unreadable';
  $recordedState = '';
  $recordedState = (is_array($workflowStatus) && isset($workflowStatus['State'])) ? (string)$workflowStatus['State'] : '';
  if (in_array($recordedState, array('finished', 'stopped', 'failed'), TRUE)) list ($DerivedState, $DerivedReason) = array($recordedState, isset($workflowStatus['Reason']) ? (string)$workflowStatus['Reason'] : '');
  else if ($recordedState === 'starting') list ($DerivedState, $DerivedReason) = (time() - (int)($workflowStatus['Created'] ?? 0) > 60) ? array('interrupted', 'start') : array('starting', '');
  else if ($recordedState === 'running') list ($DerivedState, $DerivedReason) = workflowWorkerIsAlive($workflowStatus) ? array('running', '') : array('interrupted', 'worker');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $recordedState, $workflowStatus);
  return array($DerivedState, $DerivedReason); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to clear a session's workflow files, so another workflow may start in it.
// / Accepts the session folder. Returns TRUE when nothing is left but the folder.
// / The status, the job, every stop marker & any file a write left half way is removed, & nothing else.
function releaseWorkflowSession($sessionFolder) {
  // / Set variables.
  global $DirSep, $EnableMemoryProtection;
  $SessionWasReleased = TRUE;
  $workflowFolder = $folderEntry = $entryPath = '';
  $folderEntries = array();
  $workflowFolder = workflowFolderPath($sessionFolder);
  if (is_dir($workflowFolder) && !is_link($workflowFolder)) {
    $folderEntries = @scandir($workflowFolder);
    if (is_array($folderEntries)) foreach ($folderEntries as $folderEntry) {
      if (preg_match('/^(status\.json|job\.json|stop-[a-f0-9]{24})(\.[0-9]+\.tmp)?$/', (string)$folderEntry) !== 1) continue;
      redeclare($entryPath, $workflowFolder.$DirSep.$folderEntry);
      if (!@unlink($entryPath) && file_exists($entryPath)) $SessionWasReleased = FALSE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $workflowFolder, $folderEntry, $entryPath, $folderEntries, $sessionFolder);
  return $SessionWasReleased; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether a workflow is starting or running in a session.
// / Accepts the session folder. Returns TRUE when its status says one is & its worker has not gone.
// / It changes nothing, so a run can be refused as busy before anything is counted or claimed.
function workflowSessionIsBusy($sessionFolder) {
  // / Set variables.
  global $DirSep, $EnableMemoryProtection;
  $SessionIsBusy = $statusWasRead = FALSE;
  $statusPath = $derivedState = $derivedReason = '';
  $currentStatus = array();
  $statusPath = workflowFolderPath($sessionFolder).$DirSep.'status.json';
  clearstatcache(TRUE, $statusPath);
  if (file_exists($statusPath)) {
    list ($statusWasRead, $currentStatus) = readWorkflowRecord($statusPath);
    list ($derivedState, $derivedReason) = deriveWorkflowState($currentStatus);
    $SessionIsBusy = ($derivedState === 'starting' or $derivedState === 'running'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $statusWasRead, $statusPath, $derivedState, $derivedReason, $currentStatus, $sessionFolder);
  return $SessionIsBusy; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to claim a session for one workflow.
// / Accepts the session folder & the status to begin with. Returns whether it was claimed & the reason it was not, in that order.
// / The status file is the claim. It is created only where none exists, so two requests at once cannot both claim the session.
// / A status that has ended, or whose worker is gone, is cleared first, because starting another workflow is how a user moves on from one.
// / The reason is busy when a workflow is running in the session, & start when its folder cannot be used.
function claimWorkflowSession($sessionFolder, $initialStatus) {
  // / Set variables.
  global $DirSep, $EnableMemoryProtection;
  $SessionWasClaimed = FALSE;
  $ClaimRefusal = '';
  $folderIsReady = FALSE;
  $workflowFolder = $statusPath = '';
  list ($folderIsReady, $workflowFolder) = prepareWorkflowFolder($sessionFolder);
  $statusPath = $workflowFolder.$DirSep.'status.json';
  if (!$folderIsReady) $ClaimRefusal = 'start';
  else if (workflowSessionIsBusy($sessionFolder)) $ClaimRefusal = 'busy';
  else {
    // / Whatever is left is an ended status, one whose worker is gone or one that cannot be read, & each is cleared.
    releaseWorkflowSession($sessionFolder);
    $SessionWasClaimed = writeWorkflowFile($statusPath, (string)json_encode($initialStatus, JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_UNESCAPED_SLASHES), TRUE);
    if (!$SessionWasClaimed) $ClaimRefusal = file_exists($statusPath) ? 'busy' : 'start'; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderIsReady, $workflowFolder, $statusPath, $sessionFolder, $initialStatus);
  return array($SessionWasClaimed, $ClaimRefusal); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to take the lock every count & record of running workflows is made under.
// / Accepts the run folder. Returns the lock's handle, or FALSE when the folder cannot be used.
// / The count & the record that follows it are one step, so two requests at once cannot both take the last place.
function lockWorkflowRuns($runFolder) {
  // / Set variables.
  global $DirSep, $EnableMemoryProtection;
  $LockHandle = FALSE;
  if ((string)$runFolder !== '' && !is_dir($runFolder) && !is_link($runFolder)) @mkdir($runFolder, 0700);
  if ((string)$runFolder !== '' && is_dir($runFolder) && !is_link($runFolder)) {
    $LockHandle = @fopen($runFolder.$DirSep.'runs.lock', 'c');
    if ($LockHandle !== FALSE && !@flock($LockHandle, LOCK_EX)) {
      @fclose($LockHandle);
      $LockHandle = FALSE; } }
  if ($LockHandle === FALSE) warningEntry('The folder running workflows are counted in could not be locked, so the limit on running workflows is not applied to this one.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $runFolder);
  return $LockHandle; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to give back the lock lockWorkflowRuns() took.
// / Accepts the handle. Returns TRUE when there was nothing to give back or it was given back.
function unlockWorkflowRuns($lockHandle) {
  // / Set variables.
  global $EnableMemoryProtection;
  $LockWasReleased = TRUE;
  if (is_resource($lockHandle)) {
    $LockWasReleased = @flock($lockHandle, LOCK_UN);
    @fclose($lockHandle); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $lockHandle);
  return $LockWasReleased; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to count the workflows running on this server.
// / Accepts the run folder. Returns whether they could be counted & how many there are, in that order.
// / Each running workflow has a record named for its job. A record whose worker is gone is removed rather than counted.
// / A record with no worker yet counts for a minute, which is how long a worker is given to start.
function countRunningWorkflows($runFolder) {
  // / Set variables.
  global $DirSep, $EnableMemoryProtection;
  $CountIsKnown = FALSE;
  $RunningCount = 0;
  $folderEntries = $runRecord = array();
  $folderEntry = $entryPath = '';
  $recordWasRead = $runIsLive = FALSE;
  $folderEntries = ((string)$runFolder !== '' && is_dir($runFolder) && !is_link($runFolder)) ? @scandir($runFolder) : FALSE;
  if (is_array($folderEntries)) {
    $CountIsKnown = TRUE;
    foreach ($folderEntries as $folderEntry) {
      if (preg_match('/^[a-f0-9]{24}\.run$/', (string)$folderEntry) !== 1) continue;
      redeclare($entryPath, $runFolder.$DirSep.$folderEntry);
      list ($recordWasRead, $runRecord) = readWorkflowRecord($entryPath);
      $runIsLive = FALSE;
      if ($recordWasRead && (int)($runRecord['WorkerPid'] ?? 0) > 1) $runIsLive = workflowWorkerIsAlive($runRecord);
      else if ($recordWasRead) $runIsLive = (time() - (int)($runRecord['Created'] ?? 0) <= 60);
      if ($runIsLive) $RunningCount++;
      else @unlink($entryPath); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderEntries, $runRecord, $folderEntry, $entryPath, $recordWasRead, $runIsLive, $runFolder);
  return array($CountIsKnown, $RunningCount); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to record a running workflow, or the worker that took it up.
// / Accepts the run folder, the job, the worker's process identifier & its start time, in that order.
// / Returns TRUE when the record was written. A worker of 0 is one not started yet.
function recordRunningWorkflow($runFolder, $jobId, $workerPid, $workerStart) {
  // / Set variables.
  global $DirSep, $EnableMemoryProtection;
  $RunWasRecorded = FALSE;
  $runRecord = array();
  $runRecord = array('JobId' => (string)$jobId, 'WorkerPid' => (int)$workerPid, 'WorkerStart' => (int)$workerStart, 'Created' => time());
  if ((string)$runFolder !== '' && is_dir($runFolder) && !is_link($runFolder) && preg_match('/^[a-f0-9]{24}$/', (string)$jobId) === 1) $RunWasRecorded = writeWorkflowFile($runFolder.$DirSep.$jobId.'.run', (string)json_encode($runRecord), FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $runRecord, $runFolder, $jobId, $workerPid, $workerStart);
  return $RunWasRecorded; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove a workflow's record once it has stopped running.
// / Accepts the run folder & the job. Returns TRUE when no record is left.
function forgetRunningWorkflow($runFolder, $jobId) {
  // / Set variables.
  global $DirSep, $EnableMemoryProtection;
  $RunWasForgotten = TRUE;
  $recordPath = '';
  if ((string)$runFolder !== '' && preg_match('/^[a-f0-9]{24}$/', (string)$jobId) === 1) {
    $recordPath = $runFolder.$DirSep.$jobId.'.run';
    if (file_exists($recordPath) && !@unlink($recordPath)) $RunWasForgotten = FALSE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $recordPath, $runFolder, $jobId);
  return $RunWasForgotten; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to start the worker that runs one workflow.
// / Accepts the job & the session the worker continues. Returns whether it started & what went wrong if it did not, in that order.
// / The worker is this application's entry point with --run-workflow & the job, run as the account this request runs as.
// / It is started through setsid -f, so it leads a session of its own & outlives the request that started it.
// / Nothing passes through a shell. The session & a startup key travel in the worker's environment, which only its own account can read.
// / A command line is readable by every account, & a session's name is how its files are fetched, so neither is ever put on one.
// / The startup key is bound to this job & this session, & the worker spends it, so the job starts once.
function startWorkflowWorker($jobId, $sessionContext) {
  // / Set variables.
  global $InstLoc, $DirSep, $Verbose, $EnableMemoryProtection;
  $WorkerWasStarted = FALSE;
  $StartNotice = '';
  $startupKey = $setsidPath = $phpPath = $entryPath = '';
  $workerEnvironment = $commandParts = $processPipes = $processDescriptors = array();
  $workerProcess = FALSE;
  $exitStatus = -1;
  $startupKey = deriveStartupKey('run-workflow|'.hash('sha256', (string)$sessionContext).'|'.(string)$jobId);
  $setsidPath = locateDependency('setsid');
  $phpPath = locateDependency('php');
  $entryPath = rtrim((string)$InstLoc, $DirSep).$DirSep.engineApplicationEntryPoint();
  if ($startupKey === '') $StartNotice = 'No install secret is available, so the worker could be given no startup key.';
  else if ($setsidPath === '' or $phpPath === '') $StartNotice = 'setsid or php could not be found, so the worker could not be started.';
  else if (!function_exists('proc_open')) $StartNotice = 'PHP has proc_open disabled, so the worker could not be started.';
  else {
    $workerEnvironment = getenv();
    if (!is_array($workerEnvironment)) $workerEnvironment = array();
    $workerEnvironment[engineStartupKeyVariable()] = $startupKey;
    $workerEnvironment[engineWorkerContextVariable()] = (string)$sessionContext;
    $commandParts = array($setsidPath, '-f', $phpPath, $entryPath, '--run-workflow', (string)$jobId);
    $processDescriptors = array(0 => array('file', '/dev/null', 'r'), 1 => array('file', '/dev/null', 'w'), 2 => array('file', '/dev/null', 'w'));
    $workerProcess = @proc_open($commandParts, $processDescriptors, $processPipes, (string)$InstLoc, $workerEnvironment);
    if (!is_resource($workerProcess)) $StartNotice = 'The worker process could not be created.';
    else {
      $exitStatus = proc_close($workerProcess);
      $WorkerWasStarted = ($exitStatus === 0);
      if (!$WorkerWasStarted) $StartNotice = 'setsid exited with status '.$exitStatus.', so the worker may not have started.';
      else if ($Verbose) logEntry('Started the worker for workflow job '.$jobId.'.'); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $startupKey, $setsidPath, $phpPath, $entryPath, $workerEnvironment, $commandParts, $processPipes, $processDescriptors, $workerProcess, $exitStatus, $jobId, $sessionContext);
  return array($WorkerWasStarted, $StartNotice); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to start a checked workflow on the files a user selected, in the background.
// / Accepts the settings, the workflow's identifier, the workflow as the runner reads it, the selected files, the operations & the kinds of file, in that order.
// / Returns the reply, holding Started, & JobId when it started or Reason when it did not.
// / A workflow from the library & one a page built are both started here, so each is held to the same limits. One a page built has no identifier.
// / The caller has already kept only files that are the session's own, because which files a user may act on is the application's policy.
// / One workflow runs in a session at a time, & no more than the application's limit run on the server at once.
// / A session's own workflow is asked about before the server's count, because the count includes it & busy is the truer answer.
// / A workflow that would find no file is refused before anything starts, because it would run & do nothing.
// / Only a step reading the selection, or a Select step reading the session, can find a file before the workflow runs, so only those are asked.
// / A Select step that would find more files than one step may take is refused here too, because it would fail when it ran.
// / The worker is a process of its own, so the request returns as soon as it is started.
// / Reason is busy, files, nothing, server or start.
function launchWorkflowRun($workflowSettings, $workflowId, $normalizedWorkflow, $selectedFiles, $operations, $fileTypes) {
  // / Set variables.
  global $DirSep, $EnableMemoryProtection;
  $RunReply = array('Started' => FALSE, 'Reason' => '');
  $cleanSelection = $normalizedStep = $stepInputs = $initialStatus = $jobRecord = $stepSummaries = array();
  $anyStepHasInputs = $tooManyFound = $readsSession = $inputsWereResolved = $runsAreCounted = $sessionWasClaimed = $jobWasWritten = $runWasRecorded = $workerWasStarted = FALSE;
  $selectedName = NULL;
  $jobId = $claimRefusal = $startNotice = $sessionFolder = $workflowLabel = '';
  $runningCount = $skippedCount = 0;
  $runLock = FALSE;
  $sessionFolder = $workflowSettings['SessionFolder'];
  $workflowLabel = ((string)$workflowId !== '') ? 'workflow '.(string)$workflowId : 'a workflow built in a page';
  if (workflowSessionIsBusy($sessionFolder)) $RunReply['Reason'] = 'busy';
  else {
    foreach ((array)$selectedFiles as $selectedName) if (is_string($selectedName) && $selectedName !== '' && basename($selectedName) === $selectedName && preg_match('//u', $selectedName) === 1 && !in_array($selectedName, $cleanSelection, TRUE)) $cleanSelection[] = $selectedName;
    if (count($cleanSelection) > (int)$workflowSettings['MaxFiles']) $RunReply['Reason'] = 'files';
    else {
      foreach ($normalizedWorkflow['Steps'] as $normalizedStep) {
        $readsSession = in_array('session', $normalizedStep['Input'], TRUE);
        if (!$readsSession && !in_array('selection', $normalizedStep['Input'], TRUE)) continue;
        list ($inputsWereResolved, $stepInputs, $skippedCount) = resolveStepInputs(array_merge($normalizedStep, array('Input' => array($readsSession ? 'session' : 'selection'))), $cleanSelection, array(), $sessionFolder, $operations, $fileTypes);
        if (count($stepInputs) > 0) $anyStepHasInputs = TRUE;
        if ($readsSession && count($stepInputs) > (int)$workflowSettings['MaxFiles']) $tooManyFound = TRUE; }
      if ($tooManyFound) $RunReply['Reason'] = 'files';
      else if (!$anyStepHasInputs) $RunReply['Reason'] = 'nothing';
      else {
        $runLock = lockWorkflowRuns($workflowSettings['RunFolder']);
        if ($runLock !== FALSE) list ($runsAreCounted, $runningCount) = countRunningWorkflows($workflowSettings['RunFolder']);
        if ($runsAreCounted && $runningCount >= (int)$workflowSettings['MaxConcurrent']) $RunReply['Reason'] = 'server';
        else {
          $jobId = bin2hex(random_bytes(12));
          foreach ($normalizedWorkflow['Steps'] as $normalizedStep) $stepSummaries[] = array('Operation' => $normalizedStep['Operation'], 'State' => 'waiting', 'Inputs' => 0, 'Skipped' => 0, 'Produced' => 0, 'Errors' => FALSE);
          // / The status holds the workflow in canonical form, written once here, so a page loaded again while it runs can draw what is running.
          // / The worker writes its progress over this status & keeps every key it does not change, so the document stays until the status is cleared.
          $initialStatus = array('JobId' => $jobId, 'WorkflowId' => (string)$workflowId, 'Name' => $normalizedWorkflow['Name'], 'State' => 'starting', 'Reason' => '', 'Created' => time(), 'Started' => 0, 'Ended' => 0, 'Heartbeat' => time(),
            'WorkerPid' => 0, 'WorkerStart' => 0, 'StepCount' => count($stepSummaries), 'StepNumber' => 0, 'Operation' => '', 'FilesDone' => 0, 'FilesTotal' => 0, 'Waiting' => FALSE, 'Steps' => $stepSummaries, 'Produced' => array(),
            'Document' => canonicalWorkflowText($normalizedWorkflow, $operations));
          list ($sessionWasClaimed, $claimRefusal) = claimWorkflowSession($sessionFolder, $initialStatus);
          if (!$sessionWasClaimed) $RunReply['Reason'] = $claimRefusal;
          else {
            $jobRecord = array('JobId' => $jobId, 'WorkflowId' => (string)$workflowId, 'Workflow' => $normalizedWorkflow, 'Selection' => $cleanSelection, 'Created' => time());
            $jobWasWritten = writeWorkflowFile(workflowFolderPath($sessionFolder).$DirSep.'job.json', (string)json_encode($jobRecord, JSON_UNESCAPED_SLASHES), FALSE);
            if ($runsAreCounted) $runWasRecorded = recordRunningWorkflow($workflowSettings['RunFolder'], $jobId, 0, 0); } }
        // / The lock is held only while the count & the record are made, so a worker that is slow to start holds nobody up.
        unlockWorkflowRuns($runLock);
        if ($sessionWasClaimed && $jobWasWritten) list ($workerWasStarted, $startNotice) = startWorkflowWorker($jobId, $workflowSettings['SessionContext']);
        if ($sessionWasClaimed && (!$jobWasWritten or !$workerWasStarted)) {
          errorEntry('A workflow could not be started. '.($jobWasWritten ? $startNotice : 'Its job could not be written.'), 38002, FALSE);
          releaseWorkflowSession($sessionFolder);
          if ($runWasRecorded) forgetRunningWorkflow($workflowSettings['RunFolder'], $jobId);
          $RunReply['Reason'] = 'start'; }
        else if ($workerWasStarted) {
          $RunReply = array('Started' => TRUE, 'JobId' => $jobId, 'Reason' => '');
          logEntry('Started '.$workflowLabel.' as job '.$jobId.' on '.count($cleanSelection).' selected file(s).'); } } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cleanSelection, $normalizedStep, $stepInputs, $initialStatus, $jobRecord, $stepSummaries, $anyStepHasInputs, $tooManyFound, $readsSession, $inputsWereResolved, $runsAreCounted, $sessionWasClaimed, $jobWasWritten, $runWasRecorded, $workerWasStarted, $selectedName, $jobId, $claimRefusal, $startNotice, $sessionFolder, $workflowLabel, $runningCount, $skippedCount, $runLock, $workflowSettings, $workflowId, $normalizedWorkflow, $selectedFiles, $operations, $fileTypes);
  return $RunReply; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to start a workflow from the library on the files a user selected, in the background.
// / Accepts the workflow's identifier & the selected files, in the order they were selected.
// / Returns the reply, holding Started, & JobId when it started or Reason when it did not.
// / Only a usable workflow from the library is started, so an identifier this session is not offered is unknown.
// / Reason is disabled, unknown, busy, files, nothing, server or start.
function startWorkflowRun($workflowId, $selectedFiles) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RunReply = array('Started' => FALSE, 'Reason' => '');
  $workflowSettings = $workflowLibrary = $operations = $fileTypes = array();
  $libraryWasRead = FALSE;
  $workflowSettings = workflowSettings();
  if (!$workflowSettings['Enabled']) $RunReply['Reason'] = 'disabled';
  else {
    list ($libraryWasRead, $workflowLibrary, $operations, $fileTypes) = readWorkflowLibrary($workflowSettings);
    if (!is_string($workflowId) or !isset($workflowLibrary[$workflowId]) or !$workflowLibrary[$workflowId]['Usable']) $RunReply['Reason'] = 'unknown';
    else $RunReply = launchWorkflowRun($workflowSettings, $workflowId, $workflowLibrary[$workflowId]['Workflow'], $selectedFiles, $operations, $fileTypes); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $workflowSettings, $workflowLibrary, $operations, $fileTypes, $libraryWasRead, $workflowId, $selectedFiles);
  return $RunReply; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to start a workflow a page built on the files a user selected, in the background.
// / Accepts the workflow, as the text the page sent, & the selected files, in the order they were selected.
// / Returns the reply, holding Started, & JobId when it started or Reason when it did not, & Problems when the workflow is not one this server can run.
// / The text is checked exactly as a library's file is, & is then started as one is, under every limit a library's workflow is held to.
// / The job holds the workflow as the runner reads it, so nothing a page sent is kept as it was sent.
// / Reason is disabled, builder, invalid, busy, files, nothing, server or start.
function startWorkflowDocumentRun($workflowText, $selectedFiles) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RunReply = array('Started' => FALSE, 'Reason' => '');
  $workflowSettings = $operations = $fileTypes = $normalizedWorkflow = $workflowProblems = array();
  $registryIsUsable = $workflowIsValid = $workflowIsUsable = FALSE;
  $workflowSettings = workflowSettings();
  if (!$workflowSettings['Enabled']) $RunReply['Reason'] = 'disabled';
  else if (!$workflowSettings['Builder']) $RunReply['Reason'] = 'builder';
  else {
    list ($registryIsUsable, $operations, $fileTypes) = readOperationRegistry();
    if ($registryIsUsable) list ($workflowIsValid, $normalizedWorkflow, $workflowProblems, $workflowIsUsable) = checkWorkflowText($workflowText, $operations, $fileTypes, $workflowSettings);
    else $workflowProblems[] = 'This server offers no operation, so no workflow can run.';
    if (!$workflowIsValid or !$workflowIsUsable) $RunReply = array('Started' => FALSE, 'Reason' => 'invalid', 'Problems' => $workflowProblems);
    else $RunReply = launchWorkflowRun($workflowSettings, '', $normalizedWorkflow, $selectedFiles, $operations, $fileTypes); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $workflowSettings, $operations, $fileTypes, $normalizedWorkflow, $workflowProblems, $registryIsUsable, $workflowIsValid, $workflowIsUsable, $workflowText, $selectedFiles);
  return $RunReply; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write a workflow in canonical form, the form an export saves & a page's builder opens.
// / Accepts the workflow as the runner reads it & the operations, in that order. Returns the document as JSON text.
// / Reading the text back gives the same workflow, so a workflow exported, imported & exported again is the same text.
// / Every reference is written as selection or step N, never previous, so a reader need not count steps to follow one.
// / Input is written as one reference, or as a list of them, & is left out of a Select step, which takes none.
// / A filter keeps only the tests it makes, & a size is written in bytes. A step that filters nothing has no Filter.
// / Mode is written, because the mode an operation runs in when none is named is the application's & could differ on another server.
// / Every option is written with its value for the same reason. Order is written only when it is name, which is not the default.
// / The document names no file of a user's & no path, so it serves anybody it is given to.
function canonicalWorkflowText($normalizedWorkflow, $operations) {
  // / Set variables.
  global $EnableMemoryProtection;
  $WorkflowText = '';
  $canonicalDocument = $canonicalStep = $canonicalFilter = $normalizedStep = array();
  $filterKey = '';
  $filterValue = NULL;
  $canonicalDocument = array('Format' => 'HRProprietary Workflow', 'Version' => 1, 'Name' => (string)$normalizedWorkflow['Name']);
  if ((string)$normalizedWorkflow['Description'] !== '') $canonicalDocument['Description'] = (string)$normalizedWorkflow['Description'];
  $canonicalDocument['Steps'] = array();
  foreach ($normalizedWorkflow['Steps'] as $normalizedStep) {
    redeclare($canonicalStep, array('Operation' => $normalizedStep['Operation']));
    redeclare($canonicalFilter, array());
    if (empty($operations[$normalizedStep['Operation']]['BuiltIn'])) $canonicalStep['Input'] = (count($normalizedStep['Input']) === 1) ? $normalizedStep['Input'][0] : array_values($normalizedStep['Input']);
    foreach ($normalizedStep['Filter'] as $filterKey => $filterValue) if ((is_string($filterValue) && $filterValue !== '') or (is_array($filterValue) && count($filterValue) > 0) or (is_int($filterValue) && $filterValue >= 0)) $canonicalFilter[$filterKey] = $filterValue;
    if (count($canonicalFilter) > 0) $canonicalStep['Filter'] = $canonicalFilter;
    if (empty($operations[$normalizedStep['Operation']]['BuiltIn'])) $canonicalStep['Mode'] = $normalizedStep['Mode'];
    if ($normalizedStep['Order'] === 'name') $canonicalStep['Order'] = 'name';
    if (count($normalizedStep['Options']) > 0) $canonicalStep['Options'] = $normalizedStep['Options'];
    $canonicalDocument['Steps'][] = $canonicalStep; }
  $WorkflowText = (string)json_encode($canonicalDocument, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE).PHP_EOL;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $canonicalDocument, $canonicalStep, $canonicalFilter, $normalizedStep, $filterKey, $filterValue, $normalizedWorkflow, $operations);
  return $WorkflowText; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the file a workflow a page built is exported to.
// / Accepts the workflow's name. Returns an identifier made from it, followed by -workflow.json, so --workflow --install accepts the file as it is.
// / Every run of characters an identifier cannot hold becomes one dash, & the identifier is cut to 64 characters.
// / A name with no letter or digit an identifier can hold is exported as Workflow-workflow.json.
function workflowFileName($workflowName) {
  // / Set variables.
  global $EnableMemoryProtection;
  $WorkflowFileName = '';
  $workflowIdentifier = '';
  $workflowIdentifier = trim((string)preg_replace('/[^A-Za-z0-9_]+/', '-', (string)$workflowName), '-_');
  $workflowIdentifier = rtrim(substr($workflowIdentifier, 0, 64), '-');
  if ($workflowIdentifier === '') $workflowIdentifier = 'Workflow';
  $WorkflowFileName = $workflowIdentifier.'-workflow.json';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $workflowIdentifier, $workflowName);
  return $WorkflowFileName; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to check a workflow a page holds, for its builder, an import or an export.
// / Accepts the workflow as text. Returns the reply, holding Available & Allowed, & Valid, Usable, Problems, Document & FileName when it was checked.
// / Allowed is true when the builder, or import & export, is turned on, because each is what a page checks a workflow for.
// / Usable is true when it is valid & every operation it names is turned on here. Problems names everything to fix, & is about the workflow alone.
// / Document is the workflow in canonical form, which is what an export saves & what a page's builder opens, & FileName is the name an export saves it under.
// / Nothing is run & nothing is kept.
function checkWorkflowDocument($workflowText) {
  // / Set variables.
  global $EnableMemoryProtection;
  $CheckReply = array('Available' => FALSE, 'Allowed' => FALSE);
  $workflowSettings = $operations = $fileTypes = $normalizedWorkflow = $workflowProblems = array();
  $registryIsUsable = $workflowIsValid = $workflowIsUsable = FALSE;
  $workflowSettings = workflowSettings();
  if ($workflowSettings['Enabled']) {
    $CheckReply = array('Available' => TRUE, 'Allowed' => ($workflowSettings['Builder'] or $workflowSettings['ImportExport']));
    if ($CheckReply['Allowed']) {
      list ($registryIsUsable, $operations, $fileTypes) = readOperationRegistry();
      if ($registryIsUsable) list ($workflowIsValid, $normalizedWorkflow, $workflowProblems, $workflowIsUsable) = checkWorkflowText($workflowText, $operations, $fileTypes, $workflowSettings);
      else $workflowProblems[] = 'This server offers no operation, so no workflow can be checked.';
      $CheckReply = array_merge($CheckReply, array('Valid' => $workflowIsValid, 'Usable' => $workflowIsUsable, 'Problems' => $workflowProblems,
        'Document' => $workflowIsValid ? canonicalWorkflowText($normalizedWorkflow, $operations) : '', 'FileName' => $workflowIsValid ? workflowFileName($normalizedWorkflow['Name']) : '')); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $workflowSettings, $operations, $fileTypes, $normalizedWorkflow, $workflowProblems, $registryIsUsable, $workflowIsValid, $workflowIsUsable, $workflowText);
  return $CheckReply; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to give a page a workflow from the library, as a document.
// / Accepts the workflow's identifier. Returns the reply, holding Available & Allowed, & Found, Document & FileName when it was looked for.
// / Allowed is true when the builder, or import & export, is turned on. A builder opens a workflow from its document, & an export saves it.
// / Only a workflow this session is offered is found, so nothing is given that the library does not already show.
// / Document is the workflow in canonical form, & FileName is its identifier followed by -workflow.json.
function exportWorkflowDocument($workflowId) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ExportReply = array('Available' => FALSE, 'Allowed' => FALSE);
  $workflowSettings = $workflowLibrary = $operations = $fileTypes = array();
  $libraryWasRead = $workflowWasFound = FALSE;
  $workflowSettings = workflowSettings();
  if ($workflowSettings['Enabled']) {
    $ExportReply = array('Available' => TRUE, 'Allowed' => ($workflowSettings['Builder'] or $workflowSettings['ImportExport']));
    if ($ExportReply['Allowed']) {
      list ($libraryWasRead, $workflowLibrary, $operations, $fileTypes) = readWorkflowLibrary($workflowSettings);
      $workflowWasFound = (is_string($workflowId) && isset($workflowLibrary[$workflowId]) && $workflowLibrary[$workflowId]['Usable']);
      $ExportReply = array_merge($ExportReply, array('Found' => $workflowWasFound, 'Document' => $workflowWasFound ? canonicalWorkflowText($workflowLibrary[$workflowId]['Workflow'], $operations) : '', 'FileName' => $workflowWasFound ? $workflowId.'-workflow.json' : '')); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $workflowSettings, $workflowLibrary, $operations, $fileTypes, $libraryWasRead, $workflowWasFound, $workflowId);
  return $ExportReply; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to describe what a page's builder may build a workflow from.
// / Accepts nothing. Returns the reply, holding Available, Builder, ImportExport, MaxSteps & MaxFiles, & Operations & Kinds, which are empty unless the builder is on.
// / Each operation is described by its name, whether it is this core's own, its modes & its options.
// / Each option is described by its name, its type, & its choices, default, bounds & whether it is required where it has them. A choice is described as a step names it.
// / Only an operation this server has turned on is described, because a step using any other could not run here.
// / Kinds names the kinds of file a filter may name. Nothing else about this server is described.
function describeWorkflowOperations() {
  // / Set variables.
  global $EnableMemoryProtection;
  $OperationsReply = array('Available' => FALSE, 'Builder' => FALSE, 'ImportExport' => FALSE);
  $workflowSettings = $operations = $fileTypes = $operationEntry = $optionSpec = $optionSummary = $optionSummaries = array();
  $registryIsUsable = FALSE;
  $operationName = $optionName = '';
  $workflowSettings = workflowSettings();
  if ($workflowSettings['Enabled']) {
    $OperationsReply = array('Available' => TRUE, 'Builder' => $workflowSettings['Builder'], 'ImportExport' => $workflowSettings['ImportExport'], 'MaxSteps' => (int)$workflowSettings['MaxSteps'], 'MaxFiles' => (int)$workflowSettings['MaxFiles'], 'Operations' => array(), 'Kinds' => array());
    if ($workflowSettings['Builder']) {
      list ($registryIsUsable, $operations, $fileTypes) = readOperationRegistry();
      foreach ($operations as $operationName => $operationEntry) {
        if (!$operationEntry['Enabled']) continue;
        redeclare($optionSummaries, array());
        foreach ($operationEntry['Options'] as $optionName => $optionSpec) {
          redeclare($optionSummary, array_merge(array('Name' => (string)$optionName), array_intersect_key($optionSpec, array_flip(array('Type', 'Default', 'Required', 'Minimum', 'Maximum')))));
          if (isset($optionSpec['Choices']) && is_array($optionSpec['Choices'])) $optionSummary['Choices'] = array_values(array_unique(array_map('strtolower', array_map('strval', $optionSpec['Choices']))));
          $optionSummaries[] = $optionSummary; }
        $OperationsReply['Operations'][] = array('Name' => (string)$operationName, 'BuiltIn' => !empty($operationEntry['BuiltIn']), 'Modes' => $operationEntry['Modes'], 'Options' => $optionSummaries); }
      $OperationsReply['Kinds'] = array_map('strval', array_keys($fileTypes)); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $workflowSettings, $operations, $fileTypes, $operationEntry, $optionSpec, $optionSummary, $optionSummaries, $registryIsUsable, $operationName, $optionName);
  return $OperationsReply; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to put a status in the form a page or a caller of the API is given.
// / Accepts the status, the state it is in & the reason for it, in that order. Returns the public status.
// / The worker's process & its start time are left out, because nothing outside the server needs them.
// / Document is the workflow in canonical form while it is starting or running, & is empty once it has ended, because only a page drawing what runs needs it.
function publicWorkflowStatus($workflowStatus, $derivedState, $derivedReason) {
  // / Set variables.
  global $EnableMemoryProtection;
  $PublicStatus = array();
  $startTime = $endTime = 0;
  $isUnderway = FALSE;
  $startTime = (int)($workflowStatus['Started'] ?? 0) > 0 ? (int)$workflowStatus['Started'] : (int)($workflowStatus['Created'] ?? time());
  $endTime = (int)($workflowStatus['Ended'] ?? 0) > 0 ? (int)$workflowStatus['Ended'] : time();
  $isUnderway = ($derivedState === 'starting' or $derivedState === 'running');
  $PublicStatus = array('Available' => TRUE, 'State' => (string)$derivedState, 'Reason' => (string)$derivedReason,
    'JobId' => (string)($workflowStatus['JobId'] ?? ''), 'WorkflowId' => (string)($workflowStatus['WorkflowId'] ?? ''), 'Name' => (string)($workflowStatus['Name'] ?? ''),
    'StepCount' => (int)($workflowStatus['StepCount'] ?? 0), 'StepNumber' => (int)($workflowStatus['StepNumber'] ?? 0), 'Operation' => (string)($workflowStatus['Operation'] ?? ''),
    'FilesDone' => (int)($workflowStatus['FilesDone'] ?? 0), 'FilesTotal' => (int)($workflowStatus['FilesTotal'] ?? 0), 'Waiting' => (($workflowStatus['Waiting'] ?? FALSE) === TRUE),
    'Steps' => (isset($workflowStatus['Steps']) && is_array($workflowStatus['Steps'])) ? array_values($workflowStatus['Steps']) : array(),
    'Produced' => (isset($workflowStatus['Produced']) && is_array($workflowStatus['Produced'])) ? array_values($workflowStatus['Produced']) : array(),
    'Elapsed' => max(0, $endTime - $startTime),
    'Document' => ($isUnderway && isset($workflowStatus['Document']) && is_string($workflowStatus['Document'])) ? $workflowStatus['Document'] : '');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $startTime, $endTime, $isUnderway, $workflowStatus, $derivedState, $derivedReason);
  return $PublicStatus; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report a session's workflow, for a page or a caller of the API.
// / Accepts nothing. Returns the reply, holding Available & State, & what the workflow has done so far.
// / State is none, starting, running, finished, stopped, failed or interrupted.
// / A workflow that has ended is reported once & then cleared, so a page reads its result once & the next workflow may start.
function reportWorkflowStatus() {
  // / Set variables.
  global $DirSep, $EnableMemoryProtection;
  $StatusReply = array('Available' => FALSE, 'State' => 'none');
  $workflowSettings = $workflowStatus = array();
  $statusPath = $derivedState = $derivedReason = '';
  $statusWasRead = FALSE;
  $workflowSettings = workflowSettings();
  if ($workflowSettings['Enabled']) {
    $StatusReply['Available'] = TRUE;
    $statusPath = workflowFolderPath($workflowSettings['SessionFolder']).$DirSep.'status.json';
    clearstatcache(TRUE, $statusPath);
    if (file_exists($statusPath)) {
      list ($statusWasRead, $workflowStatus) = readWorkflowRecord($statusPath);
      list ($derivedState, $derivedReason) = deriveWorkflowState($workflowStatus);
      $StatusReply = publicWorkflowStatus($workflowStatus, $derivedState, $derivedReason);
      if ($derivedState !== 'starting' && $derivedState !== 'running') releaseWorkflowSession($workflowSettings['SessionFolder']); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $workflowSettings, $workflowStatus, $statusPath, $derivedState, $derivedReason, $statusWasRead);
  return $StatusReply; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to stop a session's workflow, or clear one that has ended.
// / Accepts nothing. Returns the reply, holding Available & Stop, which is none, stopping or cleared.
// / A running workflow is asked to stop, & stops before its next operation. The operation it is in finishes first, because a converter stopped part way leaves a broken file.
// / A workflow that has ended, or whose worker is gone, is cleared.
function stopWorkflowRun() {
  // / Set variables.
  global $DirSep, $EnableMemoryProtection;
  $StopReply = array('Available' => FALSE, 'Stop' => 'none');
  $workflowSettings = $workflowStatus = array();
  $workflowFolder = $derivedState = $derivedReason = $jobId = '';
  $statusWasRead = FALSE;
  $workflowSettings = workflowSettings();
  if ($workflowSettings['Enabled']) {
    $StopReply['Available'] = TRUE;
    $workflowFolder = workflowFolderPath($workflowSettings['SessionFolder']);
    clearstatcache(TRUE, $workflowFolder.$DirSep.'status.json');
    if (file_exists($workflowFolder.$DirSep.'status.json')) {
      list ($statusWasRead, $workflowStatus) = readWorkflowRecord($workflowFolder.$DirSep.'status.json');
      list ($derivedState, $derivedReason) = deriveWorkflowState($workflowStatus);
      $jobId = preg_replace('/[^a-f0-9]/', '', (string)($workflowStatus['JobId'] ?? ''));
      if (($derivedState === 'starting' or $derivedState === 'running') && strlen($jobId) === 24) {
        if (writeWorkflowFile($workflowFolder.$DirSep.'stop-'.$jobId, (string)time(), FALSE)) $StopReply['Stop'] = 'stopping';
        else warningEntry('A workflow was asked to stop & the request could not be recorded.'); }
      else if (releaseWorkflowSession($workflowSettings['SessionFolder'])) $StopReply['Stop'] = 'cleared'; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $workflowSettings, $workflowStatus, $workflowFolder, $derivedState, $derivedReason, $jobId, $statusWasRead);
  return $StopReply; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write a workflow reply as the line a page or a caller of the API reads.
// / Accepts the reply. Returns one line of JSON holding the reply under the key Workflow.
// / Every character that means something in HTML is escaped, & so is an exclamation mark, so a name a user gave a file can never spell the tag an interface reads as an error.
// / Invalid UTF-8 is substituted rather than refused, because a reply that will not encode would tell the user nothing.
function encodeWorkflowReply($replyData) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ReplyLine = '';
  $encodedReply = '';
  $encodedReply = (string)json_encode(array('Workflow' => $replyData), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);
  $ReplyLine = str_replace('!', '\u0021', $encodedReply);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $encodedReply, $replyData);
  return $ReplyLine; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether a running workflow must stop.
// / Accepts the session folder & the job. Returns TRUE when a stop was asked for or the status is gone.
// / A status that is gone was cleared, which is a stop too.
function workflowStopIsRequested($sessionFolder, $jobId) {
  // / Set variables.
  global $DirSep, $EnableMemoryProtection;
  $StopIsRequested = FALSE;
  $workflowFolder = '';
  $workflowFolder = workflowFolderPath($sessionFolder);
  clearstatcache();
  $StopIsRequested = (file_exists($workflowFolder.$DirSep.'stop-'.(string)$jobId) or !file_exists($workflowFolder.$DirSep.'status.json'));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $workflowFolder, $sessionFolder, $jobId);
  return $StopIsRequested; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to take an operation's budget for a workflow, waiting while the server is busy.
// / Accepts the budget, the session folder, the status & the time the workflow must end by, in that order.
// / Returns whether the budget was taken & why it was not, in that order. The reason is stopped, runtime or budget.
// / A request refused a budget fails at once, & a workflow in the background waits instead, about four & a half minutes in all.
// / The status says it is waiting, & a stop is noticed within a second of being asked for.
function takeWorkflowBudget($budgetName, $sessionFolder, $workflowStatus, $runDeadline) {
  // / Set variables.
  global $EnableMemoryProtection;
  $BudgetWasTaken = FALSE;
  $BudgetRefusal = '';
  $waitSeconds = array(0, 5, 10, 20, 30, 30, 30, 30, 30, 30, 30, 30);
  $waitSecond = $secondsWaited = 0;
  $waitingStatus = array();
  foreach ($waitSeconds as $waitSecond) {
    if ($BudgetWasTaken or $BudgetRefusal !== '') break;
    if ($waitSecond > 0) {
      redeclare($waitingStatus, array_merge($workflowStatus, array('Waiting' => TRUE, 'Heartbeat' => time())));
      writeWorkflowStatus($sessionFolder, $waitingStatus, TRUE);
      for ($secondsWaited = 0; $secondsWaited < $waitSecond && $BudgetRefusal === ''; $secondsWaited++) {
        if (workflowStopIsRequested($sessionFolder, (string)$workflowStatus['JobId'])) $BudgetRefusal = 'stopped';
        else sleep(1); } }
    if ($BudgetRefusal === '' && time() > (int)$runDeadline) $BudgetRefusal = 'runtime';
    else if ($BudgetRefusal === '') $BudgetWasTaken = takeOperationBudget($budgetName); }
  if (!$BudgetWasTaken && $BudgetRefusal === '') $BudgetRefusal = 'budget';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $waitSeconds, $waitSecond, $secondsWaited, $waitingStatus, $budgetName, $sessionFolder, $workflowStatus, $runDeadline);
  return array($BudgetWasTaken, $BudgetRefusal); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run one operation on some files, for a workflow.
// / Accepts the operation, the files, the step's options & the session folder, in that order.
// / Returns whether it succeeded, whether anything went wrong, the files it produced & the reason it asks the workflow to end, in that order.
// / An operation is called as Invoke(files, options) & returns success, errors, the names it produced & what it found.
// / A name it produced is kept only when it is a plain file in the session folder, because a later step reads it from there.
// / An operation that started a process outliving it, as a stream does, is waited for here, because the next step reads what that process writes.
// / What it found may hold Halt, a short word naming why the workflow must end, such as threat.
function invokeWorkflowOperation($operationEntry, $inputFiles, $stepOptions, $sessionFolder) {
  // / Set variables.
  global $WaitForStream, $StreamPID, $StreamOutputPath, $DirSep, $EnableMemoryProtection;
  $OperationSucceeded = $OperationErrors = FALSE;
  $ProducedFiles = array();
  $HaltReason = '';
  $invokeFunction = $producedName = $producedPath = '';
  $operationOutput = $operationFindings = array();
  $operationFault = NULL;
  $streamCompleted = $streamKilled = FALSE;
  $elapsedSeconds = 0;
  $invokeFunction = (string)$operationEntry['Invoke'];
  try { list ($OperationSucceeded, $OperationErrors, $operationOutput, $operationFindings) = $invokeFunction($inputFiles, $stepOptions); }
  catch (Throwable $operationFault) {
    errorEntry('The '.$operationEntry['Name'].' operation failed with '.get_class($operationFault).'. '.$operationFault->getMessage(), 38003, FALSE);
    list ($OperationSucceeded, $OperationErrors, $operationOutput, $operationFindings) = array(FALSE, TRUE, array(), array()); }
  if (!empty($WaitForStream) && (int)$StreamPID > 0 && function_exists('waitForStream')) {
    logEntry('Waiting for detached worker '.(int)$StreamPID.' before the workflow goes on.');
    list ($streamCompleted, $streamKilled, $elapsedSeconds) = waitForStream($StreamPID, $StreamOutputPath);
    if ($streamKilled) warningEntry('Detached worker '.(int)$StreamPID.' was killed before it finished, so the workflow goes on without its output.'); }
  $WaitForStream = FALSE;
  $StreamPID = 0;
  $StreamOutputPath = '';
  foreach ((array)$operationOutput as $producedName) {
    redeclare($producedPath, rtrim((string)$sessionFolder, $DirSep).$DirSep.(string)$producedName);
    clearstatcache(TRUE, $producedPath);
    if (is_string($producedName) && $producedName !== '' && basename($producedName) === $producedName && is_file($producedPath) && !is_link($producedPath)) $ProducedFiles[] = $producedName;
    else warningEntry('The '.$operationEntry['Name'].' operation reported a file that is not in the session, so the workflow does not pass it on.'); }
  $ProducedFiles = array_values(array_unique($ProducedFiles));
  if (is_array($operationFindings) && isset($operationFindings['Halt']) && is_string($operationFindings['Halt']) && preg_match('/^[a-z][a-z-]{0,23}$/', $operationFindings['Halt']) === 1) $HaltReason = $operationFindings['Halt'];
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $invokeFunction, $producedName, $producedPath, $operationOutput, $operationFindings, $streamCompleted, $streamKilled, $elapsedSeconds, $operationFault, $operationEntry, $inputFiles, $stepOptions, $sessionFolder);
  return array((bool)$OperationSucceeded, (bool)$OperationErrors, $ProducedFiles, $HaltReason); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run one step of a workflow, as its worker.
// / Accepts the step's index, the step, the job, what each earlier step produced, the status, the operations, the kinds of file, the session folder & the time the workflow must end by, in that order.
// / Returns whether the step was attempted, its result & the status as it now stands, in that order.
// / The result holds Produced, & EndState & EndReason when the workflow must end here.
// / A step in each mode calls its operation once for every file, & in together mode once for all of them.
// / Before every call it reads whether it was asked to stop & whether it has run too long, & takes the operation's budget.
// / A step whose files all fail ends the workflow, because every later step would build on nothing.
// / A Select step calls nothing & takes no budget. The files it found are what the steps after it read, & are not counted as files made.
function runWorkflowStep($stepIndex, $normalizedStep, $jobRecord, $stepOutputs, $workflowStatus, $operations, $fileTypes, $sessionFolder, $runDeadline) {
  // / Set variables.
  global $EnableMemoryProtection;
  $StepWasAttempted = FALSE;
  $StepResult = array('Produced' => array(), 'EndState' => '', 'EndReason' => '');
  $StepStatus = $workflowStatus;
  $operationEntry = $inputFiles = $fileBatches = $batchFiles = $producedFiles = array();
  $inputsWereResolved = $operationSucceeded = $operationErrors = $budgetWasTaken = $anySucceeded = $anyErrors = FALSE;
  $skippedCount = $filesDone = 0;
  $haltReason = $budgetRefusal = $stepState = '';
  $operationEntry = isset($operations[$normalizedStep['Operation']]) ? $operations[$normalizedStep['Operation']] : array();
  $StepStatus = array_merge($StepStatus, array('StepNumber' => $stepIndex + 1, 'Operation' => $normalizedStep['Operation'], 'FilesDone' => 0, 'FilesTotal' => 0, 'Waiting' => FALSE, 'Heartbeat' => time()));
  $StepStatus['Steps'][$stepIndex]['State'] = 'running';
  writeWorkflowStatus($sessionFolder, $StepStatus, TRUE);
  if (empty($operationEntry) or !$operationEntry['Enabled']) list ($StepResult['EndState'], $StepResult['EndReason'], $stepState) = array('failed', 'unavailable', 'failed');
  else {
    $StepWasAttempted = TRUE;
    list ($inputsWereResolved, $inputFiles, $skippedCount) = resolveStepInputs($normalizedStep, $jobRecord['Selection'], $stepOutputs, $sessionFolder, $operations, $fileTypes);
    if (count($inputFiles) > (int)workflowSettings()['MaxFiles']) list ($StepResult['EndState'], $StepResult['EndReason'], $stepState) = array('failed', 'files', 'failed');
    else if (count($inputFiles) === 0) $stepState = 'skipped';
    else if (!empty($operationEntry['BuiltIn'])) list ($StepResult['Produced'], $stepState) = array($inputFiles, 'done');
    else {
      $fileBatches = ($normalizedStep['Mode'] === 'each') ? array_chunk($inputFiles, 1) : array($inputFiles);
      // / The step's files are counted before the first operation, so a page shows how many there are while it waits on that one.
      $StepStatus['FilesTotal'] = count($inputFiles);
      $StepStatus['Steps'][$stepIndex]['Inputs'] = count($inputFiles);
      $StepStatus['Steps'][$stepIndex]['Skipped'] = $skippedCount;
      writeWorkflowStatus($sessionFolder, $StepStatus, TRUE);
      foreach ($fileBatches as $batchFiles) {
        if ($StepResult['EndState'] !== '') break;
        if (workflowStopIsRequested($sessionFolder, (string)$jobRecord['JobId'])) list ($StepResult['EndState'], $StepResult['EndReason']) = array('stopped', 'stopped');
        else if (time() > (int)$runDeadline) list ($StepResult['EndState'], $StepResult['EndReason']) = array('failed', 'runtime');
        else {
          list ($budgetWasTaken, $budgetRefusal) = takeWorkflowBudget($operationEntry['Budget'], $sessionFolder, $StepStatus, $runDeadline);
          if (!$budgetWasTaken) list ($StepResult['EndState'], $StepResult['EndReason']) = ($budgetRefusal === 'stopped') ? array('stopped', 'stopped') : array('failed', $budgetRefusal);
          else {
            list ($operationSucceeded, $operationErrors, $producedFiles, $haltReason) = invokeWorkflowOperation($operationEntry, $batchFiles, $normalizedStep['Options'], $sessionFolder);
            giveBackOperationBudget($operationEntry['Budget']);
            if ($operationSucceeded) $anySucceeded = TRUE;
            if ($operationErrors or !$operationSucceeded) $anyErrors = TRUE;
            redeclare($StepResult['Produced'], array_values(array_unique(array_merge($StepResult['Produced'], $producedFiles))));
            $filesDone = $filesDone + count($batchFiles);
            $StepStatus = array_merge($StepStatus, array('FilesDone' => $filesDone, 'Waiting' => FALSE, 'Heartbeat' => time(), 'Produced' => array_values(array_unique(array_merge((array)$StepStatus['Produced'], $producedFiles)))));
            if ($haltReason !== '') list ($StepResult['EndState'], $StepResult['EndReason']) = array('failed', $haltReason);
            writeWorkflowStatus($sessionFolder, $StepStatus, TRUE); } } }
      if ($StepResult['EndState'] === '' && !$anySucceeded) list ($StepResult['EndState'], $StepResult['EndReason']) = array('failed', 'operation');
      $stepState = ($StepResult['EndState'] === '') ? 'done' : (($StepResult['EndState'] === 'stopped') ? 'stopped' : 'failed'); } }
  $StepStatus['Steps'][$stepIndex] = array('Operation' => $normalizedStep['Operation'], 'State' => $stepState, 'Inputs' => count($inputFiles), 'Skipped' => $skippedCount, 'Produced' => empty($operationEntry['BuiltIn']) ? count($StepResult['Produced']) : 0, 'Errors' => $anyErrors);
  $StepStatus['Waiting'] = FALSE;
  writeWorkflowStatus($sessionFolder, $StepStatus, TRUE);
  logEntry('Workflow job '.(string)$jobRecord['JobId'].' step '.($stepIndex + 1).', '.$normalizedStep['Operation'].', is '.$stepState.'. Files: '.count($inputFiles).', left out: '.$skippedCount.', produced: '.(empty($operationEntry['BuiltIn']) ? count($StepResult['Produced']) : 0).($anyErrors ? ', with errors.' : '.'));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $operationEntry, $inputFiles, $fileBatches, $batchFiles, $producedFiles, $inputsWereResolved, $operationSucceeded, $operationErrors, $budgetWasTaken, $anySucceeded, $anyErrors, $skippedCount, $filesDone, $haltReason, $budgetRefusal, $stepState, $stepIndex, $normalizedStep, $jobRecord, $stepOutputs, $workflowStatus, $operations, $fileTypes, $sessionFolder, $runDeadline);
  return array($StepWasAttempted, $StepResult, $StepStatus); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to leave a final status behind a worker that is ending before its workflow did.
// / Accepts nothing. Returns TRUE when there was nothing to do or the status was written.
// / It runs as the worker shuts down. A worker whose workflow ended has cleared $WorkflowWorkerJob, so this does nothing for it.
// / A worker an operation halted, as a fatal error halts one, still holds its job, so its status is written as failed, halted.
function finishInterruptedWorkflow() {
  // / Set variables.
  global $WorkflowWorkerJob, $DirSep, $EnableMemoryProtection;
  $StatusWasLeft = TRUE;
  $statusWasRead = FALSE;
  $workflowStatus = array();
  $sessionFolder = $jobId = $runFolder = '';
  if (is_array($WorkflowWorkerJob) && isset($WorkflowWorkerJob['SessionFolder'], $WorkflowWorkerJob['JobId'], $WorkflowWorkerJob['RunFolder'])) {
    $sessionFolder = (string)$WorkflowWorkerJob['SessionFolder'];
    $jobId = (string)$WorkflowWorkerJob['JobId'];
    $runFolder = (string)$WorkflowWorkerJob['RunFolder'];
    $WorkflowWorkerJob = array();
    list ($statusWasRead, $workflowStatus) = readWorkflowRecord(workflowFolderPath($sessionFolder).$DirSep.'status.json');
    if ($statusWasRead && ($workflowStatus['JobId'] ?? '') === $jobId && ($workflowStatus['State'] ?? '') === 'running') {
      $StatusWasLeft = writeWorkflowStatus($sessionFolder, array_merge($workflowStatus, array('State' => 'failed', 'Reason' => 'halted', 'Ended' => time(), 'Heartbeat' => time(), 'Waiting' => FALSE)), TRUE);
      errorEntry('Workflow job '.$jobId.' halted before it ended. The entry before this one says why.', 38004, FALSE); }
    @unlink(workflowFolderPath($sessionFolder).$DirSep.'job.json');
    forgetRunningWorkflow($runFolder, $jobId); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $statusWasRead, $workflowStatus, $sessionFolder, $jobId, $runFolder);
  return $StatusWasLeft; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run one workflow job, as the worker started for it.
// / Accepts the job. Returns whether the job ran & the exit status, 0 when the workflow finished & 1 otherwise, in that order.
// / The worker must run as the web server account & never as root, & must hold a startup key bound to this job & this session.
// / The job & the status are read from the session's workflow folder, & the status must name this job & still be starting.
// / Each step runs in turn. A step whose result says to end ends the workflow there, & a workflow whose steps all ran is finished.
// / The final status is left for the page to read, & the job & any stop marker are removed.
// / The worker reports to the web server's log under the role Workflow, & acts for the web, so a pipeline is held to what it permits from the web.
function runWorkflowWorker($jobId) {
  // / Set variables.
  global $RunningAsRoot, $CurrentUser, $ApacheUser, $TransportedWorkerContext, $EngineActiveSurface, $WorkflowWorkerJob, $DirSep, $EnableMemoryProtection;
  $WorkerRanJob = FALSE;
  $WorkerExitStatus = 1;
  $workflowSettings = $jobRecord = $workflowStatus = $operations = $fileTypes = $normalizedStep = $stepOutputs = $stepResult = array();
  $startupKey = $sessionFolder = $workflowFolder = $refusal = $finalState = $finalReason = $cleanJobId = '';
  $jobWasRead = $statusWasRead = $registryIsUsable = $stepWasAttempted = FALSE;
  $stepIndex = $runDeadline = 0;
  $cleanJobId = preg_replace('/[^a-f0-9]/', '', strtolower((string)$jobId));
  $startupKey = readTransportedStartupKey();
  $workflowSettings = workflowSettings();
  $sessionFolder = $workflowSettings['SessionFolder'];
  if ($RunningAsRoot or (string)$CurrentUser !== (string)$ApacheUser) $refusal = 'A workflow worker runs only as '.(string)$ApacheUser.', & never as root.';
  else if (strlen($cleanJobId) !== 24 or $cleanJobId !== (string)$jobId) $refusal = 'The job named is not a workflow job.';
  else if (!$workflowSettings['Enabled']) $refusal = 'Workflows are disabled.';
  else if ((string)$TransportedWorkerContext === '' or $sessionFolder === '' or !is_dir($sessionFolder)) $refusal = 'The worker was handed no session it can continue.';
  else if (!validateStartupKey('run-workflow|'.hash('sha256', (string)$TransportedWorkerContext).'|'.$cleanJobId, $startupKey, TRUE)) $refusal = 'The worker holds no startup key for this job.';
  if ($refusal === '') {
    $workflowFolder = workflowFolderPath($sessionFolder);
    list ($jobWasRead, $jobRecord) = readWorkflowRecord($workflowFolder.$DirSep.'job.json');
    list ($statusWasRead, $workflowStatus) = readWorkflowRecord($workflowFolder.$DirSep.'status.json');
    if (!$jobWasRead or !$statusWasRead or (string)($jobRecord['JobId'] ?? '') !== $cleanJobId or (string)($workflowStatus['JobId'] ?? '') !== $cleanJobId or (string)($workflowStatus['State'] ?? '') !== 'starting' or !isset($jobRecord['Workflow']['Steps'], $jobRecord['Selection']) or !is_array($jobRecord['Workflow']['Steps']) or !is_array($jobRecord['Selection'])) $refusal = 'The job '.$cleanJobId.' is not waiting to start in this session.'; }
  if ($refusal !== '') warningEntry('A workflow worker was refused. '.$refusal);
  else {
    $WorkerRanJob = TRUE;
    // / The worker acts for a web session, so a pipeline is held to the surface a web request is.
    $EngineActiveSurface = 'web';
    $WorkflowWorkerJob = array('SessionFolder' => $sessionFolder, 'JobId' => $cleanJobId, 'RunFolder' => $workflowSettings['RunFolder']);
    register_shutdown_function('finishInterruptedWorkflow');
    $workflowStatus = array_merge($workflowStatus, array('State' => 'running', 'WorkerPid' => getmypid(), 'WorkerStart' => function_exists('readProcessStartTime') ? readProcessStartTime(getmypid()) : 0, 'Started' => time(), 'Heartbeat' => time()));
    writeWorkflowStatus($sessionFolder, $workflowStatus, TRUE);
    recordRunningWorkflow($workflowSettings['RunFolder'], $cleanJobId, getmypid(), (int)$workflowStatus['WorkerStart']);
    logEntry('Running '.((string)($jobRecord['WorkflowId'] ?? '') !== '' ? 'workflow '.(string)$jobRecord['WorkflowId'] : 'a workflow built in a page').', job '.$cleanJobId.', on '.count($jobRecord['Selection']).' selected file(s).');
    list ($registryIsUsable, $operations, $fileTypes) = readOperationRegistry();
    $runDeadline = time() + (int)$workflowSettings['MaxRuntime'];
    if (!$registryIsUsable) list ($finalState, $finalReason) = array('failed', 'unavailable');
    else foreach ($jobRecord['Workflow']['Steps'] as $stepIndex => $normalizedStep) {
      if ($finalState !== '') break;
      list ($stepWasAttempted, $stepResult, $workflowStatus) = runWorkflowStep($stepIndex, $normalizedStep, $jobRecord, $stepOutputs, $workflowStatus, $operations, $fileTypes, $sessionFolder, $runDeadline);
      $stepOutputs[$stepIndex] = $stepResult['Produced'];
      if ($stepResult['EndState'] !== '') list ($finalState, $finalReason) = array($stepResult['EndState'], $stepResult['EndReason']); }
    if ($finalState === '') list ($finalState, $finalReason) = array('finished', '');
    // / The status is written over only where it is still there, because a status that is gone was cleared.
    writeWorkflowStatus($sessionFolder, array_merge($workflowStatus, array('State' => $finalState, 'Reason' => $finalReason, 'Ended' => time(), 'Heartbeat' => time(), 'Waiting' => FALSE)), TRUE);
    @unlink($workflowFolder.$DirSep.'job.json');
    @unlink($workflowFolder.$DirSep.'stop-'.$cleanJobId);
    forgetRunningWorkflow($workflowSettings['RunFolder'], $cleanJobId);
    // / An empty job tells finishInterruptedWorkflow() this workflow ended as it should.
    $WorkflowWorkerJob = array();
    logEntry('Workflow job '.$cleanJobId.' ended '.$finalState.($finalReason !== '' ? ', '.$finalReason : '').'.');
    $WorkerExitStatus = ($finalState === 'finished') ? 0 : 1; }
  // / The session is no longer needed once the key has been checked.
  $TransportedWorkerContext = '';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $workflowSettings, $jobRecord, $workflowStatus, $operations, $fileTypes, $normalizedStep, $stepOutputs, $stepResult, $startupKey, $sessionFolder, $workflowFolder, $refusal, $finalState, $finalReason, $cleanJobId, $jobWasRead, $statusWasRead, $registryIsUsable, $stepWasAttempted, $stepIndex, $runDeadline, $jobId);
  return array($WorkerRanJob, $WorkerExitStatus); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to print the library, for --workflow --list.
// / Accepts the settings & the library, in that order. Returns TRUE when every workflow in it is valid.
// / A workflow is usable, disabled when an operation it names is turned off, or invalid.
// / A disabled or an invalid one is printed with every problem found. A disabled one's problems name each step whose operation is turned off.
function printWorkflowLibrary($workflowSettings, $workflowLibrary) {
  // / Set variables.
  global $Lol, $EnableMemoryProtection;
  $LibraryIsValid = TRUE;
  $workflowRecord = array();
  $workflowId = $workflowProblem = $usabilityWord = '';
  print($Lol.'Workflows'.$Lol.$Lol);
  print('  '.str_pad('Enabled', 24).($workflowSettings['Enabled'] ? 'yes' : 'no, see --Enable Workflows-- in config.php').$Lol);
  print('  '.str_pad('Shipped library', 24).($workflowSettings['ShippedFolder'] === '' ? 'none' : $workflowSettings['ShippedFolder']).$Lol);
  print('  '.str_pad('Installed library', 24).($workflowSettings['InstalledFolder'] === '' ? 'none' : $workflowSettings['InstalledFolder'].(is_dir($workflowSettings['InstalledFolder']) ? '' : ', missing. Run -fp as root to create it.')).$Lol.$Lol);
  if (empty($workflowLibrary)) print('  No workflow is installed.'.$Lol);
  foreach ($workflowLibrary as $workflowId => $workflowRecord) {
    redeclare($usabilityWord, $workflowRecord['Usable'] ? 'usable' : ($workflowRecord['Valid'] ? 'disabled' : 'INVALID'));
    print('  '.str_pad((string)$workflowId, 28).str_pad($workflowRecord['Library'], 11).str_pad($usabilityWord, 10).($workflowRecord['Valid'] ? $workflowRecord['Workflow']['Name'] : $workflowRecord['File']).$Lol);
    if (!$workflowRecord['Valid']) $LibraryIsValid = FALSE;
    foreach ($workflowRecord['Problems'] as $workflowProblem) print('  '.str_repeat(' ', 49).$workflowProblem.$Lol); }
  print($Lol);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $workflowRecord, $workflowId, $workflowProblem, $usabilityWord, $workflowSettings, $workflowLibrary);
  return $LibraryIsValid; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to answer --workflow from the command line.
// / Accepts the arguments, the option given & whether the command was confirmed, in that order. Returns the exit status.
// / --list prints the library & is the default. --check=FILE checks a workflow file against this installation.
// / --export=ID prints a valid workflow from the library in canonical form, the form a page exports & imports, & prints nothing else, so it can be saved to a file. Any account may run these three.
// / --install=FILE checks a workflow & writes what it checked into the installed library, & --remove=ID removes one. Both need root.
// / A file is installed under its own name, which must be an identifier followed by -workflow.json.
// / What is written is the text that was checked, so a file changed after the check cannot be installed.
function runWorkflowAdminCommand($cliArguments, $cliTarget, $cliConfirmed) {
  // / Set variables.
  global $RunningAsRoot, $Lol, $DirSep, $EnableMemoryProtection;
  $CommandStatus = 0;
  $workflowSettings = $workflowLibrary = $operations = $fileTypes = $nameMatch = $normalizedWorkflow = $workflowProblems = array();
  $libraryWasRead = $sourceWasRead = $workflowIsValid = $folderIsHeld = $fileWasWritten = $fileWasRemoved = $registryIsUsable = FALSE;
  $optionName = $sourcePath = $sourceText = $sourceRefusal = $targetPath = $workflowProblem = $holdNotice = $writeNotice = $removalNotice = $exportNotice = $workflowId = '';
  $workflowSettings = workflowSettings();
  $optionName = (string)$cliTarget;
  if ($optionName === '' or $optionName === '--list') {
    list ($libraryWasRead, $workflowLibrary, $operations, $fileTypes) = readWorkflowLibrary($workflowSettings);
    if (!printWorkflowLibrary($workflowSettings, $workflowLibrary)) $CommandStatus = 1; }
  else if (strpos($optionName, '--check') === 0 or strpos($optionName, '--install') === 0) {
    $sourcePath = (strpos($optionName, '--check') === 0) ? extractCliOption($cliArguments, '--check') : extractCliOption($cliArguments, '--install');
    list ($registryIsUsable, $operations, $fileTypes) = readOperationRegistry();
    list ($sourceWasRead, $sourceText, $sourceRefusal) = readWorkflowSourceFile($sourcePath);
    if (strpos($optionName, '--install') === 0 && !$RunningAsRoot) list ($CommandStatus, $sourceRefusal) = array(1, 'Installing a workflow needs root.');
    else if (!$sourceWasRead) $CommandStatus = 1;
    else if (preg_match('/^([A-Za-z0-9][A-Za-z0-9_-]{0,63})-workflow\.json$/', basename($sourcePath), $nameMatch) !== 1) list ($CommandStatus, $sourceRefusal) = array(1, 'A workflow file is named for its identifier followed by -workflow.json, & an identifier is 1 to 64 letters, digits, dashes & underscores.');
    else if (!$registryIsUsable) list ($CommandStatus, $sourceRefusal) = array(1, 'This application offers no operation, so no workflow can be checked.');
    else {
      // / A valid workflow using an operation this server has turned off is installed, & each step using one is named, because it is offered once the operation is turned on.
      list ($workflowIsValid, $normalizedWorkflow, $workflowProblems) = checkWorkflowText($sourceText, $operations, $fileTypes, $workflowSettings);
      if (!$workflowIsValid) $CommandStatus = 1; }
    if ($sourceWasRead && $sourceRefusal === '') print($Lol.($CommandStatus === 0 ? 'VALID     ' : 'INVALID   ').(string)$sourcePath.$Lol);
    else print($Lol.'NOT READ  '.(string)$sourcePath.$Lol);
    if ($sourceRefusal !== '') print('  '.$sourceRefusal.$Lol);
    foreach ($workflowProblems as $workflowProblem) print('  '.$workflowProblem.$Lol);
    if ($CommandStatus === 0 && strpos($optionName, '--install') === 0) {
      $workflowId = (string)$nameMatch[1];
      $targetPath = $workflowSettings['InstalledFolder'].$DirSep.$workflowId.'-workflow.json';
      if ($workflowSettings['InstalledFolder'] === '' or !is_dir($workflowSettings['InstalledFolder'])) list ($CommandStatus, $holdNotice) = array(1, 'The installed library does not exist yet. Run -fp as root to create it.');
      else list ($folderIsHeld, $holdNotice) = folderIsHeldForRoot($workflowSettings['InstalledFolder']);
      if ($CommandStatus === 0 && !$folderIsHeld) $CommandStatus = 1;
      if ($CommandStatus === 0) list ($fileWasWritten, $writeNotice) = writeFileWithoutFollowing($targetPath, $sourceText, 0644);
      if ($CommandStatus === 0 && !$fileWasWritten) $CommandStatus = 1;
      if ($CommandStatus === 0) {
        logEntry('Installed the workflow '.$workflowId.' at '.$targetPath.'.');
        print('  Installed as '.$workflowId.' at '.$targetPath.'.'.$Lol); }
      else {
        errorEntry('The workflow '.$workflowId.' could not be installed. '.$holdNotice.' '.$writeNotice, 38005, FALSE);
        print('  Not installed. '.trim($holdNotice.' '.$writeNotice).$Lol); } }
    print($Lol); }
  else if (strpos($optionName, '--remove') === 0) {
    $workflowId = extractCliOption($cliArguments, '--remove');
    if (!$RunningAsRoot) list ($CommandStatus, $removalNotice) = array(1, 'Removing a workflow needs root.');
    else if (preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{0,63}$/', $workflowId) !== 1) list ($CommandStatus, $removalNotice) = array(1, 'Name the workflow by its identifier, as --workflow --list prints it.');
    else if ($workflowSettings['InstalledFolder'] === '' or !is_file($workflowSettings['InstalledFolder'].$DirSep.$workflowId.'-workflow.json')) list ($CommandStatus, $removalNotice) = array(1, 'No installed workflow is named '.$workflowId.'. A shipped workflow is part of the release & is not removed.');
    else {
      list ($fileWasRemoved, $removalNotice) = removeFileWithoutFollowing($workflowSettings['InstalledFolder'].$DirSep.$workflowId.'-workflow.json');
      if (!$fileWasRemoved) {
        $CommandStatus = 1;
        errorEntry('The workflow '.$workflowId.' could not be removed. '.$removalNotice, 38006, FALSE); }
      else {
        $removalNotice = 'Removed '.$workflowId.'.';
        logEntry('Removed the installed workflow '.$workflowId.'.'); } }
    print($Lol.$removalNotice.$Lol.$Lol); }
  else if (strpos($optionName, '--export') === 0) {
    $workflowId = extractCliOption($cliArguments, '--export');
    list ($libraryWasRead, $workflowLibrary, $operations, $fileTypes) = readWorkflowLibrary($workflowSettings);
    if (preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{0,63}$/', $workflowId) !== 1) list ($CommandStatus, $exportNotice) = array(1, 'Name the workflow by its identifier, as --workflow --list prints it.');
    else if (!isset($workflowLibrary[$workflowId]) or !$workflowLibrary[$workflowId]['Valid']) list ($CommandStatus, $exportNotice) = array(1, 'No valid workflow is named '.$workflowId.'. Run --workflow --list to read why.');
    if ($CommandStatus === 0) print(canonicalWorkflowText($workflowLibrary[$workflowId]['Workflow'], $operations));
    else print($Lol.$exportNotice.$Lol.$Lol); }
  else {
    $CommandStatus = 2;
    print($Lol.'--workflow takes --list, --check=FILE, --export=ID, --install=FILE or --remove=ID.'.$Lol.$Lol); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $workflowSettings, $workflowLibrary, $operations, $fileTypes, $nameMatch, $normalizedWorkflow, $workflowProblems, $libraryWasRead, $sourceWasRead, $workflowIsValid, $folderIsHeld, $fileWasWritten, $fileWasRemoved, $registryIsUsable, $optionName, $sourcePath, $sourceText, $sourceRefusal, $targetPath, $workflowProblem, $holdNotice, $writeNotice, $removalNotice, $exportNotice, $workflowId, $cliArguments, $cliTarget, $cliConfirmed);
  return $CommandStatus; }
// / -----------------------------------------------------------------------------------
?>
