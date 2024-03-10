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
$CoreError = 'ERROR!!! HRConvert2-2, Цей файл не може обробити ваш запит! Натомість надішліть свій файл у convertCore.php!';
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die($CoreError);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Check for required core variables.
if (!isset($Font)) $Font = 'Arial';
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = 'Перетворіть будь-що!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI Related Logic.
if (!is_numeric($FileCount)) $FileCount = 'невідома кількість';
if ($FileCount == 0) $FCPlural1 = 'Ви завантажили 0 дійсних файлів до '.$ApplicationName.'.';
if ($FileCount == 1) $FCPlural1 = 'Ви завантажили 1 дійсний файл до '.$ApplicationName.'.'; 
if ($FileCount == 2) $FCPlural1 = 'Ви завантажили 2 дійсні файли до '.$ApplicationName.'.';
if ($FileCount == 3) $FCPlural1 = 'Ви завантажили 3 дійсні файли до '.$ApplicationName.'.';
if ($FileCount == 4) $FCPlural1 = 'Ви завантажили 4 дійсні файли до '.$ApplicationName.'.';
if ($FileCount >= 5) $FCPlural1 = 'Ви завантажили 5 дійсних файлів до '.$ApplicationName.'.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Header Related Variables.
// / 'Click, Tap, or Drop files here to upload.'
$GuiHeaderText1 = 'Натисніть, торкніться або перетягніть файли сюди, щоб завантажити.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / Online File Converter, Extractor, Compressor'
$Gui1Text1 = 'Онлайн-конвертер файлів, екстрактор, компресор';
// / $ApplicationName.' is based off the open-source web-app <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> by <a href=\'https://github.com/zelon88\'>Zelon88</a> that converts files without tracking users across the net or infringing on your intellectual property.'
$Gui1Text2 = $ApplicationName.' базується на веб-додатку з відкритим кодом <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> від <a href=\'https://github.com /zelon88\'>Zelon88</a>, який перетворює файли, не відстежуючи користувачів у мережі та не порушуючи вашу інтелектуальну власність.';
// / 'More Info ...'
$Gui1Text3 = 'Більше інформації ...';
// / 'Less Info ...'
$Gui1Text4 = 'Менше інформації...';
// / 'All user-supplied data is erased automatically, so you don\'t need to worry about forfeiting your personal information or property while using our services.'
$Gui1Text5 = 'Усі надані користувачем дані видаляються автоматично, тож вам не потрібно турбуватися про втрату вашої особистої інформації чи власності під час використання наших послуг.';
// / 'Currently '.$ApplicationName.' supports '.$SupportedFormatCount.' different file formats, including documents, spreadsheets, images, media, 3D models, CAD drawings, vector files, archives, disk images, & more.'
$Gui1Text6 = 'Наразі '.$ApplicationName.' підтримує '.$SupportedFormatCount.' різні формати файлів, включаючи документи, електронні таблиці, зображення, медіафайли, 3D-моделі, креслення САПР, векторні файли, архіви, зображення дисків тощо.';
// / 'View Supported Formats ...'
$Gui1Text7 = 'Переглянути підтримувані формати...';
// / 'Hide Supported Formats ...'
$Gui1Text8 = 'Сховати підтримувані формати...';
// / 'Supported Formats'
$Gui1Text9 = 'Підтримувані формати';
// / 'Audio Formats'
$Gui1Text10 = 'Аудіоформати';
// / 'Supports specific bitrate.'
$Gui1Text11 = 'Supports specific bitrate.';
// / 'Video Formats'
$Gui1Text12 = 'Формати відео';
// / 'Stream Formats'
$Gui1Text13 = 'Формати потоку';
// / 'Document Formats'
$Gui1Text14 = 'Формати документів';
// / 'Spreadsheet Formats'
$Gui1Text15 = 'Формати електронних таблиць';
// / 'Presentation Formats'
$Gui1Text16 = 'Формати презентацій';
// / 'Archive Formats'
$Gui1Text17 = 'Формати архівів';
// / 'Can convert between archive formats & disk image formats.'
$Gui1Text18 = 'Може конвертувати між вибраними форматами архівів і форматами образів дисків.';
// / 'Image Formats'
$Gui1Text19 = 'Формати зображень';
// / 'Can convert pictures of documents to document formats.'
$Gui1Text20 = 'Може конвертувати зображення документів у формати документів.';
// / 'Supports resize & rotate.'
$Gui1Text21 = 'Підтримує зміну розміру та поворот.';
// / '3D Model Formats'
$Gui1Text22 = 'Формати 3D моделі';
// / 'Drawing Formats'
$Gui1Text23 = 'Формати малюнків';
// / 'Can convert drawing files to image formats.'
$Gui1Text24 = 'Може конвертувати формати малюнків у формати зображень.';
// / 'OCR Support'
$Gui1Text25 = 'Підтримка OCR';
// / 'OCR Operations support the following input formats...'
$Gui1Text26 = 'Операції OCR підтримують наступні формати введення...';
// / 'OCR Operations support the following output formats...'
$Gui1Text27 = 'Операції OCR підтримують наступні вихідні формати...';
// / 'Select files by clicking, tapping, or dropping them into the box below.'
$Gui1Text28 = 'Виберіть файли, натиснувши, торкнувшись або опустивши їх у поле нижче.';
// / 'Continue ...'
$Gui1Text29 = 'продовжити...';
// / 'Can convert stream formats to video formats.'
$Gui1Text30 = 'Може конвертувати формати потоку у формати відео.';
// / 'Subtitle Formats'
$Gui1Text31 = 'Формати субтитрів';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'Параметри перетворення файлів';
// / 'Bulk File Options'
$Gui2Text2 = 'Параметри масового файлу';
// / 'Scan All Files For Viruses'
$Gui2Text3 = 'Сканувати всі файли на наявність вірусів';
// / 'Compress & Download All Files'
$Gui2Text4 = 'Стисніть і завантажте всі файли';
// / 'Download'
$Gui2Text5 = 'Завантажити';
// / 'Share'
$Gui2Text6 = 'Поділіться';
// / 'Close Share Options'
$Gui2Text7 = 'Закрийте параметри спільного доступу';
// / 'Virus Scan'
$Gui2Text8 = 'Сканування на віруси';
// / 'Close Virus Scan Options'
$Gui2Text9 = 'Закрийте параметри сканування на віруси';
// / 'Archive'
$Gui2Text10 = 'Архів';
// / 'Close Archive Options'
$Gui2Text11 = 'Закрийте параметри архіву';
// / 'OCR'
$Gui2Text12 = 'OCR';
// / 'Close OCR Options'
$Gui2Text13 = 'Закрийте параметри OCR';
// / 'Convert'
$Gui2Text14 = 'конвертувати';
// / 'Close Convert Options'
$Gui2Text15 = 'Закрийте параметри перетворення';
// / 'Archive This File'
$Gui2Text16 = 'Архівуйте цей файл';
// / 'Specify Filename: '
$Gui2Text17 = 'Вкажіть назву файлу: ';
// / 'Format'
$Gui2Text18 = 'Формат';
// / 'Compress & Download'
$Gui2Text19 = 'Стиснути та завантажити';
// / 'Scan with ClamAV: '
$Gui2Text20 = 'Сканування за допомогою ClamAV: ';
// / 'Scan with ScanCore: '
$Gui2Text21 = 'Сканування за допомогою ScanCore: ';
// / 'Scan All'
$Gui2Text22 = 'Сканувати все';
// / 'Share This File'
$Gui2Text23 = 'Поділитися цим файлом';
// / 'Link Status: '
$Gui2Text24 = 'Статус посилання: ';
// / 'Not Generated'
$Gui2Text25 = 'Не створено';
// / 'Generated'
$Gui2Text26 = 'Згенерований';
// / 'Clipboard Status: '
$Gui2Text27 = 'Статус буфера обміну: ';
// / 'Copied'
$Gui2Text28 = 'Скопійовано';
// / 'File Link: '
$Gui2Text29 = 'Посилання на файл: ';
// / 'You have uploaded '.$FileCount.' valid file'.$FCPlural1.' to '.$ApplicationName.'.'
$Gui2Text30 = $FCPlural1;
// / 'Your file'.$FCPlural2.' now ready to convert using the options below.'
$Gui2Text31 = 'Тепер ваші файли готові до конвертації за допомогою наведених нижче параметрів.';
// / 'Generate Link & Copy to Clipboard'
$Gui2Text32 = 'Створити посилання та скопіювати в буфер обміну';
// / 'Generate Link'
$Gui2Text33 = 'Згенерувати посилання';
// / 'Scan This File For Viruses'
$Gui2Text34 = 'Перевірте цей файл на наявність вірусів';
// / 'Scan File With ScanCore'
$Gui2Text35 = 'Сканувати файл за допомогою ScanCore';
// / 'Scan File With ClamAV'
$Gui2Text36 = 'Сканувати файл за допомогою ClamAV';
// / 'Scan File With ScanCore & ClamAV'
$Gui2Text37 = 'Сканувати файл за допомогою ScanCore & ClamAV';
// / 'Perform Optical Character Recognition On This File'
$Gui2Text38 = 'Виконайте оптичне розпізнавання символів у цьому файлі';
// / 'Method'
$Gui2Text39 = 'метод';
// / 'Simple'
$Gui2Text40 = 'Простий';
// / 'Advanced'
$Gui2Text41 = 'Просунутий';
// / 'Convert This Archive'
$Gui2Text42 = 'Перетворити цей архів';
// / 'Convert This Document'
$Gui2Text43 = 'Перетворити цей документ';
// / 'Convert This Spreadsheet'
$Gui2Text44 = 'Перетворити цю електронну таблицю';
// / 'Convert This Audio'
$Gui2Text45 = 'Перетворити це аудіо';
// / 'Convert This Video'
$Gui2Text46 = 'Перетворити це відео';
// / 'Convert This Stream'
$Gui2Text47 = 'Перетворити цей потік';
// / Convert This 3D Model'
$Gui2Text48 = 'Перетворити цю 3D-модель';
// / 'Convert This Technical Drawing Or Vector File'
$Gui2Text49 = 'Перетворіть цей технічний малюнок або векторний файл';
// / 'Convert This Image'
$Gui2Text50 = 'Перетворити це зображення';
// / 'Archive File'
$Gui2Text51 = 'Архівний файл';
// / 'Convert Into Document'
$Gui2Text52 = 'Перетворити в документ';
// / 'Archive Files'
$Gui2Text53 = 'Архівні файли';
// / 'Convert Document'
$Gui2Text54 = 'Перетворити документ';
// / 'Convert Spreadsheet'
$Gui2Text55 = 'Перетворення електронної таблиці';
// / 'Convert Presentation'
$Gui2Text56 = 'Конвертувати презентацію';
// / 'Convert Audio'
$Gui2Text57 = 'Перетворення аудіо';
// / 'Convert Video'
$Gui2Text58 = 'Перетворення аудіо';
// / 'Convert Stream'
$Gui2Text59 = 'Перетворити потік';
// / 'Convert Model'
$Gui2Text60 = 'Перетворити модель';
// / 'Convert Drawing'
$Gui2Text61 = 'Перетворити малюнок';
// / 'Convert Image'
$Gui2Text62 = 'Перетворити зображення';
// / 'Width & Height'
$Gui2Text64 = 'Ширина висота: ';
// / 'Rotate: '
$Gui2Text65 = 'Обертати: ';
// / 'Bitrate: '
$Gui2Text66 = 'Бітрейт: ';
// / 'Delete'
$Gui2Text67 = 'Видалити';
// / 'Close Delete Options'
$Gui2Text68 = 'Закрийте параметри видалення';
// / 'Delete This File'
$Gui2Text69 = 'Видалити цей файл';
// / 'Confirm Delete'
$Gui2Text70 = 'Підтвердьте видалення';
// / 'Cannot convert this file! Try changing the name.'
$Gui2Text71 = 'Неможливо конвертувати цей файл! Спробуйте змінити назву.';
// / 'Cannot perform a virus scan on this file!'
$Gui2Text72 = 'Неможливо перевірити цей файл на віруси!';
// / 'File Link Copied to Clipboard!'
$Gui2Text73 = 'Посилання на файл скопійовано в буфер обміну!';
// / 'Operation Failed!'
$Gui2Text74 = 'Операція не виконана!';
// / Convert These Subtitles'
$Gui2Text75 = 'Перетворіть ці субтитри';
// / Convert Subtitles'
$Gui2Text76 = 'Перетворення субтитрів';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy'
$GuiFooterText1 = 'Перегляньте наші <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Загальні положення та умови</a> та <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Політика конфіденційності';
// / -----------------------------------------------------------------------------------