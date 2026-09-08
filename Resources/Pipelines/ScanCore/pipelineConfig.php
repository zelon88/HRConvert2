<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRConvert2, Copyright on 9/7/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / File Information ...
// / v3.9.3.
// / This file declares the ScanCore scanner pipeline.
// /
// / ScanCore ships inside this application rather than being installed on the host, so it
// / is present wherever HRConvert2 is. That makes it the sensible default scanner.
// /
// / A SCANNER declares no input or output formats & that is not an omission.
// / Every scanner claims every file, so there is nothing for an extension to decide. An
// / administrator picks a scanner by name in config.php & a user may pick another if the
// / interface offers one.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline configuration cannot be loaded directly!');
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The version of this pipeline folder. Read WITHOUT executing this file, then matched
// / EXACTLY against the pin in getAcceptedPipelines().
$PipelineVersion = 'v3.9.3';

// / A scanner inspects a file & reports on it. It changes nothing.
$PipelineKind = 'scanner';

// / The name an administrator writes in --Default Virus Scanner--, & the name a user
// / selects. Matched case insensitively, so scancore & ScanCore are the same scanner.
$PipelineFamily = 'ScanCore';

$PipelineDisplayName = 'ScanCore';

// / Priority orders conversion pipelines that claim the same formats. A scanner is chosen
// / by name & never competes, so this only decides the order scanners are listed in.
$PipelinePriority = 110;

$PipelineEntryPoint = 'scanWithScanCore';

// / A scanner declares no formats. See the note in the header.
// / A scanner declares EMPTY capabilities & the Pipeline Core exempts it from the rule
// / that a pipeline must read & write something. Every scanner claims every file.
$Capabilities = array(
  'Input' => array(),
  'Output' => array());

// / The subsystem this belongs to, for the readiness report.
$PipelineSubsystem = 'Security';

// / A scanner takes no request fields. It is handed paths by the caller & nothing else.
$PipelineRequestFields = array();

$PipelineSharedModules = array();
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / No pair is excluded, because no pair exists.
$PipelineExclude = array();
// / -----------------------------------------------------------------------------------
?>
