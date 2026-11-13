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
// / Set GUI - UI Selector Bar Related Variables.
// / These strings appear in the selector bar, which is present on both GUI1 & GUI2.
// / 'Language'
$GuiSelectorText1 = 'Мова';
// / 'Color'
$GuiSelectorText2 = 'Колір';
// / 'Interface'
$GuiSelectorText3 = 'Інтерфейс';
// / 'Display language, color and interface options'
$GuiSelectorText4 = 'Показати параметри мови, кольору та інтерфейсу';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / 'Online File Converter, Extractor, Compressor'
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
$Gui1Text11 = 'Підтримує вказаний бітрейт.';
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
// / 'OpenSCAD Formats'
$Gui1Text32 = 'Формати OpenSCAD';
// / 'Renders OpenSCAD source into 3D model formats.'
$Gui1Text33 = 'Перетворює вихідний код OpenSCAD у формати тривимірних моделей.';
// / 'File references inside uploaded sources are removed unless the server allows resolving them.'
$Gui1Text34 = 'Посилання на файли всередині завантажених джерел вилучаються, якщо сервер не дозволяє їх підключення.';
// / 'Delete every uploaded file & start a new session?'
// / Shown inside the start over panel on the upload page. That panel is only rendered
// / when the session already holds at least one file, so this is never shown to a first
// / time visitor who has nothing to lose.
$Gui1Text35 = 'Видалити всі завантажені файли й почати новий сеанс?';
// / 'Start Over'
// / Labels the control that opens the panel & the button inside it that confirms.
$Gui1Text36 = 'Почати спочатку';
// / 'Refresh'
// / Alternate text for the refresh control on the upload page. That control is a glyph
// / with no text of its own, so this is the only description a screen reader has & the
// / only thing shown when a browser cannot render the glyph.
$Gui1Text37 = 'Оновити';
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
// / 'Convert This 3D Model'
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
// / 'Convert These Subtitles'
$Gui2Text75 = 'Перетворіть ці субтитри';
// / 'Convert Subtitles'
$Gui2Text76 = 'Перетворення субтитрів';
// / 'Convert This Presentation'
$Gui2Text77 = 'Перетворити цю презентацію';
// / 'Convert This XPS File'
$Gui2Text78 = 'Перетворити цей файл';
// / 'Render This OpenSCAD Model'
$Gui2Text79 = 'Побудувати цю модель OpenSCAD';
// / 'Render Model'
$Gui2Text80 = 'Побудувати модель';
// / 'Convert This E-Book'
$Gui2Text81 = 'Конвертувати цю електронну книгу';
// / 'Convert E-Book'
$Gui2Text82 = 'Конвертувати електронну книгу';
// / 'Playlist source URL, only needed if the playlist uses relative paths'
$Gui2Text83 = 'Початковий URL плейлиста, потрібен лише якщо він використовує відносні шляхи';
// / 'Download A File From A URL'
$Gui2Text84 = 'Завантажити файл за URL';
// / 'Fetch'
$Gui2Text85 = 'Завантажити';
// / 'Identify'
$Gui2Text86 = 'Визначити';
// / 'Close Identify Options'
$Gui2Text87 = 'Закрийте параметри визначення';
// / 'Identify This File'
$Gui2Text88 = 'Визначити цей файл';
// / 'Size: '
$Gui2Text89 = 'Розмір: ';
// / 'Modified: '
$Gui2Text90 = 'Змінено: ';
// / 'Type: '
$Gui2Text91 = 'Тип: ';
// / 'MIME Type: '
$Gui2Text92 = 'Тип MIME: ';
// / 'Character Set: '
$Gui2Text93 = 'Кодування: ';
// / 'Extension Matches Content: '
$Gui2Text94 = 'Розширення відповідає вмісту: ';
// / 'Details: '
$Gui2Text95 = 'Подробиці: ';
// / 'Notes: '
$Gui2Text96 = 'Примітки: ';
// / 'Save report as file'
$Gui2Text97 = 'Зберегти звіт у файл';
// / 'Identify All Files'
$Gui2Text98 = 'Визначити всі файли';
// / 'Identify All'
$Gui2Text99 = 'Визначити все';
// / 'Yes'
$Gui2Text100 = 'Так';
// / 'No'
$Gui2Text101 = 'Ні';
// / 'Unknown'
$Gui2Text102 = 'Невідомо';
// / 'Cannot identify this file!'
$Gui2Text103 = 'Неможливо визначити цей файл!';
// / 'Sort By: '
$Gui2Text104 = 'Сортувати за: ';
// / 'Name'
$Gui2Text105 = 'Ім’я';
// / 'Size'
$Gui2Text106 = 'Розмір';
// / 'Date'
$Gui2Text107 = 'Дата';
// / 'Type'
$Gui2Text108 = 'Тип';
// / The words in braces, such as {fit} & {count}, are filled in by the page. Each translation keeps every one of them.
// / 'Select'
$Gui2Text109 = 'Вибрати';
// / 'Select All'
$Gui2Text110 = 'Вибрати все';
// / 'Select None'
$Gui2Text111 = 'Зняти вибір';
// / 'Select By Type'
$Gui2Text112 = 'Вибрати за типом';
// / 'Selected: '
$Gui2Text113 = 'Вибрано: ';
// / 'Documents'
$Gui2Text114 = 'Документи';
// / 'Spreadsheets'
$Gui2Text115 = 'Електронні таблиці';
// / 'Presentations'
$Gui2Text116 = 'Презентації';
// / 'Images'
$Gui2Text117 = 'Зображення';
// / 'Audio'
$Gui2Text118 = 'Аудіо';
// / 'Video'
$Gui2Text119 = 'Відео';
// / 'Archives'
$Gui2Text120 = 'Архіви';
// / 'E-Books'
$Gui2Text121 = 'Електронні книги';
// / '3D Models'
$Gui2Text122 = '3D-моделі';
// / 'Drawings'
$Gui2Text123 = 'Креслення';
// / 'Vector Images'
$Gui2Text124 = 'Векторні зображення';
// / 'Subtitles'
$Gui2Text125 = 'Субтитри';
// / 'OpenSCAD Files'
$Gui2Text126 = 'Файли OpenSCAD';
// / 'Streams'
$Gui2Text127 = 'Потоки';
// / 'Workflows'
$Gui2Text128 = 'Робочі процеси';
// / 'Files'
$Gui2Text129 = 'Файли';
// / 'Select files in the list below.'
$Gui2Text130 = 'Виберіть файли у списку нижче.';
// / 'Select By Name: '
$Gui2Text131 = 'Вибрати за назвою: ';
// / 'Use * and ? as wildcards, or write a regular expression between slashes.'
$Gui2Text132 = 'Використовуйте * і ? як символи підстановки або напишіть регулярний вираз між скісними рисками.';
// / 'Workflow'
$Gui2Text133 = 'Робочий процес';
// / 'Choose a workflow'
$Gui2Text134 = 'Виберіть робочий процес';
// / 'Run'
$Gui2Text135 = 'Запустити';
// / 'Stop'
$Gui2Text136 = 'Зупинити';
// / 'Selected files that can be used: {fit} of {count}.'
$Gui2Text137 = 'Вибрані файли, які можна використати: {fit} з {count}.';
// / 'Step {step} of {steps}: {operation}'
$Gui2Text138 = 'Крок {step} з {steps}: {operation}';
// / 'File {done} of {total}'
$Gui2Text139 = 'Файл {done} з {total}';
// / 'Waiting for the server.'
$Gui2Text140 = 'Очікування сервера.';
// / 'Starting.'
$Gui2Text141 = 'Запуск.';
// / 'Finished.'
$Gui2Text142 = 'Готово.';
// / 'Stopping.'
$Gui2Text143 = 'Зупинка.';
// / 'Stopped.'
$Gui2Text144 = 'Зупинено.';
// / 'Files made: '
$Gui2Text145 = 'Створені файли: ';
// / 'Workflows are not available on this server.'
$Gui2Text146 = 'Робочі процеси недоступні на цьому сервері.';
// / 'No workflows are installed on this server.'
$Gui2Text147 = 'На цьому сервері не встановлено жодного робочого процесу.';
// / 'That workflow is no longer available.'
$Gui2Text148 = 'Цей робочий процес більше недоступний.';
// / 'None of the selected files can be used by this workflow.'
$Gui2Text149 = 'Цей робочий процес не може використати жоден із вибраних файлів.';
// / 'Too many files are selected for one workflow.'
$Gui2Text150 = 'Для одного робочого процесу вибрано забагато файлів.';
// / 'The server is busy. Try again in a few minutes.'
$Gui2Text151 = 'Сервер зайнятий. Спробуйте ще раз за кілька хвилин.';
// / 'A workflow is already running in this session.'
$Gui2Text152 = 'У цьому сеансі вже виконується робочий процес.';
// / 'The workflow could not be started.'
$Gui2Text153 = 'Не вдалося запустити робочий процес.';
// / 'A step of the workflow failed.'
$Gui2Text154 = 'Один із кроків робочого процесу завершився помилкою.';
// / 'A virus was found, so the workflow stopped.'
$Gui2Text155 = 'Виявлено вірус, тому робочий процес зупинено.';
// / 'The workflow ran out of time.'
$Gui2Text156 = 'Робочому процесу забракло часу.';
// / 'The workflow made too many files.'
$Gui2Text157 = 'Робочий процес створив забагато файлів.';
// / 'The server was too busy to finish the workflow.'
$Gui2Text158 = 'Сервер був надто зайнятий, щоб завершити робочий процес.';
// / 'An operation this workflow needs is not available.'
$Gui2Text159 = 'Операція, потрібна цьому робочому процесу, недоступна.';
// / 'The workflow stopped unexpectedly.'
$Gui2Text160 = 'Робочий процес несподівано зупинився.';
// / The words in braces, such as {step} & {kind}, are filled in by the page. Each translation keeps every one of them.
// / 'Pipelines'
$Gui2Text161 = 'Конвеєри';
// / 'Select a pipeline above...'
$Gui2Text162 = 'Виберіть конвеєр вище...';
// / 'Select Pipeline...'
$Gui2Text163 = 'Вибрати конвеєр...';
// / 'Select Files...'
$Gui2Text164 = 'Вибрати файли...';
// / 'Add a step'
$Gui2Text165 = 'Додати крок';
// / 'Options'
$Gui2Text166 = 'Параметри';
// / 'Back'
$Gui2Text167 = 'Назад';
// / 'Take: '
$Gui2Text168 = 'Взяти: ';
// / 'the ticked files'
$Gui2Text169 = 'позначені файли';
// / 'the files step {step} made'
$Gui2Text170 = 'файли, створені кроком {step}';
// / 'each file on its own'
$Gui2Text171 = 'кожен файл окремо';
// / 'all files together'
$Gui2Text172 = 'усі файли разом';
// / 'Only these kinds: '
$Gui2Text173 = 'Лише ці види: ';
// / 'Only these extensions: '
$Gui2Text174 = 'Лише ці розширення: ';
// / 'Smallest size: '
$Gui2Text175 = 'Найменший розмір: ';
// / 'Largest size: '
$Gui2Text176 = 'Найбільший розмір: ';
// / 'In the order they arrive'
$Gui2Text177 = 'У порядку надходження';
// / 'all files'
$Gui2Text178 = 'усі файли';
// / 'all {kind}'
$Gui2Text179 = 'усі файли: {kind}';
// / 'Width: '
$Gui2Text180 = 'Ширина: ';
// / 'Height: '
$Gui2Text181 = 'Висота: ';
// / 'My workflow'
$Gui2Text182 = 'Мій робочий процес';
// / 'New'
$Gui2Text183 = 'Новий';
// / 'Import'
$Gui2Text184 = 'Імпорт';
// / 'Export'
$Gui2Text185 = 'Експорт';
// / 'This workflow is not one this server can run.'
$Gui2Text186 = 'Цей сервер не може виконати цей робочий процес.';
// / 'The file could not be read.'
$Gui2Text187 = 'Не вдалося прочитати файл.';
// / 'The workflow builder is not available on this server.'
$Gui2Text188 = 'Редактор робочих процесів недоступний на цьому сервері.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - HRC2-Functions.js Related Variables.
// / These strings are used by the client side javascript library.
// / That file is static javascript & cannot read a PHP variable, so every string it needs
// / is passed to it as an argument by the PHP that calls it.
// / 'Your browser does not support copying to the clipboard!'
$GuiFunctionsText1 = 'Ваш браузер не підтримує копіювання до буфера обміну.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy'
$GuiFooterText1 = 'Перегляньте наші <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Загальні положення та умови</a> та <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Політика конфіденційності';
// / -----------------------------------------------------------------------------------