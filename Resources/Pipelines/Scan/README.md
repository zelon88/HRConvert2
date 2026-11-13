# The Scan pipeline

The Scan pipeline is an operation. It takes a selection of files at once & decides what to do with each one.

This file is written by `Documentation/Build/hrconvert2-approvals.py --readme`.
It is rewritten from the pipeline's own declarations & from the dependency manifest,
so it cannot drift from either. Do not edit it by hand.

## What it declares

- **Version** v4.1.6
- **Kind** operation
- **Family** Scan
- **Entry point** scanSelection
- **Subsystem** Virus Scanning
- **Available to** every surface

## Formats

**Produces** txt

## Dependencies

- **ClamAV** (optional, 0.103 or newer, GPL-2.0). Scans uploads. Only used when virus scanning is enabled.
- **ScanCore** (optional, 1.8 or newer, GPL-3.0). Scans every uploaded file. Written & distributed by this project.

## Approval

`DEPENDENCY_APPROVALS.txt` beside this file is the official record for every
dependency listed above. It holds the approval status, the approval & expiry dates,
& the audit answers. That record is authoritative & this README is a summary of it.

The procedure behind it is `Documentation/ABOUT_DEPENDENCIES.txt`.
