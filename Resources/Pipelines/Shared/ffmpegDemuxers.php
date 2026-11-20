<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRConvert2, Copyright on 11/19/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / Application Information ...
// / This application is designed to provide a web-interface for converting file formats on
// / a server for users of any web browser without authentication.
// /
// / File Information ...
// / v4.3.0.
// / The FFMPEG demuxer shared module, which is not a pipeline & converts nothing on its own.
// / It names the demuxers FFMPEG & FFprobe may read a file with, which a command passes them as -format_whitelist.
// / The Video, Audio, Subtitle, Metadata, Stream & Identify pipelines each run one on a file a user gave, so none of them owns it.
// / It is version pinned exactly as a pipeline is, by the table in Resources/Contract/app-engine-config.php.
// / It is loaded once per request, before the first pipeline that declared it.
// / It raises no error of its own. FFMPEG refuses a file no demuxer named here reads, & the pipeline reports that as it reports any failure.
// / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt for the contracts this file obeys.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline component cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The version of this shared module. Read without executing this file, then matched
// / exactly against the pin in getAcceptedSharedModules().
$SharedModuleVersion = 'v4.3.0';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to name the demuxers FFMPEG & FFprobe may read one file with, from v4.3.0.
// / Accepts whether the file is a stream, which is read through its playlist.
// / Returns the demuxers' names, separated by commas & ready for -format_whitelist.
// / FFMPEG chooses a demuxer by what a file holds, whatever it is called. A DASH manifest or an ffconcat list named mp4 was read by the
// / demuxer that reads manifests or lists, which opened each file it named & wrote what it read into the output. -format_whitelist names
// / every demuxer a command may use, & the list follows a nested read, so a demuxer left out of it is refused for a file inside another.
// / A file is read by any demuxer of FFMPEG 6.1 but those that open a file another file names, hls, dash, concat & imf, & those that
// / open a network address a file names, sdp, rtsp, rtp & sap. Input devices are left out too, because no file is ever read by one.
// / A demuxer a later FFMPEG adds is not named, & a file only it reads is refused until it is traced & named here.
// / A stream is read by hls, & each segment it names by mpegts, mov, aac, mp3, ac3, eac3 or webvtt, & by nothing else, so a playlist
// / naming a manifest or a list is refused at that segment. A ts is read by mpegts alone.
function ffmpegDemuxerWhitelist($readsStream) {
  // / Set variables.
  global $EnableMemoryProtection;
  $DemuxerWhitelist = '';
  $streamDemuxers = array('hls', 'mpegts', 'mov', 'aac', 'mp3', 'ac3', 'eac3', 'webvtt');
  $mediaDemuxers = array(
    '3dostr', '4xm', 'aa', 'aac', 'aax', 'ac3', 'ac4', 'ace', 'acm', 'act', 'adf', 'adp', 'ads', 'adx', 'aea', 'afc', 'aiff', 'aix', 'alaw', 'alias_pix',
    'alp', 'amr', 'amrnb', 'amrwb', 'anm', 'apac', 'apc', 'ape', 'apm', 'apng', 'aptx', 'aptx_hd', 'aqtitle', 'argo_asf', 'argo_brp', 'argo_cvg', 'asf',
    'asf_o', 'ass', 'ast', 'au', 'av1', 'avi', 'avr', 'avs', 'avs2', 'avs3', 'bethsoftvid', 'bfi', 'bfstm', 'bin', 'bink', 'binka', 'bit', 'bitpacked',
    'bmp_pipe', 'bmv', 'boa', 'bonk', 'brender_pix', 'brstm', 'c93', 'caf', 'cavsvideo', 'cdg', 'cdxl', 'cine', 'codec2', 'codec2raw', 'cri_pipe',
    'data', 'daud', 'dcstr', 'dds_pipe', 'derf', 'dfa', 'dfpwm', 'dhav', 'dirac', 'dnxhd', 'dpx_pipe', 'dsf', 'dsicin', 'dss', 'dts', 'dtshd', 'dv',
    'dvbsub', 'dvbtxt', 'dxa', 'ea', 'ea_cdata', 'eac3', 'epaf', 'evc', 'exr_pipe', 'f32be', 'f32le', 'f64be', 'f64le', 'ffmetadata', 'film_cpk',
    'filmstrip', 'fits', 'flac', 'flic', 'flv', 'frm', 'fsb', 'fwse', 'g722', 'g723_1', 'g726', 'g726le', 'g729', 'gdv', 'gem_pipe', 'genh', 'gif',
    'gif_pipe', 'gsm', 'gxf', 'h261', 'h263', 'h264', 'hca', 'hcom', 'hdr_pipe', 'hevc', 'hnm', 'ico', 'idcin', 'idf', 'iff', 'ifv', 'ilbc', 'image2',
    'image2pipe', 'ingenient', 'ipmovie', 'ipu', 'ircam', 'iss', 'iv8', 'ivf', 'ivr', 'j2k_pipe', 'jacosub', 'jpeg_pipe', 'jpegls_pipe', 'jpegxl_anim',
    'jpegxl_pipe', 'jv', 'kux', 'kvag', 'laf', 'libgme', 'libopenmpt', 'live_flv', 'lmlm4', 'loas', 'lrc', 'luodat', 'lvf', 'lxf', 'm4v', 'matroska',
    'mca', 'mcc', 'mgsts', 'microdvd', 'mjpeg', 'mjpeg_2000', 'mlp', 'mlv', 'mm', 'mmf', 'mods', 'moflex', 'mov', 'mp3', 'mpc', 'mpc8', 'mpeg', 'mpegts',
    'mpegtsraw', 'mpegvideo', 'mpjpeg', 'mpl2', 'mpsub', 'msf', 'msnwctcp', 'msp', 'mtaf', 'mtv', 'mulaw', 'musx', 'mv', 'mvi', 'mxf', 'mxg', 'nc',
    'nistsphere', 'nsp', 'nsv', 'nut', 'nuv', 'obu', 'ogg', 'oma', 'osq', 'paf', 'pam_pipe', 'pbm_pipe', 'pcx_pipe', 'pdv', 'pfm_pipe', 'pgm_pipe',
    'pgmyuv_pipe', 'pgx_pipe', 'phm_pipe', 'photocd_pipe', 'pictor_pipe', 'pjs', 'pmp', 'png_pipe', 'pp_bnk', 'ppm_pipe', 'psd_pipe', 'psxstr', 'pva',
    'pvf', 'qcp', 'qdraw_pipe', 'qoi_pipe', 'r3d', 'rawvideo', 'realtext', 'redspark', 'rka', 'rl2', 'rm', 'roq', 'rpl', 'rsd', 'rso', 's16be', 's16le',
    's24be', 's24le', 's32be', 's32le', 's337m', 's8', 'sami', 'sbc', 'sbg', 'scc', 'scd', 'sdns', 'sdr2', 'sds', 'sdx', 'ser', 'sga', 'sgi_pipe', 'shn',
    'siff', 'simbiosis_imx', 'sln', 'smjpeg', 'smk', 'smush', 'sol', 'sox', 'spdif', 'srt', 'stl', 'subviewer', 'subviewer1', 'sunrast_pipe', 'sup',
    'svag', 'svg_pipe', 'svs', 'swf', 'tak', 'tedcaptions', 'thp', 'tiertexseq', 'tiff_pipe', 'tmv', 'truehd', 'tta', 'tty', 'txd', 'ty', 'u16be',
    'u16le', 'u24be', 'u24le', 'u32be', 'u32le', 'u8', 'usm', 'v210', 'v210x', 'vag', 'vbn_pipe', 'vc1', 'vc1test', 'vidc', 'vividas', 'vivo', 'vmd',
    'vobsub', 'voc', 'vpk', 'vplayer', 'vqf', 'vvc', 'w64', 'wady', 'wav', 'wavarc', 'wc3movie', 'webm_dash_manifest', 'webp_pipe', 'webvtt', 'wsaud',
    'wsd', 'wsvqa', 'wtv', 'wv', 'wve', 'xa', 'xbin', 'xbm_pipe', 'xmd', 'xmv', 'xpm_pipe', 'xvag', 'xwd_pipe', 'xwma', 'yop', 'yuv4mpegpipe');
  $DemuxerWhitelist = implode(',', ((bool)$readsStream ? $streamDemuxers : $mediaDemuxers));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $streamDemuxers, $mediaDemuxers, $readsStream);
  return $DemuxerWhitelist; }
// / -----------------------------------------------------------------------------------

?>
