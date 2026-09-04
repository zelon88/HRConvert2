<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRProprietary Engine, Copyright on 9/4/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / File Information ...
// / v3.8.9.
// / This file configures the Engine for the application that bundles it.
// /
// / This file is NOT the administrator's configuration & is not edited by a user.
// / An application ships with the Engine it was tested against, & this file is how the
// / developer of that application says which parts of the Engine it uses. The settings a
// / user touches live in the application's own config.php.
// /
// / Two configuration files exist & they answer different questions.
// /   This file           Which Engine capabilities this application uses. Set by a
// /                       developer, shipped with the release, replaced by an update.
// /   The app config.php  How this installation behaves. Set by an administrator, kept
// /                       across an update, & the only one anybody is asked to read.
// /
// / This file is read BEFORE the application configuration, so an application setting of
// / the same name wins. That ordering is deliberate. A developer sets a default here & an
// / administrator may still override it there, where they can see it.
// / A value the application must NOT be able to override does not belong in a variable.
// /
// / It ASSIGNS VARIABLES & DOES NOTHING ELSE. No functions, no logic, no output.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by an application.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-35000, The Engine configuration cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The version of this configuration. Matched as a MINIMUM by the Engine, the same way
// / an application matches its own configuration. A newer file carrying every required
// / setting is fine, because a setting this Engine does not know is simply not read.
$EngineConfigVersion = 'v3.8.9';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / --Engine Identity--
// /
// /  --Engine Application Name--
// /   The name this Engine reports the application by.
// /   It appears in a log line the Engine writes on its own behalf, so that an operator
// /   reading a log can tell which application produced it on a host running several.
// /   Valid options are any short text string.
// /   Default is 'HRConvert2'.
$EngineApplicationName = 'HRConvert2';

// /  --Engine Application Slug--
// /   A short lowercase form of the name, safe to use in a file name or a socket path.
// /   Nothing derives this from the name above, because a name may contain anything & a
// /   path may not.
// /   Valid options are lowercase letters, digits & hyphens.
// /   Default is 'hrconvert2'.
$EngineApplicationSlug = 'hrconvert2';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / --Engine Capabilities--
// /
// / Every capability below is a gate rather than a feature request.
// / A capability that is disabled is not loaded, is not verified & is not reported. An
// / application that does not sandbox anything should not be told its sandbox is broken.
// /
// / A capability that is enabled still degrades the way everything else in this project
// / degrades. Enabling it says this application uses it, not that it must be present.
// /
// /  --Engine Enable Environment--
// /   Detecting the host. Root or not, a terminal or not, a container or not, which
// /   separator, which line ending, which account the process runs as.
// /   Every application needs this & there is no sensible reason to disable it. It is a
// /   toggle so that the list below is complete rather than because it is optional.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$EngineEnableEnvironment = TRUE;

// /  --Engine Enable Dependency Location--
// /   Finding an executable on the host & reporting where it was found.
// /   An application that shells out to anything needs this. An application that does not
// /   should turn it off, because a locator that is never called is a locator that can
// /   still be called by mistake.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$EngineEnableDependencyLocation = TRUE;

// /  --Engine Enable Startup Keys--
// /   Proving that a process was started by this application & not by somebody else.
// /   Required by anything that spawns a long lived worker. An application that never
// /   spawns one does not need it.
// /   A key travels in the environment & never on a command line, & a key that crossed a
// /   process boundary is spent when it validates. See Documentation/ABOUT_MANAGERS.txt.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$EngineEnableStartupKeys = TRUE;

// /  --Engine Enable Operating Environment Validation--
// /   Reporting whether the host is configured the way this application needs.
// /   Kernel settings, sandbox availability, the service manager & the policy files.
// /   This is what -fp & -v report. An application with no installer does not need it.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$EngineEnableEnvironmentValidation = TRUE;

// /  --Engine Enable Resource Limits--
// /   Reading a per operation resource limit & turning it into something a process
// /   supervisor understands.
// /   Required by an application that bounds how much of a machine one unit of work may
// /   consume. An application that does not bound its work does not need it.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$EngineEnableResourceLimits = TRUE;
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / --Engine Behaviour--
// /
// /  --Engine Reports Its Own Load--
// /   Writes one log line when the Engine has been verified & loaded.
// /   An operator diagnosing a host running several applications wants to see which
// /   Engine each one loaded & when. An operator running one application does not.
// /   This has no effect unless the application has verbose logging enabled, because the
// /   line is ordinary activity rather than a warning.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$EngineReportsItsOwnLoad = TRUE;

// /  --Engine Per Manager Log Files--
// /   Gives every manager process a logfile of its own, named for the manager.
// /   The listener writes to a Core-Manager file, the resource manager to a
// /   Resource-Manager file, & so on for every role that runs.
// /   Leave this FALSE unless a manager is writing enough to matter.
// /   Four managers sharing one file is readable & four files is four things to open.
// /   Set it TRUE on a busy installation. Every process appending to one file is the only
// /   way two entries can ever interleave, & separate files remove the possibility rather
// /   than guarding against it.
// /   An append shorter than the pipe buffer is atomic anyway & most entries are.
// /   A stream inspection dump or a compiler log is not, & those are the entries worth
// /   not corrupting.
// /   Valid options are TRUE or FALSE.
// /   Default is FALSE.
$EnginePerManagerLogFiles = FALSE;

// /  --Engine Per Worker Log Files--
// /   Gives every tracked worker a logfile of its own, named for the worker.
// /   The name is taken from the worker identifier the application supplies, so one unit
// /   of work can be followed from beginning to end in a file that holds nothing else.
// /   Leave this FALSE for HRConvert2. A conversion is short & shares a file happily, &
// /   one file per conversion on a busy server is a directory nobody can read.
// /   It is here for an application that dispatches long running work.
// /   HRProtect scanning a filesystem for an hour is the case this exists for.
// /   An application that sets it TRUE must also set $LogWorkerIdentifier before the
// /   logging environment is verified, or the setting has nothing to name a file with.
// /   Valid options are TRUE or FALSE.
// /   Default is FALSE.
$EnginePerWorkerLogFiles = FALSE;

// /  --Engine Strict Contract--
// /   Refuses to load when the application has not supplied every value the contract
// /   requires, instead of falling back to a default for the ones that are missing.
// /   Set this to TRUE while developing an application against the Engine. A missing
// /   contract value is a mistake in the application & is worth failing loudly for.
// /   Set this to FALSE on anything shipped. A released application should degrade rather
// /   than refuse, & every Engine read of a contract value carries a local fallback that
// /   configuration cannot overwrite.
// /   Valid options are TRUE or FALSE.
// /   Default is FALSE.
$EngineStrictContract = FALSE;
// / -----------------------------------------------------------------------------------
?>
