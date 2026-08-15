<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRConvert2, Copyright on 8/15/2026 by Justin Grimes, www.github.com/zelon88
// /
// / LICENSE INFORMATION ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / APPLICATION INFORMATION ...
// / This application is designed to provide a web-interface for converting file formats
// / on a server for users of any web browser without authentication. 
// /
// / FILE INFORMATION ...
// / v3.6.9.
// / This file contains the configuration information for HRConvert2.
// / Fill out this file completely & accurately before running the application.
// / Serious filesystem damage could occur from incorrect directory settings.
// / Be careful to preserve all syntax & formatting.
// /
// / HARDWARE REQUIREMENTS ... 
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / DEPENDENCY REQUIREMENTS ... 
// / This application requires Debian Linux, Apache 2.4, PHP 8+, FFMPEG, Dia, LibreOffice, 
// / Mkisofs, 7zip, Unoconv, libgxps-utils, Tesseract, Unzip, OpenSCAD, Rar, Inkscape,
// / Unrar, ClamAV, MeshLab, PopplerUtils, PDFTOTEXT, ImageMagick, bwrap & xvfb-run.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / ------------------------------

// /  --Config Version--
// /   The version of HRConvert2 in which this config file last gained or lost a setting.
// /   The core refuses to run against a config file that is missing settings it requires.
// /   Do not change this value by hand. Replacing config.php with a newer one is the correct fix.
$ConfigVersion = 'v3.6.9';

// / ---Security Informations---
// /
// /  --Server URL--
// /   Externally or internally accesible domain or IP.
// /   Do not include a trailing slash.
// /   Default is localhost.
$URL = 'localhost';
// /  --Virus Scanning--
// /   Scan for viruses before performing file operations.
// /   Requires ClamAV to be installed on the server.
// /   Set to TRUE to enable virus scanning with ClamAV during file operations.
// /   Set to FALSE to disable virus scanning during file operations.
// /   The --User Virus Scanning-- config entry has a major impact on how regular virus scans are performed.
// /   If set to TRUE & --User Virus Scanning-- is set to TRUE infected files detected during virus scans will not be removed automatically.
// /   If set to TRUE & --User Virus Scanning-- is set to FALSE any infected file will immediately be deleted upon detection.
// /   If set to TRUE & --User Virus Scanning-- is set to TRUE incoming file uploads will not be scanned for viruses.
// /   If set to TRUE & --User Virus Scanning-- is set to FALSE incoming file uploads will be scanned for viruses.
// /   Valid options are TRUE or FALSE.
// /   Defalt is FALSE.
$VirusScan = FALSE;
// /  --User Virus Scanning--
// /   Provide users with options to scan their uploaded files for viruses.
// /   Requires ClamAV to be installed on the server.
// /   Set to TRUE to allow users to upload potentially infected files.
// /   Set to FALSE to disallow users uploading potentially infected files.
// /   This config entry has a major impact on how regular virus scans are performed.
// /   If set to TRUE & --Virus Scanning-- is set to TRUE infected files detected during virus scans will not be removed automatically.
// /   If set to FALSE & --Virus Scanning-- is set to TRUE any infected file will immediately be deleted upon detection.
// /   If set to TRUE & --Virus Scanning-- is set to TRUE incoming file uploads will not be scanned for viruses.
// /   If set to FALSE & --Virus Scanning-- is set to TRUE incoming file uploads will be scanned for viruses.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$AllowUserVirusScan = TRUE;
// /  --User Virus Scanning ScanCore Memory Limit--
// /   The number of bytes of memory ScanCore is allowed to allocate to large files during User Virus Scans.
// /   Files larger than this limit will be broken into chunks controlled by the --User Virus Scanning ScanCore Chunk Size-- config entry.
// /   Default is 268435456.
$ScanCoreMemoryLimit = 268435456;
// /  --User Virus Scanning ScanCore Chunk Size--
// /   In order to scan files that are larger than the memory limit, large files will be broken into chunks.
// /   The number of bytes to break large files into in order to fit them into memory.
// /   Default is 134217928.
$ScanCoreChunkSize = 134217928;
// /  --User Virus Scanning ScanCore Debug Mode--
// /   Enable an absolutely insane amount of verbosity from ScanCore during file scan operations.
// /   If set to TRUE these events will be included in the report that is submitted to the user.
// /   If set to FALSE a normal amount of logging will be submitted to the user. Enough to get the job done.
// /   If you scanned an entire 500GB hard drive with this set to TRUE ScanCore would generate 10's of GB worth of logs.
// /   This setting will have an impact on ScanCore scanning performance.
// /   Seriously, it's a lot of logs.
// /   Valid options are TRUE or FALSE.
// /   Default is FALSE.
$ScanCoreDebug = FALSE;
// /  --User Virus Scanning ScanCore Enhanced Verbosity--
// /   Enable an absolutely insane amount of console output from ScanCore during file scan operations.
// /   If set to TRUE these events will be included in the log file that is stored on the server.
// /   If set to FALSE a normal amount of logging will be stored on the server. Enough to get the job done.
// /   If you scanned an entire 500GB hard drive with this set to TRUE ScanCore would generate 10's of GB worth of logs.
// /   This setting will have an impact on ScanCore scanning performance.
// /   Seriously, it's a lot of logs.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$ScanCoreVerbose = TRUE;
// /  --Delete Build Environment--
// /   Automatically remove the build environment when it is no longer needed.
// /   Production servers should not keep the 'Build' folder in the --Installation Directory-- as a security precaution.
// /   If set to TRUE, the 'Build' folder in the root of the --Installation Directory-- will be recursively deleted.
// /   If set to FALSE, the 'Build' folder in the root of the --Installation Directory-- will NOT be deleted.
// /   This 'Build' folder could be used by an adversary to obtain configuration information about the application or server.
// /   This will NOT remove any documentation, logs, or required configuration files.
// /   If you want to keep the 'Build' folder, consider moving it out of the hosted '/var/www/html' directory.
// /   It is recommended to eventually set this to TRUE.
// /   Valid options are TRUE or FALSE.
// /   Default is FALSE.
$DeleteBuildEnvironment = FALSE;
// /  --Delete Development Documentation--
// /   Automatically remove 'README.md' & 'Documentation/CHANGELOG.txt' files when they are no longer needed.
// /   Production servers should not keep these files in the --Installation Directory-- as a security precaution.
// /   If set to TRUE, the 'README.md' & 'Documentation/CHANGELOG.txt' files will be deleted.
// /   If set to FALSE, the 'README.md' & 'Documentation/CHANGELOG.txt' files will NOT be deleted.
// /   These files could be used by an adversary to obtain configuration information about the application or server.
// /   This will NOT remove any other documentation, logs, or required configuration files.
// /   If you want to keep these files, consider moving them out of the hosted '/var/www/html' directory.
// /   It is recommended to eventually set this to TRUE.
// /   Valid options are TRUE or FALSE.
// /   Default is FALSE.
$DeleteDevelopmentDocumentation = FALSE;
// /  --Stream Duration Timeout--
// /   Set the maximum amount of time in minutes that FFMPEG will stream a file from a streaming provider for a user.
// /   This setting must be lower than the PHP execution timer set in php.ini.
// /   This setting must be lower than the --Delete Threshold-- located in the ---General Information--- section of this config.php file.
// /   This setting is costly because it requires FFMPEG & HRConvert2 to both maintain dedicated threads for the duration of the users stream.
// /   On popular public facing servers, setting this too high can result in the server becoming overwhelmed by on-going streams.
// /   Valid options are integers greater than 1, but not more than the PHP execution time or the --Delete Threshold--.
// /   This option MUST be set higher than 0.
// /   Default is 15
$StreamWatchTimeout = 15;
// /  --Stream Connection Timeout--
// /   Set the minimum amount of time in seconds that FFMPEG will wait after attempting to make a connection to a remote stream provider.
// /   Valid options are integers between 3 and 10.
// /   This option MUST be set higher than 2.
// /   Default is 10.
$StreamConnectionTimeout = 10;
// /  --Allow Streams Over HTTP---
// /   During stream conversions, HRConvert2 will attempt to connect to the stream provider specified in the stream file provided by the user.
// /   If set to TRUE, HRConvert2 will attempt to connect to non-encrypted, plain-text providers over HTTP when specified by the stream file.
// /   If set to FALSE, HRConvert2 will not utilize HTTP.
// /   Requires FFMPEG v6.1 or later. Note that FFMPEG v2.0 through v6.0 carry a severe vulnerability related to downloading stream files.
// /   If you have FFMPEG v6.0 or earlier installed, disable Stream Conversions & remove m3u8 from the list of supported file formats.
// /   Check FFMPEG version by opening a terminal on the server and running 'ffmpeg -v'
// /   Valid options are TRUE or FALSE.
// /   Default is FALSE.
$AllowStreamOverHTTP = FALSE;
// /  --Stream Inspection Layers---
// /   Stream operations are especially sensitive as they require allowing dependencies to connect to remote hosts from user-supplied URLs.
// /   HRConvert2 inspects stream files uploaded by the user to prevent malicious abuse of the FFMPEG dependency.
// /   During inspection, HRConvert2 may discover that a stream file will request FFMPEG to download additional stream files from remote hosts.
// /   The first inspection is performed on the file that was uploaded by the user. This inspection cannot be disabled. 
// /   Subsequent inspections are performed on the stream that FFMPEG would be asked to download, if the operation were allowed to proceed.
// /   The maximum possible cost for inspecting each stream conversion is one remote onnection * ($StreamInspectionLayers * $StreamInspectionFilesPerLayer).
// /   This setting tells HRConvert2 how many nested layers of stream files it is allowed to inspect before making a decision based on cost.
// /   This setting determines how much effort HRConvert2 is willing to spend before an inspection is considered complete.
// /   Set to 0 to not perform any inspection on remote stream files. Local stream files uploaded by the user will still be inspected.
// /   Valid options are integers 0 and larger.
// /   Default is 3.
// /   Maximum reccomended value is 10.
$StreamInspectionLayers = 3;
// /  --Stream Inspection Files Per Layer---
// /   Stream operations are especially sensitive as they require allowing dependencies to connect to remote hosts from user-supplied URLs.
// /   HRConvert2 inspects stream files uploaded by the user to prevent malicious abuse of the FFMPEG dependency.
// /   During inspection, HRConvert2 may discover that a stream file will request FFMPEG to download additional stream files from remote hosts.
// /   The first inspection is performed on the file that was uploaded by the user. This inspection cannot be disabled. 
// /   Subsequent inspections are performed on the stream that FFMPEG would be asked to download, if the operation were allowed to proceed.
// /   The maximum possible cost for inspecting each stream conversion is one remote onnection * ($StreamInspectionLayers * $StreamInspectionFilesPerLayer).
// /   This setting tells HRConvert2 how many files per layer of stream files it is allowed to inspect before making a decision based on cost.
// /   This setting determines how much effort HRConvert2 is willing to spend before an inspection is considered complete.
// /   Set to 0 to not perform any inspection on remote stream files. Local stream files uploaded by the user will still be inspected.
// /   Valid options are integers 0 and larger.
// /   Default is 7.
// /   Maximum reccomended value is 15.
$StreamInspectionFilesPerLayer = 7;
// /  --Default Stream Inspection Forfeit Action---
// /   Stream operations are especially sensitive as they require allowing dependencies to connect to remote hosts from user-supplied URLs.
// /   HRConvert2 inspects stream files uploaded by the user to prevent malicious abuse of the FFMPEG dependency.
// /   During inspection, HRConvert2 may discover that a stream file will request FFMPEG to download additional stream files from remote hosts.
// /   The first inspection is performed on the file that was uploaded by the user. This inspection cannot be disabled. 
// /   Subsequent inspections are performed on the stream that FFMPEG would be asked to download, if the operation were allowed to proceed.
// /   The maximum possible cost for inspecting each stream conversion is one remote onnection * ($StreamInspectionLayers * $StreamInspectionFilesPerLayer).
// /   This setting tells HRConvert2 what to do in the event that it has reached the end of it's inspection budget with no adverse findings.
// /   If set to 'ALLOW', HRConvert2 will allow FFMPEG to process the file only when the inspection has exhausted the budget with no findings.
// /   If set to 'DENY', HRConvert2 will not allow FFMPEG to process the file unless it can afford to inspect & approve every required stream file.
// /   Valid options are 'DENY' or 'ALLOW'.
// /   Default is 'DENY'
$DefaultStreamInspectionForfeitAction = 'DENY';
// /  -- Maximum Stream Inspection Size--
// /   The Stream Inspector will download up to this many bytes of a manifest file for validation during operation.
// /   The max file potential download amount observed at the server per stream request is...
// /   --Stream Inspection Layers--  X  --StreamInspectionFilesPerLayer--  X  --MaxStreamInspectionFileSize--
// /   Valid options are integers greater than 940.
$MaxStreamInspectionFileSize = 8191;
// /  --Allow SCAD Include Resolution--
// /   OpenSCAD sources reference other sources with include <file> & use <file>.
// /   A multi file assembly cannot render at all unless those references resolve to something.
// /   If set to FALSE, every reference is commented out & multi file assemblies will not render.
// /   If set to TRUE, an include or use may resolve to another source the SAME user uploaded.
// /   Resolution matches on filename alone & ignores whatever directory the reference carried.
// /   A reference that matches no uploaded file is commented out regardless of this setting.
// /   This setting does NOT control whether OpenSCAD may read arbitrary files.
// /   HRConvert2 removes every file reading primitive from every uploaded source unconditionally.
// /   import(), surface(), the deprecated import_stl() family & every dxf file= form are always removed.
// /   Those removals happen whether this setting is TRUE or FALSE & cannot be turned off.
// /   Every uploaded source is sanitized into a temporary location before OpenSCAD runs.
// /   A resolved reference points at a sanitized copy, never at the file the user uploaded.
// /   Nothing outside the users own session directory can be reached at any point.
// /   Enabling this therefore lets one uploaded file read another file the same user uploaded.
// /   Both files came from the same person, in the same session, in the same directory.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$AllowSCADIncludeResolution = TRUE;
// /  --SCAD Conversion Timeout--
// /   Set the maximum number of seconds an OpenSCAD render is permitted to run.
// /   OpenSCAD has no execution limit of its own & renders until it finishes or is killed.
// /   A crafted model can pin a CPU core indefinitely through recursion or an extreme $fn value.
// /   This value bounds an attackers cost, not just a users patience.
// /   A stranger can upload a three line file that consumes a PHP worker for this entire duration.
// /   Raising it multiplies the cost of the cheapest denial of service available against this server.
// /   Renders are niced to the lowest priority so a runaway model yields to everything else.
// /   A large real world assembly of roughly thirty sources renders well within the default.
// /   This setting must be lower than the PHP execution timer set in php.ini.
// /   Valid options are integers greater than 0.
// /   Default is 180.
$SCADConversionTimeout = 180;
// /  --Minimum Assimp Version--
// /   Assimp is the engine behind 3D skeletal scene graphs and modern format exports.
// /   This minimum exists for command line reliability, not for security.
// /   Assimp 5.0 introduced essential stability updates for modern web models.
// /   Versions prior to 5.0 feature an erratic CLI parameter processing loop.
// /   5.0 is pinned because it is the interface HRConvert2 builds its commands against.
// /   Check the installed version by running 'assimp version' in a terminal on the server.
// /   Format is major.minor.
// /   Default is '5.0'.
$MinimumAssimpVersion = '5.0';
// /  --Use PyMeshLab Python Bindings--
// /   MeshLab replaced its legacy server application with a Python library package.
// /   Enabling this toggle forces the core to pass geometry repairs to a local Python script.
// /   This bypasses the need for an active X11 virtual frame display buffer on your host.
// /   When enabled, it completely bypasses the legacy binary year-month version validation check.
// /   Requires 'python3' and the 'pymeshlab' package to be manually installed via pip on the server.
// /   True runs the modern Python script route, False defaults to the standard meshlabserver binary.
// /   Default is FALSE.
$UsePyMeshLab = FALSE;
// /  --Minimum MeshLab Version--
// /   MeshLab is the engine behind 3D geometry optimization and manifold rectification.
// /   This minimum exists for headless server parity, not for security.
// /   MeshLab changed its numbering system away from legacy version branches.
// /   The 2020.09 build is the last stable release maintaining meshlabserver.
// /   2020.09 is pinned because it is the interface HRConvert2 builds its commands against.
// /   Check the installed version by running 'meshlabserver --help' in a terminal on the server.
// /   Format is year.month.
// /   Default is '2020.09'.
$MinimumMeshlabVersion = '2020.09';
// /  --Minimum ImageMagick Version--
// /   ImageMagick is the engine behind most image conversions.
// /   This minimum exists for command line compatibility, not for security.
// /   ImageMagick command line syntax changed significantly between v6 & v7.
// /   ImageMagick v6 is what you get when you install ImageMagick using apt-get commands.
// /   ImageMagick v7 can be built from source using the included build script.
// /   The included ImageMagick v7 build script is located in the Documentation/Build folder.
// /   Check the installed version by running 'convert --version' or 'magick --version' in a terminal on the server.
// /   Format is major.minor.patch.
// /   Default is '7.1'.
$MinimumImageVersion = '7.1';
// /  --Minimum Inkscape Version--
// /   Inkscape is the engine behind every SVG conversion.
// /   This minimum exists for command line compatibility, not for security.
// /   Inkscape replaced its entire command line interface at version 1.0.
// /   The 0.92 flags such as --export-png were removed rather than deprecated.
// /   1.2 is pinned because it is the interface HRConvert2 builds its commands against.
// /   Check the installed version by running 'inkscape --version' in a terminal on the server.
// /   Format is major.minor.
// /   Default is '1.2'.
$MinimumInkscapeVersion = '1.2';
// /  --Minimum OpenSCAD Version--
// /   HRConvert2 does not probe OpenSCAD for capabilities & does not accommodate older builds.
// /   Pinning a minimum version means the export formats below can be trusted as written.
// /   2021.01 is the last long standing stable release & is widely available in distribution packages.
// /   Check the installed version by running 'openscad --version' in a terminal on the server.
// /   Format is the OpenSCAD YYYY.MM release stamp.
// /   Default is '2021.01'.
$MinimumSCADVersion = '2021.01';
// /  --Minimum FFMPEG Version--
// /   Audio & video conversions read a local file & never fetch anything remote.
// /   This minimum exists for feature & format support, not for security.
// /   Format is major.minor.
// /   Default is '4.0'.
$MinimumFFMPEGVersion = '4.0';
// /  --Minimum Stream FFMPEG Version--
// /   FFMPEG v2.0 through v6.0 carry a severe vulnerability related to downloading stream files.
// /   Those builds apply their own protocol whitelist to segments referenced inside a playlist.
// /   The whitelist HRConvert2 supplies is therefore bypassed & stream inspection cannot protect you.
// /   Stream conversions are refused entirely when the installed build does not meet this minimum.
// /   A build that does not report a parseable version number is also refused.
// /   This value must never be set below 6.1.
// /   Format is major.minor.
// /   Default is '6.1'.
$MinimumStreamFFMPEGVersion = '6.1';
// /  --Minimum LibreOffice Version--
// /   LibreOffice is the engine behind every document, spreadsheet & presentation conversion.
// /   This minimum exists for format support & conversion reliability, not for security.
// /   LibreOffice changed versioning schemes in 2024, moving from 7.6 directly to 24.2.
// /   The new scheme is year.month, so a major of 24 or higher is newer than a major of 7.
// /   HRConvert2 compares these values numerically, so both schemes are ranked correctly.
// /   Setting a minimum of 7.0 therefore accepts every modern build under either scheme.
// /   Check the installed version by running 'libreoffice --version' in a terminal on the server.
// /   Format is major.minor.
// /   Default is '7.0'.
$MinimumLibreOfficeVersion = '7.0';
// / ------------------------------

// / ------------------------------
// / ---Directory Information---
// / 
// /  --Installation Directory--
// /   Install HRConvert2 files to the following directory.
// /   Do not include a trailing slash.
// /   Do not use a path with whitespace.
// /   Changing this value is not recommended.
// /   Default is '/var/www/html/HRProprietary/HRConvert2'
$InstLoc = '/var/www/html/HRProprietary/HRConvert2';
// /  --Proprietary Directory--
// /   Install the HRConvert2 folder to the following directory.
// /   Do not include a trailing slash.
// /   Do not use a path with whitespace.
// /   Changing this value is not recommended.
// /   Default is '/var/www/html/HRProprietary'
$ProprietaryLoc = '/var/www/html/HRProprietary';
// /  --Server Root Directory--
// /   This should be pointed at the root of your web server directory.
// /   Do not include a trailing slash.
// /   Do not use a path with whitespace.
// /   Changing this value is not recommended.
// /   Default is '/var/www/html'
$ServerRootDir = '/var/www/html';
// /  --Data Storage Directory--
// /   This is where temporary data files are stored.
// /   Do not include a trailing slash.
// /   Do not use a path with whitespace.
// /   Default is '/DATA/HRConvert2'
$ConvertLoc = '/DATA/HRConvert2';
// /  --Log Storage Directory--
// /   This is where permanent Log files are stored.
// /   Do not include a trailing slash.
// /   Do not use a path with whitespace.
// /   Default is $ConvertLoc.'/Logs'
$LogDir = $ConvertLoc.'/Logs';
// /  --Home Directory--
// /   This is the Home directory for the web server user.
// /   Do not include a trailing slash.
// /   Do not use a path with whitespace.
// /   Changing this value is not recommended.
// /   Default is $ConvertLoc.
$HomeLoc = $ConvertLoc;
// /  --Backup Location--
// /   Where the previous installation is preserved after a successful update.
// /   The update process moves the old installation aside inside the installation
// /   directory first, because rename() is only atomic within one filesystem & that is
// /   what makes a rollback instant. That location is served by Apache, so the old
// /   installation is copied here & removed from there as soon as the update succeeds.
// /   A stale convertCore.php left inside the web root would be a second, older copy of
// /   the application answering requests over HTTP.
// /   This location must NOT be inside the web root & must NOT be inside the installation
// /   directory. It holds exactly one previous version & is replaced on every update.
// /   The DATA directory is excluded from the backup, because it holds live user sessions
// /   that have already been carried into the new installation.
// /   Do not include a trailing slash.
// /   Do not use a path with whitespace.
// /   Default is $ConvertLoc.'/Last-Installed-Version'
$BackupLoc = $ConvertLoc.'/Last-Installed-Version';
// /  --Append Log Hash To Log Files--
// /   This setting is used to append a 12 digit unique identifier to log file names.
// /   This randomizes log file names across multiple installations & servers.
// /   Helps to obfuscate log filenames to protect from against blind filename probes.
// /   If set to TRUE, HRConvert2 will add a random 12 digit number to the end of the log file name.
// /   If set to FALSE, HRConvert2 will not add any random digits to the log file name.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$AppendLogHashToLogFiles = TRUE;
// /  --Unique Daily Log Hash Rotation--
// /   Log files are appended with t
// /   Do not include a trailing slash.
// /   Do not use a path with whitespace.
// /   Default is $ConvertLoc.'/Logs'
$UniqueDailyLogHash = TRUE;
// / ------------------------------

// / ------------------------------
// / ---General Information---
// / 
// /  --Application Name String--
// /   The default name to display for this application.
// /   You can change this to make it fit with other services your organization provides.
// /   Default is HRConvert2.
$ApplicationName = 'HRConvert2';
// /  --Application Title String--
// /   The default title to display in taskbars & window managers.
// /   You can change this to make it fit with other services your organization provides.
// /   Default is Convert Anything!
$ApplicationTitle = 'Convert Anything!';
// /  --Enable Automatic Updates--
// /   Allow HRConvert2 to replace its own application code from the command line.
// /   It is not possible to perform an update via the web interface.
// /   If set to TRUE, an administrator may run  php convertCore.php -u  to update.
// /   If set to FALSE, that command refuses & the application may only be updated by hand.
// /   Valid options are TRUE or FALSE.
// /   Default is FALSE.
$EnableAutoUpdates = TRUE;
// /  --Automatic Update Target Version--
// /   Which release an update installs when no version is supplied on the command line.
// /   If set to 'latest', the newest tagged release is installed.
// /   If set to 'edge', the current state of the master branch is installed.
// /   If set to a version such as 'v3.6.8', exactly that tag is installed.
// /   An update to a tag that does not exist fails rather than installing something else.
// /   A version supplied on the command line always overrides this setting.
// /   Default is 'latest'.
$AutoUpdateTargetVersion = 'latest';
// /  --Update Source Repository--
// /   The GitHub repository an update is fetched from, as owner/name.
// /   Change this only if you maintain your own fork & want updates to come from it.
// /   Default is 'zelon88/HRConvert2'.
$UpdateSourceRepository = 'zelon88/HRConvert2';
// /  --Maximum Update Package Size--
// /   The largest update package that will be accepted, in bytes.
// /   A source that serves something larger is refused rather than filling the disk.
// /   Default is 524288000, which is 500 megabytes.
$MaxUpdatePackageSize = 524288000;
// /  --Update Connection Timeout--
// /   The maximum number of seconds an update download may take in total.
// /   This bounds the whole transfer, not just the time taken to connect.
// /   Default is 1000.
$UpdateConnectionTimeout = 1000;
// /  --Supported Guis--
// /   The list of GUIs that are supported by this application.
// /   Before adding a supported GUI be sure to add the matching folder full of GUI files to /UI.
// /   Errors will occur if you add an element to this array without also adding a matching GUI folder.
// /   Default is 'Default', 'Wide'.
$SupportedGuis = array('Default', 'Wide', 'Original');
// /  --Default GUI--
// /   The default GUI to use.
// /   See README.md for the latest GUI support information.
// /   If the specified GUI is not available 'en' will be used instead.
// /   ISO 639-1 reference is available here at https://www.andiamo.co.uk/resources/iso-GUI-codes/
// /   Valid options are text strings that correspond GUI codes found in the list of --Supported GUIs--.
// /   Default is Default.
$DefaultGui = 'Default';
// /  --Allow User Selectable GUI--
// /   Provide users with the option to adjust which GUI is displayed via appending a parameter to the URL.
// /   Enable or disable dynamic GUI selection via the $_GET['gui'] variable.
// /   If set to TRUE a user will be able to select different GUIs via $_GET['gui'].
// /   If set to FALSE the $DefaultGui will always be used.
// /   To submit a $_GET request append ?gui=<CODE> to the URL & repalce <CODE> with name of the desired GUI.
// /   If a user attempts a GUI that is not available --Default GUI-- will be used instead.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$AllowUserSelectableGui = TRUE;
// /  --Supported Languages--
// /   The list of languages that are supported by this application.
// /   Before adding a supported language be sure to add the matching folder full of GUI files to /Languages.
// /   Errors will occur if you add an element to this array without also adding a matching Language folder.
// /   Default is 'en', 'fr', 'es', 'zh', 'hi', 'ar', 'ru', 'uk', 'bn', 'de', 'ko', 'it', 'pt', 'vi', 'tr', 'ja', 'id', 'pl', 'nl', 'sw', 'my', 'ur', 'fa', 'he', 'aii', 'arc'.
// /  --Supported Languages--
// /   The list of languages that are supported by this application.
// /   The key is the ISO 639-1 code & is the name of the folder inside /Languages.
// /   The value is the endonym, which is the name of the language in that language itself.
// /   The endonym is used for alt text & for the title of each flag button in the UI.
// /   A user who has landed on a language they cannot read still recognizes their own.
// /   Before adding a supported language be sure to add the matching folder to /Languages.
// /   Errors will occur if you add an element to this array without also adding a matching folder.
// /   Aramaic has no ISO 639-1 code, so aii & arc use their ISO 639-3 codes instead.
$SupportedLanguages = array(
  'en' => 'English',   'fr' => 'Français',    'es' => 'Español',
  'zh' => '中文',      'hi' => 'हिन्दी',        'ar' => 'العربية',
  'ru' => 'Русский',   'uk' => 'Українська',  'bn' => 'বাংলা',
  'de' => 'Deutsch',   'ko' => '한국어',        'it' => 'Italiano',
  'pt' => 'Português', 'vi' => 'Tiếng Việt',  'tr' => 'Türkçe',
  'ja' => '日本語',     'id' => 'Bahasa Indonesia',
  'pl' => 'Polski',    'nl' => 'Nederlands',  'sw' => 'Kiswahili',
  'my' => 'မြန်မာ',      'ur' => 'اردو',         'fa' => 'فارسی',
  'he' => 'עברית',     'aii' => 'ܣܘܪܝܝܐ',     'arc' => 'ܐܪܡܝܐ');
// /  --Default Language--
// /   The default language to use for GUI elements.
// /   See README.md for the latest language support information.
// /   If the specified language is not available 'en' will be used instead.
// /   ISO 639-1 reference is available here at https://www.andiamo.co.uk/resources/iso-language-codes/
// /   Valid options are ISO 639-1 language codes found in the list of --Supported Languages--.
// /   Default is en.
$DefaultLanguage = 'en';
// /  --Allow User Selectable Language--
// /   Provide users with the option to adjust which language is displayed via appending a parameter to the URL.
// /   Enable or disable dynamic language selection via the $_GET['language'] variable.
// /   If set to TRUE a user will be able to select different languages via $_GET['language'].
// /   If set to FALSE the $DefaultLanguage will always be used.
// /   To submit a $_GET request append ?language=<CODE> to the URL & repalce <CODE> with a 2 digit ISO 639-1 language code.
// /   If a user attempts a language that is not available --Default Language-- will be used instead.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$AllowUserSelectableLanguage = TRUE;
// /  --User Shareable File Links--
// /   Provide users with the option to generate shareable URLs for the files they upload or convert.
// /   If set to TRUE the user will be provided with buttons to create URLs to files that can be copied & pasted elsewhere.
// /   If set to FALSE the user will not be provided with the buttons to create URLs to files.
// /   Files with active links will be removed after the --File Deletion Age Threshold-- is met.
// /   Active file links will break after the --File Deletion Age Theshold-- is met.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$AllowUserShare = TRUE;
// /  --Allowed Conversion Types--
// /   The list of supported conversion types.
// /   Only conversion types contained in this list will be processed.
// /   If a conversion type is disabled, options for processing that conversion will not be displayed by the UI.
// /   Default is 'Document', 'Image', 'Model', 'Scad', 'Drawing', 'SVG', 'Video', 'Subtitle', 'Audio', 'Archive', 'Stream', 'OCR'.
$SupportedConversionTypes = array('Document', 'Image', 'Model', 'Scad', 'Drawing', 'SVG', 'Video', 'Subtitle', 'Audio', 'Archive', 'Stream', 'OCR');
// /  --File Deletion Age Theshold--
// /   Age in minutes of files to be deleted.
// /   Set to 0 to keep files forever.
// /   Valid options are integers 0 or larger.
// /   Default is 60.
$DeleteThreshold = 60;
// /  --Enhanced Logging Verbosity--
// /   Enable verbose logging.
// /   If set to TRUE all core events will be logged.
// /   If set to FALSE only errors & certain core events will be logged.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$Verbose = TRUE;
// /  --Maximum Log File Size--
// /   Set the number of bytes to store in each logfile before splitting to a new one.
// /  Default is 1048576.
$MaxLogSize = 1048576;
// /  --UI Element Font--
// /   Set the default font to use throughout HRConvert2 GUI elements.
// /   The selected font must be installed on the client's machine.
// /   If the font is not available the client default will be used.
// /   Default is Arial.
$Font = 'Arial';
// /  --Allow User Selectable Colors--
// /   Provide users with the option to adjust which colors are displayed via appending a parameter to the URL.
// /   Enable or disable dynamic GUI selection via the $_GET['color'] variable.
// /   If set to TRUE a user will be able to select different colors via $_GET['gui'].
// /   If set to FALSE the --Button Color-- will always be used.
// /   To submit a $_GET request append ?color=<CODE> to the URL & repalce <CODE> with name of the desired color.
// /   If a user attempts a color that is not available --Button Color-- will be used instead.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$AllowUserSelectableColor = TRUE;
// /  --Supported Colors--
// /   The list of colors that are supported by this application.
// /   Before adding a supported color be sure to add the matching $ButtonStyle code to styleCore.php for each insalled GUI.
// /   Errors will occur if you add an element to this array without also adding code to each GUI to support the added color.
// /   Default is 'red', 'green', 'blue', 'grey', 'orange', 'purple', 'dark'.
$SupportedColors = array('red', 'green', 'blue', 'grey', 'orange', 'purple', 'dark');
// /  --Button Color--
// /   Set the default color scheme to use for buttons.
// /   Valid options are 'RED', 'GREEN', 'BLUE' or 'GREY'.
// /   Default is BLUE.
$ButtonStyle = 'BLUE';
// /  --Spinner Style--
// /   Set the default spinner to use as a loading indicator while operations are being processed.
// /   Valid options are 0, 1, 2, 3, 4, 5 or 6.
// /   Default is 6.
$SpinnerStyle = 6;
// /  --Spinner Color--
// /   Set the default color to use for the loading spinner.
// /   If you would like the spinner to automatically match the rest of the color scheme, set this to $ButtonStyle.
// /   Valid options are  'RED', 'GREEN', 'BLUE', 'GREY' or '$ButtonStyle'.
// /   Default is $ButtonStyle.
$SpinnerColor = $ButtonStyle;
// /  --Show Full GUI--
// /   Set whether or not to display a full GUI by default.
// /   If set to TRUE a full GUI with text will be displayed.
// /   If set to FALSE a minimal GUI with only required elements will be displayed.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$ShowGUI = TRUE;
// /  --Show Fine Print--
// /   Set whether or not to display the Terms of Service & Privacy Policy links.
// /   If set to TRUE links to the --Terms of Service URL-- and --Privacy Policy URL-- will display at the bottom of the page.
// /   If set to FALSE links to the --Terms of Service URL-- and --Privacy Policy URL-- will be hidden.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$ShowFinePrint = TRUE;
// /  --Terms of Service URL--
// /   Set the URL to use for the Terms of Service link at te bottom of the GUI.
// /   Only takes effect if --Show Fine Print-- is set to TRUE.
$TOSURL = 'https://www.honestrepair.net/index.php/terms-of-service/';
// /  --Privacy Policy URL--
// /   Set the URL to use for the Privacy Policy link at te bottom of the GUI.
// /   Only takes effect if --Show Fine Print-- is set to TRUE.
$PPURL = 'https://www.honestrepair.net/index.php/privacy-policy/';
// / --RAR Archive Method--
// /   Set the software package to use for creating .rar archives.
// /   This setting allows you to specify which software to use when creating .rar archives.
// /   Currently only RAR is supported.
// /   Valid options are 'rar'.
// /   Default is rar.
$RARArchiveMethod = 'rar';
// / --File Operation Retry Count--
// /   Set this to the number of attempts to make during file operations.
// /   The core will attempt significant file operations this many times, with a pause in between.
// /   If a significant file operation fails, the core will retry the operation this many times.
// /   Valid options are integers smaller than 10.
// /   Default is 5.
$RetryCount = 5;
// / --Document Engine Sleep Timer--
// /   Set this to the number of seconds to wait after attempting to start the Document Conversion Engine for it to start.
// /   Try increasing this value if Logs indicate that the Document Conversion Engine won't start.
// /   Valid options are integers smaller than 60.
// /   Do not set this timer longer than the Execution Time specified in php.ini.
// /   Default is 5.
$DocumentEngineSleepTimer = 5;
// / --Substitute Patched Conversion Engine--
// /   The developer of HRConvert2 maintains an alternative fork of the "Unoconv" package.
// /   HRConvert2 can use the included copy of Unoconv, or the system copy of Unoconv.
// /   If your system uses Python 3.12 or later, set this to TRUE.
// /   If your system uses Python 3.11 or earlier, this can be set to either TRUE or FALSE.
// /   If set to TRUE, the included "zelon88 patched" version of Unoconv will be used for document conversions.
// /   If set to FALSE, the system version of Unoconv will be used for document conversins.
// /   Default is TRUE.
$UsePatchedDocumentEngine = TRUE;
// / ------------------------------

// / ------------------------------
// / --Supported File Format Information--
// /
// /  --Supported Archive Formats--
$UserArchiveArray = array('zip', 'rar', 'tar', '7z', 'iso');
// /  --Supported Dearchive Formats--
$UserDearchiveArray = array('zip', 'rar', 'tar', 'bz', 'gz', 'bz2', '7z', 'iso', 'vhd', 'vdi', 'tar.bz2', 'tar.gz', 'cbr', 'cbz');
// /  --Supported Document Formats--
$UserDocumentArray = array('txt', 'doc', 'docx', 'rtf', 'odt', 'pdf');
// /  --Supported Spreadsheet Formats--
$UserSpreadsheetArray = array('csv', 'xls', 'xlsx', 'ods');
// /  --Supported XPS Input Formats--
$UserXPSInputArray = array('xps', 'oxps');
// /  --Supported XPS Output Formats--
$UserXPSOutputArray = array('pdf');
// /  --Supported Presentation Input Formats--
$UserPresentationInputArray = array('pptx', 'ppt', 'potx', 'potm', 'pot', 'ppa', 'odp');
// /  --Supported Presentation Output Formats--
$UserPresentationOutputArray = array('pptx', 'ppt', 'potx', 'potm', 'pot', 'ppa', 'odp', 'pdf');
// /  --Supported Image Formats--
$UserImageArray = array('jpeg', 'jpg', 'jpe', 'png', 'bmp', 'gif', 'webp', 'cin', 'dds', 'dib', 'flif', 'avif', 'gplt', 'sct', 'xcf', 'heic', 'ico');
// /  --Supported Audio Input Formats--
$UserMediaInputArray = array('sox', 'spdif', 'spx', 'tta', 'u16be', 'u16le', 'u24be', 'u24le', 'u32be', 'u32le', 'u8', 'voc', 'wav', 'wv', 'wsaud', 'mulaw', 'mxf', 'mxf_d10', 'mxf_opatom', 'oga', 'ogg', 'opus', 'oss', 'psp', 'rawvideo', 's16be', 's16le', 's24be', 's24le', 's32be', 's32le', 's8', 'sbc', 'ilbc', 'ircam', 'latm', 'lrc', 'mp2', 'mp3', 'mlp', 'flac', 'g722', 'g723_1', 'g726', 'g726le', 'gsm', 'caf', 'daud', 'dts', 'eac3', 'f32be', 'f32le', 'f64be', 'f64le', 'ac3', 'ac4', 'adts', 'aiff', 'alaw', 'amr', 'aptx', 'aptx_hd', 'argo_asf', 'argo_cvg', 'ast', 'au', 'a64', 'aa', 'aac', 'aax', 'acm', 'act', 'adp', 'adx', 'aea', 'afc', 'aix', 'alp', 'amrnb', 'amrwb', 'apac', 'apc', 'ape', 'apm', 'argo_asf', 'binka', 'bit', 'boa', 'bonk', 'brstm', 'dfpwm', 'dsf', 'dss', 'epaf', 'fsb', 'fwse', 'g729', 'hca', 'idf', 'kux', 'kvag', 'laf', 'lavfi', 'loas', 'luodat', 'lvf', 'lxf', 'mca', 'mcc', 'megsts', 'mlv', 'mmf', 'mods', 'moflex', 'mpc8', 'msf', 'msnwctcp', 'mtaf', 'musx', 'nc', 'nistsphere', 'nsp', 'paf', 'pam_pipe', 'pbm_pipe', 'pfm_pipe', 'pp_bnk', 'psxstr', 'pva', 'pvf', 'qcp', 'rka', 'rl2', 'rpl', 'rso', 's337m', 'sap', 'sbg', 'scd', 'sdns', 'sdp', 'sds', 'sdx', 'siff', 'simbiosis_imx', 'sln', 'smk', 'smush', 'sol', 'svag', 'svs', 'tak', 'thp', 'tierexseq', 'tty', 'ty', 'usm', 'vag', 'vidc', 'vpk', 'vqf', 'w64', 'wady', 'wavarc', 'wsd', 'wsvqa', 'wve', 'xa', 'xbin', 'xbm_pipe', 'xmd', 'xpm_pipe', 'xwma', 'yop', 'wma', 'm4a');
// /  --Supported Audio Output Formats--
$UserMediaOutputArray = array('mp3', 'aac', 'ogg', 'wma', 'mp2', 'flac', 'm4a');
// /  --Supported Video Input Formats--
$UserVideoInputArray = array('smoothstreaming', 'svcd', 'swf', 'truehd', 'vc1', 'vc1test', 'vcd', 'vob', 'vvc', 'webm', 'yuv4mpegpipe', 'mpjpeg', 'mxf', 'mxf_d10', 'mxf_opatom', 'nut', 'obu', 'ogv', 'psp', 'rawvideo', 'rm', 'roq', 'rtp_mpegts', 'smjpeg', 'hevc', 'hls', 'image2', 'image2pipe', 'ipod', 'ismv', 'm4v', 'matroska', 'mjpeg', 'mkvtimestamp_v2', 'mov', 'mp4', 'mpeg', 'mpeg1video', 'mpeg2video', 'mpegts', 'mpegtsraw', 'mpegvideo', 'fbdev', 'film_cpk', 'filmstrip', 'gxf', 'h261', 'h263', 'h264', 'hds', 'avs2', 'avs3', 'cavsvideo', 'cavs', 'dirac', 'dnxhd', 'dv', 'dvd', 'evc', '3g2', '3gp', 'apng', 'argo_asf', 'argo_cvg', 'asf', 'asf_stream', 'avi', 'avif', 'avm2', '3dostr', '4xm', 'adf', 'ads', 'alias_pix', 'anm', 'argo_brp', 'asf_o', 'av1', 'avs', 'bethsoftvid', 'bfi', 'bink', 'bmv', 'brender_pix', 'brender', 'cdg', 'cdxl', 'cine', 'concat', 'cri', 'dcstr', 'derf', 'dfa', 'dhav', 'dsicin', 'dtshd', 'dxa', 'ea', 'exr', 'fits', 'flic', 'frm', 'gdv', 'genh', 'gif', 'idcin', 'iff', 'ifv', 'ingenient', 'ipmovie', 'iss', 'iv8', 'ivf', 'ivr', 'j2k', 'jp2', 'jv', 'live_flv', 'lmlm4', 'mtv', 'mv', 'mvi', 'mxg', 'nsv', 'nuv', 'osq', 'pcx_pipe', 'pdv', 'pgm_pipe', 'pgmuv_pipe', 'pgx_pipe', 'phm_pipe', 'protocol_pipe', 'pictor_pipe', 'png_pipe', 'ppm_pipe', 'psd_pipe', 'qdraw_pipe', 'qoi_pipe', 'r3d', 'redspark', 'rroq', 'rsd', 'rtsp', 'sdr2', 'ser', 'sga', 'sgi_pipe', 'shn', 'sunrast_pipe', 'svg_pipe', 'tiff_pipe', 'tmv', 'v210', 'v210x', 'vbn_pipe', 'video4linux2', 'v4l2', 'vividas', 'vivo', 'vmd', 'wc3movie', 'webm_dash_manifest', 'webp_pipe', 'wtv', 'xmv', 'xvag', 'xwd_pipe', 'mkv', 'wmv');
// /  --Supported Video Output Formats--
$UserVideoOutputArray = array('3gp', 'mkv', 'avi', 'mp4', 'mpeg', 'wmv', 'mov', 'm4v');
// /  --Supported Stream Formats--
$UserStreamArray = array('m3u8', 'ts');
// /  --Supported Drawing Formats--
$UserDrawingArray = array('dxf', 'vdx', 'fig', 'dia', 'wpg');
// /  --Supported SVG Input Formats--
$UserSVGInputArray = array('svg', 'plain-svg');
// /  --Supported SVG Output Formats--
$UserSVGOutputArray = array('png', 'pdf', 'ps', 'eps', 'emf', 'wmf');
// /  --Supported Model Formats--
$UserModelArray = array('stl', 'ply', 'off', '3ds', 'fbx', 'dae', 'gltf', 'glb', 'obj', '3mf', 'x3d', 'dxf');
// /  --Supported OpenSCAD Formats--
// /   The first entry must be scad, which is the only input format this converter accepts.
// /   Every remaining entry is an export format OpenSCAD can produce from 3D geometry.
// /   DXF & SVG are deliberately absent from this list.
// /   OpenSCAD can only export those from 2D geometry such as square(), circle() or projection().
// /   A model built from cube() cannot produce them & HRConvert2 cannot know which a source is.
// /   Offering them would produce confusing failures for the overwhelming majority of uploads.
// /   PNG is deliberately absent because a useful render would require camera & image size arguments.
$UserSCADArray = array('scad', 'stl', 'off', 'amf', '3mf', 'csg');
// /  --Supported Subtitle Input Formats--
$UserSubtitleInputArray = array('sub', 'sbv', 'srt', 'stream_segment', 'ssegment', 'streamhash', 'sup', 'subtitles', 'ttml', 'uncodedframecrc', 'webvtt', 'wtv', 'oma', 'rso', 'rtp', 'rtsp', 'scc', 'sdl', 'sdl2', 'segment', 'sap', 'jacosub', 'kvag', 'microdvd', 'ffmetadata', 'fifo', 'fifo_test', 'fits', 'framecrc', 'framehash', 'framemd5', 'dash', 'crc', 'dvbsub', 'dvbtxt', 'gsm', 'ass', 'vobsub', 'mpl2', 'mpsub', 'pjs', 'realtext', 'sami', 'stl', 'subviewer', 'subviewer1', 'tedcaptions', 'txd', 'vtt', 'ssa', 'dvb', 'vplayer');
// /  --Supported Subtitle Output Formats--
$UserSubtitleOutputArray = array('vtt', 'ssa', 'ass', 'srt');
// /  --Supported OCR Formats--
$UserPDFWorkArr = array('pdf', 'jpg', 'jpeg', 'png', 'bmp', 'webp', 'gif');
// / ------------------------------
