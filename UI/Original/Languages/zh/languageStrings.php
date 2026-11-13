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
// / Set GUI - UI Selector Bar Related Variables.
// / These strings appear in the selector bar, which is present on both GUI1 & GUI2.
// / 'Language'
$GuiSelectorText1 = '语言';
// / 'Color'
$GuiSelectorText2 = '颜色';
// / 'Interface'
$GuiSelectorText3 = '界面';
// / 'Display language, color and interface options'
$GuiSelectorText4 = '显示语言、颜色和界面选项';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / 'Online File Converter, Extractor, Compressor'
$Gui1Text1 = '在线文件转换器、提取器、压缩器';
// / $ApplicationName.' is based off the open-source web-app <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> by <a href=\'https://github.com/zelon88\'>Zelon88</a> that converts files without tracking users across the net or infringing on your intellectual property.'
$Gui1Text2 = $ApplicationName.' 基于 <a href=\'https://github.com/zelon88\'>Zelon88</a> 开发的开源网络应用 <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> 转换文件时不会跟踪网络上的用户或侵犯您的知识产权。';
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
$Gui1Text17 = '存档格式';
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
// / 'OpenSCAD Formats'
$Gui1Text32 = 'OpenSCAD 格式';
// / 'Renders OpenSCAD source into 3D model formats.'
$Gui1Text33 = '将 OpenSCAD 源代码转换为三维模型格式。';
// / 'File references inside uploaded sources are removed unless the server allows resolving them.'
$Gui1Text34 = '除非服务器允许解析，否则已上传源文件中的文件引用将被移除。';
// / 'Delete every uploaded file & start a new session?'
// / Shown inside the start over panel on the upload page. That panel is only rendered
// / when the session already holds at least one file, so this is never shown to a first
// / time visitor who has nothing to lose.
$Gui1Text35 = '删除所有已上传的文件并开始新会话？';
// / 'Start Over'
// / Labels the control that opens the panel & the button inside it that confirms.
$Gui1Text36 = '重新开始';
// / 'Refresh'
// / Alternate text for the refresh control on the upload page. That control is a glyph
// / with no text of its own, so this is the only description a screen reader has & the
// / only thing shown when a browser cannot render the glyph.
$Gui1Text37 = '刷新';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = '文件转换选项';
// / 'Download'
$Gui2Text5 = '下载';
// / 'Share'
$Gui2Text6 = '分享';
// / 'Close Share Options'
$Gui2Text7 = '关闭股票期权';
// / 'Virus Scan'
$Gui2Text8 = '病毒扫描';
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
// / 'Convert This 3D Model'
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
// / 'Convert These Subtitles'
$Gui2Text75 = '转换这些字幕';
// / 'Convert Subtitles'
$Gui2Text76 = '转换字幕';
// / 'Convert This Presentation'
$Gui2Text77 = '转换此演示文稿';
// / 'Convert This XPS File'
$Gui2Text78 = '转换此文件';
// / 'Render This OpenSCAD Model'
$Gui2Text79 = '渲染此 OpenSCAD 模型';
// / 'Render Model'
$Gui2Text80 = '渲染模型';
// / 'Convert This E-Book'
$Gui2Text81 = '转换此电子书';
// / 'Convert E-Book'
$Gui2Text82 = '转换电子书';
// / 'Playlist source URL, only needed if the playlist uses relative paths'
$Gui2Text83 = '播放列表来源网址，仅当列表使用相对路径时需要';
// / 'Download A File From A URL'
$Gui2Text84 = '从网址下载文件';
// / 'Fetch'
$Gui2Text85 = '获取';
// / 'Identify'
$Gui2Text86 = '识别';
// / 'Close Identify Options'
$Gui2Text87 = '关闭识别选项';
// / 'Identify This File'
$Gui2Text88 = '识别此文件';
// / 'Size: '
$Gui2Text89 = '大小：';
// / 'Modified: '
$Gui2Text90 = '修改时间：';
// / 'Type: '
$Gui2Text91 = '类型：';
// / 'MIME Type: '
$Gui2Text92 = 'MIME 类型：';
// / 'Character Set: '
$Gui2Text93 = '字符集：';
// / 'Extension Matches Content: '
$Gui2Text94 = '扩展名与内容一致：';
// / 'Details: '
$Gui2Text95 = '详细信息：';
// / 'Notes: '
$Gui2Text96 = '备注：';
// / 'Save report as file'
$Gui2Text97 = '将报告另存为文件';
// / 'Yes'
$Gui2Text100 = '是';
// / 'No'
$Gui2Text101 = '否';
// / 'Unknown'
$Gui2Text102 = '未知';
// / 'Cannot identify this file!'
$Gui2Text103 = '无法识别此文件！';
// / 'Sort By: '
$Gui2Text104 = '排序方式：';
// / 'Name'
$Gui2Text105 = '名称';
// / 'Size'
$Gui2Text106 = '大小';
// / 'Date'
$Gui2Text107 = '日期';
// / 'Type'
$Gui2Text108 = '类型';
// / The words in braces, such as {fit} & {count}, are filled in by the page. Each translation keeps every one of them.
// / 'Select'
$Gui2Text109 = '选择';
// / 'Select All'
$Gui2Text110 = '全选';
// / 'Select None'
$Gui2Text111 = '全不选';
// / 'Select By Type'
$Gui2Text112 = '按类型选择';
// / 'Selected: '
$Gui2Text113 = '已选择：';
// / 'Documents'
$Gui2Text114 = '文档';
// / 'Spreadsheets'
$Gui2Text115 = '电子表格';
// / 'Presentations'
$Gui2Text116 = '演示文稿';
// / 'Images'
$Gui2Text117 = '图像';
// / 'Audio'
$Gui2Text118 = '音频';
// / 'Video'
$Gui2Text119 = '视频';
// / 'Archives'
$Gui2Text120 = '压缩包';
// / 'E-Books'
$Gui2Text121 = '电子书';
// / '3D Models'
$Gui2Text122 = '3D 模型';
// / 'Drawings'
$Gui2Text123 = '图纸';
// / 'Vector Images'
$Gui2Text124 = '矢量图像';
// / 'Subtitles'
$Gui2Text125 = '字幕';
// / 'OpenSCAD Files'
$Gui2Text126 = 'OpenSCAD 文件';
// / 'Streams'
$Gui2Text127 = '流媒体';
// / 'Workflows'
$Gui2Text128 = '工作流';
// / 'Files'
$Gui2Text129 = '文件';
// / 'Select files in the list below.'
$Gui2Text130 = '请在下方列表中选择文件。';
// / 'Select By Name: '
$Gui2Text131 = '按名称选择：';
// / 'Use * and ? as wildcards, or write a regular expression between slashes.'
$Gui2Text132 = '可使用 * 和 ? 作为通配符，或在两个斜杠之间输入正则表达式。';
// / 'Workflow'
$Gui2Text133 = '工作流';
// / 'Choose a workflow'
$Gui2Text134 = '选择工作流';
// / 'Run'
$Gui2Text135 = '运行';
// / 'Stop'
$Gui2Text136 = '停止';
// / 'Selected files that can be used: {fit} of {count}.'
$Gui2Text137 = '可使用的已选文件：{count} 个中的 {fit} 个。';
// / 'Step {step} of {steps}: {operation}'
$Gui2Text138 = '第 {step}/{steps} 步：{operation}';
// / 'File {done} of {total}'
$Gui2Text139 = '文件 {done}/{total}';
// / 'Waiting for the server.'
$Gui2Text140 = '正在等待服务器。';
// / 'Starting.'
$Gui2Text141 = '正在启动。';
// / 'Finished.'
$Gui2Text142 = '已完成。';
// / 'Stopping.'
$Gui2Text143 = '正在停止。';
// / 'Stopped.'
$Gui2Text144 = '已停止。';
// / 'Files made: '
$Gui2Text145 = '已生成的文件：';
// / 'Workflows are not available on this server.'
$Gui2Text146 = '此服务器上不提供工作流。';
// / 'No workflows are installed on this server.'
$Gui2Text147 = '此服务器上未安装任何工作流。';
// / 'That workflow is no longer available.'
$Gui2Text148 = '该工作流已不再可用。';
// / 'None of the selected files can be used by this workflow.'
$Gui2Text149 = '此工作流无法使用所选的任何文件。';
// / 'Too many files are selected for one workflow.'
$Gui2Text150 = '为一个工作流选择的文件过多。';
// / 'The server is busy. Try again in a few minutes.'
$Gui2Text151 = '服务器繁忙。请几分钟后重试。';
// / 'A workflow is already running in this session.'
$Gui2Text152 = '此会话中已有工作流正在运行。';
// / 'The workflow could not be started.'
$Gui2Text153 = '无法启动工作流。';
// / 'A step of the workflow failed.'
$Gui2Text154 = '工作流的某个步骤失败。';
// / 'A virus was found, so the workflow stopped.'
$Gui2Text155 = '发现病毒，因此工作流已停止。';
// / 'The workflow ran out of time.'
$Gui2Text156 = '工作流已超时。';
// / 'The workflow made too many files.'
$Gui2Text157 = '工作流生成的文件过多。';
// / 'The server was too busy to finish the workflow.'
$Gui2Text158 = '服务器过于繁忙，无法完成工作流。';
// / 'An operation this workflow needs is not available.'
$Gui2Text159 = '此工作流所需的某项操作不可用。';
// / 'The workflow stopped unexpectedly.'
$Gui2Text160 = '工作流意外停止。';
// / The words in braces, such as {step} & {kind}, are filled in by the page. Each translation keeps every one of them.
// / 'Pipelines'
$Gui2Text161 = '管道';
// / 'Select a pipeline above...'
$Gui2Text162 = '请在上方选择一个管道...';
// / 'Select Pipeline...'
$Gui2Text163 = '选择管道...';
// / 'Select Files...'
$Gui2Text164 = '选择文件...';
// / 'Add a step'
$Gui2Text165 = '添加步骤';
// / 'Options'
$Gui2Text166 = '选项';
// / 'Back'
$Gui2Text167 = '返回';
// / 'Take: '
$Gui2Text168 = '取用：';
// / 'the ticked files'
$Gui2Text169 = '已勾选的文件';
// / 'the files step {step} made'
$Gui2Text170 = '步骤 {step} 生成的文件';
// / 'each file on its own'
$Gui2Text171 = '逐个处理每个文件';
// / 'all files together'
$Gui2Text172 = '一起处理所有文件';
// / 'Only these kinds: '
$Gui2Text173 = '仅这些类型：';
// / 'Only these extensions: '
$Gui2Text174 = '仅这些扩展名：';
// / 'Smallest size: '
$Gui2Text175 = '最小大小：';
// / 'Largest size: '
$Gui2Text176 = '最大大小：';
// / 'In the order they arrive'
$Gui2Text177 = '按到达顺序';
// / 'all files'
$Gui2Text178 = '所有文件';
// / 'all {kind}'
$Gui2Text179 = '所有{kind}';
// / 'Width: '
$Gui2Text180 = '宽度：';
// / 'Height: '
$Gui2Text181 = '高度：';
// / 'My workflow'
$Gui2Text182 = '我的工作流';
// / 'New'
$Gui2Text183 = '新建';
// / 'Import'
$Gui2Text184 = '导入';
// / 'Export'
$Gui2Text185 = '导出';
// / 'This workflow is not one this server can run.'
$Gui2Text186 = '此服务器无法运行此工作流。';
// / 'The file could not be read.'
$Gui2Text187 = '无法读取该文件。';
// / 'The workflow builder is not available on this server.'
$Gui2Text188 = '此服务器上不提供工作流生成器。';
// / 'Status'
$Gui2Text189 = '状态';
// / 'A workflow is running.'
$Gui2Text190 = '有工作流正在运行。';
// / 'No workflow is running.'
$Gui2Text191 = '没有正在运行的工作流。';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - HRC2-Functions.js Related Variables.
// / These strings are used by the client side javascript library.
// / That file is static javascript & cannot read a PHP variable, so every string it needs
// / is passed to it as an argument by the PHP that calls it.
// / 'Your browser does not support copying to the clipboard!'
$GuiFunctionsText1 = 'आपका ब्राउज़र क्लिपबोर्ड पर कॉपी करने का समर्थन नहीं करता।';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy'
$GuiFooterText1 = '查看我们的<a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>服务条款</a>和<a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>隐私政策';
// / -----------------------------------------------------------------------------------