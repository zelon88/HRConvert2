<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRConvert2, Copyright on 5/12/2026 by Justin Grimes, www.github.com/zelon88
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
// / v3.8.2.
// / This file contains language specific GUI elements to be displayed at the top of pages.
// /
// / HARDWARE REQUIREMENTS ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / DEPENDENCY REQUIREMENTS ...
// / This application requires Debian Linux, Apache 2.4, PHP 8+, FFMPEG, Dia, LibreOffice, 
// / Mkisofs, 7zip, Unoconv, libgxps-utils, Tesseract, Unzip, OpenSCAD, Rar, Inkscape, Calibre,
// / Unrar, ClamAV, MeshLab, PopplerUtils, PDFTOTEXT, ImageMagick, bwrap Dia & xvfb-run.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set a flag to tell that the UI has been displayed.
$HeaderDisplayed = TRUE;
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die('ERROR!!! HRConvert2-2, This file cannot process your request! Please submit your file to convertCore.php instead!');
// / Set required resource file related variables.
$FaviconPath = $GuiImageDir.'favicon.ico';
$JqueryPath = $GuiJSDir.'jquery-4.0.0.min.js';
$JsLibraryPath = $GuiJSDir.'HRC2-Functions.js';
$DropzonePath = $GuiJSDir.'dropzone.js';
$StylesheetPath = $GuiCSSDir.'HRConvert2.css';
$DropzoneStylesheetPath = $GuiCSSDir.'dropzone.css';
// / -----------------------------------------------------------------------------------
// / A PAGE IS GENERATED EVERY REQUEST. THE FILES BESIDE IT ARE CACHED FOR AS LONG AS A
// / BROWSER LIKES. THOSE TWO FACTS DISAGREE EVERY TIME EITHER FILE IS EDITED.
// /
// / An interface page carries values & calls into the script library for behaviour, so a
// / new page running against an old cached library is a new caller talking to functions
// / that do not exist yet. That does not degrade. It throws on the first call & the
// / interface stops, & the page looks broken to a user who did nothing wrong. An ordinary
// / reload does not fix it either, because a reload revalidates the document & keeps
// / serving the script from cache.
// /
// / Appending the file's modification time gives the file a NEW URL the moment its
// / contents change & the same URL for as long as they do not. A browser therefore
// / refetches exactly when there is something new to fetch & caches normally otherwise.
// / This is the whole fix for that class of fault. No cache clearing, no version to
// / remember to bump by hand, & nothing to explain to whoever installs this next.
// /
// / Only the files this project edits are stamped. jQuery & Dropzone are third party & are
// / replaced by swapping the file for a differently named one.
// / A missing file is left alone so that a broken path still reports as a plain 404
// / rather than as a 404 with a confusing query string attached to it.
if (file_exists($JsLibraryPath)) $JsLibraryPath .= '?v='.filemtime($JsLibraryPath);
if (file_exists($StylesheetPath)) $StylesheetPath .= '?v='.filemtime($StylesheetPath);
// / -----------------------------------------------------------------------------------
?>
<html dir='<?php echo $GUIDirection; ?>'>
  <head>
    <meta charset="UTF-8">
    <link rel='shortcut icon' href='<?php echo $FaviconPath; ?>'/>
    <link rel='stylesheet' href='<?php echo $DropzoneStylesheetPath; ?>'/>
    <link rel='stylesheet' href='<?php echo $StylesheetPath; ?>'/>
    <script type='text/javascript'>var dropzoneText = '<?php echo $GuiHeaderText1; ?>';</script>
    <script type='text/javascript' src='<?php echo $JsLibraryPath; ?>'></script>
    <script type='text/javascript' src='<?php echo $DropzonePath; ?>'></script>
    <style>
      body {
        font-family: <?php echo $Font; ?>; }
        <?php if (isset($ButtonCode)) echo $ButtonCode; ?>
    </style>
    <title><?php echo $ApplicationName; ?> - <?php echo $ApplicationTitle; ?></title>
  </head>