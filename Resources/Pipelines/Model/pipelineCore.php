<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRConvert2, Copyright on 8/17/2026 by Justin Grimes, www.github.com/zelon88
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
// / v3.8.6.
// / This file is the converter for the Model pipeline. It is loaded by pipelineManager.php
// / ONLY when a Model conversion is about to be dispatched to it, so a request that
// / converts something else never parses a line of it.
// / Error block 20000 through 20003 belongs to this pipeline. Those numbers came with the code when it
// / moved out of convertCore.php & they did not change, because operators have read them.
// / This pipeline calls verifyModelVersions() & sandboxCommand(), which remain in
// / convertCore.php. A dependency verifier is core owned, because showVersionInfo()
// / reports on it whether or not this pipeline is installed.
// / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt for the contracts this file obeys.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline converter cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to build the Assimp export command for one conversion.
// / Accepts the binary, the input path, the output path & the target extension, in order.
// / Returns the command as a string.
// / Assimp is told the target format explicitly rather than being left to guess it.
// / Guessing is what failed on an obj to off conversion, & the message it produced named
// / neither the format nor the file.
// / An obj target asks for objnomtl instead of obj, & that is deliberate.
// / The obj exporter writes a material library beside the model as a second file. The user
// / asked for one file & the interface only ever shows one, so the second sat in the
// / session directory counted but invisible. objnomtl produces a single self contained obj
// / with no dangling reference to a material library that was never delivered.
function buildAssimpCommand($assimpBinary, $inputPath, $outputPath, $targetExtension) {
  // / Set variables.
  global $EnableMemoryProtection;
  $AssimpCommand = '';
  $formatIdentifier = '';
  $formatIdentifier = strtolower(trim((string)$targetExtension, '.'));
  if ($formatIdentifier === 'obj') $formatIdentifier = 'objnomtl';
  $AssimpCommand = escapeshellarg($assimpBinary).' export '.escapeshellarg($inputPath).' '.escapeshellarg($outputPath).' -f'.escapeshellarg($formatIdentifier);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $formatIdentifier, $assimpBinary, $inputPath, $outputPath, $targetExtension);
  return $AssimpCommand; }
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to build the MeshLab command for one conversion.
// / Accepts the PyMeshLab toggle, the PyMeshLab directory, the binary, the input path &
// / the output path, in that order. Returns the command as a string.
// / MeshLab is reachable two ways & the caller should not have to know which is in use.
// / The bundled PyMeshLab module needs no display server.
// / The meshlabserver binary does, so it is run under xvfb-run.
// / Both decide the output format from the extension of the path they are given.
function buildMeshLabCommand($usePyMeshLab, $pyMeshLabDir, $meshlabBinary, $inputPath, $outputPath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $MeshLabCommand = '';
  if ($usePyMeshLab) $MeshLabCommand = 'python3 -c "import sys; sys.path.insert(0, '.escapeshellarg($pyMeshLabDir).'); import pymeshlab; ms = pymeshlab.MeshSet(); ms.load_new_mesh('.escapeshellarg($inputPath).'); ms.save_current_mesh('.escapeshellarg($outputPath).');"';
  else $MeshLabCommand = 'xvfb-run -a '.escapeshellarg($meshlabBinary).' -i '.escapeshellarg($inputPath).' -o '.escapeshellarg($outputPath);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $usePyMeshLab, $pyMeshLabDir, $meshlabBinary, $inputPath, $outputPath);
  return $MeshLabCommand; }
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to convert 3D model formats.
// / Two utilities cover this between them & neither covers it alone.
// / MeshLab performs triangulation & manifold normalization on engineering formats.
// / Assimp handles scene graphs, rigs & the web asset formats MeshLab cannot write.
// / The route is chosen by what each utility can WRITE, never by what it can read.
// / Assimp reads OFF & cannot write it, so an input based route sent obj to off conversions
// / to a utility with no OFF exporter & retried the same impossible command until timeout.
// / Four routes exist. Assimp alone, MeshLab alone, MeshLab normalizing before Assimp
// / writes, & Assimp bridging an input MeshLab cannot read before MeshLab writes.
// / A target neither utility can write is refused before the retry loop is entered.
// / MeshLab is reachable two ways. The bundled PyMeshLab module needs no display server.
// / The meshlabserver binary does, so it is run under xvfb-run.
// / PyMeshLab bypasses the meshlabserver binary entirely, so its version cannot be read &
// / is not checked. Assimp is checked on every path, because every path uses it.
function convertModels($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer, $MinimumAssimpVersion, $MinimumMeshlabVersion, $UsePyMeshLab, $InstLoc, $DirSep, $EnableMemoryProtection;
  // / The six value pipeline contract. Success, errors, path, extension, filename & PID.
  // / This converter produces neither of the last two itself, so it declares the defaults.
  // / $OutputFilename is the name the user is shown. $WorkerPID stays zero unless a
  // / detached process must be supervised after the connection to the user is closed.
  $OutputFilename = basename($newPathname);
  $WorkerPID = 0;
  $ConversionSuccess = $ConversionErrors = $modelsValid =  $meshlabBinary = $assimpBinary = $readyToConvert = $meshlabCommand = $assimpCommand = $commandMayRun = FALSE;
  $returnData = $assimpData = $inputExt = $pyMeshLabDir = $intermediatePathname = $assimpInput = '';
  $meshlabOnly = $assimpSupported = array();
  $stopper = 0;
  $sleepTime = $SleepTimer;
  $outputExt = $conversionRoute = '';
  $assimpCanWrite = $meshlabCanWrite = $meshlabCanRead = array();
  // / Detect the installed versions of Assimp & MeshLab.
  list ($modelsValid, $assimpBinary, $meshlabBinary) = verifyModelVersions($MinimumAssimpVersion, $MinimumMeshlabVersion);
  // / Assimp is used by every route, so it is required unconditionally.
  if ($assimpBinary === FALSE) {
    $ConversionErrors = TRUE;
    errorEntry('The installed Assimp version is missing, unidentifiable, or too old!', 9001, FALSE); }
  // / MeshLab is only required when the binary is the one being used.
  else if (!$UsePyMeshLab && $meshlabBinary === FALSE) {
    $ConversionErrors = TRUE;
    errorEntry('The installed MeshLab version is missing, unidentifiable, or too old!', 9002, FALSE); }
  else $readyToConvert = TRUE;
  if ($readyToConvert) {
    if ($Verbose) logEntry('Converting model.');
    // / Isolate the input extension to route the model through the proper utility.
    $inputExt = strtolower(pathinfo($pathname, PATHINFO_EXTENSION));
    $outputExt = strtolower(trim((string)$extension, '.'));
    // / The route is chosen by what each utility can WRITE, not by what it can read.
    // / Assimp reads OFF perfectly well & has no OFF exporter at all, so routing an obj to
    // / off through Assimp produced 'no output format specified and I failed to guess it'
    // / on every one of the retries, then a timeout that named nothing useful.
    // / The formats each utility can produce are therefore stated here & consulted first.
    $assimpCanWrite = array('obj', 'stl', 'ply', '3ds', 'dae', 'x3d', 'fbx', 'gltf', 'glb', '3mf', 'x', 'assbin');
    $meshlabCanWrite = array('ply', 'stl', 'obj', 'off', 'dae', 'x3d', 'dxf', 'wrl', 'ctm', 'u3d');
    // / What MeshLab can open. Assimp reads considerably more, so it is not listed.
    $meshlabCanRead = array('ply', 'stl', 'obj', 'off', '3ds', 'dae', 'x3d', 'dxf', 'wrl', 'ctm', 'ptx', 'xyz', 'gts', 'asc');
    // / Engineering & CAD formats that benefit from triangulation before Assimp writes.
    $meshlabOnly = array('stl', 'ply', 'off', '3ds');
    // / The bundled PyMeshLab workspace, used when the binary is not.
    $pyMeshLabDir = $InstLoc.$DirSep.'Resources'.$DirSep.'PyMeshLab';
    // / An intermediate file bridges the two utilities on any two stage route.
    $intermediatePathname = dirname($newPathname).$DirSep.'rectified_'.basename($newPathname).'.obj';
    // / Decide the route once, before any attempt is made.
    // / A format neither utility can write is refused here rather than inside the retry
    // / loop. Retrying a command that cannot succeed only delays the same failure.
    if (in_array($outputExt, $assimpCanWrite, TRUE)) $conversionRoute = in_array($inputExt, $meshlabOnly, TRUE) ? 'normalize' : 'assimp';
    else if (in_array($outputExt, $meshlabCanWrite, TRUE)) $conversionRoute = in_array($inputExt, $meshlabCanRead, TRUE) ? 'meshlab' : 'bridge';
    else {
      $ConversionErrors = TRUE;
      errorEntry('Neither Assimp nor MeshLab can write the '.$outputExt.' format!', 9004, FALSE); }
    if ($conversionRoute !== '' && $Verbose) logEntry('Model route selected: '.$conversionRoute.', '.$inputExt.' to '.$outputExt.'.');
    // / This code will attempt the conversion up to $StopCounter number of times.
    while ($conversionRoute !== '' && !file_exists($newPathname) && $stopper <= $StopCounter) {
      // / If the last conversion attempt failed, wait a moment before trying again.
      if ($stopper !== 0) sleep($sleepTime++);
      // / Route normalize. MeshLab triangulates the mesh & Assimp writes the output.
      if ($conversionRoute === 'normalize' or $conversionRoute === 'bridge') {
        // / The bridge route runs the two utilities the other way round, so the first stage
        // / differs between them & the second stage is chosen below.
        if ($conversionRoute === 'normalize') {
          $meshlabCommand = buildMeshLabCommand($UsePyMeshLab, $pyMeshLabDir, $meshlabBinary, $pathname, $intermediatePathname);
          list ($commandMayRun, $meshlabCommand) = sandboxCommand($meshlabCommand, $pathname, $intermediatePathname, FALSE, 'meshlab');
          if (!$commandMayRun) {
            $ConversionErrors = TRUE;
            errorEntry('Bubblewrap is missing or non functional, so this model conversion cannot be isolated!', 9003, FALSE);
            break; }
          $returnData = shell_exec($meshlabCommand); }
        else {
          $assimpCommand = buildAssimpCommand($assimpBinary, $pathname, $intermediatePathname, 'obj');
          list ($commandMayRun, $assimpCommand) = sandboxCommand($assimpCommand, $pathname, $intermediatePathname, FALSE, 'meshlab');
          if (!$commandMayRun) {
            $ConversionErrors = TRUE;
            errorEntry('Bubblewrap is missing or non functional, so this model conversion cannot be isolated!', 9003, FALSE);
            break; }
          $assimpData = shell_exec($assimpCommand); }
        // / If the first stage produced nothing, hand the second the original rather than nothing.
        $assimpInput = file_exists($intermediatePathname) ? $intermediatePathname : $pathname;
        // / The second stage is sandboxed separately & is judged separately.
        // / A first stage that ran does not entitle the second one to skip the check.
        if ($conversionRoute === 'normalize') {
          $assimpCommand = buildAssimpCommand($assimpBinary, $assimpInput, $newPathname, $outputExt);
          list ($commandMayRun, $assimpCommand) = sandboxCommand($assimpCommand, $assimpInput, $newPathname, FALSE, 'meshlab');
          if (!$commandMayRun) {
            $ConversionErrors = TRUE;
            errorEntry('Bubblewrap is missing or non functional, so this model conversion cannot be isolated!', 9003, FALSE);
            break; }
          $assimpData = shell_exec($assimpCommand); }
        else {
          $meshlabCommand = buildMeshLabCommand($UsePyMeshLab, $pyMeshLabDir, $meshlabBinary, $assimpInput, $newPathname);
          list ($commandMayRun, $meshlabCommand) = sandboxCommand($meshlabCommand, $assimpInput, $newPathname, FALSE, 'meshlab');
          if (!$commandMayRun) {
            $ConversionErrors = TRUE;
            errorEntry('Bubblewrap is missing or non functional, so this model conversion cannot be isolated!', 9003, FALSE);
            break; }
          $returnData = shell_exec($meshlabCommand); } }
      // / Route assimp. A scene format Assimp can both read & write goes straight to it.
      else if ($conversionRoute === 'assimp') {
        $assimpCommand = buildAssimpCommand($assimpBinary, $pathname, $newPathname, $outputExt);
        list ($commandMayRun, $assimpCommand) = sandboxCommand($assimpCommand, $pathname, $newPathname, FALSE, 'meshlab');
        if (!$commandMayRun) {
          $ConversionErrors = TRUE;
          errorEntry('Bubblewrap is missing or non functional, so this model conversion cannot be isolated!', 9003, FALSE);
          break; }
        $assimpData = shell_exec($assimpCommand); }
      // / Route meshlab. MeshLab reads the input & writes a format Assimp cannot produce.
      else {
        $meshlabCommand = buildMeshLabCommand($UsePyMeshLab, $pyMeshLabDir, $meshlabBinary, $pathname, $newPathname);
        list ($commandMayRun, $meshlabCommand) = sandboxCommand($meshlabCommand, $pathname, $newPathname, FALSE, 'meshlab');
        if (!$commandMayRun) {
          $ConversionErrors = TRUE;
          errorEntry('Bubblewrap is missing or non functional, so this model conversion cannot be isolated!', 9003, FALSE);
          break; }
        $returnData = shell_exec($meshlabCommand); }
      // / Count the number of conversions to avoid infinite loops.
      $stopper++;
      // / Stop attempting the conversion after $StopCounter number of attempts.
      // / The loop is left immediately, because an attempt that has already been counted as
      // / the last one must not run again after the timeout has been reported.
      if ($stopper >= $StopCounter) {
        $ConversionErrors = TRUE;
        errorEntry('The model converter timed out!', 9000, FALSE);
        break; } }
    // / Log the output of each utility to the logfile, if it is not blank.
    if ($Verbose && trim($returnData) !== '') logEntry('Meshlab processing engine returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
    if ($Verbose && trim($assimpData) !== '') logEntry('Assimp returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($assimpData)))));
    // / Erase the intermediate file so a two stage conversion leaves nothing behind.
    if (file_exists($intermediatePathname)) @unlink($intermediatePathname);
    // / Erase the material sidecar Assimp writes beside an intermediate obj.
    // / The final output never has one, because the obj exporter is asked for objnomtl.
    if (file_exists($intermediatePathname.'.mtl')) @unlink($intermediatePathname.'.mtl');
    if (file_exists(substr($intermediatePathname, 0, -4).'.mtl')) @unlink(substr($intermediatePathname, 0, -4).'.mtl');

    // / The output file is the only verdict on whether the conversion produced anything.
    if (file_exists($newPathname)) $ConversionSuccess = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $returnData, $assimpData, $stopper, $pathname, $intermediatePathname, $assimpInput, $inputExt, $outputExt, $conversionRoute, $meshlabOnly, $assimpCanWrite, $meshlabCanWrite, $meshlabCanRead, $pyMeshLabDir, $sleepTime, $modelsValid, $readyToConvert, $meshlabCommand, $assimpCommand, $commandMayRun, $meshlabBinary, $assimpBinary);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $OutputFilename, $WorkerPID); }
// / -----------------------------------------------------------------------------------

?>
