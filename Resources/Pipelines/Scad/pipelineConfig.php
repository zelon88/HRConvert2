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
// / This file declares what the Scad conversion pipeline is & what it can do.
// / It is read by pipelineCore.php on every request & it must stay cheap.
// / It assigns variables & does nothing else, with no functions, no logic & no output.
// / Error block 27000 through 27009 belongs to this pipeline.
// / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt for what each declaration means.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline configuration cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The version of this pipeline folder. Read without executing this file, then matched
// / exactly against the pin in getAcceptedPipelines(). This version covers the whole
// / folder, so pipeline.php beside it ships & moves with this file.
$PipelineVersion = 'v4.0.8';

// / What this pipeline is dispatched as. A conversion pipeline takes one file & returns
// / the six value contract. An operation pipeline takes a selection & returns its own
// / shape. An undeclared kind is a conversion pipeline.
$PipelineKind = 'conversion';

// / The family this implementation serves. The interface groups controls by this name.
$PipelineFamily = 'Scad';

// / The English fallback label. A language pack may translate it. Nothing breaks if none does.
$PipelineDisplayName = 'OpenSCAD';

// / Lower runs first when two implementations both claim a conversion.
// / Scad sits FIRST of every family. A .scad file is plain text & an installation that
// / permitted scad in the document family would otherwise have the outcome decided by
// / directory read order. Source that is about to be executed must never be mistaken for
// / a document to reflow.
$PipelinePriority = 150;

// / The function dispatch calls, whose name must be unique across every installed pipeline.
// / It is defined in pipeline.php beside this file.
$PipelineEntryPoint = 'convertSCAD';

// / The depends.php subsystem this pipeline needs. Dependency Core owns installation.
// / This string must match a Subsystem name in depends.php exactly.
// / Naming the subsystem rather than the package keeps one source of truth for the version.
// / OpenSCAD executes its input, because that is what the source is for.
// / That is why this pipeline carries a sanitizer no other pipeline needs.
$PipelineSubsystem = 'SCAD';

// / Shared modules this pipeline needs, loaded before its converter is loaded.
// / This pipeline needs none. Its sanitizer is its own & is used nowhere else.
$PipelineSharedModules = array();

// / The request fields this pipeline actually reads.
// / The output extension decides the render target. Nothing else is read.
$PipelineRequestFields = array('Extension');

// / What this pipeline can read & what it can write.
// / These lists are informational while $SupportedFormatDetectionType is hardcoded-only.
// / config.php is authoritative in that mode, so an omission here cannot delete a
// / conversion. They become authoritative in the detected modes.
// / scad is the only input. Everything here is a render target rather than a conversion
// / in the usual sense, which is why the two lists have nothing in common.
$Capabilities = array(
  'Input' => array(
    'scad'),
  'Output' => array(
    'stl', 'off', 'amf', '3mf', 'csg', 'dxf', 'svg', 'png', 'pdf'));

// / Pairs removed from the cross product above, written as 'input>output'.
// / Nothing is excluded. No output format here can also be an input, so the cross product
// / has no nonsense pair in it to remove.
$PipelineExclude = array();
// / -----------------------------------------------------------------------------------
?>
