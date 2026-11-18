# The Traced Image pipeline

The Traced Image pipeline converts files. It accepts 12 input format(s) & produces 5 output format(s).

This file is written by `Documentation/Build/hrconvert2-approvals.py --readme`.
It is rewritten from the pipeline's own declarations & from the dependency manifest,
so it cannot drift from either. Do not edit it by hand.

## What it declares

- **Version** v4.2.7
- **Kind** conversion
- **Family** Trace
- **Entry point** traceImages
- **Subsystem** Tracing
- **Available to** every surface
- **Offered where** Potrace is installed

## Formats

**Accepts** bmp, pbm, pgm, ppm, pnm, png, jpg, jpeg, gif, tif, tiff, webp

**Produces** svg, dxf, pdf, eps, ps

## Dependencies

- **Bubblewrap** (required, 0.4 or newer, LGPL-2.0). Isolates every conversion from the host & the network.
- **ImageMagick** (optional, 7.1 or newer, ImageMagick). Converts raster images & splits pages for OCR.
- **Potrace** (optional, 1.16 or newer, GPL-2.0). Traces bitmaps into SVG, DXF, PDF, EPS & PostScript outlines.

## Approval

`DEPENDENCY_APPROVALS.txt` beside this file is the official record for every
dependency listed above. It holds the approval status, the approval & expiry dates,
& the audit answers. That record is authoritative & this README is a summary of it.

The procedure behind it is `Documentation/ABOUT_DEPENDENCIES.txt`.
