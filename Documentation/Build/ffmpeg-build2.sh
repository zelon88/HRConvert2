#!/bin/bash

# HOMEPAGE: https://github.com/markus-perl/ffmpeg-build-script
# LICENSE: https://github.com/markus-perl/ffmpeg-build-script/blob/master/LICENSE

PROGNAME=$(basename "$0")
FFMPEG_VERSION=6.1
SCRIPT_VERSION=1.50
CWD=$(pwd)
PACKAGES="$CWD/packages"
WORKSPACE="$CWD/workspace"
CFLAGS="-I$WORKSPACE/include"
LDFLAGS="-L$WORKSPACE/lib"
LDEXEFLAGS=""
EXTRALIBS="-ldl -lpthread -lm -lz"
MACOS_M1=false
CONFIGURE_OPTIONS=()
NONFREE_AND_GPL=false
LATEST=false
MANPAGES=1
CURRENT_PACKAGE_VERSION=0

# Check for Apple Silicon
if [[ ("$(uname -m)" == "arm64") && ("$OSTYPE" == "darwin"*) ]]; then
  # If arm64 AND darwin (macOS)
  export ARCH=arm64
  export MACOSX_DEPLOYMENT_TARGET=11.0
  MACOS_M1=true
fi

# Speed up the process
# Env Var NUMJOBS overrides automatic detection
if [[ -n "$NUMJOBS" ]]; then
  MJOBS="$NUMJOBS"
elif [[ -f /proc/cpuinfo ]]; then
  MJOBS=$(grep -c processor /proc/cpuinfo)
elif [[ "$OSTYPE" == "darwin"* ]]; then
  MJOBS=$(sysctl -n machdep.cpu.thread_count)
  CONFIGURE_OPTIONS=("--enable-videotoolbox")
  MACOS_LIBTOOL="$(which libtool)" # gnu libtool is installed in this script and need to avoid name conflict
else
  MJOBS=4
fi

make_dir() {
  remove_dir "$1"
  if ! mkdir "$1"; then
    printf "\n Failed to create dir %s" "$1"
    exit 1
  fi
}

remove_dir() {
  if [ -d "$1" ]; then
    rm -r "$1"
  fi
}

download() {
  # download url [filename[dirname]]

  DOWNLOAD_PATH="$PACKAGES"
  DOWNLOAD_FILE="${2:-"${1##*/}"}"

  if [[ "$DOWNLOAD_FILE" =~ tar. ]]; then
    TARGETDIR="${DOWNLOAD_FILE%.*}"
    TARGETDIR="${3:-"${TARGETDIR%.*}"}"
  else
    TARGETDIR="${3:-"${DOWNLOAD_FILE%.*}"}"
  fi

  if [ ! -f "$DOWNLOAD_PATH/$DOWNLOAD_FILE" ]; then
    echo "Downloading $1 as $DOWNLOAD_FILE"
    curl -L --silent -o "$DOWNLOAD_PATH/$DOWNLOAD_FILE" "$1"

    EXITCODE=$?
    if [ $EXITCODE -ne 0 ]; then
      echo ""
      echo "Failed to download $1. Exitcode $EXITCODE. Retrying in 10 seconds"
      sleep 10
      curl -L --silent -o "$DOWNLOAD_PATH/$DOWNLOAD_FILE" "$1"
    fi

    EXITCODE=$?
    if [ $EXITCODE -ne 0 ]; then
      echo ""
      echo "Failed to download $1. Exitcode $EXITCODE"
      exit 1
    fi

    echo "... Done"
  else
    echo "$DOWNLOAD_FILE has already downloaded."
  fi

  make_dir "$DOWNLOAD_PATH/$TARGETDIR"

  if [[ "$DOWNLOAD_FILE" == *"patch"* ]]; then
    return
  fi

  if [ -n "$3" ]; then
    if ! tar -xvf "$DOWNLOAD_PATH/$DOWNLOAD_FILE" -C "$DOWNLOAD_PATH/$TARGETDIR" 2>/dev/null >/dev/null; then
      echo "Failed to extract $DOWNLOAD_FILE"
      exit 1
    fi
  else
    if ! tar -xvf "$DOWNLOAD_PATH/$DOWNLOAD_FILE" -C "$DOWNLOAD_PATH/$TARGETDIR" --strip-components 1 2>/dev/null >/dev/null; then
      echo "Failed to extract $DOWNLOAD_FILE"
      exit 1
    fi
  fi

  echo "Extracted $DOWNLOAD_FILE"

  cd "$DOWNLOAD_PATH/$TARGETDIR" || (
    echo "Error has occurred."
    exit 1
  )
}

execute() {
  echo "$ $*"

  OUTPUT=$("$@" 2>&1)

  # shellcheck disable=SC2181
  if [ $? -ne 0 ]; then
    echo "$OUTPUT"
    echo ""
    echo "Failed to Execute $*" >&2
    exit 1
  fi
}

build() {
  echo ""
  echo "building $1 - version $2"
  echo "======================="
  CURRENT_PACKAGE_VERSION=$2

  if [ -f "$PACKAGES/$1.done" ]; then
    if grep -Fx "$2" "$PACKAGES/$1.done" >/dev/null; then
      echo "$1 version $2 already built. Remove $PACKAGES/$1.done lockfile to rebuild it."
      return 1
    elif $LATEST; then
      echo "$1 is outdated and will be rebuilt with latest version $2"
      return 0
    else
      echo "$1 is outdated, but will not be rebuilt. Pass in --latest to rebuild it or remove $PACKAGES/$1.done lockfile."
      return 1
    fi
  fi

  return 0
}

command_exists() {
  if ! [[ -x $(command -v "$1") ]]; then
    return 1
  fi

  return 0
}

library_exists() {
  if ! [[ -x $(pkg-config --exists --print-errors "$1" 2>&1 >/dev/null) ]]; then
    return 1
  fi

  return 0
}

build_done() {
  echo "$2" >"$PACKAGES/$1.done"
}

verify_binary_type() {
  if ! command_exists "file"; then
    return
  fi

  BINARY_TYPE=$(file "$WORKSPACE/bin/ffmpeg" | sed -n 's/^.*\:\ \(.*$\)/\1/p')
  echo ""
  case $BINARY_TYPE in
  "Mach-O 64-bit executable arm64")
    echo "Successfully built Apple Silicon (M1) for ${OSTYPE}: ${BINARY_TYPE}"
    ;;
  *)
    echo "Successfully built binary for ${OSTYPE}: ${BINARY_TYPE}"
    ;;
  esac
}

cleanup() {
  remove_dir "$PACKAGES"
  remove_dir "$WORKSPACE"
  echo "Cleanup done."
  echo ""
}

usage() {
  echo "Usage: $PROGNAME [OPTIONS]"
  echo "Options:"
  echo "  -h, --help                     Display usage information"
  echo "      --version                  Display version information"
  echo "  -b, --build                    Starts the build process"
  echo "      --enable-gpl-and-non-free  Enable GPL and non-free codecs  - https://ffmpeg.org/legal.html"
  echo "  -c, --cleanup                  Remove all working dirs"
  echo "      --latest                   Build latest version of dependencies if newer available"
  echo "      --small                    Prioritize small size over speed and usability; don't build manpages"
  echo "      --full-static              Build a full static FFmpeg binary (eg. glibc, pthreads etc...) **only Linux**"
  echo "                                 Note: Because of the NSS (Name Service Switch), glibc does not recommend static links."
  echo ""
}

echo "ffmpeg-build-script v$SCRIPT_VERSION"
echo "========================="
echo ""

while (($# > 0)); do
  case $1 in
  -h | --help)
    usage
    exit 0
    ;;
  --version)
    echo "$SCRIPT_VERSION"
    exit 0
    ;;
  -*)
    if [[ "$1" == "--build" || "$1" =~ '-b' ]]; then
      bflag='-b'
    fi
    if [[ "$1" == "--enable-gpl-and-non-free" ]]; then
      CONFIGURE_OPTIONS+=("--enable-nonfree")
      CONFIGURE_OPTIONS+=("--enable-gpl")
      NONFREE_AND_GPL=true
    fi
    if [[ "$1" == "--cleanup" || "$1" =~ '-c' && ! "$1" =~ '--' ]]; then
      cflag='-c'
      cleanup
    fi
    if [[ "$1" == "--full-static" ]]; then
      if [[ "$OSTYPE" == "darwin"* ]]; then
        echo "Error: A full static binary can only be build on Linux."
        exit 1
      fi
      LDEXEFLAGS="-static"
    fi
    if [[ "$1" == "--latest" ]]; then
      LATEST=true
    fi
    if [[ "$1" == "--small" ]]; then
      CONFIGURE_OPTIONS+=("--enable-small" "--disable-doc")
      MANPAGES=0
    fi
    shift
    ;;
  *)
    usage
    exit 1
    ;;
  esac
done

if [ -z "$bflag" ]; then
  if [ -z "$cflag" ]; then
    usage
    exit 1
  fi
  exit 0
fi

echo "Using $MJOBS make jobs simultaneously."

if $NONFREE_AND_GPL; then
  echo "With GPL and non-free codecs"
fi


if [ -n "$LDEXEFLAGS" ]; then
  echo "Start the build in full static mode."
fi

mkdir -p "$PACKAGES"
mkdir -p "$WORKSPACE"

export PATH="${WORKSPACE}/bin:$PATH"
PKG_CONFIG_PATH="$WORKSPACE/lib/pkgconfig:/usr/local/lib/x86_64-linux-gnu/pkgconfig:/usr/local/lib/pkgconfig"
PKG_CONFIG_PATH+=":/usr/local/share/pkgconfig:/usr/lib/x86_64-linux-gnu/pkgconfig:/usr/lib/pkgconfig:/usr/share/pkgconfig:/usr/lib64/pkgconfig"
export PKG_CONFIG_PATH

if ! command_exists "make"; then
  echo "make not installed."
  exit 1
fi

if ! command_exists "g++"; then
  echo "g++ not installed."
  exit 1
fi

if ! command_exists "curl"; then
  echo "curl not installed."
  exit 1
fi

if ! command_exists "cargo"; then
  echo "cargo not installed. rav1e encoder will not be available."
fi

if ! command_exists "python3"; then
  echo "python3 command not found. Lv2 filter and dav1d decoder will not be available."
fi

##
## build tools
##

if build "giflib" "5.2.1"; then
  download "https://netcologne.dl.sourceforge.net/project/giflib/giflib-$CURRENT_PACKAGE_VERSION.tar.gz"
  if [[ "$OSTYPE" == "darwin"* ]]; then
    download "https://sourceforge.net/p/giflib/bugs/_discuss/thread/4e811ad29b/c323/attachment/Makefile.patch"
    execute patch -p0 --forward "${PACKAGES}/giflib-$CURRENT_PACKAGE_VERSION/Makefile" "${PACKAGES}/Makefile.patch" || true
  fi
  cd "${PACKAGES}"/giflib-$CURRENT_PACKAGE_VERSION || exit
  #multicore build disabled for this library
  execute make
  execute make PREFIX="${WORKSPACE}" install
  build_done "giflib" $CURRENT_PACKAGE_VERSION
fi

if build "pkg-config" "0.29.2"; then
  download "https://pkgconfig.freedesktop.org/releases/pkg-config-$CURRENT_PACKAGE_VERSION.tar.gz"
  execute ./configure --silent --prefix="${WORKSPACE}" --with-pc-path="${WORKSPACE}"/lib/pkgconfig --with-internal-glib
  execute make -j $MJOBS
  execute make install
  build_done "pkg-config" $CURRENT_PACKAGE_VERSION
fi

if build "yasm" "1.3.0"; then
  download "https://github.com/yasm/yasm/releases/download/v$CURRENT_PACKAGE_VERSION/yasm-$CURRENT_PACKAGE_VERSION.tar.gz"
  execute ./configure --prefix="${WORKSPACE}"
  execute make -j $MJOBS
  execute make install
  build_done "yasm" $CURRENT_PACKAGE_VERSION
fi

if build "nasm" "2.16.01"; then
  download "https://www.nasm.us/pub/nasm/releasebuilds/$CURRENT_PACKAGE_VERSION/nasm-$CURRENT_PACKAGE_VERSION.tar.xz"
  execute ./configure --prefix="${WORKSPACE}" --disable-shared --enable-static
  execute make -j $MJOBS
  execute make install
  build_done "nasm" $CURRENT_PACKAGE_VERSION
fi

if build "zlib" "1.2.13"; then
  download "https://github.com/madler/zlib/releases/download/v$CURRENT_PACKAGE_VERSION/zlib-$CURRENT_PACKAGE_VERSION.tar.gz"
  execute ./configure --static --prefix="${WORKSPACE}"
  execute make -j $MJOBS
  execute make install
  build_done "zlib" $CURRENT_PACKAGE_VERSION
fi

if build "m4" "1.4.19"; then
  download "https://ftp.gnu.org/gnu/m4/m4-$CURRENT_PACKAGE_VERSION.tar.gz"
  execute ./configure --prefix="${WORKSPACE}"
  execute make -j $MJOBS
  execute make install
  build_done "m4" $CURRENT_PACKAGE_VERSION
fi

if build "autoconf" "2.71"; then
  download "https://ftp.gnu.org/gnu/autoconf/autoconf-$CURRENT_PACKAGE_VERSION.tar.gz"
  execute ./configure --prefix="${WORKSPACE}"
  execute make -j $MJOBS
  execute make install
  build_done "autoconf" $CURRENT_PACKAGE_VERSION
fi

if build "automake" "1.16.5"; then
  download "https://ftp.gnu.org/gnu/automake/automake-$CURRENT_PACKAGE_VERSION.tar.gz"
  execute ./configure --prefix="${WORKSPACE}"
  execute make -j $MJOBS
  execute make install
  build_done "automake" $CURRENT_PACKAGE_VERSION
fi

if build "libtool" "2.4.7"; then
  download "https://ftpmirror.gnu.org/libtool/libtool-$CURRENT_PACKAGE_VERSION.tar.gz"
  execute ./configure --prefix="${WORKSPACE}" --enable-static --disable-shared
  execute make -j $MJOBS
  execute make install
  build_done "libtool" $CURRENT_PACKAGE_VERSION
fi

if $NONFREE_AND_GPL; then
  if build "openssl" "1.1.1w"; then
    download "https://www.openssl.org/source/openssl-$CURRENT_PACKAGE_VERSION.tar.gz"
    if $MACOS_M1; then
      sed -n 's/\(##### GNU Hurd\)/"darwin64-arm64-cc" => { \n    inherit_from     => [ "darwin-common", asm("aarch64_asm") ],\n    CFLAGS           => add("-Wall"),\n    cflags           => add("-arch arm64 "),\n    lib_cppflags     => add("-DL_ENDIAN"),\n    bn_ops           => "SIXTY_FOUR_BIT_LONG", \n    perlasm_scheme   => "macosx", \n}, \n\1/g' Configurations/10-main.conf
      execute ./Configure --prefix="${WORKSPACE}" no-shared no-asm darwin64-arm64-cc
    else
      execute ./config --prefix="${WORKSPACE}" --openssldir="${WORKSPACE}" --with-zlib-include="${WORKSPACE}"/include/ --with-zlib-lib="${WORKSPACE}"/lib no-shared zlib
    fi
    execute make -j $MJOBS
    execute make install_sw
    build_done "openssl" $CURRENT_PACKAGE_VERSION
  fi
  CONFIGURE_OPTIONS+=("--enable-openssl")
else
  if build "gmp" "6.2.1"; then
    download "https://ftp.gnu.org/gnu/gmp/gmp-$CURRENT_PACKAGE_VERSION.tar.xz"
    execute ./configure --prefix="${WORKSPACE}" --disable-shared --enable-static
    execute make -j $MJOBS
    execute make install
    build_done "gmp" $CURRENT_PACKAGE_VERSION
  fi

  if build "nettle" "3.9.1"; then
    download "https://ftp.gnu.org/gnu/nettle/nettle-$CURRENT_PACKAGE_VERSION.tar.gz"
    execute ./configure --prefix="${WORKSPACE}" --disable-shared --enable-static --disable-openssl --disable-documentation --libdir="${WORKSPACE}"/lib CPPFLAGS="${CFLAGS}" LDFLAGS="${LDFLAGS}"
    execute make -j $MJOBS
    execute make install
    build_done "nettle" $CURRENT_PACKAGE_VERSION
  fi

  if [[ ! $ARCH == 'arm64' ]]; then
    if build "gnutls" "3.7.10"; then
      download "https://www.gnupg.org/ftp/gcrypt/gnutls/v3.7/gnutls-$CURRENT_PACKAGE_VERSION.tar.xz"
      execute ./configure --prefix="${WORKSPACE}" --disable-shared --enable-static --disable-doc --disable-tools --disable-cxx --disable-tests --disable-gtk-doc-html --disable-libdane --disable-nls --enable-local-libopts --disable-guile --with-included-libtasn1 --with-included-unistring --without-p11-kit CPPFLAGS="${CFLAGS}" LDFLAGS="${LDFLAGS}"
      execute make -j $MJOBS
      execute make install
      build_done "gnutls" $CURRENT_PACKAGE_VERSION
    fi
    # CONFIGURE_OPTIONS+=("--enable-gmp" "--enable-gnutls")
  fi
fi

if build "cmake" "3.27.7"; then
  download "https://github.com/Kitware/CMake/releases/download/v$CURRENT_PACKAGE_VERSION/cmake-$CURRENT_PACKAGE_VERSION.tar.gz"
  execute ./configure --prefix="${WORKSPACE}" --parallel="${MJOBS}" -- -DCMAKE_USE_OPENSSL=OFF
  execute make -j $MJOBS
  execute make install
  build_done "cmake" $CURRENT_PACKAGE_VERSION
fi

##
## video library
##

if command_exists "python3"; then
  # dav1d needs meson and ninja along with nasm to be built
  if command_exists "pip3"; then
    # meson and ninja can be installed via pip3
    execute pip3 install pip setuptools --quiet --upgrade --no-cache-dir --disable-pip-version-check
    for r in meson ninja; do
      if ! command_exists ${r}; then
        execute pip3 install ${r} --quiet --upgrade --no-cache-dir --disable-pip-version-check
      fi
      export PATH=$PATH:~/Library/Python/3.9/bin
    done
  fi
  if command_exists "meson"; then
    if build "dav1d" "1.1.0"; then
      download "https://code.videolan.org/videolan/dav1d/-/archive/$CURRENT_PACKAGE_VERSION/dav1d-$CURRENT_PACKAGE_VERSION.tar.gz"
      make_dir build

      CFLAGSBACKUP=$CFLAGS
      if $MACOS_M1; then
        export CFLAGS="-arch arm64"
      fi

      execute meson build --prefix="${WORKSPACE}" --buildtype=release --default-library=static --libdir="${WORKSPACE}"/lib
      execute ninja -C build
      execute ninja -C build install

      if $MACOS_M1; then
        export CFLAGS=$CFLAGSBACKUP
      fi

      build_done "dav1d" $CURRENT_PACKAGE_VERSION
    fi
    CONFIGURE_OPTIONS+=("--enable-libdav1d")
  fi
fi

if build "svtav1" "1.7.0"; then
  # Last known working commit which passed CI Tests from HEAD branch
  download "https://gitlab.com/AOMediaCodec/SVT-AV1/-/archive/v$CURRENT_PACKAGE_VERSION/SVT-AV1-v$CURRENT_PACKAGE_VERSION.tar.gz" "svtav1-$CURRENT_PACKAGE_VERSION.tar.gz"
  cd "${PACKAGES}"/svtav1-$CURRENT_PACKAGE_VERSION//Build/linux || exit
  execute cmake -DCMAKE_INSTALL_PREFIX="${WORKSPACE}" -DENABLE_SHARED=off -DBUILD_SHARED_LIBS=OFF ../.. -G"Unix Makefiles" -DCMAKE_BUILD_TYPE=Release
  execute make -j $MJOBS
  execute make install
  execute cp SvtAv1Enc.pc "${WORKSPACE}/lib/pkgconfig/"
  execute cp SvtAv1Dec.pc "${WORKSPACE}/lib/pkgconfig/"
  build_done "svtav1" $CURRENT_PACKAGE_VERSION
fi
CONFIGURE_OPTIONS+=("--enable-libsvtav1")


if command_exists "cargo"; then
  if [[ ! "$SKIPRAV1E" == "yes" ]]; then
    if build "rav1e" "0.6.6"; then
      execute cargo install --version "0.9.20+cargo-0.71" cargo-c
      download "https://github.com/xiph/rav1e/archive/refs/tags/v$CURRENT_PACKAGE_VERSION.tar.gz"
      execute cargo cinstall --prefix="${WORKSPACE}" --library-type=staticlib --crt-static --release
      build_done "rav1e" $CURRENT_PACKAGE_VERSION
    fi
    CONFIGURE_OPTIONS+=("--enable-librav1e")
  fi
fi

if $NONFREE_AND_GPL; then

  if build "x264" "941cae6d"; then
    download "https://code.videolan.org/videolan/x264/-/archive/$CURRENT_PACKAGE_VERSION/x264-$CURRENT_PACKAGE_VERSION.tar.gz" "x264-$CURRENT_PACKAGE_VERSION.tar.gz"
    cd "${PACKAGES}"/x264-$CURRENT_PACKAGE_VERSION || exit

    if [[ "$OSTYPE" == "linux-gnu" ]]; then
      execute ./configure --prefix="${WORKSPACE}" --enable-static --enable-pic CXXFLAGS="-fPIC ${CXXFLAGS}"
    else
      execute ./configure --prefix="${WORKSPACE}" --enable-static --enable-pic
    fi

    execute make -j $MJOBS
    execute make install
    execute make install-lib-static

    build_done "x264" $CURRENT_PACKAGE_VERSION
  fi
  CONFIGURE_OPTIONS+=("--enable-libx264")
fi

if $NONFREE_AND_GPL; then
  if build "x265" "3.5"; then
    download "https://github.com/videolan/x265/archive/Release_$CURRENT_PACKAGE_VERSION.tar.gz" "x265-$CURRENT_PACKAGE_VERSION.tar.gz" # This is actually 3.4 if looking at x265Version.txt
    cd build/linux || exit
    rm -rf 8bit 10bit 12bit 2>/dev/null
    mkdir -p 8bit 10bit 12bit
    cd 12bit || exit
    execute cmake ../../../source -DCMAKE_INSTALL_PREFIX="${WORKSPACE}" -DENABLE_SHARED=OFF -DBUILD_SHARED_LIBS=OFF -DHIGH_BIT_DEPTH=ON -DENABLE_HDR10_PLUS=ON -DEXPORT_C_API=OFF -DENABLE_CLI=OFF -DMAIN12=ON
    execute make -j $MJOBS
    cd ../10bit || exit
    execute cmake ../../../source -DCMAKE_INSTALL_PREFIX="${WORKSPACE}" -DENABLE_SHARED=OFF -DBUILD_SHARED_LIBS=OFF -DHIGH_BIT_DEPTH=ON -DENABLE_HDR10_PLUS=ON -DEXPORT_C_API=OFF -DENABLE_CLI=OFF
    execute make -j $MJOBS
    cd ../8bit || exit
    ln -sf ../10bit/libx265.a libx265_main10.a
    ln -sf ../12bit/libx265.a libx265_main12.a
    execute cmake ../../../source -DCMAKE_INSTALL_PREFIX="${WORKSPACE}" -DENABLE_SHARED=OFF -DBUILD_SHARED_LIBS=OFF -DEXTRA_LIB="x265_main10.a;x265_main12.a;-ldl" -DEXTRA_LINK_FLAGS=-L. -DLINKED_10BIT=ON -DLINKED_12BIT=ON
    execute make -j $MJOBS

    mv libx265.a libx265_main.a

    if [[ "$OSTYPE" == "darwin"* ]]; then
      execute "${MACOS_LIBTOOL}" -static -o libx265.a libx265_main.a libx265_main10.a libx265_main12.a 2>/dev/null
    else
      execute ar -M <<EOF
CREATE libx265.a
ADDLIB libx265_main.a
ADDLIB libx265_main10.a
ADDLIB libx265_main12.a
SAVE
END
EOF
    fi

    execute make install

    if [ -n "$LDEXEFLAGS" ]; then
      sed -i.backup 's/-lgcc_s/-lgcc_eh/g' "${WORKSPACE}/lib/pkgconfig/x265.pc" # The -i.backup is intended and required on MacOS: https://stackoverflow.com/questions/5694228/sed-in-place-flag-that-works-both-on-mac-bsd-and-linux
    fi

    build_done "x265" $CURRENT_PACKAGE_VERSION
  fi
  CONFIGURE_OPTIONS+=("--enable-libx265")
fi

if build "libvpx" "1.13.1"; then
  download "https://github.com/webmproject/libvpx/archive/refs/tags/v$CURRENT_PACKAGE_VERSION.tar.gz" "libvpx-$CURRENT_PACKAGE_VERSION.tar.gz"

  if [[ "$OSTYPE" == "darwin"* ]]; then
    echo "Applying Darwin patch"
    sed "s/,--version-script//g" build/make/Makefile >build/make/Makefile.patched
    sed "s/-Wl,--no-undefined -Wl,-soname/-Wl,-undefined,error -Wl,-install_name/g" build/make/Makefile.patched >build/make/Makefile
  fi

  execute ./configure --prefix="${WORKSPACE}" --disable-unit-tests --disable-shared --disable-examples --as=yasm --enable-vp9-highbitdepth
  execute make -j $MJOBS
  execute make install

  build_done "libvpx" $CURRENT_PACKAGE_VERSION
fi
CONFIGURE_OPTIONS+=("--enable-libvpx")

if $NONFREE_AND_GPL; then
  if build "xvidcore" "1.3.7"; then
    download "https://downloads.xvid.com/downloads/xvidcore-$CURRENT_PACKAGE_VERSION.tar.gz"
    cd build/generic || exit
    execute ./configure --prefix="${WORKSPACE}" --disable-shared --enable-static
    execute make -j $MJOBS
    execute make install

    if [[ -f ${WORKSPACE}/lib/libxvidcore.4.dylib ]]; then
      execute rm "${WORKSPACE}/lib/libxvidcore.4.dylib"
    fi

    if [[ -f ${WORKSPACE}/lib/libxvidcore.so ]]; then
      execute rm "${WORKSPACE}"/lib/libxvidcore.so*
    fi

    build_done "xvidcore" $CURRENT_PACKAGE_VERSION
  fi
  CONFIGURE_OPTIONS+=("--enable-libxvid")
fi

if $NONFREE_AND_GPL; then
  if build "vid_stab" "1.1.1"; then
    download "https://github.com/georgmartius/vid.stab/archive/v$CURRENT_PACKAGE_VERSION.tar.gz" "vid.stab-$CURRENT_PACKAGE_VERSION.tar.gz"

    if $MACOS_M1; then
      curl -L --silent -o "$PACKAGES/vid.stab-$CURRENT_PACKAGE_VERSION/fix_cmake_quoting.patch" "https://raw.githubusercontent.com/Homebrew/formula-patches/5bf1a0e0cfe666ee410305cece9c9c755641bfdf/libvidstab/fix_cmake_quoting.patch"
      patch -p1 <fix_cmake_quoting.patch
    fi

    execute cmake -DBUILD_SHARED_LIBS=OFF -DCMAKE_INSTALL_PREFIX="${WORKSPACE}" -DUSE_OMP=OFF -DENABLE_SHARED=off .
    execute make
    execute make install

    build_done "vid_stab" $CURRENT_PACKAGE_VERSION
  fi
  CONFIGURE_OPTIONS+=("--enable-libvidstab")
fi

if build "av1" "7b5f665"; then
  # libaom bcfe6fb == v3.5.0
  download "https://aomedia.googlesource.com/aom/+archive/$CURRENT_PACKAGE_VERSION.tar.gz" "av1.tar.gz" "av1"
  make_dir "$PACKAGES"/aom_build
  cd "$PACKAGES"/aom_build || exit
  if $MACOS_M1; then
    execute cmake -DENABLE_TESTS=0 -DENABLE_EXAMPLES=0 -DCMAKE_INSTALL_PREFIX="${WORKSPACE}" -DCMAKE_INSTALL_LIBDIR=lib -DCONFIG_RUNTIME_CPU_DETECT=0 "$PACKAGES"/av1
  else
    execute cmake -DENABLE_TESTS=0 -DENABLE_EXAMPLES=0 -DCMAKE_INSTALL_PREFIX="${WORKSPACE}" -DCMAKE_INSTALL_LIBDIR=lib "$PACKAGES"/av1
  fi
  execute make -j $MJOBS
  execute make install

  build_done "av1" $CURRENT_PACKAGE_VERSION
fi
CONFIGURE_OPTIONS+=("--enable-libaom")

if build "zimg" "3.0.5"; then
  download "https://github.com/sekrit-twc/zimg/archive/refs/tags/release-$CURRENT_PACKAGE_VERSION.tar.gz" "zimg-$CURRENT_PACKAGE_VERSION.tar.gz" "zimg"
  cd zimg-release-$CURRENT_PACKAGE_VERSION || exit
  execute "${WORKSPACE}/bin/libtoolize" -i -f -q
  execute ./autogen.sh --prefix="${WORKSPACE}"
  execute ./configure --prefix="${WORKSPACE}" --enable-static --disable-shared
  execute make -j $MJOBS
  execute make install
  build_done "zimg" $CURRENT_PACKAGE_VERSION
fi
CONFIGURE_OPTIONS+=("--enable-libzimg")

##
## audio library
##

if command_exists "python3"; then

  if command_exists "meson"; then

    if build "lv2" "1.18.10"; then
      download "https://lv2plug.in/spec/lv2-$CURRENT_PACKAGE_VERSION.tar.xz" "lv2-$CURRENT_PACKAGE_VERSION.tar.xz"
      execute meson build --prefix="${WORKSPACE}" --buildtype=release --default-library=static --libdir="${WORKSPACE}"/lib
      execute ninja -C build
      execute ninja -C build install
      build_done "lv2" $CURRENT_PACKAGE_VERSION
    fi
    if build "waflib" "b600c92"; then
      download "https://gitlab.com/drobilla/autowaf/-/archive/$CURRENT_PACKAGE_VERSION/autowaf-$CURRENT_PACKAGE_VERSION.tar.gz" "autowaf.tar.gz"
      build_done "waflib" $CURRENT_PACKAGE_VERSION
    fi
    if build "serd" "0.30.16"; then
      download "https://gitlab.com/drobilla/serd/-/archive/v$CURRENT_PACKAGE_VERSION/serd-v$CURRENT_PACKAGE_VERSION.tar.gz" "serd-v$CURRENT_PACKAGE_VERSION.tar.gz"
      execute meson build --prefix="${WORKSPACE}" --buildtype=release --default-library=static --libdir="${WORKSPACE}"/lib
      execute ninja -C build
      execute ninja -C build install
      build_done "serd" $CURRENT_PACKAGE_VERSION
    fi
    if build "pcre" "8.45"; then
      download "https://altushost-swe.dl.sourceforge.net/project/pcre/pcre/$CURRENT_PACKAGE_VERSION/pcre-$CURRENT_PACKAGE_VERSION.tar.gz" "pcre-$CURRENT_PACKAGE_VERSION.tar.gz"
      execute ./configure --prefix="${WORKSPACE}" --disable-shared --enable-static
      execute make -j $MJOBS
      execute make install
      build_done "pcre" $CURRENT_PACKAGE_VERSION
    fi
    if build "sord" "0.16.14"; then
      download "https://gitlab.com/drobilla/sord/-/archive/v$CURRENT_PACKAGE_VERSION/sord-v$CURRENT_PACKAGE_VERSION.tar.gz" "sord-v$CURRENT_PACKAGE_VERSION.tar.gz"
      execute meson build --prefix="${WORKSPACE}" --buildtype=release --default-library=static --libdir="${WORKSPACE}"/lib
      execute ninja -C build
      execute ninja -C build install
      build_done "sord" $CURRENT_PACKAGE_VERSION
    fi
    if build "sratom" "0.6.14"; then
      download "https://gitlab.com/lv2/sratom/-/archive/v$CURRENT_PACKAGE_VERSION/sratom-v$CURRENT_PACKAGE_VERSION.tar.gz" "sratom-v$CURRENT_PACKAGE_VERSION.tar.gz"
      execute meson build --prefix="${WORKSPACE}" --buildtype=release --default-library=static --libdir="${WORKSPACE}"/lib
      execute ninja -C build
      execute ninja -C build install
      build_done "sratom" $CURRENT_PACKAGE_VERSION
    fi
    if build "lilv" "0.24.20"; then
      download "https://gitlab.com/lv2/lilv/-/archive/v$CURRENT_PACKAGE_VERSION/lilv-v$CURRENT_PACKAGE_VERSION.tar.gz" "lilv-v$CURRENT_PACKAGE_VERSION.tar.gz"
      execute meson build --prefix="${WORKSPACE}" --buildtype=release --default-library=static --libdir="${WORKSPACE}"/lib
      execute ninja -C build
      execute ninja -C build install
      build_done "lilv" $CURRENT_PACKAGE_VERSION
    fi
    CFLAGS+=" -I$WORKSPACE/include/lilv-0"

    CONFIGURE_OPTIONS+=("--enable-lv2")

  fi
fi

if build "opencore" "0.1.6"; then
  download "https://deac-ams.dl.sourceforge.net/project/opencore-amr/opencore-amr/opencore-amr-$CURRENT_PACKAGE_VERSION.tar.gz" "opencore-amr-$CURRENT_PACKAGE_VERSION.tar.gz"
  execute ./configure --prefix="${WORKSPACE}" --disable-shared --enable-static
  execute make -j $MJOBS
  execute make install

  build_done "opencore" $CURRENT_PACKAGE_VERSION
fi
CONFIGURE_OPTIONS+=("--enable-libopencore_amrnb" "--enable-libopencore_amrwb")

if build "lame" "3.100"; then
  download "https://sourceforge.net/projects/lame/files/lame/$CURRENT_PACKAGE_VERSION/lame-$CURRENT_PACKAGE_VERSION.tar.gz/download?use_mirror=gigenet" "lame-$CURRENT_PACKAGE_VERSION.tar.gz"
  execute ./configure --prefix="${WORKSPACE}" --disable-shared --enable-static
  execute make -j $MJOBS
  execute make install

  build_done "lame" $CURRENT_PACKAGE_VERSION
fi
CONFIGURE_OPTIONS+=("--enable-libmp3lame")

if build "opus" "1.4"; then
  download "https://downloads.xiph.org/releases/opus/opus-$CURRENT_PACKAGE_VERSION.tar.gz"
  execute ./configure --prefix="${WORKSPACE}" --disable-shared --enable-static
  execute make -j $MJOBS
  execute make install

  build_done "opus" $CURRENT_PACKAGE_VERSION
fi
CONFIGURE_OPTIONS+=("--enable-libopus")

if build "libogg" "1.3.5"; then
  download "https://ftp.osuosl.org/pub/xiph/releases/ogg/libogg-$CURRENT_PACKAGE_VERSION.tar.xz"
  execute ./configure --prefix="${WORKSPACE}" --disable-shared --enable-static
  execute make -j $MJOBS
  execute make install
  build_done "libogg" $CURRENT_PACKAGE_VERSION
fi

if build "libvorbis" "1.3.7"; then
  download "https://ftp.osuosl.org/pub/xiph/releases/vorbis/libvorbis-$CURRENT_PACKAGE_VERSION.tar.gz"
  execute ./configure --prefix="${WORKSPACE}" --with-ogg-libraries="${WORKSPACE}"/lib --with-ogg-includes="${WORKSPACE}"/include/ --enable-static --disable-shared --disable-oggtest
  execute make -j $MJOBS
  execute make install

  build_done "libvorbis" $CURRENT_PACKAGE_VERSION
fi
CONFIGURE_OPTIONS+=("--enable-libvorbis")

if build "libtheora" "1.1.1"; then
  download "https://ftp.osuosl.org/pub/xiph/releases/theora/libtheora-$CURRENT_PACKAGE_VERSION.tar.gz"
  sed "s/-fforce-addr//g" configure >configure.patched
  chmod +x configure.patched
  mv configure.patched configure

  if ! $MACOS_M1; then
    ##BEGIN CONFIG.GUESS PATCH -- Updating config.guess file. Which allowed me to compile on aarch64 (ARMv8) [linux kernel 4.9 Ubuntu 20.04]
    rm config.guess
    curl -L --silent -o "config.guess" "https://raw.githubusercontent.com/gcc-mirror/gcc/master/config.guess"
    chmod +x config.guess
    ##END OF CONFIG.GUESS PATCH
  fi

  execute ./configure --prefix="${WORKSPACE}" --with-ogg-libraries="${WORKSPACE}"/lib --with-ogg-includes="${WORKSPACE}"/include/ --with-vorbis-libraries="${WORKSPACE}"/lib --with-vorbis-includes="${WORKSPACE}"/include/ --enable-static --disable-shared --disable-oggtest --disable-vorbistest --disable-examples --disable-asm --disable-spec
  execute make -j $MJOBS
  execute make install

  build_done "libtheora" $CURRENT_PACKAGE_VERSION
fi
CONFIGURE_OPTIONS+=("--enable-libtheora")

if $NONFREE_AND_GPL; then
  if build "fdk_aac" "2.0.2"; then
    download "https://sourceforge.net/projects/opencore-amr/files/fdk-aac/fdk-aac-$CURRENT_PACKAGE_VERSION.tar.gz/download?use_mirror=gigenet" "fdk-aac-$CURRENT_PACKAGE_VERSION.tar.gz"
    execute ./configure --prefix="${WORKSPACE}" --disable-shared --enable-static --enable-pic
    execute make -j $MJOBS
    execute make install

    build_done "fdk_aac" $CURRENT_PACKAGE_VERSION
  fi
  CONFIGURE_OPTIONS+=("--enable-libfdk-aac")
fi

##
## image library
##

if build "libtiff" "4.5.0"; then
  download "https://download.osgeo.org/libtiff/tiff-$CURRENT_PACKAGE_VERSION.tar.xz"
  execute ./configure --prefix="${WORKSPACE}" --disable-shared --enable-static --disable-dependency-tracking --disable-lzma --disable-webp --disable-zstd --without-x
  execute make -j $MJOBS
  execute make install
  build_done "libtiff" $CURRENT_PACKAGE_VERSION
fi
if build "libpng" "1.6.39"; then
  download "https://sourceforge.net/projects/libpng/files/libpng16/$CURRENT_PACKAGE_VERSION/libpng-$CURRENT_PACKAGE_VERSION.tar.gz" "libpng-$CURRENT_PACKAGE_VERSION.tar.gz"
  export LDFLAGS="${LDFLAGS}"
  export CPPFLAGS="${CFLAGS}"
  execute ./configure --prefix="${WORKSPACE}" --disable-shared --enable-static
  execute make -j $MJOBS
  execute make install
  build_done "libpng" $CURRENT_PACKAGE_VERSION
fi

## does not compile on monterey -> _PrintGifError
if [[ "$OSTYPE" != "darwin"* ]]; then
  if build "libwebp" "1.2.2"; then
    # libwebp can fail to compile on Ubuntu if these flags were left set to CFLAGS
    CPPFLAGS=
    download "https://storage.googleapis.com/downloads.webmproject.org/releases/webp/libwebp-$CURRENT_PACKAGE_VERSION.tar.gz" "libwebp-$CURRENT_PACKAGE_VERSION.tar.gz"
    execute ./configure --prefix="${WORKSPACE}" --disable-shared --enable-static --disable-dependency-tracking --disable-gl --with-zlib-include="${WORKSPACE}"/include/ --with-zlib-lib="${WORKSPACE}"/lib
    make_dir build
    cd build || exit
    execute cmake -DCMAKE_INSTALL_PREFIX="${WORKSPACE}" -DCMAKE_INSTALL_LIBDIR=lib -DCMAKE_INSTALL_BINDIR=bin -DCMAKE_INSTALL_INCLUDEDIR=include -DENABLE_SHARED=OFF -DENABLE_STATIC=ON ../
    execute make -j $MJOBS
    execute make install

    build_done "libwebp" $CURRENT_PACKAGE_VERSION
  fi
  CONFIGURE_OPTIONS+=("--enable-libwebp")
fi
##
## other library
##

if build "libsdl" "2.28.5"; then
  download "https://github.com/libsdl-org/SDL/releases/download/release-$CURRENT_PACKAGE_VERSION/SDL2-$CURRENT_PACKAGE_VERSION.tar.gz"
  execute ./configure --prefix="${WORKSPACE}" --disable-shared --enable-static
  execute make -j $MJOBS
  execute make install

  build_done "libsdl" $CURRENT_PACKAGE_VERSION
fi

if build "FreeType2" "2.11.1"; then
  download "https://downloads.sourceforge.net/freetype/freetype-$CURRENT_PACKAGE_VERSION.tar.xz"
  execute ./configure --prefix="${WORKSPACE}" --disable-shared --enable-static
  execute make -j $MJOBS
  execute make install
  build_done "FreeType2" $CURRENT_PACKAGE_VERSION
fi

CONFIGURE_OPTIONS+=("--enable-libfreetype")

if $NONFREE_AND_GPL; then
  if build "srt" "1.5.3"; then
    download "https://github.com/Haivision/srt/archive/v$CURRENT_PACKAGE_VERSION.tar.gz" "srt-$CURRENT_PACKAGE_VERSION.tar.gz"
    export OPENSSL_ROOT_DIR="${WORKSPACE}"
    export OPENSSL_LIB_DIR="${WORKSPACE}"/lib
    export OPENSSL_INCLUDE_DIR="${WORKSPACE}"/include/
    execute cmake . -DCMAKE_INSTALL_PREFIX="${WORKSPACE}" -DCMAKE_INSTALL_LIBDIR=lib -DCMAKE_INSTALL_BINDIR=bin -DCMAKE_INSTALL_INCLUDEDIR=include -DENABLE_SHARED=OFF -DENABLE_STATIC=ON -DENABLE_APPS=OFF -DUSE_STATIC_LIBSTDCXX=ON
    execute make install

    if [ -n "$LDEXEFLAGS" ]; then
      sed -i.backup 's/-lgcc_s/-lgcc_eh/g' "${WORKSPACE}"/lib/pkgconfig/srt.pc # The -i.backup is intended and required on MacOS: https://stackoverflow.com/questions/5694228/sed-in-place-flag-that-works-both-on-mac-bsd-and-linux
    fi

    build_done "srt" $CURRENT_PACKAGE_VERSION
  fi
  CONFIGURE_OPTIONS+=("--enable-libsrt")
fi

##
## zmq library
##

if build "libzmq" "4.3.1"; then
  download "https://github.com/zeromq/libzmq/releases/download/v$CURRENT_PACKAGE_VERSION/zeromq-$CURRENT_PACKAGE_VERSION.tar.gz"
  if [[ "$OSTYPE" == "darwin"* ]]; then
    export XML_CATALOG_FILES=/usr/local/etc/xml/catalog 
  fi
  execute ./configure --prefix="${WORKSPACE}" --disable-shared --enable-static
  execute make -j $MJOBS
  execute make install
  build_done "libzmq" $CURRENT_PACKAGE_VERSION
  CONFIGURE_OPTIONS+=("--enable-libzmq")
fi

##
## HWaccel library
##

if [[ "$OSTYPE" == "linux-gnu" ]]; then
  if command_exists "nvcc"; then
    if build "nv-codec" "11.1.5.3"; then
      download "https://github.com/FFmpeg/nv-codec-headers/releases/download/n$CURRENT_PACKAGE_VERSION/nv-codec-headers-$CURRENT_PACKAGE_VERSION.tar.gz"
      execute make PREFIX="${WORKSPACE}"
      execute make PREFIX="${WORKSPACE}" install
      build_done "nv-codec" $CURRENT_PACKAGE_VERSION
    fi
    CFLAGS+=" -I/usr/local/cuda/include"
    LDFLAGS+=" -L/usr/local/cuda/lib64"
    CONFIGURE_OPTIONS+=("--enable-cuda-nvcc" "--enable-cuvid" "--enable-nvdec" "--enable-nvenc" "--enable-cuda-llvm" "--enable-ffnvcodec")

    if [ -z "$LDEXEFLAGS" ]; then
      CONFIGURE_OPTIONS+=("--enable-libnpp") # Only libnpp cannot be statically linked.
    fi
    if [ -z "$CUDA_COMPUTE_CAPABILITY" ]; then
      # Set default value if no compute capability was found
      # Note that multi-architecture builds are not supported in ffmpeg
      # see https://patchwork.ffmpeg.org/comment/62905/
      export CUDA_COMPUTE_CAPABILITY=52
    fi
    CONFIGURE_OPTIONS+=("--nvccflags=-gencode arch=compute_$CUDA_COMPUTE_CAPABILITY,code=sm_$CUDA_COMPUTE_CAPABILITY -O2")
  else
    CONFIGURE_OPTIONS+=("--disable-ffnvcodec")
  fi

  # Vaapi doesn't work well with static links FFmpeg.
  if [ -z "$LDEXEFLAGS" ]; then
    # If the libva development SDK is installed, enable vaapi.
    if library_exists "libva"; then
      if build "vaapi" "1"; then
        build_done "vaapi" "1"
      fi
      CONFIGURE_OPTIONS+=("--enable-vaapi")
    fi
  fi

  if build "amf" "1.4.30"; then
    download "https://github.com/GPUOpen-LibrariesAndSDKs/AMF/archive/refs/tags/v$CURRENT_PACKAGE_VERSION.tar.gz" "AMF-$CURRENT_PACKAGE_VERSION.tar.gz" "AMF-$CURRENT_PACKAGE_VERSION"
    execute rm -rf "${WORKSPACE}/include/AMF"
    execute mkdir -p "${WORKSPACE}/include/AMF"
    execute cp -r "${PACKAGES}"/AMF-$CURRENT_PACKAGE_VERSION/AMF-$CURRENT_PACKAGE_VERSION/amf/public/include/* "${WORKSPACE}/include/AMF/"
    build_done "amf" $CURRENT_PACKAGE_VERSION
  fi
  CONFIGURE_OPTIONS+=("--enable-amf")
fi

##
## FFmpeg
##

EXTRA_VERSION=""
if [[ "$OSTYPE" == "darwin"* ]]; then
  EXTRA_VERSION="${FFMPEG_VERSION}"
fi

if [ -d "$CWD/.git" ]; then
  echo -e "\nTemporarily moving .git dir to .git.bak to workaround ffmpeg build bug" #causing ffmpeg version number to be wrong
  mv "$CWD/.git" "$CWD/.git.bak"
  # if build fails below, .git will remain in the wrong place...
fi

build "ffmpeg" "$FFMPEG_VERSION"
download "https://github.com/FFmpeg/FFmpeg/archive/refs/heads/release/$FFMPEG_VERSION.tar.gz" "FFmpeg-release-$FFMPEG_VERSION.tar.gz"
# shellcheck disable=SC2086
./configure "${CONFIGURE_OPTIONS[@]}" \
  --disable-debug \
  --disable-shared \
  --enable-pthreads \
  --enable-static \
  --enable-version3 \
  --extra-cflags="${CFLAGS}" \
  --extra-ldexeflags="${LDEXEFLAGS}" \
  --extra-ldflags="${LDFLAGS}" \
  --extra-libs="${EXTRALIBS}" \
  --pkgconfigdir="$WORKSPACE/lib/pkgconfig" \
  --pkg-config-flags="--static" \
  --prefix="${WORKSPACE}" \
  --extra-version="${EXTRA_VERSION}"

execute make -j $MJOBS
execute make install

if [ -d "$CWD/.git.bak" ]; then
  mv "$CWD/.git.bak" "$CWD/.git"
fi

INSTALL_FOLDER="/usr"  # not recommended, overwrites system ffmpeg package
if [[ "$OSTYPE" == "darwin"* ]]; then
  INSTALL_FOLDER="/usr/local"
else
  if [ -d "$HOME/.local" ]; then  # systemd-standard user path
    INSTALL_FOLDER="$HOME/.local"
  elif [ -d "/usr/local" ]; then
    INSTALL_FOLDER="/usr/local"
  fi
fi

verify_binary_type

echo ""
echo "Building done. The following binaries can be found here:"
echo "- ffmpeg: $WORKSPACE/bin/ffmpeg"
echo "- ffprobe: $WORKSPACE/bin/ffprobe"
echo "- ffplay: $WORKSPACE/bin/ffplay"
echo ""

INSTALL_NOW=1

##if [[ "$AUTOINSTALL" == "yes" ]]; then
##  INSTALL_NOW=1
##elif [[ ! "$SKIPINSTALL" == "yes" ]]; then
##  read -r -p "Install these binaries to your $INSTALL_FOLDER folder? Existing binaries will be replaced. [Y/n] " response
##  case $response in
##    "" | [yY][eE][sS] | [yY])
##      INSTALL_NOW=1
##    ;;
##  esac
##fi

if [ "$INSTALL_NOW" = 1 ]; then
  if command_exists "sudo" && [[ $INSTALL_FOLDER == /usr* ]]; then
    SUDO=sudo
  fi

  $SUDO cp "/usr/bin/ffmpeg" "/usr/bin/ffmpeg_backup"
  $SUDO cp "/usr/bin/ffprobe" "/usr/bin/ffprobe_backup"
  $SUDO cp "/usr/bin/ffplay" "/usr/bin/ffplay_backup"

  $SUDO cp "$WORKSPACE/bin/ffmpeg" "/usr/bin/ffmpeg"
  $SUDO cp "$WORKSPACE/bin/ffprobe" "/usr/bin/ffprobe"
  $SUDO cp "$WORKSPACE/bin/ffplay" "/usr/bin/ffplay"

  if [ $MANPAGES = 1 ]; then
    $SUDO mkdir -p "$INSTALL_FOLDER/share/man/man1"
    $SUDO cp "$WORKSPACE/share/man/man1"/ff* "$INSTALL_FOLDER/share/man/man1"
    if command_exists "mandb"; then
      $SUDO mandb -q
    fi
  fi
  echo "Done. FFmpeg is now installed to your system. Please run HRConvert2 with the --Delete Build Environment-- configuration variable set to TRUE at least once to clean up ffmpeg intallation files."
fi

exit 0
