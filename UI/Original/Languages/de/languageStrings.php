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
// / v3.7,4.
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
$LanguageVersion = 'v4.1.5';
$LanguageVersion = ltrim($LanguageVersion, 'vV');
// / Set the reading direction for text on the page.
$GUIDirection = 'ltr';
// / Set the side of the page to float elements to.
$GUIAlignment = 'left';
// / Which end of a dropdown the arrow sits at, as a percentage across the control.
// / This pack knows its own reading direction & is therefore the right place to decide.
// / A reader finishes at the left of a right to left line, so the arrow belongs there.
// / An arrow pinned to the right edge sits at the START of the box in this language &
// / reads as a stray icon rather than as the thing that opens the list.
$SelectArrowSide = ($GUIAlignment === 'right') ? '4%' : '96%';
// / Define an error message to display for if the core has not been loaded.
$CoreError = 'ERROR!!! HRConvert2-2, Diese Datei kann Ihre Anfrage nicht verarbeiten! Bitte senden Sie Ihre Datei stattdessen an convertCore.php!';
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die($CoreError);
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
// / Set GUI - UI Selector Bar Related Variables.
// / These strings appear in the selector bar, which is present on both GUI1 & GUI2.
// / 'Language'
$GuiSelectorText1 = 'Sprache';
// / 'Color'
$GuiSelectorText2 = 'Farbe';
// / 'Interface'
$GuiSelectorText3 = 'Oberfläche';
// / 'Display language, color and interface options'
$GuiSelectorText4 = 'Optionen für Sprache, Farbe und Oberfläche anzeigen';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / 'Online File Converter, Extractor, Compressor'
$Gui1Text1 = 'Online-Dateikonverter, Extraktor, Kompressor';
// / $ApplicationName.' is based off the open-source web-app <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> by <a href=\'https://github.com/zelon88\'>Zelon88</a> that converts files without tracking users across the net or infringing on your intellectual property.'
$Gui1Text2 = $ApplicationName.' basiert auf der Open-Source-Web-App HRConvert2 von <a href=\'https://github.com/zelon88\'>Zelon88</a>, die Dateien konvertiert, ohne Benutzer über das Internet zu verfolgen oder Ihr geistiges Eigentum zu verletzen.';
// / 'More Info ...'
$Gui1Text3 = 'Mehr Info...';
// / 'Less Info ...'
$Gui1Text4 = 'Weniger Informationen...';
// / 'All user-supplied data is erased automatically, so you don\'t need to worry about forfeiting your personal information or property while using our services.'
$Gui1Text5 = 'Alle vom Benutzer bereitgestellten Daten werden automatisch gelöscht, sodass Sie sich keine Sorgen über den Verlust Ihrer persönlichen Daten oder Ihres Eigentums machen müssen, wenn Sie unsere Dienste nutzen.';
// / 'Currently '.$ApplicationName.' supports '.$SupportedFormatCount.' different file formats, including documents, spreadsheets, images, media, 3D models, CAD drawings, vector files, archives, disk images, & more.'
$Gui1Text6 = 'Derzeit '.$ApplicationName.' unterstützt '.$SupportedFormatCount.' verschiedene Dateiformate, einschließlich Dokumente, Tabellenkalkulationen, Bilder, Medien, 3D-Modelle, CAD-Zeichnungen, Vektordateien, Archive, Disk-Images und mehr.';
// / 'View Supported Formats ...'
$Gui1Text7 = 'Unterstützte Formate Anzeigen...';
// / 'Hide Supported Formats ...'
$Gui1Text8 = 'Unterstützte Formate Ausblenden...';
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
$Gui1Text29 = 'Weitermachen...';
// / 'Can convert stream formats to video formats.'
$Gui1Text30 = 'Kann Stream-Formate in Videoformate konvertieren.';
// / 'Subtitle Formats'
$Gui1Text31 = 'Untertitel-Formate';
// / 'OpenSCAD Formats'
$Gui1Text32 = 'OpenSCAD-Formate';
// / 'Renders OpenSCAD source into 3D model formats.'
$Gui1Text33 = 'Wandelt OpenSCAD-Quellcode in 3D-Modellformate um.';
// / 'File references inside uploaded sources are removed unless the server allows resolving them.'
$Gui1Text34 = 'Dateiverweise in hochgeladenen Quelldateien werden entfernt, sofern der Server deren Auflösung nicht zulässt.';
// / 'Delete every uploaded file & start a new session?'
// / Shown inside the start over panel on the upload page. That panel is only rendered
// / when the session already holds at least one file, so this is never shown to a first
// / time visitor who has nothing to lose.
$Gui1Text35 = 'Alle hochgeladenen Dateien löschen und eine neue Sitzung starten?';
// / 'Start Over'
// / Labels the control that opens the panel & the button inside it that confirms.
$Gui1Text36 = 'Neu Anfangen';
// / 'Refresh'
// / Alternate text for the refresh control on the upload page. That control is a glyph
// / with no text of its own, so this is the only description a screen reader has & the
// / only thing shown when a browser cannot render the glyph.
$Gui1Text37 = 'Aktualisieren';
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
$Gui2Text15 = 'Konvertierungsoptionen Schließen';
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
// / 'Convert This Document'
$Gui2Text43 = 'Dieses Dokument Konvertieren';
// / 'Convert This Spreadsheet'
$Gui2Text44 = 'Diese Tabelle Konvertieren';
// / 'Convert This Audio'
$Gui2Text45 = 'Dieses Audio Konvertieren';
// / 'Convert This Video'
$Gui2Text46 = 'Dieses Video Konvertieren';
// / 'Convert This Stream'
$Gui2Text47 = 'Diesen Stream Konvertieren';
// / 'Convert This 3D Model'
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
$Gui2Text56 = 'Präsentation Konvertieren';
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
// / 'Convert These Subtitles'
$Gui2Text75 = 'Konvertieren Sie Diese Untertitel';
// / 'Convert Subtitles'
$Gui2Text76 = 'Konvertiere Untertitel';
// / 'Convert This Presentation'
$Gui2Text77 = 'Diese Präsentation umwandeln';
// / 'Convert This XPS File'
$Gui2Text78 = 'XPS-Datei Konvertieren';
// / 'Render This OpenSCAD Model'
$Gui2Text79 = 'Dieses OpenSCAD-Modell Rendern';
// / 'Render Model'
$Gui2Text80 = 'Modell Rendern';
// / 'Convert This E-Book'
$Gui2Text81 = 'Dieses E-Book konvertieren';
// / 'Convert E-Book'
$Gui2Text82 = 'E-Book konvertieren';
// / 'Playlist source URL, only needed if the playlist uses relative paths'
$Gui2Text83 = 'Quell-URL der Wiedergabeliste, nur nötig wenn die Liste relative Pfade verwendet';
// / 'Download A File From A URL'
$Gui2Text84 = 'Eine Datei von einer URL herunterladen';
// / 'Fetch'
$Gui2Text85 = 'Abrufen';
// / 'Identify'
$Gui2Text86 = 'Identifizieren';
// / 'Close Identify Options'
$Gui2Text87 = 'Identifizierungsoptionen Schließen';
// / 'Identify This File'
$Gui2Text88 = 'Diese Datei Identifizieren';
// / 'Size: '
$Gui2Text89 = 'Größe: ';
// / 'Modified: '
$Gui2Text90 = 'Geändert: ';
// / 'Type: '
$Gui2Text91 = 'Typ: ';
// / 'MIME Type: '
$Gui2Text92 = 'MIME-Typ: ';
// / 'Character Set: '
$Gui2Text93 = 'Zeichensatz: ';
// / 'Extension Matches Content: '
$Gui2Text94 = 'Endung Passt Zum Inhalt: ';
// / 'Details: '
$Gui2Text95 = 'Details: ';
// / 'Notes: '
$Gui2Text96 = 'Hinweise: ';
// / 'Save report as file'
$Gui2Text97 = 'Bericht als Datei speichern';
// / 'Identify All Files'
$Gui2Text98 = 'Alle Dateien Identifizieren';
// / 'Identify All'
$Gui2Text99 = 'Alle Identifizieren';
// / 'Yes'
$Gui2Text100 = 'Ja';
// / 'No'
$Gui2Text101 = 'Nein';
// / 'Unknown'
$Gui2Text102 = 'Unbekannt';
// / 'Cannot identify this file!'
$Gui2Text103 = 'Diese Datei kann nicht identifiziert werden!';
// / 'Sort By: '
$Gui2Text104 = 'Sortieren Nach: ';
// / 'Name'
$Gui2Text105 = 'Name';
// / 'Size'
$Gui2Text106 = 'Größe';
// / 'Date'
$Gui2Text107 = 'Datum';
// / 'Type'
$Gui2Text108 = 'Typ';
// / The words in braces, such as {fit} & {count}, are filled in by the page. Each translation keeps every one of them.
// / 'Select'
$Gui2Text109 = 'Auswählen';
// / 'Select All'
$Gui2Text110 = 'Alle Auswählen';
// / 'Select None'
$Gui2Text111 = 'Keine Auswählen';
// / 'Select By Type'
$Gui2Text112 = 'Nach Typ Auswählen';
// / 'Selected: '
$Gui2Text113 = 'Ausgewählt: ';
// / 'Documents'
$Gui2Text114 = 'Dokumente';
// / 'Spreadsheets'
$Gui2Text115 = 'Tabellen';
// / 'Presentations'
$Gui2Text116 = 'Präsentationen';
// / 'Images'
$Gui2Text117 = 'Bilder';
// / 'Audio'
$Gui2Text118 = 'Audio';
// / 'Video'
$Gui2Text119 = 'Video';
// / 'Archives'
$Gui2Text120 = 'Archive';
// / 'E-Books'
$Gui2Text121 = 'E-Books';
// / '3D Models'
$Gui2Text122 = '3D-Modelle';
// / 'Drawings'
$Gui2Text123 = 'Zeichnungen';
// / 'Vector Images'
$Gui2Text124 = 'Vektorgrafiken';
// / 'Subtitles'
$Gui2Text125 = 'Untertitel';
// / 'OpenSCAD Files'
$Gui2Text126 = 'OpenSCAD-Dateien';
// / 'Streams'
$Gui2Text127 = 'Streams';
// / 'Workflows'
$Gui2Text128 = 'Workflows';
// / 'Files'
$Gui2Text129 = 'Dateien';
// / 'Select files in the list below.'
$Gui2Text130 = 'Wählen Sie Dateien in der Liste unten aus.';
// / 'Select By Name: '
$Gui2Text131 = 'Nach Name Auswählen: ';
// / 'Use * and ? as wildcards, or write a regular expression between slashes.'
$Gui2Text132 = 'Verwenden Sie * und ? als Platzhalter oder schreiben Sie einen regulären Ausdruck zwischen Schrägstrichen.';
// / 'Workflow'
$Gui2Text133 = 'Workflow';
// / 'Choose a workflow'
$Gui2Text134 = 'Workflow auswählen';
// / 'Run'
$Gui2Text135 = 'Ausführen';
// / 'Stop'
$Gui2Text136 = 'Stopp';
// / 'Selected files that can be used: {fit} of {count}.'
$Gui2Text137 = 'Verwendbare ausgewählte Dateien: {fit} von {count}.';
// / 'Step {step} of {steps}: {operation}'
$Gui2Text138 = 'Schritt {step} von {steps}: {operation}';
// / 'File {done} of {total}'
$Gui2Text139 = 'Datei {done} von {total}';
// / 'Waiting for the server.'
$Gui2Text140 = 'Warte auf den Server.';
// / 'Starting.'
$Gui2Text141 = 'Wird gestartet.';
// / 'Finished.'
$Gui2Text142 = 'Fertig.';
// / 'Stopping.'
$Gui2Text143 = 'Wird angehalten.';
// / 'Stopped.'
$Gui2Text144 = 'Angehalten.';
// / 'Files made: '
$Gui2Text145 = 'Erstellte Dateien: ';
// / 'Workflows are not available on this server.'
$Gui2Text146 = 'Workflows sind auf diesem Server nicht verfügbar.';
// / 'No workflows are installed on this server.'
$Gui2Text147 = 'Auf diesem Server sind keine Workflows installiert.';
// / 'That workflow is no longer available.'
$Gui2Text148 = 'Dieser Workflow ist nicht mehr verfügbar.';
// / 'None of the selected files can be used by this workflow.'
$Gui2Text149 = 'Keine der ausgewählten Dateien kann von diesem Workflow verwendet werden.';
// / 'Too many files are selected for one workflow.'
$Gui2Text150 = 'Für einen Workflow sind zu viele Dateien ausgewählt.';
// / 'The server is busy. Try again in a few minutes.'
$Gui2Text151 = 'Der Server ist ausgelastet. Versuchen Sie es in ein paar Minuten erneut.';
// / 'A workflow is already running in this session.'
$Gui2Text152 = 'In dieser Sitzung läuft bereits ein Workflow.';
// / 'The workflow could not be started.'
$Gui2Text153 = 'Der Workflow konnte nicht gestartet werden.';
// / 'A step of the workflow failed.'
$Gui2Text154 = 'Ein Schritt des Workflows ist fehlgeschlagen.';
// / 'A virus was found, so the workflow stopped.'
$Gui2Text155 = 'Ein Virus wurde gefunden, daher wurde der Workflow angehalten.';
// / 'The workflow ran out of time.'
$Gui2Text156 = 'Die Zeit für den Workflow ist abgelaufen.';
// / 'The workflow made too many files.'
$Gui2Text157 = 'Der Workflow hat zu viele Dateien erstellt.';
// / 'The server was too busy to finish the workflow.'
$Gui2Text158 = 'Der Server war zu ausgelastet, um den Workflow abzuschließen.';
// / 'An operation this workflow needs is not available.'
$Gui2Text159 = 'Eine Operation, die dieser Workflow benötigt, ist nicht verfügbar.';
// / 'The workflow stopped unexpectedly.'
$Gui2Text160 = 'Der Workflow wurde unerwartet angehalten.';
// / The words in braces, such as {step} & {kind}, are filled in by the page. Each translation keeps every one of them.
// / 'Pipelines'
$Gui2Text161 = 'Pipelines';
// / 'Select a pipeline above...'
$Gui2Text162 = 'Wählen Sie oben eine Pipeline aus...';
// / 'Select Pipeline...'
$Gui2Text163 = 'Pipeline Auswählen...';
// / 'Select Files...'
$Gui2Text164 = 'Dateien Auswählen...';
// / 'Add a step'
$Gui2Text165 = 'Schritt hinzufügen';
// / 'Options'
$Gui2Text166 = 'Optionen';
// / 'Back'
$Gui2Text167 = 'Zurück';
// / 'Take: '
$Gui2Text168 = 'Verwenden: ';
// / 'the ticked files'
$Gui2Text169 = 'die markierten Dateien';
// / 'the files step {step} made'
$Gui2Text170 = 'die Dateien aus Schritt {step}';
// / 'each file on its own'
$Gui2Text171 = 'jede Datei einzeln';
// / 'all files together'
$Gui2Text172 = 'alle Dateien zusammen';
// / 'Only these kinds: '
$Gui2Text173 = 'Nur diese Arten: ';
// / 'Only these extensions: '
$Gui2Text174 = 'Nur diese Endungen: ';
// / 'Smallest size: '
$Gui2Text175 = 'Mindestgröße: ';
// / 'Largest size: '
$Gui2Text176 = 'Höchstgröße: ';
// / 'In the order they arrive'
$Gui2Text177 = 'In der Reihenfolge des Eingangs';
// / 'all files'
$Gui2Text178 = 'alle Dateien';
// / 'all {kind}'
$Gui2Text179 = 'alle {kind}';
// / 'Width: '
$Gui2Text180 = 'Breite: ';
// / 'Height: '
$Gui2Text181 = 'Höhe: ';
// / 'My workflow'
$Gui2Text182 = 'Mein Workflow';
// / 'New'
$Gui2Text183 = 'Neu';
// / 'Import'
$Gui2Text184 = 'Importieren';
// / 'Export'
$Gui2Text185 = 'Exportieren';
// / 'This workflow is not one this server can run.'
$Gui2Text186 = 'Diesen Workflow kann dieser Server nicht ausführen.';
// / 'The file could not be read.'
$Gui2Text187 = 'Die Datei konnte nicht gelesen werden.';
// / 'The workflow builder is not available on this server.'
$Gui2Text188 = 'Der Workflow-Editor ist auf diesem Server nicht verfügbar.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - HRC2-Functions.js Related Variables.
// / These strings are used by the client side javascript library.
// / That file is static javascript & cannot read a PHP variable, so every string it needs
// / is passed to it as an argument by the PHP that calls it.
// / 'Your browser does not support copying to the clipboard!'
$GuiFunctionsText1 = 'Ihr Browser unterstützt das Kopieren in die Zwischenablage nicht.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy'
$GuiFooterText1 = 'Lesen Sie unsere <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Nutzungsbedingungen</a> und <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Datenschutzrichtlinie';
// / -----------------------------------------------------------------------------------