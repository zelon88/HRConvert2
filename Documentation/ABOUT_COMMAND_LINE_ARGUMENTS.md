# 💻 HRConvert2: Command Line Interface (CLI) Guide

## About Command Line Arguments
HRConvert2 accepts input from the command line to provide administrative functionality and to support external scripting and automation.

* **No File Conversions:** File conversions are **NOT** supported from the command line. 
* **Admin Use Only:** CLI functionality exists solely for administrators maintaining, troubleshooting, or diagnosing a server.
* **Mutually Exclusive:** A command line invocation and a web request will always remain mutually exclusive. 
* **Disables Web Interface:** Any CLI argument disables the web interface and all conversion paths for that invocation. 
* **Strict Execution:** The application handles the requested operation and then stops immediately. Nothing falls through. 
* **Error Handling:** An unrecognized argument displays the help menu rather than serving a web page.
* **No User Sessions:** No session is created by a CLI invocation and no user data is touched (except by the `clean` argument).

---

## How To Use HRConvert2 From The Command Line
Begin each call with the following base command, substituting the path to your own installation:

```bash
sudo php /var/www/html/HRProprietary/HRConvert2/convertCore.php
```

### Argument Syntax Examples
Add your desired argument to the end of the base command. Arguments accepting a value can use either an equals sign (`=`) or a space. Both formats are equivalent.

* **Standard Flag:**
  ```bash
  sudo php /var/www/html/HRProprietary/HRConvert2/convertCore.php -v
  ```
* **Value Flag (with `=`):**
  ```bash
  sudo php /var/www/html/HRProprietary/HRConvert2/convertCore.php -u=v3.6.7
  ```
* **Value Flag (with space):**
  ```bash
  sudo php /var/www/html/HRProprietary/HRConvert2/convertCore.php -u v3.6.7
  ```

---

## CLI User Account Considerations

### Running as Root
HRConvert2 should be run as **root** when using the command line. 

* **Permission Safety:** Running as root does **NOT** create root-owned permission problems. 
* **Automatic Corrections:** When a directory is created under root, HRConvert2 immediately corrects the owner, group, and permissions to the values Apache requires.
* **Web Readiness:** The application maintains constant readiness for web operations; CLI invocations are never permitted to compromise this.

### Running as Non-Root
Running as a non-root user is safe but structurally limited.

* **Fail-Safe Filesystem:** Any operation requiring write access that the current user lacks will fail safely rather than partially completing. The filesystem is never left in a broken state.
* **Unrestricted Flags:** The version (`-v`) and help (`-h`) arguments work flawlessly for any user.
* **Secret File Protection:** The secret file is readable only by its owner. A non-root user (who is also not the web server user) cannot read it. This is a deliberate security feature, as this file derives every session identifier in the application.

### Manual Permission Management
If an administrator modifies an HRConvert2 installation using external tools, they must configure the files and folders within the HRConvert2 directory exactly as follows:

| Property | Required Value | Notes |
| :--- | :--- | :--- |
| **Owner** | `www-data` | Standard web server user |
| **Group** | `www-data` | Standard web server group |
| **Permissions** | `0755` | Folder/File standard permissions |
| **Secret File** | `0600` | **Exception:** Must stay owned by `www-data` |

> ⚠️ **Note:** The secret file is created automatically and should never need to be modified by hand.

#### Commands to Fix Compromised Permissions:
```bash
sudo chmod -R 0755 "/the/full/path/that/has/incorrect/permissions"
sudo chown -R www-data:www-data "/the/full/path/that/has/incorrect/permissions"
```
*Do not run the above commands against the data location without restoring the secret file permissions immediately afterward:*
```bash
sudo chmod 0600 "/DATA/HRConvert2/secret.php"
```

---

## Supported Command Line Arguments

| Short Flag | Long Flag | Description | Values / Variations |
| :--- | :--- | :--- | :--- |
| `-v` | `--version` | Displays comprehensive diagnostics and version data. | N/A |
| `-h` | `--help` | Displays the built-in help message. | N/A |
| `-c` | `--clean` | Sweeps expired sessions from data locations. | `-c` (Default)<br>`-c=<minutes>`<br>`-c=now` |
| `-u` | `--update` | Updates the application using a configured target. | `-u=latest`<br>`-u=edge`<br>`-u=v#.#.#` |

---

## Argument Deep Dives

### 🔍 About `-v` and `--version`
Performs a comprehensive diagnostic evaluation of the installation. This is far more than an echo of a version number; it reports every component version, runs dependency checks, and flags failures.

It analyzes and reports:
* Core version, configuration version, and the minimum version requirements.
* Every version-pinned dependency (verifying if minimum requirements are met).
* Functional status of the **bubblewrap sandbox** (required for OpenSCAD conversions).
* Every installed interface and whether its version matches core requirements.
* Language pack matches and discrepancies across interfaces.
* Active conversion types enabled within the configuration file.

> 🛠️ **Diagnostic Value:** Because these dependency checks are identical to the ones executed by active converters, this flag tells you if conversions will actually work. Any dependency reporting `FAILED` will cleanly refuse to run rather than failing mid-process in a confusing manner. This argument works for **any user**.

### ❓ About `-h` and `--help`
Displays the built-in help message. It lists every supported argument and references the relevant in-depth manuals located in the `Documentation` folder.

* **Blank Invocations:** Running the command with no arguments automatically triggers the help screen. Running web logic directly in a terminal would otherwise dump raw HTML into the shell and mistakenly create orphaned user sessions.
* **Invalid Flags:** Unrecognized arguments default to this help screen and write a warning to the log file.
* This argument works for **any user**.

### 🧼 About `-c` and `--clean` (Continued)
* **Now Value (`-c=now`):** Forces an immediate sweep of every single session, completely disregarding its age.
  > ⚠️ **CRITICAL WARNING:** `-c=now` deletes **EVERY** session, including those currently in use. A user mid-conversion will lose their files. Use this parameter with extreme caution.
* **Typo Protection:** Values that are neither a whole number nor the word `now` are rejected, falling back to the configured default. A warning is written to the logs and printed to the shell to prevent accidental sweeps.
* **Scope Isolation:** Nothing outside a designated session directory is ever touched. The log directory, LibreOffice profiles, and update backups live alongside session data but are strictly protected by name validation. Any location not matching its requested name is refused outright.
* **Privileges:** This argument explicitly requires write access to the data locations.

---

### 🔄 About `-u` and `--update`
Replaces the live application code with a release from your configured update source.

* **Security Boundary:** Updates are **NEVER** accessible via the web interface. Modifying application code requires file write access, which shell access natively implies. Protecting an HTTP update endpoint with a secret would insecurely reduce server authorization to a single guessable string.

#### System Requirements
* `$EnableAutoUpdates` must be set to `TRUE` in `config.php` (Default is `FALSE`).
* The server must have outbound network access to reach the update source.
* The executing user must have write access to the **PARENT** directory of the installation, not just the installation directory itself.
* The filesystem holding the installation must have free space roughly equal to **twice the installed size** of the app.

#### Target Versioning Options
If no value is supplied, the `--Automatic Update Target Version--` setting from `config.php` is used. You can override it via the CLI using these targets:

* `latest`: The newest tagged release available at the update source.
* `edge`: The current state of the `master` branch. **This is not a stable release.** It carries whatever version stamp the master branch holds.
* `v#.#.#`: Explicitly pins the update to that exact tagged release.

> 🚫 **Fallback Rules:** If a requested version does not exist at the source, the update **FAILS** immediately with no fallback. Partial versions (e.g., `v3.6`) are rejected because they target a series rather than an exact release. Updating to the currently installed version is rejected as unnecessary.

#### Configuration Merging Behavior
* **Settings Transfer:** The new release dictates the file structure, comments, and available settings. The live installation injects your existing values into any matching settings.
* **New & Removed Keys:** New settings adopt their default values. Deprecated settings are discarded. Renamed settings are treated as a removal and an addition (the core does not attempt to guess structural renames).
* **Array Settings:** **Arrays are NOT merged.** They reset entirely to the new defaults. Rewriting arrays requires structural reformatting, and partial merges of large lists (like a 200-element format list) break easily. Any array modified from the new default will be **NAMED** at the end of the update so you can reapply changes manually.

#### Atomic Replacement Process
1. The release is downloaded and unpacked into a temporary location.
2. Your existing configuration is safely merged into the temporary directory first.
3. The prepared release is moved to the same filesystem as the active installation.
4. The live data directory is moved into the new layout so no active sessions are lost.
5. The live installation and the new release are instantly swapped using **two atomic rename operations**.

> ⚡ **Web Request & Process Impact:** Because renames within a single filesystem are atomic, the directory is never half-populated. A web request arriving mid-swap will seamlessly hit either the old or new code. The tiny fraction of a second between the two renames might cause a request to drop, but it will succeed instantly on retry. Conversions already in progress are completely unaffected because running processes hold open file handles that remain valid across folder renames.

#### Failure & Rollback Mechanics
* **Pre-Exchange Failures:** Any error occurring before the directory swap leaves your live installation entirely untouched.
* **Post-Exchange Validation:** After the swap, the new installation is prompted via a separate process to report its own version. If the new core fails to parse, cannot load its config, or cannot access the CLI branch, it will fail to respond.
* **Automatic Rollback:** An installation failing this health check is instantly rolled back, restoring the previous version. The event is recorded in the system log.

#### Managing the Previous Version
* After a successful update, the old core is copied to the `--Backup Location--` configured in `config.php` (defaults to `Last-Installed-Version` inside the data directory).
* This backup is safely isolated **OUTSIDE the web root** to prevent an old, vulnerable core from answering public HTTP requests.
* It is protected from session sweeps and won't be cleared by the delete threshold.
* The backup directory holds exactly **one** previous version (overwritten on the next successful update) and explicitly excludes the live data directory.

#### 🐋 Notice for Docker Users
**Do not use the built-in update utility inside Docker containers.** Containers are designed to be disposable. Update your deployment by pulling a fresh image; an in-place container update will be completely discarded on the next image pull.

---

## 🤖 About Automation
Every command-line argument is safe to trigger via `cron` or external orchestration tools.

* **Scheduled Sweeps:** A `clean` sweep can be scheduled on servers experiencing low web traffic to keep storage clear.
* **Dependency Audits:** A `version` check (`-v`) can be automated to ensure your underlying dependencies still satisfy minimum application requirements after unattended system package upgrades.
* **Update Caution:** Automate updates with extreme care. Because it actively swaps out application code, an incompatible release will be installed just as quickly as a stable one. **Pin an exact version** in `config.php` on production servers where stability matters.
* **Logging Tiers:** All CLI actions are recorded using the same three-tier logging system as web requests:
  * `Op-Act`: Normal operational activity.
  * `WARNING`: Non-breaking items an administrator should review.
  * `ERROR`: Critical failures tied to numbered definitions in `ERROR_DESCRIPTIONS.txt`.

---

## 🔓 About Open Source
This application is open-source software licensed under the **GPLv3 license**. Anyone can obtain a copy and utilize it for personal or commercial operations.

* **Reciprocity Requirement:** If you modify this application and leverage it to generate revenue or offer it as a customer-facing product, **you MUST make your modified source code publicly available**.
* **Contributing Changes:** The ideal workflow is to fork the official `zelon88/HRConvert2` GitHub repository and commit your modifications there. Alternatively, you can submit a Pull Request to have your optimizations added directly to the official codebase (with proper authorship credit). 
* By reciprocating changes and software optimizations, the community ensures that `HRConvert2` remains highly capable, stable, and bug-free for everyone.