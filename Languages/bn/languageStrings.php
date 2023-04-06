<?php
// / -----------------------------------------------------------------------------------
// / APPLICATION INFORMATION ...
// / HRConvert2, Copyright on 4/5/2023 by Justin Grimes, www.github.com/zelon88
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
// / v3.2.2.
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
$GUIDirection = 'ltr';
// / Set the side of the page to float elements to.
$GUIAlignment = 'left';
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die('ত্রুটি!!! HRConvert2-2, এই ফাইলটি আপনার অনুরোধ প্রক্রিয়া করতে পারে না! পরিবর্তে convertCore.php এ আপনার ফাইল জমা দিন!');
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Check for required core variables.
if (!isset($Font)) $Font = 'Arial';
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = 'কিছু রূপান্তর!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI Related Logic.
$FCPlural1 = '';
if (!is_numeric($FileCount)) $FileCount = 'একটি অজানা সংখ্যা';
if ($FileCount === 0) $FCPlural1 = 'আপনি '.$ApplicationName.' এ 0টি বৈধ ফাইল আপলোড করেছেন।';
if ($FileCount === 1) $FCPlural1 = 'আপনি '.$ApplicationName.' এ 1টি বৈধ ফাইল আপলোড করেছেন৷'; 
if ($FileCount === 2) $FCPlural1 = 'আপনি '.$ApplicationName.' এ 2টি বৈধ ফাইল আপলোড করেছেন৷';
if ($FileCount >= 3) $FCPlural1 = 'আপনি '.$ApplicationName.' এ '.$FileCount.'টি বৈধ ফাইল আপলোড করেছেন।';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Header Related Variables.
// / 'Click, Tap, or Drop files here to upload.'
$GuiHeaderText1 = 'আপলোড করতে এখানে ফাইলগুলিকে ক্লিক করুন, ট্যাপ করুন বা ড্রপ করুন৷';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / Online File Converter, Extractor, Compressor'
$Gui1Text1 = 'অনলাইন ফাইল কনভার্টার, এক্সট্র্যাক্টর, কম্প্রেসার<';
// / $ApplicationName.' is based off the open-source web-app <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> by <a href=\'https://github.com/zelon88\'>Zelon88</a> that converts files without tracking users across the net or infringing on your intellectual property.'
$Gui1Text2 = '<a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> <a href=\'https://github.com/zelon88\'>Zelon88</a> দ্বারা '.$ApplicationName.' নামের ওপেন-সোর্স অ্যাপ্লিকেশনের উপর ভিত্তি করে তৈরি. এটি ব্যবহারকারীদের ট্র্যাকিং না করে বা আপনার গোপনীয়তার অধিকার লঙ্ঘন না করে ফাইলগুলিকে রূপান্তর করার জন্য ডিজাইন করা হয়েছিল৷';
// / 'More Info ...'
$Gui1Text3 = 'অধিক তথ্য ...';
// / 'Less Info ...'
$Gui1Text4 = 'কম তথ্য ...';
// / 'All user-supplied data is erased automatically, so you don\'t need to worry about forfeiting your personal information or property while using our services.'
$Gui1Text5 = 'সমস্ত ব্যবহারকারীর সরবরাহকৃত ডেটা স্বয়ংক্রিয়ভাবে মুছে ফেলা হয়, তাই আমাদের পরিষেবাগুলি ব্যবহার করার সময় আপনার ব্যক্তিগত তথ্য বা সম্পত্তি বাজেয়াপ্ত করার বিষয়ে আপনাকে চিন্তা করতে হবে না।';
// / 'Currently '.$ApplicationName.' supports '.$SupportedFormatCount.' different file formats, including documents, spreadsheets, images, media, 3D models, CAD drawings, vector files, archives, disk images, & more.'
$Gui1Text6 = 'বর্তমানে '.$ApplicationName.' নথি, স্প্রেডশীট, ছবি, মিডিয়া, ত্রিমাত্রিক মডেল, অঙ্কন ফাইল, ভেক্টর ফাইল, সংরক্ষণাগার, ডিস্ক চিত্র এবং আরও অনেক কিছু সহ 79টি ভিন্ন ফাইল ফর্ম্যাট সমর্থন করে।';
// / 'View Supported Formats ...'
$Gui1Text7 = 'সমর্থিত ফরম্যাট দেখুন ...';
// / 'Hide Supported Formats ...'
$Gui1Text8 = 'সমর্থিত বিন্যাস লুকান ...';
// / 'Supported Formats'
$Gui1Text9 = 'সমর্থিত ফরম্যাট';
// / 'Audio Formats'
$Gui1Text10 = 'অডিও ফরম্যাট';
// / 'Supports specific bitrate.'
$Gui1Text11 = 'নির্দিষ্ট বিটরেট সমর্থন করে।';
// / 'Video Formats'
$Gui1Text12 = 'ভিডিও ফরম্যাট';
// / 'Stream Formats'
$Gui1Text13 = 'স্ট্রিম বিন্যাস';
// / 'Document Formats'
$Gui1Text14 = 'নথি বিন্যাস';
// / 'Spreadsheet Formats'
$Gui1Text15 = 'স্প্রেডশীট বিন্যাস';
// / 'Presentation Formats'
$Gui1Text16 = 'উপস্থাপনা বিন্যাস';
// / 'Archive Formats'
$Gui1Text17 = 'সংরক্ষণাগার বিন্যাস';
// / 'Can convert between archive formats & disk image formats.'
$Gui1Text18 = 'নির্বাচিত সংরক্ষণাগার বিন্যাস এবং ডিস্ক চিত্র বিন্যাস মধ্যে রূপান্তর করতে পারেন.';
// / 'Image Formats'
$Gui1Text19 = 'ইমেজ ফরম্যাট';
// / 'Can convert pictures of documents to document formats.'
$Gui1Text20 = 'নথির ছবি নথি বিন্যাসে রূপান্তর করতে পারেন.';
// / 'Supports resize & rotate.'
$Gui1Text21 = 'আকার পরিবর্তন এবং ঘোরানো সমর্থন করে।';
// / '3D Model Formats'
$Gui1Text22 = '3D থ্রি ডাইমেনশনাল মডেল ফরম্যাট';
// / 'Drawing Formats'
$Gui1Text23 = 'অঙ্কন বিন্যাস';
// / 'Can convert drawing files to image formats.'
$Gui1Text24 = 'অঙ্কন বিন্যাসকে চিত্র বিন্যাসে রূপান্তর করতে পারে।';
// / 'OCR Support'
$Gui1Text25 = 'অপটিক্যাল ক্যারেক্টার রিকগনিশন সাপোর্ট';
// / 'OCR Operations support the following input formats...'
$Gui1Text26 = 'অপটিক্যাল ক্যারেক্টার রিকগনিশন অপারেশন নিম্নলিখিত ইনপুট ফর্ম্যাটগুলিকে সমর্থন করে...';
// / 'OCR Operations support the following output formats...'
$Gui1Text27 = 'অপটিক্যাল ক্যারেক্টার রিকগনিশন অপারেশন নিম্নলিখিত আউটপুট ফর্ম্যাটগুলিকে সমর্থন করে...';
// / 'Select files by clicking, tapping, or dropping them into the box below.'
$Gui1Text28 = 'নিচের বাক্সে ক্লিক করে, ট্যাপ করে বা ড্রপ করে ফাইলগুলি নির্বাচন করুন৷';
// / 'Continue ...'
$Gui1Text29 = 'চালিয়ে যান...';
// / 'Can convert stream formats to video formats.'
$Gui1Text30 = 'স্ট্রিম ফর্ম্যাটগুলিকে ভিডিও ফর্ম্যাটে রূপান্তর করতে পারে৷';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'ফাইল রূপান্তর বিকল্প';
// / 'Bulk File Options'
$Gui2Text2 = 'বাল্ক ফাইল অপশন';
// / 'Scan All Files For Viruses'
$Gui2Text3 = 'ভাইরাসের জন্য সমস্ত ফাইল স্ক্যান করুন';
// / 'Compress & Download All Files'
$Gui2Text4 = 'কম্প্রেস করুন এবং সমস্ত ফাইল ডাউনলোড করুন';
// / 'Download'
$Gui2Text5 = 'ডাউনলোড করুন';
// / 'Share'
$Gui2Text6 = 'শেয়ার করুন';
// / 'Close Share Options'
$Gui2Text7 = 'শেয়ার অপশন বন্ধ করুন';
// / 'Virus Scan'
$Gui2Text8 = 'ভাইরাস স্ক্যান';
// / 'Close Virus Scan Options'
$Gui2Text9 = 'ভাইরাস স্ক্যান বিকল্পগুলি বন্ধ করুন';
// / 'Archive'
$Gui2Text10 = 'সংরক্ষণাগার';
// / 'Close Archive Options'
$Gui2Text11 = 'সংরক্ষণাগার বিকল্পগুলি বন্ধ করুন৷';
// / 'OCR'
$Gui2Text12 = 'অপটিক্যাল ক্যারেক্টার রেকগনিশন';
// / 'Close OCR Options'
$Gui2Text13 = 'অপটিক্যাল ক্যারেক্টার রিকগনিশন অপশন বন্ধ করুন';
// / 'Convert'
$Gui2Text14 = 'রূপান্তর করুন';
// / 'Close Convert Options'
$Gui2Text15 = 'রূপান্তর বিকল্পগুলি বন্ধ করুন';
// / 'Archive This File'
$Gui2Text16 = 'এই ফাইলটি আর্কাইভ করুন';
// / 'Specify Filename: '
$Gui2Text17 = 'ফাইলের নাম উল্লেখ করুন:';
// / 'Format'
$Gui2Text18 = 'বিন্যাস';
// / 'Compress & Download'
$Gui2Text19 = 'কম্প্রেস এবং ডাউনলোড করুন';
// / 'Scan with ClamAV: '
$Gui2Text20 = 'ClamAV দিয়ে স্ক্যান করুন:';
// / 'Scan with ScanCore: '
$Gui2Text21 = 'ScanCore দিয়ে স্ক্যান করুন:';
// / 'Scan All'
$Gui2Text22 = 'সমস্ত স্ক্যান করুন';
// / 'Share This File'
$Gui2Text23 = 'এই ফাইল শেয়ার করুন';
// / 'Link Status: '
$Gui2Text24 = 'লিঙ্ক স্থিতি:';
// / 'Not Generated'
$Gui2Text25 = 'জেনারেট করা হয়নি';
// / 'Generated'
$Gui2Text26 = 'উৎপন্ন';
// / 'Clipboard Status: '
$Gui2Text27 = 'ক্লিপবোর্ড স্থিতি:';
// / 'Copied'
$Gui2Text28 = 'কপি করা হয়েছে';
// / 'File Link: '
$Gui2Text29 = 'ফাইল লিঙ্ক:';
// / 'You have uploaded '.$FileCount.' valid file'.$FCPlural1.' to '.$ApplicationName.'.'
$Gui2Text30 = $FCPlural1;
// / 'Your file'.$FCPlural2.' now ready to convert using the options below.'
$Gui2Text31 = 'আপনার ফাইলগুলি এখন নীচের বিকল্পগুলি ব্যবহার করে রূপান্তর করার জন্য প্রস্তুত৷';
// / 'Generate Link & Copy to Clipboard'
$Gui2Text32 = 'লিঙ্ক তৈরি করুন এবং ক্লিপবোর্ডে অনুলিপি করুন';
// / 'Generate Link'
$Gui2Text33 = 'লিংক তৈরি করুন';
// / 'Scan This File For Viruses'
$Gui2Text34 = 'ভাইরাসের জন্য এই ফাইলটি স্ক্যান করুন';
// / 'Scan File With ScanCore'
$Gui2Text35 = 'ScanCore দিয়ে ফাইল স্ক্যান করুন';
// / 'Scan File With ClamAV'
$Gui2Text36 = 'ClamAV দিয়ে ফাইল স্ক্যান করুন';
// / 'Scan File With ScanCore & ClamAV'
$Gui2Text37 = 'ScanCore এবং ClamAV দিয়ে ফাইল স্ক্যান করুন';
// / 'Perform Optical Character Recognition On This File'
$Gui2Text38 = 'এই ফাইলে অপটিক্যাল ক্যারেক্টার রিকগনিশন সঞ্চালন করুন';
// / 'Method'
$Gui2Text39 = 'পদ্ধতি';
// / 'Simple'
$Gui2Text40 = 'সরল';
// / 'Advanced'
$Gui2Text41 = 'উন্নত';
// / 'Convert This Archive'
$Gui2Text42 = 'এই সংরক্ষণাগার রূপান্তর করুন';
// / 'Convert This Spreadsheet'
$Gui2Text43 = 'এই নথিটি রূপান্তর করুন';
// / 'Convert This Spreadsheet'
$Gui2Text44 = 'এই স্প্রেডশীট রূপান্তর করুন';
// / 'Convert This Audio'
$Gui2Text45 = 'এই অডিও রূপান্তর করুন';
// / 'Convert This Video'
$Gui2Text46 = 'এই ভিডিওটি রূপান্তর করুন';
// / 'Convert This Stream'
$Gui2Text47 = 'এই স্ট্রীম রূপান্তর করুন';
// / Convert This 3D Model'
$Gui2Text48 = 'এই মডেল রূপান্তর করুন';
// / 'Convert This Technical Drawing Or Vector File'
$Gui2Text49 = 'এই প্রযুক্তিগত অঙ্কন বা ভেক্টর ফাইল রূপান্তর করুন';
// / 'Convert This Image'
$Gui2Text50 = 'এই চিত্রটি রূপান্তর করুন';
// / 'Archive File'
$Gui2Text51 = 'সংরক্ষণাগার ফাইল';
// / 'Convert Into Document'
$Gui2Text52 = 'নথিতে রূপান্তর করুন';
// / 'Archive Files'
$Gui2Text53 = 'সংরক্ষণাগার ফাইল';
// / 'Convert Document'
$Gui2Text54 = 'নথি রূপান্তর করুন';
// / 'Convert Spreadsheet'
$Gui2Text55 = 'স্প্রেডশীট রূপান্তর করুন';
// / 'Convert Presentation'
$Gui2Text56 = 'উপস্থাপনা রূপান্তর করুন';
// / 'Convert Audio'
$Gui2Text57 = 'অডিও রূপান্তর করুন';
// / 'Convert Video'
$Gui2Text58 = 'ভিডিও রূপান্তর';
// / 'Convert Stream'
$Gui2Text59 = 'স্ট্রিম রূপান্তর করুন';
// / 'Convert Model'
$Gui2Text60 = 'রূপান্তর মডেল';
// / 'Convert Drawing'
$Gui2Text61 = 'অঙ্কন রূপান্তর';
// / 'Convert Image'
$Gui2Text62 = 'ছবি রূপান্তর করুন';
// / 'Width & Height'
$Gui2Text64 = 'প্রস্থ উচ্চতা:';
// / 'Rotate: '
$Gui2Text65 = 'আবর্তিত:';
// / 'Bitrate: '
$Gui2Text66 = 'বিটরেট:';
// / 'Delete'
$Gui2Text67 = 'মুছে ফেলা';
// / 'Close Delete Options'
$Gui2Text68 = 'মুছে ফেলার বিকল্পগুলি বন্ধ করুন';
// / 'Delete This File'
$Gui2Text69 = 'এই ফাইলটি মুছুন';
// / 'Confirm Delete'
$Gui2Text70 = 'নিশ্চিত বাতিল';
// / 'Cannot convert this file! Try changing the name.'
$Gui2Text71 = 'এই ফাইলটি রূপান্তর করা যাবে না! নাম পরিবর্তন করার চেষ্টা করুন।';
// / 'Cannot perform a virus scan on this file!'
$Gui2Text72 = 'এই ফাইলে ভাইরাস স্ক্যান করা যাবে না!';
// / 'File Link Copied to Clipboard!'
$Gui2Text73 = 'ফাইল লিঙ্ক ক্লিপবোর্ডে অনুলিপি করা হয়েছে!';
// / 'Operation Failed!'
$Gui2Text74 = 'অপারেশন ব্যর্থ হয়েছে!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy'
$GuiFooterText1 = 'আমাদের পরিষেবার <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>শর্তাবলী</a> এবং <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>গোপনীয়তা নীতি</a> দেখুন';
// / -----------------------------------------------------------------------------------