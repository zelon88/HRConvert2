<?php
// / -----------------------------------------------------------------------------------
// / APPLICATION INFORMATION ...
// / HRConvert2, Copyright on 4/18/2023 by Justin Grimes, www.github.com/zelon88
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
// / v3.2.6.
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
// / Set GUI - 1 Related Variables.
// / Online File Converter, Extractor, Compressor'
$Gui1Text1 = 'Convertitore di file online, estrattore, compressore';
// / $ApplicationName.' is based off the open-source web-app <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> by <a href=\'https://github.com/zelon88\'>Zelon88</a> that converts files without tracking users across the net or infringing on your intellectual property.'
$Gui1Text2 = $ApplicationName.' si basa sull\'app web open source <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> di <a href=\'https://github.com/ zelon88\'>Zelon88</a> che converte i file senza tracciare gli utenti attraverso la rete o violare la tua proprietà intellettuale.';
// / 'More Info ...'
$Gui1Text3 = 'Ulteriori Informazioni...';
// / 'Less Info ...'
$Gui1Text4 = 'Meno Informazioni...';
// / 'All user-supplied data is erased automatically, so you don\'t need to worry about forfeiting your personal information or property while using our services.'
$Gui1Text5 = 'Tutti i dati forniti dall\'utente vengono cancellati automaticamente, quindi non devi preoccuparti di perdere le tue informazioni personali o proprietà durante l\'utilizzo dei nostri servizi.';
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
$Gui2Text36 = 'Scan File With ClamAV';
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
// / Convert This 3D Model'
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
// / Convert These Subtitles'
$Gui2Text75 = 'Converti Questi Sottotitoli';
// / Convert Subtitles'
$Gui2Text76 = 'Converti Sottotitoli';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy'
$GuiFooterText1 = 'Consulta i nostri <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Termini di servizio</a> e i <a href=\ \''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Informativa sulla privacy';
// / -----------------------------------------------------------------------------------