<?php
// HRProprietary Engine v4.3.2. The Engine's acceptance test. It installs the Starter & runs it as an administrator would.
//
//   bash Documentation/Build/Tests/assemble-example.sh /path/to/a/new/folder Starter
//   cd /path/to/a/new/folder
//   sudo bash Documentation/Build/Tests/contained-install.sh php Documentation/Build/Tests/test_starter-cli.php
// Run it from the root of the assembled Starter, inside contained-install.sh, where /DATA & /var/www/html are memory made for the run.
//
// The Starter is the smallest application the Engine runs, & supplies only what the contract asks of an application. An Engine that
// needs anything more of one, such as a function only the Example defines, fails here. It is how v4.3.2 found that an application
// accepting no pipeline was treated as broken.
// It copies the tree to /var/www/html/HRProprietary/Starter, where the Starter's contract installs it, & runs app.php there as root,
// each command a process of its own without a shell: --config --repair, -v, -h, --app-hello & -fp, which installs it into
// /DATA/Starter/Application & publishes the loader. Then it runs the installed copy & the loader, & meets the Environment Core missing
// & mismatched with -v, -h, -fp & --setup.
// It removes nothing. The Environment Core is moved to the scratch folder & back, & config.php & the Environment Core are changed only
// in the copy in memory, & put back as they were.

if (PHP_SAPI !== 'cli') { if (!headers_sent()) http_response_code(404); die(); }

if (!is_file('Documentation/Build/Tests/testlib-cli.php')) { fwrite(STDERR, 'Run this from the root of the application it tests. See Documentation/Build/Tests/README.txt.'.PHP_EOL); exit(64); }
require 'Documentation/Build/Tests/testlib-cli.php';

$tree = testTree(isset($argv[1]) ? $argv[1] : (string)getcwd());
if (getenv('HRP_INSTALL') !== '1') testStop('This suite installs the Starter, so it runs only inside contained-install.sh. See Documentation/Build/Tests/README.txt.');
requireContainment();
if (!is_file($tree.'/app.php') or strpos((string)@file_get_contents($tree.'/Resources/Contract/app-engine-config.php'), "\$EngineApplicationName = 'Starter';") === FALSE) testStop($tree.' is not the assembled Starter. Assemble it with assemble-example.sh, naming Starter.');

$hosted = mustBeInstallFolder('/var/www/html/HRProprietary/Starter');
$installed = mustBeInstallFolder('/DATA/Starter/Application');
$environmentCore = 'Resources/Engine/Cores/environmentCore.php';
makeFolder(scratchPath('starter'), 0700);

// Runs app.php in the folder named, as root, with the arguments named. Gives back its exit status & everything it said, in that order.
function starter($folderPath, $arguments) {
  if (!chdir($folderPath)) testStop($folderPath.' could not be entered.');
  return runCommand(array_merge(array(PHP_BINARY, 'app.php'), $arguments)); }

// Says whether PHP itself raised anything while a command ran, which no command of the Engine's should ever print.
function phpComplained($said) {
  return preg_match('/^(PHP )?(Fatal error|Warning|Notice|Deprecated|Parse error): /m', $said) === 1; }

// Says whether what a command said names the Example, which nothing the Engine says for another application should.
function namesTheExample($said) {
  return stripos($said, 'HRConvert2') !== FALSE or strpos($said, 'convertCore') !== FALSE; }

// Says whether every line named is in what a command said.
function allIn($expectedLines, $said) {
  foreach ($expectedLines as $expectedLine) if (strpos($said, $expectedLine) === FALSE) return FALSE;
  return TRUE; }

// Moves a file, without a shell, between the installation in memory & the scratch folder. Nothing is removed.
function moveFile($fromPath, $toPath) {
  list ($moveStatus, $moveSaid) = runCommand(array('mv', '-T', $fromPath, $toPath));
  if ($moveStatus !== 0) testStop($fromPath.' could not be moved to '.$toPath.'. '.$moveSaid);
  clearstatcache();
  return TRUE; }

// Writes a file of the installation in memory, which a check changes & puts back.
function rewriteInstalled($filePath, $fileText) {
  mustBeInstallFolder($filePath);
  if (file_put_contents($filePath, $fileText) !== strlen($fileText)) testStop($filePath.' could not be written.');
  clearstatcache();
  return TRUE; }

// Lists the names in a folder, in order.
function namesIn($folderPath) {
  $folderNames = @scandir($folderPath);
  if (!is_array($folderNames)) return array('<not a folder>');
  $folderNames = array_values(array_diff($folderNames, array('.', '..')));
  sort($folderNames);
  return $folderNames; }

// The rows -v prints for each tier & each of the Engine's cores, as the Starter pins them.
$tierRows = array("Starter 4.3.2\n", "  Kernel     4.3.3, requires 4.3.3\n", "  Engine     4.3.3, requires 4.3.3\n",
  "             & its Environment Core 4.3.3, requires 4.3.3\n", "  Config     4.3.2, requires 4.3.2 or later\n");
$coreRows = array("  Setup Core                  OK, 4.3.3\n", "  Dependency Core             OK, 4.3.3\n", "  Pipeline Core               OK, 4.3.2\n",
  "  Workflow Core               OK, 4.3.1\n", "  Authentication Core         OK, 4.2.3\n", "  Email Core                  OK, 4.2.0\n", "  Event Core                  OK, 4.3.1\n");
$noPipelines = "  Pipelines                   NONE, this application accepts none\n";

list ($madeStatus, $madeSaid) = runCommand(array('mkdir', '-p', $hosted));
list ($copyStatus, $copySaid) = runCommand(array('cp', '-a', $tree.'/.', $hosted.'/'));
if ($madeStatus !== 0 or $copyStatus !== 0) testStop('The Starter could not be copied to '.$hosted.'. '.$madeSaid.$copySaid);


// Part 1. With no config.php & no Environment Core, nothing can write one, & that is said & is a failure.
moveFile(mustBeInstallFolder($hosted.'/'.$environmentCore), mustBeScratch(scratchPath('starter/environmentCore.php')));
list ($status, $said) = starter($hosted, array('--config', '--repair'));
check('without its Environment Core, --config --repair writes no config.php, says why & fails', $status === 1 && !file_exists($hosted.'/Resources/config.php')
  && strpos($said, 'No configuration file & no Environment Core to write one!') !== FALSE && !phpComplained($said), $said);
moveFile(scratchPath('starter/environmentCore.php'), mustBeInstallFolder($hosted.'/'.$environmentCore));


// Part 2. --config --repair writes config.php from the two halves of the template.
list ($status, $said) = starter($hosted, array('--config', '--repair'));
check('--config --repair writes config.php from both halves of the template', $status === 0 && strpos($said, 'No config.php was found. Writing the defaults from the template.') !== FALSE
  && preg_match('/Wrote [0-9]+ setting\(s\), all defaults\./', $said) === 1 && !phpComplained($said), $said);
$configText = (string)@file_get_contents($hosted.'/Resources/config.php');
check('config.php holds the Starter\'s own setting & version, & the defaults the Starter gives the Engine\'s', strpos($configText, "\$Greeting = 'Hello from the Starter.';") !== FALSE
  && strpos($configText, "\$ConfigVersion = 'v4.3.2';") !== FALSE && strpos($configText, "\$ConvertLoc = '/DATA/Starter';") !== FALSE && strpos($configText, "\$ApplicationName = 'Starter';") !== FALSE);


// Part 3. -v, before the Starter is installed.
list ($status, $said) = starter($hosted, array('-v'));
check('-v names the Starter & each tier, at the version each pin requires', $status === 0 && allIn($tierRows, $said), $said);
check('each of the Engine\'s cores is the version the Starter pins', allIn($coreRows, $said), $said);
check('an application that accepts no pipeline is told so, & nothing about it is a warning, an error or a failure', strpos($said, $noPipelines) !== FALSE
  && strpos($said, '34001') === FALSE && strpos($said, 'Pipeline Core is unavailable') === FALSE && preg_match('/^(WARNING|ERROR)!!! .*accepts no pipeline/m', $said) === 0, $said);
check('-v says nothing of the Example, & PHP raised nothing', !namesTheExample($said) && !phpComplained($said), $said);


// Part 4. -h, which the Engine prints & the Starter adds to.
list ($status, $said) = starter($hosted, array('-h'));
check('-h describes the Starter, & names app.php', $status === 0 && strpos($said, "Starter 4.3.2\nThe smallest application the HRProprietary Engine runs. Copy it to begin one of your own.\n") !== FALSE
  && strpos($said, "  php app.php [argument]\n") !== FALSE && strpos($said, 'https://github.com/zelon88/HRProprietary') !== FALSE, $said);
check('-h lists the Starter\'s command after the Engine\'s', strpos($said, "  --app-hello                 Print what --Greeting-- holds & the Starter's version. It changes nothing.\n") !== FALSE
  && strpos($said, '  -fp, --fix-permissions') !== FALSE && strpos($said, '  -fp, --fix-permissions') < strpos($said, '  --app-hello'), $said);
check('-h says nothing of the Example, & PHP raised nothing', !namesTheExample($said) && !phpComplained($said), $said);


// Part 5. The Starter's own command, & one it does not know.
list ($status, $said) = starter($hosted, array('--app-hello'));
check('--app-hello prints the greeting & the version, & succeeds', $status === 0 && strpos($said, "Hello from the Starter.\nThis is the Starter, v4.3.2.\n") !== FALSE && !phpComplained($said), $said);
list ($status, $said) = starter($hosted, array('--app-nothing'));
check('an argument beginning --app that the Starter does not know is refused with status 2, & is a warning', $status === 2
  && strpos($said, 'The Starter accepts no --app-nothing command. Run  php app.php -h  for the ones it does.') !== FALSE
  && strpos($said, 'The application argument --app-nothing is not one the Starter accepts.') !== FALSE && !phpComplained($said), $said);
// A greeting an administrator wrote is printed as a terminal shows text.
rewriteInstalled($hosted.'/Resources/config.php', str_replace("\$Greeting = 'Hello from the Starter.';", "\$Greeting = 'Hi\x1b[2J there.';", $configText));
list ($status, $said) = starter($hosted, array('--app-hello'));
check('a control character in the greeting is printed as a question mark', $status === 0 && strpos($said, "Hi?[2J there.\n") !== FALSE && strpos($said, "\x1b") === FALSE, $said);
rewriteInstalled($hosted.'/Resources/config.php', $configText);


// Part 6. -fp installs the Starter into the data location & publishes the loader.
list ($status, $said) = starter($hosted, array('-fp'));
check('-fp installs the Starter into Application in the data location, & runs the installed copy\'s -fp', $status === 0
  && preg_match('#  Installed   Installed [0-9]+ file\(s\) from /var/www/html/HRProprietary/Starter into /DATA/Starter/Application\.\n#', $said) === 1
  && strpos($said, "  Running     /DATA/Starter/Application/app.php -fp\n") !== FALSE && !phpComplained($said), $said);
check('the installed copy publishes the loader, & the hosted location serves nothing else', preg_match('#  Published   Wrote 1 & removed [0-9]+ name\(s\)\. Serves the loader & 0 other file\(s\)\.\n#', $said) === 1
  && namesIn($hosted) === array('DATA', 'app.php') && strpos((string)file_get_contents($hosted.'/app.php'), 'STARTER-LOADER-MARKER') !== FALSE, implode(' ', namesIn($hosted)));
clearstatcache();
check('the installation is whole, & root\'s', is_file($installed.'/app.php') && is_file($installed.'/Resources/Engine/engine.php') && is_file($installed.'/Resources/config.php')
  && fileowner($installed) === 0 && fileowner($installed.'/app.php') === 0 && (fileperms($installed) & 0022) === 0, implode(' ', namesIn($installed)));
check('-fp says nothing of the Example', !namesTheExample($said), $said);


// Part 7. The installed copy, & the loader that runs it.
list ($status, $said) = starter($installed, array('-v'));
check('the installed copy\'s -v reports the installation & the hosted location as they are', $status === 0 && allIn(array_merge($tierRows, $coreRows, array($noPipelines,
  "  Installation                OK, /DATA/Starter/Application\n",
  "  Hosted location             OK, /var/www/html/HRProprietary/Starter. Serves the loader & 0 other file(s) from /DATA/Starter/Application.\n")), $said)
  && !namesTheExample($said) && !phpComplained($said), $said);
list ($status, $said) = starter($hosted, array('-v'));
check('the loader runs the installed copy', $status === 0 && strpos($said, "Starter 4.3.2\n") !== FALSE && strpos($said, "  Installation                OK, /DATA/Starter/Application\n") !== FALSE
  && !phpComplained($said), $said);
list ($status, $said) = starter($hosted, array('--app-hello'));
check('the loader hands the Starter\'s own command to the installed copy', $status === 0 && strpos($said, "Hello from the Starter.\nThis is the Starter, v4.3.2.\n") !== FALSE, $said);


// Part 8. The installed copy without its Environment Core. The Engine loads & does not start, & only what needs no Engine answers.
moveFile(mustBeInstallFolder($installed.'/'.$environmentCore), mustBeScratch(scratchPath('starter/environmentCore-installed.php')));
list ($status, $said) = starter($installed, array('-v'));
check('without its Environment Core the Engine loads & does not start, & -v says so & counts it as failed', $status === 0
  && strpos($said, "  Engine     4.3.3, requires 4.3.3, & did not start\n") !== FALSE && strpos($said, 'The Engine loaded & did not start, so nothing else can be reported. Its log says why.') !== FALSE
  && strpos($said, '1 check(s) failed.') !== FALSE && !phpComplained($said), $said);
list ($status, $said) = starter($installed, array('-h'));
check('-h still answers, without the Engine\'s commands', $status === 0 && strpos($said, 'The Engine could not be loaded, so none of its commands are listed.') !== FALSE
  && strpos($said, '  -fp, --fix-permissions') === FALSE && !phpComplained($said), $said);
list ($status, $said) = starter($installed, array('-fp'));
check('-fp is refused with status 1, & names what to replace', $status === 1 && strpos($said, 'This command needs the Engine, which could not be loaded.') !== FALSE
  && strpos($said, 'Replace Resources/Engine') !== FALSE && !phpComplained($said), $said);
moveFile(scratchPath('starter/environmentCore-installed.php'), mustBeInstallFolder($installed.'/'.$environmentCore));


// Part 9. An Environment Core of another version is judged before it is loaded, & stops the Engine as a missing one does.
$coreText = (string)file_get_contents($installed.'/'.$environmentCore);
if (substr_count($coreText, "\$EnvironmentCoreVersion = 'v4.3.3';") !== 1) testStop('The installed Environment Core does not declare v4.3.3 once.');
rewriteInstalled($installed.'/'.$environmentCore, str_replace("\$EnvironmentCoreVersion = 'v4.3.3';", "\$EnvironmentCoreVersion = 'v4.3.0';", $coreText));
list ($status, $said) = starter($installed, array('-v'));
check('an Environment Core of another version stops the Engine starting, & -v says so', $status === 0 && strpos($said, "  Engine     4.3.3, requires 4.3.3, & did not start\n") !== FALSE
  && strpos($said, '1 check(s) failed.') !== FALSE && !phpComplained($said), $said);
list ($status, $said) = starter($installed, array('--setup'));
check('--setup is refused with status 1', $status === 1 && strpos($said, 'This command needs the Engine, which could not be loaded.') !== FALSE && !phpComplained($said), $said);
rewriteInstalled($installed.'/'.$environmentCore, $coreText);
list ($status, $said) = starter($installed, array('-v'));
check('with its Environment Core as it shipped, the installed copy starts the Engine again', $status === 0 && strpos($said, "  Engine     4.3.3, requires 4.3.3\n") !== FALSE
  && strpos($said, 'did not start') === FALSE && strpos($said, $noPipelines) !== FALSE, $said);

finish('test_starter');
