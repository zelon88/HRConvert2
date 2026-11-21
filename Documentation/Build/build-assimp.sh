#!/bin/bash
# / -----------------------------------------------------------------------------------
# / Copyright Information ...
# / HRConvert2, Copyright on 11/20/2026 by Justin Grimes, www.github.com/zelon88
# /
# / License Information ...
# / This project is protected by the GNU GPLv3 Open-Source license.
# / https://www.gnu.org/licenses/gpl-3.0.html
# /
# / File Information ...
# / v4.3.2.
# / Builds HRConvert2's own Assimp & installs it beneath one prefix.
# /
# / Resources/Contract/depends.php names this script as the Assimp Fallback, & the Fallback is always
# / used, because what this build leaves out is the point of it. Dependency Core runs it & hands it two
# / values.
# /   DEPENDENCY_PREFIX   The folder to install into. The program goes to bin/assimp beneath it.
# /   DEPENDENCY_VERSION  The version the Fallback pins, 6.0.6, which is what the program reports.
# / A checksum is not used, because the source is pinned by its commit, which names everything it holds.
# / A host that cannot reach GitHub may be handed a clone of Assimp's repository instead, by its absolute
# / path, as DEPENDENCY_SOURCE. The commit is fetched from it & checked exactly as from GitHub.
# /
# / It builds Assimp 6.0.6, whose tag names the commit pinned below, with the ten patches in
# / patches/assimp beside this script, applied in order. Each must apply, or nothing is built.
# /   0001 to 0003   Two fixes upstream has not released. 0001 & 0002 are pull request #7027, which
# /                  refuses a glTF's file reference outside its folder, CVE-2026-129477. 0003 is pull
# /                  request #7029, which bounds a data URI's header, CVE-2026-129478.
# /   0004 to 0007   Four exporter faults HRConvert2 found, each a read outside a buffer, not yet
# /                  fixed upstream. Three are the glTF 2.0 exporter's & one is the X3D exporter's.
# /   0008           assimp version prints the patch level, 6.0.6 where it printed 6.0, so a pin
# /                  that moves to another patch release is seen & the build is made again.
# /   0009 & 0010    A skin fault HRConvert2 found, not yet fixed upstream. A valid glTF whose skin
# /                  names no inverse-bind matrices crashed the glTF 2.0 importer, & a bone weight for
# /                  a vertex the mesh lacks had the glTF 2.0 exporter write outside two arrays. A
# /                  joint or an animation channel naming a node outside the scene crashed it too, &
# /                  a skinned gltf or glb carried memory it never filled, as much as its joint data.
# / Only three importers are built, OBJ, FBX & glTF 2.0, because Assimp otherwise chooses an importer
# / by what a file holds & every importer it carries is reachable from an upload. The exporters are
# / the ones the Model pipeline asks for, & assxml, without which the command line does not link.
# / STL & PLY are written by MeshLab, so neither exporter is built.
# / glTF 1.0 is never read. Its exporters stay, because 6.0.6 does not build without them, & the Model
# / pipeline asks for glTF 2.0 by name. zlib is the system's, linked as a shared library, so the host's
# / updates reach it. The program is checked for the commit, the importers & the exporters before it
# / is installed, & Dependency Core asks it its version again before it is put in service.
# /
# / Usage, as root:
# /   DEPENDENCY_PREFIX=/usr/local/libexec/hrconvert2/builds/assimp DEPENDENCY_VERSION=6.0.6 bash build-assimp.sh
# / Through the application:
# /   sudo php convertCore.php --setup --install-depends --subsystem="3D Models"
# / This file requires bash, apt-get, git, CMake 3.22 or later & a C++ compiler, & installs the last three & zlib's headers.
# / Run from somewhere else, it needs the patches folder beside it.
# / -----------------------------------------------------------------------------------

set -euo pipefail

# / -----------------------------------------------------------------------------------
# / What this script builds. A later release is taken by changing all three, & the patches with them.
ASSIMP_TAG="v6.0.6"
ASSIMP_COMMIT="a79ff6bbce54d3e01186a918dc6374ed55317426"
ASSIMP_VERSION="6.0.6"
ASSIMP_REPOSITORY="https://github.com/assimp/assimp.git"
PATCH_NAMES=(
  "0001-glTF-reject-external-URIs-that-leave-the-asset-direc.patch"
  "0002-glTF-free-LazyDict-objects-if-Read-throws-on-a-rejec.patch"
  "0003-glTF-validate-the-offsets-packed-into-a-data-URI-out.patch"
  "0004-glTF2-exporter-map-node-meshes-past-meshes-skipped-f.patch"
  "0005-glTF2-exporter-export-no-tangents-for-a-mesh-without.patch"
  "0006-glTF2-exporter-leave-out-faces-whose-size-differs-fr.patch"
  "0007-X3D-exporter-stop-the-light-lookup-at-the-number-of-.patch"
  "0008-assimp_cmd-print-the-patch-level-in-the-version.patch"
  "0009-glTF2-importer-bound-a-skin-by-its-own-matrices-the-.patch"
  "0010-glTF2-exporter-leave-out-a-bone-or-a-weight-the-mesh.patch")
# / What the program it builds must report, in Assimp's own words.
EXPECTED_IMPORTS="*.fbx;*.glb;*.gltf;*.obj;*.vrm"
EXPORT_FORMATS="OBJ 3DS COLLADA X3D FBX GLTF 3MF X ASSBIN ASSJSON ASSXML"
# / -----------------------------------------------------------------------------------


# / -----------------------------------------------------------------------------------
# / What was asked for. Each value is checked for the shape it must have before anything is fetched.
PREFIX="${DEPENDENCY_PREFIX:-}"
VERSION="${DEPENDENCY_VERSION:-}"
SOURCE="${DEPENDENCY_SOURCE:-}"
PATCH_FOLDER="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/patches/assimp"
if [[ ! "$PREFIX" =~ ^/[A-Za-z0-9._/-]+$ ]] || [[ "$PREFIX" == *..* ]]; then
  echo "Refusing to build. DEPENDENCY_PREFIX must be an absolute path."
  exit 1
fi
if [ "$VERSION" != "$ASSIMP_VERSION" ]; then
  echo "Refusing to build. DEPENDENCY_VERSION is '$VERSION', & this script builds Assimp $ASSIMP_VERSION."
  exit 1
fi
if [ -n "$SOURCE" ] && { [[ "$SOURCE" != /* ]] || [ ! -d "$SOURCE" ]; }; then
  echo "Refusing to build. DEPENDENCY_SOURCE must name a clone of Assimp's repository by its absolute path."
  exit 1
fi
for PATCH_NAME in "${PATCH_NAMES[@]}"; do
  if [ ! -f "$PATCH_FOLDER/$PATCH_NAME" ]; then
    echo "Refusing to build. $PATCH_FOLDER/$PATCH_NAME is missing."
    exit 1
  fi
done
echo "Building Assimp $ASSIMP_TAG, commit $ASSIMP_COMMIT, with ${#PATCH_NAMES[@]} patches."
# / -----------------------------------------------------------------------------------


# / -----------------------------------------------------------------------------------
# / What the build needs. A compiler, CMake, git & zlib's headers are installed where the package
# / manager can, & left installed, as every build this application runs leaves what it installs.
if ! command -v g++ > /dev/null 2>&1 || ! command -v make > /dev/null 2>&1 || ! command -v cmake > /dev/null 2>&1 || ! command -v git > /dev/null 2>&1 || [ ! -f /usr/include/zlib.h ]; then
  if command -v apt-get > /dev/null 2>&1; then
    DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends build-essential cmake git ca-certificates zlib1g-dev
    echo "build-essential, cmake, git, ca-certificates & zlib1g-dev were installed to build Assimp & are left installed."
  fi
fi
for TOOL in git cmake make g++ install grep sort; do
  if ! command -v "$TOOL" > /dev/null 2>&1; then
    echo "Cannot build Assimp, because $TOOL is not installed."
    exit 1
  fi
done
if [ ! -f /usr/include/zlib.h ]; then
  echo "Cannot build Assimp, because zlib's headers are not installed."
  exit 1
fi
# / Assimp 6.0.6 asks for CMake 3.22, & an older one fails part way through the configure.
CMAKE_VERSION="$(cmake --version | head -n 1 | grep -oE '[0-9]+\.[0-9]+' | head -n 1)"
if [ -z "$CMAKE_VERSION" ] || [ "$(printf '%s\n%s\n' "3.22" "$CMAKE_VERSION" | sort -V | head -n 1)" != "3.22" ]; then
  echo "Cannot build Assimp, because CMake $CMAKE_VERSION is older than 3.22."
  exit 1
fi
# / The system's zlib, as a shared library, so the host's updates to zlib reach the program.
# / Assimp prefers a static library wherever it builds itself as one, & its own FindZLIB reads a release & a
# / debug path, so the shared library is named outright for all three, by the file the link resolves to.
ZLIB_SHARED="$(readlink -f "$(g++ -print-file-name=libz.so)")"
if [[ "$ZLIB_SHARED" != /* ]] || [ ! -e "$ZLIB_SHARED" ]; then
  echo "Cannot build Assimp, because the system's shared zlib could not be found."
  exit 1
fi
# / The work folder is new, under /var/tmp or TMPDIR, only root can open it, & it is removed however
# / the script ends.
WORK="$(mktemp -d "${TMPDIR:-/var/tmp}/hrconvert2-assimp.XXXXXXXX")"
trap 'rm -rf "$WORK"' EXIT
# / -----------------------------------------------------------------------------------


# / -----------------------------------------------------------------------------------
# / Fetch & verify.
# / The commit is fetched by its full hash, from the clone handed over first & from GitHub after it, &
# / what was fetched is compared with that hash before anything is built. A tag can be moved, & a
# / commit cannot.
FETCHED=0
git -C "$WORK" init --quiet src
for ORIGIN in ${SOURCE:+"$SOURCE"} "$ASSIMP_REPOSITORY"; do
  if git -C "$WORK/src" fetch --quiet --depth 1 "$ORIGIN" "$ASSIMP_COMMIT" || { sleep 10; git -C "$WORK/src" fetch --quiet --depth 1 "$ORIGIN" "$ASSIMP_COMMIT"; }; then
    FETCHED=1
    echo "Fetched commit $ASSIMP_COMMIT from $ORIGIN."
    break
  fi
  echo "Could not fetch commit $ASSIMP_COMMIT from $ORIGIN."
done
if [ "$FETCHED" -ne 1 ]; then
  echo "Assimp's pinned commit could not be fetched, so nothing was built."
  exit 1
fi
git -C "$WORK/src" -c advice.detachedHead=false checkout --quiet FETCH_HEAD
FETCHED_COMMIT="$(git -C "$WORK/src" rev-parse HEAD)"
if [ "$FETCHED_COMMIT" != "$ASSIMP_COMMIT" ]; then
  echo "The fetched commit is $FETCHED_COMMIT, & this script builds only $ASSIMP_COMMIT. Nothing was built."
  exit 1
fi
echo "Assimp $ASSIMP_TAG is commit $FETCHED_COMMIT, as pinned."
# / Each patch is applied in order, & git applies a patch whole or not at all. One that does not fit ends
# / the script, & the work folder goes with it, so nothing is ever built from a tree part patched.
for PATCH_NAME in "${PATCH_NAMES[@]}"; do
  if ! git -C "$WORK/src" apply "$PATCH_FOLDER/$PATCH_NAME"; then
    echo "$PATCH_NAME does not apply. Nothing was built."
    exit 1
  fi
  echo "Applied $PATCH_NAME."
done
# / -----------------------------------------------------------------------------------


# / -----------------------------------------------------------------------------------
# / Configure, build & install.
# / The hardening HRConvert2's own builds carry, where this compiler accepts it. _FORTIFY_SOURCE=3 checks
# / the size of what is copied into a buffer the compiler can measure, the stack protector & stack clash
# / protection guard the stack, & control flow protection is the processor's, on x86.
HARDENING="-O2 -U_FORTIFY_SOURCE -D_FORTIFY_SOURCE=3 -fstack-protector-strong"
for FLAG in -fstack-clash-protection -fcf-protection; do
  if echo 'int main(void) { return 0; }' | g++ -x c++ -Werror "$FLAG" -o /dev/null - > /dev/null 2>&1; then HARDENING="$HARDENING $FLAG"; fi
done
EXPORT_FLAGS=()
for FORMAT in $EXPORT_FORMATS; do EXPORT_FLAGS+=("-DASSIMP_BUILD_${FORMAT}_EXPORTER=ON"); done
echo "Compiler flags: $HARDENING"
cmake -S "$WORK/src" -B "$WORK/build" -G "Unix Makefiles" \
  -DCMAKE_BUILD_TYPE=Release \
  -DCMAKE_C_FLAGS="$HARDENING" \
  -DCMAKE_CXX_FLAGS="$HARDENING -DASSIMP_BUILD_NO_GLTF1_IMPORTER" \
  -DCMAKE_EXE_LINKER_FLAGS="-pie -Wl,-z,relro,-z,now" \
  -DBUILD_SHARED_LIBS=OFF \
  -DASSIMP_BUILD_ASSIMP_TOOLS=ON -DASSIMP_BUILD_TESTS=OFF -DASSIMP_BUILD_SAMPLES=OFF -DASSIMP_BUILD_DOCS=OFF \
  -DASSIMP_INSTALL=OFF -DASSIMP_WARNINGS_AS_ERRORS=OFF -DASSIMP_BUILD_USE_CCACHE=OFF -DASSIMP_INJECT_DEBUG_POSTFIX=OFF \
  -DASSIMP_BUILD_ZLIB=OFF -DZLIB_LIBRARY="$ZLIB_SHARED" -DZLIB_LIBRARY_REL="$ZLIB_SHARED" -DZLIB_LIBRARY_DBG="$ZLIB_SHARED" -DZLIB_INCLUDE_DIR=/usr/include \
  -DASSIMP_BUILD_DRACO=OFF -DASSIMP_BUILD_USD_IMPORTER=OFF -DASSIMP_BUILD_M3D_IMPORTER=OFF -DASSIMP_BUILD_M3D_EXPORTER=OFF \
  -DASSIMP_BUILD_ALL_IMPORTERS_BY_DEFAULT=OFF \
  -DASSIMP_BUILD_OBJ_IMPORTER=ON -DASSIMP_BUILD_FBX_IMPORTER=ON -DASSIMP_BUILD_GLTF_IMPORTER=ON \
  -DASSIMP_BUILD_ALL_EXPORTERS_BY_DEFAULT=OFF "${EXPORT_FLAGS[@]}"
cmake --build "$WORK/build" --target assimp_cmd -j"$(nproc)"
# / The prefix is made only where Dependency Core has not made it already, so a folder it made
# / private stays private until what was built has answered for itself.
if [ ! -d "$PREFIX" ]; then install -d -m 0755 "$PREFIX"; fi
install -d -m 0755 "$PREFIX/bin" "$PREFIX/share/doc/assimp"
install -m 0755 "$WORK/build/bin/assimp" "$PREFIX/bin/assimp"
# / The licences travel with the program, Assimp's own & those of every library its source tree carries.
install -m 0644 "$WORK/src/LICENSE" "$PREFIX/share/doc/assimp/LICENSE"
while IFS= read -r LICENCE; do
  install -m 0644 "$LICENCE" "$PREFIX/share/doc/assimp/$(basename "$(dirname "$LICENCE")")-$(basename "$LICENCE")"
done < <(find "$WORK/src/contrib" -mindepth 2 -maxdepth 2 -type f \( -iname 'licen[cs]e*' -o -iname 'copying*' -o -iname 'unlicense*' \) | sort)
# / What was built, & from what, so an audit reads it rather than working it out.
{
  echo "Assimp $ASSIMP_TAG, commit $ASSIMP_COMMIT, built by HRConvert2's build-assimp.sh v4.3.2."
  echo "Importers: OBJ, FBX & glTF 2.0. Exporters: $EXPORT_FORMATS. glTF 1.0 is not read."
  echo "Compiler flags: $HARDENING"
  echo "zlib: $ZLIB_SHARED"
  for PATCH_NAME in "${PATCH_NAMES[@]}"; do echo "Patch: $PATCH_NAME"; done
} > "$PREFIX/share/doc/assimp/BUILD.txt"
chmod 0644 "$PREFIX/share/doc/assimp/BUILD.txt"
# / -----------------------------------------------------------------------------------


# / -----------------------------------------------------------------------------------
# / What was installed must be the build asked for: the version & the commit, the three importers & no
# / other, the glTF 2.0 exporters, & the system's zlib.
"$PREFIX/bin/assimp" version > "$WORK/version.txt" 2>&1 || true
"$PREFIX/bin/assimp" listext > "$WORK/imports.txt" 2>&1 || true
"$PREFIX/bin/assimp" listexport > "$WORK/exports.txt" 2>&1 || true
if ! grep -qF "Version $ASSIMP_VERSION " "$WORK/version.txt" || ! grep -qF "commit ${ASSIMP_COMMIT:0:8}" "$WORK/version.txt"; then
  echo "The program built does not report Assimp $ASSIMP_VERSION, commit ${ASSIMP_COMMIT:0:8}."
  exit 1
fi
if [ "$(tr -d '[:space:]' < "$WORK/imports.txt")" != "$EXPECTED_IMPORTS" ]; then
  echo "The program built reads $(tr -d '[:space:]' < "$WORK/imports.txt"), & only $EXPECTED_IMPORTS may be read."
  exit 1
fi
if ! grep -qx "gltf2" "$WORK/exports.txt" || ! grep -qx "glb2" "$WORK/exports.txt"; then
  echo "The program built does not write glTF 2.0."
  exit 1
fi
if command -v ldd > /dev/null 2>&1 && ! ldd "$PREFIX/bin/assimp" | grep -q "libz\.so"; then
  echo "The program built does not use the system's shared zlib."
  exit 1
fi
echo "Assimp $ASSIMP_TAG installed at $PREFIX/bin/assimp."
# / -----------------------------------------------------------------------------------
