# The Identify pipeline

The Identify pipeline is an operation. It takes a selection of files at once & decides what to do with each one.

This file is written by `Documentation/Build/hrconvert2-approvals.py --readme`.
It is rewritten from the pipeline's own declarations & from the dependency manifest,
so it cannot drift from either. Do not edit it by hand.

## What it declares

- **Version** v4.1.2
- **Kind** operation
- **Family** Identify
- **Entry point** identifyFiles
- **Subsystem** Identification
- **Available to** every surface

## Formats

**Produces** txt

## Dependencies

- **7-Zip** (required, 26.02 or newer, LGPL-2.1). Extracts every archive format offered.
- **Bubblewrap** (required, 0.4 or newer, LGPL-2.0). Isolates every conversion from the host & the network.
- **FFprobe** (optional, 6.1 or newer, LGPL-2.1). Reads the duration, the container & the streams of audio & video for Identify.
- **File** (optional, 5.43 or newer, BSD-2-Clause). Tells what a file holds & its character set from its content for Identify.
- **ImageMagick** (optional, 7.1 or newer, ImageMagick). Converts raster images & splits pages for OCR.
- **PDFInfo** (optional, 20.09 or newer, GPL-2.0). Reads the page count, the version & the encryption of a PDF for Identify.

## Approval

`DEPENDENCY_APPROVALS.txt` beside this file is the official record for every
dependency listed above. It holds the approval status, the approval & expiry dates,
& the audit answers. That record is authoritative & this README is a summary of it.

The procedure behind it is `Documentation/ABOUT_DEPENDENCIES.txt`.
