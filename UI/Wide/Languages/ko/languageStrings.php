<?php
// / -----------------------------------------------------------------------------------
// / APPLICATION INFORMATION ...
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
// / Set GUI - 1 Related Variables.
// / Online File Converter, Extractor, Compressor'
$Gui1Text1 = '온라인 파일 변환기, 추출기, 압축기';
// / $ApplicationName.' is based off the open-source web-app <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> by <a href=\'https://github.com/zelon88\'>Zelon88</a> that converts files without tracking users across the net or infringing on your intellectual property.'
$Gui1Text2 = $ApplicationName.' <a href=\'https://github.com의 오픈 소스 웹 앱 <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a>를 기반으로 합니다. /zelon88\'>Zelon88</a>은 네트워크를 통해 사용자를 추적하거나 지적 재산권을 침해하지 않고 파일을 변환합니다.';
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
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = '파일 변환 옵션';
// / 'Bulk File Options'
$Gui2Text2 = '대량 파일 옵션';
// / 'Scan All Files For Viruses'
$Gui2Text3 = '모든 파일에서 바이러스 검사';
// / 'Compress & Download All Files'
$Gui2Text4 = '모든 파일 압축 및 다운로드';
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
// / 'Compress & Download'
$Gui2Text19 = '압축 및 다운로드';
// / 'Scan with ClamAV: '
$Gui2Text20 = 'ClamAV로 스캔: ';
// / 'Scan with ScanCore: '
$Gui2Text21 = 'ScanCore로 스캔: ';
// / 'Scan All'
$Gui2Text22 = '모두 스캔';
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
// / Convert This 3D Model'
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
// / Convert These Subtitles'
$Gui2Text75 = '이 자막 변환';
// / Convert Subtitles'
$Gui2Text76 = '자막 변환';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / 'Check out our <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Terms of Service</a> and <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Privacy Policy'
$GuiFooterText1 = '<a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>서비스 약관</a> 및 를 확인하세요 <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>개인정보 보호정책';
// / -----------------------------------------------------------------------------------