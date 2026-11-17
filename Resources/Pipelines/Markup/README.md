# The Markup Document pipeline

The Markup Document pipeline converts files. It accepts 30 input format(s) & produces 29 output format(s).

This file is written by `Documentation/Build/hrconvert2-approvals.py --readme`.
It is rewritten from the pipeline's own declarations & from the dependency manifest,
so it cannot drift from either. Do not edit it by hand.

## What it declares

- **Version** v4.2.5
- **Kind** conversion
- **Family** Markup
- **Entry point** convertMarkup
- **Subsystem** Markup
- **Available to** every surface
- **Offered where** Pandoc is installed

## Formats

**Accepts** md, markdown, rst, adoc, asciidoc, jira, textile, org, mediawiki, dokuwiki, creole, muse, t2t, djot, typ, tex, latex, docx, odt, epub, rtf, ipynb, opml, fb2, dbk, pod, csv, tsv, pptx, xlsx

**Produces** md, markdown, rst, adoc, asciidoc, jira, textile, org, mediawiki, dokuwiki, muse, t2t, djot, typ, tex, latex, docx, odt, epub, rtf, ipynb, opml, fb2, dbk, txt, pdf, pptx, texi, icml

## Dependencies

- **Bubblewrap** (required, 0.4 or newer, LGPL-2.0). Isolates every conversion from the host & the network.
- **Pandoc** (optional, 3.10 or newer, GPL-2.0). Converts between markup & document formats.
- **XeLaTeX** (optional, 2022 or newer, LPPL-1.3c). Typesets LaTeX documents, & the PDFs Pandoc writes, into PDF.

## Approval

`DEPENDENCY_APPROVALS.txt` beside this file is the official record for every
dependency listed above. It holds the approval status, the approval & expiry dates,
& the audit answers. That record is authoritative & this README is a summary of it.

The procedure behind it is `Documentation/ABOUT_DEPENDENCIES.txt`.
