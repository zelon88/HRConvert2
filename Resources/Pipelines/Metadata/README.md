# The Metadata Scrubber pipeline

The Metadata Scrubber pipeline is an operation. It takes a selection of files at once & decides what to do with each one.

This file is written by `Documentation/Build/hrconvert2-approvals.py --readme`.
It is rewritten from the pipeline's own declarations & from the dependency manifest,
so it cannot drift from either. Do not edit it by hand.

## What it declares

- **Version** v4.1.8
- **Kind** operation
- **Family** Metadata
- **Entry point** stripMetadata
- **Subsystem** Metadata
- **Available to** every surface

## Formats

**Accepts** jpg, jpeg, png, tif, tiff, gif, pdf, docx, xlsx, pptx, odt, ods, odp, odg, epub, mp3, flac, ogg, opus, wav, aiff, mp4, avi, wmv


## Dependencies

- **Bubblewrap** (required, 0.4 or newer, LGPL-2.0). Isolates every conversion from the host & the network.
- **FFMPEG** (optional, 6.1 or newer, LGPL-2.1). Converts audio, video, subtitles & captures streams.
- **MAT2** (optional, 0.13 or newer, LGPL-3.0). Strips the metadata a file carries for Strip Metadata.

## Approval

`DEPENDENCY_APPROVALS.txt` beside this file is the official record for every
dependency listed above. It holds the approval status, the approval & expiry dates,
& the audit answers. That record is authoritative & this README is a summary of it.

The procedure behind it is `Documentation/ABOUT_DEPENDENCIES.txt`.
