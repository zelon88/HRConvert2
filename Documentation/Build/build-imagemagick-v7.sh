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
wget https://download.imagemagick.org/archive/ImageMagick.tar.gz
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
