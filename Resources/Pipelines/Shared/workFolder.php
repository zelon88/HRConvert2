<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRConvert2, Copyright on 11/16/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / Application Information ...
// / This application is designed to provide a web-interface for converting file formats on
// / a server for users of any web browser without authentication.
// /
// / File Information ...
// / v4.2.5.
// / The work folder shared module, which is not a pipeline & converts nothing on its own.
// / It gives one conversion a folder of its own beside the staged input, runs a command in it, & takes it away again.
// / The Data, LaTeX & Trace pipelines each need one, for what a tool writes on the way to the output.
// / A command run in it finds its files by name alone, which LaTeX needs, because paranoid TeX opens a file named by its name & nothing else.
// / A user's own folder never sees those files, & two conversions never share one.
// / It is version pinned exactly as a pipeline is, by the table in Resources/Contract/app-engine-config.php.
// / It is loaded once per request, before the first pipeline that declared it.
// / It raises no error of its own. A pipeline that cannot have a folder raises its own, because it knows what it was for.
// / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt for the contracts this file obeys.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline component cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The version of this shared module. Read without executing this file, then matched
// / exactly against the pin in getAcceptedSharedModules().
$SharedModuleVersion = 'v4.2.5';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to make a folder for one conversion beside the file it converts.
// / Accepts the staged input's path & a short word naming what the folder is for, in that order.
// / Returns whether the folder was made & its path, in that order. The path is empty where none was made.
// / The folder is new, only the account running the conversion can open it, & its name begins with a dot & ends in sixteen random hex digits.
// / A name already taken is never reused, because mkdir refuses one, so nothing another conversion left is ever written into.
// / The staged input is in the temporary location, so a user's own folder never holds the work.
function makeWorkFolder($stagedPath, $workPurpose) {
  // / Set variables.
  global $DirSep, $EnableMemoryProtection;
  $FolderWasMade = FALSE;
  $WorkFolder = '';
  $candidateFolder = $cleanPurpose = '';
  $cleanPurpose = preg_replace('/[^a-z]/', '', strtolower((string)$workPurpose));
  if ($cleanPurpose === '') $cleanPurpose = 'work';
  $candidateFolder = dirname((string)$stagedPath).$DirSep.'.'.$cleanPurpose.'-'.bin2hex(random_bytes(8));
  if (is_dir(dirname((string)$stagedPath)) && @mkdir($candidateFolder, 0700)) {
    $FolderWasMade = TRUE;
    $WorkFolder = $candidateFolder; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $candidateFolder, $cleanPurpose, $stagedPath, $workPurpose);
  return array($FolderWasMade, $WorkFolder); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run a command in a work folder & keep what it says.
// / Accepts the finished command & the work folder, in that order. Returns the exit status & what the command printed, in that order.
// / The exit status is -1 where the command could not be started, which is warned about, because PHP may have proc_open disabled.
// / What the command printed on either stream is kept up to 65536 bytes, & read to the end, so the command never stalls writing it.
// / A sandbox runs a command in /work, which is the work folder, & where no sandbox could be built the command runs in the work folder
// / itself, so a command that names its files by name alone finds them either way.
function runInWorkFolder($commandToRun, $workFolder) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ExitStatus = -1;
  $CommandOutput = '';
  $processHandle = FALSE;
  $processPipes = array();
  $outputChunk = '';
  // / Standard error is the same pipe as standard output, so what every part of the command says on either stream is kept.
  $processHandle = @proc_open($commandToRun, array(0 => array('file', '/dev/null', 'r'), 1 => array('pipe', 'w'), 2 => array('redirect', 1)), $processPipes, $workFolder);
  if (!is_resource($processHandle)) warningEntry('A command could not be started in the work folder '.basename((string)$workFolder).'. PHP may have proc_open disabled.');
  else {
    while (isset($processPipes[1]) && is_resource($processPipes[1]) && !feof($processPipes[1])) {
      $outputChunk = (string)fread($processPipes[1], 8192);
      if (strlen($CommandOutput) < 65536) $CommandOutput = $CommandOutput.substr($outputChunk, 0, 65536 - strlen($CommandOutput)); }
    if (isset($processPipes[1]) && is_resource($processPipes[1])) fclose($processPipes[1]);
    $ExitStatus = (int)proc_close($processHandle); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $processHandle, $processPipes, $outputChunk, $commandToRun, $workFolder);
  return array($ExitStatus, $CommandOutput); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to take away a folder makeWorkFolder() made, & everything in it.
// / Accepts the folder's path. Returns whether nothing is left at that path.
// / The Engine's cleanFiles() does the removal, & is held to the folder that holds this one, so a path it is handed cannot reach anything else.
// / A folder that is already gone counts as removed. One that could not be removed is warned about, & the sweep takes it later.
function removeWorkFolder($workFolder) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FolderWasRemoved = FALSE;
  $cleanerWasAsked = FALSE;
  clearstatcache(TRUE, (string)$workFolder);
  if ((string)$workFolder !== '' && is_dir((string)$workFolder) && !is_link((string)$workFolder)) {
    $cleanerWasAsked = TRUE;
    cleanFiles((string)$workFolder, array(dirname((string)$workFolder))); }
  clearstatcache(TRUE, (string)$workFolder);
  $FolderWasRemoved = (!file_exists((string)$workFolder) && !is_link((string)$workFolder));
  if ($cleanerWasAsked && !$FolderWasRemoved) warningEntry('The work folder '.basename((string)$workFolder).' could not be removed, & the sweep will take it away later.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cleanerWasAsked, $workFolder);
  return $FolderWasRemoved; }
// / -----------------------------------------------------------------------------------

?>
