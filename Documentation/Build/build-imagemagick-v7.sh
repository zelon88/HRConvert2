#!/usr/bin/env bash

# Exit immediately if a command exits with a non-zero status
set -e

echo "=== Removing old ImageMagick versions ==="
apt-get purge -y imagemagick php-imagick
apt-get autoremove -y

echo "=== Installing development tools and image libraries ==="
apt-get update
apt-get install -y build-essential libpng-dev libjpeg-dev libtiff-dev libwebp-dev libgif-dev wget

echo "=== Downloading ImageMagick v7 source ==="
cd /tmp
# Remove old downloads if they exist
rm -f ImageMagick.tar.gz
rm -rf ImageMagick-7*

# / Route directly to the verified rolling archive repository link location.

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

echo "=== Configuring and compiling ImageMagick v7 ==="
./configure
make -j$(nproc)

echo "=== Installing to system ==="
make install

echo "=== Configuring library links ==="
ldconfig /usr/local/lib

echo "=== Verification ==="
magick --version

echo "=== Installation complete successfully! ==="
