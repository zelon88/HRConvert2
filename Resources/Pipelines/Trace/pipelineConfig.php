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
// / v4.2.7.
// / This file declares what the Trace conversion pipeline is & what it can do.
// / It is read by pipelineCore.php on every request & it must stay cheap.
// / It assigns variables & does nothing else, with no functions, no logic & no output.
// / Error block 46000 through 46006 belongs to this pipeline.
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
// / folder, so pipeline.php & the Policy folder beside it ship & move with this file.
$PipelineVersion = 'v4.2.7';

// / What this pipeline is dispatched as. A conversion pipeline takes one file & returns
// / the six value contract.
$PipelineKind = 'conversion';

// / The family this implementation serves. The interface groups controls by this name.
$PipelineFamily = 'Trace';

// / The English fallback label. A language pack may translate it. Nothing breaks if none does.
$PipelineDisplayName = 'Traced Image';

// / Lower runs first when two implementations both claim a conversion. This is the only one.
$PipelinePriority = 300;

// / The function dispatch calls, whose name must be unique across every installed pipeline.
// / It is defined in pipeline.php beside this file.
$PipelineEntryPoint = 'traceImages';

// / The depends.php subsystem this pipeline needs. Dependency Core owns installation.
// / This string must match a Subsystem name in depends.php exactly.
// / ImageMagick belongs to the Images subsystem, which the Image pipeline names, so it is not restated here.
$PipelineSubsystem = 'Tracing';

// / Shared modules this pipeline needs, loaded before its converter is loaded.
// / An image ImageMagick reduces first is reduced in a folder of its own, which workFolder.php makes & takes away.
$PipelineSharedModules = array('workFolder.php');

// / The request fields this pipeline actually reads.
// / Dispatch passes every field regardless & PHP discards what the entry point did not
// / declare, so this is documentation & the basis of the pipeline's own sanity check.
$PipelineRequestFields = array('Extension');

// / What this pipeline can read & what it can write, treated as a cross product.
// / config.php is authoritative over what is offered, & $UserTraceInputArray with $UserTraceOutputArray decide it.
// / Potrace reads BMP & the PNM formats itself. ImageMagick reduces every other image to a grey bitmap first, by the decoder its extension names,
// / under the policy this pipeline carries in Policy/policy.xml. Potrace draws the dark parts of the bitmap as filled outlines, so a photograph
// / comes out as black & white shapes, & a logo, a scan of a drawing or a signature comes out as the drawing it was.
$Capabilities = array(
  'Input' => array('bmp', 'pbm', 'pgm', 'ppm', 'pnm', 'png', 'jpg', 'jpeg', 'gif', 'tif', 'tiff', 'webp'),
  'Output' => array('svg', 'dxf', 'pdf', 'eps', 'ps'));

// / Pairs removed from the cross product above. A bitmap never becomes a bitmap here, so there are none.
$PipelineExclude = array();

// / What this pipeline shells out to. Names must match the manifest exactly.
// / ImageMagick is asked only for an image Potrace cannot read itself, so a server without it still traces BMP & the PNM formats.
$PipelineDependencies = array('Potrace', 'ImageMagick', 'Bubblewrap');

// / The tools this pipeline cannot run without, by their names in depends.php.
// / A pipeline is offered only where one tool of each entry is installed.
// / Potrace draws every outline, so nothing is traced without it.
$PipelineRequiredDependencies = array('Potrace');
// / -----------------------------------------------------------------------------------
?>
