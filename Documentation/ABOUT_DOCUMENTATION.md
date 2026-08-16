# 📚 HRConvert2: The Dual-Flavor Documentation Paradigm

Welcome to the official repository manual layout guide. This document outlines how we present authoritative software specs alongside high-quality, readable instructional guides to give administrators and developers the best possible reference experience.

---

## 🛠️ Core Infrastructure Specifications

### Legal & Distribution
* **Intellectual Property:** HRConvert2 is Copyright © 2026 by Justin Grimes ([GitHub](https://www.github.com/zelon88)).
* **Licensing Model:** Protected under the strict terms of the **GNU GPLv3 Open-Source License**. Refer to the [Official License Portal](https://www.gnu.org/licenses/gpl-3.0.html) for distribution requirements.

### System Intent
This application provides a highly accessible, unauthenticated web interface enabling file conversions directly on a server infrastructure using any modern web browser.

| Metric | Minimum Operational Requirements |
| :--- | :--- |
| **Hardware** | Raspberry Pi Model B+ (or any standard x86 / x64 architecture computing platform) |
| **Environment** | Debian Linux + Apache Web Server v2.4 + PHP v8.0 or greater |

> ⚙️ **Core Dependencies:** FFMPEG, Dia, LibreOffice, Mkisofs, 7zip, Unoconv, libgxps-utils, Tesseract, Unzip, OpenSCAD, Rar, Inkscape, Unrar, ClamAV, MeshLab, PopplerUtils, PDFTOTEXT, ImageMagick, Assimp, Bwrap, Dia, and xvfb-run.

---

## 🗺️ The Central Documentation Library

All system reference items are securely housed inside the `/Documentation` directory at the absolute root of the repository. Every significant subject inside this folder comes packaged in exactly **two distinct flavors**:

```text
📁 /Documentation
   ├── 📄 About Documentation.txt   <-- The Authoritative Source of Truth
   └── 🎨 About Documentation.md   <-- The Vibrant User Manual (You are here!)
```

This structural separation guarantees an absolute boundary between raw, unyielding engineering blueprints and polished, accessible user instructions.

---

## 📊 Comparing the Two Flavors

We believe documentation should never feel like a chore. By pairing these two distinct formats, we ensure both terminal-dwelling administrators and hobbyist developers get exactly what they need:

| Feature Matrix | 📄 The Plain Text Sibling (`.txt`) | 🎨 The Markdown Sibling (`.md`) |
| :--- | :--- | :--- |
| **Primary Intent** | Absolute source of truth, rigid specs. | Digestible, engaging, and friendly user manual. |
| **Visual Style** | Monospaced, zero fluff, fixed lines. | Colorful, visually striking, interactive elements. |
| **Target Audience** | System parsers, automated tools, pure engineers. | Human administrators, hobbyists, open-source fans. |
| **Formatting Assets** | Standard ASCII text, strict layout blocks. | Tables, code blocks, bold anchors, info alerts. |
| **Compatibility** | Universally readable on **any** device or terminal. | Rendered flawlessly via GitHub, Wikis, or Notion. |

---

## 🎨 Flavor Deep Dives

### 📐 1. The Engineering Specification (`.txt`)
The `.txt` version serves as the unyielding code blueprint. It is designed to be highly concise and perfectly readable inside raw terminal windows or minimal text-viewing software without any graphical overhead. 
* **Strict Fact Density:** Contains nothing but explicit technical specifications, noted exceptions, and execution syntax.
* **Prose Restriction:** Zero emotional filler, no conversational tone, and absolutely no narrative fluff.

### 🚀 2. The Premium User Manual (`.md`)
The `.md` file is the user manual you actually *want* to read. It takes the exact underlying engineering rules from the text file and translates them into a layout that feels satisfying, high-end, and premium—giving you the feeling of a well-crafted physical guide that came wrapped with a passion project.
* **Visual Anchors:** Utilizes bold headers, functional iconography, and clear spacing to make searching for commands instant.
* **Highlighted Alerts:** Embeds striking blockquotes and attention flags for high-risk system commands (like memory sanitization routines or forced data sweeps).

***

### 💡 Pro-Tip for Contributors
When extending the HRConvert2 documentation library, always write your raw technical data into the `.txt` template first to lock down your authoritative facts. Once the engineering specs are concrete, let your creativity flow into the matching `.md` layout to bring it to life!
