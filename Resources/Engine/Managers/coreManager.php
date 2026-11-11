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
// / v4.0.8.
// / This file is the Core Manager. It is a manager subcomponent of the Engine.
// / The Core Manager is the listener. It starts the other three, restarts one that dies & is
// / the only role an operator starts directly.
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
$ManagerVersion = 'v4.0.8';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to run the Core Manager supervisor loop.
// / Accepts no arguments & does not return until the listener is stopped.
// / Returns TRUE when the loop exited on a stop instruction rather than a failure.
// / Core Manager owns no budget & no registry. It routes, supervises & reaps.
function runCoreManager() {
  // / Set variables.
  global $CoreManagerSubprocessPollInterval, $ManagerSocketTimeout, $ManagerMessageBatchSize, $Verbose, $EnableMemoryProtection, $CoreManagerStopRequested;
  $CoreManagerExitedCleanly = $CoreManagerStopRequested = FALSE;
  $socketServer = FALSE;
  $serverIsOpen = $keepRunning = $stateWasWritten = FALSE;
  $socketPath = $managerRole = $targetSocket = '';
  $managerState = $managerMessages = $managerConnections = $replyPayload = $routedReply = array();
  $messagesReceived = $messageIndex = $managerPid = 0;
  $lastLogRefresh = time();
  $managerWasSpawned = $replyWasDelivered = $signalIsHandled = $subordinatesStopped = FALSE;
  $subordinateRoles = array('request-manager', 'resource-manager', 'worker-manager');
  $socketPath = buildManagerSocketPath('core-manager');
  list ($serverIsOpen, $socketServer) = openManagerSocketServer($socketPath);
  if (!$serverIsOpen) errorEntry('The Core Manager could not open its socket!', 31000, TRUE);
  else {
    // / A stop signal is a stop instruction.
    // / systemctl stop sends one to this process alone, & KillMode=mixed kills whatever is left once it exits.
    // / It is answered as the loop below waits, so the three subordinates, which are not its children, stop cleanly.
    if (function_exists('pcntl_async_signals') && function_exists('pcntl_signal') && defined('SIGTERM')) {
      pcntl_async_signals(TRUE);
      $signalIsHandled = pcntl_signal(SIGTERM, 'noteCoreManagerStop'); }
    if (!$signalIsHandled) warningEntry('The Core Manager cannot answer a stop signal, because PHP cannot handle one here. Its subordinates are stopped by whatever stops it.');
    // / Start every subordinate & record the process identifiers for supervision.
    $managerState = array('CoreManagerPid' => getmypid(), 'StartTime' => time(), 'Subordinates' => array());
    foreach ($subordinateRoles as $managerRole) {
      list ($managerWasSpawned, $managerPid) = spawnManagerProcess($managerRole);
      if ($managerWasSpawned) $managerState['Subordinates'][$managerRole] = $managerPid; }
    $stateWasWritten = writeManagerState('managers', $managerState);
    if (!$stateWasWritten) warningEntry('The Core Manager could not record its subordinate process identifiers.');
    logEntry('Core Manager started with '.count($managerState['Subordinates']).' subordinate manager(s).');
    if ($Verbose) logEntry('Core Manager starting supervisor loop. Poll interval '.(int)$CoreManagerSubprocessPollInterval.' second(s).');
    $keepRunning = TRUE;
    while ($keepRunning) {
      // / A manager runs for weeks, so it moves on to the logfile it should be writing now, once a minute.
      $lastLogRefresh = refreshProcessLog($lastLogRefresh);
      list ($messagesReceived, $managerMessages, $managerConnections) = receiveManagerMessages($socketServer, 'core', (int)$ManagerMessageBatchSize, (int)$CoreManagerSubprocessPollInterval);
      // / A stop signal ends the wait above at once, & is answered before anything else.
      if (isset($CoreManagerStopRequested) && $CoreManagerStopRequested === TRUE) {
        $keepRunning = FALSE;
        $CoreManagerExitedCleanly = TRUE;
        logEntry('Core Manager received a stop signal & is stopping its subordinate managers.'); }
      $messageIndex = 0;
      while ($messageIndex < $messagesReceived) {
        $replyPayload = array('Approved' => FALSE, 'Reason' => 'Unrecognized request type.');
        $targetSocket = '';
        // / Route by request type. Core Manager decides who answers, never what the answer is.
        if (isset($managerMessages[$messageIndex]['RequestType'])) {
          if ($managerMessages[$messageIndex]['RequestType'] === 'stop') {
            $keepRunning = FALSE;
            $CoreManagerExitedCleanly = TRUE;
            if ($Verbose) logEntry('Core Manager accepted a stop instruction & is unwinding.');
            $replyPayload = array('Approved' => TRUE, 'Reason' => 'Stopping.'); }
          // / The Resource Manager holds the budget, the registry & the session map, so every
          // / question about state goes there. Only an instruction to end a process goes to
          // / the Worker Manager, which is the only component permitted to carry one out.
          else if (in_array($managerMessages[$messageIndex]['RequestType'], array('budget', 'release', 'extend', 'convertloc', 'status', 'kill-tracked'), TRUE)) $targetSocket = buildManagerSocketPath('resource-manager');
          else if (in_array($managerMessages[$messageIndex]['RequestType'], array('kill', 'kill-every'), TRUE)) $targetSocket = buildManagerSocketPath('worker-manager'); }
        if ($targetSocket !== '') {
          if ($Verbose) logEntry('Core Manager routed a '.$managerMessages[$messageIndex]['RequestType'].' request to '.basename($targetSocket).'.');
          list ($replyWasDelivered, $routedReply) = sendManagerMessage($targetSocket, $managerMessages[$messageIndex], 'internal', (int)$ManagerSocketTimeout);
          if ($replyWasDelivered && !empty($routedReply)) $replyPayload = $routedReply;
          else $replyPayload = array('Approved' => FALSE, 'Reason' => 'The responsible manager did not answer.'); }
        replyToManagerMessage($managerConnections[$messageIndex], $replyPayload, 'core');
        $messageIndex++; }
      // / Supervise. A subordinate that has died is replaced rather than mourned.
      if ($keepRunning) {
        foreach ($subordinateRoles as $managerRole) {
          if (!isset($managerState['Subordinates'][$managerRole]) or !file_exists('/proc/'.(int)$managerState['Subordinates'][$managerRole])) {
            warningEntry('The '.$managerRole.' process is not running. Restarting it.');
            list ($managerWasSpawned, $managerPid) = spawnManagerProcess($managerRole);
            if ($managerWasSpawned) {
              $managerState['Subordinates'][$managerRole] = $managerPid;
              writeManagerState('managers', $managerState); } } } } }
    // / Stop every subordinate before leaving, so no orphan keeps a socket bound.
    $subordinatesStopped = stopSubordinateManagers($managerState['Subordinates']);
    if (!$subordinatesStopped) warningEntry('A subordinate manager outlived a stop signal & a kill signal.');
    if (is_resource($socketServer)) @fclose($socketServer);
    if (file_exists($socketPath)) @unlink($socketPath);
    logEntry('Core Manager stopped.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $socketServer, $serverIsOpen, $keepRunning, $stateWasWritten, $socketPath, $managerRole, $targetSocket, $managerState, $managerMessages, $managerConnections, $replyPayload, $routedReply, $messagesReceived, $messageIndex, $managerPid, $lastLogRefresh, $managerWasSpawned, $replyWasDelivered, $signalIsHandled, $subordinatesStopped, $subordinateRoles);
  return $CoreManagerExitedCleanly; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to note that the Core Manager was sent a stop signal.
// / Accepts the signal number, which PHP hands every signal handler. Returns TRUE, which PHP
// / does not read.
// / A signal can arrive between any two instructions, so this only notes it. The loop in
// / runCoreManager() reads the note once its wait ends, which the signal itself ends at once.
function noteCoreManagerStop($signalNumber) {
  // / Set variables.
  global $CoreManagerStopRequested;
  $CoreManagerStopRequested = TRUE;
  // / The signal number says nothing the note needs. It is cleared by hand, because a signal can
  // / arrive while purgeSensitiveMemory is already running.
  $signalNumber = NULL;
  unset($signalNumber);
  return $CoreManagerStopRequested; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to stop every subordinate manager & wait until each one has gone.
// / Accepts the recorded subordinates, as role => process identifier.
// / Returns TRUE when none of them is still running.
// / Each is asked to stop with TERM, which ends one at once, & is waited for, so none is left for the service manager.
// / One still running after twenty seconds, inside the thirty systemd allows, is killed.
function stopSubordinateManagers($subordinatePids) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $SubordinatesStopped = $managerWasKilled = FALSE;
  $managerRole = '';
  $managerPid = $waitedTenths = 0;
  $runningPids = array();
  if (is_array($subordinatePids)) foreach ($subordinatePids as $managerRole => $managerPid) {
    if (managerProcessIsAlive((int)$managerPid)) $runningPids[$managerRole] = (int)$managerPid; }
  foreach ($runningPids as $managerRole => $managerPid) terminateWorkerProcess($managerPid, FALSE, 0);
  while (count($runningPids) > 0 && $waitedTenths < 200) {
    foreach ($runningPids as $managerRole => $managerPid) if (!managerProcessIsAlive($managerPid)) unset($runningPids[$managerRole]);
    if (count($runningPids) > 0) usleep(100000);
    $waitedTenths++; }
  // / A killed manager nobody has collected yet is gone all the same, so each is asked again by state.
  foreach ($runningPids as $managerRole => $managerPid) {
    warningEntry('The '.$managerRole.' process '.$managerPid.' did not stop within twenty seconds & was killed.');
    $managerWasKilled = terminateWorkerProcess($managerPid, TRUE, 0);
    if ($managerWasKilled or !managerProcessIsAlive($managerPid)) unset($runningPids[$managerRole]); }
  $SubordinatesStopped = (count($runningPids) === 0);
  if ($Verbose && $SubordinatesStopped) logEntry('Every subordinate manager has stopped.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $managerWasKilled, $managerRole, $managerPid, $waitedTenths, $runningPids, $subordinatePids);
  return $SubordinatesStopped; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to start one subordinate manager as a detached background process.
// / Accepts the manager role name.
// / Returns a success boolean & the process identifier, in that order.
function spawnManagerProcess($managerRole) {
  // / Set variables.
  global $InstLoc, $DirSep, $EnableMemoryProtection, $Verbose;
  $ManagerWasSpawned = FALSE;
  $ManagerPid = 0;
  $spawnCommand = $spawnOutput = $startupKey = '';
  $cleanRole = preg_replace('/[^a-z\-]/', '', strtolower((string)$managerRole));
  $startupKey = deriveStartupKey('start-manager-'.$cleanRole);
  if ($cleanRole !== '' && $startupKey !== '') {
    // / The key travels in the environment & never on the command line.
    // / /proc/PID/cmdline is world readable, so a key placed there is visible in ps to
    // / every local account for the whole life of the process, & a manager runs for days.
    // / /proc/PID/environ is readable only by the owner. The child clears the variable as
    // / soon as it reads it, so nothing the manager launches later inherits it.
    $spawnCommand = engineStartupKeyVariable().'='.escapeshellarg($startupKey)
      .' nohup php '.escapeshellarg($InstLoc.$DirSep.engineApplicationEntryPoint())
      .' --start-manager '.escapeshellarg($cleanRole)
      .' > /dev/null 2>&1 & echo $!';
    $spawnOutput = shell_exec($spawnCommand);
    $ManagerPid = (int)trim((string)$spawnOutput);
    if ($ManagerPid > 0) { 
      $ManagerWasSpawned = TRUE;
      if ($Verbose) logEntry('Core Manager spawned the '.$cleanRole.' process as '.$ManagerPid.'.'); }
    else warningEntry('Could not spawn the '.$cleanRole.' manager process.'); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $spawnCommand, $spawnOutput, $startupKey, $cleanRole, $managerRole);
  return array($ManagerWasSpawned, $ManagerPid); }
// / -----------------------------------------------------------------------------------

?>
