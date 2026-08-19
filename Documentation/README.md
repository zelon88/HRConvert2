# 📚 HRConvert2: README - User Guide

Welcome to the official `Documentation/` folder! This document outlines the contents of the `Documentation/` folder and how to make use of them.

---

## 🛠️ Core Infrastructure Specifications

### Legal & Distribution
* **Intellectual Property:** HRConvert2 is Copyright © 2026 by Justin Grimes ([GitHub](https://github.com)).
* **Licensing Model:** Protected under the strict terms of the **GNU GPLv3 Open-Source License**. Refer to the [Official License Portal](https://gnu.org) for distribution requirements.

### System Intent
This application provides a highly accessible, unauthenticated web interface enabling file conversions directly on a server infrastructure using any modern web browser.

| Metric | Minimum Operational Requirements |
| :--- | :--- |
| **Hardware** | Raspberry Pi Model B+ (or any standard x86 / x64 architecture computing platform) |
| **Environment** | Debian Linux + Apache Web Server v2.4 + PHP v8.0 or greater |

> ⚙️ **Core Dependencies:** FFMPEG, Dia, LibreOffice, Mkisofs, 7zip, Unoconv, libgxps-utils, Tesseract, Unzip, OpenSCAD, Rar, Inkscape, Unrar, ClamAV, MeshLab, PopplerUtils, PDFTOTEXT, ImageMagick, Assimp, Bwrap, Dia, and xvfb-run.

---

## 🗺️ The Central Documentation Library

Every significant subject inside this folder comes packaged in **two distinct flavors**:

* `.txt` files act as rigid, uniform, and hyper-concise engineering specification stripped of unnecessary fluff or prose.
* `.md` files resemble a premium, colorful user manual that adds clear aesthetic value for hobbyists who enjoy interacting with it.

```text
📁 /Documentation
   ├── 📄 About Documentation.txt   <-- The Authoritative Source of Truth
   └── 🎨 About Documentation.md   <-- The Vibrant User Manual
```

This structural separation guarantees an absolute boundary between raw, unyielding engineering blueprints and polished, accessible user instructions.

---

## 📑 Table of Contents

Navigate the complete documentation library using the directory structure below. 

### ⚙️ Command Line & System Architecture
* [ABOUT_COMMAND_LINE_ARGUMENTS.md](ABOUT_COMMAND_LINE_ARGUMENTS.md) / [.txt](ABOUT_COMMAND_LINE_ARGUMENTS.txt) — CLI execution parameters.
* [ABOUT_DEFENSIVE_MEMORY_MANAGEMENT.md](ABOUT_DEFENSIVE_MEMORY_MANAGEMENT.md) — Memory leak prevention strategies.
* [ABOUT_DEPENDENCIES.txt](ABOUT_DEPENDENCIES.txt) — External library notes.
* [DOCKER_BUILD_INSTRUCTIONS.txt](DOCKER_BUILD_INSTRUCTIONS.txt) — Container deployment guide.

### 💾 Asset Generation
* [ABOUT_CREATING_BOOTABLE_ISO_IMAGES.md](ABOUT_CREATING_BOOTABLE_ISO_IMAGES.md) / [.txt](ABOUT_CREATING_BOOTABLE_ISO_IMAGES.txt) — ISO creation protocols.
* [BUILDING_PRE-BAKED_UEFI_ASSET_IMAGES.md](BUILDING_PRE-BAKED_UEFI_ASSET_IMAGES.md) / [.txt](BUILDING_PRE-BAKED_UEFI_ASSET_IMAGES.txt) — UEFI image guidelines.

### 🛠️ Developer & Pipeline Resources
* [CODING_CONVENTIONS.md](CODING_CONVENTIONS.md) / [.txt](CODING_CONVENTIONS.txt) — Code style guidelines.
* [CREATING_CONVERSION_PIPELINES.txt](CREATING_CONVERSION_PIPELINES.txt) — Processing engine manual.
* [CREATING_CONVERSION_PIPELINES_EXAMPLE.txt](CREATING_CONVERSION_PIPELINES_EXAMPLE.txt) — Reference script.
* [CREATING_GUIS.txt](CREATING_GUIS.txt) — Frontend design notes.
* [CREATING_LANGUAGE_PACKS.txt](CREATING_LANGUAGE_PACKS.txt) — Localization frameworks.

### 🌐 API, Meta, & Diagnostics
* [ABOUT_DOCUMENTATION.md](ABOUT_DOCUMENTATION.md) / [.txt](ABOUT_DOCUMENTATION.txt) — Handbook standards.
* [ABOUT_REST_API.md](ABOUT_REST_API.md) / [.txt](ABOUT_REST_API.txt) — Endpoint routing schemes.
* [CHANGELOG.txt](CHANGELOG.txt) — Version revision logs.
* [ERROR_DESCRIPTIONS.txt](ERROR_DESCRIPTIONS.txt) — Debugging status codes.

### 📋 Project Information
* [index.html](index.html) — Document root protection mechanism. Part of a valid installation (this is a working codebase afterall).
* [INSTALLATION_INSTRUCTIONS.txt](INSTALLATION_INSTRUCTIONS.txt) — Server provisioning steps.
* [LICENSE](LICENSE) — GNU GPLv3.
* [README.md](README.md) — Main user guide (This document).
* [SUPPORTING_THIS_PROJECT.md](SUPPORTING_THIS_PROJECT.md) / [.txt](SUPPORTING_THIS_PROJECT.txt) — Contribution guidelines.
* [ICON_CREDITS.txt](ICON_CREDITS.txt) — Asset attribution registry.

---