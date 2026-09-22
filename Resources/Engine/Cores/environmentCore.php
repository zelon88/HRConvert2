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
// / v3.9.3.
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
// / Four more, moved from the application at v3.9.3.
// / apparmorProfileIsLoaded asks the kernel whether a profile is active.
// / confirmDestructiveAction asks a human to type a word before something irreversible.
// / generateInstallSecret makes an install secret.
// /
// / confirmDestructiveAction is the interesting one. It PRINTS & READS, which sounds
// / like an application concern, & is not: every application of this shape has an
// / irreversible command & needs the same pause before it. What differs is HOW an
// / operator is reached, & that is already behind $EngineOperatorPrompt.
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report whether an AppArmor profile is loaded into the kernel.
// / Accepts the profile name as it is declared inside the profile file.
// / Returns a loaded boolean & a status word, in that order.
// / A profile on disk is not a profile in force.
// / Writing one & running apparmor_parser only at the moment it is written means a load
// / that failed, or a host that rebooted before AppArmor read it, leaves a file that
// / matches perfectly & is enforcing nothing. The check reported ok & the sandbox stayed
// / broken, which is the worst combination a diagnostic can produce.
// / The loaded set is read from securityfs, which is what the kernel is actually using.
function apparmorProfileIsLoaded($profileName) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ProfileIsLoaded = FALSE;
  $ProfileStatus = 'unknown';
  $profilesPath = '/sys/kernel/security/apparmor/profiles';
  $loadedProfiles = '';
  if (!file_exists($profilesPath)) $ProfileStatus = 'apparmor not active';
  else if (!is_readable($profilesPath)) $ProfileStatus = 'not readable by this account';
  else {
    $loadedProfiles = (string)@file_get_contents($profilesPath);
    if (strpos($loadedProfiles, (string)$profileName) !== FALSE) {
      $ProfileIsLoaded = TRUE;
      $ProfileStatus = 'loaded'; }
    else $ProfileStatus = 'NOT LOADED'; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $profilesPath, $loadedProfiles, $profileName);
  return array($ProfileIsLoaded, $ProfileStatus); }
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
// / Four more environment functions, moved from the application at v3.9.3.
// / reloadApparmorProfile reloads a kernel profile.
// / closeHRC2Connection ends a request cleanly.
// / validateInstallation checks an installation is intact.
// / None of them asks what the installation is FOR.
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to load an AppArmor profile that has just been written.
// / Accepts the absolute path of the profile.
// / Returns TRUE when the parser accepted it.
// / A profile that is written but never loaded changes nothing until the next reboot, which
// / makes a repair look like it failed.
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
// / A function to close the web server connection.
function closeHRC2Connection() {
  ignore_user_abort(TRUE);
  if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
  else {
    if (ob_get_level() > 0) ob_end_flush();
    flush(); } }
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
  $validateCommand = 'php '.escapeshellarg($installPath.'/convertCore.php').' -v 2>&1';
  exec($validateCommand, $validateOutput, $validateExitCode);
  // / The exit code proves it ran. The marker proves it ran far enough to report.
  if ($validateExitCode === 0 && is_array($validateOutput)) {
    if (strpos(implode(' ', $validateOutput), 'Core version') !== FALSE) $InstallationIsValid = TRUE; }
  if ($Verbose) logEntry('Installation validation: '.($InstallationIsValid ? 'PASSED' : 'FAILED').', exit code '.$validateExitCode.'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $validateCommand, $validateOutput, $validateExitCode, $installPath);
  return $InstallationIsValid; }
// / -----------------------------------------------------------------------------------
// / A function to generate the per-install secret used to derive session identifiers.
// / 32 bytes gives 256 bits of entropy & returns as a 64 hexadecimal character string.
function generateInstallSecret() {
  // / Set variables.
  global $EnableMemoryProtection;
  $InstallSecret = FALSE;
  $InstallSecretCheck = TRUE;
  // / random_bytes() throws rather than returning a poor result when entropy is unavailable.
  // / Fail closed. A predictable secret is worse than no installation at all.
  try { $InstallSecret = bin2hex(random_bytes(32)); }
  catch (Throwable $error) { $InstallSecretCheck = FALSE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $error);
  return array($InstallSecret, $InstallSecretCheck); }
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
