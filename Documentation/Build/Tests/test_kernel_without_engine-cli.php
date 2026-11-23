<?php
// HRProprietary Engine v4.3.1. The unit suite of what the kernel says where the Engine cannot run.
//
//   sudo bash Documentation/Build/Tests/contained.sh php Documentation/Build/Tests/test_kernel_without_engine-cli.php
// Run it from the root of the application under test. A tree named after it is tested instead, & must be that root.
//
// showKernelVersionReport() & refuseCommandWithoutEngine() are lifted out of the tree's own Resources/Kernel/kernel.php, exactly as
// the tree has it, & handed folders beneath the scratch folder that hold an Engine, none, or one of another version.
// From v4.3.1 an Engine of the version required can load & not start, as one does whose Environment Core was refused. The kernel says
// so, & a command refused in a folder beside the installation names the installation whether this folder holds an Engine or not.
//
// It removes nothing. Each part makes a folder of its own beneath the scratch folder & leaves it there.

if (PHP_SAPI !== 'cli') { if (!headers_sent()) http_response_code(404); die(); }

if (!is_file('Documentation/Build/Tests/testlib-cli.php')) { fwrite(STDERR, 'Run this from the root of the application it tests. See Documentation/Build/Tests/README.txt.'.PHP_EOL); exit(64); }
require 'Documentation/Build/Tests/testlib-cli.php';

$tree = testTree(isset($argv[1]) ? $argv[1] : (string)getcwd());
requireContainment();

// What the kernel would have set before either function ran.
$EnableMemoryProtection = TRUE;
$Verbose = FALSE;
$LogFile = '';
$Lol = PHP_EOL;
$ApplicationName = 'HRConvert2';
$EngineApplicationVersion = '4.3.1';
$KernelVersion = 'v4.3.1';
$RequiredKernelVersion = '4.3.1';
$RequiredEngineVersion = '4.3.1';
$ConfigVersion = 'v4.2.8';
$RequiredConfigVersion = '4.2.8';
$InstLoc = $ApplicationLoc = '';
$TestLog = array();

liftFunctions($tree.'/Resources/Kernel/kernel.php', array('getChildFunction', 'purgeSensitiveMemory', 'readComponentVersion', 'showKernelVersionReport', 'refuseCommandWithoutEngine'), array());

// Runs a function that prints, & gives back what it returned & what it printed, in that order.
function printed($functionName, $arguments) {
  ob_start();
  $returned = call_user_func_array($functionName, $arguments);
  $said = (string)ob_get_clean();
  return array($returned, $said); }

// Makes a folder that holds an entry point & an Engine of the version named, or no Engine where none is named.
function makeInstallation($folderName, $engineVersion) {
  makeFolder(scratchPath($folderName.'/Resources/Engine'), 0755);
  writeScratchFile(scratchPath($folderName.'/convertCore.php'), "<?php\n", 0644);
  if ($engineVersion !== '') writeScratchFile(scratchPath($folderName.'/Resources/Engine/engine.php'), "<?php\n\$EngineVersion = '".$engineVersion."';\n", 0644);
  return scratchPath($folderName); }


// Part 1. -v where the Engine is of the version required, & did not start.
$InstLoc = makeInstallation('p01', 'v4.3.1');
list ($reportPrinted, $reportSaid) = printed('showKernelVersionReport', array());
check('the report is printed', $reportPrinted === TRUE);
check('the Engine line says it did not start', strpos($reportSaid, '  Engine     4.3.1, requires 4.3.1, & did not start'.PHP_EOL) !== FALSE, $reportSaid);
check('the report says the Engine loaded & did not start, & where to look', strpos($reportSaid, 'The Engine loaded & did not start, so nothing else can be reported. Its log says why.') !== FALSE);
check('it does not say the Engine could not be loaded', strpos($reportSaid, 'could not be loaded') === FALSE);


// Part 2. -v where the Engine is of another version, or is missing. Neither loaded, so neither is said to have not started.
$InstLoc = makeInstallation('p02', 'v4.3.0');
list ($reportPrinted, $reportSaid) = printed('showKernelVersionReport', array());
check('an Engine of another version is named, & not said to have not started', strpos($reportSaid, '  Engine     4.3.0, requires 4.3.1'.PHP_EOL) !== FALSE && strpos($reportSaid, 'did not start') === FALSE, $reportSaid);
check('the report says the Engine could not be loaded', strpos($reportSaid, 'The Engine could not be loaded, so nothing else can be reported.') !== FALSE);
$InstLoc = makeInstallation('p03', '');
list ($reportPrinted, $reportSaid) = printed('showKernelVersionReport', array());
check('a missing Engine is named as not installed, & not said to have not started', strpos($reportSaid, '  Engine     not installed, requires 4.3.1'.PHP_EOL) !== FALSE && strpos($reportSaid, 'did not start') === FALSE, $reportSaid);


// Part 3. A command refused in a folder beside the installation names the installation, with or without an Engine in this folder.
$ApplicationLoc = makeInstallation('p04/installed', 'v4.3.1');
$InstLoc = makeInstallation('p04/here', 'v4.3.1');
$TestLog = array();
list ($refusalStatus, $refusalSaid) = printed('refuseCommandWithoutEngine', array('-fp', 'convertCore.php'));
check('a refused command exits 1 & is logged', $refusalStatus === 1 && $TestLog === array('WARNING!!! The command -fp was refused because the Engine is unavailable.'), json_encode($TestLog));
check('an Engine here that cannot run is said, & the installation is named', strpos($refusalSaid, 'The Engine in this folder cannot run. The installation is '.$ApplicationLoc.'.') !== FALSE
  && strpos($refusalSaid, 'Unpack a drop-in into '.$ApplicationLoc.' & run -fp there.') !== FALSE, $refusalSaid);
$InstLoc = makeInstallation('p04/empty', '');
list ($refusalStatus, $refusalSaid) = printed('refuseCommandWithoutEngine', array('-fp', 'convertCore.php'));
check('a folder with no Engine is said to hold none, & the installation is named', strpos($refusalSaid, 'This folder holds no Engine. The installation is '.$ApplicationLoc.'.') !== FALSE, $refusalSaid);


// Part 4. A command refused in the installation itself names Resources/Engine, which holds the Engine & every Core.
$InstLoc = $ApplicationLoc;
list ($refusalStatus, $refusalSaid) = printed('refuseCommandWithoutEngine', array('--setup', 'convertCore.php'));
check('in the installation, the remedy is the whole of Resources/Engine', $refusalStatus === 1 && strpos($refusalSaid, 'A mismatched Engine, or one that did not start, is a partial update. Replace Resources/Engine'.PHP_EOL) !== FALSE
  && strpos($refusalSaid, 'The installation is') === FALSE, $refusalSaid);

finish('test_kernel_without_engine');
