**🛠️ HRConvert2: About Resource Rate Limiting - System Architecture, Resource Budgeting, and Core Communications**

Welcome to the official Resource Rate Limiting manual for HRConvert2! This document describes how administrators with CLI access to the HRConvert2 can utilize the built-in features of `convertCore.php` to manage resource utilization, control high traffic environments & maximize uptime.

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

**🧭 About Resource Rate Limiting**

Standalone HRConvert2 has no request rate limiting of its own. A busy server accepts every conversion it is offered and will happily accept more work than the hardware can perform.

Resource rate limiting adds a **budget mechanism** via config.php to place hard limits on resource-intensive operations. It is entirely optional and disabled by default.

*\- WHEN DISABLED (Default Mode)*  
\! HRConvert2 behaves exactly as it always has.  
\+ Nothing is checked, nothing is refused, and no additional process runs.  
\+ Ideal for: Single-user installations or lightly loaded servers.

\+ WHEN ENABLED (Production Mode)  
\! A dedicated listener process manages system health.  
\+ It holds a global budget, grants conversion permissions, tracks active tasks,   
\+ and reclaims resources the moment a job finishes.

---

**📡 About The Listener**

The listener is contained in Resources/coreManager.php and acts as a completely **detachable component**.

## **🔐 Security & Validation**

> * **Decoupled Design**: Validated the same way as a GUI or language pack.  
> * **Lazy Loading**: Only loads after an explicit install secret exists. convertCore.php runs perfectly well without it.  
> * **Strict Versioning**: The component version must match the core required version **EXACTLY**. Mismatches are immediately refused to prevent unsafe function calls, disabling resource awareness. \[1\]

## **👥 The 4 Core Micro-Processes**

One single command spins up **four separate PHP processes**, each with dedicated responsibilities:

\[  
  "1. Core Manager (The Supervisor)",  
  "   • Supervises the other three managers and auto-restarts any that die.",  
  "   • Routes incoming requests to the specific responsible manager.",  
  "   • Holds NO budget, NO registry, and NO session map of its own."  
\]

\[  
  "2. Request Manager (The Gatekeeper)",  
  "   • Owns the only socket a worker process is permitted to address.",  
  "   • Decrypts, validates, and forwards worker requests to the Core Manager.",  
  "   • Instantly refuses any request type a worker is unauthorized to issue."  
\]

\[  
  "3. Resource Manager (The Allocator)",  
  "   • Owns the resource budget, worker registry, and session-to-location map.",  
  "   • Periodically polls the host system to recalculate the available budget.",  
  "   • Determines which data location a specific session uses.",  
  "   • Hunts down and terminates zombie workers that outlive their runtime."  
\]

\[  
  "4. Worker Manager (The Executor)",  
  "   • Completely stateless component that holds no memory of past events.",  
  "   • Destroys processes immediately when instructed by the Core Manager.",  
  "   • The ONLY component allowed to forcefully act on a process without negotiation."  
\]

---

**🧦 About Socket Communication**

Every manager listens on a local **Unix domain socket** located inside a designated Sockets folder within the data directory. \[2\]

> * **Permissions**: Created with secure 0700 permissions, owned exclusively by the web server user.  
> * **Location**: Isolated completely outside the public web root. \[3\]

DIRECTIONAL FLOW: One-Way Traffic Only 🛑  
 Workers  ───\[ Encrypted Messages \]───►  Request Manager Socket

⚠️ **Security Boundary:** There is no address a worker can send to that reaches another worker—whether from a web request, command line, or userspace. This is a deliberate security feature, **not** a limitation to be bypassed.

## **🔒 Message Cryptography & Transport**

> * **Encryption**: AES-256-GCM using a key derived from the install secret and the specific channel used. \[4\]  
> * **Replay Protection**: Every message carries a timestamp and a nonce. Messages older than \--Manager Message Skew are discarded. \[5\]  
> * **Duplex Response**: Replies travel back directly via the connection the sender opened. Workers do not need a listening address to receive answers.  
> * **Performance Batching**: Messages are processed in batches up to \--Manager Message Batch Size per loop cycle, shielding the listener from high connection overhead.  
> * **Network Limit**: Unix domain sockets cannot cross physical hosts. Split topologies (front-end and listener on separate servers) are unsupported, though shared storage is fully compatible. \[6\]

---

**🔑 About Startup Keys**

Every core-to-core startup call is cryptographically keyed against the main install secret. \[7\]

> * **The Token**: An HMAC calculated over the specific purpose authorized and a temporary time bucket (--Startup Key Window seconds).  
> * **Clock Tolerance**: The current and immediately preceding time buckets are accepted to accommodate process launch delays.  
> * **Expiration**: A key becomes completely useless just a few seconds after creation. \[8\]

*\- CRITICAL SECURITY POLICY:*  
*\- THE STARTUP KEY IS NEVER PRINTED AND NEVER WRITTEN TO LOGS.*  
*\- This prevents log scrapers or console captures from stealing active keys.*

> * **User Privilege Isolation**: Standard CLI users hold a unique per-user secret in their home directory. Because it cannot derive a valid core startup key, standard users cannot fake or start a listener that workers will trust.  
> * **Hidden Flags**: The \--start-core-manager and \--start-manager arguments are internal. They are triggered by the \-l argument alongside a valid key. They are rejected without one and are intentionally omitted from the \-h help menu.

## ---

**📊 About The Resource Budget**

System assets are calculated in **arbitrary cost units** and updated every \--Resource Poll Interval seconds based on three system pillars:

## **🧩 The Budget Equation**

> 1. **Base Budget**: Set by \--Total Resource Budget. If set to 0, it defaults to 100 units per CPU core.  
> 2. **Reserve Pool**: A dedicated slice (--Reserve Resource Percentage) that is never handed to conversions. This keeps the server responsive even under maximum load.  
> 3. **Pressure Penalty**: A dynamic throttling value scaled automatically to whichever metric is under higher pressure: **CPU Load Average** or **Memory Usage**.

\[ Idle Server \] ──► Throttles nothing (Maximum performance)  
\[ Loaded Server \] ──► Dynamically rejects new work BEFORE it crashes

## **⏱️ Lifecycle of a Job Request**

**Four operations take a budget: conversion, archiving, OCR, and the user virus scan.** Only conversion did so originally. The other three ran unmetered, which understated the load twice over: they were never held back on a full machine, and because they took nothing they also counted for nothing, so a machine saturated by them still reported itself idle and kept approving conversions on top.

Each declares the same `--Default Conversion Cost` and `--Default Expected Runtime`. They are deliberately **not** weighted against each other, because there are no measurements behind a number claiming an archive costs half of an OCR, and an invented constant in a limiter is worse than an honest uniform one.

Before an operation begins, it must declare its **estimated cost** and **expected runtime**. \[9\]

*\- The Request is REFUSED if:*  
*\- ❌ The runtime exceeds \`--Maximum Expected Runtime\`*  
*\- ❌ The worker count hits \`--Maximum Concurrent Workers\`*  
*\- ❌ The remaining unallocated budget is smaller than the requested job cost*

\+ The Request is APPROVED if:  
\+  The job fits within the safety margins.  
\+  System issues an official Budget Token.  
\+  The worker returns the token upon completion, instantly reclaiming resources.
\+  The return is registered as a shutdown handler the moment the token is issued, so an operation that dies part way through still returns what it borrowed rather than leaving it for the reaper.

A **refused** operation is not an error. Nothing failed and nothing was attempted. The core writes a warning naming the operation and prints the same alert string a refused conversion prints, which carries **no `ERROR!!!` tag**. An interface recognises it by the alert string rather than by the tag.

---

**🔄 About Worker Lifecycle**

The Resource Manager scans its internal registry every \--Worker Reap Interval seconds to clear out stagnation.

## **🧹 The Reaping Process**

A tracked worker is flagged as **stale** and handed to the Worker Manager to be destroyed if:

> * Its active process no longer exists (meaning it crashed without sending a completion message).  
> * It outlives its expected runtime plus the assigned \--Worker Stale Grace Period. \[10\]

💡 Pro-Tip: Keep the grace period generous\! A conversion that is merely slow must   
           not be reaped. A reaped conversion is a lost conversion.

## **⏳ Lifeline Extensions**

A conversion that requires extra time may request an extension.

> * **Limit**: Granted up to \--Maximum Runtime Extensions times.  
> * **Hard Ceiling**: Can never extend beyond the total \--Maximum Expected Runtime.

---

**📂 About Data Locations**

$ConvertLoc is always a valid string that resolves to a usable pathway. It represents the explicit data location **this specific worker** will use for **this explicit conversion** (with or without a listener running).

## **🔀 Additional Data Locations**

The \--Additional Data Locations flag allows you to add further pools, declared as an array containing a path and a strategy type:

| Strategy | Behavior |
| :---- | :---- |
| **roundrobin** | Distributes new sessions across the pool by session identifier. Requires no shared counter; works across detached front-ends. |
| **leastactive** | If *any* location uses this, the entire pool switches strategies to select paths based on the fewest active sessions held. |
| **redundant** | Acts as a strict standby. It takes a session only when every other location becomes completely unusable. |

⚠️ **Sticky Sessions:** A session is assigned a location **once** and keeps it for its entire lifecycle. Load balancing happens exclusively at the session level, never at the individual conversion level. Moving a session mid-lifecycle would leave user files behind. \[11\]

## **🗺️ The Map Cache**

The listener hosts the session-to-location map, guaranteeing every front-end sharing the same install secret receives the identical path answer.

> * This map is a **cache**, not a permanent log.  
> * If a listener restarts mid-session, it discovers the existing directory on disk before distributing anything, safely returning the original location.  
> * **Standalone Fallback**: With no listener running, the standard \--Convert Location is used.  
> * **Static Socket Directory**: The socket directory is permanently pinned to \--Convert Location. It never moves with a session, otherwise workers and managers would look for each other in different places.

---

**🧹 About Scheduled Storage Cleanup**

The Resource Manager sweeps every configured data location every \--Storage Cleanup Interval seconds, using the \--File Deletion Age Threshold to target expired data.

> * **Shared Cleanups**: The sweep triggers the exact same cleanup function used by ordinary standalone routines in convertCore.php.  
> * **Redundancy**: Standalone routines are required when no listener runs, but become fully redundant when a listener is active.  
> * **Scope Isolation**: Only *shared* data locations are swept. Temporary locations live inside the local installation directory, meaning each front-end owns and cleans its own space.  
> * **Disabling**: Set \--Storage Cleanup Interval to 0 to turn off the scheduled sweep entirely. \[12\]

---

**🛡️ About Per-Conversion Resource Ceilings**

Enabling \--Enable Per Conversion Limits runs every conversion inside a transient **systemd scope** carrying an isolated CPU and memory ceiling. This prevents a single heavy file from starving the host system.

**Every expensive operation runs inside a ceiling, not only format conversion.** Anything routed through `sandboxCommand()` has always been wrapped. Three pipelines built their own command and walked around the wrapper with it, and each is among the most expensive things the application does:

| Pipeline | Previously | Now |
| --- | --- | --- |
| ClamAV | Ran raw. A signature database exceeds a gigabyte once loaded. | Wrapped, type `Scan`. |
| ScanCore | Ran raw, carrying a PHP `memory_limit` and nothing else. | Wrapped, type `Scan`. |
| OpenSCAD | Fixed `nice -n 19`, no memory ceiling at all. | Wrapped, type `Scad`. |

A PHP `memory_limit` is **not** a ceiling and does not replace one — it is a budget a child interpreter imposes on itself, honours only while it is behaving, and which never sees the allocations PHP does not count. A cgroup ceiling is imposed from outside and holds whatever the process does.

For the scanners the ceiling wraps the **scanner**, not the shell pipeline it sits in; putting the pipe inside the scope would measure a shell rather than a scanner.

Replacing OpenSCAD's hardcoded `nice -n 19` with the configured ceiling is **not strictly stronger on every host**. Where a scope can be created it is far stronger, because a CPUQuota is enforced against a cgroup instead of requested from the scheduler. Where no scope can be created, niceness is derived from the configured processor share, so the stock `'Scad' => '75,1024'` yields less than a fixed 19 did. Set a smaller processor share for `Scad` to get a larger niceness back.

**A built-in ceiling exists for any type the core knows the general default would break.** `config.php` is accepted at or above a minimum version, so a server can take a newer core and keep the configuration file it already had — meaning a type the core learned about after that file was written is a type the file cannot name. For `Scan` the general 512M default is not merely tight, it is fatal: the kernel kills the scan rather than slowing it, and every virus scan fails from the moment the core is updated. The core carries its own `Scan` ceiling, uses it when `config.php` names none, and warns with the value it used and how to set it yourself. Anything an administrator **does** set always wins.

Resolution order: the listener's scaled table → `--Maximum Per Conversion Resources` → the core's built-in for that type → `--Default Per Conversion Resources`.

## **📊 Configuring Ceilings**

Ceilings are declared via \--Maximum Per Conversion Resources using a comma-separated string containing a processor percentage and a megabyte limit:

"200, 512"  \--\> (200% of ONE processor \= 2 whole cores | 512 MB Memory Ceiling)

> * **Fallback**: Any unlisted conversion type falls back to \--Default Per Conversion Resources.  
> * **Dynamic Scaling**: These limits are **maxima**. With a listener running, they actively scale down against real-time system load and concurrent flights, never dropping below \--Minimum Per Conversion Resources. Without a listener, they apply raw and unchanged.  
> * **The Sandbox Wrapper**: The systemd scope wraps the entire *sandbox* rather than just the internal tool. The ceiling safely covers bubblewrap and any child sub-processes it creates.  
> * **Kernel Reaping**: A conversion hitting its memory ceiling is forcefully terminated by the kernel. It reports as a failed conversion rather than an application memory error—keep your ceilings generous\!

## **🛑 Crucial Security Privilege Warning**

*\- CRITICAL ARCHITECTURAL RULE:*  
*\- THE WEB SERVER USER CANNOT CREATE A SYSTEM SCOPE AND MUST NOT BE GIVEN PERMISSION TO.*

An account holding systemd manage-units privileges can start transient services as root. Granting this to the web account parsing untrusted, uploaded user documents turns an application vulnerability into an immediate **direct route to root execution**.

## **🛠️ The Safe Solution: User Managers**

HRConvert2 utilizes a **user manager** instead. A user manager governs only the specific cgroup subtree systemd delegates to it and cannot spawn units under other accounts.

> * Run the \-fp argument as root once to enable lingering for the web server user and write the required delegation drop-in.  
> * Without this drop-in, processor quotas are silently ignored.

## **📝 Scope Reporting Matrix**

The exact allocation mechanism in use is reported via \-v and logged per job:

> * user 🟢 **User Scope**: Correct, secure configuration.  
> * system ⚠️ **System Scope**: Deliberately misconfigured by an operator. Remove immediately.  
> * none ⚪ **No Scope**: Deprioritized safely with nice but lacks a hard memory ceiling.

*Note: Limits are keyed by conversion type but resolved from the sandbox profile of the tool (LibreOffice, ImageMagick, Tesseract). For FFMPEG (which handles audio, video, and streams), it is approximate and billed at the heaviest configuration rate.*

---

**🩹 About Fallback Behaviour**

FAILS OPEN: Subsystem Reliability Philosophy 🔓  
 Any Subsystem Failure ──► Warning Logged ──► Conversion Proceeds Untouched

Every failure in this subsystem **fails open**. A conversion will proceed normally if the component is missing, if versions mismatch, if no listener is running, if a socket is unreachable, or if a request gets no reply. The event logs a warning, and the job runs. \[13, 14\]

> * Only an explicit, successful communication answer is permitted to refuse a conversion. A broken or lagging listener is never interpreted as a declined budget. \[15\]  
> * **Strict Override**: Flipping \--Require Resource Awareness to true inverts this behavior for strict operators who prefer to reject work rather than process unmonitored files.  
> * **Safety Notice**: Resource ceilings are a courtesy to the host health, not a security control. The sandbox is your security control. Nothing in this resource subsystem is load-bearing for security. \[16\]

---

**💻 About Command-Line Operation**

*Note: Listener commands require root or web server user execution privileges.*

*\# Start the resource listener*  
hrconvert2 \-l  
hrconvert2 \--listen

*\# Stop the listener and all child managers*  
hrconvert2 \-k  
hrconvert2 \--kill

*\# End a specific worker by its budget token or PID*  
hrconvert2 \-k \<worker-id\>

*\# Kill every tracked conversion currently in progress*  
hrconvert2 \--kill-all-workers

*\# Forcefully kill every PHP process owned by the web server user*  
hrconvert2 \--kill-every-worker

*\# Skip the confirmation prompt on broad kill operations*  
hrconvert2 \-y  
hrconvert2 \--yes

*\# Report real-time listener and resource budget status (All users)*  
hrconvert2 \--status

*\# Automatically correct folder and file owners*  
hrconvert2 \-fp  
hrconvert2 \--fix-permissions