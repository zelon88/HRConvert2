<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRConvert2, Copyright on 8/24/2026 by Justin Grimes, www.github.com/zelon88
// /
// / LICENSE INFORMATION ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / APPLICATION INFORMATION ...
// / This application is designed to provide a web-interface for converting file formats
// / on a server for users of any web browser without authentication.
// /
// / FILEINFORMATION ...
// / v3.8.0.
// / HRConvert2 Dependency Manifest.
// / This file is data. It defines nothing & does nothing.
// / dependencyCore.php reads it. Nothing else may.
// /
// / $InstLoc is in scope while this file is read, because verifyDependsManifest declares it
// / global before the require. A bundled dependency uses it rather than a hardcoded path.
// /
// / THE ARRAY ORDER IS THE INSTALL ORDER. Read it top to bottom.
// / A dependency also declares what it Requires, which is checked before it is installed.
// / The order & the chain say the same thing twice on purpose. The order is what runs, the
// / chain is what catches an order somebody has broken.
// /
// / Each entry carries the following.
// /   Name             Human readable name, used in every report.
// /   Binary           The executable that proves it is installed. Empty for a library.
// /   Type             apt, source, pip or manual.
// /   Package          The package name for Type. Several may be space separated.
// /   MinimumVersion   The oldest version this release accepts. Empty means any.
// /   VersionCommand   A command that prints a version. Empty when there is nothing to ask.
// /   VersionPattern   A regular expression with one capture group holding the version.
// /   Required         TRUE refuses the subsystem when absent. FALSE downgrades it.
// /   Subsystem        Which part of HRConvert2 stops working without it.
// /   Requires         Names that must already be installed.
// /   License          The licence it ships under. Read by the supply chain audit.
// /   Source           Where it comes from. Read by the supply chain audit.
// /   Purpose          Why HRConvert2 needs it. Read by the supply chain audit.
// /
// / HARDWARE REQUIREMENTS ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / DEPENDENCY REQUIREMENTS ...
// / This application requires Debian Linux, Apache 2.4, PHP 8+, FFMPEG, Dia, LibreOffice, 
// / Mkisofs, 7zip, Unoconv, libgxps-utils, Tesseract, Unzip, OpenSCAD, Rar, Inkscape, Calibre,
// / Unrar, ClamAV, MeshLab, PopplerUtils, PDFTOTEXT, ImageMagick, bwrap Dia & xvfb-run.
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
// / The component version. convertCore.php reads this without executing the file.
$DependsVersion = 'v3.8.0';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
$DependsManifest = array(

  // / ---- Core. Nothing else can be installed or run without these. ----
  array('Name' => 'PHP', 'Binary' => 'php', 'Type' => 'apt', 'Package' => 'php-cli php-zip php-mbstring',
    'MinimumVersion' => '8.0', 'VersionCommand' => 'php -v', 'VersionPattern' => '/^PHP (\d+\.\d+)/',
    'Required' => TRUE, 'Subsystem' => 'Core', 'Requires' => array(),
    'License' => 'PHP-3.01', 'Source' => 'https://www.php.net', 'Purpose' => 'Runs the application.'),
  // / A PHP EXTENSION IS NOT PROVED BY THE PHP BINARY BEING PRESENT.
  // / php-zip is named in the PHP package list above, but a check that only asks the binary
  // / for its version reports PHP ok while an extension it needs is missing. An extension
  // / is asked for by name, from PHP itself, & is its own entry for that reason.
  array('Name' => 'PHP OpenSSL', 'Binary' => '', 'Type' => 'php-extension', 'Package' => 'openssl',
    'MinimumVersion' => '', 'VersionCommand' => 'php -r "exit(extension_loaded(\'openssl\') ? 0 : 1);"', 'VersionPattern' => '',
    'Required' => FALSE, 'Subsystem' => 'Resource Awareness', 'Requires' => array('PHP'),
    'License' => 'PHP-3.01', 'Source' => 'https://www.php.net/manual/en/book.openssl.php', 'Purpose' => 'Encrypts every message on a manager socket. Resource awareness cannot work without it & fails quietly rather than loudly.'),
  array('Name' => 'PHP Zip', 'Binary' => '', 'Type' => 'php-extension', 'Package' => 'zip',
    'MinimumVersion' => '', 'VersionCommand' => 'php -r "exit(extension_loaded(\'zip\') ? 0 : 1);"', 'VersionPattern' => '',
    'Required' => FALSE, 'Subsystem' => 'Documents', 'Requires' => array('PHP'),
    'License' => 'PHP-3.01', 'Source' => 'https://www.php.net/manual/en/book.zip.php', 'Purpose' => 'Opens OOXML documents so external references can be stripped before LibreOffice sees them. Without it the sandbox is the only protection.'),
  array('Name' => 'Apache', 'Binary' => 'apache2', 'Type' => 'apt', 'Package' => 'apache2 libapache2-mod-php',
    'MinimumVersion' => '2.4', 'VersionCommand' => 'apache2 -v', 'VersionPattern' => '/Apache\/(\d+\.\d+)/',
    'Required' => TRUE, 'Subsystem' => 'Core', 'Requires' => array('PHP'),
    'License' => 'Apache-2.0', 'Source' => 'https://httpd.apache.org', 'Purpose' => 'Serves the web interface.'),
  array('Name' => 'Curl', 'Binary' => 'curl', 'Type' => 'apt', 'Package' => 'curl',
    'MinimumVersion' => '7.0', 'VersionCommand' => 'curl --version', 'VersionPattern' => '/^curl (\d+\.\d+)/',
    'Required' => TRUE, 'Subsystem' => 'Core', 'Requires' => array(),
    'License' => 'curl', 'Source' => 'https://curl.se', 'Purpose' => 'Fetches update packages & remote streams.'),
  array('Name' => 'Git', 'Binary' => 'git', 'Type' => 'apt', 'Package' => 'git',
    'MinimumVersion' => '2.0', 'VersionCommand' => 'git --version', 'VersionPattern' => '/git version (\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'Updates', 'Requires' => array(),
    'License' => 'GPL-2.0', 'Source' => 'https://git-scm.com', 'Purpose' => 'Retrieves releases during an update.'),

  // / ---- Isolation. Installed before any converter, because converters run inside it. ----
  array('Name' => 'Bubblewrap', 'Binary' => 'bwrap', 'Type' => 'apt', 'Package' => 'bubblewrap',
    'MinimumVersion' => '0.4', 'VersionCommand' => 'bwrap --version', 'VersionPattern' => '/bubblewrap (\d+\.\d+)/',
    'Required' => TRUE, 'Subsystem' => 'Sandbox', 'Requires' => array(),
    'License' => 'LGPL-2.0', 'Source' => 'https://github.com/containers/bubblewrap', 'Purpose' => 'Isolates every conversion from the host & the network.'),
  array('Name' => 'AppArmor Utils', 'Binary' => 'apparmor_parser', 'Type' => 'apt', 'Package' => 'apparmor-utils',
    'MinimumVersion' => '', 'VersionCommand' => '', 'VersionPattern' => '',
    'Required' => FALSE, 'Subsystem' => 'Sandbox', 'Requires' => array('Bubblewrap'),
    'License' => 'GPL-2.0', 'Source' => 'https://gitlab.com/apparmor/apparmor', 'Purpose' => 'Loads the profile bubblewrap needs on a restricted kernel.'),

  // / ---- Archives. Needed early because other dependencies arrive compressed. ----
  array('Name' => '7-Zip', 'Binary' => '7z', 'Type' => 'apt', 'Package' => 'p7zip-full p7zip-rar',
    'MinimumVersion' => '23.01', 'VersionCommand' => '7z i', 'VersionPattern' => '/7-Zip.*?(\d+\.\d+)/',
    'Required' => TRUE, 'Subsystem' => 'Archives', 'Requires' => array(),
    'License' => 'LGPL-2.1', 'Source' => 'https://www.7-zip.org', 'Purpose' => 'Extracts every archive format offered.'),
  array('Name' => 'Zip', 'Binary' => 'zip', 'Type' => 'apt', 'Package' => 'zip unzip',
    'MinimumVersion' => '3.0', 'VersionCommand' => 'zip -v', 'VersionPattern' => '/Zip (\d+\.\d+)/',
    'Required' => TRUE, 'Subsystem' => 'Archives', 'Requires' => array(),
    'License' => 'Info-ZIP', 'Source' => 'http://infozip.sourceforge.net', 'Purpose' => 'Creates zip archives.'),
  array('Name' => 'Tar', 'Binary' => 'tar', 'Type' => 'apt', 'Package' => 'tar',
    'MinimumVersion' => '1.30', 'VersionCommand' => 'tar --version', 'VersionPattern' => '/tar.*?(\d+\.\d+)/',
    'Required' => TRUE, 'Subsystem' => 'Archives', 'Requires' => array(),
    'License' => 'GPL-3.0', 'Source' => 'https://www.gnu.org/software/tar', 'Purpose' => 'Creates & extracts tar archives.'),
  array('Name' => 'RAR', 'Binary' => 'rar', 'Type' => 'manual', 'Package' => 'rar',
    'MinimumVersion' => '5.0', 'VersionCommand' => 'rar', 'VersionPattern' => '/RAR (\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'Archives', 'Requires' => array(),
    'License' => 'Proprietary', 'Source' => 'https://www.rarlab.com', 'Purpose' => 'Creates RAR archives. Licence forbids redistribution, so it is never installed automatically.'),

  // / ---- Media. ----
  array('Name' => 'FFMPEG', 'Binary' => 'ffmpeg', 'Type' => 'apt', 'Package' => 'ffmpeg',
    'MinimumVersion' => '6.1', 'VersionCommand' => 'ffmpeg -version', 'VersionPattern' => '/ffmpeg version n?(\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'Audio, Video & Streams', 'Requires' => array('Bubblewrap'),
    'License' => 'LGPL-2.1', 'Source' => 'https://ffmpeg.org', 'Purpose' => 'Converts audio, video, subtitles & captures streams.'),

  // / ---- Documents. ----
  array('Name' => 'LibreOffice', 'Binary' => 'soffice', 'Type' => 'apt', 'Package' => 'libreoffice libreoffice-java-common',
    'MinimumVersion' => '7.0', 'VersionCommand' => 'soffice --version', 'VersionPattern' => '/LibreOffice (\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'Documents', 'Requires' => array('Bubblewrap'),
    'License' => 'MPL-2.0', 'Source' => 'https://www.libreoffice.org', 'Purpose' => 'Converts documents, spreadsheets & presentations.'),
  array('Name' => 'Poppler Utils', 'Binary' => 'pdftotext', 'Type' => 'apt', 'Package' => 'poppler-utils',
    'MinimumVersion' => '20.09', 'VersionCommand' => 'pdftotext -v', 'VersionPattern' => '/pdftotext version (\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'Documents & OCR', 'Requires' => array(),
    'License' => 'GPL-2.0', 'Source' => 'https://poppler.freedesktop.org', 'Purpose' => 'Extracts text & converts XPS documents.'),

  // / ---- Images. ----
  array('Name' => 'ImageMagick', 'Binary' => 'magick', 'Type' => 'apt', 'Package' => 'imagemagick',
    'MinimumVersion' => '7.1', 'VersionCommand' => 'magick -version', 'VersionPattern' => '/ImageMagick (\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'Images', 'Requires' => array('Bubblewrap'),
    'License' => 'ImageMagick', 'Source' => 'https://imagemagick.org', 'Purpose' => 'Converts raster images & splits pages for OCR.'),
  array('Name' => 'Inkscape', 'Binary' => 'inkscape', 'Type' => 'apt', 'Package' => 'inkscape',
    'MinimumVersion' => '1.2', 'VersionCommand' => 'inkscape --version', 'VersionPattern' => '/Inkscape (\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'SVG', 'Requires' => array('Bubblewrap'),
    'License' => 'GPL-3.0', 'Source' => 'https://inkscape.org', 'Purpose' => 'Converts vector graphics.'),
  array('Name' => 'Dia', 'Binary' => 'dia', 'Type' => 'apt', 'Package' => 'dia',
    'MinimumVersion' => '0.97', 'VersionCommand' => 'dia --version', 'VersionPattern' => '/Dia version (\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'Drawings', 'Requires' => array('Bubblewrap'),
    'License' => 'GPL-2.0', 'Source' => 'https://wiki.gnome.org/Apps/Dia', 'Purpose' => 'Converts diagrams.'),

  // / ---- Models. ----
  array('Name' => 'OpenSCAD', 'Binary' => 'openscad', 'Type' => 'apt', 'Package' => 'openscad',
    'MinimumVersion' => '2021.01', 'VersionCommand' => 'openscad --version', 'VersionPattern' => '/OpenSCAD version (\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'SCAD', 'Requires' => array('Bubblewrap'),
    'License' => 'GPL-2.0', 'Source' => 'https://openscad.org', 'Purpose' => 'Renders parametric models.'),
  array('Name' => 'Assimp', 'Binary' => 'assimp', 'Type' => 'apt', 'Package' => 'assimp-utils',
    'MinimumVersion' => '5.0', 'VersionCommand' => 'assimp version', 'VersionPattern' => '/[Vv]ersion\s+v?(\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => '3D Models', 'Requires' => array('Bubblewrap'),
    'License' => 'BSD-3-Clause', 'Source' => 'https://github.com/assimp/assimp', 'Purpose' => 'Converts between 3D model formats.'),
  // / PyMeshLab is BUNDLED at Resources/PyMeshLab & is imported by inserting that directory
  // / onto the python path, exactly as convertModels does. A bare import cannot see it, so
  // / probing for one reported a dependency that ships with the application as absent.
  // / Type is bundled, so nothing tries to install or remove it.
  // / __version__ is not exposed by every build, so presence is proved by the import alone.
  array('Name' => 'PyMeshLab', 'Binary' => '', 'Type' => 'bundled', 'Package' => '',
    'MinimumVersion' => '', 'VersionCommand' => 'python3 -c "import sys; sys.path.insert(0, \''.$InstLoc.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'PyMeshLab\'); import pymeshlab; print(\'bundled\')"', 'VersionPattern' => '',
    'Required' => FALSE, 'Subsystem' => '3D Models', 'Requires' => array('Assimp'),
    'License' => 'GPL-3.0', 'Source' => 'Bundled at Resources/PyMeshLab', 'Purpose' => 'Repairs & simplifies meshes. Replaces MeshLab. Ships with HRConvert2.'),
  array('Name' => 'MeshLab', 'Binary' => 'meshlabserver', 'Type' => 'apt', 'Package' => 'meshlab',
    'MinimumVersion' => '2020.09', 'VersionCommand' => 'meshlabserver --version', 'VersionPattern' => '/(\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => '3D Models', 'Requires' => array('Assimp'),
    'License' => 'GPL-3.0', 'Source' => 'https://www.meshlab.net', 'Purpose' => 'Mesh processing. Only needed when PyMeshLab is disabled.'),

  // / ---- Optical character recognition. ----
  array('Name' => 'Tesseract', 'Binary' => 'tesseract', 'Type' => 'apt', 'Package' => 'tesseract-ocr tesseract-ocr-eng',
    'MinimumVersion' => '4.0', 'VersionCommand' => 'tesseract --version', 'VersionPattern' => '/tesseract (\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'OCR', 'Requires' => array('ImageMagick', 'Poppler Utils'),
    'License' => 'Apache-2.0', 'Source' => 'https://github.com/tesseract-ocr/tesseract', 'Purpose' => 'Reads text out of images & scanned documents.'),

  // / ---- Optical disc images. ----
  array('Name' => 'Genisoimage', 'Binary' => 'mkisofs', 'Type' => 'apt', 'Package' => 'genisoimage',
    'MinimumVersion' => '1.1', 'VersionCommand' => 'mkisofs -version', 'VersionPattern' => '/mkisofs (\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'Disc Images', 'Requires' => array('Bubblewrap'),
    'License' => 'GPL-2.0', 'Source' => 'https://wiki.debian.org/genisoimage', 'Purpose' => 'Builds ISO images from an archive.'),
  array('Name' => 'Syslinux Utils', 'Binary' => 'isohybrid', 'Type' => 'apt', 'Package' => 'syslinux-utils',
    'MinimumVersion' => '0.12', 'VersionCommand' => 'isohybrid --version', 'VersionPattern' => '/(\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'Disc Images', 'Requires' => array('Genisoimage'),
    'License' => 'GPL-2.0', 'Source' => 'https://wiki.syslinux.org', 'Purpose' => 'Makes an ISO image bootable.'),

  // / ---- Ebooks. ----
  array('Name' => 'Calibre', 'Binary' => 'ebook-convert', 'Type' => 'apt', 'Package' => 'calibre',
    'MinimumVersion' => '9.13', 'VersionCommand' => 'ebook-convert --version', 'VersionPattern' => '/calibre (\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'Ebooks', 'Requires' => array('Bubblewrap'),
    'License' => 'GPL-3.0', 'Source' => 'https://calibre-ebook.com', 'Purpose' => 'Converts between ebook formats.'),

  // / ---- Optional scanning. ----
  array('Name' => 'ClamAV', 'Binary' => 'clamscan', 'Type' => 'apt', 'Package' => 'clamav clamav-daemon',
    'MinimumVersion' => '0.103', 'VersionCommand' => 'clamscan --version', 'VersionPattern' => '/ClamAV (\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'Virus Scanning', 'Requires' => array(),
    'License' => 'GPL-2.0', 'Source' => 'https://www.clamav.net', 'Purpose' => 'Scans uploads. Only used when virus scanning is enabled.'));
// / -----------------------------------------------------------------------------------