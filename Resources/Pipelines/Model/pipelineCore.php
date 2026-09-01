<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRConvert2, Copyright on 8/17/2026 by Justin Grimes, www.github.com/zelon88
// /
// / LICENSE INFORMATION ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / APPLICATION INFORMATION ...
// / This application is designed to provide a web-interface for converting file formats on
// / a server for users of any web browser without authentication.
// /
// / FILE INFORMATION ...
// / v3.8.5.
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
// / A function to convert 3D model formats.
// / Two utilities cover this between them & neither covers it alone.
// / MeshLab performs triangulation & manifold normalization on engineering formats.
// / Assimp handles scene graphs, rigs & the web asset formats MeshLab cannot write.
// / A mesh format is therefore routed through MeshLab first & Assimp second, & a scene
// / format goes straight to Assimp.
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
  $ConversionSuccess = $ConversionErrors = $modelsValid =  $meshlabBinary = $assimpBinary = $readyToConvert = $meshlabCommand = $assimpCommand = $sandboxIsAvailable = FALSE;
  $returnData = $assimpData = $inputExt = $pyMeshLabDir = $intermediatePathname = $assimpInput = '';
  $meshlabOnly = $assimpSupported = array();
  $stopper = 0;
  $sleepTime = $SleepTimer;
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
    // / Engineering & CAD formats that need triangulation or manifold normalization first.
    $meshlabOnly = array('stl', 'ply', 'off', '3ds');
    // / Scene graphs, rigs & web assets that Assimp reads directly.
    $assimpSupported = array('fbx', 'gltf', 'glb', 'obj', 'dae', '3mf', 'x3d', 'dxf', 'bvh', 'ase');
    // / The bundled PyMeshLab workspace, used when the binary is not.
    $pyMeshLabDir = $InstLoc.$DirSep.'Resources'.$DirSep.'PyMeshLab';
    // / An intermediate file bridges the two utilities on the two stage route.
    $intermediatePathname = dirname($newPathname).$DirSep.'rectified_'.basename($newPathname).'.obj';
    // / This code will attempt the conversion up to $StopCounter number of times.
    while (!file_exists($newPathname) && $stopper <= $StopCounter) {
      // / If the last conversion attempt failed, wait a moment before trying again.
      if ($stopper !== 0) sleep($sleepTime++);
      // / Route 1. A mesh format is normalized by MeshLab before Assimp writes the output.
      if (in_array($inputExt, $meshlabOnly)) {
        if ($UsePyMeshLab) $meshlabCommand = 'python3 -c "import sys; sys.path.insert(0, '.escapeshellarg($pyMeshLabDir).'); import pymeshlab; ms = pymeshlab.MeshSet(); ms.load_new_mesh('.escapeshellarg($pathname).'); ms.save_current_mesh('.escapeshellarg($intermediatePathname).');"';
        else $meshlabCommand = 'xvfb-run -a /usr/bin/meshlabserver -i '.escapeshellarg($pathname).' -o '.escapeshellarg($intermediatePathname);
        list ($sandboxIsAvailable, $meshlabCommand) = sandboxCommand($meshlabCommand, $pathname, $intermediatePathname, FALSE, 'meshlab');
        if (!$sandboxIsAvailable) warningEntry('Bubblewrap is unavailable. A model conversion ran unsandboxed.');
        $returnData = shell_exec($meshlabCommand);
        // / If the first stage produced nothing, hand Assimp the original rather than nothing.
        $assimpInput = file_exists($intermediatePathname) ? $intermediatePathname : $pathname;
        $assimpCommand = escapeshellarg($assimpBinary).' export '.escapeshellarg($assimpInput).' '.escapeshellarg($newPathname);
        list ($sandboxIsAvailable, $assimpCommand) = sandboxCommand($assimpCommand, $assimpInput, $newPathname, FALSE, 'meshlab');
        $assimpData = shell_exec($assimpCommand); }
      // / Route 2. A scene format goes straight to Assimp & bypasses MeshLab entirely.
      else if (in_array($inputExt, $assimpSupported)) {
        $assimpCommand = escapeshellarg($assimpBinary).' export '.escapeshellarg($pathname).' '.escapeshellarg($newPathname);
        list ($sandboxIsAvailable, $assimpCommand) = sandboxCommand($assimpCommand, $pathname, $newPathname, FALSE, 'meshlab');
        if (!$sandboxIsAvailable) warningEntry('Bubblewrap is unavailable. A model conversion ran unsandboxed.');
        $assimpData = shell_exec($assimpCommand); }
      // / Route 3. An unrecognized extension is attempted with MeshLab alone.
      else {
        if ($UsePyMeshLab) $meshlabCommand = 'python3 -c "import sys; sys.path.insert(0, '.escapeshellarg($pyMeshLabDir).'); import pymeshlab; ms = pymeshlab.MeshSet(); ms.load_new_mesh('.escapeshellarg($pathname).'); ms.save_current_mesh('.escapeshellarg($newPathname).');"';
        else $meshlabCommand = 'xvfb-run -a '.escapeshellarg($meshlabBinary).' -i '.escapeshellarg($pathname).' -o '.escapeshellarg($newPathname);
        list ($sandboxIsAvailable, $meshlabCommand) = sandboxCommand($meshlabCommand, $pathname, $newPathname, FALSE, 'meshlab');
        if (!$sandboxIsAvailable) warningEntry('Bubblewrap is unavailable. A model conversion ran unsandboxed.');
        $returnData = shell_exec($meshlabCommand); }
      // / Count the number of conversions to avoid infinite loops.
      $stopper++;
      // / Stop attempting the conversion after $StopCounter number of attempts.
      if ($stopper === $StopCounter) {
        $ConversionErrors = TRUE;
        errorEntry('The model converter timed out!', 9000, FALSE); } }
    // / Log the output of each utility to the logfile, if it is not blank.
    if ($Verbose && trim($returnData) !== '') logEntry('Meshlab processing engine returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
    if ($Verbose && trim($assimpData) !== '') logEntry('Assimp returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($assimpData)))));
    // / Erase the intermediate file so a two stage conversion leaves nothing behind.
    if (file_exists($intermediatePathname)) @unlink($intermediatePathname);
    // / The output file is the only verdict on whether the conversion produced anything.
    if (file_exists($newPathname)) $ConversionSuccess = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $returnData, $assimpData, $stopper, $pathname, $intermediatePathname, $assimpInput, $inputExt, $meshlabOnly, $assimpSupported, $pyMeshLabDir, $sleepTime, $modelsValid, $readyToConvert, $meshlabCommand, $assimpCommand, $sandboxIsAvailable, $meshlabBinary, $assimpBinary);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $OutputFilename, $WorkerPID); }
// / -----------------------------------------------------------------------------------

?>
