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
$CoreError = 'ERROR!!! HRConvert2-2, 该文件无法处理您的请求！ 请将您的文件提交到convertCore.php！';
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die($CoreError);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Check for required core variables.
if (!isset($Font)) $Font = 'Arial';
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = '转换任何东西！';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI Related Logic.
if (!is_numeric($FileCount)) $FileCount = '未知数量的';
$FCPlural1 = '';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Header Related Variables.
// / 'Click, Tap, or Drop files here to upload.'
$GuiHeaderText1 = '单击、点按或将文件拖放到此处即可上传。';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / Online File Converter, Extractor, Compressor'
$Gui1Text1 = '在线文件转换器、提取器、压缩器';
// / $ApplicationName.' is based off the open-source web-app <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> by <a href=\'https://github.com/zelon88\'>Zelon88</a> that converts files without tracking users across the net or infringing on your intellectual property.'
$Gui1Text2 = $ApplicationName.' 基于 <a href=\'https://github.com 开发的开源网络应用 <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> /zelon88\'>Zelon88</a> 转换文件时不会跟踪网络上的用户或侵犯您的知识产权。';
// / 'More Info ...'
$Gui1Text3 = '更多信息 ...';
// / 'Less Info ...'
$Gui1Text4 = '信息较少...';
// / 'All user-supplied data is erased automatically, so you don\'t need to worry about forfeiting your personal information or property while using our services.'
$Gui1Text5 = '所有用户提供的数据都会自动删除，因此您在使用我们的服务时无需担心丢失您的个人信息或财产。';
// / 'Currently '.$ApplicationName.' supports '.$SupportedFormatCount.' different file formats, including documents, spreadsheets, images, media, 3D models, CAD drawings, vector files, archives, disk images, & more.'
$Gui1Text6 = '当前为“.$ApplicationName”。 支持“.$SupportedFormatCount”。 不同的文件格式，包括文档、电子表格、图像、媒体、3D 模型、CAD 绘图、矢量文件、档案、磁盘映像等。';
// / 'View Supported Formats ...'
$Gui1Text7 = '查看支持的格式...';
// / 'Hide Supported Formats ...'
$Gui1Text8 = '隐藏支持的格式...';
// / 'Supported Formats'
$Gui1Text9 = '支持的格式';
// / 'Audio Formats'
$Gui1Text10 = '音频格式';
// / 'Supports specific bitrate.'
$Gui1Text11 = '支持特定比特率。';
// / 'Video Formats'
$Gui1Text12 = '视频格式';
// / 'Stream Formats'
$Gui1Text13 = '流格式';
// / 'Document Formats'
$Gui1Text14 = '文档格式';
// / 'Spreadsheet Formats'
$Gui1Text15 = '电子表格格式';
// / 'Presentation Formats'
$Gui1Text16 = '演示格式';
// / 'Archive Formats'
$Gui1Text17 = 'Archive Formats';
// / 'Can convert between archive formats & disk image formats.'
$Gui1Text18 = '可以在选择的存档格式和磁盘映像格式之间进行转换。';
// / 'Image Formats'
$Gui1Text19 = '图像格式';
// / 'Can convert pictures of documents to document formats.'
$Gui1Text20 = '可以将文档图片转换为文档格式。';
// / 'Supports resize & rotate.'
$Gui1Text21 = '支持调整大小和旋转。';
// / '3D Model Formats'
$Gui1Text22 = '3D 模型格式';
// / 'Drawing Formats'
$Gui1Text23 = '绘图格式';
// / 'Can convert drawing files to image formats.'
$Gui1Text24 = '可以将绘图格式转换为图像格式。';
// / 'OCR Support'
$Gui1Text25 = '光学字符识别支持';
// / 'OCR Operations support the following input formats...'
$Gui1Text26 = 'OCR 操作支持以下输入格式...';
// / 'OCR Operations support the following output formats...'
$Gui1Text27 = 'OCR 操作支持以下输出格式...';
// / 'Select files by clicking, tapping, or dropping them into the box below.'
$Gui1Text28 = '通过单击、点击或将文件放入下面的框中来选择文件。';
// / 'Continue ...'
$Gui1Text29 = '继续 ...';
// / 'Can convert stream formats to video formats.'
$Gui1Text30 = '可以将流格式转换为视频格式。';
// / 'Subtitle Formats'
$Gui1Text31 = '字幕格式';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = '文件转换选项';
// / 'Bulk File Options'
$Gui2Text2 = '批量文件选项';
// / 'Scan All Files For Viruses'
$Gui2Text3 = '扫描所有文件是否存在病毒';
// / 'Compress & Download All Files'
$Gui2Text4 = '压缩并下载所有文件';
// / 'Download'
$Gui2Text5 = '下载';
// / 'Share'
$Gui2Text6 = '分享';
// / 'Close Share Options'
$Gui2Text7 = '关闭股票期权';
// / 'Virus Scan'
$Gui2Text8 = 'Virus Scan';
// / 'Close Virus Scan Options'
$Gui2Text9 = '关闭病毒扫描选项';
// / 'Archive'
$Gui2Text10 = '档案';
// / 'Close Archive Options'
$Gui2Text11 = '关闭存档选项';
// / 'OCR'
$Gui2Text12 = '光学字符识别';
// / 'Close OCR Options'
$Gui2Text13 = '关闭 OCR 选项';
// / 'Convert'
$Gui2Text14 = '转变';
// / 'Close Convert Options'
$Gui2Text15 = '关闭转换选项';
// / 'Archive This File'
$Gui2Text16 = '存档此文件';
// / 'Specify Filename: '
$Gui2Text17 = '指定文件名： ';
// / 'Format'
$Gui2Text18 = '格式';
// / 'Compress & Download'
$Gui2Text19 = '压缩并下载';
// / 'Scan with ClamAV: '
$Gui2Text20 = '使用 ClamAV 扫描： ';
// / 'Scan with ScanCore: '
$Gui2Text21 = '使用 ScanCore 扫描： ';
// / 'Scan All'
$Gui2Text22 = '扫描全部';
// / 'Share This File'
$Gui2Text23 = '分享此文件';
// / 'Link Status: '
$Gui2Text24 = '链接状态： ';
// / 'Not Generated'
$Gui2Text25 = '未生成';
// / 'Generated'
$Gui2Text26 = '生成';
// / 'Clipboard Status: '
$Gui2Text27 = '剪贴板状态： ';
// / 'Copied'
$Gui2Text28 = '已复制';
// / 'File Link: '
$Gui2Text29 = '文件链接： ';
// / 'You have uploaded '.$FileCount.' valid file'.$FCPlural1.' to '.$ApplicationName.'.'
$Gui2Text30 = '您已上传 '.$FileCount.' 个有效文件至 '.$ApplicationName;
// / 'Your file'.$FCPlural2.' now ready to convert using the options below.'
$Gui2Text31 = '您的文件现在可以使用以下选项进行转换。';
// / 'Generate Link & Copy to Clipboard'
$Gui2Text32 = '生成链接并复制到剪贴板';
// / 'Generate Link'
$Gui2Text33 = '生成链接';
// / 'Scan This File For Viruses'
$Gui2Text34 = '扫描此文件是否有病毒';
// / 'Scan File With ScanCore'
$Gui2Text35 = '使用 ScanCore 扫描文件';
// / 'Scan File With ClamAV'
$Gui2Text36 = '使用 ClamAV 扫描文件';
// / 'Scan File With ScanCore & ClamAV'
$Gui2Text37 = '使用 ScanCore 和 ClamAV 扫描文件';
// / 'Perform Optical Character Recognition On This File'
$Gui2Text38 = '对此文件执行光学字符识别';
// / 'Method'
$Gui2Text39 = '方法';
// / 'Simple'
$Gui2Text40 = '简单的';
// / 'Advanced'
$Gui2Text41 = '先进的';
// / 'Convert This Archive'
$Gui2Text42 = '转换此存档';
// / 'Convert This Document'
$Gui2Text43 = '转换此文档';
// / 'Convert This Spreadsheet'
$Gui2Text44 = '转换此电子表格';
// / 'Convert This Audio'
$Gui2Text45 = '转换此音频';
// / 'Convert This Video'
$Gui2Text46 = '转换该视频';
// / 'Convert This Stream'
$Gui2Text47 = '转换此流';
// / Convert This 3D Model'
$Gui2Text48 = '转换此 3D 模型';
// / 'Convert This Technical Drawing Or Vector File'
$Gui2Text49 = '转换此技术绘图或矢量文件';
// / 'Convert This Image'
$Gui2Text50 = '转换此图像';
// / 'Archive File'
$Gui2Text51 = '存档文件';
// / 'Convert Into Document'
$Gui2Text52 = '转换为文档';
// / 'Archive Files'
$Gui2Text53 = '存档文件';
// / 'Convert Document'
$Gui2Text54 = '转换文档';
// / 'Convert Spreadsheet'
$Gui2Text55 = '转换电子表格';
// / 'Convert Presentation'
$Gui2Text56 = '转换演示文稿';
// / 'Convert Audio'
$Gui2Text57 = '转换音频';
// / 'Convert Video'
$Gui2Text58 = '转换视频';
// / 'Convert Stream'
$Gui2Text59 = '转换流';
// / 'Convert Model'
$Gui2Text60 = '转换型号';
// / 'Convert Drawing'
$Gui2Text61 = '转换绘图';
// / 'Convert Image'
$Gui2Text62 = '转换图像';
// / 'Width & Height'
$Gui2Text64 = '宽度和高度： ';
// / 'Rotate: '
$Gui2Text65 = '旋转： ';
// / 'Bitrate: '
$Gui2Text66 = '比特率： ';
// / 'Delete'
$Gui2Text67 = '删除';
// / 'Close Delete Options'
$Gui2Text68 = '关闭删除选项';
// / 'Delete This File'
$Gui2Text69 = '删除该文件';
// / 'Confirm Delete'
$Gui2Text70 = '确认删除';
// / 'Cannot convert this file! Try changing the name.'
$Gui2Text71 = '无法转换此文件！ 尝试更改名称。';
// / 'Cannot perform a virus scan on this file!'
$Gui2Text72 = '无法对此文件执行病毒扫描！';
// / 'File Link Copied to Clipboard!'
$Gui2Text73 = '文件链接已复制到剪贴板！';
// / 'Operation Failed!'
$Gui2Text74 = '手术失败！';
// / Convert These Subtitles'
$Gui2Text75 = '转换这些字幕';
// / Convert Subtitles'
$Gui2Text76 = '转换字幕';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy'
$GuiFooterText1 = '查看我们的<a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>服务条款</a>和<a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>隐私政策';
// / -----------------------------------------------------------------------------------