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
// / v3.7.4.
// / This file contains language specific GUI related text for performing file conversions.
// / This file contains the Japanese (ja) language strings.
// / Japanese does not inflect nouns for number, so the plural helper strings are empty.
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
// / The version of this language pack for compatibility checking.
// / Compatibility check takes place in convertCore.php, buildGui() function.
$LanguageVersion = 'v3.7.4';
$LanguageVersion = ltrim($LanguageVersion, 'vV');
// / Set the reading direction for text on the page.
$GUIDirection = 'ltr';
// / Set the side of the page to float elements to.
$GUIAlignment = 'left';
// / Define an error message to display for if the core has not been loaded.
$CoreError = 'ERROR!!! HRConvert2-2, このファイルはリクエストを処理できません。ファイルは convertCore.php に送信してください。';
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die($CoreError);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Check for required core variables.
if (!isset($Font)) $Font = 'Arial';
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2';
if (!isset($ApplicationTitle)) $ApplicationTitle = 'なんでも変換！';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI Related Logic.
// / Japanese does not inflect nouns for number, so both plural helpers stay empty.
// / The sentences that use them are written so they read correctly for any count.
if (!is_numeric($FileCount)) $FileCount = '不明な数';
$FCPlural1 = '';
$FCPlural2 = '';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Header Related Variables.
// / 'Click, Tap, or Drop files here to upload.'
$GuiHeaderText1 = 'クリック、タップ、またはファイルをここにドロップしてアップロードします。';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - UI Selector Bar Related Variables.
// / These strings appear in the selector bar, which is present on both GUI1 & GUI2.
// / 'Language'
$GuiSelectorText1 = '言語';
// / 'Color'
$GuiSelectorText2 = '配色';
// / 'Interface'
$GuiSelectorText3 = 'インターフェース';
// / 'Display language, color and interface options'
$GuiSelectorText4 = '言語・配色・インターフェースのオプションを表示';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / 'Online File Converter, Extractor, Compressor'
$Gui1Text1 = 'オンラインファイル変換・展開・圧縮ツール';
// / $ApplicationName.' is based off the open-source web-app HRConvert2 by Zelon88 that converts files without tracking users across the net or infringing on your intellectual property.'
$Gui1Text2 = $ApplicationName.' は、<a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a>（作者 <a href=\'https://github.com/zelon88\'>Zelon88</a>）をベースにしたオープンソースのウェブアプリです。ユーザーを追跡することも、知的財産を侵害することもなくファイルを変換します。';
// / 'More Info ...'
$Gui1Text3 = '詳細を表示 ...';
// / 'Less Info ...'
$Gui1Text4 = '詳細を隠す ...';
// / 'All user-supplied data is erased automatically, so you don\'t need to worry about forfeiting your personal information or property while using our services.'
$Gui1Text5 = 'ユーザーが送信したデータはすべて自動的に削除されます。本サービスの利用にあたり、個人情報や所有物を手放す心配はありません。';
// / 'Currently '.$ApplicationName.' supports '.$SupportedFormatCount.' different file formats, including documents, spreadsheets, images, media, 3D models, CAD drawings, vector files, archives, disk images, & more.'
$Gui1Text6 = '現在 '.$ApplicationName.' は '.$SupportedFormatCount.' 種類のファイル形式に対応しています。文書、表計算、画像、メディア、3D モデル、CAD 図面、ベクター、アーカイブ、ディスクイメージなどが含まれます。';
// / 'View Supported Formats ...'
$Gui1Text7 = '対応形式を表示 ...';
// / 'Hide Supported Formats ...'
$Gui1Text8 = '対応形式を隠す ...';
// / 'Supported Formats'
$Gui1Text9 = '対応形式';
// / 'Audio Formats'
$Gui1Text10 = '音声形式';
// / 'Supports specific bitrate.'
$Gui1Text11 = 'ビットレートの指定に対応しています。';
// / 'Video Formats'
$Gui1Text12 = '動画形式';
// / 'Stream Formats'
$Gui1Text13 = 'ストリーム形式';
// / 'Document Formats'
$Gui1Text14 = '文書形式';
// / 'Spreadsheet Formats'
$Gui1Text15 = '表計算形式';
// / 'Presentation Formats'
$Gui1Text16 = 'プレゼンテーション形式';
// / 'Archive Formats'
$Gui1Text17 = 'アーカイブ形式';
// / 'Can convert between select archive formats & disk image formats.'
$Gui1Text18 = '一部のアーカイブ形式とディスクイメージ形式の間で変換できます。';
// / 'Image Formats'
$Gui1Text19 = '画像形式';
// / 'Can convert pictures of documents to document formats.'
$Gui1Text20 = '文書を撮影した画像を文書形式に変換できます。';
// / 'Supports resize & rotate.'
$Gui1Text21 = 'サイズ変更と回転に対応しています。';
// / '3D Model Formats'
$Gui1Text22 = '3D モデル形式';
// / 'Drawing Formats'
$Gui1Text23 = '図面形式';
// / 'Can convert drawing formats to image formats.'
$Gui1Text24 = '図面形式を画像形式に変換できます。';
// / 'OCR Support'
$Gui1Text25 = 'OCR 対応';
// / 'OCR Operations support the following input formats...'
$Gui1Text26 = 'OCR 処理は以下の入力形式に対応しています ...';
// / 'OCR Operations support the following output formats...'
$Gui1Text27 = 'OCR 処理は以下の出力形式に対応しています ...';
// / 'Select files by clicking, tapping, or dropping them into the box below.'
$Gui1Text28 = '下のボックスをクリックまたはタップするか、ファイルをドロップして選択してください。';
// / 'Continue ...'
$Gui1Text29 = '続ける ...';
// / 'Can convert stream formats to video formats.'
$Gui1Text30 = 'ストリーム形式を動画形式に変換できます。';
// / 'Subtitle Formats'
$Gui1Text31 = '字幕形式';
// / 'OpenSCAD Formats'
$Gui1Text32 = 'OpenSCAD 形式';
// / 'Renders OpenSCAD source into 3D model formats.'
$Gui1Text33 = 'OpenSCAD のソースコードを 3D モデル形式に変換します。';
// / 'File references inside uploaded sources are removed unless the server allows resolving them.'
$Gui1Text34 = 'サーバーが許可しない限り、アップロードされたソース内のファイル参照は削除されます。';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'ファイル変換オプション';
// / 'Bulk File Options'
$Gui2Text2 = '一括ファイル操作';
// / 'Scan All Files For Viruses'
$Gui2Text3 = 'すべてのファイルをウイルススキャン';
// / 'Compress & Download All Files'
$Gui2Text4 = 'すべてのファイルを圧縮してダウンロード';
// / 'Download'
$Gui2Text5 = 'ダウンロード';
// / 'Share'
$Gui2Text6 = '共有';
// / 'Close Share Options'
$Gui2Text7 = '共有オプションを閉じる';
// / 'Virus Scan'
$Gui2Text8 = 'ウイルススキャン';
// / 'Close Virus Scan Options'
$Gui2Text9 = 'ウイルススキャンオプションを閉じる';
// / 'Archive'
$Gui2Text10 = 'アーカイブ';
// / 'Close Archive Options'
$Gui2Text11 = 'アーカイブオプションを閉じる';
// / 'OCR'
$Gui2Text12 = 'OCR';
// / 'Close OCR Options'
$Gui2Text13 = 'OCR オプションを閉じる';
// / 'Convert'
$Gui2Text14 = '変換';
// / 'Close Convert Options'
$Gui2Text15 = '変換オプションを閉じる';
// / 'Archive This File'
$Gui2Text16 = 'このファイルをアーカイブ';
// / 'Specify Filename: '
$Gui2Text17 = 'ファイル名を指定： ';
// / 'Format'
$Gui2Text18 = '形式';
// / 'Compress & Download'
$Gui2Text19 = '圧縮してダウンロード';
// / 'Scan with ClamAV: '
$Gui2Text20 = 'ClamAV でスキャン： ';
// / 'Scan with ScanCore: '
$Gui2Text21 = 'ScanCore でスキャン： ';
// / 'Scan All'
$Gui2Text22 = 'すべてスキャン';
// / 'Share This File'
$Gui2Text23 = 'このファイルを共有';
// / 'Link Status: '
$Gui2Text24 = 'リンクの状態： ';
// / 'Not Generated'
$Gui2Text25 = '未生成';
// / 'Generated'
$Gui2Text26 = '生成済み';
// / 'Clipboard Status: '
$Gui2Text27 = 'クリップボードの状態： ';
// / 'Copied'
$Gui2Text28 = 'コピー済み';
// / 'File Link: '
$Gui2Text29 = 'ファイルリンク： ';
// / 'You have uploaded '.$FileCount.' valid file'.$FCPlural1.' to '.$ApplicationName.'.'
$Gui2Text30 = $FileCount.' 個の有効なファイルを '.$ApplicationName.' にアップロードしました。'.$FCPlural1;
// / 'Your file'.$FCPlural2.' now ready to convert using the options below.'
$Gui2Text31 = '以下のオプションを使用してファイルを変換できます。'.$FCPlural2;
// / 'Generate Link & Copy to Clipboard'
$Gui2Text32 = 'リンクを生成してクリップボードにコピー';
// / 'Generate Link'
$Gui2Text33 = 'リンクを生成';
// / 'Scan This File For Viruses'
$Gui2Text34 = 'このファイルをウイルススキャン';
// / 'Scan File With ScanCore'
$Gui2Text35 = 'ScanCore でファイルをスキャン';
// / 'Scan File With ClamAV'
$Gui2Text36 = 'ClamAV でファイルをスキャン';
// / 'Scan File With ScanCore & ClamAV'
$Gui2Text37 = 'ScanCore と ClamAV でファイルをスキャン';
// / 'Perform Optical Character Recognition On This File'
$Gui2Text38 = 'このファイルに光学文字認識を実行';
// / 'Method'
$Gui2Text39 = '方式';
// / 'Simple'
$Gui2Text40 = 'シンプル';
// / 'Advanced'
$Gui2Text41 = '詳細';
// / 'Convert This Archive'
$Gui2Text42 = 'このアーカイブを変換';
// / 'Convert This Document'
$Gui2Text43 = 'この文書を変換';
// / 'Convert This Spreadsheet'
$Gui2Text44 = 'この表計算ファイルを変換';
// / 'Convert This Audio'
$Gui2Text45 = 'この音声ファイルを変換';
// / 'Convert This Video'
$Gui2Text46 = 'この動画を変換';
// / 'Convert This Stream'
$Gui2Text47 = 'このストリームを変換';
// / 'Convert This 3D Model'
$Gui2Text48 = 'この 3D モデルを変換';
// / 'Convert This Technical Drawing Or Vector File'
$Gui2Text49 = 'この技術図面またはベクターファイルを変換';
// / 'Convert This Image'
$Gui2Text50 = 'この画像を変換';
// / 'Archive File'
$Gui2Text51 = 'ファイルをアーカイブ';
// / 'Convert Into Document'
$Gui2Text52 = '文書に変換';
// / 'Archive Files'
$Gui2Text53 = 'ファイルをアーカイブ';
// / 'Convert Document'
$Gui2Text54 = '文書を変換';
// / 'Convert Spreadsheet'
$Gui2Text55 = '表計算ファイルを変換';
// / 'Convert Presentation'
$Gui2Text56 = 'プレゼンテーションを変換';
// / 'Convert Audio'
$Gui2Text57 = '音声を変換';
// / 'Convert Video'
$Gui2Text58 = '動画を変換';
// / 'Convert Stream'
$Gui2Text59 = 'ストリームを変換';
// / 'Convert Model'
$Gui2Text60 = 'モデルを変換';
// / 'Convert Drawing'
$Gui2Text61 = '図面を変換';
// / 'Convert Image'
$Gui2Text62 = '画像を変換';
// / NOTE. There is no $Gui2Text63 in any language pack. The gap exists in the English
// / original & is preserved here deliberately so every pack stays index compatible.
// / 'Width & Height: '
$Gui2Text64 = '幅と高さ： ';
// / 'Rotate: '
$Gui2Text65 = '回転： ';
// / 'Bitrate: '
$Gui2Text66 = 'ビットレート： ';
// / 'Delete'
$Gui2Text67 = '削除';
// / 'Close Delete Options'
$Gui2Text68 = '削除オプションを閉じる';
// / 'Delete This File'
$Gui2Text69 = 'このファイルを削除';
// / 'Confirm Delete'
$Gui2Text70 = '削除を確認';
// / 'Cannot convert this file! Try changing the name.'
$Gui2Text71 = 'このファイルは変換できません。ファイル名を変更してお試しください。';
// / 'Cannot perform a virus scan on this file!'
$Gui2Text72 = 'このファイルはウイルススキャンできません。';
// / 'File Link Copied to Clipboard!'
$Gui2Text73 = 'ファイルリンクをクリップボードにコピーしました。';
// / 'Operation Failed!'
$Gui2Text74 = '処理に失敗しました。';
// / 'Convert These Subtitles'
$Gui2Text75 = 'この字幕を変換';
// / 'Convert Subtitles'
$Gui2Text76 = '字幕を変換';
// / 'Convert This Presentation'
$Gui2Text77 = 'このプレゼンテーションを変換';
// / 'Convert This XPS File'
$Gui2Text78 = 'この XPS ファイルを変換';
// / 'Render This OpenSCAD Model'
$Gui2Text79 = 'この OpenSCAD モデルをレンダリング';
// / 'Render Model'
$Gui2Text80 = 'モデルをレンダリング';
// / 'Convert This E-Book'
$Gui2Text81 = 'この電子書籍を変換';
// / 'Convert E-Book'
$Gui2Text82 = '電子書籍を変換';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - HRC2-Functions.js Related Variables.
// / These strings are used by the client side javascript library.
// / That file is static javascript & cannot read a PHP variable, so every string it needs
// / is passed to it as an argument by the PHP that calls it.
// / 'Your browser does not support copying to the clipboard!'
$GuiFunctionsText1 = 'お使いのブラウザはクリップボードへのコピーに対応していません。';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our Terms of Service and Privacy Policy'
$GuiFooterText1 = '<a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>利用規約</a>と<a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>プライバシーポリシー</a>をご確認ください';
// / -----------------------------------------------------------------------------------