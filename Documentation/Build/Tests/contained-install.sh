#!/bin/bash
# The HRProprietary Engine's tests. The contained runner for a whole installation. It was HRProtect's until v4.3.1.
#
#   sudo bash Documentation/Build/Tests/contained-install.sh COMMAND [ARGUMENT ...]
#
# It is contained.sh with what an installation needs added to it:
#   - /DATA & /var/www/html are new & in memory, so an application is installed, run & forgotten there.
#     An installation this machine already has is hidden beneath them & cannot be reached;
#   - /var/lib/clamav is new & in memory where this machine has one, so a test's signature is the only one ClamAV knows;
#   - every PHP configuration folder is a copy in memory, so -fp can write its file there & PHP reads it;
#   - /tmp is open to every account, as a real one is, because an application run by another account makes a folder there;
#   - /proc is mounted by the first step inside, & not by bubblewrap. Bubblewrap lays read only folders over the /proc
#     it mounts, & the kernel then refuses the Engine's own sandbox a /proc of its own, so no scanner could run.
#     In here root can therefore write below /proc/sys, which it cannot in contained.sh. Nothing in the tests does.
# Everything else this machine has mounted is bound read only, exactly as in contained.sh, & there is no network.
#
# This script makes nothing on this machine. /DATA & /var/www/html must already be there to be mounted over, & it says
# so & runs nothing where one is not. It removes nothing. It holds no rm, rmdir, unlink or mv.
# The first step inside copies each PHP configuration folder with cp, into memory.
#
# Keep the tree under test & this folder out of /mnt, /tmp, /sys, /run, /DATA & /var/www/html.

set -u

if [ "$#" -lt 1 ]; then
  echo "Usage: sudo bash Documentation/Build/Tests/contained-install.sh COMMAND [ARGUMENT ...]" >&2
  exit 64
fi

if [ "$(id -u)" != "0" ]; then
  echo "contained-install.sh: this must be run as root, & nothing was run. An installation is root's to make." >&2
  exit 64
fi

if ! command -v bwrap >/dev/null 2>&1; then
  echo "contained-install.sh: bubblewrap is not installed, & nothing was run. It is one of the Engine's own dependencies: apt install bubblewrap" >&2
  exit 69
fi

for needed in /DATA /var/www/html; do
  if [ ! -d "$needed" ] || [ -L "$needed" ]; then
    echo "contained-install.sh: $needed is not a folder on this machine, so there is nothing to mount over, & nothing was run." >&2
    echo "An empty one is enough, & nothing is ever written to it: mkdir -p /DATA /var/www/html" >&2
    exit 66
  fi
done

EXTRA=()
if [ -d /var/lib/clamav ] && [ ! -L /var/lib/clamav ]; then EXTRA+=(--tmpfs /var/lib/clamav); fi

# The first step inside. It is handed the command & its arguments, & becomes the command once it has done three things.
INSIDE='mount -t proc proc /proc || { echo "contained-install.sh: /proc could not be mounted inside, & nothing was run." >&2; exit 70; }
chmod 1777 /tmp || exit 70
count=0
for folder in /etc/php/*/*/conf.d /usr/local/etc/php/conf.d; do
  [ -d "$folder" ] || continue
  count=$((count + 1))
  cp -a "$folder" "/tmp/php-configuration.$count" && mount -t tmpfs -o mode=0755 tmpfs "$folder" && cp -a "/tmp/php-configuration.$count/." "$folder/" || { echo "contained-install.sh: $folder could not be copied into memory, & nothing was run." >&2; exit 70; }
done
exec "$@"'

exec bwrap \
  --die-with-parent \
  --unshare-pid \
  --unshare-ipc \
  --unshare-uts \
  --unshare-net \
  --cap-add ALL \
  --ro-bind / / \
  --dev /dev \
  --tmpfs /sys \
  --tmpfs /run \
  --tmpfs /tmp \
  --tmpfs /mnt \
  --dir /mnt/scratch \
  --tmpfs /mnt/other \
  --tmpfs /DATA \
  --tmpfs /var/www/html \
  ${EXTRA[@]+"${EXTRA[@]}"} \
  --setenv HRP_CONTAINED 1 \
  --setenv HRP_INSTALL 1 \
  --setenv HRP_SCRATCH /mnt/scratch \
  --setenv HRP_OTHER /mnt/other \
  -- /bin/bash -c "$INSIDE" contained-install "$@"
