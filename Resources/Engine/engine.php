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
// / v3.9.1.
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
$EngineVersion = 'v3.9.1';
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
    'coreManager.php' => 'v3.9.0',
    'resourceManager.php' => 'v3.9.0',
    'workerManager.php' => 'v3.9.0',
    'requestManager.php' => 'v3.9.0');
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
// / Filesystem helpers the data location functions depend on.
// / None of these know what the files they are looking at are for.
// / They moved with cleanDataLocation, because a function that moves without the helpers
// / it quietly depends on is a function that only appears to have moved.
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to return the file time of a specified file.
// / Only returns a value if the specified file exists.
// / Returns FALSE when the path cannot be read.
function fileTime($filePath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $Stat = FALSE;
  if (file_exists($filePath)) $Stat = @filemtime($filePath);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $filePath);
  return $Stat; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to determine whether a folder holds nothing but protected file objects.
// / A hosted session directory always contains an index.html file for document root protection.
// / This overlooks the required files and only looks to see if any user requested files remain.
function isDirEmptyOfUserFiles($path) {
  // / Set variables.
  // / Every filesystem lists these two & neither is ever a file somebody uploaded.
  // / This was a global named for the application. It is a local here, because a directory
  // / listing means the same thing to every application there has ever been.
  global $EnableMemoryProtection;
  $defaultApps = array('.', '..');
  $DirIsEmptyOfUserFiles = FALSE;
  $remaining = array();
  if (is_dir($path)) {
    $remaining = array_diff(scandir($path), array('..', '.'));
    // / Discard every protected file object. Whatever is left belongs to a user.
    $remaining = array_diff($remaining, $defaultApps);
    if (empty($remaining)) $DirIsEmptyOfUserFiles = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $defaultApps, $remaining, $path);
  return $DirIsEmptyOfUserFiles; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove a session directory once nothing of the user's remains in it.
// / Protected file objects such as the enforced index.html are removed only at this point.
function removeEmptiedSessionDir($sessionPath) {
  // / Set variables.
  global $DirSep, $EnableMemoryProtection;
  $SessionDirRemoved = FALSE;
  $leftovers = array();
  $leftover = '';
  if (isDirEmptyOfUserFiles($sessionPath)) {
    $leftovers = array_diff(scandir($sessionPath), array('..', '.'));
    // / Only protected file objects can be left at this point. Remove them with the directory.
    foreach ($leftovers as $leftover) {
      if (is_file($sessionPath.$DirSep.$leftover)) @unlink($sessionPath.$DirSep.$leftover); }
    @rmdir($sessionPath);
    if (!is_dir($sessionPath)) $SessionDirRemoved = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $leftovers, $leftover, $sessionPath);
  return $SessionDirRemoved; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to test if a folder is empty.
// / Returns TRUE only when the folder exists & holds nothing at all.
// / Returns FALSE when the folder holds anything, or when the path is not a folder.
// / Every directory contains a . and a .. entry, so both are discarded before testing.
function is_dir_empty($dir) {
  // / Set variables.
  global $EnableMemoryProtection;
  $Check = TRUE;
  $contents = array();
  // / Make sure the selected directory is actually a directory.
  if (is_dir($dir)) {
    // / Gather the contents of the directory, discarding the two entries every directory has.
    $contents = array_diff(scandir($dir), array('.', '..'));
    // / Anything left over means the directory holds something.
    if (!empty($contents)) $Check = FALSE; }
  // / A path that is not a directory at all must never be reported as an empty one.
  else $Check = FALSE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dir, $contents);
  return $Check; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to clean a selection of files.
// / Recursively deletes files.
// / This function is extremely dangerous! Please handle with care.
// / This function refuses to operate on anything outside a root the caller named. Both sides of the comparison are passed
// / through realpath() first, so a path containing .. cannot walk out of an approved root
// / while still matching it as a string prefix. If a future edit ever hands this function
// / the wrong variable, the result is a no-op & a FALSE return, not an incident.
// / The second argument is the list of roots this caller may clean under.
// / It used to be built here from the application's own paths, which meant a generic
// / cleaner knew the name of one application's directories.
// / The caller supplies the list instead & an arbitrary path is still refused for not
// / being under one of them, which is the guard that mattered.
function cleanFiles($path, $allowedRoots) {
  // / Set variables.
  // / Every filesystem lists these two & neither is ever a file somebody uploaded.
  global $DirSep, $EnableMemoryProtection;
  $defaultApps = array('.', '..');
  $variableIsSanitized = $CleanSuccess = $pathCheck = $pathIsContained = FALSE;
  $loopCheck = TRUE;
  $dirContents = $allowedRoots = array();
  $dirEntry = $childPath = $realPath = $realRoot = $allowedRoot = '';
  list ($path, $variableIsSanitized) = sanitize($path, FALSE);
  // / Assemble every location this function is permitted to operate inside.
  // / The caller's list already holds every maintenance location it needs cleaned.
  // / Resolve the supplied path to its true location before any comparison is made.
  // / realpath() returns FALSE for anything that does not exist, which fails the check below.
  $realPath = realpath($path);
  // / Confirm the resolved path sits inside an approved root & is not the root itself.
  // / The trailing separator on the root is required. Without it a sibling directory named
  // / like a sibling directory would match a root as a prefix & be accepted.
  if ($realPath !== FALSE) {
    foreach ($allowedRoots as $allowedRoot) {
      if (empty($allowedRoot)) continue;
      $realRoot = realpath($allowedRoot);
      if ($realRoot !== FALSE && strpos($realPath, $realRoot.$DirSep) === 0) {
        $pathIsContained = TRUE;
        break; } } }
  // / Make sure the selected directory is contained, sanitized, & actually a directory.
  if ($pathIsContained && $variableIsSanitized && is_dir($path)) {
    // / Iterate through each file object in the directory.
    $dirContents = array_diff(scandir($path), array('..', '.'));
    foreach ($dirContents as $dirEntry) {
      // / Build the full path to this child ONCE. Every check below refers to the CHILD,
      // / never to the parent. Testing the parent here would choose the wrong branch.
      $childPath = $path.$DirSep.$dirEntry;
      // / Protected file objects are never touched at any depth.
      if (in_array(basename($childPath), $defaultApps)) continue;
      // / If the selected file object is a file, delete it.
      if (is_file($childPath)) @unlink($childPath);
      // / If the selected file object is an empty directory, remove it outright.
      elseif (is_dir($childPath) && is_dir_empty($childPath)) @rmdir($childPath);
      // / If the selected file object is a directory with contents, recurse into it.
      // / A failure anywhere below must propagate up, so $loopCheck is never reset to TRUE here.
      elseif (is_dir($childPath)) {
        if (!cleanFiles($childPath, $allowedRoots)) $loopCheck = FALSE; } }
    // / Once all file objects in the selected directory have been deleted, attempt to delete the selected directory.
    // / The containment check above already prevents reaching any approved root, but this
    // / explicit comparison is retained so the intent survives any future change to that check.
    if (!in_array($path, $allowedRoots, TRUE)) @rmdir($path); }
  // / Check if the function was successful. Note that approved root locations are never deleted.
  $pathCheck = is_dir($path);
  if ($pathCheck && is_dir_empty($path)) $CleanSuccess = TRUE;
  if (!$pathCheck) $CleanSuccess = TRUE;
  // / A failure in any recursive call invalidates the whole operation regardless of what is left here.
  if (!$loopCheck) $CleanSuccess = FALSE;
  // / An uncontained path is never a success. Nothing was cleaned & nothing should report otherwise.
  if (!$pathIsContained) $CleanSuccess = FALSE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $defaultApps, $path, $dirContents, $dirEntry, $childPath, $realPath, $realRoot, $allowedRoot, $allowedRoots, $variableIsSanitized, $pathCheck, $pathIsContained, $loopCheck);
  return $CleanSuccess; }
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
  purgeSensitiveMemory($EnableMemoryProtection, $protectedRootDirs, $dailyDirs, $sessionDirs, $dailyDir, $dailyPath, $locationPath);
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
  $DataLocationPool = array();
  $additionalEntry = $seenPaths = array();
  $entryPath = $entryType = $primaryPath = '';
  // / $primaryPath holds the configured value & is set once, before $activePath is
  // / narrowed to the location this session actually uses.
  // / The caller passes the configured primary & the location this session settled on.
  // / The configured one wins, because the active one has already been narrowed & would
  // / otherwise nominate itself as the primary of a pool it is only a member of.
  if (!is_string($primaryPath) or $primaryPath === '') $primaryPath = (string)$activePath;
  $primaryPath = rtrim($primaryPath, $DirSep);
  $DataLocationPool[] = array('Path' => $primaryPath, 'Type' => 'primary');
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
          $DataLocationPool[] = array('Path' => $entryPath, 'Type' => $entryType);
          $seenPaths[] = $entryPath; } } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $DataLocationPool is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $activePath, $additionalLocations, $additionalEntry, $seenPaths, $entryPath, $entryType, $primaryPath);
  return $DataLocationPool; }
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
  purgeSensitiveMemory($EnableMemoryProtection, $activePath, $additionalLocations, $primaryPath, $protectedRootDirs, $convertLocPool, $distributionPool, $redundantPool, $poolEntry, $selectionMode, $cleanDailyHash, $cleanSessionHash, $sessionCount, $lowestSessionCount, $selectionIndex, $sessionIsDiscovered, $dailyHash, $sessionHash);
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
          if (!cleanFiles($sessionPath, $authorizedPaths)) {
            $CleanedLocation = FALSE; }
          // / Remove the session shell, including any protected file objects still in it.
          removeEmptiedSessionDir($sessionPath); } }
      // / Remove the daily parent only once every session inside it is gone.
      if (isDirEmptyOfUserFiles($dailyPath)) removeEmptiedSessionDir($dailyPath); }
    // / Log the result.
    if ($Verbose) logEntry('Cleaned the '.$locationName.' location. Removed Files: '.($LocationDeepCleaned ? 'TRUE' : 'FALSE').'.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $authorizedPaths, $defaultApps, $protectedRootDirs, $dailyDirs, $dailyDir, $dailyPath, $sessionDirs, $sessionDir, $sessionPath, $now, $dataLoc, $locationName, $deleteThreshold, $cleanAuthorized, $directoryIterator, $iterator, $fileObject, $realPath);
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
// / A function to report whether this application opens a listening socket at all.
// / Accepts nothing. Returns TRUE when it does.
// / An application that hands work downward to less privileged workers never needs one.
// / Nothing untrusted has to reach anything privileged in that shape, so a socket would be
// / an attack surface with no purpose. A socket that does not exist cannot be
// / authenticated to, forged against or replayed.
// / An application that has not declared a direction is assumed to want one, because that
// / is the shape the first application using this Engine had & silently withholding a
// / socket from it would break every conversion with no message worth reading.
function engineOpensSocket() {
  // / Set variables.
  global $EngineDispatchDirection, $EnableMemoryProtection;
  $SocketIsWanted = TRUE;
  if (isset($EngineDispatchDirection) && (string)$EngineDispatchDirection === 'work-downward') $SocketIsWanted = FALSE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection);
  return $SocketIsWanted; }
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
  // / An application that hands work downward opens nothing & says so once.
  // / This is a wrapper rather than an early return, because one exit per function is
  // / what makes the cleanup at the bottom reliable.
  if (!engineOpensSocket()) warningEntry('A listening socket was requested & this application is declared work-downward, so none was opened.');
  else {
    if (file_exists($socketPath)) @unlink($socketPath);
    $SocketServer = @stream_socket_server('unix://'.$socketPath, $socketErrorNumber, $socketError);
    if ($SocketServer !== FALSE) {
      @chmod($socketPath, 0600);
      stream_set_blocking($SocketServer, FALSE);
      $ServerIsOpen = TRUE;
      if ($Verbose) logEntry('Bound a manager socket at '.$socketPath.'.'); }
    else warningEntry('Could not open the manager socket at '.$socketPath.'. '.$socketError); }
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
  purgeSensitiveMemory($EnableMemoryProtection, $socketClient, $socketError, $socketErrorNumber, $encryptedMessage, $rawReply, $bytesWritten, $replyIsValid, $socketPath, $messagePayload, $keyPurpose, $timeoutSeconds);
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
  purgeSensitiveMemory($EnableMemoryProtection, $socketConnection, $socketServer, $rawMessage, $messageIsValid, $messagePayload, $messageCounter, $keyPurpose, $maxMessages, $waitSeconds);
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
  purgeSensitiveMemory($EnableMemoryProtection, $socketConnection, $encryptedReply, $bytesWritten, $replyPayload, $keyPurpose);
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
  // / The socket directory is not chowned here & that is deliberate.
  // / Ownership of every path this application manages belongs to -fp, which runs as
  // / root once & exits. Doing it here meant the listener had to be root to start, &
  // / a listener that starts as root then has to give that up somehow.
  // / The wrong owner is reported rather than corrected, because correcting it needs a
  // / privilege this process should not be holding.
  if ($directoryIsReady && !is_writable($ManagerSocketDir)) warningEntry('The manager socket directory at '.$ManagerSocketDir.' is not writable by this account. Run -fp as root to correct it.');
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
    // / No su. A listener started by its unit already runs as the right account & one
    // / started by hand as root is warned about rather than quietly dropped into place.
    $spawnCommand = $innerCommand;
    if ($Verbose) logEntry('Launching the Core Manager listener as '.($RunningAsRoot ? $ApacheUser : $CurrentUser).'.');
    shell_exec($spawnCommand);
    // / The listener records its own pid, so wait for that rather than reading one back
    // / from the spawn. Five seconds is generous for a process that only opens a socket.
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


// / -----------------------------------------------------------------------------------
// / Network & remote content.
// / Everything below reaches an address somebody else controls, & every line of it exists
// / because that is dangerous.
// / A URL is resolved, the address behind it is checked against every range that is not
// / publicly routable, & the content that arrives is inspected for addresses of its own
// / before anything is allowed to act on it.
// / A refusal here is a WARNING rather than an error. An attacker who was stopped is this
// / application working correctly & is not a fault.
// / None of this knows what a conversion is. An application that fetches a virus
// / definition or a job description wants exactly the same guards.
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report the network policy this installation runs under.
// / Accepts nothing. Returns an array of the five settings every fetch obeys.
// / An Engine that reaches a remote address needs a policy & the policy is per
// / installation rather than per Engine, so it comes from configuration.
// /
// / Each setting is read from an Engine name first & from the application's own name
// / second. That fallback is deliberate & is not clutter.
// / HRConvert2 named these for streams because streaming was the first thing that fetched
// / a URL. They govern every remote fetch now, & renaming a setting in config.php would
// / silently reset it to a default on every installation in the world at the next update.
// / A timeout quietly changing length is not a surprise worth handing anybody.
// / A new application sets the Engine names & has no stream shaped settings at all.
// /
// / Every fallback ends in a literal that configuration cannot overwrite, so a policy
// / exists even when neither name is set & a fetch is never left deciding for itself.
function networkPolicy() {
  // / Set variables.
  global $EngineAllowPlainHTTP, $EngineMaxInspectionBytes, $EngineConnectionTimeout, $EngineWatchTimeout, $EngineFetchTemp, $AllowStreamOverHTTP, $MaxStreamInspectionFileSize, $StreamConnectionTimeout, $StreamWatchTimeout, $StreamTemp, $EnableMemoryProtection;
  $NetworkPolicy = array();
  $NetworkPolicy = array(
    'AllowPlainHTTP' => isset($EngineAllowPlainHTTP) ? (bool)$EngineAllowPlainHTTP : (isset($AllowStreamOverHTTP) ? (bool)$AllowStreamOverHTTP : FALSE),
    'MaxInspectionBytes' => isset($EngineMaxInspectionBytes) ? (int)$EngineMaxInspectionBytes : (isset($MaxStreamInspectionFileSize) ? (int)$MaxStreamInspectionFileSize : 1048576),
    'ConnectionTimeout' => isset($EngineConnectionTimeout) ? (int)$EngineConnectionTimeout : (isset($StreamConnectionTimeout) ? (int)$StreamConnectionTimeout : 10),
    'WatchTimeout' => isset($EngineWatchTimeout) ? (int)$EngineWatchTimeout : (isset($StreamWatchTimeout) ? (int)$StreamWatchTimeout : 300),
    'FetchTemp' => isset($EngineFetchTemp) ? (string)$EngineFetchTemp : (isset($StreamTemp) ? (string)$StreamTemp : sys_get_temp_dir()));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $NetworkPolicy is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection);
  return $NetworkPolicy; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether an address is one this application may ever connect to.
// / Accepts the address as a string. Returns TRUE only for a public unicast address.
// /
// / PHP's filter flags are the first layer & are NOT enough on their own.
// / FILTER_FLAG_NO_PRIV_RANGE refuses 10/8, 172.16/12, 192.168/16 & fc00::/7.
// / FILTER_FLAG_NO_RES_RANGE refuses 0/8, 127/8, 169.254/16, 240/4 & a few IPv6 ranges.
// / Everything below is what those flags let through & what an attacker reaches for next.
// /
// /   100.64.0.0/10     Shared address space, carrier NAT. Private in every sense that
// /                     matters & absent from the PHP flags.
// /   192.0.0.0/24      IETF protocol assignments.
// /   192.0.2.0/24      Documentation. Never routable.
// /   198.51.100.0/24   Documentation.
// /   203.0.113.0/24    Documentation.
// /   198.18.0.0/15     Benchmarking.
// /   224.0.0.0/4       Multicast. A connection to it reaches every host that listens.
// /   255.255.255.255   Broadcast.
// /   ::/128, ::1/128   Unspecified & loopback.
// /   fe80::/10         Link local. The PHP flags miss it.
// /   ::ffff:0:0/96     IPv4 MAPPED IN IPv6. ::ffff:127.0.0.1 is the loopback address
// /                     written in a form the IPv4 flags never see. This one is the
// /                     classic bypass & is refused by unwrapping it & testing the IPv4.
// /   64:ff9b::/96      NAT64. An IPv4 address reached through an IPv6 prefix, tested the
// /                     same way for the same reason.
// /   2001:db8::/32     Documentation.
// /
// / A range list is data & belongs in one place. Nothing else in the Engine or the
// / application decides what is private, & a new range is added here & nowhere else.
function isPubliclyRoutableIP($ipAddress) {
  // / Set variables.
  global $EnableMemoryProtection;
  $Check = FALSE;
  $cleanAddress = $mappedAddress = $packedAddress = $refusedRange = '';
  $refusedRanges = array();
  $cleanAddress = trim((string)$ipAddress, "[] \t");
  // / An IPv4 address carried inside IPv6 is unwrapped & judged as the IPv4 it is.
  // / A judgement on the wrapper alone lets loopback & every private range through.
  // / The unwrapping works on the PACKED bytes rather than on the text, because the same
  // / address is written ::ffff:127.0.0.1 in one place & ::ffff:7f00:1 in another & a
  // / pattern that matched only the dotted form let the hexadecimal one straight through.
  $packedAddress = @inet_pton($cleanAddress);
  if ($packedAddress !== FALSE && strlen($packedAddress) === 16) {
    if (addressIsInRange($cleanAddress, '::ffff:0:0/96') or addressIsInRange($cleanAddress, '64:ff9b::/96')) $mappedAddress = inet_ntop(substr($packedAddress, 12, 4)); }
  if ($mappedAddress !== '' && $mappedAddress !== FALSE) $cleanAddress = $mappedAddress;
  if (filter_var($cleanAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== FALSE) {
    $Check = TRUE;
    $refusedRanges = array(
      '100.64.0.0/10', '192.0.0.0/24', '192.0.2.0/24', '198.51.100.0/24', '203.0.113.0/24',
      '198.18.0.0/15', '224.0.0.0/4', '255.255.255.255/32',
      '::/128', '::1/128', 'fe80::/10', '2001:db8::/32');
    foreach ($refusedRanges as $refusedRange) {
      if (addressIsInRange($cleanAddress, $refusedRange)) {
        $Check = FALSE;
        break; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cleanAddress, $mappedAddress, $packedAddress, $refusedRanges, $refusedRange, $ipAddress);
  return $Check; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to test whether an address falls inside a range written in CIDR notation.
// / Accepts the address & the range. Returns TRUE when it does.
// / IPv4 & IPv6 are compared as packed bytes, so a range of either family is written the
// / same way & an address of the other family never matches it.
function addressIsInRange($ipAddress, $cidrRange) {
  // / Set variables.
  global $EnableMemoryProtection;
  $IsInRange = FALSE;
  $rangeParts = array();
  $rangeAddress = $packedAddress = $packedRange = '';
  $prefixLength = $byteIndex = $bitsLeft = $mask = 0;
  $rangeParts = explode('/', (string)$cidrRange, 2);
  $rangeAddress = $rangeParts[0];
  $prefixLength = isset($rangeParts[1]) ? (int)$rangeParts[1] : (strpos($rangeAddress, ':') !== FALSE ? 128 : 32);
  $packedAddress = @inet_pton((string)$ipAddress);
  $packedRange = @inet_pton($rangeAddress);
  // / Two addresses of different families have different lengths & never compare.
  if ($packedAddress !== FALSE && $packedRange !== FALSE && strlen($packedAddress) === strlen($packedRange)) {
    $IsInRange = TRUE;
    $bitsLeft = $prefixLength;
    for ($byteIndex = 0; $byteIndex < strlen($packedAddress) && $bitsLeft > 0; $byteIndex++) {
      $mask = $bitsLeft >= 8 ? 0xFF : (0xFF << (8 - $bitsLeft)) & 0xFF;
      if ((ord($packedAddress[$byteIndex]) & $mask) !== (ord($packedRange[$byteIndex]) & $mask)) {
        $IsInRange = FALSE;
        break; }
      $bitsLeft = $bitsLeft - 8; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $rangeParts, $rangeAddress, $packedAddress, $packedRange, $prefixLength, $byteIndex, $bitsLeft, $mask, $ipAddress, $cidrRange);
  return $IsInRange; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to perform DNS lookups without following redirects.
// / Also used to detect if a host resolves to a LAN segment or private IP range.
// / Note that this has an intentionally redundant check for whether or not the IP is private.
// / A private/reserved answer means this host is untrustworthy regardless of what else it returned.
// / $URLIP will return FALSE if the lookup failed or if a DNS rebind attack is suspected.
// / $StreamContainsLAN will return TRUE if the response contained a local or reserved IP address.
// / $LookupFailed will return TRUE if no DNS response was received or FALSE if DNS succeeded.
function dnsLookup($URLHost) {
  // / Set variables.
  global $EnableMemoryProtection;
  $records = $record = array();
  $urlIP = $URLIP = $LookupFailed = $StreamContainsLAN = $isPublic = FALSE;
  // / Perform the actual DNS lookup against the $URLHost.
  $records = @dns_get_record($URLHost, DNS_A + DNS_AAAA);
  // / Check that the records received from the DNS provider were formed properly.
  if (is_array($records) && !empty($records)) {
    foreach ($records as $record) {
      // / Parse the received DNS records.
      $urlIP = $record['ip'] ?? $record['ipv6'] ?? NULL;
      if ($urlIP === NULL) continue;
      $isPublic = isPubliclyRoutableIP($urlIP);
      if ($isPublic) $URLIP = $urlIP;
      else {
        // / A private/reserved answer means this host is untrustworthy regardless of what else it returned.
        // / Discard any safe IP already found. A host answering with both is a rebinding setup, not a partial success.
        $StreamContainsLAN = TRUE;
        $URLIP = FALSE;
        break; } } }
  // / Set a flag to tell if the lookup failed outright.
  else $LookupFailed = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $records, $record, $urlIP, $isPublic);
  return array($URLIP, $StreamContainsLAN, $LookupFailed); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to scan a stream URL received from a stream file such as .m3u8.
// / This function determines whether that URL is safe for FFMPEG to handle.
// / This function performs a DNS lookup on the provided URL but DOES NOT follow redirects.
// / Not following redirects is critical.
// / To perform adequate SSRF protection we must obtain a non-redirected lookup for each remote host.
// / The information obtained here binds downstream dependencies like CURL & FFMPEG to these locations.
function gatherRemoteHostInfo($StreamURL) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  // / The resolved policy values are LOCALS & are deliberately not declared global.
  // / Each is lowercase, so convention three forbids it transcending this function, & an
  // / earlier version declared them global anyway. That achieved nothing, because every
  // / caller assigns them from networkPolicy() before reading them, & it left the last
  // / stream's settings sitting in the global scope between requests.
  // / The network policy is resolved once & every setting below comes from it.
  $networkPolicy = networkPolicy();
  $engineAllowPlainHTTP = $networkPolicy['AllowPlainHTTP'];
  $LookupFailed = $InspectionFailed = TRUE;
  $URLIP = $URLHost = $URLPort = $URLScheme = $StreamContainsLAN = $StreamDNSContainsLAN = $StreamURLResolutionFailed = FALSE;
  $urlIsSanitized = $partsAreSanitized = $schemeIsSanitized = $hostIsSanitized = FALSE;
  $allowedSchemes = array('https');
  $urlParts = array();
  // / Sanitize the supplied URL before anything else looks at it.
  list ($StreamURL, $urlIsSanitized) = sanitize($StreamURL, TRUE);
  if ($Verbose) logEntry('Inspecting Stream URL: '.$StreamURL.'.');
  // / Check if plain http stream URLs are allowed by config.php.
  if ($engineAllowPlainHTTP) array_push($allowedSchemes, 'http');
  // / A URL that could not be sanitized is unresolvable before we even parse it.
  if (!$urlIsSanitized) $StreamURLResolutionFailed = TRUE;
  else {
    // / Parse the provided URL to gather DNS information.
    // / parse_url returns FALSE if the response was seriously malformed.
    $urlParts = parse_url($StreamURL);
    // / If the parse_url response is malformed, then consider the URL unresolvable.
    if (!$urlParts) $StreamURLResolutionFailed = TRUE;
    // / If the parse_url response makes sense, then keep going.
    else {
      list ($urlParts, $partsAreSanitized) = sanitize($urlParts, TRUE);
      // / If the parse_url response is incomplete, then consider the URL unresolvable.
      if (!$partsAreSanitized or empty($urlParts['scheme']) or empty($urlParts['host'])) $StreamURLResolutionFailed = TRUE;
      // / If the parse_url response makes sense, then keep going.
      else {
        // / If the scheme is not supported by config.php, then consider the URL unresolvable.
        list ($URLScheme, $schemeIsSanitized) = sanitize(strtolower($urlParts['scheme']), TRUE);
        if (!$schemeIsSanitized or !in_array($URLScheme, $allowedSchemes, TRUE)) $StreamURLResolutionFailed = TRUE;
        // / If the scheme is supported by config.php, then keep going.
        else {
          // / Detect the host.
          list ($URLHost, $hostIsSanitized) = sanitize(strtolower($urlParts['host']), TRUE);
          if (!$hostIsSanitized) $StreamURLResolutionFailed = TRUE;
          // / Detect the port where applicable.
          // / Be very careful making changes to this code.
          // / This uses the port supplied by the request when available.
          // / When no port was supplied it falls back to 443 for https & 80 for http.
          // / This code prevents dependencies from silently performing their own DNS lookups.
          // / If this code fails to produce a valid host or port, dependencies will ignore our binding.
          // / CURL & FFMPEG cannot be allowed to do lookups that could be spoofed into following redirects.
          else $URLPort = isset($urlParts['port']) ? (int)$urlParts['port'] : ($URLScheme === 'https' ? 443 : 80); } } } }
  // / If we have successfully obtained a host, port & scheme, then resolve the address.
  if (!$StreamURLResolutionFailed) {
    // / If the host is already a literal IP, validate it directly & skip DNS entirely.
    if (filter_var($URLHost, FILTER_VALIDATE_IP)) {
      if (isPubliclyRoutableIP($URLHost)) {
        $URLIP = $URLHost;
        $LookupFailed = FALSE; }
      else $StreamContainsLAN = TRUE; }
    // / Otherwise it is a hostname, so resolve it without following redirects.
    else list($URLIP, $StreamDNSContainsLAN, $LookupFailed) = dnsLookup($URLHost);
    // / A DNS result that was not publicly routable is treated as LAN & will be denied.
    // / This condenses the DNS finding into one flag because anything containing LAN is denied.
    if ($StreamDNSContainsLAN) $StreamContainsLAN = TRUE; }
  // / If any check failed or any required value is empty, then the whole inspection failed.
  if ($StreamURLResolutionFailed or $StreamContainsLAN or $LookupFailed or empty($URLHost) or empty($URLPort) or empty($URLScheme) or empty($URLIP)) $InspectionFailed = TRUE;
  // / If everything passed then consider the inspection to have passed.
  else $InspectionFailed = FALSE;
  // / Write the information obtained to the log file.
  if ($Verbose) logEntry('URL Inspection Result: '.($InspectionFailed ? 'FAILED' : 'PASSED').', Host: '.$URLHost.', Port: '.$URLPort.', Scheme: '.$URLScheme.', Contains LAN: '.($StreamContainsLAN ? 'TRUE' : 'FALSE').', URL Resolution Failed: '.($StreamURLResolutionFailed ? 'TRUE' : 'FALSE').', Lookup Failed: '.($LookupFailed ? 'TRUE' : 'FALSE').'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $networkPolicy, $engineAllowPlainHTTP, $allowedSchemes, $urlParts, $StreamDNSContainsLAN, $urlIsSanitized, $partsAreSanitized, $schemeIsSanitized, $hostIsSanitized);
  return array($InspectionFailed, $StreamURLResolutionFailed, $StreamContainsLAN, $LookupFailed, $URLHost, $URLPort, $URLScheme, $URLIP); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to turn a URI found inside a stream file into a complete, absolute URL.
// / Stream files routinely reference segments & nested playlists by relative path, which
// / inherit their missing parts from the URL the PARENT manifest was downloaded from.
// / $ParentURL is empty for the file the user uploaded, because nobody fetched it. A relative
// / URI at that layer therefore resolves against nothing & is refused rather than guessed at.
// / Returns an empty string when the URI cannot be honestly resolved. The caller must deny on empty.
function resolveRemoteURI($StreamURI, $ParentURL) {
  // / Set variables.
  global $EnableMemoryProtection;
  $AbsoluteURL = '';
  $parentParts = array();
  $parentScheme = $parentHost = $parentPort = $parentDir = '';
  $uriIsAbsolute = $parentIsUsable = FALSE;
  $StreamURI = trim($StreamURI);
  // / Case 1. The URI already carries its own scheme, so nothing is inherited & we are done.
  // / Note this deliberately matches ANY scheme, including file: and gopher:, so that
  // / gatherRemoteHostInfo() sees & rejects them rather than us silently mangling them.
  if ($StreamURI !== '' && preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*:#', $StreamURI)) {
    $uriIsAbsolute = TRUE;
    $AbsoluteURL = $StreamURI; }
  // / Everything below here is relative & needs a parent to inherit from.
  // / The user's uploaded file has no parent, so a relative URI at layer 0 is unresolvable.
  if (!$uriIsAbsolute && $StreamURI !== '' && $ParentURL !== '') {
    $parentParts = parse_url($ParentURL);
    if ($parentParts && !empty($parentParts['scheme']) && !empty($parentParts['host'])) $parentIsUsable = TRUE; }
  // / Inherit whatever the relative form is missing from the parent it was found in.
  if ($parentIsUsable) {
    $parentScheme = strtolower($parentParts['scheme']);
    $parentHost = strtolower($parentParts['host']);
    $parentPort = isset($parentParts['port']) ? ':'.(int)$parentParts['port'] : '';
    // / Case 2. Protocol-relative (//cdn.example.com/seg.ts). Inherits the scheme only.
    if (substr($StreamURI, 0, 2) === '//') $AbsoluteURL = $parentScheme.':'.$StreamURI;
    // / Case 3. Root-relative (/hls/seg.ts). Inherits scheme, host & port. The path is replaced outright.
    elseif ($StreamURI[0] === '/') $AbsoluteURL = $parentScheme.'://'.$parentHost.$parentPort.$StreamURI;
    // / Case 4. Plain relative (seg003.ts). Appended to the parent's DIRECTORY, never to its filename.
    else {
      $parentDir = rtrim(dirname($parentParts['path'] ?? '/'), '/');
      $AbsoluteURL = $parentScheme.'://'.$parentHost.$parentPort.$parentDir.'/'.$StreamURI; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $parentParts, $parentScheme, $parentHost, $parentPort, $parentDir, $uriIsAbsolute, $parentIsUsable);
  return $AbsoluteURL; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to gather & validate IPv4 & IPv6 addresses from ANY block of content.
// / Does not perform DNS or any remote validation.
// / This function only validates the syntactical form of IP addresses, and ensures they are not in a reserved range.
// /
// / This is SSRF machinery & it lives in the core on purpose.
// / It knows nothing about streams, playlists or any other format. It is handed bytes &
// / reports which addresses in them are safe to contact. A pipeline that needs to decide
// / whether a reference is safe to follow calls this rather than writing its own.
// / A PIPELINE MUST NEVER OWN SSRF PROTECTION. A community author writing a pipeline that
// / fetches something should get this for free & must not be able to opt out of it by
// / accident. That is the same reason verifyFile() & virusScan() stayed in the core.
function inspectContentForIPs($streamFileContents) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ipMatch = '';
  $IPCount = 0;
  $ipMatchesTemp = $ip4Temp = $ip6Temp = $IPMatches = array();
  $StreamContainsLAN = $StreamContainsIP = FALSE;
  // / Regex pattern to extract matching IPv4 formats.
  $ip4Pattern = '/\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b/';
  // / Regex pattern to extract matching IPv6 formats.
  // / Loose candidate matcher. Finds hex-and-colon runs that MIGHT be IPv6.
  // / Deliberately over-matches; filter_var() (called below) is the authority on validity.
  $ip6Pattern = '/(?<![0-9A-Fa-f:])(?:[0-9A-Fa-f]{0,4}:){2,7}[0-9A-Fa-f]{0,4}(?::[0-9]{1,3}(?:\.[0-9]{1,3}){3})?(?![0-9A-Fa-f:])/';
  // / Check if the $streamFile contains any IP addresses, if those addresses are valid, & if they fall into a private subnet.
  // / $ipMatchesTemp = flat array of every potential IP string matched by either pattern.
  // / $IPMatches = flat array of every valid, publicly routable IP confirmed by filter_var.
  preg_match_all($ip4Pattern, $streamFileContents, $ip4Temp);
  preg_match_all($ip6Pattern, $streamFileContents, $ip6Temp);
  $ipMatchesTemp = array_merge($ip4Temp[0], $ip6Temp[0]);
  if (!empty($ipMatchesTemp)) {
    foreach ($ipMatchesTemp as $ipMatch) {
      // / Strip brackets from URL-form IPv6 (https://[::1]/) before validating.
      $ipMatch = trim($ipMatch, '[]');
      // / Not a real IP at all. Probably a regex over-match. Discard silently, do NOT flag.
      if (!filter_var($ipMatch, FILTER_VALIDATE_IP)) continue;
      // / Set a flag if the entry that was found is a genuine raw IP address.
      $StreamContainsIP = TRUE;
      // / Check whether each extracted IP is on a local / private subnet, or something we should not be probing.
      if (isPubliclyRoutableIP($ipMatch)) array_push($IPMatches, $ipMatch);
      else {
        // / Set a flag if the IP address that was found appears to be on a local or private subnet.
        $StreamContainsLAN = TRUE;
        // / Stop as soon as we find a dangerous IP address. There is no need to continue validating a malicious file.
        break; } } }
  // / Count the number of publicly routable IPs found before we stopped.
  $IPCount = count($IPMatches);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $ipMatch, $ipMatchesTemp, $ip4Pattern, $ip6Pattern, $streamFileContents, $ip4Temp, $ip6Temp);
  return array($IPMatches, $IPCount, $StreamContainsLAN, $StreamContainsIP); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to gather & validate domain names from ANY block of content.
// / Does not perform DNS or any remote validation.
// / This function only validates the syntactical form of domain names.
// / Preserves http:// and https:// as the only allowed protocols.
// /
// / THIS IS SSRF MACHINERY & IT LIVES IN THE CORE ON PURPOSE. See inspectContentForIPs().
function inspectContentForDomains($streamFileContents) {
  // / Set variables.
  global $EnableMemoryProtection;
  $DomainCount = 0;
  $DomainNames = $domainMatches = array();
  $StreamContainsDomain = FALSE;
  // / Matches only absolute http/https URLs & captures the hostname portion in group 1.
  // / Skips any userinfo (user:pass@) so the REAL host is captured, not the decoy before the @.
  $domainPattern = '/\bhttps?:\/\/(?:[^\/?#@\s]*@)?([a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*\.[a-zA-Z]{2,63})(?=[:\/?#]|\s|$)/i';
  // / Check if the $streamFile contains any domain names, and whether those domain names are valid.
  if (preg_match_all($domainPattern, $streamFileContents, $domainMatches)) {
    // / Set a flag if the entry that was found appears to be a domain name.
    $StreamContainsDomain = TRUE;
    // / $domainMatches[1] holds the bare hostnames & is the only row safe to hand to DNS.
    $DomainNames = $domainMatches[1];
    // / $domainMatches[0] holds the FULL match, including scheme & any user:pass@ decoy.
    // / $domainMatches[0] is NOT safe to use for DNS. It is only referenced here for counting.
    $DomainCount = count($domainMatches[0]); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $domainPattern, $streamFileContents, $domainMatches);
  return array($DomainNames, $DomainCount, $StreamContainsDomain); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to download a single remote file to local disk for inspection.
// /
// / This is the only place in the application that fetches a remote file for a pipeline.
// / It pins the resolved IP, refuses redirects, refuses DNS rebinding, & reads a bounded
// / number of bytes. Every one of those is a thing a pipeline author writing their own curl
// / call would forget. It stays in the core so that they cannot.
// / A config note worth knowing before a second pipeline uses this.
// / $engineAllowPlainHTTP, $engineConnectionTimeout, $engineMaxInspectionBytes &
// / $engineFetchTemp are all named for streaming because streaming was the only caller when they
// / were written. They govern EVERY caller of this function, not only the Stream pipeline.
// / They were not renamed because an administrator's config.php names them & a rename would
// / silently reset those settings on every installation. If a second pipeline starts
// / fetching remote files, that shared governance should be a deliberate decision rather
// / than something discovered afterwards.
// / Files are saved with a numeric name & no extension so nothing downstream is fooled by a filename.
// / The original URI is preserved in the stream record, not on disk.
// / This function does NOT follow redirects.
// / This function does NOT let CURL perform its own DNS lookup.
// / Only the first $engineMaxInspectionBytes bytes are fetched.
// / We are classifying files here, not streaming them.
// / $engineConnectionTimeout is documented in seconds & is used directly.
// / $engineWatchTimeout is documented in minutes & is converted once here.
function downloadRemoteFileForInspection($StreamURL, $URLHost, $URLPort, $URLIP, $URLScheme, $FileNumber) {
  // / Set variables.
  global $Verbose, $DirSep, $EnableMemoryProtection;
  // / The network policy is resolved once & every setting below comes from it.
  $networkPolicy = networkPolicy();
  $engineAllowPlainHTTP = $networkPolicy['AllowPlainHTTP'];
  $engineMaxInspectionBytes = $networkPolicy['MaxInspectionBytes'];
  $engineConnectionTimeout = $networkPolicy['ConnectionTimeout'];
  $engineWatchTimeout = $networkPolicy['WatchTimeout'];
  $engineFetchTemp = $networkPolicy['FetchTemp'];
  $DownloadFailed = $StreamFileTruncated = TRUE;
  $pinIsComplete = FALSE;
  $curlOutput = array();
  $curlCommand = '';
  $curlExitCode = 1;
  $downloadedBytes = 0;
  // / Sequential name for every file saved to StreamTemp.
  // / This number never resets during a walk.
  // / A layer 2 file must never overwrite a layer 1 file.
  $LocalStreamPath = $engineFetchTemp.$DirSep.$FileNumber;
  $protoString = 'https';
  // / Only widen to plain http when config allows it AND this specific URL actually uses it.
  if ($engineAllowPlainHTTP && $URLScheme === 'http') $protoString = 'http,https';
  // / Refuse outright if any component needed for the DNS pin is missing.
  // / An empty component produces a malformed --resolve entry which CURL silently ignores.
  // / CURL would then perform its own lookup & every rebinding protection would be lost.
  if (!empty($URLHost) && !empty($URLPort) && !empty($URLIP) && !empty($StreamURL)) $pinIsComplete = TRUE;
  else if ($Verbose) logEntry('Stream download refused: incomplete validation data for '.$StreamURL.'.');
  if ($pinIsComplete) {
    // / Build the command with every user influenced value escaped.
    // / There is no -L flag, so redirects are never followed.
    // / The -r flag requests only the first chunk of the file.
    // / The --max-filesize flag enforces the same ceiling when a host ignores -r.
    $curlCommand = 'curl'
      .' --resolve '.escapeshellarg($URLHost.':'.$URLPort.':'.$URLIP)
      .' --proto '.escapeshellarg('='.$protoString)
      .' --proto-redir '.escapeshellarg('='.$protoString)
      .' -r '.escapeshellarg('0-'.((int)$engineMaxInspectionBytes - 1))
      .' --max-filesize '.(int)$engineMaxInspectionBytes
      .' --connect-timeout '.(int)$engineConnectionTimeout
      .' -m '.((int)$engineWatchTimeout * 60)
      .' -sS -o '.escapeshellarg($LocalStreamPath)
      .' -- '.escapeshellarg($StreamURL).' 2>&1';
    // / The fetch runs INSIDE the sandbox, with a network & without a resolver.
    // / curl needs no resolver, because --resolve above hands it the one address the
    // / application already inspected. A namespace with no resolver is the enforcement of
    // / that. If the pin were ever malformed, curl would have nothing to fall back on &
    // / would fail rather than quietly resolving for itself, which is the failure wanted.
    // / The generic profile is used, because curl needs nothing beyond the base sandbox.
    // / The output path is also given as the input path. There is no input file & the
    // / sandbox requires a directory to bind; the one it binds is the one being written.
    $curlCommand = sandboxCommand($curlCommand, $LocalStreamPath, $LocalStreamPath, TRUE, 'generic');
    exec($curlCommand, $curlOutput, $curlExitCode);
    // / A successful download requires exit code 0 AND a file that exists with content.
    // / Either one alone is not proof of success.
    if ($curlExitCode === 0 && file_exists($LocalStreamPath)) {
      $downloadedBytes = filesize($LocalStreamPath);
      if ($downloadedBytes > 0) $DownloadFailed = FALSE;
      // / A file that filled the entire budget was almost certainly cut short.
      // / We are only holding part of it, so we cannot claim to have inspected the whole thing.
      if ($downloadedBytes < (int)$engineMaxInspectionBytes) $StreamFileTruncated = FALSE; }
    else if ($Verbose) logEntry('Stream download failed for '.$StreamURL.'. CURL exit code: '.$curlExitCode.'.');
    // / Log the outcome of this single download.
    if ($Verbose) logEntry('Stream Download Result: '.($DownloadFailed ? 'FAILED' : 'SUCCESS').', File: '.$FileNumber.', Bytes: '.$downloadedBytes.', Truncated: '.($StreamFileTruncated ? 'TRUE' : 'FALSE').'.'); }
  // / Never report a path we did not successfully write to.
  // / The caller reads this file immediately after this function returns.
  if ($DownloadFailed) $LocalStreamPath = '';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $networkPolicy, $engineAllowPlainHTTP, $engineMaxInspectionBytes, $engineConnectionTimeout, $engineWatchTimeout, $engineFetchTemp, $curlCommand, $curlOutput, $protoString, $curlExitCode, $downloadedBytes, $pinIsComplete);
  return array($DownloadFailed, $LocalStreamPath, $StreamFileTruncated); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to supervise a backgrounded FFMPEG stream conversion after the user has been served.
// / Polls the process & kills it once $engineWatchTimeout minutes have elapsed.
// / This is the only thing preventing an abandoned stream from running until PHP or the OS intervenes.
function waitForStream($StreamPID, $newPathname) {
  // / Set variables.
  global $Verbose, $engineWatchTimeout, $EnableMemoryProtection;
  // / The network policy is resolved once & every setting below comes from it.
  $networkPolicy = networkPolicy();
  $engineWatchTimeout = $networkPolicy['WatchTimeout'];
  $StreamCompleted = $StreamKilled = $pidIsUsable = FALSE;
  $psOutput = array();
  $ElapsedSeconds = 0;
  $pollInterval = 2;
  // / Config states this value in MINUTES. Convert once, here, so the loop reads in seconds.
  $timeoutSeconds = (int)$engineWatchTimeout * 60;
  // / Nothing to supervise without a real process id.
  if ((int)$StreamPID > 0) $pidIsUsable = TRUE;
  if ($pidIsUsable) {
    if ($Verbose) logEntry('Supervising stream PID '.$StreamPID.' for up to '.$timeoutSeconds.' seconds.');
    while ($ElapsedSeconds < $timeoutSeconds) {
      $psOutput = array();
      // / A ps listing with only its header row means the process is gone.
      exec('ps -p '.(int)$StreamPID, $psOutput);
      if (count($psOutput) < 2) {
        $StreamCompleted = TRUE;
        break; }
      sleep($pollInterval);
      $ElapsedSeconds += $pollInterval; }
    // / Still running after the full watch window. Terminate it.
    if (!$StreamCompleted) {
      exec('kill -9 '.(int)$StreamPID);
      $StreamKilled = TRUE;
      if ($Verbose) logEntry('Stream PID '.$StreamPID.' exceeded the watch timeout & was terminated.'); }
    else if ($Verbose) logEntry('Stream PID '.$StreamPID.' finished after '.$ElapsedSeconds.' seconds. Output exists: '.(file_exists($newPathname) ? 'TRUE' : 'FALSE').'.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $newPathname, $networkPolicy, $engineWatchTimeout, $psOutput, $pollInterval, $timeoutSeconds, $pidIsUsable);
  return array($StreamCompleted, $StreamKilled, $ElapsedSeconds); }
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to run one command as another account, permanently dropping to it first.
// / Accepts the command & the account name. Returns the output & the exit code, in order.
// / A caller that is not root runs the command as itself & nothing is dropped.
// /
// / This replaces  su -s /bin/sh <account> -c <command>  & the difference matters.
// / su requires this process to still BE root at the moment it spawns, so the privilege
// / this call exists to give up is held for the whole life of the call. It also puts a
// / shell in the chain, with an environment & a PATH somebody may be able to influence.
// / A fork drops privilege inside the child & never regains it. posix_setuid called as
// / root sets the real, the effective & the saved identity together, so there is nothing
// / left to climb back to. The parent keeps root & the child cannot reach it.
// /
// / The group is dropped BEFORE the account & that order is not interchangeable.
// / Changing the account first removes the ability to change the group, so a process that
// / did it the other way round would keep running in root's group & nothing would say so.
// /
// / A host without the pcntl or posix extension cannot do this safely, so it refuses
// / rather than falling back to running the command as root. A command that was meant to
// / run unprivileged is worse than a command that did not run.
function runAsAccount($command, $accountName) {
  // / Set variables.
  global $RunningAsRoot, $EnableMemoryProtection;
  $CommandOutput = array();
  $CommandExitCode = 1;
  $accountRecord = array();
  $outputPath = $outputContents = '';
  $childPid = $childStatus = 0;
  if (!$RunningAsRoot) exec((string)$command, $CommandOutput, $CommandExitCode);
  else if (!function_exists('pcntl_fork') or !function_exists('posix_setuid') or !function_exists('posix_getpwnam')) {
    warningEntry('A command had to run as '.$accountName.' & this PHP has no pcntl or posix extension. It was not run at all, because running it as root would be worse.');
    $CommandExitCode = 1; }
  else {
    $accountRecord = posix_getpwnam((string)$accountName);
    if (!is_array($accountRecord)) warningEntry('The account '.$accountName.' does not exist on this host, so a command that had to run as it was not run.');
    else {
      // / The child writes to a file rather than a pipe, because the parent must not read
      // / from a descriptor the child still owns while it is changing identity.
      $outputPath = tempnam(sys_get_temp_dir(), 'hrc2run');
      $childPid = pcntl_fork();
      if ($childPid === -1) warningEntry('A command that had to run as '.$accountName.' could not be forked.');
      else if ($childPid === 0) {
        // / Inside the child. Group first, account second, & neither can be undone.
        posix_setgid($accountRecord['gid']);
        posix_setuid($accountRecord['uid']);
        exec((string)$command, $CommandOutput, $CommandExitCode);
        @file_put_contents($outputPath, (string)$CommandExitCode.PHP_EOL.implode(PHP_EOL, $CommandOutput));
        exit(0); }
      else {
        pcntl_waitpid($childPid, $childStatus);
        $outputContents = (string)@file_get_contents($outputPath);
        @unlink($outputPath);
        $CommandOutput = explode(PHP_EOL, $outputContents);
        $CommandExitCode = (int)array_shift($CommandOutput); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $accountRecord, $outputPath, $outputContents, $childPid, $childStatus, $command, $accountName);
  return array($CommandOutput, $CommandExitCode); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Resource limits.
// / A limit is a share of the host granted to one operation & is expressed as a percentage
// / of a processor & a quantity of memory.
// / limitCommand depends on both of these, so they moved with it. A function that moves
// / without what it depends on has not moved, it has only appeared to.
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide the limits one conversion runs under.
// / Accepts the sandbox profile about to run.
// / Returns a validity boolean, the processor percentage & the memory megabytes, in that order.
// / The listener supplies the table when one is running, because only it knows how loaded
// / the host is & how many conversions are already in flight. Its figures are already
// / scaled & floored. With no listener the configured maxima are used unchanged, which is
// / the same behaviour a standalone installation has always had.
function resolveConversionLimit($sandboxProfile) {
  // / Set variables.
  // / The limit tables are named by the application & are read rather than requested.
  // / They are globals rather than arguments because the effective table is rewritten by
  // / the resource manager while this process runs, & a value passed in at boot would be
  // / the value from boot rather than the value now.
  // / An application that supplies none of them gets an empty table, & the caller treats
  // / an empty table as no limit rather than as a limit of nothing.
  global $EffectiveConversionLimits, $MaximumPerConversionResources, $DefaultPerConversionResources, $Verbose, $EnableMemoryProtection;
  $LimitIsValid = FALSE;
  $CpuPercentage = 0;
  $MemoryMegabytes = 0;
  $conversionType = $limitString = $limitSource = '';
  // / A ceiling this core knows a type cannot run below, consulted only when the
  // / administrator has named no ceiling for that type at all. Anything they DO set wins.
  // /
  // / THIS EXISTS BECAUSE A CORE CAN BE UPDATED WITHOUT config.php BEING UPDATED.
  // / config.php is accepted at or above a minimum version, so an installation that takes a
  // / newer core keeps whatever configuration file it already had. A type this core has
  // / learned about since that file was written is therefore a type the file does not name,
  // / & the general default is what it would fall to.
  // / For Scan that default is fatal rather than merely tight. A ClamAV signature database
  // / is well over a gigabyte once it is loaded, so handing a scan the 512M general default
  // / does not slow it down, it has the kernel kill it, & every virus scan on that server
  // / fails from the moment the core is updated. A local fallback the configuration cannot
  // / lower by omission is what stops a version skew from turning a working scanner off.
  $builtInLimits = array('Scan' => '50,2048');
  $conversionType = conversionTypeForProfile($sandboxProfile);
  // / A table supplied by the listener wins, because it reflects the host right now.
  if (is_array($EffectiveConversionLimits) && isset($EffectiveConversionLimits[$conversionType])) {
    $limitString = (string)$EffectiveConversionLimits[$conversionType];
    $limitSource = 'listener'; }
  else if (is_array($MaximumPerConversionResources) && isset($MaximumPerConversionResources[$conversionType])) {
    $limitString = (string)$MaximumPerConversionResources[$conversionType];
    $limitSource = 'config.php'; }
  else if (isset($builtInLimits[$conversionType])) {
    $limitString = (string)$builtInLimits[$conversionType];
    $limitSource = 'core built-in';
    warningEntry('config.php names no per conversion ceiling for '.$conversionType.'. Using this core\'s built-in '.$limitString.' rather than the general default, which is too small for that type. Add a '.$conversionType.' entry to --Maximum Per Conversion Resources-- to set this yourself.'); }
  else {
    $limitString = (string)$DefaultPerConversionResources;
    $limitSource = 'config.php default'; }
  list ($LimitIsValid, $CpuPercentage, $MemoryMegabytes) = parseConversionLimit($limitString);
  // / An unreadable entry falls back to the default rather than running unlimited.
  if (!$LimitIsValid && $limitSource !== 'config.php default') {
    warningEntry('The per conversion limit for '.$conversionType.' could not be read from '.$limitSource.'. Using the configured default.');
    list ($LimitIsValid, $CpuPercentage, $MemoryMegabytes) = parseConversionLimit((string)$DefaultPerConversionResources);
    $limitSource = 'config.php default'; }
  if ($Verbose && $LimitIsValid) logEntry('Conversion Limit: '.$conversionType.', CPU '.$CpuPercentage.'%, Memory '.$MemoryMegabytes.'M, Source: '.$limitSource.'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $conversionType, $limitString, $limitSource, $builtInLimits, $sandboxProfile);
  return array($LimitIsValid, $CpuPercentage, $MemoryMegabytes); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify how, or whether, a resource scope can be created.
// / Accepts no arguments.
// / Returns the mode, the systemd-run binary & the environment prefix, in that order.
// / The mode is 'user', 'system' or 'none'.
// / The answer is cached for the request, because the probe launches a process.
// /
// / A user scope is preferred & is tried first.
// / A user manager controls only its own cgroup subtree, which systemd delegates to it. It
// / cannot start a unit as another account, so nothing here can become a way to gain
// / privilege. It needs lingering enabled for the web server user, which -fp does as root.
// /
// / A system scope is accepted but is not recommended.
// / Creating a transient unit on the system bus requires manage-units, & an account holding
// / manage-units can start a transient service with User=root. Granting that to the account
// / which parses uploaded documents hands an attacker a direct route to root. It is
// / supported only because an operator may already have configured it deliberately.
function verifySystemdRun() {
  // / Set variables.
  global $ApacheUser, $Verbose, $EnableMemoryProtection;
  // / The probe result survives the call so it runs once per request rather than per file.
  static $probedMode = NULL;
  static $probedBinary = '';
  static $probedEnvironment = '';
  $ScopeMode = 'none';
  $ScopeBinary = $ScopeEnvironment = '';
  $locatedBinary = $probeCommand = $runtimeDirectory = '';
  $probeOutput = $uidOutput = array();
  $probeExitCode = 1;
  $accountUid = 0;
  if ($probedMode !== NULL) {
    $ScopeMode = $probedMode;
    $ScopeBinary = $probedBinary;
    $ScopeEnvironment = $probedEnvironment; }
  else {
    $locatedBinary = locateDependency('systemd-run');
    if ($locatedBinary === '') warningEntry('systemd-run is not installed. Per conversion limits fall back to scheduling priority only.');
    else {
      // / Build the environment a user manager needs. Without these systemd-run cannot find
      // / the bus for this account & falls straight through to the system bus.
      exec('id -u '.escapeshellarg($ApacheUser).' 2>/dev/null', $uidOutput, $probeExitCode);
      $accountUid = (int)trim(implode('', $uidOutput));
      if ($accountUid > 0) {
        $runtimeDirectory = '/run/user/'.$accountUid;
        $ScopeEnvironment = 'XDG_RUNTIME_DIR='.escapeshellarg($runtimeDirectory)
          .' DBUS_SESSION_BUS_ADDRESS='.escapeshellarg('unix:path='.$runtimeDirectory.'/bus').' '; }
      // / Try the user manager first. Create the smallest possible scope & throw it away.
      $probeCommand = $ScopeEnvironment.'timeout 10 '.escapeshellarg($locatedBinary)
        .' --user --scope --quiet --collect -p MemoryMax=64M -- /bin/true 2>&1';
      exec($probeCommand, $probeOutput, $probeExitCode);
      if ($probeExitCode === 0) {
        $ScopeMode = 'user';
        $ScopeBinary = $locatedBinary;
        if ($Verbose) logEntry('Verified systemd-run in user mode. Per conversion limits are available without any privileged permission.'); }
      else {
        // / Fall back to the system bus only if the operator has already permitted it.
        $probeOutput = array();
        $probeCommand = 'timeout 10 '.escapeshellarg($locatedBinary).' --scope --quiet --collect -p CPUQuota=100% -- /bin/true 2>&1';
        exec($probeCommand, $probeOutput, $probeExitCode);
        if ($probeExitCode === 0) {
          $ScopeMode = 'system';
          $ScopeBinary = $locatedBinary;
          $ScopeEnvironment = '';
          warningEntry('Per conversion limits are using a SYSTEM scope. This account holds systemd manage-units, which is enough to start a unit as root. Enable lingering for '.$ApacheUser.' with the -fp argument & remove that permission.'); }
        else warningEntry('A resource scope could not be created. Run the -fp argument as root to enable lingering for '.$ApacheUser.'. Per conversion limits fall back to scheduling priority only.'); } }
    $probedMode = $ScopeMode;
    $probedBinary = $ScopeBinary;
    $probedEnvironment = $ScopeEnvironment; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $probedBinary, $probedEnvironment, $probedMode, $locatedBinary, $probeCommand, $runtimeDirectory, $probeOutput, $uidOutput, $probeExitCode, $accountUid);
  return array($ScopeMode, $ScopeBinary, $ScopeEnvironment); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The sandbox.
// / Every command an application runs against a file somebody else supplied goes through
// / here. The namespace has no network, no devices beyond a minimal set, a tmpfs for
// / everything writable & read only access to the two directories the command was given.
// /
// / A profile is DATA the application supplies & is never knowledge this file holds.
// / $EngineSandboxProfiles is filled by the application before a conversion runs.
// / Nothing here knows what LibreOffice or MeshLab is & nothing here needs to.
// / An application with different tools declares different profiles & this file does not
// / change. An application that declares nothing gets the base sandbox, which is the
// / tightest outcome rather than the loosest.
// /
// / A command is never assembled with a shell in mind. bwrap execs directly, so an
// / environment variable goes through --setenv & a NAME=value prefix would be read as the
// / name of a program to run.
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report which operation type a sandbox profile belongs to.
// / Accepts the profile name. Returns the type the resource limit is looked up under.
// / A profile this application does not declare is Generic, which is the most conservative
// / limit rather than the most generous, because an unknown tool has not been measured.
function conversionTypeForProfile($sandboxProfile) {
  // / Set variables.
  // / The profile array is supplied by the application rather than fetched from it.
  // / An Engine that called back into the application would be an Engine that works for
  // / exactly one application. It reads a global the application filled instead.
  global $EngineSandboxProfiles, $EnableMemoryProtection;
  $ConversionType = 'Generic';
  $cleanProfile = strtolower(trim((string)$sandboxProfile));
  $sandboxProfiles = (isset($EngineSandboxProfiles) && is_array($EngineSandboxProfiles)) ? $EngineSandboxProfiles : array();
  if (isset($sandboxProfiles[$cleanProfile]['OperationType'])) $ConversionType = (string)$sandboxProfiles[$cleanProfile]['OperationType'];
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cleanProfile, $sandboxProfiles, $sandboxProfile);
  return $ConversionType; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to assemble the bwrap flags one sandbox profile asks for.
// / Accepts the profile name. Returns the flags as a string, which may be empty.
// / Nothing here knows what any profile is for. It reads the record the application
// / declared & turns each field into the flag that expresses it.
// / A profile this application does not declare produces no extra flags at all, which
// / leaves the base sandbox in force rather than loosening anything.
function sandboxProfileFlags($sandboxProfile) {
  // / Set variables.
  // / The profile array is supplied by the application, for the reason given above.
  // / An application that supplies nothing gets the base sandbox & no extra flags, which
  // / is the tightest outcome rather than the loosest.
  global $EngineSandboxProfiles, $EnableMemoryProtection;
  $ProfileFlags = '';
  $cleanProfile = strtolower(trim((string)$sandboxProfile));
  $sandboxProfiles = (isset($EngineSandboxProfiles) && is_array($EngineSandboxProfiles)) ? $EngineSandboxProfiles : array();
  $profileRecord = array();
  $profilePath = $profileName = $profileValue = '';
  if (isset($sandboxProfiles[$cleanProfile])) {
    $profileRecord = $sandboxProfiles[$cleanProfile];
    // / A read only bind uses the try form, so a tool installed without its data does not
    // / take the whole sandbox down with it.
    if (isset($profileRecord['ReadOnlyPaths'])) {
      foreach ((array)$profileRecord['ReadOnlyPaths'] as $profilePath) $ProfileFlags = $ProfileFlags.' --ro-bind-try '.escapeshellarg($profilePath).' '.escapeshellarg($profilePath); }
    // / Mapping to root happens before any tmpfs is created, so a directory this profile
    // / asks for is owned by the namespace root rather than by the calling account.
    if (isset($profileRecord['MapToRoot']) && $profileRecord['MapToRoot']) $ProfileFlags = $ProfileFlags.' --uid 0 --gid 0';
    if (isset($profileRecord['Tmpfs'])) {
      foreach ((array)$profileRecord['Tmpfs'] as $profilePath) $ProfileFlags = $ProfileFlags.' --tmpfs '.escapeshellarg($profilePath); }
    // / An environment variable is set with --setenv & is never prefixed onto the command.
    // / bwrap execs without a shell, so a NAME=value prefix is read as a program to run.
    if (isset($profileRecord['Environment'])) {
      foreach ((array)$profileRecord['Environment'] as $profileName => $profileValue) $ProfileFlags = $ProfileFlags.' --setenv '.escapeshellarg($profileName).' '.escapeshellarg((string)$profileValue); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cleanProfile, $sandboxProfiles, $profileRecord, $profilePath, $profileName, $profileValue, $sandboxProfile);
  return $ProfileFlags; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to wrap a command in a transient systemd scope carrying its limits.
// / Accepts the command to wrap & the sandbox profile about to run, in that order.
// / Returns the wrapped command, or the command unchanged when limiting is unavailable.
// / The scope wraps the SANDBOX, not the tool inside it, so the ceiling covers bubblewrap
// / & every process it goes on to create.
// / CPUQuota is a percentage of ONE processor. 200% is two whole cores.
// / MemoryMax is a hard ceiling. A conversion that reaches it is killed by the kernel &
// / will report a conversion failure rather than a memory error, which is why the
// / configured ceilings should be generous.
// / With no scope mechanism the command is merely deprioritized, which needs no permission
// / at all & still keeps a conversion from starving the web server under contention.
// / This never refuses. A resource ceiling is a courtesy to the host & not a security
// / control, so an unavailable limiter runs the conversion anyway.
function limitCommand($command, $sandboxProfile) {
  // / Set variables.
  global $EnablePerConversionLimits, $Verbose, $EnableMemoryProtection;
  $LimitedCommand = (string)$command;
  $scopeMode = $scopeBinary = $scopeEnvironment = $scopePrefix = '';
  $limitIsValid = FALSE;
  $cpuPercentage = $memoryMegabytes = $nicePriority = 0;
  if (!$EnablePerConversionLimits) $LimitedCommand = (string)$command;
  else {
    list ($limitIsValid, $cpuPercentage, $memoryMegabytes) = resolveConversionLimit($sandboxProfile);
    if (!$limitIsValid) warningEntry('No usable per conversion limit could be resolved. This conversion runs unlimited.');
    else {
      list ($scopeMode, $scopeBinary, $scopeEnvironment) = verifySystemdRun();
      // / --quiet keeps the unit name off stderr, which several callers capture as output.
      // / --collect releases the unit when it exits, so a failed conversion leaves nothing.
      // / Swap is pinned to zero so a memory ceiling cannot be evaded by swapping.
      if ($scopeMode === 'user' or $scopeMode === 'system') {
        $scopePrefix = $scopeEnvironment.escapeshellarg($scopeBinary)
          .($scopeMode === 'user' ? ' --user' : '')
          .' --scope --quiet --collect'
          .' -p CPUQuota='.(int)$cpuPercentage.'%'
          .' -p MemoryMax='.(int)$memoryMegabytes.'M'
          .' -p MemorySwapMax=0'
          .' -- ';
        $LimitedCommand = $scopePrefix.$command; }
      else {
        // / No cgroup is available, so the ceiling cannot be enforced. The conversion is
        // / deprioritized instead, which is weaker but needs nothing from the operator.
        // / A smaller processor share becomes a larger niceness.
        $nicePriority = (int)round(19 - (($cpuPercentage / 100) * 19));
        if ($nicePriority < 0) $nicePriority = 0;
        if ($nicePriority > 19) $nicePriority = 19;
        $LimitedCommand = 'nice -n '.$nicePriority.' '.$command; }
      if ($Verbose) logEntry('Conversion Scope: '.$scopeMode.', CPU '.$cpuPercentage.'%, Memory '.$memoryMegabytes.'M'.($scopeMode === 'none' ? ', enforced as niceness '.$nicePriority.' only' : '').'.'); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $scopeMode, $scopeBinary, $scopeEnvironment, $scopePrefix, $limitIsValid, $cpuPercentage, $memoryMegabytes, $nicePriority, $command, $sandboxProfile);
  return $LimitedCommand; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to confirm this server can actually isolate a dependency invocation.
// / Returns the absolute path of a WORKING bubblewrap binary, or FALSE.
// / A path is returned ONLY when a real sandbox was launched successfully, so a caller
// / holding a path may build a command with it without checking anything else.
// / This is a CAPABILITY check rather than a version check & is the only one in the
// / application. Bubblewrap may be installed & still be non functional.
// / Unprivileged user namespaces can be disabled at the kernel level, restricted by an
// / AppArmor profile, or blocked by a container runtime. Testing that the binary exists
// / proves nothing at all, so a real minimal sandbox is launched here instead.
// / A dependency that cannot be isolated must be refused rather than run unprotected, so a
// / FALSE from this function is the strongest signal the application produces.
function verifyBwrap() {
  // / Set variables.
  global $Verbose, $RunningAsRoot, $CurrentUser, $ApacheUser, $EnableMemoryProtection;
  $BwrapBinary = FALSE;
  $bwrapReason = '';
  $locatedBinary = $bwrapCommand = '';
  $bwrapOutput = array();
  $bwrapExitCode = 1;
  $locatedBinary = locateDependency('bwrap');
  if ($locatedBinary !== '') {
    // / Launch the smallest possible sandbox & run a command that does nothing but exit.
    // / This proves the kernel will actually grant the namespaces a real render depends on.
    // / The ro-bind-try entries are optional & are skipped on systems that do not have them.
    $bwrapCommand = 'timeout 10 '.escapeshellarg($locatedBinary)
      .' --unshare-all'
      .' --die-with-parent'
      .' --ro-bind /usr /usr'
      .' --ro-bind-try /lib /lib'
      .' --ro-bind-try /lib64 /lib64'
      .' --ro-bind-try /bin /bin'
      .' --proc /proc'
      .' --dev /dev'
      .' --tmpfs /tmp'
      .' /usr/bin/true 2>&1';
    // / A probe run as root proves nothing. Root can always create a namespace, so a check
    // / made by -fp would report a working sandbox while every web request still failed.
    // / When this is running as root the probe is re-run as the account that will actually
    // / run conversions, which is the only answer worth reporting.
    // / The probe runs as the account that will actually run conversions.
    // / runAsAccount forks & drops rather than spawning a shell through su, so the
    // / privilege this call exists to give up is not held while the command runs.
    // / stderr is KEPT. bwrap names the exact reason it could not build a namespace, &
    // / discarding it left an operator with an exit code & nothing to act on. The usual
    // / cause is a kernel restricting unprivileged user namespaces, which -fp corrects.
    if ($RunningAsRoot && $CurrentUser !== $ApacheUser) list ($bwrapOutput, $bwrapExitCode) = runAsAccount($bwrapCommand, $ApacheUser);
    else exec($bwrapCommand, $bwrapOutput, $bwrapExitCode);
    if ($bwrapExitCode === 0) $BwrapBinary = $locatedBinary;
    else $bwrapReason = trim((string)(isset($bwrapOutput[0]) ? $bwrapOutput[0] : 'no reason was reported')); }
  if ($BwrapBinary === FALSE && $bwrapReason !== '') warningEntry('Bubblewrap could not build a sandbox'.(($RunningAsRoot && $CurrentUser !== $ApacheUser) ? ' as '.$ApacheUser : '').'. '.$bwrapReason.' Run the -fp argument as root to install the AppArmor profile an unprivileged user namespace needs.');
  if ($Verbose) logEntry('Bubblewrap Sandbox Check: '.($BwrapBinary === FALSE ? 'FAILED' : 'PASSED').', Exit code: '.$bwrapExitCode.($BwrapBinary === FALSE ? ', Reason: '.$bwrapReason : ', Using: '.$BwrapBinary).'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $locatedBinary, $bwrapCommand, $bwrapOutput, $bwrapExitCode, $bwrapReason);
  return $BwrapBinary; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to wrap a dependency invocation in a bubblewrap sandbox.
// / Accepts a finished command, the real path of the input, the real path of the output,
// / & a boolean granting network access.
// / Both paths must appear in the command exactly as escapeshellarg() rendered them,
// / because that is the form matched when they are rewritten for the namespace.
// / A path built any other way will not be matched & the command will refer to a location
// / that does not exist inside the sandbox.
// / Returns a permission boolean & a command string ready to run unmodified.
// / The boolean reports whether the command MAY RUN, not whether a sandbox was built.
// / A sandbox that was built returns TRUE with a bwrap command.
// / A sandbox that could not be built returns FALSE when sandboxing is required, so every
// / caller refuses without any caller needing to know why.
// / A sandbox that could not be built returns TRUE with the unwrapped command when
// / sandboxing is not required, & writes a warning naming the conversion as unprotected.
// / A container blocks the namespaces bubblewrap needs unless it was started with
// / --security-opt seccomp=unconfined, so a separate setting governs that case.
// / The directory holding the input is mounted read only at /in.
// / The directory holding the output is mounted writable at /out.
// / When both paths share a directory it is mounted once, writable, at /work.
// / Nothing else from the data location is visible inside the namespace.
// / The mounts are derived from the supplied paths, so the caller never names one.
// / Network access is unshared unless requested, which closes every URL handler at once.
// / OpenSCAD does NOT use this. It needs a whole directory visible to resolve includes.
// / The sixth argument is a directory the command needs that is neither its input nor its
// / output. A converter loading a module, a template or a profile from inside the
// / installation cannot reach it otherwise, because the namespace binds the input directory
// / & the output directory & nothing else at all.
// / PyMeshLab is why this exists. Its command inserts the bundled module directory onto the
// / Python path, that directory was never bound, & every MeshLab route therefore failed the
// / moment --Use PyMeshLab Python Bindings-- was enabled with sandboxing on. It looked like
// / a broken conversion rather than like a missing bind.
// / A caller with nothing to bind passes nothing & is unaffected.
// / The seventh argument is a set of environment variables the command needs, keyed by
// / name. A caller must never prefix them onto the command itself.
// / bwrap execs the command with execvp & never through a shell.
// / A NAME=value prefix is therefore read as the name of a binary rather than as an
// / assignment, & the failure looks like a missing program.
// / The message is  execvp QT_QPA_PLATFORM=offscreen: No such file or directory  which
// / names a shell feature that was never involved.
// / A value naming the read only resource is rewritten to its path inside the namespace.
// / An unsandboxed command is run through a shell & takes the same variables as a prefix.
// / The eighth argument is a hosts file the application wrote, bound at /etc/hosts.
// / It is the ONLY name resolution a networked command is permitted. The application
// / resolves every name itself, inspects the address, & writes the approved pairs here.
// / A name absent from the file cannot resolve, because no resolver is bound either.
// / A redirect to an unapproved name therefore fails at lookup, & a certificate still
// / validates, because the tool connects by the real name & only the address was pinned.
function sandboxCommand($command, $inputPath, $outputPath, $allowNetwork, $sandboxProfile, $readOnlyResourcePath = '', $environmentVariables = array(), $pinnedHostsFile = '') {
  // / Set variables.
  global $Verbose, $RequireSandbox, $RequireSandboxOnDocker, $ThrowSandboxWarning, $RunningInContainer, $EnableMemoryProtection;
  $CommandMayRun = FALSE;
  $bwrapBinary = FALSE;
  // / This initializes TRUE rather than FALSE, because for this variable TRUE is the safe
  // / state. It is overwritten unconditionally below & the initial value is never read.
  $sandboxIsRequired = TRUE;
  $SandboxedCommand = $networkFlag = $mountFlags = $workingDir = $profileFlags = $resourceFlags = '';
  $environmentFlags = $environmentPrefix = $environmentName = $environmentValue = '';
  $rewriteSearch = $rewriteReplace = array();
  $inputDir = $outputDir = $sandboxInput = $sandboxOutput = '';
  $bwrapBinary = verifyBwrap();
  // / Collect the mounts this one dependency needs. Nothing else receives them.
  $profileFlags = sandboxProfileFlags($sandboxProfile);
  // / A container that CAN build a sandbox still gets one. This only decides what happens
  // / when it cannot.
  $sandboxIsRequired = $RunningInContainer ? $RequireSandboxOnDocker : $RequireSandbox;
  // / Resolve each path to the directory holding it.
  // / The output file does not exist yet, so its directory is used rather than the file.
  $inputDir = dirname($inputPath);
  $outputDir = dirname($outputPath);
  // / Build the mounts & the paths the two files carry inside the namespace.
  // / One directory means one writable mount, because the output has to be written somewhere.
  // / Two directories means a read only input & a writable output, so a dependency that is
  // / exploited while parsing a hostile file cannot modify the file it came from.
  if ($inputDir === $outputDir) {
    $workingDir = '/work';
    $mountFlags = ' --bind '.escapeshellarg($outputDir).' /work';
    $sandboxInput = escapeshellarg('/work/'.basename($inputPath));
    $sandboxOutput = escapeshellarg('/work/'.basename($outputPath)); }
  else {
    $workingDir = '/out';
    $mountFlags = ' --ro-bind '.escapeshellarg($inputDir).' /in'
      .' --bind '.escapeshellarg($outputDir).' /out';
    $sandboxInput = escapeshellarg('/in/'.basename($inputPath));
    $sandboxOutput = escapeshellarg('/out/'.basename($outputPath)); }
  // / The rewrite pairs are built rather than assumed, because a pair is only correct when
  // / there is something to bind. escapeshellarg('') returns two quote characters, so an
  // / unconditional pair would search the command for an empty quoted argument & replace
  // / every one it found with nothing.
  // / Both forms are built & which one is used depends on whether bwrap is available.
  // / A sandboxed command takes --setenv & an unsandboxed one takes a shell prefix.
  if (is_array($environmentVariables)) {
    foreach ($environmentVariables as $environmentName => $environmentValue) {
      $environmentPrefix = $environmentPrefix.$environmentName.'='.escapeshellarg((string)$environmentValue).' ';
      if (trim((string)$readOnlyResourcePath) !== '') $environmentValue = str_replace((string)$readOnlyResourcePath, '/res', (string)$environmentValue);
      $environmentFlags = $environmentFlags.' --setenv '.escapeshellarg($environmentName).' '.escapeshellarg((string)$environmentValue); } }
  $rewriteSearch = array(escapeshellarg($inputPath), escapeshellarg($outputPath));
  $rewriteReplace = array($sandboxInput, $sandboxOutput);
  if (trim((string)$readOnlyResourcePath) !== '' && is_dir((string)$readOnlyResourcePath)) {
    $resourceFlags = ' --ro-bind '.escapeshellarg((string)$readOnlyResourcePath).' /res';
    array_push($rewriteSearch, escapeshellarg((string)$readOnlyResourcePath));
    array_push($rewriteReplace, escapeshellarg('/res')); }
  // / A sandbox that could not be built is a policy decision rather than a technical one.
  // / An operator who has deliberately accepted the risk gets the command & a warning.
  // / An operator who has not gets a refusal, & every caller already handles that.
  if ($bwrapBinary === FALSE) {
    $SandboxedCommand = $environmentPrefix.$command;
    if ($sandboxIsRequired) warningEntry('Bubblewrap is unavailable & sandboxing is required, so a conversion was refused. Install bubblewrap, or set '.($RunningInContainer ? '$RequireSandboxOnDocker' : '$RequireSandbox').' to FALSE in config.php to run conversions unprotected.');
    else {
      $CommandMayRun = TRUE;
      if ($ThrowSandboxWarning) warningEntry('Bubblewrap is unavailable & sandboxing is not required, so a conversion will run unprotected.'); } }
  else {
    $CommandMayRun = TRUE;
    // / --unshare-all removes every namespace the command has no business holding.
    // / --share-net gives back ONLY the network, for the one caller that needs it.
    // / The network is shared & the resolver configuration is NOT. That is deliberate & it
    // / is a security boundary rather than an oversight.
    // / A tool given this application's resolver would resolve names for itself, at the
    // / moment of use, after the application had already inspected & approved the address.
    // / A name that answered one way to the inspection & another way to the tool is the
    // / whole of a DNS rebinding attack, & a redirect the tool follows on its own is the
    // / whole of a redirect based one.
    // / A namespace with a network & no resolver can reach an address it is given & cannot
    // / look one up. That is exactly the capability the application means to grant.
    if ($allowNetwork) {
      $networkFlag = ' --share-net';
      if (trim((string)$pinnedHostsFile) !== '' && is_file((string)$pinnedHostsFile)) $networkFlag = $networkFlag.' --ro-bind '.escapeshellarg((string)$pinnedHostsFile).' /etc/hosts'; }
    // / The rewrite is an exact match on the escaped paths rather than a pattern, so nothing
    // / else in the command can be altered by accident. Neither escaped path can appear
    // / inside the other's replacement, so a single pass is safe.
    $SandboxedCommand = escapeshellarg($bwrapBinary)
      .' --unshare-all'.$networkFlag
      .' --die-with-parent'
      .' --new-session'
      .$resourceFlags
      .' --ro-bind /usr /usr'
      .' --ro-bind-try /lib /lib'
      .' --ro-bind-try /lib64 /lib64'
      .' --ro-bind-try /bin /bin'
      .' --ro-bind-try /sbin /sbin'
      .' --ro-bind-try /etc/alternatives /etc/alternatives'
      .' --ro-bind-try /etc/fonts /etc/fonts'
      .' --ro-bind-try /etc/ld.so.cache /etc/ld.so.cache'
      .' --ro-bind-try /etc/ssl/certs /etc/ssl/certs'
      .' --ro-bind-try /opt /opt'
      .' --proc /proc'
      .' --dev /dev'
      .' --tmpfs /tmp'
      .' --tmpfs /run'
      // / A dependency resolves the running user with getpwuid() during startup & throws
      // / out of its configuration backend when the lookup fails. --unshare-all unshares
      // / the user namespace, so without these the lookup has nothing to read & LibreOffice
      // / aborts with Signal 6 before it opens a file.
      .' --ro-bind-try /etc/passwd /etc/passwd'
      .' --ro-bind-try /etc/group /etc/group'
      .' --ro-bind-try /etc/machine-id /etc/machine-id'
      .' --ro-bind-try /etc/localtime /etc/localtime'
      // / --dev builds a minimal device tree with no /dev/shm, which several dependencies
      // / require for shared memory. A tmpfs is enough & stays inside the namespace.
      .' --tmpfs /dev/shm'
      .' --setenv HOME /tmp'
      // / Every writable location a dependency reaches for is pointed at the tmpfs.
      // / Nothing tries to create state outside the namespace & fail.
      .' --setenv XDG_RUNTIME_DIR /tmp'
      .' --setenv XDG_CONFIG_HOME /tmp/.config'
      .' --setenv XDG_CACHE_HOME /tmp/.cache'
      .' --setenv XDG_DATA_HOME /tmp/.local'
      // / The caller's variables come last, so a caller may override anything above.
      .$environmentFlags
      // / Headless rendering with no display server & no OpenCL probing.
      .$profileFlags
      .$mountFlags
      .' --chdir '.$workingDir
      .' '
      // / The resource is rewritten alongside the input & the output when there is one,
      // / so a command naming it is corrected by the same single pass.
      .str_replace(
        $rewriteSearch,
        $rewriteReplace,
        $command);
    if ($Verbose) logEntry('Sandbox prepared for a dependency invocation.'); }
  // / Wrap whatever was built in its resource ceiling. This is applied to the sandbox
  // / rather than to the tool, so the ceiling covers bubblewrap & everything under it.
  // / An unsandboxed command is still limited, because the host deserves protection even
  // / when the administrator has turned the sandbox off.
  if ($CommandMayRun) $SandboxedCommand = limitCommand($SandboxedCommand, $sandboxProfile);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $pinnedHostsFile, $environmentFlags, $environmentPrefix, $environmentName, $environmentValue, $environmentVariables, $resourceFlags, $rewriteSearch, $rewriteReplace, $readOnlyResourcePath, $bwrapBinary, $sandboxIsRequired, $networkFlag, $mountFlags, $profileFlags, $workingDir, $inputDir, $outputDir, $sandboxInput, $sandboxOutput, $command, $inputPath, $outputPath, $allowNetwork, $sandboxProfile);
  return array($CommandMayRun, $SandboxedCommand); }
// / -----------------------------------------------------------------------------------

?>
