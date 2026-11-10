#!/bin/bash
# / -----------------------------------------------------------------------------------
# / Copyright Information ...
# / HRConvert2, Copyright on 11/9/2026 by Justin Grimes, www.github.com/zelon88
# /
# / License Information ...
# / This project is protected by the GNU GPLv3 Open-Source license.
# / https://www.gnu.org/licenses/gpl-3.0.html
# /
# / File Information ...
# / v4.0.6.
# / Builds 7-Zip from the source 7-Zip publishes & installs it beneath one prefix.
# /
# / Resources/Contract/depends.php names this script as the 7-Zip Fallback. Dependency Core runs
# / it where the package manager's 7-Zip is missing or older than the floor the manifest sets, as
# / Ubuntu 24.04's 23.01 is, & hands it three values.
# /   DEPENDENCY_PREFIX   The folder to install into. The program goes to bin/7z beneath it.
# /   DEPENDENCY_VERSION  The release to build, such as 26.06.
# /   DEPENDENCY_SHA256   The SHA-256 of that release's source archive.
# / Nothing is built without all three, & a source archive whose checksum differs is never
# / unpacked. Dependency Core asks what was built for its version before it is put in service.
# / A host that cannot reach GitHub or 7-zip.org may be handed the source archive instead, by its
# / absolute path, as DEPENDENCY_SOURCE. It is checked exactly as a download is.
# /
# / The program 7-Zip's makefile builds is named 7zz. It is installed as 7z, which is the name the
# / manifest gives & the name every part of the application asks for. It reads & writes every
# / format 7zz does, RAR included, & loads nothing from beside itself.
# / It is built without assembler, because the assembler 7-Zip's x86 code needs is packaged by
# / neither Debian nor Ubuntu, & the C build runs on every architecture this application does.
# /
# / Usage, as root:
# /   DEPENDENCY_PREFIX=/opt/7zip DEPENDENCY_VERSION=26.06 DEPENDENCY_SHA256=<checksum> bash build-7zip.sh
# / Through the application, with the archive at hand:
# /   sudo DEPENDENCY_SOURCE=/root/7z2606-src.tar.xz php convertCore.php --setup --install-depends --subsystem=Archives
# / -----------------------------------------------------------------------------------

set -euo pipefail

# / -----------------------------------------------------------------------------------
# / What was asked for. Each value is checked for the shape it must have before anything is
# / fetched, because each ends up in a path, an address or a comparison.
PREFIX="${DEPENDENCY_PREFIX:-}"
VERSION="${DEPENDENCY_VERSION:-}"
CHECKSUM="${DEPENDENCY_SHA256:-}"
SOURCE="${DEPENDENCY_SOURCE:-}"
if [[ ! "$PREFIX" =~ ^/[A-Za-z0-9._/-]+$ ]] || [[ "$PREFIX" == *..* ]]; then
  echo "Refusing to build. DEPENDENCY_PREFIX must be an absolute path."
  exit 1
fi
if [[ ! "$VERSION" =~ ^[0-9]+\.[0-9]+$ ]]; then
  echo "Refusing to build. DEPENDENCY_VERSION must name a 7-Zip release, such as 26.06."
  exit 1
fi
if [[ ! "$CHECKSUM" =~ ^[0-9a-f]{64}$ ]]; then
  echo "Refusing to build. DEPENDENCY_SHA256 must be the SHA-256 of the release's source archive."
  exit 1
fi
if [ -n "$SOURCE" ] && { [[ "$SOURCE" != /* ]] || [ ! -f "$SOURCE" ]; }; then
  echo "Refusing to build. DEPENDENCY_SOURCE must name a source archive by its absolute path."
  exit 1
fi
# / 7-Zip names a release's files by its version without the dot, so 26.06 is 7z2606.
ARCHIVE_NAME="7z${VERSION//./}-src.tar.xz"
echo "Building 7-Zip $VERSION."
# / -----------------------------------------------------------------------------------


# / -----------------------------------------------------------------------------------
# / What the build needs. A compiler, make & xz are installed where the package manager can, &
# / left installed, as every build this application runs leaves what it installs. curl is needed
# / only to fetch, so a host handing the archive over needs none.
if ! command -v g++ > /dev/null 2>&1 || ! command -v make > /dev/null 2>&1 || ! command -v xz > /dev/null 2>&1; then
  if command -v apt-get > /dev/null 2>&1; then
    DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends build-essential xz-utils
    echo "build-essential & xz-utils were installed to build 7-Zip & are left installed."
  fi
fi
for TOOL in tar xz g++ make sha256sum install; do
  if ! command -v "$TOOL" > /dev/null 2>&1; then
    echo "Cannot build 7-Zip, because $TOOL is not installed."
    exit 1
  fi
done
# / The work folder is new, under /var/tmp or TMPDIR, only root can open it, & it is removed
# / however the script ends.
WORK="$(mktemp -d "${TMPDIR:-/var/tmp}/hrconvert2-7zip.XXXXXXXX")"
trap 'rm -rf "$WORK"' EXIT
# / -----------------------------------------------------------------------------------


# / -----------------------------------------------------------------------------------
# / Fetch & verify.
# / An archive handed over is tried first. 7-Zip publishes each release on GitHub & on 7-zip.org,
# / & either may be out of reach from a given host, so both are tried after it. Each is copied or
# / downloaded into the work folder & checked there, so what is unpacked is what was checked, & one
# / whose checksum differs is thrown away & the next one tried. Only HTTPS is accepted, for the
# / request & for every redirect it follows.
VERIFIED=0
for ORIGIN in ${SOURCE:+"$SOURCE"} "https://github.com/ip7z/7zip/releases/download/${VERSION}/${ARCHIVE_NAME}" "https://www.7-zip.org/a/${ARCHIVE_NAME}"; do
  rm -f "$WORK/$ARCHIVE_NAME"
  if [[ "$ORIGIN" == /* ]]; then
    cp -- "$ORIGIN" "$WORK/$ARCHIVE_NAME" || { echo "Could not read $ORIGIN."; continue; }
  elif ! command -v curl > /dev/null 2>&1; then
    echo "Could not fetch $ARCHIVE_NAME from $ORIGIN, because curl is not installed."
    continue
  elif ! curl --proto '=https' --proto-redir '=https' --tlsv1.2 -fsSL --retry 5 --retry-delay 5 --connect-timeout 20 -o "$WORK/$ARCHIVE_NAME" "$ORIGIN"; then
    echo "Could not fetch $ARCHIVE_NAME from $ORIGIN."
    continue
  fi
  if echo "$CHECKSUM  $WORK/$ARCHIVE_NAME" | sha256sum -c --status -; then
    VERIFIED=1
    echo "Took $ARCHIVE_NAME from $ORIGIN, & its checksum matches."
    break
  fi
  echo "The archive from $ORIGIN does not match the checksum & was discarded."
done
if [ "$VERIFIED" -ne 1 ]; then
  echo "No source archive matching the checksum could be had, so nothing was built."
  exit 1
fi
# / -----------------------------------------------------------------------------------


# / -----------------------------------------------------------------------------------
# / Unpack, build & install.
# / cmpl_gcc.mak is the makefile 7-Zip documents for GCC without assembler, & Alone2 is the
# / bundle that builds the standalone program.
mkdir "$WORK/src"
tar -xJf "$WORK/$ARCHIVE_NAME" -C "$WORK/src" --no-same-owner --no-same-permissions
make -C "$WORK/src/CPP/7zip/Bundles/Alone2" -f ../../cmpl_gcc.mak -j"$(nproc)"
# / The prefix is made only where Dependency Core has not made it already, so a folder it made
# / private stays private until what was built has answered for itself.
if [ ! -d "$PREFIX" ]; then install -d -m 0755 "$PREFIX"; fi
install -d -m 0755 "$PREFIX/bin" "$PREFIX/share/doc/7zip"
install -m 0755 "$WORK/src/CPP/7zip/Bundles/Alone2/b/g/7zz" "$PREFIX/bin/7z"
# / The licence travels with the program, as the LGPL & the unRAR licence both ask.
install -m 0644 "$WORK/src/DOC/License.txt" "$WORK/src/DOC/copying.txt" "$WORK/src/DOC/unRarLicense.txt" "$PREFIX/share/doc/7zip/"
# / -----------------------------------------------------------------------------------


# / -----------------------------------------------------------------------------------
# / What was installed must say it is the release that was asked for.
"$PREFIX/bin/7z" i > "$WORK/info.txt" 2>&1 || true
if ! grep -qF "7-Zip (z) $VERSION (" "$WORK/info.txt"; then
  echo "The program built does not report 7-Zip $VERSION."
  exit 1
fi
echo "7-Zip $VERSION installed at $PREFIX/bin/7z."
# / -----------------------------------------------------------------------------------
