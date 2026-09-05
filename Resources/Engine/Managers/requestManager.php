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
// / This file is the Request Manager. It is a manager subcomponent of the Engine.
// / The Request Manager answers a request for budget, grants a token & takes it back when the work is finished or abandoned.
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
// / A function to run the Request Manager loop.
// / Accepts no arguments & does not return until the process is terminated.
// / Returns TRUE when the loop exited cleanly.
// / Request Manager is the only socket a worker may address. It validates & forwards.
function runRequestManager() {
  // / Set variables.
  global $ManagerSocketTimeout, $ManagerMessageBatchSize, $EnableMemoryProtection, $Verbose;
  $RequestManagerExitedCleanly = FALSE;
  $socketServer = FALSE;
  $serverIsOpen = $keepRunning = $forwardWasDelivered = FALSE;
  $socketPath = $coreSocket = '';
  $managerMessages = $managerConnections = $forwardedReply = $replyPayload = array();
  $messagesReceived = $messageIndex = 0;
  $socketPath = buildManagerSocketPath('request-manager');
  $coreSocket = buildManagerSocketPath('core-manager');
  list ($serverIsOpen, $socketServer) = openManagerSocketServer($socketPath);
  if (!$serverIsOpen) errorEntry('The Request Manager could not open its socket!', 31001, TRUE);
  else {
    // / The worker facing socket is the only one a non manager process may reach.
    @chmod($socketPath, 0660);
    logEntry('Request Manager started.');
    $keepRunning = TRUE;
    if ($Verbose) logEntry('Request Manager is listening for worker requests on '.basename($socketPath).'.');
    while ($keepRunning) {
      list ($messagesReceived, $managerMessages, $managerConnections) = receiveManagerMessages($socketServer, 'worker', (int)$ManagerMessageBatchSize, (int)$ManagerSocketTimeout);
      $messageIndex = 0;
      while ($messageIndex < $messagesReceived) {
        // / A worker may not issue a manager instruction. Only these three types pass.
        if (isset($managerMessages[$messageIndex]['RequestType']) && in_array($managerMessages[$messageIndex]['RequestType'], array('budget', 'release', 'extend', 'convertloc'), TRUE)) {
          list ($forwardWasDelivered, $forwardedReply) = sendManagerMessage($coreSocket, $managerMessages[$messageIndex], 'core', (int)$ManagerSocketTimeout * 2);
          if ($forwardWasDelivered && !empty($forwardedReply)) $replyPayload = $forwardedReply;
          else $replyPayload = array('Approved' => FALSE, 'Reason' => 'The Core Manager did not answer.'); 
          if ($Verbose) logEntry('Request Manager forwarded a '.$managerMessages[$messageIndex]['RequestType'].' request from worker '.(isset($managerMessages[$messageIndex]['WorkerPid']) ? (int)$managerMessages[$messageIndex]['WorkerPid'] : 0).'.'); }
        else {
          warningEntry('A worker submitted a request type it is not permitted to issue & it was refused.');
          $replyPayload = array('Approved' => FALSE, 'Reason' => 'That request type is not available to a worker.'); }
        replyToManagerMessage($managerConnections[$messageIndex], $replyPayload, 'worker');
        $messageIndex++; } }
    $RequestManagerExitedCleanly = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $socketServer, $serverIsOpen, $keepRunning, $forwardWasDelivered, $socketPath, $coreSocket, $managerMessages, $managerConnections, $forwardedReply, $replyPayload, $messagesReceived, $messageIndex);
  return $RequestManagerExitedCleanly; }
// / -----------------------------------------------------------------------------------

?>
