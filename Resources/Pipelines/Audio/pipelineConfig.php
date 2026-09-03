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
// / This file declares what the Audio conversion pipeline is & what it can do.
// / It is read by pipelineCore.php on EVERY request & it must stay cheap.
// / It ASSIGNS VARIABLES & DOES NOTHING ELSE. No functions, no logic, no output.
// / Error block 12000 through 12002 belongs to this pipeline.
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
$PipelineFamily = 'Audio';

// / The English fallback label. A language pack may translate it. Nothing breaks if none does.
$PipelineDisplayName = 'Audio';

// / Lower runs first when two implementations both claim a conversion.
// / Audio sits just below Video. A container holding both streams reaches the video
// / converter first, & a user who wanted only the audio track picks the audio menu.
$PipelinePriority = 550;

// / The function dispatch calls. THIS NAME MUST BE UNIQUE ACROSS EVERY INSTALLED PIPELINE.
// / It lives in pipeline.php beside this file & no longer exists in convertCore.php.
$PipelineEntryPoint = 'convertAudio';

// / The depends.php subsystem this pipeline needs. Dependency Core owns installation.
// / This string must match a Subsystem name in depends.php exactly.
// / Naming the subsystem rather than the package keeps one source of truth for the version.
// / FOUR FAMILIES NAME THIS SAME SUBSYSTEM. Audio, Video & Subtitle are pipelines & Stream
// / is still built into convertCore.php, so one installation reaches the same binary from
// / two places. verifyFFMPEGVersion() stays core owned & is the single gate all four use.
$PipelineSubsystem = 'Audio, Video & Streams';

// / Shared modules this pipeline needs, loaded before its converter is loaded.
// / This pipeline needs none.
$PipelineSharedModules = array();

// / The request fields this pipeline actually reads.
// / A bitrate is read here & nowhere else in the application.
// / It is optional. An empty or absent bitrate means FFMPEG chooses its own.
$PipelineRequestFields = array('Extension', 'Bitrate');

// / What this pipeline can read & what it can write.
// / THESE LISTS ARE INFORMATIONAL WHILE $SupportedFormatDetectionType IS hardcoded-only.
// / config.php is authoritative in that mode, so an omission here cannot delete a
// / conversion. They become authoritative in the detected modes.
// / FFMPEG reads more than this. These are the formats worth offering.
$Capabilities = array(
  'Input' => array(
    'mp3', 'wav', 'flac', 'ogg', 'oga', 'opus', 'aac', 'm4a', 'wma', 'aiff', 'aif', 'au', 'ac3',
    'amr', 'ape', 'mka', 'ra', 'voc', 'caf'),
  'Output' => array(
    'mp3', 'wav', 'flac', 'ogg', 'oga', 'opus', 'aac', 'm4a', 'wma', 'aiff', 'au', 'ac3', 'mka'));

// / Pairs removed from the cross product above, written as 'input>output'.
// / A conversion that produces the format it was handed is not a conversion. It re-encodes
// / a lossy format a second time & hands back something audibly worse than the original.
// / ogg to oga & oga to ogg are NOT excluded. Those are the same container under two names.
$PipelineExclude = array(
    'mp3>mp3', 'wav>wav', 'flac>flac', 'ogg>ogg', 'oga>oga', 'opus>opus', 'aac>aac', 'm4a>m4a',
    'wma>wma', 'aiff>aiff', 'au>au', 'ac3>ac3', 'mka>mka');
// / -----------------------------------------------------------------------------------
?>
