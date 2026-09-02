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
// / This file declares what the SVG conversion pipeline is & what it can do.
// / It is read by pipelineManager.php on EVERY request & it must stay cheap.
// / It ASSIGNS VARIABLES & DOES NOTHING ELSE. No functions, no logic, no output.
// / Error block 25000 through 25002 belongs to this pipeline.
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
// / folder, so pipelineCore.php beside it ships & moves with this file.
$PipelineVersion = 'v3.8.6';

// / What this pipeline is dispatched as. A conversion pipeline takes one file & returns
// / the six value contract. An operation pipeline takes a selection & returns its own
// / shape. An undeclared kind is a conversion pipeline.
$PipelineKind = 'conversion';

// / The family this implementation serves. The interface groups controls by this name.
$PipelineFamily = 'SVG';

// / The English fallback label. A language pack may translate it. Nothing breaks if none does.
$PipelineDisplayName = 'Vector Graphic';

// / Lower runs first when two implementations both claim a conversion.
// / SVG sits below Drawing so that a file both families could take reaches Inkscape, which
// / renders vector markup properly, rather than Dia, which imports it as shapes.
$PipelinePriority = 250;

// / The function dispatch calls. THIS NAME MUST BE UNIQUE ACROSS EVERY INSTALLED PIPELINE.
// / It lives in pipelineCore.php beside this file & no longer exists in convertCore.php.
$PipelineEntryPoint = 'convertSVG';

// / The depends.php subsystem this pipeline needs. Dependency Core owns installation.
// / This string must match a Subsystem name in depends.php exactly.
// / Naming the subsystem rather than the package keeps one source of truth for the version.
$PipelineSubsystem = 'SVG';

// / Shared modules this pipeline needs, loaded before its converter is loaded.
// / This pipeline needs none.
$PipelineSharedModules = array();

// / The request fields this pipeline actually reads.
// / This is the first pipeline that reads more than the output extension.
// / Inkscape honours an export width & an export height INDEPENDENTLY, so supplying both
// / stretches the image rather than fitting it to a box. That is the caller's decision to
// / make & the converter passes them straight through. A dimension of zero is not passed.
// / Dispatch hands over every field regardless & PHP discards the two this entry point does
// / not declare, so this list is documentation & the basis of the pipeline's sanity check.
$PipelineRequestFields = array('Extension', 'Height', 'Width');

// / What this pipeline can read & what it can write.
// / THESE LISTS ARE INFORMATIONAL WHILE $SupportedFormatDetectionType IS hardcoded-only.
// / config.php is authoritative in that mode & $UserSVGInputArray with
// / $UserSVGOutputArray decide what is offered, so an omission here cannot delete a
// / conversion. They become authoritative in the detected modes.
// / Inkscape reads far more vector formats than it writes, which is why the two lists here
// / differ far more than they do for Dia.
$Capabilities = array(
  'Input' => array('svg', 'svgz', 'ai', 'cdr', 'eps', 'ps', 'pdf', 'dxf', 'wmf', 'emf', 'cgm', 'vsd'),
  'Output' => array('svg', 'svgz', 'png', 'pdf', 'eps', 'ps', 'dxf', 'wmf', 'emf', 'tex', 'pov', 'hpgl'));

// / Pairs removed from the cross product above, written as 'input>output'.
// / A conversion that produces the format it was handed is not a conversion. Inkscape will
// / happily be asked to do it & will produce something, & that something is not what the
// / user asked for.
// / svg to svgz & svgz to svg are NOT excluded. Those are a real compression change & are
// / a conversion a user can legitimately want.
$PipelineExclude = array(
  'svg>svg', 'svgz>svgz', 'eps>eps', 'ps>ps', 'pdf>pdf', 'dxf>dxf', 'wmf>wmf', 'emf>emf');
// / -----------------------------------------------------------------------------------
?>
