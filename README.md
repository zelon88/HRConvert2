<p align="center">
  <img src="https://github.com/zelon88/HRConvert2/blob/master/UI/Default/Resources/Image/logo.png" alt="HRConvert2" width="200"/>
</p>

<h1 align="center">🚀 HRConvert2</h1>

<p align="center">
  <strong>Convert literally anything, keep all your data completely private.</strong><br>
  No database. No cookies. No accounts. No tracking. Nothing leaves your server.
</p>

<p align="center">
  <a href="https://github.com/zelon88/HRConvert2/blob/master/Documentation/INSTALLATION_INSTRUCTIONS.txt"><strong>⚡ Get Started</strong></a> ·
  <a href="https://github.com/zelon88/HRConvert2/tree/master/Documentation">📚 Documentation</a> ·
  <a href="https://hub.docker.com/r/zelon88/hrconvert2">🐳 Docker</a>
</p>

<p align="center">
  <img src="https://github.com/zelon88/HRConvert2/blob/master/Documentation/Screenshots/new-selector-bar2.png" alt="The HRConvert2 options interface" width="600"/>
</p>

---

## 🤔 Why HRConvert2?

Every free online file converter is actually a **data collection business in disguise**. Upload a contract, medical scan, or proprietary CAD file? Congratulations—you just handed it to a stranger with an ad network attached.

**HRConvert2 is different.** Same convenience. Zero compromise.

- ✅ Conversions happen on **your machine** using local tools
- ✅ Users need **no account** and leave **no trace**
- ✅ Runs on a **Raspberry Pi**
- ✅ Drag, drop, convert, download—done

### The Privacy Promise

| Feature | Status |
|---------|--------|
| **Nothing transmitted anywhere** | ✓ Conversions run locally |
| **No accounts, sessions, or cookies** | ✓ Users = temporary directories |
| **Zero tracking** | ✓ No analytics, telemetry, or CDN |
| **Auto-delete files** | ✓ Configurable age-based cleanup |
| **Isolated user storage** | ✓ Nobody can see anybody else's files |

---

## 🚀 Quick Start

### Option 1: Traditional Install (30 seconds)
```bash
git clone https://github.com/zelon88/HRConvert2
sudo bash HRConvert2/Documentation/Build/hrconvert2-setup.sh
```

The installer handles everything:
- Pulls all dependencies
- Creates storage
- Sets up AppArmor profile & PHP settings
- Installs systemd unit
- Tells you what's working

Then open `http://your-server/HRProprietary/HRConvert2/convertCore.php` and start converting.

**Pro tip:** Add `-y` to skip confirmations.

### Option 2: Docker (Even Faster)
```bash
docker run -d -p 8080:80 --cap-add SYS_ADMIN zelon88/hrconvert2
```

> ⚠️ **Note:** `SYS_ADMIN` is required for the security sandbox. Without it, HRConvert2 refuses conversions rather than running them unprotected.

### Troubleshooting
```bash
sudo php convertCore.php -fp
```
This fixes 99% of issues and tells you exactly what it corrected.

---

## ✨ What Can It Convert?

**488 formats** across every category imaginable:

| Category | Formats |
|----------|---------|
| 📄 **Documents** | .doc, .docx, .txt, .rtf, .odt, .pdf |
| 🎬 **Video** | .mp4, .mkv, .avi, .mov, .webm, .flv, + 50+ more |
| 🎵 **Audio** | .mp3, .flac, .aac, .wav, .opus, + 30+ more |
| 📊 **Spreadsheets** | .csv, .xls, .xlsx, .ods |
| 🎨 **Images** | .jpg, .png, .gif, .webp, .svg, .ico, + more |
| 📚 **E-Books** | .epub, .mobi, .azw, .azw3 |
| 🎞️ **Presentations** | .pptx, .ppt, .odp |
| 📦 **Archives** | .zip, .rar, .7z, .tar, .gz |
| 🖥️ **3D Models** | .obj, .stl, .dxf, .collada |
| ⚙️ **Technical** | OpenSCAD, CAD drawings, Vector graphics |
| 💿 **Bootable ISO** | Create bootable images for x86, ARM, UEFI |
| 🔍 **OCR** | Extract text from images and PDFs |
| 📺 **Live Streams** | Capture and convert from .m3u8 playlists |

---

## 🔒 Security First

HRConvert2 is built for **public-facing deployment**, which means it's built to be attacked. Here's what makes it bulletproof:

### Sandboxing
Every conversion dependency runs inside a **bubblewrap namespace**:
- ImageMagick, FFmpeg, Inkscape, OpenSCAD—all of them
- Each process sees only its input (read-only) and output directories
- Network is unshared—no external URL handlers
- **No sandbox = no conversion** (HRConvert2 refuses rather than risks it)

### Smart Protections
- 🔐 **Session identifiers** derived from a per-install secret (CSPRNG)
- 📌 **Dependencies version-pinned** and verified at runtime
- 🛡️ **Uploads sanitized** before any tool touches them
- 🔗 **Stream URLs** fully inspected before FFmpeg sees them
- 🔄 **Updates require shell access** (not guessable secrets)
- 📖 **Every error documented** with cause and fix

### Ransomware-Proof
Conversions run with minimal permissions—they can't encrypt your entire disk.

---

## 🌍 Global & Inclusive

### 26 Built-in Languages
Users switch languages instantly from the interface, no packs to install:

| | | | |
|---|---|---|---|
| English | Français | Español | 中文 |
| हिन्दी | العربية | Русский | Українська |
| বাংলা | Deutsch | 한국어 | Italiano |
| Português | Tiếng Việt | Türkçe | 日本語 |
| Bahasa Indonesia | Polski | Nederlands | Kiswahili |
| မြန်မာ | اردو | فارسی | עברית |

### Proper RTL Support
Arabic, Hebrew, Persian, Urdu, and Syriac display perfectly—right-to-left layouts work correctly.

### Community-Driven
Adding a language is as simple as adding one folder. No touching the core code required!

---

## 🎨 Customizable Interfaces

Choose your vibe:
- **3 Interface layouts** (Default, Wide, Original)
- **7 Color schemes** (Red, Green, Blue, Grey, Orange, Purple, Dark)
- **Switch from within the page**—no page reload

Admins can lock down preferences in config.php. Each interface is a separate folder—fork it and brand it however you want without touching the core.

---

## 💪 Advanced Features

### 🔋 Resource-Aware (Optional)
Turn on smart resource management to prevent overload:
- CPU & memory caps scale based on system load
- Runaway conversions auto-terminate
- Storage spans multiple disks (round-robin, least-active, or failover)
- System watchdog monitors everything

Best part: It fails open. No budget = conversion runs anyway. Nothing can take you offline.

### 🛠️ Command-Line Power
Control everything from the terminal:

```bash
php convertCore.php -v              # Show version & check all dependencies
php convertCore.php --status        # See current system state
php convertCore.php -u=latest       # Update to newest release
php convertCore.php -c=now          # Clean all expired sessions
php convertCore.php -fp             # Fix permissions & repair issues
php convertCore.php --config        # Interactive config wizard
php convertCore.php -l              # Start resource listener
php convertCore.php -k              # Stop resource listener
```

### 📊 Load Balancing
Distribute storage across multiple drives:
- Round-robin for even distribution
- Least-active for dynamic load
- Redundant for failover backup

### 🔄 Auto-Update
Updates pull a release, merge your config, swap atomically, then verify. Can't run? Rolls back automatically.

---

## 📋 Requirements

| Requirement | Minimum |
|-------------|---------|
| **OS** | Debian or Ubuntu Linux |
| **Web Server** | Apache 2.4 |
| **PHP** | 8.0 or later |
| **Hardware** | Raspberry Pi Model B+ (or any x86/x64) |
| **Sandbox** | Bubblewrap (required & non-negotiable) |

The installer handles all dependencies. Seriously, just run the script.

---

## 📸 Screenshots

More screenshots available in [Documentation/Screenshots](https://github.com/zelon88/HRConvert2/tree/master/Documentation/Screenshots).

---

## Supported Formats

**488 file formats** including:

- **Audio**: Mp3, Flac, Aac, Wav, Opus, Wma, + 30+ more
- **Video**: Mp4, Mkv, Avi, Mov, Webm, Flv, + 50+ more
- **Documents**: Doc, Docx, Pdf, Txt, Odt, Rtf
- **E-Books**: Epub, Mobi, Azw, Azw3, Azw4
- **Spreadsheets**: Csv, Xls, Xlsx, Ods
- **Presentations**: Pptx, Ppt, Odp
- **Archives**: Zip, Rar, 7z, Tar, Gz, Bz2
- **Images**: Jpg, Png, Gif, Webp, Svg, Ico, Heic
- **3D Models**: Obj, Stl, Dxf, Collada, Ply
- **Technical**: OpenSCAD, CAD drawings, Vector graphics
- **Bootable ISO**: Create bootable images for x86, ARM, UEFI
- **OCR**: Extract text from images and PDFs
- **Live Streams**: Capture and convert from .m3u8 playlists
- **Subtitles**: Srt, Vtt, Ass, Sub, Sbv, Ttml, + more

---

## 🤝 Contributing

We love contributions! Here's what we need:

- 🌐 **New language translations** — Just add a folder!
- 🎨 **Interface themes** — Fork a UI folder and get creative
- 🐛 **Bug reports** — Include repro steps if you can
- 🔐 **Security reports** — Please open an issue (we take them seriously!)

The architecture is deliberately modular: a language is a folder, an interface is a folder. Neither requires touching the core.

---

## 📄 License

GNU General Public License v3.0

**Free to use. Free to modify. Free to self-host.**

If you improve it, send it back. 💚

---

Made with ❤️ for privacy-conscious developers