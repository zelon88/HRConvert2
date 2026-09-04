<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRProprietary Engine, Copyright on 9/4/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / Application Information ...
// / The HRProprietary Engine provides a coherent application environment. It is bundled
// / with an application rather than installed beside one, & it is paired to the release
// / that carries it.
// /
// / File Information ...
// / v3.8.9.
// / This file is the Engine. It provides the environment an application runs in.
// / It is pinned EXACTLY by the application via $RequiredEngineVersion.
// / Error block 35000 through 35019 reserved. None are used yet.
// / See Documentation/ABOUT_ENGINE_CONTRACT.txt for what an application must supply.
// /
// / What the Engine is for.
// / An application that converts files, an application that scans them for malware & an
// / application that dispatches work to either of those all need the same environment.
// / They need to know where they are, whether they are running as root, whether there is a
// / terminal on the other end, how to find a binary, how to write a log, how to shred a
// / variable & how to prove that a process they started is one of theirs.
// / None of that is about converting a file. All of it was written inside an application
// / that converts files, & this is where it goes instead.
// /
// / What the Engine is NOT.
// / It is not a framework & it does not build the application. The application is the
// / entry point. It boots, it verifies its components, it loads this file & then it calls
// / into it. The Engine never calls application code, & an Engine that needed to would be
// / an Engine that only works for one application.
// /
// / Three tiers exist & the boundaries are not arbitrary.
// /   The kernel   Lives in the application entry file & loads this one. quickDie,
// /                purgeSensitiveMemory, redeclare, readComponentVersion &
// /                verifyCoreComponent. It cannot live here, because it is what loads
// /                this file & because it must be able to report that loading failed.
// /   The Engine   This file. The environment every application needs.
// /   The app      Everything that makes one application different from another.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by an application.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-35000, The Engine cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The version of this Engine. Read by the application WITHOUT executing this file.
$EngineVersion = 'v3.8.9';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to report whether systemd is actually running this host.
// / Accepts no arguments.
// / Returns a usability boolean & the reason, in that order.
// /
// / The binary being present proves nothing. This is the mistake this function exists for.
// / An earlier release decided systemd was available because loginctl was on the PATH.
// / Every official PHP container image ships the systemd client tools & does not run
// / systemd, so every check passed, a unit file was written, lingering was attempted, & the
// / listener was then handed to a service manager that was never going to answer. The
// / error that finally surfaced came from systemctl itself, which is the proof the binary
// / was there all along.
// /
// / /run/systemd/system EXISTS ONLY WHEN SYSTEMD IS PID 1.
// / This is the test the systemd documentation gives for exactly this question & is what
// / sd_booted does. It is a directory test, it costs nothing, & it is correct inside a
// / container, inside a chroot, & on a host running any other init.
function systemdIsUsable() {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  static $cachedUsable = NULL;
  static $cachedReason = '';
  $SystemdIsUsable = FALSE;
  $SystemdReason = '';
  if ($cachedUsable !== NULL) {
    $SystemdIsUsable = $cachedUsable;
    $SystemdReason = $cachedReason; }
  else {
    if (!is_dir('/run/systemd/system')) $SystemdReason = 'systemd is not running this host, whatever tools are installed.';
    else if (locateDependency('systemctl') === '') $SystemdReason = 'systemd is running but systemctl is not installed.';
    else {
      $SystemdIsUsable = TRUE;
      $SystemdReason = 'systemd is running & systemctl is available.'; }
    if ($Verbose) logEntry('Systemd Check: '.($SystemdIsUsable ? 'AVAILABLE' : 'UNAVAILABLE').', '.$SystemdReason);
    $cachedUsable = $SystemdIsUsable;
    $cachedReason = $SystemdReason; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / Neither value is purged, because both are return values.
  purgeSensitiveMemory($EnableMemoryProtection);
  return array($SystemdIsUsable, $SystemdReason); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read one check's status back out of an environment report.
// / Accepts the findings array & the name of the check, in that order.
// / Returns the status word, or an empty string when that check is not in the report.
// / A caller that needs to say more about one finding than a single line allows should not
// / have to run the check a second time to find out what it said.
function environmentFindingStatus($environmentFindings, $checkName) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FindingStatus = '';
  $finding = array();
  if (is_array($environmentFindings)) {
    foreach ($environmentFindings as $finding) {
      if (isset($finding['Check']) && $finding['Check'] === $checkName && isset($finding['Status'])) $FindingStatus = (string)$finding['Status']; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $finding, $environmentFindings, $checkName);
  return $FindingStatus; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read a limit pair out of its configured string.
// / Accepts a string of a processor percentage & a memory ceiling in megabytes, comma separated.
// / Returns a validity boolean, the processor percentage & the memory megabytes, in that order.
// / A pair that cannot be read is refused rather than guessed at, because a guessed ceiling
// / is worse than no ceiling. The caller falls back to the configured default.
function parseConversionLimit($limitString) {
  // / Set variables.
  global $EnableMemoryProtection;
  $LimitIsValid = FALSE;
  $CpuPercentage = 0;
  $MemoryMegabytes = 0;
  $limitParts = array();
  $limitParts = explode(',', trim((string)$limitString));
  if (count($limitParts) === 2 && ctype_digit(trim($limitParts[0])) && ctype_digit(trim($limitParts[1]))) {
    $CpuPercentage = (int)trim($limitParts[0]);
    $MemoryMegabytes = (int)trim($limitParts[1]);
    // / A percentage of zero & a ceiling of zero both mean no limit, which is not a limit.
    if ($CpuPercentage > 0 && $MemoryMegabytes > 0) $LimitIsValid = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $limitParts, $limitString);
  return array($LimitIsValid, $CpuPercentage, $MemoryMegabytes); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read a startup key out of the environment & clear it immediately.
// / Accepts no arguments. Returns the key, or an empty string when none was supplied.
// / A startup key used to travel on the command line, & /proc/PID/cmdline is world
// / readable, so every local account could read it out of ps for the whole life of the
// / process. A manager listener runs for days, so the key sat in plain view long after the
// / ten second window that made it useful had closed. Anybody watching ps during a spawn
// / had the key. /proc/PID/environ is readable only by the owner of the process.
// / The variable is cleared the moment it is read, so it is not inherited by a converter
// / the manager launches later.
function readTransportedStartupKey() {
  // / Set variables.
  global $EnableMemoryProtection;
  $TransportedKey = '';
  $rawKey = '';
  $rawKey = (string)getenv('HRCONVERT2_STARTUP_KEY');
  if ($rawKey !== '') {
    $TransportedKey = preg_replace('/[^a-f0-9]/', '', strtolower($rawKey));
    putenv('HRCONVERT2_STARTUP_KEY');
    unset($_ENV['HRCONVERT2_STARTUP_KEY'], $_SERVER['HRCONVERT2_STARTUP_KEY']); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $rawKey);
  return $TransportedKey; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to spend a startup key so it can never be presented a second time.
// / Accepts the purpose & the key. Returns TRUE when the key had not been spent before.
// / Time alone cannot make a leaked key dead. The holder of a copy validates it against
// / the same clock we do, so the window only decides how long they have, never whether
// / they succeed. Spending the key is a property this application controls.
// / The ledger lives in the manager socket directory, which is already 0700, & it holds
// / hashes rather than keys so reading it grants nothing. Entries older than three windows
// / are dropped, because a key that old cannot validate anyway.
// /
// / What this defends against, & what it does not.
// / It defends against a key that leaked. Somebody who obtained one copy of a key cannot
// / spend it twice, & cannot spend it at all once the process it was minted for has used it.
// / It does NOT defend against an account that holds the install secret. Deleting this
// / ledger un-spends a captured key, & the two accounts that can delete it are root & the
// / web server user, both of which can already read the secret at 0600 & mint a valid key
// / for any purpose in any window. That is a shorter route to the same place, so losing the
// / ledger to either of them costs nothing that was not already lost.
// / The directory holding the ledger sits inside the data location at 0755 & owned by the
// / web server user, so an unprivileged local account cannot unlink it. That is the account
// / this control exists for, & the property that has to keep holding.
function consumeStartupKey($keyPurpose, $suppliedKey) {
  // / Set variables.
  global $ManagerSocketDir, $DirSep, $StartupKeyWindow, $EnableMemoryProtection;
  $KeyWasUnspent = FALSE;
  $ledgerPath = $ledgerContents = $keyFingerprint = $rebuiltLedger = $ledgerLine = '';
  $ledgerLines = array();
  $cutoffTime = 0;
  if (!is_dir((string)$ManagerSocketDir)) $KeyWasUnspent = TRUE;
  else {
    $ledgerPath = rtrim((string)$ManagerSocketDir, $DirSep).$DirSep.'startup-keys.ledger';
    $keyFingerprint = hash('sha256', (string)$keyPurpose.'|'.(string)$suppliedKey);
    $cutoffTime = time() - (max(1, (int)$StartupKeyWindow) * 3);
    $ledgerContents = (string)@file_get_contents($ledgerPath);
    $ledgerLines = ($ledgerContents === '' ? array() : explode(PHP_EOL, $ledgerContents));
    $KeyWasUnspent = TRUE;
    foreach ($ledgerLines as $ledgerLine) {
      if (trim($ledgerLine) === '') continue;
      // / A line is a timestamp & a fingerprint. Anything older than the cutoff is dropped
      // / rather than carried, so the ledger cannot grow without bound.
      if ((int)substr($ledgerLine, 0, strpos($ledgerLine, ' ')) < $cutoffTime) continue;
      if (substr($ledgerLine, strpos($ledgerLine, ' ') + 1) === $keyFingerprint) $KeyWasUnspent = FALSE;
      $rebuiltLedger = $rebuiltLedger.$ledgerLine.PHP_EOL; }
    if ($KeyWasUnspent) {
      $rebuiltLedger = $rebuiltLedger.time().' '.$keyFingerprint.PHP_EOL;
      @file_put_contents($ledgerPath, $rebuiltLedger, LOCK_EX);
      @chmod($ledgerPath, 0600); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $ledgerPath, $ledgerContents, $keyFingerprint, $rebuiltLedger, $ledgerLine, $ledgerLines, $cutoffTime, $keyPurpose, $suppliedKey);
  return $KeyWasUnspent; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to derive a time bucketed startup key for an internal core invocation.
// / Accepts the purpose the key authorizes.
// / Returns the key, or an empty string when no install secret is available.
// / The bucket bounds the window in which a captured key can be reused.
function deriveStartupKey($keyPurpose) {
  // / Set variables.
  global $SecretKey, $StartupKeyWindow, $EnableMemoryProtection;
  $StartupKey = '';
  $timeBucket = 0;
  $keyMaterial = '';
  if (is_string($SecretKey) && strlen($SecretKey) === 64) {
    $timeBucket = (int)floor(time() / max(1, (int)$StartupKeyWindow));
    $keyMaterial = 'startup|'.$keyPurpose.'|'.$timeBucket;
    $StartupKey = hash_hmac('sha256', $keyMaterial, $SecretKey); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $timeBucket, $keyMaterial, $keyPurpose);
  return $StartupKey; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to validate a startup key supplied on the command line.
// / Accepts the purpose the key must authorize & the key that was supplied.
// / Accepts a third argument requesting that the key be spent once it validates.
// / Returns TRUE when the key matches the current or the previous window.
// / The previous window is accepted because process launch is not instant.
// / A key that crossed a process boundary is spent, so presenting it again is refused
// / however soon it happens. A key derived & checked inside one process never leaves it &
// / is not spent, because there is nothing to replay & four such keys minted in the same
// / second would otherwise refuse each other.
// / The default is not to spend, so a component built against an older core still works.
function validateStartupKey($keyPurpose, $suppliedKey, $singleUse = FALSE) {
  // / Set variables.
  global $SecretKey, $StartupKeyWindow, $EnableMemoryProtection;
  $KeyIsValid = FALSE;
  $timeBucket = 0;
  $keyWasUnspent = TRUE;
  $currentKey = $previousKey = $cleanKey = '';
  $cleanKey = preg_replace('/[^a-f0-9]/', '', strtolower((string)$suppliedKey));
  if (is_string($SecretKey) && strlen($SecretKey) === 64 && strlen($cleanKey) === 64) {
    $timeBucket = (int)floor(time() / max(1, (int)$StartupKeyWindow));
    $currentKey = hash_hmac('sha256', 'startup|'.$keyPurpose.'|'.$timeBucket, $SecretKey);
    $previousKey = hash_hmac('sha256', 'startup|'.$keyPurpose.'|'.($timeBucket - 1), $SecretKey);
    if (hash_equals($currentKey, $cleanKey) or hash_equals($previousKey, $cleanKey)) $KeyIsValid = TRUE; }
  // / The key is only spent once it has proved genuine, so a wrong guess cannot fill the
  // / ledger & cannot deny the real one.
  if ($KeyIsValid && $singleUse) {
    $keyWasUnspent = consumeStartupKey($keyPurpose, $cleanKey);
    if (!$keyWasUnspent) {
      $KeyIsValid = FALSE;
      errorEntry('A startup key for '.$keyPurpose.' was presented a second time & refused as a replay!', 31017, FALSE); } }
  if (!$KeyIsValid && $keyWasUnspent) warningEntry('A startup key for '.$keyPurpose.' was refused.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $timeBucket, $currentKey, $previousKey, $cleanKey, $keyWasUnspent, $keyPurpose, $suppliedKey, $singleUse);
  return $KeyIsValid; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to check that this host can actually do the work, without changing it.
// / Accepts no arguments.
// / Returns a readiness boolean & an array of findings, in that order.
// / THIS NEVER REPAIRS ANYTHING. Repair needs root & the listener does not have it, so a
// / validator that tried would fail confusingly on every pass. It reports, & -fp fixes.
// / Readiness is FALSE only for a condition that stops conversions outright. A policy that
// / has drifted is reported & does not fail the check, because conversions still run.
function validateOperatingEnvironment() {
  // / Set variables.
  global $RequireSandbox, $RunningInContainer, $RequireSandboxOnDocker, $EnableMemoryProtection;
  $EnvironmentIsReady = TRUE;
  $EnvironmentFindings = array();
  $sandboxIsRequired = $policyIsValid = $kernelIsReady = $dataIsProtected = FALSE;
  $policyStatus = $exposureStatus = $exposureDetail = '';
  $kernelFindings = $kernelFinding = array();
  // / The sandbox is the one thing a conversion cannot proceed without when it is required.
  $sandboxIsRequired = (bool)$RequireSandbox;
  if ($RunningInContainer && !$RequireSandboxOnDocker) $sandboxIsRequired = FALSE;
  // / Report the kernel settings first. A sandbox failure is almost always one of these &
  // / naming the setting is more use than naming the symptom.
  list ($kernelIsReady, $kernelFindings) = verifySandboxKernel(FALSE);
  foreach ($kernelFindings as $kernelFinding) $EnvironmentFindings[] = $kernelFinding;
  if (!$kernelIsReady) $EnvironmentIsReady = FALSE;
  if (verifyBwrap() === FALSE) {
    $EnvironmentFindings[] = array('Check' => 'Sandbox', 'Status' => 'FAILED', 'Detail' => 'Bubblewrap cannot build a namespace.'.($sandboxIsRequired ? ' Every conversion will be refused.' : ' Conversions will run unprotected.'));
    if ($sandboxIsRequired) $EnvironmentIsReady = FALSE; }
  else $EnvironmentFindings[] = array('Check' => 'Sandbox', 'Status' => 'ok', 'Detail' => 'Bubblewrap can build a namespace.');
  // / Policies are validated & never repaired here. A drifted policy is reported.
  // / The status word is reported as it is, rather than flattened to ok, & the sentence
  // / beside it says whether anything needs doing. Flattening lost the difference between
  // / a policy that matches & a host that never needed one.
  list ($policyIsValid, $policyStatus) = verifySandboxPolicy(FALSE);
  $EnvironmentFindings[] = array('Check' => 'Sandbox AppArmor', 'Status' => policyDisplayStatus($policyStatus), 'Detail' => describePolicyStatus('Sandbox AppArmor', $policyStatus));
  list ($policyIsValid, $policyStatus) = verifyImageMagickPolicy(FALSE);
  $EnvironmentFindings[] = array('Check' => 'ImageMagick policy', 'Status' => policyDisplayStatus($policyStatus), 'Detail' => describePolicyStatus('ImageMagick', $policyStatus));
  list ($policyIsValid, $policyStatus) = verifyOpenScadPolicy(FALSE);
  $EnvironmentFindings[] = array('Check' => 'OpenSCAD AppArmor', 'Status' => policyDisplayStatus($policyStatus), 'Detail' => describePolicyStatus('OpenSCAD AppArmor', $policyStatus));
  // / The DATA tree is part of the environment & is counted with everything else.
  // / It was reported separately & AFTER the summary line, so a run could print that every
  // / check passed & then say the tree was exposed directly underneath it. A summary that
  // / does not cover a check is worse than no summary, because it is read instead of the
  // / thing it failed to include. It is a finding now, so the count is honest & an exposed
  // / tree appears in the problems list where an operator is already looking.
  list ($dataIsProtected, $exposureStatus, $exposureDetail) = verifyDataExposure();
  $EnvironmentFindings[] = array('Check' => 'DATA exposure', 'Status' => ($exposureStatus === 'protected' ? 'ok' : strtoupper($exposureStatus)), 'Detail' => $exposureDetail);
  // / An exposed or broken tree does not stop a conversion, so it does not make the
  // / environment unready. It is reported loudly & the operator decides.
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $sandboxIsRequired, $policyIsValid, $policyStatus, $kernelIsReady, $kernelFindings, $kernelFinding, $dataIsProtected, $exposureStatus, $exposureDetail);
  return array($EnvironmentIsReady, $EnvironmentFindings); }
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to report the manager subcomponents this Engine accepts.
// / Accepts nothing. Returns an array of file name to required version.
// / A manager is a role a long lived process runs under rather than a file the application
// / loads, & each one lives in Resources/Engine/Managers named for the role it serves.
// / This list is an allowlist rather than a convenience.
// / A file that is not named here is never read, never required & never dispatched to, so
// / a file somebody drops into that directory cannot be executed by being present.
// / Each is pinned EXACTLY, the same way this Engine is pinned by the application & the
// / same way Pipeline Core pins a pipeline.
// / Raise the Engine version whenever this list changes, because the list moving means the
// / Engine moved.
function getAcceptedManagers() {
  // / Set variables.
  global $EnableMemoryProtection;
  $AcceptedManagers = array();
  $AcceptedManagers = array(
    'coreManager.php' => 'v3.8.9',
    'resourceManager.php' => 'v3.8.9',
    'workerManager.php' => 'v3.8.9',
    'requestManager.php' => 'v3.8.9');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $AcceptedManagers is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection);
  return $AcceptedManagers; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify & load one manager subcomponent.
// / Accepts the file name. Returns an availability boolean & the detected version.
// / A manager is judged before it is loaded, never after, because a manager built for
// / another Engine may accept an argument this one has dropped.
// / A manager that is absent or mismatched is a warning rather than a halt. What is lost
// / is the role that manager serves & the loss is named rather than guessed at.
function loadEngineManager($managerFileName) {
  // / Set variables.
  global $InstLoc, $DirSep, $CoreLoaded, $EnableMemoryProtection;
  $ManagerIsAvailable = FALSE;
  $ManagerVersion = '';
  $acceptedManagers = array();
  $managerPath = $requiredVersion = '';
  $acceptedManagers = getAcceptedManagers();
  if (!isset($acceptedManagers[(string)$managerFileName])) warningEntry('The manager subcomponent '.$managerFileName.' is not one this Engine accepts. It was not loaded.');
  else {
    $requiredVersion = ltrim((string)$acceptedManagers[(string)$managerFileName], 'vV');
    $managerPath = rtrim((string)$InstLoc, $DirSep).$DirSep.'Resources'.$DirSep.'Engine'.$DirSep.'Managers'.$DirSep.(string)$managerFileName;
    // / readComponentVersion is a kernel service & reads the version without executing the
    // / file, which is the whole reason a mismatched manager can be refused safely.
    $ManagerVersion = readComponentVersion('Engine'.$DirSep.'Managers'.$DirSep.(string)$managerFileName, 'ManagerVersion');
    if (!file_exists($managerPath)) warningEntry('The manager subcomponent '.$managerFileName.' is not installed. The role it serves is unavailable.');
    else if ($ManagerVersion === '') warningEntry('The manager subcomponent '.$managerFileName.' reports no version. An unknown build cannot be cleared & it was not loaded.');
    else if (ltrim((string)$ManagerVersion, 'vV') !== $requiredVersion) warningEntry('The manager subcomponent '.$managerFileName.' reports '.$ManagerVersion.' & this Engine requires '.$requiredVersion.'. It was not loaded.');
    else {
      require_once ($managerPath);
      $ManagerIsAvailable = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $acceptedManagers, $managerPath, $requiredVersion, $managerFileName);
  return array($ManagerIsAvailable, $ManagerVersion); }
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / Data locations.
// / An application keeps its working files somewhere & may keep them in several places.
// / HRConvert2 calls that a Convert Location & ScanCore would call it a quarantine.
// / The concept is the same & the code below knows nothing about either name.
// / A location carries a Path & a Type. The primary is always first in the pool.
// / A type of roundrobin, leastactive or redundant decides how work is distributed.
// / An unrecognized type becomes redundant, because a typo must never silently start
// / sending user data somewhere unintended.
// / Everything an application knows is passed in rather than read from a global.
// / That is why these functions carry more arguments than they otherwise would, & it is
// / what lets a second application use them without renaming any of its own settings.
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to count the sessions currently held in one data location.
// / Accepts the absolute path of the location.
// / Returns the number of session directories across every daily directory it holds.
// / This is the measure least active selection compares. It is only called when a session
// / is new, because an established session never chooses a location again.
function countDataLocationSessions($locationPath, $protectedRootDirs) {
  // / Set variables.
  global $DirSep, $EnableMemoryProtection;
  $SessionCount = 0;
  $dailyDirs = $sessionDirs = array();
  $dailyDir = $dailyPath = '';
  if (is_dir($locationPath)) {
    $dailyDirs = array_diff(scandir($locationPath), array('..', '.'));
    foreach ($dailyDirs as $dailyDir) {
      $dailyPath = $locationPath.$DirSep.$dailyDir;
      // / A protected root directory is not a daily directory & holds no sessions.
      if (!in_array($dailyDir, $protectedRootDirs, TRUE) && is_dir($dailyPath)) {
        $sessionDirs = array_diff(scandir($dailyPath), array('..', '.'));
        $SessionCount = $SessionCount + count($sessionDirs); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dailyDirs, $sessionDirs, $dailyDir, $dailyPath, $locationPath);
  return $SessionCount; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to enumerate every data location this installation may use.
// / Accepts no arguments.
// / Returns an array of entries, each carrying a Path & a Type, with the primary first.
// / The primary is the --Convert Location-- declared in config.php & is always present.
// / An entry that is not an array of a path & a type is skipped & reported.
// / A duplicate path is skipped, because the same location twice would double its share.
function enumerateDataLocations($primaryPath, $activePath, $additionalLocations) {
  // / Set variables.
  global $DirSep, $EnableMemoryProtection;
  $activePathPool = array();
  $additionalEntry = $seenPaths = array();
  $entryPath = $entryType = $primaryPath = '';
  // / $primaryPath holds the configured value & is set once, before $activePath is
  // / narrowed to the location this session actually uses.
  // / The caller passes the configured primary & the location this session settled on.
  // / The configured one wins, because the active one has already been narrowed & would
  // / otherwise nominate itself as the primary of a pool it is only a member of.
  if (!is_string($primaryPath) or $primaryPath === '') $primaryPath = (string)$activePath;
  $primaryPath = rtrim($primaryPath, $DirSep);
  $activePathPool[] = array('Path' => $primaryPath, 'Type' => 'primary');
  $seenPaths[] = $primaryPath;
  if (is_array($additionalLocations)) {
    foreach ($additionalLocations as $additionalEntry) {
      if (!is_array($additionalEntry) or count($additionalEntry) < 2) warningEntry('An entry in Additional Data Locations is not an array of a path & a type. It was skipped.');
      else {
        $entryPath = rtrim(trim((string)$additionalEntry[0]), $DirSep);
        $entryType = strtolower(trim((string)$additionalEntry[1]));
        // / An unrecognized type becomes a standby rather than joining the distribution.
        // / A typo must not silently start sending user data somewhere unintended.
        if ($entryType !== 'roundrobin' && $entryType !== 'leastactive' && $entryType !== 'redundant') {
          warningEntry('The data location at '.$entryPath.' declares an unrecognized type of '.$entryType.'. It will be treated as redundant.');
          $entryType = 'redundant'; }
        if ($entryPath === '') warningEntry('An entry in Additional Data Locations has an empty path. It was skipped.');
        else if (in_array($entryPath, $seenPaths, TRUE)) warningEntry('The data location at '.$entryPath.' is listed more than once. The duplicate was skipped.');
        else {
          $activePathPool[] = array('Path' => $entryPath, 'Type' => $entryType);
          $seenPaths[] = $entryPath; } } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $activePathPool is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $additionalEntry, $seenPaths, $entryPath, $entryType, $primaryPath);
  return $activePathPool; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide which data location this session will use.
// / Accepts the daily hash & the session hash, in that order.
// / Returns an absolute path as a string, ALWAYS. Every failure falls back to the location
// / config.php declares, so a caller never has to test the result before using it.
// / AN ESTABLISHED SESSION IS DISCOVERED, NOT CHOSEN. A session that already holds a
// / directory somewhere keeps that location for its whole life. Choosing again on a later
// / request would send the worker looking for the user's files where they are not.
// / Only a session with no directory anywhere is distributed, & only then does type matter.
// / A location marked leastactive puts the whole pool into least active selection.
// / Otherwise the pool is distributed by session identifier, which needs no shared counter
// / & therefore works with several front ends that never speak to each other.
// / A location marked redundant is a standby. It takes a session only when every other
// / location is unusable.
function resolveDataLocation($dailyHash, $sessionHash, $primaryPath, $activePath, $additionalLocations, $protectedRootDirs) {
  // / Set variables.
  global $DirSep, $Verbose, $EnableMemoryProtection;
  $ResolvedDataLocation = '';
  $convertLocPool = $distributionPool = $redundantPool = $poolEntry = array();
  $selectionMode = $cleanDailyHash = $cleanSessionHash = '';
  $sessionCount = $lowestSessionCount = $selectionIndex = 0;
  $sessionIsDiscovered = FALSE;
  // / The fallback is established first, so every path below can only improve on it.
  $ResolvedDataLocation = (isset($primaryPath) && is_string($primaryPath) && $primaryPath !== '') ? $primaryPath : (string)$activePath;
  $ResolvedDataLocation = rtrim($ResolvedDataLocation, $DirSep);
  $cleanDailyHash = preg_replace('/[^A-Za-z0-9]/', '', (string)$dailyHash);
  $cleanSessionHash = preg_replace('/[^A-Za-z0-9]/', '', (string)$sessionHash);
  $convertLocPool = enumerateDataLocations($primaryPath, $activePath, $additionalLocations);
  // / Discovery. An existing session directory decides the answer outright.
  if ($cleanDailyHash !== '' && $cleanSessionHash !== '') {
    foreach ($convertLocPool as $poolEntry) {
      if (!$sessionIsDiscovered && is_dir($poolEntry['Path'].$DirSep.$cleanDailyHash.$DirSep.$cleanSessionHash)) {
        $ResolvedDataLocation = $poolEntry['Path'];
        $sessionIsDiscovered = TRUE; } } }
  // / A new session is distributed across whatever is usable right now.
  if (!$sessionIsDiscovered) {
    foreach ($convertLocPool as $poolEntry) {
      if (is_dir($poolEntry['Path']) && is_writable($poolEntry['Path'])) {
        if ($poolEntry['Type'] === 'redundant') $redundantPool[] = $poolEntry;
        else $distributionPool[] = $poolEntry;
        if ($poolEntry['Type'] === 'leastactive') $selectionMode = 'leastactive'; } }
    if ($selectionMode === '') $selectionMode = 'distributed';
    // / One usable candidate is not a distribution. It is the answer.
    if (count($distributionPool) === 1) $ResolvedDataLocation = $distributionPool[0]['Path'];
    else if (count($distributionPool) > 1 && $selectionMode === 'leastactive') {
      $lowestSessionCount = -1;
      foreach ($distributionPool as $poolEntry) {
        $sessionCount = countDataLocationSessions($poolEntry['Path'], $protectedRootDirs);
        if ($lowestSessionCount < 0 or $sessionCount < $lowestSessionCount) {
          $lowestSessionCount = $sessionCount;
          $ResolvedDataLocation = $poolEntry['Path']; } } }
    else if (count($distributionPool) > 1) {
      // / Derived from the session identifier rather than from a counter, so two front ends
      // / that share storage & never speak to each other still agree on the answer.
      $selectionIndex = abs((int)crc32($cleanSessionHash)) % count($distributionPool);
      $ResolvedDataLocation = $distributionPool[$selectionIndex]['Path']; }
    // / Nothing in the distribution pool is usable, so a standby takes the session.
    else if (!empty($redundantPool)) {
      $ResolvedDataLocation = $redundantPool[0]['Path'];
      warningEntry('Every distributed data location is unusable. The redundant location at '.$ResolvedDataLocation.' has taken this session.'); }
    // / Nothing at all is usable. The configured location is returned & will fail loudly
    // / at directory verification, which is a better failure than a silent wrong path.
    else warningEntry('No configured data location is usable. Falling back to '.$ResolvedDataLocation.'.'); }
  if ($Verbose) logEntry('Data Location: '.$ResolvedDataLocation.', Pool: '.count($convertLocPool).', Session: '.($sessionIsDiscovered ? 'DISCOVERED' : 'NEW, selected by '.$selectionMode).'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $ResolvedDataLocation is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $convertLocPool, $distributionPool, $redundantPool, $poolEntry, $selectionMode, $cleanDailyHash, $cleanSessionHash, $sessionCount, $lowestSessionCount, $selectionIndex, $sessionIsDiscovered, $dailyHash, $sessionHash);
  return $ResolvedDataLocation; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove expired session data from a data location.
// / A data location holds daily directories, & each daily directory holds session
// / directories. Only a session directory is ever swept, & only once it is older than the
// / delete threshold. A daily directory is removed once every session inside it is gone.
// / The location being cleaned is authorized in two independent ways before anything is
// / read. The name must be one this function recognizes, & the path must be the one that
// / name refers to. A caller that supplies a valid name with any other path is refused,
// / which is what stops a mistake elsewhere in the core from sweeping the wrong tree.
function cleanDataLocation($dataLoc, $locationName, $deleteThreshold, $protectedRootDirs, $authorizedPaths) {
  // / Set variables.
  global $DirSep, $Verbose, $EnableMemoryProtection;
  $LocationDeepCleaned = $cleanAuthorized = FALSE;
  $CleanedLocation = TRUE;
  // / Every filesystem lists these two & neither is ever a session.
  $defaultApps = array('.', '..');
  $dailyDirs = $sessionDirs = array();
  $dailyDir = $sessionDir = $dailyPath = $sessionPath = '';
  $now = time();
  // / Determine whether this clean operation is being requested on a valid target.
  // / The caller supplies the paths it is permitted to clean & the target must be one.
  // / An earlier version matched a NAME against a short list this function knew, then
  // / checked the path that name referred to. That worked & tied a generic cleaner to one
  // / application's vocabulary, & a caller passing the wrong label was silently refused.
  // / A list of paths says the same thing without either problem, & is no weaker, because
  // / an arbitrary path is still refused for not being in it.
  // / $locationName is now only a label for the messages below.
  if (is_array($authorizedPaths) && in_array($dataLoc, $authorizedPaths, TRUE)) $cleanAuthorized = TRUE;
  // / An unauthorized target is a failure of the caller & must be reported as one.
  // / Reporting success here would let the core log that a location was cleaned when it was
  // / refused, which is the one outcome this check exists to make visible.
  if (!$cleanAuthorized) {
    $CleanedLocation = FALSE;
    errorEntry('An invalid clean operation has been blocked!', 29, FALSE); }
  // / A location that does not exist is not an error. There is simply nothing to clean.
  else if (!file_exists($dataLoc)) warningEntry('The '.$locationName.' location does not exist at '.$dataLoc.'. Nothing to clean.');
  else {
    if ($Verbose) logEntry('The valid clean operation has been authorized.');
    $dailyDirs = array_diff(scandir($dataLoc), array('..', '.'));
    // / Iterate through each daily folder in the location.
    foreach ($dailyDirs as $dailyDir) {
      // / Validate the folder.
      if (in_array($dailyDir, $defaultApps)) continue;
      // / A protected directory at this level is not a daily session parent & is never swept.
      // / The LibreOffice profile, the log directory & the update backup live at this level.
      if (in_array($dailyDir, $protectedRootDirs, TRUE)) continue;
      $dailyPath = $dataLoc.$DirSep.$dailyDir;
      // / Only directories hold sessions.
      // / Files at this level are left alone entirely.
      if (!is_dir($dailyPath)) continue;
      $sessionDirs = array_diff(scandir($dailyPath), array('..', '.'));
      // / Iterate through each session folder inside this day.
      foreach ($sessionDirs as $sessionDir) {
        if (in_array($sessionDir, $defaultApps)) continue;
        $sessionPath = $dailyPath.$DirSep.$sessionDir;
        if (!is_dir($sessionPath)) continue;
        // / See if this individual session is due for deletion.
        // / A threshold of zero expires everything, because every session is older than nothing.
        if ($now - fileTime($sessionPath) > ($deleteThreshold * 60)) {
          $LocationDeepCleaned = TRUE;
          if (file_exists($sessionPath)) {
            @chmod($sessionPath, 0777);
            $directoryIterator = new RecursiveDirectoryIterator($sessionPath, RecursiveDirectoryIterator::SKIP_DOTS);
            $iterator = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($iterator as $fileObject) {
              $realPath = $fileObject->getRealPath();
              @chmod($realPath, 0777);
              // / Strip system restrictions (immutable/append-only flags) from every object universally.
              @shell_exec("chattr -i -a " . escapeshellarg($realPath) . " 2>&1");
              if ($fileObject->isDir()) @rmdir($realPath);
              else @unlink($realPath); } }
          // / Track failure if cleanFiles returns false.
          if (!cleanFiles($sessionPath)) {
            $CleanedLocation = FALSE; }
          // / Remove the session shell, including any protected file objects still in it.
          removeEmptiedSessionDir($sessionPath); } }
      // / Remove the daily parent only once every session inside it is gone.
      if (isDirEmptyOfUserFiles($dailyPath)) removeEmptiedSessionDir($dailyPath); }
    // / Log the result.
    if ($Verbose) logEntry('Cleaned the '.$locationName.' location. Removed Files: '.($LocationDeepCleaned ? 'TRUE' : 'FALSE').'.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dailyDirs, $dailyDir, $dailyPath, $sessionDirs, $sessionDir, $sessionPath, $now, $dataLoc, $locationName, $deleteThreshold, $cleanAuthorized, $directoryIterator, $iterator, $fileObject, $realPath);
  return array($CleanedLocation, $LocationDeepCleaned); }
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / Manager machinery.
// / Everything below is shared by every manager role & by anything that starts or stops
// / one. The roles themselves live in Resources/Engine/Managers, one file each.
// / A message between two managers is encrypted with AES-256-GCM under a key derived from
// / the install secret, carries a random initialization vector & an authentication tag, &
// / is refused when its timestamp falls outside the configured skew.
// / A worker is identified by its process number AND the kernel start time recorded when it
// / was registered, because the kernel reuses numbers & a number alone would eventually
// / name a process this application never started.
// / A budget is a number of cost units rather than a number of workers.
// / None of this is about converting a file & all of it would serve a virus scanner or a
// / job dispatcher without a line changing.
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to derive a message encryption key for one purpose from the install secret.
// / Accepts a purpose string that separates one message channel from another.
// / Returns a raw 32 byte key, or an empty string when no secret is available.
function deriveManagerKey($keyPurpose) {
  // / Set variables.
  global $SecretKey, $EnableMemoryProtection;
  $DerivedKey = '';
  $keyMaterial = '';
  if (is_string($SecretKey) && strlen($SecretKey) === 64) {
    $keyMaterial = $SecretKey.'|coreManager|'.$keyPurpose;
    $DerivedKey = hash('sha256', $keyMaterial, TRUE); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $keyMaterial, $keyPurpose);
  return $DerivedKey; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to encrypt a message payload for transport over a manager socket.
// / Accepts an array payload & the channel purpose.
// / Returns a single line base64 string, or an empty string on failure.
// / A timestamp & a nonce are added so a captured message cannot be replayed later.
function encryptManagerMessage($messagePayload, $keyPurpose) {
  // / Set variables.
  global $EnableMemoryProtection;
  $EncryptedMessage = '';
  $messageKey = deriveManagerKey($keyPurpose);
  $messageJson = $messageTag = $messageCipher = '';
  $messageIv = '';
  if ($messageKey !== '' && is_array($messagePayload)) {
    $messagePayload['MessageTime'] = time();
    $messagePayload['MessageNonce'] = bin2hex(random_bytes(8));
    $messageJson = json_encode($messagePayload);
    $messageIv = random_bytes(12);
    $messageCipher = openssl_encrypt($messageJson, 'aes-256-gcm', $messageKey, OPENSSL_RAW_DATA, $messageIv, $messageTag);
    if ($messageCipher !== FALSE) $EncryptedMessage = base64_encode($messageIv.$messageTag.$messageCipher); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $messageKey, $messageJson, $messageIv, $messageTag, $messageCipher, $messagePayload, $keyPurpose);
  return $EncryptedMessage; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decrypt & validate a message received over a manager socket.
// / Accepts the base64 payload & the channel purpose.
// / Returns a validity boolean & the decoded array, in that order.
// / A message older than the configured skew window is refused as a replay.
function decryptManagerMessage($encryptedPayload, $keyPurpose) {
  // / Set variables.
  global $ManagerMessageSkew, $EnableMemoryProtection;
  $MessageIsValid = FALSE;
  $MessagePayload = array();
  $messageKey = deriveManagerKey($keyPurpose);
  $rawPayload = $messageIv = $messageTag = $messageCipher = $messageJson = '';
  $decodedPayload = array();
  $messageAge = 0;
  if ($messageKey !== '' && is_string($encryptedPayload) && $encryptedPayload !== '') {
    $rawPayload = base64_decode(trim($encryptedPayload), TRUE);
    if (is_string($rawPayload) && strlen($rawPayload) > 28) {
      $messageIv = substr($rawPayload, 0, 12);
      $messageTag = substr($rawPayload, 12, 16);
      $messageCipher = substr($rawPayload, 28);
      $messageJson = openssl_decrypt($messageCipher, 'aes-256-gcm', $messageKey, OPENSSL_RAW_DATA, $messageIv, $messageTag);
      if (is_string($messageJson) && $messageJson !== '') {
        $decodedPayload = json_decode($messageJson, TRUE);
        if (is_array($decodedPayload) && isset($decodedPayload['MessageTime'])) {
          $messageAge = abs(time() - (int)$decodedPayload['MessageTime']);
          if ($messageAge <= (int)$ManagerMessageSkew) {
            $MessagePayload = $decodedPayload;
            $MessageIsValid = TRUE; } } } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $messageKey, $rawPayload, $messageIv, $messageTag, $messageCipher, $messageJson, $decodedPayload, $messageAge, $encryptedPayload, $keyPurpose);
  return array($MessageIsValid, $MessagePayload); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to build the absolute path of a named manager socket.
// / Accepts the short socket name.
// / Returns the absolute path. Sockets live in the data location & never in the web root.
function buildManagerSocketPath($socketName) {
  // / Set variables.
  global $ManagerSocketDir, $DirSep, $EnableMemoryProtection;
  $ManagerSocketPath = '';
  $cleanSocketName = preg_replace('/[^A-Za-z0-9\-]/', '', (string)$socketName);
  if ($cleanSocketName !== '') $ManagerSocketPath = $ManagerSocketDir.$DirSep.$cleanSocketName.'.sock';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cleanSocketName, $socketName);
  return $ManagerSocketPath; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to open a listening unix domain socket for a manager.
// / Accepts the absolute socket path.
// / Returns a success boolean & the stream resource, in that order.
// / A stale socket file from a crashed manager is removed before binding.
function openManagerSocketServer($socketPath) {
  // / Set variables.
  global $EnableMemoryProtection, $Verbose;
  $ServerIsOpen = FALSE;
  $SocketServer = FALSE;
  $socketError = '';
  $socketErrorNumber = 0;
  if (file_exists($socketPath)) @unlink($socketPath);
  $SocketServer = @stream_socket_server('unix://'.$socketPath, $socketErrorNumber, $socketError);
  if ($SocketServer !== FALSE) {
    @chmod($socketPath, 0600);
    stream_set_blocking($SocketServer, FALSE);
    $ServerIsOpen = TRUE;
    if ($Verbose) logEntry('Bound a manager socket at '.$socketPath.'.'); }
  else warningEntry('Could not open the manager socket at '.$socketPath.'. '.$socketError);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $socketError, $socketErrorNumber, $socketPath);
  return array($ServerIsOpen, $SocketServer); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to send one encrypted message to a manager socket & read the reply.
// / Accepts the socket path, the array payload, the channel purpose & a timeout in seconds.
// / Returns a delivery boolean & the decoded reply array, in that order.
// / The reply travels back on the connection the sender opened, so no process ever needs
// / to be addressable by another. A worker cannot be reached by anything it did not call.
function sendManagerMessage($socketPath, $messagePayload, $keyPurpose, $timeoutSeconds) {
  // / Set variables.
  global $EnableMemoryProtection, $Verbose;
  $MessageWasDelivered = FALSE;
  $ReplyPayload = array();
  $socketClient = FALSE;
  $socketError = $encryptedMessage = $rawReply = '';
  $socketErrorNumber = $bytesWritten = 0;
  $replyIsValid = FALSE;
  $encryptedMessage = encryptManagerMessage($messagePayload, $keyPurpose);
  if ($encryptedMessage !== '' && file_exists($socketPath)) {
    $socketClient = @stream_socket_client('unix://'.$socketPath, $socketErrorNumber, $socketError, (float)$timeoutSeconds);
    if ($socketClient !== FALSE) {
      stream_set_timeout($socketClient, (int)$timeoutSeconds);
      $bytesWritten = @fwrite($socketClient, $encryptedMessage.PHP_EOL);
      if ($bytesWritten > 0) {
        $MessageWasDelivered = TRUE;
        if ($Verbose) logEntry('Sent a '.(isset($messagePayload['RequestType']) ? $messagePayload['RequestType'] : 'unknown').' message to '.basename($socketPath).'.');
        $rawReply = @fgets($socketClient, 65536);
        if (is_string($rawReply) && trim($rawReply) !== '') list ($replyIsValid, $ReplyPayload) = decryptManagerMessage($rawReply, $keyPurpose); }
      @fclose($socketClient); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $socketError, $socketErrorNumber, $encryptedMessage, $rawReply, $bytesWritten, $replyIsValid, $socketPath, $messagePayload, $keyPurpose, $timeoutSeconds);
  return array($MessageWasDelivered, $ReplyPayload); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to accept & decrypt a batch of pending messages from a listening socket.
// / Accepts the server stream, the channel purpose, a message ceiling & a wait in seconds.
// / Returns the count accepted, the decoded messages & their open connections, in that order.
// / Connections are returned open so the caller can write a reply to the correct sender.
function receiveManagerMessages($socketServer, $keyPurpose, $maxMessages, $waitSeconds) {
  // / Set variables.
  global $EnableMemoryProtection, $Verbose;
  $MessagesReceived = 0;
  $ManagerMessages = array();
  $ManagerConnections = array();
  $socketConnection = FALSE;
  $rawMessage = '';
  $messageIsValid = FALSE;
  $messagePayload = array();
  $messageCounter = 0;
  while ($messageCounter < (int)$maxMessages) {
    $socketConnection = @stream_socket_accept($socketServer, (float)$waitSeconds);
    if ($socketConnection === FALSE) break;
    stream_set_timeout($socketConnection, (int)$waitSeconds + 1);
    $rawMessage = @fgets($socketConnection, 65536);
    list ($messageIsValid, $messagePayload) = decryptManagerMessage($rawMessage, $keyPurpose);
    if ($messageIsValid) {
      $ManagerMessages[] = $messagePayload;
      $ManagerConnections[] = $socketConnection;
      $MessagesReceived++; }
    else {
      warningEntry('A manager socket message failed decryption or replay validation & was discarded.');
      @fclose($socketConnection); }
    $messageCounter++;
    $waitSeconds = 0; }
  if ($Verbose && $MessagesReceived > 0) logEntry('Accepted '.$MessagesReceived.' message(s) on the '.$keyPurpose.' channel.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $rawMessage, $messageIsValid, $messagePayload, $messageCounter, $keyPurpose, $maxMessages, $waitSeconds);
  return array($MessagesReceived, $ManagerMessages, $ManagerConnections); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write an encrypted reply onto an accepted connection & close it.
// / Accepts the open connection, the array payload & the channel purpose.
// / Returns TRUE when the reply was written.
function replyToManagerMessage($socketConnection, $replyPayload, $keyPurpose) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ReplyWasSent = FALSE;
  $encryptedReply = encryptManagerMessage($replyPayload, $keyPurpose);
  $bytesWritten = 0;
  if ($encryptedReply !== '' && is_resource($socketConnection)) {
    $bytesWritten = @fwrite($socketConnection, $encryptedReply.PHP_EOL);
    if ($bytesWritten > 0) $ReplyWasSent = TRUE; }
  if (is_resource($socketConnection)) @fclose($socketConnection);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $encryptedReply, $bytesWritten, $replyPayload, $keyPurpose);
  return $ReplyWasSent; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read a shared manager state file.
// / Accepts the short state name.
// / Returns a success boolean & the state array, in that order.
// / State is shared through the filesystem because every manager is a separate process.
function readManagerState($stateName) {
  // / Set variables.
  global $ManagerSocketDir, $DirSep, $EnableMemoryProtection;
  $StateWasRead = FALSE;
  $ManagerState = array();
  $statePath = $stateContents = '';
  $decodedState = array();
  $cleanStateName = preg_replace('/[^A-Za-z0-9\-]/', '', (string)$stateName);
  if ($cleanStateName !== '') {
    $statePath = $ManagerSocketDir.$DirSep.$cleanStateName.'.state';
    if (file_exists($statePath)) {
      $stateContents = @file_get_contents($statePath);
      if (is_string($stateContents) && $stateContents !== '') {
        $decodedState = json_decode($stateContents, TRUE);
        if (is_array($decodedState)) {
          $ManagerState = $decodedState;
          $StateWasRead = TRUE; } } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $statePath, $stateContents, $decodedState, $cleanStateName, $stateName);
  return array($StateWasRead, $ManagerState); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write a shared manager state file.
// / Accepts the short state name & the array to store.
// / Returns TRUE when the file was written completely.
function writeManagerState($stateName, $stateData) {
  // / Set variables.
  global $ManagerSocketDir, $DirSep, $EnableMemoryProtection;
  $StateWasWritten = FALSE;
  $statePath = $stateContents = '';
  $bytesWritten = 0;
  $cleanStateName = preg_replace('/[^A-Za-z0-9\-]/', '', (string)$stateName);
  if ($cleanStateName !== '' && is_array($stateData)) {
    $statePath = $ManagerSocketDir.$DirSep.$cleanStateName.'.state';
    $stateContents = json_encode($stateData);
    $bytesWritten = @file_put_contents($statePath, $stateContents, LOCK_EX);
    if ($bytesWritten === strlen($stateContents)) {
      @chmod($statePath, 0600);
      $StateWasWritten = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $statePath, $stateContents, $bytesWritten, $cleanStateName, $stateName, $stateData);
  return $StateWasWritten; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to poll actual system resource consumption.
// / Accepts no arguments.
// / Returns a success boolean & an array describing the host, in that order.
// / Load average is the primary signal because it reflects queued work, not just used memory.
function pollSystemResources() {
  // / Set variables.
  global $EnableMemoryProtection;
  $ResourcesPolled = FALSE;
  $SystemResources = array('CpuCount' => 1, 'LoadAverage' => 0.0, 'LoadPercentage' => 0.0, 'MemoryTotalKb' => 0, 'MemoryAvailableKb' => 0, 'MemoryUsedPercentage' => 0.0);
  $loadAverages = array();
  $memoryInfo = $cpuInfo = '';
  $memoryMatches = array();
  $cpuCount = 1;
  $memoryWasRead = FALSE;
  // / Processor count. nproc is not always present, so cpuinfo is the fallback.
  $cpuInfo = @file_get_contents('/proc/cpuinfo');
  // / Only a line that begins with the processor field is counted. substr_count matched the
  // / word anywhere in the file, including inside a model name that contains it, & every
  // / spurious match inflated the budget this host is willing to hand out.
  if (is_string($cpuInfo) && $cpuInfo !== '') $cpuCount = max(1, preg_match_all('/^processor\s*:/m', $cpuInfo));
  $SystemResources['CpuCount'] = $cpuCount;
  // / Load average over one minute.
  if (function_exists('sys_getloadavg')) {
    $loadAverages = sys_getloadavg();
    if (is_array($loadAverages) && isset($loadAverages[0])) {
      $SystemResources['LoadAverage'] = (float)$loadAverages[0];
      $SystemResources['LoadPercentage'] = round(((float)$loadAverages[0] / $cpuCount) * 100, 2);
      $ResourcesPolled = TRUE; } }
  // / Available memory rather than free memory. Free memory excludes reclaimable cache.
  $memoryInfo = @file_get_contents('/proc/meminfo');
  if (is_string($memoryInfo) && $memoryInfo !== '') {
    if (preg_match('/MemTotal:\s+(\d+)/', $memoryInfo, $memoryMatches)) $SystemResources['MemoryTotalKb'] = (int)$memoryMatches[1];
    if (preg_match('/MemAvailable:\s+(\d+)/', $memoryInfo, $memoryMatches)) {
      $SystemResources['MemoryAvailableKb'] = (int)$memoryMatches[1];
      $memoryWasRead = TRUE; }
    // / The percentage is only calculated when MemAvailable was actually found.
    // / MemAvailable arrived in Linux 3.14 & some container runtimes still do not expose it.
    // / Calculating regardless read the initialized zero as no memory available, reported
    // / 100 percent pressure, & drove the usable budget to zero. Every conversion on such a
    // / host was then refused for insufficient budget, permanently, with nothing in the log
    // / that pointed at the cause.
    if ($memoryWasRead && $SystemResources['MemoryTotalKb'] > 0) $SystemResources['MemoryUsedPercentage'] = round((1 - ($SystemResources['MemoryAvailableKb'] / $SystemResources['MemoryTotalKb'])) * 100, 2);
    else if (!$memoryWasRead) warningEntry('This kernel does not report MemAvailable, so the resource budget is governed by load average alone.');
    $ResourcesPolled = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $loadAverages, $memoryInfo, $cpuInfo, $memoryMatches, $cpuCount, $memoryWasRead);
  return array($ResourcesPolled, $SystemResources); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to recalculate the resource budget against observed system load.
// / Accepts the polled resource array & the currently allocated budget.
// / Returns a success boolean & the budget array, in that order.
// / The budget shrinks when the host is already busy, so an idle server is not throttled
// / & a loaded server stops accepting work before it falls over.
function calculateResourceBudget($systemResources, $allocatedBudget) {
  // / Set variables.
  global $TotalResourceBudget, $ReserveResourcePercentage, $EnableMemoryProtection;
  $BudgetCalculated = FALSE;
  $ResourceBudget = array('TotalBudget' => 0, 'ReserveBudget' => 0, 'AllocatedBudget' => 0, 'RemainingBudget' => 0, 'ConsumedBudget' => 0, 'AllowNextWorker' => FALSE, 'BudgetTime' => time());
  $baseBudget = $reserveBudget = $pressurePenalty = $usableBudget = $reserveShare = 0;
  $highestPressure = 0.0;
  if (is_array($systemResources)) {
    // / A configured budget of zero derives the budget from the processor count.
    $baseBudget = (int)$TotalResourceBudget;
    if ($baseBudget < 1) $baseBudget = max(1, (int)$systemResources['CpuCount']) * 100;
    // / A reserve outside nought to a hundred is a configuration mistake rather than an
    // / instruction. Left unclamped, a value above a hundred reserved more than the whole
    // / budget & refused every conversion without ever saying why.
    $reserveShare = (int)$ReserveResourcePercentage;
    if ($reserveShare < 0) $reserveShare = 0;
    if ($reserveShare > 100) {
      warningEntry('The configured reserve share is above 100 percent. It was treated as 100.');
      $reserveShare = 100; }
    $reserveBudget = (int)floor($baseBudget * ($reserveShare / 100));
    // / Whichever of load or memory is under more pressure governs the budget.
    $highestPressure = max((float)$systemResources['LoadPercentage'], (float)$systemResources['MemoryUsedPercentage']);
    if ($highestPressure > 100) $highestPressure = 100;
    $pressurePenalty = (int)floor($baseBudget * ($highestPressure / 100));
    $usableBudget = $baseBudget - $reserveBudget - $pressurePenalty;
    if ($usableBudget < 0) $usableBudget = 0;
    $ResourceBudget['TotalBudget'] = $baseBudget;
    $ResourceBudget['ReserveBudget'] = $reserveBudget;
    $ResourceBudget['AllocatedBudget'] = (int)$allocatedBudget;
    $ResourceBudget['ConsumedBudget'] = (int)$allocatedBudget + $pressurePenalty;
    $ResourceBudget['RemainingBudget'] = $usableBudget - (int)$allocatedBudget;
    if ($ResourceBudget['RemainingBudget'] < 0) $ResourceBudget['RemainingBudget'] = 0;
    if ($ResourceBudget['RemainingBudget'] > 0) $ResourceBudget['AllowNextWorker'] = TRUE;
    $BudgetCalculated = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $baseBudget, $reserveBudget, $pressurePenalty, $usableBudget, $reserveShare, $highestPressure, $systemResources, $allocatedBudget);
  return array($BudgetCalculated, $ResourceBudget); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether one budget request may proceed.
// / Accepts the budget array, the tracked worker registry, the conversion cost & the
// / expected runtime, in that order.
// / Returns an approval boolean & a denial reason string, in that order.
function evaluateBudgetRequest($resourceBudget, $workerRegistry, $conversionCost, $expectedRuntime) {
  // / Set variables.
  global $MaxConcurrentWorkers, $MaxExpectedRuntime, $EnableMemoryProtection;
  $RequestApproved = FALSE;
  $DenialReason = '';
  $requestedCost = (int)$conversionCost;
  $requestedRuntime = (int)$expectedRuntime;
  $trackedWorkers = count($workerRegistry);
  if ($requestedCost < 1) $requestedCost = 1;
  // / A runtime below one is not shorter than the maximum, it is nonsense. Left alone it
  // / passed the ceiling test & registered a worker that findStaleWorkers judged overdue
  // / on its very first sweep.
  if ($requestedRuntime < 1) $requestedRuntime = 1;
  if ($requestedRuntime > (int)$MaxExpectedRuntime) $DenialReason = 'The expected runtime exceeds the configured maximum.';
  else if ((int)$MaxConcurrentWorkers > 0 && $trackedWorkers >= (int)$MaxConcurrentWorkers) $DenialReason = 'The concurrent worker limit has been reached.';
  else if (!isset($resourceBudget['RemainingBudget'])) $DenialReason = 'The resource budget is unavailable.';
  else if ((int)$resourceBudget['RemainingBudget'] < $requestedCost) $DenialReason = 'The remaining resource budget is insufficient.';
  else $RequestApproved = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $requestedCost, $requestedRuntime, $trackedWorkers, $resourceBudget, $workerRegistry, $conversionCost, $expectedRuntime);
  return array($RequestApproved, $DenialReason); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to add an approved worker to the tracked registry.
// / Accepts the worker process identifier, the cost & the expected runtime.
// / Returns a success boolean & the issued budget token, in that order.
function registerTrackedWorker(&$workerRegistry, $workerPid, $conversionCost, $expectedRuntime) {
  // / Set variables.
  global $EnableMemoryProtection;
  $WorkerWasRegistered = FALSE;
  $BudgetToken = bin2hex(random_bytes(12));
  // / The kernel start time is recorded beside the number, so the worker can still be
  // / identified after the number itself has been reused by something else.
  $workerRegistry[$BudgetToken] = array(
    'WorkerPid' => (int)$workerPid,
    'ProcessStartTime' => readProcessStartTime((int)$workerPid),
    'ConversionCost' => (int)$conversionCost,
    'ExpectedRuntime' => (int)$expectedRuntime,
    'StartTime' => time(),
    'CheckTime' => time(),
    'Extensions' => 0);
  // / The assignment above cannot fail, so the registration is confirmed by reading the
  // / record back rather than by testing that the key exists.
  if (isset($workerRegistry[$BudgetToken]['WorkerPid']) && $workerRegistry[$BudgetToken]['WorkerPid'] === (int)$workerPid) $WorkerWasRegistered = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $workerPid, $conversionCost, $expectedRuntime);
  return array($WorkerWasRegistered, $BudgetToken); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove a completed worker from the tracked registry.
// / Accepts the budget token issued at registration.
// / Returns TRUE when the worker was found & released.
function releaseTrackedWorker(&$workerRegistry, $budgetToken) {
  // / Set variables.
  global $EnableMemoryProtection;
  $WorkerWasReleased = FALSE;
  $cleanToken = preg_replace('/[^a-f0-9]/', '', (string)$budgetToken);
  if ($cleanToken !== '' && isset($workerRegistry[$cleanToken])) {
    unset($workerRegistry[$cleanToken]);
    $WorkerWasReleased = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cleanToken, $budgetToken);
  return $WorkerWasReleased; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to extend the expected runtime of a tracked worker.
// / Accepts the budget token & the number of seconds requested.
// / Returns an approval boolean & the new expected runtime, in that order.
function extendTrackedWorker(&$workerRegistry, $budgetToken, $requestedSeconds) {
  // / Set variables.
  global $MaxRuntimeExtensions, $MaxExpectedRuntime, $EnableMemoryProtection;
  $ExtensionApproved = FALSE;
  $NewExpectedRuntime = 0;
  $cleanToken = preg_replace('/[^a-f0-9]/', '', (string)$budgetToken);
  $proposedRuntime = 0;
  if ($cleanToken !== '' && isset($workerRegistry[$cleanToken])) {
    $NewExpectedRuntime = (int)$workerRegistry[$cleanToken]['ExpectedRuntime'];
    $proposedRuntime = $NewExpectedRuntime + max(1, (int)$requestedSeconds);
    if ((int)$workerRegistry[$cleanToken]['Extensions'] < (int)$MaxRuntimeExtensions && $proposedRuntime <= (int)$MaxExpectedRuntime) {
      $workerRegistry[$cleanToken]['ExpectedRuntime'] = $proposedRuntime;
      $workerRegistry[$cleanToken]['Extensions'] = (int)$workerRegistry[$cleanToken]['Extensions'] + 1;
      $workerRegistry[$cleanToken]['CheckTime'] = time();
      $NewExpectedRuntime = $proposedRuntime;
      $ExtensionApproved = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cleanToken, $proposedRuntime, $budgetToken, $requestedSeconds);
  return array($ExtensionApproved, $NewExpectedRuntime); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the kernel start time of one process.
// / Accepts the process identifier. Returns the start time in clock ticks, or zero.
// / A process identifier on its own does not identify a process.
// / The kernel reuses numbers, & a worker that exited can have its number taken by
// / something unrelated within the life of one conversion. Pairing the number with the
// / start time the kernel recorded gives an identity that cannot be inherited, because a
// / new process occupying the same number necessarily started later.
// / Field 22 of /proc/PID/stat is that start time. It is read from the end of the line
// / rather than by splitting on spaces, because field 2 is the executable name & a
// / process may legitimately have spaces or brackets in it.
function readProcessStartTime($processId) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ProcessStartTime = 0;
  $cleanPid = (int)$processId;
  $statContents = $statTail = '';
  $statFields = array();
  if ($cleanPid > 1) {
    $statContents = @file_get_contents('/proc/'.$cleanPid.'/stat');
    if (is_string($statContents) && strrpos($statContents, ')') !== FALSE) {
      $statTail = trim(substr($statContents, strrpos($statContents, ')') + 1));
      $statFields = preg_split('/\s+/', $statTail);
      // / The tail begins at field 3, so field 22 sits at offset 19.
      if (is_array($statFields) && isset($statFields[19])) $ProcessStartTime = (int)$statFields[19]; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cleanPid, $statContents, $statTail, $statFields, $processId);
  return $ProcessStartTime; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to find tracked workers that have outlived their expected runtime.
// / Accepts a grace period in seconds added to every expected runtime.
// / Returns a scan boolean & an array of stale token to process identifier pairs.
// / A worker whose process has already exited is reported so its budget can be reclaimed.
function findStaleWorkers($workerRegistry, $graceSeconds) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ScanCompleted = TRUE;
  $StaleWorkers = array();
  $workerToken = '';
  $workerRecord = array();
  $workerAge = $workerDeadline = $recordedStart = $currentStart = 0;
  foreach ($workerRegistry as $workerToken => $workerRecord) {
    $workerAge = time() - (int)$workerRecord['StartTime'];
    $workerDeadline = (int)$workerRecord['ExpectedRuntime'] + (int)$graceSeconds;
    // / A process that no longer exists never sent its completion message.
    // / A number that is alive but no longer carries the start time recorded at
    // / registration belongs to something else entirely. The worker is gone & its budget is
    // / reclaimed, but the reason says so plainly, because the alternative is signalling a
    // / process this application never started.
    $recordedStart = isset($workerRecord['ProcessStartTime']) ? (int)$workerRecord['ProcessStartTime'] : 0;
    $currentStart = readProcessStartTime((int)$workerRecord['WorkerPid']);
    if (!managerProcessIsAlive((int)$workerRecord['WorkerPid'])) $StaleWorkers[$workerToken] = array('WorkerPid' => (int)$workerRecord['WorkerPid'], 'ProcessStartTime' => $recordedStart, 'StaleReason' => 'Process has exited');
    else if ($recordedStart > 0 && $currentStart > 0 && $currentStart !== $recordedStart) $StaleWorkers[$workerToken] = array('WorkerPid' => 0, 'ProcessStartTime' => $recordedStart, 'StaleReason' => 'Process has exited & its identifier has been reused');
    else if ($workerAge > $workerDeadline) $StaleWorkers[$workerToken] = array('WorkerPid' => (int)$workerRecord['WorkerPid'], 'ProcessStartTime' => $recordedStart, 'StaleReason' => 'Runtime exceeded by '.($workerAge - $workerDeadline).' second(s)'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $workerToken, $workerRecord, $workerAge, $workerDeadline, $recordedStart, $currentStart, $workerRegistry, $graceSeconds);
  return array($ScanCompleted, $StaleWorkers); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to terminate one worker process.
// / Accepts the process identifier, a boolean requesting an immediate kill & the kernel
// / start time recorded when the worker was registered, in that order.
// / Returns TRUE when the process is no longer present after the attempt.
// / A start time of zero skips the identity check, for a caller that has none to offer.
// / This is the only place in the component that acts on a process without negotiation.
function terminateWorkerProcess($workerPid, $forceKill, $expectedStartTime) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ProcessWasTerminated = FALSE;
  $cleanPid = (int)$workerPid;
  $killSignal = 15;
  $killOutput = array();
  $killExitCode = 1;
  $currentStart = 0;
  if ($forceKill) $killSignal = 9;
  // / Never signal the whole process group & never signal process zero.
  if ($cleanPid > 1 && $cleanPid !== getmypid()) {
    $currentStart = readProcessStartTime($cleanPid);
    if (!file_exists('/proc/'.$cleanPid)) $ProcessWasTerminated = TRUE;
    // / The number is alive but is not the process that was registered against it.
    // / Signalling here would kill something this application never started, as root.
    // / The worker is gone either way, so the caller is told the process is no longer
    // / present & nothing is signalled.
    else if ((int)$expectedStartTime > 0 && $currentStart > 0 && $currentStart !== (int)$expectedStartTime) {
      warningEntry('Worker '.$cleanPid.' was not terminated because its identifier now belongs to a different process. The worker had already exited.');
      $ProcessWasTerminated = TRUE; }
    else {
      exec('kill -'.$killSignal.' '.escapeshellarg((string)$cleanPid).' 2>&1', $killOutput, $killExitCode);
      usleep(250000);
      if (!file_exists('/proc/'.$cleanPid)) $ProcessWasTerminated = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cleanPid, $killSignal, $killOutput, $killExitCode, $currentStart, $workerPid, $forceKill, $expectedStartTime);
  return $ProcessWasTerminated; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to terminate every tracked worker.
// / Accepts no arguments.
// / Returns the number of processes terminated.
function killTrackedWorkers(&$workerRegistry) {
  // / Set variables.
  global $ManagerSocketTimeout, $EnableMemoryProtection;
  $WorkersKilled = 0;
  $workerToken = '';
  $workerRecord = $replyPayload = array();
  $messageWasDelivered = FALSE;
  foreach ($workerRegistry as $workerToken => $workerRecord) {
    // / The recorded start time travels with the request, so the worker manager can refuse
    // / to signal a number that now belongs to something else.
    list ($messageWasDelivered, $replyPayload) = sendManagerMessage(buildManagerSocketPath('worker-manager'), array('RequestType' => 'kill', 'WorkerPid' => (int)$workerRecord['WorkerPid'], 'ProcessStartTime' => (isset($workerRecord['ProcessStartTime']) ? (int)$workerRecord['ProcessStartTime'] : 0)), 'internal', (int)$ManagerSocketTimeout);
    if ($messageWasDelivered && isset($replyPayload['Approved']) && $replyPayload['Approved'] === TRUE) $WorkersKilled++; }
  $workerRegistry = array();
  warningEntry('Every tracked worker was ended on request. '.$WorkersKilled.' process(es) ended.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $workerToken, $workerRecord, $replyPayload, $messageWasDelivered);
  return $WorkersKilled; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run the Core Manager in the FOREGROUND, for a supervisor to own.
// / Accepts no arguments & does not return until the listener is stopped.
// / Returns TRUE when the loop exited on a stop instruction rather than a failure.
// / THIS IS THE ENTRY POINT A SERVICE MANAGER USES. It does not fork & does not return, so
// / systemd can watch the actual process, restart it when it dies & report its true state.
// / startCoreManagerListener spawns & returns instead, which leaves nothing watching Core
// / Manager itself. Core Manager supervises its three subordinates either way.
// / The startup key is derived here rather than supplied, because a service manager has no
// / way to compute one. The authorization it proves is preserved regardless. A standard
// / user holds a per user secret, so the key they derive cannot validate & this refuses.
function runCoreManagerForeground() {
  // / Set variables.
  global $ApacheUser, $CurrentUser, $RunningAsRoot, $EnableMemoryProtection;
  $ListenerExitedCleanly = $environmentIsReady = FALSE;
  $startupKey = '';
  $callerIsAuthorized = FALSE;
  $environmentFindings = array();
  if ($RunningAsRoot or $CurrentUser === $ApacheUser) $callerIsAuthorized = TRUE;
  if (!$callerIsAuthorized) errorEntry('The foreground listener was started by an account that may not operate it!', 31014, TRUE);
  else {
    // / Running as root would leave every socket owned by root & unreachable by a worker.
    // / A service manager should set User in the unit. This is the last line of defence.
    if ($RunningAsRoot) warningEntry('The foreground listener is running as root. Every socket it creates will be owned by root & no web worker will be able to reach it. Set User to '.$ApacheUser.' in the service unit.');
    $startupKey = deriveStartupKey('start-core-manager');
    if ($startupKey === '') errorEntry('The foreground listener could not derive a startup key. No install secret is available!', 31015, TRUE);
    else {
      logEntry('The Core Manager listener was started in the foreground as process '.getmypid().'.');
      // / Report the environment once at startup. A listener metering a server that cannot
      // / convert anything is worth knowing about before the first request arrives, not
      // / after. This never refuses to start, because the listener is not what is broken.
      list ($environmentIsReady, $environmentFindings) = validateOperatingEnvironment();
      if (!$environmentIsReady) warningEntry('The listener is starting into a degraded environment. The sandbox is unavailable & conversions requiring it will be refused. Run the -fp argument as root.');
      prepareManagerSocketDir();
      // / This key is derived & checked inside this process & never leaves it, so it is
      // / not spent. Spending it would refuse a service manager restarting inside the same
      // / window, because the key it derives on the way back up is byte for byte the same.
      $ListenerExitedCleanly = dispatchManagerRole('core-manager', $startupKey, FALSE); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $startupKey, $callerIsAuthorized, $environmentIsReady, $environmentFindings);
  return $ListenerExitedCleanly; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report whether a service unit owns this listener.
// / Accepts no arguments.
// / Returns TRUE when a unit named hrconvert2-listener is installed & enabled.
// / Two ways to start one listener produces two listeners. When a unit owns the lifecycle,
// / the -l argument defers to it rather than starting a second one beside it.
function listenerUnitIsInstalled() {
  // / Set variables.
  global $EnableMemoryProtection;
  $UnitIsInstalled = FALSE;
  $systemctlBinary = '';
  $commandOutput = array();
  $commandExitCode = 1;
  // / A unit can only own this listener when systemd is actually running. Deferring to a
  // / service manager that is not there meant the listener never started at all, & the
  // / start reported success because systemctl was on the PATH.
  $systemctlBinary = systemdIsUsable()[0] ? locateDependency('systemctl') : '';
  if ($systemctlBinary !== '') {
    exec(escapeshellarg($systemctlBinary).' is-enabled hrconvert2-listener 2>/dev/null', $commandOutput, $commandExitCode);
    if ($commandExitCode === 0) $UnitIsInstalled = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $systemctlBinary, $commandOutput, $commandExitCode);
  return $UnitIsInstalled; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to start the listener from an authorized command line invocation.
// / Accepts no arguments.
// / Returns TRUE when the Core Manager process was launched & recorded itself.
// / A listener that is already running is not started twice.
// / A service unit that owns this listener is started through the service manager instead.
function startCoreManagerListener() {
  // / Set variables.
  global $InstLoc, $ManagerSocketDir, $ApacheUser, $CurrentUser, $RunningAsRoot, $TotalResourceBudget, $ReserveResourcePercentage, $MaxConcurrentWorkers, $Verbose, $Lol, $EnableMemoryProtection;
  $ListenerWasStarted = FALSE;
  $managerState = $chownOutput = $listenerStatus = array();
  $stateWasRead = $directoryIsReady = $listenerIsRunning = FALSE;
  $startupKey = $spawnCommand = $innerCommand = $managerRole = '';
  $chownExitCode = $listenerPid = $waitCounter = $managerPid = $unitExitCode = 0;
  $unitOwnsListener = FALSE;
  $unitOutput = array();
  // / A service unit that owns this listener is the only thing that should start it.
  // / Starting a second one beside it produces two Core Managers fighting over one socket.
  if (listenerUnitIsInstalled()) {
    $unitOwnsListener = TRUE;
    print($Lol.'A service unit owns this listener, so it is started through the service manager.'.$Lol);
    exec('systemctl start hrconvert2-listener 2>&1', $unitOutput, $unitExitCode);
    if ($unitExitCode === 0) print('  systemctl start hrconvert2-listener'.$Lol.$Lol);
    else {
      print('  systemctl start hrconvert2-listener FAILED'.$Lol);
      print('  '.implode($Lol.'  ', $unitOutput).$Lol.$Lol); } }
  else $directoryIsReady = prepareManagerSocketDir();
  // / Hand the socket directory to the web server user before anything binds inside it.
  // / A root owned directory is unreachable to the workers that must connect.
  if (!$unitOwnsListener && $directoryIsReady && $RunningAsRoot) exec('chown -R '.escapeshellarg($ApacheUser).':'.escapeshellarg($ApacheUser).' '.escapeshellarg($ManagerSocketDir).' 2>&1', $chownOutput, $chownExitCode);
  list ($stateWasRead, $managerState) = readManagerState('managers');
  if ($unitOwnsListener) $ListenerWasStarted = TRUE;
  else if ($stateWasRead && isset($managerState['CoreManagerPid']) && managerProcessIsAlive((int)$managerState['CoreManagerPid'])) {
    print($Lol.'The listener is already running as process '.(int)$managerState['CoreManagerPid'].'.'.$Lol);
    print('Stop it with -k before starting another.'.$Lol.$Lol); }
  else if (!$directoryIsReady) errorEntry('The Core Manager socket directory could not be prepared!', 31004, FALSE);
  else {
    // / A stale record from a listener that died leaves a pid that no longer exists.
    writeManagerState('managers', array());
    $startupKey = deriveStartupKey('start-core-manager');
    // / The key travels in the environment for the same reason it does for a subordinate.
    // / The assignment sits inside the inner command, so it survives the su that follows.
    $innerCommand = 'HRCONVERT2_STARTUP_KEY='.escapeshellarg($startupKey)
      .' nohup php '.escapeshellarg($InstLoc.DIRECTORY_SEPARATOR.'convertCore.php')
      .' --start-core-manager'
      .' > /dev/null 2>&1 &';
    // / Drop to the web server user so every socket is owned by the account that must reach it.
    if ($RunningAsRoot && $CurrentUser !== $ApacheUser) $spawnCommand = 'su -s /bin/sh '.escapeshellarg($ApacheUser).' -c '.escapeshellarg($innerCommand);
    else $spawnCommand = $innerCommand;
    if ($Verbose) logEntry('Launching the Core Manager listener as '.($RunningAsRoot ? $ApacheUser.' through su' : $CurrentUser).'.');
    shell_exec($spawnCommand);
    // / The listener records its own pid, so wait for that rather than reading one back
    // / through su. Five seconds is generous for a process that only opens a socket.
    while ($waitCounter < 20 && $listenerPid < 1) {
      usleep(250000);
      list ($stateWasRead, $managerState) = readManagerState('managers');
      if ($stateWasRead && isset($managerState['CoreManagerPid'])) $listenerPid = (int)$managerState['CoreManagerPid'];
      $waitCounter++; }
    if ($listenerPid > 0 && file_exists('/proc/'.$listenerPid)) {
      $ListenerWasStarted = TRUE;
      logEntry('The Core Manager listener was started as process '.$listenerPid.' after '.round($waitCounter * 0.25, 2).' second(s).');
      // / Give the subordinates a moment to bind before reporting what came up.
      sleep(1);
      if ($Verbose) logEntry('Stop instruction sent to the Core Manager. Confirming every manager has ended.');
      list ($listenerIsRunning, $listenerStatus) = reportListenerStatus();
      print($Lol.'Listener started.'.$Lol);
      print($Lol.'  '.str_pad('Core Manager', 24).'process '.$listenerPid.$Lol);
      if (isset($listenerStatus['Subordinates'])) {
        foreach ($listenerStatus['Subordinates'] as $managerRole => $managerPid) print('  '.str_pad($managerRole, 24).'process '.(int)$managerPid.(file_exists('/proc/'.(int)$managerPid) ? '' : '  NOT RUNNING').$Lol); }
      print($Lol.'  '.str_pad('Running as', 24).$ApacheUser.$Lol);
      print('  '.str_pad('Socket directory', 24).$ManagerSocketDir.$Lol);
      print('  '.str_pad('Total budget', 24).((int)$TotalResourceBudget < 1 ? 'AUTO, derived from the processor count' : (int)$TotalResourceBudget.' cost units').$Lol);
      print('  '.str_pad('Reserved share', 24).(int)$ReserveResourcePercentage.'%'.$Lol);
      print('  '.str_pad('Concurrent limit', 24).((int)$MaxConcurrentWorkers < 1 ? 'NONE, the budget decides alone' : (int)$MaxConcurrentWorkers).$Lol);
      print($Lol.'Check it with --status & stop it with -k.'.$Lol.$Lol);
      if (count($listenerStatus['Subordinates']) < 3) print('One or more subordinate managers did not start. See the log.'.$Lol.$Lol); }
    else {
      errorEntry('The Core Manager listener could not be started!', 31005, FALSE);
      // / The startup key is NEVER printed & never written to the log. It authorizes a
      // / manager process for the length of a key window, & anything that captures console
      // / output or a logfile would be handed a working one.
      print($Lol.'The listener did not record itself within five seconds.'.$Lol);
      print('Check the log for the reason. A manager that started records itself there.'.$Lol);
      print('Confirm '.$ApacheUser.' can read the install secret & write the logfile,'.$Lol);
      print('then run the -fp argument as root & try again.'.$Lol.$Lol); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $managerState, $chownOutput, $listenerStatus, $stateWasRead, $directoryIsReady, $listenerIsRunning, $startupKey, $spawnCommand, $innerCommand, $managerRole, $chownExitCode, $listenerPid, $waitCounter, $managerPid, $unitOwnsListener, $unitOutput, $unitExitCode);
  return $ListenerWasStarted; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to stop the listener & every subordinate manager.
// / Accepts no arguments.
// / Returns TRUE when no manager process remains.
// / Every process is asked first, then signalled, then verified. A process is only reported
// / as surviving once it has been checked several times, because a process that has been
// / signalled does not disappear from the process table instantly.
// / A recorded process identifier that no longer belongs to a manager is treated as stopped
// / rather than reported as an orphan, because a stale record is not a running process.
function stopCoreManagerListener() {
  // / Set variables.
  global $Lol, $ManagerSocketTimeout, $Verbose, $EnableMemoryProtection;
  $ListenerWasStopped = TRUE;
  $managerState = $replyPayload = $survivingProcesses = $recordedProcesses = array();
  $stateWasRead = $messageWasDelivered = FALSE;
  $managerRole = $processCommand = '';
  $managerPid = $waitCounter = 0;
  list ($stateWasRead, $managerState) = readManagerState('managers');
  if (!$stateWasRead or !isset($managerState['CoreManagerPid'])) {
    print($Lol.'No listener is recorded as running.'.$Lol.$Lol);
    $ListenerWasStopped = FALSE; }
  else {
    // / Collect every recorded process. The Core Manager is stopped last so it does not
    // / restart a subordinate that has already been ended.
    if (isset($managerState['Subordinates'])) {
      foreach ($managerState['Subordinates'] as $managerRole => $managerPid) $recordedProcesses[$managerRole] = (int)$managerPid; }
    $recordedProcesses['core-manager'] = (int)$managerState['CoreManagerPid'];
    // / Ask first. The Core Manager stops its subordinates in order when it is told to.
    list ($messageWasDelivered, $replyPayload) = sendManagerMessage(buildManagerSocketPath('core-manager'), array('RequestType' => 'stop'), 'core', (int)$ManagerSocketTimeout);
    if ($Verbose) logEntry('Stop instruction '.($messageWasDelivered ? 'delivered' : 'could not be delivered').'. Confirming every manager has ended.');
    // / Give a manager that accepted the instruction a moment to unwind on its own.
    $waitCounter = 0;
    while ($waitCounter < 8) {
      usleep(250000);
      $survivingProcesses = array();
      foreach ($recordedProcesses as $managerRole => $managerPid) { if (managerProcessIsAlive($managerPid)) $survivingProcesses[$managerRole] = $managerPid; }
      if (empty($survivingProcesses)) break;
      $waitCounter++; }
    // / Signal whatever did not leave. Subordinates first, then the Core Manager.
    foreach ($recordedProcesses as $managerRole => $managerPid) {
      if (managerProcessIsAlive($managerPid)) terminateWorkerProcess($managerPid, TRUE, 0); }
    // / Verify. A process that is still present after this is reported by name.
    $survivingProcesses = array();
    foreach ($recordedProcesses as $managerRole => $managerPid) {
      if (managerProcessIsAlive($managerPid)) $survivingProcesses[$managerRole] = $managerPid; }
    writeManagerState('managers', array());
    if (empty($survivingProcesses)) {
      logEntry('The Core Manager listener was stopped.');
      print($Lol.'Listener stopped.'.$Lol.$Lol); }
    else {
      $ListenerWasStopped = FALSE;
      warningEntry(count($survivingProcesses).' manager process(es) survived a stop instruction & a kill signal.');
      print($Lol.'The listener could not be fully stopped.'.$Lol);
      foreach ($survivingProcesses as $managerRole => $managerPid) {
        $processCommand = trim((string)@file_get_contents('/proc/'.(int)$managerPid.'/comm'));
        print('  '.str_pad($managerRole, 20).'process '.(int)$managerPid.' is still present, running '.($processCommand === '' ? 'an unknown command' : $processCommand).$Lol); }
      print($Lol.'Kill them by hand, then run -l again.'.$Lol.$Lol); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $managerState, $replyPayload, $survivingProcesses, $recordedProcesses, $stateWasRead, $messageWasDelivered, $managerRole, $processCommand, $managerPid, $waitCounter);
  return $ListenerWasStopped; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report whether a recorded process identifier is a live manager process.
// / Accepts the process identifier.
// / Returns TRUE only when the process exists & is not a zombie awaiting collection.
// / A killed process whose parent has not reaped it keeps its entry under /proc, so testing
// / for the directory alone reports a process that has already ended as still running.
function managerProcessIsAlive($processId) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ProcessIsAlive = FALSE;
  $cleanPid = (int)$processId;
  $processState = $stateField = '';
  $stateMatches = array();
  if ($cleanPid > 1 && file_exists('/proc/'.$cleanPid.'/stat')) {
    $processState = (string)@file_get_contents('/proc/'.$cleanPid.'/stat');
    // / The state letter follows the command name, which may itself contain spaces & brackets.
    if (preg_match('/\)\s+(\S)/', $processState, $stateMatches)) $stateField = $stateMatches[1];
    if ($stateField !== '' && $stateField !== 'Z' && $stateField !== 'X') $ProcessIsAlive = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cleanPid, $processState, $stateField, $stateMatches, $processId);
  return $ProcessIsAlive; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to create & secure the socket directory.
// / Accepts no arguments.
// / Returns TRUE when the directory exists & is writable.
function prepareManagerSocketDir() {
  // / Set variables.
  global $ManagerSocketDir, $EnableMemoryProtection;
  $DirectoryIsReady = FALSE;
  if (!is_dir($ManagerSocketDir)) @mkdir($ManagerSocketDir, 0700, TRUE);
  if (is_dir($ManagerSocketDir)) {
    @chmod($ManagerSocketDir, 0700);
    if (is_writable($ManagerSocketDir)) $DirectoryIsReady = TRUE; }
  if (!$DirectoryIsReady) warningEntry('The manager socket directory at '.$ManagerSocketDir.' is not usable.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection);
  return $DirectoryIsReady; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to dispatch an internal manager role after its startup key is validated.
// / Accepts the role name & the supplied startup key.
// / Returns TRUE when a role ran to completion.
// / This is the single entry point convertCore.php uses to enter a manager process.
// / The third argument says whether the key reached this process from another one.
// / A key that crossed a boundary is spent, because a copy of it may exist elsewhere.
// / A key derived & checked inside one process is NOT spent. Derivation is deterministic
// / within a window, so the foreground listener restarting inside ten seconds would mint
// / the identical key & refuse itself as a replay, which under a service manager is a
// / restart loop rather than a security control.
function dispatchManagerRole($managerRole, $suppliedKey, $keyWasTransported = TRUE) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RoleWasDispatched = FALSE;
  $keyIsValid = FALSE;
  $cleanRole = preg_replace('/[^a-z\-]/', '', strtolower((string)$managerRole));
  $keyPurpose = $managerVersion = '';
  $managerFiles = array();
  $managerIsAvailable = FALSE;
  if ($cleanRole === 'core-manager') $keyPurpose = 'start-core-manager';
  else $keyPurpose = 'start-manager-'.$cleanRole;
  $keyIsValid = validateStartupKey($keyPurpose, $suppliedKey, $keyWasTransported);
  if (!$keyIsValid) errorEntry('A manager process was started with an invalid startup key!', 31006, TRUE);
  else {
    prepareManagerSocketDir();
    logEntry('A '.$cleanRole.' process started as '.getmypid().' & validated its startup key.');
    // / The role's own file is loaded here & nowhere else.
    // / A process running as one role never parses the other three, & a request that is
    // / not a manager at all never parses any of them.
    // / A role name maps to its file by a table rather than by assembling a filename from
    // / the role, because a name that assembles into a path is a name somebody can steer.
    $managerFiles = array(
      'core-manager' => 'coreManager.php',
      'resource-manager' => 'resourceManager.php',
      'worker-manager' => 'workerManager.php',
      'request-manager' => 'requestManager.php');
    if (!isset($managerFiles[$cleanRole])) errorEntry('An unrecognized manager role was requested!', 31007, TRUE);
    else {
      list ($managerIsAvailable, $managerVersion) = loadEngineManager($managerFiles[$cleanRole]);
      if (!$managerIsAvailable) errorEntry('The manager subcomponent for this role could not be loaded!', 31007, TRUE);
      else if ($cleanRole === 'core-manager') $RoleWasDispatched = runCoreManager();
      else if ($cleanRole === 'request-manager') $RoleWasDispatched = runRequestManager();
      else if ($cleanRole === 'resource-manager') $RoleWasDispatched = runResourceManager();
      else $RoleWasDispatched = runWorkerManager(); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $managerFiles, $managerIsAvailable, $managerVersion, $keyIsValid, $cleanRole, $keyPurpose, $managerRole, $suppliedKey, $keyWasTransported);
  return $RoleWasDispatched; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report listener status for the command line.
// / Accepts no arguments.
// / Returns a running boolean & a status array, in that order.
function reportListenerStatus() {
  // / Set variables.
  global $ManagerSocketTimeout, $EnableMemoryProtection;
  $ListenerIsRunning = FALSE;
  $ListenerStatus = array('CoreManagerPid' => 0, 'Subordinates' => array(), 'TrackedWorkers' => 0, 'SessionLocations' => 0, 'Budget' => array(), 'Answered' => FALSE);
  $managerState = $replyPayload = array();
  $stateWasRead = $messageWasDelivered = FALSE;
  list ($stateWasRead, $managerState) = readManagerState('managers');
  if ($stateWasRead && isset($managerState['CoreManagerPid'])) {
    $ListenerStatus['CoreManagerPid'] = (int)$managerState['CoreManagerPid'];
    if (managerProcessIsAlive((int)$managerState['CoreManagerPid'])) $ListenerIsRunning = TRUE;
    if (isset($managerState['Subordinates'])) $ListenerStatus['Subordinates'] = $managerState['Subordinates']; }
  if ($ListenerIsRunning) {
    list ($messageWasDelivered, $replyPayload) = sendManagerMessage(buildManagerSocketPath('core-manager'), array('RequestType' => 'status'), 'core', (int)$ManagerSocketTimeout * 3);
    if ($messageWasDelivered && isset($replyPayload['TrackedWorkers'])) {
      $ListenerStatus['TrackedWorkers'] = (int)$replyPayload['TrackedWorkers'];
      $ListenerStatus['SessionLocations'] = (int)($replyPayload['SessionLocations'] ?? 0);
      $ListenerStatus['Budget'] = (isset($replyPayload['Budget']) && is_array($replyPayload['Budget'])) ? $replyPayload['Budget'] : array();
      $ListenerStatus['Answered'] = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $managerState, $replyPayload, $stateWasRead, $messageWasDelivered);
  return array($ListenerIsRunning, $ListenerStatus); }
// / -----------------------------------------------------------------------------------

?>
