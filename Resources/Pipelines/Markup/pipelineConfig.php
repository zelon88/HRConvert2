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
// / This file declares what the Markup conversion pipeline is & what it can do.
// / It is read by pipelineCore.php on every request & it must stay cheap.
// / It assigns variables & does nothing else, with no functions, no logic & no output.
// / Error block 48000 through 48005 belongs to this pipeline.
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
$PipelineFamily = 'Markup';

// / The English fallback label. A language pack may translate it. Nothing breaks if none does.
$PipelineDisplayName = 'Markup Document';

// / Lower runs first when two implementations both claim a conversion. This is the only one.
$PipelinePriority = 300;

// / The function dispatch calls, whose name must be unique across every installed pipeline.
// / It is defined in pipeline.php beside this file.
$PipelineEntryPoint = 'convertMarkup';

// / The depends.php subsystem this pipeline needs. Dependency Core owns installation.
// / This string must match a Subsystem name in depends.php exactly.
$PipelineSubsystem = 'Markup';

// / Shared modules this pipeline needs, loaded before its converter is loaded.
// / This pipeline needs none. Pandoc reads one file & writes one, & makes its own folder on the tmpfs when it writes a PDF.
$PipelineSharedModules = array();

// / The request fields this pipeline actually reads.
// / Dispatch passes every field regardless & PHP discards what the entry point did not
// / declare, so this is documentation & the basis of the pipeline's own sanity check.
$PipelineRequestFields = array('Extension');

// / What this pipeline can read & what it can write, treated as a cross product less the pairs excluded below.
// / config.php is authoritative over what is offered, & $UserMarkupInputArray with $UserMarkupOutputArray decide it.
// / Pandoc reads & writes the markup languages people write by hand, the word processor & e-book formats, & a few more. md & markdown are
// / written as GitHub Flavored Markdown, which the most programs read. A PDF is typeset by XeLaTeX, which the LaTeX subsystem installs.
// / A document is converted alone, & Pandoc is sandboxed so it reads nothing else, so an image or a file it names is left out, with a
// / warning in the log. A PowerPoint or a notebook may need an image it cannot read, & then the conversion fails & the error names the image.
// / An earlier family in the order applicationConversionFamilies() keeps converts what it claims, so a word processor file still becomes
// / another word processor file, a PDF or an e-book by the Document & Ebook pipelines, & this one is asked for the rest.
// / HTML is neither read nor written. The core refuses html as a dangerous file, so none can be uploaded & asking for one ends the request,
// / & ABOUT_DATA_DIRECTORY_PROTECTION.txt keeps htm out of every format list, because a browser runs what an HTML page carries.
$Capabilities = array(
  'Input' => array('md', 'markdown', 'rst', 'adoc', 'asciidoc', 'jira', 'textile', 'org', 'mediawiki', 'dokuwiki', 'creole', 'muse', 't2t', 'djot', 'typ',
    'tex', 'latex', 'docx', 'odt', 'epub', 'rtf', 'ipynb', 'opml', 'fb2', 'dbk', 'pod', 'csv', 'tsv', 'pptx', 'xlsx'),
  'Output' => array('md', 'markdown', 'rst', 'adoc', 'asciidoc', 'jira', 'textile', 'org', 'mediawiki', 'dokuwiki', 'muse', 't2t', 'djot', 'typ',
    'tex', 'latex', 'docx', 'odt', 'epub', 'rtf', 'ipynb', 'opml', 'fb2', 'dbk', 'txt', 'pdf', 'pptx', 'texi', 'icml'));

// / Pairs removed from the cross product above, written as 'input>output'.
// / A conversion that produces the format it was handed is not a conversion.
$PipelineExclude = array('md>md', 'markdown>markdown', 'rst>rst', 'adoc>adoc', 'asciidoc>asciidoc', 'jira>jira', 'textile>textile', 'org>org', 'mediawiki>mediawiki',
  'dokuwiki>dokuwiki', 'muse>muse', 't2t>t2t', 'djot>djot', 'typ>typ', 'tex>tex', 'latex>latex', 'docx>docx', 'odt>odt', 'epub>epub',
  'rtf>rtf', 'ipynb>ipynb', 'opml>opml', 'fb2>fb2', 'dbk>dbk', 'pptx>pptx');

// / What this pipeline shells out to. Names must match the manifest exactly.
// / XeLaTeX is asked only for a PDF, so a server without it converts everything but a PDF.
$PipelineDependencies = array('Pandoc', 'XeLaTeX', 'Bubblewrap');

// / The tools this pipeline cannot run without, by their names in depends.php.
// / A pipeline is offered only where one tool of each entry is installed.
// / Pandoc converts every document, so nothing is converted without it.
$PipelineRequiredDependencies = array('Pandoc');
// / -----------------------------------------------------------------------------------
?>
