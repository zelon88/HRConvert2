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
// / File information ...
// / v4.2.6.
// / HRConvert2 Dependency Manifest, the application's half.
// / This file is data. It defines nothing & does nothing.
// / The Engine's verifyDependsManifest() reads it. Nothing else may.
// / The manifest has two halves, as config.php does. What the Engine needs whatever application bundles it, PHP, its
// / extensions, the web server, Curl, Git, Bubblewrap & AppArmor's tools, is the Engine's half, Resources/Engine/depends.php,
// / & this half is what HRConvert2 needs. The two are read as one list, the Engine's first, so a name here may be
// / Required of one there, as PHP Zip requires PHP. A name is in one half only, from v4.2.4.
// /
// / $InstLoc is in scope while this file is read, because verifyDependsManifest declares it
// / global before the require. A bundled dependency uses it rather than a hardcoded path.
// /
// / The array order is the install order, read top to bottom, after the Engine's half.
// / A dependency also declares what it Requires, which is checked before it is installed.
// / The order is what runs, & the chain is what catches an order somebody has broken.
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
// /   Fallback         For an apt Type, optional. How this application builds its OWN copy
// /                    where the package manager's is missing or outside the window.
// /
// / A Fallback holds a BuildCommand, & may hold the Version it builds, the Sha256 of what that
// / command downloads, & the Source & License of what it builds. It is honoured only where Binary
// / is a plain name & VersionCommand runs that program, because the version asked must be the own
// / build's. Dependency Core hands the command a new folder only root can open as DEPENDENCY_PREFIX,
// / with DEPENDENCY_VERSION & DEPENDENCY_SHA256, & the command installs the program at bin/ under
// / it, named as Binary names it. What it built is asked its version exactly as the package
// / manager's would be, & is put in service only when every name in it is root's alone, it answers
// / inside the window & it is the Version pinned. It is ready once the account that runs
// / conversions can run it.
// / The own copy lives in a folder of this application's own under /usr/local/libexec, so the
// / package manager's copy & every other program on the host are left exactly as they are.
// / It is asked FIRST wherever the entry is used, & the window decides. --update-depends asks
// / the package manager's copy again after the package manager has run, & takes the own copy
// / away once that one answers inside the window, so an entry falls back only for as long as
// / the package manager is behind. An entry with no Fallback is unchanged.
// /
// / A bundled Type is a microservice this project distributes rather than a package this
// / project installs. It is never installed, never updated & never removed.
// / It is listed so --check-depends, -v & the supply chain audit can all see it, & so that
// / anything needing it can name it in its own Requires list.
// / PyMeshLab & ScanCore are both bundled.
// /
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
// / Reading a format is not writing it, & the two are stored separately.
// / Assimp reads OFF & has no OFF exporter, so routing on the input extension alone would send an obj to off conversion to it.
// / ImageMagick & FFMPEG each report both directions in one line, & Assimp reports them from two separate commands.
// /
// / A capability list is a filter & is never a source of new formats.
// / magick -list format offers MSL, a scripting language ImageMagick executes, & http, https & file, which fetch a URL past every guard this application owns.
// / Detection narrows what a pipeline already declared, & never adds to it.
// /
// / Hardware requirements ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / Dependency requirements ...
// / This file & Resources/Engine/depends.php are the authoritative list of dependencies, & nothing else is.
// / Run  php convertCore.php -v  to see what this installation has.
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
$DependsVersion = 'v4.2.6';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
$DependsManifest = array(

  // / ---- Core. What the Engine needs, PHP, its extensions, the web server, Curl, Git & the sandbox, is the Engine's half,
  // / Resources/Engine/depends.php, read before this one, so it is installed first. ----
  // / A PHP extension is not proved by the PHP binary being present. It is asked for by name, from PHP itself, & is
  // / its own entry for that reason. php-zip is this application's, & the Engine's PHP entry does not install it.
  // / php-zip is Required because it backs a security filter rather than a feature.
  // / sanitizeDocumentLinks() opens an OOXML document with ZipArchive to strip external
  // / references before LibreOffice is allowed to see it. Without the extension that
  // / function warns & passes the document through unfiltered, leaving the sandbox as the
  // / only thing standing between a crafted document & the machine.
  array('Name' => 'PHP Zip', 'Binary' => '', 'Type' => 'php-extension', 'Package' => 'zip',
    'MinimumVersion' => '', 'VersionCommand' => 'php -r "exit(extension_loaded(\'zip\') ? 0 : 1);"', 'VersionPattern' => '',
    'Required' => TRUE, 'Subsystem' => 'Documents', 'Requires' => array('PHP'),
    'License' => 'PHP-3.01', 'Source' => 'https://www.php.net/manual/en/book.zip.php', 'Purpose' => 'Opens OOXML documents so external references can be stripped before LibreOffice sees them. Without it the sandbox is the only protection.'),

  // / ---- Archives. Needed early because other dependencies arrive compressed. ----
  array('Name' => '7-Zip', 'Binary' => '7z', 'Type' => 'apt', 'Package' => 'p7zip-full p7zip-rar',
    // / 26.02 IS THE FLOOR because every release from 21.07 through 26.01 decodes XZ with a heap
    // / overflow a crafted archive reaches, CVE-2026-14266. 26.01 fixed a heap overflow in the
    // / NTFS handler & seven out of bounds or uninitialized reads in others, & 25.01 fixed the
    // / symbolic links a chain of them could walk out of the extraction folder through,
    // / CVE-2025-55188. 7-Zip picks a handler by what a file holds rather than by its name, so
    // / every one of those is reachable from any upload offered for extraction.
    // / Debian's security archive carries 26.02 for bookworm, which the Docker image is built on.
    // / Ubuntu 24.04 carries 23.01, so that host builds this application's own, from the source
    // / 7-Zip publishes, pinned by version & checksum below.
    'MinimumVersion' => '26.02', 'VersionCommand' => '7z i', 'VersionPattern' => '/7-Zip.*?(\d+\.\d+)/',
    'Fallback' => array('Version' => '26.06', 'Sha256' => 'a4e374c8f864f51df48c2cfd0e1413ec3b2ea030deae852d6a655402ebb61ac4',
      'BuildCommand' => 'bash '.escapeshellarg($InstLoc.DIRECTORY_SEPARATOR.'Documentation'.DIRECTORY_SEPARATOR.'Build'.DIRECTORY_SEPARATOR.'build-7zip.sh'),
      'Source' => 'https://github.com/ip7z/7zip', 'License' => 'LGPL-2.1'),
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
    // / RAR is a proprietary binary RARLAB publishes per architecture, & the arm build is a
    // / separate download rather than a flag on the same one.
    // / The Debian rar package is x86 only, so an arm host that installs it gets nothing.
    // / This is why the entry is 'manual'. Nothing here downloads it, & the Source below is
    // / where an administrator goes. The override changes what they are told to fetch.
    'ArchitectureOverrides' => array(
      'arm64' => array('Source' => 'https://www.rarlab.com/rar/rarlinux-arm64-7.01.tar.gz',
        'Purpose' => 'Creates RAR archives. Licence forbids redistribution. On arm64 fetch the arm64 build, not the package.'),
      'arm' => array('Source' => 'https://www.rarlab.com/rar/rarlinux-arm-7.01.tar.gz',
        'Purpose' => 'Creates RAR archives. Licence forbids redistribution. On 32 bit arm fetch the arm build, not the package.')),
    'License' => 'Proprietary', 'Source' => 'https://www.rarlab.com', 'Purpose' => 'Creates RAR archives. Licence forbids redistribution, so it is never installed automatically.'),

  // / ---- Media. ----
  // / FFMPEG is built from source rather than installed from a repository.
  // / The packaged build cannot carry --enable-nonfree, which this application needs, & a nonfree build may not be redistributed, so it is built where it is used.
  // / The manifest decides when the build script runs & whether it worked, as it does for every other dependency.
  // / The script builds in a folder of its own under /var/tmp & removes it.
  array('Name' => 'FFMPEG', 'Binary' => 'ffmpeg', 'Type' => 'source', 'Package' => '',
    'BuildCommand' => 'bash '.escapeshellarg($InstLoc.DIRECTORY_SEPARATOR.'Documentation'.DIRECTORY_SEPARATOR.'Build'.DIRECTORY_SEPARATOR.'ffmpeg-build.sh'),
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
  // / FFprobe comes from the same build as FFMPEG, which installs both, so it names the same build script.
  // / It is an entry of its own so that Identify asks for it by name, & a host whose FFMPEG came without it is told so rather than assumed to have it.
  // / It follows FFMPEG, so a build made for FFMPEG has already installed it by the time it is reached & it is found present.
  // / Where FFMPEG is in place & FFprobe is not, installing FFprobe runs the whole build again, because that is the only build that makes it.
  // / Identify is all that uses it, so that is its subsystem. A subsystem's removal never touches it, because no package manager manages it.
  array('Name' => 'FFprobe', 'Binary' => 'ffprobe', 'Type' => 'source', 'Package' => '',
    'BuildCommand' => 'bash '.escapeshellarg($InstLoc.DIRECTORY_SEPARATOR.'Documentation'.DIRECTORY_SEPARATOR.'Build'.DIRECTORY_SEPARATOR.'ffmpeg-build.sh'),
    'MinimumVersion' => '6.1', 'VersionCommand' => 'ffprobe -version', 'VersionPattern' => '/ffprobe version n?(\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'Identification', 'Requires' => array('Bubblewrap'),
    'License' => 'LGPL-2.1', 'Source' => 'https://ffmpeg.org', 'Purpose' => 'Reads the duration, the container & the streams of audio & video for Identify.'),

  // / ---- Documents. ----
  array('Name' => 'LibreOffice', 'Binary' => 'soffice', 'Type' => 'apt', 'Package' => 'libreoffice libreoffice-java-common',
    'MinimumVersion' => '7.0', 'VersionCommand' => 'soffice --version', 'VersionPattern' => '/LibreOffice (\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'Documents', 'Requires' => array('Bubblewrap'),
    'License' => 'MPL-2.0', 'Source' => 'https://www.libreoffice.org', 'Purpose' => 'Converts documents, spreadsheets & presentations.'),
  array('Name' => 'Poppler Utils', 'Binary' => 'pdftotext', 'Type' => 'apt', 'Package' => 'poppler-utils',
    'MinimumVersion' => '20.09', 'VersionCommand' => 'pdftotext -v', 'VersionPattern' => '/pdftotext version (\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'Documents & OCR', 'Requires' => array(),
    'License' => 'GPL-2.0', 'Source' => 'https://poppler.freedesktop.org', 'Purpose' => 'Extracts text & converts XPS documents.'),
  // / PDFInfo comes in the same package as Poppler Utils & is an entry of its own so that Identify asks for it by name.
  // / It shares Poppler Utils' subsystem & requirements, because it is the same package.
  // / Removing the Identification subsystem therefore never removes the package Documents & OCR need.
  array('Name' => 'PDFInfo', 'Binary' => 'pdfinfo', 'Type' => 'apt', 'Package' => 'poppler-utils',
    'MinimumVersion' => '20.09', 'VersionCommand' => 'pdfinfo -v', 'VersionPattern' => '/pdfinfo version (\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'Documents & OCR', 'Requires' => array(),
    'License' => 'GPL-2.0', 'Source' => 'https://poppler.freedesktop.org', 'Purpose' => 'Reads the page count, the version & the encryption of a PDF for Identify.'),
  // / PDFUnite & PDFSeparate come in the same package as well, & are entries of their own so that the PDF pipeline asks for each by name.
  // / They share Poppler Utils' subsystem & requirements, for the reason PDFInfo does.
  array('Name' => 'PDFUnite', 'Binary' => 'pdfunite', 'Type' => 'apt', 'Package' => 'poppler-utils',
    'MinimumVersion' => '20.09', 'VersionCommand' => 'pdfunite -v', 'VersionPattern' => '/pdfunite version (\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'Documents & OCR', 'Requires' => array(),
    'License' => 'GPL-2.0', 'Source' => 'https://poppler.freedesktop.org', 'Purpose' => 'Puts several PDFs into one for Merge.'),
  array('Name' => 'PDFSeparate', 'Binary' => 'pdfseparate', 'Type' => 'apt', 'Package' => 'poppler-utils',
    'MinimumVersion' => '20.09', 'VersionCommand' => 'pdfseparate -v', 'VersionPattern' => '/pdfseparate version (\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'Documents & OCR', 'Requires' => array(),
    'License' => 'GPL-2.0', 'Source' => 'https://poppler.freedesktop.org', 'Purpose' => 'Makes a PDF of each page of a PDF for Split.'),

  // / ---- Images. ----
  // / ImageMagick is built from source rather than installed from a repository.
  // / The packaged build carries no HEIC, AVIF or JPEG XL support & this application
  // / offers all three. The same reasoning as FFMPEG applies to how it is invoked.
  array('Name' => 'ImageMagick', 'Binary' => 'magick', 'Type' => 'source', 'Package' => '',
    'BuildCommand' => 'bash '.escapeshellarg($InstLoc.DIRECTORY_SEPARATOR.'Documentation'.DIRECTORY_SEPARATOR.'Build'.DIRECTORY_SEPARATOR.'build-imagemagick-v7.sh'),
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
  // / Resvg renders SVG to PNG, & the SVG pipeline asks it before Inkscape, from v4.2.5. It is a Rust program written for that one job,
  // / reads no script & fetches nothing, & starts in a fraction of the time Inkscape takes. Inkscape still renders everything Resvg
  // / is not asked to, & renders in its place where it is missing or fails.
  // / Debian 13 carries 0.45, Ubuntu 26.04 0.46 & Ubuntu 26.10 0.47, & each is used. Debian 12 & Ubuntu 24.04 carry none, & Debian 11 &
  // / Ubuntu 22.04 carry 0.8, which predates the options the pipeline passes, so those hosts get this application's own copy.
  // / The own copy is the release binary Resvg publishes, pinned by version & checksum, for x86-64 & ARM64. Resvg publishes no checksum,
  // / so the checksum is the one this release took on 11/16/2026. 0.50.0 is not used, because its binary needs glibc 2.43, which no
  // / supported distribution carries, & 0.49.0 needs 2.35. A 32 bit ARM host has no release binary, & renders SVG with Inkscape.
  array('Name' => 'Resvg', 'Binary' => 'resvg', 'Type' => 'apt', 'Package' => 'resvg',
    'MinimumVersion' => '0.45', 'VersionCommand' => 'resvg --version', 'VersionPattern' => '/^(\d+\.\d+\.\d+)\s*$/m',
    'ArchitectureOverrides' => array(
      'x86_64' => array('Fallback' => array('Method' => 'release', 'Version' => '0.49.0', 'Sha256' => '216637e75e909e53e3098b0875cd5f7da2fdc8b4359771b4a29b539afff9d3f5',
        'BuildCommand' => 'bash '.escapeshellarg($InstLoc.DIRECTORY_SEPARATOR.'Documentation'.DIRECTORY_SEPARATOR.'Build'.DIRECTORY_SEPARATOR.'hrconvert2-release-fetch.sh').' resvg '.escapeshellarg('https://github.com/linebender/resvg/releases/download/v{version}/resvg-linux-x86_64.tar.gz').' '.escapeshellarg('resvg'),
        'Source' => 'https://github.com/linebender/resvg', 'License' => 'Apache-2.0')),
      'arm64' => array('Fallback' => array('Method' => 'release', 'Version' => '0.49.0', 'Sha256' => '00356cb6984836d7638fe10597a01ec4776b0c356e164538f165eca067f0d982',
        'BuildCommand' => 'bash '.escapeshellarg($InstLoc.DIRECTORY_SEPARATOR.'Documentation'.DIRECTORY_SEPARATOR.'Build'.DIRECTORY_SEPARATOR.'hrconvert2-release-fetch.sh').' resvg '.escapeshellarg('https://github.com/linebender/resvg/releases/download/v{version}/resvg-linux-aarch64.tar.gz').' '.escapeshellarg('resvg'),
        'Source' => 'https://github.com/linebender/resvg', 'License' => 'Apache-2.0'))),
    'Required' => FALSE, 'Subsystem' => 'SVG', 'Requires' => array('Bubblewrap'),
    'License' => 'Apache-2.0', 'Source' => 'https://github.com/linebender/resvg', 'Purpose' => 'Renders SVG to PNG before Inkscape is asked.'),
  array('Name' => 'Dia', 'Binary' => 'dia', 'Type' => 'apt', 'Package' => 'dia',
    'MinimumVersion' => '0.97', 'VersionCommand' => 'dia --version', 'VersionPattern' => '/Dia version (\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'Drawings', 'Requires' => array('Bubblewrap'),
    'License' => 'GPL-2.0', 'Source' => 'https://wiki.gnome.org/Apps/Dia', 'Purpose' => 'Converts diagrams.'),
  // / Potrace traces a bitmap into vector outlines for the Trace pipeline, from v4.2.5. It reads BMP & the PNM formats itself, &
  // / ImageMagick reduces any other image to a grey bitmap first. Debian 12 & Ubuntu 24.04 both carry 1.16.
  array('Name' => 'Potrace', 'Binary' => 'potrace', 'Type' => 'apt', 'Package' => 'potrace',
    'MinimumVersion' => '1.16', 'VersionCommand' => 'potrace --version', 'VersionPattern' => '/potrace (\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'Tracing', 'Requires' => array('Bubblewrap'),
    'License' => 'GPL-2.0', 'Source' => 'https://potrace.sourceforge.net', 'Purpose' => 'Traces bitmaps into SVG, DXF, PDF, EPS & PostScript outlines.'),

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
    // / 3.15 is the first version REFUSED, which keeps every 3.14 patch usable & stops the
    // / whole 3.15 line. A compiled extension loads under one python minor version & under
    // / no other, so an interpreter that moved on is not an upgrade, it is a broken model
    // / pipeline reported as a missing module.
    'MinimumVersion' => '3.14', 'MaximumVersion' => '3.15', 'VersionCommand' => 'python3.14 -V', 'VersionPattern' => '/Python (\d+\.\d+)/',
    // / The index lists every CPython release. The Engine filters it to the declared
    // / window & hands the highest back as DEPENDENCY_VERSION.
    'VersionSource' => 'wget -qO- https://www.python.org/ftp/python/ 2>/dev/null | grep -oE [0-9]+[.][0-9]+[.][0-9]+/ | tr -d /',
    'BuildCommand' => 'bash '.escapeshellarg($InstLoc.DIRECTORY_SEPARATOR.'Documentation'.DIRECTORY_SEPARATOR.'Build'.DIRECTORY_SEPARATOR.'hrconvert2-python-build.sh'),
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
  // / -----------------------------------------------------------------------------------
  // / pip is a dependency in its own right & not an implementation detail of the packages
  // / it installs.
  // / It was invisible before. Two entries invoked it, nothing verified it, & a host where
  // / ensurepip had been stripped, which several distributions do to their python package,
  // / failed inside a build command rather than being reported as missing.
  // / Naming it here puts it in --check-depends & in whatever audits read that, which is
  // / the point. A tool that installs other tools should be auditable.
  // /
  // / It is verified against the VERSIONED interpreter. A pip belonging to python3 tells us
  // / nothing about python3.14 & the two are frequently different pips with different sets
  // / of packages installed.
  // / Type is source because ensurepip ships inside CPython & bootstraps without a network,
  // / so there is nothing to fetch & no package manager involved.
  array('Name' => 'pip 3.14', 'Binary' => '', 'Type' => 'source', 'Package' => '',
    'MinimumVersion' => '21.0', 'VersionCommand' => 'python3.14 -m pip --version', 'VersionPattern' => '/pip (\\d+\\.\\d+)/',
    // / ensurepip BOOTSTRAPS & pip upgrades ITSELF, & both are needed.
    // / ensurepip alone installs whatever pip shipped inside that CPython release, which on
    // / a freshly built interpreter is already months old & never moves again.
    // / The second half upgrades pip through pip, which is how pip is meant to be updated.
    // / pip is not tied to a python minor version the way a compiled module is, so taking
    // / the newest is safe here in a way it is not for the interpreter above.
    // / pip lists its own available versions & the Engine picks the newest inside the
    // / window. There is no ceiling declared, so that is simply the newest pip.
    // / pip is not tied to a python minor version the way a compiled module is, which is
    // / why it may float where the interpreter may not.
    'VersionSource' => 'python3.14 -m pip index versions pip 2>/dev/null | grep -oE [0-9]+[.][0-9]+([.][0-9]+)? | head -20',
    'BuildCommand' => 'python3.14 -m ensurepip --upgrade; python3.14 -m pip install --upgrade pip${DEPENDENCY_VERSION:+==$DEPENDENCY_VERSION}',
    'Required' => FALSE, 'Subsystem' => '3D Models', 'Requires' => array('Python 3.14'),
    'License' => 'MIT', 'Source' => 'https://pip.pypa.io', 'Purpose' => 'Installs the python packages this application needs. Bundled with CPython through ensurepip.'),

  array('Name' => 'NumPy 3.14', 'Binary' => '', 'Type' => 'source', 'Package' => '',
    'MinimumVersion' => '', 'VersionCommand' => 'python3.14 -c \'import numpy; print(numpy.__version__)\'', 'VersionPattern' => '/([0-9]+\\.[0-9]+)/',
    'BuildCommand' => 'python3.14 -m ensurepip --upgrade; python3.14 -m pip install --upgrade numpy',
    'Required' => FALSE, 'Subsystem' => '3D Models', 'Requires' => array('Python 3.14', 'pip 3.14'),
    // / pip installs a prebuilt wheel where one exists & compiles from source where none does.
    // / Which happens depends on the architecture & on how new the Python is, & nothing in the output warns an operator which one they are about to wait through.
    // / On arm with a freshly built Python 3.14 there is frequently no wheel, & a numpy compile on a small board runs for a long time, which the override says.
    // / --prefer-binary asks for a wheel first without forbidding a source build, so a host with no wheel still succeeds slowly rather than being refused.
    'ArchitectureOverrides' => array(
      'arm64' => array('BuildCommand' => 'python3.14 -m ensurepip --upgrade; python3.14 -m pip install --prefer-binary --upgrade numpy',
        'Purpose' => 'Imported by PyMeshLab. On arm pip often has no wheel & compiles it, which takes a long time on a small board.'),
      'arm' => array('BuildCommand' => 'python3.14 -m ensurepip --upgrade; python3.14 -m pip install --prefer-binary --upgrade numpy',
        'Purpose' => 'Imported by PyMeshLab. On arm pip often has no wheel & compiles it, which takes a long time on a small board.')),
    'License' => 'BSD-3-Clause', 'Source' => 'https://numpy.org', 'Purpose' => 'Imported by PyMeshLab. Without it the bundle cannot load at all.'),

  // / PyMeshLab publishes wheels & no source distribution. Its wheels for Python 3.14 need glibc 2.35
  // / or newer, which means Ubuntu 22.04, Debian 12 or later. On an older host pip finds nothing it
  // / can install & this entry fails. The Model pipeline then falls back to the MeshLab binary.
  array('Name' => 'PyMeshLab', 'Binary' => '', 'Type' => 'source', 'Package' => '',
    // / It is installed with pip, as a wheel.
    // / PyMeshLab 2025.07 publishes native arm64 linux wheels, so a Raspberry Pi & a NAS get the same PyMeshLab a desktop does, from the same command.
    // / The wheel places its own Qt libraries where python looks for them, so none of the QT_PLUGIN_PATH & LD_LIBRARY_PATH handling a bundle needs applies to it.
    // / A bundled copy still works, because resolvePyMeshLabInterpreter tries an installed one first & falls back to Resources/PyMeshLab.
    // / libGL.so.1 is required at runtime & is not in the wheel, so a slim container needs libgl1, or the import fails with a message that names a library rather than a cause.
    'BuildCommand' => 'python3.14 -m pip install --prefer-binary --upgrade pymeshlab',
  // / The probe names the bundled shared objects on the loader path, because the compiled module links libraries inside the bundle that the loader has no reason to look for.
  // / Without this the module is found & then fails to load, which reports as absent.
  // / A prefix works here because this command is run through a shell, & would not work inside the sandbox, where bwrap execs without one.
  // / Qt is given a platform plugin & rendering is forced through Mesa software, because Qt refuses to start without a platform plugin.
  // / The meshlab sandbox profile sets the same three variables & records the reasons, & this probe runs unsandboxed, so it sets them as a prefix.
  // / Three things must be true before this import works, & PyMeshLab reports each as ImportError: initialization failed, which names none of them.
  // / An interpreter matching the cpython tag on the bundled object must be installed.
  // / NumPy must be installed against that interpreter, because PyMeshLab imports it first.
  // / Qt must be told where its platform plugin is, because the bundle's plugins directory holds MeshLab's filters rather than Qt's.
  // / Import pmeshlab directly to see the exception that was actually raised.
  // / QT_PLUGIN_PATH points Qt at the plugins beside the bundled Qt5 libraries, & QT_QPA_PLATFORM names which plugin to load.
  // /
  // / The platforms directory in the bundle was added by hand, because no PyMeshLab wheel ships a Qt platform plugin.
  // / It was copied from /usr/lib/x86_64-linux-gnu/qt5/plugins/platforms, & a bundle refresh drops it again.
  // / Copy the directory back after replacing the bundle, or PyMeshLab reports absent with the message above.
  // /
  // / The plugin & the bundled libQt5Core must be the same Qt minor version, & Qt refuses a plugin built against another & says so.
  // / Run the probe with QT_DEBUG_PLUGINS=1 to see every path searched & every rejection.
    'MinimumVersion' => '', 'VersionCommand' => 'QT_QPA_PLATFORM=offscreen QT_PLUGIN_PATH='.$InstLoc.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'PyMeshLab'.DIRECTORY_SEPARATOR.'pymeshlab'.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'plugins'.' LIBGL_ALWAYS_SOFTWARE=1 __EGL_VENDOR_LIBRARY_FILENAMES=/usr/share/glvnd/egl_vendor.d/50_mesa.json LD_LIBRARY_PATH='.$InstLoc.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'PyMeshLab'.DIRECTORY_SEPARATOR.'pymeshlab'.DIRECTORY_SEPARATOR.'lib'.' python3.14 -c "import sys; sys.path.insert(0, \''.$InstLoc.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'PyMeshLab\'); import pymeshlab; print(\'bundled\')"', 'VersionPattern' => '',
    'Required' => FALSE, 'Subsystem' => '3D Models', 'Requires' => array('Assimp', 'Python 3.14', 'pip 3.14', 'NumPy 3.14'),
    'License' => 'GPL-3.0', 'Source' => 'Bundled at Resources/PyMeshLab', 'Purpose' => 'Repairs & simplifies meshes. Replaces MeshLab. Ships with HRConvert2.'),
  // / meshlabserver is a Qt program & aborts where there is no display, which is every web request & every listener, so its version is asked through Qt's offscreen platform.
  array('Name' => 'MeshLab', 'Binary' => 'meshlabserver', 'Type' => 'apt', 'Package' => 'meshlab',
    'MinimumVersion' => '2020.09', 'VersionCommand' => 'QT_QPA_PLATFORM=offscreen meshlabserver --version', 'VersionPattern' => '/(\d+\.\d+)/',
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
    // / Syslinux is an x86 bootloader & syslinux-utils is packaged for x86 alone.
    // / isohybrid writes an x86 master boot record into an ISO. There is nothing for it to
    // / do on arm & no package providing it, so an arm host reports it unsupported rather
    // / than reporting a package manager failure that reads like a broken mirror.
    // / A hybrid image built on x86 still boots x86 hardware. This is about where the image
    // / can be BUILT, not what it can boot.
    // / Plain ISO creation is unaffected. Genisoimage is packaged for every architecture &
    // / an arm host can still write an image, just not make it USB bootable this way.
    'Architectures' => array('x86_64', 'x86'),
    'License' => 'GPL-2.0', 'Source' => 'https://wiki.syslinux.org', 'Purpose' => 'Makes an ISO image bootable.'),

  // / ---- Identification. ----
  // / file reads what a file holds from its content rather than its name, & reports its character set.
  // / 5.43 is the floor. Every release before 5.38 overflows the heap reading a CDF document, CVE-2019-18218.
  // / Every release before 5.43 reads past a buffer on the stack copying a string, CVE-2022-48554.
  // / Ubuntu 24.04 carries 5.45 & Debian's bookworm carries 5.44, so neither needs a build of its own.
  // / Identify runs it in the sandbox, because it parses whatever a user uploaded.
  array('Name' => 'File', 'Binary' => 'file', 'Type' => 'apt', 'Package' => 'file',
    'MinimumVersion' => '5.43', 'VersionCommand' => 'file --version', 'VersionPattern' => '/file-(\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'Identification', 'Requires' => array('Bubblewrap'),
    'License' => 'BSD-2-Clause', 'Source' => 'https://www.darwinsys.com/file', 'Purpose' => 'Tells what a file holds & its character set from its content for Identify.'),

  // / ---- Metadata. ----
  // / mat2 removes the metadata a file carries, such as its author, the place a photo was taken & the software that made it.
  // / It is run in lightweight mode, which leaves what a file shows as it was. Its other mode turns every page of a PDF into a picture & encodes a photo again.
  // / It reads a photo's metadata through ExifTool, which its package depends on, & a video's through FFMPEG.
  // / 0.13 is the floor. Every release before 0.13 writes outside its folder when it cleans a zip, CVE-2022-35410, & every office document is a zip.
  // / Ubuntu 24.04 carries 0.13.4 & Debian's bookworm carries 0.13.3, so neither needs a build of its own.
  // / The Metadata pipeline runs it in the sandbox, because it parses whatever a user uploaded.
  // / It is a Python program, & its package's modules are built for the distribution's own Python 3 & no other.
  // / Interpreter has it started by that Python, which Debian & Ubuntu name in /usr/share/python3/debian_defaults,
  // / so a host that points python3 at another Python still runs it. Without the setting it failed to start there with 41000.
  array('Name' => 'MAT2', 'Binary' => 'mat2', 'Type' => 'apt', 'Package' => 'mat2', 'Interpreter' => 'python3',
    'MinimumVersion' => '0.13', 'VersionCommand' => 'mat2 --version', 'VersionPattern' => '/mat2 (\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'Metadata', 'Requires' => array('Bubblewrap'),
    'License' => 'LGPL-3.0', 'Source' => 'https://0xacab.org/jvoisin/mat2', 'Purpose' => 'Strips the metadata a file carries for Strip Metadata.'),

  // / ---- Ebooks. ----
  array('Name' => 'Calibre', 'Binary' => 'ebook-convert', 'Type' => 'apt', 'Package' => 'calibre',
    'MinimumVersion' => '9.13', 'VersionCommand' => 'ebook-convert --version', 'VersionPattern' => '/calibre (\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'Ebooks', 'Requires' => array('Bubblewrap'),
    'License' => 'GPL-3.0', 'Source' => 'https://calibre-ebook.com', 'Purpose' => 'Converts between ebook formats.'),

  // / ---- Structured data. ----
  // / Dasel converts JSON, YAML, TOML, XML & CSV into one another for the Data pipeline, from v4.2.5, & yq writes XML, which Dasel cannot.
  // / Dasel 3 is required. Dasel 2, which every Debian & Ubuntu LTS release carries, wrote Go object dumps into XML & CSV & invalid TOML
  // / & reported success, so 3.11 is the floor & 4.0 the first version refused. No LTS release carries Dasel 3, so the package manager is
  // / not asked, & the own copy is the only copy. Name the package here once the LTS releases this application supports carry Dasel 3.
  // / The own copy is the release binary Dasel publishes, pinned by version & checksum. Dasel publishes no checksum, so the checksum is
  // / the one this release took on 11/16/2026.
  array('Name' => 'Dasel', 'Binary' => 'dasel', 'Type' => 'apt', 'Package' => '',
    'MinimumVersion' => '3.11', 'MaximumVersion' => '4.0', 'VersionCommand' => 'dasel version', 'VersionPattern' => '/^(\d+\.\d+\.\d+)\s*$/m',
    'Architectures' => array('x86_64', 'arm64', 'arm'),
    'ArchitectureOverrides' => array(
      'x86_64' => array('Fallback' => array('Method' => 'release', 'Version' => '3.11.2', 'Sha256' => '5006ee3a4239ab6a3edb1bf5c932874d814f7c276117ca677352697a4f547799',
        'BuildCommand' => 'bash '.escapeshellarg($InstLoc.DIRECTORY_SEPARATOR.'Documentation'.DIRECTORY_SEPARATOR.'Build'.DIRECTORY_SEPARATOR.'hrconvert2-release-fetch.sh').' dasel '.escapeshellarg('https://github.com/TomWright/dasel/releases/download/v{version}/dasel_linux_amd64'),
        'Source' => 'https://github.com/TomWright/dasel', 'License' => 'MIT')),
      'arm64' => array('Fallback' => array('Method' => 'release', 'Version' => '3.11.2', 'Sha256' => 'df33920a792cd8ee5573cec3632a710cd2178d7bc4fc3892c6be78f35e7cbcc6',
        'BuildCommand' => 'bash '.escapeshellarg($InstLoc.DIRECTORY_SEPARATOR.'Documentation'.DIRECTORY_SEPARATOR.'Build'.DIRECTORY_SEPARATOR.'hrconvert2-release-fetch.sh').' dasel '.escapeshellarg('https://github.com/TomWright/dasel/releases/download/v{version}/dasel_linux_arm64'),
        'Source' => 'https://github.com/TomWright/dasel', 'License' => 'MIT')),
      'arm' => array('Fallback' => array('Method' => 'release', 'Version' => '3.11.2', 'Sha256' => '6b1057cbaf17602352d9d2b5a6c0d47189f4e3ef4cda630001db9d02209c7df5',
        'BuildCommand' => 'bash '.escapeshellarg($InstLoc.DIRECTORY_SEPARATOR.'Documentation'.DIRECTORY_SEPARATOR.'Build'.DIRECTORY_SEPARATOR.'hrconvert2-release-fetch.sh').' dasel '.escapeshellarg('https://github.com/TomWright/dasel/releases/download/v{version}/dasel_linux_arm32'),
        'Source' => 'https://github.com/TomWright/dasel', 'License' => 'MIT'))),
    'Required' => FALSE, 'Subsystem' => 'Structured Data', 'Requires' => array('Bubblewrap'),
    'License' => 'MIT', 'Source' => 'https://github.com/TomWright/dasel', 'Purpose' => 'Converts JSON, YAML, TOML, XML & CSV into one another.'),
  // / yq writes the XML the Data pipeline makes, from v4.2.5. Dasel 3 cannot write a number as XML, & writes a document holding more
  // / than one root for most others, so the pipeline has Dasel write JSON & yq turn that JSON into XML under one root, then reads it
  // / back to prove it is XML. yq reads nothing a user uploaded.
  // / This is Mike Farah's yq, a Go program. The package Debian & Ubuntu call yq is another program of the same name, a Python wrapper
  // / around jq, so the package manager is never asked, & the own copy is the only copy. The version pattern names Mike Farah's, so
  // / the other is never mistaken for it.
  // / The own copy is the release binary yq publishes, pinned by version & by the checksum yq publishes beside it.
  array('Name' => 'yq', 'Binary' => 'yq', 'Type' => 'apt', 'Package' => '',
    'MinimumVersion' => '4.50', 'MaximumVersion' => '5.0', 'VersionCommand' => 'yq --version', 'VersionPattern' => '/mikefarah\/yq\/\) version v(\d+\.\d+\.\d+)/',
    'Architectures' => array('x86_64', 'arm64', 'arm'),
    'ArchitectureOverrides' => array(
      'x86_64' => array('Fallback' => array('Method' => 'release', 'Version' => '4.50.1', 'Sha256' => 'c7a1278e6bbc4924f41b56db838086c39d13ee25dcb22089e7fbf16ac901f0d4',
        'BuildCommand' => 'bash '.escapeshellarg($InstLoc.DIRECTORY_SEPARATOR.'Documentation'.DIRECTORY_SEPARATOR.'Build'.DIRECTORY_SEPARATOR.'hrconvert2-release-fetch.sh').' yq '.escapeshellarg('https://github.com/mikefarah/yq/releases/download/v{version}/yq_linux_amd64'),
        'Source' => 'https://github.com/mikefarah/yq', 'License' => 'MIT')),
      'arm64' => array('Fallback' => array('Method' => 'release', 'Version' => '4.50.1', 'Sha256' => 'cf0a663d8e4e00bb61507c5237b95b45a6aaa1fbedac77f4dc8abdadd5e2b745',
        'BuildCommand' => 'bash '.escapeshellarg($InstLoc.DIRECTORY_SEPARATOR.'Documentation'.DIRECTORY_SEPARATOR.'Build'.DIRECTORY_SEPARATOR.'hrconvert2-release-fetch.sh').' yq '.escapeshellarg('https://github.com/mikefarah/yq/releases/download/v{version}/yq_linux_arm64'),
        'Source' => 'https://github.com/mikefarah/yq', 'License' => 'MIT')),
      'arm' => array('Fallback' => array('Method' => 'release', 'Version' => '4.50.1', 'Sha256' => 'b701dbf07d631cdc219880e89981d185b11c0f50fbe94f08e1cadd854fbcd7fe',
        'BuildCommand' => 'bash '.escapeshellarg($InstLoc.DIRECTORY_SEPARATOR.'Documentation'.DIRECTORY_SEPARATOR.'Build'.DIRECTORY_SEPARATOR.'hrconvert2-release-fetch.sh').' yq '.escapeshellarg('https://github.com/mikefarah/yq/releases/download/v{version}/yq_linux_arm'),
        'Source' => 'https://github.com/mikefarah/yq', 'License' => 'MIT'))),
    'Required' => FALSE, 'Subsystem' => 'Structured Data', 'Requires' => array('Bubblewrap'),
    'License' => 'MIT', 'Source' => 'https://github.com/mikefarah/yq', 'Purpose' => 'Writes the XML the Data pipeline makes, which Dasel cannot.'),

  // / ---- Typesetting & markup. ----
  // / XeLaTeX typesets a LaTeX document into a PDF for the LaTeX pipeline, & typesets the PDFs Pandoc writes, from v4.2.5.
  // / A document fails on the first package it loads that is not installed, so the packages are the common academic set, about
  // / 0.7 GB with what they depend on. latex-extra, science & pictures add siunitx, physics, TikZ, pgfplots & the algorithm packages
  // / for about 0.1 GB of it. lmodern is the font Pandoc's template loads.
  // / The version asked is the TeX Live release, & 2022 is the floor, which Debian 12 carries. Ubuntu 24.04 carries 2023.
  array('Name' => 'XeLaTeX', 'Binary' => 'xelatex', 'Type' => 'apt', 'Package' => 'texlive-xetex texlive-latex-recommended texlive-latex-extra texlive-fonts-recommended texlive-science texlive-pictures lmodern',
    'MinimumVersion' => '2022', 'VersionCommand' => 'xelatex --version', 'VersionPattern' => '/TeX Live (\d{4})/',
    'Required' => FALSE, 'Subsystem' => 'LaTeX', 'Requires' => array('Bubblewrap'),
    'License' => 'LPPL-1.3c', 'Source' => 'https://tug.org/texlive', 'Purpose' => 'Typesets LaTeX documents, & the PDFs Pandoc writes, into PDF.'),
  // / Pandoc converts between markup & document formats for the Markup pipeline, from v4.2.5, & always runs with --sandbox, which
  // / its manual asks of anyone giving it untrusted input. Without it an RST or Org include, a LaTeX input & an image a document
  // / names read any file the sandbox shows into what a user downloads.
  // / The Pandoc Debian & Ubuntu package keeps its data files outside the program, & --sandbox then cannot write DOCX, PPTX or EPUB,
  // / so the package manager is never asked, & the own copy is the only copy. It is the release binary Pandoc publishes, which
  // / carries its data files inside it, pinned by version & checksum, for x86-64 & ARM64. Pandoc publishes no checksum, so the
  // / checksum is the one this release took on 11/16/2026. 3.10 is the floor, so a package manager's 3.1 is never used, & a 32 bit
  // / ARM host, which has no release binary, has no Markup conversions.
  array('Name' => 'Pandoc', 'Binary' => 'pandoc', 'Type' => 'apt', 'Package' => '',
    'MinimumVersion' => '3.10', 'VersionCommand' => 'pandoc --version', 'VersionPattern' => '/^pandoc (\d+(?:\.\d+)+)/m',
    'Architectures' => array('x86_64', 'arm64'),
    'ArchitectureOverrides' => array(
      'x86_64' => array('Fallback' => array('Method' => 'release', 'Version' => '3.10.1', 'Sha256' => '72948bf5784f560d5ad1876709daca27e0667f262da727bb33f77b58e52df2f5',
        'BuildCommand' => 'bash '.escapeshellarg($InstLoc.DIRECTORY_SEPARATOR.'Documentation'.DIRECTORY_SEPARATOR.'Build'.DIRECTORY_SEPARATOR.'hrconvert2-release-fetch.sh').' pandoc '.escapeshellarg('https://github.com/jgm/pandoc/releases/download/{version}/pandoc-{version}-linux-amd64.tar.gz').' '.escapeshellarg('pandoc-{version}/bin/pandoc'),
        'Source' => 'https://github.com/jgm/pandoc', 'License' => 'GPL-2.0')),
      'arm64' => array('Fallback' => array('Method' => 'release', 'Version' => '3.10.1', 'Sha256' => 'cd3963da375793a4804c65ae538b4f7b9c23f87cac7f6c74a1cf5e2fff7e8d59',
        'BuildCommand' => 'bash '.escapeshellarg($InstLoc.DIRECTORY_SEPARATOR.'Documentation'.DIRECTORY_SEPARATOR.'Build'.DIRECTORY_SEPARATOR.'hrconvert2-release-fetch.sh').' pandoc '.escapeshellarg('https://github.com/jgm/pandoc/releases/download/{version}/pandoc-{version}-linux-arm64.tar.gz').' '.escapeshellarg('pandoc-{version}/bin/pandoc'),
        'Source' => 'https://github.com/jgm/pandoc', 'License' => 'GPL-2.0'))),
    'Required' => FALSE, 'Subsystem' => 'Markup', 'Requires' => array('Bubblewrap'),
    'License' => 'GPL-2.0', 'Source' => 'https://pandoc.org', 'Purpose' => 'Converts between markup & document formats.'),

  // / ---- Email. ----
  // / msgconvert turns an Outlook message into a standard email for the Email pipeline, from v4.2.5. It is a Perl program, & it
  // / reports no version of its own, so the version asked is the version of the Perl module it is part of. Both come in one package,
  // / so the version asked is the version that runs. Debian 12 & Ubuntu 24.04 both carry 0.921.
  array('Name' => 'msgconvert', 'Binary' => 'msgconvert', 'Type' => 'apt', 'Package' => 'libemail-outlook-message-perl',
    'MinimumVersion' => '0.921', 'VersionCommand' => 'perl -MEmail::Outlook::Message -e '.escapeshellarg('print "Email::Outlook::Message $Email::Outlook::Message::VERSION\n";'), 'VersionPattern' => '/Email::Outlook::Message (\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'Email', 'Requires' => array('Bubblewrap'),
    'License' => 'GPL-1.0-or-later', 'Source' => 'https://metacpan.org/pod/Email::Outlook::Message', 'Purpose' => 'Converts Outlook messages to EML & mbox.'),

  // / ---- Graphs. ----
  // / Graphviz draws a graph written in the DOT language for the Graph pipeline, from v4.2.6. dot is the program asked, & it lays a
  // / graph out with whichever of Graphviz's layout engines the graph names. It says its version on its error stream, which the
  // / version command reads. Debian 12 & Ubuntu 24.04 both carry 2.42.2, & Ubuntu's reports itself as 2.43.
  array('Name' => 'Graphviz', 'Binary' => 'dot', 'Type' => 'apt', 'Package' => 'graphviz',
    'MinimumVersion' => '2.42', 'VersionCommand' => 'dot -V', 'VersionPattern' => '/graphviz version (\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'Graphs', 'Requires' => array('Bubblewrap'),
    'License' => 'EPL-1.0', 'Source' => 'https://graphviz.org', 'Purpose' => 'Draws graphs written in the DOT language as pictures & documents.'),

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
  // / The minimum is the release this one bundles & is tested with, so a copy an older ScanCore replaced is reported rather than trusted.
  // / v4.2.3 bundles ScanCore v1.9, which opens zip, gzip & tar containers, checks images & says when it could not read a file in full.
  array('Name' => 'ScanCore', 'Binary' => '', 'Type' => 'bundled', 'Package' => '',
    'MinimumVersion' => '1.9', 'VersionCommand' => 'php '.escapeshellarg($InstLoc.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'ScanCore'.DIRECTORY_SEPARATOR.'ScanCore.php').' -version', 'VersionPattern' => '/ScanCore v([0-9]+\\.[0-9]+)/',
    'Required' => FALSE, 'Subsystem' => 'Virus Scanning', 'Requires' => array('PHP'),
    'License' => 'GPL-3.0', 'Source' => 'Bundled at Resources/ScanCore', 'Purpose' => 'Scans every uploaded file. Written & distributed by this project.'),
  array('Name' => 'ClamAV', 'Binary' => 'clamscan', 'Type' => 'apt', 'Package' => 'clamav clamav-daemon',
    'MinimumVersion' => '0.103', 'VersionCommand' => 'clamscan --version', 'VersionPattern' => '/ClamAV (\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'Virus Scanning', 'Requires' => array(),
    'License' => 'GPL-2.0', 'Source' => 'https://www.clamav.net', 'Purpose' => 'Scans uploads. Only used when virus scanning is enabled.'));
// / -----------------------------------------------------------------------------------