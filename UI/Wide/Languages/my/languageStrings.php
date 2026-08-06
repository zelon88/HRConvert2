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
// / This application requires Debian Linux, Apache 2.4, PHP 8+, FFMPEG, Dia, bwrap,
// / Mkisofs, 7zip, LibreOffice, Unoconv, libgxps-utils, Tesseract, Unzip, OpenSCAD,
// / Unrar, Rar, ClamAV, MeshLab, PopplerUtils, PDFTOTEXT, ImageMagick & xvfb-run.
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
$CoreError = 'အမှား!!! HRConvert2-2, ဤဖိုင်သည် သင်တောင်းဆိုချက်ကို မလုပ်ဆောင်နိုင်ပါ။ သင်၏ဖိုင်ကို convertCore.php သို့ အစားထိုးပေးပို့ပါ!';
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die($CoreError);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Check for required core variables.
if (!isset($Font)) $Font = 'Arial';
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = 'မည်သည့်အရာကိုမဆို ပြောင်းလဲပါ!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI Related Logic.
if (!is_numeric($FileCount)) $FileCount = 'အရေအတွက်မသိရသော';
$FCPlural1 = ' ခု';
$FCPlural2 = 'များ';
if ($FileCount == 1) {
  $FCPlural1 = ' ခု';
  $FCPlural2 = ''; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set a flag to tell that the UI has been displayed.
$LanguageStringsLoaded = TRUE;
// / Set the reading direction for text on the page.
$GUIDirection = 'ltr';
// / Set the side of the page to float elements to.
$GUIAlignment = 'left';
// / Define an error message to display for if the core has not been loaded.
$CoreError = 'အမှား!!! HRConvert2-2, ဤဖိုင်သည် သင်တောင်းဆိုချက်ကို မလုပ်ဆောင်နိုင်ပါ။ သင်၏ဖိုင်ကို convertCore.php သို့ အစားထိုးပေးပို့ပါ!';
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die($CoreError);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Check for required core variables.
if (!isset($Font)) $Font = 'Arial';
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = 'မည်သည့်အရာကိုမဆို ပြောင်းလဲပါ!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI Related Logic.
if (!is_numeric($FileCount)) $FileCount = 'အရေအတွက်မသိရသော';
$FCPlural1 = ' ခု';
$FCPlural2 = 'များ';
if ($FileCount == 1) {
  $FCPlural1 = ' ခု';
  $FCPlural2 = ''; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Header Related Variables.
// / 'Click, Tap, or Drop files here to upload.'
$GuiHeaderText1 = 'တင်ယူရန် ဖိုင်များကို ဤနေရာတွင် နှိပ်ပါ၊ ထိပါ သို့မဟုတ် ဆွဲချပါ။';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / Online File Converter, Extractor, Compressor'
$Gui1Text1 = 'အွန်လိုင်း ဖိုင်ကွန်ဗာတာ၊ ဖိုင်ထုတ်ယူကိရိယာ၊ ဖိုင်ချုံ့ကိရိယာ';
// / $ApplicationName.' is based off the open-source web-app <a href=\'https://github.com\'>HRConvert2</a> by <a href=\'https://github.com\'>Zelon88</a> that converts files without tracking users across the net or infringing on your intellectual property.'
$Gui1Text2 = $ApplicationName.' သည် သုံးစွဲသူများကို အင်တာနက်ပေါ်တွင် ခြေရာခံခြင်းမပြုဘဲ သို့မဟုတ် သင်၏ဉာဏပစ္စည်းမူပိုင်ခွင့်ကို ချိုးဖောက်ခြင်းမရှိဘဲ ဖိုင်များကို ပြောင်းလဲပေးသည့် <a href=\'https://github.com\'>Zelon88</a> ၏ အလွတ်သုံး ဆော့ဖ်ဝဲ ဝဘ်အက်ပ် <a href=\'https://github.com\'>HRConvert2</a> အပေါ် အခြေခံထားခြင်း ဖြစ်သည်။';
// / 'More Info ...'
$Gui1Text3 = 'အချက်အလက် ထပ်မံကြည့်ရှုရန် ...';
// / 'Less Info ...'
$Gui1Text4 = 'အချက်အလက် လျှော့ချကြည့်ရှုရန် ...';
// / 'All user-supplied data is erased automatically, so you don\'t need to worry about forfeiting your personal information or property while using our services.'
$Gui1Text5 = 'သုံးစွဲသူပေးပို့သော အချက်အလက်အားလုံးကို အလိုအလျောက် ပယ်ဖျက်ပေးသောကြောင့် ကျွန်ုပ်တို့၏ ဝန်ဆောင်မှုများကို အသုံးပြုစဉ် သင်၏ ကိုယ်ရေးအချက်အလက် သို့မဟုတ် ပိုင်ဆိုင်မှုများ ဆုံးရှုံးရမည်ကို စိုးရိမ်ရန် မလိုပါ။';
// / 'Currently '.$ApplicationName.' supports '.$SupportedFormatCount.' different file formats, including documents, spreadsheets, images, media, 3D models, CAD drawings, vector files, archives, disk images, & more.'
$Gui1Text6 = 'လက်ရှိတွင် '.$ApplicationName.' သည် စာရွက်စာတမ်းများ၊ စပရက်ရှီတ်များ၊ ပုံရိပ်များ၊ မီဒီယာများ၊ 3D မော်ဒယ်များ၊ CAD ရေးဆွဲမှုများ၊ ဗက်တာဖိုင်များ၊ မော်ကွန်းဖိုင်များ၊ ดစ်ခ်ပုံရိပ်များ အပါအဝင် မတူညီသော ဖိုင်ဖော်မတ်ပေါင်း '.$SupportedFormatCount.' ခုကို ပံ့ပိုးပေးထားပါသည်။';
// / 'View Supported Formats ...'
$Gui1Text7 = 'ပံ့ပိုးထားသော ဖော်မတ်များကို ကြည့်ရှုရန် ...';
// / 'Hide Supported Formats ...'
$Gui1Text8 = 'ပံ့ပိုးထားသော ဖော်မတ်များကို ဝှက်ရန် ...';
// / 'Supported Formats'
$Gui1Text9 = 'ပံ့ပိုးထားသော ဖော်မတ်များ';
// / 'Audio Formats'
$Gui1Text10 = 'အော်ဒီယို ဖော်မတ်များ';
// / 'Supports specific bitrate.'
$Gui1Text11 = 'သတ်မှတ်ထားသော ဒေတာနှုန်းကို ပံ့ပိုးသည်။';
// / 'Video Formats'
$Gui1Text12 = 'ဗီဒီယို ဖော်မတ်များ';
// / 'Stream Formats'
$Gui1Text13 = 'တိုက်ရိုက်ထုတ်လွှင့်မှု ဖော်မတ်များ';
// / 'Document Formats'
$Gui1Text14 = 'စာရွက်စာတမ်း ဖော်မတ်များ';
// / 'Spreadsheet Formats'
$Gui1Text15 = 'စပရက်ရှီတ် ဖော်မတ်များ';
// / 'Presentation Formats'
$Gui1Text16 = 'ပရဆင်တေးရှင်း ဖော်မတ်များ';
// / 'Archive Formats'
$Gui1Text17 = 'မော်ကွန်းဖိုင် ဖော်မတ်များ';
// / 'Can convert between archive formats & disk image formats.'
$Gui1Text18 = 'ရွေးချယ်ထားသော မော်ကွန်းဖိုင် ဖော်မတ်များနှင့် ဒစ်ခ်ပုံရိပ် ဖော်မတ်များအကြား ပြောင်းလဲနိုင်သည်။';
// / 'Image Formats'
$Gui1Text19 = 'ရုပ်ပုံ ဖော်မတ်များ';
// / 'Can convert pictures of documents to document formats.'
$Gui1Text20 = 'စာရွက်စာတမ်း ရုပ်ပုံများကို စာရွက်စာတမ်း ဖော်မတ်များသို့ ပြောင်းလဲနိုင်သည်။';
// / 'Supports resize & rotate.'
$Gui1Text21 = 'အရွယ်အစား ပြောင်းလဲခြင်းနှင့် လှည့်ခြင်းတို့ကို ပံ့ပိုးသည်။';
// / '3D Model Formats'
$Gui1Text22 = '3D မော်ဒယ် ဖော်မတ်များ';
// / 'Drawing Formats'
$Gui1Text23 = 'ပုံဆွဲခြင်း ဖော်မတ်များ';
// / 'Can convert drawing files to image formats.'
$Gui1Text24 = 'ပုံဆွဲခြင်း ဖော်မတ်များကို ရုပ်ပုံ ဖော်မတ်များသို့ ပြောင်းလဲနိုင်သည်။';
// / 'OCR Support'
$Gui1Text25 = 'OCR ပံ့ပိုးမှု';
// / 'OCR Operations support the following input formats...'
$Gui1Text26 = 'OCR လုပ်ဆောင်ချက်များသည် အောက်ပါ ထည့်သွင်းမှု ဖော်မတ်များကို ပံ့ပိုးသည်...';
// / 'OCR Operations support the following output formats...'
$Gui1Text27 = 'OCR လုပ်ဆောင်ချက်များသည် အောက်ပါ ရလဒ်ထွက်ရှိမှု ဖော်မတ်များကို ပံ့ပိုးသည်...';
// / 'Select files by clicking, tapping, or dropping them into the box below.'
$Gui1Text28 = 'အောက်ပါ သေတ္တာထဲသို့ ဖိုင်များကို နှိပ်ခြင်း၊ ထိခြင်း သို့မဟုတ် ဆွဲချခြင်းဖြင့် ရွေးချယ်ပါ။';
// / 'Continue ...'
$Gui1Text29 = 'ရှေ့ဆက်ရန် ...';
// / 'Can convert stream formats to video formats.'
$Gui1Text30 = 'တိုက်ရိုက်ထုတ်လွှင့်မှု ဖော်မတ်များကို ဗီဒီယို ဖော်မတ်များသို့ ပြောင်းလဲနိုင်သည်။';
// / 'Subtitle Formats'
$Gui1Text31 = 'စာတန်းထိုး ဖော်မတ်များ';
// / 'OpenSCAD Formats'
$Gui1Text32 = 'OpenSCAD ဖော်မတ်များ';
// / 'Renders OpenSCAD source into 3D model formats.'
$Gui1Text33 = 'OpenSCAD ရင်းမြစ်ကို 3D မော်ဒယ် ဖော်မတ်များအဖြစ် ထုတ်လုပ်ပေးသည်။';
// / 'File references inside uploaded sources are removed unless the server allows resolving them.'
$Gui1Text34 = 'ဆာဗာမှ ခွင့်ပြုချက်မရှိလျှင် တင်ယူထားသော ရင်းမြစ်များအတွင်းရှိ ဖိုင်ညွှန်းဆိုချက်များကို ဖယ်ရှားမည် ဖြစ်သည်။';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'ဖိုင်ပြောင်းလဲခြင်း ရွေးချယ်စရာများ';
// / 'Bulk File Options'
$Gui2Text2 = 'ဖိုင်အများအပြားအတွက် ရွေးချယ်စရာများ';
// / 'Scan All Files For Viruses'
$Gui2Text3 = 'ဖိုင်အားလုံးကို ဗိုင်းရပ်စ် ရှိမရှိ စစ်ဆေးပါ';
// / 'Compress & Download All Files'
$Gui2Text4 = 'ဖိုင်အားလုံးကို ချုံ့ပြီး ဒေါင်းလုဒ်လုပ်ပါ';
// / 'Download'
$Gui2Text5 = 'ဒေါင်းလုဒ်လုပ်ရန်';
// / 'Share'
$Gui2Text6 = 'မျှဝေရန်';
// / 'Close Share Options'
$Gui2Text7 = 'မျှဝေခြင်း ရွေးချယ်စရာများကို ပိတ်ပါ';
// / 'Virus Scan'
$Gui2Text8 = 'ဗိုင်းရပ်စ် စစ်ဆေးခြင်း';
// / 'Close Virus Scan Options'
$Gui2Text9 = 'ဗိုင်းရပ်စ် စစ်ဆေးခြင်း ရွေးချယ်စရာများကို ပိတ်ပါ';
// / 'Archive'
$Gui2Text10 = 'မော်ကွန်းတင်ရန်';
// / 'Close Archive Options'
$Gui2Text11 = 'မော်ကွန်းတင်ခြင်း ရွေးချယ်စရာများကို ပိတ်ပါ';
// / 'OCR'
$Gui2Text12 = 'OCR';
// / 'Close OCR Options'
$Gui2Text13 = 'OCR ရွေးချယ်စရာများကို ပိတ်ပါ';
// / 'Convert'
$Gui2Text14 = 'ပြောင်းလဲရန်';
// / 'Close Convert Options'
$Gui2Text15 = 'ပြောင်းလဲခြင်း ရွေးချယ်စရာများကို ပိတ်ပါ';
// / 'Archive This File'
$Gui2Text16 = 'ဤဖိုင်ကို မော်ကွန်းတင်ပါ';
// / 'Specify Filename: '
$Gui2Text17 = 'ဖိုင်အမည် သတ်မှတ်ပါ: ';
// / 'Format'
$Gui2Text18 = 'ဖော်မတ်';
// / 'Compress & Download'
$Gui2Text19 = 'ချုံ့ပြီး ဒေါင်းလုဒ်လုပ်ပါ';
// / 'Scan with ClamAV: '
$Gui2Text20 = 'ClamAV ဖြင့် စစ်ဆေးပါ: ';
// / 'Scan with ScanCore: '
$Gui2Text21 = 'ScanCore ဖြင့် စစ်ဆေးပါ: ';
// / 'Scan All'
$Gui2Text22 = 'အားလုံးကို စစ်ဆေးပါ';
// / 'Share This File'
$Gui2Text23 = 'ဤဖိုင်ကို မျှဝေပါ';
// / 'Link Status: '
$Gui2Text24 = 'လင့်ခ် အခြေအနေ: ';
// / 'Not Generated'
$Gui2Text25 = 'မထုတ်လုပ်ရသေးပါ';
// / 'Generated'
$Gui2Text26 = 'ထုတ်လုပ်ပြီးပါပြီ';
// / 'Clipboard Status: '
$Gui2Text27 = 'ကလစ်ဘုတ် အခြေအနေ: ';
// / 'Copied'
$Gui2Text28 = 'ကူးယူပြီးပါပြီ';
// / 'File Link: '
$Gui2Text29 = 'ဖိုင်လင့်ခ်: ';
// / 'You have uploaded '.$FileCount.' valid file'.$FCPlural1.' to '.$ApplicationName.'.'
$Gui2Text30 = 'သင်သည် '.$ApplicationName.' သို့ မှန်ကန်သော ဖိုင်အရေအတွက် '.$FileCount.$FCPlural1.' ကို တင်ယူပြီးပါပြီ။';
// / 'Your file'.$FCPlural2.' now ready to convert using the options below.'
$Gui2Text31 = 'သင်၏ ဖိုင်'.$FCPlural2.'သည် အောက်ပါ ရွေးချယ်စရာများကို အသုံးပြု၍ ပြောင်းလဲရန် အဆင်သင့် ဖြစ်ပါပြီ။';
// / 'Generate Link & Copy to Clipboard'
$Gui2Text32 = 'လင့်ခ်ထုတ်လုပ်ပြီး ကလစ်ဘုတ်သို့ ကူးယူပါ';
// / 'Generate Link'
$Gui2Text33 = 'လင့်ခ် ထုတ်လုပ်ရန်';
// / 'Scan This File For Viruses'
$Gui2Text34 = 'ဤဖိုင်ကို ဗိုင်းရပ်စ် ရှိမရှိ စစ်ဆေးပါ';
// / 'Scan File With ScanCore'
$Gui2Text35 = 'ScanCore ဖြင့် ဖိုင်ကို စစ်ဆေးပါ';
// / 'Scan File With ClamAV'
$Gui2Text36 = 'ClamAV ဖြင့် ဖိုင်ကို စစ်ဆေးပါ';
// / 'Scan File With ScanCore & ClamAV'
$Gui2Text37 = 'ScanCore နှင့် ClamAV နှစ်ခုစလုံးဖြင့် ဖိုင်ကို စစ်ဆေးပါ';
// / 'Perform Optical Character Recognition On This File'
$Gui2Text38 = 'ဤဖိုင်ပေါ်တွင် စာလုံးပုံရိပ်ဖတ်စနစ် (OCR) ကို လုပ်ဆောင်ပါ';
// / 'Method'
$Gui2Text39 = 'နည်းလမ်း';
// / 'Simple'
$Gui2Text40 = 'ရိုးရှင်းသော';
// / 'Advanced'
$Gui2Text41 = 'အဆင့်မြင့်';
// / 'Convert This Archive'
$Gui2Text42 = 'ဤမော်ကွန်းဖိုင်ကို ပြောင်းလဲပါ';
// / 'Convert This Document'
$Gui2Text43 = 'ဤစာရွက်စာတမ်းကို ပြောင်းလဲပါ';
// / 'Convert This Spreadsheet'
$Gui2Text44 = 'ဤစပရက်ရှီတ်ကို ပြောင်းလဲပါ';
// / 'Convert This Audio'
$Gui2Text45 = 'ဤအော်ဒီယိုကို ပြောင်းလဲပါ';
// / 'Convert This Video'
$Gui2Text46 = 'ဤဗီဒီယိုကို ပြောင်းလဲပါ';
// / 'Convert This Stream'
$Gui2Text47 = 'ဤတိုက်ရိုက်ထုတ်လွှင့်မှုကို ပြောင်းလဲပါ';
// / Convert This 3D Model'
$Gui2Text48 = 'ဤ 3D မော်ဒယ်ကို ပြောင်းလဲပါ';
// / 'Convert This Technical Drawing Or Vector File'
$Gui2Text49 = 'ဤစက်မှုပုံဆွဲခြင်း သို့မဟုတ် ဗက်တာဖိုင်ကို ပြောင်းလဲပါ';
// / 'Convert This Image'
$Gui2Text50 = 'ဤရုပ်ပုံကို ပြောင်းလဲပါ';
// / 'Archive File'
$Gui2Text51 = 'ဖိုင်ကို မော်ကွန်းတင်ပါ';
// / 'Convert Into Document'
$Gui2Text52 = 'စာရွက်စာတမ်းအဖြစ် ပြောင်းလဲရန်';
// / 'Archive Files'
$Gui2Text53 = 'ဖိုင်များကို မော်ကွန်းတင်ပါ';
// / 'Convert Document'
$Gui2Text54 = 'စာရွက်စာတမ်းကို ပြောင်းလဲရန်';
// / 'Convert Spreadsheet'
$Gui2Text55 = 'စပရက်ရှီတ်ကို ပြောင်းလဲရန်';
// / 'Convert Presentation'
$Gui2Text56 = 'ပရစင်တေးရှင်းကို ပြောင်းလဲရန်';
// / 'Convert Audio'
$Gui2Text57 = 'အော်ဒီယိုကို ပြောင်းလဲရန်';
// / 'Convert Video'
$Gui2Text58 = 'ဗီဒီယိုကို ပြောင်းလဲရန်';
// / 'Convert Stream'
$Gui2Text59 = 'တိုက်ရိုက်ထုတ်လွှင့်မှုကို ပြောင်းလဲရန်';
// / 'Convert Model'
$Gui2Text60 = 'မော်ဒယ်ကို ပြောင်းလဲရန်';
// / 'Convert Drawing'
$Gui2Text61 = 'ပုံဆွဲခြင်းကို ပြလဲရန်';
// / 'Convert Image'
$Gui2Text62 = 'ရုပ်ပုံကို ပြောင်းလဲရန်';
// / 'Width & Height'
$Gui2Text64 = 'အကျယ်နှင့် အမြင့်: ';
// / 'Rotate: '
$Gui2Text65 = 'လှည့်ရန်: ';
// / 'Bitrate: '
$Gui2Text66 = 'ဒေတာနှုန်း: ';
// / 'Delete'
$Gui2Text67 = 'ပယ်ဖျက်ရန်';
// / 'Close Delete Options'
$Gui2Text68 = 'ပယ်ဖျက်ခြင်း ရွေးချယ်စရာများကို ပိတ်ပါ';
// / 'Delete This File'
$Gui2Text69 = 'ဤဖိုင်ကို ပယ်ဖျက်ပါ';
// / 'Confirm Delete'
$Gui2Text70 = 'ပယ်ဖျက်ခြင်းကို အတည်ပြုပါ';
// / 'Cannot convert this file! Try changing the name.'
$Gui2Text71 = 'ဤဖိုင်ကို မပြောင်းလဲနိုင်ပါ။ အမည်ပြောင်းလဲ၍ ထပ်မံကြိုးစားပါ။';
// / 'Cannot perform a virus scan on this file!'
$Gui2Text72 = 'ဤဖိုင်ကို ဗိုင်းရပ်စ် စစ်ဆေးခြင်း မပြုလုပ်နိုင်ပါ!';
// / 'File Link Copied to Clipboard!'
$Gui2Text73 = 'ဖိုင်လင့်ခ်ကို ကလစ်ဘုတ်သို့ ကူးယူပြီးပါပြီ!';
// / 'Operation Failed!'
$Gui2Text74 = 'လုပ်ဆောင်ချက် မအောင်မြင်ပါ!';
// / 'Convert These Subtitles'
$Gui2Text75 = 'ဤစာတန်းထိုးများကို ပြောင်းလဲပါ';
// / 'Convert Subtitles'
$Gui2Text76 = 'စာတန်းထိုးကို ပြောင်းလဲရန်';
// / 'Convert This Presentation'
$Gui2Text77 = 'ဤပရစင်တေးရှင်းကို ပြောင်းလဲပါ';
// / 'Convert This XPS File'
$Gui2Text78 = 'ဤ XPS ဖိုင်ကို ပြောင်းလဲပါ';
// / 'Render This OpenSCAD Model'
$Gui2Text79 = 'ဤ OpenSCAD မော်ဒယ်ကို ထုတ်လုပ်ပါ';
// / 'Render Model'
$Gui2Text80 = 'မော်ဒယ် ထုတ်လုပ်ရန်';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - HRC2-Functions.js Related Variables.
// / These strings are used by the client side javascript library.
// / That file is static javascript & cannot read a PHP variable, so every string it needs
// / is passed to it as an argument by the PHP that calls it.
// / 'Your browser does not support copying to the clipboard!'
$GuiFunctionsText1 = 'သင်၏ဘရောက်ဆာသည် ကလစ်ဘုတ်သို့ ကူးယူခြင်းကို မပံ့ပိုးပါ!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy'
$GuiFooterText1 = 'ကျွန်ုပ်တို့၏ <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>ဝန်ဆောင်မှုစည်းမျဉ်းများ</a> နှင့် <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>ကိုယ်ရေးအချက်အလက်မူဝါဒ</a> ကို ကြည့်ရှုပါ။';
// / -----------------------------------------------------------------------------------
