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
// / This file declares what the Archive conversion pipeline is & what it can do.
// / It is read by pipelineCore.php on EVERY request & it must stay cheap.
// / It ASSIGNS VARIABLES & DOES NOTHING ELSE. No functions, no logic, no output.
// / Error block 13000, 13006 through 13011, 13013, 13105 through 13107 belongs to this pipeline.
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
$PipelineFamily = 'Archive';

// / The English fallback label. A language pack may translate it. Nothing breaks if none does.
$PipelineDisplayName = 'Archive';

// / Lower runs first when two implementations both claim a conversion.
// / Archive sits LAST of the conversion families & only Stream is behind it. An archive
// / extension is unambiguous, so it never competes, & anything that did compete should win.
$PipelinePriority = 700;

// / The function dispatch calls. THIS NAME MUST BE UNIQUE ACROSS EVERY INSTALLED PIPELINE.
// / It lives in pipeline.php beside this file & no longer exists in convertCore.php.
$PipelineEntryPoint = 'convertArchives';

// / The depends.php subsystem this pipeline needs. Dependency Core owns installation.
// / This string must match a Subsystem name in depends.php exactly.
// / Naming the subsystem rather than the package keeps one source of truth for the version.
// / verifyIsoBootloaders() & generateBootableIsoCommand() MOVED HERE WITH THE CONVERTER,
// / because convertCore.php had no other caller for either. verifyArchiveVersions() &
// / verifyIsoHybridVersion() DID NOT MOVE. archiveFiles(), deleteFiles() & showVersionInfo()
// / all still call them, & those run whether or not this pipeline is installed.
$PipelineSubsystem = 'Archives';

// / Shared modules this pipeline needs, loaded before its converter is loaded.
// / This pipeline needs none.
$PipelineSharedModules = array();

// / The request fields this pipeline actually reads.
// / The output extension decides the target. Nothing else is read.
$PipelineRequestFields = array('Extension');

// / What this pipeline can read & what it can write.
// / THESE LISTS ARE INFORMATIONAL WHILE $SupportedFormatDetectionType IS hardcoded-only.
// / config.php is authoritative in that mode, so an omission here cannot delete a
// / conversion. They become authoritative in the detected modes.
// / This is the only pipeline that rewrites its own output name.
// / An extraction hands the user something named differently from what they asked for,
// / which is exactly what the fifth value of the six value contract exists for. Every
// / other pipeline returns the filename it was handed.
$Capabilities = array(
  'Input' => array(
    'zip', '7z', 'tar', 'gz', 'tgz', 'bz2', 'tbz', 'xz', 'txz', 'rar', 'iso', 'cab', 'arj',
    'lzh', 'lzma', 'z', 'cpio', 'deb', 'rpm', 'wim', 'dmg'),
  'Output' => array(
    'zip', '7z', 'tar', 'gz', 'tgz', 'bz2', 'xz', 'iso', 'wim'));

// / Pairs removed from the cross product above, written as 'input>output'.
// / A conversion that produces the format it was handed is not a conversion. It unpacks an
// / archive & repacks it into the same container, which costs time & gains the user nothing.
// / tar to gz & gz to tar are NOT excluded. Compressing or decompressing a tarball is a real
// / change & is one of the more common things anybody asks this family to do.
$PipelineExclude = array(
    'zip>zip', '7z>7z', 'tar>tar', 'gz>gz', 'bz2>bz2', 'xz>xz', 'iso>iso', 'wim>wim');
// / -----------------------------------------------------------------------------------
?>
