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
$GUIDirection = 'rtl';
// / Set the side of the page to float elements to.
$GUIAlignment = 'right';
// / Define an error message to display for if the core has not been loaded.
$CoreError = 'خطأ!!! HRConvert2-2 ، لا يمكن لهذا الملف معالجة طلبك! يرجى إرسال ملفك إلى convertCore.php بدلاً من ذلك!';
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die($CoreError);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Check for required core variables.
if (!isset($Font)) $Font = 'Arial';
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = 'تحويل أي شيء!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI Related Logic.
$FCPlural1 = '';
if (!is_numeric($FileCount)) $FileCount = 'عدد غير معروف من';
if ($FileCount === 0) $FCPlural1 = 'لقد قمت بتحميل 0 ملفات صالحة إلى '.$ApplicationName.'.';
if ($FileCount === 1) $FCPlural1 = 'لقد قمت بتحميل ملف واحد صالح إلى '.$ApplicationName.'.'; 
if ($FileCount === 2) $FCPlural1 = 'لقد قمت بتحميل ملفين صالحين إلى '.$ApplicationName.'.';
if ($FileCount >= 3) $FCPlural1 = 'لقد قمت بتحميل '.$FileCount.' ملفات صالحة إلى '.$ApplicationName.'.';
if ($FileCount >= 11) $FCPlural1 = 'لقد قمت بتحميل '.$FileCount.' ملفًا صالحًا إلى '.$ApplicationName.'.';
if ($FileCount === 100) $FCPlural1 = 'لقد قمت بتحميل '.$FileCount.'  ملف صالح إلى '.$ApplicationName.'.';
if ($FileCount >= 101) $FCPlural1 = 'لقد قمت بتحميل '.$FileCount.' ملفًا صالحًا إلى '.$ApplicationName.'.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Header Related Variables.
// / 'Click, Tap, or Drop files here to upload.'
$GuiHeaderText1 = 'انقر أو اضغط أو أفلت الملفات هنا للتحميل.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / Online File Converter, Extractor, Compressor'
$Gui1Text1 = 'محول الملفات عبر الإنترنت ، النازع ، الضاغط';
// / $ApplicationName.' is based off the open-source web-app <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> by <a href=\'https://github.com/zelon88\'>Zelon88</a> that converts files without tracking users across the net or infringing on your intellectual property.'
$Gui1Text2 = 'يعتمد '.$ApplicationName.' على تطبيق الويب المفتوح المصدر HRConvert2 بواسطة Zelon88 الذي يحول الملفات دون تتبع المستخدمين عبر الشبكة أو انتهاك حقوق الملكية الفكرية الخاصة بك.';
// / 'More Info ...'
$Gui1Text3 = 'مزيد من المعلومات ...';
// / 'Less Info ...'
$Gui1Text4 = 'معلومات أقل ...';
// / 'All user-supplied data is erased automatically, so you don\'t need to worry about forfeiting your personal information or property while using our services.'
$Gui1Text5 = 'يتم مسح جميع البيانات التي يوفرها المستخدم تلقائيًا ، لذلك لا داعي للقلق بشأن مصادرة معلوماتك الشخصية أو ممتلكاتك أثناء استخدام خدماتنا.';
// / 'Currently '.$ApplicationName.' supports '.$SupportedFormatCount.' different file formats, including documents, spreadsheets, images, media, 3D models, CAD drawings, vector files, archives, disk images, & more.'
$Gui1Text6 = 'يدعم '.$ApplicationName.' حاليًا '.$SupportedFormatCount.' تنسيقًا مختلفًا للملفات ، بما في ذلك المستندات وجداول البيانات والصور والوسائط والنماذج ثلاثية الأبعاد ورسومات CAD والملفات المتجهة والمحفوظات وصور القرص والمزيد.';
// / 'View Supported Formats ...'
$Gui1Text7 = 'عرض التنسيقات المدعومة ...';
// / 'Hide Supported Formats ...'
$Gui1Text8 = 'إخفاء التنسيقات المدعومة ...';
// / 'Supported Formats'
$Gui1Text9 = 'التنسيقات المدعومة';
// / 'Audio Formats'
$Gui1Text10 = 'تنسيقات الصوت';
// / 'Supports specific bitrate.'
$Gui1Text11 = 'يدعم معدل بت معين.';
// / 'Video Formats'
$Gui1Text12 = 'تنسيقات الفيديو';
// / 'Stream Formats'
$Gui1Text13 = 'تنسيقات الدفق';
// / 'Document Formats'
$Gui1Text14 = 'تنسيقات المستندات';
// / 'Spreadsheet Formats'
$Gui1Text15 = 'تنسيقات جداول البيانات';
// / 'Presentation Formats'
$Gui1Text16 = 'تنسيقات العرض';
// / 'Archive Formats'
$Gui1Text17 = 'Archive Formats';
// / 'Can convert between archive formats & disk image formats.'
$Gui1Text18 = 'يمكن التحويل بين تنسيقات أرشيف مختارة وتنسيقات صور القرص.';
// / 'Image Formats'
$Gui1Text19 = 'تنسيقات الصور';
// / 'Can convert pictures of documents to document formats.'
$Gui1Text20 = 'يمكن تحويل صور المستندات إلى تنسيقات المستندات.';
// / 'Supports resize & rotate.'
$Gui1Text21 = 'يدعم تغيير الحجم والتدوير.';
// / '3D Model Formats'
$Gui1Text22 = 'تنسيقات النماذج ثلاثية الأبعاد';
// / 'Drawing Formats'
$Gui1Text23 = 'تنسيقات الرسم';
// / 'Can convert drawing files to image formats.'
$Gui1Text24 = 'يمكن تحويل صيغ الرسم إلى صيغ الصور.';
// / 'OCR Support'
$Gui1Text25 = 'دعم التعرف البصري على الأحرف';
// / 'OCR Operations support the following input formats...'
$Gui1Text26 = 'تدعم عمليات التعرف الضوئي على الحروف تنسيقات الإدخال التالية ...';
// / 'OCR Operations support the following output formats...'
$Gui1Text27 = 'تدعم عمليات التعرف الضوئي على الأحرف تنسيقات الإخراج التالية ...';
// / 'Select files by clicking, tapping, or dropping them into the box below.'
$Gui1Text28 = 'حدد الملفات بالنقر فوقها أو الضغط عليها أو إسقاطها في المربع أدناه.';
// / 'Continue ...'
$Gui1Text29 = 'يكمل ...';
// / 'Can convert stream formats to video formats.'
$Gui1Text30 = 'يمكن تحويل صيغ الدفق إلى صيغ الفيديو.';
// / 'Subtitle Formats'
$Gui1Text31 = 'تنسيقات الترجمة';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'خيارات تحويل الملف';
// / 'Bulk File Options'
$Gui2Text2 = 'خيارات الملفات المجمعة';
// / 'Scan All Files For Viruses'
$Gui2Text3 = 'فحص جميع الملفات بحثًا عن الفيروسات';
// / 'Compress & Download All Files'
$Gui2Text4 = 'ضغط وتنزيل كافة الملفات';
// / 'Download'
$Gui2Text5 = 'تحميل';
// / 'Share'
$Gui2Text6 = 'يشارك';
// / 'Close Share Options'
$Gui2Text7 = 'إغلاق خيارات المشاركة';
// / 'Virus Scan'
$Gui2Text8 = 'فحص الفيروسات';
// / 'Close Virus Scan Options'
$Gui2Text9 = 'أغلق خيارات فحص الفيروسات';
// / 'Archive'
$Gui2Text10 = 'أرشيف';
// / 'Close Archive Options'
$Gui2Text11 = 'إغلاق خيارات الأرشيف';
// / 'OCR'
$Gui2Text12 = 'التعرف الضوئي على الحروف';
// / 'Close OCR Options'
$Gui2Text13 = 'أغلق خيارات OCR';
// / 'Convert'
$Gui2Text14 = 'يتحول';
// / 'Close Convert Options'
$Gui2Text15 = 'أغلق خيارات التحويل';
// / 'Archive This File'
$Gui2Text16 = 'أرشفة هذا الملف';
// / 'Specify Filename: '
$Gui2Text17 = 'حدد اسم الملف: ';
// / 'Format'
$Gui2Text18 = 'شكل';
// / 'Compress & Download'
$Gui2Text19 = 'ضغط وتنزيل';
// / 'Scan with ClamAV: '
$Gui2Text20 = 'المسح باستخدام كلاماف: ';
// / 'Scan with ScanCore: '
$Gui2Text21 = 'المسح باستخدام ScanCore: ';
// / 'Scan All'
$Gui2Text22 = 'مسح الكل';
// / 'Share This File'
$Gui2Text23 = 'شارك هذا الملف';
// / 'Link Status: '
$Gui2Text24 = 'حالة الارتباط: ';
// / 'Not Generated'
$Gui2Text25 = 'غير مولود';
// / 'Generated'
$Gui2Text26 = 'ولدت';
// / 'Clipboard Status: '
$Gui2Text27 = 'حالة الحافظة: ';
// / 'Copied'
$Gui2Text28 = 'نسخ';
// / 'File Link: '
$Gui2Text29 = 'رابط الملف: ';
// / 'You have uploaded '.$FileCount.' valid file'.$FCPlural1.' to '.$ApplicationName.'.'
$Gui2Text30 = $FCPlural1;
// / 'Your file'.$FCPlural2.' now ready to convert using the options below.'
$Gui2Text31 = 'ملفاتك جاهزة الآن للتحويل باستخدام الخيارات أدناه.';
// / 'Generate Link & Copy to Clipboard'
$Gui2Text32 = 'إنشاء ارتباط والنسخ إلى الحافظة';
// / 'Generate Link'
$Gui2Text33 = 'إنشاء ارتباط';
// / 'Scan This File For Viruses'
$Gui2Text34 = 'افحص هذا الملف بحثًا عن الفيروسات';
// / 'Scan File With ScanCore'
$Gui2Text35 = 'مسح الملف باستخدام ScanCore';
// / 'Scan File With ClamAV'
$Gui2Text36 = 'مسح الملف باستخدام ClamAV';
// / 'Scan File With ScanCore & ClamAV'
$Gui2Text37 = 'مسح الملف باستخدام ScanCore & ClamAV';
// / 'Perform Optical Character Recognition On This File'
$Gui2Text38 = 'قم بإجراء التعرف الضوئي على الأحرف في هذا الملف';
// / 'Method'
$Gui2Text39 = 'طريقة';
// / 'Simple'
$Gui2Text40 = 'بسيط';
// / 'Advanced'
$Gui2Text41 = 'متقدم';
// / 'Convert This Archive'
$Gui2Text42 = 'تحويل هذا الأرشيف';
// / 'Convert This Document'
$Gui2Text43 = 'تحويل هذا المستند';
// / 'Convert This Spreadsheet'
$Gui2Text44 = 'تحويل جدول البيانات هذا';
// / 'Convert This Audio'
$Gui2Text45 = 'تحويل هذا الصوت';
// / 'Convert This Video'
$Gui2Text46 = 'تحويل هذا الفيديو';
// / 'Convert This Stream'
$Gui2Text47 = 'تحويل هذا التدفق';
// / Convert This 3D Model'
$Gui2Text48 = 'تحويل هذا النموذج ثلاثي الأبعاد';
// / 'Convert This Technical Drawing Or Vector File'
$Gui2Text49 = 'قم بتحويل هذا الرسم الفني أو ملف المتجه';
// / 'Convert This Image'
$Gui2Text50 = 'تحويل هذه الصورة';
// / 'Archive File'
$Gui2Text51 = 'ملف الأرشيف';
// / 'Convert Into Document'
$Gui2Text52 = 'تحويل إلى مستند';
// / 'Archive Files'
$Gui2Text53 = 'أرشفة الملفات';
// / 'Convert Document'
$Gui2Text54 = 'تحويل المستند';
// / 'Convert Spreadsheet'
$Gui2Text55 = 'تحويل جدول البيانات';
// / 'Convert Presentation'
$Gui2Text56 = 'تحويل العرض التقديمي';
// / 'Convert Audio'
$Gui2Text57 = 'تحويل الصوت';
// / 'Convert Video'
$Gui2Text58 = 'تحويل الفيديو';
// / 'Convert Stream'
$Gui2Text59 = 'تحويل تيار';
// / 'Convert Model'
$Gui2Text60 = 'تحويل النموذج';
// / 'Convert Drawing'
$Gui2Text61 = 'تحويل الرسم';
// / 'Convert Image'
$Gui2Text62 = 'تحويل الصورة';
// / 'Width & Height'
$Gui2Text64 = 'عرض ارتفاع:';
// / 'Rotate: '
$Gui2Text65 = 'استدارة: ';
// / 'Bitrate: '
$Gui2Text66 = 'معدل البت: ';
// / 'Delete'
$Gui2Text67 = 'يمسح';
// / 'Close Delete Options'
$Gui2Text68 = 'أغلق خيارات الحذف';
// / 'Delete This File'
$Gui2Text69 = 'حذف هذا الملف';
// / 'Confirm Delete'
$Gui2Text70 = 'تأكيد الحذف';
// / 'Cannot convert this file! Try changing the name.'
$Gui2Text71 = 'لا يمكن تحويل هذا الملف! حاول تغيير الاسم.';
// / 'Cannot perform a virus scan on this file!'
$Gui2Text72 = 'لا يمكن إجراء فحص فيروسات على هذا الملف!';
// / 'File Link Copied to Clipboard!'
$Gui2Text73 = 'تم نسخ ارتباط الملف إلى الحافظة!';
// / 'Operation Failed!'
$Gui2Text74 = 'فشلت العملية!';
// / Convert These Subtitles'
$Gui2Text75 = 'تحويل هذه الترجمات';
// / Convert Subtitles'
$Gui2Text76 = 'تحويل الترجمة';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy'
$GuiFooterText1 = 'تحقق من <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>وسياسة الخصوصية</a> و <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>شروط الخدمة</a> الخاصة بنا';
// / -----------------------------------------------------------------------------------