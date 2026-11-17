# The Data File pipeline

The Data File pipeline converts files. It accepts 6 input format(s) & produces 6 output format(s).

This file is written by `Documentation/Build/hrconvert2-approvals.py --readme`.
It is rewritten from the pipeline's own declarations & from the dependency manifest,
so it cannot drift from either. Do not edit it by hand.

## What it declares

- **Version** v4.2.5
- **Kind** conversion
- **Family** Data
- **Entry point** convertDataFiles
- **Subsystem** Structured Data
- **Available to** every surface
- **Offered where** Dasel is installed

## Formats

**Accepts** json, yaml, yml, toml, xml, csv

**Produces** json, yaml, yml, toml, xml, csv

## Dependencies

- **Bubblewrap** (required, 0.4 or newer, LGPL-2.0). Isolates every conversion from the host & the network.
- **Dasel** (optional, 3.11 or newer, MIT). Converts JSON, YAML, TOML, XML & CSV into one another.
- **yq** (optional, 4.50 or newer, MIT). Writes the XML the Data pipeline makes, which Dasel cannot.

## Approval

`DEPENDENCY_APPROVALS.txt` beside this file is the official record for every
dependency listed above. It holds the approval status, the approval & expiry dates,
& the audit answers. That record is authoritative & this README is a summary of it.

The procedure behind it is `Documentation/ABOUT_DEPENDENCIES.txt`.
