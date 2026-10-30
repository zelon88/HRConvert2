# The E-Book pipeline

The E-Book pipeline converts files. It accepts 19 input format(s) & produces 17 output format(s).

This file is written by `Documentation/Build/hrconvert2-approvals.py --readme`.
It is rewritten from the pipeline's own declarations & from the dependency manifest,
so it cannot drift from either. Do not edit it by hand.

## What it declares

- **Version** v3.9.6
- **Kind** conversion
- **Family** Ebook
- **Entry point** convertEbooks
- **Subsystem** Ebooks
- **Available to** every surface

## Formats

**Accepts** epub, mobi, azw, azw3, fb2, lit, lrf, pdb, pml, rb, snb, tcr, txtz, htmlz, cbz, cbr, cbc, chm, recipe

**Produces** epub, mobi, azw3, fb2, lit, lrf, pdb, pml, rb, snb, tcr, txtz, htmlz, txt, rtf, docx, pdf

## Dependencies

- **Bubblewrap** (required, 0.4 or newer, LGPL-2.0). Isolates every conversion from the host & the network.
- **Calibre** (optional, 9.13 or newer, GPL-3.0). Converts between ebook formats.

## Approval

`DEPENDENCY_APPROVALS.txt` beside this file is the official record for every
dependency listed above. It holds the approval status, the approval & expiry dates,
& the audit answers. That record is authoritative & this README is a summary of it.

The procedure behind it is `Documentation/ABOUT_DEPENDENCIES.txt`.
