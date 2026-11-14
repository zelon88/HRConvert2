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
$CoreError = 'ERROR!!! HRConvert2-2, 이 파일은 귀하의 요청을 처리할 수 없습니다! 대신 ConvertCore.php에 파일을 제출하세요!';
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die($CoreError);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Check for required core variables.
if (!isset($Font)) $Font = 'Arial';
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = '무엇이든 변환하세요!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI Related Logic.
if (!is_numeric($FileCount)) $FileCount = '알 수 없는 수';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Header Related Variables.
// / 'Click, Tap, or Drop files here to upload.'
$GuiHeaderText1 = '업로드하려면 여기에서 파일을 클릭하거나 탭하거나 드롭하세요.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - UI Selector Bar Related Variables.
// / These strings appear in the selector bar, which is present on both GUI1 & GUI2.
// / 'Language'
$GuiSelectorText1 = '언어';
// / 'Color'
$GuiSelectorText2 = '색상';
// / 'Interface'
$GuiSelectorText3 = '인터페이스';
// / 'Display language, color and interface options'
$GuiSelectorText4 = '언어, 색상 및 인터페이스 옵션 표시';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / 'Online File Converter, Extractor, Compressor'
$Gui1Text1 = '온라인 파일 변환기, 추출기, 압축기';
// / $ApplicationName.' is based off the open-source web-app <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> by <a href=\'https://github.com/zelon88\'>Zelon88</a> that converts files without tracking users across the net or infringing on your intellectual property.'
$Gui1Text2 = $ApplicationName.' <a href=\'https://github.com/zelon88\'>Zelon88</a>의 오픈 소스 웹 앱 <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a>를 기반으로 합니다. 네트워크를 통해 사용자를 추적하거나 지적 재산권을 침해하지 않고 파일을 변환합니다.';
// / 'More Info ...'
$Gui1Text3 = '더 많은 정보 ...';
// / 'Less Info ...'
$Gui1Text4 = '정보 간략히 ...';
// / 'All user-supplied data is erased automatically, so you don\'t need to worry about forfeiting your personal information or property while using our services.'
$Gui1Text5 = '사용자가 제공한 모든 데이터는 자동으로 삭제되므로 서비스를 사용하는 동안 개인정보나 재산이 손실될까 걱정할 필요가 없습니다.';
// / 'Currently '.$ApplicationName.' supports '.$SupportedFormatCount.' different file formats, including documents, spreadsheets, images, media, 3D models, CAD drawings, vector files, archives, disk images, & more.'
$Gui1Text6 = '현재 '.$ApplicationName.' '.$SupportedFormatCount.'를 지원합니다. 문서, 스프레드시트, 이미지, 미디어, 3D 모델, CAD 도면, 벡터 파일, 아카이브, 디스크 이미지 등을 포함한 다양한 파일 형식.';
// / 'View Supported Formats ...'
$Gui1Text7 = '지원되는 형식 보기...';
// / 'Hide Supported Formats ...'
$Gui1Text8 = '지원되는 형식 숨기기 ...';
// / 'Supported Formats'
$Gui1Text9 = '지원되는 형식';
// / 'Audio Formats'
$Gui1Text10 = '오디오 형식';
// / 'Supports specific bitrate.'
$Gui1Text11 = '특정 비트 전송률을 지원합니다.';
// / 'Video Formats'
$Gui1Text12 = '비디오 형식';
// / 'Stream Formats'
$Gui1Text13 = '스트림 형식';
// / 'Document Formats'
$Gui1Text14 = '문서 형식';
// / 'Spreadsheet Formats'
$Gui1Text15 = '스프레드시트 형식';
// / 'Presentation Formats'
$Gui1Text16 = '프레젠테이션 형식';
// / 'Archive Formats'
$Gui1Text17 = '아카이브 형식';
// / 'Can convert between archive formats & disk image formats.'
$Gui1Text18 = '선택한 아카이브 형식과 디스크 이미지 형식 간에 변환할 수 있습니다.';
// / 'Image Formats'
$Gui1Text19 = '이미지 형식';
// / 'Can convert pictures of documents to document formats.'
$Gui1Text20 = '문서 사진을 문서 형식으로 변환할 수 있습니다.';
// / 'Supports resize & rotate.'
$Gui1Text21 = '크기 조정 및 회전을 지원합니다.';
// / '3D Model Formats'
$Gui1Text22 = '3D 모델 형식';
// / 'Drawing Formats'
$Gui1Text23 = '도면 형식';
// / 'Can convert drawing files to image formats.'
$Gui1Text24 = '도면 형식을 이미지 형식으로 변환할 수 있습니다.';
// / 'OCR Support'
$Gui1Text25 = '광학 문자 인식 지원';
// / 'OCR Operations support the following input formats...'
$Gui1Text26 = '광학 문자 인식 작업은 다음 입력 형식을 지원합니다...';
// / 'OCR Operations support the following output formats...'
$Gui1Text27 = '광학 문자 인식 작업은 다음 출력 형식을 지원합니다...';
// / 'Select files by clicking, tapping, or dropping them into the box below.'
$Gui1Text28 = '아래 상자에 파일을 클릭하거나 탭하거나 드롭하여 파일을 선택하세요.';
// / 'Continue ...'
$Gui1Text29 = '계속하다 ...';
// / 'Can convert stream formats to video formats.'
$Gui1Text30 = '스트림 형식을 비디오 형식으로 변환할 수 있습니다.';
// / 'Subtitle Formats'
$Gui1Text31 = '자막 형식';
// / 'OpenSCAD Formats'
$Gui1Text32 = 'OpenSCAD 형식';
// / 'Renders OpenSCAD source into 3D model formats.'
$Gui1Text33 = 'OpenSCAD 소스 코드를 3D 모델 형식으로 변환합니다.';
// / 'File references inside uploaded sources are removed unless the server allows resolving them.'
$Gui1Text34 = '서버가 허용하지 않는 한 업로드된 소스 파일 내부의 파일 참조는 제거됩니다.';
// / 'Delete every uploaded file & start a new session?'
// / Shown inside the start over panel on the upload page. That panel is only rendered
// / when the session already holds at least one file, so this is never shown to a first
// / time visitor who has nothing to lose.
$Gui1Text35 = '업로드한 모든 파일을 삭제하고 새 세션을 시작할까요?';
// / 'Start Over'
// / Labels the control that opens the panel & the button inside it that confirms.
$Gui1Text36 = '다시 시작';
// / 'Refresh'
// / Alternate text for the refresh control on the upload page. That control is a glyph
// / with no text of its own, so this is the only description a screen reader has & the
// / only thing shown when a browser cannot render the glyph.
$Gui1Text37 = '새로 고침';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = '파일 변환 옵션';
// / 'Download'
$Gui2Text5 = '다운로드';
// / 'Share'
$Gui2Text6 = '공유하다';
// / 'Close Share Options'
$Gui2Text7 = '공유 옵션 닫기';
// / 'Virus Scan'
$Gui2Text8 = '바이러스 검사';
// / 'Close Virus Scan Options'
$Gui2Text9 = '바이러스 검사 옵션 닫기';
// / 'Archive'
$Gui2Text10 = '보관소';
// / 'Close Archive Options'
$Gui2Text11 = '아카이브 옵션 닫기';
// / 'OCR'
$Gui2Text12 = '광학 문자 인식';
// / 'Close OCR Options'
$Gui2Text13 = '광학 문자 인식 옵션 닫기';
// / 'Convert'
$Gui2Text14 = '전환하다';
// / 'Close Convert Options'
$Gui2Text15 = '변환 옵션 닫기';
// / 'Archive This File'
$Gui2Text16 = '이 파일 보관';
// / 'Specify Filename: '
$Gui2Text17 = '파일 이름 지정: ';
// / 'Format'
$Gui2Text18 = '체재';
// / 'Share This File'
$Gui2Text23 = '이 파일 공유';
// / 'Link Status: '
$Gui2Text24 = '링크 상태: ';
// / 'Not Generated'
$Gui2Text25 = '생성되지 않음';
// / 'Generated'
$Gui2Text26 = '생성됨';
// / 'Clipboard Status: '
$Gui2Text27 = '클립보드 상태: ';
// / 'Copied'
$Gui2Text28 = '복사됨';
// / 'File Link: '
$Gui2Text29 = '파일 링크: ';
// / 'You have uploaded '.$FileCount.' valid files to '.$ApplicationName.'.'
$Gui2Text30 = $FileCount.'개의 유효한 파일을 '.$ApplicationName.'에 업로드했습니다.';
// / 'Your file'.$FCPlural2.' now ready to convert using the options below.'
$Gui2Text31 = '이제 아래 옵션을 사용하여 파일을 변환할 준비가 되었습니다.';
// / 'Generate Link & Copy to Clipboard'
$Gui2Text32 = '링크 생성 및 클립보드에 복사';
// / 'Generate Link'
$Gui2Text33 = '링크 생성';
// / 'Scan This File For Viruses'
$Gui2Text34 = '이 파일에서 바이러스 검사';
// / 'Scan File With ScanCore'
$Gui2Text35 = 'ScanCore로 파일 스캔';
// / 'Scan File With ClamAV'
$Gui2Text36 = 'ClamAV로 파일 스캔';
// / 'Scan File With ScanCore & ClamAV'
$Gui2Text37 = 'ScanCore 및 ClamAV로 파일 스캔';
// / 'Perform Optical Character Recognition On This File'
$Gui2Text38 = '이 파일에서 광학 문자 인식을 수행합니다.';
// / 'Method'
$Gui2Text39 = '방법';
// / 'Simple'
$Gui2Text40 = '단순한';
// / 'Advanced'
$Gui2Text41 = '고급의';
// / 'Convert This Archive'
$Gui2Text42 = '이 아카이브를 변환하세요';
// / 'Convert This Document'
$Gui2Text43 = '이 문서 변환';
// / 'Convert This Spreadsheet'
$Gui2Text44 = '이 스프레드시트 변환';
// / 'Convert This Audio'
$Gui2Text45 = '이 오디오 변환';
// / 'Convert This Video'
$Gui2Text46 = '이 비디오를 변환하세요';
// / 'Convert This Stream'
$Gui2Text47 = '이 스트림을 변환하세요';
// / 'Convert This 3D Model'
$Gui2Text48 = '이 3차원 모델을 변환하세요';
// / 'Convert This Technical Drawing Or Vector File'
$Gui2Text49 = '이 기술 도면 또는 벡터 파일을 변환하세요';
// / 'Convert This Image'
$Gui2Text50 = '이 이미지 변환';
// / 'Archive File'
$Gui2Text51 = '아카이브 파일';
// / 'Convert Into Document'
$Gui2Text52 = '문서로 변환';
// / 'Archive Files'
$Gui2Text53 = '아카이브 파일';
// / 'Convert Document'
$Gui2Text54 = '문서 변환';
// / 'Convert Spreadsheet'
$Gui2Text55 = '스프레드시트 변환';
// / 'Convert Presentation'
$Gui2Text56 = '프레젠테이션 변환';
// / 'Convert Audio'
$Gui2Text57 = '오디오 변환';
// / 'Convert Video'
$Gui2Text58 = '비디오 변환';
// / 'Convert Stream'
$Gui2Text59 = '스트림 변환';
// / 'Convert Model'
$Gui2Text60 = '모델 변환';
// / 'Convert Drawing'
$Gui2Text61 = '도면 변환';
// / 'Convert Image'
$Gui2Text62 = '이미지 변환';
// / 'Width & Height'
$Gui2Text64 = '너비 및 높이: ';
// / 'Rotate: '
$Gui2Text65 = '회전: ';
// / 'Bitrate: '
$Gui2Text66 = '비트 전송률: ';
// / 'Delete'
$Gui2Text67 = '삭제';
// / 'Close Delete Options'
$Gui2Text68 = '삭제 옵션 닫기';
// / 'Delete This File'
$Gui2Text69 = '이 파일 삭제';
// / 'Confirm Delete'
$Gui2Text70 = '삭제 확인';
// / 'Cannot convert this file! Try changing the name.'
$Gui2Text71 = '이 파일을 변환할 수 없습니다! 이름을 바꿔보세요.';
// / 'Cannot perform a virus scan on this file!'
$Gui2Text72 = '이 파일에 대해 바이러스 검사를 수행할 수 없습니다!';
// / 'File Link Copied to Clipboard!'
$Gui2Text73 = '파일 링크가 클립보드에 복사되었습니다!';
// / 'Operation Failed!'
$Gui2Text74 = '작업이 실패했습니다!';
// / 'Convert These Subtitles'
$Gui2Text75 = '이 자막 변환';
// / 'Convert Subtitles'
$Gui2Text76 = '자막 변환';
// / 'Convert This Presentation'
$Gui2Text77 = '이 프레젠테이션 변환하기';
// / 'Convert This XPS File'
$Gui2Text78 = 'C이 파일을 변환하세요';
// / 'Render This OpenSCAD Model'
$Gui2Text79 = '이 OpenSCAD 모델 렌더링';
// / 'Render Model'
$Gui2Text80 = '모델 렌더링';
// / 'Convert This E-Book'
$Gui2Text81 = '이 전자책 변환';
// / 'Convert E-Book'
$Gui2Text82 = '전자책 변환';
// / 'Playlist source URL, only needed if the playlist uses relative paths'
$Gui2Text83 = '재생목록 원본 URL. 상대 경로를 사용할 때만 필요합니다';
// / 'Download A File From A URL'
$Gui2Text84 = 'URL에서 파일 다운로드';
// / 'Fetch'
$Gui2Text85 = '가져오기';
// / 'Identify'
$Gui2Text86 = '식별';
// / 'Close Identify Options'
$Gui2Text87 = '식별 옵션 닫기';
// / 'Identify This File'
$Gui2Text88 = '이 파일 식별';
// / 'Size: '
$Gui2Text89 = '크기: ';
// / 'Modified: '
$Gui2Text90 = '수정 날짜: ';
// / 'Type: '
$Gui2Text91 = '형식: ';
// / 'MIME Type: '
$Gui2Text92 = 'MIME 형식: ';
// / 'Character Set: '
$Gui2Text93 = '문자 집합: ';
// / 'Extension Matches Content: '
$Gui2Text94 = '확장자와 내용 일치: ';
// / 'Details: '
$Gui2Text95 = '세부 정보: ';
// / 'Notes: '
$Gui2Text96 = '참고: ';
// / 'Save report as file'
$Gui2Text97 = '보고서를 파일로 저장';
// / 'Yes'
$Gui2Text100 = '예';
// / 'No'
$Gui2Text101 = '아니요';
// / 'Unknown'
$Gui2Text102 = '알 수 없음';
// / 'Cannot identify this file!'
$Gui2Text103 = '이 파일을 식별할 수 없습니다!';
// / 'Sort By: '
$Gui2Text104 = '정렬 기준: ';
// / 'Name'
$Gui2Text105 = '이름';
// / 'Size'
$Gui2Text106 = '크기';
// / 'Date'
$Gui2Text107 = '날짜';
// / 'Type'
$Gui2Text108 = '형식';
// / The words in braces, such as {fit} & {count}, are filled in by the page. Each translation keeps every one of them.
// / 'Select'
$Gui2Text109 = '선택';
// / 'Select All'
$Gui2Text110 = '모두 선택';
// / 'Select None'
$Gui2Text111 = '선택 해제';
// / 'Select By Type'
$Gui2Text112 = '형식별 선택';
// / 'Selected: '
$Gui2Text113 = '선택됨: ';
// / 'Documents'
$Gui2Text114 = '문서';
// / 'Spreadsheets'
$Gui2Text115 = '스프레드시트';
// / 'Presentations'
$Gui2Text116 = '프레젠테이션';
// / 'Images'
$Gui2Text117 = '이미지';
// / 'Audio'
$Gui2Text118 = '오디오';
// / 'Video'
$Gui2Text119 = '비디오';
// / 'Archives'
$Gui2Text120 = '아카이브';
// / 'E-Books'
$Gui2Text121 = '전자책';
// / '3D Models'
$Gui2Text122 = '3D 모델';
// / 'Drawings'
$Gui2Text123 = '도면';
// / 'Vector Images'
$Gui2Text124 = '벡터 이미지';
// / 'Subtitles'
$Gui2Text125 = '자막';
// / 'OpenSCAD Files'
$Gui2Text126 = 'OpenSCAD 파일';
// / 'Streams'
$Gui2Text127 = '스트림';
// / 'Workflows'
$Gui2Text128 = '워크플로';
// / 'Selected Files'
$Gui2Text129 = '선택한 파일';
// / 'Select files in the list below.'
$Gui2Text130 = '아래 목록에서 파일을 선택하세요.';
// / 'Select By Name: '
$Gui2Text131 = '이름으로 선택: ';
// / 'Use * and ? as wildcards, or write a regular expression between slashes.'
$Gui2Text132 = '* 와 ? 를 와일드카드로 사용하거나, 슬래시 사이에 정규식을 입력하세요.';
// / 'Workflow'
$Gui2Text133 = '워크플로';
// / 'Choose a workflow'
$Gui2Text134 = '워크플로 선택';
// / 'Run'
$Gui2Text135 = '실행';
// / 'Stop'
$Gui2Text136 = '중지';
// / 'Selected files that can be used: {fit} of {count}.'
$Gui2Text137 = '사용할 수 있는 선택한 파일: {count}개 중 {fit}개.';
// / 'Step {step} of {steps}: {operation}'
$Gui2Text138 = '단계 {step}/{steps}: {operation}';
// / 'File {done} of {total}'
$Gui2Text139 = '파일 {done}/{total}';
// / 'Waiting for the server.'
$Gui2Text140 = '서버를 기다리는 중입니다.';
// / 'Starting.'
$Gui2Text141 = '시작하는 중입니다.';
// / 'Finished.'
$Gui2Text142 = '완료되었습니다.';
// / 'Stopping.'
$Gui2Text143 = '중지하는 중입니다.';
// / 'Stopped.'
$Gui2Text144 = '중지되었습니다.';
// / 'Files made: '
$Gui2Text145 = '만든 파일: ';
// / 'Workflows are not available on this server.'
$Gui2Text146 = '이 서버에서는 워크플로를 사용할 수 없습니다.';
// / 'No workflows are installed on this server.'
$Gui2Text147 = '이 서버에 설치된 워크플로가 없습니다.';
// / 'That workflow is no longer available.'
$Gui2Text148 = '해당 워크플로는 더 이상 사용할 수 없습니다.';
// / 'None of the selected files can be used by this workflow.'
$Gui2Text149 = '선택한 파일 중 이 워크플로에서 사용할 수 있는 파일이 없습니다.';
// / 'Too many files are selected for one workflow.'
$Gui2Text150 = '하나의 워크플로에 너무 많은 파일이 선택되었습니다.';
// / 'The server is busy. Try again in a few minutes.'
$Gui2Text151 = '서버가 사용 중입니다. 몇 분 후에 다시 시도하세요.';
// / 'A workflow is already running in this session.'
$Gui2Text152 = '이 세션에서 이미 워크플로가 실행 중입니다.';
// / 'The workflow could not be started.'
$Gui2Text153 = '워크플로를 시작할 수 없습니다.';
// / 'A step of the workflow failed.'
$Gui2Text154 = '워크플로의 단계가 실패했습니다.';
// / 'A virus was found, so the workflow stopped.'
$Gui2Text155 = '바이러스가 발견되어 워크플로가 중지되었습니다.';
// / 'The workflow ran out of time.'
$Gui2Text156 = '워크플로의 실행 시간이 초과되었습니다.';
// / 'The workflow made too many files.'
$Gui2Text157 = '워크플로가 너무 많은 파일을 만들었습니다.';
// / 'The server was too busy to finish the workflow.'
$Gui2Text158 = '서버가 너무 바빠 워크플로를 완료하지 못했습니다.';
// / 'An operation this workflow needs is not available.'
$Gui2Text159 = '이 워크플로에 필요한 작업을 사용할 수 없습니다.';
// / 'The workflow stopped unexpectedly.'
$Gui2Text160 = '워크플로가 예기치 않게 중지되었습니다.';
// / The words in braces, such as {step} & {kind}, are filled in by the page. Each translation keeps every one of them.
// / 'Available Pipelines'
$Gui2Text161 = '사용 가능한 파이프라인';
// / 'Select a pipeline above...'
$Gui2Text162 = '위에서 파이프라인을 선택하세요...';
// / 'Select Pipeline...'
$Gui2Text163 = '파이프라인 선택...';
// / 'Select Files...'
$Gui2Text164 = '파일 선택...';
// / 'Add a step'
$Gui2Text165 = '단계 추가';
// / 'Options'
$Gui2Text166 = '옵션';
// / 'Back'
$Gui2Text167 = '뒤로';
// / 'Take: '
$Gui2Text168 = '대상: ';
// / 'the selected files'
$Gui2Text169 = '선택한 파일';
// / 'the files step {step} made'
$Gui2Text170 = '{step}단계에서 만든 파일';
// / 'each file on its own'
$Gui2Text171 = '파일마다 따로';
// / 'all files together'
$Gui2Text172 = '모든 파일을 함께';
// / 'Only these kinds: '
$Gui2Text173 = '이 종류만: ';
// / 'Only these extensions: '
$Gui2Text174 = '이 확장자만: ';
// / 'Smallest size: '
$Gui2Text175 = '최소 크기: ';
// / 'Largest size: '
$Gui2Text176 = '최대 크기: ';
// / 'In the order they arrive'
$Gui2Text177 = '도착한 순서대로';
// / 'all files'
$Gui2Text178 = '모든 파일';
// / 'all {kind}'
$Gui2Text179 = '모든 {kind}';
// / 'Width: '
$Gui2Text180 = '너비: ';
// / 'Height: '
$Gui2Text181 = '높이: ';
// / 'My workflow'
$Gui2Text182 = '내 워크플로';
// / 'New'
$Gui2Text183 = '새로 만들기';
// / 'Import'
$Gui2Text184 = '가져오기';
// / 'Export'
$Gui2Text185 = '내보내기';
// / 'This workflow is not one this server can run.'
$Gui2Text186 = '이 서버에서는 이 워크플로를 실행할 수 없습니다.';
// / 'The file could not be read.'
$Gui2Text187 = '파일을 읽을 수 없습니다.';
// / 'The workflow builder is not available on this server.'
$Gui2Text188 = '이 서버에서는 워크플로 빌더를 사용할 수 없습니다.';
// / 'Status'
$Gui2Text189 = '상태';
// / 'A workflow is running.'
$Gui2Text190 = '워크플로가 실행 중입니다.';
// / 'No workflow is running.'
$Gui2Text191 = '실행 중인 워크플로가 없습니다.';
// / 'Extract'
$Gui2Text192 = '압축 풀기';
// / 'Close Extract Options'
$Gui2Text193 = '압축 풀기 옵션 닫기';
// / 'Extract This Archive'
$Gui2Text194 = '이 아카이브 압축 풀기';
// / 'Every file it holds is added to your files, under its own name or the next free one.'
$Gui2Text195 = '안에 든 모든 파일이 원래 이름이나 다음으로 비어 있는 이름으로 내 파일에 추가됩니다.';
// / 'Extract Files'
$Gui2Text196 = '파일 압축 풀기';
// / 'to {format}'
$Gui2Text197 = '{format}(으)로';
// / 'This workflow deletes files. A deleted file cannot be brought back.'
$Gui2Text198 = '이 워크플로는 파일을 삭제합니다. 삭제한 파일은 되돌릴 수 없습니다.';
// / 'Delete & Run'
$Gui2Text199 = '삭제하고 실행';
// / 'Cancel'
$Gui2Text200 = '취소';
// / 'Rename'
$Gui2Text201 = '이름 바꾸기';
// / 'Close Rename Options'
$Gui2Text202 = '이름 바꾸기 옵션 닫기';
// / 'Rename This File'
$Gui2Text203 = '이 파일 이름 바꾸기';
// / 'New Name'
$Gui2Text204 = '새 이름';
// / 'Use {name} for the name it has, {n} for its place among the selected files & {date} for today. Its extension is kept.'
$Gui2Text205 = '{name}은 지금 이름, {n}은 선택한 파일 중의 순서, {date}는 오늘 날짜입니다. 확장자는 그대로 유지됩니다.';
// / 'Strip Metadata'
$Gui2Text206 = '메타데이터 제거';
// / 'Close Strip Metadata Options'
$Gui2Text207 = '메타데이터 옵션 닫기';
// / 'Strip The Metadata From This File'
$Gui2Text208 = '이 파일의 메타데이터 제거';
// / 'A copy is made beside it without its authors, places, dates & the software that made it. What it shows is left as it was.'
$Gui2Text209 = '작성자, 장소, 날짜, 만든 소프트웨어를 뺀 사본을 옆에 만듭니다. 보이는 내용은 그대로입니다.';
// / 'Merge PDFs'
$Gui2Text210 = 'PDF 병합';
// / 'Split PDF'
$Gui2Text211 = 'PDF 분할';
// / 'Merge Or Split'
$Gui2Text212 = '병합 또는 분할';
// / 'Close Merge & Split Options'
$Gui2Text213 = '병합 및 분할 옵션 닫기';
// / 'Merge The Selected PDFs Into One'
$Gui2Text214 = '선택한 PDF를 하나로 병합';
// / 'Their pages follow one another in the order the PDFs were selected.'
$Gui2Text215 = '페이지는 PDF를 선택한 순서대로 이어집니다.';
// / 'Select two or more PDFs to merge them.'
$Gui2Text216 = '병합하려면 PDF를 두 개 이상 선택하세요.';
// / 'Make A PDF Of Each Page'
$Gui2Text217 = '페이지마다 PDF 만들기';
// / 'Every selected file is deleted with it: {count} files.'
$Gui2Text218 = '선택한 파일도 모두 함께 삭제됩니다: {count}개.';
// / 'Every selected file is renamed with it: {count} files.'
$Gui2Text219 = '선택한 파일도 모두 함께 이름이 바뀝니다: {count}개.';
// / 'Library'
$Gui2Text220 = '라이브러리';
// / 'from Selected Files'
$Gui2Text221 = '선택한 파일에서';
// / 'from Step {step}'
$Gui2Text222 = '{step}단계에서';
// / 'Preview'
$Gui2Text223 = '미리 보기';
// / 'If the workflow ran now, each step would take:'
$Gui2Text224 = '지금 워크플로를 실행하면 각 단계가 가져갈 파일:';
// / 'Left out: '
$Gui2Text225 = '제외: ';
// / 'No files.'
$Gui2Text226 = '파일 없음.';
// / 'every file in the list when it runs'
$Gui2Text227 = '단계가 실행될 때 목록에 있는 모든 파일';
// / 'This workflow deletes these files. A deleted file cannot be brought back.'
$Gui2Text228 = '이 워크플로는 다음 파일을 삭제합니다. 삭제한 파일은 되돌릴 수 없습니다.';
// / 'A step had errors, so the workflow stopped before the step that deletes files.'
$Gui2Text229 = '한 단계에서 오류가 발생하여 파일을 삭제하는 단계 전에 워크플로가 멈췄습니다.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - HRC2-Functions.js Related Variables.
// / These strings are used by the client side javascript library.
// / That file is static javascript & cannot read a PHP variable, so every string it needs
// / is passed to it as an argument by the PHP that calls it.
// / 'Your browser does not support copying to the clipboard!'
$GuiFunctionsText1 = '사용 중인 브라우저는 클립보드 복사를 지원하지 않습니다.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy'
$GuiFooterText1 = '<a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>서비스 약관</a> 및 를 확인하세요 <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>개인정보 보호정책';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Sign In Related Variables.
// / These strings are drawn by engineGui.php, the sign in page the Engine names, & by the sign in bar in the footer.
// / A sentence that names a number is written so it reads correctly for any count, so no plural helper is needed.
// / 'Sign in'
$GuiAuthText1 = '로그인';
// / 'Sign out'
$GuiAuthText2 = '로그아웃';
// / 'Create an account'
$GuiAuthText3 = '계정 만들기';
// / 'Username'
$GuiAuthText4 = '사용자 이름';
// / 'Password'
$GuiAuthText5 = '비밀번호';
// / 'Email address'
$GuiAuthText6 = '이메일 주소';
// / 'Code'
$GuiAuthText7 = '코드';
// / 'New password'
$GuiAuthText8 = '새 비밀번호';
// / 'Confirm your account'
$GuiAuthText9 = '계정 확인';
// / 'Recover your account'
$GuiAuthText10 = '계정 복구';
// / 'Choose a new password'
$GuiAuthText11 = '새 비밀번호 선택';
// / 'Send the code again'
$GuiAuthText12 = '코드 다시 보내기';
// / 'Forgot your password?'
$GuiAuthText13 = '비밀번호를 잊으셨나요?';
// / 'Have a code?'
$GuiAuthText14 = '코드가 있으신가요?';
// / 'Back to sign in'
$GuiAuthText15 = '로그인으로 돌아가기';
// / 'Continue without signing in'
$GuiAuthText16 = '로그인하지 않고 계속';
// / 'Signed in as'
$GuiAuthText17 = '로그인한 사용자:';
// / 'Signing in is unavailable on this server right now.'
$GuiAuthText18 = '지금은 이 서버에 로그인할 수 없습니다.';
// / 'Confirm'
$GuiAuthText19 = '확인';
// / 'Send a code'
$GuiAuthText20 = '코드 보내기';
// / 'Change password'
$GuiAuthText21 = '비밀번호 변경';
// / 'The username or password is incorrect.'
$GuiAuthText22 = '사용자 이름 또는 비밀번호가 올바르지 않습니다.';
// / 'Too many attempts. Please wait a few minutes and try again.'
$GuiAuthText23 = '시도 횟수가 너무 많습니다. 몇 분 후에 다시 시도하세요.';
// / 'Confirm your account with the code we emailed you first.'
$GuiAuthText24 = '먼저 이메일로 보내 드린 코드로 계정을 확인하세요.';
// / 'This account cannot sign in. Contact the administrator.'
$GuiAuthText25 = '이 계정은 로그인할 수 없습니다. 관리자에게 문의하세요.';
// / 'New accounts cannot be created here. Contact the administrator.'
$GuiAuthText26 = '여기에서는 새 계정을 만들 수 없습니다. 관리자에게 문의하세요.';
// / 'A username is 3 to 64 letters, digits, dots, dashes or underscores, and starts with a letter or a digit.'
$GuiAuthText27 = '사용자 이름은 문자, 숫자, 마침표, 하이픈, 밑줄로 된 3~64자이며 문자나 숫자로 시작합니다.';
// / 'That username is taken. Choose another.'
$GuiAuthText28 = '이미 사용 중인 사용자 이름입니다. 다른 이름을 선택하세요.';
// / 'That email address cannot be used.'
$GuiAuthText29 = '이 이메일 주소는 사용할 수 없습니다.';
// / 'Passwords must have at least this many characters:'
$GuiAuthText30 = '비밀번호의 최소 글자 수:';
// / 'Your account was created. We emailed you a code to confirm it.'
$GuiAuthText31 = '계정이 만들어졌습니다. 확인용 코드를 이메일로 보내 드렸습니다.';
// / 'Your account was created, but this server could not email your code. Contact the administrator.'
$GuiAuthText32 = '계정이 만들어졌지만 이 서버에서 코드를 이메일로 보내지 못했습니다. 관리자에게 문의하세요.';
// / 'Your account was created. You can sign in now.'
$GuiAuthText33 = '계정이 만들어졌습니다. 이제 로그인할 수 있습니다.';
// / 'That code is not right.'
$GuiAuthText34 = '코드가 올바르지 않습니다.';
// / 'That code can no longer be used. Ask for a new one.'
$GuiAuthText35 = '이 코드는 더 이상 사용할 수 없습니다. 새 코드를 요청하세요.';
// / 'Your account is confirmed. You can sign in now.'
$GuiAuthText36 = '계정이 확인되었습니다. 이제 로그인할 수 있습니다.';
// / 'If that account is waiting to be confirmed, a new code is on its way.'
$GuiAuthText37 = '해당 계정이 확인을 기다리는 중이라면 새 코드가 발송되고 있습니다.';
// / 'If that account exists, a code is on its way to its email address.'
$GuiAuthText38 = '해당 계정이 있다면 그 이메일 주소로 코드가 발송되고 있습니다.';
// / 'Your password was changed. You can sign in now.'
$GuiAuthText39 = '비밀번호가 변경되었습니다. 이제 로그인할 수 있습니다.';
// / 'You have been signed out.'
$GuiAuthText40 = '로그아웃되었습니다.';
// / 'You were signed out, because your sign in was used somewhere else.'
$GuiAuthText41 = '로그인이 다른 곳에서 사용되어 로그아웃되었습니다.';
// / 'The server did not answer. Please try again.'
$GuiAuthText42 = '서버가 응답하지 않았습니다. 다시 시도하세요.';
// / 'Recovering an account is unavailable on this server.'
$GuiAuthText43 = '이 서버에서는 계정을 복구할 수 없습니다.';
// / -----------------------------------------------------------------------------------