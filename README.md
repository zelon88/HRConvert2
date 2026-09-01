<p align="center">
  <img src="https://github.com/zelon88/HRConvert2/blob/master/UI/Default/Resources/Image/logo.png" alt="HRConvert2" width="200"/>
</p>

<h1 align="center">HRConvert2</h1>

<p align="center">
  <strong>Convert literally anything. Keep all your data completely private.</strong><br>
  No database. No cookies. No accounts. No tracking. Nothing leaves your server.
</p>

<p align="center">
  <a href="https://github.com/zelon88/HRConvert2/blob/master/Documentation/INSTALLATION_INSTRUCTIONS.txt"><strong>Get Started</strong></a> ·
  <a href="https://github.com/zelon88/HRConvert2/tree/master/Documentation">Documentation</a> ·
  <a href="https://hub.docker.com/r/zelon88/hrconvert2">Docker Image</a>
</p>

<p align="center">
  <img src="https://github.com/zelon88/HRConvert2/blob/master/Documentation/Screenshots/new-selector-bar2.png" alt="The HRConvert2 options interface" width="600"/>
</p>

---

## Why HRConvert2?

Every free online file converter is actually a data collection business in disguise. Upload a contract, medical scan, or proprietary CAD file? You just handed it to a stranger with an ad network attached.

**HRConvert2 is different.** Same convenience. Zero compromise.

- Conversions happen on **your machine** using local tools
- Users need **no account** and leave **no trace**
- Runs on a **Raspberry Pi**
- Workflow: Drag, drop, convert, download

### The Privacy Promise

| Feature | What You Get |
|---------|--------------|
| **Nothing transmitted** | Conversions run locally only |
| **No accounts or sessions** | Users are temporary directories |
| **Zero tracking** | No analytics, telemetry, or CDN |
| **Auto-delete files** | Configurable age-based cleanup |
| **Isolated storage** | Users cannot see each other's files |

---

## Quick Start

### Traditional Install (30 seconds)
```bash
git clone https://github.com/zelon88/HRConvert2
sudo bash HRConvert2/Documentation/Build/hrconvert2-setup.sh
```

The installer handles everything:
- Pulls all dependencies
- Creates storage
- Sets up AppArmor profile & PHP settings
- Installs systemd unit
- Reports what's working

Then open `http://your-server/HRProprietary/HRConvert2/convertCore.php` and start converting.

**Pro tip:** Add `-y` to skip confirmations.

### Docker
```bash
docker run -d -p 8080:80 --cap-add SYS_ADMIN zelon88/hrconvert2
```

**Note:** `SYS_ADMIN` is required for the security sandbox. Without it, HRConvert2 refuses conversions rather than running unprotected.

### Troubleshooting
```bash
sudo php convertCore.php -fp
```
This fixes 99% of issues and reports exactly what it corrected.

---

## Supported Formats: 488 Total

| Category | Examples |
|----------|----------|
| **Documents** | doc, docx, txt, rtf, odt, pdf |
| **Video** | mp4, mkv, avi, mov, webm, flv, + 50+ more |
| **Audio** | mp3, flac, aac, wav, opus, wma, + 30+ more |
| **Spreadsheets** | csv, xls, xlsx, ods |
| **Presentations** | pptx, ppt, odp |
| **Images** | jpg, png, gif, webp, svg, ico, heic |
| **Archives** | zip, rar, 7z, tar, gz, bz2 |
| **E-Books** | epub, mobi, azw, azw3, azw4 |
| **3D Models** | obj, stl, dxf, collada, ply |
| **Technical** | OpenSCAD, CAD drawings, vector graphics |
| **Bootable ISO** | x86, ARM, UEFI, MBR/GPT hybrid |
| **OCR** | Extract text from images and PDFs |
| **Live Streams** | Capture and convert .m3u8 playlists |
| **Subtitles** | srt, vtt, ass, sub, sbv, ttml, + more |

---

## Security by Design

HRConvert2 is built for public-facing deployment—which means it's built to be attacked. Every protection is there for a reason.

### Sandboxing
Every conversion dependency runs inside a bubblewrap namespace:
- ImageMagick, FFmpeg, Inkscape, OpenSCAD—all isolated
- Each process sees only its input (read-only) and output directories
- Network is unshared—no external URL handlers
- **No sandbox = no conversion** (HRConvert2 refuses rather than takes risks)

### Multi-Layer Protections
- **Session identifiers** derived from a per-install secret (CSPRNG)
- **Dependencies version-pinned** and verified at runtime
- **Uploads sanitized** before any tool touches them
- **Stream URLs** fully inspected before FFmpeg sees them
- **Updates require shell access** (not guessable secrets)
- **Every error documented** with cause and solution

### Ransomware-Safe
Conversions run with minimal permissions—they can't encrypt your entire disk or exfiltrate data.

---

## 26 Languages Built In

Users switch languages instantly from the interface—no language packs to install:

| | | | |
|---|---|---|---|
| English | Français | Español | 中文 |
| हिन्दी | العربية | Русский | Українська |
| বাংলা | Deutsch | 한국어 | Italiano |
| Português | Tiếng Việt | Türkçe | 日本語 |
| Bahasa Indonesia | Polski | Nederlands | Kiswahili |
| မြန်မာ | اردو | فارسی | עברית |

**RTL Support:** Arabic, Hebrew, Persian, Urdu, and Syriac display with proper right-to-left layouts.

**Community-Driven:** Adding a language is as simple as adding one folder. No core code changes required.

---

## Customizable Interfaces & Appearance

Users can personalize their experience:
- **3 interface layouts:** Default, Wide, Original
- **7 color schemes:** Red, Green, Blue, Grey, Orange, Purple, Dark
- **In-page switching:** No reload needed

Admins can lock down preferences in `config.php`. Each interface lives in its own folder—fork and brand it however you want without touching the core.

---

## Advanced Features

### Resource-Aware Load Management (Optional)
Turn on smart resource management to prevent overload:
- CPU & memory caps scale with system load
- Runaway conversions auto-terminate
- Storage spans multiple disks (round-robin, least-active, or failover)
- System watchdog monitors everything

**Key design:** Fails open. No budget socket? Conversion runs anyway. Nothing can take your server offline.

### Command-Line Control
Everything is manageable from the terminal:

```bash
php convertCore.php -v              # Version & dependency check
php convertCore.php --status        # System state
php convertCore.php -u=latest       # Update to newest release
php convertCore.php -c=now          # Clean all expired sessions
php convertCore.php -fp             # Fix permissions & repair
php convertCore.php --config        # Interactive config wizard
php convertCore.php -l              # Start resource listener
php convertCore.php -k              # Stop resource listener
```

[Full command reference](https://github.com/zelon88/HRConvert2/blob/master/Documentation/USING_COMMAND_LINE.txt)

### Storage Distribution
Span storage across multiple drives with your choice of strategy:
- **Round-robin** for even distribution
- **Least-active** for dynamic load balancing
- **Redundant** for failover protection

### Auto-Update with Rollback
```bash
php convertCore.php -u=latest
```
Updates pull a release, merge your config, swap atomically, then verify. Can't run? Automatically rolls back to the previous version.

---

## System Requirements

| Requirement | Minimum |
|-------------|---------|
| **OS** | Debian or Ubuntu Linux |
| **Web Server** | Apache 2.4 |
| **PHP** | 8.0 or later |
| **Hardware** | Raspberry Pi Model B+ (any x86/x64 is comfortable) |
| **Sandbox** | Bubblewrap (required and non-negotiable) |

The installer handles all dependencies. Seriously—just run the script.

---

## Contributing

Contributions are welcome:

- **Language translations** — Just add a folder
- **Interface themes** — Fork a UI folder and get creative
- **Bug reports** — Include reproduction steps
- **Security reports** — Open an issue (we take them seriously)

The architecture is deliberately modular: a language is a folder, an interface is a folder. Neither requires touching the core.

---

## License

[GNU General Public License v3.0](https://github.com/zelon88/HRConvert2/blob/master/Documentation/LICENSE)

Free to use. Free to modify. Free to self-host.

If you improve it, send it back.

---

<p align="center">
  Made for privacy-conscious developers<br>
  <a href="https://github.com/zelon88">@zelon88</a>
</p>