<?php
// / -----------------------------------------------------------------------------------
// / APPLICATION INFORMATION ...
// / HRConvert2, Copyright on 4/13//2023 by Justin Grimes, www.github.com/zelon88
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
// / v3.2.3.
// / This file contains language specific GUI elements to be displayed at the top of pages.
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
// / Set a flag to tell that the UI has been displayed.
$HeaderDisplayed = TRUE;
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die('ERROR!!! HRConvert2-2, Diese Datei kann Ihre Anfrage nicht verarbeiten! Bitte senden Sie Ihre Datei stattdessen an convertCore.php!');
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