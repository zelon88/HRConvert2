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
// / Compatibility chec takes place in convertCore.php, buildGui() function.
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
$CoreError = 'ERRORE!!! HRConvert2-2, questo file non può elaborare la tua richiesta! Invia invece il tuo file a convertCore.php!';
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die($CoreError);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Check for required core variables.
if (!isset($Font)) $Font = 'Arial';
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = 'Converti Qualsiasi Cosa!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI Related Logic.
if (!is_numeric($FileCount)) $FileCount = 0;
if ($FileCount === 0) $FCPlural1 = 'Hai caricato 0 file validi su '.$ApplicationName.'.';
if ($FileCount === 1) $FCPlural1 = 'Hai caricato 1 file valido su '.$ApplicationName.'.'; 
if ($FileCount >= 2) $FCPlural1 = 'Hai caricato '.$FileCount.' file validi su '.$ApplicationName.'.'; 
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Header Related Variables.
// / 'Click, Tap, or Drop files here to upload.'
$GuiHeaderText1 = 'Fai clic, tocca o trascina qui i file da caricare.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - UI Selector Bar Related Variables.
// / These strings appear in the selector bar, which is present on both GUI1 & GUI2.
// / 'Language'
$GuiSelectorText1 = 'Lingua';
// / 'Color'
$GuiSelectorText2 = 'Colore';
// / 'Interface'
$GuiSelectorText3 = 'Interfaccia';
// / 'Display language, color and interface options'
$GuiSelectorText4 = 'Mostra le opzioni di lingua, colore e interfaccia';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / 'Online File Converter, Extractor, Compressor'
$Gui1Text1 = 'Convertitore di file online, estrattore, compressore';
// / $ApplicationName.' is based off the open-source web-app <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> by <a href=\'https://github.com/zelon88\'>Zelon88</a> that converts files without tracking users across the net or infringing on your intellectual property.'
$Gui1Text2 = $ApplicationName.' si basa sull’app web open source <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> di <a href=\'https://github.com/zelon88\'>Zelon88</a> che converte i file senza tracciare gli utenti attraverso la rete o violare la tua proprietà intellettuale.';
// / 'More Info ...'
$Gui1Text3 = 'Ulteriori Informazioni...';
// / 'Less Info ...'
$Gui1Text4 = 'Meno Informazioni...';
// / 'All user-supplied data is erased automatically, so you don\'t need to worry about forfeiting your personal information or property while using our services.'
$Gui1Text5 = 'Tutti i dati forniti dall’utente vengono cancellati automaticamente, quindi non devi preoccuparti di perdere le tue informazioni personali o proprietà durante l’utilizzo dei nostri servizi.';
// / 'Currently '.$ApplicationName.' supports '.$SupportedFormatCount.' different file formats, including documents, spreadsheets, images, media, 3D models, CAD drawings, vector files, archives, disk images, & more.'
$Gui1Text6 = 'Attualmente '.$ApplicationName.' supporta '.$SupportedFormatCount.' diversi formati di file, inclusi documenti, fogli di calcolo, immagini, media, modelli 3D, disegni CAD, file vettoriali, archivi, immagini disco e altro.';
// / 'View Supported Formats ...'
$Gui1Text7 = 'Visualizza Formati Supportati...';
// / 'Hide Supported Formats ...'
$Gui1Text8 = 'Nascondi Formati Supportati...';
// / 'Supported Formats'
$Gui1Text9 = 'Formati Supportati';
// / 'Audio Formats'
$Gui1Text10 = 'Formati Audio';
// / 'Supports specific bitrate.'
$Gui1Text11 = 'Supporta un bitrate specifico.';
// / 'Video Formats'
$Gui1Text12 = 'Formati Video';
// / 'Stream Formats'
$Gui1Text13 = 'Formati Flusso';
// / 'Document Formats'
$Gui1Text14 = 'Formati Documento';
// / 'Spreadsheet Formats'
$Gui1Text15 = 'Formati Foglio Elettronico';
// / 'Presentation Formats'
$Gui1Text16 = 'Formati Presentazione';
// / 'Archive Formats'
$Gui1Text17 = 'Formati Archivio';
// / 'Can convert between archive formats & disk image formats.'
$Gui1Text18 = 'Può convertire tra formati di archivio selezionati e formati immagine disco.';
// / 'Image Formats'
$Gui1Text19 = 'Formati Immagine';
// / 'Can convert pictures of documents to document formats.'
$Gui1Text20 = 'Può convertire immagini di documenti in formati documento.';
// / 'Supports resize & rotate.'
$Gui1Text21 = 'Supporta ridimensionamento e rotazione.';
// / '3D Model Formats'
$Gui1Text22 = 'Formati Modello 3D';
// / 'Drawing Formats'
$Gui1Text23 = 'Formati Di Disegno';
// / 'Can convert drawing files to image formats.'
$Gui1Text24 = 'Può convertire formati disegno in formati immagine.';
// / 'OCR Support'
$Gui1Text25 = 'Supporto OCR';
// / 'OCR Operations support the following input formats...'
$Gui1Text26 = 'Le operazioni OCR supportano i seguenti formati di input...';
// / 'OCR Operations support the following output formats...'
$Gui1Text27 = 'Le operazioni OCR supportano i seguenti formati di output...';
// / 'Select files by clicking, tapping, or dropping them into the box below.'
$Gui1Text28 = 'Seleziona i file facendo clic, toccandoli o rilasciandoli nella casella sottostante.';
// / 'Continue ...'
$Gui1Text29 = 'Continua...';
// / 'Can convert stream formats to video formats.'
$Gui1Text30 = 'Può convertire formati stream in formati video.';
// / 'Subtitle Formats'
$Gui1Text31 = 'Formati Sottotitoli';
// / 'OpenSCAD Formats'
$Gui1Text32 = 'Formati OpenSCAD';
// / 'Renders OpenSCAD source into 3D model formats.'
$Gui1Text33 = 'Converte il codice sorgente OpenSCAD in formati di modelli 3D.';
// / 'File references inside uploaded sources are removed unless the server allows resolving them.'
$Gui1Text34 = 'I riferimenti a file contenuti nei sorgenti caricati vengono rimossi, a meno che il server non ne consenta la risoluzione.';
// / 'Delete every uploaded file & start a new session?'
// / Shown inside the start over panel on the upload page. That panel is only rendered
// / when the session already holds at least one file, so this is never shown to a first
// / time visitor who has nothing to lose.
$Gui1Text35 = 'Eliminare tutti i file caricati e avviare una nuova sessione?';
// / 'Start Over'
// / Labels the control that opens the panel & the button inside it that confirms.
$Gui1Text36 = 'Ricomincia';
// / 'Refresh'
// / Alternate text for the refresh control on the upload page. That control is a glyph
// / with no text of its own, so this is the only description a screen reader has & the
// / only thing shown when a browser cannot render the glyph.
$Gui1Text37 = 'Aggiorna';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'Opzioni Di Conversione File';
// / 'Bulk File Options'
$Gui2Text2 = 'Opzioni File In Blocco';
// / 'Scan All Files For Viruses'
$Gui2Text3 = 'Scansiona Tutti I File Alla Ricerca Di Virus';
// / 'Compress & Download All Files'
$Gui2Text4 = 'Comprimi e scarica tutti i file';
// / 'Download'
$Gui2Text5 = 'Scarica';
// / 'Share'
$Gui2Text6 = 'Condividi';
// / 'Close Share Options'
$Gui2Text7 = 'Chiudi Opzioni Condivisione';
// / 'Virus Scan'
$Gui2Text8 = 'Scansione Antivirus';
// / 'Close Virus Scan Options'
$Gui2Text9 = 'Chiudi opzioni scansione virus';
// / 'Archive'
$Gui2Text10 = 'Archivia';
// / 'Close Archive Options'
$Gui2Text11 = 'Chiudi Opzioni Archivio';
// / 'OCR'
$Gui2Text12 = 'OCR';
// / 'Close OCR Options'
$Gui2Text13 = 'Chiudi Opzioni OCR';
// / 'Convert'
$Gui2Text14 = 'Converti';
// / 'Close Convert Options'
$Gui2Text15 = 'Chiudi Opzioni Di Conversione';
// / 'Archive This File'
$Gui2Text16 = 'Archivia Questo File';
// / 'Specify Filename: '
$Gui2Text17 = 'Specifica Il Nome Del File: ';
// / 'Format'
$Gui2Text18 = 'Formato';
// / 'Compress & Download'
$Gui2Text19 = 'Comprimi E Scarica';
// / 'Scan with ClamAV: '
$Gui2Text20 = 'Scansiona con ClamAV: ';
// / 'Scan with ScanCore: '
$Gui2Text21 = 'Scansiona con ScanCore: ';
// / 'Scan All'
$Gui2Text22 = 'Scansiona Tutto';
// / 'Share This File'
$Gui2Text23 =  'Condividi Questo File';
// / 'Link Status: '
$Gui2Text24 = 'Stato Collegamento: ';
// / 'Not Generated'
$Gui2Text25 = 'Non Generato';
// / 'Generated'
$Gui2Text26 = 'Generato';
// / 'Clipboard Status: '
$Gui2Text27 = 'Stato Appunti: ';
// / 'Copied'
$Gui2Text28 = 'Copiato';
// / 'File Link: '
$Gui2Text29 = 'Collegamento File: ';
// / 'You have uploaded '.$FileCount.' valid file'.$FCPlural1.' to '.$ApplicationName.'.'
$Gui2Text30 = 'Hai caricato '.$FileCount.' file valido'.$FCPlural1.' a '.$ApplicationName.'.';
// / 'Your file'.$FCPlural2.' now ready to convert using the options below.'
$Gui2Text31 = 'Il tuo file'.$FCPlural2.' ora pronto per la conversione utilizzando le opzioni seguenti.';
// / 'Generate Link & Copy to Clipboard'
$Gui2Text32 = 'Genera Collegamento E Copia Negli Appunti';
// / 'Generate Link'
$Gui2Text33 = 'Genera Collegamento';
// / 'Scan This File For Viruses'
$Gui2Text34 = 'Scansiona Questo File Alla Ricerca Di Virus';
// / 'Scan File With ScanCore'
$Gui2Text35 = 'Scansiona File Con ScanCore';
// / 'Scan File With ClamAV'
$Gui2Text36 = 'Scansiona File Con ClamAV';
// / 'Scan File With ScanCore & ClamAV'
$Gui2Text37 = 'Scansiona File Con ScanCore & ClamAV';
// / 'Perform Optical Character Recognition On This File'
$Gui2Text38 = 'Esegui il riconoscimento ottico dei caratteri su questo file';
// / 'Method'
$Gui2Text39 = 'Metodo';
// / 'Simple'
$Gui2Text40 = 'Semplice';
// / 'Advanced'
$Gui2Text41 = 'Avanzate';
// / 'Convert This Archive'
$Gui2Text42 = 'Converti Questo Archivio';
// / 'Convert This Document'
$Gui2Text43 = 'Converti Questo Documento';
// / 'Convert This Spreadsheet'
$Gui2Text44 = 'Converti Questo Foglio Elettronico';
// / 'Convert This Audio'
$Gui2Text45 = 'Converti Questo Audio';
// / 'Convert This Video'
$Gui2Text46 = 'Converti Questo Video';
// / 'Convert This Stream'
$Gui2Text47 = 'Converti Questo Flusso';
// / 'Convert This 3D Model'
$Gui2Text48 = 'Converti Questo Modello 3D';
// / 'Convert This Technical Drawing Or Vector File'
$Gui2Text49 = 'Converti Questo Disegno Tecnico O File Vettoriale';
// / 'Convert This Image'
$Gui2Text50 = 'Converti Questa Immagine';
// / 'Archive File'
$Gui2Text51 = 'Archivia File';
// / 'Convert Into Document'
$Gui2Text52 = 'Converti In Documento';
// / 'Archive Files'
$Gui2Text53 = 'Archivia File';
// / 'Convert Document'
$Gui2Text54 = 'Converti Documento';
// / 'Convert Spreadsheet'
$Gui2Text55 = 'Converti Foglio Elettronico';
// / 'Convert Presentation'
$Gui2Text56 = 'Converti Presentazione';
// / 'Convert Audio'
$Gui2Text57 = 'Converti Audio';
// / 'Convert Video'
$Gui2Text58 = 'Converti Video';
// / 'Convert Stream'
$Gui2Text59 = 'Converti Stream';
// / 'Convert Model'
$Gui2Text60 = 'Converti Modello';
// / 'Convert Drawing'
$Gui2Text61 = 'Converti Disegno';
// / 'Convert Image'
$Gui2Text62 = 'Converti Immagine';
// / 'Width & Height'
$Gui2Text64 = 'Larghezza E Altezza: ';
// / 'Rotate: '
$Gui2Text65 = 'Ruota: ';
// / 'Bitrate: '
$Gui2Text66 = 'Bitrate: ';
// / 'Delete'
$Gui2Text67 = 'Elimina';
// / 'Close Delete Options'
$Gui2Text68 = 'Chiudi Elimina Opzioni';
// / 'Delete This File'
$Gui2Text69 = 'Elimina Questo File';
// / 'Confirm Delete'
$Gui2Text70 = 'Conferma Eliminazione';
// / 'Cannot convert this file! Try changing the name.'
$Gui2Text71 = 'Impossibile convertire questo file! Prova a cambiare il nome.';
// / 'Cannot perform a virus scan on this file!'
$Gui2Text72 = 'Impossibile eseguire una scansione antivirus su questo file!';
// / 'File Link Copied to Clipboard!'
$Gui2Text73 = 'File Link Copiato negli Appunti!';
// / 'Operation Failed!'
$Gui2Text74 = 'Operazione Fallita!';
// / 'Convert These Subtitles'
$Gui2Text75 = 'Converti Questi Sottotitoli';
// / 'Convert Subtitles'
$Gui2Text76 = 'Converti Sottotitoli';
// / 'Convert This Presentation'
$Gui2Text77 = 'Converti Questa Presentazione';
// / 'Convert This XPS File'
$Gui2Text78 = 'Converti Questo XPS';
// / 'Render This OpenSCAD Model'
$Gui2Text79 = 'Genera Questo Modello OpenSCAD';
// / 'Render Model'
$Gui2Text80 = 'Genera Modello';
// / 'Convert This E-Book'
$Gui2Text81 = 'Converti questo e-book';
// / 'Convert E-Book'
$Gui2Text82 = 'Converti e-book';
// / 'Playlist source URL, only needed if the playlist uses relative paths'
$Gui2Text83 = 'URL di origine della playlist, necessario solo se usa percorsi relativi';
// / 'Download A File From A URL'
$Gui2Text84 = 'Scarica un file da un URL';
// / 'Fetch'
$Gui2Text85 = 'Preleva';
// / 'Identify'
$Gui2Text86 = 'Identifica';
// / 'Close Identify Options'
$Gui2Text87 = 'Chiudi opzioni di identificazione';
// / 'Identify This File'
$Gui2Text88 = 'Identifica Questo File';
// / 'Size: '
$Gui2Text89 = 'Dimensione: ';
// / 'Modified: '
$Gui2Text90 = 'Modificato: ';
// / 'Type: '
$Gui2Text91 = 'Tipo: ';
// / 'MIME Type: '
$Gui2Text92 = 'Tipo MIME: ';
// / 'Character Set: '
$Gui2Text93 = 'Set Di Caratteri: ';
// / 'Extension Matches Content: '
$Gui2Text94 = 'L’Estensione Corrisponde Al Contenuto: ';
// / 'Details: '
$Gui2Text95 = 'Dettagli: ';
// / 'Notes: '
$Gui2Text96 = 'Note: ';
// / 'Save report as file'
$Gui2Text97 = 'Salva il rapporto come file';
// / 'Identify All Files'
$Gui2Text98 = 'Identifica Tutti I File';
// / 'Identify All'
$Gui2Text99 = 'Identifica Tutto';
// / 'Yes'
$Gui2Text100 = 'Sì';
// / 'No'
$Gui2Text101 = 'No';
// / 'Unknown'
$Gui2Text102 = 'Sconosciuto';
// / 'Cannot identify this file!'
$Gui2Text103 = 'Impossibile identificare questo file!';
// / 'Sort By: '
$Gui2Text104 = 'Ordina Per: ';
// / 'Name'
$Gui2Text105 = 'Nome';
// / 'Size'
$Gui2Text106 = 'Dimensione';
// / 'Date'
$Gui2Text107 = 'Data';
// / 'Type'
$Gui2Text108 = 'Tipo';
// / The words in braces, such as {fit} & {count}, are filled in by the page. Each translation keeps every one of them.
// / 'Select'
$Gui2Text109 = 'Seleziona';
// / 'Select All'
$Gui2Text110 = 'Seleziona Tutto';
// / 'Select None'
$Gui2Text111 = 'Deseleziona Tutto';
// / 'Select By Type'
$Gui2Text112 = 'Seleziona Per Tipo';
// / 'Selected: '
$Gui2Text113 = 'Selezionati: ';
// / 'Documents'
$Gui2Text114 = 'Documenti';
// / 'Spreadsheets'
$Gui2Text115 = 'Fogli Di Calcolo';
// / 'Presentations'
$Gui2Text116 = 'Presentazioni';
// / 'Images'
$Gui2Text117 = 'Immagini';
// / 'Audio'
$Gui2Text118 = 'Audio';
// / 'Video'
$Gui2Text119 = 'Video';
// / 'Archives'
$Gui2Text120 = 'Archivi';
// / 'E-Books'
$Gui2Text121 = 'E-Book';
// / '3D Models'
$Gui2Text122 = 'Modelli 3D';
// / 'Drawings'
$Gui2Text123 = 'Disegni';
// / 'Vector Images'
$Gui2Text124 = 'Immagini Vettoriali';
// / 'Subtitles'
$Gui2Text125 = 'Sottotitoli';
// / 'OpenSCAD Files'
$Gui2Text126 = 'File OpenSCAD';
// / 'Streams'
$Gui2Text127 = 'Flussi';
// / 'Workflows'
$Gui2Text128 = 'Flussi Di Lavoro';
// / 'Files'
$Gui2Text129 = 'File';
// / 'Select files in the list below.'
$Gui2Text130 = 'Seleziona i file nell’elenco qui sotto.';
// / 'Select By Name: '
$Gui2Text131 = 'Seleziona Per Nome: ';
// / 'Use * and ? as wildcards, or write a regular expression between slashes.'
$Gui2Text132 = 'Usa * e ? come caratteri jolly, oppure scrivi un’espressione regolare tra due barre.';
// / 'Workflow'
$Gui2Text133 = 'Flusso Di Lavoro';
// / 'Choose a workflow'
$Gui2Text134 = 'Scegli un flusso di lavoro';
// / 'Run'
$Gui2Text135 = 'Esegui';
// / 'Stop'
$Gui2Text136 = 'Ferma';
// / 'Selected files that can be used: {fit} of {count}.'
$Gui2Text137 = 'File selezionati utilizzabili: {fit} di {count}.';
// / 'Step {step} of {steps}: {operation}'
$Gui2Text138 = 'Passo {step} di {steps}: {operation}';
// / 'File {done} of {total}'
$Gui2Text139 = 'File {done} di {total}';
// / 'Waiting for the server.'
$Gui2Text140 = 'In attesa del server.';
// / 'Starting.'
$Gui2Text141 = 'Avvio in corso.';
// / 'Finished.'
$Gui2Text142 = 'Completato.';
// / 'Stopping.'
$Gui2Text143 = 'Arresto in corso.';
// / 'Stopped.'
$Gui2Text144 = 'Fermato.';
// / 'Files made: '
$Gui2Text145 = 'File creati: ';
// / 'Workflows are not available on this server.'
$Gui2Text146 = 'I flussi di lavoro non sono disponibili su questo server.';
// / 'No workflows are installed on this server.'
$Gui2Text147 = 'Su questo server non è installato alcun flusso di lavoro.';
// / 'That workflow is no longer available.'
$Gui2Text148 = 'Quel flusso di lavoro non è più disponibile.';
// / 'None of the selected files can be used by this workflow.'
$Gui2Text149 = 'Nessuno dei file selezionati può essere usato da questo flusso di lavoro.';
// / 'Too many files are selected for one workflow.'
$Gui2Text150 = 'Sono selezionati troppi file per un solo flusso di lavoro.';
// / 'The server is busy. Try again in a few minutes.'
$Gui2Text151 = 'Il server è occupato. Riprova tra qualche minuto.';
// / 'A workflow is already running in this session.'
$Gui2Text152 = 'In questa sessione è già in esecuzione un flusso di lavoro.';
// / 'The workflow could not be started.'
$Gui2Text153 = 'Impossibile avviare il flusso di lavoro.';
// / 'A step of the workflow failed.'
$Gui2Text154 = 'Un passo del flusso di lavoro non è riuscito.';
// / 'A virus was found, so the workflow stopped.'
$Gui2Text155 = 'È stato trovato un virus, quindi il flusso di lavoro si è fermato.';
// / 'The workflow ran out of time.'
$Gui2Text156 = 'Il flusso di lavoro ha esaurito il tempo.';
// / 'The workflow made too many files.'
$Gui2Text157 = 'Il flusso di lavoro ha creato troppi file.';
// / 'The server was too busy to finish the workflow.'
$Gui2Text158 = 'Il server era troppo occupato per completare il flusso di lavoro.';
// / 'An operation this workflow needs is not available.'
$Gui2Text159 = 'Un’operazione necessaria a questo flusso di lavoro non è disponibile.';
// / 'The workflow stopped unexpectedly.'
$Gui2Text160 = 'Il flusso di lavoro si è fermato inaspettatamente.';
// / The words in braces, such as {step} & {kind}, are filled in by the page. Each translation keeps every one of them.
// / 'Pipelines'
$Gui2Text161 = 'Pipeline';
// / 'Select a pipeline above...'
$Gui2Text162 = 'Seleziona una pipeline qui sopra...';
// / 'Select Pipeline...'
$Gui2Text163 = 'Seleziona Pipeline...';
// / 'Select Files...'
$Gui2Text164 = 'Seleziona File...';
// / 'Add a step'
$Gui2Text165 = 'Aggiungi un passaggio';
// / 'Options'
$Gui2Text166 = 'Opzioni';
// / 'Back'
$Gui2Text167 = 'Indietro';
// / 'Take: '
$Gui2Text168 = 'Prendi: ';
// / 'the ticked files'
$Gui2Text169 = 'i file spuntati';
// / 'the files step {step} made'
$Gui2Text170 = 'i file creati dal passaggio {step}';
// / 'each file on its own'
$Gui2Text171 = 'ogni file separatamente';
// / 'all files together'
$Gui2Text172 = 'tutti i file insieme';
// / 'Only these kinds: '
$Gui2Text173 = 'Solo questi tipi: ';
// / 'Only these extensions: '
$Gui2Text174 = 'Solo queste estensioni: ';
// / 'Smallest size: '
$Gui2Text175 = 'Dimensione minima: ';
// / 'Largest size: '
$Gui2Text176 = 'Dimensione massima: ';
// / 'In the order they arrive'
$Gui2Text177 = 'Nell’ordine in cui arrivano';
// / 'all files'
$Gui2Text178 = 'tutti i file';
// / 'all {kind}'
$Gui2Text179 = 'tutti i file: {kind}';
// / 'Width: '
$Gui2Text180 = 'Larghezza: ';
// / 'Height: '
$Gui2Text181 = 'Altezza: ';
// / 'My workflow'
$Gui2Text182 = 'Il mio flusso di lavoro';
// / 'New'
$Gui2Text183 = 'Nuovo';
// / 'Import'
$Gui2Text184 = 'Importa';
// / 'Export'
$Gui2Text185 = 'Esporta';
// / 'This workflow is not one this server can run.'
$Gui2Text186 = 'Questo server non può eseguire questo flusso di lavoro.';
// / 'The file could not be read.'
$Gui2Text187 = 'Impossibile leggere il file.';
// / 'The workflow builder is not available on this server.'
$Gui2Text188 = 'L’editor dei flussi di lavoro non è disponibile su questo server.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - HRC2-Functions.js Related Variables.
// / These strings are used by the client side javascript library.
// / That file is static javascript & cannot read a PHP variable, so every string it needs
// / is passed to it as an argument by the PHP that calls it.
// / 'Your browser does not support copying to the clipboard!'
$GuiFunctionsText1 = 'Il tuo browser non supporta la copia negli appunti.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy'
$GuiFooterText1 = 'Consulta i nostri <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Termini di servizio</a> e i <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Informativa sulla privacy';
// / -----------------------------------------------------------------------------------