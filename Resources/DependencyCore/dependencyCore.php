<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRConvert2, Copyright on 8/28/2026 by Justin Grimes, www.github.com/zelon88
// /
// / LICENSE INFORMATION ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / APPLICATION INFORMATION ...
// / This application is designed to provide a web-interface for converting file formats
// / on a server for users of any web browser without authentication.
// /
// / FILEINFORMATION ...
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
// / HARDWARE REQUIREMENTS ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / DEPENDENCY REQUIREMENTS ...
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
$DependencyCoreVersion = 'v3.8.1';
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
  global $InstLoc, $EnableMemoryProtection, $CoreLoaded;
  $ManifestIsAvailable = FALSE;
  $DependsManifest = array();
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
      else if ($cleanDetected !== $cleanRequired) warningEntry('The dependency manifest reports v'.$cleanDetected.' & this core requires v'.$cleanRequired.'. Dependency management is unavailable.');
      else {
        require ($manifestPath);
        if (!isset($DependsManifest) or !is_array($DependsManifest) or count($DependsManifest) < 1) warningEntry('The dependency manifest loaded but declares nothing.');
        else $ManifestIsAvailable = TRUE; } } }
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
    print('You are running as '.($CurrentUser === '' ? 'an unidentified user' : $CurrentUser).'.'.$Lol.$Lol); }
  else $CallerIsAuthorized = TRUE;
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
  // / A dependency with no binary is a library. Its version command is the only evidence.
  if ((string)$dependencyEntry['Binary'] !== '') {
    $binaryPath = locateDependency((string)$dependencyEntry['Binary']);
    if ($binaryPath === '') $DependencyStatus = 'absent';
    else $DependencyIsPresent = TRUE; }
  else if ((string)$dependencyEntry['VersionCommand'] !== '') {
    exec((string)$dependencyEntry['VersionCommand'].' 2>&1', $commandOutput, $commandExitCode);
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
      $commandOutput = array();
      exec((string)$dependencyEntry['VersionCommand'].' 2>&1', $commandOutput, $commandExitCode);
      $versionOutput = implode(PHP_EOL, $commandOutput);
      $RawOutput = trim((string)(isset($commandOutput[0]) ? $commandOutput[0] : ''));
      // / A dependency that declares no pattern is not asking to be version checked. Its
      // / command ran & succeeded, which is the whole of what it claims to prove.
      // / Leaving it as unknown-version reported a fault where there is nothing to know.
      if ((string)$dependencyEntry['VersionPattern'] === '') $DependencyStatus = 'ok';
      else if (preg_match((string)$dependencyEntry['VersionPattern'], $versionOutput, $versionMatches)) {
        $DetectedVersion = $versionMatches[1];
        if ((string)$dependencyEntry['MinimumVersion'] === '') $DependencyStatus = 'ok';
        else if (compareVersionMinimum($DetectedVersion, (string)$dependencyEntry['MinimumVersion'])) $DependencyStatus = 'ok';
        else $DependencyStatus = 'outdated'; } } }
  purgeSensitiveMemory($EnableMemoryProtection, $binaryPath, $versionOutput, $versionMatches, $commandOutput, $commandExitCode, $dependencyEntry);
  return array($DependencyIsPresent, $DetectedVersion, $DependencyStatus, $RawOutput); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report the state of every dependency.
// / Accepts a subsystem filter, which may be empty for all of them.
// / Returns a readiness boolean & an array of findings, in that order.
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
  $presenceMap = array();
  list ($manifestIsAvailable, $dependsManifest, $detectedDependsVersion) = verifyDependsManifest($RequiredDependsVersion);
  if (!$manifestIsAvailable) $DependenciesAreReady = FALSE;
  else {
    foreach ($dependsManifest as $dependencyEntry) {
      if (trim((string)$subsystemFilter) !== '' && stripos((string)$dependencyEntry['Subsystem'], trim((string)$subsystemFilter)) === FALSE) continue;
      list ($dependencyIsPresent, $detectedVersion, $dependencyStatus, $rawOutput) = resolveDependencyState($dependencyEntry);
      $presenceMap[(string)$dependencyEntry['Name']] = ($dependencyStatus === 'ok' or $dependencyStatus === 'unknown-version');
      // / A dependency whose own requirements are absent is reported against them rather
      // / than against itself, because installing it first would fail anyway.
      $missingRequirements = array();
      foreach ((array)$dependencyEntry['Requires'] as $requirementName) {
        if (isset($presenceMap[$requirementName]) && $presenceMap[$requirementName] !== TRUE) $missingRequirements[] = $requirementName; }
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
  purgeSensitiveMemory($EnableMemoryProtection, $dependsManifest, $dependencyEntry, $missingRequirements, $manifestIsAvailable, $dependencyIsPresent, $detectedVersion, $dependencyStatus, $detectedDependsVersion, $requirementName, $rawOutput, $presenceMap, $finding, $subsystemFilter);
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
  purgeSensitiveMemory($EnableMemoryProtection, $finding, $dependencyFindings);
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
  global $EnableMemoryProtection;
  $InstallSucceeded = FALSE;
  $ReturnData = '';
  $installCommand = $packageList = '';
  $commandOutput = array();
  $commandExitCode = 1;
  $packageList = trim((string)$dependencyEntry['Package']);
  if ((string)$dependencyEntry['Type'] === 'bundled') {
    // / A bundled dependency ships inside the application & is already present by
    // / definition. Nothing to install & nothing to fail.
    $InstallSucceeded = TRUE;
    $ReturnData = 'This dependency ships with HRConvert2 & needs no installation.'; }
  else if ((string)$dependencyEntry['Type'] === 'manual') $ReturnData = 'This dependency is never installed automatically. Install it by hand from '.(string)$dependencyEntry['Source'].'.';
  else if ((string)$dependencyEntry['Type'] === 'source') $ReturnData = 'This dependency is built from source & is not handled by this operation.';
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
      if ($commandExitCode === 0) {
        $commandOutput = array();
        exec((string)$dependencyEntry['VersionCommand'].' 2>&1', $commandOutput, $commandExitCode);
        if ($commandExitCode === 0) $InstallSucceeded = TRUE;
        else $ReturnData = 'The package installed but PHP still cannot load the extension. The web server may need restarting.'; } } }
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
    else $operationFlags = 'pacman -S --noconfirm --needed ';
    $PackageCommand = $operationFlags.implode(' ', $escapedPackages); }
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
  purgeSensitiveMemory($EnableMemoryProtection, $packageManager, $packageList);
  return $InstallCommand; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to install every dependency that is absent or too old.
// / Accepts the authorization token, a subsystem filter & a confirmation boolean.
// / Returns a success boolean & the number installed, in that order.
// / THE MANIFEST ORDER IS THE INSTALL ORDER & IS FOLLOWED EXACTLY.
// / A dependency whose own requirements failed is skipped rather than attempted, because
// / installing it would fail & would bury the reason under a second error.
function installDepends($authorizationToken, $subsystemFilter, $operatorConfirmed) {
  // / Set variables.
  global $RequiredDependsVersion, $Lol, $Verbose, $EnableMemoryProtection;
  $InstallSucceeded = TRUE;
  $DependenciesInstalled = 0;
  $dependsManifest = $dependencyEntry = $installedNames = array();
  $manifestIsAvailable = $dependencyIsPresent = $installOneSucceeded = $callerIsAuthorized = FALSE;
  $rawOutput = $detectedVersion = $dependencyStatus = $detectedDependsVersion = $packageManager = $returnData = $requirementName = $operatorChoice = '';
  $requirementIsMissing = FALSE;
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
        if ($packageManager === 'apt-get') exec('DEBIAN_FRONTEND=noninteractive apt-get update 2>&1');
        print($Lol.'Installing dependencies in manifest order.'.$Lol);
        foreach ($dependsManifest as $dependencyEntry) {
          if (trim((string)$subsystemFilter) !== '' && stripos((string)$dependencyEntry['Subsystem'], trim((string)$subsystemFilter)) === FALSE) continue;
          list ($dependencyIsPresent, $detectedVersion, $dependencyStatus, $rawOutput) = resolveDependencyState($dependencyEntry);
          if ($dependencyStatus === 'ok' or $dependencyStatus === 'unknown-version') {
            $installedNames[(string)$dependencyEntry['Name']] = TRUE;
            print('  '.str_pad((string)$dependencyEntry['Name'], 20).'already present'.$Lol);
            continue; }
          // / Refuse to attempt anything whose own requirements are not in place.
          $requirementIsMissing = FALSE;
          foreach ((array)$dependencyEntry['Requires'] as $requirementName) {
            if (!isset($installedNames[$requirementName])) $requirementIsMissing = TRUE; }
          if ($requirementIsMissing) {
            print('  '.str_pad((string)$dependencyEntry['Name'], 20).'SKIPPED, waiting on '.implode(', ', (array)$dependencyEntry['Requires']).$Lol);
            if ($dependencyEntry['Required']) $InstallSucceeded = FALSE;
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
  purgeSensitiveMemory($EnableMemoryProtection, $dependsManifest, $dependencyEntry, $installedNames, $manifestIsAvailable, $dependencyIsPresent, $installOneSucceeded, $callerIsAuthorized, $detectedVersion, $dependencyStatus, $detectedDependsVersion, $packageManager, $returnData, $requirementName, $operatorChoice, $requirementIsMissing, $authorizationToken, $subsystemFilter, $operatorConfirmed, $rawOutput);
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
        if ($packageManager === 'apt-get') exec('DEBIAN_FRONTEND=noninteractive apt-get update 2>&1');
        print($Lol.'Upgrading installed dependencies.'.$Lol);
        foreach ($dependsManifest as $dependencyEntry) {
          if (trim((string)$subsystemFilter) !== '' && stripos((string)$dependencyEntry['Subsystem'], trim((string)$subsystemFilter)) === FALSE) continue;
          // / A bundled dependency travels with the application & is upgraded by updating it.
          // / An extension travels with PHP & is upgraded by upgrading PHP.
          if ((string)$dependencyEntry['Type'] !== 'apt' && (string)$dependencyEntry['Type'] !== 'pip') continue;
          list ($dependencyIsPresent, $detectedVersion, $dependencyStatus, $rawOutput) = resolveDependencyState($dependencyEntry);
          if ($dependencyStatus === 'absent') { print('  '.str_pad((string)$dependencyEntry['Name'], 20).'not installed, skipped'.$Lol); continue; }
          $commandOutput = array();
          if ((string)$dependencyEntry['Type'] === 'pip') $updateCommand = 'pip3 install --break-system-packages --upgrade '.escapeshellarg((string)$dependencyEntry['Package']).' 2>&1';
          else $updateCommand = dependencyPackageCommand($packageManager, (string)$dependencyEntry['Package'], 'update').' 2>&1';
          if ($updateCommand === ' 2>&1') { print('  '.str_pad((string)$dependencyEntry['Name'], 20).'no package manager, skipped'.$Lol); continue; }
          exec($updateCommand, $commandOutput, $commandExitCode);
          if ($commandExitCode === 0) {
            $DependenciesUpdated++;
            print('  '.str_pad((string)$dependencyEntry['Name'], 20).'upgraded'.$Lol); }
          else {
            print('  '.str_pad((string)$dependencyEntry['Name'], 20).'FAILED'.$Lol);
            warningEntry('Dependency '.(string)$dependencyEntry['Name'].' could not be upgraded.'); } }
        print($Lol.'Upgraded '.$DependenciesUpdated.' dependenc(ies).'.$Lol.$Lol); } } }
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
// / A dependency marked Required by the Core subsystem is never removed at all.
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
          $removeCommand = dependencyPackageCommand($packageManager, (string)$dependencyEntry['Package'], 'remove').' 2>&1';
          if ($removeCommand === ' 2>&1') { print('  '.str_pad((string)$dependencyEntry['Name'], 20).'no package manager, skipped'.$Lol); continue; }
          exec($removeCommand, $commandOutput, $commandExitCode);
          if ($commandExitCode === 0) {
            $DependenciesRemoved++;
            print('  '.str_pad((string)$dependencyEntry['Name'], 20).'removed'.$Lol);
            warningEntry('Removed dependency '.(string)$dependencyEntry['Name'].' on request.'); }
          else print('  '.str_pad((string)$dependencyEntry['Name'], 20).'FAILED'.$Lol); }
        print($Lol.'Removed '.$DependenciesRemoved.' dependenc(ies).'.$Lol.$Lol); } } }
  purgeSensitiveMemory($EnableMemoryProtection, $dependsManifest, $dependencyEntry, $manifestIsAvailable, $callerIsAuthorized, $detectedDependsVersion, $packageManager, $removeCommand, $operatorChoice, $commandOutput, $commandExitCode, $authorizationToken, $subsystemFilter, $operatorConfirmed);
  return array($UninstallSucceeded, $DependenciesRemoved); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write a supply chain audit template.
// / Accepts the destination path, which may be empty for a default beside the logs.
// / Returns a success boolean & the path written, in that order.
// / The output is comma separated so it opens in any spreadsheet without a library & can
// / be read by a script. One dependency per row.
// / The right hand columns are deliberately EMPTY. They are what the audit is for, & a
// / template that pre-filled them would be answering questions nobody had asked yet.
// / This reads the machine & needs no authorization, because a report changes nothing.
function outputSupplyChain($outputPath) {
  // / Set variables.
  global $RequiredDependsVersion, $LogDir, $DirSep, $HRConvertVersion, $Date, $Lol, $EnableMemoryProtection;
  $ReportWasWritten = FALSE;
  $ReportPath = '';
  $dependsManifest = $dependencyEntry = $reportRows = array();
  $manifestIsAvailable = $dependencyIsPresent = FALSE;
  $rawOutput = $detectedVersion = $dependencyStatus = $detectedDependsVersion = $reportContents = '';
  $bytesWritten = 0;
  list ($manifestIsAvailable, $dependsManifest, $detectedDependsVersion) = verifyDependsManifest($RequiredDependsVersion);
  if (!$manifestIsAvailable) print($Lol.'The dependency manifest is unavailable, so no report was written.'.$Lol.$Lol);
  else {
    $ReportPath = trim((string)$outputPath);
    if ($ReportPath === '') $ReportPath = rtrim((string)$LogDir, $DirSep).$DirSep.'HRConvert2-Supply-Chain-'.date('Y-m-d').'.csv';
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
  purgeSensitiveMemory($EnableMemoryProtection, $dependsManifest, $dependencyEntry, $reportRows, $manifestIsAvailable, $dependencyIsPresent, $detectedVersion, $dependencyStatus, $detectedDependsVersion, $reportContents, $bytesWritten, $outputPath, $rawOutput);
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
      if (strpbrk($rowValue, ",\"\r\n") !== FALSE) $rowValue = '"'.str_replace('"', '""', $rowValue).'"';
      $escapedValues[] = $rowValue; }
    $CsvContents = $CsvContents.implode(',', $escapedValues).PHP_EOL; }
  purgeSensitiveMemory($EnableMemoryProtection, $reportRow, $escapedValues, $rowValue, $reportRows);
  return $CsvContents; }
// / -----------------------------------------------------------------------------------