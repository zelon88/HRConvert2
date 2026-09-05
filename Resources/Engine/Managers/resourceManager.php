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
// / v3.9.0.
// / This file is the Resource Manager. It is a manager subcomponent of the Engine.
// / The Resource Manager polls the host, calculates the budget every operation is granted from & sweeps anything that has outlived its welcome.
// /
// / A manager is a role a long lived process runs under rather than a file the application
// / loads on demand. It has a lifetime measured in days, a socket & a place in a budget.
// / This file holds what this role DOES. Everything any role might need is in the Engine.
// / The sockets, the message protocol, the budget arithmetic, the worker registry & the
// / process identity checks are all Engine machinery & are shared by every role.
// / A second application using this Engine gets all of that unchanged & writes only the
// / loop that is its own.
// /
// / This file is pinned EXACTLY by getAcceptedManagers() in the Engine & is loaded by
// / loadEngineManager() when a process is dispatched to this role. It is never loaded on
// / a request that is not running as this manager.
// /
// / See Documentation/ABOUT_MANAGERS.txt for what each role is responsible for.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by an application.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-35000, A manager subcomponent cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The version of this manager. Read by the Engine WITHOUT executing this file.
$ManagerVersion = 'v3.9.0';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to run the Resource Manager loop.
// / Accepts no arguments & does not return until the process is terminated.
// / Returns TRUE when the loop exited cleanly.
// / Resource Manager owns the budget. Nothing else may adjust it.
function runResourceManager() {
  // / Set variables.
  global $ResourcePollInterval, $WorkerReapInterval, $WorkerStaleGracePeriod, $StorageCleanupInterval, $DeleteThreshold, $ManagerSocketTimeout, $ManagerMessageBatchSize, $Verbose, $EnableMemoryProtection;
  $ResourceManagerExitedCleanly = FALSE;
  $socketServer = FALSE;
  $serverIsOpen = $keepRunning = $resourcesPolled = $budgetCalculated = FALSE;
  $requestApproved = $workerWasRegistered = $workerWasReleased = $extensionApproved = FALSE;
  $sweepCompleted = $messageWasDelivered = $limitsWereScaled = FALSE;
  $scaledLimits = array();
  $socketPath = $denialReason = $budgetToken = $sessionConvertLoc = $staleToken = '';
  $systemResources = $resourceBudget = $workerRegistry = $sessionLocations = array();
  $managerMessages = $managerConnections = $replyPayload = $staleWorkers = $staleRecord = $workerRecord = $killReply = array();
  $messagesReceived = $messageIndex = $allocatedBudget = $newExpectedRuntime = 0;
  $lastPollTime = $lastReapTime = $lastSweepTime = $locationsSwept = $entriesDropped = 0;
  $environmentIsReady = $dataIsProtected = FALSE;
  $environmentFindings = array();
  $exposureStatus = $exposureDetail = '';
  $socketPath = buildManagerSocketPath('resource-manager');
  list ($serverIsOpen, $socketServer) = openManagerSocketServer($socketPath);
  if (!$serverIsOpen) errorEntry('The Resource Manager could not open its socket!', 31002, TRUE);
  else {
    logEntry('Resource Manager started. Budget, worker registry & session map are held in memory.');
    // / A session map entry is kept a little longer than a session can survive a sweep.
    $keepRunning = TRUE;
    while ($keepRunning) {
      // / Repoll the host on the configured interval & recompute from the live registry.
      if ((time() - $lastPollTime) >= (int)$ResourcePollInterval) {
        $allocatedBudget = 0;
        foreach ($workerRegistry as $workerRecord) $allocatedBudget = $allocatedBudget + (int)$workerRecord['ConversionCost'];
        list ($resourcesPolled, $systemResources) = pollSystemResources();
        list ($budgetCalculated, $resourceBudget) = calculateResourceBudget($systemResources, $allocatedBudget);
        if (!$resourcesPolled) warningEntry('The Resource Manager could not poll system resources. The budget was not adjusted.');
        if ($Verbose && $budgetCalculated) logEntry('Resource Manager recalculated the budget. Load '.$systemResources['LoadPercentage'].'%, memory '.$systemResources['MemoryUsedPercentage'].'%, remaining '.$resourceBudget['RemainingBudget'].' of '.$resourceBudget['TotalBudget'].', tracked '.count($workerRegistry).'.');
        $lastPollTime = time(); }
      // / Find what has outlived its runtime & hand each one to the Worker Manager.
      if ((time() - $lastReapTime) >= (int)$WorkerReapInterval) {
        list ($sweepCompleted, $staleWorkers) = findStaleWorkers($workerRegistry, (int)$WorkerStaleGracePeriod);
        foreach ($staleWorkers as $staleToken => $staleRecord) {
          warningEntry('Reaping worker '.$staleRecord['WorkerPid'].'. '.$staleRecord['StaleReason'].'.');
          sendManagerMessage(buildManagerSocketPath('worker-manager'), array('RequestType' => 'kill', 'WorkerPid' => (int)$staleRecord['WorkerPid']), 'internal', (int)$ManagerSocketTimeout);
          releaseTrackedWorker($workerRegistry, $staleToken); }
        // / Bound the session map so a listener that runs for months does not grow forever.
        $entriesDropped = evictExpiredSessionLocations($sessionLocations, ((int)$DeleteThreshold * 60) + 3600);
        if ($Verbose && $entriesDropped > 0) logEntry('Dropped '.$entriesDropped.' expired session location entr(ies). '.count($sessionLocations).' remain.');
        $lastReapTime = time(); }
      // / Sweep every configured data location on the storage interval.
      if ((int)$StorageCleanupInterval > 0 && (time() - $lastSweepTime) >= (int)$StorageCleanupInterval) {
        list ($sweepCompleted, $locationsSwept) = cleanEveryConvertLoc();
        if (!$sweepCompleted) warningEntry('A scheduled data location sweep did not complete cleanly.');
        else logEntry('Scheduled sweep completed across '.$locationsSwept.' data location(s).');
        // / Check the bootstrap script on the same schedule. A script left behind by an
        // / older release hands arguments to a core that may no longer accept them.
        verifyBootstrapScript();
        // / Revalidate the operating environment on the same schedule. A package upgrade, a
        // / kernel change or an administrator editing a policy can break the sandbox under a
        // / listener that has been running for weeks, & nothing else would notice until a
        // / conversion failed for a reason that looked unrelated.
        // / This REPORTS only. The listener runs as the web server user & cannot repair.
        list ($environmentIsReady, $environmentFindings) = validateOperatingEnvironment();
        if (!$environmentIsReady) warningEntry('The operating environment has degraded. The sandbox is unavailable & conversions requiring it will be refused. Run the -fp argument as root.');
        else if ($Verbose) logEntry('Operating environment revalidated. '.count($environmentFindings).' check(s) passed.');
        // / Re-prove that the web served DATA tree still hands out inert downloads.
        // /
        // / This is not a check that can be done once at install time.
        // / The protection lives in web server configuration, & web server configuration is
        // / edited by people & replaced by package upgrades long after this application was
        // / installed. Turning AllowOverride off, moving the vhost, adding a reverse proxy
        // / that drops response headers, or migrating from Apache to nginx each re-open this
        // / silently. There is no error, no failed conversion & nothing in any log; the only
        // / observable difference is that an uploaded SVG starts executing again.
        // / So it is re-established on the same schedule as everything else that can rot,
        // / by asking the server rather than by reading a file off the disk.
        // /
        // / This REPORTS only, like every other check in this block. The listener runs as
        // / the web server user, cannot edit server configuration & must not try.
        // / The exposure check is part of validateOperatingEnvironment() above & is not run
        // / a second time here. Its status is read back out of the findings so the listener
        // / can say the right thing about it, because the three outcomes need three
        // / different sentences & a degraded environment warning covers none of them.
        $exposureStatus = environmentFindingStatus($environmentFindings, 'DATA exposure');
        if ($exposureStatus === 'EXPOSED') warningEntry('The DATA directory is EXPOSED. Uploaded files are served as renderable documents rather than as downloads. Run the -fp argument as root & apply the rules in the server configuration. See Documentation/ABOUT_DATA_DIRECTORY_PROTECTION.txt.');
        // / A broken tree is an outage rather than an exposure & is worth saying so plainly.
        // / Every download & every share link is failing while this is true.
        else if ($exposureStatus === 'BROKEN') warningEntry('The DATA directory is returning a server error, so no download & no share link works. Check the web server error log for a line naming DATA/.htaccess. Deleting that file restores service.');
        else if ($exposureStatus !== 'ok') warningEntry('The exposure of the DATA directory could not be established. It reported '.$exposureStatus.'.');
        else if ($Verbose) logEntry('DATA directory exposure revalidated. The tree serves inert content.');
        $lastSweepTime = time(); }
      list ($messagesReceived, $managerMessages, $managerConnections) = receiveManagerMessages($socketServer, 'internal', (int)$ManagerMessageBatchSize, (int)$ManagerSocketTimeout);
      $messageIndex = 0;
      while ($messageIndex < $messagesReceived) {
        $replyPayload = array('Approved' => FALSE, 'Reason' => 'Unrecognized resource request.');
        if (isset($managerMessages[$messageIndex]['RequestType'])) {
          // / A data location request. Sticky for the life of the session.
          if ($managerMessages[$messageIndex]['RequestType'] === 'convertloc') {
            $sessionConvertLoc = resolveSessionLocation($sessionLocations, ($managerMessages[$messageIndex]['DailyHash'] ?? ''), ($managerMessages[$messageIndex]['SessionHash'] ?? ''));
            $replyPayload = array('Approved' => TRUE, 'Reason' => 'Resolved.', 'ConvertLoc' => $sessionConvertLoc); }
          // / A budget request. Approve, register & issue a token the worker returns later.
          else if ($managerMessages[$messageIndex]['RequestType'] === 'budget') {
            list ($requestApproved, $denialReason) = evaluateBudgetRequest($resourceBudget, $workerRegistry, ($managerMessages[$messageIndex]['ConversionCost'] ?? 0), ($managerMessages[$messageIndex]['ExpectedRuntime'] ?? 0));
            if (!$requestApproved) $replyPayload = array('Approved' => FALSE, 'Reason' => $denialReason);
            else {
              list ($workerWasRegistered, $budgetToken) = registerTrackedWorker($workerRegistry, ($managerMessages[$messageIndex]['WorkerPid'] ?? 0), ($managerMessages[$messageIndex]['ConversionCost'] ?? 0), ($managerMessages[$messageIndex]['ExpectedRuntime'] ?? 0));
              if (!$workerWasRegistered) $replyPayload = array('Approved' => FALSE, 'Reason' => 'The worker registry could not be updated.');
              else {
                list ($limitsWereScaled, $scaledLimits) = scaleConversionLimits($systemResources, count($workerRegistry));
                $replyPayload = array('Approved' => TRUE, 'Reason' => 'Approved.', 'BudgetToken' => $budgetToken, 'Limits' => ($limitsWereScaled ? $scaledLimits : array()));
                if ($Verbose) logEntry('Resource Manager approved worker '.(int)($managerMessages[$messageIndex]['WorkerPid'] ?? 0).' at cost '.(int)($managerMessages[$messageIndex]['ConversionCost'] ?? 0).'. Token '.$budgetToken.'.'); } } }
          // / A completion notice. The budget is reclaimed immediately.
          else if ($managerMessages[$messageIndex]['RequestType'] === 'release') {
            $workerWasReleased = releaseTrackedWorker($workerRegistry, ($managerMessages[$messageIndex]['BudgetToken'] ?? ''));
            $replyPayload = array('Approved' => $workerWasReleased, 'Reason' => $workerWasReleased ? 'Released.' : 'That budget token is not tracked.');
            if ($Verbose) logEntry('Resource Manager released a token. Reclaimed: '.($workerWasReleased ? 'yes' : 'no').'. Tracked now '.count($workerRegistry).'.'); }
          // / A runtime extension, against the configured ceiling & extension count.
          else if ($managerMessages[$messageIndex]['RequestType'] === 'extend') {
            list ($extensionApproved, $newExpectedRuntime) = extendTrackedWorker($workerRegistry, ($managerMessages[$messageIndex]['BudgetToken'] ?? ''), ($managerMessages[$messageIndex]['RequestedSeconds'] ?? 0));
            $replyPayload = array('Approved' => $extensionApproved, 'Reason' => $extensionApproved ? 'Extended.' : 'The extension was refused.', 'ExpectedRuntime' => $newExpectedRuntime); }
          // / A command line request for live figures. Nothing on disk can answer this.
          else if ($managerMessages[$messageIndex]['RequestType'] === 'status') {
            list ($limitsWereScaled, $scaledLimits) = scaleConversionLimits($systemResources, count($workerRegistry));
            $replyPayload = array('Approved' => TRUE, 'Reason' => 'Reported.', 'TrackedWorkers' => count($workerRegistry), 'SessionLocations' => count($sessionLocations), 'Budget' => $resourceBudget, 'Limits' => ($limitsWereScaled ? $scaledLimits : array())); }
          // / A command line request to end every tracked worker.
          else if ($managerMessages[$messageIndex]['RequestType'] === 'kill-tracked') {
            $replyPayload = array('Approved' => TRUE, 'Reason' => 'Ended '.killTrackedWorkers($workerRegistry).' tracked worker(s).'); } }
        replyToManagerMessage($managerConnections[$messageIndex], $replyPayload, 'internal');
        $messageIndex++; } }
    $ResourceManagerExitedCleanly = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $socketServer, $serverIsOpen, $keepRunning, $resourcesPolled, $budgetCalculated, $requestApproved, $workerWasRegistered, $workerWasReleased, $extensionApproved, $sweepCompleted, $messageWasDelivered, $socketPath, $denialReason, $budgetToken, $sessionConvertLoc, $staleToken, $systemResources, $resourceBudget, $workerRegistry, $sessionLocations, $managerMessages, $managerConnections, $replyPayload, $staleWorkers, $staleRecord, $workerRecord, $killReply, $scaledLimits, $limitsWereScaled, $environmentIsReady, $environmentFindings, $dataIsProtected, $exposureStatus, $exposureDetail, $messagesReceived, $messageIndex, $allocatedBudget, $newExpectedRuntime, $lastPollTime, $lastReapTime, $lastSweepTime, $locationsSwept, $entriesDropped);
  return $ResourceManagerExitedCleanly; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to scale the configured per conversion limits against current load.
// / Accepts the polled resource array & the number of workers already tracked, in that order.
// / Returns a success boolean & a table of conversion type to limit string, in that order.
// / A busy host hands out smaller ceilings, so twenty conversions cannot each claim the
// / share that was safe for one. Nothing is ever scaled below the configured minimum.
// / The table is handed to the worker with its budget approval & is used for every file in
// / that request, so a conversion never has to ask again mid batch.
function scaleConversionLimits($systemResources, $trackedWorkers) {
  // / Set variables.
  global $MaximumPerConversionResources, $DefaultPerConversionResources, $MinimumPerConversionResources, $Verbose, $EnableMemoryProtection;
  $LimitsWereScaled = FALSE;
  $ScaledLimits = array();
  $conversionType = $limitString = '';
  $limitIsValid = $minimumIsValid = FALSE;
  $cpuPercentage = $memoryMegabytes = $minimumCpu = $minimumMemory = 0;
  $scaledCpu = $scaledMemory = $concurrentShare = 0;
  $highestPressure = $pressureFactor = 0.0;
  $limitTable = array();
  list ($minimumIsValid, $minimumCpu, $minimumMemory) = parseConversionLimit((string)$MinimumPerConversionResources);
  if (!$minimumIsValid) {
    $minimumCpu = 10;
    $minimumMemory = 128;
    warningEntry('The configured minimum per conversion resources could not be read. Falling back to 10% and 128M.'); }
  // / Whichever of load or memory is under more pressure governs the reduction.
  $highestPressure = 0.0;
  if (is_array($systemResources)) $highestPressure = max((float)$systemResources['LoadPercentage'], (float)$systemResources['MemoryUsedPercentage']);
  if ($highestPressure > 95) $highestPressure = 95;
  if ($highestPressure < 0) $highestPressure = 0;
  // / A conversion already in flight has taken a share, so the next one is offered less.
  $concurrentShare = max(1, (int)$trackedWorkers + 1);
  $pressureFactor = (1 - ($highestPressure / 100)) / $concurrentShare;
  // / Build the table from the configured maxima, plus the default under its own key.
  $limitTable = is_array($MaximumPerConversionResources) ? $MaximumPerConversionResources : array();
  $limitTable['Default'] = (string)$DefaultPerConversionResources;
  foreach ($limitTable as $conversionType => $limitString) {
    list ($limitIsValid, $cpuPercentage, $memoryMegabytes) = parseConversionLimit((string)$limitString);
    if (!$limitIsValid) warningEntry('The configured per conversion limit for '.$conversionType.' could not be read & was left out of the table.');
    else {
      $scaledCpu = (int)floor($cpuPercentage * $pressureFactor);
      $scaledMemory = (int)floor($memoryMegabytes * $pressureFactor);
      // / The floor is what stops a loaded server handing out a ceiling nothing can run in.
      if ($scaledCpu < $minimumCpu) $scaledCpu = $minimumCpu;
      if ($scaledMemory < $minimumMemory) $scaledMemory = $minimumMemory;
      // / A scaled ceiling never exceeds the configured maximum.
      if ($scaledCpu > $cpuPercentage) $scaledCpu = $cpuPercentage;
      if ($scaledMemory > $memoryMegabytes) $scaledMemory = $memoryMegabytes;
      $ScaledLimits[$conversionType] = $scaledCpu.','.$scaledMemory; } }
  if (!empty($ScaledLimits)) $LimitsWereScaled = TRUE;
  if ($Verbose && $LimitsWereScaled) logEntry('Scaled per conversion limits for '.count($ScaledLimits).' type(s). Pressure '.round($highestPressure, 1).'%, in flight '.(int)$trackedWorkers.'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $ScaledLimits is not purged, because it is a return value.
  purgeSensitiveMemory($EnableMemoryProtection, $conversionType, $limitString, $limitIsValid, $minimumIsValid, $cpuPercentage, $memoryMegabytes, $minimumCpu, $minimumMemory, $scaledCpu, $scaledMemory, $concurrentShare, $highestPressure, $pressureFactor, $limitTable, $systemResources, $trackedWorkers);
  return array($LimitsWereScaled, $ScaledLimits); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to validate the bootstrap script & disable it when it no longer matches.
// / Accepts no arguments.
// / Returns a validity boolean & a status word, in that order.
// / The status is 'ok', 'disabled', 'absent', 'unreadable' or 'unwritable'.
// / The bootstrap script hands arguments to a core it does not understand. A script from
// / another release may pass an argument this core has dropped, or fail to pass one it now
// / needs, & the operator running it has no way to know that has happened.
// / When the versions differ the DisabledByCore line is rewritten, so the script refuses to
// / run & says why. It is never deleted & is not otherwise altered, so an operator can read
// / exactly what was disabled & reinstall the matching one.
// / A script that matches is left alone, including one disabled earlier that has since been
// / replaced with a matching version, which is re-enabled.
function verifyBootstrapScript() {
  // / Set variables.
  global $InstLoc, $DirSep, $RequiredConfigScript, $Verbose, $EnableMemoryProtection;
  $ScriptIsValid = FALSE;
  $ScriptStatus = 'absent';
  $scriptPath = $scriptContents = $updatedContents = $detectedVersion = $cleanDetected = $cleanRequired = '';
  $versionMatches = array();
  $bytesWritten = 0;
  $scriptIsDisabled = FALSE;
  $scriptPath = $InstLoc.$DirSep.'Documentation'.$DirSep.'Build'.$DirSep.'hrconvert2-setup.sh';
  if (!file_exists($scriptPath)) {
    // / No script is not a fault. An installation that never used one is perfectly normal.
    $ScriptIsValid = TRUE;
    $ScriptStatus = 'absent'; }
  else {
    $scriptContents = (string)@file_get_contents($scriptPath);
    if ($scriptContents === '') $ScriptStatus = 'unreadable';
    else {
      if (preg_match('/^SCRIPT_VERSION="([^"]+)"/m', $scriptContents, $versionMatches)) $detectedVersion = $versionMatches[1];
      $scriptIsDisabled = (preg_match('/^DisabledByCore="TRUE"/m', $scriptContents) === 1);
      $cleanDetected = ltrim(trim($detectedVersion), 'vV');
      $cleanRequired = ltrim(trim((string)$RequiredConfigScript), 'vV');
      // / A script that matches is left alone, & is re-enabled if it was disabled before.
      if ($cleanDetected !== '' && $cleanDetected === $cleanRequired) {
        $ScriptIsValid = TRUE;
        $ScriptStatus = 'ok';
        if ($scriptIsDisabled) {
          $updatedContents = preg_replace('/^DisabledByCore="TRUE"/m', 'DisabledByCore="FALSE"', $scriptContents, 1);
          $bytesWritten = @file_put_contents($scriptPath, $updatedContents);
          if ($bytesWritten === strlen($updatedContents)) warningEntry('The bootstrap script now matches this core & was re-enabled.');
          else warningEntry('The bootstrap script matches this core but could not be re-enabled. Edit DisabledByCore by hand.'); } }
      // / A script that does not match is disabled where it stands.
      else if ($scriptIsDisabled) $ScriptStatus = 'disabled';
      else {
        $updatedContents = preg_replace('/^DisabledByCore="FALSE"/m', 'DisabledByCore="TRUE"', $scriptContents, 1);
        if ($updatedContents === $scriptContents) {
          $ScriptStatus = 'unwritable';
          warningEntry('The bootstrap script reports v'.($cleanDetected === '' ? 'none' : $cleanDetected).' & this core requires v'.$cleanRequired.', but it carries no DisabledByCore line to set. Remove it by hand.'); }
        else {
          $bytesWritten = @file_put_contents($scriptPath, $updatedContents);
          if ($bytesWritten !== strlen($updatedContents)) {
            $ScriptStatus = 'unwritable';
            warningEntry('The bootstrap script reports v'.($cleanDetected === '' ? 'none' : $cleanDetected).' & this core requires v'.$cleanRequired.', but it could not be disabled. Remove it by hand.'); }
          else {
            $ScriptStatus = 'disabled';
            warningEntry('The bootstrap script reports v'.($cleanDetected === '' ? 'none' : $cleanDetected).' & this core requires v'.$cleanRequired.'. It has been disabled. Reinstall the script from the matching release.'); } } } } }
  if ($Verbose) logEntry('Bootstrap Script: '.$scriptPath.', Reports: '.($detectedVersion === '' ? 'none' : $detectedVersion).', Status: '.$ScriptStatus.'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $scriptPath, $scriptContents, $updatedContents, $detectedVersion, $cleanDetected, $cleanRequired, $versionMatches, $bytesWritten, $scriptIsDisabled);
  return array($ScriptIsValid, $ScriptStatus); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to sweep every configured data location on a schedule.
// / Accepts no arguments.
// / Returns a completion boolean & the number of locations swept, in that order.
// / This uses cleanDataLoc, the same function the stock clean calls use. Those calls remain
// / in convertCore.php & are what a standalone installation with no listener relies on.
// / Only the shared data locations are swept. The temporary location lives inside the
// / installation directory, so it belongs to one front end & each one cleans its own.
function cleanEveryConvertLoc() {
  // / Set variables.
  global $DeleteThreshold, $Verbose, $EnableMemoryProtection, $PrimaryConvertLoc, $AdditionalConvertLocs, $ProtectedRootDirs, $ConvertLoc;
  $SweepCompleted = TRUE;
  $LocationsSwept = 0;
  $convertLocPool = $poolEntry = array();
  $locationCleaned = $locationDeepCleaned = FALSE;
  $authorizedPoolPaths = array();
  $convertLocPool = enumerateDataLocations($PrimaryConvertLoc, $ConvertLoc, $AdditionalConvertLocs);
  // / The sweep may clean any location this installation declared & no other.
  // / The pool it just enumerated is exactly that set, so it is the authorized list.
  foreach ($convertLocPool as $poolEntry) array_push($authorizedPoolPaths, $poolEntry['Path']);
  foreach ($convertLocPool as $poolEntry) {
    if (!is_dir($poolEntry['Path'])) warningEntry('The '.$poolEntry['Type'].' data location at '.$poolEntry['Path'].' does not exist & was not swept.');
    else {
      list ($locationCleaned, $locationDeepCleaned) = cleanDataLocation($poolEntry['Path'], 'Pool location', $DeleteThreshold, $ProtectedRootDirs, $authorizedPoolPaths);
      if (!$locationCleaned) $SweepCompleted = FALSE;
      else {
        $LocationsSwept++;
        if ($Verbose) logEntry('Scheduled sweep: '.$poolEntry['Path'].', Type: '.$poolEntry['Type'].', Expired sessions removed: '.($locationDeepCleaned ? 'YES' : 'NO').'.'); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $authorizedPoolPaths, $convertLocPool, $poolEntry, $locationCleaned, $locationDeepCleaned);
  return array($SweepCompleted, $LocationsSwept); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to drop session map entries that no session can still be using.
// / Accepts the session map BY REFERENCE & the maximum age in seconds.
// / Returns the number of entries dropped.
// / The map lives in memory for the life of the process, so it must be bounded or a long
// / running listener grows one entry per session it has ever seen.
function evictExpiredSessionLocations(&$sessionLocations, $maximumAgeSeconds) {
  // / Set variables.
  global $EnableMemoryProtection;
  $EntriesDropped = 0;
  $mapKey = '';
  $mapEntry = array();
  $expiredKeys = array();
  foreach ($sessionLocations as $mapKey => $mapEntry) { if ((time() - (int)$mapEntry['LastSeen']) > (int)$maximumAgeSeconds) $expiredKeys[] = $mapKey; }
  foreach ($expiredKeys as $mapKey) {
    unset($sessionLocations[$mapKey]);
    $EntriesDropped++; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $mapKey, $mapEntry, $expiredKeys, $maximumAgeSeconds);
  return $EntriesDropped; }
// / -----------------------------------------------------------------------------------

// / A function to decide which data location a session uses & remember the answer.
// / Accepts the session map BY REFERENCE, the daily hash & the session hash.
// / Returns the absolute path as a string.
// / THE MAP IS A CACHE, NOT THE RECORD. resolveConvertLoc discovers an existing session
// / directory before it distributes anything, so a Resource Manager that restarted in the
// / middle of a session still returns the location that actually holds the files.
// / Load balancing happens here, once, when a session is first seen. Never again.
function resolveSessionLocation(&$sessionLocations, $dailyHash, $sessionHash) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection, $PrimaryConvertLoc, $AdditionalConvertLocs, $ProtectedRootDirs, $ConvertLoc;
  $SessionConvertLoc = '';
  $cleanDaily = preg_replace('/[^A-Za-z0-9]/', '', (string)$dailyHash);
  $cleanSession = preg_replace('/[^A-Za-z0-9]/', '', (string)$sessionHash);
  $mapKey = $cleanDaily.'|'.$cleanSession;
  $entryIsCached = FALSE;
  if ($cleanSession === '') $SessionConvertLoc = resolveDataLocation('', '', $PrimaryConvertLoc, $ConvertLoc, $AdditionalConvertLocs, $ProtectedRootDirs);
  else if (isset($sessionLocations[$mapKey])) {
    $SessionConvertLoc = (string)$sessionLocations[$mapKey]['Path'];
    $sessionLocations[$mapKey]['LastSeen'] = time();
    $entryIsCached = TRUE; }
  else {
    $SessionConvertLoc = resolveDataLocation($cleanDaily, $cleanSession, $PrimaryConvertLoc, $ConvertLoc, $AdditionalConvertLocs, $ProtectedRootDirs);
    $sessionLocations[$mapKey] = array('Path' => $SessionConvertLoc, 'LastSeen' => time()); }
  if ($Verbose) logEntry('Session Location: '.$SessionConvertLoc.', Session: '.($cleanSession === '' ? 'NONE' : $cleanSession).', Source: '.($entryIsCached ? 'MAP' : 'RESOLVED').'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $cleanDaily, $cleanSession, $mapKey, $entryIsCached, $dailyHash, $sessionHash);
  return $SessionConvertLoc; }
// / -----------------------------------------------------------------------------------

?>
