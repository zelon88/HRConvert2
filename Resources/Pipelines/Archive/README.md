# The Archive pipeline

The Archive pipeline converts files. It accepts 21 input format(s) & produces 9 output format(s).

This file is written by `Documentation/Build/hrconvert2-approvals.py --readme`.
It is rewritten from the pipeline's own declarations & from the dependency manifest,
so it cannot drift from either. Do not edit it by hand.

## What it declares

- **Version** v3.9.7
- **Kind** conversion
- **Family** Archive
- **Entry point** convertArchives
- **Subsystem** Archives
- **Available to** every surface

## Formats

**Accepts** zip, 7z, tar, gz, tgz, bz2, tbz, xz, txz, rar, iso, cab, arj, lzh, lzma, z, cpio, deb, rpm, wim, dmg

**Produces** zip, 7z, tar, gz, tgz, bz2, xz, iso, wim

## Dependencies

- **7-Zip** (required, 23.01 or newer, LGPL-2.1). Extracts every archive format offered.
- **Bubblewrap** (required, 0.4 or newer, LGPL-2.0). Isolates every conversion from the host & the network.
- **Genisoimage** (optional, 1.1 or newer, GPL-2.0). Builds ISO images from an archive.
- **RAR** (optional, 5.0 or newer, Proprietary). Creates RAR archives. Licence forbids redistribution. On arm64 fetch the arm64 build, not the package.
- **Syslinux Utils** (optional, 0.12 or newer, GPL-2.0). Makes an ISO image bootable.
- **Tar** (required, 1.30 or newer, GPL-3.0). Creates & extracts tar archives.
- **Zip** (required, 3.0 or newer, Info-ZIP). Creates zip archives.

## Approval

`DEPENDENCY_APPROVALS.txt` beside this file is the official record for every
dependency listed above. It holds the approval status, the approval & expiry dates,
& the audit answers. That record is authoritative & this README is a summary of it.

The procedure behind it is `Documentation/ABOUT_DEPENDENCIES.txt`.
