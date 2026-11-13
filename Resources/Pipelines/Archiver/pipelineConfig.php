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
// / This file declares what the Archiver pipeline is & what it can do.
// / It is read by pipelineCore.php on every request & it must stay cheap.
// / It assigns variables & does nothing else, with no functions, no logic & no output.
// / Error block 4000 through 4006 belongs to this pipeline, & so does 13012.
// / It also raises 13006, 13008, 13009 & 13010, which it shares with the Archive pipeline.
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
// / It takes a selection of files & puts them into an archive, one archive for the selection or one for each file.
// / It returns the name of every archive it made, which the conversion contract has no slot for.
// / It is dispatched by runOperationPipeline('Archive') from the point in convertCore.php where an archive request arrives, & convert() can never reach it.
// / The Archive pipeline is a different thing. It converts one archive into another, & this one makes an archive from any file.
$PipelineKind = 'operation';

// / The family this implementation serves. It shares the name with the Archive conversion
// / pipeline, because dispatch asks for an operation pipeline of the family & a conversion
// / never answers that.
$PipelineFamily = 'Archive';

// / The English fallback label. A language pack may translate it. Nothing breaks if none does.
$PipelineDisplayName = 'Archiver';

// / Lower runs first when two implementations both claim a conversion.
// / This is declared because every pipeline declares it & is not used for dispatch. An
// / operation pipeline is not ranked.
$PipelinePriority = 100;

// / The function dispatch calls, whose name must be unique across every installed pipeline.
// / It is defined in pipeline.php beside this file.
$PipelineEntryPoint = 'archiveSelection';

// / The depends.php subsystem this pipeline needs. Dependency Core owns installation.
// / This string must match a Subsystem name in depends.php exactly.
// / It is the subsystem the Archive pipeline names, because the two use the same archivers.
$PipelineSubsystem = 'Archives';

// / Shared modules this pipeline needs, loaded before its converter is loaded.
// / This pipeline needs none.
$PipelineSharedModules = array();

// / The request fields this pipeline actually reads.
// / Extension chooses the archiver, & Filename names one archive for the whole selection.
// / Without a Filename each file is archived under its own name.
$PipelineRequestFields = array('Extension', 'Filename');

// / What this pipeline can read & what it can write.
// / It reads every file, which is what an empty input list declares for an operation.
// / It writes the archive formats it has an archiver for, & nothing else.
// / A bootable disk image is not one of them. That is the Archive pipeline's, because a
// / bootable image is made from an archive that already holds a bootloader.
// / The core offers only what this list & config.php both hold, so a format this pipeline
// / cannot make is never offered for an archive of a selection.
$Capabilities = array(
  'Input' => array(),
  'Output' => array('zip', 'rar', '7z', 'tar', 'tar.gz', 'tar.bz2', 'iso'));

// / Pairs removed from the cross product above, written as 'input>output'.
// / Nothing is excluded, because nothing is converted.
$PipelineExclude = array();

// / The tools this pipeline asks for, by their names in depends.php.
// / Bubblewrap is named because every archiver here runs in the sandbox.
// / Each archiver gates only the formats it writes, so a missing RAR costs rar & nothing else.
$PipelineDependencies = array('7-Zip', 'Zip', 'Tar', 'RAR', 'Genisoimage', 'Bubblewrap');
// / -----------------------------------------------------------------------------------
?>
