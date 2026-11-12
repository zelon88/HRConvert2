**🛠️ HRConvert2: About Resource Rate Limiting — System Architecture, Resource Budgeting, and Core Communications**

Welcome to the official Resource Rate Limiting manual for HRConvert2. This document describes how administrators with CLI access to HRConvert2 can utilize the built-in features (see `convertCore.php` and related components).

---

## 🛠️ Core Infrastructure Specifications

### Legal & Distribution
- **Intellectual Property:** HRConvert2 is Copyright © 2026 by Justin Grimes ([GitHub](https://www.github.com/zelon88)).
- **Licensing Model:** Protected under the **GNU GPLv3** Open-Source License. Refer to the [Official License Portal](https://www.gnu.org/licenses/gpl-3.0.html) for distribution requirements.

### System Intent
This application provides a highly accessible, unauthenticated web interface enabling file conversions directly on a server infrastructure using any modern web browser.

| Metric     | Minimum Operational Requirements |
| :--------- | :------------------------------ |
| **Hardware**| Raspberry Pi Model B+ (or any standard x86 / x64 platform) |
| **Environment** | Debian Linux + Apache Web Server v2.4 + PHP v8.0 or greater |

> ⚙️ **Core Dependencies:** The authoritative list of dependencies is `Resources/Contract/depends.php` and nothing else. Run `php convertCore.php -v` to see what this installation has.

---

## 🧭 About Resource Rate Limiting

Standalone HRConvert2 has no request rate limiting by default. A busy server will accept every conversion it is offered and can accept more work than the hardware can perform.

Resource rate limiting adds a **budget mechanism** (configured in `config.php`) to place hard limits on resource-intensive operations. It is optional and enabled by default.

- WHEN DISABLED (Default Mode)
  - HRConvert2 behaves as before: nothing is checked, nothing is refused, and no additional listener processes run.
  - Ideal for single-user installations or lightly loaded servers.

- WHEN ENABLED (Production Mode)
  - A dedicated listener process manages system health.
  - It holds a global budget, grants conversion permissions, tracks active tasks, and reclaims resources as jobs finish.

---

## 📡 About the Listener

The listener is the four manager roles under `Resources/Engine/Managers`. `coreManager.php` is the one an operator starts, and it starts the other three.

### 🔐 Security & Validation
- **Engine Subcomponents:** Each role file is pinned by the Engine in `getAcceptedManagers` and loaded only in a process dispatched to that role.
- **Optional:** `convertCore.php` runs perfectly well without any of them.
- **Strict Versioning:** A role's version must match the version the Engine accepts exactly. A mismatch is refused and resource awareness becomes unavailable.

---

## 👥 The 4 Core Micro-Processes

A single command spins up four separate PHP processes, each with specific responsibilities:

1. Core Manager (The Supervisor)
   - Supervises the other managers and auto-restarts any that die.
   - Routes incoming requests to the manager responsible for them.
   - Holds no budget, no registry, and no session map of its own.

2. Request Manager (The Gatekeeper)
   - Owns the only socket a worker process may address.
   - Decrypts, validates, and forwards worker requests to the Core Manager.
   - Instantly refuses any request type a worker is unauthorized to issue.

3. Resource Manager (The Allocator)
   - Owns the resource budget, worker registry, and session-to-location map.
   - Periodically polls the host to recalculate the available budget.
   - Determines which data location a session uses.
   - Finds workers that outlive their runtime and asks the Worker Manager to end them.

4. Worker Manager (The Executor)
   - Stateless component that holds no memory of past events.
   - Destroys processes immediately when instructed by the Core Manager.
   - The only component allowed to forcefully act on a process without negotiation.

---

## 🧦 About Socket Communication

Every manager listens on a local Unix domain socket located in a designated Sockets folder within the data directory.

- **Permissions:** Created with secure 0700 permissions, owned by the web server user.
- **Location:** Isolated outside the public web root.

DIRECTIONAL FLOW: One-Way Traffic Only 🛑  
Workers ───[ Encrypted Messages ]───► Request Manager Socket

⚠️ **Security Boundary:** A worker cannot send messages that are routed directly to another worker—whether the worker was invoked by a web request, command line, or userspace. This is a deliberate security feature.

---

## 🔒 Message Cryptography & Transport

- **Encryption:** AES-256-GCM using a key derived from the install secret and the specific channel used.
- **Replay Protection:** Every message carries a timestamp and nonce. Messages older than thirty seconds (`$ManagerMessageSkew`) are discarded.
- **Duplex Response:** Replies return via the same connection the sender opened; workers need no listening address to receive answers.
- **Performance Batching:** Messages are processed in batches of up to thirty two (`$ManagerMessageBatchSize`) per loop cycle to reduce connection overhead.
- **Network Limit:** Unix domain sockets do not cross physical hosts. Split topologies (front-end and listener on separate servers) are unsupported, though shared storage is compatible.

---

## 🔑 About Startup Keys

Every core-to-core startup call is cryptographically keyed against the main install secret.

- **The Token:** An HMAC calculated over the authorized purpose and a temporary time bucket of ten seconds (`$StartupKeyWindow`).
- **Clock Tolerance:** The current and immediately preceding time buckets are accepted to accommodate launch delays.
- **Expiration:** A key becomes useless after a few seconds.

CRITICAL SECURITY POLICY:
- THE STARTUP KEY IS NEVER PRINTED AND NEVER WRITTEN TO LOGS.
- This prevents log scrapers or console captures from stealing active keys.

- **User Privilege Isolation:** Standard CLI users hold a per-user secret in a folder of their own in the temporary directory. They cannot derive a valid core startup key and therefore cannot start or fake listeners. The web server user holds the install secret for the listener's commands, so it can.
- **Hidden Flags:** `--start-core-manager` and `--start-manager` are internal. They are triggered by `-l` alongside a valid key and are rejected otherwise.

---

## 📊 About the Resource Budget

System assets are measured in arbitrary cost units. The host is polled every `--Resource Poll Interval--` seconds, and the budget is worked out from that poll and the operations in flight each time one asks for it, based on three pillars. Until v4.0.6 it was worked out only at the poll.

### 🧩 The Budget Equation
1. **Base Budget:** Set by `--Total Resource Budget--`. If 0, defaults to 100 units per CPU core.
2. **Reserve Pool:** `--Reserve Resource Percentage--` — a slice never allocated to conversions to keep the server responsive.
3. **Pressure Penalty:** A dynamic throttling value scaled against whichever metric is under higher pressure: CPU load average or memory usage.

- Idle server → no throttling (maximum performance)
- Loaded server → rejects new work before crashing
- Namespaces nearly spent → every sandbox is a user namespace, so the Resource Manager counts the web server account's at each poll. It warns at 10% free and refuses work at 1% free, with error 31025.

---

## ⏱️ Lifecycle of a Job Request

Six operation types consume budget: conversion, archiving, OCR, Identify, a fetch from a URL, and the user virus scan. Originally only conversion consumed budget; archiving, OCR and scanning were unmetered and under-represented the real load.

Each operation declares `--Default Conversion Cost--` and `--Default Expected Runtime--`. These are not weighted against one another because exact measurements are not available.

Before an operation begins, it must declare its estimated cost and expected runtime.

- The request is REFUSED if:
  - ❌ The runtime exceeds `--Maximum Expected Runtime--`
  - ❌ The worker count hits `--Maximum Concurrent Workers--`
  - ❌ The remaining unallocated budget is smaller than the requested job cost

- The request is APPROVED if:
  - The job fits within safety margins
  - The system issues an official Budget Token
  - The worker returns the token upon completion, instantly reclaiming resources
  - The return is registered as a shutdown handler when the token is issued so operations that die mid-run still return borrowed resources rather than leaving them for the reaper

A refused operation is not an error—nothing failed and nothing was attempted. The core writes a warning naming the operation and prints the same alert string a refused conversion prints, which can be used by front-ends for reporting.

---

## 🔄 About Worker Lifecycle

The Resource Manager scans its registry every `--Worker Reap Interval--` seconds to clear stagnation.

### 🧹 The Reaping Process
A tracked worker is flagged as **stale** and handed to the Worker Manager for destruction if:
- Its active process no longer exists (it crashed without sending a completion message).
- It outlives its expected runtime plus the `--Worker Stale Grace Period--`.

Pro tip: keep the grace period generous. A slow conversion must not be reaped—reaping leads to lost conversions.

### ⏳ Lifeline Extensions
A conversion that needs extra time may request an extension.
- **Limit:** Granted up to `--Maximum Runtime Extensions--` times.
- **Hard Ceiling:** Cannot extend beyond `--Maximum Expected Runtime--`.

---

## 📂 About Data Locations

`$ConvertLoc` is always a valid string that resolves to a usable path for the specific worker and conversion.

### 🔀 Additional Data Locations
The `--Additional Data Locations--` flag allows adding further pools, declared as an array containing a path and a strategy type:

| Strategy     | Behavior |
| :----------- | :------- |
| roundrobin   | Distributes new sessions across the pool by session identifier. Requires no shared counter; works across detached front-ends. |
| leastactive  | If any location uses this, the entire pool selects paths based on the fewest active sessions. |
| redundant    | Acts as a strict standby; it takes a session only when every other location is completely unusable. |

⚠️ **`--Data Storage Directory--` is always a pool member, and always its primary.** Never declare it
under `--Additional Data Locations--`. It joins the distribution and is swept on the same schedule
as every other member. A second declaration of the same path is skipped as a duplicate and the
strategy named on it is discarded, so declare each location exactly once.

⚠️ **Sticky Sessions:** A session is assigned a location once and keeps it for its lifecycle. Load balancing occurs at the session level, not at individual conversions.

---

## 🗺️ The Map Cache

The listener hosts the session-to-location map so every front-end sharing the install secret receives the same path answer.

- The map is a **cache**, not a permanent log.
- If the listener restarts mid-session, it discovers the existing directory on disk and returns the original location.
- **Standalone Fallback:** Without a listener, the standard `--Data Storage Directory--` is used.
- **Static Socket Directory:** The socket directory is pinned to `--Data Storage Directory--` and never moves with a session (otherwise workers and managers would look in different locations).

---

## 🧹 About Scheduled Storage Cleanup

⚠️ **A location missing from the sweep keeps its expired sessions forever.** Nothing else deletes
them. The clean calls in `convertCore.php` only sweep the location the running request was
assigned, so a location receiving no sessions is never reached by those either. Run the Resource
Manager with `--Enhanced Logging Verbosity--` on and read the `Scheduled sweep` lines to confirm every location you
configured is listed.

The Resource Manager sweeps each configured data location every `--Storage Cleanup Interval--` seconds, deleting files older than `--File Deletion Age Threshold--`.

- **Shared Cleanups:** The sweep uses the same cleanup function as the standalone `convertCore.php`.
- **Redundancy:** Standalone routines are required if no listener runs, but are redundant when a listener is active.
- **Scope Isolation:** Only shared data locations are swept. Temporary locations live inside the local installation directory and are cleaned by each front-end.
- **Disabling:** Set `--Storage Cleanup Interval--` to 0 to disable scheduled sweeps.

---

## 🛡️ About Per-Conversion Resource Ceilings

Enabling `--Enable Per Conversion Limits--` runs each conversion inside a transient `systemd` scope with isolated CPU and memory ceilings. This prevents a single heavy file from starving the host.

Every expensive operation runs inside a ceiling, not only format conversion. Anything routed through `sandboxCommand()` is wrapped. Example pipelines:

| Pipeline   | Previously | Now |
| :--------- | :--------- | :-- |
| ClamAV     | Ran raw; signature DB can exceed 1 GB. | Wrapped, type `Scan`. |
| ScanCore   | Ran raw with only PHP `memory_limit`. | Wrapped, type `Scan`. |
| OpenSCAD   | `nice -n 19` but no memory ceiling. | Wrapped, type `Scad`. |

A PHP `memory_limit` is not a system ceiling — it is an internal limit and does not prevent the kernel from being affected. The ceiling wraps the scanner itself (not the shell pipeline) so the actual resource consumer is measured.

A large file does not raise what a scan needs. Each scanner is sized to its ceiling when it is dispatched, and a file it cannot finish is reported as not scanned, never as clean.

- **ClamAV:** the ceiling must hold its signature database, which no argument shrinks. What ClamAV unpacks goes to a private folder in the conversion location, not to the sandbox's `/tmp`, which is always memory. Its size limit is four times the file, between ClamAV's 400M default and the 2047M it can scan, and its file-count and time limits grow by the same factor. The time limit also stretches for a processor share under one processor. A file over 2047M is refused before the database loads.
- **ScanCore:** its memory limit is the configured one or the ceiling less 64M, whichever is smaller. It holds about three chunks at once, so the chunk is held to a quarter of that limit, and a larger configured chunk is reduced with a warning.

Replacing OpenSCAD's `nice -n 19` with an actual ceiling is not strictly stronger on every host. Where a scope can be created it is far stronger, because a `CPUQuota` is enforced against a cgroup instead of requested from the scheduler. Where no scope can be created, the niceness is derived from the configured processor share, so the stock Scad entry of `'75,1024'` yields less than a fixed 19 did. Set a smaller processor share for Scad to get a larger niceness back.

### 📐 The Ceiling Table
`--Maximum Per Conversion Resources--` declares the ceiling for each conversion type as a processor percentage and a memory ceiling in megabytes, comma separated. The processor figure is a percentage of **one** processor, so `200` is two whole cores. A type that is not listed falls back to `--Default Per Conversion Resources--`, unless the core carries a built-in for it.

The resolution order is:
1. The listener's scaled table.
2. `--Maximum Per Conversion Resources--`.
3. The core's built-in for that type.
4. `--Default Per Conversion Resources--`.

These are **maxima**. With a listener running each is scaled down by what the host has free, and never below `--Minimum Per Conversion Resources--`. With no listener they are used unchanged.

- The processor share shrinks with load, and is split between the conversions in flight and the new one.
- The memory ceiling is what the host has available, less `--Reserve Resource Percentage--`, split the same way, and never more than the configured ceiling. A kernel that does not report available memory gives each conversion its split of the configured ceiling instead.

A conversion counts only those already in flight. Until v4.0.6 the listener registered the new conversion before it scaled, so a conversion on its own counted itself and was given half, and both ceilings shrank again by whichever of load or memory in use was higher. LibreOffice was killed inside the 137M a 512M document ceiling had become.

> ⚠️ **Scan's memory is never scaled.** Only its processor share is. A scanner needs the memory its loaded signature database takes, so a smaller ceiling does not slow a scan, it has the kernel kill it. The core carries its own Scan ceiling because a ClamAV database does not fit inside 512M, and anything an administrator sets always wins.

### 👤 User Scopes, Never System Scopes
The web server user cannot create a system scope and must not be given permission to. An account holding systemd `manage-units` can start a transient service as root. HRConvert2 uses a **user manager** instead, which governs only the cgroup subtree delegated to it. Running `-fp` as root enables lingering for the web server user and writes the delegation drop-in a processor quota requires.

| Mechanism | Meaning |
| :--- | :--- |
| `user` | A user scope. This is the correct configuration. |
| `system` | A system scope. An operator configured this deliberately. Remove it. |
| `none` | No scope. The conversion is deprioritized with `nice` and has no memory ceiling. |

---

## 🪂 About Fallback Behaviour

**Every failure in this subsystem fails open.** A conversion proceeds when the component is missing or mismatched, when no listener is running, when a socket cannot be reached, and when no usable reply comes back. Each is recorded as a warning. Only an explicit answer may refuse a conversion.

`--Require Resource Awareness--` inverts this for an operator who would rather refuse work than accept work they cannot account for. With it set, a conversion the listener gives no answer for is refused.

A resource ceiling is a courtesy to the host and is not a security control. The sandbox is the security control.

---

## 🐢 About Slow Uploads

**The budget meters conversions, not the web server.** Apache holds a worker for as long as an upload takes. Ubuntu's `mod_reqtimeout` keeps reading while 500 bytes or more arrive each second, and prefork starts at most 150 workers by default, so about 75 KB/s of slow uploads can hold every worker without a conversion ever starting.

HRConvert2 caps an upload at 5000M, in PHP and in its Apache configuration. How long an upload may take is server-wide, so it is the administrator's call. Replacing the body line in `/etc/apache2/mods-available/reqtimeout.conf` with this costs a slow client a hundred times as much, and fails any upload slower than about 400 Kbit/s:

```
RequestReadTimeout body=20,minrate=50000
```

---

## 💻 About Command Line Operation

| Argument | Purpose |
| :--- | :--- |
| `-l`, `--listen` | Start the resource listener. |
| `-k`, `--kill` | Stop the resource listener and every manager it started. |
| `-k <worker-id>` | End one worker by budget token or process identifier. |
| `--kill-all-workers` | End every tracked conversion in progress. |
| `--kill-every-worker` | End every PHP process owned by the web server user. |
| `-y`, `--yes` | Skip the confirmation prompt on the two above. |
| `--status` | Report listener and resource budget state. Available to any user. |
| `-fp`, `--fix-permissions` | Correct ownership and permissions, and enable conversion limits. Root only. |

The listener runs until it is stopped. It is started at boot by the service unit `--setup --install-service` writes, or from the container entrypoint.

---

## 💾 About Persistence

The Resource Manager holds the budget, the worker registry and the session map in its own memory and nowhere else, so no process can read a half written file. The one file that survives between processes is the manager process identifiers beside the sockets, because `-l`, `-k` and `--status` must find the listener before they can speak to it. A Resource Manager that restarts rebuilds its map by discovery and recalculates the budget on the next poll. An entry older than the deletion threshold plus an hour is dropped from the map.

---

## 🩺 About Troubleshooting

| Symptom | Cause & Fix |
| :--- | :--- |
| The listener starts and then stops. | The web server user cannot read the install secret or write the logfile. Run `-fp` as root. |
| Every conversion warns that the listener did not answer. | No listener is running. Check `--status` as root, and confirm four socket files exist in the Sockets directory. |
| Conversions are refused with no reason given. | A reply was delivered but could not be read. The conversion proceeds; a manager is slow or has died. |
| A processor quota appears to do nothing. | The delegation drop-in is missing, or the user manager has not restarted since it was written. Run `-fp` as root, then restart the user manager for the web server user. |
| Nothing appears in the log at all. | A root command line run created the logfile first. Run `-fp` as root. |

Every numbered error this subsystem produces is documented in `ERROR_DESCRIPTIONS.txt`.
