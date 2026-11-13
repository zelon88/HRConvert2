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

> ⚙️ **Core Dependencies:** The authoritative list of dependencies is `Resources/Contract/depends.php` and nothing else. Run `php convertCore.php -v` to see what this installation has.

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

### 🚀 Programmatic Execution Pipeline:
1. **Retrieve Session Tokens**: Submit a blank GET request to `convertCore.php` to fetch the active values of `Token1` and `Token2`.
2. **Upload Target Payload**: Submit a file upload along with `Token1` and `Token2` to `convertCore.php` via POST input.
3. **Execute Job Task**: Submit the uploaded filename, a new filename, a file extension, `Token1`, `Token2`, and a valid set of conversion options to `convertCore.php` via POST input.
4. **Read The Result**: The core returns the name of the file it created, or an error that can be checked against `ERROR_DESCRIPTIONS.txt`.
5. **Request A Download**: Submit a `download` request for the filename the core returned.
6. **Fetch The File**: The core returns a relative URL that stays valid until the deletion threshold set in `config.php`, after which the file is removed.

---

## 📥 3. User Supplied GET Requests

GET inputs modify structural application flags. While some can be specified via POST requests, **GET inputs will always take precedence**.

| Parameter | Application Context | Supported Values & Functional Behavior |
| :--- | :--- | :--- |
| `noGui` | GUI Operation Modifying Flag | Displays minimal GUI elements. Removes header images and introduction text. Triggered by setting this input to **any value**. |
| `showFiles` | GUI State Override | Tells the system to display uploaded files (`convertGui2.php`). If not specified, defaults to the upload page (`convertGui1.php`). **Valid tokens must be supplied via POST along with this request.** |
| `fileListOnly` | File List Refresh | Returns the list of uploaded files rather than a whole page. The interface uses it after an upload. **Valid tokens must be supplied via POST along with this request.** |
| `gui` | UI Interface Selector | Selects the active UI theme. Theme must be installed to the UI folder and defined inside `config.php` (can be enabled/disabled via config). |
| `language` | Localization Engine | Sets the system language. Input must match an installed code inside the `--Supported Languages--` section of `config.php`. Uses **ISO 639-1** two-letter codes (Except Aramaic which utilizes **ISO 639-3** codes `'aii'` & `'arc'`). |
| `color` | Visual Palette Selector | Selects active UI color scheme. Input must map to a supported color scheme configuration defined inside `config.php`. |
| `sort` | File List Order | Two digits. The first is what the list is sorted by, `1` name, `2` size, `3` the time a file changed & `4` its type. The second is the direction, `1` ascending & `2` descending. `11` is the default. Ties are listed by name. Anything else lists by name & is logged as a warning. Every link an interface draws carries it. **GET only.** |

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
* `file` *(Multipart file field, or `file[]` for several)*
  * An upload happens whenever a file is posted with valid tokens. No other input is needed to trigger it.
  * An upload fails with error 18 when the listener refuses it, because the disk holding the session has `--Storage Reserve--` megabytes free or less, or would have once it was written.
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
  * A name already in the session is given the next free one, such as `name_1`, & the reply names the file that was written.

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
  * A name already in the session is given the next free one, such as `name_1`, & the reply names the file that was written.
* `archextension` *(String)*
  * Defines packaging type compression envelopes.
  * *Value*: The explicit archive extension to use for the output file.
* `filesToArchive` *(String or JSON Array)*
  * Selects specific items to pack inside the container matrix.
  * *Value*: A target name or structured string list of filenames to use for input files.

### 📂 F2. Extract Operations
* `filesToExtract` *(String or JSON Array)*, from v4.1.7.
  * The archives in the session to extract.
  * *Value*: A name or list of names of archives in the session.
  * Every file each archive holds is placed in the session, under its own name or the next free one, & the reply names every file placed, one per line.
  * An archive refused for a password, its size, its count of files or its format places nothing & is named in a warning. A request that placed nothing ends with error 32.

### 🔍 G. Optical Character Recognition (OCR) Operations
* `pdfworkSelected` *(String or JSON Array)*
  * Targets documents for data scanning and textual processing layers.
  * *Value*: A target name or string list of names to use for input files.
* `userpdfconvertfilename` *(String)*
  * Names the extracted data file product.
  * *Value*: A designated custom name string to use for the output file.
  * A name already in the session is given the next free one, such as `name_1`, & the reply names the file that was written.
* `pdfextension` *(String)*
  * Controls text envelope generation formats.
  * *Value*: An explicit extension string to use for the final output document.
* `method` *(Integer: 0 or 1)*
  * Determines the underlying algorithmic processing method to use for performing character extraction.
  * *Value = 0*: Employs a fast, basic, simple approach to OCR.
  * *Value = 1*: Employs an advanced, deeper parsing approach to OCR.

### 🛡️ H. Virus Scanning Operations
* `scantype` *(String: 'clamav' | 'scancore' | 'all')*
  * Targets a specific isolated file for anti-malware verification.
  * *Value = 'clamav'*: Executes a malware scan utilizing the **ClamAV Virus Scanner**.
  * *Value = 'scancore'*: Executes a malware scan utilizing the **ScanCore Virus Scanner**.
  * *Value = 'all'*: Runs **every installed scanner**.
* `filesToScan` *(String or JSON Array)*
  * Sets targets for the anti-malware scanning engines, and is what triggers a scan.
  * *Value*: A target name or string list of names to use for input files.

> ℹ️ The interface's scan buttons post `scantype` and `filesToScan` and nothing else. To scan every file, name every file. The button names `clamscanbutton`, `scancorebutton` and `scanallbutton` are not read by the core.

### 🌐 I. Remote File Operations
* `userURLDownload` *(String)*
  * Fetches a file from a remote address into the session. Requires `--Allow User URL Download--` in `config.php`.
  * *Value*: The address. It is read as an address rather than sanitized as a filename, and every check a stream address receives is applied before anything is fetched.
* `streamBaseURL` *(String)*
  * Used during Stream Conversions to resolve relative playlist entries.
  * *Value*: The base address. It is rebuilt from its parsed parts at the point of use.

### 🔎 J. File Identification Operations
* `filesToIdentify` *(String or JSON Array)*
  * Identifies files, and is what triggers it. Identifying changes no file. Each one is hashed, and the tools installed say what it holds.
  * *Value*: A target name or string list of names to use for input files. A name the session does not hold is skipped and logged.
  * Requires `--User File Identification--` in `config.php`. When it is off, the reply is the sentence *Identifying files is disabled on this server.*
* `identifyreport` *(String: '0' | '1')*
  * *Value = '1'*: Also writes a text report of every file identified into the session.
  * Anything else writes no report, and the reply alone describes the files.
* `identifyfilename` *(String)*
  * The report's name, without its extension. A report is always `txt`. `User_Identify_Report` is used when it is empty.
  * A name already in the session is given the next free one, such as `name_1`, & the reply names the file that was written.

> ℹ️ The reply is one line of JSON after the doctype line that opens every reply: `{"Identify":{"Complete":true,"Errors":false,"Report":"","Records":[...]}}`. `Complete` is false when no file was identified, or a report was asked for and not written, and error 31 follows the JSON. `Errors` is true when anything was skipped or failed. `Report` names the report written, or is empty. Each record holds `Name`, `Size`, `SizeText`, `Modified`, `Description`, `MimeType`, `Charset`, `ExtensionMatches` (`yes`, `no` or `unknown`), `ExpectedExtensions`, `SHA256`, `SHA1`, `MD5`, `Details` and `Notes`, and where they apply `Text`, `Image`, `Media`, `Pdf` and `Archive`. `ABOUT_REST_API.txt` describes each one. Every character that means something in HTML is escaped, and so is the exclamation mark, so nothing a file carries can spell the error tag.

### 🧭 K. Workflow Operations
Every reply is one line of JSON under the key `Workflow`, after the doctype line that opens every reply. A server with `--Enable Workflows--` off, or without the Workflow Core, answers every one of them with `{"Workflow":{"Available":false}}` and says why only in its log. `ABOUT_WORKFLOWS.txt` describes the format, the operations and how a workflow runs.
* `workflowLibrary` *(1)*
  * Lists the workflows this session may run, each with `Id`, `Name`, `Description`, `Steps`, `AcceptsAll` and `Accepts`, the extensions its first step reads, and `NeedsSelection`, which is `false` when a Select step finds files of its own.
* `workflowRun` *(String)* with `workflowFiles[]` *(one for each file)*
  * Starts the workflow with that identifier on those files, in that order, and returns as soon as its worker starts: `{"Workflow":{"Started":true,"JobId":"...","Reason":""}}`.
  * A run that does not start says why in `Reason`, asked in this order: `disabled`, `unknown`, `busy`, `files`, `nothing`, `server` or `start`.
* `workflowStatus` *(1)*
  * Reports this session's workflow. `State` is `none`, `starting`, `running`, `finished`, `stopped`, `failed` or `interrupted`, with the step, the file, every file it made and the reason it ended.
  * While it is `starting` or `running`, `Document` holds the workflow in canonical form, so a page loaded again can draw what is running. It is empty once the workflow has ended.
  * **An ended workflow is reported once.** The request that reads it removes it, so the session may start another.
* `workflowStop` *(1)*
  * Asks the running workflow to stop before its next operation. `Stop` is `stopping`, `cleared` or `none`. Read `workflowStatus` until it says `stopped`.
* `workflowDocument` *(String)* with `workflowFiles[]` *(one for each file)*
  * Starts a workflow a page built, sent as its JSON text, and replies as `workflowRun` does. It is checked exactly as a library's workflow is and held to every limit. `Reason` may also be `builder`, when the builder is off, or `invalid`, with `Problems` naming everything to fix in the server's words, which are English.
* `workflowOperations` *(1)*
  * Describes what a page's builder may build from: `Builder`, `ImportExport`, `MaxSteps`, `MaxFiles`, the `Operations` this server has on, each with its `Modes` and `Options`, and the `Kinds` a filter may name. `Operations` and `Kinds` are empty unless the builder is on.
* `workflowCheck` *(String)*
  * Checks a workflow sent as JSON text, and runs and keeps nothing: `Allowed`, `Valid`, `Usable`, `Problems`, and the workflow in canonical form as `Document`, with the `FileName` an export saves it under.
* `workflowExport` *(String)*
  * Gives a workflow from the library as a `Document` in canonical form, with its `FileName`. Only a workflow this session is offered is `Found`.
* `workflowCheck` and `workflowExport` are `Allowed` when the builder or import and export is on. `ABOUT_WORKFLOWS.txt` describes the canonical form.

> ℹ️ Every character that means something in HTML is escaped in every reply, and so is the exclamation mark.
