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
// / This file declares what the Image conversion pipeline is & what it can do.
// / It is read by pipelineCore.php on EVERY request & it must stay cheap.
// / It ASSIGNS VARIABLES & DOES NOTHING ELSE. No functions, no logic, no output.
// / Error block 8000 through 8002 belongs to this pipeline.
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
$PipelineFamily = 'Image';

// / The English fallback label. A language pack may translate it. Nothing breaks if none does.
$PipelineDisplayName = 'Image';

// / Lower runs first when two implementations both claim a conversion.
// / Image sits below Video & Document & above SVG. A raster format claimed by nothing
// / else reaches ImageMagick, & a vector format reaches Inkscape first because SVG is
// / numerically ahead of it.
$PipelinePriority = 350;

// / The function dispatch calls. THIS NAME MUST BE UNIQUE ACROSS EVERY INSTALLED PIPELINE.
// / It lives in pipeline.php beside this file & no longer exists in convertCore.php.
$PipelineEntryPoint = 'convertImages';

// / The depends.php subsystem this pipeline needs. Dependency Core owns installation.
// / This string must match a Subsystem name in depends.php exactly.
// / Naming the subsystem rather than the package keeps one source of truth for the version.
// / ImageMagick is also used by the OCR pipeline, which rasterizes a PDF page with it.
// / Both name the same subsystem rather than restating the dependency.
$PipelineSubsystem = 'Images';

// / Shared modules this pipeline needs, loaded before its converter is loaded.
// / This pipeline needs none. It talks to one binary & keeps its own company.
$PipelineSharedModules = array();

// / The request fields this pipeline actually reads.
// / This pipeline reads three request fields & is the reason they exist in the signature.
// / A height & a width resize, & a rotation turns. All three are optional & a value that is
// / zero, empty or not numeric is not passed to ImageMagick at all.
$PipelineRequestFields = array('Extension', 'Height', 'Width', 'Rotate');

// / What this pipeline can read & what it can write.
// / THESE LISTS ARE INFORMATIONAL WHILE $SupportedFormatDetectionType IS hardcoded-only.
// / config.php is authoritative in that mode, so an omission here cannot delete a
// / conversion. They become authoritative in the detected modes.
// / ImageMagick reads far more than this. These are the formats a general purpose
// / converter can hand back to a browser & expect to be usable.
$Capabilities = array(
  'Input' => array(
    'jpg', 'jpeg', 'png', 'gif', 'bmp', 'tif', 'tiff', 'webp', 'ico', 'tga', 'pcx', 'ppm', 'pgm',
    'pbm', 'pnm', 'xpm', 'xbm', 'dds', 'psd', 'svg', 'eps', 'pdf', 'heic', 'avif', 'jp2', 'dpx',
    'exr', 'hdr', 'cr2', 'nef', 'arw', 'dng'),
  'Output' => array(
    'jpg', 'jpeg', 'png', 'gif', 'bmp', 'tif', 'tiff', 'webp', 'ico', 'tga', 'pcx', 'ppm', 'pgm',
    'pbm', 'pnm', 'xpm', 'dds', 'pdf', 'eps', 'avif', 'jp2', 'hdr'));

// / Pairs removed from the cross product above, written as 'input>output'.
// / A conversion that produces the format it was handed is not a conversion. It re-encodes
// / a lossy format a second time & hands back something visibly worse than the original.
// / jpg to jpeg & jpeg to jpg are NOT excluded. Those are the same format under two names &
// / a user renaming one to the other is doing something reasonable.
$PipelineExclude = array(
    'jpg>jpg', 'jpeg>jpeg', 'png>png', 'gif>gif', 'bmp>bmp', 'tif>tif', 'tiff>tiff', 'webp>webp',
    'ico>ico', 'tga>tga', 'pcx>pcx', 'dds>dds', 'eps>eps', 'avif>avif', 'jp2>jp2');
// / -----------------------------------------------------------------------------------
?>
