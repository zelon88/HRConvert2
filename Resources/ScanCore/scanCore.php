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
// / This file contains the core logic of the ScanCore application.
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
// / The following code sets global variables for the session.
function verifySCInstallation() {
  // / Set variables.
  global $SCVersions, $SCDate, $SCTime, $SCSEP, $SCReportFile, $SCLogfile, $SCConfigFile, $SCRequiredDirs, $SCVersion, $SCVersions, $argv, $SCEOL, $SCMaxLogSize, $SCDebug, $SCVerbose, $DefaultMemoryLimit, $DefaultChunkSize, $DefaultMaxLogSize, $SCReportFileName, $SCConfigVersion, $DefsFile, $DefsFileName, $FileCount;
  // / Application related variables.
  $SCInstallationVerified = $SCConfigLoaded = $SCReportFile = $SCLogfile = $SCRequiredDirs = FALSE;
  $SCEOL = PHP_EOL;
  $SCRequiredDirs = array();
  $SCSEP = DIRECTORY_SEPARATOR;
  $SCConfigFile = 'ScanCore_Config.php';
  $SCVersion = 'v1.0';
  $SCVersions = $SCConfigVersion;
  $rp = realpath(dirname(__FILE__));
  $FileCount = 0;
  // / Time related variables.
  $SCDate = date("m_d_y");
  $SCTime = date("F j, Y, g:i a");
  // / Initialize an empty array if no arguments are set.
  if (!isset($argv)) $argv = array();
  // / Load the configuration file (ScanCore_Config.php).
  if (file_exists($rp.$SCSEP.$SCConfigFile)) $SCConfigLoaded = require_once ($rp.$SCSEP.$SCConfigFile);
  // / Check to make sure the configuration file was loaded & the configuration version is compatible with the core.
  if (isset($ScanLoc) && isset($DefsFile) && isset($SCConfigVersion) && $SCConfigVersion === $SCVersion && $SCConfigLoaded) {
    // / Configuration related variables.
    $SCInstallationVerified = TRUE;
    $SCReportFile = $ReportDir.$SCSEP.$SCReportFileName;
    $SCLogfile = $ReportDir.$SCLogFileName;
    $SCRequiredDirs = array($ReportDir);
    $SCMaxLogSize = $DefaultMaxLogSize; 
    $SCDebug = $Debug;
    $SCVerbose = $Verbose;
    $SCMemoryLimit = $DefaultMemoryLimit;
    $SCChunkSize = $DefaultChunkSize; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $rp = NULL;
  unset($rp); 
  return array($SCInstallationVerified, $SCConfigLoaded); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to create required directories when they do not already exist.
function createDirs($SCRequiredDirs) { 
  // / Set variables.
  global $SCTime;
  $SCRequiredDirsExist = TRUE;
  foreach ($SCRequiredDirs as $reqdDir) {
    if (!file_exists($reqdDir)) mkdir($reqdDir);
    if (!file_exists($reqdDir)) $SCRequiredDirsExist = FALSE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $reqdDir = NULL;
  unset($reqdDir); 
  return array($SCRequiredDirsExist); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to add an entry to the logs.
function addLogEntry($entry, $error, $errorNumber) {
  // / Set variables.
  global $SCReportFile, $SCTime, $SCEOL;
  if (!is_numeric($errorNumber)) $errorNumber = 0;
  if ($error === TRUE) $preText = 'ERROR!!! ScanCore-'.$errorNumber.' on '.$SCTime.', ';
  else $preText = $SCTime.', ';
  $SCLogCreated = file_put_contents($SCReportFile, $preText.$entry.$SCEOL, FILE_APPEND);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $preText = $error = $entry = $errorNumber = NULL;
  unset($preText, $error, $entry, $errorNumber); 
  return array($SCLogCreated); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to handle important messages to the console & log file.
function processOutput($txt, $error, $errorNumber, $requiredLog, $requiredConsole, $fatal) {
  global $SCEOL, $SCDebug, $SCVerbose;
  $OutputProcessed = FALSE;
  // / Verify that all inputs are of the correct type.
  if (!is_string($txt)) $txt = '';
  if (!is_bool($error)) $error = FALSE;
  if (!is_int($errorNumber)) $errorNumber = 0;
  if (!is_bool($requiredLog)) $requiredLog = FALSE;
  if (!is_bool($requiredConsole)) $requiredConsole = FALSE;
  // / Log the provided text if $SCDebug variable (-d switch) is set.
  if ($SCDebug or $requiredLog) list ($OutputProcessed) = addLogEntry($txt, $error, $errorNumber);
  // / Output the summary text to the terminal if the $SCVerbose (-v switch) variable is set.
  if ($SCVerbose or $requiredConsole) echo $txt.$SCEOL;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $txt = $error = $errorNumber = $requiredLog = $requiredConsole = NULL;
  unset($txt, $error, $errorNumber, $requiredLog, $requiredConsole); 
  // / Stop execution as needed.
  if ($fatal) die();
  return array($OutputProcessed); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to parse supplied command-line arguments.
function parseArgs($argv) {
  // / Set variables. 
  // / Most of these should already be set to the values contained in the configuration file.
  global $SCArgsParsed, $SCReportFile, $SCLogfile, $SCMaxLogSize, $SCDebug, $SCVerbose, $SCEOL, $SCChunkSize, $SCMemoryLimit, $DefaultMemoryLimit, $DefaultChunkSize;
  $SCRecursion = FALSE;
  $SCArgsParsed = $SCPathToScan = FALSE;
  foreach ($argv as $key => $arg) {
    $arg = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $arg));
    if ($arg == '-memorylimit' or $arg == '-m') $SCMemoryLimit = $argv[$key + 1];
    if ($arg == '-chunksize' or $arg == '-c') $SCChunkSize = $argv[$key + 1];
    if ($arg == '-debug' or $arg == '-d') $SCDebug = TRUE;
    if ($arg == '-verbose' or $arg == '-v') $SCVerbose = TRUE;
    if ($arg == '-recursion' or $arg == '-r') $SCRecursion = TRUE;
    if ($arg == '-norecursion' or $arg == '-nr') $SCRecursion = FALSE;
    if ($arg == '-reportfile' or $arg == '-rf') $SCReportFile = $argv[$key + 1];
    if ($arg == '-logfile' or $arg == '-lf') $SCLogfile = $argv[$key + 1];
    if ($arg == '-maxlogsize' or $arg == '-ml') $SCMaxLogSize = $argv[$key + 1]; }
  // / Detect if a file path to scan was specified.
  if (!isset($argv[1])) processOutput('There were no arguments set!', TRUE, 100, TRUE, TRUE, FALSE);
  else $SCPathToScan = $argv[1];
  // / Detect if the MemoryLimit and ChunkSize variables are valid.
  if (!is_numeric($SCMemoryLimit) or !is_numeric($SCChunkSize)) { 
    processOutput('Either the chunkSize argument or the memoryLimit argument is invalid. Attempting to use default values.', TRUE, 200, TRUE, TRUE, FALSE);
    $SCMemoryLimit = $DefaultMemoryLimit;
    $SCChunkSize = $DefaultChunkSize; }
  if (!file_exists($argv[1])) processOutput('The specified file was not found! The first argument must be a valid file or directory path!', TRUE, 300, TRUE, TRUE, FALSE);
  else {
    $SCArgsParsed = TRUE;
    processOutput('Starting ScanCore!', FALSE, 0, TRUE, FALSE, FALSE); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $key = $arg = NULL;
  unset($key, $arg); 
  return array($SCArgsParsed, $SCPathToScan, $SCMemoryLimit, $SCChunkSize, $SCDebug, $SCVerbose, $SCRecursion, $SCReportFile, $SCLogfile, $SCMaxLogSize); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// Hunts files/folders recursively for scannable items.
function file_scan($folder, $Defs, $DefsFile, $DefData, $SCDebug, $SCVerbose, $SCMemoryLimit, $SCChunkSize, $SCRecursion) {
  // / Set variables.
  global $SCSEP, $SCEOL, $FileCount;
  $ScanComplete = FALSE;
  $DirCount = 1;
  $Infected = 0;
  if (is_dir($folder)) {
    $files = scandir($folder);
    foreach ($files as $file) {
      if ($file === '' or $file === '.' or $file === '..') continue;
      $entry = str_replace($SCSEP.$SCSEP, $SCSEP, $folder.$SCSEP.$file);
      if (!is_dir($entry)) list($checkComplete, $Infected) = virus_check($entry, $Defs, $DefsFile, $DefData, $SCDebug, $SCVerbose, $SCMemoryLimit, $SCChunkSize);
      else if (is_dir($entry) && $SCRecursion) {
        processOutput('Scanning folder "'.$entry.'" ... ', FALSE, 0, TRUE, TRUE, FALSE);
        $DirCount++; 
        list ($scanComplete, $DirCount, $FileCount, $Infected) = file_scan($entry, $Defs, $DefsFile, $DefData, $SCDebug, $SCVerbose, $SCMemoryLimit, $SCChunkSize, $SCRecursion); 
        $entry = ''; } } }
  if (!is_dir($folder) && $folder !== '.' && $folder !== '..') {
    $FileCount++;
    list($checkComplete, $Infected) = virus_check($folder, $Defs, $DefsFile, $DefData, $SCDebug, $SCVerbose, $SCMemoryLimit, $SCChunkSize); }
  $ScanComplete = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $files = $file = $entry = $folder = NULL;
  unset($files, $file, $entry, $folder); 
  return array($ScanComplete, $DirCount, $FileCount, $Infected); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// Reads tab-delimited defs file. Also hashes the file to avoid self-detection.
function load_defs($DefsFile) {
  // / Set variables.
  global $SCEOL, $SCDebug, $SCVerbose;
  $SCDefsLoaded = $Defs = $DefData = FALSE;
  if (!file_exists($DefsFile)) processOutput('Could not load the virus definition file located at "'.$DefsFile.'"! File either does not exist or cannot be read!', TRUE, 600, TRUE, TRUE, FALSE);
  else { 
    $Defs = file($DefsFile);
    $DefData = hash_file('sha256', $DefsFile);
    $counter = 0;
    $counttop = sizeof($Defs);
    while ($counter < $counttop) {
      $Defs[$counter] = explode('  ', $Defs[$counter]);
      $counter++; }
    processOutput('Loaded '.sizeof($Defs).' virus definitions.', FALSE, 0, FALSE, FALSE, FALSE);
    $SCDefsLoaded = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $counter = $counttop = NULL;
  unset($counter, $counttop); 
  return array($SCDefsLoaded, $Defs, $DefData); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// Hashes and checks files/folders for viruses against static virus defs.
function virus_check($file, $Defs, $DefsFile, $DefData, $SCDebug, $SCVerbose, $SCMemoryLimit, $SCChunkSize) {
  // / Set variables.
  global $Infected, $DefsFileName, $SCEOL;
  $CheckComplete = FALSE;
  if ($file !== $DefsFileName) {
    if (file_exists($file)) {
      processOutput('Scanning file ... ', FALSE, 0, TRUE, TRUE, FALSE);
      $filesize = filesize($file);
      $data1 = hash_file('md5', $file);
      $data2 = hash_file('sha256', $file);
      $data3 = hash_file('sha1', $file);
      // / Scan files larger than the memory limit by breaking them into chunks.
      if ($filesize >= $SCMemoryLimit && file_exists($file)) { 
        processOutput('Chunking file ... ', FALSE, 0, FALSE, FALSE, FALSE);
        $handle = @fopen($file, "r");
        if ($handle) {
          while (($buffer = fgets($handle, $SCChunkSize)) !== FALSE) {
            $data = $buffer;
            processOutput('Scanning chunk ... ', FALSE, 0, FALSE, FALSE, FALSE);
            foreach ($Defs as $virus) {
              $virus = explode("\t", $virus[0]);
              if (isset($virus[1]) && !is_null($virus[1]) && $virus[1] !== '' && $virus[1] !== ' ') {
                if (strpos(strtolower($data), strtolower($virus[1])) !== FALSE or strpos(strtolower($file), strtolower($virus[1])) !== FALSE) { 
                  // File matches virus defs.
                  processOutput('Infected: '.$file.' ('.$virus[0].', Data Match: '.$virus[1].')', FALSE, 0, TRUE, TRUE, FALSE);
                  $Infected++; } } } }
          if (!feof($handle)) {
            processOutput('Unable to open "'.$file.'"!', TRUE, 800, TRUE, TRUE, FALSE);
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
      if ($filesize < $SCMemoryLimit && file_exists($file)) {
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
  return array($CheckComplete, $Infected); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The main logic of the program.

// / Verify the installation.
list($SCInstallationVerified, $SCConfigLoaded) = verifySCInstallation();
if (!$SCInstallationVerified or !$SCConfigLoaded) die('ERROR!!! ScanCore-003, Cannot verify the ScanCore installation!'.PHP_EOL);

// / Create required directories if they don't already exist.
list($SCRequiredDirsExist) = createDirs($SCRequiredDirs);
if (!$SCInstallationVerified or !$SCConfigLoaded) die('ERROR!!! ScanCore-004, Cannot create required directories!'.PHP_EOL);

// / Process supplied command-line arguments.
// / Example:  C:\Path-To-PHP-Binary.exe C:\Path-To-ScanCore.php C:\Path-To-Scan\ -m [integer] -c [integer] -v -d
list($SCArgsParsed, $SCPathToScan, $SCMemoryLimit, $SCChunkSize, $SCDebug, $SCVerbose, $SCRecursion, $SCReportFile, $SCLogfile, $SCMaxLogSize) = parseArgs($argv);
if (!$SCArgsParsed) processOutput('Cannot verify supplied arguments!', TRUE, 005, TRUE, TRUE, TRUE);
else processOutput('Verified supplied arguments.', FALSE, 0, FALSE, FALSE, FALSE);

// / Load the virus definitions into memory and calculate it's hash (to avoid detecting our own definitions as an infection).
list($SCDefsLoaded, $Defs, $DefData) = load_defs($DefsFile);
if (!$SCDefsLoaded) processOutput('Cannot load virus definitions!', TRUE, 006, TRUE, TRUE, TRUE);
else processOutput('Loaded virus definitions.', FALSE, 0, FALSE, FALSE, FALSE);

// / Start the scanner!
list($ScanComplete, $DirCount, $FileCount, $Infected) = file_scan($SCPathToScan, $Defs, $DefsFile, $DefData, $SCDebug, $SCVerbose, $SCMemoryLimit, $SCChunkSize, $SCRecursion);
if (!$ScanComplete) processOutput('Could not complete requested scan!', TRUE, 007, TRUE, TRUE, TRUE);
else processOutput('Scanned '.$FileCount.' files in '.$DirCount.' folders and found '.$Infected.' potentially infected items.', FALSE, 0, TRUE, FALSE, TRUE);
// / -----------------------------------------------------------------------------------