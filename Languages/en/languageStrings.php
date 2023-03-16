<?php
// / -----------------------------------------------------------------------------------
// / APPLICATION INFORMATION ...
// / HRConvert2, Copyright on 3/15/2023 by Justin Grimes, www.github.com/zelon88
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
// / v3.1.9.7.
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
$LanguageStringsLoaded = TRUE;
if (!isset($CoreLoaded)) die('ERROR!!! HRConvert2-2, This file cannot process your request! Please submit your file to convertCore.php instead!');
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Check for required core variables.
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = 'Convert Anything!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set error related variables variables.

// / 'Cannot convert this file! Try changing the name.'
$Alert = 'Cannot convert this file! Try changing the name.';
// / 'Cannot perform a virus scan on this file!'
$Alert1 = 'Cannot perform a virus scan on this file!';
// / 'File Link Copied to Clipboard!'
$Alert2 = 'File Link Copied to Clipboard!';
// / 'Operation Failed!'
$Alert3 = 'Operation Failed!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set variables related to singular/plural files.
$FCPlural1 = 's';
$FCPlural2 = 's are';
if (!is_numeric($FileCount)) $FileCount = 'an unknown number of';
if ($FileCount == 1) {
  $FCPlural1 = '';
  $FCPlural2 = ' is'; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.

// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'File Conversion Options';
// / 'Bulk File Options'
$Gui2Text2 = 'Bulk File Options';
// / 'Scan All Files For Viruses'
$Gui2Text3 = 'Scan All Files For Viruses';
// / 'Compress & Download All Files'
$Gui2Text4 = 'Compress & Download All Files';
// / 'Download'
$Gui2Text5 = 'Download';
// / 'Share'
$Gui2Text6 = 'Share';
// / 'Close Share Options'
$Gui2Text7 = 'Close Share Options';
// / 'Virus Scan'
$Gui2Text8 = 'Virus Scan';
// / 'Close Virus Scan Options'
$Gui2Text9 = 'Close Virus Scan Options';
// / 'Archive'
$Gui2Text10 = 'Archive';
// / 'Close Archive Options'
$Gui2Text11 = 'Close Archive Options';
// / 'OCR'
$Gui2Text12 = 'OCR';
// / 'Close OCR Options'
$Gui2Text13 = 'Close OCR Options';
// / 'Convert'
$Gui2Text14 = 'Convert';
// / 'Close Convert Options'
$Gui2Text15 = 'Close Convert Options';
// / 'Archive This File'
$Gui2Text16 = 'Archive This File';
// / 'Specify Filename: '
$Gui2Text17 = 'Specify Filename: ';
// / 'Format'
$Gui2Text18 = 'Format';
// / 'Compress & Download'
$Gui2Text19 = 'Compress & Download';
// / 'Scan with ClamAV: '
$Gui2Text20 = 'Scan with ClamAV: ';
// / 'Scan with ScanCore: '
$Gui2Text21 = 'Scan with ScanCore: ';
// / 'Scan All'
$Gui2Text22 = 'Scan All';
// / 'Share This File'
$Gui2Text23 = 'Share This File';
// / 'Link Status: '
$Gui2Text24 = 'Link Status: ';
// / 'Not Generated'
$Gui2Text25 = 'Not Generated';
// / 'Generated'
$Gui2Text26 = 'Generated';
// / 'Clipboard Status: '
$Gui2Text27 = 'Clipboard Status: ';
// / 'Copied'
$Gui2Text28 = 'Copied';
// / 'File Link: '
$Gui2Text29 = 'File Link: ';
// / 'You have uploaded '.$FileCount.' valid file'.$FCPlural1.' to '.$ApplicationName.'.'
$Gui2Text30 = 'You have uploaded '.$FileCount.' valid file'.$FCPlural1.' to '.$ApplicationName.'.';
// / 'Your file'.$FCPlural2.' now ready to convert using the options below.'
$Gui2Text31 = 'Your file'.$FCPlural2.' now ready to convert using the options below.';
// / 'Generate Link & Copy to Clipboard'
$Gui2Text32 = 'Generate Link & Copy to Clipboard';
// / 'Generate Link'
$Gui2Text33 = 'Generate Link';
// / 'Scan This File For Viruses'
$Gui2Text34 = 'Scan This File For Viruses';
// / 'Scan File With ScanCore'
$Gui2Text35 = 'Scan File With ScanCore';
// / 'Scan File With ClamAV'
$Gui2Text36 = 'Scan File With ClamAV';
// / 'Scan File With ScanCore & ClamAV'
$Gui2Text37 = 'Scan File With ScanCore & ClamAV';
// / 'Perform Optical Character Recognition On This File'
$Gui2Text38 = 'Perform Optical Character Recognition On This File';
// / 'Method'
$Gui2Text39 = 'Method';
// / 'Simple'
$Gui2Text40 = 'Simple';
// / 'Advanced'
$Gui2Text41 = 'Advanced';
// / 'Convert This Archive'
$Gui2Text42 = 'Convert This Archive';
// / 'Convert This Spreadsheet'
$Gui2Text43 = 'Convert This Document';
// / 'Convert This Spreadsheet'
$Gui2Text44 = 'Convert This Spreadsheet';
// / 'Convert This Audio'
$Gui2Text45 = 'Convert This Audio';
// / 'Convert This Video'
$Gui2Text46 = 'Convert This Video';
// / 'Convert This Stream'
$Gui2Text47 = 'Convert This Stream';
// / Convert This 3D Model'
$Gui2Text48 = 'Convert This 3D Model';
// / 'Convert This Technical Drawing Or Vector File'
$Gui2Text49 = 'Convert This Technical Drawing Or Vector File';
// / 'Convert This Image'
$Gui2Text50 = 'Convert This Image';
// / 'Archive File'
$Gui2Text51 = 'Archive File';
// / 'Convert Into Document'
$Gui2Text52 = 'Convert Into Document';
// / 'Archive Files'
$Gui2Text53 = 'Archive Files';
// / 'Convert Document'
$Gui2Text54 = 'Convert Document';
// / 'Convert Spreadsheet'
$Gui2Text55 = 'Convert Spreadsheet';
// / 'Convert Presentation'
$Gui2Text56 = 'Convert Presentation';
// / 'Convert Audio'
$Gui2Text57 = 'Convert Audio';
// / 'Convert Video'
$Gui2Text58 = 'Convert Video';
// / 'Convert Video'
$Gui2Text59 = 'Convert Stream';
// / 'Convert Model'
$Gui2Text60 = 'Convert Model';
// / 'Convert Drawing'
$Gui2Text61 = 'Convert Drawing';
// / 'Convert Image'
$Gui2Text62 = 'Convert Image';
// / 'Width & Height'
$Gui2Text64 = 'Width & Height: ';
// / 'Rotate: '
$Gui2Text65 = 'Rotate: ';