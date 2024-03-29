## APPLICATION INFORMATION ...

Copyright on 3/22/2024 by Justin Grimes, www.github.com/zelon88. ScanCore is a portable, single thread, cross platform, command line virus scanner written in PHP that detects infections based on data match, MD5 hash, SHA1 hash, or SHA256 hash. 

Features include:

- High speed, single thread virus scanner that you use in your command line or terminal.
- Updates virus definitions automatically.
- Small memory footprint with the ability to set a custom memory limit.
- Written in PHP, so it works from Apache or the command line.
- Cross platform. Works in Windows or Linux.
- Fully portable. If you have PHP installed & in your PATH you're good to go.
- Works with portable PHP binaries.
- Virus definitions in plain text that you can actually understand.
- Fully open source, including definitions.
- Highly configurable. Great for scripting, devops, or automation.

This scanner can detect files based on the following criteria:

1. MD5 Hash
2. SHA1 Hash
3. SHA256 Hash
4. Raw Data Match

-----------------------------------------------------------------------------------

## LICENSE INFORMATION ...

This project is protected by the GNU GPLv3 Open-Source license.

-----------------------------------------------------------------------------------

## DEPENDENCY REQUIREMENTS ... 

This application requires Windows or Linux with PHP 8.0 (or later).
  
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
 
     Specify memory limit (in bytes):        -memorylimit ####
                                             -m ####
 
     Specify chunk size (in bytes);          -chunksize ####
                                             -c ####
 
     Enable "debug" mode (more logging):     -debug
                                             -d
 
     Enable "verbose" mode (more console):   -verbose
                                             -v
 
     Force a specific report file:           -reportfile /path/to/file
                                             -rf path/to/file
                                             
     Force a specific configuration file:    -configfile /path/to/file
                                             -cf path/to/file
 
     Force maximum log size (in bytes):      -maxlogsize ###
                                             -ml ###

     Perform definition update:              -updatedefinitions
                                             -ud
-----------------------------------------------------------------------------------

## USAGE TIPS ...

- If the target is a file larger than the [memorylimit] argument it will be chopped into [chunksize] and each chunk will be scanned separately. 
- If the target is a folder you must also specify [recursion] or [no-recursion] via command line arguments.
- If you use the verbose and debug arguments to scan an entire hard drive be prepared for logfiles that are several GB in size with scans that can take days to complete.

-----------------------------------------------------------------------------------

## MORE INFORMATION ...

Currently virus definitions are maintained at [The ScanCore_Definitions Github Repository](https://github.com/zelon88/ScanCore_Definitions). Definition updates can be performed using command line switches. The repository of definitions is organized into different categories. Each portable scanner can subscribe or unsubscruibe to specific definition categories, allowing administrators to build custom definitions tailored to specific servers, roles, or applications.

This scanner was designed for high performance single threaded use. It can be used with the Windows or Linux command-line, or with custom applications such as thread handlers which create & destroy multiple script instances at different targets simultaniously. The whole idea of a fast single-threaded scanner is that you can run several dozen (or hundred) scans at the same time on multiple small targets rather than running one large scan.

-----------------------------------------------------------------------------------

<3 Open-Source
