<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRConvert2, Copyright on 9/22/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / File Information ...
// / v4.3.1.
// / This file declares the ClamAV scanner pipeline.
// /
// / A scanner declares no input or output formats & that is not an omission.
// / Every scanner claims every file, so there is nothing for an extension to decide.
// / A scanner is chosen by name, by an administrator in config.php or by whoever asks for a scan.
// / This pipeline names no application & no setting of one. It asks for what it needs by the
// / names a shared layer uses, through the kernel's applicationSetting(), so an application
// / that accepts it maps those names in its contract & changes nothing here.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline configuration cannot be loaded directly!');
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The version of this pipeline folder. Read without executing this file, then matched
// / exactly against the pin in the application's contract.
$PipelineVersion = 'v4.3.1';

// / A scanner inspects a file & reports on it. It changes nothing.
$PipelineKind = 'scanner';

// / The name an administrator writes in --Default Virus Scanner--, & the name a scan is asked
// / for by. Matched case insensitively, so clamav & ClamAV are the same scanner.
$PipelineFamily = 'ClamAV';

$PipelineDisplayName = 'ClamAV';

// / Priority orders conversion pipelines that claim the same formats. A scanner is chosen
// / by name & never competes, so this only decides the order scanners are listed in.
$PipelinePriority = 100;

$PipelineEntryPoint = 'scanWithClamAV';

// / A scanner declares empty capabilities & the Pipeline Core exempts it from the rule
// / that a pipeline must read & write something. Every scanner claims every file.
$Capabilities = array(
  'Input' => array(),
  'Output' => array());

// / The subsystem this belongs to, for the readiness report.
$PipelineSubsystem = 'Security';

// / This says where the pipeline may be reached from. A scanner is useful from a terminal & from
// / a schedule, not only from a browser, so it declares cli as well.
$PipelineUsage = array('cli', 'web', 'gui');

// / A scanner takes no request fields. It is handed paths by the caller & nothing else.
$PipelineRequestFields = array();

$PipelineSharedModules = array();
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / What this pipeline shells out to. Names must match the manifest exactly.
$PipelineDependencies = array('ClamAV');

// / No pair is excluded, because no pair exists.
$PipelineExclude = array();

// / The tools this pipeline cannot run without, by their names in the dependency manifest.
// / A pipeline is offered only where one tool of each entry is installed.
// / This scanner is clamscan, so it is not offered without it.
$PipelineRequiredDependencies = array('ClamAV');
// / -----------------------------------------------------------------------------------
?>
