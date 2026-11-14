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
// / v4.2.0.
// / The Engine. It provides the environment an application runs in.
// / The application pins it exactly via $RequiredEngineVersion.
// / Error block 35000 through 35019 is reserved. 35000 is the direct load guard every component shares, the kernel's included.
// / See Documentation/ABOUT_ENGINE_CONTRACT.txt for what an application must supply.
// /
// / It answers where an application is, whether it runs as root & whether a terminal is attached.
// / It finds binaries, writes logs, shreds variables & proves that a process it started is its own.
// / It is not a framework. The application is the entry point, & it boots, verifies its components, loads this file & calls into it.
// / The Engine never calls application code, because an Engine that did would only work for one application.
// /
// / Three tiers exist.
// /   The kernel   Resources/Kernel/kernel.php, beside this directory, because it loads this file & reports when that fails.
// /   The Engine   This file. The environment every application needs.
// /   The app      Everything that makes one application different from another.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by an application.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! Engine-35000, The Engine cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The version of this Engine. Read by the application WITHOUT executing this file.
$EngineVersion = 'v4.2.0';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The environment every application built on this Engine is given.
// / Loaded here rather than by the application, because an application should not have to
// / know which files the Engine is made of.
// / Every definition inside is guarded, so an application that already defines one keeps
// / its own & nothing is redeclared. See the file for why that matters.
$engineEnvironmentFile = dirname(__FILE__).DIRECTORY_SEPARATOR.'Cores'.DIRECTORY_SEPARATOR.'environmentCore.php';
if (file_exists($engineEnvironmentFile)) require_once($engineEnvironmentFile);
else warningEntry('The Engine environment at '.$engineEnvironmentFile.' is missing. An application supplying its own definitions still runs.');
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to report whether systemd is actually running this host.
// / Accepts no arguments.
// / Returns a usability boolean & the reason, in that order.
// /
// / The binary being present proves nothing, because official PHP container images ship the systemd client tools without systemd.
// / /run/systemd/system exists only when systemd is PID 1.
// / That is the test the systemd documentation gives & what sd_booted does.
// / It is correct in a container, in a chroot & under any other init.
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
    if (!is_dir('/run/systemd/system')) $SystemdReason = 'systemd is not running.';
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
// / A startup key travels in the environment, because /proc/PID/environ is readable only by the process owner.
// / /proc/PID/cmdline is world readable, so a key on the command line could be read from ps for the life of the process.
// / The variable is cleared the moment it is read, so a converter the manager launches later does not inherit it.
function readTransportedStartupKey() {
  // / Set variables.
  global $EnableMemoryProtection;
  $TransportedKey = '';
  $rawKey = $keyVariable = '';
  $keyVariable = engineStartupKeyVariable();
  $rawKey = (string)getenv($keyVariable);
  if ($rawKey !== '') {
    $TransportedKey = preg_replace('/[^a-f0-9]/', '', strtolower($rawKey));
    putenv($keyVariable);
    unset($_ENV[$keyVariable], $_SERVER[$keyVariable]); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $rawKey, $keyVariable);
  return $TransportedKey; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the environment variable a workflow worker's session travels in.
// / Accepts nothing. Returns the application's slug upper cased, followed by _WORKER_CONTEXT.
// / For HRConvert2 it is HRCONVERT2_WORKER_CONTEXT.
function engineWorkerContextVariable() {
  // / Set variables.
  $WorkerContextVariable = strtoupper(engineApplicationSlug()).'_WORKER_CONTEXT';
  return $WorkerContextVariable; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the session a workflow worker was handed & clear it from the environment.
// / Accepts nothing. Returns the session as the application wrote it, or an empty string when none was handed over.
// / It is also kept in $TransportedWorkerContext, because the worker's startup key is bound to it & is checked once the Workflow Core loads.
// / It is cleared from the environment at once, so a converter the worker starts does not inherit it.
// / It lives here rather than in the Workflow Core, because the application adopts the session before the globals that hang off one are built, & that is before any core loads.
// / A worker started for a signed in user was handed that user's account after the session, as engineWorkerContext() writes it.
// / The account is kept in $TransportedWorkerIdentity for the Authentication Core, & the application is handed its own session alone.
// / $TransportedWorkerContext keeps the whole, so the startup key binds the account as well as the session.
function readTransportedWorkerContext() {
  // / Set variables.
  global $TransportedWorkerContext, $TransportedWorkerIdentity, $EnableMemoryProtection;
  $WorkerContext = '';
  $contextVariable = '';
  $contextParts = array();
  $contextVariable = engineWorkerContextVariable();
  $TransportedWorkerContext = (string)getenv($contextVariable);
  putenv($contextVariable);
  unset($_ENV[$contextVariable], $_SERVER[$contextVariable]);
  $TransportedWorkerIdentity = '';
  if (preg_match('/^(.*)\|([a-f0-9]{64})$/s', $TransportedWorkerContext, $contextParts) === 1) list ($WorkerContext, $TransportedWorkerIdentity) = array($contextParts[1], $contextParts[2]);
  else $WorkerContext = $TransportedWorkerContext;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $contextVariable, $contextParts);
  return $WorkerContext; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name what a workflow worker is handed.
// / Accepts the session as the application names it. Returns that session, followed by | & the signed in user's account where there is one.
// / The account is the name of its record, which grants nothing by itself, & the worker's startup key is bound to the whole.
// / A worker handed no account is anonymous, exactly as every worker was before accounts existed.
function engineWorkerContext($sessionContext) {
  // / Set variables.
  global $EnableMemoryProtection;
  $WorkerContext = '';
  $quotaOwner = '';
  $quotaOwner = storageQuotaOwner();
  $WorkerContext = (string)$sessionContext;
  if (preg_match('/^[a-f0-9]{64}$/', $quotaOwner) === 1) $WorkerContext .= '|'.$quotaOwner;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $quotaOwner, $sessionContext);
  return $WorkerContext; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to spend a startup key so it can never be presented a second time.
// / Accepts the purpose & the key. Returns TRUE when the key had not been spent before.
// / Time alone cannot make a leaked key dead, because its holder validates it against the same clock.
// / Spending the key is a property this application controls.
// / The ledger holds hashes rather than keys, so reading it grants nothing.
// / Entries older than three windows are dropped, because a key that old cannot validate anyway.
// /
// / It defends against a key that leaked, which cannot be spent twice, or at all once its process has used it.
// / It does not defend against an account holding the install secret, which can mint a valid key directly.
// / Root & the web server user can delete the ledger, & both can already read the secret, so deleting it gains them nothing.
// / The ledger sits in the socket folder, 0700 & the web server user's, so an unprivileged local account cannot reach it.
// / It is read & written again under one lock, because the listener's managers spend their keys at the same moment.
// / Until v4.1.9 each read it, & wrote back what it read with its own key, so all but the last key written were lost.
// / A ledger that cannot be opened or locked spends nothing, & says so, because a key it could not record could be replayed.
function consumeStartupKey($keyPurpose, $suppliedKey) {
  // / Set variables.
  global $ManagerSocketDir, $DirSep, $StartupKeyWindow, $EnableMemoryProtection;
  $KeyWasUnspent = TRUE;
  $ledgerPath = $ledgerContents = $keyFingerprint = $rebuiltLedger = $ledgerLine = '';
  $ledgerLines = array();
  $cutoffTime = 0;
  $ledgerHandle = FALSE;
  $ledgerIsLocked = FALSE;
  if (is_dir((string)$ManagerSocketDir)) {
    $ledgerPath = rtrim((string)$ManagerSocketDir, $DirSep).$DirSep.'startup-keys.ledger';
    $keyFingerprint = hash('sha256', (string)$keyPurpose.'|'.(string)$suppliedKey);
    $cutoffTime = time() - (max(1, (int)$StartupKeyWindow) * 3);
    $ledgerHandle = @fopen($ledgerPath, 'c+');
    if (is_resource($ledgerHandle)) $ledgerIsLocked = @flock($ledgerHandle, LOCK_EX);
    if (!$ledgerIsLocked) warningEntry('The ledger of spent startup keys at '.$ledgerPath.' could not be opened & locked, so a startup key for '.$keyPurpose.' was accepted without being recorded as spent.');
    else {
      $ledgerContents = (string)stream_get_contents($ledgerHandle);
      $ledgerLines = ($ledgerContents === '' ? array() : explode(PHP_EOL, $ledgerContents));
      // / A line is a timestamp & a fingerprint. Anything older than the cutoff is dropped
      // / rather than carried, so the ledger cannot grow without bound.
      foreach ($ledgerLines as $ledgerLine) {
        if (trim($ledgerLine) === '' or (int)substr($ledgerLine, 0, strpos($ledgerLine, ' ')) < $cutoffTime) continue;
        if (substr($ledgerLine, strpos($ledgerLine, ' ') + 1) === $keyFingerprint) $KeyWasUnspent = FALSE;
        $rebuiltLedger = $rebuiltLedger.$ledgerLine.PHP_EOL; }
      if ($KeyWasUnspent && @ftruncate($ledgerHandle, 0) && @rewind($ledgerHandle)) @fwrite($ledgerHandle, $rebuiltLedger.time().' '.$keyFingerprint.PHP_EOL);
      @fflush($ledgerHandle);
      @flock($ledgerHandle, LOCK_UN); }
    if (is_resource($ledgerHandle)) @fclose($ledgerHandle);
    if (file_exists($ledgerPath)) @chmod($ledgerPath, 0600); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $ledgerPath, $ledgerContents, $keyFingerprint, $rebuiltLedger, $ledgerLine, $ledgerLines, $cutoffTime, $ledgerHandle, $ledgerIsLocked, $keyPurpose, $suppliedKey);
  return $KeyWasUnspent; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to derive a time bucketed startup key for an internal core invocation.
// / Accepts the purpose the key authorizes.
// / Returns the key, or an empty string when no install secret is available.
// / The bucket bounds the window in which a captured key can be reused.
// / A key is a launch number of 32 hexadecimal characters drawn at random, then a signature of 64 over the purpose, the bucket & that number.
// / So every key minted is a key of its own, & a second launch inside one window is not a replay of the first.
// / A host that cannot supply random bytes mints the signature alone, as releases before v4.1.9 did, & says so.
// / Two launches of one purpose inside one window then share a key, & the second is refused as a replay.
function deriveStartupKey($keyPurpose) {
  // / Set variables.
  global $SecretKey, $StartupKeyWindow, $EnableMemoryProtection;
  $StartupKey = '';
  $timeBucket = 0;
  $keyMaterial = $launchNumber = '';
  $randomError = NULL;
  if (is_string($SecretKey) && strlen($SecretKey) === 64) {
    $timeBucket = (int)floor(time() / max(1, (int)$StartupKeyWindow));
    try { $launchNumber = bin2hex(random_bytes(16)); }
    catch (Throwable $randomError) { $launchNumber = ''; }
    if ($launchNumber === '') {
      warningEntry('A startup key for '.$keyPurpose.' carries no launch number, because this host could not supply random bytes. A second launch inside the same window will be refused as a replay.');
      $keyMaterial = 'startup|'.$keyPurpose.'|'.$timeBucket; }
    else $keyMaterial = 'startup|'.$keyPurpose.'|'.$timeBucket.'|'.$launchNumber;
    $StartupKey = $launchNumber.hash_hmac('sha256', $keyMaterial, $SecretKey); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $timeBucket, $keyMaterial, $launchNumber, $randomError, $keyPurpose);
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
// / A key of 96 characters is a launch number & the signature over it, as deriveStartupKey mints it.
// / A key of 64 is a signature alone. A release before v4.1.9 mints that, & so does a listener it started, after an update, until the listener is restarted.
// / So it is still accepted, & both are spent the same way, by the whole key.
function validateStartupKey($keyPurpose, $suppliedKey, $singleUse = FALSE) {
  // / Set variables.
  global $SecretKey, $StartupKeyWindow, $EnableMemoryProtection;
  $KeyIsValid = FALSE;
  $timeBucket = 0;
  $keyWasUnspent = TRUE;
  $currentKey = $previousKey = $cleanKey = $keySignature = $launchContext = '';
  $cleanKey = preg_replace('/[^a-f0-9]/', '', strtolower((string)$suppliedKey));
  if (strlen($cleanKey) === 96) {
    $launchContext = '|'.substr($cleanKey, 0, 32);
    $keySignature = substr($cleanKey, 32); }
  else if (strlen($cleanKey) === 64) $keySignature = $cleanKey;
  if (is_string($SecretKey) && strlen($SecretKey) === 64 && $keySignature !== '') {
    $timeBucket = (int)floor(time() / max(1, (int)$StartupKeyWindow));
    $currentKey = hash_hmac('sha256', 'startup|'.$keyPurpose.'|'.$timeBucket.$launchContext, $SecretKey);
    $previousKey = hash_hmac('sha256', 'startup|'.$keyPurpose.'|'.($timeBucket - 1).$launchContext, $SecretKey);
    if (hash_equals($currentKey, $keySignature) or hash_equals($previousKey, $keySignature)) $KeyIsValid = TRUE; }
  // / The key is only spent once it has proved genuine, so a wrong guess cannot fill the
  // / ledger & cannot deny the real one.
  if ($KeyIsValid && $singleUse) {
    $keyWasUnspent = consumeStartupKey($keyPurpose, $cleanKey);
    if (!$keyWasUnspent) {
      $KeyIsValid = FALSE;
      errorEntry('A startup key for '.$keyPurpose.' was presented a second time & refused as a replay!', 31017, FALSE); } }
  if (!$KeyIsValid && $keyWasUnspent) warningEntry('A startup key for '.$keyPurpose.' was refused.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $timeBucket, $currentKey, $previousKey, $cleanKey, $keySignature, $launchContext, $keyWasUnspent, $keyPurpose, $suppliedKey, $singleUse);
  return $KeyIsValid; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to check that this host can actually do the work, without changing it.
// / Accepts no arguments.
// / Returns a readiness boolean & an array of findings, in that order.
// / This never repairs anything. Repair needs root & the listener does not have it, so a
// / validator that tried would fail confusingly on every pass. It reports, & -fp fixes.
// / Readiness is FALSE only for a condition that stops conversions outright. A policy that
// / has drifted is reported & does not fail the check, because conversions still run.
function validateOperatingEnvironment() {
  // / Set variables.
  global $EngineEnvironmentProvider, $InstLoc, $ApplicationLoc, $RequiredSetupCoreVersion, $DirSep, $CurrentUser, $ApacheUser, $EnableMemoryProtection;
  $EnvironmentIsReady = TRUE;
  $EnvironmentFindings = array();
  $sandboxIsRequired = $providerIsReady = $scriptIsValid = $locationIsProtected = $setupIsAvailable = FALSE;
  $environmentProvider = $scriptStatus = $scriptDisplayStatus = $scriptDetail = $servedPath = $exposureStatus = $exposureDetail = $hostedStatus = $hostedDetail = $setupVersion = $hostedLocation = $documentRoot = '';
  $providerFindings = $providerFinding = $misplacedRoots = $kernelFindings = $kernelFinding = array();
  $hostedWritten = $hostedRemoved = 0;
  $kernelIsReady = $policyIsValid = $bwrapBinary = FALSE;
  $policyStatus = $sandboxLauncher = '';
  // / The sandbox is the one thing a conversion cannot proceed without when it is required.
  // / sandboxIsRequired() is the rule sandboxCommand() follows, so this finding says what a conversion meets.
  $sandboxIsRequired = sandboxIsRequired();
  // / The Engine checks what the ENGINE provides & nothing else.
  // / Bubblewrap is one of the things here that belong to it, because sandboxCommand is what
  // / builds the namespace & this is that namespace being proved.
  // / The launcher says how, so a host that keeps its namespace restriction can see that it does.
  list ($bwrapBinary, $sandboxLauncher) = verifySandboxLauncher();
  // / An administrator who is neither root nor the web server's account cannot run this application's
  // / own copy of bubblewrap, so a sandbox it cannot build says nothing of the one conversions get.
  if ($bwrapBinary === FALSE && !sandboxCopyIsJudgedHere() && namespaceRestriction() === '1' && sandboxBinaryIsNeeded()) $EnvironmentFindings[] = array('Check' => 'Sandbox', 'Status' => 'n/a', 'Detail' => 'Only root & group '.$ApacheUser.' may run '.engineApplicationName().'\'s own bubblewrap. Run -v as root.');
  else if ($bwrapBinary === FALSE) {
    $EnvironmentFindings[] = array('Check' => 'Sandbox', 'Status' => 'FAILED', 'Detail' => 'Bubblewrap cannot build a namespace.'.($sandboxIsRequired ? ' Every conversion will be refused.' : ' Conversions will run unprotected.'));
    if ($sandboxIsRequired) $EnvironmentIsReady = FALSE; }
  else $EnvironmentFindings[] = array('Check' => 'Sandbox', 'Status' => 'ok', 'Detail' => 'Bubblewrap can build a namespace'.($bwrapBinary === privateBwrapPath() ? ', through '.privateBwrapPath().' & AppArmor profile '.sandboxApparmorProfileName().'.' : '.').(strpos((string)$sandboxLauncher, '--disable-userns') !== FALSE ? ' A sandbox cannot make a user namespace.' : ' A sandbox can make user namespaces. Bubblewrap 0.8 or later stops that.'));
  // / The bootstrap script is another. The Engine pins it exactly, & one left by another
  // / release hands arguments to a core that may no longer accept them. It is reported here so
  // / -v, the listener & the Environment Manager all see it, & -fp changes it as root. It never
  // / stops a conversion, so it never makes the environment unready.
  list ($scriptIsValid, $scriptStatus) = verifyBootstrapScript();
  list ($scriptDisplayStatus, $scriptDetail) = describeBootstrapStatus($scriptStatus);
  $EnvironmentFindings[] = array('Check' => 'Bootstrap script', 'Status' => $scriptDisplayStatus, 'Detail' => $scriptDetail);
  // / What every sandbox needs from the host is the Engine's, & is reported first among the findings.
  // / A kernel that refuses every namespace stops every conversion, so it makes the environment unready.
  // / A drifted AppArmor profile is reported.
  list ($kernelIsReady, $kernelFindings) = verifySandboxKernel(FALSE);
  foreach ($kernelFindings as $kernelFinding) $EnvironmentFindings[] = $kernelFinding;
  if (!$kernelIsReady) $EnvironmentIsReady = FALSE;
  list ($policyIsValid, $policyStatus) = verifySandboxPolicy(FALSE);
  $EnvironmentFindings[] = array('Check' => 'Sandbox AppArmor', 'Status' => policyDisplayStatus($policyStatus), 'Detail' => describePolicyStatus('Sandbox AppArmor', $policyStatus));
  // / What the application keeps on this host is the application's to check.
  // / The application names one provider in engineConfig.php & the Engine calls it, as it reads $EngineSandboxProfiles.
  // / A provider returns a readiness boolean & a list of findings, the shape this function returns, & the two lists are joined.
  // / An installation that declares none gets the Engine's own checks & nothing else, which is correct rather than degraded.
  // / The policies a tool's own conversions carry are the application's, so no other application is asked about ImageMagick.
  $environmentProvider = (isset($EngineEnvironmentProvider) && is_string($EngineEnvironmentProvider)) ? trim($EngineEnvironmentProvider) : '';
  if ($environmentProvider !== '' && function_exists($environmentProvider)) {
    list ($providerIsReady, $providerFindings) = $environmentProvider();
    if (is_array($providerFindings)) foreach ($providerFindings as $providerFinding) $EnvironmentFindings[] = $providerFinding;
    if ($providerIsReady === FALSE) $EnvironmentIsReady = FALSE; }
  else if ($environmentProvider !== '') warningEntry('An environment provider named '.$environmentProvider.' was declared & is not defined. Only the Engine checks ran.');
  // / What the web server serves.
  // / Each served location is asked whether it hands its files over inert.
  // / The data location must sit outside the document root, & the hosted location is compared with the installation.
  // / An application without a hosted location serves nothing, so it is asked none of this.
  // / A served location such an application declares is named as misplaced.
  // / These come after the application's own findings.
  // / None of these stops a conversion, so none makes the environment unready, & each is reported loudly.
  // / The hosted location & the document root are the application's, read through the map as HostedLocation & DocumentRoot.
  $hostedLocation = (string)applicationSetting('HostedLocation', '');
  $documentRoot = (string)applicationSetting('DocumentRoot', '');
  foreach (servedLocations() as $servedPath) {
    list ($locationIsProtected, $exposureStatus, $exposureDetail) = verifyServedExposure($servedPath);
    $EnvironmentFindings[] = array('Check' => basename($servedPath).' exposure', 'Status' => ($exposureStatus === 'protected' ? 'ok' : strtoupper($exposureStatus)), 'Detail' => $exposureDetail); }
  // / A served location anywhere but inside the hosted location is refused, so no rule is written
  // / for it & nothing tests it. That is a mistake in the contract, & it is named here on a line of
  // / its own. It never shares an exposure line's name, because -fp & the listener read a location's
  // / exposure by that name, & a refused location named alike would stand in for the real answer.
  foreach (misplacedServedLocations() as $servedPath) $EnvironmentFindings[] = array('Check' => (basename($servedPath) === '' ? $servedPath : basename($servedPath)).' placement', 'Status' => 'MISPLACED', 'Detail' => $servedPath.' is served & is outside the hosted location'.($hostedLocation === '' ? ', which is not set' : ' '.$hostedLocation).'. No rule is written for it & its exposure is not tested.');
  if ($hostedLocation !== '') {
    // / The data location itself is never served, & is named here when it sits inside the
    // / document root, where its sessions, caches & logs could be fetched.
    $misplacedRoots = misplacedDataRoots();
    if (count($misplacedRoots) === 0) $EnvironmentFindings[] = array('Check' => 'Data location', 'Status' => 'ok', 'Detail' => 'Outside the document root.');
    else $EnvironmentFindings[] = array('Check' => 'Data location', 'Status' => 'MISPLACED', 'Detail' => implode(' & ', $misplacedRoots).' '.(count($misplacedRoots) === 1 ? 'is' : 'are').' inside the document root '.$documentRoot.', so sessions, caches & logs could be fetched. Move '.(count($misplacedRoots) === 1 ? 'it' : 'them').' outside it.');
    // / The application runs from Application in the data location, & the web server serves only the hosted location.
    // / The hosted location holds the loader. Both are reported & neither is repaired here.
    // / An application still running from inside the web root is NOT MOVED, & -fp moves it.
    // / A copy running from anywhere else is NOT INSTALLED, a release unpacked to be installed, which -fp installs as it ends.
    if ((string)@realpath($InstLoc) !== (string)@realpath($ApplicationLoc)) {
      if (pathHoldsPath($hostedLocation, $InstLoc) or ($documentRoot !== '' && pathHoldsPath($documentRoot, $InstLoc))) $EnvironmentFindings[] = array('Check' => 'Hosted location', 'Status' => 'NOT MOVED', 'Detail' => 'The application runs from '.$InstLoc.', which the web server serves. Run -fp as root to install it into '.$ApplicationLoc.'.');
      else $EnvironmentFindings[] = array('Check' => 'Hosted location', 'Status' => 'NOT INSTALLED', 'Detail' => 'The application runs from '.$InstLoc.', which is not the installed copy. Run -fp as root to install it into '.$ApplicationLoc.'.'); }
    else {
      list ($setupIsAvailable, $setupVersion) = verifyCoreComponent('Setup Core', 'Engine'.$DirSep.'Cores'.$DirSep.'setupCore.php', 'SetupCoreVersion', $RequiredSetupCoreVersion);
      if ($setupIsAvailable && function_exists('publishHostedLocation')) list ($hostedStatus, $hostedDetail, $hostedWritten, $hostedRemoved) = publishHostedLocation($hostedLocation, $InstLoc, FALSE);
      else {
        $hostedStatus = 'UNKNOWN';
        $hostedDetail = 'The Setup Core is unavailable.'; }
      $EnvironmentFindings[] = array('Check' => 'Hosted location', 'Status' => $hostedStatus, 'Detail' => $hostedDetail); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $kernelFindings, $kernelFinding, $kernelIsReady, $policyIsValid, $policyStatus, $bwrapBinary, $sandboxLauncher, $sandboxIsRequired, $environmentProvider, $providerIsReady, $providerFindings, $providerFinding, $scriptIsValid, $scriptStatus, $scriptDisplayStatus, $scriptDetail, $locationIsProtected, $setupIsAvailable, $servedPath, $exposureStatus, $exposureDetail, $hostedStatus, $hostedDetail, $setupVersion, $misplacedRoots, $hostedWritten, $hostedRemoved, $hostedLocation, $documentRoot);
  return array($EnvironmentIsReady, $EnvironmentFindings); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the bootstrap script.
// / Accepts nothing. Returns its absolute path, whether or not anything is there.
// / The script is the Engine's & ships in the application's Documentation/Build folder, named
// / for the application, so HRConvert2's is hrconvert2-setup.sh. There is no search & no
// / fallback, because a check that guessed which file to judge would one day judge another.
function bootstrapScriptPath() {
  // / Set variables.
  global $InstLoc, $EnableMemoryProtection;
  $ScriptPath = '';
  $ScriptPath = rtrim((string)$InstLoc, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'Documentation'.DIRECTORY_SEPARATOR.'Build'.DIRECTORY_SEPARATOR.engineApplicationSlug().'-setup.sh';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $ScriptPath is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection);
  return $ScriptPath; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to validate the bootstrap script without changing it.
// / Accepts nothing. Returns a validity boolean & a status word, in that order.
// / The status is one of these.
// /   absent       No script is installed. It is a convenience, so that is not a fault.
// /   ok           It matches this core & is enabled.
// /   disabled     It does not match & is already disabled, which is the safe state.
// /   mismatched   It does not match & would still run. -fp disables it.
// /   stale        It matches again & is still disabled. -fp enables it.
// /   unmarked     It does not match & carries no DisabledByCore line to set.
// /   unreadable   It could not be read.
// / A script that reports no parseable version is judged as not matching, because an unknown
// / build cannot be cleared.
// / It never writes. repairBootstrapScript() writes the script, as root, from -fp.
// / It is the Engine's, so -v, -fp & the Environment Manager can all ask it.
function verifyBootstrapScript() {
  // / Set variables.
  global $RequiredConfigScript, $Verbose, $EnableMemoryProtection;
  $ScriptIsValid = FALSE;
  $ScriptStatus = 'absent';
  $scriptPath = $scriptContents = $readNotice = $detectedVersion = $cleanDetected = $cleanRequired = '';
  $versionMatches = array();
  $scriptWasRead = $scriptIsDisabled = $scriptIsMarked = $scriptMatches = FALSE;
  $scriptPath = bootstrapScriptPath();
  clearstatcache(TRUE, $scriptPath);
  if (!file_exists($scriptPath) && !is_link($scriptPath)) {
    $ScriptIsValid = TRUE;
    $ScriptStatus = 'absent'; }
  else {
    // / Read through the kernel, which follows no link & cannot be held by a FIFO.
    list ($scriptWasRead, $scriptContents, $readNotice) = readFileWithoutFollowing($scriptPath, 1048576);
    if (!$scriptWasRead or $scriptContents === '') $ScriptStatus = 'unreadable';
    else {
      if (preg_match('/^SCRIPT_VERSION="([^"]+)"/m', $scriptContents, $versionMatches)) $detectedVersion = $versionMatches[1];
      $scriptIsDisabled = (preg_match('/^DisabledByCore="TRUE"/m', $scriptContents) === 1);
      $scriptIsMarked = (preg_match('/^DisabledByCore="(TRUE|FALSE)"/m', $scriptContents) === 1);
      $cleanDetected = ltrim(trim($detectedVersion), 'vV');
      $cleanRequired = ltrim(trim((string)$RequiredConfigScript), 'vV');
      $scriptMatches = ($cleanDetected !== '' && $cleanDetected === $cleanRequired);
      if ($scriptMatches && !$scriptIsDisabled) {
        $ScriptIsValid = TRUE;
        $ScriptStatus = 'ok'; }
      else if ($scriptMatches) $ScriptStatus = 'stale';
      else if ($scriptIsDisabled) {
        $ScriptIsValid = TRUE;
        $ScriptStatus = 'disabled'; }
      else if (!$scriptIsMarked) $ScriptStatus = 'unmarked';
      else $ScriptStatus = 'mismatched'; } }
  if ($Verbose) logEntry('Bootstrap Script: '.$scriptPath.', Reports: '.($detectedVersion === '' ? 'none' : $detectedVersion).', Status: '.$ScriptStatus.'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $scriptPath, $scriptContents, $readNotice, $detectedVersion, $cleanDetected, $cleanRequired, $versionMatches, $scriptWasRead, $scriptIsDisabled, $scriptIsMarked, $scriptMatches);
  return array($ScriptIsValid, $ScriptStatus); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to disable a bootstrap script that does not match this core, & to enable one
// / that matches again.
// / Accepts nothing. Returns a validity boolean & a status word, in that order.
// / The status is any word verifyBootstrapScript() returns, 'repaired' when a matching script was
// / enabled again, or 'failed' when a change could not be made.
// / Only root changes it, because the installation is root's, & -fp calls this only once root may
// / run the installation, so no other account can replace the folder holding it. The kernel
// / reads it & writes a new copy renamed into place, so nothing planted at the name is followed.
// / The DisabledByCore line is the only thing changed. The script is never deleted & is not
// / otherwise altered, so an operator can read exactly what was disabled & reinstall the match.
function repairBootstrapScript() {
  // / Set variables.
  global $RunningAsRoot, $EnableMemoryProtection;
  $ScriptIsValid = FALSE;
  $ScriptStatus = 'absent';
  $scriptPath = $scriptContents = $readNotice = $writeNotice = $detectedVersion = '';
  $updatedContents = NULL;
  $versionMatches = array();
  $scriptWasRead = $scriptWasWritten = FALSE;
  list ($ScriptIsValid, $ScriptStatus) = verifyBootstrapScript();
  if (($ScriptStatus === 'mismatched' or $ScriptStatus === 'stale') && !$RunningAsRoot) warningEntry('Only root may change the bootstrap script, because the installation is root\'s. Nothing was changed.');
  else if ($ScriptStatus === 'mismatched' or $ScriptStatus === 'stale') {
    $scriptPath = bootstrapScriptPath();
    list ($scriptWasRead, $scriptContents, $readNotice) = readFileWithoutFollowing($scriptPath, 1048576);
    if ($scriptWasRead && preg_match('/^SCRIPT_VERSION="([^"]+)"/m', $scriptContents, $versionMatches)) $detectedVersion = $versionMatches[1];
    if ($scriptWasRead && $ScriptStatus === 'mismatched') $updatedContents = preg_replace('/^DisabledByCore="FALSE"/m', 'DisabledByCore="TRUE"', $scriptContents, 1);
    else if ($scriptWasRead) $updatedContents = preg_replace('/^DisabledByCore="TRUE"/m', 'DisabledByCore="FALSE"', $scriptContents, 1);
    if (!is_string($updatedContents) or $updatedContents === $scriptContents) {
      $ScriptStatus = 'failed';
      warningEntry('The bootstrap script could not be read back to change it. '.$readNotice); }
    else {
      list ($scriptWasWritten, $writeNotice) = writeFileWithoutFollowing($scriptPath, $updatedContents, 0644);
      if (!$scriptWasWritten) {
        $ScriptStatus = 'failed';
        warningEntry('The bootstrap script reports '.($detectedVersion === '' ? 'no version' : $detectedVersion).' & could not be changed. '.$writeNotice.' Remove it by hand.'); }
      else if ($ScriptStatus === 'mismatched') {
        $ScriptIsValid = TRUE;
        $ScriptStatus = 'disabled';
        warningEntry('The bootstrap script reports '.($detectedVersion === '' ? 'no version' : $detectedVersion).' & does not match this core. It has been disabled. Reinstall the script from the matching release.'); }
      else {
        $ScriptIsValid = TRUE;
        $ScriptStatus = 'repaired';
        warningEntry('The bootstrap script now matches this core & was enabled again.'); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $scriptPath, $scriptContents, $readNotice, $writeNotice, $detectedVersion, $updatedContents, $versionMatches, $scriptWasRead, $scriptWasWritten);
  return array($ScriptIsValid, $ScriptStatus); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to describe a bootstrap script status for an operator.
// / Accepts the status word. Returns the word to display & a sentence, in that order.
// / A state that needs nothing is lower case & a state that needs attention is upper case, as
// / for every other finding, so findingIsBenign() & the eye agree about which is which.
function describeBootstrapStatus($scriptStatus) {
  // / Set variables.
  global $RequiredConfigScript, $EnableMemoryProtection;
  $DisplayStatus = 'UNKNOWN';
  $StatusDetail = 'This status is not one the Engine knows.';
  $requiredText = '';
  $requiredText = 'This core requires '.ltrim(trim((string)$RequiredConfigScript), 'vV').'.';
  if ($scriptStatus === 'absent') {
    $DisplayStatus = 'ok';
    $StatusDetail = 'Not installed.'; }
  else if ($scriptStatus === 'ok') {
    $DisplayStatus = 'ok';
    $StatusDetail = 'Matches this core.'; }
  else if ($scriptStatus === 'disabled') {
    $DisplayStatus = 'disabled';
    $StatusDetail = 'It does not match this core & refuses to run. '.$requiredText.' Reinstall it from the matching release.'; }
  else if ($scriptStatus === 'repaired') {
    $DisplayStatus = 'repaired';
    $StatusDetail = 'Matches this core again. Enabled.'; }
  else if ($scriptStatus === 'mismatched') {
    $DisplayStatus = 'MISMATCHED';
    $StatusDetail = 'It does not match this core & would still run. '.$requiredText.' Run -fp as root to disable it.'; }
  else if ($scriptStatus === 'stale') {
    $DisplayStatus = 'STILL DISABLED';
    $StatusDetail = 'It matches this core & is still disabled. Run -fp as root to enable it.'; }
  else if ($scriptStatus === 'unmarked') {
    $DisplayStatus = 'UNMARKED';
    $StatusDetail = 'It does not match this core & carries no DisabledByCore line to set. '.$requiredText.' Remove it by hand.'; }
  else if ($scriptStatus === 'unreadable') {
    $DisplayStatus = 'UNREADABLE';
    $StatusDetail = 'It could not be read. Check it by hand.'; }
  else if ($scriptStatus === 'failed') {
    $DisplayStatus = 'FAILED';
    $StatusDetail = 'It could not be changed. Root\'s log says why.'; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $requiredText, $scriptStatus);
  return array($DisplayStatus, $StatusDetail); }
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
    'coreManager.php' => 'v4.1.3',
    'resourceManager.php' => 'v4.2.0',
    'workerManager.php' => 'v4.0.8',
    'requestManager.php' => 'v4.1.3',
    'authenticationManager.php' => 'v4.2.0');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $AcceptedManagers is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection);
  return $AcceptedManagers; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report the managers this Engine runs as root, & only as root.
// / Accepts nothing. Returns an array of file name to required version.
// / A root manager is pinned exactly like the others & kept off getAcceptedManagers() on
// / purpose. That list is what the listener dispatches from, & a root role the listener could
// / reach is exactly what the Environment Manager's design refuses to have. Nothing but
// / loadEngineRootManager() reads this list, & only a root command calls that.
// / Raise the Engine version whenever this list changes, for the reason given above.
function getAcceptedRootManagers() {
  // / Set variables.
  global $EnableMemoryProtection;
  $AcceptedRootManagers = array();
  $AcceptedRootManagers = array(
    'environmentManager.php' => 'v4.0.8');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $AcceptedRootManagers is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection);
  return $AcceptedRootManagers; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify & load one manager subcomponent.
// / Accepts the file name. Returns an availability boolean & the detected version.
// / A manager is judged before it is loaded, never after, because a manager built for
// / another Engine may accept an argument this one has dropped.
// / A manager that is absent or mismatched is a warning rather than a halt. What is lost
// / is the role that manager serves & the loss is named rather than guessed at.
// / The path uses DIRECTORY_SEPARATOR rather than $DirSep, because a web request asks for the Authentication Manager before verifyGlobals sets $DirSep.
function loadEngineManager($managerFileName) {
  // / Set variables.
  global $InstLoc, $CoreLoaded, $EnableMemoryProtection;
  $ManagerIsAvailable = FALSE;
  $ManagerVersion = '';
  $acceptedManagers = array();
  $managerPath = $requiredVersion = '';
  $acceptedManagers = getAcceptedManagers();
  if (!isset($acceptedManagers[(string)$managerFileName])) warningEntry('The manager subcomponent '.$managerFileName.' is not one this Engine accepts. It was not loaded.');
  else {
    $requiredVersion = ltrim((string)$acceptedManagers[(string)$managerFileName], 'vV');
    $managerPath = rtrim((string)$InstLoc, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'Engine'.DIRECTORY_SEPARATOR.'Managers'.DIRECTORY_SEPARATOR.(string)$managerFileName;
    // / readComponentVersion is a kernel service & reads the version without executing the
    // / file, which is the whole reason a mismatched manager can be refused safely.
    $ManagerVersion = readComponentVersion('Engine'.DIRECTORY_SEPARATOR.'Managers'.DIRECTORY_SEPARATOR.(string)$managerFileName, 'ManagerVersion');
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
// / A function to verify & load one manager this Engine runs as root.
// / Accepts the file name. Returns an availability boolean & the detected version.
// / It judges a manager the way loadEngineManager() does, before it is loaded, against
// / getAcceptedRootManagers() rather than the list the listener dispatches from.
// / $CoreLoaded is declared global, because the manager is required inside this function & its guard reads $CoreLoaded at file scope.
function loadEngineRootManager($managerFileName) {
  // / Set variables.
  global $InstLoc, $DirSep, $CoreLoaded, $RunningAsRoot, $EnableMemoryProtection;
  $ManagerIsAvailable = FALSE;
  $ManagerVersion = '';
  $acceptedManagers = array();
  $managerPath = $requiredVersion = '';
  $acceptedManagers = getAcceptedRootManagers();
  if (!$RunningAsRoot) warningEntry('The root manager '.$managerFileName.' was asked for by an account other than root. It was not loaded.');
  else if (!isset($acceptedManagers[(string)$managerFileName])) warningEntry('The root manager '.$managerFileName.' is not one this Engine accepts. It was not loaded.');
  else {
    $requiredVersion = ltrim((string)$acceptedManagers[(string)$managerFileName], 'vV');
    $managerPath = rtrim((string)$InstLoc, $DirSep).$DirSep.'Resources'.$DirSep.'Engine'.$DirSep.'Managers'.$DirSep.(string)$managerFileName;
    // / The version is read without executing the file, so a mismatched one is refused unrun.
    $ManagerVersion = readComponentVersion('Engine'.$DirSep.'Managers'.$DirSep.(string)$managerFileName, 'ManagerVersion');
    if (!file_exists($managerPath)) warningEntry('The root manager '.$managerFileName.' is not installed. It cannot run.');
    else if ($ManagerVersion === '') warningEntry('The root manager '.$managerFileName.' reports no version. An unknown build cannot be cleared & it was not loaded.');
    else if (ltrim((string)$ManagerVersion, 'vV') !== $requiredVersion) warningEntry('The root manager '.$managerFileName.' reports '.$ManagerVersion.' & this Engine requires '.$requiredVersion.'. It was not loaded.');
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
// / They sit beside cleanDataLocation(), which depends on them.
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
// / Returns FALSE when the folder holds anything, when it cannot be read, or when the path is not a folder.
// / Every directory contains a . and a .. entry, so both are discarded before testing.
function is_dir_empty($dir) {
  // / Set variables.
  global $EnableMemoryProtection;
  $Check = TRUE;
  $listing = $contents = array();
  // / Make sure the selected directory is actually a directory.
  if (is_dir($dir)) {
    // / Gather the contents of the directory, discarding the two entries every directory has.
    // / A folder that cannot be read is not known to be empty. scandir() returns FALSE for one,
    // / & handing FALSE to array_diff() is a fatal error in PHP 8.
    $listing = @scandir($dir);
    if (!is_array($listing)) $Check = FALSE;
    else $contents = array_diff($listing, array('.', '..'));
    // / Anything left over means the directory holds something.
    if (!empty($contents)) $Check = FALSE; }
  // / A path that is not a directory at all must never be reported as an empty one.
  else $Check = FALSE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dir, $listing, $contents);
  return $Check; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to clean a selection of files.
// / Recursively deletes files, & is dangerous enough to handle with care.
// / It refuses anything outside a root the caller named.
// / Both sides of the comparison pass through realpath() first, so a path containing .. cannot walk out of an approved root.
// / A wrong variable handed to it therefore does nothing & returns FALSE.
// / The second argument is the list of roots this caller may clean under, so the cleaner knows no application's directories.
// / The path a caller hands over is sanitized here, once, & cleanFolderTree() does the walk.
function cleanFiles($path, $allowedRoots) {
  // / Set variables.
  global $EnableMemoryProtection;
  $CleanSuccess = $variableIsSanitized = FALSE;
  list ($path, $variableIsSanitized) = sanitize($path, FALSE);
  if ($variableIsSanitized) $CleanSuccess = cleanFolderTree($path, $allowedRoots);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $variableIsSanitized, $path, $allowedRoots);
  return $CleanSuccess; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to empty & remove a folder, & every folder inside it, within the roots a caller named.
// / Accepts the folder & the roots it may clean under, in that order. Returns TRUE when nothing is left of the folder.
// / It is cleanFiles() without the sanitizing. cleanFiles() calls it once with the path it was handed,
// / & it calls itself for every folder it finds, by the name that folder actually has.
// / A name a folder listing returns is the folder's own, & sanitizing it would name a different folder or none.
// / Every folder is still resolved & held to the roots, so a name cannot lead anywhere the roots do not.
function cleanFolderTree($path, $allowedRoots) {
  // / Set variables.
  // / Every filesystem lists these two & neither is ever a file somebody uploaded.
  global $DirSep, $EnableMemoryProtection;
  $defaultApps = array('.', '..');
  $CleanSuccess = $pathCheck = $pathIsContained = FALSE;
  $loopCheck = TRUE;
  // / $allowedRoots is a PARAMETER & is not initialized here. Listing it beside the locals
  // / emptied the caller's authorization before a single root was read, so nothing was ever
  // / contained & nothing was ever cleaned. The directory then failed its emptiness check &
  // / the caller reported a failure on work that had succeeded.
  $dirListing = $dirContents = array();
  $dirEntry = $childPath = $realPath = $realRoot = $allowedRoot = '';
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
      // / A path is contained by a root it is EQUAL to as well as one it sits beneath.
      // / Requiring a separator meant a caller authorizing exactly the directory it wanted
      // / cleaned was refused, which is the most obvious way to call this function.
      if ($realRoot !== FALSE && ($realPath === $realRoot or strpos($realPath, $realRoot.$DirSep) === 0)) {
        $pathIsContained = TRUE;
        break; } } }
  // / Make sure the selected directory is contained & actually a directory.
  if ($pathIsContained && is_dir($path)) {
    // / Iterate through each file object in the directory.
    // / A folder that cannot be read is a failure rather than a fault. scandir() returns FALSE for one,
    // / & handing FALSE to array_diff() is a fatal error in PHP 8, which would end the listener's sweep along with it.
    $dirListing = @scandir($path);
    if (!is_array($dirListing)) $loopCheck = FALSE;
    else $dirContents = array_diff($dirListing, array('..', '.'));
    foreach ($dirContents as $dirEntry) {
      // / Build the full path to this child ONCE. Every check below refers to the CHILD,
      // / never to the parent. Testing the parent here would choose the wrong branch.
      $childPath = $path.$DirSep.$dirEntry;
      // / Protected file objects are never touched at any depth.
      if (in_array(basename($childPath), $defaultApps)) continue;
      // / A link of any kind is removed without being followed, & this test comes first because
      // / is_file() & is_dir() both follow a link. A link to a directory would otherwise be
      // / recursed into & walk this cleaner out of the approved tree, & a dangling link is
      // / neither a file nor a directory, so it would be skipped & then block the parent rmdir.
      if (is_link($childPath)) @unlink($childPath);
      // / If the selected file object is a file, delete it.
      elseif (is_file($childPath)) @unlink($childPath);
      // / If the selected file object is an empty directory, remove it outright.
      elseif (is_dir($childPath) && is_dir_empty($childPath)) @rmdir($childPath);
      // / If the selected file object is a directory with contents, recurse into it.
      // / A failure anywhere below must propagate up, so $loopCheck is never reset to TRUE here.
      elseif (is_dir($childPath)) {
        if (!cleanFolderTree($childPath, $allowedRoots)) $loopCheck = FALSE; }
      // / A file object that is neither a link, a file nor a directory is a FIFO, a socket or a
      // / device node. It is removed rather than skipped, so no such object can survive the sweep
      // / & leave the parent directory un-removable.
      else @unlink($childPath); }
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
  purgeSensitiveMemory($EnableMemoryProtection, $defaultApps, $path, $dirListing, $dirContents, $dirEntry, $childPath, $realPath, $realRoot, $allowedRoot, $allowedRoots, $pathCheck, $pathIsContained, $loopCheck);
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
// / A function to ask the listener which data location this session must use.
// / Accepts the daily hash & the session hash, in that order.
// / Returns an absolute path as a string, ALWAYS.
// / The managers hold the session map, so the answer is the same for every front end that
// / shares this secret. With no listener the configured location is used, which is exactly
// / how a standalone installation has always behaved.
// / A command line invocation never asks. Administrative work operates on the configured
// / location & must not stall waiting on a listener that may not be running.
function requestConvertLoc($dailyHash, $sessionHash) {
  // / Set variables.
  global $ResourceAwarenessActive, $RunningFromCLI, $ManagerSocketTimeout, $DirSep, $Verbose, $EnableMemoryProtection, $PrimaryConvertLoc, $AdditionalConvertLocs, $ProtectedRootDirs, $ConvertLoc;
  $ResolvedConvertLoc = '';
  $requestPayload = $replyPayload = array();
  $messageWasDelivered = FALSE;
  $answerSource = 'config.php';
  // / The fallback is discovery, not the configured location.
  // / resolveConvertLoc reads the pool from disk & returns the location holding this session before it distributes anything.
  // / An unanswered request therefore degrades to the correct answer rather than to a plausible one.
  $ResolvedConvertLoc = rtrim(resolveDataLocation($dailyHash, $sessionHash, $PrimaryConvertLoc, $ConvertLoc, $AdditionalConvertLocs, $ProtectedRootDirs), $DirSep);
  if (!$ResourceAwarenessActive) $answerSource = 'discovery, no listener component';
  else if ($RunningFromCLI) $answerSource = 'discovery, command line context';
  else {
    $requestPayload = array('RequestType' => 'convertloc', 'DailyHash' => (string)$dailyHash, 'SessionHash' => (string)$sessionHash, 'WorkerPid' => getmypid());
    list ($messageWasDelivered, $replyPayload) = sendManagerMessage(buildManagerSocketPath('request-manager'), $requestPayload, 'worker', (int)$ManagerSocketTimeout * 3);
    // / An unanswered request is a listener that is slow or absent, not an instruction to
    // / move this session somewhere else. The configured location is the safe answer.
    // / A location discovered on disk is not a guess. It is where this session's files are.
    if (!$messageWasDelivered) { warningEntry('The Core Manager listener did not answer a data location request. Using '.$ResolvedConvertLoc.', located by searching the configured pool for this session.'); $answerSource = 'discovery, listener silent'; }
    else if (!isset($replyPayload['ConvertLoc']) or !is_string($replyPayload['ConvertLoc']) or trim($replyPayload['ConvertLoc']) === '') { warningEntry('The Core Manager listener returned no usable data location. Using '.$ResolvedConvertLoc.', located by searching the configured pool for this session.'); $answerSource = 'discovery, listener unusable'; }
    else {
      $ResolvedConvertLoc = rtrim(trim($replyPayload['ConvertLoc']), $DirSep);
      $answerSource = 'listener'; } }
  if ($Verbose) logEntry('Data Location: '.$ResolvedConvertLoc.', Source: '.$answerSource.'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $ResolvedConvertLoc is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $requestPayload, $replyPayload, $messageWasDelivered, $answerSource, $dailyHash, $sessionHash);
  return $ResolvedConvertLoc; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report whether a path is one of the configured data locations.
// / Accepts the absolute path to test.
// / Returns TRUE only when the path appears in the configured set.
// / A scheduled sweep uses this so it can clean a location this worker is not using, while
// / an arbitrary path is still refused.
function convertLocIsConfigured($candidatePath) {
  // / Set variables.
  global $PrimaryConvertLoc, $ConvertLoc, $AdditionalConvertLocs, $DirSep, $EnableMemoryProtection;
  $PathIsConfigured = FALSE;
  $convertLocPool = $poolEntry = array();
  $cleanCandidate = rtrim(trim((string)$candidatePath), $DirSep);
  $convertLocPool = enumerateDataLocations($PrimaryConvertLoc, $ConvertLoc, $AdditionalConvertLocs);
  if ($cleanCandidate !== '') {
    foreach ($convertLocPool as $poolEntry) { if ($poolEntry['Path'] === $cleanCandidate) $PathIsConfigured = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $convertLocPool, $poolEntry, $cleanCandidate, $candidatePath);
  return $PathIsConfigured; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to enumerate every data location this installation may use.
// / Accepts no arguments.
// / Returns an array of entries, each carrying a Path & a Type, with the primary first.
// / The primary is the data location the application declares, & is always present.
// / An entry that is not an array of a path & a type is skipped & reported.
// / A duplicate path is skipped, because the same location twice would double its share.
function enumerateDataLocations($primaryPath, $activePath, $additionalLocations) {
  // / Set variables.
  global $DirSep, $EnableMemoryProtection;
  $DataLocationPool = array();
  $additionalEntry = $seenPaths = array();
  $entryPath = $entryType = '';
  // / $primaryPath is a parameter & is deliberately not initialized here, because blanking it would discard the configured primary.
  // / $activePath is $ConvertLoc, which a web request has already reassigned to the pool location the listener handed out.
  // / The caller passes the configured primary & the location this session settled on.
  // / The configured one wins, because the active one would otherwise nominate itself as the primary of a pool it only belongs to.
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
// / An established session is discovered, not chosen.
// / A session that already holds a directory somewhere keeps that location for its whole life.
// / Choosing again on a later request would send the worker looking for the user's files where they are not.
// / Only a session with no directory anywhere is distributed, & only then does type matter.
// / A location marked leastactive puts the whole pool into least active selection.
// / Otherwise the pool is distributed by session identifier, which needs no shared counter between front ends.
// / A location marked redundant is a standby, which takes a session only when every other location is unusable.
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
// / Only what the sweeping account owns is swept.
// / The sweep runs as the web server account, which made every session & cannot remove what another account put there.
// / Such a folder is left, is not a failure, & -fp names it.
function cleanDataLocation($dataLoc, $locationName, $deleteThreshold, $protectedRootDirs, $authorizedPaths) {
  // / Set variables.
  global $DirSep, $Verbose, $EnableMemoryProtection;
  $LocationDeepCleaned = $cleanAuthorized = FALSE;
  $CleanedLocation = TRUE;
  // / Every filesystem lists these two & neither is ever a session.
  $defaultApps = array('.', '..');
  $dailyDirs = $sessionDirs = $entryStatus = array();
  $dailyDir = $sessionDir = $dailyPath = $sessionPath = '';
  $now = time();
  $sweeperUid = function_exists('posix_geteuid') ? (int)posix_geteuid() : -1;
  // / Determine whether this clean operation is being requested on a valid target.
  // / The caller supplies the paths it may clean, & the target must be one of them, so an arbitrary path is refused.
  // / $locationName is only a label for the messages below.
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
      // / A day another account made is not this account's to sweep.
      $entryStatus = @lstat($dailyPath);
      if ($sweeperUid >= 0 && (!is_array($entryStatus) or (int)$entryStatus['uid'] !== $sweeperUid)) {
        if ($Verbose) logEntry('The sweep left '.$dailyPath.', which the account sweeping does not own.');
        continue; }
      $sessionDirs = array_diff(scandir($dailyPath), array('..', '.'));
      // / Iterate through each session folder inside this day.
      foreach ($sessionDirs as $sessionDir) {
        if (in_array($sessionDir, $defaultApps)) continue;
        $sessionPath = $dailyPath.$DirSep.$sessionDir;
        if (!is_dir($sessionPath)) continue;
        // / So is a session another account made.
        $entryStatus = @lstat($sessionPath);
        if ($sweeperUid >= 0 && (!is_array($entryStatus) or (int)$entryStatus['uid'] !== $sweeperUid)) {
          if ($Verbose) logEntry('The sweep left '.$sessionPath.', which the account sweeping does not own.');
          continue; }
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
              // / A link of any kind is removed by its own path, never its target, & this comes
              // / first because getRealPath() returns FALSE for a dangling link & isDir() follows
              // / a link. Without this a planted link survives the sweep & blocks the parent rmdir.
              if ($fileObject->isLink()) { @unlink($fileObject->getPathname()); continue; }
              // / A non-link whose real path will not resolve is nothing this sweep can act on.
              if ($realPath === FALSE) continue;
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
  purgeSensitiveMemory($EnableMemoryProtection, $authorizedPaths, $defaultApps, $protectedRootDirs, $dailyDirs, $dailyDir, $dailyPath, $sessionDirs, $sessionDir, $sessionPath, $entryStatus, $sweeperUid, $now, $dataLoc, $locationName, $deleteThreshold, $cleanAuthorized, $directoryIterator, $iterator, $fileObject, $realPath);
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
// / A function to take a resource budget for an expensive operation & arm its return.
// / Accepts a human readable operation name used only for logging.
// / Returns TRUE when the operation may proceed.
// /
// / Every expensive operation takes a budget, not only conversion.
// / Tesseract on a large PDF, 7-Zip on a large folder & a ClamAV scan are each as heavy as a conversion.
// / An operation that takes nothing also counts for nothing, & a saturated host would report itself idle.
// / Every operation takes the same $DefaultConversionCost & $DefaultExpectedRuntime, however many an application has.
// / No measurement supports weighting them separately, & the per conversion limit table already gives finer control.
// / It fails open exactly as requestConversionBudget() does, which refuses instead when resource awareness is required.
function takeOperationBudget($operationName) {
  // / Set variables.
  global $BudgetToken, $BudgetTokenIsReleased, $BudgetReleaseIsArmed, $DefaultConversionCost, $DefaultExpectedRuntime, $Verbose, $EnableMemoryProtection;
  $BudgetWasApproved = FALSE;
  list ($BudgetWasApproved, $BudgetToken) = requestConversionBudget($DefaultConversionCost, $DefaultExpectedRuntime);
  // / Every message names the operation the same way, so $operationName is a bare noun &
  // / the sentence supplies the rest. A name of 'OCR operation' rendered 'The OCR operation
  // / operation holds', & an article written into the sentence rendered 'A OCR'.
  if (!$BudgetWasApproved) warningEntry('The '.$operationName.' operation was refused because the server is at its resource budget.');
  else {
    // / The token is out from here. Register its return before anything can die.
    // / Every one of these operations has a fatal exit between taking a budget & returning
    // / it, so the release cannot live only at the bottom of the caller's block.
    $BudgetTokenIsReleased = FALSE;
    // / The return is armed once a process, because a workflow worker takes a budget for every file it converts.
    // / The guard inside releaseBudgetOnShutdown() returns whichever token is out when the process ends.
    if (empty($BudgetReleaseIsArmed)) {
      register_shutdown_function('releaseBudgetOnShutdown');
      $BudgetReleaseIsArmed = TRUE; }
    if ($Verbose) logEntry('The '.$operationName.' operation holds budget token '.$BudgetToken.'.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $operationName);
  return $BudgetWasApproved; }
// / -----------------------------------------------------------------------------------
// / A function to return a conversion budget token however the request ends.
// / Registered as a shutdown handler the moment a budget is approved, & also called
// / directly on the normal completion path. The guard makes it safe to run twice.
// /
// / A conversion has three fatal exits between taking a budget & returning it.
// / They are error 21, & errors 5002 & 5003 from the scan of the result.
// / Each halts through quickDie, so a release placed after the conversion would never be reached.
// / An unreturned token waits for findStaleWorkers(), which on a small host refuses the next conversion meanwhile.
// / A failed conversion is a normal outcome, so it returns what it borrowed on the way out, & the reaper stays a fallback.
// / PHP runs a registered shutdown function after die(), so this is reached from a fatal exit as well as a clean one.
// / It writes to the log & to the manager socket only, because the connection to the user is already closed.
function releaseBudgetOnShutdown() {
  // / Set variables.
  global $BudgetToken, $BudgetTokenIsReleased;
  $BudgetWasReleased = TRUE;
  // / Nothing is done when the token was already returned, or there was never one to return.
  // / The flag is set before the attempt, not after.
  // / A release that fails has already warned & handed the token to the reaper, so a second attempt would warn again.
  if (empty($BudgetTokenIsReleased) && isset($BudgetToken) && (string)$BudgetToken !== '') {
    $BudgetTokenIsReleased = TRUE;
    $BudgetWasReleased = releaseConversionBudget($BudgetToken); }
  return $BudgetWasReleased; }
// / -----------------------------------------------------------------------------------
// / A function to return the resource budget an operation took.
// / Accepts a human readable operation name used only for logging.
// / Returns TRUE when the release was acknowledged, or when there was nothing to release.
// / Safe to call when the budget was refused & safe to call twice, because the guard inside
// / releaseBudgetOnShutdown() is what decides whether there is anything to do.
function giveBackOperationBudget($operationName) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $BudgetWasReleased = releaseBudgetOnShutdown();
  // / releaseConversionBudget() has already warned if it could not deliver, so a failure
  // / here is noted at the normal activity tier only.
  if ($Verbose && !$BudgetWasReleased) logEntry('The '.$operationName.' operation budget token was not confirmed as returned. The reaper remains as the fallback.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $operationName);
  return $BudgetWasReleased; }
// / -----------------------------------------------------------------------------------
// / The conversion budget clients.
// / Asking for a budget, extending one & handing it back are one conversation, so they sit with the protocol they speak.
// / A manager runs as its own process & is never loaded in a request, so they cannot live in one.
// / Nothing in them knows what a conversion is. They send a message & read a reply.
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to request permission to consume resources before a conversion begins.
// / Accepts the conversion cost & the expected runtime in seconds.
// / Returns an approval boolean & the issued budget token, in that order.
// / This FAILS OPEN. When resource awareness is unavailable the request is approved & the
// / core behaves exactly as it did before this component existed.
// / An application that requires resource awareness fails closed instead when the listener does
// / not answer, because a conversion nothing accounted for is what that setting refuses.
function requestConversionBudget($conversionCost, $expectedRuntime) {
  // / Set variables.
  global $ResourceAwarenessActive, $ManagerSocketTimeout, $EffectiveConversionLimits, $EnableMemoryProtection, $Verbose, $ConvertLoc;
  $BudgetWasApproved = FALSE;
  $BudgetToken = '';
  $requestPayload = $replyPayload = array();
  $messageWasDelivered = $awarenessIsRequired = FALSE;
  $requestSocket = '';
  $awarenessIsRequired = (bool)applicationSetting('RequireResourceAwareness', FALSE);
  if (!$ResourceAwarenessActive) $BudgetWasApproved = TRUE;
  else {
    $requestSocket = buildManagerSocketPath('request-manager');
    // / The data location is the one this session writes to, so the listener can refuse work on a disk within its reserve.
    $requestPayload = array(
      'RequestType' => 'budget',
      'ConversionCost' => (int)$conversionCost,
      'ExpectedRuntime' => (int)$expectedRuntime,
      'ConvertLoc' => (string)$ConvertLoc,
      'QuotaOwner' => storageQuotaOwner(),
      'WorkerPid' => getmypid());
    // / The worker waits longer than the chain it is waiting on. The request crosses three
    // / processes & each inner hop waits less than the one outside it, so a slow manager
    // / times out inside rather than leaving the worker with half an answer.
    list ($messageWasDelivered, $replyPayload) = sendManagerMessage($requestSocket, $requestPayload, 'worker', (int)$ManagerSocketTimeout * 3);
    // / A listener that cannot be reached must not stop a conversion that would have run, unless
    // / the application requires resource awareness.
    if (!$messageWasDelivered) {
      warningEntry('The Core Manager listener did not answer a budget request. '.($awarenessIsRequired ? 'Resource awareness is required, so the conversion was refused.' : 'Proceeding without resource awareness.'));
      $BudgetWasApproved = !$awarenessIsRequired; }
    // / A delivered message with no usable reply is a listener that is slow or broken rather
    // / than a budget that declined. Only an explicit answer refuses a conversion, unless the
    // / application requires resource awareness.
    else if (!isset($replyPayload['Approved'])) {
      warningEntry('The Core Manager listener returned no usable answer to a budget request. '.($awarenessIsRequired ? 'Resource awareness is required, so the conversion was refused.' : 'Proceeding without resource awareness.'));
      $BudgetWasApproved = !$awarenessIsRequired; }
    else if ($replyPayload['Approved'] === TRUE) {
      $BudgetWasApproved = TRUE;
      $BudgetToken = isset($replyPayload['BudgetToken']) ? (string)$replyPayload['BudgetToken'] : '';
      // / The listener scales the configured maxima against current load & hands back the
      // / table this session converts under. It is used for every file in this request.
      if (isset($replyPayload['Limits']) && is_array($replyPayload['Limits'])) $EffectiveConversionLimits = $replyPayload['Limits']; 
      if ($Verbose) logEntry('Worker '.getmypid().' was granted budget token '.$BudgetToken.'.'); }
    else logEntry('A conversion was refused by the resource budget. '.(isset($replyPayload['Reason']) && $replyPayload['Reason'] !== '' ? $replyPayload['Reason'] : 'No reason was supplied.')); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $requestPayload, $replyPayload, $messageWasDelivered, $awarenessIsRequired, $requestSocket, $conversionCost, $expectedRuntime);
  return array($BudgetWasApproved, $BudgetToken); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to ask the listener whether a write that takes no budget may go ahead.
// / Accepts the data location the write goes to & the bytes it would write, in that order.
// / Returns whether it may & the reason it may not, in that order.
// / An upload takes no budget, because it is the network that limits it & not the processor.
// / It still writes, so it asks before it does, & the listener refuses one that would leave the disk within its reserve.
// / This fails open, as a budget request does. With no listener nothing is measured & every write may go ahead.
// / An answer that measured nothing is no answer, so a listener started before storage requests refuses nothing either.
// / A command line invocation never asks, because administrative work must not wait on a listener that may not be running.
// / An application that requires resource awareness fails closed instead when the listener does not answer.
function requestStorageSpace($dataLocation, $requestedBytes) {
  // / Set variables.
  global $ResourceAwarenessActive, $RunningFromCLI, $ManagerSocketTimeout, $EnableMemoryProtection, $Verbose;
  $StorageWasGranted = FALSE;
  $StorageReason = '';
  $requestPayload = $replyPayload = array();
  $messageWasDelivered = $awarenessIsRequired = FALSE;
  $awarenessIsRequired = (bool)applicationSetting('RequireResourceAwareness', FALSE);
  if (!$ResourceAwarenessActive or $RunningFromCLI) $StorageWasGranted = TRUE;
  else {
    $requestPayload = array('RequestType' => 'storage', 'ConvertLoc' => (string)$dataLocation, 'RequestedBytes' => max(0, (int)$requestedBytes), 'QuotaOwner' => storageQuotaOwner(), 'WorkerPid' => getmypid());
    list ($messageWasDelivered, $replyPayload) = sendManagerMessage(buildManagerSocketPath('request-manager'), $requestPayload, 'worker', (int)$ManagerSocketTimeout * 3);
    if (!$messageWasDelivered or !isset($replyPayload['Approved'])) {
      warningEntry('The Core Manager listener did not answer a storage request. '.($awarenessIsRequired ? 'Resource awareness is required, so the write was refused.' : 'Proceeding without resource awareness.'));
      $StorageWasGranted = !$awarenessIsRequired;
      if ($awarenessIsRequired) $StorageReason = 'The listener did not answer, & resource awareness is required.'; }
    // / Every answer the Resource Manager gives a storage request names the disk's state.
    // / An answer that names none measured nothing, & is treated as no answer.
    // / A listener still running a release from before storage requests answers this way, because its Request Manager refuses a type it does not know.
    // / A drop in leaves that listener running until it is restarted, & an upload must not be refused for it meanwhile.
    else if (!isset($replyPayload['StorageState'])) {
      warningEntry('The Core Manager listener answered a storage request without measuring the disk. A listener still running a release from before storage requests answers this way until it is restarted. It said: '.((isset($replyPayload['Reason']) && is_string($replyPayload['Reason']) && $replyPayload['Reason'] !== '') ? $replyPayload['Reason'] : 'No reason was supplied.').' '.($awarenessIsRequired ? 'Resource awareness is required, so the write was refused.' : 'Proceeding without resource awareness.'));
      $StorageWasGranted = !$awarenessIsRequired;
      if ($awarenessIsRequired) $StorageReason = 'The listener did not measure the disk, & resource awareness is required.'; }
    else if ($replyPayload['Approved'] === TRUE) {
      $StorageWasGranted = TRUE;
      if ($Verbose) logEntry('Worker '.getmypid().' was granted storage for '.max(0, (int)$requestedBytes).' byte(s).'); }
    else $StorageReason = (isset($replyPayload['Reason']) && is_string($replyPayload['Reason']) && $replyPayload['Reason'] !== '') ? $replyPayload['Reason'] : 'No reason was supplied.'; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $requestPayload, $replyPayload, $messageWasDelivered, $awarenessIsRequired, $dataLocation, $requestedBytes);
  return array($StorageWasGranted, $StorageReason); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the owner a write is charged to.
// / Accepts no arguments.
// / Returns the owner, or an empty string for a request nobody has signed in to.
// / The owner is the signed in account's record name, which names nobody, & the Authentication Core sets it when a key signs a request in.
// / The listener charges an owner's writes against their quota, & charges an anonymous write to nobody.
function storageQuotaOwner() {
  // / Set variables.
  global $SignedInAccount;
  $QuotaOwner = (isset($SignedInAccount) && is_array($SignedInAccount) && isset($SignedInAccount['Name']) && is_string($SignedInAccount['Name'])) ? $SignedInAccount['Name'] : '';
  return $QuotaOwner; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether this installation has accounts.
// / Accepts nothing. Returns TRUE where this application offers them & its administrator turned them on.
// / A capability a missing Engine configuration leaves unset counts as on, as every other capability does, & the administrator still decides.
function authenticationIsOn() {
  // / Set variables.
  global $EngineEnableAuthentication;
  $AuthenticationIsOn = ((!isset($EngineEnableAuthentication) or $EngineEnableAuthentication === TRUE) && applicationSetting('EnableAuthentication', FALSE) === TRUE);
  return $AuthenticationIsOn; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to load the Authentication Core & the Email Core, where this installation has accounts.
// / Accepts whether to load them where accounts are off, as --account does so an administrator may prepare accounts first.
// / Returns whether the Authentication Core is loaded & whether the Email Core is, in that order.
// / Each is pinned exactly by the application, as every Engine core is, & verifyCoreComponent() names what was refused & why.
// / The Authentication Core goes on without the Email Core, because signing in needs no email. It warns every time an email is not sent.
function loadAuthenticationCores($loadWhenOff) {
  // / Set variables.
  global $RequiredAuthenticationCoreVersion, $RequiredEmailCoreVersion, $AuthenticationCoreActive, $EmailCoreActive, $EnableMemoryProtection;
  $authenticationVersion = $emailVersion = '';
  if (!isset($AuthenticationCoreActive) or !is_bool($AuthenticationCoreActive)) $AuthenticationCoreActive = FALSE;
  if (!isset($EmailCoreActive) or !is_bool($EmailCoreActive)) $EmailCoreActive = FALSE;
  if (($loadWhenOff === TRUE or authenticationIsOn()) && !$AuthenticationCoreActive) {
    list ($AuthenticationCoreActive, $authenticationVersion) = verifyCoreComponent('Authentication Core', 'Engine'.DIRECTORY_SEPARATOR.'Cores'.DIRECTORY_SEPARATOR.'authenticationCore.php', 'AuthenticationCoreVersion', (string)$RequiredAuthenticationCoreVersion);
    list ($EmailCoreActive, $emailVersion) = verifyCoreComponent('Email Core', 'Engine'.DIRECTORY_SEPARATOR.'Cores'.DIRECTORY_SEPARATOR.'emailCore.php', 'EmailCoreVersion', (string)$RequiredEmailCoreVersion); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $authenticationVersion, $emailVersion, $loadWhenOff);
  return array($AuthenticationCoreActive, $EmailCoreActive); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to let the Engine answer a request before the application serves it.
// / Accepts nothing. Returns an empty string when the application serves it, page when the Engine names a page of its own to draw in place
// / of the application's, or answered when the Engine wrote the whole answer, in which case the application draws nothing & runs nothing.
// / Only the Authentication Core answers anything. Where accounts are off, every request is the application's, exactly as it was.
// / $EnginePageState is what a page may show about the Engine's part of a request, & says Enabled FALSE where accounts are off.
function answerEngineRequest() {
  // / Set variables.
  global $RunningFromCLI, $TransportedWorkerIdentity, $EnginePageState, $EnableMemoryProtection;
  $EngineAnswer = '';
  $authenticationIsLoaded = $emailIsLoaded = FALSE;
  $EnginePageState = array('Enabled' => FALSE, 'SignedIn' => FALSE, 'Username' => '', 'Role' => '', 'Page' => '');
  list ($authenticationIsLoaded, $emailIsLoaded) = loadAuthenticationCores(FALSE);
  if ($authenticationIsLoaded && function_exists('adoptAuthenticationIdentity')) $EngineAnswer = adoptAuthenticationIdentity();
  // / A workflow worker continues as the signed in user who started it, where it was handed one.
  if ($authenticationIsLoaded && $RunningFromCLI === TRUE && isset($TransportedWorkerIdentity) && is_string($TransportedWorkerIdentity) && $TransportedWorkerIdentity !== '' && function_exists('adoptWorkerIdentity')) adoptWorkerIdentity($TransportedWorkerIdentity);
  // / Accounts are swept from here at most once in ten minutes, so an installation with no listener still sweeps them.
  // / Only a web request sweeps from here, because a command line has no visitor & may be root's, which never works in the accounts folder.
  if ($authenticationIsLoaded && $RunningFromCLI !== TRUE) sweepAccountsWhenDue(600);
  // / A visitor who must sign in where the Authentication Core could not be loaded is shown the page that says signing in is unavailable,
  // / rather than let through, because an administrator who requires accounts did not choose to serve anonymous visitors.
  // / A command line has no visitor, & -v & --config name a core that could not be loaded.
  else if (!$authenticationIsLoaded && $RunningFromCLI !== TRUE && authenticationIsOn() && applicationSetting('AllowAnonymousVisitors', TRUE) !== TRUE) {
    list ($EnginePageState['Enabled'], $EnginePageState['Page'], $EnginePageState['Available'], $EngineAnswer) = array(TRUE, 'signin', FALSE, 'page');
    errorEntry('A visitor must sign in & nobody can, because the Authentication Core could not be loaded!', 43002, FALSE); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $authenticationIsLoaded, $emailIsLoaded);
  return $EngineAnswer; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to sweep every account, code, sign in & count of failures that is over, where accounts are on & a sweep is due.
// / Accepts the fewest seconds since the last sweep, 0 for a sweep now. Returns how many records were removed.
// / The listener sweeps on its storage schedule, & a web request at most once in ten minutes, so one runs whichever is there.
// / Only the web server account sweeps. The accounts folder is that account's, & root never works by path inside a folder another account can change.
function sweepAccountsWhenDue($sweepInterval) {
  // / Set variables.
  global $CurrentUser, $ApacheUser, $RunningAsRoot, $EnableMemoryProtection;
  $RecordsRemoved = 0;
  $authenticationIsLoaded = $emailIsLoaded = $managerIsLoaded = $sweepRan = FALSE;
  $managerVersion = '';
  if (authenticationIsOn() && $RunningAsRoot !== TRUE && (string)$CurrentUser !== '' && (string)$CurrentUser === (string)$ApacheUser) {
    list ($authenticationIsLoaded, $emailIsLoaded) = loadAuthenticationCores(FALSE);
    if ($authenticationIsLoaded && !function_exists('sweepAuthenticationIfDue')) list ($managerIsLoaded, $managerVersion) = loadEngineManager('authenticationManager.php');
    if (function_exists('sweepAuthenticationIfDue')) list ($sweepRan, $RecordsRemoved) = sweepAuthenticationIfDue((int)$sweepInterval); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $authenticationIsLoaded, $emailIsLoaded, $managerIsLoaded, $sweepRan, $managerVersion, $sweepInterval);
  return $RecordsRemoved; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether a signed in user's quota allows a write, for the Resource Manager.
// / Accepts the owner & the bytes the write would add. Returns whether it may & the reason it may not, in that order.
// / An owner is only ever named where accounts are on, so an owner where they are off, or where the Authentication Manager is missing, allows the write
// / & is a warning, as a disk that cannot be measured is.
function evaluateOwnerQuota($quotaOwner, $requestedBytes) {
  // / Set variables.
  global $EnableMemoryProtection;
  $QuotaAllows = TRUE;
  $QuotaReason = '';
  $authenticationIsLoaded = $emailIsLoaded = $managerIsLoaded = FALSE;
  $managerVersion = '';
  list ($authenticationIsLoaded, $emailIsLoaded) = loadAuthenticationCores(FALSE);
  if ($authenticationIsLoaded && !function_exists('evaluateAccountQuota')) list ($managerIsLoaded, $managerVersion) = loadEngineManager('authenticationManager.php');
  if (function_exists('evaluateAccountQuota')) list ($QuotaAllows, $QuotaReason) = evaluateAccountQuota($quotaOwner, $requestedBytes);
  else warningEntry('A write was charged to a signed in user & their quota could not be read, because accounts are off or the Authentication Manager is unavailable. It was allowed.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $authenticationIsLoaded, $emailIsLoaded, $managerIsLoaded, $managerVersion, $quotaOwner, $requestedBytes);
  return array($QuotaAllows, $QuotaReason); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the folder a request's files are kept in.
// / Accepts the folder the application derived for its session. Returns the folder its files are kept in.
// / A signed in request keeps its files in the user's own folder, & every other request in its session's, as it always has.
// / The application asks before it builds anything on the folder, so every path it derives from it follows.
function engineSessionFolder($sessionFolder) {
  // / Set variables.
  global $SignedInFolder, $EnableMemoryProtection;
  $SessionFolder = (isset($SignedInFolder) && is_string($SignedInFolder) && $SignedInFolder !== '') ? $SignedInFolder : (string)$sessionFolder;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $sessionFolder);
  return $SessionFolder; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to request more runtime for a conversion that is still working.
// / Accepts the budget token & the number of additional seconds required.
// / Returns TRUE when the extension was granted or was not needed.
function requestRuntimeExtension($budgetToken, $requestedSeconds) {
  // / Set variables.
  global $ResourceAwarenessActive, $ManagerSocketTimeout, $EnableMemoryProtection;
  $ExtensionWasGranted = FALSE;
  $requestPayload = $replyPayload = array();
  $messageWasDelivered = FALSE;
  if (!$ResourceAwarenessActive or (string)$budgetToken === '') $ExtensionWasGranted = TRUE;
  else {
    $requestPayload = array('RequestType' => 'extend', 'BudgetToken' => (string)$budgetToken, 'RequestedSeconds' => (int)$requestedSeconds, 'WorkerPid' => getmypid());
    list ($messageWasDelivered, $replyPayload) = sendManagerMessage(buildManagerSocketPath('request-manager'), $requestPayload, 'worker', (int)$ManagerSocketTimeout * 3);
    // / An extension that was never answered is granted, for the same reason a budget request is.
    if (!isset($replyPayload['Approved'])) $ExtensionWasGranted = TRUE;
    else if ($replyPayload['Approved'] === TRUE) $ExtensionWasGranted = TRUE;
    else warningEntry('A runtime extension was refused. This worker may be reaped.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $requestPayload, $replyPayload, $messageWasDelivered, $budgetToken, $requestedSeconds);
  return $ExtensionWasGranted; }
// / -----------------------------------------------------------------------------------
// / A function to report that a conversion has finished & release its budget.
// / Accepts the budget token issued at approval.
// / Returns TRUE when the release was acknowledged, or when there was nothing to release.
function releaseConversionBudget($budgetToken) {
  // / Set variables.
  global $ResourceAwarenessActive, $ManagerSocketTimeout, $EnableMemoryProtection, $Verbose;
  $BudgetWasReleased = FALSE;
  $requestPayload = $replyPayload = array();
  $messageWasDelivered = FALSE;
  if (!$ResourceAwarenessActive or (string)$budgetToken === '') $BudgetWasReleased = TRUE;
  else {
    $requestPayload = array('RequestType' => 'release', 'BudgetToken' => (string)$budgetToken, 'WorkerPid' => getmypid());
    list ($messageWasDelivered, $replyPayload) = sendManagerMessage(buildManagerSocketPath('request-manager'), $requestPayload, 'worker', (int)$ManagerSocketTimeout * 3);
    if ($messageWasDelivered && isset($replyPayload['Approved']) && $replyPayload['Approved'] === TRUE) $BudgetWasReleased = TRUE;
    else warningEntry('A budget token could not be released. The reaper will reclaim it.'); 
    if ($Verbose && $BudgetWasReleased) logEntry('Worker '.getmypid().' released budget token '.(string)$budgetToken.'.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $requestPayload, $replyPayload, $messageWasDelivered, $budgetToken);
  return $BudgetWasReleased; }
// / -----------------------------------------------------------------------------------
// / A function to ask the listener to terminate one tracked worker.
// / Accepts the budget token or the process identifier of the worker.
// / Returns TRUE when the worker was terminated.
// / It is a client of readManagerState, sendManagerMessage & buildManagerSocketPath, so it sits beside them.
function killTargetedWorker($workerTarget) {
  // / Set variables.
  global $ResourceAwarenessActive, $ManagerSocketTimeout, $Lol, $EnableMemoryProtection;
  $WorkerWasKilled = FALSE;
  $requestPayload = $replyPayload = $workerRegistry = array();
  $messageWasDelivered = $registryWasRead = FALSE;
  $cleanTarget = trim((string)$workerTarget);
  $targetPid = 0;
  $targetToken = '';
  if (!$ResourceAwarenessActive) print($Lol.'Resource awareness is unavailable, so no worker is tracked.'.$Lol);
  else if ($cleanTarget === '') print($Lol.'Supply a worker identifier or process identifier.'.$Lol);
  else {
    // / A numeric target is a process identifier. Anything else is treated as a token.
    if (ctype_digit($cleanTarget)) $targetPid = (int)$cleanTarget;
    else {
      $targetToken = preg_replace('/[^a-f0-9]/', '', strtolower($cleanTarget));
      list ($registryWasRead, $workerRegistry) = readManagerState('workers');
      if (isset($workerRegistry[$targetToken])) $targetPid = (int)$workerRegistry[$targetToken]['WorkerPid']; }
    if ($targetPid < 2) print($Lol.'That worker is not tracked.'.$Lol);
    else {
      $requestPayload = array('RequestType' => 'kill', 'WorkerPid' => $targetPid, 'BudgetToken' => $targetToken);
      list ($messageWasDelivered, $replyPayload) = sendManagerMessage(buildManagerSocketPath('core-manager'), $requestPayload, 'core', (int)$ManagerSocketTimeout);
      if ($messageWasDelivered && isset($replyPayload['Approved']) && $replyPayload['Approved'] === TRUE) $WorkerWasKilled = TRUE;
      print($Lol.($WorkerWasKilled ? 'Worker '.$targetPid.' terminated.' : 'Worker '.$targetPid.' could not be terminated.').$Lol); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $requestPayload, $replyPayload, $workerRegistry, $messageWasDelivered, $registryWasRead, $cleanTarget, $targetPid, $targetToken, $workerTarget);
  return $WorkerWasKilled; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read a shared manager state file.
// / Accepts the short state name.
// / Returns a success boolean & the state array, in that order.
// / State is shared through the filesystem because every manager is a separate process.
// / Root reads none of it. The web server account writes every state file, in a folder it
// / owns, so root hands each command that needs one to that account instead.
function readManagerState($stateName) {
  // / Set variables.
  global $ManagerSocketDir, $DirSep, $RunningAsRoot, $EnableMemoryProtection;
  $StateWasRead = FALSE;
  $ManagerState = array();
  $statePath = $stateContents = '';
  $decodedState = array();
  $cleanStateName = preg_replace('/[^A-Za-z0-9\-]/', '', (string)$stateName);
  if ($RunningAsRoot) warningEntry('Root does not read the '.$cleanStateName.' state, because the web server account writes it. Nothing was read.');
  else if ($cleanStateName !== '') {
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
// /
// / Root writes none of it, because the socket folder is the web server account's & root could not write there safely.
// / -k is handed to the web server account.
// / The Environment Manager keeps its state at the top of the data root, which root owns, through writeEnvironmentState().
function writeManagerState($stateName, $stateData) {
  // / Set variables.
  global $ManagerSocketDir, $DirSep, $RunningAsRoot, $EnableMemoryProtection;
  $StateWasWritten = FALSE;
  $statePath = $stateContents = '';
  $bytesWritten = 0;
  $cleanStateName = preg_replace('/[^A-Za-z0-9\-]/', '', (string)$stateName);
  if ($RunningAsRoot) warningEntry('Root does not write the '.$cleanStateName.' state, because it belongs to the web server account. Nothing was written.');
  else if ($cleanStateName !== '' && is_array($stateData)) {
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
// / A function to name the Environment Manager's state file.
// / Accepts nothing. Returns the path, or an empty string when the data root is not known.
// / It sits at the top of the data root, root owned & readable by the web server account's
// / group. Root owns that folder, so no other account can replace a name in it, & the web
// / server account can read what a pass found, which is how --status reports the last one.
// / The Engine folder would hide it from every account the state is for, because only root can read inside it.
// / It is not a secret, & Secrets holds secrets alone.
// / The install secret is kept in Secrets, with a copy beside this file.
function environmentStatePath() {
  // / Set variables.
  global $DataRootLoc, $EnableMemoryProtection;
  $StatePath = '';
  if (isset($DataRootLoc) && is_string($DataRootLoc) && trim($DataRootLoc) !== '') $StatePath = rtrim($DataRootLoc, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.engineApplicationSlug().'-environment.state';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $StatePath is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection);
  return $StatePath; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the Environment Manager's state file.
// / Accepts nothing. Returns whether it was read, the state & a status word, in that order.
// / The status is 'read', 'absent', 'unreadable' or 'corrupt'.
// / Root & every other account read it through the kernel alike. A name that is not a plain
// / file cannot redirect or hold a reader, & a state file is a few kilobytes, so a megabyte is
// / a generous bound on what is read.
function readEnvironmentState() {
  // / Set variables.
  global $EnableMemoryProtection;
  $StateWasRead = FALSE;
  $EnvironmentState = array();
  $StateStatus = 'absent';
  $statePath = $stateContents = $readNotice = '';
  $fileWasRead = FALSE;
  $decodedState = NULL;
  $statePath = environmentStatePath();
  if ($statePath !== '') clearstatcache(TRUE, $statePath);
  if ($statePath === '' or (!file_exists($statePath) && !is_link($statePath))) $StateStatus = 'absent';
  else {
    list ($fileWasRead, $stateContents, $readNotice) = readFileWithoutFollowing($statePath, 1048576);
    if (!$fileWasRead) $StateStatus = 'unreadable';
    else {
      $decodedState = json_decode($stateContents, TRUE);
      if (!is_array($decodedState)) $StateStatus = 'corrupt';
      else {
        $EnvironmentState = $decodedState;
        $StateWasRead = TRUE;
        $StateStatus = 'read'; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $statePath, $stateContents, $readNotice, $fileWasRead, $decodedState);
  return array($StateWasRead, $EnvironmentState, $StateStatus); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write the Environment Manager's state file.
// / Accepts the state to record. Returns TRUE when it was written.
// / Only root writes it. The kernel writes a new file & renames it into place, so a reader
// / sees the old state or the new one & never part of either, & nothing planted at the name
// / is followed. The owner stays root & only the group becomes the web server account's, so
// / that account can read what a pass found & can never change it.
function writeEnvironmentState($stateData) {
  // / Set variables.
  global $RunningAsRoot, $ApacheUser, $EnableMemoryProtection;
  $StateWasWritten = FALSE;
  $statePath = $writeNotice = $groupNotice = '';
  $stateContents = FALSE;
  $fileWasWritten = $groupWasChanged = FALSE;
  $statePath = environmentStatePath();
  if (!$RunningAsRoot) warningEntry('Only root writes the Environment Manager\'s state. Nothing was written.');
  else if ($statePath === '' or !is_array($stateData)) warningEntry('The Environment Manager\'s state has nowhere to go, because the data location is not known. Nothing was written.');
  else {
    $stateContents = json_encode($stateData, JSON_INVALID_UTF8_SUBSTITUTE);
    if (!is_string($stateContents)) warningEntry('The Environment Manager\'s state could not be encoded. Nothing was written.');
    else {
      list ($fileWasWritten, $writeNotice) = writeFileWithoutFollowing($statePath, $stateContents, 0640);
      if (!$fileWasWritten) warningEntry('The Environment Manager\'s state could not be written. '.$writeNotice);
      else {
        list ($groupWasChanged, $groupNotice) = changeOwnerWithoutFollowing($statePath, '', (string)$ApacheUser);
        if (!$groupWasChanged) warningEntry($groupNotice.' The web server account cannot read the Environment Manager\'s state until it is.');
        $StateWasWritten = TRUE; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $statePath, $writeNotice, $groupNotice, $stateContents, $fileWasWritten, $groupWasChanged, $stateData);
  return $StateWasWritten; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove root's own logfiles once they are older than a number of days.
// / Accepts the number of days to keep. Returns whether the folder was examined & how many
// / files were removed, in that order.
// / Root's log folder is Engine, which no other account can read or clean, so nothing but root
// / would ever remove an old one. The Environment Manager calls this every pass, watching or
// / repairing, because removing root's own files from root's own folder changes nothing about
// / the host. Only a name shaped like one of root's logfiles is considered, so the state, the
// / lock & the supply chain report are never touched, & each file is removed through the
// / kernel, which never follows a link.
function pruneRootLogs($retentionDays) {
  // / Set variables.
  global $EngineDir, $ApplicationName, $RunningAsRoot, $EnableMemoryProtection;
  $FolderWasExamined = FALSE;
  $FilesRemoved = 0;
  $folderIsHeld = $fileWasRemoved = FALSE;
  $holdNotice = $removalNotice = $logPattern = $candidateName = $candidatePath = '';
  $candidateNames = $candidateStatus = array();
  $oldestKept = $candidateType = 0;
  if (!$RunningAsRoot) warningEntry('Only root prunes root\'s logfiles. Nothing was removed.');
  else if (!isset($EngineDir) or !is_string($EngineDir) or $EngineDir === '' or !is_dir($EngineDir)) warningEntry('Root\'s log folder does not exist yet, so no old logfile was removed. Run -fp to create it.');
  else {
    list ($folderIsHeld, $holdNotice) = folderIsHeldForRoot($EngineDir);
    if (!$folderIsHeld) warningEntry('Root\'s old logfiles were not removed. '.$holdNotice);
    else {
      $FolderWasExamined = TRUE;
      $oldestKept = time() - (max(1, (int)$retentionDays) * 86400);
      // / The name verifyLogs gives a logfile. The application, the role, the increment, the
      // / date & an optional hash, in that order.
      $logPattern = '/^'.preg_quote((string)$ApplicationName, '/').'_[A-Za-z0-9-]+_[0-9]+_[0-9_]+(_[A-Za-z0-9]+)?\.txt$/';
      $candidateNames = @scandir($EngineDir);
      if (!is_array($candidateNames)) $candidateNames = array();
      foreach ($candidateNames as $candidateName) {
        if (preg_match($logPattern, (string)$candidateName) !== 1) continue;
        $candidatePath = rtrim($EngineDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.(string)$candidateName;
        clearstatcache(TRUE, $candidatePath);
        $candidateStatus = @lstat($candidatePath);
        if (!is_array($candidateStatus)) continue;
        // / A plain file or a link & nothing else, & only once it is older than the retention.
        $candidateType = ((int)$candidateStatus['mode'] & 0170000);
        if (($candidateType !== 0100000 && $candidateType !== 0120000) or (int)$candidateStatus['mtime'] >= $oldestKept) continue;
        list ($fileWasRemoved, $removalNotice) = removeFileWithoutFollowing($candidatePath);
        if ($fileWasRemoved) $FilesRemoved++;
        else warningEntry('An old logfile of root\'s could not be removed. '.$removalNotice); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderIsHeld, $fileWasRemoved, $holdNotice, $removalNotice, $logPattern, $candidateName, $candidatePath, $candidateNames, $candidateStatus, $oldestKept, $candidateType, $retentionDays);
  return array($FolderWasExamined, $FilesRemoved); }
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
// / A function to add up the budget every tracked worker holds.
// / Accepts the tracked worker registry. Returns the total, in cost units.
// / The Resource Manager works its budget out from this each time it is asked for one, so what is
// / left is always what the conversions in flight have not taken.
function trackedWorkerCost($workerRegistry) {
  // / Set variables.
  global $EnableMemoryProtection;
  $TrackedCost = 0;
  $workerRecord = array();
  if (is_array($workerRegistry)) foreach ($workerRegistry as $workerRecord) $TrackedCost = $TrackedCost + (int)($workerRecord['ConversionCost'] ?? 0);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $workerRecord, $workerRegistry);
  return $TrackedCost; }
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
// / This is the entry point a service manager uses. It does not fork & does not return, so
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
  // / Root does not run the listener, & is refused here rather than warned.
  // / The listener sweeps the web server account's folders & keeps its state there, & root never works by name in those.
  // / The unit runs it as the web server account, & -l starts it by hand as that account.
  if ($CurrentUser === $ApacheUser && !$RunningAsRoot) $callerIsAuthorized = TRUE;
  if (!$callerIsAuthorized) errorEntry('The foreground listener was started by an account that may not operate it!', 31014, TRUE);
  else {
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
      // / not spent, because there is nothing anybody else could replay.
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
    exec(escapeshellarg($systemctlBinary).' is-enabled '.engineApplicationSlug().'-listener 2>/dev/null', $commandOutput, $commandExitCode);
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
// /
// / Root never runs the listener itself, & hands the whole start to the web server account through runAsAccount.
// / The managers sweep that account's folders by name, which root must never do, & bind sockets its web workers must reach.
function startCoreManagerListener() {
  // / Set variables.
  global $InstLoc, $ManagerSocketDir, $ApacheUser, $CurrentUser, $RunningAsRoot, $TotalResourceBudget, $ReserveResourcePercentage, $MaxConcurrentWorkers, $Verbose, $Lol, $EnableMemoryProtection;
  $ListenerWasStarted = FALSE;
  $managerState = $chownOutput = $listenerStatus = array();
  $stateWasRead = $directoryIsReady = $listenerIsRunning = FALSE;
  $startupKey = $spawnCommand = $innerCommand = $managerRole = '';
  $chownExitCode = $listenerPid = $waitCounter = $managerPid = $unitExitCode = 0;
  $unitOwnsListener = $handedToAccount = FALSE;
  $unitOutput = $accountOutput = array();
  $accountExitCode = 1;
  // / A service unit that owns this listener is the only thing that should start it.
  // / Starting a second one beside it produces two Core Managers fighting over one socket.
  if (listenerUnitIsInstalled()) {
    $unitOwnsListener = TRUE;
    print($Lol.'A service unit owns this listener. Starting it through systemd.'.$Lol);
    exec('systemctl start '.engineApplicationSlug().'-listener 2>&1', $unitOutput, $unitExitCode);
    if ($unitExitCode === 0) print('  systemctl start '.engineApplicationSlug().'-listener'.$Lol.$Lol);
    else {
      print('  systemctl start '.engineApplicationSlug().'-listener FAILED'.$Lol);
      print('  '.implode($Lol.'  ', $unitOutput).$Lol.$Lol); } }
  // / The web server account runs this same argument & reports for itself.
  else if ($RunningAsRoot) {
    $handedToAccount = TRUE;
    list ($accountOutput, $accountExitCode) = runAsAccount('php '.escapeshellarg($InstLoc.DIRECTORY_SEPARATOR.engineApplicationEntryPoint()).' -l 2>&1', (string)$ApacheUser);
    print(implode($Lol, $accountOutput).$Lol); }
  else $directoryIsReady = prepareManagerSocketDir();
  // / Hand the socket directory to the web server user before anything binds inside it.
  // / A root owned directory is unreachable to the workers that must connect.
  // / The socket directory is not chowned here, because ownership of every path this application manages belongs to -fp, which runs as root once & exits.
  // / Doing it here would mean the listener had to be root to start, & then had to give that up.
  // / The wrong owner is reported rather than corrected, because correcting it needs a privilege this process should not be holding.
  if ($directoryIsReady && !is_writable($ManagerSocketDir)) warningEntry('The manager socket directory at '.$ManagerSocketDir.' is not writable by this account. Run -fp as root to correct it.');
  // / Only the account that runs the listener reads its state. Root handed the start over
  // / above, & a unit that owns the listener is asked through systemctl instead.
  if (!$unitOwnsListener && !$handedToAccount) list ($stateWasRead, $managerState) = readManagerState('managers');
  // / A unit that failed to start, or an account that reported failure, is a failure.
  if ($unitOwnsListener) $ListenerWasStarted = ($unitExitCode === 0);
  else if ($handedToAccount) $ListenerWasStarted = ($accountExitCode === 0);
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
    $innerCommand = engineStartupKeyVariable().'='.escapeshellarg($startupKey)
      .' nohup php '.escapeshellarg($InstLoc.DIRECTORY_SEPARATOR.engineApplicationEntryPoint())
      .' --start-core-manager'
      .' > /dev/null 2>&1 &';
    // / Only the web server account reaches this. A unit starts its own listener, & root
    // / handed the start to the web server account above.
    $spawnCommand = $innerCommand;
    if ($Verbose) logEntry('Launching the Core Manager listener as '.$CurrentUser.'.');
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
      print($Lol.'  '.str_pad('Running as', 24).$CurrentUser.$Lol);
      print('  '.str_pad('Socket directory', 24).$ManagerSocketDir.$Lol);
      print('  '.str_pad('Total budget', 24).((int)$TotalResourceBudget < 1 ? 'AUTO, from the processor count' : (int)$TotalResourceBudget.' cost units').$Lol);
      print('  '.str_pad('Reserved share', 24).(int)$ReserveResourcePercentage.'%'.$Lol);
      print('  '.str_pad('Concurrent limit', 24).((int)$MaxConcurrentWorkers < 1 ? 'NONE, the budget decides' : (int)$MaxConcurrentWorkers).$Lol);
      print($Lol.'Check it with --status & stop it with -k.'.$Lol.$Lol);
      if (count($listenerStatus['Subordinates']) < 3) print('One or more subordinate managers did not start. See the log.'.$Lol.$Lol); }
    else {
      errorEntry('The Core Manager listener could not be started!', 31005, FALSE);
      // / The startup key is NEVER printed & never written to the log. It authorizes a
      // / manager process for the length of a key window, & anything that captures console
      // / output or a logfile would be handed a working one.
      print($Lol.'The listener did not record itself within five seconds. See the log.'.$Lol);
      print('Confirm '.$ApacheUser.' can read the install secret & write the logfile,'.$Lol);
      print('then run -fp as root & try again.'.$Lol.$Lol); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $managerState, $chownOutput, $listenerStatus, $stateWasRead, $directoryIsReady, $listenerIsRunning, $startupKey, $spawnCommand, $innerCommand, $managerRole, $chownExitCode, $listenerPid, $waitCounter, $managerPid, $unitOwnsListener, $unitOutput, $unitExitCode, $handedToAccount, $accountOutput, $accountExitCode);
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
    // / A listener nobody started is stopped, which is what was asked for, so this is success, as systemctl stop finds it.
    // / Processes that survive termination, further down, are still a failure & still return FALSE.
    $ListenerWasStopped = TRUE; }
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
// / A function to keep a manager writing to the logfile it should be writing now.
// / Accepts the time it last did so. Returns the time it last did so, which is now once a minute.
// / A manager runs for weeks & names its logfile as it starts.
// / The application names its logfiles by date & by size, & says how to move on through $EngineLogProvider.
// / That provider is asked here once a minute, & an application that names none leaves a manager on its first logfile.
function refreshProcessLog($lastRefreshTime) {
  // / Set variables.
  global $EngineLogProvider, $EnableMemoryProtection;
  static $problemWasReported = FALSE;
  $RefreshTime = (int)$lastRefreshTime;
  $logFunction = $logPath = '';
  $logIsReady = FALSE;
  if ((time() - $RefreshTime) >= 60) {
    $RefreshTime = time();
    $logFunction = (isset($EngineLogProvider) && is_string($EngineLogProvider)) ? trim($EngineLogProvider) : '';
    if ($logFunction !== '' && function_exists($logFunction)) list ($logIsReady, $logPath) = $logFunction();
    // / A problem is reported once for the life of the process, because this runs every minute.
    if (!$problemWasReported && $logFunction !== '' && !function_exists($logFunction)) {
      $problemWasReported = TRUE;
      warningEntry('A log provider named '.$logFunction.' was declared & does not exist, so this manager keeps the logfile it started with.'); }
    else if (!$problemWasReported && $logFunction !== '' && !$logIsReady) {
      $problemWasReported = TRUE;
      warningEntry('This manager could not move on to the logfile it should be writing now. It keeps the one it has.'); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $logFunction, $logPath, $logIsReady, $lastRefreshTime);
  return $RefreshTime; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to create & secure the socket directory.
// / Accepts no arguments.
// / Returns TRUE when the directory exists & is writable.
// / Root never changes its mode by name. The folder is the web server account's, which can
// / swap it for a link, & a chmod by name lands on whatever the link points at. Root started
// / the listener through here with -l, so 0700 could have landed on /etc. The web server
// / account secures its own folder, & -fp secures it through that account. A folder root has
// / to create is given to the web server account by changeOwnerWithoutFollowing, which
// / changes the name itself.
function prepareManagerSocketDir() {
  // / Set variables.
  global $ManagerSocketDir, $RunningAsRoot, $ApacheUser, $EnableMemoryProtection;
  $DirectoryIsReady = $ownerWasChanged = FALSE;
  $folderStatus = array();
  $ownerNotice = '';
  if (!is_dir($ManagerSocketDir) && @mkdir($ManagerSocketDir, 0700, TRUE) && $RunningAsRoot) {
    list ($ownerWasChanged, $ownerNotice) = changeOwnerWithoutFollowing($ManagerSocketDir, (string)$ApacheUser, (string)$ApacheUser);
    if (!$ownerWasChanged) warningEntry($ownerNotice); }
  clearstatcache(TRUE, $ManagerSocketDir);
  $folderStatus = @lstat($ManagerSocketDir);
  if (is_array($folderStatus) && ((int)$folderStatus['mode'] & 0170000) === 0040000) {
    if (!$RunningAsRoot) @chmod($ManagerSocketDir, 0700);
    if (is_writable($ManagerSocketDir)) $DirectoryIsReady = TRUE; }
  if (!$DirectoryIsReady) warningEntry('The manager socket directory at '.$ManagerSocketDir.' is not usable.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderStatus, $ownerWasChanged, $ownerNotice);
  return $DirectoryIsReady; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to dispatch an internal manager role after its startup key is validated.
// / Accepts the role name & the supplied startup key.
// / Returns TRUE when a role ran to completion.
// / This is the single entry point convertCore.php uses to enter a manager process.
// / The third argument says whether the key reached this process from another one.
// / A key that crossed a boundary is spent, because a copy of it may exist elsewhere.
// / A key derived & checked inside one process is NOT spent, because it never left that
// / process & there is nothing anybody else could replay.
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
  $ListenerStatus = array('CoreManagerPid' => 0, 'Subordinates' => array(), 'TrackedWorkers' => 0, 'SessionLocations' => 0, 'Budget' => array(), 'Storage' => array(), 'StorageReserveBytes' => 0, 'StorageWarningPercentage' => 0, 'Answered' => FALSE);
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
      $ListenerStatus['Storage'] = (isset($replyPayload['Storage']) && is_array($replyPayload['Storage'])) ? $replyPayload['Storage'] : array();
      $ListenerStatus['StorageReserveBytes'] = (int)($replyPayload['StorageReserveBytes'] ?? 0);
      $ListenerStatus['StorageWarningPercentage'] = (int)($replyPayload['StorageWarningPercentage'] ?? 0);
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
// / Each setting is read from an Engine name first & from the application's own name second, deliberately.
// / HRConvert2's names mention streams, & renaming a setting in config.php would silently reset it at the next update.
// / A new application sets the Engine names & has no stream shaped settings at all.
// / Every fallback ends in a literal configuration cannot overwrite, so a fetch is never left deciding for itself.
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
// / A function to say whether an address may be reached, allowing for an administrator who
// / has permitted particular private ranges.
// / Accepts the address. Returns whether it may be reached & whether it was allowed only
// / because a range permitted it, in that order.
// /
// / It wraps isPubliclyRoutableIP().
// / A publicly routable address is permitted, & a private one only when an administrator named the range it sits in.
// / Three ranges are refused whatever is configured, because a setting that can open a serious hole should refuse it.
// /   127.0.0.0/8 & ::1     This host. A service bound to localhost was bound there so nothing off the machine could reach it.
// /   169.254.0.0/16        Link local, where cloud metadata answers. 169.254.169.254 returns credentials to anything that asks.
// / The address is judged after the IPv4 in IPv6 unwrapping isPubliclyRoutableIP performs, so ::ffff:7f00:1 is loopback here.
// / A host that needs to fetch from itself has a local path, which needs no network.
function addressMayBeReached($ipAddress) {
  // / Set variables.
  global $PermittedPrivateRanges, $EnableMemoryProtection;
  $AddressIsPermitted = FALSE;
  $PermittedByRange = FALSE;
  $neverPermitted = array('127.0.0.0/8', '169.254.0.0/16', '::1/128', 'fe80::/10');
  $refusedRange = $permittedRange = '';
  $addressIsRefusedOutright = FALSE;
  if (isPubliclyRoutableIP($ipAddress)) $AddressIsPermitted = TRUE;
  else {
    foreach ($neverPermitted as $refusedRange) {
      if (!addressIsInRange($ipAddress, $refusedRange)) continue;
      $addressIsRefusedOutright = TRUE;
      break; }
    if ($addressIsRefusedOutright) warningEntry('An address in '.$refusedRange.' was refused. That range is never permitted, whatever is configured.');
    else if (isset($PermittedPrivateRanges) && is_array($PermittedPrivateRanges)) {
      foreach ($PermittedPrivateRanges as $permittedRange) {
        if (trim((string)$permittedRange) === '') continue;
        if (!addressIsInRange($ipAddress, trim((string)$permittedRange))) continue;
        $AddressIsPermitted = TRUE;
        $PermittedByRange = TRUE;
        break; } } }
  // / An allowance an administrator forgot they made should be visible in a log rather than
  // / only in a configuration file nobody has opened this year.
  if ($PermittedByRange) warningEntry('A private address was reached because '.$permittedRange.' is a permitted range.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $neverPermitted, $refusedRange, $permittedRange, $addressIsRefusedOutright, $ipAddress);
  return array($AddressIsPermitted, $PermittedByRange); }
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
// /   ::ffff:0:0/96     IPv4 mapped in IPv6. ::ffff:127.0.0.1 is the loopback address
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
// / A function to decide whether a path may be handed to a RECURSIVE operation.
// / Accepts the path. Returns TRUE only when a recursive change to it cannot wreck the host.
// /
// / chown -R & chmod -R are the two most destructive things this application can do, & they run as root.
// / A path that is empty, missing or a system directory would take the operating system with it.
// / Links & .. are resolved before judging, because /srv/data/../../ is / & a comparison of the text would miss it.
// / A refused path is reported rather than skipped silently, because an installation pointing at it is misconfigured.
function pathIsSafeToModifyRecursively($candidatePath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $PathIsSafe = FALSE;
  $resolvedPath = $refusedPath = '';
  $refusedPaths = array();
  // / An EMPTY path is refused before realpath ever sees it.
  // / realpath('') returns the current working directory rather than nothing, so an unset
  // / configuration value resolved to wherever the process happened to be standing & passed
  // / every check below. For the command line that is the installation root.
  if (trim((string)$candidatePath) !== '') $resolvedPath = (string)@realpath((string)$candidatePath);
  // / Everything below assumes a real directory. Nothing else is worth judging.
  if ($resolvedPath !== '' && is_dir($resolvedPath)) {
    $PathIsSafe = TRUE;
    // / The filesystem root & every directory an operating system needs to boot.
    // / A recursive change to any of these is unrecoverable on a running host.
    $refusedPaths = array('/', '/bin', '/boot', '/dev', '/etc', '/home', '/lib', '/lib32',
      '/lib64', '/media', '/mnt', '/opt', '/proc', '/root', '/run', '/sbin', '/srv', '/sys',
      '/tmp', '/usr', '/var');
    foreach ($refusedPaths as $refusedPath) {
      if ($resolvedPath === $refusedPath) {
        $PathIsSafe = FALSE;
        break; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $resolvedPath, $refusedPaths, $refusedPath, $candidatePath);
  return $PathIsSafe; }
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
  $permittedByRange = FALSE;
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
      // / A resolved address may be reached because it is public, or because an
      // / administrator permitted the private range it sits in. Both paths end here, so
      // / both stream inspection & user fetches get the same answer.
      list ($isPublic, $permittedByRange) = addressMayBeReached($urlIP);
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
  purgeSensitiveMemory($EnableMemoryProtection, $permittedByRange, $records, $record, $urlIP, $isPublic);
  return array($URLIP, $StreamContainsLAN, $LookupFailed); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to count how many files a session has already fetched, & to record one more.
// / Accepts the session directory & whether this call is recording rather than asking.
// / Returns the count, including the one just recorded when recording.
// /
// / The tally lives in the session directory as a file. It is not held in memory because
// / each request is a separate process, & a counter that does not survive the request
// / counts to one forever.
// / A session ending removes its directory & takes the tally with it, which is the reset.
// /
// / A tally that cannot be read counts as zero rather than as a refusal. Failing to count
// / should not stop somebody using the application, & the operation budget is a second
// / limit underneath this one.
function countSessionURLDownloads($sessionDirectory, $recordOne = FALSE) {
  // / Set variables.
  global $DirSep, $EnableMemoryProtection;
  $DownloadCount = 0;
  $tallyPath = $tallyContents = '';
  $tallyPath = rtrim((string)$sessionDirectory, $DirSep).$DirSep.'.hrc2-url-downloads';
  if (file_exists($tallyPath)) {
    $tallyContents = (string)@file_get_contents($tallyPath);
    $DownloadCount = (int)trim($tallyContents); }
  if ($recordOne) {
    $DownloadCount++;
    @file_put_contents($tallyPath, (string)$DownloadCount, LOCK_EX); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $tallyPath, $tallyContents, $sessionDirectory, $recordOne);
  return $DownloadCount; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to fetch a URL an operator typed & place it in their session.
// / Accepts the raw URL & the directory to write into. Returns whether it worked, the path
// / it wrote & a reason.
// /
// / This is the one place an operator can make this server reach out on their behalf, so every existing protection is used.
// / normalizeStreamBaseURL rebuilds the text into a URL, because sanitize() would return a filename rather than an address.
// / gatherRemoteHostInfo resolves the name & refuses anything not publicly routable, as for a URL inside a playlist.
// / downloadRemoteFileForInspection pins the address with curl --resolve, follows no redirect, stops at the byte ceiling & keeps only a 200 or 206.
// / It runs inside a namespace with a network & no resolver.
// / The file is then moved into the session, so a fetch that failed leaves nothing behind.
// / The name is taken from the URL path & passed through sanitize(), which is what sanitize() is for.
// / A URL naming no file gets one, because a session file with no name cannot be listed, converted or deleted.
function fetchUserSuppliedURL($suppliedURL, $sessionDirectory) {
  // / Set variables.
  global $DirSep, $EnableMemoryProtection, $URLDownloadMaximumBytes, $ScanURLDownloads, $Allowed;
  $FetchSucceeded = FALSE;
  $FetchedPath = $FetchReason = '';
  $baseIsUsable = $inspectionFailed = $resolutionFailed = $containsLAN = $lookupFailed = FALSE;
  $downloadFailed = $wasTruncated = $nameIsSanitized = FALSE;
  $cleanURL = $urlHost = $urlPort = $urlScheme = $urlIP = '';
  $temporaryPath = $suggestedName = $targetPath = '';
  $scanCompleted = $threatWasFound = FALSE;
  $scanFindings = array();
  $scannerUsed = $scanFinding = '';
  $fetchedExtension = '';
  $urlParts = array();
  $ceilingSetting = 0;
  // / The default config.php documents for --URL Download Maximum Bytes--, which configuration cannot overwrite.
  $defaultCeiling = 2147483648;
  list ($baseIsUsable, $cleanURL, $FetchReason) = normalizeStreamBaseURL($suppliedURL);
  if ($baseIsUsable) {
    list ($inspectionFailed, $resolutionFailed, $containsLAN, $lookupFailed, $urlHost, $urlPort, $urlScheme, $urlIP) = gatherRemoteHostInfo($cleanURL);
    if ($inspectionFailed) $FetchReason = 'The address was refused. '.($containsLAN ? 'It resolves to a private, reserved or loopback address.' : ($lookupFailed ? 'Its name could not be resolved.' : 'It did not pass inspection.'));
    else {
      // / A user's fetch gets the ceiling an administrator set for user fetches, which is a
      // / different question from how much of a playlist is worth reading.
      // / config.php says 0 is no ceiling, which downloadRemoteFileForInspection() is told with -1, because its own 0 keeps the inspection ceiling.
      // / A value that is not a whole number of 0 or more falls back to two gigabytes, the default config.php documents, & says so.
      $ceilingSetting = filter_var((isset($URLDownloadMaximumBytes) ? $URLDownloadMaximumBytes : $defaultCeiling), FILTER_VALIDATE_INT, array('options' => array('min_range' => 0)));
      if ($ceilingSetting === FALSE) {
        warningEntry('--URL Download Maximum Bytes-- is not a whole number of 0 or more, so the default of '.$defaultCeiling.' bytes is used.');
        $ceilingSetting = $defaultCeiling; }
      list ($downloadFailed, $temporaryPath, $wasTruncated) = downloadRemoteFileForInspection($cleanURL, $urlHost, $urlPort, $urlIP, $urlScheme, 'user-'.bin2hex(random_bytes(8)), ($ceilingSetting === 0 ? -1 : $ceilingSetting));
      if ($downloadFailed or $temporaryPath === '') $FetchReason = 'The address was accepted & nothing could be fetched from it.';
      else {
        // / A fetched file is scanned before it is placed, as an upload is, so fetching is not a way around the scanner.
        // / A file that fails is not placed & the temporary copy is removed.
        // / A scanner that could not run is not a file that failed, & the fetch is refused, because a scan was asked for.
        // / A Pipeline Core that did not load is a scanner that could not run, & is refused the same way.
        if (isset($ScanURLDownloads) && $ScanURLDownloads === TRUE) {
          if (!function_exists('runVirusScan')) $scanFindings = array('No scanner could run, because the Pipeline Core is not loaded.');
          else list ($scanCompleted, $threatWasFound, $scanFindings, $scannerUsed) = runVirusScan(array($temporaryPath), '');
          if (!$scanCompleted or $threatWasFound) {
            @unlink($temporaryPath);
            foreach ($scanFindings as $scanFinding) warningEntry('A fetched file was refused. '.$scanFinding);
            $FetchReason = $threatWasFound ? 'The fetched file was refused by the '.$scannerUsed.' scanner & was not kept.' : 'The fetched file could not be scanned, so it was not kept.'; } }
        if ($FetchReason !== '' && !$FetchSucceeded && $temporaryPath !== '' && !file_exists($temporaryPath)) {
          // / The scan refused it. Nothing further happens & the reason above is what is
          // / reported.
          $temporaryPath = ''; }
        else {
        $urlParts = @parse_url($cleanURL);
        $suggestedName = isset($urlParts['path']) ? basename((string)$urlParts['path']) : '';
        // / A fetched file must be a format this installation accepts, as an upload must.
        // / Otherwise fetching a file would be the way around what an administrator decided this installation handles.
        // / $Allowed is every extension any enabled subsystem reads or writes, which is the
        // / same list an upload is judged against. A format nobody enabled is refused here
        // / for the same reason it is refused there.
        // / The extension comes from the URL path & is the only thing available before the
        // / file exists. Content is judged afterwards, by the scanner & by whichever
        // / pipeline is eventually asked to read it.
        $fetchedExtension = strtolower((string)pathinfo($suggestedName, PATHINFO_EXTENSION));
        if (isset($Allowed) && is_array($Allowed) && !in_array($fetchedExtension, $Allowed, TRUE)) {
          @unlink($temporaryPath);
          $FetchReason = ($fetchedExtension === '') ? 'That address names no file type, so there is nothing to convert.' : 'This server does not handle '.$fetchedExtension.' files.'; }
        else {
        list ($suggestedName, $nameIsSanitized) = sanitize($suggestedName, TRUE);
        if (!$nameIsSanitized or trim($suggestedName) === '' or $suggestedName === '.') $suggestedName = 'download-'.bin2hex(random_bytes(4));
        $targetPath = rtrim((string)$sessionDirectory, $DirSep).$DirSep.$suggestedName;
        // / A fetch never overwrites something already in the session.
        if (file_exists($targetPath)) $targetPath = rtrim((string)$sessionDirectory, $DirSep).$DirSep.bin2hex(random_bytes(4)).'-'.$suggestedName;
        if (!@rename($temporaryPath, $targetPath)) {
          @unlink($temporaryPath);
          $FetchReason = 'The file was fetched & could not be placed in the session.'; }
        else {
          $FetchedPath = $targetPath;
          $FetchSucceeded = TRUE;
          $FetchReason = 'Fetched '.basename($targetPath).' from '.$urlHost.'.'.($wasTruncated ? ' It reached the size ceiling & was truncated.' : ''); } } } } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $fetchedExtension, $scanCompleted, $threatWasFound, $scanFindings, $scannerUsed, $scanFinding, $baseIsUsable, $inspectionFailed, $resolutionFailed, $containsLAN, $lookupFailed, $downloadFailed, $wasTruncated, $nameIsSanitized, $cleanURL, $urlHost, $urlPort, $urlScheme, $urlIP, $temporaryPath, $suggestedName, $targetPath, $urlParts, $ceilingSetting, $defaultCeiling, $suppliedURL, $sessionDirectory);
  return array($FetchSucceeded, $FetchedPath, $FetchReason); }
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
  // / The resolved policy values are locals & are deliberately not declared global.
  // / Each is lowercase, so convention three keeps it inside this function, & every caller assigns them from networkPolicy().
  // / The network policy is resolved once & every setting below comes from it.
  $permittedByRange = $hostIsPublic = FALSE;
  $networkPolicy = networkPolicy();
  $engineAllowPlainHTTP = $networkPolicy['AllowPlainHTTP'];
  $LookupFailed = $InspectionFailed = TRUE;
  $URLIP = $URLHost = $URLPort = $URLScheme = $StreamContainsLAN = $StreamDNSContainsLAN = $StreamURLResolutionFailed = FALSE;
  $urlIsWellFormed = $partsAreSanitized = $schemeIsSanitized = $hostIsSanitized = FALSE;
  $allowedSchemes = array('https');
  $urlParts = array();
  $parsedHost = '';
  // / A URL is judged as it was given & is never passed through sanitize(), which removes the characters a URL is built from.
  // / One holding anything a URL may not hold is refused, so nothing below meets a space, a quote, a backslash or a control character.
  // / The parts parse_url() finds are sanitized below, one by one.
  $urlIsWellFormed = (is_string($StreamURL) && $StreamURL !== '' && strlen($StreamURL) <= 2048 && preg_match('#^[A-Za-z0-9._~:/?\#\[\]@!$&()*+,;=%-]+\z#', $StreamURL) === 1);
  if ($Verbose) logEntry('Inspecting Stream URL: '.($urlIsWellFormed ? $StreamURL : 'one holding characters a URL cannot').'.');
  // / Check if plain http stream URLs are allowed by config.php.
  if ($engineAllowPlainHTTP) array_push($allowedSchemes, 'http');
  // / A URL that is not well formed is unresolvable before we even parse it.
  if (!$urlIsWellFormed) $StreamURLResolutionFailed = TRUE;
  else {
    // / Parse the provided URL to gather DNS information.
    // / parse_url returns FALSE if the response was seriously malformed.
    $urlParts = @parse_url($StreamURL);
    // / If the parse_url response is malformed, then consider the URL unresolvable.
    if (!is_array($urlParts)) $StreamURLResolutionFailed = TRUE;
    // / If the parse_url response makes sense, then keep going.
    else {
      // / The host as the URL names it is kept, so a host sanitize() changes can be told apart below.
      $parsedHost = isset($urlParts['host']) ? strtolower((string)$urlParts['host']) : '';
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
          // / A host sanitize() had to change is not the host the URL names, so it is refused rather than looked up.
          // / curl & FFMPEG read the host from the URL itself, & a pin made for another name would not bind them.
          list ($URLHost, $hostIsSanitized) = sanitize(strtolower($urlParts['host']), TRUE);
          if (!$hostIsSanitized or $URLHost === '' or $URLHost !== $parsedHost) $StreamURLResolutionFailed = TRUE;
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
      // / A literal address gets the same treatment as a resolved one. An operator who
      // / permitted a range means it whether they typed a name or an address.
      list ($hostIsPublic, $permittedByRange) = addressMayBeReached($URLHost);
      if ($hostIsPublic) {
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
  purgeSensitiveMemory($EnableMemoryProtection, $permittedByRange, $hostIsPublic, $networkPolicy, $engineAllowPlainHTTP, $allowedSchemes, $urlParts, $parsedHost, $StreamDNSContainsLAN, $urlIsWellFormed, $partsAreSanitized, $schemeIsSanitized, $hostIsSanitized);
  return array($InspectionFailed, $StreamURLResolutionFailed, $StreamContainsLAN, $LookupFailed, $URLHost, $URLPort, $URLScheme, $URLIP); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to make an operator supplied base URL safe to resolve stream URIs against.
// / Accepts the raw text. Returns whether it is usable, the rebuilt URL & a reason.
// /
// / sanitize() cannot be used on a URL, because it removes the characters a URL is built from.
// / https://example.com/hls/master.m3u8 would come back as example.comhlsmaster.m3u8, a filename & not an address.
// / So this rebuilds rather than filters, judging each component & assembling a new URL from the parts that passed.
// / The scheme must be http or https, because file, gopher & dict are how an SSRF reaches what a fetch never could.
// / The host goes through sanitize(), because a host is filename shaped, as gatherRemoteHostInfo() treats it.
// / The path keeps only what a path may contain, & a traversal segment is removed rather than resolved.
// / A query string & a fragment are discarded, because a base is a directory to resolve against.
// / Nothing here decides whether an address may be reached.
// / Every URL this produces still goes through gatherRemoteHostInfo() & isPubliclyRoutableIP(), as one from a playlist does.
function normalizeStreamBaseURL($suppliedBaseURL) {
  // / Set variables.
  global $EnableMemoryProtection;
  $BaseIsUsable = FALSE;
  $BaseURL = $BaseReason = '';
  $urlParts = array();
  $cleanHost = $cleanPath = $cleanPort = '';
  $hostIsSanitized = FALSE;
  $suppliedBaseURL = trim((string)$suppliedBaseURL);
  if ($suppliedBaseURL === '') $BaseReason = 'No base address was supplied.';
  else if (strlen($suppliedBaseURL) > 2048) $BaseReason = 'The base address is longer than any real one.';
  else {
    $urlParts = @parse_url($suppliedBaseURL);
    if (!is_array($urlParts)) $BaseReason = 'The base address could not be read as a URL.';
    else if (empty($urlParts['scheme']) or !in_array(strtolower($urlParts['scheme']), array('http', 'https'), TRUE)) $BaseReason = 'The base address must begin with http:// or https://.';
    else if (empty($urlParts['host'])) $BaseReason = 'The base address names no host.';
    else {
      list ($cleanHost, $hostIsSanitized) = sanitize(strtolower($urlParts['host']), TRUE);
      if (!$hostIsSanitized or $cleanHost === '' or $cleanHost !== strtolower($urlParts['host'])) $BaseReason = 'The host in the base address contains something a host name cannot.';
      else {
        // / A port is a number & anything else in that position is not a port.
        if (isset($urlParts['port'])) $cleanPort = ':'.(int)$urlParts['port'];
        $cleanPath = isset($urlParts['path']) ? (string)$urlParts['path'] : '/';
        $cleanPath = preg_replace('#[^A-Za-z0-9._~/%+-]#', '', $cleanPath);
        $cleanPath = str_replace('..', '', $cleanPath);
        $cleanPath = preg_replace('#/+#', '/', $cleanPath);
        if ($cleanPath === '' or $cleanPath[0] !== '/') $cleanPath = '/'.$cleanPath;
        $BaseURL = strtolower($urlParts['scheme']).'://'.$cleanHost.$cleanPort.$cleanPath;
        $BaseIsUsable = TRUE;
        $BaseReason = 'The base address was accepted as '.$BaseURL.'.'; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $urlParts, $cleanHost, $cleanPath, $cleanPort, $hostIsSanitized, $suppliedBaseURL);
  return array($BaseIsUsable, $BaseURL, $BaseReason); }
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
// / It validates only the syntax of each address & whether it may be reached.
// / An address may be reached by the rule addressMayBeReached() applies everywhere else, so a range an administrator permitted is permitted here too.
// / This is SSRF machinery, & it knows nothing about streams, playlists or any other format.
// / A pipeline that must decide whether a reference is safe to follow calls this rather than writing its own.
// / A pipeline must never own SSRF protection, so one that fetches something gets it & cannot opt out by accident.
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
  // / $IPMatches = flat array of every valid address confirmed by filter_var that may be reached.
  preg_match_all($ip4Pattern, $streamFileContents, $ip4Temp);
  preg_match_all($ip6Pattern, $streamFileContents, $ip6Temp);
  // / Each address is judged once, so a playlist naming one permitted server a thousand times writes one warning & not a thousand.
  $ipMatchesTemp = array_values(array_unique(array_merge($ip4Temp[0], $ip6Temp[0])));
  if (!empty($ipMatchesTemp)) {
    foreach ($ipMatchesTemp as $ipMatch) {
      // / Strip brackets from URL-form IPv6 (https://[::1]/) before validating.
      $ipMatch = trim($ipMatch, '[]');
      // / Not a real IP at all. Probably a regex over-match. Discard silently, do NOT flag.
      if (!filter_var($ipMatch, FILTER_VALIDATE_IP)) continue;
      // / Set a flag if the entry that was found is a genuine raw IP address.
      $StreamContainsIP = TRUE;
      // / Check whether each extracted IP is on a local / private subnet, or something we should not be probing.
      if (addressMayBeReached($ipMatch)[0]) array_push($IPMatches, $ipMatch);
      else {
        // / Set a flag if the IP address that was found appears to be on a local or private subnet.
        $StreamContainsLAN = TRUE;
        // / Stop as soon as we find a dangerous IP address. There is no need to continue validating a malicious file.
        break; } } }
  // / Count the number of addresses that may be reached found before we stopped.
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
// / This is SSRF machinery & it lives in the core on purpose. See inspectContentForIPs().
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
// / This is the only place the application fetches a remote file for a pipeline.
// / It pins the resolved address, refuses redirects, error pages & DNS rebinding, & reads a bounded number of bytes.
// / Every one of those is something a pipeline author writing their own curl call would forget.
// / $engineAllowPlainHTTP, $engineConnectionTimeout, $engineMaxInspectionBytes & $engineFetchTemp govern every caller.
// / A second pipeline that fetches remote files therefore shares them, which should be a deliberate decision.
// / Files are saved with a numeric name & no extension, so nothing downstream is fooled by a filename.
// / The original URI is kept in the stream record, not on disk.
// / Only the first $engineMaxInspectionBytes bytes are fetched, because this classifies files rather than streaming them.
// / $engineConnectionTimeout is in seconds & used directly, & $engineWatchTimeout is in minutes & converted once here.
// / $ByteCeiling overrides the inspection ceiling for a caller fetching a file rather than reading a playlist.
// / Zero or absent keeps the inspection ceiling.
// / A negative ceiling is no ceiling at all, so the whole file is fetched & is never reported as truncated.
// / An inspection reads enough of a playlist to understand it, while a fetched file is only useful whole.
function downloadRemoteFileForInspection($StreamURL, $URLHost, $URLPort, $URLIP, $URLScheme, $FileNumber, $ByteCeiling = 0) {
  // / Set variables.
  global $Verbose, $DirSep, $EnableMemoryProtection;
  // / The network policy is resolved once & every setting below comes from it.
  $networkPolicy = networkPolicy();
  $engineAllowPlainHTTP = $networkPolicy['AllowPlainHTTP'];
  $engineMaxInspectionBytes = $networkPolicy['MaxInspectionBytes'];
  // / A caller that named its own ceiling gets it, & zero keeps the inspection ceiling.
  // / A negative ceiling lifts the limit, which curl is told by being given no range & no size limit.
  $ceilingIsLifted = ((int)$ByteCeiling < 0);
  if ((int)$ByteCeiling > 0) $engineMaxInspectionBytes = (int)$ByteCeiling;
  $engineConnectionTimeout = $networkPolicy['ConnectionTimeout'];
  $engineWatchTimeout = $networkPolicy['WatchTimeout'];
  $engineFetchTemp = $networkPolicy['FetchTemp'];
  $DownloadFailed = $StreamFileTruncated = TRUE;
  $pinIsComplete = FALSE;
  $curlOutput = array();
  $curlCommand = '';
  $curlExitCode = 1;
  $commandMayRun = FALSE;
  $downloadedBytes = $httpStatus = 0;
  $outputLine = '';
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
    // / The --max-filesize flag enforces the same ceiling when a host ignores -r, by failing the fetch.
    // / A lifted ceiling passes neither flag.
    $curlCommand = 'curl'
      .' --resolve '.escapeshellarg($URLHost.':'.$URLPort.':'.$URLIP)
      .' --proto '.escapeshellarg('='.$protoString)
      .' --proto-redir '.escapeshellarg('='.$protoString)
      .($ceilingIsLifted ? '' : ' -r '.escapeshellarg('0-'.((int)$engineMaxInspectionBytes - 1)).' --max-filesize '.(int)$engineMaxInspectionBytes)
      .' --connect-timeout '.(int)$engineConnectionTimeout
      .' -m '.((int)$engineWatchTimeout * 60)
      .' -sS -o '.escapeshellarg($LocalStreamPath)
      .' -w '.escapeshellarg('\n%{http_code}\n')
      .' -- '.escapeshellarg($StreamURL).' 2>&1';
    // / The fetch runs inside the sandbox, with a network & without a resolver.
    // / curl needs no resolver, because --resolve above hands it the one address the application inspected.
    // / A malformed pin therefore fails rather than letting curl resolve for itself.
    // / The generic profile is used, because curl needs nothing beyond the base sandbox.
    // / The output path is also given as the input path, because the sandbox requires a directory to bind.
    // / sandboxCommand returns whether the command may run & the command, in that order.
    list ($commandMayRun, $curlCommand) = sandboxCommand($curlCommand, $LocalStreamPath, $LocalStreamPath, TRUE, 'generic');
    // / A command that may not run is not run, & the download counts as failed.
    if ($commandMayRun) exec($curlCommand, $curlOutput, $curlExitCode);
    else warningEntry('A stream download was not attempted, because no sandbox could be built.');
    // / -w printed the status the server answered with on a line of its own, & the body went to the file.
    foreach ($curlOutput as $outputLine) if (preg_match('/^[0-9]{3}$/', trim((string)$outputLine)) === 1) redeclare($httpStatus, (int)trim((string)$outputLine));
    // / A successful download requires exit code 0, a status of 200 or 206, AND a file that exists with content.
    // / None of them alone is proof of success.
    // / curl exits 0 for an error page & for a redirect it was not allowed to follow, & neither body is the file.
    if ($curlExitCode === 0 && ($httpStatus === 200 or $httpStatus === 206) && file_exists($LocalStreamPath)) {
      $downloadedBytes = filesize($LocalStreamPath);
      if ($downloadedBytes > 0) $DownloadFailed = FALSE;
      // / A file that filled the entire budget was almost certainly cut short.
      // / We are only holding part of it, so we cannot claim to have inspected the whole thing.
      // / A fetch with no ceiling was never cut short.
      if ($ceilingIsLifted or $downloadedBytes < (int)$engineMaxInspectionBytes) $StreamFileTruncated = FALSE; }
    else if ($Verbose) logEntry('Stream download failed for '.$StreamURL.'. CURL exit code: '.$curlExitCode.', HTTP status: '.$httpStatus.'.');
    // / Log the outcome of this single download.
    if ($Verbose) logEntry('Stream Download Result: '.($DownloadFailed ? 'FAILED' : 'SUCCESS').', File: '.$FileNumber.', Bytes: '.$downloadedBytes.', Truncated: '.($StreamFileTruncated ? 'TRUE' : 'FALSE').'.'); }
  // / Never report a path we did not successfully write to.
  // / The caller reads this file immediately after this function returns.
  // / Whatever a failed fetch wrote is removed, so a failure leaves nothing behind.
  if ($DownloadFailed) {
    if ($LocalStreamPath !== '' && file_exists($LocalStreamPath)) @unlink($LocalStreamPath);
    $LocalStreamPath = ''; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $ByteCeiling, $ceilingIsLifted, $httpStatus, $outputLine, $networkPolicy, $engineAllowPlainHTTP, $engineMaxInspectionBytes, $engineConnectionTimeout, $engineWatchTimeout, $engineFetchTemp, $curlCommand, $curlOutput, $protoString, $curlExitCode, $downloadedBytes, $pinIsComplete, $commandMayRun);
  return array($DownloadFailed, $LocalStreamPath, $StreamFileTruncated); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to supervise a backgrounded FFMPEG stream conversion after the user has been served.
// / Polls the process & kills it once $engineWatchTimeout minutes have elapsed.
// / This is the only thing preventing an abandoned stream from running until PHP or the OS intervenes.
function waitForStream($StreamPID, $newPathname) {
  // / Set variables.
  // / The timeout is a local read from the network policy. It was also declared global, so
  // / the cleanup below destroyed a global of that name on every stream it supervised.
  global $Verbose, $EnableMemoryProtection;
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
// / It forks, & the child drops privilege & never regains it, unlike su, which holds root for the whole call.
// / posix_setuid called as root sets the real, effective & saved identity together, so nothing is left to climb back to.
// / posix_initgroups gives the child the account's own groups & nothing else, so it keeps none of root's.
// / The group is dropped before the account, because changing the account first removes the ability to change the group.
// / The child confirms who it is before it runs anything, & a child that cannot report back is a failure.
// / The output file stays root's, & the child opens it while still root & writes through that handle.
// / The exit code is the child's own exit status, which no account can write, & a child a signal ended did not finish.
// / The child closes every handle it was given, uses /dev/null in their place & leaves root's session before it drops.
// / A host without the pcntl or posix extension is refused, because running a command as root that was meant not to is worse.
function runAsAccount($command, $accountName) {
  // / Set variables.
  global $RunningAsRoot, $EnableMemoryProtection;
  $CommandOutput = array();
  $CommandExitCode = 1;
  $accountRecord = $inheritedStreams = array();
  $outputPath = $outputContents = '';
  $childPid = $childStatus = $waitedPid = 0;
  $dropSucceeded = FALSE;
  $outputHandle = $inheritedStream = $nullInput = $nullOutput = $nullError = NULL;
  if (!$RunningAsRoot) exec((string)$command, $CommandOutput, $CommandExitCode);
  else if (!function_exists('pcntl_fork') or !function_exists('pcntl_wifexited') or !function_exists('posix_setsid') or !function_exists('posix_setuid') or !function_exists('posix_getpwnam') or !function_exists('posix_initgroups')) {
    warningEntry('A command had to run as '.$accountName.' & this PHP has no pcntl or posix extension. It was not run at all, because running it as root would be worse.');
    $CommandExitCode = 1; }
  else {
    $accountRecord = posix_getpwnam((string)$accountName);
    if (!is_array($accountRecord)) warningEntry('The account '.$accountName.' does not exist on this host, so a command that had to run as it was not run.');
    else {
      // / The child writes to a file rather than a pipe, because the parent must not read
      // / from a descriptor the child still owns while it is changing identity.
      // / tempnam creates the file 0600 for root in a folder where nobody else can replace it, &
      // / it stays root's.
      $outputPath = (string)tempnam(sys_get_temp_dir(), 'hrc2run');
      $childPid = ($outputPath === '') ? -1 : pcntl_fork();
      if ($childPid === -1) warningEntry('A command that had to run as '.$accountName.' could not be forked.');
      else if ($childPid === 0) {
        // / Inside the child. Every handle root holds is closed, & the three standard ones are opened
        // / again on /dev/null, so the command holds no terminal, lock or log of root's.
        $inheritedStreams = get_resources('stream');
        foreach ($inheritedStreams as $inheritedStream) @fclose($inheritedStream);
        $nullInput = @fopen('/dev/null', 'rb');
        $nullOutput = @fopen('/dev/null', 'wb');
        $nullError = @fopen('/dev/null', 'wb');
        // / The output is opened while this is still root, & is closed to the command that runs.
        $outputHandle = @fopen($outputPath, 'wbe');
        // / A new session leaves root's terminal behind. Groups first, account second, & none of it
        // / can be undone.
        $dropSucceeded = (posix_setsid() !== -1 && posix_initgroups((string)$accountName, (int)$accountRecord['gid']) && posix_setgid((int)$accountRecord['gid']) && posix_setuid((int)$accountRecord['uid']));
        // / Nothing runs unless the child is the account & nothing more.
        if ($dropSucceeded && (posix_getuid() !== (int)$accountRecord['uid'] or posix_geteuid() !== (int)$accountRecord['uid'] or posix_getgid() !== (int)$accountRecord['gid'] or posix_getegid() !== (int)$accountRecord['gid'] or in_array(0, posix_getgroups(), TRUE))) $dropSucceeded = FALSE;
        if ($dropSucceeded) exec((string)$command, $CommandOutput, $CommandExitCode);
        else {
          $CommandOutput = array('The command was not run, because this process could not become '.$accountName.' & nothing else.');
          $CommandExitCode = 126; }
        if (is_resource($outputHandle)) @fwrite($outputHandle, implode(PHP_EOL, $CommandOutput));
        // / The exit code goes back as this process's own exit status, which only the parent reads.
        exit((is_int($CommandExitCode) && $CommandExitCode >= 0 && $CommandExitCode <= 255) ? $CommandExitCode : 1); }
      else {
        $waitedPid = (int)pcntl_waitpid($childPid, $childStatus);
        $outputContents = (string)@file_get_contents($outputPath);
        @unlink($outputPath);
        if ($outputContents !== '') $CommandOutput = explode(PHP_EOL, $outputContents);
        // / The exit code is the child's own exit status. A child that was not waited for, or that a
        // / signal ended, did not finish, & silence is not success.
        if ($waitedPid === $childPid && pcntl_wifexited($childStatus)) $CommandExitCode = (int)pcntl_wexitstatus($childStatus);
        else {
          $CommandExitCode = 128 + (($waitedPid === $childPid && pcntl_wifsignaled($childStatus)) ? (int)pcntl_wtermsig($childStatus) : 0);
          warningEntry('A command that had to run as '.$accountName.' was ended before it finished, so it is treated as failed.'); } } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $accountRecord, $inheritedStreams, $outputPath, $outputContents, $childPid, $childStatus, $waitedPid, $dropSucceeded, $outputHandle, $inheritedStream, $nullInput, $nullOutput, $nullError, $command, $accountName);
  return array($CommandOutput, $CommandExitCode); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Resource limits.
// / A limit is a share of the host granted to one operation & is expressed as a percentage
// / of a processor & a quantity of memory.
// / limitCommand depends on both of these, so they sit beside it.
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
  // / It exists because a core can be updated without config.php, which is accepted at or above a minimum version.
  // / A type this core learned about since that file was written would otherwise fall to the general default.
  // / For Scan that default is fatal, because a loaded ClamAV signature database is over a gigabyte & 512M gets it killed.
  // / A local fallback the configuration cannot lower by omission stops a version skew from turning a working scanner off.
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
// / A function to say whether this kernel will delegate cgroup controllers to a user.
// / Accepts nothing. Returns TRUE when a user scope could hold a real limit.
// /
// / This is the difference between a host an administrator can fix & one they cannot.
// / An ordinary Linux box that cannot make a scope usually needs lingering enabled, which
// / --fix-permissions does. A NAS or appliance kernel frequently ships with cgroup
// / controllers unavailable to users at all, & no amount of configuration changes that.
// / Telling the second operator to run --fix-permissions sends them round a loop with no
// / end, & they conclude the application is broken when the kernel simply cannot do it.
// /
// / The test is whether the unified cgroup hierarchy exists & offers controllers to
// / delegate. cgroup.controllers listing nothing means the kernel has the hierarchy &
// / hands out no controllers, which is exactly the appliance case.
// / A host with no unified hierarchy at all is the older cgroup v1 layout, which cannot
// / delegate to a user either.
// /
// / A limit is never the only protection. Scheduling priority still applies & a conversion
// / is still bounded, just less precisely, so a FALSE here degrades rather than fails.
function cgroupDelegationIsAvailable() {
  // / Set variables.
  global $EnableMemoryProtection;
  $DelegationIsAvailable = FALSE;
  $controllerFile = $controllerList = '';
  $controllerFile = '/sys/fs/cgroup/cgroup.controllers';
  if (file_exists($controllerFile)) {
    $controllerList = trim((string)@file_get_contents($controllerFile));
    // / A kernel that lists controllers can delegate at least one of them.
    if ($controllerList !== '') $DelegationIsAvailable = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $controllerFile, $controllerList);
  return $DelegationIsAvailable; }
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
        // / The reason matters more than the failure. Two hosts reach this line for
        // / completely different causes & only one of them is fixable.
        // / An ordinary Linux box gets here because lingering is off, & -fp turns it on.
        // / A NAS or appliance kernel frequently ships without cgroup controllers delegated
        // / to a user, & nothing an administrator does will change that. Telling them to run
        // / -fp sends them round a loop that cannot end.
        // / Scheduling priority still applies either way, so a conversion is still bounded,
        // / just less precisely.
        else warningEntry('A resource scope could not be created. '.(cgroupDelegationIsAvailable() ? 'Run the -fp argument as root to enable lingering for '.$ApacheUser.'.' : 'This kernel does not delegate cgroup controllers, which many NAS & appliance kernels do not. There is nothing to fix here & no scope can be made on this host.').' Per conversion limits fall back to scheduling priority alone.'); } }
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
// / A function to ask the operator a question, through whatever the application provides.
// / Accepts the question. Returns their answer, or an empty string when nobody can be asked.
// /
// / The Engine does not know how the application talks to anybody.
// / A command line reads a line from the terminal, & a web request has no operator & must not block waiting for one.
// / So the application names a function in $EngineOperatorPrompt & this calls it.
// / An empty answer is a refusal, & every caller must treat it as one.
// / A question nobody could be asked has not been answered yes, so the destructive branch waiting on a yes does not run.
// / An application that declares no prompt gets a tool that reports & never destroys.
function promptOperator($operatorQuestion) {
  // / Set variables.
  global $EngineOperatorPrompt, $EnableMemoryProtection;
  $OperatorAnswer = '';
  $promptFunction = '';
  $promptFunction = (isset($EngineOperatorPrompt) && is_string($EngineOperatorPrompt)) ? trim($EngineOperatorPrompt) : '';
  if ($promptFunction !== '' && function_exists($promptFunction)) $OperatorAnswer = (string)$promptFunction($operatorQuestion);
  else if ($promptFunction !== '') warningEntry('An operator prompt named '.$promptFunction.' was declared & does not exist. Every question will be refused.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $promptFunction, $operatorQuestion);
  return $OperatorAnswer; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run whatever repair the application declares.
// / Accepts nothing. Returns whether it succeeded & how many things were corrected.
// /
// / The Environment Manager already reads this seam. Setup Core reads the same one, so an
// / application declares how it fixes itself ONCE rather than twice.
// / An application that declares no repair reports that nothing was changed, which is true.
function runApplicationRepair() {
  // / Set variables.
  global $EngineRepairProvider, $EnableMemoryProtection;
  $RepairSucceeded = FALSE;
  $PathsCorrected = 0;
  $repairFunction = '';
  $repairFunction = (isset($EngineRepairProvider) && is_string($EngineRepairProvider)) ? trim($EngineRepairProvider) : '';
  if ($repairFunction !== '' && function_exists($repairFunction)) list ($RepairSucceeded, $PathsCorrected) = $repairFunction();
  else {
    if ($repairFunction !== '') warningEntry('A repair provider named '.$repairFunction.' was declared & does not exist.');
    else warningEntry('This application declares no repair. Nothing was changed.');
    $RepairSucceeded = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $repairFunction);
  return array($RepairSucceeded, $PathsCorrected); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to fetch the configuration model the application declares.
// / Accepts nothing. Returns the model, which is empty when none is declared.
// /
// / An empty model is not an error & is not repaired around. Every caller must check it &
// / refuse the work rather than proceed on nothing. A config utility that repaired against
// / an empty model would delete every setting it could not find.
function collectConfigModel() {
  // / Set variables.
  global $EngineConfigModelProvider, $EngineConfigTemplate, $InstLoc, $DirSep, $EnableMemoryProtection;
  $ConfigModel = array();
  $modelFunction = $templatePath = $sectionName = $variableName = '';
  $templateValues = $templateMatches = $templateMatch = $sectionRecord = $modelledNames = array();
  $guessedType = $variableValue = $appendSection = '';
  $templateText = '';
  $appendSection = 'Unsectioned';
  $modelFunction = (isset($EngineConfigModelProvider) && is_string($EngineConfigModelProvider)) ? trim($EngineConfigModelProvider) : '';
  if ($modelFunction !== '' && function_exists($modelFunction)) $ConfigModel = (array)$modelFunction();
  else if ($modelFunction !== '') warningEntry('A configuration model provider named '.$modelFunction.' was declared & does not exist. Nothing can be repaired or generated.');
  else warningEntry('This application declares no configuration model. The utility can view & back up & nothing else.');
  // / The defaults come from the template & are not in the model at all.
  // / The template is a real configuration at default values, so every default is already in the form it is written back.
  // / Holding the defaults twice would let a model & a config file drift apart.
  // / The model keeps what a config file cannot say about itself: a type, a dependency between settings & an operator owned section.
  // / A template that cannot be read leaves the model as the application gave it, so its own defaults still work.
  // / The template is read with a plain match, because parseConfigFile asks for the model this function returns & would loop.
  // / A setting name is unique across the file, so the section it sits in does not have to be worked out again.
  $templatePath = (isset($EngineConfigTemplate) && trim((string)$EngineConfigTemplate) !== '') ? trim((string)$EngineConfigTemplate) : '';
  if ($templatePath !== '' && !empty($ConfigModel)) {
    if (strpos($templatePath, $DirSep) !== 0) $templatePath = $InstLoc.$DirSep.'Resources'.$DirSep.$templatePath;
    $templateText = (string)@file_get_contents($templatePath);
    if ($templateText === '') warningEntry('The configuration template at '.$templatePath.' could not be read, so the model carries no defaults.');
    else {
      preg_match_all('/^\\$([A-Za-z_][A-Za-z0-9_]*)\\s*=\\s*(.*?);\\s*$/ms', $templateText, $templateMatches, PREG_SET_ORDER);
      foreach ($templateMatches as $templateMatch) $templateValues[$templateMatch[1]] = trim($templateMatch[2]);
      foreach ($ConfigModel as $sectionName => $sectionRecord) {
        foreach ($sectionRecord['Variables'] as $variableName => $variableModel) {
          if (!isset($templateValues[$variableName])) continue;
          $ConfigModel[$sectionName]['Variables'][$variableName]['Default'] = $templateValues[$variableName];
          $modelledNames[$variableName] = TRUE; } } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $modelFunction, $templatePath, $templateText, $sectionName, $variableName, $templateValues, $templateMatches, $templateMatch, $sectionRecord, $variableModel, $modelledNames, $guessedType, $variableValue, $appendSection);
  return $ConfigModel; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report whether each served location is exposed, for a person to read.
// / Accepts nothing. Returns display rows, which are empty when the application serves nothing.
// / A row with a Label is a status line. A row with only Text is a paragraph.
// /
// / Whether a data tree is reachable by a web server is a question only an application with a web server has.
// / An application that serves nothing has nothing to answer, so the section is not shown at all.
// / The Engine answers it for each location the contract gives the served role.
// / A contract that still names $EngineDataPolicyProvider is not read, because that provider is retired.
// / The protection file is repaired as it is checked, because only --setup asks for these rows, as root, while preparing the locations.
function collectDataPolicyFindings() {
  // / Set variables.
  global $EnableMemoryProtection;
  $PolicyFindings = $servedPaths = array();
  $servedPath = $locationName = $policyStatus = $exposureStatus = $exposureDetail = '';
  $policyIsValid = $locationIsProtected = FALSE;
  $servedPaths = servedLocations();
  foreach ($servedPaths as $servedPath) {
    $locationName = basename($servedPath);
    // / Two locations are told apart by a line naming each. One needs no such line.
    if (count($servedPaths) > 1) $PolicyFindings[] = array('Text' => '  '.$servedPath);
    list ($policyIsValid, $policyStatus) = verifyServedProtection($servedPath, TRUE);
    $PolicyFindings[] = array('Label' => 'Protection file', 'Status' => policyDisplayStatus($policyStatus), 'Detail' => describePolicyStatus($locationName.' Directory', $policyStatus));
    list ($locationIsProtected, $exposureStatus, $exposureDetail) = verifyServedExposure($servedPath);
    $PolicyFindings[] = array('Label' => 'Live exposure', 'Status' => strtoupper($exposureStatus), 'Detail' => $exposureDetail);
    if ($exposureStatus === 'exposed') {
      $PolicyFindings[] = array('Text' => '');
      $PolicyFindings[] = array('Text' => '  THE '.strtoupper($locationName).' DIRECTORY IS EXPOSED. A file a user uploads is served back as a');
      $PolicyFindings[] = array('Text' => '  document rather than as a download, so an uploaded SVG runs its own script');
      $PolicyFindings[] = array('Text' => '  in this origin. The protection file is being ignored; Apache reads one only');
      $PolicyFindings[] = array('Text' => '  where AllowOverride is enabled & nginx never reads one at all. Put the rules');
      $PolicyFindings[] = array('Text' => '  in the server configuration. See Documentation/ABOUT_DATA_DIRECTORY_PROTECTION.txt.'); }
    else if ($exposureStatus !== 'protected') {
      $PolicyFindings[] = array('Text' => '');
      $PolicyFindings[] = array('Text' => '  Exposure was NOT established, which is not a pass. If the web server is not');
      $PolicyFindings[] = array('Text' => '  running yet this is expected; re-run the -fp argument once it is.'); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $PolicyFindings is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $servedPaths, $servedPath, $locationName, $policyStatus, $exposureStatus, $exposureDetail, $policyIsValid, $locationIsProtected);
  return $PolicyFindings; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to compare two version numbers numerically & report a minimum match.
// / Accepts the detected version & the minimum version required, in that order.
// / Returns TRUE when the detected version is the same as, or newer than, the required one.
// / A leading v is stripped before comparison, because casting 'v3' to an integer yields 0
// / & silently reduces a three part comparison to a two part one.
// / Comparison is numeric part by part, because a string comparison ranks 24.2 below 7.6 &
// / ranks 3.10 below 3.9.
// / A version that cannot be parsed is REFUSED. An unknown build cannot be cleared.
function compareVersionMinimum($detectedVersion, $requiredVersion) {
  // / Set variables.
  global $EnableMemoryProtection;
  $VersionIsCurrent = FALSE;
  $cleanDetected = $cleanRequired = '';
  $detectedParts = $requiredParts = array();
  $detectedMajor = $detectedMinor = $detectedPatch = 0;
  $requiredMajor = $requiredMinor = $requiredPatch = 0;
  $cleanDetected = ltrim(trim((string)$detectedVersion), 'vV');
  $cleanRequired = ltrim(trim((string)$requiredVersion), 'vV');
  // / A blank requirement means any version will do.
  if ($cleanRequired === '') $VersionIsCurrent = TRUE;
  else if ($cleanDetected === '') $VersionIsCurrent = FALSE;
  else {
    $detectedParts = explode('.', $cleanDetected);
    $requiredParts = explode('.', $cleanRequired);
    // / A version whose leading part is not a number is not a version.
    if (!ctype_digit(trim($detectedParts[0]))) $VersionIsCurrent = FALSE;
    else {
      $detectedMajor = (int)$detectedParts[0];
      $detectedMinor = isset($detectedParts[1]) ? (int)$detectedParts[1] : 0;
      $detectedPatch = isset($detectedParts[2]) ? (int)$detectedParts[2] : 0;
      $requiredMajor = (int)$requiredParts[0];
      $requiredMinor = isset($requiredParts[1]) ? (int)$requiredParts[1] : 0;
      $requiredPatch = isset($requiredParts[2]) ? (int)$requiredParts[2] : 0;
      if ($detectedMajor > $requiredMajor) $VersionIsCurrent = TRUE;
      else if ($detectedMajor === $requiredMajor) {
        if ($detectedMinor > $requiredMinor) $VersionIsCurrent = TRUE;
        else if ($detectedMinor === $requiredMinor && $detectedPatch >= $requiredPatch) $VersionIsCurrent = TRUE; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cleanDetected, $cleanRequired, $detectedParts, $requiredParts, $detectedMajor, $detectedMinor, $detectedPatch, $requiredMajor, $requiredMinor, $requiredPatch, $detectedVersion, $requiredVersion);
  return $VersionIsCurrent; }
// / -----------------------------------------------------------------------------------
// / A function to report what kind of machine this is.
// / Accepts nothing. Returns a short architecture name & the raw machine string, in that
// / order.
// /
// / The short name is one of x86_64, arm64, arm or unknown, & it is what a manifest entry
// / matches against. The raw string is kept because a host reports itself in more ways than
// / are worth enumerating & an operator reading a log wants to see what the machine
// / actually said.
// /
// / This exists because not every dependency runs EVERYWHERE.
// / A bundled binary is built for one architecture. A package has a different name, or no
// / package at all, on another. An application that assumes x86 tells a Raspberry Pi owner
// / their installation is broken when the truth is that one optional tool was never going
// / to work there.
// / Knowing the architecture is what lets a dependency say so instead.
// /
// / uname is used rather than php_uname(), because php_uname('m') reports what PHP was
// / BUILT for rather than what it is running on. A 32 bit PHP on a 64 bit ARM host answers
// / armv7l & the host is arm64, which is exactly the case this needs to get right.
function detectHostArchitecture() {
  // / Set variables.
  global $EnableMemoryProtection;
  $ArchitectureName = 'unknown';
  $MachineString = '';
  $commandOutput = array();
  $commandExitCode = 1;
  exec('uname -m 2>/dev/null', $commandOutput, $commandExitCode);
  if ($commandExitCode === 0 && isset($commandOutput[0])) $MachineString = strtolower(trim($commandOutput[0]));
  if ($MachineString === '') $MachineString = strtolower(trim((string)php_uname('m')));
  // / Each family answers to several names & the list is what hosts actually report.
  if (in_array($MachineString, array('x86_64', 'amd64'), TRUE)) $ArchitectureName = 'x86_64';
  else if (in_array($MachineString, array('aarch64', 'arm64', 'aarch64_be', 'armv8b', 'armv8l'), TRUE)) $ArchitectureName = 'arm64';
  else if (strpos($MachineString, 'arm') === 0) $ArchitectureName = 'arm';
  else if (in_array($MachineString, array('i386', 'i486', 'i586', 'i686'), TRUE)) $ArchitectureName = 'x86';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $commandOutput, $commandExitCode);
  return array($ArchitectureName, $MachineString); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to confirm this server can actually isolate a dependency invocation, & to say how.
// / Accepts nothing.
// / Returns the absolute path of a WORKING bubblewrap binary or FALSE, the launcher that built the
// / sandbox, & whether an attempt was cut short, in that order. The launcher is escaped already,
// / carries --unshare-user --disable-userns where they build a sandbox too, & goes where the binary
// / would have gone.
// / A path is returned only when a real sandbox was launched, so a caller holding one may build a command with it directly.
// / This is a capability check, because bubblewrap may be installed & still be unable to build a namespace.
// / The kernel, an AppArmor profile or a container runtime can each refuse unprivileged user namespaces.
// / A dependency that cannot be isolated is refused rather than run unprotected, so FALSE here is the strongest signal there is.
// /
// / On a restricting kernel every sandbox runs this application's own copy of bubblewrap.
// / Ubuntu 23.10 & later permit an unprivileged namespace only to a program under an AppArmor profile that grants userns.
// / This application's profile is attached to its own copy, which root alone can change & only root & the web server's account can run.
// / The host keeps the restriction every other program relies on, & bubblewrap stays as it was for each of them.
// / The copy is tried first wherever the restriction is in force, & the system's bubblewrap after it.
// / A copy that is not root's alone is never tried.
// /
// / The third answer says whether an attempt was cut short rather than refused.
// / Bubblewrap that refuses a sandbox exits 1, while a time limit, a signal or a program that could not start ends it otherwise.
// / The kernel check never lifts the restriction on an attempt that ended that way, because it says nothing about the kernel.
function verifySandboxLauncher() {
  // / Set variables.
  global $Verbose, $RunningAsRoot, $CurrentUser, $ApacheUser, $EnableMemoryProtection;
  $BwrapBinary = FALSE;
  $SandboxLauncher = $commandHint = '';
  $ProbeWasInterrupted = FALSE;
  $bwrapReasons = $candidateBinaries = $bwrapOutput = $lockOutput = array();
  $locatedBinary = $privateBinary = $candidateBinary = $bwrapCommand = $candidateLabel = $probeArguments = $lockCommand = '';
  $userNamespaceLock = ' --unshare-user --disable-userns';
  $bwrapExitCode = $copyExitCode = $lockExitCode = 1;
  $probeAsAccount = $copyIsTried = $privateIsSafe = $copyIsJudged = FALSE;
  $locatedBinary = locateDependency('bwrap');
  $privateBinary = privateBwrapPath();
  $probeAsAccount = ($RunningAsRoot && $CurrentUser !== $ApacheUser);
  $copyIsJudged = sandboxCopyIsJudgedHere();
  // / The copy is tried first wherever the restriction is in force, & never anywhere else, where the
  // / system's bubblewrap needs nothing the copy has. A copy of a bubblewrap since removed is left
  // / alone, because nothing keeps it current, & so is one the account asking can neither run nor
  // / hand to an account that can.
  $copyIsTried = (namespaceRestriction() === '1' && sandboxBinaryIsNeeded() && $locatedBinary !== '' && $copyIsJudged);
  if ($copyIsTried) {
    $privateIsSafe = sandboxBinaryIsSafe($privateBinary);
    if ($privateIsSafe) $candidateBinaries[engineApplicationName().'\'s own bubblewrap'] = $privateBinary;
    else array_push($bwrapReasons, engineApplicationName().'\'s own bubblewrap is not installed at '.$privateBinary.', or is not root\'s alone.'); }
  if ($locatedBinary !== '' && $locatedBinary !== $privateBinary) $candidateBinaries['The system\'s bubblewrap'] = $locatedBinary;
  if (count($candidateBinaries) > 0) {
    foreach ($candidateBinaries as $candidateLabel => $candidateBinary) {
      if ($BwrapBinary !== FALSE) continue;
      // / Launch the smallest possible sandbox & run a command that does nothing but exit.
      // / This proves the kernel will actually grant the namespaces a real render depends on.
      // / The ro-bind-try entries are optional & are skipped on systems that do not have them.
      $probeArguments = ' --unshare-all'
        .' --die-with-parent'
        .' --ro-bind /usr /usr'
        .' --ro-bind-try /lib /lib'
        .' --ro-bind-try /lib64 /lib64'
        .' --ro-bind-try /bin /bin'
        .' --proc /proc'
        .' --dev /dev'
        .' --tmpfs /tmp'
        .' /usr/bin/true 2>&1';
      $bwrapCommand = 'timeout -k 5 10 '.escapeshellarg($candidateBinary).$probeArguments;
      // / A probe run as root proves nothing. Root can always create a namespace, so a check
      // / made by -fp would report a working sandbox while every web request still failed.
      // / When this is running as root the probe is re-run as the account that will actually
      // / run conversions, which is the only answer worth reporting.
      // / runAsAccount forks & drops rather than spawning a shell through su, so the
      // / privilege this call exists to give up is not held while the command runs.
      // / stderr is KEPT. bwrap names the exact reason it could not build a namespace, &
      // / discarding it left an operator with an exit code & nothing to act on.
      $bwrapOutput = array();
      if ($probeAsAccount) list ($bwrapOutput, $bwrapExitCode) = runAsAccount($bwrapCommand, $ApacheUser);
      else exec($bwrapCommand, $bwrapOutput, $bwrapExitCode);
      if ($candidateBinary === $privateBinary) $copyExitCode = $bwrapExitCode;
      if ($bwrapExitCode !== 0 && $bwrapExitCode !== 1) $ProbeWasInterrupted = TRUE;
      if ($bwrapExitCode === 0) {
        $BwrapBinary = $candidateBinary;
        $SandboxLauncher = escapeshellarg($candidateBinary);
        // / A sandbox that can make user namespaces can spend every one the web server account has.
        // / Bubblewrap 0.8 & later stops that with --disable-userns, which needs --unshare-user. The
        // / same sandbox is built again with both, & the launcher carries them only where it was built.
        $lockCommand = 'timeout -k 5 10 '.escapeshellarg($candidateBinary).$userNamespaceLock.$probeArguments;
        $lockOutput = array();
        if ($probeAsAccount) list ($lockOutput, $lockExitCode) = runAsAccount($lockCommand, $ApacheUser);
        else exec($lockCommand, $lockOutput, $lockExitCode);
        if ($lockExitCode === 0) $SandboxLauncher = $SandboxLauncher.$userNamespaceLock;
        else if ($Verbose) logEntry('Bubblewrap could not stop a sandbox making user namespaces. '.trim((string)(isset($lockOutput[0]) ? $lockOutput[0] : 'No reason was reported.')).' Sandboxes run without --disable-userns.'); }
      else array_push($bwrapReasons, $candidateLabel.': '.trim((string)(isset($bwrapOutput[0]) ? $bwrapOutput[0] : 'no reason was reported'))); } }
  // / The warning names what answers the failure. A copy that is not there is installed with the
  // / dependencies & never by -fp, one that could not be started is explained by -v, & an account
  // / that cannot run the copy is sent to root, who can see what the web server's account can build.
  if (!$copyIsJudged && namespaceRestriction() === '1' && sandboxBinaryIsNeeded()) $commandHint = ' Only root & '.$ApacheUser.'\'s group may run '.engineApplicationName().'\'s own bubblewrap, so run -v as root to see what '.$ApacheUser.' can build.';
  else if ($copyIsTried && !$privateIsSafe) $commandHint = ' Run --setup --update-depends as root to install '.engineApplicationName().'\'s own bubblewrap & the AppArmor profile attached to it.';
  else if ($copyIsTried && ($copyExitCode === 126 or $copyExitCode === 127)) $commandHint = ' '.engineApplicationName().'\'s own bubblewrap could not be started, & -v run as root says why.';
  else $commandHint = ' Run the -fp argument as root to install the AppArmor profile an unprivileged user namespace needs.';
  if ($BwrapBinary === FALSE && count($bwrapReasons) > 0) warningEntry('Bubblewrap could not build a sandbox'.($probeAsAccount ? ' as '.$ApacheUser : '').'. '.implode(' ', $bwrapReasons).$commandHint);
  if ($Verbose) logEntry('Bubblewrap Sandbox Check: '.($BwrapBinary === FALSE ? 'FAILED, Reason: '.implode(' ', $bwrapReasons) : 'PASSED, Using: '.$SandboxLauncher).'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $commandHint, $bwrapReasons, $candidateBinaries, $bwrapOutput, $lockOutput, $locatedBinary, $privateBinary, $candidateBinary, $bwrapCommand, $candidateLabel, $probeArguments, $lockCommand, $userNamespaceLock, $bwrapExitCode, $copyExitCode, $lockExitCode, $probeAsAccount, $copyIsTried, $privateIsSafe, $copyIsJudged);
  return array($BwrapBinary, $SandboxLauncher, $ProbeWasInterrupted); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to confirm this server can actually isolate a dependency invocation.
// / Returns the absolute path of a WORKING bubblewrap binary, or FALSE.
// / A path is returned ONLY when a real sandbox was launched successfully.
// / It asks verifySandboxLauncher(), which also says how the sandbox was built.
// / A caller that builds a command asks that function instead, because the launcher is what the command runs.
// / Nothing in this application calls it, & it is kept for anything built on the Engine that does.
function verifyBwrap() {
  // / Set variables.
  global $EnableMemoryProtection;
  $BwrapBinary = FALSE;
  $sandboxLauncher = '';
  list ($BwrapBinary, $sandboxLauncher) = verifySandboxLauncher();
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $sandboxLauncher);
  return $BwrapBinary; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to assemble the binds that put a calling pipeline's own files in place of host files.
// / Accepts the profile name & the read only resource the call carries, in that order.
// / Returns the flags as a string, which may be empty.
// / A profile declares Overlays as host paths, each naming the file within the call's read only
// / resource that takes its place inside the namespace, & only there. The host file is never
// / touched. Each pipeline passes its own resource, so each pipeline's policy is the one its own
// / conversions see, & two pipelines that need different policies for one tool each get theirs.
// / A host path that is not a plain file here is skipped, because a bind needs somewhere to land,
// / & so is one reached through a link, because a link may lead somewhere the namespace does not
// / show & bubblewrap would then refuse the whole sandbox. A name that is not a plain file inside
// / the resource is skipped too.
// / These are emitted after the profile's own binds, so a host path the profile shows is covered,
// / & one it does not show, as the tesseract profile shows no ImageMagick folder, is made inside the
// / namespace to hold the pipeline's file.
function sandboxOverlayFlags($sandboxProfile, $readOnlyResourcePath) {
  // / Set variables.
  global $EngineSandboxProfiles, $EnableMemoryProtection;
  $OverlayFlags = '';
  $cleanProfile = strtolower(trim((string)$sandboxProfile));
  $sandboxProfiles = (isset($EngineSandboxProfiles) && is_array($EngineSandboxProfiles)) ? $EngineSandboxProfiles : array();
  $resourcePath = rtrim(trim((string)$readOnlyResourcePath), DIRECTORY_SEPARATOR);
  $hostPath = $resourceName = $overlayPath = '';
  if ($resourcePath !== '' && is_dir($resourcePath) && isset($sandboxProfiles[$cleanProfile]['Overlays']) && is_array($sandboxProfiles[$cleanProfile]['Overlays'])) {
    foreach ($sandboxProfiles[$cleanProfile]['Overlays'] as $hostPath => $resourceName) {
      $overlayPath = $resourcePath.DIRECTORY_SEPARATOR.(string)$resourceName;
      if ((string)$resourceName === '' or strpos((string)$resourceName, '..') !== FALSE or substr((string)$resourceName, 0, 1) === DIRECTORY_SEPARATOR) continue;
      if (substr((string)$hostPath, 0, 1) !== DIRECTORY_SEPARATOR or !is_file((string)$hostPath) or (string)@realpath((string)$hostPath) !== (string)$hostPath) continue;
      if (is_file($overlayPath) && !is_link($overlayPath)) $OverlayFlags = $OverlayFlags.' --ro-bind '.escapeshellarg($overlayPath).' '.escapeshellarg((string)$hostPath); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cleanProfile, $sandboxProfiles, $resourcePath, $hostPath, $resourceName, $overlayPath, $sandboxProfile, $readOnlyResourcePath);
  return $OverlayFlags; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether a conversion is refused when no sandbox can be built.
// / Accepts nothing. Returns TRUE when a sandbox is required.
// /
// / In a container --Require Sandbox On Docker-- decides & --Require Sandbox-- is not read.
// / Anywhere else --Require Sandbox-- decides & --Require Sandbox On Docker-- is not read.
// / sandboxCommand(), the operating environment check & -v all ask this one function, so none of them can disagree.
function sandboxIsRequired() {
  // / Set variables.
  global $RequireSandbox, $RequireSandboxOnDocker, $RunningInContainer;
  $SandboxIsRequired = !empty($RunningInContainer) ? (bool)$RequireSandboxOnDocker : (bool)$RequireSandbox;
  return $SandboxIsRequired; }
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
// / The sixth argument is a directory the command needs that is neither its input nor its output, bound read only.
// / A converter loading a module, a template or a profile from the installation reaches it only this way.
// / A caller with nothing to bind passes nothing & is unaffected.
// / A profile's Overlays are found in this directory, so a pipeline that carries its own policy passes the folder holding it.
// / The seventh argument is a set of environment variables the command needs, keyed by name.
// / A caller must never prefix them onto the command, because bwrap execs it without a shell.
// / A NAME=value prefix would be read as the name of a binary, & the failure would look like a missing program.
// / A value naming the read only resource is rewritten to its path inside the namespace.
// / An unsandboxed command takes the same variables through env, because nice or systemd-run in front of it runs its first word.
// / The eighth argument is a hosts file the application wrote, bound at /etc/hosts.
// / It is the ONLY name resolution a networked command is permitted. The application
// / resolves every name itself, inspects the address, & writes the approved pairs here.
// / A name absent from the file cannot resolve, because no resolver is bound either.
// / A redirect to an unapproved name therefore fails at lookup, & a certificate still
// / validates, because the tool connects by the real name & only the address was pinned.
function sandboxCommand($command, $inputPath, $outputPath, $allowNetwork, $sandboxProfile, $readOnlyResourcePath = '', $environmentVariables = array(), $pinnedHostsFile = '') {
  // / Set variables.
  global $Verbose, $ThrowSandboxWarning, $RunningInContainer, $EnableMemoryProtection;
  $CommandMayRun = FALSE;
  $bwrapBinary = FALSE;
  // / This initializes TRUE rather than FALSE, because for this variable TRUE is the safe
  // / state. It is overwritten unconditionally below & the initial value is never read.
  $sandboxIsRequired = TRUE;
  $SandboxedCommand = $networkFlag = $mountFlags = $workingDir = $profileFlags = $resourceFlags = $overlayFlags = $sandboxLauncher = '';
  $environmentFlags = $environmentPrefix = $environmentName = $environmentValue = '';
  $rewriteSearch = $rewriteReplace = array();
  $inputDir = $outputDir = $sandboxInput = $sandboxOutput = '';
  // / The launcher is what built the proving sandbox, so it is what builds this one. On a kernel that
  // / restricts unprivileged namespaces it is this application's own copy of bubblewrap.
  list ($bwrapBinary, $sandboxLauncher) = verifySandboxLauncher();
  // / Collect the mounts this one dependency needs. Nothing else receives them.
  $profileFlags = sandboxProfileFlags($sandboxProfile);
  // / The files the calling pipeline carries in place of host files, such as its own policy.
  $overlayFlags = sandboxOverlayFlags($sandboxProfile, $readOnlyResourcePath);
  // / A container that CAN build a sandbox still gets one. This only decides what happens
  // / when it cannot.
  $sandboxIsRequired = sandboxIsRequired();
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
  // / A sandboxed command takes --setenv & an unsandboxed one takes them through env.
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
    // / env runs unsandboxed, as the command it carries does here, & takes nothing but the
    // / variables the caller named, each escaped.
    $SandboxedCommand = ($environmentPrefix === '' ? '' : 'env '.$environmentPrefix).$command;
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
    $SandboxedCommand = $sandboxLauncher
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
      // / A pipeline's own file covers the host's only after the profile has bound the host's.
      .$overlayFlags
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
  purgeSensitiveMemory($EnableMemoryProtection, $pinnedHostsFile, $environmentFlags, $environmentPrefix, $environmentName, $environmentValue, $environmentVariables, $resourceFlags, $rewriteSearch, $rewriteReplace, $readOnlyResourcePath, $bwrapBinary, $sandboxLauncher, $overlayFlags, $sandboxIsRequired, $networkFlag, $mountFlags, $profileFlags, $workingDir, $inputDir, $outputDir, $sandboxInput, $sandboxOutput, $command, $inputPath, $outputPath, $allowNetwork, $sandboxProfile);
  return array($CommandMayRun, $SandboxedCommand); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The Engine's own commands.
// / Every application of this shape needs the listener, the managers, setup, configuration, the pipelines, repair & cleaning.
// / An application serves -v, -h & anything beginning --app itself, & offers every other command to dispatchEngineCommand.
// / It handles only what comes back unhandled, which is its own commands or a mistake.
// / A branch reads the application's name & entry point from the contract, & -fp reaches the repair through the declared provider.
// / It passes question three of ABOUT_ENGINE_CONTRACT.txt, because every command here needs the Engine.
// / The application refuses all of them before calling here when there is no Engine.
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report which command each of the Engine's options belongs to.
// / Accepts nothing. Returns an array keyed by option & valued by the command that owns it.
// / An option typed on its own is the most common command line mistake there is. The
// / application reports it, & it names the owning command from this map, because the
// / Engine is what knows which options its own commands take.
function engineSubOptionOwners() {
  // / Set variables.
  global $EnableMemoryProtection;
  $SubOptionOwners = array();
  $SubOptionOwners = array(
    '--check-depends' => '--setup', '--install-depends' => '--setup', '--install-deps' => '--setup',
    '--update-depends' => '--setup', '--uninstall-depends' => '--setup', '--output-supply-chain' => '--setup',
    '--detect-capabilities' => '--setup',
    '--install-complete' => '--setup', '--reinstall-existing' => '--setup', '--subsystem' => '--setup',
    '--install-service' => '--setup',
    '--reset-all-defaults' => '--config', '--reset-default-section' => '--config',
    '--reset-default-variable' => '--config', '--backup' => '--config', '--repair' => '--config',
    '--view' => '--config',
    // / --pipeline reaches into the components themselves, the way --setup reaches into
    // / dependencies & --config reaches into settings. Same shape, same place.
    '--list' => '--pipeline', '--describe' => '--pipeline', '--verify' => '--pipeline',
    '--check-depends' => '--pipeline',
    // / --workflow reads, checks, exports & installs workflows, the way --pipeline reads pipelines.
    '--check' => '--workflow', '--export' => '--workflow', '--install' => '--workflow', '--remove' => '--workflow');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection);
  return $SubOptionOwners; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to refuse a command to a user who may not run it, & say how to run it.
// / Accepts the command, the user it was refused to & the warning to log, in that order.
// / Returns the exit status a refusal earns, which is 1.
// / Three places refused this way with the same three lines, two in the Engine & -u in the
// / application, so the wording lives here once.
function refuseCommandForUser($cliCommand, $cliWhoami, $refusalWarning) {
  // / Set variables.
  global $Lol, $EnableMemoryProtection;
  $RefusalStatus = 1;
  warningEntry((string)$refusalWarning);
  print($Lol.'The '.$cliCommand.' command is only available to root.'.$Lol);
  print('You are running as '.$cliWhoami.'.'.$Lol);
  print($Lol.'  sudo php '.engineApplicationEntryPoint().' '.$cliCommand.$Lol.$Lol);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cliCommand, $cliWhoami, $refusalWarning);
  return $RefusalStatus; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run --account, the administrator's command for accounts.
// / Accepts the argument list, the action & whether the operator confirmed in advance, in that order. Returns the exit status.
// / Every account & every user's files are the web server account's, & root never works inside a folder that account can change.
// / So root asks before a removal, because an administrator is at the terminal, & hands the command to that account with the answer given,
// / as it hands -k over. The arguments are handed as they were typed, each quoted for the shell.
// / The web server account loads the Authentication Core & the Authentication Manager, even where accounts are off, & runs it.
function runEngineAccountCommand($cliArguments, $cliTarget, $cliConfirmed) {
  // / Set variables.
  global $RunningAsRoot, $ApacheUser, $InstLoc, $Lol, $EnableMemoryProtection;
  $CommandStatus = 1;
  $actionIsConfirmed = $authenticationIsLoaded = $emailIsLoaded = $managerIsLoaded = FALSE;
  $handedCommand = $handedArgument = $managerVersion = $accountUsername = '';
  $accountOutput = array();
  $accountExit = 1;
  if ($RunningAsRoot) {
    // / A removal asks first & names the account it removes. One naming no account is handed over unasked, & the web server user says what is missing.
    $accountUsername = (is_array($cliArguments) && isset($cliArguments[2]) && is_string($cliArguments[2])) ? trim($cliArguments[2]) : '';
    if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,63}$/', $accountUsername) !== 1) $accountUsername = '';
    $actionIsConfirmed = ($cliTarget !== '--remove' or $accountUsername === '') ? TRUE : confirmDestructiveAction('This removes the account '.$accountUsername.' & every file its user keeps.', $cliConfirmed);
    if ($actionIsConfirmed) {
      logEntry('Command line invocation. Handing --account to '.$ApacheUser.'.');
      $handedCommand = 'php '.escapeshellarg($InstLoc.DIRECTORY_SEPARATOR.engineApplicationEntryPoint()).' --account';
      foreach (array_slice((array)$cliArguments, 1, 3) as $handedArgument) $handedCommand .= ' '.escapeshellarg((string)$handedArgument);
      $handedCommand .= (($cliTarget === '--remove' && $accountUsername !== '') ? ' -y' : '').' 2>&1';
      list ($accountOutput, $accountExit) = runAsAccount($handedCommand, (string)$ApacheUser);
      print(implode($Lol, $accountOutput).$Lol);
      if ($accountExit === 0) $CommandStatus = 0; } }
  else {
    list ($authenticationIsLoaded, $emailIsLoaded) = loadAuthenticationCores(TRUE);
    if ($authenticationIsLoaded) list ($managerIsLoaded, $managerVersion) = loadEngineManager('authenticationManager.php');
    if (!$authenticationIsLoaded or !$managerIsLoaded) print($Lol.'The Authentication Core or the Authentication Manager is unavailable. Run  php '.engineApplicationEntryPoint().' -v  to see which.'.$Lol.$Lol);
    else $CommandStatus = runAccountCommand($cliArguments, $cliTarget, $cliConfirmed); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $actionIsConfirmed, $authenticationIsLoaded, $emailIsLoaded, $managerIsLoaded, $handedCommand, $handedArgument, $managerVersion, $accountUsername, $accountOutput, $accountExit, $cliArguments, $cliTarget, $cliConfirmed);
  return $CommandStatus; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to start one of the manager processes the listener runs.
// / Accepts the command, its target & a second target, in that order.
// / Returns the exit status, which is 0 unless the process halted. A manager that starts
// / does not return until it stops.
function runEngineManagerEntry($cliCommand, $cliTarget, $cliSecondTarget) {
  // / Set variables.
  global $ResourceAwarenessActive, $EnableMemoryProtection;
  $CommandStatus = 0;
  $cliStartupKey = '';
  // / Handle the internal Core Manager entry point.
  // / This is never typed by an administrator. It is invoked by the -l argument with a
  // / key derived from the install secret & is refused without one.
  if ($cliCommand === '--start-core-manager') {
    if (!$ResourceAwarenessActive) errorEntry('A Core Manager start was requested but the component is unavailable!', 31009, TRUE);
    else {
      // / The key arrives in the environment. A key on the command line is still read,
      // / so a listener launched by the previous release can still start its children.
      $cliStartupKey = readTransportedStartupKey();
      if ($cliStartupKey === '') $cliStartupKey = (string)$cliTarget;
      dispatchManagerRole('core-manager', $cliStartupKey); } }
  // / Handle the foreground listener entry point.
  // / A service manager runs this. It does not fork & does not return, so systemd can
  // / watch the real process, restart it when it dies & report its true state.
  // / It derives its own key rather than being handed one, because a unit file cannot
  // / compute a time bucketed HMAC. Authorization is unchanged. A standard user holds
  // / a per user secret & the key they derive will not validate.
  else if ($cliCommand === '--run-core-manager') {
    if (!$ResourceAwarenessActive) errorEntry('The foreground listener was requested but the Core Manager component is unavailable!', 31016, TRUE);
    else runCoreManagerForeground(); }
  // / Handle the internal subordinate manager entry point.
  else if ($cliCommand === '--start-manager') {
    if (!$ResourceAwarenessActive) errorEntry('A subordinate manager start was requested but the component is unavailable!', 31012, TRUE);
    else {
      // / The key arrives in the environment. A key on the command line is still
      // / accepted, because a listener spawned by the previous release is still running
      // / when this one is installed & its children must be able to start.
      $cliStartupKey = readTransportedStartupKey();
      if ($cliStartupKey === '') $cliStartupKey = (string)$cliSecondTarget;
      dispatchManagerRole($cliTarget, $cliStartupKey); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cliStartupKey, $cliCommand, $cliTarget, $cliSecondTarget);
  return $CommandStatus; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report what is running, for the --status command.
  // / Handle the --status argument. Available to any user, reports what is running.
  // / The identity is reported because a standard user cannot read the socket directory
  // / & would otherwise be told the listener is stopped whether it is or not.
// / Accepts the user it is reported to & whether that user may run the listener.
// / Returns the exit status. A report is produced or the request was refused before it got here.
function showEngineStatus($cliWhoami, $cliListenerAuthorized) {
  // / Set variables.
  global $Lol, $InstLoc, $ApacheUser, $RunningAsRoot, $ResourceAwarenessActive, $EnableMemoryProtection;
  $CommandStatus = 0;
  $cliListenerRunning = $cliStateWasRead = FALSE;
  $cliStatus = $cliAccountOutput = $cliEnvironmentState = $cliDisk = array();
  $cliAccountExit = 1;
  $cliStateStatus = $cliPassLine = '';
  // / Root reports none of this itself, because the listener state lives in the web server
  // / account's folder & readManagerState refuses root. It hands --status to that account &
  // / relays what the account saw, which is the truth about a listener that account runs.
  if ($RunningAsRoot) {
    print($Lol.'Running as          '.$cliWhoami.'. Asking '.$ApacheUser.'.'.$Lol);
    list ($cliAccountOutput, $cliAccountExit) = runAsAccount('php '.escapeshellarg($InstLoc.DIRECTORY_SEPARATOR.engineApplicationEntryPoint()).' --status 2>&1', (string)$ApacheUser);
    print(implode($Lol, $cliAccountOutput).$Lol);
    if ($cliAccountExit !== 0) $CommandStatus = 1; }
  else {
    print($Lol.'Running as          '.$cliWhoami.$Lol);
    print('Listener commands   '.($cliListenerAuthorized ? 'AVAILABLE' : 'NOT AVAILABLE to this user').$Lol);
    if (!$ResourceAwarenessActive) print('Resource awareness  UNAVAILABLE, the Core Manager component is missing or does not match this core.'.$Lol);
    // / An account that cannot read the socket folder cannot ask, so it is not asked.
    else if (!$cliListenerAuthorized) print('Listener            NOT ASKED, only '.$ApacheUser.' may read the listener state. Run --status as root.'.$Lol);
    else {
      list ($cliListenerRunning, $cliStatus) = reportListenerStatus();
      print('Listener            '.($cliListenerRunning ? 'RUNNING as process '.$cliStatus['CoreManagerPid'] : 'STOPPED').$Lol);
      print('Subordinates        '.count($cliStatus['Subordinates']).$Lol);
      print('Tracked workers     '.$cliStatus['TrackedWorkers'].$Lol);
      if (isset($cliStatus['SessionLocations'])) print('Mapped sessions     '.$cliStatus['SessionLocations'].$Lol);
      if (isset($cliStatus['Budget']['RemainingBudget'])) print('Remaining budget    '.$cliStatus['Budget']['RemainingBudget'].' of '.$cliStatus['Budget']['TotalBudget'].$Lol);
      // / Each disk holding a data location, as the listener last measured it, & the reserve it keeps on each.
      // / A listener that has not polled yet has measured nothing, & says so rather than printing nothing.
      if ($cliStatus['Answered']) {
        if (empty($cliStatus['Storage'])) print('Storage             NOT MEASURED YET'.$Lol);
        foreach ($cliStatus['Storage'] as $cliDisk) print('Storage             '.strtoupper((string)($cliDisk['State'] ?? 'unknown')).', '.describeByteCount((int)($cliDisk['FreeBytes'] ?? 0)).' free of '.describeByteCount((int)($cliDisk['TotalBytes'] ?? 0)).', '.implode(' & ', (array)($cliDisk['Locations'] ?? array())).$Lol);
        print('Storage reserve     '.($cliStatus['StorageReserveBytes'] > 0 ? describeByteCount($cliStatus['StorageReserveBytes']) : 'NONE').', warned at '.($cliStatus['StorageWarningPercentage'] > 0 ? $cliStatus['StorageWarningPercentage'].'% free' : 'NOTHING').$Lol); } }
    // / The last Environment Manager pass, from the state file root writes for every reader.
    // / It answers whether anything has been quietly wrong since somebody last looked.
    list ($cliStateWasRead, $cliEnvironmentState, $cliStateStatus) = readEnvironmentState();
    if ($cliStateWasRead) {
      $cliPassLine = (isset($cliEnvironmentState['LastPassHuman']) ? (string)$cliEnvironmentState['LastPassHuman'] : 'At an unknown time').'. '.(isset($cliEnvironmentState['ProblemCount']) ? (int)$cliEnvironmentState['ProblemCount'] : 0).' problem(s), repair '.((isset($cliEnvironmentState['RepairIsPermitted']) && $cliEnvironmentState['RepairIsPermitted'] === TRUE) ? 'permitted' : 'not permitted').'.';
      if (isset($cliEnvironmentState['LastRepairDay']) && (string)$cliEnvironmentState['LastRepairDay'] !== '') $cliPassLine = $cliPassLine.' Last repair '.(string)$cliEnvironmentState['LastRepairDay'].'.';
      print('Environment pass    '.$cliPassLine.$Lol); }
    else if ($cliStateStatus === 'absent') print('Environment pass    NONE RECORDED'.$Lol);
    else if ($cliStateStatus === 'unreadable') print('Environment pass    NOT READABLE by this user.'.$Lol);
    else print('Environment pass    UNREADABLE, not written by the Environment Manager'.$Lol);
    print($Lol); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cliListenerRunning, $cliStateWasRead, $cliStatus, $cliAccountOutput, $cliEnvironmentState, $cliAccountExit, $cliStateStatus, $cliPassLine, $cliDisk, $cliWhoami, $cliListenerAuthorized);
  return $CommandStatus; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report on the installed pipelines, for the --pipeline command.
  // / --pipeline reaches into the components the way --setup reaches into dependencies.
  // / It reports what is INSTALLED & verified rather than what is pinned, because a
  // / pinned pipeline that does not load is exactly the failure worth surfacing. Two
  // / scanner pipelines were pinned, installed, valid & never enumerated for an entire
  // / release, & nothing reported it.
// / Accepts the action & the pipeline it names, in that order. Returns the exit status.
function runEnginePipelineCommand($cliTarget, $cliSecondTarget) {
  // / Set variables.
  // / $InstLoc & $CoreLoaded are declared, because --check-depends requires depends.php inside this function.
  // / That file reads both at file scope, & without them every entry it builds warns of an undefined variable.
  global $Lol, $Pipelines, $DependsManifest, $CoreLoaded, $InstLoc, $EnableMemoryProtection;
  $CommandStatus = 0;
  $pipelineAction = $pipelineWanted = $pipelineName = $pinnedVersion = $manifestPath = '';
  $declaredDependency = $missingDependency = '';
  $pipelineRecord = $pinnedPipelines = $manifestNames = $missingDependencies = $manifestEntry = array();
  $pipelineFailures = $declaringCount = 0;
  $pipelineAction = ($cliTarget !== '') ? $cliTarget : '--list';
  if (!is_array($Pipelines) or empty($Pipelines)) $CommandStatus = 1;
  if (!is_array($Pipelines) or empty($Pipelines)) print($Lol.'No pipeline is loaded. The Pipeline Core reports why.'.$Lol.$Lol);
  else if ($pipelineAction === '--list' or $pipelineAction === '') {
    print($Lol.'Installed pipelines'.$Lol.$Lol);
    printf('  %-14s %-11s %-18s %s'.$Lol, 'PIPELINE', 'KIND', 'USAGE', 'VERSION');
    foreach ($Pipelines as $pipelineName => $pipelineRecord) printf('  %-14s %-11s %-18s %s'.$Lol, $pipelineName, $pipelineRecord['Kind'], implode(', ', $pipelineRecord['Usage']), $pipelineRecord['Version']);
    print($Lol); }
  else if ($pipelineAction === '--describe') {
    $pipelineWanted = ($cliSecondTarget !== '') ? $cliSecondTarget : '';
    // / A pipeline is named without regard to case, because every target is lowercased & a pipeline is keyed by its declared name.
    // / The declared name is printed.
    foreach (array_keys($Pipelines) as $pipelineName) {
      if (strcasecmp((string)$pipelineName, $pipelineWanted) === 0) $pipelineWanted = (string)$pipelineName; }
    if ($pipelineWanted === '') print($Lol.'Name a pipeline to describe. --pipeline --describe Image'.$Lol.$Lol);
    else if (!isset($Pipelines[$pipelineWanted])) {
      $CommandStatus = 1;
      print($Lol.'No pipeline named '.$pipelineWanted.' is loaded.'.$Lol.$Lol); }
    else {
      $pipelineRecord = $Pipelines[$pipelineWanted];
      print($Lol.$pipelineWanted.$Lol.$Lol);
      printf('  %-16s %s'.$Lol, 'Kind', $pipelineRecord['Kind']);
      printf('  %-16s %s'.$Lol, 'Usage', implode(', ', $pipelineRecord['Usage']));
      printf('  %-16s %s'.$Lol, 'Version', $pipelineRecord['Version']);
      printf('  %-16s %s'.$Lol, 'Entry point', $pipelineRecord['EntryPoint']);
      printf('  %-16s %s'.$Lol, 'Subsystem', isset($pipelineRecord['Subsystem']) ? $pipelineRecord['Subsystem'] : 'none');
      // / The record keeps the declared formats as Input & Output.
      printf('  %-16s %s'.$Lol, 'Reads', empty($pipelineRecord['Input']) ? 'every file' : implode(' ', $pipelineRecord['Input']));
      printf('  %-16s %s'.$Lol, 'Writes', empty($pipelineRecord['Output']) ? 'nothing' : implode(' ', $pipelineRecord['Output']));
      print($Lol); } }
  else if ($pipelineAction === '--verify') {
    // / Pinned against loaded, which is the one comparison nothing else makes.
    $pinnedPipelines = getAcceptedPipelines();
    $pipelineFailures = 0;
    print($Lol.'Pinned against loaded'.$Lol.$Lol);
    foreach ($pinnedPipelines as $pipelineName => $pinnedVersion) {
      if (isset($Pipelines[$pipelineName])) printf('  %-14s %s'.$Lol, $pipelineName, 'OK, '.$Pipelines[$pipelineName]['Version']);
      else { $pipelineFailures++; printf('  %-14s %s'.$Lol, $pipelineName, 'PINNED & NOT LOADED, expected '.$pinnedVersion); } }
    if ($pipelineFailures > 0) $CommandStatus = 1;
    if ($pipelineFailures > 0) print($Lol.$pipelineFailures.' pinned pipeline(s) did not load. See the warnings above.'.$Lol.$Lol);
    else print($Lol.'All '.count($pinnedPipelines).' pinned pipeline(s) loaded.'.$Lol.$Lol); }
  else if ($pipelineAction === '--check-depends') {
    // / What the pipelines declare, against what the manifest holds.
    // / A pipeline names the binaries it shells out to. The manifest decides what
    // / gets installed. Nothing compared the two, so a pipeline could depend on a
    // / tool no installation would ever be told to install.
    // / It REPORTS rather than writes. Adding an entry to the manifest is an edit to
    // / a file the application owns, & doing it silently during a boot that happens
    // / on every request is not a repair, it is a surprise.
    $manifestNames = array();
    // / The manifest is a global the Dependency Core has already loaded. There is no
    // / accessor for it & inventing one here would be a second way to read the same
    // / array, which is how two readers drift.
    // / The manifest is loaded by the Dependency Core & the Dependency Core only loads
    // / for --setup, so at this point the global is empty. It is required here when
    // / it is not already present.
    // / Checked rather than assumed: reporting every dependency as missing, including
    // / two that were plainly in the manifest, is what surfaced this.
    if (!isset($DependsManifest) or !is_array($DependsManifest)) {
      $manifestPath = contractPath('depends.php');
      if (file_exists($manifestPath)) require_once($manifestPath); }
    if (isset($DependsManifest) && is_array($DependsManifest)) foreach ($DependsManifest as $manifestEntry) $manifestNames[] = (string)$manifestEntry['Name'];
    else {
      $CommandStatus = 1;
      warningEntry('The dependency manifest could not be read, so nothing could be compared against it.'); }
    $missingDependencies = array();
    foreach ($Pipelines as $pipelineName => $pipelineRecord) {
      if (empty($pipelineRecord['Dependencies'])) continue;
      foreach ($pipelineRecord['Dependencies'] as $declaredDependency) {
        if (in_array($declaredDependency, $manifestNames, TRUE)) continue;
        $missingDependencies[] = $pipelineName.' needs '.$declaredDependency; } }
    print($Lol.'Pipeline dependencies against the manifest'.$Lol.$Lol);
    $declaringCount = 0;
    foreach ($Pipelines as $pipelineName => $pipelineRecord) {
      if (empty($pipelineRecord['Dependencies'])) continue;
      $declaringCount++;
      printf('  %-14s %s'.$Lol, $pipelineName, implode(', ', $pipelineRecord['Dependencies'])); }
    if ($declaringCount === 0) print('  No pipeline declares a dependency yet.'.$Lol);
    if (!empty($missingDependencies)) $CommandStatus = 1;
    if (empty($missingDependencies)) print($Lol.'Every declared dependency is in the manifest.'.$Lol.$Lol);
    else {
      print($Lol.'Not in the manifest. These would never be installed.'.$Lol);
      foreach ($missingDependencies as $missingDependency) print('  '.$missingDependency.$Lol);
      print($Lol.'Add them to Resources/Contract/depends.php.'.$Lol.$Lol); } }
  else {
    $CommandStatus = 2;
    print($Lol.'Unrecognized. --pipeline accepts --list, --describe, --verify & --check-depends.'.$Lol.$Lol); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $pipelineAction, $pipelineWanted, $pipelineName, $pinnedVersion, $manifestPath, $declaredDependency, $missingDependency, $pipelineRecord, $pinnedPipelines, $manifestNames, $missingDependencies, $manifestEntry, $pipelineFailures, $declaringCount, $cliTarget, $cliSecondTarget);
  return $CommandStatus; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run the configuration utility, for the --config command.
  // / The configuration utility. Setup Core owns the model, so it is the only
  // / component this path requires.
// / Accepts the argument list & whether the operator confirmed in advance, in that order.
// / Returns the exit status.
function runEngineConfigCommand($cliArguments, $cliConfirmed) {
  // / Set variables.
  global $Lol, $DirSep, $RequiredSetupCoreVersion, $EnableMemoryProtection;
  $CommandStatus = 0;
  $cliSetupIsAvailable = $cliCommandSucceeded = FALSE;
  $cliSetupVersion = '';
  list ($cliSetupIsAvailable, $cliSetupVersion) = verifyCoreComponent('Setup Core', 'Engine'.$DirSep.'Cores'.$DirSep.'setupCore.php', 'SetupCoreVersion', $RequiredSetupCoreVersion);
  if (!$cliSetupIsAvailable) {
    print($Lol.'The Setup Core component is unavailable.'.$Lol);
    $CommandStatus = 1;
    if ($cliSetupVersion === '') print('Resources/Engine/Cores/setupCore.php is missing, unreadable, or reports no version.'.$Lol.$Lol);
    else print('It reports v'.ltrim($cliSetupVersion, 'vV').' & this core requires v'.ltrim((string)$RequiredSetupCoreVersion, 'vV').'.'.$Lol.$Lol); }
  else {
    logEntry('Command line invocation. Running the configuration utility.');
    // / Every mode is resolved from the argument list, so the order they were typed
    // / in does not matter.
    if (in_array('--reset-all-defaults', $cliArguments, TRUE)) $cliCommandSucceeded = runConfigUtility('', 'reset-all', '', $cliConfirmed);
    else if (extractCliOption($cliArguments, '--reset-default-section') !== '') $cliCommandSucceeded = runConfigUtility('', 'reset-section', extractCliOption($cliArguments, '--reset-default-section'), $cliConfirmed);
    else if (extractCliOption($cliArguments, '--reset-default-variable') !== '') $cliCommandSucceeded = runConfigUtility('', 'reset-variable', extractCliOption($cliArguments, '--reset-default-variable'), $cliConfirmed);
    else if (extractCliOption($cliArguments, '--backup') !== '') $cliCommandSucceeded = runConfigUtility('', 'backup', extractCliOption($cliArguments, '--backup'), $cliConfirmed);
    else if (in_array('--repair', $cliArguments, TRUE)) $cliCommandSucceeded = runConfigUtility('', 'repair', '', $cliConfirmed);
    else if (in_array('--view', $cliArguments, TRUE)) $cliCommandSucceeded = runConfigUtility('', 'view', '', $cliConfirmed);
    else $cliCommandSucceeded = runConfigUtility('', 'interactive', '', $cliConfirmed);
    if (!$cliCommandSucceeded) $CommandStatus = 1; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cliSetupIsAvailable, $cliCommandSucceeded, $cliSetupVersion, $cliArguments, $cliConfirmed);
  return $CommandStatus; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to manage dependencies & installation, for the --setup command.
  // / Everything --setup owns. Dependency Core is required. Setup Core is loaded only
  // / by the two options that also configure & repair.
// / Accepts the argument list, the target & whether the operator confirmed in advance, in
// / that order. Returns the exit status.
// / The application's pins for Setup Core & Dependency Core are read as globals, as they
// / were here before the move. Who pins an Engine core is recorded as an open question.
function runEngineSetupCommand($cliArguments, $cliTarget, $cliConfirmed) {
  // / Set variables.
  global $Lol, $DirSep, $RunningAsRoot, $RequiredSetupCoreVersion, $RequiredDependencyCoreVersion, $RequiredDependsVersion, $EnableMemoryProtection;
  $CommandStatus = 0;
  $cliDependencyIsAvailable = $cliManifestIsAvailable = $cliDependenciesReady = $cliCommandSucceeded = FALSE;
  $cliCacheWasBuilt = $cliSetupSucceeded = $cliSetupIsAvailable = $cliTimerInstalled = FALSE;
  $cliSubsystemIsKnown = TRUE;
  $cliDependencyVersion = $cliSubsystem = $cliManifestVersion = $cliSubsystemName = $cliCommandDetail = $cliTimerStatus = '';
  $cliDependencyToken = $cliSetupVersion = '';
  $cliManifestEntries = $cliManifestEntry = $cliSubsystemNames = $cliDependencyFindings = array();
  $cliOptionalProblems = $cliDetectedCount = $cliSetupCount = 0;
  list ($cliDependencyIsAvailable, $cliDependencyVersion) = verifyCoreComponent('Dependency Core', 'Engine'.$DirSep.'Cores'.$DirSep.'dependencyCore.php', 'DependencyCoreVersion', $RequiredDependencyCoreVersion);
  $cliSubsystem = extractCliOption($cliArguments, '--subsystem');
  // / A subsystem is checked against the manifest before anything acts on it.
  // / Every --setup option that takes one matches it loosely, so a name that matched nothing would act on nothing & exit 0.
  // / It is only judged when the manifest loads, because a manifest that does not is reported by the command itself.
  if ($cliDependencyIsAvailable && $cliSubsystem !== '' && file_exists(contractPath('depends.php')) && function_exists('verifyDependsManifest')) {
    list ($cliManifestIsAvailable, $cliManifestEntries, $cliManifestVersion) = verifyDependsManifest($RequiredDependsVersion);
    if ($cliManifestIsAvailable && is_array($cliManifestEntries)) {
      $cliSubsystemIsKnown = FALSE;
      foreach ($cliManifestEntries as $cliManifestEntry) {
        if (!isset($cliManifestEntry['Subsystem'])) continue;
        $cliSubsystemNames[] = (string)$cliManifestEntry['Subsystem'];
        if (stripos((string)$cliManifestEntry['Subsystem'], $cliSubsystem) !== FALSE) $cliSubsystemIsKnown = TRUE; } } }
  if (!$cliDependencyIsAvailable) {
    print($Lol.'The Dependency Core component is unavailable.'.$Lol);
    $CommandStatus = 1;
    if ($cliDependencyVersion === '') print('Resources/Engine/Cores/dependencyCore.php is missing, unreadable, or reports no version.'.$Lol.$Lol);
    else print('It reports v'.ltrim($cliDependencyVersion, 'vV').' & this core requires v'.ltrim((string)$RequiredDependencyCoreVersion, 'vV').'.'.$Lol.$Lol); }
  // / A subsystem nothing belongs to is an argument that was not understood.
  else if (!$cliSubsystemIsKnown) {
    warningEntry('A --setup command named a subsystem the dependency manifest does not declare.');
    $CommandStatus = 2;
    print($Lol.'No dependency belongs to a subsystem named '.$cliSubsystem.'. Nothing was done.'.$Lol);
    // / One per line, because a subsystem name can itself contain a comma.
    print('Subsystems the manifest declares.'.$Lol);
    foreach (array_values(array_unique($cliSubsystemNames)) as $cliSubsystemName) print('  '.$cliSubsystemName.$Lol);
    print($Lol); }
  // / Reading the machine needs no authorization at all.
  else if (in_array('--check-depends', $cliArguments, TRUE)) {
    logEntry('Command line invocation. Checking dependencies.');
    // / Two of these three are not read & are still named, because list() assigns by
    // / position & dropping one would silently shift the others.
    // / checkDepends returns the verdict as well as the findings, & this block uses
    // / only the findings. Do not narrow the call to suit the caller.
    list ($cliDependenciesReady, $cliDependencyFindings, $cliOptionalProblems) = checkDepends($cliSubsystem);
    // / A check fails when what it checks is not ready, the way a configuration test does.
    // / An optional dependency that is missing costs a feature & does not fail the check.
    if (!$cliDependenciesReady) $CommandStatus = 1;
    showDependencyFindings($cliDependencyFindings);
    // / The verdict is printed by showDependencyFindings(), because the component that owns the table owns its summary.
    print($Lol); }
  else if (in_array('--output-supply-chain', $cliArguments, TRUE) or extractCliOption($cliArguments, '--output-supply-chain') !== '') {
    logEntry('Command line invocation. Writing a supply chain report.');
    list ($cliCommandSucceeded, $cliCommandDetail) = outputSupplyChain(extractCliOption($cliArguments, '--output-supply-chain'));
    if (!$cliCommandSucceeded) $CommandStatus = 1; }
  // / Capability detection asks every installed tool what it can read & write, &
  // / writes the answer to a cache. It reads the machine & changes nothing else, so
  // / it needs no authorization token. It does spawn a process for every dependency
  // / that can answer, which is exactly why it happens here & never in a request.
  else if (in_array('--detect-capabilities', $cliArguments, TRUE)) {
    logEntry('Command line invocation. Detecting dependency capabilities.');
    if (!function_exists('buildCapabilityCache')) $CommandStatus = 1;
    if (!function_exists('buildCapabilityCache')) errorEntry('Capability detection was requested but the Dependency Core component is unavailable!', 33002, FALSE);
    else {
      list ($cliCacheWasBuilt, $cliDetectedCount) = buildCapabilityCache();
      if (!$cliCacheWasBuilt) $CommandStatus = 1;
      if (!$cliCacheWasBuilt) print($Lol.'The capability cache could not be written. The log says why.'.$Lol.$Lol);
      else {
        print($Lol.'Capability detection complete.'.$Lol);
        print('  '.str_pad('Dependencies asked', 28).$cliDetectedCount.' answered'.$Lol);
        print($Lol.'A dependency that cannot answer is recorded as unknown. Its pipeline declaration stands.'.$Lol.$Lol); } } }
  // / Everything below rewrites the machine & carries a token derived from the
  // / install secret. The token proves the request came through this core.
  else if (in_array('--install-depends', $cliArguments, TRUE) or in_array('--install-deps', $cliArguments, TRUE)) {
    logEntry('Command line invocation. Installing dependencies.');
    $cliDependencyToken = deriveStartupKey('dependency-write');
    list ($cliSetupSucceeded, $cliSetupCount) = installDepends($cliDependencyToken, $cliSubsystem, $cliConfirmed);
    if (!$cliSetupSucceeded) $CommandStatus = 1; }
  else if (in_array('--update-depends', $cliArguments, TRUE)) {
    logEntry('Command line invocation. Upgrading dependencies.');
    $cliDependencyToken = deriveStartupKey('dependency-write');
    list ($cliSetupSucceeded, $cliSetupCount) = updateDepends($cliDependencyToken, $cliSubsystem, $cliConfirmed);
    if (!$cliSetupSucceeded) $CommandStatus = 1; }
  else if (in_array('--uninstall-depends', $cliArguments, TRUE)) {
    warningEntry('Command line invocation. Removing dependencies.');
    $cliDependencyToken = deriveStartupKey('dependency-write');
    list ($cliSetupSucceeded, $cliSetupCount) = uninstallDepends($cliDependencyToken, $cliSubsystem, $cliConfirmed);
    if (!$cliSetupSucceeded) $CommandStatus = 1; }
  // / The listener service unit, generated from the live configuration, & the Environment Manager's timer, never enabled here.
  // / Setup Core owns both. Dependency Core is already loaded by the time we get here.
  else if (in_array('--install-service', $cliArguments, TRUE)) {
    list ($cliSetupIsAvailable, $cliSetupVersion) = verifyCoreComponent('Setup Core', 'Engine'.$DirSep.'Cores'.$DirSep.'setupCore.php', 'SetupCoreVersion', $RequiredSetupCoreVersion);
    if (!$cliSetupIsAvailable) {
      $CommandStatus = 1;
      print($Lol.'This operation needs the Setup Core component, which is unavailable.'.$Lol.$Lol); }
    else if (!$RunningAsRoot) {
      warningEntry('A service unit installation was refused for an unauthorized user.');
      $CommandStatus = 1;
      print($Lol.'Installing a service unit is only available to root.'.$Lol);
      print($Lol.'  sudo php '.engineApplicationEntryPoint().' --setup --install-service'.$Lol.$Lol); }
    else {
      logEntry('Command line invocation. Installing the listener service unit & the Environment Manager timer.');
      print($Lol.'Installing the listener service unit.'.$Lol);
      list ($cliCommandSucceeded, $cliCommandDetail) = installListenerService(TRUE, TRUE);
      if (!$cliCommandSucceeded) $CommandStatus = 1;
      print($Lol.'Installing the Environment Manager timer.'.$Lol);
      list ($cliTimerInstalled, $cliTimerStatus) = installEnvironmentTimer(FALSE);
      // / A host without systemd skips the timer & says so, & that is not a failure.
      if (!$cliTimerInstalled && $cliTimerStatus !== 'skipped') $CommandStatus = 1;
      print($Lol); } }
  // / These two install dependencies AND configure, so both components are needed.
  else if (in_array('--install-complete', $cliArguments, TRUE) or in_array('--reinstall-existing', $cliArguments, TRUE)) {
    list ($cliSetupIsAvailable, $cliSetupVersion) = verifyCoreComponent('Setup Core', 'Engine'.$DirSep.'Cores'.$DirSep.'setupCore.php', 'SetupCoreVersion', $RequiredSetupCoreVersion);
    if (!$cliSetupIsAvailable) {
      print($Lol.'This operation also needs the Setup Core component, which is unavailable.'.$Lol);
      $CommandStatus = 1;
      print('Dependencies can still be managed with --setup --check-depends & --install-depends.'.$Lol.$Lol); }
    else {
      $cliDependencyToken = deriveStartupKey('dependency-write');
      if (in_array('--install-complete', $cliArguments, TRUE)) {
        logEntry('Command line invocation. Performing a complete installation.');
        list ($cliCommandSucceeded, $cliCommandDetail) = runCompleteInstall($cliDependencyToken, $cliConfirmed);
        if (!$cliCommandSucceeded) $CommandStatus = 1; }
      else {
        logEntry('Command line invocation. Reinstalling in place.');
        $cliCommandSucceeded = runReinstallExisting($cliTarget, $cliConfirmed);
        if (!$cliCommandSucceeded) $CommandStatus = 1; } } }
  // / --setup with no option is a request to see what --setup can do.
  else {
    print($Lol.'Setup options'.$Lol);
    print('  --check-depends              Report the state of every dependency.'.$Lol);
    print('  --install-depends            Install everything absent or too old.'.$Lol);
    print('  --update-depends             Upgrade everything already installed.'.$Lol);
    print('  --uninstall-depends          Remove a subsystem. Requires --subsystem.'.$Lol);
    print('  --detect-capabilities       Ask every installed tool what it can read & write.'.$Lol);
    print('  --output-supply-chain[=path] Write a supply chain audit template.'.$Lol);
    print('  --install-service            Install the listener service & the Environment Manager timer.'.$Lol);
    print('                               The timer is not enabled.'.$Lol);
    print('  --install-complete           Install '.engineApplicationName().' & every dependency.'.$Lol);
    print('  --reinstall-existing         Reinstall this installation in place.'.$Lol);
    print('  --subsystem=<Name>           Restrict the operation to one subsystem.'.$Lol);
    print('  -y, --yes                    Skip the confirmation prompt.'.$Lol);
    print($Lol.'Every option above is used WITH --setup, never on its own.'.$Lol);
    print('  sudo php '.engineApplicationEntryPoint().' --setup --check-depends'.$Lol.$Lol); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cliDependencyIsAvailable, $cliManifestIsAvailable, $cliDependenciesReady, $cliCommandSucceeded, $cliCacheWasBuilt, $cliSetupSucceeded, $cliSetupIsAvailable, $cliTimerInstalled, $cliSubsystemIsKnown, $cliDependencyVersion, $cliSubsystem, $cliManifestVersion, $cliSubsystemName, $cliCommandDetail, $cliTimerStatus, $cliDependencyToken, $cliSetupVersion, $cliManifestEntries, $cliManifestEntry, $cliSubsystemNames, $cliDependencyFindings, $cliOptionalProblems, $cliDetectedCount, $cliSetupCount, $cliArguments, $cliTarget, $cliConfirmed);
  return $CommandStatus; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to operate the listener & the workers it tracks.
  // / Handle every listener command through one gate.
  // / Refusing here rather than falling through means a real command is never reported
  // / as an unrecognized one, which sent an authorized user hunting for a typo.
// / Accepts the command, its target, whether the operator confirmed in advance, the user
// / & whether that user may run the listener, in that order. Returns the exit status.
function runEngineListenerCommand($cliCommand, $cliTarget, $cliConfirmed, $cliWhoami, $cliListenerAuthorized) {
  // / Set variables.
  global $Lol, $InstLoc, $ApacheUser, $RunningAsRoot, $ResourceAwarenessActive, $ManagerSocketTimeout, $EnableMemoryProtection;
  $CommandStatus = 0;
  $cliActionConfirmed = $cliMessageDelivered = $cliWorkerManagerLoaded = FALSE;
  $cliMessageReply = $cliAccountOutput = array();
  $cliWorkerManagerVersion = $cliHandedCommand = '';
  $cliAccountExit = 1;
  if (!$cliListenerAuthorized) {
    warningEntry('A listener command was refused for an unauthorized user.');
    $CommandStatus = 1;
    print($Lol.'The '.$cliCommand.' command is only available to root or '.$ApacheUser.'.'.$Lol);
    print('You are running as '.$cliWhoami.'.'.$Lol);
    print($Lol.'  sudo php '.engineApplicationEntryPoint().' '.$cliCommand.$Lol);
    print('  sudo -u '.$ApacheUser.' php '.engineApplicationEntryPoint().' '.$cliCommand.$Lol.$Lol);
    print('A standard user\'s secret cannot start a listener a worker can reach.'.$Lol.$Lol); }
  else if (!$ResourceAwarenessActive) {
    $CommandStatus = 1;
    print($Lol.'Resource awareness is unavailable. The Core Manager component is missing or does not match this core.'.$Lol.$Lol); }
  // / Root operates none of the running listener, because the manager list & the processes it names are the web server account's.
  // / Root confirms a destructive command itself, because an administrator is at the terminal.
  // / It then hands the command to that account, as -l is handed over, with the answer already given.
  // / The account signals only what it could already signal.
  // / -l is not here, because startCoreManagerListener already hands it over.
  // / -k with no target stops through that same account below, once it is no longer root.
  else if ($RunningAsRoot && ($cliCommand === '-k' or $cliCommand === '--kill' or $cliCommand === '--kill-all-workers' or $cliCommand === '--kill-every-worker')) {
    if ($cliCommand === '--kill-all-workers') $cliActionConfirmed = confirmDestructiveAction('This ends every tracked conversion in progress. Users will lose work.', $cliConfirmed);
    else if ($cliCommand === '--kill-every-worker') $cliActionConfirmed = confirmDestructiveAction('This ends EVERY PHP process owned by '.$ApacheUser.' on this host. Other applications sharing this server, such as WordPress or OwnCloud, will lose every session in progress.', $cliConfirmed);
    else $cliActionConfirmed = TRUE;
    if (!$cliActionConfirmed) $CommandStatus = 1;
    else {
      logEntry('Command line invocation. Handing '.$cliCommand.' to '.$ApacheUser.'.');
      // / -y is appended ONLY for the two commands that prompt, so the account does not ask
      // / again with no terminal to answer on. -k never prompts, & -k reads its target from the
      // / second argument, so appending -y there made the account read -y as a worker to kill.
      $cliHandedCommand = 'php '.escapeshellarg($InstLoc.DIRECTORY_SEPARATOR.engineApplicationEntryPoint()).' '.escapeshellarg($cliCommand)
        .($cliTarget !== '' ? ' '.escapeshellarg($cliTarget) : '')
        .(($cliCommand === '--kill-all-workers' or $cliCommand === '--kill-every-worker') ? ' -y' : '').' 2>&1';
      list ($cliAccountOutput, $cliAccountExit) = runAsAccount($cliHandedCommand, (string)$ApacheUser);
      print(implode($Lol, $cliAccountOutput).$Lol);
      if ($cliAccountExit !== 0) $CommandStatus = 1; } }
  // / Start the listener.
  else if ($cliCommand === '-l' or $cliCommand === '--listen') {
    logEntry('Command line invocation. Starting the Core Manager listener.');
    if (!startCoreManagerListener()) $CommandStatus = 1; }
  // / Stop the listener with no target, or end one worker with a target.
  else if ($cliCommand === '-k' or $cliCommand === '--kill') {
    if ($cliTarget === '') {
      logEntry('Command line invocation. Stopping the Core Manager listener.');
      if (!stopCoreManagerListener()) $CommandStatus = 1; }
    else {
      logEntry('Command line invocation. Terminating worker '.$cliTarget.'.');
      if (!killTargetedWorker($cliTarget)) $CommandStatus = 1; } }
  // / End every TRACKED worker.
  else if ($cliCommand === '--kill-all-workers') {
    $cliActionConfirmed = confirmDestructiveAction('This ends every tracked conversion in progress. Users will lose work.', $cliConfirmed);
    // / Declining a destructive action aborts it, & an abort exits 1, as it does for apt.
    if (!$cliActionConfirmed) $CommandStatus = 1;
    if ($cliActionConfirmed) {
      warningEntry('Command line invocation. Terminating every tracked worker.');
      // / The Resource Manager is asked to do this rather than the registry being walked from here, because it owns the registry.
      // / kill-tracked is a request type the Resource Manager already answers, & the Worker Manager sends it for the same purpose.
      // / The Resource Manager listens on the internal channel, which is what the Worker Manager uses.
      list ($cliMessageDelivered, $cliMessageReply) = sendManagerMessage(buildManagerSocketPath('resource-manager'), array('RequestType' => 'kill-tracked'), 'internal', (int)$ManagerSocketTimeout);
      if ($cliMessageDelivered && isset($cliMessageReply['Reason'])) print($Lol.$cliMessageReply['Reason'].$Lol);
      else if ($cliMessageDelivered) print($Lol.'The Resource Manager accepted the request & said nothing about it.'.$Lol);
      else {
        warningEntry('The Resource Manager could not be reached, so no tracked worker was ended.');
        $CommandStatus = 1;
        print($Lol.'The Resource Manager is not reachable. Nothing was ended.'.$Lol);
        print('Start the listener, or use --kill-every-worker to end every process directly.'.$Lol); } } }
  // / End every PHP process owned by the web server user.
  // / This reaches unrelated applications sharing the host & says so before it runs.
  else if ($cliCommand === '--kill-every-worker') {
    $cliActionConfirmed = confirmDestructiveAction('This ends EVERY PHP process owned by '.$ApacheUser.' on this host. Other applications sharing this server, such as WordPress or OwnCloud, will lose every session in progress.', $cliConfirmed);
    if (!$cliActionConfirmed) $CommandStatus = 1;
    if ($cliActionConfirmed) {
      warningEntry('Command line invocation. Terminating every process owned by the web server user.');
      // / killEveryWorker lives in the Worker Manager, which only a manager process loads.
      // / This called it without loading it, so a confirmed --kill-every-worker was a fatal
      // / undefined function every time, the worst moment to fail. It is loaded here the
      // / version checked way every manager is, & a refusal is said rather than thrown.
      if (!function_exists('killEveryWorker') && function_exists('loadEngineManager')) list ($cliWorkerManagerLoaded, $cliWorkerManagerVersion) = loadEngineManager('workerManager.php');
      if (!function_exists('killEveryWorker')) {
        $CommandStatus = 1;
        print($Lol.'The Worker Manager could not be loaded, so nothing was ended. The log says why.'.$Lol.$Lol); }
      else print($Lol.'Terminated '.killEveryWorker().' process(es).'.$Lol); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cliActionConfirmed, $cliMessageDelivered, $cliWorkerManagerLoaded, $cliMessageReply, $cliAccountOutput, $cliWorkerManagerVersion, $cliHandedCommand, $cliAccountExit, $cliCommand, $cliTarget, $cliConfirmed, $cliWhoami, $cliListenerAuthorized);
  return $CommandStatus; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to answer the two workflow commands.
// / Accepts the arguments, the command, its target & whether it was confirmed, in that order. Returns the exit status.
// / --run-workflow is how a request starts a workflow's worker, & is never typed by a person. Its target is the job.
// / --workflow lists, checks, installs & removes workflows, & the dispatcher has already refused a write to anybody but root.
// / Both need the Workflow Core, which the application loads & pins, so neither runs without it.
function runEngineWorkflowCommand($cliArguments, $cliCommand, $cliTarget, $cliConfirmed) {
  // / Set variables.
  global $WorkflowCoreActive, $Lol, $EnableMemoryProtection;
  $CommandStatus = 1;
  $workerRanJob = FALSE;
  if (empty($WorkflowCoreActive) or !function_exists('runWorkflowWorker') or !function_exists('runWorkflowAdminCommand')) {
    warningEntry('The command '.$cliCommand.' was refused because the Workflow Core is unavailable.');
    print($Lol.'The Workflow Core is unavailable. Run  php '.engineApplicationEntryPoint().' -v  to see why.'.$Lol.$Lol); }
  else if ($cliCommand === '--run-workflow') list ($workerRanJob, $CommandStatus) = runWorkflowWorker((string)$cliTarget);
  else $CommandStatus = runWorkflowAdminCommand($cliArguments, $cliTarget, $cliConfirmed);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $workerRanJob, $cliArguments, $cliCommand, $cliTarget, $cliConfirmed);
  return $CommandStatus; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to list what the running tree lacks that installing it needs.
// / Accepts no arguments.
// / Returns the paths it lacks, each relative to the running tree.
// / -fp installs the running tree when it is not the installation, & three files decide whether it can.
// / The Engine's configuration loads the application's contract, which names the repair & everything the installer copies.
// / Setup Core holds the installer, which compares the tree with the installation before it copies anything.
// / Without any one of them -fp would correct nothing & install nothing, & still report success.
function listMissingInstallEssentials() {
  // / Set variables.
  global $InstLoc, $EnableMemoryProtection;
  $MissingEssentials = array();
  $essentialPaths = array('Resources'.DIRECTORY_SEPARATOR.'Engine'.DIRECTORY_SEPARATOR.'engineConfig.php', 'Resources'.DIRECTORY_SEPARATOR.'Contract'.DIRECTORY_SEPARATOR.'app-engine-config.php', 'Resources'.DIRECTORY_SEPARATOR.'Engine'.DIRECTORY_SEPARATOR.'Cores'.DIRECTORY_SEPARATOR.'setupCore.php');
  $essentialPath = '';
  foreach ($essentialPaths as $essentialPath) if (!is_file(rtrim((string)$InstLoc, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$essentialPath)) $MissingEssentials[] = $essentialPath;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $essentialPaths, $essentialPath);
  return $MissingEssentials; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run one of the Engine's root only commands.
// / Accepts the command & its target, in that order. Returns the exit status.
// / The caller has already refused these to anybody but root.
// / -u is root only too, & the application dispatches it, because it loads Setup Core for it.
function runEngineRootCommand($cliCommand, $cliTarget) {
  // / Set variables.
  global $Lol, $InstLoc, $ApplicationLoc, $EnableMemoryProtection;
  $CommandStatus = 0;
  $cliPermissionsFixed = $cliManagerAvailable = $cliPassCompleted = FALSE;
  $cliPathsCorrected = 0;
  $cliManagerVersion = '';
  $cliPassFindings = $cliMissingEssentials = array();
  // / -fp from a tree that is not the installation installs that tree, so the tree must hold what installing it needs.
  if (($cliCommand === '-fp' or $cliCommand === '--fix-permissions') && (string)$ApplicationLoc !== '' && (string)@realpath((string)$InstLoc) !== (string)@realpath((string)$ApplicationLoc)) $cliMissingEssentials = listMissingInstallEssentials();
  // / The Environment Manager is loaded here by name, through loadEngineRootManager(), & never through loadEngineManager().
  // / getAcceptedManagers() is what the listener dispatches from, & a root role the listener could reach is what its design refuses.
  // / A pass that could not complete exits 1, so a timer reports it as failed.
  // / A pass that found drift has completed & exits 0, & what it found is in root's log, its state file & --status.
  // / See Documentation/ABOUT_ENVIRONMENT_MANAGER.txt.
  if ($cliCommand === '--run-environment-manager') {
    list ($cliManagerAvailable, $cliManagerVersion) = loadEngineRootManager('environmentManager.php');
    if (!$cliManagerAvailable or !function_exists('runEnvironmentManager')) {
      $CommandStatus = 1;
      print($Lol.'The Environment Manager could not be loaded. Root\'s log says why. Nothing was checked or changed.'.$Lol.$Lol); }
    else {
      list ($cliPassCompleted, $cliPassFindings) = runEnvironmentManager();
      if (!$cliPassCompleted) {
        $CommandStatus = 1;
        print($Lol.'The Environment Manager pass did not complete. Root\'s log says why.'.$Lol.$Lol); }
      // / The findings are what the pass found before any repair it made. A repair prints its own
      // / report as it runs, ending with the environment checked again, so that is above these.
      else {
        print($Lol.'Environment Manager pass complete. Findings, before repair:'.$Lol);
        showEnvironmentFindings($cliPassFindings, FALSE);
        print($Lol); } } }
  // / -fp installs the running tree when it is not the installation.
  // / A drop-in carries only the files its release changed, so one unpacked over the web root by mistake is not a whole release.
  // / Without Setup Core there is no installer, & without the Engine's configuration or the contract there is no repair & nothing to copy.
  // / -fp would then correct nothing & install nothing, & report success while the installation runs the release before.
  // / A tree lacking any of the three is refused here, with the direction a tree without an Engine is given.
  // / A tree holding all three reaches the installer, which refuses one that lacks code the installation holds.
  else if (count($cliMissingEssentials) > 0) {
    warningEntry('The command '.$cliCommand.' was refused because '.$InstLoc.' is not the installation & lacks '.implode(' & ', $cliMissingEssentials).'.');
    $CommandStatus = 1;
    print($Lol.'This folder lacks '.implode(' & ', $cliMissingEssentials).', so it is not a whole release & cannot be installed.'.$Lol);
    if (is_dir((string)$ApplicationLoc)) print('The installation is '.$ApplicationLoc.'. Unpack a drop-in into '.$ApplicationLoc.' & run -fp there.'.$Lol.$Lol);
    else print('Install from a whole release.'.$Lol.$Lol); }
  // / Handle the -fp or --fix-permissions arguments.
  // / The repair is the application's & is reached through the provider it declares, $EngineRepairProvider.
  // / HRConvert2 declares fixManagedPermissions.
  else if ($cliCommand === '-fp' or $cliCommand === '--fix-permissions') {
    logEntry('Command line invocation. Correcting managed permissions.');
    print($Lol.'Correcting permissions on managed paths.'.$Lol);
    list ($cliPermissionsFixed, $cliPathsCorrected) = runApplicationRepair();
    if (!$cliPermissionsFixed) $CommandStatus = 1;
    print($Lol.($cliPermissionsFixed ? 'Corrected '.$cliPathsCorrected.' path(s).' : 'Permissions could not be corrected.').$Lol.$Lol); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cliPermissionsFixed, $cliManagerAvailable, $cliPassCompleted, $cliPathsCorrected, $cliManagerVersion, $cliPassFindings, $cliMissingEssentials, $cliCommand, $cliTarget);
  return $CommandStatus; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to sweep expired sessions from the command line.
// / Accepts the threshold the operator supplied, if any. Returns the exit status.
// / The caller has already refused this to anybody but root & the web server account.
// /
// / Root does not sweep, & hands the sweep to the web server account instead.
// / Every name in a session folder belongs to that account, which could swap it for a link between examination & removal.
// / Run through runAsAccount, the sweep can only remove what that account could already remove.
// / The temporary location is swept whole, not one request's session folder.
function runEngineCleanCommand($cliTarget) {
  // / Set variables.
  global $Lol, $InstLoc, $DirSep, $DeleteThreshold, $ConvertTemp, $ConvertLoc, $ProtectedRootDirs, $RunningAsRoot, $ApacheUser, $EnableMemoryProtection;
  $CommandStatus = 0;
  $cliTempCleaned = $cliTempDeepCleaned = $cliDataCleaned = $cliDataDeepCleaned = FALSE;
  $cliThreshold = 0;
  $accountOutput = array();
  $accountExitCode = 1;
  $cliTarget = strtolower((string)$cliTarget);
  if ($RunningAsRoot) {
    logEntry('Command line invocation. Handing the manual clean to '.$ApacheUser.'.');
    list ($accountOutput, $accountExitCode) = runAsAccount('php '.escapeshellarg($InstLoc.$DirSep.engineApplicationEntryPoint()).' -c'.($cliTarget === '' ? '' : ' '.escapeshellarg($cliTarget)).' 2>&1', (string)$ApacheUser);
    print(implode($Lol, $accountOutput).$Lol);
    if ($accountExitCode !== 0) $CommandStatus = 1; }
  else {
    logEntry('Command line invocation. Performing a manual clean.');
    $cliThreshold = $DeleteThreshold;
    // / A threshold of zero expires everything, because every session is older than nothing.
    if ($cliTarget === 'now') $cliThreshold = 0;
    // / ctype_digit rather than is_numeric, because is_numeric accepts a negative & a
    // / negative threshold would expire every session while looking like a normal number.
    else if ($cliTarget !== '' && ctype_digit($cliTarget)) $cliThreshold = (int)$cliTarget;
    else if ($cliTarget !== '') {
      warningEntry('An unrecognized clean threshold was supplied. Using the configured default.');
      print($Lol.'Unrecognized threshold. Supply a whole number of minutes, or now.'.$Lol); }
    print($Lol.'Cleaning sessions older than '.$cliThreshold.' minute(s).'.$Lol);
    logEntry('Manual clean requested. Threshold set to '.$cliThreshold.' minute(s).');
    list ($cliTempCleaned, $cliTempDeepCleaned) = cleanDataLocation($ConvertTemp, 'Temporary files', $cliThreshold, $ProtectedRootDirs, array($ConvertTemp));
    print('  Temporary location   '.($cliTempCleaned ? 'OK' : 'FAILED').($cliTempDeepCleaned ? ', removed expired sessions' : ', nothing was expired').$Lol);
    list ($cliDataCleaned, $cliDataDeepCleaned) = cleanDataLocation($ConvertLoc, 'Data location', $cliThreshold, $ProtectedRootDirs, array($ConvertLoc));
    print('  Data location        '.($cliDataCleaned ? 'OK' : 'FAILED').($cliDataDeepCleaned ? ', removed expired sessions' : ', nothing was expired').$Lol);
    if (!$cliTempCleaned or !$cliDataCleaned) print($Lol.'One or more locations could not be cleaned. See the log for the reason.'.$Lol);
    if (!$cliTempCleaned or !$cliDataCleaned) $CommandStatus = 1;
    print($Lol); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cliTempCleaned, $cliTempDeepCleaned, $cliDataCleaned, $cliDataDeepCleaned, $cliThreshold, $accountOutput, $accountExitCode, $cliTarget);
  return $CommandStatus; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the version every file matching a pattern declares.
// / Accepts a pattern relative to the installation & the name the version is declared under.
// / Returns the versions keyed by path, & whether every matching file declared one, in that order.
// / SCRIPT_VERSION is read the way a shell script declares it. Every other name is read the
// / way a PHP file declares it, at the start of a line, so a version quoted in a comment is
// / never mistaken for a declaration.
function readDeclaredVersions($sourcePattern, $versionName) {
  // / Set variables.
  global $InstLoc, $EnableMemoryProtection;
  $DeclaredVersions = array();
  $EveryFileDeclares = FALSE;
  $sourcePaths = $versionMatches = array();
  $sourcePath = $sourceContents = $versionPattern = '';
  // / A pattern that climbs out of the installation, or a name that is not a plain word, is
  // / refused. Both come from the contract, & neither ever needs to.
  if (strpos((string)$sourcePattern, '..') === FALSE && preg_match('/^\w+$/', (string)$versionName) === 1) {
    $versionPattern = ($versionName === 'SCRIPT_VERSION') ? '/^SCRIPT_VERSION="v?([0-9]+\.[0-9]+\.[0-9]+)"/m' : '/^\s*\$'.$versionName.'\s*=\s*\'v?([0-9]+\.[0-9]+\.[0-9]+)\';/m';
    $sourcePaths = glob(rtrim((string)$InstLoc, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.ltrim((string)$sourcePattern, DIRECTORY_SEPARATOR));
    if (is_array($sourcePaths) && !empty($sourcePaths)) $EveryFileDeclares = TRUE;
    if (is_array($sourcePaths)) foreach ($sourcePaths as $sourcePath) {
      $sourceContents = (string)@file_get_contents($sourcePath);
      if (preg_match($versionPattern, $sourceContents, $versionMatches) === 1) $DeclaredVersions[$sourcePath] = $versionMatches[1];
      else $EveryFileDeclares = FALSE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $sourcePaths, $versionMatches, $sourcePath, $sourceContents, $versionPattern, $sourcePattern, $versionName);
  return array($DeclaredVersions, $EveryFileDeclares); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide what one pin should say.
// / Accepts the pin's current version, & the pattern & name its version is declared under.
// / Returns the status, the version that ships & a detail, in that order.
// / The status is 'ok', 'differs', 'hand' or 'refused'. A pin nothing declares is set by
// / hand. A pin whose components disagree, or where one of them declares nothing, cannot be
// / built, because writing either version would hide the disagreement rather than settle it.
function resolvePinVersion($pinnedVersion, $sourcePattern, $versionName) {
  // / Set variables.
  global $EnableMemoryProtection;
  $PinStatus = 'hand';
  $ShippedVersion = $PinDetail = '';
  $declaredVersions = $distinctVersions = array();
  $everyFileDeclares = FALSE;
  if ($sourcePattern === '') $PinDetail = 'no shipped file declares it';
  else {
    list ($declaredVersions, $everyFileDeclares) = readDeclaredVersions($sourcePattern, $versionName);
    $distinctVersions = array_values(array_unique($declaredVersions));
    $PinStatus = 'refused';
    if (empty($declaredVersions)) $PinDetail = 'nothing matching '.$sourcePattern.' declares '.$versionName;
    else if (!$everyFileDeclares) $PinDetail = 'a file matching '.$sourcePattern.' declares no '.$versionName;
    else if (count($distinctVersions) > 1) $PinDetail = 'the files matching '.$sourcePattern.' declare '.implode(' & ', $distinctVersions);
    else {
      $ShippedVersion = $distinctVersions[0];
      $PinStatus = ($ShippedVersion === ltrim((string)$pinnedVersion, 'vV')) ? 'ok' : 'differs'; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $declaredVersions, $distinctVersions, $everyFileDeclares, $pinnedVersion, $sourcePattern, $versionName);
  return array($PinStatus, $ShippedVersion, $PinDetail); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to describe the line one pin is written on.
// / Accepts the pin's style & its key. Returns the pattern that finds the pin's version.
// / A variable pin is written as $Required<Key> = 'v#.#.#'; in the application's entry point.
// / An entry pin is written as '<Key>' => 'v#.#.#' in a pin table. The key is matched as a
// / whole name, so writing one pin can never move its neighbour.
// / The pattern captures what comes before the version, the version, & what comes after it.
function pinLinePattern($pinStyle, $pinKey) {
  // / Set variables.
  global $EnableMemoryProtection;
  $PinPattern = '';
  $quotedKey = preg_quote((string)$pinKey, '/');
  if ($pinStyle === 'variable') $PinPattern = '/^(\s*\$Required'.$quotedKey.'\s*=\s*\'v?)([0-9]+\.[0-9]+\.[0-9]+)(\';)/m';
  else $PinPattern = '/^(\s*\''.$quotedKey.'\'\s*=>\s*\'v?)([0-9]+\.[0-9]+\.[0-9]+)(\')/m';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $quotedKey, $pinStyle, $pinKey);
  return $PinPattern; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to list every version pin the build is responsible for.
// / Accepts nothing. Returns the pins, & whether the entry point's pins could be read, in that order.
// / Each pin is the file it is written in, its style & key there, the version it says now,
// / & the pattern & name its version is declared under. Pins live in four places.
// /   The application's entry point pins the kernel, the Engine, its cores & the bootstrap
// /   script, & whatever $EnginePinSources in the contract names.
// /   getAcceptedManagers() pins each manager.
// /   $EngineAcceptedPipelines in the contract pins each pipeline.
// /   $EngineAcceptedSharedModules in the contract pins each shared module.
// / The Engine knows where its own components live, so the contract names only the
// / application's, & an entry there can never redirect a pin the Engine owns.
function listEnginePins() {
  // / Set variables.
  global $InstLoc, $DirSep, $EnginePinSources, $EngineAcceptedPipelines, $EngineAcceptedSharedModules, $EnableMemoryProtection;
  $EnginePins = array();
  $EntryPinsReadable = FALSE;
  $engineSources = $pinSources = $pinMatches = $pinMatch = array();
  $entryPath = $entryContents = $enginePath = $contractFile = $pinKey = $pinVersion = '';
  $engineSources = array('KernelVersion' => 'Resources/Kernel/kernel.php', 'EngineVersion' => 'Resources/Engine/engine.php', 'SetupCoreVersion' => 'Resources/Engine/Cores/setupCore.php',
    'DependencyCoreVersion' => 'Resources/Engine/Cores/dependencyCore.php', 'PipelineCoreVersion' => 'Resources/Engine/Cores/pipelineCore.php', 'WorkflowCoreVersion' => 'Resources/Engine/Cores/workflowCore.php', 'AuthenticationCoreVersion' => 'Resources/Engine/Cores/authenticationCore.php', 'EmailCoreVersion' => 'Resources/Engine/Cores/emailCore.php', 'ConfigScript' => 'Documentation/Build/'.engineApplicationSlug().'-setup.sh');
  $pinSources = array_merge((isset($EnginePinSources) && is_array($EnginePinSources)) ? $EnginePinSources : array(), $engineSources);
  $entryPath = $InstLoc.$DirSep.engineApplicationEntryPoint();
  $enginePath = $InstLoc.$DirSep.'Resources'.$DirSep.'Engine'.$DirSep.'engine.php';
  $contractFile = contractPath('app-engine-config.php');
  $entryContents = (string)@file_get_contents($entryPath);
  if ($entryContents !== '') $EntryPinsReadable = TRUE;
  preg_match_all('/^\s*\$Required(\w+?(?:Version|Script))\s*=\s*\'v?([0-9]+\.[0-9]+\.[0-9]+)\';/m', $entryContents, $pinMatches, PREG_SET_ORDER);
  foreach ($pinMatches as $pinMatch) $EnginePins[] = array('File' => $entryPath, 'Style' => 'variable', 'Key' => $pinMatch[1], 'Label' => '$Required'.$pinMatch[1], 'Pinned' => $pinMatch[2],
    'Pattern' => isset($pinSources[$pinMatch[1]]) ? $pinSources[$pinMatch[1]] : '', 'Name' => ($pinMatch[1] === 'ConfigScript') ? 'SCRIPT_VERSION' : $pinMatch[1]);
  foreach (getAcceptedManagers() as $pinKey => $pinVersion) $EnginePins[] = array('File' => $enginePath, 'Style' => 'entry', 'Key' => $pinKey, 'Label' => 'Manager '.$pinKey, 'Pinned' => ltrim($pinVersion, 'vV'),
    'Pattern' => 'Resources/Engine/Managers/'.basename($pinKey), 'Name' => 'ManagerVersion');
  foreach (getAcceptedRootManagers() as $pinKey => $pinVersion) $EnginePins[] = array('File' => $enginePath, 'Style' => 'entry', 'Key' => $pinKey, 'Label' => 'Manager '.$pinKey, 'Pinned' => ltrim($pinVersion, 'vV'),
    'Pattern' => 'Resources/Engine/Managers/'.basename($pinKey), 'Name' => 'ManagerVersion');
  if (isset($EngineAcceptedPipelines) && is_array($EngineAcceptedPipelines)) foreach ($EngineAcceptedPipelines as $pinKey => $pinVersion) $EnginePins[] = array('File' => $contractFile, 'Style' => 'entry', 'Key' => $pinKey,
    'Label' => 'Pipeline '.$pinKey, 'Pinned' => ltrim($pinVersion, 'vV'), 'Pattern' => 'Resources/Pipelines/'.basename($pinKey).'/pipelineConfig.php', 'Name' => 'PipelineVersion');
  if (isset($EngineAcceptedSharedModules) && is_array($EngineAcceptedSharedModules)) foreach ($EngineAcceptedSharedModules as $pinKey => $pinVersion) $EnginePins[] = array('File' => $contractFile, 'Style' => 'entry', 'Key' => $pinKey,
    'Label' => 'Shared '.$pinKey, 'Pinned' => ltrim($pinVersion, 'vV'), 'Pattern' => 'Resources/Pipelines/Shared/'.basename($pinKey), 'Name' => 'SharedModuleVersion');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $engineSources, $pinSources, $pinMatches, $pinMatch, $entryPath, $entryContents, $enginePath, $contractFile, $pinKey, $pinVersion);
  return array($EnginePins, $EntryPinsReadable); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write new versions into the pins one file holds.
// / Accepts the file & the new versions, keyed by style & then by pin key.
// / Returns whether every pin was found once & written.
// / Only the version between the quotes changes. The line, its leading v & the rest of the
// / file stay exactly as they were, & the file is read back to prove the write landed.
function writePinsToFile($pinFile, $newVersions) {
  // / Set variables.
  global $EnableMemoryProtection;
  $PinsWereWritten = FALSE;
  $fileContents = $updatedContents = $pinStyle = $pinKey = $newVersion = '';
  $styleVersions = array();
  $replacementCount = $bytesWritten = 0;
  $fileContents = (string)@file_get_contents($pinFile);
  $updatedContents = $fileContents;
  if ($fileContents !== '') {
    $PinsWereWritten = TRUE;
    foreach ($newVersions as $pinStyle => $styleVersions) foreach ($styleVersions as $pinKey => $newVersion) {
      $updatedContents = preg_replace(pinLinePattern($pinStyle, $pinKey), '${1}'.$newVersion.'${3}', $updatedContents, 1, $replacementCount);
      if ($replacementCount !== 1) $PinsWereWritten = FALSE; }
    if ($PinsWereWritten && $updatedContents !== $fileContents) {
      $bytesWritten = @file_put_contents($pinFile, $updatedContents, LOCK_EX);
      if ($bytesWritten !== strlen($updatedContents) or (string)@file_get_contents($pinFile) !== $updatedContents) $PinsWereWritten = FALSE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $fileContents, $updatedContents, $pinStyle, $pinKey, $newVersion, $styleVersions, $replacementCount, $bytesWritten, $pinFile, $newVersions);
  return $PinsWereWritten; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to build every version pin from the components that ship, for --build.
// / Accepts the action & a second target, which is --write or nothing.
// / Returns the exit status & whether every pin now matches what ships, in that order.
// / A pin is never edited by hand. Each one is read from the component it names, so a pin
// / & its component can only disagree when the components themselves do, & the build
// / refuses that rather than choosing a side. Without --write nothing is changed & a pin
// / that would change exits 1, so the same command is the check a release runs before it
// / is tagged. With --write every pin that differs is rewritten & the result is checked.
// / This rewrites source files. It is the developer's build step & never an operator's.
// / Raising a component's version is still the developer's decision. The build only makes
// / every pin agree with the versions the developer chose.
function runEngineBuildCommand($cliTarget, $cliSecondTarget) {
  // / Set variables.
  global $Lol, $InstLoc, $DirSep, $EnableMemoryProtection;
  $CommandStatus = 0;
  $PinsMatch = FALSE;
  $buildAction = $pinStatus = $shippedVersion = $pinDetail = $statusText = $managerPath = $managerName = $pinFile = '';
  $enginePins = $enginePin = $pendingWrites = $pinRows = $pinRow = $pinnedManagers = $newVersions = $managerPaths = array();
  $managerPin = '';
  $entryPinsReadable = $writePins = $fileWritten = FALSE;
  $pinsChecked = $pinsMatching = $pinsChanged = $pinsRefused = $pinsByHand = $pinsDiffering = 0;
  $buildAction = ($cliTarget === '') ? '--pins' : $cliTarget;
  $writePins = ($cliSecondTarget === '--write');
  if ($buildAction !== '--pins' or ($cliSecondTarget !== '' && !$writePins)) {
    print($Lol.'Usage: --build --pins            Check every version pin against what ships.'.$Lol.'       --build --pins --write    Rewrite every pin that differs.'.$Lol.$Lol);
    $CommandStatus = 2; }
  else {
    list ($enginePins, $entryPinsReadable) = listEnginePins();
    if (!$entryPinsReadable) {
      warningEntry('The build could not read the pins in '.engineApplicationEntryPoint().'.');
      print($Lol.'The pins in '.engineApplicationEntryPoint().' could not be read.'.$Lol); }
    foreach ($enginePins as $enginePin) {
      list ($pinStatus, $shippedVersion, $pinDetail) = resolvePinVersion($enginePin['Pinned'], $enginePin['Pattern'], $enginePin['Name']);
      if ($pinStatus === 'differs') $pendingWrites[$enginePin['File']][$enginePin['Style']][$enginePin['Key']] = $shippedVersion;
      $pinRows[] = array($enginePin['Label'], $enginePin['Pinned'], $shippedVersion, $pinStatus, $pinDetail, $enginePin['File']); }
    if ($writePins) foreach ($pendingWrites as $pinFile => $newVersions) {
      $fileWritten = writePinsToFile($pinFile, $newVersions);
      if (!$fileWritten) warningEntry('The build could not write the pins in '.$pinFile.'.');
      foreach ($pinRows as $pinRow) if ($pinRow[5] === $pinFile && $pinRow[3] === 'differs') $pinsChanged += ($fileWritten ? 1 : 0); }
    print($Lol.'Version pins'.($writePins ? '' : '. Nothing is changed without --write.').$Lol.$Lol);
    printf('  %-36s %-9s %-9s %s'.$Lol, 'PIN', 'PINNED', 'SHIPS', 'STATUS');
    foreach ($pinRows as $pinRow) {
      $pinsChecked++;
      if ($pinRow[3] === 'ok') $pinsMatching++;
      if ($pinRow[3] === 'refused') $pinsRefused++;
      if ($pinRow[3] === 'hand') $pinsByHand++;
      if ($pinRow[3] === 'differs') $pinsDiffering++;
      if ($pinRow[3] === 'ok') $statusText = 'OK';
      else if ($pinRow[3] === 'hand') $statusText = 'SET BY HAND, '.$pinRow[4];
      else if ($pinRow[3] === 'refused') $statusText = 'CANNOT BUILD, '.$pinRow[4];
      else $statusText = $writePins ? 'CHANGED' : 'WOULD CHANGE';
      printf('  %-36s %-9s %-9s %s'.$Lol, $pinRow[0], $pinRow[1], ($pinRow[2] === '' ? '-' : $pinRow[2]), $statusText); }
    // / A manager nothing pins is reported, because nothing refuses it when it is wrong.
    foreach (getAcceptedManagers() as $managerName => $managerPin) $pinnedManagers[] = basename($managerName);
    foreach (getAcceptedRootManagers() as $managerName => $managerPin) $pinnedManagers[] = basename($managerName);
    $managerPaths = glob($InstLoc.$DirSep.'Resources'.$DirSep.'Engine'.$DirSep.'Managers'.$DirSep.'*.php');
    if (!is_array($managerPaths)) $managerPaths = array();
    foreach ($managerPaths as $managerPath) if (!in_array(basename($managerPath), $pinnedManagers, TRUE)) printf('  %-36s %-9s %-9s %s'.$Lol, 'Manager '.basename($managerPath), '-', readComponentVersion('Engine'.$DirSep.'Managers'.$DirSep.basename($managerPath), 'ManagerVersion'), 'NOT PINNED, nothing refuses it when it is wrong');
    $PinsMatch = ($entryPinsReadable && $pinsRefused === 0 && ($pinsMatching + $pinsByHand + ($writePins ? $pinsChanged : 0)) === $pinsChecked);
    if (!$PinsMatch) $CommandStatus = 1;
    print($Lol.'  '.$pinsChecked.' pin(s). '.$pinsMatching.' match, '.($writePins ? $pinsChanged.' changed' : $pinsDiffering.' would change').', '.$pinsRefused.' cannot be built & '.$pinsByHand.' are set by hand.'.$Lol.$Lol);
    logEntry('The build checked '.$pinsChecked.' pin(s)'.($writePins ? ' & changed '.$pinsChanged : '').'. '.$pinsRefused.' could not be built.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $buildAction, $pinStatus, $shippedVersion, $pinDetail, $statusText, $managerPath, $managerName, $pinFile, $enginePins, $enginePin, $pendingWrites, $pinRows, $pinRow, $pinnedManagers, $newVersions, $managerPaths, $managerPin, $entryPinsReadable, $writePins, $fileWritten, $pinsChecked, $pinsMatching, $pinsChanged, $pinsRefused, $pinsByHand, $pinsDiffering, $cliTarget, $cliSecondTarget);
  return array($CommandStatus, $PinsMatch); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to replace the install wide secret with a new one.
// / Accepts whether the action was confirmed in advance with -y. Returns a success boolean.
// / Called by --rotate-secret, never reachable from the web, & root only.
// /
// / Every session ends.
// / Every session folder, token & startup key derives from the secret, so a visitor is given a new session on their next visit & cannot reach their earlier files.
// / Those folders stay where they are & expire on the delete threshold like any other.
// / That is what rotating a secret is for, so the operator is asked first, or answers with -y.
// /
// / The listener holds keys derived from the old secret, so one that is running must be stopped & started again, & root never does either through the web server account's files.
// /   Ask whether a listener is running & stop it before the secret is rewritten.
// /   A service unit that owns the listener is asked through systemctl.
// /   Otherwise the web server account is asked, & it answers & stops with the old key, which is still the one on disk.
// /   A listener that could not be stopped would keep answering with the old key, so nothing is rotated & the operator is told.
// /   Rewrite the secret as root, which is the one part that is root's. The kernel keeps the secret & rewrites it, in settleInstallSecretAsRoot.
// /   Start it again through startCoreManagerListener(), which uses the unit when one owns the listener & hands the start to the web server account when none does.
// / A listener that was not running is left stopped.
// / A container from an image built before v4.0.1 carries the secret that image was built with, & every container started from it shares it.
// / Running this once in each ends that.
function rotateInstallSecret($rotationConfirmed) {
  // / Set variables.
  global $InstLoc, $SecretKey, $ResourceAwarenessActive, $ApacheUser, $Lol, $EnableMemoryProtection;
  $SecretWasRotated = FALSE;
  $actionIsConfirmed = $listenerWasRunning = $listenerWasStopped = $listenerWasStarted = $secretIsReady = $unitOwnsListener = FALSE;
  $newSecretKey = $entryPath = $unitName = '';
  $statusOutput = $stopOutput = array();
  $statusExit = $stopExit = 1;
  $actionIsConfirmed = confirmDestructiveAction('Rotating the install secret ends every session. Visitors are given a new one & cannot reach their earlier files.', $rotationConfirmed);
  if ($actionIsConfirmed) {
    $entryPath = $InstLoc.DIRECTORY_SEPARATOR.engineApplicationEntryPoint();
    $unitName = engineApplicationSlug().'-listener';
    if ($ResourceAwarenessActive && function_exists('listenerUnitIsInstalled')) $unitOwnsListener = listenerUnitIsInstalled();
    // / Learn whether a listener is running, & stop it, BEFORE the secret is rewritten.
    if ($ResourceAwarenessActive && $unitOwnsListener) {
      exec('systemctl is-active --quiet '.escapeshellarg($unitName).' 2>&1', $statusOutput, $statusExit);
      $listenerWasRunning = ($statusExit === 0);
      if ($listenerWasRunning) {
        exec('systemctl stop '.escapeshellarg($unitName).' 2>&1', $stopOutput, $stopExit);
        $listenerWasStopped = ($stopExit === 0); } }
    else if ($ResourceAwarenessActive && function_exists('runAsAccount')) {
      list ($statusOutput, $statusExit) = runAsAccount('php '.escapeshellarg($entryPath).' --status 2>&1', (string)$ApacheUser);
      $listenerWasRunning = (stripos(implode(' ', $statusOutput), 'RUNNING as process') !== FALSE);
      if ($listenerWasRunning) {
        list ($stopOutput, $stopExit) = runAsAccount('php '.escapeshellarg($entryPath).' -k 2>&1', (string)$ApacheUser);
        $listenerWasStopped = ($stopExit === 0); } }
    if ($listenerWasRunning && !$listenerWasStopped) {
      warningEntry('The install secret was not rotated, because the running listener could not be stopped first. It would have kept answering with the old key.');
      errorEntry('The install secret could not be rotated! The secret in place is unchanged.', 31022, FALSE);
      print($Lol.'The listener is running & could not be stopped, so the install secret was NOT rotated.'.$Lol);
      print('Stop it with  sudo php '.engineApplicationEntryPoint().' -k  & run this again.'.$Lol); }
    else {
      list ($secretIsReady, $newSecretKey) = settleInstallSecretAsRoot(TRUE);
      if (!$secretIsReady) {
        errorEntry('The install secret could not be rotated! The secret in place is unchanged.', 31022, FALSE);
        print($Lol.'The install secret could not be rotated. The secret in place is unchanged.'.$Lol); }
      else {
        $SecretWasRotated = TRUE;
        print($Lol.'The install secret was rotated. Every earlier session has ended.'.$Lol);
        // / $SecretKey is the documented exception to convention 3 & is assigned here because
        // / nothing in this process derives a key again, but a reader that did would get the new one.
        $SecretKey = $newSecretKey; }
      // / A listener that was running is started again whether or not the rotation took, because
      // / it was stopped above & the secret on disk is whichever one is in place now.
      if ($listenerWasRunning) $listenerWasStarted = startCoreManagerListener();
      if ($listenerWasRunning && !$listenerWasStarted) print('The listener could not be restarted. Run  sudo php '.engineApplicationEntryPoint().' -l'.$Lol); }
    print($Lol); }
  // / Manually clean up sensitive memory. Every target is passed directly by reference.
  purgeSensitiveMemory($EnableMemoryProtection, $actionIsConfirmed, $listenerWasRunning, $listenerWasStopped, $listenerWasStarted, $secretIsReady, $unitOwnsListener, $newSecretKey, $entryPath, $unitName, $statusOutput, $stopOutput, $statusExit, $stopExit, $rotationConfirmed);
  return $SecretWasRotated; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run one of the Engine's commands, or say that the command is not one.
// / Accepts the argument list, the command, its target, a second target & whether the
// / operator confirmed in advance, in that order.
// / Returns whether the command was the Engine's & the exit status it earned, in that order.
// / A command that is not the Engine's returns FALSE & 0 & has done nothing, so the
// / application carries on with its own. The order of the tests is the order the
// / application's own chain used, so every command meets the same refusal it always did.
function dispatchEngineCommand($cliArguments, $cliCommand, $cliTarget, $cliSecondTarget, $cliConfirmed) {
  // / Set variables.
  global $RunningAsRoot, $CurrentUser, $ApacheUser, $EnableMemoryProtection;
  $CommandWasHandled = TRUE;
  $CommandStatus = 0;
  $cliListenerAuthorized = $cliPinsMatch = FALSE;
  $cliWhoami = '';
  // / Every command the listener owns. Recognized for all users & refused for the wrong one.
  $listenerCommands = array('-l', '--listen', '-k', '--kill', '--kill-all-workers', '--kill-every-worker');
  // / Every command Setup Core, Dependency Core & Pipeline Core answer.
  $setupCommands = array('--config', '--setup', '--pipeline');
  // / Every Engine command that needs root & nothing less.
  $rootCommands = array('-fp', '--fix-permissions', '--run-environment-manager');
  // / The sweep removes the web server account's own files, so root & that account may ask for it, & root hands it over.
  $cleanCommands = array('-c', '--clean');
  // / The developer's build commands. They rewrite source files, so they need write access to
  // / the tree & nothing more. An operator never has a reason to run one.
  $buildCommands = array('--build');
  // / The entry points the listener starts its managers through. Never typed by a person.
  $managerCommands = array('--start-core-manager', '--run-core-manager', '--start-manager');
  // / The entry point a request starts a workflow's worker through, & the command an administrator reads & installs workflows with.
  $workflowCommands = array('--run-workflow', '--workflow');
  // / Only the web server user & root may operate the listener.
  // / A standard user holds a per user secret, which cannot derive a valid startup key.
  // / The web server user must hold the install secret for every listener command, every
  // / manager entry point & --status, or the keys they derive are refused. Which secret an
  // / account holds is the kernel's to decide, before this Engine is loaded.
  if ($RunningAsRoot or $CurrentUser === $ApacheUser) $cliListenerAuthorized = TRUE;
  $cliWhoami = ($CurrentUser === '' ? 'an unidentified user' : $CurrentUser);
  if (in_array($cliCommand, $managerCommands, TRUE)) $CommandStatus = runEngineManagerEntry($cliCommand, $cliTarget, $cliSecondTarget);
  else if ($cliCommand === '--status') $CommandStatus = showEngineStatus($cliWhoami, $cliListenerAuthorized);
  // / A build that reports no failure but no match is still a failure, so the second value is
  // / held against the first rather than trusted to agree with it.
  else if (in_array($cliCommand, $buildCommands, TRUE)) {
    list ($CommandStatus, $cliPinsMatch) = runEngineBuildCommand($cliTarget, $cliSecondTarget);
    if (!$cliPinsMatch && $CommandStatus === 0) $CommandStatus = 1; }
  // / Every setup & config command is root only, & is refused here rather than partway through its own work.
  // / --detect-capabilities writes a cache & --config writes config.php, & a standard user can write neither.
  // / --pipeline is exempt, because it only reads, & any user may run it.
  else if (in_array($cliCommand, $setupCommands, TRUE) && $cliCommand !== '--pipeline' && !$RunningAsRoot) $CommandStatus = refuseCommandForUser($cliCommand, $cliWhoami, 'A setup command was refused for an unauthorized user.');
  else if ($cliCommand === '--pipeline') $CommandStatus = runEnginePipelineCommand($cliTarget, $cliSecondTarget);
  else if ($cliCommand === '--config') $CommandStatus = runEngineConfigCommand($cliArguments, $cliConfirmed);
  else if ($cliCommand === '--setup') $CommandStatus = runEngineSetupCommand($cliArguments, $cliTarget, $cliConfirmed);
  else if (in_array($cliCommand, $listenerCommands, TRUE)) $CommandStatus = runEngineListenerCommand($cliCommand, $cliTarget, $cliConfirmed, $cliWhoami, $cliListenerAuthorized);
  // / A root only command issued by a standard user is named rather than dismissed, & told
  // / it needs root rather than being allowed to run & quietly achieve nothing.
  else if (in_array($cliCommand, $cleanCommands, TRUE) && !$cliListenerAuthorized) $CommandStatus = refuseCommandForUser($cliCommand, $cliWhoami, 'A clean was refused for an unauthorized user.');
  else if (in_array($cliCommand, $cleanCommands, TRUE)) $CommandStatus = runEngineCleanCommand($cliTarget);
  else if (in_array($cliCommand, $rootCommands, TRUE) && !$RunningAsRoot) $CommandStatus = refuseCommandForUser($cliCommand, $cliWhoami, 'A root only command was refused for an unauthorized user.');
  else if (in_array($cliCommand, $rootCommands, TRUE)) $CommandStatus = runEngineRootCommand($cliCommand, $cliTarget);
  // / Rotating the install secret stops & starts the listener, so it is the Engine's command, & root's alone.
  // / The kernel rewrites the secret itself.
  // / Accounts are the web server account's, so --account is that account's & root's, & root hands it to that account.
  else if ($cliCommand === '--account' && !$cliListenerAuthorized) $CommandStatus = refuseCommandForUser($cliCommand, $cliWhoami, 'An account command was refused for an unauthorized user.');
  else if ($cliCommand === '--account') $CommandStatus = runEngineAccountCommand($cliArguments, $cliTarget, $cliConfirmed);
  else if ($cliCommand === '--rotate-secret' && !$RunningAsRoot) $CommandStatus = refuseCommandForUser($cliCommand, $cliWhoami, 'A root only command was refused for an unauthorized user.');
  else if ($cliCommand === '--rotate-secret') {
    warningEntry('Command line invocation. Rotating the install secret.');
    if (!rotateInstallSecret($cliConfirmed)) $CommandStatus = 1; }
  // / Installing & removing a workflow write the installed library, which is root's, so both are refused here for anybody else.
  // / --workflow is refused by what it was asked to do, because --list & --check are anybody's & only a write is root's.
  // / So the refusal names the option as typed, & the command it suggests is the whole one.
  else if ($cliCommand === '--workflow' && !$RunningAsRoot && (strpos((string)$cliTarget, '--install') === 0 or strpos((string)$cliTarget, '--remove') === 0)) $CommandStatus = refuseCommandForUser($cliCommand.' '.(string)$cliTarget, $cliWhoami, 'A workflow command that writes the installed library was refused for an unauthorized user.');
  else if (in_array($cliCommand, $workflowCommands, TRUE)) $CommandStatus = runEngineWorkflowCommand($cliArguments, $cliCommand, $cliTarget, $cliConfirmed);
  else $CommandWasHandled = FALSE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cliListenerAuthorized, $cliPinsMatch, $cliWhoami, $listenerCommands, $setupCommands, $rootCommands, $cleanCommands, $buildCommands, $managerCommands, $workflowCommands, $cliArguments, $cliCommand, $cliTarget, $cliSecondTarget, $cliConfirmed);
  return array($CommandWasHandled, $CommandStatus); }
// / -----------------------------------------------------------------------------------
?>
