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
// / v4.0.7.
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

// / HRConvert2 is started through convertCore.php.
$EngineEntryPoint = 'convertCore.php';
$EngineApplicationSlug = 'hrconvert2';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / THE PROVIDERS. Every one names a function this application defines, & the Engine calls
// / what it is given without knowing anything else about it.
// / This is the whole of how the Engine learns about an application. Six names. The log provider
// / joined them at v4.0.6, & the data policy provider was retired at v4.0.5, because the Engine
// / knows what it serves now.
// /
// / An Engine with none of them still runs & does less, which is the correct degradation
// / rather than a failure. See ABOUT_ENGINE_CONTRACT.txt.

// /   Reports on the parts of the environment that belong to this application.
$EngineEnvironmentProvider = 'applicationEnvironmentFindings';

// /   Asks a human a question & returns their answer. An empty answer is a refusal.
$EngineOperatorPrompt = 'askOperator';

// /   Returns the configuration model. What a config file cannot say about itself.
$EngineConfigModelProvider = 'applicationConfigModel';

// /   Repairs whatever this application manages.
$EngineRepairProvider = 'fixManagedPermissions';

// /   Moves a manager that has run for a while on to the logfile it should be writing now.
$EngineLogProvider = 'refreshLogFile';

// /   A real config.php at defaults. What this application's configuration looks like.
$EngineConfigTemplate = 'Contract/config-template.php';

// /   Handles arguments beginning --app. This application has none yet, & the first
// /   will be conversion from the command line.
$EngineApplicationArgumentHandler = '';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The pipelines this application accepts & the exact version each must report.
// / This is an allowlist. A folder under Resources/Pipelines that is not named here is never
// / read, never required & never enumerated, however it got there.
// / Every family this application converts is listed, & convertCore.php converts nothing on
// / its own. A family removed from this list becomes unavailable rather than falling back.
// / Comment one out only to test that.
// / Accepting a pipeline, or moving one to a new version, is one line here. The Engine is not
// / touched, so no Engine version moves for it. Until v3.9.9 this list was written into the
// / Pipeline Core, which belongs to the Engine, & each change to it was a new Pipeline Core.
// / The order is the order pipelines are enumerated & listed in.
$EngineAcceptedPipelines = array(
  'Stream' => 'v4.0.0',
  'Scad' => 'v4.0.5',
  'OCR' => 'v4.0.5',
  'Document' => 'v4.0.6',
  'Subtitle' => 'v4.0.6',
  'SVG' => 'v4.0.6',
  'Drawing' => 'v4.0.6',
  'Image' => 'v4.0.6',
  'Model' => 'v4.0.6',
  'Video' => 'v4.0.6',
  'Ebook' => 'v4.0.6',
  'Audio' => 'v4.0.6',
  'Archive' => 'v4.0.6',
  // / Scanner pipelines. They declare no formats & are chosen by name.
  'ClamAV' => 'v3.9.9',
  // / The first pipeline of kind file. Operations that do not care what is in a file.
  'Files' => 'v4.0.0',
  'ScanCore' => 'v3.9.9');

// / The shared modules a pipeline may load & the exact version each must report.
// / libreOffice.php serves the Document & OCR pipelines alike, so neither of them owns it.
$EngineAcceptedSharedModules = array(
  'libreOffice.php' => 'v4.0.6');

// / Where each of this application's own pins reads its version from.
// / php convertCore.php --build --pins --write writes every pin from the files named here,
// / so none of this application's pins is moved by hand. The kernel's & the Engine's are,
// / because the build runs inside both & cannot rewrite what loads it. A pattern may match
// / several files, such as one for each interface, & they must all declare the same
// / version. Paths are relative to the installation. The Engine knows where its own
// / components live, so they are not listed.
// / $RequiredSecretVersion has no entry, because no shipped file declares it. It is the
// / format of a file this application writes, & it is moved by hand when that format changes.
$EnginePinSources = array(
  'ConfigVersion' => 'Resources/Contract/config-template.php',
  'DependsVersion' => 'Resources/Contract/depends.php',
  'GuiVersion' => 'UI/*/uiVersionInfo.php',
  'LanguageVersion' => 'UI/*/Languages/*/languageStrings.php');

// / Every location this application keeps on disk, by the setting that holds it, & its role.
// / The role says what a location IS. The Engine decides what that means for its owner & its
// / mode, & this contract cannot ask for a mode, so a location can never be made writable by
// / the account root is protecting itself from. -fp & the Environment Manager apply them.
// /   code       The installation. root's, 0755 & 0644, read by the web server account.
// /   data-root  The data location itself. root's & 0755, & never walked.
// /   secret     The folder secrets are kept in. root's, the web server account's group &
// /              0750, & never walked.
// /   web        A folder the web server account writes. 0700 & never walked by root.
// /   served     A web folder the web server hands to browsers. The Engine writes the Apache
// /              rules that make every file in it an inert download, & proves they are in force.
// /              It must be inside HostedLocation & below it, or the Engine refuses it.
// /   root-only  root alone. 0700 & 0600.
// /   shared     A folder other software shares. Checked & never walked.
// / A location inside another keeps its own role, so DATA stays the web server account's
// / inside an installation that is root's. See Documentation/ABOUT_MANAGED_LOCATIONS.txt.
// / The data location is a root root owns. A folder for each kind of file hangs off it, & each
// / has its own role. Sessions & the web server account's cache are that account's, root's log
// / folder & the previous installation are root's. The data location itself is never walked &
// / only its own owner & mode are corrected, which is what the data-root role means. HomeLoc is
// / no longer listed, because the cache folder is the web server account's home now.
// / SecretLoc is Secrets, where this application keeps its install secret from v4.0.4. Root
// / writes it & the web server account's group reads it, & no other account can list the folder.
// / The data location is named by $DataRootLoc here, not $ConvertLoc. $ConvertLoc is narrowed to
// / the session area a request uses, so it is not the root on the command line where this is
// / applied. $DataRootLoc always holds the configured root. SessionLoc is the session area below
// / it, which is what $ConvertLoc narrows to.
$EngineManagedLocations = array(
  'ProprietaryLoc' => 'shared',
  'InstLoc' => 'code',
  'ConvertTemp' => 'served',
  'DataRootLoc' => 'data-root',
  'SecretLoc' => 'secret',
  'SessionLoc' => 'web',
  'CacheLoc' => 'web',
  'LogDir' => 'web',
  'ManagerSocketDir' => 'web',
  'EngineDir' => 'root-only',
  'BackupLoc' => 'root-only');

// / The entries at the top of a release that make up the application, & nothing else.
// / -fp run from a tree that is not installed copies exactly these into the application folder
// / in the data location, & the hosted location loses only what is a copy of one of them.
// / Whatever else shares the folder a release was unpacked into is never taken along & never
// / removed. DATA is not one of them, because the web server serves it where it is.
// / See Documentation/ABOUT_MANAGED_LOCATIONS.txt.
$EngineApplicationLayout = array('convertCore.php', 'index.html', 'README.md', 'LICENSE', 'Documentation', 'Resources', 'UI');

// / Paths inside an installation that no release of this application ships, by their path below
// / it. -fp takes each one away as root, without following anything in it, once it has given the
// / rest of the installation to root, so an installation an earlier release left one in is one
// / root may run again. Each must stay inside the installation, & one that names . or .. is refused.
// / FFmpeg's build left its whole tree in Documentation/Build/ffmpeg-build until v4.0.6, links &
// / files with more than one name included, & root refuses an installation holding a link from
// / v4.0.1 on. -fp could not take it away, so the installation stayed one root refused, which is
// / why the v4.0.5 image never answered.
// / See Documentation/ABOUT_MANAGED_LOCATIONS.txt.
$EngineRetiredPaths = array('Documentation/Build/ffmpeg-build');

// / The folders of the application whose static files a browser fetches, & the types those are.
// / The hosted location holds a copy of each beside the loader, written by -fp as root. Nothing
// / of any other type is copied, so the loader is the only PHP left in the web root.
$EngineHostedAssetFolders = array('UI');
$EngineHostedAssetTypes = array('css', 'js', 'gif', 'png', 'jpg', 'jpeg', 'svg', 'ico', 'webp', 'woff', 'woff2', 'ttf', 'otf', 'html');

// / Apache configurations a dependency's package turns on as it installs, which this application
// / never uses. javascript-common arrives with the packages Calibre recommends & publishes
// / /usr/share/javascript as /javascript on every site the host serves. A dependency install that
// / turns one on turns it off again, & one an administrator turned on is left as it is.
$EngineUnwantedApacheConfs = array('javascript-common');

// / The settings in config.php that each shared name reads, & the values this application works
// / out from them as it starts. Operators keep the names they have always used, & the shared
// / layers ask for a value by a name that says nothing about this application.
// / HostedLocation is the folder the web server serves this application from, ServerURL is the
// / address of that folder, & DocumentRoot is the web server's document root. An application
// / that maps no hosted location serves nothing.
// / LiftNamespaceRestriction says whether -fp may lift the kernel's AppArmor restriction on
// / unprivileged namespaces where this application's own profile cannot carry a sandbox. An
// / application that maps nothing permits it, as every release did until v4.0.5.
// / RequireResourceAwareness says whether a conversion the listener gives no answer for is
// / refused. An application that maps nothing lets it run.
$EngineSettingNames = array(
  'FormatDetectionType' => 'SupportedFormatDetectionType',
  'WarnOnCapabilityMismatch' => 'WarnOnCapabilityMismatch',
  'DefaultScanner' => 'DefaultVirusScanner',
  'HostedLocation' => 'HostedLoc',
  'ServerURL' => 'FullURL',
  'DocumentRoot' => 'ServerRootDir',
  'MaintainProtectionFile' => 'MaintainHTAccess',
  'LiftNamespaceRestriction' => 'AllowUnprivilegedNamespaces',
  'RequireResourceAwareness' => 'RequireResourceAwareness');
// / -----------------------------------------------------------------------------------
?>
