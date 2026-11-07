#!/bin/bash
# / -----------------------------------------------------------------------------------
# / Copyright Information ...
# / HRConvert2, Copyright on 8/14/2026 by Justin Grimes, www.github.com/zelon88
# /
# / License Information ...
# / This project is protected by the GNU GPLv3 Open-Source license.
# / https://www.gnu.org/licenses/gpl-3.0.html
# /
# / Application Information ...
# / This application is designed to provide a web-interface for converting file formats
# / on a server for users of any web browser without authentication.
# /
# / File Information ...
# / v3.8.6.
# / This file is the HRConvert2 container entrypoint. It corrects everything a mounted
# / volume may have arrived with, starts the listener when the configuration asks for one,
# / then hands the foreground to the web server.
# /
# / NOTHING HERE MAY PREVENT THE WEB SERVER FROM STARTING.
# / A user who cannot reach the page cannot report the problem. Every step below reports
# / what it found & continues, so a container that came up degraded still comes up & still
# / says so. The only thing that ends this script early is the web server itself failing
# / its own configuration test, which is reported before it is allowed to happen.
# / set -e is deliberately NOT used. It offers nothing here, because every command is
# / already guarded, & it turns any future unguarded line into a container that exits with
# / no explanation at all.
# /
# / Hardware Requirements ...
# / This application requires at least a Raspberry Pi Model B+ or greater.
# / This application will run on just about any x86 or x64 computer.
# /
# / Dependency Requirements ...
# / This file requires bash, php & Apache, & runs as root in the official container image.
# /
# / <3 Open-Source
# / -----------------------------------------------------------------------------------
CORE=/var/www/html/HRProprietary/HRConvert2/convertCore.php
CONFIG=/var/www/html/HRProprietary/HRConvert2/Resources/config.php
# / The configuration is the installation's from the first -fp on. That -fp installs the release
# / into Application in the data volume & leaves only a loader in the web root, so the web root
# / holds config.php only while no -fp has installed it. See ABOUT_MANAGED_LOCATIONS.txt.
INSTALLED_CONFIG=/DATA/HRConvert2/Application/Resources/config.php

say() {
  # / Everything this script prints is prefixed, so it is distinguishable in docker logs
  # / from the output of the core & of the web server.
  echo "[entrypoint] $*"
}

say "HRConvert2 container starting."

# / The core has to exist before anything else is worth trying.
if [ ! -f "$CORE" ]; then
  say "FAILED. convertCore.php was not found at $CORE."
  say "The image was built wrong. Serving nothing would be less useful than saying so."
  exit 1
fi

# / A mounted volume arrives owned by whoever created it on the host, so permissions,
# / policies & the PHP drop in are corrected on every start. Every step is idempotent.
# / The image carries its release in the web root, & -fp installs it into Application in the
# / data volume & leaves only the loader in the web root, so a volume kept from an older
# / container runs this image's release from its first start.
# / The first start of a container also creates its install secret, inside its own volume,
# / because the image no longer carries one. Every location is given the owner its role in
# / the contract names, so the code root runs here is root's & the web server only reads it.
say "Correcting permissions & policies."
if ! php "$CORE" -fp; then
  say "Permission & policy repair reported problems. Continuing."
fi
if [ -f "$INSTALLED_CONFIG" ]; then
  CONFIG=$INSTALLED_CONFIG
fi

# / Start the resource listener only when the configuration wants one. A container has no
# / systemd, so it is started directly rather than through a service unit.
if [ -f "$CONFIG" ]; then
  # / The path is handed over in the environment rather than interpolated into the code
  # / the interpreter is about to run, so a path containing a quote cannot become PHP.
  if HRC2_CONFIG="$CONFIG" php -r 'include getenv("HRC2_CONFIG"); exit(empty($EnableResourceAwareness) ? 1 : 0);' 2>/dev/null; then
    say "Starting the resource listener."
    if ! su -s /bin/sh www-data -c "php '$CORE' -l"; then
      say "The listener did not start. Conversions will run without resource awareness."
    fi
  else
    say "Resource awareness is disabled in config.php. No listener will be started."
  fi
else
  say "config.php was not found. The core will report why."
fi

# / Report the sandbox before serving anything. A container without SYS_ADMIN cannot build
# / a namespace, & finding that out from a failed conversion is worse than being told now.
php "$CORE" --status 2>/dev/null || true

# / THE WEB SERVER CONFIGURATION IS TESTED BEFORE THE WEB SERVER IS HANDED THE FOREGROUND.
# / apache2-foreground exits immediately on a configuration it cannot parse, which reaches
# / an operator as a container that refuses connections & explains nothing. The core writes
# / an Apache configuration during -fp above & puts back what was there if it fails a config
# / test, but a mounted configuration or an edited image can still be wrong. -fp reloads only a
# / server that is running, so it never starts the one this script hands the foreground to.
# / A failure here is reported in full & the server is still started, because a server that
# / refuses to boot is not safer than one that boots without a hardening rule.
if command -v apache2ctl > /dev/null 2>&1; then
  if ! apache2ctl configtest > /tmp/hrc2-configtest 2>&1; then
    say "WARNING. The Apache configuration did not pass its own test."
    sed 's/^/[entrypoint]   /' /tmp/hrc2-configtest
    say "Starting the web server anyway. It may exit immediately."
  fi
  rm -f /tmp/hrc2-configtest
fi

say "Handing the foreground to: $*"
exec "$@"