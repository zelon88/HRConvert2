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
// / v4.1.3.
// / This file is the Request Manager. It is a manager subcomponent of the Engine.
// / The Request Manager owns the only socket a worker may address. It refuses a request a
// / worker may not issue & forwards the rest, so a budget request reaches the Resource
// / Manager, which grants & reclaims every token, without a worker ever addressing it.
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
$ManagerVersion = 'v4.1.3';
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
  $lastLogRefresh = time();
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
      // / A manager runs for weeks, so it moves on to the logfile it should be writing now, once a minute.
      $lastLogRefresh = refreshProcessLog($lastLogRefresh);
      list ($messagesReceived, $managerMessages, $managerConnections) = receiveManagerMessages($socketServer, 'worker', (int)$ManagerMessageBatchSize, (int)$ManagerSocketTimeout);
      $messageIndex = 0;
      while ($messageIndex < $messagesReceived) {
        // / A worker may not issue a manager instruction. Only these types pass.
        if (isset($managerMessages[$messageIndex]['RequestType']) && in_array($managerMessages[$messageIndex]['RequestType'], array('budget', 'release', 'extend', 'convertloc', 'storage'), TRUE)) {
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
  purgeSensitiveMemory($EnableMemoryProtection, $socketServer, $serverIsOpen, $keepRunning, $forwardWasDelivered, $socketPath, $coreSocket, $managerMessages, $managerConnections, $forwardedReply, $replyPayload, $messagesReceived, $messageIndex, $lastLogRefresh);
  return $RequestManagerExitedCleanly; }
// / -----------------------------------------------------------------------------------

?>
