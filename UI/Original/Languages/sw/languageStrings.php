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
$GUIDirection = 'ltr';
// / Set the side of the page to float elements to.
$GUIAlignment = 'left';
// / Define an error message to display for if the core has not been loaded.
$CoreError = 'HITILAFU!!! HRConvert2-2, Faili hili haliwezi kushughulikia ombi lako! Tafadhali wasilisha faili lako kwa convertCore.php badala yake!';
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die($CoreError);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Check for required core variables.
if (!isset($Font)) $Font = 'Arial';
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = 'Badili Kitu Chochote!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI Related Logic.
if (!is_numeric($FileCount)) $FileCount = 'idadi isiyojulikana ya';
$FCPlural1 = ' yote';
$FCPlural2 = ' yenu sasa yako';
if ($FileCount == 1) {
  $FCPlural1 = '';
  $FCPlural2 = ' lako sasa liko'; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Header Related Variables.
// / 'Click, Tap, or Drop files here to upload.'
$GuiHeaderText1 = 'Bofya, Gusa, au Angusha faili hapa ili kupakia.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / Online File Converter, Extractor, Compressor'
$Gui1Text1 = 'Kigeuzi, Kichujaji, na Kikandamizaji cha Faili Mtandaoni';
// / $ApplicationName.' is based off the open-source web-app <a href=\'https://github.com\'>HRConvert2</a> by <a href=\'https://github.com\'>Zelon88</a> that converts files without tracking users across the net or infringing on your intellectual property.'
$Gui1Text2 = $ApplicationName.' inatokana na programu ya wavuti ya chanzo huru <a href=\'https://github.com\'>HRConvert2</a> iliyoundwa na <a href=\'https://github.com\'>Zelon88</a> ambayo hubadilisha faili bila kufuatilia watumiaji kwenye mtandao au kukiuka hakimiliki yako.';
// / 'More Info ...'
$Gui1Text3 = 'Maelezo Zaidi ...';
// / 'Less Info ...'
$Gui1Text4 = 'Maelezo Machache ...';
// / 'All user-supplied data is erased automatically, so you don\'t need to worry about forfeiting your personal information or property while using our services.'
$Gui1Text5 = 'Data zote zinazotolewa na mtumiaji hufutwa kiotomatiki, kwa hivyo huna haja ya kuwa na wasiwasi kuhusu kupoteza taarifa zako za kibinafsi au mali unapotumia huduma zetu.';
// / 'Currently '.$ApplicationName.' supports '.$SupportedFormatCount.' different file formats, including documents, spreadsheets, images, media, 3D models, CAD drawings, vector files, archives, disk images, & more.'
$Gui1Text6 = 'Kwa sasa '.$ApplicationName.' inasaidia miundo ya faili tofauti '.$SupportedFormatCount.', ikijumuisha nyaraka, lahajakazi, picha, vyombo vya habari, mifano ya 3D, michoro ya CAD, faili za vekta, kumbukumbu, picha za diski, na zaidi.';
// / 'View Supported Formats ...'
$Gui1Text7 = 'Angalia Miundo Inayoungwa Mkono ...';
// / 'Hide Supported Formats ...'
$Gui1Text8 = 'Ficha Miundo Inayoungwa Mkono ...';
// / 'Supported Formats'
$Gui1Text9 = 'Miundo Inayoungwa Mkono';
// / 'Audio Formats'
$Gui1Text10 = 'Miundo ya Sauti';
// / 'Supports specific bitrate.'
$Gui1Text11 = 'Inasaidia viwango maalum vya bitrate.';
// / 'Video Formats'
$Gui1Text12 = 'Miundo ya Video';
// / 'Stream Formats'
$Gui1Text13 = 'Miundo ya Kutiririsha';
// / 'Document Formats'
$Gui1Text14 = 'Miundo ya Nyaraka';
// / 'Spreadsheet Formats'
$Gui1Text15 = 'Miundo ya Lahajakazi';
// / 'Presentation Formats'
$Gui1Text16 = 'Miundo ya Wasilisho';
// / 'Archive Formats'
$Gui1Text17 = 'Miundo ya Kumbukumbu';
// / 'Can convert between archive formats & disk image formats.'
$Gui1Text18 = 'Inaweza kubadilisha kati ya miundo roshani ya kumbukumbu na miundo ya picha za diski.';
// / 'Image Formats'
$Gui1Text19 = 'Miundo ya Picha';
// / 'Can convert pictures of documents to document formats.'
$Gui1Text20 = 'Inaweza kubadilisha picha za nyaraka kuwa miundo ya nyaraka.';
// / 'Supports resize & rotate.'
$Gui1Text21 = 'Inasaidia kubadilisha ukubwa na kuzungusha.';
// / '3D Model Formats'
$Gui1Text22 = 'Miundo ya Mifano ya 3D';
// / 'Drawing Formats'
$Gui1Text23 = 'Miundo ya Michoro';
// / 'Can convert drawing files to image formats.'
$Gui1Text24 = 'Inaweza kubadilisha miundo ya michoro kuwa miundo ya picha.';
// / 'OCR Support'
$Gui1Text25 = 'Usaidizi wa OCR';
// / 'OCR Operations support the following input formats...'
$Gui1Text26 = 'Uendeshaji wa OCR unasaidia miundo ifuatayo ya kuingiza...';
// / 'OCR Operations support the following output formats...'
$Gui1Text27 = 'Uendeshaji wa OCR unasaidia miundo ifuatayo ya kutoa...';
// / 'Select files by clicking, tapping, or dropping them into the box below.'
$Gui1Text28 = 'Chagua faili kwa kubofya, kugusa, au kuziangusha kwenye kisanduku hapa chini.';
// / 'Continue ...'
$Gui1Text29 = 'Endelea ...';
// / 'Can convert stream formats to video formats.'
$Gui1Text30 = 'Inaweza kubadilisha miundo ya kutiririsha kuwa miundo ya video.';
// / 'Subtitle Formats'
$Gui1Text31 = 'Miundo ya Manukuu';
// / 'OpenSCAD Formats'
$Gui1Text32 = 'Miundo ya OpenSCAD';
// / 'Renders OpenSCAD source into 3D model formats.'
$Gui1Text33 = 'Inatoa chanzo cha OpenSCAD kuwa miundo ya mfano wa 3D.';
// / 'File references inside uploaded sources are removed unless the server allows resolving them.'
$Gui1Text34 = 'Marejeleo ya faili ndani ya vyanzo vilivyopakuliwa huondolewa isipokuwa seva inaruhusu kuzitatua.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'Chaguzi za Kubadilisha Faili';
// / 'Bulk File Options'
$Gui2Text2 = 'Chaguzi za Faili Nyingi';
// / 'Scan All Files For Viruses'
$Gui2Text3 = 'Changanua Faili Zote kwa Ajili ya Virusi';
// / 'Compress & Download All Files'
$Gui2Text4 = 'Kandamiza & Pakua Faili Zote';
// / 'Download'
$Gui2Text5 = 'Pakua';
// / 'Share'
$Gui2Text6 = 'Shiriki';
// / 'Close Share Options'
$Gui2Text7 = 'Funga Chaguzi za Kushiriki';
// / 'Virus Scan'
$Gui2Text8 = 'Uchanganuzi wa Virusi';
// / 'Close Virus Scan Options'
$Gui2Text9 = 'Funga Chaguzi za Uchanganuzi wa Virusi';
// / 'Archive'
$Gui2Text10 = 'Hifadhi Kumbukumbu';
// / 'Close Archive Options'
$Gui2Text11 = 'Funga Chaguzi za Hifadhi ya Kumbukumbu';
// / 'OCR'
$Gui2Text12 = 'OCR';
// / 'Close OCR Options'
$Gui2Text13 = 'Funga Chaguzi za OCR';
// / 'Convert'
$Gui2Text14 = 'Badilisha';
// / 'Close Convert Options'
$Gui2Text15 = 'Funga Chaguzi za Kubadilisha';
// / 'Archive This File'
$Gui2Text16 = 'Hifadhi Faili Hili kwenye Kumbukumbu';
// / 'Specify Filename: '
$Gui2Text17 = 'Taja Jina la Faili: ';
// / 'Format'
$Gui2Text18 = 'Muundo';
// / 'Compress & Download'
$Gui2Text19 = 'Kandamiza & Pakua';
// / 'Scan with ClamAV: '
$Gui2Text20 = 'Changanua kwa ClamAV: ';
// / 'Scan with ScanCore: '
$Gui2Text21 = 'Changanua kwa ScanCore: ';
// / 'Scan All'
$Gui2Text22 = 'Changanua Zote';
// / 'Share This File'
$Gui2Text23 = 'Shiriki Faili Hili';
// / 'Link Status: '
$Gui2Text24 = 'Hali ya Kiungo: ';
// / 'Not Generated'
$Gui2Text25 = 'Haijaundwa';
// / 'Generated'
$Gui2Text26 = 'Imeundwa';
// / 'Clipboard Status: '
$Gui2Text27 = 'Hali ya Ubao wa Nakala: ';
// / 'Copied'
$Gui2Text28 = 'Imenakiliwa';
// / 'File Link: '
$Gui2Text29 = 'Kiungo cha Faili: ';
// / 'You have uploaded '.$FileCount.' valid file'.$FCPlural1.' to '.$ApplicationName.'.'
$Gui2Text30 = 'Umepakia '.$FileCount.' faili halali'.$FCPlural1.' kwenye '.$ApplicationName.'.';
// / 'Your file'.$FCPlural2.' now ready to convert using the options below.'
$Gui2Text31 = 'Faili'.$FCPlural2.' tayari kubadilishwa kwa kutumia chaguzi hapa chini.';
// / 'Generate Link & Copy to Clipboard'
$Gui2Text32 = 'Unda Kiungo & Nakili kwenye Ubao wa Nakala';
// / 'Generate Link'
$Gui2Text33 = 'Unda Kiungo';
// / 'Scan This File For Viruses'
$Gui2Text34 = 'Changanua Faili Hili kwa Ajili ya Virusi';
// / 'Scan File With ScanCore'
$Gui2Text35 = 'Changanua Faili kwa ScanCore';
// / 'Scan File With ClamAV'
$Gui2Text36 = 'Changanua Faili kwa ClamAV';
// / 'Scan File With ScanCore & ClamAV'
$Gui2Text37 = 'Changanua Faili kwa ScanCore & ClamAV';
// / 'Perform Optical Character Recognition On This File'
$Gui2Text38 = 'Fanya Utambuzi wa herufi kwa Njia ya Macho Kwenye Faili Hili';
// / 'Method'
$Gui2Text39 = 'Njia';
// / 'Simple'
$Gui2Text40 = 'Rahisi';
// / 'Advanced'
$Gui2Text41 = 'Iliyobobea';
// / 'Convert This Archive'
$Gui2Text42 = 'Badilisha Kumbukumbu Hii';
// / 'Convert This Document'
$Gui2Text43 = 'Badilisha Nyaraka Hii';
// / 'Convert This Spreadsheet'
$Gui2Text44 = 'Badilisha Lahajakazi Hii';
// / 'Convert This Audio'
$Gui2Text45 = 'Badilisha Sauti Hii';
// / 'Convert This Video'
$Gui2Text46 = 'Badilisha Video Hii';
// / 'Convert This Stream'
$Gui2Text47 = 'Badilisha Mtiririko Huu';
// / Convert This 3D Model'
$Gui2Text48 = 'Badilisha Mfano Huu wa 3D';
// / 'Convert This Technical Drawing Or Vector File'
$Gui2Text49 = 'Badilisha Mchoro Huu wa Kiufundi au Faili ya Vekta';
// / 'Convert This Image'
$Gui2Text50 = 'Badilisha Picha Hii';
// / 'Archive File'
$Gui2Text51 = 'Hifadhi Faili kwenye Kumbukumbu';
// / 'Convert Into Document'
$Gui2Text52 = 'Badilisha Kuwa Nyaraka';
// / 'Archive Files'
$Gui2Text53 = 'Hifadhi Faili kwenye Kumbukumbu';
// / 'Convert Document'
$Gui2Text54 = 'Badilisha Nyaraka';
// / 'Convert Spreadsheet'
$Gui2Text55 = 'Badilisha Lahajakazi';
// / 'Convert Presentation'
$Gui2Text56 = 'Badilisha Wasilisho';
// / 'Convert Audio'
$Gui2Text57 = 'Badilisha Sauti';
// / 'Convert Video'
$Gui2Text58 = 'Badilisha Video';
// / 'Convert Stream'
$Gui2Text59 = 'Badilisha Mtiririko';
// / 'Convert Model'
$Gui2Text60 = 'Badilisha Mfano';
// / 'Convert Drawing'
$Gui2Text61 = 'Badilisha Mchoro';
// / 'Convert Image'
$Gui2Text62 = 'Badilisha Picha';
// / 'Width & Height'
$Gui2Text64 = 'Upana & Urefu: ';
// / 'Rotate: '
$Gui2Text65 = 'Zungusha: ';
// / 'Bitrate: '
$Gui2Text66 = 'Bitrate: ';
// / 'Delete'
$Gui2Text67 = 'Futa';
// / 'Close Delete Options'
$Gui2Text68 = 'Funga Chaguzi za Kufuta';
// / 'Delete This File'
$Gui2Text69 = 'Futa Faili Hili';
// / 'Confirm Delete'
$Gui2Text70 = 'Thibitisha Kufuta';
// / 'Cannot convert this file! Try changing the name.'
$Gui2Text71 = 'Haiwezi kubadilisha faili hili! Jaribu kubadilisha jina.';
// / 'Cannot perform a virus scan on this file!'
$Gui2Text72 = 'Haiwezi kufanya uchanganuzi wa virusi kwenye faili hili!';
// / 'File Link Copied to Clipboard!'
$Gui2Text73 = 'Kiungo cha Faili Kimenakiliwa kwenye Ubao wa Nakala!';
// / 'Operation Failed!'
$Gui2Text74 = 'Uendeshaji Umeshindwa!';
// / 'Convert These Subtitles'
$Gui2Text75 = 'Badilisha Manukuu Haya';
// / 'Convert Subtitles'
$Gui2Text76 = 'Badilisha Manukuu';
// / 'Convert This Presentation'
$Gui2Text77 = 'Badilisha Wasilisho Hili';
// / 'Convert This XPS File'
$Gui2Text78 = 'Badilisha Faili Hili la XPS';
// / 'Render This OpenSCAD Model'
$Gui2Text79 = 'Toa Mfano Huu wa OpenSCAD';
// / 'Render Model'
$Gui2Text80 = 'Toa Mfano';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - HRC2-Functions.js Related Variables.
// / These strings are used by the client side javascript library.
// / That file is static javascript & cannot read a PHP variable, so every string it needs
// / is passed to it as an argument by the PHP that calls it.
// / 'Your browser does not support copying to the clipboard!'
$GuiFunctionsText1 = 'Kivinjari chako hakiauni unakili kwenye ubao wa nakala!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy'
$GuiFooterText1 = 'Angalia <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Masharti yetu ya Huduma</a> na <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Sera ya Faragha';
// / -----------------------------------------------------------------------------------
