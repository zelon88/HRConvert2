<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRConvert2, Copyright on 11/12/2026 by Justin Grimes, www.github.com/zelon88
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
// / v4.1.6.
// / This file declares what the Scan pipeline is & what it can do.
// / It is read by pipelineCore.php on every request & it must stay cheap.
// / It assigns variables & does nothing else, with no functions, no logic & no output.
// / Error blocks 16000 through 16003, 17000 & 17001, 19000 & 19001, & 20000 & 20001 belong to this pipeline.
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
$PipelineVersion = 'v4.1.6';

// / This is an operation pipeline, as OCR & Identify are.
// / It takes a selection of files a user asked to have scanned, scans each one with ClamAV, ScanCore or both & writes the report a user reads.
// / It returns whether any file was found infected & where the report is, which the conversion contract has no slot for.
// / It is dispatched by runOperationPipeline('Scan') from the point in convertCore.php where a user's scan request arrives, & convert() can never reach it.
// / The ClamAV & ScanCore pipelines are the scanners. This one asks them, through runVirusScan(), & names neither tool itself.
$PipelineKind = 'operation';

// / The family this implementation serves.
$PipelineFamily = 'Scan';

// / The English fallback label. A language pack may translate it. Nothing breaks if none does.
$PipelineDisplayName = 'Scan';

// / Lower runs first when two implementations both claim a conversion.
// / This is declared because every pipeline declares it & is not used for dispatch. An
// / operation pipeline is not ranked.
$PipelinePriority = 100;

// / The function dispatch calls, whose name must be unique across every installed pipeline.
// / It is defined in pipeline.php beside this file.
$PipelineEntryPoint = 'scanSelection';

// / The depends.php subsystem this pipeline needs. Dependency Core owns installation.
// / This string must match a Subsystem name in depends.php exactly.
// / It is the subsystem both scanners belong to, because this pipeline is the user's way to reach either.
$PipelineSubsystem = 'Virus Scanning';

// / Shared modules this pipeline needs, loaded before its converter is loaded.
// / This pipeline needs none.
$PipelineSharedModules = array();

// / The request fields this pipeline actually reads.
// / Filename names the report a user downloads, & Method says which scanners run, all, clamav or scancore.
// / The extension is always txt, because a report is plain text.
$PipelineRequestFields = array('Filename', 'Method');

// / What this pipeline can read & what it can write.
// / It reads every file, which is what an empty input list declares for an operation.
// / It writes a plain text report & nothing else, & changes no file it scans.
$Capabilities = array(
  'Input' => array(),
  'Output' => array('txt'));

// / Pairs removed from the cross product above, written as 'input>output'.
// / Nothing is excluded, because nothing is converted.
$PipelineExclude = array();

// / The tools this pipeline asks for, by their names in depends.php.
// / It runs no tool of its own. Each scanner is the ClamAV or ScanCore pipeline's, & the subsystem above names both.
$PipelineDependencies = array();
// / -----------------------------------------------------------------------------------
?>
