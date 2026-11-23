#!/bin/bash
# The HRProprietary Engine's tests. The contained runner. It was HRProtect's until v4.3.1.
#
#   sudo bash Documentation/Build/Tests/contained.sh COMMAND [ARGUMENT ...]
#   sudo bash Documentation/Build/Tests/contained.sh php Documentation/Build/Tests/test_events-cli.php
#
# Run it from the root of the application under test, which is the tree a suite tests.
#
# It runs COMMAND inside bubblewrap, the sandbox the Engine itself depends on, with:
#   - everything this machine has mounted bound read only, so nothing on it can be changed or removed;
#   - an empty /sys & /run, a new /dev, /proc & /tmp, & a new /mnt that holds /mnt/scratch;
#   - a second new filesystem at /mnt/other, for what a suite must do across two filesystems;
#   - process numbers of its own, so nothing outside can be signalled, & no network.
# Everything new is memory, & it is gone when COMMAND ends.
# COMMAND still runs as root, because what the suites test is root's to do.
#
# This script removes nothing. It holds no rm, rmdir, unlink or mv, & it leaves nothing behind.
# It makes no folder on this machine either: /sys, /run, /tmp & /mnt are there already.
#
# A suite does not take this script's word for any of it. Each one looks at where it is before it
# does anything, & stops where the filesystem root, or anything else that was here, can be written.
#
# Keep the tree under test & this folder out of /mnt, /tmp, /sys & /run. COMMAND cannot see what those held.

set -u

if [ "$#" -lt 1 ]; then
  echo "Usage: sudo bash Documentation/Build/Tests/contained.sh COMMAND [ARGUMENT ...]" >&2
  exit 64
fi

if [ "$(id -u)" != "0" ]; then
  echo "contained.sh: this must be run as root, & nothing was run. The suites test what root does." >&2
  exit 64
fi

if ! command -v bwrap >/dev/null 2>&1; then
  echo "contained.sh: bubblewrap is not installed, & nothing was run. It is one of the Engine's own dependencies: apt install bubblewrap" >&2
  exit 69
fi

exec bwrap \
  --die-with-parent \
  --unshare-pid \
  --unshare-ipc \
  --unshare-uts \
  --unshare-net \
  --cap-add ALL \
  --ro-bind / / \
  --dev /dev \
  --proc /proc \
  --tmpfs /sys \
  --tmpfs /run \
  --tmpfs /tmp \
  --tmpfs /mnt \
  --dir /mnt/scratch \
  --tmpfs /mnt/other \
  --setenv HRP_CONTAINED 1 \
  --setenv HRP_SCRATCH /mnt/scratch \
  --setenv HRP_OTHER /mnt/other \
  -- "$@"
