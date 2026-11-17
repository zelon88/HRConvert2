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
// / v4.2.6.
// / This file declares what the Graph conversion pipeline is & what it can do.
// / It is read by pipelineCore.php on every request & it must stay cheap.
// / It assigns variables & does nothing else, with no functions, no logic & no output.
// / Error block 49000 through 49004 belongs to this pipeline.
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
$PipelineVersion = 'v4.2.6';

// / What this pipeline is dispatched as. A conversion pipeline takes one file & returns
// / the six value contract.
$PipelineKind = 'conversion';

// / The family this implementation serves. The interface groups controls by this name.
$PipelineFamily = 'Graph';

// / The English fallback label. A language pack may translate it. Nothing breaks if none does.
$PipelineDisplayName = 'Graph';

// / Lower runs first when two implementations both claim a conversion. This is the only one.
$PipelinePriority = 300;

// / The function dispatch calls, whose name must be unique across every installed pipeline.
// / It is defined in pipeline.php beside this file.
$PipelineEntryPoint = 'convertGraph';

// / The depends.php subsystem this pipeline needs. Dependency Core owns installation.
// / This string must match a Subsystem name in depends.php exactly.
// / It is Graphs, because --subsystem matches any subsystem whose name holds what is typed, & no other subsystem holds Graphs.
$PipelineSubsystem = 'Graphs';

// / Shared modules this pipeline needs, loaded before its converter is loaded.
// / This pipeline needs none. Graphviz reads one file & writes one.
$PipelineSharedModules = array();

// / The request fields this pipeline actually reads.
// / Dispatch passes every field regardless & PHP discards what the entry point did not
// / declare, so this is documentation & the basis of the pipeline's own sanity check.
$PipelineRequestFields = array('Extension');

// / What this pipeline can read & what it can write, treated as a cross product less the pairs excluded below.
// / config.php is authoritative over what is offered, & $UserGraphInputArray with $UserGraphOutputArray decide it.
// / A graph is written in Graphviz's DOT language, & laid out by the engine its own layout attribute names, or by dot where it names none.
// / gv is the only input, as Graphviz itself recommends, because a file named dot is a Word template to the Document pipeline. A graph saved
// / as dot is renamed gv before it is uploaded.
// / Every output is a picture of the graph. Graphviz's own text formats, which describe the layout to another program, are not offered.
$Capabilities = array(
  'Input' => array('gv'),
  'Output' => array('svg', 'png', 'pdf', 'jpg', 'gif', 'webp', 'eps', 'ps'));

// / Pairs removed from the cross product above, written as 'input>output'.
// / No output is a graph description, so no pair converts a file into its own format.
$PipelineExclude = array();

// / What this pipeline shells out to. Names must match the manifest exactly.
$PipelineDependencies = array('Graphviz', 'Bubblewrap');

// / The tools this pipeline cannot run without, by their names in depends.php.
// / A pipeline is offered only where one tool of each entry is installed.
// / Graphviz draws every graph, so nothing is converted without it.
$PipelineRequiredDependencies = array('Graphviz');
// / -----------------------------------------------------------------------------------
?>
