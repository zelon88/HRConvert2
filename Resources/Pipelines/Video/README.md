# The Video pipeline

The Video pipeline converts files. It accepts 16 input format(s) & produces 13 output format(s).

This file is written by `Documentation/Build/hrconvert2-approvals.py --readme`.
It is rewritten from the pipeline's own declarations & from the dependency manifest,
so it cannot drift from either. Do not edit it by hand.

## What it declares

- **Version** v3.9.6
- **Kind** conversion
- **Family** Video
- **Entry point** convertVideos
- **Subsystem** Audio, Video & Streams
- **Available to** every surface

## Formats

**Accepts** mp4, m4v, mkv, avi, mov, wmv, flv, webm, mpg, mpeg, ogv, 3gp, ts, vob, asf, rm

**Produces** mp4, m4v, mkv, avi, mov, wmv, flv, webm, mpg, mpeg, ogv, 3gp, ts

## Dependencies

- **Bubblewrap** (required, 0.4 or newer, LGPL-2.0). Isolates every conversion from the host & the network.
- **FFMPEG** (optional, 6.1 or newer, LGPL-2.1). Converts audio, video, subtitles & captures streams.

## Approval

`DEPENDENCY_APPROVALS.txt` beside this file is the official record for every
dependency listed above. It holds the approval status, the approval & expiry dates,
& the audit answers. That record is authoritative & this README is a summary of it.

The procedure behind it is `Documentation/ABOUT_DEPENDENCIES.txt`.
