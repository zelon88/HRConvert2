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
$LanguageVersion = 'v4.1.7';
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
// / Set GUI - UI Selector Bar Related Variables.
// / These strings appear in the selector bar, which is present on both GUI1 & GUI2.
// / 'Language'
$GuiSelectorText1 = 'Taal';
// / 'Color'
$GuiSelectorText2 = 'Kleur';
// / 'Interface'
$GuiSelectorText3 = 'Interface';
// / 'Display language, color and interface options'
$GuiSelectorText4 = 'Opties voor taal, kleur en interface weergeven';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / 'Online File Converter, Extractor, Compressor'
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
// / 'Delete every uploaded file & start a new session?'
// / Shown inside the start over panel on the upload page. That panel is only rendered
// / when the session already holds at least one file, so this is never shown to a first
// / time visitor who has nothing to lose.
$Gui1Text35 = 'Alle geüploade bestanden verwijderen en een nieuwe sessie starten?';
// / 'Start Over'
// / Labels the control that opens the panel & the button inside it that confirms.
$Gui1Text36 = 'Opnieuw Beginnen';
// / 'Refresh'
// / Alternate text for the refresh control on the upload page. That control is a glyph
// / with no text of its own, so this is the only description a screen reader has & the
// / only thing shown when a browser cannot render the glyph.
$Gui1Text37 = 'Vernieuwen';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'Bestandsconversie Opties';
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
// / 'Convert This 3D Model'
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
$Gui2Text80 = 'Model Renderen';
// / 'Convert This E-Book'
$Gui2Text81 = 'Dit e-boek converteren';
// / 'Convert E-Book'
$Gui2Text82 = 'E-boek converteren';
// / 'Playlist source URL, only needed if the playlist uses relative paths'
$Gui2Text83 = 'Bron-URL van de afspeellijst, alleen nodig bij relatieve paden';
// / 'Download A File From A URL'
$Gui2Text84 = 'Een bestand downloaden van een URL';
// / 'Fetch'
$Gui2Text85 = 'Ophalen';
// / 'Identify'
$Gui2Text86 = 'Identificeren';
// / 'Close Identify Options'
$Gui2Text87 = 'Sluit Identificatie Opties';
// / 'Identify This File'
$Gui2Text88 = 'Identificeer Dit Bestand';
// / 'Size: '
$Gui2Text89 = 'Grootte: ';
// / 'Modified: '
$Gui2Text90 = 'Gewijzigd: ';
// / 'Type: '
$Gui2Text91 = 'Type: ';
// / 'MIME Type: '
$Gui2Text92 = 'MIME-Type: ';
// / 'Character Set: '
$Gui2Text93 = 'Tekenset: ';
// / 'Extension Matches Content: '
$Gui2Text94 = 'Extensie Komt Overeen Met Inhoud: ';
// / 'Details: '
$Gui2Text95 = 'Details: ';
// / 'Notes: '
$Gui2Text96 = 'Opmerkingen: ';
// / 'Save report as file'
$Gui2Text97 = 'Rapport als bestand opslaan';
// / 'Yes'
$Gui2Text100 = 'Ja';
// / 'No'
$Gui2Text101 = 'Nee';
// / 'Unknown'
$Gui2Text102 = 'Onbekend';
// / 'Cannot identify this file!'
$Gui2Text103 = 'Kan dit bestand niet identificeren!';
// / 'Sort By: '
$Gui2Text104 = 'Sorteren Op: ';
// / 'Name'
$Gui2Text105 = 'Naam';
// / 'Size'
$Gui2Text106 = 'Grootte';
// / 'Date'
$Gui2Text107 = 'Datum';
// / 'Type'
$Gui2Text108 = 'Type';
// / The words in braces, such as {fit} & {count}, are filled in by the page. Each translation keeps every one of them.
// / 'Select'
$Gui2Text109 = 'Selecteren';
// / 'Select All'
$Gui2Text110 = 'Alles Selecteren';
// / 'Select None'
$Gui2Text111 = 'Niets Selecteren';
// / 'Select By Type'
$Gui2Text112 = 'Selecteren Op Type';
// / 'Selected: '
$Gui2Text113 = 'Geselecteerd: ';
// / 'Documents'
$Gui2Text114 = 'Documenten';
// / 'Spreadsheets'
$Gui2Text115 = 'Spreadsheets';
// / 'Presentations'
$Gui2Text116 = 'Presentaties';
// / 'Images'
$Gui2Text117 = 'Afbeeldingen';
// / 'Audio'
$Gui2Text118 = 'Audio';
// / 'Video'
$Gui2Text119 = 'Video';
// / 'Archives'
$Gui2Text120 = 'Archieven';
// / 'E-Books'
$Gui2Text121 = 'E-books';
// / '3D Models'
$Gui2Text122 = '3D-modellen';
// / 'Drawings'
$Gui2Text123 = 'Tekeningen';
// / 'Vector Images'
$Gui2Text124 = 'Vectorafbeeldingen';
// / 'Subtitles'
$Gui2Text125 = 'Ondertitels';
// / 'OpenSCAD Files'
$Gui2Text126 = 'OpenSCAD-bestanden';
// / 'Streams'
$Gui2Text127 = 'Streams';
// / 'Workflows'
$Gui2Text128 = 'Workflows';
// / 'Files'
$Gui2Text129 = 'Bestanden';
// / 'Select files in the list below.'
$Gui2Text130 = 'Selecteer bestanden in de lijst hieronder.';
// / 'Select By Name: '
$Gui2Text131 = 'Selecteren Op Naam: ';
// / 'Use * and ? as wildcards, or write a regular expression between slashes.'
$Gui2Text132 = 'Gebruik * en ? als jokertekens, of schrijf een reguliere expressie tussen schuine strepen.';
// / 'Workflow'
$Gui2Text133 = 'Workflow';
// / 'Choose a workflow'
$Gui2Text134 = 'Kies een workflow';
// / 'Run'
$Gui2Text135 = 'Uitvoeren';
// / 'Stop'
$Gui2Text136 = 'Stoppen';
// / 'Selected files that can be used: {fit} of {count}.'
$Gui2Text137 = 'Bruikbare geselecteerde bestanden: {fit} van {count}.';
// / 'Step {step} of {steps}: {operation}'
$Gui2Text138 = 'Stap {step} van {steps}: {operation}';
// / 'File {done} of {total}'
$Gui2Text139 = 'Bestand {done} van {total}';
// / 'Waiting for the server.'
$Gui2Text140 = 'Wachten op de server.';
// / 'Starting.'
$Gui2Text141 = 'Bezig met starten.';
// / 'Finished.'
$Gui2Text142 = 'Voltooid.';
// / 'Stopping.'
$Gui2Text143 = 'Bezig met stoppen.';
// / 'Stopped.'
$Gui2Text144 = 'Gestopt.';
// / 'Files made: '
$Gui2Text145 = 'Gemaakte bestanden: ';
// / 'Workflows are not available on this server.'
$Gui2Text146 = 'Workflows zijn niet beschikbaar op deze server.';
// / 'No workflows are installed on this server.'
$Gui2Text147 = 'Er zijn geen workflows geïnstalleerd op deze server.';
// / 'That workflow is no longer available.'
$Gui2Text148 = 'Die workflow is niet meer beschikbaar.';
// / 'None of the selected files can be used by this workflow.'
$Gui2Text149 = 'Geen van de geselecteerde bestanden kan door deze workflow worden gebruikt.';
// / 'Too many files are selected for one workflow.'
$Gui2Text150 = 'Er zijn te veel bestanden geselecteerd voor één workflow.';
// / 'The server is busy. Try again in a few minutes.'
$Gui2Text151 = 'De server is bezet. Probeer het over een paar minuten opnieuw.';
// / 'A workflow is already running in this session.'
$Gui2Text152 = 'Er loopt al een workflow in deze sessie.';
// / 'The workflow could not be started.'
$Gui2Text153 = 'De workflow kon niet worden gestart.';
// / 'A step of the workflow failed.'
$Gui2Text154 = 'Een stap van de workflow is mislukt.';
// / 'A virus was found, so the workflow stopped.'
$Gui2Text155 = 'Er is een virus gevonden, dus de workflow is gestopt.';
// / 'The workflow ran out of time.'
$Gui2Text156 = 'De workflow had geen tijd meer.';
// / 'The workflow made too many files.'
$Gui2Text157 = 'De workflow heeft te veel bestanden gemaakt.';
// / 'The server was too busy to finish the workflow.'
$Gui2Text158 = 'De server was te druk om de workflow af te ronden.';
// / 'An operation this workflow needs is not available.'
$Gui2Text159 = 'Een bewerking die deze workflow nodig heeft, is niet beschikbaar.';
// / 'The workflow stopped unexpectedly.'
$Gui2Text160 = 'De workflow is onverwacht gestopt.';
// / The words in braces, such as {step} & {kind}, are filled in by the page. Each translation keeps every one of them.
// / 'Pipelines'
$Gui2Text161 = 'Pijplijnen';
// / 'Select a pipeline above...'
$Gui2Text162 = 'Selecteer hierboven een pijplijn...';
// / 'Select Pipeline...'
$Gui2Text163 = 'Pijplijn Selecteren...';
// / 'Select Files...'
$Gui2Text164 = 'Bestanden Selecteren...';
// / 'Add a step'
$Gui2Text165 = 'Een stap toevoegen';
// / 'Options'
$Gui2Text166 = 'Opties';
// / 'Back'
$Gui2Text167 = 'Terug';
// / 'Take: '
$Gui2Text168 = 'Neem: ';
// / 'the ticked files'
$Gui2Text169 = 'de aangevinkte bestanden';
// / 'the files step {step} made'
$Gui2Text170 = 'de bestanden die stap {step} maakte';
// / 'each file on its own'
$Gui2Text171 = 'elk bestand afzonderlijk';
// / 'all files together'
$Gui2Text172 = 'alle bestanden samen';
// / 'Only these kinds: '
$Gui2Text173 = 'Alleen deze soorten: ';
// / 'Only these extensions: '
$Gui2Text174 = 'Alleen deze extensies: ';
// / 'Smallest size: '
$Gui2Text175 = 'Minimale grootte: ';
// / 'Largest size: '
$Gui2Text176 = 'Maximale grootte: ';
// / 'In the order they arrive'
$Gui2Text177 = 'In de volgorde waarin ze binnenkomen';
// / 'all files'
$Gui2Text178 = 'alle bestanden';
// / 'all {kind}'
$Gui2Text179 = 'alle {kind}';
// / 'Width: '
$Gui2Text180 = 'Breedte: ';
// / 'Height: '
$Gui2Text181 = 'Hoogte: ';
// / 'My workflow'
$Gui2Text182 = 'Mijn workflow';
// / 'New'
$Gui2Text183 = 'Nieuw';
// / 'Import'
$Gui2Text184 = 'Importeren';
// / 'Export'
$Gui2Text185 = 'Exporteren';
// / 'This workflow is not one this server can run.'
$Gui2Text186 = 'Deze server kan deze workflow niet uitvoeren.';
// / 'The file could not be read.'
$Gui2Text187 = 'Het bestand kon niet worden gelezen.';
// / 'The workflow builder is not available on this server.'
$Gui2Text188 = 'De workfloweditor is niet beschikbaar op deze server.';
// / 'Status'
$Gui2Text189 = 'Status';
// / 'A workflow is running.'
$Gui2Text190 = 'Er loopt een workflow.';
// / 'No workflow is running.'
$Gui2Text191 = 'Er loopt geen workflow.';
// / 'Extract'
$Gui2Text192 = 'Uitpakken';
// / 'Close Extract Options'
$Gui2Text193 = 'Sluit Uitpakopties';
// / 'Extract This Archive'
$Gui2Text194 = 'Pak Dit Archief Uit';
// / 'Every file it holds is added to your files, under its own name or the next free one.'
$Gui2Text195 = 'Elk bestand erin komt bij je bestanden, onder zijn eigen naam of de volgende vrije naam.';
// / 'Extract Files'
$Gui2Text196 = 'Pak Bestanden Uit';
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
