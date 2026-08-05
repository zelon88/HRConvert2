#!/bin/bash

# -----------------------------------------------------------------------------------
# COPYRIGHT INFORMATION ...
# HRConvert2, Copyright on 5/18/2026 by Justin Grimes, www.github.com/zelon88

# LICENSE INFORMATION ...
# This project is protected by the GNU GPLv3 Open-Source license.
# https://www.gnu.org/licenses/gpl-3.0.html

# APPLICATION INFORMATION ...
# This application is designed to provide a web-interface for converting file formats
# on a server for users of any web browser without authentication.

# FILE INFORMATION ...
# v3.4.5.
# This file helps build FFMPEG from source to enable more advanced file operations.

# HARDWARE REQUIREMENTS ...
# This application requires at least a Raspberry Pi Model B+ or greater.
# This application will run on just about any x86 or x64 computer.

# DEPENDENCY REQUIREMENTS ...
# This application requires Debian Linux, Apache 2.4, PHP 8+, FFMPEG, Dia,
# Mkisofs, 7zip, LibreOffice, Unoconv, libgxps-utils, Tesseract, Unzip, Rar,
# Unrar, ClamAV, MeshLab, PopplerUtils, PDFTOTEXT, ImageMagick & xvfb-run.

# <3 Open-Source
# -----------------------------------------------------------------------------------

# Helper script to download and run the build-ffmpeg script.

make_dir () {
    if [ ! -d $1 ]; then
        if ! mkdir $1; then            
            printf "\n Failed to create dir %s" "$1";
            exit 1
        fi
    fi    
}

command_exists() {
    if ! [[ -x $(command -v "$1") ]]; then
        return 1
    fi

    return 0
}

TARGET='ffmpeg-build'

if ! command_exists "curl"; then
    echo "curl not installed.";
    exit 1
fi

echo "ffmpeg-build-script-downloader v0.1"
echo "========================================="
echo ""

echo "First we create the ffmpeg build directory $TARGET"
make_dir $TARGET
cd $TARGET

echo "Now we download and execute the build script"
echo ""

bash ../ffmpeg-build2.sh --build --enable-gpl-and-non-free

