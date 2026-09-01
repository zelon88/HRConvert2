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
// / This file declares what the Scad conversion pipeline is & what it can do.
// / It is read by pipelineManager.php on EVERY request & it must stay cheap.
// / It ASSIGNS VARIABLES & DOES NOTHING ELSE. No functions, no logic, no output.
// / rror block 28000 through 28009 belongs to this pipeline. Those numbers came with the code
// / when it
// / moved out of convertCore.php & they did not change.
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
// / folder, so pipelineCore.php beside it ships & moves with this file.
$PipelineVersion = 'v3.8.5';

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

// / The function dispatch calls. THIS NAME MUST BE UNIQUE ACROSS EVERY INSTALLED PIPELINE.
// / It lives in pipelineCore.php beside this file & no longer exists in convertCore.php.
$PipelineEntryPoint = 'convertSCAD';

// / The depends.php subsystem this pipeline needs. Dependency Core owns installation.
// / OpenSCAD EXECUTES ITS INPUT. That is what the source is for & it is why this pipeline
// / carries a sanitizer no other pipeline needs. rectifySCAD() & sanitizeAllSCADUploads()
// / moved here with the converter, because convertCore.php had no other caller for either.
$PipelineSubsystem = 'openscad';

// / Shared modules this pipeline needs, loaded before its converter is loaded.
// / This pipeline needs none. Its sanitizer is its own & is used nowhere else.
$PipelineSharedModules = array();

// / The request fields this pipeline actually reads.
// / The output extension decides the render target. Nothing else is read.
$PipelineRequestFields = array('Extension');

// / What this pipeline can read & what it can write.
// / THESE LISTS ARE INFORMATIONAL WHILE $SupportedFormatDetectionType IS hardcoded-only.
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
