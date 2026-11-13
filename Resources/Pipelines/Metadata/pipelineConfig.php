<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRConvert2, Copyright on 11/13/2026 by Justin Grimes, www.github.com/zelon88
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
// / v4.1.8.
// / This file declares what the Metadata pipeline is & what it can do.
// / It is read by pipelineCore.php on every request & it must stay cheap.
// / It assigns variables & does nothing else, with no functions, no logic & no output.
// / Error block 41000 through 41019 belongs to this pipeline, & 41000 through 41008 are raised.
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
$PipelineVersion = 'v4.1.8';

// / This is an operation pipeline, as the Extractor is.
// / It takes a selection of files & makes a copy of each one, without the metadata it carries, beside it in the session.
// / Metadata is what a file says about itself rather than what it shows, such as its author, the place a photo was taken & the software that made it.
// / It returns the name of every copy it made, which the conversion contract has no slot for, & a workflow hands them to the step after it.
// / It is dispatched by runOperationPipeline('Metadata') from the point in convertCore.php where a request to strip metadata arrives, & convert() can never reach it.
$PipelineKind = 'operation';

// / The family this implementation serves.
$PipelineFamily = 'Metadata';

// / The English fallback label. A language pack may translate it. Nothing breaks if none does.
$PipelineDisplayName = 'Metadata Scrubber';

// / Lower runs first when two implementations both claim a conversion.
// / This is declared because every pipeline declares it & is not used for dispatch. An
// / operation pipeline is not ranked.
$PipelinePriority = 100;

// / The function dispatch calls, whose name must be unique across every installed pipeline.
// / It is defined in pipeline.php beside this file.
$PipelineEntryPoint = 'stripMetadata';

// / The depends.php subsystem this pipeline needs. Dependency Core owns installation.
// / This string must match a Subsystem name in depends.php exactly.
$PipelineSubsystem = 'Metadata';

// / Shared modules this pipeline needs, loaded before its converter is loaded.
// / This pipeline needs none.
$PipelineSharedModules = array();

// / The request fields this pipeline actually reads.
// / Filename names each copy. Without one, a copy is given its file's name, & so the next free one, because that name is the file's own.
$PipelineRequestFields = array('Filename');

// / What this pipeline can read & what it can write.
// / It reads the formats mat2 strips & a server is likely to be handed, which are photos & pictures, PDFs, office documents, ebooks, audio & video.
// / A video's metadata is stripped with FFMPEG, so a video is refused while FFMPEG is missing, & nothing else is.
// / It writes each copy in the format of its file, so it declares no output formats.
$Capabilities = array(
  'Input' => array('jpg', 'jpeg', 'png', 'tif', 'tiff', 'gif', 'pdf', 'docx', 'xlsx', 'pptx', 'odt', 'ods', 'odp', 'odg', 'epub', 'mp3', 'flac', 'ogg', 'opus', 'wav', 'aiff', 'mp4', 'avi', 'wmv'),
  'Output' => array());

// / Pairs removed from the cross product above, written as 'input>output'.
// / Nothing is excluded, because nothing is converted.
$PipelineExclude = array();

// / The tools this pipeline asks for, by their names in depends.php.
// / mat2 strips every format above, FFMPEG is what it strips a video with, & Bubblewrap is named because mat2 runs in the sandbox.
$PipelineDependencies = array('MAT2', 'FFMPEG', 'Bubblewrap');
// / -----------------------------------------------------------------------------------
?>
