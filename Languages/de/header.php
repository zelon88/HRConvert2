<?php 
// / -----------------------------------------------------------------------------------
// / APPLICATION INFORMATION ...
// / HRConvert2, Copyright on 1/10/2023 by Justin Grimes, www.github.com/zelon88
// /
// / LICENSE INFORMATION ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / APPLICATION INFORMATION ...
// / This application is designed to provide a web-interface for converting file formats
// / on a server for users of any web browser without authentication.
// /
// / FILE INFORMATION
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
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = 'Konvertieren Sie alles!'; 
if (!isset($Font)) $Font = 'Arial'; ?>
<html>
  <head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="Resources/favicon.ico"/>
    <link rel="stylesheet" href="Resources/dropzone.css"/>
    <link rel="stylesheet" href="Resources/HRConvert2.css"/>
    <script type="text/javascript" src="Resources/HRC2-Functions.js"></script>
    <script type="text/javascript">var dropzoneText = 'Klicken, tippen oder legen Sie Dateien hier ab, um sie hochzuladen.';</script>
    <script type="text/javascript" src="Resources/dropzone.js"></script>
    <style>
      body {
        font-family: <?php echo $Font; ?>; }
        <?php if (isset($ButtonCode)) echo $ButtonCode; ?>
    </style>
    <title><?php echo $ApplicationName; ?> - <?php echo $ApplicationTitle; ?></title>
  </head>
  