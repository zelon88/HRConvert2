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
// / v4.1.7.
// / This file declares what the Extractor pipeline is & what it can do.
// / It is read by pipelineCore.php on every request & it must stay cheap.
// / It assigns variables & does nothing else, with no functions, no logic & no output.
// / Error block 39100 through 39104 belongs to this pipeline.
// / It also raises 39000 through 39003, which belong to the archiveExtraction.php shared module it uses.
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
$PipelineVersion = 'v4.1.7';

// / This is an operation pipeline, as the Archiver is.
// / It takes a selection of archives & puts every file each one holds into the session beside it, under its own name.
// / It returns the name of every file it placed, which the conversion contract has no slot for, & a workflow hands them to the step after it.
// / It is dispatched by runOperationPipeline('Extract') from the point in convertCore.php where an extract request arrives, & convert() can never reach it.
// / The Archive pipeline extracts an archive too, to put what it holds into another archive. Both extract through the archiveExtraction.php shared module.
$PipelineKind = 'operation';

// / The family this implementation serves.
$PipelineFamily = 'Extract';

// / The English fallback label. A language pack may translate it. Nothing breaks if none does.
$PipelineDisplayName = 'Extractor';

// / Lower runs first when two implementations both claim a conversion.
// / This is declared because every pipeline declares it & is not used for dispatch. An
// / operation pipeline is not ranked.
$PipelinePriority = 100;

// / The function dispatch calls, whose name must be unique across every installed pipeline.
// / It is defined in pipeline.php beside this file.
$PipelineEntryPoint = 'extractSelection';

// / The depends.php subsystem this pipeline needs. Dependency Core owns installation.
// / This string must match a Subsystem name in depends.php exactly.
// / It is the subsystem the Archive pipeline names, because both extract with 7-Zip.
$PipelineSubsystem = 'Archives';

// / Shared modules this pipeline needs, loaded before its converter is loaded.
// / archiveExtraction.php lists, extracts & measures an archive within the limits config.php sets.
$PipelineSharedModules = array('archiveExtraction.php');

// / The request fields this pipeline actually reads.
// / None. Every file is named as the archive names it, & a name already taken is given the next free one.
$PipelineRequestFields = array();

// / What this pipeline can read & what it can write.
// / It reads the archives 7-Zip opens & a server is likely to be handed, & config.php's list of archives it may read decides which of them a user is offered.
// / A .tar.gz is read as gz, & a gzip, bzip2 or xz that holds a tar gives up the files in the tar.
// / It writes whatever each archive holds, so it declares no output formats. What it places is held to the names & formats an upload is.
$Capabilities = array(
  'Input' => array('zip', '7z', 'rar', 'tar', 'gz', 'tgz', 'bz', 'bz2', 'tbz', 'tbz2', 'xz', 'txz', 'iso', 'vhd', 'vdi', 'cbz', 'cbr'),
  'Output' => array());

// / Pairs removed from the cross product above, written as 'input>output'.
// / Nothing is excluded, because nothing is converted.
$PipelineExclude = array();

// / The tools this pipeline asks for, by their names in depends.php.
// / 7-Zip extracts every format above, & Bubblewrap is named because it runs in the sandbox.
$PipelineDependencies = array('7-Zip', 'Bubblewrap');
// / -----------------------------------------------------------------------------------
?>
