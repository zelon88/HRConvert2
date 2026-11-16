<?php
// / -----------------------------------------------------------------------------------
// / THIS IS HRCONVERT2'S HALF OF THE CONFIGURATION TEMPLATE, & everything below is a real
// / config.php at default values. Not a description of one.
// / It holds the header & the guard a config.php opens with, $ConfigVersion & every setting only
// / HRConvert2 reads. Every setting the kernel or the Engine reads is the Engine's, in its half,
// / Resources/Engine/config-template.php, & config.php is written from both halves.
// / The kernel reads the names in both to know which settings config.php must hold & which become
// / globals, so no list of them is kept anywhere else. See configurationTemplatePaths() in
// / Resources/Kernel/kernel.php.
// / A missing config.php is written FROM them, with the Engine's settings first in each section the
// / two share. A repair adds whatever either holds that a live configuration lacks.
// / AN OPERATOR VALUE IS NEVER REPLACED by a default here.
// / Adding a setting to this application means adding it here & to applicationConfigModel() in
// / convertCore.php, & raising $ConfigVersion below & $RequiredConfigVersion in convertCore.php.
// / Resources/config.php is what --config --repair writes from both halves, & the build checks it still is.
// / ABOUT_COMPONENTS.txt says why each one is needed.
// / Everything below the COPY FROM HERE marker is copied verbatim, including the header &
// / the guard, because a generated configuration needs both. This note is not copied,
// / because a generated configuration is not a template & should not claim to be.
// / -----------------------------------------------------------------------------------

// / Copy from here down.
// / This file is generated from Resources/Contract/config-template.php & Resources/Engine/config-template.php. Edit the values below, never the structure.
// / A repair adds whatever either template holds & keeps every value set here. A structural edit is lost when the file is generated again.
// / -----------------------------------------------------------------------------------
// / Copyright information ...
// / HRConvert2, Copyright on 9/22/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / Application information ...
// / This application is designed to provide a web-interface for converting file formats
// / on a server for users of any web browser without authentication. 
// /
// / File information ...
// / v4.1.7.
// / This file contains the configuration information for HRConvert2.
// / Fill out this file completely & accurately before running the application.
// / Serious filesystem damage could occur from incorrect directory settings.
// / Be careful to preserve all syntax & formatting.
// /
// / Hardware requirements ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / Dependency requirements ...
// / The authoritative list of dependencies is Resources/Engine/depends.php & Resources/Contract/depends.php, the Engine's half & the application's, & nothing else.
// / Run  php convertCore.php -v  to see what this installation has.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Refuse direct execution. This file is a component & has no standalone context.
// / This halt cannot use quickDie. Reaching this line means convertCore.php was never
// / loaded, so quickDie is not defined & calling it would replace a clear refusal with an
// / undefined function error.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-2: This file cannot process your request! Please submit your file to convertCore.php instead!'.PHP_EOL);
// / -----------------------------------------------------------------------------------

// / ------------------------------
// /  --Config Version--
// /   The version of HRConvert2 in which this config file last gained or lost a setting.
// /   The core refuses to run against a config file that is missing settings it requires.
// /   Do not change this value by hand. An update & --config --repair keep it current.
$ConfigVersion = 'v4.2.0';
// / ------------------------------

// / ------------------------------
// / ---Security Informations---
// /
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
// /   Keep this at a quarter of the memory limit or less. ScanCore holds about three chunks
// /   in memory at once, so a larger chunk exhausts the memory limit on any file bigger than
// /   one chunk. A larger value is reduced to a quarter at scan time & a warning is logged.
// /   Default is 67108864.
$ScanCoreChunkSize = 67108864;
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
// /  --Stream Inspection Layers--
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
// /  --Stream Inspection Files Per Layer--
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
// /  --Default Stream Inspection Forfeit Action--
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
// /   Default is 'DENY'.
$DefaultStreamInspectionForfeitAction = 'DENY';
// /  --Allow SCAD Include Resolution--
// /   OpenSCAD sources reference other sources with include <file> & use <file>.
// /   A multi file assembly cannot render at all unless those references resolve to something.
// /   If set to FALSE, every reference is removed & multi file assemblies will not render.
// /   If set to TRUE, an include or use may resolve to another source the SAME user uploaded.
// /   Resolution matches on filename alone & ignores whatever directory the reference carried.
// /   A reference that matches no uploaded file is removed regardless of this setting.
// /   This setting does NOT control whether OpenSCAD may read arbitrary files.
// /   HRConvert2 removes every file reading primitive from every uploaded source unconditionally.
// /   import(), surface(), the deprecated import_stl() family & every dxf file= form are always removed.
// /   Those removals happen whether this setting is TRUE or FALSE & cannot be turned off.
// /   A removal overwrites the call with whitespace of the same length & never with a comment.
// /   Writing a comment marker was the subject of GHSA-mqvr-h4q5-chg9. Do not reintroduce one.
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
// /  --Use PyMeshLab Python Bindings--
// /   MeshLab replaced its legacy server application with a Python library package.
// /   Enabling this toggle forces the core to pass geometry repairs to a local Python script.
// /   This bypasses the need for an active X11 virtual frame display buffer on your host.
// /   When enabled, it completely bypasses the legacy binary year-month version validation check.
// /   When disabled, standard Meshlab is used instead.
// /   Requires 'python3' and the 'pymeshlab' package to be manually installed via pip on the server.
// /   True runs the modern Python script route, False defaults to the standard meshlabserver binary.
// /   Default is TRUE.
$UsePyMeshLab = TRUE;
// /  --URL Downloads Per Session--
// /   How many files one visitor may fetch before this server stops accepting more.
// /   Without this, one visitor can queue as many fetches as they can type, & each one
// /   costs bandwidth & disk whether or not anything is ever converted.
// /   The count is per session, so it resets when a session does.
// /   0 means no limit. Do not use 0 on anything a stranger can reach.
// /   Default is 10.
$URLDownloadsPerSession = 10;
// /  --Allow User URL Download--
// /   Whether a user may type a URL & have this server fetch it into their session.
// /   THIS SERVER HAS NO AUTHENTICATION. Anyone who can reach the page can use this, & a
// /   server that fetches any address a stranger names is an open proxy. It can be used to
// /   scan, to launder traffic, or to make this host's address appear in somebody else's
// /   logs. That is why it is FALSE by default & why an appliance on a public network
// /   should probably leave it that way.
// /   What it is NOT is an SSRF hole. Every fetch goes through the same address filter a URL
// /   inside a playlist receives, so a private, reserved or loopback address is refused, the
// /   address is pinned with curl --resolve, no redirect is followed, & the fetch runs in a
// /   namespace with a network & no resolver.
// /   Turn it on for a trusted network where users need to convert something they can only
// /   reach by link. Leave it off everywhere else.
// /   Default is FALSE.
$AllowUserURLDownload = FALSE;
// /  --Maximum Extracted Size--
// /   The most one archive may hold once it is extracted, in megabytes.
// /   An archive is small & what it holds may not be. A few kilobytes can hold gigabytes of zeros,
// /   so an archive extracted without a ceiling can fill the disk this server writes to.
// /   An archive is listed before it is extracted, & refused when it says it holds more than this.
// /   It is held to this while it is extracted too, & stopped once it passes it, because an archive
// /   may say anything about itself. A gzip names its size in four bytes & a bzip2 not at all.
// /   It applies to every extraction, by a file's Extract button, by a workflow's Extract step &
// /   by an archive converted into another. An extraction still running after an hour is stopped.
// /   It holds a PDF split into its pages too, & a split whose pages hold more keeps none of them.
// /   0 means no ceiling. Do not use 0 on anything a stranger can reach.
// /   Valid options are integers 0 and larger.
// /   Default is 1024, which is one gigabyte.
$MaxExtractedSize = 1024;
// /  --Maximum Extracted Files--
// /   The most files & folders one archive may hold once it is extracted, counted together.
// /   A folder counts whether or not the archive lists it, because extracting a file makes every
// /   folder it sits in.
// /   An archive holding more is refused before it is extracted, & stopped if it makes more while
// /   it is extracted. It applies wherever --Maximum Extracted Size-- does.
// /   A PDF with more pages than this is not split.
// /   0 means no limit. Do not use 0 on anything a stranger can reach.
// /   Valid options are integers 0 and larger.
// /   Default is 1000.
$MaxExtractedFiles = 1000;

// / ------------------------------

// / ------------------------------
// / ---General Information---
// / 
// /  --Supported GUIs--
// /   The list of GUIs that are supported by this application.
// /   Before adding a supported GUI be sure to add the matching folder full of GUI files to /UI.
// /   Errors will occur if you add an element to this array without also adding a matching GUI folder.
// /   Default is 'Default', 'Wide'.
// / Wide was removed at v3.9.1. It was Original with one number changed, & that number is
// /   now --GUI Maximum Width-- above. Set that to 2000 for the layout Wide used to give.
$SupportedGuis = array('Default', 'Original');
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
// /   Active file links will break after the --File Deletion Age Threshold-- is met.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$AllowUserShare = TRUE;
// /  --User File Identification--
// /   Provide users with the option to identify the files they upload or convert.
// /   Identify reports a file's size, when it changed, its SHA-256, SHA-1 & MD5 hashes, what it holds & whether its name agrees.
// /   An image, an audio or video file, a PDF & an archive are also read by the tool for that format, each inside the sandbox.
// /   A user may also ask for the same details as a plain text report, which is saved beside their files.
// /   If set to TRUE the user will be provided with buttons to identify one file or every file.
// /   If set to FALSE the user will not be provided with the buttons, & a request to identify a file is refused.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$AllowUserIdentify = TRUE;
// /  --Allowed Conversion Types--
// /   The list of supported conversion types.
// /   Only conversion types contained in this list will be processed.
// /   If a conversion type is disabled, options for processing that conversion will not be displayed by the UI.
// /   Default is 'Document', 'Image', 'Model', 'Scad', 'Drawing', 'SVG', 'Video', 'Subtitle', 'Audio', 'Archive', 'Stream', 'OCR'.
$SupportedConversionTypes = array('Document', 'Image', 'Model', 'Scad', 'Drawing', 'SVG', 'Video', 'Subtitle', 'Audio', 'Archive', 'Stream', 'OCR', 'Ebook');
// /  --Allow Creation Of Bootable ISO Images--
// /   Allow or disallow users to create bootable ISO images for multiple target systems.
// /   The list of supported bootable image formats is contained in the --Supported Bootable ISO Output Formats-- config.php section.
// /   Creating bootable .iso images is only possible through the Archive conversion process.
// /   This setting requires that Archive conversions be a member of --Supported Conversion Types-- in config.php.
// /   This setting requires that the iso format is a member of --Supported Archive Formats-- in config.php.
// /   If this is set to TRUE, users will be allowed to create bootable .iso files that match the list of supported formats.
// /   If this is set to FALSE, users will be NOT allowed to create bootable .iso files of any kind.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$AllowBootableIsoImage = TRUE;
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
// /  --Logo URL--
// /   Where the logo at the top of the page sends somebody who clicks it.
// /   'preserve-session' is the default & is not an address. It means come back HERE,
// /   carrying the session, the language, the colour & the interface, so a click on the
// /   logo is a way back to a clean page rather than a way out of the application.
// /   That matters because this application has no navigation. A logo that leaves takes
// /   a user's session with it & their files are gone.
// /   Any http or https address is used as written. Use one to send visitors to your own
// /   site, & accept that they lose their session by going there.
// /   Anything else is refused & treated as 'preserve-session'.
// /   Default is 'preserve-session'.
$LogoURL = 'preserve-session';
// /  --RAR Archive Method--
// /   Set the software package to use for creating .rar archives.
// /   This setting allows you to specify which software to use when creating .rar archives.
// /   Currently only RAR is supported.
// /   Valid options are 'rar'.
// /   Default is rar.
$RARArchiveMethod = 'rar';
// /  --GUI Maximum Width--
// /   The widest the interface is allowed to become, in pixels, on a large display.
// /   This is a CEILING & not a fixed width. The interface is fluid below it & takes the
// /   width of the device it is on, so a phone gets the whole screen & a wide monitor gets
// /   this number rather than a line of text running the full two metres of glass.
// /   1400 suits most desktops. 1000 is the classic narrow layout this application shipped
// /   with for years. 2000 is what the separate Wide interface used to be, & is why that
// /   interface no longer needs to exist.
// /   Anything below 600 or above 3840 is ignored & the default is used, because a value
// /   outside that range is a typo rather than a preference.
// /   Default is 1400.
$GuiMaxWidth = 1400;
// / ------------------------------

// / ------------------------------
// / ---Supported File Format Information---
// /
// /  --Supported Archive Formats--.
$UserArchiveArray = array('zip', 'rar', 'tar', '7z', 'iso');
// /  --Supported Bootable ISO Output Formats--.
$UserBootableIsoArray = array('iso_mbr-boot', 'iso_gpt-boot', 'iso_gpt-boot-x86', 'iso_gpt-boot-x86-64', 'iso_gpt-boot-arm32', 'iso_gpt-boot-arm64');
// /  --Supported Dearchive Formats--.
$UserDearchiveArray = array('zip', 'rar', 'tar', 'bz', 'gz', 'bz2', '7z', 'iso', 'vhd', 'vdi', 'tar.bz2', 'tar.gz', 'cbr', 'cbz');
// /  --Supported Document Formats--.
$UserDocumentArray = array('txt', 'doc', 'docx', 'rtf', 'odt', 'pdf');
// /  --Supported Spreadsheet Formats--.
$UserSpreadsheetArray = array('csv', 'xls', 'xlsx', 'ods');
// /  --Supported XPS Input Formats--.
$UserXPSInputArray = array('xps', 'oxps');
// /  --Supported XPS Output Formats--.
$UserXPSOutputArray = array('pdf');
// /  --Supported Presentation Input Formats--.
$UserPresentationInputArray = array('pptx', 'ppt', 'potx', 'potm', 'pot', 'ppa', 'odp');
// /  --Supported Presentation Output Formats--.
$UserPresentationOutputArray = array('pptx', 'ppt', 'potx', 'potm', 'pot', 'ppa', 'odp', 'pdf');
// /  --Supported Image Formats--.
$UserImageArray = array('jpeg', 'jpg', 'jpe', 'png', 'bmp', 'gif', 'webp', 'cin', 'dds', 'dib', 'flif', 'avif', 'gplt', 'sct', 'xcf', 'heic', 'ico', 'tiff', 'tif', 'heif', 'jp2', 'j2k', 'jxl');
// /   Ten formats were offered as uploads the pipeline never claimed & eleven as outputs it
// /   cannot write. Each one was accepted by the interface & refused by the converter.
// /  --Supported Image Input Formats--
// /   What a user may upload. Every entry is a format the Image pipeline declares
// /   as an input, so nothing here is offered & then refused.
$UserImageInputArray = array('jpeg', 'jpg', 'png', 'bmp', 'gif', 'webp', 'dds', 'avif', 'heic', 'ico', 'tiff', 'tif', 'jp2');
// /  --Supported Image Output Formats--
// /   What a user may ask for. Every entry is a format the pipeline declares as an
// /   output.
$UserImageOutputArray = array('jpeg', 'jpg', 'png', 'bmp', 'gif', 'webp', 'dds', 'avif', 'ico', 'tiff', 'tif', 'jp2');
// /  --Supported Audio Input Formats--.
$UserMediaInputArray = array('sox', 'spdif', 'spx', 'tta', 'u16be', 'u16le', 'u24be', 'u24le', 'u32be', 'u32le', 'u8', 'voc', 'wav', 'wv', 'wsaud', 'mulaw', 'mxf', 'mxf_d10', 'mxf_opatom', 'oga', 'ogg', 'opus', 'oss', 'psp', 'rawvideo', 's16be', 's16le', 's24be', 's24le', 's32be', 's32le', 's8', 'sbc', 'ilbc', 'ircam', 'latm', 'lrc', 'mp2', 'mp3', 'mlp', 'flac', 'g722', 'g723_1', 'g726', 'g726le', 'gsm', 'caf', 'daud', 'dts', 'eac3', 'f32be', 'f32le', 'f64be', 'f64le', 'ac3', 'ac4', 'adts', 'aiff', 'alaw', 'amr', 'aptx', 'aptx_hd', 'argo_asf', 'argo_cvg', 'ast', 'au', 'a64', 'aa', 'aac', 'aax', 'acm', 'act', 'adp', 'adx', 'aea', 'afc', 'aix', 'alp', 'amrnb', 'amrwb', 'apac', 'apc', 'ape', 'apm', 'argo_asf', 'binka', 'bit', 'boa', 'bonk', 'brstm', 'dfpwm', 'dsf', 'dss', 'epaf', 'fsb', 'fwse', 'g729', 'hca', 'idf', 'kux', 'kvag', 'laf', 'lavfi', 'loas', 'luodat', 'lvf', 'lxf', 'mca', 'mcc', 'megsts', 'mlv', 'mmf', 'mods', 'moflex', 'mpc8', 'msf', 'msnwctcp', 'mtaf', 'musx', 'nc', 'nistsphere', 'nsp', 'paf', 'pam_pipe', 'pbm_pipe', 'pfm_pipe', 'pp_bnk', 'psxstr', 'pva', 'pvf', 'qcp', 'rka', 'rl2', 'rpl', 'rso', 's337m', 'sap', 'sbg', 'scd', 'sdns', 'sdp', 'sds', 'sdx', 'siff', 'simbiosis_imx', 'sln', 'smk', 'smush', 'sol', 'svag', 'svs', 'tak', 'thp', 'tierexseq', 'tty', 'ty', 'usm', 'vag', 'vidc', 'vpk', 'vqf', 'w64', 'wady', 'wavarc', 'wsd', 'wsvqa', 'wve', 'xa', 'xbin', 'xbm_pipe', 'xmd', 'xpm_pipe', 'xwma', 'yop', 'wma', 'm4a');
// /  --Supported Audio Output Formats--.
$UserMediaOutputArray = array('mp3', 'aac', 'ogg', 'wma', 'mp2', 'flac', 'm4a');
// /  --Supported Video Input Formats--.
$UserVideoInputArray = array('smoothstreaming', 'svcd', 'swf', 'truehd', 'vc1', 'vc1test', 'vcd', 'vob', 'vvc', 'webm', 'yuv4mpegpipe', 'mpjpeg', 'mxf', 'mxf_d10', 'mxf_opatom', 'nut', 'obu', 'ogv', 'psp', 'rawvideo', 'rm', 'roq', 'rtp_mpegts', 'smjpeg', 'hevc', 'hls', 'image2', 'image2pipe', 'ipod', 'ismv', 'm4v', 'matroska', 'mjpeg', 'mkvtimestamp_v2', 'mov', 'mp4', 'mpeg', 'mpeg1video', 'mpeg2video', 'mpegts', 'mpegtsraw', 'mpegvideo', 'fbdev', 'film_cpk', 'filmstrip', 'gxf', 'h261', 'h263', 'h264', 'hds', 'avs2', 'avs3', 'cavsvideo', 'cavs', 'dirac', 'dnxhd', 'dv', 'dvd', 'evc', '3g2', '3gp', 'apng', 'argo_asf', 'argo_cvg', 'asf', 'asf_stream', 'avi', 'avif', 'avm2', '3dostr', '4xm', 'adf', 'ads', 'alias_pix', 'anm', 'argo_brp', 'asf_o', 'av1', 'avs', 'bethsoftvid', 'bfi', 'bink', 'bmv', 'brender_pix', 'brender', 'cdg', 'cdxl', 'cine', 'concat', 'cri', 'dcstr', 'derf', 'dfa', 'dhav', 'dsicin', 'dtshd', 'dxa', 'ea', 'exr', 'fits', 'flic', 'frm', 'gdv', 'genh', 'gif', 'idcin', 'iff', 'ifv', 'ingenient', 'ipmovie', 'iss', 'iv8', 'ivf', 'ivr', 'j2k', 'jp2', 'jv', 'live_flv', 'lmlm4', 'mtv', 'mv', 'mvi', 'mxg', 'nsv', 'nuv', 'osq', 'pcx_pipe', 'pdv', 'pgm_pipe', 'pgmuv_pipe', 'pgx_pipe', 'phm_pipe', 'protocol_pipe', 'pictor_pipe', 'png_pipe', 'ppm_pipe', 'psd_pipe', 'qdraw_pipe', 'qoi_pipe', 'r3d', 'redspark', 'rroq', 'rsd', 'rtsp', 'sdr2', 'ser', 'sga', 'sgi_pipe', 'shn', 'sunrast_pipe', 'svg_pipe', 'tiff_pipe', 'tmv', 'v210', 'v210x', 'vbn_pipe', 'video4linux2', 'v4l2', 'vividas', 'vivo', 'vmd', 'wc3movie', 'webm_dash_manifest', 'webp_pipe', 'wtv', 'xmv', 'xvag', 'xwd_pipe', 'mkv', 'wmv');
// /  --Supported Video Output Formats--.
$UserVideoOutputArray = array('3gp', 'mkv', 'avi', 'mp4', 'mpeg', 'wmv', 'mov', 'm4v');
// /  --Supported Stream Formats--.
$UserStreamArray = array('m3u8', 'ts');
// /   The old array offered ts as an upload & m3u8 as an output, which is each direction
// /   backwards. A stream is fetched as a playlist & written as a media file.
// /  --Supported Drawing Formats--.
$UserDrawingArray = array('dxf', 'vdx', 'fig', 'dia', 'wpg');
// /   Every format offered is both read & written, so the two lists are identical today.
// /   They are separate because the pipeline writes eighteen formats & only five are
// /   offered, & adding an output no longer means offering it as an upload.
// /  --Supported Drawing Input Formats--
// /   What a user may upload. Every entry is a format the Drawing pipeline declares
// /   as an input, so nothing here is offered & then refused.
$UserDrawingInputArray = array('dxf', 'vdx', 'fig', 'dia', 'wpg');
// /  --Supported Drawing Output Formats--
// /   What a user may ask for. Every entry is a format the pipeline declares as an
// /   output.
$UserDrawingOutputArray = array('dxf', 'vdx', 'fig', 'dia', 'wpg');
// /  --Supported SVG Input Formats--.
$UserSVGInputArray = array('svg', 'plain-svg');
// /  --Supported SVG Output Formats--.
$UserSVGOutputArray = array('png', 'pdf', 'ps', 'eps', 'emf', 'wmf');
// /  --Supported Model Formats--
// /   This array was offered as both the input list & the output list, which the audio,
// /   video & vector converters each split in two. The two below carry those directions.
// /   All three are required. A config missing any of them is refused at startup & no
// /   command adds a missing setting, so take the shipped file or add them by hand.
// /   This one remains because other code reads the combined list.
$UserModelArray = array('stl', 'ply', 'off', '3ds', 'fbx', 'dae', 'gltf', 'glb', 'obj', '3mf', 'x3d', 'dxf', 'x', 'ctm');
// /  --Supported Model Input Formats--
// /   What a user may upload for a model conversion.
// /   Every entry must be a format the Model pipeline declares as an input, or the upload is
// /   offered & then refused by a pipeline that never claimed it.
$UserModelInputArray = array('stl', 'ply', 'off', '3ds', 'fbx', 'dae', 'gltf', 'glb', 'obj', '3mf', 'x3d', 'dxf', 'x', 'ctm');
// /  --Supported Model Output Formats--
// /   What a user may ask for. This list is longer than the input list & that is the whole
// /   reason the two are separate.
// /   wrl is written by MeshLab & read by it poorly, so it is an output & never an input.
// /   u3d is deliberately absent. MeshLab writes it only through a separate converter this
// /   bundle does not carry, & a format that works most of the time is worse than one that
// /   was never offered.
$UserModelOutputArray = array('stl', 'ply', 'off', '3ds', 'fbx', 'dae', 'gltf', 'glb', 'obj', '3mf', 'x3d', 'dxf', 'x', 'ctm', 'wrl', 'assbin', 'json');
// /  --Supported OpenSCAD Formats--
// /   The first entry must be scad, which is the only input format this converter accepts.
// /   Every remaining entry is an export format OpenSCAD can produce from 3D geometry.
// /   DXF & SVG are deliberately absent from this list.
// /   OpenSCAD can only export those from 2D geometry such as square(), circle() or projection().
// /   A model built from cube() cannot produce them & HRConvert2 cannot know which a source is.
// /   Offering them would produce confusing failures for the overwhelming majority of uploads.
// /   PNG is deliberately absent because a useful render would require camera & image size arguments.
$UserSCADArray = array('scad', 'stl', 'off', 'amf', '3mf', 'csg');
// /   The old array carried scad first & relied on that position to mean input. Everything
// /   after it was an output. A positional convention is not a declaration & nothing
// /   enforced it.
// /  --Supported SCAD Input Formats--
// /   What a user may upload. Every entry is a format the Scad pipeline declares
// /   as an input, so nothing here is offered & then refused.
$UserSCADInputArray = array('scad');
// /  --Supported SCAD Output Formats--
// /   What a user may ask for. Every entry is a format the pipeline declares as an
// /   output.
$UserSCADOutputArray = array('stl', 'off', 'amf', '3mf', 'csg');
// /  --Supported Subtitle Input Formats--.
$UserSubtitleInputArray = array('sub', 'sbv', 'srt', 'stream_segment', 'ssegment', 'streamhash', 'sup', 'subtitles', 'ttml', 'uncodedframecrc', 'webvtt', 'wtv', 'oma', 'rso', 'rtp', 'rtsp', 'scc', 'sdl', 'sdl2', 'segment', 'sap', 'jacosub', 'kvag', 'microdvd', 'ffmetadata', 'fifo', 'fifo_test', 'fits', 'framecrc', 'framehash', 'framemd5', 'dash', 'crc', 'dvbsub', 'dvbtxt', 'gsm', 'ass', 'vobsub', 'mpl2', 'mpsub', 'pjs', 'realtext', 'sami', 'stl', 'subviewer', 'subviewer1', 'tedcaptions', 'txd', 'vtt', 'ssa', 'dvb', 'vplayer');
// /  --Supported Subtitle Output Formats--.
$UserSubtitleOutputArray = array('vtt', 'ssa', 'ass', 'srt');
// /  --Supported OCR Formats--.
$UserPDFWorkArr = array('pdf', 'jpg', 'jpeg', 'png', 'bmp', 'webp', 'gif');
// /  --Supported Ebook Input Formats--
// /   The e-book formats a user may convert FROM.
// /   Several of these overlap with the document & archive formats on purpose. The routing
// /   loop tries the document & archive families FIRST, so an ordinary docx to pdf stays
// /   with LibreOffice & a cbz to cbr stays with 7-Zip. Only a pair that no earlier family
// /   can satisfy, such as docx to epub, reaches this pipeline.
// /   PDF input is supported but is the weakest conversion Calibre performs. A PDF has no
// /   reflowable structure to recover, so the result is frequently poor. It is offered
// /   because it is occasionally the only option, not because it works well.
$UserEbookInputArray = array('epub', 'mobi', 'azw', 'azw3', 'azw4', 'fb2', 'fbz', 'lit', 'lrf', 'pdb', 'pml', 'rb', 'snb', 'tcr', 'txt', 'txtz', 'rtf', 'odt', 'docx', 'pdf', 'chm', 'cbz', 'cbr', 'cbc', 'prc', 'opf', 'recipe');
// /  --Supported Ebook Output Formats--
// /   The e-book formats a user may convert TO.
// /   Calibre writes PDF through Qt WebEngine, which needs more memory than any other output, so
// /   PDF is the first to fail inside a small --Maximum Per Conversion Resources-- ceiling.
// /   Calibre writes PML only zipped, as pmlz, & writes OEB as a folder, which cannot be handed
// /   to a browser, so neither pml nor oeb is offered.
$UserEbookOutputArray = array('epub', 'mobi', 'azw3', 'fb2', 'lit', 'lrf', 'pdb', 'pmlz', 'rb', 'snb', 'tcr', 'txt', 'txtz', 'rtf', 'docx', 'pdf');
// / ------------------------------
