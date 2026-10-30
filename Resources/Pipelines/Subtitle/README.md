# The Subtitle pipeline

The Subtitle pipeline converts files. It accepts 14 input format(s) & produces 8 output format(s).

This file is written by `Documentation/Build/hrconvert2-approvals.py --readme`.
It is rewritten from the pipeline's own declarations & from the dependency manifest,
so it cannot drift from either. Do not edit it by hand.

## What it declares

- **Version** v3.9.6
- **Kind** conversion
- **Family** Subtitle
- **Entry point** convertSubtitles
- **Subsystem** Audio, Video & Streams
- **Available to** every surface

## Formats

**Accepts** srt, ass, ssa, vtt, sub, sbv, smi, ttml, stl, jacosub, mpl2, pjs, realtext, subviewer

**Produces** srt, ass, ssa, vtt, sub, jacosub, mpl2, ttml

## Dependencies

- **Bubblewrap** (required, 0.4 or newer, LGPL-2.0). Isolates every conversion from the host & the network.
- **FFMPEG** (optional, 6.1 or newer, LGPL-2.1). Converts audio, video, subtitles & captures streams.

## Approval

`DEPENDENCY_APPROVALS.txt` beside this file is the official record for every
dependency listed above. It holds the approval status, the approval & expiry dates,
& the audit answers. That record is authoritative & this README is a summary of it.

The procedure behind it is `Documentation/ABOUT_DEPENDENCIES.txt`.
