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
// / This file declares what the Data conversion pipeline is & what it can do.
// / It is read by pipelineCore.php on every request & it must stay cheap.
// / It assigns variables & does nothing else, with no functions, no logic & no output.
// / Error block 44000 through 44007 belongs to this pipeline.
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
$PipelineFamily = 'Data';

// / The English fallback label. A language pack may translate it. Nothing breaks if none does.
$PipelineDisplayName = 'Data File';

// / Lower runs first when two implementations both claim a conversion. This is the only one.
$PipelinePriority = 300;

// / The function dispatch calls, whose name must be unique across every installed pipeline.
// / It is defined in pipeline.php beside this file.
$PipelineEntryPoint = 'convertDataFiles';

// / The depends.php subsystem this pipeline needs. Dependency Core owns installation.
// / This string must match a Subsystem name in depends.php exactly.
// / It is not Data, because --subsystem matches any subsystem whose name holds what is typed, & Data would install & remove Metadata's MAT2 too.
$PipelineSubsystem = 'Structured Data';

// / Shared modules this pipeline needs, loaded before its converter is loaded.
// / Every conversion works in a folder of its own, which workFolder.php makes & takes away.
$PipelineSharedModules = array('workFolder.php');

// / The request fields this pipeline actually reads.
// / Dispatch passes every field regardless & PHP discards what the entry point did not
// / declare, so this is documentation & the basis of the pipeline's own sanity check.
$PipelineRequestFields = array('Extension');

// / What this pipeline can read & what it can write, treated as a cross product less the pairs excluded below.
// / config.php is authoritative over what is offered, & $UserDataInputArray with $UserDataOutputArray decide it.
// / yml is YAML by its other name, so yaml to yml is offered, & writes the same YAML under the name asked for.
// / A CSV file is a table, so a document is written as CSV only where it is a list of records with the same plain fields,
// / & Dasel refuses anything else. TOML is written only where the document is a table at its top, & the pipeline asks first.
// / XML is written by yq from what Dasel reads, under one root, & read back before it is offered.
$Capabilities = array(
  'Input' => array('json', 'yaml', 'yml', 'toml', 'xml', 'csv'),
  'Output' => array('json', 'yaml', 'yml', 'toml', 'xml', 'csv'));

// / Pairs removed from the cross product above, written as 'input>output'.
// / A conversion that produces the format it was handed is not a conversion.
$PipelineExclude = array('json>json', 'yaml>yaml', 'yml>yml', 'toml>toml', 'xml>xml', 'csv>csv');

// / What this pipeline shells out to. Names must match the manifest exactly.
// / yq is asked only for XML, so a server without it converts everything but XML.
$PipelineDependencies = array('Dasel', 'yq', 'Bubblewrap');

// / The tools this pipeline cannot run without, by their names in depends.php.
// / A pipeline is offered only where one tool of each entry is installed. An array in it names tools any one of which will do.
// / Dasel reads every format, so nothing is converted without it.
$PipelineRequiredDependencies = array('Dasel');
// / -----------------------------------------------------------------------------------
?>
