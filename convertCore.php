<?php if (php_sapi_name() !== 'cli') print('<!DOCTYPE HTML>'.PHP_EOL);
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRConvert2, Copyright on 9/22/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / Application Information ...
// / This application is designed to provide a web-interface for converting file formats
// / on a server for users of any web browser without authentication.
// /
// / File Information ...
// / v4.2.4.
// / HRConvert2 Convert Core.
// / This file contains the core logic of the application.
// /
// / Hardware Requirements ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / Dependency Requirements ...
// / The authoritative list of dependencies is Resources/Engine/depends.php & Resources/Contract/depends.php, the Engine's half & the application's, & nothing else.
// / Run  php convertCore.php -v  to see what this installation has.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify that this installation is complete, current & usable.
// / Returns a verification boolean, the path of the config file, the version reported by
// / versionInfo.php & the core loaded flag, in that order.
// / The pins below are this application's, & they are all it supplies.
// / Everything else is the kernel's verifyInstallationState, which every application built on the Engine boots through.
// / It finds who runs this process, reads versionInfo.php & config.php, checks both against these pins, derives every location & reads the install secret.
// / Every failure other than the secret is fatal & halts there, because nothing downstream
// / can operate without a config file or against a mismatched core.
function verifyInstallation() {
  // / Set variables.
  global $HRConvertVersion, $EngineApplicationVersion, $RequiredConfigVersion, $RequiredGuiVersion, $RequiredLanguageVersion, $RequiredSetupCoreVersion, $RequiredDependencyCoreVersion, $RequiredDependsVersion, $RequiredPipelineCoreVersion, $RequiredWorkflowCoreVersion, $RequiredAuthenticationCoreVersion, $RequiredEmailCoreVersion, $RequiredEngineVersion, $RequiredConfigScript, $RequiredSecretVersion, $CoreLoaded, $Version, $EnableMemoryProtection;
  $InstallationIsVerified = FALSE;
  $configFile = '';
  $CoreLoaded = TRUE;
  // / Define what version of HRConvert2 this core file represents.
  // / Note that this number does not have to match the version numbers of individual components listed below.
  // / The version of the core is typically several versions ahead of individual component versions. This is normal.
  $HRConvertVersion = 'v4.2.4';
  $HRConvertVersion = ltrim($HRConvertVersion, 'vV');
  // / Handed to the Engine here, where it is defined, so there is only ever one copy.
  $EngineApplicationVersion = $HRConvertVersion;
  // / Define the minimum acceptable config.php version that this convertCore.php can accept.
  // / This is only raised when a release adds or removes a config setting.
  // / A release that changes no settings leaves this alone, so existing config files keep working.
  // / Any config.php version that is greater (newer) than the version listed below is considered acceptable.
  $RequiredConfigVersion = 'v4.2.0';
  $RequiredConfigVersion = ltrim($RequiredConfigVersion, 'vV');
  // / Define the GUI version that this convertCore.php can accept.
  // / Note that this check looks for the component version to be identical to what is listed below.
  // / A GUI version that does not exactly match the version listed below is not considered acceptable.
  // / This is because Guis are not always guaranteed to be forward or reverse compatible.
  $RequiredGuiVersion = 'v4.2.3';
  $RequiredGuiVersion = ltrim($RequiredGuiVersion, 'vV');
  // / Define the Language Pack version that this convertCore.php can accept.
  // / Note that this check looks for the component version to be identical to what is listed below.
  // / A Language Pack version that does not exactly match the version listed below is not considered acceptable.
  // / This is because Language Packs are not always guaranteed to be forward or reverse compatible.
  $RequiredLanguageVersion = 'v4.2.3';
  $RequiredLanguageVersion = ltrim($RequiredLanguageVersion, 'vV');
  // / No Core Manager version is pinned here. Every manager role is a subcomponent of the
  // / Engine & getAcceptedManagers() inside the Engine pins each one exactly.
  // / The Setup Core component version this core requires.
  // / This is an exact match. A component built for another core may not be called safely.
  // / Raise this when the Setup Core itself changes.
  // / The configuration model is the Engine's engineConfigModel() & applicationConfigModel() in this file, & their defaults are the two templates, so a new setting raises nothing here.
  $RequiredSetupCoreVersion = 'v4.2.4';
  $RequiredSetupCoreVersion = ltrim($RequiredSetupCoreVersion, 'vV');
  // / The Dependency Core component version this core requires.
  // / This is an EXACT match. A component built for another core may not be called safely.
  $RequiredDependencyCoreVersion = 'v4.2.4';
  $RequiredDependencyCoreVersion = ltrim($RequiredDependencyCoreVersion, 'vV');
  // / The dependency manifest version this core requires.
  // / Raise this whenever a dependency is added, removed, or its minimum version moves.
  // / A manifest from another release may name a package that no longer exists.
  $RequiredDependsVersion = 'v4.2.4';
  $RequiredDependsVersion = ltrim($RequiredDependsVersion, 'vV');
  // / The Pipeline Core component version this core requires.
  // / This is an exact match, because a component built for another core may declare an entry point whose arguments have moved, or capabilities this core cannot honour.
  // / Raise this when the Pipeline Core itself changes & for nothing else.
  // / It is an Engine core, at Resources/Engine/Cores/pipelineCore.php.
  // / The pipelines this application accepts are listed in its contract, in app-engine-config.php, so adding a pipeline or moving one to a new version raises nothing here.
  $RequiredPipelineCoreVersion = 'v4.2.4';
  $RequiredPipelineCoreVersion = ltrim($RequiredPipelineCoreVersion, 'vV');
  // / The Workflow Core component version this core requires.
  // / This is an exact match, because a component built for another core may call an operation with arguments that have moved.
  // / Raise this when the Workflow Core itself changes & for nothing else. It is an Engine core, at Resources/Engine/Cores/workflowCore.php.
  $RequiredWorkflowCoreVersion = 'v4.2.4';
  $RequiredWorkflowCoreVersion = ltrim($RequiredWorkflowCoreVersion, 'vV');
  // / The Authentication Core & Email Core component versions this core requires.
  // / Each is an exact match, for the reason every Engine core's is. The Engine loads them where accounts are on.
  // / They are Engine cores, at Resources/Engine/Cores/authenticationCore.php & Resources/Engine/Cores/emailCore.php.
  $RequiredAuthenticationCoreVersion = 'v4.2.3';
  $RequiredAuthenticationCoreVersion = ltrim($RequiredAuthenticationCoreVersion, 'vV');
  $RequiredEmailCoreVersion = 'v4.2.0';
  $RequiredEmailCoreVersion = ltrim($RequiredEmailCoreVersion, 'vV');
  // / The Engine version this application requires.
  // / This is an EXACT match & it is free, because the Engine is bundled with this release
  // / rather than installed beside it. The updater replaces both together, so the pin & the
  // / file it points at cannot drift apart on an installation nobody has edited by hand.
  // / An administrator who edits the Engine by hand loses the edit at the next update. That
  // / is the same bargain every bundled component makes.
  // / --build cannot write this pin, because it runs inside the Engine. Move it by hand.
  $RequiredEngineVersion = 'v4.2.4';
  $RequiredEngineVersion = ltrim($RequiredEngineVersion, 'vV');
  // / The bootstrap script version this core expects, as an exact match.
  // / The Engine compares the script against this, & -fp disables it as root when it differs.
  $RequiredConfigScript = 'v4.0.1';
  $RequiredConfigScript = ltrim($RequiredConfigScript, 'vV');
  // / The secret file version this core requires.
  // / This is an EXACT match. A secret file reporting anything else is deleted & rewritten.
  // / Raise this to force every installation in the wild off an exposed or outdated secret.
  // / Raising it invalidates every active session on the next request. That is the point.
  $RequiredSecretVersion = 'v3.7.7';
  $RequiredSecretVersion = ltrim($RequiredSecretVersion, 'vV');
  // / The kernel verifies the installation against the pins above.
  list ($InstallationIsVerified, $configFile) = verifyInstallationState(__FILE__, $HRConvertVersion, $RequiredConfigVersion);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $configFile is not purged, because it is a return value.
  purgeSensitiveMemory($EnableMemoryProtection);
  return array($InstallationIsVerified, $configFile, $Version, $CoreLoaded); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify that all required POST & GET inputs are properly sanitized.
function verifyInputs() {
  // / Set variables.
  global $ShowGUI, $EnableMemoryProtection, $NoGui, $ShowFiles, $FileListOnly, $StreamBaseURL, $UserURLDownload, $LogoURL, $ResolvedLogoURL, $SupportedLanguages, $SupportedColors, $SupportedGuis;
  $InputsAreVerified = TRUE;
  $GUI = $Color = $Language = $Token1 = $Token2 = $Height = $Width = $Rotate = $Bitrate = $Method = $Download = $UserFilename = $UserExtension = $Archive = $UserScanType = $IdentifyReport = $IdentifyFilename = $Sort = $WorkflowName = $WorkflowDocument = $WorkflowCheck = $WorkflowExport = $WorkflowPreview = $WorkflowPreviewDocument = $WorkflowStage = $WorkflowStageDescriptions = $RenamePattern = $var = $rawInput = '';
  $variableIsSanitized = $ConvertSelected = $PDFWorkSelected = $FilesToArchive = $FilesToScan = $FilesToDelete = $FilesToIdentify = $FilesToExtract = $FilesToRename = $FilesToStrip = $FilesToMerge = $FilesToSplit = $WorkflowFiles = $logoParts = $logoQuery = array();
  $key = 0;
  // / Determine whether or not to display a full GUI or a minimalized GUI.
  // / The default action is set in config.php. The user can opt for less than the default, but never more.
  // / The user can only disable the full GUI and fall back to a minimal one.
  // / The user can never force enable a full GUI if $ShowGUI is set to FALSE in config.php.
  if (isset($_GET['noGui'])) $ShowGUI = FALSE;
  if (!$ShowGUI) $_GET['noGui'] = TRUE;
  // / Every superglobal this application reads is read here & nowhere else.
  // / An interface that reads $_GET defines API surface, & an interface is not entitled to
  // / define API surface. These three carry no data, only presence, so the value is
  // / discarded & a boolean is handed on. Nothing downstream needs to sanitize a boolean.
  // / A GUI receives these through buildGUI & must never look at $_GET for itself.
  $NoGui = isset($_GET['noGui']);
  $ShowFiles = isset($_GET['showFiles']);
  $FileListOnly = isset($_GET['fileListOnly']);
  // / Sanitize each variable as needed & build a list of error check results.
  if (isset($_POST['filesToDelete'])) list ($FilesToDelete, $variableIsSanitized[$key++]) = sanitize($_POST['filesToDelete'], TRUE);
  if (isset($_POST['language'])) list ($Language, $variableIsSanitized[$key++]) = sanitize($_POST['language'], TRUE);
  if (isset($_GET['language'])) list ($_GET['language'], $variableIsSanitized[$key++]) = sanitize($_GET['language'], TRUE);
  if (isset($_GET['language'])) list ($Language, $variableIsSanitized[$key++]) = sanitize($_GET['language'], TRUE);
  if (isset($_POST['color'])) list ($Color, $variableIsSanitized[$key++]) = sanitize($_POST['color'], TRUE);
  if (isset($_GET['color'])) list ($_GET['color'], $variableIsSanitized[$key++]) = sanitize($_GET['color'], TRUE);
  if (isset($_GET['color'])) list ($Color, $variableIsSanitized[$key++]) = sanitize($_GET['color'], TRUE);
  if (isset($_POST['gui'])) list ($GUI, $variableIsSanitized[$key++]) = sanitize($_POST['gui'], TRUE);
  if (isset($_GET['gui'])) list ($_GET['gui'], $variableIsSanitized[$key++]) = sanitize($_GET['gui'], TRUE);
  if (isset($_GET['gui'])) list ($GUI, $variableIsSanitized[$key++]) = sanitize($_GET['gui'], TRUE);
  // / The sort order rides in the address, as the language, the colour & the interface do, & is read from nowhere else.
  if (isset($_GET['sort'])) list ($Sort, $variableIsSanitized[$key++]) = sanitize($_GET['sort'], TRUE);
  if (isset($_POST['Token1'])) list ($Token1, $variableIsSanitized[$key++]) = sanitize($_POST['Token1'], TRUE);
  if (isset($_POST['Token2'])) list ($Token2, $variableIsSanitized[$key++]) = sanitize($_POST['Token2'], TRUE);
  if (isset($_POST['height'])) list ($Height, $variableIsSanitized[$key++]) = sanitize($_POST['height'], TRUE);
  if (isset($_POST['width'])) list ($Width, $variableIsSanitized[$key++]) = sanitize($_POST['width'], TRUE);
  // / An empty base is the normal case & means the playlist must carry full URLs.
  $StreamBaseURL = $UserURLDownload = '';
  if (isset($_POST['rotate'])) list ($Rotate, $variableIsSanitized[$key++]) = sanitize($_POST['rotate'], TRUE);
  if (isset($_POST['bitrate'])) list ($Bitrate, $variableIsSanitized[$key++]) = sanitize($_POST['bitrate'], TRUE);
  if (isset($_POST['method'])) list ($Method, $variableIsSanitized[$key++]) = sanitize($_POST['method'], TRUE);
  if (isset($_POST['download'])) list ($Download, $variableIsSanitized[$key++]) = sanitize($_POST['download'], TRUE);
  if (isset($_POST['archive'])) list ($Archive, $variableIsSanitized[$key++]) = sanitize($_POST['archive'], TRUE);
  if (isset($_POST['extension'])) list ($UserExtension, $variableIsSanitized[$key++]) = sanitize($_POST['extension'], TRUE);
  if (isset($_POST['filesToArchive'])) list ($FilesToArchive, $variableIsSanitized[$key++]) = sanitize($_POST['filesToArchive'], TRUE);
  if (isset($_POST['archextension'])) list ($UserExtension, $variableIsSanitized[$key++]) = sanitize($_POST['archextension'], TRUE);
  if (isset($_POST['userfilename'])) list ($UserFilename, $variableIsSanitized[$key++]) = sanitize($_POST['userfilename'], TRUE);
  if (isset($_POST['userconvertfilename'])) list ($UserFilename, $variableIsSanitized[$key++]) = sanitize($_POST['userconvertfilename'], TRUE);
  if (isset($_POST['pdfworkSelected'])) list ($PDFWorkSelected, $variableIsSanitized[$key++]) = sanitize($_POST['pdfworkSelected'], TRUE);
  if (isset($_POST['convertSelected'])) list ($ConvertSelected, $variableIsSanitized[$key++]) = sanitize($_POST['convertSelected'], TRUE);
  // / The logo destination is computed here, where the session values already are.
  // /
  // / 'preserve-session' is not an address & is never emitted as one. It means build a link
  // / back to this page carrying what a user has already chosen, so clicking the logo
  // / returns them to a clean page rather than out of the application. This server has no
  // / navigation, so a logo that leaves takes the session with it & the files with that.
  // /
  // / The three carried values are language, colour & interface. Each has ALREADY been
  // / through sanitize() above, & each is checked against the list of what this
  // / installation supports before it is put in a URL. A value that is not in the list is
  // / left out rather than corrected, because a link is not the place to argue with input.
  // /
  // / The session tokens are deliberately NOT carried. A session belongs to the browser
  // / that holds it & putting one in a link makes it something that can be copied out of a
  // / history, a referrer header or somebody's shoulder.
  // /
  // / A configured address is used as written & is required to be http or https. Anything
  // / else falls back to preserving the session, because a logo pointing at javascript: or
  // / data: is a way to run something in a visitor's browser.
  $ResolvedLogoURL = 'convertCore.php';
  if (isset($LogoURL) && strtolower(trim((string)$LogoURL)) !== 'preserve-session') {
    $logoParts = @parse_url(trim((string)$LogoURL));
    if (is_array($logoParts) && isset($logoParts['scheme']) && in_array(strtolower($logoParts['scheme']), array('http', 'https'), TRUE)) $ResolvedLogoURL = trim((string)$LogoURL);
    else warningEntry('The configured Logo URL is not an http or https address & was refused. The logo will return to this page instead.'); }
  else {
    $logoQuery = array();
    if (isset($Language) && isset($SupportedLanguages) && is_array($SupportedLanguages) && array_key_exists($Language, $SupportedLanguages)) $logoQuery[] = 'language='.rawurlencode($Language);
    if (isset($Color) && isset($SupportedColors) && is_array($SupportedColors) && in_array($Color, $SupportedColors, TRUE)) $logoQuery[] = 'color='.rawurlencode($Color);
    if (isset($GUI) && isset($SupportedGuis) && is_array($SupportedGuis) && in_array($GUI, $SupportedGuis, TRUE)) $logoQuery[] = 'gui='.rawurlencode($GUI);
    if (sortCodeIsValid($Sort)) $logoQuery[] = 'sort='.rawurlencode($Sort);
    if (!empty($logoQuery)) $ResolvedLogoURL = 'convertCore.php?'.implode('&', $logoQuery); }
  // / The logo carries the sort order too, because the order a user chose is one of the choices it keeps.
  // / The stream base address is NOT passed through sanitize() & that is deliberate.
  // / sanitize() removes the characters a filename must never hold, which are the ones a
  // / URL is made of. It would return a filename rather than an address.
  // / normalizeStreamBaseURL() in the Engine rebuilds it from parsed components instead &
  // / applies sanitize() to the host, which is the one part that is filename shaped.
  // / It is stored raw here & normalized at the point of use, so the reason a bad one was
  // / refused can be reported to the operator who typed it.
  if (isset($_POST['streamBaseURL']) && is_string($_POST['streamBaseURL'])) $StreamBaseURL = $_POST['streamBaseURL'];
  // / The download address is read raw for the same reason the stream base is. A URL that
  // / has been through sanitize() is a filename & is no longer an address.
  // / fetchUserSuppliedURL rebuilds it & every check a playlist URL receives is applied to
  // / it before anything is fetched.
  if (isset($_POST['userURLDownload']) && is_string($_POST['userURLDownload'])) $UserURLDownload = $_POST['userURLDownload'];
  // / A workflow a page sends is read raw for the same reason, & that is deliberate.
  // / It is JSON, & sanitize() removes the quotes, braces & slashes JSON is written with, so a sanitized workflow is no longer one.
  // / The Workflow Core reads it only as data, & checks every key & value it holds before anything acts on it, more strictly than sanitize() would.
  // / A name it gives what an operation makes is sanitized where the operation uses it, as a name a user types for a button is.
  if (isset($_POST['workflowDocument']) && is_string($_POST['workflowDocument'])) $WorkflowDocument = $_POST['workflowDocument'];
  if (isset($_POST['workflowCheck']) && is_string($_POST['workflowCheck'])) $WorkflowCheck = $_POST['workflowCheck'];
  if (isset($_POST['workflowPreviewDocument']) && is_string($_POST['workflowPreviewDocument'])) $WorkflowPreviewDocument = $_POST['workflowPreviewDocument'];
  // / A workflow an administrator stages for every user is read raw for the same reason, & so is what describes it, which is JSON too, from v4.2.2.
  // / The Workflow Core checks both as it checks any workflow a page sends, & only for a request signed in to an administrator's account.
  if (isset($_POST['workflowStage']) && is_string($_POST['workflowStage'])) $WorkflowStage = $_POST['workflowStage'];
  if (isset($_POST['workflowStageDescriptions']) && is_string($_POST['workflowStageDescriptions'])) $WorkflowStageDescriptions = $_POST['workflowStageDescriptions'];
  // / A raw input is read only when it is text. Anything else is refused & named, because casting a list to text is a PHP warning, & no page sends one.
  foreach (array('streamBaseURL', 'userURLDownload', 'workflowDocument', 'workflowCheck', 'workflowPreviewDocument', 'workflowStage', 'workflowStageDescriptions') as $rawInput) if (isset($_POST[$rawInput]) && !is_string($_POST[$rawInput])) warningEntry('A request carried '.$rawInput.' as something other than text, & it was refused.');
  if (isset($_POST['pdfextension'])) list ($UserExtension, $variableIsSanitized[$key++]) = sanitize($_POST['pdfextension'], TRUE);
  if (isset($_POST['userpdfconvertfilename'])) list ($UserFilename, $variableIsSanitized[$key++]) = sanitize($_POST['userpdfconvertfilename'], TRUE);
  if (isset($_POST['scantype'])) list ($UserScanType, $variableIsSanitized[$key++]) = sanitize($_POST['scantype'], TRUE);
  if (isset($_POST['filesToScan'])) list ($FilesToScan, $variableIsSanitized[$key++]) = sanitize($_POST['filesToScan'], TRUE);
  // / Identify reads the files to identify, whether to write a report, & the name to give it.
  if (isset($_POST['filesToIdentify'])) list ($FilesToIdentify, $variableIsSanitized[$key++]) = sanitize($_POST['filesToIdentify'], TRUE);
  if (isset($_POST['identifyreport'])) list ($IdentifyReport, $variableIsSanitized[$key++]) = sanitize($_POST['identifyreport'], TRUE);
  if (isset($_POST['identifyfilename'])) list ($IdentifyFilename, $variableIsSanitized[$key++]) = sanitize($_POST['identifyfilename'], TRUE);
  // / Extract reads the archives to extract & nothing else, because every file is named as its archive names it.
  if (isset($_POST['filesToExtract'])) list ($FilesToExtract, $variableIsSanitized[$key++]) = sanitize($_POST['filesToExtract'], TRUE);
  // / Rename reads the files to rename & the pattern their new names are made from, which sanitizeNamePattern() sanitizes, keeping its tokens.
  if (isset($_POST['filesToRename'])) list ($FilesToRename, $variableIsSanitized[$key++]) = sanitize($_POST['filesToRename'], TRUE);
  if (isset($_POST['renamepattern'])) list ($RenamePattern, $variableIsSanitized[$key++]) = sanitizeNamePattern($_POST['renamepattern']);
  // / Strip Metadata, Merge & Split read the files they work on. A name Merge gives what it makes arrives as userfilename.
  if (isset($_POST['filesToStrip'])) list ($FilesToStrip, $variableIsSanitized[$key++]) = sanitize($_POST['filesToStrip'], TRUE);
  if (isset($_POST['filesToMerge'])) list ($FilesToMerge, $variableIsSanitized[$key++]) = sanitize($_POST['filesToMerge'], TRUE);
  if (isset($_POST['filesToSplit'])) list ($FilesToSplit, $variableIsSanitized[$key++]) = sanitize($_POST['filesToSplit'], TRUE);
  // / A workflow run reads the workflow to run & the files, in the order a user selected them. A preview of a library's workflow names it as a run does.
  if (isset($_POST['workflowRun'])) list ($WorkflowName, $variableIsSanitized[$key++]) = sanitize($_POST['workflowRun'], TRUE);
  if (isset($_POST['workflowPreview'])) list ($WorkflowPreview, $variableIsSanitized[$key++]) = sanitize($_POST['workflowPreview'], TRUE);
  if (isset($_POST['workflowFiles'])) list ($WorkflowFiles, $variableIsSanitized[$key++]) = sanitize($_POST['workflowFiles'], TRUE);
  // / An export names a workflow in the library by its identifier.
  if (isset($_POST['workflowExport'])) list ($WorkflowExport, $variableIsSanitized[$key++]) = sanitize($_POST['workflowExport'], TRUE);
  // / Check the list of error check results and see if any errors occurred.
  foreach ($variableIsSanitized as $var) if (!$var) ($InputsAreVerified = FALSE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $logoParts, $logoQuery, $variableIsSanitized, $key, $var, $rawInput);
  return array($InputsAreVerified, $ShowGUI, $GUI, $Color, $Language, $Token1, $Token2, $Height, $Width, $Rotate, $Bitrate, $Method, $Download, $UserFilename, $UserExtension, $FilesToArchive, $PDFWorkSelected, $ConvertSelected, $FilesToScan, $FilesToDelete, $UserScanType, $UserURLDownload, $FilesToIdentify, $IdentifyReport, $IdentifyFilename, $Sort, $WorkflowName, $WorkflowFiles, $WorkflowDocument, $WorkflowCheck, $WorkflowExport, $FilesToExtract, $FilesToRename, $RenamePattern, $FilesToStrip, $FilesToMerge, $FilesToSplit, $WorkflowPreview, $WorkflowPreviewDocument, $WorkflowStage, $WorkflowStageDescriptions); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to set the styles to use for the session.
function verifyColors($ButtonStyle) {
  // / Set variables.
  global $Color, $SupportedColors, $AllowUserSelectableColor, $ColorToUse, $GreenButtonCode, $BlueButtonCode, $RedButtonCode, $OrangeButtonCode, $PurpleButtonCode, $DarkButtonCode, $DefaultButtonCode, $EnableMemoryProtection;
  $ColorsAreSet = FALSE;
  $ColorToUse = 'blue';
  $ButtonStyle = strtolower($ButtonStyle);
  $ButtonCode = $DefaultButtonCode;
  $validColors = array('green', 'blue', 'red', 'grey', 'orange', 'purple', 'dark');
  // / Make sure $SupportedColors is valid.
  if (!isset($SupportedColors) or !is_array($SupportedColors)) $SupportedColors = $validColors;
  // / Make sure the Default Color is valid.
  if (isset($ButtonStyle)) if (in_array($ButtonStyle, $SupportedColors)) $ColorToUse = $ButtonStyle;
  // / If allowed and if specified, detect the user's specified color and set that as the color to use.
  if (isset($AllowUserSelectableColor)) {
    if ($AllowUserSelectableColor) if (isset($Color)) if (in_array($Color, $SupportedColors)) {
      $ColorToUse = $Color; } }
  // / Set the $Color variable to whatever the current color is so the next page will use the same one.
  $_GET['color'] = $ColorToUse;
  // / Validate the desired color and set it as the color to use if possible.
  if (in_array($ColorToUse, $validColors)) {
    $ColorsAreSet = TRUE;
    if ($ColorToUse === 'green') $ButtonCode = $GreenButtonCode;
    if ($ColorToUse === 'blue') $ButtonCode = $BlueButtonCode;
    if ($ColorToUse === 'red') $ButtonCode = $RedButtonCode;
    if ($ColorToUse === 'orange') $ButtonCode = $OrangeButtonCode;
    if ($ColorToUse === 'purple') $ButtonCode = $PurpleButtonCode;
    if ($ColorToUse === 'dark') $ButtonCode = $DarkButtonCode;
    if ($ColorToUse === 'grey') $ButtonCode = $DefaultButtonCode; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $validColors);
  return array($ColorsAreSet, $ButtonCode); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to set the GUI to use for the session.
function verifyGui() {
  // / Set variables.
  global $GUI, $DefaultGui, $SupportedGuis, $AllowUserSelectableGui, $GuiFiles, $GuiDir, $GuiResourcesDir, $GuiImageDir, $GuiCSSDir, $GuiJSDir, $GuiHeaderFile, $GuiFooterFile, $GuiUI1File, $GuiUI2File, $GuiEngineFile, $GuiTaskbarFile, $GreenButtonCode, $BlueButtonCode, $RedButtonCode, $OrangeButtonCode, $PurpleButtonCode, $DarkButtonCode, $DefaultButtonCode, $Font, $GuiVersion, $RequiredGuiVersion, $EnableMemoryProtection, $CoreLoaded, $GuiWidth, $GuiMaxWidth, $GUIAlignment;
  // / The interface width is decided ONCE, here, before the stylesheet that uses it loads.
  // / $GuiWidth is FLUID rather than fixed. min() takes whichever is smaller, so a phone is
  // / bounded by its own viewport & a wide monitor by the configured ceiling.
  // / A fixed pixel width does one of two wrong things. Too small & a large display wastes
  // / most of itself. Too large & a phone scrolls sideways, which is the most irritating
  // / thing an interface can do on a device held in one hand.
  // / 96vw rather than 100vw leaves room for a scrollbar. A page sized to the whole viewport
  // / gains a horizontal scrollbar the moment it gains a vertical one.
  // / A value outside the sane range is a typo rather than a preference & the default is
  // / used instead of trusting it.
  // / This is what makes the separate Wide interface unnecessary. It differed from Original
  // / by one number hardcoded in four files, & that number is a setting now.
  $GuiWidth = 'min('.((isset($GuiMaxWidth) && (int)$GuiMaxWidth >= 600 && (int)$GuiMaxWidth <= 3840) ? (int)$GuiMaxWidth : 1400).'px, 96vw)';
  $reqFile = $GuiIsSet = FALSE;
  $GuiToUse = $defaultGui = 'Default';
  $styleCoreFile = $guiVersionFile = '';
  $GuiFiles = $guiFiles = array();
  // / Only installed interfaces are listed, because a fallback naming one that is not installed is the failure this list exists to prevent.
  $defaultGuis = array('Default', 'Original');
  // / Make sure $SupportedGuis is valid.
  if (!isset($SupportedGuis) or !is_array($SupportedGuis)) $SupportedGuis = $defaultGuis;
  // / Make sure the Default GUI is valid.
  if (isset($DefaultGui)) if (in_array($DefaultGui, $SupportedGuis)) $GuiToUse = $DefaultGui;
  // / If allowed and if specified, detect the user's specified GUI and set that as the GUI to use.
  if (isset($AllowUserSelectableGui)) {
    if ($AllowUserSelectableGui) if (isset($GUI)) if (in_array($GUI, $SupportedGuis)) {
      $GuiToUse = $GUI; }
    if (!$AllowUserSelectableGui) {
      $GuiToUse = $defaultGui;
      if (in_array($DefaultGui, $SupportedGuis)) $GuiToUse = $DefaultGui; } }
  // / Build the list of candidate GUIs to try, in order of preference.
  // / The default is appended so a broken or version incompatible GUI has somewhere to fall
  // / back to. A user cannot choose their way out of a broken GUI, so the fallback is silent
  // / to them & noisy in the log.
  $candidateGuis = array($GuiToUse);
  if ($GuiToUse !== $defaultGui) array_push($candidateGuis, $defaultGui);
  foreach ($candidateGuis as $candidateGui) {
    // / Set the variables to a URL safe relative path to required UI files.
    $GuiToUse = $candidateGui;
    $GuiDir = 'UI/'.$GuiToUse.'/';
    $styleCoreFile = $GuiDir.'styleCore.php';
    $GuiHeaderFile = $GuiDir.'header.php';
    $GuiFooterFile = $GuiDir.'footer.php';
    $GuiUI1File = $GuiDir.'convertGui1.php';
    $GuiUI2File = $GuiDir.'convertGui2.php';
    // / The page the Engine names, drawn in place of the two above when the Engine answers a request with a page of its own.
    $GuiEngineFile = $GuiDir.'engineGui.php';
    // / The bar at the top of both pages, from v4.2.1. Both pages draw it, so it is drawn once.
    $GuiTaskbarFile = $GuiDir.'taskbar.php';
    $guiVersionFile = $GuiDir.'uiVersionInfo.php';
    $GuiResourcesDir = $GuiDir.'Resources/';
    $GuiImageDir = $GuiResourcesDir.'Image/';
    $GuiCSSDir = $GuiResourcesDir.'CSS/';
    $GuiJSDir = $GuiResourcesDir.'JS/';
    $guiFiles = array($GuiHeaderFile, $GuiFooterFile, $GuiUI1File, $GuiUI2File, $GuiEngineFile, $GuiTaskbarFile, $styleCoreFile, $guiVersionFile);
    // / The resources folder is part of a GUI, not an optional extra.
    // / It began as an optional convenience & the documentation still called it optional,
    // / but the core had quietly come to depend on it. header.php builds five asset paths
    // / out of these directories on every render & never asks whether they are there, so a
    // / GUI without them did not fail verification. It rendered, with no stylesheet, no
    // / script library & a broken favicon, & the first sign of trouble was an interface
    // / that did nothing when clicked.
    // / A GUI that cannot be styled or scripted is not a working GUI. Checking here means
    // / such a GUI is refused while there is still a Default to fall back to, & the log
    // / says which folder was missing instead of leaving an administrator to infer it from
    // / a blank looking page.
    $guiDirs = array($GuiResourcesDir, $GuiImageDir, $GuiCSSDir, $GuiJSDir);
    $GuiFiles = array();
    $guiDirsExist = TRUE;
    // / Verify that the required GUI folder & every required file exists.
    if (is_dir($GuiDir)) {
      foreach ($guiDirs as $reqDir) if (!is_dir($reqDir)) {
        $guiDirsExist = FALSE;
        warningEntry('GUI '.$GuiToUse.' is missing its '.$reqDir.' folder.'); }
      foreach ($guiFiles as $reqFile) if (file_exists($reqFile)) array_push($GuiFiles, $reqFile);
      // / uiVersionInfo.php only assigns variables, so a failed GUI can still be replaced here.
      // / Nothing has printed yet, unlike the language pack case in buildGUI().
      if (count($guiFiles) === count($GuiFiles) && $guiDirsExist) {
        require($guiVersionFile);
        if ($GuiVersion === $RequiredGuiVersion) $GuiIsSet = TRUE;
        else warningEntry('GUI '.$GuiToUse.' reports version '.$GuiVersion.' but this core requires '.$RequiredGuiVersion.'.'); }
      else if (count($guiFiles) !== count($GuiFiles)) warningEntry('GUI '.$GuiToUse.' is missing one or more required files.'); }
    else warningEntry('GUI '.$GuiToUse.' does not exist at '.$GuiDir.'.');
    // / A usable GUI has been found, so stop looking.
    if ($GuiIsSet) break; }
  // / Set the $GUI variable to whatever the current GUI is so the next page will use the same one.
  $_GET['gui'] = $GuiToUse;
  // / Load the style data once a compatible GUI has been settled on.
  if ($GuiIsSet) {
    // / Every button code is initialized before the style file is read.
    // / The file assigns these lowercase names into this scope, which nothing here shows, so initializing them says where they come from.
    // / It also gives each one a fallback configuration cannot overwrite.
    $greenButtonCode = $blueButtonCode = $redButtonCode = $orangeButtonCode = '';
    $purpleButtonCode = $darkButtonCode = $defaultButtonCode = '';
    require_once($styleCoreFile);
    $GreenButtonCode = $greenButtonCode;
    $BlueButtonCode = $blueButtonCode;
    $RedButtonCode = $redButtonCode;
    $OrangeButtonCode = $orangeButtonCode;
    $PurpleButtonCode = $purpleButtonCode;
    $DarkButtonCode = $darkButtonCode;
    $DefaultButtonCode = $defaultButtonCode;
    // / A style file that supplied nothing usable is worth saying so, because every button
    // / on the page will render without styling & the page will otherwise look merely odd.
    if ($DefaultButtonCode === '') warningEntry('The style file for GUI '.$GuiToUse.' supplied no default button code. Buttons will render unstyled.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $defaultGuis, $defaultGui, $candidateGuis, $candidateGui, $reqFile, $reqDir, $guiFiles, $guiDirs, $guiDirsExist, $styleCoreFile, $guiVersionFile, $greenButtonCode, $blueButtonCode, $redButtonCode, $orangeButtonCode, $purpleButtonCode, $darkButtonCode, $defaultButtonCode);
  return array($GuiIsSet, $GuiToUse, $GuiDir, $GuiFiles); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to set the language to use for the session.
function verifyLanguage() {
  // / Set variables.
  global $Language, $DefaultLanguage, $SupportedLanguages, $AllowUserSelectableLanguage, $LanguageFiles, $GuiDir, $LanguageDir, $LanguageStringsFile, $LanguageFlagFile, $LanguageBaselineFile, $EnableMemoryProtection;
  $reqFile = $LanguageIsSet = FALSE;
  $LanguageToUse = $defaultLanguage = 'en';
  $LanguageFiles = $languageFiles = array();
  $defaultLanguages = array(
  'en' => 'English',   'fr' => 'Français',    'es' => 'Español',
  'zh' => '中文',      'hi' => 'हिन्दी',        'ar' => 'العربية',
  'ru' => 'Русский',   'uk' => 'Українська',  'bn' => 'বাংলা',
  'de' => 'Deutsch',   'ko' => '한국어',        'it' => 'Italiano',
  'pt' => 'Português', 'vi' => 'Tiếng Việt',  'tr' => 'Türkçe',
  'ja' => '日本語',     'id' => 'Bahasa Indonesia',
  'pl' => 'Polski',    'nl' => 'Nederlands',  'sw' => 'Kiswahili',
  'my' => 'မြန်မာ',      'ur' => 'اردو',         'fa' => 'فارسی',
  'he' => 'עברית',     'aii' => 'ܣܘܪܝܝܐ',     'arc' => 'ܐܪܡܝܐ');
  // / Make sure $SupportedLanguages is valid.
  if (!isset($SupportedLanguages) or !is_array($SupportedLanguages)) $SupportedLanguages = $defaultLanguages;
  // / Make sure the Default Language is valid.
  // / The language code is a KEY in this array & the endonym is the value, so membership
  // / is tested with array_key_exists() rather than with in_array().
  if (isset($DefaultLanguage)) if (array_key_exists($DefaultLanguage, $SupportedLanguages)) $LanguageToUse = $DefaultLanguage;
  // / If allowed and if specified, detect the user's specified language and set that as the language to use.
  if (isset($AllowUserSelectableLanguage)) {
    if ($AllowUserSelectableLanguage) if (isset($Language)) if (array_key_exists($Language, $SupportedLanguages)) {
      $LanguageToUse = $Language; }
    if (!$AllowUserSelectableLanguage) {
      $LanguageToUse = $defaultLanguage;
      if (array_key_exists($DefaultLanguage, $SupportedLanguages)) $LanguageToUse = $DefaultLanguage; } }
  // / A language listed in config.php is not a promise that every GUI carries it.
  // / config.php lists the languages the installation offers, & a language pack lives inside a GUI, whose author ships the packs they care about.
  // / So this falls back the way verifyGui() does, through the selection, then the configured default, then English, & the first one present wins.
  // / A user cannot choose their way out of a missing pack, so the fallback is silent to them & noisy here.
  $candidateLanguages = array($LanguageToUse);
  if (isset($DefaultLanguage) && !in_array($DefaultLanguage, $candidateLanguages)) array_push($candidateLanguages, $DefaultLanguage);
  if (!in_array($defaultLanguage, $candidateLanguages)) array_push($candidateLanguages, $defaultLanguage);
  foreach ($candidateLanguages as $candidateLanguage) {
    $LanguageFiles = array();
    $LanguageDir = $GuiDir.'Languages/'.$candidateLanguage.'/';
    $LanguageStringsFile = $LanguageDir.'languageStrings.php';
    $LanguageFlagFile = $LanguageDir.'flag.png';
    $languageFiles = array($LanguageStringsFile, $LanguageFlagFile);
    // / Verify that the language folder & every required file within it exists.
    if (is_dir($LanguageDir)) {
      foreach ($languageFiles as $reqFile) if (file_exists($reqFile)) array_push($LanguageFiles, $reqFile);
      if (count($languageFiles) === count($LanguageFiles)) $LanguageIsSet = TRUE;
      else warningEntry('Language pack '.$candidateLanguage.' at '.$LanguageDir.' is missing one or more required files.'); }
    else warningEntry('The GUI at '.$GuiDir.' does not carry a '.$candidateLanguage.' language pack.');
    // / A usable pack has been found, so stop looking.
    if ($LanguageIsSet) {
      $LanguageToUse = $candidateLanguage;
      break; } }
  // / The baseline is what makes a partial pack usable.
  // / buildGUI loads this FIRST & then loads the selected pack over the top, so a pack that
  // / is missing a string inherits it instead of leaving an undefined variable for an
  // / interface to print. It is only worth loading when it is a different file to the one
  // / that was selected.
  $LanguageBaselineFile = '';
  foreach ($candidateLanguages as $candidateLanguage) {
    $baselineFile = $GuiDir.'Languages/'.$candidateLanguage.'/languageStrings.php';
    if ($candidateLanguage !== $LanguageToUse && file_exists($baselineFile)) {
      $LanguageBaselineFile = $baselineFile;
      break; } }
  // / Set the $Language variable to whatever the current language is so the next page will use the same one.
  $_GET['language'] = $LanguageToUse;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $defaultLanguages, $reqFile, $languageFiles, $defaultLanguage, $candidateLanguages, $candidateLanguage, $baselineFile);
  return array($LanguageIsSet, $LanguageToUse, $LanguageDir, $LanguageFiles); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to set this application's own global variables for the session.
// / Accepts nothing. Returns a validity boolean, which is TRUE once every one has been set.
// / The Engine's are set before this runs, by the Engine's startEngine(), & the data locations by the kernel. They were set here until v4.2.4.
// / What is left is HRConvert2's own. What its pipelines retry with, what its interfaces draw with, the files it refuses to touch, where its
// / own resources are, the report a user's scan writes & the formats each enabled subsystem reads & writes.
// / $Allowed is every extension any enabled subsystem reads or writes. An upload is judged against it, the Engine reads it as AllowedExtensions
// / through the contract's map to judge a fetched file the same way, & a language pack reads $SupportedFormatCount.
// / Every list is read from a setting the kernel confirmed config.php holds, so there is nothing here that can fail, & the check is reaching the end.
function verifyGlobals() {
  // / Set variables.
  global $InstLoc, $DirSep, $RetryCount, $StopCounter, $SleepTimer, $ConvertGuiCounter1, $File, $FCPlural, $FCPlural1, $FCPlural2, $FCPlural3, $GreenButtonCode, $BlueButtonCode, $RedButtonCode, $PurpleButtonCode, $OrangeButtonCode, $DarkButtonCode, $DefaultButtonCode, $HeaderDisplayed, $UIDisplayed, $FooterDisplayed, $LanguageStringsLoaded, $GUIDisplayed, $GUIDirection, $GUIAlignment, $Alert, $Alert1, $Alert2, $Alert3, $DefaultApps, $DangerousFiles, $ResourcesDir, $BootloadersDir, $ConsolidatedLogFileName, $SupportedConversionTypes, $Allowed, $SupportedFormatCount, $ArchiveArray, $DearchiveArray, $DocumentArray, $SpreadsheetArray, $PresentationInputArray, $PresentationOutputArray, $XPSInputArray, $XPSOutputArray, $ImageArray, $MediaInputArray, $MediaOutputArray, $VideoInputArray, $VideoOutputArray, $StreamArray, $DrawingArray, $SVGInputArray, $SVGOutputArray, $ModelArray, $SubtitleInputArray, $SubtitleOutputArray, $PDFWorkArr, $StreamOutputArray, $SCADArray, $SCADInputArray, $SCADOutputArray, $EbookInputArray, $EbookOutputArray, $BootableIsoArray, $ImageInputArray, $ImageOutputArray, $DrawingInputArray, $DrawingOutputArray, $ModelInputArray, $ModelOutputArray,
    $UserArchiveArray, $UserDearchiveArray, $UserDocumentArray, $UserSpreadsheetArray, $UserXPSInputArray, $UserXPSOutputArray, $UserPresentationInputArray, $UserPresentationOutputArray, $UserImageArray, $UserImageInputArray, $UserImageOutputArray, $UserMediaInputArray, $UserMediaOutputArray, $UserVideoInputArray, $UserVideoOutputArray, $UserStreamArray, $UserDrawingArray, $UserDrawingInputArray, $UserDrawingOutputArray, $UserSVGInputArray, $UserSVGOutputArray, $UserModelArray, $UserModelInputArray, $UserModelOutputArray, $UserSCADArray, $UserSCADInputArray, $UserSCADOutputArray, $UserSubtitleInputArray, $UserSubtitleOutputArray, $UserPDFWorkArr, $UserEbookInputArray, $UserEbookOutputArray, $UserBootableIsoArray, $AllowBootableIsoImage, $EnableMemoryProtection;
  $GlobalsAreVerified = FALSE;
  // / Pipeline related variables. A pipeline that retries reads how many attempts remain, & how long to wait between them.
  $SleepTimer = 0;
  $StopCounter = $RetryCount;
  // / UI Related variables.
  $ConvertGuiCounter1 = 0;
  $File = $FCPlural = $FCPlural1 = $FCPlural2 = $FCPlural3 = $GreenButtonCode = $BlueButtonCode = $RedButtonCode = $PurpleButtonCode = $OrangeButtonCode = $DarkButtonCode = $DefaultButtonCode = '';
  $HeaderDisplayed = $UIDisplayed = $FooterDisplayed = $LanguageStringsLoaded = $GUIDisplayed = FALSE;
  $GUIDirection = 'ltr';
  $GUIAlignment = 'left';
  $Alert = 'Cannot convert this file! Try changing the name.';
  $Alert1 = 'Cannot perform a virus scan on this file!';
  $Alert2 = 'File Link Copied to Clipboard!';
  $Alert3 = 'Operation Failed!';
  // / Security related variables.
  $DefaultApps = array('.', '..');
  $DangerousFiles = array(NULL, '.js', '.php', '.html', '.css', '.phar', '..', 'index.php', 'index.html', '--');
  // / Directory related variables.
  $ResourcesDir = $InstLoc.$DirSep.'Resources';
  $BootloadersDir = $ResourcesDir.$DirSep.'Bootloaders';
  // / A/V related variables.
  // / The report a user's scan writes is named here, because a page asks for it by name to download it.
  // / The Scan pipeline writes it, & keeps each scanner's own log beside a user's files, from v4.1.6.
  $ConsolidatedLogFileName = 'User_Consolidated_Virus_Scan_Report.txt';
  // / Format related variables.
  $ImageInputArray = $ImageOutputArray = $SCADInputArray = $SCADOutputArray = array();
  $DrawingInputArray = $DrawingOutputArray = array();
  $ModelInputArray = $ModelOutputArray = array();
  $ArchiveArray = $DearchiveArray = $DocumentArray = $SpreadsheetArray = $PresentationInputArray = $PresentationOutputArray = $XPSInputArray = $XPSOutputArray = $ImageArray = $MediaInputArray = $MediaOutputArray = $VideoInputArray = $VideoOutputArray = $StreamArray = $DrawingArray = $ModelArray = $SubtitleInputArray = $SubtitleOutputArray = $PDFWorkArr = $StreamOutputArray = $SCADArray = $SCADOutputArray = $SVGInputArray = $SVGOutputArray = $EbookInputArray = $EbookOutputArray = $BootableIsoArray = $allArrays = array();
  if (in_array('Archive', $SupportedConversionTypes)) $ArchiveArray = array_map('strtolower', $UserArchiveArray);
  if (in_array('Archive', $SupportedConversionTypes)) $DearchiveArray = array_map('strtolower', $UserDearchiveArray);
  if (in_array('Archive', $SupportedConversionTypes) && $AllowBootableIsoImage) {
    $BootableIsoArray = array_map('strtolower', $UserBootableIsoArray);
    $ArchiveArray = array_map('strtolower', array_merge($ArchiveArray, $BootableIsoArray)); }
  if (in_array('Document', $SupportedConversionTypes)) $DocumentArray = array_map('strtolower', $UserDocumentArray);
  if (in_array('Document', $SupportedConversionTypes)) $SpreadsheetArray = array_map('strtolower', $UserSpreadsheetArray);
  if (in_array('Document', $SupportedConversionTypes)) $XPSInputArray = array_map('strtolower', $UserXPSInputArray);
  if (in_array('Document', $SupportedConversionTypes)) $XPSOutputArray = array_map('strtolower', $UserXPSOutputArray);
  if (in_array('Document', $SupportedConversionTypes)) $PresentationInputArray = array_map('strtolower', $UserPresentationInputArray);
  if (in_array('Document', $SupportedConversionTypes)) $PresentationOutputArray = array_map('strtolower', $UserPresentationOutputArray);
  if (in_array('Image', $SupportedConversionTypes)) {
    $ImageArray = array_map('strtolower', $UserImageArray);
    $ImageInputArray = array_map('strtolower', $UserImageInputArray);
    $ImageOutputArray = array_map('strtolower', $UserImageOutputArray); }
  if (in_array('Audio', $SupportedConversionTypes)) $MediaInputArray = array_map('strtolower', $UserMediaInputArray);
  if (in_array('Audio', $SupportedConversionTypes)) $MediaOutputArray = array_map('strtolower', $UserMediaOutputArray);
  if (in_array('Video', $SupportedConversionTypes)) $VideoInputArray = array_map('strtolower', $UserVideoInputArray);
  if (in_array('Video', $SupportedConversionTypes)) $VideoOutputArray = array_map('strtolower', $UserVideoOutputArray);
  // / Stream is already split & is not given an input & output pair like the others.
  // / $StreamArray is what may be fetched & $StreamOutputArray is derived from the audio
  // / and video lists, because a stream is written as a media file rather than as another
  // / stream. A configured output list here would be a third place to keep in step with
  // / those two & would be wrong the first time either changed.
  if (in_array('Stream', $SupportedConversionTypes) && in_array('Audio', $SupportedConversionTypes) && in_array('Video', $SupportedConversionTypes)) $StreamArray = array_map('strtolower', $UserStreamArray);
  if (in_array('Stream', $SupportedConversionTypes) && in_array('Audio', $SupportedConversionTypes) && in_array('Video', $SupportedConversionTypes)) $StreamOutputArray = array_map('strtolower', array_merge($UserMediaOutputArray, $UserVideoOutputArray));
  if (in_array('Drawing', $SupportedConversionTypes)) {
    $DrawingArray = array_map('strtolower', $UserDrawingArray);
    $DrawingInputArray = array_map('strtolower', $UserDrawingInputArray);
    $DrawingOutputArray = array_map('strtolower', $UserDrawingOutputArray); }
  if (in_array('SVG', $SupportedConversionTypes)) $SVGInputArray = array_map('strtolower', $UserSVGInputArray);
  if (in_array('SVG', $SupportedConversionTypes)) $SVGOutputArray = array_map('strtolower', $UserSVGOutputArray);
  // / The Model lists are read directly, because both are in the required configuration list & a configuration missing either one never reaches this line.
  // / The required list is what the startup check reads, so a setting absent from it is never verified & a fallback for it would become permanent.
  // / It is also how the core decides a configuration is valid at all.
  // / $UserModelArray remains required & still feeds $ModelArray, which other code reads.
  if (in_array('Model', $SupportedConversionTypes)) {
    $ModelArray = array_map('strtolower', $UserModelArray);
    $ModelInputArray = array_map('strtolower', $UserModelInputArray);
    $ModelOutputArray = array_map('strtolower', $UserModelOutputArray); }
  if (in_array('Scad', $SupportedConversionTypes)) {
    $SCADArray = array_map('strtolower', $UserSCADArray);
    $SCADInputArray = array_map('strtolower', $UserSCADInputArray);
    $SCADOutputArray = array_map('strtolower', $UserSCADOutputArray); }
  // / The SCAD output list is read rather than derived, so the upload list & the output list are each declared.
  if (in_array('Subtitle', $SupportedConversionTypes)) $SubtitleInputArray = $UserSubtitleInputArray;
  if (in_array('Subtitle', $SupportedConversionTypes)) $SubtitleOutputArray = array_map('strtolower', $UserSubtitleOutputArray);
  if (in_array('OCR', $SupportedConversionTypes) && in_array('Document', $SupportedConversionTypes)) $PDFWorkArr = array_map('strtolower', $UserPDFWorkArr);
  if (in_array('Ebook', $SupportedConversionTypes)) $EbookInputArray = array_map('strtolower', $UserEbookInputArray);
  if (in_array('Ebook', $SupportedConversionTypes)) $EbookOutputArray = array_map('strtolower', $UserEbookOutputArray);
  $allArrays = array(
    $ArchiveArray, $DearchiveArray, $DocumentArray, $SpreadsheetArray,
    $PresentationInputArray, $PresentationOutputArray, $ImageArray,
    $MediaInputArray, $MediaOutputArray, $VideoInputArray, $VideoOutputArray,
    $StreamArray, $StreamOutputArray, $DrawingArray, $SVGInputArray,
    $SVGOutputArray, $ModelArray, $ModelInputArray, $ModelOutputArray,
    $ImageInputArray, $ImageOutputArray, $SCADInputArray, $SCADOutputArray,
    $DrawingInputArray, $DrawingOutputArray, $SubtitleInputArray, $SubtitleOutputArray,
    $PDFWorkArr, $XPSInputArray, $XPSOutputArray, $SCADArray, $BootableIsoArray,
    $EbookInputArray, $EbookOutputArray);
  $Allowed = array_map('strtolower', array_unique(array_merge(...$allArrays)));
  $SupportedFormatCount = count($Allowed);
  $GlobalsAreVerified = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $allArrays);
  return array($GlobalsAreVerified); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to ask ImageMagick where its configuration actually lives.
// / Accepts no arguments.
// / Returns the absolute configuration directory, or an empty string when none is found.
// / A distribution package puts policy.xml in /etc/ImageMagick-7, & a build from source puts it under its prefix, usually /usr/local/etc/ImageMagick-7.
// / ImageMagick reports its own configuration path, so it is asked rather than guessed.
// / Nothing is written there, & it is asked so the policy an earlier release wrote there is found & put back.
function resolveImageMagickConfigDir() {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  static $cachedDirectory = NULL;
  $ConfigDirectory = '';
  $magickBinary = $outputLine = '';
  $commandOutput = $pathMatches = $candidateDirectories = array();
  $commandExitCode = 1;
  $candidateDirectory = '';
  if ($cachedDirectory !== NULL) $ConfigDirectory = $cachedDirectory;
  else {
    $magickBinary = locateDependency('magick');
    if ($magickBinary === '') $magickBinary = locateDependency('convert');
    // / -list configure prints CONFIGURE_PATH, which is where this build reads policy.xml.
    if ($magickBinary !== '') {
      exec(escapeshellarg($magickBinary).' -list configure 2>&1', $commandOutput, $commandExitCode);
      if ($commandExitCode === 0) {
        foreach ($commandOutput as $outputLine) {
          if ($ConfigDirectory === '' && preg_match('/^CONFIGURE_PATH\s+(\S.*?)\s*$/', (string)$outputLine, $pathMatches)) $ConfigDirectory = rtrim(trim($pathMatches[1]), DIRECTORY_SEPARATOR); } } }
    // / A build that will not say is looked for in the usual places, newest first.
    if ($ConfigDirectory === '') {
      $candidateDirectories = array('/usr/local/etc/ImageMagick-7', '/etc/ImageMagick-7', '/usr/local/etc/ImageMagick-6', '/etc/ImageMagick-6');
      foreach ($candidateDirectories as $candidateDirectory) {
        if ($ConfigDirectory === '' && is_dir($candidateDirectory)) $ConfigDirectory = $candidateDirectory; }
      if ($ConfigDirectory !== '') warningEntry('ImageMagick would not report its configuration path, so '.$ConfigDirectory.' was located by search. Confirm it is the directory this build actually reads.'); }
    if ($Verbose) logEntry('ImageMagick configuration path: '.($ConfigDirectory === '' ? 'NOT FOUND' : $ConfigDirectory).'.');
    $cachedDirectory = $ConfigDirectory; }
  // / The static is never purged. The answer is kept for the life of the process, because ImageMagick is asked by
  // / running it, & purging a static empties it. It holds a path & nothing secret.
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $magickBinary, $outputLine, $commandOutput, $pathMatches, $candidateDirectories, $commandExitCode, $candidateDirectory);
  return $ConfigDirectory; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to cover the policy.xml the installed ImageMagick reads, inside one sandbox profile.
// / Accepts the sandbox profile a conversion is about to run under.
// / Returns TRUE when that profile now covers the file, & FALSE when ImageMagick named no folder.
// / The profiles name where a package or a build from source keeps the policy. A build with another
// / prefix keeps it somewhere else, so ImageMagick is asked, as it is everywhere else, & the answer
// / joins the profile's Overlays. It is asked as a conversion that needs the answer is about to run,
// / because asking as every request starts would run ImageMagick for requests that convert nothing.
function coverResolvedImageMagickPolicy($sandboxProfile) {
  // / Set variables.
  global $EngineSandboxProfiles, $DirSep, $EnableMemoryProtection;
  $PolicyIsCovered = FALSE;
  $configDirectory = $policyPath = '';
  $configDirectory = resolveImageMagickConfigDir();
  if ($configDirectory !== '' && is_array($EngineSandboxProfiles) && isset($EngineSandboxProfiles[$sandboxProfile]) && is_array($EngineSandboxProfiles[$sandboxProfile])) {
    $policyPath = $configDirectory.$DirSep.'policy.xml';
    if (!isset($EngineSandboxProfiles[$sandboxProfile]['Overlays']) or !is_array($EngineSandboxProfiles[$sandboxProfile]['Overlays'])) $EngineSandboxProfiles[$sandboxProfile]['Overlays'] = array();
    if (!isset($EngineSandboxProfiles[$sandboxProfile]['Overlays'][$policyPath])) $EngineSandboxProfiles[$sandboxProfile]['Overlays'][$policyPath] = 'policy.xml';
    $PolicyIsCovered = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $configDirectory, $policyPath, $sandboxProfile);
  return $PolicyIsCovered; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to check the ImageMagick policy each conversion carries, & to take back the one an
// / earlier release wrote into the host.
// / Accepts a boolean permitting a change.
// / Returns a validity boolean & a status word, in that order.
// / Each pipeline that runs ImageMagick carries its own policy, in a Policy folder beside its converter, & takes it into every conversion's sandbox.
// / Nothing is written into the host.
// / A copy an earlier release wrote over the host's policy.xml is put back from the copy verifyPolicyFile() kept, so the directory is resolved from the binary rather than guessed.
// / A pipeline that is installed & is missing its policy is reported, because its conversions then run under the host's own.
// / A copy of this application's that kept no backup of what it replaced is left in place & called kept, because removing it would leave ImageMagick with no policy at all.
// / Every ImageMagick on the host is looked at, not only the one the conversions run, because a host with a package & a build keeps a policy for each.
// / Where several say different things, the status that most needs saying is the one reported.
// / The answer is kept for the life of the process by the Engine's policyAnswerMemory(), which says when a kept answer is used.
function verifyImageMagickPolicy($mayRepair) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection, $DirSep, $InstLoc;
  $PolicyIsValid = $answerIsKept = FALSE;
  $PolicyStatus = 'unchecked';
  $configDirectory = $retireStatus = $pipelineName = $pipelineFolder = $policyPath = $copyStatus = '';
  $hostCopyIsRetired = $policyIsCarried = FALSE;
  $policyPaths = $foundPaths = array();
  $statusRanks = array('none' => 0, 'retired' => 1, 'kept' => 2, 'outdated' => 3, 'refused' => 4);
  list ($answerIsKept, $PolicyIsValid, $PolicyStatus) = policyAnswerMemory('ImageMagick', $mayRepair, '', array());
  if (!$answerIsKept) {
    $configDirectory = resolveImageMagickConfigDir();
    $retireStatus = 'none';
    if ($configDirectory !== '') $policyPaths[] = $configDirectory.$DirSep.'policy.xml';
    $foundPaths = array_merge((array)glob('/etc/ImageMagick-*/policy.xml'), (array)glob('/usr/local/etc/ImageMagick-*/policy.xml'));
    foreach ($foundPaths as $policyPath) if (is_string($policyPath) && !in_array($policyPath, $policyPaths, TRUE)) $policyPaths[] = $policyPath;
    foreach ($policyPaths as $policyPath) {
      list ($hostCopyIsRetired, $copyStatus) = retirePolicyFile('ImageMagick', $policyPath, $mayRepair, FALSE);
      if (isset($statusRanks[$copyStatus]) && $statusRanks[$copyStatus] > $statusRanks[$retireStatus]) $retireStatus = $copyStatus; }
    // / Every pipeline here that runs ImageMagick carries a policy, & is judged only if it is installed.
    $policyIsCarried = TRUE;
    foreach (array('Image', 'OCR', 'Identify') as $pipelineName) {
      $pipelineFolder = $InstLoc.$DirSep.'Resources'.$DirSep.'Pipelines'.$DirSep.$pipelineName;
      if (is_file($pipelineFolder.$DirSep.'pipeline.php') && !is_file($pipelineFolder.$DirSep.'Policy'.$DirSep.'policy.xml')) $policyIsCarried = FALSE; }
    if (!$policyIsCarried) $PolicyStatus = 'not carried';
    else if ($retireStatus === 'outdated' or $retireStatus === 'refused') $PolicyStatus = $retireStatus;
    else if ($retireStatus === 'retired' or $retireStatus === 'kept') $PolicyStatus = $retireStatus;
    else if ($configDirectory === '') $PolicyStatus = 'absent';
    else $PolicyStatus = 'carried';
    $PolicyIsValid = ($PolicyStatus === 'carried' or $PolicyStatus === 'retired' or $PolicyStatus === 'kept' or $PolicyStatus === 'absent');
    if ($Verbose) logEntry('Policy Check: ImageMagick, Status: '.$PolicyStatus.'.');
    list ($answerIsKept) = policyAnswerMemory('ImageMagick', $mayRepair, '', array(), array($PolicyIsValid, $PolicyStatus)); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $answerIsKept, $configDirectory, $retireStatus, $pipelineName, $pipelineFolder, $policyPath, $copyStatus, $hostCopyIsRetired, $policyIsCarried, $policyPaths, $foundPaths, $statusRanks, $mayRepair);
  return array($PolicyIsValid, $PolicyStatus); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to check what confines OpenSCAD on this host, & to take back the override an
// / earlier release added to it.
// / Accepts a boolean permitting a change.
// / Returns a validity boolean & a status word, in that order.
// / Nothing is added to a profile that confines OpenSCAD.
// / A render runs in its sandbox, where the session area is /work, so a grant by host path never reaches one.
// / An override an earlier release added is taken back here, & a profile that confines OpenSCAD is reported, because it is what governs a render.
// / Most distributions do not confine OpenSCAD at all, & then there is nothing to report.
// / The answer is kept for the life of the process by the Engine's policyAnswerMemory(), which says when a kept answer is used.
function verifyOpenScadPolicy($mayRepair) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $PolicyIsValid = $answerIsKept = FALSE;
  $PolicyStatus = 'unchecked';
  $confiningProfile = $overridePath = $retireStatus = $candidateProfile = '';
  $candidateProfiles = array('/etc/apparmor.d/usr.bin.openscad', '/etc/apparmor.d/openscad');
  $overrideIsRetired = FALSE;
  list ($answerIsKept, $PolicyIsValid, $PolicyStatus) = policyAnswerMemory('OpenSCAD AppArmor', $mayRepair, '', array());
  if (!$answerIsKept) {
    foreach ($candidateProfiles as $candidateProfile) {
      if ($confiningProfile === '' && file_exists($candidateProfile)) $confiningProfile = $candidateProfile; }
    if ($confiningProfile === '') {
      // / No profile confines OpenSCAD, so there is nothing to report & nothing is wrong.
      $PolicyIsValid = TRUE;
      $PolicyStatus = 'unconfined';
      // / Logged once per process rather than per conversion. A host where nothing confines
      // / OpenSCAD reports this on every single model, which is noise about a non event.
      if ($Verbose) logEntry('Policy Check: OpenSCAD, Status: unconfined, nothing confines it & the sandbox is what isolates it.'); }
    else {
      $overridePath = '/etc/apparmor.d/local/'.basename($confiningProfile);
      list ($overrideIsRetired, $retireStatus) = retirePolicyFile('OpenSCAD AppArmor', $overridePath, $mayRepair, TRUE);
      // / The profile is loaded again without what was added to it.
      if ($retireStatus === 'retired') reloadApparmorProfile($confiningProfile);
      $PolicyStatus = ($retireStatus === 'none' ? 'confined' : $retireStatus);
      $PolicyIsValid = ($PolicyStatus === 'confined' or $PolicyStatus === 'retired');
      if ($Verbose) logEntry('Policy Check: OpenSCAD, Status: '.$PolicyStatus.', confined by '.$confiningProfile.'.'); }
    list ($answerIsKept) = policyAnswerMemory('OpenSCAD AppArmor', $mayRepair, '', array(), array($PolicyIsValid, $PolicyStatus)); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $answerIsKept, $confiningProfile, $overridePath, $retireStatus, $candidateProfile, $candidateProfiles, $overrideIsRetired, $mayRepair);
  return array($PolicyIsValid, $PolicyStatus); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to give the Engine's version report what only HRConvert2 knows.
// / Accepts nothing. Returns the rows & sections the report places among its own, keyed by where each goes.
// / The Engine prints -v & names this in $EngineReportProvider, so this application writes no report of its own.
// /   Dependencies               A row for every dependency HRConvert2 runs, under the manifest line.
// /   DependencyNotes            Rows after the sandbox line, at the end of the dependencies.
// /   SectionsAfterDependencies  Titled sections after the dependencies.
// /   SwitchedOff                Each feature of HRConvert2 that is installed & switched off, & the setting that switches it on.
// /   SectionsAfterSwitchedOff   Titled sections after the features that are switched off.
// /   SectionsAfterResources     Titled sections after resource awareness, at the end of the report.
// / A row is a Label, a Text & whether it Failed, & a failed row is counted with the report's own.
// / Every dependency check here is the IDENTICAL check the matching converter performs, through toolPath(), so this
// / answers whether an installation will actually work rather than what is configured.
// / An interface or language pack version is read by PATTERN rather than by loading the
// / file, because loading twenty version files would overwrite the variable each time.
function applicationVersionReport() {
  // / Set variables.
  global $RequiredGuiVersion, $RequiredLanguageVersion, $InstLoc, $SupportedConversionTypes, $SupportedGuis, $SupportedLanguages, $DirSep, $UsePyMeshLab, $AllowBootableIsoImage, $AllowUserURLDownload, $AllowUserVirusScan, $VirusScan, $AllowUserShare, $AllowUserIdentify, $EnableMemoryProtection;
  $ReportSections = array('Dependencies' => array(), 'DependencyNotes' => array(), 'SectionsAfterDependencies' => array(), 'SwitchedOff' => array(), 'SectionsAfterSwitchedOff' => array(), 'SectionsAfterResources' => array());
  $modelsAreValid = $ocrToolsAreValid = $archiveToolsAreValid = $libreOfficeIsValid = $subsystemIsReady = FALSE;
  $ffmpegBinary = $inkscapeBinary = $diaBinary = $scadBinary = $imageBinary = $ebookBinary = FALSE;
  $assimpBinary = $meshlabBinary = $tesseractBinary = $pdftotextBinary = FALSE;
  $sevenZipBinary = $rarBinary = $zipBinary = $tarBinary = $mkisofsBinary = $isoHybridBinary = FALSE;
  $fileBinary = $probeBinary = $pdfInfoBinary = FALSE;
  $pdfUniteBinary = $pdfSeparateBinary = $mat2Binary = FALSE;
  $dependencyChecks = $dependencyState = $subsystemChecks = $sectionRows = $guiMatches = $langMatches = array();
  $dependencyName = $subsystemName = $installedGui = $installedLang = $installedEndonym = $checkDir = $checkFile = $foundVersion = $langLine = '';
  $langOk = $langTotal = 0;
  // / Run every dependency check so this reports what WORKS, not what is configured.
  $ffmpegBinary = toolPath('FFMPEG');
  $libreOfficeIsValid = (toolPath('LibreOffice') !== FALSE);
  $inkscapeBinary = toolPath('Inkscape');
  $diaBinary = toolPath('Dia');
  $scadBinary = toolPath('OpenSCAD');
  $imageBinary = toolPath('ImageMagick');
  $ebookBinary = toolPath('Calibre');
  $assimpBinary = toolPath('Assimp');
  $meshlabBinary = toolPath('MeshLab');
  $modelsAreValid = ($assimpBinary !== FALSE && ($UsePyMeshLab or $meshlabBinary !== FALSE));
  $tesseractBinary = toolPath('Tesseract');
  $pdftotextBinary = toolPath('Poppler Utils');
  $ocrToolsAreValid = ($tesseractBinary !== FALSE && $pdftotextBinary !== FALSE);
  $sevenZipBinary = toolPath('7-Zip');
  $rarBinary = toolPath('RAR');
  $zipBinary = toolPath('Zip');
  $tarBinary = toolPath('Tar');
  $mkisofsBinary = toolPath('Genisoimage');
  $archiveToolsAreValid = ($sevenZipBinary !== FALSE && $zipBinary !== FALSE && $tarBinary !== FALSE && $mkisofsBinary !== FALSE);
  $isoHybridBinary = toolPath('Syslinux Utils');
  // / Identify reads a file with each of these where it is installed, & hashes it whether or not they are.
  // / ImageMagick & 7-Zip are listed already, for the conversions they serve.
  $fileBinary = toolPath('File');
  $probeBinary = toolPath('FFprobe');
  $pdfInfoBinary = toolPath('PDFInfo');
  // / Merge runs PDFUnite, Split runs PDFSeparate, & Strip Metadata runs MAT2.
  // / Each operation is offered while its pipeline is installed, so a missing tool is named here before a request fails on it.
  $pdfUniteBinary = toolPath('PDFUnite');
  $pdfSeparateBinary = toolPath('PDFSeparate');
  $mat2Binary = toolPath('MAT2');
  // / Dependencies. An optional one says so, because losing it costs a feature rather than a subsystem.
  $dependencyChecks = array(
    'FFMPEG, media & streams' => array($ffmpegBinary !== FALSE, toolRequirement('FFMPEG'), TRUE),
    'LibreOffice, documents' => array($libreOfficeIsValid, toolRequirement('LibreOffice'), TRUE),
    'ImageMagick, images' => array($imageBinary !== FALSE, toolRequirement('ImageMagick'), TRUE),
    'Inkscape, SVG' => array($inkscapeBinary !== FALSE, toolRequirement('Inkscape'), TRUE),
    'Dia, drawings' => array($diaBinary !== FALSE, toolRequirement('Dia'), TRUE),
    'OpenSCAD, scad' => array($scadBinary !== FALSE, toolRequirement('OpenSCAD'), TRUE),
    'Assimp, models' => array($assimpBinary !== FALSE, toolRequirement('Assimp'), TRUE),
    'Meshlab, models' => array($meshlabBinary !== FALSE, toolRequirement('MeshLab'), FALSE),
    'Tesseract, OCR' => array($tesseractBinary !== FALSE, toolRequirement('Tesseract'), TRUE),
    'Pdftotext, OCR' => array($pdftotextBinary !== FALSE, toolRequirement('Poppler Utils'), TRUE),
    '7-Zip, all extraction' => array($sevenZipBinary !== FALSE, toolRequirement('7-Zip'), TRUE),
    'Zip, archives' => array($zipBinary !== FALSE, toolRequirement('Zip'), TRUE),
    'Tar, archives' => array($tarBinary !== FALSE, toolRequirement('Tar'), TRUE),
    'Mkisofs, iso' => array($mkisofsBinary !== FALSE, toolRequirement('Genisoimage'), TRUE),
    'Rar, archives' => array($rarBinary !== FALSE, toolRequirement('RAR'), FALSE),
    'Isohybrid, iso' => array($isoHybridBinary !== FALSE, toolRequirement('Syslinux Utils'), FALSE),
    'Calibre, e-books' => array($ebookBinary !== FALSE, toolRequirement('Calibre'), TRUE),
    'File, identification' => array($fileBinary !== FALSE, toolRequirement('File'), FALSE),
    'FFprobe, identification' => array($probeBinary !== FALSE, toolRequirement('FFprobe'), FALSE),
    'PDFInfo, identification' => array($pdfInfoBinary !== FALSE, toolRequirement('PDFInfo'), FALSE),
    'PDFUnite, merging' => array($pdfUniteBinary !== FALSE, toolRequirement('PDFUnite'), FALSE),
    'PDFSeparate, splitting' => array($pdfSeparateBinary !== FALSE, toolRequirement('PDFSeparate'), FALSE),
    'MAT2, metadata' => array($mat2Binary !== FALSE, toolRequirement('MAT2'), FALSE));
  foreach ($dependencyChecks as $dependencyName => $dependencyState) {
    if ($dependencyState[0]) $ReportSections['Dependencies'][] = array('Label' => $dependencyName, 'Text' => 'OK'.($dependencyState[2] ? '' : ', optional'), 'Failed' => FALSE);
    else $ReportSections['Dependencies'][] = array('Label' => $dependencyName, 'Text' => 'FAILED, requires '.$dependencyState[1].' or later'.($dependencyState[2] ? '' : ', optional'), 'Failed' => TRUE); }
  $ReportSections['DependencyNotes'][] = array('Label' => 'PyMeshLab, models', 'Text' => ($UsePyMeshLab ? 'enabled, Meshlab not required' : 'disabled, Meshlab required'), 'Failed' => FALSE);
  // / A subsystem is ready only when every dependency it needs is.
  $subsystemChecks = array(
    '3D models' => $modelsAreValid,
    'OCR' => $ocrToolsAreValid,
    'Archives' => $archiveToolsAreValid);
  foreach ($subsystemChecks as $subsystemName => $subsystemIsReady) $sectionRows[] = array('Label' => $subsystemName, 'Text' => ($subsystemIsReady ? 'READY' : 'NOT READY'), 'Failed' => !$subsystemIsReady);
  if (!$AllowBootableIsoImage) $sectionRows[] = array('Label' => 'Bootable disk images', 'Text' => 'DISABLED in config.php', 'Failed' => FALSE);
  else $sectionRows[] = array('Label' => 'Bootable disk images', 'Text' => ($mkisofsBinary === FALSE ? 'NOT READY' : 'READY'), 'Failed' => ($mkisofsBinary === FALSE));
  $ReportSections['SectionsAfterDependencies']['Subsystem readiness'] = $sectionRows;
  // / Features that are installed & switched off, each with the setting that switches it on.
  if (!$AllowUserURLDownload) $ReportSections['SwitchedOff'][] = array('Fetch from a URL', 'Allow User URL Download');
  if (!$AllowUserVirusScan) $ReportSections['SwitchedOff'][] = array('User virus scanning', 'User Virus Scanning');
  if (!$VirusScan) $ReportSections['SwitchedOff'][] = array('Virus scanning', 'Virus Scanning');
  if (!$AllowUserShare) $ReportSections['SwitchedOff'][] = array('Share a file', 'User Shareable File Links');
  if (!$AllowUserIdentify) $ReportSections['SwitchedOff'][] = array('Identify a file', 'User File Identification');
  // / Every installed interface, named, & whether it matches the version the core requires.
  $sectionRows = array();
  foreach ($SupportedGuis as $installedGui) {
    $checkDir = $InstLoc.$DirSep.'UI'.$DirSep.$installedGui;
    $checkFile = $checkDir.$DirSep.'uiVersionInfo.php';
    $foundVersion = '';
    if (!is_dir($checkDir)) $sectionRows[] = array('Label' => $installedGui, 'Text' => 'MISSING, no folder', 'Failed' => TRUE);
    else if (!file_exists($checkFile)) $sectionRows[] = array('Label' => $installedGui, 'Text' => 'FAILED, no uiVersionInfo.php', 'Failed' => TRUE);
    else {
      // / The literal is read & then stripped of its leading v, because the file strips it
      // / too. Comparing the raw literal against the stripped requirement never matches.
      if (preg_match_all('/\$GuiVersion\s*=\s*[\'"]([^\'"]+)[\'"]/', (string)@file_get_contents($checkFile), $guiMatches)) $foundVersion = ltrim(end($guiMatches[1]), 'vV');
      if ($foundVersion === $RequiredGuiVersion) $sectionRows[] = array('Label' => $installedGui, 'Text' => 'OK, '.$foundVersion, 'Failed' => FALSE);
      else $sectionRows[] = array('Label' => $installedGui, 'Text' => 'FAILED, reports '.($foundVersion === '' ? 'no version' : $foundVersion), 'Failed' => TRUE); } }
  $ReportSections['SectionsAfterSwitchedOff']['Installed interfaces'] = $sectionRows;
  // / Language pack coverage, per interface. A pack that fails is named.
  $sectionRows = array();
  foreach ($SupportedGuis as $installedGui) {
    $langOk = $langTotal = 0;
    $langLine = '';
    foreach ($SupportedLanguages as $installedLang => $installedEndonym) {
      $langTotal++;
      $checkFile = $InstLoc.$DirSep.'UI'.$DirSep.$installedGui.$DirSep.'Languages'.$DirSep.$installedLang.$DirSep.'languageStrings.php';
      $foundVersion = '';
      if (file_exists($checkFile)) {
        if (preg_match('/\$LanguageVersion\s*=\s*[\'"]([^\'"]+)[\'"]/', (string)@file_get_contents($checkFile), $langMatches)) $foundVersion = ltrim($langMatches[1], 'vV'); }
      if ($foundVersion === $RequiredLanguageVersion) $langOk++;
      else $langLine .= ' '.$installedLang; }
    $sectionRows[] = array('Label' => $installedGui, 'Text' => $langOk.' of '.$langTotal.' OK'.($langLine === '' ? '' : ', failed:'.$langLine), 'Failed' => ($langLine !== '')); }
  $ReportSections['SectionsAfterSwitchedOff']['Installed language packs'] = $sectionRows;
  // / What this installation converts ends the report.
  $ReportSections['SectionsAfterResources']['Enabled conversion types'] = array(array('Label' => '', 'Text' => implode(', ', $SupportedConversionTypes), 'Failed' => FALSE));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $modelsAreValid, $ocrToolsAreValid, $archiveToolsAreValid, $libreOfficeIsValid, $subsystemIsReady, $ffmpegBinary, $inkscapeBinary, $diaBinary, $scadBinary, $imageBinary, $ebookBinary, $assimpBinary, $meshlabBinary, $tesseractBinary, $pdftotextBinary, $sevenZipBinary, $rarBinary, $zipBinary, $tarBinary, $mkisofsBinary, $isoHybridBinary, $fileBinary, $probeBinary, $pdfInfoBinary, $pdfUniteBinary, $pdfSeparateBinary, $mat2Binary, $dependencyChecks, $dependencyState, $subsystemChecks, $sectionRows, $guiMatches, $langMatches, $dependencyName, $subsystemName, $installedGui, $installedLang, $installedEndonym, $checkDir, $checkFile, $foundVersion, $langLine, $langOk, $langTotal);
  return $ReportSections; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to give the help -h prints what only this application knows.
// / Accepts nothing. Returns the description, the arguments, the sections & the address the Engine's showHelpInfo() prints, keyed by those names.
// / The help is the Engine's, from v4.2.4, so it lists the Engine's commands itself. This is a summary & deliberately not a manual.
// / Everything HRConvert2 does is documented at length in the Documentation folder, & this points there rather than attempting to reproduce it.
// / HRConvert2 has no arguments of its own yet, so it lists none.
function applicationHelpInfo() {
  // / Set variables.
  $HelpInfo = array(
    'Description' => 'A self hosted file conversion server.',
    'Arguments' => array(),
    'Sections' => array('Documentation' => array(
      '  Documentation/INSTALLATION_INSTRUCTIONS.txt  Installing & configuring a server.',
      '  Documentation/ERROR_DESCRIPTIONS.txt         Every numbered error, its cause & its fix.',
      '  Documentation/CREATING_GUIS.txt              Building & installing an interface.',
      '  Documentation/CREATING_LANGUAGE_PACKS.txt    Translating the interface.',
      '  Documentation/CODING_CONVENTIONS.txt         Conventions this codebase follows.',
      '  Documentation/DOCKER_BUILD_INSTRUCTIONS.txt  Building the container image.',
      '  Documentation/CHANGELOG.txt                  What changed & when.',
      '  Documentation/DEVELOPMENT_NOTES.txt          Why each subsystem is built the way it is.')),
    'Address' => 'https://github.com/zelon88/HRConvert2');
  return $HelpInfo; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to load the Files pipeline, which does the work on disk for an upload, a
// / delete & a file listing.
// / Accepts nothing. Returns a readiness boolean & the entry point name, in that order.
// / The Pipeline Core is what loads a pipeline, & loadPipelineCore does not exist at all when
// / the Pipeline Core is absent or mismatched. Calling it regardless was a fatal error on
// / every page, because the page lists files, & an installation without the component must
// / still be served. Every caller already reports a Files pipeline that is not ready.
function loadFilesPipeline() {
  // / Set variables.
  $FilesPipelineIsReady = FALSE;
  $FilesEntryPoint = '';
  if (function_exists('loadPipelineCore')) list ($FilesPipelineIsReady, $FilesEntryPoint) = loadPipelineCore('Files');
  return array($FilesPipelineIsReady, $FilesEntryPoint); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether an extension is one this application refuses to touch.
// / Accepts an extension as getExtension() returns it. Returns TRUE when it is dangerous.
// / getExtension() returns an extension with no leading dot & $DangerousFiles holds dotted
// / extensions alongside bare filenames, so a test of one spelling never matched the other.
// / $Allowed happens to exclude every dangerous format, which was the only reason that was
// / not a hole. Both spellings are tested here, once, for every caller.
// / An empty extension matches the NULL entry, so a file with no extension is dangerous.
function extensionIsDangerous($extension) {
  // / Set variables.
  global $DangerousFiles, $EnableMemoryProtection;
  $ExtensionIsDangerous = FALSE;
  $lowercaseExtension = strtolower((string)$extension);
  if (in_array($lowercaseExtension, $DangerousFiles) or in_array('.'.$lowercaseExtension, $DangerousFiles)) $ExtensionIsDangerous = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $extension, $lowercaseExtension);
  return $ExtensionIsDangerous; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to list the files in a directory that this application is willing to show.
// / Accepts the directory. Returns the names of the files, leaving out every dangerous one.
function getFiles($pathToFiles) {
  // / Set variables.
  global $DangerousFiles, $Pipelines, $EnableMemoryProtection;
  $Files = array();
  $listedNames = array();
  $directoryWasRead = FALSE;
  $candidateName = $candidateExtension = '';
  $candidateIsDangerous = FALSE;
  $filesPipelineIsReady = FALSE;
  $filesEntryPoint = '';
  // / Listing is the component & filtering is the policy.
  // / The Files pipeline reads the directory & returns plain names, & which names this application shows is $DangerousFiles, which an engine cannot guess.
  // / The pipeline omits directories & the dot entries, so nothing here has to.
  // / A pipeline's code is loaded on demand & is not loaded by enumeration.
  // / Enumeration reads pipelineConfig.php, & the functions live in pipeline.php, which loadPipelineCore requires.
  // / So function_exists is asked only after loading, because without that a page render finds no lister & the file list comes back empty.
  // / loadPipelineCore requires once, so asking every time costs nothing.
  list ($filesPipelineIsReady, $filesEntryPoint) = loadFilesPipeline();
  if ($filesPipelineIsReady && function_exists('listFilesInDirectory')) list ($directoryWasRead, $listedNames) = listFilesInDirectory($pathToFiles);
  else warningEntry('The Files pipeline could not be loaded, so no file list could be built.');
  if ($directoryWasRead) foreach ($listedNames as $candidateName) {
    $candidateExtension = getExtension($candidateName);
    $candidateIsDangerous = FALSE;
    if (extensionIsDangerous($candidateExtension)) $candidateIsDangerous = TRUE;
    if (in_array(strtolower($candidateName), $DangerousFiles)) $candidateIsDangerous = TRUE;
    if (!$candidateIsDangerous) array_push($Files, $candidateName); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $filesPipelineIsReady, $filesEntryPoint, $pathToFiles, $listedNames, $directoryWasRead, $candidateName, $candidateExtension, $candidateIsDangerous);
  return $Files; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether a sort code is one this application understands.
// / Accepts the code. Returns TRUE when it is.
// / A code is two digits. The first says what to sort by, 1 the name, 2 the size, 3 the time it changed & 4 the type.
// / The second says which way, 1 ascending & 2 descending.
// / It is a number rather than a word, because it rides in every link an interface draws & says nothing to anybody else.
// / Nothing about it is secret, so it is checked against what it may be rather than encrypted.
function sortCodeIsValid($sortCode) {
  // / Set variables.
  global $EnableMemoryProtection;
  $CodeIsValid = FALSE;
  $CodeIsValid = ((is_string($sortCode) or is_int($sortCode)) && preg_match('/^[1-4][12]$/', (string)$sortCode) === 1);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $sortCode);
  return $CodeIsValid; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to set the order the session's files are listed in.
// / Accepts the sort code asked for. Returns the code in force.
// / No code asked for is the default, by name ascending.
// / A code this application does not understand falls back to the default & says so.
// / The default is a local, so nothing a request carries can leave the list without an order.
function verifySort($requestedSort) {
  // / Set variables.
  global $EnableMemoryProtection;
  $SortToUse = '';
  $defaultSort = '11';
  if ((string)$requestedSort === '') $SortToUse = $defaultSort;
  else if (sortCodeIsValid($requestedSort)) $SortToUse = (string)$requestedSort;
  else {
    $SortToUse = $defaultSort;
    warningEntry('A sort order this application does not understand was asked for, so the files are listed by name.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $defaultSort, $requestedSort);
  return $SortToUse; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to put the session's files in the order asked for & describe each one.
// / Accepts the session folder, the names of the files in it & the sort code, in that order.
// / Returns the names in order & a record for each, keyed by name, in that order.
// / A record holds Size in bytes, SizeText, Modified as a Unix time, ModifiedText, Type, which is the extension, & Kinds.
// / Kinds lists the kinds of file applicationFileTypes() says it is, which is how an interface selects the files of one kind.
// / A file can be more than one kind, as an rtf file is a document & an e-book.
// / The Files pipeline reads the sizes & the times, because it is what touches the session folder.
// / A file it could not describe keeps its place in the list, sorted as if it were empty & oldest, & shows no size or time.
// / Ties are broken by name, so the order never depends on the order the folder was read in.
function arrangeFiles($sessionFolder, $fileNames, $sortCode) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ArrangedFiles = $FileDetails = array();
  $describedFiles = $sortKeys = $fileTypes = $fileKinds = $typeExtensions = array();
  $descriptionsWereRead = $fileWasDescribed = FALSE;
  $fileName = $sortField = $typeName = '';
  $sortDirection = SORT_ASC;
  $sortFlags = SORT_NATURAL | SORT_FLAG_CASE;
  $fileSize = $fileTime = 0;
  if (function_exists('describeFilesInDirectory')) list ($descriptionsWereRead, $describedFiles) = describeFilesInDirectory($sessionFolder, $fileNames);
  if (!$descriptionsWereRead && !empty($fileNames)) warningEntry('The files in this session could not be described, so the list shows no sizes or times.');
  $sortField = sortCodeIsValid($sortCode) ? substr((string)$sortCode, 0, 1) : '1';
  if (sortCodeIsValid($sortCode) && substr((string)$sortCode, 1, 1) === '2') $sortDirection = SORT_DESC;
  if ($sortField === '2' or $sortField === '3') $sortFlags = SORT_NUMERIC;
  if ($sortField === '4') $sortFlags = SORT_STRING | SORT_FLAG_CASE;
  $fileTypes = applicationFileTypes();
  foreach ((array)$fileNames as $fileName) {
    redeclare($fileKinds, array());
    foreach ($fileTypes as $typeName => $typeExtensions) if (in_array(strtolower(getExtension($fileName)), $typeExtensions, TRUE)) $fileKinds[] = $typeName;
    $fileWasDescribed = isset($describedFiles[$fileName]);
    $fileSize = $fileWasDescribed ? (int)$describedFiles[$fileName]['Size'] : 0;
    $fileTime = $fileWasDescribed ? (int)$describedFiles[$fileName]['Modified'] : 0;
    $FileDetails[$fileName] = array('Size' => $fileSize, 'SizeText' => $fileWasDescribed ? describeByteCount($fileSize) : '', 'Modified' => $fileTime, 'ModifiedText' => $fileWasDescribed ? date('Y-m-d H:i', $fileTime) : '', 'Type' => getExtension($fileName), 'Kinds' => $fileKinds);
    $ArrangedFiles[] = $fileName;
    if ($sortField === '2') $sortKeys[] = $fileSize;
    else if ($sortField === '3') $sortKeys[] = $fileTime;
    else if ($sortField === '4') $sortKeys[] = $FileDetails[$fileName]['Type'];
    else $sortKeys[] = $fileName; }
  // / The keys are sorted in the direction asked for, & the names ascending within a tie, & the names follow both.
  if (!empty($ArrangedFiles)) array_multisort($sortKeys, $sortDirection, $sortFlags, $ArrangedFiles, SORT_ASC, SORT_NATURAL | SORT_FLAG_CASE);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $describedFiles, $sortKeys, $fileTypes, $fileKinds, $typeExtensions, $descriptionsWereRead, $fileWasDescribed, $fileName, $sortField, $typeName, $sortDirection, $sortFlags, $fileSize, $fileTime, $sessionFolder, $fileNames, $sortCode);
  return array($ArrangedFiles, $FileDetails); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write what an Identify operation produced as the reply an interface reads.
// / Accepts the operation boolean, the errors boolean & what the operation produced, in that order.
// / Returns one line of JSON holding Complete, Errors, Report & Records, under the key Identify.
// / Every value a file or a tool supplied is plain text by now, & the encoding still escapes every character that means something in HTML.
// / An exclamation mark is escaped too, so a title a file carries can never spell the tag an interface reads as an error.
// / Invalid UTF-8 is substituted rather than refused, because a reply that will not encode would tell the user nothing.
function encodeIdentifyReply($identifyComplete, $identifyErrors, $identifyResult) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ReplyLine = '';
  $replyData = array();
  $encodedReply = '';
  $replyData = array('Identify' => array('Complete' => (bool)$identifyComplete, 'Errors' => (bool)$identifyErrors,
    'Report' => (is_array($identifyResult) && isset($identifyResult['Report'])) ? (string)$identifyResult['Report'] : '',
    'Records' => (is_array($identifyResult) && isset($identifyResult['Records']) && is_array($identifyResult['Records'])) ? array_values($identifyResult['Records']) : array()));
  $encodedReply = (string)json_encode($replyData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);
  $ReplyLine = str_replace('!', '\u0021', $encodedReply);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $replyData, $encodedReply, $identifyComplete, $identifyErrors, $identifyResult);
  return $ReplyLine; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the conversion families & the extensions each reads & writes.
// / Accepts nothing. Returns each family, keyed by name, as the list it reads & the list it writes, in that order.
// / The order is significant & Stream must remain last.
// / Every stream output format is also an ordinary audio or video format, so an mp3 to aac conversion belongs to Audio & to Stream both.
// / The first family to match wins, so Stream sits last & only ever claims a file no earlier family recognized.
// / A stream family needs both halves together, because a family matches only when the input & the output extension are both in it.
// / The two halves are kept apart everywhere else, so that detecting an m3u8 does not also claim every ordinary audio & video file.
// / convertFiles() resolves a conversion with it, & a workflow asks it which files a conversion reads, so the two cannot disagree.
function applicationConversionFamilies() {
  // / Set variables.
  global $DocumentArray, $SpreadsheetArray, $PresentationInputArray, $PresentationOutputArray, $XPSInputArray, $XPSOutputArray, $ImageInputArray, $ImageOutputArray, $ModelInputArray, $ModelOutputArray, $SCADInputArray, $SCADOutputArray, $SVGInputArray, $SVGOutputArray, $VideoInputArray, $VideoOutputArray, $SubtitleInputArray, $SubtitleOutputArray, $MediaInputArray, $MediaOutputArray, $ArchiveArray, $DrawingInputArray, $DrawingOutputArray, $EbookInputArray, $EbookOutputArray, $StreamArray, $StreamOutputArray;
  $ConversionFamilies = array(
    'Document' => array(array_merge($DocumentArray, $SpreadsheetArray, $PresentationInputArray, $XPSInputArray), array_merge($DocumentArray, $SpreadsheetArray, $PresentationOutputArray, $XPSOutputArray)),
    'Image' => array($ImageInputArray, $ImageOutputArray),
    'Model' => array($ModelInputArray, $ModelOutputArray),
    'Scad' => array($SCADInputArray, $SCADOutputArray),
    'SVG' => array($SVGInputArray, $SVGOutputArray),
    'Video' => array($VideoInputArray, $VideoOutputArray),
    'Subtitle' => array($SubtitleInputArray, $SubtitleOutputArray),
    'Audio' => array($MediaInputArray, $MediaOutputArray),
    'Archive' => array($ArchiveArray, $ArchiveArray),
    'Drawing' => array($DrawingInputArray, $DrawingOutputArray),
    'Ebook' => array($EbookInputArray, $EbookOutputArray),
    'Stream' => array(array_merge($StreamArray, $StreamOutputArray), array_merge($StreamArray, $StreamOutputArray)));
  return $ConversionFamilies; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the kinds of file this application reads, by the lists config.php keeps.
// / Accepts nothing. Returns each kind's name & the extensions it reads.
// / A workflow's filter names a kind, & an interface's select by type lists them.
// / A kind whose list is empty is left out, because its family is switched off.
function applicationFileTypes() {
  // / Set variables.
  global $DocumentArray, $XPSInputArray, $SpreadsheetArray, $PresentationInputArray, $ImageInputArray, $MediaInputArray, $VideoInputArray, $DearchiveArray, $EbookInputArray, $ModelInputArray, $DrawingInputArray, $SVGInputArray, $SubtitleInputArray, $SCADInputArray, $StreamArray, $EnableMemoryProtection;
  $FileTypes = array();
  $typeName = '';
  $typeExtensions = $typeLists = array();
  $typeLists = array('Document' => array_merge((array)$DocumentArray, (array)$XPSInputArray), 'Spreadsheet' => $SpreadsheetArray, 'Presentation' => $PresentationInputArray, 'Image' => $ImageInputArray,
    'Audio' => $MediaInputArray, 'Video' => $VideoInputArray, 'Archive' => $DearchiveArray, 'Ebook' => $EbookInputArray, 'Model' => $ModelInputArray, 'Drawing' => $DrawingInputArray,
    'Vector' => $SVGInputArray, 'Subtitle' => $SubtitleInputArray, 'Scad' => $SCADInputArray, 'Stream' => $StreamArray);
  foreach ($typeLists as $typeName => $typeExtensions) if (is_array($typeExtensions) && count($typeExtensions) > 0) $FileTypes[$typeName] = array_values(array_unique(array_map('strtolower', $typeExtensions)));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $typeName, $typeExtensions, $typeLists);
  return $FileTypes; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether a verified pipeline may be offered to a user.
// / Accepts the pipeline's folder name. Returns TRUE where the tools it cannot run without are installed, from v4.2.1.
// / The Pipeline Core answers, from what the pipeline declares in $PipelineRequiredDependencies, & never runs a tool to answer.
// / A pipeline is offered wherever the Pipeline Core cannot be asked, as every pipeline was before v4.2.1, so a fault in finding out never hides one that works.
function pipelineIsOffered($pipelineFolderName) {
  // / Set variables.
  global $EnableMemoryProtection;
  $PipelineIsOffered = TRUE;
  $unmetRequirements = array();
  if (function_exists('pipelineToolsAreInstalled')) list ($PipelineIsOffered, $unmetRequirements) = pipelineToolsAreInstalled($pipelineFolderName);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $unmetRequirements, $pipelineFolderName);
  return $PipelineIsOffered; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether an operation pipeline of a family is installed & may be offered.
// / Accepts the family. Returns TRUE when the Pipeline Core holds an operation pipeline of that family whose tools are installed.
// / An operation whose pipeline is missing, or whose tools are, is offered to no page & no workflow, so a workflow that needs it reads as turned off on this server rather than failing as it runs.
// / Until v4.2.1 this was operationPipelineIsInstalled(), which offered an operation whose tool was not installed, & every press of its button failed.
function operationPipelineIsOffered($operationFamily) {
  // / Set variables.
  global $PipelineCoreActive, $Pipelines, $EnableMemoryProtection;
  $PipelineIsOffered = FALSE;
  $folderName = '';
  $pipelineRecord = array();
  if (!empty($PipelineCoreActive) && is_array($Pipelines)) foreach ($Pipelines as $folderName => $pipelineRecord) {
    if (is_array($pipelineRecord) && (string)($pipelineRecord['Kind'] ?? '') === 'operation' && (string)($pipelineRecord['Family'] ?? '') === (string)$operationFamily && pipelineIsOffered($folderName)) $PipelineIsOffered = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderName, $pipelineRecord, $operationFamily);
  return $PipelineIsOffered; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the scanners a user may be offered.
// / Accepts nothing. Returns each scanner, named as runVirusScan() names it, whose pipeline is installed & whose tools are.
// / Scan is offered where one is, & a file's Scan panel draws a button for each one.
function offeredScanners() {
  // / Set variables.
  global $PipelineCoreActive, $Pipelines, $EnableMemoryProtection;
  $OfferedScanners = $pipelineRecord = array();
  $folderName = '';
  if (!empty($PipelineCoreActive) && is_array($Pipelines)) foreach ($Pipelines as $folderName => $pipelineRecord) {
    if (is_array($pipelineRecord) && (string)($pipelineRecord['Kind'] ?? '') === 'scanner' && pipelineIsOffered($folderName)) $OfferedScanners[] = strtolower((string)($pipelineRecord['Family'] ?? '')); }
  $OfferedScanners = array_values(array_unique($OfferedScanners));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $pipelineRecord, $folderName);
  return $OfferedScanners; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the conversion types a user may be offered.
// / Accepts nothing. Returns each type --Supported Conversion Types-- names that this server can run, from v4.2.1.
// / A type is offered where a conversion pipeline of its family is installed & the tools it cannot run without are. XPS is the Document family's.
// / OCR is the OCR operation pipeline's, & is offered where it may be.
// / Where the Pipeline Core is unavailable, every type is offered as configured, because a fault in finding out must never hide one that works.
// / $SupportedConversionTypes is never narrowed, because an upload is accepted for every format it names & a request is judged by it.
// / What an interface offers is $OfferedConversionTypes, which buildGUI() sets from this.
function offeredConversionTypes() {
  // / Set variables.
  global $SupportedConversionTypes, $PipelineCoreActive, $Pipelines, $EnableMemoryProtection;
  $OfferedTypes = $pipelineRecord = $offeredFamilies = array();
  $folderName = $conversionType = $typeFamily = '';
  if (empty($PipelineCoreActive) or !is_array($Pipelines)) $OfferedTypes = array_values((array)$SupportedConversionTypes);
  else {
    foreach ($Pipelines as $folderName => $pipelineRecord) {
      if (is_array($pipelineRecord) && (string)($pipelineRecord['Kind'] ?? '') === 'conversion' && pipelineIsOffered($folderName)) $offeredFamilies[(string)($pipelineRecord['Family'] ?? '')] = TRUE; }
    foreach ((array)$SupportedConversionTypes as $conversionType) {
      redeclare($typeFamily, ((string)$conversionType === 'XPS') ? 'Document' : (string)$conversionType);
      if ($typeFamily === 'OCR') { if (operationPipelineIsOffered('OCR')) $OfferedTypes[] = (string)$conversionType; }
      else if (isset($offeredFamilies[$typeFamily])) $OfferedTypes[] = (string)$conversionType; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $pipelineRecord, $offeredFamilies, $folderName, $conversionType, $typeFamily);
  return $OfferedTypes; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the conversion families a user may be offered, & what each reads & writes.
// / Accepts nothing. Returns the families applicationConversionFamilies() names, less any offeredConversionTypes() leaves out.
// / A workflow's Convert offers these, so a step cannot ask for a format no installed tool writes, & leaves out a file no installed tool reads.
function offeredConversionFamilies() {
  // / Set variables.
  global $EnableMemoryProtection;
  $OfferedFamilies = $offeredTypes = $familyFormats = array();
  $familyName = '';
  $offeredTypes = offeredConversionTypes();
  foreach (applicationConversionFamilies() as $familyName => $familyFormats) if (in_array($familyName, $offeredTypes, TRUE)) $OfferedFamilies[$familyName] = $familyFormats;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $offeredTypes, $familyFormats, $familyName);
  return $OfferedFamilies; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether the Files pipeline is installed & verified.
// / Accepts nothing. Returns TRUE when the Pipeline Core holds the pipeline of kind file that deletes & renames a session's files.
// / Delete & Rename are offered to a workflow only where it is, as an operation whose pipeline is missing is offered to none.
function filesPipelineIsInstalled() {
  // / Set variables.
  global $PipelineCoreActive, $Pipelines, $EnableMemoryProtection;
  $PipelineIsInstalled = FALSE;
  $folderName = '';
  $pipelineRecord = array();
  if (!empty($PipelineCoreActive) && is_array($Pipelines)) foreach ($Pipelines as $folderName => $pipelineRecord) {
    if (is_array($pipelineRecord) && (string)($pipelineRecord['Kind'] ?? '') === 'file' && (string)($pipelineRecord['Family'] ?? '') === 'Files') $PipelineIsInstalled = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $folderName, $pipelineRecord);
  return $PipelineIsInstalled; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to list the formats the operation pipelines of one family read or write.
// / Accepts the family, the list to read, which is Input or Output, & whether to leave out a pipeline that may not be offered, in that order.
// / Returns every format those pipelines declare in that list, lowercased & each once.
// / A workflow is checked against every format a family declares & runs only those it may be offered, from v4.2.1,
// / so a step naming a format whose tool is not installed here is a workflow this server cannot run, & not a mistake in the workflow.
function operationFamilyFormats($operationFamily, $formatList, $offeredOnly) {
  // / Set variables.
  global $PipelineCoreActive, $Pipelines, $EnableMemoryProtection;
  $FamilyFormats = $pipelineRecord = array();
  $folderName = '';
  if (!empty($PipelineCoreActive) && is_array($Pipelines)) foreach ($Pipelines as $folderName => $pipelineRecord) {
    if (is_array($pipelineRecord) && (string)($pipelineRecord['Kind'] ?? '') === 'operation' && (string)($pipelineRecord['Family'] ?? '') === (string)$operationFamily && is_array($pipelineRecord[(string)$formatList] ?? NULL) && ($offeredOnly !== TRUE or pipelineIsOffered($folderName))) redeclare($FamilyFormats, array_merge($FamilyFormats, $pipelineRecord[(string)$formatList])); }
  $FamilyFormats = array_values(array_unique(array_map('strtolower', array_map('strval', $FamilyFormats))));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $pipelineRecord, $folderName, $operationFamily, $formatList, $offeredOnly);
  return $FamilyFormats; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to list the formats an archive of a selection of files may be made in.
// / Accepts nothing. Returns the formats config.php offers that the installed Archiver pipeline says it can write, with zip first, & none where it may not be offered.
// / config.php's list serves the Archive pipeline as well, which converts one archive into another & makes a bootable disk
// / image from an archive holding a bootloader. The Archiver makes neither, so a bootable image is not offered for a selection.
// / Until v4.1.6 it was, & every file of such a request failed with error 4004.
function archiveOperationFormats() {
  // / Set variables.
  global $ArchiveArray, $EnableMemoryProtection;
  $ArchiveFormats = $configuredFormats = array();
  $configuredFormats = array_values(array_unique(array_map('strtolower', array_merge(array('zip'), (array)$ArchiveArray))));
  $ArchiveFormats = array_values(array_intersect($configuredFormats, operationFamilyFormats('Archive', 'Output', TRUE)));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $configuredFormats);
  return $ArchiveFormats; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to list the formats an archive may be extracted from.
// / Accepts nothing. Returns the formats config.php lists as archives this server reads that the installed Extractor pipeline says it reads, & none where it may not be offered.
// / config.php's list is the one a file's other archive controls are drawn from, so an archive this server would not read is never offered for extraction.
// / A .tar.gz is listed by its last extension, gz, as every file is. With Archive conversions off, config.php lists none, & nothing is offered.
function extractOperationFormats() {
  // / Set variables.
  global $DearchiveArray;
  $ExtractFormats = array();
  $ExtractFormats = array_values(array_intersect(array_values(array_unique(array_map('strtolower', (array)$DearchiveArray))), operationFamilyFormats('Extract', 'Input', TRUE)));
  return $ExtractFormats; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to list the formats metadata may be stripped from.
// / Accepts nothing. Returns the formats the installed Metadata pipeline says it reads, & none without it or without the tools it cannot run without, MAT2 among them.
function metadataOperationFormats() {
  // / Set variables.
  $MetadataFormats = array();
  $MetadataFormats = operationFamilyFormats('Metadata', 'Input', TRUE);
  return $MetadataFormats; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to declare the operations a workflow may run, & the kinds of file a step may name.
// / Accepts nothing. Returns an array holding Operations & FileTypes. It is $EngineOperationProvider.
// / Each operation names the function that runs it, the budget it takes, whether this server allows it, what it reads, its modes & its options.
// / Every one runs the code a button in an interface runs, so a workflow does nothing a user could not do by hand.
// / Convert decides what it reads from the extension it is asked to write, because a conversion to jpg reads an image & not a spreadsheet.
// / Convert passes on a file already in the format it writes, through convertOperationKeeps(), from v4.2.3, so the step after it takes that file too.
// / Archive's Filename names the one archive every file goes into, & an empty one archives each file under its own name.
// / Archive offers the formats archiveOperationFormats() lists, which the Archiver pipeline can write, & none without it, so it is offered only where the Archiver is installed.
// / Archive, OCR, Identify & Scan are each an operation pipeline's, & one whose pipeline is missing is not offered, from v4.1.6, nor one whose tools are not installed, from v4.2.1.
// / Convert offers the formats of the families offeredConversionFamilies() names, so a step cannot ask for a format no installed tool writes.
// / Scan is offered where a scanner is, & its Scanner offers all & each scanner offeredScanners() names. All is every scanner this server has.
// / A choice that is only offered on some servers is declared twice, from v4.2.1. Choices is every choice this application knows & Offered is the ones this server runs.
// / A workflow naming a choice this server does not offer is valid & is not offered here, so a shipped workflow whose tool is not installed is not called a broken one.
// / Extract is the Extractor pipeline's, from v4.1.7. It reads the archives extractOperationFormats() lists, one at a time, & names no option, because every file is named as its archive names it.
// / What it places joins the session beside the archive, so the step after it reads those files as it reads any other.
// / Delete & Rename hand their work to the Files pipeline, through deleteFiles() & renameFiles(). Each says it consumes the files it takes, so the Workflow Core refuses a later step taking them.
// / Neither takes a budget, as neither button does, so a workflow that cleans up still runs while the Resource Manager refuses every budget, as it does once storage is spent.
// / Rename's Pattern makes each new name from {name}, {n} & {date}, & never changes an extension.
// / StripMetadata is the Metadata pipeline's, & reads the formats metadataOperationFormats() lists. It makes a clean copy beside each file, named as OCR names what it makes.
// / Merge & Split are the PDF pipeline's. Merge puts every PDF it is given into one, named by Filename, & Split makes a PDF of each page of every PDF it is given.
// / Convert's Extension sorts its formats into groups by the kind of file each is, which convertOperationGroups() lists, so a page can show them a group at a time.
// / SessionFiles names getFiles(), which lists a session's files as a page lists them, so the Workflow Core's Select step finds only what a user could see & select.
// / See Documentation/ABOUT_WORKFLOWS.txt.
function applicationOperations() {
  // / Set variables.
  global $SupportedConversionTypes, $AllowUserIdentify, $AllowUserVirusScan, $DocumentArray, $PDFWorkArr, $ArchiveArray, $EnableMemoryProtection;
  $OperationRegistry = array();
  $convertOutputs = $convertChoices = $archiveFormats = $archiveChoices = $extractFormats = $ocrFormats = $metadataFormats = $familyFormats = $scanners = array();
  $familyName = '';
  foreach (offeredConversionFamilies() as $familyName => $familyFormats) if (is_array($familyFormats[1])) redeclare($convertOutputs, array_merge($convertOutputs, $familyFormats[1]));
  redeclare($convertOutputs, array_values(array_unique(array_map('strtolower', $convertOutputs))));
  foreach (applicationConversionFamilies() as $familyName => $familyFormats) if (is_array($familyFormats[1])) redeclare($convertChoices, array_merge($convertChoices, $familyFormats[1]));
  redeclare($convertChoices, array_values(array_unique(array_map('strtolower', $convertChoices))));
  $scanners = offeredScanners();
  $archiveFormats = archiveOperationFormats();
  $archiveChoices = array_values(array_intersect(array_values(array_unique(array_map('strtolower', array_merge(array('zip'), (array)$ArchiveArray)))), operationFamilyFormats('Archive', 'Output', FALSE)));
  $extractFormats = extractOperationFormats();
  $metadataFormats = metadataOperationFormats();
  $ocrFormats = array_values(array_unique(array_merge(array('pdf'), (array)$DocumentArray)));
  $OperationRegistry = array(
    'Operations' => array(
      'Convert' => array('Invoke' => 'invokeConvertOperation', 'Budget' => 'conversion', 'Enabled' => (count($convertOutputs) > 0), 'Accepts' => 'convertOperationAccepts', 'Keeps' => 'convertOperationKeeps', 'Modes' => array('each'),
        'Options' => array('Extension' => array('Type' => 'choice', 'Required' => TRUE, 'Choices' => $convertChoices, 'Offered' => $convertOutputs, 'Groups' => convertOperationGroups()), 'Filename' => array('Type' => 'name', 'Default' => ''),
          'Height' => array('Type' => 'integer', 'Default' => 0, 'Minimum' => 0, 'Maximum' => 65535), 'Width' => array('Type' => 'integer', 'Default' => 0, 'Minimum' => 0, 'Maximum' => 65535),
          'Rotate' => array('Type' => 'integer', 'Default' => 0, 'Minimum' => -360, 'Maximum' => 360), 'Bitrate' => array('Type' => 'integer', 'Default' => 0, 'Minimum' => 0, 'Maximum' => 100000))),
      'Archive' => array('Invoke' => 'invokeArchiveOperation', 'Budget' => 'archive', 'Enabled' => (in_array('Archive', (array)$SupportedConversionTypes, TRUE) && in_array('zip', $archiveFormats, TRUE)), 'Inputs' => array(), 'Modes' => array('together', 'each'),
        'Options' => array('Extension' => array('Type' => 'choice', 'Default' => 'zip', 'Choices' => $archiveChoices, 'Offered' => $archiveFormats), 'Filename' => array('Type' => 'name', 'Default' => 'Workflow_Files'))),
      'Extract' => array('Invoke' => 'invokeExtractOperation', 'Budget' => 'extract', 'Enabled' => (in_array('Archive', (array)$SupportedConversionTypes, TRUE) && count($extractFormats) > 0), 'Inputs' => $extractFormats, 'Modes' => array('each'), 'Options' => array()),
      'OCR' => array('Invoke' => 'invokeOcrOperation', 'Budget' => 'OCR', 'Enabled' => (in_array('OCR', (array)$SupportedConversionTypes, TRUE) && in_array('Document', (array)$SupportedConversionTypes, TRUE) && operationPipelineIsOffered('OCR')), 'Inputs' => (array)$PDFWorkArr, 'Modes' => array('each'),
        'Options' => array('Extension' => array('Type' => 'choice', 'Default' => 'pdf', 'Choices' => $ocrFormats), 'Method' => array('Type' => 'choice', 'Default' => '0', 'Choices' => array('0', '1', '2')), 'Filename' => array('Type' => 'name', 'Default' => ''))),
      'Identify' => array('Invoke' => 'invokeIdentifyOperation', 'Budget' => 'Identify', 'Enabled' => ($AllowUserIdentify === TRUE && operationPipelineIsOffered('Identify')), 'Inputs' => array(), 'Modes' => array('together'),
        'Options' => array('Report' => array('Type' => 'boolean', 'Default' => TRUE), 'Filename' => array('Type' => 'name', 'Default' => 'User_Identify_Report'))),
      'Scan' => array('Invoke' => 'invokeScanOperation', 'Budget' => 'user virus scan', 'Enabled' => ($AllowUserVirusScan === TRUE && operationPipelineIsOffered('Scan') && count($scanners) > 0), 'Inputs' => array(), 'Modes' => array('together'),
        'Options' => array('Scanner' => array('Type' => 'choice', 'Default' => 'all', 'Choices' => array('all', 'clamav', 'scancore'), 'Offered' => array_values(array_unique(array_merge(array('all'), $scanners)))))),
      'Delete' => array('Invoke' => 'invokeDeleteOperation', 'Budget' => FALSE, 'Enabled' => filesPipelineIsInstalled(), 'Inputs' => array(), 'Modes' => array('together'), 'Consumes' => 'deleted', 'Options' => array()),
      'Rename' => array('Invoke' => 'invokeRenameOperation', 'Budget' => FALSE, 'Enabled' => filesPipelineIsInstalled(), 'Inputs' => array(), 'Modes' => array('together'), 'Consumes' => 'renamed',
        'Options' => array('Pattern' => array('Type' => 'name', 'Default' => '{name}-{n}'))),
      'StripMetadata' => array('Invoke' => 'invokeStripMetadataOperation', 'Budget' => 'metadata', 'Enabled' => (count($metadataFormats) > 0), 'Inputs' => $metadataFormats, 'Modes' => array('each'),
        'Options' => array('Filename' => array('Type' => 'name', 'Default' => ''))),
      'Merge' => array('Invoke' => 'invokeMergeOperation', 'Budget' => 'merge', 'Enabled' => operationPipelineIsOffered('PDF'), 'Inputs' => array('pdf'), 'Modes' => array('together'),
        'Options' => array('Filename' => array('Type' => 'name', 'Default' => 'Merged'))),
      'Split' => array('Invoke' => 'invokeSplitOperation', 'Budget' => 'split', 'Enabled' => operationPipelineIsOffered('PDF'), 'Inputs' => array('pdf'), 'Modes' => array('each'), 'Options' => array())),
    'FileTypes' => applicationFileTypes(),
    'SessionFiles' => 'getFiles');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $convertOutputs, $convertChoices, $archiveFormats, $archiveChoices, $extractFormats, $ocrFormats, $metadataFormats, $familyFormats, $scanners, $familyName);
  return $OperationRegistry; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to say whether a Convert step changes a file beyond its format, from v4.2.3.
// / Accepts the step's options. Returns TRUE when a height, a width, a rotation or a bitrate is asked for.
// / A value of 0 is what a page sends for one left alone, & asks for nothing.
function convertOptionsTransform($operationOptions) {
  // / Set variables.
  global $EnableMemoryProtection;
  $OptionsTransform = FALSE;
  $optionName = '';
  foreach (array('Height', 'Width', 'Rotate', 'Bitrate') as $optionName) if (is_array($operationOptions) && isset($operationOptions[$optionName]) && (int)$operationOptions[$optionName] !== 0) $OptionsTransform = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $optionName, $operationOptions);
  return $OptionsTransform; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether Convert reads a file, given the extension it is asked to write.
// / Accepts the file's extension & the step's options. Returns TRUE when a family reads the one & writes the other.
// / A file that is already the format asked for is not read, because there is nothing to convert.
// / It asks the lists convertFiles() asks, less any family this server does not offer, so a workflow leaves out a file the converter would refuse,
// / & one no installed tool reads, from v4.2.1.
function convertOperationAccepts($fileExtension, $operationOptions) {
  // / Set variables.
  global $EnableMemoryProtection;
  $OperationAccepts = FALSE;
  $outputExtension = $inputExtension = $familyName = '';
  $familyFormats = array();
  $inputExtension = strtolower((string)$fileExtension);
  $outputExtension = (is_array($operationOptions) && isset($operationOptions['Extension'])) ? strtolower((string)$operationOptions['Extension']) : '';
  foreach (offeredConversionFamilies() as $familyName => $familyFormats) if (is_array($familyFormats[0]) && is_array($familyFormats[1]) && in_array($inputExtension, $familyFormats[0]) && in_array($outputExtension, $familyFormats[1])) $OperationAccepts = TRUE;
  if ($inputExtension === $outputExtension) $OperationAccepts = FALSE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $outputExtension, $inputExtension, $familyName, $familyFormats, $fileExtension, $operationOptions);
  return $OperationAccepts; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether Convert passes a file on unchanged, given the extension it is asked to write, from v4.2.3.
// / Accepts the file's extension & the step's options. Returns TRUE when the file is already the format asked for & nothing else is asked of it.
// / Until v4.2.3 such a file was left out of the step, so a later step taking what Convert made never took it, & a zip of every image made jpg lacked the jpg selected.
// / The Workflow Core asks this only about a file Convert does not read, & passes a kept file on in its place without converting it or counting it as made.
// / A step that resizes, rotates or re-encodes keeps nothing, & leaves such a file out as before, because no pipeline converts a format to itself,
// / & a file passed on unchanged would be passed on without what the step asks of it.
function convertOperationKeeps($fileExtension, $operationOptions) {
  // / Set variables.
  global $EnableMemoryProtection;
  $OperationKeeps = FALSE;
  $outputExtension = '';
  $outputExtension = (is_array($operationOptions) && isset($operationOptions['Extension'])) ? strtolower((string)$operationOptions['Extension']) : '';
  if ($outputExtension !== '' && strtolower((string)$fileExtension) === $outputExtension && !convertOptionsTransform($operationOptions)) $OperationKeeps = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $outputExtension, $fileExtension, $operationOptions);
  return $OperationKeeps; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to sort the formats Convert writes into groups, each named for a kind of file.
// / Accepts nothing. Returns each kind's name & the formats written as that kind, in the order applicationFileTypes() names the kinds.
// / A page shows a long list of formats a group at a time, as tabs beneath Convert's own, & names each group as it names the kind.
// / A format written as more than one kind is in each of their groups, so pdf is a Document, a Presentation & an Ebook alike.
// / The groups are drawn from the lists the families are, so every format Convert writes is in at least one of them.
function convertOperationGroups() {
  // / Set variables.
  global $DocumentArray, $XPSOutputArray, $SpreadsheetArray, $PresentationOutputArray, $ImageOutputArray, $MediaOutputArray, $VideoOutputArray, $ArchiveArray, $EbookOutputArray, $ModelOutputArray, $DrawingOutputArray, $SVGOutputArray, $SubtitleOutputArray, $SCADOutputArray, $StreamArray, $EnableMemoryProtection;
  $ConvertGroups = array();
  $groupName = '';
  $groupFormats = $groupLists = array();
  $groupLists = array('Document' => array_merge((array)$DocumentArray, (array)$XPSOutputArray), 'Spreadsheet' => $SpreadsheetArray, 'Presentation' => $PresentationOutputArray, 'Image' => $ImageOutputArray,
    'Audio' => $MediaOutputArray, 'Video' => $VideoOutputArray, 'Archive' => $ArchiveArray, 'Ebook' => $EbookOutputArray, 'Model' => $ModelOutputArray, 'Drawing' => $DrawingOutputArray,
    'Vector' => $SVGOutputArray, 'Subtitle' => $SubtitleOutputArray, 'Scad' => $SCADOutputArray, 'Stream' => $StreamArray);
  foreach ($groupLists as $groupName => $groupFormats) if (is_array($groupFormats) && count($groupFormats) > 0) $ConvertGroups[$groupName] = array_values(array_unique(array_map('strtolower', array_map('strval', $groupFormats))));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $groupName, $groupFormats, $groupLists);
  return $ConvertGroups; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run Convert, as a button & a workflow both run it.
// / Accepts the files & the options, which are Extension, Filename, Height, Width, Rotate & Bitrate.
// / Returns whether any file converted, whether anything went wrong, the names it made & what it found, which is nothing, in that order.
// / A size, a rotation or a bitrate of 0 is passed on as none, which is what a page sends when a user leaves one alone.
function invokeConvertOperation($selectedFiles, $operationOptions) {
  // / Set variables.
  global $EnableMemoryProtection;
  $OperationSucceeded = $OperationErrors = FALSE;
  $ProducedFiles = $OperationFindings = array();
  $optionValues = array();
  $numericName = '';
  $optionValues = array('Filename' => (string)($operationOptions['Filename'] ?? ''), 'Extension' => strtolower((string)($operationOptions['Extension'] ?? '')));
  foreach (array('Height', 'Width', 'Rotate', 'Bitrate') as $numericName) $optionValues[$numericName] = (isset($operationOptions[$numericName]) && (string)$operationOptions[$numericName] !== '0') ? (string)$operationOptions[$numericName] : '';
  list ($OperationSucceeded, $OperationErrors, $ProducedFiles) = convertFiles($selectedFiles, $optionValues['Filename'], $optionValues['Extension'], $optionValues['Height'], $optionValues['Width'], $optionValues['Rotate'], $optionValues['Bitrate']);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $optionValues, $numericName, $selectedFiles, $operationOptions);
  return array($OperationSucceeded, $OperationErrors, $ProducedFiles, $OperationFindings); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run Archive, as a button & a workflow both run it.
// / Accepts the files & the options, which are Extension & Filename.
// / Archiving a selection is the Archiver pipeline's, from v4.1.6, & this dispatches to it as the OCR & Identify adapters dispatch to theirs.
// / Returns whether any file was archived, whether anything went wrong, the archives it made & what it found, which is nothing, in that order.
// / A Filename puts every file into one archive of that name, & an empty one archives each file under its own name.
function invokeArchiveOperation($selectedFiles, $operationOptions) {
  // / Set variables.
  global $PipelineCoreActive, $EnableMemoryProtection;
  $OperationSucceeded = $OperationErrors = FALSE;
  $ProducedFiles = $OperationFindings = array();
  $operationOutput = array();
  if (!$PipelineCoreActive or !function_exists('runOperationPipeline')) {
    $OperationErrors = TRUE;
    errorEntry('Archive was requested but no Archiver pipeline is installed!', 34006, FALSE); }
  else {
    list ($OperationSucceeded, $OperationErrors, $operationOutput) = runOperationPipeline('Archive', $selectedFiles, (string)($operationOptions['Filename'] ?? ''), strtolower((string)($operationOptions['Extension'] ?? 'zip')), '');
    if (is_array($operationOutput)) $ProducedFiles = array_values(array_filter($operationOutput, 'is_string')); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $operationOutput, $selectedFiles, $operationOptions);
  return array($OperationSucceeded, $OperationErrors, $ProducedFiles, $OperationFindings); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run Extract, as a button & a workflow both run it.
// / Accepts the archives & the options, which are none, because every file is named as its archive names it.
// / Extracting an archive is the Extractor pipeline's, from v4.1.7, & this dispatches to it as the Archive adapter dispatches to the Archiver.
// / Returns whether any file was extracted, whether anything went wrong, the files it placed & what it found, which is nothing, in that order.
// / What it placed sits beside the archive in the session, so a workflow's next step reads it from there as it reads any file.
function invokeExtractOperation($selectedFiles, $operationOptions) {
  // / Set variables.
  global $PipelineCoreActive, $EnableMemoryProtection;
  $OperationSucceeded = $OperationErrors = FALSE;
  $ProducedFiles = $OperationFindings = array();
  $operationOutput = array();
  if (!$PipelineCoreActive or !function_exists('runOperationPipeline')) {
    $OperationErrors = TRUE;
    errorEntry('Extract was requested but no Extractor pipeline is installed!', 34006, FALSE); }
  else {
    list ($OperationSucceeded, $OperationErrors, $operationOutput) = runOperationPipeline('Extract', $selectedFiles, '', '', '');
    if (is_array($operationOutput)) $ProducedFiles = array_values(array_filter($operationOutput, 'is_string')); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $operationOutput, $selectedFiles, $operationOptions);
  return array($OperationSucceeded, $OperationErrors, $ProducedFiles, $OperationFindings); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run OCR, as a button & a workflow both run it.
// / Accepts the files & the options, which are Extension, Method & Filename.
// / Returns whether any file was read, whether anything went wrong, the names it made & what it found, which is nothing, in that order.
// / OCR is an operation pipeline, & there is no built in fallback for it, so a missing OCR pipeline is reported rather than read as an OCR run that produced nothing.
function invokeOcrOperation($selectedFiles, $operationOptions) {
  // / Set variables.
  global $PipelineCoreActive, $EnableMemoryProtection;
  $OperationSucceeded = $OperationErrors = FALSE;
  $ProducedFiles = $OperationFindings = array();
  $operationOutput = array();
  if (!$PipelineCoreActive or !function_exists('runOperationPipeline')) {
    $OperationErrors = TRUE;
    errorEntry('OCR was requested but no OCR pipeline is installed & this core has no built in OCR!', 34006, FALSE); }
  else {
    list ($OperationSucceeded, $OperationErrors, $operationOutput) = runOperationPipeline('OCR', $selectedFiles, (string)($operationOptions['Filename'] ?? ''), strtolower((string)($operationOptions['Extension'] ?? 'pdf')), (string)($operationOptions['Method'] ?? '0'));
    if (is_array($operationOutput)) $ProducedFiles = array_values($operationOutput); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $operationOutput, $selectedFiles, $operationOptions);
  return array($OperationSucceeded, $OperationErrors, $ProducedFiles, $OperationFindings); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run Identify, as a button & a workflow both run it.
// / Accepts the files & the options, which are Report & Filename. Report is a boolean, or 1 as a page sends it.
// / Returns whether any file was identified, whether anything went wrong, the report it wrote if any & what it found, in that order.
// / What it found is Identify's result, holding Records & Report, which a button shows & a workflow passes over.
function invokeIdentifyOperation($selectedFiles, $operationOptions) {
  // / Set variables.
  global $PipelineCoreActive, $EnableMemoryProtection;
  $OperationSucceeded = $OperationErrors = FALSE;
  $ProducedFiles = $OperationFindings = array();
  if (!$PipelineCoreActive or !function_exists('runOperationPipeline')) {
    $OperationErrors = TRUE;
    errorEntry('Identify was requested but no Identify pipeline is installed!', 34006, FALSE); }
  else {
    list ($OperationSucceeded, $OperationErrors, $OperationFindings) = runOperationPipeline('Identify', $selectedFiles, (string)($operationOptions['Filename'] ?? ''), 'txt', $operationOptions['Report'] ?? FALSE);
    if (!is_array($OperationFindings)) $OperationFindings = array();
    if (isset($OperationFindings['Report']) && is_string($OperationFindings['Report']) && $OperationFindings['Report'] !== '') $ProducedFiles[] = $OperationFindings['Report']; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $selectedFiles, $operationOptions);
  return array($OperationSucceeded, $OperationErrors, $ProducedFiles, $OperationFindings); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to scan files for a workflow, & keep the report beside them.
// / Accepts the files & the options, which hold Scanner, all, clamav or scancore.
// / Returns whether a verdict was reached, whether anything went wrong, the report it kept & what it found, in that order.
// / What it found holds Halt as threat when any file is found infected, which ends a workflow, so nothing is made from an infected file.
// / It holds Suspicious, the files found suspicious, from v4.2.3, & the workflow names them & goes on, because a heuristic is not a detection.
// / It holds Incomplete, the files a scanner could not read in full, from v4.2.3, which the workflow names too & goes on, because an
// / encrypted archive is an ordinary file & not a threat.
// / A button's scan hands its report straight to the browser, & a workflow keeps it in the session instead, beside what it scanned.
// / The report is named User_Virus_Scan_Report.txt, or the next free name when that is taken.
function invokeScanOperation($selectedFiles, $operationOptions) {
  // / Set variables.
  global $ConvertDir, $EnableMemoryProtection;
  $OperationSucceeded = $OperationErrors = FALSE;
  $ProducedFiles = $OperationFindings = array();
  $threatWasFound = $nameWasFound = FALSE;
  $reportPath = $reportName = $keptPath = $keptName = '';
  $suspiciousFiles = $incompleteFiles = array();
  list ($OperationSucceeded, $OperationErrors, $threatWasFound, $reportPath, $reportName, $suspiciousFiles, $incompleteFiles) = userVirusScan($selectedFiles, strtolower((string)($operationOptions['Scanner'] ?? 'all')));
  if ($threatWasFound) $OperationFindings['Halt'] = 'threat';
  if (count($suspiciousFiles) > 0) $OperationFindings['Suspicious'] = $suspiciousFiles;
  if (count($incompleteFiles) > 0) $OperationFindings['Incomplete'] = $incompleteFiles;
  clearstatcache(TRUE, (string)$reportPath);
  if (is_file((string)$reportPath)) {
    list ($nameWasFound, $keptPath, $keptName) = array(TRUE, $ConvertDir.'User_Virus_Scan_Report.txt', 'User_Virus_Scan_Report.txt');
    if (file_exists($keptPath) or is_link($keptPath)) list ($nameWasFound, $keptPath, $keptName) = findFreeOutputName($ConvertDir, 'User_Virus_Scan_Report', 'txt');
    if ($nameWasFound && @copy((string)$reportPath, $keptPath)) $ProducedFiles[] = $keptName;
    else {
      $OperationErrors = TRUE;
      warningEntry('A workflow\'s virus scan report could not be kept beside the files it scanned.'); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $threatWasFound, $nameWasFound, $reportPath, $reportName, $keptPath, $keptName, $suspiciousFiles, $incompleteFiles, $selectedFiles, $operationOptions);
  return array($OperationSucceeded, $OperationErrors, $ProducedFiles, $OperationFindings); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run Delete, as a button & a workflow both run it.
// / Accepts the files & the options, which are none.
// / Returns whether any file was deleted, whether anything went wrong, the files it made, which are none, & what it found, which is nothing, in that order.
// / deleteFiles() decides what may be deleted & the Files pipeline removes it, as for a button.
// / A file it deletes is gone by its name, so the registry says Delete consumes what it takes.
function invokeDeleteOperation($selectedFiles, $operationOptions) {
  // / Set variables.
  global $EnableMemoryProtection;
  $OperationSucceeded = $OperationErrors = FALSE;
  $ProducedFiles = $OperationFindings = array();
  list ($OperationSucceeded, $OperationErrors) = deleteFiles($selectedFiles);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $selectedFiles, $operationOptions);
  return array($OperationSucceeded, $OperationErrors, $ProducedFiles, $OperationFindings); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run Rename, as a button & a workflow both run it.
// / Accepts the files & the options, which are Pattern.
// / Returns whether any file has its new name, whether anything went wrong, the name each file now has & what it found, which is nothing, in that order.
// / The names it returns are what a workflow's next step reads. A file is gone by the name it had, so the registry says Rename consumes what it takes.
function invokeRenameOperation($selectedFiles, $operationOptions) {
  // / Set variables.
  global $EnableMemoryProtection;
  $OperationSucceeded = $OperationErrors = FALSE;
  $ProducedFiles = $OperationFindings = array();
  list ($OperationSucceeded, $OperationErrors, $ProducedFiles) = renameFiles($selectedFiles, (string)($operationOptions['Pattern'] ?? ''));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $selectedFiles, $operationOptions);
  return array($OperationSucceeded, $OperationErrors, $ProducedFiles, $OperationFindings); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to dispatch an operation to the operation pipeline of a family, & keep the names it made.
// / Accepts the operation's name, the family, the files, the name to give what it makes, the extension & the method, in that order.
// / Returns whether it succeeded, whether anything went wrong & the names it made, in that order.
// / A missing pipeline is reported rather than read as an operation that made nothing, because nothing in this core can do its work.
function dispatchOperationPipeline($operationName, $operationFamily, $selectedFiles, $userFilename, $userExtension, $operationMethod) {
  // / Set variables.
  global $PipelineCoreActive, $EnableMemoryProtection;
  $OperationSucceeded = $OperationErrors = FALSE;
  $ProducedFiles = $operationOutput = array();
  if (!$PipelineCoreActive or !function_exists('runOperationPipeline')) {
    $OperationErrors = TRUE;
    errorEntry($operationName.' was requested but no '.$operationFamily.' pipeline is installed!', 34006, FALSE); }
  else {
    list ($OperationSucceeded, $OperationErrors, $operationOutput) = runOperationPipeline($operationFamily, $selectedFiles, (string)$userFilename, (string)$userExtension, (string)$operationMethod);
    if (is_array($operationOutput)) $ProducedFiles = array_values(array_filter($operationOutput, 'is_string')); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $operationOutput, $operationName, $operationFamily, $selectedFiles, $userFilename, $userExtension, $operationMethod);
  return array($OperationSucceeded, $OperationErrors, $ProducedFiles); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run Strip Metadata, as a button & a workflow both run it.
// / Accepts the files & the options, which are Filename.
// / Returns whether any file was cleaned, whether anything went wrong, the clean copies it made & what it found, which is nothing, in that order.
// / Stripping metadata is the Metadata pipeline's. It makes a clean copy beside each file, under the Filename given or the file's own name, & the next free name when that is taken, as OCR does.
function invokeStripMetadataOperation($selectedFiles, $operationOptions) {
  // / Set variables.
  global $EnableMemoryProtection;
  $OperationSucceeded = $OperationErrors = FALSE;
  $ProducedFiles = $OperationFindings = array();
  list ($OperationSucceeded, $OperationErrors, $ProducedFiles) = dispatchOperationPipeline('Strip Metadata', 'Metadata', $selectedFiles, (string)($operationOptions['Filename'] ?? ''), '', '');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $selectedFiles, $operationOptions);
  return array($OperationSucceeded, $OperationErrors, $ProducedFiles, $OperationFindings); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run Merge, as a button & a workflow both run it.
// / Accepts the PDFs, in the order their pages are to follow one another, & the options, which are Filename.
// / Returns whether a PDF was made, whether anything went wrong, the PDF it made & what it found, which is nothing, in that order.
// / Merging is the PDF pipeline's. An empty Filename names what it makes Merged.
function invokeMergeOperation($selectedFiles, $operationOptions) {
  // / Set variables.
  global $EnableMemoryProtection;
  $OperationSucceeded = $OperationErrors = FALSE;
  $ProducedFiles = $OperationFindings = array();
  list ($OperationSucceeded, $OperationErrors, $ProducedFiles) = dispatchOperationPipeline('Merge', 'PDF', $selectedFiles, (string)($operationOptions['Filename'] ?? ''), 'pdf', 'merge');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $selectedFiles, $operationOptions);
  return array($OperationSucceeded, $OperationErrors, $ProducedFiles, $OperationFindings); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run Split, as a button & a workflow both run it.
// / Accepts the PDFs & the options, which are none.
// / Returns whether any page was made, whether anything went wrong, the PDF made of each page & what it found, which is nothing, in that order.
// / Splitting is the PDF pipeline's. Each page of a PDF named report.pdf becomes report-1.pdf, report-2.pdf & so on.
function invokeSplitOperation($selectedFiles, $operationOptions) {
  // / Set variables.
  global $EnableMemoryProtection;
  $OperationSucceeded = $OperationErrors = FALSE;
  $ProducedFiles = $OperationFindings = array();
  list ($OperationSucceeded, $OperationErrors, $ProducedFiles) = dispatchOperationPipeline('Split', 'PDF', $selectedFiles, '', 'pdf', 'split');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $selectedFiles, $operationOptions);
  return array($OperationSucceeded, $OperationErrors, $ProducedFiles, $OperationFindings); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to declare every sandbox profile this application uses.
// / Accepts nothing. Returns an array of profile name to a record describing it.
// /
// / This is CODE rather than configuration & it is never shown to an administrator.
// / Every entry below was arrived at by bisection rather than by reading documentation.
// / Documentation/Build/bisect.sh removes one permission at a time until a conversion
// / stops working, & what is left is the least permissive profile that still runs.
// / An administrator has nothing to decide here. A looser profile is less safe & a tighter
// / one does not work, so there is no third option worth offering anybody.
// /
// / The Engine asks for this array as it starts, through $EngineSandboxProvider in the contract,
// / & assembles the sandbox from it. It knows nothing about LibreOffice or MeshLab & it does
// / not need to, because a profile is data.
// / An application with different tools declares different profiles & the Engine is
// / unchanged. That is the whole reason this is an array rather than a run of branches.
// /
// / Every field is optional except OperationType. A profile that needs nothing declares
// / nothing, which is why ffmpeg, archive & poppler are one line each.
// /
// /   OperationType   What the per operation resource limit is looked up under.
// /   ReadOnlyPaths   Bound read only if present. A missing path is skipped rather than
// /                   refused, because a tool may be installed without its data.
// /   Tmpfs           An empty writable directory created inside the namespace.
// /   Environment     Set inside the namespace. NEVER prefixed onto the command, because
// /                   bwrap execs without a shell & would read NAME=value as a program.
// /   MapToRoot       Maps this process to root INSIDE the namespace & nowhere else.
// /                   An unprivileged user namespace, so it confers nothing on the host.
// /   Overlays        Host files a calling pipeline's own file covers inside the namespace,
// /                   keyed by the host path & naming the file within the read only resource
// /                   the call carries. The host file is never touched. A host path that is
// /                   not a plain file here, or that is reached through a link, is skipped.
// /   Reason          Why this profile is not empty. Read this before changing anything.
// /
// / Adding a profile.
// / Run bisect.sh against the conversion, add the entry, write the Reason & stop.
// / Nothing else in this application needs to know the profile exists.
function getSandboxProfiles() {
  // / Set variables.
  global $EnableMemoryProtection;
  $SandboxProfiles = array();
  $SandboxProfiles = array(

    // / LibreOffice starts a display server & probes OpenCL unless it is told not to.
    // / Both fail inside a namespace & both are slow before they fail.
    'libreoffice' => array(
      'OperationType' => 'Document',
      'ReadOnlyPaths' => array('/etc/libreoffice', '/etc/java'),
      'Environment' => array('SAL_USE_VCLPLUGIN' => 'svp', 'SAL_DISABLE_OPENCL' => '1'),
      'Reason' => 'Headless rendering with no display server & no OpenCL probing.'),

    // / Both major versions are bound, because a host may carry either.
    // / The delegate list & the rest of the configuration are the host's, & the policy is the calling pipeline's own, which covers the host's wherever a package or a build keeps one.
    'imagemagick' => array(
      'OperationType' => 'Image',
      'ReadOnlyPaths' => array('/etc/ImageMagick-7', '/etc/ImageMagick-6', '/usr/share/ImageMagick-7', '/usr/share/ImageMagick-6'),
      'Overlays' => array(
        '/etc/ImageMagick-7/policy.xml' => 'policy.xml',
        '/etc/ImageMagick-6/policy.xml' => 'policy.xml',
        '/usr/local/etc/ImageMagick-7/policy.xml' => 'policy.xml',
        '/usr/local/etc/ImageMagick-6/policy.xml' => 'policy.xml'),
      'Reason' => 'The delegate list is read from these directories, & the calling pipeline\'s policy covers the host\'s.'),

    // / Language data lives in one of two places depending on how it was installed.
    // / The OCR page split runs ImageMagick under this profile too, & carries its own policy. A
    // / build from source keeps its policy below /usr, which every sandbox shows, so the carried one
    // / covers each place a policy is kept, as it does for the imagemagick profile.
    'tesseract' => array(
      'OperationType' => 'OCR',
      'ReadOnlyPaths' => array('/usr/share/tesseract-ocr', '/usr/share/tessdata'),
      'Overlays' => array(
        '/etc/ImageMagick-7/policy.xml' => 'policy.xml',
        '/etc/ImageMagick-6/policy.xml' => 'policy.xml',
        '/usr/local/etc/ImageMagick-7/policy.xml' => 'policy.xml',
        '/usr/local/etc/ImageMagick-6/policy.xml' => 'policy.xml'),
      'Reason' => 'Recognition fails with no language data & the failure names no cause. The page split\'s policy covers the host\'s.'),

    'inkscape' => array(
      'OperationType' => 'SVG',
      'ReadOnlyPaths' => array('/usr/share/inkscape', '/etc/inkscape'),
      'Reason' => 'Templates & the default preferences are read from these directories.'),

    'dia' => array(
      'OperationType' => 'Drawing',
      'ReadOnlyPaths' => array('/usr/share/dia'),
      'Reason' => 'Shape libraries are read from this directory.'),

    'calibre' => array(
      'OperationType' => 'Ebook',
      'ReadOnlyPaths' => array('/usr/share/calibre'),
      'Reason' => 'Conversion recipes are read from this directory.'),

    // / This profile is the most complicated one here & every line of it was earned.
    // / meshlabserver links Qt & initializes GLEW whatever it has been asked to do, so a
    // / format conversion that renders nothing still requires an OpenGL context.
    // / The offscreen platform plugin starts Qt & supplies no GL, so an X server is needed.
    // / Xvfb will only create /tmp/.X11-unix as root & then checks that it is root owned,
    // / which is what MapToRoot & the Tmpfs below are for.
    // / The vendor file keeps glvnd away from a GPU driver that crashes while enumerating
    // / devices this namespace deliberately does not expose.
    'meshlab' => array(
      'OperationType' => 'Model',
      'ReadOnlyPaths' => array('/usr/share/meshlab', '/usr/share/pymeshlab'),
      'Tmpfs' => array('/tmp/.X11-unix'),
      'MapToRoot' => TRUE,
      'Environment' => array(
        'LIBGL_ALWAYS_SOFTWARE' => '1',
        'GALLIUM_DRIVER' => 'llvmpipe',
        '__EGL_VENDOR_LIBRARY_FILENAMES' => '/usr/share/glvnd/egl_vendor.d/50_mesa.json'),
      'Reason' => 'An X server inside the namespace, rendering in software, with no GPU driver loaded.'),

    'openscad' => array(
      'OperationType' => 'Scad',
      'ReadOnlyPaths' => array('/usr/share/openscad'),
      'Reason' => 'The standard library is read from this directory.'),

    // / clamscan cannot start without its signature database, & ClamAV 1.5 & later also refuses without the certificates it verifies that database against.
    // / Both are read only, & both are where every Debian & Ubuntu package puts them.
    'clamav' => array(
      'OperationType' => 'Scan',
      'ReadOnlyPaths' => array('/var/lib/clamav', '/etc/clamav'),
      'Reason' => 'The signature database & the certificates that verify it are read from these directories.'),

    // / ScanCore ships inside this application, so its directory is handed to the sandbox by
    // / the pipeline as a read only resource rather than named here.
    // / ScanCore is a PHP program, & PHP loads its extensions from the files under /etc/php.
    // / Without them ctype is missing & ScanCore dies loading its classifiers.
    'scancore' => array(
      'OperationType' => 'Scan',
      'ReadOnlyPaths' => array('/etc/php'),
      'Reason' => 'PHP reads the configuration that loads its extensions from this directory.'),

    // / mat2 is a Python program reading what the base sandbox shows, & renders a PDF with the fonts it shows too.
    // / The Metadata pipeline keeps it from starting a sandbox of its own, because this one allows no namespace inside it.
    // / mat2 chooses how to read a file by the type Python names for its extension, & Python names docx, xlsx, pptx, OpenDocument,
    // / EPUB, FLAC, Ogg & WMV extensions, & an EPUB's XHTML parts, only from /etc/mime.types. Without it mat2 says the format of each
    // / is None & makes nothing. Python alone names Opus audio/opus, which mat2 does not read, where this file names it audio/ogg.
    'mat2' => array(
      'OperationType' => 'Document',
      'ReadOnlyPaths' => array('/etc/mime.types'),
      'Reason' => 'Python names the type of docx, xlsx, pptx, OpenDocument, EPUB, FLAC, Ogg, Opus & WMV files from this file.'),

    // / Everything below needs nothing beyond the base sandbox.
    // / A profile that declares only its operation type is not an oversight.
    'ffmpeg' => array('OperationType' => 'Video'),
    'archive' => array('OperationType' => 'Archive'),
    'poppler' => array('OperationType' => 'Document'),
    'generic' => array('OperationType' => 'Generic'));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $SandboxProfiles is not purged, because it is the return value.
  purgeSensitiveMemory($EnableMemoryProtection);
  return $SandboxProfiles; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to hold the configuration utility's understanding of HRConvert2's half of config.php.
// / Accepts no arguments.
// / Returns a multidimensional array of sections, each holding its variables.
// / The Engine reads it because the contract names it in $EngineConfigModelProvider, & joins it to engineConfigModel(),
// / which models the Engine's half. Every setting the kernel or the Engine reads is modelled there.
// /
// / This array changes when HRConvert2's half of the template, Resources/Contract/config-template.php, changes.
// / Update it whenever $ConfigVersion is bumped, & without fail whenever $RequiredConfigVersion is bumped.
// / A variable it does not know is reported as unaccounted & can still be edited by hand, but cannot be reset or repaired.
// /
// / The section names must match config.php exactly, including any typo.
// / A section header in config.php is written as three dashes either side of the name.
// / Security Informations is spelled that way in the file & here, & correcting it here alone would orphan every setting in that section.
// / A section the Engine's half shares is one section, & the Engine's settings come first in it.
// /
// / Every default is read from the template, so a reset restores exactly what a fresh installation would have had.
// /
// / Each variable carries the following.
// /   Type          bool, int, string, array, path, expression or version.
// /   Default       Optional. The value a reset writes, where the template's is not the one to write.
// /   Depends       Another variable this one is meaningless without. Empty when free standing.
// /   Conflict      Optional. What follows when this is on & the variable it depends on is off, for a variable that is not ignored then.
// /   Description   The label config.php gives it. The comment block is shown alongside.
// /
// / A section that is not writable is shown & never touched, because its contents are
// / order dependent & are not administrator tunable.
function applicationConfigModel() {
  // / Set variables.
  global $EnableMemoryProtection;
  $ConfigModel = array();
  $ConfigModel = array(
    // / $ConfigVersion is declared above the first section header, so it belongs to no
    // / section. It is modelled here as read only, so no reset & no edit touches it.
    // / An update's merge takes the new file's stamp, & a repair that completes the file raises it.
    'Unsectioned' => array('Writable' => FALSE, 'Variables' => array(
      'ConfigVersion' => array('Type' => 'version', 'Depends' => '', 'Description' => 'Config Version. Read only. Raised by an update & by a repair.'))),
    'General Information' => array('Writable' => TRUE, 'Variables' => array(
      'SupportedGuis' => array('Type' => 'array', 'Default' => 'array(\'Default\', \'Original\')', 'Depends' => '', 'Description' => 'Supported GUIs'),
      'DefaultGui' => array('Type' => 'string', 'Depends' => '', 'Description' => 'Default GUI'),
      'AllowUserSelectableGui' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Allow User Selectable GUI'),
      'SupportedLanguages' => array('Type' => 'array', 'Default' => 'array( \'en\' => \'English\',   \'fr\' => \'Français\',    \'es\' => \'Español\', \'zh\' => \'中文\',      \'hi\' => \'हिन्दी\',        \'ar\' => \'العربية\', \'ru\' => \'Русский\',   \'uk\' => \'Українська\',  \'bn\' => \'বাংলা\', \'de\' => \'Deutsch\',   \'ko\' => \'한국어\',        \'it\' => \'Italiano\', \'pt\' => \'Português\', \'vi\' => \'Tiếng Việt\',  \'tr\' => \'Türkçe\', \'ja\' => \'日本語\',     \'id\' => \'Bahasa Indonesia\', \'pl\' => \'Polski\',    \'nl\' => \'Nederlands\',  \'sw\' => \'Kiswahili\', \'my\' => \'မြန်မာ\',      \'ur\' => \'اردو\',         \'fa\' => \'فارسی\', \'he\' => \'עברית\',     \'aii\' => \'ܣܘܪܝܝܐ\',     \'arc\' => \'ܐܪܡܝܐ\')', 'Depends' => '', 'Description' => 'Supported Languages'),
      'DefaultLanguage' => array('Type' => 'string', 'Depends' => '', 'Description' => 'Default Language'),
      'AllowUserSelectableLanguage' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Allow User Selectable Language'),
      'AllowUserShare' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'User Shareable File Links'),
      'AllowUserIdentify' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'User File Identification'),
      'SupportedConversionTypes' => array('Type' => 'array', 'Default' => 'array(\'Document\', \'Image\', \'Model\', \'Scad\', \'Drawing\', \'SVG\', \'Video\', \'Subtitle\', \'Audio\', \'Archive\', \'Stream\', \'OCR\', \'Ebook\')', 'Depends' => '', 'Description' => 'Allowed Conversion Types'),
      'AllowBootableIsoImage' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Allow Creation Of Bootable ISO Images'),
      'Font' => array('Type' => 'string', 'Depends' => '', 'Description' => 'UI Element Font'),
      'AllowUserSelectableColor' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Allow User Selectable Colors'),
      'SupportedColors' => array('Type' => 'array', 'Default' => 'array(\'red\', \'green\', \'blue\', \'grey\', \'orange\', \'purple\', \'dark\')', 'Depends' => '', 'Description' => 'Supported Colors'),
      'ButtonStyle' => array('Type' => 'string', 'Depends' => '', 'Description' => 'Button Color'),
      'SpinnerStyle' => array('Type' => 'int', 'Depends' => '', 'Description' => 'Spinner Style'),
      'SpinnerColor' => array('Type' => 'expression', 'Depends' => '', 'Description' => 'Spinner Color'),
      'ShowGUI' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Show Full GUI'),
      'ShowFinePrint' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Show Fine Print'),
      'TOSURL' => array('Type' => 'string', 'Depends' => 'ShowFinePrint', 'Description' => 'Terms of Service URL'),
      'PPURL' => array('Type' => 'string', 'Depends' => 'ShowFinePrint', 'Description' => 'Privacy Policy URL'),
      'RARArchiveMethod' => array('Type' => 'string', 'Depends' => '', 'Description' => 'RAR Archive Method'),
      'GuiMaxWidth' => array('Type' => 'int', 'Depends' => '', 'Description' => 'GUI Maximum Width'),
      'LogoURL' => array('Type' => 'string', 'Depends' => '', 'Description' => 'Logo URL'))),
    'Security Informations' => array('Writable' => TRUE, 'Variables' => array(
      'VirusScan' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Virus Scanning'),
      'AllowUserVirusScan' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'User Virus Scanning'),
      'ScanCoreMemoryLimit' => array('Type' => 'int', 'Depends' => '', 'Description' => 'User Virus Scanning ScanCore Memory Limit'),
      'ScanCoreChunkSize' => array('Type' => 'int', 'Depends' => '', 'Description' => 'User Virus Scanning ScanCore Chunk Size'),
      'ScanCoreDebug' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'User Virus Scanning ScanCore Debug Mode'),
      'ScanCoreVerbose' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'User Virus Scanning ScanCore Enhanced Verbosity'),
      'StreamInspectionLayers' => array('Type' => 'int', 'Depends' => '', 'Description' => 'Stream Inspection Layers'),
      'StreamInspectionFilesPerLayer' => array('Type' => 'int', 'Depends' => 'StreamInspectionLayers', 'Description' => 'Stream Inspection Files Per Layer'),
      'DefaultStreamInspectionForfeitAction' => array('Type' => 'string', 'Depends' => '', 'Description' => 'Default Stream Inspection Forfeit Action'),
      'AllowSCADIncludeResolution' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Allow SCAD Include Resolution'),
      'SCADConversionTimeout' => array('Type' => 'int', 'Depends' => '', 'Description' => 'SCAD Conversion Timeout'),
      'UsePyMeshLab' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Use PyMeshLab Python Bindings'),
      'AllowUserURLDownload' => array('Type' => 'bool', 'Depends' => '', 'Description' => 'Allow User URL Download'),
      'URLDownloadsPerSession' => array('Type' => 'int', 'Depends' => 'AllowUserURLDownload', 'Description' => 'URL Downloads Per Session'),
      'MaxExtractedSize' => array('Type' => 'int', 'Depends' => '', 'Description' => 'Maximum Extracted Size'),
      'MaxExtractedFiles' => array('Type' => 'int', 'Depends' => '', 'Description' => 'Maximum Extracted Files'))),
    // / The format lists ARE modelled & the section stays Writable FALSE.
    // / Those answer different questions. Modelling them lets the utility say whether a
    // / list is present & report the section honestly instead of as an unknown. Writable
    // / FALSE keeps the utility from ever writing one, because a format list is a thing
    // / an administrator curates & a tool that normalizes it would undo that work.
    // / The defaults below are what this application ships. They are here so a generated
    // / configuration can emit a list that is absent, & for nothing else.
    'Supported File Format Information' => array('Writable' => FALSE, 'Variables' => array(
      'UserArchiveArray' => array('Type' => 'array', 'Default' => 'array(\'zip\', \'rar\', \'tar\', \'7z\', \'iso\')', 'Depends' => '', 'Description' => 'Supported Archive Formats'),
      'UserBootableIsoArray' => array('Type' => 'array', 'Default' => 'array(\'iso_mbr-boot\', \'iso_gpt-boot\', \'iso_gpt-boot-x86\', \'iso_gpt-boot-x86-64\', \'iso_gpt-boot-arm32\', \'iso_gpt-boot-arm64\')', 'Depends' => '', 'Description' => 'Supported Bootable ISO Output Formats'),
      'UserDearchiveArray' => array('Type' => 'array', 'Default' => 'array(\'zip\', \'rar\', \'tar\', \'bz\', \'gz\', \'bz2\', \'7z\', \'iso\', \'vhd\', \'vdi\', \'tar.bz2\', \'tar.gz\', \'cbr\', \'cbz\')', 'Depends' => '', 'Description' => 'Supported Dearchive Formats'),
      'UserDocumentArray' => array('Type' => 'array', 'Default' => 'array(\'txt\', \'doc\', \'docx\', \'rtf\', \'odt\', \'pdf\')', 'Depends' => '', 'Description' => 'Supported Document Formats'),
      'UserSpreadsheetArray' => array('Type' => 'array', 'Default' => 'array(\'csv\', \'xls\', \'xlsx\', \'ods\')', 'Depends' => '', 'Description' => 'Supported Spreadsheet Formats'),
      'UserXPSInputArray' => array('Type' => 'array', 'Default' => 'array(\'xps\', \'oxps\')', 'Depends' => '', 'Description' => 'Supported XPS Input Formats'),
      'UserXPSOutputArray' => array('Type' => 'array', 'Default' => 'array(\'pdf\')', 'Depends' => '', 'Description' => 'Supported XPS Output Formats'),
      'UserPresentationInputArray' => array('Type' => 'array', 'Default' => 'array(\'pptx\', \'ppt\', \'potx\', \'potm\', \'pot\', \'ppa\', \'odp\')', 'Depends' => '', 'Description' => 'Supported Presentation Input Formats'),
      'UserPresentationOutputArray' => array('Type' => 'array', 'Default' => 'array(\'pptx\', \'ppt\', \'potx\', \'potm\', \'pot\', \'ppa\', \'odp\', \'pdf\')', 'Depends' => '', 'Description' => 'Supported Presentation Output Formats'),
      'UserImageArray' => array('Type' => 'array', 'Default' => 'array(\'jpeg\', \'jpg\', \'jpe\', \'png\', \'bmp\', \'gif\', \'webp\', \'cin\', \'dds\', \'dib\', \'flif\', \'avif\', \'gplt\', \'sct\', \'xcf\', \'heic\', \'ico\', \'tiff\', \'tif\', \'heif\', \'jp2\', \'j2k\', \'jxl\')', 'Depends' => '', 'Description' => 'Supported Image Formats'),
      'UserImageInputArray' => array('Type' => 'array', 'Default' => 'array(\'jpeg\', \'jpg\', \'png\', \'bmp\', \'gif\', \'webp\', \'dds\', \'avif\', \'heic\', \'ico\', \'tiff\', \'tif\', \'jp2\')', 'Depends' => '', 'Description' => 'Supported Image Input Formats'),
      'UserImageOutputArray' => array('Type' => 'array', 'Default' => 'array(\'jpeg\', \'jpg\', \'png\', \'bmp\', \'gif\', \'webp\', \'dds\', \'avif\', \'ico\', \'tiff\', \'tif\', \'jp2\')', 'Depends' => '', 'Description' => 'Supported Image Output Formats'),
      'UserMediaInputArray' => array('Type' => 'array', 'Default' => 'array(\'sox\', \'spdif\', \'spx\', \'tta\', \'u16be\', \'u16le\', \'u24be\', \'u24le\', \'u32be\', \'u32le\', \'u8\', \'voc\', \'wav\', \'wv\', \'wsaud\', \'mulaw\', \'mxf\', \'mxf_d10\', \'mxf_opatom\', \'oga\', \'ogg\', \'opus\', \'oss\', \'psp\', \'rawvideo\', \'s16be\', \'s16le\', \'s24be\', \'s24le\', \'s32be\', \'s32le\', \'s8\', \'sbc\', \'ilbc\', \'ircam\', \'latm\', \'lrc\', \'mp2\', \'mp3\', \'mlp\', \'flac\', \'g722\', \'g723_1\', \'g726\', \'g726le\', \'gsm\', \'caf\', \'daud\', \'dts\', \'eac3\', \'f32be\', \'f32le\', \'f64be\', \'f64le\', \'ac3\', \'ac4\', \'adts\', \'aiff\', \'alaw\', \'amr\', \'aptx\', \'aptx_hd\', \'argo_asf\', \'argo_cvg\', \'ast\', \'au\', \'a64\', \'aa\', \'aac\', \'aax\', \'acm\', \'act\', \'adp\', \'adx\', \'aea\', \'afc\', \'aix\', \'alp\', \'amrnb\', \'amrwb\', \'apac\', \'apc\', \'ape\', \'apm\', \'argo_asf\', \'binka\', \'bit\', \'boa\', \'bonk\', \'brstm\', \'dfpwm\', \'dsf\', \'dss\', \'epaf\', \'fsb\', \'fwse\', \'g729\', \'hca\', \'idf\', \'kux\', \'kvag\', \'laf\', \'lavfi\', \'loas\', \'luodat\', \'lvf\', \'lxf\', \'mca\', \'mcc\', \'megsts\', \'mlv\', \'mmf\', \'mods\', \'moflex\', \'mpc8\', \'msf\', \'msnwctcp\', \'mtaf\', \'musx\', \'nc\', \'nistsphere\', \'nsp\', \'paf\', \'pam_pipe\', \'pbm_pipe\', \'pfm_pipe\', \'pp_bnk\', \'psxstr\', \'pva\', \'pvf\', \'qcp\', \'rka\', \'rl2\', \'rpl\', \'rso\', \'s337m\', \'sap\', \'sbg\', \'scd\', \'sdns\', \'sdp\', \'sds\', \'sdx\', \'siff\', \'simbiosis_imx\', \'sln\', \'smk\', \'smush\', \'sol\', \'svag\', \'svs\', \'tak\', \'thp\', \'tierexseq\', \'tty\', \'ty\', \'usm\', \'vag\', \'vidc\', \'vpk\', \'vqf\', \'w64\', \'wady\', \'wavarc\', \'wsd\', \'wsvqa\', \'wve\', \'xa\', \'xbin\', \'xbm_pipe\', \'xmd\', \'xpm_pipe\', \'xwma\', \'yop\', \'wma\', \'m4a\')', 'Depends' => '', 'Description' => 'Supported Audio Input Formats'),
      'UserMediaOutputArray' => array('Type' => 'array', 'Default' => 'array(\'mp3\', \'aac\', \'ogg\', \'wma\', \'mp2\', \'flac\', \'m4a\')', 'Depends' => '', 'Description' => 'Supported Audio Output Formats'),
      'UserVideoInputArray' => array('Type' => 'array', 'Default' => 'array(\'smoothstreaming\', \'svcd\', \'swf\', \'truehd\', \'vc1\', \'vc1test\', \'vcd\', \'vob\', \'vvc\', \'webm\', \'yuv4mpegpipe\', \'mpjpeg\', \'mxf\', \'mxf_d10\', \'mxf_opatom\', \'nut\', \'obu\', \'ogv\', \'psp\', \'rawvideo\', \'rm\', \'roq\', \'rtp_mpegts\', \'smjpeg\', \'hevc\', \'hls\', \'image2\', \'image2pipe\', \'ipod\', \'ismv\', \'m4v\', \'matroska\', \'mjpeg\', \'mkvtimestamp_v2\', \'mov\', \'mp4\', \'mpeg\', \'mpeg1video\', \'mpeg2video\', \'mpegts\', \'mpegtsraw\', \'mpegvideo\', \'fbdev\', \'film_cpk\', \'filmstrip\', \'gxf\', \'h261\', \'h263\', \'h264\', \'hds\', \'avs2\', \'avs3\', \'cavsvideo\', \'cavs\', \'dirac\', \'dnxhd\', \'dv\', \'dvd\', \'evc\', \'3g2\', \'3gp\', \'apng\', \'argo_asf\', \'argo_cvg\', \'asf\', \'asf_stream\', \'avi\', \'avif\', \'avm2\', \'3dostr\', \'4xm\', \'adf\', \'ads\', \'alias_pix\', \'anm\', \'argo_brp\', \'asf_o\', \'av1\', \'avs\', \'bethsoftvid\', \'bfi\', \'bink\', \'bmv\', \'brender_pix\', \'brender\', \'cdg\', \'cdxl\', \'cine\', \'concat\', \'cri\', \'dcstr\', \'derf\', \'dfa\', \'dhav\', \'dsicin\', \'dtshd\', \'dxa\', \'ea\', \'exr\', \'fits\', \'flic\', \'frm\', \'gdv\', \'genh\', \'gif\', \'idcin\', \'iff\', \'ifv\', \'ingenient\', \'ipmovie\', \'iss\', \'iv8\', \'ivf\', \'ivr\', \'j2k\', \'jp2\', \'jv\', \'live_flv\', \'lmlm4\', \'mtv\', \'mv\', \'mvi\', \'mxg\', \'nsv\', \'nuv\', \'osq\', \'pcx_pipe\', \'pdv\', \'pgm_pipe\', \'pgmuv_pipe\', \'pgx_pipe\', \'phm_pipe\', \'protocol_pipe\', \'pictor_pipe\', \'png_pipe\', \'ppm_pipe\', \'psd_pipe\', \'qdraw_pipe\', \'qoi_pipe\', \'r3d\', \'redspark\', \'rroq\', \'rsd\', \'rtsp\', \'sdr2\', \'ser\', \'sga\', \'sgi_pipe\', \'shn\', \'sunrast_pipe\', \'svg_pipe\', \'tiff_pipe\', \'tmv\', \'v210\', \'v210x\', \'vbn_pipe\', \'video4linux2\', \'v4l2\', \'vividas\', \'vivo\', \'vmd\', \'wc3movie\', \'webm_dash_manifest\', \'webp_pipe\', \'wtv\', \'xmv\', \'xvag\', \'xwd_pipe\', \'mkv\', \'wmv\')', 'Depends' => '', 'Description' => 'Supported Video Input Formats'),
      'UserVideoOutputArray' => array('Type' => 'array', 'Default' => 'array(\'3gp\', \'mkv\', \'avi\', \'mp4\', \'mpeg\', \'wmv\', \'mov\', \'m4v\')', 'Depends' => '', 'Description' => 'Supported Video Output Formats'),
      'UserStreamArray' => array('Type' => 'array', 'Default' => 'array(\'m3u8\', \'ts\')', 'Depends' => '', 'Description' => 'Supported Stream Formats'),
      'UserDrawingArray' => array('Type' => 'array', 'Default' => 'array(\'dxf\', \'vdx\', \'fig\', \'dia\', \'wpg\')', 'Depends' => '', 'Description' => 'Supported Drawing Formats'),
      'UserDrawingInputArray' => array('Type' => 'array', 'Default' => 'array(\'dxf\', \'vdx\', \'fig\', \'dia\', \'wpg\')', 'Depends' => '', 'Description' => 'Supported Drawing Input Formats'),
      'UserDrawingOutputArray' => array('Type' => 'array', 'Default' => 'array(\'dxf\', \'vdx\', \'fig\', \'dia\', \'wpg\')', 'Depends' => '', 'Description' => 'Supported Drawing Output Formats'),
      'UserSVGInputArray' => array('Type' => 'array', 'Default' => 'array(\'svg\', \'plain-svg\')', 'Depends' => '', 'Description' => 'Supported SVG Input Formats'),
      'UserSVGOutputArray' => array('Type' => 'array', 'Default' => 'array(\'png\', \'pdf\', \'ps\', \'eps\', \'emf\', \'wmf\')', 'Depends' => '', 'Description' => 'Supported SVG Output Formats'),
      'UserModelArray' => array('Type' => 'array', 'Default' => 'array(\'stl\', \'ply\', \'off\', \'3ds\', \'fbx\', \'dae\', \'gltf\', \'glb\', \'obj\', \'3mf\', \'x3d\', \'dxf\', \'x\', \'ctm\')', 'Depends' => '', 'Description' => 'Supported Model Formats'),
      'UserModelInputArray' => array('Type' => 'array', 'Default' => 'array(\'stl\', \'ply\', \'off\', \'3ds\', \'fbx\', \'dae\', \'gltf\', \'glb\', \'obj\', \'3mf\', \'x3d\', \'dxf\', \'x\', \'ctm\')', 'Depends' => '', 'Description' => 'Supported Model Input Formats'),
      'UserModelOutputArray' => array('Type' => 'array', 'Default' => 'array(\'stl\', \'ply\', \'off\', \'3ds\', \'fbx\', \'dae\', \'gltf\', \'glb\', \'obj\', \'3mf\', \'x3d\', \'dxf\', \'x\', \'ctm\', \'wrl\', \'assbin\', \'json\')', 'Depends' => '', 'Description' => 'Supported Model Output Formats'),
      'UserSCADArray' => array('Type' => 'array', 'Default' => 'array(\'scad\', \'stl\', \'off\', \'amf\', \'3mf\', \'csg\')', 'Depends' => '', 'Description' => 'Supported OpenSCAD Formats'),
      'UserSCADInputArray' => array('Type' => 'array', 'Default' => 'array(\'scad\')', 'Depends' => '', 'Description' => 'Supported SCAD Input Formats'),
      'UserSCADOutputArray' => array('Type' => 'array', 'Default' => 'array(\'stl\', \'off\', \'amf\', \'3mf\', \'csg\')', 'Depends' => '', 'Description' => 'Supported SCAD Output Formats'),
      'UserSubtitleInputArray' => array('Type' => 'array', 'Default' => 'array(\'sub\', \'sbv\', \'srt\', \'stream_segment\', \'ssegment\', \'streamhash\', \'sup\', \'subtitles\', \'ttml\', \'uncodedframecrc\', \'webvtt\', \'wtv\', \'oma\', \'rso\', \'rtp\', \'rtsp\', \'scc\', \'sdl\', \'sdl2\', \'segment\', \'sap\', \'jacosub\', \'kvag\', \'microdvd\', \'ffmetadata\', \'fifo\', \'fifo_test\', \'fits\', \'framecrc\', \'framehash\', \'framemd5\', \'dash\', \'crc\', \'dvbsub\', \'dvbtxt\', \'gsm\', \'ass\', \'vobsub\', \'mpl2\', \'mpsub\', \'pjs\', \'realtext\', \'sami\', \'stl\', \'subviewer\', \'subviewer1\', \'tedcaptions\', \'txd\', \'vtt\', \'ssa\', \'dvb\', \'vplayer\')', 'Depends' => '', 'Description' => 'Supported Subtitle Input Formats'),
      'UserSubtitleOutputArray' => array('Type' => 'array', 'Default' => 'array(\'vtt\', \'ssa\', \'ass\', \'srt\')', 'Depends' => '', 'Description' => 'Supported Subtitle Output Formats'),
      'UserPDFWorkArr' => array('Type' => 'array', 'Default' => 'array(\'pdf\', \'jpg\', \'jpeg\', \'png\', \'bmp\', \'webp\', \'gif\')', 'Depends' => '', 'Description' => 'Supported OCR Formats'),
      'UserEbookInputArray' => array('Type' => 'array', 'Default' => 'array(\'epub\', \'mobi\', \'azw\', \'azw3\', \'azw4\', \'fb2\', \'fbz\', \'lit\', \'lrf\', \'pdb\', \'pml\', \'rb\', \'snb\', \'tcr\', \'txt\', \'txtz\', \'rtf\', \'odt\', \'docx\', \'pdf\', \'chm\', \'cbz\', \'cbr\', \'cbc\', \'prc\', \'opf\', \'recipe\')', 'Depends' => '', 'Description' => 'Supported Ebook Input Formats'),
      'UserEbookOutputArray' => array('Type' => 'array', 'Default' => 'array(\'epub\', \'mobi\', \'azw3\', \'fb2\', \'lit\', \'lrf\', \'pdb\', \'pmlz\', \'rb\', \'snb\', \'tcr\', \'txt\', \'txtz\', \'rtf\', \'docx\', \'pdf\')', 'Depends' => '', 'Description' => 'Supported Ebook Output Formats'))));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection);
  return $ConfigModel; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to scan a path & act on the verdict.
// / Accepts the path. Returns whether the scan ran & whether a threat was found.
// /
// / It dispatches to a scanner pipeline & does not know which scanner ran.
// / A scanner is chosen by name through $DefaultVirusScanner, so an installation can run ScanCore where ClamAV is not wanted.
// /
// / The scanner reports & the caller decides.
// / A pipeline never deletes anything, because removing an infected file is a policy this application holds & another application may not want.
// /
// / Nothing the scanner said is repeated, to the user or to the log.
// / The findings are read for a verdict & discarded, because a signature name comes from a database this application does not control.
function virusScan($path) {
  // / Set variables.
  global $AllowUserVirusScan, $DefaultVirusScanner, $Verbose, $EnableMemoryProtection;
  $ScanComplete = FALSE;
  $VirusFound = FALSE;
  $scanFindings = array();
  $scannerUsed = '';
  if (!function_exists('runVirusScan')) errorEntry('The Pipeline Core is unavailable, so '.$path.' was not scanned!', 506, FALSE);
  else {
    list ($ScanComplete, $VirusFound, $scanFindings, $scannerUsed) = runVirusScan(array($path), isset($DefaultVirusScanner) ? (string)$DefaultVirusScanner : '');
    if (!$ScanComplete) errorEntry('A virus scan could not be completed for '.$path.'!', 505, FALSE);
    else if ($Verbose) logEntry('Scanned '.$path.' with '.($scannerUsed !== '' ? $scannerUsed : 'the default scanner').'. Findings: '.count($scanFindings).'.');
    if ($VirusFound) {
      // / The file goes unless the operator asked to keep it for their own inspection.
      if (!$AllowUserVirusScan && is_file($path) && !is_dir($path)) @unlink($path);
      errorEntry('There were potentially infected files detected at '.$path.'!', 500, FALSE); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $scanFindings, $scannerUsed, $path);
  return array($ScanComplete, $VirusFound); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert a file based on a pre-determined input type & return the results.
// / Streams are the only conversion that keeps running after the user has been served.
// / The stream supervision variables are globals so the core can reach them after this returns.
// / Every converter returns the output path & the extension it actually produced, because a
// / bootable disk image rewrites both. Everything else hands back what it was given.
// / $UserFilename is initialized here rather than only inside the Archive branch.
// / An undefined return value is a warning & a warning corrupts an AJAX response.
function convert($type, $pathname, $newPathname, $extension, $height, $width, $rotate, $bitrate) {
  // / Set variables.
  global $SupportedConversionTypes, $PipelineCoreActive, $WaitForStream, $StreamPID, $StreamOutputPath, $Verbose, $EnableMemoryProtection;
  $ConversionSuccess = $ConversionErrors = FALSE;
  $UserFilename = basename($newPathname);
  $WorkerPID = 0;
  $pipelineServesThisConversion = FALSE;
  $inputExtension = getExtension($pathname);
  // / Check that the required conversion type is allowed.
  if (in_array($type, $SupportedConversionTypes)) {
    // / Try the pipeline manager first, then fall back to the built in routing.
    // / A subsystem that can fall back must fall back before it errors. The routing below
    // / is the local fallback that configuration cannot overwrite, so a missing, unreadable
    // / or version mismatched Pipeline Core costs an installation nothing but a warning.
    // /
    // / The question is whether a pipeline serves this conversion, not whether the manager
    // / LOADED. Those are different questions & asking the wrong one is how a working
    // / installation loses a conversion it has always been able to perform.
    // / A manager that verified an Ebook pipeline is ACTIVE, & it has nothing whatsoever to
    // / say about txt to rtf. Handing that conversion to it anyway produced a warning about
    // / no Document pipeline claiming the pair, followed by error 21, on an installation
    // / whose built in Document converter was sitting right there the entire time.
    // /
    // / So the family, the input extension & the output extension are ALL asked about
    // / before dispatch. A family that is not served falls through to the routing below &
    // / converts exactly as it did before this component existed.
    if ($PipelineCoreActive && function_exists('familyHasPipeline')) $pipelineServesThisConversion = familyHasPipeline($type, $inputExtension, $extension);
    if ($pipelineServesThisConversion) list ($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $UserFilename, $WorkerPID) = runConversion($type, $pathname, $newPathname, $extension, $height, $width, $rotate, $bitrate);
    // / Every conversion family has a pipeline, & this file converts nothing on its own.
    // / There is no built in dispatcher to fall back to, so a family with no installed pipeline is reported here rather than failing silently.
    // / Restoring a converter to this file is not the fix for this error.
    // / Repair the pipeline folder, & run with -v to read the warnings above this line, which name the folder that was refused & the reason.
    // / A request for the format a file already has is refused for what it is, because no pipeline claims one.
    else if (strtolower((string)$inputExtension) === strtolower((string)$extension)) {
      $ConversionErrors = TRUE;
      errorEntry('The file is already '.strtolower((string)$extension).', so there is nothing to convert!', 34009, FALSE); }
    else {
      $ConversionErrors = TRUE;
      errorEntry('The '.$type.' conversion family has no installed pipeline!', 34005, FALSE); }
    // / A non zero worker PID means the core must stay alive after serving the user.
    // / Any pipeline whose dependency outlives the request reports its process here, & is supervised for up to $StreamWatchTimeout minutes by the code that supervises FFMPEG.
    // / These are set inside this branch, so a conversion of any other type never overwrites them.
    if ($WorkerPID > 0) {
      $WaitForStream = TRUE;
      $StreamPID = $WorkerPID;
      $StreamOutputPath = $newPathname; } }
  // / An entry point that returned nothing usable in the filename slot still has to hand the
  // / core a name, because an undefined value becomes a warning inside an AJAX response.
  if (!is_string($UserFilename) or $UserFilename === '') $UserFilename = basename($newPathname);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $pipelineServesThisConversion, $inputExtension, $WorkerPID, $type, $pathname, $height, $width, $rotate, $bitrate);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $UserFilename); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to find a name for an output that nothing in its folder already has.
// / Accepts the folder, the name the output was asked for without its extension, & the extension.
// / A taken name is given _1 before its extension, then _2, & so on.
// / The suffix is an underscore & a number, because sanitize() turns a space & brackets into exactly that.
// / Returns whether a free name was found, the free path & the free name, in that order.
function findFreeOutputName($directory, $baseName, $extension) {
  // / Set variables.
  global $EnableMemoryProtection;
  $NameWasFound = FALSE;
  $FreePathname = $FreeName = $candidateName = $candidatePathname = '';
  $suffixNumber = 0;
  // / A session never holds this many copies of one name, so reaching the limit means something is wrong.
  $suffixLimit = 1000;
  for ($suffixNumber = 1; $suffixNumber <= $suffixLimit && !$NameWasFound; $suffixNumber++) {
    // / Each candidate replaces the one before it through redeclare(), so a name that was taken is shredded rather than left behind.
    // / sanitizeString() is what sanitize() runs on one string, & it returns the string alone, which redeclare() can take.
    redeclare($candidateName, sanitizeString($baseName.'_'.$suffixNumber.'.'.$extension, TRUE));
    redeclare($candidatePathname, sanitizeString($directory.$candidateName, FALSE));
    clearstatcache(TRUE, $candidatePathname);
    // / A link is a taken name even when it resolves nowhere, because writing to it would follow it.
    if (!file_exists($candidatePathname) && !is_link($candidatePathname)) {
      $NameWasFound = TRUE;
      $FreeName = $candidateName;
      $FreePathname = $candidatePathname; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $directory, $baseName, $extension, $candidateName, $candidatePathname, $suffixNumber, $suffixLimit);
  return array($NameWasFound, $FreePathname, $FreeName); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to put a file an operation made into the session, under the name given or the next free one.
// / Accepts the file's path & the name to give it, in that order.
// / Returns whether it was placed, the name it was placed under & whether its name or its format was refused, in that order.
// / A name is held to what an upload's is. It is sanitized, & a name or a format this application refuses to show or to handle is refused here too.
// / A name already taken is given _1 before its extension, then _2, & so on, by findFreeOutputName(), as an output is.
// / Nothing in the session is ever written over. A name is taken by linking the file to it, which fails rather than replaces when the name was taken in the meantime.
// / A filesystem that cannot link falls back to a rename, once the name is found to be free.
// / The Extractor, Metadata & PDF pipelines each place what they make through it, from a folder of their own inside the session.
// / A placed file is given the time it was placed, from v4.2.3, as an upload is. A file an archive held keeps the time the archive gave it,
// / so a signed in user's lifetime, which counts from a file's time, would have removed a file extracted a moment before.
function placeSessionFile($sourcePath, $fileName) {
  // / Set variables.
  global $ConvertDir, $Allowed, $DangerousFiles, $PermissionLevels, $EnableMemoryProtection;
  $FileWasPlaced = $NameWasRefused = FALSE;
  $PlacedName = '';
  $cleanName = $fileExtension = $baseName = $candidateName = $candidatePath = $freePath = '';
  $nameIsSanitized = $nameWasFound = FALSE;
  $attemptNumber = 0;
  list ($cleanName, $nameIsSanitized) = sanitize((string)$fileName, TRUE);
  if ($nameIsSanitized && is_string($cleanName)) $fileExtension = getExtension($cleanName);
  if (!$nameIsSanitized or !is_string($cleanName) or $cleanName === '' or $cleanName === '.' or $cleanName === '..' or strtolower($cleanName) === 'index.html' or in_array(strtolower($cleanName), (array)$DangerousFiles) or extensionIsDangerous($fileExtension) or !in_array($fileExtension, (array)$Allowed)) $NameWasRefused = TRUE;
  else {
    $baseName = (string)pathinfo($cleanName, PATHINFO_FILENAME);
    $candidateName = $cleanName;
    for ($attemptNumber = 0; $attemptNumber < 3 && !$FileWasPlaced; $attemptNumber++) {
      redeclare($candidatePath, $ConvertDir.$candidateName);
      clearstatcache(TRUE, $candidatePath);
      // / A name that is taken, or that a link holds, is passed over for the next free one.
      if (file_exists($candidatePath) or is_link($candidatePath)) {
        // / The name before it is shredded first, as redeclare() would, because a list() cannot pass through redeclare().
        purgeSensitiveMemory($EnableMemoryProtection, $candidateName, $freePath);
        list ($nameWasFound, $freePath, $candidateName) = findFreeOutputName($ConvertDir, $baseName, $fileExtension);
        if (!$nameWasFound) break;
        redeclare($candidatePath, $freePath); }
      if (@link((string)$sourcePath, $candidatePath)) {
        @unlink((string)$sourcePath);
        $FileWasPlaced = TRUE; }
      else {
        clearstatcache(TRUE, $candidatePath);
        if (!file_exists($candidatePath) && !is_link($candidatePath) && @rename((string)$sourcePath, $candidatePath)) $FileWasPlaced = TRUE; } }
    if ($FileWasPlaced) {
      @chmod($candidatePath, $PermissionLevels);
      @touch($candidatePath);
      $PlacedName = $candidateName; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cleanName, $fileExtension, $baseName, $candidateName, $candidatePath, $freePath, $nameIsSanitized, $nameWasFound, $attemptNumber, $sourcePath, $fileName);
  return array($FileWasPlaced, $PlacedName, $NameWasRefused); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to verify files before performing operations on them.
// / Accepts the file, the output name & extension the user asked for, & three switches.
// / $clean removes a stale staged copy & gives an output whose name is taken the next free one.
// / $copy stages the file in the temporary location & $skip tolerates a file that was not staged.
// / A caller that adds to an output it already named passes $clean as FALSE, so the name stays.
// / Returns a verification boolean, the staged path, the original path, the original
// / extension, the output path & the output name, in that order.
// / A file this refuses is skipped rather than halted on. Every caller reports a file that
// / failed verification & moves on, so a bad name costs that file & nothing else.
function verifyFile($file, $UserFilename, $UserExtension, $clean, $copy, $skip) {
  // / Set variables.
  global $ConvertDir, $ConvertTempDir, $Allowed, $Verbose, $EnableMemoryProtection;
  $FileIsVerified = $Pathname = $OldPathname = $NewPathname = $variableIsSanitized = $freeNameWasFound = FALSE;
  $outputBaseName = $requestedName = $requestedPathname = $freePathname = $freeName = '';
  // / If the $UserFilename is blank then use the original filename instead.
  $OldExtension = getExtension($file);
  if ($UserFilename === '') $UserFilename = trim(str_replace('.'.$OldExtension, '', $file), '.');
  // / Check to make sure all iteration specific required variables are properly sanitized.
  list ($file, $variableIsSanitized) = sanitize($file, FALSE);
  list ($Pathname, $variableIsSanitized) = sanitize($ConvertTempDir.$file, FALSE);
  list ($OldPathname, $variableIsSanitized) = sanitize($ConvertDir.$file, FALSE);
  // / Check if the selected file is safe to handle.
  if (in_array(strtolower($UserExtension), $Allowed) or $UserExtension == '') if (in_array(strtolower($OldExtension), $Allowed) && !extensionIsDangerous($OldExtension) && $file !== '.' && $file !== '..' && $file !== 'index.html') $FileIsVerified = TRUE;
  // / A file refused here is a request this application handled correctly, so it is a
  // / warning rather than error 14000, which halted every request that carried one.
  if (!$FileIsVerified) warningEntry('The file '.$file.' failed first stage validation & was refused.');
  if ($FileIsVerified) {
    if ($Verbose && file_exists($Pathname) && $clean) logEntry('Deleting stale file '.$Pathname.'.');
    // / Remove the temp file if one already exists.
    if (file_exists($Pathname) && $clean) @unlink($Pathname);
    // / Check to make sure that the stale file was deleted if required or creating a new one will cause problems.
    if (file_exists($Pathname) && $clean) errorEntry('Could not delete stale file '.$Pathname.'!', 14001, TRUE);
    if ($Verbose && file_exists($OldPathname) && $copy) logEntry('Copying file '.$file.' to '.$Pathname.'.');
    // / Copy the file to the working directory.
    if (file_exists($OldPathname) && $copy) @copy($OldPathname, $Pathname);
    // / Check to make sure the temporary file was created.
    if (!$skip) if (!file_exists($Pathname)) errorEntry('The file '.$Pathname.' failed second stage validation!', 14002, TRUE);
    if (file_exists($Pathname)) if ($Verbose && $copy) logEntry('Copied file '.$file.'.');
    // / If the $UserFilename & $UserExtension variables are valid we can prepare for a $NewPathname.
    if ($UserFilename && $UserExtension) {
      $outputBaseName = $UserFilename;
      list ($requestedName, $variableIsSanitized) = sanitize($UserFilename.'.'.$UserExtension, TRUE);
      // / Define the path the output asked for.
      list ($requestedPathname, $variableIsSanitized) = sanitize($ConvertDir.$requestedName, FALSE);
      // / Make sure the output is not a dangerous file.
      if (extensionIsDangerous($UserExtension) or !in_array(strtolower($UserExtension), $Allowed)) errorEntry('The file '.$file.' failed third stage validation!', 14003, TRUE);
      // / An output whose name is taken is given the next free one, so nothing a user already has is replaced.
      // / A converter skips an output it finds, so a taken name would otherwise hand the user the old file unchanged.
      // / The original holds its own name, so this also keeps an output from ever being the file it was made from.
      clearstatcache(TRUE, $requestedPathname);
      if ($clean && (file_exists($requestedPathname) or is_link($requestedPathname))) {
        list ($freeNameWasFound, $freePathname, $freeName) = findFreeOutputName($ConvertDir, $outputBaseName, $UserExtension);
        if ($freeNameWasFound && $Verbose) logEntry('The name '.$requestedName.' is taken, so the output will be '.$freeName.'.');
        if (!$freeNameWasFound) {
          $FileIsVerified = FALSE;
          errorEntry('No free name was found for the output of '.$file.', so it was refused!', 14006, FALSE); } }
      // / The name asked for & the free one are held under names of their own, so the output is named once.
      $NewPathname = ($freeNameWasFound ? $freePathname : $requestedPathname);
      $UserFilename = ($freeNameWasFound ? $freeName : $requestedName);
      // / An output that IS the original is refused, because writing it would write over the only copy.
      // / A caller adding to an output it already named keeps that name, so this is still checked.
      if ($FileIsVerified && $NewPathname === $OldPathname) {
        $FileIsVerified = FALSE;
        errorEntry('The output for '.$file.' would replace the original, so it was refused!', 14005, FALSE); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $clean, $copy, $skip, $file, $variableIsSanitized, $outputBaseName, $requestedName, $requestedPathname, $freeNameWasFound, $freePathname, $freeName);
  return array($FileIsVerified, $Pathname, $OldPathname, $OldExtension, $NewPathname, $UserFilename); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to build & display the GUI.
// / The variables declared here will be usable in GUI elements.
// / Files like header, footer, styleCore, convertGui1 & convertGui2 have access to them.
// / Every language string is a function local rather than a global, because the language
// / pack is required from inside this function. Nothing outside this call can read them.
function buildGUI($guiType, $ShowGUI, $ButtonCode) {
  // / Set variables.
  global $GuiFiles, $LanguageFiles, $LanguageStringsFile, $LanguageBaselineFile, $GuiHeaderFile, $GuiFooterFile, $GuiUI1File, $GuiUI2File, $CoreLoaded, $ConvertDir, $ConvertTempDir, $Token1, $Token2, $SesHash, $SesHash2, $SesHash3, $SesHash4, $Date, $Time, $TOSURL, $PPURL, $ShowFinePrint, $PDFWorkArr, $ArchiveArray, $DearchiveArray, $DocumentArray, $SpreadsheetArray, $ImageArray, $ImageOutputArray, $ModelArray, $DrawingArray, $VideoInputArray, $VideoOutputArray, $SubtitleInputArray, $SubtitleOutputArray, $StreamArray, $MediaInputArray, $MediaOutputArray, $PresentationInputArray, $PresentationOutputArray, $XPSInputArray, $XPSOutputArray, $ConvertGuiCounter1, $ConsolidatedLogFileName, $Alert, $Alert1, $Alert2, $Alert3, $FCPlural, $FCPlural1, $FCPlural2, $FCPlural3, $File, $Files, $FileCount, $SpinnerStyle, $SpinnerColor, $PacmanLoc, $Allowed, $AllowUserVirusScan, $AllowUserShare, $SupportedConversionTypes, $FullURL, $LanguageDir, $FaviconPath, $DropzonePath, $DropzoneStylesheetPath, $StylesheetPath, $JsLibraryPath, $WorkflowsJsPath, $JqueryPath, $GUIDirection, $SupportedFormatCount, $GUIAlignment, $HeaderDisplayed, $UIDisplayed, $FooterDisplayed, $LanguageStringsLoaded, $GUIDisplayed, $GuiResourcesDir, $GuiImageDir, $GuiCSSDir, $GuiJSDir, $StreamOutputArray, $SCADArray, $SCADOutputArray, $AllowUserSelectableColor, $AllowUserSelectableGui, $AllowUserSelectableLanguage, $SupportedColors, $SupportedGuis, $SupportedLanguages, $ColorToUse, $GuiToUse, $LanguageToUse, $GuiDir, $SVGInputArray, $SVGOutputArray, $LanguageFlagFile, $LanguageVersion, $RequiredLanguageVersion, $DefaultLanguage, $BootableIsoArray, $AllowBootableIsoImage, $EbookInputArray, $EbookOutputArray, $EnableMemoryProtection, $NoGui, $ShowFiles, $FileListOnly, $Verbose, $AllowUserURLDownload, $GuiMaxWidth, $GuiWidth, $LogoURL, $ResolvedLogoURL, $AllowUserIdentify, $SortToUse, $FileDetails, $EnableWorkflows, $EnableWorkflowBuilder, $EnableWorkflowImportExport, $GuiEngineFile, $GuiTaskbarFile, $EngineAnswer, $EnginePageState;
  $GUIDisplayed = FALSE;
  $guiUIFile = $GuiUI1File;
  $Files = $FileDetails = $ArchiveOperationArray = $ExtractOperationArray = $MetadataOperationArray = $OfferedOperations = $OfferedConversionTypes = $OfferedScanners = array();
  $FileCount = 0;
  // / Make sure the $guiType is valid.
  // / A non numeric type cannot be compared against a range, so it is replaced outright.
  // / Only two GUI types exist, so anything outside that range is clamped into it.
  if (!is_numeric($guiType)) $guiType = 1;
  $guiType = (int)$guiType;
  if ($guiType < 1) $guiType = 1;
  if ($guiType > 2) $guiType = 2;
  // / Determine which loading indicator to use.
  $PacmanLoc = $GuiImageDir.'pacman'.$SpinnerStyle.strtolower($SpinnerColor).'.gif';
  if (!file_exists($PacmanLoc)) $PacmanLoc = $GuiImageDir.'pacman1grey.gif';
  // / Gather a list of files.
  // / Both interfaces get the file list, not only the second.
  // / CREATING_GUIS.txt documents $Files & $FileCount as available to a GUI & does not
  // / qualify which one, so populating them for only one interface contradicted the
  // / contract every interface is written against. An upload page that cannot see whether
  // / the session already holds files cannot offer to clear them, & cannot tell a first
  // / visit apart from a return visit.
  // / getFiles() is a filtered scandir with no side effects, so this costs the upload page
  // / one directory listing & nothing else.
  // / The files arrive in the order the user chose, each with its size & the time it changed.
  list ($Files, $FileDetails) = arrangeFiles($ConvertDir, getFiles($ConvertDir), $SortToUse);
  $FileCount = count($Files);
  // / The formats a file or a selection may be archived in, which an interface offers instead of $ArchiveArray.
  // / $ArchiveArray holds the bootable disk images the Archive pipeline converts an archive into, & archiving a file makes none of them.
  $ArchiveOperationArray = archiveOperationFormats();
  // / The formats an archive may be extracted from, which an interface draws a file's Extract button for.
  $ExtractOperationArray = extractOperationFormats();
  // / The formats metadata may be stripped from, which an interface draws a file's Strip Metadata button for.
  $MetadataOperationArray = metadataOperationFormats();
  // / The conversion types an interface offers, which are --Supported Conversion Types-- less any this server cannot run, from v4.2.1.
  // / An interface draws a conversion from these, never from $SupportedConversionTypes, which still decides what may be uploaded.
  $OfferedConversionTypes = offeredConversionTypes();
  // / The scanners a file's Scan panel draws a button for, as clamav & scancore.
  $OfferedScanners = offeredScanners();
  // / Which of a file's operations an interface offers. Each is an operation pipeline's, & one whose pipeline is missing is not offered, as a workflow does not offer it.
  // / Nor is one whose tools are not installed, from v4.2.1, so Strip Metadata is not drawn where MAT2 is not installed.
  // / A button that could only fail is not drawn, & -v names the pipeline that is missing & the tool it cannot run without.
  // / Rename is the Files pipeline's, & Merge & Split are the PDF pipeline's, each drawn only for a PDF.
  $OfferedOperations = array('Scan' => ($AllowUserVirusScan === TRUE && operationPipelineIsOffered('Scan') && count($OfferedScanners) > 0), 'Identify' => ($AllowUserIdentify === TRUE && operationPipelineIsOffered('Identify')),
    'OCR' => (in_array('OCR', $OfferedConversionTypes, TRUE) && operationPipelineIsOffered('OCR')), 'Archive' => (count($ArchiveOperationArray) > 0),
    'Extract' => (in_array('Archive', (array)$SupportedConversionTypes, TRUE) && count($ExtractOperationArray) > 0),
    'Rename' => filesPipelineIsInstalled(), 'StripMetadata' => (count($MetadataOperationArray) > 0), 'Merge' => operationPipelineIsOffered('PDF'), 'Split' => operationPipelineIsOffered('PDF'));
  // / An interface that renders an empty list has either been handed the wrong directory
  // / or a directory with nothing in it, & those are different faults with different
  // / fixes. Saying which directory was read, & whether this was the framed request, is
  // / the difference between diagnosing it & guessing at it.
  if ($Verbose) logEntry('GUI '.$guiType.' file list: Directory: '.$ConvertDir.', Files: '.$FileCount.', List only: '.($FileListOnly ? 'YES' : 'NO').', Session: '.$SesHash.'/'.$SesHash2.'.');
  // / The baseline loads first, & the selected pack loads over the top.
  // / A language pack is nothing but variable assignments, so every string ends set to the later pack's value, or to the baseline's where the later pack does not mention it.
  // / That is the whole fallback, & it is per string rather than per pack.
  // / An incomplete pack shows the strings it has in the user's language, & no interface can print an undefined variable, whatever a pack omits.
  if ($LanguageBaselineFile !== '' && file_exists($LanguageBaselineFile)) require_once($LanguageBaselineFile);
  // / Load language specific GUI elements, if there are any.
  if (in_array($LanguageStringsFile, $LanguageFiles)) require_once($LanguageStringsFile);
  // / A version mismatch is a warning, not a refusal.
  // / The version says whether a pack was written against this core, & an administrator should know when it was not.
  // / It does not decide whether the page can be built, because the baseline underneath guarantees every string the interface reads has a value.
  // / A user who cannot read the page cannot report the problem. A page in the wrong
  // / language beats no page at all, & a page that is right except for one new button beats
  // / both.
  if ($LanguageVersion !== $RequiredLanguageVersion) warningEntry('Language pack '.$LanguageToUse.' reports version '.$LanguageVersion.' but this core requires '.$RequiredLanguageVersion.'. Any string it does not define is being taken from '.($LanguageBaselineFile === '' ? 'nowhere, because no baseline pack was available' : $LanguageBaselineFile).'.');
  // / Build the GUI as long as a pack actually loaded. A pack sets this flag itself, so it
  // / is TRUE only if one of the two requires above really ran.
  if ($LanguageStringsLoaded) {
    // / A fragment request gets a fragment. No header & no footer.
    // / --File List Only-- asks for a piece of a page that is about to be placed INSIDE a
    // / page that already exists. Answering it with a whole document sends a second copy of
    // / everything the header carries, which is every script this interface defines, & a
    // / second copy of the footer, which is why the privacy notice appeared twice.
    // / Worse, the duplicated script redefined the helper & re-ran its ready handler, which
    // / fetched the fragment again, which redefined the helper again. That is a request
    // / loop several times a second, & it destroyed every click handler on each pass, which
    // / is why nothing in the list could be clicked.
    // / A fragment is not a document & must not be dressed as one.
    if (!$FileListOnly && in_array($GuiHeaderFile, $GuiFiles)) require_once($GuiHeaderFile);
    // / Build and define the different GUI types that are available.
    if ($guiType === 1) $guiUIFile = $GuiUI1File;
    if ($guiType === 2) $guiUIFile = $GuiUI2File;
    // / A request the Engine answered with a page of its own draws that page between the same header & footer.
    if (isset($EngineAnswer) && $EngineAnswer === 'page') $guiUIFile = $GuiEngineFile;
    // / Build the specified GUI.
    if (in_array($guiUIFile, $GuiFiles)) require_once($guiUIFile);
    // / Load the footer.
    if (!$FileListOnly && in_array($GuiFooterFile, $GuiFiles)) require_once($GuiFooterFile);
    // / A fragment never loads the header or the footer, so it cannot be judged on whether
    // / they displayed. It is complete when the interface itself rendered.
    if ($FileListOnly) { if ($UIDisplayed && $LanguageStringsLoaded) $GUIDisplayed = TRUE; }
    else if ($HeaderDisplayed && $UIDisplayed && $FooterDisplayed && $LanguageStringsLoaded) $GUIDisplayed = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $guiType, $guiUIFile, $ButtonCode);
  return $GUIDisplayed; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to display the GUI.
function showGUI($ShowGUI, $ButtonCode) {
  // / Set variables.
  global $ShowFiles;
  $GUIDisplayed = FALSE;
  // / $ShowFiles is read from the superglobal once, in verifyGlobals, & everything after
  // / that reads the variable. Reading $_GET again here meant the same question was asked
  // / of two different places, which is how they end up disagreeing.
  // / Call the GUI from the selected language pack after files have been uploaded.
  if ($ShowFiles) $GUIDisplayed = buildGUI(2, $ShowGUI, $ButtonCode);
  // / Call the GUI from the selected language pack before files have been uploaded.
  else $GUIDisplayed = buildGUI(1, $ShowGUI, $ButtonCode);
  return $GUIDisplayed; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to add up the size of everything in an upload.
// / Accepts no arguments.
// / Returns the bytes uploaded.
// / PHP has received every file by the time this runs, & reports each one's size itself.
// / A file that did not arrive is counted as nothing.
function measureUploadSize() {
  // / Set variables.
  global $EnableMemoryProtection;
  $UploadBytes = 0;
  $uploadSizes = array();
  $uploadSize = 0;
  if (isset($_FILES['file']['size'])) $uploadSizes = is_array($_FILES['file']['size']) ? $_FILES['file']['size'] : array($_FILES['file']['size']);
  foreach ($uploadSizes as $uploadSize) $UploadBytes = $UploadBytes + max(0, (int)$uploadSize);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $uploadSizes, $uploadSize);
  return $UploadBytes; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to upload a selection of files.
function uploadFiles() {
  // / Set variables.
  global $VirusScan, $AllowUserVirusScan, $ConvertDir, $Verbose, $PermissionLevels, $Allowed, $OperationErrors, $Pipelines, $EnableMemoryProtection;
  $UploadComplete = $UploadErrors = $virusFound = $variableIsSanitized = FALSE;
  $file = $fileExtension = $placedName = $sourcePath = '';
  $uploadIndex = 0;
  $scanComplete = $fileWasPlaced = FALSE;
  $filesPipelineIsReady = FALSE;
  $filesEntryPoint = '';
  // / What may be uploaded is this application's policy. Putting the bytes somewhere is not.
  // / $Allowed & $DangerousFiles are HRConvert2's lists & stay here. The placement is the
  // / Files pipeline, which sanitizes the destination name, resolves it & refuses anything
  // / landing outside the directory it was permitted.
  if (!is_array($_FILES['file']['name'])) $_FILES['file']['name'] = array($_FILES['file']['name']);
  foreach ($_FILES['file']['name'] as $uploadIndex => $file) {
    $UploadComplete = FALSE;
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $OperationErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 6000, FALSE);
      continue; }
    if ($Verbose) logEntry('User selected to Upload file '.$file.'.');
    $fileExtension = getExtension($file);
    if (extensionIsDangerous($fileExtension) or !in_array(strtolower($fileExtension), $Allowed)) {
      errorEntry('Unsupported file format, '.$fileExtension.'!', 6001, FALSE);
      continue; }
    // / The temporary name is indexed, so a multiple file upload hands copy() each file rather than an array.
    $sourcePath = is_array($_FILES['file']['tmp_name']) ? (isset($_FILES['file']['tmp_name'][$uploadIndex]) ? $_FILES['file']['tmp_name'][$uploadIndex] : '') : $_FILES['file']['tmp_name'];
    // / Loaded on demand. See the note in getFiles for why enumeration is not enough.
    list ($filesPipelineIsReady, $filesEntryPoint) = loadFilesPipeline();
    if ($filesPipelineIsReady && function_exists('placeFileInDirectory')) list ($fileWasPlaced, $placedName) = placeFileInDirectory($ConvertDir, $sourcePath, pathinfo($file, PATHINFO_BASENAME), $PermissionLevels);
    else {
      $fileWasPlaced = FALSE;
      warningEntry('The Files pipeline is not available, so nothing was uploaded.'); }
    if (!$fileWasPlaced) {
      $UploadErrors = TRUE;
      errorEntry('Could not upload file '.$file.'!', 6002, FALSE); }
    else {
      $UploadComplete = TRUE;
      if ($Verbose) logEntry('Uploaded file '.$file.'.'); }
    // / The scan runs on what LANDED rather than on what was offered, & only when something
    // / did. Scanning a file that was refused reports on nothing.
    if ($fileWasPlaced && !$AllowUserVirusScan && $VirusScan) {
      if ($Verbose) logEntry('Starting virus scan.');
      list ($scanComplete, $virusFound) = virusScan($ConvertDir.$placedName);
      if (!$scanComplete) errorEntry('Could not perform a virus scan!', 6003, TRUE);
      if ($virusFound) errorEntry('Virus detected!', 6004, TRUE);
      if ($Verbose) logEntry('Virus scan complete.'); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $filesPipelineIsReady, $filesEntryPoint, $file, $fileExtension, $placedName, $sourcePath, $uploadIndex, $variableIsSanitized, $scanComplete, $virusFound, $fileWasPlaced);
  return array($UploadComplete, $UploadErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to stage a selection of files for download.
function downloadFiles($Download) {
  // / Set variables.
  global $DangerousFiles, $Verbose, $ConsolidatedLogFileName, $Allowed, $EnableMemoryProtection;
  $DownloadComplete = $DownloadErrors = $clean = $copy = $skip = $variableIsSanitized = FALSE;
  $fileIsVerified = FALSE;
  $file = $fileExtension = $pathname = $oldPathname = $oldExtension = $newPathname = $resolvedFilename = '';
  // / The result describes the operation, not the last file in the list.
  // / This carried the same fault deleteFiles() carried. $DownloadComplete was reset to
  // / FALSE at the top of every iteration & set TRUE only by a successful one, so the value
  // / that survived the loop described whatever happened to the LAST entry. Nine files
  // / staged out of ten reported total failure if the tenth was refused, & the caller
  // / answers a failure here with a fatal error 19 that prints ERROR!!! to the page.
  // / Counting instead, on the same terms as deleteFiles(). An entry refused before it is
  // / attempted is recorded as an error for the caller but is not counted as an attempt,
  // / because refusing input the application was right to refuse is not a failure of the
  // / download.
  $filesAttempted = $filesStaged = 0;
  list ($Download, $variableIsSanitized) = sanitize($Download, FALSE);
  // / Make sure the input files are formatted into an array.
  // / A single filename & a list of them are the same operation with a different count.
  if (!is_array($Download)) $Download = array($Download);
  // / Iterate through the array of input files.
  foreach ($Download as $file) {
    // / Every iteration decides these for itself, so one file in a list never flags a later one.
    $clean = $copy = $skip = FALSE;
    // / Make sure the file is sanitized before processing it.
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      // / This wrote to $OperationErrors, which this function does not declare, does not
      // / read & does not return. A refused filename therefore reported no error at all.
      $DownloadErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 3000, FALSE);
      continue; }
    if ($Verbose) logEntry('User selected to Download file '.$file.'.');
    if ($file === $ConsolidatedLogFileName) $skip = TRUE;
    else $clean = $copy = TRUE;
    $fileExtension = getExtension($file);
    // / Make sure the file is not in the list of dangerous formats.
    if (extensionIsDangerous($fileExtension) or in_array(strtolower($file), $DangerousFiles) or !in_array(strtolower($fileExtension), $Allowed)) {
      $DownloadErrors = TRUE;
      // / A name with no extension is named as one, because a blank here read as a missing word.
      errorEntry('Unsupported file format, '.($fileExtension === '' ? 'no extension' : $fileExtension).'!', 3004, FALSE);
      continue; }
    // / Past this point the file is one this function agreed to act on, so it counts.
    $filesAttempted++;
    // / Make sure all iteration specific required variables are properly sanitized.
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname, $resolvedFilename) = verifyFile($file, FALSE, FALSE, $clean, $copy, $skip);
    if (!$fileIsVerified) {
      // / This wrote to $ArchiveErrors, which belonged to archiveFiles(). A file that failed
      // / verification here reported success to the caller.
      $DownloadErrors = TRUE;
      errorEntry('Could not verify the input file.', 3001, FALSE);
      continue; }
    // / Make sure that the file exists.
    if (!file_exists($oldPathname) && !$skip) {
      $DownloadErrors = TRUE;
      errorEntry('File '.$file.' does not exist!', 3002, FALSE);
      continue; }
    // / A staged file that is not where it was staged to is a failure of this operation.
    // / This logged error 3003 & then fell through to report the file as staged anyway.
    if (!file_exists($pathname)) {
      $DownloadErrors = TRUE;
      errorEntry('Could not verify the input file.', 3003, FALSE);
      continue; }
    $filesStaged++;
    // / Report the path that was actually staged, which is $pathname.
    // / This reported $newPathname & printed 'Verified file .' on every download.
    // / verifyFile() initialises $NewPathname to FALSE & only fills it inside its own
    // / if ($UserFilename && $UserExtension) branch, which builds the RENAMED destination an
    // / operation is about to write to. A download renames nothing, so this is the one caller
    // / that passes FALSE for both of those, the branch never runs, & FALSE concatenates into
    // / a log line as an empty string rather than as anything a reader would notice.
    // / The archive, conversion & OCR callers all pass a real filename & extension, so the
    // / same line reads correctly for them & the fault looked like it belonged to the log
    // / rather than to this one call.
    if ($Verbose) logEntry('Verified file '.$pathname.'.'); }
  // / The operation is complete when every file it agreed to act on was staged.
  // / An empty list is complete because there was nothing to stage, & reporting a fatal
  // / failure for asking to download nothing would be a worse answer than doing nothing.
  $DownloadComplete = ($filesStaged === $filesAttempted);
  if ($Verbose) logEntry('Download result: Attempted: '.$filesAttempted.', Staged: '.$filesStaged.', Errors: '.($DownloadErrors ? 'YES' : 'NO').'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $file, $fileExtension, $clean, $copy, $skip, $variableIsSanitized, $fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname, $resolvedFilename, $filesAttempted, $filesStaged);
  return array($DownloadComplete, $DownloadErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to delete a selection of files.
// / Each name is removed from the session directory & from the temporary one, by the Files
// / pipeline, which refuses any name that resolves outside the directory it was handed.
function deleteFiles($FilesToDelete) {
  // / Set variables.
  global $Verbose, $ConvertDir, $ConvertTempDir, $Pipelines, $EnableMemoryProtection;
  $DeleteComplete = $DeleteErrors = $variableIsSanitized = FALSE;
  $file = $fileExtension = '';
  $filesAttempted = $filesDeleted = 0;
  $permittedNames = array();
  $permittedDirectory = '';
  $filesPipelineIsReady = FALSE;
  $filesEntryPoint = '';
  $removalSucceeded = FALSE;
  $namesAttempted = $namesRemoved = 0;
  // / What may be deleted is this application's decision, & doing the deleting is not.
  // / $DangerousFiles is a list of formats HRConvert2 refuses to touch, which no other application has & an engine cannot guess, so the policy stays here.
  // / The removal itself is the Files pipeline, which is handed a directory it may operate in & refuses anything resolving outside it.
  // / The result describes the operation, not the last file in the list.
  list ($FilesToDelete, $variableIsSanitized) = sanitize($FilesToDelete, FALSE);
  if (!is_array($FilesToDelete)) $FilesToDelete = array($FilesToDelete);
  foreach ($FilesToDelete as $file) {
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $DeleteErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 23000, FALSE);
      continue; }
    if ($Verbose) logEntry('User selected to Delete file '.$file.'.');
    $fileExtension = getExtension($file);
    if (extensionIsDangerous($fileExtension) or $fileExtension === '') {
      $DeleteErrors = TRUE;
      errorEntry('Unsupported file format, '.$fileExtension.'!', 23001, FALSE);
      continue; }
    $filesAttempted++;
    $permittedNames[] = pathinfo($file, PATHINFO_BASENAME); }
  // / BOTH DIRECTORIES, because a file exists in the session directory & may also exist in
  // / the temporary one, & leaving either behind is a file a user asked to be gone.
  // / A name absent from one of them is not an error. It is a file that was never there.
  if (!empty($permittedNames)) {
    // / Loaded on demand. See the note in getFiles for why enumeration is not enough.
    list ($filesPipelineIsReady, $filesEntryPoint) = loadFilesPipeline();
    if ($filesPipelineIsReady && function_exists('removeFilesFromDirectory')) {
      foreach (array($ConvertTempDir, $ConvertDir) as $permittedDirectory) {
        list ($removalSucceeded, $namesAttempted, $namesRemoved) = removeFilesFromDirectory($permittedDirectory, $permittedNames);
        $filesDeleted += $namesRemoved; }
      // / Counted across both directories, so a file removed from each counts twice. What
      // / matters is that every name asked for is gone from everywhere it could have been.
      $DeleteComplete = ($filesDeleted > 0);
      if (!$DeleteComplete) {
        $DeleteErrors = TRUE;
        errorEntry('Could not delete the requested file(s)!', 23002, FALSE); } }
    else {
      $DeleteErrors = TRUE;
      errorEntry('The Files pipeline is not available, so nothing was deleted!', 23003, FALSE); } }
  if ($Verbose) logEntry('Delete result: Attempted: '.$filesAttempted.', Deleted: '.$filesDeleted.', Errors: '.($DeleteErrors ? 'yes' : 'no').'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $filesPipelineIsReady, $filesEntryPoint, $permittedDirectory, $file, $fileExtension, $variableIsSanitized, $filesAttempted, $filesDeleted, $permittedNames, $removalSucceeded, $namesAttempted, $namesRemoved);
  return array($DeleteComplete, $DeleteErrors); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to sanitize the pattern a selection of new names is made from, keeping its tokens.
// / Accepts the pattern as a user typed it. Returns the pattern & whether it was sanitized, in that order, as sanitize() does.
// / A pattern holds text & the tokens {name}, {n} & {date}, which renameFiles() fills in for each file.
// / sanitize() removes braces, so each token is held as a control character while sanitize() runs, & put back after.
// / A pattern holding a control character of its own is refused before that, so a token can never be forged.
// / A pattern is at most 64 characters, as a name a workflow's option gives is.
function sanitizeNamePattern($rawPattern) {
  // / Set variables.
  global $EnableMemoryProtection;
  $NamePattern = '';
  $PatternIsSanitized = FALSE;
  $heldPattern = '';
  $tokenNames = array('{name}', '{n}', '{date}');
  $tokenHolders = array(chr(1), chr(2), chr(3));
  if (is_string($rawPattern) && strlen($rawPattern) <= 64 && preg_match('/[\x00-\x1F\x7F]/', $rawPattern) !== 1) {
    list ($heldPattern, $PatternIsSanitized) = sanitize(str_replace($tokenNames, $tokenHolders, $rawPattern), TRUE);
    if ($PatternIsSanitized && is_string($heldPattern)) $NamePattern = str_replace($tokenHolders, $tokenNames, $heldPattern);
    else $PatternIsSanitized = FALSE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $heldPattern, $tokenNames, $tokenHolders, $rawPattern);
  return array($NamePattern, $PatternIsSanitized); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to rename a selection of files in the session.
// / Accepts the files & the pattern their new names are made from.
// / Returns whether any file has its new name, whether anything went wrong & the name each file now has, in that order.
// / {name} is a file's name without its extension, {n} its place in the selection, counting from 1, & {date} the day it is renamed, written as 2026-11-13.
// / A pattern holding none of them gives every file the same name, so each file after the first is given the next free one, as an output is.
// / The extension is never changed, so a rename never makes a file look like a format it is not. A tar.gz, tar.bz2, tar.xz or tar.zst keeps both of its extensions.
// / A new name is sanitized as an upload's is, & a name this application refuses to show is refused.
// / Nothing in the session is ever written over. The Files pipeline renames by linking, which fails rather than replaces when the name was taken in the meantime.
// / A file whose new name is its own keeps it, & is named among the renamed, so a workflow passes it on.
function renameFiles($FilesToRename, $NamePattern) {
  // / Set variables.
  global $Verbose, $ConvertDir, $DangerousFiles, $EnableMemoryProtection;
  $RenameComplete = $RenameErrors = FALSE;
  $RenamedFiles = array();
  $file = $filePath = $extensionPart = $baseName = $newBase = $newName = $candidateName = $freePath = $todaysDate = $cleanPattern = $filesEntryPoint = '';
  $variableIsSanitized = $patternIsSanitized = $renameIsPossible = $baseIsSanitized = $nameWasFound = $fileWasRenamed = $nameWasTaken = $filesPipelineIsReady = FALSE;
  $fileNumber = $attemptNumber = 0;
  $extensionMatch = array();
  if (!is_array($FilesToRename)) $FilesToRename = array($FilesToRename);
  list ($cleanPattern, $patternIsSanitized) = sanitizeNamePattern($NamePattern);
  if (!$patternIsSanitized or $cleanPattern === '') {
    $RenameErrors = TRUE;
    errorEntry('The pattern new names are made from could not be sanitized or is empty, so nothing was renamed!', 23100, FALSE); }
  else {
    list ($filesPipelineIsReady, $filesEntryPoint) = loadFilesPipeline();
    if (!$filesPipelineIsReady or !function_exists('renameFileInDirectory')) {
      $RenameErrors = TRUE;
      errorEntry('The Files pipeline is not available, so nothing was renamed!', 23101, FALSE); }
    else $renameIsPossible = TRUE; }
  // / Every file in a selection is given the same date, so a selection renamed at midnight is not split across two days.
  $todaysDate = date('Y-m-d');
  if ($renameIsPossible) foreach ($FilesToRename as $file) {
    // / A file's place counts every file given, so a file that fails does not change the number of the files after it.
    $fileNumber++;
    redeclare($fileWasRenamed, FALSE);
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $RenameErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 23102, FALSE);
      continue; }
    redeclare($filePath, $ConvertDir.$file);
    clearstatcache(TRUE, $filePath);
    if (!is_file($filePath) or is_link($filePath) or extensionIsDangerous(getExtension($file))) {
      $RenameErrors = TRUE;
      warningEntry('The file '.$file.' is not a file in the session this server handles, so it was not renamed.');
      continue; }
    if ($Verbose) logEntry('User selected to Rename file '.$file.'.');
    // / The extension a file had is the one it keeps, spelt as it was.
    redeclare($extensionPart, (preg_match('/\.(tar\.(?:gz|bz2|xz|zst))$/i', $file, $extensionMatch) === 1) ? $extensionMatch[1] : (string)pathinfo($file, PATHINFO_EXTENSION));
    redeclare($baseName, substr($file, 0, -(strlen($extensionPart) + 1)));
    // / The tokens are filled in before the name is sanitized, so what a file's own name brings is held to the same rules as what the pattern brings.
    // / The name before it is shredded first, as redeclare() would, because a list() cannot pass through redeclare().
    purgeSensitiveMemory($EnableMemoryProtection, $newBase, $baseIsSanitized);
    list ($newBase, $baseIsSanitized) = sanitize(str_replace(array('{name}', '{n}', '{date}'), array($baseName, (string)$fileNumber, $todaysDate), $cleanPattern), TRUE);
    redeclare($newName, (string)$newBase.'.'.$extensionPart);
    if (!$baseIsSanitized or !is_string($newBase) or $newBase === '' or strtolower($newName) === 'index.html' or in_array(strtolower($newName), (array)$DangerousFiles)) {
      $RenameErrors = TRUE;
      warningEntry('The pattern gave '.$file.' a name this server does not accept, so it was not renamed.');
      continue; }
    if ($newName === $file) {
      $RenamedFiles[] = $file;
      if ($Verbose) logEntry('The file '.$file.' already has the name it was given.');
      continue; }
    redeclare($candidateName, $newName);
    for ($attemptNumber = 0; $attemptNumber < 3 && !$fileWasRenamed; $attemptNumber++) {
      clearstatcache(TRUE, $ConvertDir.$candidateName);
      // / A name that is taken, or that a link holds, is passed over for the next free one.
      if (file_exists($ConvertDir.$candidateName) or is_link($ConvertDir.$candidateName)) {
        purgeSensitiveMemory($EnableMemoryProtection, $candidateName, $freePath, $nameWasFound);
        list ($nameWasFound, $freePath, $candidateName) = findFreeOutputName($ConvertDir, (string)$newBase, $extensionPart);
        if (!$nameWasFound) break; }
      purgeSensitiveMemory($EnableMemoryProtection, $fileWasRenamed, $nameWasTaken);
      list ($fileWasRenamed, $nameWasTaken) = renameFileInDirectory($ConvertDir, $file, $candidateName);
      // / A rename that failed for any reason but a taken name is not tried again, because trying again would fail the same way.
      if (!$fileWasRenamed && !$nameWasTaken) break; }
    if ($fileWasRenamed) {
      $RenamedFiles[] = $candidateName;
      if ($Verbose) logEntry('Renamed file '.$file.' to '.$candidateName.'.'); }
    else {
      $RenameErrors = TRUE;
      errorEntry('Could not rename file '.$file.' to '.$newName.'!', 23103, FALSE); } }
  if (count($RenamedFiles) > 0) $RenameComplete = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $file, $filePath, $extensionPart, $baseName, $newBase, $newName, $candidateName, $freePath, $todaysDate, $cleanPattern, $filesEntryPoint, $variableIsSanitized, $patternIsSanitized, $renameIsPossible, $baseIsSanitized, $nameWasFound, $fileWasRenamed, $nameWasTaken, $filesPipelineIsReady, $fileNumber, $attemptNumber, $extensionMatch, $FilesToRename, $NamePattern);
  return array($RenameComplete, $RenameErrors, $RenamedFiles); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert a selection of files.
// / Returns whether any file converted, whether anything went wrong & the name of every file it made, in that order.
// / It prints nothing. The core prints what it made for a button, & a workflow passes it to the next step.
// / A backgrounded successful stream has no output file when this function returns.
// / $WaitForStream tells us the conversion is still running & must not be judged by its output.
// / A family is resolved by applicationConversionFamilies(), so a workflow asks the same lists this does.
function convertFiles($ConvertSelected, $UserFilename, $UserExtension, $Height, $Width, $Rotate, $Bitrate) {
  // / Set variables.
  global $Verbose, $VirusScan, $WaitForStream, $EnableMemoryProtection;
  $MainConversionSuccess = $MainConversionErrors = $virusFound = $skip = $isExtensionSupported = $fileIsVerified = $variableIsSanitized = $outputExists = $conversionSuccess = $conversionErrors = $fileConversionSuccess = $anyStreamStarted = $scanComplete = FALSE;
  $clean = $copy = TRUE;
  $pathname = $oldPathname = $oldExtension = $newPathname = $file = $convertedFilename = $extension = $verifiedFilename = '';
  $ConvertedFiles = $familyFormats = $conversionFamilies = array();
  $arrKey = '';
  $conversionFamilies = applicationConversionFamilies();
  // / Make sure the input files are formatted into an array.
  if (!is_array($ConvertSelected)) $ConvertSelected = array($ConvertSelected);
  // / Iterate through the array of input files.
  foreach ($ConvertSelected as $file) {
    $fileConversionSuccess = $isExtensionSupported = $conversionSuccess = FALSE;
    // / Reset $WaitForStream for each file.
    // / It is a global set inside convert() & a stale TRUE would make the next file skip its output check.
    $WaitForStream = FALSE;
    // / Make sure the file is sanitized before processing it.
    list ($file, $variableIsSanitized) = sanitize($file, TRUE);
    if (!$variableIsSanitized or !is_string($file) or $file === '' or $file === '.' or $file === '..' or $file === 'index.html') {
      $MainConversionErrors = TRUE;
      errorEntry('Could not sanitize the input file!', 5000, FALSE);
      continue; }
    // / Every file asks verifyFile() for a clean output, so an output whose name is taken is given the next free one.
    // / An archive is no exception, so a conversion never adds to an archive that already has the name.
    if ($Verbose) logEntry('User selected to Convert file '.$file.'.');
    // / Verify the file before performing any operations on it.
    list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname, $verifiedFilename) = verifyFile($file, $UserFilename, $UserExtension, $clean, $copy, $skip);
    if (!$fileIsVerified) {
      $MainConversionErrors = TRUE;
      errorEntry('Could not verify the input file.', 5001, FALSE);
      continue; }
    else if ($Verbose) logEntry('Verified file '.$newPathname.'.');
    // / Scan with the default scanner if $VirusScan is set to TRUE in config.php.
    // / The staged input is scanned, because it is what the converter reads & the output does not exist yet.
    if ($VirusScan) {
      if ($Verbose) logEntry('Starting virus scan.');
      list ($scanComplete, $virusFound) = virusScan($pathname);
      if (!$scanComplete) errorEntry('Could not perform a virus scan!', 5002, TRUE);
      if ($virusFound) errorEntry('Virus detected!', 5003, TRUE);
      if ($Verbose) logEntry('Virus scan complete.'); }
    // / Loop through the families & only call the converter if both input & output files are supported.
    // / This loop resolves. It does not enumerate.
    // / An interface offers every family that claims the input extension, which is why an
    // / rtf file shows a Document menu & an E-Book menu. By the time a request arrives the
    // / user has already chosen an output format from one of those menus, so the input &
    // / output pair together name one family & the first match is the right match.
    foreach ($conversionFamilies as $arrKey => $familyFormats) {
      if (!is_array($familyFormats[0]) or !is_array($familyFormats[1])) continue;
      if (!in_array(strtolower($oldExtension), $familyFormats[0])) continue;
      if (!in_array(strtolower($UserExtension), $familyFormats[1])) continue;
      $isExtensionSupported = TRUE;
      list ($conversionSuccess, $conversionErrors, $newPathname, $extension, $convertedFilename) = convert($arrKey, $pathname, $newPathname, $UserExtension, $Height, $Width, $Rotate, $Bitrate);
      if ($conversionErrors) {
        $MainConversionErrors = TRUE;
        logEntry($arrKey.' conversion finished with errors.'); }
      if ($Verbose) logEntry($arrKey.' Conversion Complete.');
      break; }
    // / An unsupported combination never reached a converter at all.
    if (!$isExtensionSupported) {
      $MainConversionErrors = TRUE;
      errorEntry('The conversion '.$oldExtension.' to '.$UserExtension.' is not supported!', 5006, FALSE);
      continue; }
    // / Record that at least one file in this request launched a stream.
    // / $WaitForStream is reset on every iteration, so without this the core would only
    // / ever see the value belonging to the LAST file & would abandon an earlier stream.
    if ($WaitForStream) $anyStreamStarted = TRUE;
    // / The converter already failed & already logged the reason it failed.
    // / Checking for an output file here would only report the same failure a second time.
    if (!$conversionSuccess) {
      $MainConversionErrors = TRUE;
      continue; }
    // / A backgrounded stream is judged by its launch, everything else by its output file.
    $outputExists = $WaitForStream ? TRUE : file_exists($newPathname);
    if ($outputExists) {
      $fileConversionSuccess = TRUE;
      // / The name is returned, so the caller decides what is done with it.
      $ConvertedFiles[] = $convertedFilename;
      if ($Verbose) logEntry('Created a file at '.$newPathname.'.'); }
    else {
      $MainConversionErrors = TRUE;
      errorEntry('Could not create '.$newPathname.' from '.$oldPathname.'!', 5005, FALSE); }
    // / Record that at least one file in this request converted successfully.
    // / This is aggregated for the same reason $anyStreamStarted is.
    if ($fileConversionSuccess) $MainConversionSuccess = TRUE; }
  // / Restore the aggregate so the core can supervise a stream started by ANY file.
  $WaitForStream = $anyStreamStarted;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $familyFormats, $conversionFamilies, $file, $pathname, $oldPathname, $oldExtension, $newPathname, $fileIsVerified, $scanComplete, $virusFound, $variableIsSanitized, $arrKey, $clean, $copy, $skip, $isExtensionSupported, $outputExists, $conversionSuccess, $conversionErrors, $fileConversionSuccess, $anyStreamStarted, $convertedFilename, $extension, $verifiedFilename);
  return array($MainConversionSuccess, $MainConversionErrors, $ConvertedFiles); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to scan the files a user selected with ClamAV, ScanCore or both, & build the
// / report they download.
// / Accepts the files & the scan type, which is clamav, scancore or all, in that order.
// / Returns whether a verdict was reached, whether anything went wrong, whether any file was found infected, the report's path, its name
// / the files found suspicious & the files not scanned in full, in that order. A suspicious file is one ScanCore matched without a hash, from v4.2.3,
// / & is not an infected one. A file not scanned in full is one ScanCore could not read all of, such as an encrypted archive, from v4.2.3.
// / The scan is the Scan pipeline's, from v4.1.6. This dispatches to it, names the report, & reads what the scan found.
// / A missing Scan pipeline is reported rather than read as a scan that found nothing, because a clean verdict nobody reached is the one answer a scan must never give.
function userVirusScan($FilesToScan, $type) {
  // / Set variables.
  global $PipelineCoreActive, $ConsolidatedLogFileName, $EnableMemoryProtection;
  $ScanComplete = $ScanErrors = $UserVirusFound = FALSE;
  $ReportPath = $ReportName = '';
  $SuspiciousFiles = $IncompleteFiles = $scanFindings = array();
  if (!$PipelineCoreActive or !function_exists('runOperationPipeline')) {
    $ScanErrors = TRUE;
    errorEntry('A virus scan was requested but no Scan pipeline is installed!', 34006, FALSE); }
  else {
    list ($ScanComplete, $ScanErrors, $scanFindings) = runOperationPipeline('Scan', $FilesToScan, (string)$ConsolidatedLogFileName, 'txt', (string)$type);
    if (is_array($scanFindings)) {
      $UserVirusFound = (($scanFindings['Threat'] ?? FALSE) === TRUE);
      $ReportPath = is_string($scanFindings['Report'] ?? NULL) ? $scanFindings['Report'] : '';
      $ReportName = is_string($scanFindings['ReportName'] ?? NULL) ? $scanFindings['ReportName'] : '';
      $SuspiciousFiles = (isset($scanFindings['Suspicious']) && is_array($scanFindings['Suspicious'])) ? array_values(array_filter($scanFindings['Suspicious'], 'is_string')) : array();
      $IncompleteFiles = (isset($scanFindings['Incomplete']) && is_array($scanFindings['Incomplete'])) ? array_values(array_filter($scanFindings['Incomplete'], 'is_string')) : array(); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $scanFindings, $FilesToScan, $type);
  return array($ScanComplete, $ScanErrors, $UserVirusFound, $ReportPath, $ReportName, $SuspiciousFiles, $IncompleteFiles); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The main logic of the program that makes use of the functions above.

// / The kernel is checked, then loaded, before anything else runs.
// / Everything below calls into it, quickDie included, so this is the one place in the
// / application allowed to halt without quickDie, because a missing kernel takes quickDie with
// / it. Each halt prints its message & exits with status one. A plain die with a message exits
// / with status zero, which would report a missing kernel as a success to anything watching.
// / It is found beside THIS file rather than through $InstLoc, which comes from a
// / configuration that has not been read yet & may name a different installation entirely.
// / The kernel that belongs to this entry point is the one that shipped beside it.
// / The version is read with the same pattern readComponentVersion uses, because that
// / function lives in the kernel & so cannot be asked before it is loaded. A mismatched
// / kernel is never executed, the same guarantee a mismatched Engine gets.
// / The build cannot write this pin or the Engine's, because it runs inside both. Move them
// / by hand, with the version each file declares.
// / The name this application goes by until config.php names it. The kernel labels whatever it reports with it before config.php is read.
$DefaultApplicationName = 'HRConvert2';
$RequiredKernelVersion = 'v4.2.4';
$KernelFile = dirname(__FILE__).DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'Kernel'.DIRECTORY_SEPARATOR.'kernel.php';
$KernelVersionMatch = array();
if (!is_readable($KernelFile)) {
  print('ERROR!!! '.$DefaultApplicationName.'-28006, The kernel is missing or unreadable at '.$KernelFile.'! Nothing can be loaded or logged without it.'.PHP_EOL);
  exit(1); }
if (!preg_match('/\$KernelVersion\s*=\s*\'([^\']+)\'/', (string)@file_get_contents($KernelFile), $KernelVersionMatch) or ltrim($KernelVersionMatch[1], 'vV') !== ltrim($RequiredKernelVersion, 'vV')) {
  print('ERROR!!! '.$DefaultApplicationName.'-28007, The kernel at '.$KernelFile.' reports '.(isset($KernelVersionMatch[1]) ? $KernelVersionMatch[1] : 'no version').' & this application requires '.$RequiredKernelVersion.'!'.PHP_EOL);
  exit(1); }
$KernelVersionMatch = array();
require_once ($KernelFile);

// / Root runs nothing an account other than root could have changed. The tree this file is in
// / is read as soon as the kernel that reads it is loaded, & before anything else in it runs.
// / The check is consumed & not acted on further here. verifyCodeIsTrustedByRoot halts every
// / command but a repair itself, says so, & a repair reads the tree again as it finishes, so
// / FALSE reaching this line means a repair that has already been warned about is running.
// / This file names itself, so a refusal names the command that gives the tree back to root.
$CodeIsTrusted = verifyCodeIsTrustedByRoot(dirname(__FILE__), basename(__FILE__));

// / The following code resets PHP's time limit for execution.
$TimeReset = setTimeLimit();
if (!$TimeReset) quickDie('Could not set the execution timer!', 3);

// / The following code sets date & time related variables.
list ($TimeIsSet, $Date, $Time, $EpochTime) = verifyTime();
if (!$TimeIsSet or !$Date or !$Time) quickDie('Could not verify timezone!', 4);

// / The following code verifies that the installation is valid.
list ($InstallationIsVerified, $ConfigFile, $Version, $CoreLoaded) = verifyInstallation();
if (!$InstallationIsVerified) quickDie('Could not verify installation!', 5);

// / The following code verifies that string inputs to the core are properly sanitized.
list ($InputsAreVerified, $ShowGUI, $GUI, $Color, $Language, $Token1, $Token2, $Height, $Width, $Rotate, $Bitrate, $Method, $Download, $UserFilename, $UserExtension, $FilesToArchive, $PDFWorkSelected, $ConvertSelected, $FilesToScan, $FilesToDelete, $UserScanType, $UserURLDownload, $FilesToIdentify, $IdentifyReport, $IdentifyFilename, $Sort, $WorkflowName, $WorkflowFiles, $WorkflowDocument, $WorkflowCheck, $WorkflowExport, $FilesToExtract, $FilesToRename, $RenamePattern, $FilesToStrip, $FilesToMerge, $FilesToSplit, $WorkflowPreview, $WorkflowPreviewDocument, $WorkflowStage, $WorkflowStageDescriptions) = verifyInputs();
if (!$InputsAreVerified) quickDie('Could not verify inputs!', 6);

// / The following code verifies enough user information to generate a unique session identifier.
list ($SessionIsVerified, $IP, $HashedUserAgent) = verifySession();
if (!$SessionIsVerified) quickDie('Could not verify session!', 7);

// / The following code verifies the tokens supplied by the user, if any.
// / If no tokens were supplied by the user, generate new ones.
list ($TokensAreValid, $Token1, $Token2) = verifyTokens($Token1, $Token2);

// / The following code generates a series of unique session identifiers.
list ($SesHashIsVerified, $SesHash, $SesHash2, $SesHash3, $SesHash4) = verifySesHash($Token1);
if (!$SesHashIsVerified) quickDie('Could not verify unique session identifier!', 8);

// / The following code reads the Engine configuration, which is data rather than code.
// / It is read HERE, before the logging environment, & the Engine itself is verified &
// / loaded much later. Those are two different things & they are needed at two different
// / times. verifyLogs() decides the name of the logfile & some of that decision belongs to
// / the Engine, so the settings have to exist before it runs. The Engine's own functions
// / are not needed until a conversion or a command line report asks for one.
// / An absent configuration is not an error. Every setting it carries has a local fallback
// / & the Engine reports its own absence when it is verified.
$EngineConfigPath = $InstLoc.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'Engine'.DIRECTORY_SEPARATOR.'engineConfig.php';
if (file_exists($EngineConfigPath)) require_once ($EngineConfigPath);

// / The following code verifies the logging environment.
list ($LogFileExists, $LogFile) = verifyLogs();
if (!$LogFileExists) quickDie('Could not verify logging environment!', 9);
if ($Verbose) logEntry('Verified logging environment.');

// / The following code verifies & loads the Engine, which provides the environment this application runs in rather than anything it does.
// / The kernel's loadEngine() verifies it, loads it & has it start, & the Engine's startEngine() builds everything a request hangs off it.
// / That is what kind of machine this is, the sandbox profiles this application declares, resource awareness & the gate on it, a workflow
// / worker's session, the Engine's own answer to a request, the session's folders & the Pipeline & Workflow Cores. It was this file's until v4.2.4.
// / It is loaded before this application's own globals, & that position is not negotiable, because the session's folders are the Engine's.
// / An Engine that is missing or mismatched is a warning, & what is lost is named while everything that does not depend on it carries on.
list ($EngineActive, $EngineVersion) = loadEngine();

// / The following code sets this application's own global variables for the session.
list ($GlobalsAreVerified) = verifyGlobals();
if (!$GlobalsAreVerified) errorEntry('Could not verify globals!', 11, TRUE);
else if ($Verbose) logEntry('Verified globals.');

// / The following code decides if the security context being attempted matches a valid CLI or web request.
// / The kernel's runCommandLine() reads the command line, answers -v & -h, hands an argument beginning --app to this application's
// / handler & every other command to the Engine. It names this file in what it prints. Error 27 should not be possible.
list ($CommandLineHandled, $UserType, $CommandLineStatus) = runCommandLine(basename(__FILE__));

// / Only enable file operations for web users, when the script is being run with Apache+PHP.
// / The kernel's admitWebRequest() refuses root & every account but the web server's, refuses a request with no Engine to serve it, & has the
// / Engine make the request's folders & sweep expired sessions. Nothing below runs unless it admits the request.
if (!$CommandLineHandled && $UserType === 'web' && admitWebRequest(basename(__FILE__))) {

  // / The following code displays the appropriate GUI for the session.
  // / Every file operation answers with a terse reply, & suppresses the interface here so its caller receives a short answer it can read.
  // / A caller can tell a failure from a success only by reading the body, & a whole page carries the script literal that recognises a core error.
  // / A file operation returns a filename or an error, & never a page.
  // / An upload is known by its files, because Dropzone sends the file & the two tokens & no upload field.
  // / A request the Engine wrote the whole answer to draws nothing here.
  if ($EngineAnswer !== 'answered' && !isset($_POST['filesToArchive']) && !isset($_POST['filesToExtract']) && !isset($_POST['filesToRename']) && !isset($_POST['filesToStrip']) && !isset($_POST['filesToMerge']) && !isset($_POST['filesToSplit']) && !isset($_POST['convertSelected']) && !isset($_POST['pdfworkSelected']) && !isset($_POST['download']) && !isset($_POST['upload']) && empty($_FILES) && !isset($_POST['filesToScan']) && !isset($_POST['filesToDelete']) && !isset($_POST['filesToIdentify']) && !isset($_POST['workflowLibrary']) && !isset($_POST['workflowRun']) && !isset($_POST['workflowDocument']) && !isset($_POST['workflowStatus']) && !isset($_POST['workflowStop']) && !isset($_POST['workflowOperations']) && !isset($_POST['workflowCheck']) && !isset($_POST['workflowExport']) && !isset($_POST['workflowPreview']) && !isset($_POST['workflowPreviewDocument']) && !isset($_POST['workflowStage'])) {

    // / The following code sets the GUI for the session.
    list ($GuiIsSet, $GuiToUse, $GuiDir, $GuiFiles) = verifyGui();
    if (!$GuiIsSet) errorEntry('Could not verify GUI! GUI set to '.$GuiToUse.'!', 25, TRUE);
    else if ($Verbose) logEntry('Verified GUI. GUI set to '.$GuiToUse.'.');

    // / The following code sets the color scheme for the session.
    list ($ColorsAreSet, $ButtonCode) = verifyColors($ButtonStyle);
    if (!$ColorsAreSet) errorEntry('Could not verify color scheme! Color set to '.$ButtonStyle.'!', 15, TRUE);
    else if ($Verbose) logEntry('Verified color scheme. Color set to '.$ButtonStyle.'.');

    // / The following code sets the language for the session.
    list ($LanguageIsSet, $LanguageToUse, $LanguageDir, $LanguageFiles) = verifyLanguage();
    if (!$LanguageIsSet) errorEntry('Could not verify language! Language set to '.$LanguageToUse.'!', 16, TRUE);
    else if ($Verbose) logEntry('Verified language. Language set to '.$LanguageToUse.'.');

    // / The following code sets the order the session's files are listed in.
    // / It cannot fail, because a code it does not understand falls back to listing by name.
    $SortToUse = verifySort($Sort);
    if ($Verbose) logEntry('Verified sort order. Sort set to '.$SortToUse.'.');

    // / The following code actually builds & renders the GUI.
    $GUIDisplayed = showGUI($ShowGUI, $ButtonCode);
    if (!$GUIDisplayed) errorEntry('Could not display GUI!', 17, TRUE);
    else if ($Verbose) logEntry('Displaying the GUI.'); }

  // / If this is an API call with a simple output, continue without having displayed any GUI at all.
  else if ($Verbose) logEntry('Skipping display GUI procedure.');

  // / Check if we're providing the user with tokens generated during this session.
  // / In no other code path do we generate a new token that gets provided to the user.
  // / The tokens are the session, so neither is ever written to a log.
  // / The session's folder names it in every entry, as the session label.
  if (!$TokensAreValid && $Verbose) logEntry('Providing the user with a new pair of tokens.');

  // / Only enable file related operations if valid tokens were supplied by the user.
  // / A request the Engine answered, with a page of its own or otherwise, runs none, as one whose visitor must sign in first.
  if ($TokensAreValid && $EngineAnswer === '') {
    if ($Verbose) logEntry('Verified supplied tokens.');

    // / The following code is performed when a user initiates a file upload.
    if (!empty($_FILES)) {
      logEntry('Initiating Uploader.');
      // / An upload takes no budget, so it asks the listener whether the disk holding this session can take it.
      // / A refusal fails the upload as any other failure does, & the warning says why.
      // / With no Engine there is no listener to ask, so the upload goes ahead as it always has.
      if (function_exists('requestStorageSpace')) list ($UploadIsPermitted, $UploadRefusal) = requestStorageSpace($ConvertLoc, measureUploadSize());
      else list ($UploadIsPermitted, $UploadRefusal) = array(TRUE, '');
      if (!$UploadIsPermitted) {
        warningEntry('An upload was refused. '.$UploadRefusal);
        list ($UploadComplete, $UploadErrors) = array(FALSE, TRUE); }
      else list ($UploadComplete, $UploadErrors) = uploadFiles();
      if (!$UploadComplete) errorEntry('Upload Failed!', 18, TRUE);
      if ($UploadErrors) logEntry('Upload finished with errors.');
      if ($Verbose) logEntry('Upload Complete.'); }

    // / The following code is performed when a user downloads a selection of files.
    if (isset($_POST['download'])) {
      logEntry('Initiating Downloader.');
      list ($DownloadComplete, $DownloadErrors) = downloadFiles($Download);
      if (!$DownloadComplete) errorEntry('Download Failed!', 19, TRUE);
      if ($DownloadErrors) logEntry('Download finished with errors.');
      if ($Verbose) logEntry('Download Complete.'); }

    // / The following code is performed when a user deletes a selection of files.
    if (isset($_POST['filesToDelete'])) {
      logEntry('Initiating Deletor.');
      list ($DeleteComplete, $DeleteErrors) = deleteFiles($FilesToDelete);
      if (!$DeleteComplete) errorEntry('Delete Failed!', 24, TRUE);
      if ($DeleteErrors) logEntry('Delete finished with errors.');
      if ($Verbose) logEntry('Delete Complete.'); }

    // / The following code is performed when a user archives a selection of files.
    // / A refusal prints the same alert a refused conversion prints. It carries no
    // / ERROR!!! tag because nothing failed, so an interface must recognise it by the
    // / alert string. That is what the failureStrings list in the GUI is for.
    if (isset($_POST['filesToArchive'])) {
      if (!takeOperationBudget('archive')) print($Alert3.$Lol);
      else {
        logEntry('Initiating Archiver.');
        list ($ArchiveComplete, $ArchiveErrors, $ArchivedFiles, $ArchiveFindings) = invokeArchiveOperation($FilesToArchive, array('Filename' => $UserFilename, 'Extension' => $UserExtension));
        // / The core prints, & the operation returned, as contract 6 says. The interface reads one line per file.
        foreach ($ArchivedFiles as $ArchivedFile) print($ArchivedFile.$Lol);
        if (!$ArchiveComplete) errorEntry('Archive Failed!', 20, TRUE);
        if ($ArchiveErrors) logEntry('Archive finished with errors.');
        if ($Verbose) logEntry('Archive Complete.'); }
      $BudgetWasReleased = giveBackOperationBudget('archive'); }

    // / The following code is performed when a user extracts a selection of archives.
    // / Extracting is the Extractor pipeline's, from v4.1.7, & what it placed is named one file per line, as an archive request names what it made.
    // / An interface draws its file list again rather than downloading anything, because an archive may hold a great many files.
    // / A refusal prints the alert a refused archive request prints, for the same reason.
    if (isset($_POST['filesToExtract'])) {
      if (!takeOperationBudget('extract')) print($Alert3.$Lol);
      else {
        logEntry('Initiating Extractor.');
        list ($ExtractComplete, $ExtractErrors, $ExtractedFiles, $ExtractFindings) = invokeExtractOperation($FilesToExtract, array());
        // / The core prints, & the operation returned, as contract 6 says. The interface reads one line per file.
        foreach ($ExtractedFiles as $ExtractedFile) print($ExtractedFile.$Lol);
        if (!$ExtractComplete) errorEntry('Extract Failed!', 32, TRUE);
        if ($ExtractErrors) logEntry('Extract finished with errors.');
        if ($Verbose) logEntry('Extract Complete.'); }
      $BudgetWasReleased = giveBackOperationBudget('extract'); }

    // / The following code is performed when a user renames a selection of files.
    // / The name each file now has is printed one per line, as an extraction names what it placed, & an interface draws its file list again.
    // / Renaming reads nothing inside a file, so it takes no budget, as a deletion takes none.
    if (isset($_POST['filesToRename'])) {
      logEntry('Initiating Renamer.');
      list ($RenameComplete, $RenameErrors, $RenamedFiles, $RenameFindings) = invokeRenameOperation($FilesToRename, array('Pattern' => $RenamePattern));
      // / The core prints, & the operation returned, as contract 6 says. The interface reads one line per file.
      foreach ($RenamedFiles as $RenamedFile) print($RenamedFile.$Lol);
      if (!$RenameComplete) errorEntry('Rename Failed!', 33, TRUE);
      if ($RenameErrors) logEntry('Rename finished with errors.');
      if ($Verbose) logEntry('Rename Complete.'); }

    // / The following code is performed when a user strips the metadata from a selection of files.
    // / The clean copy made of each file is printed one per line, & an interface draws its file list again.
    // / A refusal prints the alert a refused archive request prints, for the same reason.
    if (isset($_POST['filesToStrip'])) {
      if (!takeOperationBudget('metadata')) print($Alert3.$Lol);
      else {
        logEntry('Initiating Metadata Scrubber.');
        list ($StripComplete, $StripErrors, $StrippedFiles, $StripFindings) = invokeStripMetadataOperation($FilesToStrip, array('Filename' => $UserFilename));
        // / The core prints, & the operation returned, as contract 6 says. The interface reads one line per file.
        foreach ($StrippedFiles as $StrippedFile) print($StrippedFile.$Lol);
        if (!$StripComplete) errorEntry('Strip Metadata Failed!', 34, TRUE);
        if ($StripErrors) logEntry('Strip Metadata finished with errors.');
        if ($Verbose) logEntry('Strip Metadata Complete.'); }
      $BudgetWasReleased = giveBackOperationBudget('metadata'); }

    // / The following code is performed when a user merges a selection of PDFs into one.
    // / The PDF it made is printed on a line of its own, & an interface draws its file list again.
    if (isset($_POST['filesToMerge'])) {
      if (!takeOperationBudget('merge')) print($Alert3.$Lol);
      else {
        logEntry('Initiating PDF Merger.');
        list ($MergeComplete, $MergeErrors, $MergedFiles, $MergeFindings) = invokeMergeOperation($FilesToMerge, array('Filename' => $UserFilename));
        // / The core prints, & the operation returned, as contract 6 says. The interface reads one line per file.
        foreach ($MergedFiles as $MergedFile) print($MergedFile.$Lol);
        if (!$MergeComplete) errorEntry('Merge Failed!', 35, TRUE);
        if ($MergeErrors) logEntry('Merge finished with errors.');
        if ($Verbose) logEntry('Merge Complete.'); }
      $BudgetWasReleased = giveBackOperationBudget('merge'); }

    // / The following code is performed when a user splits a selection of PDFs into one PDF for each page.
    // / Every page it made is printed one per line, & an interface draws its file list again.
    if (isset($_POST['filesToSplit'])) {
      if (!takeOperationBudget('split')) print($Alert3.$Lol);
      else {
        logEntry('Initiating PDF Splitter.');
        list ($SplitComplete, $SplitErrors, $SplitFiles, $SplitFindings) = invokeSplitOperation($FilesToSplit, array());
        // / The core prints, & the operation returned, as contract 6 says. The interface reads one line per file.
        foreach ($SplitFiles as $SplitFile) print($SplitFile.$Lol);
        if (!$SplitComplete) errorEntry('Split Failed!', 36, TRUE);
        if ($SplitErrors) logEntry('Split finished with errors.');
        if ($Verbose) logEntry('Split Complete.'); }
      $BudgetWasReleased = giveBackOperationBudget('split'); }

    // / The following code is performed when a user converts a selection of files.
    if (isset($_POST['convertSelected'])) {
      if (!takeOperationBudget('conversion')) print($Alert3.$Lol);
      else {
        logEntry('Initiating Converter.');
        list ($ConversionComplete, $ConversionErrors, $ConvertedFiles, $ConversionFindings) = invokeConvertOperation($ConvertSelected, array('Filename' => $UserFilename, 'Extension' => $UserExtension, 'Height' => $Height, 'Width' => $Width, 'Rotate' => $Rotate, 'Bitrate' => $Bitrate));
        // / The core prints, & the operation returned, as contract 6 says. The interface reads one line per file.
        foreach ($ConvertedFiles as $ConvertedFile) print($ConvertedFile.$Lol);
        if (!$ConversionComplete) errorEntry('Conversion Failed!', 21, TRUE);
        if ($ConversionErrors) logEntry('Conversion finished with errors.');
        if ($Verbose) logEntry('Conversion Complete.'); }
      $BudgetWasReleased = giveBackOperationBudget('conversion'); }

    // / The following code is performed when a user performs OCR on a selection of files.
    if (isset($_POST['pdfworkSelected'])) {
      if (!takeOperationBudget('OCR')) print($Alert3.$Lol);
      else {
        logEntry('Initiating OCR.');
        // / OCR is an operation pipeline & is dispatched from here rather than through
        // / convert(). It takes a selection of files rather than one file, it decides its
        // / own route per file, & it returns two values rather than the six a conversion
        // / pipeline returns. Forcing it into the conversion contract would have meant
        // / describing a batch as a single file & discarding most of what it reports.
        // / A missing or mismatched OCR pipeline is reported by the adapter rather than read as an OCR run that produced nothing.
        list ($ConversionComplete, $ConversionErrors, $OcrOutputFilenames, $OcrFindings) = invokeOcrOperation($PDFWorkSelected, array('Filename' => $UserFilename, 'Extension' => $UserExtension, 'Method' => $Method));
        // / The core prints, & the pipeline returned, as contract 6 says.
        // / The interface reads this reply one line per produced file, exactly as it reads the reply from a conversion.
        // / A pipeline never prints these itself, because its output shares a stream with any PHP warning.
        foreach ($OcrOutputFilenames as $OcrOutputFilename) print($OcrOutputFilename.$Lol);
        if (!$ConversionComplete) errorEntry('OCR Operation Failed!', 22, TRUE);
        if ($ConversionErrors) logEntry('OCR Operation finished with errors.');
        if ($Verbose) logEntry('OCR Operation Complete.'); }
      $BudgetWasReleased = giveBackOperationBudget('OCR'); }

    // / The following code is performed when a user identifies a selection of files.
    // / Identify is an operation pipeline & is dispatched as OCR is. It changes no file & may write a report.
    // / Its reply is one line of JSON, because an interface fills in what it shows rather than downloading a file.
    // / A request when the feature is off is refused in words, as a URL download is, because an API caller deserves to know why.
    if (isset($_POST['filesToIdentify'])) {
      if (!$AllowUserIdentify) {
        warningEntry('An identification was requested & the feature is disabled.');
        print('Identifying files is disabled on this server.'.$Lol); }
      else if (!takeOperationBudget('Identify')) print($Alert3.$Lol);
      else {
        logEntry('Initiating Identify.');
        list ($IdentifyComplete, $IdentifyErrors, $IdentifyFiles, $IdentifyResult) = invokeIdentifyOperation($FilesToIdentify, array('Report' => $IdentifyReport, 'Filename' => $IdentifyFilename));
        // / The core prints, & the pipeline returned, as contract 6 says.
        print(encodeIdentifyReply($IdentifyComplete, $IdentifyErrors, $IdentifyResult).$Lol);
        if (!$IdentifyComplete) errorEntry('Identify Failed!', 31, TRUE);
        if ($IdentifyErrors) logEntry('Identify finished with errors.');
        if ($Verbose) logEntry('Identify Complete.'); }
      $BudgetWasReleased = giveBackOperationBudget('Identify'); }

    // / The following code is performed when a user asks the server to fetch a URL.
    // / A URL the operator typed is fetched into their session, & only when an
    // / administrator has turned it on.
    // / This server has no authentication, so an open fetcher is an open proxy & is off by
    // / default. See --Allow User URL Download-- in config.php.
    // / It takes an operation budget like every other unit of work, because a fetch costs
    // / time & bandwidth & a queue of them is a queue.
    if (isset($_POST['userURLDownload']) && trim($UserURLDownload) !== '') {
      if (!$AllowUserURLDownload) {
        warningEntry('A URL download was requested & the feature is disabled.');
        print('Downloading from a URL is disabled on this server.'.$Lol); }
      // / One visitor may fetch a limited number of files per session.
      // / A fetch costs bandwidth & disk whether or not anything is ever converted, so
      // / without a count one visitor can queue as many as they can type.
      // / The tally is a file in the session directory rather than anything held in
      // / memory, because each request is a separate process & a counter that does not
      // / survive the request counts to one forever.
      // / A session ending takes the tally with it, which is the reset.
      else if ((int)$URLDownloadsPerSession > 0 && countSessionURLDownloads($ConvertDir) >= (int)$URLDownloadsPerSession) {
        warningEntry('A session reached its URL download limit of '.(int)$URLDownloadsPerSession.'.');
        print('This session has fetched '.(int)$URLDownloadsPerSession.' file(s), which is the limit.'.$Lol); }
      else if (!takeOperationBudget('user url download')) print($Alert3.$Lol);
      else {
        logEntry('Initiating a user requested download.');
        list ($URLFetchSucceeded, $URLFetchedPath, $URLFetchReason) = fetchUserSuppliedURL($UserURLDownload, $ConvertDir);
        // / Recorded only on success. A refused fetch cost bandwidth & should not also
        // / cost somebody one of their attempts, or a user with a typo runs out of tries
        // / without ever having fetched anything.
        if ($URLFetchSucceeded) countSessionURLDownloads($ConvertDir, TRUE);
        if ($URLFetchSucceeded) logEntry('A user requested download completed. '.$URLFetchReason);
        else warningEntry('A user requested download did not complete. '.$URLFetchReason);
        print($URLFetchReason.$Lol);
        giveBackOperationBudget('user url download'); } }

    // / The following code is performed when a user performs a virus scan on a selection of files.
    if (isset($_POST['filesToScan']) && $AllowUserVirusScan) {
      if (!takeOperationBudget('user virus scan')) print($Alert3.$Lol);
      else {
        logEntry('Initiating User Virus Scanner.');
        list ($ScanComplete, $ScanErrors, $UserVirusFound, $UserScanReportPath, $UserScanReportName, $UserSuspiciousFiles, $UserIncompleteFiles) = userVirusScan($FilesToScan, $UserScanType);
        if (!$ScanComplete) errorEntry('User Virus Scan Failed!', 23, TRUE);
        if ($UserVirusFound) logEntry('The User Virus Scan detected infected files.');
        if (!$UserVirusFound) logEntry('The User Virus Scan did not detect any infected files.');
        if (count($UserSuspiciousFiles) > 0) logEntry('The User Virus Scan found '.count($UserSuspiciousFiles).' suspicious file(s).');
        if (count($UserIncompleteFiles) > 0) logEntry('The User Virus Scan could not read '.count($UserIncompleteFiles).' file(s) in full.');
        if ($ScanErrors) logEntry('User Virus Scan finished with errors.');
        if ($Verbose) logEntry('User Virus Scan Complete.'); }
      $BudgetWasReleased = giveBackOperationBudget('user virus scan'); }

    // / The following code is performed when a page or a caller of the API asks about workflows.
    // / Each answer is one line of JSON under the key Workflow, as Identify answers under its own.
    // / The work is the Workflow Core's. A workflow runs in a worker of its own, so a request here lists, starts, reads or stops one.
    // / A page's builder asks what it may build from, has a workflow checked or a library's workflow given as a document, & runs what it built.
    // / An administrator's page stages what its builder holds for root to install for every user, from v4.2.2. Whether the request is an administrator's is the Engine's to say.
    // / Which files a user may name is this application's policy, so a run & a preview are handed only files this session lists.
    // / A preview names the files each step would take & runs nothing. It is answered before a run is looked for, so a request naming both runs nothing.
    // / A server with workflows off, or without the Workflow Core, answers that none are available, & says why in its log.
    if (isset($_POST['workflowLibrary']) or isset($_POST['workflowRun']) or isset($_POST['workflowDocument']) or isset($_POST['workflowStatus']) or isset($_POST['workflowStop']) or isset($_POST['workflowOperations']) or isset($_POST['workflowCheck']) or isset($_POST['workflowExport']) or isset($_POST['workflowPreview']) or isset($_POST['workflowPreviewDocument']) or isset($_POST['workflowStage'])) {
      if (!$EnableWorkflows or !$WorkflowCoreActive or !function_exists('encodeWorkflowReply')) {
        warningEntry('A workflow was requested & '.($EnableWorkflows ? 'the Workflow Core is unavailable.' : 'workflows are disabled.'));
        print('{"Workflow":{"Available":false}}'.$Lol); }
      else if (isset($_POST['workflowPreview'])) print(encodeWorkflowReply(previewLibraryWorkflow($WorkflowPreview, array_values(array_intersect((array)$WorkflowFiles, getFiles($ConvertDir))))).$Lol);
      else if (isset($_POST['workflowPreviewDocument'])) print(encodeWorkflowReply(previewWorkflowDocument($WorkflowPreviewDocument, array_values(array_intersect((array)$WorkflowFiles, getFiles($ConvertDir))))).$Lol);
      else if (isset($_POST['workflowRun'])) print(encodeWorkflowReply(startWorkflowRun($WorkflowName, array_values(array_intersect((array)$WorkflowFiles, getFiles($ConvertDir))))).$Lol);
      else if (isset($_POST['workflowDocument'])) print(encodeWorkflowReply(startWorkflowDocumentRun($WorkflowDocument, array_values(array_intersect((array)$WorkflowFiles, getFiles($ConvertDir))))).$Lol);
      else if (isset($_POST['workflowStop'])) print(encodeWorkflowReply(stopWorkflowRun()).$Lol);
      else if (isset($_POST['workflowStatus'])) print(encodeWorkflowReply(reportWorkflowStatus()).$Lol);
      else if (isset($_POST['workflowCheck'])) print(encodeWorkflowReply(checkWorkflowDocument($WorkflowCheck)).$Lol);
      else if (isset($_POST['workflowExport'])) print(encodeWorkflowReply(exportWorkflowDocument($WorkflowExport)).$Lol);
      else if (isset($_POST['workflowStage'])) print(encodeWorkflowReply(stageWorkflowForEveryone($WorkflowStage, $WorkflowStageDescriptions)).$Lol);
      else if (isset($_POST['workflowOperations'])) print(encodeWorkflowReply(describeWorkflowOperations()).$Lol);
      else print(encodeWorkflowReply(describeWorkflowLibrary()).$Lol); }

    // / Close the web server connection after all required content has been served, & wait for any detached worker a pipeline handed back.
    // / Nothing below this point may produce any output, because the user has already been served. The Engine's finishWebRequest() does both.
    $WebRequestIsFinished = finishWebRequest(); } }

// / Stop execution of the application, with the status a command line earned.
// / A served web request never sees a status, so it exits 0.
// / A handled command line exits with the status its command set, which is 0, 1 or 2.
exit($CommandLineHandled ? (int)$CommandLineStatus : 0);
// / -----------------------------------------------------------------------------------
?>