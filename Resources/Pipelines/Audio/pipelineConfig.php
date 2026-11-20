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
// / This file declares what the Audio conversion pipeline is & what it can do.
// / It is read by pipelineCore.php on every request & it must stay cheap.
// / It assigns variables & does nothing else, with no functions, no logic & no output.
// / Error block 12000 through 12002 belongs to this pipeline.
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
$PipelineFamily = 'Audio';

// / The English fallback label. A language pack may translate it. Nothing breaks if none does.
$PipelineDisplayName = 'Audio';

// / Lower runs first when two implementations both claim a conversion.
// / Audio sits just below Video. A container holding both streams reaches the video
// / converter first, & a user who wanted only the audio track picks the audio menu.
$PipelinePriority = 550;

// / The function dispatch calls, whose name must be unique across every installed pipeline.
// / It is defined in pipeline.php beside this file.
$PipelineEntryPoint = 'convertAudio';

// / The depends.php subsystem this pipeline needs. Dependency Core owns installation.
// / This string must match a Subsystem name in depends.php exactly.
// / Naming the subsystem rather than the package keeps one source of truth for the version.
// / Four pipelines name this same subsystem: Audio, Video, Subtitle & Stream. All four ask
// / the manifest for FFMPEG through verifiedToolPath(), so one minimum governs every one.
$PipelineSubsystem = 'Audio, Video & Streams';

// / Shared modules this pipeline needs, loaded before its converter is loaded.
// / ffmpegDemuxers.php names the demuxers FFMPEG may read a file with, from v4.3.0.
$PipelineSharedModules = array('ffmpegDemuxers.php');

// / The request fields this pipeline actually reads.
// / A bitrate is read here & nowhere else in the application.
// / It is optional. An empty or absent bitrate means FFMPEG chooses its own.
$PipelineRequestFields = array('Extension', 'Bitrate');

// / What this pipeline can read & what it can write.
// / These lists are informational while $SupportedFormatDetectionType is hardcoded-only.
// / config.php is authoritative in that mode, so an omission here cannot delete a
// / conversion. They become authoritative in the detected modes.
// / FFMPEG reads more than this. These are the formats worth offering.
// / A video is read for its sound, from v4.2.8, so a film can become an mp3. -vn leaves its picture out. A ts is the Stream family's & is not read.
// / mp2 is written, as config.php has always offered it.
$Capabilities = array(
  'Input' => array(
    'mp3', 'wav', 'flac', 'ogg', 'oga', 'opus', 'aac', 'm4a', 'wma', 'aiff', 'aif', 'au', 'ac3',
    'amr', 'ape', 'mka', 'ra', 'voc', 'caf', 'mp4', 'm4v', 'mkv', 'webm', 'avi', 'mov', 'wmv', 'asf',
    'flv', 'mpg', 'mpeg', 'ogv', '3gp', 'vob', 'rm'),
  'Output' => array(
    'mp3', 'mp2', 'wav', 'flac', 'ogg', 'oga', 'opus', 'aac', 'm4a', 'wma', 'aiff', 'au', 'ac3', 'mka'));

// / Pairs removed from the cross product above, written as 'input>output'.
// / A conversion that produces the format it was handed is not a conversion. It re-encodes
// / a lossy format a second time & hands back something audibly worse than the original.
// / ogg to oga & oga to ogg are NOT excluded. Those are the same container under two names.
$PipelineExclude = array(
    'mp3>mp3', 'mp2>mp2', 'wav>wav', 'flac>flac', 'ogg>ogg', 'oga>oga', 'opus>opus', 'aac>aac', 'm4a>m4a',
    'wma>wma', 'aiff>aiff', 'au>au', 'ac3>ac3', 'mka>mka');

// / The tools this pipeline cannot run without, by their names in depends.php, from v4.2.1.
// / A pipeline is offered only where one tool of each entry is installed. An array in it names tools any one of which will do.
// / FFMPEG converts every audio format, so nothing is converted without it.
$PipelineRequiredDependencies = array('FFMPEG');
// / -----------------------------------------------------------------------------------
?>
