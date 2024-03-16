# -----------------------------------------------------------------------------------
# COPYRIGHT INFORMATION ...
# HRConvert2, Copyright on 3/12/2024 by Justin Grimes, www.github.com/zelon88

# LICENSE INFORMATION ...
# This project is protected by the GNU GPLv3 Open-Source license.
# https://www.gnu.org/licenses/gpl-3.0.html

# APPLICATION INFORMATION ...
# This application is designed to provide a web-interface for converting file formats
# on a server for users of any web browser without authentication.

# FILE INFORMATION ...
# v3.3.3.
# This file contains the Docker build file for the project.
# Build this with...
#   docker build -t HRConvert2 .
# Run this with...
#   docker run -d -p 8080:80 -p 8443:443 HRConvert2

# HARDWARE REQUIREMENTS ...
# This application requires at least a Raspberry Pi Model B+ or greater.
# This application will run on just about any x86 or x64 computer.

# DEPENDENCY REQUIREMENTS ...
# This application requires Debian Linux (w/3rd Party audio license),
# Apache 2.4, PHP 8+, LibreOffice, Unoconv, ClamAV, Tesseract, Rar, Unrar, Unzip,
# 7zipper, FFMPEG, PDFTOTEXT, Dia, PopplerUtils, MeshLab, Mkisofs & ImageMagick.

# <3 Open-Source
# -----------------------------------------------------------------------------------

# Build this with...
#   docker build -t hrconvert2 .

# Run this with...
#   docker run -d -p 8080:80 -p 8443:443 hrconvert2

# Use the official PHP parent image.
FROM php:8.1-apache

# Create required directories.
RUN mkdir /DATA && \
  mkdir /DATA/HRConvert2

# Set permissions for required directories.
RUN chmod -R 0755 /DATA && \
  chown -R www-data:www-data /DATA && \
  chmod -R 0755 /var/www/html && \
  chown -R www-data:www-data /var/www/html

# Set the working directory in the container.
WORKDIR /var/www/html/HRProprietary

# Create & process a debian.sources file.
RUN apt-get update

# Install git.
RUN apt-get install -y git sed

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
RUN apt-get install -y zlib1g-dev
RUN apt-get install -y libpng-dev
RUN apt-get install -y libzip-dev
RUN docker-php-ext-install gd zip
RUN apt-get install -y libreoffice-common default-jre libreoffice-java-common poppler-utils
RUN apt-get install -y clamav unoconv p7zip-full meshlab dia pandoc python3 zip unzip rar ffmpeg
RUN apt-get install -y xpdf mkisofs imagemagick meshlab tesseract-ocr

# Copy required files.
RUN cp HRConvert2/Documentation/Build/php.ini /usr/local/etc/php.ini
RUN cp HRConvert2/index.html /var/www/html/index.html
RUN cp HRConvert2/index.html /var/www/html/HRProprietary/index.html

# Expose the ports Apache listens on to the host.
# Set these to whatever ports suits your needs.
EXPOSE 80
EXPOSE 443

# Start Apache & Unoconv when the container runs.
CMD ["sh", "-c", "apache2-foreground && python3 /usr/bin/unoconv -l"]
