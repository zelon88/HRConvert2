#!/bin/bash
# / -----------------------------------------------------------------------------------
# / Copyright Information ...
# / HRConvert2, Copyright on 11/16/2026 by Justin Grimes, www.github.com/zelon88
# /
# / License Information ...
# / This project is protected by the GNU GPLv3 Open-Source license.
# / https://www.gnu.org/licenses/gpl-3.0.html
# /
# / File Information ...
# / v4.2.5.
# / Fetches one program a project publishes as a release binary, checks it & installs it beneath one prefix.
# /
# / Resources/Contract/depends.php names this script as the Fallback of every entry whose own copy is the
# / project's own release binary rather than a build from source. Those are Dasel, yq, Pandoc & Resvg.
# / Dependency Core runs it where the package manager's copy is missing or outside the window the entry
# / declares, & hands it three values, exactly as it hands them to build-7zip.sh.
# /   DEPENDENCY_PREFIX   The folder to install into. The program goes to bin/<program> beneath it.
# /   DEPENDENCY_VERSION  The release to fetch, such as 3.11.2.
# /   DEPENDENCY_SHA256   The SHA-256 of that release's file for this architecture.
# / The entry gives the program's name, the address of the file with {version} where the version goes,
# / & the path of the program inside the file when the file is a tar.gz archive rather than the program.
# / Nothing is fetched without all of them, & a file whose checksum differs is never unpacked or installed.
# / Dependency Core asks what was installed for its version before it is put in service.
# / A host that cannot reach GitHub may be handed the file instead, by its absolute path, as DEPENDENCY_SOURCE.
# / It is checked exactly as a download is.
# /
# / Usage, as root:
# /   DEPENDENCY_PREFIX=/opt/dasel DEPENDENCY_VERSION=3.11.2 DEPENDENCY_SHA256=<checksum> \
# /     bash hrconvert2-release-fetch.sh dasel 'https://github.com/TomWright/dasel/releases/download/v{version}/dasel_linux_amd64'
# /   DEPENDENCY_PREFIX=/opt/pandoc DEPENDENCY_VERSION=3.10.1 DEPENDENCY_SHA256=<checksum> \
# /     bash hrconvert2-release-fetch.sh pandoc 'https://github.com/jgm/pandoc/releases/download/{version}/pandoc-{version}-linux-amd64.tar.gz' 'pandoc-{version}/bin/pandoc'
# / Through the application, with the file at hand:
# /   sudo DEPENDENCY_SOURCE=/root/dasel_linux_amd64 php convertCore.php --setup --install-depends --subsystem="Structured Data"
# / -----------------------------------------------------------------------------------

set -euo pipefail

# / -----------------------------------------------------------------------------------
# / What was asked for. Each value is checked for the shape it must have before anything is
# / fetched, because each ends up in a path, an address or a comparison.
PROGRAM="${1:-}"
ADDRESS_TEMPLATE="${2:-}"
MEMBER_TEMPLATE="${3:-}"
PREFIX="${DEPENDENCY_PREFIX:-}"
VERSION="${DEPENDENCY_VERSION:-}"
CHECKSUM="${DEPENDENCY_SHA256:-}"
SOURCE="${DEPENDENCY_SOURCE:-}"
if [[ ! "$PROGRAM" =~ ^[A-Za-z0-9][A-Za-z0-9._+-]*$ ]]; then
  echo "Refusing to fetch. The first argument must name the program, such as dasel."
  exit 1
fi
if [[ ! "$ADDRESS_TEMPLATE" =~ ^https://[A-Za-z0-9./_{}+-]+$ ]] || [[ "$ADDRESS_TEMPLATE" == *..* ]]; then
  echo "Refusing to fetch. The second argument must be an https address."
  exit 1
fi
if [ -n "$MEMBER_TEMPLATE" ] && { [[ ! "$MEMBER_TEMPLATE" =~ ^[A-Za-z0-9._{}+/-]+$ ]] || [[ "$MEMBER_TEMPLATE" == *..* ]] || [[ "$MEMBER_TEMPLATE" == /* ]]; }; then
  echo "Refusing to fetch. The third argument must be a relative path inside the archive."
  exit 1
fi
if [[ ! "$PREFIX" =~ ^/[A-Za-z0-9._/-]+$ ]] || [[ "$PREFIX" == *..* ]]; then
  echo "Refusing to fetch. DEPENDENCY_PREFIX must be an absolute path."
  exit 1
fi
if [[ ! "$VERSION" =~ ^[0-9]+(\.[0-9]+){1,3}$ ]]; then
  echo "Refusing to fetch. DEPENDENCY_VERSION must name a release, such as 3.11.2."
  exit 1
fi
if [[ ! "$CHECKSUM" =~ ^[0-9a-f]{64}$ ]]; then
  echo "Refusing to fetch. DEPENDENCY_SHA256 must be the SHA-256 of the release's file."
  exit 1
fi
if [ -n "$SOURCE" ] && { [[ "$SOURCE" != /* ]] || [ ! -f "$SOURCE" ]; }; then
  echo "Refusing to fetch. DEPENDENCY_SOURCE must name the release's file by its absolute path."
  exit 1
fi
# / The version goes wherever the templates say, & nowhere else.
ADDRESS="${ADDRESS_TEMPLATE//\{version\}/$VERSION}"
MEMBER="${MEMBER_TEMPLATE//\{version\}/$VERSION}"
FILE_NAME="${ADDRESS##*/}"
echo "Fetching $PROGRAM $VERSION."
# / -----------------------------------------------------------------------------------


# / -----------------------------------------------------------------------------------
# / What the fetch needs. curl is needed only to download, so a host handing the file over needs none.
for TOOL in sha256sum install mktemp; do
  if ! command -v "$TOOL" > /dev/null 2>&1; then
    echo "Cannot install $PROGRAM, because $TOOL is not installed."
    exit 1
  fi
done
if [ -n "$MEMBER" ] && ! command -v tar > /dev/null 2>&1; then
  echo "Cannot install $PROGRAM, because tar is not installed."
  exit 1
fi
# / The work folder is new, under /var/tmp or TMPDIR, only root can open it, & it is removed
# / however the script ends.
WORK="$(mktemp -d "${TMPDIR:-/var/tmp}/hrconvert2-release.XXXXXXXX")"
trap 'rm -rf "$WORK"' EXIT
# / -----------------------------------------------------------------------------------


# / -----------------------------------------------------------------------------------
# / Fetch & verify.
# / A file handed over is tried first, then the project's release. Each is copied or downloaded into
# / the work folder & checked there, so what is installed is what was checked. Only HTTPS is accepted,
# / for the request & for every redirect it follows.
VERIFIED=0
for ORIGIN in ${SOURCE:+"$SOURCE"} "$ADDRESS"; do
  rm -f "$WORK/$FILE_NAME"
  if [[ "$ORIGIN" == /* ]]; then
    cp -- "$ORIGIN" "$WORK/$FILE_NAME" || { echo "Could not read $ORIGIN."; continue; }
  elif ! command -v curl > /dev/null 2>&1; then
    echo "Could not fetch $FILE_NAME from $ORIGIN, because curl is not installed."
    continue
  elif ! curl --proto '=https' --proto-redir '=https' --tlsv1.2 -fsSL --retry 5 --retry-delay 5 --connect-timeout 20 -o "$WORK/$FILE_NAME" "$ORIGIN"; then
    echo "Could not fetch $FILE_NAME from $ORIGIN."
    continue
  fi
  if echo "$CHECKSUM  $WORK/$FILE_NAME" | sha256sum -c --status -; then
    VERIFIED=1
    echo "Took $FILE_NAME from $ORIGIN, & its checksum matches."
    break
  fi
  echo "The file from $ORIGIN does not match the checksum & was discarded."
done
if [ "$VERIFIED" -ne 1 ]; then
  echo "No file matching the checksum could be had, so nothing was installed."
  exit 1
fi
# / -----------------------------------------------------------------------------------


# / -----------------------------------------------------------------------------------
# / Unpack where the file is an archive, & install.
# / Only the one member the entry names is taken out of an archive, into a folder of its own, & it
# / must arrive as a plain file. Nothing else the archive holds is unpacked.
CANDIDATE="$WORK/$FILE_NAME"
if [ -n "$MEMBER" ]; then
  mkdir "$WORK/unpacked"
  tar -xzf "$WORK/$FILE_NAME" -C "$WORK/unpacked" --no-same-owner --no-same-permissions -- "$MEMBER"
  CANDIDATE="$WORK/unpacked/$MEMBER"
fi
if [ -L "$CANDIDATE" ] || [ ! -f "$CANDIDATE" ]; then
  echo "The release's file holds no program at ${MEMBER:-$FILE_NAME}, so nothing was installed."
  exit 1
fi
# / The prefix is made only where Dependency Core has not made it already, so a folder it made
# / private stays private until what was installed has answered for itself.
if [ ! -d "$PREFIX" ]; then install -d -m 0755 "$PREFIX"; fi
install -d -m 0755 "$PREFIX/bin"
install -m 0755 "$CANDIDATE" "$PREFIX/bin/$PROGRAM"
echo "$PROGRAM $VERSION installed at $PREFIX/bin/$PROGRAM."
# / -----------------------------------------------------------------------------------
