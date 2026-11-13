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
// / v3.8.2.
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
$LanguageVersion = 'v4.1.8';
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
$CoreError = 'ERROR!!! HRConvert2-2, This file cannot process your request! Please submit your file to convertCore.php instead!';
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die($CoreError);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Check for required core variables.
if (!isset($Font)) $Font = 'Arial';
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = 'Convert Anything!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI Related Logic.
if (!is_numeric($FileCount)) $FileCount = 'an unknown number of';
$FCPlural1 = 's';
$FCPlural2 = 's are';
if ($FileCount == 1) {
  $FCPlural1 = '';
  $FCPlural2 = ' is'; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Header Related Variables.
// / 'Click, Tap, or Drop files here to upload.'
$GuiHeaderText1 = 'Click, Tap, or Drop files here to upload.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - UI Selector Bar Related Variables.
// / These strings appear in the selector bar, which is present on both GUI1 & GUI2.
// / 'Language'
$GuiSelectorText1 = 'Language';
// / 'Color'
$GuiSelectorText2 = 'Color';
// / 'Interface'
$GuiSelectorText3 = 'Interface';
// / 'Display language, color and interface options'
$GuiSelectorText4 = 'Display language, color and interface options';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / 'Online File Converter, Extractor, Compressor'
$Gui1Text1 = 'Online File Converter, Extractor, Compressor';
// / $ApplicationName.' is based off the open-source web-app <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> by <a href=\'https://github.com/zelon88\'>Zelon88</a> that converts files without tracking users across the net or infringing on your intellectual property.'
$Gui1Text2 = $ApplicationName.' is based off the open-source web-app <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> by <a href=\'https://github.com/zelon88\'>Zelon88</a> that converts files without tracking users across the net or infringing on your intellectual property.';
// / 'More Info ...'
$Gui1Text3 = 'More Info ...';
// / 'Less Info ...'
$Gui1Text4 = 'Less Info ...';
// / 'All user-supplied data is erased automatically, so you don\'t need to worry about forfeiting your personal information or property while using our services.'
$Gui1Text5 = 'All user-supplied data is erased automatically, so you don\'t need to worry about forfeiting your personal information or property while using our services.';
// / 'Currently '.$ApplicationName.' supports '.$SupportedFormatCount.' different file formats, including documents, spreadsheets, images, media, 3D models, CAD drawings, vector files, archives, disk images, & more.'
$Gui1Text6 = 'Currently '.$ApplicationName.' supports '.$SupportedFormatCount.' different file formats, including documents, spreadsheets, images, media, 3D models, CAD drawings, vector files, archives, disk images, & more.';
// / 'View Supported Formats ...'
$Gui1Text7 = 'View Supported Formats ...';
// / 'Hide Supported Formats ...'
$Gui1Text8 = 'Hide Supported Formats ...';
// / 'Supported Formats'
$Gui1Text9 = 'Supported Formats';
// / 'Audio Formats'
$Gui1Text10 = 'Audio Formats';
// / 'Supports specific bitrate.'
$Gui1Text11 = 'Supports specific bitrate.';
// / 'Video Formats'
$Gui1Text12 = 'Video Formats';
// / 'Stream Formats'
$Gui1Text13 = 'Stream Formats';
// / 'Document Formats'
$Gui1Text14 = 'Document Formats';
// / 'Spreadsheet Formats'
$Gui1Text15 = 'Spreadsheet Formats';
// / 'Presentation Formats'
$Gui1Text16 = 'Presentation Formats';
// / 'Archive Formats'
$Gui1Text17 = 'Archive Formats';
// / 'Can convert between archive formats & disk image formats.'
$Gui1Text18 = 'Can convert between select archive formats & disk image formats.';
// / 'Image Formats'
$Gui1Text19 = 'Image Formats';
// / 'Can convert pictures of documents to document formats.'
$Gui1Text20 = 'Can convert pictures of documents to document formats.';
// / 'Supports resize & rotate.'
$Gui1Text21 = 'Supports resize & rotate.';
// / '3D Model Formats'
$Gui1Text22 = '3D Model Formats';
// / 'Drawing Formats'
$Gui1Text23 = 'Drawing Formats';
// / 'Can convert drawing files to image formats.'
$Gui1Text24 = 'Can convert drawing formats to image formats.';
// / 'OCR Support'
$Gui1Text25 = 'OCR Support';
// / 'OCR Operations support the following input formats...'
$Gui1Text26 = 'OCR Operations support the following input formats...';
// / 'OCR Operations support the following output formats...'
$Gui1Text27 = 'OCR Operations support the following output formats...';
// / 'Select files by clicking, tapping, or dropping them into the box below.'
$Gui1Text28 = 'Select files by clicking, tapping, or dropping them into the box below.';
// / 'Continue ...'
$Gui1Text29 = 'Continue ...';
// / 'Can convert stream formats to video formats.'
$Gui1Text30 = 'Can convert stream formats to video formats.';
// / 'Subtitle Formats'
$Gui1Text31 = 'Subtitle Formats';
// / 'OpenSCAD Formats'
$Gui1Text32 = 'OpenSCAD Formats';
// / 'Renders OpenSCAD source into 3D model formats.'
$Gui1Text33 = 'Renders OpenSCAD source into 3D model formats.';
// / 'File references inside uploaded sources are removed unless the server allows resolving them.'
$Gui1Text34 = 'File references inside uploaded sources are removed unless the server allows resolving them.';
// / 'Delete every uploaded file & start a new session?'
// / Shown inside the start over panel on the upload page. That panel is only rendered
// / when the session already holds at least one file, so this is never shown to a first
// / time visitor who has nothing to lose.
$Gui1Text35 = 'Delete every uploaded file & start a new session?';
// / 'Start Over'
// / Labels the control that opens the panel & the button inside it that confirms.
$Gui1Text36 = 'Start Over';
// / 'Refresh'
// / Alternate text for the refresh control on the upload page. That control is a glyph
// / with no text of its own, so this is the only description a screen reader has & the
// / only thing shown when a browser cannot render the glyph.
$Gui1Text37 = 'Refresh';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'File Conversion Options';
// / 'Download'
$Gui2Text5 = 'Download';
// / 'Share'
$Gui2Text6 = 'Share';
// / 'Close Share Options'
$Gui2Text7 = 'Close Share Options';
// / 'Virus Scan'
$Gui2Text8 = 'Virus Scan';
// / 'Close Virus Scan Options'
$Gui2Text9 = 'Close Virus Scan Options';
// / 'Archive'
$Gui2Text10 = 'Archive';
// / 'Close Archive Options'
$Gui2Text11 = 'Close Archive Options';
// / 'OCR'
$Gui2Text12 = 'OCR';
// / 'Close OCR Options'
$Gui2Text13 = 'Close OCR Options';
// / 'Convert'
$Gui2Text14 = 'Convert';
// / 'Close Convert Options'
$Gui2Text15 = 'Close Convert Options';
// / 'Archive This File'
$Gui2Text16 = 'Archive This File';
// / 'Specify Filename: '
$Gui2Text17 = 'Specify Filename: ';
// / 'Format'
$Gui2Text18 = 'Format';
// / 'Share This File'
$Gui2Text23 = 'Share This File';
// / 'Link Status: '
$Gui2Text24 = 'Link Status: ';
// / 'Not Generated'
$Gui2Text25 = 'Not Generated';
// / 'Generated'
$Gui2Text26 = 'Generated';
// / 'Clipboard Status: '
$Gui2Text27 = 'Clipboard Status: ';
// / 'Copied'
$Gui2Text28 = 'Copied';
// / 'File Link: '
$Gui2Text29 = 'File Link: ';
// / 'You have uploaded '.$FileCount.' valid file'.$FCPlural1.' to '.$ApplicationName.'.'
$Gui2Text30 = 'You have uploaded '.$FileCount.' valid file'.$FCPlural1.' to '.$ApplicationName.'.';
// / 'Your file'.$FCPlural2.' now ready to convert using the options below.'
$Gui2Text31 = 'Your file'.$FCPlural2.' now ready to convert using the options below.';
// / 'Generate Link & Copy to Clipboard'
$Gui2Text32 = 'Generate Link & Copy to Clipboard';
// / 'Generate Link'
$Gui2Text33 = 'Generate Link';
// / 'Scan This File For Viruses'
$Gui2Text34 = 'Scan This File For Viruses';
// / 'Scan File With ScanCore'
$Gui2Text35 = 'Scan File With ScanCore';
// / 'Scan File With ClamAV'
$Gui2Text36 = 'Scan File With ClamAV';
// / 'Scan File With ScanCore & ClamAV'
$Gui2Text37 = 'Scan File With ScanCore & ClamAV';
// / 'Perform Optical Character Recognition On This File'
$Gui2Text38 = 'Perform Optical Character Recognition On This File';
// / 'Method'
$Gui2Text39 = 'Method';
// / 'Simple'
$Gui2Text40 = 'Simple';
// / 'Advanced'
$Gui2Text41 = 'Advanced';
// / 'Convert This Archive'
$Gui2Text42 = 'Convert This Archive';
// / 'Convert This Document'
$Gui2Text43 = 'Convert This Document';
// / 'Convert This Spreadsheet'
$Gui2Text44 = 'Convert This Spreadsheet';
// / 'Convert This Audio'
$Gui2Text45 = 'Convert This Audio';
// / 'Convert This Video'
$Gui2Text46 = 'Convert This Video';
// / 'Convert This Stream'
$Gui2Text47 = 'Convert This Stream';
// / 'Convert This 3D Model'
$Gui2Text48 = 'Convert This 3D Model';
// / 'Convert This Technical Drawing Or Vector File'
$Gui2Text49 = 'Convert This Technical Drawing Or Vector File';
// / 'Convert This Image'
$Gui2Text50 = 'Convert This Image';
// / 'Archive File'
$Gui2Text51 = 'Archive File';
// / 'Convert Into Document'
$Gui2Text52 = 'Convert Into Document';
// / 'Archive Files'
$Gui2Text53 = 'Archive Files';
// / 'Convert Document'
$Gui2Text54 = 'Convert Document';
// / 'Convert Spreadsheet'
$Gui2Text55 = 'Convert Spreadsheet';
// / 'Convert Presentation'
$Gui2Text56 = 'Convert Presentation';
// / 'Convert Audio'
$Gui2Text57 = 'Convert Audio';
// / 'Convert Video'
$Gui2Text58 = 'Convert Video';
// / 'Convert Stream'
$Gui2Text59 = 'Convert Stream';
// / 'Convert Model'
$Gui2Text60 = 'Convert Model';
// / 'Convert Drawing'
$Gui2Text61 = 'Convert Drawing';
// / 'Convert Image'
$Gui2Text62 = 'Convert Image';
// / 'Width & Height'
$Gui2Text64 = 'Width & Height: ';
// / 'Rotate: '
$Gui2Text65 = 'Rotate: ';
// / 'Bitrate: '
$Gui2Text66 = 'Bitrate: ';
// / 'Delete'
$Gui2Text67 = 'Delete';
// / 'Close Delete Options'
$Gui2Text68 = 'Close Delete Options';
// / 'Delete This File'
$Gui2Text69 = 'Delete This File';
// / 'Confirm Delete'
$Gui2Text70 = 'Confirm Delete';
// / 'Cannot convert this file! Try changing the name.'
$Gui2Text71 = 'Cannot convert this file! Try changing the name.';
// / 'Cannot perform a virus scan on this file!'
$Gui2Text72 = 'Cannot perform a virus scan on this file!';
// / 'File Link Copied to Clipboard!'
$Gui2Text73 = 'File Link Copied to Clipboard!';
// / 'Operation Failed!'
$Gui2Text74 = 'Operation Failed!';
// / 'Convert These Subtitles'
$Gui2Text75 = 'Convert These Subtitles';
// / 'Convert Subtitles'
$Gui2Text76 = 'Convert Subtitles';
// / 'Convert This Presentation'
$Gui2Text77 = 'Convert This Presentation';
// / 'Convert This XPS File'
$Gui2Text78 = 'Convert This XPS File';
// / 'Render This OpenSCAD Model'
$Gui2Text79 = 'Render This OpenSCAD Model';
// / 'Render Model'
$Gui2Text80 = 'Render Model';
// / 'Convert This E-Book'
$Gui2Text81 = 'Convert This E-Book';
// / 'Convert E-Book'
$Gui2Text82 = 'Convert E-Book';
// / 'Playlist source URL, only needed if the playlist uses relative paths'
$Gui2Text83 = 'Playlist source URL, only needed if the playlist uses relative paths';
// / 'Download A File From A URL'
$Gui2Text84 = 'Download A File From A URL';
// / 'Fetch'
$Gui2Text85 = 'Fetch';
// / 'Identify'
$Gui2Text86 = 'Identify';
// / 'Close Identify Options'
$Gui2Text87 = 'Close Identify Options';
// / 'Identify This File'
$Gui2Text88 = 'Identify This File';
// / 'Size: '
$Gui2Text89 = 'Size: ';
// / 'Modified: '
$Gui2Text90 = 'Modified: ';
// / 'Type: '
$Gui2Text91 = 'Type: ';
// / 'MIME Type: '
$Gui2Text92 = 'MIME Type: ';
// / 'Character Set: '
$Gui2Text93 = 'Character Set: ';
// / 'Extension Matches Content: '
$Gui2Text94 = 'Extension Matches Content: ';
// / 'Details: '
$Gui2Text95 = 'Details: ';
// / 'Notes: '
$Gui2Text96 = 'Notes: ';
// / 'Save report as file'
$Gui2Text97 = 'Save report as file';
// / 'Yes'
$Gui2Text100 = 'Yes';
// / 'No'
$Gui2Text101 = 'No';
// / 'Unknown'
$Gui2Text102 = 'Unknown';
// / 'Cannot identify this file!'
$Gui2Text103 = 'Cannot identify this file!';
// / 'Sort By: '
$Gui2Text104 = 'Sort By: ';
// / 'Name'
$Gui2Text105 = 'Name';
// / 'Size'
$Gui2Text106 = 'Size';
// / 'Date'
$Gui2Text107 = 'Date';
// / 'Type'
$Gui2Text108 = 'Type';
// / The words in braces, such as {fit} & {count}, are filled in by the page. Each translation keeps every one of them.
// / 'Select'
$Gui2Text109 = 'Select';
// / 'Select All'
$Gui2Text110 = 'Select All';
// / 'Select None'
$Gui2Text111 = 'Select None';
// / 'Select By Type'
$Gui2Text112 = 'Select By Type';
// / 'Selected: '
$Gui2Text113 = 'Selected: ';
// / 'Documents'
$Gui2Text114 = 'Documents';
// / 'Spreadsheets'
$Gui2Text115 = 'Spreadsheets';
// / 'Presentations'
$Gui2Text116 = 'Presentations';
// / 'Images'
$Gui2Text117 = 'Images';
// / 'Audio'
$Gui2Text118 = 'Audio';
// / 'Video'
$Gui2Text119 = 'Video';
// / 'Archives'
$Gui2Text120 = 'Archives';
// / 'E-Books'
$Gui2Text121 = 'E-Books';
// / '3D Models'
$Gui2Text122 = '3D Models';
// / 'Drawings'
$Gui2Text123 = 'Drawings';
// / 'Vector Images'
$Gui2Text124 = 'Vector Images';
// / 'Subtitles'
$Gui2Text125 = 'Subtitles';
// / 'OpenSCAD Files'
$Gui2Text126 = 'OpenSCAD Files';
// / 'Streams'
$Gui2Text127 = 'Streams';
// / 'Workflows'
$Gui2Text128 = 'Workflows';
// / 'Selected Files'
$Gui2Text129 = 'Selected Files';
// / 'Select files in the list below.'
$Gui2Text130 = 'Select files in the list below.';
// / 'Select By Name: '
$Gui2Text131 = 'Select By Name: ';
// / 'Use * and ? as wildcards, or write a regular expression between slashes.'
$Gui2Text132 = 'Use * and ? as wildcards, or write a regular expression between slashes.';
// / 'Workflow'
$Gui2Text133 = 'Workflow';
// / 'Choose a workflow'
$Gui2Text134 = 'Choose a workflow';
// / 'Run'
$Gui2Text135 = 'Run';
// / 'Stop'
$Gui2Text136 = 'Stop';
// / 'Selected files that can be used: {fit} of {count}.'
$Gui2Text137 = 'Selected files that can be used: {fit} of {count}.';
// / 'Step {step} of {steps}: {operation}'
$Gui2Text138 = 'Step {step} of {steps}: {operation}';
// / 'File {done} of {total}'
$Gui2Text139 = 'File {done} of {total}';
// / 'Waiting for the server.'
$Gui2Text140 = 'Waiting for the server.';
// / 'Starting.'
$Gui2Text141 = 'Starting.';
// / 'Finished.'
$Gui2Text142 = 'Finished.';
// / 'Stopping.'
$Gui2Text143 = 'Stopping.';
// / 'Stopped.'
$Gui2Text144 = 'Stopped.';
// / 'Files made: '
$Gui2Text145 = 'Files made: ';
// / 'Workflows are not available on this server.'
$Gui2Text146 = 'Workflows are not available on this server.';
// / 'No workflows are installed on this server.'
$Gui2Text147 = 'No workflows are installed on this server.';
// / 'That workflow is no longer available.'
$Gui2Text148 = 'That workflow is no longer available.';
// / 'None of the selected files can be used by this workflow.'
$Gui2Text149 = 'None of the selected files can be used by this workflow.';
// / 'Too many files are selected for one workflow.'
$Gui2Text150 = 'Too many files are selected for one workflow.';
// / 'The server is busy. Try again in a few minutes.'
$Gui2Text151 = 'The server is busy. Try again in a few minutes.';
// / 'A workflow is already running in this session.'
$Gui2Text152 = 'A workflow is already running in this session.';
// / 'The workflow could not be started.'
$Gui2Text153 = 'The workflow could not be started.';
// / 'A step of the workflow failed.'
$Gui2Text154 = 'A step of the workflow failed.';
// / 'A virus was found, so the workflow stopped.'
$Gui2Text155 = 'A virus was found, so the workflow stopped.';
// / 'The workflow ran out of time.'
$Gui2Text156 = 'The workflow ran out of time.';
// / 'The workflow made too many files.'
$Gui2Text157 = 'The workflow made too many files.';
// / 'The server was too busy to finish the workflow.'
$Gui2Text158 = 'The server was too busy to finish the workflow.';
// / 'An operation this workflow needs is not available.'
$Gui2Text159 = 'An operation this workflow needs is not available.';
// / 'The workflow stopped unexpectedly.'
$Gui2Text160 = 'The workflow stopped unexpectedly.';
// / The words in braces, such as {step} & {kind}, are filled in by the page. Each translation keeps every one of them.
// / 'Available Pipelines'
$Gui2Text161 = 'Available Pipelines';
// / 'Select a pipeline above...'
$Gui2Text162 = 'Select a pipeline above...';
// / 'Select Pipeline...'
$Gui2Text163 = 'Select Pipeline...';
// / 'Select Files...'
$Gui2Text164 = 'Select Files...';
// / 'Add a step'
$Gui2Text165 = 'Add a step';
// / 'Options'
$Gui2Text166 = 'Options';
// / 'Back'
$Gui2Text167 = 'Back';
// / 'Take: '
$Gui2Text168 = 'Take: ';
// / 'the selected files'
$Gui2Text169 = 'the selected files';
// / 'the files step {step} made'
$Gui2Text170 = 'the files step {step} made';
// / 'each file on its own'
$Gui2Text171 = 'each file on its own';
// / 'all files together'
$Gui2Text172 = 'all files together';
// / 'Only these kinds: '
$Gui2Text173 = 'Only these kinds: ';
// / 'Only these extensions: '
$Gui2Text174 = 'Only these extensions: ';
// / 'Smallest size: '
$Gui2Text175 = 'Smallest size: ';
// / 'Largest size: '
$Gui2Text176 = 'Largest size: ';
// / 'In the order they arrive'
$Gui2Text177 = 'In the order they arrive';
// / 'all files'
$Gui2Text178 = 'all files';
// / 'all {kind}'
$Gui2Text179 = 'all {kind}';
// / 'Width: '
$Gui2Text180 = 'Width: ';
// / 'Height: '
$Gui2Text181 = 'Height: ';
// / 'My workflow'
$Gui2Text182 = 'My workflow';
// / 'New'
$Gui2Text183 = 'New';
// / 'Import'
$Gui2Text184 = 'Import';
// / 'Export'
$Gui2Text185 = 'Export';
// / 'This workflow is not one this server can run.'
$Gui2Text186 = 'This workflow is not one this server can run.';
// / 'The file could not be read.'
$Gui2Text187 = 'The file could not be read.';
// / 'The workflow builder is not available on this server.'
$Gui2Text188 = 'The workflow builder is not available on this server.';
// / 'Status'
$Gui2Text189 = 'Status';
// / 'A workflow is running.'
$Gui2Text190 = 'A workflow is running.';
// / 'No workflow is running.'
$Gui2Text191 = 'No workflow is running.';
// / 'Extract'
$Gui2Text192 = 'Extract';
// / 'Close Extract Options'
$Gui2Text193 = 'Close Extract Options';
// / 'Extract This Archive'
$Gui2Text194 = 'Extract This Archive';
// / 'Every file it holds is added to your files, under its own name or the next free one.'
$Gui2Text195 = 'Every file it holds is added to your files, under its own name or the next free one.';
// / 'Extract Files'
$Gui2Text196 = 'Extract Files';
// / 'to {format}'
$Gui2Text197 = 'to {format}';
// / 'This workflow deletes files. A deleted file cannot be brought back.'
$Gui2Text198 = 'This workflow deletes files. A deleted file cannot be brought back.';
// / 'Delete & Run'
$Gui2Text199 = 'Delete & Run';
// / 'Cancel'
$Gui2Text200 = 'Cancel';
// / 'Rename'
$Gui2Text201 = 'Rename';
// / 'Close Rename Options'
$Gui2Text202 = 'Close Rename Options';
// / 'Rename This File'
$Gui2Text203 = 'Rename This File';
// / 'New Name'
$Gui2Text204 = 'New Name';
// / 'Use {name} for the name it has, {n} for its place among the selected files & {date} for today. Its extension is kept.'
$Gui2Text205 = 'Use {name} for the name it has, {n} for its place among the selected files & {date} for today. Its extension is kept.';
// / 'Strip Metadata'
$Gui2Text206 = 'Strip Metadata';
// / 'Close Strip Metadata Options'
$Gui2Text207 = 'Close Strip Metadata Options';
// / 'Strip The Metadata From This File'
$Gui2Text208 = 'Strip The Metadata From This File';
// / 'A copy is made beside it without its authors, places, dates & the software that made it. What it shows is left as it was.'
$Gui2Text209 = 'A copy is made beside it without its authors, places, dates & the software that made it. What it shows is left as it was.';
// / 'Merge PDFs'
$Gui2Text210 = 'Merge PDFs';
// / 'Split PDF'
$Gui2Text211 = 'Split PDF';
// / 'Merge Or Split'
$Gui2Text212 = 'Merge Or Split';
// / 'Close Merge & Split Options'
$Gui2Text213 = 'Close Merge & Split Options';
// / 'Merge The Selected PDFs Into One'
$Gui2Text214 = 'Merge The Selected PDFs Into One';
// / 'Their pages follow one another in the order the PDFs were selected.'
$Gui2Text215 = 'Their pages follow one another in the order the PDFs were selected.';
// / 'Select two or more PDFs to merge them.'
$Gui2Text216 = 'Select two or more PDFs to merge them.';
// / 'Make A PDF Of Each Page'
$Gui2Text217 = 'Make A PDF Of Each Page';
// / 'Every selected file is deleted with it: {count} files.'
$Gui2Text218 = 'Every selected file is deleted with it: {count} files.';
// / 'Every selected file is renamed with it: {count} files.'
$Gui2Text219 = 'Every selected file is renamed with it: {count} files.';
// / 'Library'
$Gui2Text220 = 'Library';
// / 'from Selected Files'
$Gui2Text221 = 'from Selected Files';
// / 'from Step {step}'
$Gui2Text222 = 'from Step {step}';
// / 'Preview'
$Gui2Text223 = 'Preview';
// / 'If the workflow ran now, each step would take:'
$Gui2Text224 = 'If the workflow ran now, each step would take:';
// / 'Left out: '
$Gui2Text225 = 'Left out: ';
// / 'No files.'
$Gui2Text226 = 'No files.';
// / 'every file in the list when it runs'
$Gui2Text227 = 'every file in the list when it runs';
// / 'This workflow deletes these files. A deleted file cannot be brought back.'
$Gui2Text228 = 'This workflow deletes these files. A deleted file cannot be brought back.';
// / 'A step had errors, so the workflow stopped before the step that deletes files.'
$Gui2Text229 = 'A step had errors, so the workflow stopped before the step that deletes files.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - HRC2-Functions.js Related Variables.
// / These strings are used by the client side javascript library.
// / That file is static javascript & cannot read a PHP variable, so every string it needs
// / is passed to it as an argument by the PHP that calls it.
// / 'Your browser does not support copying to the clipboard!'
$GuiFunctionsText1 = 'Your browser does not support copying to the clipboard!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy'
$GuiFooterText1 = 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy';
// / -----------------------------------------------------------------------------------