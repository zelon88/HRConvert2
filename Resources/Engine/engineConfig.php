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
// / This file configures the Engine for the application that bundles it.
// /
// / This file is NOT the administrator's configuration & is not edited by a user.
// / An application ships with the Engine it was tested against, & this file is how the
// / developer of that application says which parts of the Engine it uses. The settings a
// / user touches live in the application's own config.php.
// /
// / Two configuration files exist & they answer different questions.
// /   This file           Which Engine capabilities this application uses. Set by a
// /                       developer, shipped with the release, replaced by an update.
// /   The app config.php  How this installation behaves. Set by an administrator, kept
// /                       across an update, & the only one anybody is asked to read.
// /
// / This file is read AFTER the application configuration, & the order is not a choice.
// / config.php is how an installation finds itself. The directory it names is verified
// / before anything is derived from it, & the path to this file is derived from it. An
// / installation with no config.php writes one from its template before anything else is
// / trusted, & reads no Engine configuration while it does.
// / So a setting here with the same name as one in config.php would win over the
// / administrator, silently. No name is set in both. Every name this file & the contract
// / set begins with Engine, & an application's own checks are where that is enforced.
// / A value an administrator may change belongs in config.php & nowhere else.
// / A value the application must NOT be able to override does not belong in a variable.
// /
// / It assigns variables & does nothing else. No functions, no logic, no output.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by an application.
// / This file is the Engine's, so its guard names the Engine.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! Engine-35000, The Engine configuration cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The version of this configuration. Matched as a MINIMUM by the Engine, the same way
// / an application matches its own configuration. A newer file carrying every required
// / setting is fine, because a setting this Engine does not know is simply not read.
$EngineConfigVersion = 'v4.2.4';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / --Engine Identity--
// /
// /  --Engine Application Name--
// /   The name this Engine reports the application by.
// /   It appears in a log line the Engine writes on its own behalf, so that an operator
// /   reading a log can tell which application produced it on a host running several.
// /   Valid options are any short text string.
// /   Default is 'Application'. An application sets its own in Contract/app-engine-config.php.
$EngineApplicationName = 'Application';

// /  --Engine Entry Point--
// /   The file an application is started through. The Engine runs it to start managers & the
// /   listener, & names it in the instructions it prints. An application sets its own in
// /   Contract/app-engine-config.php. Default 'app.php', the Engine's own example application.
$EngineEntryPoint = 'app.php';

// /  --Engine Application Slug--
// /   A short lowercase form of the name, safe to use in a file name or a socket path.
// /   Nothing derives this from the name above, because a name may contain anything & a
// /   path may not.
// /   Valid options are lowercase letters, digits & hyphens.
// /   Default is 'hrconvert2'.
$EngineApplicationSlug = 'application';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / --Engine Capabilities--
// /
// / Every capability below is a gate rather than a feature request.
// / A capability that is disabled is not loaded, is not verified & is not reported. An
// / application that does not sandbox anything should not be told its sandbox is broken.
// /
// / A capability that is enabled still degrades the way everything else in this project
// / degrades. Enabling it says this application uses it, not that it must be present.
// /
// /  --Engine Enable Environment--
// /   Detecting the host. Root or not, a terminal or not, a container or not, which
// /   separator, which line ending, which account the process runs as.
// /   Every application needs this & there is no sensible reason to disable it. It is a
// /   toggle so that the list below is complete rather than because it is optional.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$EngineEnableEnvironment = TRUE;

// /  --Engine Enable Dependency Location--
// /   Finding an executable on the host & reporting where it was found.
// /   An application that shells out to anything needs this. An application that does not
// /   should turn it off, because a locator that is never called is a locator that can
// /   still be called by mistake.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$EngineEnableDependencyLocation = TRUE;

// /  --Engine Enable Startup Keys--
// /   Proving that a process was started by this application & not by somebody else.
// /   Required by anything that spawns a long lived worker. An application that never
// /   spawns one does not need it.
// /   A key travels in the environment & never on a command line, & a key that crossed a
// /   process boundary is spent when it validates. See Documentation/ABOUT_MANAGERS.txt.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$EngineEnableStartupKeys = TRUE;

// /  --Engine Enable Operating Environment Validation--
// /   Reporting whether the host is configured the way this application needs.
// /   Kernel settings, sandbox availability, the service manager & the policy files.
// /   This is what -fp & -v report. An application with no installer does not need it.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$EngineEnableEnvironmentValidation = TRUE;

// /  --Engine Enable Resource Limits--
// /   Reading a per operation resource limit & turning it into something a process
// /   supervisor understands.
// /   Required by an application that bounds how much of a machine one unit of work may
// /   consume. An application that does not bound its work does not need it.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$EngineEnableResourceLimits = TRUE;

// /  --Engine Enable Authentication--
// /   Usernames & passwords, the accounts behind them, & a folder each signed in user keeps.
// /   An application that serves anonymous visitors only does not need it.
// /   Enabling it says this application offers it. An installation still has it off until
// /   its administrator turns it on in the application's config.php.
// /   It needs the Email Core to confirm an address or to recover an account. Without the
// /   Email Core, signing in with valid credentials still works, & every email that would
// /   have been sent & every incorrect password is a warning in the log.
// /   See Documentation/ABOUT_AUTHENTICATION.txt.
// /   Valid options are TRUE or FALSE.
// /   Default is FALSE.
$EngineEnableAuthentication = FALSE;
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / --Engine Email--
// /
// / The Email Core sends through the host's own mail system, & never connects to a mail
// / server of its own accord. A host with no mail system sends nothing, & says so.
// / The text below is the Engine's default. An application replaces it in its contract, &
// / an administrator replaces the application's in config.php.
// / Each may name {APPLICATION}, {USERNAME}, {CODE}, {MINUTES} & {ADDRESS}, & each is
// / replaced before the email is sent. {CODE} is what the reader types into the page, &
// / {MINUTES} is how long it may be used for.
// / An email carries a code rather than a link, because a mail system that checks links
// / opens each one before the reader does, & would spend a link that works once.
// /
// /  --Engine Email Confirmation Subject--
// /   The subject of the email that confirms a new account's address.
$EngineEmailConfirmationSubject = '{APPLICATION}: confirm your account';

// /  --Engine Email Confirmation Body--
// /   The text of the email that confirms a new account's address.
$EngineEmailConfirmationBody = "Hello {USERNAME},\n\nYour confirmation code for {APPLICATION} is {CODE}\n\nEnter it on the page where you created your account. It can be used for {MINUTES} minutes.\n\nIf you did not create an account at {ADDRESS}, you can ignore this email.\n";

// /  --Engine Email Recovery Subject--
// /   The subject of the email that lets the owner of an account choose a new password.
$EngineEmailRecoverySubject = '{APPLICATION}: recover your account';

// /  --Engine Email Recovery Body--
// /   The text of the email that lets the owner of an account choose a new password.
$EngineEmailRecoveryBody = "Hello {USERNAME},\n\nYour recovery code for {APPLICATION} is {CODE}\n\nEnter it on the page where you asked to recover your account, with the new password you want. It can be used for {MINUTES} minutes.\n\nIf you did not ask to recover an account at {ADDRESS}, you can ignore this email. Your password has not changed.\n";
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / --Engine Behaviour--
// /
// /  --Engine Reports Its Own Load--
// /   Writes one log line when the Engine has been verified & loaded.
// /   An operator diagnosing a host running several applications wants to see which
// /   Engine each one loaded & when. An operator running one application does not.
// /   This has no effect unless the application has verbose logging enabled, because the
// /   line is ordinary activity rather than a warning.
// /   Valid options are TRUE or FALSE.
// /   Default is TRUE.
$EngineReportsItsOwnLoad = TRUE;

// /  --Engine Per Manager Log Files--
// /   Gives every manager process a logfile of its own, named for the manager.
// /   The listener writes to a Core-Manager file, the resource manager to a
// /   Resource-Manager file, & so on for every role that runs.
// /   Leave this FALSE unless a manager is writing enough to matter.
// /   Four managers sharing one file is readable & four files is four things to open.
// /   Set it TRUE on a busy installation. Every process appending to one file is the only
// /   way two entries can ever interleave, & separate files remove the possibility rather
// /   than guarding against it.
// /   An append shorter than the pipe buffer is atomic anyway & most entries are.
// /   A stream inspection dump or a compiler log is not, & those are the entries worth
// /   not corrupting.
// /   Valid options are TRUE or FALSE.
// /   Default is FALSE.
$EnginePerManagerLogFiles = FALSE;

// /  --Engine Per Worker Log Files--
// /   Gives every tracked worker a logfile of its own, named for the worker.
// /   The name is taken from the worker identifier the application supplies, so one unit
// /   of work can be followed from beginning to end in a file that holds nothing else.
// /   Leave this FALSE for HRConvert2. A conversion is short & shares a file happily, &
// /   one file per conversion on a busy server is a directory nobody can read.
// /   It is here for an application that dispatches long running work.
// /   HRProtect scanning a filesystem for an hour is the case this exists for.
// /   An application that sets it TRUE must also set $LogWorkerIdentifier before the
// /   logging environment is verified, or the setting has nothing to name a file with.
// /   Valid options are TRUE or FALSE.
// /   Default is FALSE.
$EnginePerWorkerLogFiles = FALSE;

// / -----------------------------------------------------------------------------------
// / --Engine Network Policy--
// /
// / Every setting here has a fallback to the name HRConvert2 already uses, so leaving the
// / whole section commented changes nothing about an existing installation.
// / A new application sets these & has no stream shaped settings of its own.
// / networkPolicy() in the Engine resolves them & is the only thing that reads them.
// /
// /  --Engine Allow Plain HTTP--
// /   Permits a fetch over http rather than https.
// /   Falls back to --Allow Stream Over HTTP-- & then to FALSE.
// /   Valid options are TRUE or FALSE.
// /   Default is the application's own setting.
// $EngineAllowPlainHTTP = FALSE;

// /  --Engine Max Inspection Bytes--
// /   How much of a remote file is read before it is judged.
// /   A file larger than this is truncated for inspection rather than refused, because the
// /   beginning of a file is where an address hides.
// /   Falls back to --Max Stream Inspection File Size-- & then to one megabyte.
// $EngineMaxInspectionBytes = 1048576;

// /  --Engine Connection Timeout--
// /   Seconds to wait for a remote host before giving up.
// /   Falls back to --Stream Connection Timeout-- & then to ten.
// $EngineConnectionTimeout = 10;

// /  --Engine Watch Timeout--
// /   Seconds to supervise a detached worker before it is considered overdue.
// /   Falls back to --Stream Watch Timeout-- & then to three hundred.
// $EngineWatchTimeout = 300;

// /  --Engine Fetch Temp--
// /   Where a remote file is written while it is being inspected.
// /   Falls back to --Stream Temp-- & then to the system temporary directory.
// $EngineFetchTemp = '';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// /  --Engine Environment Provider--
// /   The name of an application function that reports on THIS application's environment.
// /   The Engine checks what the Engine provides, which is Bubblewrap, the bootstrap script & what the web server serves.
// /   Every other check an installation wants belongs to the application that wants it.
// /   The named function accepts nothing & returns a readiness boolean & a list of findings,
// /   each finding an array of Check, Status & Detail. The Engine appends them to its own.
// /   Leave it empty & only the Engine's own checks run, which is correct rather than
// /   degraded. An application with no AppArmor policy should not be asked about one.
// /   A name that is declared & not defined is a warning, because a check nobody notices is
// /   missing is worse than one that was never claimed.
$EngineEnvironmentProvider = '';

// /  --Engine Operator Prompt--
// /   The name of a function that asks a human a question & returns their answer, for an
// /   application that reaches its operator some other way than a terminal.
// /   The Engine has work that must not proceed unasked. An application that names nothing
// /   is asked on the terminal by the Engine itself, & a web request, which has no operator,
// /   gets a REFUSAL for every such question rather than blocking. A refusal is the safe
// /   answer. Nothing destructive happens because nobody could be asked.
$EngineOperatorPrompt = '';

// /  --Engine Data Policy Provider-- is retired.
// /   The Engine reports the exposure of every location the contract gives the served role itself.
// /   A contract that still names one is not read.

// /  --Engine Config Model Provider--
// /   The name of a function returning this application's configuration model.
// /   The model is the configuration & it belongs to the application. It names every
// /   section, setting, type, default & the prose explaining each one. An engine that
// /   carried it would be an engine that knows this application has a
// /   --Supported File Format Information-- section, which is exactly the knowledge that
// /   keeping them separate exists to prevent.
// /   An application that names nothing gets a config utility that can still back up,
// /   view & verify a file, & cannot repair, reset or generate one. That degradation is
// /   correct. Repairing a setting means knowing what it should be.
// /   There is no fallback model & there must never be. A wrong model would rewrite an
// /   installation's settings to another application's defaults, which is worse than
// /   refusing to do anything at all.
$EngineConfigModelProvider = '';

// /  --Engine Operation Provider--
// /   The name of a function returning the operations a workflow may run, & the kinds of file a step may name.
// /   The Workflow Core runs what it is handed & knows nothing else about any of it.
// /   The named function accepts nothing & returns an array holding Operations & FileTypes.
// /   See Documentation/ABOUT_WORKFLOWS.txt for what each operation declares.
// /   An application that names nothing has no workflows, which is correct rather than degraded.
$EngineOperationProvider = '';

// /  --Engine Shipped Workflows--
// /   The folder inside the installation that holds the workflows a release ships, relative to it.
// /   An application that names none ships no workflows, & an administrator may still install some.
$EngineShippedWorkflows = '';

// /  --Engine Setting Defaults--
// /   The defaults an application gives the Engine's own settings, where the Engine's would
// /   not do, keyed by setting & written as config.php writes a value, so a string carries
// /   its quotes. The settings, their documentation & their defaults are the Engine's, in
// /   Resources/Engine/config-template.php, the Engine's half of the configuration template.
// /   The application's half is always Resources/Contract/config-template.php, & the kernel
// /   reads both by those names before this file is loaded, so neither is named here.
// /   A default given here replaces only the value, in a config.php written from the
// /   templates & in what a reset writes. A setting written across several lines, as an
// /   array is, is replaced whole. Only the Engine's settings are given one, & check thirteen
// /   of hrconvert2-check.py reports a default named for any other.
// /   An application names itself & its folders here.
// /   Leave it empty & every setting of the Engine's has the Engine's default.
$EngineSettingDefaults = array();

// /  --Application Argument Handler--
// /   The name of a function that handles this application's own command line
// /   arguments. Every argument beginning --app is handed to it untouched.
// /   The Engine owns every other argument. Setup, configuration, the managers, the
// /   listener, cleanup & self update are true of any application of this shape.
// /   It is a prefix rather than a registry of names. A registry means the Engine must ask
// /   the application what it accepts before it can report an unknown argument, & an
// /   application that answers badly makes the Engine report nonsense. A prefix needs
// /   no negotiation. Everything --app is the application's & everything else is not.
// /   It is checked BEFORE any engine argument, so an application argument can never
// /   collide with an engine one however many are added later.
// /   The handler receives the command, its =target & a second positional argument.
// /   The command arrives lowercased, like every command. The two values arrive exactly as
// /   typed, because an application's values may be paths. Its return value is not read
// /   yet, so a handled argument exits 0.
// /   An application that names none is told plainly that it accepts no --app command.
$EngineApplicationArgumentHandler = '';

// /  --Engine Enforce GPL Compliant Deps--
// /   Whether a dependency must declare a license this project can legally depend on.
// /   A GPLv3 application that ships alongside a tool under an incompatible license is a
// /   licensing problem rather than a technical one, & it is the kind that surfaces years
// /   later when somebody redistributes.
// /   With this on, an entry declaring no license or an incompatible one is REFUSED &
// /   reported. With it off, the license field is documentation.
// /   Default FALSE, because turning it on will refuse entries that work today & that is
// /   an administrator's decision rather than an upgrade's.
$EngineEnforceGPLCompliantDeps = FALSE;

// /  --Engine Allow Incomplete Deps--
// /   Whether an entry missing its optional fields is accepted.
// /   License, Source & Purpose are provenance rather than mechanism. A developer who
// /   builds their own tool & names its path has told the application everything it needs
// /   to USE the thing, & refusing it teaches them the manifest is decoration.
// /   Default TRUE. An installation that wants every entry documented sets it FALSE.
$EngineAllowIncompleteDeps = TRUE;

// /  --Engine Allow Incomplete Deps Update--
// /   Whether an INCOMPLETE entry may be updated or reinstalled.
// /   Using what an operator supplied & changing it are different risks, which is why this
// /   is separate from the setting above. Using a tool at a path somebody chose is their
// /   decision. Running an update against an entry with no declared source means fetching
// /   from somewhere nobody wrote down, over a binary somebody deliberately placed.
// /   Default FALSE. An incomplete entry is REPORTED & SKIPPED by an update rather than
// /   acted on, & the operator is told which entries were skipped & why.
$EngineAllowIncompleteDepsUpdate = FALSE;

// /  --Engine Protect Hosted Locations--
// /   Whether every location this application declares as HOSTED is given a document root
// /   protection page.
// /   A directory a web server can reach that has no index lists every file in it on most default configurations.
// /   The page is generated rather than copied, so it exists on every installation.
// /   It is a second layer. The first is the server refusing to serve the directory at all.
// /   Default TRUE, because an index file costs nothing & the failure it prevents is a listing of user data.
$EngineProtectHostedLocations = TRUE;

// /  --Engine Active Surface--
// /   Which surface this application is currently being reached through. cli, web or gui.
// /   A pipeline declares which surfaces it may be reached from & this is what that is
// /   compared against. A pipeline that permits only web is refused from a terminal, & one
// /   that permits only cli is refused from a browser.
// /   An empty value enforces nothing, which is what a process that has not read its command
// /   line yet gets. It is set by the kernel's runCommandLine() once it knows how the application
// /   was reached, because the Engine cannot know that. A CLI invocation & an HTTP request reach
// /   the same file.
$EngineActiveSurface = '';

// /  --Engine Repair Provider--
// /   Optional. The name of a function that repairs whatever else this application keeps,
// /   beyond what the Engine's own repair covers. The Engine's repairInstallation runs it as
// /   root, before it verifies the environment, for -fp, --setup & the Environment Manager
// /   alike, so there is one place an application declares how it fixes itself.
// /   The named function accepts nothing & returns a success boolean & a count of what it corrected.
// /   Leave it empty & the Engine's repair is the whole of the repair.
$EngineRepairProvider = '';

// /  --Engine Report Provider--
// /   The name of a function that gives the report -v prints what only this application
// /   knows. The Engine prints the report itself, & places what the function returns among
// /   its own sections. The function accepts nothing & returns rows & titled sections keyed by
// /   where each goes: Dependencies, DependencyNotes, SectionsAfterDependencies, SwitchedOff,
// /   SectionsAfterSwitchedOff & SectionsAfterResources. A row is a Label, a Text & whether it
// /   Failed. A switched off feature is a pair, the feature & the setting that switches it on.
// /   Leave it empty & the report carries the Engine's sections alone.
$EngineReportProvider = '';

// /  --Engine Help Provider--
// /   The name of a function that gives the help -h prints what only this application knows.
// /   The Engine prints the help itself, with every command of its own. The function accepts nothing &
// /   returns Description, the line printed under the application's name, Arguments, lines for the
// /   application's own arguments printed after the Engine's, Sections, titled lines printed after the
// /   Engine's, & Address, the line printed last. Each is optional.
// /   Leave it empty & the help lists the Engine's commands alone.
$EngineHelpProvider = '';

// /  --Engine Sandbox Provider--
// /   The name of a function that returns the sandbox profiles this application's tools run under,
// /   keyed by profile name. The Engine asks for them as it starts & assembles a sandbox from each,
// /   & knows nothing about any tool a profile names, because a profile is data.
// /   A name that is declared & not defined is a warning.
// /   Leave it empty & the application declares no profile of its own.
$EngineSandboxProvider = '';

// /  --Engine Session Folders--
// /   The folders every session holds for the application's own tools, beside the Engine's StreamTemp.
// /   The Engine makes each with the session, never sweeps one at the root of a data location, & gives
// /   each a global of the same name holding its path, so each must be a name a global can carry.
// /   Leave it empty & a session holds the Engine's folders alone.
$EngineSessionFolders = array();

// /  --Engine Policy Checks--
// /   The policies an application keeps on the host, beside the Engine's own sandbox
// /   profile, by name. Each names the function that checks it as 'Check', & the label its
// /   line carries in an environment report as 'Finding'. A check accepts whether it may
// /   repair & returns a validity boolean & a status word, as verifySandboxPolicy() does.
// /   The Engine reports each with its own findings & repairs each with its own policies.
// /   Leave it empty & the sandbox profile is the only policy checked.
$EnginePolicyChecks = array();

// /  --Engine Log Provider--
// /   The name of a function that moves a process on to the logfile it should be writing now.
// /   The application names its logfiles, by date & by size, & a request names one as it starts.
// /   A manager runs for weeks, so every manager loop asks this once a minute.
// /   The named function accepts nothing & returns whether a logfile is in use & its path.
// /   Leave it empty & a manager keeps the logfile it started with.
$EngineLogProvider = '';

// /  --Engine Accepted Pipelines--
// /   Which pipeline folders under Resources/Pipelines this application accepts, each
// /   pinned to the exact version its pipelineConfig.php must report.
// /   This is an allowlist. A folder that is not named here is never read, never required & never enumerated.
// /   A directory scan would hand code execution to anybody able to write a folder, & this list keeps one inert.
// /   It is the application's data, so the Engine's default accepts nothing.
// /   A key must be a plain folder name & a value a version such as v3.9.6. An entry that
// /   is neither is refused with a warning & the others are still accepted.
$EngineAcceptedPipelines = array();

// /  --Engine Accepted Shared Modules--
// /   Which files under Resources/Pipelines/Shared a pipeline may load, each pinned to the
// /   exact version the module must report. An allowlist for the same reason as above.
// /   A key must be a plain file name ending .php & a value a version.
$EngineAcceptedSharedModules = array();

// /  --Engine Setting Names--
// /   Which of the application's own settings each shared name reads, so the Engine & the
// /   Pipeline Core never name one application's settings. A shared name the application
// /   does not map reads the default the shared layer chose for it.
// /   FormatDetectionType, WarnOnCapabilityMismatch, DefaultScanner & RequireResourceAwareness are read.
$EngineSettingNames = array();


// / -----------------------------------------------------------------------------------
// / --Engine Manager Timing--
// /
// / Four names here do not begin with Engine, because every manager & the Engine read them by the
// / names they had while the application set them, which it did until v4.2.4. Nothing an
// / administrator sets has any of them, so none shadows a setting in config.php.
// /
// /  --Manager Socket Timeout--
// /   How many seconds a manager or a worker waits on a manager's socket before it gives up.
// /   A request waits three times this long for the Request Manager's answer.
$ManagerSocketTimeout = 2;

// /  --Manager Message Batch Size--
// /   How many messages a manager takes from its socket in one pass of its loop.
$ManagerMessageBatchSize = 32;

// /  --Manager Message Skew--
// /   How many seconds a message's clock may differ from the manager's before it is refused as stale.
$ManagerMessageSkew = 30;

// /  --Startup Key Window--
// /   How many seconds one startup key lasts. A key is accepted for the window it was made in
// /   & the one before, & a key file older than three windows is removed.
$StartupKeyWindow = 10;
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / --Engine Operating Shape--
// /
// /  --Engine Dispatch Direction--
// /   Which way work moves through this application, & therefore what the Engine opens.
// /
// /   Set this to 'requests-upward' for an application where unprivileged work asks a
// /   privileged process for permission. HRConvert2 is this shape. A web request needs
// /   budget before it converts anything, so a listening socket has to exist & anything
// /   that reaches it has to be authenticated.
// /
// /   Set this to 'work-downward' for an application where a privileged process hands
// /   tasks to less privileged workers. A scanner dispatching workers is this shape.
// /   Nothing untrusted ever needs to talk to anything privileged, so no listening socket
// /   is opened at all. A worker takes its instructions from how it was started, does the
// /   work & exits, & the result is an exit code & a file the parent already owns.
// /
// /   The second shape is strictly safer & is safer because it is one way.
// /   A socket that does not exist cannot be authenticated to, forged against or replayed.
// /   An application that runs privileged & opens a listening socket has made the secret
// /   that authenticates it the only thing standing between a compromise of the
// /   unprivileged side & the privileged one.
// /
// /   This exists so the Engine does not quietly assume the shape of the first application
// /   that used it. Declaring it wrong for a privileged application is the mistake this
// /   setting is here to prevent.
// /   Valid options are 'requests-upward' or 'work-downward'.
// /   Default is 'requests-upward'.
$EngineDispatchDirection = 'requests-upward';
// / -----------------------------------------------------------------------------------


// /  --Engine Strict Contract--
// /   Refuses to load when the application has not supplied every value the contract
// /   requires, instead of falling back to a default for the ones that are missing.
// /   Set this to TRUE while developing an application against the Engine. A missing
// /   contract value is a mistake in the application & is worth failing loudly for.
// /   Set this to FALSE on anything shipped. A released application should degrade rather
// /   than refuse, & every Engine read of a contract value carries a local fallback that
// /   configuration cannot overwrite.
// /   Valid options are TRUE or FALSE.
// /   Default is FALSE.
$EngineStrictContract = FALSE;
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The application's overrides are loaded LAST & win.
// / Everything above is an Engine DEFAULT. An application drops a file in Contract that
// / sets what it needs & leaves the rest alone, the way a package ships a configuration &
// / the thing being configured drops one beside it.
// /
// / This file belongs to the Engine & is replaced wholesale when a newer Engine arrives.
// / The override belongs to the application & survives that.
// /
// / An application with no override runs on these defaults, names no providers, & does less, which is the correct degradation.
// / The override is found in the application's contract at Resources/Contract, beside this directory & not inside it.
$engineApplicationOverride = contractPath('app-engine-config.php');
if (file_exists($engineApplicationOverride)) require_once($engineApplicationOverride);
// / -----------------------------------------------------------------------------------
?>
