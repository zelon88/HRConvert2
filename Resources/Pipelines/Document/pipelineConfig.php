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
// / This file declares what the Document conversion pipeline is & what it can do.
// / It is read by pipelineCore.php on EVERY request & it must stay cheap.
// / It ASSIGNS VARIABLES & DOES NOTHING ELSE. No functions, no logic, no output.
// / Error block 2005, 7000 through 7001 belongs to this pipeline.
// / Those numbers came with the code when it moved out of convertCore.php.
// / They did not change, because operators have already read them.
// / because the code that raises them went there.
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
$PipelineFamily = 'Document';

// / The English fallback label. A language pack may translate it. Nothing breaks if none does.
$PipelineDisplayName = 'Document';

// / Lower runs first when two implementations both claim a conversion.
// / Document sits BELOW Subtitle & Ebook on purpose. Several subtitle formats are plain
// / text & several e-book formats are markup, so an installation permitting txt or html in
// / more than one family would otherwise have the outcome decided by directory read order.
// / A user who wanted the document treatment reaches it through the document menu.
$PipelinePriority = 600;

// / The function dispatch calls. THIS NAME MUST BE UNIQUE ACROSS EVERY INSTALLED PIPELINE.
// / It lives in pipeline.php beside this file & no longer exists in convertCore.php.
$PipelineEntryPoint = 'convertDocuments';

// / The depends.php subsystem this pipeline needs. Dependency Core owns installation.
// / This string must match a Subsystem name in depends.php exactly.
// / Naming the subsystem rather than the package keeps one source of truth for the version.
$PipelineSubsystem = 'Documents';

// / Shared modules this pipeline needs, loaded before its converter is loaded.
// / The OCR pipeline declares this same module & that is the point.
// / convertWithLibreOffice(), sanitizeDocumentLinks(), neutralizeDocumentReferences() &
// / verifyDocumentConversionEngine() have callers in both pipelines. Neither one can own
// / them without making the other depend on it, so they live in the shared module & are
// / loaded once for whichever pipelines a request happens to need.
$PipelineSharedModules = array('libreOffice.php');

// / The request fields this pipeline actually reads.
// / LibreOffice infers the output format from the target extension passed to --convert-to,
// / so the extension is the entire request. A height, a width, a rotation & a bitrate are
// / all handed over by dispatch & all four are discarded by PHP.
$PipelineRequestFields = array('Extension');

// / What this pipeline can read & what it can write.
// / THESE LISTS ARE INFORMATIONAL WHILE $SupportedFormatDetectionType IS hardcoded-only.
// / config.php is authoritative in that mode & $UserDocumentArray decides what is offered,
// / so an omission here cannot delete a conversion. They become authoritative in the
// / detected modes.
// / xps & oxps are inputs only. They are handled by the xpstopdf branch in the converter
// / rather than by LibreOffice, & nothing here writes them back out.
$Capabilities = array(
  'Input' => array('doc', 'docx', 'docm', 'dot', 'dotx', 'odt', 'ott', 'fodt', 'rtf', 'txt', 'html', 'htm', 'xml', 'wpd', 'abw', 'pages', 'xls', 'xlsx', 'xlsm', 'ods', 'ots', 'fods', 'csv', 'ppt', 'pptx', 'odp', 'otp', 'fodp', 'xps', 'oxps'),
  'Output' => array('pdf', 'doc', 'docx', 'odt', 'ott', 'fodt', 'rtf', 'txt', 'html', 'xml', 'epub', 'xls', 'xlsx', 'ods', 'csv', 'ppt', 'pptx', 'odp'));

// / Pairs removed from the cross product above, written as 'input>output'.
// / A conversion that produces the format it was handed is not a conversion. LibreOffice
// / will open it & write it back out, & the result is a reflowed copy of what the user
// / already had rather than the file they uploaded.
$PipelineExclude = array(
  'doc>doc', 'docx>docx', 'odt>odt', 'ott>ott', 'fodt>fodt', 'rtf>rtf', 'txt>txt',
  'html>html', 'xml>xml', 'xls>xls', 'xlsx>xlsx', 'ods>ods', 'csv>csv',
  'ppt>ppt', 'pptx>pptx', 'odp>odp');
// / -----------------------------------------------------------------------------------
?>
