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
// / This file declares what the Stream conversion pipeline is & what it can do.
// / It is read by pipelineCore.php on every request & it must stay cheap.
// / It assigns variables & does nothing else, with no functions, no logic & no output.
// / Error block 21000 through 21002 belongs to this pipeline.
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
// / A detached worker pipeline is still a conversion pipeline.
// / There is no third kind & there does not need to be. This one returns the same six
// / values as every other conversion pipeline. The only difference is that the sixth value
// / is not zero, & everything the core does about that is described in contract 5.
$PipelineKind = 'conversion';

// / The family this implementation serves. The interface groups controls by this name.
$PipelineFamily = 'Stream';

// / The English fallback label. A language pack may translate it. Nothing breaks if none does.
$PipelineDisplayName = 'Stream';

// / Lower runs first when two implementations both claim a conversion.
// / Stream sits last of every family & that is load bearing.
// / Its outputs are ordinary audio & video containers, & an administrator may list what it
// / reads as an ordinary video too, as a ts can be. A local file must always reach the
// / converter that reads a file, never the one that fetches a URL, unless a request names
// / Stream. convertFiles() is what enforces this, by keeping Stream at the end of its hand
// / ordered family maps & taking the first family that matches.
// / Until v4.3.0 the Stream family read & wrote every audio & video format, so an mp4 or an
// / mp3 was claimed by the Video or Audio family as well as by this one. This number does NOT enforce it, because
// / resolvePipelineCandidates() filters on the family before it reads a priority, so a
// / priority only orders the implementations inside one family.
$PipelinePriority = 900;

// / The function dispatch calls, whose name must be unique across every installed pipeline.
// / It is defined in pipeline.php beside this file.
$PipelineEntryPoint = 'convertStreams';

// / The depends.php subsystem this pipeline needs. Dependency Core owns installation.
// / This string must match a Subsystem name in depends.php exactly.
// / Naming the subsystem rather than the package keeps one source of truth for the version.
// / Four families name this subsystem: Stream, Video, Subtitle & Audio.
// / Stream asks for FFMPEG by manifest name like every other pipeline.
// / The manifest owns the minimum, 6.1, which is what Stream needs.
$PipelineSubsystem = 'Audio, Video & Streams';

// / Shared modules this pipeline needs, loaded before its converter is loaded.
// / ffmpegDemuxers.php names the demuxers FFMPEG may read a stream & its segments with, from v4.3.0.
// / streamFileWalker() is defined in pipeline.php & used nowhere else.
$PipelineSharedModules = array('ffmpegDemuxers.php');

// / The request fields this pipeline actually reads.
// / A stream is decided by its target & its output extension. A height, a width, a
// / rotation & a bitrate are all handed over by dispatch & all four are discarded by PHP.
$PipelineRequestFields = array('Extension');

// / What this pipeline can read & what it can write.
// / These lists are informational while $SupportedFormatDetectionType is hardcoded-only.
// / config.php is authoritative in that mode & $UserStreamArray decides what is offered.
// / The input list here is not a file format list in the usual sense.
// / A stream input is a playlist or manifest naming media held somewhere else, which is
// / why streamFileWalker() inspects it before FFMPEG is ever launched at it.
$Capabilities = array(
  'Input' => array('m3u', 'm3u8', 'pls', 'mpd', 'asx', 'xspf'),
  'Output' => array('3gp', 'mkv', 'avi', 'mp4', 'mpeg', 'wmv', 'mov', 'm4v', 'ogv', 'webm', 'flv', 'ts', 'mpg',
    'mp3', 'aac', 'ogg', 'wma', 'mp2', 'flac', 'm4a', 'wav', 'oga', 'opus', 'aiff', 'au', 'ac3', 'mka'));

// / Pairs removed from the cross product above, written as 'input>output'.
// / No output format declared here is also an input. config.php offers a ts as a stream, though, & the Video family writes one from
// / v4.2.8, so the stream menu offers a ts as a ts, & a ts is not converted to itself, nor a playlist, as no format is.
$PipelineExclude = array('ts>ts', 'm3u8>m3u8');

// / The tools this pipeline cannot run without, by their names in depends.php, from v4.2.1.
// / A pipeline is offered only where one tool of each entry is installed. An array in it names tools any one of which will do.
// / FFMPEG captures & converts every stream, so nothing is converted without it.
$PipelineRequiredDependencies = array('FFMPEG');
// / -----------------------------------------------------------------------------------
?>
