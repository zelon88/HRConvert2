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
// / This file declares what the Subtitle conversion pipeline is & what it can do.
// / It is read by pipelineCore.php on EVERY request & it must stay cheap.
// / It ASSIGNS VARIABLES & DOES NOTHING ELSE. No functions, no logic, no output.
// / Error block 22000 through 22002 belongs to this pipeline.
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
$PipelineFamily = 'Subtitle';

// / The English fallback label. A language pack may translate it. Nothing breaks if none does.
$PipelineDisplayName = 'Subtitle';

// / Lower runs first when two implementations both claim a conversion.
// / Subtitle sits above Document deliberately. Several subtitle formats are plain text &
// / an installation that permits txt in both families would otherwise have the outcome
// / decided by whichever folder the manager happened to read first.
$PipelinePriority = 200;

// / The function dispatch calls. THIS NAME MUST BE UNIQUE ACROSS EVERY INSTALLED PIPELINE.
// / It lives in pipeline.php beside this file & no longer exists in convertCore.php.
$PipelineEntryPoint = 'convertSubtitles';

// / The depends.php subsystem this pipeline needs. Dependency Core owns installation.
// / This string must match a Subsystem name in depends.php exactly.
// / Naming the subsystem rather than the package keeps one source of truth for the version.
// / FOUR FAMILIES NAME THIS SAME SUBSYSTEM. Video & Subtitle are pipelines, & Audio &
// / Stream are still built into convertCore.php. Naming the subsystem rather than restating
// / the dependency is what keeps that from becoming four disagreeing minimum versions.
$PipelineSubsystem = 'Audio, Video & Streams';

// / Shared modules this pipeline needs, loaded before its converter is loaded.
// / This pipeline needs none.
$PipelineSharedModules = array();

// / The request fields this pipeline actually reads.
// / FFMPEG infers the subtitle format from the output file extension rather than from a
// / flag, exactly as Calibre does for e-books, so the extension is the entire request.
$PipelineRequestFields = array('Extension');

// / What this pipeline can read & what it can write.
// / THESE LISTS ARE INFORMATIONAL WHILE $SupportedFormatDetectionType IS hardcoded-only.
// / config.php is authoritative in that mode & $UserSubtitleArray decides what is offered,
// / so an omission here cannot delete a conversion. They become authoritative in the
// / detected modes.
// / A subtitle is read & written in the same set of formats, which is why the two lists
// / here match. That is unusual & is a property of the format family rather than a mistake.
$Capabilities = array(
  'Input' => array('srt', 'ass', 'ssa', 'vtt', 'sub', 'sbv', 'smi', 'ttml', 'stl', 'jacosub', 'mpl2', 'pjs', 'realtext', 'subviewer'),
  'Output' => array('srt', 'ass', 'ssa', 'vtt', 'sub', 'jacosub', 'mpl2', 'ttml'));

// / Pairs removed from the cross product above, written as 'input>output'.
// / A conversion that produces the format it was handed is not a conversion.
// / ass to ssa & ssa to ass are NOT excluded. SSA is the older revision of the same format
// / & moving between them is a real change a user can legitimately want.
$PipelineExclude = array(
  'srt>srt', 'ass>ass', 'ssa>ssa', 'vtt>vtt', 'sub>sub', 'jacosub>jacosub',
  'mpl2>mpl2', 'ttml>ttml');
// / -----------------------------------------------------------------------------------
?>
