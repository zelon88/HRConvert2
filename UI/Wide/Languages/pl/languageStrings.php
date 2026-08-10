<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRConvert2, Copyright on 8/10/2026 by Justin Grimes, www.github.com/zelon88
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
// / v3.6.4.
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
$LanguageVersion = 'v3.6.4';
$LanguageVersion = ltrim($LanguageVersion, 'vV');
// / Set the reading direction for text on the page.
$GUIDirection = 'ltr';
// / Set the side of the page to float elements to.
$GUIAlignment = 'left';
// / Define an error message to display for if the core has not been loaded.
$CoreError = 'BŁĄD!!! HRConvert2-2, Ten plik nie może przetworzyć Twojego żądania! Proszę przesłać swój plik do convertCore.php!';
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die($CoreError);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Check for required core variables.
if (!isset($Font)) $Font = 'Arial';
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = 'Konwertuj Wszystko!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI Related Logic.
if (!is_numeric($FileCount)) $FileCount = 'nieznaną liczbę';
$FCPlural1 = '';
$FCPlural2 = '';
if ($FileCount == 1) {
  $FCPlural1 = '';
  $FCPlural2 = ''; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Header Related Variables.
// / 'Click, Tap, or Drop files here to upload.'
$GuiHeaderText1 = 'Kliknij, Dotknij lub Upuść pliki tutaj, aby przesłać.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - UI Selector Bar Related Variables.
// / These strings appear in the selector bar, which is present on both GUI1 & GUI2.
// / 'Language'
$GuiSelectorText1 = 'Język';
// / 'Color'
$GuiSelectorText2 = 'Kolor';
// / 'Interface'
$GuiSelectorText3 = 'Interfejs';
// / 'Display language, color and interface options'
$GuiSelectorText4 = 'Pokaż opcje języka, koloru i interfejsu';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set a flag to tell that the UI has been displayed.
$LanguageStringsLoaded = TRUE;
// / Set the reading direction for text on the page.
$GUIDirection = 'ltr';
// / Set the side of the page to float elements to.
$GUIAlignment = 'left';
// / Define an error message to display for if the core has not been loaded.
$CoreError = 'BŁĄD!!! HRConvert2-2, Ten plik nie może przetworzyć Twojego żądania! Proszę przesłać swój plik do convertCore.php!';
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die($CoreError);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Check for required core variables.
if (!isset($Font)) $Font = 'Arial';
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = 'Konwertuj Wszystko!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI Related Logic.
if (!is_numeric($FileCount)) $FileCount = 'nieznaną liczbę';
$FCPlural1 = 'ów';
$FCPlural2 = 'i są';
if ($FileCount == 1) {
  $FCPlural1 = '';
  $FCPlural2 = ''; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Header Related Variables.
// / 'Click, Tap, or Drop files here to upload.'
$GuiHeaderText1 = 'Kliknij, Dotknij lub Upuść pliki tutaj, aby przesłać.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / Online File Converter, Extractor, Compressor'
$Gui1Text1 = 'Konwerter Plików, Ekstraktor, Kompresor Online';
// / $ApplicationName.' is based off the open-source web-app <a href=\'https://github.com\'>HRConvert2</a> by <a href=\'https://github.com\'>Zelon88</a> that converts files without tracking users across the net or infringing on your intellectual property.'
$Gui1Text2 = $ApplicationName.' bazuje na otwartej aplikacji internetowej <a href=\'https://github.com\'>HRConvert2</a> autorstwa <a href=\'https://github.com\'>Zelon88</a>, która konwertuje pliki bez śledzenia użytkowników w sieci i bez naruszania Twojej własności intelektualnej.';
// / 'More Info ...'
$Gui1Text3 = 'Więcej Informacji ...';
// / 'Less Info ...'
$Gui1Text4 = 'Mniej Informacji ...';
// / 'All user-supplied data is erased automatically, so you don\'t need to worry about forfeiting your personal information or property while using our services.'
$Gui1Text5 = 'Wszystkie dane dostarczone przez użytkownika są usuwane automatycznie, więc nie musisz się martwić o utratę swoich danych osobowych lub własności podczas korzystania z naszych usług.';
// / 'Currently '.$ApplicationName.' supports '.$SupportedFormatCount.' different file formats, including documents, spreadsheets, images, media, 3D models, CAD drawings, vector files, archives, disk images, & more.'
$Gui1Text6 = 'Obecnie '.$ApplicationName.' obsługuje '.$SupportedFormatCount.' różnych formatów plików, w tym dokumenty, arkusze kalkulacyjne, obrazy, multimedia, modele 3D, rysunki CAD, pliki wektorowe, archiwa, obrazy płyt i inne.';
// / 'View Supported Formats ...'
$Gui1Text7 = 'Zobacz Obsługiwane Formaty ...';
// / 'Hide Supported Formats ...'
$Gui1Text8 = 'Ukryj Obsługiwane Formaty ...';
// / 'Supported Formats'
$Gui1Text9 = 'Obsługiwane Formaty';
// / 'Audio Formats'
$Gui1Text10 = 'Formaty Audio';
// / 'Supports specific bitrate.'
$Gui1Text11 = 'Obsługuje określony bitrate.';
// / 'Video Formats'
$Gui1Text12 = 'Formaty Wideo';
// / 'Stream Formats'
$Gui1Text13 = 'Formaty Strumieni';
// / 'Document Formats'
$Gui1Text14 = 'Formaty Dokumentów';
// / 'Spreadsheet Formats'
$Gui1Text15 = 'Formaty Arkuszy Kalkulacyjnych';
// / 'Presentation Formats'
$Gui1Text16 = 'Formaty Prezentacji';
// / 'Archive Formats'
$Gui1Text17 = 'Formaty Archiwów';
// / 'Can convert between archive formats & disk image formats.'
$Gui1Text18 = 'Może konwertować między wybranymi formatami archiwów a formatami obrazów płyt.';
// / 'Image Formats'
$Gui1Text19 = 'Formaty Obrazów';
// / 'Can convert pictures of documents to document formats.'
$Gui1Text20 = 'Może konwertować zdjęcia dokumentów na formaty dokumentów.';
// / 'Supports resize & rotate.'
$Gui1Text21 = 'Obsługuje zmianę rozmiaru i obracanie.';
// / '3D Model Formats'
$Gui1Text22 = 'Formaty Modeli 3D';
// / 'Drawing Formats'
$Gui1Text23 = 'Formaty Rysunków';
// / 'Can convert drawing files to image formats.'
$Gui1Text24 = 'Może konwertować formaty rysunków na formaty obrazów.';
// / 'OCR Support'
$Gui1Text25 = 'Obsługa OCR';
// / 'OCR Operations support the following input formats...'
$Gui1Text26 = 'Operacje OCR obsługują następujące formaty wejściowe...';
// / 'OCR Operations support the following output formats...'
$Gui1Text27 = 'Operacje OCR obsługują następujące formaty wyjściowe...';
// / 'Select files by clicking, tapping, or dropping them into the box below.'
$Gui1Text28 = 'Wybierz pliki klikając, dotykając lub upuszczając je w poniższym polu.';
// / 'Continue ...'
$Gui1Text29 = 'Kontynuuj ...';
// / 'Can convert stream formats to video formats.'
$Gui1Text30 = 'Może konwertować formaty strumieni na formaty wideo.';
// / 'Subtitle Formats'
$Gui1Text31 = 'Formaty Napisów';
// / 'OpenSCAD Formats'
$Gui1Text32 = 'Formaty OpenSCAD';
// / 'Renders OpenSCAD source into 3D model formats.'
$Gui1Text33 = 'Renderuje źródło OpenSCAD do formatów modeli 3D.';
// / 'File references inside uploaded sources are removed unless the server allows resolving them.'
$Gui1Text34 = 'Odniesienia do plików w przesłanych źródłach są usuwane, chyba że serwer pozwala na ich sprawdzanie.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'Opcje Konwersji Plików';
// / 'Bulk File Options'
$Gui2Text2 = 'Masowe Opcje Plików';
// / 'Scan All Files For Viruses'
$Gui2Text3 = 'Skanuj Wszystkie Pliki w Poszukiwaniu Wirusów';
// / 'Compress & Download All Files'
$Gui2Text4 = 'Skompresuj i Pobierz Wszystkie Pliki';
// / 'Download'
$Gui2Text5 = 'Pobierz';
// / 'Share'
$Gui2Text6 = 'Udostępnij';
// / 'Close Share Options'
$Gui2Text7 = 'Zamknij Opcje Udostępniania';
// / 'Virus Scan'
$Gui2Text8 = 'Skanowanie Antywirusowe';
// / 'Close Virus Scan Options'
$Gui2Text9 = 'Zamknij Opcje Skanowania Antywirusowego';
// / 'Archive'
$Gui2Text10 = 'Archiwum';
// / 'Close Archive Options'
$Gui2Text11 = 'Zamknij Opcje Archiwum';
// / 'OCR'
$Gui2Text12 = 'OCR';
// / 'Close OCR Options'
$Gui2Text13 = 'Zamknij Opcje OCR';
// / 'Convert'
$Gui2Text14 = 'Konwertuj';
// / 'Close Convert Options'
$Gui2Text15 = 'Zamknij Opcje Konwersji';
// / 'Archive This File'
$Gui2Text16 = 'Zarchiwizuj Ten Plik';
// / 'Specify Filename: '
$Gui2Text17 = 'Określ Nazwę Pliku: ';
// / 'Format'
$Gui2Text18 = 'Format';
// / 'Compress & Download'
$Gui2Text19 = 'Skompresuj i Pobierz';
// / 'Scan with ClamAV: '
$Gui2Text20 = 'Skanuj za Pomocą ClamAV: ';
// / 'Scan with ScanCore: '
$Gui2Text21 = 'Skanuj za Pomocą ScanCore: ';
// / 'Scan All'
$Gui2Text22 = 'Skanuj Wszystko';
// / 'Share This File'
$Gui2Text23 = 'Udostępnij Ten Plik';
// / 'Link Status: '
$Gui2Text24 = 'Status Linku: ';
// / 'Not Generated'
$Gui2Text25 = 'Nie Wygenerowano';
// / 'Generated'
$Gui2Text26 = 'Wygenerowano';
// / 'Clipboard Status: '
$Gui2Text27 = 'Status Schowka: ';
// / 'Copied'
$Gui2Text28 = 'Skopiowano';
// / 'File Link: '
$Gui2Text29 = 'Link do Pliku: ';
// / 'You have uploaded '.$FileCount.' valid file'.$FCPlural1.' to '.$ApplicationName.'.'
$Gui2Text30 = 'Przesłano '.$FileCount.' prawidłowy plik'.$FCPlural1.' do '.$ApplicationName.'.';
// / 'Your file'.$FCPlural2.' now ready to convert using the options below.'
$Gui2Text31 = 'Twój plik'.$FCPlural2.' gotowy do konwersji przy użyciu poniższych opcji.';
// / 'Generate Link & Copy to Clipboard'
$Gui2Text32 = 'Wygeneruj Link i Skopiuj do Schowka';
// / 'Generate Link'
$Gui2Text33 = 'Wygeneruj Link';
// / 'Scan This File For Viruses'
$Gui2Text34 = 'Skanuj Ten Plik w Poszukiwaniu Wirusów';
// / 'Scan File With ScanCore'
$Gui2Text35 = 'Skanuj Plik za Pomocą ScanCore';
// / 'Scan File With ClamAV'
$Gui2Text36 = 'Skanuj Plik za Pomocą ClamAV';
// / 'Scan File With ScanCore & ClamAV'
$Gui2Text37 = 'Skanuj Plik za Pomocą ScanCore i ClamAV';
// / 'Perform Optical Character Recognition On This File'
$Gui2Text38 = 'Wykonaj Optyczne Rozpoznawanie Znaków na Tym Pliku';
// / 'Method'
$Gui2Text39 = 'Metoda';
// / 'Simple'
$Gui2Text40 = 'Prosta';
// / 'Advanced'
$Gui2Text41 = 'Zaawansowana';
// / 'Convert This Archive'
$Gui2Text42 = 'Konwertuj To Archiwum';
// / 'Convert This Document'
$Gui2Text43 = 'Konwertuj Ten Dokument';
// / 'Convert This Spreadsheet'
$Gui2Text44 = 'Konwertuj Ten Arkusz Kalkulacyjny';
// / 'Convert This Audio'
$Gui2Text45 = 'Konwertuj Ten Plik Audio';
// / 'Convert This Video'
$Gui2Text46 = 'Konwertuj Ten Plik Wideo';
// / 'Convert This Stream'
$Gui2Text47 = 'Konwertuj Ten Strumień';
// / Convert This 3D Model'
$Gui2Text48 = 'Konwertuj Ten Model 3D';
// / 'Convert This Technical Drawing Or Vector File'
$Gui2Text49 = 'Konwertuj Ten Rysunek Techniczny lub Plik Wektorowy';
// / 'Convert This Image'
$Gui2Text50 = 'Konwertuj Ten Obraz';
// / 'Archive File'
$Gui2Text51 = 'Zarchiwizuj Plik';
// / 'Convert Into Document'
$Gui2Text52 = 'Konwertuj na Dokument';
// / 'Archive Files'
$Gui2Text53 = 'Zarchiwizuj Pliki';
// / 'Convert Document'
$Gui2Text54 = 'Konwertuj Dokument';
// / 'Convert Spreadsheet'
$Gui2Text55 = 'Konwertuj Arkusz Kalkulacyjny';
// / 'Convert Presentation'
$Gui2Text56 = 'Konwertuj Prezentację';
// / 'Convert Audio'
$Gui2Text57 = 'Konwertuj Audio';
// / 'Convert Video'
$Gui2Text58 = 'Konwertuj Wideo';
// / 'Convert Stream'
$Gui2Text59 = 'Konwertuj Strumień';
// / 'Convert Model'
$Gui2Text60 = 'Konwertuj Model';
// / 'Convert Drawing'
$Gui2Text61 = 'Konwertuj Rysunek';
// / 'Convert Image'
$Gui2Text62 = 'Konwertuj Obraz';
// / 'Width & Height'
$Gui2Text64 = 'Szerokość i Wysokość: ';
// / 'Rotate: '
$Gui2Text65 = 'Obróć: ';
// / 'Bitrate: '
$Gui2Text66 = 'Bitrate: ';
// / 'Delete'
$Gui2Text67 = 'Usuń';
// / 'Close Delete Options'
$Gui2Text68 = 'Zamknij Opcje Usuwania';
// / 'Delete This File'
$Gui2Text69 = 'Usuń Ten Plik';
// / 'Confirm Delete'
$Gui2Text70 = 'Potwierdź Usunięcie';
// / 'Cannot convert this file! Try changing the name.'
$Gui2Text71 = 'Nie można przekonwertować tego pliku! Spróbuj zmienić nazwę.';
// / 'Cannot perform a virus scan on this file!'
$Gui2Text72 = 'Nie można wykonać skanowania antywirusowego tego pliku!';
// / 'File Link Copied to Clipboard!'
$Gui2Text73 = 'Link do Pliku Skopiowany do Schowka!';
// / 'Operation Failed!'
$Gui2Text74 = 'Operacja Nie Powiodła Się!';
// / 'Convert These Subtitles'
$Gui2Text75 = 'Konwertuj Te Napisy';
// / 'Convert Subtitles'
$Gui2Text76 = 'Konwertuj Napisy';
// / 'Convert This Presentation'
$Gui2Text77 = 'Konwertuj Tę Prezentację';
// / 'Convert This XPS File'
$Gui2Text78 = 'Konwertuj Ten Plik XPS';
// / 'Render This OpenSCAD Model'
$Gui2Text79 = 'Renderuj Ten Model OpenSCAD';
// / 'Render Model'
$Gui2Text80 = 'Renderuj Model';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - HRC2-Functions.js Related Variables.
// / These strings are used by the client side javascript library.
// / That file is static javascript & cannot read a PHP variable, so every string it needs
// / is passed to it as an argument by the PHP that calls it.
// / 'Your browser does not support copying to the clipboard!'
$GuiFunctionsText1 = 'Twoja przeglądarka nie obsługuje kopiowania do schowka!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy'
$GuiFooterText1 = 'Zapoznaj się z naszymi <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Warunkami Świadczenia Usług</a> oraz <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Polityką Prywatności';
// / -----------------------------------------------------------------------------------
