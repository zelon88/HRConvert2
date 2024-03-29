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
// / The following code sets global variables for the session.
function verifyInstallation() {
  // / Set variables.
  global $Date, $Time, $Version, $InstallationVerified, $FileCount, $DirCount, $Infected, $EOL, $SEP, $RP, $CoreFile;
  // / Time related variables.
  $Date = date("m_d_y");
  $Time = date("F j, Y, g:i a");
  // / Application related variables.
  $Version = 'v1.3';
  $FileCount = $DirCount = $Infected = 0;;
  $EOL = PHP_EOL;
  $SEP = DIRECTORY_SEPARATOR;
  $RP = realpath(dirname(__FILE__));
  $CoreFile = 'ScanCore.php';
  $InstallationVerified = TRUE;
  return array($InstallationVerified, $Version); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to load a specified configuration file.
// / If either the -configfile or -cf argument is not set, the default configuration file named 'ScanCore_Config.php' will be used instead.
// / If either the -defsfile or -df argument is not set, the definitions file named specified in 'ScanCore_Config.php' will be used instead. 
function loadConfig($Version) {
  // / Set variables.
  global $argv, $ConfigFilePath, $RP, $SEP, $EOL, $ConfigFile, $ScanLoc, $DefsFile, $ConfigVersion, $ConfigLoaded, $DefsExist, $Version, $ReportFile, $ReportDir, $ReportFileName, $RequiredDirs, $InstallDir, $MaxLogSize, $MemoryLimit, $ChunkSize, $DefaultMemoryLimit, $DefaultChunkSize, $DefaultMaxLogSize, $DefinitionRepositoryName, $DefinitionUpdates, $DefinitionUpdateDomain, $DefinitionUpdateURL, $DefInstallDir, $DefGitDir, $ApplicationRepositoryName, $ApplicationUpdates, $ApplicationUpdateDomain, $ApplicationUpdateURL, $AppInstallDir, $AppGitDir, $DefinitionsUpdateSubscriptions, $DefsFileName, $Verbose, $Debug, $UpdateMethod;
  $ConfigLoaded = $DefsExist = FALSE;
  $ConfigFile = 'ScanCore_Config.php';
  $ConfigFilePath = $RP.$SEP.$ConfigFile;
  // / Initialize an empty array if no arguments are set.
  if (!isset($argv)) $argv = array();
  // / Briefly iterate through supplied arguments just to see if we need to load a special configuration file.
  foreach ($argv as $key => $arg) if ($arg == '-configfile' or $arg == '-cf') $ConfigFilePath = $argv[$key + 1];
  // / Load the configuration file located at $ConfigFile.
  if (file_exists($ConfigFilePath)) $ConfigLoaded = require_once ($ConfigFilePath);
  // / Briefly iterate through supplied arguments just to see if we need to load a special definitions file.
  foreach ($argv as $key => $arg) if ($arg == '-defsfile' or $arg == '-df') $DefsFile = $argv[$key + 1];
  // / Check to make sure the configuration file was loaded & the configuration version is compatible with the core.
  if (isset($ScanLoc) && isset($DefsFile) && isset($ConfigVersion) && $ConfigVersion === $Version && $ConfigLoaded) {
    // / Check if the definitions file exists.
    if (file_exists($DefsFile)) $DefsExist = TRUE;
    // / Configuration related variables.
    $ReportFile = $ReportDir.$SEP.$ReportFileName;
    $RequiredDirs = array($ReportDir);
    $UpdateMethod = strtolower($UpdateMethod);
    $MaxLogSize = $DefaultMaxLogSize; 
    $MemoryLimit = $DefaultMemoryLimit;
    $ChunkSize = $DefaultChunkSize; 
    $DefInstallDir = $InstallDir.$SEP.$DefinitionRepositoryName;
    $AppInstallDir = $InstallDir.$SEP.$ApplicationRepositoryName;
    $DefGitDir = $DefInstallDir.$SEP.'.git';
    $AppGitDir = $AppInstallDir.$SEP.'.git'; }
    // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
    $arg = $key = NULL;
    unset($arg, $key);
  return array($ConfigLoaded, $DefsExist, $ConfigFilePath); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to reliably build help & version information.
function buildHelpInformation() {
  // / Set variables.
  global $DefinitionsUpdateSubscriptions, $SubText, $VersionText, $HelpText, $ApplicationUpdateURL, $DefinitionUpdateURL, $DefsFile, $CoreFile, $ConfigFile, $Version, $EOL, $RP;
  $InformationBuilt = FALSE;
  $SubText = $VersionText = $HelpText = '';
  foreach ($DefinitionsUpdateSubscriptions as $defSubs) $SubText = $SubText.' '.$defSubs.',';
  if (file_exists($CoreFile) && file_exists($DefsFile) && file_exists($ConfigFile)) {
    $InformationBuilt = TRUE;
    $SubText = trim(trim($SubText, ','), ' ');
    $originalRepo = 'https://github.com/zelon88/ScanCore';
    $licenseText = 'GPLv3';
    $verText1 = 'ScanCore '.$Version.' by Justin Grimes (@zelon88), licensed under '.$licenseText.'.'.$EOL;
    $verText2 = 'The original source code for this application can be found at:  '.$originalRepo.$EOL;
    $verText3 = 'This installation is located at:  '.realpath(__FILE__).$EOL;
    $verText4 = 'This installation is using a definitions file located at:  '.realpath($DefsFile).$EOL;
    $verText5 = 'This installation is using a configuration file located at:  '.realpath($ConfigFile).$EOL;
    $verText6 = 'This installation downloads Application updates from:  '.$ApplicationUpdateURL.$EOL;
    $verText7 = 'This installation downloads Definition updates from:  '.$DefinitionUpdateURL.$EOL;
    $verText8 = 'Configuration file was last updated on:  '.date("F d Y H:i:s.", @filectime($ConfigFile)).$EOL;
    $verText9 = 'Application update was last installed on:  '.date("F d Y H:i:s.", @filectime($CoreFile)).$EOL;
    $verText10 = 'Definition update was last installed on:  '.date("F d Y H:i:s.", @filectime($DefsFile)).$EOL;
    $verText11 = 'This installation has the following Definition Subscriptions:  '.$SubText;
    $helpText0 = $EOL.'Reqiured Arguments Include:'.$EOL;
    $helpText1 = $EOL.'  File or folder to scan:                 /path/to/scan'.$EOL;
    $helpText2 = $EOL.'Optional Arguments Include:'.$EOL;
    $helpText3 = $EOL.'  Show version information:               -version'.$EOL;
    $helpText4 = '                                          -ver'.$EOL;
    $helpText5 = $EOL.'  Show help information:                  -help'.$EOL;
    $helpText6 = '                                          -h'.$EOL;
    $helpText7 = $EOL.'  Force recursion:                        -recursion'.$EOL;
    $helpText8 = '                                          -r'.$EOL;
    $helpText9 = $EOL.'  Force no recursion:                     -norecursion'.$EOL;
    $helpText10 = '                                          -nr'.$EOL;
    $helpText11 = $EOL.'  Specify memory limit (in bytes):        -memorylimit ####'.$EOL;
    $helpText12 = '                                          -m ####'.$EOL;
    $helpText13 = $EOL.'  Specify chunk size (in bytes);          -chunksize ####'.$EOL;
    $helpText14 = '                                          -c ####'.$EOL;
    $helpText15 = $EOL.'  Enable "debug" mode (more logging):     -debug'.$EOL;
    $helpText16 = '                                          -d'.$EOL;
    $helpText17 = $EOL.'  Enable "verbose" mode (more console):   -verbose'.$EOL;
    $helpText18 = '                                          -v'.$EOL;
    $helpText21 = $EOL.'  Force a specific report file:           -reportfile /path/to/file'.$EOL;
    $helpText22 = '                                          -rf path/to/file'.$EOL;
    $helpText23 = $EOL.'  Force a specific configuration file:    -configfile /path/to/file'.$EOL;
    $helpText24 = '                                          -cf path/to/file'.$EOL;
    $helpText25 = $EOL.'  Force a specific definitions file:      -defsfile /path/to/file'.$EOL;
    $helpText26 = '                                          -df path/to/file'.$EOL;
    $helpText27 = $EOL.'  Force maximum log size (in bytes):      -maxlogsize ###'.$EOL;
    $helpText28 = '                                          -ml ###'.$EOL;
    $helpText29 = $EOL.'  Perform definition update:              -updatedefinitions'.$EOL;
    $helpText30 = '                                          -ud'.$EOL;
    $helpText31 = $EOL.'  Perform application update:             -updateapplication'.$EOL;
    $helpText32 = '                                          -ua'.$EOL;
    $qsText1 = $EOL.'Quick Start Example:'.$EOL;
    $qsText2 = $EOL.'  C:\Path-To-PHP-Binary.exe C:\Path-To-ScanCore.php C:\Path-To-Scan\ -m [integer] -c [integer] -v -d'.$EOL;
    $HelpText = $verText1.$qsText1.$qsText2.$helpText0.$helpText1.$helpText2.$helpText3.$helpText4.$helpText5.$helpText6.$helpText7.$helpText8.$helpText9.$helpText10.$helpText11.$helpText12.$helpText13.$helpText14.$helpText15.$helpText16.$helpText17.$helpText18.$helpText21.$helpText22.$helpText23.$helpText24.$helpText25.$helpText26.$helpText27.$helpText28.$helpText29.$helpText30.$helpText31.$helpText32;
    $VersionText = $verText1.$verText2.$verText3.$verText4.$verText5.$verText6.$verText7.$verText8.$verText9.$verText10.$verText11; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $defSubs = $verText1 = $verText2 = $verText3 = $verText4 = $verText5 = $verText6 = $verText7 = $originalRepo = $licenseText = $arg = $key = $helpText0 = $helpText1 = $helpText2 = $helpText3 = $helpText4 = $helpText5 = $helpText6 = $helpText7 = $helpText8 = $helpText9 = $helpText10 = $helpText11 = $helpText12 = $helpText13 = $helpText14 = $helpText15 = $helpText16 = $helpText17 = $helpText18 = $helpText21 = $helpText22 = $helpText23 = $helpText24 = $helpText25 = $helpText26 = $helpText27 = $helpText28 = $helpText29 = $helpText30 = $qsText1 = $qsText2 = NULL;
  unset($defSubs, $verText1, $verText2, $verText3, $verText4, $verText5, $verText6, $verText7, $originalRepo, $licenseText, $arg, $key, $helpText0, $helpText1, $helpText2, $helpText3, $helpText4, $helpText5, $helpText6, $helpText7, $helpText8, $helpText9, $helpText10, $helpText11, $helpText12, $helpText13, $helpText14, $helpText15, $helpText16, $helpText17, $helpText18, $helpText19, $helpText20, $helpText21, $helpText22, $helpText23, $helpText24, $helpText25, $helpText26, $helpText27, $helpText28, $helpText29, $helpText30, $qsText1, $qsText2); 
  return array($InformationBuilt, $SubText, $VersionText, $HelpText); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to create required directories when they do not already exist.
function createDirs($RequiredDirs) { 
  // / Set variables.
  global $Time, $RP, $SEP;
  $RequiredDirsExist = TRUE;
  // / Iterate through each required directory.
  foreach ($RequiredDirs as $reqdDir) {
    // / Detect if the directory already exists & create it if required.
    if (!file_exists($reqdDir)) mkdir($reqdDir);
    // / If an index.html file is present in the installation directory, copy it to the newly created dictory.
    if (!file_exists($reqdDir.$SEP.'index.html')) if (file_exists($RP.$SEP.'index.html')) copy($RP.$SEP.'index.html', $reqdDir.$SEP.'index.html');
    if (!file_exists($reqdDir)) $RequiredDirsExist = FALSE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $reqdDir = NULL;
  unset($reqdDir); 
  return array($RequiredDirsExist); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to add an entry to the logs.
function addLogEntry($entry, $error, $errorNumber) {
  // / Set variables.
  global $ReportFile, $Time, $EOL;
  if (!is_numeric($errorNumber)) $errorNumber = 0;
  if ($error === TRUE) $preText = 'ERROR!!! ScanCore-'.$errorNumber.' on '.$Time.', ';
  else $preText = $Time.', ';
  $LogCreated = file_put_contents($ReportFile, $preText.$entry.$EOL, FILE_APPEND);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $preText = $error = $entry = $errorNumber = NULL;
  unset($preText, $error, $entry, $errorNumber); 
  return array($LogCreated); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to handle important messages to the console & log file.
function processOutput($txt, $error, $errorNumber, $requiredLog, $requiredConsole, $fatal) {
  global $Date, $Tiome, $EOL, $Debug, $Verbose;
  $OutputProcessed = FALSE;
  // / Verify that all inputs are of the correct type.
  if (!is_string($txt)) $txt = '';
  if (!is_int($errorNumber)) $errorNumber = 0;
  // / Log the provided text if $Debug variable (-d switch) is set.
  if ($Debug or $requiredLog) list ($OutputProcessed) = addLogEntry($txt, $error, $errorNumber);
  // / Output the summary text to the terminal if the $Verbose (-v switch) variable is set.
  if ($Verbose or $requiredConsole) echo $txt.$EOL;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $txt = $error = $errorNumber = $requiredLog = $requiredConsole = NULL;
  unset($txt, $error, $errorNumber, $requiredLog, $requiredConsole); 
  // / Stop execution as needed.
  if ($fatal) die();
  return array($OutputProcessed); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to parse supplied command-line arguments.
// / The -configfile & -cf arguments are processed by the loadConfig() function.
// / The -defsfile & -df arguments are processed by the loadConfig() function.
function parseArgs($argv) {
  // / Set variables.
  // / Most of these should already be set to the values contained in the configuration file.
  global $ArgsParsed, $ReportFile, $MaxLogSize, $Debug, $Verbose, $ChunkSize, $MemoryLimit, $DefaultMemoryLimit, $DefaultChunkSize, $PerformDefUpdate, $PerformAppUpdate, $VersionText, $HelpText, $ConfigFilePath, $PerformScan, $DefsFile;
  $PerformScan = $Recursion = FALSE;
  $ArgsParsed = $PathToScan = $PerformDefUpdate = $PerformAppUpdate = $showVersion = $showHelp = FALSE;
  foreach ($argv as $key => $arg) {
    $arg = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $arg));
    if ($arg == '-version' or $arg == '-ver') $showVersion = TRUE;
    if ($arg == '-h' or $arg == '-help') $showHelp = TRUE;
    if ($arg == '-memorylimit' or $arg == '-m') $MemoryLimit = $argv[$key + 1];
    if ($arg == '-chunksize' or $arg == '-c') $ChunkSize = $argv[$key + 1];
    if ($arg == '-debug' or $arg == '-d') $Debug = TRUE;
    if ($arg == '-verbose' or $arg == '-v') $Verbose = TRUE;
    if ($arg == '-recursion' or $arg == '-r') $Recursion = TRUE;
    if ($arg == '-norecursion' or $arg == '-nr') $Recursion = FALSE;
    if ($arg == '-updatedefinitions' or $arg == '-ud') $PerformDefUpdate = TRUE;
    // The update application feature is not ready yet, so don't uncomment this.
    //if ($arg == '-updateapplication' or $arg == '-ua') $PerformAppUpdate = TRUE;
    if ($arg == '-reportfile' or $arg == '-rf' or $arg == '-logfile' or $arg == '-lf') $ReportFile = $argv[$key + 1];
    if ($arg == '-maxlogsize' or $arg == '-ml') $MaxLogSize = $argv[$key + 1]; }
  // / Detect if version or help information is being requested.
  if ($showVersion or $showHelp) {
    // / Build the help & version information.
    list ($InformationBuilt, $SubText, $VersionText, $HelpText) = buildHelpInformation();
    if ($InformationBuilt) processOutput('Built version information.', FALSE, 0, FALSE, FALSE, FALSE);
    else processOutput('Cannot not build version information!', TRUE, 0, TRUE, TRUE, TRUE);
    if ($showVersion) processOutput($VersionText, FALSE, 0, TRUE, TRUE, FALSE);
    if ($showHelp) processOutput($HelpText, FALSE, 0, TRUE, TRUE, FALSE);
    $ArgsParsed = TRUE; }
  // / Detect if an update is being requested.
  if ($PerformDefUpdate or $PerformAppUpdate) {
    processOutput('Starting ScanCore updater!', FALSE, 0, TRUE, TRUE, FALSE);
    $ArgsParsed = TRUE; }
  if (!$PerformDefUpdate && !$PerformAppUpdate && !$showVersion && !$showHelp) {
    // / Detect if no arguments were supplied.
    if (!isset($argv[1])) processOutput('There were no arguments set!', TRUE, 100, TRUE, TRUE, FALSE);
    else {
      // / Detect if a valid path to scan was supplied.
      if (!file_exists($argv[1])) processOutput('The specified file was not found! The first argument must be a valid file or directory path!', TRUE, 300, TRUE, TRUE, FALSE);
      else {
        $PathToScan = $argv[1];
        // / Detect if the MemoryLimit and ChunkSize variables are valid.
        if (!is_numeric($MemoryLimit) or !is_numeric($ChunkSize)) {
          processOutput('Using default ChunkSize & MemoryLimit values.', TRUE, 0, TRUE, FALSE, FALSE);
          $MemoryLimit = $DefaultMemoryLimit;
          $ChunkSize = $DefaultChunkSize; }
        // / Output status information.
        processOutput('Starting ScanCore!', FALSE, 0, TRUE, TRUE, FALSE);
        processOutput('Loaded configuration file:  '.$ConfigFilePath, FALSE, 0, TRUE, FALSE, FALSE);
        if (is_numeric($ChunkSize)) processOutput('The ChunkSize is:  '.$ChunkSize, TRUE, 0, FALSE, FALSE, FALSE);
        if (is_numeric($MemoryLimit)) processOutput('The MemoryLimit is:  '.$ChunkSize, TRUE, 0, FALSE, FALSE, FALSE);
        $ArgsParsed = $PerformScan = TRUE; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $key = $arg = $showVersion = $showHelp = NULL;
  unset($key, $arg, $showVersion, $showHelp);
  return array($ArgsParsed, $PerformScan, $PathToScan, $MemoryLimit, $ChunkSize, $Debug, $Verbose, $Recursion, $ReportFile, $MaxLogSize, $PerformDefUpdate, $PerformAppUpdate); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove files & folders.
function clean($Location) {
  // / Set variables.
  global $SEP;
  $LocationCleaned = FALSE;
  $f = FALSE;
  $i = array();
  // / Detect if the location is a folder.
  if (is_dir($Location)) {
    // / Scan the folder for contents.
    $i = array_diff(scandir($Location), array('..', '.'));
    // / Iterate through the contents of the folder.
    foreach ($i as $f) {
      // / If this object is a folder, run this function on it.
      if (is_dir($Location.$SEP.$f)) clean($Location.$SEP.$f);
      // / If this object is a file, delete it.
      else unlink($Location.$SEP.$f); }
    // / Try to delete the folder now that we've deleted the contents.
    if (is_dir($Location)) rmdir($Location); }
  // / If the location is a file, delete it.
  if (file_exists($Location) && !is_dir($Location)) unlink($Location);
  // / Check if the location was deleted.
  if (!is_dir($Location) && !file_exists($Location)) $LocationCleaned = TRUE; 
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $f = $i = NULL;
  unset($f, $i); 
  return array($LocationCleaned); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify internet connectivity before attempting to perform update operations.
// / Type must be either 'application' or 'definition'.
// / This function is limited to the two domains defined in config.php, to reduce potential for abuse.
function connectionSuccess($type) {
  global $ApplicationUpdateDomain, $DefinitionUpdateDomain;
  $ConnectionResult = TRUE;
  $connection = FALSE;
  $urlToCheck = '';
  if ($type === 'application') $urlToCheck = $ApplicationUpdateDomain;
  if ($type === 'definition') $urlToCheck = $DefinitionUpdateDomain;
  if ($urlToCheck !== '') $connection = @fsockopen($urlToCheck, 443);
  if ($connection) fclose($connection);
  else $ConnectionResult = FALSE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $connection = NULL;
  unset($connection); 
  return $ConnectionResult; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to install definition updates.
function updateDefinitions() {
  // / Set variables.
  global $DefinitionUpdates, $DefinitionUpdateURL, $DefinitionsUpdateSubscriptions, $InstallDir, $DefsFile, $DefinitionRepositoryName, $DefInstallDir, $DefGitDir, $UpdateMethod, $SEP, $EOL, $SubText, $RP;
  $UpdateDefininitionsComplete = $UpdateDefinitionsErrors = $defSubs = $writeCheck = $defInstallDirCleaned = $defGitDirCleaned = $cleanCheck = $rawWriteCheck = $rawWriteCheckDir = FALSE;
  $subData = $subData1 = $returnData = $rawDefData = $rawDefURL = '';
  $subCount = 0;
  $subCount1 = count($DefinitionsUpdateSubscriptions);
  // / Only perform definition updates if they are enabled in $ConfigFile.
  if ($DefinitionUpdates) {
    processOutput('Starting definition update. Update method is:  '.$UpdateMethod, FALSE, 0, TRUE, FALSE, FALSE);
    processOutput('Preparing to install the following Definition Subscriptions:  '.$SubText, FALSE, 0, FALSE, FALSE, FALSE);
    processOutput('Cleaning update environment.', FALSE, 0, TRUE, FALSE, FALSE);
    // / If a definition install directory already exists, remove all the files inside & then remove the folder.
    list($defInstallDirCleaned) = clean($DefInstallDir);
    list($defGitDirCleaned) = clean($DefGitDir);
    processOutput('Verifying network connectivity.', FALSE, 0, FALSE, FALSE, FALSE);
    $ConnectionResult = connectionSuccess('definition');
    if ($ConnectionResult) processOutput('Verified network connectivity.', FALSE, 0, FALSE, FALSE, FALSE);
    else processOutput('Cannot not verify network connectivity!', TRUE, 400, TRUE, TRUE, FALSE);
    // / Continue only if a connection could be made and the definition install directory was able to be cleaned.
    if ($ConnectionResult && $defGitDirCleaned && $defInstallDirCleaned) {
      // / Download the latest definitions from the $DefinitionUpdateURL.
      // / Perform the definition update by downloading the raw definition data.
      if ($UpdateMethod === 'raw') {
        processOutput('Creating a folder at:  '.$DefInstallDir, FALSE, 0, FALSE, FALSE, FALSE);
        $rawWriteCheckDir = mkdir($DefInstallDir);
        foreach ($DefinitionsUpdateSubscriptions as $defSubs) {
          $rawDefData = '';
          $defSubFile = $DefInstallDir.$SEP.'ScanCore_'.$defSubs.'.def';
          $rawDefURL = $DefinitionUpdateURL.'/raw/main/ScanCore_'.$defSubs.'.def';
          processOutput('Attempting download with built in functions against URL:  '.$rawDefURL, FALSE, 0, FALSE, FALSE, FALSE);
          if (file_exists($rawDefURL)) $rawDefData = file_get_contents($DefinitionUpdateURL);
          if (!file_exists($rawDefURL) or $rawDefData === '') {
            processOutput('Attempting download with cURL against URL:  '.$rawDefURL, FALSE, 0, FALSE, FALSE, FALSE);
            $returnData = shell_exec('curl -Ls '.$rawDefURL.' --output '.$defSubFile); 
            $rawWriteCheck = file_exists($defSubFile); }
          else $rawWriteCheck = file_put_contents($defSubFile, $rawDefData); } }
      // / Perform the definition update using 'git', if available.
      if ($UpdateMethod === 'git') $returnData = shell_exec('git clone '.$DefinitionUpdateURL);
      // / Only continue with the update if the previous operation was able to create a folder.
      if (is_dir($DefInstallDir)) {
        // / Copy an index.html file to the newly created folder as document root protection, incase this application is in a hosted location.
        if (file_exists($RP.$SEP.'index.html')) copy($RP.$SEP.'index.html', $DefInstallDir.$SEP.'index.html');
        // / Remove the .git directory, just in case this is installed in a hosted location we don't want to maintin that many directories.
        if (is_dir($DefGitDir)) list($cleanCheck) = clean($DefGitDir);
        else $cleanCheck = TRUE;
        // / Iterate through the list of susbscribed definitions.
        foreach ($DefinitionsUpdateSubscriptions as $defSubs) {
          $defSubFile = $DefInstallDir.$SEP.'ScanCore_'.$defSubs.'.def';
          // / Build the new definitions in memory from the subscriptions that apply to this installation.
          if (file_exists($defSubFile)) {
            processOutput('Loading Definition Subscription file:  '.$defSubFile, FALSE, 0, FALSE, FALSE, FALSE);
            $subCount++;
            $subData1 = file_get_contents($defSubFile);
            if ($subData1 !== FALSE) $subData = $subData.$EOL.$subData1; } }
        // / Write the new definition data to a new definition file.
        if (file_exists($DefsFile)) $writeCheck = unlink($DefsFile);
        else $writeCheck = TRUE;
        processOutput('Writing Combined Definitions file:  '.$DefsFile, FALSE, 0, FALSE, FALSE, FALSE);
        $writeCheck = file_put_contents($DefsFile, $subData); }
    // / If a definition install directory already exists, remove all the files inside & then remove the folder.
    list($defInstallDirCleaned) = clean($DefInstallDir);
    list($defGitDirCleaned) = clean($DefGitDir); } }
  // / Check if the subscription file was written successfully.
  if ($UpdateMethod === 'raw' && $rawWriteCheck) $UpdateDefininitionsComplete = TRUE;
  if ($UpdateMethod === 'git' && $writeCheck) $UpdateDefininitionsComplete = TRUE;
  if ($subCount !== $subCount1) $UpdateDefinitionsErrors = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $defSubs = $defSubFile = $subData = $writeCheck = $subCount = $subCount1 = $returnData = $f = $i = $cleanCheck = $rawWriteCheck = $rawWriteCheckDir = $rawDefData = $rawDefURL = $defGitDirCleaned = $defInstallDirCleaned = $subData1 = NULL;
  unset($defSubs, $defSubFile, $subData, $writeCheck, $subCount, $subCount1, $returnData, $f, $i, $cleanCheck, $rawWriteCheck, $rawWriteCheckDir, $rawDefData, $rawDefURL, $defGitDirCleaned, $defInstallDirCleaned, $subData1);
  return array($UpdateDefininitionsComplete, $UpdateDefinitionsErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to install application updates.
function updateApplication() {
  global $ApplicationUpdates, $ApplicationUpdateURL, $InstallDir, $ApplicationRepositoryName, $AppInstallDir, $AppGitDir, $ConfigFile;
  $UpdateApplicationComplete = $UpdateApplicationErrors = $configCopied = FALSE;
  $returnData = '';
  $configInc = 0;
  $backupConfigFile = $ConfigFile.'_'.$configInc.'_'.'.bak';
  // / Only perform application updates if they are enabled in $ConfigFile.
  // / If application updates are enabled, download the latest application update from the $ApplicationUpdateURL.
  if ($ApplicationUpdates) {
    // / Check if an existing backup configuration file exists, & set a path to a new one with an unused name.
    while (file_exists($backupConfigFile)) {
      $configInc++;
      $backupConfigFile = $ConfigFile.'_'.$configInc.'_'.'.bak'; }
    // / Copy the configuration file to a backup.
    $configCopied = copy($ConfigFile, $backupConfigFile);
    // / Only proceed if the configuration file was backed up.
    if ($configCopied) {
    //$returnData = shell_exec('git clone '.$ApplicationUpdateURL.' '.$InstallDir);
  } }
  // / Check if the git operation returned some output.
  if ($returnData !== '') $UpdateApplicationComplete = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $returnData = $configInc = $backupConfigFile = $configCopied = NULL;
  unset($sreturnData, $configInc, $backupConfigFile, $configCopied);
  return array($UpdateApplicationComplete, $UpdateApplicationErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// Read tab-delimited definitions file. Also hash the file to avoid self-detection.
function load_defs($DefsFile) {
  // / Set variables.
  $DefsLoaded = $Defs = $DefData = FALSE;
  if (!file_exists($DefsFile)) processOutput('Cannot not load the definitions file:  '.$DefsFile, TRUE, 500, TRUE, TRUE, TRUE);
  else {
    processOutput('Loaded the definitions file:  '.$DefsFile, FALSE, 0, FALSE, FALSE, FALSE);
    $Defs = file($DefsFile);
    $DefData = hash_file('sha256', $DefsFile);
    $counter = 0;
    $counttop = sizeof($Defs);
    while ($counter < $counttop) {
      $Defs[$counter] = explode('  ', $Defs[$counter]);
      $counter++; }
    processOutput('Found '.sizeof($Defs).' definitions.', FALSE, 0, FALSE, FALSE, FALSE);
    $DefsLoaded = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $counter = $counttop = NULL;
  unset($counter, $counttop); 
  return array($DefsLoaded, $Defs, $DefData); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// Hunt files/folders recursively for scannable items.
function file_scan($folder, $Defs, $DefsFile, $DefData, $Debug, $Verbose, $MemoryLimit, $ChunkSize, $Recursion) {
  // / Set variables.
  global $SEP, $FileCount, $DirCount, $Infected;
  $ScanComplete = FALSE;
  $DirCount++;
  if (is_dir($folder)) {
    processOutput('Scanning folder:  '.$folder, FALSE, 0, TRUE, FALSE, FALSE);
    $files = scandir($folder);
    foreach ($files as $file) {
      if ($file === '' or $file === '.' or $file === '..') continue;
      $entry = str_replace($SEP.$SEP, $SEP, $folder.$SEP.$file);
      if (!is_dir($entry)) list($checkComplete, $Infected, $FileCount) = virus_check($entry, $Defs, $DefsFile, $DefData, $Debug, $Verbose, $MemoryLimit, $ChunkSize);
      if (is_dir($entry) && $Recursion) {
        list ($scanComplete, $DirCount, $FileCount, $Infected) = file_scan($entry, $Defs, $DefsFile, $DefData, $Debug, $Verbose, $MemoryLimit, $ChunkSize, $Recursion); 
        $entry = ''; } } }
  if (!is_dir($folder) && $folder !== '.' && $folder !== '..') {
    $FileCount++;
    list($checkComplete, $Infected, $FileCount) = virus_check($folder, $Defs, $DefsFile, $DefData, $Debug, $Verbose, $MemoryLimit, $ChunkSize); }
  $ScanComplete = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $files = $file = $entry = $folder = NULL;
  unset($files, $file, $entry, $folder); 
  return array($ScanComplete, $DirCount, $FileCount, $Infected); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// Hash & check files/folders for viruses against static virus definitions.
function virus_check($file, $Defs, $DefsFile, $DefData, $Debug, $Verbose, $MemoryLimit, $ChunkSize) {
  // / Set variables.
  global $Infected, $DefsFileName, $FileCount;
  $CheckComplete = FALSE;
  if ($file !== $DefsFileName) {
    if (file_exists($file)) {
      $FileCount++;
      processOutput('Scanning file:  '.$file, FALSE, 0, TRUE, FALSE, FALSE);
      $filesize = filesize($file);
      $data1 = hash_file('md5', $file);
      $data2 = hash_file('sha256', $file);
      $data3 = hash_file('sha1', $file);
      // / Scan files larger than the memory limit by breaking them into chunks.
      if ($filesize >= $MemoryLimit && file_exists($file)) { 
        processOutput('Chunking file:  '.$file, FALSE, 0, TRUE, FALSE, FALSE);
        $handle = @fopen($file, "r");
        if ($handle) {
          while (($buffer = fgets($handle, $ChunkSize)) !== FALSE) {
            $data = $buffer;
            processOutput('Scanning chunk.', FALSE, 0, TRUE, FALSE, FALSE);
            foreach ($Defs as $virus) {
              $virus = explode("\t", $virus[0]);
              if (isset($virus[1]) && !is_null($virus[1]) && $virus[1] !== '' && $virus[1] !== ' ') {
                if (strpos(strtolower($data), strtolower($virus[1])) !== FALSE or strpos(strtolower($file), strtolower($virus[1])) !== FALSE) { 
                  // File matches virus defs.
                  processOutput('Infected: '.$file.' ('.$virus[0].', Data Match: '.$virus[1].')', FALSE, 0, TRUE, TRUE, FALSE);
                  $Infected++; } } } }
          if (!feof($handle)) {
            processOutput('Unable to open '.$file.'!', TRUE, 600, TRUE, TRUE, FALSE);
            fclose($handle); } 
          if (isset($virus[2]) && !is_null($virus[2]) && $virus[2] !== '' && $virus[2] !== ' ') {
            if (strpos(strtolower($data1), strtolower($virus[2])) !== FALSE) {
              // File matches virus defs.
              processOutput('Infected: '.$file.' ('.$virus[0].', MD5 Hash Match: '.$virus[2].')', FALSE, 0, TRUE, TRUE, FALSE);
              $Infected++; } }
            if (isset($virus[3]) && !is_null($virus[3]) && $virus[3] !== '' && $virus[3] !== ' ') {
              if (strpos(strtolower($data2), strtolower($virus[3])) !== FALSE) {
                // File matches virus defs.
                processOutput('Infected: '.$file.' ('.$virus[0].', SHA256 Hash Match: '.$virus[3].')', FALSE, 0, TRUE, TRUE, FALSE);
                $Infected++; } } 
            if (isset($virus[4]) && !is_null($virus[4]) && $virus[4] !== '' && $virus[4] !== ' ') {
              if (strpos(strtolower($data3), strtolower($virus[4])) !== FALSE) {
                // File matches virus defs.
                processOutput('Infected: '.$file.' ('.$virus[0].', SHA1 Hash Match: '.$virus[4].')', FALSE, 0, TRUE, TRUE, FALSE);
                $Infected++; } } } }
      // / Scan files smaller than the memory limit by fitting the entire file into memory.
      if ($filesize < $MemoryLimit && file_exists($file)) {
        $data = file_get_contents($file); }
      if ($DefData !== $data2) {
        foreach ($Defs as $virus) {
          $virus = explode("\t", $virus[0]);
          if (isset($virus[1]) && !is_null($virus[1]) && $virus[1] !== '' && $virus[1] !== ' ') {
            if (strpos(strtolower($data), strtolower($virus[1])) !== FALSE or strpos(strtolower($file), strtolower($virus[1])) !== FALSE) {
              // File matches virus defs.
              processOutput('Infected: '.$file.' ('.$virus[0].', Data Match: '.$virus[1].')', FALSE, 0, TRUE, TRUE, FALSE);
              $Infected++; } }
          if (isset($virus[2]) && !is_null($virus[2]) && $virus[2] !== '' && $virus[2] !== ' ') {
            if (strpos(strtolower($data1), strtolower($virus[2])) !== FALSE) {
              // File matches virus defs.
              processOutput('Infected: '.$file.' ('.$virus[0].', MD5 Hash Match: '.$virus[2].')', FALSE, 0, TRUE, TRUE, FALSE);
              $Infected++; } }
            if (isset($virus[3]) && !is_null($virus[3]) && $virus[3] !== '' && $virus[3] !== ' ') {
              if (strpos(strtolower($data2), strtolower($virus[3])) !== FALSE) {
                // File matches virus defs.
                processOutput('Infected: '.$file.' ('.$virus[0].', SHA256 Hash Match: '.$virus[3].')', FALSE, 0, TRUE, TRUE, FALSE);
                $Infected++; } } 
            if (isset($virus[4]) && !is_null($virus[4]) && $virus[4] !== '' && $virus[4] !== ' ') {
              if (strpos(strtolower($data3), strtolower($virus[4])) !== FALSE) {
                // File matches virus defs.
                processOutput('Infected: '.$file.' ('.$virus[0].', SHA1 Hash Match: '.$virus[4].')', FALSE, 0, TRUE, TRUE, FALSE);
                $Infected++; } } } } } }
  $CheckComplete = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $file = $filesize = $data = $buffer = $handle = $virus = $data1 = $data2 = $data3 = NULL;
  unset($file, $filesize, $data, $buffer, $handle, $virus, $data1, $data2, $data3);
  return array($CheckComplete, $Infected, $FileCount); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The main logic of the program.

// / Verify the installation.
list($InstallationVerified, $Version) = verifyInstallation();
if (!$InstallationVerified) die('ERROR!!! ScanCore-1, Cannot verify the ScanCore installation!'.$EOL);

// / Load the configuration file.
list ($ConfigLoaded, $DefsExist, $ConfigFilePath) = loadConfig($Version);
if (!$ConfigLoaded) die('ERROR!!! ScanCore-2, Cannot load the configuration file located at:  '.$ConfigFilePath.$EOL);
if (!$DefsExist) die('ERROR!!! ScanCore-3, Cannot verify the definitions file located at:  '.$DefsFile.$EOL);

// / Create required directories if they don't already exist.
list($RequiredDirsExist) = createDirs($RequiredDirs);
if (!$ConfigLoaded) die('ERROR!!! ScanCore-4, Cannot create required directories!'.$EOL);

// / Process supplied command-line arguments.
// / Example:  C:\Path-To-PHP-Binary.exe C:\Path-To-ScanCore.php C:\Path-To-Scan\ -m [integer] -c [integer] -v -d
list($ArgsParsed, $PerformScan, $PathToScan, $MemoryLimit, $ChunkSize, $Debug, $Verbose, $Recursion, $ReportFile, $MaxLogSize, $PerformDefUpdate, $PerformAppUpdate) = parseArgs($argv);
if (!$ArgsParsed) processOutput('Cannot verify supplied arguments!', TRUE, 5, TRUE, TRUE, TRUE);
else processOutput('Verified supplied arguments.', FALSE, 0, TRUE, FALSE, FALSE);

// / Perform a definition update, when required.
if ($PerformDefUpdate) {
  list($UpdateDefininitionsComplete, $UpdateDefinitionsErrors) = updateDefinitions();
  if (!$UpdateDefininitionsComplete) processOutput('Cannot install definition update!', TRUE, 6, TRUE, TRUE, TRUE); 
  else processOutput('Installed definition update.', FALSE, 0, TRUE, TRUE, FALSE); }

// / Perform an application update, when required.
if ($PerformAppUpdate) {
  list($UpdateApplicationComplete, $UpdateApplicationErrors) = updateApplication();
  if (!$UpdateApplicationComplete) processOutput('Cannot install application update!', TRUE, 7, TRUE, TRUE, TRUE); 
  else processOutput('Installed application update.', FALSE, 0, TRUE, TRUE, TRUE); }

// / Perform scanning operations, when required
if ($PerformScan) {
  // / Load the virus definitions into memory and calculate it's hash (to avoid detecting our own definitions as an infection).
  list($DefsLoaded, $Defs, $DefData) = load_defs($DefsFile);
  if (!$DefsLoaded) processOutput('Cannot load definitions!', TRUE, 8, TRUE, TRUE, TRUE);
  else processOutput('Loaded definitions.', FALSE, 0, TRUE, FALSE, FALSE);

  // / Start the scanner!
  list($ScanComplete, $DirCount, $FileCount, $Infected) = file_scan($PathToScan, $Defs, $DefsFile, $DefData, $Debug, $Verbose, $MemoryLimit, $ChunkSize, $Recursion);
  if (!$ScanComplete) processOutput('Cannot not complete requested scan!', TRUE, 9, TRUE, TRUE, TRUE);
  else processOutput('Scanned '.$FileCount.' files in '.$DirCount.' folders and found '.$Infected.' potentially infected items.', FALSE, 0, TRUE, TRUE, TRUE); }
// / -----------------------------------------------------------------------------------