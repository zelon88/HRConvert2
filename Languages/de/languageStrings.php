<?php
// / -----------------------------------------------------------------------------------
// / APPLICATION INFORMATION ...
// / HRConvert2, Copyright on 4/13/2023 by Justin Grimes, www.github.com/zelon88
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
// / Set a flag to tell that the UI has been displayed.
$LanguageStringsLoaded = TRUE;
// / Set the reading direction for text on the page.
$GUIDirection = 'ltr';
// / Set the side of the page to float elements to.
$GUIAlignment = 'left';
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die('ERROR!!! HRConvert2-2, Diese Datei kann Ihre Anfrage nicht verarbeiten! Bitte senden Sie Ihre Datei stattdessen an convertCore.php!');
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Check for required core variables.
if (!isset($Font)) $Font = 'Arial';
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = 'Konvertieren Sie alles!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI Related Logic.
$FCPlural1 = '';
if ($FileCount === 0 or !is_numeric($FileCount)) $FCPlural1 = 'Sie haben 0 gültige Dateien auf '.$ApplicationName.'-2 hochgeladen.';
if ($FileCount === 1) $FCPlural1 = 'Sie haben 1 gültige Datei auf '.$ApplicationName.'-2 hochgeladen.'; 
if ($FileCount >= 2) $FCPlural1 = 'Sie haben '.$FileCount.' gültige Dateien auf '.$ApplicationName.'-2 hochgeladen.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Header Related Variables.
// / 'Click, Tap, or Drop files here to upload.'
$GuiHeaderText1 = 'Klicken, tippen oder legen Sie Dateien hier ab, um sie hochzuladen.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / Online File Converter, Extractor, Compressor'
$Gui1Text1 = 'Online-Dateikonverter, Extraktor, Kompressor';
// / $ApplicationName.' is based off the open-source web-app <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> by <a href=\'https://github.com/zelon88\'>Zelon88</a> that converts files without tracking users across the net or infringing on your intellectual property.'
$Gui1Text2 = $ApplicationName.' basiert auf der Open-Source-Web-App HRConvert2 von <a href=\'https://github.com/zelon88\'>Zelon88</a>, die Dateien konvertiert, ohne Benutzer über das Internet zu verfolgen oder Ihr geistiges Eigentum zu verletzen.';
// / 'More Info ...'
$Gui1Text3 = 'Mehr Info ...';
// / 'Less Info ...'
$Gui1Text4 = 'Weniger Informationen ...';
// / 'All user-supplied data is erased automatically, so you don\'t need to worry about forfeiting your personal information or property while using our services.'
$Gui1Text5 = 'Alle vom Benutzer bereitgestellten Daten werden automatisch gelöscht, sodass Sie sich keine Sorgen über den Verlust Ihrer persönlichen Daten oder Ihres Eigentums machen müssen, wenn Sie unsere Dienste nutzen.';
// / 'Currently '.$ApplicationName.' supports '.$SupportedFormatCount.' different file formats, including documents, spreadsheets, images, media, 3D models, CAD drawings, vector files, archives, disk images, & more.'
$Gui1Text6 = 'Derzeit '.$ApplicationName.' unterstützt '.$SupportedFormatCount.' verschiedene Dateiformate, einschließlich Dokumente, Tabellenkalkulationen, Bilder, Medien, 3D-Modelle, CAD-Zeichnungen, Vektordateien, Archive, Disk-Images und mehr.';
// / 'View Supported Formats ...'
$Gui1Text7 = 'Unterstützte Formate Anzeigen ...';
// / 'Hide Supported Formats ...'
$Gui1Text8 = 'Unterstützte Formate Ausblenden ...';
// / 'Supported Formats'
$Gui1Text9 = 'Unterstützte Formate';
// / 'Audio Formats'
$Gui1Text10 = 'Audio-Formate';
// / 'Supports specific bitrate.'
$Gui1Text11 = 'Unterstützt bestimmte Bitrate.';
// / 'Video Formats'
$Gui1Text12 = 'Video-Formate';
// / 'Stream Formats'
$Gui1Text13 = 'Stream-Formate';
// / 'Document Formats'
$Gui1Text14 = 'Dokumenten-Formate';
// / 'Spreadsheet Formats'
$Gui1Text15 = 'Tabellen-Formate';
// / 'Presentation Formats'
$Gui1Text16 = 'Präsentations-Formate';
// / 'Archive Formats'
$Gui1Text17 = 'Archiv-Formate';
// / 'Can convert between archive formats & disk image formats.'
$Gui1Text18 = 'Kann zwischen ausgewählten Archivformaten und Disk-Image-Formaten konvertieren.';
// / 'Image Formats'
$Gui1Text19 = 'Bild-Formate';
// / 'Can convert pictures of documents to document formats.'
$Gui1Text20 = 'Kann Bilder von Dokumenten in Dokumentformate konvertieren.';
// / 'Supports resize & rotate.'
$Gui1Text21 = 'Unterstützt die Größenänderung und Drehung.';
// / '3D Model Formats'
$Gui1Text22 = 'Modell-Formate';
// / 'Drawing Formats'
$Gui1Text23 = 'Zeichnung-Formate';
// / 'Can convert drawing files to image formats.'
$Gui1Text24 = 'Kann Zeichnungsformate in Bildformate konvertieren.';
// / 'OCR Support'
$Gui1Text25 = 'Optische Zeichenerkennungsunterstützung';
// / 'OCR Operations support the following input formats...'
$Gui1Text26 = 'Optische Zeichenerkennungsoperationen unterstützen die folgenden Eingabeformate...';
// / 'OCR Operations support the following output formats...'
$Gui1Text27 = 'Operationen unterstützen die folgenden Ausgabeformate...';
// / 'Select files by clicking, tapping, or dropping them into the box below.'
$Gui1Text28 = 'Wählen Sie Dateien aus, indem Sie sie anklicken, antippen oder in das unten stehende Feld ziehen.';
// / 'Continue ...'
$Gui1Text29 = 'Weitermachen ...';
// / 'Can convert stream formats to video formats.'
$Gui1Text30 = 'Kann Stream-Formate in Videoformate konvertieren.';
// / 'Subtitle Formats'
$Gui1Text31 = 'Untertitel-Formate';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'Dateikonvertierungsoptionen';
// / 'Bulk File Options'
$Gui2Text2 = 'Massendateioptionen';
// / 'Scan All Files For Viruses'
$Gui2Text3 = 'Alle Dateien Auf Viren Scannen';
// / 'Compress & Download All Files'
$Gui2Text4 = 'Alle Dateien Komprimieren Und Herunterladen';
// / 'Download'
$Gui2Text5 = 'Herunterladen';
// / 'Share'
$Gui2Text6 = 'Aktie';
// / 'Close Share Options'
$Gui2Text7 = 'Schließen Sie Die Freigabeoptionen';
// / 'Virus Scan'
$Gui2Text8 = 'Virusscan';
// / 'Close Virus Scan Options'
$Gui2Text9 = 'Schließen Sie Die Virus-Scan-Optionen';
// / 'Archive'
$Gui2Text10 = 'Archiv';
// / 'Close Archive Options'
$Gui2Text11 = 'Archivoptionen schließen';
// / 'OCR'
$Gui2Text12 = 'Optische Zeichenerkennung';
// / 'Close OCR Options'
$Gui2Text13 = 'Optionen Der Optischen Zeichenerkennung Schließen';
// / 'Convert'
$Gui2Text14 = 'Konvertieren';
// / 'Close Convert Options'
$Gui2Text15 = 'Close Convert Options';
// / 'Archive This File'
$Gui2Text16 = 'Diese Datei Archivieren';
// / 'Specify Filename: '
$Gui2Text17 = 'Dateinamen Angeben: ';
// / 'Format'
$Gui2Text18 = 'Format';
// / 'Compress & Download'
$Gui2Text19 = 'Komprimieren Und Herunterladen';
// / 'Scan with ClamAV: '
$Gui2Text20 = 'Mit ClamAV Scannen: ';
// / 'Scan with ScanCore: '
$Gui2Text21 = 'Mit ScanCore Scannen: ';
// / 'Scan All'
$Gui2Text22 = 'Alles Scannen';
// / 'Share This File'
$Gui2Text23 = 'Teile Diese Datei';
// / 'Link Status: '
$Gui2Text24 = 'Verbindungsstatus: ';
// / 'Not Generated'
$Gui2Text25 = 'Nicht Generiert';
// / 'Generated'
$Gui2Text26 = 'Generiert';
// / 'Clipboard Status: '
$Gui2Text27 = 'Zwischenablagestatus: ';
// / 'Copied'
$Gui2Text28 = 'Kopiert';
// / 'File Link: '
$Gui2Text29 = 'Dateilink: ';
// / 'You have uploaded '.$FileCount.' valid file'.$FCPlural1.' to '.$ApplicationName.'.'
$Gui2Text30 = $FCPlural1;
// / 'Your file'.$FCPlural2.' now ready to convert using the options below.'
$Gui2Text31 = 'Ihre Dateien können jetzt mithilfe der folgenden optionen konvertiert werden.';
// / 'Generate Link & Copy to Clipboard'
$Gui2Text32 = 'Link Generieren Und In Die Zwischenablage Kopieren';
// / 'Generate Link'
$Gui2Text33 = 'Verknüpfung Generieren';
// / 'Scan This File For Viruses'
$Gui2Text34 = 'Scannen Sie Diese Datei Auf Viren';
// / 'Scan File With ScanCore'
$Gui2Text35 = 'Datei mit ScanCore Scannen';
// / 'Scan File With ClamAV'
$Gui2Text36 = 'Datei mit ClamAV Scannen';
// / 'Scan File With ScanCore & ClamAV'
$Gui2Text37 = 'Datei Mit ScanCore & ClamAV Scannen';
// / 'Perform Optical Character Recognition On This File'
$Gui2Text38 = 'Führen Sie eine optische Zeichenerkennung für diese Datei durch';
// / 'Method'
$Gui2Text39 = 'Methode';
// / 'Simple'
$Gui2Text40 = 'Einfach';
// / 'Advanced'
$Gui2Text41 = 'Fortschrittlich';
// / 'Convert This Archive'
$Gui2Text42 = 'Konvertieren Sie Dieses Archiv';
// / 'Convert This Spreadsheet'
$Gui2Text43 = 'Dieses Dokument Konvertieren';
// / 'Convert This Spreadsheet'
$Gui2Text44 = 'Diese Tabelle Konvertieren';
// / 'Convert This Audio'
$Gui2Text45 = 'Dieses Audio Konvertieren';
// / 'Convert This Video'
$Gui2Text46 = 'Dieses Video Konvertieren';
// / 'Convert This Stream'
$Gui2Text47 = 'Diesen Stream Konvertieren';
// / Convert This 3D Model'
$Gui2Text48 = 'Dieses Modell Konvertieren';
// / 'Convert This Technical Drawing Or Vector File'
$Gui2Text49 = 'Konvertieren Sie diese technische Zeichnung oder Vektordatei';
// / 'Convert This Image'
$Gui2Text50 = 'Dieses Bild Konvertieren';
// / 'Archive File'
$Gui2Text51 = 'Datei Archivieren';
// / 'Convert Into Document'
$Gui2Text52 = 'In Dokument Umwandeln';
// / 'Archive Files'
$Gui2Text53 = 'Dateien Archivieren';
// / 'Convert Document'
$Gui2Text54 = 'Dokument Konvertieren';
// / 'Convert Spreadsheet'
$Gui2Text55 = 'Tabellenkalkulation Konvertieren';
// / 'Convert Presentation'
$Gui2Text56 = 'Convert Presentation';
// / 'Convert Audio'
$Gui2Text57 = 'Audio Konvertieren';
// / 'Convert Video'
$Gui2Text58 = 'Video Konvertieren';
// / 'Convert Stream'
$Gui2Text59 = 'Stream Konvertieren';
// / 'Convert Model'
$Gui2Text60 = 'Modell Konvertieren';
// / 'Convert Drawing'
$Gui2Text61 = 'Zeichnung Konvertieren';
// / 'Convert Image'
$Gui2Text62 = 'Bild Konvertieren';
// / 'Width & Height'
$Gui2Text64 = 'Breite Höhe: ';
// / 'Rotate: '
$Gui2Text65 = 'Drehen: ';
// / 'Bitrate: '
$Gui2Text66 = 'Bitrate: ';
// / 'Delete'
$Gui2Text67 = 'Löschen';
// / 'Close Delete Options'
$Gui2Text68 = 'Schließen Sie Die Löschoptionen';
// / 'Delete This File'
$Gui2Text69 = 'Diese Datei Löschen';
// / 'Confirm Delete'
$Gui2Text70 = 'Diese Datei Löschen';
// / 'Cannot convert this file! Try changing the name.'
$Gui2Text71 = 'Diese Datei kann nicht konvertiert werden! Versuchen Sie, den Namen zu ändern.';
// / 'Cannot perform a virus scan on this file!'
$Gui2Text72 = 'Für diese Datei kann kein Virenscan durchgeführt werden!';
// / 'File Link Copied to Clipboard!'
$Gui2Text73 = 'Dateilink In Die Zwischenablage Kopiert!';
// / 'Operation Failed!'
$Gui2Text74 = 'Operation Fehlgeschlagen!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy'
$GuiFooterText1 = 'Lesen Sie unsere <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Nutzungsbedingungen</a> und <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Datenschutzrichtlinie';
// / -----------------------------------------------------------------------------------