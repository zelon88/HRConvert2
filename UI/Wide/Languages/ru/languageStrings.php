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
$CoreError = 'ERROR!!! HRConvert2-2, Этот файл не может обработать ваш запрос! Вместо этого отправьте файл на ConvertCore.php!';
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die($CoreError);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Check for required core variables.
if (!isset($Font)) $Font = 'Arial';
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = 'Конвертируйте что угодно!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI Related Logic.
if (!is_numeric($FileCount)) $FileCount = 'неизвестное количество';
if ($FileCount === 0) $FCPlural1 = 'Вы загрузили 0 действительных файлов в '.$ApplicationName.'.';
if ($FileCount === 1) $FCPlural1 = 'Вы загрузили 1 действительный файл в '.$ApplicationName.'.'; 
if ($FileCount >= 2) $FCPlural1 = 'Вы загрузили '.$FileCount.' действительных файла в '.$ApplicationName.'.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Header Related Variables.
// / 'Click, Tap, or Drop files here to upload.'
$GuiHeaderText1 = 'Нажмите, коснитесь или перетащите файлы сюда, чтобы загрузить.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / Online File Converter, Extractor, Compressor'
$Gui1Text1 = 'Онлайн-конвертер файлов, экстрактор, компрессор';
// / $ApplicationName.' is based off the open-source web-app <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> by <a href=\'https://github.com/zelon88\'>Zelon88</a> that converts files without tracking users across the net or infringing on your intellectual property.'
$Gui1Text2 = $ApplicationName.' основан на веб-приложении с открытым исходным кодом <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> от <a href=\'https://github.com /zelon88\'>Zelon88</a>, который конвертирует файлы, не отслеживая пользователей в сети и не нарушая вашу интеллектуальную собственность.';
// / 'More Info ...'
$Gui1Text3 = 'Больше информации ...';
// / 'Less Info ...'
$Gui1Text4 = 'Меньше информации...';
// / 'All user-supplied data is erased automatically, so you don\'t need to worry about forfeiting your personal information or property while using our services.'
$Gui1Text5 = 'Все предоставленные пользователем данные удаляются автоматически, поэтому вам не нужно беспокоиться о потере вашей личной информации или имущества при использовании наших услуг.';
// / 'Currently '.$ApplicationName.' supports '.$SupportedFormatCount.' different file formats, including documents, spreadsheets, images, media, 3D models, CAD drawings, vector files, archives, disk images, & more.'
$Gui1Text6 = 'В настоящее время '.$ApplicationName.' поддерживает '.$SupportedFormatCount.' различные форматы файлов, включая документы, электронные таблицы, изображения, мультимедиа, 3D-модели, чертежи САПР, векторные файлы, архивы, образы дисков и многое другое.';
// / 'View Supported Formats ...'
$Gui1Text7 = 'Просмотр поддерживаемых форматов...';
// / 'Hide Supported Formats ...'
$Gui1Text8 = 'Скрыть поддерживаемые форматы...';
// / 'Supported Formats'
$Gui1Text9 = 'Поддерживаемые форматы';
// / 'Audio Formats'
$Gui1Text10 = 'Аудио форматы';
// / 'Supports specific bitrate.'
$Gui1Text11 = 'Поддерживает определенный битрейт.';
// / 'Video Formats'
$Gui1Text12 = 'Видео форматы';
// / 'Stream Formats'
$Gui1Text13 = 'Форматы потока';
// / 'Document Formats'
$Gui1Text14 = 'Форматы документов';
// / 'Spreadsheet Formats'
$Gui1Text15 = 'Форматы электронных таблиц';
// / 'Presentation Formats'
$Gui1Text16 = 'Форматы презентаций';
// / 'Archive Formats'
$Gui1Text17 = 'Форматы архивов';
// / 'Can convert between archive formats & disk image formats.'
$Gui1Text18 = 'Может конвертировать между выбранными форматами архивов и форматами образов дисков.';
// / 'Image Formats'
$Gui1Text19 = 'Форматы изображений';
// / 'Can convert pictures of documents to document formats.'
$Gui1Text20 = 'Может конвертировать изображения документов в форматы документов.';
// / 'Supports resize & rotate.'
$Gui1Text21 = 'Поддерживает изменение размера и поворот.';
// / '3D Model Formats'
$Gui1Text22 = 'Форматы 3D-моделей';
// / 'Drawing Formats'
$Gui1Text23 = 'Форматы чертежей';
// / 'Can convert drawing files to image formats.'
$Gui1Text24 = 'Может конвертировать форматы чертежей в форматы изображений.';
// / 'OCR Support'
$Gui1Text25 = 'Поддержка оптического распознавания символов';
// / 'OCR Operations support the following input formats...'
$Gui1Text26 = 'Операции OCR поддерживают следующие входные форматы...';
// / 'OCR Operations support the following output formats...'
$Gui1Text27 = 'Операции OCR поддерживают следующие форматы вывода...';
// / 'Select files by clicking, tapping, or dropping them into the box below.'
$Gui1Text28 = 'Выберите файлы, щелкнув, коснувшись или перетащив их в поле ниже.';
// / 'Continue ...'
$Gui1Text29 = 'Продолжать ...';
// / 'Can convert stream formats to video formats.'
$Gui1Text30 = 'Может конвертировать потоковые форматы в видеоформаты.';
// / 'Subtitle Formats'
$Gui1Text31 = 'Форматы субтитров';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'Параметры преобразования файлов';
// / 'Bulk File Options'
$Gui2Text2 = 'Параметры массового файла';
// / 'Scan All Files For Viruses'
$Gui2Text3 = 'Сканировать все файлы на наличие вирусов';
// / 'Compress & Download All Files'
$Gui2Text4 = 'Сжать и загрузить все файлы';
// / 'Download'
$Gui2Text5 = 'Скачать';
// / 'Share'
$Gui2Text6 = 'Делиться';
// / 'Close Share Options'
$Gui2Text7 = 'Закрыть параметры общего доступа';
// / 'Virus Scan'
$Gui2Text8 = 'Сканирование на вирусы';
// / 'Close Virus Scan Options'
$Gui2Text9 = 'Закрыть параметры сканирования на вирусы';
// / 'Archive'
$Gui2Text10 = 'Архив';
// / 'Close Archive Options'
$Gui2Text11 = 'Закрыть параметры архива';
// / 'OCR'
$Gui2Text12 = 'оптическое распознавание текста';
// / 'Close OCR Options'
$Gui2Text13 = 'Закрыть параметры распознавания';
// / 'Convert'
$Gui2Text14 = 'Конвертировать';
// / 'Close Convert Options'
$Gui2Text15 = 'Закрыть параметры конвертации';
// / 'Archive This File'
$Gui2Text16 = 'Архивировать этот файл';
// / 'Specify Filename: '
$Gui2Text17 = 'Укажите имя файла: ';
// / 'Format'
$Gui2Text18 = 'Формат';
// / 'Compress & Download'
$Gui2Text19 = 'Сжать и скачать';
// / 'Scan with ClamAV: '
$Gui2Text20 = 'Сканирование с помощью ClamAV: ';
// / 'Scan with ScanCore: '
$Gui2Text21 = 'Сканирование с помощью ScanCore: ';
// / 'Scan All'
$Gui2Text22 = 'Сканировать все';
// / 'Share This File'
$Gui2Text23 = 'Поделиться этим файлом';
// / 'Link Status: '
$Gui2Text24 = 'Статус ссылки: ';
// / 'Not Generated'
$Gui2Text25 = 'Не создано';
// / 'Generated'
$Gui2Text26 = 'Сгенерировано';
// / 'Clipboard Status: '
$Gui2Text27 = 'Статус буфера обмена: ';
// / 'Copied'
$Gui2Text28 = 'Скопировано';
// / 'File Link: '
$Gui2Text29 = 'Ссылка на файл: ';
// / 'You have uploaded '.$FileCount.' valid file'.$FCPlural1.' to '.$ApplicationName.'.'
$Gui2Text30 = $FCPlural1;
// / 'Your file'.$FCPlural2.' now ready to convert using the options below.'
$Gui2Text31 = 'Теперь ваши файлы готовы к конвертации с использованием приведенных ниже параметров.';
// / 'Generate Link & Copy to Clipboard'
$Gui2Text32 = 'Создать ссылку и скопировать в буфер обмена';
// / 'Generate Link'
$Gui2Text33 = 'Создать ссылку';
// / 'Scan This File For Viruses'
$Gui2Text34 = 'Сканируйте этот файл на наличие вирусов';
// / 'Scan File With ScanCore'
$Gui2Text35 = 'Сканировать файл с помощью ScanCore';
// / 'Scan File With ClamAV'
$Gui2Text36 = 'Сканировать файл с помощью ClamAV';
// / 'Scan File With ScanCore & ClamAV'
$Gui2Text37 = 'Сканирование файла с помощью ScanCore и ClamAV';
// / 'Perform Optical Character Recognition On This File'
$Gui2Text38 = 'Выполните оптическое распознавание символов в этом файле';
// / 'Method'
$Gui2Text39 = 'Метод';
// / 'Simple'
$Gui2Text40 = 'Простой';
// / 'Advanced'
$Gui2Text41 = 'Передовой';
// / 'Convert This Archive'
$Gui2Text42 = 'Конвертировать этот архив';
// / 'Convert This Document'
$Gui2Text43 = 'Преобразовать этот документ';
// / 'Convert This Spreadsheet'
$Gui2Text44 = 'Преобразовать эту таблицу';
// / 'Convert This Audio'
$Gui2Text45 = 'Конвертировать это аудио';
// / 'Convert This Video'
$Gui2Text46 = 'Конвертировать это видео';
// / 'Convert This Stream'
$Gui2Text47 = 'Конвертировать этот поток';
// / Convert This 3D Model'
$Gui2Text48 = 'Преобразуйте эту 3D-модель';
// / 'Convert This Technical Drawing Or Vector File'
$Gui2Text49 = 'Преобразуйте этот технический рисунок или векторный файл';
// / 'Convert This Image'
$Gui2Text50 = 'Конвертировать это изображение';
// / 'Archive File'
$Gui2Text51 = 'Архивный файл';
// / 'Convert Into Document'
$Gui2Text52 = 'Преобразовать в документ';
// / 'Archive Files'
$Gui2Text53 = 'Архивные файлы';
// / 'Convert Document'
$Gui2Text54 = 'Конвертировать документ';
// / 'Convert Spreadsheet'
$Gui2Text55 = 'Преобразование электронной таблицы';
// / 'Convert Presentation'
$Gui2Text56 = 'Преобразование презентации';
// / 'Convert Audio'
$Gui2Text57 = 'Конвертировать аудио';
// / 'Convert Video'
$Gui2Text58 = 'Конвертировать видео';
// / 'Convert Stream'
$Gui2Text59 = 'Конвертировать поток';
// / 'Convert Model'
$Gui2Text60 = 'Преобразование модели';
// / 'Convert Drawing'
$Gui2Text61 = 'Преобразование чертежа';
// / 'Convert Image'
$Gui2Text62 = 'Преобразование чертежа';
// / 'Width & Height'
$Gui2Text64 = 'Ширина высота: ';
// / 'Rotate: '
$Gui2Text65 = 'Поворот: ';
// / 'Bitrate: '
$Gui2Text66 = 'Битрейт: ';
// / 'Delete'
$Gui2Text67 = 'Удалить';
// / 'Close Delete Options'
$Gui2Text68 = 'Закрыть параметры удаления';
// / 'Delete This File'
$Gui2Text69 = 'Удалить этот файл';
// / 'Confirm Delete'
$Gui2Text70 = 'Подтвердите удаление';
// / 'Cannot convert this file! Try changing the name.'
$Gui2Text71 = 'Невозможно преобразовать этот файл! Попробуйте изменить имя.';
// / 'Cannot perform a virus scan on this file!'
$Gui2Text72 = 'Невозможно выполнить проверку этого файла на вирусы!';
// / 'File Link Copied to Clipboard!'
$Gui2Text73 = 'Ссылка на файл скопирована в буфер обмена!';
// / 'Operation Failed!'
$Gui2Text74 = 'Операция не удалась!';
// / Convert These Subtitles'
$Gui2Text75 = 'Конвертируйте эти субтитры';
// / Convert Subtitles'
$Gui2Text76 = 'Конвертировать субтитры';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy'
$GuiFooterText1 = 'Ознакомьтесь с нашими <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Условиями обслуживания</a> и <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Политика конфиденциальности';
// / -----------------------------------------------------------------------------------