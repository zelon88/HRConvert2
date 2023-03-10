<?php
// / -----------------------------------------------------------------------------------
// / APPLICATION INFORMATION ...
// / HRConvert2, Copyright on 3/9/2023 by Justin Grimes, www.github.com/zelon88
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
// / v3.1.9.6.
// / This file contains language specific GUI related text for performing file conversions.
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
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die('ERROR!!! HRConvert2-2, This file cannot process your request! Please submit your file to convertCore.php instead!');
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set variables.
$Alert = 'Cannot convert this file! Try changing the name.';
$Alert1 = 'Cannot perform a virus scan on this file!';
$Alert2 = 'File Link Copied to Clipboard!';
$Alert3 = 'Operation Failed!';
$FCPlural1 = 's';
$FCPlural2 = 's are';
if (!is_numeric($FileCount)) $FileCount = 'an unknown number of';
if ($FileCount == 1) {
  $FCPlural1 = '';
  $FCPlural2 = ' is'; }
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = 'Convert Anything!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / 
$Gui2Text1 = '
      <h3>File Conversion Options</h3>
      <p>You have uploaded '.$FileCount.' valid file'.$FCPlural1.' to '.$ApplicationName.'.</p> 
      <p>Your file'.$FCPlural2.' now ready to convert using the options below.</p>';
$Gui2Text2 = '
      <button id=\'scandocMoreOptionsButton\' name=\'scandocMoreOptionsButton\' class=\'info-button\' onclick="toggle_visibility(\'compressAllOptions\');">Bulk File Options</button>';
$Gui2Text3 = '
        <p><strong>Scan All Files For Viruses</strong></p>
        <p>Scan with ClamAV: <input type=\'checkbox\' id=\'clamscanall\' value=\'clamscanall\' name=\'clamScan\' checked></p>
        <p>Scan with ScanCore: <input type=\'checkbox\' id=\'scancoreall\' value=\'scancoreall\' name=\'phpavScan\' checked></p>
        <p><input type=\'submit\' id=\'scanAllButton\' name=\'scanAllButton\' class=\'info-button\' value=\'Scan All\' onclick="toggle_visibility(\'loadingCommandDiv\');"></p>';
$Gui2Text4 = 'Compress & Download All Files';
$Gui2Text5 = 'Download';
$Gui2Text6 = 'Share';
$Gui2Text7 = 'Close Share Options';
$Gui2Text8 = 'Virus Scan';
$Gui2Text9 = 'Close Virus Scan Options';
$Gui2Text10 = 'Archive';
$Gui2Text11 = 'Close Archive Options';
$Gui2Text12 = 'OCR';
$Gui2Text13 = 'Close OCR Options';
$Gui2Text14 = 'Convert';
$Gui2Text15 = 'Close Convert Options';
$Gui2Text16 = 'Archive This File';
$Gui2Text17 = 'Specify Filename: ';
$Gui2Text18 = 'Format';
$Gui2Text19 = 'Compress & Download';
$Gui2Text20 = '';
$Gui2Text21 = 'Close OCR Options';
$Gui2Text22 = '';
$Gui2Text23 = 'Close OCR Options';
$Gui2Text24 = '';
$Gui2Text25 = 'Close OCR Options';
$Gui2Text26 = '';
$Gui2Text27 = 'Close OCR Options';
$Gui2Text28 = '';
$Gui2Text29 = 'Close OCR Options';
$Gui2Text30 = '';
$Gui2Text31 = 'Close OCR Options';
$Gui2Text32 = '';
$Gui2Text33 = 'Close OCR Options';
$Gui2Text34 = '';
$Gui2Text35 = 'Close OCR Options';