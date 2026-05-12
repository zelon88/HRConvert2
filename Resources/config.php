<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRConvert2, Copyright on 5/8/2026 by Justin Grimes, www.github.com/zelon88
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
// / v3.4.1.
// / This file contains the configuration information for HRConvert2.
// / Fill out this file completely & accurately before running the application.
// / Serious filesystem damage could occur from incorrect directory settings.
// / Be careful to preserve all syntax & formatting.
// /
// / HARDWARE REQUIREMENTS ... 
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / DEPENDENCY REQUIREMENTS ... 
// / This application requires Debian Linux (w/3rd Party audio license), Apache 2.4,
// / PHP 8+, LibreOffice, Unoconv, ClamAV, Tesseract,  Unzip, FFMPEG, Mkisofs, 7zip,
// / Rar, Unrar, libgxps-utils, PopplerUtils, MeshLab, PDFTOTEXT, Dia, ImageMagick.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / ------------------------------
 
// / ---Security Informations---
// / 
// /  --Salts--
// /   Salts for hashing operations.
// /   Change these Salts to something completely random and keep them secret. 
// /   Store your Salts in hardcopy form or on an encrypted drive in case of emergency.
$Salts1 = 'something1SoRa21nDoMThatNobody_4Wiljl_evar+guess+i1tgdgdfgfdsfgdasfdas';
$Salts2 = 'gdf4sgdfsg1sdfsomethingSoRa33nDoMThatNobody_Will2_evar_guess+it';
$Salts3 = 'somethingSoRanDoMThatNobo423432dy54534534_Will_evar+guess+it';
$Salts4 = 'somethin1gSoRanDoMThat123:l_will_evar-guess+it';
$Salts5 = 'somethingSoRanDoMThatNobodyr3454r3r33_Will_evar+guess+it';
$Salts6 = 'somethingSoR5anDoMThatNob2odyawryoglukfgy;/,6^&__Will_evar+guess+it';
// /  --Server URL--
// /   Externally or internally accesible domain or IP.
// /   Do not include a trailing slash.
// /   Default is localhost.
$URL = 'localhost';
// /  --Virus Scanning--
// /   Scan for viruses before performing file operations.
// /   Requires ClamAV to be installed on the server.
// /   Set to TRUE to enable virus scanning with ClamAV during file operations.
// /   Set to FALSE to disable virus scanning during file operations.
// /   The --User Virus Scanning-- config entry has a major impact on how regular virus scans are performed.
// /   If set to TRUE & --User Virus Scanning-- is set to TRUE infected files detected during virus scans will not be removed automatically.
// /   If set to TRUE & --User Virus Scanning-- is set to FALSE any infected file will immediately be deleted upon detection.
// /   If set to TRUE & --User Virus Scanning-- is set to TRUE incoming file uploads will not be scanned for viruses.
// /   If set to TRUE & --User Virus Scanning-- is set to FALSE incoming file uploads will be scanned for viruses.
// /   Regardless of how --User Virus Scanning-- is set, infected files cannot be downloaded, archived, converted, or OCR'd.
// /   Valid options are TRUE or FALSE.
// /   Defalt is FALSE.
$VirusScan = FALSE;
// /  --User Virus Scanning--
// /   Provide users with options to scan their uploaded files for viruses.
// /   Requires ClamAV to be installed on the server.
// /   Set to TRUE to allow users to upload potentially infected files.
// /   Set to FALSE to disallow users uploading potentially infected files.
// /   This config entry has a major impact on how regular virus scans are performed.
// /   If set to TRUE & --Virus Scanning-- is set to TRUE infected files detected during virus scans will not be removed automatically.
// /   If set to FALSE & --Virus Scanning-- is set to TRUE any infected file will immediately be deleted upon detection.
// /   If set to TRUE & --Virus Scanning-- is set to TRUE incoming file uploads will not be scanned for viruses.
// /   If set to FALSE & --Virus Scanning-- is set to TRUE incoming file uploads will be scanned for viruses.
// /   Regardless of how --User Virus Scanning-- is set, infected files cannot be downloaded, archived, converted, or OCR'd.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$AllowUserVirusScan = TRUE;
// /  --User Virus Scanning ScanCore Memory Limit--
// /   The number of bytes of memory ScanCore is allowed to allocate to large files during User Virus Scans.
// /   Files larger than this limit will be broken into chunks controlled by the --User Virus Scanning ScanCore Chunk Size-- config entry.
// /   Default is 268435456.
$ScanCoreMemoryLimit = 268435456;
// /  --User Virus Scanning ScanCore Chunk Size--
// /   In order to scan files that are larger than the memory limit, large files will be broken into chunks.
// /   The number of bytes to break large files into in order to fit them into memory.
// /   Default is 134217928.
$ScanCoreChunkSize = 134217928;
// /  --User Virus Scanning ScanCore Debug Mode--
// /   Enable an absolutely insane amount of verbosity from ScanCore during file scan operations.
// /   If set to TRUE these events will be included in the report that is submitted to the user.
// /   If set to FALSE a normal amount of logging will be submitted to the user. Enough to get the job done.
// /   If you scanned an entire 500GB hard drive with this set to TRUE ScanCore would generate 10's of GB worth of logs.
// /   This setting will have an impact on ScanCore scanning performance.
// /   Seriously, it's a lot of logs.
// /   Valid options are TRUE or FALSE.
// /   Default is FALSE.
$ScanCoreDebug = FALSE;
// /  --User Virus Scanning ScanCore Enhanced Verbosity--
// /   Enable an absolutely insane amount of console output from ScanCore during file scan operations.
// /   If set to TRUE these events will be included in the log file that is stored on the server.
// /   If set to FALSE a normal amount of logging will be stored on the server. Enough to get the job done.
// /   If you scanned an entire 500GB hard drive with this set to TRUE ScanCore would generate 10's of GB worth of logs.
// /   This setting will have an impact on ScanCore scanning performance.
// /   Seriously, it's a lot of logs.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$ScanCoreVerbose = TRUE;
// /  --Delete Build Environment--
// /   Automatically remove the build environment when it is no longer needed.
// /   Production servers should not keep the 'Build' folder in the --Installation Directory-- as a security precaution.
// /   If set to TRUE, the 'Build' folder in the root of the --Installation Directory-- will be recursively deleted.
// /   If set to FALSE, the 'Build' folder in the root of the --Installation Directory-- will NOT be deleted.
// /   This 'Build' folder could be used by an adversary to obtain configuration information about the application or server.
// /   This will NOT remove any documentation, logs, or required configuration files.
// /   If you want to keep the 'Build' folder, consider moving it out of the hosted '/var/www/html' directory.
// /   It is recommended to eventually set this to TRUE.
// /   Valid options are TRUE or FALSE.
// /   Default is FALSE.
$DeleteBuildEnvironment = FALSE;
// /  --Delete Development Documentation--
// /   Automatically remove 'README.md' & 'Documentation/CHANGELOG.txt' files when they are no longer needed.
// /   Production servers should not keep these files in the --Installation Directory-- as a security precaution.
// /   If set to TRUE, the 'README.md' & 'Documentation/CHANGELOG.txt' files will be deleted.
// /   If set to FALSE, the 'README.md' & 'Documentation/CHANGELOG.txt' files will NOT be deleted.
// /   These files could be used by an adversary to obtain configuration information about the application or server.
// /   This will NOT remove any other documentation, logs, or required configuration files.
// /   If you want to keep these files, consider moving them out of the hosted '/var/www/html' directory.
// /   It is recommended to eventually set this to TRUE.
// /   Valid options are TRUE or FALSE.
// /   Default is FALSE.
$DeleteDevelopmentDocumentation = FALSE;
// / ------------------------------

// / ------------------------------
// / ---Directory Information---
// / 
// /  --Home Directory--
// /   This is the Home directory for the web server user.
// /   Do not include a trailing slash.
// /   Do not use a path with whitespace.
// /   Changing this value is not recommended.
// /   Default is /var/www
$HomeLoc = '/var/www';
// /  --Installation Directory--
// /   Install HRConvert2 files to the following directory.
// /   Do not include a trailing slash.
// /   Do not use a path with whitespace.
// /   Changing this value is not recommended.
// /   Default is /var/www/html/HRProprietary/HRConvert2.
$InstLoc = '/var/www/html/HRProprietary/HRConvert2';
// /  --Proprietary Directory--
// /   Install the HRConvert2 folder to the following directory.
// /   Do not include a trailing slash.
// /   Do not use a path with whitespace.
// /   Changing this value is not recommended.
// /   Default is /var/www/html/HRProprietary.
$ProprietaryLoc = '/var/www/html/HRProprietary';
// /  --Server Root Directory--
// /   This should be pointed at the root of your web server directory.
// /   Do not include a trailing slash.
// /   Do not use a path with whitespace.
// /   Default is /var/www/html.
$ServerRootDir = '/var/www/html';
// /  --Data Storage Directory--
// /   This is where temporary data files are stored.
// /   Do not include a trailing slash.
// /   Do not use a path with whitespace.
// /   Default is /DATA/HRConvert2.
$ConvertLoc = '/path/to/convertloc';
// /  --Log Storage Directory--
// /   This is where permanent Log files are stored.
// /   Do not include a trailing slash.
// /   Do not use a path with whitespace.
// /   Default is /var/www/html/HRProprietary/HRConvert2/Logs.
$LogDir = '/var/www/html/HRProprietary/HRConvert2/Logs';
// / ------------------------------

// / ------------------------------
// / --Supported File Format Information--
// /
// /  --Supported Archive Formats--
$UserArchiveArray = array('zip', 'rar', 'tar', '7z', 'iso');
// /  --Supported Dearchive Formats--
$UserDearchiveArray = array('zip', 'rar', 'tar', 'bz', 'gz', 'bz2', '7z', 'iso', 'vhd', 'vdi', 'tar.bz2', 'tar.gz', 'cbr', 'cbz');
// /  --Supported Document Formats--
$UserDocumentArray = array('txt', 'doc', 'docx', 'rtf', 'odt', 'pdf');
// /  --Supported Spreadsheet Formats--
$UserSpreadsheetArray = array('csv', 'xls', 'xlsx', 'ods');
// /  --Supported XPS Input Formats--
$UserXPSInputArray = array('xps', 'oxps');
// /  --Supported XPS Output Formats--
$UserXPSOutputArray = array('pdf');
// /  --Supported Presentation Input Formats--
$UserPresentationInputArray = array('pptx', 'ppt', 'potx', 'potm', 'pot', 'ppa', 'odp');
// /  --Supported Presentation Output Formats--
$UserPresentationOutputArray = array('pptx', 'ppt', 'potx', 'potm', 'pot', 'ppa', 'odp', 'pdf');
// /  --Supported Image Formats--
$UserImageArray = array('jpeg', 'jpg', 'jpe', 'png', 'bmp', 'gif', 'webp', 'cin', 'dds', 'dib', 'flif', 'avif', 'gplt', 'sct', 'xcf', 'heic', 'ico');
// /  --Supported Audio Input Formats--
$UserMediaInputArray = array('sox', 'spdif', 'spx', 'tta', 'u16be', 'u16le', 'u24be', 'u24le', 'u32be', 'u32le', 'u8', 'voc', 'wav', 'wv', 'wsaud', 'mulaw', 'mxf', 'mxf_d10', 'mxf_opatom', 'oga', 'ogg', 'opus', 'oss', 'psp', 'rawvideo', 's16be', 's16le', 's24be', 's24le', 's32be', 's32le', 's8', 'sbc', 'ilbc', 'ircam', 'latm', 'lrc', 'mp2', 'mp3', 'mlp', 'flac', 'g722', 'g723_1', 'g726', 'g726le', 'gsm', 'caf', 'daud', 'dts', 'eac3', 'f32be', 'f32le', 'f64be', 'f64le', 'ac3', 'ac4', 'adts', 'aiff', 'alaw', 'amr', 'aptx', 'aptx_hd', 'argo_asf', 'argo_cvg', 'ast', 'au', 'a64', 'aa', 'aac', 'aax', 'acm', 'act', 'adp', 'adx', 'aea', 'afc', 'aix', 'alp', 'amrnb', 'amrwb', 'apac', 'apc', 'ape', 'apm', 'argo_asf', 'binka', 'bit', 'boa', 'bonk', 'brstm', 'dfpwm', 'dsf', 'dss', 'epaf', 'fsb', 'fwse', 'g729', 'hca', 'idf', 'kux', 'kvag', 'laf', 'lavfi', 'loas', 'luodat', 'lvf', 'lxf', 'mca', 'mcc', 'megsts', 'mlv', 'mmf', 'mods', 'moflex', 'mpc8', 'msf', 'msnwctcp', 'mtaf', 'musx', 'nc', 'nistsphere', 'nsp', 'paf', 'pam_pipe', 'pbm_pipe', 'pfm_pipe', 'pp_bnk', 'psxstr', 'pva', 'pvf', 'qcp', 'rka', 'rl2', 'rpl', 'rso', 's337m', 'sap', 'sbg', 'scd', 'sdns', 'sdp', 'sds', 'sdx', 'siff', 'simbiosis_imx', 'sln', 'smk', 'smush', 'sol', 'svag', 'svs', 'tak', 'thp', 'tierexseq', 'tty', 'ty', 'usm', 'vag', 'vidc', 'vpk', 'vqf', 'w64', 'wady', 'wavarc', 'wsd', 'wsvqa', 'wve', 'xa', 'xbin', 'xbm_pipe', 'xmd', 'xpm_pipe', 'xwma', 'yop', 'wma', 'm4a');
// /  --Supported Audio Output Formats--
$UserMediaOutputArray = array('mp3', 'aac', 'ogg', 'wma', 'mp2', 'flac', 'm4a');
// /  --Supported Video Input Formats--
$UserVideoInputArray = array('smoothstreaming', 'svcd', 'swf', 'truehd', 'vc1', 'vc1test', 'vcd', 'vob', 'vvc', 'webm', 'yuv4mpegpipe', 'mpjpeg', 'mxf', 'mxf_d10', 'mxf_opatom', 'nut', 'obu', 'ogv', 'psp', 'rawvideo', 'rm', 'roq', 'rtp_mpegts', 'smjpeg', 'hevc', 'hls', 'image2', 'image2pipe', 'ipod', 'ismv', 'm4v', 'matroska', 'mjpeg', 'mkvtimestamp_v2', 'mov', 'mp4', 'mpeg', 'mpeg1video', 'mpeg2video', 'mpegts', 'mpegtsraw', 'mpegvideo', 'fbdev', 'film_cpk', 'filmstrip', 'gxf', 'h261', 'h263', 'h264', 'hds', 'avs2', 'avs3', 'cavsvideo', 'cavs', 'dirac', 'dnxhd', 'dv', 'dvd', 'evc', '3g2', '3gp', 'apng', 'argo_asf', 'argo_cvg', 'asf', 'asf_stream', 'avi', 'avif', 'avm2', '3dostr', '4xm', 'adf', 'ads', 'alias_pix', 'anm', 'argo_brp', 'asf_o', 'av1', 'avs', 'bethsoftvid', 'bfi', 'bink', 'bmv', 'brender_pix', 'brender', 'cdg', 'cdxl', 'cine', 'concat', 'cri', 'dcstr', 'derf', 'dfa', 'dhav', 'dsicin', 'dtshd', 'dxa', 'ea', 'exr', 'fits', 'flic', 'frm', 'gdv', 'genh', 'gif', 'idcin', 'iff', 'ifv', 'ingenient', 'ipmovie', 'iss', 'iv8', 'ivf', 'ivr', 'j2k', 'jp2', 'jv', 'live_flv', 'lmlm4', 'mtv', 'mv', 'mvi', 'mxg', 'nsv', 'nuv', 'osq', 'pcx_pipe', 'pdv', 'pgm_pipe', 'pgmuv_pipe', 'pgx_pipe', 'phm_pipe', 'protocol_pipe', 'pictor_pipe', 'png_pipe', 'ppm_pipe', 'psd_pipe', 'qdraw_pipe', 'qoi_pipe', 'r3d', 'redspark', 'rroq', 'rsd', 'rtsp', 'sdr2', 'ser', 'sga', 'sgi_pipe', 'shn', 'sunrast_pipe', 'svg_pipe', 'tiff_pipe', 'tmv', 'v210', 'v210x', 'vbn_pipe', 'video4linux2', 'v4l2', 'vividas', 'vivo', 'vmd', 'wc3movie', 'webm_dash_manifest', 'webp_pipe', 'wtv', 'xmv', 'xvag', 'xwd_pipe', 'mkv', 'wmv');
// /  --Supported Video Output Formats--
$UserVideoOutputArray = array('3gp', 'mkv', 'avi', 'mp4', 'mpeg', 'wmv', 'mov', 'm4v');
// /  --Supported Stream Formats--
$UserStreamArray = array('m3u8');
// /  --Supported Drawing Formats--
$UserDrawingArray = array('svg', 'dxf', 'vdx', 'fig', 'dia', 'wpg', 'png');
// /  --Supported Model Formats--
$UserModelArray = array('3ds', 'obj', 'collada', 'off', 'ply', 'stl', 'gts', 'dxf', 'u3d', 'vrml', 'x3d');
// /  --Supported Subtitle Input Formats--
$UserSubtitleInputArray = array('sub', 'sbv', 'srt', 'stream_segment', 'ssegment', 'streamhash', 'sup', 'subtitles', 'ttml', 'uncodedframecrc', 'webvtt', 'wtv', 'oma', 'rso', 'rtp', 'rtsp', 'scc', 'sdl', 'sdl2', 'segment', 'sap', 'jacosub', 'kvag', 'microdvd', 'ffmetadata', 'fifo', 'fifo_test', 'fits', 'framecrc', 'framehash', 'framemd5', 'dash', 'crc', 'dvbsub', 'dvbtxt', 'gsm', 'ass', 'vobsub', 'mpl2', 'mpsub', 'pjs', 'realtext', 'sami', 'stl', 'subviewer', 'subviewer1', 'tedcaptions', 'txd', 'vtt', 'ssa', 'dvb', 'vplayer');
// /  --Supported Subtitle Output Formats--
$UserSubtitleOutputArray = array('vtt', 'ssa', 'ass', 'srt');
// /  --Supported OCR Formats--
$UserPDFWorkArr = array('pdf', 'jpg', 'jpeg', 'png', 'bmp', 'webp', 'gif');
// / ------------------------------
