<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRConvert2, Copyright on 8/5/2026 by Justin Grimes, www.github.com/zelon88
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
// / v3.5.8.
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
// / Set the reading direction for text on the page.
$GUIDirection = 'rtl';
// / Set the side of the page to float elements to.
$GUIAlignment = 'right';
// / Define an error message to display for if the core has not been loaded.
$CoreError = 'غلطی!!! HRConvert2-2، یہ فائل آپ کی درخواست پر کارروائی نہیں کر سکتی! براہ کرم اپنی فائل اس کے بجائے convertCore.php پر جمع کرائیں!';
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die($CoreError);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Check for required core variables.
if (!isset($Font)) $Font = 'Arial';
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = 'کچھ بھی تبدیل کریں!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI Related Logic.
if (!is_numeric($FileCount)) $FileCount = 'نامعلوم تعداد میں';
$FCPlural1 = ' عدد فائلیں معتبر';
$FCPlural2 = 'یں اب تبدیل ہونے کے لیے تیار ہیں';
if ($FileCount == 1) {
  $FCPlural1 = ' عدد فائل معتبر';
  $FCPlural2 = ' اب تبدیل ہونے کے لیے تیار ہے'; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Header Related Variables.
// / 'Click, Tap, or Drop files here to upload.'
$GuiHeaderText1 = 'اپ لوڈ کرنے کے لیے فائلیں یہاں کلک کریں، ٹیپ کریں یا ڈراپ کریں۔';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / Online File Converter, Extractor, Compressor'
$Gui1Text1 = 'آن لائن فائل کنورٹر، ایکسٹریکٹر، کمپریسر';
// / $ApplicationName.' is based off the open-source web-app <a href=\'https://github.com\'>HRConvert2</a> by <a href=\'https://github.com\'>Zelon88</a> that converts files without tracking users across the net or infringing on your intellectual property.'
$Gui1Text2 = $ApplicationName.' اوپن-سورس ویب ایپ <a href=\'https://github.com\'>HRConvert2</a> پر مبنی ہے جسے <a href=\'https://github.com\'>Zelon88</a> نے بنایا ہے، جو انٹرنیٹ پر صارفین کو ٹریک کیے بغیر یا آپ کے دانشورانہ املاک کی خلاف ورزی کیے بغیر فائلیں تبدیل کرتی ہے۔';
// / 'More Info ...'
$Gui1Text3 = 'مزید معلومات ...';
// / 'Less Info ...'
$Gui1Text4 = 'کم معلومات ...';
// / 'All user-supplied data is erased automatically, so you don\'t need to worry about forfeiting your personal information or property while using our services.'
$Gui1Text5 = 'صارف کا فراہم کردہ تمام ڈیٹا خود بخود حذف ہو جاتا ہے، اس لیے ہماری خدمات استعمال کرتے وقت آپ کو اپنی ذاتی معلومات یا املاک کے ضائع ہونے کی فکر کرنے کی ضرورت نہیں ہے۔';
// / 'Currently '.$ApplicationName.' supports '.$SupportedFormatCount.' different file formats, including documents, spreadsheets, images, media, 3D models, CAD drawings, vector files, archives, disk images, & more.'
$Gui1Text6 = 'فی الحال '.$ApplicationName.' فائلوں کے '.$SupportedFormatCount.' مختلف فارمیٹس کو سپورٹ کرتا ہے، بشمول دستاویزات، اسپرSpreadsheets، تصاویر، میڈیا، 3D ماڈلز، CAD ڈرائنگز، ویکٹر فائلیں، آرکائیوز، ڈسک امیجز اور بہت کچھ۔';
// / 'View Supported Formats ...'
$Gui1Text7 = 'سپورٹڈ فارمیٹس دیکھیں ...';
// / 'Hide Supported Formats ...'
$Gui1Text8 = 'سپورٹڈ فارمیٹس چھپائیں ...';
// / 'Supported Formats'
$Gui1Text9 = 'سپورٹڈ فارمیٹس';
// / 'Audio Formats'
$Gui1Text10 = 'آڈیو فارمیٹس';
// / 'Supports specific bitrate.'
$Gui1Text11 = 'مخصوص ڈیٹا ریٹ کی سپورٹ کرتا ہے۔';
// / 'Video Formats'
$Gui1Text12 = 'ویڈیو فارمیٹس';
// / 'Stream Formats'
$Gui1Text13 = 'اسٹریم فارمیٹس';
// / 'Document Formats'
$Gui1Text14 = 'دستاویزی فارمیٹس';
// / 'Spreadsheet Formats'
$Gui1Text15 = 'اسپرSpreadsheet فارمیٹس';
// / 'Presentation Formats'
$Gui1Text16 = 'پریزنٹیشن فارمیٹس';
// / 'Archive Formats'
$Gui1Text17 = 'آرکائیو فارمیٹس';
// / 'Can convert between archive formats & disk image formats.'
$Gui1Text18 = 'منتخب آرکائیو فارمیٹس اور ڈسک امیج فارمیٹس کے درمیان تبدیل کر سکتا ہے۔';
// / 'Image Formats'
$Gui1Text19 = 'تصویری فارمیٹس';
// / 'Can convert pictures of documents to document formats.'
$Gui1Text20 = 'دستاویزات کی تصاویر کو دستاویزی فارمیٹس میں تبدیل کر سکتا ہے۔';
// / 'Supports resize & rotate.'
$Gui1Text21 = 'سائز کی تبدیلی اور گھمانے کی سپورٹ کرتا ہے۔';
// / '3D Model Formats'
$Gui1Text22 = '3D ماڈل فارمیٹس';
// / 'Drawing Formats'
$Gui1Text23 = 'ڈرائنگ فارمیٹس';
// / 'Can convert drawing files to image formats.'
$Gui1Text24 = 'ڈرائنگ فارمیٹس کو تصویری فارمیٹس میں تبدیل کر سکتا ہے۔';
// / 'OCR Support'
$Gui1Text25 = 'OCR سپورٹ';
// / 'OCR Operations support the following input formats...'
$Gui1Text26 = 'OCR آپریشنز درج ذیل ان پٹ فارمیٹس کو سپورٹ کرتے ہیں...';
// / 'OCR Operations support the following output formats...'
$Gui1Text27 = 'OCR آپریشنز درج ذیل آؤٹ پٹ فارمیٹس کو سپورٹ کرتے ہیں...';
// / 'Select files by clicking, tapping, or dropping them into the box below.'
$Gui1Text28 = 'نیچے دیے گئے باکس میں فائلوں پر کلک، ٹیپ، یا ڈراپ کر کے منتخب کریں۔';
// / 'Continue ...'
$Gui1Text29 = 'جاری رکھیں ...';
// / 'Can convert stream formats to video formats.'
$Gui1Text30 = 'اسٹریم فارمیٹس کو ویڈیو فارمیٹس میں تبدیل کر سکتا ہے۔';
// / 'Subtitle Formats'
$Gui1Text31 = 'سب ٹائٹل فارمیٹس';
// / 'OpenSCAD Formats'
$Gui1Text32 = 'OpenSCAD فارمیٹس';
// / 'Renders OpenSCAD source into 3D model formats.'
$Gui1Text33 = 'OpenSCAD سورس کو 3D ماڈل فارمیٹس میں رینڈر کرتا ہے۔';
// / 'File references inside uploaded sources are removed unless the server allows resolving them.'
$Gui1Text34 = 'اپ لوڈ کردہ سورسز کے اندر فائل کے حوالے حذف کر دیے جاتے ہیں جب تک کہ سرور انہیں حل کرنے کی اجازت نہ دے۔';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'فائل تبدیل کرنے کے اختیارات';
// / 'Bulk File Options'
$Gui2Text2 = 'بلک فائل اختیارات';
// / 'Scan All Files For Viruses'
$Gui2Text3 = 'وائرس کے لیے تمام فائلوں کو اسکین کریں';
// / 'Compress & Download All Files'
$Gui2Text4 = 'تمام فائلوں کو کمپریس اور ڈاؤن لوڈ کریں';
// / 'Download'
$Gui2Text5 = 'ڈاؤن لوڈ';
// / 'Share'
$Gui2Text6 = 'شیئر کریں';
// / 'Close Share Options'
$Gui2Text7 = 'شیئرنگ کے اختیارات بند کریں';
// / 'Virus Scan'
$Gui2Text8 = 'وائرس اسکین';
// / 'Close Virus Scan Options'
$Gui2Text9 = 'وائرس اسکین کے اختیارات بند کریں';
// / 'Archive'
$Gui2Text10 = 'آرکائیو';
// / 'Close Archive Options'
$Gui2Text11 = 'آرکائیو کے اختیارات بند کریں';
// / 'OCR'
$Gui2Text12 = 'OCR';
// / 'Close OCR Options'
$Gui2Text13 = 'OCR کے اختیارات بند کریں';
// / 'Convert'
$Gui2Text14 = 'تبدیل کریں';
// / 'Close Convert Options'
$Gui2Text15 = 'تبدیلی کے اختیارات بند کریں';
// / 'Archive This File'
$Gui2Text16 = 'اس فائل کو آرکائیو کریں';
// / 'Specify Filename: '
$Gui2Text17 = 'فائل کا نام درج کریں: ';
// / 'Format'
$Gui2Text18 = 'فارمیٹ';
// / 'Compress & Download'
$Gui2Text19 = 'کمپریس اور ڈاؤن لوڈ کریں';
// / 'Scan with ClamAV: '
$Gui2Text20 = 'ClamAV کے ساتھ اسکین کریں: ';
// / 'Scan with ScanCore: '
$Gui2Text21 = 'ScanCore کے ساتھ اسکین کریں: ';
// / 'Scan All'
$Gui2Text22 = 'سب اسکین کریں';
// / 'Share This File'
$Gui2Text23 = 'اس فائل کو شیئر کریں';
// / 'Link Status: '
$Gui2Text24 = 'لینک کی صورتحال: ';
// / 'Not Generated'
$Gui2Text25 = 'تخلیق نہیں ہوا';
// / 'Generated'
$Gui2Text26 = 'تخلیق ہو گیا';
// / 'Clipboard Status: '
$Gui2Text27 = 'کلپ بورڈ کی صورتحال: ';
// / 'Copied'
$Gui2Text28 = 'کاپی ہو گیا';
// / 'File Link: '
$Gui2Text29 = 'فائل کا لنک: ';
// / 'You have uploaded '.$FileCount.' valid file'.$FCPlural1.' to '.$ApplicationName.'.'
$Gui2Text30 = 'آپ نے '.$ApplicationName.' پر '.$FileCount.$FCPlural1.' اپ لوڈ کی ہیں۔';
// / 'Your file'.$FCPlural2.' now ready to convert using the options below.'
$Gui2Text31 = 'آپ کی فائل' . $FCPlural2 . ' نیچے دیے گئے اختیارات کا استعمال کرتے ہوئے تبدیل ہونے کے لیے تیار ہے۔';
// / 'Generate Link & Copy to Clipboard'
$Gui2Text32 = 'لینک تخلیق کریں اور کلپ بورڈ پر کاپی کریں';
// / 'Generate Link'
$Gui2Text33 = 'لینک تخلیق کریں';
// / 'Scan This File For Viruses'
$Gui2Text34 = 'اس فائل کو وائرس کے لیے اسکین کریں';
// / 'Scan File With ScanCore'
$Gui2Text35 = 'ScanCore کے ساتھ فائل اسکین کریں';
// / 'Scan File With ClamAV'
$Gui2Text36 = 'ClamAV کے ساتھ فائل اسکین کریں';
// / 'Scan File With ScanCore & ClamAV'
$Gui2Text37 = 'ScanCore اور ClamAV دونوں سے فائل اسکین کریں';
// / 'Perform Optical Character Recognition On This File'
$Gui2Text38 = 'اس فائل پر آپٹیکل کریکٹر ریکگنیشن (OCR) لاگو کریں';
// / 'Method'
$Gui2Text39 = 'طریقہ';
// / 'Simple'
$Gui2Text40 = 'سادہ';
// / 'Advanced'
$Gui2Text41 = 'ایڈوانسڈ';
// / 'Convert This Archive'
$Gui2Text42 = 'اس آرکائیو کو تبدیل کریں';
// / 'Convert This Document'
$Gui2Text43 = 'اس دستاویز کو تبدیل کریں';
// / 'Convert This Spreadsheet'
$Gui2Text44 = 'اس اسپرSpreadsheet کو تبدیل کریں';
// / 'Convert This Audio'
$Gui2Text45 = 'اس آڈیو کو تبدیل کریں';
// / 'Convert This Video'
$Gui2Text46 = 'اس ویڈیو کو تبدیل کریں';
// / 'Convert This Stream'
$Gui2Text47 = 'اس اسٹریم کو تبدیل کریں';
// / Convert This 3D Model'
$Gui2Text48 = 'اس 3D ماڈل کو تبدیل کریں';
// / 'Convert This Technical Drawing Or Vector File'
$Gui2Text49 = 'اس ٹیکنیکل ڈرائنگ یا ویکٹر فائل کو تبدیل کریں';
// / 'Convert This Image'
$Gui2Text50 = 'اس تصویر کو تبدیل کریں';
// / 'Archive File'
$Gui2Text51 = 'فائل کو آرکائیو کریں';
// / 'Convert Into Document'
$Gui2Text52 = 'دستاویز میں تبدیل کریں';
// / 'Archive Files'
$Gui2Text53 = 'فائلوں کو آرکائیو کریں';
// / 'Convert Document'
$Gui2Text54 = 'دستاویز تبدیل کریں';
// / 'Convert Spreadsheet'
$Gui2Text55 = 'اسپرSpreadsheet تبدیل کریں';
// / 'Convert Presentation'
$Gui2Text56 = 'پریزنٹیشن تبدیل کریں';
// / 'Convert Audio'
$Gui2Text57 = 'آڈیو تبدیل کریں';
// / 'Convert Video'
$Gui2Text58 = 'ویڈیو تبدیل کریں';
// / 'Convert Stream'
$Gui2Text59 = 'اسٹریم تبدیل کریں';
// / 'Convert Model'
$Gui2Text60 = 'ماڈل تبدیل کریں';
// / 'Convert Drawing'
$Gui2Text61 = 'ڈرائنگ تبدیل کریں';
// / 'Convert Image'
$Gui2Text62 = 'تصویر تبدیل کریں';
// / 'Width & Height'
$Gui2Text64 = 'چوڑائی اور اونچائی: ';
// / 'Rotate: '
$Gui2Text65 = 'گھمائیں: ';
// / 'Bitrate: '
$Gui2Text66 = 'ڈیٹا ریٹ: ';
// / 'Delete'
$Gui2Text67 = 'حذف کریں';
// / 'Close Delete Options'
$Gui2Text68 = 'حذف کرنے کے اختیارات بند کریں';
// / 'Delete This File'
$Gui2Text69 = 'یہ فائل حذف کریں';
// / 'Confirm Delete'
$Gui2Text70 = 'حذف کرنے کی تصدیق کریں';
// / 'Cannot convert this file! Try changing the name.'
$Gui2Text71 = 'یہ فائل تبدیل نہیں ہو سکتی! نام تبدیل کرنے کی کوشش کریں۔';
// / 'Cannot perform a virus scan on this file!'
$Gui2Text72 = 'اس فائل پر وائرس اسکین نہیں کیا جا سکتا!';
// / 'File Link Copied to Clipboard!'
$Gui2Text73 = 'فائل کا لنک کلپ بورڈ پر کاپی ہو گیا!';
// / 'Operation Failed!'
$Gui2Text74 = 'عمل ناکام ہو گیا!';
// / 'Convert These Subtitles'
$Gui2Text75 = 'ان سب ٹائٹلز کو تبدیل کریں';
// / 'Convert Subtitles'
$Gui2Text76 = 'سب ٹائٹل تبدیل کریں';
// / 'Convert This Presentation'
$Gui2Text77 = 'اس پریزنٹیشن کو تبدیل کریں';
// / 'Convert This XPS File'
$Gui2Text78 = 'اس XPS فائل کو تبدیل کریں';
// / 'Render This OpenSCAD Model'
$Gui2Text79 = 'اس OpenSCAD ماڈل کو رینڈر کریں';
// / 'Render Model'
$Gui2Text80 = 'ماڈل رینڈر کریں';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - HRC2-Functions.js Related Variables.
// / These strings are used by the client side javascript library.
// / That file is static javascript & cannot read a PHP variable, so every string it needs
// / is passed to it as an argument by the PHP that calls it.
// / 'Your browser does not support copying to the clipboard!'
$GuiFunctionsText1 = 'آپ کا براؤزر کلپ بورڈ پر کاپی کرنے کی صلاحیت نہیں رکھتا!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy'
$GuiFooterText1 = 'ہماری <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>سروس کی شرائط</a> اور <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>رازداری کی پالیسی</a> ملاحظہ کریں';
// / -----------------------------------------------------------------------------------
