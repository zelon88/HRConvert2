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
// / This file declares what the Stream conversion pipeline is & what it can do.
// / It is read by pipelineManager.php on EVERY request & it must stay cheap.
// / It ASSIGNS VARIABLES & DOES NOTHING ELSE. No functions, no logic, no output.
// / Error block 14000 through 14001 & 26000 through 26007 belong to this pipeline. Those
// / numbers came with the code when it moved out of convertCore.php & they did not change.
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
// / A DETACHED WORKER PIPELINE IS STILL A CONVERSION PIPELINE.
// / There is no third kind & there does not need to be. This one returns the same six
// / values as every other conversion pipeline. The only difference is that the sixth value
// / is not zero, & everything the core does about that is described in contract 5.
$PipelineKind = 'conversion';

// / The family this implementation serves. The interface groups controls by this name.
$PipelineFamily = 'Stream';

// / The English fallback label. A language pack may translate it. Nothing breaks if none does.
$PipelineDisplayName = 'Stream';

// / Lower runs first when two implementations both claim a conversion.
// / STREAM SITS LAST OF EVERY FAMILY & THAT IS LOAD BEARING.
// / Its outputs are ordinary audio & video containers, so an mp4 or an mp3 is claimed by
// / the Video or Audio family as well as by this one. A local file must always reach the
// / converter that reads a file, never the one that fetches a URL. Before this pipeline
// / existed, convertFiles() enforced this by keeping Stream at the end of a hand ordered
// / array & saying so in a comment. This number is that comment made enforceable.
$PipelinePriority = 900;

// / The function dispatch calls. THIS NAME MUST BE UNIQUE ACROSS EVERY INSTALLED PIPELINE.
// / It lives in pipelineCore.php beside this file & no longer exists in convertCore.php.
$PipelineEntryPoint = 'convertStreams';

// / The depends.php subsystem this pipeline needs. Dependency Core owns installation.
// / FOUR FAMILIES NAME THIS SUBSYSTEM. Stream, Video, Subtitle & Audio.
// / Stream is gated on $MinimumStreamFFMPEGVersion rather than $MinimumFFMPEGVersion,
// / because it is the only one of the four that fetches a remote URL & the protocol
// / handling it relies on is newer than the file reading the others need.
$PipelineSubsystem = 'ffmpeg';

// / Shared modules this pipeline needs, loaded before its converter is loaded.
// / This pipeline needs none. streamFileWalker() moved here with it & is used nowhere else.
$PipelineSharedModules = array();

// / The request fields this pipeline actually reads.
// / A stream is decided by its target & its output extension. A height, a width, a
// / rotation & a bitrate are all handed over by dispatch & all four are discarded by PHP.
$PipelineRequestFields = array('Extension');

// / What this pipeline can read & what it can write.
// / THESE LISTS ARE INFORMATIONAL WHILE $SupportedFormatDetectionType IS hardcoded-only.
// / config.php is authoritative in that mode & $UserStreamArray decides what is offered.
// / THE INPUT LIST HERE IS NOT A FILE FORMAT LIST IN THE USUAL SENSE.
// / A stream input is a playlist or manifest naming media held somewhere else, which is
// / why streamFileWalker() inspects it before FFMPEG is ever launched at it.
$Capabilities = array(
  'Input' => array('m3u', 'm3u8', 'pls', 'mpd', 'asx', 'xspf'),
  'Output' => array('mp4', 'mkv', 'webm', 'ts', 'mp3', 'aac', 'm4a', 'flv'));

// / Pairs removed from the cross product above, written as 'input>output'.
// / Nothing is excluded. No output format here can also be an input, so the cross product
// / contains no nonsense pair to remove.
$PipelineExclude = array();
// / -----------------------------------------------------------------------------------
?>
