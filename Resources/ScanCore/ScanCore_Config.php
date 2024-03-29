<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / ScanCore, Copyright on 3/28/2024 by Justin Grimes, www.github.com/zelon88 
// / 
// / LICENSE INFORMATION ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / BSD or MIT licensing is available. Reach out to @zelon88 for more information.
// / https://www.gnu.org/licenses/gpl-3.0.html
// / 
// / APPLICATION INFORMATION ...
// / This application is designed to scan files & folders for viruses.
// / 
// / FILE INFORMATION ...
// / v1.3.
// / This file contains the core logic of the ScanCore application.
// /
// / HARDWARE REQUIREMENTS ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// / 
// / DEPENDENCY REQUIREMENTS ... 
// / This application should run on Linux or Windows systems with PHP 8.0 (or later).
// / Git is preferred for performing automatic update operations, but not required.
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
// / Reqiured Arguments Include:
// / 
// /   File or folder to scan:                 /path/to/scan
// / 
// / Optional Arguments Include:
// / 
// /   Show version information:               -version
// /                                           -ver
// / 
// /   Show help information:                  -help
// /                                           -h
// / 
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
// /   Force a specific report file:           -reportfile /path/to/file
// /                                           -rf path/to/file
// / 
// /   Force a specific configuration file:    -configfile /path/to/file
// /                                           -cf path/to/file
// / 
// /   Force a specific definitions file:      -defsfile /path/to/file
// /                                           -df path/to/file
// / 
// /   Force maximum log size (in bytes):      -maxlogsize ###
// /                                           -ml ###
// / 
// /   Perform definition update:              -updatedefinitions
// /                                           -ud
// / 
// /   Perform application update:             -updateapplication
// /                                           -ua
// / 
// / <3 Open-Source
// / -----------------------------------------------------------------------------------



// / -----------------------------------------------------------------------------------
// / General Information ...
// / 
// /  --Allow Application Updates--
// /   Allow application updates. Requires git. Will replace ScanCore_Config.php & rename the original.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$ApplicationUpdates = TRUE;
// /  --Application Update URL--
// /   The URL of a Git repository containing application updates.
// /   Valid options are a URL to a ScanCore source code Git repository, formatted as a string.
// /   Default is 'https://github.com/zelon88/ScanCore'.
$ApplicationUpdateURL = 'https://github.com/zelon88/ScanCore';
// /  --Application Update Domain--
// /   The domain, including http or https, that you intent to use for application updates.
// /   ScanCore will test this connection before attempting any update operations.
// /   Valid options are a URL to the domain where you connect for definition updates.
// /   Default is 'github.com'
$ApplicationUpdateDomain = 'github.com';
// /  --Application Repository Name--
// /   The name of the repository containing the application updates to use.
// /   Valid options are the name of the repository, formatted as a string.
// /   Default is 'ScanCore'.
$ApplicationRepositoryName = 'ScanCore';
// /  --Allow Definition Updates--
// /   Allow definition updates.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$DefinitionUpdates = TRUE;
// /  --Definition Update URL--
// /   The URL of a Git repository containing the definition updates to use.
// /   Valid options are a URL to a ScanCore source code Git repository, formatted as a string.
// /   Default is 'https://github.com/zelon88/ScanCore_Definitions'.
$DefinitionUpdateURL = 'https://github.com/zelon88/ScanCore_Definitions';
// /  --Definition Update Domain--
// /   The domain, including http or https, that you intent to use for definition updates.
// /   ScanCore will test this connection before attempting any update operations.
// /   Valid options are a URL to the domain where you connect for definition updates.
// /   Default is 'github.com'
$DefinitionUpdateDomain = 'github.com';
// /  --Definition Repository Name--
// /   The name of the repository containing the definition updates to use.
// /   Valid options are the name of the repository, formatted as a string.
// /   Default is 'ScanCore_Definitions'.
$DefinitionRepositoryName = 'ScanCore_Definitions';
// /  --Definition Subscriptions--
// /   The type of definition updates to subscribe to.
// /   Must be formatted as an array.
// /   Valid options are 'Virus', 'Malware', 'Pup'.
// /   Default is 'Virus', 'Malware', 'PUP'.
$DefinitionsUpdateSubscriptions = array('Virus', 'Malware', 'PUP');
// /  --Update Method--
// /   The method to use while performing updates.
// /   If 'git' is installed locally, the 'git' option is preferred.
// /   If 'git' is not installed & cannot be installed, the 'raw' option can be used instead.
// /   Valid options are 'git', 'raw'.
// /   Default is 'raw'.
$UpdateMethod = 'raw';
// /  --Default Maximum Log Size--
// /   Number of bytes to store in each logfile before splitting to a new one.
// /   Must be formatted as an integer, or an equation that evaluates to an integer.
// /   Default is 1024*1024*32.
$DefaultMaxLogSize = 1024*1024*32;
// /  --Enable Debug Mode--
// /   Enable "debug" mode (more logging).
// /   Valid options are TRUE or FALSE.
// /   Default is FALSE.
$Debug = FALSE;
// /  --Enable Verbose Mode--
// /   Enable "verbose" mode (more console).
// /   Valid options are TRUE or FALSE.
// /   Default is FALSE.
$Verbose = FALSE;
// /  --Memory Limit--
// /   The maximum number of bytes of memory to allocate to file scan operations.
// /   Must be formatted as an integer, or an equation that evaluates to an integer.
// /   Default is 1024*1024*512.
$DefaultMemoryLimit = 1024*1024*512;
// /  --Chunk Size--
// /   When scanning large files the file will be scanned this many bytes at a time.
// /   Must be formatted as an integer, or an equation that evaluates to an integer.
// /   Default is 1024*1024*128.
$DefaultChunkSize = 1024*1024*128;
// /  --Configuration Version--
// /   The version of this file, used for internal version integrity checks.
// /   Must be formatted as a string. Must match the version of ScanCore.php file.
$ConfigVersion = 'v1.3';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Directory locations ...
// / 
// /  --Scan Location--
// /   The default path to scan if run with no input scan path argument.
// /   Default is ''.
$ScanLoc = '';
// /  --Report Location--
// /   The absolute path where report files are stored.
// /   Default is 'Logs'.
$ReportDir = 'Logs';
// /  --Report File Name--
// /   The filename for the ScanCore report file.
// /   Default is 'ScanCore_Report.txt'.
$ReportFileName = 'ScanCore_Report.txt';
// /  --Definitions File Name--
// /   The filename for the ScanCore virus definition file.
// /   Default is 'ScanCore_Combined_Definitions.def'.
$DefsFileName = 'ScanCore_Combined_Definitions.def';
// /  --Installation Directory--
// /   The absolute path where this application is installed.
// /   Default is realpath(dirname(__FILE__)).
$InstallDir = realpath(dirname(__FILE__));
// /  --Definitions File--
// /   The absolute path where the Definitions File can be found.
// /   Default is  $InstallDir.DIRECTORY_SEPARATOR.$DefsFileName.
$DefsFile = $InstallDir.DIRECTORY_SEPARATOR.$DefsFileName;
// / -----------------------------------------------------------------------------------