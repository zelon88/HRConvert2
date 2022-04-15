<!DOCTYPE HTML>
<?php
// / -----------------------------------------------------------------------------------
// / APPLICATION INFORMATION ...
// / HRConvert2, Copyright on 4/11/2022 by Justin Grimes, www.github.com/zelon88
// / 
// / LICENSE INFORMATION ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / 
// / APPLICATION DESCRIPTION ...
// / This application is designed to provide a web-interface for converting file formats
// / on a server for users of any web browser without authentication. 
// / 
// / HARDWARE REQUIREMENTS ... 
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// / 
// / DEPENDENCY REQUIREMENTS ... 
// / This application requires Debian Linux (w/3rd Party audio license), 
// / Apache 2.4, PHP 7+, LibreOffice, Unoconv, ClamAV, Tesseract, Rar, Unrar, Unzip, 
// / 7zipper, FFMPEG, PDF2TXT, Dia and ImageMagick.
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code will load required HRConvert2 files.
if (!file_exists(realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR.'config.php')) die ('ERROR!!! HRConvert226, Cannot process the HRConvert2 Configuration file (config.php)!'.PHP_EOL.'<br />'); 
else require_once (realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR.'config.php'); 
if (!file_exists(realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR.'sanitizeCore.php')) die ('ERROR!!! HRConvert233, Cannot process the HRConvert2 Sanitize Core file (sanitizeCore.php)!'.PHP_EOL.'<br />'); 
else require_once (realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR.'sanitizeCore.php'); 
if (!file_exists(realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR.'styleCore.php')) die ('ERROR!!! HRConvert231, Cannot process the HRConvert2 Style Core file (styleCore.php)!'.PHP_EOL.'<br />'); 
else require_once (realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR.'styleCore.php'); 
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The folloiwing code attempts to detect the users IP so it can be used as a unique identifier for the session.
  // / If it is not unique we will adjust it later.
if (!empty($_SERVER['HTTP_CLIENT_IP'])) $IP = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_SERVER['HTTP_CLIENT_IP']), ENT_QUOTES, 'UTF-8'); 
elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) $IP = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_SERVER['HTTP_X_FORWARDED_FOR']), ENT_QUOTES, 'UTF-8'); 
else $IP = htmlentities(str_replace('..', '', str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_SERVER['REMOTE_ADDR'])), ENT_QUOTES, 'UTF-8'); 
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code sets an echo variable that adjusts printed URL's to https when SSL is enabled.
if (!empty($_SERVER['HTTPS']) && $_SERVER['SERVER_PORT'] == 443) $URLEcho = 's'; 
else $URLEcho = '';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code sets or validates a Token so it can be used as a unique identifier for the session.
if (!isset($Token1) or strlen($Token1) < 19) $Token1 = hash('ripemd160', rand(0, 1000000000).rand(0, 1000000000)); 
if (isset($Token2)) if ($Token2 !== hash('ripemd160', $Token1.$Salts1.$Salts2.$Salts3.$Salts4.$Salts5.$Salts6)) die('ERROR!!! HRConvert263, Authentication error!!!');
if (!isset($Token2)) $Token2 = hash('ripemd160', $Token1.$Salts1.$Salts2.$Salts3.$Salts4.$Salts5.$Salts6); 
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code sets the styles to use for for the session.
$ButtonCode = $defaultButtonCode;
if (isset($ButtonStyle)) { 
  if (strtolower($ButtonStyle) === 'green') $ButtonCode = $greenButtonCode;
  if (strtolower($ButtonStyle) === 'blue') $ButtonCode = $blueButtonCode;
  if (strtolower($ButtonStyle) === 'red') $ButtonCode = $redButtonCode; 
  if (strtolower($ButtonStyle) === 'grey') $ButtonCode = $defaultButtonCode; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code sets the language to use for the session.
$LanguageToUse = 'en';
$SupportedLanguages = array('en', 'fr', 'es', 'zh', 'hi', 'ar', 'ru', 'uk', 'bn', 'de', 'ko', 'it', 'pt');
if (isset($_GET['language'])) $_GET['language'] = str_replace('..', '', str_replace(str_split('[](){};:$!#^&%@>*<'), '', $_GET['language']));
if (isset($DefaultLanguage)) if (in_array($DefaultLanguage, $SupportedLanguages)) $LanguageToUse = $DefaultLanguage;
if (isset($AllowUserSelectableLanguage)) { 
  if ($AllowUserSelectableLanguage) if (isset($_GET['language'])) if (in_array($_GET['language'], $SupportedLanguages)) $LanguageToUse = $_GET['language'];
  if (!$AllowUserSelectableLanguage) $LanguageToUse = $DefaultLanguage; }
$_GET['language'] = $LanguageToUse;
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code sets the global variables for the session.
$HRConvertVersion = 'v2.7.5';
$Date = date("m_d_y");
$Time = date("F j, Y, g:i a");
$CoreLoaded = TRUE;
$JanitorFile = 'janitor.php';
$JanitorDeleteIndex = FALSE;
$Current_URL = "http$URLEcho://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$SesHash = substr(hash('ripemd160', $Date.$Salts1.$Salts2.$Salts3.$Salts4.$Salts5.$Salts6), -12);
$SesHash2 = substr(hash('ripemd160', $SesHash.$Token1.$Date.$IP.$Salts1.$Salts2.$Salts3.$Salts4.$Salts5.$Salts6), -12);
$SesHash3 = $SesHash.'/'.$SesHash2;
$SesHash4 = hash('ripemd160', $Salts6.$Salts5.$Salts4.$Salts3.$Salts2.$Salts1);
$ConvertDir0 = str_replace('..', '', $ConvertLoc.DIRECTORY_SEPARATOR.$SesHash);
$ConvertDir = str_replace('..', '', $ConvertDir0.DIRECTORY_SEPARATOR.$SesHash2.DIRECTORY_SEPARATOR);
$ConvertTemp = $InstLoc.'/DATA';
$ConvertTempDir0 = str_replace('..', '', $ConvertTemp.DIRECTORY_SEPARATOR.$SesHash);
$ConvertTempDir = str_replace('..', '', $ConvertTempDir0.DIRECTORY_SEPARATOR.$SesHash2.DIRECTORY_SEPARATOR);
$LogInc = '0';
$ConvertGuiCounter1 = 0;
$LogFile = str_replace('..', '', $LogDir.'/HRConvert2_'.$LogInc.'_'.$Date.'_'.$SesHash4.'_'.$SesHash.'.txt');
$ClamLogFile = str_replace('..', '', $LogDir.'/ClamLog_'.$Date.'_'.$SesHash4.'_'.$SesHash.'.txt');
$DefaultLogDir = $InstLoc.'/Logs';
$DefaultLogSize = '1048576';
$DefaultApps = array('.', '..');
$RequiredDirs = array($ConvertDir0, $ConvertDir, $ConvertTemp, $ConvertTempDir0, $ConvertTempDir);
$RequiredIndexes = array($ConvertTemp, $ConvertTempDir0, $ConvertTempDir);
$DangerousFiles = array('js', 'php', 'html', 'css', 'phar');
$Allowed =  array('svg', 'dxf', 'vdx', 'fig', '3ds', 'obj', 'collada', 'off', 'ply', 'stl', 'ptx', 'dxf', 'u3d', 'vrml', 'mov', 'mp4', 'mkv', 'flv', 'ogv', 'wmv', 'mpg', 'mpeg', 'm4v', '3gp', 'flac', 'aac', 'dat', 'cfg', 'txt', 'doc', 'docx', 'rtf' ,'xls', 'xlsx', 'ods', 'odt', 'odt', 'jpg', 'mp3', 'zip', 'rar', 'tar', 'tar.gz', 'tar.bz', 'tar.bZ2', '3gp', 'mkv', 'avi', 'mp4', 'flv', 'mpeg', 'wmv', 'avi', 'aac', 'mp2', 'wma', 'wav', 'ogg', 'jpeg', 'bmp', 'webp', 'png', 'avif', 'crw', 'ico', 'xwd', 'cin', 'dcr', 'dds', 'dib', 'flif', 'gplt', 'nef', 'orf', 'ora', 'sct', 'sfw', 'xcf', 'xwg', 'gif', 'pdf', 'abw', 'iso', 'vhd', 'vdi', 'pages', 'pptx', 'ppt', 'xps', 'potx', 'pot', 'ppa', 'ppa', 'odp');
$AllowedPDFw =  array('txt', 'doc', 'docx', 'rtf' ,'xls', 'xlsx', 'ods', 'odt', 'jpg', 'jpeg', 'bmp', 'webp', 'png', 'gif', 'pdf', 'abw');
$DangerousFiles1 = array('.', '..', 'index.php', 'index.html');
$ArchiveArray = array('zip', 'rar', 'tar', 'bz', 'gz', 'bz2', '7z', 'iso', 'vhd', 'vdi', 'tar.bz2', 'tar.gz');
$DearchiveArray = array('zip', 'rar', 'tar', 'bz', 'gz', 'bz2', '7z', 'iso', 'vhd');
$DocumentArray = array('txt', 'doc', 'docx', 'rtf', 'xls', 'xlsx', 'odt', 'ods', 'pptx', 'ppt', 'xps', 'potx', 'potm', 'pot', 'ppa', 'odp');
$DocArray = array('txt', 'doc', 'docx', 'rtf', 'odt', 'abw');
$SpreadsheetArray = array('csv', 'xls', 'xlsx', 'odt', 'ods');
$PresentationArray = array('ppt', 'xps', 'potx', 'potm', 'pot', 'ppa', 'odp');
$ImageArray = array('jpeg', 'jpg', 'png', 'bmp', 'webp', 'gif', 'avif', 'crw', 'ico', 'cin', 'xwd', 'dcr', 'dds', 'dib', 'flif', 'gplt', 'nef', 'orf', 'ora', 'sct', 'sfw', 'xcf', 'xwg');
$MediaArray = array('mp3', 'aac', 'oog', 'wma', 'mp2', 'flac', 'm4a', 'm4p');
$VideoArray = array('3gp', 'mkv', 'avi', 'mp4', 'flv', 'mpeg', 'wmv', 'mov', 'm4v');
$DrawingArray = array('svg', 'dxf', 'vdx', 'fig');
$ModelArray = array('3ds', 'obj', 'collada', 'off', 'ply', 'stl', 'ptx', 'dxf', 'u3d', 'vrml');
$ConvertArray = array('zip', 'rar', 'tar', 'bz', 'gz', 'bz2', '7z', 'iso', 'vhd', 'vdi', 'tar.bz2', 'tar.gz', 'txt', 'doc', 'docx', 'rtf', 'xls', 'xlsx', 'odt', 'ods', 'pptx', 'ppt', 'xps', 'potx', 'potm', 'pot', 'ppa', 'odp', 'jpeg', 'jpg', 'png', 'bmp', 'webp', 'avif', 'crw', 'ico', 'cin', 'xwd', 'dcr', 'dds', 'dib', 'flif', 'gplt', 'nef', 'orf', 'ora', 'sct', 'sfw', 'xcf', 'xwg', 'gif', 'pdf', 'abw', 'mp3', 'mp4', 'mov', 'aac', 'oog', 'wma', 'mp2', 'flac', 'm4a', '3gp', 'mkv', 'avi', 'mp4', 'flv', 'mpeg', 'wmv', 'svg', 'dxf', 'vdx', 'fig', '3ds', 'obj', 'collada', 'off', 'ply', 'stl', 'ptx', 'dxf', 'u3d', 'vrml', 'm4v', 'm4p');
$PDFWorkArr = array('pdf', 'jpg', 'jpeg', 'png', 'bmp', 'webp', 'gif');
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / GUI specific resources.
function getFiles($pathToFiles) { 
  global $DangerousFiles, $DangerousFiles1;
  $Files = array();
  $dirtyFileArr = scandir($pathToFiles);
  foreach ($dirtyFileArr as $dirtyFile) {
    $dirtyExt = pathinfo($pathToFiles.DIRECTORY_SEPARATOR.$dirtyFile, PATHINFO_EXTENSION);
    if (in_array(strtolower($dirtyExt), $DangerousFiles) or in_array(strtolower($dirtyFile), $DangerousFiles1) or is_dir($pathToFiles.DIRECTORY_SEPARATOR.$dirtyFile)) continue;
    array_push($Files, $dirtyFile); }
  return ($Files); }
function getExtension($pathToFile) { 
  return pathinfo($pathToFile, PATHINFO_EXTENSION); }
function getFilesize($File) { 
  $Size = @filesize($File);
  if ($Size < 1024) $Size=$Size." Bytes"; 
  elseif (($Size < 1048576) && ($Size > 1023)) $Size = round($Size / 1024, 1)." KB";
  elseif (($Size < 1073741824) && ($Size > 1048575)) $Size = round($Size / 1048576, 1)." MB";
  else ($Size = round($Size/1073741824, 1)." GB");
  return ($Size); }
function symlinkmtime($symlinkPath) { 
  $stat = @lstat($symlinkPath);
  return isset($stat['mtime']) ? $stat['mtime'] : null; }
function fileTime($filePath) {
  if (file_exists($filePath)) {
    $stat = @filemtime($filePath);
    return ($stat); } }
function is_dir_empty($dir) { 
  if (is_dir($dir)) { 
    $contents = scandir($dir);
    foreach ($contents as $content) if ($content == '.' or $content == '..') return FALSE; }
  return TRUE; }
function cleanFiles($path) { 
  global $ConvertLoc, $ConvertTemp, $InstLoc, $DefaultApps;
  if (is_dir($path)) { 
    $i = scandir($path);
    foreach ($i as $f) { 
      if (is_file($path.DIRECTORY_SEPARATOR.$f) && !in_array(basename($path.DIRECTORY_SEPARATOR.$f), $DefaultApps)) @unlink($path.DIRECTORY_SEPARATOR.$f);  
      if (is_dir($path.DIRECTORY_SEPARATOR.$f) && !in_array(basename($path.DIRECTORY_SEPARATOR.$f), $DefaultApps) && is_dir_empty($path)) @rmdir($path.DIRECTORY_SEPARATOR.$f);
      if (is_dir($path.DIRECTORY_SEPARATOR.$f) && !in_array(basename($path.DIRECTORY_SEPARATOR.$f), $DefaultApps) && !is_dir_empty($path)) cleanFiles($path.DIRECTORY_SEPARATOR.$f); } 
    if ($path !== $ConvertLoc && $path !== $ConvertTemp) @rmdir($path); } }

// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code creates a logfile if one does not exist.
if (!is_numeric($MaxLogSize)) $MaxLogSize = $DefaultLogSize;
if (!is_dir($LogDir)) mkdir($LogDir);
if (!is_dir($LogDir)) $LogDir = $DefaultLogDir;
if (!is_dir($LogDir)) die('ERROR!!! HRConvert2146, The log directory does not exist at '.$LogDir.' on '.$Time.'.');
if (!file_exists($LogDir.'/index.html')) @copy('index.html', $LogDir.'/index.html');
while (file_exists($LogFile) && round((filesize($LogFile) / $MaxLogSize), 2) > $MaxLogSize) { 
  $LogInc++; 
  $LogFile = $LogDir.'/HRConvert2_'.$LogInc.'.txt.'; 
  $MAKELogFile = file_put_contents($LogFile, 'OP-Act: Logfile created on '.$Time.'.'.PHP_EOL, FILE_APPEND); }
if (!file_exists($LogFile)) $MAKELogFile = file_put_contents($LogFile, 'OP-Act: Logfile created on '.$Time.'.'.PHP_EOL, FILE_APPEND);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code cleans up old files.
if (file_exists($ConvertTemp)) { 
  $DFiles = array_diff(scandir($ConvertTemp), array('..', '.'));
  $now = time();
  foreach ($DFiles as $DFile) { 
    if (in_array($DFile, $DefaultApps)) continue;
    $DFilePath = $ConvertTemp.DIRECTORY_SEPARATOR.$DFile;
    if ($DFilePath == $ConvertTemp.'/index.html') continue; 
    if ($now - fileTime($DFilePath) > ($Delete_Threshold * 60)) {
      if (is_dir($DFilePath)) { 
        @chmod ($DFilePath, 0755);
        cleanFiles($DFilePath);
        if (is_dir_empty($DFilePath)) @rmdir($DFilePath); } } } }
if (file_exists($ConvertLoc)) { 
  $DFiles = array_diff(scandir($ConvertLoc), array('..', '.'));
  $now = time();
  foreach ($DFiles as $DFile) { 
    if (in_array($DFile, $DefaultApps)) continue;
    $DFilePath = $ConvertLoc.DIRECTORY_SEPARATOR.$DFile;
    if ($now - fileTime($DFilePath) > ($Delete_Threshold * 60)) {
      if (is_dir($DFilePath)) { 
        @chmod ($DFilePath, 0755);
        cleanFiles($DFilePath); 
        if (is_dir_empty($DFilePath)) @rmdir($DFilePath); } } } }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code creates required directoreis if they do not exist.
if (!is_dir($ConvertLoc)) {
  $txt = ('ERROR!!! HRConvert278, The specified ConvertLoc does not exist at '.$ConvertLoc.' on '.$Time.'.');
  $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); 
  die('ERROR!!! HRConvert278'); }
foreach ($RequiredDirs as $RequiredDir) { 
  if (!is_dir($RequiredDir)) { 
    @mkdir($RequiredDir); 
    $txt = ('OP-Act: Created a directory at '.$RequiredDir.' on '.$Time.'.');
    $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } }
foreach ($RequiredIndexes as $RequiredIndex) @copy('index.html', $RequiredIndex.'/index.html'); 
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code is performed when a user initiates a file upload.
if (!empty($_FILES)) {
  $txt = ('OP-Act: Initiated Uploader on '.$Time.'.');
  $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
  if (!is_array($_FILES['file']['name'])) $_FILES['file']['name'] = array($_FILES['file']['name']); 
  foreach ($_FILES['file']['name'] as $key => $file) {
    if ($file == '.' or $file == '..' or $file == 'index.html') continue; 
    foreach ($DangerousFiles as $DangerousFile) if (strpos($file, $DangerousFile) == TRUE) continue 2;   
    $file = htmlentities(str_replace('..', '', str_replace(str_split('\\/[](){};:$!#^&%@>*<'), '', $file)), ENT_QUOTES, 'UTF-8');
    $F0 = pathinfo($file, PATHINFO_EXTENSION);
    if (in_array(strtolower($F0), $DangerousFiles)) { 
      $txt = ('ERROR!!! HRConvert2103, Unsupported file format, '.$F0.' on '.$Time.'.');
      $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
      continue; }
    $F2 = pathinfo($file, PATHINFO_BASENAME);
    $F3 = str_replace('..', '', str_replace(' ', '_', str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $ConvertDir.DIRECTORY_SEPARATOR.$F2)));
    if ($file == "") {
      $txt = ('ERROR!!! HRConvert2160, No file specified on '.$Time.'.');
      $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
      die('ERROR!!! HRConvert2160'); }
    @copy($_FILES['file']['tmp_name'], $F3);
    if (!file_exists($F3)) {
      $txt = ('ERROR!!! HRConvert2230, Could not upload '.$file.' to '.$F3.' on '.$Time.'!'); 
      $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
      die('ERROR!!! HRConvert2230'); }
    else { 
      $txt = ('OP-Act: '."Uploaded $file to $F3 on $Time".'.'); 
      $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); }
    @chmod($F3, 0755); 
    // / The following code checks the Cloud Location with ClamAV after copying, just in case.
    if ($VirusScan or $VirusScan === '1') {
      shell_exec(str_replace('  ', ' ', str_replace('  ', ' ', 'clamscan -r '.$F3.' | grep FOUND >> '.$ClamLogFile)));
      $ClamLogFileDATA = @file_get_contents($ClamLogFile);
      if (strpos($ClamLogFileDATA, 'Virus Detected') == 'true' or strpos($ClamLogFileDATA, 'FOUND') == 'true') {
        $txt = ('WARNING HRConvert2338, There were potentially infected files detected. The file transfer could not be completed at this time. Please check your file for viruses or try again later.');
        $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);          
        @unlink($F3);
        die($txt); } 
      @unlink($ClamLogFile); } } 
  // / Free un-needed memory.
  $txt = $file = $F0 = $F2 = $F3 = $ClamLogFileDATA = $Upload = $MAKELogFile = null;
  unset ($txt, $file, $F0, $F2, $F3, $ClamLogFileDATA, $Upload, $MAKELogFile); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code is performed when a user downloads a selection of files.
if (isset($download)) {
  $download = trim(str_replace('..', '', str_replace(str_split('[](){};:$!#^&%@>*<'), '', $download)), DIRECTORY_SEPARATOR);
  $txt = ('OP-Act: Initiated Downloader with input '.$download.' on '.$Time.'.');
  $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
  if (!is_array($download)) $download = array($download); 
  foreach ($download as $file) {
    $file = str_replace('..', '', str_replace(str_split('[](){};:$!#^&%@>*<'), '', $file));
    if ($file == '.' or $file == '..' or $file == 'index.html') continue;
    foreach ($DangerousFiles as $DangerousFile) { 
      if (strpos($file, $DangerousFile) == TRUE) continue 2; }
    $file = $ConvertDir.$file;
    if (!file_exists($file) or $file == "") {
      $txt = ('ERROR!!! HRConvert2260, '.$file.' doesn\'t exist on '.$Time.'.');
      $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
      continue; }
    $F2 = pathinfo($file, PATHINFO_BASENAME);
    $F3 = $ConvertTempDir.$F2;
    if (file_exists($F3)) { 
      @touch($F3); }
    if (!is_dir($file) && !file_exists($file)) { 
      @symlink($file, $F3); 
      $txt = ('OP-Act: '."Submitted $file to $F3 on $Time".'.');
      $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); }
    if (is_dir($file)) { 
      @mkdir($F3, 0755);
      foreach ($iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($file, \RecursiveDirectoryIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::SELF_FIRST) as $item) {
        foreach ($DangerousFiles as $DangerousFile) { 
          if (strpos($item, $DangerousFile) == TRUE) continue 2; }
        $F4 = $F3 . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
        if ($item->isDir()) {
          @mkdir($F4); }   
        else {
          @symlink($item, $F4); } } } } 
  // / Free un-needed memory.
  $txt = $_POST['filesToDownload'] = $file = $F2 = $F3 = $F4 = $iterator = $item = $MAKELogFile = null;
  unset ($txt, $_POST['filesToDownload'], $file, $F2, $F3, $F4, $iterator, $item, $MAKELogFile); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code is performed when a user selects files for archiving.
if (isset($_POST['filesToArchive'])) { 
  $_POST['archive'] = str_replace('..', '', str_replace(str_split('[](){};:$!#^&%@>*<'), '', $_POST['archive']));
  if (!is_array($_POST['filesToArchive'])) $_POST['filesToArchive'] = array($_POST['filesToArchive']); 
  $txt = ('OP-Act: Initiated Archiver on '.$Time.'.');
  $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); 
  foreach ($_POST['filesToArchive'] as $key => $TFile1) {
    $TFile1 = str_replace('..', '', str_replace(' ', '\ ', str_replace(str_split('[](){};:$!#^&%@>*<'), '', $TFile1))); 
    foreach ($DangerousFiles as $DangerousFile) if (strpos($TFile1, $DangerousFile) == TRUE) continue 2; 
    $archarray = array('zip', '7z', 'rar', 'tar', 'tar.gz', 'tar.bz2', 'iso', 'vhd');
    $rararr = array('rar');
    $ziparr = array('zip');
    $tararr = array('7z', 'tar', 'tar.gz', 'tar.bz2', 'iso', 'vhd');
    $filename = str_replace('..', '', str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $ConvertDir.$TFile1));
    $filename1 = pathinfo($filename, PATHINFO_BASENAME);
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $_POST['archextension'] = str_replace('..', '', str_replace(str_split('[](){};:$!#^&%@>*<'), '', $_POST['archextension']));
    $UserExt = $_POST['archextension'];
    $_POST['userfilename'] = str_replace('..', '', str_replace(str_split('[](){};:$!#^&%@>*<'), '', $_POST['userfilename']));
    $UserFileName = str_replace('..', '', str_replace(' ', '\ ', str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $_POST['userfilename'])));
    $archSrc = str_replace('..', '', str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $ConvertTempDir.$TFile1));
    $archDst = str_replace('..', '', str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $ConvertDir.$UserFileName));
    if (is_dir($filename) or (!in_array(strtolower($ext), $Allowed))) { 
      $txt = ('ERROR!!! HRConvert2290, Unsupported File Format.');
      $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
      die('ERROR!!! HRConvert2290'); } 
    // / Check the Cloud Location with ClamAV before archiving, just in case.
    if ($VirusScan or $VirusScan === '1') {
      shell_exec(str_replace('  ', ' ', str_replace('  ', ' ', 'clamscan -r '.$archSrc.' | grep FOUND >> '.$ClamLogFile)));
      $ClamLogFileDATA = file_get_contents($ClamLogFile);
      if (strpos($ClamLogFileDATA, 'Virus Detected') == 'true' or strpos($ClamLogFileDATA, 'FOUND') == 'true') {
        $txt = ('Warning!!! HRConvert2338, There were potentially infected files detected. The file transfer could not be completed at this time. Please check your file for viruses or try again later.');
        $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
        @unlink($archSrc);
        die($txt); } }
    // / Handle archiving of rar compatible files. 
    if (in_array($UserExt, $rararr)) {
      @copy($filename, $ConvertTempDir.$TFile1); 
      shell_exec('rar a -ep '.$archDst.' '.$archSrc); } 
    // / Handle archiving of .zip compatible files.
    if (in_array($UserExt, $ziparr)) { 
      @copy($filename, $ConvertTempDir.$TFile1); 
      shell_exec('zip -j '.$archDst.'.zip '.$archSrc); } 
    // / Handle archiving of 7zipper compatible files.
    if (in_array($UserExt, $tararr)) {
      @copy($filename, $ConvertTempDir.$TFile1); 
      shell_exec('7z a '.$archDst.'.'.$UserExt.' '.$archSrc); } 
    if (!file_exists($ConvertTempDir.$TFile1)) { 
      $txt = ('ERROR!!! HRC2333, Could not archive '.$filename.' to '.$ConvertTempDir.$TFile.' on '.$Time.'!');
      $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); 
      die('ERROR!!! HRC2333'); }
    else { 
      $txt = ('OP-Act: Archived '.$filename.' to '.$ConvertTempDir.$TFile.' on '.$Time.'.');
      $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } } 
  // / Free un-needed memory.
  $_POST['archive'] = $txt = $filesToArchive = $key = $TFile1 = $archarray = $rararr = $ziparr = $tararr = $filename = $filename1 = $ext = $_POST['archextension'] = $UserExt = $_POST['userfilename'] = $UserFileName = $archSrc = $archDst = $ClamLogFileDATA = $MAKELogFile = null;
  unset ($_POST['archive'], $filesToArchive, $key, $TFile1, $archarray, $rararr, $ziparr, $tararr, $filename, $filename1, $ext, $_POST['archextension'], $UserExt, $_POST['userfilename'], $UserFileName, $archSrc, $archDst, $ClamLogFileDATA, $MAKELogFile); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code is performed when a user selects files to convert to other formats.
if (isset($_POST['convertSelected'])) {
  $_POST['convertSelected'] = str_replace('..', '', str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, str_replace(str_split('[](){};:$!#^&%@>*<'), '', $_POST['convertSelected'])));
  $txt = ('OP-Act: Initiated HRConvert2 on '.$Time.'.');
  $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
  if (!is_array($_POST['convertSelected'])) $_POST['convertSelected'] = array($_POST['convertSelected']);
  foreach ($_POST['convertSelected'] as $key => $file) {
    $file = str_replace('..', '', str_replace(str_split('[](){};:$!#^&%@>*<'), '', $file));
    foreach ($DangerousFiles as $DangerousFile) if (strpos($file, $DangerousFile) == TRUE) continue 2;
    $txt = ('OP-Act: User selected to Convert file '.$file.'.');
    $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
    $file1 = str_replace('..', '', str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $ConvertDir.$file));
    $file2 = str_replace('..', '', str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $ConvertTempDir.$file));
    @copy($file1, $file2); 
    if (!file_exists($file2)) {
      $txt = ('ERROR!!! HRConvert2381, '."Could not copy $file1 to $file2 on $Time".'!'); 
      $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); 
      die('ERROR!!! HRConvert2381'); }
    else {
      $txt = ('OP-Act: '."Copied $file1 to $file2 on $Time".'.'); 
      $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); }
    $extension = str_replace('..', '', str_replace(str_split('[](){};:$!#^&%@>*<'), '', $_POST['extension']));
    $pathname = str_replace('..', '', str_replace(' ', '\ ', str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $ConvertTempDir.$file)));
    $oldPathname = str_replace('..', '', str_replace(' ', '\ ', str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $ConvertDir.$file)));
    $filename = pathinfo($pathname, PATHINFO_FILENAME);
    $oldExtension = pathinfo($pathname, PATHINFO_EXTENSION);
    $newPathname = str_replace('..', '', str_replace(' ', '\ ', str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $ConvertDir.str_replace('..', '', str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, str_replace(str_split('[](){};:$!#^&%@>*<'), '', $_POST['userconvertfilename'].'.'.$extension))))));
    $docarray =  array('txt', 'doc', 'xls', 'xlsx', 'docx', 'rtf', 'ods', 'odt', 'dat', 'cfg', 'pages', 'pptx', 'ppt', 'xps', 'potx', 'pot', 'ppa', 'odp', 'odt', 'abw');
    $imgarray = array('jpg', 'jpeg', 'bmp', 'webp', 'png', 'avif', 'crw', 'ico', 'cin', 'xwd', 'dcr', 'dds', 'dib', 'flif', 'gplt', 'nef', 'orf', 'ora', 'sct', 'sfw', 'xcf', 'xwg', 'gif');
    $audioarray =  array('mp3', 'wma', 'wav', 'ogg', 'mp2', 'flac', 'aac');
    $videoarray =  array('3gp', 'mkv', 'avi', 'mp4', 'flv', 'mpeg', 'wmv');
    $ModelArray = array('3ds', 'obj', 'collada', 'off', 'ply', 'stl', 'ptx', 'dxf', 'u3d', 'vrml');
    $drawingarray = array('xvg', 'dxf', 'vdx', 'fig');
    $pdfarray = array('pdf');
    $archarray = array('zip', '7z', 'rar', 'tar', 'tar.gz', 'tar.bz2', 'iso', 'vhd',);
    $array7z = array('7z', 'zip', 'rar', 'iso', 'vhd');
    $array7zo = array('7z', 'zip');
    $arrayzipo = array('zip');
    $array7zo2 = array('vhd', 'iso');
    $arraytaro = array('tar.gz', 'tar.bz2', 'tar');
    $arrayraro = array('rar',);
    // / Code to increment the conversion in the event that an output file already exists.
    while (file_exists($newPathname)) $newPathname = $ConvertDir.str_replace('..', '', str_replace(str_split('[](){};:$!#^&%@>*<'), '', $_POST['userconvertfilename'].'.'.$extension));
    if (in_array(strtolower($oldExtension), $Allowed)) { 
      // / Code to convert document files.
      if (in_array(strtolower($oldExtension), $docarray)) {
        // / The following code performs several compatibility checks before copying or converting anything.
        if (!file_exists('/usr/bin/unoconv')) {
          $txt = ('ERROR!!! HRConvert2654, Could not verify the document conversion engine on '.$Time.'.');
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); 
          die('ERROR!!! HRConvert2654'); }
        if (file_exists('/usr/bin/unoconv')) {
          $txt = ('OP-Act: Verified the document conversion engine on '.$Time.'.');
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); 
          // / The following code checks to see if Unoconv is in memory.
          exec("pgrep soffice.bin", $DocEnginePID, $DocEngineStatus);
          if (count($DocEnginePID) == 0) {
            $txt = ('OP-Act: Starting the document conversion engine on '.$Time.'.');
            $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); 
            exec('/usr/bin/unoconv -l &', $DocEnginePID1); 
            exec("pgrep soffice.bin", $DocEnginePID, $DocEngineStatus); } }
        if (count($DocEnginePID) > 0) {
          $txt = ('OP-Act, The document conversion engine PID is '.$DocEnginePID[0]);
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
          $txt = ("OP-Act, Executing \"unoconv -o $newPathname -f $extension $pathname\" on ".$Time.'.');
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
          exec("unoconv -o $newPathname -f $extension $pathname", $returnDATA); 
          if (!is_array($returnDATA)) {
            $txt = ('OP-Act, Unoconv returned '.$returnDATA.' on '.$Time.'.');
            $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); }
          if (is_array($returnDATA)) {
            $txt = ('OP-Act, Unoconv returned the following on '.$Time.':');
            $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); }
            foreach ($returnDATA as $returnDATALINE) {
              $txt = ($returnDATALINE);
              $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } }
        // / For some reason files take a moment to appear after being created with Unoconv.
        $stopper = 0;
        while (!file_exists($newPathname)) {
          exec("unoconv -o $newPathname -f $extension $pathname");
          $stopper++;
          if ($stopper == 10) {
            $txt = ('ERROR!!! HRConvert2425, The converter timed out while copying your file. ');
            $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
            die('ERROR!!! HRConvert2425'); } } }  
      // / Code to convert and manipulate image files.
      if (in_array(strtolower($oldExtension), $imgarray)) {
        $height = str_replace('..', '', str_replace(str_split('[](){};:$!#^&%@>*<'), '', $_POST['height']));
        $width = str_replace('..', '', str_replace(str_split('[](){};:$!#^&%@>*<'), '', $_POST['width']));
        $_POST["rotate"] = str_replace('..', '', str_replace(str_split('[](){};:$!#^&%@>*<'), '', $_POST['rotate']));
        $rotate = ('-rotate '.$_POST["rotate"]);
        $wxh = $width.'x'.$height;
        if ($wxh == '0x0' or $wxh =='x0' or $wxh == '0x' or $wxh == '0' or $wxh == '00' or $wxh == '' or $wxh == ' ') {
          $txt = ("OP-Act, Executing \"convert -background none $pathname $rotate $newPathname\" on ".$Time.'.');
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
          shell_exec("convert -background none $pathname $rotate $newPathname"); } 
        else {
          $txt = ("OP-Act, Executing \"convert -background none -resize $wxh $rotate $pathname $newPathname\" on ".$Time.'.');
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
          shell_exec("convert -background none -resize $wxh $rotate $pathname $newPathname"); } } 
      // / Code to convert and manipulate 3d model files.
      if (in_array(strtolower($oldExtension), $ModelArray)) { 
        $txt = ("OP-Act, Executing \"meshlabserver -i $pathname -o $newPathname\" on ".$Time.'.');
        $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
        shell_exec("meshlabserver -i $pathname -o $newPathname"); } 
      // / Code to convert and manipulate drawing files.
      if (in_array(strtolower($oldExtension), $drawingarray)) { 
        $txt = ("OP-Act, Executing \"dia $pathname -e $newPathname\" on ".$Time.'.');
        $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
        shell_exec("dia $pathname -e $newPathname"); } 
      // / Code to convert and manipulate video files.
      if (in_array(strtolower($oldExtension), $videoarray)) { 
        $txt = ("OP-Act, Executing \"ffmpeg -i $pathname -c:v libx264 $newPathname\" on ".$Time.'.');
        $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
        shell_exec("ffmpeg -i $pathname -c:v libx264 $newPathname"); } 
      // / Code to convert and manipulate audio files.
      if (in_array(strtolower($oldExtension), $audioarray)) { 
        $ext = (' -f ' . $extension);
          if (isset($_POST['bitrate'])) {
            $bitrate = str_replace('..', '', str_replace(str_split('[](){};:$!#^&%@>*<'), '', $_POST['bitrate'])); }
          if (!isset($_POST['bitrate'])) {
            $bitrate = 'auto'; }
        if ($bitrate = 'auto') {
          $br = ' '; } 
        elseif ($bitrate != 'auto' ) {
          $br = (' -ab ' . $bitrate . ' '); } 
          $txt = ("OP-Act, Executing \"ffmpeg -i $pathname$ext$br$newPathname\" on ".$Time.'.');
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
          shell_exec("ffmpeg -y -i $pathname$ext$br$newPathname"); } 
      // / Code to detect and extract an archive.
      if (in_array(strtolower($oldExtension), $archarray)) {
        $safedir2 = $ConvertDir.$filename;
        $safedir3 = $safedir2.'.7z';
        $safedir4 = $safedir2.'.zip';
        // / Create a folder to contain extracted files.
        mkdir("$safedir2", 0755);
        // / Code to Extract the selected archive using 7z.
        // / The code exists so we can change extraction methods granularly.
        if (in_array(strtolower($oldExtension), $arrayzipo)) {
          shell_exec("7z x $pathname -o$safedir2"); } 
        if (in_array(strtolower($oldExtension), $array7zo)) {
          shell_exec("7z x $pathname -o$safedir2"); } 
        if (in_array(strtolower($oldExtension), $array7zo2)) {
          shell_exec("7z x $pathname -o$safedir2"); } 
        if (in_array(strtolower($oldExtension), $arrayraro)) {
          shell_exec("7z x $pathname -o$safedir2"); } 
        if (in_array(strtolower($oldExtension), $arraytaro)) {
          shell_exec("7z x $pathname -o$safedir2"); }
        $txt = ("OP-Act, Executing \"7z x $pathname -o$safedir2\" on ".$Time.'.');
        $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } 
        // / Code to rearchive files using 7z.
        if (in_array($extension,$array7zo)) {
          $txt = ("OP-Act, Executing \"7z a -t$extension $safedir3 $safedir2\" on ".$Time.'.');
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
          if (file_exists($safedir3)) @unlink($safedir3);
          shell_exec('7z a -t'.$extension.' '.$safedir3.' '.$safedir2);
          @copy($safedir3, $newPathname); }
        // / Code to rearchive files using zip.
        if (in_array($extension,$arrayzipo)) {
          $txt = ("OP-Act, Executing \"zip -r -j $safedir4 $safedir2\" on ".$Time.'.');
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
          if (file_exists($safedir3)) @unlink($safedir4);
          shell_exec('zip -r -j '.$safedir4.' '.$safedir2);
          @copy($safedir4, $newPathname); }
        // / Code to rearachive files using tar.
        if (in_array($extension, $arraytaro)) {
          $txt = ("OP-Act, Executing \"tar -cjf '.$newPathname -C $safedir2 ".'. on '.$Time.'.');
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
          shell_exec('tar -cjf '.$newPathname.' -C '.$safedir2.' .'); } 
        // / Code to rearchive files using rar.
        if (in_array($extension, $arrayraro)) {
          $txt = ("OP-Act, Executing \"rar a -ep1 -r $newPathname $safedir2\" on ".$Time.'.');
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
          shell_exec('rar a -ep1 -r '.$newPathname.' '.$safedir2); }  
    // / Error handler & logger for converting files.
    if (!file_exists($newPathname)) {
      $txt = ('ERROR!!! HRConvert2524, '."Conversion failed! $newPathname could not be created from $oldPathname".'!');
      $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
      die('ERROR!!! HRConvert2524'); } 
    if (file_exists($newPathname)) {
      $txt = ('OP-Act: File '.$newPathname.' was created on '.$Time.'.');
      $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); }  
    // / Code to clean up temporary files & directories.
    $delFiles = glob($safedir2 . '/*');
    foreach ($delFiles as $delFile) {
      if (is_file($delFile)) {
        @chmod($delFile, 0755);
        @unlink($delFile); }
      elseif (is_dir($delFile)) {
        @chmod($delFile, 0755);
        @rmdir($delFile); } 
      @rmdir($safedir2); } } }
  // / Free un-needed memory.
  $_POST['convertSelected'] = $txt = $key = $file = $file1 = $file2 = $extension = $pathname = $oldPathname = $filename = $oldExtension= $newPathname = $docarray = $imgarray = $audioarray = $videoarray = $ModelArray = $drawingarray = $pdfarray = $archarray = $array7z = $array7zo = $arrayzipo = $arraytaro = $arrayraro = $_POST['userconvertfilename'] = $returnDATA = $returnDATALINE = $stopper = $height = $width = $_POST['height'] = $_POST['width'] = $rotate = $_POST['rotate'] = $wxh = $bitrate = $_POST['bitrate'] = $safedir2 = $safedir3 = $safedir4 = $delFiles = $delFile = $MAKELogFile = null;
  unset ($_POST['convertSelected'], $txt, $key, $file, $file1, $file2, $extension, $pathname, $oldPathname, $filename, $oldExtension, $newPathname, $docarray, $imgarray, $audioarray, $videoarray, $ModelArray, $drawingarray, $pdfarray, $archarray, $array7z, $array7zo, $arrayzipo, $arraytaro, $arrayraro, $_POST['userconvertfilename'], $returnDATA, $returnDATALINE, $stopper, $height, $width, $_POST['height'], $_POST['width'], $rotate, $_POST['rotate'], $wxh, $bitrate, $_POST['bitrate'], $safedir2, $safedir3, $safedir4, $delFiles, $delFile, $MAKELogFile ); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code is performed whenever a user selects a document or PDF for manipulation.
if (isset($_POST['pdfworkSelected'])) {
  $_POST['pdfworkSelected'] = str_replace('..', '', str_replace(str_split('[](){};:$!#^&%@>*<'), '', $_POST['pdfworkSelected']));
  $txt = ('OP-Act: Initiated PDFWork on '.$Time.'.');
  $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
  if (!is_array($_POST['pdfworkSelected'])) $_POST['pdfworkSelected'] = array($_POST['pdfworkSelected']);
  foreach ($_POST['pdfworkSelected'] as $file) {
    $file = str_replace('..', '', str_replace(str_split('[](){};:$!#^&%@>*<'), '', $file));
    foreach ($DangerousFiles as $DangerousFile) if (strpos($file, $DangerousFile) == TRUE) continue 2; 
    $txt = ('OP-Act: User selected to PDFWork file '.$file.' on '.$Time.'.');
    $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
    $file1 = $ConvertDir.$file;
    $file2 = $ConvertTempDir.$file;
    @copy($file1, $file2); 
    if (!file_exists($file2)) {
      $txt = ('ERROR!!! HRConvert2551, '."Could not copy $file1 to $file2 on $Time".'!'); 
      $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
      die('ERROR!!! HRConvert2551'); }
    else { 
      $txt = ('Copied '.$file1.' to '.$file2.' on '.$Time.''); 
      $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); }
    // / If no output format is selected the default of PDF is used instead.
    if (isset($_POST['pdfextension'])) $extension = str_replace('..', '', str_replace(str_split('[](){};:$!#^&%@>*<'), '', $_POST['pdfextension'])); 
    if (!isset($_POST['pdfextension'])) $extension = 'pdf'; 
    $pathname = str_replace('..', '', str_replace(' ', '\ ', str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $ConvertTempDir.$file))); 
    $oldPathname = str_replace('..', '', str_replace(' ', '\ ', str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $ConvertDir.$file)));
    $filename = pathinfo($pathname, PATHINFO_FILENAME);
    $oldExtension = pathinfo($pathname, PATHINFO_EXTENSION);
    $newFile = str_replace('..', '', str_replace(str_split('[](){};:$!#^&%@>*<'), '', $_POST['userpdfconvertfilename'].'.'.$extension));
    $newPathname = str_replace('..', '', str_replace(' ', '\ ', str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $ConvertDir.$newFile)));
    $doc1array =  array('txt', 'pages', 'doc', 'xls', 'xlsx', 'docx', 'rtf', 'odt', 'ods', 'odt');
    $img1array = array('jpg', 'jpeg', 'bmp', 'webp', 'png', 'gif');
    $pdf1array = array('pdf');
    $multiple = FALSE; 
      if (in_array(strtolower($oldExtension), $AllowedPDFw)) {
        while (file_exists($newPathname)) {
          $newFile = str_replace('..', '', str_replace(str_split('[](){};:$!#^&%@>*<'), '', $_POST['userpdfconvertfilename'].'.'.$extension));
          $newPathname = str_replace('..', '', str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $ConvertDir.$newFile)); } } 
          // / Code to convert a PDF to a document.
          if (in_array(strtolower($oldExtension), $pdf1array)) {
            if (in_array($extension, $doc1array)) {
              $pathnameTEMP = str_replace('..', '', str_replace('.'.$oldExtension, '.txt', $pathname));
              $_POST['method'] = str_replace('..', '', str_replace(str_split('[](){};:$!#^&%@>*<'), '', $_POST['method']));
              if ($_POST['method'] == '0' or $_POST['method'] == '') {
                shell_exec("pdftotext -layout $pathname $pathnameTEMP"); 
                $txt = ('OP-Act: '."Converted $pathnameTEMP1 to $pathnameTEMP on $Time".' using method 0.'); 
                $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); 
                if ((!file_exists($pathnameTEMP) or filesize($pathnameTEMP) < '5')) { 
                  $txt = ('Warning!!! HRC2591, There was a problem using the selected method to convert your file. Switching to automatic method and retrying the conversion.'); 
                  $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
                  $_POST['method'] = '1'; 
                  $txt = ('OP-Act: Attempting PDFWork conversion "method 2" on '.$Time.'.'); 
                  $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } }
              if ($_POST['method'] == '1') {
                $pathnameTEMP1 = str_replace('..', '', str_replace('.'.$oldExtension, '.jpg' , $pathname));
                shell_exec("convert $pathname $pathnameTEMP1");
                if (!file_exists($pathnameTEMP1)) {
                  $PagedFilesArrRAW = scandir($ConvertTempDir);
                  foreach ($PagedFilesArrRAW as $PagedFile) {
                    $pathnameTEMP1 = str_replace('..', '', str_replace('.'.$oldExtension, '.jpg' , $pathname));
                    if ($PagedFile == '.' or $PagedFile == '..' or $PagedFile == '.AppData' or $PagedFile == 'index.html') continue;
                    if (strpos($PagedFile, '.txt') !== false) continue;
                    if (strpos($PagedFile, '.pdf') !== false) continue;
                    $CleanFilname = str_replace('..', '', str_replace($oldExtension, '', $filename));
                    $CleanPathnamePages = str_replace('.jpg', '', $PagedFile);
                    $CleanPathnamePages = str_replace('.txt', '', $CleanPathnamePages);
                    $CleanPathnamePages = str_replace('.pdf', '', $CleanPathnamePages);
                    $CleanPathnamePages = str_replace($CleanFilname, '', $CleanPathnamePages);
                    $CleanPathnamePages = str_replace('..', '', str_replace('-', '', $CleanPathnamePages));
                    $PageNumber = $CleanPathnamePages;
                    if (is_numeric($PageNumber)) {
                      $pathnameTEMP1 = str_replace('..', '', str_replace('.jpg', '-'.$PageNumber.'.jpg', $pathnameTEMP1));
                      $pathnameTEMP = str_replace('..', '', str_replace('.'.$oldExtension, '-'.$PageNumber.'.txt', $pathname)); 
                      $pathnameTEMPTesseract = str_replace('..', '', str_replace('.'.$oldExtension, '-'.$PageNumber, $pathname)); 
                      $pathnameTEMP0 = str_replace('..', '', str_replace('-'.$PageNumber.'.txt', '.txt', $pathnameTEMP)); 
                      shell_exec("tesseract $pathnameTEMP1 $pathnameTEMPTesseract");
                      $READPAGEDATA = file_get_contents($pathnameTEMP);
                      $WRITEDOCUMENT = file_put_contents($pathnameTEMP0, $READPAGEDATA.PHP_EOL, FILE_APPEND);
                      $multiple = TRUE; 
                      $txt = ('OP-Act: '."Converted $pathnameTEMP1 to $pathnameTEMP on $Time".' using method 1.'); 
                      $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
                      $pathnameTEMP = $pathnameTEMP0;
                      if (!file_exists($pathnameTEMP0)) {
                        $txt = ('ERROR!!! HRConvert2617, HRC2610, $pathnameTEMP0 does not exist on '.$Time.'.'); 
                        $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } } } }
                    if (!$multiple) {
                      $pathnameTEMPTesseract = str_replace('..', '', str_replace('.'.$txt, '', $pathnameTEMP));
                      shell_exec("tesseract $pathnameTEMP1 $pathnameTEMPTesseract");
                      $txt = ('OP-Act: '."Converted $pathnameTEMP1 to $pathnameTEMP on $Time".' using method 1.');    
                      $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } } } } 
            // / Code to convert a document to a PDF.
            if (in_array(strtolower($oldExtension), $doc1array)) { 
              if (in_array($extension, $pdf1array)) {
                system("/usr/bin/unoconv -o $newPathname -f pdf $pathname"); 
                $txt = ('OP-Act: '."Converted $pathname to $newPathname on $Time".' using method 2.'); 
                $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } } 
          // / Code to convert an image to a PDF.
          if (in_array(strtolower($oldExtension), $img1array)) {
            $pathnameTEMP = str_replace('..', '', str_replace('.'.$oldExtension, '.txt' , $pathname));
            $pathnameTEMPTesseract = str_replace('..', '', str_replace('.'.$oldExtension, '', $pathname));
            $imgmethod = '1';
            shell_exec("tesseract $pathname $pathnameTEMPTesseract"); 
            if (!file_exists($pathnameTEMP)) {
              $imgmethod = '2';
              $pathnameTEMP3 = str_replace('..', '', str_replace('.'.$oldExtension, '.pdf' , $pathname));
              system("/usr/bin/unoconv -o $pathnameTEMP3 -f pdf $pathname");
              shell_exec("pdftotext -layout $pathnameTEMP3 $pathnameTEMP"); } 
            if (file_exists($pathnameTEMP)) {
              $txt = ('OP-Act: '."Converted $pathname to $pathnameTEMP1 on $Time".' using method '.$imgmethod.'.'); 
              $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } 
            if (!file_exists($pathnameTEMP)) {
              $txt = ('ERROR!!! HRConvert2667, '."Could not convert $pathname to $pathnameTEMP1 on $Time".' using method '.$imgmethod.'.'); 
              $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } }
        // / If the output file is a txt file we leave it as-is.
        if (!file_exists($newPathname)) { 
          if ($extension == 'txt') { 
            if (file_exists($pathnameTEMP)) {
              rename($pathnameTEMP, $newPathname); 
              $txt = ('OP-Act: HRC2613, '."Renamed $pathnameTEMP to $pathname on $Time".'.'); 
              $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } }
          // / If the output file is not a txt file we convert it with Unoconv.
          if ($extension !== 'txt') {
            system("/usr/bin/unoconv -o $newPathname -f $extension $pathnameTEMP");
            $txt = ('OP-Act: '."Converted $pathnameTEMP to $newPathname on $Time".'.'); 
            $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } }
        // / Error handler for if the output file does not exist.
        if (!file_exists($newPathname)) {
          $txt = ('ERROR!!! HRConvert2620, '."Could not convert $pathname to $newPathname on $Time".'!'); 
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
          die('ERROR!!! HRConvert2620'); } } 
  // / Free un-needed memory.
  $_POST['pdfworkSelected'] = $txt = $MAKELogFile = $file = $file1 = $file2 = $_POST['pdfextension'] = $extension = $pathname = $oldPathname = $filename = $oldExtension = $newFile = $newPathname = $doc1array = $img1array = $pdf1array = $pathnameTEMP = $_POST['method']= $pathnameTEMP1 = $PagedFilesArrRAW = $PagedFile = $CleanFilname = $CleanPathnamePages = $PageNumber = $READPAGEDATA = $WRITEDOCUMENT = $multiple = $pathnameTEMP0 = $pathnameTEMPTesseract = $pathnameTEMP0 = $imgmethod = $pathnameTEMP3 = null;
  unset ($_POST['pdfworkSelected'], $txt, $MAKELogFile, $file, $file1, $file2, $_POST['pdfextension'], $extension, $pathname, $oldPathname , $filename, $oldExtension, $newFile, $newPathname, $doc1array, $img1array, $pdf1array, $pathnameTEMP, $_POST['method'], $pathnameTEMP1, $PagedFilesArrRAW, $PagedFile, $CleanFilname, $CleanPathnamePages, $PageNumber, $READPAGEDATA, $WRITEDOCUMENT, $multiple, $pathnameTEMP0, $pathnameTEMPTesseract, $pathnameTEMP0, $imgmethod, $pathnameTEMP3); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code sync's the users AppData between the ConvertLoc and the InstLoc.
foreach ($iterator = new \RecursiveIteratorIterator (
  new \RecursiveDirectoryIterator ($ConvertDir, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $item) {
    @chmod($item, 0755);
    if (is_dir($item)) {
      if (!file_exists($ConvertTempDir.DIRECTORY_SEPARATOR.$iterator->getSubPathName())) mkdir($ConvertTempDir.DIRECTORY_SEPARATOR.$iterator->getSubPathName()); }
    else if (!is_link($ConvertTempDir.DIRECTORY_SEPARATOR.$iterator->getSubPathName()) or !file_exists($ConvertTempDir.DIRECTORY_SEPARATOR.$iterator->getSubPathName())) symlink($item, $ConvertTempDir.DIRECTORY_SEPARATOR.$iterator->getSubPathName()); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code prepares & loads the GUI.
if (isset($ShowGUI)) if (!$ShowGUI) $_GET['noGui'] = TRUE;

if (isset($_GET['showFiles']) or isset($_POST['showFiles'])) { 
  require_once('Languages/'.$LanguageToUse.'/header.php');
  require_once('Languages/'.$LanguageToUse.'/convertGui2.php'); 
  require_once('Languages/'.$LanguageToUse.'/footer.php'); }


if (!isset($_GET['showFiles'])) { 
  require_once('Languages/'.$LanguageToUse.'/header.php');
  require_once('Languages/'.$LanguageToUse.'/convertGui1.php'); 
  require_once('Languages/'.$LanguageToUse.'/footer.php'); }
// / -----------------------------------------------------------------------------------
?>
