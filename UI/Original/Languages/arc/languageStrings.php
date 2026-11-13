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
// / v3.8.3.
// / This file contains language specific GUI related text for performing file conversions.
// /
// / HARDWARE REQUIREMENTS ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / DEPENDENCY REQUIREMENTS ...
// / The authoritative list of dependencies is Resources/Contract/depends.php & nothing else.
// / Run  php convertCore.php -v  to see what this installation has.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set a flag to tell that the UI has been displayed.
$LanguageStringsLoaded = TRUE;
// / The version of this language pack for compatibility checking.
// / Compatibility check takes place in convertCore.php, buildGui() function.
$LanguageVersion = 'v4.1.8';
$LanguageVersion = ltrim($LanguageVersion, 'vV');
// / Set the reading direction for text on the page.
$GUIDirection = 'rtl';
// / Set the side of the page to float elements to.
$GUIAlignment = 'right';
// / Which end of a dropdown the arrow sits at, as a percentage across the control.
// / This pack knows its own reading direction & is therefore the right place to decide.
// / A reader finishes at the left of a right to left line, so the arrow belongs there.
// / An arrow pinned to the right edge sits at the START of the box in this language &
// / reads as a stray icon rather than as the thing that opens the list.
$SelectArrowSide = ($GUIAlignment === 'right') ? '4%' : '96%';
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
// / 'Online File Converter, Extractor, Compressor'
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
// / 'Convert This 3D Model'
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
// / 'Playlist source URL, only needed if the playlist uses relative paths'
$Gui2Text83 = 'ܐܘܪܚܐ ܕܡܒܘܥܐ ܕܠܝܣܬܐ، ܣܢܝܩܐ ܐܢ ܠܝܣܬܐ ܟܡܚܫܚܐ ܐܘܪܚܬܐ ܢܣܝܒܬܐ';
// / 'Download A File From A URL'
$Gui2Text84 = 'ܡܚܬ ܦܝܠܐ ܡܢ ܐܘܪܚܐ';
// / 'Fetch'
$Gui2Text85 = 'ܡܝܬܐ';
// / 'Identify'
$Gui2Text86 = 'ܦܪܘܫ';
// / 'Close Identify Options'
$Gui2Text87 = 'ܐܚܕ ܓܘܒܝ̈ܐ ܕܦܘܪܫܢܐ';
// / 'Identify This File'
$Gui2Text88 = 'ܦܪܘܫ ܗܢܐ ܦܬܩܐ';
// / 'Size: '
$Gui2Text89 = 'ܪܒܘܬܐ: ';
// / 'Modified: '
$Gui2Text90 = 'ܫܘܚܠܦܐ ܐܚܪܝܐ: ';
// / 'Type: '
$Gui2Text91 = 'ܐܕܫܐ: ';
// / 'MIME Type: '
$Gui2Text92 = 'ܐܕܫܐ ܕ-MIME: ';
// / 'Character Set: '
$Gui2Text93 = 'ܛܘܟܣܐ ܕܐܬܘ̈ܬܐ: ';
// / 'Extension Matches Content: '
$Gui2Text94 = 'ܫܘܠܡܐ ܕܫܡܐ ܫܘܐ ܠܡܕܡ ܕܒܓܘܗ: ';
// / 'Details: '
$Gui2Text95 = 'ܦܘܫܩ̈ܐ: ';
// / 'Notes: '
$Gui2Text96 = 'ܥܘܗܕܢ̈ܐ: ';
// / 'Save report as file'
$Gui2Text97 = 'ܢܛܘܪ ܡܘܕܥܢܘܬܐ ܐܝܟ ܦܬܩܐ';
// / 'Yes'
$Gui2Text100 = 'ܐܝܢ';
// / 'No'
$Gui2Text101 = 'ܠܐ';
// / 'Unknown'
$Gui2Text102 = 'ܠܐ ܝܕܝܥܐ';
// / 'Cannot identify this file!'
$Gui2Text103 = 'ܠܐ ܡܨܐ ܠܡܦܪܫ ܗܢܐ ܦܬܩܐ!';
// / 'Sort By: '
$Gui2Text104 = 'ܛܟܣ ܒܝܕ: ';
// / 'Name'
$Gui2Text105 = 'ܫܡܐ';
// / 'Size'
$Gui2Text106 = 'ܪܒܘܬܐ';
// / 'Date'
$Gui2Text107 = 'ܙܒܢܐ';
// / 'Type'
$Gui2Text108 = 'ܐܕܫܐ';
// / The words in braces, such as {fit} & {count}, are filled in by the page. Each translation keeps every one of them.
// / 'Select'
$Gui2Text109 = 'ܓܒܝ';
// / 'Select All'
$Gui2Text110 = 'ܓܒܝ ܟܠ';
// / 'Select None'
$Gui2Text111 = 'ܫܪܝ ܓܒܝܬܐ';
// / 'Select By Type'
$Gui2Text112 = 'ܓܒܝ ܒܝܕ ܐܕܫܐ';
// / 'Selected: '
$Gui2Text113 = 'ܓܒܝ̈ܐ: ';
// / 'Documents'
$Gui2Text114 = 'ܟܬܝܒܬ̈ܐ';
// / 'Spreadsheets'
$Gui2Text115 = 'ܦܬܘܪ̈ܐ ܕܚܘܫܒܢܐ';
// / 'Presentations'
$Gui2Text116 = 'ܚܘܘܝ̈ܐ';
// / 'Images'
$Gui2Text117 = 'ܨܘܪ̈ܬܐ';
// / 'Audio'
$Gui2Text118 = 'ܩܠܐ';
// / 'Video'
$Gui2Text119 = 'ܚܙܘܐ';
// / 'Archives'
$Gui2Text120 = 'ܓܙ̈ܐ';
// / 'E-Books'
$Gui2Text121 = 'ܟܬܒ̈ܐ ܐܠܩܛܪܘܢܝ̈ܐ';
// / '3D Models'
$Gui2Text122 = 'ܛܘܦܣ̈ܐ ܕܬܠܬܐ ܡܫܘܚܝܢ';
// / 'Drawings'
$Gui2Text123 = 'ܪ̈ܫܡܐ';
// / 'Vector Images'
$Gui2Text124 = 'ܨܘܪ̈ܬܐ ܕܒܩܛܘܪ';
// / 'Subtitles'
$Gui2Text125 = 'ܟܬܝܒܬܐ ܕܠܬܚܬ';
// / 'OpenSCAD Files'
$Gui2Text126 = 'ܦܬܩ̈ܐ ܕ-OpenSCAD';
// / 'Streams'
$Gui2Text127 = 'ܪ̈ܕܝܬܐ';
// / 'Workflows'
$Gui2Text128 = 'ܡܪ̈ܕܝܬܐ ܕܦܘܠܚܢܐ';
// / 'Selected Files'
$Gui2Text129 = 'ܦܬܩ̈ܐ ܓܒܝ̈ܐ';
// / 'Select files in the list below.'
$Gui2Text130 = 'ܓܒܝ ܦܬܩ̈ܐ ܒܪܫܝܡܬܐ ܕܠܬܚܬ.';
// / 'Select By Name: '
$Gui2Text131 = 'ܓܒܝ ܒܝܕ ܫܡܐ: ';
// / 'Use * and ? as wildcards, or write a regular expression between slashes.'
$Gui2Text132 = 'ܦܠܘܚ ܒ- * ܘ- ? ܐܝܟ ܐܬܘ̈ܬܐ ܕܚܘܠܦܐ، ܐܘ ܟܬܘܒ ܡܡܠܐ ܩܢܘܢܝܐ (regex) ܒܝܬ ܬܪܝܢ ܣܪ̈ܛܐ ܣܡܝܟ̈ܐ.';
// / 'Workflow'
$Gui2Text133 = 'ܡܪܕܝܬܐ ܕܦܘܠܚܢܐ';
// / 'Choose a workflow'
$Gui2Text134 = 'ܓܒܝ ܡܪܕܝܬܐ ܕܦܘܠܚܢܐ';
// / 'Run'
$Gui2Text135 = 'ܫܪܝ';
// / 'Stop'
$Gui2Text136 = 'ܟܠܝ';
// / 'Selected files that can be used: {fit} of {count}.'
$Gui2Text137 = 'ܦܬܩ̈ܐ ܓܒܝ̈ܐ ܕܡܫܟܚܝܢ ܠܡܬܦܠܚܘ: {fit} ܡܢ {count}.';
// / 'Step {step} of {steps}: {operation}'
$Gui2Text138 = 'ܕܪܓܐ {step} ܡܢ {steps}: {operation}';
// / 'File {done} of {total}'
$Gui2Text139 = 'ܦܬܩܐ {done} ܡܢ {total}';
// / 'Waiting for the server.'
$Gui2Text140 = 'ܡܣܟܝܢܢ ܠܡܫܡܫܢܐ.';
// / 'Starting.'
$Gui2Text141 = 'ܡܫܪܝܢܢ.';
// / 'Finished.'
$Gui2Text142 = 'ܫܠܡ.';
// / 'Stopping.'
$Gui2Text143 = 'ܟܠܝܢܢ.';
// / 'Stopped.'
$Gui2Text144 = 'ܐܬܟܠܝ.';
// / 'Files made: '
$Gui2Text145 = 'ܦܬܩ̈ܐ ܕܐܬܥܒܕܘ: ';
// / 'Workflows are not available on this server.'
$Gui2Text146 = 'ܡܪ̈ܕܝܬܐ ܕܦܘܠܚܢܐ ܠܐ ܡܛܝܒܢ ܥܠ ܗܢܐ ܡܫܡܫܢܐ.';
// / 'No workflows are installed on this server.'
$Gui2Text147 = 'ܠܝܬ ܡܪ̈ܕܝܬܐ ܕܦܘܠܚܢܐ ܡܩܝܡ̈ܢ ܥܠ ܗܢܐ ܡܫܡܫܢܐ.';
// / 'That workflow is no longer available.'
$Gui2Text148 = 'ܗܝ ܡܪܕܝܬܐ ܕܦܘܠܚܢܐ ܠܐ ܡܛܝܒܐ ܬܘܒ.';
// / 'None of the selected files can be used by this workflow.'
$Gui2Text149 = 'ܐܦܠܐ ܚܕ ܡܢ ܦܬܩ̈ܐ ܓܒܝ̈ܐ ܡܫܟܚ ܠܡܬܦܠܚܘ ܒܗܕܐ ܡܪܕܝܬܐ ܕܦܘܠܚܢܐ.';
// / 'Too many files are selected for one workflow.'
$Gui2Text150 = 'ܦܬܩ̈ܐ ܣܓܝ̈ܐܐ ܛܒ ܐܬܓܒܝܘ ܠܚܕܐ ܡܪܕܝܬܐ ܕܦܘܠܚܢܐ.';
// / 'The server is busy. Try again in a few minutes.'
$Gui2Text151 = 'ܡܫܡܫܢܐ ܛܪܝܕ. ܢܣܐ ܬܘܒ ܒܬܪ ܩܠܝܠ ܩܛܝܢ̈ܐ.';
// / 'A workflow is already running in this session.'
$Gui2Text152 = 'ܚܕܐ ܡܪܕܝܬܐ ܕܦܘܠܚܢܐ ܪܗܛܐ ܡܢ ܟܕܘ ܒܗܢܐ ܡܘܬܒܐ.';
// / 'The workflow could not be started.'
$Gui2Text153 = 'ܠܐ ܐܫܟܚܬ ܡܪܕܝܬܐ ܕܦܘܠܚܢܐ ܠܡܫܪܝܘ.';
// / 'A step of the workflow failed.'
$Gui2Text154 = 'ܚܕ ܕܪܓܐ ܕܡܪܕܝܬܐ ܕܦܘܠܚܢܐ ܚܪܒ.';
// / 'A virus was found, so the workflow stopped.'
$Gui2Text155 = 'ܐܫܬܟܚ ܒܝܪܘܣ، ܘܡܛܠ ܗܢܐ ܐܬܟܠܝܬ ܡܪܕܝܬܐ ܕܦܘܠܚܢܐ.';
// / 'The workflow ran out of time.'
$Gui2Text156 = 'ܫܠܡ ܙܒܢܐ ܕܡܪܕܝܬܐ ܕܦܘܠܚܢܐ.';
// / 'The workflow made too many files.'
$Gui2Text157 = 'ܡܪܕܝܬܐ ܕܦܘܠܚܢܐ ܥܒܕܬ ܦܬܩ̈ܐ ܣܓܝ̈ܐܐ ܛܒ.';
// / 'The server was too busy to finish the workflow.'
$Gui2Text158 = 'ܡܫܡܫܢܐ ܛܪܝܕ ܗܘܐ ܛܒ ܠܡܫܠܡܘ ܡܪܕܝܬܐ ܕܦܘܠܚܢܐ.';
// / 'An operation this workflow needs is not available.'
$Gui2Text159 = 'ܦܘܠܚܢܐ ܕܣܢܝܩܐ ܠܗ ܗܕܐ ܡܪܕܝܬܐ ܠܐ ܡܛܝܒ.';
// / 'The workflow stopped unexpectedly.'
$Gui2Text160 = 'ܡܪܕܝܬܐ ܕܦܘܠܚܢܐ ܐܬܟܠܝܬ ܕܠܐ ܣܘܟܝܐ.';
// / The words in braces, such as {step} & {kind}, are filled in by the page. Each translation keeps every one of them.
// / 'Available Pipelines'
$Gui2Text161 = 'ܩܢܝ̈ܐ ܕܐܝܬ';
// / 'Select a pipeline above...'
$Gui2Text162 = 'ܓܒܝ ܩܢܝܐ ܠܥܠ...';
// / 'Select Pipeline...'
$Gui2Text163 = 'ܓܒܝ ܩܢܝܐ...';
// / 'Select Files...'
$Gui2Text164 = 'ܓܒܝ ܦܬܩ̈ܐ...';
// / 'Add a step'
$Gui2Text165 = 'ܐܘܣܦ ܕܪܓܐ';
// / 'Options'
$Gui2Text166 = 'ܓܒܝܘܬ̈ܐ';
// / 'Back'
$Gui2Text167 = 'ܠܒܣܬܪܐ';
// / 'Take: '
$Gui2Text168 = 'ܣܒ: ';
// / 'the selected files'
$Gui2Text169 = 'ܦܬܩ̈ܐ ܓܒܝ̈ܐ';
// / 'the files step {step} made'
$Gui2Text170 = 'ܦܬܩ̈ܐ ܕܥܒܕ ܕܪܓܐ {step}';
// / 'each file on its own'
$Gui2Text171 = 'ܟܠ ܦܬܩܐ ܒܠܚܘܕܘܗܝ';
// / 'all files together'
$Gui2Text172 = 'ܟܠܗܘܢ ܦܬܩ̈ܐ ܐܟܚܕܐ';
// / 'Only these kinds: '
$Gui2Text173 = 'ܗܠܝܢ ܐܕ̈ܫܐ ܒܠܚܘܕ: ';
// / 'Only these extensions: '
$Gui2Text174 = 'ܗܠܝܢ ܐܘܪܟ̈ܢܐ ܒܠܚܘܕ: ';
// / 'Smallest size: '
$Gui2Text175 = 'ܪܒܘܬܐ ܙܥܘܪܬܐ: ';
// / 'Largest size: '
$Gui2Text176 = 'ܪܒܘܬܐ ܪܒܬܐ: ';
// / 'In the order they arrive'
$Gui2Text177 = 'ܐܝܟ ܛܟܣܐ ܕܡܛܝܢ';
// / 'all files'
$Gui2Text178 = 'ܟܠܗܘܢ ܦܬܩ̈ܐ';
// / 'all {kind}'
$Gui2Text179 = 'ܟܠ {kind}';
// / 'Width: '
$Gui2Text180 = 'ܦܬܘܐ: ';
// / 'Height: '
$Gui2Text181 = 'ܪܡܘܬܐ: ';
// / 'My workflow'
$Gui2Text182 = 'ܡܪܕܝܬܐ ܕܦܘܠܚܢܐ ܕܝܠܝ';
// / 'New'
$Gui2Text183 = 'ܚܕܬܐ';
// / 'Import'
$Gui2Text184 = 'ܡܥܠܢܘܬܐ';
// / 'Export'
$Gui2Text185 = 'ܡܦܩܢܘܬܐ';
// / 'This workflow is not one this server can run.'
$Gui2Text186 = 'ܗܢܐ ܡܫܡܫܢܐ ܠܐ ܡܫܟܚ ܠܡܪܗܛܘ ܗܕܐ ܡܪܕܝܬܐ ܕܦܘܠܚܢܐ.';
// / 'The file could not be read.'
$Gui2Text187 = 'ܠܐ ܐܫܬܟܚ ܠܡܬܩܪܝܘ ܦܬܩܐ.';
// / 'The workflow builder is not available on this server.'
$Gui2Text188 = 'ܒܢܝܐ ܕܡܪ̈ܕܝܬܐ ܕܦܘܠܚܢܐ ܠܐ ܡܛܝܒ ܥܠ ܗܢܐ ܡܫܡܫܢܐ.';
// / 'Status'
$Gui2Text189 = 'ܐܣܟܡܐ';
// / 'A workflow is running.'
$Gui2Text190 = 'ܚܕܐ ܡܪܕܝܬܐ ܕܦܘܠܚܢܐ ܪܗܛܐ.';
// / 'No workflow is running.'
$Gui2Text191 = 'ܠܝܬ ܡܪܕܝܬܐ ܕܦܘܠܚܢܐ ܕܪܗܛܐ.';
// / 'Extract'
$Gui2Text192 = 'ܐܦܩ';
// / 'Close Extract Options'
$Gui2Text193 = 'ܐܚܕ ܓܘܒܝ̈ܐ ܕܡܦܩܢܘܬܐ';
// / 'Extract This Archive'
$Gui2Text194 = 'ܐܦܩ ܗܢܐ ܓܙܐ';
// / 'Every file it holds is added to your files, under its own name or the next free one.'
$Gui2Text195 = 'ܟܠ ܦܬܩܐ ܕܐܝܬ ܒܗ ܡܬܬܘܣܦ ܠܦܬܩ̈ܐ ܕܝܠܟ ܒܫܡܗ ܐܘ ܒܫܡܐ ܚܐܪܐ ܕܒܬܪܗ.';
// / 'Extract Files'
$Gui2Text196 = 'ܐܦܩ ܦܬܩ̈ܐ';
// / 'to {format}'
$Gui2Text197 = 'ܠܘܬ {format}';
// / 'This workflow deletes files. A deleted file cannot be brought back.'
$Gui2Text198 = 'ܗܕܐ ܡܪܕܝܬܐ ܕܦܘܠܚܢܐ ܛܠܩܐ ܦܬܩ̈ܐ. ܦܬܩܐ ܛܠܝܩܐ ܠܐ ܡܫܟܚ ܕܢܗܦܟ.';
// / 'Delete & Run'
$Gui2Text199 = 'ܛܠܘܩ ܘܪܗܛ';
// / 'Cancel'
$Gui2Text200 = 'ܒܛܠ';
// / 'Rename'
$Gui2Text201 = 'ܫܚܠܦ ܫܡܐ';
// / 'Close Rename Options'
$Gui2Text202 = 'ܐܚܕ ܓܘܒܝ̈ܐ ܕܫܘܚܠܦ ܫܡܐ';
// / 'Rename This File'
$Gui2Text203 = 'ܫܚܠܦ ܫܡܐ ܕܗܢܐ ܦܬܩܐ';
// / 'New Name'
$Gui2Text204 = 'ܫܡܐ ܚܕܬܐ';
// / 'Use {name} for the name it has, {n} for its place among the selected files & {date} for today. Its extension is kept.'
$Gui2Text205 = 'ܦܠܚ {name} ܠܫܡܐ ܕܐܝܬ ܠܗ، {n} ܠܕܘܟܬܗ ܒܝܬ ܦܬܩ̈ܐ ܓܒܝ̈ܐ ܘ {date} ܠܝܘܡܐ ܕܝܘܡܢܐ. ܫܘܓܪܗ ܦܐܫ ܐܝܟ ܕܐܝܬܘܗܝ.';
// / 'Strip Metadata'
$Gui2Text206 = 'ܫܩܘܠ ܡܝܛܕܬܐ';
// / 'Close Strip Metadata Options'
$Gui2Text207 = 'ܐܚܕ ܓܘܒܝ̈ܐ ܕܡܝܛܕܬܐ';
// / 'Strip The Metadata From This File'
$Gui2Text208 = 'ܫܩܘܠ ܡܝܛܕܬܐ ܡܢ ܗܢܐ ܦܬܩܐ';
// / 'A copy is made beside it without its authors, places, dates & the software that made it. What it shows is left as it was.'
$Gui2Text209 = 'ܢܘܣܚܐ ܡܬܒܪܐ ܥܠ ܓܢܒܗ ܕܠܐ ܟܬܘܒ̈ܐ، ܕܘܟ̈ܝܬܐ، ܙܒܢ̈ܐ ܘܡܢܓܢܘܢܘܬܐ ܕܒܪܬܗ. ܡܐ ܕܡܚܘܐ ܦܐܫ ܐܝܟ ܕܐܝܬܘܗܝ.';
// / 'Merge PDFs'
$Gui2Text210 = 'ܚܒܫ ܦܬܩ̈ܐ ܕPDF';
// / 'Split PDF'
$Gui2Text211 = 'ܦܠܓ PDF';
// / 'Merge Or Split'
$Gui2Text212 = 'ܚܒܫ ܐܘ ܦܠܓ';
// / 'Close Merge & Split Options'
$Gui2Text213 = 'ܐܚܕ ܓܘܒܝ̈ܐ ܕܚܒܫܐ ܘܦܠܓܐ';
// / 'Merge The Selected PDFs Into One'
$Gui2Text214 = 'ܚܒܫ ܦܬܩ̈ܐ ܕPDF ܓܒܝ̈ܐ ܠܚܕ';
// / 'Their pages follow one another in the order the PDFs were selected.'
$Gui2Text215 = 'ܦܐܬ̈ܐ ܕܝܠܗܘܢ ܐܬܝ̈ܢ ܚܕܐ ܒܬܪ ܚܕܐ ܐܝܟ ܛܘܟܣܐ ܕܐܬܓܒܝܘ.';
// / 'Select two or more PDFs to merge them.'
$Gui2Text216 = 'ܓܒܝ ܬܪܝܢ ܐܘ ܝܬܝܪ ܦܬܩ̈ܐ ܕPDF ܕܬܚܒܫ ܐܢܘܢ.';
// / 'Make A PDF Of Each Page'
$Gui2Text217 = 'ܥܒܕ ܚܕ PDF ܡܢ ܟܠ ܦܐܬܐ';
// / 'Every selected file is deleted with it: {count} files.'
$Gui2Text218 = 'ܟܠ ܦܬܩܐ ܓܒܝܐ ܡܬܛܠܩ ܥܡܗ: {count}.';
// / 'Every selected file is renamed with it: {count} files.'
$Gui2Text219 = 'ܫܡܐ ܕܟܠ ܦܬܩܐ ܓܒܝܐ ܡܫܬܚܠܦ ܥܡܗ: {count}.';
// / 'Library'
$Gui2Text220 = 'ܒܝܬ ܟܬܒ̈ܐ';
// / 'from Selected Files'
$Gui2Text221 = 'ܡܢ ܦܬܩ̈ܐ ܓܒܝ̈ܐ';
// / 'from Step {step}'
$Gui2Text222 = 'ܡܢ ܕܪܓܐ {step}';
// / 'Preview'
$Gui2Text223 = 'ܚܙܝܬܐ ܩܕܡܝܬܐ';
// / 'If the workflow ran now, each step would take:'
$Gui2Text224 = 'ܐܢ ܡܪܕܝܬܐ ܕܦܘܠܚܢܐ ܫܪܝܐ ܗܫܐ، ܟܠ ܕܪܓܐ ܢܣܒ:';
// / 'Left out: '
$Gui2Text225 = 'ܫܒܝܩ̈ܐ: ';
// / 'No files.'
$Gui2Text226 = 'ܠܝܬ ܦܬܩ̈ܐ.';
// / 'every file in the list when it runs'
$Gui2Text227 = 'ܟܠ ܦܬܩܐ ܕܒܣܕܪܐ ܡܐ ܕܕܪܓܐ ܫܪܐ';
// / 'This workflow deletes these files. A deleted file cannot be brought back.'
$Gui2Text228 = 'ܗܕܐ ܡܪܕܝܬܐ ܕܦܘܠܚܢܐ ܛܠܩܐ ܗܠܝܢ ܦܬܩ̈ܐ. ܦܬܩܐ ܛܠܝܩܐ ܠܐ ܡܫܟܚ ܕܢܗܦܟ.';
// / 'A step had errors, so the workflow stopped before the step that deletes files.'
$Gui2Text229 = 'ܚܕ ܕܪܓܐ ܗܘܘ ܠܗ ܦܘܕ̈ܢܐ، ܘܡܛܠ ܗܢܐ ܐܬܟܠܝܬ ܡܪܕܝܬܐ ܕܦܘܠܚܢܐ ܩܕܡ ܕܪܓܐ ܕܛܠܩ ܦܬܩ̈ܐ.';
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
