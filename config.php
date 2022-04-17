<?php
// / ------------------------------
// / ---Application Information---
// / 
// This file contains the configuration data for the HRConvert2 Server application.
// Make sure to fill out the information below 100% accuratly BEFORE you attempt to run
// any HRConvert2 Server application scripts. Severe filesystem damage could result.
// / 
// BE SURE TO FILL OUT ALL INFORMATION ACCURATELY !!!
// PRESERVE ALL SYNTAX AND FORMATTING !!!
// SERIOUS FILESYSTEM DAMAGE COULD RESULT FROM INCORRECT DATABASE OR DIRECTORY INFO !!!
// / ------------------------------

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
$URL = 'localhost';
// /  --Virus Scanning--
// /   Scan for viruses during directory scan.
// /   Requires ClamAV to be installed on the server.
// /   Set to TRUE to enable virus scanning with ClamAV.
// /   Set to FALSE to disable virus scanning. 
// /   (ClamAV MUST be installed on the localhost!!!).
$VirusScan = FALSE;
// / ------------------------------

// / ------------------------------ 
// / ---Directory Information---
// / 
// /  --Installation Directory--
// /   Install HRConvert2 to the following directory.
// /   DO NOT CHANGE THE DEFAULT INSTALL DIRECTORY!!! 
$InstLoc = '/var/www/html/HRProprietary/HRConvert2';
// /  --Server Root Directory--
// /   This should be pointed at the root of your web server directory.
// /   (NO SLASH AFTER DIRECTORY!!!) ...  
$ServerRootDir = '/var/www/html';
// /  --Data Storage Location--
// /   This is where temporary data files are stored. (NO SLASH AFTER DIRECTORY!!!) ...  
$ConvertLoc = '/home/justin/Documents/Projects/DATA/ConvertDATA';
// /  --Log Storage Location--
// /   This is where permanent Log files are stored. (NO SLASH AFTER DIRECTORY!!!) ... 
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
// /   If a user attempts a language that is not available $DefaultLanguage will be used instead.
// /   If set to FALSE the $DefaultLanguage will always be used.
$AllowUserSelectableLanguage = TRUE;
// /  --File Deletion Age Theshold--
// /   Age in minutes of files to be deleted.
// /   Set to '0' to keep files indefinately.
// /   Default is '30'.
$DeleteThreshold = 30;
// /  --Enhanced Logging Verbosity--
// /   Enable verbose logging.
// /   If set to TRUE all core events will be logged.
// /   If set to FALSE only certain errors & certain major core events will be logged.
$Verbose = TRUE;
// /  --Maximum Log File Size--
// /   Set the number of bytes to store in each logfile before splitting to a new one.
$MaxLogSize = '1048576';
// /  --UI Element Font--
// /   Set the default font to use throughout HRConvert2 GUI elements.
// /   Whatever font you choose must be installed on the client's machine.
// /   If the font is not available the client default will be used.
$Font = 'Arial';
// /  --Button Color--
// /   Set the default color scheme to use for buttons.
// /   Valid options are 'RED', 'GREEN', 'BLUE', or 'GREY'.
$ButtonStyle = 'BLUE';
// /  --Show Full GUI--
// /   Set whether or not to display a full GUI by default.
// /   If this is set to TRUE a full GUI with text will be displayed.
// /   If this is set to FALSE a minimal GUI with only required elements will be displayed.
$ShowGUI = TRUE;
// /  --Show Fine Print--
// /   Set whether or not to display the Terms of Service & Privacy Policy links.
// /   If set to TRUE links to the $TOSURL and $PPURL will display at the bottom of the page.
$ShowFinePrint = TRUE;
// /  --Terms of Service URL--
// /   The URL to use for the Terms of Service link at te bottom of the GUI.
// /   Only takes effect if $ShowFinePrint is set to TRUE.
$TOSURL = 'https://www.honestrepair.net/index.php/terms-of-service/';
// /  --Privacy Policy URL--
// /   The URL to use for the Privacy Policy link at te bottom of the GUI.
// /   Only takes effect if $ShowFinePrint is set to TRUE.
$PPURL = 'https://www.honestrepair.net/index.php/privacy-policy/';
// / ------------------------------ 
