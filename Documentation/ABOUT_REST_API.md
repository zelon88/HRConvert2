# 🛠️ HRConvert2 REST API Reference Manual

Welcome to the official developer reference manual for the **HRConvert2 REST API**. This document details how to programmatically interact with the core conversion engine without relying on the default browser graphical interfaces.

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

## 📂 1. File Sharing Operations

File sharing can be implemented entirely client-side by submitting a `download` POST request. This returns a structured URL string that can be copied directly to the user's clipboard for external distribution.

---

## ⚙️ 2. The Core REST API

The engine supports inputs through explicit **POST** and **GET** requests.

### 🛑 Integration Requirements:
* **Target Endpoint**: All programmatic requests must be submitted directly to `convertCore.php`.
* **Payload Encoding**: All POST requests must be strictly encoded as `"multipart/form-data"`.
* **Sanitization Engine**: All API inputs are automatically sanitized by the core engine for special characters to prevent malicious or unintentional code injection. **As a result of sanitization, the target filename may change after upload.**

### 🔄 GUI Fallback Routing Matrix:
* If `Token1` and `Token2` are **NOT** submitted via POST input $\rightarrow$ `convertGui1.php` is displayed prompting for file uploads.
* If `Token1` and `Token2` are **BOTH** submitted via POST input $\rightarrow$ `convertGui2.php` is displayed providing conversion options.
* *Note*: The included browser UIs make extensive use of JQuery + Ajax to submit requests & process responses. GUI files cannot handle direct API requests and will throw an error if they receive raw API requests.

### 🚀 Programmatic 3-Step Execution Pipeline:
1. **Retrieve Session Tokens**: Submit a blank GET request to `convertCore.php` to fetch the active values of `Token1` and `Token2`.
2. **Upload Target Payload**: Submit a file upload along with `Token1` and `Token2` to `convertCore.php` via POST input.
3. **Execute Job Task**: Submit the uploaded filename, a new filename, a file extension, `Token1`, `Token2`, and a valid set of conversion options to `convertCore.php` via POST input.

---

## 📥 3. User Supplied GET Requests

GET inputs modify structural application flags. While some can be specified via POST requests, **GET inputs will always take precedence**.

| Parameter | Application Context | Supported Values & Functional Behavior |
| :--- | :--- | :--- |
| `noGui` | GUI Operation Modifying Flag | Displays minimal GUI elements. Removes header images and introduction text. Triggered by setting this input to **any value**. |
| `showFiles` | GUI State Override | Tells the system to display uploaded files (`convertGui2.php`). If not specified, defaults to the upload page (`convertGui1.php`). **Valid tokens must be supplied via POST along with this request.** |
| `gui` | UI Interface Selector | Selects the active UI theme. Theme must be installed to the UI folder and defined inside `config.php` (can be enabled/disabled via config). |
| `language` | Localization Engine | Sets the system language. Input must match an installed code inside the `--Supported Languages--` section of `config.php`. Uses **ISO 639-1** two-letter codes (Except Aramaic which utilizes **ISO 639-3** codes `'aii'` & `'arc'`). |
| `color` | Visual Palette Selector | Selects active UI color scheme. Input must map to a supported color scheme configuration defined inside `config.php`. |

---

## 📤 4. User Supplied POST Requests

POST inputs are organized into distinct functional operational blocks based on processing cycles.

### 🔑 A. General Session Authentication
* `Token1` *(String)*
  * Used whenever a user intends to communicate with HRConvert2.
  * Provided via a hidden form field.
  * Must be stored & resubmitted with every subsequent request that is part of the same session.
  * *Value*: Match the hidden form field from `convertGui1.php` possessing an `id` of `'Token1'`.
* `Token2` *(String)*
  * Used whenever a user intends to communicate with HRConvert2.
  * Provided via a hidden form field.
  * Must be stored & resubmitted with every subsequent request that is part of the same session.
  * *Value*: Match the hidden form field from `convertGui1.php` possessing an `id` of `'Token2'`.

### 🗄️ B. General File Operations
* `download` *(String or JSON Array)*
  * Used at any time to download user files or generate file URLs for sharing operations.
  * *Value*: A target name or string list of names to use for input files.
* `filesToDelete` *(String or JSON Array)*
  * Used at any time to purge user files immediately from temporary hosting storage.
  * *Value*: A target name or string list of names to use for input files.

### 🔄 C. File Conversion Operations (Standard)
* `convertSelected` *(String or JSON Array)*
  * Used during standard File Conversions (excluding OCR & bulk archiving workflows).
  * *Value*: A target name or string list of names to use for input files.
* `extension` *(String)*
  * Used during standard File Conversions (excluding OCR & bulk archiving workflows).
  * *Value*: The target extension to use for the output file.
* `userconvertfilename` *(String)*
  * Used during standard File Conversions (excluding OCR & bulk archiving workflows).
  * *Value*: The custom filename string to use for the output file.

### 🖼️ D. Image Conversions
* `height` *(Integer)*
  * Used during Image Conversion jobs to override vertical sizing dimensions.
  * *Value*: Image height in total number of pixels. **Set to 0 to preserve the original image height.**
* `width` *(Integer)*
  * Used during Image Conversion jobs to override horizontal sizing dimensions.
  * *Value*: Image width in total number of pixels. **Set to 0 to preserve the original image width.**
* `rotate` *(Integer)*
  * Used during Image Conversion jobs to manipulate file spatial orientation.
  * *Value*: Number of degrees to rotate the target image clockwise. **Set to 0 to bypass rotation.**

### 🎵 E. Audio Conversions
* `bitrate` *(Integer)*
  * Used during Audio Conversion processing pipelines to define audio quality bandwidth constraints.
  * *Value*: Target bitrate to use for the output file. **Set to 0 to enable automatic bitrate detection.**

### 📦 F. Archive Operations (Excluding Conversions)
* `archive` *(String)*
  * Trigger parameter used during Archive Operations.
  * *Value*: Set this input to **any value** to trigger package compilation workflows.
* `userfilename` *(String)*
  * Specifies output branding for target packages.
  * *Value*: The designated text name to apply to the generated output file.
* `archExtension` *(String)*
  * Defines packaging type compression envelopes.
  * *Value*: The explicit archive extension to use for the output file.
* `filesToArchive` *(String or JSON Array)*
  * Selects specific items to pack inside the container matrix.
  * *Value*: A target name or structured string list of filenames to use for input files.

### 🔍 G. Optical Character Recognition (OCR) Operations
* `pdfworkSelected` *(String or JSON Array)*
  * Targets documents for data scanning and textual processing layers.
  * *Value*: A target name or string list of names to use for input files.
* `userpdfconvertfilename` *(String)*
  * Names the extracted data file product.
  * *Value*: A designated custom name string to use for the output file.
* `pdfextension` *(String)*
  * Controls text envelope generation formats.
  * *Value*: An explicit extension string to use for the final output document.
* `method` *(Integer: 0 or 1)*
  * Determines the underlying algorithmic processing method to use for performing character extraction.
  * *Value = 0*: Employs a fast, basic, simple approach to OCR.
  * *Value = 1*: Employs a advanced, deeper parsing approach to OCR.

### 🛡️ H. Virus Scanning Operations
* `scantype` *(String: 'clamav' | 'scancore' | 'all')*
  * Targets a specific isolated file for anti-malware verification.
  * *Value = 'clamav'*: Executes a malware scan utilizing the **ClamAV Virus Scanner**.
  * *Value = 'scancore'*: Executes a malware scan utilizing the **ScanCore Virus Scanner**.
  * *Value = 'all'*: Parallelizes scanning operations across **all installed engines**.
* `filestoscan` *(String or JSON Array)*
  * Sets targets for the anti-malware scanning engines.
  * *Value*: A target name or string list of names to use for input files.
* `clamScanButton` *(String)*
  * Universal trigger targeting all files for processing under the ClamAV ecosystem.
  * *Value*: Set to **any value** to immediately initiate comprehensive ClamAV scanning.
* `scancorebutton` *(String)*
  * Universal trigger targeting all files for processing under the ScanCore ecosystem.
  * *Value*: Set to **any value** to immediately initiate comprehensive ScanCore scanning.
* `scanallbutton` *(String)*
  * Global override triggering an exhaustive scan of all stored files against every single active scanner engine.
  * *Value*: Set to **any value** to immediately initiate full-coverage virus scanning.
