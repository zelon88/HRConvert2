<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRConvert2, Copyright on 8/31/2026 by Justin Grimes, www.github.com/zelon88
// /
// / LICENSE INFORMATION ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / APPLICATION INFORMATION ...
// / This application is designed to provide a web-interface for converting file formats
// / on a server for users of any web browser without authentication.l
// /
// / FILE INFORMATION ...
// / v3.8.7.
// / This file contains the current HRConvert2 version for update verification purposes.
// /
// / HARDWARE REQUIREMENTS ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / DEPENDENCY REQUIREMENTS ...
// / This application requires Debian Linux (w/3rd Party audio license), Apache 2.4,
// / PHP 8+, LibreOffice, Unoconv, ClamAV, Tesseract,  Unzip, FFMPEG, Mkisofs, 7zip,
// / Rar, Unrar, libgxps-utils, PopplerUtils, MeshLab, PDFTOTEXT, Dia, ImageMagick.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Refuse direct execution. This file is a component & has no standalone context.
// / This halt cannot use quickDie. Reaching this line means convertCore.php was never
// / loaded, so quickDie is not defined & calling it would replace a clear refusal with an
// / undefined function error.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-2: This file cannot process your request! Please submit your file to convertCore.php instead!'.PHP_EOL);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The version of this HRConvert2 installation.
$Version = 'v3.8.6';
$Version = ltrim($Version, 'vV');
// / -----------------------------------------------------------------------------------
