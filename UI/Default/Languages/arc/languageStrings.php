<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRConvert2, Copyright on 8/17/2026 by Justin Grimes, www.github.com/zelon88
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
// / v3.7.4.
// / This file contains language specific GUI related text for performing file conversions.
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
$LanguageStringsLoaded = TRUE;
// / The version of this language pack for compatibility checking.
// / Compatibility check takes place in convertCore.php, buildGui() function.
$LanguageVersion = 'v3.7.4';
$LanguageVersion = ltrim($LanguageVersion, 'vV');
// / Set the reading direction for text on the page.
$GUIDirection = 'rtl';
// / Set the side of the page to float elements to.
$GUIAlignment = 'right';
// / Define an error message to display for if the core has not been loaded.
$CoreError = 'ܦܘܕܐ!!! HRConvert2-2, ܗܢܐ ܦܬܩܐ ܠܐ ܡܨܐ ܕܦܠܚ ܠܒܥܘܬܟ! ܒܒܥܘ ܕܫܕܪ ܠܦܬܩܟ ܠܕܘܟܬܐ ܕ-convertCore.php ܚܠܦ ܗܕܐ!';
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die($CoreError);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Check for required core variables.
if (!isset($Font)) $Font = 'Arial';
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = 'ܫܚܠܦ ܟܠ ܡܕܡ!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI Related Logic.
if (!is_numeric($FileCount)) $FileCount = 'ܡܢܝܢܐ ܠܐ ܝܕܝܥܐ ܕ';
$FCPlural1 = '̈ܐ ܬܪܝܨܐ';
$FCPlural2 = '̈ܐ ܕܝܠܟ ܥܬܝܕܝܢ ܐܢܘܢ ܗܫܐ';
if ($FileCount == 1) {
  $FCPlural1 = 'ܐ ܬܪܝܨܐ';
  $FCPlural2 = 'ܐ ܕܝܠܟ ܥܬܝܕ ܗܘ ܗܫܐ'; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Header Related Variables.
// / 'Click, Tap, or Drop files here to upload.'
$GuiHeaderText1 = 'ܢܩܘܫ، ܙܘܥ، ܐܘ ܐܪܡܐ ܦܬܩ̈ܐ ܗܪܟܐ ܠܡܣܩܘ.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - UI Selector Bar Related Variables.
// / These strings appear in the selector bar, which is present on both GUI1 & GUI2.
// / 'Language'
$GuiSelectorText1 = 'ܠܫܢܐ';
// / 'Color'
$GuiSelectorText2 = 'ܓܘܢܐ';
// / 'Interface'
$GuiSelectorText3 = 'ܚܙܘܐ';
// / 'Display language, color and interface options'
$GuiSelectorText4 = 'ܚܘܝ ܓܒܝܬܐ ܕܠܫܢܐ ܘܓܘܢܐ ܘܚܙܘܐ';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / Online File Converter, Extractor, Compressor'
$Gui1Text1 = 'ܡܫ推ܠܦܢܐ، ܡܦܩܢܐ، ܘܡܥܝܨܢܐ ܕܦܬܩ̈ܐ ܒܢܘܠܐ';
// / $ApplicationName.' is based off the open-source web-app <a href=\'https://github.com\'>HRConvert2</a> by <a href=\'https://github.com\'>Zelon88</a> that converts files without tracking users across the net or infringing on your intellectual property.'
$Gui1Text2 = $ApplicationName.' ܡܫܬܬܐܣ ܥܠ ܬܘܩܢܐ ܕܢܘܠܐ ܕܡܥܝܢܐ ܦܬܝܚܐ <a href=\'https://github.com\'>HRConvert2</a> ܕܥܒܝܕ ܡܢ <a href=\'https://github.com\'>Zelon88</a> ܕܡܫܚܠܦ ܦܬܩ̈ܐ ܕܠܐ ܥܩܒܐ ܕܡܦܠܚܢ̈ܐ ܒܐܣܪܐ ܐܘ ܚܒܠܘܬܐ ܕܩܢܝܢܐ ܪܕܝܢܝܐ ܕܝܠܟ.';
// / 'More Info ...'
$Gui1Text3 = 'ܝܬܝܪ ܝܕܥܬܐ ...';
// / 'Less Info ...'
$Gui1Text4 = 'ܒܨܝܪ ܝܕܥܬܐ ...';
// / 'All user-supplied data is erased automatically, so you don\'t need to worry about forfeiting your personal information or property while using our services.'
$Gui1Text5 = 'Inter ܠܗܘܢ ܢتܒ̈ܐ ܕܡܘܫܛܝܢ ܡܢ ܡܦܠܚܢܐ ܡܬܛܠܩܝܢ ܝܬܝܪܐܝܬ، ܗܟܢܐ ܠܐ ܙܕܩ ܠܟ ܕܬܨܦܐ ܥܠ ܐܒܕܢܐ ܕܝܕܥܬܐ ܕܝܠܢܝܬܐ ܐܘ ܩܢܝܢܐ ܕܝܠܟ ܟܕ ܡܦܠܚ ܐܢܬ ܬܫܡܫܬ̈ܐ ܕܝܠܢ.';
// / 'Currently '.$ApplicationName.' supports '.$SupportedFormatCount.' different file formats, including documents, spreadsheets, images, media, 3D models, CAD drawings, vector files, archives, disk images, & more.'
$Gui1Text6 = 'ܗܫܐ ܛܟܣܐ ܕ-'.$ApplicationName.' ܡܣܝܥ ܠ-'.$SupportedFormatCount.' ܫܘܚܠܦ̈ܐ ܡܫܚܠܦ̈ܐ ܕܦܬܩ̈ܐ، ܒܗܠܝܢ ܟܬܝܒܬ̈ܐ، ܦܬܘܪ̈ܐ ܕܚܘܫܒܢܐ، ܨܘܪܬ̈ܐ، ܡܕܝܐ، ܛܘܦܣ̈ܐ ܕܬܠܬܐ ܡܫܘܚܝܢ، ܪܫμ̈ܐ ܕ-CAD، ܦܬܩ̈ܐ ܘܩܛܘܪܝ̈ܐ، ܓܙ̈ܐ، ܨܘܪܬ̈ܐ ܕܕܝܣܩ̈ܐ، ܘܝܬܝܪ.';
// / 'View Supported Formats ...'
$Gui1Text7 = 'ܚܙܝ ܫܘܚܠܦ̈ܐ ܡܣܝܥ̈ܐ ...';
// / 'Hide Supported Formats ...'
$Gui1Text8 = 'ܛܫܝ ܫܘܚܠܦ̈ܐ ܡܣܝܥ̈ܐ ...';
// / 'Supported Formats'
$Gui1Text9 = 'ܫܘܚܠܦ̈ܐ ܡܣܝܥ̈ܐ';
// / 'Audio Formats'
$Gui1Text10 = 'ܫܘܚܠܦ̈ܐ ܕܩܠܐ';
// / 'Supports specific bitrate.'
$Gui1Text11 = 'ܡܣܝܥ ܠܩܨܐ ܕܢܬܒ̈ܐ ܕܝܠܢܝܐ.';
// / 'Video Formats'
$Gui1Text12 = 'ܫܘܚܠܦ̈ܐ ܕܚܙܘܐ';
// / 'Stream Formats'
$Gui1Text13 = 'ܫܘܚܠܦ̈ܐ ܕܪܕܝܬܐ';
// / 'Document Formats'
$Gui1Text14 = 'ܫܘܚܠܦ̈ܐ ܕܟܬܝܒܬ̈ܐ';
// / 'Spreadsheet Formats'
$Gui1Text15 = 'ܫܘܚܠܦ̈ܐ ܕܦܬܘܪ̈ܐ ܕܚܘܫܒܢܐ';
// / 'Presentation Formats'
$Gui1Text16 = 'ܫܘܚܠܦ̈ܐ ܕܚܘܘܝܐ';
// / 'Archive Formats'
$Gui1Text17 = 'ܫܘܚܠܦ̈ܐ ܕܓܙ̈ܐ';
// / 'Can convert between archive formats & disk image formats.'
$Gui1Text18 = 'ܡܨܐ ܠܡܫܚܠܦܘ ܒܝܢ ܫܘܚܠܦ̈ܐ ܕܓܙ̈ܐ ܓܒܝ̈ܐ ܘܫܘܚܠܦ̈ܐ ܕܨܘܪܬ̈ܐ ܕܕܝܣܩ̈ܐ.';
// / 'Image Formats'
$Gui1Text19 = 'ܫܘܚܠܦ̈ܐ ܕܨܘܪܬܐ';
// / 'Can convert pictures of documents to document formats.'
$Gui1Text20 = 'ܡܨܐ ܠܡܫܚܠܦܘ ܨܘܪܬ̈ܐ ܕܟܬܝܒܬ̈ܐ ܠܫܘܚܠܦ̈ܐ ܕܟܬܝܒܬ̈ܐ.';
// / 'Supports resize & rotate.'
$Gui1Text21 = 'ܡܣܝܥ ܠܫܘܚܠܦ ܡܫܘܚܬܐ ܘܚܘܕܪܐ.';
// / '3D Model Formats'
$Gui1Text22 = 'ܫܘܚܠܦ̈ܐ ܕܛܘܦܣ̈ܐ ܕܬܠܬܐ ܡܫܘܚܝܢ';
// / 'Drawing Formats'
$Gui1Text23 = 'ܫܘܚܠܦ̈ܐ ܕܪܫܡܐ';
// / 'Can convert drawing files to image formats.'
$Gui1Text24 = 'ܡܨܐ ܠܡܫܚܠܦܘ ܫܘܚܠܦ̈ܐ ܕܪܫܡܐ ܠܫܘܚܠܦ̈ܐ ܕܨܘܪܬܐ.';
// / 'OCR Support'
$Gui1Text25 = 'ܡܣܝܥܢܘܬܐ ܕ-OCR';
// / 'OCR Operations support the following input formats...'
$Gui1Text26 = 'ܦܘܠܚܢ̈ܐ ܕ-OCR ܡܣܝܥܝܢ ܠܫܘܚܠܦ̈ܐ ܕܡܥܠܬܐ ܗܠܝܢ...';
// / 'OCR Operations support the following output formats...'
$Gui1Text27 = 'ܦܘܠܚܢ̈ܐ ܕ-OCR ܡܣܝܥܝܢ ܠܫܘܚܠܦ̈ܐ ܕܡܦܩܬܐ ܗܠܝܢ...';
// / 'Select files by clicking, tapping, or dropping them into the box below.'
$Gui1Text28 = 'ܓܒܝ ܦܬܩ̈ܐ ܒܢܩܫܐ، ܙܘܥܐ، ܐܘ ܐܪܡܝܬܐ ܕܝܠܗܘܢ ܠܩܒܘܬܐ ܕܠܬܚܬ.';
// / 'Continue ...'
$Gui1Text29 = 'ܦܘܫ ܩܕܝܡܐ ...';
// / 'Can convert stream formats to video formats.'
$Gui1Text30 = 'ܡܨܐ ܠܡܫܚܠܦܘ ܫܘܚܠܦ̈ܐ ܕܪܕܝܬܐ ܠܫܘܚܠܦ̈ܐ ܕܚܙܘܐ.';
// / 'Subtitle Formats'
$Gui1Text31 = 'ܫܘܚܠܦ̈ܐ ܕܟܬܝܒܬܐ ܕܠܬܚﺘ';
// / 'OpenSCAD Formats'
$Gui1Text32 = 'ܫܘܚܠܦ̈ܐ ܕ-OpenSCAD';
// / 'Renders OpenSCAD source into 3D model formats.'
$Gui1Text33 = 'ܡܥܒܕ ܡܥܝܢܐ ܕ-OpenSCAD ܠܫܘܚܠܦ̈ܐ ܕܛܘܦܣܐ ܕܬܠܬܐ ܡܫܘܚܝܢ.';
// / 'File references inside uploaded sources are removed unless the server allows resolving them.'
$Gui1Text34 = 'ܐܣܘܪ̈ܐ ܕܦܬܩ̈ܐ ܒܓܘ ܡܥܝܢ̈ܐ ܕܐܣܩܘ ܡܫܬܩܠܝܢ ܐܠܐ ܐܢ ܫܒܩ ܡܫܡܫܢܐ ܠܡܫܪܐ ܐܢܘܢ.';
// / 'Delete every uploaded file & start a new session?'
// / Shown inside the start over panel on the upload page. That panel is only rendered
// / when the session already holds at least one file, so this is never shown to a first
// / time visitor who has nothing to lose.
$Gui1Text35 = 'ܛܠܘܩ ܟܠܗܘܢ ܦܬܩܐ ܕܐܣܩܘ ܘܫܪܐ ܡܘܬܒܐ ܚܕܬܐ؟';
// / 'Start Over'
// / Labels the control that opens the panel & the button inside it that confirms.
$Gui1Text36 = 'ܫܪܐ ܡܢ ܪܫܐ';
// / 'Refresh'
// / Alternate text for the refresh control on the upload page. That control is a glyph
// / with no text of its own, so this is the only description a screen reader has & the
// / only thing shown when a browser cannot render the glyph.
$Gui1Text37 = 'ܚܕܬ';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'ܓܘܒܝ̈ܐ ܕܫܘܚܠܦ ܦܬܩܐ';
// / 'Bulk File Options'
$Gui2Text2 = 'ܓܘܒܝ̈ܐ ܕܦܬܩ̈ܐ ܡܪܘܚܩ̈ܐ';
// / 'Scan All Files For Viruses'
$Gui2Text3 = 'ܒܨܝ ܟܠܗܘܢ ܦܬܩ̈ܐ ܠܒܝܪܘܣ̈ܐ';
// / 'Compress & Download All Files'
$Gui2Text4 = 'ܥܘܨ ܘܨܚܒ ܟܠܗܘܢ ܦܬܩ̈ܐ';
// / 'Download'
$Gui2Text5 = 'ܨܚܒ';
// / 'Share'
$Gui2Text6 = 'ܫܪܟ';
// / 'Close Share Options'
$Gui2Text7 = 'ܐܚܕ ܓܘܒܝ̈ܐ ܕܫܘܬܦܐ';
// / 'Virus Scan'
$Gui2Text8 = 'ܒܨܝܬܵܐ ܕܒܝܪܘܣ̈ܐ';
// / 'Close Virus Scan Options'
$Gui2Text9 = 'ܐܚܕ ܓܘܒܝ̈ܐ ܕܒܨܝܬܵܐ ܕܒܝܪܘܣ̈ܐ';
// / 'Archive'
$Gui2Text10 = 'ܓܙܐ';
// / 'Close Archive Options'
$Gui2Text11 = 'ܐܚܕ ܓܘܒܝ̈ܐ ܕܓܙܐ';
// / 'OCR'
$Gui2Text12 = 'OCR';
// / 'Close OCR Options'
$Gui2Text13 = 'ܐܚܕ ܓܘܒܝ̈ܐ ܕ-OCR';
// / 'Convert'
$Gui2Text14 = 'ܫܚܠܦ';
// / 'Close Convert Options'
$Gui2Text15 = 'ܐܚܕ ܓܘܒܝ̈ܐ ܕܫܘܚܠܦܐ';
// / 'Archive This File'
$Gui2Text16 = 'ܓܙܝ ܗܢܐ ܦܬܩܐ';
// / 'Specify Filename: '
$Gui2Text17 = 'ܚܬܡ ܫܡܐ ܕܦܬܩܐ: ';
// / 'Format'
$Gui2Text18 = 'ܫܘܚܠܦܐ';
// / 'Compress & Download'
$Gui2Text19 = 'ܥܘܨ ܘܨܚܒ';
// / 'Scan with ClamAV: '
$Gui2Text20 = 'ܒܨܝ ܒܝܕ ClamAV: ';
// / 'Scan with ScanCore: '
$Gui2Text21 = 'ܒܨܝ ܒܝܕ ScanCore: ';
// / 'Scan All'
$Gui2Text22 = 'ܒܨܝ ܟܠ';
// / 'Share This File'
$Gui2Text23 = 'ܫܪܟ ܗܢܐ ܦܬܩܐ';
// / 'Link Status: '
$Gui2Text24 = 'ܐܝܟܢܝܘܬܐ ܕܐܣܪܐ: ';
// / 'Not Generated'
$Gui2Text25 = 'ܠܐ ܒܪܝܐ';
// / 'Generated'
$Gui2Text26 = 'ܒܪܝܐ';
// / 'Clipboard Status: '
$Gui2Text27 = 'ܐܝܟܢܝܘܬܐ ܕܕܦܐ ܕܢܩܫܐ: ';
// / 'Copied'
$Gui2Text28 = 'ܡܘܥܬܩܐ';
// / 'File Link: '
$Gui2Text29 = 'ܐܣܪܐ ܕܦܬܩܐ: ';
// / 'You have uploaded '.$FileCount.' valid file'.$FCPlural1.' to '.$ApplicationName.'.'
$Gui2Text30 = 'ܐܣܩܬ '.$FileCount.' ܦܬܩ'.$FCPlural1.' ܠ- '.$ApplicationName.'.';
// / 'Your file'.$FCPlural2.' now ready to convert using the options below.'
$Gui2Text31 = 'ܦܬܩ'.$FCPlural2.' ܠܡܫܚܠܦܘ ܒܝܕ ܓܘܒܝ̈ܐ ܕܠܬܚܬ.';
// / 'Generate Link & Copy to Clipboard'
$Gui2Text32 = 'ܒܪܝ ܐܣܪܐ ܘܐܥܬܩ ܠܕܦܐ ܕܢܩܫܐ';
// / 'Generate Link'
$Gui2Text33 = 'ܒܪܝ ܐܣܪܐ';
// / 'Scan This File For Viruses'
$Gui2Text34 = 'ܒܨܝ ܗܢܐ ܦܬܩܐ ܠܒܝܪܘܣ̈ܐ';
// / 'Scan File With ScanCore'
$Gui2Text35 = 'ܒܨܝ ܦܬܩܐ ܒܝܕ ScanCore';
// / 'Scan File With ClamAV'
$Gui2Text36 = 'ܒܨܝ ܦܬܩܐ ܒܝܕ ClamAV';
// / 'Scan File With ScanCore & ClamAV'
$Gui2Text37 = 'ܒܨܝ ܦܬܩܐ ܒܝܕ ScanCore ܘ-ClamAV';
// / 'Perform Optical Character Recognition On This File'
$Gui2Text38 = 'ܥܒܕ ܝܕܥܢܘܬܐ ܕܐܬܘܬ̈ܐ (OCR) ܥܠ ܗܢܐ ܦܬܩܐ';
// / 'Method'
$Gui2Text39 = 'ܐܘܪܚܐ';
// / 'Simple'
$Gui2Text40 = 'ܦܫܝܛܬܐ';
// / 'Advanced'
$Gui2Text41 = 'ܡܛܘܪܬܐ';
// / 'Convert This Archive'
$Gui2Text42 = 'ܫܚܠܦ ܗܢܐ ܓܙܐ';
// / 'Convert This Document'
$Gui2Text43 = 'ܫܚܠف ܗܢܐ ܟܬܝܒܬܐ';
// / 'Convert This Spreadsheet'
$Gui2Text44 = 'ܫܚܠܦ ܗܢܐ ܦܵܬܘܿܪܵܐ ܕܚܘܼܫܒܵܢܵܐ';
// / 'Convert This Audio'
$Gui2Text45 = 'ܫܚܠܦ ܗܢܐ ܩܠܐ';
// / 'Convert This Video'
$Gui2Text46 = 'ܫܚܠܦ ܗܢܐ ܚܸܙܘܵܐ';
// / 'Convert This Stream'
$Gui2Text47 = 'ܫܚܠܦ ܗܢܐ ܪܕܝܬܐ';
// / Convert This 3D Model'
$Gui2Text48 = 'ܫܚܠܦ ܗܢܐ ܛܘܦܣܐ ܕܬܠܬܐ ܡܫܘܚܝܢ';
// / 'Convert This Technical Drawing Or Vector File'
$Gui2Text49 = 'ܫܚܠܦ ܗܢܐ ܪܫܡܐ ܝܕܥܬܢܝܐ ܐܘ ܦܬܩܐ ܘܩܛܘܪܝܐ';
// / 'Convert This Image'
$Gui2Text50 = 'ܫܚܠܦ ܗܢܐ ܨܘܪܬܐ';
// / 'Archive File'
$Gui2Text51 = 'ܓܙܝ ܦܬܩܐ';
// / 'Convert Into Document'
$Gui2Text52 = 'ܫܚܠܦ ܠܟܬܝܒܬܐ';
// / 'Archive Files'
$Gui2Text53 = 'ܓܙܝ ܦܬܩ̈ܐ';
// / 'Convert Document'
$Gui2Text54 = 'ܫܚܠܦ ܟܬܝܒܬܐ';
// / 'Convert Spreadsheet'
$Gui2Text55 = 'ܫܚܠܦ ܦܬܘܪܐ ܕܚܘܫܒܢܐ';
// / 'Convert Presentation'
$Gui2Text56 = 'ܫܚܠܦ ܚܘܘܝܐ';
// / 'Convert Audio'
$Gui2Text57 = 'ܫܚܠܦ ܩܠܐ';
// / 'Convert Video'
$Gui2Text58 = 'ܫܚܠܦ ܚܙܘܐ';
// / 'Convert Stream'
$Gui2Text59 = 'ܫܚܠܦ ܪܕܝܬܐ';
// / 'Convert Model'
$Gui2Text60 = 'ܫܚܠܦ ܛܘܦܣܐ';
// / 'Convert Drawing'
$Gui2Text61 = 'ܫܚܠܦ ܪܫܡܐ';
// / 'Convert Image'
$Gui2Text62 = 'ܫܚܠܦ ܨܘܪܬܐ';
// / 'Width & Height'
$Gui2Text64 = 'ܦܬܘܐ ܘܪܡܘܬܐ: ';
// / 'Rotate: '
$Gui2Text65 = 'ܚܕܪ: ';
// / 'Bitrate: '
$Gui2Text66 = 'ܩܨܐ ܕܢܬܒ̈ܐ: ';
// / 'Delete'
$Gui2Text67 = 'ܛܠܘܩ';
// / 'Close Delete Options'
$Gui2Text68 = 'ܐܚܕ ܓܘܒܝ̈ܐ ܕܛܠܩܐ';
// / 'Delete This File'
$Gui2Text69 = 'ܛܠܘܩ ܗܢܐ ܦܬܩܐ';
// / 'Confirm Delete'
$Gui2Text70 = 'ܚܬܡ ܛܠܩܐ';
// / 'Cannot convert this file! Try changing the name.'
$Gui2Text71 = 'ܠܹܐ ܡܵܨܹܐ ܠܡܫܲܚܠܘܼܦܹܐ ܗܵܢܵܐ ܦܸܬܩܵܐ! ܓܪܘܼܒ ܫܲܚܠܸܦ ܫܸܡܵܐ.';
// / 'Cannot perform a virus scan on this file!'
$Gui2Text72 = 'ܠܹܐ ܡܵܨܹܐ ܠܡܸܥܒܲܕ ܒܨܵܝܬܵܐ ܕܒܝܼܪܘܼܣܹܐ ܥܲܠ ܗܵܢܵܐ ܦܸܬܩܵܐ!';
// / 'File Link Copied to Clipboard!'
$Gui2Text73 = 'ܐܣܪܐ ܕܦܬܩܐ ܡܘܥܬܩܐ ܝܠܗ ܠܕܦܐ ܕܢܩܫܐ!';
// / 'Operation Failed!'
$Gui2Text74 = 'ܦܘܠܚܢܐ ܚܪܒܠܗ!';
// / 'Convert These Subtitles'
$Gui2Text75 = 'ܫܚܠܦ ܟܬܝܒܬܐ ܕܠܬܚܬ ܗܠܝܢ';
// / 'Convert Subtitles'
$Gui2Text76 = 'ܫܚܠܦ ܟܬܝܒܬܐ ܕܠܬܚܬ';
// / 'Convert This Presentation'
$Gui2Text77 = 'ܫܚܠܦ ܚܘܘܝܐ ܗܢܐ';
// / 'Convert This XPS File'
$Gui2Text78 = 'ܫܚܠܦ ܦܬܩܐ ܕ-XPS ܗܢܐ';
// / 'Render This OpenSCAD Model'
$Gui2Text79 = 'ܥܒܕ ܛܘܦܣܐ ܕ-OpenSCAD ܗܢܐ';
// / 'Render Model'
$Gui2Text80 = 'ܥܒܕ ܛܘܦܣܐ';
// / 'Convert This E-Book'
$Gui2Text81 = 'ܫܰܚܠܶܦ ܗܳܢܳܐ ܟܬܳܒ݂ܳܐ ܐܶܠܶܩܛܪܳܘܢܳܝܳܐ';
// / 'Convert E-Book'
$Gui2Text82 = 'ܫܰܚܠܶܦ ܟܬܳܒ݂ܳܐ ܐܶܠܶܩܛܪܳܘܢܳܝܳܐ';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - HRC2-Functions.js Related Variables.
// / These strings are used by the client side javascript library.
// / That file is static javascript & cannot read a PHP variable, so every string it needs
// / is passed to it as an argument by the PHP that calls it.
// / 'Your browser does not support copying to the clipboard!'
$GuiFunctionsText1 = 'ܠܵܘܚܵܐ ܕܚܙܵܝܬܵܐ ܕܝܼܵܘܟ݂ܘܿܢ ܠܹܐ ܡܣܲܝܥܵܐ ܠܐܸܥܬܵܩܵܐ ܠܕܲܦܵܐ ܕܢܩܵܫܵܐ!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy'
$GuiFooterText1 = 'ܚܙܝ <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>ܩܝܡܐ ܕܬܫܡܫܬܐ</a> ܘ<a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>ܕܝܠܢܝܘܬܐ ܕܦܘܪܩܢܐ</a> ܕܝܠܢ';
// / -----------------------------------------------------------------------------------
