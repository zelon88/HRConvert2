# The Drawing pipeline

The Drawing pipeline converts files. It accepts 7 input format(s) & produces 18 output format(s).

This file is written by `Documentation/Build/hrconvert2-approvals.py --readme`.
It is rewritten from the pipeline's own declarations & from the dependency manifest,
so it cannot drift from either. Do not edit it by hand.

## What it declares

- **Version** v3.9.6
- **Kind** conversion
- **Family** Drawing
- **Entry point** convertDrawings
- **Subsystem** Drawings
- **Available to** every surface

## Formats

**Accepts** dia, dxf, fig, vdx, wpg, shape, cgm

**Produces** dia, dxf, fig, vdx, wpg, shape, cgm, png, jpg, bmp, tif, eps, pdf, svg, wmf, emf, plt, tex

## Dependencies

- **Bubblewrap** (required, 0.4 or newer, LGPL-2.0). Isolates every conversion from the host & the network.
- **Dia** (optional, 0.97 or newer, GPL-2.0). Converts diagrams.

## Approval

`DEPENDENCY_APPROVALS.txt` beside this file is the official record for every
dependency listed above. It holds the approval status, the approval & expiry dates,
& the audit answers. That record is authoritative & this README is a summary of it.

The procedure behind it is `Documentation/ABOUT_DEPENDENCIES.txt`.
