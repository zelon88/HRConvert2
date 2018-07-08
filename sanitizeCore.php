<?php
// / -----------------------------------------------------------------------------------
// / This file is intended to be included in PHP files that require safe sanitization of 
// / supported POST and GET inputs. 

// / This file also dictates the basic HRConvert2 API. (NOT INLCLUDING APP-SPECIFIC API's)

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
// / OFFICIAL HRCONVERT2 SANITIZED API INPUTS

// / The following blocks of code each represent a distnct HRConvert2 API input.
// / To use the official API, satisfy the corresponding POST or GET variables below.
// / API inputs require that the user be logged in. Non-logged-in users will receieve a login screen.
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Sanitize the Token GET variable.
if (isset($_POST['Token1'])) {
  $Token1 = str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['Token1']); }
if (isset($_POST['Token2'])) {
  $Token2 = str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['Token2']); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Sanitize the noGui GET variable to disable the descriptive header text.
// / Good for usage in a small iframe.
if (isset($_POST['noGui'])) {
  $_GET = str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_GET['noGui']); }
// / -----------------------------------------------------------------------------------

// / Can be used to automatically download and install the latest HRConvert2 update from Github. 
// / Will perform "AutoDownload", "AutoInstall", "AutoClean", and "CompatCheck" consecutively. 
  // / Accepts a value of '1' or 'true'.
  // / ONLY ADMINISTRATORS CAN AUTO-UPDATE HRC2 !!!
if (isset($_POST['AutoUpdate'])) {
  $AutoUpdatePOST = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['AutoUpdate']), ENT_QUOTES, 'UTF-8'); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Can be used to automatically download the latest HRConvert2 package from Github.
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
  $_POST['pdfworkSelected'] = str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['pdfworkSelected']);
  if (!is_array($_POST['pdfworkSelected'])) {
    $_POST['pdfworkSelected'] = array($_POST['pdfworkSelected']); } 
  $_POST['pdfextension'] = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['pdfextension']), ENT_QUOTES, 'UTF-8'); 
  $_POST['userpdfconvertfilename'] = htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['userpdfconvertfilename']), ENT_QUOTES, 'UTF-8');
  $_POST['method'] =  htmlentities(str_replace(str_split('~#[](){};:$!#^&%@>*<"\''), '', $_POST['method']), ENT_QUOTES, 'UTF-8'); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
set_time_limit(0);
// / -----------------------------------------------------------------------------------
?>