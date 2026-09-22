<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRConvert2, Copyright on 9/8/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / File Information ...
// / v3.9.3.
// / The Files pipeline. Operations on a file that do not care what is inside it.
// /
// / THE FIRST PIPELINE OF KIND file. Deleting, listing & moving a file is the same work
// / whatever the extension says, which is why it declares no formats.
// / An application that stores files needs this. An application that converts them needs it
// / too, which is why it was living inside convertCore.php before it was a component.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-2: This file cannot be loaded directly!');
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The version this pipeline reports. Pinned in getAcceptedPipelines.
$PipelineVersion = 'v3.9.3';

// / What this pipeline does. A file operation rather than a conversion or a scan.
$PipelineKind = 'file';

// / Where it may be reached from. A file operation is as useful from a terminal as from a
// / browser, & more useful from a schedule than either.
$PipelineUsage = array('cli', 'web', 'gui');

// / The family & the name shown to a user.
$PipelineFamily = 'Files';
$PipelineDisplayName = 'File Operations';

// / Chosen by name rather than by extension, so priority decides nothing here.
$PipelinePriority = 100;

// / The function the Pipeline Core calls.
$PipelineEntryPoint = 'operateOnFiles';

// / The subsystem this belongs to, for the readiness report.
$PipelineSubsystem = 'Core';

// / A file operation claims every file, so the lists are empty & that is the honest
// / declaration rather than an omission. The Pipeline Core exempts this kind from the rule
// / that a pipeline must read & write something.
$Capabilities = array(
  'Input' => array(),
  'Output' => array());

// / It takes no request fields. The caller hands it paths & an operation.
$PipelineRequestFields = array();

// / Nothing shared is needed. It uses only what the Engine already provides.
$PipelineSharedModules = array();

// / Nothing is excluded.
$PipelineExclude = array();
// / -----------------------------------------------------------------------------------
