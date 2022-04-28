<?php
// / -----------------------------------------------------------------------------------
// / APPLICATION INFORMATION ...
// / HRConvert2, Copyright on 4/11/2022 by Justin Grimes, www.github.com/zelon88
// /
// / LICENSE INFORMATION ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / APPLICATION INFORMATION ...
// / This application is designed to provide a web-interface for converting file formats
// / on a server for users of any web browser without authentication. 
// /
// / FILE INFORMATION
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
// / Apache 2.4, PHP 7+, LibreOffice, Unoconv, ClamAV, Tesseract, Rar, Unrar, Unzip, 
// / 7zipper, FFMPEG, PdfToText, Dia, PopplerUtils, MeshLab & ImageMagick.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / ------------------------------  
// / ---Security Informations---
// / 
// /  --Salts--
// /   Change these Salts to something completely random and keep it a secret. 
// /   Store your $Salts in hardcopy form or an encrypted drive in case of emergency.
// /   IF YOU LOSE YOUR SALTS YOU WILL BE UNABLE TO DECODE USER ID'S AFTER AN EMEREGENCY.
$Salts1 = 'somethingSoRanDoMThatNobody_4Will_evar+guess+itgdgdfgfdsfgdasfdas';
$Salts2 = 'gdfsgdfsgsdfsomethingSoRa33nDoMThatNobody_Will_evar+guess+it';
$Salts3 = 'somethingSoRanDoMThatNobo423432dy54534534534_Will_evar+guess+it';
$Salts4 = 'somethingSoRanDoMThat231;l_Will_evar+guess+it';
$Salts5 = 'somethingSoRanDoMThatNobodyr3454r3r33_Will_evar+guess+it';
$Salts6 = 'somethingSoRanDoMThatNob2odyawryoglukfgy;/.5^&#&__Will_evar+guess+it';
// /  --Server URL--
// /   Externally or internally accesible domain or IP.
// /   Do not include a trailing slash.
$URL = 'localhost';
// /  --Virus Scanning--
// /   Scan for viruses before performing file operations.
// /   Requires ClamAV to be installed on the server.
// /   Set to TRUE to enable virus scanning with ClamAV during file operations.
// /   Set to FALSE to disable virus scanning during file operations.
// /   If an infected file is detected the user will not be allowed to download, archive, convert, or OCR the infected file.
// /   The --User Virus Scanning-- config entry has a major impact on how regular virus scans are performed.
// /   If set to TRUE & --User Virus Scanning-- is set to TRUE infected files detected during virus scans will remain until normal cleanup.
// /   If set to TRUE & --User Virus Scanning-- is set to FALSE any infected file will immediately be deleted upon detection.
// /   If set to TRUE & --User Virus Scanning-- is set to TRUE incoming file uploads will not be scanned for viruses.
// /   If set to TRUE & --User Virus Scanning-- is set to FALSE incoming file uploads will be scanned for viruses.
// /   Regardless of how --User Virus Scanning-- is set, infected files cananot be downloaded, archived, converted, or OCR'd.
$VirusScan = FALSE;
// /  --User Virus Scanning--
// /   Give users the options to scan their uploaded files for viruses.
// /   Requires ClamAV to be installed on the server.
// /   Will disable virus scanning during uploads.
// /   Will allow infected files detected during virus scanning operations to remain.
// /   Set to TRUE to enable users to upload potentially infected files.
// /   Set to FALSE to disable users uploading potentially infected files.
// /   This config entry has a major impact on how regular virus scans are performed.
// /   If set to TRUE & --Virus Scanning-- is set to TRUE infected files detected during virus scans will remain until normal cleanup.
// /   If set to FALSE & --Virus Scanning-- is set to TRUE any infected file will immediately be deleted upon detection.
// /   If set to TRUE & --Virus Scanning-- is set to TRUE incoming file uploads will not be scanned for viruses.
// /   If set to FALSE & --Virus Scanning-- is set to TRUE incoming file uploads will be scanned for viruses.
// /   Regardless of how --User Virus Scanning-- is set, infected files cananot be downloaded, archived, converted, or OCR'd.
$AllowUserVirusScan = TRUE;
// /  --User Virus Scanning ScanCore Memory Limit--
// /   The number of bytes of memory ScanCore is allowed to allocate to large fules during User Virus Scans.
// /   Files larger than this limit will be broken into chunks controlled by the --User Virus Scanning ScanCore Chunk Size-- config entry.
$ScanCoreMemoryLimit = 268435456;
// /  --User Virus Scanning ScanCore Chunk Size--
// /   In order to scan files that are larger than the memory limit, large files will be broken into chunks.
// /   The number of bytes to break large files into in order to fit them into memory.
$ScanCoreChunkSize = 134217728;
// /  --User Virus Scanning ScanCore Debug Mode--
// /   Enable an absolutely insane amount of verbosity from ScanCore during file scan operations.
// /   If set to TRUE these events will be included in the report that is submitted to the user.
// /   If set to FALSE a normal amount of logging will be submitted to the user. Enough to get the job done.
// /   If you scanned an entire 500GB hard drive with this set to TRUE ScanCore would generate 10's of GB worth of logs.
// /   This setting will have an impact on ScanCore scanning performance.
// /   Seriously, it's a lot of logs.
$ScanCoreDebug = FALSE;
// /  --User Virus Scanning ScanCore Enhanced Verbosity--
// /   Enable an absolutely insane amount of console output from ScanCore during file scan operations.
// /   If set to TRUE these events will be included in the log file that is stored on the server.
// /   If set to FALSE a normal amount of logging will be stored on the server. Enough to get the job done.
// /   If you scanned an entire 500GB hard drive with this set to TRUE ScanCore would generate 10's of GB worth of logs.
// /   This setting will have an impact on ScanCore scanning performance.
// /   Seriously, it's a lot of logs.
$ScanCoreVerbose = TRUE;
// / ------------------------------

// / ------------------------------ 
// / ---Directory Information---
// / 
// /  --Installation Directory--
// /   Install HRConvert2 to the following directory.
// /   Do not include a trailing slash.
// /   Changing this value is not recommended. 
$InstLoc = '/var/www/html/HRProprietary/HRConvert2';
// /  --Server Root Directory--
// /   This should be pointed at the root of your web server directory.
// /   Do not include a trailing slash.
$ServerRootDir = '/var/www/html';
// /  --Data Storage Directory--
// /   This is where temporary data files are stored. 
// /   Do not include a trailing slash.
$ConvertLoc = '/home/justin/Documents/Projects/DATA/ConvertDATA';
// /  --Log Storage Directory--
// /   This is where permanent Log files are stored. 
// /   Do not include a trailing slash.
$LogDir = '/var/www/html/HRProprietary/HRConvert2/Logs';
// / ------------------------------ 

// / ------------------------------ 
// / ---General Information---
// / 
// /  --Application Name String--
// /   The default name to display for this application.
// /   You can change this to make it fit with other services your organization provides.
$ApplicationName = 'HRConvert2';
// /  --Application Title String--
// /   The default title to display in taskbars & window managers.
// /   You can change this to make it fit with other services your organization provides.
$ApplicationTitle = 'Convert Anything!';
// /  --Supported Languages--
// /   The list of languages that are supported by this application.
// /   Before adding a supported language be sure to add the matching folder full of GUI files to /Languages.
// /   Errors will occur if you add an element to this array without also adding a matching Language folder.
$SupportedLanguages = array('en', 'fr', 'es', 'zh', 'hi', 'ar', 'ru', 'uk', 'bn', 'de', 'ko', 'it', 'pt');
// /  --Default Language--
// /   The default language for GUI elements.
// /   Uses ISO 639-1 language standard.
// /   ISO 639-1 reference is available here: https://www.andiamo.co.uk/resources/iso-language-codes/
// /   If the specified language is not available 'en' will be used instead.
// /   See README.md for the latest language support information.
$DefaultLanguage = 'en';
// /  --Allow User Selectable Language--
// /   Enable or disable dynamic language selection via the $_GET['language'] variable.
// /   If set to TRUE a user will be able to select different languages via $_GET['language'].
// /   If set to FALSE the $DefaultLanguage will always be used.
// /   To submit a $_GET request append ?language=<CODE> to the URL & repalce <CODE> with a 2 digit ISO 639-1 language code.
// /   If a user attempts a language that is not available --Default Language-- will be used instead.
$AllowUserSelectableLanguage = TRUE;
// /  --User Shareable File Links--
// /   Allow users to generate shareable URLs for the files they upload or convert.
// /   If set to TRUE the user will be provided with buttons to create URLs to files that can be copied & pasted elsewhere.
// /   If set to FALSE the user will not be provided with the buttons to create URLs to files.
// /   Regardless of what this is set to, URLs still need to be created during normal download routines to enable downloads.
// /   A savvy user could still craft a shareable download link by requesting & cancelling a download after receiving the URL.
// /   This does not change the behavior of normal file cleanup. 
// /   Files with active links will still be removed after the --File Deletion Age Threshold-- is met.
// /   Active file links will break after the --File Deletion Age Theshold-- is met.
$AllowUserShare = TRUE;
// /  --File Deletion Age Theshold--
// /   Age in minutes of files to be deleted.
// /   Set to 0 to keep files indefinately.
// /   Default is 30.
$DeleteThreshold = 30;
// /  --Enhanced Logging Verbosity--
// /   Enable verbose logging.
// /   If set to TRUE all core events will be logged.
// /   If set to FALSE only certain errors & certain major core events will be logged.
$Verbose = TRUE;
// /  --Maximum Log File Size--
// /   Set the number of bytes to store in each logfile before splitting to a new one.
$MaxLogSize = 1048576;
// /  --UI Element Font--
// /   Set the default font to use throughout HRConvert2 GUI elements.
// /   Whatever font you choose must be installed on the client's machine.
// /   If the font is not available the client default will be used.
$Font = 'Arial';
// /  --Button Color--
// /   Set the default color scheme to use for buttons.
// /   Valid options are 'RED', 'GREEN', 'BLUE' or 'GREY'.
$ButtonStyle = 'BLUE';
// /  --Spinner Style--
// /   Set the default spinner to use as a loading indicator while operations are being processed.
// /   Valid options are 0, 1, 2, 3, 4, 5 or 6.
$SpinnerStyle = 6;
// /  --Spinner Color--
// /   Set the default color to use for the loading spinner.
// /   If you would like the spinner to automatically match the rest of the color scheme, set this to $ButtonStyle.
// /   Valid options are  'RED', 'GREEN', 'BLUE', 'GREY' or '$ButtonStyle'.
$SpinnerColor = $ButtonStyle;
// /  --Show Full GUI--
// /   Set whether or not to display a full GUI by default.
// /   If this is set to TRUE a full GUI with text will be displayed.
// /   If this is set to FALSE a minimal GUI with only required elements will be displayed.
$ShowGUI = TRUE;
// /  --Show Fine Print--
// /   Set whether or not to display the Terms of Service & Privacy Policy links.
// /   If set to TRUE links to the --Terms of Service URL-- and --Privacy Policy URL-- will display at the bottom of the page.
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
