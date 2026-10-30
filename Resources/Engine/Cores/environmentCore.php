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
// / v3.9.6.
// / The environment the Engine provides to every application built on it.
// /
// / THE ENGINE OWNS THESE. An application uses them & does not define them. That is the
// / difference between an engine an application lends functions to & an engine an
// / application is built on.
// /
// / EVERY DEFINITION IS GUARDED BY function_exists & that is not defensive habit.
// / HRConvert2 defines several of these itself, & has to, because its boot sequence reaches
// / them BEFORE the Engine has loaded. locateDependency is needed to find php. sanitize is
// / needed to read an argument. readComponentVersion is what verifies the Engine itself &
// / therefore cannot live inside it.
// / So an application that already has one keeps it & the Engine defines the rest. A new
// / application defines none of them & gets all of them. Neither arrangement redeclares a
// / function, which is fatal in php & would take the whole application down at load.
// /
// / As HRConvert2 moves its boot sequence behind the Engine these guards stop mattering
// / one at a time. Removing them before that is finished turns a working installation into
// / a fatal error at line one.
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
// / THIS IS THE BAN HAMMER & it removes rather than escapes.
// / Escaping assumes you know every context the value will reach. Removal does not, & a
// / character that is gone cannot be interpreted by a shell, a path resolver or a parser
// / that nobody remembered was in the chain.
// /
// / IT CANNOT BE USED ON A URL. The characters it must remove from a filename are the
// / characters a URL is built from, so a URL passed through this comes back as a filename.
// / An address is rebuilt from parsed components instead. See normalizeStreamBaseURL.
// /
// / The second return value is whether the input was ALREADY clean. A caller that only
// / wants a safe value can ignore it. A caller that needs to know somebody sent something
// / they should not have must read it, because a cleaned value looks innocent afterwards.
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
// / Two filesystem utilities. Reading a size, & reading the modification time OF A SYMLINK
// / ITSELF rather than of the file it points at.
// /
// / THAT DISTINCTION IS THE WHOLE REASON symlinkmtime EXISTS. filemtime FOLLOWS a link &
// / reports the target's time, & there is no argument that stops it. lstat does not follow,
// / so it is the only way to ask when the LINK was last touched.
// / It matters the moment storage is shared. If a session holds a link to a stored file,
// / the target's time says when that file was created, which may be months ago & may be
// / shared by twenty sessions. The link's time says when THIS session last touched it,
// / which is what an expiry sweep actually needs to know.
// / Getting this backwards means expiring a live session because the file it points at is
// / old, or keeping a dead one alive because somebody else refreshed the target.
// /
// / NEITHER IS CALLED BY HRConvert2 TODAY & both are kept on purpose.
// / An engine is a component library & the test for a component is whether it is USEFUL,
// / not whether the first application built on it happens to call it. A file operation
// / pipeline that hands back a listing wants a size.
// / They were nearly deleted for being uncalled, which is the wrong test in this repo.
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
// / Four environment utilities, moved from the application at v3.9.3.
// / policyDisplayStatus turns a policy state into a word for a report. escapeApache
// / ConfigPath quotes a path for a server configuration. apacheModuleIsListed asks
// / whether a module is loaded. verifyEncryption asks whether this build can encrypt.
// / None of them knows what the application does with the answer.
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to turn an internal policy status into the word an operator should read.
// / Accepts the internal status word.
// / Returns 'ok' when nothing needs doing, or the status unchanged when something does.
// /
// / A STATUS COLUMN IS SCANNED, NOT READ. IT MUST ONLY SAY ok WHEN NOTHING IS WRONG.
// / unrestricted, unconfined & distribution all describe a host that is already correct,
// / & all three read like something is missing. An administrator skimming a wall of output
// / for problems should not have to know which of the unusual looking words are the good
// / ones. The word becomes ok & the sentence beside it explains why.
// /
// / A state that changed something keeps its own word.
// / installed, repaired & corrected are not problems, but they did alter this machine, &
// / an operator is entitled to see that at a glance rather than have it flattened into ok.
function policyDisplayStatus($policyStatus) {
  // / Set variables.
  global $EnableMemoryProtection;
  $DisplayStatus = (string)$policyStatus;
  $benignStatuses = array('ok', 'unchanged', 'unrestricted', 'unconfined', 'distribution', 'absent', 'n/a');
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
// / Moved from the application at v3.9.3.
// / confirmDestructiveAction asks a human to type a word before something irreversible.
// / apparmorProfileIsLoaded & generateInstallSecret were moved here too & went back at v3.9.5.
// / Both are reached before the Engine loads, so the application has to own them.
// /
// / confirmDestructiveAction is the interesting one. It PRINTS & READS, which sounds
// / like an application concern, & is not: every application of this shape has an
// / irreversible command & needs the same pause before it. What differs is HOW an
// / operator is reached, & that is already behind $EngineOperatorPrompt.
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
// / Moved from the application at v3.9.3.
// / validateInstallation checks an installation is intact, & does not ask what it is for.
// / reloadApparmorProfile & closeHRC2Connection were moved here too & went back at v3.9.5.
// / Both are reached before the Engine loads, so the application has to own them.
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to prove that an installation actually runs.
// / Called after the swap. The new installation is asked to report its own version as a
// / separate process. A core that cannot parse, cannot load its config, & cannot reach
// / the command line branch will not answer, & that is the condition worth catching.
// / A file count or a directory listing proves nothing about whether the code executes.
function validateInstallation($installPath) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $InstallationIsValid = FALSE;
  $validateCommand = '';
  $validateOutput = array();
  $validateExitCode = 1;
  $validateCommand = 'php '.escapeshellarg($installPath.'/'.engineApplicationEntryPoint()).' -v 2>&1';
  exec($validateCommand, $validateOutput, $validateExitCode);
  // / The exit code proves it ran. The marker proves it ran far enough to report.
  if ($validateExitCode === 0 && is_array($validateOutput)) {
    if (strpos(implode(' ', $validateOutput), 'Core version') !== FALSE) $InstallationIsValid = TRUE; }
  if ($Verbose) logEntry('Installation validation: '.($InstallationIsValid ? 'PASSED' : 'FAILED').', exit code '.$validateExitCode.'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $validateCommand, $validateOutput, $validateExitCode, $installPath);
  return $InstallationIsValid; }
// / -----------------------------------------------------------------------------------
// / Five more, moved from the application at v3.9.6.
// / extractCliOption reads one option out of an argument list. verifyApacheModule asks
// / whether a module is loaded & can enable it. fetchOwnResponseHeaders reads the headers
// / this server actually sends. describePolicyStatus turns a policy state into words.
// / toolMinimum reads a tool's minimum version from the manifest, its only owner.
// / None of them knows what this application is for, & none calls back into it.
// / reportUnrecognizedArgument was considered & stays, because it calls showHelpInfo(),
// / which prints this application's own help.
// / -----------------------------------------------------------------------------------

// / A function to read the value of a --option=value argument.
// / Accepts the argument list & the option name, in that order.
// / Returns the value, or an empty string when the option was not supplied.
// / The value is returned exactly as typed. A path is case sensitive & a section name may
// / carry spaces, so nothing is lowercased or stripped here.
function extractCliOption($cliArguments, $optionName) {
  // / Set variables.
  global $EnableMemoryProtection;
  $OptionValue = '';
  $argumentText = '';
  $optionPrefix = trim((string)$optionName).'=';
  foreach ($cliArguments as $argumentText) {
    if (strpos((string)$argumentText, $optionPrefix) === 0) $OptionValue = substr((string)$argumentText, strlen($optionPrefix)); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $argumentText, $optionPrefix, $cliArguments, $optionName);
  return $OptionValue; }
// / -----------------------------------------------------------------------------------

// / A function to confirm an Apache module is loaded, & to enable it when it is not.
// / Accepts the module name as Apache reports it & a boolean permitting repair, in that order.
// / Returns a loaded boolean & the status word, in that order.
// /
// / A <Directory> BLOCK THAT NEEDS A MODULE IS WORTH NOTHING WITHOUT IT.
// / The DATA rules are Header directives wrapped in <IfModule mod_headers.c>. That guard is
// / there so the configuration still PARSES on a server without the module, which is what
// / stops a missing module from turning into a 500. It also means that on such a server the
// / rules are skipped entirely, in silence, while the configuration reports itself written,
// / tested, reloaded & correct. Measured on a host with headers disabled; four green lines &
// / a DATA tree serving executable SVGs.
// /
// / So the module is not assumed. It is checked, & where it can be enabled it is enabled,
// / because -fp is already root & already editing this server's configuration. Turning on
// / the module that configuration depends on is the same act, not a larger one.
// /
// / apachectl -M IS THE ONLY HONEST ANSWER TO WHETHER A MODULE IS LOADED.
// / A file existing under mods-enabled says an administrator intended it, not that the
// / running server has it; the symlink may postdate the last reload, or point at nothing.
// / Asking the binary reports what is actually in the server.
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

// / A function to explain a policy status in a sentence.
// / Accepts the policy name & the status word, in that order.
// / Returns a sentence saying whether anything needs doing.
// / A bare status word is not a report.
// / unrestricted & unconfined & distribution all mean nothing needs doing, & all three read
// / like something is missing. An operator should never have to ask which of the words on
// / this screen are the bad ones.
function describePolicyStatus($policyName, $policyStatus) {
  // / Set variables.
  global $EnableMemoryProtection;
  $StatusDescription = '';
  if ($policyStatus === 'ok') $StatusDescription = 'Matches this release. Nothing to do.';
  else if ($policyStatus === 'installed') $StatusDescription = 'Written for the first time.';
  else if ($policyStatus === 'repaired') $StatusDescription = 'Rewritten to match this release.';
  else if ($policyStatus === 'unchanged') $StatusDescription = 'Already correct. Nothing to do.';
  else if ($policyStatus === 'unrestricted') $StatusDescription = 'This kernel does not restrict unprivileged namespaces, so no profile is needed. Nothing to do.';
  else if ($policyStatus === 'unconfined') $StatusDescription = 'Nothing on this host confines OpenSCAD, so no override is needed. The bubblewrap sandbox is what isolates it. Nothing to do.';
  else if ($policyStatus === 'distribution') $StatusDescription = 'The distribution profile governs this binary & has been extended through its own include. Nothing to do.';
  else if ($policyStatus === 'absent') $StatusDescription = 'Not installed on this host, so there is nothing to write a policy for.';
  else if ($policyStatus === 'foreign') $StatusDescription = 'Present but not written by this application. Review it, or re-run -fp to replace it.';
  else if ($policyStatus === 'outdated') $StatusDescription = 'Written by an older release. Re-run -fp as root to repair it.';
  else if ($policyStatus === 'not loaded') $StatusDescription = 'ON DISK BUT NOT IN FORCE. The kernel is not using it. Reload AppArmor, or reboot.';
  else if ($policyStatus === 'refused') $StatusDescription = 'REFUSED. The existing file could not be backed up, so it was left alone.';
  else if ($policyStatus === 'removed, distribution profile kept') $StatusDescription = 'Our competing profile was removed. The distribution profile governs this binary.';
  else $StatusDescription = 'Reported '.$policyStatus.'.';
  // / The DATA policy is the one whose presence proves nothing.
  // / Every other policy here is read by the kernel or by a converter the moment it exists.
  // / This one is a .htaccess, which Apache reads only where AllowOverride is enabled &
  // / permits it, so 'Matches this release. Nothing to do.' would be a lie on
  // / exactly the installations that are exposed. The exposure check further down is the
  // / answer & this line points at it rather than pretending to be it.
  if ($policyStatus === 'disabled') $StatusDescription = 'Not maintained, because --Maintain HTAccess-- is FALSE in config.php. The server configuration is carrying these rules alone.';
  else if ($policyName === 'DATA Directory' && ($policyStatus === 'ok' or $policyStatus === 'installed' or $policyStatus === 'repaired')) $StatusDescription = $StatusDescription.' This file is INERT unless AllowOverride is enabled. See the exposure check below.';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $policyName, $policyStatus);
  return $StatusDescription; }
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
    $manifestPath = $InstLoc.$DirSep.'Resources'.$DirSep.'Engine'.$DirSep.'Contract'.$DirSep.'depends.php';
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
// / THIS IS THE APPLICATION'S IDENTITY, NOT ITS BRANDING. $EngineApplicationName is set in
// / Contract/app-engine-config.php & does not change. $ApplicationName is a config setting an
// / operator may rename, & it is shown in the interface. The Engine uses the identity, so
// / nothing it writes to disk moves when an operator renames their installation.
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
// / Three more, moved from the application at v3.9.6 once their names were no longer fixed.
// / resolveUpdateTarget works out where a self update comes from. verifyPhpConfiguration
// / writes & checks a PHP settings file. dataProtectionContents builds a data protection file.
// / Each once wrote the word HRConvert2 into what it produced. Each now asks for the name, so
// / HRConvert2 gets exactly what it always got & another application gets its own.
// / verifySandboxKernel was considered & stays, because it calls apparmorProfileIsLoaded(),
// / which runs before the Engine loads & so has to live in the application.
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

// / A function to write the PHP settings HRConvert2 cannot run without.
// / Accepts a boolean permitting a write.
// / Returns a success boolean & the number of configurations written, in that order.
// /
// / A DROP IN IS WRITTEN. php.ini ITSELF IS NEVER EDITED.
// / Every PHP build reads conf.d after php.ini, so a drop in overrides the distribution
// / value without touching a file the package manager owns. A php.ini edited in place is
// / reverted by the next PHP upgrade & leaves an administrator wondering why uploads
// / started failing months later.
// / EVERY SAPI IS WRITTEN. The web server & the command line read different directories,
// / & a limit set for one while the other keeps the default is how a conversion that works
// / from a terminal refuses from a browser.
// / max_execution_time is derived from --Stream Duration Timeout--, because HRConvert2
// / holds a PHP worker for the whole of a backgrounded stream. A limit below that leaves
// / FFMPEG running with nothing left to terminate it.
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
    .'; / HRCONVERT2-POLICY-MARKER'.PHP_EOL
    .'; / Re-run  sudo php '.engineApplicationEntryPoint().' -fp  to rewrite this file.'.PHP_EOL
    .'; / php.ini itself is never touched, so a PHP upgrade cannot revert these.'.PHP_EOL
    .PHP_EOL
    .'; / A PHP worker is held for the whole of a backgrounded stream conversion.'.PHP_EOL
    .'max_execution_time = '.$executionTime.PHP_EOL
    .'max_input_time = 90'.PHP_EOL
    .'memory_limit = 512M'.PHP_EOL
    .PHP_EOL
    .'; / An upload larger than these is refused by PHP before '.engineApplicationName().' ever sees it.'.PHP_EOL
    .'post_max_size = 5000M'.PHP_EOL
    .'upload_max_filesize = 5000M'.PHP_EOL
    .'max_file_uploads = 100'.PHP_EOL
    .PHP_EOL
    .'; / An error printed into a response corrupts the filename list the interface parses.'.PHP_EOL
    .'display_errors = Off'.PHP_EOL
    .'log_errors = On'.PHP_EOL
    .PHP_EOL
    .'zlib.output_compression = On'.PHP_EOL;
  // / Every SAPI this host might be running. A packaged PHP splits them by version & by
  // / server, an official container image uses one shared directory.
  $globResults = glob('/etc/php/*/*/conf.d');
  if (is_array($globResults)) $candidateDirectories = $globResults;
  if (is_dir('/usr/local/etc/php/conf.d')) $candidateDirectories[] = '/usr/local/etc/php/conf.d';
  if (empty($candidateDirectories)) {
    $ConfigurationIsValid = FALSE;
    warningEntry('No PHP configuration directory was found, so the settings '.engineApplicationName().' needs could not be written. Set them in php.ini by hand.'); }
  else {
    foreach ($candidateDirectories as $candidateDir) {
      $dropInPath = $candidateDir.$DirSep.'99-'.engineApplicationSlug().'.ini';
      $existingContents = file_exists($dropInPath) ? (string)@file_get_contents($dropInPath) : '';
      if ($existingContents === $dropInContents) print('  '.str_pad('Unchanged', 12).$dropInPath.$Lol);
      else if (!$mayRepair or !$RunningAsRoot) {
        $ConfigurationIsValid = FALSE;
        print('  '.str_pad('Missing', 12).$dropInPath.$Lol); }
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

// / A function to supply the contents of the DATA directory protection file.
// / Accepts no arguments.
// / Returns the complete file contents, carrying the marker that identifies it as ours.
// /
// / $ConvertTempDir IS INSIDE THE WEB ROOT & HOLDS USER SUPPLIED BYTES.
// / $ConvertTemp is $InstLoc/DATA & every file a user uploads or produces is copied there
// / by verifyFile() so a browser can fetch it by URL. That makes the web server hand out
// / attacker chosen content from this application's own origin, & for any format a browser
// / treats as ACTIVE that content is code rather than data.
// / An SVG is the clear case. It is a document, not a picture; it is served as
// / image/svg+xml & a <script> element inside it executes.
// /
// / Content-Disposition is what closes this. The file is handed over as a download & is
// / never rendered as a document, so nothing inside it runs. The CSP is a second &
// / independent stop for the same thing, kept because one directive is one point of
// / failure & these two fail in different ways.
// /
// / X-Content-Type-Options DOES NOT CLOSE THIS & IS NOT WHY THIS WORKS.
// / An SVG here is served with a correct image/svg+xml type, so there is nothing to sniff
// / & nothing for nosniff to prevent. It is set because it does close the neighbouring case
// / of a file whose type is ambiguous. It must never be reported as the control that
// / stopped the script, because it did not.
function dataProtectionContents() {
  // / Set variables.
  global $EnableMemoryProtection;
  $ProtectionContents = '';
  $ProtectionContents = '# HRCONVERT2-POLICY-MARKER'."\n"
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
    .'# configuration, & verify with the -fp argument rather than assuming.'."\n"
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
    .'  # file here that is meant to render. It cannot be user supplied, because index.html'."\n"
    .'  # is a literal entry in $DangerousFiles & the core refuses to stage a file by that'."\n"
    .'  # name, so excluding it opens nothing.'."\n"
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
// / A directory a web server can reach & that has no index LISTS ITSELF on most default
// / configurations. Every file in it, to anybody who asks for the directory rather than for
// / a file in it.
// /
// / THE PAGE IS GENERATED RATHER THAN COPIED. Copying assumes a source file exists at a
// / known path, & the one place this application did that copied from the working directory,
// / which is whatever the web server happened to be in. A generated page cannot be missing.
// /
// / AN EXISTING index.html IS LEFT ALONE. An application may have a real one, & replacing a
// / page somebody wrote with a blank one is worse than the listing this prevents.
// / It writes index.html only. A server configured to prefer index.php is a server that
// / needs its own directive, & guessing at every name a server might index would be
// / guessing rather than protecting.
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
// / MOVED FROM THE APPLICATION AT v3.9.3. Printing an environment report is not something
// / one application does differently from another, & the findings it prints are already
// / assembled by the Engine & by whatever provider the application named.
// / A function to print an environment report.
// / Accepts the findings array & a boolean limiting the report to problems, in that order.
// / Returns the number of checks that were not ok.
// /
// / The same check reported twice in one run is noise, not thoroughness.
// / The -fp argument repairs the policies & then revalidates to prove the repairs took. Both
// / steps produce the same rows, so an operator was reading the AppArmor, ImageMagick &
// / OpenSCAD lines twice in a single run & the kernel line twice on top of that. Printing a
// / clean check a second time tells nobody anything; printing a check that is STILL wrong
// / after a repair tells them the repair did not work, which is the entire point of the
// / second pass. So the confirmation pass reports only what is still a problem & says so in
// / one line when there is nothing left to report.
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
    $findingIsBenign = in_array($finding['Status'], array('ok', 'installed', 'repaired', 'corrected', 'removed', 'disabled'), TRUE);
    if (!$findingIsBenign) $ProblemsFound++;
    if (!$onlyProblems or !$findingIsBenign) print('  '.str_pad($finding['Check'], 28).str_pad($finding['Status'], 14).$finding['Detail'].$Lol); }
  if ($onlyProblems && $ProblemsFound === 0) print('  '.str_pad('All checks', 28).str_pad('ok', 14).count($environmentFindings).' check(s) passed. Nothing above needs attention.'.$Lol);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $finding, $findingIsBenign, $onlyProblems, $environmentFindings);
  return $ProblemsFound; }
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
