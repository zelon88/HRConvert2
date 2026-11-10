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
// / v4.0.6.
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
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! Engine-35000, A manager subcomponent cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The version of this manager. Read by the Engine WITHOUT executing this file.
$ManagerVersion = 'v4.0.6';
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
  $socketPath = buildManagerSocketPath('resource-manager');
  list ($serverIsOpen, $socketServer) = openManagerSocketServer($socketPath);
  if (!$serverIsOpen) errorEntry('The Resource Manager could not open its socket!', 31002, TRUE);
  else {
    logEntry('Resource Manager started. Budget, worker registry & session map are held in memory.');
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
        // / Revalidate the operating environment on the same schedule. A package upgrade, a
        // / kernel change or an administrator editing a policy can break the sandbox under a
        // / listener that has been running for weeks, & nothing else would notice until a
        // / conversion failed for a reason that looked unrelated.
        // / This REPORTS only. The listener runs as the web server user & cannot repair.
        list ($environmentIsReady, $environmentFindings) = validateOperatingEnvironment();
        if (!$environmentIsReady) warningEntry('The operating environment has degraded. The sandbox is unavailable & conversions requiring it will be refused. Run the -fp argument as root.');
        else if ($Verbose) logEntry('Operating environment revalidated. '.count($environmentFindings).' check(s) passed.');
        // / The bootstrap script is one of those findings. A script left behind by another
        // / release hands arguments to a core that may no longer accept them. The listener runs
        // / as the web server account, which cannot write to an installation root owns, so it
        // / says what the script needs & -fp does it as root. Until v4.0.1 the listener rewrote
        // / the script itself, which stopped working once the installation became root's.
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
        // / Each served location has an exposure line named for its folder, so HRConvert2's reads
        // / DATA, & one the Engine refused has a placement line instead. Until v4.0.5 only the DATA
        // / line was read, so a location it did not name was never re-proved at all.
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
          // / The budget is worked out again from the conversions tracked now, because the last poll
          // / may be several requests old. Until v4.0.6 it was worked out only as the host was polled,
          // / so every request between two polls was judged against the same remaining budget.
          // / The limits are scaled before the conversion is registered, so they count only the
          // / conversions already in flight. Until v4.0.6 the conversion was registered first, so it
          // / was counted as in flight against itself & a conversion on its own was given half.
          else if ($managerMessages[$messageIndex]['RequestType'] === 'budget') {
            list ($budgetCalculated, $resourceBudget) = calculateResourceBudget($systemResources, trackedWorkerCost($workerRegistry));
            if ($budgetCalculated) list ($requestApproved, $denialReason) = evaluateBudgetRequest($resourceBudget, $workerRegistry, ($managerMessages[$messageIndex]['ConversionCost'] ?? 0), ($managerMessages[$messageIndex]['ExpectedRuntime'] ?? 0));
            if (!$budgetCalculated) $replyPayload = array('Approved' => FALSE, 'Reason' => 'The resource budget is unavailable.');
            else if (!$requestApproved) $replyPayload = array('Approved' => FALSE, 'Reason' => $denialReason);
            // / A sandbox the kernel can give no namespace fails inside it, so it is refused here.
            else if ($namespaceState === 'spent') $replyPayload = array('Approved' => FALSE, 'Reason' => '1% or fewer of the web server account\'s user namespaces are free.');
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
          // / A command line request for live figures. Nothing on disk can answer this.
          // / The budget is worked out from the conversions tracked now, as a budget request's is, &
          // / the limits are what the next conversion would be given.
          else if ($managerMessages[$messageIndex]['RequestType'] === 'status') {
            list ($budgetCalculated, $resourceBudget) = calculateResourceBudget($systemResources, trackedWorkerCost($workerRegistry));
            list ($limitsWereScaled, $scaledLimits) = scaleConversionLimits($systemResources, count($workerRegistry));
            $replyPayload = array('Approved' => TRUE, 'Reason' => 'Reported.', 'TrackedWorkers' => count($workerRegistry), 'SessionLocations' => count($sessionLocations), 'Budget' => $resourceBudget, 'Limits' => ($limitsWereScaled ? $scaledLimits : array())); }
          // / A command line request to end every tracked worker.
          else if ($managerMessages[$messageIndex]['RequestType'] === 'kill-tracked') {
            $replyPayload = array('Approved' => TRUE, 'Reason' => 'Ended '.killTrackedWorkers($workerRegistry).' tracked worker(s).'); } }
        replyToManagerMessage($managerConnections[$messageIndex], $replyPayload, 'internal');
        $messageIndex++; } }
    $ResourceManagerExitedCleanly = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $socketServer, $serverIsOpen, $keepRunning, $resourcesPolled, $budgetCalculated, $requestApproved, $workerWasRegistered, $workerWasReleased, $extensionApproved, $sweepCompleted, $messageWasDelivered, $socketPath, $denialReason, $budgetToken, $sessionConvertLoc, $staleToken, $systemResources, $resourceBudget, $workerRegistry, $sessionLocations, $managerMessages, $managerConnections, $replyPayload, $staleWorkers, $staleRecord, $killReply, $scaledLimits, $limitsWereScaled, $environmentIsReady, $environmentFindings, $scriptFinding, $scriptStatus, $scriptDetail, $locationStatus, $hostedStatus, $hostedDetail, $dataIsProtected, $exposureStatus, $exposureDetail, $locationName, $findingCheck, $locationFinding, $messagesReceived, $messageIndex, $newExpectedRuntime, $lastLogRefresh, $lastPollTime, $lastReapTime, $lastSweepTime, $locationsSwept, $entriesDropped, $namespaceState, $lastNamespaceState, $namespacesInUse, $namespaceLimit);
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
// / EACH CEILING IS SCALED BY THE RESOURCE IT LIMITS. The processor share shrinks with load. The
// / memory ceiling is a share of the memory the host has available, less the reserved share, split
// / between the conversions in flight & this one, & never more than the configured ceiling.
// / Until v4.0.6 both shrank by whichever of load or memory in use was higher, as a fraction of the
// / configured ceiling. A desktop with half its memory in use halved every memory ceiling while
// / gigabytes stood free, & LibreOffice was killed inside the 137M a document conversion was left.
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
      // / A scanner needs the memory its loaded signature database takes, & neither the file
      // / nor the load changes that, so Scan keeps its configured memory & only its processor
      // / share is scaled. config.php has always said to treat that figure as a floor. Until
      // / v3.9.9 it was scaled with the rest, & a quiet server with one scan in flight handed
      // / ClamAV 740M of a configured 2048M, so the kernel killed it while it loaded. Whether
      // / a scan may start at all is the budget's decision, & a ceiling it cannot run inside
      // / does not protect the host, it only turns the scan into a failure.
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
