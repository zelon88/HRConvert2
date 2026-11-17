# The Email pipeline

The Email pipeline converts files. It accepts 1 input format(s) & produces 2 output format(s).

This file is written by `Documentation/Build/hrconvert2-approvals.py --readme`.
It is rewritten from the pipeline's own declarations & from the dependency manifest,
so it cannot drift from either. Do not edit it by hand.

## What it declares

- **Version** v4.2.5
- **Kind** conversion
- **Family** Email
- **Entry point** convertEmails
- **Subsystem** Email
- **Available to** every surface
- **Offered where** msgconvert is installed

## Formats

**Accepts** msg

**Produces** eml, mbox

## Dependencies

- **Bubblewrap** (required, 0.4 or newer, LGPL-2.0). Isolates every conversion from the host & the network.
- **msgconvert** (optional, 0.921 or newer, GPL-1.0-or-later). Converts Outlook messages to EML & mbox.

## Approval

`DEPENDENCY_APPROVALS.txt` beside this file is the official record for every
dependency listed above. It holds the approval status, the approval & expiry dates,
& the audit answers. That record is authoritative & this README is a summary of it.

The procedure behind it is `Documentation/ABOUT_DEPENDENCIES.txt`.
