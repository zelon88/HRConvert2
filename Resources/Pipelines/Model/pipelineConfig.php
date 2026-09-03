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
// / v3.8.8.
// / This file declares what the Model conversion pipeline is & what it can do.
// / It is read by pipelineCore.php on EVERY request & it must stay cheap.
// / It ASSIGNS VARIABLES & DOES NOTHING ELSE. No functions, no logic, no output.
// / Error block 9000 through 9004 belongs to this pipeline.
// / Those numbers came with the code when it moved out of convertCore.php.
// / They did not change, because operators have already read them.
// / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt for what each declaration means.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline configuration cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The version of this pipeline folder. Read WITHOUT executing this file, then matched
// / EXACTLY against the pin in getAcceptedPipelines(). This version covers the whole
// / folder, so pipeline.php beside it ships & moves with this file.
$PipelineVersion = 'v3.8.8';

// / What this pipeline is dispatched as. A conversion pipeline takes one file & returns
// / the six value contract. An operation pipeline takes a selection & returns its own
// / shape. An undeclared kind is a conversion pipeline.
$PipelineKind = 'conversion';

// / The family this implementation serves. The interface groups controls by this name.
$PipelineFamily = 'Model';

// / The English fallback label. A language pack may translate it. Nothing breaks if none does.
$PipelineDisplayName = '3D Model';

// / Lower runs first when two implementations both claim a conversion.
// / No other family currently claims a mesh format, so this number only has to be stable.
$PipelinePriority = 450;

// / The function dispatch calls. THIS NAME MUST BE UNIQUE ACROSS EVERY INSTALLED PIPELINE.
// / It lives in pipeline.php beside this file & no longer exists in convertCore.php.
$PipelineEntryPoint = 'convertModels';

// / The depends.php subsystem this pipeline needs. Dependency Core owns installation.
// / This string must match a Subsystem name in depends.php exactly.
// / Naming the subsystem rather than the package keeps one source of truth for the version.
// / Two binaries serve this family & only one is named here.
// / Assimp converts between mesh formats & MeshLab handles what Assimp will not. Assimp is
// / named because it is the one without which nothing here works at all, & verifyModelVersions()
// / in convertCore.php is what actually gates both.
$PipelineSubsystem = '3D Models';

// / Shared modules this pipeline needs, loaded before its converter is loaded.
// / This pipeline needs none.
$PipelineSharedModules = array();

// / The request fields this pipeline actually reads.
// / A mesh conversion is decided entirely by the output extension. A height, a width, a
// / rotation & a bitrate are all handed over by dispatch & all four are discarded by PHP.
$PipelineRequestFields = array('Extension');

// / What this pipeline can read & what it can write.
// / THESE LISTS ARE INFORMATIONAL WHILE $SupportedFormatDetectionType IS hardcoded-only.
// / config.php is authoritative in that mode, so an omission here cannot delete a
// / conversion. They become authoritative in the detected modes.
// / Assimp & MeshLab between them read more than this. These are the formats worth
// / offering from a web interface.
$Capabilities = array(
  'Input' => array(
    'obj', 'stl', 'ply', 'dae', 'fbx', '3ds', 'blend', 'gltf', 'glb', 'x3d', 'off', 'lwo',
    'ms3d', 'ac', 'b3d', 'q3d', 'irr', 'md2', 'md3', 'md5mesh', 'smd', 'ase', 'dxf', 'raw'),
  // / A format here that neither Assimp nor MeshLab can WRITE is refused at conversion time
  // / with error 9004. Reading a format is not the same as writing it, & Assimp reads
  // / several it cannot produce. The writer lists live in pipeline.php beside the route
  // / selection that consults them.
  'Output' => array(
    'obj', 'stl', 'ply', 'dae', 'fbx', 'gltf', 'glb', 'x3d', 'off', '3ds', 'assbin', 'json'));

// / Pairs removed from the cross product above, written as 'input>output'.
// / A conversion that produces the format it was handed is not a conversion.
$PipelineExclude = array(
    'obj>obj', 'stl>stl', 'ply>ply', 'dae>dae', 'fbx>fbx', 'gltf>gltf', 'glb>glb', 'x3d>x3d',
    'off>off', '3ds>3ds');
// / -----------------------------------------------------------------------------------
?>
