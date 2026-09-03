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
// / This file declares what the Drawing conversion pipeline is & what it can do.
// / It is read by pipelineCore.php on EVERY request & it must stay cheap.
// / It ASSIGNS VARIABLES & DOES NOTHING ELSE. No functions, no logic, no output.
// / Error block 10000 through 10002 belongs to this pipeline.
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
$PipelineFamily = 'Drawing';

// / The English fallback label. A language pack may translate it. Nothing breaks if none does.
$PipelineDisplayName = 'Drawing';

// / Lower runs first when two implementations both claim a conversion.
// / Drawing sits above SVG because Dia exports SVG without truly rendering it, so a file
// / both families could take should reach Inkscape rather than Dia. The two do not
// / currently overlap, & this ordering is here so that they cannot start to by accident.
$PipelinePriority = 300;

// / The function dispatch calls. THIS NAME MUST BE UNIQUE ACROSS EVERY INSTALLED PIPELINE.
// / It lives in pipeline.php beside this file & no longer exists in convertCore.php.
$PipelineEntryPoint = 'convertDrawings';

// / The depends.php subsystem this pipeline needs. Dependency Core owns installation.
// / This string must match a Subsystem name in depends.php exactly.
// / Naming the subsystem rather than the package keeps one source of truth for the version.
$PipelineSubsystem = 'Drawings';

// / Shared modules this pipeline needs, loaded before its converter is loaded.
// / This pipeline needs none.
$PipelineSharedModules = array();

// / The request fields this pipeline actually reads.
// / Dispatch passes every field regardless & PHP discards what the entry point did not
// / declare, so this is documentation & the basis of the pipeline's own sanity check.
$PipelineRequestFields = array('Extension');

// / What this pipeline can read & what it can write.
// / THESE LISTS ARE INFORMATIONAL WHILE $SupportedFormatDetectionType IS hardcoded-only.
// / config.php is authoritative in that mode & $UserDrawingArray decides what is offered,
// / so an omission here cannot delete a conversion. They become authoritative in the
// / detected modes, which is what they are for.
// / Dia is a single utility reading & writing the same diagram family in both directions,
// / which is why one array would almost do. They are kept separate because Dia writes
// / several raster & page formats it cannot read back.
$Capabilities = array(
  'Input' => array('dia', 'dxf', 'fig', 'vdx', 'wpg', 'shape', 'cgm'),
  'Output' => array('dia', 'dxf', 'fig', 'vdx', 'wpg', 'shape', 'cgm', 'png', 'jpg', 'bmp', 'tif', 'eps', 'pdf', 'svg', 'wmf', 'emf', 'plt', 'tex'));

// / Pairs removed from the cross product above, written as 'input>output'.
// / A conversion that produces the format it was handed is not a conversion. Dia will
// / happily be asked to do it & will produce something, & that something is not what the
// / user asked for.
// / svg is an OUTPUT here & deliberately not an input. Dia can open an SVG & will make a
// / poor job of it, because it imports the markup as shapes rather than rendering it.
// / Inkscape owns that direction & the SVG pipeline is where it belongs.
$PipelineExclude = array(
  'dia>dia', 'dxf>dxf', 'fig>fig', 'vdx>vdx', 'wpg>wpg', 'shape>shape', 'cgm>cgm');
// / -----------------------------------------------------------------------------------
?>
