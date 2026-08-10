<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRConvert2, Copyright on 8/8/2026 by Justin Grimes, www.github.com/zelon88
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
// / v3.6.2.
// / This file contains language specific GUI related text for performing file conversions.
// /
// / HARDWARE REQUIREMENTS ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / DEPENDENCY REQUIREMENTS ...
// / This application requires Debian Linux, Apache 2.4, PHP 8+, FFMPEG, Dia, LibreOffice, 
// / Mkisofs, 7zip, Unoconv, libgxps-utils, Tesseract, Unzip, OpenSCAD, Rar, Inkscape,
// / Unrar, ClamAV, MeshLab, PopplerUtils, PDFTOTEXT, ImageMagick, bwrap & xvfb-run.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set a flag to tell that the UI has been displayed.
$LanguageStringsLoaded = TRUE;
// / The version of this language pack for compatibility checking.
// / Compatibility check takes place in convertCore.php, buildGui() function.
$LanguageVersion = 'v3.6.2';
$LanguageVersion = ltrim($LanguageVersion, 'vV');
// / Set the reading direction for text on the page.
$GUIDirection = 'ltr';
// / Set the side of the page to float elements to.
$GUIAlignment = 'left';
// / Define an error message to display for if the core has not been loaded.
$CoreError = 'FOUT!!! HRConvert2-2, Dit bestand kan uw verzoek niet verwerken! Dien uw bestand in plaats daarvan in bij convertCore.php!';
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die($CoreError);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Check for required core variables.
if (!isset($Font)) $Font = 'Arial';
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = 'Converteer Alles!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI Related Logic.
if (!is_numeric($FileCount)) $FileCount = 'een onbekend aantal';
$FCPlural1 = 'en';
$FCPlural2 = 'en zijn';
if ($FileCount == 1) {
  $FCPlural1 = '';
  $FCPlural2 = ' is'; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Header Related Variables.
// / 'Click, Tap, or Drop files here to upload.'
$GuiHeaderText1 = 'Klik, Tik of Sleep bestanden hierheen om te uploaden.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / Online File Converter, Extractor, Compressor'
$Gui1Text1 = 'Online Bestandsconverteerder, Uitpakker, Comprimerer';
// / $ApplicationName.' is based off the open-source web-app <a href=\'https://github.com\'>HRConvert2</a> by <a href=\'https://github.com\'>Zelon88</a> that converts files without tracking users across the net or infringing on your intellectual property.'
$Gui1Text2 = $ApplicationName.' is gebaseerd op de open-source web-app <a href=\'https://github.com\'>HRConvert2</a> van <a href=\'https://github.com\'>Zelon88</a> die bestanden converteert zonder gebruikers over het internet te volgen of inbreuk te maken op uw intellectuele eigendom.';
// / 'More Info ...'
$Gui1Text3 = 'Meer Info ...';
// / 'Less Info ...'
$Gui1Text4 = 'Minder Info ...';
// / 'All user-supplied data is erased automatically, so you don\'t need to worry about forfeiting your personal information or property while using our services.'
$Gui1Text5 = 'Alle door de gebruiker verstrekte gegevens worden automatisch verwijderd, dus u hoeft zich geen zorgen te maken over het verliezen van uw persoonlijke gegevens of eigendommen tijdens het gebruik van onze diensten.';
// / 'Currently '.$ApplicationName.' supports '.$SupportedFormatCount.' different file formats, including documents, spreadsheets, images, media, 3D models, CAD drawings, vector files, archives, disk images, & more.'
$Gui1Text6 = 'Momenteel ondersteunt '.$ApplicationName.' '.$SupportedFormatCount.' verschillende bestandsformaten, waaronder documenten, spreadsheets, afbeeldingen, media, 3D-modellen, CAD-tekeningen, vectorbestanden, archieven, schijfkopieën & meer.';
// / 'View Supported Formats ...'
$Gui1Text7 = 'Bekijk Ondersteunde Formaten ...';
// / 'Hide Supported Formats ...'
$Gui1Text8 = 'Verberg Ondersteunde Formaten ...';
// / 'Supported Formats'
$Gui1Text9 = 'Ondersteunde Formaten';
// / 'Audio Formats'
$Gui1Text10 = 'Audioformaten';
// / 'Supports specific bitrate.'
$Gui1Text11 = 'Ondersteunt specifieke bitrate.';
// / 'Video Formats'
$Gui1Text12 = 'Videoformaten';
// / 'Stream Formats'
$Gui1Text13 = 'Streamformaten';
// / 'Document Formats'
$Gui1Text14 = 'Documentformaten';
// / 'Spreadsheet Formats'
$Gui1Text15 = 'Spreadsheetformaten';
// / 'Presentation Formats'
$Gui1Text16 = 'Presentatieformaten';
// / 'Archive Formats'
$Gui1Text17 = 'Archiefformaten';
// / 'Can convert between archive formats & disk image formats.'
$Gui1Text18 = 'Kan converteren tussen geselecteerde archiefformaten & schijfkopieformaten.';
// / 'Image Formats'
$Gui1Text19 = 'Afbeeldingsformaten';
// / 'Can convert pictures of documents to document formats.'
$Gui1Text20 = 'Kan foto\'s van documenten converteren naar documentformaten.';
// / 'Supports resize & rotate.'
$Gui1Text21 = 'Ondersteunt formaat wijzigen & roteren.';
// / '3D Model Formats'
$Gui1Text22 = '3D-modelformaten';
// / 'Drawing Formats'
$Gui1Text23 = 'Tekeningformaten';
// / 'Can convert drawing files to image formats.'
$Gui1Text24 = 'Kan tekeningformaten converteren naar afbeeldingsformaten.';
// / 'OCR Support'
$Gui1Text25 = 'OCR-ondersteuning';
// / 'OCR Operations support the following input formats...'
$Gui1Text26 = 'OCR-bewerkingen ondersteunen de volgende invoerformaten...';
// / 'OCR Operations support the following output formats...'
$Gui1Text27 = 'OCR-bewerkingen ondersteunen de volgende uitvoerformaten...';
// / 'Select files by clicking, tapping, or dropping them into the box below.'
$Gui1Text28 = 'Selecteer bestanden door te klikken, te tikken of ze in het onderstaande vak te slepen.';
// / 'Continue ...'
$Gui1Text29 = 'Doorgaan ...';
// / 'Can convert stream formats to video formats.'
$Gui1Text30 = 'Kan streamformaten converteren naar videoformaten.';
// / 'Subtitle Formats'
$Gui1Text31 = 'Ondertitelformaten';
// / 'OpenSCAD Formats'
$Gui1Text32 = 'OpenSCAD-formaten';
// / 'Renders OpenSCAD source into 3D model formats.'
$Gui1Text33 = 'Rendert OpenSCAD-bronbestanden naar 3D-modelformaten.';
// / 'File references inside uploaded sources are removed unless the server allows resolving them.'
$Gui1Text34 = 'Bestandsreferenties binnen geüploade bronbestanden worden verwijderd, tenzij de server toestaat deze op te lossen.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'Bestandsconversie Opties';
// / 'Bulk File Options'
$Gui2Text2 = 'Bulk Bestandsopties';
// / 'Scan All Files For Viruses'
$Gui2Text3 = 'Scan Alle Bestanden Op Virussen';
// / 'Compress & Download All Files'
$Gui2Text4 = 'Comprimeer & Download Alle Bestanden';
// / 'Download'
$Gui2Text5 = 'Download';
// / 'Share'
$Gui2Text6 = 'Delen';
// / 'Close Share Options'
$Gui2Text7 = 'Sluit Deelopties';
// / 'Virus Scan'
$Gui2Text8 = 'Virusscan';
// / 'Close Virus Scan Options'
$Gui2Text9 = 'Sluit Virusscan Opties';
// / 'Archive'
$Gui2Text10 = 'Archief';
// / 'Close Archive Options'
$Gui2Text11 = 'Sluit Archiefopties';
// / 'OCR'
$Gui2Text12 = 'OCR';
// / 'Close OCR Options'
$Gui2Text13 = 'Sluit OCR-opties';
// / 'Convert'
$Gui2Text14 = 'Converteer';
// / 'Close Convert Options'
$Gui2Text15 = 'Sluit Converteeropties';
// / 'Archive This File'
$Gui2Text16 = 'Archiveer Dit Bestand';
// / 'Specify Filename: '
$Gui2Text17 = 'Specificeer Bestandsnaam: ';
// / 'Format'
$Gui2Text18 = 'Formaat';
// / 'Compress & Download'
$Gui2Text19 = 'Comprimeer & Download';
// / 'Scan with ClamAV: '
$Gui2Text20 = 'Scan met ClamAV: ';
// / 'Scan with ScanCore: '
$Gui2Text21 = 'Scan met ScanCore: ';
// / 'Scan All'
$Gui2Text22 = 'Scan Alles';
// / 'Share This File'
$Gui2Text23 = 'Deel Dit Bestand';
// / 'Link Status: '
$Gui2Text24 = 'Linkstatus: ';
// / 'Not Generated'
$Gui2Text25 = 'Niet Gegenereerd';
// / 'Generated'
$Gui2Text26 = 'Gegenereerd';
// / 'Clipboard Status: '
$Gui2Text27 = 'Klembordstatus: ';
// / 'Copied'
$Gui2Text28 = 'Kopieerd';
// / 'File Link: '
$Gui2Text29 = 'Bestandslink: ';
// / 'You have uploaded '.$FileCount.' valid file'.$FCPlural1.' to '.$ApplicationName.'.'
$Gui2Text30 = 'U heeft '.$FileCount.' geldig bestand'.$FCPlural1.' geüpload naar '.$ApplicationName.'.';
// / 'Your file'.$FCPlural2.' now ready to convert using the options below.'
$Gui2Text31 = 'Uw bestand'.$FCPlural2.' nu klaar om te converteren met behulp van de onderstaande opties.';
// / 'Generate Link & Copy to Clipboard'
$Gui2Text32 = 'Genereer Link & Kopieer naar Klembord';
// / 'Generate Link'
$Gui2Text33 = 'Genereer Link';
// / 'Scan This File For Viruses'
$Gui2Text34 = 'Scan Dit Bestand Op Virussen';
// / 'Scan File With ScanCore'
$Gui2Text35 = 'Scan Bestand Met ScanCore';
// / 'Scan File With ClamAV'
$Gui2Text36 = 'Scan Bestand Met ClamAV';
// / 'Scan File With ScanCore & ClamAV'
$Gui2Text37 = 'Scan Bestand Met ScanCore & ClamAV';
// / 'Perform Optical Character Recognition On This File'
$Gui2Text38 = 'Voer Optische Tekenherkenning Uit Op Dit Bestand';
// / 'Method'
$Gui2Text39 = 'Methode';
// / 'Simple'
$Gui2Text40 = 'Eenvoudig';
// / 'Advanced'
$Gui2Text41 = 'Geavanceerd';
// / 'Convert This Archive'
$Gui2Text42 = 'Converteer Dit Archief';
// / 'Convert This Document'
$Gui2Text43 = 'Converteer Dit Document';
// / 'Convert This Spreadsheet'
$Gui2Text44 = 'Converteer Dit Spreadsheet';
// / 'Convert This Audio'
$Gui2Text45 = 'Converteer Deze Audio';
// / 'Convert This Video'
$Gui2Text46 = 'Converteer Deze Video';
// / 'Convert This Stream'
$Gui2Text47 = 'Converteer Deze Stream';
// / Convert This 3D Model'
$Gui2Text48 = 'Converteer Dit 3D-model';
// / 'Convert This Technical Drawing Or Vector File'
$Gui2Text49 = 'Converteer Deze Technische Tekening Of Dit Vectorbestand';
// / 'Convert This Image'
$Gui2Text50 = 'Converteer Deze Afbeelding';
// / 'Archive File'
$Gui2Text51 = 'Archiveer Bestand';
// / 'Convert Into Document'
$Gui2Text52 = 'Converteer Naar Document';
// / 'Archive Files'
$Gui2Text53 = 'Archiveer Bestanden';
// / 'Convert Document'
$Gui2Text54 = 'Converteer Document';
// / 'Convert Spreadsheet'
$Gui2Text55 = 'Converteer Spreadsheet';
// / 'Convert Presentation'
$Gui2Text56 = 'Converteer Presentatie';
// / 'Convert Audio'
$Gui2Text57 = 'Converteer Audio';
// / 'Convert Video'
$Gui2Text58 = 'Converteer Video';
// / 'Convert Stream'
$Gui2Text59 = 'Converteer Stream';
// / 'Convert Model'
$Gui2Text60 = 'Converteer Model';
// / 'Convert Drawing'
$Gui2Text61 = 'Converteer Tekening';
// / 'Convert Image'
$Gui2Text62 = 'Converteer Afbeelding';
// / 'Width & Height'
$Gui2Text64 = 'Breedte & Hoogte: ';
// / 'Rotate: '
$Gui2Text65 = 'Roteer: ';
// / 'Bitrate: '
$Gui2Text66 = 'Bitrate: ';
// / 'Delete'
$Gui2Text67 = 'Verwijderen';
// / 'Close Delete Options'
$Gui2Text68 = 'Sluit Verwijderopties';
// / 'Delete This File'
$Gui2Text69 = 'Verwijder Dit Bestand';
// / 'Confirm Delete'
$Gui2Text70 = 'Bevestig Verwijdering';
// / 'Cannot convert this file! Try changing the name.'
$Gui2Text71 = 'Kan dit bestand ikke converteren! Probeer de naam te wijzigen.';
// / 'Cannot perform a virus scan on this file!'
$Gui2Text72 = 'Kan geen virusscan uitvoeren op dit bestand!';
// / 'File Link Copied to Clipboard!'
$Gui2Text73 = 'Bestandslink Gekopieerd naar Klembord!';
// / 'Operation Failed!'
$Gui2Text74 = 'Bewerking Mislukt!';
// / 'Convert These Subtitles'
$Gui2Text75 = 'Converteer Deze Ondertitels';
// / 'Convert Subtitles'
$Gui2Text76 = 'Converteer Ondertitels';
// / 'Convert This Presentation'
$Gui2Text77 = 'Converteer Deze Presentatie';
// / 'Convert This XPS File'
$Gui2Text78 = 'Converteer Dit XPS-bestand';
// / 'Render This OpenSCAD Model'
$Gui2Text79 = 'Render Dit OpenSCAD-model';
// / 'Render Model'
$Gui2Text80 = 'Render Model';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - HRC2-Functions.js Related Variables.
// / These strings are used by the client side javascript library.
// / That file is static javascript & cannot read a PHP variable, so every string it needs
// / is passed to it as an argument by the PHP that calls it.
// / 'Your browser does not support copying to the clipboard!'
$GuiFunctionsText1 = 'Uw browser ondersteunt het kopiëren naar het klembord niet!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy'
$GuiFooterText1 = 'Bekijk onze <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Servicevoorwaarden</a> en ons <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacybeleid';
// / -----------------------------------------------------------------------------------
