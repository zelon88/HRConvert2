# -----------------------------------------------------------------------------------
# COPYRIGHT INFORMATION ...
# HRConvert2, Copyright on 8/13/2026 by Justin Grimes, www.github.com/zelon88
# 
# LICENSE INFORMATION ...
# This project is protected by the GNU GPLv3 Open-Source license.
# https://www.gnu.org/licenses/gpl-3.0.html
# 
# APPLICATION INFORMATION ...
# This application is designed to provide a web-interface for converting file formats
# on a server for users of any web browser without authentication.
# 
# FILE INFORMATION ...
# v3.6.6.
# This file contains the Docker build file for the project.
# Build this with...
#   docker build -t HRConvert2 .
# Run this with...
#   docker run -d -p 8080:80 -p 8443:443 HRConvert2
# 
# HARDWARE REQUIREMENTS ...
# This application requires at least a Raspberry Pi Model B+ or greater.
# This application will run on just about any x86 or x64 computer.
# 
# DEPENDENCY REQUIREMENTS ...
# This application requires Debian Linux, Apache 2.4, PHP 8+, FFMPEG, Dia, LibreOffice, 
# Mkisofs, 7zip, Unoconv, libgxps-utils, Tesseract, Unzip, OpenSCAD, Rar, Inkscape,
# Unrar, ClamAV, MeshLab, PopplerUtils, PDFTOTEXT, ImageMagick, bwrap & xvfb-run.
# 
# <3 Open-Source
# -----------------------------------------------------------------------------------

# Build this with...
#   docker build -t hrconvert2 .

# Run this with...
#   docker run -d -p 8080:80 -p 8443:443 hrconvert2

# Use the official PHP parent image.
#FROM php:8.5-apache
FROM php:8.5-apache-bookworm

# Create required directories.
RUN mkdir /DATA && \
  mkdir /DATA/HRConvert2

# Create & process a debian.sources file.
#RUN rm -vf /var/lib/apt/lists/*
RUN apt update

# Install git and compilation tools.
RUN apt install -y git sed libtool autoconf automake


# Set the working directory in the container.
WORKDIR /var/www/html/HRProprietary

# Download the latest HRConvert2 source code from the official repository.
ARG HRCONVERT2_REF=master
RUN git clone https://github.com/zelon88/HRConvert2 && \
    cd HRConvert2 && \
    git checkout ${HRCONVERT2_REF}

# Add non-free repos to software sources. Required for rar support.
# There are two options for accomplishing this. 
# Uncomment the option that suits your needs, and comment out the other.
#   The first option replaces the debian.sources file.
#   The second option modifies the existing debian.sources file in-place.
RUN cp HRConvert2/Documentation/Build/debian.sources /etc/apt/sources.list.d/debian.sources
#RUN sed -i -e's/ main/ main contrib non-free/g' /etc/apt/sources.list.d/debian.sources

# Install dependencies.
# The very first thing that must happen is apt-update.
#RUN apt-get update && apt-get install -y --allow-downgrades zlib1g=1:1.2.13.dfsg-1 zlib1g-dev=1:1.2.13.dfsg-1
RUN apt-get update && apt-get install -y zlib1g zlib1g-dev
RUN apt install -y libpng-dev
RUN apt install -y libzip-dev
RUN apt install -y libreoffice-common
RUN apt --fix-broken install
RUN apt install -y libreoffice-java-common
RUN apt install -y poppler-utils
RUN apt install -y clamav unoconv p7zip-full 
RUN apt install -y dia pandoc python3 zip unzip
RUN apt install -y xpdf mkisofs
RUN apt install -y libx11-6
RUN apt install -y libgl1-mesa-glx 
RUN apt install -y xvfb
RUN apt install -y meshlab
RUN apt install -y openscad
RUN apt install -y inkscape
RUN apt install -y bubblewrap
RUN apt install -y tesseract-ocr tar libgxps-utils
RUN /bin/bash HRConvert2/Documentation/Build/ffmpeg-build2.sh --build --enable-gpl-and-non-free
# FIX: Strip out all 'sudo ' commands from the downloaded script
RUN sed -i 's/sudo //g' HRConvert2/Documentation/Build/build-imagemagick-v7.sh
RUN /bin/bash HRConvert2/Documentation/Build/build-imagemagick-v7.sh
# This needs to take place AFTER all other zlib related packages are installed.
RUN docker-php-ext-install gd zip
RUN apt install -y default-jre
# I understand that the following line will probably uninstall some previous version of LibreOffice, 
#  but this seems to be the only way to get soffice.bin to work. I can't explain it.
RUN apt install -y libreoffice
RUN rm -r packages
RUN rm -r workspace

## arm64 rar install hack (rar CLI only available for x86/x64 from rar labs)
RUN DPKG_ARCH="$(dpkg --print-architecture)"; \
    if [ "${DPKG_ARCH}" = "amd64" ]; then \
        apt install -y rar; \
    else \
        dpkg --add-architecture i386 && \
        apt install qemu-user-static libc6-i386-cross libstdc++6-i386-cross wget -y && \
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
RUN mkdir -p /usr/local/etc/ImageMagick-7
RUN cp HRConvert2/Documentation/Build/policy.xml /usr/local/etc/ImageMagick-7/policy.xml
RUN cp HRConvert2/index.html /var/www/html/index.html
RUN cp HRConvert2/index.html /var/www/html/HRProprietary/index.html

# Create any missing directories safely.
RUN mkdir -p /DATA
RUN mkdir -p /DATA/HRConvert2
RUN mkdir -p /DATA/HRConvert2/Logs

# Set permissions for required directories.
RUN chmod -R 0755 /DATA && \
  chmod -R 0755 /var/www && \
  chown -R www-data:www-data /DATA && \
  chown -R www-data:www-data /var/www

# Expose the ports Apache & Unoconv listens on to the host.
# Set these to whatever ports suits your needs.
EXPOSE 80
EXPOSE 443

# Copy the startup script into the container
COPY Documentation/Build/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Start Apache & Unoconv when the container runs.
#CMD ["sh", "-c", "apache2-foreground"]
ENTRYPOINT ["sh", "-c", "mkdir -p /DATA/HRConvert2/Logs && chown -R www-data:www-data /DATA && exec /entrypoint.sh"]
