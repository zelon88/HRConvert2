# The File Operations pipeline

The File Operations pipeline performs operations that do not depend on what is inside a file.

This file is written by `Documentation/Build/hrconvert2-approvals.py --readme`.
It is rewritten from the pipeline's own declarations & from the dependency manifest,
so it cannot drift from either. Do not edit it by hand.

## What it declares

- **Version** v3.9.3
- **Kind** file
- **Family** Files
- **Entry point** operateOnFiles
- **Subsystem** Core
- **Available to** cli, web, gui

## Dependencies

This pipeline uses no dependency outside the application itself.

## Approval

`DEPENDENCY_APPROVALS.txt` beside this file is the official record for every
dependency listed above. It holds the approval status, the approval & expiry dates,
& the audit answers. That record is authoritative & this README is a summary of it.

The procedure behind it is `Documentation/ABOUT_DEPENDENCIES.txt`.
