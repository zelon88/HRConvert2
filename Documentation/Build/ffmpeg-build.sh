#!/bin/bash
# / -----------------------------------------------------------------------------------
# / Copyright Information ...
# / HRConvert2, Copyright on 5/18/2026 by Justin Grimes, www.github.com/zelon88
# /
# / License Information ...
# / This project is protected by the GNU GPLv3 Open-Source license.
# / https://www.gnu.org/licenses/gpl-3.0.html
# /
# / File Information ...
# / v4.0.6.
# / Builds FFMPEG from source by running ffmpeg-build2.sh, which sits beside it, in a folder of
# / its own.
# /
# / Resources/Contract/depends.php names this script as the FFMPEG BuildCommand, so
# / --setup --install-depends is what normally runs it, as root.
# /
# / IT BUILDS OUTSIDE THE INSTALLATION, in a folder only root can open, & removes it however the
# / build ends. ffmpeg-build2.sh downloads & builds every library FFMPEG needs in the folder it
# / is started in, several gigabytes of them, & leaves links & files with more than one name
# / there. Until v4.0.6 that folder was Documentation/Build/ffmpeg-build in the installation, &
# / it stayed there after the build. Root refuses to run an installation holding a link from
# / v4.0.1 on, which is why the v4.0.5 image never answered. The folder is made new under
# / /var/tmp, or under TMPDIR where that is set, because /tmp is often held in memory & this
# / build would fill it.
# /
# / Usage, as root:
# /   bash ffmpeg-build.sh
# / -----------------------------------------------------------------------------------


# / -----------------------------------------------------------------------------------
# / What the build needs before anything is made.
if ! command -v curl > /dev/null 2>&1; then
  echo "Cannot build FFMPEG, because curl is not installed."
  exit 1
fi
SCRIPT_FOLDER="$(dirname "$(readlink -f "${BASH_SOURCE[0]}")")"
if [ ! -f "$SCRIPT_FOLDER/ffmpeg-build2.sh" ]; then
  echo "Cannot build FFMPEG, because $SCRIPT_FOLDER/ffmpeg-build2.sh is missing."
  exit 1
fi
# / -----------------------------------------------------------------------------------


# / -----------------------------------------------------------------------------------
# / The folder it builds in. mktemp makes it new, with a name nobody can guess, & only root
# / can open it.
WORK="$(mktemp -d "${TMPDIR:-/var/tmp}/hrconvert2-ffmpeg.XXXXXXXX")" || { echo "Cannot build FFMPEG, because no folder to build in could be made."; exit 1; }
trap 'rm -rf "$WORK"' EXIT
cd "$WORK" || exit 1
echo "Building FFMPEG in $WORK, which is removed when the build ends."
# / -----------------------------------------------------------------------------------


# / -----------------------------------------------------------------------------------
# / The build. Its exit code is this script's.
bash "$SCRIPT_FOLDER/ffmpeg-build2.sh" --build --enable-gpl-and-non-free
# / -----------------------------------------------------------------------------------
