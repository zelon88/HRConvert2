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
  <img src="https://github.com/zelon88/HRConvert2/blob/master/Documentation/Screenshots/new-selector-bar2.png" alt="The HRConvert2 options interface"/>
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

**488 file formats** across documents, ebooks, comics, spreadsheets, presentations, images, audio, video,
streams, 3D models, CAD drawings, vector graphics, subtitles & archives.

- **Resource Aware** Throttles conversions to available CPU & RAM. Stops accepting requests before falling over.
- **Load Balanced, Redundant Storage Locations** Optionally `leastactive`, `roundrobin`, or `redundant` storage configuration.
- **Capable Of Handling An _Enormous Amount of Web Traffic_** HRConvert2 responds intelligently to heavy loads.
- **Bootable disk images.** Builds MBR, UEFI & hybrid ISO images from uploaded files, for
  x86, x86-64, ARM32 & ARM64. Bundled bootloaders are hash-pinned & verified before use.
- **Conversions run inside their own sandbox.** Called dependencies cannot see the filesystem.
- **A command line interface** for updating, diagnostics & maintenance.
- **Creates bootable iso images** from any archive format. Supports 5 system architecture types automatically.
- **Optical Character Recognition**, on PDFs & images. Reads pictures, outputs documents.
- **OpenSCAD rendering**, with multi-file assemblies supported. Automatically resolves include paths.
- **Live stream capture/re-encoding** from `.m3u8` playlists, with full SSRF inspection before any fetch.
- **Virus Scanning** with ClamAV or [scanCore](https://github.com/zelon88/scanCore).
- **Temporary Share Links** that expire with the file.
- **26 languages**, switchable by the user, built in. No language packs to install.
- **3 interfaces & 7 color schemes**, switchable from within the page.
- **Proper Right-to-left language support** for Arabic, Hebrew, Persian, Urdu & Syriac.
- **Every dependency is version pinned & validated.** Comprehensive binary validation performed at runtime.
- **Self-updating from the command line**, with automatic rollback if the update will not run.
- **Installs cleanly alongside other software** on the same server, like WordPress. Does not live in the web root.

---

## Built To Be Exposed

HRConvert2 is designed for public-facing deployment, which means it is built to be attacked.

**Every conversion dependency runs inside a bubblewrap namespace.** ImageMagick, FFMPEG,
Inkscape, Dia, Assimp, MeshLab, 7-Zip, Tesseract, pdftotext, OpenSCAD & every archive
utility. Each conversion sees exactly two directories — the one holding its input, mounted
read only, and the one receiving its output. Nothing else on the disk exists inside the
namespace. The network is unshared, which closes every URL handler in every dependency at
once. A server that cannot build a sandbox **refuses the conversion** rather than quietly
running without one.

- **Uploads are sanitized** before any dependency touches them.
- **OpenSCAD reads arbitrary files by design** & cannot be given a sandbox through its own
  arguments, so the operating system provides one. Filtering the source is a convenience
  layer, not a boundary — four bypasses were reported against the line-oriented filter & a
  fifth against the stateful rewrite that replaced it. The sandbox is the boundary.
- **Stream files are fully inspected before FFMPEG sees them.** Every referenced host is
  resolved without following redirects, checked against private & reserved address ranges,
  and pinned by IP so no dependency can be redirected to your internal network.
- **Session identifiers are derived from a per-install secret**, generated with a CSPRNG at
  install time & never transmitted.
- **Every dependency is version-pinned** & the pin is verified at runtime, not at install
  time. `php convertCore.php -v` reports whether every one of them actually satisfies it.
- **Updates are never reachable over HTTP.** Replacing application code requires shell
  access, which is the correct authorization for the operation. An endpoint protected by a
  secret would reduce that to one guessable string.
- **Errors are documented.** Every numbered error has an entry explaining the cause &
  the fix in [ERROR_DESCRIPTIONS.txt](https://github.com/zelon88/HRConvert2/blob/master/Documentation/ERROR_DESCRIPTIONS.txt).

Security reports are welcome & are taken seriously. Several of the protections above exist
because somebody took the time to find & report a real flaw.

---

## Command Line

HRConvert2 answers to the command line as well as to a browser. The two are mutually
exclusive — an argument supplied on the command line disables the web interface entirely for
that invocation, creates no session & touches no user data.

```
  -v, --version               Display version & dependency information.
  -h, --help                  Display the built in help message.
  --status                    Report listener, budget & data location state.
  -c, --clean                 Sweep expired sessions from both data locations.
  -c=<minutes>                Sweep sessions older than that many minutes.
  -c=now                      Sweep every session regardless of age.
  -u, --update                Update the application using the configured target.
  -u=latest                   Update to the newest tagged release.
  -u=edge                     Update to the current state of the master branch.
  -u=v#.#.#                   Update to exactly that tagged release.
  -fp, --fix-permissions      Correct ownership, permissions & policy files.
  -l, --listen                Start the resource listener.
  -k, --kill                  Stop the resource listener.
  -k <worker-id>              End one worker by budget token or process identifier.
  --kill-all-workers          End every tracked conversion in progress.
  --kill-every-worker         End every PHP process owned by the web server user.
  -y, --yes                   Skip the confirmation prompt on the two above.
```

`-v` is the useful one. It is not an echo of a version number — it runs every dependency
check the converters run, enumerates every installed interface & language pack, & reports
which of them actually work. One command answers *will this install convert anything*, which
is a different question from *what is configured*.

`-u` downloads a release, merges your existing configuration into the new one, swaps the
installation atomically, then asks the new core to report its own version. An installation
that cannot answer is rolled back automatically & the previous version is preserved.

`-fp` is a powerful repair utility that will correct most permissions issues, if run as root.
This also repairs apparmor policy & IM7 policy files.

Full details in
[USING_COMMAND_LINE.txt](https://github.com/zelon88/HRConvert2/blob/master/Documentation/USING_COMMAND_LINE.txt) or [USING_COMMAND_LINE.md](https://github.com/zelon88/HRConvert2/blob/master/Documentation/USING_COMMAND_LINE.md).

<p align="center">
  <img src="https://github.com/zelon88/HRConvert2/blob/master/Documentation/Screenshots/command-line-version-argument-small.png" alt="The HRConvert2 interface options menu"/>
</p>

<p align="center">
  <img src="https://github.com/zelon88/HRConvert2/blob/master/Documentation/Screenshots/command-line-help-argument-small.png" alt="The HRConvert2 interface options menu"/>
</p>

## Optional Resource Awareness

-**Resource Awareness is Disabled by Default** HRConvert2 will accept every conversion it is offered, in stock form. This is considered "*Standalone*" mode.
-**When Optional Resource Awareness is Enabled** HRConvert2 will attempt to connect to a local resource listener through memory sockets to request resources to perform the conversion.
-**Fails Open By Default** HRConvert2 will proceed with a conversion in stock form when no resource listener is available to service it's request for resources.
-**When Resource Awareness is Enabled** Running `sudo php convertCore.php --listen` will start four "*Manager*" processes. HRConvert2 worker processes switch from "*Standalone*" mode to "*Worker*" mode.
-**The Resource Manager** holds a budget derived from processor count, load average and memory pressure.
-**A Worker Asks Permission** before it consumes budget, and returns its budget upon completion.
-**An Idle Server Throttles Nothing.** A loaded server eventually stop accepting work before it falls over. This is entirely configurable in a variety of ways.
-**Individual Conversions Are Resource Limited** By dynamically capping CPU and RAM consumption on a per-conversion basis based on load.
-**Number Of Concurrent Sessions Are Limited** By dynamically preventing the server from accepting more work than it can handle. And by throttlling back operations that it's taking in.
-**Workers Are Reaped Automatically** Workers lifecycle is managed by the "*Worker Manager*". Runaway workers are detected and detroyed. 
-**Fails Open - Even More** A missing component, a version mismatch, a dead listener, an unreachable socket or an unanswered request all let the conversion proceed with a warning.
-**Only Explicit Refusals From The Budget Throttle Conversions, Unless Configured Otherwise** Refusal logic can be inverted to *Force* Resource Awareness, and refuse when it is unavailable.
-**Load Balanced Or Redundant Storage** Sessions can be spread across several storage paths, chosen round robin, by least active, or with a standby held in reserve. A session is assigned a location once and keeps it for life, so a load balanced deployment never loses track of a user's files. Build high-accessibility, high-performance storage arrays from file paths directly in `config.php`. 
-**Resource Awareness Means Disk Drive Awareness** The "Resource Manager" maintains performance of the *Entire Server*. Including storage devices.

**Useful Commands**
```
sudo php convertCore.php -l         # start the listener
php convertCore.php --status        # see what it is doing
sudo php convertCore.php -k         # stop it
sudo php convertCore.php -fp        # fix permissions, repair common problems

Full detail is in [About Resource Rate Limiting.txt](https://github.com/zelon88/HRConvert2/blob/master/Documentation/ABOUT_RESOURCE_RATE_LIMITING.txt) or [About Resource Rate Limiting.md](https://github.com/zelon88/HRConvert2/blob/master/Documentation/ABOUT_RESOURCE_RATE_LIMITING.md).
```

---

## Requirements

Debian or Ubuntu Linux, Apache 2.4 & PHP 8 or later. Everything else is a package install.

A Raspberry Pi Model B+ is enough to run it. Anything x86 or x64 will be comfortable.

**Bubblewrap is required.** Debian 12 & Ubuntu 24.04 restrict unprivileged user namespaces by
default. Running `sudo php convertCore.php -fp` installs the AppArmor profile that lifts the 
restriction, repairs the ImageMagick policy, and corrects ownership across the installation.
Run it once after installing, and again after any command line invocation made as root.
The [Installation Instructions](https://github.com/zelon88/HRConvert2/blob/master/Documentation/INSTALLATION_INSTRUCTIONS.txt) cover the rest.
[Docker](https://hub.docker.com/r/zelon88/hrconvert2) version is also available.

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

<p align="center">
  <img src="https://github.com/zelon88/HRConvert2/blob/master/Documentation/Screenshots/new-selector-bar3-small.png" alt="The HRConvert2 interface options menu"/>
</p>

---

## Interface & Appearance

Three interfaces ship with the application: **Default**, **Wide** & **Original**. Seven color
schemes: red, green, blue, grey, orange, purple & dark.

Users pick their own from a selector inside the page — language by flag, colour by swatch,
interface by name. Administrators can lock any of it down in `config.php`. Every interface
lives in its own folder under `/UI` & can be forked without touching the core, so a
deployment can carry its own branding.

Interfaces & language packs are version-checked against the core. One that does not match is
not loaded, & the default is used instead rather than rendering a broken page.

<p align="center">
  <img src="https://github.com/zelon88/HRConvert2/blob/master/Documentation/Screenshots/new-selector-bar-small.png" alt="The HRConvert2 upload options menu"/>
</p>

<p align="center">
  <img src="https://github.com/zelon88/HRConvert2/blob/master/Documentation/Screenshots/new-selector-bar2-small.png" alt="The HRConvert2 interface options menu"/>
</p>

---

<details>
<summary><strong>Supports 488 Formats</strong> — click to expand
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

#### E-Books & Comics
Epub, Mobi, Azw, Azw3, Azw4, Fb2, Fbz, Lit, Lrf, Pdb, Pml, Rb, Snb, Tcr, Txtz, Chm,
Cbc, Prc, Opf, Recipe, Oeb, plus Txt, Rtf, Odt, Docx & Pdf.
Converts between e-book formats & document formats.

#### Spreadsheets
Csv, Xls, Xlsx, Ods.

#### Presentations
Pptx, Ppt, Pot, Potx, Potm, Ppa, Odp, Xps, Oxps.

#### Archives & Disk Images
Zip, Rar, Tar, 7z, Bz, Gz, Bz2, Tar.bz2, Tar.gz, Iso, Vhd, Vdi, Cbr, Cbz.
Converts between archive formats & disk image formats.

#### Bootable Disk Images
Converts any supported archive format into a bootable ISO from any uploaded file set.
Supports Legacy, UEFI for x86, x86-64, ARM32 & ARM64, MBR/GPT hybrid.
Included bootloaders are cryptographically validated at runtime.

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
  <img src="https://github.com/zelon88/HRConvert2/blob/master/Documentation/Screenshots/new-selector-bar3.png" alt="The HRConvert2 interface options menu"/>
</p>

<p align="center">
  <img src="https://github.com/zelon88/HRConvert2/blob/master/Documentation/Screenshots/new-selector-bar.png" alt="The HRConvert2 upload options menu"/>
</p>

<p align="center">
  <img src="https://github.com/zelon88/HRConvert2/blob/master/Documentation/Screenshots/new-selector-bar2.png" alt="The HRConvert2 interface options menu"/>
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