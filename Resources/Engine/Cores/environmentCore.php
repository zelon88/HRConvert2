<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRProprietary Engine, Copyright on 9/8/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / File Information ...
// / v4.0.8.
// / The environment the Engine provides to every application built on it.
// /
// / The Engine owns these, & an application uses them without defining them.
// / Every definition is guarded by function_exists, because the kernel already defines several before the Engine loads.
// / The boot needs locateDependency to find php, sanitize to read an argument & readComponentVersion to verify the Engine.
// / A function defined earlier is kept & the Engine defines the rest, because a redeclared function is fatal in PHP.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by an application.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! Engine-35000, The Engine environment cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to remove everything a value must never contain.
// / Accepts any value & whether to apply the strict rule. Returns the cleaned value &
// / whether it was already clean, in that order.
// /
// / It removes rather than escapes.
// / Escaping assumes every context the value will reach is known, & a removed character cannot be interpreted by anything.
// / It cannot be used on a URL, because the characters it removes from a filename are the ones a URL is built from.
// / An address is rebuilt from its parsed parts instead, as normalizeStreamBaseURL() does.
// / The second return value is whether the input was already clean.
// / A caller that must know somebody sent something they should not have reads it, because a cleaned value looks innocent.
if (!function_exists('sanitize')) {
  function sanitize($suppliedValue, $strictMode) {
    // / Set variables.
    global $EnableMemoryProtection;
    $CleanValue = $suppliedValue;
    $ValueWasClean = FALSE;
    $allowedPattern = '';
    $allowedPattern = $strictMode ? '/[^A-Za-z0-9._-]/' : '/[^A-Za-z0-9._\\- ]/';
    if (is_array($suppliedValue)) {
      $CleanValue = array();
      foreach ($suppliedValue as $suppliedKey => $suppliedItem) {
        list ($CleanValue[$suppliedKey], $itemWasClean) = sanitize($suppliedItem, $strictMode); }
      $ValueWasClean = ($CleanValue === $suppliedValue); }
    else if (is_string($suppliedValue)) {
      $CleanValue = preg_replace($allowedPattern, '', $suppliedValue);
      $ValueWasClean = ($CleanValue === $suppliedValue); }
    else $ValueWasClean = TRUE;
    // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
    purgeSensitiveMemory($EnableMemoryProtection, $allowedPattern, $suppliedValue, $strictMode);
    return array($CleanValue, $ValueWasClean); } }
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to find an executable on this host.
// / Accepts the name. Returns the full path, or an empty string.
// /
// / command -v rather than which, because which is a separate package that a minimal
// / container frequently does not have, & its absence reports every dependency as missing.
// / command is a shell builtin & is always there.
if (!function_exists('locateDependency')) {
  function locateDependency($dependencyName) {
    // / Set variables.
    global $EnableMemoryProtection;
    $DependencyPath = '';
    $commandOutput = array();
    $commandExitCode = 1;
    exec('command -v '.escapeshellarg($dependencyName).' 2>/dev/null', $commandOutput, $commandExitCode);
    if ($commandExitCode === 0 && isset($commandOutput[0])) $DependencyPath = trim($commandOutput[0]);
    // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
    purgeSensitiveMemory($EnableMemoryProtection, $commandOutput, $commandExitCode, $dependencyName);
    return $DependencyPath; } }
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to take the extension off a path.
// / Accepts the path. Returns the extension in lower case, without a dot.
// /
// / Lower case because a format list is written in lower case & JPG is not a different
// / format from jpg. A comparison that fails on capitalisation is a bug reported as an
// / unsupported file.
if (!function_exists('getExtension')) {
  function getExtension($suppliedPath) {
    // / Set variables.
    global $EnableMemoryProtection;
    $Extension = '';
    $Extension = strtolower((string)pathinfo((string)$suppliedPath, PATHINFO_EXTENSION));
    // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
    purgeSensitiveMemory($EnableMemoryProtection, $suppliedPath);
    return $Extension; } }
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / Two filesystem utilities, one reading a size & one reading the modification time of a link itself.
// / filemtime follows a link & reports its target's time, while lstat does not follow.
// / An expiry sweep needs the time a session last touched its link, not the time the shared file it points at was made.
// / Neither is called by HRConvert2, & both are kept because an engine is a component library.
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to return the filesize of a specified file.
function getFilesize($File) {
  // / Set variables.
  $Size = @filesize($File);
  // / Determine the most efficient unit of measure to represent the specified value in.
  if ($Size < 1024) $Size = $Size." Bytes";
  elseif (($Size < 1048576) && ($Size > 1023)) $Size = round($Size / 1024, 1)." KB";
  elseif (($Size < 1073741824) && ($Size > 1048575)) $Size = round($Size / 1048576, 1)." MB";
  else $Size = round($Size/1073741824, 1)." GB";
  return $Size; }
// / -----------------------------------------------------------------------------------
// / A function to return the file time of a specified symlink.
function symlinkmtime($symlinkPath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $Stat = @lstat($symlinkPath);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $symlinkPath);
  return isset($Stat['mtime']) ? $Stat['mtime'] : NULL; }
// / -----------------------------------------------------------------------------------
// / Four environment utilities.
// / policyDisplayStatus turns a policy state into a word for a report.
// / escapeApacheConfigPath quotes a path for a server configuration.
// / apacheModuleIsListed asks whether a module is loaded.
// / verifyEncryption asks whether this build can encrypt.
// / None of them knows what the application does with the answer.
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to turn an internal policy status into the word an operator should read.
// / Accepts the internal status word.
// / Returns 'ok' when nothing needs doing, or the status unchanged when something does.
// /
// / A status column is scanned rather than read, so it says ok only when nothing is wrong.
// / A state where nothing needs doing, such as unconfined or carried, becomes ok, & the sentence beside it says why.
// / installed, repaired & corrected keep their own word, because each changed this machine.
function policyDisplayStatus($policyStatus) {
  // / Set variables.
  global $EnableMemoryProtection;
  $DisplayStatus = (string)$policyStatus;
  $benignStatuses = array('ok', 'unchanged', 'unrestricted', 'unconfined', 'carried', 'confined', 'absent', 'host', 'n/a');
  // / kept is not flattened, because it is a change an earlier release made that only the
  // / administrator can undo, & they are owed seeing it.
  if (in_array((string)$policyStatus, $benignStatuses, TRUE)) $DisplayStatus = 'ok';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $benignStatuses, $policyStatus);
  return $DisplayStatus; }
// / -----------------------------------------------------------------------------------
// / A function to render a filesystem path safely inside an Apache configuration file.
// / Accepts the path.
// / Returns the path quoted, with any quote or backslash in it escaped.
// / A directory name is not necessarily a bare word.
// / Apache treats whitespace as an argument separator, so an installation under a path with
// / a space in it silently produces a <Directory> block governing the wrong directory. A
// / quoted path is correct in every case & costs nothing in the ordinary one.
function escapeApacheConfigPath($configPath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $EscapedPath = '';
  $EscapedPath = '"'.str_replace(array('\\', '"'), array('\\\\', '\\"'), (string)$configPath).'"';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $configPath);
  return $EscapedPath; }
// / -----------------------------------------------------------------------------------
// / A function to find a module name in the output of apachectl -M.
// / Accepts the module name & the captured output lines, in that order.
// / Returns TRUE when that module is listed.
// / The output is one module per line, indented, & suffixed with (shared) or (static). A
// / plain comparison against the whole line therefore never matches, & a substring search
// / for a short name would match a longer one that contains it.
function apacheModuleIsListed($moduleName, $commandOutput) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ModuleWasFound = FALSE;
  $outputLine = '';
  if (is_array($commandOutput)) {
    foreach ($commandOutput as $outputLine) {
      if (strtok(trim((string)$outputLine), ' ') === (string)$moduleName) $ModuleWasFound = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $outputLine, $commandOutput, $moduleName);
  return $ModuleWasFound; }
// / -----------------------------------------------------------------------------------
// / A function to set an echo variable that adjusts printed URL's to https when SSL is enabled.
function verifyEncryption() {
  $EncryptionVerified = TRUE;
  // / Determine if the connection is encrypted and adjust the $URLEcho accordingly.
  if (!empty($_SERVER['HTTPS']) && $_SERVER['SERVER_PORT'] == 443) $URLEcho = 's';
  else $URLEcho = '';
  return array($EncryptionVerified, $URLEcho); }
// / -----------------------------------------------------------------------------------
// / confirmDestructiveAction asks a human to type a word before something irreversible.
// / Every application of this shape has an irreversible command & needs the same pause before it.
// / How an operator is reached differs, & that is already behind $EngineOperatorPrompt.
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to ask for confirmation on a destructive command line action.
// / Accepts the prompt text & a boolean indicating confirmation was already given.
// / Returns TRUE when the action may proceed.
function confirmDestructiveAction($promptText, $confirmationSupplied) {
  // / Set variables.
  global $Lol, $EnableMemoryProtection;
  $ActionIsConfirmed = FALSE;
  $inputHandle = FALSE;
  $typedAnswer = '';
  if ($confirmationSupplied) $ActionIsConfirmed = TRUE;
  else {
    print($Lol.$promptText.$Lol.'Type YES to continue. Anything else cancels. '.$Lol);
    $inputHandle = @fopen('php://stdin', 'r');
    if ($inputHandle !== FALSE) {
      $typedAnswer = trim((string)fgets($inputHandle));
      @fclose($inputHandle);
      if ($typedAnswer === 'YES') $ActionIsConfirmed = TRUE; }
    if (!$ActionIsConfirmed) print($Lol.'Cancelled.'.$Lol); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $inputHandle, $typedAnswer, $promptText, $confirmationSupplied);
  return $ActionIsConfirmed; }
// / -----------------------------------------------------------------------------------
// / validateInstallation checks an installation is intact, & does not ask what it is for.
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to prove that an installation actually runs.
// / Called after the swap. The new installation is asked to report its own version as a
// / separate process. A core that cannot parse, cannot load its config, & cannot reach
// / the command line branch will not answer, & that is the condition worth catching.
// / A file count or a directory listing proves nothing about whether the code executes.
// /
// / The answer is recognized by its first line, which is the application's name & its version in every release that can be updated to.
// / -v still ends with the words Core version, so an older updater recognizes this release.
// / Checks -v reports as failed do not fail this, because an update that changed nothing about them would still be rolled back.
function validateInstallation($installPath) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $InstallationIsValid = FALSE;
  $validateCommand = '';
  $validateOutput = array();
  $validateExitCode = 1;
  $validateLine = '';
  $validateCommand = 'php '.escapeshellarg($installPath.'/'.engineApplicationEntryPoint()).' -v 2>&1';
  exec($validateCommand, $validateOutput, $validateExitCode);
  // / The exit code proves it ran. The marker proves it ran far enough to report.
  if ($validateExitCode === 0 && is_array($validateOutput)) {
    foreach ($validateOutput as $validateLine) {
      if (preg_match('/^'.preg_quote(engineApplicationName(), '/').' v?[0-9]+\.[0-9]+\.[0-9]+$/', trim((string)$validateLine)) === 1 or strpos((string)$validateLine, 'Core version') !== FALSE) {
        $InstallationIsValid = TRUE;
        break; } } }
  if ($Verbose) logEntry('Installation validation: '.($InstallationIsValid ? 'PASSED' : 'FAILED').', exit code '.$validateExitCode.'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $validateCommand, $validateOutput, $validateExitCode, $validateLine, $installPath);
  return $InstallationIsValid; }
// / -----------------------------------------------------------------------------------
// / Four environment utilities.
// / verifyApacheModule asks whether a module is loaded & can enable it.
// / fetchOwnResponseHeaders reads the headers this server actually sends.
// / describePolicyStatus turns a policy state into words.
// / toolMinimum reads a tool's minimum version from the manifest, its only owner.
// / None of them knows what this application is for, & none calls back into it.
// / reportUnrecognizedArgument stays in the application, because it calls showHelpInfo(), which prints the application's own help.
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to confirm an Apache module is loaded, & to enable it when it is not.
// / Accepts the module name as Apache reports it & a boolean permitting repair, in that order.
// / Returns a loaded boolean & the status word, in that order.
// /
// / A rule that needs a module does nothing without it.
// / A served location's rules are Header directives inside <IfModule mod_headers.c>, so the configuration still parses without the module.
// / On such a server the rules are skipped in silence, while the configuration reports itself written, tested & reloaded.
// / So the module is checked, & enabled where it can be, because -fp is already root & already editing this configuration.
// / apachectl -M is asked, because a link under mods-enabled says what an administrator intended & not what the running server has.
function verifyApacheModule($moduleName, $mayRepair) {
  // / Set variables.
  global $RunningAsRoot, $EnableMemoryProtection;
  $ModuleIsLoaded = FALSE;
  $ModuleStatus = 'unknown';
  $controlBinary = $loadPath = $enabledPath = $moduleSlug = '';
  $commandOutput = array();
  $commandExitCode = 1;
  $linkWasMade = FALSE;
  $controlBinary = locateDependency('apachectl');
  if ($controlBinary === '') $controlBinary = locateDependency('apache2ctl');
  if ($controlBinary === '') {
    $ModuleStatus = 'unknown';
    warningEntry('apachectl could not be found, so it was not possible to confirm whether the '.$moduleName.' Apache module is loaded. The DATA rules depend on it.'); }
  else {
    exec(escapeshellarg($controlBinary).' -M 2>&1', $commandOutput, $commandExitCode);
    if (apacheModuleIsListed($moduleName, $commandOutput)) {
      $ModuleIsLoaded = TRUE;
      $ModuleStatus = 'ok'; }
    else {
      // / Debian & Ubuntu keep every module available & enable it with a link, which is all
      // / a2enmod does. The link is made directly, because a2enmod is a distribution script
      // / that a minimal image may not carry.
      $moduleSlug = str_replace('_module', '', (string)$moduleName);
      $loadPath = '/etc/apache2/mods-available/'.$moduleSlug.'.load';
      $enabledPath = '/etc/apache2/mods-enabled/'.$moduleSlug.'.load';
      if (!$mayRepair or !$RunningAsRoot) {
        $ModuleStatus = 'missing';
        warningEntry('The '.$moduleName.' Apache module is not loaded, so the DATA rules are being skipped. Run the -fp argument as root to enable it.'); }
      else if (!file_exists($loadPath)) {
        // / Nothing to enable. On a layout that compiles modules in, or one where the module
        // / is genuinely not installed, this application does not go looking further.
        $ModuleStatus = 'missing';
        warningEntry('The '.$moduleName.' Apache module is not loaded & no '.$loadPath.' exists to enable, so the DATA rules are being skipped. Install the module & run the -fp argument again.'); }
      else {
        if (!file_exists($enabledPath)) $linkWasMade = @symlink('../mods-available/'.$moduleSlug.'.load', $enabledPath);
        else $linkWasMade = TRUE;
        // / A module enabled now is not in the running server until it reloads, & the reload
        // / belongs to the caller so that one reload covers the module & the configuration.
        if ($linkWasMade) {
          $ModuleIsLoaded = TRUE;
          $ModuleStatus = 'enabled'; }
        else {
          $ModuleStatus = 'missing';
          warningEntry('The '.$moduleName.' Apache module could not be enabled at '.$enabledPath.', so the DATA rules are being skipped.'); } } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $controlBinary, $loadPath, $enabledPath, $moduleSlug, $commandOutput, $commandExitCode, $linkWasMade, $moduleName, $mayRepair);
  return array($ModuleIsLoaded, $ModuleStatus); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to fetch one URL & return its response headers.
// / Accepts the URL & a timeout in seconds, in that order.
// / Returns a reachability boolean & the raw response headers, in that order.
// / curl first, then PHP streams, then a refusal. Neither mechanism is guaranteed present;
// / a minimal container often has no curl, & allow_url_fopen is commonly disabled.
// / This is a request THIS SERVER makes TO ITSELF & is not a third party connection.
function fetchOwnResponseHeaders($requestURL, $timeoutSeconds) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RequestSucceeded = FALSE;
  $ResponseHeaders = '';
  $curlBinary = $curlCommand = '';
  $curlOutput = array();
  $curlExitCode = 1;
  $streamHeaders = array();
  $curlBinary = locateDependency('curl');
  if ($curlBinary !== '') {
    // / -s is quiet, -I is headers only, -L follows a redirect to where the file really is,
    // / & --max-time bounds a server that accepts the connection & then never answers.
    $curlCommand = escapeshellarg($curlBinary).' -s -I -L --max-time '.(int)$timeoutSeconds.' '.escapeshellarg($requestURL).' 2>/dev/null';
    exec($curlCommand, $curlOutput, $curlExitCode);
    if ($curlExitCode === 0 && !empty($curlOutput)) {
      $RequestSucceeded = TRUE;
      $ResponseHeaders = implode("\n", $curlOutput); } }
  // / No curl, or curl could not reach it. Try the interpreter's own client before giving up.
  if (!$RequestSucceeded && function_exists('get_headers') && (bool)ini_get('allow_url_fopen')) {
    @ini_set('default_socket_timeout', (int)$timeoutSeconds);
    $streamHeaders = @get_headers($requestURL, FALSE);
    if (is_array($streamHeaders) && !empty($streamHeaders)) {
      $RequestSucceeded = TRUE;
      $ResponseHeaders = implode("\n", $streamHeaders); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $curlBinary, $curlCommand, $curlOutput, $curlExitCode, $streamHeaders, $requestURL, $timeoutSeconds);
  return array($RequestSucceeded, $ResponseHeaders); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to explain a policy status in a sentence.
// / Accepts the policy name & the status word, in that order.
// / Returns a sentence saying whether anything needs doing.
// / A bare status word is not a report.
// / unrestricted, unconfined, carried & confined all mean nothing needs doing, & all four read
// / like something is missing or wrong. An operator should never have to ask which of the words
// / on this screen are the bad ones.
function describePolicyStatus($policyName, $policyStatus) {
  // / Set variables.
  global $EnableMemoryProtection;
  $StatusDescription = '';
  if ($policyStatus === 'ok') $StatusDescription = 'Matches this release.';
  else if ($policyStatus === 'installed') $StatusDescription = 'Written.';
  else if ($policyStatus === 'repaired') $StatusDescription = 'Rewritten to match this release.';
  else if ($policyStatus === 'unchanged') $StatusDescription = 'Already correct.';
  else if ($policyStatus === 'unrestricted') $StatusDescription = 'Namespaces are not restricted. No profile is needed.';
  else if ($policyStatus === 'unconfined') $StatusDescription = 'Nothing on this host confines OpenSCAD. The sandbox isolates it.';
  else if ($policyStatus === 'carried') $StatusDescription = 'Carried into each conversion\'s sandbox. The host is unchanged.';
  else if ($policyStatus === 'not carried') $StatusDescription = 'A pipeline is missing the policy it carries, so its conversions use the host\'s. Restore the pipeline from this release.';
  else if ($policyStatus === 'retired') $StatusDescription = 'Written by an earlier release. The host\'s own file is back.';
  else if ($policyStatus === 'kept') $StatusDescription = 'An earlier release wrote this over the host\'s file & kept no copy. Left in place. Restore the original from its package or build.';
  else if ($policyStatus === 'confined') $StatusDescription = 'A profile on this host confines it. Nothing is added to it. If its conversions fail, check that profile.';
  else if ($policyStatus === 'unsupported') $StatusDescription = 'Namespaces are restricted & this AppArmor cannot load a profile that permits one.';
  else if ($policyStatus === 'host') $StatusDescription = 'A container uses its host\'s AppArmor. The kernel\'s line says what the host needs.';
  else if ($policyStatus === 'exposed') $StatusDescription = 'NOT ATTACHED. Another account could put a program where this profile attaches. Not written, & an earlier copy is removed. The log names the folder. Make every folder above it root\'s alone & run -fp as root.';
  else if ($policyStatus === 'absent') $StatusDescription = 'Not installed. No policy is needed.';
  else if ($policyStatus === 'foreign') $StatusDescription = 'Not written by this application. Review it, or run -fp to replace it.';
  else if ($policyStatus === 'outdated') $StatusDescription = 'Written by an older release. Run -fp as root.';
  else if ($policyStatus === 'not loaded') $StatusDescription = 'ON DISK BUT NOT IN FORCE. Reload AppArmor, or reboot.';
  else if ($policyStatus === 'refused') $StatusDescription = 'REFUSED. Could not be changed safely & was left alone. The log says why.';
  else $StatusDescription = 'Reported '.$policyStatus.'.';
  // / A served location's protection file is the one policy whose presence proves nothing.
  // / Every other policy here is read by the kernel or by a converter the moment it exists.
  // / This one is a .htaccess, which Apache reads only where AllowOverride is enabled &
  // / permits it, so 'Matches this release. Nothing to do.' would be a lie on
  // / exactly the installations that are exposed. The exposure check further down is the
  // / answer & this line points at it rather than pretending to be it.
  // / A protection file is named for its folder followed by Directory, as DATA Directory is, & no other policy's name ends that way.
  if ($policyStatus === 'disabled') $StatusDescription = 'Not maintained. --Maintain HTAccess-- is FALSE in config.php. The server configuration carries these rules alone.';
  else if (substr((string)$policyName, -10) === ' Directory' && ($policyStatus === 'ok' or $policyStatus === 'installed' or $policyStatus === 'repaired')) $StatusDescription = $StatusDescription.' INERT unless AllowOverride is enabled. See the exposure check below.';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $policyName, $policyStatus);
  return $StatusDescription; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read a tool's minimum version from the manifest, for display.
// / Accepts the manifest name. Returns the minimum, or an empty string when there is none.
// / -v shows each tool's minimum. That number is read from the manifest, the only owner of it,
// / rather than from a setting that could disagree with what the pipelines actually enforce.
// / It reads the manifest directly rather than through the Pipeline Core, so -v can still show
// / minimums on an installation whose Pipeline Core failed to load.
function toolMinimum($manifestName) {
  // / Set variables.
  global $DependsManifest, $CoreLoaded, $InstLoc, $DirSep, $EnableMemoryProtection;
  $ToolMinimum = '';
  $manifestPath = '';
  $manifestEntry = array();
  // / $CoreLoaded is in scope because the manifest is required inside this function, & every
  // / component checks it at file scope.
  if (!isset($DependsManifest) or !is_array($DependsManifest)) {
    $manifestPath = contractPath('depends.php');
    if (file_exists($manifestPath)) require_once ($manifestPath); }
  if (isset($DependsManifest) && is_array($DependsManifest)) foreach ($DependsManifest as $manifestEntry) {
    if (!isset($manifestEntry['Name']) or (string)$manifestEntry['Name'] !== (string)$manifestName) continue;
    $ToolMinimum = isset($manifestEntry['MinimumVersion']) ? (string)$manifestEntry['MinimumVersion'] : '';
    break; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $manifestPath, $manifestEntry, $manifestName);
  return $ToolMinimum; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report the application's name, for anything a person reads.
// / Accepts nothing. Returns the name the application declared in its engine contract.
// /
// / This is the identity, not the branding.
// / $EngineApplicationName is set in Contract/app-engine-config.php & does not change, while $ApplicationName is a setting an operator may rename.
// / The Engine uses the identity, so nothing it writes to disk moves when an installation is renamed.
// / An application that declares no name gets 'Application', the Engine's own default.
function engineApplicationName() {
  // / Set variables.
  global $EngineApplicationName;
  $ApplicationDisplayName = (isset($EngineApplicationName) && trim((string)$EngineApplicationName) !== '') ? trim((string)$EngineApplicationName) : 'Application';
  return $ApplicationDisplayName; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report the application's name in the form used for names on disk.
// / Accepts nothing. Returns the name lowercased, with anything but letters & digits removed.
// / Unit names, configuration files, sandbox profiles & backup suffixes all use this form.
// / For HRConvert2 it is hrconvert2, which is exactly what those names always were, so an
// / existing installation sees no name change at all.
function engineApplicationSlug() {
  // / Set variables.
  $ApplicationSlug = strtolower(preg_replace('/[^A-Za-z0-9]/', '', engineApplicationName()));
  if ($ApplicationSlug === '') $ApplicationSlug = 'application';
  return $ApplicationSlug; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the environment variable a startup key travels in.
// / Accepts nothing. Returns the application's slug upper cased, followed by _STARTUP_KEY.
// / For HRConvert2 it is HRCONVERT2_STARTUP_KEY, so a listener started by the previous release hands its children a key they can read.
function engineStartupKeyVariable() {
  // / Set variables.
  $StartupKeyVariable = strtoupper(engineApplicationSlug()).'_STARTUP_KEY';
  return $StartupKeyVariable; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the marker the Engine writes into every policy file it maintains.
// / Accepts nothing. Returns the application's slug upper cased, followed by -POLICY-MARKER.
// / For HRConvert2 it is HRCONVERT2-POLICY-MARKER, which every installation already carries.
// / It is built from the identity rather than the branding, so renaming an installation moves nothing.
// / Two applications on one host each mark their own files, & neither mistakes the other's for its own.
function enginePolicyMarker() {
  // / Set variables.
  $PolicyMarker = strtoupper(engineApplicationSlug()).'-POLICY-MARKER';
  return $PolicyMarker; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report the file an application is started through.
// / Accepts nothing. Returns the entry point the application declared in its engine contract.
// / The Engine starts managers & the listener by running this file, & names it in the
// / instructions it prints. For HRConvert2 it is convertCore.php. An application that declares
// / none gets app.php, which is what the Engine's own example application is called.
function engineApplicationEntryPoint() {
  // / Set variables.
  global $EngineEntryPoint;
  $ApplicationEntryPoint = (isset($EngineEntryPoint) && trim((string)$EngineEntryPoint) !== '') ? basename(trim((string)$EngineEntryPoint)) : 'app.php';
  return $ApplicationEntryPoint; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report the application's own version.
// / Accepts nothing. Returns the version the application handed the Engine, without a leading v.
// / The application owns its version & hands it over once, where it defines it, so there is
// / never a second copy to drift. It has no default in engineConfig.php on purpose: that file
// / loads later & a default there would overwrite the version already handed over.
function engineApplicationVersion() {
  // / Set variables.
  global $EngineApplicationVersion;
  $ApplicationVersion = isset($EngineApplicationVersion) ? ltrim(trim((string)$EngineApplicationVersion), 'vV') : '';
  return $ApplicationVersion; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Three utilities whose output names the application.
// / resolveUpdateTarget works out where a self update comes from.
// / verifyPhpConfiguration writes & checks a PHP settings file.
// / dataProtectionContents builds a data protection file.
// / Each asks for the application's name, so every application gets its own.
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to work out which release to fetch & the URL that will deliver it.
// / Three targets are supported & each resolves to a different kind of URL.
// / A version such as v3.6.8 resolves to that tag's tarball directly. A tag that does
// / not exist produces a 404 at download time, which is the correct failure.
// / latest asks the GitHub API which tag is newest, then resolves to that tag.
// / edge resolves to the current state of the master branch. A branch tarball carries
// / whatever version stamp master happens to hold, which may match no release at all.
// / The API call is the only network request this function makes & is only made for latest.
function resolveUpdateTarget($requestedVersion) {
  // / Set variables.
  global $Verbose, $UpdateSourceRepository, $EnableMemoryProtection;
  $TargetResolved = FALSE;
  $TargetVersion = $TargetURL = $apiURL = $apiResponse = $apiMatches = '';
  $curlCommand = $curlOutput = '';
  $curlExitCode = 1;
  $requestedVersion = strtolower(trim($requestedVersion));
  // / The current state of the master branch. No tag & no version guarantee.
  if ($requestedVersion === 'edge') {
    $TargetVersion = 'edge';
    $TargetURL = 'https://github.com/'.$UpdateSourceRepository.'/archive/refs/heads/master.tar.gz';
    $TargetResolved = TRUE; }
  // / The newest tag, whatever it currently is. One API call resolves the name.
  else if ($requestedVersion === 'latest') {
    $apiURL = 'https://api.github.com/repos/'.$UpdateSourceRepository.'/releases/latest';
    // / A user agent is mandatory. The GitHub API refuses a request without one.
    // / -L follows the redirect chain, which is what a plain wget could not do.
    $curlCommand = 'curl -L -s --max-time 30 --max-filesize 1048576'
      .' -H '.escapeshellarg('User-Agent: '.engineApplicationName().'-Updater')
      .' -H '.escapeshellarg('Accept: application/vnd.github+json')
      .' '.escapeshellarg($apiURL).' 2>/dev/null';
    $apiResponse = shell_exec($curlCommand);
    // / Read the tag name out of the response without parsing the whole document.
    // / json_decode would also work. This avoids depending on the shape of the rest of it.
    if (is_string($apiResponse) && preg_match('/"tag_name"\s*:\s*"([^"]+)"/', $apiResponse, $apiMatches)) {
      $TargetVersion = $apiMatches[1];
      $TargetURL = 'https://github.com/'.$UpdateSourceRepository.'/archive/refs/tags/'.$TargetVersion.'.tar.gz';
      $TargetResolved = TRUE; }
    else errorEntry('Could not determine the latest release from the update source!', 29000, FALSE); }
  // / An explicit tag. Anything that is not edge or latest is treated as one.
  // / The tag is used in a URL, so it is checked against a strict pattern first.
  else {
    if (preg_match('/^v?[0-9]+\.[0-9]+\.[0-9]+$/', $requestedVersion)) {
      $TargetVersion = (strpos($requestedVersion, 'v') === 0) ? $requestedVersion : 'v'.$requestedVersion;
      $TargetURL = 'https://github.com/'.$UpdateSourceRepository.'/archive/refs/tags/'.$TargetVersion.'.tar.gz';
      $TargetResolved = TRUE; }
    else errorEntry('The requested update target is not a recognized version!', 29001, FALSE); }
  if ($Verbose && $TargetResolved) logEntry('Update target resolved to '.$TargetVersion.'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $apiURL, $apiResponse, $apiMatches, $curlCommand, $curlOutput, $curlExitCode, $requestedVersion);
  return array($TargetResolved, $TargetVersion, $TargetURL); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report the largest upload the web server & PHP accept.
// / Accepts nothing. Returns the ceiling in megabytes.
// / The PHP drop in & the Apache configuration both read it, so the two never disagree. Apache
// / 2.4.54 & later refuse a request body over 1 GiB unless told otherwise.
function uploadCeilingMegabytes() {
  // / Set variables.
  $UploadCeiling = 5000;
  return $UploadCeiling; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write the PHP settings HRConvert2 cannot run without.
// / Accepts a boolean permitting a write.
// / Returns a success boolean & the number of configurations written, in that order.
// /
// / A drop in is written, & php.ini itself is never edited.
// / Every PHP build reads conf.d after php.ini, & a php.ini edited in place is reverted by the next PHP upgrade.
// / Every SAPI is written, because a limit the web server lacks makes a conversion that works from a terminal refuse from a browser.
// / max_execution_time is derived from --Stream Duration Timeout--, because a backgrounded stream holds a PHP worker for its whole duration.
// / A lower limit leaves FFMPEG running with nothing left to end it.
function verifyPhpConfiguration($mayRepair) {
  // / Set variables.
  global $StreamWatchTimeout, $RunningAsRoot, $Lol, $Verbose, $EnableMemoryProtection, $DirSep;
  $ConfigurationIsValid = TRUE;
  $ConfigurationsWritten = 0;
  $dropInContents = $dropInPath = $existingContents = $candidateDir = '';
  $candidateDirectories = $globResults = array();
  $executionTime = $bytesWritten = 0;
  // / Two minutes of headroom over the longest stream this installation permits.
  $executionTime = (int)$StreamWatchTimeout + 120;
  if ($executionTime < 1200) $executionTime = 1200;
  $dropInContents = '; / Written by '.engineApplicationName().'. Do not edit by hand.'.PHP_EOL
    .'; / '.enginePolicyMarker().PHP_EOL
    .'; / Re-run  sudo php '.engineApplicationEntryPoint().' -fp  to rewrite this file.'.PHP_EOL
    .'; / php.ini itself is never touched, so a PHP upgrade cannot revert these.'.PHP_EOL
    .PHP_EOL
    .'; / A PHP worker is held for the whole of a backgrounded stream conversion.'.PHP_EOL
    .'max_execution_time = '.$executionTime.PHP_EOL
    .'max_input_time = 90'.PHP_EOL
    .'memory_limit = 512M'.PHP_EOL
    .PHP_EOL
    .'; / An upload larger than these is refused by PHP before '.engineApplicationName().' ever sees it.'.PHP_EOL
    .'post_max_size = '.uploadCeilingMegabytes().'M'.PHP_EOL
    .'upload_max_filesize = '.uploadCeilingMegabytes().'M'.PHP_EOL
    .'max_file_uploads = 100'.PHP_EOL
    .PHP_EOL
    .'; / An error printed into a response corrupts the filename list the interface parses.'.PHP_EOL
    .'display_errors = Off'.PHP_EOL
    .'log_errors = On'.PHP_EOL
    .PHP_EOL
    .'zlib.output_compression = On'.PHP_EOL;
  // / Every SAPI this host might be running. A packaged PHP splits them by version & by
  // / server, an official container image uses one shared directory.
  // / One a removed package left behind is passed over, because nothing reads it.
  $globResults = glob('/etc/php/*/*/conf.d');
  if (is_array($globResults)) foreach ($globResults as $candidateDir) if (phpConfigFolderIsLive($candidateDir)) $candidateDirectories[] = $candidateDir;
  if (is_dir('/usr/local/etc/php/conf.d')) $candidateDirectories[] = '/usr/local/etc/php/conf.d';
  if (empty($candidateDirectories)) {
    $ConfigurationIsValid = FALSE;
    warningEntry('No PHP configuration directory was found, so the settings '.engineApplicationName().' needs could not be written. Set them in php.ini by hand.'); }
  else {
    foreach ($candidateDirectories as $candidateDir) {
      $dropInPath = $candidateDir.$DirSep.'99-'.engineApplicationSlug().'.ini';
      $existingContents = file_exists($dropInPath) ? (string)@file_get_contents($dropInPath) : '';
      if ($existingContents === $dropInContents) print('  '.str_pad('Unchanged', 12).$dropInPath.$Lol);
      // / A drop in that is there & differs is reported as such.
      else if (!$mayRepair or !$RunningAsRoot) {
        $ConfigurationIsValid = FALSE;
        print('  '.str_pad(($existingContents === '' ? 'Missing' : 'Differs'), 12).$dropInPath.$Lol); }
      else {
        $bytesWritten = @file_put_contents($dropInPath, $dropInContents);
        if ($bytesWritten !== strlen($dropInContents)) {
          $ConfigurationIsValid = FALSE;
          print('  '.str_pad('FAILED', 12).$dropInPath.$Lol); }
        else {
          @chmod($dropInPath, 0644);
          $ConfigurationsWritten++;
          print('  '.str_pad(($existingContents === '' ? 'Written' : 'Updated'), 12).$dropInPath.$Lol); } } }
    if ($ConfigurationsWritten > 0) {
      logEntry('Wrote PHP configuration to '.$ConfigurationsWritten.' location(s). max_execution_time '.$executionTime.'.');
      print('  '.str_pad('Note', 12).'Restart the web server for these to take effect.'.$Lol); } }
  if ($Verbose) logEntry('PHP Configuration: '.count($candidateDirectories).' location(s) examined, '.$ConfigurationsWritten.' written.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dropInContents, $dropInPath, $existingContents, $candidateDir, $candidateDirectories, $globResults, $executionTime, $bytesWritten, $mayRepair);
  return array($ConfigurationIsValid, $ConfigurationsWritten); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether a PHP configuration folder belongs to a PHP that is installed.
// / Accepts the folder, as /etc/php/<version>/<server>/conf.d. Returns TRUE when the program that
// / reads it is there, & for a folder this function does not know how to judge.
// / A package that was removed & not purged leaves its configuration behind, & nothing reads it.
function phpConfigFolderIsLive($configDirectory) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FolderIsLive = TRUE;
  $pathMatches = $programPaths = array();
  $phpVersion = $serverName = $programPath = '';
  if (preg_match('#^/etc/php/([0-9]+\.[0-9]+)/([a-z0-9]+)/conf\.d$#', (string)$configDirectory, $pathMatches) === 1) {
    $phpVersion = $pathMatches[1];
    $serverName = $pathMatches[2];
    // / Where Debian & Ubuntu put the program that reads each server's folder.
    $programPaths = array(
      'apache2' => array('/usr/lib/apache2/modules/libphp'.$phpVersion.'.so'),
      'cli' => array('/usr/bin/php'.$phpVersion),
      'fpm' => array('/usr/sbin/php-fpm'.$phpVersion),
      'cgi' => array('/usr/bin/php-cgi'.$phpVersion, '/usr/lib/cgi-bin/php'.$phpVersion),
      'phpdbg' => array('/usr/bin/phpdbg'.$phpVersion),
      'embed' => array('/usr/lib/libphp'.$phpVersion.'.so'));
    if (isset($programPaths[$serverName])) {
      $FolderIsLive = FALSE;
      foreach ($programPaths[$serverName] as $programPath) if (file_exists($programPath)) $FolderIsLive = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $pathMatches, $programPaths, $phpVersion, $serverName, $programPath, $configDirectory);
  return $FolderIsLive; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to supply the contents of the DATA directory protection file.
// / Accepts no arguments.
// / Returns the complete file contents, carrying the marker that identifies it as ours.
// /
// / A served location is inside the web root & holds bytes users supplied, which the server hands out from the application's own origin.
// / For a format a browser treats as active, such as SVG, that content is code rather than data.
// / Content-Disposition closes this, because a file handed over as a download is never rendered.
// / The CSP is a second & independent stop, because the two fail in different ways.
// / X-Content-Type-Options does not close this, because an SVG is served with its correct type & there is nothing to sniff.
// / It is set for a file whose type is ambiguous, & must never be reported as what stopped a script.
function dataProtectionContents() {
  // / Set variables.
  global $EnableMemoryProtection;
  $ProtectionContents = '';
  $ProtectionContents = '# '.enginePolicyMarker()."\n"
    .'# This file is written & maintained by '.engineApplicationName().'. Do not edit it by hand.'."\n"
    .'# Run the -fp argument as root to reinstall or repair it.'."\n"
    .'#'."\n"
    .'# THIS TREE HOLDS USER SUPPLIED BYTES & IS SERVED DIRECTLY BY THE WEB SERVER.'."\n"
    .'# Content-Disposition hands every file over as a download so nothing inside it is'."\n"
    .'# ever rendered as a document. The CSP stops script execution independently, in case'."\n"
    .'# the disposition is stripped by a proxy or overridden further up the configuration.'."\n"
    .'#'."\n"
    .'# THIS FILE DOES NOTHING UNLESS AllowOverride IS ENABLED FOR THIS DIRECTORY.'."\n"
    .'# Debian, Ubuntu & the standard php:*-apache container all ship AllowOverride None'."\n"
    .'# for /var/www, & an ignored .htaccess produces no error, no log line & no warning of'."\n"
    .'# any kind. Prefer a <Directory> block in the server configuration, which the -fp'."\n"
    .'# argument writes & activates for you, & verify with -fp rather than assuming.'."\n"
    .'# See Documentation/ABOUT_DATA_DIRECTORY_PROTECTION.txt.'."\n"
    .'#'."\n"
    .'# EVERY DIRECTIVE HERE IS LEGAL UNDER AllowOverride FileInfo & NOTHING ELSE IS.'."\n"
    .'# A directive an override level does not permit is not ignored, it is a hard 500 for'."\n"
    .'# this whole tree, which takes out every download & every share link on the server.'."\n"
    .'# An <IfModule> guard does NOT prevent that; it tests whether a module is loaded, not'."\n"
    .'# whether the directive is permitted, so a guarded php_flag still returns 500 under'."\n"
    .'# AllowOverride FileInfo. Options, DirectoryIndex & php_flag therefore live in the'."\n"
    .'# server configuration only. Do not add them here.'."\n"
    .'# DirectoryIndex needs AllowOverride Indexes & Options needs AllowOverride Options.'."\n"
    .'# Neither is implied by FileInfo, which is why naming index.html as the index cannot'."\n"
    .'# be done from here & is done in the <Directory> block the -fp argument writes.'."\n"
    ."\n"
    .'<IfModule mod_headers.c>'."\n"
    .'  # index.html is this application\'s own document root protection page & is the one'."\n"
    .'  # file here that is meant to render. It cannot be user supplied, because the'."\n"
    .'  # application refuses to stage a file by that name, so excluding it opens nothing.'."\n"
    .'  <FilesMatch "^(?!index\.html$).*$">'."\n"
    .'    Header always set Content-Disposition "attachment"'."\n"
    .'    Header always set X-Content-Type-Options "nosniff"'."\n"
    .'    Header always set Content-Security-Policy "default-src \'none\'; sandbox"'."\n"
    .'    Header always set Referrer-Policy "no-referrer"'."\n"
    .'  </FilesMatch>'."\n"
    .'</IfModule>'."\n"
    ."\n"
    .'# Nothing in this tree is ever a program. The core already refuses to stage a'."\n"
    .'# dangerous extension, so this is a second independent statement of the same rule.'."\n"
    .'# RemoveHandler is used rather than php_flag because php_flag needs AllowOverride'."\n"
    .'# Options & this file must never be the reason a server stops serving.'."\n"
    .'RemoveHandler .php .phtml .phps .php3 .php4 .php5 .php7 .php8 .phar .cgi .pl .py .sh'."\n";
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection);
  return $ProtectionContents; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to give a directory a document root protection page.
// / Accepts the directory. Returns whether it is protected & whether a page was written.
// /
// / A directory a web server can reach without an index lists every file in it on most default configurations.
// / The page is generated rather than copied, so it cannot be missing.
// / An existing index.html is left alone, because an application may have a real one.
// / Only index.html is written, & a server that prefers another name needs its own directive.
function protectHostedLocation($hostedDirectory) {
  // / Set variables.
  global $EngineProtectHostedLocations, $EnableMemoryProtection;
  $LocationIsProtected = FALSE;
  $PageWasWritten = FALSE;
  $resolvedDirectory = $indexPath = $pageContents = '';
  if (!isset($EngineProtectHostedLocations) or $EngineProtectHostedLocations !== TRUE) $LocationIsProtected = TRUE;
  else {
    $resolvedDirectory = (string)@realpath((string)$hostedDirectory);
    if ($resolvedDirectory === '' or !is_dir($resolvedDirectory)) warningEntry('A hosted location could not be protected because it does not resolve: '.(string)$hostedDirectory);
    else {
      $indexPath = $resolvedDirectory.DIRECTORY_SEPARATOR.'index.html';
      if (file_exists($indexPath)) $LocationIsProtected = TRUE;
      else {
        $pageContents = '<!DOCTYPE html>'.PHP_EOL.'<html lang="en">'.PHP_EOL.'<head>'.PHP_EOL
          .'<meta charset="utf-8">'.PHP_EOL.'<meta name="robots" content="noindex, nofollow">'.PHP_EOL
          .'<title>Nothing to see here</title>'.PHP_EOL.'</head>'.PHP_EOL.'<body>'.PHP_EOL
          .'<p>This directory does not list its contents.</p>'.PHP_EOL.'</body>'.PHP_EOL.'</html>'.PHP_EOL;
        if (@file_put_contents($indexPath, $pageContents) !== FALSE) {
          @chmod($indexPath, 0644);
          $LocationIsProtected = TRUE;
          $PageWasWritten = TRUE; }
        else warningEntry('A hosted location could not be given a protection page: '.$resolvedDirectory); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $resolvedDirectory, $indexPath, $pageContents, $hostedDirectory);
  return array($LocationIsProtected, $PageWasWritten); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / What the web server serves is the Engine's to guard.
// / It holds the protection file each served location carries, & the probe that proves it is in force.
// / It also holds the writer every policy file goes through, & the path helpers that find a data location inside the document root.
// / The Apache configuration that refuses every data location is Setup Core's, because only root writes it.
// / A served location is one the contract gives the served role, so none of these knows that HRConvert2 calls its own DATA.
// / Each names a location by its folder's own name.
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to sort the locations the contract gives the served role by where they are.
// / Accepts nothing. Returns the locations that are served & the locations that are refused, in
// / that order. Each list holds paths in the order the contract names them, once each & without
// / a trailing separator. A setting that is empty or missing is in neither.
// / A served location lies below the hosted location, & never at it or above it.
// / The rules written for one turn PHP off & hand every file over as a download, so at or above the hosted location they would stop the loader & hand out its source.
// / The probe knows a location's address only from where it sits below the hosted location.
// / A location anywhere else is refused, gets no rules & no role, & the Environment Manager names it.
// / An application that maps no hosted location serves nothing, so each served location it declares is refused.
// / pathIsBelowPath() says how a path is judged.
// / Refusing a location the application still writes into would leave it served without its rules, so nothing safe to write rules for is refused.
// / One reached through a link is served by its written path, which Apache matches, & the kernel refuses root's own work there.
function classifyServedLocations() {
  // / Set variables.
  global $EngineManagedLocations, $EnableMemoryProtection;
  $ServedLocations = $RefusedLocations = array();
  $settingName = $locationRole = $locationPath = $hostedLocation = '';
  $hostedLocation = rtrim((string)applicationSetting('HostedLocation', ''), DIRECTORY_SEPARATOR);
  if (isset($EngineManagedLocations) && is_array($EngineManagedLocations)) foreach ($EngineManagedLocations as $settingName => $locationRole) {
    if ($locationRole !== 'served' or !isset($GLOBALS[$settingName]) or !is_string($GLOBALS[$settingName])) continue;
    $locationPath = rtrim($GLOBALS[$settingName], DIRECTORY_SEPARATOR);
    // / The top of the filesystem is named as itself, so it is refused by name rather than lost.
    if ($locationPath === '' && trim($GLOBALS[$settingName]) !== '') $locationPath = DIRECTORY_SEPARATOR;
    if ($locationPath === '' or in_array($locationPath, $ServedLocations, TRUE) or in_array($locationPath, $RefusedLocations, TRUE)) continue;
    if (pathIsBelowPath($hostedLocation, $locationPath)) $ServedLocations[] = $locationPath;
    else $RefusedLocations[] = $locationPath; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / Neither $ServedLocations nor $RefusedLocations is purged, because both are return values.
  purgeSensitiveMemory($EnableMemoryProtection, $settingName, $locationRole, $locationPath, $hostedLocation);
  return array($ServedLocations, $RefusedLocations); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether one location lies below another, for a rule a web server matches.
// / Accepts the outer location & the inner location, in that order. Returns TRUE only when the
// / inner one lies below the outer one & is not the outer one.
// / Each is judged as it is written, because that is the path Apache matches a rule by.
// / The part of the inner path below the outer one must name no . or .., which Apache resolves itself.
// / A path that is not absolute is below nothing.
// / A link below the outer location is served by its written path, & the kernel refuses root's own work through it.
function pathIsBelowPath($outerPath, $innerPath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $PathIsBelow = FALSE;
  $outerWritten = $innerWritten = '';
  $belowSegments = array();
  $outerWritten = rtrim((string)$outerPath, DIRECTORY_SEPARATOR);
  $innerWritten = rtrim((string)$innerPath, DIRECTORY_SEPARATOR);
  if ($outerWritten !== '' && substr($outerWritten, 0, 1) === DIRECTORY_SEPARATOR && pathTextIsBelow($outerWritten, $innerWritten)) {
    $belowSegments = explode(DIRECTORY_SEPARATOR, (string)substr($innerWritten, strlen($outerWritten) + 1));
    $PathIsBelow = (!in_array('.', $belowSegments, TRUE) && !in_array('..', $belowSegments, TRUE)); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $outerWritten, $innerWritten, $belowSegments, $outerPath, $innerPath);
  return $PathIsBelow; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to list the locations this application serves user supplied files from.
// / Accepts nothing. Returns the path of each served location classifyServedLocations() accepts.
// / An application that serves nothing gets an empty list, & every caller reads that as nothing
// / to protect rather than as a failure.
// / HRConvert2 serves one, the DATA folder in its hosted location, which $ConvertTemp names.
function servedLocations() {
  // / Set variables.
  global $EnableMemoryProtection;
  $ServedLocations = $refusedLocations = array();
  list ($ServedLocations, $refusedLocations) = classifyServedLocations();
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $ServedLocations is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $refusedLocations);
  return $ServedLocations; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to list the locations given the served role that are not served.
// / Accepts nothing. Returns the path of each served location classifyServedLocations() refuses.
// / Each is a mistake in the contract, so it is named wherever the locations are reported.
function misplacedServedLocations() {
  // / Set variables.
  global $EnableMemoryProtection;
  $RefusedLocations = $servedPaths = array();
  list ($servedPaths, $RefusedLocations) = classifyServedLocations();
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $RefusedLocations is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $servedPaths);
  return $RefusedLocations; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the served locations in a sentence.
// / Accepts nothing. Returns the folder name of each served location joined with an ampersand,
// / or an empty string when the application serves nothing.
// / HRConvert2 serves one location, so every sentence built with this reads DATA, as it always did.
function servedLocationNames() {
  // / Set variables.
  global $EnableMemoryProtection;
  $LocationNames = '';
  $folderNames = array();
  $servedPath = '';
  foreach (servedLocations() as $servedPath) $folderNames[] = basename($servedPath);
  $LocationNames = implode(' & ', $folderNames);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderNames, $servedPath);
  return $LocationNames; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether the protection file of each served location is maintained.
// / Accepts nothing. Returns TRUE unless the application's setting says otherwise.
// / The setting is the application's & is read through the map in its contract, by the shared
// / name MaintainProtectionFile. HRConvert2 keeps it in --Maintain HTAccess--. An application
// / that maps nothing, or a setting that is missing, keeps the file maintained, because a read
// / that assumes a setting exists is exactly the assumption this codebase does not make.
function protectionFileIsMaintained() {
  // / Set variables.
  global $EnableMemoryProtection;
  $FileIsMaintained = TRUE;
  $maintainSetting = NULL;
  $maintainSetting = applicationSetting('MaintainProtectionFile', TRUE);
  if ($maintainSetting !== NULL) $FileIsMaintained = (bool)$maintainSetting;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $maintainSetting);
  return $FileIsMaintained; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to validate, install or repair one policy file.
// / Accepts a human readable name, the absolute path, the contents the application
// / requires & a boolean permitting a write, in that order.
// / Returns a validity boolean & a status word, in that order.
// / The status is 'ok', 'installed', 'repaired', 'missing', 'outdated', 'foreign' or 'refused'.
// / A file the application wrote carries its marker, which enginePolicyMarker() names.
// / A file without that marker belongs to the distribution or the administrator, & is never overwritten without a backup or outside a repair.
// / A write is only attempted while running as root, & every other context reports & moves on.
// /
// / A policy in a folder another account can change is read & written without following anything.
// / There only a plain file with one name is read as the policy, through readFileWithoutFollowing, so a link or a FIFO is never followed.
// / Anything else at the name is replaced with writeFileWithoutFollowing, which never opens what was there.
// / A file without the marker there is left alone & reported, because no backup of it can be kept safely in such a folder.
// / A folder only root can change keeps a backup, & is worked in by the resolved path its trust check judged.
// / The marker & the backup suffix are named for the application.
function verifyPolicyFile($policyName, $policyPath, $policyContents, $mayRepair) {
  // / Set variables.
  global $RunningAsRoot, $Verbose, $EnableMemoryProtection;
  $PolicyIsValid = FALSE;
  $PolicyStatus = 'missing';
  $existingContents = $policyDirectory = $backupPath = $readNotice = '';
  $bytesWritten = 0;
  $fileExists = $carriesMarker = $contentsMatch = $directoryIsTrusted = $policyWasWritten = FALSE;
  $resolvedDirectory = $trustNotice = $writeNotice = $trustedPath = '';
  $policyDirectory = dirname($policyPath);
  if (is_dir($policyDirectory)) list ($directoryIsTrusted, $resolvedDirectory, $trustNotice) = pathIsTrustedByRoot($policyDirectory);
  // / A folder root trusts is worked in by the path that was judged, which is the resolved one.
  // / A link on the way there sits in a folder that was never judged, & could be repointed between the check & the write.
  if ($directoryIsTrusted) $trustedPath = $resolvedDirectory.DIRECTORY_SEPARATOR.basename((string)$policyPath);
  if ($directoryIsTrusted) {
    $fileExists = file_exists($trustedPath);
    if ($fileExists) $existingContents = (string)@file_get_contents($trustedPath); }
  // / In a folder another account can change, only a plain file with one name is a policy.
  else if (is_dir($policyDirectory)) list ($fileExists, $existingContents, $readNotice) = readFileWithoutFollowing($policyPath, 1048576);
  if ($fileExists) {
    $carriesMarker = (strpos($existingContents, enginePolicyMarker()) !== FALSE);
    $contentsMatch = ($existingContents === $policyContents); }
  // / A file the application wrote, byte for byte as it wants it, needs nothing.
  if ($fileExists && $contentsMatch) {
    $PolicyIsValid = TRUE;
    $PolicyStatus = 'ok'; }
  // / Everything below here is a change to the filesystem & needs root & permission.
  else if (!$mayRepair or !$RunningAsRoot) {
    if (!$fileExists) warningEntry('The '.$policyName.' policy is missing from '.$policyPath.'. Run the -fp argument as root to install it.');
    else if ($carriesMarker) warningEntry('The '.$policyName.' policy at '.$policyPath.' was written by an older release & no longer matches. Run the -fp argument as root to repair it.');
    else warningEntry('The '.$policyName.' policy at '.$policyPath.' was not written by this application & may not permit what '.engineApplicationName().' needs. Review it, or run the -fp argument as root to replace it.');
    $PolicyStatus = ($fileExists ? 'foreign' : 'missing');
    if ($fileExists && $carriesMarker) $PolicyStatus = 'outdated'; }
  else {
    if (!is_dir($policyDirectory)) @mkdir($policyDirectory, 0755, TRUE);
    // / The folder's trust is decided again here, because it may have just been created.
    if (is_dir($policyDirectory)) list ($directoryIsTrusted, $resolvedDirectory, $trustNotice) = pathIsTrustedByRoot($policyDirectory);
    if ($directoryIsTrusted) $trustedPath = $resolvedDirectory.DIRECTORY_SEPARATOR.basename((string)$policyPath);
    if (!is_dir($policyDirectory)) warningEntry('The directory '.$policyDirectory.' does not exist & could not be created, so the '.$policyName.' policy was not written.');
    // / A folder another account can change. Nothing is backed up & nothing is followed, so a
    // / file somebody else wrote there is left exactly as it is.
    else if (!$directoryIsTrusted && $fileExists && !$carriesMarker) {
      $PolicyStatus = 'refused';
      warningEntry('The '.$policyName.' policy at '.$policyPath.' was not written by this application & sits in a folder another account can change, where no backup of it can be kept safely, so it was left alone. Move it aside & run -fp again to install this one.'); }
    else if (!$directoryIsTrusted) {
      list ($policyWasWritten, $writeNotice) = writeFileWithoutFollowing($policyPath, $policyContents, 0644);
      if (!$policyWasWritten) warningEntry('The '.$policyName.' policy could not be written to '.$policyPath.'. '.$writeNotice);
      else {
        $PolicyIsValid = TRUE;
        $PolicyStatus = ($fileExists ? 'repaired' : 'installed');
        logEntry('The '.$policyName.' policy was '.$PolicyStatus.' at '.$policyPath.' without following anything already there.'); } }
    else {
      // / Never replace a file without keeping the original. An administrator who tuned a
      // / policy by hand must be able to put it back.
      if ($fileExists && !$carriesMarker) {
        $backupPath = $trustedPath.'.'.engineApplicationSlug().'-backup-'.date('Y-m-d-His');
        if (@copy($trustedPath, $backupPath)) warningEntry('The existing '.$policyName.' policy was preserved at '.$backupPath.' before being replaced.');
        // / A backup that failed leaves the file alone, as the warning says.
        else {
          $backupPath = '';
          warningEntry('The existing '.$policyName.' policy at '.$policyPath.' could not be backed up. It was left alone.'); } }
      if ($fileExists && !$carriesMarker && $backupPath === '') $PolicyStatus = 'refused';
      else {
        $bytesWritten = @file_put_contents($trustedPath, $policyContents);
        if ($bytesWritten !== strlen($policyContents)) warningEntry('The '.$policyName.' policy could not be written to '.$policyPath.'.');
        else {
          @chmod($trustedPath, 0644);
          $PolicyIsValid = TRUE;
          $PolicyStatus = ($fileExists ? 'repaired' : 'installed');
          logEntry('The '.$policyName.' policy was '.$PolicyStatus.' at '.$policyPath.'.'); } } } }
  if ($Verbose) logEntry('Policy Check: '.$policyName.', Path: '.$policyPath.', Status: '.$PolicyStatus.'.'.($readNotice !== '' ? ' '.$readNotice : ''));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $existingContents, $policyDirectory, $backupPath, $readNotice, $bytesWritten, $fileExists, $carriesMarker, $contentsMatch, $directoryIsTrusted, $policyWasWritten, $resolvedDirectory, $trustNotice, $writeNotice, $trustedPath, $policyName, $policyPath, $policyContents, $mayRepair);
  return array($PolicyIsValid, $PolicyStatus); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to keep the protection file of one served location current without waiting for -fp.
// / Accepts the served location & whether the file is maintained, in that order.
// / Returns TRUE when the file is present & correct, when it belongs to somebody else, or when
// / it is not maintained & none the application wrote is left behind.
// /
// / A protection file the application wrote must never be the reason the server stops.
// / A directive the directory's AllowOverride does not permit makes Apache refuse every request in the tree.
// / So the file is brought up to date on every request, & an upgrade repairs itself the first time anybody loads a page.
// /
// / Only a file carrying the application's marker is ever touched.
// / An administrator who wrote their own .htaccess here gets to keep it, exactly as
// / verifyPolicyFile() treats every other policy. Theirs is left alone & -fp is where the
// / offer to replace it lives, because that is an interactive, root, deliberate act.
// /
// / This is a read & a comparison on a small file, & it writes only when the contents
// / actually differ, which after the first request is never.
function maintainProtectionFile($servedPath, $maintainProtection) {
  // / Set variables.
  global $Verbose, $RunningAsRoot, $EnableMemoryProtection;
  $ProtectionIsCurrent = FALSE;
  $protectionPath = $existingContents = $desiredContents = $readNotice = $removalNotice = $writeNotice = $locationName = '';
  $carriesMarker = $fileWasRead = $fileWasRemoved = $fileWasWritten = FALSE;
  $bytesWritten = 0;
  $locationName = basename((string)$servedPath);
  $protectionPath = rtrim((string)$servedPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'.htaccess';
  if ((string)$servedPath === '' or !is_dir($servedPath)) $ProtectionIsCurrent = FALSE;
  // / Turning the setting off has to actually take effect.
  // / Leaving a file behind that the application wrote, & then declining to maintain it,
  // / would give an administrator a stale copy of a rule set that no longer matches the
  // / release & no way to notice. So a file carrying the marker is removed. A file the
  // / administrator wrote is left exactly where it is, because it was never ours to delete.
  else if (!$maintainProtection) {
    $ProtectionIsCurrent = TRUE;
    // / Root reaches this through -fp, & the folder is the web server account's, so the file
    // / is read & removed through the kernel. A link or a FIFO there is never followed or waited on.
    list ($fileWasRead, $existingContents, $readNotice) = readFileWithoutFollowing($protectionPath, 1048576);
    if ($fileWasRead && strpos($existingContents, enginePolicyMarker()) !== FALSE) {
      list ($fileWasRemoved, $removalNotice) = removeFileWithoutFollowing($protectionPath);
      if ($fileWasRemoved) warningEntry('--Maintain HTAccess-- is disabled, so the '.$locationName.' protection file this application wrote at '.$protectionPath.' was removed. The server configuration is now carrying these rules alone. Run the -fp argument & read the '.$locationName.' exposure line to confirm the tree is still protected.');
      else warningEntry('--Maintain HTAccess-- is disabled & the '.$locationName.' protection file this application wrote could not be removed. '.$removalNotice); } }
  else {
    $desiredContents = dataProtectionContents();
    list ($fileWasRead, $existingContents, $readNotice) = readFileWithoutFollowing($protectionPath, 1048576);
    if ($fileWasRead) $carriesMarker = (strpos($existingContents, enginePolicyMarker()) !== FALSE);
    // / Already exactly right. This is the case on every request after the first.
    if ($existingContents === $desiredContents) $ProtectionIsCurrent = TRUE;
    // / Somebody else's file. Leave it completely alone & say nothing on every request.
    else if ($existingContents !== '' && !$carriesMarker) $ProtectionIsCurrent = TRUE;
    else {
      // / A web request writes its own file in its own folder. Root never writes by name there.
      if ($RunningAsRoot) list ($fileWasWritten, $writeNotice) = writeFileWithoutFollowing($protectionPath, $desiredContents, 0644);
      else {
        $bytesWritten = @file_put_contents($protectionPath, $desiredContents);
        $fileWasWritten = ($bytesWritten === strlen($desiredContents));
        if ($fileWasWritten) @chmod($protectionPath, 0644); }
      if ($fileWasWritten) {
        $ProtectionIsCurrent = TRUE;
        // / Worth a warning rather than a normal entry, because the installation was running with a protection file that did not match this release.
        warningEntry('The '.$locationName.' protection file at '.$protectionPath.' did not match this release & was rewritten. If downloads or share links were returning a server error, that is now corrected.'); }
      // / A failed write is not fatal. The tree may be exposed or may be broken, & the
      // / exposure check is what establishes which, so this only records that it happened.
      else if ($Verbose) logEntry('The '.$locationName.' protection file at '.$protectionPath.' could not be written. '.$writeNotice); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $protectionPath, $existingContents, $desiredContents, $readNotice, $removalNotice, $writeNotice, $locationName, $carriesMarker, $fileWasRead, $fileWasRemoved, $fileWasWritten, $bytesWritten, $servedPath, $maintainProtection);
  return $ProtectionIsCurrent; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to keep the protection file of every served location current without waiting for -fp.
// / Accepts no arguments.
// / Returns TRUE when every one is current, & FALSE when one is not or nothing is served.
// / A web request calls this on every page, beside the index files. Each location is kept by
// / maintainProtectionFile(), which says why.
// / Nothing served is a warning, because whoever calls this expects something to protect. A
// / missing contract, or a served location it places outside the hosted location, would
// / otherwise stop the protection file being kept without a word.
function maintainDataProtection() {
  // / Set variables.
  global $EnableMemoryProtection;
  $ProtectionIsCurrent = FALSE;
  $servedPaths = array();
  $servedPath = '';
  $maintainProtection = TRUE;
  $maintainProtection = protectionFileIsMaintained();
  $servedPaths = servedLocations();
  if (count($servedPaths) === 0) warningEntry('No served location is set inside the hosted location, so no protection file was kept current.');
  else {
    $ProtectionIsCurrent = TRUE;
    foreach ($servedPaths as $servedPath) if (!maintainProtectionFile($servedPath, $maintainProtection)) $ProtectionIsCurrent = FALSE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $servedPaths, $servedPath, $maintainProtection);
  return $ProtectionIsCurrent; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to install or repair the protection file of one served location.
// / Accepts the served location & a boolean permitting repair, in that order.
// / Returns a validity boolean & the status word, in that order.
// / It has the shape of every other policy the Engine maintains, so it backs up a file it did not write & refuses one it cannot back up.
// / The policy is named for its folder followed by Directory, as DATA Directory is, which is how describePolicyStatus() knows a protection file.
// / Installing the file does not prove it works, & verifyServedExposure() is what proves it.
function verifyServedProtection($servedPath, $mayRepair) {
  // / Set variables.
  global $EnableMemoryProtection;
  $PolicyIsValid = FALSE;
  $PolicyStatus = 'unchecked';
  $protectionPath = '';
  $maintainProtection = TRUE;
  $maintainProtection = protectionFileIsMaintained();
  if ((string)$servedPath === '') warningEntry('A served location is not set, so its protection file could not be checked.');
  else if (!$maintainProtection) {
    // / The administrator has said not to maintain this file, so it is not maintained & the
    // / server configuration is carrying the rules alone. This is a valid configuration &
    // / is reported as a deliberate state rather than as a missing one.
    // / The removal of a file the application wrote earlier lives in maintainProtectionFile()
    // / & is called rather than repeated, so -fp does it now instead of leaving it for
    // / whichever web request happens to arrive next. Its answer changes nothing here, because
    // / a file it could not remove has already been named in a warning.
    maintainProtectionFile($servedPath, $maintainProtection);
    $PolicyIsValid = TRUE;
    $PolicyStatus = 'disabled'; }
  else {
    $protectionPath = rtrim((string)$servedPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'.htaccess';
    list ($PolicyIsValid, $PolicyStatus) = verifyPolicyFile(basename((string)$servedPath).' Directory', $protectionPath, dataProtectionContents(), $mayRepair); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $protectionPath, $maintainProtection, $servedPath, $mayRepair);
  return array($PolicyIsValid, $PolicyStatus); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to install or repair the protection file of every served location.
// / Accepts a boolean permitting repair.
// / Returns a validity boolean & the status word, in that order.
// / The status is the first failed location's, or else the first one that is not ok, so a
// / location that is fine never hides one that is not. HRConvert2 serves one location, so its
// / answer is exactly that location's.
function verifyDataProtectionPolicy($mayRepair) {
  // / Set variables.
  global $EnableMemoryProtection;
  $PolicyIsValid = FALSE;
  $PolicyStatus = 'unchecked';
  $servedPaths = array();
  $servedPath = $locationStatus = '';
  $locationIsValid = FALSE;
  $servedPaths = servedLocations();
  if (count($servedPaths) === 0) warningEntry('No served location is set inside the hosted location, so no protection file could be checked.');
  else {
    $PolicyIsValid = TRUE;
    foreach ($servedPaths as $servedPath) {
      list ($locationIsValid, $locationStatus) = verifyServedProtection($servedPath, $mayRepair);
      // / The first location that failed names the status. Until one has, the first that changed
      // / anything does, so a file written for one location never stands in for one refused.
      if (!$locationIsValid && $PolicyIsValid) {
        $PolicyIsValid = FALSE;
        $PolicyStatus = $locationStatus; }
      else if ($PolicyIsValid && ($PolicyStatus === 'unchecked' or $PolicyStatus === 'ok')) $PolicyStatus = $locationStatus; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $servedPaths, $servedPath, $locationStatus, $locationIsValid, $mayRepair);
  return array($PolicyIsValid, $PolicyStatus); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to list the forms a path takes, as it is written & as it resolves.
// / Accepts the path. Returns each form once, without a trailing separator, with the top of the
// / filesystem as a lone separator. An empty path has no forms.
function pathForms($pathText) {
  // / Set variables.
  global $EnableMemoryProtection;
  $PathForms = array();
  $pathForm = $resolvedPath = '';
  $pathForm = rtrim((string)$pathText, DIRECTORY_SEPARATOR);
  if ($pathForm === '' && (string)$pathText !== '') $pathForm = DIRECTORY_SEPARATOR;
  // / An empty path is never resolved, because realpath answers an empty path with the current
  // / folder & the comparison would then be against wherever the command happened to be run.
  if ($pathForm !== '') {
    $PathForms[] = $pathForm;
    $resolvedPath = (string)@realpath($pathForm);
    if ($resolvedPath !== '' && $resolvedPath !== $pathForm) $PathForms[] = $resolvedPath; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $PathForms is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $pathForm, $resolvedPath, $pathText);
  return $PathForms; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether one path lies below another, by their text alone.
// / Accepts the outer path & the inner path, in that order, each as one form, written or resolved.
// / Returns TRUE when the inner path is below the outer one & is not the outer one. The comparison
// / is by whole folders, so /var/www/html2 is not below /var/www/html, & nothing is resolved.
function pathTextIsBelow($outerText, $innerText) {
  // / Set variables.
  global $EnableMemoryProtection;
  $TextIsBelow = FALSE;
  $outerFolder = $innerFolder = '';
  // / Each is compared as a folder, ending in one separator, so the top of the filesystem is itself.
  $outerFolder = rtrim((string)$outerText, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
  $innerFolder = rtrim((string)$innerText, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
  $TextIsBelow = ((string)$innerText !== '' && $innerFolder !== $outerFolder && strpos($innerFolder, $outerFolder) === 0);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $outerFolder, $innerFolder, $outerText, $innerText);
  return $TextIsBelow; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether one path holds another.
// / Accepts the outer path & the inner path, in that order.
// / Returns TRUE when the inner path is the outer path or lies anywhere below it.
// / The comparison is by whole folders, so /var/www/html2 is not inside /var/www/html.
// / Each path is judged as it is written & as it resolves, & either way is enough. The web
// / server walks a path as it is written, so a location written inside the document root is
// / served even when a link there leads somewhere else, & a link can lead back in from outside.
function pathHoldsPath($outerPath, $innerPath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $PathIsHeld = FALSE;
  $outerForm = $innerForm = '';
  foreach (pathForms($outerPath) as $outerForm) foreach (pathForms($innerPath) as $innerForm) if ($innerForm === $outerForm or pathTextIsBelow($outerForm, $innerForm)) $PathIsHeld = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $outerForm, $innerForm, $outerPath, $innerPath);
  return $PathIsHeld; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to list the root of every data location, the primary first & then each pool member.
// / Accepts nothing. Returns the list, without repeats.
// / A pool member is declared by its root & verifyGlobals rewrites it to the Sessions folder
// / below that root, so the root is that folder's parent. Either form is understood here.
function dataLocationRoots() {
  // / Set variables.
  global $DataRootLoc, $AdditionalConvertLocs, $EnableMemoryProtection;
  $DataRoots = array();
  $additionalEntry = array();
  $additionalPath = '';
  if (isset($DataRootLoc) && is_string($DataRootLoc) && trim($DataRootLoc) !== '') $DataRoots[] = rtrim($DataRootLoc, DIRECTORY_SEPARATOR);
  if (isset($AdditionalConvertLocs) && is_array($AdditionalConvertLocs)) {
    foreach ($AdditionalConvertLocs as $additionalEntry) {
      if (!is_array($additionalEntry) or !isset($additionalEntry[0]) or !is_string($additionalEntry[0]) or trim($additionalEntry[0]) === '') continue;
      $additionalPath = rtrim($additionalEntry[0], DIRECTORY_SEPARATOR);
      $DataRoots[] = (basename($additionalPath) === 'Sessions') ? dirname($additionalPath) : $additionalPath; } }
  $DataRoots = array_values(array_unique($DataRoots));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $DataRoots is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $additionalEntry, $additionalPath);
  return $DataRoots; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to list every data location inside the web server's document root.
// / Accepts nothing. Returns the roots of those locations, or nothing when none is inside.
// / The data location is never meant to be served. Inside the document root its Sessions, Cache
// / & Logs folders could be fetched by anybody who guessed a name, because the web server account
// / that serves them can read them. Only the served locations in the hosted location are served.
function misplacedDataRoots() {
  // / Set variables.
  global $EnableMemoryProtection;
  $MisplacedRoots = array();
  $dataRoot = $documentRoot = '';
  // / The document root is the application's setting, read through the map as DocumentRoot.
  $documentRoot = applicationSetting('DocumentRoot', '');
  // / With no document root configured there is nothing to be inside.
  if (is_string($documentRoot) && trim($documentRoot) !== '') foreach (dataLocationRoots() as $dataRoot) if (pathHoldsPath($documentRoot, $dataRoot)) $MisplacedRoots[] = $dataRoot;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $MisplacedRoots is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $dataRoot, $documentRoot);
  return $MisplacedRoots; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to prove whether one served location serves user content as inert downloads.
// / Accepts the served location.
// / Returns a protection boolean, a status word & a sentence explaining it, in that order.
// /
// / This asks the server, & does not read a file off the disk & assume.
// / Apache ignores a .htaccess in silence where AllowOverride is off, as Debian, Ubuntu & the php apache container ship /var/www.
// / A check that tested for the file would report success on exactly the installations that are exposed.
// / A canary carrying an inert script element is written into the location, fetched over HTTP & removed.
// /
// / The address is the server URL & the location's path below the hosted location, so HRConvert2's is /DATA/ below its URL.
// / A location outside the hosted location has no address this can know, & is reported as not established.
// / classifyServedLocations() refuses such a location, so none of the Engine's callers asks.
// /
// / A failure to reach the server is neither a pass nor a failure, & is reported as not established with a warning.
// / The server URL may be a name this host cannot resolve, & during --setup the web server may not be listening yet.
// /
// / The probe is written with writeFileWithoutFollowing & is never changed by name afterwards.
// / The location belongs to the web server account & this runs as root, so a probe changed by name could be swapped for a link first.
function verifyServedExposure($servedPath) {
  // / Set variables.
  global $Verbose, $RunningAsRoot, $ApacheUser, $EnableMemoryProtection;
  $DataIsProtected = FALSE;
  $ExposureStatus = 'unverified';
  $ExposureDetail = '';
  $canaryName = $canaryPath = $canaryURL = $responseHeaders = $locationName = $servedURL = $hostedForm = $servedForm = $hostedLocation = $serverURL = '';
  $requestSucceeded = $carriesDisposition = $carriesSandbox = $canaryWasWritten = $ownerWasChanged = $canaryWasRemoved = $parentIsHeld = FALSE;
  $writeNotice = $ownerNotice = $removalNotice = $holdNotice = '';
  $locationName = basename((string)$servedPath);
  // / The hosted location & the address it is served at are the application's, read through the
  // / map as HostedLocation & ServerURL.
  $hostedLocation = (string)applicationSetting('HostedLocation', '');
  $serverURL = (string)applicationSetting('ServerURL', '');
  // / The location's address is worked out from whichever form of each path, as written or as
  // / resolved, places it below the hosted location. Each folder name in it is encoded, so a
  // / name holding a space or a # is asked for at the address the web server serves it at.
  foreach (pathForms($hostedLocation) as $hostedForm) foreach (pathForms((string)$servedPath) as $servedForm) {
    if ($servedURL === '' && pathTextIsBelow($hostedForm, $servedForm)) $servedURL = rtrim($serverURL, '/').'/'.implode('/', array_map('rawurlencode', explode(DIRECTORY_SEPARATOR, (string)substr($servedForm, strlen(rtrim($hostedForm, DIRECTORY_SEPARATOR)) + 1)))).'/'; }
  if ((string)$servedPath === '' or $serverURL === '') {
    $ExposureStatus = 'unverified';
    $ExposureDetail = 'The '.$locationName.' location or the server URL is not set, so nothing could be tested.';
    warningEntry('The '.$locationName.' directory exposure check could not run because the '.$locationName.' location or the server URL is not set.'); }
  else if ($servedURL === '') {
    $ExposureStatus = 'unverified';
    $ExposureDetail = $servedPath.' is not inside the hosted location, so the address it is served at is not known & nothing could be tested.';
    warningEntry('The '.$locationName.' directory exposure check could not run because '.$servedPath.' is not inside the hosted location '.$hostedLocation.'.'); }
  else {
    // / A served location root has to create belongs to the web server account, like every other.
    // / Root makes it only in a folder no other account can change, & never makes the folders
    // / above it, because mkdir follows every folder above the one it makes.
    if (!is_dir($servedPath) && !is_link($servedPath)) {
      list ($parentIsHeld, $holdNotice) = folderIsHeldForRoot(dirname((string)$servedPath));
      if (!$parentIsHeld) warningEntry('The '.$locationName.' location at '.$servedPath.' does not exist & was not created. '.$holdNotice);
      else if (@mkdir($servedPath, 0700) && $RunningAsRoot) {
        list ($ownerWasChanged, $ownerNotice) = changeOwnerWithoutFollowing($servedPath, $ApacheUser, $ApacheUser);
        if (!$ownerWasChanged) warningEntry($ownerNotice); } }
    // / The probe is named for the application, as its policy marker is.
    $canaryName = engineApplicationSlug().'-protection-canary-'.substr(bin2hex(random_bytes(8)), 0, 16).'.svg';
    $canaryPath = $servedPath.DIRECTORY_SEPARATOR.$canaryName;
    $canaryURL = $servedURL.$canaryName;
    // / The probe carries a script element so it has the same shape as a real upload. The
    // / script is empty, so a browser that did run it would do nothing.
    list ($canaryWasWritten, $writeNotice) = writeFileWithoutFollowing($canaryPath, '<svg xmlns="http://www.w3.org/2000/svg"><script>/* inert probe */</script></svg>', 0644);
    if (!$canaryWasWritten) {
      $ExposureStatus = 'unverified';
      $ExposureDetail = 'A probe file could not be written into '.$servedPath.', so nothing could be tested.';
      warningEntry('The '.$locationName.' directory exposure check could not write its probe into '.$servedPath.'. '.$writeNotice); }
    else {
      list ($requestSucceeded, $responseHeaders) = fetchOwnResponseHeaders($canaryURL, 5);
      // / Remove the probe whatever happened. It must never outlive the check that made it.
      // / The location is the web server account's, so root removes it through the kernel.
      list ($canaryWasRemoved, $removalNotice) = removeFileWithoutFollowing($canaryPath);
      if (!$canaryWasRemoved) warningEntry('The '.$locationName.' directory exposure probe could not be removed. '.$removalNotice);
      if (!$requestSucceeded) {
        $ExposureStatus = 'unreachable';
        $ExposureDetail = 'This server could not fetch '.$canaryURL.' from itself. Check it by hand in a browser.';
        warningEntry('The '.$locationName.' directory exposure check could not reach '.$canaryURL.'. The exposure of this installation was NOT established. Verify it by hand.'); }
      // / A 5xx is not exposure & must never be reported as one.
      // / It means this tree serves nothing at all, so every download & share link on this server is broken, which needs its own word.
      // / The common cause is a directive in the protection file that the directory's AllowOverride level does not permit.
      // / Apache refuses the request rather than ignoring such a directive, & an <IfModule> guard does not help.
      else if (stripos($responseHeaders, ' 500') !== FALSE or stripos($responseHeaders, ' 503') !== FALSE) {
        $ExposureStatus = 'broken';
        $ExposureDetail = 'The server returned an error for '.$canaryURL.'. NO download & NO share link works right now. Check the web server error log for a line naming '.$locationName.'/.htaccess.';
        errorEntry('The '.$locationName.' directory is returning a server error, so no download & no share link can work. The usual cause is a directive in '.$locationName.'/.htaccess that this directory\'s AllowOverride level does not permit; the web server error log names it. Delete that file to restore service, then apply the rules in the server configuration instead.', 503, FALSE); }
      else if (stripos($responseHeaders, '404 ') !== FALSE or stripos($responseHeaders, '403 ') !== FALSE) {
        $ExposureStatus = 'unreachable';
        $ExposureDetail = 'The server refused or did not find '.$canaryURL.'. Either --Server URL-- does not describe this installation, or the '.$locationName.' tree is not served at that path.';
        warningEntry('The '.$locationName.' directory exposure check received a refusal for '.$canaryURL.'. The --Server URL-- setting may not describe this installation.'); }
      else {
        $carriesDisposition = (stripos($responseHeaders, 'content-disposition:') !== FALSE && stripos($responseHeaders, 'attachment') !== FALSE);
        $carriesSandbox = (stripos($responseHeaders, 'content-security-policy:') !== FALSE && stripos($responseHeaders, 'sandbox') !== FALSE);
        // / Either one closes it. Both are set together, so one missing is worth saying.
        if ($carriesDisposition or $carriesSandbox) {
          $DataIsProtected = TRUE;
          $ExposureStatus = 'protected';
          $ExposureDetail = 'The '.$locationName.' tree is served as inert content.';
          if (!$carriesDisposition) $ExposureDetail = $ExposureDetail.' Content-Disposition is absent & the CSP is carrying this alone.';
          if (!$carriesSandbox) $ExposureDetail = $ExposureDetail.' The CSP is absent & Content-Disposition is carrying this alone.';
          if ($Verbose) logEntry($locationName.' Exposure Check: protected. '.($carriesDisposition ? 'Content-Disposition present. ' : '').($carriesSandbox ? 'CSP sandbox present.' : '')); }
        else {
          $ExposureStatus = 'exposed';
          $ExposureDetail = 'The '.$locationName.' tree serves user supplied files as renderable documents. An uploaded SVG will execute its own script in this origin.';
          // / This is a warning rather than an error because the application still works &
          // / an administrator can still use it. It is loud because nothing else will say so.
          warningEntry('THE '.strtoupper($locationName).' DIRECTORY IS EXPOSED. User supplied files at '.$servedURL.' are served as renderable documents rather than as downloads, so an uploaded SVG will execute its own script in this origin. If you installed the '.$locationName.'/.htaccess, it is being ignored; Apache reads one only where AllowOverride is enabled for that directory. Run the -fp argument as root to write & activate the rules in the server configuration instead. See Documentation/ABOUT_DATA_DIRECTORY_PROTECTION.txt.'); } } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $canaryWasRemoved, $removalNotice, $canaryName, $canaryPath, $canaryURL, $responseHeaders, $locationName, $servedURL, $hostedForm, $servedForm, $hostedLocation, $serverURL, $requestSucceeded, $carriesDisposition, $carriesSandbox, $canaryWasWritten, $writeNotice, $ownerWasChanged, $ownerNotice, $parentIsHeld, $holdNotice, $servedPath);
  return array($DataIsProtected, $ExposureStatus, $ExposureDetail); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to prove whether every served location serves user content as inert downloads.
// / Accepts no arguments.
// / Returns a protection boolean, a status word & a sentence explaining it, in that order.
// / Each served location is asked on its own by verifyServedExposure(), & the answer returned
// / is the worst of them, in this order: broken, exposed, unreachable, unverified, protected.
// / So a location that is fine never hides one that is not. HRConvert2 serves one location, so
// / its answer is exactly that location's.
function verifyDataExposure() {
  // / Set variables.
  global $EnableMemoryProtection;
  $DataIsProtected = FALSE;
  $ExposureStatus = 'unverified';
  $ExposureDetail = '';
  $servedPaths = $statusOrder = array();
  $servedPath = $locationStatus = $locationDetail = '';
  $locationIsProtected = FALSE;
  $worstRank = $locationRank = 0;
  $statusOrder = array('broken', 'exposed', 'unreachable', 'unverified', 'protected');
  $worstRank = count($statusOrder);
  $servedPaths = servedLocations();
  if (count($servedPaths) === 0) {
    $ExposureDetail = 'No served location is set inside the hosted location, so nothing could be tested.';
    warningEntry('The exposure check could not run, because no served location is set inside the hosted location.'); }
  else foreach ($servedPaths as $servedPath) {
    list ($locationIsProtected, $locationStatus, $locationDetail) = verifyServedExposure($servedPath);
    // / A status this does not know is ranked worst, because an answer nobody can read is not a pass.
    $locationRank = array_search($locationStatus, $statusOrder, TRUE);
    if ($locationRank === FALSE) $locationRank = 0;
    if ($locationRank < $worstRank) {
      $worstRank = $locationRank;
      $ExposureStatus = $locationStatus;
      $ExposureDetail = $locationDetail; } }
  $DataIsProtected = ($ExposureStatus === 'protected');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $servedPaths, $statusOrder, $servedPath, $locationStatus, $locationDetail, $locationIsProtected, $worstRank, $locationRank);
  return array($DataIsProtected, $ExposureStatus, $ExposureDetail); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the path of this application's own copy of bubblewrap.
// / Accepts nothing. Returns the absolute path, which is the path the AppArmor profile attaches to.
// / The copy is this application's alone.
// / Ubuntu grants an unprivileged namespace through a profile attached to a program's own binary, as its profiles for Steam, Flatpak & Chrome are.
// / A profile attached to the system's bubblewrap would hand a namespace to every program on the host that runs it.
// / The copy lives in a folder of its own under /usr/local/libexec, where a helper program installed locally belongs.
// / It is named for the application, so two applications on one host never share one.
function privateBwrapPath() {
  // / Set variables.
  $BinaryPath = '/usr/local/libexec/'.engineApplicationSlug().'/bwrap';
  return $BinaryPath; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether this host is one where every sandbox runs this application's own
// / copy of bubblewrap whenever the kernel restricts unprivileged namespaces.
// / Accepts nothing. Returns TRUE on a host, not a container, whose kernel can restrict them,
// / whether or not it does now, & whose AppArmor can load the profile attached to the copy.
// / A container cannot load a profile, a kernel without the restriction needs none, & an AppArmor
// / that cannot read the userns rule is never handed one, so none of the three takes a copy.
function sandboxBinaryIsNeeded() {
  // / Set variables.
  global $RunningInContainer;
  $BinaryIsNeeded = FALSE;
  $BinaryIsNeeded = (!$RunningInContainer && namespaceRestriction() !== '' && is_file('/etc/apparmor.d/abi/4.0'));
  return $BinaryIsNeeded; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether this application's own copy of bubblewrap may be run.
// / Accepts the path of the copy. Returns TRUE when it is a plain file root owns, that no other
// / account can change, in folders root alone can change, & FALSE otherwise.
// / The profile grants a namespace to whatever runs at that path, so a copy another account could change would be its way past the restriction.
// / A copy that is not root's alone is never run, & --setup --update-depends replaces it.
// / A path reached through a link is refused too, because AppArmor attaches the profile to the path a program really has.
// / PHP's memory of the path is cleared first, because a long running process such as the listener would judge a stale state.
function sandboxBinaryIsSafe($binaryPath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $BinaryIsSafe = $pathIsTrusted = FALSE;
  $resolvedPath = $trustNotice = '';
  clearstatcache(TRUE, $binaryPath);
  if (is_file($binaryPath) && !is_link($binaryPath)) {
    list ($pathIsTrusted, $resolvedPath, $trustNotice) = pathIsTrustedByRoot($binaryPath);
    $BinaryIsSafe = ($pathIsTrusted && $resolvedPath === $binaryPath); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $pathIsTrusted, $resolvedPath, $trustNotice, $binaryPath);
  return $BinaryIsSafe; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether the account asking can judge this application's own copy of bubblewrap.
// / Accepts nothing. Returns TRUE for root, which runs the copy as the web server's account, & for the
// / web server's account itself.
// / Only root & the web server account's group may run the copy, so an administrator who is neither cannot build a sandbox through it.
// / What such an account finds says nothing of the web server account, so it is reported as not judged rather than as a copy that will not run.
function sandboxCopyIsJudgedHere() {
  // / Set variables.
  global $RunningAsRoot, $CurrentUser, $ApacheUser;
  $CopyIsJudgedHere = ($RunningAsRoot or (string)$CurrentUser === (string)$ApacheUser);
  return $CopyIsJudgedHere; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether the account that runs conversions can run this application's own
// / copy of bubblewrap.
// / Accepts the path of the copy. Returns whether that account ran it & the account it was run as,
// / in that order.
// / A copy root trusts can still be one the web server account cannot run.
// / A folder above it closed to that account, a group that is not its own, or a filesystem that runs no programs stops it.
// / Lifting the namespace restriction answers none of them.
// / So it is run with --version, which builds no namespace, as that account when root asks & as the account asking otherwise.
// / The answer is about running the program & nothing else.
function sandboxBinaryIsRunnable($binaryPath) {
  // / Set variables.
  global $RunningAsRoot, $CurrentUser, $ApacheUser, $EnableMemoryProtection;
  $BinaryIsRunnable = FALSE;
  $RunAccount = $versionCommand = '';
  $commandOutput = array();
  $commandExitCode = 1;
  $RunAccount = (($RunningAsRoot && $CurrentUser !== $ApacheUser) ? (string)$ApacheUser : (string)$CurrentUser);
  // / This runs unsandboxed, as every probe of the sandbox does, because it is bubblewrap itself.
  // / It takes no argument but --version, so it starts nothing & reads nothing.
  $versionCommand = 'timeout -k 5 10 '.escapeshellarg($binaryPath).' --version 2>&1';
  if ($RunningAsRoot && $CurrentUser !== $ApacheUser) list ($commandOutput, $commandExitCode) = runAsAccount($versionCommand, $ApacheUser);
  else exec($versionCommand, $commandOutput, $commandExitCode);
  $BinaryIsRunnable = ($commandExitCode === 0);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $versionCommand, $commandOutput, $commandExitCode, $binaryPath);
  return array($BinaryIsRunnable, $RunAccount); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether this host's sandboxes want for this application's own copy.
// / Accepts nothing. Returns TRUE where the host takes a copy & the copy is missing, is not root's
// / alone, or cannot be run by the account that runs conversions.
// / A restriction is never lifted to cover any of those, because making the copy again answers each, & --setup --update-depends does that.
// / An account that cannot judge whether the copy runs takes it for one that does, because what it finds is about itself.
function sandboxBinaryIsUnusable() {
  // / Set variables.
  global $EnableMemoryProtection;
  $BinaryIsUnusable = $copyIsRunnable = FALSE;
  $binaryPath = $runAccount = '';
  $binaryPath = privateBwrapPath();
  if (sandboxBinaryIsNeeded()) {
    if (sandboxBinaryIsSafe($binaryPath) && !sandboxCopyIsJudgedHere()) $copyIsRunnable = TRUE;
    else if (sandboxBinaryIsSafe($binaryPath)) list ($copyIsRunnable, $runAccount) = sandboxBinaryIsRunnable($binaryPath);
    $BinaryIsUnusable = !$copyIsRunnable; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $copyIsRunnable, $binaryPath, $runAccount);
  return $BinaryIsUnusable; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether only root could put a program where the sandbox profile attaches.
// / Accepts nothing. Returns whether the path is held & the reason it is not, in that order.
// / The profile grants a namespace to whatever runs at the copy's path, whoever put it there.
// / So it is attached only where the copy, or the deepest part of its path that exists, & every folder above that are root's alone.
// / They are on a stock Ubuntu host.
// / A host where another account can write a folder on the path, as Debian's staff group can write /usr/local, gets no profile.
function sandboxPathIsHeld() {
  // / Set variables.
  global $EnableMemoryProtection;
  $PathIsHeld = $pathIsTrusted = FALSE;
  $HoldNotice = $existingPath = $resolvedPath = '';
  $existingPath = privateBwrapPath();
  clearstatcache(TRUE, $existingPath);
  while ($existingPath !== DIRECTORY_SEPARATOR && !file_exists($existingPath) && !is_link($existingPath)) {
    $existingPath = dirname($existingPath);
    clearstatcache(TRUE, $existingPath); }
  list ($pathIsTrusted, $resolvedPath, $HoldNotice) = pathIsTrustedByRoot($existingPath);
  if ($pathIsTrusted && $resolvedPath !== $existingPath) $HoldNotice = $existingPath.' leads to '.$resolvedPath.'.';
  $PathIsHeld = ($pathIsTrusted && $resolvedPath === $existingPath);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $pathIsTrusted, $existingPath, $resolvedPath);
  return array($PathIsHeld, $HoldNotice); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report on this application's own copy of bubblewrap.
// / Accepts the bubblewrap binary the sandboxes here were just built with, or FALSE where none was
// / built or none was tried. Returns a status word & a sentence, in that order.
// / The status is 'none' where no copy is needed, 'ok', 'MISSING' when no sandbox can be built
// / without it, 'UNSAFE' when it is not root's alone, 'ORPHANED' when the bubblewrap it was copied
// / from is no longer installed, 'NOT RUNNABLE' when the account that runs conversions cannot run
// / it, or 'BEHIND' when it is the one every sandbox runs & differs from the system's now. An
// / account that can neither run it nor hand it to the web server's account is told it is ok &
// / that root can say whether that account can run it.
// / It only reports, because a copy is made or refreshed only when dependencies or the application are updated.
// / -fp & the Environment Manager therefore never change the program every sandbox runs.
// / A copy nothing needs right now is reported ok, with what to run before it is needed.
// / A copy behind the system's is a problem only while it is in use, because a fix to the system's bubblewrap reaches no sandbox until it is refreshed.
function verifySandboxBinary($sandboxBinary) {
  // / Set variables.
  global $ApacheUser, $EnableMemoryProtection;
  $BinaryStatus = 'none';
  $BinaryDetail = $binaryPath = $systemPath = $binaryHash = $systemHash = $trustNotice = $resolvedPath = $commandHint = $runAccount = '';
  $copyIsInUse = $pathIsTrusted = $copyIsRunnable = $copyIsJudged = FALSE;
  $binaryPath = privateBwrapPath();
  $copyIsInUse = ($sandboxBinary === $binaryPath);
  $systemPath = locateDependency('bwrap');
  if ($systemPath !== '') $systemPath = (string)@realpath($systemPath);
  // / Without the system's bubblewrap there is nothing to copy, & installing the dependencies is
  // / what makes both.
  $commandHint = ($systemPath === '' ? ' Run --setup --install-depends as root to install bubblewrap & the copy.' : ' Run --setup --update-depends as root to install it.');
  clearstatcache(TRUE, $binaryPath);
  if (!sandboxBinaryIsNeeded()) $BinaryStatus = 'none';
  else if (!file_exists($binaryPath) && !is_link($binaryPath)) {
    $BinaryStatus = ((namespaceRestriction() === '1' && $sandboxBinary === FALSE) ? 'MISSING' : 'ok');
    $BinaryDetail = 'Not installed at '.$binaryPath.'. '.($BinaryStatus === 'MISSING' ? 'No sandbox can be built without it while unprivileged namespaces are restricted.' : 'Nothing needs it now.').$commandHint; }
  else if (!sandboxBinaryIsSafe($binaryPath)) {
    list ($pathIsTrusted, $resolvedPath, $trustNotice) = pathIsTrustedByRoot($binaryPath);
    $BinaryStatus = 'UNSAFE';
    $BinaryDetail = $binaryPath.' is not root\'s alone, so no sandbox runs it.'.($trustNotice === '' ? '' : ' '.$trustNotice).' Run --setup --update-depends as root to replace it, once every folder above it is root\'s alone.'; }
  else if ($systemPath === '') {
    $BinaryStatus = 'ORPHANED';
    $BinaryDetail = $binaryPath.' copies a bubblewrap that is no longer installed. No sandbox runs it. Run --setup --install-depends as root.'; }
  else {
    // / A copy just used to build a sandbox has run. Any other is run, because root's checks cannot
    // / see whether the account that runs conversions may, unless the account asking cannot run it.
    $copyIsJudged = sandboxCopyIsJudgedHere();
    if ($copyIsInUse) $copyIsRunnable = TRUE;
    else if ($copyIsJudged) list ($copyIsRunnable, $runAccount) = sandboxBinaryIsRunnable($binaryPath);
    // / An account that cannot read the copy cannot compare it, & is not told it differs.
    if ($copyIsRunnable && $systemPath !== $binaryPath) {
      $binaryHash = (string)@hash_file('sha256', $binaryPath);
      $systemHash = (string)@hash_file('sha256', $systemPath); }
    if (!$copyIsInUse && !$copyIsJudged) {
      $BinaryStatus = 'ok';
      $BinaryDetail = $binaryPath.', AppArmor profile '.sandboxApparmorProfileName().'. Only root & group '.$ApacheUser.' may run it. Run -v as root.'; }
    else if (!$copyIsRunnable) {
      $BinaryStatus = 'NOT RUNNABLE';
      $BinaryDetail = $binaryPath.' is in place, & '.$runAccount.' cannot run it. Every folder above it must let that account through, it must belong to that account\'s group, & its filesystem must run programs. Run --setup --update-depends as root to make it again.'; }
    else if ($binaryHash !== '' && $systemHash !== '' && $binaryHash !== $systemHash) {
      $BinaryStatus = ($copyIsInUse ? 'BEHIND' : 'ok');
      $BinaryDetail = $binaryPath.' differs from '.$systemPath.', which has been updated since it was copied'.($copyIsInUse ? '' : ', & nothing runs it now').'. Run --setup --update-depends as root to take the update.'; }
    else {
      $BinaryStatus = 'ok';
      $BinaryDetail = $binaryPath.', AppArmor profile '.sandboxApparmorProfileName().'.'; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $binaryPath, $systemPath, $binaryHash, $systemHash, $trustNotice, $resolvedPath, $commandHint, $runAccount, $copyIsInUse, $pathIsTrusted, $copyIsRunnable, $copyIsJudged, $sandboxBinary);
  return array($BinaryStatus, $BinaryDetail); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to install or refresh this application's own copy of bubblewrap, & the profile
// / attached to it.
// / Accepts the width of the column the line's label is printed in, so the line lines up with
// / those printed around it. Returns a sanity boolean, a status word & a sentence, in that order.
// / The boolean is TRUE when nothing is left to do, which is a copy matching the system's that the
// / web server's account can run, with its profile in force, or a host that takes no copy. The
// / status is 'none' where no copy is needed, 'installed', 'updated', 'unchanged', 'unsupported' or
// / 'FAILED'. Each but 'none' is printed for the command that asked, & each that leaves something
// / to do is a warning.
// / It runs only when dependencies or the application are updated, & never from -fp.
// / --setup --install-depends, --update-depends & --install-complete call it, & so do the hand over to an installed copy & -u.
// / So neither -fp nor an unattended repair changes the program every sandbox runs underneath a working installation.
// /
// / The copy is made from the system's bubblewrap, which must be root's alone, into a folder root alone can change.
// / No folder is made beneath one another account could change.
// / It is root's, & only root & the web server account's group may read or run it.
// / It is made where only root can open it, given its mode & only then its group, so no other account can hold it open for writing.
// / tempnam makes it 0600, which a default ACL on the folder cannot widen as it could the mode copy() would give it.
// / It is written beside its final name & renamed over it, so a sandbox starting meanwhile runs the old copy or the new one, & never part of either.
// / Then the web server account runs it, because root's checks cannot see what that account may run.
// / Then the profile is installed, so the copy carries a sandbox as soon as it is there.
function installSandboxBinary($columnWidth) {
  // / Set variables.
  global $RunningAsRoot, $RunningInContainer, $ApacheUser, $Lol, $EnableMemoryProtection;
  $SandboxBinaryIsReady = FALSE;
  $InstallStatus = 'none';
  $InstallDetail = $binaryPath = $binaryFolder = $folderPath = $systemPath = $resolvedSource = $stagingPath = $resolvedFolder = $resolvedParent = $trustNotice = $policyStatus = $groupAnswer = $sourceHash = $webGroupName = $ownerNotice = $runAccount = '';
  $accountRecord = $groupRecord = $commandOutput = array();
  $webGroupNumber = -1;
  $commandExitCode = 1;
  $sourceIsTrusted = $folderIsTrusted = $parentIsTrusted = $copyExisted = $copyIsWritten = $modeWasSet = $ownerWasChanged = $copyIsRunnable = $policyIsValid = FALSE;
  $binaryPath = privateBwrapPath();
  $binaryFolder = dirname($binaryPath);
  if (!$RunningAsRoot or $RunningInContainer or namespaceRestriction() === '') $SandboxBinaryIsReady = TRUE;
  else if (!sandboxBinaryIsNeeded()) {
    $InstallStatus = 'unsupported';
    $InstallDetail = 'The AppArmor here cannot read the userns rule the profile needs, so no copy was made.'; }
  else {
    $systemPath = locateDependency('bwrap');
    if ($systemPath !== '') list ($sourceIsTrusted, $resolvedSource, $trustNotice) = pathIsTrustedByRoot($systemPath);
    // / The web server's account is given the copy through its own group, which is the group it
    // / runs conversions with. id is asked where PHP has no posix functions, as elsewhere.
    if (function_exists('posix_getpwnam') && function_exists('posix_getgrgid')) {
      $accountRecord = @posix_getpwnam((string)$ApacheUser);
      if (is_array($accountRecord) && isset($accountRecord['gid'])) $groupRecord = @posix_getgrgid((int)$accountRecord['gid']); }
    if (is_array($groupRecord) && isset($groupRecord['name'], $groupRecord['gid'])) {
      $webGroupName = (string)$groupRecord['name'];
      $webGroupNumber = (int)$groupRecord['gid']; }
    else {
      exec('id -gn '.escapeshellarg((string)$ApacheUser).' 2>/dev/null', $commandOutput, $commandExitCode);
      $groupAnswer = trim(implode('', $commandOutput));
      if ($commandExitCode === 0 && preg_match('/^[A-Za-z0-9_.-]+$/', $groupAnswer) === 1) $webGroupName = $groupAnswer;
      $commandOutput = array();
      exec('id -g '.escapeshellarg((string)$ApacheUser).' 2>/dev/null', $commandOutput, $commandExitCode);
      $groupAnswer = trim(implode('', $commandOutput));
      if ($commandExitCode === 0 && preg_match('/^[0-9]+$/', $groupAnswer) === 1) $webGroupNumber = (int)$groupAnswer; }
    if ($systemPath === '' or $resolvedSource === $binaryPath) {
      $InstallStatus = 'FAILED';
      $InstallDetail = 'Bubblewrap is not installed, so there is nothing to copy.'; }
    else if (!$sourceIsTrusted) {
      $InstallStatus = 'FAILED';
      $InstallDetail = $systemPath.' is not root\'s alone, so it was not copied. '.$trustNotice; }
    else if ($webGroupNumber < 0 or $webGroupName === '') {
      $InstallStatus = 'FAILED';
      $InstallDetail = 'The web server\'s account, '.$ApacheUser.', could not be found, so no copy could be made for it alone.'; }
    else {
      // / Each folder is made only where nothing is, & only beneath a folder root alone can change,
      // / so nothing is made through a name another account could swap. It is given its mode
      // / outright, because mkdir leaves out whatever the umask removes.
      foreach (array(dirname($binaryFolder), $binaryFolder) as $folderPath) {
        list ($parentIsTrusted, $resolvedParent, $trustNotice) = pathIsTrustedByRoot(dirname($folderPath));
        if ($parentIsTrusted && $resolvedParent === dirname($folderPath) && !file_exists($folderPath) && !is_link($folderPath) && @mkdir($folderPath, 0755)) @chmod($folderPath, 0755); }
      list ($folderIsTrusted, $resolvedFolder, $trustNotice) = pathIsTrustedByRoot($binaryFolder);
      // / The copy's own folder is this application's, so the web server's account is let through it
      // / whatever made it. A folder above it is not, & is said below if it stops that account.
      if ($folderIsTrusted && $resolvedFolder === $binaryFolder && ((int)@fileperms($binaryFolder) & 07777) !== 0755) @chmod($binaryFolder, 0755);
      $sourceHash = (string)@hash_file('sha256', $resolvedSource);
      if (!$folderIsTrusted or $resolvedFolder !== $binaryFolder) {
        $InstallStatus = 'FAILED';
        $InstallDetail = $binaryFolder.' is not a folder root alone can change, so nothing was copied into it. '.$trustNotice; }
      else if (sandboxBinaryIsSafe($binaryPath) && @filegroup($binaryPath) === $webGroupNumber && ((int)@fileperms($binaryPath) & 07777) === 0750 && $sourceHash !== '' && (string)@hash_file('sha256', $binaryPath) === $sourceHash) {
        $InstallStatus = 'unchanged';
        $InstallDetail = $binaryPath.' matches '.$resolvedSource.'.'; }
      else {
        $copyExisted = (file_exists($binaryPath) or is_link($binaryPath));
        // / tempnam falls back to the system's temporary folder where it cannot write the one it is
        // / given, & a staging file anywhere else would never be renamed over the copy.
        $stagingPath = (string)@tempnam($binaryFolder, '.bwrap-');
        if ($stagingPath !== '' && dirname($stagingPath) !== $binaryFolder) {
          @unlink($stagingPath);
          $stagingPath = ''; }
        if ($stagingPath !== '') $copyIsWritten = @copy($resolvedSource, $stagingPath);
        if ($copyIsWritten) $modeWasSet = @chmod($stagingPath, 0750);
        if ($modeWasSet) list ($ownerWasChanged, $ownerNotice) = changeOwnerWithoutFollowing($stagingPath, 'root', $webGroupName);
        if ($ownerWasChanged && @rename($stagingPath, $binaryPath)) {
          $InstallStatus = ($copyExisted ? 'updated' : 'installed');
          $InstallDetail = $binaryPath.', copied from '.$resolvedSource.'.';
          logEntry(engineApplicationName().'\'s own bubblewrap was '.$InstallStatus.' at '.$binaryPath.', copied from '.$resolvedSource.'.'); }
        else {
          if ($stagingPath !== '') @unlink($stagingPath);
          $InstallStatus = 'FAILED';
          $InstallDetail = $binaryPath.' could not be written.'.($ownerNotice !== '' ? ' '.$ownerNotice : ''); } } }
    // / The account that runs conversions must be able to run the copy, which root's checks cannot
    // / see, & the profile goes in straight after, so the copy carries a sandbox as soon as it is
    // / there. Both are said with the copy's result, because it is no use without either.
    if ($InstallStatus === 'installed' or $InstallStatus === 'updated' or $InstallStatus === 'unchanged') {
      list ($copyIsRunnable, $runAccount) = sandboxBinaryIsRunnable($binaryPath);
      list ($policyIsValid, $policyStatus) = verifySandboxPolicy(TRUE);
      $SandboxBinaryIsReady = ($copyIsRunnable && $policyIsValid);
      if (!$copyIsRunnable) $InstallDetail = $InstallDetail.' '.$runAccount.' cannot run it. Every folder above it must let that account through, & its filesystem must run programs.';
      if (!$policyIsValid) $InstallDetail = $InstallDetail.' The '.sandboxApparmorProfileName().' profile is '.$policyStatus.', so no sandbox runs the copy yet.'; } }
  if ($InstallStatus !== 'none') print('  '.str_pad('Bwrap copy', max(11, (int)$columnWidth)).$InstallStatus.', '.$InstallDetail.$Lol);
  if ($InstallStatus !== 'none' && !$SandboxBinaryIsReady) warningEntry(engineApplicationName().'\'s own bubblewrap: '.$InstallStatus.'. '.$InstallDetail);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $binaryPath, $binaryFolder, $folderPath, $systemPath, $resolvedSource, $stagingPath, $resolvedFolder, $resolvedParent, $trustNotice, $policyStatus, $groupAnswer, $sourceHash, $webGroupName, $ownerNotice, $runAccount, $accountRecord, $groupRecord, $commandOutput, $webGroupNumber, $commandExitCode, $sourceIsTrusted, $folderIsTrusted, $parentIsTrusted, $copyExisted, $copyIsWritten, $modeWasSet, $ownerWasChanged, $copyIsRunnable, $policyIsValid, $columnWidth);
  return array($SandboxBinaryIsReady, $InstallStatus, $InstallDetail); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read whether this kernel restricts unprivileged user namespaces through AppArmor.
// / Accepts nothing. Returns what kernel.apparmor_restrict_unprivileged_userns reads, which is 1
// / while the restriction is in force, or an empty string on a kernel that has no such setting.
// / Ubuntu 23.10 & later ship it reading 1. Every account may read it, which is what lets a web
// / request decide how to build its sandbox without asking root anything.
function namespaceRestriction() {
  // / Set variables.
  global $EnableMemoryProtection;
  $RestrictionValue = '';
  $restrictionPath = '/proc/sys/kernel/apparmor_restrict_unprivileged_userns';
  if (file_exists($restrictionPath)) $RestrictionValue = trim((string)@file_get_contents($restrictionPath));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $restrictionPath);
  return $RestrictionValue; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the AppArmor profile attached to this application's own copy of bubblewrap.
// / Accepts nothing. Returns the profile name, which is also the name of its file in /etc/apparmor.d.
// / It is named for the application, so two applications on one host never share one.
// / HRConvert2's is hrconvert2-bwrap.
function sandboxApparmorProfileName() {
  // / Set variables.
  $ProfileName = engineApplicationSlug().'-bwrap';
  return $ProfileName; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report whether an AppArmor profile is loaded into the kernel.
// / Accepts the profile name as it is declared inside the profile file.
// / Returns a loaded boolean & a status word, in that order.
// / A profile on disk is not a profile in force.
// / A load that failed, or a reboot before AppArmor read it, leaves a perfect file enforcing nothing, so the loaded set is checked.
// / The loaded set is read from securityfs, which is what the kernel is using, & only root may read it.
// / Any other account is answered 'not readable by this account'.
// / The name is matched whole, as a line begins.
function apparmorProfileIsLoaded($profileName) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ProfileIsLoaded = FALSE;
  $ProfileStatus = 'unknown';
  $profilesPath = '/sys/kernel/security/apparmor/profiles';
  $loadedProfiles = '';
  if (!file_exists($profilesPath)) $ProfileStatus = 'apparmor not active';
  else {
    $loadedProfiles = @file_get_contents($profilesPath);
    if (!is_string($loadedProfiles)) $ProfileStatus = 'not readable by this account';
    else if (preg_match('/^'.preg_quote((string)$profileName, '/').' \(/m', $loadedProfiles) === 1) {
      $ProfileIsLoaded = TRUE;
      $ProfileStatus = 'loaded'; }
    else $ProfileStatus = 'NOT LOADED'; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $profilesPath, $loadedProfiles, $profileName);
  return array($ProfileIsLoaded, $ProfileStatus); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to load an AppArmor profile that has just been written.
// / Accepts the absolute path of the profile.
// / Returns TRUE when the parser accepted it.
// / A profile that is written but never loaded changes nothing until the next reboot, which makes a repair look like it failed.
function reloadApparmorProfile($profilePath) {
  // / Set variables.
  global $RunningAsRoot, $EnableMemoryProtection;
  $ProfileWasLoaded = FALSE;
  $parserBinary = '';
  $parserOutput = array();
  $parserExitCode = 1;
  $parserBinary = locateDependency('apparmor_parser');
  if (!$RunningAsRoot) warningEntry('An AppArmor profile was written but could not be loaded, because loading one requires root.');
  else if ($parserBinary === '') warningEntry('An AppArmor profile was written but apparmor_parser is not installed, so it was not loaded.');
  else {
    exec(escapeshellarg($parserBinary).' -r '.escapeshellarg($profilePath).' 2>&1', $parserOutput, $parserExitCode);
    if ($parserExitCode === 0) {
      $ProfileWasLoaded = TRUE;
      logEntry('The AppArmor profile at '.$profilePath.' was loaded.'); }
    else warningEntry('apparmor_parser refused the profile at '.$profilePath.'. '.implode(' ', $parserOutput)); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $parserBinary, $parserOutput, $parserExitCode, $profilePath);
  return $ProfileWasLoaded; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to take this application's sandbox profile out of the kernel & off the disk.
// / Accepts the absolute path of the profile. Returns TRUE when nothing of it is left on the disk or
// / in the kernel.
// / Only a file carrying this application's marker, in a folder root alone can change, is taken.
// / apparmor_parser -R takes the profiles that file names out of the kernel, & nothing else on the
// / host is touched. Taking one out needs root, as loading one does.
// / A profile still loaded is never called gone.
// / One the parser could not take out, or one loaded with no file left to name it, is taken out by name through the kernel's own interface.
// / The file goes only once neither profile is loaded, so the next repair can still name one that could not be taken out.
function removeSandboxPolicy($profilePath) {
  // / Set variables.
  global $RunningAsRoot, $EnableMemoryProtection;
  $PolicyWasRemoved = $folderIsTrusted = $fileIsOurs = $fileIsAbsent = $profileIsLoaded = $profileIsHeld = $profileWasUnloaded = FALSE;
  $parserBinary = $profileContents = $resolvedFolder = $trustNotice = $profileName = $loadStatus = '';
  $parserOutput = $profileNames = array();
  $parserExitCode = 1;
  clearstatcache(TRUE, $profilePath);
  $profileNames = array(sandboxApparmorProfileName(), engineApplicationSlug().'-unpriv-bwrap');
  list ($folderIsTrusted, $resolvedFolder, $trustNotice) = pathIsTrustedByRoot(dirname($profilePath));
  if ($folderIsTrusted && $resolvedFolder === dirname($profilePath) && is_file($profilePath) && !is_link($profilePath)) $profileContents = (string)@file_get_contents($profilePath);
  $fileIsOurs = (strpos($profileContents, enginePolicyMarker()) !== FALSE);
  $fileIsAbsent = (!file_exists($profilePath) && !is_link($profilePath));
  if ($RunningAsRoot && ($fileIsOurs or $fileIsAbsent)) {
    $parserBinary = locateDependency('apparmor_parser');
    if ($fileIsOurs && $parserBinary !== '') exec(escapeshellarg($parserBinary).' -R '.escapeshellarg($profilePath).' 2>&1', $parserOutput, $parserExitCode);
    foreach ($profileNames as $profileName) {
      list ($profileIsLoaded, $loadStatus) = apparmorProfileIsLoaded($profileName);
      if ($profileIsLoaded) {
        @file_put_contents('/sys/kernel/security/apparmor/.remove', $profileName);
        list ($profileIsLoaded, $loadStatus) = apparmorProfileIsLoaded($profileName);
        if (!$profileIsLoaded) $profileWasUnloaded = TRUE; }
      // / A list root cannot read is not a list without the profile.
      if ($profileIsLoaded or $loadStatus === 'not readable by this account') $profileIsHeld = TRUE; }
    if ($parserExitCode === 0) $profileWasUnloaded = TRUE;
    if (!$profileIsHeld && $fileIsOurs) $PolicyWasRemoved = @unlink($profilePath);
    else if (!$profileIsHeld) $PolicyWasRemoved = TRUE;
    if ($PolicyWasRemoved && ($fileIsOurs or $profileWasUnloaded)) logEntry('The '.sandboxApparmorProfileName().' profile was '.($profileWasUnloaded ? 'taken out of the kernel' : 'not loaded').($fileIsOurs ? ', & '.$profilePath.' was removed.' : '.')); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderIsTrusted, $fileIsOurs, $fileIsAbsent, $profileIsLoaded, $profileIsHeld, $profileWasUnloaded, $parserBinary, $profileContents, $resolvedFolder, $trustNotice, $profileName, $loadStatus, $parserOutput, $profileNames, $parserExitCode, $profilePath);
  return $PolicyWasRemoved; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to hold the AppArmor policy every sandbox this application builds runs under.
// / Accepts no arguments.
// / Returns the complete contents of an AppArmor profile file, holding two profiles.
// / Ubuntu 23.10 & later restrict unprivileged user namespaces, which stops bubblewrap & every sandboxed conversion with it.
// / A program may make one only under a profile that grants userns, which this does.
// / It is Ubuntu's own policy for bubblewrap, bwrap-userns-restrict as 26.04 ships it, attached to this application's own copy.
// / The first profile is bubblewrap's, which builds the namespace.
// / Everything bubblewrap starts runs stacked under the second as well, which denies every capability.
// / A hostile file can therefore use the namespace to reach nothing it could not reach before.
// / mediate_deleted judges a file made without a name, or deleted, by the name it had, so linking one into place is allowed.
// / It attaches to privateBwrapPath() & nothing else, so bubblewrap stays as it was for every other program on the host.
// / kernel.apparmor_restrict_unprivileged_unconfined stops a program entering a profile by name, & does not stop this.
// / The ABI stays 4.0, which 24.04 & every later release can read.
function sandboxApparmorContents() {
  // / Set variables.
  global $EnableMemoryProtection;
  $ProfileContents = $profileName = $childName = $sharedRules = '';
  $profileName = sandboxApparmorProfileName();
  $childName = engineApplicationSlug().'-unpriv-bwrap';
  // / Both profiles permit the same things. What separates them is the capabilities, which
  // / only bubblewrap's has.
  $sharedRules = '  allow file rwlkm /{**,},'.PHP_EOL
    .'  allow network,'.PHP_EOL
    .'  allow unix,'.PHP_EOL
    .'  allow ptrace,'.PHP_EOL
    .'  allow signal,'.PHP_EOL
    .'  allow mqueue,'.PHP_EOL
    .'  allow io_uring,'.PHP_EOL
    .'  allow userns,'.PHP_EOL
    .'  allow mount,'.PHP_EOL
    .'  allow umount,'.PHP_EOL
    .'  allow pivot_root,'.PHP_EOL
    .'  allow dbus,'.PHP_EOL;
  $ProfileContents = 'abi <abi/4.0>,'.PHP_EOL
    .'include <tunables/global>'.PHP_EOL
    .PHP_EOL
    .'# Written by '.engineApplicationName().'. Do not edit by hand.'.PHP_EOL
    .'# '.enginePolicyMarker().PHP_EOL
    .'# Ubuntu\'s policy for bubblewrap, bwrap-userns-restrict, attached to '.engineApplicationName().'\'s own copy.'.PHP_EOL
    .'# Bubblewrap may build an unprivileged user namespace. Everything it starts runs with no capabilities.'.PHP_EOL
    .PHP_EOL
    .'profile '.$profileName.' '.privateBwrapPath().' flags=(attach_disconnected,mediate_deleted) {'.PHP_EOL
    .'  allow capability,'.PHP_EOL
    .$sharedRules
    .'  allow pix /** -> &'.$profileName.'//&'.$childName.','.PHP_EOL
    .'  include if exists <local/'.$profileName.'>'.PHP_EOL
    .'}'.PHP_EOL
    .PHP_EOL
    .'profile '.$childName.' flags=(attach_disconnected,mediate_deleted) {'.PHP_EOL
    .$sharedRules
    .'  allow pix /** -> &'.$childName.','.PHP_EOL
    .'  audit deny capability,'.PHP_EOL
    .'  include if exists <local/'.$childName.'>'.PHP_EOL
    .'}'.PHP_EOL;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $profileName, $childName, $sharedRules);
  return $ProfileContents; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to take back a policy file an earlier release wrote into the host.
// / Accepts a human readable name, the absolute path, a boolean permitting a change & a boolean
// / permitting the file's removal where nothing can be put back, in that order.
// / Returns whether nothing of this application's is left there & a status word, in that order.
// / The status is 'none' when nothing of this application's is there, 'retired' when it was taken
// / back now, 'outdated' when it is there & no change was permitted, 'kept' when it replaced a file
// / no copy was kept of & may not be removed, or 'refused' when it could not be taken back.
// /
// / A file is put back as it was before this application replaced it.
// / verifyPolicyFile() keeps a timestamped copy of anything it replaces, so the newest copy is put back.
// / A file that replaced nothing is removed.
// / Only a file carrying this application's marker is touched, & only in a folder root alone can change.
// / The copies are left where they are, because nothing here could make them again.
// / A file a tool reads as its whole policy is never removed where nothing can be put back.
// / A copy can be missing or deleted by hand, & removing such a policy.xml would leave ImageMagick with no policy at all.
// / So it is kept & reported, & the administrator restores the original from its package or build.
// / An AppArmor addition is removed, because the profile that includes it reads none by default.
function retirePolicyFile($policyName, $policyPath, $mayRepair, $mayRemove) {
  // / Set variables.
  global $RunningAsRoot, $Verbose, $EnableMemoryProtection;
  $PolicyIsRetired = TRUE;
  $RetireStatus = 'none';
  $policyDirectory = $resolvedDirectory = $trustNotice = $trustedPath = $backupPrefix = $backupPath = $backupContents = $entryName = '';
  $directoryIsTrusted = $fileIsOurs = FALSE;
  $backupNames = array();
  $policyDirectory = dirname((string)$policyPath);
  // / Most hosts have nothing at the name, & are answered before anything is walked.
  if (file_exists((string)$policyPath) && is_dir($policyDirectory)) list ($directoryIsTrusted, $resolvedDirectory, $trustNotice) = pathIsTrustedByRoot($policyDirectory);
  if ($directoryIsTrusted) {
    $trustedPath = $resolvedDirectory.DIRECTORY_SEPARATOR.basename((string)$policyPath);
    $fileIsOurs = (is_file($trustedPath) && strpos((string)@file_get_contents($trustedPath), enginePolicyMarker()) !== FALSE); }
  else if (file_exists((string)$policyPath) && $Verbose) logEntry('The '.$policyName.' policy at '.$policyPath.' sits in a folder another account can change, so it is not judged here. '.$trustNotice);
  if ($fileIsOurs) {
    $backupPrefix = basename($trustedPath).'.'.engineApplicationSlug().'-backup-';
    foreach ((array)@scandir($resolvedDirectory) as $entryName) if (strpos((string)$entryName, $backupPrefix) === 0 && is_file($resolvedDirectory.DIRECTORY_SEPARATOR.$entryName)) $backupNames[] = (string)$entryName;
    // / The suffix is a timestamp written year first, so the last name is the newest copy.
    sort($backupNames, SORT_STRING); }
  if (!$fileIsOurs) $RetireStatus = 'none';
  else if (count($backupNames) === 0 && !$mayRemove) {
    $PolicyIsRetired = FALSE;
    $RetireStatus = 'kept';
    if ($mayRepair && $RunningAsRoot) warningEntry('The '.$policyName.' policy an earlier release wrote at '.$policyPath.' kept no copy of what it replaced, & removing it would leave no policy there at all, so it was left in place. Restore the original from the package or the build that provided it.'); }
  else if (!$mayRepair or !$RunningAsRoot) {
    $PolicyIsRetired = FALSE;
    $RetireStatus = 'outdated'; }
  else {
    if (count($backupNames) > 0) {
      $backupPath = $resolvedDirectory.DIRECTORY_SEPARATOR.$backupNames[count($backupNames) - 1];
      $backupContents = (string)@file_get_contents($backupPath);
      if (@file_put_contents($trustedPath, $backupContents) === strlen($backupContents) && (string)@file_get_contents($trustedPath) === $backupContents) {
        $RetireStatus = 'retired';
        warningEntry('The '.$policyName.' policy an earlier release wrote at '.$policyPath.' was put back as it was, from '.$backupPath.'.'); }
      else {
        $PolicyIsRetired = FALSE;
        $RetireStatus = 'refused';
        warningEntry('The '.$policyName.' policy an earlier release wrote at '.$policyPath.' could not be put back from '.$backupPath.'. Put it back by hand.'); } }
    else {
      @unlink($trustedPath);
      if (!file_exists($trustedPath)) {
        $RetireStatus = 'retired';
        warningEntry('The '.$policyName.' policy an earlier release wrote at '.$policyPath.' replaced nothing, so it was removed.'); }
      else {
        $PolicyIsRetired = FALSE;
        $RetireStatus = 'refused';
        warningEntry('The '.$policyName.' policy an earlier release wrote at '.$policyPath.' could not be removed. Remove it by hand.'); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $policyDirectory, $resolvedDirectory, $trustNotice, $trustedPath, $backupPrefix, $backupPath, $backupContents, $entryName, $directoryIsTrusted, $fileIsOurs, $backupNames, $policyName, $policyPath, $mayRepair, $mayRemove);
  return array($PolicyIsRetired, $RetireStatus); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to validate or repair the AppArmor profile every sandbox this application builds
// / runs under, which is attached to its own copy of bubblewrap.
// / Accepts a boolean permitting a write.
// / Returns a validity boolean & a status word, in that order.
// / Every sandboxed conversion depends on it, so the application checks it as every request &
// / command starts, rather than from a dependency check.
// / It is written wherever the kernel can restrict unprivileged namespaces, so a restriction switched on later finds it loaded.
// / A kernel without the restriction needs none.
// / An AppArmor that cannot read the userns rule is never handed one, because a profile that fails to load is tripped over at every boot.
// / A new one the parser refuses is taken out again.
// / Where the restriction is off, a missing or old profile is not a fault, & is written only by a command permitted to write it.
// / Root reads which profiles are loaded & loads this one when it is not, & any other account judges the file alone.
// / It is never attached where an account other than root could put a program, because it grants a namespace to whatever runs there.
// / Such a path is reported exposed, & a repair takes back one attached before.
// / A container's AppArmor is its host's, so a container writes none.
// / An extension of a distribution profile is taken back, & is what this reports until it is.
function verifySandboxPolicy($mayRepair) {
  // / Set variables.
  global $RunningAsRoot, $RunningInContainer, $EnableMemoryProtection;
  static $cachedValid = NULL;
  static $cachedStatus = 'unchecked';
  static $cachedMayRepair = FALSE;
  static $cachedPathIsHeld = TRUE;
  $PolicyIsValid = FALSE;
  $PolicyStatus = 'unchecked';
  $restrictionValue = $profileName = $profilePath = $profileContents = $profileLoadStatus = $retireStatus = $holdNotice = '';
  $profileIsLoaded = $includeIsRetired = $profileWasLoaded = $profileWasRemoved = FALSE;
  $pathIsHeld = TRUE;
  $restrictionValue = namespaceRestriction();
  if ($restrictionValue !== '' && !$RunningInContainer) list ($pathIsHeld, $holdNotice) = sandboxPathIsHeld();
  // / A cached result answers every later call but one. A failure found without permission to
  // / repair is checked again when a repair is permitted, because an Environment Manager pass
  // / checks before it repairs, & the cached check would answer the repair with the failure it
  // / was asked to fix. So is an unrestricted answer, because the profile written ahead of a
  // / restriction is the repair's to write & the check made at start never writes it.
  // / It answers only while the copy's path is held as it was when the answer was made, because an install in the same run can change that.
  if ($cachedValid !== NULL && $cachedPathIsHeld === $pathIsHeld && ($cachedMayRepair or !$mayRepair or ($cachedValid && $cachedStatus !== 'unrestricted'))) {
    $PolicyIsValid = $cachedValid;
    $PolicyStatus = $cachedStatus; }
  else {
    $profileName = sandboxApparmorProfileName();
    $profilePath = '/etc/apparmor.d/'.$profileName;
    $profileContents = sandboxApparmorContents();
    list ($includeIsRetired, $retireStatus) = retirePolicyFile('Sandbox AppArmor local', '/etc/apparmor.d/local/bwrap', $mayRepair, TRUE);
    // / The profile that included it is loaded again without what it added, whoever wrote that profile.
    // / systemBwrapProfileFinding() names one no package installed.
    if ($retireStatus === 'retired' && is_file('/etc/apparmor.d/bwrap')) reloadApparmorProfile('/etc/apparmor.d/bwrap');
    if ($restrictionValue === '') {
      $PolicyIsValid = TRUE;
      $PolicyStatus = 'unrestricted'; }
    else if ($RunningInContainer) {
      $PolicyIsValid = TRUE;
      $PolicyStatus = 'host'; }
    else if (!is_file('/etc/apparmor.d/abi/4.0')) {
      $PolicyIsValid = ($restrictionValue !== '1');
      $PolicyStatus = ($PolicyIsValid ? 'unrestricted' : 'unsupported');
      if (!$PolicyIsValid) warningEntry('This kernel restricts unprivileged user namespaces & the AppArmor on this host cannot load a profile that permits one, so the '.$profileName.' profile was not written.'); }
    else if ($restrictionValue !== '1' && (!$mayRepair or !$RunningAsRoot)) {
      $PolicyIsValid = TRUE;
      $PolicyStatus = 'unrestricted'; }
    else if (!$pathIsHeld) {
      $PolicyIsValid = FALSE;
      $PolicyStatus = 'exposed';
      if ($mayRepair && $RunningAsRoot) {
        $profileWasRemoved = removeSandboxPolicy($profilePath);
        warningEntry('The '.$profileName.' profile is not attached to '.privateBwrapPath().', because an account other than root could put a program there. '.$holdNotice.($profileWasRemoved ? '' : ' The one already at '.$profilePath.' could not be taken out.')); } }
    else {
      list ($PolicyIsValid, $PolicyStatus) = verifyPolicyFile('Sandbox AppArmor', $profilePath, $profileContents, $mayRepair);
      if ($RunningAsRoot) {
        list ($profileIsLoaded, $profileLoadStatus) = apparmorProfileIsLoaded($profileName);
        // / A matching file the kernel is not using enforces nothing, & one just written is loaded now.
        if ($mayRepair && $PolicyIsValid && ($PolicyStatus === 'installed' or $PolicyStatus === 'repaired' or !$profileIsLoaded)) {
          $profileWasLoaded = reloadApparmorProfile($profilePath);
          // / One taken out again is reported as an AppArmor that cannot load it, as one that cannot
          // / read the userns rule is, rather than as a file on disk the kernel is not using.
          if (!$profileWasLoaded && $PolicyStatus === 'installed' && (string)@file_get_contents($profilePath) === $profileContents) {
            @unlink($profilePath);
            $PolicyIsValid = ($restrictionValue !== '1');
            $PolicyStatus = ($PolicyIsValid ? 'unrestricted' : 'unsupported');
            warningEntry('The '.$profileName.' profile could not be loaded, so it was removed from '.$profilePath.' again & no boot trips over it.'); }
          list ($profileIsLoaded, $profileLoadStatus) = apparmorProfileIsLoaded($profileName); }
        if ($PolicyIsValid && is_file($profilePath) && !$profileIsLoaded && $profileLoadStatus === 'NOT LOADED') {
          $PolicyIsValid = FALSE;
          $PolicyStatus = 'not loaded'; } } }
    if ($PolicyIsValid && ($retireStatus === 'outdated' or $retireStatus === 'refused')) {
      $PolicyIsValid = FALSE;
      $PolicyStatus = $retireStatus; }
    $cachedValid = $PolicyIsValid;
    $cachedStatus = $PolicyStatus;
    $cachedMayRepair = (bool)$mayRepair;
    $cachedPathIsHeld = $pathIsHeld; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $restrictionValue, $profileName, $profilePath, $profileContents, $profileLoadStatus, $retireStatus, $holdNotice, $profileIsLoaded, $includeIsRetired, $profileWasLoaded, $profileWasRemoved, $pathIsHeld, $mayRepair);
  return array($PolicyIsValid, $PolicyStatus); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read what the sysctl files other than this application's drop in set a setting to.
// / Accepts the setting's name & the path of this application's drop in, in that order.
// / Returns every value those files give it, in the order they are found, which may be none.
// / Every place a boot reads sysctl files from is searched, & each value is returned rather than
// / the one a boot would settle on, because which file a boot reads last is not this
// / application's to predict. A caller asks whether any of them refuses or permits what it needs.
function sysctlValuesElsewhere($settingName, $ownPath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $SettingValues = array();
  $configFolder = $configPath = $configLine = '';
  $configPaths = $lineMatches = array();
  $configPaths = array('/etc/sysctl.conf');
  foreach (array('/etc/sysctl.d', '/run/sysctl.d', '/usr/local/lib/sysctl.d', '/usr/lib/sysctl.d', '/lib/sysctl.d') as $configFolder) $configPaths = array_merge($configPaths, glob($configFolder.'/*.conf') ?: array());
  foreach ($configPaths as $configPath) {
    if ($configPath === $ownPath or !is_file($configPath)) continue;
    // / A boot reads a name written with slashes as it reads one written with dots, & ignores the
    // / leading dash that only asks it to carry on past a setting it cannot apply.
    foreach (explode("\n", (string)@file_get_contents($configPath)) as $configLine) if (preg_match('/^\s*-?\s*([A-Za-z0-9_.\/]+)\s*=\s*(\S+)\s*$/', $configLine, $lineMatches) === 1 && str_replace('/', '.', $lineMatches[1]) === $settingName) $SettingValues[] = $lineMatches[2]; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $configFolder, $configPath, $configLine, $configPaths, $lineMatches, $settingName, $ownPath);
  return $SettingValues; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to keep the sysctl drop in that holds, for the next boot, the kernel settings this
// / application changed.
// / Accepts the settings changed just now, each as name => value, whether lifting the AppArmor
// / restriction is permitted, & whether this may change the drop in, in that order.
// / Returns a status word & a sentence, in that order. The status is 'none' when there is no drop
// / in & none is needed, & otherwise ok, installed, repaired, removed, outdated or FAILED.
// /
// / It holds what was changed, & nothing else.
// / A setting is held when it was changed now, or when this file held it before & the kernel still reads what the file says.
// / A kernel booted since an earlier release wrote this file reads it whether or not the host needed it.
// / So a namespace setting is held from that file only where the next boot would refuse namespaces without it.
// / That is where another sysctl file sets it to 0, or for the clone, whose default is built into the kernel, where no other file permits it.
// / The AppArmor restriction is held only while it reads 0 & lifting it is permitted, so a forbidding host gets it back at its next boot.
// / A restriction that reads 0 is not proved here to need lifting, because proving it means switching it on for every program.
// / One this file lifted stays lifted until it is switched on & -fp proves the profile carries a sandbox, & is let go then.
// / The settings are compared rather than the text, so a file an earlier release wrote that holds exactly what is needed is left alone.
// / A file without this application's header is somebody else's & is never touched.
function maintainNamespaceDropIn($changedSettings, $mayLiftRestriction, $mayChange) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $DropInStatus = 'none';
  $DropInDetail = '';
  $dropInPath = $headerText = $existingContents = $existingLine = $settingName = $settingPath = $currentValue = $heldText = $existingText = $heldList = '';
  $settingPaths = $existingSettings = $heldSettings = $lineMatches = $valuesElsewhere = array();
  $fileExists = $fileIsOurs = $fileIsCurrent = $settingIsHeld = FALSE;
  $dropInPath = '/etc/sysctl.d/99-'.engineApplicationSlug().'-userns.conf';
  $headerText = '# / Written by '.engineApplicationName().'. Holds the kernel settings it changed so a sandbox can build a namespace.'.PHP_EOL;
  $settingPaths = array(
    'kernel.unprivileged_userns_clone' => '/proc/sys/kernel/unprivileged_userns_clone',
    'user.max_user_namespaces' => '/proc/sys/user/max_user_namespaces',
    'kernel.apparmor_restrict_unprivileged_userns' => '/proc/sys/kernel/apparmor_restrict_unprivileged_userns');
  $fileExists = is_file($dropInPath);
  if ($fileExists) {
    $existingContents = (string)@file_get_contents($dropInPath);
    $fileIsOurs = (strpos($existingContents, '# / Written by '.engineApplicationName().'.') === 0);
    $fileIsCurrent = (strpos($existingContents, $headerText) === 0); }
  if ($fileExists && !$fileIsOurs) {
    if ($Verbose) logEntry('The sysctl drop in at '.$dropInPath.' was not written by '.engineApplicationName().', so it is left alone.'); }
  else {
    foreach (explode("\n", $existingContents) as $existingLine) if (preg_match('/^\s*([a-z_.]+)\s*=\s*(\S+)\s*$/', $existingLine, $lineMatches) === 1) $existingSettings[$lineMatches[1]] = $lineMatches[2];
    foreach ($settingPaths as $settingName => $settingPath) {
      if (isset($existingSettings[$settingName])) $existingText = $existingText.$settingName.' = '.$existingSettings[$settingName].PHP_EOL;
      if (!file_exists($settingPath)) continue;
      $currentValue = trim((string)@file_get_contents($settingPath));
      if (isset($changedSettings[$settingName])) $heldSettings[$settingName] = (string)$changedSettings[$settingName];
      else if (isset($existingSettings[$settingName]) && $existingSettings[$settingName] === $currentValue) {
        if ($settingName === 'kernel.apparmor_restrict_unprivileged_userns') $settingIsHeld = ($currentValue === '0' && $mayLiftRestriction);
        else if ($fileIsCurrent) $settingIsHeld = TRUE;
        else {
          $valuesElsewhere = sysctlValuesElsewhere($settingName, $dropInPath);
          $settingIsHeld = (in_array('0', $valuesElsewhere, TRUE) or ($settingName === 'kernel.unprivileged_userns_clone' && count($valuesElsewhere) === 0)); }
        if ($settingIsHeld) $heldSettings[$settingName] = $currentValue; } }
    foreach ($heldSettings as $settingName => $currentValue) {
      $heldText = $heldText.$settingName.' = '.$currentValue.PHP_EOL;
      $heldList = $heldList.($heldList === '' ? '' : ', ').$settingName.' = '.$currentValue; }
    if ($heldText === '' && !$fileExists) $DropInStatus = 'none';
    else if ($fileExists && $heldText === $existingText) {
      $DropInStatus = 'ok';
      $DropInDetail = 'Holds '.$heldList.' for the next boot.'; }
    else if (!$mayChange) {
      $DropInStatus = 'outdated';
      $DropInDetail = $dropInPath.' holds '.($heldText === '' ? 'settings nothing needs held any more' : 'other settings than these need').'. Run -fp as root to correct it.'; }
    else if ($heldText === '') {
      @unlink($dropInPath);
      $DropInStatus = (file_exists($dropInPath) ? 'FAILED' : 'removed');
      $DropInDetail = ($DropInStatus === 'removed' ? 'Removed '.$dropInPath.'. Nothing '.engineApplicationName().' changed needs holding for the next boot.' : $dropInPath.' could not be removed. Remove it by hand.'); }
    else {
      $DropInStatus = ($fileExists ? 'repaired' : 'installed');
      $DropInDetail = 'Wrote '.$dropInPath.', which holds '.$heldList.' for the next boot.';
      $heldText = $headerText.'# / Remove this file & reboot to restore the distribution default.'.PHP_EOL.$heldText;
      if (!is_dir(dirname($dropInPath)) or @file_put_contents($dropInPath, $heldText) !== strlen($heldText)) {
        $DropInStatus = 'FAILED';
        $DropInDetail = $dropInPath.' could not be written, so what was changed lasts until the next boot.'; }
      else @chmod($dropInPath, 0644); }
    // / A drop in that could not be kept is something the administrator should know is happening.
    if ($DropInStatus === 'FAILED') warningEntry('Sysctl drop in: '.$DropInStatus.'. '.$DropInDetail);
    else if ($DropInStatus !== 'none' && $DropInStatus !== 'ok') logEntry('Sysctl drop in: '.$DropInStatus.'. '.$DropInDetail); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $dropInPath, $headerText, $existingContents, $existingLine, $settingName, $settingPath, $currentValue, $heldText, $existingText, $heldList, $settingPaths, $existingSettings, $heldSettings, $lineMatches, $valuesElsewhere, $fileExists, $fileIsOurs, $fileIsCurrent, $settingIsHeld, $changedSettings, $mayLiftRestriction, $mayChange);
  return array($DropInStatus, $DropInDetail); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name a profile on this host that hands the system's bubblewrap a namespace.
// / Accepts nothing. Returns a finding, or an empty array when there is nothing to name.
// / An unconfined profile with the userns rule attached to /usr/bin/bwrap lets any program build a namespace through the system's bubblewrap.
// / That defeats the restriction privateBwrapPath() exists to keep, so such a profile is named & never changed, because it is the administrator's.
// / A profile some package installed is that package's, & is not named.
// / Neither is one this account cannot read, or one on a host where no package manager can say who installed it.
function systemBwrapProfileFinding() {
  // / Set variables.
  global $RunningInContainer, $EnableMemoryProtection;
  $ProfileFinding = array();
  $profilePath = '/etc/apparmor.d/bwrap';
  $profileContents = $queryCommand = $dpkgQueryPath = $rpmPath = '';
  $commandOutput = array();
  $commandExitCode = 1;
  if (!$RunningInContainer && is_file($profilePath)) $profileContents = (string)@file_get_contents($profilePath);
  // / dpkg-query & rpm say which package installed a file, & fail for a file none did. Each is run by
  // / the path it was found at, because root's PATH is not every shell's.
  $dpkgQueryPath = locateDependency('dpkg-query');
  $rpmPath = ($dpkgQueryPath === '') ? locateDependency('rpm') : '';
  if ($dpkgQueryPath !== '') $queryCommand = escapeshellarg($dpkgQueryPath).' -S '.escapeshellarg($profilePath).' 2>&1';
  else if ($rpmPath !== '') $queryCommand = escapeshellarg($rpmPath).' -qf '.escapeshellarg($profilePath).' 2>&1';
  if ($queryCommand !== '' && strpos($profileContents, '/usr/bin/bwrap') !== FALSE && preg_match('/^\s*userns\s*,/m', $profileContents) === 1) {
    // / This runs unsandboxed, as every query of the host's package manager does, because it reads
    // / the package database & takes nothing a user supplied.
    exec($queryCommand, $commandOutput, $commandExitCode);
    if ($commandExitCode !== 0) $ProfileFinding = array('Check' => 'system bubblewrap profile', 'Status' => 'kept', 'Detail' => $profilePath.' belongs to no package & lets any program build a namespace through /usr/bin/bwrap. '.engineApplicationName().' does not use it. Left as it is. Remove it once nothing else needs it.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $ProfileFinding is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $profilePath, $profileContents, $queryCommand, $dpkgQueryPath, $rpmPath, $commandOutput, $commandExitCode);
  return $ProfileFinding; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to check, & optionally correct, the kernel settings a sandbox needs.
// / Accepts a boolean permitting a change.
// / Returns a readiness boolean & an array of findings, in that order.
// /
// / Three separate things block an unprivileged user namespace, & only one is AppArmor.
// / A profile granting userns covers Ubuntu 23.10 & later, & does nothing where kernel.unprivileged_userns_clone or user.max_user_namespaces reads zero.
// /
// / The AppArmor restriction is left in force wherever this application's profile carries a sandbox.
// / That is proved by building one, as the web server account, through the profile, once verifySandboxPolicy() has put it in place.
// / Only a sandbox that cannot be built that way lifts the restriction, & only where the setting mapped as LiftNamespaceRestriction permits it.
// / A host that forbids lifting it is told no sandbox can be built, & is never lifted, at its next boot either.
// / An unattended repair never lifts it, because lifting it changes what every program on the host may do, so only -fp does.
// / A restriction this application lifted is put back as a repair runs, where lifting is now forbidden.
// / A restriction is never lifted to cover a copy of bubblewrap that cannot be used, because --setup --update-depends makes the copy again.
// / Only a copy that is in place & runs & still cannot carry a sandbox, twice over, leaves lifting as the last resort.
// / A sandbox attempt cut short never lifts it, because a time limit or a signal says nothing about the kernel.
// / A lift that did not help is read back once it is put back, & one that could not be put back is FAILED & says so.
// /
// / A host that blocks the namespace makes bubblewrap fall back to its setuid path, which cannot configure loopback inside it.
// / That surfaces as a loopback error rather than a namespace error, which points an operator at networking instead.
// / Whatever is changed is held for the next boot by maintainNamespaceDropIn(), which holds that & nothing else.
function verifySandboxKernel($mayRepair) {
  // / Set variables.
  global $RunningAsRoot, $RunningInContainer, $Verbose, $EngineSettingNames, $EngineRepairIsUnattended, $CurrentUser, $ApacheUser, $EnableMemoryProtection;
  $KernelIsReady = TRUE;
  $KernelFindings = $changedSettings = array();
  $sysctlPath = $sysctlValue = $bwrapPath = $sandboxLauncher = $profileName = $policyStatus = $dropInStatus = $dropInDetail = $settingHint = '';
  $dropInPath = $dropInContents = $restoredDetail = $binaryStatus = $binaryDetail = $carriedDetail = '';
  $mayLiftRestriction = $policyIsValid = $repairIsUnattended = $restrictionWasLifted = $copyIsUnusable = $liftIsOurs = $probeWasInterrupted = $retryWasInterrupted = FALSE;
  $bwrapBinary = FALSE;
  $systemProfileFinding = array();
  // / A container inherits these from its host & cannot change them.
  // / /proc/sys is mounted read only in every unprivileged container, so a repair here can
  // / only ever fail. Reporting FAILED for something that was never this machine's to fix
  // / sends an operator hunting inside the container for a setting that lives outside it.
  // / The values are still READ & reported, because they are exactly what decides whether
  // / the sandbox works, & knowing them is what tells an operator to go and fix the host.
  if ($RunningInContainer) $mayRepair = FALSE;
  $mayLiftRestriction = (bool)applicationSetting('LiftNamespaceRestriction', TRUE);
  $repairIsUnattended = (isset($EngineRepairIsUnattended) && $EngineRepairIsUnattended === TRUE);
  $bwrapPath = locateDependency('bwrap');
  // / Debian gates unprivileged namespaces behind this. Ubuntu ships it reading 1.
  $sysctlPath = '/proc/sys/kernel/unprivileged_userns_clone';
  if (file_exists($sysctlPath)) {
    $sysctlValue = trim((string)@file_get_contents($sysctlPath));
    if ($sysctlValue === '1') $KernelFindings[] = array('Check' => 'unprivileged_userns_clone', 'Status' => 'ok', 'Detail' => 'Unprivileged user namespaces are permitted.');
    else if (!$mayRepair or !$RunningAsRoot) {
      $KernelIsReady = FALSE;
      $KernelFindings[] = array('Check' => 'unprivileged_userns_clone', 'Status' => 'BLOCKED', 'Detail' => 'Reads '.$sysctlValue.'. Bubblewrap cannot build a namespace. '.($RunningInContainer ? 'This is a HOST setting. Correct it on the host, not in this container.' : 'Run -fp as root.')); }
    else {
      @file_put_contents($sysctlPath, '1');
      $sysctlValue = trim((string)@file_get_contents($sysctlPath));
      if ($sysctlValue !== '1') {
        $KernelIsReady = FALSE;
        $KernelFindings[] = array('Check' => 'unprivileged_userns_clone', 'Status' => 'FAILED', 'Detail' => 'Could not be set to 1.'); }
      else {
        $changedSettings['kernel.unprivileged_userns_clone'] = '1';
        $KernelFindings[] = array('Check' => 'unprivileged_userns_clone', 'Status' => 'corrected', 'Detail' => 'Set to 1.'); } } }
  // / A namespace budget of zero refuses every namespace whatever else is permitted.
  $sysctlPath = '/proc/sys/user/max_user_namespaces';
  if (file_exists($sysctlPath)) {
    $sysctlValue = trim((string)@file_get_contents($sysctlPath));
    if ((int)$sysctlValue > 0) $KernelFindings[] = array('Check' => 'max_user_namespaces', 'Status' => 'ok', 'Detail' => $sysctlValue.' namespaces permitted.');
    else if (!$mayRepair or !$RunningAsRoot) {
      $KernelIsReady = FALSE;
      $KernelFindings[] = array('Check' => 'max_user_namespaces', 'Status' => 'BLOCKED', 'Detail' => 'Reads 0. No namespace can be created. '.($RunningInContainer ? 'This is a HOST setting. Correct it on the host, not in this container.' : 'Run -fp as root.')); }
    else {
      @file_put_contents($sysctlPath, '15000');
      if ((int)trim((string)@file_get_contents($sysctlPath)) < 1) {
        $KernelIsReady = FALSE;
        $KernelFindings[] = array('Check' => 'max_user_namespaces', 'Status' => 'FAILED', 'Detail' => 'Could not be raised.'); }
      else {
        $changedSettings['user.max_user_namespaces'] = '15000';
        $KernelFindings[] = array('Check' => 'max_user_namespaces', 'Status' => 'corrected', 'Detail' => 'Raised to 15000.'); } } }
  // / Ubuntu gates the namespace behind AppArmor rather than behind a clone sysctl, so the
  // / two checks above can both read fine while this one is what refuses.
  $sysctlPath = '/proc/sys/kernel/apparmor_restrict_unprivileged_userns';
  if (file_exists($sysctlPath)) {
    $sysctlValue = namespaceRestriction();
    $profileName = sandboxApparmorProfileName();
    $settingHint = (is_array($EngineSettingNames) && isset($EngineSettingNames['LiftNamespaceRestriction'])) ? ' through $'.$EngineSettingNames['LiftNamespaceRestriction'] : '';
    $carriedDetail = ' Every sandbox runs '.privateBwrapPath().' under AppArmor profile '.$profileName.'.';
    $dropInPath = '/etc/sysctl.d/99-'.engineApplicationSlug().'-userns.conf';
    $dropInContents = is_file($dropInPath) ? (string)@file_get_contents($dropInPath) : '';
    $restrictionWasLifted = (strpos($dropInContents, '# / Written by '.engineApplicationName().'.') === 0 && preg_match('/^\s*kernel\.apparmor_restrict_unprivileged_userns\s*=\s*0\s*$/m', $dropInContents) === 1);
    if ($sysctlValue === '0' && !$mayLiftRestriction && $restrictionWasLifted && $mayRepair && $RunningAsRoot && !in_array('0', sysctlValuesElsewhere('kernel.apparmor_restrict_unprivileged_userns', $dropInPath), TRUE)) {
      // / The profile goes in first, so a sandbox can be built through it the moment the
      // / restriction is back on.
      list ($policyIsValid, $policyStatus) = verifySandboxPolicy($mayRepair);
      @file_put_contents($sysctlPath, '1');
      $sysctlValue = namespaceRestriction();
      $restoredDetail = 'Switched back on. '.engineApplicationName().' had lifted it & config.php forbids that now'.$settingHint.'.';
      if ($sysctlValue === '1' && $bwrapPath !== '') list ($bwrapBinary, $sandboxLauncher) = verifySandboxLauncher();
      if ($sysctlValue === '1' && $bwrapPath !== '' && $bwrapBinary === FALSE) $copyIsUnusable = sandboxBinaryIsUnusable();
      if ($sysctlValue !== '1') {
        $KernelIsReady = FALSE;
        $KernelFindings[] = array('Check' => 'apparmor userns restrict', 'Status' => 'FAILED', 'Detail' => engineApplicationName().' had lifted it & config.php forbids that now'.$settingHint.', but it could not be switched back on.'); }
      else if ($bwrapPath === '') $KernelFindings[] = array('Check' => 'apparmor userns restrict', 'Status' => 'corrected', 'Detail' => $restoredDetail);
      else if ($bwrapBinary !== FALSE) $KernelFindings[] = array('Check' => 'apparmor userns restrict', 'Status' => 'corrected', 'Detail' => $restoredDetail.($bwrapBinary === privateBwrapPath() ? $carriedDetail : ' Bubblewrap builds a namespace here without the '.$profileName.' profile.'));
      else {
        $KernelIsReady = FALSE;
        $KernelFindings[] = array('Check' => 'apparmor userns restrict', 'Status' => 'BLOCKED', 'Detail' => $restoredDetail.($copyIsUnusable ? ' No sandbox can be built without '.engineApplicationName().'\'s own bubblewrap, which the '.$profileName.' profile is attached to.' : ' The '.$profileName.' profile could not carry a sandbox, so no sandbox can be built.')); } }
    // / A lift config.php forbids is outdated until -fp or a permitted repair switches the restriction back on, which the branch above does.
    else if ($sysctlValue === '0' && !$mayLiftRestriction && $restrictionWasLifted && !in_array('0', sysctlValuesElsewhere('kernel.apparmor_restrict_unprivileged_userns', $dropInPath), TRUE)) $KernelFindings[] = array('Check' => 'apparmor userns restrict', 'Status' => 'outdated', 'Detail' => 'Not restricting unprivileged namespaces, for any program. '.engineApplicationName().' lifted it & config.php forbids that now'.$settingHint.'. -fp switches it back on.');
    // / A restriction an earlier release lifted is named once this application no longer needs it lifted.
    // / The drop in holds it lifted at every boot, for every program on the host, & by now other programs may rely on it.
    // / Putting it back is the administrator's, so the line says what does, & -fp lets the drop in go once the copy carries a sandbox.
    // / A lift another file holds too is the administrator's own, & the copy is judged once, & only for a lift that is ours.
    else if ($sysctlValue !== '1') {
      $liftIsOurs = ($mayLiftRestriction && $restrictionWasLifted && !in_array('0', sysctlValuesElsewhere('kernel.apparmor_restrict_unprivileged_userns', $dropInPath), TRUE) && sandboxBinaryIsNeeded());
      if ($liftIsOurs) $copyIsUnusable = sandboxBinaryIsUnusable();
      if ($liftIsOurs && !$copyIsUnusable) $KernelFindings[] = array('Check' => 'apparmor userns restrict', 'Status' => 'kept', 'Detail' => 'Not restricting unprivileged namespaces, for any program. An earlier release lifted it & '.$dropInPath.' holds it lifted at every boot. '.engineApplicationName().' no longer needs it. Other programs may. To restrict it, run  sudo sysctl -w kernel.apparmor_restrict_unprivileged_userns=1  & then -fp.');
      else if ($liftIsOurs) $KernelFindings[] = array('Check' => 'apparmor userns restrict', 'Status' => 'ok', 'Detail' => 'Not restricting unprivileged namespaces. An earlier release lifted it. It stays lifted until '.engineApplicationName().'\'s own bubblewrap is in place. --setup --update-depends installs it.');
      else $KernelFindings[] = array('Check' => 'apparmor userns restrict', 'Status' => 'ok', 'Detail' => 'Not restricting unprivileged namespaces.'); }
    else {
      // / The profile is put in place before a sandbox is built through it, so -fp proves what it
      // / has just written rather than what was there before. Its own result is reported on a line
      // / of its own, & what decides this one is whether a sandbox can actually be built.
      list ($policyIsValid, $policyStatus) = verifySandboxPolicy($mayRepair);
      if ($bwrapPath !== '') list ($bwrapBinary, $sandboxLauncher, $probeWasInterrupted) = verifySandboxLauncher();
      if ($bwrapPath !== '' && $bwrapBinary === FALSE) $copyIsUnusable = sandboxBinaryIsUnusable();
      // / Without bubblewrap nothing here builds a sandbox, so nothing could need the restriction lifted.
      if ($bwrapPath === '') $KernelFindings[] = array('Check' => 'apparmor userns restrict', 'Status' => 'ok', 'Detail' => 'Restricted, & left in force. Bubblewrap is not installed.');
      else if ($bwrapBinary !== FALSE && $bwrapBinary === privateBwrapPath()) $KernelFindings[] = array('Check' => 'apparmor userns restrict', 'Status' => 'ok', 'Detail' => 'Restricted, & left in force.'.$carriedDetail);
      else if ($bwrapBinary !== FALSE) $KernelFindings[] = array('Check' => 'apparmor userns restrict', 'Status' => 'ok', 'Detail' => 'Restricted, & left in force. Bubblewrap builds a namespace here without the '.$profileName.' profile.');
      else if ($copyIsUnusable) {
        $KernelIsReady = FALSE;
        $KernelFindings[] = array('Check' => 'apparmor userns restrict', 'Status' => 'BLOCKED', 'Detail' => 'Restricted, & left in force. No sandbox can be built without '.engineApplicationName().'\'s own bubblewrap, which the '.$profileName.' profile is attached to.'); }
      // / An account that can neither run the copy nor hand it to one that can is told so, rather than
      // / that a sandbox cannot be built, because what it found is about itself.
      else if (!sandboxCopyIsJudgedHere() && sandboxBinaryIsNeeded()) $KernelFindings[] = array('Check' => 'apparmor userns restrict', 'Status' => 'n/a', 'Detail' => 'Restricted. Only root & group '.$ApacheUser.' may run '.engineApplicationName().'\'s own bubblewrap. Run -v as root.');
      else if (!$mayLiftRestriction) {
        $KernelIsReady = FALSE;
        $KernelFindings[] = array('Check' => 'apparmor userns restrict', 'Status' => 'BLOCKED', 'Detail' => 'Restricted, & the '.$profileName.' profile could not carry a sandbox. config.php forbids lifting the restriction'.$settingHint.', so no sandbox can be built.'); }
      else if (!$mayRepair or !$RunningAsRoot or $repairIsUnattended) {
        $KernelIsReady = FALSE;
        $KernelFindings[] = array('Check' => 'apparmor userns restrict', 'Status' => 'BLOCKED', 'Detail' => 'Reads 1, & the '.$profileName.' profile could not carry a sandbox. '.($RunningInContainer ? 'This is a HOST setting. Correct it on the host, or run this container with --cap-add SYS_ADMIN.' : ($repairIsUnattended ? 'An unattended repair never lifts it. Run -fp as root.' : 'Run -fp as root.'))); }
      else {
        // / THE FALLBACK. The restriction protects a general purpose desktop from a hostile local
        // / user. Leaving it in force where no sandbox can be built does not make a conversion
        // / host safer, it disables the sandbox that isolates every hostile FILE the host exists to
        // / process, which is the threat that actually arrives here. So it is lifted, where the
        // / application permits, & the log says why the profile could not carry the sandbox.
        // / A lift is proved like any other repair. One that did not let a sandbox be built is put
        // / back, because a host is never left weakened for nothing.
        // / The sandbox is tried once more before anything is lifted, so one that failed under load
        // / is never answered by lifting the restriction for the whole host.
        list ($bwrapBinary, $sandboxLauncher, $retryWasInterrupted) = verifySandboxLauncher();
        if ($bwrapBinary !== FALSE) $KernelFindings[] = array('Check' => 'apparmor userns restrict', 'Status' => 'ok', 'Detail' => 'Restricted, & left in force.'.($bwrapBinary === privateBwrapPath() ? $carriedDetail : ' Bubblewrap builds a namespace here without the '.$profileName.' profile.'));
        else if ($probeWasInterrupted or $retryWasInterrupted) {
          $KernelIsReady = FALSE;
          $KernelFindings[] = array('Check' => 'apparmor userns restrict', 'Status' => 'BLOCKED', 'Detail' => 'Restricted, & left in force. A sandbox attempt was cut short, not refused. Nothing was lifted. Run -fp again. The log says what stopped it.'); }
        else {
          @file_put_contents($sysctlPath, '0');
          $sysctlValue = namespaceRestriction();
          if ($sysctlValue === '0') list ($bwrapBinary, $sandboxLauncher) = verifySandboxLauncher();
          if ($sysctlValue !== '0') {
            $KernelIsReady = FALSE;
            $KernelFindings[] = array('Check' => 'apparmor userns restrict', 'Status' => 'FAILED', 'Detail' => 'The '.$profileName.' profile could not carry a sandbox, & the restriction could not be lifted either.'); }
          else if ($bwrapBinary === FALSE) {
            @file_put_contents($sysctlPath, '1');
            $sysctlValue = namespaceRestriction();
            $KernelIsReady = FALSE;
            if ($sysctlValue === '1') $KernelFindings[] = array('Check' => 'apparmor userns restrict', 'Status' => 'FAILED', 'Detail' => 'Neither the '.$profileName.' profile nor lifting the restriction let a sandbox be built, so the restriction was put back. The log says why.');
            else {
              warningEntry('Lifting kernel.apparmor_restrict_unprivileged_userns did not let a sandbox be built, & it could not be switched back on. It reads '.($sysctlValue === '' ? 'nothing' : $sysctlValue).'.');
              $KernelFindings[] = array('Check' => 'apparmor userns restrict', 'Status' => 'FAILED', 'Detail' => 'Neither the '.$profileName.' profile nor lifting the restriction let a sandbox be built, & the restriction could not be put back. Set kernel.apparmor_restrict_unprivileged_userns to 1 as root.'); } }
          else {
            $changedSettings['kernel.apparmor_restrict_unprivileged_userns'] = '0';
            $KernelFindings[] = array('Check' => 'apparmor userns restrict', 'Status' => 'corrected', 'Detail' => 'Set to 0. The '.$profileName.' profile could not carry a sandbox, so the restriction is lifted for this host. The log says why.'); } } } }
    // / This application's own copy of bubblewrap has a line of its own, which only reports.
    list ($binaryStatus, $binaryDetail) = verifySandboxBinary($bwrapBinary);
    if ($binaryStatus !== 'none') $KernelFindings[] = array('Check' => 'bubblewrap copy', 'Status' => $binaryStatus, 'Detail' => $binaryDetail);
    // / So does a profile that hands the system's bubblewrap a namespace, which only reports too.
    $systemProfileFinding = systemBwrapProfileFinding();
    if (!empty($systemProfileFinding)) $KernelFindings[] = $systemProfileFinding; }
  // / A setuid bubblewrap is a symptom rather than a cause. It is how a distribution shipped
  // / a working sandbox on a host that forbids the namespace, & it is the configuration that
  // / produces the loopback error. Report it so the cause is not mistaken for networking.
  if ($bwrapPath !== '' && file_exists($bwrapPath) && (fileperms($bwrapPath) & 0x800)) $KernelFindings[] = array('Check' => 'bubblewrap mode', 'Status' => 'setuid', 'Detail' => 'Running setuid, which cannot configure loopback in a new network namespace. Permitting unprivileged namespaces lets it stop.');
  // / Whatever was changed is held for the next boot, & whatever no longer needs holding is let go.
  list ($dropInStatus, $dropInDetail) = maintainNamespaceDropIn($changedSettings, $mayLiftRestriction, ($mayRepair && $RunningAsRoot));
  if ($dropInStatus !== 'none') $KernelFindings[] = array('Check' => 'sysctl drop in', 'Status' => $dropInStatus, 'Detail' => $dropInDetail);
  if ($Verbose) logEntry('Sandbox kernel check completed across '.count($KernelFindings).' setting(s). Ready: '.($KernelIsReady ? 'YES' : 'NO').'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $changedSettings, $sysctlPath, $sysctlValue, $bwrapPath, $sandboxLauncher, $profileName, $policyStatus, $dropInStatus, $dropInDetail, $settingHint, $dropInPath, $dropInContents, $restoredDetail, $binaryStatus, $binaryDetail, $carriedDetail, $mayLiftRestriction, $policyIsValid, $repairIsUnattended, $restrictionWasLifted, $copyIsUnusable, $liftIsOurs, $probeWasInterrupted, $retryWasInterrupted, $bwrapBinary, $systemProfileFinding, $mayRepair);
  return array($KernelIsReady, $KernelFindings); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to print an environment report.
// / Accepts the findings array & a boolean limiting the report to problems, in that order.
// / Returns the number of checks that were not ok.
// / -fp repairs the policies & then checks again to prove the repairs took, & both passes produce the same rows.
// / So the confirmation pass reports only what is still a problem, & one line when nothing is.
// / A check still wrong after a repair says the repair did not work, which is the point of the second pass.
function showEnvironmentFindings($environmentFindings, $onlyProblems) {
  // / Set variables.
  global $Lol, $EnableMemoryProtection;
  $ProblemsFound = 0;
  $finding = array();
  $findingIsBenign = FALSE;
  foreach ($environmentFindings as $finding) {
    // / policyDisplayStatus has already reduced every benign policy state to ok, so the
    // / column can be trusted. The words left here are the ones that changed something or
    // / went wrong, & only the second kind is a problem.
    $findingIsBenign = findingIsBenign($finding['Status']);
    if (!$findingIsBenign) $ProblemsFound++;
    if (!$onlyProblems or !$findingIsBenign) print('  '.str_pad($finding['Check'], 28).str_pad($finding['Status'], 14).$finding['Detail'].$Lol); }
  if ($onlyProblems && $ProblemsFound === 0) print('  '.str_pad('All checks', 28).str_pad('ok', 14).count($environmentFindings).' check(s) passed.'.$Lol);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $finding, $findingIsBenign, $onlyProblems, $environmentFindings);
  return $ProblemsFound; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether one finding's status is a problem.
// / Accepts the status word. Returns TRUE when nothing about it needs attention.
// / A status that changed something, such as repaired, is not a problem, because the change
// / is what was asked for. Only a status that went wrong is one.
// / showEnvironmentFindings & the Environment Manager both decide with this, so the report & the count a pass acts on cannot disagree.
function findingIsBenign($findingStatus) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FindingIsBenign = FALSE;
  $FindingIsBenign = in_array((string)$findingStatus, array('ok', 'installed', 'repaired', 'corrected', 'removed', 'retired', 'kept', 'disabled'), TRUE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $findingStatus);
  return $FindingIsBenign; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report what kind of host this is, as an environment record.
// / Accepts nothing. Returns the record.
// /
// / One call rather than four, because these facts are asked for together & an application
// / that wants one of them usually wants the rest in the same breath.
// / Nothing here is expensive & nothing here changes while a process is running.
if (!function_exists('describeEnvironment')) {
  function describeEnvironment() {
    // / Set variables.
    global $EnableMemoryProtection;
    $EnvironmentRecord = array();
    $architectureName = $machineString = '';
    list ($architectureName, $machineString) = detectHostArchitecture();
    $EnvironmentRecord = array(
      'Architecture'   => $architectureName,
      'Machine'        => $machineString,
      'Interface'      => PHP_SAPI,
      'IsCommandLine'  => (PHP_SAPI === 'cli'),
      'IsRoot'         => (function_exists('posix_geteuid') && posix_geteuid() === 0),
      'ProcessId'      => getmypid(),
      'PhpVersion'     => PHP_VERSION,
      'CgroupsUsable'  => cgroupDelegationIsAvailable());
    // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
    purgeSensitiveMemory($EnableMemoryProtection, $architectureName, $machineString);
    return $EnvironmentRecord; } }
// / -----------------------------------------------------------------------------------
?>
