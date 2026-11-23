<?php
// HRProprietary Engine v4.3.2. The unit suite of the installer, which -fp & --setup --install-complete run.
//
//   sudo bash Documentation/Build/Tests/contained.sh php Documentation/Build/Tests/test_install-cli.php
// Run it from the root of the application under test. A tree named after it is tested instead, & must be that root.
//
// installApplicationTree(), retireApplicationTree(), oldInstallationPath() & placeStagedTree() are lifted out of the tree's own
// Resources/Engine/Cores/setupCore.php, exactly as the tree has it, into a namespace where rename(), exec(), chmod(),
// removeTreeWithoutFollowing() & pathIsSafeToModifyRecursively() are stood in for. Each part installs a release of its own into a
// data location of its own beneath the scratch folder, & makes a rename, a removal or a mode fail where its checks need one to.
// From v4.3.2 nothing is removed from Application before the new installation is in place, & an installation that cannot be put in
// place puts back the one it was to replace. Part 5 is the case that left no installation at all until then.
//
// It removes nothing. The command the installer runs to remove a folder, & the removal it asks of the kernel, each set what is at
// the path aside instead, so what the installer would have removed is still there to be read. A rename & a mode are let through only
// beneath the scratch folder, & the kernel's removal is never reached.

if (PHP_SAPI !== 'cli') { if (!headers_sent()) http_response_code(404); die(); }

if (!is_file('Documentation/Build/Tests/testlib-cli.php')) { fwrite(STDERR, 'Run this from the root of the application it tests. See Documentation/Build/Tests/README.txt.'.PHP_EOL); exit(64); }
require 'Documentation/Build/Tests/testlib-cli.php';

$tree = testTree(isset($argv[1]) ? $argv[1] : (string)getcwd());
requireContainment();

// What the kernel & the Engine would have set before the installer ran. The layout names three entries every release here carries,
// & a fourth none does, which is skipped.
$EnableMemoryProtection = TRUE;
$Verbose = FALSE;
$LogFile = '';
$RunningAsRoot = TRUE;
$DirSep = DIRECTORY_SEPARATOR;
$Lol = PHP_EOL;
$EngineEntryPoint = 'app.php';
$EngineApplicationLayout = array('app.php', 'Resources', 'UI', 'README.txt');
$TestLog = array();
$TestCalls = array();
$TestRemoved = array();

// The kernel's removal is never run here. Its stand-in answers every call, & this stops the suite were one ever to reach it.
function removeTreeWithoutFollowing($treePath) {
  testStop('removeTreeWithoutFollowing() was reached for '.$treePath.' without its stand-in answering. Nothing was removed.'); }

$setupFile = $tree.'/Resources/Engine/Cores/setupCore.php';
liftFunctions($tree.'/Resources/Kernel/kernel.php', array('getChildFunction', 'purgeSensitiveMemory', 'pathIsTrustedByRoot', 'treeIsTrustedByRoot'), array());
liftFunctions($tree.'/Resources/Engine/engine.php', array('pathIsSafeToModifyRecursively'), array());
liftFunctions($tree.'/Resources/Engine/Cores/environmentCore.php', array('engineApplicationEntryPoint'), array());
liftFunctions($setupFile, array('codeMissingFromTree', 'copyApplicationEntry'), array());
standInFor('Install', array('rename', 'exec', 'chmod', 'removeTreeWithoutFollowing', 'pathIsSafeToModifyRecursively'));
liftFunctionsInto('Install', $setupFile, array('oldInstallationPath', 'placeStagedTree', 'retireApplicationTree', 'installApplicationTree'), array('rename', 'exec', 'removeTreeWithoutFollowing'));

// Notes a call the installer made, in order. The staged copy's name is random, so it is noted as <staged>.
function noteCall($callName, $callDetail) {
  global $TestCalls;
  $TestCalls[] = $callName.' '.preg_replace('#/\.Application-install-[0-9a-f]{16}#', '/<staged>', $callDetail);
  return TRUE; }

// Sets aside what is at a path the installer removed, & notes what kind of removal it was, the path & where what was there went,
// which is empty where nothing was.
function setAsideFor($removalKind, $removedPath) {
  global $TestRemoved;
  mustBeScratch($removedPath);
  noteCall($removalKind, $removedPath);
  $TestRemoved[] = array($removalKind, $removedPath, (file_exists($removedPath) or is_link($removedPath)) ? setAside($removedPath) : '');
  return TRUE; }

// A hook for exec(). The installer runs one command, rm -rf on one folder named by escapeshellarg(), & anything else stops the suite.
function commandHook() {
  return function ($arguments, $callNumber) {
    $commandText = (string)$arguments[0];
    if (count($arguments) !== 1 or preg_match("/^rm -rf '([^']+)' 2>&1$/D", $commandText, $commandParts) !== 1) testStop('The installer ran a command this suite does not expect, '.$commandText.', & it was not run.');
    setAsideFor('command', $commandParts[1]);
    return array('answer', ''); }; }

// A hook for removeTreeWithoutFollowing(). The calls numbered are refused, as the kernel refuses a tree it cannot remove. Every other
// sets aside what is at the path & counts as done, as the kernel's does for a path that holds nothing.
function removalHook($refusedCalls) {
  return function ($arguments, $callNumber) use ($refusedCalls) {
    $treePath = mustBeScratch((string)$arguments[0]);
    if (in_array($callNumber, $refusedCalls, TRUE)) {
      noteCall('kernel', $treePath.' (refused)');
      return array('answer', array(FALSE, $treePath.' could not be removed completely.')); }
    setAsideFor('kernel', $treePath);
    return array('answer', array(TRUE, '')); }; }

// A hook for rename(). A rename from a source named is refused, & every other is let through beneath the scratch folder.
// A source ending in * names every path that begins with what comes before it, as the staged copy's random name needs.
function renameHook($refusedSources) {
  return function ($arguments, $callNumber) use ($refusedSources) {
    $fromPath = mustBeScratch((string)$arguments[0]);
    $toPath = mustBeScratch((string)$arguments[1]);
    foreach ($refusedSources as $refusedSource) {
      if ($fromPath === $refusedSource or (substr($refusedSource, -1) === '*' && strpos($fromPath, substr($refusedSource, 0, -1)) === 0)) {
        noteCall('rename', $fromPath.' -> '.$toPath.' (refused)');
        return array('answer', FALSE); } }
    noteCall('rename', $fromPath.' -> '.$toPath);
    return NULL; }; }

// A hook for chmod(). Setting the mode named on the path named is refused, & every other is let through beneath the scratch folder.
function modeHook($refusedPath, $refusedMode) {
  return function ($arguments, $callNumber) use ($refusedPath, $refusedMode) {
    $modePath = mustBeScratch((string)$arguments[0]);
    if ($modePath === $refusedPath && $arguments[1] === $refusedMode) return array('answer', FALSE);
    return NULL; }; }

// A hook for pathIsSafeToModifyRecursively() that takes the path named for a system folder, & judges every other as the tree does.
function systemFolderHook($systemPath) {
  return function ($arguments, $callNumber) use ($systemPath) {
    return ((string)$arguments[0] === $systemPath) ? array('answer', FALSE) : NULL; }; }

// The hooks every run begins with, before a part puts its own in the place of any of them.
function runHooks($partHooks) {
  return array_merge(array('rename' => renameHook(array()), 'exec' => commandHook(), 'chmod' => modeHook('', 0), 'removeTreeWithoutFollowing' => removalHook(array())), $partHooks); }

// The hooks between runs. Nothing lifted runs between them, so a call to any stand-in then stops the suite.
function idleHooks() {
  $refusal = function ($arguments, $callNumber) { testStop('A stand-in was called between runs of the installer, & nothing was done.'); };
  return array('rename' => $refusal, 'exec' => $refusal, 'chmod' => $refusal, 'removeTreeWithoutFollowing' => $refusal, 'pathIsSafeToModifyRecursively' => $refusal); }

// Runs the installer with the hooks a part names. Returns what it returned.
function runInstaller($releasePath, $applicationPath, $backupPath, $partHooks) {
  global $TestLog, $TestCalls, $TestRemoved;
  $TestLog = $TestCalls = $TestRemoved = array();
  setHooks(runHooks($partHooks));
  $installResult = \Install\installApplicationTree($releasePath, $applicationPath, $backupPath);
  setHooks(idleHooks());
  clearstatcache();
  return $installResult; }

// Runs placeStagedTree() alone, with the hooks a part names. Returns what it returned.
function runPlacement($stagedPath, $applicationPath, $asidePath, $partHooks) {
  global $TestLog, $TestCalls, $TestRemoved;
  $TestLog = $TestCalls = $TestRemoved = array();
  setHooks(runHooks($partHooks));
  $placeResult = \Install\placeStagedTree($stagedPath, $applicationPath, $asidePath);
  setHooks(idleHooks());
  clearstatcache();
  return $placeResult; }

// Makes a folder of a part's own, & each folder above it, root's & 0755, as the folders of a release root trusts are. Returns it.
function partFolder($relativePath) {
  $folderPath = '';
  foreach (explode('/', $relativePath) as $folderName) {
    $folderPath = ($folderPath === '' ? '' : $folderPath.'/').$folderName;
    makeFolder(scratchPath($folderPath), 0755); }
  return scratchPath($relativePath); }

// Makes the release a part installs, beside the part's data location, & marks its app.php & its engine.php with the mark named.
// DATA & Notes.txt are not in the layout, so neither is installed. The three files the layout names are.
function makeRelease($partName, $releaseMark) {
  $releasePath = partFolder($partName.'/release');
  writeScratchFile($releasePath.'/app.php', "<?php\n// ".$releaseMark."\n", 0644);
  partFolder($partName.'/release/Resources/Engine');
  writeScratchFile($releasePath.'/Resources/Engine/engine.php', "<?php\n// ".$releaseMark."\n", 0644);
  partFolder($partName.'/release/UI/Default');
  writeScratchFile($releasePath.'/UI/Default/header.php', "<?php\n", 0644);
  partFolder($partName.'/release/DATA');
  writeScratchFile($releasePath.'/DATA/index.html', "\n", 0644);
  writeScratchFile($releasePath.'/Notes.txt', $releaseMark."\n", 0644);
  return $releasePath; }

// Makes an installation as an earlier run of the installer would have left one, marked with the mark named. Returns its folder.
function makeInstallation($installationPath, $installationMark) {
  makeFolder($installationPath, 0755);
  writeScratchFile($installationPath.'/app.php', "<?php\n// ".$installationMark."\n", 0644);
  makeFolder($installationPath.'/Resources', 0755);
  makeFolder($installationPath.'/Resources/Engine', 0755);
  writeScratchFile($installationPath.'/Resources/Engine/engine.php', "<?php\n// ".$installationMark."\n", 0644);
  return $installationPath; }

// Gives the mark of the installation in a folder, or nothing where it holds none.
function markOf($folderPath) {
  $entryText = (is_file($folderPath.'/app.php') && !is_link($folderPath.'/app.php')) ? (string)file_get_contents($folderPath.'/app.php') : '';
  return (preg_match('#^<\?php\n// (.+)\n$#D', $entryText, $markParts) === 1) ? $markParts[1] : ''; }

// Gives the mark of what a removal set aside, or nothing where it set nothing aside.
function markRemoved($removalIndex) {
  global $TestRemoved;
  return (isset($TestRemoved[$removalIndex]) && $TestRemoved[$removalIndex][2] !== '') ? markOf($TestRemoved[$removalIndex][2]) : ''; }

// Says whether a removal was of the kind named, at the path named.
function removalWas($removalIndex, $removalKind, $removedPath) {
  global $TestRemoved;
  return isset($TestRemoved[$removalIndex]) && $TestRemoved[$removalIndex][0] === $removalKind && $TestRemoved[$removalIndex][1] === $removedPath; }

// Lists the names in a folder, in order.
function namesIn($folderPath) {
  $folderNames = @scandir($folderPath);
  if (!is_array($folderNames)) return array('<not a folder>');
  $folderNames = array_values(array_diff($folderNames, array('.', '..')));
  sort($folderNames);
  return $folderNames; }

// The line the installer logs where it installed a release of three files, with what it says of the installation it replaced.
function installedLine($releasePath, $applicationPath, $retireNotice) {
  return 'Op-Act: Installed 3 file(s) from '.$releasePath.' into '.$applicationPath.'.'.($retireNotice === '' ? '' : ' '.$retireNotice); }

setHooks(idleHooks());


// Part 1. A first installation, where Application holds nothing yet.
$releasePath = makeRelease('p01', 'release one');
$dataPath = partFolder('p01/data');
$applicationPath = $dataPath.'/Application';
list ($wasInstalled, $installNotice) = runInstaller($releasePath, $applicationPath, $dataPath.'/Last-Installed-Version', array());
check('a first installation is installed', $wasInstalled === TRUE && markOf($applicationPath) === 'release one', $installNotice);
check('only the entries the layout names are installed', is_file($applicationPath.'/Resources/Engine/engine.php') && is_file($applicationPath.'/UI/Default/header.php')
  && namesIn($applicationPath) === array('Resources', 'UI', 'app.php'), implode(' ', namesIn($applicationPath)));
check('Application is 0755', (fileperms($applicationPath) & 07777) === 0755, decoct(fileperms($applicationPath) & 07777));
check('the staged copy is renamed into place, & nothing else is moved or removed', $TestCalls === array('rename '.$dataPath.'/<staged> -> '.$applicationPath) && $TestRemoved === array(), json_encode($TestCalls));
check('nothing is left beside Application', namesIn($dataPath) === array('Application'), implode(' ', namesIn($dataPath)));
check('it is logged as installed, & nothing is warned about', $TestLog === array(installedLine($releasePath, $applicationPath, '')), json_encode($TestLog));


// Part 2. The installation it replaces is kept as the backup.
$releasePath = makeRelease('p02', 'release two');
$dataPath = partFolder('p02/data');
$applicationPath = makeInstallation($dataPath.'/Application', 'release one');
$backupPath = $dataPath.'/Last-Installed-Version';
list ($wasInstalled, $installNotice) = runInstaller($releasePath, $applicationPath, $backupPath, array());
check('an installation the backup can hold is replaced', $wasInstalled === TRUE && markOf($applicationPath) === 'release two', $installNotice);
check('the one it replaced is the backup', markOf($backupPath) === 'release one');
check('it is moved to the backup before the new one takes its name, & nothing is removed', $TestCalls === array('rename '.$applicationPath.' -> '.$backupPath, 'rename '.$dataPath.'/<staged> -> '.$applicationPath)
  && $TestRemoved === array(), json_encode($TestCalls));
check('nothing else is left beside Application', namesIn($dataPath) === array('Application', 'Last-Installed-Version'), implode(' ', namesIn($dataPath)));
check('the notice says where it is kept', $TestLog === array(installedLine($releasePath, $applicationPath, 'The installation it replaced is kept at '.$backupPath.'.')), json_encode($TestLog));


// Part 3. An earlier backup gives way to the installation being replaced.
$releasePath = makeRelease('p03', 'release three');
$dataPath = partFolder('p03/data');
$applicationPath = makeInstallation($dataPath.'/Application', 'release two');
$backupPath = makeInstallation($dataPath.'/Last-Installed-Version', 'release one');
list ($wasInstalled, $installNotice) = runInstaller($releasePath, $applicationPath, $backupPath, array());
check('an installation is replaced where a backup is already kept', $wasInstalled === TRUE && markOf($applicationPath) === 'release three', $installNotice);
check('the backup is the installation it replaced', markOf($backupPath) === 'release two');
check('the earlier backup is removed first, & nothing else is', count($TestRemoved) === 1 && removalWas(0, 'command', $backupPath) && markRemoved(0) === 'release one', json_encode($TestRemoved));
check('then the installation takes its place, & the new one takes the installation\'s', $TestCalls === array('command '.$backupPath, 'rename '.$applicationPath.' -> '.$backupPath, 'rename '.$dataPath.'/<staged> -> '.$applicationPath), json_encode($TestCalls));


// Part 4. An installation the backup cannot hold waits at Application.old, & is removed only once the new one is in place.
$releasePath = makeRelease('p04', 'release four');
$dataPath = partFolder('p04/data');
$applicationPath = makeInstallation($dataPath.'/Application', 'release three');
$oldPath = $applicationPath.'.old';
list ($wasInstalled, $installNotice) = runInstaller($releasePath, $applicationPath, '', array());
check('an installation is replaced where no backup is configured', $wasInstalled === TRUE && markOf($applicationPath) === 'release four', $installNotice);
check('the one it replaced is moved to Application.old, & removed from there only once the new one has the name', $TestCalls === array('kernel '.$oldPath, 'rename '.$applicationPath.' -> '.$oldPath,
  'rename '.$dataPath.'/<staged> -> '.$applicationPath, 'kernel '.$oldPath), json_encode($TestCalls));
check('nothing was at Application.old before, & what was removed from it after is the installation it replaced', count($TestRemoved) === 2 && removalWas(0, 'kernel', $oldPath) && $TestRemoved[0][2] === ''
  && removalWas(1, 'kernel', $oldPath) && markRemoved(1) === 'release three', json_encode($TestRemoved));
check('nothing is left beside Application', namesIn($dataPath) === array('Application'), implode(' ', namesIn($dataPath)));
check('the notice says it was not kept, & why', $TestLog === array(installedLine($releasePath, $applicationPath, 'The installation it replaced was not kept, because no backup location is configured.')), json_encode($TestLog));


// Part 5. A new installation that cannot take the name puts back the one it was to replace. Until v4.3.2 that one had been removed,
// & Application was left missing.
$releasePath = makeRelease('p05', 'release five');
$dataPath = partFolder('p05/data');
$applicationPath = makeInstallation($dataPath.'/Application', 'release four');
$oldPath = $applicationPath.'.old';
list ($wasInstalled, $installNotice) = runInstaller($releasePath, $applicationPath, '', array('rename' => renameHook(array($dataPath.'/.Application-install-*'))));
check('a new installation that cannot take the name is not installed', $wasInstalled === FALSE, $installNotice);
check('the installation it was to replace is put back, whole', markOf($applicationPath) === 'release four' && is_file($applicationPath.'/Resources/Engine/engine.php'), implode(' ', namesIn($dataPath)));
check('it was moved aside, & moved back once the new one could not take the name', $TestCalls === array('kernel '.$oldPath, 'rename '.$applicationPath.' -> '.$oldPath,
  'rename '.$dataPath.'/<staged> -> '.$applicationPath.' (refused)', 'rename '.$oldPath.' -> '.$applicationPath, 'command '.$dataPath.'/<staged>'), json_encode($TestCalls));
check('nothing of it was removed, & the staged copy was', count($TestRemoved) === 2 && $TestRemoved[0][2] === '' && $TestRemoved[1][0] === 'command' && markRemoved(1) === 'release five', json_encode($TestRemoved));
check('nothing is left beside Application', namesIn($dataPath) === array('Application'), implode(' ', namesIn($dataPath)));
check('it is warned about, & the notice is the warning', $TestLog === array('WARNING!!! The staged installation could not be moved into '.$applicationPath.', so the installation it would have replaced was put back.')
  && $installNotice === 'The staged installation could not be moved into '.$applicationPath.', so the installation it would have replaced was put back.', json_encode($TestLog));


// Part 6. One the backup holds is put back from the backup.
$releasePath = makeRelease('p06', 'release six');
$dataPath = partFolder('p06/data');
$applicationPath = makeInstallation($dataPath.'/Application', 'release five');
$backupPath = $dataPath.'/Last-Installed-Version';
list ($wasInstalled, $installNotice) = runInstaller($releasePath, $applicationPath, $backupPath, array('rename' => renameHook(array($dataPath.'/.Application-install-*'))));
check('a new installation that cannot take the name is not installed where the backup holds the old one', $wasInstalled === FALSE, $installNotice);
check('the installation it was to replace is put back from the backup', markOf($applicationPath) === 'release five' && !file_exists($backupPath) && namesIn($dataPath) === array('Application'), implode(' ', namesIn($dataPath)));
check('it went to the backup, & came back from there', $TestCalls === array('rename '.$applicationPath.' -> '.$backupPath, 'rename '.$dataPath.'/<staged> -> '.$applicationPath.' (refused)',
  'rename '.$backupPath.' -> '.$applicationPath, 'command '.$dataPath.'/<staged>'), json_encode($TestCalls));
check('the warning says it was put back', $TestLog === array('WARNING!!! The staged installation could not be moved into '.$applicationPath.', so the installation it would have replaced was put back.'), json_encode($TestLog));


// Part 7. One that cannot be put back is left whole where it waits, & the warning says where.
$releasePath = makeRelease('p07', 'release seven');
$dataPath = partFolder('p07/data');
$applicationPath = makeInstallation($dataPath.'/Application', 'release six');
$oldPath = $applicationPath.'.old';
list ($wasInstalled, $installNotice) = runInstaller($releasePath, $applicationPath, '', array('rename' => renameHook(array($dataPath.'/.Application-install-*', $oldPath))));
check('neither installation takes the name', $wasInstalled === FALSE && !file_exists($applicationPath) && !is_link($applicationPath), $installNotice);
check('the installation it was to replace is whole at Application.old', markOf($oldPath) === 'release six' && is_file($oldPath.'/Resources/Engine/engine.php'), implode(' ', namesIn($dataPath)));
check('nothing of it was removed, & the staged copy was', count($TestRemoved) === 2 && $TestRemoved[0][2] === '' && $TestRemoved[1][0] === 'command' && markRemoved(1) === 'release seven', json_encode($TestRemoved));
check('it was moved aside, & could not be moved back', $TestCalls === array('kernel '.$oldPath, 'rename '.$applicationPath.' -> '.$oldPath, 'rename '.$dataPath.'/<staged> -> '.$applicationPath.' (refused)',
  'rename '.$oldPath.' -> '.$applicationPath.' (refused)', 'command '.$dataPath.'/<staged>'), json_encode($TestCalls));
check('the warning names where it is & says what to do', $TestLog === array('WARNING!!! The staged installation could not be moved into '.$applicationPath.', & the installation it would have replaced could not be put back from '.$oldPath.'. Move it back by hand.'), json_encode($TestLog));


// Part 8. A first installation that cannot take the name has nothing to put back.
$releasePath = makeRelease('p08', 'release eight');
$dataPath = partFolder('p08/data');
$applicationPath = $dataPath.'/Application';
list ($wasInstalled, $installNotice) = runInstaller($releasePath, $applicationPath, $dataPath.'/Last-Installed-Version', array('rename' => renameHook(array($dataPath.'/.Application-install-*'))));
check('a first installation that cannot take the name is not installed', $wasInstalled === FALSE && namesIn($dataPath) === array(), $installNotice);
check('nothing is put back where nothing was', $TestCalls === array('rename '.$dataPath.'/<staged> -> '.$applicationPath.' (refused)', 'command '.$dataPath.'/<staged>'), json_encode($TestCalls));
check('the warning says only that it could not be moved', $TestLog === array('WARNING!!! The staged installation could not be moved into '.$applicationPath.'.'), json_encode($TestLog));


// Part 9. What a run that stopped part way left at Application.old is removed before the installation is moved there.
$releasePath = makeRelease('p09', 'release nine');
$dataPath = partFolder('p09/data');
$applicationPath = makeInstallation($dataPath.'/Application', 'release eight');
$oldPath = makeInstallation($dataPath.'/Application.old', 'release seven');
list ($wasInstalled, $installNotice) = runInstaller($releasePath, $applicationPath, '', array());
check('an installation is replaced where an earlier run left one at Application.old', $wasInstalled === TRUE && markOf($applicationPath) === 'release nine', $installNotice);
check('what was left is removed before the installation is moved there', $TestCalls === array('kernel '.$oldPath, 'rename '.$applicationPath.' -> '.$oldPath, 'rename '.$dataPath.'/<staged> -> '.$applicationPath, 'kernel '.$oldPath)
  && markRemoved(0) === 'release seven' && markRemoved(1) === 'release eight', json_encode($TestCalls));
check('nothing is left beside Application', namesIn($dataPath) === array('Application'), implode(' ', namesIn($dataPath)));


// Part 10. What was left at Application.old that cannot be removed stops the installation before anything is moved.
$releasePath = makeRelease('p10', 'release ten');
$dataPath = partFolder('p10/data');
$applicationPath = makeInstallation($dataPath.'/Application', 'release nine');
$oldPath = makeInstallation($dataPath.'/Application.old', 'release eight');
list ($wasInstalled, $installNotice) = runInstaller($releasePath, $applicationPath, '', array('removeTreeWithoutFollowing' => removalHook(array(1))));
check('an installation is not replaced where what was left at Application.old cannot be removed', $wasInstalled === FALSE, $installNotice);
check('neither installation is moved or removed', markOf($applicationPath) === 'release nine' && markOf($oldPath) === 'release eight' && $TestCalls === array('kernel '.$oldPath.' (refused)', 'command '.$dataPath.'/<staged>'), json_encode($TestCalls));
check('both are warned about, with why', $TestLog === array('WARNING!!! What an earlier installation or update left at '.$oldPath.' could not be removed, so the installation at '.$applicationPath.' was not moved aside. '.$oldPath.' could not be removed completely.',
  'WARNING!!! The installation at '.$applicationPath.' could not be moved aside, so the new one was not put in its place.'), json_encode($TestLog));


// Part 11. The installation it replaced is left at Application.old where it cannot be removed, & the new one stays installed.
$releasePath = makeRelease('p11', 'release eleven');
$dataPath = partFolder('p11/data');
$applicationPath = makeInstallation($dataPath.'/Application', 'release ten');
$oldPath = $applicationPath.'.old';
list ($wasInstalled, $installNotice) = runInstaller($releasePath, $applicationPath, '', array('removeTreeWithoutFollowing' => removalHook(array(2))));
check('an installation whose predecessor cannot be removed is installed', $wasInstalled === TRUE && markOf($applicationPath) === 'release eleven', $installNotice);
check('the one it replaced is left at Application.old', markOf($oldPath) === 'release ten' && $TestCalls === array('kernel '.$oldPath, 'rename '.$applicationPath.' -> '.$oldPath,
  'rename '.$dataPath.'/<staged> -> '.$applicationPath, 'kernel '.$oldPath.' (refused)'), json_encode($TestCalls));
check('it is warned about, with why, & the installation is logged', $TestLog === array('WARNING!!! The installation it replaced is left at '.$oldPath.', & could not be removed. '.$oldPath.' could not be removed completely. Remove it by hand.',
  installedLine($releasePath, $applicationPath, 'The installation it replaced was not kept, because no backup location is configured.')), json_encode($TestLog));


// Part 12. A mode that cannot be set on an installation in place is warned about. Until v4.3.2 it was reported as a move that failed.
$releasePath = makeRelease('p12', 'release twelve');
$dataPath = partFolder('p12/data');
$applicationPath = $dataPath.'/Application';
list ($wasInstalled, $installNotice) = runInstaller($releasePath, $applicationPath, '', array('chmod' => modeHook($applicationPath, 0755)));
check('an installation in place whose mode cannot be set is installed', $wasInstalled === TRUE && markOf($applicationPath) === 'release twelve', $installNotice);
check('its mode is still the staged copy\'s', (fileperms($applicationPath) & 07777) === 0700, decoct(fileperms($applicationPath) & 07777));
check('it is warned about, & logged as installed', $TestLog === array('WARNING!!! The installation at '.$applicationPath.' is in place, & its mode could not be set to 0755. The installed copy\'s -fp sets it.',
  installedLine($releasePath, $applicationPath, '')), json_encode($TestLog));


// Part 13. A backup that holds Application, is Application or is inside it is never emptied, & the installation is replaced as one the
// backup cannot hold is.
foreach (array('a' => array('holds', ''), 'b' => array('is', '/Application'), 'c' => array('is inside', '/Application/Backup')) as $caseLetter => $overlapCase) {
  $releasePath = makeRelease('p13'.$caseLetter, 'release thirteen');
  $dataPath = partFolder('p13'.$caseLetter.'/data');
  $applicationPath = makeInstallation($dataPath.'/Application', 'release twelve');
  $oldPath = $applicationPath.'.old';
  $backupPath = $dataPath.$overlapCase[1];
  list ($wasInstalled, $installNotice) = runInstaller($releasePath, $applicationPath, $backupPath, array());
  check('a backup that '.$overlapCase[0].' Application is not used, & the installation is replaced', $wasInstalled === TRUE && markOf($applicationPath) === 'release thirteen', $installNotice);
  check('a backup that '.$overlapCase[0].' Application is never emptied', $TestCalls === array('kernel '.$oldPath, 'rename '.$applicationPath.' -> '.$oldPath, 'rename '.$dataPath.'/<staged> -> '.$applicationPath, 'kernel '.$oldPath)
    && markRemoved(1) === 'release twelve', json_encode($TestCalls));
  check('the notice says why a backup that '.$overlapCase[0].' Application was not used', $TestLog === array(installedLine($releasePath, $applicationPath, 'The installation it replaced was not kept, because '.$backupPath.' could not hold it. '
    .$backupPath.' is the application folder, is inside it, or holds it.')), json_encode($TestLog)); }


// Part 14. An Application that resolves to a system folder is never moved.
$releasePath = makeRelease('p14', 'release fourteen');
$dataPath = partFolder('p14/data');
$applicationPath = makeInstallation($dataPath.'/Application', 'release thirteen');
$oldPath = $applicationPath.'.old';
list ($wasInstalled, $installNotice) = runInstaller($releasePath, $applicationPath, '', array('pathIsSafeToModifyRecursively' => systemFolderHook($applicationPath)));
check('an installation in a system folder is not replaced, moved or removed', $wasInstalled === FALSE && markOf($applicationPath) === 'release thirteen' && $TestCalls === array('kernel '.$oldPath, 'command '.$dataPath.'/<staged>'), json_encode($TestCalls));
check('it is warned about', $TestLog === array('WARNING!!! The installation at '.$applicationPath.' could not be moved aside, so the new one was not put in its place.'), json_encode($TestLog));


// Part 15. Where the installation waits, by name.
check('it waits beside Application, at the name an update uses', \Install\oldInstallationPath('/srv/data/Application') === '/srv/data/Application.old' && \Install\oldInstallationPath('/srv/data/Application/') === '/srv/data/Application.old');
check('an Application with no name has nowhere to wait', \Install\oldInstallationPath('') === '');


// Part 16. placeStagedTree() puts back only a folder, & only where nothing has the name.
$dataPath = partFolder('p16/data');
makeInstallation($dataPath.'/Elsewhere', 'release fifteen');
if (!symlink(mustBeScratch($dataPath.'/Elsewhere'), mustBeScratch($dataPath.'/Application.old'))) testStop('A link could not be made at '.$dataPath.'/Application.old.');
list ($wasPlaced, $placeStatus) = runPlacement($dataPath.'/staged', $dataPath.'/Application', $dataPath.'/Application.old', array('rename' => renameHook(array($dataPath.'/staged'))));
check('a link where the installation waits is not put back', $wasPlaced === FALSE && $placeStatus === 'unrestored' && !file_exists($dataPath.'/Application') && !is_link($dataPath.'/Application')
  && is_link($dataPath.'/Application.old') && $TestCalls === array('rename '.$dataPath.'/staged -> '.$dataPath.'/Application (refused)'), json_encode(array($placeStatus, $TestCalls)));
$dataPath = partFolder('p16/occupied');
makeInstallation($dataPath.'/Application.old', 'release sixteen');
makeFolder($dataPath.'/Application', 0755);
list ($wasPlaced, $placeStatus) = runPlacement($dataPath.'/staged', $dataPath.'/Application', $dataPath.'/Application.old', array('rename' => renameHook(array($dataPath.'/staged'))));
check('nothing is put back over a name something has', $wasPlaced === FALSE && $placeStatus === 'unrestored' && markOf($dataPath.'/Application.old') === 'release sixteen' && namesIn($dataPath.'/Application') === array()
  && $TestCalls === array('rename '.$dataPath.'/staged -> '.$dataPath.'/Application (refused)'), json_encode(array($placeStatus, $TestCalls)));
list ($wasPlaced, $placeStatus) = runPlacement($dataPath.'/staged', $dataPath.'/Application', '', array('rename' => renameHook(array($dataPath.'/staged'))));
check('where nothing was moved aside, the word is failed', $wasPlaced === FALSE && $placeStatus === 'failed' && $TestLog === array(), json_encode(array($placeStatus, $TestLog)));

finish('test_install');
