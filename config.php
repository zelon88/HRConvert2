<?php

// This file contains the configuration data for the HRConvert2 Server application.
// Make sure to fill out the information below 100% accuratly BEFORE you attempt to run
// any HRConvert2 Server application scripts. Severe filesystem damage could result.

// BE SURE TO FILL OUT ALL INFORMATION ACCURATELY !!!
// PRESERVE ALL SYNTAX AND FORMATTING !!!
// SERIOUS FILESYSTEM DAMAGE COULD RESULT FROM INCORRECT DATABASE OR DIRECTORY INFO !!!
// / ------------------------------


// / ------------------------------
// / License Information ...
// /   To continue, please accept the included GPLv3 license by changing the following 
// /   variable to '1'. By changing the '$Accept_GPLv3_OpenSource_License' variable to '1'
// /   you aknowledge that you have read and agree to the terms of the included LICENSE file.
$Accept_GPLv3_OpenSource_License = '1';
// / ------------------------------

// / ------------------------------  
// / Security Information ... 
// /   Change these Salts to something completely random and keep it a secret. 
// /   Store your $Salts in hardcopy form or an encrypted drive in case of emergency.
// /   IF YOU LOSE YOUR SALTS YOU WILL BE UNABLE TO DECODE USER ID'S AFTER AN EMEREGENCY.
$Salts1 = 'somethingSoRanDoMThatNobody_4Will_evar+guess+itgdgdfgfdsfgdasfdas';
$Salts2 = 'gdfsgdfsgsdfsomethingSoRa33nDoMThatNobody_Will_evar+guess+it';
$Salts3 = 'somethingSoRanDoMThatNobo423432dy54534534534_Will_evar+guess+it';
$Salts4 = 'somethingSoRanDoMThat231;l_Will_evar+guess+it';
$Salts5 = 'somethingSoRanDoMThatNobodyr3454r3r33_Will_evar+guess+it';
$Salts6 = 'somethingSoRanDoMThatNob2odyawryoglukfgy;/.5^&#&__Will_evar+guess+it';
// /   Externally or internally accesible domain or IP.
$URL = 'localhost';
// /   Scan for viruses during directory scan. 
// /   Set to TRUE to enable virus scanning with ClamAV.
// /   Set to FALSE to disable virus scanning. 
// /   (ClamAV MUST be installed on the localhost!!!).
$VirusScan = FALSE;
// / ------------------------------

// / ------------------------------ 
// / Directory locations ...
// /   Install HRConvert2 to the following directory.
// /   DO NOT CHANGE THE DEFAULT INSTALL DIRECTORY!!! 
$InstLoc = '/var/www/html/HRProprietary/HRConvert2';
// /   The ServerRootDir should be pointed at the root of your web server directory.
// /   (NO SLASH AFTER DIRECTORY!!!) ...  
$ServerRootDir = '/var/www/html';
// /   The CloudLoc is where temporary data files are stored. (NO SLASH AFTER DIRECTORY!!!) ...  
$ConvertLoc = '/home/justin/Documents/Projects/DATA/ConvertDATA';
// /   The CloudLoc is where permanent Log files are stored. (NO SLASH AFTER DIRECTORY!!!) ... 
$LogDir = '/var/www/html/HRProprietary/HRConvert2/Logs';
// / ------------------------------ 

// / ------------------------------ 
// / General Information ...
// /   The default name to display for this application.
// /   You can change this to make it fit with other services your organization provides.
$ApplicationName = 'HRConvert2';
// /   The default title to display in taskbars & window managers.
// /   You can change this to make it fit with other services your organization provides.
$ApplicationTitle = 'Convert Anything!';
// /   Age in minutes of files to be deleted.
// /   Set to '0' to keep files indefinately.
// /   Default is '30'.
$Delete_Threshold = '30';
// /   Number of bytes to store in each logfile before splitting to a new one.
$MaxLogSize = '1048576';
// /   The default font to use throughout HRConvert2 GUI elements.
$Font = 'Arial';
// /   Set whether or not to display a full GUI by default.
$ShowGUI = TRUE;
// /   Set whether or not to display the Terms of Service & Privacy Policy links.
$ShowFinePrint = TRUE;
// /   Terms of Service URL.
$TOSURL = 'https://www.honestrepair.net/index.php/terms-of-service/';
// /   Privacy Policy URL.
$PPURL = 'https://www.honestrepair.net/index.php/privacy-policy/';
// / ------------------------------ 
