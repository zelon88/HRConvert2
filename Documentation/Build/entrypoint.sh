#!/bin/bash
# / -----------------------------------------------------------------------------------
# / HRConvert2 container entrypoint.
# / Starts the Unoconv listener, then hands the foreground to Apache.
# / Apache must be the foreground process, or the container exits as soon as it starts.
# / LANG is set because LibreOffice inherits it & a C locale mangles non ASCII filenames.
# / -----------------------------------------------------------------------------------
export LANG=C.UTF-8
export LC_ALL=C.UTF-8
export HOME=/DATA/HRConvert2

# / Update the ClamAV signature database if the daemon is present.
freshclam > /dev/null 2>&1 || true

# / Hand the foreground to Apache.
exec apache2-foreground