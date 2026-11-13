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
// / Set GUI - UI Selector Bar Related Variables.
// / These strings appear in the selector bar, which is present on both GUI1 & GUI2.
// / 'Language'
$GuiSelectorText1 = 'Bahasa';
// / 'Color'
$GuiSelectorText2 = 'Warna';
// / 'Interface'
$GuiSelectorText3 = 'Antarmuka';
// / 'Display language, color and interface options'
$GuiSelectorText4 = 'Tampilkan opsi bahasa, warna, dan antarmuka';
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
// / 'Delete every uploaded file & start a new session?'
// / Shown inside the start over panel on the upload page. That panel is only rendered
// / when the session already holds at least one file, so this is never shown to a first
// / time visitor who has nothing to lose.
$Gui1Text35 = 'Hapus semua berkas yang diunggah dan mulai sesi baru?';
// / 'Start Over'
// / Labels the control that opens the panel & the button inside it that confirms.
$Gui1Text36 = 'Mulai Ulang';
// / 'Refresh'
// / Alternate text for the refresh control on the upload page. That control is a glyph
// / with no text of its own, so this is the only description a screen reader has & the
// / only thing shown when a browser cannot render the glyph.
$Gui1Text37 = 'Segarkan';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'Opsi Konversi Berkas';
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
// / 'Convert This E-Book'
$Gui2Text81 = 'Konversi E-Book Ini';
// / 'Convert E-Book'
$Gui2Text82 = 'Konversi E-Book';
// / 'Playlist source URL, only needed if the playlist uses relative paths'
$Gui2Text83 = 'URL sumber playlist, hanya diperlukan jika playlist memakai jalur relatif';
// / 'Download A File From A URL'
$Gui2Text84 = 'Unduh berkas dari URL';
// / 'Fetch'
$Gui2Text85 = 'Ambil';
// / 'Identify'
$Gui2Text86 = 'Identifikasi';
// / 'Close Identify Options'
$Gui2Text87 = 'Tutup Opsi Identifikasi';
// / 'Identify This File'
$Gui2Text88 = 'Identifikasi Berkas Ini';
// / 'Size: '
$Gui2Text89 = 'Ukuran: ';
// / 'Modified: '
$Gui2Text90 = 'Diubah: ';
// / 'Type: '
$Gui2Text91 = 'Jenis: ';
// / 'MIME Type: '
$Gui2Text92 = 'Jenis MIME: ';
// / 'Character Set: '
$Gui2Text93 = 'Set Karakter: ';
// / 'Extension Matches Content: '
$Gui2Text94 = 'Ekstensi Sesuai Isi: ';
// / 'Details: '
$Gui2Text95 = 'Rincian: ';
// / 'Notes: '
$Gui2Text96 = 'Catatan: ';
// / 'Save report as file'
$Gui2Text97 = 'Simpan laporan sebagai berkas';
// / 'Yes'
$Gui2Text100 = 'Ya';
// / 'No'
$Gui2Text101 = 'Tidak';
// / 'Unknown'
$Gui2Text102 = 'Tidak diketahui';
// / 'Cannot identify this file!'
$Gui2Text103 = 'Berkas ini tidak dapat diidentifikasi.';
// / 'Sort By: '
$Gui2Text104 = 'Urutkan Berdasarkan: ';
// / 'Name'
$Gui2Text105 = 'Nama';
// / 'Size'
$Gui2Text106 = 'Ukuran';
// / 'Date'
$Gui2Text107 = 'Tanggal';
// / 'Type'
$Gui2Text108 = 'Jenis';
// / The words in braces, such as {fit} & {count}, are filled in by the page. Each translation keeps every one of them.
// / 'Select'
$Gui2Text109 = 'Pilih';
// / 'Select All'
$Gui2Text110 = 'Pilih Semua';
// / 'Select None'
$Gui2Text111 = 'Batalkan Semua Pilihan';
// / 'Select By Type'
$Gui2Text112 = 'Pilih Berdasarkan Jenis';
// / 'Selected: '
$Gui2Text113 = 'Dipilih: ';
// / 'Documents'
$Gui2Text114 = 'Dokumen';
// / 'Spreadsheets'
$Gui2Text115 = 'Lembar Kerja';
// / 'Presentations'
$Gui2Text116 = 'Presentasi';
// / 'Images'
$Gui2Text117 = 'Gambar';
// / 'Audio'
$Gui2Text118 = 'Audio';
// / 'Video'
$Gui2Text119 = 'Video';
// / 'Archives'
$Gui2Text120 = 'Arsip';
// / 'E-Books'
$Gui2Text121 = 'E-Book';
// / '3D Models'
$Gui2Text122 = 'Model 3D';
// / 'Drawings'
$Gui2Text123 = 'Gambar Teknik';
// / 'Vector Images'
$Gui2Text124 = 'Gambar Vektor';
// / 'Subtitles'
$Gui2Text125 = 'Takarir';
// / 'OpenSCAD Files'
$Gui2Text126 = 'Berkas OpenSCAD';
// / 'Streams'
$Gui2Text127 = 'Strim';
// / 'Workflows'
$Gui2Text128 = 'Alur Kerja';
// / 'Files'
$Gui2Text129 = 'Berkas';
// / 'Select files in the list below.'
$Gui2Text130 = 'Pilih berkas pada daftar di bawah.';
// / 'Select By Name: '
$Gui2Text131 = 'Pilih Berdasarkan Nama: ';
// / 'Use * and ? as wildcards, or write a regular expression between slashes.'
$Gui2Text132 = 'Gunakan * dan ? sebagai karakter pengganti, atau tulis ekspresi reguler di antara garis miring.';
// / 'Workflow'
$Gui2Text133 = 'Alur Kerja';
// / 'Choose a workflow'
$Gui2Text134 = 'Pilih alur kerja';
// / 'Run'
$Gui2Text135 = 'Jalankan';
// / 'Stop'
$Gui2Text136 = 'Hentikan';
// / 'Selected files that can be used: {fit} of {count}.'
$Gui2Text137 = 'Berkas terpilih yang dapat digunakan: {fit} dari {count}.';
// / 'Step {step} of {steps}: {operation}'
$Gui2Text138 = 'Langkah {step} dari {steps}: {operation}';
// / 'File {done} of {total}'
$Gui2Text139 = 'Berkas {done} dari {total}';
// / 'Waiting for the server.'
$Gui2Text140 = 'Menunggu server.';
// / 'Starting.'
$Gui2Text141 = 'Memulai.';
// / 'Finished.'
$Gui2Text142 = 'Selesai.';
// / 'Stopping.'
$Gui2Text143 = 'Menghentikan.';
// / 'Stopped.'
$Gui2Text144 = 'Dihentikan.';
// / 'Files made: '
$Gui2Text145 = 'Berkas yang dibuat: ';
// / 'Workflows are not available on this server.'
$Gui2Text146 = 'Alur kerja tidak tersedia di server ini.';
// / 'No workflows are installed on this server.'
$Gui2Text147 = 'Tidak ada alur kerja yang terpasang di server ini.';
// / 'That workflow is no longer available.'
$Gui2Text148 = 'Alur kerja itu sudah tidak tersedia.';
// / 'None of the selected files can be used by this workflow.'
$Gui2Text149 = 'Tidak satu pun berkas terpilih dapat digunakan oleh alur kerja ini.';
// / 'Too many files are selected for one workflow.'
$Gui2Text150 = 'Terlalu banyak berkas dipilih untuk satu alur kerja.';
// / 'The server is busy. Try again in a few minutes.'
$Gui2Text151 = 'Server sedang sibuk. Coba lagi dalam beberapa menit.';
// / 'A workflow is already running in this session.'
$Gui2Text152 = 'Sebuah alur kerja sudah berjalan di sesi ini.';
// / 'The workflow could not be started.'
$Gui2Text153 = 'Alur kerja tidak dapat dimulai.';
// / 'A step of the workflow failed.'
$Gui2Text154 = 'Sebuah langkah alur kerja gagal.';
// / 'A virus was found, so the workflow stopped.'
$Gui2Text155 = 'Virus ditemukan, jadi alur kerja dihentikan.';
// / 'The workflow ran out of time.'
$Gui2Text156 = 'Waktu alur kerja habis.';
// / 'The workflow made too many files.'
$Gui2Text157 = 'Alur kerja membuat terlalu banyak berkas.';
// / 'The server was too busy to finish the workflow.'
$Gui2Text158 = 'Server terlalu sibuk untuk menyelesaikan alur kerja.';
// / 'An operation this workflow needs is not available.'
$Gui2Text159 = 'Operasi yang dibutuhkan alur kerja ini tidak tersedia.';
// / 'The workflow stopped unexpectedly.'
$Gui2Text160 = 'Alur kerja berhenti secara tak terduga.';
// / The words in braces, such as {step} & {kind}, are filled in by the page. Each translation keeps every one of them.
// / 'Pipelines'
$Gui2Text161 = 'Pipeline';
// / 'Select a pipeline above...'
$Gui2Text162 = 'Pilih pipeline di atas...';
// / 'Select Pipeline...'
$Gui2Text163 = 'Pilih Pipeline...';
// / 'Select Files...'
$Gui2Text164 = 'Pilih Berkas...';
// / 'Add a step'
$Gui2Text165 = 'Tambah langkah';
// / 'Options'
$Gui2Text166 = 'Opsi';
// / 'Back'
$Gui2Text167 = 'Kembali';
// / 'Take: '
$Gui2Text168 = 'Ambil: ';
// / 'the ticked files'
$Gui2Text169 = 'berkas yang dicentang';
// / 'the files step {step} made'
$Gui2Text170 = 'berkas yang dibuat langkah {step}';
// / 'each file on its own'
$Gui2Text171 = 'setiap berkas secara terpisah';
// / 'all files together'
$Gui2Text172 = 'semua berkas sekaligus';
// / 'Only these kinds: '
$Gui2Text173 = 'Hanya jenis ini: ';
// / 'Only these extensions: '
$Gui2Text174 = 'Hanya ekstensi ini: ';
// / 'Smallest size: '
$Gui2Text175 = 'Ukuran terkecil: ';
// / 'Largest size: '
$Gui2Text176 = 'Ukuran terbesar: ';
// / 'In the order they arrive'
$Gui2Text177 = 'Sesuai urutan kedatangan';
// / 'all files'
$Gui2Text178 = 'semua berkas';
// / 'all {kind}'
$Gui2Text179 = 'semua {kind}';
// / 'Width: '
$Gui2Text180 = 'Lebar: ';
// / 'Height: '
$Gui2Text181 = 'Tinggi: ';
// / 'My workflow'
$Gui2Text182 = 'Alur kerja saya';
// / 'New'
$Gui2Text183 = 'Baru';
// / 'Import'
$Gui2Text184 = 'Impor';
// / 'Export'
$Gui2Text185 = 'Ekspor';
// / 'This workflow is not one this server can run.'
$Gui2Text186 = 'Server ini tidak dapat menjalankan alur kerja ini.';
// / 'The file could not be read.'
$Gui2Text187 = 'Berkas tidak dapat dibaca.';
// / 'The workflow builder is not available on this server.'
$Gui2Text188 = 'Penyusun alur kerja tidak tersedia di server ini.';
// / 'Status'
$Gui2Text189 = 'Status';
// / 'A workflow is running.'
$Gui2Text190 = 'Sebuah alur kerja sedang berjalan.';
// / 'No workflow is running.'
$Gui2Text191 = 'Tidak ada alur kerja yang berjalan.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - HRC2-Functions.js Related Variables.
// / These strings are used by the client side javascript library.
// / That file is static javascript & cannot read a PHP variable, so every string it needs
// / is passed to it as an argument by the PHP that calls it.
// / 'Your browser does not support copying to the clipboard!'
$GuiFunctionsText1 = 'Peramban Anda tidak mendukung penyalinan ke papan klip.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / The closing anchor tag for this string lives in footer.php & must NOT be repeated here.
// / 'Check out our Terms of Service and Privacy Policy'
$GuiFooterText1 = 'Lihat <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Ketentuan Layanan</a> dan <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Kebijakan Privasi';
// / -----------------------------------------------------------------------------------