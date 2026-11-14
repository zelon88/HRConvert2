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
$LanguageVersion = 'v4.2.0';
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
// / Set GUI - UI Selector Bar Related Variables.
// / These strings appear in the selector bar, which is present on both GUI1 & GUI2.
// / 'Language'
$GuiSelectorText1 = 'Язык';
// / 'Color'
$GuiSelectorText2 = 'Цвет';
// / 'Interface'
$GuiSelectorText3 = 'Интерфейс';
// / 'Display language, color and interface options'
$GuiSelectorText4 = 'Показать настройки языка, цвета и интерфейса';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / 'Online File Converter, Extractor, Compressor'
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
// / 'OpenSCAD Formats'
$Gui1Text32 = 'Форматы OpenSCAD';
// / 'Renders OpenSCAD source into 3D model formats.'
$Gui1Text33 = 'Преобразует исходный код OpenSCAD в форматы трёхмерных моделей.';
// / 'File references inside uploaded sources are removed unless the server allows resolving them.'
$Gui1Text34 = 'Ссылки на файлы внутри загруженных исходников удаляются, если на сервере не разрешено их подключение.';
// / 'Delete every uploaded file & start a new session?'
// / Shown inside the start over panel on the upload page. That panel is only rendered
// / when the session already holds at least one file, so this is never shown to a first
// / time visitor who has nothing to lose.
$Gui1Text35 = 'Удалить все загруженные файлы и начать новый сеанс?';
// / 'Start Over'
// / Labels the control that opens the panel & the button inside it that confirms.
$Gui1Text36 = 'Начать заново';
// / 'Refresh'
// / Alternate text for the refresh control on the upload page. That control is a glyph
// / with no text of its own, so this is the only description a screen reader has & the
// / only thing shown when a browser cannot render the glyph.
$Gui1Text37 = 'Обновить';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'Параметры преобразования файлов';
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
// / 'Convert This 3D Model'
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
// / 'Convert These Subtitles'
$Gui2Text75 = 'Конвертируйте эти субтитры';
// / 'Convert Subtitles'
$Gui2Text76 = 'Конвертировать субтитры';
// / 'Convert This Presentation'
$Gui2Text77 = 'Преобразовать эту презентацию';
// / 'Convert This XPS File'
$Gui2Text78 = 'Преобразовать этот файл';
// / 'Render This OpenSCAD Model'
$Gui2Text79 = 'Собрать эту модель OpenSCAD';
// / 'Render Model'
$Gui2Text80 = 'Собрать модель';
// / 'Convert This E-Book'
$Gui2Text81 = 'Конвертировать эту электронную книгу';
// / 'Convert E-Book'
$Gui2Text82 = 'Конвертировать электронную книгу';
// / 'Playlist source URL, only needed if the playlist uses relative paths'
$Gui2Text83 = 'Исходный URL плейлиста, нужен только если плейлист использует относительные пути';
// / 'Download A File From A URL'
$Gui2Text84 = 'Скачать файл по URL';
// / 'Fetch'
$Gui2Text85 = 'Загрузить';
// / 'Identify'
$Gui2Text86 = 'Определить';
// / 'Close Identify Options'
$Gui2Text87 = 'Закрыть параметры определения';
// / 'Identify This File'
$Gui2Text88 = 'Определить этот файл';
// / 'Size: '
$Gui2Text89 = 'Размер: ';
// / 'Modified: '
$Gui2Text90 = 'Изменён: ';
// / 'Type: '
$Gui2Text91 = 'Тип: ';
// / 'MIME Type: '
$Gui2Text92 = 'Тип MIME: ';
// / 'Character Set: '
$Gui2Text93 = 'Кодировка: ';
// / 'Extension Matches Content: '
$Gui2Text94 = 'Расширение соответствует содержимому: ';
// / 'Details: '
$Gui2Text95 = 'Подробности: ';
// / 'Notes: '
$Gui2Text96 = 'Примечания: ';
// / 'Save report as file'
$Gui2Text97 = 'Сохранить отчёт в файл';
// / 'Yes'
$Gui2Text100 = 'Да';
// / 'No'
$Gui2Text101 = 'Нет';
// / 'Unknown'
$Gui2Text102 = 'Неизвестно';
// / 'Cannot identify this file!'
$Gui2Text103 = 'Невозможно определить этот файл!';
// / 'Sort By: '
$Gui2Text104 = 'Сортировать по: ';
// / 'Name'
$Gui2Text105 = 'Имя';
// / 'Size'
$Gui2Text106 = 'Размер';
// / 'Date'
$Gui2Text107 = 'Дата';
// / 'Type'
$Gui2Text108 = 'Тип';
// / The words in braces, such as {fit} & {count}, are filled in by the page. Each translation keeps every one of them.
// / 'Select'
$Gui2Text109 = 'Выбрать';
// / 'Select All'
$Gui2Text110 = 'Выбрать все';
// / 'Select None'
$Gui2Text111 = 'Снять выбор';
// / 'Select By Type'
$Gui2Text112 = 'Выбрать по типу';
// / 'Selected: '
$Gui2Text113 = 'Выбрано: ';
// / 'Documents'
$Gui2Text114 = 'Документы';
// / 'Spreadsheets'
$Gui2Text115 = 'Электронные таблицы';
// / 'Presentations'
$Gui2Text116 = 'Презентации';
// / 'Images'
$Gui2Text117 = 'Изображения';
// / 'Audio'
$Gui2Text118 = 'Аудио';
// / 'Video'
$Gui2Text119 = 'Видео';
// / 'Archives'
$Gui2Text120 = 'Архивы';
// / 'E-Books'
$Gui2Text121 = 'Электронные книги';
// / '3D Models'
$Gui2Text122 = '3D-модели';
// / 'Drawings'
$Gui2Text123 = 'Чертежи';
// / 'Vector Images'
$Gui2Text124 = 'Векторные изображения';
// / 'Subtitles'
$Gui2Text125 = 'Субтитры';
// / 'OpenSCAD Files'
$Gui2Text126 = 'Файлы OpenSCAD';
// / 'Streams'
$Gui2Text127 = 'Потоки';
// / 'Workflows'
$Gui2Text128 = 'Рабочие процессы';
// / 'Selected Files'
$Gui2Text129 = 'Выбранные файлы';
// / 'Select files in the list below.'
$Gui2Text130 = 'Выберите файлы в списке ниже.';
// / 'Select By Name: '
$Gui2Text131 = 'Выбрать по имени: ';
// / 'Use * and ? as wildcards, or write a regular expression between slashes.'
$Gui2Text132 = 'Используйте * и ? как подстановочные знаки или напишите регулярное выражение между косыми чертами.';
// / 'Workflow'
$Gui2Text133 = 'Рабочий процесс';
// / 'Choose a workflow'
$Gui2Text134 = 'Выберите рабочий процесс';
// / 'Run'
$Gui2Text135 = 'Запустить';
// / 'Stop'
$Gui2Text136 = 'Остановить';
// / 'Selected files that can be used: {fit} of {count}.'
$Gui2Text137 = 'Выбранные файлы, которые можно использовать: {fit} из {count}.';
// / 'Step {step} of {steps}: {operation}'
$Gui2Text138 = 'Шаг {step} из {steps}: {operation}';
// / 'File {done} of {total}'
$Gui2Text139 = 'Файл {done} из {total}';
// / 'Waiting for the server.'
$Gui2Text140 = 'Ожидание сервера.';
// / 'Starting.'
$Gui2Text141 = 'Запуск.';
// / 'Finished.'
$Gui2Text142 = 'Готово.';
// / 'Stopping.'
$Gui2Text143 = 'Остановка.';
// / 'Stopped.'
$Gui2Text144 = 'Остановлено.';
// / 'Files made: '
$Gui2Text145 = 'Созданные файлы: ';
// / 'Workflows are not available on this server.'
$Gui2Text146 = 'Рабочие процессы недоступны на этом сервере.';
// / 'No workflows are installed on this server.'
$Gui2Text147 = 'На этом сервере не установлено ни одного рабочего процесса.';
// / 'That workflow is no longer available.'
$Gui2Text148 = 'Этот рабочий процесс больше недоступен.';
// / 'None of the selected files can be used by this workflow.'
$Gui2Text149 = 'Этот рабочий процесс не может использовать ни один из выбранных файлов.';
// / 'Too many files are selected for one workflow.'
$Gui2Text150 = 'Для одного рабочего процесса выбрано слишком много файлов.';
// / 'The server is busy. Try again in a few minutes.'
$Gui2Text151 = 'Сервер занят. Повторите попытку через несколько минут.';
// / 'A workflow is already running in this session.'
$Gui2Text152 = 'В этом сеансе уже выполняется рабочий процесс.';
// / 'The workflow could not be started.'
$Gui2Text153 = 'Не удалось запустить рабочий процесс.';
// / 'A step of the workflow failed.'
$Gui2Text154 = 'Один из шагов рабочего процесса завершился ошибкой.';
// / 'A virus was found, so the workflow stopped.'
$Gui2Text155 = 'Обнаружен вирус, поэтому рабочий процесс остановлен.';
// / 'The workflow ran out of time.'
$Gui2Text156 = 'У рабочего процесса закончилось время.';
// / 'The workflow made too many files.'
$Gui2Text157 = 'Рабочий процесс создал слишком много файлов.';
// / 'The server was too busy to finish the workflow.'
$Gui2Text158 = 'Сервер был слишком загружен, чтобы завершить рабочий процесс.';
// / 'An operation this workflow needs is not available.'
$Gui2Text159 = 'Операция, необходимая этому рабочему процессу, недоступна.';
// / 'The workflow stopped unexpectedly.'
$Gui2Text160 = 'Рабочий процесс неожиданно остановился.';
// / The words in braces, such as {step} & {kind}, are filled in by the page. Each translation keeps every one of them.
// / 'Available Pipelines'
$Gui2Text161 = 'Доступные конвейеры';
// / 'Select a pipeline above...'
$Gui2Text162 = 'Выберите конвейер выше...';
// / 'Select Pipeline...'
$Gui2Text163 = 'Выбрать конвейер...';
// / 'Select Files...'
$Gui2Text164 = 'Выбрать файлы...';
// / 'Add a step'
$Gui2Text165 = 'Добавить шаг';
// / 'Options'
$Gui2Text166 = 'Параметры';
// / 'Back'
$Gui2Text167 = 'Назад';
// / 'Take: '
$Gui2Text168 = 'Взять: ';
// / 'the selected files'
$Gui2Text169 = 'выбранные файлы';
// / 'the files step {step} made'
$Gui2Text170 = 'файлы, созданные шагом {step}';
// / 'each file on its own'
$Gui2Text171 = 'каждый файл отдельно';
// / 'all files together'
$Gui2Text172 = 'все файлы вместе';
// / 'Only these kinds: '
$Gui2Text173 = 'Только эти виды: ';
// / 'Only these extensions: '
$Gui2Text174 = 'Только эти расширения: ';
// / 'Smallest size: '
$Gui2Text175 = 'Наименьший размер: ';
// / 'Largest size: '
$Gui2Text176 = 'Наибольший размер: ';
// / 'In the order they arrive'
$Gui2Text177 = 'В порядке поступления';
// / 'all files'
$Gui2Text178 = 'все файлы';
// / 'all {kind}'
$Gui2Text179 = 'все файлы: {kind}';
// / 'Width: '
$Gui2Text180 = 'Ширина: ';
// / 'Height: '
$Gui2Text181 = 'Высота: ';
// / 'My workflow'
$Gui2Text182 = 'Мой рабочий процесс';
// / 'New'
$Gui2Text183 = 'Новый';
// / 'Import'
$Gui2Text184 = 'Импорт';
// / 'Export'
$Gui2Text185 = 'Экспорт';
// / 'This workflow is not one this server can run.'
$Gui2Text186 = 'Этот сервер не может выполнить этот рабочий процесс.';
// / 'The file could not be read.'
$Gui2Text187 = 'Не удалось прочитать файл.';
// / 'The workflow builder is not available on this server.'
$Gui2Text188 = 'Редактор рабочих процессов недоступен на этом сервере.';
// / 'Status'
$Gui2Text189 = 'Состояние';
// / 'A workflow is running.'
$Gui2Text190 = 'Выполняется рабочий процесс.';
// / 'No workflow is running.'
$Gui2Text191 = 'Ни один рабочий процесс не выполняется.';
// / 'Extract'
$Gui2Text192 = 'Извлечь';
// / 'Close Extract Options'
$Gui2Text193 = 'Закрыть параметры извлечения';
// / 'Extract This Archive'
$Gui2Text194 = 'Извлечь этот архив';
// / 'Every file it holds is added to your files, under its own name or the next free one.'
$Gui2Text195 = 'Каждый файл из него добавляется к вашим файлам под своим именем или под следующим свободным.';
// / 'Extract Files'
$Gui2Text196 = 'Извлечь файлы';
// / 'to {format}'
$Gui2Text197 = 'в {format}';
// / 'This workflow deletes files. A deleted file cannot be brought back.'
$Gui2Text198 = 'Этот рабочий процесс удаляет файлы. Удалённый файл нельзя восстановить.';
// / 'Delete & Run'
$Gui2Text199 = 'Удалить и запустить';
// / 'Cancel'
$Gui2Text200 = 'Отмена';
// / 'Rename'
$Gui2Text201 = 'Переименовать';
// / 'Close Rename Options'
$Gui2Text202 = 'Закрыть параметры переименования';
// / 'Rename This File'
$Gui2Text203 = 'Переименовать этот файл';
// / 'New Name'
$Gui2Text204 = 'Новое имя';
// / 'Use {name} for the name it has, {n} for its place among the selected files & {date} for today. Its extension is kept.'
$Gui2Text205 = 'Используйте {name} для текущего имени, {n} для его места среди выбранных файлов и {date} для сегодняшней даты. Расширение сохраняется.';
// / 'Strip Metadata'
$Gui2Text206 = 'Удалить метаданные';
// / 'Close Strip Metadata Options'
$Gui2Text207 = 'Закрыть параметры метаданных';
// / 'Strip The Metadata From This File'
$Gui2Text208 = 'Удалить метаданные из этого файла';
// / 'A copy is made beside it without its authors, places, dates & the software that made it. What it shows is left as it was.'
$Gui2Text209 = 'Рядом создаётся копия без авторов, мест, дат и программы, которая его создала. То, что он показывает, остаётся прежним.';
// / 'Merge PDFs'
$Gui2Text210 = 'Объединить PDF';
// / 'Split PDF'
$Gui2Text211 = 'Разделить PDF';
// / 'Merge Or Split'
$Gui2Text212 = 'Объединить или разделить';
// / 'Close Merge & Split Options'
$Gui2Text213 = 'Закрыть параметры объединения и разделения';
// / 'Merge The Selected PDFs Into One'
$Gui2Text214 = 'Объединить выбранные PDF в один';
// / 'Their pages follow one another in the order the PDFs were selected.'
$Gui2Text215 = 'Их страницы идут друг за другом в том порядке, в котором были выбраны PDF.';
// / 'Select two or more PDFs to merge them.'
$Gui2Text216 = 'Выберите два или более PDF, чтобы объединить их.';
// / 'Make A PDF Of Each Page'
$Gui2Text217 = 'Сделать PDF из каждой страницы';
// / 'Every selected file is deleted with it: {count} files.'
$Gui2Text218 = 'Вместе с ним удаляется каждый выбранный файл: {count}.';
// / 'Every selected file is renamed with it: {count} files.'
$Gui2Text219 = 'Вместе с ним переименовывается каждый выбранный файл: {count}.';
// / 'Library'
$Gui2Text220 = 'Библиотека';
// / 'from Selected Files'
$Gui2Text221 = 'из выбранных файлов';
// / 'from Step {step}'
$Gui2Text222 = 'из шага {step}';
// / 'Preview'
$Gui2Text223 = 'Предпросмотр';
// / 'If the workflow ran now, each step would take:'
$Gui2Text224 = 'Если запустить рабочий процесс сейчас, каждый шаг возьмёт:';
// / 'Left out: '
$Gui2Text225 = 'Пропущено: ';
// / 'No files.'
$Gui2Text226 = 'Нет файлов.';
// / 'every file in the list when it runs'
$Gui2Text227 = 'все файлы списка на момент запуска шага';
// / 'This workflow deletes these files. A deleted file cannot be brought back.'
$Gui2Text228 = 'Этот рабочий процесс удаляет эти файлы. Удалённый файл нельзя восстановить.';
// / 'A step had errors, so the workflow stopped before the step that deletes files.'
$Gui2Text229 = 'В одном из шагов были ошибки, поэтому рабочий процесс остановился перед шагом, который удаляет файлы.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - HRC2-Functions.js Related Variables.
// / These strings are used by the client side javascript library.
// / That file is static javascript & cannot read a PHP variable, so every string it needs
// / is passed to it as an argument by the PHP that calls it.
// / 'Your browser does not support copying to the clipboard!'
$GuiFunctionsText1 = 'Ваш браузер не поддерживает копирование в буфер обмена.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy'
$GuiFooterText1 = 'Ознакомьтесь с нашими <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Условиями обслуживания</a> и <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Политика конфиденциальности';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Sign In Related Variables.
// / These strings are drawn by engineGui.php, the sign in page the Engine names, & by the sign in bar in the footer.
// / A sentence that names a number is written so it reads correctly for any count, so no plural helper is needed.
// / 'Sign in'
$GuiAuthText1 = 'Войти';
// / 'Sign out'
$GuiAuthText2 = 'Выйти';
// / 'Create an account'
$GuiAuthText3 = 'Создать учётную запись';
// / 'Username'
$GuiAuthText4 = 'Имя пользователя';
// / 'Password'
$GuiAuthText5 = 'Пароль';
// / 'Email address'
$GuiAuthText6 = 'Адрес электронной почты';
// / 'Code'
$GuiAuthText7 = 'Код';
// / 'New password'
$GuiAuthText8 = 'Новый пароль';
// / 'Confirm your account'
$GuiAuthText9 = 'Подтвердите учётную запись';
// / 'Recover your account'
$GuiAuthText10 = 'Восстановите учётную запись';
// / 'Choose a new password'
$GuiAuthText11 = 'Выберите новый пароль';
// / 'Send the code again'
$GuiAuthText12 = 'Отправить код ещё раз';
// / 'Forgot your password?'
$GuiAuthText13 = 'Забыли пароль?';
// / 'Have a code?'
$GuiAuthText14 = 'Есть код?';
// / 'Back to sign in'
$GuiAuthText15 = 'Вернуться ко входу';
// / 'Continue without signing in'
$GuiAuthText16 = 'Продолжить без входа';
// / 'Signed in as'
$GuiAuthText17 = 'Вы вошли как';
// / 'Signing in is unavailable on this server right now.'
$GuiAuthText18 = 'Вход на этот сервер сейчас недоступен.';
// / 'Confirm'
$GuiAuthText19 = 'Подтвердить';
// / 'Send a code'
$GuiAuthText20 = 'Отправить код';
// / 'Change password'
$GuiAuthText21 = 'Сменить пароль';
// / 'The username or password is incorrect.'
$GuiAuthText22 = 'Неверное имя пользователя или пароль.';
// / 'Too many attempts. Please wait a few minutes and try again.'
$GuiAuthText23 = 'Слишком много попыток. Подождите несколько минут и попробуйте снова.';
// / 'Confirm your account with the code we emailed you first.'
$GuiAuthText24 = 'Сначала подтвердите учётную запись кодом, который мы отправили вам по почте.';
// / 'This account cannot sign in. Contact the administrator.'
$GuiAuthText25 = 'Эта учётная запись не может войти. Обратитесь к администратору.';
// / 'New accounts cannot be created here. Contact the administrator.'
$GuiAuthText26 = 'Здесь нельзя создавать новые учётные записи. Обратитесь к администратору.';
// / 'A username is 3 to 64 letters, digits, dots, dashes or underscores, and starts with a letter or a digit.'
$GuiAuthText27 = 'Имя пользователя состоит из букв, цифр, точек, дефисов или подчёркиваний, от 3 до 64 знаков, и начинается с буквы или цифры.';
// / 'That username is taken. Choose another.'
$GuiAuthText28 = 'Это имя пользователя занято. Выберите другое.';
// / 'That email address cannot be used.'
$GuiAuthText29 = 'Этот адрес электронной почты нельзя использовать.';
// / 'Passwords must have at least this many characters:'
$GuiAuthText30 = 'Наименьшая допустимая длина пароля в знаках:';
// / 'Your account was created. We emailed you a code to confirm it.'
$GuiAuthText31 = 'Учётная запись создана. Мы отправили вам по почте код для её подтверждения.';
// / 'Your account was created, but this server could not email your code. Contact the administrator.'
$GuiAuthText32 = 'Учётная запись создана, но этот сервер не смог отправить вам код. Обратитесь к администратору.';
// / 'Your account was created. You can sign in now.'
$GuiAuthText33 = 'Учётная запись создана. Теперь вы можете войти.';
// / 'That code is not right.'
$GuiAuthText34 = 'Этот код неверен.';
// / 'That code can no longer be used. Ask for a new one.'
$GuiAuthText35 = 'Этот код больше нельзя использовать. Запросите новый.';
// / 'Your account is confirmed. You can sign in now.'
$GuiAuthText36 = 'Учётная запись подтверждена. Теперь вы можете войти.';
// / 'If that account is waiting to be confirmed, a new code is on its way.'
$GuiAuthText37 = 'Если эта учётная запись ожидает подтверждения, новый код уже в пути.';
// / 'If that account exists, a code is on its way to its email address.'
$GuiAuthText38 = 'Если эта учётная запись существует, код уже отправлен на её адрес электронной почты.';
// / 'Your password was changed. You can sign in now.'
$GuiAuthText39 = 'Пароль изменён. Теперь вы можете войти.';
// / 'You have been signed out.'
$GuiAuthText40 = 'Вы вышли из системы.';
// / 'You were signed out, because your sign in was used somewhere else.'
$GuiAuthText41 = 'Вы вышли из системы, потому что ваш вход был использован в другом месте.';
// / 'The server did not answer. Please try again.'
$GuiAuthText42 = 'Сервер не ответил. Попробуйте ещё раз.';
// / 'Recovering an account is unavailable on this server.'
$GuiAuthText43 = 'Восстановление учётных записей на этом сервере недоступно.';
// / -----------------------------------------------------------------------------------