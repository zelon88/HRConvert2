<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRConvert2, Copyright on 9/22/2026 by Justin Grimes, www.github.com/zelon88
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
// / v4.0.8.
// / The Model pipeline's converter. pipelineCore.php loads it only to dispatch a Model conversion.
// / Error block 9000 through 9004 belongs to this pipeline.
// / Assimp & MeshLab come from verifiedToolPath().
// / MeshLab is reached through PyMeshLab when an interpreter can import it, & otherwise through meshlabserver.
// / The bundled PyMeshLab at Resources/PyMeshLab is a compiled extension tagged for one interpreter, such as cpython-314.
// / No other interpreter version loads it, & renaming the object does not help.
// / resolvePyMeshLabInterpreter() reads the tag, so a bundle built for another interpreter needs no code change.
// / PyMeshLab's wheels need glibc 2.35 or newer, so an older host has meshlabserver alone.
// / The system python3 must never be replaced to satisfy this, though another interpreter may be installed beside it.
// / The bundle is kept, because a current distribution ships no meshlabserver.
// / meshlabserver initializes GLEW at startup, so even a conversion needs OpenGL, which xvfb-run supplies.
// / The meshlab sandbox profile lets Xvfb run in a namespace, & comments each of its settings.
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
// / Assimp is told the target format, because a guessed format fails with a message naming neither it nor the file.
// / An obj target asks for objnomtl, one self contained obj.
// / The obj exporter writes a material library as a second file, which nobody sees & nothing cleans.
function buildAssimpCommand($assimpBinary, $inputPath, $outputPath, $targetExtension) {
  // / Set variables.
  global $EnableMemoryProtection;
  $AssimpCommand = '';
  $formatIdentifier = '';
  $formatIdentifier = strtolower(trim((string)$targetExtension, '.'));
  // / An extension is not always the identifier Assimp knows the format by.
  // / obj becomes objnomtl, which writes the geometry & no material sidecar.
  // / json becomes assjson, the name the exporter is registered as.
  if ($formatIdentifier === 'obj') $formatIdentifier = 'objnomtl';
  if ($formatIdentifier === 'json') $formatIdentifier = 'assjson';
  // / Standard error is captured. A converter whose failure goes to a stream nothing reads
  // / produces a timeout & no explanation, which is exactly what an unreadable PyMeshLab
  // / traceback did. Every retry then repeated a command nobody could see failing.
  $AssimpCommand = escapeshellarg($assimpBinary).' export '.escapeshellarg($inputPath).' '.escapeshellarg($outputPath).' -f'.escapeshellarg($formatIdentifier).' 2>&1';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $formatIdentifier, $assimpBinary, $inputPath, $outputPath, $targetExtension);
  return $AssimpCommand; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to find the interpreter that can actually load PyMeshLab.
// / Accepts the bundle directory.
// / Returns the interpreter name or an empty string, & whether PyMeshLab is installed rather than bundled.
// / A compiled extension loads under one Python minor version only, which the bundle names in its object.
// / An interpreter that cannot load the object reports the module absent, which reads as a path problem.
// / An empty name means no interpreter here can load it, & the caller falls back to meshlabserver.
function resolvePyMeshLabInterpreter($pyMeshLabDir) {
  // / Set variables.
  global $EnableMemoryProtection;
  $InterpreterName = '';
  $PyMeshLabIsInstalled = FALSE;
  $objectPaths = $tagMatches = array();
  $candidateName = $objectPath = $probeCommand = '';
  $probeOutput = array();
  $probeExitCode = 1;
  // / An installed PyMeshLab is tried first, & a bundled one second.
  // / An installed wheel places its own libraries where python looks, so it needs no path & no Qt environment.
  // / The bundle is the fallback, so an installation that has one keeps working.
  // / The probe proves the import, not the presence of a file.
  foreach (array('python3.14', 'python3') as $candidateName) {
    if (locateDependency($candidateName) === '') continue;
    $probeOutput = array();
    $probeExitCode = 1;
    exec('QT_QPA_PLATFORM=offscreen '.escapeshellarg($candidateName).' -c '.escapeshellarg('import pymeshlab').' 2>&1', $probeOutput, $probeExitCode);
    if ($probeExitCode !== 0) continue;
    $InterpreterName = $candidateName;
    $PyMeshLabIsInstalled = TRUE;
    break; }
  // / Only look for a bundle when nothing is installed.
  if ($InterpreterName === '') {
  $objectPaths = glob(rtrim((string)$pyMeshLabDir, '/').'/pymeshlab/*.cpython-*.so');
  if (is_array($objectPaths)) {
    foreach ($objectPaths as $objectPath) {
      if (!preg_match('/cpython-(\\d)(\\d+)/', basename($objectPath), $tagMatches)) continue;
      // / cpython-314 means python3.14. The tag carries no dot & the name does.
      $candidateName = 'python'.$tagMatches[1].'.'.$tagMatches[2];
      if (locateDependency($candidateName) === '') continue;
      // / An interpreter existing is not the same as the module loading, which is what this tests.
      // / The import is attempted once, with the environment the conversion will use.
      $probeCommand = 'QT_QPA_PLATFORM=offscreen QT_PLUGIN_PATH='.escapeshellarg(rtrim((string)$pyMeshLabDir, '/').'/pymeshlab/lib/plugins')
        .' LD_LIBRARY_PATH='.escapeshellarg(rtrim((string)$pyMeshLabDir, '/').'/pymeshlab/lib')
        .' '.escapeshellarg($candidateName).' -c '.escapeshellarg('import sys; sys.path.insert(0, '.var_export((string)$pyMeshLabDir, TRUE).'); import pymeshlab').' 2>&1';
      $probeOutput = array();
      $probeExitCode = 1;
      exec($probeCommand, $probeOutput, $probeExitCode);
      if ($probeExitCode === 0) {
        $InterpreterName = $candidateName;
        break; }
      warningEntry('PyMeshLab was found & could not be imported by '.$candidateName.'. It reported: '.trim(implode(' ', array_slice($probeOutput, -2))));
      break; } }
  // / Closes the bundle search opened above.
  }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $probeCommand, $probeOutput, $probeExitCode, $objectPaths, $tagMatches, $candidateName, $objectPath, $pyMeshLabDir);
  return array($InterpreterName, $PyMeshLabIsInstalled); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to build the MeshLab command for one conversion.
// / Accepts the PyMeshLab toggle, the PyMeshLab directory, the binary, the input path & the output path.
// / Returns the command & the environment it needs, in that order.
// / The bundled PyMeshLab needs no display server, & meshlabserver runs under xvfb-run.
// / Both decide the output format from the extension of the path they are given.
// / The environment is returned, not prefixed, because bwrap execs without a shell & would run NAME=value.
// / sandboxCommand() turns it into --setenv, or into a prefix when it cannot sandbox.
function buildMeshLabCommand($usePyMeshLab, $pyMeshLabDir, $meshlabBinary, $inputPath, $outputPath, $interpreterName) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $MeshLabCommand = $pythonPrologue = '';
  $CommandEnvironment = array();
  if ($usePyMeshLab) {
    // / The bundled directory goes on the Python path only when it exists.
    // / A pip installed PyMeshLab lives under /usr, which the sandbox already binds.
    // / The bundle holds a pymeshlab package, so its parent goes on the path.
    // / Its shared objects under pymeshlab/lib go on the loader's path, or the extension fails to load.
    // / The escaped directory is what sandboxCommand() rewrites, so the suffix is appended outside it.
    // / It survives the rewrite as /res/pymeshlab/lib.
    if (is_dir((string)$pyMeshLabDir)) {
      $pythonPrologue = 'import sys; sys.path.insert(0, '.escapeshellarg($pyMeshLabDir).'); ';
      $CommandEnvironment['LD_LIBRARY_PATH'] = rtrim((string)$pyMeshLabDir, '/').'/pymeshlab/lib';
      // / Qt renders offscreen & is told where to find the plugin that does it.
      // / QT_QPA_PLATFORM names which plugin to load & QT_PLUGIN_PATH is what says where to
      // / look. Without the second, Qt searches the system path & reports the module as
      // / failing to initialize, which names neither the plugin nor the path.
      // / Both values name the bundle directory, so sandboxCommand rewrites them to the path
      // / inside the namespace along with everything else that names it.
      // / See the PyMeshLab entry in depends.php. That platforms directory was added by hand
      // / & no wheel ships one, so a bundle refresh will drop it & break this route.
      $CommandEnvironment['QT_PLUGIN_PATH'] = rtrim((string)$pyMeshLabDir, '/').'/pymeshlab/lib/plugins';
      $CommandEnvironment['QT_QPA_PLATFORM'] = 'offscreen'; }
    $MeshLabCommand = escapeshellarg((string)$interpreterName).' -c "'.$pythonPrologue.'import pymeshlab; ms = pymeshlab.MeshSet(); ms.load_new_mesh('.escapeshellarg($inputPath).'); ms.save_current_mesh('.escapeshellarg($outputPath).');" 2>&1'; }
  // / meshlabserver initializes GLEW at startup, so even a format conversion needs an OpenGL context.
  // / The offscreen platform plugin supplies none, & an X server supplies GLX, which is why xvfb-run is here.
  // / The server does not listen on TCP, because the sandbox leaves loopback down & a failed bind kills the display.
  // / -e names a file for the X server's own error output, which xvfb-run otherwise discards.
  // / It is attached only under verbose logging, because a working server prints harmless keysym warnings.
  else $MeshLabCommand = 'xvfb-run -a'.($Verbose ? ' -e /dev/stderr' : '').' -s '.escapeshellarg('-screen 0 1280x1024x24 -nolisten tcp').' '.escapeshellarg($meshlabBinary).' -i '.escapeshellarg($inputPath).' -o '.escapeshellarg($outputPath).' 2>&1';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $pythonPrologue, $usePyMeshLab, $pyMeshLabDir, $meshlabBinary, $inputPath, $outputPath, $interpreterName);
  return array($MeshLabCommand, $CommandEnvironment); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert 3D model formats.
// / MeshLab triangulates & normalizes engineering formats.
// / Assimp handles scene graphs, rigs & the web asset formats MeshLab cannot write.
// / The route is chosen by what each utility can write, because Assimp reads formats it cannot write.
// / Four routes exist: Assimp alone, MeshLab alone, MeshLab before Assimp, & Assimp before MeshLab.
// / A target neither utility can write is refused before the retry loop.
// / MeshLab's version cannot be read through PyMeshLab, so it is checked on the meshlabserver route only.
// / Assimp is checked on every route, because every route uses it.
function convertModels($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer, $UsePyMeshLab, $InstLoc, $DirSep, $EnableMemoryProtection;
  // / The six value pipeline contract. Success, errors, path, extension, filename & PID.
  // / The last two keep their defaults, & $WorkerPID stays 0 unless a detached process is supervised.
  $OutputFilename = basename($newPathname);
  $WorkerPID = 0;
  $ConversionSuccess = $ConversionErrors = $modelsValid =  $meshlabBinary = $assimpBinary = $readyToConvert = $meshlabCommand = $assimpCommand = $commandMayRun = FALSE;
  $returnData = $assimpData = $inputExt = $pyMeshLabDir = $intermediatePathname = $assimpInput = '';
  $meshlabOnly = array();
  $stopper = 0;
  $sleepTime = $SleepTimer;
  $outputExt = $conversionRoute = $pyMeshLabInterpreter = $sidecarPathname = '';
  $meshlabEnvironment = array();
  $pyMeshLabInUse = $pyMeshLabIsInstalled = FALSE;
  $assimpCanWrite = $meshlabCanWrite = $meshlabCanRead = array();
  // / Detect the installed versions of Assimp & MeshLab.
  // / Each tool by its manifest name. PyMeshLab stands in for MeshLab when it is enabled, so
  // / MeshLab is required only when PyMeshLab is not. This matches the check it replaces.
  $assimpBinary = verifiedToolPath('Assimp');
  $meshlabBinary = verifiedToolPath('MeshLab');
  $modelsValid = ($assimpBinary !== FALSE && ($UsePyMeshLab or $meshlabBinary !== FALSE));
  // / Kept from the check this replaces: with PyMeshLab on & MeshLab absent there is no fallback.
  if ($UsePyMeshLab && $meshlabBinary === FALSE) warningEntry('PyMeshLab is enabled & no usable MeshLab binary was found. A model conversion has no fallback if the bundle does not load.');
  // / Assimp is used by every route, so it is required unconditionally.
  if ($assimpBinary === FALSE) {
    $ConversionErrors = TRUE;
    errorEntry('The installed Assimp version is missing, unidentifiable, or too old!', 9001, FALSE); }
  // / MeshLab is not tested here & that is deliberate.
  // / Whether the binary is needed depends on whether PyMeshLab actually loads.
  // / That is not known until the import is attempted a few lines below.
  else $readyToConvert = TRUE;
  if ($readyToConvert) {
    if ($Verbose) logEntry('Converting model.');
    // / Isolate the input extension to route the model through the proper utility.
    $inputExt = strtolower(pathinfo($pathname, PATHINFO_EXTENSION));
    $outputExt = strtolower(trim((string)$extension, '.'));
    // / The route is chosen by what each utility can write, not by what it can read.
    // / Assimp reads OFF & has no OFF exporter.
    // / The formats each utility can produce are therefore stated here & consulted first.
    // / These are extensions rather than Assimp identifiers. buildAssimpCommand maps the
    // / two where they differ, & a format missing from here is refused before it is tried.
    $assimpCanWrite = array('obj', 'stl', 'ply', '3ds', 'dae', 'x3d', 'fbx', 'gltf', 'glb', '3mf', 'x', 'assbin', 'json');
    $meshlabCanWrite = array('ply', 'stl', 'obj', 'off', 'dae', 'x3d', 'dxf', 'wrl', 'ctm', 'u3d');
    // / What MeshLab can open. Assimp reads considerably more, so it is not listed.
    $meshlabCanRead = array('ply', 'stl', 'obj', 'off', '3ds', 'dae', 'x3d', 'dxf', 'wrl', 'ctm', 'ptx', 'xyz', 'gts', 'asc');
    // / Inputs passed through MeshLab before Assimp writes the output.
    // / Assimp reads every one of them, but MeshLab triangulates them the way a consumer expects.
    $meshlabOnly = array('stl', 'ply', 'off', '3ds');
    // / The bundled PyMeshLab workspace, used when the binary is not.
    // / This directory is handed to sandboxCommand() as a read only resource, because the
    // / namespace binds the input & the output & nothing else. Without that bind the Python
    // / path points at a directory which does not exist inside the sandbox, import fails, &
    // / every MeshLab route fails with it. An Assimp command passes nothing, because Assimp
    // / loads nothing out of the installation.
    $pyMeshLabDir = $InstLoc.$DirSep.'Resources'.$DirSep.'PyMeshLab';
    // / A subsystem that can fall back must fall back before it errors.
    // / PyMeshLab is used only when an interpreter exists that can load it.
    // / The MeshLab binary does the same work when no such interpreter is present.
    // / A warning then says why the requested route was not taken.
    $pyMeshLabInUse = FALSE;
    if ($UsePyMeshLab) {
      list ($pyMeshLabInterpreter, $pyMeshLabIsInstalled) = resolvePyMeshLabInterpreter($pyMeshLabDir);
      // / An installed copy needs no bundle directory. Passing one would insert a path
      // / that may not exist onto sys.path & set Qt variables pointing at nothing.
      if ($pyMeshLabIsInstalled) $pyMeshLabDir = '';
      if ($pyMeshLabInterpreter !== '') $pyMeshLabInUse = TRUE;
      else warningEntry('PyMeshLab is enabled & could not be used. Either no interpreter matching the bundled build is installed, or one is & the module did not import. Falling back to the MeshLab binary.'); }
    // / Now that it is known which of the two will run, the one that will run is required.
    // / A route needing the binary with no binary installed is refused here rather than
    // / discovered inside the retry loop as a command with nothing to execute.
    if (!$pyMeshLabInUse && $meshlabBinary === FALSE) {
      $ConversionErrors = TRUE;
      $readyToConvert = FALSE;
      errorEntry('The installed MeshLab version is missing, unidentifiable, or too old & PyMeshLab is not usable!', 9002, FALSE); }
    // / An intermediate file bridges the two utilities on any two stage route.
    // / The target's extension is dropped before obj is appended.
    // / Appending to the whole basename produced names like rectified_x.obj.obj, which
    // / worked because the final segment decides the format & read like a mistake.
    $intermediatePathname = dirname($newPathname).$DirSep.'rectified_'.pathinfo($newPathname, PATHINFO_FILENAME).'.obj';
    // / Decide the route once, before any attempt is made.
    // / A format neither utility can write is refused here rather than inside the retry
    // / loop. Retrying a command that cannot succeed only delays the same failure.
    if (in_array($outputExt, $assimpCanWrite, TRUE)) $conversionRoute = in_array($inputExt, $meshlabOnly, TRUE) ? 'normalize' : 'assimp';
    else if (in_array($outputExt, $meshlabCanWrite, TRUE)) $conversionRoute = in_array($inputExt, $meshlabCanRead, TRUE) ? 'meshlab' : 'bridge';
    else {
      $ConversionErrors = TRUE;
      errorEntry('Neither Assimp nor MeshLab can write the '.$outputExt.' format!', 9004, FALSE); }
    if ($conversionRoute !== '' && $Verbose) logEntry('Model route selected: '.$conversionRoute.', '.$inputExt.' to '.$outputExt.'.');
    // / Which of the two utilities will do the MeshLab half is recorded, under verbose only.
    // / It records what will run, not what was asked for.
    if ($conversionRoute !== '' && $Verbose) logEntry('MeshLab work will run through '.($pyMeshLabInUse ? 'the bundled PyMeshLab.' : 'the meshlabserver binary.'));
    // / Attempt the conversion as many times as --File Operation Retry Count-- allows, & stop at the
    // / first attempt that leaves a file.
    while ($conversionRoute !== '' && pipelineAttemptIsDue($newPathname, $stopper)) {
      // / If the last conversion attempt failed, wait a moment before trying again.
      if ($stopper !== 0) sleep($sleepTime++);
      // / Route normalize. MeshLab triangulates the mesh & Assimp writes the output.
      if ($conversionRoute === 'normalize' or $conversionRoute === 'bridge') {
        // / The bridge route runs the two utilities the other way round, so the first stage
        // / differs between them & the second stage is chosen below.
        if ($conversionRoute === 'normalize') {
          list ($meshlabCommand, $meshlabEnvironment) = buildMeshLabCommand($pyMeshLabInUse, $pyMeshLabDir, $meshlabBinary, $pathname, $intermediatePathname, $pyMeshLabInterpreter);
          list ($commandMayRun, $meshlabCommand) = sandboxCommand($meshlabCommand, $pathname, $intermediatePathname, FALSE, 'meshlab', $pyMeshLabDir, $meshlabEnvironment);
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
          list ($meshlabCommand, $meshlabEnvironment) = buildMeshLabCommand($pyMeshLabInUse, $pyMeshLabDir, $meshlabBinary, $assimpInput, $newPathname, $pyMeshLabInterpreter);
          list ($commandMayRun, $meshlabCommand) = sandboxCommand($meshlabCommand, $assimpInput, $newPathname, FALSE, 'meshlab', $pyMeshLabDir, $meshlabEnvironment);
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
        list ($meshlabCommand, $meshlabEnvironment) = buildMeshLabCommand($pyMeshLabInUse, $pyMeshLabDir, $meshlabBinary, $pathname, $newPathname, $pyMeshLabInterpreter);
        list ($commandMayRun, $meshlabCommand) = sandboxCommand($meshlabCommand, $pathname, $newPathname, FALSE, 'meshlab', $pyMeshLabDir, $meshlabEnvironment);
        if (!$commandMayRun) {
          $ConversionErrors = TRUE;
          errorEntry('Bubblewrap is missing or non functional, so this model conversion cannot be isolated!', 9003, FALSE);
          break; }
        $returnData = shell_exec($meshlabCommand); }
      // / Count the number of conversions to avoid infinite loops.
      $stopper++; }
    // / Every attempt was made & none left a file.
    // / What the converter printed is logged above, with --Enhanced Logging Verbosity-- on.
    // / A route that could not be sandboxed has already said so, & ran nothing to judge.
    if ($conversionRoute !== '' && $commandMayRun && !file_exists($newPathname)) {
      $ConversionErrors = TRUE;
      errorEntry('The model converter produced no file after '.$stopper.' attempt(s)!', 9000, FALSE);
      // / The error on its own says only that something was tried repeatedly. The command is what
      // / was tried, & without it an operator has a number & no way to reproduce the failure. It
      // / names paths this session already owns & carries no secret.
      warningEntry('The command that produced no file was: '.trim(($conversionRoute === 'assimp' ? $assimpCommand : $meshlabCommand))); }
    // / Log the output of each utility to the logfile, if it is not blank.
    // / A converter that produced nothing at all is worth a line of its own. Testing for
    // / output & staying silent when there is none meant the one case that needed
    // / explaining was the one case that explained nothing.
    if (!file_exists($newPathname) && trim($returnData) === '' && trim($assimpData) === '') warningEntry('Neither utility produced any output at all. The command was found & run, & it printed nothing on either stream.');
    // / A bundled PyMeshLab is compiled against one Python minor version.
    // / An object tagged 314 will not load under 3.12 or under 3.11.
    // / The interpreter reports that as the module being absent.
    // / An operator reading that goes looking at the path instead of at the build.
    if ($UsePyMeshLab && !file_exists($newPathname) && stripos((string)$returnData, 'pymeshlab') !== FALSE && stripos((string)$returnData, 'No module named') !== FALSE) warningEntry('PyMeshLab could not be imported. A bundled build is compiled for one Python minor version, so check that the interpreter matches the cpython tag on the object in Resources/PyMeshLab/pymeshlab. Set --Use PyMeshLab Python Bindings-- to FALSE to use the meshlabserver binary instead.');
    if ($Verbose && trim($returnData) !== '') logEntry('Meshlab processing engine returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
    if ($Verbose && trim($assimpData) !== '') logEntry('Assimp returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($assimpData)))));
    // / Erase the intermediate file so a two stage conversion leaves nothing behind.
    if (file_exists($intermediatePathname)) @unlink($intermediatePathname);
    // / Erase the material sidecar written beside an intermediate obj.
    // / The final output never has one, because the obj exporter is asked for objnomtl.
    // / Two names are possible & both are derived from the intermediate rather than counted
    // / back from the end of it. A magic offset here would have to be corrected every time
    // / the intermediate is renamed, & would keep working incorrectly if it were not.
    $sidecarPathname = dirname($intermediatePathname).$DirSep.pathinfo($intermediatePathname, PATHINFO_FILENAME).'.mtl';
    if (file_exists($intermediatePathname.'.mtl')) @unlink($intermediatePathname.'.mtl');
    if (file_exists($sidecarPathname)) @unlink($sidecarPathname);

    // / The output file is the only verdict on whether the conversion produced anything.
    if (file_exists($newPathname)) $ConversionSuccess = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $pyMeshLabIsInstalled, $sidecarPathname, $meshlabEnvironment, $pyMeshLabInUse, $pyMeshLabInterpreter, $returnData, $assimpData, $stopper, $pathname, $intermediatePathname, $assimpInput, $inputExt, $outputExt, $conversionRoute, $meshlabOnly, $assimpCanWrite, $meshlabCanWrite, $meshlabCanRead, $pyMeshLabDir, $sleepTime, $modelsValid, $readyToConvert, $meshlabCommand, $assimpCommand, $commandMayRun, $meshlabBinary, $assimpBinary);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $OutputFilename, $WorkerPID); }
// / -----------------------------------------------------------------------------------

?>
