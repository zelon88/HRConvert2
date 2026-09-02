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
// / This file is the Pipeline Manager component. It owns the detachable conversion
// / pipelines, their version pins, their capability declarations & their dispatch.
// / It is pinned EXACTLY by convertCore.php via $RequiredPipelineManagerVersion.
// / Error block 34000 through 34019 reserved. 34000 through 34006 are used.
// / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt for the contracts this file enforces.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
// / quickDie is not available here, because a component loaded directly has no core to
// / provide it. This is the one documented exception to quickDie being the only halt.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, The Pipeline Manager component cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The version of this component. Read by convertCore.php WITHOUT executing this file.
$PipelineManagerVersion = 'v3.8.6';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to report which pipeline folders this manager accepts & at what version.
// / Accepts no arguments. Returns an array keyed by folder name, valued by exact version.
// / This list is an allowlist & not a convenience.
// / A folder present under Resources/Pipelines that is not named here is never read, never
// / required & never enumerated. A directory scan would hand code execution to anybody able
// / to write a folder, so the pin list is what keeps an unexpected folder inert.
// / Accepting a community pipeline is one line here & one version bump on this file.
function getAcceptedPipelines() {
  // / Set variables.
  // / EVERY FAMILY IS LISTED HERE & convertCore.php CONVERTS NOTHING ON ITS OWN.
  // / A family removed from this list becomes unavailable rather than falling back.
  // / There is no built in dispatcher left behind it. Comment one out only to test that.
  // / Adding a community pipeline is one line here plus a version bump on this file.
  $AcceptedPipelines = array(
    'Stream' => 'v3.8.6',
    'Scad' => 'v3.8.6',
    'OCR' => 'v3.8.6',
    'Document' => 'v3.8.6',
    'Subtitle' => 'v3.8.6',
    'SVG' => 'v3.8.6',
    'Drawing' => 'v3.8.6',
    'Image' => 'v3.8.6',
    'Model' => 'v3.8.6',
    'Video' => 'v3.8.6',
    'Ebook' => 'v3.8.6',
    'Audio' => 'v3.8.6',
    'Archive' => 'v3.8.6');
  return $AcceptedPipelines; }
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to report which shared modules this manager accepts & at what version.
// / Accepts no arguments. Returns an array keyed by file name, valued by exact version.
// / A SHARED MODULE IS NOT A PIPELINE. It converts nothing & is never dispatched to.
// / It exists for code that two or more pipelines both need & that neither can own.
// / convertWithLibreOffice() is the case that created this. It has callers in both the
// / Document pipeline & the OCR pipeline. Leaving it in convertCore.php would have kept
// / document conversion logic in the file this work exists to get it out of, & putting it
// / in the Document pipeline would have made OCR depend on another pipeline being
// / installed, verified & loaded first.
// / This is an allowlist for the same reason getAcceptedPipelines() is one. A file that is
// / not named here is never read & never required.
function getAcceptedSharedModules() {
  // / Set variables.
  $AcceptedSharedModules = array(
    'libreOffice.php' => 'v3.8.6');
  return $AcceptedSharedModules; }
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to load one shared module after checking its version without executing it.
// / Accepts the module file name. Returns a readiness boolean.
// / The version is read with the same file_get_contents & regex approach used for a
// / pipeline, so a module built for another manager is judged before it is loaded.
// / require_once is correct here & require is not. A module defines functions & is
// / declared by more than one pipeline on purpose, so a second load would be a fatal
// / redeclare in a request a user is waiting on. Document & OCR in one request load
// / libreOffice.php once between them.
function loadSharedModule($sharedModuleName) {
  // / Set variables.
  global $InstLoc, $CoreLoaded, $Verbose, $EnableMemoryProtection, $DirSep;
  $ModuleIsReady = FALSE;
  $acceptedModules = $versionMatches = array();
  $modulePath = $moduleContents = $detectedVersion = $cleanDetected = $cleanRequired = '';
  $acceptedModules = getAcceptedSharedModules();
  if (!isset($acceptedModules[$sharedModuleName])) warningEntry('A pipeline asked for the shared module '.$sharedModuleName.', which this Pipeline Manager does not accept.');
  else {
    $modulePath = $InstLoc.$DirSep.'Resources'.$DirSep.'Pipelines'.$DirSep.'Shared'.$DirSep.$sharedModuleName;
    if (!file_exists($modulePath)) warningEntry('The shared module '.$sharedModuleName.' is not installed at '.$modulePath.'.');
    else {
      $moduleContents = @file_get_contents($modulePath);
      if (!is_string($moduleContents) or $moduleContents === '') warningEntry('The shared module '.$sharedModuleName.' could not be read.');
      else {
        if (preg_match('/\$SharedModuleVersion\s*=\s*\'([^\']+)\'/', $moduleContents, $versionMatches)) $detectedVersion = $versionMatches[1];
        $cleanDetected = ltrim(trim($detectedVersion), 'vV');
        $cleanRequired = ltrim(trim((string)$acceptedModules[$sharedModuleName]), 'vV');
        // / A module that reports no version is refused. An unknown build cannot be cleared.
        if ($cleanDetected === '') warningEntry('The shared module '.$sharedModuleName.' reports no version & was refused.');
        else if ($cleanDetected !== $cleanRequired) warningEntry('The shared module '.$sharedModuleName.' reports v'.$cleanDetected.' & this manager requires v'.$cleanRequired.'. It was refused.');
        else {
          require_once ($modulePath);
          $ModuleIsReady = TRUE;
          if ($Verbose) logEntry('Loaded the shared module '.$sharedModuleName.'.'); } } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $acceptedModules, $versionMatches, $modulePath, $moduleContents, $detectedVersion, $cleanDetected, $cleanRequired, $sharedModuleName);
  return $ModuleIsReady; }
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to verify one pipeline folder & read its declarations without running it.
// / Accepts the folder name & the version this manager requires, in that order.
// / Returns an availability boolean & the declaration record, in that order.
// / The version is read from pipelineConfig.php WITHOUT executing it, the same way
// / readComponentVersion() reads a core component. A pipeline built for another manager may
// / declare an entry point whose arguments have moved.
// / pipelineConfig.php is required with require & NOT require_once, because enumeration &
// / dispatch run in different scopes & each needs the declarations in its own scope.
// / Every declaration is nulled immediately before the require.
// / Enumeration loops, so each config lands in the same function scope as the last one. A
// / config that forgets a declaration would otherwise silently inherit the previous
// / pipeline's value & be credited with capabilities it does not have.
function verifyPipelineComponent($pipelineFolderName, $requiredPipelineVersion) {
  // / Set variables.
  global $InstLoc, $CoreLoaded, $EnableMemoryProtection, $DirSep;
  $PipelineIsAvailable = FALSE;
  $PipelineRecord = array();
  $pipelineFolder = $configPath = $corePath = $configContents = '';
  $detectedVersion = $cleanDetected = $cleanRequired = '';
  $versionMatches = array();
  $declarationsAreValid = FALSE;
  $pipelineFolder = $InstLoc.$DirSep.'Resources'.$DirSep.'Pipelines'.$DirSep.$pipelineFolderName;
  $configPath = $pipelineFolder.$DirSep.'pipelineConfig.php';
  $corePath = $pipelineFolder.$DirSep.'pipelineCore.php';
  if (!is_dir($pipelineFolder)) warningEntry('The '.$pipelineFolderName.' pipeline is not installed at '.$pipelineFolder.'.');
  else if (!file_exists($configPath)) warningEntry('The '.$pipelineFolderName.' pipeline has no pipelineConfig.php at '.$configPath.'.');
  else {
    $configContents = @file_get_contents($configPath);
    if (!is_string($configContents) or $configContents === '') warningEntry('The '.$pipelineFolderName.' pipeline configuration at '.$configPath.' could not be read.');
    else {
      if (preg_match('/\$PipelineVersion\s*=\s*\'([^\']+)\'/', $configContents, $versionMatches)) $detectedVersion = $versionMatches[1];
      $cleanDetected = ltrim(trim($detectedVersion), 'vV');
      $cleanRequired = ltrim(trim((string)$requiredPipelineVersion), 'vV');
      // / A pipeline that reports no version is refused. An unknown build cannot be cleared.
      if ($cleanDetected === '') warningEntry('The '.$pipelineFolderName.' pipeline reports no version & was refused.');
      else if ($cleanDetected !== $cleanRequired) warningEntry('The '.$pipelineFolderName.' pipeline reports v'.$cleanDetected.' & this manager requires v'.$cleanRequired.'. It was refused.');
      else {
        // / Clear every declaration so a config that omits one cannot inherit the last one.
        $PipelineVersion = $PipelineFamily = $PipelineDisplayName = $PipelineEntryPoint = $PipelineSubsystem = NULL;
        $PipelinePriority = $PipelineRequestFields = $Capabilities = $PipelineExclude = NULL;
        $PipelineKind = $PipelineSharedModules = NULL;
        require ($configPath);
        list ($declarationsAreValid, $PipelineRecord) = validatePipelineDeclarations($pipelineFolderName, $corePath, $PipelineFamily, $PipelineDisplayName, $PipelinePriority, $PipelineEntryPoint, $PipelineSubsystem, $PipelineRequestFields, $Capabilities, $PipelineExclude, $PipelineKind, $PipelineSharedModules);
        if ($declarationsAreValid) {
          $PipelineRecord['Version'] = $cleanDetected;
          $PipelineIsAvailable = TRUE; } } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $PipelineRecord is not purged, because it is a return value.
  purgeSensitiveMemory($EnableMemoryProtection, $pipelineFolder, $configPath, $corePath, $configContents, $detectedVersion, $cleanDetected, $cleanRequired, $versionMatches, $declarationsAreValid, $PipelineVersion, $PipelineFamily, $PipelineDisplayName, $PipelinePriority, $PipelineEntryPoint, $PipelineSubsystem, $PipelineRequestFields, $Capabilities, $PipelineExclude, $PipelineKind, $PipelineSharedModules, $pipelineFolderName, $requiredPipelineVersion);
  return array($PipelineIsAvailable, $PipelineRecord); }
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to check that a pipeline declared everything it is required to declare.
// / Accepts the folder name, the path to its core file & every declaration, in order.
// / Returns a validity boolean & the assembled declaration record, in that order.
// / A refusal names the folder & the declaration that was wrong, because a pipeline author
// / reading only that the pipeline was refused cannot correct anything.
function validatePipelineDeclarations($pipelineFolderName, $pipelineCorePath, $declaredFamily, $declaredDisplayName, $declaredPriority, $declaredEntryPoint, $declaredSubsystem, $declaredRequestFields, $declaredCapabilities, $declaredExclusions, $declaredKind, $declaredSharedModules) {
  // / Set variables.
  global $EnableMemoryProtection;
  $DeclarationsAreValid = FALSE;
  $PipelineRecord = array();
  $inputExtensions = $outputExtensions = $cleanExclusions = array();
  if (!is_string($declaredFamily) or trim((string)$declaredFamily) === '') warningEntry('The '.$pipelineFolderName.' pipeline declared no family & was refused.');
  else if (!is_string($declaredEntryPoint) or !preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', (string)$declaredEntryPoint)) warningEntry('The '.$pipelineFolderName.' pipeline declared an unusable entry point name & was refused.');
  else if (!is_array($declaredCapabilities) or !isset($declaredCapabilities['Input']) or !isset($declaredCapabilities['Output'])) warningEntry('The '.$pipelineFolderName.' pipeline declared no usable capabilities & was refused.');
  else if (!is_array($declaredCapabilities['Input']) or !is_array($declaredCapabilities['Output'])) warningEntry('The '.$pipelineFolderName.' pipeline declared capabilities that are not arrays & was refused.');
  else if (count($declaredCapabilities['Input']) === 0 or count($declaredCapabilities['Output']) === 0) warningEntry('The '.$pipelineFolderName.' pipeline declared an empty capability list & was refused.');
  else {
    // / Extensions are compared lowercased everywhere else, so they are lowercased here once.
    $inputExtensions = array_map('strtolower', $declaredCapabilities['Input']);
    $outputExtensions = array_map('strtolower', $declaredCapabilities['Output']);
    if (is_array($declaredExclusions)) $cleanExclusions = array_map('strtolower', $declaredExclusions);
    $PipelineRecord['Folder'] = $pipelineFolderName;
    $PipelineRecord['CorePath'] = $pipelineCorePath;
    $PipelineRecord['Family'] = trim((string)$declaredFamily);
    $PipelineRecord['DisplayName'] = (is_string($declaredDisplayName) && trim((string)$declaredDisplayName) !== '') ? trim((string)$declaredDisplayName) : trim((string)$declaredFamily);
    $PipelineRecord['Priority'] = is_numeric($declaredPriority) ? (int)$declaredPriority : 500;
    $PipelineRecord['EntryPoint'] = (string)$declaredEntryPoint;
    $PipelineRecord['Subsystem'] = is_string($declaredSubsystem) ? (string)$declaredSubsystem : '';
    $PipelineRecord['RequestFields'] = is_array($declaredRequestFields) ? $declaredRequestFields : array();
    $PipelineRecord['Input'] = $inputExtensions;
    $PipelineRecord['Output'] = $outputExtensions;
    $PipelineRecord['Exclude'] = $cleanExclusions;
    // / A conversion pipeline & an operation pipeline are verified identically & dispatched
    // / DIFFERENTLY. A conversion pipeline takes one file & returns the six value contract,
    // / & convert() dispatches to it. An operation pipeline takes a selection of files,
    // / decides its own route, returns its own shape, & is dispatched from wherever the
    // / core handles that operation. OCR is the first of these. Forcing it into the
    // / conversion contract would have meant describing a batch as a single file.
    // / An undeclared kind is a conversion pipeline, so every pipeline written before this
    // / declaration existed keeps working without being edited.
    $PipelineRecord['Kind'] = (is_string($declaredKind) && trim((string)$declaredKind) === 'operation') ? 'operation' : 'conversion';
    $PipelineRecord['SharedModules'] = is_array($declaredSharedModules) ? $declaredSharedModules : array();
    // / A pipeline with no core file is a Phase 1 pipeline whose entry point is still in the
    // / core. That is a supported arrangement & is not a fault.
    $PipelineRecord['HasCoreFile'] = file_exists($pipelineCorePath);
    $DeclarationsAreValid = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $PipelineRecord is not purged, because it is a return value.
  purgeSensitiveMemory($EnableMemoryProtection, $inputExtensions, $outputExtensions, $cleanExclusions, $pipelineFolderName, $pipelineCorePath, $declaredFamily, $declaredDisplayName, $declaredPriority, $declaredEntryPoint, $declaredSubsystem, $declaredRequestFields, $declaredCapabilities, $declaredExclusions, $declaredKind, $declaredSharedModules);
  return array($DeclarationsAreValid, $PipelineRecord); }
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to verify & read every pipeline this manager accepts.
// / Accepts no arguments. Returns a completion boolean, the pipeline records & the count.
// / NO PIPELINE CODE IS EXECUTED HERE. Only declarations are read.
// / This runs once per request & the result is carried forward. Rebuilding it for the
// / interface & again for dispatch would double the file reads for nothing.
// / An entry point name claimed twice is refused on the second claim, because loading both
// / would fatally redeclare the function during a request a user is waiting on.
function enumeratePipelines() {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $EnumerationComplete = FALSE;
  $Pipelines = array();
  $PipelineCount = 0;
  $acceptedPipelines = $claimedEntryPoints = array();
  $pipelineIsAvailable = FALSE;
  $pipelineRecord = array();
  $acceptedPipelines = getAcceptedPipelines();
  foreach ($acceptedPipelines as $folderName => $requiredVersion) {
    list ($pipelineIsAvailable, $pipelineRecord) = verifyPipelineComponent($folderName, $requiredVersion);
    if (!$pipelineIsAvailable) continue;
    // / Two pipelines cannot share an entry point. The second one to claim it is refused.
    if (isset($claimedEntryPoints[$pipelineRecord['EntryPoint']])) {
      errorEntry('The '.$folderName.' pipeline declared the entry point '.$pipelineRecord['EntryPoint'].', which the '.$claimedEntryPoints[$pipelineRecord['EntryPoint']].' pipeline already claimed. It was refused.', 34004, FALSE);
      continue; }
    $claimedEntryPoints[$pipelineRecord['EntryPoint']] = $folderName;
    $Pipelines[$folderName] = $pipelineRecord;
    $PipelineCount++; }
  if ($PipelineCount > 0) $EnumerationComplete = TRUE;
  if (!$EnumerationComplete) errorEntry('The Pipeline Manager could not verify a single conversion pipeline!', 34001, FALSE);
  else if ($Verbose) logEntry('Verified '.$PipelineCount.' conversion pipeline(s).');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $Pipelines is not purged, because it is a return value.
  purgeSensitiveMemory($EnableMemoryProtection, $acceptedPipelines, $claimedEntryPoints, $pipelineIsAvailable, $pipelineRecord);
  return array($EnumerationComplete, $Pipelines, $PipelineCount); }
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to report whether one pipeline claims one conversion.
// / Accepts a pipeline record, the input extension & the output extension, in that order.
// / Returns a claim boolean.
// / Capability is the cross product of the declared input & output lists, narrowed by the
// / declared exclusion pairs. Full pair enumeration would make a forty format pipeline
// / unreadable & the exclusion list keeps the nonsense pairs honest at a fraction of the cost.
function pipelineClaimsConversion($pipelineRecord, $inputExtension, $outputExtension) {
  // / Set variables.
  global $SupportedFormatDetectionType, $EnableMemoryProtection;
  $PipelineClaimsIt = FALSE;
  $cleanInput = $cleanOutput = $excludedPair = $detectionType = '';
  $cleanInput = strtolower(trim((string)$inputExtension));
  $cleanOutput = strtolower(trim((string)$outputExtension));
  $excludedPair = $cleanInput.'>'.$cleanOutput;
  // / Initialize a local fallback that configuration cannot overwrite. An administrator who
  // / has not got this setting at all, or who typed something this manager does not know,
  // / gets the behaviour the application has always had rather than a refused conversion.
  $detectionType = 'hardcoded-only';
  if (isset($SupportedFormatDetectionType) && in_array((string)$SupportedFormatDetectionType, array('hardcoded-only', 'detected-restrictive', 'detected-additive'), TRUE)) $detectionType = (string)$SupportedFormatDetectionType;
  // / UNDER hardcoded-only, config.php IS AUTHORITATIVE & A PIPELINE MAY NOT NARROW IT.
  // / convertFiles() has already tested this pair against the administrator's format arrays
  // / before anything reached the manager, so the pair is approved by the time it arrives.
  // / Testing it a second time against a declaration written by a pipeline author would let
  // / an incomplete $Capabilities list quietly delete a conversion the installation has
  // / always performed, which is precisely what happened to txt to rtf.
  // / So the declared lists are informational here & only the exclusions are enforced. An
  // / exclusion is the pipeline stating outright that it CANNOT do something, & honouring
  // / that is safe in any mode because it only ever removes a pair that would have failed.
  // / The declared lists become authoritative in the detected modes, which is what they are
  // / for & is why they are still required, validated & carried in the record.
  if (!in_array($excludedPair, $pipelineRecord['Exclude'], TRUE)) {
    if ($detectionType === 'hardcoded-only') $PipelineClaimsIt = TRUE;
    else if (in_array($cleanInput, $pipelineRecord['Input'], TRUE) && in_array($cleanOutput, $pipelineRecord['Output'], TRUE)) $PipelineClaimsIt = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cleanInput, $cleanOutput, $excludedPair, $detectionType, $pipelineRecord, $inputExtension, $outputExtension);
  return $PipelineClaimsIt; }
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to report every family that can do something with one input extension.
// / Accepts the input extension. Returns a completion boolean & the description records.
// / This is for the interface & it never resolves to one answer.
// / An rtf file is claimed by Document & by Ebook & BOTH are offered, each with its own
// / options panel & its own format list. A file supported by ImageMagick & by 7z shows an
// / image menu & an archive menu. Offering every available conversion is the design.
// / Two implementations of one family are merged into one entry whose output list is the
// / union of both, because a user choosing a format should never have to choose a converter.
function describePipelineCapabilities($inputExtension) {
  // / Set variables.
  global $Pipelines, $EnableMemoryProtection;
  $DescriptionComplete = FALSE;
  $CapabilityDescriptions = array();
  $cleanInput = $familyKey = '';
  $mergedOutputs = array();
  $cleanInput = strtolower(trim((string)$inputExtension));
  if (is_array($Pipelines)) {
    foreach ($Pipelines as $pipelineRecord) {
      if (!in_array($cleanInput, $pipelineRecord['Input'], TRUE)) continue;
      $familyKey = $pipelineRecord['Family'];
      if (!isset($CapabilityDescriptions[$familyKey])) {
        $CapabilityDescriptions[$familyKey] = array();
        $CapabilityDescriptions[$familyKey]['Family'] = $pipelineRecord['Family'];
        $CapabilityDescriptions[$familyKey]['DisplayName'] = $pipelineRecord['DisplayName'];
        $CapabilityDescriptions[$familyKey]['RequestFields'] = $pipelineRecord['RequestFields'];
        $CapabilityDescriptions[$familyKey]['Output'] = array(); }
      // / The union of both implementations, with anything either one excludes for this
      // / input removed, so a menu never offers a pair no installed implementation claims.
      $mergedOutputs = array();
      foreach ($pipelineRecord['Output'] as $candidateOutput) if (!in_array($cleanInput.'>'.$candidateOutput, $pipelineRecord['Exclude'], TRUE)) array_push($mergedOutputs, $candidateOutput);
      redeclare($CapabilityDescriptions[$familyKey]['Output'], array_values(array_unique(array_merge($CapabilityDescriptions[$familyKey]['Output'], $mergedOutputs)))); }
    $DescriptionComplete = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $CapabilityDescriptions is not purged, because it is a return value.
  purgeSensitiveMemory($EnableMemoryProtection, $cleanInput, $familyKey, $mergedOutputs, $inputExtension);
  return array($DescriptionComplete, $CapabilityDescriptions); }
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to build the union of every format every verified pipeline can handle.
// / Accepts no arguments. Returns a completion boolean, the input union & the output union.
// / verifyGlobals() builds $Allowed from $allArrays & verifyFile() rejects any extension
// / missing from it, so a pipeline declared format that never reaches $allArrays cannot be
// / uploaded & can therefore never be converted. This is what feeds that.
function buildPipelineFormatArrays() {
  // / Set variables.
  global $Pipelines, $EnableMemoryProtection;
  $FormatArraysAreBuilt = FALSE;
  $PipelineInputFormats = $PipelineOutputFormats = array();
  if (is_array($Pipelines)) {
    foreach ($Pipelines as $pipelineRecord) {
      redeclare($PipelineInputFormats, array_merge($PipelineInputFormats, $pipelineRecord['Input']));
      redeclare($PipelineOutputFormats, array_merge($PipelineOutputFormats, $pipelineRecord['Output'])); }
    redeclare($PipelineInputFormats, array_values(array_unique($PipelineInputFormats)));
    redeclare($PipelineOutputFormats, array_values(array_unique($PipelineOutputFormats)));
    $FormatArraysAreBuilt = TRUE; }
  return array($FormatArraysAreBuilt, $PipelineInputFormats, $PipelineOutputFormats); }
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to rank every implementation that can serve one conversion.
// / Accepts the family, the input extension & the output extension, in that order.
// / Returns a resolution boolean & the ordered candidate folder names, in that order.
// / THIS IS FOR DISPATCH & IT DOES RESOLVE, unlike describePipelineCapabilities().
// / By the time a conversion arrives the user has already chosen an output format from one
// / specific menu, so the family, input & output together usually name one implementation.
// / More than one candidate means an alternative implementation of the same family is
// / installed, which is what makes fallback possible.
// / Ordering is by declared priority & then by folder name, so the result never depends on
// / the order a filesystem happened to return.
function resolvePipelineCandidates($conversionFamily, $inputExtension, $outputExtension) {
  // / Set variables.
  global $Pipelines, $EnableMemoryProtection;
  $CandidatesAreResolved = FALSE;
  $CandidateFolders = array();
  $candidateRanking = array();
  $sortKey = '';
  if (is_array($Pipelines)) {
    foreach ($Pipelines as $folderName => $pipelineRecord) {
      if ($pipelineRecord['Family'] !== $conversionFamily) continue;
      // / An operation pipeline is never a conversion candidate. OCR shares this manager
      // / with the conversion pipelines & has an entirely different signature, so reaching
      // / it from convert() would call it with the wrong arguments & a wrong return shape.
      if ($pipelineRecord['Kind'] !== 'conversion') continue;
      if (!pipelineClaimsConversion($pipelineRecord, $inputExtension, $outputExtension)) continue;
      // / The key sorts numerically by priority & alphabetically within a priority.
      // / Padding keeps 40 below 400 without a second comparison pass.
      $sortKey = str_pad((string)$pipelineRecord['Priority'], 6, '0', STR_PAD_LEFT).'-'.$folderName;
      $candidateRanking[$sortKey] = $folderName; }
    ksort($candidateRanking);
    $CandidateFolders = array_values($candidateRanking);
    if (count($CandidateFolders) > 0) $CandidatesAreResolved = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $CandidateFolders is not purged, because it is a return value.
  purgeSensitiveMemory($EnableMemoryProtection, $candidateRanking, $sortKey, $conversionFamily, $inputExtension, $outputExtension);
  return array($CandidatesAreResolved, $CandidateFolders); }
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to report whether any installed pipeline serves one specific conversion.
// / Accepts the family, the input extension & the output extension, in that order.
// / Returns a boolean & nothing else. It reads declarations & loads no pipeline code.
// /
// / The core asks this before it dispatches & that ordering is the whole point.
// / A manager that verified one pipeline is active, & being active says nothing about
// / whether it can serve the conversion in front of it. A core that asked only whether the
// / manager had loaded would hand it every conversion & lose the ones no pipeline claims,
// / on an installation whose built in converter for that family works perfectly.
// /
// / This is deliberately cheap. It re-runs the same resolution dispatch will run rather
// / than caching a verdict, because the answer is a scan of arrays already in memory & a
// / cached verdict is one more thing that can disagree with the code that acts on it.
function familyHasPipeline($conversionFamily, $inputExtension, $outputExtension) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FamilyIsServed = FALSE;
  $candidateFolders = array();
  list ($FamilyIsServed, $candidateFolders) = resolvePipelineCandidates($conversionFamily, $inputExtension, $outputExtension);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $candidateFolders, $conversionFamily, $inputExtension, $outputExtension);
  return $FamilyIsServed; }
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to load one pipeline's converter & confirm its entry point exists.
// / Accepts the pipeline folder name. Returns a readiness boolean & the entry point name.
// / pipelineCore.php is required with require_once & NEVER require, because it defines
// / functions & a second load is a fatal redeclare in a request a user is waiting on.
// / A pipeline with no core file is a pipeline whose entry point still lives in the core.
// / That is supported & is how a pipeline is migrated out of convertCore.php one step at a
// / time, so the entry point is tested rather than the file.
function loadPipelineCore($pipelineFolderName) {
  // / Set variables.
  global $Pipelines, $CoreLoaded, $Verbose, $EnableMemoryProtection;
  $PipelineIsReady = FALSE;
  $PipelineEntryPointName = '';
  $pipelineRecord = array();
  if (!isset($Pipelines[$pipelineFolderName])) warningEntry('Dispatch asked for the '.$pipelineFolderName.' pipeline, which was never verified.');
  else {
    $pipelineRecord = $Pipelines[$pipelineFolderName];
    $PipelineEntryPointName = $pipelineRecord['EntryPoint'];
    // / Shared modules load FIRST. A converter that calls into one would otherwise be
    // / parsed & called before the functions it depends on exist. A module that refuses to
    // / load only warns here, because the entry point check below is what actually decides
    // / whether this pipeline can run & a converter may declare a module it barely uses.
    foreach ($pipelineRecord['SharedModules'] as $sharedModuleName) loadSharedModule($sharedModuleName);
    if ($pipelineRecord['HasCoreFile']) require_once ($pipelineRecord['CorePath']);
    // / The entry point is the only thing that decides whether this pipeline can run,
    // / whether it arrived from its own folder or was already present in the core.
    if (!function_exists($PipelineEntryPointName)) errorEntry('The '.$pipelineFolderName.' pipeline declared the entry point '.$PipelineEntryPointName.', which does not exist!', 34002, FALSE);
    else {
      $PipelineIsReady = TRUE;
      if ($Verbose) logEntry('Loaded the '.$pipelineFolderName.' pipeline, entry point '.$PipelineEntryPointName.'.'); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $PipelineEntryPointName is not purged, because it is a return value.
  purgeSensitiveMemory($EnableMemoryProtection, $pipelineRecord, $pipelineFolderName);
  return array($PipelineIsReady, $PipelineEntryPointName); }
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to dispatch an OCR operation to the installed OCR pipeline.
// / Accepts the selected files, the requested filename, the requested extension & the
// / method, in that order. Returns an operation boolean, an errors boolean & the list of
// / filenames the operation produced, in that order.
// / THE FILENAME LIST IS RETURNED RATHER THAN PRINTED. A pipeline never prints & neither
// / does this manager. The core prints the list once it has it. See contract 6.
// / THIS IS NOT runConversion() & THE DIFFERENCE IS NOT COSMETIC.
// / OCR takes a SELECTION of files rather than one, chooses a different route per file
// / depending on whether it is a PDF, a document or an image, & reports one verdict for the
// / whole batch. The six value conversion contract has nowhere to put any of that.
// / Fallback across implementations is deliberately NOT attempted here. A half completed
// / OCR batch has already written output files & already consumed budget, so re-running it
// / against a second implementation would duplicate work the user can see. The first
// / installed OCR pipeline is the one that runs.
function runOcrOperation($selectedFiles, $userFilename, $userExtension, $ocrMethod) {
  // / Set variables.
  global $Pipelines, $Verbose, $EnableMemoryProtection;
  $OperationSuccessful = $OperationErrors = FALSE;
  $OutputFilenames = array();
  $pipelineIsReady = FALSE;
  $entryPointName = $chosenFolder = '';
  if (is_array($Pipelines)) {
    foreach ($Pipelines as $folderName => $pipelineRecord) {
      if ($pipelineRecord['Family'] !== 'OCR') continue;
      if ($pipelineRecord['Kind'] !== 'operation') continue;
      $chosenFolder = $folderName;
      break; } }
  if ($chosenFolder === '') {
    $OperationErrors = TRUE;
    errorEntry('OCR was requested but no OCR pipeline is installed!', 34006, FALSE); }
  else {
    list ($pipelineIsReady, $entryPointName) = loadPipelineCore($chosenFolder);
    if (!$pipelineIsReady) $OperationErrors = TRUE;
    else {
      if ($Verbose) logEntry('Dispatching an OCR operation to the '.$chosenFolder.' pipeline.');
      list ($OperationSuccessful, $OperationErrors, $OutputFilenames) = $entryPointName($selectedFiles, $userFilename, $userExtension, $ocrMethod); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $selectedFiles is not purged, because the caller still holds the selection.
  // / $OutputFilenames is not purged, because it is a return value.
  purgeSensitiveMemory($EnableMemoryProtection, $pipelineIsReady, $entryPointName, $chosenFolder, $userFilename, $userExtension, $ocrMethod);
  return array($OperationSuccessful, $OperationErrors, $OutputFilenames); }
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to dispatch one conversion to the best implementation that will take it.
// / Accepts the family, the input path, the output path, the target extension & the four
// / optional request fields, in that order.
// / Returns the six value pipeline contract. Success, errors, path, extension, filename & PID.
// /
// / Every argument is passed to every pipeline & PHP discards what the pipeline did not
// / DECLARE. An entry point taking three parameters is called with seven & ignores four.
// / This is what lets a pipeline author add a converter without touching the core or
// / config.php. $PipelineRequestFields documents what a pipeline reads & is the basis of
// / its own sanity check. It is not a dispatch mechanism.
// /
// / A subsystem that can fall back must fall back before it errors.
// / Candidates are tried in priority order. A failure warns & moves to the next one. Error
// / 34003 fires only once the LAST candidate has also failed.
// /
// / The output path is unlinked between attempts.
// / Every converter in this application decides success by testing file_exists on the
// / output path. A failed attempt can leave a partial file behind, so a second attempt that
// / did not clear the path first would inherit it & report a success that never happened.
function runConversion($conversionFamily, $pathname, $newPathname, $extension, $height, $width, $rotate, $bitrate) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $ConversionSuccess = $ConversionErrors = FALSE;
  $NewPathname = $newPathname;
  $Extension = $extension;
  $OutputFilename = basename($newPathname);
  $WorkerPID = 0;
  $candidatesAreResolved = $pipelineIsReady = FALSE;
  $candidateFolders = array();
  $entryPointName = $inputExtension = '';
  $attemptCount = 0;
  $inputExtension = getExtension($pathname);
  list ($candidatesAreResolved, $candidateFolders) = resolvePipelineCandidates($conversionFamily, $inputExtension, $extension);
  // / The core calls familyHasPipeline() before it dispatches, so this branch is defensive
  // / rather than expected. Reaching it means something called this function directly
  // / without asking first, which is worth a warning naming the pair that was refused.
  if (!$candidatesAreResolved) warningEntry('The '.$conversionFamily.' pipeline dispatcher was called for '.$inputExtension.' to '.$extension.', which no installed pipeline claims.');
  else {
    foreach ($candidateFolders as $candidateFolder) {
      $attemptCount++;
      list ($pipelineIsReady, $entryPointName) = loadPipelineCore($candidateFolder);
      if (!$pipelineIsReady) continue;
      // / Clear any partial output an earlier candidate left behind before trying again.
      if ($attemptCount > 1 && file_exists($NewPathname)) @unlink($NewPathname);
      if ($Verbose) logEntry('Dispatching a '.$conversionFamily.' conversion to the '.$candidateFolder.' pipeline.');
      list ($ConversionSuccess, $ConversionErrors, $NewPathname, $Extension, $OutputFilename, $WorkerPID) = $entryPointName($pathname, $NewPathname, $Extension, $height, $width, $rotate, $bitrate);
      if ($ConversionSuccess) break;
      // / A failure that has another candidate behind it is a warning, never an error.
      // / The candidate already logged why it failed under its own name.
      if (count($candidateFolders) > $attemptCount) warningEntry('The '.$candidateFolder.' pipeline could not complete a '.$inputExtension.' to '.$Extension.' conversion. Falling back to the next implementation.'); }
    // / Every implementation has now been tried & none of them produced anything.
    // / A pipeline that reported a reason has already been heard. Do not restate it.
    // / Calling code need not log a failure the callee has already logged, & duplicating
    // / the entry misreports the origin & doubles the log volume.
    // / This matters most where it is least obvious. A refused stream is not a broken
    // / pipeline. Stream inspection denying a playlist is HRConvert2 working correctly, &
    // / the Stream pipeline has already said so under its own error number with the actual
    // / reason attached. A second, louder line claiming every pipeline failed sits
    // / underneath that & tells an operator to go looking for a fault that is not there.
    // / So 34003 now fires ONLY when a pipeline failed & said NOTHING AT ALL about why.
    // / That is a genuine defect in the pipeline & is worth its own number. A refusal is not.
    if (!$ConversionSuccess) {
      if ($ConversionErrors) {
        if ($Verbose) logEntry('The '.$conversionFamily.' conversion of '.$inputExtension.' to '.$Extension.' did not complete. The reason is logged above under the pipeline that reported it.'); }
      else {
        $ConversionErrors = TRUE;
        if (count($candidateFolders) > 1) errorEntry('All '.count($candidateFolders).' installed '.$conversionFamily.' pipelines failed to convert '.$inputExtension.' to '.$Extension.' & none reported a reason!', 34003, FALSE);
        else errorEntry('The '.$conversionFamily.' pipeline failed to convert '.$inputExtension.' to '.$Extension.' & reported no reason!', 34003, FALSE); } } }
  // / An entry point that returned nothing usable in the filename slot still has to hand the
  // / core a name, because an undefined value becomes a warning inside an AJAX response.
  if (!is_string($OutputFilename) or $OutputFilename === '') $OutputFilename = basename($NewPathname);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / The six contract values are not purged, because they are return values.
  purgeSensitiveMemory($EnableMemoryProtection, $candidatesAreResolved, $candidateFolders, $pipelineIsReady, $entryPointName, $inputExtension, $attemptCount, $conversionFamily, $pathname, $newPathname, $extension, $height, $width, $rotate, $bitrate);
  return array($ConversionSuccess, $ConversionErrors, $NewPathname, $Extension, $OutputFilename, $WorkerPID); }
// / -----------------------------------------------------------------------------------
?>
