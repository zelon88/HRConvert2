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
// / v4.3.0.
// / This file declares what the Image conversion pipeline is & what it can do.
// / It is read by pipelineCore.php on every request & it must stay cheap.
// / It assigns variables & does nothing else, with no functions, no logic & no output.
// / Error block 8000 through 8002 belongs to this pipeline.
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
$PipelineVersion = 'v4.3.0';

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

// / The function dispatch calls, whose name must be unique across every installed pipeline.
// / It is defined in pipeline.php beside this file.
$PipelineEntryPoint = 'convertImages';

// / The depends.php subsystem this pipeline needs. Dependency Core owns installation.
// / This string must match a Subsystem name in depends.php exactly.
// / Naming the subsystem rather than the package keeps one source of truth for the version.
// / ImageMagick is also used by the OCR pipeline, which rasterizes a PDF page with it.
// / Both name the same subsystem rather than restating the dependency.
$PipelineSubsystem = 'Images';

// / Shared modules this pipeline needs, loaded before its converter is loaded.
// / imageMagickDecoders.php names the decoder ImageMagick reads an image with, from v4.3.0.
$PipelineSharedModules = array('imageMagickDecoders.php');

// / The request fields this pipeline actually reads.
// / This pipeline reads three request fields & is the reason they exist in the signature.
// / A height & a width resize, & a rotation turns. All three are optional & a value that is
// / zero, empty or not numeric is not passed to ImageMagick at all.
$PipelineRequestFields = array('Extension', 'Height', 'Width', 'Rotate');

// / What this pipeline can read & what it can write.
// / These lists are informational while $SupportedFormatDetectionType is hardcoded-only.
// / config.php is authoritative in that mode, so an omission here cannot delete a
// / conversion. They become authoritative in the detected modes.
// / ImageMagick reads far more than this. These are the formats a general purpose
// / converter can hand back to a browser & expect to be usable.
// / SVG & EPS are not read, from v4.2.7. The policy this pipeline carries refuses both, because an SVG draws any image it names &
// / a PostScript file is a program. An SVG is the SVG pipeline's, & EPS is still written.
// / JPEG XL, jpe, Cineon, DIB & the JPEG 2000 codestream are read & written, & HEIF is read, from v4.2.8.
// / HEIF & AVIF are written only where libheif has an encoder for them, & ImageMagick is told the format it writes, so one without fails.
// / HEIC, HEIF & EXR are written, & EXR & the camera raw formats read, from v4.3.0, where ImageMagick is built with libheif's encoders,
// / OpenEXR & LibRaw, as Documentation/Build/build-imagemagick-v7.sh builds it. LibRaw only reads.
$Capabilities = array(
  'Input' => array(
    'jpg', 'jpeg', 'jpe', 'png', 'gif', 'bmp', 'dib', 'tif', 'tiff', 'webp', 'ico', 'tga', 'pcx', 'ppm', 'pgm',
    'pbm', 'pnm', 'xpm', 'xbm', 'dds', 'psd', 'pdf', 'heic', 'heif', 'avif', 'jp2', 'j2k', 'jxl', 'dpx', 'cin',
    'exr', 'hdr', 'cr2', 'nef', 'arw', 'dng'),
  'Output' => array(
    'jpg', 'jpeg', 'jpe', 'png', 'gif', 'bmp', 'dib', 'tif', 'tiff', 'webp', 'ico', 'tga', 'pcx', 'ppm', 'pgm',
    'pbm', 'pnm', 'xpm', 'dds', 'pdf', 'eps', 'avif', 'heic', 'heif', 'jp2', 'j2k', 'jxl', 'cin', 'hdr', 'exr'));

// / What this pipeline shells out to. Names must match the manifest exactly.
$PipelineDependencies = array('ImageMagick', 'Bubblewrap');

// / Pairs removed from the cross product above, written as 'input>output'.
// / A conversion that produces the format it was handed is not a conversion. It re-encodes
// / a lossy format a second time & hands back something visibly worse than the original.
// / jpg to jpeg & jpeg to jpg are NOT excluded. Those are the same format under two names &
// / a user renaming one to the other is doing something reasonable.
$PipelineExclude = array(
    'jpg>jpg', 'jpeg>jpeg', 'jpe>jpe', 'png>png', 'gif>gif', 'bmp>bmp', 'dib>dib', 'tif>tif', 'tiff>tiff', 'webp>webp',
    'ico>ico', 'tga>tga', 'pcx>pcx', 'ppm>ppm', 'pgm>pgm', 'pbm>pbm', 'pnm>pnm', 'xpm>xpm', 'dds>dds', 'pdf>pdf', 'eps>eps',
    'avif>avif', 'heic>heic', 'heif>heif', 'jp2>jp2', 'j2k>j2k', 'jxl>jxl', 'cin>cin', 'hdr>hdr', 'exr>exr');

// / The tools this pipeline cannot run without, by their names in depends.php, from v4.2.1.
// / A pipeline is offered only where one tool of each entry is installed. An array in it names tools any one of which will do.
// / ImageMagick converts every image, so nothing is converted without it.
$PipelineRequiredDependencies = array('ImageMagick');
// / -----------------------------------------------------------------------------------
?>
