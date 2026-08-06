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
// / v3.5.6.
// / This file contains language specific GUI related text for performing file conversions.
// / This file contains the Turkish (tr) language strings.
// / Turkish does not pluralize a noun that follows a numeral, so the plural helpers are empty.
// / Turkish uses vowel harmony, so a suffix cannot be appended to a variable safely.
// / Every sentence here is written so no suffix ever attaches to interpolated content.
// / This file must be saved as UTF-8 without a byte order mark.
// / A byte order mark emits three bytes before any output & will break header delivery.
// / This file contains the dotted & dotless Turkish letters. Do not process it with a
// / case changing routine that is not Turkish locale aware.
// /
// / HARDWARE REQUIREMENTS ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / DEPENDENCY REQUIREMENTS ...
// / This application requires Debian Linux, Apache 2.4, PHP 8+, FFMPEG, Dia,
// / Mkisofs, 7zip, LibreOffice, Unoconv, libgxps-utils, Tesseract, Unzip, Rar,
// / Unrar, ClamAV, MeshLab, PopplerUtils, PDFTOTEXT, ImageMagick, xvfb-run,
// / OpenSCAD & Bubblewrap.
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
$CoreError = 'ERROR!!! HRConvert2-2, Bu dosya isteğinizi işleyemez. Lütfen dosyanızı bunun yerine convertCore.php adresine gönderin.';
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die($CoreError);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Check for required core variables.
if (!isset($Font)) $Font = 'Arial';
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2';
if (!isset($ApplicationTitle)) $ApplicationTitle = 'Her Şeyi Dönüştür!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI Related Logic.
// / Turkish does not pluralize a noun that follows a numeral, so both plural helpers stay empty.
// / Bes dosya is correct & bes dosyalar is not, whatever the count happens to be.
if (!is_numeric($FileCount)) $FileCount = 'belirsiz sayıda';
$FCPlural1 = '';
$FCPlural2 = '';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Header Related Variables.
// / 'Click, Tap, or Drop files here to upload.'
$GuiHeaderText1 = 'Yüklemek için dosyaları buraya tıklayın, dokunun veya sürükleyip bırakın.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / 'Online File Converter, Extractor, Compressor'
$Gui1Text1 = 'Çevrimiçi Dosya Dönüştürücü, Ayıklayıcı ve Sıkıştırıcı';
// / $ApplicationName.' is based off the open-source web-app HRConvert2 by Zelon88 that converts files without tracking users across the net or infringing on your intellectual property.'
// / The application name is followed by a comma rather than a suffix, because a Turkish
// / suffix must agree with the vowels of the word before it & that word is configurable.
$Gui1Text2 = $ApplicationName.', <a href=\'https://github.com/zelon88\'>Zelon88</a> tarafından geliştirilen açık kaynaklı web uygulaması <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> temel alınarak hazırlanmıştır. Kullanıcıları internette takip etmeden veya fikri mülkiyetinizi ihlal etmeden dosyaları dönüştürür.';
// / 'More Info ...'
$Gui1Text3 = 'Daha Fazla Bilgi ...';
// / 'Less Info ...'
$Gui1Text4 = 'Bilgiyi Gizle ...';
// / 'All user-supplied data is erased automatically, so you don\'t need to worry about forfeiting your personal information or property while using our services.'
$Gui1Text5 = 'Kullanıcı tarafından gönderilen tüm veriler otomatik olarak silinir. Bu nedenle hizmetlerimizi kullanırken kişisel bilgilerinizi veya mülkiyetinizi kaybetme endişesi taşımanıza gerek yoktur.';
// / 'Currently '.$ApplicationName.' supports '.$SupportedFormatCount.' different file formats, including documents, spreadsheets, images, media, 3D models, CAD drawings, vector files, archives, disk images, & more.'
$Gui1Text6 = 'Şu anda '.$ApplicationName.' uygulaması '.$SupportedFormatCount.' farklı dosya biçimini desteklemektedir. Bunlara belgeler, hesap tabloları, görüntüler, ortam dosyaları, 3B modeller, CAD çizimleri, vektör dosyaları, arşivler, disk kalıpları ve daha fazlası dahildir.';
// / 'View Supported Formats ...'
$Gui1Text7 = 'Desteklenen Biçimleri Görüntüle ...';
// / 'Hide Supported Formats ...'
$Gui1Text8 = 'Desteklenen Biçimleri Gizle ...';
// / 'Supported Formats'
$Gui1Text9 = 'Desteklenen Biçimler';
// / 'Audio Formats'
$Gui1Text10 = 'Ses Biçimleri';
// / 'Supports specific bitrate.'
$Gui1Text11 = 'Belirli bit hızını destekler.';
// / 'Video Formats'
$Gui1Text12 = 'Video Biçimleri';
// / 'Stream Formats'
$Gui1Text13 = 'Akış Biçimleri';
// / 'Document Formats'
$Gui1Text14 = 'Belge Biçimleri';
// / 'Spreadsheet Formats'
$Gui1Text15 = 'Hesap Tablosu Biçimleri';
// / 'Presentation Formats'
$Gui1Text16 = 'Sunum Biçimleri';
// / 'Archive Formats'
$Gui1Text17 = 'Arşiv Biçimleri';
// / 'Can convert between select archive formats & disk image formats.'
$Gui1Text18 = 'Belirli arşiv biçimleri ile disk kalıbı biçimleri arasında dönüştürme yapabilir.';
// / 'Image Formats'
$Gui1Text19 = 'Görüntü Biçimleri';
// / 'Can convert pictures of documents to document formats.'
$Gui1Text20 = 'Belge fotoğraflarını belge biçimlerine dönüştürebilir.';
// / 'Supports resize & rotate.'
$Gui1Text21 = 'Yeniden boyutlandırma ve döndürme desteklenir.';
// / '3D Model Formats'
$Gui1Text22 = '3B Model Biçimleri';
// / 'Drawing Formats'
$Gui1Text23 = 'Çizim Biçimleri';
// / 'Can convert drawing formats to image formats.'
$Gui1Text24 = 'Çizim biçimlerini görüntü biçimlerine dönüştürebilir.';
// / 'OCR Support'
$Gui1Text25 = 'OCR Desteği';
// / 'OCR Operations support the following input formats...'
$Gui1Text26 = 'OCR işlemleri aşağıdaki giriş biçimlerini destekler ...';
// / 'OCR Operations support the following output formats...'
$Gui1Text27 = 'OCR işlemleri aşağıdaki çıkış biçimlerini destekler ...';
// / 'Select files by clicking, tapping, or dropping them into the box below.'
$Gui1Text28 = 'Aşağıdaki kutuya tıklayarak, dokunarak veya dosyaları sürükleyip bırakarak seçim yapın.';
// / 'Continue ...'
$Gui1Text29 = 'Devam Et ...';
// / 'Can convert stream formats to video formats.'
$Gui1Text30 = 'Akış biçimlerini video biçimlerine dönüştürebilir.';
// / 'Subtitle Formats'
$Gui1Text31 = 'Altyazı Biçimleri';
// / 'OpenSCAD Formats'
$Gui1Text32 = 'OpenSCAD Biçimleri';
// / 'Renders OpenSCAD source into 3D model formats.'
$Gui1Text33 = 'OpenSCAD kaynak kodunu 3B model biçimlerine dönüştürür.';
// / 'File references inside uploaded sources are removed unless the server allows resolving them.'
$Gui1Text34 = 'Sunucu çözümlemeye izin vermediği sürece, yüklenen kaynak dosyalarındaki dosya referansları kaldırılır.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'Dosya Dönüştürme Seçenekleri';
// / 'Bulk File Options'
$Gui2Text2 = 'Toplu Dosya Seçenekleri';
// / 'Scan All Files For Viruses'
$Gui2Text3 = 'Tüm Dosyaları Virüse Karşı Tara';
// / 'Compress & Download All Files'
$Gui2Text4 = 'Tüm Dosyaları Sıkıştır ve İndir';
// / 'Download'
$Gui2Text5 = 'İndir';
// / 'Share'
$Gui2Text6 = 'Paylaş';
// / 'Close Share Options'
$Gui2Text7 = 'Paylaşım Seçeneklerini Kapat';
// / 'Virus Scan'
$Gui2Text8 = 'Virüs Taraması';
// / 'Close Virus Scan Options'
$Gui2Text9 = 'Virüs Tarama Seçeneklerini Kapat';
// / 'Archive'
$Gui2Text10 = 'Arşivle';
// / 'Close Archive Options'
$Gui2Text11 = 'Arşiv Seçeneklerini Kapat';
// / 'OCR'
$Gui2Text12 = 'OCR';
// / 'Close OCR Options'
$Gui2Text13 = 'OCR Seçeneklerini Kapat';
// / 'Convert'
$Gui2Text14 = 'Dönüştür';
// / 'Close Convert Options'
$Gui2Text15 = 'Dönüştürme Seçeneklerini Kapat';
// / 'Archive This File'
$Gui2Text16 = 'Bu Dosyayı Arşivle';
// / 'Specify Filename: '
$Gui2Text17 = 'Dosya Adı Belirtin: ';
// / 'Format'
$Gui2Text18 = 'Biçim';
// / 'Compress & Download'
$Gui2Text19 = 'Sıkıştır ve İndir';
// / 'Scan with ClamAV: '
$Gui2Text20 = 'ClamAV ile tara: ';
// / 'Scan with ScanCore: '
$Gui2Text21 = 'ScanCore ile tara: ';
// / 'Scan All'
$Gui2Text22 = 'Tümünü Tara';
// / 'Share This File'
$Gui2Text23 = 'Bu Dosyayı Paylaş';
// / 'Link Status: '
$Gui2Text24 = 'Bağlantı Durumu: ';
// / 'Not Generated'
$Gui2Text25 = 'Oluşturulmadı';
// / 'Generated'
$Gui2Text26 = 'Oluşturuldu';
// / 'Clipboard Status: '
$Gui2Text27 = 'Pano Durumu: ';
// / 'Copied'
$Gui2Text28 = 'Kopyalandı';
// / 'File Link: '
$Gui2Text29 = 'Dosya Bağlantısı: ';
// / 'You have uploaded '.$FileCount.' valid file'.$FCPlural1.' to '.$ApplicationName.'.'
// / The application name is placed before the verb so that no suffix has to attach to it.
$Gui2Text30 = $ApplicationName.' uygulamasına '.$FileCount.' geçerli dosya yüklediniz.'.$FCPlural1;
// / 'Your file'.$FCPlural2.' now ready to convert using the options below.'
$Gui2Text31 = 'Dosyalarınız artık aşağıdaki seçenekler kullanılarak dönüştürülmeye hazır.'.$FCPlural2;
// / 'Generate Link & Copy to Clipboard'
$Gui2Text32 = 'Bağlantı Oluştur ve Panoya Kopyala';
// / 'Generate Link'
$Gui2Text33 = 'Bağlantı Oluştur';
// / 'Scan This File For Viruses'
$Gui2Text34 = 'Bu Dosyayı Virüse Karşı Tara';
// / 'Scan File With ScanCore'
$Gui2Text35 = 'Dosyayı ScanCore ile Tara';
// / 'Scan File With ClamAV'
$Gui2Text36 = 'Dosyayı ClamAV ile Tara';
// / 'Scan File With ScanCore & ClamAV'
$Gui2Text37 = 'Dosyayı ScanCore ve ClamAV ile Tara';
// / 'Perform Optical Character Recognition On This File'
$Gui2Text38 = 'Bu Dosyada Optik Karakter Tanıma Uygula';
// / 'Method'
$Gui2Text39 = 'Yöntem';
// / 'Simple'
$Gui2Text40 = 'Basit';
// / 'Advanced'
$Gui2Text41 = 'Gelişmiş';
// / 'Convert This Archive'
$Gui2Text42 = 'Bu Arşivi Dönüştür';
// / 'Convert This Document'
$Gui2Text43 = 'Bu Belgeyi Dönüştür';
// / 'Convert This Spreadsheet'
$Gui2Text44 = 'Bu Hesap Tablosunu Dönüştür';
// / 'Convert This Audio'
$Gui2Text45 = 'Bu Ses Dosyasını Dönüştür';
// / 'Convert This Video'
$Gui2Text46 = 'Bu Videoyu Dönüştür';
// / 'Convert This Stream'
$Gui2Text47 = 'Bu Akışı Dönüştür';
// / 'Convert This 3D Model'
$Gui2Text48 = 'Bu 3B Modeli Dönüştür';
// / 'Convert This Technical Drawing Or Vector File'
$Gui2Text49 = 'Bu Teknik Çizimi veya Vektör Dosyasını Dönüştür';
// / 'Convert This Image'
$Gui2Text50 = 'Bu Görüntüyü Dönüştür';
// / 'Archive File'
$Gui2Text51 = 'Dosyayı Arşivle';
// / 'Convert Into Document'
$Gui2Text52 = 'Belgeye Dönüştür';
// / 'Archive Files'
$Gui2Text53 = 'Dosyaları Arşivle';
// / 'Convert Document'
$Gui2Text54 = 'Belgeyi Dönüştür';
// / 'Convert Spreadsheet'
$Gui2Text55 = 'Hesap Tablosunu Dönüştür';
// / 'Convert Presentation'
$Gui2Text56 = 'Sunumu Dönüştür';
// / 'Convert Audio'
$Gui2Text57 = 'Sesi Dönüştür';
// / 'Convert Video'
$Gui2Text58 = 'Videoyu Dönüştür';
// / 'Convert Stream'
$Gui2Text59 = 'Akışı Dönüştür';
// / 'Convert Model'
$Gui2Text60 = 'Modeli Dönüştür';
// / 'Convert Drawing'
$Gui2Text61 = 'Çizimi Dönüştür';
// / 'Convert Image'
$Gui2Text62 = 'Görüntüyü Dönüştür';
// / NOTE. There is no $Gui2Text63 in any language pack. The gap exists in the English
// / original & is preserved here deliberately so every pack stays index compatible.
// / 'Width & Height: '
$Gui2Text64 = 'Genişlik ve Yükseklik: ';
// / 'Rotate: '
$Gui2Text65 = 'Döndür: ';
// / 'Bitrate: '
$Gui2Text66 = 'Bit Hızı: ';
// / 'Delete'
$Gui2Text67 = 'Sil';
// / 'Close Delete Options'
$Gui2Text68 = 'Silme Seçeneklerini Kapat';
// / 'Delete This File'
$Gui2Text69 = 'Bu Dosyayı Sil';
// / 'Confirm Delete'
$Gui2Text70 = 'Silmeyi Onayla';
// / 'Cannot convert this file! Try changing the name.'
$Gui2Text71 = 'Bu dosya dönüştürülemiyor. Adını değiştirmeyi deneyin.';
// / 'Cannot perform a virus scan on this file!'
$Gui2Text72 = 'Bu dosyada virüs taraması yapılamıyor.';
// / 'File Link Copied to Clipboard!'
$Gui2Text73 = 'Dosya bağlantısı panoya kopyalandı.';
// / 'Operation Failed!'
$Gui2Text74 = 'İşlem başarısız oldu.';
// / 'Convert These Subtitles'
$Gui2Text75 = 'Bu Altyazıları Dönüştür';
// / 'Convert Subtitles'
$Gui2Text76 = 'Altyazıları Dönüştür';
// / 'Convert This Presentation'
$Gui2Text77 = 'Bu Sunumu Dönüştür';
// / 'Convert This XPS File'
$Gui2Text78 = 'Bu XPS Dosyasını Dönüştür';
// / 'Render This OpenSCAD Model'
$Gui2Text79 = 'Bu OpenSCAD Modelini İşle';
// / 'Render Model'
$Gui2Text80 = 'Modeli İşle';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - HRC2-Functions.js Related Variables.
// / These strings are used by the client side javascript library.
// / That file is static javascript & cannot read a PHP variable, so every string it needs
// / is passed to it as an argument by the PHP that calls it.
// / 'Your browser does not support copying to the clipboard!'
$GuiFunctionsText1 = 'Tarayıcınız panoya kopyalamayı desteklemiyor.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / The closing anchor tag for this string lives in footer.php & must NOT be repeated here.
// / 'Check out our Terms of Service and Privacy Policy'
$GuiFooterText1 = '<a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Hizmet Şartlarımıza</a> ve <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Gizlilik Politikamıza göz atın';
// / -----------------------------------------------------------------------------------