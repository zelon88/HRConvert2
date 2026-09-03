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
// / This file declares what the OCR pipeline is & what it can do.
// / It is read by pipelineCore.php on EVERY request & it must stay cheap.
// / It ASSIGNS VARIABLES & DOES NOTHING ELSE. No functions, no logic, no output.
// / Error block 8001, 15000 through 15014 belongs to this pipeline.
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

// / This is an operation pipeline & it is the first one.
// / A conversion pipeline takes one file, returns the six value contract, & is dispatched
// / by convert(). This one takes a SELECTION of files, chooses a different route per file
// / depending on whether it is a PDF, a document or an image, & reports one verdict for
// / the whole batch as two values. It is dispatched by runOcrOperation() from the point in
// / convertCore.php where an OCR request arrives, & convert() can never reach it.
// / Forcing OCR into the conversion contract would have meant describing a batch as a
// / single file & discarding most of what it reports.
$PipelineKind = 'operation';

// / The family this implementation serves.
$PipelineFamily = 'OCR';

// / The English fallback label. A language pack may translate it. Nothing breaks if none does.
$PipelineDisplayName = 'Optical Character Recognition';

// / Lower runs first when two implementations both claim a conversion.
// / This is declared because every pipeline declares it & is not used for dispatch. An
// / operation pipeline is not ranked. Fallback across OCR implementations is deliberately
// / not attempted, because a half completed batch has already written output files the
// / user can see & already consumed budget.
$PipelinePriority = 100;

// / The function dispatch calls. THIS NAME MUST BE UNIQUE ACROSS EVERY INSTALLED PIPELINE.
// / It lives in pipeline.php beside this file & no longer exists in convertCore.php.
$PipelineEntryPoint = 'ocrFiles';

// / The depends.php subsystem this pipeline needs. Dependency Core owns installation.
// / This string must match a Subsystem name in depends.php exactly.
// / Naming the subsystem rather than the package keeps one source of truth for the version.
// / Tesseract is named here & it is not the only dependency this pipeline uses.
// / pdftotext reads a PDF directly, ImageMagick rasterizes a page for the advanced route, &
// / LibreOffice converts a document to PDF before any of that. Each route gates on the
// / specific tool it needs rather than on an overall verdict, so a missing pdftotext does
// / not stop an image being read by Tesseract. Tesseract is named because it is the one
// / without which nothing here works at all.
$PipelineSubsystem = 'OCR';

// / Shared modules this pipeline needs, loaded before its converter is loaded.
// / The document pipeline declares this same module & that is the point.
// / A document being OCR'd is converted to PDF first, by the same code a document
// / conversion uses. Both pipelines in one request load this module once between them.
$PipelineSharedModules = array('libreOffice.php');

// / The request fields this pipeline actually reads.
// / Method selects between the direct route & the rasterize & read route, which is why
// / this is the only pipeline so far that reads something the conversion contract has no
// / slot for at all.
$PipelineRequestFields = array('Extension', 'Filename', 'Method');

// / What this pipeline can read & what it can write.
// / THESE LISTS ARE INFORMATIONAL WHILE $SupportedFormatDetectionType IS hardcoded-only.
// / They mirror the allowed input list inside the converter itself, which is the gate that
// / actually runs today.
// / Every route ends at a text file which is then converted to the requested output.
// / The output list is the set of text bearing formats rather than anything Tesseract knows.
$Capabilities = array(
  'Input' => array('pdf', 'txt', 'doc', 'docx', 'rtf', 'xls', 'xlsx', 'ods', 'odt', 'abw', 'jpg', 'jpeg', 'bmp', 'webp', 'png', 'gif'),
  'Output' => array('txt', 'doc', 'docx', 'rtf', 'odt', 'pdf'));

// / Pairs removed from the cross product above, written as 'input>output'.
// / NOTHING IS EXCLUDED HERE & txt>txt IS DELIBERATELY ALLOWED.
// / Everywhere else in this application, converting a format into itself is nonsense. OCR
// / is the exception. A scanned page inside a PDF that already contains a text layer, or a
// / text file produced by a previous OCR run, are both legitimate things to read again.
// / The output of OCR is not a re-encoding of the input, it is a reading of it.
$PipelineExclude = array();
// / -----------------------------------------------------------------------------------
?>
