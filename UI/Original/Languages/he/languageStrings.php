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
// / Unrar, ClamAV, MeshLab, PopplerUtils, PDFTOTEXT, ImageMagick, bwrap Dia & xvfb-run.
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
$GUIDirection = 'rtl';
// / Set the side of the page to float elements to.
$GUIAlignment = 'right';
// / Define an error message to display for if the core has not been loaded.
$CoreError = 'שגיאה!!! HRConvert2-2, קובץ זה אינו יכול לעבד את הבקשה שלך! אנא שלח את הקובץ שלך אל convertCore.php במקום זאת!';
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die($CoreError);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Check for required core variables.
if (!isset($Font)) $Font = 'Arial';
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = 'המר הכל!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI Related Logic.
if (!is_numeric($FileCount)) $FileCount = 'מספר לא ידוע של';
$FCPlural1 = 'ים תקינים';
$FCPlural2 = 'ים שלך מוכנים כעת';
if ($FileCount == 1) {
  $FCPlural1 = ' תקין';
  $FCPlural2 = ' שלך מוכן כעת'; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Header Related Variables.
// / 'Click, Tap, or Drop files here to upload.'
$GuiHeaderText1 = 'לחץ, הקש, או גרור קבצים לכאן כדי להעלות.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - UI Selector Bar Related Variables.
// / These strings appear in the selector bar, which is present on both GUI1 & GUI2.
// / 'Language'
$GuiSelectorText1 = 'שפה';
// / 'Color'
$GuiSelectorText2 = 'צבע';
// / 'Interface'
$GuiSelectorText3 = 'ממשק';
// / 'Display language, color and interface options'
$GuiSelectorText4 = 'הצג אפשרויות שפה, צבע וממשק';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / Online File Converter, Extractor, Compressor'
$Gui1Text1 = 'ממיר, מחלץ ומדחס קבצים מקוון';
// / $ApplicationName.' is based off the open-source web-app <a href=\'https://github.com\'>HRConvert2</a> by <a href=\'https://github.com\'>Zelon88</a> that converts files without tracking users across the net or infringing on your intellectual property.'
$Gui1Text2 = $ApplicationName.' מבוסס על אפליקציית הרשת בקוד פתוח <a href=\'https://github.com\'>HRConvert2</a> מאת <a href=\'https://github.com\'>Zelon88</a> שממירה קבצים מבלי לעקוב אחר משתמשים ברשת או להפר את הקניין הרוחני שלך.';
// / 'More Info ...'
$Gui1Text3 = 'עוד מידע ...';
// / 'Less Info ...'
$Gui1Text4 = 'פחות מידע ...';
// / 'All user-supplied data is erased automatically, so you don\'t need to worry about forfeiting your personal information or property while using our services.'
$Gui1Text5 = 'כל הנתונים המסופקים על ידי המשתמש נמחקים אוטומטית, כך שאינך צריך לדאוג לגבי ויתור על המידע האישי או הרכוש שלך בזמן השימוש בשירותים שלנו.';
// / 'Currently '.$ApplicationName.' supports '.$SupportedFormatCount.' different file formats, including documents, spreadsheets, images, media, 3D models, CAD drawings, vector files, archives, disk images, & more.'
$Gui1Text6 = 'כעת '.$ApplicationName.' תומך ב-'.$SupportedFormatCount.' פורמטי קבצים שונים, כולל מסמכים, גיליונות אלקטרוניים, תמונות, מדיה, מודלים בתלת-ממד, שרטוטי CAD, קובצי וקטור, ארכיונים, אימג׳ים של דיסקים ועוד.';
// / 'View Supported Formats ...'
$Gui1Text7 = 'הצג פורמטים נתמכים ...';
// / 'Hide Supported Formats ...'
$Gui1Text8 = 'הסתר פורמטים נתמכים ...';
// / 'Supported Formats'
$Gui1Text9 = 'פורמטים נתמכים';
// / 'Audio Formats'
$Gui1Text10 = 'פורמטי אודיו';
// / 'Supports specific bitrate.'
$Gui1Text11 = 'תומך בקצב נתונים (Bitrate) ספציפי.';
// / 'Video Formats'
$Gui1Text12 = 'פורמטי וידאו';
// / 'Stream Formats'
$Gui1Text13 = 'פורמטי סטרימינג';
// / 'Document Formats'
$Gui1Text14 = 'פורמטי מסמכים';
// / 'Spreadsheet Formats'
$Gui1Text15 = 'פורמטי גיליונות אלקטרוניים';
// / 'Presentation Formats'
$Gui1Text16 = 'פורמטי מצגות';
// / 'Archive Formats'
$Gui1Text17 = 'פורמטי ארכיון';
// / 'Can convert between archive formats & disk image formats.'
$Gui1Text18 = 'יכול להמיר בין פורמטי ארכיון נבחרים לבין פורמטי אימג׳ של דיסקים.';
// / 'Image Formats'
$Gui1Text19 = 'פורמטי תמונות';
// / 'Can convert pictures of documents to document formats.'
$Gui1Text20 = 'יכול להמיר תמונות של מסמכים לפורמטים של מסמכים.';
// / 'Supports resize & rotate.'
$Gui1Text21 = 'תומך בשינוי גודל וסיבוב.';
// / '3D Model Formats'
$Gui1Text22 = 'פורמטי מודלים בתלת-ממד';
// / 'Drawing Formats'
$Gui1Text23 = 'פורמטי שרטוט';
// / 'Can convert drawing files to image formats.'
$Gui1Text24 = 'יכול להמיר פורמטי שרטוט לפורמטי תמונות.';
// / 'OCR Support'
$Gui1Text25 = 'תמיכת OCR';
// / 'OCR Operations support the following input formats...'
$Gui1Text26 = 'פעולות OCR תומכות בפורמטי הקלט הבאים...';
// / 'OCR Operations support the following output formats...'
$Gui1Text27 = 'פעולות OCR תומכות בפורמטי הפלט הבאים...';
// / 'Select files by clicking, tapping, or dropping them into the box below.'
$Gui1Text28 = 'בחר קבצים על ידי לחיצה, הקשה או גרירתם לתיבה למטה.';
// / 'Continue ...'
$Gui1Text29 = 'המשך ...';
// / 'Can convert stream formats to video formats.'
$Gui1Text30 = 'יכול להמיר פורמטי סטרימינג לפורמטי וידאו.';
// / 'Subtitle Formats'
$Gui1Text31 = 'פורמטי כתוביות';
// / 'OpenSCAD Formats'
$Gui1Text32 = 'פורמטי OpenSCAD';
// / 'Renders OpenSCAD source into 3D model formats.'
$Gui1Text33 = 'מרנדר מקור OpenSCAD לפורמטים של מודלים בתלת-ממד.';
// / 'File references inside uploaded sources are removed unless the server allows resolving them.'
$Gui1Text34 = 'הפניות לקבצים בתוך מקורות שהועלו מוסרות אלא אם כן השרת מאפשר לפתור אותן.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'אפשרויות המרת קבצים';
// / 'Bulk File Options'
$Gui2Text2 = 'אפשרויות קבצים מרובים';
// / 'Scan All Files For Viruses'
$Gui2Text3 = 'סרוק את כל הקבצים לאיתור וירוסים';
// / 'Compress & Download All Files'
$Gui2Text4 = 'דחוס והורד את כל הקבצים';
// / 'Download'
$Gui2Text5 = 'הורד';
// / 'Share'
$Gui2Text6 = 'שתף';
// / 'Close Share Options'
$Gui2Text7 = 'סגור אפשרויות שיתוף';
// / 'Virus Scan'
$Gui2Text8 = 'סריקת וירוסים';
// / 'Close Virus Scan Options'
$Gui2Text9 = 'סגור אפשרויות סריקת וירוסים';
// / 'Archive'
$Gui2Text10 = 'ארכיון';
// / 'Close Archive Options'
$Gui2Text11 = 'סגור אפשרויות ארכיון';
// / 'OCR'
$Gui2Text12 = 'OCR';
// / 'Close OCR Options'
$Gui2Text13 = 'סגור אפשרויות OCR';
// / 'Convert'
$Gui2Text14 = 'המר';
// / 'Close Convert Options'
$Gui2Text15 = 'סגור אפשרויות המרה';
// / 'Archive This File'
$Gui2Text16 = 'ארכב קובץ זה';
// / 'Specify Filename: '
$Gui2Text17 = 'ציין שם קובץ: ';
// / 'Format'
$Gui2Text18 = 'פורמט';
// / 'Compress & Download'
$Gui2Text19 = 'דחוס והורד';
// / 'Scan with ClamAV: '
$Gui2Text20 = 'סרוק באמצעות ClamAV: ';
// / 'Scan with ScanCore: '
$Gui2Text21 = 'סרוק באמצעות ScanCore: ';
// / 'Scan All'
$Gui2Text22 = 'סרוק הכל';
// / 'Share This File'
$Gui2Text23 = 'שתף קובץ זה';
// / 'Link Status: '
$Gui2Text24 = 'סטטוס קישור: ';
// / 'Not Generated'
$Gui2Text25 = 'לא נוצר';
// / 'Generated'
$Gui2Text26 = 'נוצר';
// / 'Clipboard Status: '
$Gui2Text27 = 'סטטוס לוח גזירים: ';
// / 'Copied'
$Gui2Text28 = 'הועתק';
// / 'File Link: '
$Gui2Text29 = 'קישור לקובץ: ';
// / 'You have uploaded '.$FileCount.' valid file'.$FCPlural1.' to '.$ApplicationName.'.'
$Gui2Text30 = 'העלית '.$FileCount.' קובץ'.$FCPlural1.' אל '.$ApplicationName.'.';
// / 'Your file'.$FCPlural2.' now ready to convert using the options below.'
$Gui2Text31 = 'הקובץ'.$FCPlural2.' להמרה באמצעות האפשרויות שלהלן.';
// / 'Generate Link & Copy to Clipboard'
$Gui2Text32 = 'צור קישור והעתק ללוח הגזירים';
// / 'Generate Link'
$Gui2Text33 = 'צור קישור';
// / 'Scan This File For Viruses'
$Gui2Text34 = 'סרוק קובץ זה לאיתור וירוסים';
// / 'Scan File With ScanCore'
$Gui2Text35 = 'סרוק קובץ באמצעות ScanCore';
// / 'Scan File With ClamAV'
$Gui2Text36 = 'סרוק קובץ באמצעות ClamAV';
// / 'Scan File With ScanCore & ClamAV'
$Gui2Text37 = 'סרוק קובץ באמצעות ScanCore ו-ClamAV';
// / 'Perform Optical Character Recognition On This File'
$Gui2Text38 = 'בצע זיהוי תווים אופטי (OCR) על קובץ זה';
// / 'Method'
$Gui2Text39 = 'שיטה';
// / 'Simple'
$Gui2Text40 = 'פשוטה';
// / 'Advanced'
$Gui2Text41 = 'מתקדמת';
// / 'Convert This Archive'
$Gui2Text42 = 'המר ארכיון זה';
// / 'Convert This Document'
$Gui2Text43 = 'המר מסמך זה';
// / 'Convert This Spreadsheet'
$Gui2Text44 = 'המר גיליון אלקטרוני זה';
// / 'Convert This Audio'
$Gui2Text45 = 'המר אודיו זה';
// / 'Convert This Video'
$Gui2Text46 = 'המר וידאו זה';
// / 'Convert This Stream'
$Gui2Text47 = 'המר סטרימינג זה';
// / Convert This 3D Model'
$Gui2Text48 = 'המר מודל תלת-ממד זה';
// / 'Convert This Technical Drawing Or Vector File'
$Gui2Text49 = 'המר שרטוט טכני או קובץ וקטור זה';
// / 'Convert This Image'
$Gui2Text50 = 'המר תמונה זו';
// / 'Archive File'
$Gui2Text51 = 'ארכב קובץ';
// / 'Convert Into Document'
$Gui2Text52 = 'המר למסמך';
// / 'Archive Files'
$Gui2Text53 = 'ארכב קבצים';
// / 'Convert Document'
$Gui2Text54 = 'המר מסמך';
// / 'Convert Spreadsheet'
$Gui2Text55 = 'המר גיליון אלקטרוני';
// / 'Convert Presentation'
$Gui2Text56 = 'המר מצגת';
// / 'Convert Audio'
$Gui2Text57 = 'המר אודיו';
// / 'Convert Video'
$Gui2Text58 = 'המר וידאו';
// / 'Convert Stream'
$Gui2Text59 = 'המר סטרימינג';
// / 'Convert Model'
$Gui2Text60 = 'המר מודל';
// / 'Convert Drawing'
$Gui2Text61 = 'המר שרטוט';
// / 'Convert Image'
$Gui2Text62 = 'המר תמונה';
// / 'Width & Height'
$Gui2Text64 = 'רוחב וגובה: ';
// / 'Rotate: '
$Gui2Text65 = 'סובב: ';
// / 'Bitrate: '
$Gui2Text66 = 'קצב נתונים: ';
// / 'Delete'
$Gui2Text67 = 'מחק';
// / 'Close Delete Options'
$Gui2Text68 = 'סגור אפשרויות מחיקה';
// / 'Delete This File'
$Gui2Text69 = 'מחק קובץ זה';
// / 'Confirm Delete'
$Gui2Text70 = 'אשר מחיקה';
// / 'Cannot convert this file! Try changing the name.'
$Gui2Text71 = 'לא ניתן להמיר קובץ זה! נסה לשנות את השם.';
// / 'Cannot perform a virus scan on this file!'
$Gui2Text72 = 'לא ניתן לבצע סריקת וירוסים בקובץ זה!';
// / 'File Link Copied to Clipboard!'
$Gui2Text73 = 'הקישור לקובץ הועתק ללוח הגזירים!';
// / 'Operation Failed!'
$Gui2Text74 = 'הפעולה נכשלה!';
// / 'Convert These Subtitles'
$Gui2Text75 = 'המר כתוביות אלו';
// / 'Convert Subtitles'
$Gui2Text76 = 'המר כתוביות';
// / 'Convert This Presentation'
$Gui2Text77 = 'המר מצגת זו';
// / 'Convert This XPS File'
$Gui2Text78 = 'המר קובץ XPS זה';
// / 'Render This OpenSCAD Model'
$Gui2Text79 = 'רנדר מודל OpenSCAD זה';
// / 'Render Model'
$Gui2Text80 = 'רנדר מודל';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - HRC2-Functions.js Related Variables.
// / These strings are used by the client side javascript library.
// / That file is static javascript & cannot read a PHP variable, so every string it needs
// / is passed to it as an argument by the PHP that calls it.
// / 'Your browser does not support copying to the clipboard!'
$GuiFunctionsText1 = 'הדפדפן שלך אינו תומך בהעתקה ללוח הגזירים!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy'
$GuiFooterText1 = 'עיין ב<a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>תנאי השירות</a> וב<a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>מדיניות הפרטיות</a> שלנו';
// / -----------------------------------------------------------------------------------
