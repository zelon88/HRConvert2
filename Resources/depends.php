<?php
// / -----------------------------------------------------------------------------------
// / Copyright information ...
// / HRConvert2, Copyright on 8/28/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / Application information ...
// / This application is designed to provide a web-interface for converting file formats
// / on a server for users of any web browser without authentication.
// /
// / Fileinformation ...
// / v3.8.1.
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
// /   BuildCommand     For a source Type. The shell command that builds & installs it.
// /
// / A bundled Type is a microservice this project distributes rather than a package this
// / project installs. It is never installed, never updated & never removed.
// / It is listed so --check-depends, -v & the supply chain audit can all see it, & so that
// / anything needing it can name it in its own Requires list.
// / PyMeshLab & ScanCore are both bundled.
// /
// / A source Type was declared here & refused by Dependency Core until v3.8.9.
// / Anything outside a package repository therefore had to be installed by a hand written
// / script kept in step with this manifest by hand, which is the arrangement this file
// / exists to replace.
// / A build command runs as root, takes minutes & prints a great deal while it works.
// / Only the tail of its output is kept, because a compiler log helps nobody in a report.
// / A build that reports success is still only believed once VersionCommand answers.
// /
// / Capability fields. Every one is optional & an entry that omits them is not less
// / correct, it is a dependency whose formats cannot be asked for.
// /   CapabilityCommand    A command that prints what the tool can read & write.
// /   CapabilityStyle      matrix or list. How the output is shaped, not what it holds.
// /   CapabilityPattern    The expression that reads it. Its meaning follows the style.
// /   CapabilityDirection  For a list style. read, write, or readwrite.
// /   CapabilityAliases    Maps the token a tool prints to the extension a user types.
// /
// / An entry with no CapabilityCommand resolves to unknown, which is a third state & not
// / an empty one. Unknown means the pipeline declaration stands untouched. Half of these
// / dependencies cannot enumerate anything. LibreOffice, Inkscape, Dia & OpenSCAD have no
// / format list command at all, & reading that silence as a report of no capability would
// / delete every document conversion on a working installation.
// /
// / A command that runs & returns nothing is a parse failure rather than a report of no
// / capability, & it resolves to unknown for the same reason.
// /
// / Reading a format is not writing it & the two are stored separately.
// / Assimp reads OFF & has no OFF exporter. An earlier release routed on the input
// / extension alone, sent an obj to off conversion to Assimp, & retried a command that
// / could never succeed until it timed out. ImageMagick & FFMPEG each report both
// / directions in one line. Assimp reports them from two separate commands.
// /
// / A capability list is a FILTER & is never a source of new formats.
// / magick -list format offers MSL, a scripting language ImageMagick executes, & http,
// / https & file, which fetch a URL without passing through any guard this application
// / owns. Detection narrows what a pipeline already declared. It never adds to it.
// /
// / Hardware requirements ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / Dependency requirements ...
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
$DependsVersion = 'v3.9.0';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
$DependsManifest = array(

  // / ---- Core. Nothing else can be installed or run without these. ----
  array('Name' => 'PHP', 'Binary' => 'php', 'Type' => 'apt', 'Package' => 'php-cli php-zip php-mbstring',
    'MinimumVersion' => '8.0', 'VersionCommand' => 'php -v', 'VersionPattern' => '/^PHP (\d+\.\d+)/',
    'Required' => TRUE, 'Subsystem' => 'Core', 'Requires' => array(),
    'License' => 'PHP-3.01', 'Source' => 'https://www.php.net', 'Purpose' => 'Runs the application.'),
  // / A PHP extension is not proved by the PHP binary being present.
  // / php-zip is Required because it backs a security filter rather than a feature.
  // / sanitizeDocumentLinks() opens an OOXML document with ZipArchive to strip external
  // / references before LibreOffice is allowed to see it. Without the extension that
  // / function warns & passes the document through unfiltered, leaving the sandbox as the
  // / only thing standing between a crafted document & the machine.
  // / php-zip is named in the PHP package list above, but a check that only asks the binary
  // / for its version reports PHP ok while an extension it needs is missing. An extension
  // / is asked for by name, from PHP itself, & is its own entry for that reason.
  array('Name' => 'PHP OpenSSL', 'Binary' => '', 'Type' => 'php-extension', 'Package' => 'openssl',
    'MinimumVersion' => '', 'VersionCommand' => 'php -r "exit(extension_loaded(\'openssl\') ? 0 : 1);"', 'VersionPattern' => '',
    'Required' => FALSE, 'Subsystem' => 'Resource Awareness', 'Requires' => array('PHP'),
    'License' => 'PHP-3.01', 'Source' => 'https://www.php.net/manual/en/book.openssl.php', 'Purpose' => 'Encrypts every message on a manager socket. Resource awareness cannot work without it & fails quietly rather than loudly.'),
  array('Name' => 'PHP Zip', 'Binary' => '', 'Type' => 'php-extension', 'Package' => 'zip',
    'MinimumVersion' => '', 'VersionCommand' => 'php -r "exit(extension_loaded(\'zip\') ? 0 : 1);"', 'VersionPattern' => '',
    'Required' => TRUE, 'Subsystem' => 'Documents', 'Requires' => array('PHP'),
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
  // / FFMPEG is built from source rather than installed from a repository.
  // / The packaged build cannot carry --enable-nonfree & this application needs it.
  // / A nonfree build may not be redistributed, so it is built where it is used.
  // / The build script was called by hand from the Dockerfile until v3.8.9, which meant
  // / the manifest did not know this dependency was built & the audit did not record it.
  // / The script still does the work. The manifest now decides when it runs & whether it
  // / worked, which is the arrangement every other dependency already had.
  array('Name' => 'FFMPEG', 'Binary' => 'ffmpeg', 'Type' => 'source', 'Package' => '',
    'BuildCommand' => 'cd '.$InstLoc.DIRECTORY_SEPARATOR.'Documentation'.DIRECTORY_SEPARATOR.'Build && bash ffmpeg-build.sh',
    'MinimumVersion' => '6.1', 'VersionCommand' => 'ffmpeg -version', 'VersionPattern' => '/ffmpeg version n?(\d+\.\d+)/',
    // / FFMPEG names muxers rather than extensions. It has no mkv & no wmv. Matroska is
    // / the muxer behind one & ASF is the muxer behind the other, so both are aliased here
    // / or both are lost. An entry such as mov,mp4,m4a,3gp,3g2,mj2 is one demuxer answering
    // / to six names, so a matched name is split on commas before it is compared.
    'CapabilityCommand' => 'ffmpeg -formats', 'CapabilityStyle' => 'matrix',
    'CapabilityPattern' => '/^\s(?<read>[D\s])(?<write>[E\s])\s(?<name>[A-Za-z0-9_,]+)\s/m',
    // / Each of these was found by running the real output through the reader rather than
    // / by reading documentation. m4v, mpg & ts are all formats FFMPEG writes perfectly &
    // / none of them appear under those names, so a narrowing pass would have removed four
    // / working video conversions from a correct installation.
    // / This is why detected-advisory exists & why it should be run for a while before
    // / anything is allowed to narrow. The table is hand maintained & a gap in it is a
    // / false negative, which is the direction that costs somebody a conversion.
    'CapabilityAliases' => array('matroska' => 'mkv', 'matroska,webm' => 'mkv', 'asf' => 'wmv',
      'mpeg' => 'mpg', 'mpegts' => 'ts', 'mp4' => 'm4v', 'ogg' => 'oga',
      'adts' => 'aac', 'ipod' => 'm4a', 'matroska' => 'mka', 'image2' => 'jpg',
      'ass' => 'ssa', 'webvtt' => 'vtt', 'microdvd' => 'sub'),
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
  // / ImageMagick is built from source rather than installed from a repository.
  // / The packaged build carries no HEIC, AVIF or JPEG XL support & this application
  // / offers all three. The same reasoning as FFMPEG applies to how it is invoked.
  array('Name' => 'ImageMagick', 'Binary' => 'magick', 'Type' => 'source', 'Package' => '',
    'BuildCommand' => 'bash '.$InstLoc.DIRECTORY_SEPARATOR.'Documentation'.DIRECTORY_SEPARATOR.'Build'.DIRECTORY_SEPARATOR.'build-imagemagick-v7.sh',
    'MinimumVersion' => '7.1', 'VersionCommand' => 'magick -version', 'VersionPattern' => '/ImageMagick (\d+\.\d+)/',
    // / The format column may carry a hyphen & may overrun its width, as RADIAL-GRADIENT
    // / does, so the name is not anchored to a column. A trailing asterisk is native blob
    // / support rather than part of the name. A continuation line describing a format
    // / carries no mode column & therefore cannot match.
    'CapabilityCommand' => 'magick -list format', 'CapabilityStyle' => 'matrix',
    'CapabilityPattern' => '/^\s*(?<name>[A-Za-z0-9][A-Za-z0-9-]*)\*?\s+(?<read>[r-])(?<write>[w-])[+-]\s/m',
    // / ImageMagick has no TIF. It reports TIFF & TIFF64. An installation permitting tif
    // / would lose it to a narrowing pass without this line.
    'CapabilityAliases' => array('tiff' => 'tif', 'jpeg' => 'jpg', 'jpe' => 'jpg'),
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
    // / Assimp answers two separate questions with two separate commands, so the read set
    // / is detected here & the write set is detected by the entry below. Neither command
    // / reports flags, so each is a plain list & each declares its own direction.
    // / listext prints one line of *.ext;*.ext, so the pattern strips the glob.
    'CapabilityCommand' => 'assimp listext', 'CapabilityStyle' => 'list',
    'CapabilityDirection' => 'read', 'CapabilityPattern' => '/;/',
    // / listexport prints export identifiers, one per line, & seven of the twenty two are
    // / not the extension a user types. collada is dae, gltf2 is gltf, glb2 is glb, stlb is
    // / stl, plyb is ply, fbxa is fbx & objnomtl is obj.
    // / Those last four are variants rather than names for the same thing. This table says
    // / what Assimp is able to write. It does not say what the pipeline should ask for, &
    // / the Model pipeline still names objnomtl deliberately to avoid the material sidecar
    // / the plain obj exporter leaves beside the model.
    'CapabilityWriteCommand' => 'assimp listexport', 'CapabilityWriteStyle' => 'list',
    'CapabilityWritePattern' => '/\R/',
    'CapabilityAliases' => array('collada' => 'dae', 'gltf2' => 'gltf', 'glb2' => 'glb',
      'stlb' => 'stl', 'plyb' => 'ply', 'fbxa' => 'fbx', 'objnomtl' => 'obj', 'stp' => 'step'),
    'Required' => FALSE, 'Subsystem' => '3D Models', 'Requires' => array('Bubblewrap'),
    'License' => 'BSD-3-Clause', 'Source' => 'https://github.com/assimp/assimp', 'Purpose' => 'Converts between 3D model formats.'),
  // / PyMeshLab is BUNDLED at Resources/PyMeshLab & is imported by inserting that directory
  // / onto the python path, exactly as convertModels does. A bare import cannot see it, so
  // / probing for one reported a dependency that ships with the application as absent.
  // / Type is bundled, so nothing tries to install or remove it.
  // / __version__ is not exposed by every build, so presence is proved by the import alone.
// / The probe names python3.14 rather than python3 & that matters.
// / A probe using python3 asked an interpreter that cannot load the bundle.
// / It reported the bundle as absent on a machine where the bundle was present & intact.
  // / The interpreter the bundled PyMeshLab was compiled against.
  // / A compiled extension loads under one Python minor version & under no other.
  // / The bundled object names the version it needs, currently cpython-314.
  // / Type is manual because no mainstream distribution packages this version yet.
  // / Debian bookworm ships 3.11 & Ubuntu 24.04 ships 3.12, so neither can load the bundle.
  // / It is optional & the 3D Models subsystem works without it.
  // / MeshLab performs the same work whenever this is absent.
  // / Install it ALONGSIDE the system interpreter & never in place of it.
  // / A distribution writes its own tooling against the interpreter it ships.
  // / Replacing /usr/bin/python3 breaks the package manager on Debian & on Ubuntu.
  // / The Model pipeline reads the tag off the bundle & finds whichever version matches.
  // / Rebuilding the bundle for a locally available interpreter needs no change here.
  array('Name' => 'Python 3.14', 'Binary' => 'python3.14', 'Type' => 'source', 'Package' => '',
    'MinimumVersion' => '3.14', 'VersionCommand' => 'python3.14 -V', 'VersionPattern' => '/Python (\d+\.\d+)/',
    // / make altinstall is the whole reason this is safe & it is not optional.
    // / A plain make install would overwrite /usr/bin/python3 & break the package manager.
    // / altinstall writes python3.14 & leaves every unversioned name exactly as it was.
    // / --enable-shared builds libpython so the compiled PyMeshLab module can link it.
    // / ldconfig then tells the loader about /usr/local/lib, which the sandbox reads from
    // / /etc/ld.so.cache rather than searching for itself.
    'BuildCommand' => 'set -e; DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends build-essential wget libssl-dev zlib1g-dev libbz2-dev libreadline-dev libsqlite3-dev libffi-dev liblzma-dev; cd /tmp; rm -rf Python-3.14.0 Python-3.14.0.tgz; wget -q https://www.python.org/ftp/python/3.14.0/Python-3.14.0.tgz; tar -xf Python-3.14.0.tgz; cd Python-3.14.0; ./configure --enable-shared --prefix=/usr/local; make -j$(nproc); make altinstall; ldconfig; cd /tmp; rm -rf Python-3.14.0 Python-3.14.0.tgz',
    'Required' => FALSE, 'Subsystem' => '3D Models', 'Requires' => array(),
    'License' => 'PSF-2.0', 'Source' => 'https://www.python.org', 'Purpose' => 'Loads the bundled PyMeshLab, which is the only mesh route MeshLab has not removed.'),
  // / NumPy, which PyMeshLab imports before it does anything else.
  // / It is installed against the SAME interpreter the bundle was built for rather than
  // / against the system one. A host may carry numpy for python3 & none for python3.14 &
  // / those are two different installations that share a name.
  // / PyMeshLab catches an import failure & re-raises its own ImportError: initialization
  // / failed, which names neither numpy nor anything else. Four separate theories about Qt
  // / plugins & library paths were tested against that message before the real cause was
  // / read by importing the inner module directly & letting the original exception through.
  // / A dependency this manifest does not declare is a dependency nobody installs.
  array('Name' => 'NumPy 3.14', 'Binary' => '', 'Type' => 'source', 'Package' => '',
    'MinimumVersion' => '', 'VersionCommand' => 'python3.14 -c \'import numpy; print(numpy.__version__)\'', 'VersionPattern' => '/([0-9]+\\.[0-9]+)/',
    'BuildCommand' => 'python3.14 -m ensurepip --upgrade; python3.14 -m pip install --upgrade numpy',
    'Required' => FALSE, 'Subsystem' => '3D Models', 'Requires' => array('Python 3.14'),
    'License' => 'BSD-3-Clause', 'Source' => 'https://numpy.org', 'Purpose' => 'Imported by PyMeshLab. Without it the bundle cannot load at all.'),

  array('Name' => 'PyMeshLab', 'Binary' => '', 'Type' => 'bundled', 'Package' => '',
  // / The probe names the bundled shared objects on the loader path.
  // / The compiled module links libraries that ship inside the bundle rather than system
  // / ones, & the loader has no reason to look there.
  // / Without this the module is found & then fails to load, which reports as absent.
  // / A prefix works here because this command is run through a shell.
  // / It would not work inside the sandbox, where bwrap execs without one.
  // / Qt is given a platform plugin & rendering is forced through Mesa software.
  // / The compiled module links Qt, & Qt refuses to start without a platform plugin.
  // / A probe with no display reported ImportError: initialization failed, which reads
  // / as a broken bundle & is Qt declining to start rather than the module being absent.
  // / The same three variables are what the meshlab sandbox profile sets, & the reasons
  // / are recorded there. This probe runs unsandboxed, so it sets them as a prefix.
  // / Three things have to be true before this import works & each one hid the others.
  // / An interpreter matching the cpython tag on the bundled object must be installed.
  // / NumPy must be installed against THAT interpreter, because PyMeshLab imports it first.
  // / Qt must be told where its platform plugin is, because the bundle carries Qt5 & the
  // / plugins directory beside it holds MeshLab's filters rather than Qt's.
  // / PyMeshLab reports all three as ImportError: initialization failed, which names none
  // / of them. Import pmeshlab directly to see the exception that was actually raised.
  // / QT_PLUGIN_PATH points Qt at the plugins beside the bundled Qt5 libraries.
  // / QT_QPA_PLATFORM names which plugin to load & says nothing about where to find it,
  // / so without this Qt searches the system path, finds nothing it can use & reports
  // / ImportError: initialization failed, which names neither the plugin nor the path.
  // /
  // / The platforms directory in that bundle was ADDED BY HAND & is not in the wheel.
  // / No PyMeshLab wheel ships a Qt platform plugin. The wheel carries Qt5 libraries & a
  // / plugins directory holding MeshLab's own filters & format handlers, & expects the
  // / platform plugin to come from the host.
  // / It was copied from /usr/lib/x86_64-linux-gnu/qt5/plugins/platforms on this machine.
  // / A bundle refresh will drop it again & PyMeshLab will report absent with exactly the
  // / message above, which names nothing that would lead anybody back to this note.
  // / Copy the directory back after replacing the bundle.
  // /
  // / The plugin & the bundled libQt5Core must be the same Qt minor version. Qt refuses a
  // / plugin built against a different one & says so, which is the readable failure.
  // / Run the probe with QT_DEBUG_PLUGINS=1 to see every path searched & every rejection.
    'MinimumVersion' => '', 'VersionCommand' => 'QT_QPA_PLATFORM=offscreen QT_PLUGIN_PATH='.$InstLoc.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'PyMeshLab'.DIRECTORY_SEPARATOR.'pymeshlab'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'plugins'.' LIBGL_ALWAYS_SOFTWARE=1 __EGL_VENDOR_LIBRARY_FILENAMES=/usr/share/glvnd/egl_vendor.d/50_mesa.json LD_LIBRARY_PATH='.$InstLoc.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'PyMeshLab'.DIRECTORY_SEPARATOR.'pymeshlab'.DIRECTORY_SEPARATOR.'lib'.' python3.14 -c "import sys; sys.path.insert(0, \''.$InstLoc.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'PyMeshLab\'); import pymeshlab; print(\'bundled\')"', 'VersionPattern' => '',
    'Required' => FALSE, 'Subsystem' => '3D Models', 'Requires' => array('Assimp', 'Python 3.14', 'NumPy 3.14'),
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
  // / ScanCore is a standalone virus scanner this project writes & distributes.
  // / It ships inside the application & is a microservice rather than a package.
  // / Presence is proved by asking it to report its own version, because a bundled file
  // / being on disk says nothing about whether it runs.
  // / ScanCore reports its own version with -version, & that is what is asked for here.
  // / The pattern is anchored to the name rather than matching the first pair of numbers
  // / it finds. That output also carries three timestamps & several paths, & an
  // / unanchored pattern would keep working right up until the order of those lines
  // / changed, then report a date as a version.
  // / An earlier probe invented an argument, which ScanCore correctly refused with Cannot
  // / verify supplied arguments, & a probe after that read the version out of the file with
  // / grep. Reading rather than running was defensible & was not necessary. The interface
  // / exists & asking the thing itself is the honest answer.
  array('Name' => 'ScanCore', 'Binary' => '', 'Type' => 'bundled', 'Package' => '',
    'MinimumVersion' => '', 'VersionCommand' => 'php '.escapeshellarg($InstLoc.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'ScanCore'.DIRECTORY_SEPARATOR.'ScanCore.php').' -version', 'VersionPattern' => '/ScanCore v([0-9]+\\.[0-9]+)/',
    'Required' => FALSE, 'Subsystem' => 'Virus Scanning', 'Requires' => array('PHP'),
    'License' => 'GPL-3.0', 'Source' => 'Bundled at Resources/ScanCore', 'Purpose' => 'Scans every uploaded file. Written & distributed by this project.'),
  array('Name' => 'ClamAV', 'Binary' => 'clamscan', 'Type' => 'apt', 'Package' => 'clamav clamav-daemon',
    'MinimumVersion' => '0.103', 'VersionCommand' => 'clamscan --version', 'VersionPattern' => '/ClamAV (\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'Virus Scanning', 'Requires' => array(),
    'License' => 'GPL-2.0', 'Source' => 'https://www.clamav.net', 'Purpose' => 'Scans uploads. Only used when virus scanning is enabled.'));
// / -----------------------------------------------------------------------------------