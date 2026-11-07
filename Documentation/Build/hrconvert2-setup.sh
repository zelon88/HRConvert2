#!/bin/bash
# / -----------------------------------------------------------------------------------
# / Copyright Information ...
# / HRConvert2, Copyright on 8/25/2026 by Justin Grimes, www.github.com/zelon88
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
# / v4.0.1.
# / This file is the HRConvert2 bootstrap.
# /
# / This script does ONE job. It makes sure PHP exists, then hands over to convertCore.php.
# / Every decision about installing, configuring, updating or repairing an installation is
# / made in Resources/Engine/Cores/setupCore.php, which is version pinned against the core &
# / is the only place that understands config.php.
# /
# / A configuration utility written in bash would be a second implementation of the same
# / understanding, kept in step by hand. This is not that.
# /
# / Clone the repository, run this as root, get a working installation.
# /
# /   git clone https://github.com/zelon88/HRConvert2.git
# /   sudo bash HRConvert2/Documentation/Build/hrconvert2-setup.sh
# /
# / Usage.
# /   sudo bash hrconvert2-setup.sh                       Complete installation. Prompts once.
# /   sudo bash hrconvert2-setup.sh -y                    Complete installation. No prompts.
# /   sudo bash hrconvert2-setup.sh --config              Configure interactively.
# /   sudo bash hrconvert2-setup.sh --install-deps        Dependencies only.
# /   sudo bash hrconvert2-setup.sh --reinstall-existing  Reinstall in place.
# /
# / Every argument is passed through untouched, so anything convertCore.php accepts works.
# /
# / Hardware Requirements ...
# / This application requires at least a Raspberry Pi Model B+ or greater.
# / This application will run on just about any x86 or x64 computer.
# /
# / Dependency Requirements ...
# / This file requires bash & root. It installs PHP with apt-get when PHP is missing.
# /
# / <3 Open-Source
# / -----------------------------------------------------------------------------------

# / This line is written by convertCore.php -fp, as root, when the script no longer matches the
# / core, & written back when a matching script is in place again.
# / Do not edit it by hand. Reinstall the script instead.
DisabledByCore="FALSE"

SCRIPT_VERSION="v4.0.1"
set -u

# / The core lives two directories above this one when the script is where it ships.
# / There is deliberately no search & no fallback. A script that guessed which installation
# / it was operating on would eventually guess wrong on a machine hosting two of them.
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CORE_PATH="${HRCONVERT2_CORE:-$SCRIPT_DIR/../../convertCore.php}"

if [ "$DisabledByCore" = "TRUE" ]; then
  echo
  echo "This bootstrap script has been disabled by convertCore.php -fp."
  echo "It reports $SCRIPT_VERSION & no longer matches the installed core."
  echo "Reinstall it from the release that matches your core, then try again."
  echo
  exit 1
fi

if [ ! -f "$CORE_PATH" ]; then
  echo
  echo "convertCore.php was not found at:"
  echo "  $CORE_PATH"
  echo
  echo "Run this script from Documentation/Build inside an installation, or set the path."
  echo "  HRCONVERT2_CORE=/path/to/convertCore.php bash $(basename "$0")"
  echo
  exit 1
fi
CORE_PATH="$(cd "$(dirname "$CORE_PATH")" && pwd)/$(basename "$CORE_PATH")"

# / PHP is the only thing this script installs. Everything else is setupCore's problem.
if ! command -v php > /dev/null 2>&1; then
  echo
  echo "PHP is not installed. Installing it is the only thing this script does by itself."
  if [ "$(id -u)" -ne 0 ]; then
    echo "Installing PHP requires root. Re-run with sudo."
    echo
    exit 1
  fi
  if command -v apt-get > /dev/null 2>&1; then
    DEBIAN_FRONTEND=noninteractive apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y php-cli php-zip php-mbstring
  elif command -v dnf > /dev/null 2>&1; then
    dnf install -y php-cli php-zip
  elif command -v yum > /dev/null 2>&1; then
    yum install -y php-cli php-zip
  elif command -v apk > /dev/null 2>&1; then
    apk add --no-cache php-cli php-zip
  else
    echo "No supported package manager was found. Install PHP by hand & run this again."
    echo
    exit 1
  fi
fi

if ! command -v php > /dev/null 2>&1; then
  echo
  echo "PHP still is not available after the install attempt. Nothing further was done."
  echo
  exit 1
fi

# / Everything below this line is the core's decision, not this script's.
# / With no argument the intent is a complete installation, which is why somebody runs a
# / bootstrap script in the first place.
if [ "$#" -eq 0 ]; then
  set -- --setup --install-complete
fi

# / A bare -y means an unattended complete installation rather than a lone confirmation.
if [ "$#" -eq 1 ] && { [ "$1" = "-y" ] || [ "$1" = "--yes" ]; }; then
  set -- --setup --install-complete -y
fi

if [ "$(id -u)" -ne 0 ]; then
  echo
  echo "Note. Most setup arguments require root & convertCore.php will say so."
  echo
fi

exec php "$CORE_PATH" "$@"
