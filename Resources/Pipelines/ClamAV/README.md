# The ClamAV pipeline

The ClamAV pipeline examines a file rather than converting it. It reports what it finds & converts nothing.

This file is written by `Documentation/Build/hrconvert2-approvals.py --readme`.
It is rewritten from the pipeline's own declarations & from the dependency manifest,
so it cannot drift from either. Do not edit it by hand.

## What it declares

- **Version** v3.9.5
- **Kind** scanner
- **Family** ClamAV
- **Entry point** scanWithClamAV
- **Subsystem** Security
- **Available to** cli, web, gui

## Dependencies

- **Bubblewrap** (required, 0.4 or newer, LGPL-2.0). Isolates every conversion from the host & the network.
- **ClamAV** (optional, 0.103 or newer, GPL-2.0). Scans uploads. Only used when virus scanning is enabled.

## Approval

`DEPENDENCY_APPROVALS.txt` beside this file is the official record for every
dependency listed above. It holds the approval status, the approval & expiry dates,
& the audit answers. That record is authoritative & this README is a summary of it.

The procedure behind it is `Documentation/ABOUT_DEPENDENCIES.txt`.
