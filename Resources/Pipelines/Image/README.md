# The Image pipeline

The Image pipeline converts files. It accepts 32 input format(s) & produces 22 output format(s).

This file is written by `Documentation/Build/hrconvert2-approvals.py --readme`.
It is rewritten from the pipeline's own declarations & from the dependency manifest,
so it cannot drift from either. Do not edit it by hand.

## What it declares

- **Version** v3.9.6
- **Kind** conversion
- **Family** Image
- **Entry point** convertImages
- **Subsystem** Images
- **Available to** every surface

## Formats

**Accepts** jpg, jpeg, png, gif, bmp, tif, tiff, webp, ico, tga, pcx, ppm, pgm, pbm, pnm, xpm, xbm, dds, psd, svg, eps, pdf, heic, avif, jp2, dpx, exr, hdr, cr2, nef, arw, dng

**Produces** jpg, jpeg, png, gif, bmp, tif, tiff, webp, ico, tga, pcx, ppm, pgm, pbm, pnm, xpm, dds, pdf, eps, avif, jp2, hdr

## Dependencies

- **Bubblewrap** (required, 0.4 or newer, LGPL-2.0). Isolates every conversion from the host & the network.
- **ImageMagick** (optional, 7.1 or newer, ImageMagick). Converts raster images & splits pages for OCR.

## Approval

`DEPENDENCY_APPROVALS.txt` beside this file is the official record for every
dependency listed above. It holds the approval status, the approval & expiry dates,
& the audit answers. That record is authoritative & this README is a summary of it.

The procedure behind it is `Documentation/ABOUT_DEPENDENCIES.txt`.
