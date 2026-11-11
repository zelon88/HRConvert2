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
// / File Information ...
// / v4.0.8.
// / A detachable dependency management component. convertCore.php runs without it.
// / This file defines functions only. setupCore.php & convertCore.php dispatch into them.
// /
// / It is required into the running process, as coreManager.php & setupCore.php are, & never run as a separate process.
// / A separate process would need a startup key to prove who launched it, & a required component inherits the caller's authorization.
// / A key is still required to change anything.
// / checkDepends reads the machine & needs nothing.
// / installDepends, updateDepends & uninstallDepends refuse without a token convertCore.php derived from the install secret.
// / A component reached some way nobody anticipated still cannot install a package.
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
$DependencyCoreVersion = 'v4.0.8';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether a folder belongs to one account & nobody else.
// / Accepts the folder & the numeric user id of the account. Returns TRUE when the folder is
// / not a link, is a directory, is owned by that id & has a mode of exactly 0700.
// / A folder the account owns may have lost bits to the umask, so it is put back to 0700.
// / Ownership is compared outright, because root may write to & chmod any folder, so being
// / able to do either proves nothing about whose folder it is.
function folderIsPrivateToAccount($folderPath, $accountId) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FolderIsPrivate = FALSE;
  clearstatcache(TRUE, $folderPath);
  if (!is_link($folderPath) && is_dir($folderPath) && fileowner($folderPath) === (int)$accountId) {
    @chmod($folderPath, 0700);
    clearstatcache(TRUE, $folderPath);
    if ((fileperms($folderPath) & 0777) === 0700) $FolderIsPrivate = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderPath, $accountId);
  return $FolderIsPrivate; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to find a scratch folder for dependency commands that only this account controls.
// / Accepts nothing. Returns a readiness boolean & the folder, in that order. A failure
// / returns FALSE & an empty string, & the caller must then run nothing at all.
// / A dependency command runs with HOME, its working directory & every XDG location pointed at this folder.
// / Tools read configuration & code from all of them, as python imports usercustomize from HOME & pip & git read configuration there.
// / So a folder another account can write would run its code as whoever runs the command, & --setup runs them as root.
// / The folder is named for the account & is used only when folderIsPrivateToAccount agrees.
// / A name another account has taken is warned about, & a folder with an unpredictable name is made instead.
// / mkdir fails rather than follow anything placed there first.
function resolveProbeScratchFolder() {
  // / Set variables.
  global $CurrentUser, $EnableMemoryProtection;
  // / The fallback folder is kept for the life of the process, so a taken name costs one
  // / folder & one warning rather than one of each for every dependency command.
  // / It holds a path & nothing secret.
  static $fallbackFolder = '';
  $FolderIsReady = FALSE;
  $ScratchFolder = '';
  $applicationSlug = $accountSlug = $candidateFolder = '';
  $accountId = -1;
  $applicationSlug = preg_replace('/[^A-Za-z0-9]/', '', engineApplicationName());
  $accountSlug = preg_replace('/[^A-Za-z0-9_-]/', '', (string)$CurrentUser);
  if ($accountSlug === '') $accountSlug = 'unknown';
  // / Some systems do not have posix extensions, so id is asked instead.
  $accountId = function_exists('posix_geteuid') ? (int)posix_geteuid() : (int)trim((string)shell_exec('id -u'));
  $candidateFolder = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$applicationSlug.'-probe-'.$accountSlug;
  if (!file_exists($candidateFolder) && !is_link($candidateFolder)) @mkdir($candidateFolder, 0700);
  if (folderIsPrivateToAccount($candidateFolder, $accountId)) {
    $FolderIsReady = TRUE;
    $ScratchFolder = $candidateFolder; }
  else {
    // / The fallback is checked again on every call, because a temporary directory cleaner
    // / may have removed it from underneath a process that runs for days.
    if ($fallbackFolder === '' or !folderIsPrivateToAccount($fallbackFolder, $accountId)) {
      if ($fallbackFolder === '') warningEntry('The dependency scratch folder at '.$candidateFolder.' is a link, belongs to another account or can be reached by one, & was not used. A folder with an unpredictable name is used instead. Delete it as root.');
      $fallbackFolder = $candidateFolder.'-'.bin2hex(random_bytes(8));
      if (!@mkdir($fallbackFolder, 0700) or !folderIsPrivateToAccount($fallbackFolder, $accountId)) $fallbackFolder = ''; }
    if ($fallbackFolder !== '') {
      $FolderIsReady = TRUE;
      $ScratchFolder = $fallbackFolder; }
    else warningEntry('No private scratch folder could be made for a dependency command, so it was not run.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $applicationSlug, $accountSlug, $candidateFolder, $accountId);
  return array($FolderIsReady, $ScratchFolder); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to build the environment a dependency command runs under.
// / Accepts nothing. Returns a run of exports to place in front of a command, ending in a
// / semicolon & a space.
// /
// / A dependency command runs outside the sandbox, which is why this exists.
// / sandboxCommand() points every writable location a tool reaches for at a tmpfs, so a conversion creates no stray state.
// / A version or capability probe runs as the web server account, in the installation root, with HOME unset.
// / A library that finds no HOME falls back to the working directory & writes into the installation itself.
// / A .cache directory beside convertCore.php is fontconfig, a shader cache or a LibreOffice profile doing exactly that.
// / Every variable below is pointed at a folder resolveProbeScratchFolder finds or makes for the running account alone.
// / With no such folder the prefix ends the shell before the command runs, because either other outcome is worse.
// / A prefix is correct here & would not be inside the sandbox, because exec() runs a shell & bwrap does not.
// / A package manager command is not given this, because redirecting apt's cache would change the machine.
function dependencyEnvironmentPrefix() {
  // / Set variables.
  global $EnableMemoryProtection;
  $EnvironmentPrefix = '';
  $scratchRoot = '';
  $scratchIsReady = FALSE;
  // / The folder is made rather than left to the tool, because a tool that cannot create it
  // / falls back to somewhere else & the point of this is to stop that happening.
  list ($scratchIsReady, $scratchRoot) = resolveProbeScratchFolder();
  // / These are exports followed by a semicolon rather than NAME=value prefixes.
  // / A prefix applies only to a simple command, & a source build runs in a subshell, where HOME=x ( ... ) is a syntax error.
  // / An export applies to everything after it in the same shell, subshells included, & the shell is discarded afterwards.
  // / The working directory moves too, because a tool that ignores HOME writes to a relative path wherever the process stands.
  // / cd is first, so a failure to enter the directory stops the command rather than running it in the installation root.
  if (!$scratchIsReady) $EnvironmentPrefix = 'exit 1; ';
  else $EnvironmentPrefix = 'cd '.escapeshellarg($scratchRoot).' || exit 1; export HOME='.escapeshellarg($scratchRoot)
    .'; export XDG_CACHE_HOME='.escapeshellarg($scratchRoot.DIRECTORY_SEPARATOR.'.cache')
    .'; export XDG_CONFIG_HOME='.escapeshellarg($scratchRoot.DIRECTORY_SEPARATOR.'.config')
    .'; export XDG_DATA_HOME='.escapeshellarg($scratchRoot.DIRECTORY_SEPARATOR.'.local')
    .'; export XDG_RUNTIME_DIR='.escapeshellarg($scratchRoot)
    .'; ';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $scratchRoot, $scratchIsReady);
  return $EnvironmentPrefix; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify & load the dependency manifest.
// / Accepts the manifest version this core requires.
// / Returns an availability boolean, the manifest array & the detected version, in order.
// / The version is read from the file WITHOUT executing it, for the same reason every
// / other component is. A manifest from another release may name packages that no longer
// / exist, or omit one this release cannot run without.
// / This is an EXACT match, the same rule applied to a GUI or a language pack.
function verifyDependsManifest($requiredDependsVersion) {
  // / Set variables.
  // / $CoreLoaded is declared so the guard at the top of depends.php can see it.
  // / The require below happens inside this function, so a global the required file tests
  // / must be in this scope. It is not read here & looks unused, & removing it on that
  // / reading made every manifest refuse to load with error 2.
  global $InstLoc, $CoreLoaded, $EnableMemoryProtection;
  $ManifestIsAvailable = FALSE;
  $DependsManifest = NULL;
  $DetectedDependsVersion = '';
  $manifestPath = $manifestContents = $cleanDetected = $cleanRequired = '';
  $versionMatches = array();
  $manifestPath = contractPath('depends.php');
  if (!file_exists($manifestPath)) warningEntry('The dependency manifest is not installed. Dependency management is unavailable.');
  else {
    $manifestContents = @file_get_contents($manifestPath);
    if (!is_string($manifestContents) or $manifestContents === '') warningEntry('The dependency manifest could not be read. Dependency management is unavailable.');
    else {
      if (preg_match('/\$DependsVersion\s*=\s*\'([^\']+)\'/', $manifestContents, $versionMatches)) $DetectedDependsVersion = $versionMatches[1];
      $cleanDetected = ltrim(trim($DetectedDependsVersion), 'vV');
      $cleanRequired = ltrim(trim((string)$requiredDependsVersion), 'vV');
      if ($cleanDetected === '') warningEntry('The dependency manifest reports no version. Dependency management is unavailable.');
      // / A requirement that arrived empty is a caller passing the wrong global rather than
      // / a manifest with the wrong version, & the message has to be able to say so.
      // / Reporting  requires v  with nothing after it sent an operator looking at the
      // / manifest, which was correct the whole time.
      else if ($cleanRequired === '') warningEntry('The dependency manifest reports v'.$cleanDetected.' & no required version was supplied. This is a fault in the caller rather than in the manifest.');
      else if ($cleanDetected !== $cleanRequired) warningEntry('The dependency manifest reports v'.$cleanDetected.' & this core requires v'.$cleanRequired.'. Dependency management is unavailable.');
      else {
        // / The manifest is nulled above rather than started as an empty array, so that a
        // / file which fails to declare it is caught by the isset below instead of quietly
        // / inheriting an empty array that reads as a manifest declaring nothing.
        require ($manifestPath);
        if (!isset($DependsManifest) or !is_array($DependsManifest) or count($DependsManifest) < 1) warningEntry('The dependency manifest loaded but declares nothing.');
        else $ManifestIsAvailable = TRUE; } } }
  // / A caller receives an array whatever happened, so a foreach over the result is safe.
  if (!is_array($DependsManifest)) $DependsManifest = array();
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $manifestPath, $manifestContents, $cleanDetected, $cleanRequired, $versionMatches, $requiredDependsVersion);
  return array($ManifestIsAvailable, $DependsManifest, $DetectedDependsVersion); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to confirm the caller is permitted to change this machine.
// / Accepts the authorization token supplied by the caller.
// / Returns TRUE when the token is valid & this invocation is running as root.
// / Installing a package rewrites the machine. Two things must both be true. The caller
// / must hold a token derived from the install secret, which proves it came through
// / convertCore.php, & the process must be root, which proves it can actually do the work.
function validateDependencyAuthorization($authorizationToken) {
  // / Set variables.
  global $RunningAsRoot, $CurrentUser, $Lol, $EnableMemoryProtection;
  $CallerIsAuthorized = FALSE;
  $tokenIsValid = FALSE;
  $tokenIsValid = validateStartupKey('dependency-write', $authorizationToken);
  if (!$tokenIsValid) errorEntry('A dependency operation was attempted without a valid authorization token!', 33000, FALSE);
  else if (!$RunningAsRoot) {
    print($Lol.'Dependency management requires root.'.$Lol);
    print('You are running as '.($CurrentUser === '' ? 'an unidentified user' : $CurrentUser).'.'.$Lol.$Lol);
    // / A refusal that only reaches the console leaves no trace for anybody reading the log
    // / afterwards. The token path already reports itself, so this one does too.
    warningEntry('A dependency operation was refused because the caller is not root.'); }
  else $CallerIsAuthorized = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $tokenIsValid, $authorizationToken);
  return $CallerIsAuthorized; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to identify the package manager this host uses.
// / Accepts no arguments.
// / Returns the manager name, or an empty string when none is recognized.
// / The answer is cached for the request, because every dependency would otherwise ask.
function detectPackageManager() {
  // / Set variables.
  global $EnableMemoryProtection;
  static $cachedManager = NULL;
  $PackageManager = '';
  $candidateManagers = array('apt-get', 'dnf', 'yum', 'apk', 'pacman');
  $candidateManager = '';
  if ($cachedManager !== NULL) $PackageManager = $cachedManager;
  else {
    foreach ($candidateManagers as $candidateManager) {
      if ($PackageManager === '' && locateDependency($candidateManager) !== '') $PackageManager = $candidateManager; }
    if ($PackageManager === '') warningEntry('No supported package manager was found on this host. Dependencies must be installed by hand.');
    $cachedManager = $PackageManager; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $candidateManagers, $candidateManager);
  return $PackageManager; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to apply an entry's architecture rules to the host it is running on.
// / Accepts a manifest entry & the architecture name. Returns the entry with any override
// / applied, whether this architecture is supported, & a reason when it is not.
// /
// / A manifest entry may differ by architecture, & most do not, so an entry with no architecture keys is unchanged.
// / 'Architectures' names every architecture the entry works on.
// / An entry running on another is UNSUPPORTED, its own state rather than a failure, because the tool was never going to work there.
// / 'ArchitectureOverrides' replaces individual fields for one architecture, such as a package name, a build command or a source.
// / Only the named fields change, & everything else is inherited.
// / An entry may use either, both or neither.
function applyArchitectureRules($dependencyEntry, $architectureName) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ResolvedEntry = $dependencyEntry;
  $ArchitectureIsSupported = TRUE;
  $UnsupportedReason = '';
  $supportedList = $overrideSet = array();
  $overrideKey = $overrideValue = '';
  if (isset($ResolvedEntry['Architectures']) && is_array($ResolvedEntry['Architectures'])) {
    $supportedList = $ResolvedEntry['Architectures'];
    if (!in_array($architectureName, $supportedList, TRUE)) {
      $ArchitectureIsSupported = FALSE;
      $UnsupportedReason = 'Supported on '.implode(', ', $supportedList).'. This host is '.$architectureName.'.'; } }
  // / An override is applied only for the architecture that names it.
  if ($ArchitectureIsSupported && isset($ResolvedEntry['ArchitectureOverrides'][$architectureName]) && is_array($ResolvedEntry['ArchitectureOverrides'][$architectureName])) {
    $overrideSet = $ResolvedEntry['ArchitectureOverrides'][$architectureName];
    foreach ($overrideSet as $overrideKey => $overrideValue) $ResolvedEntry[$overrideKey] = $overrideValue; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $supportedList, $overrideSet, $overrideKey, $overrideValue, $dependencyEntry, $architectureName);
  return array($ResolvedEntry, $ArchitectureIsSupported, $UnsupportedReason); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to choose which version of a dependency to install.
// / Accepts a manifest entry. Returns the chosen version & a reason, in that order.
// /
// / A build command that names a version installs only that one, & can neither take a patch nor stop before a breaking release.
// / So an entry may declare a VersionSource, a command listing what is available, & this picks the highest inside the window.
// / The window is inclusive at MinimumVersion & exclusive at MaximumVersion, so 3.14 to 3.15 accepts every 3.14 patch & no 3.15.
// / Naming the first refused version is easier to get right than naming the last accepted one.
// / The sort is numeric per field, because a lexical sort ranks 3.14.10 below 3.14.2 & would install an older release.
// / An entry with no VersionSource is unchanged.
// / A source that returns nothing usable is not an error, because a host with no route to an index should still install a pinned version.
function resolveTargetVersion($dependencyEntry) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ChosenVersion = '';
  $ChoiceReason = '';
  $sourceCommand = $candidate = $best = '';
  $candidateList = $commandOutput = array();
  $commandExitCode = 1;
  $sourceCommand = isset($dependencyEntry['VersionSource']) ? trim((string)$dependencyEntry['VersionSource']) : '';
  if ($sourceCommand === '') $ChoiceReason = 'This entry names no version source, so its build command decides.';
  else {
    exec(dependencyEnvironmentPrefix().$sourceCommand.' 2>/dev/null', $commandOutput, $commandExitCode);
    foreach ($commandOutput as $candidate) {
      $candidate = trim($candidate);
      if (!preg_match('/^[0-9]+(\\.[0-9]+)*$/', $candidate)) continue;
      if (isset($dependencyEntry['MinimumVersion']) && (string)$dependencyEntry['MinimumVersion'] !== '' && !compareVersionMinimum($candidate, (string)$dependencyEntry['MinimumVersion'])) continue;
      if (isset($dependencyEntry['MaximumVersion']) && trim((string)$dependencyEntry['MaximumVersion']) !== '' && compareVersionMinimum($candidate, (string)$dependencyEntry['MaximumVersion'])) continue;
      $candidateList[] = $candidate; }
    if (empty($candidateList)) $ChoiceReason = 'Nothing the version source offered fits the declared window. The build command decides.';
    else {
      foreach ($candidateList as $candidate) if ($best === '' or compareVersionMinimum($candidate, $best)) $best = $candidate;
      $ChosenVersion = $best;
      $ChoiceReason = 'Chose '.$best.' from '.count($candidateList).' candidate(s) inside the declared window.'; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $sourceCommand, $candidate, $best, $candidateList, $commandOutput, $commandExitCode, $dependencyEntry);
  return array($ChosenVersion, $ChoiceReason); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to judge a manifest entry before anything is probed.
// / Accepts the entry. Returns whether it is complete, whether its license is clear & the
// / reason it was refused, in that order.
// /
// / Provenance & mechanism are different things.
// / An entry with no License & no Source can still name a working binary, so it says how to use a tool & not where it came from.
// / Whether that is acceptable is an installation's decision, so both gates are settings.
// / GPL compatibility is checked by name against a deliberately short list.
// / A license it does not recognize is treated as unclear rather than incompatible, because only a wrong refusal is loud.
// / GPL-2.0 only is absent on purpose, because it is not compatible with GPLv3.
function judgeDependencyEntry($dependencyEntry) {
  // / Set variables.
  global $EngineEnforceGPLCompliantDeps, $EngineAllowIncompleteDeps, $EnableMemoryProtection;
  $EntryIsComplete = TRUE;
  $EntryIsLicenseClear = TRUE;
  $GateReason = '';
  $declaredLicense = $provenanceField = '';
  $compatibleLicenses = $missingFields = array();
  $compatibleLicenses = array('GPL-3.0', 'GPL-2.0-or-later', 'LGPL-2.1', 'LGPL-3.0', 'MIT',
    'BSD-2-Clause', 'BSD-3-Clause', 'Apache-2.0', 'ISC', 'MPL-2.0', 'Zlib', 'Unlicense',
    'CC0-1.0', 'PHP-3.01', 'Public Domain');
  $declaredLicense = isset($dependencyEntry['License']) ? trim((string)$dependencyEntry['License']) : '';
  if (isset($EngineEnforceGPLCompliantDeps) && $EngineEnforceGPLCompliantDeps === TRUE) {
    if ($declaredLicense === '') {
      $EntryIsLicenseClear = FALSE;
      $GateReason = 'It declares no license & --Engine Enforce GPL Compliant Deps-- is on.'; }
    else if (!in_array($declaredLicense, $compatibleLicenses, TRUE)) {
      $EntryIsLicenseClear = FALSE;
      $GateReason = 'It declares '.$declaredLicense.', which is not on the compatible list.'; } }
  // / Completeness is about the fields that say where a thing came from, never about the
  // / fields needed to run it. A missing Binary or VersionCommand is a broken entry & is
  // / caught by the probe rather than here.
  if ($EntryIsLicenseClear && (!isset($EngineAllowIncompleteDeps) or $EngineAllowIncompleteDeps !== TRUE)) {
    foreach (array('License', 'Source', 'Purpose') as $provenanceField) {
      if (!isset($dependencyEntry[$provenanceField]) or trim((string)$dependencyEntry[$provenanceField]) === '') $missingFields[] = $provenanceField; }
    if (!empty($missingFields)) {
      $EntryIsComplete = FALSE;
      $GateReason = 'It declares no '.implode(' or ', $missingFields).' & --Engine Allow Incomplete Deps-- is off.'; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $declaredLicense, $provenanceField, $compatibleLicenses, $missingFields, $dependencyEntry);
  return array($EntryIsComplete, $EntryIsLicenseClear, $GateReason); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the installed version of one dependency.
// / Accepts one manifest entry.
// / Returns a presence boolean, the detected version & a status word, in that order.
// / The status is 'ok', 'outdated', 'unknown-version', 'absent' or 'unverifiable'.
// / A dependency that is present but will not report a version is 'unknown-version'. It is
// / NOT treated as absent, because reinstalling something that works helps nobody.
// / The raw output is returned as well, because a pattern that does not match is the most
// / common manifest fault there is & the only way to fix one is to see what it was matched
// / against. A dependency that reports unknown-version shows its first line of output.
function resolveDependencyState($dependencyEntry) {
  // / Set variables.
  global $HostArchitecture, $EnableMemoryProtection;
  $entryIsComplete = $entryIsLicenseClear = TRUE;
  $entryWasRefused = FALSE;
  $entryGateReason = '';
  $DependencyIsPresent = FALSE;
  $DetectedVersion = '';
  $DependencyStatus = 'absent';
  $RawOutput = '';
  $binaryPath = $versionOutput = '';
  $versionMatches = $commandOutput = array();
  $commandExitCode = 1;
  $commandWasExecuted = FALSE;
  $architectureIsSupported = TRUE;
  $unsupportedReason = '';
  // / The architecture rules are applied FIRST, so everything below judges the entry this
  // / host actually has rather than the one the manifest wrote for a different machine.
  // / An entry that cannot run here is reported as unsupported & is NOT probed. Running a
  // / version command for a binary that was never built for this architecture produces a
  // / confusing error rather than a clear answer.
  list ($dependencyEntry, $architectureIsSupported, $unsupportedReason) = applyArchitectureRules($dependencyEntry, (string)$HostArchitecture);
  // / The entry is judged before it is probed, & a refusal short circuits the architecture
  // / check below by making it unsupported for a different stated reason.
  // / A SEPARATE STATEMENT rather than another arm of the chain. Adding a branch to the
  // / if/else below is what broke this function the first time it was attempted.
  // / Both gates default to off, so an installation setting neither reaches the probe with
  // / $architectureIsSupported exactly as applyArchitectureRules left it.
  list ($entryIsComplete, $entryIsLicenseClear, $entryGateReason) = judgeDependencyEntry($dependencyEntry);
  if (!$entryIsLicenseClear or !$entryIsComplete) {
    $architectureIsSupported = FALSE;
    $unsupportedReason = $entryGateReason;
    $entryWasRefused = TRUE;
    warningEntry((string)$dependencyEntry['Name'].' was refused. '.$entryGateReason); }
  if (!$architectureIsSupported) {
    // / A refusal & an unsupported architecture reach here the same way & mean different
    // / things. An operator whose entry was refused by a policy needs to read that, not be
    // / told their machine is wrong.
    $DependencyStatus = $entryWasRefused ? 'refused' : 'unsupported';
    $RawOutput = $unsupportedReason; }
  else {
  // / A dependency with no binary is a library. Its version command is the only evidence.
  if ((string)$dependencyEntry['Binary'] !== '') {
      // / A path & a name are different questions, & asking the wrong one refuses a working tool.
      // / locateDependency finds a binary by name & refuses anything containing a path separator.
      // / So the kind of value decides the question.
      // / A separator means the operator chose this file, & it is verified where they put it.
      // / A bare name means find it, by the search order & the command -v fallback.
      if (strpos((string)$dependencyEntry['Binary'], DIRECTORY_SEPARATOR) !== FALSE) {
        $binaryPath = (string)$dependencyEntry['Binary'];
        if (is_file($binaryPath) && is_executable($binaryPath)) $DependencyIsPresent = TRUE;
        else {
          // / A path that is not there is a CONFIGURATION MISTAKE rather than a missing
          // / tool, & it reads differently. Reporting both as absent is what hid this.
          // / An operator who named a path wants to know the path is wrong. Telling them to
          // / install something they already installed sends them the wrong way entirely.
          $DependencyStatus = 'misconfigured';
          $RawOutput = 'The manifest names '.$binaryPath.' & nothing executable is there.';
          warningEntry('The manifest names a path for '.(string)$dependencyEntry['Name'].' & nothing executable is at it: '.$binaryPath);
          $binaryPath = ''; } }
      else {
        $binaryPath = locateDependency((string)$dependencyEntry['Binary']);
        if ($binaryPath === '') $DependencyStatus = 'absent';
        else $DependencyIsPresent = TRUE; } }
  else if ((string)$dependencyEntry['VersionCommand'] !== '') {
    // / A library has no binary, so its version command is both the presence test & the
    // / version read. The output is kept & reused below rather than running it a second
    // / time, which spawned two processes per library & repeated any side effect.
    exec(dependencyEnvironmentPrefix().'('.(string)$dependencyEntry['VersionCommand'].') < /dev/null 2>&1', $commandOutput, $commandExitCode);
    $commandWasExecuted = TRUE;
    if ($commandExitCode === 0) $DependencyIsPresent = TRUE;
    // / A library has no binary to look for, so a failed command is the ONLY evidence there
    // / is. Reporting absent without saying why leaves an operator with nothing to act on,
    // / & the reason is usually an import error naming exactly what is wrong.
    else $RawOutput = trim((string)(isset($commandOutput[count($commandOutput) - 1]) ? $commandOutput[count($commandOutput) - 1] : '')); }
  else $DependencyStatus = 'unverifiable';
  if ($DependencyIsPresent) {
    $DependencyStatus = 'unknown-version';
    if ((string)$dependencyEntry['VersionCommand'] === '') $DependencyStatus = 'ok';
    else {
      if (!$commandWasExecuted) {
        $commandOutput = array();
        exec(dependencyEnvironmentPrefix().'('.(string)$dependencyEntry['VersionCommand'].') < /dev/null 2>&1', $commandOutput, $commandExitCode); }
      $versionOutput = implode(PHP_EOL, $commandOutput);
      $RawOutput = trim((string)(isset($commandOutput[0]) ? $commandOutput[0] : ''));
      // / A dependency that declares no pattern is not asking to be version checked. Its
      // / command ran & succeeded, which is the whole of what it claims to prove.
      // / Leaving it as unknown-version reported a fault where there is nothing to know.
      if ((string)$dependencyEntry['VersionPattern'] === '') $DependencyStatus = 'ok';
      // / A pattern that matches but captures nothing is a manifest fault rather than a
      // / missing dependency, so the group is tested before it is read.
      else if (preg_match((string)$dependencyEntry['VersionPattern'], $versionOutput, $versionMatches) && isset($versionMatches[1])) {
        $DetectedVersion = $versionMatches[1];
        if ((string)$dependencyEntry['MinimumVersion'] === '') $DependencyStatus = 'ok';
        // / Too new is a state of its own, not outdated & not ok.
        // / A dependency past what this application supports fails at the point of use, with an error about a flag or a symbol.
        // / ImageMagick 6 to 7 changed the command line, FFMPEG 4 to 5 changed flags, & Python 3.15 stops every compiled module loading.
        // / MaximumVersion is the first version not accepted, so 3.15 excludes the whole 3.15 line & leaves every 3.14 patch usable.
        // / An entry that declares no ceiling has no upper bound.
        else if (!compareVersionMinimum($DetectedVersion, (string)$dependencyEntry['MinimumVersion'])) $DependencyStatus = 'outdated';
        else if (isset($dependencyEntry['MaximumVersion']) && trim((string)$dependencyEntry['MaximumVersion']) !== '' && compareVersionMinimum($DetectedVersion, (string)$dependencyEntry['MaximumVersion'])) $DependencyStatus = 'too-new';
        else $DependencyStatus = 'ok'; } } }
  // / The else above wraps every probe. An unsupported entry reaches the return with its
  // / status already set & nothing having been executed on its behalf.
  }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $entryIsComplete, $entryIsLicenseClear, $entryWasRefused, $entryGateReason, $architectureIsSupported, $unsupportedReason, $binaryPath, $versionOutput, $versionMatches, $commandOutput, $commandExitCode, $commandWasExecuted, $dependencyEntry);
  return array($DependencyIsPresent, $DetectedVersion, $DependencyStatus, $RawOutput); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say which versions of a dependency this release accepts.
// / Accepts a manifest entry. Returns text such as 26.02 or later.
function dependencyWindowText($dependencyEntry) {
  // / Set variables.
  global $EnableMemoryProtection;
  $WindowText = 'any version';
  $minimumVersion = $maximumVersion = '';
  $minimumVersion = isset($dependencyEntry['MinimumVersion']) ? trim((string)$dependencyEntry['MinimumVersion']) : '';
  $maximumVersion = isset($dependencyEntry['MaximumVersion']) ? trim((string)$dependencyEntry['MaximumVersion']) : '';
  if ($minimumVersion !== '' && $maximumVersion !== '') $WindowText = $minimumVersion.' or later & earlier than '.$maximumVersion;
  else if ($minimumVersion !== '') $WindowText = $minimumVersion.' or later';
  else if ($maximumVersion !== '') $WindowText = 'a version earlier than '.$maximumVersion;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $minimumVersion, $maximumVersion, $dependencyEntry);
  return $WindowText; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the folder every dependency this application builds for itself lives under.
// / Accepts nothing. Returns the absolute path.
// / It is this application's alone. It sits under /usr/local/libexec beside this application's own
// / copy of bubblewrap, in the folder named for the application, so two applications on one host
// / never share a build & neither touches the copy a package manager keeps.
function ownBuildsRoot() {
  // / Set variables.
  $BuildsRoot = '/usr/local/libexec/'.engineApplicationSlug().'/builds';
  return $BuildsRoot; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the folder this application's own build of one dependency lives in.
// / Accepts a manifest entry. Returns the absolute path, or an empty string for a name nothing safe
// / can be made from.
// / The folder is named for the entry & is laid out as a prefix, so a build installs its program at
// / bin/ beneath it, as one handed --prefix would.
function ownBuildFolder($dependencyEntry) {
  // / Set variables.
  global $EnableMemoryProtection;
  $BuildFolder = $nameSlug = '';
  $nameSlug = trim((string)preg_replace('/[^a-z0-9]+/', '-', strtolower(isset($dependencyEntry['Name']) ? (string)$dependencyEntry['Name'] : '')), '-');
  if ($nameSlug !== '') $BuildFolder = ownBuildsRoot().DIRECTORY_SEPARATOR.$nameSlug;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $nameSlug, $dependencyEntry);
  return $BuildFolder; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether a manifest entry asks for this application's own build where the
// / package manager's falls short.
// / Accepts the entry, with its architecture rules applied. Returns TRUE when a package manager
// / installs it, it declares a Fallback carrying a build command, it names its program by a plain
// / name, & its VersionCommand runs that program.
// / An own build is found by its program, so an entry with none, or one naming a path an operator chose, has nowhere to put one.
// / Every other Type already says how it is made, so only a package manager entry falls back.
// / The version asked is the program's own, because the build's folder is put first on the PATH of the version command.
// / That finds the own build only where the command runs the program by its name.
// / A command running some other program would be answered by the package manager's copy, & misjudge the own build.
function ownBuildIsDeclared($dependencyEntry) {
  // / Set variables.
  global $EnableMemoryProtection;
  $BuildIsDeclared = FALSE;
  $binaryName = $versionCommand = '';
  $binaryName = isset($dependencyEntry['Binary']) ? (string)$dependencyEntry['Binary'] : '';
  $versionCommand = isset($dependencyEntry['VersionCommand']) ? trim((string)$dependencyEntry['VersionCommand']) : '';
  $BuildIsDeclared = (isset($dependencyEntry['Type'], $dependencyEntry['Fallback']) && (string)$dependencyEntry['Type'] === 'apt'
    && is_array($dependencyEntry['Fallback']) && isset($dependencyEntry['Fallback']['BuildCommand'])
    && trim((string)$dependencyEntry['Fallback']['BuildCommand']) !== ''
    && preg_match('/^[A-Za-z0-9][A-Za-z0-9._+-]*$/', $binaryName) === 1
    && preg_match('/^'.preg_quote($binaryName, '/').'(\s|$)/', $versionCommand) === 1 && ownBuildFolder($dependencyEntry) !== '');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $binaryName, $versionCommand, $dependencyEntry);
  return $BuildIsDeclared; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether the program of this application's own build of a dependency may
// / be run.
// / Accepts the build's folder & the path of its program. Returns whether it may & why it may not,
// / in that order.
// / Root runs it to ask its version, so it must be a plain file root owns, which no other account can change.
// / Its folders must be ones root alone can change, exactly as for this application's copy of bubblewrap.
// / A path where nothing is has no reason, because there is simply no build.
// / A program that is there & fails is never run, & the reason says why.
// / The whole build is judged, because a program may read what was installed beside it.
// / A folder another account could change is one root later empties, so every name must be root's alone, & none a link.
// / treeIsTrustedByRoot() judges every folder above the build as well.
function ownBuildIsTrusted($buildFolder, $binaryPath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $BuildIsTrusted = $treeIsTrusted = FALSE;
  $TrustNotice = $trustNotice = '';
  clearstatcache(TRUE, $binaryPath);
  if (!file_exists($binaryPath) && !is_link($binaryPath)) $TrustNotice = '';
  else if (is_link($binaryPath) or !is_file($binaryPath)) $TrustNotice = $binaryPath.' is not a plain file.';
  else {
    list ($treeIsTrusted, $trustNotice) = treeIsTrustedByRoot($buildFolder, array());
    if (!$treeIsTrusted) $TrustNotice = $trustNotice;
    else if (!is_executable($binaryPath)) $TrustNotice = $binaryPath.' cannot be run.';
    else $BuildIsTrusted = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $treeIsTrusted, $trustNotice, $buildFolder, $binaryPath);
  return array($BuildIsTrusted, $TrustNotice); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether the account that runs conversions can run this application's own
// / build of a dependency.
// / Accepts the path of the program. Returns whether that account can & the account it is, in that
// / order.
// / A build root trusts can still be one the web server's account cannot run. A folder above it closed
// / to that account, or a filesystem that runs no programs, stops it, & root's own checks see neither.
// / So root asks as that account whether the program may be read & run, which runs nothing, as the copy
// / of bubblewrap is asked. Any other account is the one asking, & its own version probe has just run
// / the program, so it is answered TRUE.
function ownBuildIsRunnable($binaryPath) {
  // / Set variables.
  global $RunningAsRoot, $CurrentUser, $ApacheUser, $EnableMemoryProtection;
  $BuildIsRunnable = TRUE;
  $RunAccount = (string)$CurrentUser;
  $accessCommand = '';
  $commandOutput = array();
  $commandExitCode = 1;
  if ($RunningAsRoot && (string)$CurrentUser !== (string)$ApacheUser) {
    $RunAccount = (string)$ApacheUser;
    // / This runs unsandboxed, as the probe of the copy of bubblewrap does. It is the shell's own test
    // / & reads nothing but the permissions on the way to the program.
    $accessCommand = 'test -r '.escapeshellarg((string)$binaryPath).' && test -x '.escapeshellarg((string)$binaryPath);
    list ($commandOutput, $commandExitCode) = runAsAccount($accessCommand, (string)$ApacheUser);
    $BuildIsRunnable = ($commandExitCode === 0); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $accessCommand, $commandOutput, $commandExitCode, $binaryPath);
  return array($BuildIsRunnable, $RunAccount); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to describe a manifest entry as its own build in one folder.
// / Accepts the entry, with its architecture rules applied, & the folder. Returns the entry rewritten
// / so every probe runs the program in that folder. Binary is its path, the folder's bin is put
// / first on the PATH of every command the entry runs, & the Source & License are the Fallback's.
// / The PATH is put ahead of a command rather than written into it, because a command is written by
// / whoever added the entry & rewriting their text is a good way to break a quoting they got right.
// / The architecture keys are taken off, because they are already applied & applying them again
// / would put back the Binary they name.
function ownBuildEntry($dependencyEntry, $buildFolder) {
  // / Set variables.
  global $EnableMemoryProtection;
  $OwnEntry = $dependencyEntry;
  $binFolder = $pathPrefix = $commandKey = $provenanceField = '';
  $binFolder = rtrim((string)$buildFolder, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'bin';
  $pathPrefix = 'export PATH='.escapeshellarg($binFolder).':"$PATH"; ';
  $OwnEntry['Binary'] = $binFolder.DIRECTORY_SEPARATOR.(string)$dependencyEntry['Binary'];
  foreach (array('VersionCommand', 'CapabilityCommand', 'CapabilityWriteCommand') as $commandKey) {
    if (isset($OwnEntry[$commandKey]) && trim((string)$OwnEntry[$commandKey]) !== '') $OwnEntry[$commandKey] = $pathPrefix.(string)$OwnEntry[$commandKey]; }
  foreach (array('Source', 'License') as $provenanceField) {
    if (isset($dependencyEntry['Fallback'][$provenanceField]) && trim((string)$dependencyEntry['Fallback'][$provenanceField]) !== '') $OwnEntry[$provenanceField] = (string)$dependencyEntry['Fallback'][$provenanceField]; }
  unset($OwnEntry['Fallback'], $OwnEntry['Architectures'], $OwnEntry['ArchitectureOverrides']);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $OwnEntry is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $binFolder, $pathPrefix, $commandKey, $provenanceField, $dependencyEntry, $buildFolder);
  return $OwnEntry; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the program of this application's own build of a dependency, where one may
// / be run, without asking it anything.
// / Accepts a manifest entry. Returns the entry as its own build & the program's path where one may
// / be run, or the entry unchanged & an empty path, in that order.
// / It asks no version, so it costs no process. resolveDependencyChoice() asks the version, & that
// / is what decides whether a conversion uses the build. This is for what only needs the program.
function ownBuildInService($dependencyEntry) {
  // / Set variables.
  global $HostArchitecture, $EnableMemoryProtection;
  $ServiceEntry = $dependencyEntry;
  $ServicePath = '';
  $resolvedEntry = array();
  $architectureIsSupported = $buildIsTrusted = FALSE;
  $unsupportedReason = $buildFolder = $binaryPath = $trustNotice = '';
  list ($resolvedEntry, $architectureIsSupported, $unsupportedReason) = applyArchitectureRules($dependencyEntry, (string)$HostArchitecture);
  if ($architectureIsSupported && ownBuildIsDeclared($resolvedEntry)) {
    $buildFolder = ownBuildFolder($resolvedEntry);
    $binaryPath = $buildFolder.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.(string)$resolvedEntry['Binary'];
    list ($buildIsTrusted, $trustNotice) = ownBuildIsTrusted($buildFolder, $binaryPath);
    if ($buildIsTrusted) {
      $ServiceEntry = ownBuildEntry($resolvedEntry, $buildFolder);
      $ServicePath = $binaryPath; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $resolvedEntry, $architectureIsSupported, $buildIsTrusted, $unsupportedReason, $buildFolder, $binaryPath, $trustNotice, $dependencyEntry);
  return array($ServiceEntry, $ServicePath); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the state of a dependency, from this application's own build where it has one.
// / Accepts a manifest entry. Returns a presence boolean, the detected version, a status word, the
// / raw output, the path of the program & where the program came from, in that order. The first four
// / are exactly what resolveDependencyState() returns. Where it came from is 'own' for this
// / application's own build & 'system' for anything else, & the path is empty where there is no
// / program to name.
// / The own build is asked first, & the window decides.
// / One that answers inside the window is the program used.
// / One that does not falls back to the package manager's copy where that answers inside the window, & a warning says so.
// / Where neither does, the own build's state is returned, because an install builds it again.
// / An own build answering with no readable version is misconfigured, because a build this application made always reports one.
// / An own build that is not root's alone is never run.
// / One root asks about must be one the conversion account can run, or root's answer would describe a program no conversion reaches.
// / Then the package manager's copy is used where it answers inside the window, & the warning says --update-depends removes the build.
// / Where it does not, the entry is misconfigured & says why.
// / resolveDependencyState() still reads the package manager's copy alone, because whether an own build is needed is about that copy.
function resolveDependencyChoice($dependencyEntry) {
  // / Set variables.
  global $HostArchitecture, $EnableMemoryProtection;
  $DependencyIsPresent = FALSE;
  $DetectedVersion = $RawOutput = $BinaryPath = '';
  $DependencyStatus = 'absent';
  $BinaryOrigin = 'system';
  $resolvedEntry = array();
  $architectureIsSupported = $ownIsTrusted = $ownIsPresent = $ownIsRunnable = FALSE;
  $unsupportedReason = $ownFolder = $ownPath = $ownNotice = $ownLead = $ownAdvice = $ownVersion = $ownStatus = $ownOutput = $runAccount = '';
  list ($resolvedEntry, $architectureIsSupported, $unsupportedReason) = applyArchitectureRules($dependencyEntry, (string)$HostArchitecture);
  if ($architectureIsSupported && ownBuildIsDeclared($resolvedEntry)) {
    $ownFolder = ownBuildFolder($resolvedEntry);
    $ownPath = $ownFolder.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.(string)$resolvedEntry['Binary'];
    list ($ownIsTrusted, $ownNotice) = ownBuildIsTrusted($ownFolder, $ownPath);
    if ($ownNotice !== '') {
      $ownLead = ' is never run. ';
      $ownAdvice = ' Run --setup --install-depends as root to build it again.'; }
    if ($ownIsTrusted) list ($ownIsPresent, $ownVersion, $ownStatus, $ownOutput) = resolveDependencyState(ownBuildEntry($resolvedEntry, $ownFolder));
    if ($ownIsTrusted && $ownStatus === 'ok') list ($ownIsRunnable, $runAccount) = ownBuildIsRunnable($ownPath);
    if ($ownIsTrusted && $ownStatus === 'ok' && !$ownIsRunnable) {
      $ownLead = ' is not used. ';
      $ownNotice = $runAccount.', which runs conversions, cannot run '.$ownPath.'. Every folder above it must let that account through, & its filesystem must run programs.'; } }
  if ($ownIsTrusted && $ownStatus === 'ok' && $ownIsRunnable) {
    list ($DependencyIsPresent, $DetectedVersion, $DependencyStatus, $RawOutput) = array($ownIsPresent, $ownVersion, $ownStatus, $ownOutput);
    $BinaryPath = $ownPath;
    $BinaryOrigin = 'own'; }
  else {
    list ($DependencyIsPresent, $DetectedVersion, $DependencyStatus, $RawOutput) = resolveDependencyState($dependencyEntry);
    // / The package manager's program is found exactly as it always was, by its name unless the
    // / manifest named a path.
    if ($DependencyIsPresent) $BinaryPath = (string)$resolvedEntry['Binary'];
    if ($BinaryPath !== '' && strpos($BinaryPath, DIRECTORY_SEPARATOR) === FALSE) $BinaryPath = locateDependency($BinaryPath);
    // / The advice follows what is in service. --install-depends finds a package manager's copy that
    // / answers & leaves everything as it is, so only --update-depends takes the own build away.
    if ($ownNotice !== '' && $DependencyStatus === 'ok') warningEntry(engineApplicationName().'\'s own build of '.(string)$resolvedEntry['Name'].$ownLead.$ownNotice.' The package manager\'s '.$DetectedVersion.' is used. Run --setup --update-depends as root to take the own build away.');
    else if ($ownNotice !== '') {
      $DependencyStatus = 'misconfigured';
      $RawOutput = engineApplicationName().'\'s own build'.$ownLead.$ownNotice;
      warningEntry(engineApplicationName().'\'s own build of '.(string)$resolvedEntry['Name'].$ownLead.$ownNotice.$ownAdvice); }
    else if ($ownIsTrusted && $DependencyStatus === 'ok') warningEntry(engineApplicationName().'\'s own build of '.(string)$resolvedEntry['Name'].' is '.$ownStatus.($ownVersion !== '' ? ' at '.$ownVersion : '').', so the package manager\'s '.$DetectedVersion.' is used. Run --setup --update-depends as root to take the own build away.');
    else if ($ownIsTrusted) {
      list ($DependencyIsPresent, $DetectedVersion, $DependencyStatus, $RawOutput) = array($ownIsPresent, $ownVersion, $ownStatus, $ownOutput);
      $BinaryPath = $ownPath;
      $BinaryOrigin = 'own';
      if ($ownStatus === 'unknown-version') {
        $DependencyStatus = 'misconfigured';
        $RawOutput = engineApplicationName().'\'s own build answers with no version this release can read. '.$ownOutput; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $resolvedEntry, $architectureIsSupported, $ownIsTrusted, $ownIsPresent, $ownIsRunnable, $unsupportedReason, $ownFolder, $ownPath, $ownNotice, $ownLead, $ownAdvice, $ownVersion, $ownStatus, $ownOutput, $runAccount, $dependencyEntry);
  return array($DependencyIsPresent, $DetectedVersion, $DependencyStatus, $RawOutput, $BinaryPath, $BinaryOrigin); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove a folder this application built, & everything in it, without following
// / anything.
// / Accepts the folder's path. Returns whether nothing is left of it.
// / It removes only a folder directly in the builds folder, & only while that folder is root's alone,
// / so no account but root could have put a name in the way. removeTreeWithoutFollowing() in the
// / Kernel does the removal. It examines every name with lstat, removes a link as a link, & makes
// / every folder root's alone before anything in it is listed, which matters here because a build
// / is often taken away for being one another account could change. A removal it could not finish
// / is warned about here, & the caller says which build it was.
function removeOwnBuildTree($treePath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $TreeIsRemoved = $parentIsTrusted = FALSE;
  $resolvedParent = $trustNotice = $treeName = $removalNotice = '';
  $treePath = rtrim((string)$treePath, DIRECTORY_SEPARATOR);
  $treeName = basename($treePath);
  if (dirname($treePath) === ownBuildsRoot() && $treeName !== '' && $treeName !== '.' && $treeName !== '..') list ($parentIsTrusted, $resolvedParent, $trustNotice) = pathIsTrustedByRoot(ownBuildsRoot());
  if ($parentIsTrusted && $resolvedParent === ownBuildsRoot()) list ($TreeIsRemoved, $removalNotice) = removeTreeWithoutFollowing($treePath);
  else {
    clearstatcache(TRUE, $treePath);
    $TreeIsRemoved = (!file_exists($treePath) && !is_link($treePath)); }
  if (!$TreeIsRemoved && $removalNotice !== '') warningEntry($removalNotice);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $parentIsTrusted, $resolvedParent, $trustNotice, $treeName, $removalNotice, $treePath);
  return $TreeIsRemoved; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to hold the lock every change to this application's own builds is made under.
// / Accepts nothing. Returns the open handle holding the lock, or FALSE where none could be held.
// / It allows one change at a time. A build, a removal & a sweep each hold it while they change the builds
// / folder, so a sweep never takes away a folder another run is building in. The lock file sits in the
// / builds folder, which root alone can change, & is opened without following anything. The kernel
// / lets go of it when a process ends, however it ends, so an interrupted run never leaves it held.
// / A run that has to wait for another says so first, because a build can take an hour on arm.
function holdOwnBuildLock() {
  // / Set variables.
  global $Lol, $EnableMemoryProtection;
  $LockHandle = FALSE;
  $lockPath = $lockNotice = '';
  $lockWasCreated = $lockWouldBlock = FALSE;
  $lockPath = ownBuildsRoot().DIRECTORY_SEPARATOR.'.lock';
  list ($LockHandle, $lockWasCreated, $lockNotice) = openFileWithoutFollowing($lockPath, 0600);
  if ($LockHandle !== FALSE && !@flock($LockHandle, LOCK_EX | LOCK_NB, $lockWouldBlock)) {
    if ($lockWouldBlock) print('  '.str_pad('Own builds', 20).'waiting, because another run is changing them.'.$Lol);
    if (!@flock($LockHandle, LOCK_EX)) {
      @fclose($LockHandle);
      $LockHandle = FALSE;
      $lockNotice = $lockPath.' could not be locked.'; } }
  if ($LockHandle === FALSE) warningEntry(engineApplicationName().'\'s own builds could not be locked, so none was changed. '.$lockNotice);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $LockHandle is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $lockPath, $lockNotice, $lockWasCreated, $lockWouldBlock);
  return $LockHandle; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to let go of the lock holdOwnBuildLock() took.
// / Accepts the handle it returned. Returns whether the lock was let go of, which is TRUE where there
// / was none to let go of.
function releaseOwnBuildLock($lockHandle) {
  // / Set variables.
  global $EnableMemoryProtection;
  $LockIsReleased = TRUE;
  if (is_resource($lockHandle)) {
    $LockIsReleased = @flock($lockHandle, LOCK_UN);
    @fclose($lockHandle); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $lockHandle);
  return $LockIsReleased; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to build this application's own copy of a dependency & put it in service.
// / Accepts the entry, with its architecture rules applied, & a sentence saying why it is built.
// / Returns a sanity boolean, a status word & a sentence, in that order. The boolean is TRUE when a
// / build answering inside the entry's window is in service. The status is 'installed', 'updated'
// / or 'FAILED'.
// / It is built beside the one in service & only then put in its place.
// / The Fallback's command gets a new folder only root can open as DEPENDENCY_PREFIX.
// / DEPENDENCY_VERSION & DEPENDENCY_SHA256 carry the Version & Sha256 the Fallback pins, empty where nothing is pinned.
// / Both are always set, so a value left in root's environment never stands in for one.
// / What it built is asked its version as the one in service would be.
// / It is renamed into place only when it is root's alone throughout, answers inside the window & is the version pinned.
// / A sandbox therefore runs the old build or the new one & never part of either.
// / The one in service is renamed aside first, because a folder cannot be renamed over another.
// / A conversion starting between the two renames runs the package manager's copy where that answers inside the window, or is refused.
// / A build that fails leaves the one in service as it was.
// / Each folder is made only where nothing is, & only beneath one root alone can change.
// / This application's own folders are opened to every account, because the conversion account must reach the build through them.
// / The whole build is made under holdOwnBuildLock(), & is ready only once the conversion account can run what was put in service.
function buildOwnCopy($dependencyEntry, $buildReason) {
  // / Set variables.
  global $RunningAsRoot, $HostArchitecture, $Lol, $EnableMemoryProtection;
  $BuildIsReady = FALSE;
  $BuildStatus = 'FAILED';
  $BuildDetail = '';
  $finalFolder = $buildsFolder = $folderPath = $resolvedFolder = $resolvedParent = $trustNotice = $stagingFolder = $retiredFolder = $runAccount = '';
  $pinnedVersion = $pinnedChecksum = $environmentExports = $stagedBinary = $stagedNotice = $stagedVersion = $stagedStatus = $stagedOutput = '';
  $folderIsTrusted = $parentIsTrusted = $commandStarted = $stagedIsTrusted = $stagedIsPresent = $copyExisted = $copyIsInService = $copyIsRunnable = $lockIsReleased = FALSE;
  $lockHandle = FALSE;
  $commandOutput = array();
  $commandExitCode = 1;
  $finalFolder = ownBuildFolder($dependencyEntry);
  $buildsFolder = ownBuildsRoot();
  $pinnedVersion = isset($dependencyEntry['Fallback']['Version']) ? trim((string)$dependencyEntry['Fallback']['Version']) : '';
  $pinnedChecksum = isset($dependencyEntry['Fallback']['Sha256']) ? strtolower(trim((string)$dependencyEntry['Fallback']['Sha256'])) : '';
  if (!$RunningAsRoot) $BuildDetail = 'Only root builds one.';
  else if (!ownBuildIsDeclared($dependencyEntry)) $BuildDetail = 'Its manifest entry declares no Fallback that can be built.';
  else {
    // / Each folder is made only where nothing is, & only beneath a folder root alone can change, so
    // / nothing is made through a name another account could swap. It is given its mode outright,
    // / because mkdir leaves out whatever the umask removes.
    foreach (array(dirname(dirname($buildsFolder)), dirname($buildsFolder), $buildsFolder) as $folderPath) {
      list ($parentIsTrusted, $resolvedParent, $trustNotice) = pathIsTrustedByRoot(dirname($folderPath));
      if ($parentIsTrusted && $resolvedParent === dirname($folderPath) && !file_exists($folderPath) && !is_link($folderPath) && @mkdir($folderPath, 0755)) @chmod($folderPath, 0755); }
    list ($folderIsTrusted, $resolvedFolder, $trustNotice) = pathIsTrustedByRoot($buildsFolder);
    if (!$folderIsTrusted or $resolvedFolder !== $buildsFolder) $BuildDetail = $buildsFolder.' is not a folder root alone can change, so nothing was built into it. '.$trustNotice;
    else {
      // / The application's folder & the builds folder are this application's, so the account that
      // / runs conversions is let through them whatever made them. A folder above them is not, & is
      // / said below if it stops that account.
      foreach (array(dirname($buildsFolder), $buildsFolder) as $folderPath) if (((int)@fileperms($folderPath) & 07777) !== 0755) @chmod($folderPath, 0755);
      $lockHandle = holdOwnBuildLock();
      $stagingFolder = $buildsFolder.DIRECTORY_SEPARATOR.'.'.basename($finalFolder).'-'.bin2hex(random_bytes(6));
      if ($lockHandle === FALSE) $BuildDetail = $buildsFolder.' could not be locked, so nothing was built into it.';
      else if (!@mkdir($stagingFolder, 0700)) $BuildDetail = $stagingFolder.' could not be made.';
      else {
        print('  '.str_pad((string)$dependencyEntry['Name'], 20).'building '.engineApplicationName().'\'s own'.($pinnedVersion !== '' ? ' '.$pinnedVersion : '').' from source. Reason: '.$buildReason.' '.((in_array((string)$HostArchitecture, array('arm64', 'arm'), TRUE)) ? 'An hour or more on this machine' : 'A few minutes').'. A status line follows every minute.'.$Lol);
        logEntry('Building '.engineApplicationName().'\'s own '.(string)$dependencyEntry['Name'].', because '.$buildReason);
        $environmentExports = 'export DEPENDENCY_PREFIX='.escapeshellarg($stagingFolder).'; export DEPENDENCY_VERSION='.escapeshellarg($pinnedVersion).'; export DEPENDENCY_SHA256='.escapeshellarg($pinnedChecksum).'; ';
        list ($commandStarted, $commandOutput, $commandExitCode) = runInstallCommand(dependencyEnvironmentPrefix().$environmentExports.'('.(string)$dependencyEntry['Fallback']['BuildCommand'].')', (string)$dependencyEntry['Name']);
        $stagedBinary = $stagingFolder.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.(string)$dependencyEntry['Binary'];
        if (!$commandStarted) $BuildDetail = 'The build could not be started.';
        else if ($commandExitCode !== 0) $BuildDetail = 'The build exited with code '.$commandExitCode.'. '.trim(implode(' ', array_slice($commandOutput, -3)));
        else {
          list ($stagedIsTrusted, $stagedNotice) = ownBuildIsTrusted($stagingFolder, $stagedBinary);
          if ($stagedIsTrusted) list ($stagedIsPresent, $stagedVersion, $stagedStatus, $stagedOutput) = resolveDependencyState(ownBuildEntry($dependencyEntry, $stagingFolder));
          if (!$stagedIsTrusted) $BuildDetail = 'The build put no program root alone can change at '.$stagedBinary.'. '.$stagedNotice;
          else if ($stagedStatus !== 'ok') $BuildDetail = 'What it built is '.$stagedStatus.($stagedVersion !== '' ? ' at '.$stagedVersion : '').', & this release needs '.dependencyWindowText($dependencyEntry).'.';
          else if ($pinnedVersion !== '' && !(compareVersionMinimum($stagedVersion, $pinnedVersion) && compareVersionMinimum($pinnedVersion, $stagedVersion))) $BuildDetail = 'It built '.$stagedVersion.' & the Fallback pins '.$pinnedVersion.'.';
          else {
            // / The build is opened to every account only now, & then renamed into place. A build
            // / already in service is renamed aside first & removed once the new one is in.
            @chmod($stagingFolder, 0755);
            clearstatcache(TRUE, $finalFolder);
            $copyExisted = (file_exists($finalFolder) or is_link($finalFolder));
            if ($copyExisted) {
              $retiredFolder = $buildsFolder.DIRECTORY_SEPARATOR.'.'.basename($finalFolder).'-retired-'.bin2hex(random_bytes(6));
              if (!@rename($finalFolder, $retiredFolder)) $retiredFolder = ''; }
            if ($copyExisted && $retiredFolder === '') $BuildDetail = $finalFolder.' could not be moved aside, so the new build was not put in service.';
            else if (!@rename($stagingFolder, $finalFolder)) {
              $BuildDetail = $finalFolder.' could not be written, so the new build was not put in service.';
              if ($retiredFolder !== '' && @rename($retiredFolder, $finalFolder)) $retiredFolder = ''; }
            else {
              $copyIsInService = TRUE;
              $BuildStatus = ($copyExisted ? 'updated' : 'installed');
              $BuildDetail = $finalFolder.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.(string)$dependencyEntry['Binary'].', '.(string)$dependencyEntry['Name'].' '.$stagedVersion.'.';
              logEntry(engineApplicationName().'\'s own build of '.(string)$dependencyEntry['Name'].' was '.$BuildStatus.' at '.$finalFolder.', version '.$stagedVersion.'.'); }
            if ($copyIsInService && $retiredFolder !== '' && !removeOwnBuildTree($retiredFolder)) warningEntry($retiredFolder.', a build taken out of service, could not be removed.');
            else if (!$copyIsInService && $retiredFolder !== '') warningEntry($finalFolder.' could not be put back, & the build that was in service is kept at '.$retiredFolder.'.'); } }
        if (!$copyIsInService && !removeOwnBuildTree($stagingFolder)) warningEntry($stagingFolder.', a build that was not put in service, could not be removed.'); }
      $lockIsReleased = releaseOwnBuildLock($lockHandle); }
    // / What is in service is no use to a conversion until the account that runs one can run it, which
    // / root's own checks cannot see, so it is said with the build's result.
    if ($copyIsInService) {
      list ($copyIsRunnable, $runAccount) = ownBuildIsRunnable($finalFolder.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.(string)$dependencyEntry['Binary']);
      $BuildIsReady = $copyIsRunnable;
      if (!$copyIsRunnable) $BuildDetail = $BuildDetail.' '.$runAccount.', which runs conversions, cannot run it. Every folder above it must let that account through, & its filesystem must run programs.'; } }
  if (!$BuildIsReady) warningEntry(engineApplicationName().'\'s own build of '.(string)$dependencyEntry['Name'].' '.($copyIsInService ? 'is in service & not ready' : 'FAILED').'. '.$BuildDetail);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $finalFolder, $buildsFolder, $folderPath, $resolvedFolder, $resolvedParent, $trustNotice, $stagingFolder, $retiredFolder, $runAccount, $pinnedVersion, $pinnedChecksum, $environmentExports, $stagedBinary, $stagedNotice, $stagedVersion, $stagedStatus, $stagedOutput, $folderIsTrusted, $parentIsTrusted, $commandStarted, $stagedIsTrusted, $stagedIsPresent, $copyExisted, $copyIsInService, $copyIsRunnable, $lockIsReleased, $lockHandle, $commandOutput, $commandExitCode, $dependencyEntry, $buildReason);
  return array($BuildIsReady, $BuildStatus, $BuildDetail); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to take this application's own build of a dependency out of service & remove it.
// / Accepts a manifest entry. Returns whether none is left in service & a sentence, in that order.
// / The sentence is empty where there was nothing to take away.
// / It is renamed aside first, so a conversion starting meanwhile finds the package manager's copy &
// / never part of this one, & what was renamed is removed afterwards. Nothing is done beneath a
// / builds folder another account could change, & everything is done under holdOwnBuildLock().
function detachOwnCopy($dependencyEntry) {
  // / Set variables.
  global $RunningAsRoot, $EnableMemoryProtection;
  $CopyIsDetached = TRUE;
  $DetachDetail = '';
  $finalFolder = $retiredFolder = $resolvedFolder = $trustNotice = '';
  $folderIsTrusted = $lockIsReleased = FALSE;
  $lockHandle = FALSE;
  $finalFolder = ownBuildFolder($dependencyEntry);
  if ($finalFolder !== '') clearstatcache(TRUE, $finalFolder);
  if ($finalFolder !== '' && (file_exists($finalFolder) or is_link($finalFolder))) {
    $CopyIsDetached = FALSE;
    list ($folderIsTrusted, $resolvedFolder, $trustNotice) = pathIsTrustedByRoot(ownBuildsRoot());
    if (!$RunningAsRoot) $DetachDetail = 'Only root takes '.$finalFolder.' away.';
    else if (!$folderIsTrusted or $resolvedFolder !== ownBuildsRoot()) $DetachDetail = ownBuildsRoot().' is not a folder root alone can change, so '.$finalFolder.' was left where it is. '.$trustNotice;
    else {
      $lockHandle = holdOwnBuildLock();
      $retiredFolder = ownBuildsRoot().DIRECTORY_SEPARATOR.'.'.basename($finalFolder).'-retired-'.bin2hex(random_bytes(6));
      if ($lockHandle === FALSE) $DetachDetail = ownBuildsRoot().' could not be locked, so '.$finalFolder.' is still in service.';
      else if (!@rename($finalFolder, $retiredFolder)) $DetachDetail = $finalFolder.' could not be moved aside, so it is still in service.';
      else {
        $CopyIsDetached = TRUE;
        $DetachDetail = $finalFolder.' was taken away.';
        logEntry(engineApplicationName().'\'s own build of '.(string)$dependencyEntry['Name'].' was taken out of service from '.$finalFolder.'.');
        if (!removeOwnBuildTree($retiredFolder)) warningEntry($retiredFolder.', a build taken out of service, could not be removed.'); }
      $lockIsReleased = releaseOwnBuildLock($lockHandle); }
    if (!$CopyIsDetached) warningEntry(engineApplicationName().'\'s own build of '.(string)$dependencyEntry['Name'].' could not be taken away. '.$DetachDetail); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $finalFolder, $retiredFolder, $resolvedFolder, $trustNotice, $folderIsTrusted, $lockIsReleased, $lockHandle, $dependencyEntry);
  return array($CopyIsDetached, $DetachDetail); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to take away whatever is in the builds folder & is not a build the manifest declares.
// / Accepts the whole manifest. Returns a sanity boolean & the number taken away, in that order. The
// / boolean is TRUE when nothing is left that should not be.
// / A run that stops part way leaves its work behind. A build interrupted while it runs leaves the
// / folder it was building in, & one interrupted between its two renames leaves the build it took out
// / of service. An entry an update renamed or took out of the manifest leaves its build. Each is taken
// / away under holdOwnBuildLock(), so nothing another run is building is touched. Only a folder named
// / for an entry declaring a Fallback this host can build is kept, whatever filter the command was
// / given, & each one taken away is printed. Whether a kept build should stay is settleOwnBuild()'s
// / question & not this one's.
function sweepOwnBuilds($dependsManifest) {
  // / Set variables.
  global $RunningAsRoot, $HostArchitecture, $Lol, $EnableMemoryProtection;
  $SweepIsClean = TRUE;
  $ItemsRemoved = 0;
  $keptNames = $folderEntries = $dependencyEntry = $resolvedEntry = array();
  $folderEntry = $resolvedFolder = $trustNotice = $unsupportedReason = '';
  $folderIsTrusted = $architectureIsSupported = $lockIsReleased = FALSE;
  $lockHandle = FALSE;
  clearstatcache(TRUE, ownBuildsRoot());
  if ($RunningAsRoot && (is_dir(ownBuildsRoot()) or is_link(ownBuildsRoot()))) {
    list ($folderIsTrusted, $resolvedFolder, $trustNotice) = pathIsTrustedByRoot(ownBuildsRoot());
    if (!$folderIsTrusted or $resolvedFolder !== ownBuildsRoot()) {
      $SweepIsClean = FALSE;
      warningEntry(ownBuildsRoot().' is not a folder root alone can change, so nothing left in it was taken away. '.$trustNotice); }
    else {
      foreach ((array)$dependsManifest as $dependencyEntry) {
        if (!is_array($dependencyEntry)) continue;
        list ($resolvedEntry, $architectureIsSupported, $unsupportedReason) = applyArchitectureRules($dependencyEntry, (string)$HostArchitecture);
        if ($architectureIsSupported && ownBuildIsDeclared($resolvedEntry)) $keptNames[basename(ownBuildFolder($resolvedEntry))] = TRUE; }
      $lockHandle = holdOwnBuildLock();
      if ($lockHandle === FALSE) $SweepIsClean = FALSE;
      else {
        $folderEntries = @scandir(ownBuildsRoot());
        foreach ((is_array($folderEntries) ? $folderEntries : array()) as $folderEntry) {
          if ($folderEntry === '.' or $folderEntry === '..' or $folderEntry === '.lock' or isset($keptNames[$folderEntry])) continue;
          if (removeOwnBuildTree(ownBuildsRoot().DIRECTORY_SEPARATOR.$folderEntry)) {
            $ItemsRemoved++;
            print('  '.str_pad('Own builds', 20).'took away '.$folderEntry.', which no build in service owns.'.$Lol); }
          else {
            $SweepIsClean = FALSE;
            warningEntry(ownBuildsRoot().DIRECTORY_SEPARATOR.$folderEntry.' belongs to no build in service & could not be taken away.'); } }
        $lockIsReleased = releaseOwnBuildLock($lockHandle); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $keptNames, $folderEntries, $dependencyEntry, $resolvedEntry, $folderEntry, $resolvedFolder, $trustNotice, $unsupportedReason, $folderIsTrusted, $architectureIsSupported, $lockIsReleased, $lockHandle, $dependsManifest);
  return array($SweepIsClean, $ItemsRemoved); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether a dependency needs this application's own build, & to make that so.
// / Accepts a manifest entry. Returns a sanity boolean, a status word & a sentence, in that order.
// / The boolean is TRUE when nothing is left to do, which is an entry answering inside its window,
// / from the package manager's copy or from the own build, or an entry declaring no Fallback with no
// / own build left behind. The status is 'none' where there was nothing to do, 'removed' where an own
// / build was taken away, 'unchanged' where the own build in service answers & is the version
// / pinned, & otherwise 'installed', 'updated' or 'FAILED'. Each but 'none' & 'unchanged' is printed.
// / It runs only when dependencies are installed or updated, always after the package manager has.
// / The package manager's copy is asked first, & one answering inside the window is used & any own build is taken away.
// / A host whose package manager has caught up therefore goes back to the copy it keeps current.
// / Otherwise the own build is built, or built again.
// / A rebuild follows when it is missing, not root's alone, outside the window, older than pinned, or not runnable by the conversion account.
// / An own build an entry no longer declares is taken away, so dropping a Fallback detaches it everywhere.
// / A Fallback this release cannot use is warned about, because nothing else would say why it was never built.
function settleOwnBuild($dependencyEntry) {
  // / Set variables.
  global $HostArchitecture, $Lol, $EnableMemoryProtection;
  $BuildIsSettled = FALSE;
  $SettleStatus = 'none';
  $SettleDetail = '';
  $resolvedEntry = array();
  $architectureIsSupported = $systemIsPresent = $ownIsTrusted = $ownIsPresent = $ownIsRunnable = FALSE;
  $unsupportedReason = $systemVersion = $systemStatus = $systemOutput = $detachDetail = $ownFolder = $ownPath = $ownNotice = $ownVersion = $ownStatus = $ownOutput = $pinnedVersion = $buildReason = $runAccount = '';
  list ($resolvedEntry, $architectureIsSupported, $unsupportedReason) = applyArchitectureRules($dependencyEntry, (string)$HostArchitecture);
  if ($architectureIsSupported && isset($resolvedEntry['Fallback']) && !ownBuildIsDeclared($resolvedEntry)) warningEntry((string)$resolvedEntry['Name'].' declares a Fallback this release cannot use, so no own build is made. Only an apt entry naming its program by a plain name, with a VersionCommand that runs that program, may have one, & its Fallback must carry a BuildCommand.');
  if (!$architectureIsSupported or !ownBuildIsDeclared($resolvedEntry)) {
    list ($BuildIsSettled, $detachDetail) = detachOwnCopy($resolvedEntry);
    if ($detachDetail !== '') {
      $SettleStatus = ($BuildIsSettled ? 'removed' : 'FAILED');
      $SettleDetail = (!$BuildIsSettled ? $detachDetail : 'its manifest entry declares no Fallback here, so '.$detachDetail); } }
  else {
    list ($systemIsPresent, $systemVersion, $systemStatus, $systemOutput) = resolveDependencyState($resolvedEntry);
    if ($systemStatus === 'ok') {
      list ($BuildIsSettled, $detachDetail) = detachOwnCopy($resolvedEntry);
      // / The package manager's copy answers whether or not an own build could be taken away, &
      // / resolveDependencyChoice() warns while one is still there.
      $BuildIsSettled = TRUE;
      if ($detachDetail !== '') {
        $SettleStatus = 'removed';
        $SettleDetail = 'the package manager\'s '.$systemVersion.' is inside the window, so '.$detachDetail; } }
    else {
      $ownFolder = ownBuildFolder($resolvedEntry);
      $ownPath = $ownFolder.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.(string)$resolvedEntry['Binary'];
      $pinnedVersion = isset($resolvedEntry['Fallback']['Version']) ? trim((string)$resolvedEntry['Fallback']['Version']) : '';
      list ($ownIsTrusted, $ownNotice) = ownBuildIsTrusted($ownFolder, $ownPath);
      if ($ownIsTrusted) list ($ownIsPresent, $ownVersion, $ownStatus, $ownOutput) = resolveDependencyState(ownBuildEntry($resolvedEntry, $ownFolder));
      if ($ownIsTrusted && $ownStatus === 'ok') list ($ownIsRunnable, $runAccount) = ownBuildIsRunnable($ownPath);
      if ($ownIsTrusted && $ownStatus === 'ok' && $ownIsRunnable && ($pinnedVersion === '' or compareVersionMinimum($ownVersion, $pinnedVersion))) {
        $BuildIsSettled = TRUE;
        $SettleStatus = 'unchanged';
        $SettleDetail = $ownPath.', '.(string)$resolvedEntry['Name'].' '.$ownVersion.'.'; }
      else {
        if ($systemStatus === 'absent') $buildReason = 'the package manager installed none';
        else $buildReason = 'the package manager\'s is '.($systemVersion !== '' ? $systemVersion : $systemStatus);
        $buildReason = $buildReason.', & this release needs '.dependencyWindowText($resolvedEntry).'.';
        list ($BuildIsSettled, $SettleStatus, $SettleDetail) = buildOwnCopy($resolvedEntry, $buildReason); } } }
  if ($SettleStatus !== 'none' && $SettleStatus !== 'unchanged') print('  '.str_pad((string)$resolvedEntry['Name'].' own build', 20).$SettleStatus.', '.$SettleDetail.$Lol);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $resolvedEntry, $architectureIsSupported, $systemIsPresent, $ownIsTrusted, $ownIsPresent, $ownIsRunnable, $unsupportedReason, $systemVersion, $systemStatus, $systemOutput, $detachDetail, $ownFolder, $ownPath, $ownNotice, $ownVersion, $ownStatus, $ownOutput, $pinnedVersion, $buildReason, $runAccount, $dependencyEntry);
  return array($BuildIsSettled, $SettleStatus, $SettleDetail); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether a package manager's install gave a dependency this release accepts.
// / Accepts the entry, whether the package manager reported success & what it printed, in that order.
// / Returns whether the entry now answers inside its window & what to report, in that order.
// / A package manager reports success for whatever version it has, as Ubuntu 24.04 does for 7-Zip 23.01.
// / So an install is believed once the entry answers inside its window.
// / An entry declaring a Fallback is believed once settleOwnBuild() has settled it, whether or not the package manager succeeded.
// / settleOwnBuild() builds this application's own copy where the package manager's falls short, because falling back comes before failing.
function believePackageInstall($dependencyEntry, $packageWasInstalled, $packageOutput) {
  // / Set variables.
  global $HostArchitecture, $EnableMemoryProtection;
  $InstallIsBelieved = FALSE;
  $ReportText = (string)$packageOutput;
  $resolvedEntry = array();
  $architectureIsSupported = $fallbackIsDeclared = $buildIsSettled = $dependencyIsPresent = FALSE;
  $unsupportedReason = $settleStatus = $settleDetail = $detectedVersion = $dependencyStatus = $rawOutput = '';
  list ($resolvedEntry, $architectureIsSupported, $unsupportedReason) = applyArchitectureRules($dependencyEntry, (string)$HostArchitecture);
  $fallbackIsDeclared = ($architectureIsSupported && ownBuildIsDeclared($resolvedEntry));
  list ($buildIsSettled, $settleStatus, $settleDetail) = settleOwnBuild($dependencyEntry);
  if ($settleDetail !== '') $ReportText = trim($ReportText.PHP_EOL.$settleDetail);
  if ($fallbackIsDeclared) $InstallIsBelieved = $buildIsSettled;
  else if ($packageWasInstalled) {
    list ($dependencyIsPresent, $detectedVersion, $dependencyStatus, $rawOutput) = resolveDependencyState($dependencyEntry);
    $InstallIsBelieved = ($dependencyStatus === 'ok' or $dependencyStatus === 'unknown-version');
    if (!$InstallIsBelieved) $ReportText = trim($ReportText.PHP_EOL.'The package manager reported success, & '.(string)$dependencyEntry['Name'].' is '.$dependencyStatus.($detectedVersion !== '' ? ' at '.$detectedVersion : '').'. This release needs '.dependencyWindowText($resolvedEntry).'.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $resolvedEntry, $architectureIsSupported, $fallbackIsDeclared, $buildIsSettled, $dependencyIsPresent, $unsupportedReason, $settleStatus, $settleDetail, $detectedVersion, $dependencyStatus, $rawOutput, $dependencyEntry, $packageWasInstalled, $packageOutput);
  return array($InstallIsBelieved, $ReportText); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report the state of every dependency.
// / Accepts a subsystem filter, which may be empty for all of them.
// / Returns a readiness boolean, an array of findings & the count of optional problems,
// / in that order.
// / Readiness is FALSE only when something marked Required is missing or too old. An
// / optional dependency that is absent downgrades a subsystem & does not fail the check.
// / This changes nothing & needs no authorization. It is safe to run from anywhere.
function checkDepends($subsystemFilter) {
  // / Set variables.
  global $RequiredDependsVersion, $Verbose, $HostArchitecture, $EnableMemoryProtection;
  $DependenciesAreReady = TRUE;
  $DependencyFindings = array();
  $OptionalProblems = 0;
  $finding = array();
  $dependsManifest = $dependencyEntry = $missingRequirements = $resolvedEntry = array();
  $manifestIsAvailable = $dependencyIsPresent = $architectureIsSupported = $systemIsPresent = FALSE;
  $detectedVersion = $dependencyStatus = $detectedDependsVersion = $requirementName = $rawOutput = $binaryPath = $binaryOrigin = $unsupportedReason = $systemVersion = $systemStatus = $systemOutput = '';
  $presenceMap = $stateMap = array();
  list ($manifestIsAvailable, $dependsManifest, $detectedDependsVersion) = verifyDependsManifest($RequiredDependsVersion);
  if (!$manifestIsAvailable) $DependenciesAreReady = FALSE;
  else {
    // / Presence is resolved for the WHOLE manifest before anything is filtered or reported.
    // / A requirement usually lives in a different subsystem from the thing that needs it.
    // / Bubblewrap sits in Sandbox & nine subsystems require it, so a map built only from
    // / the filtered entries left every one of those requirements unknown. The isset test
    // / below then read unknown as satisfied & the report claimed a subsystem was ready
    // / without ever having looked at what it depends on.
    // / Each entry is read from this application's own build where it has one, so the report names the
    // / program a conversion runs, & says whether the entry can have one. An own build in service is
    // / asked first wherever it is used, so the package manager's copy is asked here as well, & the
    // / report says when that one has caught up, because nothing else would.
    foreach ($dependsManifest as $dependencyEntry) {
      list ($dependencyIsPresent, $detectedVersion, $dependencyStatus, $rawOutput, $binaryPath, $binaryOrigin) = resolveDependencyChoice($dependencyEntry);
      list ($resolvedEntry, $architectureIsSupported, $unsupportedReason) = applyArchitectureRules($dependencyEntry, (string)$HostArchitecture);
      $systemVersion = $systemStatus = '';
      if ($binaryOrigin === 'own' && $dependencyStatus === 'ok') list ($systemIsPresent, $systemVersion, $systemStatus, $systemOutput) = resolveDependencyState($dependencyEntry);
      $presenceMap[(string)$dependencyEntry['Name']] = ($dependencyStatus === 'ok' or $dependencyStatus === 'unknown-version');
      $stateMap[(string)$dependencyEntry['Name']] = array($detectedVersion, $dependencyStatus, $rawOutput, $binaryPath, $binaryOrigin, ($architectureIsSupported && ownBuildIsDeclared($resolvedEntry)), ($systemStatus === 'ok' ? $systemVersion : '')); }
    foreach ($dependsManifest as $dependencyEntry) {
      if (trim((string)$subsystemFilter) !== '' && stripos((string)$dependencyEntry['Subsystem'], trim((string)$subsystemFilter)) === FALSE) continue;
      $detectedVersion = $stateMap[(string)$dependencyEntry['Name']][0];
      $dependencyStatus = $stateMap[(string)$dependencyEntry['Name']][1];
      $rawOutput = $stateMap[(string)$dependencyEntry['Name']][2];
      // / A dependency whose own requirements are absent is reported against them rather
      // / than against itself, because installing it first would fail anyway.
      // / A requirement the manifest does not name at all is reported too, because a typo
      // / in Requires is otherwise indistinguishable from a satisfied requirement.
      $missingRequirements = array();
      foreach ((array)$dependencyEntry['Requires'] as $requirementName) {
        if (!isset($presenceMap[$requirementName]) or $presenceMap[$requirementName] !== TRUE) $missingRequirements[] = $requirementName; }
      // / A REQUIRED dependency that this architecture cannot run is a problem, not a note.
      // / An optional one is neither. It was never going to work here & saying so once is
      // / the whole of what an operator needs.
      // / Nothing shipped is both required & architecture limited today. The rule is here
      // / because the alternative is an installation that reports itself ready while a tool
      // / it cannot run without was quietly skipped.
      if ($dependencyEntry['Required'] && in_array($dependencyStatus, array('absent', 'outdated', 'unsupported', 'too-new', 'misconfigured', 'refused'), TRUE)) $DependenciesAreReady = FALSE;
      $DependencyFindings[] = array(
        'Name' => (string)$dependencyEntry['Name'],
        'Status' => $dependencyStatus,
        // / n/a means nothing was asked for. unknown means it was asked for & not answered.
        'Detected' => ($detectedVersion !== '' ? $detectedVersion : (((string)$dependencyEntry['VersionCommand'] === '' or (string)$dependencyEntry['VersionPattern'] === '') ? 'n/a' : 'unknown')),
        'Minimum' => ((string)$dependencyEntry['MinimumVersion'] === '' ? 'any' : (string)$dependencyEntry['MinimumVersion']),
        'Required' => (bool)$dependencyEntry['Required'],
        'Subsystem' => (string)$dependencyEntry['Subsystem'],
        'Type' => (string)$dependencyEntry['Type'],
        'Package' => (string)$dependencyEntry['Package'],
        'Missing' => $missingRequirements,
        'Output' => $rawOutput,
        'Path' => $stateMap[(string)$dependencyEntry['Name']][3],
        'Origin' => $stateMap[(string)$dependencyEntry['Name']][4],
        'Fallback' => $stateMap[(string)$dependencyEntry['Name']][5],
        'SystemVersion' => $stateMap[(string)$dependencyEntry['Name']][6]); } }
  // / Count what is wrong but optional, so the summary can say so. A report that says
  // / every requirement is met, printed directly beneath two failures, invites an operator
  // / to believe the failures do not matter without ever saying that they do not.
  foreach ($DependencyFindings as $finding) {
    if (!$finding['Required'] && (in_array($finding['Status'], array('absent', 'outdated', 'too-new', 'misconfigured', 'refused'), TRUE))) $OptionalProblems++; }
  if ($Verbose) logEntry('Dependency check completed across '.count($DependencyFindings).' dependenc(ies). Ready: '.($DependenciesAreReady ? 'YES' : 'NO').'. Optional problems: '.$OptionalProblems.'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dependsManifest, $dependencyEntry, $missingRequirements, $resolvedEntry, $manifestIsAvailable, $dependencyIsPresent, $architectureIsSupported, $systemIsPresent, $detectedVersion, $dependencyStatus, $detectedDependsVersion, $requirementName, $rawOutput, $binaryPath, $binaryOrigin, $unsupportedReason, $systemVersion, $systemStatus, $systemOutput, $presenceMap, $stateMap, $finding, $subsystemFilter);
  return array($DependenciesAreReady, $DependencyFindings, $OptionalProblems); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to print a dependency report to the console.
// / Accepts the findings array.
// / Returns the number of lines printed.
function showDependencyFindings($dependencyFindings) {
  // / Set variables.
  global $Lol, $EnableMemoryProtection;
  $LinesPrinted = 0;
  $finding = array();
  $requiredProblems = $optionalProblems = 0;
  // / Required is a column of its own. Appending it to the status overflowed the pad &
  // / ran one column into the next, which is what produced output like
  // / 'unknown-version (optional)unknown'.
  print($Lol.str_pad('DEPENDENCY', 20).str_pad('STATUS', 17).str_pad('NEEDED', 10).str_pad('FOUND', 12).str_pad('MINIMUM', 12).'SUBSYSTEM'.$Lol);
  foreach ($dependencyFindings as $finding) {
    print(str_pad($finding['Name'], 20)
      .str_pad($finding['Status'], 17)
      .str_pad($finding['Required'] ? 'required' : 'optional', 10)
      .str_pad($finding['Detected'], 12)
      .str_pad($finding['Minimum'], 12)
      .$finding['Subsystem'].$Lol);
    if (!empty($finding['Missing'])) print(str_pad('', 20).'waiting on '.implode(', ', $finding['Missing']).$Lol);
    // / A version command that ran & produced something the pattern did not match. Showing
    // / what it actually printed is the only way to correct the pattern in depends.php.
    if (($finding['Status'] === 'unknown-version' or $finding['Status'] === 'absent') && trim((string)$finding['Output']) !== '') print(str_pad('', 20).'reported: '.substr(trim((string)$finding['Output']), 0, 90).$Lol);
    // / A reason this application gives itself is printed whole, because the end of it, which says
    // / what is wrong, is the part an operator acts on. Only a tool's own output is cut short.
    else if (($finding['Status'] === 'misconfigured' or $finding['Status'] === 'refused') && trim((string)$finding['Output']) !== '') print(str_pad('', 20).'reported: '.trim((string)$finding['Output']).$Lol);
    // / A program this application built for itself is named with where it is, so it is never taken for
    // / the package manager's, which the host keeps current. One the package manager has caught up with
    // / says what takes it away. An entry that can have one & is not ready says what makes one.
    if (isset($finding['Origin']) && $finding['Origin'] === 'own') print(str_pad('', 20).engineApplicationName().'\'s own build, '.(string)$finding['Path'].(!empty($finding['SystemVersion']) ? '. The package manager\'s '.(string)$finding['SystemVersion'].' qualifies too. --setup --update-depends, run as root, removes the own build.' : '').$Lol);
    else if (!empty($finding['Fallback']) && in_array($finding['Status'], array('absent', 'outdated', 'too-new', 'unknown-version', 'misconfigured'), TRUE)) print(str_pad('', 20).'--setup --install-depends, run as root, builds '.engineApplicationName().'\'s own'.$Lol);
    // / A problem is anything that is not ok & not deliberately unverifiable.
    if ($finding['Status'] !== 'ok' && $finding['Status'] !== 'unverifiable') {
      if ($finding['Required']) $requiredProblems++;
      else $optionalProblems++; }
    $LinesPrinted++; }
  // / Report both counts, because saying only that every required dependency is fine contradicts two broken optional ones.
  // / An empty table is not a clean bill of health, because it means nothing was checked.
  if ($LinesPrinted === 0) print($Lol.'No dependency was checked. Either no entry matches the subsystem named, or the manifest at '.contractPath('depends.php').' could not be loaded. Run -v to see which.'.$Lol);
  else print($Lol.($requiredProblems === 0 ? 'Every required dependency is present & current.' : $requiredProblems.' REQUIRED dependenc(ies) are missing or too old. Their subsystems refuse.').$Lol);
  if ($optionalProblems > 0) print($optionalProblems.' optional dependenc(ies) are missing or unverified. Their subsystems are unavailable.'.$Lol);
  print($Lol);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $optionalProblems, $requiredProblems, $finding, $dependencyFindings);
  return $LinesPrinted; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to describe a length of time in its two largest units.
// / Accepts a number of seconds. Returns text such as 3h 10m, 4m 30s or 45s.
function describeElapsedTime($elapsedSeconds) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ElapsedText = '';
  $wholeSeconds = max(0, (int)$elapsedSeconds);
  if ($wholeSeconds >= 3600) $ElapsedText = intdiv($wholeSeconds, 3600).'h '.intdiv($wholeSeconds % 3600, 60).'m';
  else if ($wholeSeconds >= 60) $ElapsedText = intdiv($wholeSeconds, 60).'m '.($wholeSeconds % 60).'s';
  else $ElapsedText = $wholeSeconds.'s';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $elapsedSeconds, $wholeSeconds);
  return $ElapsedText; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run a long install command while showing the operator that it is alive.
// / Accepts the command text & the name to show.
// / Returns whether the command started, its output lines & its exit code.
// / An install can run for hours on a slow connection, so a status line prints every minute with the elapsed time & the last output.
// / Standard input comes from /dev/null, so a command that asks a question gets an immediate
// / end of input & fails, rather than waiting invisibly for an answer that cannot come.
// / pip is told never to ask, to skip its own version check, & to resume a download that
// / drops. A pip too old to resume ignores that setting, as tested against pip 24.0.
function runInstallCommand($commandText, $displayName) {
  // / Set variables.
  global $Lol, $EnableMemoryProtection;
  $CommandStarted = FALSE;
  $CommandOutput = array();
  $CommandExitCode = -1;
  $commandPipes = $carriageParts = array();
  $commandProcess = $selectResult = FALSE;
  $pendingText = $lastLine = $completeLine = '';
  $newlineAt = 0;
  $startedAt = $lastBeatAt = microtime(TRUE);
  $readableStreams = $writableStreams = $exceptionalStreams = NULL;
  $commandProcess = proc_open('export PIP_NO_INPUT=1 PIP_DISABLE_PIP_VERSION_CHECK=1 PIP_RESUME_RETRIES=10; ('.$commandText.') 2>&1',
    array(0 => array('file', '/dev/null', 'r'), 1 => array('pipe', 'w')), $commandPipes);
  if (!is_resource($commandProcess)) warningEntry('Could not start the install command for '.$displayName.'.');
  else {
    $CommandStarted = TRUE;
    stream_set_blocking($commandPipes[1], FALSE);
    while (!feof($commandPipes[1])) {
      $readableStreams = array($commandPipes[1]);
      $writableStreams = $exceptionalStreams = NULL;
      $selectResult = @stream_select($readableStreams, $writableStreams, $exceptionalStreams, 1);
      if ($selectResult === FALSE) usleep(250000);
      else if ($selectResult > 0) $pendingText .= (string)fread($commandPipes[1], 65536);
      while (($newlineAt = strpos($pendingText, "\n")) !== FALSE) {
        $completeLine = rtrim(substr($pendingText, 0, $newlineAt), "\r");
        $pendingText = (string)substr($pendingText, $newlineAt + 1);
        $CommandOutput[] = $completeLine;
        $carriageParts = explode("\r", $completeLine);
        if (trim((string)end($carriageParts)) !== '') $lastLine = trim((string)end($carriageParts)); }
      if (microtime(TRUE) - $lastBeatAt >= 60) {
        $lastBeatAt = microtime(TRUE);
        print('  '.str_pad($displayName, 20).'still working, '.describeElapsedTime($lastBeatAt - $startedAt).'. '.(($lastLine !== '') ? substr($lastLine, 0, 64) : 'Nothing printed yet.').$Lol); } }
    if (trim($pendingText) !== '') $CommandOutput[] = rtrim($pendingText, "\r");
    fclose($commandPipes[1]);
    $CommandExitCode = proc_close($commandProcess); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $commandText, $displayName, $commandPipes, $carriageParts, $commandProcess, $selectResult, $pendingText, $lastLine, $completeLine, $newlineAt, $startedAt, $lastBeatAt, $readableStreams, $writableStreams, $exceptionalStreams);
  return array($CommandStarted, $CommandOutput, $CommandExitCode); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to install one dependency.
// / Accepts one manifest entry & the package manager in use, in that order.
// / Returns a success boolean & whatever the package manager printed, in that order.
// / A dependency of Type manual is NEVER installed. Its licence forbids redistribution or
// / it needs a decision this utility is not entitled to make. It is reported instead.
// / A dependency of Type source runs the build command its entry carries.
// / A package manager's install is believed once the entry answers inside its window, & an entry
// / declaring a Fallback builds this application's own copy where the package manager's does not.
function installOneDependency($dependencyEntry, $packageManager) {
  // / Set variables.
  // / $Lol is declared because a source build reports its progress. A build takes minutes
  // / & an operator watching a silent terminal has no way to tell it apart from a hang.
  global $Lol, $EnableMemoryProtection, $HostArchitecture, $EngineAllowIncompleteDepsUpdate;
  $chosenVersion = $choiceReason = $chosenVersionPrefix = '';
  $InstallSucceeded = FALSE;
  // / The gate results are set before any branch. $entryIsBlocked was only ever set to TRUE,
  // / inside the two gates, so an entry that passed both reached the test below with it unset.
  $entryIsComplete = $entryIsLicenseClear = $entryIsBlocked = $commandStarted = $buildUsesPip = FALSE;
  $entryGateReason = '';
  $ReturnData = '';
  // / Using what an operator supplied & CHANGING it are different risks.
  // / An entry with no declared Source names nowhere to fetch from. Running an update
  // / against it means acting on a binary somebody deliberately placed, using whatever a
  // / package manager decides, & the operator finds out afterwards.
  // / So a blocked entry is SKIPPED rather than acted on. Nothing is removed & nothing is
  // / overwritten, & the reason is reported rather than logged quietly.
  // / --Engine Allow Incomplete Deps Update-- turns this off for an installation that
  // / would rather have the update than the certainty.
  list ($entryIsComplete, $entryIsLicenseClear, $entryGateReason) = judgeDependencyEntry($dependencyEntry);
  if (!$entryIsLicenseClear) {
    warningEntry('Refusing to install '.(string)$dependencyEntry['Name'].'. '.$entryGateReason);
    $ReturnData = 'Refused. '.$entryGateReason;
    $entryIsBlocked = TRUE; }
  else if (!$entryIsComplete && (!isset($EngineAllowIncompleteDepsUpdate) or $EngineAllowIncompleteDepsUpdate !== TRUE)) {
    warningEntry('Skipping '.(string)$dependencyEntry['Name'].'. '.$entryGateReason.' Nothing was changed.');
    $ReturnData = 'Skipped. '.$entryGateReason;
    $entryIsBlocked = TRUE; }
  // / A blocked entry does no work. Everything below reaches the return untouched.
  if (!$entryIsBlocked) {
  $installCommand = $packageList = '';
  $commandOutput = array();
  $commandExitCode = 1;
  $packageList = trim((string)$dependencyEntry['Package']);
  // / A bundled dependency ships inside the application & is a microservice this project
  // / distributes rather than a package this project installs.
  // / It is never installed, never updated & never removed by this component.
  // / It is represented so that --check-depends, -v & the supply chain audit can all see
  // / it, & so that anything requiring it can say so in its own Requires list.
  // / Reporting an install here would claim work that did not happen.
  if ((string)$dependencyEntry['Type'] === 'bundled') {
    // / A bundled dependency ships inside the application & is already present by
    // / definition. Nothing to install & nothing to fail.
    $InstallSucceeded = TRUE;
    $ReturnData = 'This dependency ships with '.engineApplicationName().' & needs no installation.'; }
  else if ((string)$dependencyEntry['Type'] === 'manual') $ReturnData = 'This dependency is never installed automatically. Install it by hand from '.(string)$dependencyEntry['Source'].'.';
  // / A source built dependency runs the command its manifest entry carries.
  // / A build takes minutes & prints a great deal, so only the tail of its output is kept, & the log records that it ran.
  // / A build with no command is a manifest that has not said how, & says so.
  else if ((string)$dependencyEntry['Type'] === 'source') {
    if (!isset($dependencyEntry['BuildCommand']) or trim((string)$dependencyEntry['BuildCommand']) === '') $ReturnData = 'This dependency is built from source & its manifest entry names no build command.';
    else {
      // / A source build on a small board is measured in HOURS rather than minutes.
      // / Saying several minutes on a Raspberry Pi is how an operator concludes it has hung
      // / & kills a build that was working. The number is not the point. Knowing the build
      // / is slow HERE, rather than broken, is the point.
      // / This reads the architecture rather than timing anything, because a guess made
      // / before the build starts is worth more than an accurate figure afterwards.
      $buildUsesPip = (bool)preg_match('/\bpip[\d.]*\s+install\b/', (string)$dependencyEntry['BuildCommand']);
      print('  '.str_pad((string)$dependencyEntry['Name'], 20).(($buildUsesPip) ? 'installing with pip. A slow connection makes this slow' : 'building from source. '.((in_array((string)$HostArchitecture, array('arm64', 'arm'), TRUE)) ? 'An hour or more on this machine' : 'Several minutes')).'. A status line follows every minute.'.$Lol);
      logEntry((($buildUsesPip) ? 'Installing '.(string)$dependencyEntry['Name'].' with pip.' : 'Building '.(string)$dependencyEntry['Name'].' from source.'));
      $commandOutput = array();
      // / A chosen version is handed to the build command as DEPENDENCY_VERSION.
      // / An environment variable rather than a substitution, because a build command is
      // / written by whoever added the entry & rewriting their text is a good way to break
      // / a quoting they got right.
      // / A build command that ignores the variable is unaffected, which is every entry
      // / that declares no version source.
      list ($chosenVersion, $choiceReason) = resolveTargetVersion($dependencyEntry);
      if ($chosenVersion !== '') {
        logEntry((string)$dependencyEntry['Name'].': '.$choiceReason);
        $chosenVersionPrefix = 'export DEPENDENCY_VERSION='.escapeshellarg($chosenVersion).'; '; }
      list ($commandStarted, $commandOutput, $commandExitCode) = runInstallCommand(dependencyEnvironmentPrefix().$chosenVersionPrefix.'('.(string)$dependencyEntry['BuildCommand'].')', (string)$dependencyEntry['Name']);
      $ReturnData = implode(PHP_EOL, array_slice($commandOutput, -12));
      if (!$commandStarted) $ReturnData = 'The build could not be started. The log says why.';
      else if ($commandExitCode !== 0) warningEntry('The source build for '.(string)$dependencyEntry['Name'].' exited with code '.$commandExitCode.'.');
      else {
        // / A build that reported success is still only believed once the thing it built
        // / answers for itself, exactly as a package install is.
        if ((string)$dependencyEntry['VersionCommand'] === '') $InstallSucceeded = TRUE;
        else {
          $commandOutput = array();
          exec(dependencyEnvironmentPrefix().'('.(string)$dependencyEntry['VersionCommand'].') < /dev/null 2>&1', $commandOutput, $commandExitCode);
          if ($commandExitCode === 0) $InstallSucceeded = TRUE;
          else $ReturnData = 'The build completed & the result does not answer. Check the tail of the build output in the log.'; } } } }
  else if ($packageList === '') $ReturnData = 'This dependency names no package to install.';
  // / A PHP extension is installed differently on a packaged PHP & on an official container
  // / image. Debian ships a php-NAME metapackage that resolves to the running version. The
  // / container image builds the extension instead & has no package manager entry for it.
  else if ((string)$dependencyEntry['Type'] === 'php-extension') {
    if (locateDependency('docker-php-ext-install') !== '') $installCommand = 'docker-php-ext-install '.escapeshellarg($packageList).' 2>&1';
    else if ($packageManager === '') $ReturnData = 'No package manager is available on this host.';
    else $installCommand = dependencyPackageCommand($packageManager, 'php-'.$packageList, 'install').' 2>&1';
    if ($installCommand !== '') {
      list ($commandStarted, $commandOutput, $commandExitCode) = runInstallCommand($installCommand, (string)$dependencyEntry['Name']);
      $ReturnData = implode(PHP_EOL, $commandOutput);
      // / An extension is only installed once PHP can see it. A package that unpacked but
      // / was never enabled reports success from the package manager & fails every use.
      // / An entry declaring no version command has nothing to ask, so the package manager
      // / is the whole of the evidence. Running an empty command returned a non zero code &
      // / reported an extension PHP could not load, which was advice for a problem that did
      // / not exist.
      if ($commandStarted && $commandExitCode === 0) {
        if ((string)$dependencyEntry['VersionCommand'] === '') $InstallSucceeded = TRUE;
        else {
          $commandOutput = array();
          exec(dependencyEnvironmentPrefix().'('.(string)$dependencyEntry['VersionCommand'].') < /dev/null 2>&1', $commandOutput, $commandExitCode);
          if ($commandExitCode === 0) $InstallSucceeded = TRUE;
          else $ReturnData = 'The package installed but PHP still cannot load the extension. The web server may need restarting.'; } } } }
  else if ((string)$dependencyEntry['Type'] === 'pip') {
    $installCommand = 'pip3 install --break-system-packages '.escapeshellarg($packageList).' 2>&1';
    list ($commandStarted, $commandOutput, $commandExitCode) = runInstallCommand($installCommand, (string)$dependencyEntry['Name']);
    $ReturnData = implode(PHP_EOL, $commandOutput);
    if ($commandStarted && $commandExitCode === 0) $InstallSucceeded = TRUE; }
  else if ($packageManager === '') $ReturnData = 'No package manager is available on this host.';
  else {
    // / Every package name is escaped separately. A manifest is not user input, but a
    // / manifest from an update is not this release's manifest either.
    $installCommand = dependencyInstallCommand($packageManager, $packageList).' 2>&1';
    list ($commandStarted, $commandOutput, $commandExitCode) = runInstallCommand($installCommand, (string)$dependencyEntry['Name']);
    $ReturnData = implode(PHP_EOL, $commandOutput);
    if ($commandStarted && $commandExitCode === 0) $InstallSucceeded = TRUE; }
  // / A package manager reports success for whatever version it has, so its install is believed once
  // / the entry answers inside its window. One declaring a Fallback is settled whether or not the
  // / package manager succeeded, because falling back comes before failing.
  if ((string)$dependencyEntry['Type'] === 'apt') list ($InstallSucceeded, $ReturnData) = believePackageInstall($dependencyEntry, $InstallSucceeded, $ReturnData);
  // / Closes the blocked check above.
  }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $commandStarted, $buildUsesPip, $entryIsComplete, $entryIsLicenseClear, $entryGateReason, $entryIsBlocked, $chosenVersion, $choiceReason, $chosenVersionPrefix, $installCommand, $packageList, $commandOutput, $commandExitCode, $dependencyEntry, $packageManager);
  return array($InstallSucceeded, $ReturnData); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to build the command that installs, updates or removes a package.
// / Accepts the package manager, the package list & the operation, in that order.
// / Returns the complete command, or an empty string for an unrecognized manager.
// / Every package name is escaped individually, so a space separated list stays a list of
// / arguments rather than becoming one argument or a second command.
function dependencyPackageCommand($packageManager, $packageList, $packageOperation) {
  // / Set variables.
  global $EnableMemoryProtection;
  $PackageCommand = '';
  $escapedPackages = $packageNames = array();
  $packageName = $operationFlags = '';
  $packageNames = preg_split('/\s+/', trim((string)$packageList));
  foreach ($packageNames as $packageName) { if (trim($packageName) !== '') $escapedPackages[] = escapeshellarg(trim($packageName)); }
  if (empty($escapedPackages)) $PackageCommand = '';
  else if ($packageManager === 'apt-get') {
    if ($packageOperation === 'remove') $operationFlags = 'DEBIAN_FRONTEND=noninteractive apt-get remove -y ';
    else if ($packageOperation === 'update') $operationFlags = 'DEBIAN_FRONTEND=noninteractive apt-get install -y --only-upgrade ';
    else $operationFlags = 'DEBIAN_FRONTEND=noninteractive apt-get install -y ';
    $PackageCommand = $operationFlags.implode(' ', $escapedPackages); }
  else if ($packageManager === 'dnf' or $packageManager === 'yum') {
    if ($packageOperation === 'remove') $operationFlags = $packageManager.' remove -y ';
    else if ($packageOperation === 'update') $operationFlags = $packageManager.' upgrade -y ';
    else $operationFlags = $packageManager.' install -y ';
    $PackageCommand = $operationFlags.implode(' ', $escapedPackages); }
  else if ($packageManager === 'apk') {
    if ($packageOperation === 'remove') $operationFlags = 'apk del ';
    else if ($packageOperation === 'update') $operationFlags = 'apk upgrade ';
    else $operationFlags = 'apk add --no-cache ';
    $PackageCommand = $operationFlags.implode(' ', $escapedPackages); }
  else if ($packageManager === 'pacman') {
    if ($packageOperation === 'remove') $operationFlags = 'pacman -R --noconfirm ';
    // / Without its own branch an upgrade fell through to the install flags, & --needed
    // / skips a package that is already installed, so every upgrade silently did nothing.
    else if ($packageOperation === 'update') $operationFlags = 'pacman -S --noconfirm ';
    else $operationFlags = 'pacman -S --noconfirm --needed ';
    $PackageCommand = $operationFlags.implode(' ', $escapedPackages); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $escapedPackages, $packageNames, $packageName, $operationFlags, $packageManager, $packageList, $packageOperation);
  return $PackageCommand; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to build an install command. A thin name over dependencyPackageCommand.
// / Accepts the package manager & the package list, in that order.
// / Returns the complete command.
function dependencyInstallCommand($packageManager, $packageList) {
  // / Set variables.
  global $EnableMemoryProtection;
  $InstallCommand = dependencyPackageCommand($packageManager, $packageList, 'install');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $packageManager, $packageList);
  return $InstallCommand; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to list which of the given Apache configurations are enabled.
// / Accepts the configuration names, each without .conf.
// / Returns the names enabled now.
// / A configuration is enabled while its name is in conf-enabled, which is where a2enconf puts it.
function enabledApacheConfs($confNames) {
  // / Set variables.
  global $EnableMemoryProtection;
  $EnabledConfs = array();
  $confName = $enabledPath = '';
  foreach ((array)$confNames as $confName) {
    $confName = basename((string)$confName);
    $enabledPath = '/etc/apache2/conf-enabled/'.$confName.'.conf';
    // / The same name is asked about before & after an install, so nothing PHP remembers of it
    // / may answer the second question.
    clearstatcache(TRUE, $enabledPath);
    if ($confName !== '' && (file_exists($enabledPath) or is_link($enabledPath))) $EnabledConfs[] = $confName; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $EnabledConfs is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $confName, $enabledPath, $confNames);
  return $EnabledConfs; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to turn off the Apache configurations a dependency install turned on.
// / Accepts the names enabled before the install & the names the contract does not want.
// / Returns the names turned off.
// / A package can publish something on every site as it installs. javascript-common, which the
// / packages Calibre recommends pull in, turns on an alias of /javascript for every site the host
// / serves, & this application uses none of it. Only a configuration this install turned on is
// / turned off, so one an administrator enabled stays. a2disconf records the choice as the
// / administrator's, so a later upgrade of the package does not turn it on again. A web server
// / that is already running keeps the alias until it is reloaded, & the operator is told so.
function turnOffApacheConfsInstallEnabled($enabledBefore, $unwantedConfs) {
  // / Set variables.
  global $Lol, $EnableMemoryProtection;
  $ConfsTurnedOff = array();
  $confName = '';
  $commandOutput = array();
  $commandExitCode = 1;
  foreach (enabledApacheConfs($unwantedConfs) as $confName) {
    if (in_array($confName, (array)$enabledBefore, TRUE)) continue;
    $commandOutput = array();
    // / This runs unsandboxed & convention 22 requires that to be said. a2disconf changes the
    // / host's web server configuration, which is its whole purpose, & the name is the contract's.
    exec('a2disconf -q '.escapeshellarg($confName).' 2>&1', $commandOutput, $commandExitCode);
    if ($commandExitCode === 0) {
      $ConfsTurnedOff[] = $confName;
      print('  '.str_pad($confName, 20).'turned off. This install turned it on & nothing uses it. Reload Apache.'.$Lol);
      warningEntry('The dependency install turned on the Apache configuration '.$confName.', which this application does not use, so it was turned off. A running web server keeps it until it is reloaded.'); }
    else warningEntry('The dependency install turned on the Apache configuration '.$confName.', which this application does not use, & it could not be turned off. '.trim(implode(' ', $commandOutput)).' Run  a2disconf '.$confName.'  as root.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $ConfsTurnedOff is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $confName, $commandOutput, $commandExitCode, $enabledBefore, $unwantedConfs);
  return $ConfsTurnedOff; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to bring this application's own copy of bubblewrap up to date with the dependencies.
// / Accepts the manifest & the subsystem filter the command was given, in that order.
// / Returns TRUE when nothing is left to do for the copy, as installSandboxBinary() decides, or
// / where the filter does not cover it.
// / A dependency install or update is one of the two times the copy changes, & an application
// / update is the other. It runs when the filter covers the subsystem bubblewrap is listed in, &
// / installSandboxBinary() says what it did on a line of the dependency list.
function refreshSandboxBinary($dependsManifest, $subsystemFilter) {
  // / Set variables.
  global $EnableMemoryProtection;
  $BinaryIsReady = TRUE;
  $sandboxSubsystem = $binaryStatus = $binaryDetail = '';
  $dependencyEntry = array();
  foreach ((array)$dependsManifest as $dependencyEntry) if (is_array($dependencyEntry) && isset($dependencyEntry['Binary'], $dependencyEntry['Subsystem']) && (string)$dependencyEntry['Binary'] === 'bwrap') $sandboxSubsystem = (string)$dependencyEntry['Subsystem'];
  if (function_exists('installSandboxBinary') && $sandboxSubsystem !== '' && (trim((string)$subsystemFilter) === '' or stripos($sandboxSubsystem, trim((string)$subsystemFilter)) !== FALSE)) list ($BinaryIsReady, $binaryStatus, $binaryDetail) = installSandboxBinary(20);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $sandboxSubsystem, $binaryStatus, $binaryDetail, $dependencyEntry, $dependsManifest, $subsystemFilter);
  return $BinaryIsReady; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to install every dependency that is absent or too old.
// / Accepts the authorization token, a subsystem filter & a confirmation boolean.
// / Returns a success boolean & the number installed, in that order.
// / The manifest order is the install order & is followed exactly.
// / A dependency whose own requirements failed is skipped rather than attempted, because
// / installing it would fail & would bury the reason under a second error.
function installDepends($authorizationToken, $subsystemFilter, $operatorConfirmed) {
  // / Set variables.
  global $RequiredDependsVersion, $Lol, $Verbose, $EnableMemoryProtection, $EngineUnwantedApacheConfs;
  $InstallSucceeded = TRUE;
  $DependenciesInstalled = 0;
  $dependsManifest = $dependencyEntry = $installedNames = $missingRequirements = $commandOutput = array();
  $commandExitCode = 1;
  $confsBefore = $confsTurnedOff = array();
  $manifestIsAvailable = $dependencyIsPresent = $installOneSucceeded = $callerIsAuthorized = $sandboxBinaryIsReady = $sweepIsClean = FALSE;
  $itemsSwept = 0;
  $rawOutput = $detectedVersion = $dependencyStatus = $detectedDependsVersion = $packageManager = $returnData = $requirementName = $operatorChoice = '';
  $callerIsAuthorized = validateDependencyAuthorization($authorizationToken);
  if (!$callerIsAuthorized) $InstallSucceeded = FALSE;
  else {
    list ($manifestIsAvailable, $dependsManifest, $detectedDependsVersion) = verifyDependsManifest($RequiredDependsVersion);
    if (!$manifestIsAvailable) $InstallSucceeded = FALSE;
    else {
      $packageManager = detectPackageManager();
      if (!$operatorConfirmed) {
        print($Lol.'This will install packages on this host using '.($packageManager === '' ? 'no detected package manager' : $packageManager).'.'.$Lol);
        $operatorChoice = promptOperator('Type YES to continue. Anything else cancels. ');
        if ($operatorChoice !== 'YES') { print($Lol.'Cancelled. Nothing was installed.'.$Lol.$Lol); $InstallSucceeded = FALSE; } }
      if ($InstallSucceeded) {
        // / Which unwanted Apache configurations are on before anything is installed, so only one
        // / this install turns on is turned off again.
        $confsBefore = enabledApacheConfs($EngineUnwantedApacheConfs);
        // / Refresh the package index once rather than once per dependency.
        // / The index refresh is checked. A refresh that failed makes every install below
        // / fail with a message about the package rather than about the index.
        if ($packageManager === 'apt-get') {
          $commandOutput = array();
          exec('DEBIAN_FRONTEND=noninteractive apt-get update 2>&1', $commandOutput, $commandExitCode);
          if ($commandExitCode !== 0) warningEntry('The package index could not be refreshed. Installs may fail or fetch stale versions.'); }
        // / Anything already on the host counts as satisfied, whether or not the filter
        // / covers it. A requirement almost always lives in a different subsystem from the
        // / thing that needs it, so seeding this only from the filtered entries made a
        // / filtered install refuse nearly everything it was asked to do. Installing the
        // / Ebooks subsystem reported that Calibre was waiting on Bubblewrap, on a host
        // / where Bubblewrap was installed & working.
        foreach ($dependsManifest as $dependencyEntry) {
          list ($dependencyIsPresent, $detectedVersion, $dependencyStatus, $rawOutput) = resolveDependencyChoice($dependencyEntry);
          if ($dependencyStatus === 'ok' or $dependencyStatus === 'unknown-version') $installedNames[(string)$dependencyEntry['Name']] = TRUE; }
        print($Lol.'Installing dependencies in manifest order.'.$Lol);
        foreach ($dependsManifest as $dependencyEntry) {
          if (trim((string)$subsystemFilter) !== '' && stripos((string)$dependencyEntry['Subsystem'], trim((string)$subsystemFilter)) === FALSE) continue;
          list ($dependencyIsPresent, $detectedVersion, $dependencyStatus, $rawOutput) = resolveDependencyChoice($dependencyEntry);
          if ($dependencyStatus === 'ok' or $dependencyStatus === 'unknown-version') {
            $installedNames[(string)$dependencyEntry['Name']] = TRUE;
            // / Present, not installed. Installed is reserved for work this run actually
            // / did, so a reader can tell what changed on this machine just now from what
            // / was already true of it. A bundled dependency is present for the same
            // / reason & is never installed by anything.
            print('  '.str_pad((string)$dependencyEntry['Name'], 20).'present'.$Lol);
            continue; }
          // / Refuse to attempt anything whose own requirements are not in place.
          // / Only the requirements actually missing are named. Printing the whole Requires
          // / list named requirements that were satisfied & sent an operator looking at them.
          $missingRequirements = array();
          foreach ((array)$dependencyEntry['Requires'] as $requirementName) {
            if (!isset($installedNames[$requirementName])) $missingRequirements[] = $requirementName; }
          if (count($missingRequirements) > 0) {
            print('  '.str_pad((string)$dependencyEntry['Name'], 20).'SKIPPED, waiting on '.implode(', ', $missingRequirements).$Lol);
            if ($dependencyEntry['Required']) $InstallSucceeded = FALSE;
            continue; }
          // / A bundled dependency is never installed by anything & must not say it was.
          // / It ships inside this application, so there is no work for this run to do &
          // / no count to increment. Reporting an install would claim work that did not
          // / happen, & it claimed exactly that for PyMeshLab & for ScanCore.
          // / Whether it actually works is a separate question that --check-depends answers,
          // / & a bundled file being on disk has never been evidence that it runs.
          if ((string)$dependencyEntry['Type'] === 'bundled') {
            $installedNames[(string)$dependencyEntry['Name']] = TRUE;
            print('  '.str_pad((string)$dependencyEntry['Name'], 20).'bundled, ships with this application'.$Lol);
            continue; }
          list ($installOneSucceeded, $returnData) = installOneDependency($dependencyEntry, $packageManager);
          if ($installOneSucceeded) {
            $DependenciesInstalled++;
            $installedNames[(string)$dependencyEntry['Name']] = TRUE;
            print('  '.str_pad((string)$dependencyEntry['Name'], 20).'installed'.$Lol);
            logEntry('Installed dependency '.(string)$dependencyEntry['Name'].'.'); }
          else {
            print('  '.str_pad((string)$dependencyEntry['Name'], 20).'FAILED'.((string)$dependencyEntry['Type'] === 'manual' ? ', install by hand' : '').$Lol);
            if ($Verbose && trim($returnData) !== '') print('      '.str_replace(PHP_EOL, PHP_EOL.'      ', trim($returnData)).$Lol);
            if ($dependencyEntry['Required']) $InstallSucceeded = FALSE;
            warningEntry('Dependency '.(string)$dependencyEntry['Name'].' could not be installed. '.trim($returnData)); } }
        // / What an interrupted run or a renamed entry left among this application's own builds is taken
        // / away. The answer is not acted on, because sweepOwnBuilds() has already warned about anything
        // / it could not take away, & nothing it leaves is ever run.
        list ($sweepIsClean, $itemsSwept) = sweepOwnBuilds($dependsManifest);
        $confsTurnedOff = turnOffApacheConfsInstallEnabled($confsBefore, $EngineUnwantedApacheConfs);
        // / The answer is not acted on. installSandboxBinary() has already printed the copy's line &
        // / warned where it is not ready, & the system's bubblewrap still builds every sandbox the
        // / kernel permits it to, so the dependencies themselves are not failed for it.
        $sandboxBinaryIsReady = refreshSandboxBinary($dependsManifest, $subsystemFilter);
        print($Lol.'Installed '.$DependenciesInstalled.' dependenc(ies).'.(count($confsTurnedOff) > 0 ? ' Turned off '.count($confsTurnedOff).' Apache configuration(s) the install turned on.' : '').$Lol.$Lol); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $confsBefore, $confsTurnedOff, $dependsManifest, $dependencyEntry, $installedNames, $manifestIsAvailable, $dependencyIsPresent, $installOneSucceeded, $callerIsAuthorized, $sandboxBinaryIsReady, $sweepIsClean, $itemsSwept, $detectedVersion, $dependencyStatus, $detectedDependsVersion, $packageManager, $returnData, $requirementName, $operatorChoice, $missingRequirements, $commandOutput, $commandExitCode, $authorizationToken, $subsystemFilter, $operatorConfirmed, $rawOutput);
  return array($InstallSucceeded, $DependenciesInstalled); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to upgrade every dependency that is already installed.
// / Accepts the authorization token, a subsystem filter & a confirmation boolean.
// / Returns a success boolean & the number upgraded, in that order.
// / An absent dependency is NOT installed here. Updating & installing are different
// / intentions & an operator who asked to update did not ask to add anything.
// / This application's own copy of bubblewrap is made here when it is missing, because it is a
// / copy of a dependency rather than one, & this is the command a host is told to run for it.
// / This application's own build of a dependency is settled after the package manager, which takes
// / it away where the package manager has caught up & builds it again where it is behind the
// / version its Fallback pins.
function updateDepends($authorizationToken, $subsystemFilter, $operatorConfirmed) {
  // / Set variables.
  global $RequiredDependsVersion, $Lol, $EnableMemoryProtection;
  $UpdateSucceeded = TRUE;
  $DependenciesUpdated = 0;
  $dependsManifest = $dependencyEntry = array();
  $manifestIsAvailable = $dependencyIsPresent = $callerIsAuthorized = $sandboxBinaryIsReady = $ownBuildIsSettled = $sweepIsClean = FALSE;
  $itemsSwept = 0;
  $rawOutput = $detectedVersion = $dependencyStatus = $detectedDependsVersion = $packageManager = $updateCommand = $operatorChoice = $settleStatus = $settleDetail = '';
  $commandOutput = array();
  $commandExitCode = 1;
  $callerIsAuthorized = validateDependencyAuthorization($authorizationToken);
  if (!$callerIsAuthorized) $UpdateSucceeded = FALSE;
  else {
    list ($manifestIsAvailable, $dependsManifest, $detectedDependsVersion) = verifyDependsManifest($RequiredDependsVersion);
    if (!$manifestIsAvailable) $UpdateSucceeded = FALSE;
    else {
      $packageManager = detectPackageManager();
      if (!$operatorConfirmed) {
        print($Lol.'This will upgrade installed packages on this host.'.$Lol);
        $operatorChoice = promptOperator('Type YES to continue. Anything else cancels. ');
        if ($operatorChoice !== 'YES') { print($Lol.'Cancelled. Nothing was upgraded.'.$Lol.$Lol); $UpdateSucceeded = FALSE; } }
      if ($UpdateSucceeded) {
        // / The index refresh is checked. A refresh that failed makes every install below
        // / fail with a message about the package rather than about the index.
        if ($packageManager === 'apt-get') {
          $commandOutput = array();
          exec('DEBIAN_FRONTEND=noninteractive apt-get update 2>&1', $commandOutput, $commandExitCode);
          if ($commandExitCode !== 0) warningEntry('The package index could not be refreshed. Installs may fail or fetch stale versions.'); }
        print($Lol.'Upgrading installed dependencies.'.$Lol);
        foreach ($dependsManifest as $dependencyEntry) {
          if (trim((string)$subsystemFilter) !== '' && stripos((string)$dependencyEntry['Subsystem'], trim((string)$subsystemFilter)) === FALSE) continue;
          // / A bundled dependency travels with the application & is upgraded by updating it.
          // / An extension travels with PHP & is upgraded by upgrading PHP.
          if ((string)$dependencyEntry['Type'] !== 'apt' && (string)$dependencyEntry['Type'] !== 'pip') continue;
          list ($dependencyIsPresent, $detectedVersion, $dependencyStatus, $rawOutput) = resolveDependencyChoice($dependencyEntry);
          // / An unsupported dependency is named & skipped rather than attempted.
          // / A package that does not exist for this architecture fails like a broken mirror, & sends an operator after a network fault.
          if ($dependencyStatus === 'unsupported') { print('  '.str_pad((string)$dependencyEntry['Name'], 20).'not available on this machine. '.(string)$rawOutput.$Lol); continue; }
          if ($dependencyStatus === 'absent') { print('  '.str_pad((string)$dependencyEntry['Name'], 20).'not installed, skipped'.$Lol); continue; }
          $commandOutput = array();
          // / The command is tested before the redirect is appended. Comparing the finished
          // / string against ' 2>&1' worked only while that suffix never changed, & an empty
          // / command would have been executed the moment it did.
          if ((string)$dependencyEntry['Type'] === 'pip') $updateCommand = 'pip3 install --break-system-packages --upgrade '.escapeshellarg((string)$dependencyEntry['Package']);
          else $updateCommand = dependencyPackageCommand($packageManager, (string)$dependencyEntry['Package'], 'update');
          if (trim($updateCommand) === '') print('  '.str_pad((string)$dependencyEntry['Name'], 20).'no package manager, skipped'.$Lol);
          else {
            exec($updateCommand.' 2>&1', $commandOutput, $commandExitCode);
            if ($commandExitCode === 0) {
              $DependenciesUpdated++;
              print('  '.str_pad((string)$dependencyEntry['Name'], 20).'upgraded'.$Lol); }
            else {
              print('  '.str_pad((string)$dependencyEntry['Name'], 20).'FAILED'.$Lol);
              warningEntry('Dependency '.(string)$dependencyEntry['Name'].' could not be upgraded.'); } }
          // / The own build is settled after the package manager, whatever the package manager did,
          // / & a required dependency it leaves outside its window fails the update.
          if ((string)$dependencyEntry['Type'] === 'apt') {
            list ($ownBuildIsSettled, $settleStatus, $settleDetail) = settleOwnBuild($dependencyEntry);
            if ($settleStatus === 'installed' or $settleStatus === 'updated') $DependenciesUpdated++;
            if (!$ownBuildIsSettled && $dependencyEntry['Required']) $UpdateSucceeded = FALSE; } }
        // / What an interrupted run or a renamed entry left among this application's own builds is taken
        // / away, as an install does, & the answer is not acted on for the same reason.
        list ($sweepIsClean, $itemsSwept) = sweepOwnBuilds($dependsManifest);
        // / The copy follows the bubblewrap just upgraded. The answer is not acted on, because
        // / installSandboxBinary() has already printed the copy's line & warned where it is not
        // / ready, & the upgrade is not failed for it, as for an install.
        $sandboxBinaryIsReady = refreshSandboxBinary($dependsManifest, $subsystemFilter);
        print($Lol.'Upgraded '.$DependenciesUpdated.' dependenc(ies).'.$Lol.$Lol); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dependsManifest, $dependencyEntry, $manifestIsAvailable, $dependencyIsPresent, $callerIsAuthorized, $sandboxBinaryIsReady, $ownBuildIsSettled, $sweepIsClean, $itemsSwept, $detectedVersion, $dependencyStatus, $detectedDependsVersion, $packageManager, $updateCommand, $operatorChoice, $settleStatus, $settleDetail, $commandOutput, $commandExitCode, $authorizationToken, $subsystemFilter, $operatorConfirmed, $rawOutput);
  return array($UpdateSucceeded, $DependenciesUpdated); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove dependencies from this host.
// / Accepts the authorization token, a subsystem filter & a confirmation boolean.
// / Returns a success boolean & the number removed, in that order.
// / A subsystem filter is mandatory. Removing everything in the manifest would take PHP,
// / Apache & the package manager with it & leave a machine that cannot be recovered
// / without physical access. This function refuses to run without a filter, whatever the
// / operator has confirmed.
// / Nothing in the Core subsystem is ever removed, whatever the operator confirmed.
// / A dependency marked Required in the Sandbox subsystem is never removed either.
// / This application's own build of a dependency goes with the package it stands in for.
function uninstallDepends($authorizationToken, $subsystemFilter, $operatorConfirmed) {
  // / Set variables.
  global $RequiredDependsVersion, $Lol, $EnableMemoryProtection;
  $UninstallSucceeded = TRUE;
  $DependenciesRemoved = 0;
  $dependsManifest = $dependencyEntry = array();
  $manifestIsAvailable = $callerIsAuthorized = $ownBuildIsGone = $sweepIsClean = FALSE;
  $itemsSwept = 0;
  $detectedDependsVersion = $packageManager = $removeCommand = $operatorChoice = $detachDetail = '';
  $commandOutput = array();
  $commandExitCode = 1;
  $callerIsAuthorized = validateDependencyAuthorization($authorizationToken);
  if (!$callerIsAuthorized) $UninstallSucceeded = FALSE;
  else if (trim((string)$subsystemFilter) === '') {
    $UninstallSucceeded = FALSE;
    print($Lol.'A subsystem must be named. Every dependency includes PHP, Apache & the package manager.'.$Lol);
    print($Lol.'  sudo php '.engineApplicationEntryPoint().' --setup --uninstall-depends --subsystem=Ebooks'.$Lol.$Lol);
    warningEntry('An unfiltered dependency removal was refused.'); }
  else {
    list ($manifestIsAvailable, $dependsManifest, $detectedDependsVersion) = verifyDependsManifest($RequiredDependsVersion);
    if (!$manifestIsAvailable) $UninstallSucceeded = FALSE;
    else {
      $packageManager = detectPackageManager();
      if (!$operatorConfirmed) {
        print($Lol.'This will REMOVE packages providing the '.trim((string)$subsystemFilter).' subsystem.'.$Lol);
        print('Anything else on this host that uses them will stop working.'.$Lol);
        $operatorChoice = promptOperator('Type YES to continue. Anything else cancels. ');
        if ($operatorChoice !== 'YES') { print($Lol.'Cancelled. Nothing was removed.'.$Lol.$Lol); $UninstallSucceeded = FALSE; } }
      if ($UninstallSucceeded) {
        print($Lol.'Removing dependencies for '.trim((string)$subsystemFilter).'.'.$Lol);
        foreach ($dependsManifest as $dependencyEntry) {
          if (stripos((string)$dependencyEntry['Subsystem'], trim((string)$subsystemFilter)) === FALSE) continue;
          // / The core is never removed, whatever was asked for or confirmed.
          if ((string)$dependencyEntry['Subsystem'] === 'Core' or ((string)$dependencyEntry['Subsystem'] === 'Sandbox' && $dependencyEntry['Required'])) {
            print('  '.str_pad((string)$dependencyEntry['Name'], 20).'REFUSED, this is core infrastructure'.$Lol);
            continue; }
          if ((string)$dependencyEntry['Type'] !== 'apt') { print('  '.str_pad((string)$dependencyEntry['Name'], 20).'not managed by a package manager, skipped'.$Lol); continue; }
          // / An own build is taken away first, so removing a subsystem leaves nothing of this
          // / application's behind, whatever the package manager then does.
          list ($ownBuildIsGone, $detachDetail) = detachOwnCopy($dependencyEntry);
          if ($detachDetail !== '') print('  '.str_pad((string)$dependencyEntry['Name'].' own build', 20).($ownBuildIsGone ? 'removed, ' : 'FAILED, ').$detachDetail.$Lol);
          if (!$ownBuildIsGone) $UninstallSucceeded = FALSE;
          $commandOutput = array();
          $removeCommand = dependencyPackageCommand($packageManager, (string)$dependencyEntry['Package'], 'remove');
          if (trim($removeCommand) === '') { print('  '.str_pad((string)$dependencyEntry['Name'], 20).'no package manager, skipped'.$Lol); continue; }
          exec($removeCommand.' 2>&1', $commandOutput, $commandExitCode);
          if ($commandExitCode === 0) {
            $DependenciesRemoved++;
            print('  '.str_pad((string)$dependencyEntry['Name'], 20).'removed'.$Lol);
            warningEntry('Removed dependency '.(string)$dependencyEntry['Name'].' on request.'); }
          else {
            // / A failure that only printed left the caller told the whole operation had
            // / succeeded, & left nothing in the log at all.
            $UninstallSucceeded = FALSE;
            print('  '.str_pad((string)$dependencyEntry['Name'], 20).'FAILED'.$Lol);
            warningEntry('Dependency '.(string)$dependencyEntry['Name'].' could not be removed.'); } }
        // / Whatever an interrupted run left beside the builds just taken away goes with them. The answer
        // / is not acted on, because sweepOwnBuilds() has already warned about anything it left.
        list ($sweepIsClean, $itemsSwept) = sweepOwnBuilds($dependsManifest);
        print($Lol.'Removed '.$DependenciesRemoved.' dependenc(ies).'.$Lol.$Lol); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dependsManifest, $dependencyEntry, $manifestIsAvailable, $callerIsAuthorized, $ownBuildIsGone, $sweepIsClean, $itemsSwept, $detectedDependsVersion, $packageManager, $removeCommand, $operatorChoice, $detachDetail, $commandOutput, $commandExitCode, $authorizationToken, $subsystemFilter, $operatorConfirmed);
  return array($UninstallSucceeded, $DependenciesRemoved); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to apply the alias table of one dependency to a set of detected tokens.
// / Accepts the detected tokens & the alias table. Returns the expanded set.
// / An alias ADDS an extension & never renames one. A tool that prints TIFF can still be
// / handed a file named tiff, so removing the token in favour of tif would lose a format
// / while adding one. Both are kept.
// / This table is the reason a narrowing pass is safe. FFMPEG has no mkv & no wmv, it has
// / matroska & asf. Assimp has no dae, it has collada. Seven of Assimp's twenty two export
// / identifiers are not the extension a user types. Without this table a narrowing pass
// / would delete working conversions on a correctly installed machine.
function applyCapabilityAliases($detectedTokens, $aliasTable) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ExpandedTokens = array();
  $detectedToken = $aliasKey = $aliasValue = '';
  $ExpandedTokens = $detectedTokens;
  if (is_array($aliasTable)) {
    foreach ($aliasTable as $aliasKey => $aliasValue) {
      $aliasKey = strtolower(trim((string)$aliasKey));
      $aliasValue = strtolower(trim((string)$aliasValue));
      if ($aliasKey === '' or $aliasValue === '') continue;
      if (in_array($aliasKey, $ExpandedTokens, TRUE) && !in_array($aliasValue, $ExpandedTokens, TRUE)) array_push($ExpandedTokens, $aliasValue); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $ExpandedTokens is not purged, because it is a return value.
  purgeSensitiveMemory($EnableMemoryProtection, $detectedToken, $aliasKey, $aliasValue, $detectedTokens, $aliasTable);
  return $ExpandedTokens; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run one capability command & read what it prints.
// / Accepts the command, the style, the pattern & the direction, in that order.
// / Returns a success boolean, the readable tokens & the writable tokens, in that order.
// / A failure here is a failure to ASK & never an answer of nothing.
// / The caller decides what that means.
// / The conclusion it must never reach is that the tool can do nothing at all.
// /
// / Two styles exist because two shapes of output exist.
// / A matrix prints one line per format carrying its own direction flags.
// / ImageMagick & FFMPEG both do this & the pattern names three groups for it.
// / A read or write group holding a hyphen or a space means no.
// / Anything else means yes.
// / That lets one reader handle ImageMagick using r & w & FFMPEG using D & E, without
// / knowing anything about either.
// / A list prints names with no flags at all, which is what Assimp does.
// / The manifest declares the direction for a list, because the output cannot.
function runCapabilityCommand($capabilityCommand, $capabilityStyle, $capabilityPattern, $capabilityDirection) {
  // / Set variables.
  global $EnableMemoryProtection;
  $CommandSucceeded = FALSE;
  $ReadableTokens = $WritableTokens = array();
  $commandOutput = $patternMatches = $patternMatch = $splitTokens = array();
  $commandExitCode = 1;
  $rawOutput = $splitToken = '';
  exec(dependencyEnvironmentPrefix().(string)$capabilityCommand.' 2>&1', $commandOutput, $commandExitCode);
  $rawOutput = implode(PHP_EOL, $commandOutput);
  // / An exit code is not consulted. Several of these tools describe themselves & then
  // / exit non zero, & what matters is whether anything parseable came back.
  if (trim($rawOutput) === '') warningEntry('The capability command '.$capabilityCommand.' printed nothing.');
  else if ((string)$capabilityStyle === 'matrix') {
    if (preg_match_all((string)$capabilityPattern, $rawOutput, $patternMatches, PREG_SET_ORDER)) {
      foreach ($patternMatches as $patternMatch) {
        if (!isset($patternMatch['name'])) continue;
        // / One demuxer may answer to several names. FFMPEG prints mov,mp4,m4a,3gp,3g2,mj2
        // / as a single entry, & every one of those is a real extension a user may upload.
        $splitTokens = explode(',', strtolower(trim($patternMatch['name'])));
        foreach ($splitTokens as $splitToken) {
          $splitToken = trim($splitToken);
          if ($splitToken === '') continue;
          // / A flag of hyphen or space means the tool cannot. Anything else means it can.
          if (isset($patternMatch['read']) && trim($patternMatch['read']) !== '' && trim($patternMatch['read']) !== '-' && !in_array($splitToken, $ReadableTokens, TRUE)) array_push($ReadableTokens, $splitToken);
          if (isset($patternMatch['write']) && trim($patternMatch['write']) !== '' && trim($patternMatch['write']) !== '-' && !in_array($splitToken, $WritableTokens, TRUE)) array_push($WritableTokens, $splitToken); } }
      if (count($ReadableTokens) > 0 or count($WritableTokens) > 0) $CommandSucceeded = TRUE; } }
  else {
    // / A list carries no flags, so the manifest says which direction it describes.
    $splitTokens = preg_split((string)$capabilityPattern, $rawOutput);
    if (is_array($splitTokens)) {
      foreach ($splitTokens as $splitToken) {
        // / A glob such as *.obj & any surrounding whitespace are stripped, so a list of
        // / globs & a list of bare names are read by the same code.
        $splitToken = strtolower(trim($splitToken));
        $splitToken = ltrim($splitToken, '*');
        $splitToken = ltrim($splitToken, '.');
        // / A compound glob such as *.mesh.xml is reduced to the final segment, because
        // / that is what getExtension() returns for a file named that way & a token nothing
        // / can ever match is noise in the cache rather than a capability.
        if (strpos($splitToken, '.') !== FALSE) $splitToken = substr($splitToken, strrpos($splitToken, '.') + 1);
        if ($splitToken === '' or !preg_match('/^[a-z0-9][a-z0-9_-]*$/', $splitToken)) continue;
        if ((string)$capabilityDirection !== 'write' && !in_array($splitToken, $ReadableTokens, TRUE)) array_push($ReadableTokens, $splitToken);
        if ((string)$capabilityDirection !== 'read' && !in_array($splitToken, $WritableTokens, TRUE)) array_push($WritableTokens, $splitToken); }
      if (count($ReadableTokens) > 0 or count($WritableTokens) > 0) $CommandSucceeded = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / The two token sets are not purged, because they are return values.
  purgeSensitiveMemory($EnableMemoryProtection, $commandOutput, $commandExitCode, $rawOutput, $patternMatches, $patternMatch, $splitTokens, $splitToken, $capabilityCommand, $capabilityStyle, $capabilityPattern, $capabilityDirection);
  return array($CommandSucceeded, $ReadableTokens, $WritableTokens); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to detect what one dependency can actually read & write.
// / Accepts one manifest entry.
// / Returns a read state, a write state, the readable extensions & the writable
// / extensions, in that order.
// /
// / The state is the whole point of this function & it has three values.
// /   detected      The tool answered & the answer is usable.
// /   unknown       The tool was never asked, or was asked & could not answer.
// /   unavailable   The tool is not installed, so there is nothing to ask.
// /
// / Unknown is not empty & the difference decides whether an installation works.
// / LibreOffice, Inkscape, Dia & OpenSCAD have no format list command in any released
// / version. Reading that silence as a report of no capability would remove every document
// / conversion, every drawing conversion & every vector conversion from a machine where
// / all four are installed & working. Unknown means the pipeline declaration stands.
// /
// / A command that runs & yields nothing is also unknown rather than empty.
// / That is the floor. A parse failure & an honest answer of nothing look identical from
// / here, so the safe reading is the one that changes nothing.
// /
// / The two directions carry their own state, because one command can fail while the other succeeds.
// / Assimp's listext reports what it imports & listexport what it exports.
// / One state would let a working listext mark Assimp detected while listexport never answered.
// / A narrowing pass reading that would refuse every model conversion on the machine.
function detectDependencyCapability($dependencyEntry) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ReadState = $WriteState = 'unknown';
  $ReadableExtensions = $WritableExtensions = array();
  $commandSucceeded = $writeSucceeded = FALSE;
  $readTokens = $writeTokens = $secondRead = $secondWrite = $aliasTable = $probeEntry = array();
  $binaryPath = $firstDirection = '';
  // / This application's own build answers for an entry with one in service, through the same
  // / commands with its folder first on the PATH, because that is the program a conversion runs.
  list ($probeEntry, $binaryPath) = ownBuildInService($dependencyEntry);
  if ($binaryPath === '' && (string)$probeEntry['Binary'] !== '') {
    // / A path the manifest names is looked for where it says, as resolveDependencyState() looks.
    // / locateDependency() refuses a path, which reported a tool named by its path unavailable.
    if (strpos((string)$probeEntry['Binary'], DIRECTORY_SEPARATOR) !== FALSE) $binaryPath = ((is_file((string)$probeEntry['Binary']) && is_executable((string)$probeEntry['Binary'])) ? (string)$probeEntry['Binary'] : '');
    else $binaryPath = locateDependency((string)$probeEntry['Binary']);
    if ($binaryPath === '') $ReadState = $WriteState = 'unavailable'; }
  if ($ReadState !== 'unavailable' && isset($probeEntry['CapabilityCommand']) && (string)$probeEntry['CapabilityCommand'] !== '') {
    $aliasTable = (isset($probeEntry['CapabilityAliases']) && is_array($probeEntry['CapabilityAliases'])) ? $probeEntry['CapabilityAliases'] : array();
    $firstDirection = (isset($probeEntry['CapabilityDirection']) ? (string)$probeEntry['CapabilityDirection'] : 'readwrite');
    list ($commandSucceeded, $readTokens, $writeTokens) = runCapabilityCommand(
      (string)$probeEntry['CapabilityCommand'],
      (isset($probeEntry['CapabilityStyle']) ? (string)$probeEntry['CapabilityStyle'] : 'matrix'),
      (string)$probeEntry['CapabilityPattern'],
      $firstDirection);
    // / A command only settles the directions it was asked about. A read only command
    // / leaves the write state exactly as it was, which is unknown.
    if ($commandSucceeded) {
      if ($firstDirection !== 'write') {
        $ReadableExtensions = applyCapabilityAliases($readTokens, $aliasTable);
        $ReadState = 'detected'; }
      if ($firstDirection !== 'read') {
        $WritableExtensions = applyCapabilityAliases($writeTokens, $aliasTable);
        $WriteState = 'detected'; } }
    else warningEntry('Capability detection for '.(string)$probeEntry['Name'].' produced nothing usable. Its declared formats will be trusted instead.');
    // / A dependency answering two questions with two commands runs the second one here.
    // / Assimp reports what it can import with listext & what it can export with
    // / listexport, & neither command mentions the other direction at all.
    if (isset($probeEntry['CapabilityWriteCommand']) && (string)$probeEntry['CapabilityWriteCommand'] !== '') {
      list ($writeSucceeded, $secondRead, $secondWrite) = runCapabilityCommand(
        (string)$probeEntry['CapabilityWriteCommand'],
        (isset($probeEntry['CapabilityWriteStyle']) ? (string)$probeEntry['CapabilityWriteStyle'] : 'list'),
        (string)$probeEntry['CapabilityWritePattern'],
        'write');
      // / A second command that failed leaves the write state unknown & the write set
      // / empty. Those two together are read as nothing being known about writing, which
      // / is true, rather than as the tool being unable to write, which is not.
      if ($writeSucceeded) {
        $WritableExtensions = applyCapabilityAliases($secondWrite, $aliasTable);
        $WriteState = 'detected'; }
      else {
        $WritableExtensions = array();
        $WriteState = 'unknown';
        warningEntry('The write capability command for '.(string)$probeEntry['Name'].' produced nothing usable. Its declared output formats will be trusted instead.'); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / The two extension sets are not purged, because they are return values.
  purgeSensitiveMemory($EnableMemoryProtection, $commandSucceeded, $writeSucceeded, $readTokens, $writeTokens, $secondRead, $secondWrite, $aliasTable, $probeEntry, $binaryPath, $firstDirection, $dependencyEntry);
  return array($ReadState, $WriteState, $ReadableExtensions, $WritableExtensions); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to build the capability cache by asking every installed dependency.
// / Accepts nothing. Returns a success boolean & the number of dependencies detected.
// / Detection spawns a process for every dependency that can answer, so it happens here &
// / never during a request. A conversion reads the file this writes.
// / The cache is a PHP file & that is deliberate. It cannot render anything if it is ever
// / served, it refuses to execute without the core, & require_once is the only parser it
// / needs. A JSON or an ini file would need a parser & would be readable as text.
// /
// / Root writes it without following anything & never hands it to the web server account.
// / Root writes a new file, renames it into place & leaves it root's & 0644, so the web server reads it & cannot edit it.
function buildCapabilityCache() {
  // / Set variables.
  // / The pin the core sets is $RequiredDependsVersion. $DependsVersion is what the
  // / manifest declares & is not set until the manifest has been read, so passing it here
  // / handed verifyDependsManifest() an empty string & every manifest was refused for
  // / reporting a version this core did not require.
  global $CacheLoc, $DirSep, $RequiredDependsVersion, $RunningAsRoot, $EnableMemoryProtection;
  $CacheWasBuilt = FALSE;
  $DetectedCount = 0;
  $manifestIsAvailable = $cacheWasWritten = FALSE;
  $writeNotice = '';
  $dependsManifest = $dependencyEntry = $cacheRecords = array();
  $readableExtensions = $writableExtensions = array();
  $readState = $writeState = $detectedDependsVersion = $cachePath = $cacheContents = '';
  list ($manifestIsAvailable, $dependsManifest, $detectedDependsVersion) = verifyDependsManifest($RequiredDependsVersion);
  if (!$manifestIsAvailable) warningEntry('Capability detection was skipped, because the dependency manifest is unavailable.');
  else {
    foreach ($dependsManifest as $dependencyEntry) {
      list ($readState, $writeState, $readableExtensions, $writableExtensions) = detectDependencyCapability($dependencyEntry);
      $cacheRecords[(string)$dependencyEntry['Name']] = array(
        'ReadState' => $readState,
        'WriteState' => $writeState,
        'Subsystem' => (string)$dependencyEntry['Subsystem'],
        'CanRead' => $readableExtensions,
        'CanWrite' => $writableExtensions);
      // / A dependency counts as answered when it settled either direction. Assimp settles
      // / them with two commands & one of those can succeed while the other does not.
      if ($readState === 'detected' or $writeState === 'detected') $DetectedCount++; }
    // / The cache records which manifest built it. A manifest that changed may name a
    // / dependency this file has never heard of, or may have corrected a pattern, & either
    // / way a cache built by the old one is not evidence about the new one.
    $cacheContents = '<?php'.PHP_EOL
      .'// / '.engineApplicationName().' capability cache. Written by Dependency Core.'.PHP_EOL
      .'// / This file is generated. Every edit made here is lost the next time it is built.'.PHP_EOL
      .'// / Rebuild it with --setup --detect-capabilities.'.PHP_EOL
      .'if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die(\'ERROR!!! Engine-33003, A generated cache cannot be loaded directly!\'.PHP_EOL);'.PHP_EOL
      .'$CapabilityCacheFormat = \'v3.8.7\';'.PHP_EOL
      .'$CapabilityCacheManifest = '.var_export((string)$detectedDependsVersion, TRUE).';'.PHP_EOL
      .'$CapabilityCacheBuilt = '.var_export(time(), TRUE).';'.PHP_EOL
      .'$CapabilityCache = '.var_export($cacheRecords, TRUE).';'.PHP_EOL
      .'?>'.PHP_EOL;
    // / The cache lives in the cache folder, which the web server account owns & root provisions inside the data root it owns.
    // / Root writes it through the kernel, which the root owned data root lets it do without following anything.
    // / The web server account writes its own.
    $cachePath = rtrim((string)$CacheLoc, $DirSep).$DirSep.'capability-cache.php';
    if ($RunningAsRoot) list ($cacheWasWritten, $writeNotice) = writeFileWithoutFollowing($cachePath, $cacheContents, 0644);
    else $cacheWasWritten = (@file_put_contents($cachePath, $cacheContents, LOCK_EX) !== FALSE);
    if (!$cacheWasWritten) warningEntry('The capability cache could not be written to '.$cachePath.'. Detection will be repeated on the next run & conversions will use declared formats. '.$writeNotice);
    else {
      // / A file root writes is 0644 from the moment it exists, so the web server can read it
      // / without being handed it. Any other account writes a file of its own, made 0640.
      if (!$RunningAsRoot) @chmod($cachePath, 0640);
      $CacheWasBuilt = TRUE;
      logEntry('Capability cache written. '.$DetectedCount.' of '.count($cacheRecords).' dependencies answered.'); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $manifestIsAvailable, $dependsManifest, $dependencyEntry, $cacheRecords, $readableExtensions, $writableExtensions, $readState, $writeState, $detectedDependsVersion, $cachePath, $cacheContents, $cacheWasWritten, $writeNotice);
  return array($CacheWasBuilt, $DetectedCount); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the capability cache & judge whether it still describes this host.
// / Accepts nothing. Returns an availability boolean & the cache records, in that order.
// / A cache that is missing, unreadable, or was built by a different manifest is refused.
// / A refused cache is not an EMPTY one.
// / The caller receives no records & must read that as every dependency being unknown,
// / which leaves every pipeline declaration standing. Reading it as no capability would
// / take the whole application down on a missing file.
// / Root never requires it, because the web server account can put any file into the cache folder under this name.
// / Root answers as if there were no cache, which leaves every declared format standing.
// / Nothing root does depends on the cache, because conversions run as the web server account, which still reads it.
function readCapabilityCache() {
  // / Set variables.
  // / $CoreLoaded is declared so the guard at the top of the generated file can see it.
  // / The require below happens inside this function, so a global the file tests must be
  // / in this scope or a valid cache refuses to load itself.
  // / $RequiredDependsVersion for the same reason buildCapabilityCache() uses it. This
  // / function may run before the manifest has been read, & a comparison against a value
  // / that is not set yet refuses every cache that was ever written.
  global $CacheLoc, $DirSep, $RequiredDependsVersion, $CoreLoaded, $RunningAsRoot, $Verbose, $EnableMemoryProtection;
  $CacheIsAvailable = FALSE;
  $CapabilityRecords = array();
  $cachePath = $requiredManifest = '';
  $CapabilityCacheFormat = $CapabilityCacheManifest = $CapabilityCacheBuilt = $CapabilityCache = NULL;
  $cachePath = rtrim((string)$CacheLoc, $DirSep).$DirSep.'capability-cache.php';
  $requiredManifest = ltrim((string)$RequiredDependsVersion, 'vV');
  if ($RunningAsRoot) {
    if ($Verbose) logEntry('Root does not load the capability cache, so declared formats stand for this run.'); }
  else if (file_exists($cachePath)) {
    // / The declarations are nulled before the require, so a truncated or partly written
    // / file is caught here instead of inheriting whatever was in scope.
    require ($cachePath);
    if (!is_array($CapabilityCache)) warningEntry('The capability cache declares nothing usable & was ignored.');
    else if ((string)$CapabilityCacheFormat !== 'v3.8.7') warningEntry('The capability cache was written in format '.(string)$CapabilityCacheFormat.' & this release reads v3.8.7. It was ignored.');
    else if (ltrim((string)$CapabilityCacheManifest, 'vV') !== $requiredManifest) warningEntry('The capability cache was built from manifest '.(string)$CapabilityCacheManifest.' & this release ships '.$requiredManifest.'. It was ignored.');
    else {
      $CapabilityRecords = $CapabilityCache;
      $CacheIsAvailable = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $CapabilityRecords is not purged, because it is a return value.
  purgeSensitiveMemory($EnableMemoryProtection, $cachePath, $requiredManifest, $CapabilityCacheFormat, $CapabilityCacheManifest, $CapabilityCacheBuilt, $CapabilityCache);
  return array($CacheIsAvailable, $CapabilityRecords); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write a supply chain audit template.
// / Accepts the destination path, which may be empty for a default beside the logs.
// / Returns a success boolean & the path written, in that order.
// / The output is comma separated so it opens in any spreadsheet without a library & can
// / be read by a script. One dependency per row.
// / The right hand columns are deliberately EMPTY. They are what the audit is for, & a
// / template that pre-filled them would be answering questions nobody had asked yet.
// / This reads the machine & needs no authorization, because reading changes nothing.
// / The destination is NOT taken from the caller. Only a file name is, & the report is
// / always written beside the logs. An operation that needs no authorization must not be
// / able to choose where on the disk it writes.
function outputSupplyChain($outputPath) {
  // / Set variables.
  global $RequiredDependsVersion, $LogDir, $EngineDir, $DirSep, $Date, $Lol, $RunningAsRoot, $EnableMemoryProtection;
  $ReportWasWritten = $reportWasWritten = FALSE;
  $ReportPath = $reportName = $writeNotice = $fallbackNotice = '';
  $dependsManifest = $dependencyEntry = $reportRows = array();
  $manifestIsAvailable = $dependencyIsPresent = FALSE;
  $rawOutput = $detectedVersion = $dependencyStatus = $detectedDependsVersion = $reportContents = $requestedName = $binaryPath = $binaryOrigin = $servicePath = '';
  $auditEntry = array();
  $bytesWritten = 0;
  list ($manifestIsAvailable, $dependsManifest, $detectedDependsVersion) = verifyDependsManifest($RequiredDependsVersion);
  if (!$manifestIsAvailable) print($Lol.'The dependency manifest is unavailable, so no report was written.'.$Lol.$Lol);
  else {
    // / The report is written beside the logs & nowhere else.
    // / This operation needs no authorization, because reading the machine changes nothing.
    // / Writing a file somewhere the caller names is not reading the machine. Every other
    // / write in this component sits behind a token & a root check, so an unauthenticated
    // / caller must not be able to choose a destination. Only the file NAME is taken from
    // / the caller, & only its basename.
    $reportName = engineApplicationName().'-Supply-Chain-'.date('Y-m-d').'.csv';
    if (trim((string)$outputPath) !== '') {
      $requestedName = basename(trim((string)$outputPath));
      if ($requestedName === '' or strtolower(pathinfo($requestedName, PATHINFO_EXTENSION)) !== 'csv') warningEntry('A supply chain report destination was ignored because it is not a csv file name. The default was used.');
      else if ($requestedName !== trim((string)$outputPath)) {
        warningEntry('A supply chain report destination named a directory. Only the file name was used & the report was written beside the logs.');
        $reportName = $requestedName; }
      else $reportName = $requestedName; }
    $ReportPath = rtrim((string)$LogDir, $DirSep).$DirSep.$reportName;
    $reportRows[] = array(engineApplicationName().' Supply Chain Audit');
    $reportRows[] = array('Application version', engineApplicationVersion(), 'Manifest version', $detectedDependsVersion, 'Generated', (string)$Date);
    $reportRows[] = array('');
    $reportRows[] = array('Dependency', 'Package', 'Type', 'Installed Version', 'Minimum Required', 'Status', 'Required', 'Subsystem', 'Licence', 'Upstream Source', 'Purpose',
      'Reviewed By', 'Review Date', 'CVE Check Performed', 'Known Advisories', 'Licence Still Acceptable', 'Upstream Still Maintained', 'Action Required', 'Notes');
    foreach ($dependsManifest as $dependencyEntry) {
      list ($dependencyIsPresent, $detectedVersion, $dependencyStatus, $rawOutput, $binaryPath, $binaryOrigin) = resolveDependencyChoice($dependencyEntry);
      // / A program this application built for itself is audited as what the host runs, with the
      // / licence & upstream its Fallback declares.
      $auditEntry = $dependencyEntry;
      if ($binaryOrigin === 'own') list ($auditEntry, $servicePath) = ownBuildInService($dependencyEntry);
      $reportRows[] = array(
        (string)$dependencyEntry['Name'],
        ((string)$dependencyEntry['Package'] !== '' ? (string)$dependencyEntry['Package'] : 'none, '.(string)$dependencyEntry['Type']),
        (string)$dependencyEntry['Type'].($binaryOrigin === 'own' ? ', '.engineApplicationName().'\'s own build at '.$binaryPath : ''),
        ($detectedVersion === '' ? 'not detected' : $detectedVersion),
        ((string)$dependencyEntry['MinimumVersion'] === '' ? 'any' : (string)$dependencyEntry['MinimumVersion']),
        $dependencyStatus,
        ($dependencyEntry['Required'] ? 'yes' : 'no'),
        (string)$dependencyEntry['Subsystem'],
        (string)$auditEntry['License'],
        (string)$auditEntry['Source'],
        (string)$dependencyEntry['Purpose'],
        '', '', '', '', '', '', '', ''); }
    $reportContents = buildCsvContents($reportRows);
    // / Root writes its report in Engine/, the folder it owns inside the data root, through the kernel, which follows nothing.
    // / Beside the logs is the web server account's folder, which root does not write.
    // / Engine/ does not exist until -fp has run after an upgrade, so a write that fails there is reported rather than retried elsewhere.
    // / Every other account owns its log folder & writes the report there with a plain call.
    if ($RunningAsRoot) {
      $ReportPath = rtrim((string)$EngineDir, $DirSep).$DirSep.$reportName;
      list ($reportWasWritten, $writeNotice) = writeFileWithoutFollowing($ReportPath, $reportContents, 0644);
      $fallbackNotice = $writeNotice; }
    else {
      $bytesWritten = @file_put_contents($ReportPath, $reportContents);
      $reportWasWritten = ($bytesWritten === strlen($reportContents));
      if ($reportWasWritten) @chmod($ReportPath, 0644); }
    if (!$reportWasWritten) {
      print($Lol.'The supply chain report could not be written to '.$ReportPath.'.'.($RunningAsRoot ? ' '.$fallbackNotice : '').$Lol.$Lol);
      errorEntry('A supply chain report could not be written!', 33001, FALSE); }
    else {
      $ReportWasWritten = TRUE;
      logEntry('A supply chain report covering '.count($dependsManifest).' dependenc(ies) was written to '.$ReportPath.'.');
      print($Lol.'Wrote a supply chain audit template covering '.count($dependsManifest).' dependenc(ies).'.$Lol);
      print($ReportPath.$Lol);
      print($Lol.'The right hand columns are blank on purpose. They are the audit.'.$Lol.$Lol); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dependsManifest, $dependencyEntry, $reportRows, $manifestIsAvailable, $dependencyIsPresent, $detectedVersion, $dependencyStatus, $detectedDependsVersion, $reportContents, $reportWasWritten, $reportName, $writeNotice, $fallbackNotice, $bytesWritten, $requestedName, $outputPath, $rawOutput, $binaryPath, $binaryOrigin, $servicePath, $auditEntry);
  return array($ReportWasWritten, $ReportPath); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to turn rows of values into comma separated contents.
// / Accepts an array of rows, each an array of values.
// / Returns the complete contents.
// / A value carrying a comma, a quote or a newline is quoted & its quotes are doubled,
// / which is the only escaping the format has.
function buildCsvContents($reportRows) {
  // / Set variables.
  global $EnableMemoryProtection;
  $CsvContents = '';
  $reportRow = $escapedValues = array();
  $rowValue = '';
  foreach ($reportRows as $reportRow) {
    $escapedValues = array();
    foreach ($reportRow as $rowValue) {
      $rowValue = (string)$rowValue;
      // / A spreadsheet executes a value beginning with =, +, - or @ as a formula.
      // / Some of these values are parsed out of the version banner of an external binary,
      // / & this file exists to be opened in a spreadsheet by an operator, so a crafted
      // / banner would otherwise run when the audit was read. A leading apostrophe is the
      // / documented way to force a literal & is stripped by the spreadsheet on display.
      if ($rowValue !== '' && strpbrk(substr($rowValue, 0, 1), '=+-@') !== FALSE) $rowValue = '\''.$rowValue;
      if (strpbrk($rowValue, ",\"\r\n") !== FALSE) $rowValue = '"'.str_replace('"', '""', $rowValue).'"';
      $escapedValues[] = $rowValue; }
    $CsvContents = $CsvContents.implode(',', $escapedValues).PHP_EOL; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $reportRow, $escapedValues, $rowValue, $reportRows);
  return $CsvContents; }
// / -----------------------------------------------------------------------------------