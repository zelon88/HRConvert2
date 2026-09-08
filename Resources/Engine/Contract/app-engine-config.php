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
// / THIS APPLICATION'S ENGINE SETTINGS. It is loaded AFTER engineConfig.php & anything it
// / sets wins.
// /
// / The arrangement is the one Apache & php use. A package ships a configuration with
// / working defaults, & the thing being configured drops a file beside it that overrides
// / what it needs & leaves the rest alone. Neither file has to know what the other says.
// /
// / WHY IT MATTERS HERE. engineConfig.php belongs to the ENGINE & is replaced wholesale
// / when a newer Engine arrives. This file belongs to the APPLICATION & survives that.
// / Before this split an Engine upgrade meant reading engineConfig.php line by line to find
// / which values were this application's, & merging by hand. Now it is a directory copy.
// /
// / SET ONLY WHAT DIFFERS. A setting repeated here at the same value the Engine already
// / uses is a value that will silently stop tracking the Engine's default when that default
// / changes for a good reason.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-35000, An engine configuration cannot be loaded directly!');
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / What this application is called.
// / The Engine uses the name in log entries & error numbers, & the slug wherever a name has
// / to survive a filesystem or a socket path.
$EngineApplicationName = 'HRConvert2';
$EngineApplicationSlug = 'hrconvert2';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / THE PROVIDERS. Every one names a function this application defines, & the Engine calls
// / what it is given without knowing anything else about it.
// / This is the whole of how the Engine learns about an application. Six names.
// /
// / An Engine with none of them still runs & does less, which is the correct degradation
// / rather than a failure. See ABOUT_ENGINE_CONTRACT.txt.

// /   Reports on the parts of the environment that belong to this application.
$EngineEnvironmentProvider = 'applicationEnvironmentFindings';

// /   Asks a human a question & returns their answer. An empty answer is a refusal.
$EngineOperatorPrompt = 'askOperator';

// /   Reports whether this application's data is exposed by a web server.
$EngineDataPolicyProvider = 'applicationDataPolicyFindings';

// /   Returns the configuration model. What a config file cannot say about itself.
$EngineConfigModelProvider = 'applicationConfigModel';

// /   Repairs whatever this application manages.
$EngineRepairProvider = 'fixManagedPermissions';

// /   A real config.php at defaults. What this application's configuration looks like.
$EngineConfigTemplate = 'Engine/Contract/config-template.php';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / Anything else this application needs to differ from the Engine default goes below.
// / Nothing does today, which is why there is nothing here.
// / -----------------------------------------------------------------------------------
?>
