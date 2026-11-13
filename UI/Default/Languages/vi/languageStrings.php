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
// / This file contains the Vietnamese (vi) language strings.
// / Vietnamese does not inflect nouns for number, so the plural helper strings are empty.
// / A noun following a numeral is never pluralized, whatever the count happens to be.
// / This file must be saved as UTF-8 without a byte order mark.
// / A byte order mark emits three bytes before any output & will break header delivery.
// / Vietnamese uses stacked diacritics, so this file must never be normalized to a form
// / that decomposes them, & must never be processed by a routine that strips accents.
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
$LanguageVersion = 'v4.1.5';
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
$CoreError = 'ERROR!!! HRConvert2-2, Tệp này không thể xử lý yêu cầu của bạn. Vui lòng gửi tệp của bạn đến convertCore.php.';
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die($CoreError);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Check for required core variables.
if (!isset($Font)) $Font = 'Arial';
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2';
if (!isset($ApplicationTitle)) $ApplicationTitle = 'Chuyển Đổi Mọi Thứ!';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI Related Logic.
// / Vietnamese does not inflect nouns for number, so both plural helpers stay empty.
// / Nam tep is correct & there is no plural form of tep to use instead.
if (!is_numeric($FileCount)) $FileCount = 'một số lượng không xác định';
$FCPlural1 = '';
$FCPlural2 = '';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Header Related Variables.
// / 'Click, Tap, or Drop files here to upload.'
$GuiHeaderText1 = 'Nhấp, chạm hoặc kéo thả tệp vào đây để tải lên.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - UI Selector Bar Related Variables.
// / These strings appear in the selector bar, which is present on both GUI1 & GUI2.
// / 'Language'
$GuiSelectorText1 = 'Ngôn ngữ';
// / 'Color'
$GuiSelectorText2 = 'Màu sắc';
// / 'Interface'
$GuiSelectorText3 = 'Giao diện';
// / 'Display language, color and interface options'
$GuiSelectorText4 = 'Hiển thị tùy chọn ngôn ngữ, màu sắc và giao diện';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 1 Related Variables.
// / 'Online File Converter, Extractor, Compressor'
$Gui1Text1 = 'Công Cụ Chuyển Đổi, Giải Nén & Nén Tệp Trực Tuyến';
// / $ApplicationName.' is based off the open-source web-app HRConvert2 by Zelon88 that converts files without tracking users across the net or infringing on your intellectual property.'
$Gui1Text2 = $ApplicationName.' được xây dựng dựa trên ứng dụng web mã nguồn mở <a href=\'https://github.com/zelon88/HRConvert2\'>HRConvert2</a> của <a href=\'https://github.com/zelon88\'>Zelon88</a>, chuyển đổi tệp mà không theo dõi người dùng trên mạng hay xâm phạm quyền sở hữu trí tuệ của bạn.';
// / 'More Info ...'
$Gui1Text3 = 'Xem Thêm ...';
// / 'Less Info ...'
$Gui1Text4 = 'Thu Gọn ...';
// / 'All user-supplied data is erased automatically, so you don\'t need to worry about forfeiting your personal information or property while using our services.'
$Gui1Text5 = 'Toàn bộ dữ liệu do người dùng cung cấp đều được xóa tự động, vì vậy bạn không cần lo lắng về việc mất thông tin cá nhân hay tài sản của mình khi sử dụng dịch vụ của chúng tôi.';
// / 'Currently '.$ApplicationName.' supports '.$SupportedFormatCount.' different file formats, including documents, spreadsheets, images, media, 3D models, CAD drawings, vector files, archives, disk images, & more.'
$Gui1Text6 = 'Hiện tại '.$ApplicationName.' hỗ trợ '.$SupportedFormatCount.' định dạng tệp khác nhau, bao gồm tài liệu, bảng tính, hình ảnh, đa phương tiện, mô hình 3D, bản vẽ CAD, tệp vector, tệp nén, ảnh đĩa & nhiều định dạng khác.';
// / 'View Supported Formats ...'
$Gui1Text7 = 'Xem Các Định Dạng Được Hỗ Trợ ...';
// / 'Hide Supported Formats ...'
$Gui1Text8 = 'Ẩn Các Định Dạng Được Hỗ Trợ ...';
// / 'Supported Formats'
$Gui1Text9 = 'Các Định Dạng Được Hỗ Trợ';
// / 'Audio Formats'
$Gui1Text10 = 'Định Dạng Âm Thanh';
// / 'Supports specific bitrate.'
$Gui1Text11 = 'Hỗ trợ chỉ định tốc độ bit.';
// / 'Video Formats'
$Gui1Text12 = 'Định Dạng Video';
// / 'Stream Formats'
$Gui1Text13 = 'Định Dạng Luồng';
// / 'Document Formats'
$Gui1Text14 = 'Định Dạng Tài Liệu';
// / 'Spreadsheet Formats'
$Gui1Text15 = 'Định Dạng Bảng Tính';
// / 'Presentation Formats'
$Gui1Text16 = 'Định Dạng Bài Thuyết Trình';
// / 'Archive Formats'
$Gui1Text17 = 'Định Dạng Tệp Nén';
// / 'Can convert between select archive formats & disk image formats.'
$Gui1Text18 = 'Có thể chuyển đổi giữa một số định dạng tệp nén & định dạng ảnh đĩa.';
// / 'Image Formats'
$Gui1Text19 = 'Định Dạng Hình Ảnh';
// / 'Can convert pictures of documents to document formats.'
$Gui1Text20 = 'Có thể chuyển đổi ảnh chụp tài liệu sang các định dạng tài liệu.';
// / 'Supports resize & rotate.'
$Gui1Text21 = 'Hỗ trợ thay đổi kích thước & xoay.';
// / '3D Model Formats'
$Gui1Text22 = 'Định Dạng Mô Hình 3D';
// / 'Drawing Formats'
$Gui1Text23 = 'Định Dạng Bản Vẽ';
// / 'Can convert drawing formats to image formats.'
$Gui1Text24 = 'Có thể chuyển đổi định dạng bản vẽ sang định dạng hình ảnh.';
// / 'OCR Support'
$Gui1Text25 = 'Hỗ Trợ OCR';
// / 'OCR Operations support the following input formats...'
$Gui1Text26 = 'Các thao tác OCR hỗ trợ những định dạng đầu vào sau ...';
// / 'OCR Operations support the following output formats...'
$Gui1Text27 = 'Các thao tác OCR hỗ trợ những định dạng đầu ra sau ...';
// / 'Select files by clicking, tapping, or dropping them into the box below.'
$Gui1Text28 = 'Chọn tệp bằng cách nhấp, chạm hoặc kéo thả tệp vào ô bên dưới.';
// / 'Continue ...'
$Gui1Text29 = 'Tiếp Tục ...';
// / 'Can convert stream formats to video formats.'
$Gui1Text30 = 'Có thể chuyển đổi định dạng luồng sang định dạng video.';
// / 'Subtitle Formats'
$Gui1Text31 = 'Định Dạng Phụ Đề';
// / 'OpenSCAD Formats'
$Gui1Text32 = 'Định Dạng OpenSCAD';
// / 'Renders OpenSCAD source into 3D model formats.'
$Gui1Text33 = 'Kết xuất mã nguồn OpenSCAD thành các định dạng mô hình 3D.';
// / 'File references inside uploaded sources are removed unless the server allows resolving them.'
$Gui1Text34 = 'Các tham chiếu tệp bên trong mã nguồn được tải lên sẽ bị loại bỏ, trừ khi máy chủ cho phép phân giải chúng.';
// / 'Delete every uploaded file & start a new session?'
// / Shown inside the start over panel on the upload page. That panel is only rendered
// / when the session already holds at least one file, so this is never shown to a first
// / time visitor who has nothing to lose.
$Gui1Text35 = 'Xóa mọi tệp đã tải lên và bắt đầu phiên mới?';
// / 'Start Over'
// / Labels the control that opens the panel & the button inside it that confirms.
$Gui1Text36 = 'Bắt Đầu Lại';
// / 'Refresh'
// / Alternate text for the refresh control on the upload page. That control is a glyph
// / with no text of its own, so this is the only description a screen reader has & the
// / only thing shown when a browser cannot render the glyph.
$Gui1Text37 = 'Làm Mới';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - 2 Related Variables.
// / 'File Conversion Options'
$Gui2Text1 = 'Tùy Chọn Chuyển Đổi Tệp';
// / 'Bulk File Options'
$Gui2Text2 = 'Tùy Chọn Hàng Loạt';
// / 'Scan All Files For Viruses'
$Gui2Text3 = 'Quét Vi-rút Cho Tất Cả Tệp';
// / 'Compress & Download All Files'
$Gui2Text4 = 'Nén & Tải Xuống Tất Cả Tệp';
// / 'Download'
$Gui2Text5 = 'Tải Xuống';
// / 'Share'
$Gui2Text6 = 'Chia Sẻ';
// / 'Close Share Options'
$Gui2Text7 = 'Đóng Tùy Chọn Chia Sẻ';
// / 'Virus Scan'
$Gui2Text8 = 'Quét Vi-rút';
// / 'Close Virus Scan Options'
$Gui2Text9 = 'Đóng Tùy Chọn Quét Vi-rút';
// / 'Archive'
$Gui2Text10 = 'Nén';
// / 'Close Archive Options'
$Gui2Text11 = 'Đóng Tùy Chọn Nén';
// / 'OCR'
$Gui2Text12 = 'OCR';
// / 'Close OCR Options'
$Gui2Text13 = 'Đóng Tùy Chọn OCR';
// / 'Convert'
$Gui2Text14 = 'Chuyển Đổi';
// / 'Close Convert Options'
$Gui2Text15 = 'Đóng Tùy Chọn Chuyển Đổi';
// / 'Archive This File'
$Gui2Text16 = 'Nén Tệp Này';
// / 'Specify Filename: '
$Gui2Text17 = 'Chỉ định tên tệp: ';
// / 'Format'
$Gui2Text18 = 'Định dạng';
// / 'Compress & Download'
$Gui2Text19 = 'Nén & Tải Xuống';
// / 'Scan with ClamAV: '
$Gui2Text20 = 'Quét bằng ClamAV: ';
// / 'Scan with ScanCore: '
$Gui2Text21 = 'Quét bằng ScanCore: ';
// / 'Scan All'
$Gui2Text22 = 'Quét Tất Cả';
// / 'Share This File'
$Gui2Text23 = 'Chia Sẻ Tệp Này';
// / 'Link Status: '
$Gui2Text24 = 'Trạng thái liên kết: ';
// / 'Not Generated'
$Gui2Text25 = 'Chưa tạo';
// / 'Generated'
$Gui2Text26 = 'Đã tạo';
// / 'Clipboard Status: '
$Gui2Text27 = 'Trạng thái bảng nhớ tạm: ';
// / 'Copied'
$Gui2Text28 = 'Đã sao chép';
// / 'File Link: '
$Gui2Text29 = 'Liên kết tệp: ';
// / 'You have uploaded '.$FileCount.' valid file'.$FCPlural1.' to '.$ApplicationName.'.'
$Gui2Text30 = 'Bạn đã tải lên '.$FileCount.' tệp hợp lệ vào '.$ApplicationName.'.'.$FCPlural1;
// / 'Your file'.$FCPlural2.' now ready to convert using the options below.'
$Gui2Text31 = 'Tệp của bạn hiện đã sẵn sàng để chuyển đổi bằng các tùy chọn bên dưới.'.$FCPlural2;
// / 'Generate Link & Copy to Clipboard'
$Gui2Text32 = 'Tạo Liên Kết & Sao Chép Vào Bảng Nhớ Tạm';
// / 'Generate Link'
$Gui2Text33 = 'Tạo Liên Kết';
// / 'Scan This File For Viruses'
$Gui2Text34 = 'Quét Vi-rút Cho Tệp Này';
// / 'Scan File With ScanCore'
$Gui2Text35 = 'Quét Tệp Bằng ScanCore';
// / 'Scan File With ClamAV'
$Gui2Text36 = 'Quét Tệp Bằng ClamAV';
// / 'Scan File With ScanCore & ClamAV'
$Gui2Text37 = 'Quét Tệp Bằng ScanCore & ClamAV';
// / 'Perform Optical Character Recognition On This File'
$Gui2Text38 = 'Thực Hiện Nhận Dạng Ký Tự Quang Học Trên Tệp Này';
// / 'Method'
$Gui2Text39 = 'Phương thức';
// / 'Simple'
$Gui2Text40 = 'Đơn giản';
// / 'Advanced'
$Gui2Text41 = 'Nâng cao';
// / 'Convert This Archive'
$Gui2Text42 = 'Chuyển Đổi Tệp Nén Này';
// / 'Convert This Document'
$Gui2Text43 = 'Chuyển Đổi Tài Liệu Này';
// / 'Convert This Spreadsheet'
$Gui2Text44 = 'Chuyển Đổi Bảng Tính Này';
// / 'Convert This Audio'
$Gui2Text45 = 'Chuyển Đổi Tệp Âm Thanh Này';
// / 'Convert This Video'
$Gui2Text46 = 'Chuyển Đổi Video Này';
// / 'Convert This Stream'
$Gui2Text47 = 'Chuyển Đổi Luồng Này';
// / 'Convert This 3D Model'
$Gui2Text48 = 'Chuyển Đổi Mô Hình 3D Này';
// / 'Convert This Technical Drawing Or Vector File'
$Gui2Text49 = 'Chuyển Đổi Bản Vẽ Kỹ Thuật Hoặc Tệp Vector Này';
// / 'Convert This Image'
$Gui2Text50 = 'Chuyển Đổi Hình Ảnh Này';
// / 'Archive File'
$Gui2Text51 = 'Nén Tệp';
// / 'Convert Into Document'
$Gui2Text52 = 'Chuyển Đổi Thành Tài Liệu';
// / 'Archive Files'
$Gui2Text53 = 'Nén Các Tệp';
// / 'Convert Document'
$Gui2Text54 = 'Chuyển Đổi Tài Liệu';
// / 'Convert Spreadsheet'
$Gui2Text55 = 'Chuyển Đổi Bảng Tính';
// / 'Convert Presentation'
$Gui2Text56 = 'Chuyển Đổi Bài Thuyết Trình';
// / 'Convert Audio'
$Gui2Text57 = 'Chuyển Đổi Âm Thanh';
// / 'Convert Video'
$Gui2Text58 = 'Chuyển Đổi Video';
// / 'Convert Stream'
$Gui2Text59 = 'Chuyển Đổi Luồng';
// / 'Convert Model'
$Gui2Text60 = 'Chuyển Đổi Mô Hình';
// / 'Convert Drawing'
$Gui2Text61 = 'Chuyển Đổi Bản Vẽ';
// / 'Convert Image'
$Gui2Text62 = 'Chuyển Đổi Hình Ảnh';
// / NOTE. There is no $Gui2Text63 in any language pack. The gap exists in the English
// / original & is preserved here deliberately so every pack stays index compatible.
// / 'Width & Height: '
$Gui2Text64 = 'Chiều rộng & chiều cao: ';
// / 'Rotate: '
$Gui2Text65 = 'Xoay: ';
// / 'Bitrate: '
$Gui2Text66 = 'Tốc độ bit: ';
// / 'Delete'
$Gui2Text67 = 'Xóa';
// / 'Close Delete Options'
$Gui2Text68 = 'Đóng Tùy Chọn Xóa';
// / 'Delete This File'
$Gui2Text69 = 'Xóa Tệp Này';
// / 'Confirm Delete'
$Gui2Text70 = 'Xác Nhận Xóa';
// / 'Cannot convert this file! Try changing the name.'
$Gui2Text71 = 'Không thể chuyển đổi tệp này. Hãy thử đổi tên tệp.';
// / 'Cannot perform a virus scan on this file!'
$Gui2Text72 = 'Không thể quét vi-rút cho tệp này.';
// / 'File Link Copied to Clipboard!'
$Gui2Text73 = 'Đã sao chép liên kết tệp vào bảng nhớ tạm.';
// / 'Operation Failed!'
$Gui2Text74 = 'Thao tác thất bại.';
// / 'Convert These Subtitles'
$Gui2Text75 = 'Chuyển Đổi Phụ Đề Này';
// / 'Convert Subtitles'
$Gui2Text76 = 'Chuyển Đổi Phụ Đề';
// / 'Convert This Presentation'
$Gui2Text77 = 'Chuyển Đổi Bài Thuyết Trình Này';
// / 'Convert This XPS File'
$Gui2Text78 = 'Chuyển Đổi Tệp XPS Này';
// / 'Render This OpenSCAD Model'
$Gui2Text79 = 'Kết Xuất Mô Hình OpenSCAD Này';
// / 'Render Model'
$Gui2Text80 = 'Kết Xuất Mô Hình';
// / 'Convert This E-Book'
$Gui2Text81 = 'Chuyển đổi sách điện tử này';
// / 'Convert E-Book'
$Gui2Text82 = 'Chuyển đổi sách điện tử';
// / 'Playlist source URL, only needed if the playlist uses relative paths'
$Gui2Text83 = 'URL nguồn của danh sách phát, chỉ cần khi danh sách dùng đường dẫn tương đối';
// / 'Download A File From A URL'
$Gui2Text84 = 'Tải tệp từ một URL';
// / 'Fetch'
$Gui2Text85 = 'Lấy về';
// / 'Identify'
$Gui2Text86 = 'Nhận Dạng';
// / 'Close Identify Options'
$Gui2Text87 = 'Đóng Tùy Chọn Nhận Dạng';
// / 'Identify This File'
$Gui2Text88 = 'Nhận Dạng Tệp Này';
// / 'Size: '
$Gui2Text89 = 'Kích thước: ';
// / 'Modified: '
$Gui2Text90 = 'Sửa đổi: ';
// / 'Type: '
$Gui2Text91 = 'Loại: ';
// / 'MIME Type: '
$Gui2Text92 = 'Kiểu MIME: ';
// / 'Character Set: '
$Gui2Text93 = 'Bộ ký tự: ';
// / 'Extension Matches Content: '
$Gui2Text94 = 'Phần mở rộng khớp nội dung: ';
// / 'Details: '
$Gui2Text95 = 'Chi tiết: ';
// / 'Notes: '
$Gui2Text96 = 'Ghi chú: ';
// / 'Save report as file'
$Gui2Text97 = 'Lưu báo cáo thành tệp';
// / 'Identify All Files'
$Gui2Text98 = 'Nhận Dạng Tất Cả Tệp';
// / 'Identify All'
$Gui2Text99 = 'Nhận Dạng Tất Cả';
// / 'Yes'
$Gui2Text100 = 'Có';
// / 'No'
$Gui2Text101 = 'Không';
// / 'Unknown'
$Gui2Text102 = 'Không rõ';
// / 'Cannot identify this file!'
$Gui2Text103 = 'Không thể nhận dạng tệp này.';
// / 'Sort By: '
$Gui2Text104 = 'Sắp xếp theo: ';
// / 'Name'
$Gui2Text105 = 'Tên';
// / 'Size'
$Gui2Text106 = 'Kích thước';
// / 'Date'
$Gui2Text107 = 'Ngày';
// / 'Type'
$Gui2Text108 = 'Loại';
// / The words in braces, such as {fit} & {count}, are filled in by the page. Each translation keeps every one of them.
// / 'Select'
$Gui2Text109 = 'Chọn';
// / 'Select All'
$Gui2Text110 = 'Chọn Tất Cả';
// / 'Select None'
$Gui2Text111 = 'Bỏ Chọn Tất Cả';
// / 'Select By Type'
$Gui2Text112 = 'Chọn Theo Loại';
// / 'Selected: '
$Gui2Text113 = 'Đã chọn: ';
// / 'Documents'
$Gui2Text114 = 'Tài Liệu';
// / 'Spreadsheets'
$Gui2Text115 = 'Bảng Tính';
// / 'Presentations'
$Gui2Text116 = 'Bài Thuyết Trình';
// / 'Images'
$Gui2Text117 = 'Hình Ảnh';
// / 'Audio'
$Gui2Text118 = 'Âm Thanh';
// / 'Video'
$Gui2Text119 = 'Video';
// / 'Archives'
$Gui2Text120 = 'Tệp Nén';
// / 'E-Books'
$Gui2Text121 = 'Sách Điện Tử';
// / '3D Models'
$Gui2Text122 = 'Mô Hình 3D';
// / 'Drawings'
$Gui2Text123 = 'Bản Vẽ';
// / 'Vector Images'
$Gui2Text124 = 'Ảnh Vector';
// / 'Subtitles'
$Gui2Text125 = 'Phụ Đề';
// / 'OpenSCAD Files'
$Gui2Text126 = 'Tệp OpenSCAD';
// / 'Streams'
$Gui2Text127 = 'Luồng';
// / 'Workflows'
$Gui2Text128 = 'Quy Trình';
// / 'Files'
$Gui2Text129 = 'Tệp';
// / 'Select files in the list below.'
$Gui2Text130 = 'Chọn tệp trong danh sách bên dưới.';
// / 'Select By Name: '
$Gui2Text131 = 'Chọn Theo Tên: ';
// / 'Use * and ? as wildcards, or write a regular expression between slashes.'
$Gui2Text132 = 'Dùng * và ? làm ký tự đại diện, hoặc viết biểu thức chính quy giữa hai dấu gạch chéo.';
// / 'Workflow'
$Gui2Text133 = 'Quy Trình';
// / 'Choose a workflow'
$Gui2Text134 = 'Chọn một quy trình';
// / 'Run'
$Gui2Text135 = 'Chạy';
// / 'Stop'
$Gui2Text136 = 'Dừng';
// / 'Selected files that can be used: {fit} of {count}.'
$Gui2Text137 = 'Số tệp đã chọn có thể dùng: {fit} trên {count}.';
// / 'Step {step} of {steps}: {operation}'
$Gui2Text138 = 'Bước {step} trên {steps}: {operation}';
// / 'File {done} of {total}'
$Gui2Text139 = 'Tệp {done} trên {total}';
// / 'Waiting for the server.'
$Gui2Text140 = 'Đang chờ máy chủ.';
// / 'Starting.'
$Gui2Text141 = 'Đang bắt đầu.';
// / 'Finished.'
$Gui2Text142 = 'Đã xong.';
// / 'Stopping.'
$Gui2Text143 = 'Đang dừng.';
// / 'Stopped.'
$Gui2Text144 = 'Đã dừng.';
// / 'Files made: '
$Gui2Text145 = 'Tệp đã tạo: ';
// / 'Workflows are not available on this server.'
$Gui2Text146 = 'Quy trình không khả dụng trên máy chủ này.';
// / 'No workflows are installed on this server.'
$Gui2Text147 = 'Không có quy trình nào được cài đặt trên máy chủ này.';
// / 'That workflow is no longer available.'
$Gui2Text148 = 'Quy trình đó không còn khả dụng.';
// / 'None of the selected files can be used by this workflow.'
$Gui2Text149 = 'Quy trình này không thể dùng tệp nào trong số các tệp đã chọn.';
// / 'Too many files are selected for one workflow.'
$Gui2Text150 = 'Đã chọn quá nhiều tệp cho một quy trình.';
// / 'The server is busy. Try again in a few minutes.'
$Gui2Text151 = 'Máy chủ đang bận. Hãy thử lại sau vài phút.';
// / 'A workflow is already running in this session.'
$Gui2Text152 = 'Một quy trình đang chạy trong phiên này.';
// / 'The workflow could not be started.'
$Gui2Text153 = 'Không thể bắt đầu quy trình.';
// / 'A step of the workflow failed.'
$Gui2Text154 = 'Một bước của quy trình đã thất bại.';
// / 'A virus was found, so the workflow stopped.'
$Gui2Text155 = 'Phát hiện vi-rút nên quy trình đã dừng.';
// / 'The workflow ran out of time.'
$Gui2Text156 = 'Quy trình đã hết thời gian.';
// / 'The workflow made too many files.'
$Gui2Text157 = 'Quy trình đã tạo quá nhiều tệp.';
// / 'The server was too busy to finish the workflow.'
$Gui2Text158 = 'Máy chủ quá bận nên không thể hoàn tất quy trình.';
// / 'An operation this workflow needs is not available.'
$Gui2Text159 = 'Một thao tác mà quy trình này cần không khả dụng.';
// / 'The workflow stopped unexpectedly.'
$Gui2Text160 = 'Quy trình đã dừng bất ngờ.';
// / The words in braces, such as {step} & {kind}, are filled in by the page. Each translation keeps every one of them.
// / 'Pipelines'
$Gui2Text161 = 'Đường Ống';
// / 'Select a pipeline above...'
$Gui2Text162 = 'Chọn một đường ống ở trên...';
// / 'Select Pipeline...'
$Gui2Text163 = 'Chọn Đường Ống...';
// / 'Select Files...'
$Gui2Text164 = 'Chọn Tệp...';
// / 'Add a step'
$Gui2Text165 = 'Thêm một bước';
// / 'Options'
$Gui2Text166 = 'Tùy chọn';
// / 'Back'
$Gui2Text167 = 'Quay lại';
// / 'Take: '
$Gui2Text168 = 'Lấy: ';
// / 'the ticked files'
$Gui2Text169 = 'các tệp đã đánh dấu';
// / 'the files step {step} made'
$Gui2Text170 = 'các tệp do bước {step} tạo ra';
// / 'each file on its own'
$Gui2Text171 = 'từng tệp riêng lẻ';
// / 'all files together'
$Gui2Text172 = 'tất cả tệp cùng lúc';
// / 'Only these kinds: '
$Gui2Text173 = 'Chỉ các loại này: ';
// / 'Only these extensions: '
$Gui2Text174 = 'Chỉ các phần mở rộng này: ';
// / 'Smallest size: '
$Gui2Text175 = 'Kích thước nhỏ nhất: ';
// / 'Largest size: '
$Gui2Text176 = 'Kích thước lớn nhất: ';
// / 'In the order they arrive'
$Gui2Text177 = 'Theo thứ tự đến';
// / 'all files'
$Gui2Text178 = 'tất cả tệp';
// / 'all {kind}'
$Gui2Text179 = 'tất cả {kind}';
// / 'Width: '
$Gui2Text180 = 'Chiều rộng: ';
// / 'Height: '
$Gui2Text181 = 'Chiều cao: ';
// / 'My workflow'
$Gui2Text182 = 'Quy trình của tôi';
// / 'New'
$Gui2Text183 = 'Mới';
// / 'Import'
$Gui2Text184 = 'Nhập';
// / 'Export'
$Gui2Text185 = 'Xuất';
// / 'This workflow is not one this server can run.'
$Gui2Text186 = 'Máy chủ này không thể chạy quy trình này.';
// / 'The file could not be read.'
$Gui2Text187 = 'Không thể đọc tệp.';
// / 'The workflow builder is not available on this server.'
$Gui2Text188 = 'Trình tạo quy trình không khả dụng trên máy chủ này.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - HRC2-Functions.js Related Variables.
// / These strings are used by the client side javascript library.
// / That file is static javascript & cannot read a PHP variable, so every string it needs
// / is passed to it as an argument by the PHP that calls it.
// / 'Your browser does not support copying to the clipboard!'
$GuiFunctionsText1 = 'Trình duyệt của bạn không hỗ trợ sao chép vào bảng nhớ tạm.';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set GUI - Footer Related Variables.
// / The closing anchor tag for this string lives in footer.php & must NOT be repeated here.
// / 'Check out our Terms of Service and Privacy Policy'
$GuiFooterText1 = 'Xem <a href=\''.$TOSURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Điều Khoản Dịch Vụ</a> và <a href=\''.$PPURL.'\' target=\'_blank\' rel=\'noopener noreferrer\'>Chính Sách Bảo Mật';
// / -----------------------------------------------------------------------------------