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
$GUIDirection = 'rtl';
// / Set the side of the page to float elements to.
$GUIAlignment = 'right';
// / Which end of a dropdown the arrow sits at, as a percentage across the control.
// / This pack knows its own reading direction & is therefore the right place to decide.
// / A reader finishes at the left of a right to left line, so the arrow belongs there.
// / An arrow pinned to the right edge sits at the START of the box in this language &
// / reads as a stray icon rather than as the thing that opens the list.
$SelectArrowSide = ($GUIAlignment === 'right') ? '4%' : '96%';
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
// / Set GUI - UI Selector Bar Related Variables.
// / These strings appear in the selector bar, which is present on both GUI1 & GUI2.
// / 'Language'
$GuiSelectorText1 = 'زبان';
// / 'Color'
$GuiSelectorText2 = 'رنگ';
// / 'Interface'
$GuiSelectorText3 = 'انٹرفیس';
// / 'Display language, color and interface options'
$GuiSelectorText4 = 'زبان، رنگ اور انٹرفیس کے اختیارات دکھائیں';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / 'Online File Converter, Extractor, Compressor'
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
// / 'Delete every uploaded file & start a new session?'
// / Shown inside the start over panel on the upload page. That panel is only rendered
// / when the session already holds at least one file, so this is never shown to a first
// / time visitor who has nothing to lose.
$Gui1Text35 = 'تمام اپ لوڈ کردہ فائلیں حذف کر کے نیا سیشن شروع کریں؟';
// / 'Start Over'
// / Labels the control that opens the panel & the button inside it that confirms.
$Gui1Text36 = 'نئے سرے سے شروع کریں';
// / 'Refresh'
// / Alternate text for the refresh control on the upload page. That control is a glyph
// / with no text of its own, so this is the only description a screen reader has & the
// / only thing shown when a browser cannot render the glyph.
$Gui1Text37 = 'تازہ کریں';
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
// / 'Convert This 3D Model'
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
// / 'Convert This E-Book'
$Gui2Text81 = 'اس ای بک کو تبدیل کریں';
// / 'Convert E-Book'
$Gui2Text82 = 'ای بک تبدیل کریں';
// / 'Playlist source URL, only needed if the playlist uses relative paths'
$Gui2Text83 = 'پلے لسٹ کا ماخذ یو آر ایل، صرف اس صورت میں درکار جب نسبتی راستے استعمال ہوں';
// / 'Download A File From A URL'
$Gui2Text84 = 'یو آر ایل سے فائل ڈاؤن لوڈ کریں';
// / 'Fetch'
$Gui2Text85 = 'حاصل کریں';
// / 'Identify'
$Gui2Text86 = 'شناخت کریں';
// / 'Close Identify Options'
$Gui2Text87 = 'شناخت کے اختیارات بند کریں';
// / 'Identify This File'
$Gui2Text88 = 'اس فائل کی شناخت کریں';
// / 'Size: '
$Gui2Text89 = 'سائز: ';
// / 'Modified: '
$Gui2Text90 = 'ترمیم شدہ: ';
// / 'Type: '
$Gui2Text91 = 'قسم: ';
// / 'MIME Type: '
$Gui2Text92 = 'MIME قسم: ';
// / 'Character Set: '
$Gui2Text93 = 'حروف کا مجموعہ: ';
// / 'Extension Matches Content: '
$Gui2Text94 = 'ایکسٹینشن مواد سے مطابقت رکھتی ہے: ';
// / 'Details: '
$Gui2Text95 = 'تفصیلات: ';
// / 'Notes: '
$Gui2Text96 = 'نوٹس: ';
// / 'Save report as file'
$Gui2Text97 = 'رپورٹ کو فائل کے طور پر محفوظ کریں';
// / 'Identify All Files'
$Gui2Text98 = 'تمام فائلوں کی شناخت کریں';
// / 'Identify All'
$Gui2Text99 = 'سب کی شناخت کریں';
// / 'Yes'
$Gui2Text100 = 'ہاں';
// / 'No'
$Gui2Text101 = 'نہیں';
// / 'Unknown'
$Gui2Text102 = 'نامعلوم';
// / 'Cannot identify this file!'
$Gui2Text103 = 'اس فائل کی شناخت نہیں کی جا سکتی!';
// / 'Sort By: '
$Gui2Text104 = 'ترتیب بلحاظ: ';
// / 'Name'
$Gui2Text105 = 'نام';
// / 'Size'
$Gui2Text106 = 'سائز';
// / 'Date'
$Gui2Text107 = 'تاریخ';
// / 'Type'
$Gui2Text108 = 'قسم';
// / The words in braces, such as {fit} & {count}, are filled in by the page. Each translation keeps every one of them.
// / 'Select'
$Gui2Text109 = 'منتخب کریں';
// / 'Select All'
$Gui2Text110 = 'سب منتخب کریں';
// / 'Select None'
$Gui2Text111 = 'انتخاب ختم کریں';
// / 'Select By Type'
$Gui2Text112 = 'قسم کے لحاظ سے منتخب کریں';
// / 'Selected: '
$Gui2Text113 = 'منتخب شدہ: ';
// / 'Documents'
$Gui2Text114 = 'دستاویزات';
// / 'Spreadsheets'
$Gui2Text115 = 'اسپریڈشیٹس';
// / 'Presentations'
$Gui2Text116 = 'پریزنٹیشنز';
// / 'Images'
$Gui2Text117 = 'تصاویر';
// / 'Audio'
$Gui2Text118 = 'آڈیو';
// / 'Video'
$Gui2Text119 = 'ویڈیو';
// / 'Archives'
$Gui2Text120 = 'آرکائیوز';
// / 'E-Books'
$Gui2Text121 = 'ای بکس';
// / '3D Models'
$Gui2Text122 = '3D ماڈلز';
// / 'Drawings'
$Gui2Text123 = 'ڈرائنگز';
// / 'Vector Images'
$Gui2Text124 = 'ویکٹر تصاویر';
// / 'Subtitles'
$Gui2Text125 = 'سب ٹائٹلز';
// / 'OpenSCAD Files'
$Gui2Text126 = 'OpenSCAD فائلیں';
// / 'Streams'
$Gui2Text127 = 'اسٹریمز';
// / 'Workflows'
$Gui2Text128 = 'ورک فلوز';
// / 'Files'
$Gui2Text129 = 'فائلیں';
// / 'Select files in the list below.'
$Gui2Text130 = 'نیچے دی گئی فہرست میں سے فائلیں منتخب کریں۔';
// / 'Select By Name: '
$Gui2Text131 = 'نام کے لحاظ سے منتخب کریں: ';
// / 'Use * and ? as wildcards, or write a regular expression between slashes.'
$Gui2Text132 = '* اور ? کو وائلڈ کارڈ کے طور پر استعمال کریں، یا دو سلیشز کے درمیان ریگولر ایکسپریشن لکھیں۔';
// / 'Workflow'
$Gui2Text133 = 'ورک فلو';
// / 'Choose a workflow'
$Gui2Text134 = 'ایک ورک فلو منتخب کریں';
// / 'Run'
$Gui2Text135 = 'چلائیں';
// / 'Stop'
$Gui2Text136 = 'روکیں';
// / 'Selected files that can be used: {fit} of {count}.'
$Gui2Text137 = 'قابلِ استعمال منتخب فائلیں: {count} میں سے {fit}۔';
// / 'Step {step} of {steps}: {operation}'
$Gui2Text138 = 'مرحلہ {step} از {steps}: {operation}';
// / 'File {done} of {total}'
$Gui2Text139 = 'فائل {done} از {total}';
// / 'Waiting for the server.'
$Gui2Text140 = 'سرور کا انتظار ہو رہا ہے۔';
// / 'Starting.'
$Gui2Text141 = 'شروع ہو رہا ہے۔';
// / 'Finished.'
$Gui2Text142 = 'مکمل ہو گیا۔';
// / 'Stopping.'
$Gui2Text143 = 'روکا جا رہا ہے۔';
// / 'Stopped.'
$Gui2Text144 = 'روک دیا گیا۔';
// / 'Files made: '
$Gui2Text145 = 'بنائی گئی فائلیں: ';
// / 'Workflows are not available on this server.'
$Gui2Text146 = 'اس سرور پر ورک فلوز دستیاب نہیں ہیں۔';
// / 'No workflows are installed on this server.'
$Gui2Text147 = 'اس سرور پر کوئی ورک فلو انسٹال نہیں ہے۔';
// / 'That workflow is no longer available.'
$Gui2Text148 = 'وہ ورک فلو اب دستیاب نہیں ہے۔';
// / 'None of the selected files can be used by this workflow.'
$Gui2Text149 = 'منتخب فائلوں میں سے کوئی بھی اس ورک فلو کے کام نہیں آ سکتی۔';
// / 'Too many files are selected for one workflow.'
$Gui2Text150 = 'ایک ورک فلو کے لیے بہت زیادہ فائلیں منتخب کی گئی ہیں۔';
// / 'The server is busy. Try again in a few minutes.'
$Gui2Text151 = 'سرور مصروف ہے۔ چند منٹ بعد دوبارہ کوشش کریں۔';
// / 'A workflow is already running in this session.'
$Gui2Text152 = 'اس سیشن میں ایک ورک فلو پہلے سے چل رہا ہے۔';
// / 'The workflow could not be started.'
$Gui2Text153 = 'ورک فلو شروع نہیں کیا جا سکا۔';
// / 'A step of the workflow failed.'
$Gui2Text154 = 'ورک فلو کا ایک مرحلہ ناکام ہو گیا۔';
// / 'A virus was found, so the workflow stopped.'
$Gui2Text155 = 'ایک وائرس ملا، اس لیے ورک فلو روک دیا گیا۔';
// / 'The workflow ran out of time.'
$Gui2Text156 = 'ورک فلو کا وقت ختم ہو گیا۔';
// / 'The workflow made too many files.'
$Gui2Text157 = 'ورک فلو نے بہت زیادہ فائلیں بنا دیں۔';
// / 'The server was too busy to finish the workflow.'
$Gui2Text158 = 'سرور ورک فلو مکمل کرنے کے لیے بہت مصروف تھا۔';
// / 'An operation this workflow needs is not available.'
$Gui2Text159 = 'اس ورک فلو کے لیے درکار ایک عمل دستیاب نہیں ہے۔';
// / 'The workflow stopped unexpectedly.'
$Gui2Text160 = 'ورک فلو غیر متوقع طور پر رک گیا۔';
// / The words in braces, such as {step} & {kind}, are filled in by the page. Each translation keeps every one of them.
// / 'Pipelines'
$Gui2Text161 = 'پائپ لائنز';
// / 'Select a pipeline above...'
$Gui2Text162 = 'اوپر سے ایک پائپ لائن منتخب کریں...';
// / 'Select Pipeline...'
$Gui2Text163 = 'پائپ لائن منتخب کریں...';
// / 'Select Files...'
$Gui2Text164 = 'فائلیں منتخب کریں...';
// / 'Add a step'
$Gui2Text165 = 'ایک مرحلہ شامل کریں';
// / 'Options'
$Gui2Text166 = 'اختیارات';
// / 'Back'
$Gui2Text167 = 'واپس';
// / 'Take: '
$Gui2Text168 = 'لیں: ';
// / 'the ticked files'
$Gui2Text169 = 'نشان زدہ فائلیں';
// / 'the files step {step} made'
$Gui2Text170 = 'مرحلہ {step} کی بنائی ہوئی فائلیں';
// / 'each file on its own'
$Gui2Text171 = 'ہر فائل الگ سے';
// / 'all files together'
$Gui2Text172 = 'تمام فائلیں ایک ساتھ';
// / 'Only these kinds: '
$Gui2Text173 = 'صرف یہ اقسام: ';
// / 'Only these extensions: '
$Gui2Text174 = 'صرف یہ ایکسٹینشنز: ';
// / 'Smallest size: '
$Gui2Text175 = 'کم سے کم سائز: ';
// / 'Largest size: '
$Gui2Text176 = 'زیادہ سے زیادہ سائز: ';
// / 'In the order they arrive'
$Gui2Text177 = 'آنے کی ترتیب میں';
// / 'all files'
$Gui2Text178 = 'تمام فائلیں';
// / 'all {kind}'
$Gui2Text179 = 'تمام {kind}';
// / 'Width: '
$Gui2Text180 = 'چوڑائی: ';
// / 'Height: '
$Gui2Text181 = 'اونچائی: ';
// / 'My workflow'
$Gui2Text182 = 'میرا ورک فلو';
// / 'New'
$Gui2Text183 = 'نیا';
// / 'Import'
$Gui2Text184 = 'درآمد کریں';
// / 'Export'
$Gui2Text185 = 'برآمد کریں';
// / 'This workflow is not one this server can run.'
$Gui2Text186 = 'یہ سرور اس ورک فلو کو نہیں چلا سکتا۔';
// / 'The file could not be read.'
$Gui2Text187 = 'فائل پڑھی نہیں جا سکی۔';
// / 'The workflow builder is not available on this server.'
$Gui2Text188 = 'اس سرور پر ورک فلو بلڈر دستیاب نہیں ہے۔';
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
