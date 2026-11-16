<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRProprietary Engine, Copyright on 9/4/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / File Information ...
// / v4.2.4.
// / THIS IS THE ENGINE'S HALF OF THE CONFIGURATION TEMPLATE, & everything below the marker is a
// / real config.php fragment at the Engine's default values. Not a description of one.
// / Every setting the kernel or the Engine reads is here, with its documentation, & nowhere else.
// /
// / config.php is one file with two halves, & each has a template.
// /   This file                              Every setting the kernel or the Engine reads.
// /   Resources/Contract/config-template.php The application's half. The header & guard a config.php
// /                                          opens with, $ConfigVersion & every setting only the
// /                                          application reads.
// / The kernel reads the names in both halves to know which settings config.php must hold & which
// / become globals, so no list of setting names is kept anywhere else.
// / See configurationTemplatePaths() in Resources/Kernel/kernel.php.
// / A missing config.php is written from both halves, the Engine's settings first in each section
// / the two share. A repair adds whatever either holds that a configuration lacks, & AN OPERATOR
// / VALUE IS NEVER REPLACED by a default here.
// / A default that names the application, its name, its title, its folders & where its updates come
// / from, is the application's to give, in its contract, as $EngineSettingDefaults. The value here is
// / what an application that gives none is written.
// / Adding a setting the Engine reads means adding it here & to engineConfigModel() in
// / Resources/Engine/engine.php, & raising $ConfigVersion in the application's half & the
// / $RequiredConfigVersion its entry point pins, because the kernel refuses a config.php without it.
// / This file belongs to the Engine & is replaced wholesale when a newer Engine arrives.
// / Everything below the marker is copied into a generated configuration, & nothing above it is.
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by an application.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! Engine-35000, The Engine configuration template cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / Copy from here down.
// / ------------------------------
// / ---Security Informations---
// /
// /  --Server URL--
// /   Externally or internally accesible domain or IP.
// /   Do not include a trailing slash.
// /   Default is localhost.
$URL = 'localhost';
// /  --Enable Memory Protection--
// /   This application includes an extremely robust, heavily fortified memory protection routines.
// /   The memory routines contained in this application are designed to protect sensitive data against debugging tools.
// /   The memory routines contained in this application rely on defensive coding standards established in 'Documentation/CODING_CONVENTIONS.txt'.
// /   This setting controls the default fallback behaviour in the event that a sensitive memory operation failed to cleanup sensitive memory.
// /   If set to TRUE, failed memory protection operations are considered fatal. 
// /   If set to TRUE, the core will produce fatal ERROR 40000 upon failure of a sensitive memory cleanup operation.
// /   If set to FALSE, failed memory protection operations are not considered fatal.
// /   If set to FALSE, failed memory protection operations will result in a non-fatal WARNING being written to the logfile.
// /   If this is set to TRUE or FALSE, a failed memory protection operation will result in a log entry containing the origin function.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$EnableMemoryProtection = TRUE;
// /  --Delete Build Environment--
// /   Automatically remove the build environment when it is no longer needed.
// /   Production servers should not keep the 'Build' folder in the installation as a security precaution.
// /   If set to TRUE, the Dockerfile in Documentation/Build & any 'Build' folder in the root of the
// /   installation are deleted. Only the files directly inside that 'Build' folder are
// /   removed. A folder inside it is left where it is & the failure is logged as error 26000.
// /   Documentation/Build itself is NOT removed. The Resource Manager & the source builds read scripts
// /   from it while the application runs.
// /   If set to FALSE, nothing is deleted.
// /   A build environment could be used by an adversary to obtain configuration information about the application or server.
// /   This will NOT remove any documentation, logs, or required configuration files.
// /   If you want to keep the 'Build' folder, consider moving it out of the hosted '/var/www/html' directory.
// /   It is recommended to eventually set this to TRUE.
// /   Valid options are TRUE or FALSE.
// /   Default is FALSE.
$DeleteBuildEnvironment = FALSE;
// /  --Delete Development Documentation--
// /   Automatically remove 'README.md' & 'Documentation/CHANGELOG.txt' files when they are no longer needed.
// /   Production servers should not keep these files in the installation as a security precaution.
// /   If set to TRUE, the 'README.md' & 'Documentation/CHANGELOG.txt' files will be deleted.
// /   If set to FALSE, the 'README.md' & 'Documentation/CHANGELOG.txt' files will NOT be deleted.
// /   These files could be used by an adversary to obtain configuration information about the application or server.
// /   This will NOT remove any other documentation, logs, or required configuration files.
// /   If you want to keep these files, consider moving them out of the hosted '/var/www/html' directory.
// /   It is recommended to eventually set this to TRUE.
// /   Valid options are TRUE or FALSE.
// /   Default is FALSE.
$DeleteDevelopmentDocumentation = FALSE;
// /  --Maintain HTAccess--
// /   Whether the application writes & maintains the .htaccess that protects its DATA directory.
// /   The DATA directory sits inside the web root & holds the files users upload & convert,
// /   because a browser has to be able to fetch them by URL. That makes the web server hand
// /   out user supplied content from this application's own origin, & for any format a
// /   browser treats as ACTIVE that content is code rather than data. An SVG is the clear
// /   case; it is served as image/svg+xml & a script element inside it executes.
// /   The rules that close this belong in the server configuration, which the -fp argument
// /   writes & activates. The .htaccess is the FALLBACK for a server this cannot configure.
// /   If set to TRUE, the -fp & --setup arguments write the file, & every web request checks
// /   that it still matches this release & rewrites it when it does not.
// /   If set to FALSE, the file is never written, & a file this application previously wrote
// /   is REMOVED so that turning this off actually takes effect. A file an administrator
// /   wrote themselves is never touched either way.
// /   Set this to FALSE where the DATA rules are already applied in the server configuration
// /   & a second copy in a .htaccess would be redundant, or where policy forbids the
// /   application writing into the web root at all.
// /   Turning this off does NOT make the DATA directory safe by itself. Run the -fp argument
// /   & read the DATA exposure line it prints to confirm the tree is still protected.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$MaintainHTAccess = TRUE;
// /  --Require Sandbox--
// /   Whether a conversion that cannot be isolated is refused or run unprotected.
// /   A conversion dependency parses an untrusted file & most of them have a history of
// /   memory corruption bugs. Bubblewrap bounds what a successful exploit can reach.
// /   If set to TRUE, a server without a working sandbox refuses every conversion that
// /   depends on one. That is every type except documents.
// /   If set to FALSE, those conversions run unprotected & every one writes a warning.
// /   In a container this setting is not read. --Require Sandbox On Docker-- decides there.
// /   Run -v to see whether this server has a working sandbox.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$RequireSandbox = TRUE;
// /  --Require Sandbox On Docker--
// /   Whether a conversion that cannot be isolated is refused when running in a container.
// /   This is the container equivalent of --Require Sandbox-- & exists because a container
// /   cannot build a sandbox at all unless it was started with the correct options.
// /   Outside a container this setting is not read. --Require Sandbox-- decides there.
// /   Bubblewrap builds its sandbox from unprivileged user namespaces, & the default
// /   container seccomp & AppArmor profiles both block the syscalls that requires.
// /   A container started normally therefore has NO working sandbox, & requiring one would
// /   mean refusing every conversion except documents.
// /   If set to FALSE, a container without a working sandbox runs conversions unprotected.
// /   Every one of them writes a warning unless --Throw Sandbox Warning-- is disabled.
// /   If set to TRUE, a container without a working sandbox refuses those conversions,
// /   which matches the behaviour of a bare metal installation.
// /   A container that CAN build a sandbox always gets one. This setting only decides what
// /   happens when it cannot.
// /   To give a container a working sandbox, start it with the following options.
// /     --security-opt seccomp=unconfined
// /     --security-opt apparmor=unconfined
// /   Some hardened hosts block unprivileged user namespaces at the kernel level, & no
// /   container option overrides that. Those hosts need a host side change instead.
// /   Run -v inside the container to see whether a sandbox works.
// /   The container is detected automatically & only when two independent signals agree,
// /   because a false positive would relax this control on a bare metal server.
// /   Valid options are TRUE or FALSE.
// /   Default is FALSE.
$RequireSandboxOnDocker = FALSE;
// /  --Throw Sandbox Warning--
// /   Whether an unprotected conversion writes a warning to the log.
// /   A conversion runs unprotected only when bubblewrap is unavailable & --Require Sandbox--
// /   is FALSE, which is a state the administrator chose deliberately.
// /   If set to TRUE, every such conversion writes a warning. A busy server on which the
// /   sandbox is permanently unavailable will produce one per conversion.
// /   If set to FALSE, those warnings are suppressed. The state is still reported by
// /   -v & is still recorded once at startup.
// /   This does NOT suppress the refusal that occurs when --Require Sandbox-- is TRUE.
// /   A refusal is a failure & is always reported.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$ThrowSandboxWarning = TRUE;
// /  --Stream Duration Timeout--
// /   Set the maximum amount of time in minutes that FFMPEG will stream a file from a streaming provider for a user.
// /   This setting must be lower than the PHP execution timer set in php.ini.
// /   This setting must be lower than the --File Deletion Age Threshold-- located in the ---General Information--- section of this config.php file.
// /   This setting is costly because it requires FFMPEG & the application to both maintain dedicated threads for the duration of the users stream.
// /   On popular public facing servers, setting this too high can result in the server becoming overwhelmed by on-going streams.
// /   Valid options are integers greater than 1, but not more than the PHP execution time or the --File Deletion Age Threshold--.
// /   This option MUST be set higher than 0.
// /   Default is 15.
$StreamWatchTimeout = 15;
// /  --Stream Connection Timeout--
// /   Set the minimum amount of time in seconds that FFMPEG will wait after attempting to make a connection to a remote stream provider.
// /   Valid options are integers between 3 and 10.
// /   This option MUST be set higher than 2.
// /   Default is 10.
$StreamConnectionTimeout = 10;
// /  --Allow Streams Over HTTP--
// /   During stream conversions, the application will attempt to connect to the stream provider specified in the stream file provided by the user.
// /   If set to TRUE, the application will attempt to connect to non-encrypted, plain-text providers over HTTP when specified by the stream file.
// /   If set to FALSE, the application will not utilize HTTP.
// /   Requires FFMPEG v6.1 or later. Note that FFMPEG v2.0 through v6.0 carry a severe vulnerability related to downloading stream files.
// /   If you have FFMPEG v6.0 or earlier installed, disable Stream Conversions & remove m3u8 from the list of supported file formats.
// /   Check FFMPEG version by opening a terminal on the server and running 'ffmpeg -v'
// /   Valid options are TRUE or FALSE.
// /   Default is FALSE.
$AllowStreamOverHTTP = FALSE;
// /  --Maximum Stream Inspection Size--
// /   The Stream Inspector will download up to this many bytes of a manifest file for validation during operation.
// /   The max file potential download amount observed at the server per stream request is...
// /   --Stream Inspection Layers--  X  --StreamInspectionFilesPerLayer--  X  --MaxStreamInspectionFileSize--
// /   Valid options are integers greater than 940.
$MaxStreamInspectionFileSize = 8191;
// /  --Allow Unprivileged Namespaces--
// /   Permits the application to switch off kernel.apparmor_restrict_unprivileged_userns, as a last resort.
// /   Ubuntu 23.10 & later turn that restriction on, & every sandbox is built in an unprivileged namespace.
// /   Where the restriction is on, the application builds each sandbox through its own copy of bubblewrap, which only root & the web server's group may run.
// /   Where it is off, the system's bubblewrap builds them, because it needs no profile & stays up to date with the host.
// /   Its own AppArmor profile is attached to that copy, as Ubuntu attaches its own to Steam, Flatpak & Chrome.
// /   --setup --install-depends, --update-depends & --install-complete install the copy, & so does an update.
// /   -fp only installs the profile.
// /   Where the copy works, a restriction in force stays in force & this setting changes nothing.
// /   The restriction is never switched off to cover a copy that is missing or that the web server's account cannot run.
// /   One an earlier release switched off stays off until it is switched back on, & -fp then lets it go.
// /   Other programs on the host may rely on it by then, so the application never switches it back on itself.
// /   Once the copy is in place, -fp & -v say so & name the command that switches it back on.
// /   Where bubblewrap refuses a sandbox twice, the -fp argument switches the restriction off, & nothing else ever does.
// /   An attempt a time limit or a signal cut short is never taken for a refusal.
// /   The restriction protects a general purpose desktop from a hostile local user.
// /   On a dedicated appliance it disables the sandbox that isolates every hostile file the machine exists to process.
// /   That file is the threat which actually arrives on an appliance like this one.
// /   Set this to FALSE on a shared host carrying untrusted local users.
// /   The restriction is then never switched off, & one the application switched off is switched back on by the next -fp.
// /   Where kernel.apparmor_restrict_unprivileged_unconfined is 0, any local account can enter a profile that permits a
// /   namespace by name, this one & the distribution's own among them. Setting it to 1 closes that for every profile, &
// /   costs the application nothing, because its profile is attached by path rather than entered by name. Do so on a shared host.
// /   Conversions will refuse where the copy does not work, unless --Require Sandbox-- is also FALSE.
// /   Setting both to FALSE runs every conversion unprotected & is worse again.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$AllowUnprivilegedNamespaces = TRUE;
// /  --Permitted Private Ranges--
// /   Private address ranges this server MAY fetch from, as CIDR, for stream inspection &
// /   URL downloads only.
// /
// /   Empty is the default & means what it has always meant. A private, reserved or
// /   loopback address is refused, & that is the right answer for anything a stranger can
// /   reach.
// /
// /   It is not the right answer everywhere. An intranet installation with a media server
// /   at 10.0.4.20 or a NAS at 192.168.1.50 is asking this application to reach a resource
// /   the administrator owns, on a network nobody outside can address. Refusing that is
// /   not security, it is an application refusing to do its job on the network it was
// /   installed on.
// /
// /   Example:
// /     $PermittedPrivateRanges = array('10.0.4.0/24', '192.168.1.0/24');
// /
// /   NAME THE SMALLEST RANGE THAT CONTAINS WHAT YOU NEED. '10.0.0.0/8' permits sixteen
// /   million addresses & every service on all of them, including things nobody remembers
// /   installing. A /24 around one server is a decision. A /8 is a shrug.
// /
// /   Three ranges are refused NO MATTER WHAT IS LISTED HERE & listing them does nothing.
// /     127.0.0.0/8 & ::1     This host. Every service bound to localhost was bound there
// /                           precisely so nothing off this machine could reach it, & a
// /                           fetcher that reaches them hands that decision to whoever can
// /                           type a URL.
// /     169.254.0.0/16        Link local, which is where cloud metadata lives. Reaching
// /                           169.254.169.254 on a cloud host returns credentials.
// /     ::ffff:0:0/96 forms   IPv4 written as IPv6. The address is judged after unwrapping,
// /                           so this is not a range you can list your way around.
// /
// /   Every fetch from a permitted range is logged as a warning naming the address, because
// /   an allowance an administrator forgot they made should be visible in a log rather than
// /   only in this file.
// /   Default is an empty array.
$PermittedPrivateRanges = array();
// /  --URL Download Maximum Bytes--
// /   The largest file this server will fetch on a user's behalf, in bytes.
// /   A fetch is a stranger spending YOUR bandwidth & YOUR disk, so it needs a ceiling that
// /   an upload does not, because an upload is bounded by what a browser will push.
// /   The fetch asks for no more than this figure. A server that honours the request stops
// /   at it, & the file is placed & reported as truncated. A server that ignores the request
// /   & sends more fails the fetch instead, & nothing is placed.
// /   0 means no ceiling. Do not use 0 on anything a stranger can reach.
// /   Valid options are integers 0 and larger.
// /   Anything else is replaced with the default & a warning is written.
// /   Default is 2147483648, which is two gigabytes.
$URLDownloadMaximumBytes = 2147483648;
// /  --Scan URL Downloads--
// /   Whether a fetched file is scanned before it is placed in the session.
// /   An upload is scanned. A fetch was not, which meant the easiest way to get an
// /   unscanned file onto this server was to ask it to fetch one.
// /   The scanner is whichever --Default Virus Scanner-- names.
// /   A file that fails a scan is NOT placed in the session & the temporary copy is
// /   removed, so nothing infected reaches a place a user can convert or download it from.
// /   Default is TRUE.
$ScanURLDownloads = TRUE;
// /  --Default Virus Scanner--
// /   Which scanner runs when the application scans a file on its own.
// /   The name matches a scanner pipeline's family, case insensitively, so the scanners on
// /   offer are the scanner pipelines the application ships & any an installation adds.
// /     scancore   Ships inside the application, so it is present wherever it is. The default.
// /     clamav     Installed on the host. Better signatures, & it has to be there.
// /   It is read only where the application's contract maps the scanner a shared layer asks for.
// /   A user may choose the other one if the interface offers it. This decides what runs
// /   when nobody chose.
// /   A name no pipeline provides is an error rather than a silent fall back to whatever
// /   happens to be installed, because a scan that quietly used a different scanner than
// /   the one an administrator configured is worse than a scan that refused.
// /   Default is 'scancore'.
$DefaultVirusScanner = 'scancore';
// /  --Environment Manager May Repair--
// /   Whether the Environment Manager may change this host, or only report on it.
// /   It runs as root on a timer, opens no socket & reads no input from anywhere but this
// /   file. See Documentation/ABOUT_ENVIRONMENT_MANAGER.txt.
// /   FALSE means it looks, logs what has drifted & changes nothing. That is the default &
// /   is what a new installation should run until an administrator has read what it WOULD
// /   have done.
// /   TRUE means it also repairs, once a day, & a repair is what --fix-permissions does. It
// /   calls the same function, so there is no second definition of a correct installation.
// /   The one difference is that nobody is watching it, so it starts & enables no service.
// /   A repair is logged as a warning rather than as normal activity, because an
// /   installation repairing the same thing every day is not healthy.
// /   Default is FALSE.
$EnvironmentManagerMayRepair = FALSE;
// /  --Environment Manager May Rewrite Configs--
// /   Whether a permitted repair may rewrite the host configuration this application owns.
// /   Only ever applies when --Environment Manager May Repair-- is TRUE. --fix-permissions
// /   typed by an administrator rewrites them whatever this says.
// /   Those are the application's OWN drop ins, settings & policies. The php drop in in the php
// /   conf.d & the Apache configuration in conf-available, each named after the application,
// /   the sandbox kernel settings & the policies & AppArmor profiles this application ships. With this FALSE each
// /   one is checked, reported & left exactly as it is. This defaults to TRUE while repair
// /   itself defaults to FALSE, because every one of them is something this application writes.
// /   Set it FALSE if you have tuned one of those files by hand. A rewrite reverts it to
// /   the shipped version silently & you will find out at the next conversion.
// /   Default is TRUE.
$EnvironmentManagerMayRewriteConfigs = TRUE;
// /  --Enable Authentication--
// /   Usernames & passwords. A user who signs in keeps their files in a folder of their own,
// /   which no sweep removes, & every other visitor works exactly as before.
// /   No cookie is ever set. A signed in page holds a short lived key in its memory & trades
// /   it for a new one on a timer, so closing the tab ends the sign in.
// /   Accounts are kept in Accounts & each user's files in Users, both in the data location
// /   & outside the web root. -fp makes both, whether or not this is on.
// /   Confirming an address & recovering an account need the Email Core & a mail system on
// /   this host. Without them, signing in with valid credentials still works, & --config
// /   names what is missing.
// /   See Documentation/ABOUT_AUTHENTICATION.txt.
// /   Valid options are TRUE or FALSE.
// /   Default is FALSE.
$EnableAuthentication = FALSE;
// /  --Allow Anonymous Visitors--
// /   Whether a visitor who has not signed in may still use this server, in a session that
// /   is removed after --File Deletion Age Threshold-- as every session always has been.
// /   Set it FALSE to require every visitor to sign in first.
// /   Requires --Enable Authentication--.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$AllowAnonymousVisitors = TRUE;
// /  --Allow Registration--
// /   Whether a visitor may create an account of their own.
// /   Set it FALSE to have only an administrator create accounts, with --account --add.
// /   Requires --Enable Authentication--.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$AllowRegistration = TRUE;
// /  --Require Email Confirmation--
// /   Whether a new account must confirm its address with a code emailed to it before it
// /   may sign in. An account an administrator creates is confirmed already.
// /   Requires --Allow Registration--.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$RequireEmailConfirmation = TRUE;
// /  --User Storage Quota--
// /   The megabytes each signed in user may keep. A write that would take them past it is
// /   refused. Set it to 0 for no limit.
// /   The listener keeps the quota, as it keeps the storage reserve, so it is kept only where
// /   --Enable Resource Awareness-- is TRUE & the listener runs.
// /   Requires --Enable Authentication--.
// /   Valid options are integers 0 and larger.
// /   Default is 1024.
$UserStorageQuota = 1024;
// /  --Sign In Key Lifetime--
// /   The seconds a signed in page's key may be presented for. A page trades it for a new
// /   one long before then. A key that is not traded in time ends the sign in.
// /   Values below 10 are raised to 10.
// /   Requires --Enable Authentication--.
// /   Valid options are integers 10 and larger.
// /   Default is 30.
$SignInKeyLifetime = 30;
// /  --Sign In Exchange Interval--
// /   The seconds between a page's trades. It is held to half of --Sign In Key Lifetime-- at
// /   most, so every key is traded at least twice before its life is over.
// /   Requires --Enable Authentication--.
// /   Valid options are integers 2 and larger.
// /   Default is 6.
$SignInExchangeInterval = 6;
// /  --Sign In Maximum Lifetime--
// /   The seconds a sign in may last however busy its page is, after which the user signs
// /   in again. Set it to 0 to let a sign in last as long as its page is open.
// /   Requires --Enable Authentication--.
// /   Valid options are integers 0 and larger.
// /   Default is 43200, which is twelve hours.
$SignInMaximumLifetime = 43200;
// /  --Minimum Password Length--
// /   The fewest characters a new password may have. Values below 8 are raised to 8.
// /   Requires --Enable Authentication--.
// /   Valid options are integers 8 and larger.
// /   Default is 12.
$MinimumPasswordLength = 12;
// /  --Sign In Attempt Limit--
// /   The failed attempts an account, or an address a request comes from, may make inside
// /   --Sign In Attempt Window-- before every further attempt is refused until the oldest
// /   is that old. It limits registrations, codes & recovery requests from an address too.
// /   Requires --Enable Authentication--.
// /   Valid options are integers 1 and larger.
// /   Default is 5.
$SignInAttemptLimit = 5;
// /  --Sign In Attempt Window--
// /   The seconds --Sign In Attempt Limit-- counts over. Values below 60 are raised to 60.
// /   Requires --Enable Authentication--.
// /   Valid options are integers 60 and larger.
// /   Default is 900, which is fifteen minutes.
$SignInAttemptWindow = 900;
// /  --Unconfirmed Account Lifetime--
// /   The seconds a new account has to confirm its address, & the life of the code sent to
// /   it. An account still unconfirmed after it is removed. Values below 600 are raised to 600.
// /   Requires --Require Email Confirmation--.
// /   Valid options are integers 600 and larger.
// /   Default is 86400, which is one day.
$UnconfirmedAccountLifetime = 86400;
// /  --Recovery Code Lifetime--
// /   The seconds a recovery code may be used for. Values below 300 are raised to 300.
// /   Requires --Enable Authentication--.
// /   Valid options are integers 300 and larger.
// /   Default is 1800, which is thirty minutes.
$RecoveryCodeLifetime = 1800;
// /  --Email Sender--
// /   The address the emails this server sends are from. Leave it empty to let this host's
// /   mail system name the sender.
// /   Requires --Enable Authentication--.
// /   Default is empty.
$EmailSender = '';
// /  --Email Confirmation Subject--
// /   The subject of the email that confirms a new account's address. Leave it empty for
// /   the one this release ships. It may name {APPLICATION}, {USERNAME}, {CODE},
// /   {MINUTES} & {ADDRESS}, & each is replaced before the email is sent.
// /   Requires --Enable Authentication--.
// /   Default is empty.
$EmailConfirmationSubject = '';
// /  --Email Confirmation Body--
// /   The text of that email. Leave it empty for the one this release ships. It may name
// /   the same five, & should name {CODE}, because the code is what the reader types.
// /   Requires --Enable Authentication--.
// /   Default is empty.
$EmailConfirmationBody = '';
// /  --Email Recovery Subject--
// /   The subject of the email that lets the owner of an account choose a new password.
// /   Leave it empty for the one this release ships. It may name the same five.
// /   Requires --Enable Authentication--.
// /   Default is empty.
$EmailRecoverySubject = '';
// /  --Email Recovery Body--
// /   The text of that email. Leave it empty for the one this release ships. It may name
// /   the same five, & should name {CODE}.
// /   Requires --Enable Authentication--.
// /   Default is empty.
$EmailRecoveryBody = '';
// / ------------------------------

// / ------------------------------
// / ---Directory Information---
// / 
// /  --Installation Directory--
// /   The directory the web server serves the application from.
// /   The application itself is installed in Application inside the --Data Storage Directory--,
// /   outside the web root, & this directory holds only its loader, the static files a browser
// /   fetches & DATA, which -fp writes. See Documentation/ABOUT_MANAGED_LOCATIONS.txt.
// /   Do not include a trailing slash.
// /   Do not use a path with whitespace.
// /   Changing this value is not recommended.
// /   Default is the folder named after the application in the --Proprietary Directory--.
$InstLoc = '/var/www/html/HRProprietary/Application';
// /  --Proprietary Directory--
// /   Install the application's folder to the following directory.
// /   Do not include a trailing slash.
// /   Do not use a path with whitespace.
// /   Changing this value is not recommended.
// /   Default is '/var/www/html/HRProprietary'.
$ProprietaryLoc = '/var/www/html/HRProprietary';
// /  --Server Root Directory--
// /   This should be pointed at the root of your web server directory.
// /   Do not include a trailing slash.
// /   Do not use a path with whitespace.
// /   Changing this value is not recommended.
// /   Default is '/var/www/html'.
$ServerRootDir = '/var/www/html';
// /  --Data Storage Directory--
// /   This is the data location, a folder root owns with a folder for each kind of file below
// /   it. Sessions are kept in Sessions, the web server user's caches & home in Cache, its logs
// /   in Logs & its sockets in Sockets. Root's log is in Engine & the previous installation in
// /   Last-Installed-Version. The install secret sits at the top of it. Root creates & owns this
// /   folder; the web server user owns only Sessions, Cache, Logs & Sockets below it.
// /   Do not include a trailing slash.
// /   Do not use a path with whitespace.
// /   Default is the folder named after the application in /DATA.
$ConvertLoc = '/DATA/Application';
// /  --Log Storage Directory--
// /   This is where permanent Log files are stored.
// /   Do not include a trailing slash.
// /   Do not use a path with whitespace.
// /   Default is $ConvertLoc.'/Logs'.
$LogDir = $ConvertLoc.'/Logs';
// /  --Home Directory--
// /   This is the Home directory for the web server user, where its tools keep their own
// /   configuration & caches. It is the Cache folder inside the data location by default, which
// /   the web server user owns. A value equal to the data location itself, as older releases
// /   shipped, is the folder root now owns & the web server user cannot write, so the Cache
// /   folder is used instead & a warning says so.
// /   Do not include a trailing slash.
// /   Do not use a path with whitespace.
// /   Default is $ConvertLoc.'/Cache'.
$HomeLoc = $ConvertLoc.'/Cache';
// /  --Backup Location--
// /   Where the previous installation is preserved after a successful update.
// /   The update first moves the old installation aside in the folder that holds it, because
// /   rename() is only atomic within one filesystem & that is what makes a rollback instant.
// /   It is copied here & removed from that folder as soon as the update succeeds.
// /   An install that replaces an installation keeps the one it replaced here too.
// /   This location must NOT be inside the web root & must NOT be inside the installation
// /   directory. It holds exactly one previous version & is replaced on every update.
// /   The DATA directory is never part of the backup, because it holds live user sessions.
// /   Do not include a trailing slash.
// /   Do not use a path with whitespace.
// /   Default is $ConvertLoc.'/Last-Installed-Version'.
$BackupLoc = $ConvertLoc.'/Last-Installed-Version';
// /  --Append Log Hash To Log Files--
// /   This setting is used to append an 18 character identifier to the name of every log file.
// /   The identifier is derived from the install secret with a keyed hash, so it differs between
// /   installations & cannot be worked out by anybody who does not hold that secret.
// /   It protects log files from requests that guess their names.
// /   If set to TRUE, the application will add the identifier to the end of every log file name.
// /   If set to FALSE, a log file is named by the application, its role, its number & the date alone.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$AppendLogHashToLogFiles = TRUE;
// /  --Unique Daily Log Hash Rotation--
// /   This setting decides whether the identifier from --Append Log Hash To Log Files-- changes daily.
// /   If set to TRUE, a new identifier is derived every day, so one day's name says nothing of the next.
// /   If set to FALSE, the identifier stays the same from day to day until the install secret changes.
// /   A new log file is started every day either way, because the date is part of every log file name.
// /   This setting has no effect if --Append Log Hash To Log Files-- is set to FALSE.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$UniqueDailyLogHash = TRUE;
// /  --Additional Data Locations--
// /   Extra locations are used to load balance sessions across more than one data location.
// /   Each entry is an array of a path & a type, in that order.
// /   Leave this as an empty array to keep a single data location.
// /   This setting only has any effect if --Enable Resource Awareness-- is also set.
// /   If --Enable Resource Awareness-- is not set, the application will fall back to the --Data Storage Directory--.
// /   If a resource listener is not running, the application will fall back to the --Data Storage Directory--.
// /   The listener decides. Every front end sharing the install secret gets the same answer.
// /   Each location is a data root of its own, as the --Data Storage Directory-- is.
// /   -fp gives it to root & gives the Sessions folder it makes inside it to the web server user.
// /   Every location must be OUTSIDE the web root.
// /   A location that does not exist is skipped rather than created.
// /   Valid storage types.
// /     roundrobin   Joins the distribution. New sessions are spread across the pool by
// /                  session identifier, which needs no shared counter between servers.
// /     leastactive  Joins the distribution. If ANY location declares this type, the whole
// /                  pool is selected by fewest sessions held instead of by identifier.
// /     redundant    A standby. Takes a session only when every other location is unusable.
// /   Example.
// /     $AdditionalConvertLocs = array(
// /       array('/DATA2/Application', 'roundrobin'),
// /       array('/DATA3/Application', 'leastactive'),
// /       array('/DATA4/Application', 'redundant'));
// /   The second element of the array must be a string containing the storage type.
// /   The first element of the array must be a string containing a valid directory path.
// /   Default is an empty array.
$AdditionalConvertLocs = array();
// /  --Storage Cleanup Interval--
// /   How often the Resource Manager sweeps every configured data location, in seconds.
// /   The sweep uses --File Deletion Age Threshold-- to decide what has expired.
// /   Set to 0 to disable the scheduled sweep entirely.
// /   Standalone installations, or installs where no listener is available are unaffected by this setting.
// /   Standalone workers also sweep for stale files at runtime.
// /   If a standalone worker encounters stale files, the worker will also sweep them.
// /   This setting ONLY sets the Manager cleanup CHECK interval.
// /   Valid options are integers 0 and larger.
// /   Default is 300.
$StorageCleanupInterval = 300;
// / ------------------------------

// / ------------------------------
// / ---General Information---
// / 
// /  --Application Name String--
// /   The default name to display for this application.
// /   You can change this to make it fit with other services your organization provides.
// /   Default is the application's own name.
$ApplicationName = 'Application';
// /  --Application Title String--
// /   The default title to display in taskbars & window managers.
// /   You can change this to make it fit with other services your organization provides.
// /   Default is the title the application gives itself.
$ApplicationTitle = 'Application';
// /  --Enable Automatic Updates--
// /   Allow the application to replace its own code from the command line.
// /   It is not possible to perform an update via the web interface.
// /   If set to TRUE, an administrator may run -u to update.
// /   If set to FALSE, that command refuses & the application may only be updated by hand.
// /   Valid options are TRUE or FALSE.
// /   Default is FALSE.
$EnableAutoUpdates = FALSE;
// /  --Automatic Update Target Version--
// /   Which release an update installs when no version is supplied on the command line.
// /   If set to 'latest', the newest tagged release is installed.
// /   If set to 'edge', the current state of the master branch is installed.
// /   If set to a version such as 'v3.6.8', exactly that tag is installed.
// /   An update to a tag that does not exist fails rather than installing something else.
// /   A version supplied on the command line always overrides this setting.
// /   Default is 'latest'.
$AutoUpdateTargetVersion = 'latest';
// /  --Update Source Repository--
// /   The GitHub repository an update is fetched from, as owner/name.
// /   Change this only if you maintain your own fork & want updates to come from it.
// /   Default is the repository the application is published from.
$UpdateSourceRepository = '';
// /  --Maximum Update Package Size--
// /   The largest update package that will be accepted, in bytes.
// /   A source that serves something larger is refused rather than filling the disk.
// /   Default is 524288000, which is 500 megabytes.
$MaxUpdatePackageSize = 524288000;
// /  --Update Connection Timeout--
// /   The maximum number of seconds an update download may take in total.
// /   This bounds the whole transfer, not just the time taken to connect.
// /   Default is 1000.
$UpdateConnectionTimeout = 1000;
// /  --Enable Workflows--
// /   Provide users with workflows, each a list of steps that runs one operation after another on the files a user selects.
// /   A workflow converts, archives, reads text from, identifies & scans files, as the buttons do, & passes what each step made to the next.
// /   It runs in the background, so a user may watch it, stop it or leave it, & each operation takes a resource budget as a button does.
// /   The workflows offered are the ones this release ships & the ones an administrator installs with --workflow --install.
// /   A workflow that uses an operation switched off here is not offered.
// /   If set to TRUE the user will be provided with a Workflows panel, & a page may ask for the library, run one & watch it.
// /   If set to FALSE no workflow is offered or run.
// /   See Documentation/ABOUT_WORKFLOWS.txt.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$EnableWorkflows = TRUE;
// /  --Enable Workflow Builder--
// /   Provide users with a builder in the Workflows panel, where a workflow is put together from the operations this server offers & run.
// /   A workflow a page builds is checked exactly as a library's is, & is held to every limit a library's workflow is held to.
// /   It may use Select, which reads the files in a user's session again at that step, so a step after it can take what earlier steps made.
// /   If set to TRUE the Workflows panel offers the builder, & a page may run a workflow it built.
// /   If set to FALSE only the workflows in the libraries are offered.
// /   Requires --Enable Workflows--.
// /   See Documentation/ABOUT_WORKFLOWS.txt.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$EnableWorkflowBuilder = TRUE;
// /  --Enable Workflow Import & Export--
// /   Let users save a workflow as a file, & open a workflow file in the builder.
// /   A file holds the workflow alone, as generic JSON naming no file of a user's & no path, so it can be shared, & --workflow --install installs it as it is.
// /   Opening a file needs --Enable Workflow Builder-- too, because a workflow is opened in the builder. Saving one from the libraries does not.
// /   If set to TRUE the Workflows panel offers Export, & Import when the builder is on.
// /   If set to FALSE neither is offered.
// /   Requires --Enable Workflows--.
// /   See Documentation/ABOUT_WORKFLOWS.txt.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$EnableWorkflowImportExport = TRUE;
// /  --File Deletion Age Threshold--
// /   Age in minutes of files to be deleted.
// /   Set to 0 to keep files forever.
// /   Valid options are integers 0 or larger.
// /   Default is 60.
$DeleteThreshold = 60;
// /  --Enhanced Logging Verbosity--
// /   Enable verbose logging.
// /   If set to TRUE all core events will be logged.
// /   If set to FALSE only errors & certain core events will be logged.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$Verbose = TRUE;
// /  --Maximum Log File Size--
// /   Set the number of bytes to store in each logfile before splitting to a new one.
// /  Default is 1048576.
$MaxLogSize = 1048576;
// /  --Supported Format Detection Type--
// /   Decides what decides which conversions this installation offers.
// /   Three things have an opinion & this setting says how they are combined.
// /   The arrays below are yours & say what is permitted on this machine.
// /   Every conversion pipeline declares the formats it is willing to claim at all.
// /   Dependency Core asks each installed tool what it can actually read & write.
// /   Nothing here ever widens the accepted format surface past what a pipeline declares.
// /   ImageMagick reports that it reads MSL, which is a scripting language it executes,
// /   & http, https & file, which fetch a URL without passing any guard this application
// /   owns. A tool's own opinion of itself is a filter & is never a source of formats.
// /
// /   Set this to hardcoded-only to let the arrays below decide everything.
// /   Detection never runs & a pipeline declaration is never enforced.
// /
// /   Set this to detected-advisory to run detection & change nothing.
// /   Every disagreement is logged & every conversion carries on exactly as before.
// /   This is the setting to run first & to leave running for a while.
// /   A tool names its formats after its own internals rather than after the extension a
// /   user types. FFMPEG has no mkv & no wmv, it has matroska & asf. Assimp has no dae, it
// /   has collada. Seven of Assimp's twenty two export names are not the extension.
// /   Those are corrected by a table in depends.php, & a gap in that table looks exactly
// /   like a tool that cannot do something it does perfectly well.
// /   Advisory finds the gaps on your machine & names them in the log, without any of them
// /   costing you a conversion while you are finding out.
// /
// /   Set this to detected-restrictive once the log is quiet.
// /   A format no installed tool can produce is dropped, so the interface stops offering
// /   conversions that were always going to fail. This can never widen anything.
// /
// /   Set this to detected-additive to also offer a format a pipeline declares & the
// /   arrays below omit, where the tool confirms it. Still bounded by the declaration.
// /   Use it only where you trust every installed pipeline.
// /
// /   A pipeline can always refuse a pair its own exclusion list names, in every mode.
// /   Refusing is safe anywhere, because it only ever removes a broken pairing.
// /   Valid options are 'hardcoded-only', 'detected-advisory', 'detected-restrictive'
// /   or 'detected-additive'.
// /   Default is 'detected-advisory'.
$SupportedFormatDetectionType = 'detected-advisory';
// /  --Warn On Capability Mismatch--
// /   Logs a warning when a pipeline declaration disagrees with the arrays below.
// /   The warning names the format, the conversion family & which side it came from.
// /   This has no effect while --Supported Format Detection Type-- is hardcoded-only.
// /   Detection never runs in that mode, so there is nothing to report.
// /   Under detected-advisory this warning is the entire point of the setting.
// /   Leaving this enabled is recommended wherever detection is active.
// /   A format quietly appearing or disappearing is worth knowing about.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$WarnOnCapabilityMismatch = TRUE;
// /  --File Operation Retry Count--
// /   Set this to the number of attempts to make during file operations.
// /   The application will attempt significant file operations this many times, with a pause in between.
// /   If a significant file operation fails, the application will retry the operation this many times.
// /   A pipeline runs its converter up to this many times, until the converter produces something.
// /   Valid options are integers smaller than 10.
// /   Default is 5.
$RetryCount = 5;
// / ------------------------------

// / ------------------------------
// / ---Resource Management Information---
// / 
// /  --Enable Per Conversion Limits--
// /   Runs every conversion inside a transient systemd scope carrying a processor & memory
// /   ceiling, so one conversion cannot starve the host.
// / This requires permission the web server user does not have by default.
// /   Creating a transient scope on the system bus is a privileged operation. The application
// /   proves it can create one before it relies on it, & falls back to running unlimited
// /   with a warning when it cannot. To grant the permission, install a polkit rule
// /   allowing the web server user to manage transient units, then restart the web server.
// /   A conversion that reaches its memory ceiling is killed by the kernel & is reported as
// /   a failed conversion rather than as a memory error. Keep the ceilings generous.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$EnablePerConversionLimits = TRUE;
// /  --Maximum Per Conversion Resources--
// /   The ceiling one conversion of each type may claim, as 'processor,memory'.
// /   The processor figure is a percentage of ONE core. 200 is two whole cores.
// /   If you have an 8 core processor, 800 is all 8 cores.
// /   The memory figure is a hard ceiling in megabytes.
// /   These are MAXIMA. With a listener running they are scaled down against current load
// /   & against how many conversions are already in flight, & never below
// /   --Minimum Per Conversion Resources--. With no listener they are used unchanged.
// /   Scan's memory is the one figure that is never scaled. Only its processor share is.
// /   A type that is not listed falls back to --Default Per Conversion Resources--.
// /   An entry that cannot be read is reported & falls back to the default.
// /   Valid keys are Document, Spreadsheet, Presentation, Image, Video, Audio, Archive,
// /   Model, Scad, Drawing, SVG, Subtitle, Stream, OCR, Ebook & Scan.
// /   Scan covers BOTH virus scanners, ClamAV & ScanCore.
// /   Be generous with Scan & treat its memory figure as a floor rather than a target.
// /   A ClamAV signature database is well over a gigabyte once it is loaded, so a ceiling
// /   that is merely tight does not slow a scan down, it has the kernel kill it, & the
// /   scan is then reported as a failed scan rather than as a memory problem.
// /   If scans begin failing on a server where they used to work, raise this first.
$MaximumPerConversionResources = array(
  'Document'     => '50,512',
  'Spreadsheet'  => '50,512',
  'Presentation' => '50,768',
  'Image'        => '75,1024',
  'Video'        => '90,2048',
  'Audio'        => '50,512',
  'Archive'      => '50,512',
  'Model'        => '75,2048',
  'Scad'         => '75,1024',
  'Drawing'      => '50,512',
  'SVG'          => '50,512',
  'Subtitle'     => '25,256',
  'Stream'       => '90,2048',
  'OCR'          => '75,1024',
  'Ebook'        => '50,768',
  'Scan'         => '50,2048');
// /  --Default Per Conversion Resources--
// /   The ceiling used for any conversion type not named above.
// /   Same 'processor,memory' format.
// /   Default is '50,512'.
$DefaultPerConversionResources = '50,512';
// /  --Minimum Per Conversion Resources--
// /   The floor a scaled ceiling is never reduced below.
// /   A loaded server must still hand out a ceiling a conversion can actually run inside,
// /   or it refuses work by starving it instead of by refusing it, which is worse.
// /   Same 'processor,memory' format.
// /   Default is '10,128'.
$MinimumPerConversionResources = '10,128';
// /  --Enable Resource Awareness--
// /   Allows the application to check a resource budget before starting a conversion.
// /   Requires the coreManager.php component & a running listener.
// /   When disabled, the application behaves exactly as it did before this component existed.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$EnableResourceAwareness = TRUE;
// /  --Require Resource Awareness--
// /   Refuses to run at all when resource awareness is unavailable.
// /   Refuses a conversion the listener does not answer for, rather than running it unbudgeted.
// /   Set this on a server that must never accept work it cannot account for.
// /   Leave this FALSE unless a missing component should take the whole site down.
// /   Valid options are TRUE or FALSE.
// /   Default is FALSE.
$RequireResourceAwareness = FALSE;
// /  --Core Manager Subprocess Poll Interval--
// /   How long the Core Manager waits for work before checking on its subordinates.
// /   A shorter interval detects a dead manager faster & wakes the process more often.
// /   Valid options are integers 1 and larger.
// /   Default is 5.
$CoreManagerSubprocessPollInterval = 5;
// /  --Resource Poll Interval--
// /   How often the Resource Manager re-reads system load & memory to adjust the budget.
// /   Valid options are integers 1 and larger.
// /   Default is 10.
$ResourcePollInterval = 10;
// /  --Worker Reap Interval--
// /   How often the Worker Manager scans for workers that have outlived their runtime.
// /   Valid options are integers 1 and larger.
// /   Default is 15.
$WorkerReapInterval = 15;
// /  --Worker Stale Grace Period--
// /   Seconds added to every expected runtime before a worker is considered stale.
// /   A conversion that is merely slow should not be reaped, so keep this generous.
// /   Valid options are integers 0 and larger.
// /   Default is 60.
$WorkerStaleGracePeriod = 60;
// /  --Total Resource Budget--
// /   The total budget available for concurrent conversions, in arbitrary cost units.
// /   Set to 0 to derive the budget from the processor count at one hundred units per core.
// /   Valid options are integers 0 and larger.
// /   Default is 0.
$TotalResourceBudget = 0;
// /  --Reserve Resource Percentage--
// /   The share of the total budget never handed to a conversion.
// /   This is what keeps the server answering requests while it is fully loaded.
// /   Valid options are integers 0 through 90.
// /   Default is 20.
$ReserveResourcePercentage = 20;
// /  --Maximum Concurrent Workers--
// /   A hard ceiling on tracked conversions, applied before the budget is consulted.
// /   Set to 0 to let the budget decide alone.
// /   Valid options are integers 0 and larger.
// /   Default is 0.
$MaxConcurrentWorkers = 0;
// /  --Maximum Expected Runtime--
// /   The longest runtime a worker may declare or extend to, in seconds.
// /   A request above this is refused rather than truncated.
// /   Valid options are integers greater than 0.
// /   Default is 900.
$MaxExpectedRuntime = 900;
// /  --Maximum Runtime Extensions--
// /   How many times one worker may ask for more time before it is refused.
// /   Valid options are integers 0 and larger.
// /   Default is 3.
$MaxRuntimeExtensions = 3;
// /  --Default Conversion Cost--
// /   The budget cost charged for a conversion that does not declare its own.
// /   Valid options are integers greater than 0.
// /   Default is 10.
$DefaultConversionCost = 10;
// /  --Default Expected Runtime--
// /   The runtime declared for a conversion that does not declare its own, in seconds.
// /   Valid options are integers greater than 0.
// /   Default is 120.
$DefaultExpectedRuntime = 120;
// /  --Low Storage Warning--
// /   The free share of a disk at which the listener warns that a data location is filling, in percent.
// /   The listener measures every disk holding a data location each --Resource Poll Interval--, & logs a
// /   warning once when a disk falls to this share free or less, & a line once when it recovers.
// /   Requires a running listener. See --Enable Resource Awareness--.
// /   Set to 0 to warn about nothing but the reserve below.
// /   Valid options are integers 0 through 99.
// /   Default is 10.
$LowStorageWarning = 10;
// /  --Storage Reserve--
// /   The free space the listener keeps on every disk holding a data location, in megabytes.
// /   Once a disk has this much free or less, nothing new is written to it. Uploads, conversions & every
// /   other operation that writes are refused, & the log says why, until more is free.
// /   An upload that would take a disk within the reserve is refused before it is written.
// /   Downloads & deletes still work, because deleting is how space is freed.
// /   Keep it larger than the largest file a conversion here writes, because a conversion that started
// /   before the reserve was reached may still finish.
// /   It keeps a disk from filling to nothing, which would stop the logs & the operating system as well.
// /   Requires a running listener. See --Enable Resource Awareness--.
// /   Set to 0 to refuse nothing.
// /   Valid options are integers 0 and larger.
// /   Default is 1024.
$StorageReserve = 1024;
// /  --Maximum Workflow Steps--
// /   The most steps a workflow may have. A workflow with more is not offered, & --workflow --list says why.
// /   Requires --Enable Workflows--.
// /   Valid options are integers 1 and larger.
// /   Default is 16.
$MaxWorkflowSteps = 16;
// /  --Maximum Workflow Files--
// /   The most files a workflow is started on, & the most any one of its steps runs on.
// /   Requires --Enable Workflows--.
// /   Valid options are integers 1 and larger.
// /   Default is 100.
$MaxWorkflowFiles = 100;
// /  --Maximum Workflow Runtime--
// /   The longest a workflow may run, in minutes. It is checked before each operation, & a workflow past it stops there.
// /   An operation already running is left to finish, because a converter stopped part way leaves a broken file.
// /   Keep it shorter than --File Deletion Age Threshold--, or a session may be swept while its workflow still runs.
// /   Requires --Enable Workflows--.
// /   Valid options are integers 1 and larger.
// /   Default is 30.
$MaxWorkflowRuntime = 30;
// /  --Maximum Concurrent Workflows--
// /   The most workflows that may run at once on this server, across every session.
// /   A workflow waiting for a resource budget is still running, so it counts.
// /   One more is refused until one ends, & the page says the server is busy.
// /   Requires --Enable Workflows--.
// /   Valid options are integers 1 and larger.
// /   Default is 4.
$MaxConcurrentWorkflows = 4;
// / ------------------------------
