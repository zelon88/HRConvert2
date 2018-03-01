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
  // / To continue, please accept the included GPLv3 license by changing the following 
  // / variable to '1'. By changing the '$Accept_GPLv3_OpenSource_License' variable to '1'
  // / you aknowledge that you have read and agree to the terms of the included LICENSE file.
$Accept_GPLv3_OpenSource_License = '1';
// / ------------------------------

// / ------------------------------  
// / Security Information ... 
  // / HRConvert2 Server can run on a local machine or on a network as a server to
  // / serve clients over http using standard web browsers.

  // / Secret Salts.
    // / Change these to something completely random and keep it a secret. Store your $Salts
    // / in hardcopy form or an encrypted drive in case of emergency.
    // / IF YOU LOSE YOUR SALTS YOU WILL BE UNABLE TO DECODE USER ID'S AFTER AN EMEREGENCY.
$Salts1 = 'somethingSoRaqnDasdoMThatNobody_Will_evar+guess+itgdgdfgfdsfgdasfdas';
$Salts2 = 'gdfsgdfsgsdfsomade212121oRanDoMThatNobody_Will_evar+guess+it';
$Salts3 = 'sometewhingSoRaewnDoMThatNobody54534534534_Will_evar+guess+it';
$Salts4 = 'somethinewgSoRanw3dDoMThatNobody;lk;jll;;l_Will_evar+guess+it';
$Salts5 = 'sometewhingSoRasadasnDoMThatNobodyr3454r3r33_Will_evar+guess+it';
$Salts6 = 'sometewhingSoRadasnDoMTfgeehatNobodyawryoglukfgy;/.5^&#&__Will_evar+guess+it';
  // / Externally or internally accesible domain or IP.
$URL = 'https://www.honestrepair.net';
  // / Scan for viruses during directory scan. Use '1' for default. 
   // / (ClamAV MUST be installed on the localhost!!!).
$VirusScan = '1';
  // / Use multi-threaded virus scanning. Virus scanning is extremely resource intensive. 
    // / If you are running an older machine (Rpi, CoreDuo, or any single-core CPU) leave 
    // / this setting disabled '0'.
$HighPerformanceAV = '1';
  // / Thorough A/V scanning requires stricter permissions, and may require additional 
    // / ClamAV user, usergroup, and permissions configuration.
    // / Disable '0' if you experience errors.
    // / Enable '0' if you experience false-negatives.
$ThoroughAV = '0';
  // / Persistent A/V scanning will try to achieve the highest level of scanning that is
    // / possible with available permissions. 
    // / When enabled; If errors are encountered ANY AND EVERY attempt to recover from the 
      // / error will be made. No expense will be spared to complete the operation.
    // / When disabled; If errors are encountered, NO ATTEMPTS to recover from the error
      // / will be made. The operation will be abandoned and abort after reasonable effort.
$PersistentAV = '1';
// / ------------------------------

// / ------------------------------ 
// / Directory locations ...
// / The ServerRootDir should be pointed at the root of your web server directory.
  // / (NO SLASH AFTER DIRECTORY!!!) ...  
$ServerRootDir = '/var/www/html';
  // / Use format '/home/YOUR_USERNAME/Desktop/TestDir'. (NO SLASH AFTER DIRECTORY!!!) ...  
  // / YOU MUST INSTALL HRConvert2 TO THE FOLLOWING DIRECTORY!!!
  // / DO NOT CHANGE THE DEFAULT INSTALL DIRECTORY!!! 
$InstLoc = '/var/www/html/HRProprietary/HRConvert2';
  // / The CloudLoc is where permanent Cloud files are stored. (NO SLASH AFTER DIRECTORY!!!) ...  
$ConvertLoc = '/mnt/22E65FA5E175984D/Convert';
// / ------------------------------ 

// / ------------------------------ 
// / General Information ...
// / Authentication Mode.
  // / Set to '0' for no authentication... Convert anything from anyone.
  // / Set to '1' for Logged-In users only... Only logged-in users can convert files.
  // / Set to '2' for Admin-Only... Only administrators can convert files.
$Auth_Level = '0';
// / HRCloud2 Integration.
  // / Set to '0' to use HRConvert2 as a standalone application.
  // / Set to '1' to enable HRCloud2-user features for logged-in users when HRCloud2 is installed.
$HRC2_Integration = '1';
$HRC2_InstLoc = '/var/www/html/HRProprietary/HRCloud2';
// / Number of minutes to keep user supplied files.
  // / Default is '30'.
  // / Set to '0' to keep files indefinately.
$Delete_Threshold = '30';
// / Log directory.
$LogDir = '/var/www/html/HRProprietary/HRConvert2/Logs';
// / Number of megabytes to store in each logfile before splitting to a new one.
$MaxLogSize = '1';
// / Terms of Service URL.
$TOSURL = 'https://www.honestrepair.net/index.php/terms-of-service/';
// / Privacy Policy URL.
$PPURL = 'https://www.honestrepair.net/index.php/privacy-policy/';
// / ------------------------------ 
