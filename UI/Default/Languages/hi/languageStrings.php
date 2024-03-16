<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
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
$GUIDirection = 'ltr';
// / Set the side of the page to float elements to.
$GUIAlignment = 'left';
// / Define an error message to display for if the core has not been loaded.
$CoreError = 'गलती!!! HRConvert2-2, यह फ़ाइल आपके अनुरोध पर कार्रवाई नहीं कर सकती! इसके बजाय कृपया अपनी फ़ाइल ConvertCore.php पर सबमिट करें!';
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die($CoreError);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Check for required core variables.
if (!isset($Font)) $Font = 'Arial';
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = 'कुछ भी रूपांतरित करें!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI Related Logic.
$FCPlural1 = '';
if (!is_numeric($FileCount)) $FileCount = 'अज्ञात संख्या';
if ($FileCount === 0) $FCPlural1 = 'आपने 0 मान्य फ़ाइलें '.$ApplicationName.'-2 पर अपलोड की हैं।';
if ($FileCount === 1) $FCPlural1 = 'आपने '.$ApplicationName.'-2 पर 1 वैध फ़ाइल अपलोड की है।'; 
if ($FileCount > 1) $FCPlural1 = 'आपने '.$FileCount.' मान्य फ़ाइलें '.$ApplicationName.'-2 पर अपलोड की हैं।';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Header Related Variables.
// / 'Click, Tap, or Drop files here to upload.'
$GuiHeaderText1 = 'फ़ाइलों को अपलोड करने के लिए यहां क्लिक करें, टैप करें या ड्रॉप करें.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / Online File Converter, Extractor, Compressor'
$Gui1Text1 = 'ऑनलाइन फ़ाइल कन्वर्टर, एक्सट्रैक्टर, कंप्रेसर';
// / $ApplicationName.' is based off the open-source web-app <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> by <a href=\'https://github.com/zelon88\'>Zelon88</a> that converts files without tracking users across the net or infringing on your intellectual property.'
$Gui1Text2 = $ApplicationName.' द्वारा ओपन-सोर्स वेब-ऐप <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> पर आधारित है <a href=\'https://github.com/zelon88\'>Zelon88</a> जो नेट पर उपयोगकर्ताओं को ट्रैक किए बिना या आपकी बौद्धिक संपदा का उल्लंघन किए बिना फ़ाइलों को परिवर्तित करता है।';
// / 'More Info ...'
$Gui1Text3 = 'अधिक जानकारी...';
// / 'Less Info ...'
$Gui1Text4 = 'कम जानकारी...';
// / 'All user-supplied data is erased automatically, so you don\'t need to worry about forfeiting your personal information or property while using our services.'
$Gui1Text5 = 'उपयोगकर्ता द्वारा प्रदान किया गया सभी डेटा स्वचालित रूप से मिटा दिया जाता है, इसलिए आपको हमारी सेवाओं का उपयोग करते समय अपनी व्यक्तिगत जानकारी या संपत्ति को खोने के बारे में चिंता करने की आवश्यकता नहीं है।';
// / 'Currently '.$ApplicationName.' supports '.$SupportedFormatCount.' different file formats, including documents, spreadsheets, images, media, 3D models, CAD drawings, vector files, archives, disk images, & more.'
$Gui1Text6 = 'वर्तमान में'.$ApplicationName.' समर्थन करता है '.$SupportedFormatCount.' दस्तावेजों, स्प्रेडशीट्स, छवियों, मीडिया, 3डी मॉडल, सीएडी आरेखण, वेक्टर फाइलों, अभिलेखागार, डिस्क छवियों, और अधिक सहित विभिन्न फ़ाइल स्वरूप।';
// / 'View Supported Formats ...'
$Gui1Text7 = 'समर्थित प्रारूप देखें...';
// / 'Hide Supported Formats ...'
$Gui1Text8 = 'समर्थित प्रारूप छुपाएं...';
// / 'Supported Formats'
$Gui1Text9 = 'समर्थित प्रारूप';
// / 'Audio Formats'
$Gui1Text10 = 'ऑडियो प्रारूप';
// / 'Supports specific bitrate.'
$Gui1Text11 = 'विशिष्ट बिटरेट का समर्थन करता है.';
// / 'Video Formats'
$Gui1Text12 = 'वीडियो प्रारूप';
// / 'Stream Formats'
$Gui1Text13 = 'स्ट्रीम प्रारूप';
// / 'Document Formats'
$Gui1Text14 = 'दस्तावेज़ प्रारूप';
// / 'Spreadsheet Formats'
$Gui1Text15 = 'स्प्रेडशीट प्रारूप';
// / 'Presentation Formats'
$Gui1Text16 = 'प्रस्तुति प्रारूप';
// / 'Archive Formats'
$Gui1Text17 = 'संग्रह प्रारूप';
// / 'Can convert between archive formats & disk image formats.'
$Gui1Text18 = 'चुनिंदा संग्रह प्रारूपों और डिस्क छवि प्रारूपों के बीच परिवर्तित कर सकते हैं।';
// / 'Image Formats'
$Gui1Text19 = 'छवि प्रारूप';
// / 'Can convert pictures of documents to document formats.'
$Gui1Text20 = 'दस्तावेज़ों के चित्रों को दस्तावेज़ स्वरूपों में बदल सकते हैं.';
// / 'Supports resize & rotate.'
$Gui1Text21 = 'आकार बदलने और घुमाने का समर्थन करता है.';
// / '3D Model Formats'
$Gui1Text22 = '3डी मॉडल प्रारूप';
// / 'Drawing Formats'
$Gui1Text23 = 'आरेखण प्रारूप';
// / 'Can convert drawing files to image formats.'
$Gui1Text24 = 'आरेखण प्रारूपों को छवि प्रारूपों में परिवर्तित कर सकते हैं.';
// / 'OCR Support'
$Gui1Text25 = 'ओसीआर समर्थन';
// / 'OCR Operations support the following input formats...'
$Gui1Text26 = 'ओसीआर संचालन निम्नलिखित इनपुट स्वरूपों का समर्थन करता है...';
// / 'OCR Operations support the following output formats...'
$Gui1Text27 = 'ओसीआर संचालन निम्नलिखित आउटपुट स्वरूपों का समर्थन करता है...';
// / 'Select files by clicking, tapping, or dropping them into the box below.'
$Gui1Text28 = 'फ़ाइलों को क्लिक करके, टैप करके या उन्हें नीचे दिए बॉक्स में डालकर चुनें.';
// / 'Continue ...'
$Gui1Text29 = 'जारी रखें...';
// / 'Can convert stream formats to video formats.'
$Gui1Text30 = 'स्ट्रीम प्रारूपों को वीडियो प्रारूपों में कनवर्ट कर सकते हैं.';
// / 'Subtitle Formats'
$Gui1Text31 = 'उपशीर्षक प्रारूप';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'फ़ाइल रूपांतरण विकल्प';
// / 'Bulk File Options'
$Gui2Text2 = 'बल्क फ़ाइल विकल्प';
// / 'Scan All Files For Viruses'
$Gui2Text3 = 'वायरस के लिए सभी फाइलों को स्कैन करें';
// / 'Compress & Download All Files'
$Gui2Text4 = 'सभी फ़ाइलें कंप्रेस और डाउनलोड करें';
// / 'Download'
$Gui2Text5 = 'डाउनलोड';
// / 'Share'
$Gui2Text6 = 'साझा करें';
// / 'Close Share Options'
$Gui2Text7 = 'शेयर विकल्प बंद करें';
// / 'Virus Scan'
$Gui2Text8 = 'वायरस स्कैन';
// / 'Close Virus Scan Options'
$Gui2Text9 = 'वायरस स्कैन विकल्प बंद करें';
// / 'Archive'
$Gui2Text10 = 'आर्काइव';
// / 'Close Archive Options'
$Gui2Text11 = 'संग्रह विकल्प बंद करें';
// / 'OCR'
$Gui2Text12 = 'ओसीआर';
// / 'Close OCR Options'
$Gui2Text13 = 'ओसीआर विकल्प बंद करें';
// / 'Convert'
$Gui2Text14 = 'कन्वर्ट';
// / 'Close Convert Options'
$Gui2Text15 = 'कन्वर्ट विकल्प बंद करें';
// / 'Archive This File'
$Gui2Text16 = 'इस फ़ाइल को संग्रहीत करें';
// / 'Specify Filename: '
$Gui2Text17 = 'फ़ाइल का नाम निर्दिष्ट करें:';
// / 'Format'
$Gui2Text18 = 'प्रारूप';
// / 'Compress & Download'
$Gui2Text19 = 'संपीड़ित करें और डाउनलोड करें';
// / 'Scan with ClamAV: '
$Gui2Text20 = 'क्लैमाव के साथ स्कैन करें:';
// / 'Scan with ScanCore: '
$Gui2Text21 = 'स्कैनकोर के साथ स्कैन करें:';
// / 'Scan All'
$Gui2Text22 = 'सभी स्कैन करें';
// / 'Share This File'
$Gui2Text23 = 'इस फ़ाइल को साझा करें';
// / 'Link Status: '
$Gui2Text24 = 'लिंक स्थिति:';
// / 'Not Generated'
$Gui2Text25 = 'उत्पन्न नहीं हुआ';
// / 'Generated'
$Gui2Text26 = 'उत्पन्न';
// / 'Clipboard Status: '
$Gui2Text27 = 'क्लिपबोर्ड स्थिति:';
// / 'Copied'
$Gui2Text28 = 'कॉपी किया गया';
// / 'File Link: '
$Gui2Text29 = 'फ़ाइल लिंक:';
// / 'You have uploaded '.$FileCount.' valid file'.$FCPlural1.' to '.$ApplicationName.'.'
$Gui2Text30 = 'आपने अपलोड कर दिया है'.$FileCount.' मान्य फ़ाइल'.$FCPlural1.' से '.$ApplicationName.'.';
// / 'Your file'.$FCPlural2.' now ready to convert using the options below.'
$Gui2Text31 = 'आपकी फ़ाइलें अब नीचे दिए गए विकल्पों का उपयोग कर कनवर्ट करने के लिए तैयार हैं.';
// / 'Generate Link & Copy to Clipboard'
$Gui2Text32 = 'लिंक जेनरेट करें और क्लिपबोर्ड पर कॉपी करें';
// / 'Generate Link'
$Gui2Text33 = 'लिंक जेनरेट करें';
// / 'Scan This File For Viruses'
$Gui2Text34 = 'इस फ़ाइल को वायरस के लिए स्कैन करें';
// / 'Scan File With ScanCore'
$Gui2Text35 = 'स्कैनकोर के साथ फ़ाइल स्कैन करें';
// / 'Scan File With ClamAV'
$Gui2Text36 = 'क्लैमाव के साथ फ़ाइल स्कैन करें';
// / 'Scan File With ScanCore & ClamAV'
$Gui2Text37 = 'स्कैनकोर और क्लैमएवी के साथ फ़ाइल स्कैन करें';
// / 'Perform Optical Character Recognition On This File'
$Gui2Text38 = 'इस फ़ाइल पर ऑप्टिकल वर्ण पहचान करें';
// / 'Method'
$Gui2Text39 = 'विधि';
// / 'Simple'
$Gui2Text40 = 'सरल';
// / 'Advanced'
$Gui2Text41 = 'उन्नत';
// / 'Convert This Archive'
$Gui2Text42 = 'इस संग्रह को रूपांतरित करें';
// / 'Convert This Document'
$Gui2Text43 = 'इस दस्तावेज़ को रूपांतरित करें';
// / 'Convert This Spreadsheet'
$Gui2Text44 = 'इस स्प्रैडशीट को रूपांतरित करें';
// / 'Convert This Audio'
$Gui2Text45 = 'इस ऑडियो को कन्वर्ट करें';
// / 'Convert This Video'
$Gui2Text46 = 'इस वीडियो को कन्वर्ट करें';
// / 'Convert This Stream'
$Gui2Text47 = 'इस स्ट्रीम को कनवर्ट करें';
// / Convert This 3D Model'
$Gui2Text48 = 'इस 3डी मॉडल को बदलें';
// / 'Convert This Technical Drawing Or Vector File'
$Gui2Text49 = 'इस तकनीकी आरेखण या वेक्टर फ़ाइल को रूपांतरित करें';
// / 'Convert This Image'
$Gui2Text50 = 'इस छवि को बदलें';
// / 'Archive File'
$Gui2Text51 = 'पुरालेख फ़ाइल';
// / 'Convert Into Document'
$Gui2Text52 = 'दस्तावेज़ में बदलें';
// / 'Archive Files'
$Gui2Text53 = 'संग्रह फ़ाइलें';
// / 'Convert Document'
$Gui2Text54 = 'दस्तावेज़ बदलें';
// / 'Convert Spreadsheet'
$Gui2Text55 = 'स्प्रेडशीट बदलें';
// / 'Convert Presentation'
$Gui2Text56 = 'प्रस्तुतिकरण बदलें';
// / 'Convert Audio'
$Gui2Text57 = 'ऑडियो कन्वर्ट करें';
// / 'Convert Video'
$Gui2Text58 = 'वीडियो कन्वर्ट करें';
// / 'Convert Stream'
$Gui2Text59 = 'स्ट्रीम कनवर्ट करें';
// / 'Convert Model'
$Gui2Text60 = 'मॉडल बदलें';
// / 'Convert Drawing'
$Gui2Text61 = 'आरेखण रूपांतरित करें';
// / 'Convert Image'
$Gui2Text62 = 'छवि बदलें';
// / 'Width & Height'
$Gui2Text64 = 'चौड़ाई और ऊंचाई:';
// / 'Rotate: '
$Gui2Text65 = 'घुमाएँ:';
// / 'Bitrate: '
$Gui2Text66 = 'बिटरेट:';
// / 'Delete'
$Gui2Text67 = 'हटाएं';
// / 'Close Delete Options'
$Gui2Text68 = 'विकल्प हटाएं बंद करें';
// / 'Delete This File'
$Gui2Text69 = 'इस फ़ाइल को हटाएं';
// / 'Confirm Delete'
$Gui2Text70 = 'हटाने की पुष्टि करें';
// / 'Cannot convert this file! Try changing the name.'
$Gui2Text71 = 'इस फ़ाइल को परिवर्तित नहीं किया जा सकता! नाम बदलने का प्रयास करें।';
// / 'Cannot perform a virus scan on this file!'
$Gui2Text72 = 'इस फ़ाइल पर वायरस स्कैन नहीं कर सकता!';
// / 'File Link Copied to Clipboard!'
$Gui2Text73 = 'फ़ाइल लिंक क्लिपबोर्ड पर कॉपी किया गया!';
// / 'Operation Failed!'
$Gui2Text74 = 'ऑपरेशन विफल!';
// / Convert These Subtitles'
$Gui2Text75 = 'इन उपशीर्षकों को बदलें';
// / Convert Subtitles'
$Gui2Text76 = 'उपशीर्षक बदलें';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy'
$GuiFooterText1 = 'हमारी <a href=\''.$TOSURL.' target=\'_blank\' rel=\'noopener noreferrer\'>सेवा की शर्तें</a> और <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>गोपनीयता नीति';
// / -----------------------------------------------------------------------------------