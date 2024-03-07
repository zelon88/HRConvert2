<?php
// / -----------------------------------------------------------------------------------
// / APPLICATION INFORMATION ...
// / HRConvert2, Copyright on 3/5/2024 by Justin Grimes, www.github.com/zelon88
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
// / v3.3.
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
// / This application requires Debian Linux (w/3rd Party audio license), 
// / Apache 2.4, PHP 8+, LibreOffice, Unoconv, ClamAV, Tesseract, Rar, Unrar, Unzip, 
// / 7zipper, FFMPEG, PdfToText, Dia, PopplerUtils, MeshLab, Mkisofs & ImageMagick.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / ------------------------------
 
// / ---Security Informations---
// / 
// /  --Salts--
// /   Salts for hashing operations.
// /   Change these Salts to something completely random and keep them secret. 
// /   Store your Salts in hardcopy form or an encrypted drive in case of emergency.
$Salts1 = 'something1SoRa21nDoMThatNobody_4Wiljl_evar+guess+i1tgdgdfgfdsfgdasfdas';
$Salts2 = 'gdf4sgdfsg1sdfsomethingSoRa33nDoMThatNobody_Will2_evar_guess+it';
$Salts3 = 'somethingSoRanDoMThatNobo423432dy54534534_Will_evar+guess+it';
$Salts4 = 'somethin1gSoRanDoMThat123:l_will_evar-guess+it';
$Salts5 = 'somethingSoRanDoMThatNobodyr3454r3r33_Will_evar+guess+it';
$Salts6 = 'somethingSoR5anDoMThatNob2odyawryoglukfgy;/,6^&__Will_evar+guess+it';
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
// /   If set to TRUE & --User Virus Scanning-- is set to TRUE infected files detected during virus scans will remain until normal cleanup.
// /   If set to TRUE & --User Virus Scanning-- is set to FALSE any infected file will immediately be deleted upon detection.
// /   If set to TRUE & --User Virus Scanning-- is set to TRUE incoming file uploads will not be scanned for viruses.
// /   If set to TRUE & --User Virus Scanning-- is set to FALSE incoming file uploads will be scanned for viruses.
// /   Regardless of how --User Virus Scanning-- is set, infected files cannot be downloaded, archived, converted, or OCR'd.
// /   Valid options are TRUE or FALSE.
// /   Defalt is FALSE.
$VirusScan = FALSE;
// /  --User Virus Scanning--
// /   Provide users with options to scan their uploaded files for viruses.
// /   Requires ClamAV to be installed on the server.
// /   Set to TRUE to enable users to upload potentially infected files.
// /   Set to FALSE to disable users uploading potentially infected files.
// /   This config entry has a major impact on how regular virus scans are performed.
// /   If set to TRUE & --Virus Scanning-- is set to TRUE infected files detected during virus scans will remain until normal cleanup.
// /   If set to FALSE & --Virus Scanning-- is set to TRUE any infected file will immediately be deleted upon detection.
// /   If set to TRUE & --Virus Scanning-- is set to TRUE incoming file uploads will not be scanned for viruses.
// /   If set to FALSE & --Virus Scanning-- is set to TRUE incoming file uploads will be scanned for viruses.
// /   Regardless of how --User Virus Scanning-- is set, infected files cannot be downloaded, archived, converted, or OCR'd.
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
// / ------------------------------

// / ------------------------------
// / ---Directory Information---
// / 
// /  --Installation Directory--
// /   Install HRConvert2 to the following directory.
// /   Do not include a trailing slash.
// /   Do not use a path with whitespace.
// /   Changing this value is not recommended.
// /   Default is /var/www/html/HRProprietary/HRConvert2.
$InstLoc = '/var/www/html/HRProprietary/HRConvert2';
// /  --Server Root Directory--
// /   This should be pointed at the root of your web server directory.
// /   Do not include a trailing slash.
// /   Do not use a path with whitespace.
// /   Default is /var/www/html.
$ServerRootDir = '/var/www/html';
// /  --Data Storage Directory--
// /   This is where temporary data files are stored.
// /   Do not include a trailing slash.
// /   Do not use a path with whitespace.
// /   Default is /DATA/HRConvert2.
$ConvertLoc = '/DATA/HRConvert2';
// /  --Log Storage Directory--
// /   This is where permanent Log files are stored.
// /   Do not include a trailing slash.
// /   Do not use a path with whitespace.
// /   Default is /var/www/html/HRProprietary/HRConvert2/Logs.
$LogDir = '/var/www/html/HRProprietary/HRConvert2/Logs';
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
// /  --Supported Guis--
// /   The list of GUIs that are supported by this application.
// /   Before adding a supported GUI be sure to add the matching folder full of GUI files to /UI.
// /   Errors will occur if you add an element to this array without also adding a matching GUI folder.
// /   Default is 'Default'.
$SupportedGuis = array('Default');
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
// /   If set to TRUE a user will be able to select different languages via $_GET['gui'].
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
// /   Default is 'en', 'fr', 'es', 'zh', 'hi', 'ar', 'ru', 'uk', 'bn', 'de', 'ko', 'it', 'pt'.
$SupportedLanguages = array('en', 'fr', 'es', 'zh', 'hi', 'ar', 'ru', 'uk', 'bn', 'de', 'ko', 'it', 'pt');
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
// /   Default is 'Document', 'Image', 'Model', 'Drawing', 'Video', 'Audio', 'Archive', 'Stream', 'OCR'.
$SupportedConversionTypes = array('Document', 'Image', 'Model', 'Drawing', 'Video', 'Subtitle', 'Audio', 'Archive', 'Stream', 'OCR');
// /  --File Deletion Age Theshold--
// /   Age in minutes of files to be deleted.
// /   Set to 0 to keep files forever.
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
// /   If this is set to TRUE a full GUI with text will be displayed.
// /   If this is set to FALSE a minimal GUI with only required elements will be displayed.
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
// /   The URL to use for the Terms of Service link at te bottom of the GUI.
// /   Only takes effect if --Show Fine Print-- is set to TRUE.
$TOSURL = 'https://www.honestrepair.net/index.php/terms-of-service/';
// /  --Privacy Policy URL--
// /   The URL to use for the Privacy Policy link at te bottom of the GUI.
// /   Only takes effect if --Show Fine Print-- is set to TRUE.
$PPURL = 'https://www.honestrepair.net/index.php/privacy-policy/';
// / ------------------------------

// / ------------------------------
// / --Supported File Format Information--

// /  --Supported Archive Formats--
$UserArchiveArray = array('zip', 'rar', 'tar', '7z', 'iso');
// /  --Supported Dearchive Formats--
$UserDearchiveArray = array('zip', 'rar', 'tar', 'bz', 'gz', 'bz2', '7z', 'iso', 'vhd', 'vdi', 'tar.bz2', 'tar.gz');
// /  --Supported Document Formats--
$UserDocumentArray = array('txt', 'doc', 'docx', 'rtf', 'odt', 'pdf');
// /  --Supported Spreadsheet Formats--
$UserSpreadsheetArray = array('csv', 'xls', 'xlsx', 'ods');
// /  --Supported Presentation Formats--
$UserPresentationArray = array('pages', 'pptx', 'ppt', 'xps', 'potx', 'potm', 'pot', 'ppa', 'odp');
// /  --Supported Image Formats--
$UserImageArray = array('jpeg', 'jpg', 'jpe', 'png', 'bmp', 'gif', 'webp', 'cin', 'dds', 'dib', 'flif', 'avif', 'gplt', 'sct', 'xcf', 'heic', 'ico');
// /  --Supported Audio Formats--
$UserMediaArray = array('mp3', 'aac', 'oog', 'wma', 'mp2', 'flac', 'm4a', 'm4p');
// /  --Supported Video Formats--
$UserVideoArray = array('3gp', 'mkv', 'avi', 'mp4', 'flv', 'mpeg', 'wmv', 'mov', 'm4v');
// /  --Supported Stream Formats--
$UserStreamArray = array('m3u8');
// /  --Supported Drawing Formats--
$UserDrawingArray = array('svg', 'dxf', 'vdx', 'fig', 'dia', 'wpg', 'png');
// /  --Supported Model Formats--
$UserModelArray = array('3ds', 'obj', 'collada', 'off', 'ply', 'stl', 'gts', 'dxf', 'u3d', 'vrml', 'x3d');
// /  --Supported Subtitle Formats--
$UserSubtitleArray = array('vtt', 'ssa', 'ass', 'srt', 'dvb');
// /  --Supported OCR Formats--
$UserPDFWorkArr = array('pdf', 'jpg', 'jpeg', 'png', 'bmp', 'webp', 'gif');
// / ------------------------------