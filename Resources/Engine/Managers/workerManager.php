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
// / v3.8.9.
// / This file is the Worker Manager. It is a manager subcomponent of the Engine.
// / The Worker Manager tracks live workers & terminates one that has outlived its expected runtime.
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
$ManagerVersion = 'v3.8.9';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to run the Worker Manager loop.
// / Accepts no arguments & does not return until the process is terminated.
// / Returns TRUE when the loop exited cleanly.
// / Worker Manager is the only component that may end a process without negotiation.
function runWorkerManager() {
  // / Set variables.
  global $ManagerSocketTimeout, $ManagerMessageBatchSize, $Verbose, $EnableMemoryProtection;
  $WorkerManagerExitedCleanly = FALSE;
  $socketServer = FALSE;
  $serverIsOpen = $keepRunning = $processWasTerminated = FALSE;
  $socketPath = '';
  $managerMessages = $managerConnections = $replyPayload = array();
  $messagesReceived = $messageIndex = $killedCount = 0;
  $socketPath = buildManagerSocketPath('worker-manager');
  list ($serverIsOpen, $socketServer) = openManagerSocketServer($socketPath);
  if (!$serverIsOpen) errorEntry('The Worker Manager could not open its socket!', 31003, TRUE);
  else {
    logEntry('Worker Manager started.');
    $keepRunning = TRUE;
    while ($keepRunning) {
      list ($messagesReceived, $managerMessages, $managerConnections) = receiveManagerMessages($socketServer, 'internal', (int)$ManagerMessageBatchSize, (int)$ManagerSocketTimeout);
      $messageIndex = 0;
      while ($messageIndex < $messagesReceived) {
        $replyPayload = array('Approved' => FALSE, 'Reason' => 'Unrecognized worker request.');
        if (isset($managerMessages[$messageIndex]['RequestType'])) {
          if ($managerMessages[$messageIndex]['RequestType'] === 'kill') {
            $processWasTerminated = terminateWorkerProcess(($managerMessages[$messageIndex]['WorkerPid'] ?? 0), TRUE, ($managerMessages[$messageIndex]['ProcessStartTime'] ?? 0));
            if ($Verbose) logEntry('Worker Manager ended process '.(int)($managerMessages[$messageIndex]['WorkerPid'] ?? 0).'. Result: '.($processWasTerminated ? 'OK' : 'FAILED').'.');
            $replyPayload = array('Approved' => $processWasTerminated, 'Reason' => $processWasTerminated ? 'Terminated.' : 'The process could not be terminated.'); }
          else if ($managerMessages[$messageIndex]['RequestType'] === 'kill-every') {
            $killedCount = killEveryWorker();
            $replyPayload = array('Approved' => TRUE, 'Reason' => 'Terminated '.$killedCount.' process(es).', 'KilledCount' => $killedCount); } }
        replyToManagerMessage($managerConnections[$messageIndex], $replyPayload, 'internal');
        $messageIndex++; } }
    $WorkerManagerExitedCleanly = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $serverIsOpen, $keepRunning, $processWasTerminated, $socketPath, $managerMessages, $managerConnections, $replyPayload, $messagesReceived, $messageIndex, $killedCount);
  return $WorkerManagerExitedCleanly; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to terminate every PHP process owned by the web server user.
// / Accepts no arguments.
// / Returns the number of processes terminated.
// / This ends unrelated applications sharing the host. It is never called automatically.
function killEveryWorker() {
  // / Set variables.
  global $ApacheUser, $ManagerSocketTimeout, $EnableMemoryProtection;
  $ProcessesKilled = 0;
  $processOutput = array();
  $processExitCode = 1;
  $processLine = '';
  $processPid = 0;
  $protectedPids = array();
  $managerState = array();
  $stateWasRead = FALSE;
  // / The listener must survive its own instruction, so every manager is excluded.
  list ($stateWasRead, $managerState) = readManagerState('managers');
  $protectedPids[] = getmypid();
  if (isset($managerState['CoreManagerPid'])) $protectedPids[] = (int)$managerState['CoreManagerPid'];
  if (isset($managerState['Subordinates'])) foreach ($managerState['Subordinates'] as $processLine) $protectedPids[] = (int)$processLine;
  exec('pgrep -u '.escapeshellarg($ApacheUser).' php 2>/dev/null', $processOutput, $processExitCode);
  foreach ($processOutput as $processLine) {
    $processPid = (int)trim($processLine);
    if ($processPid > 1 && !in_array($processPid, $protectedPids, TRUE)) {
      if (terminateWorkerProcess($processPid, TRUE, 0)) $ProcessesKilled++; } }
  // / The registry lives in Resource Manager memory, so it is told to forget rather than
  // / having a file emptied underneath it.
  sendManagerMessage(buildManagerSocketPath('resource-manager'), array('RequestType' => 'kill-tracked'), 'internal', (int)$ManagerSocketTimeout);
  warningEntry('Every process owned by '.$ApacheUser.' was terminated on request. '.$ProcessesKilled.' process(es) ended. Unrelated applications on this host were affected.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $processOutput, $processExitCode, $processLine, $processPid, $protectedPids, $managerState, $stateWasRead);
  return $ProcessesKilled; }
// / -----------------------------------------------------------------------------------

?>
