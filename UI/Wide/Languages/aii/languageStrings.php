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
// / v3.8.2.
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
$CoreError = 'ܦܵܘܕܵܐ!!! HRConvert2-2، ܗܵܢܵܐ ܦܸܬܩܵܐ ܠܹܐ ܡܵܨܹܐ ܕܦܵܠܹܚ ܠܒܵܥܘܼܬܵܘܟ݂ܘܿܢ! ܒܒܵܥܘܼ ܫܲܕܸܪܘܼܢ ܦܸܬܩܵܘܟ݂ܘܿܢ ܠܕܘܼܟܬܵܐ ܕ-convertCore.php ܚܠܵܦ ܗܵܕܹܐ!';
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die($CoreError);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Check for required core variables.
if (!isset($Font)) $Font = 'Arial';
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = 'ܫܲܚܠܸܦ ܟܠ ܡܸܢܕܝܼ!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI Related Logic.
if (!is_numeric($FileCount)) $FileCount = 'ܡܸܢܝܵܢܵܐ ܠܵܐ ܝܕܝܼܥܵܐ ܕ';
$FCPlural1 = 'ܹܐ ܬܪܝܼܨܹܐ';
$FCPlural2 = 'ܹܐ ܕܝܼܵܘܟ݂ܘܿܢ ܝܼܠܵܗ̇ ܗܕܝܼܪܹܐ ܗܵܫܵܐ';
if ($FileCount == 1) {
  $FCPlural1 = 'ܵܐ ܬܪܝܼܨܵܐ';
  $FCPlural2 = 'ܵܐ ܕܝܼܵܘܟ݂ܘܿܢ ܝܼܠܹܗ ܗܕܝܼܪܵܐ ܗܵܫܵܐ'; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Header Related Variables.
// / 'Click, Tap, or Drop files here to upload.'
$GuiHeaderText1 = 'ܢܩܘܿܫܘܼܢ، ܙܘܼܥܘܼܢ، ܐܵܘ ܐܲܪܡܘܼܢ ܦܸܬܩܹܐ ܠܗܵܪܟܵܐ ܠܡܲܣܩܘܼ.';
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
$Gui1Text1 = 'ܡܫܲܚܠܦܵܢܵܐ، ܡܦܸܩܵܢܵܐ، ܘܡܥܝܼܨܵܢܵܐ ܕܦܸܬܩܹܐ ܒܢܸܘܠܵܐ';
// / $ApplicationName.' is based off the open-source web-app <a href=\'https://github.com\'>HRConvert2</a> by <a href=\'https://github.com\'>Zelon88</a> that converts files without tracking users across the net or infringing on your intellectual property.'
$Gui1Text2 = $ApplicationName.' ܟܹܐ ܡܸܫܬܲܬܸܣ ܥܲܠ ܬܘܼܩܵܢܵܐ ܕܢܸܘܠܵܐ ܕܡܲܥܝܵܢܵܐ ܦܬܝܼܚܵܐ <a href=\'https://github.com\'>HRConvert2</a> ܕܥܒ݂ܝܼܕܵܐ ܝܠܹܗ ܡܸܢ <a href=\'https://github.com\'>Zelon88</a> ܕܟܹܐ ܡܫܲܚܠܸܦ ܦܸܬܩܹܐ ܕܠܵܐ ܥܸܩܒܵܐ ܕܡܦܲܠܚܵܢܹܐ ܒܐܸܣܵܪܵܐ ܐܵܘ ܚܒܵܠܘܼܬܵܐ ܕܩܸܢܝܵܢܵܐ ܪܸܕܝܵܢܵܝܵܐ ܕܝܼܵܘܟ݂ܘܿܢ.';
// / 'More Info ...'
$Gui1Text3 = 'ܝܲܬܝܼܪ ܝܼܕܲܥܬܵܐ ...';
// / 'Less Info ...'
$Gui1Text4 = 'ܒܨܝܼܪ ܝܼܕܲܥܬܵܐ ...';
// / 'All user-supplied data is erased automatically, so you don\'t need to worry about forfeiting your personal information or property while using our services.'
$Gui1Text5 = 'ܟܠܗܘܿܢ ܢܵܬܘܿܙܹܐ ܕܡܘܼܫܸܛܹܐ ܡܸܢ ܡܦܲܠܚܵܢܵܐ ܟܹܐ ܛܵܠܩܝܼ ܝܵܬܝܼܪܵܐܝܼܬ݂، ܗܵܕܟ݂ܵܐ ܠܵܐ ܙܵܕܸܩ ܠܵܘܟ݂ܘܿܢ ܕܨܵܦܘܼܢ ܥܲܠ ܐܲܒ݂ܕܵܢܵܐ ܕܝܼܕܲܥܬܵܐ ܕܝܼܠܵܢܵܝܬܵܐ ܐܵܘ ܩܸܢܝܵܢܵܐ ܕܝܼܵܘܟ݂ܘܿܢ ܟܲܕ ܡܦܲܠܚܝܼܬܘܿܢ ܬܸܫܡܸܫܬܵܬܹ̈ܐ ܕܝܼܠܲܢ.';
// / 'Currently '.$ApplicationName.' supports '.$SupportedFormatCount.' different file formats, including documents, spreadsheets, images, media, 3D models, CAD drawings, vector files, archives, disk images, & more.'
$Gui1Text6 = 'ܗܵܫܵܐ ܛܲܟ݂ܣܵܐ ܕ-'.$ApplicationName.' ܟܹܐ ܡܣܲܝܸܥ ܠ-'.$SupportedFormatCount.' ܫܘܼܚܠܵܦܹܐ ܡܫܲܚܠܦܹܐ ܕܦܸܬܩܹܐ، ܒܗܸܠܹܝܢ ܟܬܝܼܒ݂ܵܬܹ̈ܐ، ܦܵܬܘܿܪܹܐ ܕܚܘܼܫܒܵܢܵܐ، ܨܘܼܪܵܬܹ̈ܐ، ܡܕܝܵܐ، ܛܘܼܦܸܣܹܐ ܕܬܠܵܬܵܐ ܡܫܘܼܚܝܼܢ، ܪܸܫܡܹܐ ܕ-CAD، ܦܸܬܩܹܐ ܘܩܸܛܘܿܪܝܹܐ، ܓܲܙܹܐ، ܨܘܼܪܵܬܹ̈ܐ ܕܕܝܼܣܩܹܐ، ܘܝܲܬܝܼܪ.';
// / 'View Supported Formats ...'
$Gui1Text7 = 'ܚܙܝܼ ܫܘܼܚܠܵܦܹܐ ܡܣܲܝܥܹܐ ...';
// / 'Hide Supported Formats ...'
$Gui1Text8 = 'ܛܫܝܼ ܫܘܼܚܠܵܦܹܐ ...';
// / 'Supported Formats'
$Gui1Text9 = 'ܫܘܼܚܠܵܦܹܐ ܡܣܲܝܥܹܐ';
// / 'Audio Formats'
$Gui1Text10 = 'ܫܘܼܚܠܵܦܹܐ ܕܩܵܠܵܐ';
// / 'Supports specific bitrate.'
$Gui1Text11 = 'ܟܹܐ ܡܣܲܝܸܥ ܠܦܸܨܠܵܐ ܕܢܵܬܘܿܙܹܐ (Bitrate) ܕܝܼܠܵܢܵܝܵܐ.';
// / 'Video Formats'
$Gui1Text12 = 'ܫܘܼܚܠܵܦܹܐ ܕܚܸܙܘܵܐ';
// / 'Stream Formats'
$Gui1Text13 = 'ܫܘܼܚܠܵܦܹܐ ܕܪܕܵܝܬܵܐ';
// / 'Document Formats'
$Gui1Text14 = 'ܫܘܼܚܠܵܦܹܐ ܕܟܬܝܼܒ݂ܵܬܹ̈ܐ';
// / 'Spreadsheet Formats'
$Gui1Text15 = 'ܫܘܼܚܠܵܦܹܐ ܕܦܵܬܘܿܪܹܐ ܕܚܘܼܫܒܵܢܵܐ';
// / 'Presentation Formats'
$Gui1Text16 = 'ܫܘܼܚܠܵܦܹܐ ܕܚܘܼܘܵܝܵܐ';
// / 'Archive Formats'
$Gui1Text17 = 'ܫܘܼܚܠܵܦܹܐ ܕܓܲܙܹܐ';
// / 'Can convert between archive formats & disk image formats.'
$Gui1Text18 = 'ܟܹܐ ܡܵܨܹܐ ܠܡܫܲܚܠܘܼܦܹܐ ܒܹܝܢ ܫܘܼܚܠܵܦܹܐ ܕܓܲܙܹܐ ܓܘܼܒܝܹܐ ܘܫܘܼܚܠܵܦܹܐ ܕܨܘܼܪܵܬܹ̈ܐ ܕܕܝܼܣܩܹܐ.';
// / 'Image Formats'
$Gui1Text19 = 'ܫܘܼܚܠܵܦܹܐ ܕܨܘܼܪܬܵܐ';
// / 'Can convert pictures of documents to document formats.'
$Gui1Text20 = 'ܟܹܐ ܡܵܨܹܐ ܠܡܫܲܚܠܘܼܦܹܐ ܨܘܼܪܵܬܹ̈ܐ ܕܟܬܝܼܒ݂ܵܬܹ̈ܐ ܠܫܘܼܚܠܵܦܹܐ ܕܟܬܝܼܒ݂ܵܬܹ̈ܐ.';
// / 'Supports resize & rotate.'
$Gui1Text21 = 'ܟܹܐ ܡܣܲܝܸܥ ܠܫܘܼܚܠܵܦ ܡܫܘܼܚܬܵܐ ܘܚܘܼܕܪܵܐ.';
// / '3D Model Formats'
$Gui1Text22 = 'ܫܘܼܚܠܵܦܹܐ ܕܛܘܼܦܸܣܹܐ ܕܬܠܵܬܵܐ ܡܫܘܼܚܝܼܢ';
// / 'Drawing Formats'
$Gui1Text23 = 'ܫܘܼܚܠܵܦܹܐ ܕܪܸܫܡܵܐ';
// / 'Can convert drawing files to image formats.'
$Gui1Text24 = 'ܟܹܐ ܡܵܨܹܐ ܠܡܫܲܚܠܘܼܦܹܐ ܦܸܬܩܹܐ ܕܪܸܫܡܵܐ ܠܫܘܼܚܠܵܦܹܐ ܕܨܘܼܪܬܵܐ.';
// / 'OCR Support'
$Gui1Text25 = 'ܡܣܲܝܥܵܢܘܼܬܵܐ ܕ-OCR';
// / 'OCR Operations support the following input formats...'
$Gui1Text26 = 'ܦܘܼܠܚܵܢܹܐ ܕ-OCR ܟܹܐ ܡܣܲܝܥܝܼ ܠܫܘܼܚܠܵܦܹܐ ܕܡܲܥܠܬܵܐ ܗܠܹܝܢ...';
// / 'OCR Operations support the following output formats...'
$Gui1Text27 = 'ܦܘܼܠܚܵܢܹܐ ܕ-OCR ܟܹܐ ܡܣܲܝܥܝܼ ܠܫܘܼܚܠܵܦܹܐ ܕܡܲܦܩܬܵܐ ܗܠܹܝܢ...';
// / 'Select files by clicking, tapping, or dropping them into the box below.'
$Gui1Text28 = 'ܓܒܝܼ ܦܸܬܩܹܐ ܒܢܩܵܫܵܐ، ܙܘܼܥܵܐ، ܐܵܘ ܐܲܪܡܝܼܬܵܐ ܕܝܼܠܹܗܘܿܢ ܠܩܒܘܼܬܵܐ ܕܠܬܸܚܬ.';
// / 'Continue ...'
$Gui1Text29 = 'ܦܘܼܫ ܩܲܕܝܼܡܵܐ ...';
// / 'Can convert stream formats to video formats.'
$Gui1Text30 = 'ܟܹܐ ܡܵܨܹܐ ܠܡܫܲܚܠܘܼܦܹܐ ܫܘܼܚܠܵܦܹܐ ܕܪܕܵܝܬܵܐ ܠܫܘܼܚܠܵܦܹܐ ܕܚܸܙܘܵܐ.';
// / 'Subtitle Formats'
$Gui1Text31 = 'ܫܘܼܚܠܵܦܹܐ ܕܟܬܝܼܒ݂ܬܵܐ ܕܠܬܸܚܬ';
// / 'OpenSCAD Formats'
$Gui1Text32 = 'ܫܘܼܚܠܵܦܹܐ ܕ-OpenSCAD';
// / 'Renders OpenSCAD source into 3D model formats.'
$Gui1Text33 = 'ܟܹܐ ܡܥܲܒܸܕ ܡܲܥܝܵܢܵܐ ܕ-OpenSCAD ܠܫܘܼܚܠܵܦܹܐ ܕܛܘܼܦܸܣܵܐ ܕܬܠܵܬܵܐ ܡܫܘܼܚܝܼܢ.';
// / 'File references inside uploaded sources are removed unless the server allows resolving them.'
$Gui1Text34 = 'ܐܸܣܵܪܹܐ ܕܦܸܬܩܹܐ ܒܓܵܘ ܡܲܥܝܵܢܹܐ ܕܐܲܣܩܘܼ ܟܹܐ ܡܸܫܬܲܩܠܝܼ ܐܸܠܵܐ ܐܸܢ ܫܵܒܸܩ ܡܫܲܡܫܵܢܵܐ ܠܡܸܫܪܵܐ ܐܸܢܘܿܢ.';
// / 'Delete every uploaded file & start a new session?'
// / Shown inside the start over panel on the upload page. That panel is only rendered
// / when the session already holds at least one file, so this is never shown to a first
// / time visitor who has nothing to lose.
$Gui1Text35 = 'ܛܠܘܿܩ ܟܠܗܘܿܢ ܦܸܬܩܹܐ ܕܐܲܣܩܘܼ ܘܫܲܪܹܐ ܡܵܘܬܒ݂ܵܐ ܚܲܕ݂ܬܵܐ؟';
// / 'Start Over'
// / Labels the control that opens the panel & the button inside it that confirms.
$Gui1Text36 = 'ܫܲܪܹܐ ܡܸܢ ܪܹܫܵܐ';
// / 'Refresh'
// / Alternate text for the refresh control on the upload page. That control is a glyph
// / with no text of its own, so this is the only description a screen reader has & the
// / only thing shown when a browser cannot render the glyph.
$Gui1Text37 = 'ܚܲܕܸܬ';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'ܓܘܼܒܵܝܹܐ ܕܫܘܼܚܠܵܦ ܦܸܬܩܹܐ';
// / 'Bulk File Options'
$Gui2Text2 = 'ܓܘܼܒܵܝܹܐ ܕܦܸܬܩܹܐ ܡܪܘܼܚܩܹܐ';
// / 'Scan All Files For Viruses'
$Gui2Text3 = 'ܒܨܝܼ ܟܠܗܘܿܢ ܦܸܬܩܹܐ ܠܒܝܼܪܘܼܣܹܐ';
// / 'Compress & Download All Files'
$Gui2Text4 = 'ܥܘܼܨ ܘܨܵܚܸܒ ܟܠܗܘܿܢ ܦܸܬܩܹܐ';
// / 'Download'
$Gui2Text5 = 'ܨܵܚܸܒ';
// / 'Share'
$Gui2Text6 = 'ܫܵܪܸܟ';
// / 'Close Share Options'
$Gui2Text7 = 'ܐܲܚܸܕ ܓܘܼܒܵܝܹܐ ܕܫܘܼܬܵܦܵܐ';
// / 'Virus Scan'
$Gui2Text8 = 'ܒܨܵܝܬܵܐ ܕܒܝܼܪܘܼܣܹܐ';
// / 'Close Virus Scan Options'
$Gui2Text9 = 'ܐܲܚܸܕ ܓܘܼܒܵܝܹܐ ܕܒܨܵܝܬܵܐ ܕܒܝܼܪܘܼܣܹܐ';
// / 'Archive'
$Gui2Text10 = 'ܓܲܙܵܐ';
// / 'Close Archive Options'
$Gui2Text11 = 'ܐܲܚܸܕ ܓܘܼܒܵܝܹܐ ܕܓܲܙܵܐ';
// / 'OCR'
$Gui2Text12 = 'OCR';
// / 'Close OCR Options'
$Gui2Text13 = 'ܐܲܚܸܕ ܓܘܼܒܵܝܹܐ ܕ-OCR';
// / 'Convert'
$Gui2Text14 = 'ܫܲܚܠܸܦ';
// / 'Close Convert Options'
$Gui2Text15 = 'ܐܲܚܸܕ ܓܘܼܒܵܝܹܐ ܕܫܘܼܚܠܵܦܵܐ';
// / 'Archive This File'
$Gui2Text16 = 'ܓܙܝܼ ܗܵܢܵܐ ܦܸܬܩܵܐ';
// / 'Specify Filename: '
$Gui2Text17 = 'ܚܲܬܸܡ ܫܸܡܵܐ ܕܦܸܬܩܵܐ: ';
// / 'Format'
$Gui2Text18 = 'ܫܘܼܚܠܵܦܵܐ';
// / 'Compress & Download'
$Gui2Text19 = 'ܥܘܼܨ ܘܨܵܚܸܒ';
// / 'Scan with ClamAV: '
$Gui2Text20 = 'ܒܨܝܼ ܒܝܲܕ ClamAV: ';
// / 'Scan with ScanCore: '
$Gui2Text21 = 'Ref ܒܝܲܕ ScanCore: ';
// / 'Scan All'
$Gui2Text22 = 'ܒܨܝܼ ܟܠ';
// / 'Share This File'
$Gui2Text23 = 'ܫܵܪܸܟ ܗܵܢܵܐ ܦܸܬܩܵܐ';
// / 'Link Status: '
$Gui2Text24 = 'ܐܲܝܟܲܢܵܝܘܼܬܵܐ ܕܐܸܣܵܪܵܐ: ';
// / 'Not Generated'
$Gui2Text25 = 'ܠܵܐ ܒܪܝܼܵܐ';
// / 'Generated'
$Gui2Text26 = 'ܒܪܝܼܵܐ';
// / 'Clipboard Status: '
$Gui2Text27 = 'ܐܲܝܟܲܢܵܝܘܼܬܵܐ ܕܕܲܦܵܐ ܕܢܩܵܫܵܐ: ';
// / 'Copied'
$Gui2Text28 = 'ܡܘܼܥܬܸܩܵܐ';
// / 'File Link: '
$Gui2Text29 = 'ܐܸܣܵܪܵܐ ܕܦܸܬܩܵܐ: ';
// / 'You have uploaded '.$FileCount.' valid file'.$FCPlural1.' to '.$ApplicationName.'.'
$Gui2Text30 = 'ܐܲܣܩܘܼܬܘܿܢ '.$FileCount.' ܦܸܬܩܵܐ'.$FCPlural1.' ܠ- '.$ApplicationName.'.';
// / 'Your file'.$FCPlural2.' now ready to convert using the options below.'
$Gui2Text31 = 'ܦܸܬܩܵܐ'.$FCPlural2.' ܠܡܫܲܚܠܘܼܦܹܐ ܒܝܲܕ ܓܘܼܒܵܝܹܐ ܕܠܬܸܚܬ.';
// / 'Generate Link & Copy to Clipboard'
$Gui2Text32 = 'ܒܪܝܼ ܐܸܣܵܪܵܐ ܘܲܐܥܬܸܩ ܠܕܲܦܵܐ ܕܢܩܵܫܵܐ';
// / 'Generate Link'
$Gui2Text33 = 'ܒܪܝܼ ܐܸܣܵܪܵܐ';
// / 'Scan This File For Viruses'
$Gui2Text34 = 'ܒܨܝܼ ܗܵܢܵܐ ܦܸܬܩܵܐ ܠܒܝܼܪܘܼܣܹܐ';
// / 'Scan File With ScanCore'
$Gui2Text35 = 'ܒܨܝܼ ܦܸܬܩܵܐ ܒܝܲܕ ScanCore';
// / 'Scan File With ClamAV'
$Gui2Text36 = 'ܒܨܝܼ ܦܸܬܩܵܐ ܒܝܲܕ ClamAV';
// / 'Scan File With ScanCore & ClamAV'
$Gui2Text37 = 'ܒܨܝܼ ܦܸܬܩܵܐ ܒܝܲܕ ScanCore ܘ-ClamAV';
// / 'Perform Optical Character Recognition On This File'
$Gui2Text38 = 'ܥܒܸܕ ܝܵܕܥܵܢܘܼܬܵܐ ܕܐܵܬܘܵܬܹ̈ܐ (OCR) ܥܲܠ ܗܵܢܵܐ ܦܸܬܩܵܐ';
// / 'Method'
$Gui2Text39 = 'ܐܘܼܪܚܵܐ';
// / 'Simple'
$Gui2Text40 = 'ܦܫܝܼܛܬܵܐ';
// / 'Advanced'
$Gui2Text41 = 'ܡܛܲܘܲܪܬܵܐ';
// / 'Convert This Archive'
$Gui2Text42 = 'ܫܲܚܠܸܦ ܗܵܢܵܐ ܓܲܙܵܐ';
// / 'Convert This Document'
$Gui2Text43 = 'ܫܲܚܠܸܦ ܗܵܢܵܐ ܟܬܝܼܒ݂ܬܵܐ';
// / 'Convert This Spreadsheet'
$Gui2Text44 = 'ܫܲܚܠܸܦ ܗܵܢܵܐ ܦܵܬܘܿܪܵܐ ܕܚܘܼܫܒܵܢܵܐ';
// / 'Convert This Audio'
$Gui2Text45 = 'ܫܲܚܠܸܦ ܗܵܢܵܐ ܩܵܠܵܐ';
// / 'Convert This Video'
$Gui2Text46 = 'ܫܲܚܠܸܦ ܗܵܢܵܐ ܚܸܙܘܵܐ';
// / 'Convert This Stream'
$Gui2Text47 = 'ܫܲܚܠܸܦ ܗܵܢܵܐ ܪܕܵܝܬܵܐ';
// / Convert This 3D Model'
$Gui2Text48 = 'ܫܲܚܠܸܦ ܗܵܢܵܐ ܛܘܼܦܸܣܵܐ ܕතܠܵܬܵܐ ܡܫܘܼܚܝܼܢ';
// / 'Convert This Technical Drawing Or Vector File'
$Gui2Text49 = 'ܫܲܚܠܸܦ ܗܵܢܵܐ ܪܸܫܡܵܐ ܝܕܲܥܬܢܵܝܵܐ ܐܵܘ ܦܸܬܩܵܐ ܘܩܛܘܿܪܝܵܐ';
// / 'Convert This Image'
$Gui2Text50 = 'ܫܲܚܠܸܦ ܗܵܢܵܐ ܨܘܼܪܬܵܐ';
// / 'Archive File'
$Gui2Text51 = 'ܓܙܝܼ ܦܸܬܩܵܐ';
// / 'Convert Into Document'
$Gui2Text52 = 'ܫܲܚܠܸܦ ܠܟܬܝܼܒ݂ܬܵܐ';
// / 'Archive Files'
$Gui2Text53 = 'ܓܙܝܼ ܦܸܬܩܹܐ';
// / 'Convert Document'
$Gui2Text54 = 'ܫܲܚܠܸܦ ܟܬܝܼܒ݂ܬܵα';
// / 'Convert Spreadsheet'
$Gui2Text55 = 'ܫܲܚܠܸܦ ܦܵܬܘܿܪܵܐ ܕܚܘܼܫܒܵܢܵܐ';
// / 'Convert Presentation'
$Gui2Text56 = 'ܫܲܚܠܸܦ ܚܘܼܘܵܝܵܐ';
// / 'Convert Audio'
$Gui2Text57 = 'ܫܲܚܠܸܦ ܩܵܠܵܐ';
// / 'Convert Video'
$Gui2Text58 = 'ܫܲܚܠܸܦ ܚܸܙܘܵܐ';
// / 'Convert Stream'
$Gui2Text59 = 'ܫܲܚܠܸܦ ܪܕܵܝܬܵܐ';
// / 'Convert Model'
$Gui2Text60 = 'ܫܲܚܠܸܦ ܛܘܼܦܸܣܵܐ';
// / 'Convert Drawing'
$Gui2Text61 = 'ܫܲܚܠܸܦ ܪܸܫܡܵܐ';
// / 'Convert Image'
$Gui2Text62 = 'ܫܲܚܠܸܦ ܨܘܼܪܬܵܐ';
// / 'Width & Height'
$Gui2Text64 = 'ܦܸܬܘܵܐ ܘܪܵܡܘܼܬܵܐ: ';
// / 'Rotate: '
$Gui2Text65 = 'ܚܕܸܪ: ';
// / 'Bitrate: '
$Gui2Text66 = 'ܦܸܨܠܵܐ ܕܢܵܬܘܿܙܹܐ: ';
// / 'Delete'
$Gui2Text67 = 'ܛܠܘܿܩ';
// / 'Close Delete Options'
$Gui2Text68 = 'ܐܲܚܸܕ ܓܘܼܒܵܝܹܐ ܕܛܠܵܩܵܐ';
// / 'Delete This File'
$Gui2Text69 = 'ܛܠܘܿܩ ܗܵܢܵܐ ܦܸܬܩܵܐ';
// / 'Confirm Delete'
$Gui2Text70 = 'ܚܲܬܸܡ ܛܠܵܩܵܐ';
// / 'Cannot convert this file! Try changing the name.'
$Gui2Text71 = 'ܠܹܐ ܡܵܨܹܐ ܠܡܫܲܚܠܘܼܦܹܐ ܗܵܢܵܐ ܦܸܬܩܵܐ! ܓܪܘܼܒ ܫܲܚܠܸܦ ܫܸܡܵܐ.';
// / 'Cannot perform a virus scan on this file!'
$Gui2Text72 = 'ܠܹܐ ܡܵܨܹܐ ܠܡܸܥܒܲܕ ܒܨܵܝܬܵܐ ܕܒܝܼܪܘܼܣܹܐ ܥܲܠ ܗܵܢܵܐ ܦܸܬܩܵܐ!';
// / 'File Link Copied to Clipboard!'
$Gui2Text73 = 'ܐܸܣܵܪܵܐ ܕܦܸܬܩܵܐ ܡܘܼܥܬܸܩܵܐ ܝܠܹܗ ܠܕܲܦܵܐ ܕܢܩܵܫܵܐ!';
// / 'Operation Failed!'
$Gui2Text74 = 'ܦܘܼܠܚܵܢܵܐ ܚܪܸܒ݂ܠܹܗ!';
// / 'Convert These Subtitles'
$Gui2Text75 = 'ܫܲܚܠܸܦ ܟܬܝܼܒ݂ܬܵܐ ܕܠܬܸܚܬ ܗܠܹܝܢ';
// / 'Convert Subtitles'
$Gui2Text76 = 'ܫܲܚܠܸܦ ܟܬܝܼܒ݂ܬܵܐ ܕܠܬܸܚܬ';
// / 'Convert This Presentation'
$Gui2Text77 = 'ܫܲܚܠܸܦ ܚܘܼܘܵܝܵܐ ܗܵܢܵܐ';
// / 'Convert This XPS File'
$Gui2Text78 = 'ܫܲܚܠܸܦ ܦܸܬܩܵܐ ܕ-XPS ܗܵܢܵܐ';
// / 'Render This OpenSCAD Model'
$Gui2Text79 = 'ܥܒܸܕ ܛܘܼܦܸܣܵܐ ܕ-OpenSCAD ܗܵܢܵܐ';
// / 'Render Model'
$Gui2Text80 = 'ܥܒܸܕ ܛܘܼܦܸܣܵܐ';
// / 'Convert This E-Book'
$Gui2Text81 = 'ܫܲܚܠܸܦ ܐܵܗܵܐ ܟܬܵܒ݂ܵܐ ܐܸܠܸܩܛܪܘܿܢܵܝܵܐ';
// / 'Convert E-Book'
$Gui2Text82 = 'ܫܲܚܠܸܦ ܟܬܵܒ݂ܵܐ ܐܸܠܸܩܛܪܘܿܢܵܝܵܐ';
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
$GuiFooterText1 = 'ܚܙܝܼ <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>ܩܝܵܡܹܐ ܕܬܸܫܡܸܫܬܵܐ</a> ܘ<a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>ܕܝܼܠܵܢܵܝܘܼܬܵܐ ܕܦܘܼܪܩܵܢܵܐ</a> ܕܝܼܠܲܢ';
// / -----------------------------------------------------------------------------------
