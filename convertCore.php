<!DOCTYPE HTML>
<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRConvert2, Copyright on 3/28/2024 by Justin Grimes, www.github.com/zelon88
// /
// / LICENSE INFORMATION ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / APPLICATION INFORMATION ...
// / This application is designed to provide a web-interface for converting file formats
// / on a server for users of any web browser without authentication.
// /
// / FILE INFORMATION ...
// / v3.3.5.
// / This file contains the core logic of the application.
// /
// / HARDWARE REQUIREMENTS ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / DEPENDENCY REQUIREMENTS ...
// / This application requires Debian Linux (w/3rd Party audio license),
// / Apache 2.4, PHP 8+, LibreOffice, Unoconv, ClamAV, Tesseract, Rar, Unrar, Unzip,
// / 7zipper, FFMPEG, PDFTOTEXT, Dia, PopplerUtils, MeshLab, Mkisofs & ImageMagick.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to reset PHP's time limit for execution.
function setTimeLimit() {
  $TimeReset = set_time_limit(0);
  return $TimeReset; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to set the date & time for the session.
function verifyTime() {
  // / Set variables.
  global $TimeIsSet, $Date, $Time;
  $TimeIsSet = FALSE;
  $tzAbbreviations = DateTimeZone::listAbbreviations();
  $tzList = array();
  // / Build a list of timezones supported by this PHP installation.
  foreach ($tzAbbreviations as $zone) foreach ($zone as $item) if (is_string($item['timezone_id']) && $item['timezone_id'] !== '') $tzList[] = $item['timezone_id'];
  $tzList = array_unique($tzList);
  $zoneList = array_values($tzList);
  // / Check that the currently set timezone is valid.
  if (in_array(@date_default_timezone_get(), $zoneList)) $TimeIsSet = TRUE;
  // / Try to set the time regardless of whether or not the timezone is correct.
  $Date = date("m_d_y");
  $Time = date("F j, Y, g:i a");
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $tzAbbreviations = $tzList = $zoneList = $zone = $item = NULL;
  unset($tzAbbreviations, $tzList, $zoneList, $zone, $item);
  return array($TimeIsSet, $Date, $Time); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to sanitize input strings with varying degrees of tolerance.
// / Filters a given string of | \ ~ # [ ] ( ) { } ; : $ ! # ^ & % @ > * < " / '
// / This function will replace any of the above specified charcters with NOTHING. No character at all. An empty string.
// / This function will replace whitespace with the underscore character.
// / Set $strict to TRUE to also filter out backslash characters as well. Example:  /
function sanitizeString($Variable, $strict) {
  if ($strict) $Variable = htmlentities(trim(str_replace(' ', '_', str_replace('..', '', str_replace('//', '', str_replace(str_split('|\\~#[](){};:$!#^&%@>*<"\'/'), '', $Variable))))), ENT_QUOTES, 'UTF-8');
  if (!$strict) $Variable = htmlentities(trim(str_replace(' ', '_', str_replace('..', '', str_replace('//', '', str_replace(str_split('|\\[](){};"\''), '', $Variable))))), ENT_QUOTES, 'UTF-8');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $strict = NULL;
  unset($strict);
  return $Variable; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to sanitize input strings or arrays with varying degrees of tolerance.
// / Filters a given string of | \ ~ # [ ] ( ) { } ; : $ ! # ^ & % @ > * < " / '
// / This function will replace any of the above specified charcters with NOTHING. No character at all. An empty string.
// / This function will replace whitespace with the underscore character.
// / Set $strict to TRUE to also filter out backslash characters as well. Example:  /
function sanitize($Variable, $strict) {
  // / Set variables.
  $VariableIsSanitized = TRUE;
  $var = '';
  $key = 0;
  if (!is_bool($strict)) $strict = TRUE;
  // / Only continue if the input variable is a type that we can properly sanitize.
  if (!is_string($Variable) && !is_numeric($Variable) && !is_array($Variable)) $VariableIsSanitized = FALSE;
  else {
    // / Sanitize array inputs.
    // / Note that when $strict is TRUE this also filters out backslashes.
    if (is_array($Variable)) foreach ($Variable as $key => $var) $Variable[$key] = sanitizeString($Variable[$key], $strict);
    // / Sanitize string & numeric inputs.
    // / Note that when $strict is TRUE this also filters out backslashes.
    if (is_string($Variable) or is_numeric($Variable)) $Variable = sanitizeString($Variable, $strict); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $strict = $key = $var = NULL;
  unset($strict, $key, $var);
  return array($Variable, $VariableIsSanitized); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to load required HRConvert2 files.
function verifyInstallation() {
  // / Set variables.
  global $Salts1, $Salts2, $Salts3, $Salts4, $Salts5, $Salts6, $URL, $VirusScan, $AllowUserVirusScan, $InstLoc, $ServerRootDir, $ConvertLoc, $LogDir, $ApplicationName, $ApplicationTitle, $SupportedLanguages, $DefaultLanguage, $AllowUserSelectableLanguage, $SupportedGuis, $DefaultGui, $AllowUserSelectableGui, $DeleteThreshold, $Verbose, $MaxLogSize, $Font, $ButtonStyle, $DefaultColor, $SupportedColors, $AllowUserSelectableColor, $ColorToUse, $ShowGUI, $ShowFinePrint, $TOSURL, $PPURL, $ScanCoreMemoryLimit, $ScanCoreChunkSize, $ScanCoreDebug, $ScanCoreVerbose, $SpinnerStyle, $SpinnerColor, $URL, $AllowUserShare, $SupportedConversionTypes, $VersionInfoFile, $Version, $DeleteBuildEnvironment, $DeleteDevelopmentDocumentation, $UserArchiveArray, $UserDearchiveArray, $UserDocumentArray, $UserSpreadsheetArray, $UserPresentationArray, $UserImageArray, $UserMediaArray, $UserVideoArray, $UserStreamArray, $UserDrawingArray, $UserModelArray, $UserSubtitleArray, $UserPDFWorkArr, $RARArchiveMethod, $RetryCount;
  // / Define absolute paths for files that we only have relative paths for.
  $InstallationIsVerified = $buildDirDeleted = $dockerFileDeleted = $readmeDeleted = $changelogFileDeleted = $buildEnvDeleted = $devDocsDeleted = $checkOne = $checkTwo = FALSE;
  $ConfigFile = realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'config.php');
  $VersionInfoFile = realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'versionInfo.php');
  $dockerFile = realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'Documentation'.DIRECTORY_SEPARATOR.'Build'.DIRECTORY_SEPARATOR.'Dockerfile');
  $changelogFile = realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'Documentation'.DIRECTORY_SEPARATOR.'CHANGELOG.txt');
  $readmeFile = realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'README.md');
  $buildDir = realpath(dirname(__DIR__).DIRECTORY_SEPARATOR.'Build');
  // / Check for required files & stop execution if they are missing.
  if (!file_exists($ConfigFile)) die ('ERROR!!! HRConvert2-0: Could not process the HRConvert2 Configuration file (config.php)!'.PHP_EOL.'<br />');
  else require_once ($ConfigFile);
  if (!file_exists($VersionInfoFile)) die ('ERROR!!! HRConvert2-24000: Could not process the HRConvert2 Version Information file (versionInfo.php)!'.PHP_EOL.'<br />');
  else require_once ($VersionInfoFile);
  // / Delete the build environment if specified by config.php.
  if ($DeleteBuildEnvironment) {
    if (is_dir($buildDir)) $buildDirDeleted = cleanFiles($buildDir);
    if (file_exists($dockerFile)) $dockerFileDeleted = unlink($dockerFile);
    if ($buildDirDeleted && $dockerFileDeleted) $buildEnvDeleted = TRUE; }
  // / Delete the development environment if specified by config.php.
  if ($DeleteDevelopmentDocumentation) {
    if (file_exists($changelogFile)) $changelogFileDeleted = unlink($changelogFile);
    if (file_exists($readmeFile)) $readmeDeleted = unlink($readmeFile);
    if ($changelogFileDeleted && $readmeDeleted) $devDocsDeleted = TRUE; }
  // / Perform a check to see if any required operations failed. 
  if ($DeleteBuildEnvironment) if (!$buildEnvDeleted) $checkOne = TRUE;
  if ($DeleteDevelopmentDocumentation) if (!$devDocsDeleted) $checkTwo = TRUE;
  // / Installation is considered verified when check one & check two are both false.
  if (!$checkOne && !$checkTwo) $InstallationIsVerified = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $buildDir = $buildDirDeleted = $dockerFile = $dockerFileDeleted = $readmeFile = $readmeDeleted = $changelogFile = $changelogFileDeleted = $buildEnvDeleted = $devDocsDeleted = $checkOne = $checkTwo = NULL;
  unset($buildDir, $buildDirDeleted, $dockerFile, $dockerFileDeleted, $readmeFile, $readmeDeleted, $changelogFile, $changelogFileDeleted, $buildEnvDeleted, $devDocsDeleted, $checkOne, $checkTwo);
  return array($InstallationIsVerified, $ConfigFile, $Version); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to attempt to detect the users IP so it can be used as an identifier for the session.
function verifySession() {
  // / Set variables.
  $IP = '';
  $HashedUserAgent = hash('sha256', $_SERVER['HTTP_USER_AGENT']);
  $SessionIsVerified = TRUE;
  // / Detect an IP that we can use as an identifier for the session.
  if (!empty($_SERVER['HTTP_CLIENT_IP'])) $IP = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_SERVER['HTTP_CLIENT_IP']), ENT_QUOTES, 'UTF-8');
  elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) $IP = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_SERVER['HTTP_X_FORWARDED_FOR']), ENT_QUOTES, 'UTF-8');
  else $IP = htmlentities(str_replace('..', '', str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_SERVER['REMOTE_ADDR'])), ENT_QUOTES, 'UTF-8');
  return array($SessionIsVerified, $IP, $HashedUserAgent); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to define the $SesHash related variables for the session.
function verifySesHash($IP, $HashedUserAgent) {
  // / Set variables.
  global $Date, $Salts1, $Salts2, $Salts3, $Salts4, $Salts5, $Salts6, $Token1;
  if (is_string($Salts1) && is_string($Salts2) && is_string($Salts3) && is_string($Salts4) && is_string($Salts4) && is_string($Salts5) && is_string($Salts6)) {
    $SesHashIsVerified = TRUE;
    $SesHash = substr(hash('ripemd160', $Date.$Salts1.$Salts2.$Salts3.$Salts4.$Salts5.$Salts6), -12);
    $SesHash2 = substr(hash('ripemd160', $SesHash.$Token1.$Date.$IP.$HashedUserAgent.$Salts1.$Salts2.$Salts3.$Salts4.$Salts5.$Salts6), -12);
    $SesHash3 = $SesHash.'/'.$SesHash2;
    $SesHash4 = hash('ripemd160', $Salts6.$Salts5.$Salts4.$Salts3.$Salts2.$Salts1); }
  else $SesHashIsVerified = $SesHash = $SesHash2 = $SesHash3 = $SesHash4 = FALSE;
  return array($SesHashIsVerified, $SesHash, $SesHash2, $SesHash3, $SesHash4); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to create a logfile if one does not exist.
function verifyLogs() {
  // / Set variables.
  global $LogDir, $LogFile, $MaxLogSize, $InstLoc, $SesHash, $SesHash4, $DefaultLogDir, $DefaultLogSize, $Time, $Date, $LogInc, $LogInc2, $MaxLogSize, $LogDir, $LogFile, $VirusScan, $ApplicationName, $PermissionLevels;
  $LogExists = $logWritten = FALSE;
  $LogInc = $LogInc2 = 0;
  $LogFile = str_replace('..', '', $LogDir.'/'.$ApplicationName.'_'.$LogInc.'_'.$Date.'_'.$SesHash4.'_'.$SesHash.'.txt');
  $DefaultLogDir = $InstLoc.'/Logs';
  $DefaultLogSize = 1048576;
  $ClamLogFile = str_replace('..', '', $LogDir.'/ClamLog_'.$LogInc2.'_'.$Date.'_'.$SesHash4.'_'.$SesHash.'.txt');
  if (!is_numeric($MaxLogSize)) $MaxLogSize = $DefaultLogSize;
  if (!is_dir($LogDir)) @mkdir($LogDir, $PermissionLevels);
  if (!is_dir($LogDir)) $LogDir = $DefaultLogDir;
  if (!is_dir($LogDir)) die('ERROR!!! '.$Time.': '.$ApplicationName.'-3, The log directory does not exist at '.$LogDir.'.');
  if (!file_exists($LogDir.'/index.html')) @copy('index.html', $LogDir.'/index.html');
  // / Create a log file depending on whether or not the max filesize has been reached.
  while (file_exists($LogFile) && round((filesize($LogFile) / $MaxLogSize), 2) > $MaxLogSize) {
    $LogInc++;
    $LogFile = str_replace('..', '', $LogDir.'/'.$ApplicationName.'_'.$LogInc.'_'.$Date.'_'.$SesHash4.'_'.$SesHash.'.txt');
    $logWritten = file_put_contents($LogFile, 'OP-Act, '.$Time.': Logfile created using method 0.'.PHP_EOL, FILE_APPEND); }
  if (!file_exists($LogFile)) $logWritten = file_put_contents($LogFile, 'OP-Act, '.$Time.': Logfile created using method 1.'.PHP_EOL, FILE_APPEND);
  if (file_exists($LogFile)) $LogExists = TRUE;
  // / Set a clamlog file depending on whether or not the max filesize has been reached, but do not create one yet.
  if ($VirusScan) {
    while (file_exists($ClamLogFile) && round((filesize($ClamLogFile) / $MaxLogSize), 2) > $MaxLogSize) {
      $LogInc2++;
      $LogFile = str_replace('..', '', $LogDir.'/ClamLog_'.$LogInc2.'_'.$Date.'_'.$SesHash4.'_'.$SesHash.'.txt'); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $logWritten = NULL;
  unset($logWritten);
  return array($LogExists, $LogFile, $ClamLogFile); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to format a log entry & write it to the logfile.
function logEntry($entry) {
  // / Set variables.
  global $Time, $LogFile, $SesHash3;
  $LogWritten = FALSE;
  // / Format the input string into a log entry & write it to the $LogFile.
  $LogWritten = file_put_contents($LogFile, 'Op-Act, '.$Time.', '.$SesHash3.': '.$entry.PHP_EOL, FILE_APPEND);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $entry = NULL;
  unset($entry);
  return $LogWritten; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to format an error entry & write it to the logfile.
function errorEntry($entry, $errorNumber, $die) {
  // / Set variables.
  global $Time, $LogFile, $SesHash3, $ApplicationName;
  $LogWritten = FALSE;
  // / Format the error number into a unique error identifier.
  if (!is_numeric($errorNumber)) $errorNumber = $ApplicationName.'-###';
  else $errorNumber = $ApplicationName.'-'.$errorNumber;
  // / Format the input string into a log entry with the error number & write it to the $LogFile.
  $LogWritten = file_put_contents($LogFile, 'ERROR!!! '.$Time.', '.$errorNumber.', '.$SesHash3.': '.$entry.PHP_EOL, FILE_APPEND);
  if ($die) die('ERROR!!! '.$Time.' '.$errorNumber.': '.$entry.PHP_EOL);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $entry = $errorNumber = $die = NULL;
  unset($entry, $errorNumber, $die);
  return $LogWritten; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to set an echo variable that adjusts printed URL's to https when SSL is enabled.
function verifyEncryption() {
  $EncryptionVerified = TRUE;
  // / Determine if the connection is encrypted and adjust the $URLEcho accordingly.
  if (!empty($_SERVER['HTTPS']) && $_SERVER['SERVER_PORT'] == 443) $URLEcho = 's';
  else $URLEcho = '';
  return array($EncryptionVerified, $URLEcho); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to set or validate a Token so it can be used as a unique identifier for the session.
function verifyTokens($Token1, $Token2) {
  // / Verify variables.
  global $Salts1, $Salts2, $Salts3, $Salts4, $Salts5, $Salts6;
  $TokensAreValid = TRUE;
  // / Check that tokens are valid & set them if they are not.
  if (!isset($Token1) or $Token1 === '' or strlen($Token1) < 19) $Token1 = hash('ripemd160', rand(0, 1000000000).rand(0, 1000000000));
  if (isset($Token2)) if ($Token2 !== hash('ripemd160', $Token1.$Salts1.$Salts2.$Salts3.$Salts4.$Salts5.$Salts6)) $TokensAreValid = FALSE;
  if (!isset($Token2) or $Token2 === '' or strlen($Token2) < 19) $Token2 = hash('ripemd160', $Token1.$Salts1.$Salts2.$Salts3.$Salts4.$Salts5.$Salts6);
  return array($TokensAreValid, $Token1, $Token2); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify that all required POST & GET inputs are properly sanitized.
function verifyInputs() {
  // / Set variables.
  $var = FALSE;
  $InputsAreVerified = TRUE;
  $GUI = $Color = $Language = $Token1 = $Token2 = $Height = $Width = $Rotate = $Bitrate = $Method = $Download = $UserFilename = $UserExtension = $Archive = $UserScanType = $ScanAll = $UserClamScan = $UserScanCoreScan = $var = '';
  $variableIsSanitized = $ConvertSelected = $PDFWorkSelected = $FilesToArchive = $FilesToScan = $FilesToDelete = array();
  $key = 0;
  $ScanType = 'all';
  // / Sanitize each variable as needed & build a list of error check results.
  if (isset($_POST['noGui'])) $_GET['noGui'] = TRUE;
  if (isset($_POST['filesToDelete'])) list ($FilesToDelete, $variableIsSanitized[$key++]) = sanitize($_POST['filesToDelete'], TRUE);
  if (isset($_POST['language'])) list ($Language, $variableIsSanitized[$key++]) = sanitize($_POST['language'], TRUE);
  if (isset($_GET['language'])) list ($Language, $variableIsSanitized[$key++]) = sanitize($_GET['language'], TRUE);
  if (isset($_POST['color'])) list ($Color, $variableIsSanitized[$key++]) = sanitize($_POST['color'], TRUE);
  if (isset($_GET['color'])) list ($Color, $variableIsSanitized[$key++]) = sanitize($_GET['color'], TRUE);
  if (isset($_POST['gui'])) list ($GUI, $variableIsSanitized[$key++]) = sanitize($_POST['gui'], TRUE);
  if (isset($_GET['gui'])) list ($GUI, $variableIsSanitized[$key++]) = sanitize($_GET['gui'], TRUE);
  if (isset($_POST['Token1'])) list ($Token1, $variableIsSanitized[$key++]) = sanitize($_POST['Token1'], TRUE);
  if (isset($_POST['Token2'])) list ($Token2, $variableIsSanitized[$key++]) = sanitize($_POST['Token2'], TRUE);
  if (isset($_POST['height'])) list ($Height, $variableIsSanitized[$key++]) = sanitize($_POST['height'], TRUE);
  if (isset($_POST['width'])) list ($Width, $variableIsSanitized[$key++]) = sanitize($_POST['width'], TRUE);
  if (isset($_POST['rotate'])) list ($Rotate, $variableIsSanitized[$key++]) = sanitize($_POST['rotate'], TRUE);
  if (isset($_POST['bitrate'])) list ($Bitrate, $variableIsSanitized[$key++]) = sanitize($_POST['bitrate'], TRUE);
  if (isset($_POST['method'])) list ($Method, $variableIsSanitized[$key++]) = sanitize($_POST['method'], TRUE);
  if (isset($_POST['download'])) list ($Download, $variableIsSanitized[$key++]) = sanitize($_POST['download'], TRUE);
  if (isset($_POST['archive'])) list ($Archive, $variableIsSanitized[$key++]) = sanitize($_POST['archive'], TRUE);
  if (isset($_POST['extension'])) list ($UserExtension, $variableIsSanitized[$key++]) = sanitize($_POST['extension'], TRUE);
  if (isset($_POST['filesToArchive'])) list ($FilesToArchive, $variableIsSanitized[$key++]) = sanitize($_POST['filesToArchive'], TRUE);
  if (isset($_POST['archextension'])) list ($UserExtension, $variableIsSanitized[$key++]) = sanitize($_POST['archextension'], TRUE);
  if (isset($_POST['userfilename'])) list ($UserFilename, $variableIsSanitized[$key++]) = sanitize($_POST['userfilename'], TRUE);
  if (isset($_POST['userconvertfilename'])) list ($UserFilename, $variableIsSanitized[$key++]) = sanitize($_POST['userconvertfilename'], TRUE);
  if (isset($_POST['pdfworkSelected'])) list ($PDFWorkSelected, $variableIsSanitized[$key++]) = sanitize($_POST['pdfworkSelected'], TRUE);
  if (isset($_POST['convertSelected'])) list ($ConvertSelected, $variableIsSanitized[$key++]) = sanitize($_POST['convertSelected'], TRUE);
  if (isset($_POST['pdfextension'])) list ($UserExtension, $variableIsSanitized[$key++]) = sanitize($_POST['pdfextension'], TRUE);
  if (isset($_POST['userpdfconvertfilename'])) list ($UserFilename, $variableIsSanitized[$key++]) = sanitize($_POST['userpdfconvertfilename'], TRUE);
  if (isset($_POST['scanallbutton'])) list ($ScanAll, $variableIsSanitized[$key++]) = sanitize($_POST['scanallbutton'], TRUE);
  if (isset($_POST['scantype'])) list ($UserScanType, $variableIsSanitized[$key++]) = sanitize($_POST['scantype'], TRUE);
  if (isset($_POST['clamscanbutton'])) list ($UserClamScan, $variableIsSanitized[$key++]) = sanitize($_POST['clamscanbutton'], TRUE);
  if (isset($_POST['scancorebutton'])) list ($UserScanCoreScan, $variableIsSanitized[$key++]) = sanitize($_POST['scancorebutton'], TRUE);
  if (isset($_POST['filesToScan'])) list ($FilesToScan, $variableIsSanitized[$key++]) = sanitize($_POST['filesToScan'], TRUE);
  // / Handle when a user submits User Virus Scan options.
  if (isset($_POST['clamScanButton']) && isset($_POST['filesToScan'])) $ScanType = 'clamav';
  if (isset($_POST['scancorebutton']) && isset($_POST['filesToScan'])) $ScanType = 'scancore';
  if (isset($_POST['scanallbutton']) && isset($_POST['filesToScan'])) $ScanType = 'all';
  // / Check the list of error check results and see if any errors occured.
  foreach ($variableIsSanitized as $var) if (!$var) ($InputsAreVerified = FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $variableIsSanitized = $key = $var = NULL;
  unset($variableIsSanitized, $key, $var);
  return array($InputsAreVerified, $GUI, $Color, $Language, $Token1, $Token2, $Height, $Width, $Rotate, $Bitrate, $Method, $Download, $UserFilename, $UserExtension, $FilesToArchive, $PDFWorkSelected, $ConvertSelected, $FilesToScan, $FilesToDelete, $UserScanType); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to set the styles to use for the session.
function verifyColors($ButtonStyle) {
  // / Set variables.
  global $ButtonStyle, $Color, $SupportedColors, $AllowUserSelectableColor, $ColorToUse, $GreenButtonCode, $BlueButtonCode, $RedButtonCode, $DefaultButtonCode;
  $ColorsAreSet = FALSE;
  $ColorToUse = 'blue';
  $ButtonStyle = strtolower($ButtonStyle);
  $ButtonCode = $DefaultButtonCode;
  $validColors = array('green', 'blue', 'red', 'grey');
  // / Make sure $SupportedColors is valid.
  if (!isset($SupportedColors) or !is_array($SupportedColors)) $SupportedColors = $validColors;
  // / Make sure the Default Color is valid.
  if (isset($ButtonStyle)) if (in_array($ButtonStyle, $SupportedColors)) $ColorToUse = $ButtonStyle;
  // / If allowed and if specified, detect the users specified color and set that as the color to use.
  if (isset($AllowUserSelectableColor)) {
    if ($AllowUserSelectableColor) if (isset($Color)) if (in_array($Color, $SupportedColors)) {
      $ColorToUse = $Color; }
    if (!$AllowUserSelectableColor) $ButtonStyle = $DefaultColor; }
  // / Set the $Color variable to whatever the current color is so the next page will use the same one.
  $_GET['color'] = $ColorToUse;
  // / Validate the desired color and set it as the color to use if possible.
  if (in_array($ColorToUse, $validColors)) {
    $ColorsAreSet = TRUE;
    if ($ColorToUse === 'green') $ButtonCode = $GreenButtonCode;
    if ($ColorToUse === 'blue') $ButtonCode = $BlueButtonCode;
    if ($ColorToUse === 'red') $ButtonCode = $RedButtonCode;
    if ($ColorToUse === 'grey') $ButtonCode = $DefaultButtonCode; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $validColors = NULL;
  unset($validColors);
  return array($ColorsAreSet, $ButtonCode); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to set the GUI to use for the session.
function verifyGui() {
  // / Set variables.
  global $GUI, $DefaultGui, $SupportedGuis, $AllowUserSelectableGui, $GuiFiles, $GuiDir, $GuiResourcesDir, $GuiImageDir, $GuiCSSDir, $GuiJSDir, $GuiHeaderFile, $GuiFooterFile, $GuiUI1File, $GuiUI2File, $GreenButtonCode, $BlueButtonCode, $RedButtonCode, $DefaultButtonCode, $Font;
  $defaultGui = $reqFile =  $variableIsSanitized = FALSE;
  $GuiIsSet = TRUE;
  $GuiToUse = 'Default';
  $GuiFiles = $guiFiles = array();
  $defaultGuis = array('Default');
  // / Make sure $SupportedGuis is valid.
  if (!isset($SupportedGuis) or !is_array($SupportedGuis)) $SupportedGuis = $defaultGuis;
  // / Make sure the Default GUI is valid.
  if (isset($DefaultGui)) if (in_array($DefaultGui, $SupportedGuis)) $GuiToUse = $DefaultGui;
  // / If allowed and if specified, detect the users specified GUI and set that as the GUI to use.
  if (isset($AllowUserSelectableGui)) {
    if ($AllowUserSelectableGui) if (isset($GUI)) if (in_array($GUI, $SupportedGuis)) {
      $GuiToUse = $GUI; }
    if (!$AllowUserSelectableGui) $GuiToUse = $DefaultGui; }
  // / Set the $GUI variable to whatever the current GUI is so the next page will use the same one.
  $_GET['gui'] = $GuiToUse;
  // / Set the variables to a URL safe relative path to required UI files.
  $GuiDir = 'UI/'.$GuiToUse.'/';
  $StyleCoreFile = $GuiDir.'styleCore.php';
  $GuiHeaderFile = $GuiDir.'header.php';
  $GuiFooterFile = $GuiDir.'footer.php';
  $GuiUI1File = $GuiDir.'convertGui1.php';
  $GuiUI2File = $GuiDir.'convertGui2.php';
  $GuiResourcesDir = $GuiDir.'Resources/';
  $GuiImageDir = $GuiResourcesDir.'Image/';
  $GuiCSSDir = $GuiResourcesDir.'CSS/';
  $GuiJSDir = $GuiResourcesDir.'JS/';
  $guiFiles = array($GuiHeaderFile, $GuiFooterFile, $GuiUI1File, $GuiUI2File, $StyleCoreFile);
  // / Verify that the required GUI folder exists.
  if (is_dir($GuiDir)) $GuiIsSet = TRUE;
  // / Verify that required GUI files exist.
  foreach ($guiFiles as $reqFile) if (file_exists($reqFile)) array_push($GuiFiles, $reqFile);
  // / Determine if the styleCore.php file is part of the desired GUI, and load it if required.
  if (in_array($StyleCoreFile, $GuiFiles)) { 
    // / Load the styleCore.php file.
    require_once($StyleCoreFile);
    // / Set the variables for required color data.
    $GreenButtonCode = $greenButtonCode; 
    $BlueButtonCode = $blueButtonCode;
    $RedButtonCode = $redButtonCode; 
    $DefaultButtonCode = $defaultButtonCode; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $defaultGuis = $reqFile = $guiFiles = $greenButtonCode = $blueButtonCode = $redButtonCode = $defaultButtonCode = NULL;
  unset($defaultGuis, $reqFile, $guiFiles, $greenButtonCode, $blueButtonCode, $redButtonCode, $defaultButtonCode);
  return array($GuiIsSet, $GuiToUse, $GuiDir, $GuiFiles); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to set the language to use for the session.
function verifyLanguage() {
  // / Set variables.
  global $Language, $DefaultLanguage, $SupportedLanguages, $AllowUserSelectableLanguage, $LanguageFiles, $GuiDir, $LanguageDir, $LanguageStringsFile, $Language;
  $defaultLanguages = $reqFile = $variableIsSanitized = FALSE;
  $LanguageIsSet = TRUE;
  $LanguageToUse = 'en';
  $LanguageFiles = $languageFiles = array();
  $defaultLanguages = array('en', 'fr', 'es', 'zh', 'hi', 'ar', 'ru', 'uk', 'bn', 'de', 'ko', 'it', 'pt');
  // / Make sure $SupportedLanguages is valid.
  if (!isset($SupportedLanguages) or !is_array($SupportedLanguages)) $SupportedLanguages = $defaultLanguages;
  // / Make sure the Default Language is valid.
  if (isset($DefaultLanguage)) if (in_array($DefaultLanguage, $SupportedLanguages)) $LanguageToUse = $DefaultLanguage;
  // / If allowed and if specified, detect the users specified language and set that as the language to use.
  if (isset($AllowUserSelectableLanguage)) {
    if ($AllowUserSelectableLanguage) if (isset($Language)) if (in_array($Language, $SupportedLanguages)) {
      $LanguageToUse = $Language; }
    if (!$AllowUserSelectableLanguage) $LanguageToUse = $DefaultLanguage; }
  // / Set the $Language variable to whatever the current language is so the next page will use the same one.
  $_GET['language'] = $LanguageToUse;
  // / Set the variables to required UI files.
  $LanguageDir = $GuiDir.'Languages/'.$LanguageToUse.'/';
  $LanguageStringsFile = $LanguageDir.'languageStrings.php';
  $languageFiles = array($LanguageStringsFile);
  // / Verify that the required langauge folder exists.
  if (!is_dir($LanguageDir)) $LanguageIsSet = FALSE;
  // / Verify that required language files exist.
  if (file_exists($LanguageStringsFile)) $LanguageIsSet = TRUE;
  foreach ($languageFiles as $reqFile) if (file_exists($reqFile)) array_push($LanguageFiles, $reqFile);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $defaultLanguages = $reqFile = $variableIsSanitized = $languageFiles = NULL;
  unset($defaultLanguages, $reqFile, $variableIsSanitized, $languageFiles);
  return array($LanguageIsSet, $LanguageToUse, $LanguageDir, $LanguageFiles); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to reliably sanitize a path or URL of dangerous strings.
function securePath($PathToSecure, $DangerArr, $isURL) {
  // / Set variables.
  global $DirSep;
  // / Loop through each dangerous file & remove it from the supplied path.
  foreach ($DangerArr as $dArr) $PathToSecure = str_replace($dArr, '', $PathToSecure);
  // / Remove double directory separatorsthat may have been created during the last step.
  $PathToSecure = str_replace($DirSep.$DirSep, $DirSep, str_replace($DirSep.$DirSep, $DirSep, str_replace('..', '', $PathToSecure)));
  // / Detect if the path is a URL & remove double directory separators that may exist.
  if ($isURL) $PathToSecure = str_replace($DirSep, '/', $PathToSecure);
  // / Rempve double dots that may have been created during the last step.
  $PathToSecure = str_replace('..', '', $PathToSecure);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $dArr = $isURL = NULL;
  unset($dArr, $isURL);
  return $PathToSecure; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to set the global variables for the session.
function verifyGlobals() {
  // / Set global variables to be used through the entire application.
  global $URL, $URLEcho, $HRConvertVersion, $Date, $Time, $SesHash, $SesHash2, $SesHash3, $SesHash4, $CoreLoaded, $ConvertDir, $InstLoc, $ConvertTemp, $ConvertTempDir, $ConvertGuiCounter1, $DefaultApps, $RequiredDirs, $RequiredIndexes, $DangerousFiles, $Allowed, $ArchiveArray, $DearchiveArray, $DocumentArray, $SpreadsheetArray, $PresentationArray, $ImageArray, $MediaArray, $VideoArray, $StreamArray, $DrawingArray, $ModelArray, $SubtitleArray, $PDFWorkArr, $ConvertLoc, $DirSep, $SupportedConversionTypes, $Lol, $Lolol, $Append, $PathExt, $ConsolidatedLogFileName, $ConsolidatedLogFile, $Alert, $Alert1, $Alert2, $Alert3, $FCPlural, $FCPlural1, $FCPlural2, $FCPlural3, $UserClamLogFile, $UserClamLogFileName, $UserScanCoreLogFile, $UserScanCoreFileName, $SpinnerStyle, $SpinnerColor, $FullURL, $ServerRootDir, $StopCounter, $SleepTimer, $PermissionLevels, $ApacheUser, $File, $HeaderDisplayed, $UIDisplayed, $FooterDisplayed, $LanguageStringsLoaded, $GUIDisplayed, $Version, $GUIDirection, $SupportedFormatCount, $GUIAlignment, $GreenButtonCode, $BlueButtonCode, $RedButtonCode, $DefaultButtonCode, $UserArchiveArray, $UserDearchiveArray, $UserDocumentArray, $UserSpreadsheetArray, $UserPresentationArray, $UserImageArray, $UserMediaArray, $UserVideoArray, $UserStreamArray, $UserDrawingArray, $UserModelArray, $UserSubtitleArray, $UserPDFWorkArr, $RetryCount;
  // / Application related variables.
  $HRConvertVersion = 'v3.3.5';
  $GlobalsAreVerified = FALSE;
  $CoreLoaded = TRUE;
  $SleepTimer = 0;
  $StopCounter = $RetryCount;
  $PermissionLevels = 0755;
  $ApacheUser = 'www-data';
  // / Convinience variables.
  $DirSep = DIRECTORY_SEPARATOR;
  $Lol = PHP_EOL;
  $Lolol = $Lolol;
  $Append = FILE_APPEND;
  $PathExt = PATHINFO_EXTENSION;
  // / UI Related variables.
  $ConvertGuiCounter1 = 0;
  $File = $FCPlural = $FCPlural1 = $FCPlural2 = $FCPlural3 = $GreenButtonCode = $BlueButtonCode = $RedButtonCode = $DefaultButtonCode = '';
  $HeaderDisplayed = $UIDisplayed = $FooterDisplayed =$LanguageStringsLoaded = $GUIDisplayed = FALSE;
  $GUIDirection = 'ltr';
  $GUIAlignment = 'left';
  $Alert = 'Cannot convert this file! Try changing the name.';
  $Alert1 = 'Cannot perform a virus scan on this file!';
  $Alert2 = 'File Link Copied to Clipboard!';
  $Alert3 = 'Operation Failed!';
  // / Security related variables.
  $DefaultApps = array('.', '..');
  $DangerousFiles = array('js', 'php', '.html', 'css', 'phar', '.', '..', 'index.php', 'index.html');
  // / URL related variables.
  $subDir = securePath(str_replace($ServerRootDir.$DirSep, '', $InstLoc), $DangerousFiles, TRUE);
  $partURL = securePath($URL.'/'.$subDir, $DangerousFiles, TRUE);
  $FullURL = 'http'.$URLEcho.'://'.$partURL;
  // / Directory related variables.
  $convertDir0 = securePath($ConvertLoc.$DirSep.$SesHash, $DangerousFiles, $DangerousFiles, FALSE);
  $ConvertDir = securePath($convertDir0.$DirSep.$SesHash2.$DirSep, $DangerousFiles, FALSE);
  $ConvertTemp = securePath($InstLoc.'/DATA', $DangerousFiles, FALSE);
  $convertTempDir0 = securePath($ConvertTemp.$DirSep.$SesHash, $DangerousFiles, FALSE);
  $ConvertTempDir = securePath($convertTempDir0.$DirSep.$SesHash2.$DirSep, $DangerousFiles, FALSE);
  $RequiredDirs = array($convertDir0, $ConvertDir, $ConvertTemp, $convertTempDir0, $ConvertTempDir);
  $RequiredIndexes = array($ConvertTemp, $convertTempDir0, $ConvertTempDir);
  // / A/V related variables.
  $UserClamLogFileName = 'User_ClamScan_Virus_Scan_Report.txt';
  $UserClamLogFile = $ConvertDir.$UserClamLogFileName;
  $UserScanCoreFileName = 'User_ScanCore_Virus_Scan_Report.txt';
  $UserScanCoreLogFile = $ConvertDir.$UserScanCoreFileName;
  $ConsolidatedLogFileName = 'User_Consolidated_Virus_Scan_Report.txt';
  $ConsolidatedLogFile = $ConvertTempDir.$ConsolidatedLogFileName;
  // / Format related variables.
  $ArchiveArray = $DearchiveArray = $DocumentArray = $SpreadsheetArray = $PresentationArray = $ImageArray = $MediaArray = $VideoArray = $StreamArray = $DrawingArray = $ModelArray = $SubtitleArray = $PDFWorkArr = array();
  if (in_array('Archive', $SupportedConversionTypes)) $ArchiveArray = $UserArchiveArray;
  if (in_array('Archive', $SupportedConversionTypes)) $DearchiveArray = $UserArchiveArray;
  if (in_array('Document', $SupportedConversionTypes)) $DocumentArray = $UserDocumentArray;
  if (in_array('Document', $SupportedConversionTypes)) $SpreadsheetArray = $UserSpreadsheetArray;
  if (in_array('Document', $SupportedConversionTypes)) $PresentationArray = $UserPresentationArray;
  if (in_array('Image', $SupportedConversionTypes)) $ImageArray = $UserImageArray;
  if (in_array('Audio', $SupportedConversionTypes)) $MediaArray = $UserMediaArray;
  if (in_array('Video', $SupportedConversionTypes)) $VideoArray = $UserVideoArray;
  if (in_array('Stream', $SupportedConversionTypes) && in_array('Audio', $SupportedConversionTypes)) $StreamArray = $UserStreamArray;
  if (in_array('Drawing', $SupportedConversionTypes)) $DrawingArray = $UserDrawingArray;
  if (in_array('Model', $SupportedConversionTypes)) $ModelArray = $UserModelArray;
  if (in_array('Subtitle', $SupportedConversionTypes)) $SubtitleArray = $UserSubtitleArray;
  if (in_array('OCR', $SupportedConversionTypes) && in_array('Document', $SupportedConversionTypes)) $PDFWorkArr = $UserPDFWorkArr;
  $Allowed = array_unique(array_merge(array_merge(array_merge(array_merge(array_merge(array_merge(array_merge(array_merge(array_merge(array_merge(array_merge(array_merge($ArchiveArray, $DearchiveArray), $DocumentArray), $SpreadsheetArray), $PresentationArray), $ImageArray), $MediaArray), $VideoArray), $StreamArray), $DrawingArray), $ModelArray), $SubtitleArray), $PDFWorkArr));
  $SupportedFormatCount = count($Allowed);
  // / Perform a version integrity check.
  if ($HRConvertVersion === $Version) $GlobalsAreVerified = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $convertDir0 = $convertTempDir0 = $subDir = $partURL = NULL;
  unset($convertDir0, $convertTempDir0, $subDir, $partURL);
  return array($GlobalsAreVerified, $CoreLoaded); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to sanitize & verifie an array of files.
function getFiles($pathToFiles) {
  // / Set variables.
  global $DangerousFiles, $DirSep, $PathExt;
  $Files = $dirtyFileArr = array();
  if (is_dir($pathToFiles)) $dirtyFileArr = @scandir($pathToFiles);
  // / Iterate through each detected file & make sure it's not dangerous before adding it to the output array.
  foreach ($dirtyFileArr as $dirtyFile) {
    $dirtyExt = pathinfo($pathToFiles.$DirSep.$dirtyFile, $PathExt);
    // / Make sure the file is safe to handle.
    if (in_array(strtolower($dirtyExt), $DangerousFiles) or is_dir($pathToFiles.$DirSep.$dirtyFile)) continue;
    array_push($Files, $dirtyFile); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $dirtyFile = $pathToFiles = $dirtyFileArr = NULL;
  unset($dirtyFile, $pathToFiles, $dirtyFileArr);
  return $Files; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to return the extension to a specified file.
function getExtension($pathToFile) {
  // / Set variables.
  global $PathExt;
  $Pathinfo = pathinfo($pathToFile, $PathExt);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $pathToFile = NULL;
  unset($pathToFile);
  return $Pathinfo;  }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to return the filesize of a specified file.
function getFilesize($File) {
  // / Set variables.
  $Size = @filesize($File);
  // / Determine the most efficient unit of measure to represent the specified value in.
  if ($Size < 1024) $Size = $Size." Bytes";
  elseif (($Size < 1048576) && ($Size > 1023)) $Size = round($Size / 1024, 1)." KB";
  elseif (($Size < 1073741824) && ($Size > 1048575)) $Size = round($Size / 1048576, 1)." MB";
  else $Size = round($Size/1073741824, 1)." GB";
  return $Size; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to return the file time of a specified symlink.
function symlinkmtime($symlinkPath) {
  // / Set variables.
  $Stat = @lstat($symlinkPath);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $symlinkPath = NULL;
  unset($symlinkPath);
  return isset($Stat['mtime']) ? $Stat['mtime'] : NULL; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to return the file time of a specified file.
// / Only returns a value if the specified file exists.
function fileTime($filePath) {
  if (file_exists($filePath)) $Stat = @filemtime($filePath);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $filePath = NULL;
  unset($filePath);
  return $Stat; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to test if a folder is empty.
// / Returns TRUE when a folder is empty.
// / Returns FALSE when a folder is not empty.
function is_dir_empty($dir) {
  // / Set variables.
  $Check = TRUE;
  // / Make sure the selected directory is actually a directory.
  if (is_dir($dir)) {
    // / Gather the contents of the directory.
    $contents = scandir($dir);
    // / Iterate through the contents of the directory & break once any valid file is found.
    foreach ($contents as $content) if ($content == '.' or $content == '..') $Check = FALSE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $dir = $contents = $content = NULL;
  unset($dir, $contents, $content); 
  return $Check; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to scan an input file or folder for viruses with ClamAV.
function virusScan($path) {
  // / Set variables.
  global $Verbose, $ClamLogFile, $AllowUserVirusScan, $Lol, $Lolol, $ApplicationName;
  $ScanComplete = TRUE;
  $VirusFound = FALSE;
  $returnData = '';
  $returnData = shell_exec(str_replace('  ', ' ', str_replace('  ', ' ', 'clamscan -r '.$path.' | grep FOUND >> '.$ClamLogFile)));
  $clamLogFileDATA = @file_get_contents($ClamLogFile);
  // / Check if ClamAV found an infection in the specified file.
  if (strpos($clamLogFileDATA, 'Virus Detected') !== FALSE or strpos($clamLogFileDATA, 'FOUND') !== FALSE) {
    $ScanComplete = $virusFound = TRUE;
    // / If the specified file exists, is infected, is not a directory, & $AllowUserVirusScan is set to FALSE then delete the infected file. 
    if (file_exists($path)) if (is_file($path) && !is_dir($path) && !$AllowUserVirusScan) @unlink($path);
    errorEntry('There were potentially infected files detected at '.$path.'!', 500, FALSE);
    errorEntry('ClamAV output the following: '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))), 501, TRUE); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $returnData = $clamLogFileDATA = $path = NULL;
  unset($returnData, $clamLogFileDATA, $path);
  return array($ScanComplete, $VirusFound); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to create required directories if they do not exist.
function verifyRequiredDirs() {
  // /  Set variables.
  global $ConvertLoc, $RequiredDirs, $RequiredIndexes, $Time, $LogFile, $Verbose, $PermissionLevels;
  $RequiredDirsExist = FALSE;
  // / If the $ConvertLoc does not exist we stop execution rather than create one.
  if (!is_dir($ConvertLoc)) errorEntry('The specified Data Storage Directory does not exist at '.$ConvertLoc.'!', 1000, TRUE);
  // / Iterate through the array of required directories.
  foreach ($RequiredDirs as $requiredDir) {
    // / Check that the currently selected directory exists.
    if (!is_dir($requiredDir)) {
      if ($Verbose) logEntry('Created a directory at '.$requiredDir.'.');
      // / Try to create the currently selected directory.
      @mkdir($requiredDir, $PermissionLevels); }
    // / Re-check to see if our attempt to create the directory was successful & log the result.
    if (is_dir($requiredDir)) $RequiredDirsExist = TRUE;
    else errorEntry('Could not create a directory at '.$requiredDir.'!', 1001, TRUE); }
  // / Make sure that each required directory has an index.html file for document root protection.
  foreach ($RequiredIndexes as $requiredIndex) @copy('index.html', $requiredIndex.'/index.html');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $requiredDir = $requiredIndex = $MAKELogFile = NULL;
  unset($requiredDir, $requiredIndex, $MAKELogFile); 
  return array($RequiredDirsExist, $RequiredDirs); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to clean a selection of files.
// / Recursively deletes files.
// / This function is extremely dangerous! Please handle with care.
function cleanFiles($path) {
  // / Set variables.
  global $ConvertLoc, $ConvertTemp, $DefaultApps, $DirSep;
  $variableIsSanitized = $i = $f = $CleanSuccess = $pathCheck = $loopCheck = FALSE;
  list ($path, $variableIsSanitized) = sanitize($path, FALSE);
  // / Make sure the selected directory is actually a directory.
  if ($variableIsSanitized && is_dir($path)) {
    $i = array_diff(scandir($path), array('..', '.'));
    // / Iterate through each file object in the directory.
    foreach ($i as $f) {
      // / If the selected file object is a file, delete it.
      if (is_file($path.$DirSep.$f) && !in_array(basename($path.$DirSep.$f), $DefaultApps)) @unlink($path.$DirSep.$f);
      // / If the selected file object is a directory, try to delete it.
      if (is_dir($path.$DirSep.$f) && !in_array(basename($path.$DirSep.$f), $DefaultApps) && is_dir_empty($path)) @rmdir($path.$DirSep.$f);
      // / If the selected file object is a directory that still exists, run this function on it to remove any file objects it contains.
      if (is_dir($path.$DirSep.$f) && !in_array(basename($path.$DirSep.$f), $DefaultApps) && !is_dir_empty($path)) $loopCheck = cleanFiles($path.$DirSep.$f); }
    // / Once all file objects in the selected directory have been deleted, attempt to delete the selected directory.
    if ($path !== $ConvertLoc && $path !== $ConvertTemp) @rmdir($path); }
  // / Check if the function was successful. Note that $ConvertLoc and $ConvertTemp locations are not deleted..
  $pathCheck = is_dir($path);
  if ($pathCheck) if (is_dir_empty($path)) $CleanSuccess = TRUE;
  if (!$pathCheck) $CleanSuccess = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $path = $i = $f = $variableIsSanitized = $pathCheck = $loopCheck = NULL;
  unset($path, $i, $f, $variableIsSanitized, $pathCheck, $loopCheck); 
  return $CleanSuccess; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to clean up old files in the $TempLoc.
function cleanTempLoc() {
  // / Set variables.
  global $ConvertTemp, $DeleteThreshold, $DefaultApps, $DirSep, $PermissionLevels;
  $TempLocDeepCleaned = FALSE;
  $CleanedTempLoc = $loopCheck = TRUE;
  // / Make sure the directory to be scanned exists.
  if (file_exists($ConvertTemp)) {
    $dFiles = array_diff(scandir($ConvertTemp), array('..', '.'));
    $now = time();
    // / Iterate through each subfolder in the directory.
    foreach ($dFiles as $dFile) {
      // / Validate the folder.
      if (in_array($dFile, $DefaultApps)) continue;
      $dFilePath = $ConvertTemp.$DirSep.$dFile;
      if ($dFilePath == $ConvertTemp.'/index.html') continue;
      // / See if the folder is due for deletion.
      if ($now - fileTime($dFilePath) > ($DeleteThreshold * 60)) {
        // / If the folder is due to be deleted, recursively delete it.
        if (is_dir($dFilePath)) {
          $TempLocDeepCleaned = TRUE;
          @chmod ($dFilePath, $PermissionLevels);
          $loopCheck = cleanFiles($dFilePath);
          if (is_dir_empty($dFilePath)) @rmdir($dFilePath); } }
      // / Check if the most recent iteration of the loop was successful.
      if (!$loopCheck) $CleanedTempLoc = FALSE; $loopCheck = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $dFiles = $dFile = $dFilePath = $now = $loopCheck = NULL;
  unset($dFiles, $dFile, $dFilePath, $now, $loopCheck);
  return array($CleanedTempLoc, $TempLocDeepCleaned); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to clean up old files in the $ConvertLoc.
function cleanConvertLoc() {
  // / Set variables.
  global $ConvertLoc, $DeleteThreshold, $DefaultApps, $DirSep, $PermissionLevels;
  $ConvertLocDeepCleaned = FALSE;
  $CleanedConvertLoc = $loopCheck = TRUE;
  // / Make sure the directory to be scanned exists.
  if (file_exists($ConvertLoc)) {
    $dFiles = array_diff(scandir($ConvertLoc), array('..', '.'));
    $now = time();
    // / Iterate through each subfolder in the directory.
    foreach ($dFiles as $dFile) {
      // / Validate the folder.
      if (in_array($dFile, $DefaultApps)) continue;
      $dFilePath = $ConvertLoc.$DirSep.$dFile;
      // / See if the folder is due for deletion.
      if ($now - fileTime($dFilePath) > ($DeleteThreshold * 60)) {
        // / If the folder is due to be deleted, recursively delete it.
        if (is_dir($dFilePath)) {
          $ConvertLocDeepCleaned = TRUE;
          @chmod ($dFilePath, $PermissionLevels);
          $loopCheck = cleanFiles($dFilePath);
          if (is_dir_empty($dFilePath)) @rmdir($dFilePath); } }
      // / Check if the most recent iteration of the loop was successful.
      if (!$loopCheck) $CleanedConvertLoc = FALSE; $loopCheck = TRUE; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $dFiles = $dFile = $dFilePath = $now = $loopCheck = NULL;
  unset($dFiles, $dFile, $dFilePath, $now, $loopCheck);
  return array($CleanedConvertLoc, $ConvertLocDeepCleaned); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify that the document conversion engine is installed & running.
function verifyDocumentConversionEngine() {
  // / Set variables.
  global $Verbose, $Lol, $Lolol;
  $DocEnginePID = 0;
  $DocumentEngineStarted = FALSE;
  $returnData = '';
  // / Determine if the document conversion engine (Unoconv) is installed.
  if (!file_exists('/usr/bin/unoconv')) errorEntry('Could not verify the document conversion engine installation at /usr/bin/unoconv!', 2000, TRUE);
  if (file_exists('/usr/bin/unoconv')) {
    if ($Verbose) logEntry('Verified the document conversion engine installation.');
    $DocEnginePID = shell_exec('pgrep soffice.bin');
    if ($Verbose) logEntry('The document conversion engine PID is: '.str_replace($Lol, '', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($DocEnginePID)))));
    // / Determine if the document conversion engine is already running.
    if ($DocEnginePID === 0 or $DocEnginePID === '' or $DocEnginePID === NULL or !$DocEnginePID) {
      // / Try to start the document conversion engine.
      if ($Verbose)logEntry('Starting the document conversion engine.');
      $returnData = shell_exec('/usr/bin/unoconv -l &');
      if ($Verbose && trim($returnData) !== '') logEntry('The document conversion engine PID is: '.str_replace($Lol, '', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($DocEnginePID))))); } }
  $DocumentEnginePID = trim($DocEnginePID);
  // / Write the document engine PID to the log file.
  if ($DocEnginePID !== 0 && $DocEnginePID !== '' && $DocEnginePID !== NULL) {
    $DocumentEngineStarted = TRUE;
    if ($Verbose) logEntry('The document conversion engine is running.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $returnData = NULL;
  unset($returnData);
  return array($DocumentEngineStarted, $DocEnginePID); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert document formats.
function convertDocuments($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer;
  $ConversionSuccess = $ConversionErrors = FALSE;
  $returnData = '';
  $stopper = 0;
  $sleepTime = $SleepTimer;
  // / The following code verifies that the document conversion engine is installed & running.
  list ($documentEngineStarted, $documentEnginePID) = verifyDocumentConversionEngine();
  if (!$documentEngineStarted) {
    $ConversionErrors = TRUE;
    errorEntry('Could not verify the document conversion engine!', 7000, FALSE); }
  else if ($Verbose) logEntry('Verified the document conversion engine.');
  // / The following code performs the actual document conversion.
  if ($documentEngineStarted) {
    if ($Verbose) logEntry('Converting document.');
    // / This code will attempt the conversion up to $StopCounter number of times.
    while (!file_exists($newPathname) && $stopper <= $StopCounter) {
      // / If the last conversion attempt failed, wait a moment before trying again.
      if ($stopper !== 0) sleep($sleepTime++);
      // / Attempt the conversion.
      $returnData = shell_exec('unoconv -o '.$newPathname.' -f '.$extension.' '.$pathname);
      // / Count the number of conversions to avoid infinite loops.
      $stopper++;
      // / Stop attempting the conversion after $StopCounter number of attempts.
      if ($stopper === $StopCounter) {
        $ConversionErrors = TRUE;
        errorEntry('The document converter timed out!', 7001, FALSE); } }
    if ($Verbose && trim($returnData) !== '') logEntry('Unoconv returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData))))); }
  if (file_exists($newPathname)) $ConversionSuccess = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $stopper = $pathname = $newPathname = $extension = $returnData = $documentEngineStarted = $documentEnginePID = $sleepTime = NULL;
  unset($stopper, $pathname, $newPathname, $extension, $returnData, $documentEngineStarted, $documentEnginePID, $sleepTime);
  return array($ConversionSuccess, $ConversionErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert image formats.
function convertImages($pathname, $newPathname, $height, $width, $rotate) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer;
  $ConversionSuccess = $ConversionErrors = $imgMethod = FALSE;
  $returnData = $wh = '';
  $stopper = $whx = 0;
  $sleepTime = $SleepTimer;
  // / Validate the height, width, & rotate arguments.
  if (!is_numeric($height) or $height === FALSE) $height = 0;
  if (!is_numeric($width) or $width === FALSE) $width = 0;
  if (!is_numeric($rotate) or $rotate === FALSE) '-rotate '.$rotate;
  $wxh = $width.'x'.$height;
  if ($wxh == '0x0' or $wxh =='x0' or $wxh == '0x' or $wxh == '0' or $wxh == '00' or $wxh == '' or $wxh == ' ') $wh = '';
  else $wh = '-resize '.$wxh.' ';
  if ($Verbose) logEntry('Converting image.');
  // / This code will attempt the conversion up to $StopCounter number of times.
  while (!file_exists($newPathname) && $stopper <= $StopCounter) {
    // / If the last conversion attempt failed, wait a moment before trying again.
    if ($stopper !== 0) sleep($sleepTime++);
    // / Attempt the conversion.
    $returnData = shell_exec('convert -background none '.$wh.$rotate.' '.$pathname.' '.$newPathname);
    // / Count the number of conversions to avoid infinite loops.
    $stopper++;
    // / Stop attempting the conversion after $StopCounter number of attempts.
    if ($stopper === $StopCounter) {
      $ConversionErrors = TRUE;
      errorEntry('The image converter timed out!', 8000, FALSE); } }
  if ($Verbose && trim($returnData) !== '') logEntry('ImageMagick returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
  if (file_exists($newPathname)) $ConversionSuccess = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $returnData = $stopper = $pathname = $newPathname = $height = $width = $extension = $wxh = $rotate = $imgMethod = $wh = $sleepTime = NULL;
  unset($returnData, $stopper, $pathname, $newPathname, $height, $width, $extension, $wxh, $rotate, $imgMethod, $wh, $sleepTime);
  return array($ConversionSuccess, $ConversionErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert 3D model formats.
function convertModels($pathname, $newPathname) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer;
  $ConversionSuccess = $ConversionErrors = FALSE;
  $returnData = '';
  $stopper = 0;
  $sleepTime = $SleepTimer;
  if ($Verbose) logEntry('Converting model.');
  // / This code will attempt the conversion up to $StopCounter number of times.
  while (!file_exists($newPathname) && $stopper <= $StopCounter) {
    // / If the last conversion attempt failed, wait a moment before trying again.
    if ($stopper !== 0) sleep($sleepTime++);
    // / Attempt the conversion.
    $returnData = shell_exec('meshlabserver -i '.$pathname.' -o '.$newPathname);
    // / Count the number of conversions to avoid infinite loops.
    $stopper++;
    // / Stop attempting the conversion after $StopCounter number of attempts.
    if ($stopper === $StopCounter) {
      $ConversionErrors = TRUE;
      errorEntry('The model converter timed out!', 9000, FALSE); } }
  if ($Verbose && trim($returnData) !== '') logEntry('Meshlab returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
  if (file_exists($newPathname)) $ConversionSuccess = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $returnData = $stopper = $pathname = $newPathname = NULL;
  unset($returnData, $stopper, $pathname, $newPathname);
  return array($ConversionSuccess, $ConversionErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert 2D vector drawing formats.
function convertDrawings($pathname, $newPathname) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer;
  $ConversionSuccess = $ConversionErrors = FALSE;
  $returnData = '';
  $stopper = 0;
  $sleepTime = $SleepTimer;
  if ($Verbose) logEntry('Converting drawing.');
  // / This code will attempt the conversion up to $StopCounter number of times.
  while (!file_exists($newPathname) && $stopper <= $StopCounter) {
    // / If the last conversion attempt failed, wait a moment before trying again.
    if ($stopper !== 0) sleep($sleepTime++);
    // / Attempt the conversion.
    $returnData = shell_exec('dia '.$pathname.' -e '.$newPathname);
    // / Count the number of conversions to avoid infinite loops.
    $stopper++;
    // / Stop attempting the conversion after $StopCounter number of attempts.
    if ($stopper === $StopCounter) { 
      $ConversionErrors = TRUE;
      errorEntry('The drawing converter timed out!', 10000, FALSE); } }
  if ($Verbose && trim($returnData) !== '') logEntry('Dia returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
  if (file_exists($newPathname)) $ConversionSuccess = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $returnData = $stopper = $pathname = $newPathname = $sleepTime = NULL;
  unset($returnData, $stopper, $pathname, $newPathname, $sleepTime);
  return array($ConversionSuccess, $ConversionErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert video formats.
function convertVideos($pathname, $newPathname) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer;
  $ConversionSuccess = $ConversionErrors = FALSE;
  $returnData = '';
  $stopper = 0;
  $sleepTime = $SleepTimer;
  if ($Verbose) logEntry('Converting video.');
  // / This code will attempt the conversion up to $StopCounter number of times.
  while (!file_exists($newPathname) && $stopper <= $StopCounter) {
    // / If the last conversion attempt failed, wait a moment before trying again.
    if ($stopper !== 0) sleep($sleepTime++);
    // / Attempt the conversion.
    $returnData = shell_exec('ffmpeg -i '.$pathname.' -c:v libx264 '.$newPathname);
    // / Count the number of conversions to avoid infinite loops.
    $stopper++;
    // / Stop attempting the conversion after $StopCounter number of attempts.
    if ($stopper === $StopCounter) {
      $ConversionErrors = TRUE;
      errorEntry('The video converter timed out!', 11000, FALSE); } }
  if ($Verbose && trim($returnData) !== '') logEntry('Ffmpeg returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
  if (file_exists($newPathname)) $ConversionSuccess = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $returnData = $stopper = $pathname = $newPathname = $sleepTime = NULL;
  unset($returnData, $stopper, $pathname, $newPathname, $sleepTime);
  return array($ConversionSuccess, $ConversionErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert subtitle formats.
function convertSubtitles($pathname, $newPathname) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer;
  $ConversionSuccess = $ConversionErrors = FALSE;
  $returnData = '';
  $stopper = 0;
  $sleepTime = $SleepTimer;
  if ($Verbose) logEntry('Converting subtitle.');
  // / This code will attempt the conversion up to $StopCounter number of times.
  while (!file_exists($newPathname) && $stopper <= $StopCounter) {
    // / If the last conversion attempt failed, wait a moment before trying again.
    if ($stopper !== 0) sleep($sleepTime++);
    // / Attempt the conversion.
    $returnData = shell_exec('ffmpeg -i '.$pathname.' '.$newPathname);
    // / Count the number of conversions to avoid infinite loops.
    $stopper++;
    // / Stop attempting the conversion after $StopCounter number of attempts.
    if ($stopper === $StopCounter) {
      $ConversionErrors = TRUE;
      errorEntry('The subtitle converter timed out!', 22000, FALSE); } }
  if ($Verbose && trim($returnData) !== '') logEntry('Ffmpeg returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
  if (file_exists($newPathname)) $ConversionSuccess = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $returnData = $stopper = $pathname = $newPathname = $sleepTime = NULL;
  unset($returnData, $stopper, $pathname, $newPathname, $sleepTime);
  return array($ConversionSuccess, $ConversionErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert stream formats.
function convertStreams($pathname, $newPathname) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer;
  $ConversionSuccess = $ConversionErrors = FALSE;
  $returnData = '';
  $stopper = 0;
  $sleepTime = $SleepTimer;
  if ($Verbose) logEntry('Converting stream.');
  // / This code will attempt the conversion up to $StopCounter number of times.
  while (!file_exists($newPathname) && $stopper <= $StopCounter) {
    // / If the last conversion attempt failed, wait a moment before trying again.
    if ($stopper !== 0) sleep($sleepTime++);
    // / Attempt the conversion.
    $returnData = shell_exec('ffmpeg -protocol_whitelist file,http,https,tcp,tls,crypto -i '.$pathname.' -c copy '.$newPathname);
    // / Count the number of conversions to avoid infinite loops.
    $stopper++;
    // / Stop attempting the conversion after $StopCounter number of attempts.
    if ($stopper === $StopCounter) {
      $ConversionErrors = TRUE;
      errorEntry('The stream converter timed out!', 21000, FALSE); } }
  if ($Verbose && trim($returnData) !== '') logEntry('Ffmpeg returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
  if (file_exists($newPathname)) $ConversionSuccess = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $returnData = $stopper = $pathname = $newPathname = $sleepTime = NULL;
  unset($returnData, $stopper, $pathname, $newPathname, $sleepTime);
  return array($ConversionSuccess, $ConversionErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert audio formats.
function convertAudio($pathname, $newPathname, $extension, $bitrate) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer;
  $ConversionSuccess = $ConversionErrors = FALSE;
  $returnData = '';
  $stopper = 0;
  $sleepTime = $SleepTimer;

if ($extension === 'mkv') $extension = 'matroska';

$AudioIn = array('');
$AudioOut = array('');
  $ext = ' -f '.$extension;


  // / Determine if the bitrate is being set.
  if (!is_numeric($bitrate) or $bitrate === FALSE) $bitrate = 'auto';
  if ($bitrate = 'auto') $br = ' ';
  elseif ($bitrate != 'auto' ) $br = (' -b:'.$bitrate.' ');
  $ConversionSuccess = $ConversionErrors = FALSE;
  if ($Verbose) logEntry('Converting audio.');
  // / This code will attempt the conversion up to $StopCounter number of times.
  while (!file_exists($newPathname) && $stopper <= $StopCounter) {
    // / If the last conversion attempt failed, wait a moment before trying again.
    if ($stopper !== 0) sleep($sleepTime++);
    // / Attempt the conversion.
    logEntry('ffmpeg -y -i '.$pathname.$ext.$br.$newPathname);
    $returnData = shell_exec('ffmpeg -y -i '.$pathname.$ext.$br.$newPathname);
    // / Count the number of conversions to avoid infinite loops.
    $stopper++;
    // / Stop attempting the conversion after $StopCounter number of attempts.
    if ($stopper === $StopCounter) {
      $ConversionErrors = TRUE;
      errorEntry('The audio converter timed out!', 12000, FALSE); } }
  if ($Verbose && trim($returnData) !== '') logEntry('Ffmpeg returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
  if (file_exists($newPathname)) $ConversionSuccess = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $returnData = $stopper = $pathname = $newPathname = $ext = $extension = $sleepTime = NULL;
  unset($returnData, $stopper, $pathname, $newPathname, $ext, $extension, $sleepTime);
  return array($ConversionSuccess, $ConversionErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert archive & disk image formats.
function convertArchives($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $ConvertDir, $Lol, $Lolol, $StopCounter, $SleepTimer, $PathExt, $PermissionLevels, $RARArchiveMethod;
  $ConversionSuccess = $ConversionErrors = FALSE;
  $returnData = '';
  $filename = pathinfo($pathname, PATHINFO_FILENAME);
  $safedir2 = $ConvertDir.$filename;
  $safedir3 = $safedir2.'.7z';
  $safedir4 = $safedir2.'.zip';
  $array7zo = array('7z');
  $arrayzipo = array('zip');
  $array7zo2 = array('vhd', 'vdi', 'iso');
  $arraytaro = array('tar.gz', 'tar.bz2', 'tar');
  $arrayraro = array('rar');
  $rarMethod = 'other';
  $stopper = 0;
  $sleepTime = $SleepTimer;
  $oldExtension =  pathinfo($pathname, $PathExt);
  // / Create a folder to contain extracted files.
  @mkdir($safedir2, $PermissionLevels);
  if (!is_dir($safedir2)) $ConversionErrors = TRUE;
  // / Check if output files already exist & delete them if they do.
  if (file_exists($safedir3)) @unlink($safedir3);
  if (file_exists($safedir4)) @unlink($safedir4);
  if ($Verbose) logEntry('Extracting file '.$pathname,' to '.$safedir2.'.');
  // / Code to Extract the selected archive.
  // / Currently only 7z is used, but this code exists to give flexibility.
  // / At one time I tried using zip for zip, rar for rar, ect.
  // / I determined that 7z was the most reliable in all cases.
  // / However that may some day change, so the code exists to allow future granularity.
  if (in_array(strtolower($oldExtension), $arrayzipo)) $returnData = shell_exec('7z x -aoa '.$pathname.' -o'.$safedir2);
  if (in_array(strtolower($oldExtension), $array7zo)) $returnData = shell_exec('7z x -aoa '.$pathname.' -o'.$safedir2);
  if (in_array(strtolower($oldExtension), $array7zo2)) $returnData = shell_exec('7z x -y '.$pathname.' -o'.$safedir2);
  if (in_array(strtolower($oldExtension), $arrayraro)) $returnData = shell_exec('7z x -aoa '.$pathname.' -o'.$safedir2);
  if (in_array(strtolower($oldExtension), $arraytaro)) $returnData = shell_exec('7z x -aoa '.$pathname.' -o'.$safedir2);
  if ($Verbose) logEntry('The extractor returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
  if ($Verbose) logEntry('Archiving file '.$safedir2.' to '.$newPathname.'.');
  // / Code to rearchive archive files using 7z.
  if (in_array($extension, $array7zo)) {
    // / This code will attempt the archive operation up to $StopCounter number of times.
    while ($stopper <= $StopCounter) {
      // / If the last conversion attempt failed, wait a moment before trying again.
      if ($stopper !== 0) sleep($sleepTime++);
      // / Attempt the conversion.
      $returnData = shell_exec('7z a -t'.$extension.' '.$newPathname.' '.$safedir2);
      if ($Verbose && trim($returnData) !== '') logEntry('The archiver returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
      // / Count the number of conversions to avoid infinite loops.
      $stopper++;
    // / Stop attempting the archive operation after $StopCounter number of attempts.
      if ($stopper === $StopCounter) {
        $ConversionErrors = TRUE;
        errorEntry('The archiver timed out!', 13001, FALSE); } } }
  // / Code to rearchive disk image files using mkisofs.
  if (in_array($extension, $array7zo2)) {
    // / This code will attempt the archive operation up to $StopCounter number of times.
    while ($stopper <= $StopCounter) {
      // / If the last conversion attempt failed, wait a moment before trying again.
      if ($stopper !== 0) sleep($sleepTime++);
      // / Attempt the conversion.
      $returnData = shell_exec('mkisofs -o '.$newPathname.' '.$safedir2);
      if ($Verbose && trim($returnData) !== '') logEntry('The archiver returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
      // / Count the number of conversions to avoid infinite loops.
      $stopper++;
    // / Stop attempting the archive operation after $StopCounter number of attempts.
      if ($stopper === $StopCounter) {
        $ConversionErrors = TRUE;
        errorEntry('The archiver timed out!', 13002, FALSE); } } }
  // / Code to rearchive archive files using zip.
  if (in_array($extension, $arrayzipo)) {
    // / This code will attempt the archive operation up to $StopCounter number of times.
    while ($stopper <= $StopCounter) {
      // / If the last conversion attempt failed, wait a moment before trying again.
      if ($stopper !== 0) sleep($sleepTime++);
      // / Attempt the conversion.
      $returnData = shell_exec('zip -r -j '.$newPathname.' '.$safedir2);
      if ($Verbose && trim($returnData) !== '') logEntry('The archiver returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
      // / Count the number of conversions to avoid infinite loops.
      $stopper++;
    // / Stop attempting the archive operation after $StopCounter number of attempts.
      if ($stopper === $StopCounter) {
        $ConversionErrors = TRUE;
        errorEntry('The archiver timed out!', 13003, FALSE); } } }
  // / Code to rearachive archive files using tar.
  if (in_array($extension, $arraytaro)) {
    // / This code will attempt the archive operation up to $StopCounter number of times.
    while ($stopper <= $StopCounter) {
      // / If the last conversion attempt failed, wait a moment before trying again.
      if ($stopper !== 0) sleep($sleepTime++);
      // / Attempt the conversion.
      $returnData = shell_exec('tar -cjf '.$newPathname.' -C '.$safedir2.' .');
      if ($Verbose && trim($returnData) !== '') logEntry('The archiver returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
      // / Count the number of conversions to avoid infinite loops.
      $stopper++;
    // / Stop attempting the archive operation after $StopCounter number of attempts.
      if ($stopper === $StopCounter) {
        $ConversionErrors = TRUE;
        errorEntry('The archiver timed out!', 13004, FALSE); } } }
  // / Code to rearchive archive files using rar.
  if (in_array($extension, $arrayraro)) {
    if ($RARArchiveMethod === 'rar' && file_exists('/usr/bin/rar')) $rarMethod = 'rar';
    else $rarMethod = 'other';
    // / This code will attempt the archive operation up to $StopCounter number of times.
    while ($stopper <= $StopCounter) {
      // / If the last conversion attempt failed, wait a moment before trying again.
      if ($stopper !== 0) sleep($sleepTime++);
      // / Attempt the conversion.
      if ($rarMethod === 'rar') $returnData = shell_exec('rar a -ep1 -r '.$newPathname.' '.$safedir2);
      else $returnData = shell_exec('7z a -t'.$extension.' '.$newPathname.' '.$safedir2);
      if ($Verbose && trim($returnData) !== '') logEntry('The archiver returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
      // / Count the number of conversions to avoid infinite loops.
      $stopper++;
    // / Stop attempting the archive operation after $StopCounter number of attempts.
      if ($stopper === $StopCounter) {
        $ConversionErrors = TRUE;
        errorEntry('The archiver timed out!', 13005, FALSE); } } }
  // / Check if any errors occurred.
  if (!file_exists($newPathname)) {
    $ConversionErrors = TRUE;
    errorEntry('The archiver failed to produce an archive!', 13000, FALSE); }
  else $ConversionSuccess = TRUE;
  // / Code to clean up temporary files & directories.
  cleanFiles($safedir2);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $filename = $safedir2 = $safedir3 = $safedir4 = $oldExtension = $returnData = $pathname = $newPathname = $extension = $array7zo = $arrayzipo = $array7zo2 = $arraytaro = $arrayraro = $sleepTime = $rarMethod = NULL;
  unset($filename, $safedir2, $safedir3, $safedir4, $oldExtension, $returnData, $pathname, $newPathname, $extension, $array7zo, $arrayzipo, $array7zo2, $arraytaro, $arrayraro, $sleepTime, $rarMethod);
  return array($ConversionSuccess, $ConversionErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert a file based on a pre-determined input type and return the results.
function convert($type, $pathname, $newPathname, $extension, $height, $width, $rotate, $bitrate) {
  // / Set variables.
  global $Verbose, $SupportedConversionTypes;
  $ConversionSuccess = $ConversionErrors = FALSE;
  // / Check that the required conversion type is allowed.
  if (in_array($type, $SupportedConversionTypes)) { 
    if ($type === 'Document') list ($ConversionSuccess, $ConversionErrors) = convertDocuments($pathname, $newPathname, $extension);
    if ($type === 'Image') list ($ConversionSuccess, $ConversionErrors) = convertImages($pathname, $newPathname, $height, $width, $rotate);
    if ($type === 'Model') list ($ConversionSuccess, $ConversionErrors) = convertModels($pathname, $newPathname);
    if ($type === 'Drawing') list ($ConversionSuccess, $ConversionErrors) = convertDrawings($pathname, $newPathname);
    if ($type === 'Video') list ($ConversionSuccess, $ConversionErrors) = convertVideos($pathname, $newPathname);
    if ($type === 'Subtitle') list ($ConversionSuccess, $ConversionErrors) = convertSubtitles($pathname, $newPathname);
    if ($type === 'Stream') list ($ConversionSuccess, $ConversionErrors) = convertStreams($pathname, $newPathname);
    if ($type === 'Audio') list ($ConversionSuccess, $ConversionErrors) = convertAudio($pathname, $newPathname, $extension, $bitrate);
    if ($type === 'Archive') list ($ConversionSuccess, $ConversionErrors) = convertArchives($pathname, $newPathname, $extension); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $type = $pathname = $newPathname = $extension = $height = $width = $rotate = $bitrate = NULL;
  unset($type, $pathname, $newPathname, $extension, $height, $width, $rotate, $bitrate);
  return array($ConversionSuccess, $ConversionErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to syncronize the users AppData between the $ConvertLoc and the $InstLoc.
function syncLocations() {
  // / Set variables.
  global $ConvertDir, $ConvertTempDir, $DirSep, $PermissionLevels, $ApacheUser;
  $LocationsSynced = TRUE;
  // / Iterate through each file object in the $ConvertDir, skipping dots.
  foreach ($iterator = new \RecursiveIteratorIterator (new \RecursiveDirectoryIterator ($ConvertDir, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $item) {
    // / Verify the permissions on the file object.
    @chmod($item, $PermissionLevels);
    @chown($item, $ApacheUser);
    // / If the file object is a directory, make a corresponding directory in the $ConvertTempDir.
    if (is_dir($item)) {
      if (!file_exists($ConvertTempDir.$DirSep.$iterator->getSubPathName())) @mkdir($ConvertTempDir.$DirSep.$iterator->getSubPathName(), $PermissionLevels); }
    // / If the file object is a file that does not already exist in the $ConvertTempDir, create a symlink to it in the $ConvertTempDir.
    else if (!is_link($ConvertTempDir.$DirSep.$iterator->getSubPathName()) or !file_exists($ConvertTempDir.$DirSep.$iterator->getSubPathName())) symlink($item, $ConvertTempDir.$DirSep.$iterator->getSubPathName()); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $iterator = $item = NULL;
  unset($iterator, $item);
  return $LocationsSynced; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify files before performing operations on them.
function verifyFile($file, $UserFilename, $UserExtension, $clean, $copy, $skip) {
  global $DangerousFiles, $ConvertDir, $ConvertTempDir, $Allowed, $Verbose, $PathExt;
  $FileIsVerified = $Pathname = $OldPathname = $NewPathname = $variableIsSanitized = FALSE;
  // / Check to make sure all iteration specific required variables are properly sanitized.
  list ($file, $variableIsSanitized) = sanitize($file, FALSE);
  list ($Pathname, $variableIsSanitized) = sanitize($ConvertTempDir.$file, FALSE);
  list ($OldPathname, $variableIsSanitized) = sanitize($ConvertDir.$file, FALSE);
  $OldExtension = pathinfo($Pathname, $PathExt);
  // / Check if the selected file is safe to handle.
  if (in_array(strtolower($OldExtension), $Allowed) && !in_array(strtolower($OldExtension), $DangerousFiles) && $file !== '.' && $file !== '..' && $file !== 'index.html') $FileIsVerified = TRUE;
  if (!$FileIsVerified) errorEntry('The file '.$file.' failed first stage validation!', 14000, TRUE);
  if ($FileIsVerified) {
    if ($Verbose && file_exists($Pathname) && $clean) logEntry('Deleting stale file '.$Pathname.'.');
    // / Remove the temp file if one already exists.
    if (file_exists($Pathname) && $clean) @unlink($Pathname);
    // / Check to make sure that the stale file was deleted if required or creating a new one will cause problems.
    if (file_exists($Pathname) && $clean) errorEntry('Could not delete stale file '.$Pathname.'!', 14001, TRUE);
    if ($Verbose && file_exists($OldPathname) && $copy) logEntry('Copying file '.$file.' to '.$Pathname.'.');
    // / Copy the file to the working directory.
    if (file_exists($OldPathname) && $copy) @copy($OldPathname, $Pathname);
    // / Check to make sure the temporary file was created.
    if (!$skip) if (!file_exists($Pathname)) errorEntry('The file '.$Pathname.' failed second stage validation!', 14002, TRUE);
    if (file_exists($Pathname)) if ($Verbose  && $copy) logEntry('Copied file '.$file.'.');
    // / If the $UserFilename & $UserExtension variables are valid we can prepare for a $NewPathfile.
    if ($UserFilename && $UserExtension) {
      // / Define the $NewPathname if required.
      list ($NewPathname, $variableIsSanitized) = sanitize($ConvertDir.$UserFilename.'.'.$UserExtension, FALSE);
      // / Make sure the $NewPathname is not a dangerous file.
      if (in_array(strtolower($UserExtension), $DangerousFiles)) errorEntry('The file '.$file.' failed third stage validation!', 14003, TRUE);
      if ($Verbose && file_exists($NewPathname) && $clean) logEntry('Deleting stale file '.$Pathname.'.');
      // / Remove the $NewPathname file if it already exists.
      if (file_exists($NewPathname) && $clean) @unlink($NewPathname);
      // / Check to make sure that the stale file was deleted if required or creating a new one will cause problems.
      if (file_exists($NewPathname) && $clean) errorEntry('Could not delete stale file '.$NewPathname.'!', 14004, TRUE); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $file = $variableIsSanitized = NULL;
  unset($file, $variableIsSanitized);
  return array($FileIsVerified, $Pathname, $OldPathname, $OldExtension, $NewPathname); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to build the GUI.
function buildGUI($guiType, $ButtonCode) {
  // / Set variables.
  global $GuiFiles, $LanguageFiles, $LanguageStringsFile, $GuiHeaderFile, $GuiFooterFile, $GuiUI1File, $GuiUI2File, $CoreLoaded, $ConvertDir, $ConvertTempDir, $Token1, $Token2, $SesHash, $SesHash2, $SesHash3, $SesHash4, $Date, $Time, $TOSURL, $PPURL, $ShowFinePrint, $PDFWorkArr, $ArchiveArray, $DearchiveArray, $DocumentArray, $SpreadsheetArray, $ImageArray, $ModelArray, $DrawingArray, $VideoArray, $SubtitleArray, $StreamArray, $MediaArray, $PresentationArray, $ConvertGuiCounter1, $ConsolidatedLogFileName, $Alert, $Alert1, $Alert2, $Alert3, $FCPlural, $FCPlural1, $FCPlural2, $FCPlural3, $File, $Files, $FileCount, $SpinnerStyle, $SpinnerColor, $PacmanLoc, $Allowed, $AllowUserVirusScan, $AllowUserShare, $SupportedConversionTypes, $FullURL, $LanguageDir, $FaviconPath, $DropzonePath, $DropzoneStylesheetPath, $StylesheetPath, $JsLibraryPath, $JqueryPath, $GUIDirection, $SupportedFormatCount, $GUIAlignment, $HeaderDisplayed, $UIDisplayed, $FooterDisplayed, $LanguageStringsLoaded, $GUIDisplayed, $GuiResourcesDir, $GuiImageDir, $GuiCSSDir, $GuiJSDir;
  $guiUIFile = $GuiUI1File;
  $Files = array();
  $FileCount = 0;
  // / Make sure the $guiType is valid.
  if (!is_numeric($guiType)) {
    if ($guiType < 0) $guiType = 0;
    if ($guiType > 0) $guiType = 1; }
  // / Determine which loading indicator to use.
  $PacmanLoc = $GuiImageDir.'pacman'.$SpinnerStyle.strtolower($SpinnerColor).'.gif';
  if (!file_exists($PacmanLoc)) $PacmanLoc = $GuiImageDir.'pacman1grey.gif';
  // / Gather a list of files.
  if ($guiType === 2) {
    $Files = getFiles($ConvertDir);
    $FileCount = count($Files); }
  // / Load language specific GUI elements, if there are any.
  if (in_array($LanguageStringsFile, $LanguageFiles)) require_once($LanguageStringsFile);
  // / Load the header.
  if (in_array($GuiHeaderFile, $GuiFiles)) require_once($GuiHeaderFile);
  // / Build and define the different GUI types that are available.
  if ($guiType === 1) $guiUIFile = $GuiUI1File;
  if ($guiType === 2) $guiUIFile = $GuiUI2File;
  // / Build the specified GUI.
  if (in_array($guiUIFile, $GuiFiles)) require_once($guiUIFile);
  // / Load the footer.
  if (in_array($GuiFooterFile, $GuiFiles)) require_once($GuiFooterFile);
  // / Check if the required GUI elements were loaded.
  if ($HeaderDisplayed && $UIDisplayed && $FooterDisplayed && $LanguageStringsLoaded) $GUIDisplayed = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $guiType = $languageUIFile = NULL; 
  unset($guiType, $languageUIFile); 
  return $GUIDisplayed; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to display the GUI.
function showGUI($ShowGUI, $ButtonCode) {
  // / Set variables.
  global $ButtonCode;
  $GUIDisplayed = FALSE;
  // / Determine whether to show a full or minimal GUI.
  if (isset($ShowGUI)) if (!$ShowGUI) $_GET['noGui'] = TRUE;
  // / Call the GUI from the selected language pack after files have been uploaded.
  if (isset($_GET['showFiles'])) $GUIDisplayed = buildGui(2, $ButtonCode);
  // / Call the GUI from the selected language pack before files have been uploaded.
  if (!isset($_GET['showFiles'])) $GUIDisplayed = buildGui(1, $ButtonCode);
  return $GUIDisplayed; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to upload a selection of files.
function uploadFiles() {
  // / Set variables.
  global $DangerousFiles, $VirusScan, $AllowUserVirusScan, $ConvertDir, $LogFile, $Verbose, $PathExt, $PermissionLevels, $Allowed;
  $UploadComplete = $UploadErrors = $virusFound = $variableIsSanitized = FALSE;
  $file = $f0 = $f1 = '';
  // / Make sure the input files are formatted into an array.
  if (!is_array($_FILES['file']['name'])) $_FILES['file']['name'] = array($_FILES['file']['name']);
  // / Iterate through the array of input files.
  foreach ($_FILES['file']['name'] as $file) {
    $UploadComplete = FALSE;
    // / Make sure the file is sanitized before processing it.
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $OperationErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 6000, FALSE); 
      continue; }
    if ($Verbose) logEntry('User selected to Upload file '.$file.'.');
    $f0 = pathinfo($file, $PathExt);
    // / Make sure the file is not in the list of dangerous formats.
    if (in_array(strtolower($f0), $DangerousFiles) or !in_array(strtolower($f0), $Allowed)) {
      errorEntry('Unsupported file format, '.$f0.'!', 6001, FALSE);
      continue; }
    list ($f1, $variableIsSanitized) = sanitize($ConvertDir.pathinfo($file, PATHINFO_BASENAME), FALSE);
    // / Code to remove an output file that already exists.
    if (file_exists($f1)) @unlink($f1);
    @copy($_FILES['file']['tmp_name'], $f1);
    if (!file_exists($f1)) {
      $UploadErrors = TRUE;
      errorEntry('Could not upload file '.$file.' to '.$f1.'!', 6002, FALSE); }
    else {
      $UploadComplete = TRUE;
      if ($Verbose) logEntry('Uploaded file '.$file.' to '.$f1.'.'); }
    @chmod($f1, $PermissionLevels);
    // / Scan with ClamAV if $AllowUserVirusScan is set to FALSE in config.php.
    if (!$AllowUserVirusScan) {
      // / Scan with ClamAV if $VirusScan is set to TRUE in config.php.
      if ($VirusScan) {
        if ($Verbose) logEntry('Starting virus scan.');
        list ($scanComplete, $virusFound) = virusScan($f1);
        if (!$scanComplete) errorEntry('Could not perform a virus scan!', 6003, TRUE);
        if ($virusFound) errorEntry('Virus detected!', 6004, TRUE);
        if ($Verbose) logEntry('Virus scan complete.'); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $file = $f0 = $f1 = $variableIsSanitized = $scanComplete = $virusFound = NULL;
  unset ($file, $f0, $f1, $variableIsSanitized, $scanComplete, $virusFound);
  return array($UploadComplete, $UploadErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to upload a selection of files.
function downloadFiles($Download) {
  // / Set variables.
  global $DangerousFiles, $Verbose, $PathExt, $Download, $ConvertDir, $ConsolidatedLogFileName, $Allowed;
  $DownloadComplete = $DownloadErrors = $clean = $copy = $skip = $variableIsSanitized = FALSE;
  $file = $f0 = '';
  list ($Download, $variableIsSanitized) = sanitize($Download, FALSE);
  // / Make sure the input files are formatted into an array.
  if (!is_array($Download)) $Download = array($Download);
  // / Iterate through the array of input files.
  foreach ($Download as $file) {
    $DownloadComplete = FALSE;
    // / Make sure the file is sanitized before processing it.
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $OperationErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 3000, FALSE); 
      continue; }
    if ($Verbose) logEntry('User selected to Download file '.$file.'.');
    if ($file === $ConsolidatedLogFileName) $skip = TRUE;
    else $clean = $copy = TRUE;
    $f0 = pathinfo($file, $PathExt);
    // / Make sure the file is not in the list of dangerous formats.
    if (in_array(strtolower($f0), $DangerousFiles) or !in_array(strtolower($f0), $Allowed)) {
      errorEntry('Unsupported file format, '.$f0.'!', 3004, FALSE);
      continue; }
    // / Make sure all iteration specific required variables are properly sanitized.
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname) = verifyFile($file, FALSE, FALSE, $clean, $copy, $skip);
    if (!$fileIsVerified) {
      $ArchiveErrors = TRUE;
      errorEntry('Could not verify the input file.', 3001, FALSE);
      continue; }
    // / Make sure that the file exists.
    if (!file_exists($oldPathname) && !$skip) {
      $DownloadErrors = TRUE;
      errorEntry('File '.$file.' does not exist!', 3002, FALSE);
      continue; }
    if (!file_exists($pathname)) errorEntry('Could not verify the input file.', 3003, FALSE);
    else {
      if (!$DownloadErrors) $DownloadComplete = TRUE;
      if ($Verbose) logEntry('Verified file'.$newPathname.'.'); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $file = $f0 = $clean = $copy = $skip = $variableIsSanitized = NULL;
  unset ($file, $f0, $clean, $copy, $skip, $variableIsSanitized); 
  return array($DownloadComplete, $DownloadErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to delete a selection of files.
function deleteFiles($FilesToDelete) {
  // / Set variables.
  global $DangerousFiles, $Verbose, $FilesToDelete, $ConvertDir, $ConvertTempDir;
  $DeleteComplete = $DeleteErrors = $variableIsSanitized = FALSE;
  $file = $f0 = $f1 = '';
  list ($FilesToDelete, $variableIsSanitized) = sanitize($FilesToDelete, FALSE);
  // / Make sure the input files are formatted into an array.
  if (!is_array($FilesToDelete)) $FilesToDelete = array($FilesToDelete);
  // / Iterate through the array of input files.
  foreach ($FilesToDelete as $file) {
    $DeleteComplete = FALSE;
    // / Make sure the file is sanitized before processing it.
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $OperationErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 23000, FALSE); 
      continue; }
    if ($Verbose) logEntry('User selected to Delete file '.$file.'.');
    $f0 = pathinfo($file, $PathExt);
    // / Make sure the file is not in the list of dangerous formats.
    if (in_array(strtolower($f0), $DangerousFiles)) {
      errorEntry('Unsupported file format, '.$f0.'!', 23001, FALSE);
      continue; }
    list ($f0, $variableIsSanitized) = sanitize($ConvertTempDir.pathinfo($file, PATHINFO_BASENAME), FALSE);
    // / Code to remove the selected file from the $ConvertTempDir.
    if (file_exists($f0)) @unlink($f1);
    list ($f1, $variableIsSanitized) = sanitize($ConvertDir.pathinfo($file, PATHINFO_BASENAME), FALSE);
    // / Code to remove the selected file from the $ConvertDir.
    if (file_exists($f1)) @unlink($f1);
    // / Check that the selected files were deleted.
    if (!file_exists($f0) && !file_exists($f1)) {
     if ($Verbose) logEntry('Deleted file '.$file.'.');
     $DeleteComplete = TRUE; }
    else {
      $DeleteErrors = TRUE;
      if ($Verbose) logEntry('Could not delete file '.$file.'!', 23002, FALSE); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $file = $f0 = $f1 = NULL;
  unset($file, $f0, $f1);
  return array($DeleteComplete, $DeleteErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to archive a selection of files.
function archiveFiles($FilesToArchive, $UserFilename, $UserExtension) {
  // / Set variables.
  global $Verbose, $VirusScan, $ConvertTempDir, $Lol, $Lolol, $RARArchiveMethod;
  $ArchiveComplete = $ArchiveErrors = $virusFound = $skip = $variableIsSanitized = FALSE;
  $clean = $copy = TRUE;
  $returnData = $file = '';
  $rararr = array('rar');
  $ziparr = array('zip');
  $tararr = array('7z', 'tar', 'tar.gz', 'tar.bz2');
  $isoarr = array('iso');
  $rarMethod = 'other';
  // / Make sure the input files are formatted into an array.
  if (!is_array($FilesToArchive)) $FilesToArchive = array($FilesToArchive);
  // / Iterate through the array of input files.
  foreach ($FilesToArchive as $file) {
    $ArchiveComplete = FALSE;
    // / Make sure the file is sanitized before processing it.
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $OperationErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 4000, FALSE); 
      continue; }
    // / Set the $clean & $copy arguments for the verifyFiles() function as needed,
    if (count($FilesToArchive) > 1) $clean = FALSE; $copy = TRUE;
    if ($Verbose) logEntry('User selected to Archive file '.$file.'.');
    // / Verify the file before performing any operations on it.
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname) = verifyFile($file, $UserFilename, $UserExtension, $clean, $copy, $skip);
    if (!$fileIsVerified) {
      $ArchiveErrors = TRUE;
      errorEntry('Could not verify the input file.', 4001, FALSE);
      continue; }
    else if ($Verbose) logEntry('Verified file'.$newPathname.'.');
    // / Scan with ClamAV if $VirusScan is set to TRUE in config.php.
    if ($VirusScan) {
      if ($Verbose) logEntry('Starting virus scan.');
      list ($scanComplete, $virusFound) = virusScan($pathname);
      if (!$scanComplete) errorEntry('Could not perform a virus scan!', 4002, TRUE);
      if ($virusFound) errorEntry('Virus detected!', 4003, TRUE);
      if ($Verbose) logEntry('Virus scan complete.'); }
    // / Handle archiving of rar compatible files.
    if (in_array($UserExtension, $rararr)) {
      if ($RARArchiveMethod === 'rar') $rarMethod = 'rar';
      else $rarMethod = 'other';
      if ($rarMethod === 'rar' && file_exists('/usr/bin/rar')) $returnData = shell_exec('rar a -ep '.$newPathname.' '.$pathname);
      else $returnData = shell_exec('7z a '.$newPathname.' '.$pathname); }
    // / Handle archiving of .zip compatible files.
    if (in_array($UserExtension, $ziparr)) $returnData = shell_exec('zip -j '.$newPathname.' '.$pathname);
    // / Handle archiving of 7zipper compatible files.
    if (in_array($UserExtension, $tararr)) $returnData = shell_exec('7z a '.$newPathname.' '.$pathname);
    // / Handle archiving of mkisofs compatible files.
    if (in_array($UserExtension, $isoarr)) $returnData = shell_exec('mkisofs -o '.$newPathname.' '.$pathname);
    if ($Verbose && trim($returnData) !== '') logEntry('The archiver returned the following: '.$Lol.'  ');
    if (!file_exists($newPathname)) {
      $ArchiveError = TRUE;
      errorEntry('Could not archive file '.$pathname.' to '.$newPathname.'!', 4004, FALSE); }
    else {
      $ArchiveComplete = TRUE;
      if ($Verbose) logEntry('Archived file '.$pathname.' to '.$newPathname.'.'); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $file = $rararr = $ziparr = $tararr = $isoarr = $pathname = $userFileName = $oldPathname = $newPathname = $scanComplete = $virusFound = $returnData = $variableIsSanitized = $fileIsVerified = $oldExtension = $clean = $copy = $skip = $variableIsSanitized = $rarMethod = NULL;
  unset ($file, $rararr, $ziparr, $tararr, $isoarr, $pathname, $userFileName, $oldPathname, $newPathname, $scanComplete, $virusFound, $returnData, $variableIsSanitized, $fileIsVerified, $oldExtension, $clean, $copy, $skip, $variableIsSanitized, $rarMethod); 
  return array($ArchiveComplete, $ArchiveErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert a selection of files.
function convertFiles($ConvertSelected, $UserFilename, $UserExtension, $Height, $Width, $Rotate, $Bitrate) {
  // / Set variables.
  global $Verbose, $VirusScan, $DocumentArray, $ImageArray, $ModelArray, $DrawingArray, $VideoArray, $SubtitleArray, $StreamArray, $MediaArray, $ArchiveArray;
  $MainConversionSuccess = $MainConversionErrors = $virusFound = $skip = $isExtensionSupported = $fileIsVerified = $variableIsSanitized = FALSE;
  $clean = $copy = TRUE;
  $docarray =  $DocumentArray;
  $imgarray = $ImageArray;
  $modelarray = $ModelArray;
  $drawingarray = $DrawingArray;
  $videoarray =  $VideoArray;
  $subtitleArray = $SubtitleArray;
  $streamarray = $StreamArray;
  $audioarray =  $MediaArray;
  $archarray = $ArchiveArray; 
  $pdfarray = array('pdf');
  $array7z = array('7z', 'zip', 'rar', 'iso', 'vhd');
  $array7zo = array('7z', 'zip');
  $arrayzipo = array('zip');
  $array7zo2 = array('vhd', 'iso');
  $arraytaro = array('tar.gz', 'tar.bz2', 'tar');
  $arrayraro = array('rar');
  $arrayArray = array('Document' => $docarray, 'Image' => $imgarray, 'Model' => $modelarray, 'Drawing' => $drawingarray, 'Video' => $videoarray, 'Subtitle' => $subtitleArray, 'Stream' => $streamarray, 'Audio' => $audioarray, 'Archive' => $archarray);
  $arrKey = 0;
  $file = '';
  // / Make sure the input files are formatted into an array.
  if (!is_array($ConvertSelected)) $ConvertSelected = array($ConvertSelected);
  // / Iterate through the array of input files.
  foreach ($ConvertSelected as $file) {
    $MainConversionSuccess = FALSE;
    // / Make sure the file is sanitized before processing it.
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $OperationErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 5000, FALSE); 
      continue; }
    // / Set the $clean & $copy arguments for the verifyFiles() function as needed,
    if (count($ConvertSelected) > 1) $clean = FALSE; $copy = TRUE;
    if (in_array($UserExtension, $archarray)) $clean = FALSE;
    if (in_array($UserExtension, $docarray)) $clean = FALSE;
    if ($Verbose) logEntry('User selected to Convert file '.$file.'.');
    // / Verify the file before performing any operations on it.
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname) = verifyFile($file, $UserFilename, $UserExtension, $clean, $copy, $skip);
    if (!$fileIsVerified) {
      $MainConversionErrors = TRUE;
      errorEntry('Could not verify the input file.', 5001, FALSE);
      continue; }
    else if ($Verbose) logEntry('Verified file '.$newPathname.'.');
    // / Scan with ClamAV if $VirusScan is set to TRUE in config.php.
    if ($VirusScan) {
      if ($Verbose) logEntry('Starting virus scan.');
      list ($scanComplete, $virusFound) = virusScan($newPathname);
      if (!$scanComplete) errorEntry('Could not perform a virus scan!', 5002, TRUE);
      if ($virusFound) errorEntry('Virus detected!', 5003, TRUE);
      if ($Verbose) logEntry('Virus scan complete.'); }
    // / Iterate through the array of supported formats & call the appropriate code to perform the conversion.
    foreach ($arrayArray as $arrKey => $arrArray) {
      // / Code to convert & manipulate files.
      if (in_array(strtolower($oldExtension), $arrArray)) {
        $isExtensionSupported = TRUE;
        list ($ConversionSuccess, $ConversionErrors) = convert($arrKey, $pathname, $newPathname, $UserExtension, $Height, $Width, $Rotate, $Bitrate);
        if (!$ConversionSuccess) {
          $MainConversionSuccess = FALSE;
          errorEntry('Could not convert the selected '.$arrKey.'!', 5004, FALSE); }
        if ($ConversionErrors) {
          $MainConversionErrors = TRUE;
          logEntry($arrKey.' conversion finished with errors.'); }
        if ($Verbose) logEntry($arrKey.' Conversion Complete'); } }
    // / Error handler & logger for converting files.
    if (!$isExtensionSupported) errorEntry('File extension '.$oldExtension.' is not supported!', 5006, FALSE);
    if (!file_exists($newPathname)) {
      $MainConversionErrors = TRUE;
      $MainConversionSuccess = FALSE;
      errorEntry('Could not create '.$newPathname.' from '.$oldPathname.'!', 5005, FALSE); }
    if (file_exists($newPathname)) {
      $MainConversionSuccess = TRUE;
      if ($Verbose) logEntry('Created a file at '.$newPathname.'.'); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $file = $pathname = $oldPathname = $oldExtension= $newPathname = $docarray = $imgarray = $audioarray = $videoarray = $subtitleArray = $streamarray = $modelarray = $drawingarray = $pdfarray = $archarray = $array7z = $array7zo = $arrayzipo = $arraytaro = $arrayraro = $arrayArray = $fileIsVerified = $scanComplete = $virusFound = $variableIsSanitized = $arrKey = $clean = $copy = $skip = $isExtensionSupported = NULL;
  unset ($file, $pathname, $oldPathname, $oldExtension, $newPathname, $docarray, $imgarray, $audioarray, $videoarray, $subtitleArray, $streamarray, $modelarray, $drawingarray, $pdfarray, $archarray, $array7z, $array7zo, $arrayzipo, $arraytaro, $arrayraro, $arrayArray, $fileIsVerified, $scanComplete, $virusFound, $variableIsSanitized, $arrKey, $clean, $copy, $skip, $isExtensionSupported);
  return array($MainConversionSuccess, $MainConversionErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to OCR a selection of files.
function ocrFiles($PDFWorkSelected, $UserFilename, $UserExtension, $Method) {
  // / Set variables.
  global $Verbose, $VirusScan, $ConvertTempDir, $ConvertDir, $Lol, $Lolol, $Append;
  $OperationSuccessful = $OperationErrors = $multiple = $virusFound = $skip = $variableIsSanitized = FALSE;
  $clean = $copy = TRUE;
  $returnData = $file = '';
  $doc1array =  array('txt', 'pages', 'doc', 'xls', 'xlsx', 'docx', 'rtf', 'odt', 'ods');
  $img1array = array('jpg', 'jpeg', 'bmp', 'webp', 'png', 'gif');
  $pdf1array = array('pdf');
  $allowedOCR =  array('txt', 'doc', 'docx', 'rtf' ,'xls', 'xlsx', 'ods', 'odt', 'jpg', 'jpeg', 'bmp', 'webp', 'png', 'gif', 'pdf', 'abw');
  // / Make sure the input files are formatted into an array.
  if (!is_array($PDFWorkSelected)) $PDFWorkSelected = array($PDFWorkSelected);
  // / Iterate through the array of input files.
  foreach ($PDFWorkSelected as $file) {
    $OperationSuccessful = FALSE;
    // / Make sure the file is sanitized before processing it.
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $OperationErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 15000, FALSE); 
      continue; }
    if ($Verbose) logEntry('User selected to perform OCR on file '.$file.'.');
    // / Verify the file before performing any operations on it.
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname) = verifyFile($file, $UserFilename, $UserExtension, $clean, $copy, $skip);
    $pathnameTEMP = str_replace('..', '', str_replace('.'.$oldExtension, '.txt' , $pathname));
    if (!$fileIsVerified) {
      $MainConversionErrors = TRUE;
      errorEntry('Could not verify the input file.', 15001, FALSE);
      continue; }
    else if ($Verbose) logEntry('Verified file '.$newPathname.'.');
    // / Scan with ClamAV if $VirusScan is set to TRUE in config.php.
    if ($VirusScan) {
      if ($Verbose) logEntry('Starting virus scan.');
      list ($scanComplete, $virusFound) = virusScan($newPathname);
      if (!$scanComplete) errorEntry('Could not perform a virus scan!', 15002, TRUE);
      if ($virusFound) errorEntry('Virus detected!', 15003, TRUE);
      if ($Verbose) logEntry('Virus scan complete.'); }
    if (in_array(strtolower($oldExtension), $allowedOCR)) {
      // / Code to convert a PDF to a document.
      if (!in_array(strtolower($oldExtension), $pdf1array)) {
        if (in_array($UserExtension, $doc1array)) {
          // / If Method 1 is selected, attempt a direct conversion.
          if ($Method === 0 or $Method === '0' or $Method === '') {
            if ($Verbose) logEntry('Performing OCR using method 0.');
            // / Perform the conversion using PDFTOTEXT.
            $returnData = shell_exec('pdftotext -layout '.$pathname.' '.$pathnameTEMP);
            if ($Verbose && trim($returnData) !== '') logEntry('The converter returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
            if (!file_exists($pathnameTEMP)) {
              errorEntry('Could not complete the conversion using method 0. Reattempting using method 1.', 15004, FALSE);
              $Method = 1; } }
            // / If Method 2 is selected, attempt to convert each page of the .pdf to .jpg, then convert that to .txt.
            if ($Method === 1 or $Method === '1') {
              $pathnameTEMP1 = str_replace('..', '', str_replace('.'.$oldExtension, '.jpg' , $pathname));
              if ($Verbose) logEntry('Performing OCR intermediate operation using method 0.');
              // / Perform the conversion using ImageMagick.
              $returnData = shell_exec('convert '.$pathname.' '.$pathnameTEMP1);
              if ($Verbose && trim($returnData) !== '') logEntry('The converter returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
              // / If a file doesn't exist there is a good chance it is because ImageMagick has split the pages up.
              if (!file_exists($pathnameTEMP1)) {
                // / Scan the current directory for files matching the filename.
                $pagedFilesArrRAW = scandir($ConvertTempDir);
                foreach ($pagedFilesArrRAW as $pagedFile) {
                  $filename = pathinfo($pathname, PATHINFO_FILENAME);
                  // / Look for files with the same filename but in .jpg format.
                  if (strpos($pagedFile, $filename) !== TRUE) continue;
                  if (strpos($pagedFile, '.jpg') !== TRUE) continue;
                  if ($pagedFile == '.' or $pagedFile == '..' or $pagedFile == '.AppData' or $pagedFile == 'index.html') continue;
                  // / Set page specific variables.
                  $pathnameTEMP1 = str_replace('..', '', str_replace('.'.$oldExtension, '.jpg' , $pathname));
                  $cleanFilname = str_replace('..', '', str_replace($oldExtension, '', $filename));
                  $pageNumber = str_replace('..', '', str_replace('-', '', str_replace($cleanFilname, '', str_replace('.jpg', '', $pagedFile))));
                  $pathnameTEMP1 = str_replace('..', '', str_replace('.jpg', '-'.$pageNumber.'.jpg', $pathnameTEMP1));
                  $pathnameTEMP = str_replace('..', '', str_replace('.'.$oldExtension, '-'.$pageNumber.'.txt', $pathname)); 
                  $pathnameTEMPTesseract = str_replace('..', '', str_replace('.'.$oldExtension, '-'.$pageNumber, $pathname));
                  $pathnameTEMP0 = str_replace('..', '', str_replace('-'.$pageNumber.'.txt', '.txt', $pathnameTEMP));
                  if ($Verbose) logEntry('Performing OCR final operation using method 0.');
                  // / Perform the conversion using Tesseract.
                  $returnData = shell_exec('tesseract '.$pathnameTEMP1.' '.$pathnameTEMPTesseract);
                  if ($Verbose && trim($returnData) !== '') logEntry('The converter returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
                  if (!file_exists($pathnameTEMP)) errorEntry('Could not complete the conversion using method 1.', 15005, FALSE);
                  // / Recompile all of the text files into one big text file.
                  $readPageData = file_get_contents($pathnameTEMP);
                  $writePageData = file_put_contents($pathnameTEMP0, $readPageData.$Lol, $Append);
                  $multiple = TRUE;
                  if (!file_exists($pathnameTEMP0)) errorEntry('Could not OCR file!', 15006, FALSE); } }
                  if ($Verbose) logEntry('Converted file '.$pathnameTEMP1.' to '.$pathnameTEMP.'.');
              if (!$multiple) {
                $pathnameTEMPTesseract = str_replace('..', '', str_replace('.txt', '', $pathnameTEMP));
                if ($Verbose) logEntry('Performing OCR final using method 0.');
                $returnData = shell_exec('tesseract '.$pathnameTEMP1.' '.$pathnameTEMPTesseract);
                if ($Verbose && trim($returnData) !== '') logEntry('The converter returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData))))); } } } }
        // / Code to convert a document to a PDF.
        if (in_array(strtolower($oldExtension), $doc1array)) {
          if (in_array($UserExtension, $pdf1array)) {
            // / The following code verifies that the document conversion engine is installed & running.
            list ($documentEngineStarted, $documentEnginePID) = verifyDocumentConversionEngine();
            if (!$documentEngineStarted) {
              $OperationErrors = TRUE;
              errorEntry('Could not verify the document conversion engine!', 15007, FALSE); }
            // / Perform the conversion using Unoconv.
            $returnData = shell_execs('/usr/bin/unoconv -o '.$newPathname.' -f pdf '.$pathname);
            if ($Verbose && trim($returnData) !== '') logEntry('The converter returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData))))); } }
        // / Code to convert an image to a PDF.
        if (in_array(strtolower($oldExtension), $img1array)) {
          $pathnameTEMPTesseract = str_replace('..', '', str_replace('.'.$oldExtension, '', $pathname));
          if ($Verbose) logEntry('Performing OCR operation using method 0.');
          // / Perform the conversion using Unoconv.
          $returnData = shell_exec('tesseract '.$pathname.' '.$pathnameTEMPTesseract);
          if ($Verbose && trim($returnData) !== '') logEntry('The converter returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
          if (!file_exists($pathnameTEMP)) {
            $pathnameTEMP3 = str_replace('..', '', str_replace('.'.$oldExtension, '.pdf' , $pathname));
            // / The following code verifies that the document conversion engine is installed & running.
            list ($documentEngineStarted, $documentEnginePID) = verifyDocumentConversionEngine();
            if (!$documentEngineStarted) {
              $OperationErrors = TRUE;
              errorEntry('Could not verify the document conversion engine!', 15008, FALSE); }
            if ($Verbose) logEntry('Performing OCR intermediate operation using method 0.');
            // / Perform the conversion using Unoconv.
            $returnData = shell_exec('/usr/bin/unoconv -o '.$pathnameTEMP3.' -f pdf '.$pathname);
            if ($Verbose && trim($returnData) !== '') logEntry('Performing OCR final operation using method 0.');
            // / Perform the conversion using PDFTOTEXT.
            $returnData = shell_exec('pdftotext -layout '.$pathnameTEMP3.' '.$pathnameTEMP);
            if ($Verbose && trim($returnData) !== '') logEntry('The converter returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData))))); }
          if ($Verbose && file_exists($pathnameTEMP)) logEntry('Created an intermediate file at '.$pathnameTEMP.'.');
          if (!file_exists($pathnameTEMP)) {
            $OperationErrors = TRUE; 
            if ($Verbose) errorEntry('Could not create an intermediate directory at '.$pathnameTEMP.'!', 15009, FALSE); } }
      // / If the output file is a txt file we leave it as-is.
      if ($UserExtension == 'txt') {
        if (file_exists($pathnameTEMP)) {
          rename($pathnameTEMP, $newPathname);
          if ($Verbose) logEntry('Renamed file '.$pathname.' to '.$pathnameTEMP.'.'); } }
      // / If the output file is not a txt file we convert it with Unoconv.
      if ($UserExtension !== 'txt') {
        // / Perform the conversion using Unoconv.
        $returnData = shell_exec('/usr/bin/unoconv -o '.$newPathname.' -f '.$UserExtension.' '.$pathnameTEMP);
        if ($Verbose && trim($returnData) !== '') logEntry('The converter returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData))))); }
      // / Error handler for if the output file does not exist.
      if (file_exists($newPathname)) {
        $OperationSuccessful = TRUE;
        if ($Verbose) logEntry('Created a file at '.$newPathname.'.'); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $file = $file1 = $file2 = $pathname = $oldPathname = $filename = $oldExtension = $newPathname = $doc1array = $img1array = $pdf1array = $pathnameTEMP = $pathnameTEMP1 = $pagedFilesArrRAW = $pagedFile = $cleanFilname = $pageNumber = $readPageData = $writePageData = $multiple = $pathnameTEMPTesseract = $pathnameTEMP3 = $clean = $copy = $skip =$allowedOCR = $variableIsSanitized = NULL;
  unset ($file, $file1, $file2, $pathname, $oldPathname , $filename, $oldExtension, $newPathname, $doc1array, $img1array, $pdf1array, $pathnameTEMP, $pathnameTEMP1, $pagedFilesArrRAW, $pagedFile, $cleanFilname, $pageNumber, $readPageData, $writePageData, $multiple, $pathnameTEMPTesseract, $pathnameTEMP3, $clean, $copy, $skip, $allowedOCR, $variableIsSanitized); 
  return array($OperationSuccessful, $OperationErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to create a user virus logfiles if required.
// / Type can be either 'clamav' or 'scancore'.
function verifyUserVirusLogs($type) {
  // / Set variables.
  global $Verbose, $Time, $ConvertDir, $ConvertTempDir, $UserClamLogFile, $UserScanCoreLogFile, $SesHash3, $Lol, $Append, $UserScanCoreLogFileName;
  $LogsExist = FALSE;
  $userClamLogFileName = $userScanCoreLogFileName = '';
  // / Verify the User Clam Log File if needed.
  if ($type === 'clamav') {
    // / Remove the old User ClamAV Virus Log file if one already exists.
    if (file_exists($UserClamLogFile)) {
      if ($Verbose) logEntry('Deleting stale file '.$UserClamLogFile.'.');
      @unlink($UserClamLogFile); }
    // / Make sure that the stale file was deleted if required or creating a new one will cause problems.
    if (file_exists($UserClamLogFile)) errorEntry('Could not delete stale file '.$UserClamLogFile.'!', 16000, TRUE);
    else file_put_contents($UserClamLogFile, 'Op-Act, '.$Time.', '.$SesHash3.': Created a User Clam Log File.'.$Lol, $Append);
    // / Make sure that the file was successfully replaced.
    if (!file_exists($UserClamLogFile)) errorEntry('Could not create a file at '.$UserClamLogFile.'!', 16001, TRUE);
    else {
      $LogsExist = TRUE;
      if ($Verbose) logEntry('Created a file at '.$UserClamLogFile.'.'); } }
  // / Verify the User ScanCore Log File if needed.
  if ($type === 'scancore') {
    // / Remove the old User ScanCore Virus Log file if one already exists.
    if (file_exists($UserScanCoreLogFile)) {
      if ($Verbose) logEntry('Deleting stale file '.$UserScanCoreLogFileName.'.');
      @unlink($UserScanCoreLogFile); }
    // / Make sure that the stale file was deleted if required or creating a new one will cause problems.
    if (file_exists($UserScanCoreLogFile)) errorEntry('Could not delete stale file '.$UserScanCoreFile.'!', 16002, TRUE);
    else file_put_contents($UserScanCoreLogFile, 'Op-Act, '.$Time.', '.$SesHash3.': Created a User ScanCore Log File.'.$Lol, $Append);
    // / Make sure that the file was successfully replaced.
    if (!file_exists($UserScanCoreLogFile)) errorEntry('Could not create a file at '.$UserScanCoreLogFile.'!', 16003, TRUE);
    else {
      $LogsExist = TRUE;
      if ($Verbose) logEntry('Created a file at '.$UserScanCoreLogFile.'.'); } }
  return array($LogsExist, $UserClamLogFile, $UserScanCoreLogFile); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to format a log entry & write it to the logfile.
// / Type can be either 'clamav' or 'scancore'.
function userVirusLogEntry($Entry, $type) {
  // / Set variables.
  global $Time, $UserClamLogFile, $UserScanCoreLogFile, $SesHash3, $Lol, $Append;
  $LogWritten = $logWrittenA = $logWrittenB = FALSE;
  // / Format the input string into a log entry & write it to the $UserClamLogFile.
  if ($type === 'clamav') $logWrittenA = file_put_contents($UserClamLogFile, 'Op-Act, '.$Time.', '.$SesHash3.': '.$Entry.$Lol, $Append);
  // / Format the input string into a log entry & write it to the $UserScanCoreLogFile.
  if ($type === 'scancore') $logWrittenB = file_put_contents($UserScanCoreLogFile, 'Op-Act, '.$Time.', '.$SesHash3.': '.$Entry.$Lol, $Append);
  // / Check that a log entry was written.
  if ($type === 'clamav') if ($logWrittenA) $LogWritten = TRUE;
  if ($type === 'scancore') if ($logWrittenB) $LogWritten = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $logWrittenA = $logWrittenB = NULL;
  unset($logWrittenA, $logWrittenB);
  return $LogWritten; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to scan a user supplied file on-demand with ClamAV.
function userClamScan($FilesToScan) {
  // / Set variables.
  global $Verbose, $ConvertDir, $Lol, $Lolol, $UserClamLogFile;
  $OperationSuccessful = $OperationErrors = $UserVirusFound = $userFilename = $userExtension = $clean = $copy = $userFilename = $userExtension = $variableIsSanitized = FALSE;
  $skip = TRUE;
  $returnData = $txt = $file = $clamLogFileDATA = '';
  $txt = 'Initiating User Virus Scan with ClamAV.';
  userVirusLogEntry($txt, 'clamav');
  if ($Verbose) logEntry($txt);
  // / Make sure the input files are formatted into an array.
  if (!is_array($FilesToScan)) $FilesToScan = array($FilesToScan);
  // / Iterate through the array of input files.
  foreach ($FilesToScan as $file) {
    $UserVirusFound = FALSE;
    // / Make sure the file is sanitized before processing it.
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $OperationErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 17000, FALSE);
      continue; }
    if ($Verbose) logEntry('User selected to perform a Clam Scan on file '.$file.'.');
    // / Verify the file before performing any operations on it.
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname) = verifyFile($file, $userFilename, $userExtension, $clean, $copy, $skip);
    if (!$fileIsVerified) {
      $OperationErrors = TRUE;
      errorEntry('Could not verify the input file.', 17001, FALSE);
      continue; }
    $OperationSuccessful = TRUE;
    $txt = 'Scanning '.$file.'.';
    if ($Verbose) logEntry($txt);
    userVirusLogEntry($txt, 'clamav');
    // / Scan the selected file with ClamAV.
    $returnData = shell_exec(str_replace('  ', ' ', str_replace('   ', ' ', 'clamscan -r '.$ConvertDir.$file.' | grep FOUND >> '.$UserClamLogFile)));
    // / Write the full ClamAV output to the normal $LogFile.
    if ($Verbose) logEntry('The Virus Scanner returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
    // / Load the contents of the User Clam Log File for processing because it has been sanitized of unnecessary data & whitespace.
    $clamLogFileDATA = @file_get_contents($UserClamLogFile);
    // / Check the contents of the User Clam Log File for virus detections.
    if (strpos($clamLogFileDATA, 'FOUND') !== FALSE or strpos($clamLogFileDATA, 'FOUND') === TRUE) {
      $UserVirusFound = TRUE;
      $txt = 'WARNING!!! Potentially infected file detected at '.$file.'!';
      if ($Verbose) logEntry($txt);
      userVirusLogEntry($txt, 'clamav'); }
      // / Write the results of the scan to both log files.
    else {
      $txt = 'No infection detected in '.$file.'.';
      if ($Verbose) logEntry($txt);
      userVirusLogEntry($txt, 'clamav'); } }
  $txt = 'ClamAV Virus Scan Complete.';
  if ($Verbose) logEntry($txt);
  userVirusLogEntry($txt, 'clamav');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $variableIsSanitized = $clean = $copy = $skip = $returnData = $txt = $userFilename = $userExtension = $clamLogFileDATA  = NULL;
  unset($variableIsSanitized, $clean, $copy, $skip, $returnData, $txt, $userFilename, $userExtension, $clamLogFileDATA);
  return array($OperationSuccessful, $OperationErrors, $UserVirusFound); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A fuction to prepare the execution environment for ScanCore.
function startScanCore($pathname, $UserScanCoreLogFile) {
  // / Set variables.
  global $InstLoc, $ConvertDir, $MaxLogSize, $ScanCoreMemoryLimit, $ScanCoreChunkSize, $ScanCoreDebug, $ScanCoreVerbose, $DirSep, $ScanCoreVerbose, $ScanCoreDebug, $Date, $SesHash, $SesHash2; 
  $ReturnData = $scVerbose = $scDebug = '';
  $ScanCoreFile = $InstLoc.$DirSep.'Resources'.$DirSep.'ScanCore'.$DirSep.'ScanCore.php';
  $scInc = 0;
  if ($ScanCoreVerbose) $scVerbose = ' -v';
  if ($ScanCoreDebug) $scDebug = ' -d';
  // / Make sure that ScanCore is installed.
  if (!file_exists($ScanCoreFile)) errorEntry('Could not verify the ScanCore Virus Scanner!', 18000, TRUE);
  // / The filename for the ScanCore log file.
  $scLogFile = $ConvertDir.$DirSep.'ScanCore_'.$SesHash.'_'.$SesHash2.'_'.$Date.'_'.$scInc.'_Log.txt';
  while (file_exists($scLogFile)) $scLogFile = $ConvertDir.$DirSep.'ScanCore_'.$SesHash.'_'.$SesHash2.'_'.$Date.'_'.$scInc++.'_Log.txt';
  // / Run ScanCore with the information supplied.
  $ReturnData = shell_exec('php '.$ScanCoreFile.' '.$pathname.' -m '.$ScanCoreMemoryLimit.' -c '.$ScanCoreChunkSize.' -lf '.$scLogFile.' -rf '.$UserScanCoreLogFile.' -ml '.$MaxLogSize.' -r'.$scVerbose.$scDebug);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $pathname = $scVerbose = $scDebug = $scLogFile = $scInc = NULL;
  unset($pathname, $scVerbose, $scDebug, $scLogFile, $scInc);
  return $ReturnData; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to scan a user supplied file on-demand with ScanCore.
function userScanCoreScan($FilesToScan) {
  // / Set variables.
  global $Verbose, $ConvertDir, $Lol, $Lolol, $UserScanCoreLogFile;
  $OperationSuccessful = $OperationErrors = $UserVirusFound = $userFilename = $userExtension = $clean = $copy = $variableIsSanitized = FALSE;
  $skip = TRUE;
  $returnData = $txt = $file = $scanCoreLogFileDATA = '';
  $txt = 'Initiating User Virus Scan with ScanCore.';
  userVirusLogEntry($txt, 'scancore');
  if ($Verbose) logEntry($txt);
  // / Make sure the input files are formatted into an array.
  if (!is_array($FilesToScan)) $FilesToScan = array($FilesToScan);
  // / Iterate through the array of input files.
  foreach ($FilesToScan as $file) {
    $UserVirusFound = FALSE;
    // / Make sure the file is sanitized before processing it.
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $OperationErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 19000, FALSE);
      continue; }
    if ($Verbose) logEntry('User selected to perform a ScanCore Scan on file '.$file.'.');
    // / Verify the file before performing any operations on it.
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname) = verifyFile($file, $userFilename, $userExtension, $clean, $copy, $skip);
    if (!$fileIsVerified) {
      $OperationErrors = TRUE;
      errorEntry('Could not verify the input file.', 19001, FALSE);
      continue; }
    $OperationSuccessful = TRUE;
    $txt = 'Scanning '.$file.'.';
    if ($Verbose) logEntry($txt);
    userVirusLogEntry($txt, 'scancore');
    // / Scan the selected file with ScanCore.
    $returnData = startScanCore($ConvertDir.$file, $UserScanCoreLogFile);
    // / Write the full ScanCore output to the normal $LogFile.
    if ($Verbose) logEntry('ScanCore returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
    // / Load the contents of the User ScanCore Log File for processing because it has been sanitized of unnecessary data & whitespace.
    $scanCoreLogFileDATA = @file_get_contents($UserScanCoreLogFile);
    // / Check the contents of the User ScanCore Log File for virus detections.
    if (strpos($scanCoreLogFileDATA, 'Infected') !== FALSE or strpos($scanCoreLogFileDATA, 'Infected') === TRUE) {
      $UserVirusFound = TRUE;
      $txt = 'WARNING!!! Potentially infected file detected at '.$file.'!';
      if ($Verbose) logEntry($txt);
      userVirusLogEntry($txt, 'scancore'); }
    // / Write the results of the scan to both log files.
    else {
      $txt = 'No infection detected in '.$file.'.';
      if ($Verbose) logEntry($txt);
      userVirusLogEntry($txt, 'scancore'); } }
  $txt = 'ScanCore Virus Scan Complete.';
  if ($Verbose) logEntry($txt);
  userVirusLogEntry($txt, 'scancore');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $variableIsSanitized = $clean = $copy = $skip = $returnData = $txt = $userFilename = $userExtension = $scanCoreLogFileDATA = NULL;
  unset($variableIsSanitized, $clean, $copy, $skip, $returnData, $txt, $userFilename, $userExtension, $scanCoreLogFileDATA);
  return array($OperationSuccessful, $OperationErrors, $UserVirusFound); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to process the results of a User Virus Scan & check for any failures or errors.
// / Type can be either 'clamav', 'scancore', or 'all'.
function checkUserVirusScanResults($type, $scan1Complete, $scan1Errors, $scan2Complete, $scan2Errors) {
  // / Set variables.
  $ScanErrors = FALSE;
  // / Check that all the input check results are valid.
  if (!is_bool($scan1Complete)) $scan1Complete = FALSE;
  if (!is_bool($scan1Errors)) $scan1Errors = FALSE;
  if (!is_bool($scan2Complete)) $scan2Complete = FALSE;
  if (!is_bool($scan2Errors)) $scan2Errors = FALSE;
  // / Check if all required scan operations are complete & if any erros occured.
  if ($type == 'all') {
    if ($scan1Complete && $scan2Complete) $ScanComplete = TRUE;
    if ($scan1Errors or $scan2Errors) $ScanErrors = TRUE; }
  // / Set results using only ClamAV output.
  if ($type == 'clamav') {
    $ScanComplete = TRUE;
     if ($scan1Complete) $ScanComplete = TRUE;
     if ($scan1Errors) $ScanErrors = TRUE; }
  // / Set results using only ScanCore output.
  if ($type == 'scancore') {
    $ScanComplete = TRUE;
     if ($scan2Complete) $ScanComplete = TRUE;
     if ($scan2Errors) $ScanErrors = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $scan1Complete = $scan1Errors = $scan2Complete = $scan2Errors = NULL;
  unset($scan1Complete, $scan1Errors, $scan2Complete, $scan2Errors);
  return array($ScanComplete, $ScanErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to define & verify that a Consolidated User Virus Log File exists.
// / Type can be either 'clamav', 'scancore', or 'all'.
function verifyConsolidatedLogFile() {
  // / Set variables.
  global $Verbose, $ConsolidatedLogFile, $Append;
  $ConsolidatedLogsExist = FALSE;
  // / Remove the old Consolidated Virus Log file if one already exists.
  if (file_exists($ConsolidatedLogFile)) {
    if ($Verbose) logEntry('Deleting stale consolidated log file.');
    @unlink($ConsolidatedLogFile); }
  // / Make sure that the stale file was deleted if required or creating a new one will cause problems.
  if (file_exists($ConsolidatedLogFile)) errorEntry('Could not delete stale file '.$ConsolidatedLogFile.'!', 20000, TRUE);
  // / Attempt to create a new consolidated log one if the previous one was successfully removed.
  else file_put_contents($ConsolidatedLogFile, '', $Append);
  // / Make sure that the file was successfully replaced.
  if (!file_exists($ConsolidatedLogFile)) errorEntry('Could not create a file at '.$ConsolidatedLogFile.'!', 20001, TRUE);
  else {
    $ConsolidatedLogsExist = TRUE;
    if ($Verbose) logEntry('Created a file at '.$ConsolidatedLogFile.'.'); }
  return array($ConsolidatedLogsExist, $ConsolidatedLogFile); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to consolidate User Virus Scan log files generated via various methods into one meaningful report.
// / Type can be either 'clamav', 'scancore', or 'all'.
function consolidateLogs($type, $UserClamLogFile, $UserScanCoreLogFile) {
  // / Set variables.
  global $Verbose, $Lol, $Append, $ConsolidatedLogFile, $UserClamLogFile, $UserScanCoreLogFile;
  $ConsolidatedLogsExist = $ConsolidatedLogErrors = $logWrittenA = $logWrittenB = $logWrittenC = $logWrittenD = $logWrittenE = FALSE;
  $userClamLogData = $userScanCoreData = $consolidatedLogData = $txt = $userScanCoreLogData = '';
  $spacer = '----------';
  list ($ConsolidatedLogsExist, $ConsolidatedLogFile) = verifyConsolidatedLogFile();
  if ($type === 'clamav') {
    // / Load the User Clam Log File into memory.
    $userClamLogData = file_get_contents($UserClamLogFile);
    $logWrittenA = file_put_contents($ConsolidatedLogFile, $userClamLogData.$Lol.$spacer.$Lol, $Append); }
  if ($type === 'scancore') {
    // / Load the User Scan Core Log File into memory.
    $userScanCoreLogData = file_get_contents($UserScanCoreLogFile);
    $logWrittenB = file_put_contents($ConsolidatedLogFile, $userScanCoreLogData.$Lol.$spacer.$Lol, $Append); }
  if ($type === 'all') {
    // / Load the Consolidated Log File into memory.
    $txt = 'User selected to scan files with all available scanners.';
    $logWrittenC = file_put_contents($ConsolidatedLogFile, $txt.$Lol.$spacer.$Lol, $Append);
    $userClamLogData = file_get_contents($UserClamLogFile);
    $logWrittenD = file_put_contents($ConsolidatedLogFile, $userClamLogData.$Lol.$spacer.$Lol, $Append);
    $userScanCoreLogData = file_get_contents($UserScanCoreLogFile);
    $logWrittenE = file_put_contents($ConsolidatedLogFile, $userScanCoreLogData.$Lol.$spacer.$Lol, $Append); }
  // / Check to be sure that the $ConsolidatedLogFile exists.
  if ($type === 'clamav' && !$logWrittenA) $ConsolidatedLogErrors = TRUE; 
  if ($type === 'scancore' && !$logWrittenB) $ConsolidatedLogErrors = TRUE;
  if ($type === 'all') if (!$logWrittenC or !$logWrittenD or !$logWrittenE) $ConsolidatedLogErrors = TRUE;
  if (file_exists($ConsolidatedLogFile)) $ConsolidatedLogsExist = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $type = $txt = $spacer = $logWrittenA = $logWrittenB = $logWrittenC = $logWrittenD = $logWrittenE = $userClamLogData = $userScanCoreLogData = NULL;
  unset($type, $txt, $spacer, $logWrittenA, $logWrittenB, $logWrittenC, $logWrittenD, $logWrittenE, $userClamLogData, $userScanCoreLogData);
  return array($ConsolidatedLogsExist, $ConsolidatedLogErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to scan an input file or folder for viruses with ClamAV.
// / Type can be either 'clamav', 'scancore', or 'all'.
function userVirusScan($FilesToScan, $type) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $ApplicationName, $UserClamLogFile, $UserScanCoreLogFile;
  $ScanComplete = $ScanErrors = $UserVirusFound = $scan1Complete = $scan1Errors = $scan2Complete = $scan2Errors = $ConsolidatedLogsExist = $ConsolidatedLogErrors = FALSE;
  $returnData = $fileToScan = '';
  // / Check that the $type input variable is valid.
  if ($type !== 'all' && $type !== 'clamav' && $type !== 'scancore') $type = 'all';
  // / Make sure the input files are formatted into an array.
  if (!is_array($FilesToScan)) $FilesToScan = array($FilesToScan);
  list ($LogsExist, $UserClamLogFile, $UserScanCoreLogFile) = verifyUserVirusLogs($type);
  // / Iterate through the array of input files.
  foreach ($FilesToScan as $fileToScan) {
    $ScanComplete = $scan1Complete = $scan2Complete = FALSE;
    // / Perform a User Virus Scan using ClamAV if required.
    if ($type === 'clamav' or $type === 'all') {
      // / Prepare to run a ClamAV Scan.
      list ($scan1Complete, $scan1Errors, $UserVirusFound) = userClamScan($FilesToScan); }
    // / Perform a User Virus Scan using ScanCore if required.
    if ($type === 'scancore' or $type === 'all') {
      // / Prepare to run a ScanCore Scan.
      list ($scan2Complete, $scan2Errors, $UserVirusFound) = userScanCoreScan($FilesToScan); } }
  // / Check the results of the virus scan for failures or errors.
  list ($ScanComplete, $ScanErrors) = checkUserVirusScanResults($type, $scan1Complete, $scan1Errors, $scan2Complete, $scan2Errors);
  // / Consolidate the log files created during the scan into the $ConvertTempDir so the user can access them.
  list ($ConsolidatedLogsExist, $ConsolidatedLogErrors) = consolidateLogs($type, $UserClamLogFile, $UserScanCoreLogFile);
  // / Verify that all operations are complete.
  if ($ScanErrors or $ConsolidatedLogErrors) $ScanErrors = TRUE;
  if (!$ConsolidatedLogsExist) $ScanComplete = FALSE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $fileToScan = $returnData = $path = $type = $scan1Complete = $scan1Errors = $scan2Complete = $scan2Errors = NULL;
  unset($fileToScan, $returnData ,$path, $type, $scan1Complete, $scan1Errors, $scan2Complete, $scan2Errors);
  return array($ScanComplete, $ScanErrors, $UserVirusFound); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The main logic of the program that makes use of the functions above.

// / The following code resets PHP's time limit for execution.
$TimeReset = setTimeLimit();
if (!$TimeReset) die('ERROR!!! HRConvert2-3: Could not set the execution timer!');

// / The following code sets date & time related variables.
list ($TimeIsSet, $Date, $Time) = verifyTime();
if (!$TimeIsSet or !$Date or !$Time) die('ERROR!!! HRConvert2-4: Could not verify timezone!');

// / The following code verifies that the installation is valid.
list ($InstallationIsVerified, $ConfigFile, $Version) = verifyInstallation();
if (!$InstallationIsVerified) die('ERROR!!! '.$Time.', HRConvert2-5: Could not verify installation!');

// / The following code verifies that string inputs to the core are properly sanitized.
list ($InputsAreVerified, $GUI, $Color, $Language, $Token1, $Token2, $Height, $Width, $Rotate, $Bitrate, $Method, $Download, $UserFilename, $UserExtension, $FilesToArchive, $PDFWorkSelected, $ConvertSelected, $FilesToScan, $FilesToDelete, $UserScanType) = verifyInputs();
if (!$InputsAreVerified) die('ERROR!!! '.$Time.', '.$ApplicationName.'-6: Could not verify inputs!');

// / The following code verifies enough user information to generate a unique session identifier.
list ($SessionIsVerified, $IP, $HashedUserAgent) = verifySession();
if (!$SessionIsVerified) die('ERROR!!! '.$Time.', '.$ApplicationName.'-7: Could not verify session!');

// / The following code generates a series of unique session identifiers.
list ($SesHashIsVerified, $SesHash, $SesHash2, $SesHash3, $SesHash4) = verifySesHash($IP, $HashedUserAgent);
if (!$SesHashIsVerified) die('ERROR!!! '.$Time.': '.$ApplicationName.'-8: Could not verify unique session identifier!');

// / The following code verifies the logging environment.
list ($LogFileExists, $LogFile, $ClamLogFile) = verifyLogs();
if (!$LogFileExists) die('ERROR!!! '.$Time.', '.$ApplicationName.'-9, '.$SesHash3.': Could not verify logging environment!');

// / The following code tries to verify that the session is encrypted, if possible.
list ($EncryptionVerified, $URLEcho) = verifyEncryption();
if (!$EncryptionVerified) errorEntry('Could not verify connection!', 10, TRUE);
else if ($Verbose) logEntry('Verified inbound connection.');

// / The following code verifies & sanitizes global variables for the session.
list ($GlobalsAreVerified, $CoreLoaded) = verifyGlobals();
if (!$GlobalsAreVerified) errorEntry('Could not verify globals!', 11, TRUE);
else if ($Verbose) logEntry('Verified globals.');

// / The following code verifies that required directories exist & creates them where needed.
list ($RequiredDirsExist, $RequiredDirs) = verifyRequiredDirs();
if (!$RequiredDirsExist) errorEntry('Could not verify required directories!', 12, TRUE);
else if ($Verbose) logEntry('Verified required directories.');

// / The following code removes old files from the $ConvertTempLoc.
list ($CleanedTempLoc, $TempLocDeepCleaned) = cleanTempLoc();
if (!$CleanedTempLoc) errorEntry('Could not clean the temporary location!', 13, TRUE);
else if ($Verbose) logEntry('Cleaned temporary location.');

// / The following code removes old files from the $ConvertLoc.
list ($CleanedConvertLoc, $ConvertLocDeepCleaned) = cleanConvertLoc();
if (!$CleanedConvertLoc) errorEntry('Could not clean the convert location!', 14, TRUE);
else if ($Verbose) logEntry('Cleaned convert location.');

// / The following code verifies the tokens supplied by the user, if any.
list ($TokensAreValid, $Token1, $Token2) = verifyTokens($Token1, $Token2);
if (!$TokensAreValid) if ($Verbose) logEntry('Could not verify tokens.');
if ($TokensAreValid) if ($Verbose) logEntry('Verified tokens.');

// / The following code sets the language for the session.
list ($GuiIsSet, $GuiToUse, $GuiDir, $GuiFiles) = verifyGui();
if (!$GuiIsSet) errorEntry('Could not verify GUI! GUI set to '.$GuiToUse.'!', 25, TRUE);
else if ($Verbose) logEntry('Verified GUI. GUI set to '.$GuiToUse.'.');

// / The following code sets the color scheme for the session.
list ($ColorsAreSet, $ButtonCode) = verifyColors($ButtonStyle);
if (!$ColorsAreSet) errorEntry('Could not verify color scheme! Color set to '.$ButtonStyle.'!', 15, TRUE);
else if ($Verbose) logEntry('Verified color scheme. Color set to '.$ButtonStyle.'.');

// / The following code sets the language for the session.
list ($LanguageIsSet, $LanguageToUse, $LanguageDir, $LanguageFiles) = verifyLanguage();
if (!$LanguageIsSet) errorEntry('Could not verify language! Language set to '.$LanguageToUse.'!', 16, TRUE);
else if ($Verbose) logEntry('Verified language. Language set to '.$LanguageToUse.'.');

// / The following code displays the appropriate GUI for the session.
if (!isset($_POST['filesToArchive']) && !isset($_POST['convertSelected']) && !isset($_POST['pdfworkSelected']) && !isset($_POST['download']) && !isset($_POST['upload']) && !isset($_POST['filesToScan'])) {
  $GUIDisplayed = showGUI($ShowGUI, $ButtonCode);
  if (!$GUIDisplayed) errorEntry('Could not display GUI!', 17, TRUE);
  else if ($Verbose)  logEntry('Displaying the GUI.'); }
else if ($Verbose) logEntry('Skipping display GUI procedure.');

// / Only enable file related operations if valid tokens have been supplied.
if ($TokensAreValid) {
  // / The following code is performed when a user initiates a file upload.
  if ($TokensAreValid && !empty($_FILES)) {
    logEntry('Initiating Uploader.');
    list ($UploadComplete, $UploadErrors) = uploadFiles();
    if (!$UploadComplete) errorEntry('Upload Failed!', 18, TRUE);
    if ($UploadErrors) logEntry('Upload finished with errors.');
    if ($Verbose) logEntry('Upload Complete.'); }

  // / The following code is performed when a user downloads a selection of files.
  if (isset($_POST['download'])) {
    logEntry('Initiating Downloader.');
    list ($DownloadComplete, $DownloadErrors) = downloadFiles($Download);
    if (!$DownloadComplete) errorEntry('Download Failed!', 19, TRUE);
    if ($DownloadErrors) logEntry('Download finished with errors.');
    if ($Verbose) logEntry('Download Complete.'); }

  // / The following code is performed when a user deletes a selection of files.
  if (isset($_POST['filesToDelete'])) {
    logEntry('Initiating Deletor.');
    list ($DeleteComplete, $DeleteErrors) = deleteFiles($FilesToDelete);
    if (!$DeleteComplete) errorEntry('Delete Failed!', 24, TRUE);
    if ($DeleteErrors) logEntry('Delete finished with errors.');
    if ($Verbose) logEntry('Delete Complete.'); }

  // / The following code is performed when a user archives a selection of files.
  if (isset($_POST['filesToArchive'])) { 
    logEntry('Initiating Archiver.');
    list ($ArchiveComplete, $ArchiveErrors) = archiveFiles($FilesToArchive, $UserFilename, $UserExtension);
    if (!$ArchiveComplete) errorEntry('Archive Failed!', 20, TRUE);
    if ($ArchiveErrors) logEntry('Archive finished with errors.');
    if ($Verbose) logEntry('Archive Complete.'); }

  // / The following code is performed when a user converts a selection of files.
  if (isset($_POST['convertSelected'])) {
    logEntry('Initiating Converter.');
    list ($ConversionComplete, $ConversionErrors) = convertFiles($ConvertSelected, $UserFilename, $UserExtension, $Height, $Width, $Rotate, $Bitrate);
    if (!$ConversionComplete) errorEntry('Conversion Failed!', 21, TRUE);
    if ($ConversionErrors) logEntry('Conversion finished with errors.');
    if ($Verbose) logEntry('Conversion Complete.'); }

  // / The following code is performed when a user performs OCR on a selection of files.
  if (isset($_POST['pdfworkSelected'])) {
    logEntry('Initiating Converter.');
    list ($ConversionComplete, $ConversionErrors) = ocrFiles($PDFWorkSelected, $UserFilename, $UserExtension, $Method);
    if (!$ConversionComplete) errorEntry('OCR Operation Failed!', 22, TRUE);
    if ($ConversionErrors) logEntry('OCR Operation finished with errors.');
    if ($Verbose)  logEntry('Conversion Complete.'); }

  // / The following code is performed when a user performs a virus scan on a selection of files.
  if (isset($_POST['filesToScan']) && $AllowUserVirusScan) {
    logEntry('Initiating User Virus Scannner.');
    list ($ScanComplete, $ScanErrors, $UserVirusFound) = userVirusScan($FilesToScan, $UserScanType);
    if (!$ScanComplete) errorEntry('User Virus Scan Failed!', 23, TRUE);
    if ($UserVirusFound) logEntry('The User Virus Scan detected infected files.');
    if (!$UserVirusFound) logEntry('The User Virus Scan did not detect any infected files.');
    if ($ScanErrors) logEntry('User Virus Scan finished with errors.');
    if ($Verbose)  logEntry('User Virus Scan Complete.'); } }
// / -----------------------------------------------------------------------------------
?>
