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
// / v3.9.0.
// / This file is the converter for the Model pipeline. It is loaded by pipelineCore.php
// / ONLY when a Model conversion is about to be dispatched to it, so a request that
// / converts something else never parses a line of it.
// / Error block 9000 through 9004 belongs to this pipeline.
// / Those numbers came with the code when it moved out of convertCore.php.
// / They did not change, because operators have already read them.
// / An earlier header claimed 20000 through 20003, which this pipeline never raised.
// / This pipeline calls verifyModelVersions() & sandboxCommand(), which remain in
// / convertCore.php. A dependency verifier is core owned, because showVersionInfo()
// / reports on it whether or not this pipeline is installed.
// / A note on PyMeshLab, which is bundled at Resources/PyMeshLab.
// / The bundled module is a compiled extension rather than plain Python.
// / Its object is named for the interpreter it was built against, as cpython-314.
// / That tag is chosen by CPython rather than by PyMeshLab.
// / An import under Python 3.12 only ever considers an object tagged 312.
// / It also considers one tagged abi3, & one carrying no tag at all.
// / An object tagged 314 is never opened by a 3.12 interpreter.
// / Renaming the file does not help, because the code inside it is the problem.
// / A compiled extension links against symbols & structures that change between versions.
// / A renamed object would be found & would then crash instead of being skipped.
// / The stable abi3 interface would avoid this & is not practical for this kind of module.
// / Python 3.14 is therefore not a requirement of this application.
// / It is not listed in depends.php & nobody should be asked to install it.
// / The meshlabserver binary performs the same work & is already a dependency.
// / A host without a matching interpreter falls back to that binary & warns once.
// / The system python3 must never be replaced to satisfy this.
// / A distribution writes its own tooling against the interpreter it ships.
// / A machine whose package manager cannot run is worse than a slower conversion.
// / An interpreter may be installed alongside the system one & will then be found.
// / The bundle is kept even though it is unusable here, & that is deliberate.
// / MeshLab removed meshlabserver from its own releases in 2022.
// / This host runs 2020.09 & therefore still has it.
// / A current distribution ships no meshlabserver at all.
// / PyMeshLab is the only route on such a host, so the bundle is not dead weight.
// / resolvePyMeshLabInterpreter() reads whichever tag a future bundle carries.
// / Replacing the bundle with one built for the local interpreter needs no code change.
// /
// / A note on running meshlabserver inside the sandbox.
// / It links Qt & it initializes GLEW at startup whatever it has been asked to do.
// / A format conversion that renders nothing still requires an OpenGL context.
// / The offscreen platform plugin starts Qt & supplies no GL, so it is not enough.
// / An X server supplies GLX & therefore supplies GL, which is why xvfb-run is used.
// / Four separate problems stood between that server & a working namespace.
// / Xvfb needs /tmp/.X11-unix to exist & the sandbox mounts an empty tmpfs at /tmp.
// / Xvfb will only create that directory when its effective user is root.
// / Xvfb then checks that the directory is owned by root & rejects it when it is not.
// / A GPU vendor driver crashed while enumerating devices the sandbox does not expose.
// / The meshlab sandbox profile answers all four & each answer is commented there.
// / The profile mounts a tmpfs at the socket path so the directory is already present.
// / It maps this process to root inside the namespace, which grants nothing on the host.
// / It names the Mesa vendor file so the GPU driver is never loaded at all.
// / Software rendering is correct here regardless, because a conversion renders nothing.
// / None of this was visible until xvfb-run was asked to report the server's own output.
// / It discards that output by default & reports only that the display was unreachable.
// / Several rounds were spent reading Qt's guess about a cause rather than the cause.
// / This path only ever worked unsandboxed, where /tmp is the real one on the host.
// / A host has a root owned /tmp/.X11-unix already & Xvfb was content with it.
// / It failed from the day this profile began sandboxing & nobody saw it fail.
// / The normalize route falls through to Assimp when MeshLab produces nothing.
// / Every model conversion since then silently skipped the normalization step.
// / A well formed mesh does not care & a non manifold one quietly converts worse.
// / A fallback that works too well turns a hard failure into a silent regression.
// /
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
  // / An extension is not always the identifier Assimp knows the format by.
  // / obj becomes objnomtl, which writes the geometry & no material sidecar. The final
  // / output should never have one, & a sidecar left beside it is a file the session owns
  // / & never cleans.
  // / json becomes assjson, which is what the exporter is registered as. Asking for json
  // / produced 'no output format specified and I failed to guess it', then the retry loop
  // / ran the same impossible command four more times & timed out naming nothing.
  // / json is a declared output of this pipeline, so this mapping is what makes the
  // / declaration true rather than aspirational.
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
// / A function to find the interpreter that can actually load the bundled PyMeshLab.
// / Accepts the bundle directory. Returns an interpreter name, or an empty string.
// / A compiled extension is built against one Python minor version.
// / It will not load under any other version.
// / The bundle states which version it needs in the name of its object.
// / That name is derived here rather than assuming the interpreter is python3.
// / An interpreter that cannot load an object reports the module as absent.
// / That message reads as a path problem & is not one.
// / Deriving the name from the bundle removes the guesswork entirely.
// / An empty return means no interpreter on this host can load the bundle.
// / The caller falls back to the MeshLab binary when that happens.
function resolvePyMeshLabInterpreter($pyMeshLabDir) {
  // / Set variables.
  global $EnableMemoryProtection;
  $InterpreterName = '';
  $objectPaths = $tagMatches = array();
  $candidateName = $objectPath = $probeCommand = '';
  $probeOutput = array();
  $probeExitCode = 1;
  $objectPaths = glob(rtrim((string)$pyMeshLabDir, '/').'/pymeshlab/*.cpython-*.so');
  if (is_array($objectPaths)) {
    foreach ($objectPaths as $objectPath) {
      if (!preg_match('/cpython-(\\d)(\\d+)/', basename($objectPath), $tagMatches)) continue;
      // / cpython-314 means python3.14. The tag carries no dot & the name does.
      $candidateName = 'python'.$tagMatches[1].'.'.$tagMatches[2];
      if (locateDependency($candidateName) === '') continue;
      // / An interpreter existing is not the same as the module loading & the difference
      // / is the whole point of this test.
      // / An earlier version stopped here. It found python3.14, reported PyMeshLab in use,
      // / built a command that raised ImportError & left nothing to fall back to, because
      // / the fallback had already been decided against.
      // / Installing the interpreter to clear a warning therefore broke the conversion path
      // / that had been working, which is worse than the warning was.
      // / The import is attempted once, with the same environment the conversion will use.
      // / It costs one interpreter startup per request & buys the difference between a
      // / route that works & a route that is merely available.
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
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $probeCommand, $probeOutput, $probeExitCode, $objectPaths, $tagMatches, $candidateName, $objectPath, $pyMeshLabDir);
  return $InterpreterName; }
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to build the MeshLab command for one conversion.
// / Accepts the PyMeshLab toggle, the PyMeshLab directory, the binary, the input path &
// / the output path, in that order. Returns the command as a string.
// / MeshLab is reachable two ways & the caller should not have to know which is in use.
// / The bundled PyMeshLab module needs no display server.
// / The meshlabserver binary needs a real display & is run under xvfb-run.
// / Both decide the output format from the extension of the path they are given.
// / Returns the command & the environment it needs, in that order.
// / The variables are returned rather than prefixed onto the command, because bwrap execs
// / without a shell & would read a NAME=value prefix as the name of a binary.
// / sandboxCommand() turns them into --setenv when it sandboxes & into a prefix when it
// / cannot, so this function does not need to know which happened.
function buildMeshLabCommand($usePyMeshLab, $pyMeshLabDir, $meshlabBinary, $inputPath, $outputPath, $interpreterName) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $MeshLabCommand = $pythonPrologue = '';
  $CommandEnvironment = array();
  if ($usePyMeshLab) {
    // / The bundled directory is only put on the Python path when it is actually there.
    // / PyMeshLab is normally a pip package living under /usr, which the sandbox already
    // / binds, & this application does not ship a copy. Inserting a directory that does not
    // / exist achieved nothing & made it look as though the module had been provided.
    // / The bundle is a directory holding a pymeshlab package, so the PARENT goes on the
    // / path & the import finds the package inside it.
    // / It also ships its own shared objects under pymeshlab/lib, & the dynamic loader has
    // / no reason to look there. Without this the compiled extension is found & then fails
    // / to load, which reports as a missing module & sends everybody looking at sys.path.
    // / The escaped directory is what sandboxCommand() rewrites, so the suffix is appended
    // / outside it & survives the rewrite as /res/pymeshlab/lib.
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
  // / meshlabserver needs a real display & the offscreen platform plugin will not do.
  // / It initializes GLEW at startup whatever it has been asked to do.
  // / A pure format conversion that renders nothing still requires an OpenGL context.
  // / Qt starting successfully is not enough, & the offscreen plugin supplies no GL.
  // / An attempt to use it failed with  GLEW initialization failed: Missing GL version.
  // / An X server supplies GLX & therefore supplies GL, which is why xvfb-run is here.
  // / The server is told not to listen on a TCP port.
  // / The sandbox unshares the network namespace & leaves loopback down.
  // / An X server binding a port in that namespace fails & takes the display with it.
  // / xvfb-run then retries the next display number & reports the last one it tried.
  // / That is why an earlier failure named display 109 rather than the usual 99.
  // / -e names a file for the X server's own error output.
  // / xvfb-run discards that output by default & reports only that the display was never
  // / reached, which is Qt complaining about a symptom rather than the server explaining
  // / a cause. Several rounds of diagnosis were guesswork for exactly that reason.
  // / It is attached only under verbose logging, because a working X server still reports
  // / around thirty keysym warnings from the keymap compiler on every start.
  // / Those warnings say outright that they are not fatal & they are not worth carrying in
  // / an ordinary log. A server that fails to start is worth every line it prints.
  else $MeshLabCommand = 'xvfb-run -a'.($Verbose ? ' -e /dev/stderr' : '').' -s '.escapeshellarg('-screen 0 1280x1024x24 -nolisten tcp').' '.escapeshellarg($meshlabBinary).' -i '.escapeshellarg($inputPath).' -o '.escapeshellarg($outputPath).' 2>&1';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $pythonPrologue, $usePyMeshLab, $pyMeshLabDir, $meshlabBinary, $inputPath, $outputPath, $interpreterName);
  return array($MeshLabCommand, $CommandEnvironment); }
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
// / The meshlabserver binary needs a real display & is run under xvfb-run.
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
  $meshlabOnly = array();
  $stopper = 0;
  $sleepTime = $SleepTimer;
  $outputExt = $conversionRoute = $pyMeshLabInterpreter = $sidecarPathname = '';
  $meshlabEnvironment = array();
  $pyMeshLabInUse = FALSE;
  $assimpCanWrite = $meshlabCanWrite = $meshlabCanRead = array();
  // / Detect the installed versions of Assimp & MeshLab.
  list ($modelsValid, $assimpBinary, $meshlabBinary) = verifyModelVersions($MinimumAssimpVersion, $MinimumMeshlabVersion);
  // / Assimp is used by every route, so it is required unconditionally.
  if ($assimpBinary === FALSE) {
    $ConversionErrors = TRUE;
    errorEntry('The installed Assimp version is missing, unidentifiable, or too old!', 9001, FALSE); }
  // / MeshLab is not tested here & that is deliberate.
  // / Whether the binary is needed depends on whether PyMeshLab actually loads, & that is
  // / not known until the import has been attempted a few lines below.
  // / An earlier version tested $UsePyMeshLab here, which is a setting rather than a
  // / capability. With PyMeshLab enabled, the bundle failing to import & no binary
  // / installed, nothing refused, the fallback chose the binary anyway & buildMeshLabCommand
  // / assembled xvfb-run with an empty path where a program should have been.
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
    // / These are extensions rather than Assimp identifiers. buildAssimpCommand maps the
    // / two where they differ, & a format missing from here is refused before it is tried.
    $assimpCanWrite = array('obj', 'stl', 'ply', '3ds', 'dae', 'x3d', 'fbx', 'gltf', 'glb', '3mf', 'x', 'assbin', 'json');
    $meshlabCanWrite = array('ply', 'stl', 'obj', 'off', 'dae', 'x3d', 'dxf', 'wrl', 'ctm', 'u3d');
    // / What MeshLab can open. Assimp reads considerably more, so it is not listed.
    $meshlabCanRead = array('ply', 'stl', 'obj', 'off', '3ds', 'dae', 'x3d', 'dxf', 'wrl', 'ctm', 'ptx', 'xyz', 'gts', 'asc');
    // / Inputs that are passed through MeshLab before Assimp writes the output.
    // / Each of these carries geometry Assimp will accept & will not always triangulate the
    // / way a consumer expects, so MeshLab normalizes first & Assimp exports from that.
    // / The name is historical. These are not formats only MeshLab can read, & Assimp reads
    // / every one of them.
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
    // / An earlier version failed the whole conversion instead.
    // / It did so on a machine where meshlabserver was installed & working perfectly.
    $pyMeshLabInUse = FALSE;
    if ($UsePyMeshLab) {
      $pyMeshLabInterpreter = resolvePyMeshLabInterpreter($pyMeshLabDir);
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
    // / This reports what WILL RUN rather than what was asked for. An earlier version tested
    // / $UsePyMeshLab, so an installation with PyMeshLab enabled & failing to import logged
    // / nothing at all & left no record of which utility actually did the work.
    if ($conversionRoute !== '' && $Verbose) logEntry('MeshLab work will run through '.($pyMeshLabInUse ? 'the bundled PyMeshLab.' : 'the meshlabserver binary.'));
    // / This code will attempt the conversion up to $StopCounter number of times.
    while ($conversionRoute !== '' && !file_exists($newPathname) && $stopper <= $StopCounter) {
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
      $stopper++;
      // / Stop attempting the conversion after $StopCounter number of attempts.
      // / The loop is left immediately, because an attempt that has already been counted as
      // / the last one must not run again after the timeout has been reported.
      if ($stopper >= $StopCounter) {
        $ConversionErrors = TRUE;
        errorEntry('The model converter timed out!', 9000, FALSE);
        // / A timeout on its own says only that something was tried repeatedly. The command
        // / is what was tried, & without it an operator has a number & no way to reproduce
        // / the failure. It names paths this session already owns & carries no secret.
        warningEntry('The command that timed out was: '.trim(($conversionRoute === 'assimp' ? $assimpCommand : $meshlabCommand)));
        break; } }
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
  purgeSensitiveMemory($EnableMemoryProtection, $sidecarPathname, $meshlabEnvironment, $pyMeshLabInUse, $pyMeshLabInterpreter, $returnData, $assimpData, $stopper, $pathname, $intermediatePathname, $assimpInput, $inputExt, $outputExt, $conversionRoute, $meshlabOnly, $assimpCanWrite, $meshlabCanWrite, $meshlabCanRead, $pyMeshLabDir, $sleepTime, $modelsValid, $readyToConvert, $meshlabCommand, $assimpCommand, $commandMayRun, $meshlabBinary, $assimpBinary);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $OutputFilename, $WorkerPID); }
// / -----------------------------------------------------------------------------------

?>
