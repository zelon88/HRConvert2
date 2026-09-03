<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / ScanCore, Copyright on 3/31/2024 by Justin Grimes, www.github.com/zelon88 
// / 
// / LICENSE INFORMATION ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / BSD or MIT licensing is available. Reach out to @zelon88 for more information.
// / https://www.gnu.org/licenses/gpl-3.0.html
// / 
// / APPLICATION INFORMATION ...
// / This application is designed to scan files & folders for viruses.
// / 
// / FILE INFORMATION ...
// / v1.8.
// / This file contains the configuration entries of the ScanCore application.
// / 
// / VERSION COMPATIBILITY ...
// / This file is a minimum match against the core.
// / A configuration file newer than the core is accepted.
// / A configuration file older than the core is refused, because entries may be absent.
// / Any entry that is missing or invalid falls back to a built in default & is reported.
// / 
// / <3 Open-Source
// / -----------------------------------------------------------------------------------



// / -----------------------------------------------------------------------------------
// / General Information ...
// / 
// /  --Allow Application Updates--
// /   Allow application updates. Requires git. Will replace ScanCore_Config.php & rename the original.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$ApplicationUpdates = TRUE;
// /  --Application Update URL--
// /   The URL of a Git repository containing application updates.
// /   Valid options are a URL to a ScanCore source code Git repository, formatted as a string.
// /   Default is 'https://github.com/zelon88/ScanCore'.
$ApplicationUpdateURL = 'https://github.com/zelon88/ScanCore';
// /  --Application Update Domain--
// /   The host name that you intend to use for application updates.
// /   ScanCore will test this connection before attempting any update operations.
// /   Supply a bare host name. A scheme or a trailing path is stripped before the test.
// /   Default is 'github.com'.
$ApplicationUpdateDomain = 'github.com';
// /  --Application Repository Name--
// /   The name of the repository containing the application updates to use.
// /   This also names the temporary folder that updates are staged in.
// /   Valid options are the name of the repository, formatted as a string.
// /   Default is 'ScanCore'.
$ApplicationRepositoryName = 'ScanCore';
// /  --Application Branch Name--
// /   The name of the repository branch containing the application updates to use.
// /   Valid options are the name of the application repository branch, formatted as a string.
// /   Default is 'master'.
$ApplicationBranchName = 'master';
// /  --Application Subscriptions--
// /   The type of application updates to subscribe to.
// /   Must be formatted as an array.
// /   A name containing two dots is refused, because it would write outside the installation.
// /   Valid options are 'README.md', 'ScanCore.php', 'ScanCore_Config.php', 'index.html', 'Documentation/CHANGELOG.txt', 'Documentation/index.html'.
// /   Default is 'README.md', 'ScanCore.php', 'ScanCore_Config.php', 'index.html', 'Documentation/CHANGELOG.txt', 'Documentation/index.html'.
$ApplicationUpdateSubscriptions = array('README.md', 'ScanCore.php', 'ScanCore_Config.php', 'index.html', 'ScanCore_Classifiers.def', 'Documentation/CHANGELOG.txt', 'Documentation/index.html');
// /  --Allow Definition Updates--
// /   Allow definition updates.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$DefinitionUpdates = TRUE;
// /  --Definition Update URL--
// /   The URL of a Git repository containing the definition updates to use.
// /   Valid options are a URL to a ScanCore source code Git repository, formatted as a string.
// /   Default is 'https://github.com/zelon88/ScanCore_Definitions'.
$DefinitionUpdateURL = 'https://github.com/zelon88/ScanCore_Definitions';
// /  --Definition Update Domain--
// /   The host name that you intend to use for definition updates.
// /   ScanCore will test this connection before attempting any update operations.
// /   Supply a bare host name. A scheme or a trailing path is stripped before the test.
// /   Default is 'github.com'.
$DefinitionUpdateDomain = 'github.com';
// /  --Definition Repository Name--
// /   The name of the repository containing the definition updates to use.
// /   This also names the temporary folder that updates are staged in.
// /   Valid options are the name of the repository, formatted as a string.
// /   Default is 'ScanCore_Definitions'.
$DefinitionRepositoryName = 'ScanCore_Definitions';
// /  --Definition Branch Name--
// /   The name of the repository branch containing the definition updates to use.
// /   Valid options are the name of the definition repository branch, formatted as a string.
// /   Default is 'main'.
$DefinitionBranchName = 'main';
// /  --Definition Subscriptions--
// /   The type of definition updates to subscribe to.
// /   Must be formatted as an array.
// /   Valid options are 'Virus', 'Malware', 'Pup'.
// /   Default is 'Virus', 'Malware', 'PUP'.
$DefinitionsUpdateSubscriptions = array('Virus', 'Malware', 'PUP');
// /  --Update Method--
// /   The method to use while performing updates.
// /   If 'git' is installed locally, the 'git' option is preferred.
// /   If 'git' is not installed & cannot be installed, the 'raw' option can be used instead.
// /   An unrecognized value falls back to 'git' & is reported as a warning.
// /   Valid options are 'git', 'raw'.
// /   Default is 'git'.
$UpdateMethod = 'git';
// /  --Connection Timeout--
// /   Number of seconds to wait for an update host before giving up on it.
// /   Must be formatted as an integer, or an equation that evaluates to an integer.
// /   Default is 15.
$ConnectionTimeout = 15;
// /  --Default Maximum Log Size--
// /   Number of bytes to store in each logfile before splitting to a new one.
// /   The previous report file is renamed with a date suffix once this size is reached.
// /   Must be formatted as an integer, or an equation that evaluates to an integer.
// /   Default is 1024*1024*32.
$DefaultMaxLogSize = 1024*1024*32;
// /  --Enable Debug Mode--
// /   Enable "debug" mode (more logging).
// /   Valid options are TRUE or FALSE.
// /   Default is FALSE.
$Debug = FALSE;
// /  --Enable Verbose Mode--
// /   Enable "verbose" mode (more console).
// /   Valid options are TRUE or FALSE.
// /   Default is FALSE.
$Verbose = FALSE;
// /  --Memory Limit--
// /   The maximum number of bytes of memory to allocate to file scan operations.
// /   This value is applied to the interpreter as a ceiling when the scanner starts.
// /   Must be formatted as an integer, or an equation that evaluates to an integer.
// /   Default is 1024*1024*512.
$DefaultMemoryLimit = 1024*1024*512;
// /  --Chunk Size--
// /   Files are read this many bytes at a time, regardless of how large they are.
// /   A signature spanning two chunks is still detected, so a small value is safe.
// /   A smaller value lowers peak memory use. A larger value lowers the number of reads.
// /   Must be formatted as an integer, or an equation that evaluates to an integer.
// /   Default is 1024*1024*8.
$DefaultChunkSize = 1024*1024*8;
// /  --Maximum Scan Depth--
// /   The number of folders the scanner will descend through during a recursive scan.
// /   This is the backstop that stops a looping folder structure from running forever.
// /   Must be formatted as an integer, or an equation that evaluates to an integer.
// /   Default is 64.
$MaxScanDepth = 64;
// /  --Follow Symbolic Links--
// /   Follow symbolic links encountered during a scan.
// /   Leaving this disabled stops a link from sending the scanner outside the scan path.
// /   Valid options are TRUE or FALSE.
// /   Default is FALSE.
$FollowSymlinks = FALSE;
// /  --Minimum Data Signature Length--
// /   Data signatures shorter than this many characters are ignored when loading definitions.
// /   A very short signature matches ordinary content & produces false detections.
// /   Raise this value if the definition set is producing detections you do not trust.
// /   Must be formatted as an integer, or an equation that evaluates to an integer.
// /   Default is 4.
$MinimumDataSignatureLength = 4;
// /  --Classify Content--
// /   Inspect each scanned file for executable code & for file or URL handlers.
// /   A handler is a construct that lets a data file reach out to another file or host.
// /   This reports what a file can do. It does not stop the file from doing it.
// /   Findings are written as Classified: lines, never as Infected: lines.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$ClassifyContent = TRUE;
// /  --Enabled Classifiers--
// /   The classifiers that are switched on. Anything not named here is never loaded,
// /   so a disabled classifier costs nothing to scan for.
// /   A name is matched without regard to case, so SCAD, Scad & scad are the same.
// /   The special entry 'Language' controls detection of executable code. Remove it and
// /   ScanCore stops reporting source files as code & classifies them for handlers instead.
// /   Every other entry names a type declared in the classifier definitions file.
// /   Must be formatted as an array.
// /   Valid options are 'Language', 'SCAD', 'PDF', 'Document', 'Spreadsheet',
// /   'Presentation', 'XPS', 'SVG', 'Markup', 'Stream', 'Transport', 'Model', 'Drawing', 'Ebook',
// /   'Subtitle', plus any type you declare yourself in ScanCore_Classifiers.def.
// /   Default is all of them.
$EnabledClassifiers = array('Language', 'SCAD', 'PDF', 'Document', 'Spreadsheet', 'Presentation', 'XPS', 'SVG', 'Markup', 'Stream', 'Transport', 'Model', 'Drawing', 'Ebook', 'Subtitle');
// /  --Inspect Archived Content--
// /   Open a zip container & classify the entries inside it.
// /   A word processing document, a presentation, an ebook & an XPS package are all
// /   zip files whose interesting content is compressed, so a byte scan of the
// /   container itself finds nothing at all.
// /   Every entry is measured against the decompression budget below before it is read.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$InspectArchivedContent = TRUE;
// /  --Maximum Archive Entries--
// /   The number of entries ScanCore will look at inside one container.
// /   This is the first defence against a container holding millions of small members.
// /   Must be formatted as an integer, or an equation that evaluates to an integer.
// /   Default is 512.
$MaxArchiveEntries = 512;
// /  --Maximum Archive Entry Size--
// /   The number of bytes ScanCore will decompress from any single container entry.
// /   The read is bounded by this value & not by what the container claims, so an entry
// /   with a dishonest header cannot talk ScanCore into a larger read than this.
// /   Must be formatted as an integer, or an equation that evaluates to an integer.
// /   Default is 1024*1024*8.
$MaxArchiveEntrySize = 1024*1024*8;
// /  --Maximum Archive Total Size--
// /   The number of bytes ScanCore will decompress from one container in total.
// /   Reading stops at this point & whatever was found so far is still reported.
// /   Must be formatted as an integer, or an equation that evaluates to an integer.
// /   Default is 1024*1024*64.
$MaxArchiveTotalSize = 1024*1024*64;
// /  --Maximum Archive Compression Ratio--
// /   The number of times an entry may expand beyond its stored size before it is
// /   refused. An entry that expands a thousand times is the shape of a decompression
// /   bomb, & it is refused before any of it is decompressed rather than afterwards.
// /   Ordinary markup compresses somewhere between five & twenty times.
// /   Must be formatted as an integer, or an equation that evaluates to an integer.
// /   Default is 200.
$MaxArchiveCompressionRatio = 200;
// /  --Classifier File Name--
// /   The filename for the ScanCore classifier definition file.
// /   This file ships with the application & is updated with the application.
// /   Default is 'ScanCore_Classifiers.def'.
$ClassifierFileName = 'ScanCore_Classifiers.def';
// /  --Minimum Language Signatures--
// /   Number of language signatures a file must carry before it is called that language.
// /   This only applies to a file whose extension names no language, because an
// /   extension that does name one settles the question by itself.
// /   A value of one will report ordinary prose as source code.
// /   Must be formatted as an integer, or an equation that evaluates to an integer.
// /   Default is 2.
$MinimumLanguageSignatures = 2;
// /  --Report Suspicious Findings--
// /   Report a low or medium confidence definition match as a Suspicious line.
// /   A high confidence match is always reported as an Infected line & this does not
// /   change that. A caller testing a report for "Infected: " is unaffected either way.
// /   Set this to FALSE to keep only confirmed matches in the report.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$ReportSuspicious = TRUE;
// /  --Use Hash Index--
// /   Keep a packed binary index beside the definitions file & load that instead.
// /   A hash held in a PHP array costs far more than the hash itself, because the
// /   array holds a whole record for every one. A million samples that way needs a
// /   gigabyte of memory & eleven seconds to parse. The same million as raw bytes
// /   needs thirty four megabytes & fifteen milliseconds, because it is one string
// /   read straight off disk & searched by halving.
// /   The index is rebuilt automatically whenever the definitions file changes.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$UseHashIndex = TRUE;
// /  --Hash Index Threshold--
// /   Number of hashes a definitions file must hold before an index is built for it.
// /   A small set is cheaper to parse than to index & needs no second file on disk.
// /   Must be formatted as an integer, or an equation that evaluates to an integer.
// /   Default is 10000.
$HashIndexThreshold = 10000;
// /  --Preserve Ownership--
// /   Hand anything ScanCore creates to the user & group that owns the installation.
// /   This only does anything when ScanCore is running as root. An administrator running
// /   an update under sudo would otherwise leave every file owned by root, & the service
// /   user that runs ScanCore the rest of the time could no longer write its own report
// /   file or install its own definition update.
// /   The mode & owner of a file being replaced by an update are always preserved,
// /   whoever is running, so an update never quietly changes a permission you set.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$PreserveOwnership = TRUE;
// /  --Owner User--
// /   The user that should own anything ScanCore creates while running as root.
// /   Leave this empty to inherit from whoever owns the installation directory, which
// /   is correct for almost every installation.
// /   Set it to your web server user, such as 'www-data', if the installation directory
// /   is owned by somebody other than the account that runs ScanCore day to day.
// /   Valid options are a user name, formatted as a string, or an empty string.
// /   Default is ''.
$OwnerUser = '';
// /  --Owner Group--
// /   The group that should own anything ScanCore creates while running as root.
// /   Leave this empty to inherit from the installation directory.
// /   Valid options are a group name, formatted as a string, or an empty string.
// /   Default is ''.
$OwnerGroup = '';
// /  --File Permissions--
// /   The mode applied to a file ScanCore creates while running as root.
// /   A file that is being replaced keeps the mode it already had instead.
// /   Must be formatted as an octal integer.
// /   Default is 0644.
$FilePermissions = 0644;
// /  --Directory Permissions--
// /   The mode applied to a directory ScanCore creates while running as root.
// /   Must be formatted as an octal integer.
// /   Default is 0755.
$DirectoryPermissions = 0755;
// /  --Configuration Version--
// /   The version of this file, used for internal version integrity checks.
// /   Must be formatted as a string. Must be equal to or newer than the ScanCore.php file.
$ConfigVersion = 'v1.8';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Directory locations ...
// / 
// /  --Scan Location--
// /   The default path to scan if run with no input scan path argument.
// /   A relative path is resolved against the installation directory.
// /   Default is ''.
$ScanLoc = '';
// /  --Report Location--
// /   The path where report files are stored.
// /   A relative path is resolved against the installation directory.
// /   Default is 'Logs'.
$ReportDir = 'Logs';
// /  --Report File Name--
// /   The filename for the ScanCore report file.
// /   Default is 'ScanCore_Report.txt'.
$ReportFileName = 'ScanCore_Report.txt';
// /  --Definitions File Name--
// /   The filename for the ScanCore virus definition file.
// /   Default is 'ScanCore_Combined_Definitions.def'.
$DefsFileName = 'ScanCore_Combined_Definitions.def';
// /  --Installation Directory--
// /   The absolute path where this application is installed.
// /   Default is realpath(dirname(__FILE__)).
$InstallDir = realpath(dirname(__FILE__));
// /  --Definitions File--
// /   The absolute path where the Definitions File can be found.
// /   Default is  $InstallDir.DIRECTORY_SEPARATOR.$DefsFileName.
$DefsFile = $InstallDir.DIRECTORY_SEPARATOR.$DefsFileName;
// / -----------------------------------------------------------------------------------
