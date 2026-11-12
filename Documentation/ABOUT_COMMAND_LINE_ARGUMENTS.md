# 💻 HRConvert2: Command Line Interface (CLI) Guide

Welcome to the official Command Line Interface (CLI) manual for HRConvert2! This document describes how administrators with CLI access to the HRConvert2 can utilize the built-in features of `convertCore.php` to simplify maintenance, improve reliability, troubleshoot effectively & maximize uptime.

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

Arguments are case insensitive as of v3.9.9. A path or a name given to an option is passed exactly as typed.

---

## CLI User Account Considerations

### Running as Root
HRConvert2 should be run as **root** when using the command line.

* **Nothing Left Unusable:** The data location is root's, and the session, cache, log and socket folders in it belong to the web server user. Root logs to a file of its own in `Engine`, which no other account can read. The install secret is kept in `Secrets`, belongs to root and is readable by the web server user's group and nobody else.
* **The Two Rules:** Root never runs, reads as code, or works by name inside anything the web server user can change. See [ABOUT_MANAGED_LOCATIONS.txt](ABOUT_MANAGED_LOCATIONS.txt).
* **The Gate:** Every command run as root first reads the whole installation. Every name in it must belong to root and be writable by nobody else, with no links, and every folder above it must pass the same test, `/var/www/html` included. Anything else stops the command with error 28008. Only the repairs, `-fp`, `--setup` and `--config --repair`, run anyway after a warning, because they are what give an installation to root.

### Running as Non-Root
Running as a non-root user is safe but structurally limited.

* **Fail-Safe Filesystem:** Any operation requiring write access that the current user lacks will fail safely rather than partially completing. The filesystem is never left in a broken state.
* **Unrestricted Flags:** The version (`-v`) and help (`-h`) arguments work for any user.
* **Secret File Protection:** The install secret is `root:www-data 0640`, in a folder that is `root:www-data 0750` and at the top of the data location, so a non-root user who is not the web server user and is not in its group can read neither copy, and cannot even list `Secrets`. This is deliberate, because this file derives every session identifier in the application. Any other account's command is given a secret of its own, in a folder of its own, even where that account is in the group, and so is the web server user's for anything but the listener's commands, `-c` and `--status`. `-v` names that row `Per user secret`.

### Manual Permission Management
An administrator who modifies an HRConvert2 installation using external tools must leave every location the way `-fp` leaves it:

| Location | Owner & Mode |
| :--- | :--- |
| **The installation** | `root:root`, folders `0755`, files `0644` |
| **The DATA folder in the hosted location** | `www-data:www-data 0700` |
| **The data location** | `root:root 0755` |
| **Its `Sessions`, `Cache`, `Logs` & `Sockets` folders** | `www-data:www-data 0700` |
| **Its `Engine` folder** | `root:root 0700` |
| **Its `Secrets` folder** | `root:www-data 0750` |
| **The install secret** | `root:www-data 0640`, in `Secrets` and at the top of the data location |
| **The backup** | `root:root 0700` |

> ⚠️ **Do not give the installation to the web server user.** `chown -R www-data` was the documented repair until v4.0.1, and it let the web server user change code that root then runs. To correct anything, run `-fp` instead:

```bash
sudo php /var/www/html/HRProprietary/HRConvert2/convertCore.php -fp
```

## Supported Command Line Arguments

| Argument | Description | Requires |
|---|---|---|
| `-v`, `--version` | Display version & dependency information. | any user |
| `-h`, `--help` | Display the built in help message. | any user |
| `--status` | Report listener, budget & data location state. | any user |
| `-c`, `--clean` | Sweep expired sessions from both data locations. Root hands the sweep to `www-data`. | root or `www-data` |
| `-c=<minutes>` | Sweep sessions older than that many minutes. | root or `www-data` |
| `-c=now` | Sweep every session regardless of age. | root or `www-data` |
| `-u`, `--update` | Update using the configured target. | root |
| `-u=latest` | Update to the newest tagged release. | root |
| `-u=edge` | Update to the current state of the master branch. | root |
| `-u=v#.#.#` | Update to exactly that tagged release. | root |
| `-fp`, `--fix-permissions` | Correct ownership, permissions & policy files. | root |
| `--run-environment-manager` | Run one Environment Manager pass. It watches, & repairs only when allowed. | root |
| `--rotate-secret [-y]` | Replace the install secret with a new key. Every session ends. | root |
| `-l`, `--listen` | Start the resource listener. | root or `www-data` |
| `-k`, `--kill` | Stop the resource listener. | root or `www-data` |
| `-k <worker-id>` | End one worker by budget token or process identifier. | root or `www-data` |
| `--kill-all-workers` | End every tracked conversion in progress. | root or `www-data` |
| `--kill-every-worker` | End every PHP process owned by the web server user. | root or `www-data` |
| `-y`, `--yes` | Skip the confirmation prompt on the two above & on `--rotate-secret`. | — |
| `--config <argument>` | Configure, view, verify, repair & back up `config.php`. | root, except `--backup` |
| `--setup <argument>` | Install, check & audit dependencies. | root |
| `--pipeline <argument>` | List, describe & verify the installed pipelines. | any user |
| `--workflow --list` / `--check=<file>` | List every workflow & why one is not usable, or check a workflow file. | any user |
| `--workflow --install=<file>` / `--remove=<id>` | Install a checked workflow into the installed library, or remove one. | root |
| `--build --pins [--write]` | Check every version pin against what ships, or rewrite every pin that differs. A developer step that rewrites source files. | write access to the tree |
| `--app<name>` | Handed to the application untouched. HRConvert2 declares none yet. | — |

`ABOUT_COMMAND_LINE_ARGUMENTS.txt` covers `--config`, `--setup`, `--pipeline`, `--workflow` and `--app` option by option. `ABOUT_WORKFLOWS.txt` describes workflows.

### 🚦 Exit Status
Every command exits with a status a script can act on, as of v3.9.9.

| Status | Meaning |
| :--- | :--- |
| `0` | The command did what was asked, or what it asks for was already true. A report exits `0` whatever it reports. |
| `1` | The command failed, found a fault, was refused or was declined. Every fatal error. |
| `2` | The arguments were not understood. Nothing was attempted. |

---

## Argument Deep Dives

### 🔍 About `-v` and `--version`
Performs a comprehensive diagnostic evaluation of the installation. This is far more than an echo of a version number; it reports every component version, runs dependency checks, and flags failures.

It analyzes and reports:
* Core version, configuration version, and the minimum version requirements.
* Every version-pinned dependency (verifying if minimum requirements are met).
* Functional status of the **bubblewrap sandbox** (every dependency command runs inside it).
* Every installed interface and whether its version matches core requirements.
* Language pack matches and discrepancies across interfaces.
* Active conversion types enabled within the configuration file.

> 🛠️ **Diagnostic Value:** Because these dependency checks are identical to the ones executed by active converters, this flag tells you if conversions will actually work. Any dependency reporting `FAILED` will cleanly refuse to run rather than failing mid-process in a confusing manner. This argument works for **any user**.

* **Only the web server user asks the listener.** Its state is in a folder only that account can read, so `-v` run as root or any other account reports the listener as `NOT ASKED` and names `--status`, which root hands to that account. Until v4.0.6 another account reported a running listener as `STOPPED`.
* **The secret row is named for the secret it judges.** An account with a secret of its own reads `Per user secret`, where until v4.0.6 it read `Install secret`.

### ❓ About `-h` and `--help`
Displays the built-in help message. It lists every supported argument and references the relevant in-depth manuals located in the `Documentation` folder.

* **Blank Invocations:** Running the command with no arguments automatically triggers the help screen. Running web logic directly in a terminal would otherwise dump raw HTML into the shell and mistakenly create orphaned user sessions.
* **Invalid Flags:** Unrecognized arguments default to this help screen and write a warning to the log file.
* This argument works for **any user**.

### 🧼 About `-c` and `--clean`
Sweeps expired sessions from the temporary location and from the data location. The sweep normally happens on every web request; this argument exists for a server where no web request is coming.

* **No Value:** Uses `--File Deletion Age Threshold--` from `config.php`.
* **Whole Number (`-c=<minutes>`):** Uses that many minutes instead, for this run only.
* **Now Value (`-c=now`):** Forces an immediate sweep of every single session, completely disregarding its age.
  > ⚠️ **CRITICAL WARNING:** `-c=now` deletes **EVERY** session, including those currently in use. A user mid-conversion will lose their files. Use this parameter with extreme caution.
* **Typo Protection:** Values that are neither a whole number nor the word `now` are rejected, falling back to the configured default. A warning is written to the logs and printed to the shell to prevent accidental sweeps.
* **Scope Isolation:** Nothing outside a designated session directory is ever touched. The log directory, LibreOffice profiles, and update backups live alongside session data but are strictly protected by name validation. Any location that is not one of the configured data locations is refused outright.
* **The Web Server User Sweeps:** Every name in a session belongs to the web server user, which can swap any of them for a link mid-sweep. Root hands the whole argument to that user instead of sweeping itself, and prints what it reports. Until v4.0.1 root swept as itself.
* **Only What That User Owns:** A day or a session folder another account put in a data location is left where it is, because the web server user could never remove it, and `-fp` names it. Until v4.0.6 the sweep tried, failed on every pass, and said only that it had not completed cleanly.
* **The Whole DATA Folder:** From v4.0.1 the temporary location is the whole DATA folder. Until then this argument and every web request swept only the session folder of the request making them, so no expired session in DATA was ever removed.
* **Privileges:** This argument requires root or the web server user.

---

## `--status`

Reports what the resource listener is doing, and what this account is able to see of it — the
running identity, whether the component is available, the socket directory, each manager's
process identifier, tracked workers, remaining budget and mapped sessions. It also reports when
the last Environment Manager pass ran, what it found and when it last repaired.

**A standard user cannot read the socket directory and is not asked.** The directory is `0700`
and belongs to the web server user, so a standard user could only ever see a listener that
appears stopped, whether it is running or not. The listener row says `NOT ASKED` to such a user
and leaves the listener's own rows out. Until v4.0.6 it said `STOPPED`, with a note that it might
be wrong. Run it as root for the true picture.

**Root does not read the state itself.** It hands `--status` to the web server user and relays
what that user saw, because the state file belongs to that user. The report names root as asking
and the web server user as answering.

---

## `-fp`, `--fix-permissions`

Corrects everything about an installation that root is required to correct.

- Gives every managed location its role. The installation is root's, each data root is root's
  with its sessions, cache, logs and sockets the web server user's, and the backup is root's
  alone. See [ABOUT_MANAGED_LOCATIONS.txt](ABOUT_MANAGED_LOCATIONS.txt). A location counts as
  corrected only when its owner or mode differed from its role, so a run straight after another
  corrects none. Until v4.0.6 every location a role was applied to was counted.
- Moves each session an older release kept at the top of a data root into that root's `Sessions`
  folder. Only a folder the web server user owns, named as the core names a session, is moved.
  Every other folder there, and every name in `Sessions` that user does not own, is named and
  left, because the sweep runs as that user and could never remove it. Until v4.0.6 every folder
  was moved, so a release unpacked into a data root went into `Sessions` as root's. The capability
  cache an older release left at the top of a data root is moved into `Cache` or removed.
- Removes from the installation what an earlier release left there and no release ships, such as
  the tree FFmpeg's build left in `Documentation/Build/ffmpeg-build` until v4.0.6, without
  following anything in it. A link or a file with more than one name it does not know is named,
  to be removed by hand before `-fp` is run again.
- Installs the tree it runs from into `Application` in the data location, when that tree is not
  the installed one, and hands over to the installed copy as it ends.
- Writes the loader, the protection page and the static files into the hosted location, and
  removes every copy of the application left there. Names a `.git` or `.github` folder a release
  left in it and an `.old` installation an update left beside it, which the Apache configuration
  it writes refuses, and removes none of them.
- Removes the build environment and the development documentation when `config.php` asks for it.
- Disables a bootstrap script that does not match this core, and enables one that matches again.
- Installs or repairs the AppArmor profile named for the application, where the kernel can
  restrict unprivileged namespaces. It is attached to the application's own copy of bubblewrap,
  which every sandbox runs, and only where no account but root could put a program at that path.
  One attached where that is no longer so is taken out. The copy is made only by
  `--setup --install-depends`, `--update-depends` and `--install-complete` and as an update is
  installed, and never by `-fp` run from the installed copy.
- Never builds or removes the application's own build of a dependency, which only
  `--setup --install-depends`, `--update-depends` and `--uninstall-depends` do. See
  [ABOUT_DEPENDENCIES.txt](ABOUT_DEPENDENCIES.txt).
- Lifts that restriction only where bubblewrap refuses a sandbox twice and `config.php` permits
  it, and never for an attempt cut short or to cover a copy that is missing or cannot be run, and
  holds for the next boot only the kernel settings it changed. Where `config.php` forbids lifting
  it, switches back on a restriction this application lifted.
- Names a restriction an earlier release lifted that the application's own copy of bubblewrap no
  longer needs lifted, and says how to put it back, but leaves it lifted, because by now other
  programs on the host may rely on it. Names `/etc/apparmor.d/bwrap` where no package installed
  it, which install guides before v3.8.0 asked for, and leaves it as it is.
- Writes the listener's service unit, and the Environment Manager's where they are installed, for
  the installed copy.
- Puts back, as they were, the ImageMagick policy and the AppArmor additions an earlier release
  wrote into the host. Each pipeline now carries its own policy into its sandbox.
- Enables lingering and cgroup delegation for the web server user, when per conversion limits
  are enabled. Lingering already on is left as it is.
- Writes the PHP settings into the configuration folder of every PHP that is installed, and
  passes over a folder a removed PHP left behind, which nothing reads.

Every step is idempotent. A policy file this application did not write is backed up with a
timestamp before replacement; if the backup fails, the original is left alone and the step is
refused.

**Run this once after updating from v4.0.0.** Until v4.0.1 this handed the installation and every
data location to the web server user, and root then ran code that account could change. It now
gives each location the role it needs, and an installation v4.0.0 left behind keeps the old
ownership until it runs. That run also moves the installation out of the web root, into
`Application` in the data location. A command run as root no longer leaves a logfile or a secret the web
server user cannot use, so it no longer has to be followed by this.

---

## `--run-environment-manager`

Runs one Environment Manager pass and exits. The timer `--setup --install-service` writes runs it
hourly once an administrator enables that timer, and an administrator may run it by hand. Until
v4.0.6 only `--setup --install-complete` wrote that timer, so a host that installed its listener
with `--install-service` had no timer, and the manager never ran there.

It checks what `-v` checks, logs every problem as a warning, records what it found and prints it.
**It changes nothing** unless `--Environment Manager May Repair--` is `TRUE` in `config.php`. Then
the first pass of a day that finds a problem runs the repair `-fp` runs, unattended: it starts and
enables no service, and rewrites the host configuration this application owns only when
`--Environment Manager May Rewrite Configs--` is `TRUE`. Every later pass that day only reports.

What it found is kept in `hrconvert2-environment.state` at the top of the data location, readable
by the web server user and written only by root, and `--status` reports the last pass. Every pass
also removes root's own logfiles older than thirty days. One pass runs at a time, and one started
beside another checks and changes nothing.

It exits `0` when the pass completed, whatever it found, and `1` when it could not run. See
[ABOUT_ENVIRONMENT_MANAGER.txt](ABOUT_ENVIRONMENT_MANAGER.txt).

---

## `--rotate-secret`

Replaces the install secret with a new key. **Every session ends**, because every session
folder, token and startup key derives from the secret. It asks first, and `-y` answers in advance.

A running listener is stopped and started again, and root does neither. It asks the web server
user whether a listener is running and stops it, both **before** the secret is rewritten, so the
stop is signed with the key on disk the running listener still answers to. Root then rewrites the
secret, and the web server user starts the listener again, reading the new key from disk, through
its service unit when one owns it. A listener that was not running is left stopped. It is never
started as root.

Run it once in every container started from an image built before v4.0.1. Each of those carries
the secret its image was built with, and every container started from the image shares it.

---

## `-l`, `--listen`

Starts the resource listener — four processes holding a resource budget, deciding which data
location each session uses, and managing worker lifecycle.

Resource awareness is optional and enabled by default. With no listener running, HRConvert2
behaves exactly as it always has. `$EnableResourceAwareness` must be `TRUE` before this does
anything.

Started as root, the listener drops to the web server user before launching, so every socket
belongs to the account that must reach it. A listener left running as root is unreachable by
every web request.

The startup key is never printed and never logged. A failed start points at the log rather than
reproducing the command.

Normally started at boot by the service unit `--setup --install-service` writes, or from a container entrypoint, rather than by hand.

**A stop signal stops every manager, not only the first.** When the service is stopped, the Core
Manager signals each manager it started, waits up to twenty seconds for them all to end, ends any
still running, and only then ends itself. The managers are not its children by then, so until
v4.0.6 it ended alone and the service manager killed the three it left behind.

---

## `-k`, `--kill`

With no value, stops the listener and every manager it started. Each is asked to stop, given two
seconds to unwind, then signalled, then verified. A process that has ended but not yet been
collected by its parent is not mistaken for a survivor.

With a value, ends one worker by budget token or process identifier.

```
php convertCore.php -k                  # stop the listener
php convertCore.php -k 12345            # end the worker running as process 12345
php convertCore.php -k a1b2c3d4e5f6     # end the worker holding that budget token
```

A worker that is ended loses whatever it was converting.

Run as root, this is handed to the web server user, which reads its own listener state and
signals its own processes; root reads no state and signals nothing itself.

---

## `--kill-all-workers` and `--kill-every-worker`

Both prompt for confirmation. Both accept `-y` to skip the prompt, which exists for scripts and
not for convenience. Run as root, both are confirmed at the terminal first and then handed to the
web server user with the answer already given; root ends nothing itself.

**`--kill-all-workers`** ends every *tracked* conversion. Every user mid conversion loses their
files. Nothing outside HRConvert2 is touched.

**`--kill-every-worker`** ends **every PHP process owned by the web server user**. This reaches
other applications sharing the host — WordPress, OwnCloud and anything else running as that user
lose every session in progress. It exists for a server that has become unresponsive and must be
recovered without a reboot. It is not a cleanup tool. Manager processes are excluded so the
listener survives its own instruction.

Neither is a substitute for the reaper, which ends overrunning workers automatically without
ending anything else.

---

### 🔄 About `-u` and `--update`
Replaces the live application code with a release from your configured update source.

* **Security Boundary:** Updates are **NEVER** accessible via the web interface. Modifying application code requires file write access, which shell access natively implies. Protecting an HTTP update endpoint with a secret would insecurely reduce server authorization to a single guessable string.

#### System Requirements
* `$EnableAutoUpdates` must be set to `TRUE` in `config.php` (Default is `FALSE`).
* The server must have outbound network access to reach the update source.
* The update runs as root. The folder holding the installation and the `--Proprietary Directory--` must belong to root and be writable by nobody else, or the update is refused with ERROR 31023.
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
1. The release is downloaded and unpacked into a private working folder beside the installation.
2. Your existing configuration is safely merged into the temporary directory first.
3. The prepared release is moved to the same filesystem as the active installation.
4. The live data directory is moved into the new layout so no active sessions are lost.
5. The live installation and the new release are instantly swapped using **two atomic rename operations**.
6. The new release's own `-fp` runs as the update finishes, so its locations get their roles.

> ⚡ **Web Request & Process Impact:** Because renames within a single filesystem are atomic, the directory is never half-populated. A web request arriving mid-swap will seamlessly hit either the old or new code. The tiny fraction of a second between the two renames might cause a request to drop, but it will succeed instantly on retry. Conversions already in progress are completely unaffected because running processes hold open file handles that remain valid across folder renames.

#### Failure & Rollback Mechanics
* **Pre-Exchange Failures:** Any error occurring before the directory swap leaves your live installation entirely untouched.
* **Post-Exchange Validation:** After the swap, the new installation is prompted via a separate process to report its own version. If the new core fails to parse, cannot load its config, or cannot access the CLI branch, it will fail to respond.
* **Automatic Rollback:** An installation failing this health check is instantly rolled back, restoring the previous version. The event is recorded in the system log.

#### Managing the Previous Version
* After a successful update, the old core is copied to the `--Backup Location--` configured in `config.php`, which defaults to `Last-Installed-Version` inside the data directory. The data location is root's now, so the backup's folder is root's and the copy is kept by default. An update refuses to keep a backup **only** where `$BackupLoc` has been pointed at a folder another account could replace, and a WARNING says so there. See `Documentation/ABOUT_MANAGED_LOCATIONS.txt`.
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