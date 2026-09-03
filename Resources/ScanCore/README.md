## APPLICATION INFORMATION ...

Copyright on 3/29/2024 by Justin Grimes, www.github.com/zelon88. ScanCore is a portable, single thread, self-updating, cross platform, command line virus scanner written in PHP that detects infections by file hash, file name, content signature, host name, or url. It also classifies what a data file is able to reach out to, which is a different question from whether the file is known to be bad.

Features include:

- High speed, single thread virus scanner that you use in your command line or terminal.
- Updates application code automatically.
- Updates virus definitions automatically.
- Reports a confirmed hash match differently from a heuristic match, so a filename pattern never carries the weight of a known sample.
- Classifies data files for the file and url handlers they carry, and for executable source code.
- Opens zip containers such as word processing documents and ebooks, within a decompression budget that refuses a bomb before it expands.
- Small memory footprint with the ability to set a custom memory limit.
- Keeps a packed binary index of large definition sets, so a million hashes load in under a second.
- Written in PHP, so it works from Apache or the command line.
- Cross platform. Works in Windows or Linux.
- Fully portable. If you have PHP installed & in your PATH you're good to go.
- Virus definitions in plain text that you can actually understand.
- Fully open source, including definitions.
- Highly configurable. Great for scripting, devops, or automation.
- Leaves file ownership and permissions alone when an administrator runs it as root.

This scanner can detect files based on the following criteria:

1. MD5 hash
2. SHA1 hash
3. SHA256 hash
4. File name
5. Content signature, including raw bytes
6. Host name
7. Url

-----------------------------------------------------------------------------------

## LICENSE INFORMATION ...

This project is protected by the GNU GPLv3 Open-Source license.

-----------------------------------------------------------------------------------

## DEPENDENCY REQUIREMENTS ...

This application requires Windows or Linux with PHP 8.0 (or later).

Nothing else is required to scan. The following are optional and each one has a fallback.

- Either the PHP zip extension or the unzip binary, to look inside zip containers. Without either, a container is scanned as bytes and its compressed members are not read.
- Git or cURL, to perform application and definition updates. The built in downloader is tried first.
- The PHP posix extension, to hand new files to the right owner when running as root. Without it, files created as root stay owned by root.

-----------------------------------------------------------------------------------

## VALID SWITCHES / ARGUMENTS / USAGE ...

Quick Start Example:

     C:\Path-To-PHP-Binary.exe C:\Path-To-ScanCore.php C:\Path-To-Scan\ -m [integer] -c [integer] -v -d
     C:\PHP\PHP.exe C:\scanCore\scanCore.php C:\Windows\Temp -memorylimit 4000000000 -chunksize 1000000000 -verbose -debug
     C:\PHP\PHP.exe C:\scanCore\scanCore.php C:\Windows\Temp -m 4000000000 -c 1000000000 -v -d
     C:\PHP\PHP.exe C:\scanCore\scanCore.php C:\Windows\Temp -nr -m 1000000000 -c 200000000 -v -d

Start by opening a command-prompt.
1. Type the absolute path to a portable PHP 8.0+ binary, or use the php command if it's in your PATH.
2. Now type the absolute path to the ScanCore PHP file as the only argument for the PHP binary.
3. Everything after the path to ScanCore will be passed to ScanCore as an argument.
4. The first Argument must be a valid absolute path to the file or folder being scanned.
5. Optional arguments can be specified after the scan path. Separate them with spaces.

Reqiured Arguments Include:

     File or folder to scan:                 /path/to/scan

Optional Arguments Include:

     Show version information:               -version
                                             -ver

     Show help information:                  -help
                                             -h

     Force recursion:                        -recursion
                                             -r

     Force no recursion:                     -norecursion
                                             -nr

     Follow symbolic links while scanning:   -followsymlinks
                                             -fs

     Do not follow symbolic links:           -nofollowsymlinks
                                             -nfs

     Specify memory limit (in bytes):        -memorylimit ####
                                             -m ####

     Specify chunk size (in bytes):          -chunksize ####
                                             -c ####

     Force maximum scan depth (in folders):  -maxdepth ###
                                             -md ###

     Enable "debug" mode (more logging):     -debug
                                             -d

     Enable "verbose" mode (more console):   -verbose
                                             -v

     Force a specific report file:           -reportfile /path/to/file
                                             -rf /path/to/file

     Force a specific configuration file:    -configfile /path/to/file
                                             -cf /path/to/file

     Force a specific definitions file:      -defsfile /path/to/file
                                             -df /path/to/file

     Force maximum log size (in bytes):      -maxlogsize ###
                                             -ml ###

     Report low confidence matches:          -suspicious
                                             -sus

     Report confirmed matches only:          -nosuspicious
                                             -nsus

     Enable content classification:          -classify
                                             -cy

     Disable content classification:         -noclassify
                                             -ncy

     Choose which classifiers run:           -classifiers scad,pdf
                                             -cl scad,pdf

     Inspect inside zip containers:          -inspectarchives
                                             -ia

     Do not open zip containers:             -noinspectarchives
                                             -nia

     Container entry count budget:           -maxarchiveentries ###
                                             -mae ###

     Container entry size budget (bytes):    -maxarchiveentrysize ###
                                             -maes ###

     Container total size budget (bytes):    -maxarchivetotalsize ###
                                             -mats ###

     Container expansion ratio limit:        -maxarchiveratio ###
                                             -mar ###

     Language signatures needed to decide:   -minlanguagesignatures ###
                                             -mls ###

     Shortest usable data signature:         -mindatasignature ###
                                             -mds ###

     Own created files as this user:         -owner www-data
                                             -ow www-data

     Own created files as this group:        -group www-data
                                             -gr www-data

     Leave created files owned by root:      -nopreserveownership
                                             -npo

     Do not use a packed hash index:         -nohashindex
                                             -nhi

     Rebuild the packed hash index:          -rebuildindex
                                             -ri

     Hashes needed before indexing:          -hashindexthreshold ###
                                             -hit ###

     Seconds to wait for an update host:     -connectiontimeout ###
                                             -ct ###

     Choose the update method:               -updatemethod git
                                             -um raw

     Perform definition update:              -updatedefinitions
                                             -ud

     Perform application update:             -updateapplication
                                             -ua

Every argument above has a matching entry in ScanCore_Config.php. The argument wins for that run, and the configuration file supplies the value for every run that does not name one.

-----------------------------------------------------------------------------------

## WHAT SCANCORE REPORTS ...

ScanCore writes three kinds of finding, and each one opens with its own word so a caller can tell them apart without reading further.

     Infected: /path/to/file (Name, SHA256 Hash Match: 7d14bfe...)

A definition matched with high confidence. A whole file hash is an exact match, so it carries this word by default.

     Suspicious: /path/to/file (Name, Name Match: invoice)

A definition matched with medium or low confidence. A file name pattern or a host name is a heuristic rather than proof, so it carries this word by default. Use -nosuspicious to leave these out of the report entirely.

     Classified: /path/to/file (File may contain file or URL handlers - Type: scad, Handlers: include(file), surface(file))

The classifier recognised something the file is able to do. This is never a threat report, and a classification line never begins with the word Infected.

A scan ends with a summary of each kind.

     Scanned 12 files in 1 folders and found 2 potentially infected items.
     Flagged 3 files as suspicious.
     Classified 5 files that may contain file or URL handlers and 5 files that may contain executable code.

-----------------------------------------------------------------------------------

## CONTENT CLASSIFICATION ...

The virus scanner asks whether a file is known to be bad. The classifier asks what a file is able to do if something opens it. Those are different questions and they are reported separately.

A handler is a construct that lets a data file reach out to another file or another host. An OpenSCAD source can name another file with include or surface. A playlist names other hosts by design. A word processing document can carry a relationship pointing at an external address. The classifier reports what is present so a caller can decide whether to proceed. It does not neutralise anything.

Executable code is detected first. A file recognised as source code is reported as code and is not classified for handlers, because at that point the whole file is a handler.

Classifiers are declared in ScanCore_Classifiers.def, which is plain text you can read and extend. A type record maps a name onto the extensions it covers, a lang record declares an executable language, a magic record identifies a type by its opening bytes whatever its extension claims, and a handler line names what to look for. Choose which ones run with the EnabledClassifiers setting or the -classifiers argument.

A zip container is opened and its members are classified individually, which is how a handler inside a word processing document is found at all. Four independent limits govern how far a container may be allowed to expand, and any one of them refuses an entry on its own. Those limits exist because a small container can declare an enormous member.

-----------------------------------------------------------------------------------

## VIRUS DEFINITIONS ...

A definition is one indicator on one line, and nothing about it is positional.

     sha256 Trojan.Win32.DarkComet   = 7d14bfe...  src=malwarebazaar confidence=high
     name   Trojan.Win32.FormBook    = "Receipt.exe"          confidence=low
     host   Malware.C2.Emotet        = "evil.example"         confidence=medium
     url    Malware.Dropper.Foo      = "http://x/y.php"       confidence=medium
     data   Exploit.CVE-2016-7200    = "\x4d\x5a\x90"         scope=pdf

The kind is named on every line, so a hash can never end up in the wrong column. A name indicator is matched against the file name only and a data indicator against file content only. A host indicator sits on a name boundary, so an indicator naming ow.ly cannot match inside snow.lyrics. A byte with no printable form is written as an escape, so a definition file stays a text file you can read in a diff.

Confidence decides which word opens the report line. A whole file hash defaults to high and a string indicator defaults to medium.

The tab delimited format used before version 1.8 is no longer read. A definitions file still in that format is reported by name rather than silently loading as nothing.

A definitions file holding more hashes than the HashIndexThreshold setting gets a packed binary index beside it. The index is rebuilt whenever the definitions file changes, and it is what lets a very large hash set load in well under a second instead of parsing on every scan.

-----------------------------------------------------------------------------------

## EXIT STATUS ...

     0     The requested operation completed. A clean scan and an infected scan both return this.
     1     A fatal error stopped the requested operation.

A caller that wants to know whether anything was found should read the report rather than the exit status, because finding a threat is a successful scan.

-----------------------------------------------------------------------------------

## RUNNING AS ROOT ...

An administrator running an update under sudo would otherwise leave every file owned by root, and the service account that runs ScanCore the rest of the time could no longer write its own report or install its own update.

Anything ScanCore creates while running as root is handed to the user and group that owns the installation directory. Set OwnerUser and OwnerGroup, or use -owner and -group, when the installation directory belongs to somebody other than the account that runs ScanCore day to day.

A file being replaced by an update keeps the owner and the mode it already had. The mode is preserved whoever is running, and the owner is restored only when running as root. None of this does anything at all unless the process is root.

-----------------------------------------------------------------------------------

## USAGE TIPS ...

- Files are always read in chunks of [chunksize] bytes, whatever their size. A signature spanning two chunks is still found, so a small chunk size is safe and lowers peak memory.
- The [memorylimit] argument is applied to the interpreter as a ceiling when the scan starts.
- If the target is a folder you must also specify [recursion] or [no-recursion] via command line arguments.
- Symbolic links are skipped unless you ask for them with -followsymlinks. A looping link would otherwise make a recursive scan walk the same files repeatedly.
- If you use the verbose and debug arguments to scan an entire hard drive be prepared for logfiles that are several GB in size with scans that can take days to complete.
- The report file rotates once it reaches [maxlogsize] bytes rather than growing without limit.
- ScanCore never scans its own definitions file or its own report file, both of which are full of signatures by design.

-----------------------------------------------------------------------------------

## MORE INFORMATION ...

Currently virus definitions are maintained at [The ScanCore_Definitions Github Repository](https://github.com/zelon88/ScanCore_Definitions). Definition updates can be performed using command line switches. The repository of definitions is organized into different categories. Each portable scanner can subscribe or unsubscruibe to specific definition categories, allowing administrators to build custom definitions tailored to specific servers, roles, or applications.

That repository is also a build pipeline rather than a folder of hand edited files. A recipe describes one source, saying where to fetch it, how to read it and whether it may be republished. Raw downloads are kept exactly as they arrived so a build is reproducible and a bad feed stays traceable. A build policy refuses an indicator that is too short, too repetitive, or on an allowlist of values that must never become an indicator, and every refusal is written to a build report with the reason. You can run the same build at home with one command.

An update is staged before it is applied. A download is written beside its target, verified, and only then moved into place, with the previous file kept aside until the new one has proven itself. A failed verification leaves the installation untouched, and a failed test puts the previous files back.

This scanner was designed for high performance single threaded use. It can be used with the Windows or Linux command-line, or with custom applications such as thread handlers which create & destroy multiple script instances at different targets simultaniously. The whole idea of a fast single-threaded scanner is that you can run several dozen (or hundred) scans at the same time on multiple small targets rather than running one large scan.

-----------------------------------------------------------------------------------

<3 Open-Source