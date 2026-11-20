# The Image pipeline

The Image pipeline converts files. It accepts 36 input format(s) & produces 30 output format(s).

This file is written by `Documentation/Build/hrconvert2-approvals.py --readme`.
It is rewritten from the pipeline's own declarations & from the dependency manifest,
so it cannot drift from either. Do not edit it by hand.

## What it declares

- **Version** v4.3.0
- **Kind** conversion
- **Family** Image
- **Entry point** convertImages
- **Subsystem** Images
- **Available to** every surface
- **Offered where** ImageMagick is installed

## Formats

**Accepts** jpg, jpeg, jpe, png, gif, bmp, dib, tif, tiff, webp, ico, tga, pcx, ppm, pgm, pbm, pnm, xpm, xbm, dds, psd, pdf, heic, heif, avif, jp2, j2k, jxl, dpx, cin, exr, hdr, cr2, nef, arw, dng

**Produces** jpg, jpeg, jpe, png, gif, bmp, dib, tif, tiff, webp, ico, tga, pcx, ppm, pgm, pbm, pnm, xpm, dds, pdf, eps, avif, heic, heif, jp2, j2k, jxl, cin, hdr, exr

## Dependencies

- **Bubblewrap** (required, 0.4 or newer, LGPL-2.0). Isolates every conversion from the host & the network.
- **ImageMagick** (optional, 7.1 or newer, ImageMagick). Converts raster images & splits pages for OCR.

## Approval

`DEPENDENCY_APPROVALS.txt` beside this file is the official record for every
dependency listed above. It holds the approval status, the approval & expiry dates,
& the audit answers. That record is authoritative & this README is a summary of it.

The procedure behind it is `Documentation/ABOUT_DEPENDENCIES.txt`.
