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
// / v4.3.5.
// / The Model pipeline's converter. pipelineCore.php loads it only to dispatch a Model conversion.
// / Error block 9000 through 9008 belongs to this pipeline.
// / Assimp & MeshLab come from verifiedToolPath().
// / Assimp is HRConvert2's own build, from v4.3.2, with three importers, OBJ, FBX & glTF 2.0, & the depends.php entry says why.
// / Each upload is read by one program. Assimp reads obj, fbx, gltf & glb, & MeshLab reads every other model this pipeline takes.
// / Each runs in a folder of the conversion's own, given a copy of the upload & an empty folder to write into, & only the output leaves.
// / What a glTF or an OBJ names outside itself is checked before Assimp opens it, as the convenience on top of the sandbox.
// / MeshLab is reached through PyMeshLab when an interpreter can import it, & otherwise through meshlabserver.
// / The bundled PyMeshLab at Resources/PyMeshLab is a compiled extension tagged for one interpreter, such as cpython-314.
// / No other interpreter version loads it, & renaming the object does not help.
// / resolvePyMeshLabInterpreter() reads the tag, so a bundle built for another interpreter needs no code change.
// / PyMeshLab's wheels need glibc 2.35 or newer, so an older host has meshlabserver alone.
// / The system python3 must never be replaced to satisfy this, though another interpreter may be installed beside it.
// / The bundle is kept, because a current distribution ships no meshlabserver.
// / meshlabserver initializes GLEW at startup, so even a conversion needs OpenGL, which xvfb-run supplies.
// / The meshlab sandbox profile lets Xvfb run in a namespace, & comments each of its settings. Assimp runs under the assimp profile.
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
// / gltf & glb ask for gltf2 & glb2, from v4.3.2. Assimp names glTF 1.0 gltf & glb, & wrote a gltf & a glb in it until then.
// / dae asks for collada, from v4.3.2, which is the name Assimp knows it by. It wrote one under dae as well, & said dae was unknown.
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
  if ($formatIdentifier === 'gltf') $formatIdentifier = 'gltf2';
  if ($formatIdentifier === 'glb') $formatIdentifier = 'glb2';
  if ($formatIdentifier === 'dae') $formatIdentifier = 'collada';
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
// / A function to give the exit status one attempt of one stage or two is judged by, from v4.3.1.
// / Accepts the status the attempt was judged by before the stage that just ran, & that stage's status, in that order.
// / Returns the status something outside the converter stopped the first stage with, where it did, & otherwise the status of the
// / stage that just ran.
// / So an attempt is made again where KILL or TERM stopped either stage, as pipelineCommandWasStopped() tells them apart from a crash,
// / & a status a converter chose for itself, which may be higher than a signal's, never hides one.
function modelAttemptStatus($attemptStatus, $stageStatus) {
  // / Set variables.
  global $EnableMemoryProtection;
  $JudgedStatus = 0;
  $JudgedStatus = (pipelineCommandWasStopped($attemptStatus) ? (int)$attemptStatus : (int)$stageStatus);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $JudgedStatus is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection, $attemptStatus, $stageStatus);
  return $JudgedStatus; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether a model names a file by a plain file name, from v4.3.2.
// / Accepts the name as the model writes it. Returns TRUE for a plain file name & for an empty name, & FALSE for anything else.
// / A plain file name has no slash, no backslash, no colon & no NUL, & is not . or .., as written & once percent encoding is undone.
// / A slash or a backslash reaches another folder, a colon a drive or a scheme, & . & .. a folder itself or the one above it.
// / A plain name is looked for beside the model, which is alone in its folder, so it finds nothing. An empty name opens nothing.
function referenceIsPlainName($referenceName) {
  // / Set variables.
  global $EnableMemoryProtection;
  $NameIsPlain = TRUE;
  $nameForm = '';
  foreach (array((string)$referenceName, rawurldecode((string)$referenceName)) as $nameForm) {
    if ($nameForm === '.' or $nameForm === '..' or strpbrk($nameForm, '/\\:') !== FALSE or strpos($nameForm, "\0") !== FALSE) $NameIsPlain = FALSE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $nameForm, $referenceName);
  return $NameIsPlain; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to judge the material libraries one piece of an OBJ names, from v4.3.2.
// / Accepts a piece of the OBJ that ends with a newline or at the end of the file, & whether the piece is wanted without the unsafe ones.
// / Returns whether the piece was judged, how many libraries it names by anything but a plain file name, the piece as Assimp reads
// / it with those lines left out, which is empty unless it was wanted, & why it was not judged, in that order.
// / The piece is read as Assimp reads it. A backslash before the end of a line joins the next line on, from the end of the line to
// / the next newline, & a carriage return, a NUL & a form feed each end a line as a newline does. So the joins are made first, & any
// / of the four begins a line. A backslash still before the end of a line once they are made is one no program writes.
// / An mtllib line is found in any case, after spaces, tabs & a byte order mark, where Assimp needs it in lower case at the start of
// / its line, so a line Assimp would not have read may be left out, & none it would have read is kept. Its name is the rest of the line.
// / The piece Assimp is given has its joins made & every line ended with a newline, so Assimp reads its lines exactly as they were judged.
function judgeObjText($objText, $copyIsWanted) {
  // / Set variables.
  global $EnableMemoryProtection;
  $TextIsJudged = FALSE;
  $UnsafeCount = 0;
  $CopyText = $JudgeProblem = '';
  $libraryName = '';
  $lineMatches = $lineMatch = array();
  $keptFrom = $lineStart = 0;
  $joinIsUsed = FALSE;
  $objText = (string)$objText;
  $joinIsUsed = (preg_match('/\\\\[\r\n\x00\x0c]/', $objText) === 1);
  if ($joinIsUsed) redeclare($objText, preg_replace('/\\\\(?:\n|[\r\x00\x0c][^\n]*\n)/', '', $objText));
  if (!is_string($objText)) $JudgeProblem = 'it could not be read as text';
  else if ($joinIsUsed && preg_match('/\\\\[\r\n\x00\x0c]/', $objText) === 1) $JudgeProblem = 'a backslash in it joins its lines in a way no program writes';
  else if (preg_match_all('/(?:^|[\r\n\x00\x0c])(?:\xEF\xBB\xBF)?[ \t]*mtllib(?:[ \t]([^\r\n\x00\x0c]*))?(?=[\r\n\x00\x0c]|$)/i', $objText, $lineMatches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) === FALSE) $JudgeProblem = 'it could not be read as text';
  else {
    $TextIsJudged = TRUE;
    foreach ($lineMatches as $lineMatch) {
      $libraryName = (isset($lineMatch[1][0]) ? trim((string)$lineMatch[1][0], " \t") : '');
      if (referenceIsPlainName($libraryName)) continue;
      $UnsafeCount++;
      // / The line is left out & the end of the line before it is kept, so the lines either side of it stay apart.
      $lineStart = $lineMatch[0][1] + (strpos("\r\n\x00\x0c", substr((string)$lineMatch[0][0], 0, 1)) !== FALSE ? 1 : 0);
      if ($copyIsWanted) $CopyText = $CopyText.substr($objText, $keptFrom, $lineStart - $keptFrom);
      $keptFrom = $lineMatch[0][1] + strlen((string)$lineMatch[0][0]); }
    if ($copyIsWanted) $CopyText = strtr($CopyText.substr($objText, $keptFrom), "\r\x00\x0c", "\n\n\n"); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $libraryName, $lineMatches, $lineMatch, $keptFrom, $lineStart, $joinIsUsed, $objText, $copyIsWanted);
  return array($TextIsJudged, $UnsafeCount, $CopyText, $JudgeProblem); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to give Assimp an OBJ without a material library it names by anything but a plain file name, from v4.3.2.
// / Accepts the staged OBJ & the path of the copy Assimp reads, in that order.
// / Returns whether the copy was made, how many lines were left out of it & why it was refused, in that order. Where it was not made
// / & no reason is given, it could not be read or written.
// / Assimp opens the material library an mtllib line names, from beside the OBJ, & writes the names & texture paths it reads into
// / the model it makes. A plain file name finds nothing, because the OBJ is alone in its folder, so it is kept. A line naming a path,
// / a drive, a scheme or the folder above is left out, & a warning says how many were.
// / The OBJ is read a megabyte at a time & cut after its last newline, so every line is judged whole, & one longer than 8 MiB is refused.
// / The first pass only reads. An OBJ with nothing to leave out is linked as it is, & only one with a line to leave out is read a
// / second time & written, as judgeObjText() reads it.
// / The sandbox is the boundary. This is the convenience on top of it, as ABOUT_SCAD_SANITIZATION.txt says of OpenSCAD's.
function copyObjForAssimp($objPath, $copyPath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $CopyIsMade = FALSE;
  $LinesLeftOut = 0;
  $RefusalReason = '';
  $sourceHandle = $copyHandle = FALSE;
  $pendingText = $copyText = $judgeProblem = '';
  $passNumber = $cutAt = $unsafeCount = $pendingLength = 0;
  $copyIsWanted = $sourceIsRead = $textIsJudged = $writeFailed = FALSE;
  for ($passNumber = 1; $passNumber <= 2 && $RefusalReason === '' && !$writeFailed && !$CopyIsMade; $passNumber++) {
    $copyIsWanted = ($passNumber === 2);
    $LinesLeftOut = 0;
    $sourceIsRead = FALSE;
    $sourceHandle = @fopen((string)$objPath, 'rb');
    // / The copy is made only where nothing is, so nothing is ever written through a name something else holds.
    if ($copyIsWanted) $copyHandle = @fopen((string)$copyPath, 'xb');
    if ($sourceHandle === FALSE or ($copyIsWanted && $copyHandle === FALSE)) $writeFailed = TRUE;
    while (!$writeFailed && $RefusalReason === '' && !$sourceIsRead) {
      // / Each read joins what was left of the last, & the whole lines in it are judged. A read that adds nothing before the end is a failure.
      $pendingLength = strlen($pendingText);
      $pendingText .= (string)fread($sourceHandle, 1048576);
      $sourceIsRead = feof($sourceHandle);
      if (!$sourceIsRead && strlen($pendingText) === $pendingLength) $writeFailed = TRUE;
      else if ($sourceIsRead) $cutAt = strlen($pendingText);
      else $cutAt = (strrpos($pendingText, "\n") === FALSE ? 0 : strrpos($pendingText, "\n") + 1);
      if ($writeFailed) break;
      if ($cutAt === 0 && strlen($pendingText) > 8388608) $RefusalReason = 'a line in it is longer than 8 MiB';
      else if ($cutAt > 0) {
        list ($textIsJudged, $unsafeCount, $copyText, $judgeProblem) = judgeObjText(substr($pendingText, 0, $cutAt), $copyIsWanted);
        if (!$textIsJudged) $RefusalReason = $judgeProblem;
        else {
          $LinesLeftOut = $LinesLeftOut + $unsafeCount;
          if ($copyIsWanted && @fwrite($copyHandle, $copyText) !== strlen($copyText)) $writeFailed = TRUE; }
        redeclare($copyText, '');
        redeclare($pendingText, (string)substr($pendingText, $cutAt)); } }
    if (is_resource($sourceHandle)) fclose($sourceHandle);
    if (is_resource($copyHandle)) fclose($copyHandle);
    // / An OBJ with nothing to leave out is the one Assimp reads, linked where it can be & copied where it cannot.
    if ($passNumber === 1 && !$writeFailed && $RefusalReason === '' && $LinesLeftOut === 0) {
      $CopyIsMade = (@link((string)$objPath, (string)$copyPath) or @copy((string)$objPath, (string)$copyPath));
      if (!$CopyIsMade) $writeFailed = TRUE; }
    else if ($passNumber === 2 && !$writeFailed && $RefusalReason === '') $CopyIsMade = TRUE; }
  // / A copy that was begun & not finished is never read.
  if (!$CopyIsMade && is_file((string)$copyPath) && !is_link((string)$copyPath)) @unlink((string)$copyPath);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $sourceHandle, $copyHandle, $pendingText, $copyText, $judgeProblem, $passNumber, $cutAt, $unsafeCount, $pendingLength, $copyIsWanted, $sourceIsRead, $textIsJudged, $writeFailed, $objPath, $copyPath);
  return array($CopyIsMade, $LinesLeftOut, $RefusalReason); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to count the keys of every object in a decoded JSON value, from v4.3.2.
// / Accepts the value json_decode() returned, with each object as an object. Returns the number of keys.
// / PHP keeps one value for a key an object names twice, so a document naming one twice has fewer keys here than in its text.
function countDecodedJsonKeys($decodedValue) {
  // / Set variables.
  global $EnableMemoryProtection;
  $KeyCount = 0;
  $memberValue = NULL;
  $memberName = '';
  if (is_object($decodedValue)) {
    foreach (get_object_vars($decodedValue) as $memberName => $memberValue) $KeyCount = $KeyCount + 1 + ((is_object($memberValue) or is_array($memberValue)) ? countDecodedJsonKeys($memberValue) : 0); }
  else if (is_array($decodedValue)) {
    foreach ($decodedValue as $memberValue) if (is_object($memberValue) or is_array($memberValue)) $KeyCount = $KeyCount + countDecodedJsonKeys($memberValue); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $memberValue, $memberName, $decodedValue);
  return $KeyCount; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say what is unsafe about the uri a glTF gives one buffer or one image, from v4.3.2.
// / Accepts the uri's first 512 bytes, whether the uri is longer than that & whether it is a buffer's, in that order.
// / Returns what is wrong with it, which is empty where nothing is.
// / A data URI holds what it names. Its header, everything before its first comma, must be shorter than 128 bytes, because a longer
// / one reaches a read outside the header's buffer in an Assimp without pull request #7029, CVE-2026-129478.
// / A buffer must be a data URI, because a buffer is the geometry & one named by a path is read from that path into the model made.
// / An image may also be a plain file name, which finds nothing beside a model alone in its folder.
// / Assimp reads a uri as a data URI where it begins with data: in lower case, & as a path otherwise, so only that spelling is one.
function describeUnsafeGltfUri($uriHead, $uriIsLonger, $uriIsBuffer) {
  // / Set variables.
  global $EnableMemoryProtection;
  $UnsafeReason = '';
  $commaAt = FALSE;
  if (strncmp((string)$uriHead, 'data:', 5) === 0) {
    $commaAt = strpos((string)$uriHead, ',');
    if ($commaAt === FALSE or $commaAt >= 128) $UnsafeReason = 'a data URI whose header is 128 bytes or longer'; }
  else if ($uriIsBuffer) $UnsafeReason = 'named by a path, where a buffer must be held in a data URI';
  else if ($uriIsLonger) $UnsafeReason = 'named by a path longer than 512 bytes';
  else if (!referenceIsPlainName($uriHead)) $UnsafeReason = 'named by a path, where an image must be a data URI or a plain file name';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $commaAt, $uriHead, $uriIsLonger, $uriIsBuffer);
  return $UnsafeReason; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to check what a glTF names outside itself before Assimp opens it, from v4.3.2.
// / Accepts the path of the copy Assimp reads & its extension, gltf or glb, in that order.
// / Returns whether it may be converted & why it may not, in that order.
// / A glb's header must say glTF 2, & its length, its JSON's & its binary chunk's must each end inside the file, because Assimp trusts
// / the length of the chunk it reads. Each buffer & each image is judged by describeUnsafeGltfUri(). A glb's first buffer may name
// / nothing, because it is the glb's own binary chunk.
// / A key named twice in one object is refused, because PHP keeps the last & Assimp the first, so each would judge a different uri.
// / So every key in the text is counted, a string skipped whole from its quote to the next quote no backslash escapes, & compared
// / with the keys PHP decoded.
// / The JSON is read whole. One larger than a quarter of the memory this request has left is refused, because PHP ends a request
// / that runs out of memory with nothing to say. A glb keeps its geometry outside its JSON, so it converts at any size.
// / The sandbox is the boundary. This is the convenience on top of it, as ABOUT_SCAD_SANITIZATION.txt says of OpenSCAD's.
// / The function is longer than most, so its first return value is its sanity check.
function checkGltfReferences($modelPath, $modelExtension) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ModelIsSafe = FALSE;
  $RefusalReason = '';
  $fileSize = $jsonOffset = $jsonLength = $chunkEnd = $memoryLimit = $readBudget = $rawKeyCount = $closeAt = $slashCount = $afterAt = $entryIndex = 0;
  $quoteAt = FALSE;
  $glbHeader = $chunkHeader = $jsonText = $limitText = $collectionName = $uriReason = '';
  $headerValues = $chunkValues = array();
  $gltfRoot = $entryValue = NULL;
  clearstatcache(TRUE, (string)$modelPath);
  $fileSize = (int)@filesize((string)$modelPath);
  if (strtolower((string)$modelExtension) !== 'glb') $jsonLength = $fileSize;
  else {
    $glbHeader = (string)@file_get_contents((string)$modelPath, FALSE, NULL, 0, 20);
    if (strlen($glbHeader) === 20) $headerValues = unpack('a4Magic/VVersion/VLength/VJsonLength/a4JsonType', $glbHeader);
    if (strlen($glbHeader) !== 20 or !is_array($headerValues)) $RefusalReason = 'it is shorter than the header a glb begins with';
    else if ($headerValues['Magic'] !== 'glTF') $RefusalReason = 'it does not begin with glTF, as a glb does';
    else if ((int)$headerValues['Version'] !== 2) $RefusalReason = 'its header says glTF '.(int)$headerValues['Version'].', & only glTF 2 is read';
    else if ((int)$headerValues['Length'] < 20 or (int)$headerValues['Length'] > $fileSize) $RefusalReason = 'its header says it is '.(int)$headerValues['Length'].' bytes long, & it is '.$fileSize;
    else if ($headerValues['JsonType'] !== 'JSON') $RefusalReason = 'its first chunk is not its JSON';
    else if ((int)$headerValues['JsonLength'] < 2 or 20 + (int)$headerValues['JsonLength'] > (int)$headerValues['Length']) $RefusalReason = 'its JSON chunk ends past the end its header gives';
    else {
      $jsonOffset = 20;
      $jsonLength = (int)$headerValues['JsonLength'];
      $chunkEnd = 20 + $jsonLength;
      if ((int)$headerValues['Length'] - $chunkEnd >= 8) {
        $chunkHeader = (string)@file_get_contents((string)$modelPath, FALSE, NULL, $chunkEnd, 8);
        if (strlen($chunkHeader) === 8) $chunkValues = unpack('VChunkLength/a4ChunkType', $chunkHeader);
        if (strlen($chunkHeader) !== 8 or !is_array($chunkValues) or $chunkEnd + 8 + (int)$chunkValues['ChunkLength'] > (int)$headerValues['Length']) $RefusalReason = 'its binary chunk ends past the end its header gives'; }
      else if ((int)$headerValues['Length'] !== $chunkEnd) $RefusalReason = 'it ends part way through the header of a chunk'; } }
  if ($RefusalReason === '') {
    // / memory_limit is a number of bytes, or one ending in K, M or G, & -1 where there is no limit.
    $limitText = strtolower(trim((string)ini_get('memory_limit')));
    $memoryLimit = (int)$limitText;
    if (substr($limitText, -1) === 'g') $memoryLimit = $memoryLimit * 1073741824;
    else if (substr($limitText, -1) === 'm') $memoryLimit = $memoryLimit * 1048576;
    else if (substr($limitText, -1) === 'k') $memoryLimit = $memoryLimit * 1024;
    $readBudget = ($memoryLimit <= 0 ? 268435456 : intdiv(max(0, $memoryLimit - memory_get_usage()), 4));
    if ($jsonLength > $readBudget) $RefusalReason = 'its JSON is '.round($jsonLength / 1048576, 1).' MiB, & this request has room to read '.round($readBudget / 1048576, 1).' MiB of it';
    else {
      $jsonText = (string)@file_get_contents((string)$modelPath, FALSE, NULL, $jsonOffset, $jsonLength);
      $gltfRoot = json_decode($jsonText, FALSE, 512);
      if (strlen($jsonText) !== $jsonLength) $RefusalReason = 'its JSON could not be read';
      else if (json_last_error() !== JSON_ERROR_NONE) $RefusalReason = 'its JSON could not be read, because of '.strtolower(json_last_error_msg());
      else if (!is_object($gltfRoot)) $RefusalReason = 'its JSON is not an object';
      else {
        // / A string is skipped from its quote to the next quote that an even number of backslashes comes before, & is a key where a colon follows it.
        $quoteAt = strpos($jsonText, '"');
        while ($quoteAt !== FALSE) {
          $closeAt = (int)$quoteAt;
          do {
            $closeAt = strpos($jsonText, '"', $closeAt + 1);
            $slashCount = 0;
            if ($closeAt !== FALSE) while ($jsonText[$closeAt - 1 - $slashCount] === '\\') $slashCount++; }
          while ($closeAt !== FALSE && $slashCount % 2 === 1);
          if ($closeAt === FALSE) break;
          $afterAt = $closeAt + 1 + strspn($jsonText, " \t\r\n", $closeAt + 1);
          if ($afterAt < $jsonLength && $jsonText[$afterAt] === ':') $rawKeyCount++;
          $quoteAt = strpos($jsonText, '"', $closeAt + 1); }
        if ($rawKeyCount !== countDecodedJsonKeys($gltfRoot)) $RefusalReason = 'its JSON names a key twice in one object, & Assimp reads the first where PHP reads the last';
        else {
          foreach (array('buffers', 'images') as $collectionName) {
            if ($RefusalReason !== '' or !isset($gltfRoot->$collectionName)) continue;
            if (!is_array($gltfRoot->$collectionName)) $RefusalReason = 'its '.$collectionName.' are not a list';
            else foreach ($gltfRoot->$collectionName as $entryIndex => $entryValue) {
              if ($RefusalReason !== '') break;
              if (!is_object($entryValue)) $RefusalReason = substr($collectionName, 0, -1).' '.$entryIndex.' is not an object';
              // / A buffer naming nothing is a glb's own binary chunk, which only the glb's first buffer may be.
              else if (!isset($entryValue->uri)) {
                if ($collectionName === 'buffers' && !($entryIndex === 0 && strtolower((string)$modelExtension) === 'glb')) $RefusalReason = 'buffer '.$entryIndex.' names nothing to read'; }
              else if (!is_string($entryValue->uri)) $RefusalReason = substr($collectionName, 0, -1).' '.$entryIndex.' gives a uri that is not text';
              else {
                $uriReason = describeUnsafeGltfUri(substr($entryValue->uri, 0, 512), (strlen($entryValue->uri) > 512), ($collectionName === 'buffers'));
                if ($uriReason !== '') $RefusalReason = substr($collectionName, 0, -1).' '.$entryIndex.' is '.$uriReason; } } }
          if ($RefusalReason === '') $ModelIsSafe = TRUE; } } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $fileSize, $jsonOffset, $jsonLength, $chunkEnd, $memoryLimit, $readBudget, $rawKeyCount, $quoteAt, $closeAt, $slashCount, $afterAt, $entryIndex, $glbHeader, $chunkHeader, $jsonText, $limitText, $collectionName, $uriReason, $headerValues, $chunkValues, $gltfRoot, $entryValue, $modelPath, $modelExtension);
  return array($ModelIsSafe, $RefusalReason); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to check an OFF before MeshLab opens it, from v4.3.3.
// / Accepts the path of the copy MeshLab reads. Returns whether it may be converted & why it may not, in that order.
// / Lines are skipped & split as MeshLab's reader skips & splits them.
// / A header of ST, C & N before OFF is followed by three counts, then each vertex on a line of its own, then each face.
// / A face is a line naming 3 or 4 different vertices the file holds. A face's colour & a vertex's are 3 or 4 numbers or none.
// / Any other OFF is refused. DEVELOPMENT_NOTES.txt says why.
// / The sandbox is the boundary. This is the convenience on top of it, as ABOUT_SCAD_SANITIZATION.txt says of OpenSCAD's.
// / The function is longer than most, so its first return value is its sanity check.
function checkOffStructure($modelPath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ModelIsSafe = FALSE;
  $RefusalReason = '';
  $headerMatch = $offCounts = $lineTokens = $faceIndices = array();
  $vertexTokens = $verticesRead = $facesRead = $cornerCount = 0;
  $lineText = '';
  $offHandle = @fopen($modelPath, 'rb');
  if ($offHandle === FALSE) $RefusalReason = 'it could not be read';
  // / Reading stops once the faces the header counts are read, as MeshLab's does.
  while ($RefusalReason === '' && ($offCounts === array() or $verticesRead < $offCounts[0] or $facesRead < $offCounts[1]) && ($lineText = fgets($offHandle, 4096)) !== FALSE) {
    if (substr($lineText, -1) !== "\n" && !feof($offHandle)) $RefusalReason = 'a line is longer than 4 KiB';
    $lineText = rtrim($lineText, "\n");
    // / An empty line & one starting with a hash or a carriage return are skipped. A carriage return is allowed only at a line's end.
    if ($RefusalReason !== '' or $lineText === '' or $lineText[0] === '#' or $lineText[0] === "\r") continue;
    if (substr($lineText, -1) === "\r") $lineText = substr($lineText, 0, -1);
    $lineTokens = preg_split('/[ \t]+/', $lineText, -1, PREG_SPLIT_NO_EMPTY);
    if (strpos($lineText, "\r") !== FALSE) $RefusalReason = 'a line holds a carriage return before its end';
    else if ($headerMatch === array()) {
      if (!preg_match('/^(ST)?(C?)(N?)OFF$/', (string)array_shift($lineTokens), $headerMatch)) $RefusalReason = 'its header is not ST, C & N before OFF';
      else $vertexTokens = 3 + strlen($headerMatch[1]) + (3 * strlen($headerMatch[3]));
      // / The counts may be on the header's line or the next.
      if ($lineTokens === array()) continue; }
    if ($RefusalReason !== '') continue;
    if ($offCounts === array()) {
      if (count($lineTokens) !== 3 or preg_grep('/^\d{1,9}$/', $lineTokens, PREG_GREP_INVERT)) $RefusalReason = 'its counts are not three whole numbers';
      else $offCounts = array_map('intval', $lineTokens); }
    else if ($verticesRead < $offCounts[0]) {
      if (!in_array(count($lineTokens) - $vertexTokens, ($headerMatch[2] === 'C' ? array(0, 3, 4) : array(0)), TRUE)) $RefusalReason = 'vertex '.$verticesRead.' is not a line of the numbers its header gives';
      $verticesRead++; }
    else {
      $cornerCount = (int)reset($lineTokens);
      $faceIndices = array_slice($lineTokens, 1, $cornerCount);
      if (!in_array((string)reset($lineTokens), array('3', '4'), TRUE) or preg_grep('/^\d{1,9}$/', $faceIndices, PREG_GREP_INVERT) or count(array_unique(array_map('intval', $faceIndices))) !== $cornerCount or max(array_map('intval', $faceIndices)) >= $offCounts[0] or !in_array(count($lineTokens) - $cornerCount - 1, array(0, 3, 4), TRUE)) $RefusalReason = 'face '.$facesRead.' is not 3 or 4 different vertices the file holds, with a colour of 3 or 4 numbers or none';
      $facesRead++; } }
  if ($RefusalReason === '' && ($offCounts === array() or $verticesRead < $offCounts[0] or $facesRead < $offCounts[1])) $RefusalReason = 'it ends before the vertices & faces its header counts';
  if ($RefusalReason === '') $ModelIsSafe = TRUE;
  if ($offHandle !== FALSE) fclose($offHandle);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $offHandle, $lineText, $headerMatch, $offCounts, $lineTokens, $faceIndices, $vertexTokens, $verticesRead, $facesRead, $cornerCount, $modelPath);
  return array($ModelIsSafe, $RefusalReason); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether the Assimp in use reads only what HRConvert2's own build reads, from v4.3.2.
// / Accepts the program's path. Returns whether it does & the formats it reads besides, in that order.
// / HRConvert2's own build reads obj, fbx, gltf & glb, & vrm, which is glTF 2.0 by another name, & nothing else. That is what keeps
// / every other importer Assimp carries away from an upload, because Assimp chooses an importer by what a file holds where no
// / importer claims its extension. Dependency Core uses no other build, & this holds where an operator names some other program in
// / the manifest, so a distribution's Assimp is never handed an upload in its place.
// / listext opens no file, so it runs outside the sandbox, as Dependency Core asks the program its version.
function assimpReadsOnlyItsOwn($assimpBinary) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ReadsOnlyItsOwn = FALSE;
  $OtherFormats = array();
  $listOutput = $formatMatches = array();
  $listExitCode = 1;
  $listedFormat = '';
  exec(escapeshellarg((string)$assimpBinary).' listext 2>/dev/null', $listOutput, $listExitCode);
  if ($listExitCode === 0 && preg_match_all('/\*\.([A-Za-z0-9_-]+)/', implode(' ', $listOutput), $formatMatches) > 0) {
    foreach ($formatMatches[1] as $listedFormat) if (!in_array(strtolower($listedFormat), array('obj', 'fbx', 'gltf', 'glb', 'vrm'), TRUE)) $OtherFormats[] = strtolower($listedFormat);
    $ReadsOnlyItsOwn = (count($OtherFormats) === 0); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $listOutput, $formatMatches, $listExitCode, $listedFormat, $assimpBinary);
  return array($ReadsOnlyItsOwn, $OtherFormats); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to make the folders one model conversion works in, from v4.3.2.
// / Accepts the staged input's path. Returns whether all four were made, the work folder & the in, mid & out folders inside it, in that order.
// / makeWorkFolder() makes the work folder beside the staged input, new & private to the account running the conversion. in holds the
// / copy of the upload a converter reads, mid what the first of two converters writes for the second, & out what the last one writes.
// / A converter is shown the folder it reads, read only, & the one it writes, & so it never sees the user's own folder.
// / A work folder whose inner folders were not all made is still named, so the caller takes it away.
function makeModelWorkFolders($pathname) {
  // / Set variables.
  global $DirSep, $EnableMemoryProtection;
  $FoldersAreMade = FALSE;
  $WorkFolder = $InFolder = $MidFolder = $OutFolder = '';
  $folderWasMade = FALSE;
  list ($folderWasMade, $WorkFolder) = makeWorkFolder($pathname, 'model');
  if ($folderWasMade) {
    $InFolder = $WorkFolder.$DirSep.'in';
    $MidFolder = $WorkFolder.$DirSep.'mid';
    $OutFolder = $WorkFolder.$DirSep.'out';
    $FoldersAreMade = (@mkdir($InFolder, 0700) && @mkdir($MidFolder, 0700) && @mkdir($OutFolder, 0700)); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderWasMade, $pathname);
  return array($FoldersAreMade, $WorkFolder, $InFolder, $MidFolder, $OutFolder); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to move a converted model out of the folder it was written in, from v4.3.2.
// / Accepts the path the converter wrote & the path the core chose for the output, in that order.
// / Returns whether the output was placed & why it was not, in that order.
// / Only the output leaves, & a gltf's buffer with it, under the name the gltf gives it, which was chosen free before the converter ran.
// / The buffer takes its name by a link, which fails rather than replaces a name taken since, so nothing of the user's is written over.
// / Each must be a plain file, because a link placed in the user's folder would be followed when it is downloaded. Whatever else the
// / converter wrote goes with its folder, as a side file of the Collada exporter does, & as anything an exploited converter wrote would.
// / The output is renamed where both folders share a filesystem, & copied where they do not.
function placeModelOutput($workOutput, $newPathname) {
  // / Set variables.
  global $DirSep, $EnableMemoryProtection;
  $OutputIsPlaced = FALSE;
  $PlacementReason = '';
  $workBuffer = $placedBuffer = '';
  $bufferIsPlaced = FALSE;
  clearstatcache(TRUE, (string)$workOutput);
  if (!is_file((string)$workOutput) or is_link((string)$workOutput)) $PlacementReason = 'What was written is not a plain file.';
  else {
    if (strtolower(pathinfo((string)$workOutput, PATHINFO_EXTENSION)) === 'gltf') {
      $workBuffer = dirname((string)$workOutput).$DirSep.pathinfo((string)$workOutput, PATHINFO_FILENAME).'.bin';
      $placedBuffer = dirname((string)$newPathname).$DirSep.basename($workBuffer);
      clearstatcache(TRUE, $workBuffer);
      if (file_exists($workBuffer) or is_link($workBuffer)) {
        if (!is_file($workBuffer) or is_link($workBuffer)) $PlacementReason = 'The buffer written beside it is not a plain file.';
        else {
          $bufferIsPlaced = @link($workBuffer, $placedBuffer);
          clearstatcache(TRUE, $placedBuffer);
          if (!$bufferIsPlaced && !file_exists($placedBuffer) && !is_link($placedBuffer)) $bufferIsPlaced = @copy($workBuffer, $placedBuffer);
          if (!$bufferIsPlaced) $PlacementReason = basename($placedBuffer).', its buffer, was taken or could not be written.'; } } }
    if ($PlacementReason === '') {
      $OutputIsPlaced = (@rename((string)$workOutput, (string)$newPathname) or (@copy((string)$workOutput, (string)$newPathname) && @unlink((string)$workOutput)));
      if (!$OutputIsPlaced) {
        $PlacementReason = 'It could not be written there.';
        @unlink((string)$newPathname);
        // / The buffer placed a moment ago is this conversion's own, & is no use without the gltf that names it.
        if ($bufferIsPlaced) @unlink($placedBuffer); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $workBuffer, $placedBuffer, $bufferIsPlaced, $workOutput, $newPathname);
  return array($OutputIsPlaced, $PlacementReason); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert 3D model formats.
// / Each upload is read by one program, from v4.3.2. Assimp reads obj, fbx, gltf & glb, & MeshLab reads stl, ply, off, 3ds, dae, x3d
// / & ctm. Assimp writes every format it can, & MeshLab writes the rest, so the route follows from what reads the input & what
// / writes the output. Assimp alone, MeshLab alone, MeshLab before Assimp, called normalize, & Assimp before MeshLab, called bridge.
// / Between two, a model in the conversion's own folder carries it, & the second runs only where the first left one. MeshLab writes an
// / obj for Assimp. Assimp writes a 3ds for MeshLab, from v4.3.2, where it wrote an obj, because meshlabserver 2020.09, which Ubuntu
// / 24.04 packages, reads no obj at all, & a 3ds is binary, so no name in the model becomes a line or a tag MeshLab reads. A 3ds
// / carries no vertex colours, which an stl has no place for in any case.
// / MeshLab writes STL & PLY, from v4.3.2, so no conversion runs Assimp's PreTransformVertices, which its STL & PLY exporters ran.
// / A format neither reads, or neither writes, is refused before anything runs.
// / x, 3mf & dxf are written & not read, from v4.3.2. MeshLab has no reader for any of them, & the own build no importer.
// / Each converter reads a copy of the upload & writes into an empty folder, which makeModelWorkFolders() makes, & only the output
// / leaves it, under the name the core chose. Until v4.3.2 a converter was shown the user's own folder, & wrote what it liked there.
// / What an OBJ or a glTF names outside itself is checked before Assimp opens it. A refusal is a warning, because an upload caused it
// / & it was handled. An Assimp reading more than HRConvert2's own build reads is never used.
// / MeshLab's version cannot be read through PyMeshLab, so it is checked on the meshlabserver route only.
// / The function is longer than most, so it returns the six value contract, whose first value is its sanity check.
function convertModels($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer, $UsePyMeshLab, $InstLoc, $DirSep, $EnableMemoryProtection;
  // / The six value pipeline contract. Success, errors, path, extension, filename & PID.
  // / The last two keep their defaults, & $WorkerPID stays 0 unless a detached process is supervised.
  $OutputFilename = basename($newPathname);
  $WorkerPID = 0;
  $ConversionSuccess = $ConversionErrors = $meshlabBinary = $assimpBinary = $commandMayRun = FALSE;
  $foldersAreMade = $inputIsStaged = $outputIsPlaced = $readsOnlyItsOwn = $modelIsSafe = $nameWasFound = $assimpIsNeeded = $meshlabIsNeeded = $pyMeshLabInUse = $pyMeshLabIsInstalled = FALSE;
  $returnData = $assimpData = $inputExt = $outputExt = $pyMeshLabDir = $conversionRoute = $pyMeshLabInterpreter = $meshlabCommand = $assimpCommand = $lastCommand = '';
  $workFolder = $inFolder = $midFolder = $outFolder = $stagedInput = $middlePath = $workOutput = $workName = $bufferPath = $freePath = $freeName = $refusalReason = $placementReason = '';
  $assimpReads = $meshlabReads = $assimpWrites = $meshlabWrites = $meshlabEnvironment = $otherFormats = array();
  $stopper = $exitStatus = $stageStatus = $linesLeftOut = 0;
  $sleepTime = $SleepTimer;
  // / Isolate the extensions to route the model through the proper utility.
  $inputExt = strtolower(pathinfo($pathname, PATHINFO_EXTENSION));
  $outputExt = strtolower(trim((string)$extension, '.'));
  // / Which program reads each upload & which writes each output. These are extensions rather than Assimp identifiers, &
  // / buildAssimpCommand() maps the ones that differ. Assimp reads only what HRConvert2's own build has importers for, & MeshLab reads
  // / the rest, so an upload meets one reader. MeshLab reads & writes more than this, & these are the formats offered.
  $assimpReads = array('obj', 'fbx', 'gltf', 'glb');
  $meshlabReads = array('stl', 'ply', 'off', '3ds', 'dae', 'x3d', 'ctm');
  $assimpWrites = array('obj', '3ds', 'dae', 'x3d', 'fbx', 'gltf', 'glb', '3mf', 'x', 'assbin', 'json');
  $meshlabWrites = array('ply', 'stl', 'off', 'dxf', 'wrl', 'ctm');
  // / Decide the route once, before anything runs. Retrying a command that cannot succeed only delays the same failure.
  // / READING A FORMAT IS NOT THE SAME AS WRITING IT. Assimp reads more than it writes & MeshLab writes what Assimp cannot.
  if (!in_array($inputExt, $assimpReads, TRUE) && !in_array($inputExt, $meshlabReads, TRUE)) {
    $ConversionErrors = TRUE;
    errorEntry('Neither Assimp nor MeshLab reads the '.$inputExt.' format here!', 9005, FALSE); }
  else if (in_array($outputExt, $assimpWrites, TRUE)) $conversionRoute = (in_array($inputExt, $assimpReads, TRUE) ? 'assimp' : 'normalize');
  else if (in_array($outputExt, $meshlabWrites, TRUE)) $conversionRoute = (in_array($inputExt, $assimpReads, TRUE) ? 'bridge' : 'meshlab');
  else {
    $ConversionErrors = TRUE;
    errorEntry('Neither Assimp nor MeshLab can write the '.$outputExt.' format!', 9004, FALSE); }
  $assimpIsNeeded = ($conversionRoute !== '' && $conversionRoute !== 'meshlab');
  $meshlabIsNeeded = ($conversionRoute !== '' && $conversionRoute !== 'assimp');
  // / Assimp is what the manifest names, which is HRConvert2's own build, & is never used where it reads more than that build reads.
  if ($assimpIsNeeded) {
    $assimpBinary = verifiedToolPath('Assimp');
    if ($assimpBinary === FALSE) {
      $ConversionErrors = TRUE;
      $conversionRoute = '';
      errorEntry('The installed Assimp version is missing, unidentifiable, or too old!', 9001, FALSE); }
    else {
      list ($readsOnlyItsOwn, $otherFormats) = assimpReadsOnlyItsOwn($assimpBinary);
      if (!$readsOnlyItsOwn) {
        $ConversionErrors = TRUE;
        $conversionRoute = '';
        errorEntry('The Assimp in use reads '.(count($otherFormats) > 0 ? implode(', ', array_slice($otherFormats, 0, 12)).(count($otherFormats) > 12 ? ' & more' : '') : 'nothing it will name').' besides obj, fbx, gltf & glb, so it was not used!', 9008, FALSE); } } }
  // / MeshLab is found only for a route that runs it. PyMeshLab stands in for MeshLab when it is enabled, so MeshLab is required only
  // / when PyMeshLab is not, & whether PyMeshLab loads is not known until the import is attempted.
  if ($meshlabIsNeeded) {
    $meshlabBinary = verifiedToolPath('MeshLab');
    // / Kept from the check this replaces: with PyMeshLab on & MeshLab absent there is no fallback.
    if ($UsePyMeshLab && $meshlabBinary === FALSE) warningEntry('PyMeshLab is enabled & no usable MeshLab binary was found. A model conversion has no fallback if the bundle does not load.');
    // / The bundled PyMeshLab workspace, used when the binary is not.
    // / This directory is handed to sandboxCommand() as a read only resource, because the namespace binds the input & the output &
    // / nothing else. Without that bind the Python path points at a directory which does not exist inside the sandbox, import fails,
    // / & every MeshLab route fails with it. An Assimp command passes nothing, because Assimp loads nothing out of the installation.
    $pyMeshLabDir = $InstLoc.$DirSep.'Resources'.$DirSep.'PyMeshLab';
    // / A subsystem that can fall back must fall back before it errors.
    // / PyMeshLab is used only when an interpreter exists that can load it.
    // / The MeshLab binary does the same work when no such interpreter is present.
    // / A warning then says why the requested route was not taken.
    if ($UsePyMeshLab) {
      list ($pyMeshLabInterpreter, $pyMeshLabIsInstalled) = resolvePyMeshLabInterpreter($pyMeshLabDir);
      // / An installed copy needs no bundle directory. Passing one would insert a path
      // / that may not exist onto sys.path & set Qt variables pointing at nothing.
      if ($pyMeshLabIsInstalled) $pyMeshLabDir = '';
      if ($pyMeshLabInterpreter !== '') $pyMeshLabInUse = TRUE;
      else warningEntry('PyMeshLab is enabled & could not be used. Either no interpreter matching the bundled build is installed, or one is & the module did not import. Falling back to the MeshLab binary.'); }
    // / A route needing the binary with no binary installed is refused here rather than
    // / discovered inside the retry loop as a command with nothing to execute.
    if (!$pyMeshLabInUse && $meshlabBinary === FALSE) {
      $ConversionErrors = TRUE;
      $conversionRoute = '';
      errorEntry('The installed MeshLab version is missing, unidentifiable, or too old & PyMeshLab is not usable!', 9002, FALSE); } }
  // / The upload is staged in a folder of the conversion's own, & a glTF, an OBJ & an OFF are checked before a converter opens one.
  if ($conversionRoute !== '') {
    if ($Verbose) logEntry('Converting model.');
    list ($foldersAreMade, $workFolder, $inFolder, $midFolder, $outFolder) = makeModelWorkFolders($pathname);
    $stagedInput = $inFolder.$DirSep.basename($pathname);
    if (!$foldersAreMade) $inputIsStaged = FALSE;
    else if ($inputExt === 'obj') list ($inputIsStaged, $linesLeftOut, $refusalReason) = copyObjForAssimp($pathname, $stagedInput);
    else $inputIsStaged = (@link($pathname, $stagedInput) or @copy($pathname, $stagedInput));
    if ($inputIsStaged && ($inputExt === 'gltf' or $inputExt === 'glb')) list ($modelIsSafe, $refusalReason) = checkGltfReferences($stagedInput, $inputExt);
    if ($inputIsStaged && $inputExt === 'off') list ($modelIsSafe, $refusalReason) = checkOffStructure($stagedInput);
    if ($refusalReason !== '') {
      $ConversionErrors = TRUE;
      $conversionRoute = '';
      warningEntry('The model '.basename((string)$pathname).' was not converted, because '.$refusalReason.'. '.($inputExt === 'off' ? 'MeshLab' : 'Assimp').' never opened it.'); }
    else if (!$inputIsStaged) {
      $ConversionErrors = TRUE;
      $conversionRoute = '';
      errorEntry('Could not stage '.basename((string)$pathname).' for conversion!', 9006, FALSE); }
    else if ($linesLeftOut > 0) warningEntry(basename((string)$pathname).' names '.$linesLeftOut.' material librar'.($linesLeftOut === 1 ? 'y' : 'ies').' by a path rather than a plain file name, & Assimp read it without '.($linesLeftOut === 1 ? 'that line.' : 'those lines.')); }
  // / A gltf's buffer is written beside it under the gltf's own name, & the gltf names it so. The core chose a free name for the gltf,
  // / & one is chosen for its buffer here, before Assimp runs, so the gltf names the buffer it is placed beside.
  $workName = basename($newPathname);
  if ($conversionRoute !== '' && $outputExt === 'gltf') {
    $bufferPath = dirname($newPathname).$DirSep.pathinfo($newPathname, PATHINFO_FILENAME).'.bin';
    clearstatcache(TRUE, $bufferPath);
    if (file_exists($bufferPath) or is_link($bufferPath)) {
      list ($nameWasFound, $freePath, $freeName) = findFreeOutputName(dirname($newPathname).$DirSep, pathinfo($newPathname, PATHINFO_FILENAME), 'bin');
      if ($nameWasFound) $workName = pathinfo($freeName, PATHINFO_FILENAME).'.gltf';
      else {
        $ConversionErrors = TRUE;
        $conversionRoute = '';
        errorEntry('Could not stage '.basename((string)$pathname).' for conversion!', 9006, FALSE); } } }
  // / An intermediate file bridges the two utilities on any two stage route, in the mid folder, so neither sees the other's input.
  // / It is an obj MeshLab writes for Assimp, & a 3ds Assimp writes for MeshLab.
  $middlePath = $midFolder.$DirSep.'rectified_'.pathinfo($newPathname, PATHINFO_FILENAME).($conversionRoute === 'bridge' ? '.3ds' : '.obj');
  $workOutput = $outFolder.$DirSep.$workName;
  if ($conversionRoute !== '' && $Verbose) logEntry('Model route selected: '.$conversionRoute.', '.$inputExt.' to '.$outputExt.'.');
  // / Which of the two utilities will do the MeshLab half is recorded, under verbose only.
  // / It records what will run, not what was asked for.
  if ($conversionRoute !== '' && $meshlabIsNeeded && $Verbose) logEntry('MeshLab work will run through '.($pyMeshLabInUse ? 'the bundled PyMeshLab.' : 'the meshlabserver binary.'));
  // / Attempt the conversion, & stop at the first attempt that leaves a file. From v4.3.1 a converter that ran to its end
  // / & left none is not asked again, because it would leave none again, & one a signal stopped is, as pipelineRetryIsDue() decides.
  // / An attempt runs one stage or two, & is judged by the status modelAttemptStatus() gives it, so a signal stopping either is seen.
  while ($conversionRoute !== '' && pipelineRetryIsDue($workOutput, $stopper, $exitStatus)) {
    // / If the last conversion attempt failed, wait a moment before trying again.
    if ($stopper !== 0) sleep($sleepTime++);
    redeclare($exitStatus, 0);
    // / What a stopped attempt left between its stages is taken away, so an attempt reads only what the one before it finished.
    if (is_file($middlePath)) @unlink($middlePath);
    // / The first stage is MeshLab's on the meshlab & normalize routes, & Assimp's on the assimp & bridge routes. It writes the output
    // / on a one stage route, & the model between the two on a two stage route.
    if ($conversionRoute === 'meshlab' or $conversionRoute === 'normalize') {
      list ($meshlabCommand, $meshlabEnvironment) = buildMeshLabCommand($pyMeshLabInUse, $pyMeshLabDir, $meshlabBinary, $stagedInput, ($conversionRoute === 'normalize' ? $middlePath : $workOutput), $pyMeshLabInterpreter);
      list ($commandMayRun, $meshlabCommand) = sandboxCommand($meshlabCommand, $stagedInput, ($conversionRoute === 'normalize' ? $middlePath : $workOutput), FALSE, 'meshlab', $pyMeshLabDir, $meshlabEnvironment);
      if (!$commandMayRun) {
        $ConversionErrors = TRUE;
        errorEntry('Bubblewrap is missing or non functional, so this model conversion cannot be isolated!', 9003, FALSE);
        break; }
      redeclare($lastCommand, $meshlabCommand);
      list ($returnData, $stageStatus) = runPipelineCommand($meshlabCommand);
      redeclare($exitStatus, modelAttemptStatus($exitStatus, $stageStatus)); }
    else {
      $assimpCommand = buildAssimpCommand($assimpBinary, $stagedInput, ($conversionRoute === 'bridge' ? $middlePath : $workOutput), ($conversionRoute === 'bridge' ? '3ds' : $outputExt));
      list ($commandMayRun, $assimpCommand) = sandboxCommand($assimpCommand, $stagedInput, ($conversionRoute === 'bridge' ? $middlePath : $workOutput), FALSE, 'assimp');
      if (!$commandMayRun) {
        $ConversionErrors = TRUE;
        errorEntry('Bubblewrap is missing or non functional, so this model conversion cannot be isolated!', 9003, FALSE);
        break; }
      redeclare($lastCommand, $assimpCommand);
      list ($assimpData, $stageStatus) = runPipelineCommand($assimpCommand);
      redeclare($exitStatus, modelAttemptStatus($exitStatus, $stageStatus)); }
    // / What a first stage the time limit stopped or a signal ended left between the stages is not a model, from v4.3.5, so it is taken
    // / away & the second stage never reads it. The attempt is judged by the first stage's status, & pipelineRetryIsDue() says so once.
    if (($conversionRoute === 'normalize' or $conversionRoute === 'bridge') && pipelineCommandWasInterrupted($stageStatus) && is_file($middlePath)) @unlink($middlePath);
    // / The second stage reads the model the first wrote, & runs only where the first left one, because the upload is read by one program.
    // / It is sandboxed separately & is judged separately. A first stage that ran does not entitle the second one to skip the check.
    if (($conversionRoute === 'normalize' or $conversionRoute === 'bridge') && is_file($middlePath) && !is_link($middlePath)) {
      if ($conversionRoute === 'normalize') {
        $assimpCommand = buildAssimpCommand($assimpBinary, $middlePath, $workOutput, $outputExt);
        list ($commandMayRun, $assimpCommand) = sandboxCommand($assimpCommand, $middlePath, $workOutput, FALSE, 'assimp');
        if (!$commandMayRun) {
          $ConversionErrors = TRUE;
          errorEntry('Bubblewrap is missing or non functional, so this model conversion cannot be isolated!', 9003, FALSE);
          break; }
        redeclare($lastCommand, $assimpCommand);
        list ($assimpData, $stageStatus) = runPipelineCommand($assimpCommand);
        redeclare($exitStatus, modelAttemptStatus($exitStatus, $stageStatus)); }
      else {
        list ($meshlabCommand, $meshlabEnvironment) = buildMeshLabCommand($pyMeshLabInUse, $pyMeshLabDir, $meshlabBinary, $middlePath, $workOutput, $pyMeshLabInterpreter);
        list ($commandMayRun, $meshlabCommand) = sandboxCommand($meshlabCommand, $middlePath, $workOutput, FALSE, 'meshlab', $pyMeshLabDir, $meshlabEnvironment);
        if (!$commandMayRun) {
          $ConversionErrors = TRUE;
          errorEntry('Bubblewrap is missing or non functional, so this model conversion cannot be isolated!', 9003, FALSE);
          break; }
        redeclare($lastCommand, $meshlabCommand);
        list ($returnData, $stageStatus) = runPipelineCommand($meshlabCommand);
        redeclare($exitStatus, modelAttemptStatus($exitStatus, $stageStatus)); } }
    // / An output a converter that ended before it finished left may be part of one. pipelineRetryIsDue() takes it away, from v4.3.5, before
    // / the attempt is made again where that is allowed. This pipeline took it away itself until then.
    // / Count the number of conversions to avoid infinite loops.
    $stopper++; }
  // / Every attempt was made & none left a file.
  // / What the converter printed is logged below, with --Enhanced Logging Verbosity-- on.
  // / A route that could not be sandboxed has already said so, & ran nothing to judge.
  if ($conversionRoute !== '' && $commandMayRun && !is_file($workOutput)) {
    $ConversionErrors = TRUE;
    errorEntry('The model converter produced no file after '.$stopper.' attempt(s)!', 9000, FALSE);
    // / The error on its own says only that something was tried. The command is what was tried, & without it an operator has a number
    // / & no way to reproduce the failure. It names this conversion's own folder & carries no secret.
    warningEntry('The command that produced no file was: '.trim($lastCommand));
    // / A converter that produced nothing at all is worth a line of its own. Testing for output & staying silent when there is none
    // / meant the one case that needed explaining was the one case that explained nothing.
    if (trim($returnData) === '' && trim($assimpData) === '') warningEntry('Neither utility produced any output at all. The command was found & run, & it printed nothing on either stream.'); }
  // / A bundled PyMeshLab is compiled against one Python minor version.
  // / An object tagged 314 will not load under 3.12 or under 3.11.
  // / The interpreter reports that as the module being absent.
  // / An operator reading that goes looking at the path instead of at the build.
  if ($pyMeshLabInUse && !is_file($workOutput) && stripos((string)$returnData, 'pymeshlab') !== FALSE && stripos((string)$returnData, 'No module named') !== FALSE) warningEntry('PyMeshLab could not be imported. A bundled build is compiled for one Python minor version, so check that the interpreter matches the cpython tag on the object in Resources/PyMeshLab/pymeshlab. Set --Use PyMeshLab Python Bindings-- to FALSE to use the meshlabserver binary instead.');
  // / Log the output of each utility to the logfile, if it is not blank.
  if ($Verbose && trim($returnData) !== '') logEntry('Meshlab processing engine returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
  if ($Verbose && trim($assimpData) !== '') logEntry('Assimp returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($assimpData)))));
  // / Only the output leaves the work folder, & a gltf's buffer with it.
  if ($conversionRoute !== '' && is_file($workOutput)) {
    list ($outputIsPlaced, $placementReason) = placeModelOutput($workOutput, $newPathname);
    if (!$outputIsPlaced) {
      $ConversionErrors = TRUE;
      errorEntry('Could not move the converted model to '.basename((string)$newPathname).'! '.$placementReason, 9007, FALSE); } }
  // / The work folder goes whatever happened, with the copy of the upload & everything the converters left in it.
  if ($workFolder !== '') removeWorkFolder($workFolder);
  // / The output file is the only verdict on whether the conversion produced anything.
  if ($outputIsPlaced && file_exists($newPathname)) $ConversionSuccess = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $meshlabBinary, $assimpBinary, $commandMayRun, $foldersAreMade, $inputIsStaged, $outputIsPlaced, $readsOnlyItsOwn, $modelIsSafe, $nameWasFound, $assimpIsNeeded, $meshlabIsNeeded, $pyMeshLabInUse, $pyMeshLabIsInstalled, $returnData, $assimpData, $inputExt, $outputExt, $pyMeshLabDir, $conversionRoute, $pyMeshLabInterpreter, $meshlabCommand, $assimpCommand, $lastCommand, $workFolder, $inFolder, $midFolder, $outFolder, $stagedInput, $middlePath, $workOutput, $workName, $bufferPath, $freePath, $freeName, $refusalReason, $placementReason, $assimpReads, $meshlabReads, $assimpWrites, $meshlabWrites, $meshlabEnvironment, $otherFormats, $stopper, $exitStatus, $stageStatus, $linesLeftOut, $sleepTime, $pathname);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $OutputFilename, $WorkerPID); }
// / -----------------------------------------------------------------------------------

?>
