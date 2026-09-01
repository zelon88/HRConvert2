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
// / This file declares what the Video conversion pipeline is & what it can do.
// / It is read by pipelineManager.php on EVERY request & it must stay cheap.
// / It ASSIGNS VARIABLES & DOES NOTHING ELSE. No functions, no logic, no output.
// / Error block 11000 through 11002 belongs to this pipeline. Those numbers came with the
// / code when it moved out of convertCore.php & they did not change.
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

// / The family this implementation serves. The interface groups controls by this name.
$PipelineFamily = 'Video';

// / The English fallback label. A language pack may translate it. Nothing breaks if none does.
$PipelineDisplayName = 'Video';

// / Lower runs first when two implementations both claim a conversion.
// / Video sits above Audio so that a container holding both streams is offered to the
// / video converter first. A user who wanted only the audio track picks the audio menu,
// / which is a separate family & is reached by choosing an audio output format.
$PipelinePriority = 500;

// / The function dispatch calls. THIS NAME MUST BE UNIQUE ACROSS EVERY INSTALLED PIPELINE.
// / It lives in pipelineCore.php beside this file & no longer exists in convertCore.php.
$PipelineEntryPoint = 'convertVideos';

// / The depends.php subsystem this pipeline needs. Dependency Core owns installation.
// / FOUR FAMILIES NAME THIS SAME SUBSYSTEM. Video & Subtitle are pipelines, & Audio &
// / Stream are still built into convertCore.php. Naming the subsystem rather than restating
// / the dependency is what keeps that from becoming four disagreeing minimum versions.
$PipelineSubsystem = 'ffmpeg';

// / The request fields this pipeline actually reads.
// / This converter hardcodes libx264 & reads nothing but the output extension. A bitrate,
// / a height & a width are all passed to it by dispatch & all three are discarded by PHP,
// / because the entry point does not declare them.
$PipelineRequestFields = array('Extension');

// / What this pipeline can read & what it can write.
// / THESE LISTS ARE INFORMATIONAL WHILE $SupportedFormatDetectionType IS hardcoded-only.
// / config.php is authoritative in that mode & $UserVideoArray decides what is offered, so
// / an omission here cannot delete a conversion. They become authoritative in the detected
// / modes.
// / FFMPEG reads a great deal more than this. These are the container formats a general
// / purpose converter can hand back to a browser & expect to be usable.
$Capabilities = array(
  'Input' => array('mp4', 'm4v', 'mkv', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mpg', 'mpeg', 'ogv', '3gp', 'ts', 'vob', 'asf', 'rm'),
  'Output' => array('mp4', 'm4v', 'mkv', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mpg', 'mpeg', 'ogv', '3gp', 'ts'));

// / Pairs removed from the cross product above, written as 'input>output'.
// / A conversion that produces the format it was handed is not a conversion. FFMPEG will
// / happily re-encode a file into its own container & the result is a slower, larger, lossier
// / copy of what the user already had.
$PipelineExclude = array(
  'mp4>mp4', 'm4v>m4v', 'mkv>mkv', 'avi>avi', 'mov>mov', 'wmv>wmv', 'flv>flv',
  'webm>webm', 'mpg>mpg', 'mpeg>mpeg', 'ogv>ogv', '3gp>3gp', 'ts>ts');
// / -----------------------------------------------------------------------------------
?>
