<?php
// / -----------------------------------------------------------------------------------
// / APPLICATION INFORMATION ...
// /   HR-AV, Copyright on 4/19/2022 by Justin Grimes, www.github.com/zelon88 
// /   This file is a heavily modified version of PHP-AV maintained by Justin Grimes.
// /   This file was designed to function as part of the HR-AV anti-virus application.
// /   This file may not work properly outside of it's intended environment or use-case.
// /   This file should be used outside it's intended application only during development.
// /   Serious data loss or filesystem damage may result! Execute this file at your own risk!
// / 
// / LICENSE INFORMATION ...
// /   This project is protected by the GNU GPLv3 Open-Source license.
// / 
// / DEPENDENCY REQUIREMENTS ... 
// /   This application requires Windows 7 (or later) with PHP 7.0 (or later).
// / 
// / VALID SWITCHES / ARGUMENTS / USAGE ...
// /   Quick Start Example:
// /    C:\Path-To-PHP-Binary.exe C:\Path-To-ScanCore.php C:\Path-To-Scan\ -m [integer] -c [integer] -v -d
// / 
// /   Start by opening a command-prompt.
// /   Type the absolute path to a portable PHP 7.0+ binary. Don't press enter just yet.
// /   Now type the absolute path to this PHP file as the only argument for the PHP binary.
// /   Everything after the path to this script will be passed to this file as an argument.
// /   The first Argument Must be a valid absolute path to the file or folder being scanned.
// /   Optional arguments can be specified after the scan path. Separate them with spaces.
// /   
// /   Optional Arguments Include:
// /     Force recursion:                        -recursion
// /                                             -r
// / 
// /     Force no recursion:                     -norecursion
// /                                             -nr
// / 
// /     Specify memory limit (in bytes):        -memorylimit ####
// /                                             -m ####
// / 
// /     Specify chunk size (in bytes);          -chunksize ####
// /                                             -c ####
// / 
// /     Enable "debug" mode (more logging):     -debug
// /                                             -d
// / 
// /     Enable "verbose" mode (more console):   -verbose
// /                                             -v
// / 
// /     Force a specific log file:              -logfile /path/to/file
// /                                             -lf path/to/file
// / 
// /     Force a specific report file:           -reportfile /path/to/file
// /                                             -rf path/to/file
// / 
// /     Force maximum log size (in bytes):      -maxlogsize ###
// /                                             -ml ###
// / 
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code will load required HR-AV files.
$rp = realpath(dirname(__FILE__));
if (!file_exists($rp.DIRECTORY_SEPARATOR.'ScanCore_Config.php')) die ('ERROR!!! ScanCore-0, Cannot process the HR-AV ScanCore Configuration file (config.php)!'.PHP_EOL); 
else require_once ($rp.DIRECTORY_SEPARATOR.'ScanCore_Config.php');
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code sets the global variables for the session.
  // / Application related variables.
  $scanCoreVersion = 'v0.8';
  $Versions = 'PHP-AV App v4.0 | Virus Definition v4.9, 4/10/2019';
  $encType = 'ripemd160';
  $dirCount = 1;
  $fileCount = $infected = 0;
  // / Time related variables.
  $Date = date("m_d_y");
  $Time = date("F j, Y, g:i a"); 
  // / Directory related variables.
  $ReportFile = $ReportDir.$ReportFileName;
  $logfile = $ReportDir.$logfilename;
  $RequiredDirs = array($ReportDir, $LogsDir);
  // / Unset unneeded variables for security purposes.
  $encType = $RandomNumber = $SesHash = $SesHash2 = $SesHash3 = $Salts1 = $Salts2 = $Salts3 = $Salts4 = $Salts5 = $Salts6 = NULL;
  unset($encType, $RandomNumber, $SesHash, $SesHash2, $SesHash3, $Salts1, $Salts2, $Salts3, $Salts4, $Salts5, $Salts6);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to create required directories when they do not already exist.
function createDirs($RequiredDirs) { 
  global $Time;
  foreach ($RequiredDirs as $reqdDir) {
    if (!file_exists($reqdDir)) mkdir($reqdDir);
    if (!file_exists($reqdDir)) die('ERROR!!! ScanCore-10 on '.$Time.', Cannot create required directory "'.$reqdDir.'"!'.PHP_EOL); }
  return TRUE; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to add an entry to the logs.
function addLogEntry($entry, $error, $errorNumber) {
  global $ReportFile, $Time;
  if (!is_numeric($errorNumber)) $errorNumber = 0;
  if ($error === TRUE) $preText = 'ERROR!!! ScanCore-'.$errorNumber.' on '.$Time.', ';
  else $preText = $Time.', ';
  return(file_put_contents($ReportFile, $preText.$entry.PHP_EOL, FILE_APPEND)); } 
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to parse supplied command-line arguments.
function parseArgs($argv) { 
  global $ReportFile, $logfile, $MaxLogSize, $debug, $verbose;
  $memoryLimit = 4000000;
  $chunkSize = 1000000; 
  $recursion = TRUE;
  $debug = $pathToScan = $verbose = FALSE;
  $reportFile = $ReportFile;
  $maxLogSize = $MaxLogSize;
  foreach ($argv as $key => $arg) {
    trim($arg);
    $arg = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $arg));
    if ($arg == '-memorylimit' or $arg == '-m') $memoryLimit = $argv[$key + 1];
    if ($arg == '-chunksize' or $arg == '-c') $chunkSize = $argv[$key + 1]; 
    if ($arg == '-debug' or $arg == '-d') $debug = TRUE; 
    if ($arg == '-verbose' or $arg == '-v') $verbose = TRUE;
    if ($arg == '-recursion' or $arg == '-r') $recursion = TRUE; 
    if ($arg == '-norecursion' or $arg == '-nr') $recursion = FALSE;
    if ($arg == '-reportfile' or $arg == '-rf') $reportFile = $argv[$key + 1];
    if ($arg == '-logfile' or $arg == '-lf') $logfile = $argv[$key + 1]; 
    if ($arg == '-maxlogsize' or $arg == '-ml') $maxLogSize = $argv[$key + 1]; }
  if (!isset($argv[1])) {
    $txt = 'There were no arguments set!'; 
    addLogEntry($txt, TRUE, 100);
    die ($txt.PHP_EOL); }
  if (!file_exists($argv[1])) { 
    $txt = 'The specified file was not found! The first argument must be a valid file or directory path!'; 
    addLogEntry($txt, TRUE, 200);
    die ($txt.PHP_EOL); }
  $pathToScan = $argv[1];
  if (!is_numeric($memoryLimit) or !is_numeric($chunkSize)) { 
    $txt = 'Either the chunkSize argument or the memoryLimit argument is invalid. Substituting default values.';
    addLogEntry($txt, TRUE, 300); 
    echo $txt.PHP_EOL;
    $memoryLimit = $defaultMemoryLimit; 
    $chunkSize = $defaultChunkSize; }
  return(array($pathToScan, $memoryLimit, $chunkSize, $debug, $verbose, $recursion, $reportFile, $logfile, $maxLogSize)); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// Hunts files/folders recursively for scannable items.
function file_scan($folder, $defs, $DefsFile, $defData, $debug, $verbose, $memoryLimit, $chunkSize, $recursion) {
  global $fileCount, $dirCount, $infected;
  if (is_dir($folder)) {
    $files = scandir($folder);
    foreach ($files as $file) {
      if ($file === '' or $file === '.' or $file === '..') continue;
      $entry = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $folder.DIRECTORY_SEPARATOR.$file);
      if (!is_dir($entry)) list($fileCount, $infected) = virus_check($entry, $defs, $DefsFile, $defData, $debug, $verbose, $memoryLimit, $chunkSize);
      else if (is_dir($entry) && $recursion) {
        if ($debug) { 
          $txt = 'Scanning folder "'.$entry.'" ... ';
          addLogEntry($txt, FALSE, 0); }
        if ($verbose) { 
          $txt = 'Scanning folder "'.$entry.'" ... ';
          echo $txt.PHP_EOL; } }
        $dirCount++; 
        list ($dirCount1, $fileCount1, $infected1) = file_scan($entry, $defs, $DefsFile, $defData, $debug, $verbose, $memoryLimit, $chunkSize, $recursion); } }
  if (!is_dir($folder) && $folder !== '.' && $folder !== '..') {
    $fileCount++;
    list($fileCount1, $infected1) = virus_check($folder, $defs, $DefsFile, $defData, $debug, $verbose, $memoryLimit, $chunkSize); }
  return array($dirCount, $fileCount, $infected); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// Reads tab-delimited defs file. Also hashes the file to avoid self-detection.
function load_defs($file, $debug, $verbose) {
  if (!file_exists($file)) {
    $defs = $defData = FALSE;
    $txt = 'Could not load the virus definition file located at "'.$file.'"! File either does not exist or cannot be read!';
    addLogEntry($txt, TRUE, 600); 
    if ($verbose) echo $txt.PHP_EOL;
    die(); }
  else { 
    $defs = file($file);
    $counter = 0;
    $counttop = sizeof($defs);
    $defData = hash_file('sha256', $file);
    while ($counter < $counttop) {
      $defs[$counter] = explode('  ', $defs[$counter]);
      $counter++; }
    if ($debug) { 
      $txt = 'Loaded '.sizeof($defs).' virus definitions.';
      addLogEntry($txt, FALSE, 0); } }
    if ($verbose) { 
      $txt = 'Loaded '.sizeof($defs).' virus definitions.'; 
      echo $txt.PHP_EOL; } 
  return (array($defs, $defData)); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// Hashes and checks files/folders for viruses against static virus defs.
function virus_check($file, $defs, $DefsFile, $defData, $debug, $verbose, $memoryLimit, $chunkSize) {
  global $fileCount, $dirCount, $infected, $DefsFileName;
  if ($file !== $DefsFileName) {
    if (file_exists($file)) { 
      $txt = ('Scanning file "'.$file.'".');
      addLogEntry($txt, FALSE, 0);
      if ($verbose) echo $txt.PHP_EOL;
      $filesize = filesize($file);
      $data1 = hash_file('md5', $file);
      $data2 = hash_file('sha256', $file);
      $data3 = hash_file('sha1', $file);
      // / Scan files larger than the memory limit by breaking them into chunks.
      if ($filesize >= $memoryLimit && file_exists($file)) { 
        if ($debug) { 
          $txt = 'Chunking file ... ';
          addLogEntry($txt, FALSE, 0); }
        if ($verbose) {
          $txt = 'Chunking File ... ';
          echo $txt.PHP_EOL; }
        $handle = @fopen($file, "r");
        if ($handle) {
          while (($buffer = fgets($handle, $chunkSize)) !== FALSE) {
            $data = $buffer; 
            if ($debug) { 
              $txt = 'Scanning chunk ... ';
              addLogEntry($txt, FALSE, 0); }
            if ($verbose) { 
              $txt = 'Scanning chunk ... '; 
              echo $txt.PHP_EOL; }
            foreach ($defs as $virus) {
              $virus = explode("\t", $virus[0]);
              if (isset($virus[1]) && !is_null($virus[1]) && $virus[1] !== '' && $virus[1] !== ' ') {
                if (strpos(strtolower($data), strtolower($virus[1])) !== FALSE or strpos(strtolower($file), strtolower($virus[1])) !== FALSE) { 
                  // File matches virus defs.
                  $txt = 'Infected: '.$file.' ('.$virus[0].', Data Match: '.$virus[1].')';
                  addLogEntry($txt, FALSE, 0);
                  if ($verbose) echo $txt.PHP_EOL;
                  $infected++; } } } }
          if (!feof($handle)) {
            $txt = 'Unable to open "'.$file.'"!';
            addLogEntry($txt, TRUE, 800); 
            if ($verbose) echo $txt.PHP_EOL; }
          fclose($handle); } 
          if (isset($virus[2]) && !is_null($virus[2]) && $virus[2] !== '' && $virus[2] !== ' ') {
            if (strpos(strtolower($data1), strtolower($virus[2])) !== FALSE) {
              // File matches virus defs.
              $txt = 'Infected: '.$file.' ('.$virus[0].', MD5 Hash Match: '.$virus[2].')';
              addLogEntry($txt, FALSE, 0);
              if ($verbose) echo $txt.PHP_EOL;
              $infected++; } }
            if (isset($virus[3]) && !is_null($virus[3]) && $virus[3] !== '' && $virus[3] !== ' ') {
              if (strpos(strtolower($data2), strtolower($virus[3])) !== FALSE) {
                // File matches virus defs.
                $txt = 'Infected: '.$file.' ('.$virus[0].', SHA256 Hash Match: '.$virus[3].')';
                addLogEntry($txt, FALSE, 0);
                if ($verbose) echo $txt.PHP_EOL;
                $infected++; } } 
            if (isset($virus[4]) && !is_null($virus[4]) && $virus[4] !== '' && $virus[4] !== ' ') {
              if (strpos(strtolower($data3), strtolower($virus[4])) !== FALSE) {
                // File matches virus defs.
                $txt = 'Infected: '.$file.' ('.$virus[0].', SHA1 Hash Match: '.$virus[4].')';
                addLogEntry($txt, FALSE, 0);
                if ($verbose) echo $txt.PHP_EOL;
                $infected++; } } } 
      // / Scan files smaller than the memory limit by fitting the entire file into memory.
      if ($filesize < $memoryLimit && file_exists($file)) {
        $data = file_get_contents($file); }
      if ($defData !== $data2) {
        foreach ($defs as $virus) {
          $virus = explode("\t", $virus[0]);
          if (isset($virus[1]) && !is_null($virus[1]) && $virus[1] !== '' && $virus[1] !== ' ') {
            if (strpos(strtolower($data), strtolower($virus[1])) !== FALSE or strpos(strtolower($file), strtolower($virus[1])) !== FALSE) {
             // File matches virus defs.
              $txt = 'Infected: '.$file.' ('.$virus[0].', Data Match: '.$virus[1].')';
              addLogEntry($txt, FALSE, 0);
              if ($verbose) echo $txt.PHP_EOL;
              $infected++; } }
          if (isset($virus[2]) && !is_null($virus[2]) && $virus[2] !== '' && $virus[2] !== ' ') {
            if (strpos(strtolower($data1), strtolower($virus[2])) !== FALSE) {
                // File matches virus defs.
              $txt = 'Infected: '.$file.' ('.$virus[0].', MD5 Hash Match: '.$virus[2].')';
              addLogEntry($txt, FALSE, 0);
              if ($verbose) echo $txt.PHP_EOL;
              $infected++; } }
            if (isset($virus[3]) && !is_null($virus[3]) && $virus[3] !== '' && $virus[3] !== ' ') {
              if (strpos(strtolower($data2), strtolower($virus[3])) !== FALSE) {
                // File matches virus defs.
                $txt = 'Infected: '.$file.' ('.$virus[0].', SHA256 Hash Match: '.$virus[3].')';
                addLogEntry($txt, FALSE, 0);
                if ($verbose) echo $txt.PHP_EOL;
                $infected++; } } 
            if (isset($virus[4]) && !is_null($virus[4]) && $virus[4] !== '' && $virus[4] !== ' ') {
              if (strpos(strtolower($data3), strtolower($virus[4])) !== FALSE) {
                // File matches virus defs.
                $txt = 'Infected: '.$file.' ('.$virus[0].', SHA1 Hash Match: '.$virus[4].')';
                addLogEntry($txt, FALSE, 0);
                if ($verbose) echo $txt.PHP_EOL;
                $infected++; } } } } } }
  return (array($fileCount, $infected)); } 
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The main logic of the program.

// / Create required directories if they don't already exist.
createDirs($RequiredDirs);
// / Process supplied command-line arguments.
  // / C:\Path-To-PHP-Binary.exe C:\Path-To-ScanCore.php C:\Path-To-Scan\ -m [integer] -c [integer] -v -d
list($pathToScan, $memoryLimit, $chunkSize, $debug, $verbose, $recursion, $ReportFile, $logfile, $MaxLogSize) = parseArgs($argv);
// / Set some welcome text. 
  // / Log the welcome text if $debug variable (-d switch) is set.
  // / Output the welcome text to the terminal if the $verbose (-v switch) variable is set.
$txt = 'Starting ScanCore!';
if ($debug) addLogEntry($txt, FALSE, 0);
if ($verbose) echo PHP_EOL.$txt.PHP_EOL;
// / Load the virus definitions into memory and calculate it's hash (to avoid detecting our own definitions as an infection).
list($defs, $defData) = load_defs($DefsFile, $debug, $verbose);
// / Start the scanner!
list($dirCount, $fileCount, $infected) = file_scan($pathToScan, $defs, $DefsFile, $defData, $debug, $verbose, $memoryLimit, $chunkSize, $recursion);
// / Copy the report file to the Logs directory for safe permanent keeping.
@copy($ReportFile, $logfile);
// / Set some summary text. 
  // / Log the summart text if $debug variable (-d switch) is set.
  // / Output the summary text to the terminal if the $verbose (-v switch) variable is set.
$txt = 'Scanned '.$fileCount.' files in '.$dirCount.' folders and found '.$infected.' potentially infected items.';
if ($debug) addLogEntry($txt, FALSE, 0);
if ($verbose) echo $txt.PHP_EOL; 
// / -----------------------------------------------------------------------------------
