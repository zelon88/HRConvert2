#!/usr/bin/env bash
# / -----------------------------------------------------------------------------------
# / Copyright Information ...
# / HRConvert2, Copyright on 8/12/2026 by Justin Grimes, www.github.com/zelon88
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
# / v4.2.9.
# / This file is the ImageMagick 7 source build for HRConvert2.
# / It removes the packaged ImageMagick & any earlier source build, then builds & installs
# / version 7 under /usr/local with HEIC, AVIF, JPEG XL, OpenEXR & camera raw support, which
# / the packaged build lacks. Run as root. Resources/Contract/depends.php names this script as
# / the ImageMagick BuildCommand, so --setup --install-depends is what normally runs it.
# /
# / Hardware Requirements ...
# / This application requires at least a Raspberry Pi Model B+ or greater.
# / This application will run on just about any x86 or x64 computer.
# /
# / Dependency Requirements ...
# / This file requires bash, apt-get, wget, tar & make, & is run as root.
# / It downloads the ImageMagick source over the network.
# /
# / <3 Open-Source
# / -----------------------------------------------------------------------------------

# Exit immediately if a command exits with a non-zero status
set -e

echo "=== Removing old ImageMagick versions ==="

# Remove APT-installed versions
apt-get purge -y imagemagick php-imagick 2>/dev/null || true
apt-get autoremove -y

# Remove prior source-built versions from /usr/local
echo "Cleaning up prior source builds from /usr/local..."
rm -f /usr/local/bin/magick
rm -f /usr/local/bin/convert
rm -f /usr/local/bin/identify
rm -f /usr/local/bin/composite
rm -f /usr/local/bin/mogrify
rm -f /usr/local/bin/montage
rm -f /usr/local/bin/display
rm -rf /usr/local/lib/libMagick*
rm -rf /usr/local/include/ImageMagick*
rm -rf /usr/local/share/ImageMagick*
rm -rf /usr/local/etc/ImageMagick*

# Optional: remove man pages and docs
rm -f /usr/local/share/man/man1/magick*
rm -f /usr/local/share/man/man1/convert*

echo "=== Installing development tools and image libraries ==="
apt-get update
apt-get install -y build-essential wget

# Core image libraries
echo "Installing core image format libraries..."
apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libtiff-dev \
    libwebp-dev \
    libgif-dev

# Modern format support (HEIC, AVIF, JPEG-XL, JPEG2000, OpenEXR & camera raw)
echo "Installing modern format libraries..."
apt-get install -y \
    libheif-dev \
    libavif-dev \
    libjxl-dev \
    libopenjp2-7-dev \
    libopenexr-dev \
    libraw-dev

# / libheif reads & writes HEIC & AVIF through codecs. Debian 12 & Raspberry Pi OS build every codec into libheif1. Ubuntu 24.04 splits
# / each into a plugin package of its own & installs only the AV1 ones, so a HEIC was not read there, & neither a HEIC nor a HEIF
# / written, while ImageMagick still named both. Each plugin this release has is installed, & a release that names none has them built in.
# / libde265 reads HEVC, which HEIC & HEIF hold, x265 writes it, & aomenc writes AV1, which AVIF holds. libheif1 depends on an AV1 reader.
echo "Installing the libheif codecs this release packages separately..."
HEIF_PLUGINS=""
for HEIF_PLUGIN in libheif-plugin-libde265 libheif-plugin-x265 libheif-plugin-aomenc; do
    if apt-cache show "$HEIF_PLUGIN" > /dev/null 2>&1; then HEIF_PLUGINS="$HEIF_PLUGINS $HEIF_PLUGIN"; fi
done
if [ -n "$HEIF_PLUGINS" ]; then
    # shellcheck disable=SC2086
    apt-get install -y $HEIF_PLUGINS
else
    echo "This release packages no libheif codec separately, so libheif1 holds them."
fi

# Additional useful libraries
echo "Installing additional utility libraries..."
apt-get install -y \
    libfftw3-dev \
    libgs-dev \
    libxml2-dev \
    libfreetype6-dev \
    libfontconfig1-dev

echo "=== Downloading ImageMagick v7 source ==="
# / The source is fetched & built in a folder made new for this run under /var/tmp or TMPDIR,
# / that only root can open & that is removed however the build ends. Until v4.0.6 it was
# / fetched to fixed names in /tmp, where every account can write, so a folder another account
# / put there first was one root unpacked into & built from.
WORK="$(mktemp -d "${TMPDIR:-/var/tmp}/hrconvert2-imagemagick.XXXXXXXX")"
trap 'rm -rf "$WORK"' EXIT
cd "$WORK"

MAX_RETRIES=5
RETRY_COUNT=0

while [ $RETRY_COUNT -lt $MAX_RETRIES ]; do
    wget https://download.imagemagick.org/archive/ImageMagick.tar.gz && break
    RETRY_COUNT=$((RETRY_COUNT + 1))
    echo "Download attempt $RETRY_COUNT failed, retrying in 10 seconds..."
    sleep 10
done

if [ $RETRY_COUNT -eq $MAX_RETRIES ]; then
    echo "Failed to download after $MAX_RETRIES attempts"
    exit 1
fi

tar -xf ImageMagick.tar.gz
cd ImageMagick-7*

echo "=== Configuring ImageMagick v7 with modern format support ==="
echo "Configure flags:"
echo "  --with-quantum-depth=8    (8-bit color depth, standard)"
echo "  --with-heic=yes           (HEIC/HEIF support for iPhone photos)"
echo "  --with-avif=yes           (AVIF modern image codec)"
echo "  --with-jxl=yes            (JPEG XL next-gen compression)"
echo "  --with-openjp2=yes        (JPEG 2000 support)"
echo "  --with-openexr=yes        (OpenEXR high dynamic range images)"
echo "  --with-raw=yes            (camera raw images through LibRaw)"
echo "  --enable-shared           (Build shared libraries)"
echo ""

./configure \
    --with-quantum-depth=8 \
    --with-png=yes \
    --with-jpeg=yes \
    --with-tiff=yes \
    --with-webp=yes \
    --with-heic=yes \
    --with-avif=yes \
    --with-jxl=yes \
    --with-openjp2=yes \
    --with-openexr=yes \
    --with-raw=yes \
    --with-xml=yes \
    --with-freetype=yes \
    --with-fontconfig=yes \
    --enable-shared \
    --disable-static \
    --prefix=/usr/local

echo ""
echo "=== Compiling ImageMagick v7 ==="
echo "Using $(nproc) CPU cores for parallel compilation..."
make -j"$(nproc)"

echo ""
echo "=== Installing to system ==="
make install

echo ""
echo "=== Configuring library links ==="
ldconfig /usr/local/lib

echo ""
echo "========================================="
echo "=== VERIFICATION - ImageMagick v7.1 ==="
echo "========================================="
echo ""

# Get version info
echo "Version Information:"
magick --version

echo ""
echo "========================================="
echo "=== CHECKING MODERN FORMAT SUPPORT ==="
echo "========================================="
echo ""

# Extract delegates line from magick --version
DELEGATES=$(magick --version | grep "Delegates (built-in):")

echo "$DELEGATES"
echo ""

# Function to report whether a delegate is present.
# It reports & never fails, because ImageMagick is installed by now, & under set -e a missing delegate would end the script with an error.
check_delegate() {
    local format=$1
    local friendly=$2
    if echo "$DELEGATES" | grep -q "$format"; then
        echo "  ✓ $friendly"
    else
        echo "  ✗ $friendly (NOT FOUND - dependency may be missing)"
    fi
    return 0
}

# / Function to report whether ImageMagick reads, or reads & writes, a format, from the mode -list format gives it.
# / A delegate can be built in & still lack a codec. Until v4.2.9 AVIF was reported found when it could only be read, & no AVIF was
# / ever written. It reports & never fails, as check_delegate does.
check_format() {
    local format=$1
    local wanted=$2
    local friendly=$3
    local mode
    mode=$(magick -list format | awk -v format="$format" '{name = $1; sub(/\*$/, "", name)} name == format {for (i = 2; i <= NF; i++) if ($i ~ /^[r-][w-][+-]$/) {print $i; exit}}')
    if [ "$wanted" = "rw" ] && [ "${mode:0:2}" = "rw" ]; then
        echo "  ✓ $friendly, read & written"
    elif [ "$wanted" = "r" ] && [ "${mode:0:1}" = "r" ]; then
        echo "  ✓ $friendly, read"
    else
        echo "  ✗ $friendly (NOT FOUND - '${mode:-absent}' where '$wanted' was wanted, so a library or codec may be missing)"
    fi
    return 0
}

echo "Modern Format Support:"
check_delegate "heic" "HEIC/HEIF (iPhone photos, iPhone video thumbnails)"
check_delegate "jxl" "JPEG-XL (next-generation image compression)"
check_delegate "jp2" "JPEG2000 (high-quality archival format)"
check_delegate "openexr" "OpenEXR (high dynamic range images)"
check_delegate "raw" "LibRaw (camera raw images)"
# AVIF is read & written by the HEIC coder through libheif, so it is listed as a format & never as a delegate.
check_format "HEIC" "rw" "HEIC (HEVC through libheif)"
check_format "AVIF" "rw" "AVIF (AV1 through libheif)"
check_format "EXR" "rw" "EXR (OpenEXR)"
check_format "DNG" "r" "DNG & the camera raw formats LibRaw reads"

echo ""
echo "========================================="
echo "=== SUPPORTED INPUT IMAGE FORMATS ==="
echo "========================================="
echo ""

magick -list format | grep -E "^\s*[A-Z]+\s+r" | head -30

echo ""
echo "========================================="
echo "=== SUPPORTED OUTPUT IMAGE FORMATS ==="
echo "========================================="
echo ""

magick -list format | grep -E "^\s*[A-Z]+.*w" | head -30

echo ""
echo "========================================="
echo "=== INSTALLATION COMPLETE ==="
echo "========================================="
echo ""
echo "ImageMagick v7 is now installed with modern format support."
echo "Location: /usr/local/bin/magick"
echo ""
echo "Test image conversions:"
echo "  magick input.heic output.jpg"
echo "  magick input.jxl output.png"
echo "  magick input.jp2 output.bmp"
echo ""
