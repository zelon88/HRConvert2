# 💿 Creating Bootable ISO Images

HRConvert2 supports creating bootable system media using special operational intent suffix flags applied to target file selections.

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

## 🧭 About Bootable ISO Image Creation

When a user selects a bootable option from the application dropdown menu, the pipeline intercepts the request based on **five specific pseudo-extensions**:
* `*.iso_MBR-Boot`
* `*.iso_GPT-Boot-x86`
* `*.iso_GPT-Boot-x86-64`
* `*.iso_GPT-Boot-ARM32`
* `*.iso_GPT-Boot-ARM64`

### ⚙️ How It Works
1. **Intercept**: The core conversion engine processes the selected input archive or folder.
2. **Strip**: It strips away the custom tracking suffix from the destination path.
3. **Rectify**: It normalizes the filename to a pure standard `.iso` extension.
4. **Compile**: It builds a boot-compatible installation layout using a highly secure runtime sandbox.

> [!NOTE]
> By passing intent via file path naming structures, the state of the conversion remains standalone. The core application logic automatically drops the suffix tag immediately after processing, allowing downstream storage and execution procedures to complete without trailing extension artifacts.

---

## 🛠️ The Bootloader Injection Pipeline

The creation process adapts to existing environment contents or injects static fallback files in place before the filesystem is packed.

### 📁 1. Source Decompression
The pipeline extracts the uploaded source archive into a dedicated workspace folder on the host system filesystem.

### 💾 2. Bootloader Resolution & Asset Injection
The application verifies whether valid boot configurations already exist inside the workspace folder. If the structures are missing, the system injects pre-compiled fallback loaders out of its internal storage matrix directory (`$BootloadersDir`):

* **MBR Boot**  
  Leverages a shared real-mode 16-bit bootstrap execution setup (`isolinux.bin` and `ldlinux.c32`) that functions natively across **x86** and **x86-64**.
* **GPT / UEFI Boot**  
  Copies architecture-specific pre-baked FAT virtual filesystem image partitions directly into the workspace root tree. These images map boot loaders to the exact naming structures required by motherboard firmware:
  * `BOOTIA32.EFI`
  * `BOOTX64.EFI`
  * `BOOTARM.EFI`
  * `BOOTAA64.EFI`

### 🔄 3. In-Place Rectification
All asset placement calculations occur on the host system side directly within the local scratch directory. This updates the workspace structure entirely in place without altering global dependencies or relying on dangerous loop device mounts.

### 🔒 4. Sandboxed Compiling
The final `mkisofs` shell invocation runs inside a restricted **Bubblewrap** container namespace. Because the workspace folder is fully prepared in step 2, Bubblewrap functions with strict read-only access to the source data and write access to the output destination file path, securing the primary system.

