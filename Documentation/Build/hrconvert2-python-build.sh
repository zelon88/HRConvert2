#!/bin/bash
# / -----------------------------------------------------------------------------------
# / Copyright Information ...
# / HRConvert2, Copyright on 9/8/2026 by Justin Grimes, www.github.com/zelon88
# /
# / License Information ...
# / This project is protected by the GNU GPLv3 Open-Source license.
# / https://www.gnu.org/licenses/gpl-3.0.html
# /
# / File Information ...
# / v3.9.3.
# / Builds a versioned CPython & installs it beside the system one.
# /
# / It lives here rather than inside depends.php because FFMPEG & ImageMagick already build
# / this way & Python was the only source dependency carrying its build inline. Eight
# / hundred characters of shell inside a quoted php string cannot be read, tested or run on
# / its own, & this can.
# /
# / DEPENDENCY_VERSION is supplied by the Dependency Core when the manifest declares a
# / version source. It is the version chosen from what is actually available, inside the
# / window MinimumVersion & MaximumVersion describe.
# / Running this by hand with nothing set builds the fallback below, so the script works
# / outside the application as well as inside it.
# /
# / Usage:
# /   DEPENDENCY_VERSION=3.14.2 bash hrconvert2-python-build.sh
# / -----------------------------------------------------------------------------------

set -e

# / -----------------------------------------------------------------------------------
# / The version to build, & the line it must stay inside.
# / A compiled extension loads under ONE python minor version & under no other, so a build
# / that wandered to 3.15 would leave every compiled module in this application unloadable
# / while reporting a successful install.
# / The guard is here as well as in the manifest because this script can be run by hand.
PYTHON_LINE='3.14'
PYTHON_FALLBACK='3.14.0'
PYTHON_VERSION="${DEPENDENCY_VERSION:-$PYTHON_FALLBACK}"

case "$PYTHON_VERSION" in
  "$PYTHON_LINE".*) ;;
  *)
    echo "Refusing to build $PYTHON_VERSION. This application builds the $PYTHON_LINE line only."
    echo "A compiled extension loads under one python minor version & under no other."
    exit 1 ;;
esac

echo "Building Python $PYTHON_VERSION."
# / -----------------------------------------------------------------------------------


# / -----------------------------------------------------------------------------------
# / What CPython needs to build with every module this application uses.
# / A missing header does not fail the build. It silently produces an interpreter without
# / that module, & the failure arrives later as an ImportError naming ssl or lzma rather
# / than naming a missing package here.
DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
  build-essential wget libssl-dev zlib1g-dev libbz2-dev libreadline-dev \
  libsqlite3-dev libffi-dev liblzma-dev
# / -----------------------------------------------------------------------------------


# / -----------------------------------------------------------------------------------
# / Fetch, build & install.
# /
# / make altinstall is the whole reason this is safe & it is not optional.
# / A plain make install would overwrite /usr/bin/python3 & break the package manager,
# / which on a Debian host breaks apt itself.
# / altinstall writes python3.14 & leaves every unversioned name exactly as it was.
# /
# / --enable-shared builds libpython so a compiled module can link against it.
# / ldconfig then tells the loader about /usr/local/lib, which the sandbox reads from
# / /etc/ld.so.cache rather than searching for itself.
wget -q "https://www.python.org/ftp/python/$PYTHON_VERSION/Python-$PYTHON_VERSION.tgz"
tar -xf "Python-$PYTHON_VERSION.tgz"
cd "Python-$PYTHON_VERSION"
./configure --enable-shared --prefix=/usr/local
make -j"$(nproc)"
make altinstall
ldconfig
cd ..
rm -rf "Python-$PYTHON_VERSION" "Python-$PYTHON_VERSION.tgz"

echo "Python $PYTHON_VERSION installed as python${PYTHON_LINE}."
# / -----------------------------------------------------------------------------------
