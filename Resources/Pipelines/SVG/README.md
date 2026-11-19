# The Vector Graphic pipeline

The Vector Graphic pipeline converts files. It accepts 10 input format(s) & produces 11 output format(s).

This file is written by `Documentation/Build/hrconvert2-approvals.py --readme`.
It is rewritten from the pipeline's own declarations & from the dependency manifest,
so it cannot drift from either. Do not edit it by hand.

## What it declares

- **Version** v4.2.8
- **Kind** conversion
- **Family** SVG
- **Entry point** convertSVG
- **Subsystem** SVG
- **Available to** every surface
- **Offered where** Inkscape is installed

## Formats

**Accepts** svg, svgz, ai, cdr, eps, ps, pdf, wmf, emf, vsd

**Produces** svg, svgz, png, pdf, eps, ps, dxf, wmf, emf, tex, hpgl

## Dependencies

- **Bubblewrap** (required, 0.4 or newer, LGPL-2.0). Isolates every conversion from the host & the network.
- **Inkscape** (optional, 1.2 or newer, GPL-3.0). Converts vector graphics.
- **Resvg** (optional, 0.45 or newer, Apache-2.0). Renders SVG to PNG before Inkscape is asked.

## Approval

`DEPENDENCY_APPROVALS.txt` beside this file is the official record for every
dependency listed above. It holds the approval status, the approval & expiry dates,
& the audit answers. That record is authoritative & this README is a summary of it.

The procedure behind it is `Documentation/ABOUT_DEPENDENCIES.txt`.
