<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRConvert2, Copyright on 11/16/2026 by Justin Grimes, www.github.com/zelon88
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
// / v4.2.5.
// / This file declares what the LaTeX conversion pipeline is & what it can do.
// / It is read by pipelineCore.php on every request & it must stay cheap.
// / It assigns variables & does nothing else, with no functions, no logic & no output.
// / Error block 45000 through 45005 belongs to this pipeline.
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
$PipelineVersion = 'v4.2.5';

// / What this pipeline is dispatched as. A conversion pipeline takes one file & returns
// / the six value contract.
$PipelineKind = 'conversion';

// / The family this implementation serves. The interface groups controls by this name.
$PipelineFamily = 'LaTeX';

// / The English fallback label. A language pack may translate it. Nothing breaks if none does.
$PipelineDisplayName = 'LaTeX Document';

// / Lower runs first when two implementations both claim a conversion. This is the only one.
$PipelinePriority = 300;

// / The function dispatch calls, whose name must be unique across every installed pipeline.
// / It is defined in pipeline.php beside this file.
$PipelineEntryPoint = 'typesetLaTeX';

// / The depends.php subsystem this pipeline needs. Dependency Core owns installation.
// / This string must match a Subsystem name in depends.php exactly.
$PipelineSubsystem = 'LaTeX';

// / Shared modules this pipeline needs, loaded before its converter is loaded.
// / Every document is typeset in a folder of its own, which workFolder.php makes & takes away, so what TeX writes beside a PDF never reaches a user's folder.
$PipelineSharedModules = array('workFolder.php');

// / The request fields this pipeline actually reads.
// / Dispatch passes every field regardless & PHP discards what the entry point did not
// / declare, so this is documentation & the basis of the pipeline's own sanity check.
$PipelineRequestFields = array('Extension');

// / What this pipeline can read & what it can write, treated as a cross product.
// / config.php is authoritative over what is offered, & $UserLaTeXInputArray with $UserLaTeXOutputArray decide it.
// / A document is typeset alone, so one that includes another file, an image or a bibliography it was uploaded beside fails.
$Capabilities = array(
  'Input' => array('tex', 'latex'),
  'Output' => array('pdf'));

// / Pairs removed from the cross product above. A LaTeX document never becomes LaTeX here, so there are none.
$PipelineExclude = array();

// / What this pipeline shells out to. Names must match the manifest exactly.
$PipelineDependencies = array('XeLaTeX', 'Bubblewrap');

// / The tools this pipeline cannot run without, by their names in depends.php.
// / A pipeline is offered only where one tool of each entry is installed.
// / XeLaTeX typesets every document, so nothing is typeset without it.
$PipelineRequiredDependencies = array('XeLaTeX');
// / -----------------------------------------------------------------------------------
?>
