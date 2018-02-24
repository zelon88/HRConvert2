<?php
// / -----------------------------------------------------------------------------------
// / APPLICATION INFORMATION ...
// / HRConvert2, Copyright on 2/21/2016 by Justin Grimes, www.github.com/zelon88
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
// / Apache 2.4, PHP 7.0+, JScript, WordPress & mySql (optional), LibreOffice, Unoconv, 
// / ClamAV, Tesseract, Rar, Unrar, Unzip, 7zipper, FFMPEG, PDF2TXT, and ImageMagick.
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code will load required HRConvert2 files.
if (!file_exists('config.php')) {
  echo nl2br('ERROR!!! HRConvert226, Cannot process the HRConvert2 Configuration file (config.php)!'."\n"); 
  die (); }
else {
  require_once ('config.php'); }
if ($HRC2_Integration == '1' && is_dir($HRC2_InstLoc)) {
  if (!file_exists('sanitizeCore.php')) {
    echo nl2br('ERROR!!! HRConvert233, Cannot process the HRConvert2 Sanitize Core file (sanitizeCore.php)!'."\n"); 
    die (); }
  else {
    require_once ('sanitizeCore.php'); } }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code sets the global variables for the session.
$HRConvertVersion = 'v0.8';
$Date = date("m_d_y");
$Time = date("F j, Y, g:i a"); 
$Current_URL = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$SesHash = substr(hash('ripemd160', $Date.$Salts1.$Salts2.$Salts3.$Salts4.$Salts5.$Salts6), -12);
$ConvertDir = $ConvertLoc.'/'.$SesHash;
$ConvertTemp = $InstLoc.'/DATA/';
$ConvertTempDir = $ConvertTemp.'/'.$SesHash;
$LogInc = '0';
$LogFile = $LogDir.'/HRConvert2_'.$LogInc.'_'.$Date.'_'.$SesHash.'.txt.';
$defaultLogDir = '/var/www/html/HRProprietary/HRConvert2/Logs';
$defaultLogSize = '1';
if (!is_numeric($MaxLogSize)) $MaxLogSize = $defaultLogSize;
if (!is_dir($LogDir)) $LogDir = $defaultLogDir;
if (!is_dir($LogDir)) mkdir($LogDir);
if (!file_exists($LogDir.'/index.html')) copy('index.html', $LogDir.'/index.html');
while (file_exists($LogFile) && round((filesize($LogFile) / 1048576), 2) > $MaxLogSize) { 
  $LogInc++; 
  $LogFile = $LogDir.'/HRConvert2_'.$LogInc.'.txt.'; 
  if (file_exists($LogFile) && round((filesize($LogFile) / 1048576), 2) > $MaxLogSize) { 
    $MAKELogFile = file_put_contents($LogFile, 'OP-Act: Logfile created on '.$Time.'.'.PHP_EOL, FILE_APPEND); 
    continue; } }
if (!file_exists($LogFile)) $MAKELogFile = file_put_contents($LogFile, 'OP-Act: Logfile created on '.$Time.'.'.PHP_EOL, FILE_APPEND);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code loads HRCloud2 if HRC2_Integration is enabled in config.php.
if ($HRC2_Integration == '1' && !is_dir($HRC2_InstLoc)) {
  $MAKELogFile = file_put_contents($LogFile, 'ERROR!!! HRConvert256, Could not enable HRCloud2 integration on '.$Time.'.'.PHP_EOL, FILE_APPEND); }
if ($HRC2_Integration == '1' && is_dir($HRC2_InstLoc)) {
  $HRC2_InstLoc = rtrim($HRC2_InstLoc, '/');
  if (!file_exists($HRC2_InstLoc.'/commonCore.php')) {
    echo nl2br('ERROR!!! HRConvert233, Cannot process the HRConvert2 Configuration file (config.php)!'."\n"); 
    die (); }
  else {
    require_once ($HRC2_InstLoc.'/commonCore.php'); } } 
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code is performed when a user initiates a file upload.
if(isset($upload)) {
  $txt = ('OP-Act: Initiated Uploader on '.$Time.'.');
  $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
  if (!is_array($_FILES["filesToUpload"]['name'])) {
    $_FILES["filesToUpload"]['name'] = array($_FILES["filesToUpload"]['name']); }
  foreach ($_FILES['filesToUpload']['name'] as $key=>$file) {
    if ($file == '.' or $file == '..' or $file == 'index.html') continue;     
    $_GET['UserDirPOST'] = htmlentities(str_replace(str_split('.[]{};:$!#^&%@>*<'), '', $_GET['UserDirPOST']), ENT_QUOTES, 'UTF-8');
    $file = htmlentities(str_replace(str_split('\\/[]{};:$!#^&%@>*<'), '', $file), ENT_QUOTES, 'UTF-8');
    $DangerousFiles = array('js', 'php', 'html', 'css');
    $F0 = pathinfo($file, PATHINFO_EXTENSION);
    if (in_array($F0, $DangerousFiles)) { 
      $txt = ("ERROR!!! HRConvert2103, Unsupported file format, $F0 on $Time.");
      $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
      echo nl2br($txt."\n".'--------------------'."\n"); 
      continue; }
    $F2 = pathinfo($file, PATHINFO_BASENAME);
    $F3 = str_replace('//', '/', $ConvertDir.$F2);
    if($file == "") {
      $txt = ("ERROR!!! HRConvert2160, No file specified on $Time.");
      $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
      echo nl2br($txt."\n".'--------------------'."\n"); 
      die(); }
    $COPY_TEMP = copy($_FILES['filesToUpload']['tmp_name'][$key], $F3);
    $txt = ('OP-Act: '."Uploaded $file to $ConvertTempDir on $Time".'.');
    echo nl2br ('OP-Act: '."Uploaded $file on $Time".'.'."\n".'--------------------'."\n");
    $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
    chmod($F3, 0755); 
    // / The following code checks the Cloud Location with ClamAV after copying, just in case.
    if ($VirusScan == '1') {
      shell_exec(str_replace('  ', ' ', str_replace('  ', ' ', 'clamscan -r '.$Thorough.' '.$F3.' | grep FOUND >> '.$ClamLogDir)));
      $ClamLogFileDATA = @file_get_contents($ClamLogDir);
      if (strpos($ClamLogFileDATA, 'Virus Detected') == 'true' or strpos($ClamLogFileDATA, 'FOUND') == 'true') {
        $txt = ('WARNING HRConvert2338, There were potentially infected files detected. The file
          transfer could not be completed at this time. Please check your file for viruses or
          try again later.'."\n");
        $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);          
        unlink($F3);
        die($txt); } } } 
  // / Free un-needed memory.
  $txt = $file = $F0 = $F2 = $F3 = $ClamLogFileDATA = $Upload = $MAKELogFile = null;
  unset ($txt, $file, $F0, $F2, $F3, $ClamLogFileDATA, $Upload, $MAKELogFile); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code is performed when a user downloads a selection of files.
if (isset($download)) {
  $txt = ('OP-Act: Initiated Downloader on '.$Time.'.');
  $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
  if (!is_array($_POST['filesToDownload'])) {
    $_POST['filesToDownload'] = array($_POST['filesToDownload']); }
  foreach ($_POST['filesToDownload'] as $key=>$file) {
    $file = str_replace(str_split('[]{};:$!#^&%@>*<'), '', $file);
    if ($file == '.' or $file == '..' or $file == 'index.html') continue;
    $file1 = $file;
    $file1 = trim($file, '/');
    $file = $ConvertDir.$file;
    if (!file_exists($file)) continue;
    $F2 = pathinfo($file, PATHINFO_BASENAME);
    $F3 = $ConvertTempDir.$F2;
    if($file == "") {
      $txt = ("ERROR!!! HRConvert2187, No file specified on $Time".'.');
      $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
      echo nl2br("ERROR!!! HRConvert2187, No file specified"."\n");
      die(); }
    if (file_exists($F3)) { 
      @touch($F3); }
    if (!is_dir($file) or !file_exists($file)) { 
      $COPY_TEMP = symlink($file, $F3); 
      $txt = ('OP-Act: '."Submitted $file to $F3 on $Time".'.');
      $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); }
    if (is_dir($file)) { 
      mkdir($F3, 0755);
      foreach ($iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($file, \RecursiveDirectoryIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::SELF_FIRST) as $item) {
        if ($item->isDir()) {
          mkdir($F3 . DIRECTORY_SEPARATOR . $iterator->getSubPathName()); }   
        else {
          symlink($item, $F3 . DIRECTORY_SEPARATOR . $iterator->getSubPathName()); } } } } 
  // / Free un-needed memory.
  $txt = $_POST['filesToDownload'] = $key = $file = $file1 = $F2 = $F3 = $COPY_TEMP = $iterator = $item = $MAKELogFile = null;
  unset ($txt, $_POST['filesToDownload'], $key, $file, $file1, $F2, $F3, $COPY_TEMP, $iterator, $item, $MAKELogFile); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code will be performed when a user selects archives to extract.
if (isset($_POST["dearchiveButton"])) {
  // / The following code sets the global dearchive variables for the session.
  $txt = ('OP-Act: Initiated Dearchiver on '.$Time.'.');
  $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
  $_POST['dearchiveButton'] = str_replace(str_split('[]{};:$!#^&%@>*<'), '', $_POST['dearchiveButton']);
  $UDP = '';
  $allowed =  array('zip', '7z', 'rar', 'tar', 'tar.gz', 'tar.bz2', 'iso', 'vhd');
  $archarray = array('zip', '7z', 'rar', 'tar', 'tar.gz', 'tar.bz2', 'iso', 'vhd');
  $rararr = array('rar');
  $ziparr = array('zip');
  $tararr = array('7z', 'tar', 'tar.gz', 'tar.bz2', 'iso', 'vhd');
  if ($UserDirPOST !== '/' or $UserDirPOST !== '//') {
    $UDP = $UserDirPOST; }
  if (isset($_POST["filesToDearchive"])) {
    if (!is_array($_POST["filesToDearchive"])) {
      $_POST['filesToDearchive'] = str_replace(str_split('[]{};:$!#^&%@>*<'), '', $_POST['filesToDearchive']);
      $_POST['filesToDearchive'] = array($_POST['filesToDearchive']); }
    foreach (($_POST['filesToDearchive']) as $File) {
      if ($File == '.' or $File == '..') continue;
      // / The following code sets variables for each archive being extracted.
      $File = str_replace(str_split('[]{};:$!#^&%@>*<'), '', $File); 
      $File = str_replace(' ', '\ ', $File); 
      $File = str_replace('//', '/', str_replace('//', '/', $File));   
      $File = ltrim($UDP.$File, '/'); 
      // / The following code sets and detects the USER directory and filename variables to be used for the operation.
      $dearchUserPath = str_replace('//', '/', $ConvertDir.'/'.$File);
      $ext = pathinfo(str_replace('//', '/', $ConvertDir.'/'.$File), PATHINFO_EXTENSION); 
      $dearchUserDir = str_replace('.'.$ext, '', $dearchUserPath);
      $dearchUserFile = pathinfo($dearchUserPath, PATHINFO_FILENAME);
      $dearchUserFilename = $dearchUserFile.'.'.$ext;
      // / The following code sets the TEMP directory and filename variables to be used to copy files for the operation.
      $dearchTempPath = str_replace('//', '/', $ConvertTempDir.'/'.$File);
      $dearchTempDir = str_replace('.'.$ext, '', $dearchTempPath);
      $dearchTempFile = $dearchUserFile;
      $dearchTempFilename = $dearchUserFile.'.'.$ext;
      // / The following code creates all of the temporary directories and file copies needed for the operation.
      // / The following code is performed when a dearchTempDir already exists.
      if (file_exists($dearchTempDir)) {
        copy ('index.html', $dearchTempDir.'/index.html');
        if (!is_dir($dearchTempDir)) {
          mkdir ($dearchTempDir, 0755);  
        if (is_dir($dearchTempDir)) {
          $txt = ('OP-Act: Verified '.$dearchTempDir.' on '.$Time.'.');
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } }
        // / The follwing code creates a dearchTempDir if one does not exist, and checks again.
        if (!is_dir($dearchTempDir)) {
          mkdir($dearchTempDir, 0755); 
          copy ('index.html', $dearchTempDir.'/index.html');
        if (is_dir($dearchTempDir)) {
          $txt = ('OP-Act: Created '.$dearchTempDir.' on '.$Time.'!');
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } } 
        // / The following double checks that all directories exist, and writes an error to the logfile if there are any.
        if (!is_dir($dearchTempDir)) {
          $txt = ('ERROR!!! HRConvert2390, Could not create a temp directory at '.$dearchTempDir.' on '.$Time.'!');
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); 
          die($txt); } 
        if (!is_dir($dearchTempDir)) {
          $txt = ('ERROR!!! HRConvert2394, Could not create a temp directory at '.$dearchTempDir.' on '.$Time.'!');
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); 
          die($txt); } }
      if (!file_exists($dearchTempDir)) {
        mkdir($dearchTempDir);
        copy ('index.html', $dearchTempDir.'/index.html');
        if (is_dir($dearchTempDir)) {
          $txt = ('OP-Act: Created '.$dearchTempDir.' on '.$Time.'.');
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } 
        if (!is_dir($dearchTempDir)) {
          $txt = ('ERROR!!! HRConvert2404, Could not create a temp directory at '.$dearchTempDir.' on '.$Time.'!');
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } }
      // / The following code creates all of the user directories and file copies needed for the operation.
      // / The following code is performed when a dearchUserDir already exists.
      if (file_exists($dearchUserDir)) {
        copy ('index.html', $dearchUserDir.'/index.html');
        if (!is_dir($dearchUserDir)) {
          mkdir ($dearchUserDir, 0755);  
        if (is_dir($dearchUserDir)) {
          $txt = ('OP-Act: Verified '.$dearchUserDir.' on '.$Time.'.');
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } }
        // / The follwing code creates a dearchUserDir if one does not exist, and checks again.
        if (!is_dir($dearchUserDir)) {
          mkdir($dearchUserDir, 0755); 
          copy ('index.html', $dearchUserDir.'/index.html');
        if (is_dir($dearchUserDir)) {
          $txt = ('OP-Act: Created '.$dearchUserDir.' on '.$Time.'!');
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } } 
        // / The following double checks that all directories exist, and writes an error to the logfile if there are any.
        if (!is_dir($dearchUserDir)) {
          $txt = ('ERROR!!! HRConvert2390, Could not create a user directory at '.$dearchUserDir.' on '.$Time.'!');
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); 
          die($txt); } }
      if (!file_exists($dearchUserDir)) {
        mkdir($dearchUserDir);
        copy ('index.html', $dearchUserDir.'/index.html');
        if (is_dir($dearchUserDir)) {
          $txt = ('OP-Act: Created '.$dearchUserDir.' on '.$Time.'.');
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } 
        if (!is_dir($dearchUserDir)) {
          $txt = ('ERROR!!! HRConvert2404, Could not create a user directory at '.$dearchUserDir.' on '.$Time.'!');
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } }
      // / The following code checks that the source files exist and are valid, and returns any errors that occur.
      if (file_exists($dearchUserDir)) {
        if (is_dir($dearchUserDir)) {
          copy ($dearchUserPath, $dearchTempPath);
          if (!file_exists($dearchTempPath)) {
            $txt = ('ERROR!!! HRConvert2412, There was a problem copying '.$dearchUserPath.' to '.$dearchTempPath.' on '.$Time.'.');
            $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); 
            die($txt); } 
          if (file_exists($dearchTempPath)) { 
            $txt = ('OP-Act, Copied '.$dearchUserPath.' to '.$dearchTempPath.' on '.$Time.'.');
            $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); 
            // / Check the Cloud Location with ClamAV before dearchiving, just in case.
            if ($VirusScan == '1') {
              shell_exec(str_replace('  ', ' ', str_replace('  ', ' ', 'clamscan -r '.$Thorough.' '.$dearchTempPath.' | grep FOUND >> '.$ClamLogDir)));
              $ClamLogFileDATA = file_get_contents($ClamLogDir);
              if (strpos($ClamLogFileDATA, 'Virus Detected') == 'true' or strpos($ClamLogFileDATA, 'FOUND') == 'true') {
                $txt = ('WARNING HRConvert2338, There were potentially infected files detected. The file
                  transfer could not be completed at this time. Please check your file for viruses or
                  try again later.'."\n");
                $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);          
                unlink($dearchTempPath);
                die($txt); } } } }
        if (!is_dir($dearchUserDir)) {
          $txt = ('ERROR!!! HRConvert2419, Discrepency detected! The dearchive directory supplied is not a directory on '.$Time.'!');
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } }
      if (!file_exists($dearchUserDir)) {
          mkdir($dearchUserDir, 0755); 
          if (file_exists($dearchUserDir)) {
            $txt = ('OP-Act: Created '.$dearchUserDir.' on '.$Time.'.');
            $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); 
            die($txt); }
          if (!file_exists($dearchUserDir)) {
            $txt = ('ERROR!!! HRConvert2428, The dearchive directory was not detected on '.$Time.'!');
            $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); 
            die($txt); } }
      // / Handle dearchiving of rar compatible files.
      if(in_array($ext,$rararr)) {
        $txt = ('OP-Act: Executing "unrar e '.$dearchTempPath.' '.$dearchUserDir.'" on '.$Time.'.');
        $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
        shell_exec('unrar e '.$dearchTempPath.' '.$dearchUserDir);
        if (file_exists($dearchUserDir)) {
          $txt = ('OP-Act: '."Dearchived $dearchTempPath to $dearchUserDir using method 1 on $Time".'.'); 
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } }
      // / Handle dearchiving of .zip compatible files.
      if(in_array($ext,$ziparr)) {
        $txt = ('OP-Act: Executing "unzip -o '.$dearchTempPath.' -d '.$dearchUserDir.'" on '.$Time.'.');
        $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
        shell_exec('unzip -o '.$dearchTempPath.' -d '.$dearchUserDir);
        if (file_exists($dearchUserDir)) {
          $txt = ('OP-Act: '."Dearchived $dearchTempPath to $dearchUserDir using method 2 on $Time".'.'); 
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } } 
      // / Handle dearchiving of 7zipper compatible files.
      if(in_array($ext,$tararr)) {
        $txt = ('OP-Act: Executing "7z e '.$dearchTempPath.' '.$dearchUserDir.'" on '.$Time.'.'); 
        $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
        shell_exec('7z e '.$dearchTempPath.' '.$dearchUserDir); 
        if (file_exists($dearchUserDir)) {
          $txt = ('OP-Act: '."Dearchived $dearchTempPath to $dearchUserDir using method 3 on $Time".'.'); 
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } } }
    if (file_exists($dearchUserDir)) {
      $dearchFiles = scandir($dearchUserDir);
      foreach ($dearchFiles as $dearchFile) {
        $DangerousFiles = array('js', 'php', 'html', 'css');
        $dearchFileLoc = $dearchUserDir.'/'.$dearchFile;
        $ext = pathinfo($dearchFileLoc, PATHINFO_EXTENSION);
        if (in_array($ext, $DangerousFiles) && $dearchFile !== 'index.html') {
          unlink($dearchFileLoc);
          $txt = ('ERROR!!! HRConvert2568, Unsupported file format, '.$ext.' on '.$Time."\n".'--------------------'."\n"); 
          echo nl2br ('ERROR!!! HRConvert2568, Unsupported file format, '.$ext.' on '.$Time."\n".'--------------------'."\n"); 
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } } }
    // / Return an error if the extraction failed and no files were created.
    if (!file_exists($dearchUserDir)) {
      $txt = ('ERROR!!! HRConvert2449, There was a problem creating '.$dearchUserDir.' on '.$Time."\n".'--------------------'."\n"); 
      echo nl2br ('ERROR!!! HRConvert2449, There was a problem creating '.$dearchUserDir.' on '.$Time."\n".'--------------------'."\n"); 
      $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } } 
  // / Free un-needed memory.
  $_POST['dearchiveButton'] = $txt = $UDP = $allowed = $archarray = $rararr = $ziparr = $tararr = $_POST["filesToDearchive"] = $File = $dearchUserPath = $ext
   = $dearchUserDir = $dearchUserFile = $dearchUserFilename = $dearchTempPath = $dearchTempDir = $dearchTempFile = $dearchTempFilename = $ClamLogFileDATA = $MAKELogFile = null;
  unset ($_POST['dearchiveButton'], $txt, $UDP, $allowed, $archarray, $rararr, $ziparr, $tararr, $_POST["filesToDearchive"], $File, $dearchUserPath, $ext, 
   $dearchUserDir, $dearchUserfile, $dearchUserFilename, $dearchTempPath, $dearchTempDir, $dearchTempFile, $dearchTempFilename, $ClamLogFileDATA, $MAKELogFile); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code is performed when a user selects files to convert to other formats.
if (isset($_POST['convertSelected'])) {
  $txt = ('OP-Act: Initiated HRConvert2 on '.$Time.'.');
  $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
  $_POST['convertSelected'] = str_replace('//', '/', str_replace(str_split('[]{};:$!#^&%@>*<'), '', $_POST['convertSelected']));
  foreach ($_POST['convertSelected'] as $key => $file) {
    $file = htmlentities(str_replace(str_split('[]{};:$!#^&%@>*<'), '', $file), ENT_QUOTES, 'UTF-8'); 
    $txt = ('OP-Act: User '.$UserID.' selected to Convert file '.$file.'.');
    $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
    $allowed =  array('svg', 'dxf', 'vdx', 'fig', '3ds', 'obj', 'collada', 'off', 'ply', 'stl', 'ptx', 'dxf', 'u3d', 'vrml', 'mov', 'mp4', 'mkv', 'flv', 'ogv', 'wmv', 'mpg', 'mpeg', 'm4v', '3gp', 'flac', 'aac', 'dat', 
      'cfg', 'txt', 'doc', 'docx', 'rtf' ,'xls', 'xlsx', 'ods', 'odf', 'odt', 'jpg', 'mp3', 'zip', 'rar', 'tar', 'tar.gz', 'tar.bz', 'tar.bZ2', '3gp', 'mkv', 'avi', 'mp4', 'flv', 'mpeg', 'wmv', 
      'avi', 'aac', 'mp2', 'wma', 'wav', 'ogg', 'jpeg', 'bmp', 'png', 'gif', 'pdf', 'abw', 'iso', 'vhd', 'vdi', 'pages', 'pptx', 'ppt', 'xps', 'potx', 'pot', 'ppa', 'ppa', 'odp');
    $file1 = str_replace('//', '/', $ConvertDir.$file);
    $file2 = str_replace('//', '/', $ConvertTempDir.$file);
    copy($file1, $file2); 
    if (file_exists($file2)) {
      $txt = ('OP-Act: '."Copied $file1 to $file2 on $Time".'.'); 
      $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); }
    if (!file_exists($file2)) {
      $txt = ('ERROR!!! HRConvert2381, '."Could not copy $file1 to $file2 on $Time".'!'); 
      $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
      echo nl2br('ERROR!!! HRConvert2381, There was a problem copying your file between internal HRCloud directories.
        Please rename your file or try again later.'."\n");
      die(); }
    $convertcount = 0;
    $extension = str_replace(str_split('[]{};:$!#^&%@>*<'), '', $_POST['extension']);
    $pathname = str_replace('//', '/', $ConvertTempDir.$file);
    $pathname = str_replace(' ', '\ ', $pathname);
    $oldPathname = str_replace('//', '/', $ConvertDir.$file);
    $filename = pathinfo($pathname, PATHINFO_FILENAME);
    $oldExtension = pathinfo($pathname, PATHINFO_EXTENSION);
    $newFile = str_replace('//', '/', str_replace(str_split('[]{};:$!#^&%@>*<'), '', $_POST['userconvertfilename'].'_'.$convertcount.'.'.$extension));
    $newPathname = str_replace('//', '/', $ConvertDir.$newFile);
    $docarray =  array('txt', 'doc', 'xls', 'xlsx', 'docx', 'rtf', 'odf', 'ods', 'odt', 'dat', 'cfg', 'pages', 'pptx', 'ppt', 'xps', 'potx', 'pot', 'ppa', 'odp', 'odf');
    $imgarray = array('jpg', 'jpeg', 'bmp', 'png', 'gif');
    $audioarray =  array('mp3', 'wma', 'wav', 'ogg', 'mp2', 'flac', 'aac');
    $videoarray =  array('3gp', 'mkv', 'avi', 'mp4', 'flv', 'mpeg', 'wmv');
    $modelarray = array('3ds', 'obj', 'collada', 'off', 'ply', 'stl', 'ptx', 'dxf', 'u3d', 'vrml');
    $drawingarray = array('xvg', 'dxf', 'vdx', 'fig');
    $pdfarray = array('pdf');
    $abwarray = array('abw');
    $archarray = array('zip', '7z', 'rar', 'tar', 'tar.gz', 'tar.bz2', 'iso', 'vhd',);
    $array7z = array('7z', 'zip', 'rar', 'iso', 'vhd');
    $array7zo = array('7z');
    $arrayzipo = array('zip');
    $array7zo2 = array('vhd', 'iso');
    $arraytaro = array('tar.gz', 'tar.bz2', 'tar');
    $arrayraro = array('rar',);
    $abwstd = array('doc', 'abw');
    $abwuno = array('docx', 'pdf', 'txt', 'rtf', 'odf', 'dat', 'cfg');
    $stub = ('http://localhost/HRProprietary/HRClou2/DATA/');
    $newFileURL = $stub.$UserID.$UserDirPOST.$newFile;
    // / Code to increment the conversion in the event that an output file already exists.    
    while(file_exists($newPathname)) {
      $convertcount++;
      $newFile = str_replace(str_split('[]{};:$!#^&%@>*<'), '', $_POST['userconvertfilename'].'_'.$convertcount.'.'.$extension);
      $newPathname = $ConvertDir.$newFile; }
          // / Code to convert document files.
          // / Note: Some servers may experience a delay between the script finishing and the
            // / converted file being placed into their Cloud drive. If your files do not immediately
            // / appear, simply refresh the page.
          if (in_array($oldExtension, $docarray)) {
          // / The following code performs several compatibility checks before copying or converting anything.
            if (!file_exists('/usr/bin/unoconv')) {
              $txt = ('ERROR!!! HRConvert2654 Could not verify the document conversion engine on '.$Time.'.');
              $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); 
              die($txt); }
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
                foreach($returnDATA as $returnDATALINE) {
                  $txt = ($returnDATALINE);
                  $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } }
            // / For some reason files take a moment to appear after being created with Unoconv.
            $stopper = 0;
            while(!file_exists($newPathname)) {
              exec("unoconv -o $newPathname -f $extension $pathname");
              $stopper++;
              if ($stopper == 10) {
                $txt = 'ERROR!!! HRConvert2425, The converter timed out while copying your file. ';
                $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
                die($txt); } } }
          // / Code to convert and manipulate image files.
          if (in_array($oldExtension, $imgarray)) {
            $height = str_replace(str_split('[]{};:$!#^&%@>*<'), '', $_POST['height']);
            $width = str_replace(str_split('[]{};:$!#^&%@>*<'), '', $_POST['width']);
            $_POST["rotate"] = str_replace(str_split('[]{};:$!#^&%@>*<'), '', $_POST['rotate']);
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
          if (in_array($oldExtension, $modelarray)) { 
            $txt = ("OP-Act, Executing \"meshlabserver -i $pathname -o $newPathname\" on ".$Time.'.');
            $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
            shell_exec("meshlabserver -i $pathname -o $newPathname"); } 
          // / Code to convert and manipulate drawing files.
          if (in_array($oldExtension, $drawingarray)) { 
            $txt = ("OP-Act, Executing \"dia $pathname -e $newPathname\" on ".$Time.'.');
            $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
            shell_exec("dia $pathname -e $newPathname"); } 
          // / Code to convert and manipulate video files.
          if (in_array($oldExtension, $videoarray)) { 
            $txt = ("OP-Act, Executing \"HandBrakeCLI -i $pathname -o $newPathname\" on ".$Time.'.');
            $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
            shell_exec("HandBrakeCLI -i $pathname -o $newPathname"); } 
          // / Code to convert and manipulate audio files.
          if (in_array($oldExtension, $audioarray)) { 
            $ext = (' -f ' . $extension);
              if (isset($_POST['bitrate'])) {
                $bitrate = str_replace(str_split('[]{};:$!#^&%@>*<'), '', $_POST['bitrate']); }
              if (!isset($_POST['bitrate'])) {
                $bitrate = 'auto'; }                  
            if ($bitrate = 'auto') {
              $br = ' '; } 
            elseif ($bitrate != 'auto' ) {
              $br = (' -ab ' . $bitrate . ' '); } 
              $txt = ("OP-Act, Executing \"ffmpeg -i $pathname$ext$br$newPathname\" on ".$Time.'.');
              $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
            shell_exec("ffmpeg -y -i $pathname$ext$br$newPathname"); } 
          // / Code to detect and extract an archive, and then re-archive the extracted
            // / files using a different method.
          if (in_array($oldExtension, $archarray)) {
            $safedir1 = $ConvertTempDir;
            $safedirTEMP = $ConvertTempDir.$filename;
            $safedirTEMP2 = pathinfo($safedirTEMP, PATHINFO_EXTENSION);
            $safedirTEMP3 = $ConvertTempDir.pathinfo($safedirTEMP, PATHINFO_BASENAME);            
            $safedir2 = $ConvertTempDir.$safedirTEMP3;
            mkdir("$safedir2", 0755);
            $safedir3 = ($safedir2.'.7z');
            $safedir4 = ($safedir2.'.zip');
          if(in_array($oldExtension, $arrayzipo)) {
            shell_exec("unzip $pathname -d $safedir2"); } 
          if(in_array($oldExtension, $array7zo)) {
            shell_exec("7z e $pathname -o$safedir2"); } 
          if(in_array($oldExtension, $array7zo2)) {
            shell_exec("7z e $pathname -o$safedir2"); } 
          if(in_array($oldExtension, $arrayraro)) {
            shell_exec("unrar e $pathname $safedir2"); } 
          if(in_array($oldExtension, $arraytaro)) {
            shell_exec("7z e $pathname -o$safedir2"); } 
            if (in_array($extension,$array7zo)) {
              shell_exec("7z a -t$extension $safedir3 $safedir2");
              copy($safedir3, $newPathname); } 
            if (file_exists($safedir3)) {
              @chmod($safedir3, 0755); 
              @unlink($safedir3);
              $delFiles = glob($safedir2 . '/*');
               foreach($delFiles as $delFile) {
                if(is_file($delFile) ) {
                  @chmod($delFile, 0755);  
                  @unlink($delFile); }
                elseif(is_dir($delFile) ) {
                  @chmod($delFile, 0755);
                  @rmdir($delFile); } }    
                  @rmdir($safedir2); }
            elseif (in_array($extension,$arrayzipo)) {
              shell_exec("zip -r -j $safedir4 $safedir2");
              @copy($safedir4, $newPathname); } 
              if (file_exists($safedir4)) {
                @chmod($safedir4, 0755); 
                @unlink($safedir4);
                $delFiles = glob($safedir2 . '/*');
                  foreach($delFiles as $delFile){
                    if(is_file($delFile)) {
                      @chmod($delFile, 0755);  
                      @unlink($delFile); }
                    elseif(is_dir($delFile)) {
                      @chmod($delFile, 0755);
                      @rmdir($delFile); } }    
                      @rmdir($safedir2); }
                    elseif (in_array($extension, $arraytaro)) {
                      shell_exec("tar czf $newPathname $safedir2");
                      $delFiles = glob($safedir2 . '/*');
                    foreach($delFiles as $delFile){
                      if(is_file($delFile)) {
                        @chmod($delFile, 0755);  
                        @unlink($delFile); }
                      elseif(is_dir($delFile)) {
                        @chmod($delFile, 0755);
                        @rmdir($delFile); } }     
                        @rmdir($safedir2); } 
                      elseif(in_array($extension, $arrayraro)) {
                        shell_exec("rar a -ep ".$newPathname.' '.$safedir2);
                        $delFiles = glob($safedir2 . '/*');
                          foreach($delFiles as $delFile){
                            if(is_file($delFile)) {
                              @chmod($delFile, 0755);  
                              unlink($delFile); }
                            elseif(is_dir($delFile) ) {
                              @chmod($delFile, 0755);
                              @rmdir($delFile); } } 
                              @rmdir($safedir2); } }
// / Error handler and logger for converting files.
  if (!file_exists($newPathname)) {
    echo nl2br('ERROR!!! HRConvert2524, There was an error during the file conversion process and your file was not copied.'."\n");
    $txt = ('ERROR!!! HRConvert2524, '."Conversion failed! $newPathname could not be created from $oldPathname".'!');
    $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
    die(); } 
  if (file_exists($newPathname)) {
    $txt = ('OP-Act: File '.$newPathname.' was created on '.$Time.'.');
    $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } } 
  // / Free un-needed memory.
  $_POST['convertSelected'] = $txt = $key = $file = $allowed = $file1 = $file2 = $convertcount = $extension = $pathname = $oldPathname = $filename = $oldExtension
   = $newFile = $newPathname = $docarray = $imgarray = $audioarray = $videoarray = $modelarray = $drawingarray = $pdfarray = $abwarray = $archarray = $array7z = $array7zo
   = $arrayzipo = $arraytaro = $arrayraro = $abwstd = $abwuno = $stub = $newFileURL = $_POST['userconvertfilename'] = $returnDATA = $returnDATALINE = $stopper = $height 
   = $width = $_POST['height'] = $_POST['width'] = $rotate = $_POST['rotate'] = $wxh = $bitrate = $_POST['bitrate'] = $safedir1 = $safedirTEMP = $safedirTEMP2 = $safedirTEMP3
   = $safedir2 = $safedir3 = $safedir4 = $delFiles = $delFile = $MAKELogFile = null;
  unset ($_POST['convertSelected'], $txt, $key, $file, $allowed, $file1, $file2, $convertcount, $extension, $pathname, $oldPathname, $filename, $oldExtension, 
   $newFile, $newPathname, $docarray, $imgarray, $audioarray, $videoarray, $modelarray, $drawingarray, $pdfarray, $abwarray, $archarray, $array7z, $array7zo,
   $arrayzipo, $arraytaro, $arrayraro, $abwstd, $abwuno, $stub, $newFileURL, $_POST['userconvertfilename'], $returnDATA, $returnDATALINE, $stopper, $height, 
   $width, $_POST['height'], $_POST['width'], $rotate, $_POST['rotate'], $wxh, $bitrate, $_POST['bitrate'], $safedir1, $safedirTEMP, $safedirTEMP2, $safedirTEMP3,
   $safedir2, $safedir3, $safedir4, $delFiles, $delFile, $MAKELogFile ); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code is performed whenever a user selects a document or PDF for manipulation.
if (isset($_POST['pdfworkSelected'])) {
  $_POST['pdfworkSelected'] = str_replace(str_split('[]{};:$!#^&%@>*<'), '', $_POST['pdfworkSelected']);
  $txt = ('OP-Act: Initiated PDFWork on '.$Time.'.');
  $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
    if (!is_array($_POST['pdfworkSelected'])) {
      $_POST['pdfworkSelected'] = array($_POST['pdfworkSelected']); } 
  $pdfworkcount = '0';
  foreach ($_POST['pdfworkSelected'] as $key=>$file) {
    $file = str_replace(str_split('[]{};:$!#^&%@>*<'), '', $file);
    $txt = ('OP-Act: User '.$UserID.' selected to PDFWork file '.$file.' on '.$Time.'.');
    $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
    $allowedPDFw =  array('txt', 'doc', 'docx', 'rtf' ,'xls', 'xlsx', 'ods', 'odf', 'odt', 'jpg', 'jpeg', 'bmp', 'png', 'gif', 'pdf', 'abw');
    $file1 = $ConvertDir.$file;
    $file2 = $ConvertTempDir.$file;
    copy($file1, $file2); 
    if (file_exists($file2)) {
      $txt = ('OP-Act: '."Copied $file1 to $file2 on $Time".'.'); 
      $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); }
    if (!file_exists($file2)) {
      $txt = ('ERROR!!! HRConvert2551, '."Could not copy $file1 to $file2 on $Time".'!'); 
      $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
      echo nl2br('ERROR!!! HRConvert2551, There was a problem copying your file between internal HRCloud directories.
        Please rename your file or try again later.'."\n");
      die(); }
    // / If no output format is selected the default of PDF is used instead.
    if (isset($_POST['pdfextension'])) {
      $extension = str_replace(str_split('[]{};:$!#^&%@>*<'), '', $_POST['pdfextension']); } 
    if (!isset($_POST['pdfextension'])) {
      $extension = 'pdf'; }
    $pathname = str_replace('//', '/', $ConvertTempDir.$file); 
    $pathname = str_replace(' ', '\ ', $pathname);
    $oldPathname = str_replace('//', '/', $ConvertDir.$file);
    $filename = pathinfo($pathname, PATHINFO_FILENAME);
    $oldExtension = pathinfo($pathname, PATHINFO_EXTENSION);
    $newFile = str_replace(str_split('[]{};:$!#^&%@>*<'), '', $_POST['userpdfconvertfilename'].'_'.$pdfworkcount.'.'.$extension);
    $newPathname = str_replace('//', '/', $ConvertDir.$newFile);
    $doc1array =  array('txt', 'pages', 'doc', 'xls', 'xlsx', 'docx', 'rtf', 'odf', 'ods', 'odt');
    $img1array = array('jpg', 'jpeg', 'bmp', 'png', 'gif');
    $pdf1array = array('pdf');
    $stub = ($URL.'/HRProprietary/HRCloud2/DATA/');
    $newFileURL = $stub.$UserID.$UserDirPOST.$newFile;
      if (in_array($oldExtension, $allowedPDFw)) {
        while(file_exists($newPathname)) {
          $pdfworkcount++; 
          $newFile = str_replace(str_split('[]{};:$!#^&%@>*<'), '', $_POST['userpdfconvertfilename'].'_'.$pdfworkcount.'.'.$extension);
          $newPathname = str_replace('//', '/', $ConvertDir.$newFile); } } 
          // / Code to convert a PDF to a document.
          if (in_array($oldExtension, $pdf1array)) {
            if (in_array($extension, $doc1array)) {
              $pathnameTEMP = str_replace('.'.$oldExtension, '.txt', $pathname);
              $_POST['method'] = str_replace(str_split('[]{};:$!#^&%@>*<'), '', $_POST['method']);
              if ($_POST['method1'] == '0' or $_POST['method1'] == '') {
                shell_exec("pdftotext -layout $pathname $pathnameTEMP"); 
                $txt = ('OP-Act: '."Converted $pathnameTEMP1 to $pathnameTEMP on $Time".' using method 0.'); 
                $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); 
                if ((!file_exists($pathnameTEMP) or filesize($pathnameTEMP) < '5')) { 
                  $txt = ('Warning!!! HRC2591, There was a problem using the selected method to convert your file. Switching to 
                    automatic method and retrying the conversion.'."\n"); 
                  $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
                  echo nl2br('Warning!!! HRC2591, There was a problem using the selected method to convert your file. Switching to 
                    automatic method and retrying the conversion on '.$Time.'.'."\n");
                  $_POST['method1'] = '1'; 
                  $txt = ('Notice!!! HRC2601, Attempting PDFWork conversion "method 2" on '.$Time.'.'."\n"); 
                  echo ($txt."\n".'--------------------'."\n"); 
                  $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } }          
              if ($_POST['method1'] == '1') {
                $pathnameTEMP1 = str_replace('.'.$oldExtension, '.jpg' , $pathname);
                shell_exec("convert $pathname $pathnameTEMP1");
                if (!file_exists($pathnameTEMP1)) {
                  $PagedFilesArrRAW = scandir($ConvertTempDir);
                  foreach ($PagedFilesArrRAW as $PagedFile) {
                    $pathnameTEMP1 = str_replace('.'.$oldExtension, '.jpg' , $pathname);
                    if ($PagedFile == '.' or $PagedFile == '..' or $PagedFile == '.AppData' or $PagedFile == 'index.html') continue;
                    if (strpos($PagedFile, '.txt') !== false) continue;
                    if (strpos($PagedFile, '.pdf') !== false) continue;
                    $CleanFilname = str_replace($oldExtension, '', $filename);
                    $CleanPathnamePages = str_replace('.jpg', '', $PagedFile);
                    $CleanPathnamePages = str_replace('.txt', '', $CleanPathnamePages);
                    $CleanPathnamePages = str_replace('.pdf', '', $CleanPathnamePages);
                    $CleanPathnamePages = str_replace($CleanFilname, '', $CleanPathnamePages);                    
                    $CleanPathnamePages = str_replace('-', '', $CleanPathnamePages);
                    $PageNumber = $CleanPathnamePages;
                    if (is_numeric($PageNumber)) {
                      $pathnameTEMP1 = str_replace('.jpg', '-'.$PageNumber.'.jpg', $pathnameTEMP1);
                      $pathnameTEMP = str_replace('.'.$oldExtension, '-'.$PageNumber.'.txt', $pathname); 
                      $pathnameTEMPTesseract = str_replace('.'.$oldExtension, '-'.$PageNumber, $pathname); 
                      $pathnameTEMP0 = str_replace('-'.$PageNumber.'.txt', '.txt', $pathnameTEMP); 
                      echo nl2br("\n".$pathnameTEMP."\n");
                      shell_exec("tesseract $pathnameTEMP1 $pathnameTEMPTesseract");
                      $READPAGEDATA = file_get_contents($pathnameTEMP);
                      $WRITEDOCUMENT = file_put_contents($pathnameTEMP0, $READPAGEDATA.PHP_EOL, FILE_APPEND);
                      $multiple = '1'; 
                      $txt = ('OP-Act: '."Converted $pathnameTEMP1 to $pathnameTEMP on $Time".' using method 1.'); 
                      $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
                      $pathnameTEMP = $pathnameTEMP0;
                      if (!file_exists($pathnameTEMP0)) {
                        $txt = ('ERROR!!! HRConvert2617, HRC2610, $pathnameTEMP0 does not exist on '.$Time.'.'."\n"); 
                        $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);   
                        echo ($txt."\n".'--------------------'."\n"); } } } }
                    if ($multiple !== '1') {
                    $pathnameTEMPTesseract = str_replace('.'.$txt, '', $pathnameTEMP);
                    shell_exec("tesseract $pathnameTEMP1 $pathnameTEMPTesseract");
                    $txt = ('OP-Act: '."Converted $pathnameTEMP1 to $pathnameTEMP on $Time".' using method 1.'); 
                    echo ($txt."\n".'--------------------'."\n");    
                    $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } } } } 
            // / Code to convert a document to a PDF.
            if (in_array($oldExtension, $doc1array)) {                
              if (in_array($extension, $pdf1array)) {
                system("/usr/bin/unoconv -o $newPathname -f pdf $pathname"); 
                $txt = ('OP-Act: '."Converted $pathname to $newPathname on $Time".' using method 2.'); 
                echo ('OP-Act: '."Performed PDFWork on $Time".' using method 2.'."\n".'--------------------'."\n"); 
                $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } } 
          // / Code to convert an image to a PDF.
          if (in_array($oldExtension, $img1array)) {
            $pathnameTEMP = str_replace('.'.$oldExtension, '.txt' , $pathname);
            $pathnameTEMPTesseract = str_replace('.'.$oldExtension, '', $pathname);
            $imgmethod = '1';
            shell_exec("tesseract $pathname $pathnameTEMPTesseract"); 
            if (!file_exists($pathnameTEMP)) {
              $imgmethod = '2';
              $pathnameTEMP3 = str_replace('.'.$oldExtension, '.pdf' , $pathname);
              system("/usr/bin/unoconv -o $pathnameTEMP3 -f pdf $pathname");
              shell_exec("pdftotext -layout $pathnameTEMP3 $pathnameTEMP"); } 
            if (file_exists($pathnameTEMP)) {
              $txt = ('OP-Act: '."Converted $pathname to $pathnameTEMP1 on $Time".' using method '.$imgmethod.'.'); 
              echo('OP-Act: '."Performed PDFWork on $Time".' using method '.$imgmethod.'.'."\n".'--------------------'."\n");
              $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } 
            if (!file_exists($pathnameTEMP)) {
              $txt = ('ERROR!!! HRConvert2667, '."An internal error occured converting $pathname to $pathnameTEMP1 on $Time".' using method '.$imgmethod.'.'); 
              echo('ERROR!!! HRConvert2667, '."An internal error occured your file on $Time".' using method '.$imgmethod.'.'."\n".'--------------------'."\n");
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
            echo nl2br('OP-Act: '."Performing finishing touches on $Time".'.');
            $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } }
        // / Error handler for if the output file does not exist.
        if (!file_exists($newPathname)) {
          echo nl2br('ERROR!!! HRConvert2620, There was a problem converting your file! Please rename your file or try again later.'."\n".'--------------------'."\n"); 
          $txt = ('ERROR!!! HRConvert2620, '."Could not convert $pathname to $newPathname on $Time".'!'); 
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
           die(); } } 
  // / Free un-needed memory.
  $_POST['pdfworkSelected'] = $txt = $MAKELogFile = $pdfworkcount = $key = $file = $allowedPDFw = $file1 = $file2 = $_POST['pdfextension'] = $extension = $pathname 
   = $oldPathname = $filename = $oldExtension = $newFile = $newPathname = $doc1array = $img1array = $pdf1array = $stub = $newFileURL = $pathnameTEMP = $_POST['method']
   = $_POST['method1'] = $pathnameTEMP1 = $PagedFilesArrRAW = $PagedFile = $CleanFilname = $CleanPathnamePages = $PageNumber = $READPAGEDATA = $WRITEDOCUMENT = $multiple
   = $pathnameTEMP0 = $pathnameTEMPTesseract = $pathnameTEMP0 = $imgmethod = $pathnameTEMP3 = null;
  unset ($_POST['pdfworkSelected'], $txt, $MAKELogFile, $pdfworkcount, $key, $file, $allowedPDFw, $file1, $file2, $_POST['pdfextension'], $extension, $pathname,
   $oldPathname , $filename, $oldExtension, $newFile, $newPathname, $doc1array, $img1array, $pdf1array, $stub, $newFileURL, $pathnameTEMP, $_POST['method'],
   $_POST['method1'], $pathnameTEMP1, $PagedFilesArrRAW, $PagedFile, $CleanFilname, $CleanPathnamePages, $PageNumber, $READPAGEDATA, $WRITEDOCUMENT, $multiple,
   $pathnameTEMP0, $pathnameTEMPTesseract, $pathnameTEMP0, $imgmethod, $pathnameTEMP3); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code loads the GUI.
require_once('convertGui.php');
// / -----------------------------------------------------------------------------------

?>