# The Graph pipeline

The Graph pipeline converts files. It accepts 1 input format(s) & produces 8 output format(s).

This file is written by `Documentation/Build/hrconvert2-approvals.py --readme`.
It is rewritten from the pipeline's own declarations & from the dependency manifest,
so it cannot drift from either. Do not edit it by hand.

## What it declares

- **Version** v4.2.6
- **Kind** conversion
- **Family** Graph
- **Entry point** convertGraph
- **Subsystem** Graphs
- **Available to** every surface
- **Offered where** Graphviz is installed

## Formats

**Accepts** gv

**Produces** svg, png, pdf, jpg, gif, webp, eps, ps

## Dependencies

- **Bubblewrap** (required, 0.4 or newer, LGPL-2.0). Isolates every conversion from the host & the network.
- **Graphviz** (optional, 2.42 or newer, EPL-1.0). Draws graphs written in the DOT language as pictures & documents.

## Approval

`DEPENDENCY_APPROVALS.txt` beside this file is the official record for every
dependency listed above. It holds the approval status, the approval & expiry dates,
& the audit answers. That record is authoritative & this README is a summary of it.

The procedure behind it is `Documentation/ABOUT_DEPENDENCIES.txt`.
