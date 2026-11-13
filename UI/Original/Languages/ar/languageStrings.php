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
$LanguageVersion = 'v4.1.6';
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
// / Set GUI - UI Selector Bar Related Variables.
// / These strings appear in the selector bar, which is present on both GUI1 & GUI2.
// / 'Language'
$GuiSelectorText1 = 'اللغة';
// / 'Color'
$GuiSelectorText2 = 'اللون';
// / 'Interface'
$GuiSelectorText3 = 'الواجهة';
// / 'Display language, color and interface options'
$GuiSelectorText4 = 'عرض خيارات اللغة واللون والواجهة';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / 'Online File Converter, Extractor, Compressor'
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
$Gui1Text17 = 'تنسيقات الأرشيف';
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
// / 'OpenSCAD Formats'
$Gui1Text32 = 'صيغ OpenSCAD';
// / 'Renders OpenSCAD source into 3D model formats.'
$Gui1Text33 = 'يحوّل شفرة OpenSCAD المصدرية إلى صيغ نماذج ثلاثية الأبعاد.';
// / 'File references inside uploaded sources are removed unless the server allows resolving them.'
$Gui1Text34 = 'تُزال مراجع الملفات الموجودة داخل الملفات المصدرية المرفوعة ما لم يسمح الخادم بمعالجتها.';
// / 'Delete every uploaded file & start a new session?'
// / Shown inside the start over panel on the upload page. That panel is only rendered
// / when the session already holds at least one file, so this is never shown to a first
// / time visitor who has nothing to lose.
$Gui1Text35 = 'هل تريد حذف جميع الملفات المرفوعة وبدء جلسة جديدة؟';
// / 'Start Over'
// / Labels the control that opens the panel & the button inside it that confirms.
$Gui1Text36 = 'ابدأ من جديد';
// / 'Refresh'
// / Alternate text for the refresh control on the upload page. That control is a glyph
// / with no text of its own, so this is the only description a screen reader has & the
// / only thing shown when a browser cannot render the glyph.
$Gui1Text37 = 'تحديث';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'خيارات تحويل الملف';
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
// / 'Convert This 3D Model'
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
// / 'Convert These Subtitles'
$Gui2Text75 = 'تحويل هذه الترجمات';
// / 'Convert Subtitles'
$Gui2Text76 = 'تحويل الترجمة';
// / 'Convert This Presentation'
$Gui2Text77 = 'تحويل هذا العرض التقديمي';
// / 'Convert This XPS File'
$Gui2Text78 = 'تحويل ملف هذا';
// / 'Render This OpenSCAD Model'
$Gui2Text79 = 'إنشاء نموذج OpenSCAD هذا';
// / 'Render Model'
$Gui2Text80 = 'إنشاء النموذج';
// / 'Convert This E-Book'
$Gui2Text81 = 'تحويل هذا الكتاب الإلكتروني';
// / 'Convert E-Book'
$Gui2Text82 = 'تحويل الكتاب الإلكتروني';
// / 'Playlist source URL, only needed if the playlist uses relative paths'
$Gui2Text83 = 'عنوان URL لمصدر قائمة التشغيل، مطلوب فقط إذا كانت تستخدم مسارات نسبية';
// / 'Download A File From A URL'
$Gui2Text84 = 'تنزيل ملف من عنوان URL';
// / 'Fetch'
$Gui2Text85 = 'جلب';
// / 'Identify'
$Gui2Text86 = 'تعرّف';
// / 'Close Identify Options'
$Gui2Text87 = 'أغلق خيارات التعرّف';
// / 'Identify This File'
$Gui2Text88 = 'تعرّف على هذا الملف';
// / 'Size: '
$Gui2Text89 = 'الحجم: ';
// / 'Modified: '
$Gui2Text90 = 'آخر تعديل: ';
// / 'Type: '
$Gui2Text91 = 'النوع: ';
// / 'MIME Type: '
$Gui2Text92 = 'نوع MIME: ';
// / 'Character Set: '
$Gui2Text93 = 'مجموعة المحارف: ';
// / 'Extension Matches Content: '
$Gui2Text94 = 'الامتداد يطابق المحتوى: ';
// / 'Details: '
$Gui2Text95 = 'التفاصيل: ';
// / 'Notes: '
$Gui2Text96 = 'ملاحظات: ';
// / 'Save report as file'
$Gui2Text97 = 'حفظ التقرير كملف';
// / 'Yes'
$Gui2Text100 = 'نعم';
// / 'No'
$Gui2Text101 = 'لا';
// / 'Unknown'
$Gui2Text102 = 'غير معروف';
// / 'Cannot identify this file!'
$Gui2Text103 = 'لا يمكن التعرّف على هذا الملف!';
// / 'Sort By: '
$Gui2Text104 = 'فرز حسب: ';
// / 'Name'
$Gui2Text105 = 'الاسم';
// / 'Size'
$Gui2Text106 = 'الحجم';
// / 'Date'
$Gui2Text107 = 'التاريخ';
// / 'Type'
$Gui2Text108 = 'النوع';
// / The words in braces, such as {fit} & {count}, are filled in by the page. Each translation keeps every one of them.
// / 'Select'
$Gui2Text109 = 'تحديد';
// / 'Select All'
$Gui2Text110 = 'تحديد الكل';
// / 'Select None'
$Gui2Text111 = 'إلغاء تحديد الكل';
// / 'Select By Type'
$Gui2Text112 = 'التحديد حسب النوع';
// / 'Selected: '
$Gui2Text113 = 'المحدد: ';
// / 'Documents'
$Gui2Text114 = 'المستندات';
// / 'Spreadsheets'
$Gui2Text115 = 'جداول البيانات';
// / 'Presentations'
$Gui2Text116 = 'العروض التقديمية';
// / 'Images'
$Gui2Text117 = 'الصور';
// / 'Audio'
$Gui2Text118 = 'الصوت';
// / 'Video'
$Gui2Text119 = 'الفيديو';
// / 'Archives'
$Gui2Text120 = 'الأرشيفات';
// / 'E-Books'
$Gui2Text121 = 'الكتب الإلكترونية';
// / '3D Models'
$Gui2Text122 = 'النماذج ثلاثية الأبعاد';
// / 'Drawings'
$Gui2Text123 = 'الرسومات';
// / 'Vector Images'
$Gui2Text124 = 'الصور المتجهة';
// / 'Subtitles'
$Gui2Text125 = 'الترجمات النصية';
// / 'OpenSCAD Files'
$Gui2Text126 = 'ملفات OpenSCAD';
// / 'Streams'
$Gui2Text127 = 'البث';
// / 'Workflows'
$Gui2Text128 = 'مسارات العمل';
// / 'Files'
$Gui2Text129 = 'الملفات';
// / 'Select files in the list below.'
$Gui2Text130 = 'حدد الملفات من القائمة أدناه.';
// / 'Select By Name: '
$Gui2Text131 = 'التحديد حسب الاسم: ';
// / 'Use * and ? as wildcards, or write a regular expression between slashes.'
$Gui2Text132 = 'استخدم * و ? كأحرف بدل، أو اكتب تعبيرًا نمطيًا بين شرطتين مائلتين.';
// / 'Workflow'
$Gui2Text133 = 'سير العمل';
// / 'Choose a workflow'
$Gui2Text134 = 'اختر سير عمل';
// / 'Run'
$Gui2Text135 = 'تشغيل';
// / 'Stop'
$Gui2Text136 = 'إيقاف';
// / 'Selected files that can be used: {fit} of {count}.'
$Gui2Text137 = 'الملفات المحددة القابلة للاستخدام: {fit} من {count}.';
// / 'Step {step} of {steps}: {operation}'
$Gui2Text138 = 'الخطوة {step} من {steps}: {operation}';
// / 'File {done} of {total}'
$Gui2Text139 = 'الملف {done} من {total}';
// / 'Waiting for the server.'
$Gui2Text140 = 'في انتظار الخادم.';
// / 'Starting.'
$Gui2Text141 = 'جارٍ البدء.';
// / 'Finished.'
$Gui2Text142 = 'اكتمل.';
// / 'Stopping.'
$Gui2Text143 = 'جارٍ الإيقاف.';
// / 'Stopped.'
$Gui2Text144 = 'توقف.';
// / 'Files made: '
$Gui2Text145 = 'الملفات المُنشأة: ';
// / 'Workflows are not available on this server.'
$Gui2Text146 = 'مسارات العمل غير متاحة على هذا الخادم.';
// / 'No workflows are installed on this server.'
$Gui2Text147 = 'لا توجد مسارات عمل مثبتة على هذا الخادم.';
// / 'That workflow is no longer available.'
$Gui2Text148 = 'سير العمل هذا لم يعد متاحًا.';
// / 'None of the selected files can be used by this workflow.'
$Gui2Text149 = 'لا يمكن لسير العمل هذا استخدام أي من الملفات المحددة.';
// / 'Too many files are selected for one workflow.'
$Gui2Text150 = 'تم تحديد ملفات كثيرة جدًا لسير عمل واحد.';
// / 'The server is busy. Try again in a few minutes.'
$Gui2Text151 = 'الخادم مشغول. حاول مرة أخرى بعد بضع دقائق.';
// / 'A workflow is already running in this session.'
$Gui2Text152 = 'يوجد سير عمل قيد التشغيل بالفعل في هذه الجلسة.';
// / 'The workflow could not be started.'
$Gui2Text153 = 'تعذر بدء سير العمل.';
// / 'A step of the workflow failed.'
$Gui2Text154 = 'فشلت إحدى خطوات سير العمل.';
// / 'A virus was found, so the workflow stopped.'
$Gui2Text155 = 'تم العثور على فيروس، لذا توقف سير العمل.';
// / 'The workflow ran out of time.'
$Gui2Text156 = 'نفد الوقت المتاح لسير العمل.';
// / 'The workflow made too many files.'
$Gui2Text157 = 'أنشأ سير العمل ملفات كثيرة جدًا.';
// / 'The server was too busy to finish the workflow.'
$Gui2Text158 = 'كان الخادم مشغولًا جدًا لإكمال سير العمل.';
// / 'An operation this workflow needs is not available.'
$Gui2Text159 = 'إحدى العمليات التي يحتاجها سير العمل هذا غير متاحة.';
// / 'The workflow stopped unexpectedly.'
$Gui2Text160 = 'توقف سير العمل بشكل غير متوقع.';
// / The words in braces, such as {step} & {kind}, are filled in by the page. Each translation keeps every one of them.
// / 'Pipelines'
$Gui2Text161 = 'خطوط المعالجة';
// / 'Select a pipeline above...'
$Gui2Text162 = 'اختر خط معالجة من الأعلى...';
// / 'Select Pipeline...'
$Gui2Text163 = 'اختيار خط معالجة...';
// / 'Select Files...'
$Gui2Text164 = 'اختيار الملفات...';
// / 'Add a step'
$Gui2Text165 = 'إضافة خطوة';
// / 'Options'
$Gui2Text166 = 'الخيارات';
// / 'Back'
$Gui2Text167 = 'رجوع';
// / 'Take: '
$Gui2Text168 = 'المدخلات: ';
// / 'the ticked files'
$Gui2Text169 = 'الملفات المؤشر عليها';
// / 'the files step {step} made'
$Gui2Text170 = 'الملفات التي أنشأتها الخطوة {step}';
// / 'each file on its own'
$Gui2Text171 = 'كل ملف على حدة';
// / 'all files together'
$Gui2Text172 = 'كل الملفات معًا';
// / 'Only these kinds: '
$Gui2Text173 = 'هذه الأنواع فقط: ';
// / 'Only these extensions: '
$Gui2Text174 = 'هذه الامتدادات فقط: ';
// / 'Smallest size: '
$Gui2Text175 = 'أصغر حجم: ';
// / 'Largest size: '
$Gui2Text176 = 'أكبر حجم: ';
// / 'In the order they arrive'
$Gui2Text177 = 'بترتيب الوصول';
// / 'all files'
$Gui2Text178 = 'كل الملفات';
// / 'all {kind}'
$Gui2Text179 = 'كل الملفات: {kind}';
// / 'Width: '
$Gui2Text180 = 'العرض: ';
// / 'Height: '
$Gui2Text181 = 'الارتفاع: ';
// / 'My workflow'
$Gui2Text182 = 'سير عملي';
// / 'New'
$Gui2Text183 = 'جديد';
// / 'Import'
$Gui2Text184 = 'استيراد';
// / 'Export'
$Gui2Text185 = 'تصدير';
// / 'This workflow is not one this server can run.'
$Gui2Text186 = 'لا يستطيع هذا الخادم تشغيل سير العمل هذا.';
// / 'The file could not be read.'
$Gui2Text187 = 'تعذرت قراءة الملف.';
// / 'The workflow builder is not available on this server.'
$Gui2Text188 = 'منشئ سير العمل غير متاح على هذا الخادم.';
// / 'Status'
$Gui2Text189 = 'الحالة';
// / 'A workflow is running.'
$Gui2Text190 = 'يوجد سير عمل قيد التشغيل.';
// / 'No workflow is running.'
$Gui2Text191 = 'لا يوجد سير عمل قيد التشغيل.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - HRC2-Functions.js Related Variables.
// / These strings are used by the client side javascript library.
// / That file is static javascript & cannot read a PHP variable, so every string it needs
// / is passed to it as an argument by the PHP that calls it.
// / 'Your browser does not support copying to the clipboard!'
$GuiFunctionsText1 = 'متصفحك لا يدعم النسخ إلى الحافظة.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy'
$GuiFooterText1 = 'تحقق من <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>وسياسة الخصوصية</a> و <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>شروط الخدمة</a> الخاصة بنا';
// / -----------------------------------------------------------------------------------