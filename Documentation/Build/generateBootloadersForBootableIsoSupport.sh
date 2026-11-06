#!/usr/bin/env bash

# -----------------------------------------------------------------------------------
# COPYRIGHT INFORMATION ...
# HRConvert2, Copyright on 8/16/2026 by Justin Grimes, www.github.com/zelon88
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
# v3.7.3.
# This file contains a script to generate Bootloader images for bootable iso support.
#
# HARDWARE REQUIREMENTS ...
# This application requires at least a Raspberry Pi Model B+ or greater.
# This application will run on just about any x86 or x64 computer.
#
# DEPENDENCY REQUIREMENTS ...
# The authoritative list of dependencies is Resources/Contract/depends.php & nothing else.
# Run  php convertCore.php -v  to see what this installation has.
#
# <3 Open-Source
# -----------------------------------------------------------------------------------


set -e

OUTPUT_DIR="/var/www/html/HRProprietary/HRConvert2/Resources/Bootloaders"
mkdir -p "$OUTPUT_DIR"

echo "Starting Legacy Boot Asset Build Process..."

# / Create the raw baseline floppy emulation file image.
dd if=/dev/zero of="$OUTPUT_DIR"/blank_efi_2880.img bs=1k count=2880 2>/dev/null
mkfs.vfat "$OUTPUT_DIR"/blank_efi_2880.img > /dev/null

# / Initialize the standard required internal UEFI file tree.
mmd -i "$OUTPUT_DIR"/blank_efi_2880.img ::/EFI
mmd -i "$OUTPUT_DIR"/blank_efi_2880.img ::/EFI/BOOT

declare -A ARCHITECTURES=(
    ["ia32"]="BOOTIA32.EFI"
    ["x64"]="BOOTX64.EFI"
    ["arm"]="BOOTARM.EFI"
    ["arm64"]="BOOTAA64.EFI"
)

echo "Starting UEFI Asset Build Process..."

for KEY in "${!ARCHITECTURES[@]}"; do
    IMG_NAME="uefi_${KEY}.img"
    EFI_FILE="${ARCHITECTURES[$KEY]}"
    IMG_PATH="${OUTPUT_DIR}/${IMG_NAME}"
    
    echo "Building Asset: $IMG_NAME (Target: $EFI_FILE)"
    
    dd if=/dev/zero of="$IMG_PATH" bs=1k count=2880 2>/dev/null
    mkfs.vfat "$IMG_PATH" > /dev/null
    
    mmd -i "$IMG_PATH" ::/EFI
    mmd -i "$IMG_PATH" ::/EFI/BOOT
    
    # / The stub is made by mktemp. A fixed name in /tmp could be made first by any account,
    # / & whatever it held would be copied into the image as its boot loader & then pinned.
    STUB_LOADER=$(mktemp)
    mcopy -i "$IMG_PATH" "$STUB_LOADER" "::/EFI/BOOT/${EFI_FILE}"
    rm -f "$STUB_LOADER"
    
    echo "Success: $IMG_NAME generated."
done

echo ""
echo "=========================================================================="
echo " THESE HASHES PIN THE IMAGES THIS RUN JUST WROTE."
echo " mkfs.vfat writes a new volume serial every time, so every run differs."
echo " Paste each into the matching 'hash' of \$assetMap in verifyIsoBootloaders(),"
echo " in Resources/Pipelines/Archive/pipeline.php. That changes the Archive"
echo " pipeline, so raise its \$PipelineVersion & its pin in"
echo " Resources/Contract/app-engine-config.php as well."
echo "=========================================================================="
echo ""

# / Extract SHA256 hashes cleanly using awk to cut the filename off.
HASH_BLANK=$(sha256sum "$OUTPUT_DIR/blank_efi_2880.img" | awk '{print $1}')
HASH_X86=$(sha256sum "$OUTPUT_DIR/uefi_ia32.img" | awk '{print $1}')
HASH_X64=$(sha256sum "$OUTPUT_DIR/uefi_x64.img" | awk '{print $1}')
HASH_ARM32=$(sha256sum "$OUTPUT_DIR/uefi_arm.img" | awk '{print $1}')
HASH_ARM64=$(sha256sum "$OUTPUT_DIR/uefi_arm64.img" | awk '{print $1}')

echo "  blank_efi    $HASH_BLANK"
echo "  uefi_ia32    $HASH_X86"
echo "  uefi_x64     $HASH_X64"
echo "  uefi_arm     $HASH_ARM32"
echo "  uefi_arm64   $HASH_ARM64"
echo ""
echo "=========================================================================="