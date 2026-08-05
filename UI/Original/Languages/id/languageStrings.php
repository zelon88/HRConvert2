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
// / This file contains the Indonesian (id) language strings.
// / Indonesian does not inflect nouns for number, so the plural helper strings are empty.
// / This file uses berkas for file, matching the Ubuntu & GNOME Indonesian localizations.
// / The loan word file is also widely understood if a less formal register is preferred.
// / This file must be saved as UTF-8 without a byte order mark.
// / A byte order mark emits three bytes before any output & will break header delivery.
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
$CoreError = 'ERROR!!! HRConvert2-2, Berkas ini tidak dapat memproses permintaan Anda. Silakan kirim berkas Anda ke convertCore.php.';
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die($CoreError);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Check for required core variables.
if (!isset($Font)) $Font = 'Arial';
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2';
if (!isset($ApplicationTitle)) $ApplicationTitle = 'Konversi Apa Saja!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI Related Logic.
// / Indonesian does not inflect nouns for number, so both plural helpers stay empty.
// / A noun following a numeral is never pluralized, so the sentences read correctly for any count.
if (!is_numeric($FileCount)) $FileCount = 'sejumlah';
$FCPlural1 = '';
$FCPlural2 = '';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Header Related Variables.
// / 'Click, Tap, or Drop files here to upload.'
$GuiHeaderText1 = 'Klik, ketuk, atau jatuhkan berkas di sini untuk mengunggah.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / 'Online File Converter, Extractor, Compressor'
$Gui1Text1 = 'Pengonversi, Pengekstrak, & Pemampat Berkas Daring';
// / $ApplicationName.' is based off the open-source web-app HRConvert2 by Zelon88 that converts files without tracking users across the net or infringing on your intellectual property.'
$Gui1Text2 = $ApplicationName.' dibangun berdasarkan aplikasi web sumber terbuka <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> oleh <a href=\'https://github.com/zelon88\'>Zelon88</a>, yang mengonversi berkas tanpa melacak pengguna di internet atau melanggar hak kekayaan intelektual Anda.';
// / 'More Info ...'
$Gui1Text3 = 'Selengkapnya ...';
// / 'Less Info ...'
$Gui1Text4 = 'Sembunyikan ...';
// / 'All user-supplied data is erased automatically, so you don\'t need to worry about forfeiting your personal information or property while using our services.'
$Gui1Text5 = 'Semua data yang dikirim pengguna dihapus secara otomatis, sehingga Anda tidak perlu khawatir kehilangan informasi pribadi atau kepemilikan Anda saat menggunakan layanan kami.';
// / 'Currently '.$ApplicationName.' supports '.$SupportedFormatCount.' different file formats, including documents, spreadsheets, images, media, 3D models, CAD drawings, vector files, archives, disk images, & more.'
$Gui1Text6 = 'Saat ini '.$ApplicationName.' mendukung '.$SupportedFormatCount.' format berkas yang berbeda, termasuk dokumen, lembar kerja, gambar, media, model 3D, gambar teknik CAD, berkas vektor, arsip, citra diska, & lainnya.';
// / 'View Supported Formats ...'
$Gui1Text7 = 'Lihat Format yang Didukung ...';
// / 'Hide Supported Formats ...'
$Gui1Text8 = 'Sembunyikan Format yang Didukung ...';
// / 'Supported Formats'
$Gui1Text9 = 'Format yang Didukung';
// / 'Audio Formats'
$Gui1Text10 = 'Format Audio';
// / 'Supports specific bitrate.'
$Gui1Text11 = 'Mendukung bitrate tertentu.';
// / 'Video Formats'
$Gui1Text12 = 'Format Video';
// / 'Stream Formats'
$Gui1Text13 = 'Format Strim';
// / 'Document Formats'
$Gui1Text14 = 'Format Dokumen';
// / 'Spreadsheet Formats'
$Gui1Text15 = 'Format Lembar Kerja';
// / 'Presentation Formats'
$Gui1Text16 = 'Format Presentasi';
// / 'Archive Formats'
$Gui1Text17 = 'Format Arsip';
// / 'Can convert between select archive formats & disk image formats.'
$Gui1Text18 = 'Dapat mengonversi antara sebagian format arsip & format citra diska.';
// / 'Image Formats'
$Gui1Text19 = 'Format Gambar';
// / 'Can convert pictures of documents to document formats.'
$Gui1Text20 = 'Dapat mengonversi foto dokumen menjadi format dokumen.';
// / 'Supports resize & rotate.'
$Gui1Text21 = 'Mendukung pengubahan ukuran & rotasi.';
// / '3D Model Formats'
$Gui1Text22 = 'Format Model 3D';
// / 'Drawing Formats'
$Gui1Text23 = 'Format Gambar Teknik';
// / 'Can convert drawing formats to image formats.'
$Gui1Text24 = 'Dapat mengonversi format gambar teknik menjadi format gambar.';
// / 'OCR Support'
$Gui1Text25 = 'Dukungan OCR';
// / 'OCR Operations support the following input formats...'
$Gui1Text26 = 'Operasi OCR mendukung format masukan berikut ...';
// / 'OCR Operations support the following output formats...'
$Gui1Text27 = 'Operasi OCR mendukung format keluaran berikut ...';
// / 'Select files by clicking, tapping, or dropping them into the box below.'
$Gui1Text28 = 'Pilih berkas dengan mengeklik, mengetuk, atau menjatuhkannya ke dalam kotak di bawah ini.';
// / 'Continue ...'
$Gui1Text29 = 'Lanjutkan ...';
// / 'Can convert stream formats to video formats.'
$Gui1Text30 = 'Dapat mengonversi format strim menjadi format video.';
// / 'Subtitle Formats'
$Gui1Text31 = 'Format Takarir';
// / 'OpenSCAD Formats'
$Gui1Text32 = 'Format OpenSCAD';
// / 'Renders OpenSCAD source into 3D model formats.'
$Gui1Text33 = 'Merender kode sumber OpenSCAD menjadi format model 3D.';
// / 'File references inside uploaded sources are removed unless the server allows resolving them.'
$Gui1Text34 = 'Rujukan berkas di dalam kode sumber yang diunggah akan dihapus, kecuali server mengizinkan penyelesaiannya.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'Opsi Konversi Berkas';
// / 'Bulk File Options'
$Gui2Text2 = 'Opsi Berkas Massal';
// / 'Scan All Files For Viruses'
$Gui2Text3 = 'Pindai Semua Berkas dari Virus';
// / 'Compress & Download All Files'
$Gui2Text4 = 'Mampatkan & Unduh Semua Berkas';
// / 'Download'
$Gui2Text5 = 'Unduh';
// / 'Share'
$Gui2Text6 = 'Bagikan';
// / 'Close Share Options'
$Gui2Text7 = 'Tutup Opsi Berbagi';
// / 'Virus Scan'
$Gui2Text8 = 'Pindai Virus';
// / 'Close Virus Scan Options'
$Gui2Text9 = 'Tutup Opsi Pindai Virus';
// / 'Archive'
$Gui2Text10 = 'Arsipkan';
// / 'Close Archive Options'
$Gui2Text11 = 'Tutup Opsi Arsip';
// / 'OCR'
$Gui2Text12 = 'OCR';
// / 'Close OCR Options'
$Gui2Text13 = 'Tutup Opsi OCR';
// / 'Convert'
$Gui2Text14 = 'Konversi';
// / 'Close Convert Options'
$Gui2Text15 = 'Tutup Opsi Konversi';
// / 'Archive This File'
$Gui2Text16 = 'Arsipkan Berkas Ini';
// / 'Specify Filename: '
$Gui2Text17 = 'Tentukan Nama Berkas: ';
// / 'Format'
$Gui2Text18 = 'Format';
// / 'Compress & Download'
$Gui2Text19 = 'Mampatkan & Unduh';
// / 'Scan with ClamAV: '
$Gui2Text20 = 'Pindai dengan ClamAV: ';
// / 'Scan with ScanCore: '
$Gui2Text21 = 'Pindai dengan ScanCore: ';
// / 'Scan All'
$Gui2Text22 = 'Pindai Semua';
// / 'Share This File'
$Gui2Text23 = 'Bagikan Berkas Ini';
// / 'Link Status: '
$Gui2Text24 = 'Status Tautan: ';
// / 'Not Generated'
$Gui2Text25 = 'Belum Dibuat';
// / 'Generated'
$Gui2Text26 = 'Sudah Dibuat';
// / 'Clipboard Status: '
$Gui2Text27 = 'Status Papan Klip: ';
// / 'Copied'
$Gui2Text28 = 'Tersalin';
// / 'File Link: '
$Gui2Text29 = 'Tautan Berkas: ';
// / 'You have uploaded '.$FileCount.' valid file'.$FCPlural1.' to '.$ApplicationName.'.'
$Gui2Text30 = 'Anda telah mengunggah '.$FileCount.' berkas yang valid ke '.$ApplicationName.'.'.$FCPlural1;
// / 'Your file'.$FCPlural2.' now ready to convert using the options below.'
$Gui2Text31 = 'Berkas Anda sekarang siap dikonversi menggunakan opsi di bawah ini.'.$FCPlural2;
// / 'Generate Link & Copy to Clipboard'
$Gui2Text32 = 'Buat Tautan & Salin ke Papan Klip';
// / 'Generate Link'
$Gui2Text33 = 'Buat Tautan';
// / 'Scan This File For Viruses'
$Gui2Text34 = 'Pindai Berkas Ini dari Virus';
// / 'Scan File With ScanCore'
$Gui2Text35 = 'Pindai Berkas dengan ScanCore';
// / 'Scan File With ClamAV'
$Gui2Text36 = 'Pindai Berkas dengan ClamAV';
// / 'Scan File With ScanCore & ClamAV'
$Gui2Text37 = 'Pindai Berkas dengan ScanCore & ClamAV';
// / 'Perform Optical Character Recognition On This File'
$Gui2Text38 = 'Lakukan Pengenalan Karakter Optik pada Berkas Ini';
// / 'Method'
$Gui2Text39 = 'Metode';
// / 'Simple'
$Gui2Text40 = 'Sederhana';
// / 'Advanced'
$Gui2Text41 = 'Lanjutan';
// / 'Convert This Archive'
$Gui2Text42 = 'Konversi Arsip Ini';
// / 'Convert This Document'
$Gui2Text43 = 'Konversi Dokumen Ini';
// / 'Convert This Spreadsheet'
$Gui2Text44 = 'Konversi Lembar Kerja Ini';
// / 'Convert This Audio'
$Gui2Text45 = 'Konversi Audio Ini';
// / 'Convert This Video'
$Gui2Text46 = 'Konversi Video Ini';
// / 'Convert This Stream'
$Gui2Text47 = 'Konversi Strim Ini';
// / 'Convert This 3D Model'
$Gui2Text48 = 'Konversi Model 3D Ini';
// / 'Convert This Technical Drawing Or Vector File'
$Gui2Text49 = 'Konversi Gambar Teknik atau Berkas Vektor Ini';
// / 'Convert This Image'
$Gui2Text50 = 'Konversi Gambar Ini';
// / 'Archive File'
$Gui2Text51 = 'Arsipkan Berkas';
// / 'Convert Into Document'
$Gui2Text52 = 'Konversi Menjadi Dokumen';
// / 'Archive Files'
$Gui2Text53 = 'Arsipkan Berkas';
// / 'Convert Document'
$Gui2Text54 = 'Konversi Dokumen';
// / 'Convert Spreadsheet'
$Gui2Text55 = 'Konversi Lembar Kerja';
// / 'Convert Presentation'
$Gui2Text56 = 'Konversi Presentasi';
// / 'Convert Audio'
$Gui2Text57 = 'Konversi Audio';
// / 'Convert Video'
$Gui2Text58 = 'Konversi Video';
// / 'Convert Stream'
$Gui2Text59 = 'Konversi Strim';
// / 'Convert Model'
$Gui2Text60 = 'Konversi Model';
// / 'Convert Drawing'
$Gui2Text61 = 'Konversi Gambar Teknik';
// / 'Convert Image'
$Gui2Text62 = 'Konversi Gambar';
// / NOTE. There is no $Gui2Text63 in any language pack. The gap exists in the English
// / original & is preserved here deliberately so every pack stays index compatible.
// / 'Width & Height: '
$Gui2Text64 = 'Lebar & Tinggi: ';
// / 'Rotate: '
$Gui2Text65 = 'Rotasi: ';
// / 'Bitrate: '
$Gui2Text66 = 'Bitrate: ';
// / 'Delete'
$Gui2Text67 = 'Hapus';
// / 'Close Delete Options'
$Gui2Text68 = 'Tutup Opsi Hapus';
// / 'Delete This File'
$Gui2Text69 = 'Hapus Berkas Ini';
// / 'Confirm Delete'
$Gui2Text70 = 'Konfirmasi Penghapusan';
// / 'Cannot convert this file! Try changing the name.'
$Gui2Text71 = 'Berkas ini tidak dapat dikonversi. Coba ubah namanya.';
// / 'Cannot perform a virus scan on this file!'
$Gui2Text72 = 'Pemindaian virus tidak dapat dilakukan pada berkas ini.';
// / 'File Link Copied to Clipboard!'
$Gui2Text73 = 'Tautan berkas disalin ke papan klip.';
// / 'Operation Failed!'
$Gui2Text74 = 'Operasi gagal.';
// / 'Convert These Subtitles'
$Gui2Text75 = 'Konversi Takarir Ini';
// / 'Convert Subtitles'
$Gui2Text76 = 'Konversi Takarir';
// / 'Convert This Presentation'
$Gui2Text77 = 'Konversi Presentasi Ini';
// / 'Convert This XPS File'
$Gui2Text78 = 'Konversi Berkas XPS Ini';
// / 'Render This OpenSCAD Model'
$Gui2Text79 = 'Render Model OpenSCAD Ini';
// / 'Render Model'
$Gui2Text80 = 'Render Model';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / The closing anchor tag for this string lives in footer.php & must NOT be repeated here.
// / 'Check out our Terms of Service and Privacy Policy'
$GuiFooterText1 = 'Lihat <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Ketentuan Layanan</a> dan <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Kebijakan Privasi';
// / -----------------------------------------------------------------------------------
