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
