<!DOCTYPE HTML>
<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRConvert2, Copyright on 8/5/2026 by Justin Grimes, www.github.com/zelon88
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
// / v3.5.4.
// / This file contains the core logic of the application.
// /
// / HARDWARE REQUIREMENTS ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / DEPENDENCY REQUIREMENTS ...
// / This application requires Debian Linux, Apache 2.4, PHP 8+, FFMPEG, Dia,
// / Mkisofs, 7zip, LibreOffice, Unoconv, libgxps-utils, Tesseract, Unzip, Rar,
// / Unrar, ClamAV, MeshLab, PopplerUtils, PDFTOTEXT, ImageMagick & xvfb-run.
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
// A function to thwart any potential blind attacks that utilize time as an exfiltration mechanism.
// Introduces arbitrary noise in the form of random delay timers.
// This function introduces entropy into the duration of every HRConvert2 operation.
// Set $opType to one of the following options; sanitize, filework, or core.
// When $opType is set to ''
function timingProtect($opType) {


}
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to sanitize input strings with varying degrees of tolerance.
// / Filters a given string of | \ ~ # [ ] ( ) { } ; : $ ! # ^ & % @ > * < " / ' ` chr(9) chr(10) chr(13) chr(0)
// / This function will replace any of the above specified charcters with NOTHING. No character at all. An empty string.
// / This function will replace whitespace with the underscore character.
// / This function will remove leading and traling dashes.
// / Set $strict to TRUE to also filter out backslash characters as well. Example:  /
function sanitizeString($Variable, $strict) {
  // / Set variables.
  // / Note that this function does not use the global $DangerousFiles. 
  // / Instead this function defines & destroys it's own array every time it is called.
  $dangerFiles = array(NULL, '.js', '.php', '.html', '.css', '.phar', '..', 'index.php', 'index.html', '--');
  // / Check for dangerous files or escape conditions.
  foreach ($dangerFiles as $danFile) $Variable = str_replace($danFile, '', $Variable);
  if ($strict) $Variable = trim(trim(str_replace(' ', '_', str_replace('..', '', str_replace('//', '', str_replace(str_split(',|\\~#[](){};:$!#^&%@>*<"\'/`'.chr(9).chr(10).chr(13).chr(0)), '', $Variable))))), '-');
  if (!$strict) $Variable = trim(trim(str_replace(' ', '_', str_replace('..', '', str_replace('//', '', str_replace(str_split(',|\\~#[](){};:$!#^&%@>*<"\'`'.chr(9).chr(10).chr(13).chr(0)), '', $Variable))))), '-');
  // / Check for dangerous files or escape conditions one more time.
  foreach ($dangerFiles as $danFile) $Variable = str_replace($danFile, '', $Variable);
  // / Trim the variable one last time to avoid any crafted leading dashes or directory separators.
  $Variable = trim(trim(trim(trim($Variable, '-'), '.'), '-'), '.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $strict = $dangerFiles = $danFile = NULL;
  unset($strict, $dangerFiles, $danFile);
  return $Variable; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to sanitize input strings or arrays with varying degrees of tolerance.
// / Filters a given string of | \ ~ # [ ] ( ) { } ; : $ ! # ^ & % @ > * < " / ' ` chr(9) chr(10) chr(13) chr(0)
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
// / A function to produce random number variables for truly unique per-session identifiers.
// / This function uses the built-in PHP "random_int" function to generate the random number.
// / This function does not generate it's own number. It produces a number from random_int().
// / Random number range is hardcoded to 100000000000000000 to 999999999999999999 for consistency.
// / Returns FALSE in $RandomNumberCheck if the system entropy source was unavailable.
// / The caller MUST check $RandomNumberCheck. A predictable identifier is worse than none.
function generateRandomNumber() {
  // / Set variables.
  $RandomNumber = FALSE;
  $RandomNumberCheck = TRUE;
  // / random_int() throws rather than returning a poor result when entropy is unavailable.
  try { $RandomNumber = random_int(100000000000000000, 999999999999999999); }
  catch (Throwable $error) { $RandomNumberCheck = FALSE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $error = NULL;
  unset($error);
  return array($RandomNumber, $RandomNumberCheck); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to generate the per-install secret used to derive session identifiers.
// / 32 bytes gives 256 bits of entropy & returns as a 64 hexadecimal character string.
function generateInstallSecret() {
  // / Set variables.
  $InstallSecret = FALSE;
  $InstallSecretCheck = TRUE;
  // / random_bytes() throws rather than returning a poor result when entropy is unavailable.
  // / Fail closed. A predictable secret is worse than no installation at all.
  try { $InstallSecret = bin2hex(random_bytes(32)); }
  catch (Throwable $error) { $InstallSecretCheck = FALSE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $error = NULL;
  unset($error);
  return array($InstallSecret, $InstallSecretCheck); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to load required HRConvert2 files.
// / This function verifies the installation environment.
function verifyInstallation() {
  // / Set variables.
  global $URL, $VirusScan, $AllowUserVirusScan, $InstLoc, $ServerRootDir, $ConvertLoc, $LogDir, $ApplicationName, $ApplicationTitle, $SupportedLanguages, $DefaultLanguage, $AllowUserSelectableLanguage, $SupportedGuis, $DefaultGui, $AllowUserSelectableGui, $DeleteThreshold, $Verbose, $MaxLogSize, $Font, $ButtonStyle, $DefaultColor, $SupportedColors, $AllowUserSelectableColor, $ColorToUse, $ShowGUI, $ShowFinePrint, $TOSURL, $PPURL, $ScanCoreMemoryLimit, $ScanCoreChunkSize, $ScanCoreDebug, $ScanCoreVerbose, $SpinnerStyle, $SpinnerColor, $AllowUserShare, $SupportedConversionTypes, $VersionInfoFile, $Version, $UserArchiveArray, $UserDearchiveArray, $UserDocumentArray, $UserSpreadsheetArray, $UserPresentationInputArray, $UserPresentationOutputArray, $UserXPSInputArray, $UserXPSOutputArray, $UserImageArray, $UserMediaInputArray, $UserMediaOutputArray, $UserVideoInputArray, $UserVideoOutputArray, $UserStreamArray, $UserDrawingArray, $UserModelArray, $UserSubtitleInputArray, $UserSubtitleOutputArray, $UserPDFWorkArr, $RARArchiveMethod, $RetryCount, $DocumentEngineSleepTimer, $HomeLoc, $ProprietaryLoc, $UsePatchedDocumentEngine, $StreamTemp, $StreamWatchTimeout, $StreamConnectionTimeout, $AllowStreamOverHTTP, $StreamInspectionLayers, $StreamInspectionFilesPerLayer, $DefaultStreamInspectionForfeitAction, $MaxStreamInspectionFileSize, $UniqueDailyLogHash, $AppendLogHashToLogFiles, $SecretKey, $MinimumSCADVersion, $AllowSCADIncludeResolution, $SCADConversionTimeout, $UserSCADArray, $SCADArray;
  $InstallationIsVerified = $secret = $secretFile = $secretFileContent = $createSecretFile = $SecretKey = $secretFailed = $loadSecretFile = $secretFileWriteComplete = $secretCheck = FALSE;
  $check1 = $check2 = TRUE;
  $bytesWritten = 0;
  // / Define absolute paths for files that we only have relative paths for.
  $ConfigFile = realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'config.php');
  $VersionInfoFile = realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'versionInfo.php');
  // / Check for required files & stop execution if they are missing.
  if (!file_exists($ConfigFile)) die ('ERROR!!! HRConvert2-0: Could not process the HRConvert2 Configuration file (config.php)!'.PHP_EOL.'<br />');
  else require_once ($ConfigFile);
  if (!file_exists($VersionInfoFile)) die ('ERROR!!! HRConvert2-24000: Could not process the HRConvert2 Version Information file (versionInfo.php)!'.PHP_EOL.'<br />');
  else require_once ($VersionInfoFile);
  // / Define the location of the per-install secret file.
  $secretFile = $ConvertLoc.DIRECTORY_SEPARATOR.'secret.php';
  // / If a secret file does not exist, create one.
  if (!file_exists($secretFile)) {
    $createSecretFile = TRUE;
    list ($secret, $secretCheck) = generateInstallSecret();
    if ($secretCheck) {
      $secretFileContent = '<?php $SecretKey = \''.$secret.'\';';
      $bytesWritten = file_put_contents($secretFile, $secretFileContent, LOCK_EX); }
    // / Check that the secret key, & only the secret key, was written to the secret file.
    // / If we just appended the secret to an existing file this will catch it & delete the file.
    if ($secretCheck && $bytesWritten === strlen($secretFileContent)) {
      @chmod($secretFile, 0600);
      $SecretKey = $secret;
      $secretFileWriteComplete = TRUE; }
    else if (file_exists($secretFile)) @unlink($secretFile); }
  // / If a secret file does exist, load it & make sure it is valid.
  else {
    @chmod($secretFile, 0600);
    $loadSecretFile = TRUE;
    require_once ($secretFile);
    if (empty($SecretKey) or strlen($SecretKey) !== 64) $secretFailed = TRUE; }
  // / Check if a secret file was needed, & whether it was actually created.
  if ($createSecretFile) if (!$secretFileWriteComplete) $check1 = FALSE;
  // / Check if a secret key file was found, & if the secret key was loaded successfully.
  if ($loadSecretFile) if ($secretFailed or empty($SecretKey)) $check2 = FALSE;
  // / Perform a check to see if all required tests passed.
  if ($check1 && $check2) $InstallationIsVerified = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $SecretKey is deliberately NOT cleared here because the rest of the core needs it.
  $secret = $secretCheck = $secretFile = $secretFileContent = $secretFileWriteComplete = $createSecretFile = $loadSecretFile = $secretFailed = $bytesWritten = $check1 = $check2 = NULL;
  unset($secret, $secretCheck, $secretFile, $secretFileContent, $secretFileWriteComplete, $createSecretFile, $loadSecretFile, $secretFailed, $bytesWritten, $check1, $check2);
  return array($InstallationIsVerified, $ConfigFile, $Version); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to detect the users IP & user agent.
// / NOTE: As of v3.5.x the IP is NO LONGER used to derive any session identifier.
// / HTTP_CLIENT_IP & HTTP_X_FORWARDED_FOR are attacker-supplied headers & cannot be trusted
// / as a binding factor. They also rotate legitimately on mobile networks, which broke sessions.
// / The token pair is the credential. The IP is retained for logging purposes only.
function verifySession() {
  // / Set variables.
  $IP = '';
  $SessionIsVerified = FALSE;
  list ($HashedUserAgent, $SessionIsVerified) = sanitize(hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? ''), TRUE);
  // / Detect an IP for logging. Header-supplied values are recorded but never trusted.
  if (!empty($_SERVER['HTTP_CLIENT_IP'])) list ($IP, $SessionIsVerified) = sanitize($_SERVER['HTTP_CLIENT_IP'], TRUE);
  elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) list ($IP, $SessionIsVerified) = sanitize($_SERVER['HTTP_X_FORWARDED_FOR'], TRUE);
  else list ($IP, $SessionIsVerified) = sanitize($_SERVER['REMOTE_ADDR'] ?? '', TRUE);
  return array($SessionIsVerified, $IP, $HashedUserAgent); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to define the $SesHash related variables for the session.
// / $SesHash  = per-server-per-day. The parent directory holding every session for today.
// / $SesHash2 = per-session. The individual user directory inside today's parent.
// / $SesHash3 = the combined relative path, "[server-daily]/[session]".
// / $SesHash4 = per-server-per-day log file identifier. Never tied to any individual session.
// / Every one of these is derived with HMAC keyed on the per-install $SecretKey.
// / Nothing here is derivable from the server name, the date, the version file, or a default
// / config.php. An attacker who knows the machine still cannot compute any of these values.
function verifySesHash($Token1) {
  // / Set variables.
  global $Date, $SecretKey, $UniqueDailyLogHash;
  $SesHashIsVerified = $inputsAreUsable = FALSE;
  $SesHash = $SesHash2 = $SesHash3 = $SesHash4 = FALSE;
  $dailyContext = '';
  // / Both the install secret & a well formed Token1 are required before anything can be derived.
  if (!empty($SecretKey) && strlen($SecretKey) === 64 && !empty($Token1) && ctype_digit((string)$Token1) && strlen((string)$Token1) === 18) $inputsAreUsable = TRUE;
  if ($inputsAreUsable) {
    // / The daily context. Server name is included only for domain separation between vhosts
    // / sharing one installation. It contributes no secrecy & is not relied on for any.
    $dailyContext = 'HRC2-DAILY|'.$Date.'|'.($_SERVER['SERVER_NAME'] ?? '');
    // / Per-server-per-day parent directory.
    $SesHash = substr(hash_hmac('sha256', $dailyContext, $SecretKey), -18);
    // / Per-session directory. Bound to the daily parent so a token cannot be replayed into a different day's directory tree.
    $SesHash2 = substr(hash_hmac('sha256', 'HRC2-SESSION|'.$SesHash.'|'.(string)$Token1, $SecretKey), -18);
    // / The combined relative path used everywhere else in the core.
    $SesHash3 = $SesHash.DIRECTORY_SEPARATOR.$SesHash2;
    // / The log file identifier. Distinct context string so it can never collide with $SesHash.
    // / Derived only from daily context so it is never tied to an individual user.
    if ($UniqueDailyLogHash) $SesHash4 = substr(hash_hmac('sha256', 'HRC2-LOG|'.$dailyContext, $SecretKey), -18);
    else $SesHash4 = substr(hash_hmac('sha256', 'HRC2-LOG|'.($_SERVER['SERVER_NAME'] ?? ''), $SecretKey), -18);
    // / Confirm every identifier came out the expected length before anything trusts them.
    if (strlen($SesHash) === 18 && strlen($SesHash2) === 18 && strlen($SesHash4) === 18 && !empty($SesHash3)) $SesHashIsVerified = TRUE; }
  // / Any failure at any point invalidates all four. Never hand back a partial set.
  if (!$SesHashIsVerified) $SesHash = $SesHash2 = $SesHash3 = $SesHash4 = FALSE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $dailyContext = $inputsAreUsable = NULL;
  unset($dailyContext, $inputsAreUsable);
  return array($SesHashIsVerified, $SesHash, $SesHash2, $SesHash3, $SesHash4); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to create a logfile if one does not exist.
// / The log hash suffix is assembled before any filename that uses it is built.
// / The ClamAV rotation loop rotates the ClamAV log instead of the application log.
// / The rotation condition compares the file size directly against the configured maximum.
function verifyLogs() {
  // / Set variables.
  global $LogDir, $LogFile, $MaxLogSize, $InstLoc, $SesHash, $SesHash4, $DefaultLogDir, $DefaultLogSize, $Time, $Date, $LogInc, $LogInc2, $VirusScan, $ApplicationName, $PermissionLevels, $ConvertLoc, $AppendLogHashToLogFiles;
  $LogExists = $logWritten = FALSE;
  $logHashAppend = '';
  $LogInc = $LogInc2 = 0;
  $DefaultLogDir = $ConvertLoc.'/Logs';
  $DefaultLogSize = 1048576;
  // / Build the hash suffix before it is used in any filename.
  if ($AppendLogHashToLogFiles) $logHashAppend = '_'.$SesHash4;
  $ClamLogFile = str_replace('..', '', $LogDir.'/ClamLog_'.$LogInc2.'_'.$Date.$logHashAppend.'.txt');
  $LogFile = str_replace('..', '', $LogDir.'/'.$ApplicationName.'_'.$LogInc.'_'.$Date.$logHashAppend.'.txt');
  if (!is_numeric($MaxLogSize)) $MaxLogSize = $DefaultLogSize;
  if (!is_dir($LogDir)) @mkdir($LogDir, $PermissionLevels);
  if (!is_dir($LogDir)) $LogDir = $DefaultLogDir;
  if (!is_dir($LogDir)) die('ERROR!!! '.$Time.': '.$ApplicationName.'-3, The log directory does not exist at '.$LogDir.'.');
  if (!file_exists($LogDir.'/index.html')) @copy('index.html', $LogDir.'/index.html');
  // / Advance to a new log file whenever the current one has reached the maximum size.
  while (file_exists($LogFile) && filesize($LogFile) > $MaxLogSize) {
    $LogInc++;
    $LogFile = str_replace('..', '', $LogDir.'/'.$ApplicationName.'_'.$LogInc.'_'.$Date.$logHashAppend.'.txt'); }
  if (!file_exists($LogFile)) $logWritten = file_put_contents($LogFile, 'OP-Act, '.$Time.': Logfile created using method 1.'.PHP_EOL, FILE_APPEND);
  if (file_exists($LogFile)) $LogExists = TRUE;
  // / Set a clamlog file depending on whether or not the max filesize has been reached.
  // / The ClamAV log file is not created here, only named.
  if ($VirusScan) {
    while (file_exists($ClamLogFile) && filesize($ClamLogFile) > $MaxLogSize) {
      $LogInc2++;
      $ClamLogFile = str_replace('..', '', $LogDir.'/ClamLog_'.$LogInc2.'_'.$Date.$logHashAppend.'.txt'); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $logWritten = $logHashAppend = NULL;
  unset($logWritten, $logHashAppend);
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
// / A function to set or validate the token pair used to identify a session.
// / Token1 is the session credential & carries the entropy. It is an 18 digit random number.
// / Token2 is an HMAC signature over Token1, keyed with the per-install $SecretKey.
// / An attacker who does not hold $SecretKey cannot produce a valid Token2 for any Token1.
// / This validation touches no files & depends on no directory state.
// / This validation runs BEFORE the session hashes exist & introduces no circular dependency.
// / This validation is arithmetic only. 
function verifyTokens($Token1, $Token2) {
  // / Set variables.
  global $SecretKey;
  $TokensAreValid = $randomCheck = $secretIsUsable = $issueNewSession = FALSE;
  $expectedToken2 = '';
  // / Without the install secret nothing can be validated or signed.
  if (!empty($SecretKey) && strlen($SecretKey) === 64) $secretIsUsable = TRUE;
  // / An absent or malformed Token1 means there is no session to validate, so issue a new one.
  // / Note this deliberately discards any Token2 that arrived with it. A Token2 signed
  // / over a token we are about to replace is meaningless.
  if (empty($Token1) or !ctype_digit((string)$Token1) or strlen((string)$Token1) !== 18) $issueNewSession = TRUE;
  // / Token1 is well formed, so recompute what Token2 SHOULD be & compare.
  // / hash_equals() compares in constant time so the comparison itself leaks nothing.
  elseif ($secretIsUsable) {
    $expectedToken2 = hash_hmac('sha256', (string)$Token1, $SecretKey);
    if (!empty($Token2) && hash_equals($expectedToken2, (string)$Token2)) $TokensAreValid = TRUE;
    // / The pair did not verify. Do not trust either half. Issue an entirely new session.
    else $issueNewSession = TRUE; }
  // / Issue a fresh token pair. This is the only place tokens are ever generated.
  if ($issueNewSession && $secretIsUsable) {
    list ($Token1, $randomCheck) = generateRandomNumber();
    if ($randomCheck) {
      $Token2 = hash_hmac('sha256', (string)$Token1, $SecretKey);
      $TokensAreValid = TRUE; } }
  // / Any failure at any point invalidates both halves. Never hand back a partial pair.
  if (!$TokensAreValid) $Token1 = $Token2 = FALSE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $expectedToken2 = $randomCheck = $secretIsUsable = $issueNewSession = NULL;
  unset($expectedToken2, $randomCheck, $secretIsUsable, $issueNewSession);
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
  if (isset($_GET['language'])) list ($_GET['language'], $variableIsSanitized[$key++]) = sanitize($_GET['language'], TRUE);
  if (isset($_GET['language'])) list ($Language, $variableIsSanitized[$key++]) = sanitize($_GET['language'], TRUE);
  if (isset($_POST['color'])) list ($Color, $variableIsSanitized[$key++]) = sanitize($_POST['color'], TRUE);
  if (isset($_GET['color'])) list ($_GET['color'], $variableIsSanitized[$key++]) = sanitize($_GET['color'], TRUE);
  if (isset($_GET['color'])) list ($Color, $variableIsSanitized[$key++]) = sanitize($_GET['color'], TRUE);
  if (isset($_POST['gui'])) list ($GUI, $variableIsSanitized[$key++]) = sanitize($_POST['gui'], TRUE);
  if (isset($_GET['gui'])) list ($_GET['gui'], $variableIsSanitized[$key++]) = sanitize($_GET['gui'], TRUE);
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
// / A function to set the global variables for the session.
// / The stream timeouts are left in the units config.php documents them in.
// / $StreamWatchTimeout is stated in minutes & $StreamConnectionTimeout is stated in seconds.
// / Each point of use converts once, where the required unit is actually known.
// / Converting here as well produced a fifteen hour watch timeout & a ten million second connect timeout.
function verifyGlobals() {
  // / Set global variables to be used through the entire application.
  global $URL, $URLEcho, $HRConvertVersion, $Date, $Time, $SesHash, $SesHash2, $SesHash3, $SesHash4, $CoreLoaded, $ConvertDir, $InstLoc, $ConvertTemp, $ConvertTempDir, $ConvertGuiCounter1, $DefaultApps, $RequiredDirs, $RequiredIndexes, $DangerousFiles, $Allowed, $ArchiveArray, $DearchiveArray, $DocumentArray, $SpreadsheetArray, $PresentationInputArray, $PresentationOutputArray, $XPSInputArray, $XPSOutputArray, $ImageArray, $MediaInputArray, $MediaOutputArray, $VideoInputArray, $VideoOutputArray, $StreamArray, $DrawingArray, $ModelArray, $SubtitleInputArray, $SubtitleOutputArray, $PDFWorkArr, $ConvertLoc, $DirSep, $SupportedConversionTypes, $Lol, $Lolol, $Append, $PathExt, $ConsolidatedLogFileName, $ConsolidatedLogFile, $Alert, $Alert1, $Alert2, $Alert3, $FCPlural, $FCPlural1, $FCPlural2, $FCPlural3, $UserClamLogFile, $UserClamLogFileName, $UserScanCoreLogFile, $UserScanCoreFileName, $SpinnerStyle, $SpinnerColor, $FullURL, $ServerRootDir, $StopCounter, $SleepTimer, $PermissionLevels, $ApacheUser, $File, $HeaderDisplayed, $UIDisplayed, $FooterDisplayed, $LanguageStringsLoaded, $GUIDisplayed, $Version, $GUIDirection, $SupportedFormatCount, $GUIAlignment, $GreenButtonCode, $BlueButtonCode, $RedButtonCode, $DefaultButtonCode, $UserArchiveArray, $UserDearchiveArray, $UserDocumentArray, $UserSpreadsheetArray, $UserXPSInputArray, $UserXPSOutputArray, $UserPresentationInputArray, $UserPresentationOutputArray, $UserImageArray, $UserMediaInputArray, $UserMediaOutputArray, $UserVideoInputArray, $UserVideoOutputArray, $UserStreamArray, $UserDrawingArray, $UserModelArray, $UserSubtitleInputArray, $UserSubtitleOutputArray, $UserPDFWorkArr, $RetryCount, $DocumentEngineSleepTimer, $HomeLoc, $ProprietaryLoc, $RequiredCleanupFolders, $PathToUnoconv, $UsePatchedDocumentEngine, $StreamTemp, $StreamWatchTimeout, $StreamConnectionTimeout, $AllowStreamOverHTTP, $StreamInspectionLayers, $StreamInspectionFilesPerLayer, $DefaultStreamInspectionForfeitAction, $MaxStreamInspectionFileSize, $WaitForStream, $StreamPID, $StreamOutputPath, $LogDir, $StreamOutputArray, $ScadTemp, $MinimumSCADVersion, $AllowSCADIncludeResolution, $SCADConversionTimeout, $UserSCADArray, $SCADArray, $SCADOutputArray;
  // / Application related variables.
  putenv('HOME='.$HomeLoc);
  $HRConvertVersion = 'v3.5.4';
  $GlobalsAreVerified = FALSE;
  $CoreLoaded = TRUE;
  $SleepTimer = 0;
  $StopCounter = $RetryCount;
  $PermissionLevels = 0755;
  $ApacheUser = 'www-data';
  // / Convinience variables.
  $DirSep = DIRECTORY_SEPARATOR;
  $Lol = PHP_EOL;
  $Lolol = PHP_EOL.PHP_EOL;
  $Append = FILE_APPEND;
  $PathExt = PATHINFO_EXTENSION;
  // / UI Related variables.
  $ConvertGuiCounter1 = 0;
  $File = $FCPlural = $FCPlural1 = $FCPlural2 = $FCPlural3 = $GreenButtonCode = $BlueButtonCode = $RedButtonCode = $DefaultButtonCode = '';
  $HeaderDisplayed = $UIDisplayed = $FooterDisplayed = $LanguageStringsLoaded = $GUIDisplayed = FALSE;
  $GUIDirection = 'ltr';
  $GUIAlignment = 'left';
  $Alert = 'Cannot convert this file! Try changing the name.';
  $Alert1 = 'Cannot perform a virus scan on this file!';
  $Alert2 = 'File Link Copied to Clipboard!';
  $Alert3 = 'Operation Failed!';
  // / Security related variables.
  $DefaultApps = array('.', '..');
  $DangerousFiles = array(NULL, '.js', '.php', '.html', '.css', '.phar', '..', 'index.php', 'index.html', '--');
  // / Stream related variables.
  // / The two stream timeouts are deliberately left in their documented units here.
  // / Do not convert them in this function.
  $WaitForStream = FALSE;
  $StreamPID = 0;
  $StreamOutputPath = '';
  // / URL related variables.
  $subDir = sanitizeString(str_replace($ServerRootDir.$DirSep, '', $InstLoc), FALSE);
  $partURL = sanitizeString($URL.'/'.$subDir, FALSE);
  $FullURL = 'http'.$URLEcho.'://'.$partURL;
  // / Directory related variables.
  $convertDir0 = sanitizeString($ConvertLoc.$DirSep.$SesHash, FALSE);
  $ConvertDir = sanitizeString($convertDir0.$DirSep.$SesHash2.$DirSep, FALSE);
  $ConvertTemp = sanitizeString($InstLoc.$DirSep.'DATA', FALSE);
  $convertTempDir0 = sanitizeString($ConvertTemp.$DirSep.$SesHash, FALSE);
  $ConvertTempDir = sanitizeString($convertTempDir0.$DirSep.$SesHash2.$DirSep, FALSE);
  $StreamTemp = $ConvertDir.'StreamTemp';
  $ScadTemp = $ConvertDir.'ScadTemp';
  $RequiredDirs = array($HomeLoc, $convertDir0, $ConvertDir, $ConvertTemp, $convertTempDir0, $ConvertTempDir, $StreamTemp, $ScadTemp, $LogDir);
  $RequiredIndexes = array($ConvertTemp, $convertTempDir0, $ConvertTempDir);
  $RequiredCleanupFolders = array($InstLoc.$DirSep.'Logs', $InstLoc.$DirSep.'.cache', $InstLoc.$DirSep.'.config', $ProprietaryLoc.$DirSep.'.cache', $ProprietaryLoc.$DirSep.'.config');
  $PathToUnoconv = $InstLoc.$DirSep.'Resources'.$DirSep.'Unoconv'.$DirSep.'unoconv';
  if (!$UsePatchedDocumentEngine) $PathToUnoconv = $DirSep.'usr'.$DirSep.'bin'.$DirSep.'unoconv';
  // / A/V related variables.
  $UserClamLogFileName = 'User_ClamScan_Virus_Scan_Report.txt';
  $UserClamLogFile = $ConvertDir.$UserClamLogFileName;
  $UserScanCoreFileName = 'User_ScanCore_Virus_Scan_Report.txt';
  $UserScanCoreLogFile = $ConvertDir.$UserScanCoreFileName;
  $ConsolidatedLogFileName = 'User_Consolidated_Virus_Scan_Report.txt';
  $ConsolidatedLogFile = $ConvertTempDir.$ConsolidatedLogFileName;
  // / Format related variables.
  $ArchiveArray = $DearchiveArray = $DocumentArray = $SpreadsheetArray = $PresentationInputArray = $PresentationOutputArray = $XPSInputArray = $XPSOutputArray = $ImageArray = $MediaInputArray = $MediaOutputArray = $VideoInputArray = $VideoOutputArray = $StreamArray = $DrawingArray = $ModelArray = $SubtitleArray = $PDFWorkArr = $StreamOutputArray = $SCADArray = $SCADOutputArray = $allArrays = array();
  if (in_array('Archive', $SupportedConversionTypes)) $ArchiveArray = $UserArchiveArray;
  if (in_array('Archive', $SupportedConversionTypes)) $DearchiveArray = $UserDearchiveArray;
  if (in_array('Document', $SupportedConversionTypes)) $DocumentArray = $UserDocumentArray;
  if (in_array('Document', $SupportedConversionTypes)) $SpreadsheetArray = $UserSpreadsheetArray;
  if (in_array('Document', $SupportedConversionTypes)) $XPSInputArray = $UserXPSInputArray;
  if (in_array('Document', $SupportedConversionTypes)) $XPSOutputArray = $UserXPSOutputArray;
  if (in_array('Document', $SupportedConversionTypes)) $PresentationInputArray = $UserPresentationInputArray;
  if (in_array('Document', $SupportedConversionTypes)) $PresentationOutputArray = $UserPresentationOutputArray;
  if (in_array('Image', $SupportedConversionTypes)) $ImageArray = $UserImageArray;
  if (in_array('Audio', $SupportedConversionTypes)) $MediaInputArray = $UserMediaInputArray;
  if (in_array('Audio', $SupportedConversionTypes)) $MediaOutputArray = $UserMediaOutputArray;
  if (in_array('Video', $SupportedConversionTypes)) $VideoInputArray = $UserVideoInputArray;
  if (in_array('Video', $SupportedConversionTypes)) $VideoOutputArray = $UserVideoOutputArray;
  if (in_array('Stream', $SupportedConversionTypes) && in_array('Audio', $SupportedConversionTypes) && in_array('Video', $SupportedConversionTypes)) $StreamArray = array_merge(array_merge($UserStreamArray, $UserMediaOutputArray), $UserVideoOutputArray);
  if (in_array('Stream', $SupportedConversionTypes) && in_array('Audio', $SupportedConversionTypes) && in_array('Video', $SupportedConversionTypes)) $StreamOutputArray = array_merge($UserMediaOutputArray, $UserVideoOutputArray);
  if (in_array('Drawing', $SupportedConversionTypes)) $DrawingArray = $UserDrawingArray;
  if (in_array('Model', $SupportedConversionTypes)) $ModelArray = $UserModelArray;
  if (in_array('Scad', $SupportedConversionTypes)) $SCADArray = $UserSCADArray;
  if (in_array('Scad', $SupportedConversionTypes)) $SCADOutputArray = array_diff($SCADArray, array('scad'));
  if (in_array('Subtitle', $SupportedConversionTypes)) $SubtitleInputArray = $UserSubtitleInputArray;
  if (in_array('Subtitle', $SupportedConversionTypes)) $SubtitleOutputArray = $UserSubtitleOutputArray;
  if (in_array('OCR', $SupportedConversionTypes) && in_array('Document', $SupportedConversionTypes)) $PDFWorkArr = $UserPDFWorkArr;
  $allArrays = [
    $ArchiveArray, $DearchiveArray, $DocumentArray, $SpreadsheetArray,
    $PresentationInputArray, $PresentationOutputArray, $ImageArray,
    $MediaInputArray, $MediaOutputArray, $VideoInputArray, $VideoOutputArray,
    $StreamArray, $DrawingArray, $ModelArray, $SubtitleInputArray, $PDFWorkArr,
    $XPSInputArray, $XPSOutputArray, $SCADArray];
  $Allowed = array_unique(array_merge(...$allArrays));
  $SupportedFormatCount = count($Allowed);
  // / Perform a version integrity check.
  if ($HRConvertVersion === $Version) $GlobalsAreVerified = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $convertDir0 = $convertTempDir0 = $subDir = $partURL = $allArrays = NULL;
  unset($convertDir0, $convertTempDir0, $subDir, $partURL, $allArrays);
  return array($GlobalsAreVerified, $CoreLoaded); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove the build & development environments when config.php asks for it.
// / This runs from the core after verifyGlobals() so every global it needs already exists.
// / It also runs after verifyLogs() so its failures can actually be written to the log.
// / These paths live under the application directory, not part of the regular cleanup routine.
function cleanBuildEnvironment() {
  // / Set variables.
  global $Verbose, $DeleteBuildEnvironment, $DeleteDevelopmentDocumentation, $DirSep;
  $BuildEnvCleaned = TRUE;
  $BuildEnvDeleted = $DevDocsDeleted = FALSE;
  $buildDirContents = array();
  $buildDirEntry = '';
  // / Define absolute paths for the files & folders that may need to be removed.
  $dockerFile = realpath(dirname(__FILE__).$DirSep.'Documentation'.$DirSep.'Build'.$DirSep.'Dockerfile');
  $changelogFile = realpath(dirname(__FILE__).$DirSep.'Documentation'.$DirSep.'CHANGELOG.txt');
  $readmeFile = realpath(dirname(__FILE__).$DirSep.'README.md');
  $buildDir = realpath(dirname(__DIR__).$DirSep.'Build');
  // / Delete the build environment if specified by config.php.
  if ($DeleteBuildEnvironment) {
    if ($Verbose) logEntry('Removing the build environment.');
    // / Remove the contents of the build directory one entry at a time.
    if ($buildDir !== FALSE && is_dir($buildDir)) {
      $buildDirContents = array_diff(scandir($buildDir), array('.', '..'));
      foreach ($buildDirContents as $buildDirEntry) {
        if (is_file($buildDir.$DirSep.$buildDirEntry)) @unlink($buildDir.$DirSep.$buildDirEntry); }
      @rmdir($buildDir); }
    if ($dockerFile !== FALSE && file_exists($dockerFile)) @unlink($dockerFile);
    // / Confirm the build environment is actually gone before reporting success.
    if (!file_exists($dockerFile) && !is_dir($buildDir)) $BuildEnvDeleted = TRUE;
    else {
      $BuildEnvCleaned = FALSE;
      errorEntry('Could not remove the build environment!', 26000, FALSE); }
    if ($Verbose && $BuildEnvDeleted) logEntry('Removed the build environment.'); }
  // / Delete the development documentation if specified by config.php.
  if ($DeleteDevelopmentDocumentation) {
    if ($Verbose) logEntry('Removing the development documentation.');
    if ($changelogFile !== FALSE && file_exists($changelogFile)) @unlink($changelogFile);
    if ($readmeFile !== FALSE && file_exists($readmeFile)) @unlink($readmeFile);
    // / Confirm the development documentation is actually gone before reporting success.
    if (!file_exists($changelogFile) && !file_exists($readmeFile)) $DevDocsDeleted = TRUE;
    else {
      $BuildEnvCleaned = FALSE;
      errorEntry('Could not remove the development documentation!', 26001, FALSE); }
    if ($Verbose && $DevDocsDeleted) logEntry('Removed the development documentation.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $dockerFile = $changelogFile = $readmeFile = $buildDir = $buildDirContents = $buildDirEntry = NULL;
  unset($dockerFile, $changelogFile, $readmeFile, $buildDir, $buildDirContents, $buildDirEntry);
  return array($BuildEnvCleaned, $BuildEnvDeleted, $DevDocsDeleted); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to sanitize & return the extension to a specified file.
function getExtension($pathToFile) {
  // / Set variables.
  global $PathExt;
  $Pathinfo = '';
  $pathinfoCleaned = FALSE;
  list ($pathinfo, $pathinfoCleaned) = sanitize(pathinfo(strtolower($pathToFile), $PathExt), TRUE);
  if ($pathinfoCleaned) $Pathinfo = trim($pathinfo);
  else errorEntry('Could not process extension for file '.$pathToFile.'!', 300, FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $pathToFile = $pathinfoCleaned = $pathinfo = NULL;
  unset($pathToFile, $pathinfoCleaned, $pathinfo);
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
// / A function to sanitize & verify an array of files.
function getFiles($pathToFiles) {
  // / Set variables.
  global $DangerousFiles, $DirSep;
  $Files = $dirtyFileArr = array();
  if (is_dir($pathToFiles)) $dirtyFileArr = @scandir($pathToFiles);
  // / Iterate through each detected file & make sure it's not dangerous before adding it to the output array.
  foreach ($dirtyFileArr as $dirtyFile) {
    $dirtyExt = getExtension($pathToFiles.$DirSep.$dirtyFile);
    // / Add the selected file to the array of clean files only if it is safe to handle.
    if (!in_array(strtolower($dirtyExt), $DangerousFiles) && !is_dir($pathToFiles.$DirSep.$dirtyFile)) array_push($Files, $dirtyFile);
    else if ($dirtyExt === '.' or $dirtyExt === '..') errorEntry('Could not display file '.$dirtyFile.'!', 400, FALSE); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $dirtyFile = $pathToFiles = $dirtyFileArr = $dirtyExt = NULL;
  unset($dirtyFile, $pathToFiles, $dirtyFileArr, $dirtyExt);
  return $Files; }
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
// / Returns FALSE when the path cannot be read.
function fileTime($filePath) {
  // / Set variables.
  $Stat = FALSE;
  if (file_exists($filePath)) $Stat = @filemtime($filePath);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $filePath = NULL;
  unset($filePath);
  return $Stat; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to test if a folder is empty.
// / Returns TRUE only when the folder exists & holds nothing at all.
// / Returns FALSE when the folder holds anything, or when the path is not a folder.
// / Every directory contains a . and a .. entry, so both are discarded before testing.
function is_dir_empty($dir) {
  // / Set variables.
  $Check = TRUE;
  $contents = array();
  // / Make sure the selected directory is actually a directory.
  if (is_dir($dir)) {
    // / Gather the contents of the directory, discarding the two entries every directory has.
    $contents = array_diff(scandir($dir), array('.', '..'));
    // / Anything left over means the directory holds something.
    if (!empty($contents)) $Check = FALSE; }
  // / A path that is not a directory at all must never be reported as an empty one.
  else $Check = FALSE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $dir = $contents = NULL;
  unset($dir, $contents);
  return $Check; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to determine whether a folder holds nothing but protected file objects.
// / A hosted session directory always contains an index.html file for document root protection.
// / This overlooks the required files and only looks to see if any user requested files remain.
function isDirEmptyOfUserFiles($path) {
  // / Set variables.
  global $DefaultApps;
  $DirIsEmptyOfUserFiles = FALSE;
  $remaining = array();
  if (is_dir($path)) {
    $remaining = array_diff(scandir($path), array('..', '.'));
    // / Discard every protected file object. Whatever is left belongs to a user.
    $remaining = array_diff($remaining, $DefaultApps);
    if (empty($remaining)) $DirIsEmptyOfUserFiles = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $remaining = $path = NULL;
  unset($remaining, $path);
  return $DirIsEmptyOfUserFiles; }
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
  if (stripos($clamLogFileDATA, 'Virus Detected') !== FALSE or strpos($clamLogFileDATA, 'FOUND') !== FALSE) {
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
  global $ConvertLoc, $RequiredDirs, $RequiredIndexes, $RequiredCleanupFolders, $Time, $LogFile, $Verbose, $PermissionLevels, $DirSep, $InstLoc;
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
  foreach ($RequiredIndexes as $requiredIndex) @copy($InstLoc.$DirSep.'index.html', $requiredIndex.$DirSep.'index.html');
  foreach ($RequiredCleanupFolders as $requiredCleanupFolder) if (file_exists($requiredCleanupFolder)) cleanFiles($requiredCleanupFolder);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $requiredDir = $requiredIndex = $requiredCleanupFolder = NULL;
  unset($requiredDir, $requiredIndex, $requiredCleanupFolder); 
  return array($RequiredDirsExist, $RequiredDirs); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove a session directory once nothing of the user's remains in it.
// / Protected file objects such as the enforced index.html are removed only at this point.
function removeEmptiedSessionDir($sessionPath) {
  // / Set variables.
  global $DefaultApps, $DirSep;
  $SessionDirRemoved = FALSE;
  $leftovers = array();
  $leftover = '';
  if (isDirEmptyOfUserFiles($sessionPath)) {
    $leftovers = array_diff(scandir($sessionPath), array('..', '.'));
    // / Only protected file objects can be left at this point. Remove them with the directory.
    foreach ($leftovers as $leftover) {
      if (is_file($sessionPath.$DirSep.$leftover)) @unlink($sessionPath.$DirSep.$leftover); }
    @rmdir($sessionPath);
    if (!is_dir($sessionPath)) $SessionDirRemoved = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $leftovers = $leftover = $sessionPath = NULL;
  unset($leftovers, $leftover, $sessionPath);
  return $SessionDirRemoved; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to clean a selection of files.
// / Recursively deletes files.
// / This function is extremely dangerous! Please handle with care.
// / This function refuses to operate on anything outside $ConvertLoc or $ConvertTemp. Both sides of the comparison are passed
// / through realpath() first, so a path containing .. cannot walk out of an approved root
// / while still matching it as a string prefix. If a future edit ever hands this function
// / the wrong variable, the result is a no-op & a FALSE return, not an incident.
function cleanFiles($path) {
  // / Set variables.
  global $ConvertLoc, $ConvertTemp, $DefaultApps, $DirSep, $RequiredCleanupFolders;
  $variableIsSanitized = $CleanSuccess = $pathCheck = $pathIsContained = FALSE;
  $loopCheck = TRUE;
  $dirContents = $allowedRoots = array();
  $dirEntry = $childPath = $realPath = $realRoot = $allowedRoot = '';
  list ($path, $variableIsSanitized) = sanitize($path, FALSE);
  // / Assemble every location this function is permitted to operate inside.
  // / $RequiredCleanupFolders holds the maintenance locations the core needs cleaned.
  $allowedRoots = array($ConvertLoc, $ConvertTemp);
  if (is_array($RequiredCleanupFolders)) $allowedRoots = array_merge($allowedRoots, $RequiredCleanupFolders);
  // / Resolve the supplied path to its true location before any comparison is made.
  // / realpath() returns FALSE for anything that does not exist, which fails the check below.
  $realPath = realpath($path);
  // / Confirm the resolved path sits inside an approved root & is not the root itself.
  // / The trailing separator on the root is required. Without it a sibling directory named
  // / like "ConvertLocEvil" would match "ConvertLoc" as a prefix & be accepted.
  if ($realPath !== FALSE) {
    foreach ($allowedRoots as $allowedRoot) {
      if (empty($allowedRoot)) continue;
      $realRoot = realpath($allowedRoot);
      if ($realRoot !== FALSE && strpos($realPath, $realRoot.$DirSep) === 0) {
        $pathIsContained = TRUE;
        break; } } }
  // / Make sure the selected directory is contained, sanitized, & actually a directory.
  if ($pathIsContained && $variableIsSanitized && is_dir($path)) {
    // / Iterate through each file object in the directory.
    $dirContents = array_diff(scandir($path), array('..', '.'));
    foreach ($dirContents as $dirEntry) {
      // / Build the full path to this child ONCE. Every check below refers to the CHILD,
      // / never to the parent. Testing the parent here would choose the wrong branch.
      $childPath = $path.$DirSep.$dirEntry;
      // / Protected file objects are never touched at any depth.
      if (in_array(basename($childPath), $DefaultApps)) continue;
      // / If the selected file object is a file, delete it.
      if (is_file($childPath)) @unlink($childPath);
      // / If the selected file object is an empty directory, remove it outright.
      elseif (is_dir($childPath) && is_dir_empty($childPath)) @rmdir($childPath);
      // / If the selected file object is a directory with contents, recurse into it.
      // / A failure anywhere below must propagate up, so $loopCheck is never reset to TRUE here.
      elseif (is_dir($childPath)) {
        if (!cleanFiles($childPath)) $loopCheck = FALSE; } }
    // / Once all file objects in the selected directory have been deleted, attempt to delete the selected directory.
    // / The containment check above already prevents reaching any approved root, but this
    // / explicit comparison is retained so the intent survives any future change to that check.
    if (!in_array($path, $allowedRoots, TRUE)) @rmdir($path); }
  // / Check if the function was successful. Note that approved root locations are never deleted.
  $pathCheck = is_dir($path);
  if ($pathCheck && is_dir_empty($path)) $CleanSuccess = TRUE;
  if (!$pathCheck) $CleanSuccess = TRUE;
  // / A failure in any recursive call invalidates the whole operation regardless of what is left here.
  if (!$loopCheck) $CleanSuccess = FALSE;
  // / An uncontained path is never a success. Nothing was cleaned & nothing should report otherwise.
  if (!$pathIsContained) $CleanSuccess = FALSE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $path = $dirContents = $dirEntry = $childPath = $realPath = $realRoot = $allowedRoot = $allowedRoots = $variableIsSanitized = $pathCheck = $pathIsContained = $loopCheck = NULL;
  unset($path, $dirContents, $dirEntry, $childPath, $realPath, $realRoot, $allowedRoot, $allowedRoots, $variableIsSanitized, $pathCheck, $pathIsContained, $loopCheck);
  return $CleanSuccess; }
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to clean up old files in the $TempLoc.
// / The directory structure is two levels: [server-daily]/[individual-session].
// / Sessions are swept individually. 
// / A daily parent's own mtime only changes when a NEW session is created inside it.
// / So the parent cannot be trusted to reflect whether the sessions it holds are still in use.
// / A quiet hour would otherwise delete active sessions.
function cleanTempLoc() {
  // / Set variables.
  global $ConvertTemp, $DeleteThreshold, $DefaultApps, $DirSep, $PermissionLevels;
  $TempLocDeepCleaned = FALSE;
  $CleanedTempLoc = $loopCheck = TRUE;
  $dailyDirs = $sessionDirs = array();
  $dailyDir = $sessionDir = $dailyPath = $sessionPath = '';
  $now = time();
  // / Make sure the directory to be scanned exists.
  if (file_exists($ConvertTemp)) {
    $dailyDirs = array_diff(scandir($ConvertTemp), array('..', '.'));
    // / Iterate through each daily folder in the directory.
    foreach ($dailyDirs as $dailyDir) {
      // / Validate the folder.
      if (in_array($dailyDir, $DefaultApps)) continue;
      $dailyPath = $ConvertTemp.$DirSep.$dailyDir;
      // / The hosted location enforces an index.html in every folder as a document root
      // / misconfiguration protection mechanism. It is never a session & is never swept.
      if ($dailyPath === $ConvertTemp.$DirSep.'index.html') continue;
      // / Only directories hold sessions. Files at this level are left alone entirely.
      if (!is_dir($dailyPath)) continue;
      $sessionDirs = array_diff(scandir($dailyPath), array('..', '.'));
      // / Iterate through each session folder inside this day.
      foreach ($sessionDirs as $sessionDir) {
        if (in_array($sessionDir, $DefaultApps)) continue;
        $sessionPath = $dailyPath.$DirSep.$sessionDir;
        if (!is_dir($sessionPath)) continue;
        // / See if this individual session is due for deletion.
        if ($now - fileTime($sessionPath) > ($DeleteThreshold * 60)) {
          $TempLocDeepCleaned = TRUE;
          @chmod ($sessionPath, $PermissionLevels);
          $loopCheck = cleanFiles($sessionPath);
          // / Remove the session shell, including any protected file objects still in it.
          removeEmptiedSessionDir($sessionPath); }
        // / Check if the most recent iteration of the loop was successful.
        if (!$loopCheck) { $CleanedTempLoc = FALSE; }
        $loopCheck = TRUE; }
      // / Remove the daily parent only once every session inside it is gone.
      if (isDirEmptyOfUserFiles($dailyPath)) removeEmptiedSessionDir($dailyPath); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $dailyDirs = $dailyDir = $dailyPath = $sessionDirs = $sessionDir = $sessionPath = $now = $loopCheck = NULL;
  unset($dailyDirs, $dailyDir, $dailyPath, $sessionDirs, $sessionDir, $sessionPath, $now, $loopCheck);
  return array($CleanedTempLoc, $TempLocDeepCleaned); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to clean up old files in the $ConvertLoc.
// / The directory structure is two levels: [server-daily]/[individual-session].
// / Sessions are swept individually. 
// / A daily parent's own mtime only changes when a NEW session is created inside it.
// / So the parent cannot be trusted to reflect whether the sessions it holds are still in use.
// / A quiet hour would otherwise delete active sessions.
// / NOTE: secret.php lives at the root of this directory & must NEVER be removed.
// / It is protected here by the is_dir() check, since it is a file & not a directory.
function cleanConvertLoc() {
  // / Set variables.
  global $ConvertLoc, $DeleteThreshold, $DefaultApps, $DirSep, $PermissionLevels;
  $ConvertLocDeepCleaned = FALSE;
  $CleanedConvertLoc = $loopCheck = TRUE;
  $dailyDirs = $sessionDirs = array();
  $dailyDir = $sessionDir = $dailyPath = $sessionPath = '';
  $now = time();
  // / Make sure the directory to be scanned exists.
  if (file_exists($ConvertLoc)) {
    $dailyDirs = array_diff(scandir($ConvertLoc), array('..', '.'));
    // / Iterate through each daily folder in the directory.
    foreach ($dailyDirs as $dailyDir) {
      // / Validate the folder.
      if (in_array($dailyDir, $DefaultApps)) continue;
      $dailyPath = $ConvertLoc.$DirSep.$dailyDir;
      // / Only directories hold sessions. Files at this level, including secret.php, are never touched.
      if (!is_dir($dailyPath)) continue;
      $sessionDirs = array_diff(scandir($dailyPath), array('..', '.'));
      // / Iterate through each session folder inside this day.
      foreach ($sessionDirs as $sessionDir) {
        if (in_array($sessionDir, $DefaultApps)) continue;
        $sessionPath = $dailyPath.$DirSep.$sessionDir;
        if (!is_dir($sessionPath)) continue;
        // / See if this individual session is due for deletion.
        if ($now - fileTime($sessionPath) > ($DeleteThreshold * 60)) {
          $ConvertLocDeepCleaned = TRUE;
          @chmod ($sessionPath, $PermissionLevels);
          $loopCheck = cleanFiles($sessionPath);
          // / Remove the session shell, including any protected file objects still in it.
          removeEmptiedSessionDir($sessionPath); }
        // / Check if the most recent iteration of the loop was successful.
        if (!$loopCheck) { $CleanedConvertLoc = FALSE; }
        $loopCheck = TRUE; }
      // / Remove the daily parent only once every session inside it is gone.
      if (isDirEmptyOfUserFiles($dailyPath)) removeEmptiedSessionDir($dailyPath); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $dailyDirs = $dailyDir = $dailyPath = $sessionDirs = $sessionDir = $sessionPath = $now = $loopCheck = NULL;
  unset($dailyDirs, $dailyDir, $dailyPath, $sessionDirs, $sessionDir, $sessionPath, $now, $loopCheck);
  return array($CleanedConvertLoc, $ConvertLocDeepCleaned); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify that the Document Conversion Engine is installed & running.
function verifyDocumentConversionEngine() {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $ApacheUser, $DocumentEngineSleepTimer, $PathToUnoconv, $HomeLoc;
  $DocEnginePID = 0;
  $docEnginePIDCheck = $docEngineUserCheck = $DocumentEngineStarted = $installCheck = $okToStart = FALSE;
  $returnData = $docEngineUser = '';
  // / Determine if the Document Conversion Engine (Unoconv) is installed.
  if (!file_exists($PathToUnoconv)) errorEntry('Could not verify the Document Conversion Engine installation at '.$PathToUnoconv.'!', 2000, TRUE);
  else if ($Verbose) {
    $installCheck = TRUE;
    logEntry('Verified the Document Conversion Engine installation.'); }
  // / If Unoconv is installed, check that the Unoconv listener (soffice.bin) is running.
  if ($installCheck) {
    // / Try to determine the PID for soffice.bin using pgrep.
    $DocEnginePID = str_replace($Lol, '', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim(shell_exec('pgrep soffice.bin')))));
    if ($Verbose) logEntry('The Document Conversion Engine PID is: '.str_replace($Lol, '', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($DocEnginePID)))));
    // / Parse the results of the pgrep call.
    if ($DocEnginePID === 0 or $DocEnginePID === '' or $DocEnginePID === NULL or !$DocEnginePID) $docEnginePIDCheck = FALSE;
    if ($DocEnginePID !== 0 && $DocEnginePID !== '' && $DocEnginePID !== NULL) $docEnginePIDCheck = TRUE;
    // / Try to determine who owns the Unoconv Listener (soffice.bin) process using ps.
    // / We need whoever owns the process to have read & write access to HRConvert2 data locations.
    // / If the included rc.local script is used, this process should run at system startup as the root user.
    // / For more information, please see the included INSTALLATION_INSTRUCTIONS.txt file.
    $docEngineUser = str_replace($Lol, '', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim(shell_exec('ps -o user= -p'.$DocEnginePID)))));
    if ($Verbose) logEntry('The Document Conversion Engine owner is: '.str_replace($Lol, '', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($docEngineUser)))));
    if ($docEngineUser === $ApacheUser or $docEngineUser === 'root') $docEngineUserCheck = TRUE;
    // / Parse the results of the ps call.
    // / We should only try to start the Document Conversion Engine only under certain circumstances.
    // / When the $docEnginePIDCheck is FALSE. This indicates that Unoconv Listener (soffice.bin) is not running.
    // / When the $docEnginePIDCheck is TRUE but the $docEngineUSerCheck is FALSE. This indicates that Unoconv Listener is running as the incorrect user.
    if ($docEnginePIDCheck === FALSE) $okToStart = TRUE;
    if ($docEnginePIDCheck === TRUE) if ($docEngineUserCheck === FALSE) $okToStart = TRUE;
    // / Only start the Document Conversion Engine if it is not running, or running as the incorrect user.
    if ($okToStart) { 
      // / Try to start the Document Conversion Engine.
      if ($Verbose) logEntry('Starting the Document Conversion Engine.');
      $returnData = exec('python3 '.$PathToUnoconv.' -l --verbose --user-profile='.$HomeLoc.' > /dev/null 2>&1 &');
      sleep($DocumentEngineSleepTimer);
      if ($Verbose && trim($returnData) !== '') logEntry('The Document Conversion Engine returned the following: '.str_replace($Lol, '', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData))))); } }
  // / Try to determine the PID for soffice.bin using pgrep.
  $DocEnginePID = str_replace($Lol, '', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim(shell_exec('pgrep soffice.bin')))));
  if ($Verbose) logEntry('The Document Conversion Engine PID is: '.str_replace($Lol, '', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($DocEnginePID)))));
  // / Write the status of the Document Conversion Engine to the log file.
  if ($DocEnginePID !== 0 && $DocEnginePID !== '' && $DocEnginePID !== NULL && $installCheck) {
    $DocumentEngineStarted = TRUE;
    if ($Verbose) logEntry('The Document Conversion Engine is running.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $returnData = $docEnginePIDCheck = $docEngineUserCheck = $docEngineUser = $installCheck = $okToStart = NULL;
  unset($returnData, $docEnginePIDCheck, $docEngineUserCheck, $docEngineUser, $installCheck, $okToStart);
  return array($DocumentEngineStarted, $DocEnginePID); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert document formats.
function convertDocuments($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer, $XPSInputArray, $PathToUnoconv, $HomeLoc;
  $ConversionSuccess = $ConversionErrors = FALSE;
  $returnData = '';
  $stopper = 0;
  $sleepTime = $SleepTimer;
  $arrayxpsi = array('xps', 'oxps');
  $oldExtension =  getExtension($pathname);
  // / The following code verifies that the Document Conversion Engine is installed & running.
  list ($documentEngineStarted, $documentEnginePID) = verifyDocumentConversionEngine();
  if (!$documentEngineStarted) {
    $ConversionErrors = TRUE;
    errorEntry('Could not verify the Document Conversion Engine!', 7000, FALSE); }
  else if ($Verbose) logEntry('Verified the Document Conversion Engine.');
  // / The following code performs the actual document conversion.
  if ($documentEngineStarted) {
    if ($Verbose) logEntry('Converting document.');
    // / This code will attempt the conversion up to $StopCounter number of times.
    while (!file_exists($newPathname) && $stopper <= $StopCounter) {
      // / If the last conversion attempt failed, wait a moment before trying again.
      if ($stopper !== 0) sleep($sleepTime++);
      if (in_array(strtolower($oldExtension), $arrayxpsi)) $returnData = shell_exec('xpstopdf '.$pathname.' '.$newPathname);
      // / Attempt the conversion using Unoconv for all other files.
      if (!in_array(strtolower($oldExtension), $arrayxpsi)) $returnData = shell_exec('python3 '.$PathToUnoconv.' --verbose --user-profile='.$HomeLoc.' -o '.$newPathname.' -f '.$extension.' '.$pathname);
      // / Count the number of conversions to avoid infinite loops.
      $stopper++;
      // / Stop attempting the conversion after $StopCounter number of attempts.
      if ($stopper === $StopCounter) {
        $ConversionErrors = TRUE;
        errorEntry('The document converter timed out!', 7001, FALSE); } }
    // / Log the output of the operation to the logfile, if it is not blank.
    if ($Verbose && trim($returnData) !== '') logEntry('Unoconv returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData))))); }
  if (file_exists($newPathname)) $ConversionSuccess = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $stopper = $pathname = $newPathname = $extension = $returnData = $documentEngineStarted = $documentEnginePID = $sleepTime = $oldExtension = $arrayxpsi = NULL;
  unset($stopper, $pathname, $newPathname, $extension, $returnData, $documentEngineStarted, $documentEnginePID, $sleepTime, $oldExtension, $arrayxpsi);
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
  // / Log the output of the operation to the logfile, if it is not blank.
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
    $returnData = shell_exec('xvfb-run -a /usr/bin/meshlabserver -i '.$pathname.' -o '.$newPathname);
    // / Count the number of conversions to avoid infinite loops.
    $stopper++;
    // / Stop attempting the conversion after $StopCounter number of attempts.
    if ($stopper === $StopCounter) {
      $ConversionErrors = TRUE;
      errorEntry('The model converter timed out!', 9000, FALSE); } }
  // / Log the output of the operation to the logfile, if it is not blank.
  if ($Verbose && trim($returnData) !== '') logEntry('Meshlab returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
  if (file_exists($newPathname)) $ConversionSuccess = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $returnData = $stopper = $pathname = $newPathname = NULL;
  unset($returnData, $stopper, $pathname, $newPathname);
  return array($ConversionSuccess, $ConversionErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to resolve a single OpenSCAD file reference against the users uploaded files.
// / OpenSCAD references frequently carry a directory structure that does not exist here.
// / A reference like <../lib/threads.scad> is matched on its basename alone, threads.scad.
// / Only files the user actually uploaded to this session are eligible for a match.
// / The path returned points at the SANITIZED copy in ScadTemp, never at the users original.
// / OpenSCAD resolves a relative include against the directory of the file it is reading.
// / The sanitized copy lives in ScadTemp, so every reference it holds must point there too.
// / Returns an empty string when nothing matched, & the caller must comment the reference out.
function resolveSCADInclude($scadReference, $sessionFiles) {
  // / Set variables.
  global $ScadTemp, $DirSep;
  $ResolvedFile = $sessionFile = '';
  $referenceIsUsable = FALSE;
  $referenceBase = strtolower(trim(basename(str_replace('\\', '/', trim($scadReference)))));
  // / A reference with no usable filename can never resolve to anything.
  // / Only .scad sources may ever be resolved as an include or a use.
  if ($referenceBase !== '' && $referenceBase !== '.' && $referenceBase !== '..' && getExtension($referenceBase) === 'scad') $referenceIsUsable = TRUE;
  if ($referenceIsUsable) {
    foreach ($sessionFiles as $sessionFile) {
      if (strtolower(basename($sessionFile)) === $referenceBase) {
        $ResolvedFile = $ScadTemp.$DirSep.basename($sessionFile);
        break; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $referenceBase = $sessionFile = $referenceIsUsable = NULL;
  unset($referenceBase, $sessionFile, $referenceIsUsable);
  return $ResolvedFile; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to neutralize every file reading primitive in a block of OpenSCAD source.
// / OpenSCAD reads arbitrary files by design & offers no sandbox of any kind.
// / A hostile .scad can embed the contents of any file the web server user can read.
// / This function is the only thing standing between an uploaded .scad & the filesystem.
// / Every primitive below is neutralized, not just the two most commonly known ones.
// / include <file> & use <file> read OpenSCAD source directly.
// / import("file") reads STL, OFF, DXF, SVG, 3MF & AMF geometry.
// / surface("file") reads a heightmap from a DAT or a PNG.
// / import_stl(), import_dxf() & import_off() are deprecated aliases that still function.
// / dxf_linear_extrude(), dxf_rotate_extrude(), dxf_dim() & dxf_cross() take a file= parameter.
// / When $resolveIncludes is FALSE every reference is commented out unconditionally.
// / When $resolveIncludes is TRUE an include or use may be rewritten to a sanitized copy.
// / A reference that resolves to nothing is commented out either way.
// / Only include & use are ever rewritten. Geometry & heightmap reads are always removed.
// / This function performs no disk access at all. Source in, source out.
function sanitizeSCAD($scadContents, $sessionFiles, $resolveIncludes) {
  // / Set variables.
  $SanitizedSCAD = '';
  $ReferencesFound = $ReferencesResolved = $ReferencesRemoved = 0;
  $scadLines = $lineMatches = array();
  $scadLine = $trimmedLine = $reference = $resolvedPath = $marker = '';
  $lineWasHandled = FALSE;
  // / Matches include <file> & use <file>. These are the only two forms ever rewritten.
  $includePattern = '/\b(include|use)\s*<([^>]*)>/i';
  // / Matches every remaining primitive that reads a file. None of these are ever rewritten.
  // / The file= forms are listed explicitly because a pattern written for the modern
  // / import("file") syntax will walk straight past dxf_dim(file="secret").
  $importPattern = '/\b(import|surface|import_stl|import_dxf|import_off)\s*\(/i';
  $dxfFilePattern = '/\b(dxf_linear_extrude|dxf_rotate_extrude|dxf_dim|dxf_cross)\s*\(/i';
  // / A file= parameter anywhere at all is treated as a read attempt.
  $filePropertyPattern = '/\bfile\s*=/i';
  // / Split on any line ending so a file authored on any platform is handled the same way.
  $scadLines = preg_split('/\R/', $scadContents);
  foreach ($scadLines as $scadLine) {
    $lineWasHandled = FALSE;
    $trimmedLine = trim($scadLine);
    // / A line that is already a comment cannot read anything, so pass it through untouched.
    if (strncmp($trimmedLine, '//', 2) === 0) {
      $SanitizedSCAD .= $scadLine.PHP_EOL;
      continue; }
    // / Handle include & use. These are the only references eligible for resolution.
    if (preg_match($includePattern, $scadLine, $lineMatches)) {
      $ReferencesFound++;
      $reference = $lineMatches[2];
      $resolvedPath = '';
      // / Only attempt resolution when config.php has explicitly enabled it.
      if ($resolveIncludes) $resolvedPath = resolveSCADInclude($reference, $sessionFiles);
      if ($resolvedPath !== '') {
        // / Rewrite the reference to point at the sanitized copy we matched it to.
        $ReferencesResolved++;
        $SanitizedSCAD .= str_replace('<'.$reference.'>', '<'.$resolvedPath.'>', $scadLine).PHP_EOL; }
      else {
        // / Nothing matched, or resolution is disabled. Neutralize the line.
        $ReferencesRemoved++;
        $SanitizedSCAD .= '// HRC2-REMOVED-UNRESOLVED: '.$scadLine.PHP_EOL; }
      $lineWasHandled = TRUE; }
    // / Handle every remaining file reading primitive. None of these are ever resolved.
    // / Geometry & heightmap reads have no legitimate use in a single file conversion here.
    if (!$lineWasHandled) {
      $marker = '';
      if (preg_match($importPattern, $scadLine)) $marker = '// HRC2-REMOVED-IMPORT: ';
      elseif (preg_match($dxfFilePattern, $scadLine)) $marker = '// HRC2-REMOVED-DXF: ';
      elseif (preg_match($filePropertyPattern, $scadLine)) $marker = '// HRC2-REMOVED-FILEPARAM: ';
      if ($marker !== '') {
        $ReferencesFound++;
        $ReferencesRemoved++;
        $SanitizedSCAD .= $marker.$scadLine.PHP_EOL;
        $lineWasHandled = TRUE; } }
    // / Nothing on this line reads a file, so it passes through unchanged.
    if (!$lineWasHandled) $SanitizedSCAD .= $scadLine.PHP_EOL; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $scadContents = $sessionFiles = $resolveIncludes = $scadLines = $scadLine = $trimmedLine = NULL;
  $lineMatches = $reference = $resolvedPath = $marker = $lineWasHandled = NULL;
  $includePattern = $importPattern = $dxfFilePattern = $filePropertyPattern = NULL;
  unset($scadContents, $sessionFiles, $resolveIncludes, $scadLines, $scadLine, $trimmedLine);
  unset($lineMatches, $reference, $resolvedPath, $marker, $lineWasHandled);
  unset($includePattern, $importPattern, $dxfFilePattern, $filePropertyPattern);
  return array($SanitizedSCAD, $ReferencesFound, $ReferencesResolved, $ReferencesRemoved); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to sanitize EVERY OpenSCAD source the user uploaded, in one flat pass.
// / Nothing here follows a reference from one file to another.
// / Following references would require cycle detection, because a.scad may include b.scad
// / while b.scad includes a.scad, & both are legitimate files the user uploaded.
// / Sanitizing the whole upload set instead means every file OpenSCAD can possibly reach
// / has already been through the filter, & no traversal is ever performed.
// / The set is closed & bounded by whatever the user was willing to upload, so no depth
// / or width budget is required the way the stream inspector needs one.
// / Every sanitized copy is written to ScadTemp & the users originals are never modified.
function sanitizeAllSCADUploads() {
  // / Set variables.
  global $Verbose, $ConvertDir, $ScadTemp, $DirSep, $AllowSCADIncludeResolution;
  $AllSanitized = TRUE;
  $FilesSanitized = $ReferencesFound = $ReferencesResolved = $ReferencesRemoved = 0;
  $fileFound = $fileResolved = $fileRemoved = $bytesWritten = 0;
  $sessionFiles = array();
  $sessionFile = $scadContents = $sanitizedSCAD = $sanitizedPath = '';
  // / Gather every file the user uploaded to this session.
  $sessionFiles = getFiles($ConvertDir);
  foreach ($sessionFiles as $sessionFile) {
    // / Only OpenSCAD sources are sanitized. Everything else is left alone entirely.
    if (getExtension($sessionFile) !== 'scad') continue;
    $scadContents = @file_get_contents($ConvertDir.$sessionFile);
    if ($scadContents === FALSE) {
      $AllSanitized = FALSE;
      errorEntry('Could not read the OpenSCAD source file '.$sessionFile.'!', 27006, FALSE);
      continue; }
    // / Neutralize every file reading primitive in this source.
    list ($sanitizedSCAD, $fileFound, $fileResolved, $fileRemoved) = sanitizeSCAD($scadContents, $sessionFiles, $AllowSCADIncludeResolution);
    $ReferencesFound = $ReferencesFound + $fileFound;
    $ReferencesResolved = $ReferencesResolved + $fileResolved;
    $ReferencesRemoved = $ReferencesRemoved + $fileRemoved;
    // / Write the sanitized copy under the same basename so a resolved reference finds it.
    $sanitizedPath = $ScadTemp.$DirSep.basename($sessionFile);
    $bytesWritten = file_put_contents($sanitizedPath, $sanitizedSCAD, LOCK_EX);
    if ($bytesWritten === strlen($sanitizedSCAD)) $FilesSanitized++;
    else {
      $AllSanitized = FALSE;
      errorEntry('Could not stage the sanitized OpenSCAD source '.$sessionFile.'!', 27001, FALSE); } }
  if ($Verbose) logEntry('OpenSCAD Sanitization Result: Files Sanitized: '.$FilesSanitized.', References Found: '.$ReferencesFound.', Resolved: '.$ReferencesResolved.', Removed: '.$ReferencesRemoved.', Resolution Enabled: '.($AllowSCADIncludeResolution ? 'TRUE' : 'FALSE').'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $sessionFiles = $sessionFile = $scadContents = $sanitizedSCAD = $sanitizedPath = NULL;
  $fileFound = $fileResolved = $fileRemoved = $bytesWritten = NULL;
  unset($sessionFiles, $sessionFile, $scadContents, $sanitizedSCAD, $sanitizedPath);
  unset($fileFound, $fileResolved, $fileRemoved, $bytesWritten);
  return array($AllSanitized, $FilesSanitized, $ReferencesFound, $ReferencesResolved, $ReferencesRemoved); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to confirm the installed OpenSCAD meets the minimum version HRConvert2 requires.
// / HRConvert2 does not probe for capabilities & does not accommodate older builds.
// / A pinned minimum version means the export formats in config.php can be trusted as written.
// / OpenSCAD reports its version as "OpenSCAD version YYYY.MM" on standard error, not standard output.
function verifySCADVersion() {
  // / Set variables.
  global $Verbose, $MinimumSCADVersion;
  $SCADVersionIsValid = FALSE;
  $versionOutput = $versionMatches = array();
  $versionExitCode = 1;
  $detectedVersion = '';
  // / OpenSCAD writes its version banner to standard error, so it must be redirected to be captured.
  exec('openscad --version 2>&1', $versionOutput, $versionExitCode);
  if ($versionExitCode === 0 && !empty($versionOutput)) {
    // / Match the YYYY.MM release stamp anywhere in the banner.
    if (preg_match('/(\d{4})\.(\d{2})/', implode(' ', $versionOutput), $versionMatches)) {
      $detectedVersion = $versionMatches[1].'.'.$versionMatches[2];
      // / A plain string comparison is correct here because the format is fixed width & zero padded.
      if ($detectedVersion >= $MinimumSCADVersion) $SCADVersionIsValid = TRUE; } }
  if ($Verbose) logEntry('OpenSCAD Version Check: '.($SCADVersionIsValid ? 'PASSED' : 'FAILED').', Detected: '.($detectedVersion === '' ? 'NONE' : $detectedVersion).', Required: '.$MinimumSCADVersion.'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $versionOutput = $versionMatches = $versionExitCode = $detectedVersion = NULL;
  unset($versionOutput, $versionMatches, $versionExitCode, $detectedVersion);
  return $SCADVersionIsValid; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert OpenSCAD source files into a supported export format.
// / The users uploaded .scad is never modified & never replaced.
// / Every uploaded source is sanitized into ScadTemp before OpenSCAD is allowed to run.
// / The whole upload set is sanitized rather than just the requested file, because a
// / resolved include would otherwise hand OpenSCAD a source that was never filtered.
// / Sanitized copies are never retained. If they are needed again they are regenerated.
// / OpenSCAD has no execution bound of its own, so the render is killed by timeout.
// / The render is niced so a runaway model yields to everything else on the server.
// / OpenSCAD error output is deliberately NOT written to the log.
// / A failed parse quotes the offending line, which would turn the log into an exfiltration channel.
// / The installed OpenSCAD version is verified before any conversion is attempted.
// / HRConvert2 does not accommodate builds older than the pinned minimum.
function convertSCAD($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $DirSep, $SCADConversionTimeout, $ScadTemp;
  // / The version check result is cached for the life of the request.
  // / A bulk conversion of ten files would otherwise run ten identical version checks.
  static $SCADVersionChecked = FALSE;
  static $SCADVersionIsValid = FALSE;
  $ConversionSuccess = $ConversionErrors = $AllSanitized = $readyToRender = FALSE;
  $FilesSanitized = $ReferencesFound = $ReferencesResolved = $ReferencesRemoved = $openscadExitCode = 0;
  $sanitizedPath = $openscadCommand = '';
  $openscadOutput = array();
  // / Confirm the installed OpenSCAD is new enough before anything else happens.
  if (!$SCADVersionChecked) {
    $SCADVersionIsValid = verifySCADVersion();
    $SCADVersionChecked = TRUE; }
  if (!$SCADVersionIsValid) {
    $ConversionErrors = TRUE;
    errorEntry('The installed OpenSCAD version is missing or too old for HRConvert2!', 27005, FALSE); }
  else {
    // / Sanitize every uploaded source, not just this one.
    // / A resolved include points at a sanitized copy, so every copy must already exist.
    list ($AllSanitized, $FilesSanitized, $ReferencesFound, $ReferencesResolved, $ReferencesRemoved) = sanitizeAllSCADUploads();
    // / The sanitized copy of the requested file carries the same basename as the original.
    $sanitizedPath = $ScadTemp.$DirSep.basename($pathname);
    if ($AllSanitized && file_exists($sanitizedPath)) $readyToRender = TRUE;
    else {
      $ConversionErrors = TRUE;
      errorEntry('Could not prepare the OpenSCAD sources for rendering!', 27000, FALSE); } }
  // / Render only from the sanitized copy. The users original is never handed to OpenSCAD.
  if ($readyToRender) {
    if ($Verbose) logEntry('Converting OpenSCAD model to '.$extension.'.');
    // / nice yields the render to everything else on the server.
    // / A legitimate render competes with nothing, so it loses nothing by yielding.
    // / A runaway render can no longer starve the rest of the server while it burns down its timeout.
    // / timeout enforces a wall clock limit because OpenSCAD will not stop on its own.
    // / Standard output & standard error are both discarded rather than captured.
    $openscadCommand = 'nice -n 19 timeout '.(int)$SCADConversionTimeout
      .' openscad -o '.escapeshellarg($newPathname)
      .' '.escapeshellarg($sanitizedPath)
      .' > /dev/null 2>&1';
    exec($openscadCommand, $openscadOutput, $openscadExitCode);
    // / An exit code of 124 is the timeout command reporting that it killed the render.
    if ($openscadExitCode === 124) {
      $ConversionErrors = TRUE;
      errorEntry('The OpenSCAD converter timed out after '.(int)$SCADConversionTimeout.' seconds!', 27002, FALSE); }
    else if ($openscadExitCode !== 0) {
      $ConversionErrors = TRUE;
      errorEntry('The OpenSCAD converter failed with exit code '.$openscadExitCode.'!', 27003, FALSE); }
    // / Remove every sanitized copy immediately. None of them are retained for any reason.
    // / ScadTemp holds nothing else, so the whole directory is cleared in one operation.
    cleanFiles($ScadTemp);
    if (!is_dir_empty($ScadTemp)) errorEntry('Could not remove the sanitized OpenSCAD sources!', 27004, FALSE); }
  if (file_exists($newPathname)) $ConversionSuccess = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $sanitizedPath = $openscadCommand = $openscadOutput = $openscadExitCode = $readyToRender = NULL;
  $AllSanitized = $FilesSanitized = $ReferencesFound = $ReferencesResolved = $ReferencesRemoved = NULL;
  $pathname = $newPathname = $extension = NULL;
  unset($sanitizedPath, $openscadCommand, $openscadOutput, $openscadExitCode, $readyToRender);
  unset($AllSanitized, $FilesSanitized, $ReferencesFound, $ReferencesResolved, $ReferencesRemoved);
  unset($pathname, $newPathname, $extension);
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
  // / Log the output of the operation to the logfile, if it is not blank.
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
  // / Log the output of the operation to the logfile, if it is not blank.
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
  // / Log the output of the operation to the logfile, if it is not blank.
  if ($Verbose && trim($returnData) !== '') logEntry('Ffmpeg returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
  if (file_exists($newPathname)) $ConversionSuccess = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $returnData = $stopper = $pathname = $newPathname = $sleepTime = NULL;
  unset($returnData, $stopper, $pathname, $newPathname, $sleepTime);
  return array($ConversionSuccess, $ConversionErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to simply return whether or not an IP is private or public.
// / Used for server-side request forgery (SSRF) protection.
// / Returns TRUE only for publicly routable addresses. Anything private, reserved, loopback,
// / or link-local returns FALSE, as does any string that is not a valid IP at all.
// / This is the single source of truth for IP safety. Do not duplicate this logic.
function isPubliclyRoutableIP($ip) {
  // / Set variables.
  $Check = (bool)filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
  return $Check; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to perform DNS lookups without following redirects.
// / Also used to detect if a host resolves to a LAN segment or private IP range.
// / Note that this has an intentionally redundant check for whether or not the IP is private.
// / A private/reserved answer means this host is untrustworthy regardless of what else it returned.
// / $URLIP will return FALSE if the lookup failed or if a DNS rebind attack is suspected.
// / $StreamContainsLAN will return TRUE if the response contained a local or reserved IP address.
// / $LookupFailed will return TRUE if no DNS response was received or FALSE if DNS succeeded.
function dnsLookup($URLHost) {
  // / Set variables.
  $records = $record = array();
  $urlIP = $URLIP = $LookupFailed = $StreamContainsLAN = $isPublic = FALSE;
  // / Perform the actual DNS lookup against the $URLHost.
  $records = @dns_get_record($URLHost, DNS_A + DNS_AAAA);
  // / Check that the records received from the DNS provider were formed properly.
  if (is_array($records) && !empty($records)) {
    foreach ($records as $record) {
      // / Parse the received DNS records.
      $urlIP = $record['ip'] ?? $record['ipv6'] ?? NULL;
      if ($urlIP === NULL) continue;
      $isPublic = isPubliclyRoutableIP($urlIP);
      if ($isPublic) $URLIP = $urlIP;
      else {
        // / A private/reserved answer means this host is untrustworthy regardless of what else it returned.
        // / Discard any safe IP already found. A host answering with both is a rebinding setup, not a partial success.
        $StreamContainsLAN = TRUE;
        $URLIP = FALSE;
        break; } } }
  // / Set a flag to tell if the lookup failed outright.
  else $LookupFailed = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $records = $record = $urlIP = $isPublic = NULL;
  unset($records, $record, $urlIP, $isPublic);
  return array($URLIP, $StreamContainsLAN, $LookupFailed); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to scan a stream URL received from a stream file such as .m3u8.
// / This function determines whether that URL is safe for FFMPEG to handle.
// / This function performs a DNS lookup on the provided URL but DOES NOT follow redirects.
// / Not following redirects is critical.
// / To perform adequate SSRF protection we must obtain a non-redirected lookup for each remote host.
// / The information obtained here binds downstream dependencies like CURL & FFMPEG to these locations.
function gatherRemoteStreamHostInfo($StreamURL) {
  // / Set variables.
  global $Verbose, $AllowStreamOverHTTP;
  $LookupFailed = $InspectionFailed = TRUE;
  $URLIP = $URLHost = $URLPort = $URLScheme = $StreamContainsLAN = $StreamDNSContainsLAN = $StreamURLResolutionFailed = FALSE;
  $urlIsSanitized = $partsAreSanitized = $schemeIsSanitized = $hostIsSanitized = FALSE;
  $allowedSchemes = array('https');
  $urlParts = array();
  // / Sanitize the supplied URL before anything else looks at it.
  list ($StreamURL, $urlIsSanitized) = sanitize($StreamURL, TRUE);
  if ($Verbose) logEntry('Inspecting Stream URL: '.$StreamURL.'.');
  // / Check if plain http stream URLs are allowed by config.php.
  if ($AllowStreamOverHTTP) array_push($allowedSchemes, 'http');
  // / A URL that could not be sanitized is unresolvable before we even parse it.
  if (!$urlIsSanitized) $StreamURLResolutionFailed = TRUE;
  else {
    // / Parse the provided URL to gather DNS information.
    // / parse_url returns FALSE if the response was seriously malformed.
    $urlParts = parse_url($StreamURL);
    // / If the parse_url response is malformed, then consider the URL unresolvable.
    if (!$urlParts) $StreamURLResolutionFailed = TRUE;
    // / If the parse_url response makes sense, then keep going.
    else {
      list ($urlParts, $partsAreSanitized) = sanitize($urlParts, TRUE);
      // / If the parse_url response is incomplete, then consider the URL unresolvable.
      if (!$partsAreSanitized or empty($urlParts['scheme']) or empty($urlParts['host'])) $StreamURLResolutionFailed = TRUE;
      // / If the parse_url response makes sense, then keep going.
      else {
        // / If the scheme is not supported by config.php, then consider the URL unresolvable.
        list ($URLScheme, $schemeIsSanitized) = sanitize(strtolower($urlParts['scheme']), TRUE);
        if (!$schemeIsSanitized or !in_array($URLScheme, $allowedSchemes, TRUE)) $StreamURLResolutionFailed = TRUE;
        // / If the scheme is supported by config.php, then keep going.
        else {
          // / Detect the host.
          list ($URLHost, $hostIsSanitized) = sanitize(strtolower($urlParts['host']), TRUE);
          if (!$hostIsSanitized) $StreamURLResolutionFailed = TRUE;
          // / Detect the port where applicable.
          // / Be very careful making changes to this code.
          // / This uses the port supplied by the request when available.
          // / When no port was supplied it falls back to 443 for https & 80 for http.
          // / This code prevents dependencies from silently performing their own DNS lookups.
          // / If this code fails to produce a valid host or port, dependencies will ignore our binding.
          // / CURL & FFMPEG cannot be allowed to do lookups that could be spoofed into following redirects.
          else $URLPort = isset($urlParts['port']) ? (int)$urlParts['port'] : ($URLScheme === 'https' ? 443 : 80); } } } }
  // / If we have successfully obtained a host, port & scheme, then resolve the address.
  if (!$StreamURLResolutionFailed) {
    // / If the host is already a literal IP, validate it directly & skip DNS entirely.
    if (filter_var($URLHost, FILTER_VALIDATE_IP)) {
      if (isPubliclyRoutableIP($URLHost)) {
        $URLIP = $URLHost;
        $LookupFailed = FALSE; }
      else $StreamContainsLAN = TRUE; }
    // / Otherwise it is a hostname, so resolve it without following redirects.
    else list($URLIP, $StreamDNSContainsLAN, $LookupFailed) = dnsLookup($URLHost);
    // / A DNS result that was not publicly routable is treated as LAN & will be denied.
    // / This condenses the DNS finding into one flag because anything containing LAN is denied.
    if ($StreamDNSContainsLAN) $StreamContainsLAN = TRUE; }
  // / If any check failed or any required value is empty, then the whole inspection failed.
  if ($StreamURLResolutionFailed or $StreamContainsLAN or $LookupFailed or empty($URLHost) or empty($URLPort) or empty($URLScheme) or empty($URLIP)) $InspectionFailed = TRUE;
  // / If everything passed then consider the inspection to have passed.
  else $InspectionFailed = FALSE;
  // / Write the information obtained to the log file.
  if ($Verbose) logEntry('URL Inspection Result: '.($InspectionFailed ? 'FAILED' : 'PASSED').', Host: '.$URLHost.', Port: '.$URLPort.', Scheme: '.$URLScheme.', Contains LAN: '.($StreamContainsLAN ? 'TRUE' : 'FALSE').', URL Resolution Failed: '.($StreamURLResolutionFailed ? 'TRUE' : 'FALSE').', Lookup Failed: '.($LookupFailed ? 'TRUE' : 'FALSE').'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $allowedSchemes = $urlParts = $StreamDNSContainsLAN = $urlIsSanitized = $partsAreSanitized = $schemeIsSanitized = $hostIsSanitized = NULL;
  unset($allowedSchemes, $urlParts, $StreamDNSContainsLAN, $urlIsSanitized, $partsAreSanitized, $schemeIsSanitized, $hostIsSanitized);
  return array($InspectionFailed, $StreamURLResolutionFailed, $StreamContainsLAN, $LookupFailed, $URLHost, $URLPort, $URLScheme, $URLIP); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to turn a URI found inside a stream file into a complete, absolute URL.
// / Stream files routinely reference segments & nested playlists by relative path, which
// / inherit their missing parts from the URL the PARENT manifest was downloaded from.
// / $ParentURL is empty for the file the user uploaded, because nobody fetched it. A relative
// / URI at that layer therefore resolves against nothing & is refused rather than guessed at.
// / Returns an empty string when the URI cannot be honestly resolved. The caller must deny on empty.
function resolveStreamURI($StreamURI, $ParentURL) {
  // / Set variables.
  $AbsoluteURL = '';
  $parentParts = array();
  $parentScheme = $parentHost = $parentPort = $parentDir = '';
  $uriIsAbsolute = $parentIsUsable = FALSE;
  $StreamURI = trim($StreamURI);
  // / Case 1. The URI already carries its own scheme, so nothing is inherited & we are done.
  // / Note this deliberately matches ANY scheme, including file: and gopher:, so that
  // / gatherRemoteStreamHostInfo() sees & rejects them rather than us silently mangling them.
  if ($StreamURI !== '' && preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*:#', $StreamURI)) {
    $uriIsAbsolute = TRUE;
    $AbsoluteURL = $StreamURI; }
  // / Everything below here is relative & needs a parent to inherit from.
  // / The user's uploaded file has no parent, so a relative URI at layer 0 is unresolvable.
  if (!$uriIsAbsolute && $StreamURI !== '' && $ParentURL !== '') {
    $parentParts = parse_url($ParentURL);
    if ($parentParts && !empty($parentParts['scheme']) && !empty($parentParts['host'])) $parentIsUsable = TRUE; }
  // / Inherit whatever the relative form is missing from the parent it was found in.
  if ($parentIsUsable) {
    $parentScheme = strtolower($parentParts['scheme']);
    $parentHost = strtolower($parentParts['host']);
    $parentPort = isset($parentParts['port']) ? ':'.(int)$parentParts['port'] : '';
    // / Case 2. Protocol-relative (//cdn.example.com/seg.ts). Inherits the scheme only.
    if (substr($StreamURI, 0, 2) === '//') $AbsoluteURL = $parentScheme.':'.$StreamURI;
    // / Case 3. Root-relative (/hls/seg.ts). Inherits scheme, host & port. The path is replaced outright.
    elseif ($StreamURI[0] === '/') $AbsoluteURL = $parentScheme.'://'.$parentHost.$parentPort.$StreamURI;
    // / Case 4. Plain relative (seg003.ts). Appended to the parent's DIRECTORY, never to its filename.
    else {
      $parentDir = rtrim(dirname($parentParts['path'] ?? '/'), '/');
      $AbsoluteURL = $parentScheme.'://'.$parentHost.$parentPort.$parentDir.'/'.$StreamURI; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $parentParts = $parentScheme = $parentHost = $parentPort = $parentDir = $uriIsAbsolute = $parentIsUsable = NULL;
  unset($parentParts, $parentScheme, $parentHost, $parentPort, $parentDir, $uriIsAbsolute, $parentIsUsable);
  return $AbsoluteURL; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to gather and validate IPv4 & IPv6 addresses from stream files.
// / Does not perform DNS or any remote validation.
// / This function only validates the syntactical form of IP addresses, and ensures they are not in a reserved range.
function inspectStreamIP($streamFileContents) {
  // / Set variables.
  $ipMatch = '';
  $IPCount = 0;
  $ipMatchesTemp = $ip4Temp = $ip6Temp = $IPMatches = array();
  $StreamContainsLAN = $StreamContainsIP = FALSE;
  // / Regex pattern to extract matching IPv4 formats.
  $ip4Pattern = '/\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b/';
  // / Regex pattern to extract matching IPv6 formats.
  // / Loose candidate matcher. Finds hex-and-colon runs that MIGHT be IPv6.
  // / Deliberately over-matches; filter_var() (called below) is the authority on validity.
  $ip6Pattern = '/(?<![0-9A-Fa-f:])(?:[0-9A-Fa-f]{0,4}:){2,7}[0-9A-Fa-f]{0,4}(?::[0-9]{1,3}(?:\.[0-9]{1,3}){3})?(?![0-9A-Fa-f:])/';
  // / Check if the $streamFile contains any IP addresses, if those addresses are valid, & if they fall into a private subnet.
  // / $ipMatchesTemp = flat array of every potential IP string matched by either pattern.
  // / $IPMatches = flat array of every valid, publicly routable IP confirmed by filter_var.
  preg_match_all($ip4Pattern, $streamFileContents, $ip4Temp);
  preg_match_all($ip6Pattern, $streamFileContents, $ip6Temp);
  $ipMatchesTemp = array_merge($ip4Temp[0], $ip6Temp[0]);
  if (!empty($ipMatchesTemp)) {
    foreach ($ipMatchesTemp as $ipMatch) {
      // / Strip brackets from URL-form IPv6 (https://[::1]/) before validating.
      $ipMatch = trim($ipMatch, '[]');
      // / Not a real IP at all. Probably a regex over-match. Discard silently, do NOT flag.
      if (!filter_var($ipMatch, FILTER_VALIDATE_IP)) continue;
      // / Set a flag if the entry that was found is a genuine raw IP address.
      $StreamContainsIP = TRUE;
      // / Check whether each extracted IP is on a local / private subnet, or something we should not be probing.
      if (isPubliclyRoutableIP($ipMatch)) array_push($IPMatches, $ipMatch);
      else {
        // / Set a flag if the IP address that was found appears to be on a local or private subnet.
        $StreamContainsLAN = TRUE;
        // / Stop as soon as we find a dangerous IP address. There is no need to continue validating a malicious file.
        break; } } }
  // / Count the number of publicly routable IPs found before we stopped.
  $IPCount = count($IPMatches);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $ipMatch = $ipMatchesTemp = $ip4Pattern = $ip6Pattern = $streamFileContents = $ip4Temp = $ip6Temp = NULL;
  unset($ipMatch, $ipMatchesTemp, $ip4Pattern, $ip6Pattern, $streamFileContents, $ip4Temp, $ip6Temp);
  return array($IPMatches, $IPCount, $StreamContainsLAN, $StreamContainsIP); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to gather and validate domain names from stream files.
// / Does not perform DNS or any remote validation.
// / This function only validates the syntactical form of domain names.
// / Preserves http:// and https:// as the only allowed protocols.
function inspectStreamDomain($streamFileContents) {
  // / Set variables.
  $DomainCount = 0;
  $DomainNames = $domainMatches = array();
  $StreamContainsDomain = FALSE;
  // / Matches only absolute http/https URLs & captures the hostname portion in group 1.
  // / Skips any userinfo (user:pass@) so the REAL host is captured, not the decoy before the @.
  $domainPattern = '/\bhttps?:\/\/(?:[^\/?#@\s]*@)?([a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*\.[a-zA-Z]{2,63})(?=[:\/?#]|\s|$)/i';
  // / Check if the $streamFile contains any domain names, and whether those domain names are valid.
  if (preg_match_all($domainPattern, $streamFileContents, $domainMatches)) {
    // / Set a flag if the entry that was found appears to be a domain name.
    $StreamContainsDomain = TRUE;
    // / $domainMatches[1] holds the bare hostnames & is the only row safe to hand to DNS.
    $DomainNames = $domainMatches[1];
    // / $domainMatches[0] holds the FULL match, including scheme & any user:pass@ decoy.
    // / $domainMatches[0] is NOT safe to use for DNS. It is only referenced here for counting.
    $DomainCount = count($domainMatches[0]); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $domainPattern = $streamFileContents = $domainMatches = NULL;
  unset($domainPattern, $streamFileContents, $domainMatches);
  return array($DomainNames, $DomainCount, $StreamContainsDomain); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to confirm a downloaded file is genuinely MPEG-TS & not something disguised as one.
// / MPEG-TS is a fixed-size packet format: every packet is 188 bytes & begins with sync byte 0x47.
// / Requiring the sync byte at EVERY expected boundary makes a coincidental match effectively impossible.
// / Does NOT check the file extension. FFMPEG dispatches on content, so we must too.
// / Note that $MaxStreamInspectionFileSize must stay above ($packetSize * $packetsToCheck).
// / Otherwise every genuine segment will fail this check for the wrong reason.
function inspectTSFile($fileContents) {
  // / Set variables.
  $packetSize = 188;
  $packetsToCheck = 5;
  $syncByte = "\x47";
  $offset = 0;
  $bytesRequired = 0;
  $Check = FALSE;
  $bytesRequired = $packetSize * $packetsToCheck;
  // / A file too short to hold the packets we intend to check cannot be validated, so reject it.
  // / This gate also prevents the loop below from reading past the end of the string.
  if (strlen($fileContents) >= $bytesRequired) {
    // / Assume success, then let any single missing sync byte disprove it & stop immediately.
    $Check = TRUE;
    // / Walk the expected packet boundaries & confirm the sync byte appears at every one.
    for ($offset = 0; $offset < $bytesRequired; $offset += $packetSize) {
      if ($fileContents[$offset] !== $syncByte) {
        $Check = FALSE;
        break; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $packetSize = $packetsToCheck = $syncByte = $offset = $bytesRequired = $fileContents = NULL;
  unset($packetSize, $packetsToCheck, $syncByte, $offset, $bytesRequired, $fileContents);
  return $Check; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to determine what a stream file actually IS, based only on its content.
// / The filename & extension are deliberately ignored. FFMPEG dispatches on content markers,
// / so a .ts file whose bytes begin with #EXTM3U will be treated by FFMPEG as a playlist.
// / This is the single source of truth for stream file classification. Do not duplicate this logic.
function classifyStreamContent($streamContents) {
  // / Set variables.
  $IsPlaylist = $IsSegment = FALSE;
  // / A playlist must open with the #EXTM3U tag. ltrim handles a BOM or leading whitespace.
  if (strncmp(ltrim($streamContents), '#EXTM3U', 7) === 0) $IsPlaylist = TRUE;
  // / Only check for MPEG-TS if it is not already a playlist. Nothing can legitimately be both.
  else $IsSegment = inspectTSFile($streamContents);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $streamContents = NULL;
  unset($streamContents);
  return array($IsPlaylist, $IsSegment); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to download a single remote stream file to local disk for inspection.
// / Files are saved with a numeric name & no extension so nothing downstream is fooled by a filename.
// / The original URI is preserved in the stream record, not on disk.
// / This function does NOT follow redirects.
// / This function does NOT let CURL perform its own DNS lookup.
// / Only the first $MaxStreamInspectionFileSize bytes are fetched.
// / We are classifying files here, not streaming them.
// / $StreamConnectionTimeout is documented in seconds & is used directly.
// / $StreamWatchTimeout is documented in minutes & is converted once here.
function downloadStreamFile($StreamURL, $URLHost, $URLPort, $URLIP, $URLScheme, $FileNumber) {
  // / Set variables.
  global $Verbose, $AllowStreamOverHTTP, $StreamConnectionTimeout, $StreamWatchTimeout, $DirSep, $MaxStreamInspectionFileSize, $StreamTemp;
  $DownloadFailed = $StreamFileTruncated = TRUE;
  $pinIsComplete = FALSE;
  $curlOutput = array();
  $curlCommand = '';
  $curlExitCode = 1;
  $downloadedBytes = 0;
  // / Sequential name for every file saved to StreamTemp.
  // / This number never resets during a walk.
  // / A layer 2 file must never overwrite a layer 1 file.
  $LocalStreamPath = $StreamTemp.$DirSep.$FileNumber;
  $protoString = 'https';
  // / Only widen to plain http when config allows it AND this specific URL actually uses it.
  if ($AllowStreamOverHTTP && $URLScheme === 'http') $protoString = 'http,https';
  // / Refuse outright if any component needed for the DNS pin is missing.
  // / An empty component produces a malformed --resolve entry which CURL silently ignores.
  // / CURL would then perform its own lookup & every rebinding protection would be lost.
  if (!empty($URLHost) && !empty($URLPort) && !empty($URLIP) && !empty($StreamURL)) $pinIsComplete = TRUE;
  else if ($Verbose) logEntry('Stream download refused: incomplete validation data for '.$StreamURL.'.');
  if ($pinIsComplete) {
    // / Build the command with every user influenced value escaped.
    // / There is no -L flag, so redirects are never followed.
    // / The -r flag requests only the first chunk of the file.
    // / The --max-filesize flag enforces the same ceiling when a host ignores -r.
    $curlCommand = 'curl'
      .' --resolve '.escapeshellarg($URLHost.':'.$URLPort.':'.$URLIP)
      .' --proto '.escapeshellarg('='.$protoString)
      .' --proto-redir '.escapeshellarg('='.$protoString)
      .' -r '.escapeshellarg('0-'.((int)$MaxStreamInspectionFileSize - 1))
      .' --max-filesize '.(int)$MaxStreamInspectionFileSize
      .' --connect-timeout '.(int)$StreamConnectionTimeout
      .' -m '.((int)$StreamWatchTimeout * 60)
      .' -sS -o '.escapeshellarg($LocalStreamPath)
      .' -- '.escapeshellarg($StreamURL).' 2>&1';
    exec($curlCommand, $curlOutput, $curlExitCode);
    // / A successful download requires exit code 0 AND a file that exists with content.
    // / Either one alone is not proof of success.
    if ($curlExitCode === 0 && file_exists($LocalStreamPath)) {
      $downloadedBytes = filesize($LocalStreamPath);
      if ($downloadedBytes > 0) $DownloadFailed = FALSE;
      // / A file that filled the entire budget was almost certainly cut short.
      // / We are only holding part of it, so we cannot claim to have inspected the whole thing.
      if ($downloadedBytes < (int)$MaxStreamInspectionFileSize) $StreamFileTruncated = FALSE; }
    else if ($Verbose) logEntry('Stream download failed for '.$StreamURL.'. CURL exit code: '.$curlExitCode.'.');
    // / Log the outcome of this single download.
    if ($Verbose) logEntry('Stream Download Result: '.($DownloadFailed ? 'FAILED' : 'SUCCESS').', File: '.$FileNumber.', Bytes: '.$downloadedBytes.', Truncated: '.($StreamFileTruncated ? 'TRUE' : 'FALSE').'.'); }
  // / Never report a path we did not successfully write to.
  // / The caller reads this file immediately after this function returns.
  if ($DownloadFailed) $LocalStreamPath = '';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $curlCommand = $curlOutput = $protoString = $curlExitCode = $downloadedBytes = $pinIsComplete = NULL;
  unset($curlCommand, $curlOutput, $protoString, $curlExitCode, $downloadedBytes, $pinIsComplete);
  return array($DownloadFailed, $LocalStreamPath, $StreamFileTruncated); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to scan a single local stream file & determine if it is safe for FFMPEG to handle.
// / $CurrentLayer 0 is the file the user uploaded. Layers 1+ are files HRConvert2 downloaded for inspection.
// / Downloaded files are saved with a numeric name & NO extension on purpose, so extension-based
// / checks apply to layer 0 only. From layer 1 onward, content is the only authority.
// / This function inspects. It does not connect to anything & it does not decide the fate of the walk.
function inspectStreamFile($StreamFile, $ParentURL, $CurrentLayer) {
  // / Set variables.
  global $Verbose, $AllowStreamOverHTTP, $SupportedConversionTypes, $StreamArray;
  $StreamContainsIP = $StreamContainsLAN = $StreamContainsHTTP = $StreamContainsDomain = FALSE;
  $streamFileExtension = $RawURI = $streamFileContents = '';
  $StreamURIs = $DomainMatches = $IPMatches = $streamLineMatches = array();
  $DomainCount = $IPCount = 0;
  $looksLikePlaylist = $looksLikeSegment = $ContentMismatch = $ContentUnknown = $InspectionFailed = FALSE;
  $extensionAllowed = TRUE;
  // / Get the file extension of the $StreamFile.
  $streamFileExtension = getExtension($StreamFile);
  // / Log the start of stream file inspection.
  if ($Verbose) logEntry('Inspecting Stream File '.$StreamFile.' at layer '.$CurrentLayer.' for security risks.');
  // / For sanity, double check that Stream & Audio operations are even allowed in config.php.
  // / Extra precaution is required due to the cost, sensitivity, & potential consequences of this function being abused.
  // / The supported-format check applies to layer 0 only. Files we downloaded have no extension by design.
  if ($CurrentLayer === 0 && !in_array($streamFileExtension, $StreamArray)) $extensionAllowed = FALSE;
  if (in_array('Stream', $SupportedConversionTypes) && in_array('Audio', $SupportedConversionTypes) && $extensionAllowed) {
    // / Read the contents of the stream file.
    $streamFileContents = file_get_contents($StreamFile);
    // / Classify by content. This is the ONLY place that decision is made. See classifyStreamContent().
    list ($looksLikePlaylist, $looksLikeSegment) = classifyStreamContent($streamFileContents);
    // / Determine if the content of the file matches its file extension.
    // / Layer 0 only. A name that disagrees with the content is not a quirk to work around, it is the attack.
    if ($CurrentLayer === 0) {
      if ($looksLikePlaylist && $streamFileExtension !== 'm3u8') $ContentMismatch = TRUE;
      if ($looksLikeSegment && $streamFileExtension !== 'ts') $ContentMismatch = TRUE; }
    // / Neither format. FFMPEG would probe this & pick some demuxer we never anticipated.
    if (!$looksLikePlaylist && !$looksLikeSegment) $ContentUnknown = TRUE;
    // / Any single failure condition ends this inspection immediately.
    if ($ContentMismatch or $ContentUnknown) $InspectionFailed = TRUE;
    // / If the file passed classification, then continue.
    if (!$InspectionFailed) {
      // / Set a flag if the file references any plain-http address. Controlled by config.php.
      if (stripos($streamFileContents, 'http://') !== FALSE) $StreamContainsHTTP = TRUE;
      // / Check the stream file contents for domain names and assemble them into an array.
      list ($DomainMatches, $DomainCount, $StreamContainsDomain) = inspectStreamDomain($streamFileContents);
      // / Check the stream file contents for raw IP addresses and assemble them into an array.
      list ($IPMatches, $IPCount, $StreamContainsLAN, $StreamContainsIP) = inspectStreamIP($streamFileContents);
      // / Iterate through the $streamFileContents & extract the complete URI from each URI line.
      // / These URIs are raw & completely unvalidated. The walker validates them one at a time.
      foreach (preg_split('/\R/', $streamFileContents) as $streamLine) {
        $streamLine = trim($streamLine);
        // / Skip empty lines in the playlist file.
        if ($streamLine === '') continue;
        // / Non-# lines are URIs. # lines may still carry a URI="" attribute.
        // / #EXT-X-KEY, #EXT-X-MAP & #EXT-X-MEDIA all reference fetchable URIs this way.
        if ($streamLine[0] !== '#') $RawURI = $streamLine;
        elseif (preg_match('/URI="([^"]*)"/i', $streamLine, $streamLineMatches)) $RawURI = $streamLineMatches[1];
        // / A # line with no URI attribute is just a tag. Nothing to inspect.
        else continue;
        // / One record per URI found. Only RawURI, ParentURL & Layer are knowable here.
        // / Everything else is filled in later by the walker as each stage completes.
        $StreamURIs[] = array(
          'RawURI'      => $RawURI,
          'AbsoluteURL' => '',
          'ParentURL'   => $ParentURL,
          'Layer'       => $CurrentLayer,
          'URLHost'     => '',
          'URLPort'     => '',
          'URLScheme'   => '',
          'URLIP'       => '',
          'LocalPath'   => '',
          'IsPlaylist'  => FALSE,
          'IsSegment'   => FALSE,
          'Inspected'   => FALSE,
          'Failed'      => FALSE,
          'FailReason'  => ''); } } }
  // / If the operation is not permitted by config.php, or the extension is not supported, then fail.
  else $InspectionFailed = TRUE;
  // / Any single failure condition fails this file. Flags are one-way & nothing can clear them.
  if ($StreamContainsLAN or $ContentMismatch or $ContentUnknown) $InspectionFailed = TRUE;
  // / A plain-http reference is only fatal when config.php has disabled it.
  if ($StreamContainsHTTP && !$AllowStreamOverHTTP) $InspectionFailed = TRUE;
  // / Log the result of the inspection of this single file.
  if ($Verbose) logEntry('Stream File Inspection Result: '.($InspectionFailed ? 'FAILED' : 'PASSED').', Layer: '.$CurrentLayer.', URIs Found: '.count($StreamURIs).', Domains: '.$DomainCount.', IPs: '.$IPCount.', Contains LAN: '.($StreamContainsLAN ? 'TRUE' : 'FALSE').', Contains IP: '.($StreamContainsIP ? 'TRUE' : 'FALSE').', Contains Domain: '.($StreamContainsDomain ? 'TRUE' : 'FALSE').', Contains HTTP: '.($StreamContainsHTTP ? 'TRUE' : 'FALSE').', Content Mismatch: '.($ContentMismatch ? 'TRUE' : 'FALSE').', Content Unknown: '.($ContentUnknown ? 'TRUE' : 'FALSE').'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $streamFileContents = $DomainMatches = $IPMatches = $streamLineMatches = $RawURI = $extensionAllowed = NULL;
  unset($streamFileContents, $DomainMatches, $IPMatches, $streamLineMatches, $RawURI, $extensionAllowed);
  return array($InspectionFailed, $StreamURIs, $StreamContainsLAN, $StreamContainsIP, $StreamContainsHTTP, $looksLikePlaylist, $looksLikeSegment); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The walker. Owns the queue, the seen-set, the depth counter & every budget.
// / Every other stream function answers a question. This one is the only thing that decides to continue.
// / $FileBudget resets each layer because it bounds the width of one layer.
// / $TotalBudget never resets because it bounds the entire tree regardless of shape.
// / $Halt is one-way. Once anything sets it, nothing may clear it.
function streamFileWalker($StreamFile) {
  global $Verbose, $StreamInspectionLayers, $StreamInspectionFilesPerLayer, $DefaultStreamInspectionForfeitAction;
  // / Set variables.
  $Halt = $StreamBudgetExhausted = $InspectionFailed = $DownloadFailed = $StreamFileTruncated = FALSE;
  $looksLikePlaylist = $looksLikeSegment = $StreamContainsLAN = $StreamContainsIP = $StreamContainsHTTP = FALSE;
  $StreamURLResolutionFailed = $LookupFailed = FALSE;
  $urlHost = $urlPort = $urlScheme = $urlIP = FALSE;
  $SeenURLs = $AllStreamURIs = array();
  $currentLayerFiles = $nextLayerFiles = $streamURIs = $layerFile = $uriRecord = array();
  $currentLayer = $FileNumber = $FileBudget = $index = 0;
  $HaltReason = '';
  $LayerBudget = $StreamInspectionLayers;
  // / Hard ceiling on total connections for the entire walk, regardless of tree shape.
  // / Per-layer budgets bound each file individually. This bounds the whole tree.
  $TotalBudget = $StreamInspectionLayers * $StreamInspectionFilesPerLayer;
  // / Layer 0 is the user's uploaded file. It has no SourceURL because nobody fetched it.
  $currentLayerFiles[] = array('LocalPath' => $StreamFile, 'SourceURL' => '');
  if ($Verbose) logEntry('Beginning Stream Walk on '.$StreamFile.'. Layer budget: '.$LayerBudget.', Files per layer: '.$StreamInspectionFilesPerLayer.', Total connection budget: '.$TotalBudget.'.');
  // / Walk one whole layer at a time until we run out of layers, work, or patience.
  while (!$Halt && !empty($currentLayerFiles) && $LayerBudget > 0) {
    $FileBudget = $StreamInspectionFilesPerLayer;
    $nextLayerFiles = array();
    foreach ($currentLayerFiles as $layerFile) {
      if ($Halt) break;
      // / Inspect one local file. Returns the URIs it references & its own local flags.
      list ($InspectionFailed, $streamURIs, $StreamContainsLAN, $StreamContainsIP, $StreamContainsHTTP, $looksLikePlaylist, $looksLikeSegment) = inspectStreamFile($layerFile['LocalPath'], $layerFile['SourceURL'], $currentLayer);
      if ($InspectionFailed) {
        $Halt = TRUE;
        $HaltReason = 'File inspection failed at layer '.$currentLayer.' on '.$layerFile['LocalPath'];
        break; }
      // / A genuine segment is a leaf. It has no URIs & nothing to recurse into, so this branch is done.
      if ($looksLikeSegment) continue;
      // / A file that is neither playlist nor segment should never have passed inspection.
      // / If it somehow did, refuse rather than guessing what FFMPEG would make of it.
      if (!$looksLikePlaylist) {
        $Halt = TRUE;
        $InspectionFailed = TRUE;
        $HaltReason = 'Unclassifiable file passed inspection at layer '.$currentLayer;
        break; }
      // / Early exit. If this file alone exceeds the per-layer budget, the walk can never complete.
      // / Deny now, before spending a single connection on a file that was never going to pass.
      if (count($streamURIs) > $FileBudget) {
        $StreamBudgetExhausted = TRUE;
        $Halt = TRUE;
        $HaltReason = 'File references '.count($streamURIs).' URIs, exceeding the per-layer budget of '.$FileBudget;
        break; }
      foreach ($streamURIs as $index => $uriRecord) {
        // / Resolve relative URIs against the URL this manifest came from.
        $streamURIs[$index]['AbsoluteURL'] = resolveStreamURI($uriRecord['RawURI'], $uriRecord['ParentURL']);
        // / An empty result means the URI was relative with no parent to inherit from,
        // / or the parent URL itself was unusable. Never guess. Refuse.
        if ($streamURIs[$index]['AbsoluteURL'] === '') {
          $streamURIs[$index]['Failed'] = TRUE;
          $streamURIs[$index]['FailReason'] = 'Unresolvable URI';
          $Halt = TRUE;
          $InspectionFailed = TRUE;
          $HaltReason = 'Unresolvable URI "'.$uriRecord['RawURI'].'" at layer '.$currentLayer;
          break; }
        // / Skip anything already seen. Prevents cycles from burning the budget.
        // / No budget is refunded here because none was ever spent on this URL.
        if (isset($SeenURLs[$streamURIs[$index]['AbsoluteURL']])) continue;
        $SeenURLs[$streamURIs[$index]['AbsoluteURL']] = TRUE;
        // / Validate scheme, host & DNS. Nothing connects until this passes.
        list ($InspectionFailed, $StreamURLResolutionFailed, $StreamContainsLAN, $LookupFailed, $urlHost, $urlPort, $urlScheme, $urlIP) = gatherRemoteStreamHostInfo($streamURIs[$index]['AbsoluteURL']);
        if ($InspectionFailed) {
          $streamURIs[$index]['Failed'] = TRUE;
          $streamURIs[$index]['FailReason'] = 'Host validation failed';
          $Halt = TRUE;
          $HaltReason = 'Host validation failed for '.$streamURIs[$index]['AbsoluteURL'].' (LAN: '.($StreamContainsLAN ? 'TRUE' : 'FALSE').', Lookup Failed: '.($LookupFailed ? 'TRUE' : 'FALSE').', URL Resolution Failed: '.($StreamURLResolutionFailed ? 'TRUE' : 'FALSE').')';
          break; }
        $streamURIs[$index]['URLHost'] = $urlHost;
        $streamURIs[$index]['URLPort'] = $urlPort;
        $streamURIs[$index]['URLScheme'] = $urlScheme;
        $streamURIs[$index]['URLIP'] = $urlIP;
        // / Spend budget BEFORE connecting, never after.
        if ($FileBudget < 1 or $TotalBudget < 1) {
          $StreamBudgetExhausted = TRUE;
          $Halt = TRUE;
          $HaltReason = 'Budget exhausted before connecting (files remaining this layer: '.$FileBudget.', total remaining: '.$TotalBudget.')';
          break; }
        $FileBudget--;
        $TotalBudget--;
        // / Increment before the call so a failed download still burns its number.
        // / Reusing a number could leave a partial file where the next fetch expects to write.
        $FileNumber++;
        // / Fetch with the pinned IP so CURL cannot re-resolve or follow a redirect we did not validate.
        list ($DownloadFailed, $streamURIs[$index]['LocalPath'], $StreamFileTruncated) = downloadStreamFile($streamURIs[$index]['AbsoluteURL'], $urlHost, $urlPort, $urlIP, $urlScheme, $FileNumber);
        if ($DownloadFailed) {
          $streamURIs[$index]['Failed'] = TRUE;
          $streamURIs[$index]['FailReason'] = 'Download failed';
          $Halt = TRUE;
          $InspectionFailed = TRUE;
          $HaltReason = 'Download failed for '.$streamURIs[$index]['AbsoluteURL'];
          break; }
        // / Classify what actually came back. The URI's extension is irrelevant & untrustworthy.
        $downloadedContents = file_get_contents($streamURIs[$index]['LocalPath']);
        list ($streamURIs[$index]['IsPlaylist'], $streamURIs[$index]['IsSegment']) = classifyStreamContent($downloadedContents);
        $downloadedContents = NULL;
        unset($downloadedContents);
        // / Neither format. FFMPEG would probe this & pick some demuxer we never anticipated.
        if (!$streamURIs[$index]['IsPlaylist'] && !$streamURIs[$index]['IsSegment']) {
          $streamURIs[$index]['Failed'] = TRUE;
          $streamURIs[$index]['FailReason'] = 'Unrecognized content';
          $Halt = TRUE;
          $InspectionFailed = TRUE;
          $HaltReason = 'Unrecognized content returned by '.$streamURIs[$index]['AbsoluteURL'];
          break; }
        // / A playlist we only half-read cannot be honestly called inspected. A truncated segment is fine,
        // / because a segment only ever needed its first few packets to be identified.
        if ($streamURIs[$index]['IsPlaylist'] && $StreamFileTruncated) {
          $streamURIs[$index]['Failed'] = TRUE;
          $streamURIs[$index]['FailReason'] = 'Playlist exceeded inspection size limit';
          $Halt = TRUE;
          $InspectionFailed = TRUE;
          $HaltReason = 'Playlist at '.$streamURIs[$index]['AbsoluteURL'].' exceeded the inspection size limit & could only be partially read';
          break; }
        $streamURIs[$index]['Inspected'] = TRUE;
        // / Only playlists have children. Segments are leaves & cost nothing further.
        // / Queue playlists with the URL THEY were fetched from, because that is what
        // / their own relative URIs must resolve against.
        if ($streamURIs[$index]['IsPlaylist']) $nextLayerFiles[] = array(
          'LocalPath' => $streamURIs[$index]['LocalPath'],
          'SourceURL' => $streamURIs[$index]['AbsoluteURL']); }
      // / Preserve this file's records before $streamURIs is overwritten by the next file in the layer.
      $AllStreamURIs = array_merge($AllStreamURIs, $streamURIs); }
    // / This layer is finished. Advance to whatever it queued up.
    $currentLayerFiles = $nextLayerFiles;
    $currentLayer++;
    $LayerBudget--; }
  // / Ran out of layers with work still pending. The inspection is incomplete either way.
  if (!empty($currentLayerFiles) && $LayerBudget < 1) {
    $StreamBudgetExhausted = TRUE;
    if ($HaltReason === '') $HaltReason = 'Layer budget exhausted with '.count($currentLayerFiles).' file(s) still uninspected'; }
  // / Apply the configured forfeit action to any incomplete inspection.
  if ($StreamBudgetExhausted && $DefaultStreamInspectionForfeitAction === 'DENY') $InspectionFailed = TRUE;
  // / Log the outcome of the entire walk.
  if ($Verbose) logEntry('Stream Walk Result: '.($InspectionFailed ? 'DENIED' : 'ALLOWED').', Layers Walked: '.$currentLayer.', Files Downloaded: '.$FileNumber.', URIs Examined: '.count($AllStreamURIs).', Unique URLs Seen: '.count($SeenURLs).', Budget Exhausted: '.($StreamBudgetExhausted ? 'TRUE' : 'FALSE').', Reason: '.($HaltReason === '' ? 'NONE' : $HaltReason).'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $layerFile & $uriRecord hold whole records including validated IPs & local paths, so they matter most here.
  $currentLayerFiles = $nextLayerFiles = $streamURIs = $layerFile = $uriRecord = $currentLayer = $index = NULL;
  $urlHost = $urlPort = $urlScheme = $urlIP = NULL;
  unset($currentLayerFiles, $nextLayerFiles, $streamURIs, $layerFile, $uriRecord, $currentLayer, $index);
  unset($urlHost, $urlPort, $urlScheme, $urlIP);
  return array($InspectionFailed, $StreamBudgetExhausted, $HaltReason, $AllStreamURIs, $SeenURLs); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to supervise a backgrounded FFMPEG stream conversion after the user has been served.
// / Polls the process & kills it once $StreamWatchTimeout minutes have elapsed.
// / This is the only thing preventing an abandoned stream from running until PHP or the OS intervenes.
function waitForStream($StreamPID, $newPathname) {
  // / Set variables.
  global $Verbose, $StreamWatchTimeout;
  $StreamCompleted = $StreamKilled = $pidIsUsable = FALSE;
  $psOutput = array();
  $ElapsedSeconds = 0;
  $pollInterval = 2;
  // / Config states this value in MINUTES. Convert once, here, so the loop reads in seconds.
  $timeoutSeconds = (int)$StreamWatchTimeout * 60;
  // / Nothing to supervise without a real process id.
  if ((int)$StreamPID > 0) $pidIsUsable = TRUE;
  if ($pidIsUsable) {
    if ($Verbose) logEntry('Supervising stream PID '.$StreamPID.' for up to '.$timeoutSeconds.' seconds.');
    while ($ElapsedSeconds < $timeoutSeconds) {
      $psOutput = array();
      // / A ps listing with only its header row means the process is gone.
      exec('ps -p '.(int)$StreamPID, $psOutput);
      if (count($psOutput) < 2) {
        $StreamCompleted = TRUE;
        break; }
      sleep($pollInterval);
      $ElapsedSeconds += $pollInterval; }
    // / Still running after the full watch window. Terminate it.
    if (!$StreamCompleted) {
      exec('kill -9 '.(int)$StreamPID);
      $StreamKilled = TRUE;
      if ($Verbose) logEntry('Stream PID '.$StreamPID.' exceeded the watch timeout & was terminated.'); }
    else if ($Verbose) logEntry('Stream PID '.$StreamPID.' finished after '.$ElapsedSeconds.' seconds. Output exists: '.(file_exists($newPathname) ? 'TRUE' : 'FALSE').'.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $psOutput = $pollInterval = $timeoutSeconds = $pidIsUsable = NULL;
  unset($psOutput, $pollInterval, $timeoutSeconds, $pidIsUsable);
  return array($StreamCompleted, $StreamKilled, $ElapsedSeconds); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert stream formats.
// / The stream file is fully inspected before FFMPEG is allowed anywhere near it.
// / FFMPEG is launched in the background so the user can be served immediately.
function convertStreams($pathname, $newPathname) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StreamConnectionTimeout, $AllowStreamOverHTTP;
  $ConversionSuccess = $ConversionErrors = $WaitForStream = FALSE;
  $InspectionFailed = $StreamBudgetExhausted = FALSE;
  $AllStreamURIs = $SeenURLs = array();
  $HaltReason = $httpString = $returnData = $ffmpegCommand = '';
  $StreamPID = 0;
  if ($Verbose) logEntry('Beginning stream conversion for '.$pathname.'.');
  // / Inspect the entire stream tree BEFORE FFMPEG is permitted to touch it.
  // / Nothing below this point runs unless the walk returned a clean verdict.
  list ($InspectionFailed, $StreamBudgetExhausted, $HaltReason, $AllStreamURIs, $SeenURLs) = streamFileWalker($pathname);
  if ($InspectionFailed) {
    $ConversionErrors = TRUE;
    errorEntry('Stream inspection denied this file. '.$HaltReason, 21001, FALSE); }
  // / The inspection returned a clean verdict, so FFMPEG may now be permitted to run.
  else {
    // / Only widen the protocol whitelist to plain http when config.php explicitly allows it.
    if ($AllowStreamOverHTTP) $httpString = ',http';
    // / Launch FFMPEG in the background & capture its PID so waitForStream() can reap it later.
    // / -rw_timeout is an INPUT option & must appear before -i to have any effect.
    $ffmpegCommand = 'ffmpeg -protocol_whitelist '.escapeshellarg('file,https,tcp,tls,crypto'.$httpString)
      .' -rw_timeout '.((int)$StreamConnectionTimeout * 1000000)
      .' -i '.escapeshellarg($pathname)
      .' -c copy '.escapeshellarg($newPathname)
      .' > /dev/null 2>&1 & echo $!';
    $returnData = shell_exec($ffmpegCommand);
    $StreamPID = (int)trim($returnData);
    // / A PID of 0 means the process never started at all.
    if ($StreamPID > 0) {
      $ConversionSuccess = TRUE;
      $WaitForStream = TRUE;
      if ($Verbose) logEntry('FFMPEG launched in background as PID '.$StreamPID.' for '.$newPathname.'.'); }
    else {
      $ConversionErrors = TRUE;
      errorEntry('The stream converter failed to launch!', 21000, FALSE); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $returnData = $ffmpegCommand = $httpString = $AllStreamURIs = $SeenURLs = $HaltReason = $StreamBudgetExhausted = NULL;
  unset($returnData, $ffmpegCommand, $httpString, $AllStreamURIs, $SeenURLs, $HaltReason, $StreamBudgetExhausted);
  return array($ConversionSuccess, $ConversionErrors, $WaitForStream, $StreamPID); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert audio formats.
// / The bitrate check compares instead of assigning.
function convertAudio($pathname, $newPathname, $extension, $bitrate) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $StopCounter, $SleepTimer;
  $ConversionSuccess = $ConversionErrors = FALSE;
  $returnData = $br = '';
  $stopper = 0;
  $sleepTime = $SleepTimer;
  if ($extension === 'mkv') $extension = 'matroska';
  $ext = ' -f '.$extension;
  // / Determine if the bitrate is being set.
  if (!is_numeric($bitrate) or $bitrate === FALSE) $bitrate = 'auto';
  if ($bitrate === 'auto') $br = ' ';
  else $br = ' -b:a '.$bitrate.' ';
  if ($Verbose) logEntry('Converting audio.');
  // / This code will attempt the conversion up to $StopCounter number of times.
  while (!file_exists($newPathname) && $stopper <= $StopCounter) {
    // / If the last conversion attempt failed, wait a moment before trying again.
    if ($stopper !== 0) sleep($sleepTime++);
    // / Attempt the conversion.
    $returnData = shell_exec('ffmpeg -y -i '.$pathname.$ext.$br.$newPathname);
    // / Count the number of conversions to avoid infinite loops.
    $stopper++;
    // / Stop attempting the conversion after $StopCounter number of attempts.
    if ($stopper === $StopCounter) {
      $ConversionErrors = TRUE;
      errorEntry('The audio converter timed out!', 12000, FALSE); } }
  // / Log the output of the operation to the logfile, if it is not blank.
  if ($Verbose && trim($returnData) !== '') logEntry('Ffmpeg returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
  if (file_exists($newPathname)) $ConversionSuccess = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $returnData = $stopper = $pathname = $newPathname = $ext = $br = $extension = $bitrate = $sleepTime = NULL;
  unset($returnData, $stopper, $pathname, $newPathname, $ext, $br, $extension, $bitrate, $sleepTime);
  return array($ConversionSuccess, $ConversionErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert archive & disk image formats.
function convertArchives($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $ConvertDir, $Lol, $Lolol, $StopCounter, $SleepTimer, $PermissionLevels, $RARArchiveMethod;
  $ConversionSuccess = $ConversionErrors = FALSE;
  $returnData = '';
  $filename = pathinfo($pathname, PATHINFO_FILENAME);
  $safedir2 = $ConvertDir.$filename;
  $safedir3 = $safedir2.'.7z';
  $safedir4 = $safedir2.'.zip';
  $array7zo = array('7z', 'cbz', 'cbr');
  $arrayzipo = array('zip');
  $array7zo2 = array('vhd', 'vdi', 'iso');
  $arraytaro = array('tar.gz', 'tar.bz2', 'tar');
  $arrayraro = array('rar');
  $rarMethod = 'other';
  $stopper = 0;
  $sleepTime = $SleepTimer;
  $oldExtension =  getExtension($pathname);
  // / Create a folder to contain extracted files.
  @mkdir($safedir2, $PermissionLevels);
  if (!is_dir($safedir2)) $ConversionErrors = TRUE;
  // / Code to Extract the selected archive.
  // / Currently only 7z is used, but this code exists to give flexibility.
  // / At one time I tried using zip for zip, rar for rar, ect.
  // / I determined that 7z was the most reliable in all cases.
  // / However that may some day change, so the code exists to allow future granularity.
  if ($Verbose) logEntry('Extracting file '.$pathname,' to '.$safedir2.'.');
  if (in_array(strtolower($oldExtension), $arrayzipo)) $returnData = shell_exec('7z x -aoa '.$pathname.' -o'.$safedir2);
  if (in_array(strtolower($oldExtension), $array7zo)) $returnData = shell_exec('7z x -aoa '.$pathname.' -o'.$safedir2);
  if (in_array(strtolower($oldExtension), $array7zo2)) $returnData = shell_exec('7z x -y '.$pathname.' -o'.$safedir2);
  if (in_array(strtolower($oldExtension), $arrayraro)) $returnData = shell_exec('7z x -aoa '.$pathname.' -o'.$safedir2);
  if (in_array(strtolower($oldExtension), $arraytaro)) $returnData = shell_exec('7z x -aoa '.$pathname.' -o'.$safedir2);
  // / Log the output of the extract operation to the logfile, if it is not blank.
  if ($Verbose && trim($returnData) !== '') logEntry('The extractor returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
  if ($Verbose) logEntry('Archiving file '.$safedir2.' to '.$newPathname.'.');
  // / Code to rearchive archive files using 7z.
  if (in_array($extension, $array7zo)) {
    // / This code will attempt the archive operation up to $StopCounter number of times.
    while ($stopper <= $StopCounter) {
      // / If the last conversion attempt failed, wait a moment before trying again.
      if ($stopper !== 0) sleep($sleepTime++);
      // / Attempt the conversion.
      $returnData = shell_exec('7z a -t'.$extension.' '.$newPathname.' '.$safedir2);
      // / Log the output of the archive operation to the logfile, if it is not blank.
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
      // / Log the output of the archive operation to the logfile, if it is not blank.
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
      // / Log the output of the archive operation to the logfile, if it is not blank.
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
      // / Log the output of the archive operation to the logfile, if it is not blank.
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
      // / Log the output of the archive operation to the logfile, if it is not blank.
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
  else ($ConversionSuccess = TRUE);
  // / Code to clean up temporary files & directories.
  cleanFiles($safedir2);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $filename = $safedir2 = $safedir3 = $safedir4 = $oldExtension = $returnData = $pathname = $newPathname = $extension = $array7zo = $arrayzipo = $array7zo2 = $arraytaro = $arrayraro = $sleepTime = $rarMethod = NULL;
  unset($filename, $safedir2, $safedir3, $safedir4, $oldExtension, $returnData, $pathname, $newPathname, $extension, $array7zo, $arrayzipo, $array7zo2, $arraytaro, $arrayraro, $sleepTime, $rarMethod);
  return array($ConversionSuccess, $ConversionErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert a file based on a pre-determined input type & return the results.
// / Streams are the only conversion that keeps running after the user has been served.
// / The stream supervision variables are globals so the core can reach them after this returns.
function convert($type, $pathname, $newPathname, $extension, $height, $width, $rotate, $bitrate) {
  // / Set variables.
  global $Verbose, $SupportedConversionTypes, $WaitForStream, $StreamPID, $StreamOutputPath;
  $ConversionSuccess = $ConversionErrors = FALSE;
  // / Check that the required conversion type is allowed.
  if (in_array($type, $SupportedConversionTypes)) {
    if ($type === 'Document') list ($ConversionSuccess, $ConversionErrors) = convertDocuments($pathname, $newPathname, $extension);
    if ($type === 'Image') list ($ConversionSuccess, $ConversionErrors) = convertImages($pathname, $newPathname, $height, $width, $rotate);
    if ($type === 'Model') list ($ConversionSuccess, $ConversionErrors) = convertModels($pathname, $newPathname);
    if ($type === 'Scad') list ($ConversionSuccess, $ConversionErrors) = convertSCAD($pathname, $newPathname, $extension);
    if ($type === 'Drawing') list ($ConversionSuccess, $ConversionErrors) = convertDrawings($pathname, $newPathname);
    if ($type === 'Video') list ($ConversionSuccess, $ConversionErrors) = convertVideos($pathname, $newPathname);
    if ($type === 'Subtitle') list ($ConversionSuccess, $ConversionErrors) = convertSubtitles($pathname, $newPathname);
    // / Capture the stream supervision values into globals the core can read later.
    if ($type === 'Stream') {
      list ($ConversionSuccess, $ConversionErrors, $WaitForStream, $StreamPID) = convertStreams($pathname, $newPathname);
      $StreamOutputPath = $newPathname; }
    if ($type === 'Audio') list ($ConversionSuccess, $ConversionErrors) = convertAudio($pathname, $newPathname, $extension, $bitrate);
    if ($type === 'Archive') list ($ConversionSuccess, $ConversionErrors) = convertArchives($pathname, $newPathname, $extension); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $type = $pathname = $newPathname = $extension = $height = $width = $rotate = $bitrate = NULL;
  unset($type, $pathname, $newPathname, $extension, $height, $width, $rotate, $bitrate);
  return array($ConversionSuccess, $ConversionErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify files before performing operations on them.
function verifyFile($file, $UserFilename, $UserExtension, $clean, $copy, $skip) {
  global $DangerousFiles, $ConvertDir, $ConvertTempDir, $Allowed, $Verbose;
  $FileIsVerified = $Pathname = $OldPathname = $NewPathname = $variableIsSanitized = FALSE;
  // / If the $UserFilename is blank then use the original filename instead.
  $OldExtension = getExtension($file);
  if ($UserFilename === '') $UserFilename = trim(str_replace('.'.$OldExtension, '', $file), '.');
  // / Check to make sure all iteration specific required variables are properly sanitized.
  list ($file, $variableIsSanitized) = sanitize($file, FALSE);
  list ($Pathname, $variableIsSanitized) = sanitize($ConvertTempDir.$file, FALSE);
  list ($OldPathname, $variableIsSanitized) = sanitize($ConvertDir.$file, FALSE);
  // / Check if the selected file is safe to handle.
  if (in_array(strtolower($UserExtension), $Allowed) or $UserExtension == '') if (in_array(strtolower($OldExtension), $Allowed) && !in_array(strtolower($OldExtension), $DangerousFiles) && $file !== '.' && $file !== '..' && $file !== 'index.html') $FileIsVerified = TRUE;
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
    // / If the $UserFilename & $UserExtension variables are valid we can prepare for a $NewPathname.
    if ($UserFilename && $UserExtension) {
      list($UserFilename, $variableIsSanitized) = sanitize($UserFilename.'.'.$UserExtension, TRUE);
      // / Define the $NewPathname if required.
      list ($NewPathname, $variableIsSanitized) = sanitize($ConvertDir.$UserFilename, FALSE);
      // / Make sure the $NewPathname is not a dangerous file.
      if (in_array(strtolower($UserExtension), $DangerousFiles) or !in_array(strtolower($UserExtension), $Allowed)) errorEntry('The file '.$file.' failed third stage validation!', 14003, TRUE);
      if ($Verbose && file_exists($NewPathname) && $clean) logEntry('Deleting stale file '.$Pathname.'.');
      // / Remove the $NewPathname file if it already exists.
      if (file_exists($NewPathname) && $clean) @unlink($NewPathname);
      // / Check to make sure that the stale file was deleted if required or creating a new one will cause problems.
      if (file_exists($NewPathname) && $clean) errorEntry('Could not delete stale file '.$NewPathname.'!', 14004, TRUE); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $file = $variableIsSanitized = NULL;
  unset($file, $variableIsSanitized);
  return array($FileIsVerified, $Pathname, $OldPathname, $OldExtension, $NewPathname, $UserFilename); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to build the GUI.
function buildGUI($guiType, $ButtonCode) {
  // / Set variables.
  // / The variables defined here will be usable in GUI elements, 
  // / Files like header, footer, styleCore, convertGui1, & convertGui2 have access to these variables.
  global $GuiFiles, $LanguageFiles, $LanguageStringsFile, $GuiHeaderFile, $GuiFooterFile, $GuiUI1File, $GuiUI2File, $CoreLoaded, $ConvertDir, $ConvertTempDir, $Token1, $Token2, $SesHash, $SesHash2, $SesHash3, $SesHash4, $Date, $Time, $TOSURL, $PPURL, $ShowFinePrint, $PDFWorkArr, $ArchiveArray, $DearchiveArray, $DocumentArray, $SpreadsheetArray, $ImageArray, $ModelArray, $DrawingArray, $VideoInputArray, $VideoOutputArray, $SubtitleInputArray, $SubtitleOutputArray, $StreamArray, $MediaInputArray, $MediaOutputArray, $PresentationInputArray, $PresentationOutputArray, $XPSInputArray, $XPSOutputArray, $ConvertGuiCounter1, $ConsolidatedLogFileName, $Alert, $Alert1, $Alert2, $Alert3, $FCPlural, $FCPlural1, $FCPlural2, $FCPlural3, $File, $Files, $FileCount, $SpinnerStyle, $SpinnerColor, $PacmanLoc, $Allowed, $AllowUserVirusScan, $AllowUserShare, $SupportedConversionTypes, $FullURL, $LanguageDir, $FaviconPath, $DropzonePath, $DropzoneStylesheetPath, $StylesheetPath, $JsLibraryPath, $JqueryPath, $GUIDirection, $SupportedFormatCount, $GUIAlignment, $HeaderDisplayed, $UIDisplayed, $FooterDisplayed, $LanguageStringsLoaded, $GUIDisplayed, $GuiResourcesDir, $GuiImageDir, $GuiCSSDir, $GuiJSDir, $StreamOutputArray, $SCADArray, $SCADOutputArray;
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
  if (isset($_GET['showFiles'])) $GUIDisplayed = buildGUI(2, $ButtonCode);
  // / Call the GUI from the selected language pack before files have been uploaded.
  if (!isset($_GET['showFiles'])) $GUIDisplayed = buildGUI(1, $ButtonCode);
  return $GUIDisplayed; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to upload a selection of files.
function uploadFiles() {
  // / Set variables.
  global $DangerousFiles, $VirusScan, $AllowUserVirusScan, $ConvertDir, $LogFile, $Verbose, $PermissionLevels, $Allowed;
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
    $f0 = getExtension($file);
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
  global $DangerousFiles, $Verbose, $Download, $ConvertDir, $ConsolidatedLogFileName, $Allowed;
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
    $f0 = getExtension($file);
    // / Make sure the file is not in the list of dangerous formats.
    if (in_array(strtolower($f0), $DangerousFiles) or !in_array(strtolower($f0), $Allowed)) {
      errorEntry('Unsupported file format, '.$f0.'!', 3004, FALSE);
      continue; }
    // / Make sure all iteration specific required variables are properly sanitized.
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname, $UserFilename) = verifyFile($file, FALSE, FALSE, $clean, $copy, $skip);
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
// / Each location is now unlinked using its own variable.
function deleteFiles($FilesToDelete) {
  // / Set variables.
  global $DangerousFiles, $Verbose, $ConvertDir, $ConvertTempDir;
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
      $DeleteErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 23000, FALSE);
      continue; }
    if ($Verbose) logEntry('User selected to Delete file '.$file.'.');
    $f0 = getExtension($file);
    // / Make sure the file is not in the list of dangerous formats.
    if (in_array(strtolower($f0), $DangerousFiles)) {
      errorEntry('Unsupported file format, '.$f0.'!', 23001, FALSE);
      continue; }
    // / Remove the selected file from the hosted location.
    list ($f0, $variableIsSanitized) = sanitize($ConvertTempDir.pathinfo($file, PATHINFO_BASENAME), FALSE);
    if (file_exists($f0)) @unlink($f0);
    // / Remove the selected file from the working location.
    list ($f1, $variableIsSanitized) = sanitize($ConvertDir.pathinfo($file, PATHINFO_BASENAME), FALSE);
    if (file_exists($f1)) @unlink($f1);
    // / Check that the selected files were deleted.
    if (!file_exists($f0) && !file_exists($f1)) {
      if ($Verbose) logEntry('Deleted file '.$file.'.');
      $DeleteComplete = TRUE; }
    else {
      $DeleteErrors = TRUE;
      errorEntry('Could not delete file '.$file.'!', 23002, FALSE); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $file = $f0 = $f1 = $variableIsSanitized = NULL;
  unset($file, $f0, $f1, $variableIsSanitized);
  return array($DeleteComplete, $DeleteErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to archive a selection of files.
function archiveFiles($FilesToArchive, $UserFilename, $UserExtension) {
  // / Set variables.
  global $Verbose, $VirusScan, $ConvertTempDir, $Lol, $Lolol, $RARArchiveMethod, $Lol;
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
    if (!$variableIsSanitized or !is_string($file) or $file === '.' or $file === '..' or $file === 'index.html') {
      $OperationErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 4000, FALSE); 
      continue; }
    // / Set the $clean & $copy arguments for the verifyFiles() function as needed,
    if (count($FilesToArchive) > 1) $clean = FALSE; $copy = TRUE;
    if ($Verbose) logEntry('User selected to Archive file '.$file.'.');
    // / Verify the file before performing any operations on it.
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname, $UserFilename) = verifyFile($file, $UserFilename, $UserExtension, $clean, $copy, $skip);
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
    // / Log the output of the archive operation to the logfile, if it is not blank.
    if ($Verbose && trim($returnData) !== '') logEntry('The archiver returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
    if (!file_exists($newPathname)) {
      $ArchiveError = TRUE;
      errorEntry('Could not archive file '.$pathname.' to '.$newPathname.'!', 4004, FALSE); }
    else {
      $ArchiveComplete = TRUE;
      print ($UserFilename.$Lol);
      if ($Verbose) logEntry('Archived file '.$pathname.' to '.$newPathname.'.'); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $file = $rararr = $ziparr = $tararr = $isoarr = $pathname = $userFileName = $oldPathname = $newPathname = $scanComplete = $virusFound = $returnData = $variableIsSanitized = $fileIsVerified = $oldExtension = $clean = $copy = $skip = $variableIsSanitized = $rarMethod = NULL;
  unset ($file, $rararr, $ziparr, $tararr, $isoarr, $pathname, $userFileName, $oldPathname, $newPathname, $scanComplete, $virusFound, $returnData, $variableIsSanitized, $fileIsVerified, $oldExtension, $clean, $copy, $skip, $variableIsSanitized, $rarMethod); 
  return array($ArchiveComplete, $ArchiveErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert a selection of files.
// / A backgrounded stream has no output file when this function returns.
// / $WaitForStream tells us the conversion is still running & must not be judged by its output.
function convertFiles($ConvertSelected, $UserFilename, $UserExtension, $Height, $Width, $Rotate, $Bitrate) {
  // / Set variables.
  global $Verbose, $VirusScan, $SpreadsheetArray, $PresentationInputArray, $XPSInputArray, $DocumentArray, $ImageArray, $ModelArray, $DrawingArray, $VideoInputArray, $SubtitleInputArray, $StreamArray, $MediaInputArray, $ArchiveArray, $Lol, $WaitForStream, $SCADArray;
  $MainConversionSuccess = $MainConversionErrors = $virusFound = $skip = $isExtensionSupported = $fileIsVerified = $variableIsSanitized = $outputExists = FALSE;
  $clean = $copy = TRUE;
  $docarray =  array_merge($DocumentArray, $SpreadsheetArray, $PresentationInputArray, $XPSInputArray);
  $imgarray = $ImageArray;
  $modelarray = $ModelArray;
  $drawingarray = $DrawingArray;
  $videoarray =  $VideoInputArray;
  $subtitleArray = $SubtitleInputArray;
  $streamarray = $StreamArray;
  $audioarray =  $MediaInputArray;
  $archarray = $ArchiveArray;
  $scadarray = $SCADArray;
  $arrayArray = array('Document' => $docarray, 'Image' => $imgarray, 'Model' => $modelarray, 'Scad' => $scadarray, 'Drawing' => $drawingarray, 'Video' => $videoarray, 'Subtitle' => $subtitleArray, 'Stream' => $streamarray, 'Audio' => $audioarray, 'Archive' => $archarray);
  $arrKey = 0;
  $file = '';
  // / Make sure the input files are formatted into an array.
  if (!is_array($ConvertSelected)) $ConvertSelected = array($ConvertSelected);
  // / Iterate through the array of input files.
  foreach ($ConvertSelected as $file) {
    $MainConversionSuccess = $isExtensionSupported = FALSE;
    // / Make sure the file is sanitized before processing it.
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $MainConversionErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 5000, FALSE);
      continue; }
    // / Set the $clean & $copy arguments for the verifyFiles() function as needed.
    if (count($ConvertSelected) > 1) $clean = FALSE;
    if (in_array($UserExtension, $archarray) or in_array($UserExtension, $docarray)) $clean = FALSE;
    if ($Verbose) logEntry('User selected to Convert file '.$file.'.');
    // / Verify the file before performing any operations on it.
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname, $UserFilename) = verifyFile($file, $UserFilename, $UserExtension, $clean, $copy, $skip);
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
// / Find the one format family that handles both the input & the output extension.
    // / Only one family can ever match, so stop looking as soon as it is found.
    foreach ($arrayArray as $arrKey => $arrArray) {
      if (!in_array(strtolower($oldExtension), $arrArray)) continue;
      if (!in_array(strtolower($UserExtension), $arrArray)) continue;
      $isExtensionSupported = TRUE;
      list ($ConversionSuccess, $ConversionErrors) = convert($arrKey, $pathname, $newPathname, $UserExtension, $Height, $Width, $Rotate, $Bitrate);
      if ($ConversionErrors) {
        $MainConversionErrors = TRUE;
        logEntry($arrKey.' conversion finished with errors.'); }
      if ($Verbose) logEntry($arrKey.' Conversion Complete.');
      break; }
    // / An unsupported combination never reached a converter at all.
    if (!$isExtensionSupported) {
      $MainConversionErrors = TRUE;
      errorEntry('File extension '.$oldExtension.' is not supported!', 5006, FALSE);
      continue; }
    // / The converter already failed & already logged the reason it failed.
    // / Checking for an output file here would only report the same failure a second time.
    if (!$ConversionSuccess) {
      $MainConversionErrors = TRUE;
      continue; }
    // / A backgrounded stream is judged by its launch, everything else by its output file.
    $outputExists = $WaitForStream ? TRUE : file_exists($newPathname);
    if ($outputExists) {
      $MainConversionSuccess = TRUE;
      // / Output the filename of the converted file to the UI so it can be given to the user.
      print($UserFilename.$Lol);
      if ($Verbose) logEntry('Created a file at '.$newPathname.'.'); }
    else {
      $MainConversionErrors = TRUE;
      errorEntry('Could not create '.$newPathname.' from '.$oldPathname.'!', 5005, FALSE); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $file = $pathname = $oldPathname = $oldExtension = $newPathname = $docarray = $imgarray = $audioarray = $videoarray = $subtitleArray = $streamarray = $modelarray = $drawingarray = $archarray = $scadarray = $arrayArray = $fileIsVerified = $scanComplete = $virusFound = $variableIsSanitized = $arrKey = $clean = $copy = $skip = $isExtensionSupported = $outputExists = NULL;
  unset($file, $pathname, $oldPathname, $oldExtension, $newPathname, $docarray, $imgarray, $audioarray, $videoarray, $subtitleArray, $streamarray, $modelarray, $drawingarray, $archarray, $scadarray, $arrayArray, $fileIsVerified, $scanComplete, $virusFound, $variableIsSanitized, $arrKey, $clean, $copy, $skip, $isExtensionSupported, $outputExists);
  return array($MainConversionSuccess, $MainConversionErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to OCR a selection of files.
function ocrFiles($PDFWorkSelected, $UserFilename, $UserExtension, $Method) {
  // / Set variables.
  global $Verbose, $VirusScan, $ConvertTempDir, $ConvertDir, $Lol, $Lolol, $Append, $PathToUnoconv, $HomeLoc, $Lol;
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
    $loopCheck = FALSE;
    // / Make sure the file is sanitized before processing it.
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $OperationErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 15000, FALSE); 
      continue; }
    if ($Verbose) logEntry('User selected to perform OCR on file '.$file.'.');
    // / Verify the file before performing any operations on it.
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname, $UserFilename) = verifyFile($file, $UserFilename, $UserExtension, $clean, $copy, $skip);
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
    // / Code to convert a PDF to a document.
    if (in_array(strtolower($oldExtension), $allowedOCR)) {
      if (in_array(strtolower($oldExtension), $pdf1array)) {
        // / If Method 1 is selected, attempt a direct conversion.
        if (in_array($UserExtension, $doc1array)) {
          if ($Method === 0 or $Method === '0' or $Method === '') {
            if ($Verbose) logEntry('Performing OCR using method 0.');
            // / Perform the conversion using PDFTOTEXT.
            $returnData = shell_exec('pdftotext -layout '.$pathname.' '.$pathnameTEMP);
            // / Log the output of the operation to the logfile, if it is not blank.
            if ($Verbose && trim($returnData) !== '') logEntry('The converter (PTT-1) returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
            // / Check if the conversion was successful and retry with method 1 if required. 
            if (!file_exists($pathnameTEMP)) {
              errorEntry('Could not complete the conversion using method 0. Reattempting using method 1.', 15004, FALSE);
              $Method = 1; }
            else if ($Verbose) logEntry('A file was created at '.$pathnameTEMP); }
            // / If Method 2 is selected, attempt to convert each page of the .pdf to .jpg, then convert that to .txt.
            if ($Method === 1 or $Method === '1') {
              $pathnameTEMP1 = str_replace('..', '', str_replace('.'.$oldExtension, '.jpg' , $pathname));
              if ($Verbose) logEntry('Performing OCR intermediate operation using method 0.');
              // / Perform the conversion using ImageMagick.
              $returnData = shell_exec('convert '.$pathname.' '.$pathnameTEMP1);
              // / Log the output of the operation to the logfile, if it is not blank.
              if ($Verbose && trim($returnData) !== '') logEntry('The converter (IM-1) returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
              // / If a file doesn't exist there is a good chance it is because ImageMagick has split the pages up.
              if (!file_exists($pathnameTEMP1)) {
                // / Scan the current directory for files matching the filename.
                $pagedFilesArrRAW = scandir($ConvertTempDir);
                foreach ($pagedFilesArrRAW as $pagedFile) {
                  $filename = pathinfo($pathname, PATHINFO_FILENAME);
                  // / Look for files with the same filename but in .jpg format. Skip the rest.
                  if (stripos($pagedFile, $filename) !== TRUE) continue;
                  if (stripos($pagedFile, '.jpg') !== TRUE) continue;
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
                  // / Log the output of the operation to the logfile, if it is not blank.
                  if ($Verbose && trim($returnData) !== '') logEntry('The converter (T-1) returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
                  if (!file_exists($pathnameTEMP1)) errorEntry('Could not complete the conversion using method 1.', 15005, FALSE);
                  else if ($Verbose) logEntry('A file was created at '.$pathnameTEMP1);
                  // / Recompile all of the text files into one big text file.
                  $readPageData = file_get_contents($pathnameTEMP);
                  $writePageData = file_put_contents($pathnameTEMP0, $readPageData.$Lol, $Append);
                  $multiple = TRUE;
                  if (!file_exists($pathnameTEMP0)) errorEntry('Could not OCR file!', 15006, FALSE); 
                  else if ($Verbose) logEntry('A file was created at '.$pathnameTEMP0);} }
              if ($Verbose) logEntry('Converted file '.$pathnameTEMP1.' to '.$pathnameTEMP.'.');
              if (!$multiple) {
                $pathnameTEMPTesseract = str_replace('..', '', str_replace('.txt', '', $pathnameTEMP));
                if ($Verbose) logEntry('Performing OCR final using method 0.');
                $returnData = shell_exec('tesseract '.$pathnameTEMP1.' '.$pathnameTEMPTesseract);
                // / Log the output of the operation to the logfile, if it is not blank.
                if ($Verbose && trim($returnData) !== '') logEntry('The converter (T-2) returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData))))); } } } }
        // / Code to convert a document to a PDF.
        if (in_array(strtolower($oldExtension), $doc1array)) {
          if (in_array($UserExtension, $pdf1array)) {
            // / The following code verifies that the Document Conversion Engine is installed & running.
            list ($documentEngineStarted, $documentEnginePID) = verifyDocumentConversionEngine();
            if (!$documentEngineStarted) {
              $OperationErrors = TRUE;
              errorEntry('Could not verify the Document Conversion Engine!', 15007, FALSE); }
            // / Perform the conversion using Unoconv.
            $returnData = shell_exec('python3 '.$PathToUnoconv.' --verbose --user-profile='.$HomeLoc.' -o '.$newPathname.' -f pdf '.$pathname);
            // / Log the output of the operation to the logfile, if it is not blank.
            if ($Verbose && trim($returnData) !== '') logEntry('The converter (U-1) returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData))))); } }
        // / Code to convert an image to a PDF.
        if (in_array(strtolower($oldExtension), $img1array)) {
          $pathnameTEMPTesseract = str_replace('..', '', str_replace('.'.$oldExtension, '', $pathname));
          if ($Verbose) logEntry('Performing OCR operation using method 0.');
          // / Perform the conversion using Tesseract.
          $returnData = shell_exec('tesseract '.$pathname.' '.$pathnameTEMPTesseract);
          // / Log the output of the operation to the logfile, if it is not blank.
          if ($Verbose && trim($returnData) !== '') logEntry('The converter (T-3) returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
          if (!file_exists($pathnameTEMP)) {
            $pathnameTEMP3 = str_replace('..', '', str_replace('.'.$oldExtension, '.pdf' , $pathname));
            // / The following code verifies that the Document Conversion Engine is installed & running.
            list ($documentEngineStarted, $documentEnginePID) = verifyDocumentConversionEngine();
            if (!$documentEngineStarted) {
              $OperationErrors = TRUE;
              errorEntry('Could not verify the Document Conversion Engine!', 15008, FALSE); }
            if ($Verbose) logEntry('Performing OCR intermediate operation using method 0.');
            // / Perform the conversion using Unoconv.
            $returnData = shell_exec('python3 '.$PathToUnoconv.' --verbose --user-profile='.$HomeLoc.' -o '.$pathnameTEMP3.' -f pdf '.$pathname);
            // / Log the output of the operation to the logfile, if it is not blank.
                  if ($Verbose && trim($returnData) !== '') logEntry('The converter (U-2) returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
            // / Perform the conversion using PDFTOTEXT.
            $returnData = shell_exec('pdftotext -layout '.$pathnameTEMP3.' '.$pathnameTEMP);
            // / Log the output of the operation to the logfile, if it is not blank.
            if ($Verbose && trim($returnData) !== '') logEntry('The converter (PTT-2) returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData))))); }
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
          // / The following code verifies that the Document Conversion Engine is installed & running.
          list ($documentEngineStarted, $documentEnginePID) = verifyDocumentConversionEngine();
          if (!$documentEngineStarted) {
            $OperationErrors = TRUE;
            errorEntry('Could not verify the Document Conversion Engine!', 15010, FALSE); }
        // / Perform the conversion using Unoconv.
        $returnData = shell_exec('python3 '.$PathToUnoconv.' --verbose --user-profile='.$HomeLoc.' -o '.$newPathname.' -f '.$UserExtension.' '.$pathnameTEMP);
        // / Log the output of the operation to the logfile, if it is not blank.
        if ($Verbose && trim($returnData) !== '') logEntry('The converter (U-3) returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData))))); }
      // / Error handler for if the output file does not exist.
      if (file_exists($newPathname)) {
        $loopCheck = TRUE;
        print($UserFilename.$Lol); }
      else if ($Verbose) errorEntry('Could not create a file at '.$pathnameTEMP.'!', 15011, FALSE); } }
  // / Error handler for if any failures happened during file loops.
  if ($loopCheck) $OperationSuccessful = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $file = $file1 = $file2 = $pathname = $oldPathname = $filename = $oldExtension = $newPathname = $doc1array = $img1array = $pdf1array = $pathnameTEMP = $pathnameTEMP1 = $pagedFilesArrRAW = $pagedFile = $cleanFilname = $pageNumber = $readPageData = $writePageData = $multiple = $pathnameTEMPTesseract = $pathnameTEMP3 = $clean = $copy = $skip =$allowedOCR = $variableIsSanitized = $loopCheck = NULL;
  unset ($file, $file1, $file2, $pathname, $oldPathname , $filename, $oldExtension, $newPathname, $doc1array, $img1array, $pdf1array, $pathnameTEMP, $pathnameTEMP1, $pagedFilesArrRAW, $pagedFile, $cleanFilname, $pageNumber, $readPageData, $writePageData, $multiple, $pathnameTEMPTesseract, $pathnameTEMP3, $clean, $copy, $skip, $allowedOCR, $variableIsSanitized, $loopCheck); 
  return array($OperationSuccessful, $OperationErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to create a user virus logfiles if required.
// / Type can be either 'clamav' or 'scancore'.
function verifyUserVirusLogs($type) {
  // / Set variables.
  global $Verbose, $Time, $ConvertDir, $ConvertTempDir, $UserClamLogFile, $UserScanCoreLogFile, $SesHash3, $Lol, $Append, $UserScanCoreFileName;
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
    if (file_exists($UserScanCoreLogFile)) errorEntry('Could not delete stale file '.$UserScanCoreFileName.'!', 16002, TRUE);
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
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname, $UserFilename) = verifyFile($file, $userFilename, $userExtension, $clean, $copy, $skip);
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
    // / Normally we don't write dependency output if it is blank, but for virus scans we do. 
    // / Blank virus scan output means scanner malfunction or potential tampering of the results. 
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
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname, $UserFilename) = verifyFile($file, $userFilename, $userExtension, $clean, $copy, $skip);
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
    // / Normally we don't write dependency output if it is blank, but for virus scans we do. 
    // / Blank virus scan output means scanner malfunction or potential tampering of the results. 
    if ($Verbose) logEntry('ScanCore returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
    // / Load the contents of the User ScanCore Log File for processing because it has been sanitized of unnecessary data & whitespace.
    $scanCoreLogFileDATA = @file_get_contents($UserScanCoreLogFile);
    // / Check the contents of the User ScanCore Log File for virus detections.
    if (stripos($scanCoreLogFileDATA, 'Infected') !== FALSE or stripos($scanCoreLogFileDATA, 'Infected') === TRUE) {
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
  global $Verbose, $Lol, $Append, $ConsolidatedLogFile, $UserClamLogFile, $UserScanCoreLogFile, $ConsolidatedLogFile, $ConsolidatedLogFileName;
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
  return array($ConsolidatedLogsExist, $ConsolidatedLogErrors, $ConsolidatedLogFile, $ConsolidatedLogFileName); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to scan an input file or folder for viruses with ClamAV.
// / Type can be either 'clamav', 'scancore', or 'all'.
function userVirusScan($FilesToScan, $type) {
  // / Set variables.
  global $Verbose, $Lol, $Lolol, $ApplicationName, $UserClamLogFile, $UserScanCoreLogFile, $ConsolidatedLogFile, $ConsolidatedLogFileName;
  $ScanComplete = $ScanErrors = $UserVirusFound = $scan1Complete = $scan1Errors = $scan2Complete = $scan2Errors = $ConsolidatedLogsExist = $ConsolidatedLogErrors = FALSE;
  $fileToScan = '';
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
  list ($ConsolidatedLogsExist, $ConsolidatedLogErrors, $ConsolidatedLogFile, $ConsolidatedLogFileName) = consolidateLogs($type, $UserClamLogFile, $UserScanCoreLogFile);
  // / Verify that all operations are complete.
  if ($ScanErrors or $ConsolidatedLogErrors) $ScanErrors = TRUE;
  if (!$ConsolidatedLogsExist) $ScanComplete = FALSE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  $fileToScan = $path = $type = $scan1Complete = $scan1Errors = $scan2Complete = $scan2Errors = NULL;
  unset($fileToScan, $returnData ,$path, $type, $scan1Complete, $scan1Errors, $scan2Complete, $scan2Errors);
  return array($ScanComplete, $ScanErrors, $UserVirusFound, $ConsolidatedLogFile, $ConsolidatedLogFileName); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to close the web server connection.
function closeHRC2Connection() {
  ignore_user_abort(TRUE);
  if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
  else {
    if (ob_get_level() > 0) ob_end_flush();
    flush(); } }
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

// / The following code verifies the tokens supplied by the user, if any.
// / If no tokens were supplied by the user, generate new ones.
list ($TokensAreValid, $Token1, $Token2) = verifyTokens($Token1, $Token2);

// / The following code generates a series of unique session identifiers.
list ($SesHashIsVerified, $SesHash, $SesHash2, $SesHash3, $SesHash4) = verifySesHash($Token1);
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

// / The following code removes the build & development environments if config.php asks for it.
list ($BuildEnvCleaned, $BuildEnvDeleted, $DevDocsDeleted) = cleanBuildEnvironment();
if (!$BuildEnvCleaned) errorEntry('Could not clean the build environment!', 26, TRUE);
else if ($Verbose) logEntry('Verified the build environment.');

// / The following code sets the GUI for the session.
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
  else if ($Verbose) logEntry('Displaying the GUI.'); }
else if ($Verbose) logEntry('Skipping display GUI procedure.');

// / Close the web server connection when the supplied tokens could not be verified.
if (!$TokensAreValid) {
  if ($Verbose) logEntry('Could not verify tokens. Token1: '.$Token1.' Token2: '.$Token2.'.');
  logEntry('Closing connection.');
  closeHRC2Connection();
  die(); }

// / Only enable file related operations if valid tokens have been supplied.
if ($TokensAreValid) {
  if ($Verbose) logEntry('Verified tokens. Token1: '.$Token1.' Token2: '.$Token2.'.');

  // / The following code is performed when a user initiates a file upload.
  if (!empty($_FILES)) {
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
    if ($Verbose) logEntry('Conversion Complete.'); }

  // / The following code is performed when a user performs a virus scan on a selection of files.
  if (isset($_POST['filesToScan']) && $AllowUserVirusScan) {
    logEntry('Initiating User Virus Scannner.');
    list ($ScanComplete, $ScanErrors, $UserVirusFound) = userVirusScan($FilesToScan, $UserScanType);
    if (!$ScanComplete) errorEntry('User Virus Scan Failed!', 23, TRUE);
    if ($UserVirusFound) logEntry('The User Virus Scan detected infected files.');
    if (!$UserVirusFound) logEntry('The User Virus Scan did not detect any infected files.');
    if ($ScanErrors) logEntry('User Virus Scan finished with errors.');
    if ($Verbose) logEntry('User Virus Scan Complete.'); }

  // / Close the web server connection after all required content has been served.
  logEntry('Closing connection.');
  closeHRC2Connection();

  // / Nothing below this point may produce any output.
  // / The user has already been served & the connection is already closed.
  // / If a user still has a pending stream open, keep running to monitor the FFMPEG process.
  if ($WaitForStream && $StreamPID > 0) {
    logEntry('Waiting up to '.$StreamWatchTimeout.' minutes for the user to watch the stream.');
    list ($StreamCompleted, $StreamKilled, $ElapsedSeconds) = waitForStream($StreamPID, $StreamOutputPath);
    if ($StreamKilled) logEntry('The users stream was killed.');
    if ($StreamCompleted) logEntry('The users stream has completed after '.$ElapsedSeconds.' seconds.'); }

  // / Stop execution of the application.
  die(); }
// / -----------------------------------------------------------------------------------
?>