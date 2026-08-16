# 📐 HRConvert2: Core Coding Conventions & Architecture Manual

Welcome to the official developer style guide! This manual details the strict quality baselines, architectural constraints, and safety safeguards enforced across the HRConvert2 codebase. 

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

Whether you are writing core logic or polishing a layout block, these conventions exist to ensure unyielding security, prevent memory leaks, and keep our codebase a pleasure to read and maintain.

---

## 🚀 The Build & Distribution Lifecycle

Development takes place entirely in the open. SCM actions are tracked across distinct channels depending on your environment requirements:

```text
📦 Build Lifecycle Matrix
   ├── 🌙 Nightly Builds ──> Raw repository snapshots / Immediate features / Regular updates
   ├── 🚀 Major Releases ──> High-stability packages / Milestone patches / Production benchmarks
   └── 🐋 Docker Images  ──> Elongated update cycles / Streamlined, turnkey deployment footprints
```

> 🛑 **The Ground Rule:** Uploading broken, incomplete, or untested code to the public tree is strictly prohibited. Code living in the repository must remain fully executable across all tested cases.

---

## 🛡️ General Coding Conventions

To safeguard the codebase against logic pollution and resource leaks, all contributions must strictly conform to these foundational rules:

### 🧩 Separation of Concerns
* **Rule 1: Isolated UI Layers** — Core application logic and presentation interfaces must remain completely distinct. UI template pages are strictly forbidden from executing core system logic; local UI scripts may only process parameters required to generate local visual layout elements.
* **Rule 2: Abstracted Component Assembly** — The core module must never contain hardcoded UI elements. The core engine is programmatically restricted to building or assembling interface layouts dynamically out of separate, highly modular layout blocks.

### 🧠 Variables & Memory Lifecycle Management
* **Rule 3: Global Scope Identifiers** — Variables starting with an **uppercase first letter** carry global scope (`$GlobalVariable`). Only uppercase-initialized variables are authorized to transcend multiple function boundaries, serve as global constants, or transport multi-function returns.
* **Rule 4: Micro-Scope Identifiers** — Variables starting with a **lowercase first letter** are restricted to a severely limited scope (`$localVariable`). Lowercase variables must never be accessed, extended, or referenced outside the isolated function block in which they are initialized.
* **Rule 5: User-Land Memory Sanitization** — All lowercase variables must be initialized exclusively within the function execution block where they are utilized, and securely destroyed via explicit `NULL` and `unset()` assignments immediately after their utility terminates. 

### 🎛️ Function Design & Robustness
* **Rule 6: Sequential Structural Order** — Functions go first; procedural logic goes after. All function definitions must be written sequentially at the absolute top of a codebase file. Nesting function declarations within logical code streams is prohibited.
* **Rule 7: Complex Return Integrity** — If a function contains intricate execution loops or stretches past approximately 35 lines of code, it **MUST** return a dedicated validation sanity check alongside its standard operational payload.
* **Rule 8: Sanity Verification Requirement** — The calling runtime context handling a function with an embedded sanity check must programmatically verify that the check has passed before proceeding with data execution.
* **Rule 9: Failure Telemetry** — If an internal sanity check failure alters expected application routing or compromises an operation, the event must immediately write to the system logs.

### 📝 Code Quality & Repository Hygiene
* **Rule 10: Changelog Auditing** — Any and all modifications made to application code files must be manually cataloged within the `CHANGELOG.txt` reference library.
* **Rule 11: Grammar & Prose** — All inline code comments must be written as complete sentences using proper capitalization, whole grammar structures, and correct punctuation.
* **Rule 12: Interface Documentation** — Functions must be prefaced by a comprehensive structural comment detailing their precise architectural utility.
* **Rule 13: Human-Coherent Typography** — Function inputs and return parameter identifiers must be inherently meaningful to an outside reviewer. Generic, single-letter, or non-descriptive names (e.g., `$f`, `$a`) are entirely banned within input arrays or functional returns.
* **Rule 14: Atomic Upload Batches** — System file updates must be grouped into comprehensive batches, verified completely through extensive local testing routines, and uploaded only when execution is flawless. Unfinished, raw, or untested patches must never be committed to the tree.
* **Rule 15: Zero-Error Execution Tolerances** — No browser console warnings, JavaScript exceptions, or HTML markup syntax errors are permitted to persist in any nightly build or major milestone release. Software must execute across all target platforms without throwing unhandled HTML, JS, or PHP warnings. Operation-driven errors generated normally during expected execution are acceptable.
* **Rule 16: Telemetry Documentation** — Any explicit warning or tracking error message generated by the application runtime must be comprehensively mapped inside the `ERROR_DESCRIPTIONS.txt` master index.
* **Rule 17: Absolute Zero-Telemetry Policy** — No browser cookies. No database dependencies. No outbound third-party API connections. No tracking analytics. **Zero exceptions.**
* **Rule 18: High-Priority Markers** — If an engineering block requires comments written in all-capital lettering, the underlying logic is officially classified as critically important to system stability.

---

## 🔒 Security Boundaries & Data Control

### 🌐 Escaping & Encoding Controls
Data escaping belongs exclusively at the absolute point of logical use—never within the application core. The core guarantees that raw content states are inherently secure, while individual output wrappers handle context-specific encoding. 

The core cannot predict whether a value will resolve into a URL, raw HTML text, a shell argument, or a JSON payload, and must not guess.

| Output Target Context | Native Helper Function | Target Application Vector |
| :--- | :--- | :--- |
| **URL Path Segment (PHP)** | `rawurlencode()` | Encoding raw filenames injection-bound for an HTTP link |
| **URL Path Segment (JS)** | `encodeURIComponent()` | Processing transient filenames constructed at runtime in the client |
| **Text Markup Injection** | `htmlspecialchars()` | Any data string actively echoed into HTML markup segments |
| **Subprocess Invocations** | `escapeshellarg()` | Any variable payload reaching a background terminal command |

> 🚫 **Crucial Constraint:** These mechanisms are fundamentally non-interchangeable. For example, `rawurlencode()` mutates folder slash marks (`/`) into `%2F` and will instantly break path resolution if applied to complete system file tracks. Similarly, `htmlspecialchars()` offers zero URL protection, and `escapeshellarg()` provides no security inside an HTML context.

#### ⚠️ Double-Encoding Protections
Never encode the same value twice. A filename echoed into a JS string literal is encoded by the JS helper at runtime and **must NOT** also be `rawurlencode()`d in PHP. Double encoding turns a space into `%2520`.

The core function `sanitizeString()` deliberately permits `?`, `+`, and `=` because all three are legitimate in a filename. It is not a URL sanitizer and must not become one.

### 🛡️ Input Sanitization Boundaries
* **Remote Code Execution (RCE) Shielding:** The `sanitize()` and `sanitizeString()` functions establish a formal RCE boundary protecting all user-controlled data input streams. Data surviving this filter cannot inject into a shell, into HTML, or into a path.
* **The OpenSCAD Scanner:** This component behaves purely as a user convenience layer for tracking `#include` targets and notifying users of dead links; it does not function as an active defensive barrier.
* **File Reads:** The `file_get_contents()` function is only as dangerous as the sink it feeds. The file read operation itself cannot execute code as an RCE vector; threat vectors are restricted to the target path limits and what happens to the resulting bytes later.

---

## 📉 Fallback, Failure, & Logging Mechanics

### 🎛️ The Redundancy Pattern
Any localized subsystem capable of utilizing a structural fallback **MUST** drop down to that secondary safe state before throwing a system-wide error. 

```text
🔄 Fallback Protocol Sequence
   └── 1. Execute Action ──(Fails)──> 2. Log Warning ──> 3. Invoke Default Path ──(Fails)──> 4. Fatal Error
```

A numbered system error must trigger only when the default parameter block has completely failed, at which point processing becomes unrecoverable. 

An administrator who cannot render the primary page interface cannot read or report the problem; fallback configurations keeping the user interface active while loudly writing to system logs are structurally superior to displaying a blank screen or a raw error code.

#### 🛠️ Hardcoded Backups & Validation
* **Uniform Trust Paths:** Never validate a variable on a single code path while trusting it implicitly on another. If `$DefaultLanguage` is thoroughly evaluated before execution inside one code block, it must be evaluated across every operational block.
* **Configuration Isolation:** Always initialize a localized fallback parameter that cannot be overwritten by administrative system configurations. Declaring a lowercase `$defaultGui` variable directly alongside the global configuration `$DefaultGui` provides the running function with an immutable fallback boundary if an administrator writes an invalid configuration value.
* **Asset Loading Sequence:** Components replaced by pulling a secondary configuration file may be safely evaluated *after* file loading completes. Conversely, any routine that actively outputs UI code to the browser must be validated *before* file compilation. For instance, a language pack consists entirely of variable mappings and can be dynamically swapped mid-execution; a UI template outputs immediate HTML and cannot be undone once loaded.

### 🪵 Telemetry & Logging Tiers
The logging infrastructure categorizes runtime data into three strict operational tiers:

* **`logEntry()` [Op-Act]:** Records ordinary system activity and background metrics. This telemetry is automatically suppressed when the configuration variable `$Verbose` is toggled to `FALSE`.
* **`warningEntry()` [WARNING!!!]:** Written to the system logs in all runtime configurations. Carries no specific failure code number and signifies operations that administrators should actively review. Warnings never halt application execution.
* **`errorEntry()` [ERROR!!!]:** Written to the system logs in all runtime configurations. Instantly halts processing and carries a fully documented, numbered failure signature mapped in `ERROR_DESCRIPTIONS.txt`.

> 🛡️ **Defensive Logging Rule:** Any malicious event initiated by an attacker that HRConvert2 handles correctly must be recorded as a system **warning**, not an application error. Blocked SSRF manipulation vectors, rejected file path requests, and dropped stream captures are categorized as warnings. This ensures that an operator running with verbose logs disabled can instantly distinguish a blocked external attack vector from an ordinary internal conversion failure.

---

## 🔢 Precise Version Validation

System version components must be evaluated against strict matching logic tables to prevent rendering errors, configuration corruption, or binary tool mismatches:

* **`config.php` ──> MINIMUM Match:** A newer file structure carrying every required deployment parameter is acceptable.
* **UI Themes & Layout Folders ──> EXACT Match:** Newer or older template structures can break visual layouts in either direction.
* **Language Packs ──> EXACT Match:** Prevents missing localization strings or unmatched UI keys.
* **Binary Dependencies ──> MINIMUM Match:** Evaluated exclusively via numerical calculation, never as basic text strings.

#### 🧮 Mathematical Evaluation Rules
Always process version validation parameters using strict numerical operators. Simple string evaluations fail structurally because a string check ranks engine version `24.2` lower than version `7.6`, and mistakenly ranks version `3.10` lower than `3.9`. 

The core engine must programmatically explode the version string on the dot separator, cast each split element into a true integer, and evaluate major updates before minor updates. Additionally, you must completely strip any leading alphabetical characters (such as `v`) prior to calculation; casting a string like `'v3'` straight to an integer drops the numerical value to `0`, destructively reducing a precise three-part version comparison down to an invalid two-part comparison. Any dependency package reporting an unparseable or completely missing version string is **refused immediately** to prevent unvalidated background executions.

> 📊 **Dual Baselines:** Two minimums may exist for a single dependency when one represents a feature floor and the other represents a security floor (e.g., FFMPEG). Name them separately in `config.php` and explicitly state which is which.

---

## 📦 Function Architecture & Repetition

* **Single Exit Rule:** All functions must implement a single-exit layout, utilizing exactly **one** return statement positioned at the absolute end of the execution block.
* **Pre-Branch Initialization:** Initialize every variable the function uses before the first conditional branch, including ones only assigned inside a conditional. An undefined variable reaching a comparison is a warning in PHP 8, and a warning printed into an AJAX response corrupts the payload layout.
* **Parameterization over Globals:** Pass a configurable value as a **parameter** rather than reading it as a global when more than one caller needs a different value. For example, `verifyFFMPEGVersion()` takes its minimum as an argument because streams and audio require distinct baselines.
* **Declaration Safeguards:** Do not declare a variable global *and* accept it as a parameter simultaneously. The global declaration wins and the parameter is silently discarded.
* **The Duplication Limit:** Any pattern or logical layout repeated more than twice across the application belongs inside an independent function or a layout template. The cost is not the typing; it is that a bug fix gets applied $N-1$ times. Every bug found in a repeated block in this codebase was present in some copies and absent in others. When a repeated block cannot be collapsed, developers must mechanically verify call site counts before and after editing to prevent silent misses.

---

## 🌍 Language Packs & Localization

* **Scope Isolation:** A language pack is required from inside `buildGUI()`, wrapping all translation strings entirely within local function execution scopes. Nothing outside that call can read them. This is deliberate and is what stops a stray string leaking into an unrelated part of the core.
* **Localized Logic:** Simple logical parameters are permitted inside language packs to handle structural localization elements such as grammatical plural selection mechanics. Languages that do not inflect for number leave the plural helpers empty and write the sentence so it reads correctly for any count.
* **Endonyms:** Language names inside user selection menus must be rendered as untranslated **Endonyms** (e.g., *Deutsch*, *Español*, *Français*). A user who has landed on a language they cannot read must still recognize their own.
* **BOM Prevention:** Language packs must be saved as standard UTF-8 files **without a Byte Order Mark (BOM)**. A hidden BOM character sequence emits three leading data bytes prematurely, breaking HTTP header delivery.
* **Layout Cleanliness:** Layout modules must never duplicate structural HTML tags that are already actively supplied by the central `footer.php` system.

***
*Document generated for architectural compliance across all repository code contributions.*
