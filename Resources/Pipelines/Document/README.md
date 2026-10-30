# The Document pipeline

The Document pipeline converts files. It accepts 30 input format(s) & produces 18 output format(s).

This file is written by `Documentation/Build/hrconvert2-approvals.py --readme`.
It is rewritten from the pipeline's own declarations & from the dependency manifest,
so it cannot drift from either. Do not edit it by hand.

## What it declares

- **Version** v3.8.8
- **Kind** conversion
- **Family** Document
- **Entry point** convertDocuments
- **Subsystem** Documents
- **Available to** every surface

## Formats

**Accepts** doc, docx, docm, dot, dotx, odt, ott, fodt, rtf, txt, html, htm, xml, wpd, abw, pages, xls, xlsx, xlsm, ods, ots, fods, csv, ppt, pptx, odp, otp, fodp, xps, oxps

**Produces** pdf, doc, docx, odt, ott, fodt, rtf, txt, html, xml, epub, xls, xlsx, ods, csv, ppt, pptx, odp

## Dependencies

- **Bubblewrap** (required, 0.4 or newer, LGPL-2.0). Isolates every conversion from the host & the network.
- **LibreOffice** (optional, 7.0 or newer, MPL-2.0). Converts documents, spreadsheets & presentations.
- **PHP Zip** (required, PHP-3.01). Opens OOXML documents so external references can be stripped before LibreOffice sees them. Without it the sandbox is the only protection.

## Approval

`DEPENDENCY_APPROVALS.txt` beside this file is the official record for every
dependency listed above. It holds the approval status, the approval & expiry dates,
& the audit answers. That record is authoritative & this README is a summary of it.

The procedure behind it is `Documentation/ABOUT_DEPENDENCIES.txt`.
