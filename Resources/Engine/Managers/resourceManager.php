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
// / v4.2.0.
// / This file is the Resource Manager. It is a manager subcomponent of the Engine.
// / The Resource Manager polls the host, calculates the budget every operation is granted from & sweeps anything that has outlived its welcome.
// / It watches the free space on every disk holding a data location, & refuses what would write into the reserve kept on one.
// /
// / A manager is a role a long lived process runs under, with a socket & a place in a budget.
// / This file holds what the role does.
// / The sockets, messages, budget arithmetic, worker registry & identity checks are the Engine's, shared by every role.
// / getAcceptedManagers() pins this file exactly, & loadEngineManager() loads it only for a process in this role.
// /
// / See Documentation/ABOUT_MANAGERS.txt for what each role is responsible for.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by an application.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! Engine-35000, A manager subcomponent cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The version of this manager. Read by the Engine WITHOUT executing this file.
$ManagerVersion = 'v4.2.0';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to run the Resource Manager loop.
// / Accepts no arguments & does not return until the process is terminated.
// / Returns TRUE when the loop exited cleanly.
// / Resource Manager owns the budget. Nothing else may adjust it.
function runResourceManager() {
  // / Set variables.
  global $ResourcePollInterval, $WorkerReapInterval, $WorkerStaleGracePeriod, $StorageCleanupInterval, $DeleteThreshold, $ManagerSocketTimeout, $ManagerMessageBatchSize, $Verbose, $EnableMemoryProtection, $PrimaryConvertLoc, $ConvertLoc, $AdditionalConvertLocs;
  $ResourceManagerExitedCleanly = FALSE;
  $socketServer = FALSE;
  $serverIsOpen = $keepRunning = $resourcesPolled = $budgetCalculated = FALSE;
  $requestApproved = $workerWasRegistered = $workerWasReleased = $extensionApproved = FALSE;
  $sweepCompleted = $messageWasDelivered = $limitsWereScaled = FALSE;
  $scaledLimits = array();
  $socketPath = $denialReason = $budgetToken = $sessionConvertLoc = $staleToken = '';
  $systemResources = $resourceBudget = $workerRegistry = $sessionLocations = array();
  $managerMessages = $managerConnections = $replyPayload = $staleWorkers = $staleRecord = $killReply = array();
  $messagesReceived = $messageIndex = $newExpectedRuntime = 0;
  $lastPollTime = $lastReapTime = $lastSweepTime = $locationsSwept = $entriesDropped = 0;
  $lastLogRefresh = time();
  $environmentIsReady = $dataIsProtected = FALSE;
  $environmentFindings = $scriptFinding = array();
  $exposureStatus = $exposureDetail = $scriptStatus = $scriptDetail = $locationStatus = $hostedStatus = $hostedDetail = $locationName = $findingCheck = '';
  $locationFinding = array();
  $namespaceState = $lastNamespaceState = 'unknown';
  $namespacesInUse = $namespaceLimit = 0;
  $thresholdsAreValid = $storageWasMeasured = $storageAllows = $storageWasReported = FALSE;
  $storageWarningPercentage = $storageReserveBytes = 0;
  $storagePool = $storageRecords = $lastStorageStates = $storageRecord = array();
  $storageKey = $storageState = $storageReason = '';
  $socketPath = buildManagerSocketPath('resource-manager');
  list ($serverIsOpen, $socketServer) = openManagerSocketServer($socketPath);
  if (!$serverIsOpen) errorEntry('The Resource Manager could not open its socket!', 31002, TRUE);
  else {
    logEntry('Resource Manager started. Budget, worker registry & session map are held in memory.');
    // / The storage thresholds & the data locations are read once, as config.php is.
    // / A fault in either is reported once here rather than at every poll.
    list ($thresholdsAreValid, $storageWarningPercentage, $storageReserveBytes) = readStorageThresholds();
    $storagePool = enumerateDataLocations($PrimaryConvertLoc, $ConvertLoc, $AdditionalConvertLocs);
    // / A session map entry is kept a little longer than a session can survive a sweep.
    $keepRunning = TRUE;
    while ($keepRunning) {
      // / A manager runs for weeks, so it moves on to the logfile it should be writing now, once a minute.
      $lastLogRefresh = refreshProcessLog($lastLogRefresh);
      // / Repoll the host on the configured interval & recompute from the live registry.
      if ((time() - $lastPollTime) >= (int)$ResourcePollInterval) {
        list ($resourcesPolled, $systemResources) = pollSystemResources();
        list ($budgetCalculated, $resourceBudget) = calculateResourceBudget($systemResources, trackedWorkerCost($workerRegistry));
        if (!$resourcesPolled) warningEntry('The Resource Manager could not poll system resources. The budget was not adjusted.');
        if ($Verbose && $budgetCalculated) logEntry('Resource Manager recalculated the budget. Load '.$systemResources['LoadPercentage'].'%, memory '.$systemResources['MemoryUsedPercentage'].'%, remaining '.$resourceBudget['RemainingBudget'].' of '.$resourceBudget['TotalBudget'].', tracked '.count($workerRegistry).'.');
        // / User namespaces are counted on the same interval. Each change of state is logged once.
        list ($namespaceState, $namespacesInUse, $namespaceLimit) = measureNamespaceHeadroom();
        if ($namespaceState !== $lastNamespaceState) {
          if ($namespaceState === 'spent') errorEntry('The web server account holds '.$namespacesInUse.' of its '.$namespaceLimit.' user namespaces! No conversion is granted until more than 1% are free.', 31025, FALSE);
          else if ($namespaceState === 'low') warningEntry('The web server account holds '.$namespacesInUse.' of its '.$namespaceLimit.' user namespaces. Conversions are refused once 1% or fewer are free.');
          else if ($namespaceState === 'ok') logEntry('The web server account holds '.$namespacesInUse.' of its '.$namespaceLimit.' user namespaces.');
          $lastNamespaceState = $namespaceState; }
        // / The free space on every disk holding a data location is measured on the same interval.
        // / Each change of a disk's state is logged once.
        list ($storageWasMeasured, $storageRecords) = measureStorageHeadroom($storagePool, $storageWarningPercentage, $storageReserveBytes);
        foreach ($storageRecords as $storageKey => $storageRecord) {
          if (!isset($lastStorageStates[$storageKey]) or $lastStorageStates[$storageKey] !== $storageRecord['State']) $storageWasReported = reportStorageState($storageRecord, $storageWarningPercentage, $storageReserveBytes);
          $lastStorageStates[$storageKey] = $storageRecord['State']; }
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
        // / Accounts are swept on the same schedule, where the installation has them.
        if (function_exists('sweepAccountsWhenDue')) sweepAccountsWhenDue(0);
        // / Revalidate the operating environment on the same schedule. A package upgrade, a
        // / kernel change or an administrator editing a policy can break the sandbox under a
        // / listener that has been running for weeks, & nothing else would notice until a
        // / conversion failed for a reason that looked unrelated.
        // / This REPORTS only. The listener runs as the web server user & cannot repair.
        list ($environmentIsReady, $environmentFindings) = validateOperatingEnvironment();
        if (!$environmentIsReady) warningEntry('The operating environment has degraded. The sandbox is unavailable & conversions requiring it will be refused. Run the -fp argument as root.');
        else if ($Verbose) logEntry('Operating environment revalidated. '.count($environmentFindings).' check(s) passed.');
        // / The bootstrap script is one of those findings, because a script from another release may hand a core arguments it refuses.
        // / The listener runs as the web server account, which cannot write to the installation, so -fp repairs it as root.
        $scriptStatus = environmentFindingStatus($environmentFindings, 'Bootstrap script');
        foreach ($environmentFindings as $scriptFinding) if (isset($scriptFinding['Check'], $scriptFinding['Detail']) && $scriptFinding['Check'] === 'Bootstrap script') $scriptDetail = (string)$scriptFinding['Detail'];
        if ($scriptStatus !== '' && !findingIsBenign($scriptStatus)) warningEntry('The bootstrap script reported '.$scriptStatus.'. '.$scriptDetail);
        // / The data location is another. Inside the document root its sessions, caches & logs
        // / could be fetched, & only an administrator can move it, so every pass says so.
        $locationStatus = environmentFindingStatus($environmentFindings, 'Data location');
        if ($locationStatus !== '' && !findingIsBenign($locationStatus)) warningEntry('The data location is inside the document root, so its sessions, caches & logs could be fetched. Move it outside the document root. The -v argument names it.');
        // / So is the hosted location, which holds the loader the web server runs. A release unpacked
        // / there & not installed, or a copy that has drifted, is root's to put right with -fp.
        $hostedStatus = environmentFindingStatus($environmentFindings, 'Hosted location');
        foreach ($environmentFindings as $scriptFinding) if (isset($scriptFinding['Check'], $scriptFinding['Detail']) && $scriptFinding['Check'] === 'Hosted location') $hostedDetail = (string)$scriptFinding['Detail'];
        if ($hostedStatus !== '' && !findingIsBenign($hostedStatus)) warningEntry('The hosted location reported '.$hostedStatus.'. '.$hostedDetail);
        // / Re-prove that the web served DATA tree still hands out inert downloads.
        // / Web server configuration changes long after installation, & a lost protection fails silently, so it is re-proved on a schedule.
        // / It is proved by asking the server, not by reading a file off the disk.
        // / This only reports, because the listener runs as the web server user & cannot edit server configuration.
        // / The exposure check ran inside validateOperatingEnvironment() above, so its status is read back from the findings.
        // / The three outcomes need three different sentences, which a degraded environment warning does not give.
        // / Each served location has an exposure line named for its folder, & one the Engine refused has a placement line instead.
        foreach ($environmentFindings as $locationFinding) {
          if (!isset($locationFinding['Check'], $locationFinding['Status'])) continue;
          $findingCheck = (string)$locationFinding['Check'];
          $exposureStatus = (string)$locationFinding['Status'];
          $exposureDetail = isset($locationFinding['Detail']) ? (string)$locationFinding['Detail'] : '';
          if (substr($findingCheck, -10) === ' placement') {
            if ($exposureStatus !== 'ok') warningEntry('The '.substr($findingCheck, 0, -10).' location reported '.$exposureStatus.'. '.$exposureDetail);
            continue; }
          if (substr($findingCheck, -9) !== ' exposure') continue;
          $locationName = substr($findingCheck, 0, -9);
          if ($exposureStatus === 'EXPOSED') warningEntry('The '.$locationName.' directory is EXPOSED. Uploaded files are served as renderable documents rather than as downloads. Run the -fp argument as root & apply the rules in the server configuration. See Documentation/ABOUT_DATA_DIRECTORY_PROTECTION.txt.');
          // / A broken tree is an outage rather than an exposure & is worth saying so plainly.
          // / Every download & every share link is failing while this is true.
          else if ($exposureStatus === 'BROKEN') warningEntry('The '.$locationName.' directory is returning a server error, so no download & no share link works. Check the web server error log for a line naming '.$locationName.'/.htaccess. Deleting that file restores service.');
          else if ($exposureStatus !== 'ok') warningEntry('The exposure of the '.$locationName.' directory could not be established. It reported '.$exposureStatus.'.');
          else if ($Verbose) logEntry($locationName.' directory exposure revalidated. The tree serves inert content.'); }
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
          // / The budget is worked out again from the conversions tracked now, because the last poll may be several requests old.
          // / The limits are scaled before the conversion is registered, so they count only the conversions already in flight.
          else if ($managerMessages[$messageIndex]['RequestType'] === 'budget') {
            list ($budgetCalculated, $resourceBudget) = calculateResourceBudget($systemResources, trackedWorkerCost($workerRegistry));
            if ($budgetCalculated) list ($requestApproved, $denialReason) = evaluateBudgetRequest($resourceBudget, $workerRegistry, ($managerMessages[$messageIndex]['ConversionCost'] ?? 0), ($managerMessages[$messageIndex]['ExpectedRuntime'] ?? 0));
            // / Every operation that takes a budget may write, so none starts on a disk within the reserve.
            // / The disk is measured now rather than read from the last poll, because a disk can fill between two polls.
            list ($storageAllows, $storageState, $storageReason) = evaluateStorageRequest($storagePool, ($managerMessages[$messageIndex]['ConvertLoc'] ?? ''), 0, ($managerMessages[$messageIndex]['QuotaOwner'] ?? ''), $storageWarningPercentage, $storageReserveBytes);
            if (!$budgetCalculated) $replyPayload = array('Approved' => FALSE, 'Reason' => 'The resource budget is unavailable.');
            else if (!$requestApproved) $replyPayload = array('Approved' => FALSE, 'Reason' => $denialReason);
            // / A sandbox the kernel can give no namespace fails inside it, so it is refused here.
            else if ($namespaceState === 'spent') $replyPayload = array('Approved' => FALSE, 'Reason' => '1% or fewer of the web server account\'s user namespaces are free.');
            else if (!$storageAllows) $replyPayload = array('Approved' => FALSE, 'Reason' => $storageReason);
            else {
              list ($limitsWereScaled, $scaledLimits) = scaleConversionLimits($systemResources, count($workerRegistry));
              list ($workerWasRegistered, $budgetToken) = registerTrackedWorker($workerRegistry, ($managerMessages[$messageIndex]['WorkerPid'] ?? 0), ($managerMessages[$messageIndex]['ConversionCost'] ?? 0), ($managerMessages[$messageIndex]['ExpectedRuntime'] ?? 0));
              if (!$workerWasRegistered) $replyPayload = array('Approved' => FALSE, 'Reason' => 'The worker registry could not be updated.');
              else {
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
          // / A request to write that takes no budget, such as an upload. It names what it would write,
          // / so a write that would reach into the reserve is refused before it starts.
          else if ($managerMessages[$messageIndex]['RequestType'] === 'storage') {
            list ($storageAllows, $storageState, $storageReason) = evaluateStorageRequest($storagePool, ($managerMessages[$messageIndex]['ConvertLoc'] ?? ''), ($managerMessages[$messageIndex]['RequestedBytes'] ?? 0), ($managerMessages[$messageIndex]['QuotaOwner'] ?? ''), $storageWarningPercentage, $storageReserveBytes);
            $replyPayload = array('Approved' => $storageAllows, 'Reason' => ($storageAllows ? 'Approved.' : $storageReason), 'StorageState' => $storageState); }
          // / A command line request for live figures. Nothing on disk can answer this.
          // / The budget is worked out from the conversions tracked now, as a budget request's is, &
          // / the limits are what the next conversion would be given.
          else if ($managerMessages[$messageIndex]['RequestType'] === 'status') {
            list ($budgetCalculated, $resourceBudget) = calculateResourceBudget($systemResources, trackedWorkerCost($workerRegistry));
            list ($limitsWereScaled, $scaledLimits) = scaleConversionLimits($systemResources, count($workerRegistry));
            $replyPayload = array('Approved' => TRUE, 'Reason' => 'Reported.', 'TrackedWorkers' => count($workerRegistry), 'SessionLocations' => count($sessionLocations), 'Budget' => $resourceBudget, 'Limits' => ($limitsWereScaled ? $scaledLimits : array()), 'Storage' => $storageRecords, 'StorageWarningPercentage' => $storageWarningPercentage, 'StorageReserveBytes' => $storageReserveBytes); }
          // / A command line request to end every tracked worker.
          else if ($managerMessages[$messageIndex]['RequestType'] === 'kill-tracked') {
            $replyPayload = array('Approved' => TRUE, 'Reason' => 'Ended '.killTrackedWorkers($workerRegistry).' tracked worker(s).'); } }
        replyToManagerMessage($managerConnections[$messageIndex], $replyPayload, 'internal');
        $messageIndex++; } }
    $ResourceManagerExitedCleanly = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $socketServer, $serverIsOpen, $keepRunning, $resourcesPolled, $budgetCalculated, $requestApproved, $workerWasRegistered, $workerWasReleased, $extensionApproved, $sweepCompleted, $messageWasDelivered, $socketPath, $denialReason, $budgetToken, $sessionConvertLoc, $staleToken, $systemResources, $resourceBudget, $workerRegistry, $sessionLocations, $managerMessages, $managerConnections, $replyPayload, $staleWorkers, $staleRecord, $killReply, $scaledLimits, $limitsWereScaled, $environmentIsReady, $environmentFindings, $scriptFinding, $scriptStatus, $scriptDetail, $locationStatus, $hostedStatus, $hostedDetail, $dataIsProtected, $exposureStatus, $exposureDetail, $locationName, $findingCheck, $locationFinding, $messagesReceived, $messageIndex, $newExpectedRuntime, $lastLogRefresh, $lastPollTime, $lastReapTime, $lastSweepTime, $locationsSwept, $entriesDropped, $namespaceState, $lastNamespaceState, $namespacesInUse, $namespaceLimit, $thresholdsAreValid, $storageWasMeasured, $storageAllows, $storageWasReported, $storageWarningPercentage, $storageReserveBytes, $storagePool, $storageRecords, $lastStorageStates, $storageRecord, $storageKey, $storageState, $storageReason);
  return $ResourceManagerExitedCleanly; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to scale the configured per conversion limits against what the host has free.
// / Accepts the polled resource array & the number of conversions already in flight, which the one
// / these limits are for is not among, in that order.
// / Returns a success boolean & a table of conversion type to limit string, in that order.
// / A busy host hands out smaller ceilings, so twenty conversions cannot each claim the
// / share that was safe for one. Nothing is ever scaled below the configured minimum.
// / The table is handed to the worker with its budget approval & is used for every file in
// / that request, so a conversion never has to ask again mid batch.
// / Each ceiling is scaled by the resource it limits.
// / The processor share shrinks with load.
// / The memory ceiling is a share of the memory available, less the reserved share, split between the conversions in flight & this one.
// / It is never more than the configured ceiling.
function scaleConversionLimits($systemResources, $trackedWorkers) {
  // / Set variables.
  global $MaximumPerConversionResources, $DefaultPerConversionResources, $MinimumPerConversionResources, $ReserveResourcePercentage, $Verbose, $EnableMemoryProtection;
  $LimitsWereScaled = FALSE;
  $ScaledLimits = array();
  $conversionType = $limitString = '';
  $limitIsValid = $minimumIsValid = FALSE;
  $cpuPercentage = $memoryMegabytes = $minimumCpu = $minimumMemory = 0;
  $scaledCpu = $scaledMemory = $concurrentShare = $memoryShare = $reserveShare = 0;
  $loadPressure = $loadFactor = 0.0;
  $limitTable = array();
  list ($minimumIsValid, $minimumCpu, $minimumMemory) = parseConversionLimit((string)$MinimumPerConversionResources);
  if (!$minimumIsValid) {
    $minimumCpu = 10;
    $minimumMemory = 128;
    warningEntry('The configured minimum per conversion resources could not be read. Falling back to 10% and 128M.'); }
  // / Every conversion in flight has taken a share, & so does this one.
  $concurrentShare = max(1, (int)$trackedWorkers + 1);
  // / The processor share shrinks with load alone.
  if (is_array($systemResources) && isset($systemResources['LoadPercentage'])) $loadPressure = (float)$systemResources['LoadPercentage'];
  if ($loadPressure > 95) $loadPressure = 95;
  if ($loadPressure < 0) $loadPressure = 0;
  $loadFactor = (1 - ($loadPressure / 100)) / $concurrentShare;
  // / The memory share is what the host has available, less the reserved share, split between them.
  // / A kernel that does not report available memory leaves no share to work out, & each conversion
  // / is given its split of the configured ceiling instead.
  $reserveShare = min(100, max(0, (int)$ReserveResourcePercentage));
  if (is_array($systemResources) && isset($systemResources['MemoryAvailableKb']) && (int)$systemResources['MemoryAvailableKb'] > 0) $memoryShare = (int)floor(((int)$systemResources['MemoryAvailableKb'] / 1024) * (1 - ($reserveShare / 100)) / $concurrentShare);
  // / Build the table from the configured maxima, plus the default under its own key.
  $limitTable = is_array($MaximumPerConversionResources) ? $MaximumPerConversionResources : array();
  $limitTable['Default'] = (string)$DefaultPerConversionResources;
  foreach ($limitTable as $conversionType => $limitString) {
    list ($limitIsValid, $cpuPercentage, $memoryMegabytes) = parseConversionLimit((string)$limitString);
    if (!$limitIsValid) warningEntry('The configured per conversion limit for '.$conversionType.' could not be read & was left out of the table.');
    else {
      $scaledCpu = (int)floor($cpuPercentage * $loadFactor);
      $scaledMemory = ($memoryShare > 0) ? $memoryShare : (int)floor($memoryMegabytes / $concurrentShare);
      // / A scanner needs the memory its signature database takes, which neither the file nor the load changes.
      // / So Scan keeps its configured memory, which config.php calls a floor, & only its processor share is scaled.
      // / Whether a scan may start at all is the budget's decision, because a ceiling it cannot run inside only makes it fail.
      if ($conversionType === 'Scan') $scaledMemory = $memoryMegabytes;
      // / The floor is what stops a loaded server handing out a ceiling nothing can run in.
      if ($scaledCpu < $minimumCpu) $scaledCpu = $minimumCpu;
      if ($scaledMemory < $minimumMemory) $scaledMemory = $minimumMemory;
      // / A scaled ceiling never exceeds the configured maximum.
      if ($scaledCpu > $cpuPercentage) $scaledCpu = $cpuPercentage;
      if ($scaledMemory > $memoryMegabytes) $scaledMemory = $memoryMegabytes;
      $ScaledLimits[$conversionType] = $scaledCpu.','.$scaledMemory; } }
  if (!empty($ScaledLimits)) $LimitsWereScaled = TRUE;
  if ($Verbose && $LimitsWereScaled) logEntry('Scaled per conversion limits for '.count($ScaledLimits).' type(s). Load '.round($loadPressure, 1).'%, memory share '.($memoryShare > 0 ? $memoryShare.'M' : 'NOT REPORTED').', in flight '.(int)$trackedWorkers.'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $ScaledLimits is not purged, because it is a return value.
  purgeSensitiveMemory($EnableMemoryProtection, $conversionType, $limitString, $limitIsValid, $minimumIsValid, $cpuPercentage, $memoryMegabytes, $minimumCpu, $minimumMemory, $scaledCpu, $scaledMemory, $concurrentShare, $memoryShare, $reserveShare, $loadPressure, $loadFactor, $limitTable, $systemResources, $trackedWorkers);
  return array($LimitsWereScaled, $ScaledLimits); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to measure how many user namespaces the web server account has left.
// / Accepts no arguments.
// / Returns the state, the namespaces in use & the limit, in that order.
// / The state is ok, low with 10% of the limit or less free, spent with 1% or less free, or
// / unknown where the limit is unreadable or zero.
// / Every sandbox is a user namespace, & the kernel refuses a new one once this account holds
// / user.max_user_namespaces of them. Each distinct user namespace a process of this account is
// / in, other than this process's own, is counted once. A process of another account cannot be
// / read & is passed over. A namespace with no process of its own is not seen, so the count is a
// / floor.
function measureNamespaceHeadroom() {
  // / Set variables.
  global $EnableMemoryProtection;
  $NamespaceState = 'unknown';
  $NamespacesInUse = $NamespaceLimit = $namespacesFree = 0;
  $lowPercentage = 10;
  $spentPercentage = 1;
  $ownNamespace = $processNamespace = $processPath = '';
  $namespacesSeen = $processPaths = array();
  $NamespaceLimit = (int)trim((string)@file_get_contents('/proc/sys/user/max_user_namespaces'));
  $ownNamespace = (string)@readlink('/proc/self/ns/user');
  if ($NamespaceLimit > 0 && $ownNamespace !== '') {
    $processPaths = glob('/proc/[0-9]*', GLOB_ONLYDIR | GLOB_NOSORT);
    if (is_array($processPaths)) foreach ($processPaths as $processPath) {
      $processNamespace = (string)@readlink($processPath.'/ns/user');
      if ($processNamespace !== '' && $processNamespace !== $ownNamespace) $namespacesSeen[$processNamespace] = TRUE; }
    $NamespacesInUse = count($namespacesSeen);
    $namespacesFree = $NamespaceLimit - $NamespacesInUse;
    if ($namespacesFree * 100 <= $NamespaceLimit * $spentPercentage) $NamespaceState = 'spent';
    else if ($namespacesFree * 100 <= $NamespaceLimit * $lowPercentage) $NamespaceState = 'low';
    else $NamespaceState = 'ok'; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $namespacesFree, $lowPercentage, $spentPercentage, $ownNamespace, $processNamespace, $processPath, $namespacesSeen, $processPaths);
  return array($NamespaceState, $NamespacesInUse, $NamespaceLimit); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read the two storage thresholds the application sets.
// / Accepts no arguments.
// / Returns whether both were usable, the warning as a percentage & the reserve in bytes, in that order.
// / The warning is the share of a disk free at which the listener warns, & the reserve is the space it keeps free.
// / A value of 0 turns that threshold off.
// / A value that is not a whole number in range is replaced by the default & reported, because a typo should not refuse every upload.
// / An application that maps neither name is given the defaults.
function readStorageThresholds() {
  // / Set variables.
  global $EnableMemoryProtection;
  $ThresholdsAreValid = TRUE;
  $WarningPercentage = $ReserveBytes = 0;
  $defaultWarning = 10;
  $defaultReserveMegabytes = 1024;
  $warningSetting = $reserveSetting = '';
  $warningSetting = applicationSetting('StorageWarningPercentage', $defaultWarning);
  $reserveSetting = applicationSetting('StorageReserveMegabytes', $defaultReserveMegabytes);
  // / A whole number arrives as an integer from config.php, & a quoted one as a string of digits. Both are accepted.
  if (is_string($warningSetting) && ctype_digit($warningSetting)) $warningSetting = (int)$warningSetting;
  if (is_string($reserveSetting) && ctype_digit($reserveSetting)) $reserveSetting = (int)$reserveSetting;
  if (is_int($warningSetting) && $warningSetting >= 0 && $warningSetting <= 99) $WarningPercentage = $warningSetting;
  else {
    $ThresholdsAreValid = FALSE;
    $WarningPercentage = $defaultWarning;
    warningEntry('The low storage warning is not a whole number from 0 to 99, so a disk is reported low at '.$defaultWarning.'% free.'); }
  if (is_int($reserveSetting) && $reserveSetting >= 0) $ReserveBytes = $reserveSetting * 1048576;
  else {
    $ThresholdsAreValid = FALSE;
    $ReserveBytes = $defaultReserveMegabytes * 1048576;
    warningEntry('The storage reserve is not a whole number of megabytes, so '.$defaultReserveMegabytes.' MB is kept free on every disk holding a data location.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $defaultWarning, $defaultReserveMegabytes, $warningSetting, $reserveSetting);
  return array($ThresholdsAreValid, $WarningPercentage, $ReserveBytes); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to measure the disk holding one data location.
// / Accepts the data location, the warning as a percentage & the reserve in bytes, in that order.
// / Returns the state, the free bytes, the size in bytes & the device the disk is, in that order.
// / The state is spent with the reserve or less free, low with the warning's share of the disk or less free, & ok otherwise.
// / It is unknown where the disk cannot be measured.
// / The reserve is tested first, so a reserve larger than the warning's share still refuses.
// / Free means free to this account, so the blocks a filesystem keeps for root are not counted.
function measureStorageAt($dataLocation, $warningPercentage, $reserveBytes) {
  // / Set variables.
  global $EnableMemoryProtection;
  $StorageState = 'unknown';
  $FreeBytes = $TotalBytes = 0;
  $DeviceId = '';
  $freeSpace = $totalSpace = $locationFacts = FALSE;
  if (is_string($dataLocation) && $dataLocation !== '' && is_dir($dataLocation)) {
    $freeSpace = @disk_free_space($dataLocation);
    $totalSpace = @disk_total_space($dataLocation);
    $locationFacts = @stat($dataLocation); }
  if ($freeSpace !== FALSE && $totalSpace !== FALSE && $totalSpace > 0) {
    $FreeBytes = (int)$freeSpace;
    $TotalBytes = (int)$totalSpace;
    if (is_array($locationFacts) && isset($locationFacts['dev'])) $DeviceId = (string)$locationFacts['dev'];
    if ((int)$reserveBytes > 0 && $FreeBytes <= (int)$reserveBytes) $StorageState = 'spent';
    else if ((int)$warningPercentage > 0 && $FreeBytes * 100 <= $TotalBytes * (int)$warningPercentage) $StorageState = 'low';
    else $StorageState = 'ok'; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $freeSpace, $totalSpace, $locationFacts, $dataLocation, $warningPercentage, $reserveBytes);
  return array($StorageState, $FreeBytes, $TotalBytes, $DeviceId); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to measure every disk holding a data location.
// / Accepts the data locations, the warning as a percentage & the reserve in bytes, in that order.
// / Returns whether every disk was measured & a record for each disk, in that order.
// / A record holds the data locations on the disk, its state, its free bytes & its size in bytes.
// / Locations on one disk share one record, because they share one free space, & one entry says so once.
// / A location that cannot be measured is a record of its own.
function measureStorageHeadroom($dataLocations, $warningPercentage, $reserveBytes) {
  // / Set variables.
  global $EnableMemoryProtection;
  $StorageWasMeasured = TRUE;
  $StorageRecords = array();
  $poolEntry = array();
  $storageState = $deviceId = $recordKey = '';
  $freeBytes = $totalBytes = 0;
  foreach ((array)$dataLocations as $poolEntry) {
    if (!is_array($poolEntry) or !isset($poolEntry['Path'])) continue;
    // / What the disk before this one measured is shredded first, as redeclare() would, because a list() cannot pass through redeclare().
    purgeSensitiveMemory($EnableMemoryProtection, $storageState, $freeBytes, $totalBytes, $deviceId);
    list ($storageState, $freeBytes, $totalBytes, $deviceId) = measureStorageAt((string)$poolEntry['Path'], $warningPercentage, $reserveBytes);
    redeclare($recordKey, ($deviceId !== '') ? 'device '.$deviceId : 'location '.(string)$poolEntry['Path']);
    if ($storageState === 'unknown') $StorageWasMeasured = FALSE;
    if (isset($StorageRecords[$recordKey])) $StorageRecords[$recordKey]['Locations'][] = (string)$poolEntry['Path'];
    else $StorageRecords[$recordKey] = array('Locations' => array((string)$poolEntry['Path']), 'State' => $storageState, 'FreeBytes' => $freeBytes, 'TotalBytes' => $totalBytes); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $poolEntry, $storageState, $deviceId, $recordKey, $freeBytes, $totalBytes, $dataLocations, $warningPercentage, $reserveBytes);
  return array($StorageWasMeasured, $StorageRecords); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to log a disk's change of state.
// / Accepts the disk's record, the warning as a percentage & the reserve in bytes, in that order.
// / Returns whether an entry was written.
// / A disk within the reserve is an error, because work is being refused, & a disk that is low is a warning.
// / A disk measured for the first time, or one that has recovered, is normal activity.
function reportStorageState($storageRecord, $warningPercentage, $reserveBytes) {
  // / Set variables.
  global $EnableMemoryProtection;
  $EntryWasWritten = FALSE;
  $diskName = $freeText = $reserveText = '';
  $freePercentage = 0;
  $diskName = 'The disk holding '.implode(' & ', (array)$storageRecord['Locations']);
  $freeText = describeByteCount((int)$storageRecord['FreeBytes']);
  $reserveText = describeByteCount((int)$reserveBytes);
  if ((int)$storageRecord['TotalBytes'] > 0) $freePercentage = (int)floor((int)$storageRecord['FreeBytes'] * 100 / (int)$storageRecord['TotalBytes']);
  if ($storageRecord['State'] === 'spent') $EntryWasWritten = errorEntry($diskName.' has '.$freeText.' free, within its storage reserve of '.$reserveText.'! Nothing more is written to it until more is free.', 31026, FALSE);
  else if ($storageRecord['State'] === 'low') $EntryWasWritten = warningEntry($diskName.' has '.$freeText.' free, '.$freePercentage.'% of its size, which is '.(int)$warningPercentage.'% or less.'.((int)$reserveBytes > 0 ? ' Nothing more is written to it once '.$reserveText.' or less is free.' : ''));
  else if ($storageRecord['State'] === 'ok') $EntryWasWritten = logEntry($diskName.' has '.$freeText.' free, '.$freePercentage.'% of its size.');
  else $EntryWasWritten = warningEntry('The free space on the disk holding '.implode(' & ', (array)$storageRecord['Locations']).' could not be measured, so nothing is refused for want of it.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $diskName, $freeText, $reserveText, $freePercentage, $storageRecord, $warningPercentage, $reserveBytes);
  return $EntryWasWritten; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether a request may write to the disk holding a data location.
// / Accepts the data locations, the one the request names, the bytes it would write, the owner it is charged to,
// / the warning as a percentage & the reserve in bytes, in that order.
// / Returns whether it may, the disk's state & the reason it may not, in that order.
// / Only a configured data location is measured. One a request names that is not configured is answered for the
// / primary, so a request cannot have this account measure any path it likes.
// / A disk within the reserve refuses every write, & one outside it refuses a write that would take it within.
// / A disk that cannot be measured refuses nothing, & the poll has already said why.
// / An owner's quota is tested last.
function evaluateStorageRequest($dataLocations, $requestedLocation, $requestedBytes, $quotaOwner, $warningPercentage, $reserveBytes) {
  // / Set variables.
  global $EnableMemoryProtection;
  $StorageAllows = FALSE;
  $StorageState = 'unknown';
  $StorageReason = '';
  $configuredPaths = array();
  $cleanLocation = $measuredLocation = $deviceId = $quotaReason = '';
  $writeBytes = $freeBytes = $totalBytes = 0;
  $quotaAllows = FALSE;
  $configuredPaths = array_column((array)$dataLocations, 'Path');
  $cleanLocation = rtrim((string)$requestedLocation, DIRECTORY_SEPARATOR);
  // / The primary is listed first, so it is the answer for a location that is not configured.
  $measuredLocation = in_array($cleanLocation, $configuredPaths, TRUE) ? $cleanLocation : (string)($configuredPaths[0] ?? '');
  $writeBytes = max(0, (int)$requestedBytes);
  list ($StorageState, $freeBytes, $totalBytes, $deviceId) = measureStorageAt($measuredLocation, $warningPercentage, $reserveBytes);
  if ($StorageState === 'spent') $StorageReason = 'The disk holding the data location has '.describeByteCount($freeBytes).' free, within its storage reserve of '.describeByteCount((int)$reserveBytes).'.';
  else if ($StorageState !== 'unknown' && (int)$reserveBytes > 0 && $writeBytes > 0 && $freeBytes - $writeBytes <= (int)$reserveBytes) $StorageReason = 'Writing '.describeByteCount($writeBytes).' would leave the disk holding the data location within its storage reserve of '.describeByteCount((int)$reserveBytes).'.';
  else {
    list ($quotaAllows, $quotaReason) = evaluateStorageQuota($quotaOwner, $measuredLocation, $writeBytes);
    if ($quotaAllows) $StorageAllows = TRUE;
    else $StorageReason = $quotaReason; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $configuredPaths, $cleanLocation, $measuredLocation, $deviceId, $quotaReason, $writeBytes, $freeBytes, $totalBytes, $quotaAllows, $dataLocations, $requestedLocation, $requestedBytes, $quotaOwner, $warningPercentage, $reserveBytes);
  return array($StorageAllows, $StorageState, $StorageReason); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether an owner's quota allows a write.
// / Accepts the owner the write is charged to, the data location & the bytes it would write, in that order.
// / Returns whether the quota allows it & the reason it does not, in that order.
// / An anonymous write is charged to nobody & is allowed. A signed in user's is charged to their account, & the Authentication Manager
// / answers it from --User Storage Quota-- & what their folder holds, through the Engine's evaluateOwnerQuota().
function evaluateStorageQuota($quotaOwner, $dataLocation, $requestedBytes) {
  // / Set variables.
  global $EnableMemoryProtection;
  $QuotaAllows = TRUE;
  $QuotaReason = '';
  if ((string)$quotaOwner !== '' && function_exists('evaluateOwnerQuota')) list ($QuotaAllows, $QuotaReason) = evaluateOwnerQuota((string)$quotaOwner, $requestedBytes);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $quotaOwner, $dataLocation, $requestedBytes);
  return array($QuotaAllows, $QuotaReason); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to sweep every configured data location on a schedule.
// / Accepts no arguments.
// / Returns a completion boolean & the number of locations swept, in that order.
// / It uses cleanDataLocation(), as the core's own clean does, which is what an installation with no listener relies on.
// / Only the shared data locations are swept.
// / The temporary location lives inside the hosted location, so each front end cleans its own.
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

// / -----------------------------------------------------------------------------------
// / A function to decide which data location a session uses & remember the answer.
// / Accepts the session map BY REFERENCE, the daily hash & the session hash.
// / Returns the absolute path as a string.
// / The map is a cache, not the record.
// / resolveConvertLoc discovers an existing session directory before it distributes anything.
// / A Resource Manager that restarted mid session therefore still returns the location that holds the files.
// / Load balancing happens here, once, when a session is first seen.
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
