# The Extractor pipeline

The Extractor pipeline is an operation. It takes a selection of files at once & decides what to do with each one.

This file is written by `Documentation/Build/hrconvert2-approvals.py --readme`.
It is rewritten from the pipeline's own declarations & from the dependency manifest,
so it cannot drift from either. Do not edit it by hand.

## What it declares

- **Version** v4.1.8
- **Kind** operation
- **Family** Extract
- **Entry point** extractSelection
- **Subsystem** Archives
- **Available to** every surface

## Formats

**Accepts** zip, 7z, rar, tar, gz, tgz, bz, bz2, tbz, tbz2, xz, txz, iso, vhd, vdi, cbz, cbr


## Dependencies

- **7-Zip** (required, 26.02 or newer, LGPL-2.1). Extracts every archive format offered.
- **Bubblewrap** (required, 0.4 or newer, LGPL-2.0). Isolates every conversion from the host & the network.
- **RAR** (optional, 5.0 or newer, Proprietary). Creates RAR archives. Licence forbids redistribution. On arm64 fetch the arm64 build, not the package.
- **Tar** (required, 1.30 or newer, GPL-3.0). Creates & extracts tar archives.
- **Zip** (required, 3.0 or newer, Info-ZIP). Creates zip archives.

## Approval

`DEPENDENCY_APPROVALS.txt` beside this file is the official record for every
dependency listed above. It holds the approval status, the approval & expiry dates,
& the audit answers. That record is authoritative & this README is a summary of it.

The procedure behind it is `Documentation/ABOUT_DEPENDENCIES.txt`.
