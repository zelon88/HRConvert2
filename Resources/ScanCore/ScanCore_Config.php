<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / ScanCore, Copyright on 3/22/2024 by Justin Grimes, www.github.com/zelon88 
// / 
// / LICENSE INFORMATION ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// / 
// / APPLICATION INFORMATION ...
// / This application is designed to scan files & folders for viruses.
// / 
// / FILE INFORMATION ...
// / v1.0.
// / This file contains the configuration data for the ScanCore application.
// / Make sure to fill out the information below 100% accurately.
// /
// / HARDWARE REQUIREMENTS ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// / 
// / DEPENDENCY REQUIREMENTS ... 
// / This application should run on Linux or Windows systems with PHP 8.0 (or later).
// / 
// / VALID SWITCHES / ARGUMENTS / USAGE ...
// / Quick Start Example:
// /  C:\Path-To-PHP-Binary.exe C:\Path-To-ScanCore.php C:\Path-To-Scan\ -m [integer] -c [integer] -v -d
// / 
// / Start by opening a command-prompt.
// / Type the absolute path to a portable PHP 7.0+ binary. Don't press enter just yet.
// / Now type the absolute path to this PHP file as the only argument for the PHP binary.
// / Everything after the path to this script will be passed to this file as an argument.
// / The first Argument Must be a valid absolute path to the file or folder being scanned.
// / Optional arguments can be specified after the scan path. Separate them with spaces.
// / 
// / Optional Arguments Include:
// /   Force recursion:                        -recursion
// /                                           -r
// / 
// /   Force no recursion:                     -norecursion
// /                                           -nr
// / 
// /   Specify memory limit (in bytes):        -memorylimit ####
// /                                           -m ####
// / 
// /   Specify chunk size (in bytes);          -chunksize ####
// /                                           -c ####
// / 
// /   Enable "debug" mode (more logging):     -debug
// /                                           -d
// / 
// /   Enable "verbose" mode (more console):   -verbose
// /                                           -v
// / 
// /   Force a specific log file:              -logfile /path/to/file
// /                                           -lf path/to/file
// / 
// /   Force a specific report file:           -reportfile /path/to/file
// /                                           -rf path/to/file
// / 
// /   Force maximum log size (in bytes):      -maxlogsize ###
// /                                           -ml ###
// / 
// / <3 Open-Source
// / -----------------------------------------------------------------------------------



// / -----------------------------------------------------------------------------------
// / General Information ...
  // / Number of bytes to store in each logfile before splitting to a new one.
$DefaultMaxLogSize = '100000000000000000000';
  // / Enable "debug" mode (more logging).
$Debug = FALSE;
  // / Enable "verbose" mode (more console).
$Verbose = FALSE;
  // / The maximum number of bytes of memory to allocate to file scan operations.
$DefaultMemoryLimit = 4000000;
  // / When scanning large files the file will be scanned this many bytes at a time.
$DefaultChunkSize = 1000000;
  // / The version of this file, used for internal version integrity checks.
$SCConfigVersion = 'v1.0';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Directory locations ...
  // / The default location to scan if run with no input scan path argument.
$ScanLoc = '';
  // / The absolute path where log files are stored.
$LogsDir = 'Logs';
  // / The absolute path where report files are stored.
$ReportDir = 'Logs';
  // / The filename for the ScanCore report file.
$SCReportFileName = 'ScanCore_Report.txt';
  // / The filename for the ScanCore log file.
$SCLogFileName = 'ScanCore_Latest-Log.txt';
  // / The filename for the ScanCore virus definition file.
$DefsFileName = 'ScanCore_Virus.def';
  // / The filename for the ScanCore virus definition file.
$DefsDir = realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR;
  // / The absolute path where virus definitions are found.
$DefsFile = $DefsDir.$DefsFileName;
// / -----------------------------------------------------------------------------------