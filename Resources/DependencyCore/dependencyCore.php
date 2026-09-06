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
// / v3.8.1.
// / A detachable dependency management component. convertCore.php runs without it.
// / This file defines functions only. setupCore.php & convertCore.php dispatch into them.
// /
// / CALL METHOD. This component is required into the running process, the same way
// / coreManager.php & setupCore.php are. It is NOT invoked over the command line & is NOT
// / shelled out to. A separate process needs a startup key to prove who launched it. A
// / component required into this one inherits the authorization the caller already has.
// /
// / A KEY IS STILL REQUIRED TO CHANGE ANYTHING. checkDepends reads the machine & needs
// / nothing. installDepends, updateDepends & uninstallDepends rewrite it & refuse without
// / a token convertCore.php derived from the install secret. A component that can be
// / reached some way nobody anticipated still cannot install a package.
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
$DependencyCoreVersion = 'v3.9.0';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to build the environment a dependency command runs under.
// / Accepts nothing. Returns a run of exports to place in front of a command, ending in a
// / semicolon & a space.
// /
// / A dependency command runs OUTSIDE the sandbox & that is the whole reason this exists.
// / sandboxCommand() already points every writable location a tool reaches for at a tmpfs
// / inside the namespace, so a conversion creates no state anywhere it should not.
// / A version probe & a capability probe get no such treatment. They run as the web server
// / account, with the working directory set to the installation root, & with HOME unset,
// / because a web server does not set it.
// / A library that reads HOME, finds nothing & falls back to the working directory then
// / writes into the installation itself. A .cache directory appearing beside convertCore.php
// / is fontconfig, or a shader cache, or a LibreOffice profile, doing exactly that.
// /
// / Every variable below is one a tool actually reaches for. They are pointed at a
// / directory under the system temporary location, which is disposable by definition & is
// / writable by whichever account is running.
// /
// / A prefix is correct here & would NOT be correct inside the sandbox. exec() runs the
// / command through a shell, which reads NAME=value as an assignment. bwrap execs directly
// / & would read the same text as the name of a program to run.
// /
// / A package manager command is deliberately not given this. apt has its own cache in its
// / own place & redirecting it would be a change to the machine rather than to this
// / application.
function dependencyEnvironmentPrefix() {
  // / Set variables.
  global $ApplicationName, $EnableMemoryProtection;
  $EnvironmentPrefix = '';
  $scratchRoot = $applicationSlug = '';
  $applicationSlug = (isset($ApplicationName) && (string)$ApplicationName !== '') ? preg_replace('/[^A-Za-z0-9]/', '', (string)$ApplicationName) : 'HRConvert2';
  $scratchRoot = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$applicationSlug.'-probe';
  // / The directory is created rather than left to the tool, because a tool that cannot
  // / create it falls back to somewhere else & the point of this is to stop that happening.
  if (!is_dir($scratchRoot)) @mkdir($scratchRoot, 0700, TRUE);
  // / These are exports followed by a semicolon rather than NAME=value prefixes.
  // / A prefix applies only to a simple command. A source build is wrapped in a subshell
  // / so its exit code can be read, & HOME=x ( ... ) is a syntax error in sh, which is what
  // / --install-depends printed on the first source build it attempted.
  // / An export applies to everything that follows it in the same shell, including a
  // / subshell & every command inside one, & the shell is discarded when the call returns.
  // / The working directory moves too, & that is a separate protection from the variables.
  // / A tool that ignores HOME & writes to a relative path writes wherever the process
  // / happens to be standing, & for a probe that is the installation root. cd puts it
  // / somewhere disposable instead, so both kinds of tool land in the same scratch space.
  // / cd is first, so a failure to enter the directory stops the command rather than
  // / running it in the installation root anyway.
  $EnvironmentPrefix = 'cd '.escapeshellarg($scratchRoot).' || exit 1; export HOME='.escapeshellarg($scratchRoot)
    .'; export XDG_CACHE_HOME='.escapeshellarg($scratchRoot.DIRECTORY_SEPARATOR.'.cache')
    .'; export XDG_CONFIG_HOME='.escapeshellarg($scratchRoot.DIRECTORY_SEPARATOR.'.config')
    .'; export XDG_DATA_HOME='.escapeshellarg($scratchRoot.DIRECTORY_SEPARATOR.'.local')
    .'; export XDG_RUNTIME_DIR='.escapeshellarg($scratchRoot)
    .'; ';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $scratchRoot, $applicationSlug);
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
  $manifestPath = $InstLoc.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'depends.php';
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
  global $EnableMemoryProtection;
  $DependencyIsPresent = FALSE;
  $DetectedVersion = '';
  $DependencyStatus = 'absent';
  $RawOutput = '';
  $binaryPath = $versionOutput = '';
  $versionMatches = $commandOutput = array();
  $commandExitCode = 1;
  $commandWasExecuted = FALSE;
  // / A dependency with no binary is a library. Its version command is the only evidence.
  if ((string)$dependencyEntry['Binary'] !== '') {
    $binaryPath = locateDependency((string)$dependencyEntry['Binary']);
    if ($binaryPath === '') $DependencyStatus = 'absent';
    else $DependencyIsPresent = TRUE; }
  else if ((string)$dependencyEntry['VersionCommand'] !== '') {
    // / A library has no binary, so its version command is both the presence test & the
    // / version read. The output is kept & reused below rather than running it a second
    // / time, which spawned two processes per library & repeated any side effect.
    exec(dependencyEnvironmentPrefix().(string)$dependencyEntry['VersionCommand'].' 2>&1', $commandOutput, $commandExitCode);
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
        exec(dependencyEnvironmentPrefix().(string)$dependencyEntry['VersionCommand'].' 2>&1', $commandOutput, $commandExitCode); }
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
        else if (compareVersionMinimum($DetectedVersion, (string)$dependencyEntry['MinimumVersion'])) $DependencyStatus = 'ok';
        else $DependencyStatus = 'outdated'; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $binaryPath, $versionOutput, $versionMatches, $commandOutput, $commandExitCode, $commandWasExecuted, $dependencyEntry);
  return array($DependencyIsPresent, $DetectedVersion, $DependencyStatus, $RawOutput); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report the state of every dependency.
// / Accepts a subsystem filter, which may be empty for all of them.
// / Returns a readiness boolean, an array of findings & the count of optional problems,
// / in that order.
// / Readiness is FALSE only when something marked Required is missing or too old. An
// / optional dependency that is absent downgrades a subsystem & does not fail the check.
// / THIS CHANGES NOTHING & NEEDS NO AUTHORIZATION. It is safe to run from anywhere.
function checkDepends($subsystemFilter) {
  // / Set variables.
  global $RequiredDependsVersion, $Verbose, $EnableMemoryProtection;
  $DependenciesAreReady = TRUE;
  $DependencyFindings = array();
  $OptionalProblems = 0;
  $finding = array();
  $dependsManifest = $dependencyEntry = $missingRequirements = array();
  $manifestIsAvailable = $dependencyIsPresent = FALSE;
  $detectedVersion = $dependencyStatus = $detectedDependsVersion = $requirementName = $rawOutput = '';
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
    foreach ($dependsManifest as $dependencyEntry) {
      list ($dependencyIsPresent, $detectedVersion, $dependencyStatus, $rawOutput) = resolveDependencyState($dependencyEntry);
      $presenceMap[(string)$dependencyEntry['Name']] = ($dependencyStatus === 'ok' or $dependencyStatus === 'unknown-version');
      $stateMap[(string)$dependencyEntry['Name']] = array($detectedVersion, $dependencyStatus, $rawOutput); }
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
      if ($dependencyEntry['Required'] && ($dependencyStatus === 'absent' or $dependencyStatus === 'outdated')) $DependenciesAreReady = FALSE;
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
        'Output' => $rawOutput); } }
  // / Count what is wrong but optional, so the summary can say so. A report that says
  // / every requirement is met, printed directly beneath two failures, invites an operator
  // / to believe the failures do not matter without ever saying that they do not.
  foreach ($DependencyFindings as $finding) {
    if (!$finding['Required'] && ($finding['Status'] === 'absent' or $finding['Status'] === 'outdated')) $OptionalProblems++; }
  if ($Verbose) logEntry('Dependency check completed across '.count($DependencyFindings).' dependenc(ies). Ready: '.($DependenciesAreReady ? 'YES' : 'NO').'. Optional problems: '.$OptionalProblems.'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dependsManifest, $dependencyEntry, $missingRequirements, $manifestIsAvailable, $dependencyIsPresent, $detectedVersion, $dependencyStatus, $detectedDependsVersion, $requirementName, $rawOutput, $presenceMap, $stateMap, $finding, $subsystemFilter);
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
    // / A problem is anything that is not ok & not deliberately unverifiable.
    if ($finding['Status'] !== 'ok' && $finding['Status'] !== 'unverifiable') {
      if ($finding['Required']) $requiredProblems++;
      else $optionalProblems++; }
    $LinesPrinted++; }
  // / Report both counts. Saying only that every REQUIRED dependency is fine, while the
  // / table above shows two optional ones broken, reads as a contradiction.
  print($Lol.($requiredProblems === 0 ? 'Every required dependency is present & current.' : $requiredProblems.' REQUIRED dependenc(ies) are missing or too old. The subsystems that need them will refuse.').$Lol);
  if ($optionalProblems > 0) print($optionalProblems.' optional dependenc(ies) are missing or unverified. Those subsystems are unavailable & everything else works.'.$Lol);
  print($Lol);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $optionalProblems, $requiredProblems, $finding, $dependencyFindings);
  return $LinesPrinted; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to install one dependency.
// / Accepts one manifest entry & the package manager in use, in that order.
// / Returns a success boolean & whatever the package manager printed, in that order.
// / A dependency of Type manual is NEVER installed. Its licence forbids redistribution or
// / it needs a decision this utility is not entitled to make. It is reported instead.
// / A dependency of Type source is not built here either. Building from source is its own
// / problem & is handled separately.
function installOneDependency($dependencyEntry, $packageManager) {
  // / Set variables.
  // / $Lol is declared because a source build reports its progress. A build takes minutes
  // / & an operator watching a silent terminal has no way to tell it apart from a hang.
  global $Lol, $EnableMemoryProtection;
  $InstallSucceeded = FALSE;
  $ReturnData = '';
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
    $ReturnData = 'This dependency ships with HRConvert2 & needs no installation.'; }
  else if ((string)$dependencyEntry['Type'] === 'manual') $ReturnData = 'This dependency is never installed automatically. Install it by hand from '.(string)$dependencyEntry['Source'].'.';
  // / A source built dependency runs the command its manifest entry carries.
  // / This was refused outright until v3.8.9, which meant anything not in a package
  // / repository had to be installed by a hand written script kept in step by hand.
  // / A build takes minutes rather than seconds & prints a great deal while it works.
  // / Only the tail of that output is kept, because a full compiler log in a report helps
  // / nobody & the log already records that the build ran.
  // / A build with no command is a manifest that has not said how, & says so.
  else if ((string)$dependencyEntry['Type'] === 'source') {
    if (!isset($dependencyEntry['BuildCommand']) or trim((string)$dependencyEntry['BuildCommand']) === '') $ReturnData = 'This dependency is built from source & its manifest entry names no build command.';
    else {
      print('  '.str_pad((string)$dependencyEntry['Name'], 20).'building from source, this takes several minutes'.$Lol);
      logEntry('Building '.(string)$dependencyEntry['Name'].' from source.');
      $commandOutput = array();
      exec(dependencyEnvironmentPrefix().'('.(string)$dependencyEntry['BuildCommand'].') 2>&1', $commandOutput, $commandExitCode);
      $ReturnData = implode(PHP_EOL, array_slice($commandOutput, -12));
      if ($commandExitCode !== 0) warningEntry('The source build for '.(string)$dependencyEntry['Name'].' exited with code '.$commandExitCode.'.');
      else {
        // / A build that reported success is still only believed once the thing it built
        // / answers for itself, exactly as a package install is.
        if ((string)$dependencyEntry['VersionCommand'] === '') $InstallSucceeded = TRUE;
        else {
          $commandOutput = array();
          exec(dependencyEnvironmentPrefix().(string)$dependencyEntry['VersionCommand'].' 2>&1', $commandOutput, $commandExitCode);
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
      exec($installCommand, $commandOutput, $commandExitCode);
      $ReturnData = implode(PHP_EOL, $commandOutput);
      // / An extension is only installed once PHP can see it. A package that unpacked but
      // / was never enabled reports success from the package manager & fails every use.
      // / An entry declaring no version command has nothing to ask, so the package manager
      // / is the whole of the evidence. Running an empty command returned a non zero code &
      // / reported an extension PHP could not load, which was advice for a problem that did
      // / not exist.
      if ($commandExitCode === 0) {
        if ((string)$dependencyEntry['VersionCommand'] === '') $InstallSucceeded = TRUE;
        else {
          $commandOutput = array();
          exec(dependencyEnvironmentPrefix().(string)$dependencyEntry['VersionCommand'].' 2>&1', $commandOutput, $commandExitCode);
          if ($commandExitCode === 0) $InstallSucceeded = TRUE;
          else $ReturnData = 'The package installed but PHP still cannot load the extension. The web server may need restarting.'; } } } }
  else if ((string)$dependencyEntry['Type'] === 'pip') {
    $installCommand = 'pip3 install --break-system-packages '.escapeshellarg($packageList).' 2>&1';
    exec($installCommand, $commandOutput, $commandExitCode);
    $ReturnData = implode(PHP_EOL, $commandOutput);
    if ($commandExitCode === 0) $InstallSucceeded = TRUE; }
  else if ($packageManager === '') $ReturnData = 'No package manager is available on this host.';
  else {
    // / Every package name is escaped separately. A manifest is not user input, but a
    // / manifest from an update is not this release's manifest either.
    $installCommand = dependencyInstallCommand($packageManager, $packageList).' 2>&1';
    exec($installCommand, $commandOutput, $commandExitCode);
    $ReturnData = implode(PHP_EOL, $commandOutput);
    if ($commandExitCode === 0) $InstallSucceeded = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $installCommand, $packageList, $commandOutput, $commandExitCode, $dependencyEntry, $packageManager);
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
// / A function to install every dependency that is absent or too old.
// / Accepts the authorization token, a subsystem filter & a confirmation boolean.
// / Returns a success boolean & the number installed, in that order.
// / The manifest order is the install order & is followed exactly.
// / A dependency whose own requirements failed is skipped rather than attempted, because
// / installing it would fail & would bury the reason under a second error.
function installDepends($authorizationToken, $subsystemFilter, $operatorConfirmed) {
  // / Set variables.
  global $RequiredDependsVersion, $Lol, $Verbose, $EnableMemoryProtection;
  $InstallSucceeded = TRUE;
  $DependenciesInstalled = 0;
  $dependsManifest = $dependencyEntry = $installedNames = $missingRequirements = $commandOutput = array();
  $commandExitCode = 1;
  $manifestIsAvailable = $dependencyIsPresent = $installOneSucceeded = $callerIsAuthorized = FALSE;
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
        $operatorChoice = askOperator('Type YES to continue. Anything else cancels. ');
        if ($operatorChoice !== 'YES') { print($Lol.'Cancelled. Nothing was installed.'.$Lol.$Lol); $InstallSucceeded = FALSE; } }
      if ($InstallSucceeded) {
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
          list ($dependencyIsPresent, $detectedVersion, $dependencyStatus, $rawOutput) = resolveDependencyState($dependencyEntry);
          if ($dependencyStatus === 'ok' or $dependencyStatus === 'unknown-version') $installedNames[(string)$dependencyEntry['Name']] = TRUE; }
        print($Lol.'Installing dependencies in manifest order.'.$Lol);
        foreach ($dependsManifest as $dependencyEntry) {
          if (trim((string)$subsystemFilter) !== '' && stripos((string)$dependencyEntry['Subsystem'], trim((string)$subsystemFilter)) === FALSE) continue;
          list ($dependencyIsPresent, $detectedVersion, $dependencyStatus, $rawOutput) = resolveDependencyState($dependencyEntry);
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
        print($Lol.'Installed '.$DependenciesInstalled.' dependenc(ies).'.$Lol.$Lol); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dependsManifest, $dependencyEntry, $installedNames, $manifestIsAvailable, $dependencyIsPresent, $installOneSucceeded, $callerIsAuthorized, $detectedVersion, $dependencyStatus, $detectedDependsVersion, $packageManager, $returnData, $requirementName, $operatorChoice, $missingRequirements, $commandOutput, $commandExitCode, $authorizationToken, $subsystemFilter, $operatorConfirmed, $rawOutput);
  return array($InstallSucceeded, $DependenciesInstalled); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to upgrade every dependency that is already installed.
// / Accepts the authorization token, a subsystem filter & a confirmation boolean.
// / Returns a success boolean & the number upgraded, in that order.
// / An absent dependency is NOT installed here. Updating & installing are different
// / intentions & an operator who asked to update did not ask to add anything.
function updateDepends($authorizationToken, $subsystemFilter, $operatorConfirmed) {
  // / Set variables.
  global $RequiredDependsVersion, $Lol, $EnableMemoryProtection;
  $UpdateSucceeded = TRUE;
  $DependenciesUpdated = 0;
  $dependsManifest = $dependencyEntry = array();
  $manifestIsAvailable = $dependencyIsPresent = $callerIsAuthorized = FALSE;
  $rawOutput = $detectedVersion = $dependencyStatus = $detectedDependsVersion = $packageManager = $updateCommand = $operatorChoice = '';
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
        $operatorChoice = askOperator('Type YES to continue. Anything else cancels. ');
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
          list ($dependencyIsPresent, $detectedVersion, $dependencyStatus, $rawOutput) = resolveDependencyState($dependencyEntry);
          if ($dependencyStatus === 'absent') { print('  '.str_pad((string)$dependencyEntry['Name'], 20).'not installed, skipped'.$Lol); continue; }
          $commandOutput = array();
          // / The command is tested before the redirect is appended. Comparing the finished
          // / string against ' 2>&1' worked only while that suffix never changed, & an empty
          // / command would have been executed the moment it did.
          if ((string)$dependencyEntry['Type'] === 'pip') $updateCommand = 'pip3 install --break-system-packages --upgrade '.escapeshellarg((string)$dependencyEntry['Package']);
          else $updateCommand = dependencyPackageCommand($packageManager, (string)$dependencyEntry['Package'], 'update');
          if (trim($updateCommand) === '') { print('  '.str_pad((string)$dependencyEntry['Name'], 20).'no package manager, skipped'.$Lol); continue; }
          exec($updateCommand.' 2>&1', $commandOutput, $commandExitCode);
          if ($commandExitCode === 0) {
            $DependenciesUpdated++;
            print('  '.str_pad((string)$dependencyEntry['Name'], 20).'upgraded'.$Lol); }
          else {
            print('  '.str_pad((string)$dependencyEntry['Name'], 20).'FAILED'.$Lol);
            warningEntry('Dependency '.(string)$dependencyEntry['Name'].' could not be upgraded.'); } }
        print($Lol.'Upgraded '.$DependenciesUpdated.' dependenc(ies).'.$Lol.$Lol); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dependsManifest, $dependencyEntry, $manifestIsAvailable, $dependencyIsPresent, $callerIsAuthorized, $detectedVersion, $dependencyStatus, $detectedDependsVersion, $packageManager, $updateCommand, $operatorChoice, $commandOutput, $commandExitCode, $authorizationToken, $subsystemFilter, $operatorConfirmed, $rawOutput);
  return array($UpdateSucceeded, $DependenciesUpdated); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove dependencies from this host.
// / Accepts the authorization token, a subsystem filter & a confirmation boolean.
// / Returns a success boolean & the number removed, in that order.
// / A SUBSYSTEM FILTER IS MANDATORY. Removing everything in the manifest would take PHP,
// / Apache & the package manager with it & leave a machine that cannot be recovered
// / without physical access. This function refuses to run without a filter, whatever the
// / operator has confirmed.
// / Nothing in the Core subsystem is ever removed, whatever the operator confirmed.
// / A dependency marked Required in the Sandbox subsystem is never removed either.
function uninstallDepends($authorizationToken, $subsystemFilter, $operatorConfirmed) {
  // / Set variables.
  global $RequiredDependsVersion, $Lol, $EnableMemoryProtection;
  $UninstallSucceeded = TRUE;
  $DependenciesRemoved = 0;
  $dependsManifest = $dependencyEntry = array();
  $manifestIsAvailable = $callerIsAuthorized = FALSE;
  $detectedDependsVersion = $packageManager = $removeCommand = $operatorChoice = '';
  $commandOutput = array();
  $commandExitCode = 1;
  $callerIsAuthorized = validateDependencyAuthorization($authorizationToken);
  if (!$callerIsAuthorized) $UninstallSucceeded = FALSE;
  else if (trim((string)$subsystemFilter) === '') {
    $UninstallSucceeded = FALSE;
    print($Lol.'A subsystem must be named. Removing every dependency would remove PHP,'.$Lol);
    print('Apache & the package manager, & leave a host that cannot be recovered remotely.'.$Lol);
    print($Lol.'  sudo php convertCore.php --setup --uninstall-depends --subsystem=Ebooks'.$Lol.$Lol);
    warningEntry('An unfiltered dependency removal was refused.'); }
  else {
    list ($manifestIsAvailable, $dependsManifest, $detectedDependsVersion) = verifyDependsManifest($RequiredDependsVersion);
    if (!$manifestIsAvailable) $UninstallSucceeded = FALSE;
    else {
      $packageManager = detectPackageManager();
      if (!$operatorConfirmed) {
        print($Lol.'This will REMOVE packages providing the '.trim((string)$subsystemFilter).' subsystem.'.$Lol);
        print('Anything else on this host that uses them will stop working.'.$Lol);
        $operatorChoice = askOperator('Type YES to continue. Anything else cancels. ');
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
        print($Lol.'Removed '.$DependenciesRemoved.' dependenc(ies).'.$Lol.$Lol); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dependsManifest, $dependencyEntry, $manifestIsAvailable, $callerIsAuthorized, $detectedDependsVersion, $packageManager, $removeCommand, $operatorChoice, $commandOutput, $commandExitCode, $authorizationToken, $subsystemFilter, $operatorConfirmed);
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
// / The two directions carry their OWN state & an earlier version did not.
// / Assimp answers with two commands. listext reports what it imports & listexport reports
// / what it exports, & one of them can fail while the other succeeds. A single state made
// / a successful listext report the whole dependency as detected, while the write set sat
// / empty because listexport never answered. A narrowing pass reading that would conclude
// / Assimp can write nothing at all & would refuse every model conversion on the machine.
// / That is the exact failure this design exists to prevent, one level further in.
function detectDependencyCapability($dependencyEntry) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ReadState = $WriteState = 'unknown';
  $ReadableExtensions = $WritableExtensions = array();
  $commandSucceeded = $writeSucceeded = FALSE;
  $readTokens = $writeTokens = $secondRead = $secondWrite = $aliasTable = array();
  $binaryPath = $firstDirection = '';
  if ((string)$dependencyEntry['Binary'] !== '') {
    $binaryPath = locateDependency((string)$dependencyEntry['Binary']);
    if ($binaryPath === '') $ReadState = $WriteState = 'unavailable'; }
  if ($ReadState !== 'unavailable' && isset($dependencyEntry['CapabilityCommand']) && (string)$dependencyEntry['CapabilityCommand'] !== '') {
    $aliasTable = (isset($dependencyEntry['CapabilityAliases']) && is_array($dependencyEntry['CapabilityAliases'])) ? $dependencyEntry['CapabilityAliases'] : array();
    $firstDirection = (isset($dependencyEntry['CapabilityDirection']) ? (string)$dependencyEntry['CapabilityDirection'] : 'readwrite');
    list ($commandSucceeded, $readTokens, $writeTokens) = runCapabilityCommand(
      (string)$dependencyEntry['CapabilityCommand'],
      (isset($dependencyEntry['CapabilityStyle']) ? (string)$dependencyEntry['CapabilityStyle'] : 'matrix'),
      (string)$dependencyEntry['CapabilityPattern'],
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
    else warningEntry('Capability detection for '.(string)$dependencyEntry['Name'].' produced nothing usable. Its declared formats will be trusted instead.');
    // / A dependency answering two questions with two commands runs the second one here.
    // / Assimp reports what it can import with listext & what it can export with
    // / listexport, & neither command mentions the other direction at all.
    if (isset($dependencyEntry['CapabilityWriteCommand']) && (string)$dependencyEntry['CapabilityWriteCommand'] !== '') {
      list ($writeSucceeded, $secondRead, $secondWrite) = runCapabilityCommand(
        (string)$dependencyEntry['CapabilityWriteCommand'],
        (isset($dependencyEntry['CapabilityWriteStyle']) ? (string)$dependencyEntry['CapabilityWriteStyle'] : 'list'),
        (string)$dependencyEntry['CapabilityWritePattern'],
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
        warningEntry('The write capability command for '.(string)$dependencyEntry['Name'].' produced nothing usable. Its declared output formats will be trusted instead.'); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / The two extension sets are not purged, because they are return values.
  purgeSensitiveMemory($EnableMemoryProtection, $commandSucceeded, $writeSucceeded, $readTokens, $writeTokens, $secondRead, $secondWrite, $aliasTable, $binaryPath, $firstDirection, $dependencyEntry);
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
function buildCapabilityCache() {
  // / Set variables.
  // / The pin the core sets is $RequiredDependsVersion. $DependsVersion is what the
  // / manifest declares & is not set until the manifest has been read, so passing it here
  // / handed verifyDependsManifest() an empty string & every manifest was refused for
  // / reporting a version this core did not require.
  global $ConvertLoc, $DirSep, $RequiredDependsVersion, $RunningAsRoot, $ApacheUser, $EnableMemoryProtection;
  $CacheWasBuilt = FALSE;
  $DetectedCount = 0;
  $manifestIsAvailable = FALSE;
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
      .'// / HRConvert2 capability cache. Written by Dependency Core.'.PHP_EOL
      .'// / This file is generated. Every edit made here is lost the next time it is built.'.PHP_EOL
      .'// / Rebuild it with --setup --detect-capabilities.'.PHP_EOL
      .'if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die(\'ERROR!!! HRConvert2-33003, A generated cache cannot be loaded directly!\'.PHP_EOL);'.PHP_EOL
      .'$CapabilityCacheFormat = \'v3.8.7\';'.PHP_EOL
      .'$CapabilityCacheManifest = '.var_export((string)$detectedDependsVersion, TRUE).';'.PHP_EOL
      .'$CapabilityCacheBuilt = '.var_export(time(), TRUE).';'.PHP_EOL
      .'$CapabilityCache = '.var_export($cacheRecords, TRUE).';'.PHP_EOL
      .'?>'.PHP_EOL;
    $cachePath = rtrim((string)$ConvertLoc, $DirSep).$DirSep.'capability-cache.php';
    if (@file_put_contents($cachePath, $cacheContents, LOCK_EX) === FALSE) warningEntry('The capability cache could not be written to '.$cachePath.'. Detection will be repeated on the next run & conversions will use declared formats.');
    else {
      // / Detection is run from the command line & usually as root, so the file it writes
      // / is owned by root & the web server cannot read it. Every request would then find
      // / no cache, fall back to declared formats, & nothing would say why.
      // / This is the same mistake a configuration backup made before it was corrected.
      if ($RunningAsRoot && (string)$ApacheUser !== '') @chown($cachePath, (string)$ApacheUser);
      @chmod($cachePath, 0640);
      $CacheWasBuilt = TRUE;
      logEntry('Capability cache written. '.$DetectedCount.' of '.count($cacheRecords).' dependencies answered.'); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $manifestIsAvailable, $dependsManifest, $dependencyEntry, $cacheRecords, $readableExtensions, $writableExtensions, $readState, $writeState, $detectedDependsVersion, $cachePath, $cacheContents);
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
function readCapabilityCache() {
  // / Set variables.
  // / $CoreLoaded is declared so the guard at the top of the generated file can see it.
  // / The require below happens inside this function, so a global the file tests must be
  // / in this scope or a valid cache refuses to load itself.
  // / $RequiredDependsVersion for the same reason buildCapabilityCache() uses it. This
  // / function may run before the manifest has been read, & a comparison against a value
  // / that is not set yet refuses every cache that was ever written.
  global $ConvertLoc, $DirSep, $RequiredDependsVersion, $CoreLoaded, $EnableMemoryProtection;
  $CacheIsAvailable = FALSE;
  $CapabilityRecords = array();
  $cachePath = $requiredManifest = '';
  $CapabilityCacheFormat = $CapabilityCacheManifest = $CapabilityCacheBuilt = $CapabilityCache = NULL;
  $cachePath = rtrim((string)$ConvertLoc, $DirSep).$DirSep.'capability-cache.php';
  $requiredManifest = ltrim((string)$RequiredDependsVersion, 'vV');
  if (file_exists($cachePath)) {
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
  global $RequiredDependsVersion, $LogDir, $DirSep, $HRConvertVersion, $Date, $Lol, $EnableMemoryProtection;
  $ReportWasWritten = FALSE;
  $ReportPath = '';
  $dependsManifest = $dependencyEntry = $reportRows = array();
  $manifestIsAvailable = $dependencyIsPresent = FALSE;
  $rawOutput = $detectedVersion = $dependencyStatus = $detectedDependsVersion = $reportContents = $requestedName = '';
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
    $ReportPath = rtrim((string)$LogDir, $DirSep).$DirSep.'HRConvert2-Supply-Chain-'.date('Y-m-d').'.csv';
    if (trim((string)$outputPath) !== '') {
      $requestedName = basename(trim((string)$outputPath));
      if ($requestedName === '' or strtolower(pathinfo($requestedName, PATHINFO_EXTENSION)) !== 'csv') warningEntry('A supply chain report destination was ignored because it is not a csv file name. The default was used.');
      else if ($requestedName !== trim((string)$outputPath)) {
        warningEntry('A supply chain report destination named a directory. Only the file name was used & the report was written beside the logs.');
        $ReportPath = rtrim((string)$LogDir, $DirSep).$DirSep.$requestedName; }
      else $ReportPath = rtrim((string)$LogDir, $DirSep).$DirSep.$requestedName; }
    $reportRows[] = array('HRConvert2 Supply Chain Audit');
    $reportRows[] = array('Application version', (string)$HRConvertVersion, 'Manifest version', $detectedDependsVersion, 'Generated', (string)$Date);
    $reportRows[] = array('');
    $reportRows[] = array('Dependency', 'Package', 'Type', 'Installed Version', 'Minimum Required', 'Status', 'Required', 'Subsystem', 'Licence', 'Upstream Source', 'Purpose',
      'Reviewed By', 'Review Date', 'CVE Check Performed', 'Known Advisories', 'Licence Still Acceptable', 'Upstream Still Maintained', 'Action Required', 'Notes');
    foreach ($dependsManifest as $dependencyEntry) {
      list ($dependencyIsPresent, $detectedVersion, $dependencyStatus, $rawOutput) = resolveDependencyState($dependencyEntry);
      $reportRows[] = array(
        (string)$dependencyEntry['Name'],
        ((string)$dependencyEntry['Package'] !== '' ? (string)$dependencyEntry['Package'] : 'none, '.(string)$dependencyEntry['Type']),
        (string)$dependencyEntry['Type'],
        ($detectedVersion === '' ? 'not detected' : $detectedVersion),
        ((string)$dependencyEntry['MinimumVersion'] === '' ? 'any' : (string)$dependencyEntry['MinimumVersion']),
        $dependencyStatus,
        ($dependencyEntry['Required'] ? 'yes' : 'no'),
        (string)$dependencyEntry['Subsystem'],
        (string)$dependencyEntry['License'],
        (string)$dependencyEntry['Source'],
        (string)$dependencyEntry['Purpose'],
        '', '', '', '', '', '', '', ''); }
    $reportContents = buildCsvContents($reportRows);
    $bytesWritten = @file_put_contents($ReportPath, $reportContents);
    if ($bytesWritten !== strlen($reportContents)) {
      print($Lol.'The supply chain report could not be written to '.$ReportPath.'.'.$Lol.$Lol);
      errorEntry('A supply chain report could not be written!', 33001, FALSE); }
    else {
      @chmod($ReportPath, 0644);
      $ReportWasWritten = TRUE;
      logEntry('A supply chain report covering '.count($dependsManifest).' dependenc(ies) was written to '.$ReportPath.'.');
      print($Lol.'Wrote a supply chain audit template covering '.count($dependsManifest).' dependenc(ies).'.$Lol);
      print($ReportPath.$Lol);
      print($Lol.'The right hand columns are blank on purpose. They are the audit.'.$Lol.$Lol); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dependsManifest, $dependencyEntry, $reportRows, $manifestIsAvailable, $dependencyIsPresent, $detectedVersion, $dependencyStatus, $detectedDependsVersion, $reportContents, $bytesWritten, $requestedName, $outputPath, $rawOutput);
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