# -----------------------------------------------------------------------------------
# COPYRIGHT INFORMATION ...
# HRConvert2, Copyright on 5/17/2026 by Justin Grimes, www.github.com/zelon88

# LICENSE INFORMATION ...
# This project is protected by the GNU GPLv3 Open-Source license.
# https://www.gnu.org/licenses/gpl-3.0.html

# APPLICATION INFORMATION ...
# This application is designed to provide a web-interface for converting file formats
# on a server for users of any web browser without authentication.

# FILE INFORMATION ...
# v3.4.4.
# This file contains the Docker build file for the project.
# Build this with...
#   docker build -t HRConvert2 .
# Run this with...
#   docker run -d -p 8080:80 -p 8443:443 HRConvert2

# HARDWARE REQUIREMENTS ...
# This application requires at least a Raspberry Pi Model B+ or greater.
# This application will run on just about any x86 or x64 computer.

# DEPENDENCY REQUIREMENTS ...
# This application requires Debian Linux, Apache 2.4, PHP 8+, FFMPEG, Dia,
# Mkisofs, 7zip, LibreOffice, Unoconv, libgxps-utils, Tesseract, Unzip, Rar,
# Unrar, ClamAV, MeshLab, PopplerUtils, PDFTOTEXT, ImageMagick & xvfb-run.

# <3 Open-Source
# -----------------------------------------------------------------------------------

# Build this with...
#   docker build -t hrconvert2 .

# Run this with...
#   docker run -d -p 8080:80 -p 8443:443 hrconvert2

# Use the official PHP parent image.
FROM php:8.3-apache

# Create required directories.
RUN mkdir /DATA && \
  mkdir /DATA/HRConvert2

# Create & process a debian.sources file.
#RUN rm -vf /var/lib/apt/lists/*
RUN apt-get update

# Install git.
RUN apt-get install -y git sed

# Set the working directory in the container.
WORKDIR /var/www/html/HRProprietary

# Download the latest HRConvert2 source code from the official repository.
RUN git clone https://github.com/zelon88/HRConvert2

# Add non-free repos to software sources. Required for rar support.
# There are two options for accomplishing this. 
# Uncomment the option that suits your needs, and comment out the other.
#   The first option replaces the debian.sources file.
#   The second option modifies the existing debian.sources file in-place.
RUN cp HRConvert2/Documentation/Build/debian.sources /etc/apt/sources.list.d/debian.sources
#RUN sed -i -e's/ main/ main contrib non-free/g' /etc/apt/sources.list.d/debian.sources

# Re-process the updated debian.sources file.
RUN apt-get update

# Install dependencies.
#RUN apt-get upgrade
RUN apt-get install -y --allow-downgrades zlib1g=1:1.2.13.dfsg-1 zlib1g-dev=1:1.2.13.dfsg-1
RUN apt-get install -y libpng-dev
RUN apt-get install -y libzip-dev
RUN apt-get install -y libreoffice-common
RUN apt --fix-broken install
RUN apt-get install -y libreoffice-java-common
RUN apt-get install -y poppler-utils
RUN apt-get install -y clamav unoconv p7zip-full 
RUN apt-get install -y dia pandoc python3 zip unzip
RUN apt-get install -y xpdf mkisofs imagemagick
RUN apt-get install -y --allow-downgrades libx11-6=2:1.8.4-2+deb12u2
RUN apt-get install -y libgl1-mesa-glx 
RUN apt-get install -y xvfb
RUN apt-get install -y meshlab
RUN apt-get install -y tesseract-ocr tar libgxps-utils
RUN /bin/bash HRConvert2/Documentation/Build/ffmpeg-build2.sh --build --enable-gpl-and-non-free
RUN docker-php-ext-install gd zip
RUN rm -r packages
RUN rm -r workspace

## arm64 rar install hack (rar CLI only available for x86/x64 from rar labs)
RUN DPKG_ARCH="$(dpkg --print-architecture)"; \
    if [ "${DPKG_ARCH}" = "amd64" ]; then \
        apt-get install -y rar; \
    else \
        dpkg --add-architecture i386 && \
        apt-get install qemu-user-static libc6-i386-cross libstdc++6-i386-cross wget -y && \
        cp /usr/i686-linux-gnu/lib/libm.so.6 /lib && \
        cp /usr/i686-linux-gnu/lib/libc.so.6 /lib && \
        cp /usr/i686-linux-gnu/lib/libpthread.so.0 /lib && \
        cp /usr/i686-linux-gnu/lib/ld-linux.so.2 /lib && \
        cp /usr/i686-linux-gnu/lib/libstdc++.so.6 /lib && \
        cp /usr/i686-linux-gnu/lib/libgcc_s.so.1  /lib && \
        wget https://www.rarlab.com/rar/rarlinux-x32-700b3.tar.gz  -O /tmp/rarlinux-x32-700b3.tar.gz && \
        tar -zxvf /tmp/rarlinux-x32-700b3.tar.gz -C /tmp && \
        cp HRConvert2/Documentation/Build/rar-arm64 /tmp/rar-arm64 && \
        chmod +x /tmp/rar-arm64 &&  ln -s /tmp/rar-arm64 /usr/bin/rar; \
    fi

# Copy required files.
RUN cp HRConvert2/Documentation/Build/php.ini /usr/local/etc/php.ini
RUN cp HRConvert2/Documentation/Build/policy.xml /etc/ImageMagick-6/policy.xml
RUN cp HRConvert2/index.html /var/www/html/index.html
RUN cp HRConvert2/index.html /var/www/html/HRProprietary/index.html

# Set permissions for required directories.
RUN chmod -R 0755 /DATA && \
  chown -R www-data:www-data /DATA && \
  chmod -R 0755 /var/www/html && \
  chown -R www-data:www-data /var/www/html

# Expose the ports Apache listens on to the host.
# Set these to whatever ports suits your needs.
EXPOSE 80
EXPOSE 443

# Start Apache & Unoconv when the container runs.
CMD ["sh", "-c", "apache2-foreground && python3 /var/www/HRProprietary/HRConvert2/Resources/Unoconv/unoconv -l"]