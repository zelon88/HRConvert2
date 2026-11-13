<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRConvert2, Copyright on 9/22/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / Application Information ...
// / This application is designed to provide a web-interface for converting file formats on
// / a server for users of any web browser without authentication.
// /
// / File Information ...
// / v4.1.7.
// / The Stream pipeline's converter. pipelineCore.php loads it only to dispatch a Stream conversion.
// / Error block 21000 through 21002 belongs to this pipeline.
// / This is the reference implementation of a detached worker pipeline.
// / It launches FFMPEG in the background & hands back the process ID.
// / The core supervises that process after the connection to the user closes.
// / Read contract 5 in Documentation/ABOUT_PIPELINE_COMPONENTS.txt before copying this.
// / Getting any one of its four pieces wrong orphans a process on the server.
// / The core owns the network, & a pipeline owns its format.
// / Here, because each is knowledge about a format:
// /   inspectTSFile           MPEG-TS is 188 byte packets each starting with 0x47
// /   classifyStreamContent   an #EXTM3U header, or else ask inspectTSFile
// /   inspectStreamFile       what a playlist may reference & what it may not
// /   streamFileWalker        how deep to follow a playlist & when to stop
// / In the Engine, because a pipeline must never own SSRF protection:
// /   isPubliclyRoutableIP            is this address safe to contact
// /   dnsLookup                       resolve a host & judge the result
// /   gatherRemoteHostInfo            parse & resolve a URL into its parts
// /   resolveRemoteURI                resolve a relative reference against a parent
// /   inspectContentForIPs            find IP literals in bytes & flag the reserved ones
// /   inspectContentForDomains        find domain references in bytes
// /   downloadRemoteFileForInspection pinned, redirect refusing, size bounded fetch
// /   waitForStream                   supervise a detached worker of any kind
// / A pipeline that fetches something gets that protection & cannot opt out of it by accident.
// / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt for the contracts this file obeys.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline converter cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to confirm a downloaded file is genuinely MPEG-TS & not something disguised as one.
// / MPEG-TS is a fixed-size packet format: every packet is 188 bytes & begins with sync byte 0x47.
// / Requiring the sync byte at EVERY expected boundary makes a coincidental match effectively impossible.
// / The first five packets are checked, or every whole packet when there are fewer.
// / A segment holds a PAT, a PMT & at least one packet of media, so fewer than three whole packets is not one.
// / The last segment of a stream is often that short.
// / Trailing bytes short of a packet are not judged, because an inspection reads only the start of a segment.
// / Does NOT check the file extension. FFMPEG dispatches on content, so we must too.
// / Note that $MaxStreamInspectionFileSize must stay above ($packetSize * $packetsToCheck).
// / Otherwise every genuine segment will fail this check for the wrong reason.
function inspectTSFile($fileContents) {
  // / Set variables.
  global $EnableMemoryProtection;
  $packetSize = 188;
  $packetsToCheck = 5;
  $packetsRequired = 3;
  $syncByte = "\x47";
  $offset = 0;
  $bytesToCheck = 0;
  $Check = FALSE;
  $bytesToCheck = $packetSize * min($packetsToCheck, intdiv(strlen((string)$fileContents), $packetSize));
  // / A file too short to hold the packets a segment needs cannot be validated, so reject it.
  // / This gate also prevents the loop below from reading past the end of the string.
  if ($bytesToCheck >= $packetSize * $packetsRequired) {
    // / Assume success, then let any single missing sync byte disprove it & stop immediately.
    $Check = TRUE;
    // / Walk the expected packet boundaries & confirm the sync byte appears at every one.
    for ($offset = 0; $offset < $bytesToCheck; $offset += $packetSize) {
      if ($fileContents[$offset] !== $syncByte) {
        $Check = FALSE;
        break; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $packetSize, $packetsToCheck, $packetsRequired, $syncByte, $offset, $bytesToCheck, $fileContents);
  return $Check; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to determine what a stream file actually IS, based only on its content.
// / The filename & extension are deliberately ignored. FFMPEG dispatches on content markers,
// / so a .ts file whose bytes begin with #EXTM3U will be treated by FFMPEG as a playlist.
// / This is the single source of truth for stream file classification. Do not duplicate this logic.
function classifyStreamContent($streamContents) {
  // / Set variables.
  global $EnableMemoryProtection;
  $IsPlaylist = $IsSegment = FALSE;
  // / A playlist must open with the #EXTM3U tag. Ltrim handles a BOM or leading whitespace.
  if (strncmp(ltrim($streamContents), '#EXTM3U', 7) === 0) $IsPlaylist = TRUE;
  // / Only check for MPEG-TS if it is not already a playlist. Nothing can legitimately be both.
  else $IsSegment = inspectTSFile($streamContents);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $streamContents);
  return array($IsPlaylist, $IsSegment); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to scan a single local stream file & determine if it is safe for FFMPEG to handle.
// / $CurrentLayer 0 is the file the user uploaded. Layers 1+ are files HRConvert2 downloaded for inspection.
// / Downloaded files are saved with a numeric name & NO extension on purpose, so extension-based
// / checks apply to layer 0 only. From layer 1 onward, content is the only authority.
// / This function inspects. It does not connect to anything & it does not decide the fate of the walk.
function inspectStreamFile($StreamFile, $ParentURL, $CurrentLayer) {
  // / Set variables.
  global $Verbose, $AllowStreamOverHTTP, $SupportedConversionTypes, $StreamArray, $EnableMemoryProtection;
  $StreamContainsIP = $StreamContainsLAN = $StreamContainsHTTP = $StreamContainsDomain = FALSE;
  $streamFileExtension = $RawURI = $streamFileContents = '';
  $StreamURIs = $DomainMatches = $IPMatches = $streamLineMatches = array();
  $DomainCount = $IPCount = 0;
  $looksLikePlaylist = $looksLikeSegment = $ContentMismatch = $ContentUnknown = $InspectionFailed = FALSE;
  $extensionAllowed = TRUE;
  // / Get the file extension of the $StreamFile.
  $streamFileExtension = getExtension($StreamFile);
  // / Log the start of stream file inspection.
  if ($Verbose) logEntry('Inspecting Stream File '.$StreamFile.' at layer '.$CurrentLayer.' for security risks.');
  // / For sanity, double check that Stream & Audio operations are even allowed in config.php.
  // / Extra precaution is required due to the cost, sensitivity, & potential consequences of this function being abused.
  // / The supported-format check applies to layer 0 only. Files we downloaded have no extension by design.
  if ($CurrentLayer === 0 && !in_array($streamFileExtension, $StreamArray)) $extensionAllowed = FALSE;
  if (in_array('Stream', $SupportedConversionTypes) && in_array('Audio', $SupportedConversionTypes) && $extensionAllowed) {
    // / Read the contents of the stream file.
    $streamFileContents = file_get_contents($StreamFile);
    // / Classify by content. This is the ONLY place that decision is made. See classifyStreamContent().
    list ($looksLikePlaylist, $looksLikeSegment) = classifyStreamContent($streamFileContents);
    // / Determine if the content of the file matches its file extension.
    // / Layer 0 only. A name that disagrees with the content is not a quirk to work around, it is the attack.
    if ($CurrentLayer === 0) {
      if ($looksLikePlaylist && $streamFileExtension !== 'm3u8') $ContentMismatch = TRUE;
      if ($looksLikeSegment && $streamFileExtension !== 'ts') $ContentMismatch = TRUE; }
    // / Neither format. FFMPEG would probe this & pick some demuxer we never anticipated.
    if (!$looksLikePlaylist && !$looksLikeSegment) $ContentUnknown = TRUE;
    // / Any single failure condition ends this inspection immediately.
    if ($ContentMismatch or $ContentUnknown) $InspectionFailed = TRUE;
    // / If the file passed classification, then continue.
    if (!$InspectionFailed) {
      // / Set a flag if the file references any plain-http address. Controlled by config.php.
      if (stripos($streamFileContents, 'http://') !== FALSE) $StreamContainsHTTP = TRUE;
      // / Check the stream file contents for domain names and assemble them into an array.
      list ($DomainMatches, $DomainCount, $StreamContainsDomain) = inspectContentForDomains($streamFileContents);
      // / Check the stream file contents for raw IP addresses and assemble them into an array.
      list ($IPMatches, $IPCount, $StreamContainsLAN, $StreamContainsIP) = inspectContentForIPs($streamFileContents);
      // / Iterate through the $streamFileContents & extract the complete URI from each URI line.
      // / These URIs are raw & completely unvalidated. The walker validates them one at a time.
      foreach (preg_split('/\R/', $streamFileContents) as $streamLine) {
        $streamLine = trim($streamLine);
        // / Skip empty lines in the playlist file.
        if ($streamLine === '') continue;
        // / Non-# lines are URIs. # lines may still carry a URI="" attribute.
        // / #EXT-X-KEY, #EXT-X-MAP & #EXT-X-MEDIA all reference fetchable URIs this way.
        if ($streamLine[0] !== '#') $RawURI = $streamLine;
        elseif (preg_match('/URI="([^"]*)"/i', $streamLine, $streamLineMatches)) $RawURI = $streamLineMatches[1];
        // / A # line with no URI attribute is just a tag. Nothing to inspect.
        else continue;
        // / One record per URI found. Only RawURI, ParentURL & Layer are knowable here.
        // / Everything else is filled in later by the walker as each stage completes.
        $StreamURIs[] = array(
          'RawURI'      => $RawURI,
          'AbsoluteURL' => '',
          'ParentURL'   => $ParentURL,
          'Layer'       => $CurrentLayer,
          'URLHost'     => '',
          'URLPort'     => '',
          'URLScheme'   => '',
          'URLIP'       => '',
          'LocalPath'   => '',
          'IsPlaylist'  => FALSE,
          'IsSegment'   => FALSE,
          'Inspected'   => FALSE,
          'Failed'      => FALSE,
          'FailReason'  => ''); } } }
  // / If the operation is not permitted by config.php, or the extension is not supported, then fail.
  else $InspectionFailed = TRUE;
  // / Any single failure condition fails this file. Flags are one-way & nothing can clear them.
  if ($StreamContainsLAN or $ContentMismatch or $ContentUnknown) $InspectionFailed = TRUE;
  // / A plain-http reference is only fatal when config.php has disabled it.
  if ($StreamContainsHTTP && !$AllowStreamOverHTTP) $InspectionFailed = TRUE;
  // / A LAN reference in an uploaded stream file is always worth an operator seeing.
  if ($StreamContainsLAN) warningEntry('Stream File '.$StreamFile.' references a private, reserved or loopback address.');
  // / Content that disagrees with its own extension is the shape of a disguised file.
  if ($ContentMismatch) warningEntry('Stream File '.$StreamFile.' content does not match its file extension.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $streamFileExtension, $streamLine, $streamFileContents, $DomainMatches, $IPMatches, $streamLineMatches, $RawURI, $extensionAllowed);
  return array($InspectionFailed, $StreamURIs, $StreamContainsLAN, $StreamContainsIP, $StreamContainsHTTP, $looksLikePlaylist, $looksLikeSegment); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The walker. Owns the queue, the seen-set, the depth counter & every budget.
// / Every other stream function answers a question. This one is the only thing that decides to continue.
// / $FileBudget resets each layer because it bounds the width of one layer.
// / $TotalBudget never resets because it bounds the entire tree regardless of shape.
// / $Halt is one-way. Once anything sets it, nothing may clear it.
function streamFileWalker($StreamFile) {
  global $Verbose, $StreamInspectionLayers, $StreamInspectionFilesPerLayer, $DefaultStreamInspectionForfeitAction, $EnableMemoryProtection, $StreamBaseURL;
  // / Set variables.
  $Halt = $StreamBudgetExhausted = $InspectionFailed = $DownloadFailed = $StreamFileTruncated = FALSE;
  $looksLikePlaylist = $looksLikeSegment = $StreamContainsLAN = $StreamContainsIP = $StreamContainsHTTP = FALSE;
  $StreamURLResolutionFailed = $LookupFailed = FALSE;
  $urlHost = $urlPort = $urlScheme = $urlIP = FALSE;
  $SeenURLs = $AllStreamURIs = array();
  $currentLayerFiles = $nextLayerFiles = $streamURIs = $layerFile = $uriRecord = array();
  $currentLayer = $FileNumber = $FileBudget = $index = 0;
  $HaltReason = '';
  $LayerBudget = $StreamInspectionLayers;
  // / Hard ceiling on total connections for the entire walk, regardless of tree shape.
  // / Per-layer budgets bound each file individually. This bounds the whole tree.
  $TotalBudget = $StreamInspectionLayers * $StreamInspectionFilesPerLayer;
  // / Layer 0 is the user's uploaded file. It has no SourceURL because nobody fetched it.
  // / The base address an operator supplied becomes the parent of the FIRST file, which is
  // / the only thing a relative URI in an uploaded playlist can be resolved against.
  // / Empty is the normal case & leaves behaviour exactly as it was.
  // / It is normalized here rather than where it was typed, so the reason a bad one was
  // / refused reaches the log beside the conversion it stopped.
  // / Nothing about being supplied by an operator exempts what it produces. Every URL
  // / resolved against it still goes through gatherRemoteHostInfo & the address filter.
  $baseIsUsable = FALSE;
  $normalizedBase = $baseReason = '';
  if (isset($StreamBaseURL) && trim((string)$StreamBaseURL) !== '') {
    list ($baseIsUsable, $normalizedBase, $baseReason) = normalizeStreamBaseURL($StreamBaseURL);
    if ($baseIsUsable) logEntry('A stream base address was supplied. '.$baseReason);
    else warningEntry('A stream base address was supplied & refused. '.$baseReason); }
  $currentLayerFiles[] = array('LocalPath' => $StreamFile, 'SourceURL' => $normalizedBase);
  // / Walk one whole layer at a time until we run out of layers, work, or patience.
  // / Layer 0 is always walked, because the uploaded file is local & its inspection cannot be disabled.
  // / With no layer to spend, nothing it references is fetched, & the budget check below ends the walk before any connection.
  while (!$Halt && !empty($currentLayerFiles) && ($LayerBudget > 0 or $currentLayer === 0)) {
    $FileBudget = $StreamInspectionFilesPerLayer;
    $nextLayerFiles = array();
    foreach ($currentLayerFiles as $layerFile) {
      if ($Halt) break;
      // / Inspect one local file. Returns the URIs it references & its own local flags.
      list ($InspectionFailed, $streamURIs, $StreamContainsLAN, $StreamContainsIP, $StreamContainsHTTP, $looksLikePlaylist, $looksLikeSegment) = inspectStreamFile($layerFile['LocalPath'], $layerFile['SourceURL'], $currentLayer);
      if ($InspectionFailed) {
        $Halt = TRUE;
        $HaltReason = 'File inspection failed at layer '.$currentLayer.' on '.$layerFile['LocalPath'];
        break; }
      // / A genuine segment is a leaf. It has no URIs & nothing to recurse into, so this branch is done.
      if ($looksLikeSegment) continue;
      // / A file that is neither playlist nor segment should never have passed inspection.
      // / If it somehow did, refuse rather than guessing what FFMPEG would make of it.
      if (!$looksLikePlaylist) {
        $Halt = TRUE;
        $InspectionFailed = TRUE;
        $HaltReason = 'Unclassifiable file passed inspection at layer '.$currentLayer;
        break; }
      // / Early exit. If this file alone exceeds the per-layer budget, the walk can never complete.
      // / Deny now, before spending a single connection on a file that was never going to pass.
      // / With no layer to spend there is no per-layer budget to exceed, so the first reference ends the walk below instead.
      if ($LayerBudget > 0 && count($streamURIs) > $FileBudget) {
        $StreamBudgetExhausted = TRUE;
        $Halt = TRUE;
        $HaltReason = 'File references '.count($streamURIs).' URIs, exceeding the per-layer budget of '.$FileBudget;
        break; }
      foreach ($streamURIs as $index => $uriRecord) {
        // / Resolve relative URIs against the URL this manifest came from.
        $streamURIs[$index]['AbsoluteURL'] = resolveRemoteURI($uriRecord['RawURI'], $uriRecord['ParentURL']);
        // / An empty result means the URI was relative with no parent to inherit from,
        // / or the parent URL itself was unusable. Never guess. Refuse.
        if ($streamURIs[$index]['AbsoluteURL'] === '') {
          $streamURIs[$index]['Failed'] = TRUE;
          $streamURIs[$index]['FailReason'] = 'Unresolvable URI';
          $Halt = TRUE;
          $InspectionFailed = TRUE;
          // / A relative URI in an UPLOADED playlist cannot be resolved & that is not a
          // / fault in the file. A player fetching that playlist from a website resolves
          // / entries against the address it fetched them from, & an upload has no such
          // / address. The reason says so, because Unresolvable URI on its own reads as a
          // / malformed playlist & sends somebody looking for a defect that is not there.
          $HaltReason = 'Unresolvable URI "'.$uriRecord['RawURI'].'" at layer '.$currentLayer.'. It is a relative path & the playlist it came from was uploaded as a file, so the address it was relative TO is not known. Supply a playlist whose entries carry a full https URL.';
          break; }
        // / Skip anything already seen. Prevents cycles from burning the budget.
        // / No budget is refunded here because none was ever spent on this URL.
        if (isset($SeenURLs[$streamURIs[$index]['AbsoluteURL']])) continue;
        $SeenURLs[$streamURIs[$index]['AbsoluteURL']] = TRUE;
        // / Validate scheme, host & DNS. Nothing connects until this passes.
        list ($InspectionFailed, $StreamURLResolutionFailed, $StreamContainsLAN, $LookupFailed, $urlHost, $urlPort, $urlScheme, $urlIP) = gatherRemoteHostInfo($streamURIs[$index]['AbsoluteURL']);
        if ($InspectionFailed) {
          $streamURIs[$index]['Failed'] = TRUE;
          $streamURIs[$index]['FailReason'] = 'Host validation failed';
          $Halt = TRUE;
          $HaltReason = 'Host validation failed for '.$streamURIs[$index]['AbsoluteURL'].' (LAN: '.($StreamContainsLAN ? 'TRUE' : 'FALSE').', Lookup Failed: '.($LookupFailed ? 'TRUE' : 'FALSE').', URL Resolution Failed: '.($StreamURLResolutionFailed ? 'TRUE' : 'FALSE').')';
          break; }
        $streamURIs[$index]['URLHost'] = $urlHost;
        $streamURIs[$index]['URLPort'] = $urlPort;
        $streamURIs[$index]['URLScheme'] = $urlScheme;
        $streamURIs[$index]['URLIP'] = $urlIP;
        // / Spend budget BEFORE connecting, never after.
        if ($FileBudget < 1 or $TotalBudget < 1 or $LayerBudget < 1) {
          $StreamBudgetExhausted = TRUE;
          $Halt = TRUE;
          $HaltReason = ($LayerBudget < 1 ? 'No layer may be fetched, because --Stream Inspection Layers-- is '.(int)$StreamInspectionLayers.', & the uploaded file references '.$streamURIs[$index]['AbsoluteURL'] : 'Budget exhausted before connecting (files remaining this layer: '.$FileBudget.', total remaining: '.$TotalBudget.')');
          break; }
        $FileBudget--;
        $TotalBudget--;
        // / Increment before the call so a failed download still burns its number.
        // / Reusing a number could leave a partial file where the next fetch expects to write.
        $FileNumber++;
        // / Fetch with the pinned IP so CURL cannot re-resolve or follow a redirect we did not validate.
        list ($DownloadFailed, $streamURIs[$index]['LocalPath'], $StreamFileTruncated) = downloadRemoteFileForInspection($streamURIs[$index]['AbsoluteURL'], $urlHost, $urlPort, $urlIP, $urlScheme, $FileNumber);
        if ($DownloadFailed) {
          $streamURIs[$index]['Failed'] = TRUE;
          $streamURIs[$index]['FailReason'] = 'Download failed';
          $Halt = TRUE;
          $InspectionFailed = TRUE;
          $HaltReason = 'Download failed for '.$streamURIs[$index]['AbsoluteURL'];
          break; }
        // / Classify what actually came back. The URI's extension is irrelevant & untrustworthy.
        $downloadedContents = file_get_contents($streamURIs[$index]['LocalPath']);
        list ($streamURIs[$index]['IsPlaylist'], $streamURIs[$index]['IsSegment']) = classifyStreamContent($downloadedContents);
        $downloadedContents = NULL;
        unset($downloadedContents);
        // / Neither format. FFMPEG would probe this & pick some demuxer we never anticipated.
        if (!$streamURIs[$index]['IsPlaylist'] && !$streamURIs[$index]['IsSegment']) {
          $streamURIs[$index]['Failed'] = TRUE;
          $streamURIs[$index]['FailReason'] = 'Unrecognized content';
          $Halt = TRUE;
          $InspectionFailed = TRUE;
          $HaltReason = 'Unrecognized content returned by '.$streamURIs[$index]['AbsoluteURL'];
          break; }
        // / A playlist we only half-read cannot be honestly called inspected. A truncated segment is fine,
        // / because a segment only ever needed its first few packets to be identified.
        if ($streamURIs[$index]['IsPlaylist'] && $StreamFileTruncated) {
          $streamURIs[$index]['Failed'] = TRUE;
          $streamURIs[$index]['FailReason'] = 'Playlist exceeded inspection size limit';
          $Halt = TRUE;
          $InspectionFailed = TRUE;
          $HaltReason = 'Playlist at '.$streamURIs[$index]['AbsoluteURL'].' exceeded the inspection size limit & could only be partially read';
          break; }
        $streamURIs[$index]['Inspected'] = TRUE;
        // / Only playlists have children. Segments are leaves & cost nothing further.
        // / Queue playlists with the URL THEY were fetched from, because that is what
        // / their own relative URIs must resolve against.
        if ($streamURIs[$index]['IsPlaylist']) $nextLayerFiles[] = array(
          'LocalPath' => $streamURIs[$index]['LocalPath'],
          'SourceURL' => $streamURIs[$index]['AbsoluteURL']); }
      // / Preserve this file's records before $streamURIs is overwritten by the next file in the layer.
      $AllStreamURIs = array_merge($AllStreamURIs, $streamURIs); }
    // / This layer is finished. Advance to whatever it queued up.
    $currentLayerFiles = $nextLayerFiles;
    $currentLayer++;
    $LayerBudget--; }
  // / Ran out of layers with work still pending. The inspection is incomplete either way.
  if (!empty($currentLayerFiles) && $LayerBudget < 1) {
    $StreamBudgetExhausted = TRUE;
    if ($HaltReason === '') $HaltReason = 'Layer budget exhausted with '.count($currentLayerFiles).' file(s) still uninspected'; }
  // / Apply the configured forfeit action to any incomplete inspection.
  if ($StreamBudgetExhausted && $DefaultStreamInspectionForfeitAction === 'DENY') $InspectionFailed = TRUE;
  // / Log the outcome of the entire walk, which is reported here & nowhere else.
  // / A denied walk is always logged, because an operator needs to know a stream file was refused.
  // / A clean walk is logged only under verbose logging.
  // / A refusal is not a failure of HRConvert2, & may be an ordinary file with relative URIs.
  if ($InspectionFailed) warningEntry('Stream Walk Result: DENIED, Layers Walked: '.$currentLayer.', Files Downloaded: '.$FileNumber.', URIs Examined: '.count($AllStreamURIs).', Unique URLs Seen: '.count($SeenURLs).', Budget Exhausted: '.($StreamBudgetExhausted ? 'TRUE' : 'FALSE').', Reason: '.($HaltReason === '' ? 'NONE' : $HaltReason).'.');
  else if ($Verbose) logEntry('Stream Walk Result: ALLOWED, Layers Walked: '.$currentLayer.', Files Downloaded: '.$FileNumber.', URIs Examined: '.count($AllStreamURIs).', Unique URLs Seen: '.count($SeenURLs).', Budget Exhausted: '.($StreamBudgetExhausted ? 'TRUE' : 'FALSE').', Reason: '.($HaltReason === '' ? 'NONE' : $HaltReason).'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $layerFile & $uriRecord hold whole records including validated IPs & local paths, so they matter most here.
  purgeSensitiveMemory($EnableMemoryProtection, $baseIsUsable, $normalizedBase, $baseReason, $looksLikePlaylist, $looksLikeSegment, $currentLayerFiles, $nextLayerFiles, $streamURIs, $layerFile, $uriRecord, $currentLayer, $index, $urlHost, $urlPort, $urlScheme, $urlIP);
  return array($InspectionFailed, $StreamBudgetExhausted, $HaltReason, $AllStreamURIs, $SeenURLs); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert stream formats.
// / The stream file is fully inspected before FFMPEG is allowed anywhere near it.
// / FFMPEG is launched in the background so the user can be served immediately.
// / The manifest's FFMPEG minimum is 6.1.
// / Builds from 2.0 through 6.0 whitelist nested playlist segments themselves, which inspection cannot protect.
function convertStreams($pathname, $newPathname) {
  // / Set variables.
  global $Verbose, $StreamConnectionTimeout, $AllowStreamOverHTTP, $EnableMemoryProtection, $DirSep;
  $ConversionSuccess = $ConversionErrors = FALSE;
  $ffmpegVersionIsValid = $inspectionFailed = $streamBudgetExhausted = FALSE;
  $allStreamURIs = $seenURLs = array();
  $haltReason = $httpString = $returnData = $ffmpegCommand = $pinnedHosts = $pinnedHostsFile = '';
  $commandMayRun = FALSE;
  $streamRecord = array();
  // / The six value pipeline contract. A stream is the reference case for a pipeline whose
  // / dependency outlives the request, so it is the one that returns a non zero worker PID.
  // / The core derives $WaitForStream from that PID rather than from a separate return value.
  $StreamPID = 0;
  $Extension = getExtension($newPathname);
  $OutputFilename = basename($newPathname);
  if ($Verbose) logEntry('Beginning stream conversion for '.$pathname.'.');
  // / Confirm the installed FFMPEG is not one of the builds that ignores our protocol whitelist.
  $ffmpegVersionIsValid = (verifiedToolPath('FFMPEG') !== FALSE);
  if (!$ffmpegVersionIsValid) {
    $ConversionErrors = TRUE;
    errorEntry('The installed FFMPEG version is missing, unidentifiable, or vulnerable to stream playlist protocol bypass!', 21002, FALSE); }
  else {
    // / Inspect the entire stream tree BEFORE FFMPEG is permitted to touch it.
    // / Nothing below this point runs unless the walk returned a clean verdict.
    list ($inspectionFailed, $streamBudgetExhausted, $haltReason, $allStreamURIs, $seenURLs) = streamFileWalker($pathname);
    if ($inspectionFailed) {
      $ConversionErrors = TRUE;
      errorEntry('Stream inspection denied this file. '.$haltReason.'.', 21001, FALSE); }
    // / The inspection returned a clean verdict, so FFMPEG may now be permitted to run.
    else {
      // / Only widen the protocol whitelist to plain http when config.php explicitly allows it.
      if ($AllowStreamOverHTTP) $httpString = ',http';
      // / Launch FFMPEG in the background & capture its PID so waitForStream() can reap it later.
      // / -rw_timeout is an INPUT option & must appear before -i to have any effect.
      // / The connection timeout is documented in seconds & is converted to microseconds here.
      $ffmpegCommand = 'ffmpeg -protocol_whitelist '.escapeshellarg('file,https,tcp,tls,crypto'.$httpString)
        .' -rw_timeout '.((int)$StreamConnectionTimeout * 1000000)
        .' -i '.escapeshellarg($pathname)
        .' -c copy '.escapeshellarg($newPathname);
      // / This command is sandboxed, & bwrap is PID 1 in the PID namespace --unshare-all creates.
      // / When bwrap dies the kernel kills every process inside, & it lives exactly as long as its child.
      // / Its identifier is the right handle, so waitForStream & terminateWorkerProcess work unchanged.
      // / The network is granted, because a stream is fetched from a remote address.
      // / The namespace binds the resolver configuration only when the network is granted.
      // / The playlist was resolved & refused unless publicly routable, then fetched & inspected for addresses.
      // / The protocol whitelist permits nothing this application did not name, & the sandbox adds to those guards.
      // / Every name the walker approved is pinned to the address it resolved to.
      // / FFMPEG is given no resolver, so this file is the only lookup it can make.
      // / A name the walker did not see cannot resolve, which stops a redirect it never inspected.
      // / The real name is still used on the wire, so TLS validates.
      // / Only the walker's own answers for URIs it did not refuse are written here.
      $pinnedHosts = '';
      foreach ($allStreamURIs as $streamRecord) {
        if (!isset($streamRecord['URLHost'], $streamRecord['URLIP'])) continue;
        if ((string)$streamRecord['URLHost'] === '' or (string)$streamRecord['URLIP'] === '') continue;
        // / The address is judged by the rule the walker judged it by, so a range an administrator permitted is pinned too.
        if (!addressMayBeReached((string)$streamRecord['URLIP'])[0]) continue;
        // / The host is checked again here against what a hostname may contain, & it has
        // / already been through sanitize() in the Engine.
        // / This file has LINE BASED syntax, so a newline inside a hostname would add a
        // / mapping nobody wrote & point any name at any address. The address is already
        // / proven by addressMayBeReached above, so this is the half that was not.
        // / A value crossing out of one component & into a file format another tool parses
        // / is worth proving where it lands rather than trusting where it came from.
        if (!preg_match('/^[a-z0-9]([a-z0-9.-]{0,252}[a-z0-9])?$/i', (string)$streamRecord['URLHost'])) continue;
        redeclare($pinnedHosts, $pinnedHosts.(string)$streamRecord['URLIP'].' '.(string)$streamRecord['URLHost'].PHP_EOL); }
      // / The file is written to the fetch folder under a name of its own, so no file of the user's is overwritten & two conversions never share one.
      // / bwrap reads it as it starts, which is after the shell has handed back the job's identifier, so it is left for the session's cleanup rather than removed here.
      $pinnedHostsFile = networkPolicy()['FetchTemp'].$DirSep.'pinned-'.bin2hex(random_bytes(8)).'.hosts';
      @file_put_contents($pinnedHostsFile, $pinnedHosts);
      // / The playlist is the input & is bound read only.
      // / The segments it names are fetched over the granted network, by the addresses pinned above.
      // / sandboxCommand returns whether the command may run & the command, in that order.
      list ($commandMayRun, $ffmpegCommand) = sandboxCommand($ffmpegCommand, $pathname, $newPathname, TRUE, 'ffmpeg', '', array(), $pinnedHostsFile);
      // / The command is backgrounded OUTSIDE the sandbox, so the identifier captured is the
      // / one the shell started, which is bwrap.
      $ffmpegCommand = $ffmpegCommand.' > /dev/null 2>&1 & echo $!';
      // / A command that may not run is not run, & the launch check below reports it.
      if ($commandMayRun) $returnData = shell_exec($ffmpegCommand);
      else warningEntry('The stream converter was not launched, because no sandbox could be built.');
      $StreamPID = (int)trim($returnData);
      // / A PID of 0 means the process never started at all.
      if ($StreamPID > 0) {
        // / A pipeline that hands back a PID reports success on LAUNCH, never on completion.
        $ConversionSuccess = TRUE;
        if ($Verbose) logEntry('FFMPEG launched in background as PID '.$StreamPID.' for '.$newPathname.'.'); }
      else {
        $ConversionErrors = TRUE;
        errorEntry('The stream converter failed to launch!', 21000, FALSE); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $newPathname is not purged, because it is a return value.
  purgeSensitiveMemory($EnableMemoryProtection, $pinnedHosts, $pinnedHostsFile, $streamRecord, $returnData, $ffmpegCommand, $commandMayRun, $httpString, $allStreamURIs, $seenURLs, $haltReason, $streamBudgetExhausted, $inspectionFailed, $ffmpegVersionIsValid, $pathname);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $Extension, $OutputFilename, $StreamPID); }
// / -----------------------------------------------------------------------------------

?>
