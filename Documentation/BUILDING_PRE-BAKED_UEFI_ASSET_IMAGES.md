# 📦 Building Pre-Baked UEFI Asset Images

This guide describes how to generate the four architecture-specific `*.img` files required by the HRConvert2 bootable ISO pipeline. 

Because the target production server lacks `dosfstools`, we compile these static filesystem images **once on a development machine** and bundle them directly into the `$BootloadersDir` folder.

---

## 🛠️ Core Infrastructure Specifications

### Legal & Distribution
* **Intellectual Property:** HRConvert2 is Copyright © 2026 by Justin Grimes ([GitHub](https://www.github.com/zelon88)).
* **Licensing Model:** Protected under the strict terms of the **GNU GPLv3 Open-Source License**. Refer to the [Official License Portal](https://www.gnu.org/licenses/gpl-3.0.html) for distribution requirements.

### System Intent
This application provides a highly accessible, unauthenticated web interface enabling file conversions directly on a server infrastructure using any modern web browser.

| Metric | Minimum Operational Requirements |
| :--- | :--- |
| **Hardware** | Raspberry Pi Model B+ (or any standard x86 / x64 architecture computing platform) |
| **Environment** | Debian Linux + Apache Web Server v2.4 + PHP v8.0 or greater |

> ⚙️ **Core Dependencies:** FFMPEG, Dia, LibreOffice, Mkisofs, 7zip, Unoconv, libgxps-utils, Tesseract, Unzip, OpenSCAD, Rar, Inkscape, Unrar, ClamAV, MeshLab, PopplerUtils, PDFTOTEXT, ImageMagick, Assimp, Bwrap, Dia, and xvfb-run.

---

## 🧰 Development Prerequisites

Ensure your development machine (Debian/Ubuntu) has the required tools installed to build loop images without root privileges:

```bash
sudo apt update && sudo apt install -y dosfstools mtools
```

---

## 🛠️ Step-by-Step Generation Script

You can save and run the following bash script on your development machine to build all four required assets automatically.

```bash
#!/usr/bin/env bash

# [generateBootloadersForBootableIsoSupport.sh]
# [Part of HRConvert2 by zelon88 - https://github.com/zelon88/HRConvert2]
# [Full File Available At 'Documentation/Build/generateBootloadersForBootableIsoSupport.sh']
# [Full Header Text Omitted]
# [Bootloader Generator - v3.7.2]

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
    
    touch /tmp/stub_loader.efi
    mcopy -i "$IMG_PATH" /tmp/stub_loader.efi "::/EFI/BOOT/${EFI_FILE}"
    rm -f /tmp/stub_loader.efi
    
    echo "Success: $IMG_NAME generated."
done

echo ""
echo "=========================================================================="
echo " COPY AND PASTE THE FOLLOWING PHP DEFINITIONS INTO YOUR CONFIGURATION"
echo "=========================================================================="
echo ""

# / Extract SHA256 hashes cleanly using awk to cut the filename off.
HASH_BLANK=$(sha256sum "$OUTPUT_DIR/blank_efi_2880.img" | awk '{print $1}')
HASH_X86=$(sha256sum "$OUTPUT_DIR/uefi_ia32.img" | awk '{print $1}')
HASH_X64=$(sha256sum "$OUTPUT_DIR/uefi_x64.img" | awk '{print $1}')
HASH_ARM32=$(sha256sum "$OUTPUT_DIR/uefi_arm.img" | awk '{print $1}')
HASH_ARM64=$(sha256sum "$OUTPUT_DIR/uefi_arm64.img" | awk '{print $1}')

echo "\$RequiredBlankHash = '$HASH_BLANK';"
echo "\$Requiredx86Hash = '$HASH_X86';"
echo "\$Requiredx8664Hash = '$HASH_X64';"
echo "\$RequiredARM32Hash = '$HASH_ARM32';"
echo "\$RequiredARM64Hash = '$HASH_ARM64';"
echo ""
echo "=========================================================================="

```

---

## 📂 Bundled Distribution Layout

Once the script completes, verify that the output files are structured exactly as shown below. 
The isolinux/isolinux.bin file can be obtained from a linux machine with isolinux installed.
Hash the isolinux file using the `sha256sum isolinux.bin` command.

All bootloader files mush be hashed and hased added to the core they can be used for generating bootable iso images:

```text
\$BootloadersDir/
├── isolinux/isolinux.bin       <- Copy from your system's syslinux package
├── isolinux/extra/ldlinux.c32  <- Copy from your system's syslinux package
├── uefi_ia32.img               <- 2.88MB FAT Image (Contains \EFI\BOOT\BOOTIA32.EFI)
├── uefi_x64.img                <- 2.88MB FAT Image (Contains \EFI\BOOT\BOOTX64.EFI)
├── uefi_arm.img                <- 2.88MB FAT Image (Contains \EFI\BOOT\BOOTARM.EFI)
└── uefi_arm64.img              <- 2.88MB FAT Image (Contains \EFI\BOOT\BOOTAA64.EFI)
```

Once the script completes, verify that the output files are structured exactly as shown above. 

The isolinux/isolinux.bin file can be obtained from a linux machine with `isolinux` installed.
  It is located in `/usr/lib/ISOLINUX/`
  To find it, run `find / -name isolinux.bin 2>/dev/null`
  Copy `isolinux.bin` to `/var/www/html/HRProprietary/HRConvert2/Resources/Bootloaders/isolinux`
  Hash the isolinux file using the `sha256sum /var/www/html/HRProprietary/HRConvert2/Resources/Bootloaders/isolinux/isolinux.bin` command.


The isolinux/extra/ldlinux.c32 file can be obtained from a linux machine with `syslinux-common` installed.
  It is located in `/usr/lib/syslinux/modules/bios/`
  To find it, run `find / -name isolinux.bin 2>/dev/null`
  Copy `ldlinux.c32` to `/var/www/html/HRProprietary/HRConvert2/Resources/Bootloaders/isolinux/extra`
  Hash the isolinux file using the `sha256sum /var/www/html/HRProprietary/HRConvert2/Resources/Bootloaders/isolinux/extra/ldlinux.c32` command.
 

All bootloader files mush be hashed and hased added to the core they can be used for generating bootable iso images.

---

## 💡 Pipeline Operational Notes

### 🔄 Empty Payload Compatibility
Modern consumer computer motherboards executing in UEFI mode only require that a valid **FAT filesystem cluster signature** and **proper internal file tree structures** are detectable on the El Torito boot sector. 

Even if the internal `.EFI` executable payload remains a 0-byte structural placeholder asset inside your base package distribution, `mkisofs` will generate a valid layout. Advanced users can drop their custom compiled setup scripts or payloads directly into the workspace folder to overwrite these defaults.
