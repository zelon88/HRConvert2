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
$LanguageVersion = 'v4.2.0';
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
// / 'Delete every uploaded file & start a new session?'
// / Shown inside the start over panel on the upload page. That panel is only rendered
// / when the session already holds at least one file, so this is never shown to a first
// / time visitor who has nothing to lose.
$Gui1Text35 = 'アップロードしたすべてのファイルを削除して、新しいセッションを開始しますか？';
// / 'Start Over'
// / Labels the control that opens the panel & the button inside it that confirms.
$Gui1Text36 = 'やり直す';
// / 'Refresh'
// / Alternate text for the refresh control on the upload page. That control is a glyph
// / with no text of its own, so this is the only description a screen reader has & the
// / only thing shown when a browser cannot render the glyph.
$Gui1Text37 = '更新';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'ファイル変換オプション';
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
// / 'Playlist source URL, only needed if the playlist uses relative paths'
$Gui2Text83 = 'プレイリストの元URL。相対パスを使う場合のみ必要です';
// / 'Download A File From A URL'
$Gui2Text84 = 'URLからファイルをダウンロード';
// / 'Fetch'
$Gui2Text85 = '取得';
// / 'Identify'
$Gui2Text86 = '識別';
// / 'Close Identify Options'
$Gui2Text87 = '識別オプションを閉じる';
// / 'Identify This File'
$Gui2Text88 = 'このファイルを識別';
// / 'Size: '
$Gui2Text89 = 'サイズ：';
// / 'Modified: '
$Gui2Text90 = '更新日時：';
// / 'Type: '
$Gui2Text91 = '種類：';
// / 'MIME Type: '
$Gui2Text92 = 'MIME タイプ：';
// / 'Character Set: '
$Gui2Text93 = '文字セット：';
// / 'Extension Matches Content: '
$Gui2Text94 = '拡張子と内容の一致：';
// / 'Details: '
$Gui2Text95 = '詳細：';
// / 'Notes: '
$Gui2Text96 = '注記：';
// / 'Save report as file'
$Gui2Text97 = 'レポートをファイルとして保存';
// / 'Yes'
$Gui2Text100 = 'はい';
// / 'No'
$Gui2Text101 = 'いいえ';
// / 'Unknown'
$Gui2Text102 = '不明';
// / 'Cannot identify this file!'
$Gui2Text103 = 'このファイルは識別できません。';
// / 'Sort By: '
$Gui2Text104 = '並べ替え：';
// / 'Name'
$Gui2Text105 = '名前';
// / 'Size'
$Gui2Text106 = 'サイズ';
// / 'Date'
$Gui2Text107 = '日付';
// / 'Type'
$Gui2Text108 = '種類';
// / The words in braces, such as {fit} & {count}, are filled in by the page. Each translation keeps every one of them.
// / 'Select'
$Gui2Text109 = '選択';
// / 'Select All'
$Gui2Text110 = 'すべて選択';
// / 'Select None'
$Gui2Text111 = '選択を解除';
// / 'Select By Type'
$Gui2Text112 = '種類で選択';
// / 'Selected: '
$Gui2Text113 = '選択中：';
// / 'Documents'
$Gui2Text114 = '文書';
// / 'Spreadsheets'
$Gui2Text115 = '表計算';
// / 'Presentations'
$Gui2Text116 = 'プレゼンテーション';
// / 'Images'
$Gui2Text117 = '画像';
// / 'Audio'
$Gui2Text118 = '音声';
// / 'Video'
$Gui2Text119 = '動画';
// / 'Archives'
$Gui2Text120 = 'アーカイブ';
// / 'E-Books'
$Gui2Text121 = '電子書籍';
// / '3D Models'
$Gui2Text122 = '3D モデル';
// / 'Drawings'
$Gui2Text123 = '図面';
// / 'Vector Images'
$Gui2Text124 = 'ベクター画像';
// / 'Subtitles'
$Gui2Text125 = '字幕';
// / 'OpenSCAD Files'
$Gui2Text126 = 'OpenSCAD ファイル';
// / 'Streams'
$Gui2Text127 = 'ストリーム';
// / 'Workflows'
$Gui2Text128 = 'ワークフロー';
// / 'Selected Files'
$Gui2Text129 = '選択したファイル';
// / 'Select files in the list below.'
$Gui2Text130 = '下の一覧からファイルを選択してください。';
// / 'Select By Name: '
$Gui2Text131 = '名前で選択：';
// / 'Use * and ? as wildcards, or write a regular expression between slashes.'
$Gui2Text132 = '* と ? をワイルドカードとして使うか、スラッシュで囲んだ正規表現を書いてください。';
// / 'Workflow'
$Gui2Text133 = 'ワークフロー';
// / 'Choose a workflow'
$Gui2Text134 = 'ワークフローを選択';
// / 'Run'
$Gui2Text135 = '実行';
// / 'Stop'
$Gui2Text136 = '停止';
// / 'Selected files that can be used: {fit} of {count}.'
$Gui2Text137 = '使用できる選択ファイル：{count} 件中 {fit} 件。';
// / 'Step {step} of {steps}: {operation}'
$Gui2Text138 = 'ステップ {step}/{steps}：{operation}';
// / 'File {done} of {total}'
$Gui2Text139 = 'ファイル {done}/{total}';
// / 'Waiting for the server.'
$Gui2Text140 = 'サーバーを待っています。';
// / 'Starting.'
$Gui2Text141 = '開始しています。';
// / 'Finished.'
$Gui2Text142 = '完了しました。';
// / 'Stopping.'
$Gui2Text143 = '停止しています。';
// / 'Stopped.'
$Gui2Text144 = '停止しました。';
// / 'Files made: '
$Gui2Text145 = '作成されたファイル：';
// / 'Workflows are not available on this server.'
$Gui2Text146 = 'このサーバーではワークフローを利用できません。';
// / 'No workflows are installed on this server.'
$Gui2Text147 = 'このサーバーにはワークフローがインストールされていません。';
// / 'That workflow is no longer available.'
$Gui2Text148 = 'そのワークフローは利用できなくなりました。';
// / 'None of the selected files can be used by this workflow.'
$Gui2Text149 = '選択したファイルのうち、このワークフローで使用できるものはありません。';
// / 'Too many files are selected for one workflow.'
$Gui2Text150 = '1 つのワークフローに選択されたファイルが多すぎます。';
// / 'The server is busy. Try again in a few minutes.'
$Gui2Text151 = 'サーバーが混み合っています。数分後にもう一度お試しください。';
// / 'A workflow is already running in this session.'
$Gui2Text152 = 'このセッションでは既にワークフローが実行中です。';
// / 'The workflow could not be started.'
$Gui2Text153 = 'ワークフローを開始できませんでした。';
// / 'A step of the workflow failed.'
$Gui2Text154 = 'ワークフローのステップが失敗しました。';
// / 'A virus was found, so the workflow stopped.'
$Gui2Text155 = 'ウイルスが見つかったため、ワークフローを停止しました。';
// / 'The workflow ran out of time.'
$Gui2Text156 = 'ワークフローの制限時間を超えました。';
// / 'The workflow made too many files.'
$Gui2Text157 = 'ワークフローが作成したファイルが多すぎます。';
// / 'The server was too busy to finish the workflow.'
$Gui2Text158 = 'サーバーが混み合っていたため、ワークフローを完了できませんでした。';
// / 'An operation this workflow needs is not available.'
$Gui2Text159 = 'このワークフローに必要な操作が利用できません。';
// / 'The workflow stopped unexpectedly.'
$Gui2Text160 = 'ワークフローが予期せず停止しました。';
// / The words in braces, such as {step} & {kind}, are filled in by the page. Each translation keeps every one of them.
// / 'Available Pipelines'
$Gui2Text161 = '利用できるパイプライン';
// / 'Select a pipeline above...'
$Gui2Text162 = '上からパイプラインを選択してください...';
// / 'Select Pipeline...'
$Gui2Text163 = 'パイプラインを選択...';
// / 'Select Files...'
$Gui2Text164 = 'ファイルを選択...';
// / 'Add a step'
$Gui2Text165 = 'ステップを追加';
// / 'Options'
$Gui2Text166 = 'オプション';
// / 'Back'
$Gui2Text167 = '戻る';
// / 'Take: '
$Gui2Text168 = '対象：';
// / 'the selected files'
$Gui2Text169 = '選択したファイル';
// / 'the files step {step} made'
$Gui2Text170 = 'ステップ {step} が作成したファイル';
// / 'each file on its own'
$Gui2Text171 = 'ファイルごとに個別に';
// / 'all files together'
$Gui2Text172 = 'すべてのファイルをまとめて';
// / 'Only these kinds: '
$Gui2Text173 = 'これらの種類のみ：';
// / 'Only these extensions: '
$Gui2Text174 = 'これらの拡張子のみ：';
// / 'Smallest size: '
$Gui2Text175 = '最小サイズ：';
// / 'Largest size: '
$Gui2Text176 = '最大サイズ：';
// / 'In the order they arrive'
$Gui2Text177 = '届いた順';
// / 'all files'
$Gui2Text178 = 'すべてのファイル';
// / 'all {kind}'
$Gui2Text179 = 'すべての{kind}';
// / 'Width: '
$Gui2Text180 = '幅：';
// / 'Height: '
$Gui2Text181 = '高さ：';
// / 'My workflow'
$Gui2Text182 = 'マイ ワークフロー';
// / 'New'
$Gui2Text183 = '新規';
// / 'Import'
$Gui2Text184 = 'インポート';
// / 'Export'
$Gui2Text185 = 'エクスポート';
// / 'This workflow is not one this server can run.'
$Gui2Text186 = 'このサーバーではこのワークフローを実行できません。';
// / 'The file could not be read.'
$Gui2Text187 = 'ファイルを読み取れませんでした。';
// / 'The workflow builder is not available on this server.'
$Gui2Text188 = 'このサーバーではワークフロー ビルダーを利用できません。';
// / 'Status'
$Gui2Text189 = '状態';
// / 'A workflow is running.'
$Gui2Text190 = 'ワークフローを実行中です。';
// / 'No workflow is running.'
$Gui2Text191 = '実行中のワークフローはありません。';
// / 'Extract'
$Gui2Text192 = '展開';
// / 'Close Extract Options'
$Gui2Text193 = '展開オプションを閉じる';
// / 'Extract This Archive'
$Gui2Text194 = 'このアーカイブを展開';
// / 'Every file it holds is added to your files, under its own name or the next free one.'
$Gui2Text195 = '中のファイルはすべて、元の名前か次に空いている名前で、あなたのファイルに加わります。';
// / 'Extract Files'
$Gui2Text196 = 'ファイルを展開';
// / 'to {format}'
$Gui2Text197 = '{format} へ';
// / 'This workflow deletes files. A deleted file cannot be brought back.'
$Gui2Text198 = 'このワークフローはファイルを削除します。削除したファイルは元に戻せません。';
// / 'Delete & Run'
$Gui2Text199 = '削除して実行';
// / 'Cancel'
$Gui2Text200 = 'キャンセル';
// / 'Rename'
$Gui2Text201 = '名前を変更';
// / 'Close Rename Options'
$Gui2Text202 = '名前の変更オプションを閉じる';
// / 'Rename This File'
$Gui2Text203 = 'このファイルの名前を変更';
// / 'New Name'
$Gui2Text204 = '新しい名前';
// / 'Use {name} for the name it has, {n} for its place among the selected files & {date} for today. Its extension is kept.'
$Gui2Text205 = '{name} は今の名前、{n} は選択したファイルの中での順番、{date} は今日の日付になります。拡張子はそのまま残ります。';
// / 'Strip Metadata'
$Gui2Text206 = 'メタデータを削除';
// / 'Close Strip Metadata Options'
$Gui2Text207 = 'メタデータのオプションを閉じる';
// / 'Strip The Metadata From This File'
$Gui2Text208 = 'このファイルのメタデータを削除';
// / 'A copy is made beside it without its authors, places, dates & the software that made it. What it shows is left as it was.'
$Gui2Text209 = '作成者、場所、日時、作成したソフトウェアを除いたコピーを隣に作ります。表示される内容は変わりません。';
// / 'Merge PDFs'
$Gui2Text210 = 'PDF を結合';
// / 'Split PDF'
$Gui2Text211 = 'PDF を分割';
// / 'Merge Or Split'
$Gui2Text212 = '結合または分割';
// / 'Close Merge & Split Options'
$Gui2Text213 = '結合と分割のオプションを閉じる';
// / 'Merge The Selected PDFs Into One'
$Gui2Text214 = '選択した PDF を 1 つに結合';
// / 'Their pages follow one another in the order the PDFs were selected.'
$Gui2Text215 = 'ページは PDF を選択した順に並びます。';
// / 'Select two or more PDFs to merge them.'
$Gui2Text216 = '結合するには PDF を 2 つ以上選択してください。';
// / 'Make A PDF Of Each Page'
$Gui2Text217 = '各ページを PDF にする';
// / 'Every selected file is deleted with it: {count} files.'
$Gui2Text218 = '選択したファイルもすべて削除されます：{count} 件。';
// / 'Every selected file is renamed with it: {count} files.'
$Gui2Text219 = '選択したファイルもすべて名前が変わります：{count} 件。';
// / 'Library'
$Gui2Text220 = 'ライブラリ';
// / 'from Selected Files'
$Gui2Text221 = '選択したファイルから';
// / 'from Step {step}'
$Gui2Text222 = 'ステップ {step} から';
// / 'Preview'
$Gui2Text223 = 'プレビュー';
// / 'If the workflow ran now, each step would take:'
$Gui2Text224 = '今ワークフローを実行すると、各ステップが対象にするファイル：';
// / 'Left out: '
$Gui2Text225 = '除外：';
// / 'No files.'
$Gui2Text226 = 'ファイルはありません。';
// / 'every file in the list when it runs'
$Gui2Text227 = 'ステップの実行時に一覧にあるすべてのファイル';
// / 'This workflow deletes these files. A deleted file cannot be brought back.'
$Gui2Text228 = 'このワークフローは次のファイルを削除します。削除したファイルは元に戻せません。';
// / 'A step had errors, so the workflow stopped before the step that deletes files.'
$Gui2Text229 = 'ステップでエラーが発生したため、ファイルを削除するステップの前でワークフローを停止しました。';
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

// / -----------------------------------------------------------------------------------
// / Set GUI - Sign In Related Variables.
// / These strings are drawn by engineGui.php, the sign in page the Engine names, & by the sign in bar in the footer.
// / A sentence that names a number is written so it reads correctly for any count, so no plural helper is needed.
// / 'Sign in'
$GuiAuthText1 = 'サインイン';
// / 'Sign out'
$GuiAuthText2 = 'サインアウト';
// / 'Create an account'
$GuiAuthText3 = 'アカウントを作成';
// / 'Username'
$GuiAuthText4 = 'ユーザー名';
// / 'Password'
$GuiAuthText5 = 'パスワード';
// / 'Email address'
$GuiAuthText6 = 'メールアドレス';
// / 'Code'
$GuiAuthText7 = 'コード';
// / 'New password'
$GuiAuthText8 = '新しいパスワード';
// / 'Confirm your account'
$GuiAuthText9 = 'アカウントを確認';
// / 'Recover your account'
$GuiAuthText10 = 'アカウントを復旧';
// / 'Choose a new password'
$GuiAuthText11 = '新しいパスワードを選択';
// / 'Send the code again'
$GuiAuthText12 = 'コードを再送信';
// / 'Forgot your password?'
$GuiAuthText13 = 'パスワードをお忘れですか？';
// / 'Have a code?'
$GuiAuthText14 = 'コードをお持ちですか？';
// / 'Back to sign in'
$GuiAuthText15 = 'サインインに戻る';
// / 'Continue without signing in'
$GuiAuthText16 = 'サインインせずに続ける';
// / 'Signed in as'
$GuiAuthText17 = 'サインイン中のユーザー：';
// / 'Signing in is unavailable on this server right now.'
$GuiAuthText18 = '現在、このサーバーではサインインできません。';
// / 'Confirm'
$GuiAuthText19 = '確認';
// / 'Send a code'
$GuiAuthText20 = 'コードを送信';
// / 'Change password'
$GuiAuthText21 = 'パスワードを変更';
// / 'The username or password is incorrect.'
$GuiAuthText22 = 'ユーザー名またはパスワードが正しくありません。';
// / 'Too many attempts. Please wait a few minutes and try again.'
$GuiAuthText23 = '試行回数が多すぎます。数分待ってからもう一度お試しください。';
// / 'Confirm your account with the code we emailed you first.'
$GuiAuthText24 = 'まず、メールでお送りしたコードでアカウントを確認してください。';
// / 'This account cannot sign in. Contact the administrator.'
$GuiAuthText25 = 'このアカウントはサインインできません。管理者に連絡してください。';
// / 'New accounts cannot be created here. Contact the administrator.'
$GuiAuthText26 = 'ここでは新しいアカウントを作成できません。管理者に連絡してください。';
// / 'A username is 3 to 64 letters, digits, dots, dashes or underscores, and starts with a letter or a digit.'
$GuiAuthText27 = 'ユーザー名は英字、数字、ピリオド、ハイフン、アンダースコアの3～64文字で、英字か数字で始まります。';
// / 'That username is taken. Choose another.'
$GuiAuthText28 = 'そのユーザー名は使用されています。別の名前を選んでください。';
// / 'That email address cannot be used.'
$GuiAuthText29 = 'そのメールアドレスは使用できません。';
// / 'Passwords must have at least this many characters:'
$GuiAuthText30 = 'パスワードの最小文字数：';
// / 'Your account was created. We emailed you a code to confirm it.'
$GuiAuthText31 = 'アカウントを作成しました。確認用のコードをメールでお送りしました。';
// / 'Your account was created, but this server could not email your code. Contact the administrator.'
$GuiAuthText32 = 'アカウントを作成しましたが、このサーバーからコードをメールで送信できませんでした。管理者に連絡してください。';
// / 'Your account was created. You can sign in now.'
$GuiAuthText33 = 'アカウントを作成しました。サインインできます。';
// / 'That code is not right.'
$GuiAuthText34 = 'そのコードは正しくありません。';
// / 'That code can no longer be used. Ask for a new one.'
$GuiAuthText35 = 'そのコードはもう使用できません。新しいコードを請求してください。';
// / 'Your account is confirmed. You can sign in now.'
$GuiAuthText36 = 'アカウントを確認しました。サインインできます。';
// / 'If that account is waiting to be confirmed, a new code is on its way.'
$GuiAuthText37 = 'そのアカウントが確認待ちであれば、新しいコードを送信しています。';
// / 'If that account exists, a code is on its way to its email address.'
$GuiAuthText38 = 'そのアカウントが存在すれば、そのメールアドレスにコードを送信しています。';
// / 'Your password was changed. You can sign in now.'
$GuiAuthText39 = 'パスワードを変更しました。サインインできます。';
// / 'You have been signed out.'
$GuiAuthText40 = 'サインアウトしました。';
// / 'You were signed out, because your sign in was used somewhere else.'
$GuiAuthText41 = 'サインインが別の場所で使用されたため、サインアウトしました。';
// / 'The server did not answer. Please try again.'
$GuiAuthText42 = 'サーバーが応答しませんでした。もう一度お試しください。';
// / 'Recovering an account is unavailable on this server.'
$GuiAuthText43 = 'このサーバーではアカウントを復旧できません。';
// / -----------------------------------------------------------------------------------