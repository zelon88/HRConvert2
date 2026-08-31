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
// / This application requires Debian Linux, Apache 2.4, PHP 8+, FFMPEG, Dia, LibreOffice, 
// / Mkisofs, 7zip, Unoconv, libgxps-utils, Tesseract, Unzip, OpenSCAD, Rar, Inkscape, Calibre,
// / Unrar, ClamAV, MeshLab, PopplerUtils, PDFTOTEXT, ImageMagick, bwrap Dia & xvfb-run.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set a flag to tell that the UI has been displayed.
$LanguageStringsLoaded = TRUE;
// / The version of this language pack for compatibility checking.
// / Compatibility check takes place in convertCore.php, buildGui() function.
$LanguageVersion = 'v3.8.3';
$LanguageVersion = ltrim($LanguageVersion, 'vV');
// / Set the reading direction for text on the page.
$GUIDirection = 'rtl';
// / Set the side of the page to float elements to.
$GUIAlignment = 'right';
// / Define an error message to display for if the core has not been loaded.
$CoreError = 'خطا!!! HRConvert2-2، این فایل نمی‌تواند درخواست شما را پردازش کند! لطفاً فایل خود را به جای آن به convertCore.php ارسال کنید!';
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die($CoreError);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Check for required core variables.
if (!isset($Font)) $Font = 'Arial';
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = 'هر چیزی را تبدیل کنید!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI Related Logic.
if (!is_numeric($FileCount)) $FileCount = 'تعداد نامشخصی';
$FCPlural1 = ' عدد فایل معتبر';
$FCPlural2 = '‌های شما اکنون آماده تبدیل هستند';
if ($FileCount == 1) {
  $FCPlural1 = ' عدد فایل معتبر';
  $FCPlural2 = ' شما اکنون آماده تبدیل است'; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Header Related Variables.
// / 'Click, Tap, or Drop files here to upload.'
$GuiHeaderText1 = 'برای بارگذاری، اینجا کلیک کنید، ضربه بزنید یا فایل‌ها را رها کنید.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - UI Selector Bar Related Variables.
// / These strings appear in the selector bar, which is present on both GUI1 & GUI2.
// / 'Language'
$GuiSelectorText1 = 'زبان';
// / 'Color'
$GuiSelectorText2 = 'رنگ';
// / 'Interface'
$GuiSelectorText3 = 'رابط کاربری';
// / 'Display language, color and interface options'
$GuiSelectorText4 = 'نمایش گزینه‌های زبان، رنگ و رابط کاربری';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / Online File Converter, Extractor, Compressor'
$Gui1Text1 = 'مبدل، استخراج‌کننده و فشرده‌ساز آنلاین فایل';
// / $ApplicationName.' is based off the open-source web-app <a href=\'https://github.com\'>HRConvert2</a> by <a href=\'https://github.com\'>Zelon88</a> that converts files without tracking users across the net or infringing on your intellectual property.'
$Gui1Text2 = $ApplicationName.' بر اساس برنامه وب متن‌باز <a href=\'https://github.com\'>HRConvert2</a> اثر <a href=\'https://github.com\'>Zelon88</a> توسعه یافته است که فایل‌ها را بدون ردیابی کاربران در اینترنت یا نقض مالکیت معنوی شما تبدیل می‌کند.';
// / 'More Info ...'
$Gui1Text3 = 'اطلاعات بیشتر ...';
// / 'Less Info ...'
$Gui1Text4 = 'اطلاعات کمتر ...';
// / 'All user-supplied data is erased automatically, so you don\'t need to worry about forfeiting your personal information or property while using our services.'
$Gui1Text5 = 'تمام داده‌های ارائه شده توسط کاربر به طور خودکار حذف می‌شوند، بنابراین نیازی نیست نگران از دست رفتن اطلاعات شخصی یا دارایی خود در هنگام استفاده از خدمات ما باشید.';
// / 'Currently '.$ApplicationName.' supports '.$SupportedFormatCount.' different file formats, including documents, spreadsheets, images, media, 3D models, CAD drawings, vector files, archives, disk images, & more.'
$Gui1Text6 = 'در حال حاضر '.$ApplicationName.' از '.$SupportedFormatCount.' فرمت فایل مختلف پشتیبانی می‌کند، از جمله اسناد، صفحات گسترده، تصاویر، رسانه‌ها، مدل‌های سه‌بعدی، نقشه‌های CAD، فایل‌های برداری، بایگانی‌ها، ایمیج‌های دیسک و غیره.';
// / 'View Supported Formats ...'
$Gui1Text7 = 'مشاهده فرمت‌های پشتیبانی شده ...';
// / 'Hide Supported Formats ...'
$Gui1Text8 = 'پنهان کردن فرمت‌های پشتیبانی شده ...';
// / 'Supported Formats'
$Gui1Text9 = 'فرمت‌های پشتیبانی شده';
// / 'Audio Formats'
$Gui1Text10 = 'فرمت‌های صوتی';
// / 'Supports specific bitrate.'
$Gui1Text11 = 'از نرخ داده (Bitrate) مشخصی پشتیبانی می‌کند.';
// / 'Video Formats'
$Gui1Text12 = 'فرمت‌های ویدئویی';
// / 'Stream Formats'
$Gui1Text13 = 'فرمت‌های استریم';
// / 'Document Formats'
$Gui1Text14 = 'فرمت‌های سند';
// / 'Spreadsheet Formats'
$Gui1Text15 = 'فرمت‌های صفحه گسترده';
// / 'Presentation Formats'
$Gui1Text16 = 'فرمت‌های ارائه';
// / 'Archive Formats'
$Gui1Text17 = 'فرمت‌های بایگانی';
// / 'Can convert between archive formats & disk image formats.'
$Gui1Text18 = 'می‌تواند بین فرمت‌های بایگانی انتخاب شده و فرمت‌های ایمیج دیسک تبدیل انجام دهد.';
// / 'Image Formats'
$Gui1Text19 = 'فرمت‌های تصویر';
// / 'Can convert pictures of documents to document formats.'
$Gui1Text20 = 'می‌تواند تصاویر اسناد را به فرمت‌های متنی و سند تبدیل کند.';
// / 'Supports resize & rotate.'
$Gui1Text21 = 'از تغییر اندازه و چرخش پشتیبانی می‌کند.';
// / '3D Model Formats'
$Gui1Text22 = 'فرمت‌های مدل سه‌بعدی';
// / 'Drawing Formats'
$Gui1Text23 = 'فرمت‌های نقشه کشی';
// / 'Can convert drawing files to image formats.'
$Gui1Text24 = 'می‌تواند فرمت‌های نقشه‌کشی را به فرمت‌های تصویری تبدیل کند.';
// / 'OCR Support'
$Gui1Text25 = 'پشتیبانی از OCR';
// / 'OCR Operations support the following input formats...'
$Gui1Text26 = 'عملیات OCR از فرمت‌های ورودی زیر پشتیبانی می‌کند...';
// / 'OCR Operations support the following output formats...'
$Gui1Text27 = 'عملیات OCR از فرمت‌های خروجی زیر پشتیبانی می‌کند...';
// / 'Select files by clicking, tapping, or dropping them into the box below.'
$Gui1Text28 = 'فایل‌ها را با کلیک کردن، ضربه زدن یا رها کردن آن‌ها در کادر زیر انتخاب کنید.';
// / 'Continue ...'
$Gui1Text29 = 'ادامه ...';
// / 'Can convert stream formats to video formats.'
$Gui1Text30 = 'می‌تواند فرمت‌های استریم را به فرمت‌های ویدئویی تبدیل کند.';
// / 'Subtitle Formats'
$Gui1Text31 = 'فرمت‌های زیرنویس';
// / 'OpenSCAD Formats'
$Gui1Text32 = 'فرمت‌های OpenSCAD';
// / 'Renders OpenSCAD source into 3D model formats.'
$Gui1Text33 = 'سورس OpenSCAD را به فرمت‌های مدل سه‌بعدی رندر می‌کند.';
// / 'File references inside uploaded sources are removed unless the server allows resolving them.'
$Gui1Text34 = 'ارجاعات فایل در سورس‌های بارگذاری شده حذف می‌شوند، مگر اینکه سرور اجازه پردازش آن‌ها را بدهد.';
// / 'Delete every uploaded file & start a new session?'
// / Shown inside the start over panel on the upload page. That panel is only rendered
// / when the session already holds at least one file, so this is never shown to a first
// / time visitor who has nothing to lose.
$Gui1Text35 = 'همه فایل‌های بارگذاری‌شده حذف و نشست جدیدی آغاز شود؟';
// / 'Start Over'
// / Labels the control that opens the panel & the button inside it that confirms.
$Gui1Text36 = 'شروع دوباره';
// / 'Refresh'
// / Alternate text for the refresh control on the upload page. That control is a glyph
// / with no text of its own, so this is the only description a screen reader has & the
// / only thing shown when a browser cannot render the glyph.
$Gui1Text37 = 'تازه‌سازی';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'گزینه‌های تبدیل فایل';
// / 'Bulk File Options'
$Gui2Text2 = 'گزینه‌های دسته‌ای فایل';
// / 'Scan All Files For Viruses'
$Gui2Text3 = 'اسکن تمام فایل‌ها برای ویروس';
// / 'Compress & Download All Files'
$Gui2Text4 = 'فشرده‌سازی و دانلود تمام فایل‌ها';
// / 'Download'
$Gui2Text5 = 'دانلود';
// / 'Share'
$Gui2Text6 = 'اشتراک‌گذاری';
// / 'Close Share Options'
$Gui2Text7 = 'بستن گزینه‌های اشتراک‌گذاری';
// / 'Virus Scan'
$Gui2Text8 = 'اسکن ویروس';
// / 'Close Virus Scan Options'
$Gui2Text9 = 'بستن گزینه‌های اسکن ویروس';
// / 'Archive'
$Gui2Text10 = 'بایگانی';
// / 'Close Archive Options'
$Gui2Text11 = 'بستن گزینه‌های بایگانی';
// / 'OCR'
$Gui2Text12 = 'OCR';
// / 'Close OCR Options'
$Gui2Text13 = 'بستن گزینه‌های OCR';
// / 'Convert'
$Gui2Text14 = 'تبدیل';
// / 'Close Convert Options'
$Gui2Text15 = 'بستن گزینه‌های تبدیل';
// / 'Archive This File'
$Gui2Text16 = 'بایگانی کردن این فایل';
// / 'Specify Filename: '
$Gui2Text17 = 'تعیین نام فایل: ';
// / 'Format'
$Gui2Text18 = 'فرمت';
// / 'Compress & Download'
$Gui2Text19 = 'فشرده‌سازی و دانلود';
// / 'Scan with ClamAV: '
$Gui2Text20 = 'اسکن با ClamAV: ';
// / 'Scan with ScanCore: '
$Gui2Text21 = 'اسکن با ScanCore: ';
// / 'Scan All'
$Gui2Text22 = 'اسکن همه';
// / 'Share This File'
$Gui2Text23 = 'اشتراک‌گذاری این فایل';
// / 'Link Status: '
$Gui2Text24 = 'وضعیت لینک: ';
// / 'Not Generated'
$Gui2Text25 = 'ایجاد نشده';
// / 'Generated'
$Gui2Text26 = 'ایجاد شده';
// / 'Clipboard Status: '
$Gui2Text27 = 'وضعیت کلیپ‌بورد: ';
// / 'Copied'
$Gui2Text28 = 'کپی شد';
// / 'File Link: '
$Gui2Text29 = 'لینک فایل: ';
// / 'You have uploaded '.$FileCount.' valid file'.$FCPlural1.' to '.$ApplicationName.'.'
$Gui2Text30 = 'شما تعداد '.$FileCount.$FCPlural1.' را در '.$ApplicationName.' بارگذاری کرده‌اید.';
// / 'Your file'.$FCPlural2.' now ready to convert using the options below.'
$Gui2Text31 = 'فایل'.$FCPlural2.' با استفاده از گزینه‌های زیر است.';
// / 'Generate Link & Copy to Clipboard'
$Gui2Text32 = 'ایجاد لینک و کپی در کلیپ‌بورد';
// / 'Generate Link'
$Gui2Text33 = 'ایجاد لینک';
// / 'Scan This File For Viruses'
$Gui2Text34 = 'اسکن این فایل برای ویروس';
// / 'Scan File With ScanCore'
$Gui2Text35 = 'اسکن فایل با ScanCore';
// / 'Scan File With ClamAV'
$Gui2Text36 = 'اسکن فایل با ClamAV';
// / 'Scan File With ScanCore & ClamAV'
$Gui2Text37 = 'اسکن فایل با ScanCore و ClamAV';
// / 'Perform Optical Character Recognition On This File'
$Gui2Text38 = 'انجام تشخیص نوری نویسه‌ها (OCR) روی این فایل';
// / 'Method'
$Gui2Text39 = 'روش';
// / 'Simple'
$Gui2Text40 = 'ساده';
// / 'Advanced'
$Gui2Text41 = 'پیشرفته';
// / 'Convert This Archive'
$Gui2Text42 = 'تبدیل این فایل بایگانی';
// / 'Convert This Document'
$Gui2Text43 = 'تبدیل این سند';
// / 'Convert This Spreadsheet'
$Gui2Text44 = 'تبدیل این صفحه گسترده';
// / 'Convert This Audio'
$Gui2Text45 = 'تبدیل این فایل صوتی';
// / 'Convert This Video'
$Gui2Text46 = 'تبدیل این ویدئو';
// / 'Convert This Stream'
$Gui2Text47 = 'تبدیل این استریم';
// / Convert This 3D Model'
$Gui2Text48 = 'تبدیل این مدل سه‌بعدی';
// / 'Convert This Technical Drawing Or Vector File'
$Gui2Text49 = 'تبدیل این نقشه فنی یا فایل برداری';
// / 'Convert This Image'
$Gui2Text50 = 'تبدیل این تصویر';
// / 'Archive File'
$Gui2Text51 = 'بایگانی کردن فایل';
// / 'Convert Into Document'
$Gui2Text52 = 'تبدیل به سند';
// / 'Archive Files'
$Gui2Text53 = 'بایگانی کردن فایل‌ها';
// / 'Convert Document'
$Gui2Text54 = 'تبدیل سند';
// / 'Convert Spreadsheet'
$Gui2Text55 = 'تبدیل صفحه گسترده';
// / 'Convert Presentation'
$Gui2Text56 = 'تبدیل ارائه';
// / 'Convert Audio'
$Gui2Text57 = 'تبدیل فایل صوتی';
// / 'Convert Video'
$Gui2Text58 = 'تبدیل ویدئو';
// / 'Convert Stream'
$Gui2Text59 = 'تبدیل استریم';
// / 'Convert Model'
$Gui2Text60 = 'تبدیل مدل';
// / 'Convert Drawing'
$Gui2Text61 = 'تبدیل نقشه';
// / 'Convert Image'
$Gui2Text62 = 'تبدیل تصویر';
// / 'Width & Height'
$Gui2Text64 = 'عرض و ارتفاع: ';
// / 'Rotate: '
$Gui2Text65 = 'چرخش: ';
// / 'Bitrate: '
$Gui2Text66 = 'نرخ داده: ';
// / 'Delete'
$Gui2Text67 = 'حذف';
// / 'Close Delete Options'
$Gui2Text68 = 'بستن گزینه‌های حذف';
// / 'Delete This File'
$Gui2Text69 = 'حذف این فایل';
// / 'Confirm Delete'
$Gui2Text70 = 'تایید حذف';
// / 'Cannot convert this file! Try changing the name.'
$Gui2Text71 = 'امکان تبدیل این فایل وجود ندارد! نام آن را تغییر دهید.';
// / 'Cannot perform a virus scan on this file!'
$Gui2Text72 = 'امکان انجام اسکن ویروس روی این فایل وجود ندارد!';
// / 'File Link Copied to Clipboard!'
$Gui2Text73 = 'لینک فایل در کلیپ‌بورد کپی شد!';
// / 'Operation Failed!'
$Gui2Text74 = 'عملیات ناموفق بود!';
// / 'Convert These Subtitles'
$Gui2Text75 = 'تبدیل این زیرنویس‌ها';
// / 'Convert Subtitles'
$Gui2Text76 = 'تبدیل زیرنویس';
// / 'Convert This Presentation'
$Gui2Text77 = 'تبدیل این ارائه';
// / 'Convert This XPS File'
$Gui2Text78 = 'تبدیل این فایل XPS';
// / 'Render This OpenSCAD Model'
$Gui2Text79 = 'رندر این مدل OpenSCAD';
// / 'Render Model'
$Gui2Text80 = 'رندر مدل';
// / 'Convert This E-Book'
$Gui2Text81 = 'تبدیل این کتاب الکترونیکی';
// / 'Convert E-Book'
$Gui2Text82 = 'تبدیل کتاب الکترونیکی';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - HRC2-Functions.js Related Variables.
// / These strings are used by the client side javascript library.
// / That file is static javascript & cannot read a PHP variable, so every string it needs
// / is passed to it as an argument by the PHP that calls it.
// / 'Your browser does not support copying to the clipboard!'
$GuiFunctionsText1 = 'مرورگر شما از کپی کردن در کلیپ‌بورد پشتیبانی نمی‌کند!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy'
$GuiFooterText1 = 'از <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>شرایط خدمات</a> و <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>سیاست حریم خصوصی</a> ما دیدن کنید';
// / -----------------------------------------------------------------------------------
