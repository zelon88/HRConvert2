# The OpenSCAD pipeline

The OpenSCAD pipeline converts files. It accepts 1 input format(s) & produces 9 output format(s).

This file is written by `Documentation/Build/hrconvert2-approvals.py --readme`.
It is rewritten from the pipeline's own declarations & from the dependency manifest,
so it cannot drift from either. Do not edit it by hand.

## What it declares

- **Version** v3.9.6
- **Kind** conversion
- **Family** Scad
- **Entry point** convertSCAD
- **Subsystem** SCAD
- **Available to** every surface

## Formats

**Accepts** scad

**Produces** stl, off, amf, 3mf, csg, dxf, svg, png, pdf

## Dependencies

- **Bubblewrap** (required, 0.4 or newer, LGPL-2.0). Isolates every conversion from the host & the network.
- **OpenSCAD** (optional, 2021.01 or newer, GPL-2.0). Renders parametric models.

## Approval

`DEPENDENCY_APPROVALS.txt` beside this file is the official record for every
dependency listed above. It holds the approval status, the approval & expiry dates,
& the audit answers. That record is authoritative & this README is a summary of it.

The procedure behind it is `Documentation/ABOUT_DEPENDENCIES.txt`.
