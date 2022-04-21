<?php
// This file contains the configuration data for the ScanCore Server application.
// Make sure to fill out the information below 100% accuratly BEFORE you attempt to run
// any ScanCore Server application scripts. Severe filesystem damage could result.

// BE SURE TO FILL OUT ALL INFORMATION ACCURATELY !!!
// PRESERVE ALL SYNTAX AND FORMATTING !!!
// SERIOUS FILESYSTEM DAMAGE COULD RESULT FROM INCORRECT DATABASE OR DIRECTORY INFO !!!

// htts://github.com/zelon88/ScanCore
// / ------------------------------


// / ------------------------------
// / License Information ...
  // / To continue, please accept the included GPLv3 license by changing the following 
  // / variable to '1'. By changing the '$Accept_GPLv3_OpenSource_License' variable to '1'
  // / you aknowledge that you have read and agree to the terms of the included LICENSE file.
$Accept_GPLv3_OpenSource_License = '1';
// / ------------------------------

// / ------------------------------ 
// / Directory locations ...
  // / The default location to scan if run with no input scan path argument. 
$ScanLoc = '';
  // / The absolute path where log files are stored.
$LogsDir = 'Logs';
  // / The absolute path where report files are stored.
$ReportDir = 'Reports';
  // / The filename for the ScanCore report file.
$ReportFileName = 'ScanCore_Report.txt';
  // / The filename for the ScanCore log file.
$logfilename = 'ScanCore_Latest-Log.txt';
  // / The filename for the ScanCore virus definition file.
$DefsFileName = 'ScanCore_Virus.def';
  // / The filename for the ScanCore virus definition file.
$DefsDir = realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR;
  // / The absolute path where virus definitions are found.
$DefsFile = $DefsDir.$DefsFileName;
// / ------------------------------ 

// / ------------------------------ 
// / General Information ...
  // / Number of bytes to store in each logfile before splitting to a new one.
$MaxLogSize = '100000000000000000000';
// / ------------------------------ 