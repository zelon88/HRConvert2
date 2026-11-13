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
// / This file declares what the PDF pipeline is & what it can do.
// / It is read by pipelineCore.php on every request & it must stay cheap.
// / It assigns variables & does nothing else, with no functions, no logic & no output.
// / Error block 42000 through 42019 belongs to this pipeline, & 42000 through 42008 are raised.
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
// / It serves two operations, & the method it is handed says which. merge puts a selection of PDFs into one, & split makes a PDF of each page of every PDF it is handed.
// / It returns the name of every PDF it made, which the conversion contract has no slot for, & a workflow hands them to the step after it.
// / It is dispatched by runOperationPipeline('PDF') from the points in convertCore.php where a merge or a split request arrives, & convert() can never reach it.
// / The Document pipeline converts a PDF into another format, & this one leaves every PDF a PDF.
$PipelineKind = 'operation';

// / The family this implementation serves.
$PipelineFamily = 'PDF';

// / The English fallback label. A language pack may translate it. Nothing breaks if none does.
$PipelineDisplayName = 'PDF Pages';

// / Lower runs first when two implementations both claim a conversion.
// / This is declared because every pipeline declares it & is not used for dispatch. An
// / operation pipeline is not ranked.
$PipelinePriority = 100;

// / The function dispatch calls, whose name must be unique across every installed pipeline.
// / It is defined in pipeline.php beside this file.
$PipelineEntryPoint = 'operateOnPages';

// / The depends.php subsystem this pipeline needs. Dependency Core owns installation.
// / This string must match a Subsystem name in depends.php exactly.
// / It is the subsystem Poppler Utils is in, because PDFUnite & PDFSeparate come in its package.
$PipelineSubsystem = 'Documents & OCR';

// / Shared modules this pipeline needs, loaded before its converter is loaded.
// / This pipeline needs none.
$PipelineSharedModules = array();

// / The request fields this pipeline actually reads.
// / Filename names what merge makes, & Method says whether to merge or to split.
$PipelineRequestFields = array('Filename', 'Method');

// / What this pipeline can read & what it can write.
// / It reads a PDF & writes a PDF, & nothing else.
$Capabilities = array(
  'Input' => array('pdf'),
  'Output' => array('pdf'));

// / Pairs removed from the cross product above, written as 'input>output'.
// / Nothing is excluded, because nothing is converted.
$PipelineExclude = array();

// / The tools this pipeline asks for, by their names in depends.php.
// / PDFUnite merges, PDFSeparate splits, PDFInfo counts the pages of a PDF before it is split, & Bubblewrap is named because each runs in the sandbox.
$PipelineDependencies = array('PDFUnite', 'PDFSeparate', 'PDFInfo', 'Bubblewrap');
// / -----------------------------------------------------------------------------------
?>
