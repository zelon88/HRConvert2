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

> ⚙️ **Core Dependencies:** FFMPEG, Dia, LibreOffice, Mkisofs, 7zip, Unoconv, libgxps-utils, Tesseract, Unzip, OpenSCAD, Rar, Inkscape, Unrar, ClamAV, MeshLab, PopplerUtils, PDFTOTEXT, ImageMagick, ...

---

## 🧭 About Resource Rate Limiting

Standalone HRConvert2 has no request rate limiting by default. A busy server will accept every conversion it is offered and can accept more work than the hardware can perform.

Resource rate limiting adds a **budget mechanism** (configured in `config.php`) to place hard limits on resource-intensive operations. It is optional and disabled by default.

- WHEN DISABLED (Default Mode)
  - HRConvert2 behaves as before: nothing is checked, nothing is refused, and no additional listener processes run.
  - Ideal for single-user installations or lightly loaded servers.

- WHEN ENABLED (Production Mode)
  - A dedicated listener process manages system health.
  - It holds a global budget, grants conversion permissions, tracks active tasks, and reclaims resources as jobs finish.

---

## 📡 About the Listener

The listener is implemented in `Resources/coreManager.php` and acts as a detachable component.

### 🔐 Security & Validation
- **Decoupled Design:** Validated the same way as a GUI or language pack.
- **Lazy Loading:** Only loads if an explicit install secret exists. `convertCore.php` runs without it.
- **Strict Versioning:** Component versions must match the core required version exactly. Mismatches are refused to prevent unsafe calls and to disable resource awareness when incompatible.

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
   - Hunts down and terminates zombie workers that outlive their runtime.

4. Worker Manager (The Executor)
   - Stateless component that holds no memory of past events.
   - Destroys processes immediately when instructed by the Core Manager.
   - The only component allowed to forcefully act on a process without negotiation.

---

## 🧦 About Socket Communication

Every manager listens on a local Unix domain socket located in a designated Sockets folder within the data directory. [2]

- **Permissions:** Created with secure 0700 permissions, owned by the web server user.
- **Location:** Isolated outside the public web root. [3]

DIRECTIONAL FLOW: One-Way Traffic Only 🛑  
Workers ───[ Encrypted Messages ]───► Request Manager Socket

⚠️ **Security Boundary:** A worker cannot send messages that are routed directly to another worker—whether the worker was invoked by a web request, command line, or userspace. This is a deliberate security feature.

---

## 🔒 Message Cryptography & Transport

- **Encryption:** AES-256-GCM using a key derived from the install secret and the specific channel used. [4]
- **Replay Protection:** Every message carries a timestamp and nonce. Messages older than `--Manager Message Skew` are discarded. [5]
- **Duplex Response:** Replies return via the same connection the sender opened; workers need no listening address to receive answers.
- **Performance Batching:** Messages are processed in batches up to `--Manager Message Batch Size` per loop cycle to reduce connection overhead.
- **Network Limit:** Unix domain sockets do not cross physical hosts. Split topologies (front-end and listener on separate servers) are unsupported, though shared storage is compatible. [...]

---

## 🔑 About Startup Keys

Every core-to-core startup call is cryptographically keyed against the main install secret. [7]

- **The Token:** An HMAC calculated over the authorized purpose and a temporary time bucket (`--Startup Key Window` seconds).
- **Clock Tolerance:** The current and immediately preceding time buckets are accepted to accommodate launch delays.
- **Expiration:** A key becomes useless after a few seconds. [8]

CRITICAL SECURITY POLICY:
- THE STARTUP KEY IS NEVER PRINTED AND NEVER WRITTEN TO LOGS.
- This prevents log scrapers or console captures from stealing active keys.

- **User Privilege Isolation:** Standard CLI users hold a per-user secret in their home directory. They cannot derive a valid core startup key and therefore cannot start or fake listeners.
- **Hidden Flags:** `--start-core-manager` and `--start-manager` are internal. They are triggered by `-l` alongside a valid key and are rejected otherwise.

---

## 📊 About the Resource Budget

System assets are measured in arbitrary cost units and updated every `--Resource Poll Interval` seconds based on three pillars.

### 🧩 The Budget Equation
1. **Base Budget:** Set by `--Total Resource Budget`. If 0, defaults to 100 units per CPU core.
2. **Reserve Pool:** `--Reserve Resource Percentage` — a slice never allocated to conversions to keep the server responsive.
3. **Pressure Penalty:** A dynamic throttling value scaled against whichever metric is under higher pressure: CPU load average or memory usage.

- Idle server → no throttling (maximum performance)
- Loaded server → rejects new work before crashing

---

## ⏱️ Lifecycle of a Job Request

Four operation types consume budget: conversion, archiving, OCR, and the user virus scan. Originally only conversion consumed budget; the other three were unmetered and under-represented the real load.

Each operation declares `--Default Conversion Cost` and `--Default Expected Runtime`. These are not weighted against one another because exact measurements are not available.

Before an operation begins, it must declare its estimated cost and expected runtime. [9]

- The request is REFUSED if:
  - ❌ The runtime exceeds `--Maximum Expected Runtime`
  - ❌ The worker count hits `--Maximum Concurrent Workers`
  - ❌ The remaining unallocated budget is smaller than the requested job cost

- The request is APPROVED if:
  - The job fits within safety margins
  - The system issues an official Budget Token
  - The worker returns the token upon completion, instantly reclaiming resources
  - The return is registered as a shutdown handler when the token is issued so operations that die mid-run still return borrowed resources rather than leaving them for the reaper

A refused operation is not an error—nothing failed and nothing was attempted. The core writes a warning naming the operation and prints the same alert string a refused conversion prints, which can be used by front-ends for reporting.

---

## 🔄 About Worker Lifecycle

The Resource Manager scans its registry every `--Worker Reap Interval` seconds to clear stagnation.

### 🧹 The Reaping Process
A tracked worker is flagged as **stale** and handed to the Worker Manager for destruction if:
- Its active process no longer exists (it crashed without sending a completion message).
- It outlives its expected runtime plus the `--Worker Stale Grace Period`. [10]

Pro tip: keep the grace period generous. A slow conversion must not be reaped—reaping leads to lost conversions.

### ⏳ Lifeline Extensions
A conversion that needs extra time may request an extension.
- **Limit:** Granted up to `--Maximum Runtime Extensions` times.
- **Hard Ceiling:** Cannot extend beyond `--Maximum Expected Runtime`.

---

## 📂 About Data Locations

`$ConvertLoc` is always a valid string that resolves to a usable path for the specific worker and conversion.

### 🔀 Additional Data Locations
The `--Additional Data Locations` flag allows adding further pools, declared as an array containing a path and a strategy type:

| Strategy     | Behavior |
| :----------- | :------- |
| roundrobin   | Distributes new sessions across the pool by session identifier. Requires no shared counter; works across detached front-ends. |
| leastactive  | If any location uses this, the entire pool selects paths based on the fewest active sessions. |
| redundant    | Acts as a strict standby; it takes a session only when every other location is completely unusable. |

⚠️ **Sticky Sessions:** A session is assigned a location once and keeps it for its lifecycle. Load balancing occurs at the session level, not at individual conversions.

---

## 🗺️ The Map Cache

The listener hosts the session-to-location map so every front-end sharing the install secret receives the same path answer.

- The map is a **cache**, not a permanent log.
- If the listener restarts mid-session, it discovers the existing directory on disk and returns the original location.
- **Standalone Fallback:** Without a listener, the standard `--Convert Location` is used.
- **Static Socket Directory:** The socket directory is pinned to `--Convert Location` and never moves with a session (otherwise workers and managers would look in different locations).

---

## 🧹 About Scheduled Storage Cleanup

The Resource Manager sweeps each configured data location every `--Storage Cleanup Interval` seconds, deleting files older than `--File Deletion Age Threshold`.

- **Shared Cleanups:** The sweep uses the same cleanup function as the standalone `convertCore.php`.
- **Redundancy:** Standalone routines are required if no listener runs, but are redundant when a listener is active.
- **Scope Isolation:** Only shared data locations are swept. Temporary locations live inside the local installation directory and are cleaned by each front-end.
- **Disabling:** Set `--Storage Cleanup Interval` to 0 to disable scheduled sweeps. [12]

---

## 🛡️ About Per-Conversion Resource Ceilings

Enabling `--Enable Per Conversion Limits` runs each conversion inside a transient `systemd` scope with isolated CPU and memory ceilings. This prevents a single heavy file from starving the host.

Every expensive operation runs inside a ceiling, not only format conversion. Anything routed through `sandboxCommand()` is wrapped. Example pipelines:

| Pipeline   | Previously | Now |
| :--------- | :--------- | :-- |
| ClamAV     | Ran raw; signature DB can exceed 1 GB. | Wrapped, type `Scan`. |
| ScanCore   | Ran raw with only PHP `memory_limit`. | Wrapped, type `Scan`. |
| OpenSCAD   | `nice -n 19` but no memory ceiling. | Wrapped, type `Scad`. |

A PHP `memory_limit` is not a system ceiling — it is an internal limit and does not prevent the kernel from being affected. The ceiling wraps the scanner itself (not the shell pipeline) so the actual resource consumer is measured.

Replacing OpenSCAD's `nice -n 19` with an actual ceiling is not
