<?php
// / -----------------------------------------------------------------------------------
// / This file is intended to be included in PHP files that require safe sanitization of 
// / supported POST and GET inputs. 

// / This file also dictates the basic HRCloud2 API. (NOT INLCLUDING APP-SPECIFIC API's)

// / If you're looking to add code to sanitize additional 
// / POST or GET inputs, you should put it in this file and then require this file into
// / your code project, or app.
// / -----------------------------------------------------------------------------------



// / -----------------------------------------------------------------------------------
// / Developers add your code between the following comment lines.....



$your_code_here = null;



// / Developers DO NOT add your code below this comment line.
// / -----------------------------------------------------------------------------------



// / -----------------------------------------------------------------------------------
set_time_limit(0);
// / OFFICIAL HRCLOUD2 SANITIZED API INPUTS

// / The following blocks of code each represent a distnct HRCloud2 API input.
// / To use the official API, satisfy the corresponding POST or GET variables below.
// / API inputs require that the user be logged in. Non-logged-in users will receieve a login screen.
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Can be used to save changed settings.
if (isset($_POST['Save'])) {
  $saveSettings = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['Save']), ENT_QUOTES, 'UTF-8'); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Can be used to load default (current) settings.
if (isset($_POST['LoadDefaults'])) {
  $loadDefaultSettings = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['LoadDefaults']), ENT_QUOTES, 'UTF-8'); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The following code sets variables for generating client-side Apps based on user supplied specs. 
if (isset($_POST['GenerateClient'])) {
  $GenerateClient = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['GenerateClient']), ENT_QUOTES, 'UTF-8'); }
if (isset($_POST['GenClientOS'])) {
  $GenClientOS = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['GenClientOS']), ENT_QUOTES, 'UTF-8'); }
if (isset($_POST['GenClientCPU'])) {
  $GenClientCPU = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['GenClientCPU']), ENT_QUOTES, 'UTF-8'); }
if (isset($_POST['GenClientHomepage'])) {
  $GenClientHomepage = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['GenClientHomepage']), ENT_QUOTES, 'UTF-8'); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
  // / ONLY ADMINISTRATORS CAN SET COMPRESSION SETTINGS !!!
// / Can be used by administrators to set data compression settings for user uploaded content.
  // / "DataCompressionPOST" can be set to 0 for "disabled" or 1 for "enabled".
// / "DataCompressionMethod" can be set to 0, 1, or 2. 
  // / 0 = Disabled.
  // / 1 = Automatic.
  // / 2 = Maximum performance.
  // / 3 = Maximum storage capacity.
if (isset($_POST['DataCompression'])) {
  $DataCompression = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['DataCompression']), ENT_QUOTES, 'UTF-8'); }
if (isset($_POST['DataCompressionMethod'])) {
  $DataCompressionMethod = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['DataCompressionMethod']), ENT_QUOTES, 'UTF-8'); }
if (isset($_POST['NEWDataCompression'])) {
  $DataCompressionMethod = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['NEWDataCompression']), ENT_QUOTES, 'UTF-8'); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Can be used to clear the HRCloud2 cache files. Accepts a value of '1' or 'true'.
if (isset($_POST['ClearCachePOST'])) {
  $ClearCachePOST = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['ClearCachePOST']), ENT_QUOTES, 'UTF-8'); }
if (isset($_POST['ClearUserCache'])) {
  $ClearCachePOST = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['ClearUserCache']), ENT_QUOTES, 'UTF-8'); }
if (isset($_POST['ClearCache'])) {
  $ClearCachePOST = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['ClearCache']), ENT_QUOTES, 'UTF-8'); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Can be used to specify a specific file or directory within a users Cloud to scan for viruses with ClamAV.
if (isset($_POST['NEWVirusScan'])) { 
  $NEWVirusScan = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['NEWVirusScan']), ENT_QUOTES, 'UTF-8'); }
if (isset($_POST['userscanfilename'])) { 
  $userscanfilename = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['userscanfilename']), ENT_QUOTES, 'UTF-8'); }
if (isset($_POST['scanSelected'])) { 
  $scanSelected = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['scanSelected']), ENT_QUOTES, 'UTF-8'); }
if (isset($_POST['HighPerformanceAV'])) { 
  $HighPerformanceAV = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['HighPerformanceAV']), ENT_QUOTES, 'UTF-8'); }
if (isset($_POST['NEWHighPerformanceAV'])) { 
  $NEWHighPerformanceAV = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<'), '', $_POST['NEWHighPerformanceAV']), ENT_QUOTES, 'UTF-8'); }
if (isset($_POST['NEWThoroughAV'])) { 
  $NEWThoroughAV = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<'), '', $_POST['NEWThoroughAV']), ENT_QUOTES, 'UTF-8'); }
if (isset($_POST['NEWPersistentAV'])) { 
  $NEWPersistentAV = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<'), '', $_POST['NEWPersistentAV']), ENT_QUOTES, 'UTF-8'); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Can be used to sanitize new settings inputs.
if (isset($_POST['NEWColorScheme'])) { 
  $NEWColorScheme = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['NEWColorScheme']), ENT_QUOTES, 'UTF-8'); }
if (isset($_POST['NEWShowHRAI'])) { 
  $NEWShowHRAI = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['NEWShowHRAI']), ENT_QUOTES, 'UTF-8'); }
if (isset($_POST['NEWHRAIAudio'])) { 
  $NEWHRAIAudio = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['NEWHRAIAudio']), ENT_QUOTES, 'UTF-8'); }
if (isset($_POST['NEWShowTips'])) { 
  $NEWShowTips = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['NEWShowTips']), ENT_QUOTES, 'UTF-8'); }
if (isset($_POST['NEWTimezone'])) { 
  $NEWTimezone = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['NEWTimezone']), ENT_QUOTES, 'UTF-8'); }
if (isset($_POST['NEWNickname'])) { 
  $NEWNickname = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['NEWNickname']), ENT_QUOTES, 'UTF-8'); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Can be used to automatically download and install the latest HRCloud2 update from Github. 
// / Will perform "AutoDownload", "AutoInstall", "AutoClean", and "CompatCheck" consecutively. 
  // / Accepts a value of '1' or 'true'.
  // / ONLY ADMINISTRATORS CAN AUTO-UPDATE HRC2 !!!
if (isset($_POST['AutoUpdate'])) {
  $AutoUpdatePOST = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['AutoUpdate']), ENT_QUOTES, 'UTF-8'); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Can be used to automatically download the latest HRCloud2 package from Github.
  // / DOES NOT INSTALL OR REPLACE ANYTHING !!!
  // / ONLY ADMINISTRATORS CAN DOWNLOAD HRC2 UPDATES !!!
if (isset($_POST['AutoDownload'])) {
  $AutoDownloadPOST = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['AutoDownload']), ENT_QUOTES, 'UTF-8'); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Can be used to automatically install an official HRC2 update package that was download manually.
  // / WILL EXTRACT AND OVER-WRITE HRC2 SYSTEM FILES WITH ONES FROM /Resources/TEMP
if (isset($_POST['AutoInstall'])) {
  $AutoInstallPOST = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['AutoInstall']), ENT_QUOTES, 'UTF-8'); }

// / Can be used to clean up the HRC2 temp directories and perform compatibility adjustments after a manual update.
  // / ONLY ADMINISTRATORS CAN DOWNLOAD HRC2 UPDATES !!!
if (isset($_POST['AutoClean'])) {
  $AutoCleanPOST = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['AutoClean']), ENT_QUOTES, 'UTF-8'); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Can be used to automatically check for and repair compatibility bugs and known issues.
  // / Accepts a value of '1' or 'true'.
if (isset($_POST['CheckCompatibility'])) {
  $CheckCompatPOST = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['CheckCompatibility']), ENT_QUOTES, 'UTF-8'); }
if (isset($_POST['CheckCompat'])) {
  $CheckCompatPOST = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['CheckCompat']), ENT_QUOTES, 'UTF-8'); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Can be used to specify shared files for UN-sharing. Will ONLY delete the shared copy of the file. Originals will remain.
if (isset($_POST['unshareConfirm'])) {
  $_POST['unshareConfirm'] = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['unshareConfirm']), ENT_QUOTES, 'UTF-8'); 
  if (!is_array($_POST['filesToUnShare'])) {
    $_POST['filesToUnShare'] = array($_POST['filesToUnShare']); 
    $_POST['filesToUnShare'] = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['filesToUnShare']), ENT_QUOTES, 'UTF-8'); } }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Can be used to specify files for sharing files with other people by giving them a static URL on the server.
if (isset($_POST['shareConfirm'])) {
  $_POST['shareConfirm'] = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['shareConfirm']), ENT_QUOTES, 'UTF-8'); 
  if (!is_array($_POST['filesToShare'])) {
    $_POST['filesToShare'] = array($_POST['filesToShare']); 
    $_POST['filesToShare'] = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['filesToShare']), ENT_QUOTES, 'UTF-8'); } }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Can be used to create directories in the user Drive root. 
  // / This can be used to create a directory or retreive the contents of an existing directory.
  // / Example: 'Pictures/' needs to exist before 'Pictures/Flowers' can be created.
if (isset($_POST['dirToMake'])) {
  $MAKEUserDir = htmlentities(str_replace(str_split('.~#[](){};:$!#^&%@>*<"\''), '', $_POST['dirToMake']), ENT_QUOTES, 'UTF-8'); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / UserDir's can be POSTed or GETed using the "UserDIR" or "UserDirPOST" variables.
  // / Must specify either UserDir or UserDirPOST as a POST or GET variable.
if (isset($_GET['UserDirPOST'])) {
  $_GET['UserDirPOST'] = htmlentities(str_replace(str_split('.~#[](){};:$!#^&%@>*<"\''), '', $_GET['UserDirPOST']), ENT_QUOTES, 'UTF-8');
  $_POST['UserDirPOST'] = str_replace('//', '/', $_GET['UserDirPOST']);
  $_POST['UserDir'] = str_replace('//', '/', $_GET['UserDirPOST']); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Can be used to trigger HRStreamer on a valid ".Platlist" file.
  // / Must specify $_POST['streamselected'] as an array of files from the CloudLoc.
if (isset($_GET['playlistSelected'])) {
  $_GET['playlistSelected'] = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_GET['playlistSelected']), ENT_QUOTES, 'UTF-8');
  $_POST['playlistSelected'] = $_GET['playlistSelected']; }
if (isset($_POST['playlistSelected'])) {
  $_POST['playlistSelected'] = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['playlistSelected']), ENT_QUOTES, 'UTF-8');
  $_GET['playlistSelected'] = $_POST['playlistSelected']; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Can be used to upload multiple files.
  // / Must specify upload as a POST variable.
  // / Must specify $_FILES['filesToUpload'] as an array of files from the client's device.
if (isset($_POST['upload'])) {
  $upload = htmlentities(str_replace(str_split('\\/~#[](){};:$!#^&%@>*<"\''), '', $_POST['upload']), ENT_QUOTES, 'UTF-8'); 
  if (isset($_POST['filesToUpload'])) {
    $_POST['filesToUpload'] = htmlentities(str_replace(str_split('\\/~#[](){};:$!#^&%@>*<"\''), '', $_POST['filesToUpload']), ENT_QUOTES, 'UTF-8'); 
    if (!is_array($_POST['filesToUpload'])) {
      $_FILES['filesToUpload'] = array($_FILES['filesToUpload']); 
      $_FILES['filesToUpload'] = htmlentities(str_replace(str_split('\\/~#[](){};:$!#^&%@>*<"\''), '', $_FILES['filesToUpload']), ENT_QUOTES, 'UTF-8'); } } }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Can be used to download multiple files.
  // / must specify download as a POST variable.
  // / Must specify $_POST['filesToDownload'] as a string or an array of filenames in the CloudLoc.
if (isset($_POST['download'])) {
  $download = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<'), '', $_POST['download']), ENT_QUOTES, 'UTF-8'); 
  if (isset($_POST['filesToUpload'])) {
    $_POST['filesToDownload'] = htmlentities(str_replace(str_split('\\/~#[](){};:$!#^&%@>*<"\''), '', $_POST['filesToDownload']), ENT_QUOTES, 'UTF-8');   
    if (!is_array($_POST['filesToDownload'])) {
      $_POST['filesToDownload'] = array($_POST['filesToDownload']); 
      $_POST['filesToDownload'] = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['filesToDownload']), ENT_QUOTES, 'UTF-8'); } } }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Can be used to copy multiple files (will auto-increment with _0, _1, _2, _3, _##, ect. ect...).
  // / must specify copy as a POST variable.
  // / Must specify $_POST['filesToCopy'] as a string or an array of filenames in the CloudLoc.
if (isset($_POST['copy'])) {
  $_POST['copy'] = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<'), '', $_POST['copy']), ENT_QUOTES, 'UTF-8');
  if (!is_array($_POST['filesToCopy'])) {
    $_POST['newcopyfilename'] = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['newcopyfilename']), ENT_QUOTES, 'UTF-8');
    $_POST['filesToCopy'] = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['filesToCopy']), ENT_QUOTES, 'UTF-8');
    $_POST['filesToCopy'] = array($_POST['filesToCopy']); } }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Can be used to rename multiple files (will auto-increment with _0, _1, _2, _3, _##, ect. ect...).
  // / must specify rename as a POST variable.
  // / Must specify $_POST['filesToRename'] as a string or an array of filenames in the CloudLoc.
  // / Must specify a renamefilename as a POST variable.
if (isset($_POST['rename'])) {
  $_POST['rename'] = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['rename']), ENT_QUOTES, 'UTF-8');
  if (!is_array($_POST['filesToRename'])) {
    $_POST['renamefilename'] = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['renamefilename']), ENT_QUOTES, 'UTF-8'); 
    $_POST['filesToRename'] = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['filesToRename']), ENT_QUOTES, 'UTF-8');
    $_POST['filesToRename'] = array($_POST['filesToRename']); } }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Can be used to delete multiple files.
  // / must specify deleteconfirm as a POST variable.
  // / Must specify $_POST['filesToDelete'] as a string or an array of filenames in the CloudLoc.
if (isset($_POST['deleteconfirm'])) {
  $_POST['deleteconfirm'] = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['deleteconfirm']), ENT_QUOTES, 'UTF-8');
  if (!is_array($_POST['filesToDelete'])) {
    $_POST['filesToDelete'] = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['filesToDelete']), ENT_QUOTES, 'UTF-8');
    $_POST['filesToDelete'] = array($_POST['filesToDelete']); } }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Can be used to archive multiple files (will auto-increment with _0, _1, _2, _3, _##, ect. ect...).
  // / must specify archive as a POST variable.
  // / Must specify $_POST['filesToArchive'] as a string or an array of filenames in the CloudLoc.
  // / Must specify "archextension" and "userfilename" POST variables. 
    // / The filename should NOT contain an extension.
if (isset($_POST['archive'])) {
  $_POST['archive'] = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['archive']), ENT_QUOTES, 'UTF-8');
  if (!is_array($_POST['filesToArchive'])) {
    $_POST['filesToArchive'] = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['filesToArchive']), ENT_QUOTES, 'UTF-8');
    $_POST['filesToArchive'] = array($_POST['filesToArchive']);
    $_POST['archextension'] = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['archextension']), ENT_QUOTES, 'UTF-8');
    $_POST['userfilename'] = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['userfilename']), ENT_QUOTES, 'UTF-8'); } }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Can be used to de-archive multiple files, archives, or disk images.
  // / must specify dearchiveButton as a POST variable.
  // / Must specify $_POST['filesToDearchive'] as a string or an array of filenames in the CloudLoc.
if (isset($_POST["dearchiveButton"])) {
  $_POST['dearchiveButton'] = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['dearchiveButton']), ENT_QUOTES, 'UTF-8'); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Can be used to convert multiple files. Supports images, documents, media, archives, disk images, & more.
  // / IMPORTANT NOTE: For basic document or image to .pdf conversions this method of conversion will suffice.
  // / For Advanced .pdf conversions requiring OCR, please use the "pdfwork" API input instead.
    // / Must specify $_POST['convertSelected'] as a string or an array of filenames in the CloudLoc.
    // / Must specify an "extension" and a "userconvertfilename" . 
    // / OPTIONAL: Audio Files Only. Specify either pure integer to select a bitrate or "auto" for automatic (no quotes) .
      // / The userconvertfilename should NOT contain an extension.
if (isset($_POST['convertSelected'])) {
  $_POST['convertSelected'] = str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['convertSelected']);
  if (!is_array($_POST['convertSelected'])) {
    $_POST['convertSelected'] = array($_POST['convertSelected']); }
  $_POST['extension'] = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['extension']), ENT_QUOTES, 'UTF-8'); 
  $_POST['userconvertfilename'] = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['userconvertfilename']), ENT_QUOTES, 'UTF-8');
  if (isset($_POST['bitrate'])) {
    $_POST['bitrate'] = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['bitrate']), ENT_QUOTES, 'UTF-8'); } }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Can be used to convert multiple document, image, or .pdf files to other document or .pdf files.
// / Really handy for taking pictures of documents and turning them into actual document files. 
  // / Must specify $_POST['pdfworSelected'] as a string or an array of filenames in the CloudLoc.
  // // Must specift pdfextension, userpdfconvertfilename, and method.
    // /  Method must either be 0 or 1.
      // / Method 0 is automatic. The simplest method is chosen first. Best for simple image or .pdf to document conversions.
      // / Method 1 is advanced. This is best for advanced format support and multi-page .pdf to document conversions.
      // / Method 1 requires unoconv. If conversions fail make sure to run "unoconv -l" or "unoconv --listen" in a terminal window.
if (isset($_POST['pdfworkSelected'])) {
  $_POST['pdfworkSelected'] = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['pdfworkSelected']), ENT_QUOTES, 'UTF-8');
  $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
    if (!is_array($_POST['pdfworkSelected'])) {
      $_POST['pdfworkSelected'] = array($_POST['pdfworkSelected']); } 
  $_POST['pdfextension'] = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['pdfextension']), ENT_QUOTES, 'UTF-8'); 
  $_POST['userpdfconvertfilename'] = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['userpdfconvertfilename']), ENT_QUOTES, 'UTF-8');
  $_POST['method'] =  htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['method']), ENT_QUOTES, 'UTF-8'); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Can be used to add selected files to a users favorite files.
if (isset($_POST['favoriteConfirm'])) {
  $_POST['favoriteConfirm'] = str_replace(str_split('~#[](){};:$!#^&%@>*<'), '', $_POST['favoriteConfirm']); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Can be used to remove selected files from a users favorite files.
if (isset($_POST['favoriteDelete'])) {
  $_POST['favoriteDelete'] = str_replace(str_split('~#[](){};:$!#^&%@>*<'), '', $_POST['favoriteDelete']); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Can be used to display a list of favorite files in the Drive section instead of all files.
if (isset($_GET['showFavoritesPOST']) or isset($_POST['showFavorites'])) {
  $showFavorites = '1'; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
set_time_limit(0);
// / -----------------------------------------------------------------------------------
?>