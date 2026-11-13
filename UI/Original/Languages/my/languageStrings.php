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
$LanguageVersion = 'v4.1.8';
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
// / Set GUI - UI Selector Bar Related Variables.
// / These strings appear in the selector bar, which is present on both GUI1 & GUI2.
// / 'Language'
$GuiSelectorText1 = 'ဘာသာစကား';
// / 'Color'
$GuiSelectorText2 = 'အရောင်';
// / 'Interface'
$GuiSelectorText3 = 'အင်တာဖေ့စ်';
// / 'Display language, color and interface options'
$GuiSelectorText4 = 'ဘာသာစကား၊ အရောင်နှင့် အင်တာဖေ့စ် ရွေးချယ်စရာများကို ပြပါ';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / 'Online File Converter, Extractor, Compressor'
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
// / 'Delete every uploaded file & start a new session?'
// / Shown inside the start over panel on the upload page. That panel is only rendered
// / when the session already holds at least one file, so this is never shown to a first
// / time visitor who has nothing to lose.
$Gui1Text35 = 'တင်ထားသော ဖိုင်အားလုံးကို ပယ်ဖျက်ပြီး ဆက်ရှင်အသစ် စတင်မလား။';
// / 'Start Over'
// / Labels the control that opens the panel & the button inside it that confirms.
$Gui1Text36 = 'ပြန်စပါ';
// / 'Refresh'
// / Alternate text for the refresh control on the upload page. That control is a glyph
// / with no text of its own, so this is the only description a screen reader has & the
// / only thing shown when a browser cannot render the glyph.
$Gui1Text37 = 'ပြန်လည်ဆန်းသစ်ပါ';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'ဖိုင်ပြောင်းလဲခြင်း ရွေးချယ်စရာများ';
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
// / 'Convert This 3D Model'
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
// / 'Convert This E-Book'
$Gui2Text81 = 'ဤအီလက်ထရွန်နစ်စာအုပ်ကို ပြောင်းလဲရန်';
// / 'Convert E-Book'
$Gui2Text82 = 'အီလက်ထရွန်နစ်စာအုပ် ပြောင်းလဲရန်';
// / 'Playlist source URL, only needed if the playlist uses relative paths'
$Gui2Text83 = 'ဖွင့်စာရင်း၏ မူလ URL၊ ဆွယ်လမ်းကြောင်းများ သုံးမှသာ လိုအပ်သည်';
// / 'Download A File From A URL'
$Gui2Text84 = 'URL မှ ဖိုင်ကို ဒေါင်းလုဒ်လုပ်ရန်';
// / 'Fetch'
$Gui2Text85 = 'ရယူရန်';
// / 'Identify'
$Gui2Text86 = 'ခွဲခြားသတ်မှတ်ပါ';
// / 'Close Identify Options'
$Gui2Text87 = 'ခွဲခြားသတ်မှတ်ခြင်း ရွေးချယ်စရာများကို ပိတ်ပါ';
// / 'Identify This File'
$Gui2Text88 = 'ဤဖိုင်ကို ခွဲခြားသတ်မှတ်ပါ';
// / 'Size: '
$Gui2Text89 = 'အရွယ်အစား: ';
// / 'Modified: '
$Gui2Text90 = 'ပြင်ဆင်ခဲ့သည့်အချိန်: ';
// / 'Type: '
$Gui2Text91 = 'အမျိုးအစား: ';
// / 'MIME Type: '
$Gui2Text92 = 'MIME အမျိုးအစား: ';
// / 'Character Set: '
$Gui2Text93 = 'စာလုံးအစု: ';
// / 'Extension Matches Content: '
$Gui2Text94 = 'ဖိုင်တိုးချဲ့အမည်နှင့် အကြောင်းအရာ ကိုက်ညီမှု: ';
// / 'Details: '
$Gui2Text95 = 'အသေးစိတ်: ';
// / 'Notes: '
$Gui2Text96 = 'မှတ်ချက်များ: ';
// / 'Save report as file'
$Gui2Text97 = 'အစီရင်ခံစာကို ဖိုင်အဖြစ် သိမ်းဆည်းပါ';
// / 'Yes'
$Gui2Text100 = 'ဟုတ်ကဲ့';
// / 'No'
$Gui2Text101 = 'မဟုတ်ပါ';
// / 'Unknown'
$Gui2Text102 = 'မသိရှိပါ';
// / 'Cannot identify this file!'
$Gui2Text103 = 'ဤဖိုင်ကို ခွဲခြားသတ်မှတ်၍ မရပါ!';
// / 'Sort By: '
$Gui2Text104 = 'စီစဉ်ရန်: ';
// / 'Name'
$Gui2Text105 = 'အမည်';
// / 'Size'
$Gui2Text106 = 'အရွယ်အစား';
// / 'Date'
$Gui2Text107 = 'ရက်စွဲ';
// / 'Type'
$Gui2Text108 = 'အမျိုးအစား';
// / The words in braces, such as {fit} & {count}, are filled in by the page. Each translation keeps every one of them.
// / 'Select'
$Gui2Text109 = 'ရွေးပါ';
// / 'Select All'
$Gui2Text110 = 'အားလုံးရွေးပါ';
// / 'Select None'
$Gui2Text111 = 'ရွေးချယ်မှုအားလုံး ဖြုတ်ပါ';
// / 'Select By Type'
$Gui2Text112 = 'အမျိုးအစားအလိုက် ရွေးပါ';
// / 'Selected: '
$Gui2Text113 = 'ရွေးထားသည်: ';
// / 'Documents'
$Gui2Text114 = 'စာရွက်စာတမ်းများ';
// / 'Spreadsheets'
$Gui2Text115 = 'စပရက်ရှီတ်များ';
// / 'Presentations'
$Gui2Text116 = 'ပရဆင်တေးရှင်းများ';
// / 'Images'
$Gui2Text117 = 'ရုပ်ပုံများ';
// / 'Audio'
$Gui2Text118 = 'အော်ဒီယို';
// / 'Video'
$Gui2Text119 = 'ဗီဒီယို';
// / 'Archives'
$Gui2Text120 = 'မော်ကွန်းဖိုင်များ';
// / 'E-Books'
$Gui2Text121 = 'အီလက်ထရွန်နစ်စာအုပ်များ';
// / '3D Models'
$Gui2Text122 = '3D မော်ဒယ်များ';
// / 'Drawings'
$Gui2Text123 = 'ပုံဆွဲချက်များ';
// / 'Vector Images'
$Gui2Text124 = 'ဗက်တာ ရုပ်ပုံများ';
// / 'Subtitles'
$Gui2Text125 = 'စာတန်းထိုးများ';
// / 'OpenSCAD Files'
$Gui2Text126 = 'OpenSCAD ဖိုင်များ';
// / 'Streams'
$Gui2Text127 = 'တိုက်ရိုက်ထုတ်လွှင့်မှုများ';
// / 'Workflows'
$Gui2Text128 = 'လုပ်ငန်းစဉ်များ';
// / 'Selected Files'
$Gui2Text129 = 'ရွေးထားသော ဖိုင်များ';
// / 'Select files in the list below.'
$Gui2Text130 = 'အောက်ပါစာရင်းမှ ဖိုင်များကို ရွေးပါ။';
// / 'Select By Name: '
$Gui2Text131 = 'အမည်ဖြင့် ရွေးပါ: ';
// / 'Use * and ? as wildcards, or write a regular expression between slashes.'
$Gui2Text132 = '* နှင့် ? ကို အစားထိုးသင်္ကေတအဖြစ် သုံးပါ၊ သို့မဟုတ် မျဉ်းစောင်းနှစ်ခုကြားတွင် ပုံမှန်ဖော်ပြချက် (regular expression) ကို ရေးပါ။';
// / 'Workflow'
$Gui2Text133 = 'လုပ်ငန်းစဉ်';
// / 'Choose a workflow'
$Gui2Text134 = 'လုပ်ငန်းစဉ်တစ်ခု ရွေးပါ';
// / 'Run'
$Gui2Text135 = 'လုပ်ဆောင်ပါ';
// / 'Stop'
$Gui2Text136 = 'ရပ်ပါ';
// / 'Selected files that can be used: {fit} of {count}.'
$Gui2Text137 = 'အသုံးပြုနိုင်သော ရွေးထားသည့်ဖိုင်များ: {count} ခုအနက် {fit} ခု။';
// / 'Step {step} of {steps}: {operation}'
$Gui2Text138 = 'အဆင့် {step}/{steps}: {operation}';
// / 'File {done} of {total}'
$Gui2Text139 = 'ဖိုင် {done}/{total}';
// / 'Waiting for the server.'
$Gui2Text140 = 'ဆာဗာကို စောင့်နေသည်။';
// / 'Starting.'
$Gui2Text141 = 'စတင်နေသည်။';
// / 'Finished.'
$Gui2Text142 = 'ပြီးဆုံးပါပြီ။';
// / 'Stopping.'
$Gui2Text143 = 'ရပ်တန့်နေသည်။';
// / 'Stopped.'
$Gui2Text144 = 'ရပ်တန့်လိုက်ပါပြီ။';
// / 'Files made: '
$Gui2Text145 = 'ပြုလုပ်ထားသော ဖိုင်များ: ';
// / 'Workflows are not available on this server.'
$Gui2Text146 = 'ဤဆာဗာတွင် လုပ်ငန်းစဉ်များကို အသုံးမပြုနိုင်ပါ။';
// / 'No workflows are installed on this server.'
$Gui2Text147 = 'ဤဆာဗာတွင် ထည့်သွင်းထားသော လုပ်ငန်းစဉ် မရှိပါ။';
// / 'That workflow is no longer available.'
$Gui2Text148 = 'ထိုလုပ်ငန်းစဉ်ကို အသုံးမပြုနိုင်တော့ပါ။';
// / 'None of the selected files can be used by this workflow.'
$Gui2Text149 = 'ရွေးထားသောဖိုင်များထဲမှ မည်သည့်ဖိုင်ကိုမျှ ဤလုပ်ငန်းစဉ်က အသုံးမပြုနိုင်ပါ။';
// / 'Too many files are selected for one workflow.'
$Gui2Text150 = 'လုပ်ငန်းစဉ်တစ်ခုအတွက် ဖိုင်များ အလွန်များစွာ ရွေးထားသည်။';
// / 'The server is busy. Try again in a few minutes.'
$Gui2Text151 = 'ဆာဗာ အလုပ်များနေသည်။ မိနစ်အနည်းငယ်အကြာတွင် ထပ်ကြိုးစားပါ။';
// / 'A workflow is already running in this session.'
$Gui2Text152 = 'ဤဆက်ရှင်တွင် လုပ်ငန်းစဉ်တစ်ခု လုပ်ဆောင်နေပြီ ဖြစ်သည်။';
// / 'The workflow could not be started.'
$Gui2Text153 = 'လုပ်ငန်းစဉ်ကို စတင်၍ မရပါ။';
// / 'A step of the workflow failed.'
$Gui2Text154 = 'လုပ်ငန်းစဉ်၏ အဆင့်တစ်ခု မအောင်မြင်ပါ။';
// / 'A virus was found, so the workflow stopped.'
$Gui2Text155 = 'ဗိုင်းရပ်စ်တစ်ခု တွေ့ရှိသဖြင့် လုပ်ငန်းစဉ်ကို ရပ်တန့်လိုက်သည်။';
// / 'The workflow ran out of time.'
$Gui2Text156 = 'လုပ်ငန်းစဉ်၏ အချိန် ကုန်သွားပါပြီ။';
// / 'The workflow made too many files.'
$Gui2Text157 = 'လုပ်ငန်းစဉ်သည် ဖိုင်များ အလွန်များစွာ ပြုလုပ်ခဲ့သည်။';
// / 'The server was too busy to finish the workflow.'
$Gui2Text158 = 'ဆာဗာ အလွန်အလုပ်များနေသဖြင့် လုပ်ငန်းစဉ်ကို အပြီးမသတ်နိုင်ခဲ့ပါ။';
// / 'An operation this workflow needs is not available.'
$Gui2Text159 = 'ဤလုပ်ငန်းစဉ် လိုအပ်သော လုပ်ဆောင်ချက်တစ်ခုကို အသုံးမပြုနိုင်ပါ။';
// / 'The workflow stopped unexpectedly.'
$Gui2Text160 = 'လုပ်ငန်းစဉ် မမျှော်လင့်ဘဲ ရပ်သွားပါသည်။';
// / The words in braces, such as {step} & {kind}, are filled in by the page. Each translation keeps every one of them.
// / 'Available Pipelines'
$Gui2Text161 = 'ရနိုင်သော ပိုက်လိုင်းများ';
// / 'Select a pipeline above...'
$Gui2Text162 = 'အထက်တွင် ပိုက်လိုင်းတစ်ခု ရွေးပါ...';
// / 'Select Pipeline...'
$Gui2Text163 = 'ပိုက်လိုင်း ရွေးရန်...';
// / 'Select Files...'
$Gui2Text164 = 'ဖိုင်များ ရွေးရန်...';
// / 'Add a step'
$Gui2Text165 = 'အဆင့်တစ်ခု ထည့်ရန်';
// / 'Options'
$Gui2Text166 = 'ရွေးချယ်စရာများ';
// / 'Back'
$Gui2Text167 = 'နောက်သို့';
// / 'Take: '
$Gui2Text168 = 'ယူရန်: ';
// / 'the selected files'
$Gui2Text169 = 'ရွေးထားသော ဖိုင်များ';
// / 'the files step {step} made'
$Gui2Text170 = 'အဆင့် {step} မှ ပြုလုပ်ခဲ့သော ဖိုင်များ';
// / 'each file on its own'
$Gui2Text171 = 'ဖိုင်တစ်ခုချင်းစီ သီးခြား';
// / 'all files together'
$Gui2Text172 = 'ဖိုင်အားလုံး အတူတကွ';
// / 'Only these kinds: '
$Gui2Text173 = 'ဤအမျိုးအစားများသာ: ';
// / 'Only these extensions: '
$Gui2Text174 = 'ဤတိုးချဲ့အမည်များသာ: ';
// / 'Smallest size: '
$Gui2Text175 = 'အငယ်ဆုံး အရွယ်အစား: ';
// / 'Largest size: '
$Gui2Text176 = 'အကြီးဆုံး အရွယ်အစား: ';
// / 'In the order they arrive'
$Gui2Text177 = 'ရောက်ရှိသည့် အစီအစဉ်အတိုင်း';
// / 'all files'
$Gui2Text178 = 'ဖိုင်အားလုံး';
// / 'all {kind}'
$Gui2Text179 = '{kind} အားလုံး';
// / 'Width: '
$Gui2Text180 = 'အကျယ်: ';
// / 'Height: '
$Gui2Text181 = 'အမြင့်: ';
// / 'My workflow'
$Gui2Text182 = 'ကျွန်ုပ်၏ လုပ်ငန်းစဉ်';
// / 'New'
$Gui2Text183 = 'အသစ်';
// / 'Import'
$Gui2Text184 = 'တင်သွင်းရန်';
// / 'Export'
$Gui2Text185 = 'ထုတ်ယူရန်';
// / 'This workflow is not one this server can run.'
$Gui2Text186 = 'ဤဆာဗာသည် ဤလုပ်ငန်းစဉ်ကို မလုပ်ဆောင်နိုင်ပါ။';
// / 'The file could not be read.'
$Gui2Text187 = 'ဖိုင်ကို ဖတ်၍ မရပါ။';
// / 'The workflow builder is not available on this server.'
$Gui2Text188 = 'ဤဆာဗာတွင် လုပ်ငန်းစဉ် တည်ဆောက်ကိရိယာ မရရှိနိုင်ပါ။';
// / 'Status'
$Gui2Text189 = 'အခြေအနေ';
// / 'A workflow is running.'
$Gui2Text190 = 'လုပ်ငန်းစဉ်တစ်ခု လုပ်ဆောင်နေသည်။';
// / 'No workflow is running.'
$Gui2Text191 = 'လုပ်ဆောင်နေသော လုပ်ငန်းစဉ် မရှိပါ။';
// / 'Extract'
$Gui2Text192 = 'ထုတ်ယူပါ';
// / 'Close Extract Options'
$Gui2Text193 = 'ထုတ်ယူခြင်း ရွေးချယ်စရာများကို ပိတ်ပါ';
// / 'Extract This Archive'
$Gui2Text194 = 'ဤမော်ကွန်းကို ထုတ်ယူပါ';
// / 'Every file it holds is added to your files, under its own name or the next free one.'
$Gui2Text195 = 'ယင်းတွင်ပါဝင်သော ဖိုင်တိုင်းကို ၎င်း၏အမည် သို့မဟုတ် နောက်ထပ်လွတ်နေသော အမည်ဖြင့် သင့်ဖိုင်များထဲသို့ ထည့်ပေးပါသည်။';
// / 'Extract Files'
$Gui2Text196 = 'ဖိုင်များကို ထုတ်ယူပါ';
// / 'to {format}'
$Gui2Text197 = '{format} သို့';
// / 'This workflow deletes files. A deleted file cannot be brought back.'
$Gui2Text198 = 'ဤလုပ်ငန်းစဉ်သည် ဖိုင်များကို ပယ်ဖျက်သည်။ ပယ်ဖျက်ပြီးသော ဖိုင်ကို ပြန်ယူ၍ မရပါ။';
// / 'Delete & Run'
$Gui2Text199 = 'ပယ်ဖျက်ပြီး လုပ်ဆောင်ပါ';
// / 'Cancel'
$Gui2Text200 = 'မလုပ်တော့ပါ';
// / 'Rename'
$Gui2Text201 = 'အမည်ပြောင်းရန်';
// / 'Close Rename Options'
$Gui2Text202 = 'အမည်ပြောင်းခြင်း ရွေးချယ်စရာများကို ပိတ်ပါ';
// / 'Rename This File'
$Gui2Text203 = 'ဤဖိုင်၏ အမည်ကို ပြောင်းပါ';
// / 'New Name'
$Gui2Text204 = 'အမည်သစ်';
// / 'Use {name} for the name it has, {n} for its place among the selected files & {date} for today. Its extension is kept.'
$Gui2Text205 = 'လက်ရှိအမည်အတွက် {name}၊ ရွေးထားသော ဖိုင်များထဲရှိ နေရာအတွက် {n} နှင့် ယနေ့ရက်စွဲအတွက် {date} ကို သုံးပါ။ ဖိုင်အမျိုးအစား နောက်ဆက်မပြောင်းပါ။';
// / 'Strip Metadata'
$Gui2Text206 = 'မက်တာဒေတာ ဖယ်ရှားရန်';
// / 'Close Strip Metadata Options'
$Gui2Text207 = 'မက်တာဒေတာ ရွေးချယ်စရာများကို ပိတ်ပါ';
// / 'Strip The Metadata From This File'
$Gui2Text208 = 'ဤဖိုင်မှ မက်တာဒေတာကို ဖယ်ရှားပါ';
// / 'A copy is made beside it without its authors, places, dates & the software that made it. What it shows is left as it was.'
$Gui2Text209 = 'ရေးသားသူ၊ နေရာ၊ ရက်စွဲနှင့် ၎င်းကို ဖန်တီးခဲ့သော ဆော့ဖ်ဝဲ မပါသော မိတ္တူတစ်ခုကို ဘေးတွင် ပြုလုပ်ပါသည်။ ပြသသည့်အရာမှာ ယခင်အတိုင်း ရှိနေပါသည်။';
// / 'Merge PDFs'
$Gui2Text210 = 'PDF များ ပေါင်းရန်';
// / 'Split PDF'
$Gui2Text211 = 'PDF ခွဲရန်';
// / 'Merge Or Split'
$Gui2Text212 = 'ပေါင်းရန် သို့မဟုတ် ခွဲရန်';
// / 'Close Merge & Split Options'
$Gui2Text213 = 'ပေါင်းခြင်းနှင့် ခွဲခြင်း ရွေးချယ်စရာများကို ပိတ်ပါ';
// / 'Merge The Selected PDFs Into One'
$Gui2Text214 = 'ရွေးထားသော PDF များကို တစ်ခုတည်း ပေါင်းပါ';
// / 'Their pages follow one another in the order the PDFs were selected.'
$Gui2Text215 = 'စာမျက်နှာများသည် PDF များကို ရွေးခဲ့သည့် အစီအစဉ်အတိုင်း ဆက်တိုက် ရှိပါသည်။';
// / 'Select two or more PDFs to merge them.'
$Gui2Text216 = 'ပေါင်းရန် PDF နှစ်ခု သို့မဟုတ် ထို့ထက်ပို၍ ရွေးပါ။';
// / 'Make A PDF Of Each Page'
$Gui2Text217 = 'စာမျက်နှာတိုင်းမှ PDF တစ်ခုစီ ပြုလုပ်ပါ';
// / 'Every selected file is deleted with it: {count} files.'
$Gui2Text218 = 'ရွေးထားသော ဖိုင်တိုင်းကိုလည်း အတူ ပယ်ဖျက်ပါမည်: {count} ခု။';
// / 'Every selected file is renamed with it: {count} files.'
$Gui2Text219 = 'ရွေးထားသော ဖိုင်တိုင်း၏ အမည်ကိုလည်း အတူ ပြောင်းပါမည်: {count} ခု။';
// / 'Library'
$Gui2Text220 = 'စာကြည့်တိုက်';
// / 'from Selected Files'
$Gui2Text221 = 'ရွေးထားသော ဖိုင်များမှ';
// / 'from Step {step}'
$Gui2Text222 = 'အဆင့် {step} မှ';
// / 'Preview'
$Gui2Text223 = 'အစမ်းကြည့်ရန်';
// / 'If the workflow ran now, each step would take:'
$Gui2Text224 = 'လုပ်ငန်းစဉ်ကို ယခု လုပ်ဆောင်ပါက အဆင့်တစ်ခုစီ ယူမည့် ဖိုင်များ:';
// / 'Left out: '
$Gui2Text225 = 'ချန်ထားသည်: ';
// / 'No files.'
$Gui2Text226 = 'ဖိုင် မရှိပါ။';
// / 'every file in the list when it runs'
$Gui2Text227 = 'အဆင့် လုပ်ဆောင်ချိန်တွင် စာရင်းထဲရှိ ဖိုင်အားလုံး';
// / 'This workflow deletes these files. A deleted file cannot be brought back.'
$Gui2Text228 = 'ဤလုပ်ငန်းစဉ်သည် ဤဖိုင်များကို ပယ်ဖျက်သည်။ ပယ်ဖျက်ပြီးသော ဖိုင်ကို ပြန်ယူ၍ မရပါ။';
// / 'A step had errors, so the workflow stopped before the step that deletes files.'
$Gui2Text229 = 'အဆင့်တစ်ခုတွင် အမှားများ ရှိခဲ့သဖြင့် ဖိုင်များကို ပယ်ဖျက်သည့် အဆင့်မတိုင်မီ လုပ်ငန်းစဉ် ရပ်သွားသည်။';
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
