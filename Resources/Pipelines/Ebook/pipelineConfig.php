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
// / This file declares what the Ebook conversion pipeline is & what it can do.
// / It is read by pipelineManager.php on EVERY request & it must stay cheap.
// / It ASSIGNS VARIABLES & DOES NOTHING ELSE. No functions, no logic, no output.
// / Error block 30000 through 30005 belongs to this pipeline. It did not move when this
// / pipeline became a component & it is not going to.
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
// / A second implementation of the same family declares this same string & is merged into
// / the same menu, then ranked against this one at dispatch.
$PipelineFamily = 'Ebook';

// / The English fallback label. A language pack may translate it. Nothing breaks if none does.
$PipelineDisplayName = 'E-Book';

// / Lower runs first when two implementations both claim a conversion.
// / Leave room between pipelines so an alternative can be slotted either side without
// / renumbering anything. Stream sits at 900 because its outputs are ordinary audio &
// / video formats & it must never claim a file an earlier family recognized.
$PipelinePriority = 400;

// / The function dispatch calls. THIS NAME MUST BE UNIQUE ACROSS EVERY INSTALLED PIPELINE.
// / Two pipelines sharing a name would fatally redeclare the moment both load in one
// / request, which happens whenever an upload batch spans two families.
// / It lives in pipelineCore.php beside this file & no longer exists in convertCore.php.
$PipelineEntryPoint = 'convertEbooks';

// / The depends.php subsystem this pipeline needs. Dependency Core owns installation.
// / Naming the subsystem here rather than restating the dependency keeps one source of truth.
$PipelineSubsystem = 'calibre';

// / The request fields this pipeline actually reads.
// / Dispatch passes every field regardless & PHP discards what the entry point did not
// / declare, so this is documentation & the basis of the pipeline's own sanity check.
// / It is NOT a dispatch mechanism & changing it does not change what gets passed.
$PipelineRequestFields = array('Extension');

// / What this pipeline can read & what it can write.
// / THESE LISTS ARE INFORMATIONAL WHILE $SupportedFormatDetectionType IS hardcoded-only.
// / config.php is authoritative in that mode & $UserEbookInputArray with
// / $UserEbookOutputArray decide what is offered, so an omission here cannot delete a
// / conversion. They become authoritative in the detected modes.
// / They are treated as a CROSS PRODUCT. Every input is assumed convertible to every
// / output unless the pair appears in $PipelineExclude below.
$Capabilities = array(
  'Input' => array('epub', 'mobi', 'azw', 'azw3', 'fb2', 'lit', 'lrf', 'pdb', 'pml', 'rb', 'snb', 'tcr', 'txtz', 'htmlz', 'cbz', 'cbr', 'cbc', 'chm', 'recipe'),
  'Output' => array('epub', 'mobi', 'azw3', 'fb2', 'lit', 'lrf', 'pdb', 'pml', 'rb', 'snb', 'tcr', 'txtz', 'htmlz', 'txt', 'rtf', 'docx', 'pdf'));

// / Pairs removed from the cross product above, written as 'input>output'.
// / A conversion that produces the format it was handed is not a conversion. Calibre will
// / happily be asked to do it & will produce something, & that something is not what the
// / user asked for. Refusing the pair here means the menu never offers it.
$PipelineExclude = array(
  'epub>epub', 'mobi>mobi', 'azw3>azw3', 'fb2>fb2', 'lit>lit', 'lrf>lrf',
  'pdb>pdb', 'pml>pml', 'rb>rb', 'snb>snb', 'tcr>tcr', 'txtz>txtz', 'htmlz>htmlz');
// / -----------------------------------------------------------------------------------
?>
