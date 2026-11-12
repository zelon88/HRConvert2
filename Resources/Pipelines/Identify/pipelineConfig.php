<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRConvert2, Copyright on 11/11/2026 by Justin Grimes, www.github.com/zelon88
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
// / v4.1.2.
// / This file declares what the Identify pipeline is & what it can do.
// / It is read by pipelineCore.php on every request & it must stay cheap.
// / It assigns variables & does nothing else, with no functions, no logic & no output.
// / Error block 37000 through 37004 belongs to this pipeline.
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
// / folder, so pipeline.php & the policy beside this file ship & move with it.
$PipelineVersion = 'v4.1.2';

// / This is an operation pipeline, as OCR is.
// / It takes a selection of files, reports what each one is & changes none of them.
// / It returns the records it gathered & the name of any report it wrote, which the conversion contract has no slot for.
// / It is dispatched by runOperationPipeline('Identify') from the point in convertCore.php where an Identify request arrives, & convert() can never reach it.
$PipelineKind = 'operation';

// / The family this implementation serves.
$PipelineFamily = 'Identify';

// / The English fallback label. A language pack may translate it. Nothing breaks if none does.
$PipelineDisplayName = 'Identify';

// / Lower runs first when two implementations both claim a conversion.
// / This is declared because every pipeline declares it & is not used for dispatch. An
// / operation pipeline is not ranked.
$PipelinePriority = 100;

// / The function dispatch calls, whose name must be unique across every installed pipeline.
// / It is defined in pipeline.php beside this file.
$PipelineEntryPoint = 'identifyFiles';

// / The depends.php subsystem this pipeline needs. Dependency Core owns installation.
// / This string must match a Subsystem name in depends.php exactly.
// / File & FFprobe belong to it. PDFInfo shares the package Documents & OCR need, so it belongs to theirs.
// / Every tool this pipeline uses is optional. A file is still measured & hashed with none of them.
$PipelineSubsystem = 'Identification';

// / Shared modules this pipeline needs, loaded before its converter is loaded.
// / It needs none.
$PipelineSharedModules = array();

// / The request fields this pipeline actually reads.
// / Filename names the report, & Method says whether one is written at all.
// / The extension is always txt, because a report is plain text.
$PipelineRequestFields = array('Filename', 'Method');

// / What this pipeline can read & what it can write.
// / It reads every file, which is what an empty input list declares for an operation.
// / It writes a plain text report & nothing else.
$Capabilities = array(
  'Input' => array(),
  'Output' => array('txt'));

// / Pairs removed from the cross product above, written as 'input>output'.
// / Nothing is excluded, because nothing is converted.
$PipelineExclude = array();

// / The tools this pipeline asks for, by their names in depends.php.
// / Bubblewrap is named because every tool here runs in the sandbox.
// / Each of the others only adds detail, so a missing one costs the details it would have read.
$PipelineDependencies = array('File', 'FFprobe', 'PDFInfo', 'ImageMagick', '7-Zip', 'Bubblewrap');
// / -----------------------------------------------------------------------------------
?>
