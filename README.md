<p align="center">
  <img src="https://github.com/zelon88/HRConvert2/blob/master/UI/Default/Resources/Image/logo.png" alt="HRConvert2" width="200"/>
</p>

<h1 align="center">HRConvert2</h1>

<p align="center">
  <strong>A self-hosted, drag-and-drop file conversion server.</strong><br>
  No database. No cookies. No accounts. No tracking. Nothing leaves your server.
</p>

<p align="center">
  <a href="https://github.com/zelon88/HRConvert2/blob/master/Documentation/INSTALLATION_INSTRUCTIONS.txt"><strong>Installation Instructions</strong></a> ·
  <a href="https://github.com/zelon88/HRConvert2/tree/master/Documentation">Documentation</a> ·
  <a href="https://hub.docker.com/r/zelon88/hrconvert2">Docker Image</a>
</p>

<p align="center">
  <img src="https://github.com/zelon88/HRConvert2/blob/master/Documentation/Screenshots/new-webui.png" alt="The HRConvert2 upload interface"/>
</p>

---

## Why HRConvert2

Every free online file converter is a data collection business. You upload a contract, a
medical scan, a CAD drawing of something you have not patented yet, and you have handed it
to a stranger with an ad network.

HRConvert2 is the same convenience without the transaction. It runs on hardware you control,
converts files locally, and deletes them on a timer you set. Users need no account and leave
no trace. It will run on a Raspberry Pi.

- **Nothing is transmitted anywhere.** Conversions happen on your machine using local tools.
- **No accounts, no sessions, no cookies, no database.** A user is a temporary directory.
- **No tracking of any kind.** No analytics, no telemetry, no external fonts, no CDN.
- **Files are deleted automatically** once they pass the age threshold you configure.
- **Every user gets isolated scratch space.** Nobody can see anybody else's files.
- **Drag, drop, convert, download.** That is the entire workflow.

---

## Features

**447 file formats** across documents, spreadsheets, presentations, images, audio, video,
streams, 3D models, CAD drawings, vector graphics, subtitles & archives.

- Optical Character Recognition on PDFs & images.
- OpenSCAD rendering, with every render isolated in an operating system sandbox.
- Live stream capture from `.m3u8` playlists, with full SSRF inspection before any fetch.
- On-demand virus scanning with ClamAV or [scanCore](https://github.com/zelon88/scanCore).
- Automatic background virus scanning of every upload, if you want it.
- Temporary share links that expire with the file.
- **26 languages**, switchable by the user, built in. No language packs to install.
- **3 interfaces & 7 color schemes**, switchable by the user.
- Right-to-left layout support for Arabic, Hebrew, Persian, Urdu & Syriac.
- Installs cleanly alongside WordPress & other software on the same server.
- Every dependency version-checked at runtime, so a broken install says so instead of failing quietly.

---

## Built To Be Exposed

HRConvert2 is designed for public-facing deployment, which means it is built to be attacked.

- **Uploads are sanitized** before any dependency touches them.
- **OpenSCAD renders run inside a bubblewrap namespace** that can see nothing but the one
  session directory. OpenSCAD reads arbitrary files by design & cannot be given a sandbox
  through its own arguments, so the operating system provides one. A server that cannot
  create that sandbox refuses OpenSCAD conversions rather than falling back.
- **Stream files are fully inspected before FFMPEG sees them.** Every referenced host is
  resolved without following redirects, checked against private & reserved address ranges,
  and pinned by IP so no dependency can be redirected to your internal network.
- **Session identifiers are derived from a per-install secret**, generated with a CSPRNG at
  install time & never transmitted.
- **Every dependency is version-pinned** against known-vulnerable releases.
- **Errors are documented.** Every numbered error has an entry explaining the cause &
  the fix in [ERROR_DESCRIPTIONS.txt](https://github.com/zelon88/HRConvert2/blob/master/Documentation/ERROR_DESCRIPTIONS.txt).

Security reports are welcome & are taken seriously. Several of the protections above exist
because somebody took the time to find & report a real flaw.

---

## Requirements

Debian or Ubuntu Linux, Apache 2.4 & PHP 8 or later. Everything else is a package install.

A Raspberry Pi Model B+ is enough to run it. Anything x86 or x64 will be comfortable.

Full dependency list & a step-by-step walkthrough are in the
[Installation Instructions](https://github.com/zelon88/HRConvert2/blob/master/Documentation/INSTALLATION_INSTRUCTIONS.txt).
A [Docker image](https://hub.docker.com/r/zelon88/hrconvert2) is also available.

---

## Languages

Users switch languages from the interface, or you can force one in `config.php`, or pass it
in the URL with `?language=de`. Every translation ships with the application.

| | | | |
|---|---|---|---|
| English `en` | Français `fr` | Español `es` | 中文 `zh` |
| हिन्दी `hi` | العربية `ar` | Русский `ru` | Українська `uk` |
| বাংলা `bn` | Deutsch `de` | 한국어 `ko` | Italiano `it` |
| Português `pt` | Tiếng Việt `vi` | Türkçe `tr` | 日本語 `ja` |
| Bahasa Indonesia `id` | Polski `pl` | Nederlands `nl` | Kiswahili `sw` |
| မြန်မာ `my` | اردو `ur` | فارسی `fa` | עברית `he` |
| ܣܘܪܝܝܐ `aii` | ܐܪܡܝܐ `arc` | | |

Adding a language means adding one folder. The application is built so that communities can
translate it for themselves without touching a single line of application code.

---

## Interface & Appearance

Three interfaces ship with the application: **Default**, **Wide** & **Original**. Seven color
schemes: red, green, blue, grey, orange, purple & dark.

Users pick their own from the interface. Administrators can lock any of it down in
`config.php`. Every interface lives in its own folder under `/UI` & can be forked without
touching the core, so a deployment can carry its own branding.

---

<details>
<summary><strong>Supports 457 Formats</strong> — click to expand
</summary>

#### Audio
Mp3, Mp2, Aac, Flac, Ogg, Opus, Wav, Wma, M4a, M4p, Aiff, Ac3, Ac4, Eac3, Alac, Ape, Amr,
Au, Caf, Dts, Gsm, Mlp, Oga, Spx, Tak, Tta, Voc, W64, Wv, G722, G726, Aptx, Adx, Shn, Sox
& hundreds more.
Output bitrate is selectable.

#### Video
Mp4, Mkv, Avi, Mov, Wmv, Flv, Mpeg, M4v, 3gp, 3g2, Webm, Ogv, Asf, Vob, Rm, Swf, Dv, Av1,
H261, H263, H264, Hevc, Dnxhd, Mpegts, Mxf, Nsv, Ivf, R3d, Apng, Cdg, Yuv4mpegpipe
& hundreds more.

#### Streams
M3u8. Captures a live stream & converts it into any supported video or audio format.

#### Documents
Doc, Docx, Txt, Rtf, Odt, Pdf.

#### Spreadsheets
Csv, Xls, Xlsx, Ods.

#### Presentations
Pptx, Ppt, Pot, Potx, Potm, Ppa, Odp, Xps, Oxps.

#### Archives & Disk Images
Zip, Rar, Tar, 7z, Bz, Gz, Bz2, Tar.bz2, Tar.gz, Iso, Vhd, Vdi, Cbr, Cbz.
Converts between archive formats & disk image formats.

#### Images
Jpg, Jpeg, Jpe, Png, Bmp, Gif, Webp, Heic, Ico, Avif, Flif, Cin, Dds, Dib, Gplt, Sct, Xcf.
Supports resize & rotate. Photographs of documents can be converted into documents.

#### 3D Models
3ds, Obj, Collada, Off, Ply, Stl, Gts, Ptx, Dxf, U3d, X3d, Vrml.

#### OpenSCAD
Renders `.scad` source into Stl, Off, Amf, 3mf & Csg. Multi-file assemblies are supported.

#### Vector Graphics
Svg, converted with Inkscape. Supports export sizing.

#### Technical Drawings
Dxf, Fig, Vdx, Dia, Wpg. Converts drawings into image formats.

#### Subtitles
Srt, Vtt, WebVTT, Ass, Ssa, Sub, Sbv, Sup, Ttml, Scc, Sami, Vobsub, Mpl2, Mpsub, Pjs,
Realtext, Subviewer, Jacosub, Microdvd, Vplayer, Tedcaptions, Dvb & many more.

#### OCR
Reads Jpg, Jpeg, Png, Bmp, Pdf & Gif. Writes Doc, Docx, Txt, Rtf, Odt & Pdf.

</details>

---

## Screenshots

<p align="center">
  <img src="https://github.com/zelon88/HRConvert2/blob/master/Documentation/Screenshots/new-selectorbar.png" alt="The HRConvert2 upload options menu"/>
</p>

<p align="center">
  <img src="https://github.com/zelon88/HRConvert2/blob/master/Documentation/Screenshots/new-selectorbar2.png" alt="The HRConvert2 interface options menu"/>
</p>

<p align="center">
  <img src="https://github.com/zelon88/HRConvert2/blob/master/Documentation/Screenshots/new-webui2.png" alt="The HRConvert2 conversion options menu"/>
</p>

More screenshots are in [Documentation/Screenshots](https://github.com/zelon88/HRConvert2/tree/master/Documentation/Screenshots).

---

## Contributing

Translations, interface themes & bug reports are all welcome. The application is deliberately
modular: a language is a folder, an interface is a folder, & neither requires touching the core.

If you find a security issue, please open an issue. Reports that include a reproduction are
worth their weight & have directly shaped this project.

---

## License

[GNU General Public License v3.0](https://github.com/zelon88/HRConvert2/blob/master/Documentation/LICENSE).

Free to use, free to modify, free to self-host. If you improve it, send it back.