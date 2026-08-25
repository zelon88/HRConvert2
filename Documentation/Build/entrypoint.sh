#!/bin/bash
# / -----------------------------------------------------------------------------------
# / HRConvert2 container entrypoint. v3.7.9.
# / Corrects everything a mounted volume may have arrived with, starts the listener when
# / the configuration asks for one, then hands the foreground to the web server.
# / -----------------------------------------------------------------------------------
set -e
CORE=/var/www/html/HRProprietary/HRConvert2/convertCore.php

# / A mounted volume arrives owned by whoever created it on the host, so permissions,
# / policies & the PHP drop in are corrected on every start. Every step is idempotent.
php "$CORE" -fp || echo "Permission & policy repair reported problems. Continuing."

# / Start the resource listener only when the configuration wants one. A container has no
# / systemd, so it is started directly rather than through a service unit.
if php -r 'include "/var/www/html/HRProprietary/HRConvert2/Resources/config.php"; exit(empty($EnableResourceAwareness) ? 1 : 0);' 2>/dev/null; then
  su -s /bin/sh www-data -c "php $CORE -l" || echo "The listener did not start. Conversions will run without resource awareness."
fi

# / Report the sandbox before serving anything. A container without SYS_ADMIN cannot build
# / a namespace, & finding that out from a failed conversion is worse than being told now.
php "$CORE" --status 2>/dev/null || true

exec "$@"