<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRConvert2, Copyright on 8/20/2026 by Justin Grimes, www.github.com/zelon88
// /
// / LICENSE INFORMATION ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / APPLICATION INFORMATION ...
// / This application is designed to provide a web-interface for converting file formats
// / on a server for users of any web browser without authentication.
// /
// / FILEINFORMATION ...
// / v3.7.8.
// / This file contains the core manager resource listener logic of the application.
// / This file contains logic related to rate planning & resouce management.
// /
// / HARDWARE REQUIREMENTS ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / DEPENDENCY REQUIREMENTS ...
// / This application requires Debian Linux, Apache 2.4, PHP 8+, FFMPEG, Dia, LibreOffice, 
// / Mkisofs, 7zip, Unoconv, libgxps-utils, Tesseract, Unzip, OpenSCAD, Rar, Inkscape, Calibre,
// / Unrar, ClamAV, MeshLab, PopplerUtils, PDFTOTEXT, ImageMagick, bwrap Dia & xvfb-run.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Refuse direct execution. This file is a component & has no standalone context.
// / This is the ONE halt in the application that cannot use quickDie. Reaching this line
// / means convertCore.php was never loaded, so quickDie is not defined & calling it would
// / replace a clear refusal with an undefined function error.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-2: This file cannot process your request! Please submit your file to convertCore.php instead!'.PHP_EOL);
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The component version. convertCore.php checks this without executing the file.
$CoreManagerVersion = 'v3.7.8';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to derive a message encryption key for one purpose from the install secret.
// / Accepts a purpose string that separates one message channel from another.
// / Returns a raw 32 byte key, or an empty string when no secret is available.
function deriveManagerKey($keyPurpose) {
  // / Set variables.
  global $SecretKey, $EnableMemoryProtection;
  $DerivedKey = '';
  $keyMaterial = '';
  if (is_string($SecretKey) && strlen($SecretKey) === 64) {
    $keyMaterial = $SecretKey.'|coreManager|'.$keyPurpose;
    $DerivedKey = hash('sha256', $keyMaterial, TRUE); }
  purgeSensitiveMemory($EnableMemoryProtection, $keyMaterial, $keyPurpose);
  return $DerivedKey; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to encrypt a message payload for transport over a manager socket.
// / Accepts an array payload & the channel purpose.
// / Returns a single line base64 string, or an empty string on failure.
// / A timestamp & a nonce are added so a captured message cannot be replayed later.
function encryptManagerMessage($messagePayload, $keyPurpose) {
  // / Set variables.
  global $EnableMemoryProtection;
  $EncryptedMessage = '';
  $messageKey = deriveManagerKey($keyPurpose);
  $messageJson = $messageTag = $messageCipher = '';
  $messageIv = '';
  if ($messageKey !== '' && is_array($messagePayload)) {
    $messagePayload['MessageTime'] = time();
    $messagePayload['MessageNonce'] = bin2hex(random_bytes(8));
    $messageJson = json_encode($messagePayload);
    $messageIv = random_bytes(12);
    $messageCipher = openssl_encrypt($messageJson, 'aes-256-gcm', $messageKey, OPENSSL_RAW_DATA, $messageIv, $messageTag);
    if ($messageCipher !== FALSE) $EncryptedMessage = base64_encode($messageIv.$messageTag.$messageCipher); }
  purgeSensitiveMemory($EnableMemoryProtection, $messageKey, $messageJson, $messageIv, $messageTag, $messageCipher, $messagePayload, $keyPurpose);
  return $EncryptedMessage; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decrypt & validate a message received over a manager socket.
// / Accepts the base64 payload & the channel purpose.
// / Returns a validity boolean & the decoded array, in that order.
// / A message older than the configured skew window is refused as a replay.
function decryptManagerMessage($encryptedPayload, $keyPurpose) {
  // / Set variables.
  global $ManagerMessageSkew, $EnableMemoryProtection;
  $MessageIsValid = FALSE;
  $MessagePayload = array();
  $messageKey = deriveManagerKey($keyPurpose);
  $rawPayload = $messageIv = $messageTag = $messageCipher = $messageJson = '';
  $decodedPayload = array();
  $messageAge = 0;
  if ($messageKey !== '' && is_string($encryptedPayload) && $encryptedPayload !== '') {
    $rawPayload = base64_decode(trim($encryptedPayload), TRUE);
    if (is_string($rawPayload) && strlen($rawPayload) > 28) {
      $messageIv = substr($rawPayload, 0, 12);
      $messageTag = substr($rawPayload, 12, 16);
      $messageCipher = substr($rawPayload, 28);
      $messageJson = openssl_decrypt($messageCipher, 'aes-256-gcm', $messageKey, OPENSSL_RAW_DATA, $messageIv, $messageTag);
      if (is_string($messageJson) && $messageJson !== '') {
        $decodedPayload = json_decode($messageJson, TRUE);
        if (is_array($decodedPayload) && isset($decodedPayload['MessageTime'])) {
          $messageAge = abs(time() - (int)$decodedPayload['MessageTime']);
          if ($messageAge <= (int)$ManagerMessageSkew) {
            $MessagePayload = $decodedPayload;
            $MessageIsValid = TRUE; } } } } }
  purgeSensitiveMemory($EnableMemoryProtection, $messageKey, $rawPayload, $messageIv, $messageTag, $messageCipher, $messageJson, $decodedPayload, $messageAge, $encryptedPayload, $keyPurpose);
  return array($MessageIsValid, $MessagePayload); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to build the absolute path of a named manager socket.
// / Accepts the short socket name.
// / Returns the absolute path. Sockets live in the data location & never in the web root.
function buildManagerSocketPath($socketName) {
  // / Set variables.
  global $ManagerSocketDir, $DirSep, $EnableMemoryProtection;
  $ManagerSocketPath = '';
  $cleanSocketName = preg_replace('/[^A-Za-z0-9\-]/', '', (string)$socketName);
  if ($cleanSocketName !== '') $ManagerSocketPath = $ManagerSocketDir.$DirSep.$cleanSocketName.'.sock';
  purgeSensitiveMemory($EnableMemoryProtection, $cleanSocketName, $socketName);
  return $ManagerSocketPath; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to open a listening unix domain socket for a manager.
// / Accepts the absolute socket path.
// / Returns a success boolean & the stream resource, in that order.
// / A stale socket file from a crashed manager is removed before binding.
function openManagerSocketServer($socketPath) {
  // / Set variables.
  global $EnableMemoryProtection, $Verbose;
  $ServerIsOpen = FALSE;
  $SocketServer = FALSE;
  $socketError = '';
  $socketErrorNumber = 0;
  if (file_exists($socketPath)) @unlink($socketPath);
  $SocketServer = @stream_socket_server('unix://'.$socketPath, $socketErrorNumber, $socketError);
  if ($SocketServer !== FALSE) {
    @chmod($socketPath, 0600);
    stream_set_blocking($SocketServer, FALSE);
    $ServerIsOpen = TRUE;
    if ($Verbose) logEntry('Bound a manager socket at '.$socketPath.'.'); }
  else warningEntry('Could not open the manager socket at '.$socketPath.'. '.$socketError);
  purgeSensitiveMemory($EnableMemoryProtection, $socketError, $socketErrorNumber, $socketPath);
  return array($ServerIsOpen, $SocketServer); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to send one encrypted message to a manager socket & read the reply.
// / Accepts the socket path, the array payload, the channel purpose & a timeout in seconds.
// / Returns a delivery boolean & the decoded reply array, in that order.
// / The reply travels back on the connection the sender opened, so no process ever needs
// / to be addressable by another. A worker cannot be reached by anything it did not call.
function sendManagerMessage($socketPath, $messagePayload, $keyPurpose, $timeoutSeconds) {
  // / Set variables.
  global $EnableMemoryProtection, $Verbose;
  $MessageWasDelivered = FALSE;
  $ReplyPayload = array();
  $socketClient = FALSE;
  $socketError = $encryptedMessage = $rawReply = '';
  $socketErrorNumber = $bytesWritten = 0;
  $replyIsValid = FALSE;
  $encryptedMessage = encryptManagerMessage($messagePayload, $keyPurpose);
  if ($encryptedMessage !== '' && file_exists($socketPath)) {
    $socketClient = @stream_socket_client('unix://'.$socketPath, $socketErrorNumber, $socketError, (float)$timeoutSeconds);
    if ($socketClient !== FALSE) {
      stream_set_timeout($socketClient, (int)$timeoutSeconds);
      $bytesWritten = @fwrite($socketClient, $encryptedMessage.PHP_EOL);
      if ($bytesWritten > 0) {
        $MessageWasDelivered = TRUE;
        if ($Verbose) logEntry('Sent a '.(isset($messagePayload['RequestType']) ? $messagePayload['RequestType'] : 'unknown').' message to '.basename($socketPath).'.');
        $rawReply = @fgets($socketClient, 65536);
        if (is_string($rawReply) && trim($rawReply) !== '') list ($replyIsValid, $ReplyPayload) = decryptManagerMessage($rawReply, $keyPurpose); }
      @fclose($socketClient); } }
  purgeSensitiveMemory($EnableMemoryProtection, $socketError, $socketErrorNumber, $encryptedMessage, $rawReply, $bytesWritten, $replyIsValid, $socketPath, $messagePayload, $keyPurpose, $timeoutSeconds);
  return array($MessageWasDelivered, $ReplyPayload); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to accept & decrypt a batch of pending messages from a listening socket.
// / Accepts the server stream, the channel purpose, a message ceiling & a wait in seconds.
// / Returns the count accepted, the decoded messages & their open connections, in that order.
// / Connections are returned open so the caller can write a reply to the correct sender.
function receiveManagerMessages($socketServer, $keyPurpose, $maxMessages, $waitSeconds) {
  // / Set variables.
  global $EnableMemoryProtection, $Verbose;
  $MessagesReceived = 0;
  $ManagerMessages = array();
  $ManagerConnections = array();
  $socketConnection = FALSE;
  $rawMessage = '';
  $messageIsValid = FALSE;
  $messagePayload = array();
  $messageCounter = 0;
  while ($messageCounter < (int)$maxMessages) {
    $socketConnection = @stream_socket_accept($socketServer, (float)$waitSeconds);
    if ($socketConnection === FALSE) break;
    stream_set_timeout($socketConnection, (int)$waitSeconds + 1);
    $rawMessage = @fgets($socketConnection, 65536);
    list ($messageIsValid, $messagePayload) = decryptManagerMessage($rawMessage, $keyPurpose);
    if ($messageIsValid) {
      $ManagerMessages[] = $messagePayload;
      $ManagerConnections[] = $socketConnection;
      $MessagesReceived++; }
    else {
      warningEntry('A manager socket message failed decryption or replay validation & was discarded.');
      @fclose($socketConnection); }
    $messageCounter++;
    $waitSeconds = 0; }
  if ($Verbose && $MessagesReceived > 0) logEntry('Accepted '.$MessagesReceived.' message(s) on the '.$keyPurpose.' channel.');
  purgeSensitiveMemory($EnableMemoryProtection, $rawMessage, $messageIsValid, $messagePayload, $messageCounter, $keyPurpose, $maxMessages, $waitSeconds);
  return array($MessagesReceived, $ManagerMessages, $ManagerConnections); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write an encrypted reply onto an accepted connection & close it.
// / Accepts the open connection, the array payload & the channel purpose.
// / Returns TRUE when the reply was written.
function replyToManagerMessage($socketConnection, $replyPayload, $keyPurpose) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ReplyWasSent = FALSE;
  $encryptedReply = encryptManagerMessage($replyPayload, $keyPurpose);
  $bytesWritten = 0;
  if ($encryptedReply !== '' && is_resource($socketConnection)) {
    $bytesWritten = @fwrite($socketConnection, $encryptedReply.PHP_EOL);
    if ($bytesWritten > 0) $ReplyWasSent = TRUE; }
  if (is_resource($socketConnection)) @fclose($socketConnection);
  purgeSensitiveMemory($EnableMemoryProtection, $encryptedReply, $bytesWritten, $replyPayload, $keyPurpose);
  return $ReplyWasSent; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read a shared manager state file.
// / Accepts the short state name.
// / Returns a success boolean & the state array, in that order.
// / State is shared through the filesystem because every manager is a separate process.
function readManagerState($stateName) {
  // / Set variables.
  global $ManagerSocketDir, $DirSep, $EnableMemoryProtection;
  $StateWasRead = FALSE;
  $ManagerState = array();
  $statePath = $stateContents = '';
  $decodedState = array();
  $cleanStateName = preg_replace('/[^A-Za-z0-9\-]/', '', (string)$stateName);
  if ($cleanStateName !== '') {
    $statePath = $ManagerSocketDir.$DirSep.$cleanStateName.'.state';
    if (file_exists($statePath)) {
      $stateContents = @file_get_contents($statePath);
      if (is_string($stateContents) && $stateContents !== '') {
        $decodedState = json_decode($stateContents, TRUE);
        if (is_array($decodedState)) {
          $ManagerState = $decodedState;
          $StateWasRead = TRUE; } } } }
  purgeSensitiveMemory($EnableMemoryProtection, $statePath, $stateContents, $decodedState, $cleanStateName, $stateName);
  return array($StateWasRead, $ManagerState); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write a shared manager state file.
// / Accepts the short state name & the array to store.
// / Returns TRUE when the file was written completely.
function writeManagerState($stateName, $stateData) {
  // / Set variables.
  global $ManagerSocketDir, $DirSep, $EnableMemoryProtection;
  $StateWasWritten = FALSE;
  $statePath = $stateContents = '';
  $bytesWritten = 0;
  $cleanStateName = preg_replace('/[^A-Za-z0-9\-]/', '', (string)$stateName);
  if ($cleanStateName !== '' && is_array($stateData)) {
    $statePath = $ManagerSocketDir.$DirSep.$cleanStateName.'.state';
    $stateContents = json_encode($stateData);
    $bytesWritten = @file_put_contents($statePath, $stateContents, LOCK_EX);
    if ($bytesWritten === strlen($stateContents)) {
      @chmod($statePath, 0600);
      $StateWasWritten = TRUE; } }
  purgeSensitiveMemory($EnableMemoryProtection, $statePath, $stateContents, $bytesWritten, $cleanStateName, $stateName, $stateData);
  return $StateWasWritten; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to poll actual system resource consumption.
// / Accepts no arguments.
// / Returns a success boolean & an array describing the host, in that order.
// / Load average is the primary signal because it reflects queued work, not just used memory.
function pollSystemResources() {
  // / Set variables.
  global $EnableMemoryProtection;
  $ResourcesPolled = FALSE;
  $SystemResources = array('CpuCount' => 1, 'LoadAverage' => 0.0, 'LoadPercentage' => 0.0, 'MemoryTotalKb' => 0, 'MemoryAvailableKb' => 0, 'MemoryUsedPercentage' => 0.0);
  $loadAverages = array();
  $memoryInfo = $cpuInfo = '';
  $memoryMatches = array();
  $cpuCount = 1;
  // / Processor count. nproc is not always present, so cpuinfo is the fallback.
  $cpuInfo = @file_get_contents('/proc/cpuinfo');
  if (is_string($cpuInfo) && $cpuInfo !== '') $cpuCount = max(1, substr_count($cpuInfo, 'processor'));
  $SystemResources['CpuCount'] = $cpuCount;
  // / Load average over one minute.
  if (function_exists('sys_getloadavg')) {
    $loadAverages = sys_getloadavg();
    if (is_array($loadAverages) && isset($loadAverages[0])) {
      $SystemResources['LoadAverage'] = (float)$loadAverages[0];
      $SystemResources['LoadPercentage'] = round(((float)$loadAverages[0] / $cpuCount) * 100, 2);
      $ResourcesPolled = TRUE; } }
  // / Available memory rather than free memory. Free memory excludes reclaimable cache.
  $memoryInfo = @file_get_contents('/proc/meminfo');
  if (is_string($memoryInfo) && $memoryInfo !== '') {
    if (preg_match('/MemTotal:\s+(\d+)/', $memoryInfo, $memoryMatches)) $SystemResources['MemoryTotalKb'] = (int)$memoryMatches[1];
    if (preg_match('/MemAvailable:\s+(\d+)/', $memoryInfo, $memoryMatches)) $SystemResources['MemoryAvailableKb'] = (int)$memoryMatches[1];
    if ($SystemResources['MemoryTotalKb'] > 0) $SystemResources['MemoryUsedPercentage'] = round((1 - ($SystemResources['MemoryAvailableKb'] / $SystemResources['MemoryTotalKb'])) * 100, 2);
    $ResourcesPolled = TRUE; }
  purgeSensitiveMemory($EnableMemoryProtection, $loadAverages, $memoryInfo, $cpuInfo, $memoryMatches, $cpuCount);
  return array($ResourcesPolled, $SystemResources); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to recalculate the resource budget against observed system load.
// / Accepts the polled resource array & the currently allocated budget.
// / Returns a success boolean & the budget array, in that order.
// / The budget shrinks when the host is already busy, so an idle server is not throttled
// / & a loaded server stops accepting work before it falls over.
function calculateResourceBudget($systemResources, $allocatedBudget) {
  // / Set variables.
  global $TotalResourceBudget, $ReserveResourcePercentage, $EnableMemoryProtection;
  $BudgetCalculated = FALSE;
  $ResourceBudget = array('TotalBudget' => 0, 'ReserveBudget' => 0, 'AllocatedBudget' => 0, 'RemainingBudget' => 0, 'ConsumedBudget' => 0, 'AllowNextWorker' => FALSE, 'BudgetTime' => time());
  $baseBudget = $reserveBudget = $pressurePenalty = $usableBudget = 0;
  $highestPressure = 0.0;
  if (is_array($systemResources)) {
    // / A configured budget of zero derives the budget from the processor count.
    $baseBudget = (int)$TotalResourceBudget;
    if ($baseBudget < 1) $baseBudget = max(1, (int)$systemResources['CpuCount']) * 100;
    $reserveBudget = (int)floor($baseBudget * ((int)$ReserveResourcePercentage / 100));
    // / Whichever of load or memory is under more pressure governs the budget.
    $highestPressure = max((float)$systemResources['LoadPercentage'], (float)$systemResources['MemoryUsedPercentage']);
    if ($highestPressure > 100) $highestPressure = 100;
    $pressurePenalty = (int)floor($baseBudget * ($highestPressure / 100));
    $usableBudget = $baseBudget - $reserveBudget - $pressurePenalty;
    if ($usableBudget < 0) $usableBudget = 0;
    $ResourceBudget['TotalBudget'] = $baseBudget;
    $ResourceBudget['ReserveBudget'] = $reserveBudget;
    $ResourceBudget['AllocatedBudget'] = (int)$allocatedBudget;
    $ResourceBudget['ConsumedBudget'] = (int)$allocatedBudget + $pressurePenalty;
    $ResourceBudget['RemainingBudget'] = $usableBudget - (int)$allocatedBudget;
    if ($ResourceBudget['RemainingBudget'] < 0) $ResourceBudget['RemainingBudget'] = 0;
    if ($ResourceBudget['RemainingBudget'] > 0) $ResourceBudget['AllowNextWorker'] = TRUE;
    $BudgetCalculated = TRUE; }
  purgeSensitiveMemory($EnableMemoryProtection, $baseBudget, $reserveBudget, $pressurePenalty, $usableBudget, $highestPressure, $systemResources, $allocatedBudget);
  return array($BudgetCalculated, $ResourceBudget); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide whether one budget request may proceed.
// / Accepts the current budget array, the conversion cost & the expected runtime.
// / Returns an approval boolean & a denial reason string, in that order.
function evaluateBudgetRequest($resourceBudget, $workerRegistry, $conversionCost, $expectedRuntime) {
  // / Set variables.
  global $MaxConcurrentWorkers, $MaxExpectedRuntime, $EnableMemoryProtection;
  $RequestApproved = FALSE;
  $DenialReason = '';
  $requestedCost = (int)$conversionCost;
  $requestedRuntime = (int)$expectedRuntime;
  $trackedWorkers = count($workerRegistry);
  if ($requestedCost < 1) $requestedCost = 1;
  if ($requestedRuntime > (int)$MaxExpectedRuntime) $DenialReason = 'The expected runtime exceeds the configured maximum.';
  else if ((int)$MaxConcurrentWorkers > 0 && $trackedWorkers >= (int)$MaxConcurrentWorkers) $DenialReason = 'The concurrent worker limit has been reached.';
  else if (!isset($resourceBudget['RemainingBudget'])) $DenialReason = 'The resource budget is unavailable.';
  else if ((int)$resourceBudget['RemainingBudget'] < $requestedCost) $DenialReason = 'The remaining resource budget is insufficient.';
  else $RequestApproved = TRUE;
  purgeSensitiveMemory($EnableMemoryProtection, $requestedCost, $requestedRuntime, $trackedWorkers, $resourceBudget, $workerRegistry, $conversionCost, $expectedRuntime);
  return array($RequestApproved, $DenialReason); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to add an approved worker to the tracked registry.
// / Accepts the worker process identifier, the cost & the expected runtime.
// / Returns a success boolean & the issued budget token, in that order.
function registerTrackedWorker(&$workerRegistry, $workerPid, $conversionCost, $expectedRuntime) {
  // / Set variables.
  global $EnableMemoryProtection;
  $WorkerWasRegistered = FALSE;
  $BudgetToken = bin2hex(random_bytes(12));
  $workerRegistry[$BudgetToken] = array(
    'WorkerPid' => (int)$workerPid,
    'ConversionCost' => (int)$conversionCost,
    'ExpectedRuntime' => (int)$expectedRuntime,
    'StartTime' => time(),
    'CheckTime' => time(),
    'Extensions' => 0);
  if (isset($workerRegistry[$BudgetToken])) $WorkerWasRegistered = TRUE;
  purgeSensitiveMemory($EnableMemoryProtection, $workerPid, $conversionCost, $expectedRuntime);
  return array($WorkerWasRegistered, $BudgetToken); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove a completed worker from the tracked registry.
// / Accepts the budget token issued at registration.
// / Returns TRUE when the worker was found & released.
function releaseTrackedWorker(&$workerRegistry, $budgetToken) {
  // / Set variables.
  global $EnableMemoryProtection;
  $WorkerWasReleased = FALSE;
  $cleanToken = preg_replace('/[^a-f0-9]/', '', (string)$budgetToken);
  if ($cleanToken !== '' && isset($workerRegistry[$cleanToken])) {
    unset($workerRegistry[$cleanToken]);
    $WorkerWasReleased = TRUE; }
  purgeSensitiveMemory($EnableMemoryProtection, $cleanToken, $budgetToken);
  return $WorkerWasReleased; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to extend the expected runtime of a tracked worker.
// / Accepts the budget token & the number of seconds requested.
// / Returns an approval boolean & the new expected runtime, in that order.
function extendTrackedWorker(&$workerRegistry, $budgetToken, $requestedSeconds) {
  // / Set variables.
  global $MaxRuntimeExtensions, $MaxExpectedRuntime, $EnableMemoryProtection;
  $ExtensionApproved = FALSE;
  $NewExpectedRuntime = 0;
  $cleanToken = preg_replace('/[^a-f0-9]/', '', (string)$budgetToken);
  $proposedRuntime = 0;
  if ($cleanToken !== '' && isset($workerRegistry[$cleanToken])) {
    $NewExpectedRuntime = (int)$workerRegistry[$cleanToken]['ExpectedRuntime'];
    $proposedRuntime = $NewExpectedRuntime + max(1, (int)$requestedSeconds);
    if ((int)$workerRegistry[$cleanToken]['Extensions'] < (int)$MaxRuntimeExtensions && $proposedRuntime <= (int)$MaxExpectedRuntime) {
      $workerRegistry[$cleanToken]['ExpectedRuntime'] = $proposedRuntime;
      $workerRegistry[$cleanToken]['Extensions'] = (int)$workerRegistry[$cleanToken]['Extensions'] + 1;
      $workerRegistry[$cleanToken]['CheckTime'] = time();
      $NewExpectedRuntime = $proposedRuntime;
      $ExtensionApproved = TRUE; } }
  purgeSensitiveMemory($EnableMemoryProtection, $cleanToken, $proposedRuntime, $budgetToken, $requestedSeconds);
  return array($ExtensionApproved, $NewExpectedRuntime); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to find tracked workers that have outlived their expected runtime.
// / Accepts a grace period in seconds added to every expected runtime.
// / Returns a scan boolean & an array of stale token to process identifier pairs.
// / A worker whose process has already exited is reported so its budget can be reclaimed.
function findStaleWorkers($workerRegistry, $graceSeconds) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ScanCompleted = TRUE;
  $StaleWorkers = array();
  $workerToken = '';
  $workerRecord = array();
  $workerAge = $workerDeadline = 0;
  foreach ($workerRegistry as $workerToken => $workerRecord) {
    $workerAge = time() - (int)$workerRecord['StartTime'];
    $workerDeadline = (int)$workerRecord['ExpectedRuntime'] + (int)$graceSeconds;
    // / A process that no longer exists never sent its completion message.
    if (!managerProcessIsAlive((int)$workerRecord['WorkerPid'])) $StaleWorkers[$workerToken] = array('WorkerPid' => (int)$workerRecord['WorkerPid'], 'StaleReason' => 'Process has exited');
    else if ($workerAge > $workerDeadline) $StaleWorkers[$workerToken] = array('WorkerPid' => (int)$workerRecord['WorkerPid'], 'StaleReason' => 'Runtime exceeded by '.($workerAge - $workerDeadline).' second(s)'); }
  purgeSensitiveMemory($EnableMemoryProtection, $workerToken, $workerRecord, $workerAge, $workerDeadline, $workerRegistry, $graceSeconds);
  return array($ScanCompleted, $StaleWorkers); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to terminate one worker process.
// / Accepts the process identifier & a boolean requesting an immediate kill.
// / Returns TRUE when the process is no longer present after the attempt.
// / This is the only place in the component that acts on a process without negotiation.
function terminateWorkerProcess($workerPid, $forceKill) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ProcessWasTerminated = FALSE;
  $cleanPid = (int)$workerPid;
  $killSignal = 15;
  $killOutput = array();
  $killExitCode = 1;
  if ($forceKill) $killSignal = 9;
  // / Never signal the whole process group & never signal process zero.
  if ($cleanPid > 1 && $cleanPid !== getmypid()) {
    if (!file_exists('/proc/'.$cleanPid)) $ProcessWasTerminated = TRUE;
    else {
      exec('kill -'.$killSignal.' '.escapeshellarg((string)$cleanPid).' 2>&1', $killOutput, $killExitCode);
      usleep(250000);
      if (!file_exists('/proc/'.$cleanPid)) $ProcessWasTerminated = TRUE; } }
  purgeSensitiveMemory($EnableMemoryProtection, $cleanPid, $killSignal, $killOutput, $killExitCode, $workerPid, $forceKill);
  return $ProcessWasTerminated; }
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
    $spawnCommand = 'nohup php '.escapeshellarg($InstLoc.$DirSep.'convertCore.php')
      .' --start-manager '.escapeshellarg($cleanRole)
      .' '.escapeshellarg($startupKey)
      .' > /dev/null 2>&1 & echo $!';
    $spawnOutput = shell_exec($spawnCommand);
    $ManagerPid = (int)trim((string)$spawnOutput);
    if ($ManagerPid > 0) { 
      $ManagerWasSpawned = TRUE;
      if ($Verbose) logEntry('Core Manager spawned the '.$cleanRole.' process as '.$ManagerPid.'.'); }
    else warningEntry('Could not spawn the '.$cleanRole.' manager process.'); }
  purgeSensitiveMemory($EnableMemoryProtection, $spawnCommand, $spawnOutput, $startupKey, $cleanRole, $managerRole);
  return array($ManagerWasSpawned, $ManagerPid); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run the Core Manager supervisor loop.
// / Accepts no arguments & does not return until the listener is stopped.
// / Returns TRUE when the loop exited on a stop instruction rather than a failure.
// / Core Manager owns no budget & no registry. It routes, supervises & reaps.
function runCoreManager() {
  // / Set variables.
  global $CoreManagerSubprocessPollInterval, $ManagerSocketTimeout, $ManagerMessageBatchSize, $Verbose, $EnableMemoryProtection;
  $CoreManagerExitedCleanly = FALSE;
  $socketServer = FALSE;
  $serverIsOpen = $keepRunning = $stateWasWritten = FALSE;
  $socketPath = $managerRole = $targetSocket = '';
  $managerState = $managerMessages = $managerConnections = $replyPayload = $routedReply = array();
  $messagesReceived = $messageIndex = $managerPid = 0;
  $managerWasSpawned = $replyWasDelivered = FALSE;
  $subordinateRoles = array('request-manager', 'resource-manager', 'worker-manager');
  $socketPath = buildManagerSocketPath('core-manager');
  list ($serverIsOpen, $socketServer) = openManagerSocketServer($socketPath);
  if (!$serverIsOpen) errorEntry('The Core Manager could not open its socket!', 31000, TRUE);
  else {
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
      list ($messagesReceived, $managerMessages, $managerConnections) = receiveManagerMessages($socketServer, 'core', (int)$ManagerMessageBatchSize, (int)$CoreManagerSubprocessPollInterval);
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
    foreach ($managerState['Subordinates'] as $managerRole => $managerPid) terminateWorkerProcess($managerPid, FALSE);
    if (is_resource($socketServer)) @fclose($socketServer);
    if (file_exists($socketPath)) @unlink($socketPath);
    logEntry('Core Manager stopped.'); }
  purgeSensitiveMemory($EnableMemoryProtection, $serverIsOpen, $keepRunning, $stateWasWritten, $socketPath, $managerRole, $targetSocket, $managerState, $managerMessages, $managerConnections, $replyPayload, $routedReply, $messagesReceived, $messageIndex, $managerPid, $managerWasSpawned, $replyWasDelivered, $subordinateRoles);
  return $CoreManagerExitedCleanly; }
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
  purgeSensitiveMemory($EnableMemoryProtection, $serverIsOpen, $keepRunning, $forwardWasDelivered, $socketPath, $coreSocket, $managerMessages, $managerConnections, $forwardedReply, $replyPayload, $messagesReceived, $messageIndex);
  return $RequestManagerExitedCleanly; }
// / -----------------------------------------------------------------------------------

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

// / A function to decide which data location a session uses & remember the answer.
// / Accepts the session map BY REFERENCE, the daily hash & the session hash.
// / Returns the absolute path as a string.
// / THE MAP IS A CACHE, NOT THE RECORD. resolveConvertLoc discovers an existing session
// / directory before it distributes anything, so a Resource Manager that restarted in the
// / middle of a session still returns the location that actually holds the files.
// / Load balancing happens here, once, when a session is first seen. Never again.
function resolveSessionLocation(&$sessionLocations, $dailyHash, $sessionHash) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $SessionConvertLoc = '';
  $cleanDaily = preg_replace('/[^A-Za-z0-9]/', '', (string)$dailyHash);
  $cleanSession = preg_replace('/[^A-Za-z0-9]/', '', (string)$sessionHash);
  $mapKey = $cleanDaily.'|'.$cleanSession;
  $entryIsCached = FALSE;
  if ($cleanSession === '') $SessionConvertLoc = resolveConvertLoc('', '');
  else if (isset($sessionLocations[$mapKey])) {
    $SessionConvertLoc = (string)$sessionLocations[$mapKey]['Path'];
    $sessionLocations[$mapKey]['LastSeen'] = time();
    $entryIsCached = TRUE; }
  else {
    $SessionConvertLoc = resolveConvertLoc($cleanDaily, $cleanSession);
    $sessionLocations[$mapKey] = array('Path' => $SessionConvertLoc, 'LastSeen' => time()); }
  if ($Verbose) logEntry('Session Location: '.$SessionConvertLoc.', Session: '.($cleanSession === '' ? 'NONE' : $cleanSession).', Source: '.($entryIsCached ? 'MAP' : 'RESOLVED').'.');
  purgeSensitiveMemory($EnableMemoryProtection, $cleanDaily, $cleanSession, $mapKey, $entryIsCached, $dailyHash, $sessionHash);
  return $SessionConvertLoc; }
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
  purgeSensitiveMemory($EnableMemoryProtection, $mapKey, $mapEntry, $expiredKeys, $maximumAgeSeconds);
  return $EntriesDropped; }
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
  global $DeleteThreshold, $Verbose, $EnableMemoryProtection;
  $SweepCompleted = TRUE;
  $LocationsSwept = 0;
  $convertLocPool = $poolEntry = array();
  $locationCleaned = $locationDeepCleaned = FALSE;
  $convertLocPool = enumerateConvertLocs();
  foreach ($convertLocPool as $poolEntry) {
    if (!is_dir($poolEntry['Path'])) warningEntry('The '.$poolEntry['Type'].' data location at '.$poolEntry['Path'].' does not exist & was not swept.');
    else {
      list ($locationCleaned, $locationDeepCleaned) = cleanDataLoc($poolEntry['Path'], 'ConvertLocPool', $DeleteThreshold);
      if (!$locationCleaned) $SweepCompleted = FALSE;
      else {
        $LocationsSwept++;
        if ($Verbose) logEntry('Scheduled sweep: '.$poolEntry['Path'].', Type: '.$poolEntry['Type'].', Expired sessions removed: '.($locationDeepCleaned ? 'YES' : 'NO').'.'); } } }
  purgeSensitiveMemory($EnableMemoryProtection, $convertLocPool, $poolEntry, $locationCleaned, $locationDeepCleaned);
  return array($SweepCompleted, $LocationsSwept); }
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
  purgeSensitiveMemory($EnableMemoryProtection, $serverIsOpen, $keepRunning, $resourcesPolled, $budgetCalculated, $requestApproved, $workerWasRegistered, $workerWasReleased, $extensionApproved, $sweepCompleted, $messageWasDelivered, $socketPath, $denialReason, $budgetToken, $sessionConvertLoc, $staleToken, $systemResources, $resourceBudget, $workerRegistry, $sessionLocations, $managerMessages, $managerConnections, $replyPayload, $staleWorkers, $staleRecord, $workerRecord, $killReply, $scaledLimits, $limitsWereScaled, $messagesReceived, $messageIndex, $allocatedBudget, $newExpectedRuntime, $lastPollTime, $lastReapTime, $lastSweepTime, $locationsSwept, $entriesDropped);
  return $ResourceManagerExitedCleanly; }
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
            $processWasTerminated = terminateWorkerProcess(($managerMessages[$messageIndex]['WorkerPid'] ?? 0), TRUE);
            if ($Verbose) logEntry('Worker Manager ended process '.(int)($managerMessages[$messageIndex]['WorkerPid'] ?? 0).'. Result: '.($processWasTerminated ? 'OK' : 'FAILED').'.');
            $replyPayload = array('Approved' => $processWasTerminated, 'Reason' => $processWasTerminated ? 'Terminated.' : 'The process could not be terminated.'); }
          else if ($managerMessages[$messageIndex]['RequestType'] === 'kill-every') {
            $killedCount = killEveryWorker();
            $replyPayload = array('Approved' => TRUE, 'Reason' => 'Terminated '.$killedCount.' process(es).', 'KilledCount' => $killedCount); } }
        replyToManagerMessage($managerConnections[$messageIndex], $replyPayload, 'internal');
        $messageIndex++; } }
    $WorkerManagerExitedCleanly = TRUE; }
  purgeSensitiveMemory($EnableMemoryProtection, $serverIsOpen, $keepRunning, $processWasTerminated, $socketPath, $managerMessages, $managerConnections, $replyPayload, $messagesReceived, $messageIndex, $killedCount);
  return $WorkerManagerExitedCleanly; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to terminate every tracked worker.
// / Accepts no arguments.
// / Returns the number of processes terminated.
function killTrackedWorkers(&$workerRegistry) {
  // / Set variables.
  global $ManagerSocketTimeout, $EnableMemoryProtection;
  $WorkersKilled = 0;
  $workerToken = '';
  $workerRecord = $replyPayload = array();
  $messageWasDelivered = FALSE;
  foreach ($workerRegistry as $workerToken => $workerRecord) {
    list ($messageWasDelivered, $replyPayload) = sendManagerMessage(buildManagerSocketPath('worker-manager'), array('RequestType' => 'kill', 'WorkerPid' => (int)$workerRecord['WorkerPid']), 'internal', (int)$ManagerSocketTimeout);
    if ($messageWasDelivered && isset($replyPayload['Approved']) && $replyPayload['Approved'] === TRUE) $WorkersKilled++; }
  $workerRegistry = array();
  warningEntry('Every tracked worker was ended on request. '.$WorkersKilled.' process(es) ended.');
  purgeSensitiveMemory($EnableMemoryProtection, $workerToken, $workerRecord, $replyPayload, $messageWasDelivered);
  return $WorkersKilled; }
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
      if (terminateWorkerProcess($processPid, TRUE)) $ProcessesKilled++; } }
  // / The registry lives in Resource Manager memory, so it is told to forget rather than
  // / having a file emptied underneath it.
  sendManagerMessage(buildManagerSocketPath('resource-manager'), array('RequestType' => 'kill-tracked'), 'internal', (int)$ManagerSocketTimeout);
  warningEntry('Every process owned by '.$ApacheUser.' was terminated on request. '.$ProcessesKilled.' process(es) ended. Unrelated applications on this host were affected.');
  purgeSensitiveMemory($EnableMemoryProtection, $processOutput, $processExitCode, $processLine, $processPid, $protectedPids, $managerState, $stateWasRead);
  return $ProcessesKilled; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to start the listener from an authorized command line invocation.
// / Accepts no arguments.
// / Returns TRUE when the Core Manager process was launched & recorded itself.
// / A listener that is already running is not started twice.
// / -----------------------------------------------------------------------------------
// / A function to start the listener from an authorized command line invocation.
// / Accepts no arguments.
// / Returns TRUE when the Core Manager process was launched & recorded itself.
// / A listener that is already running is not started twice.
function startCoreManagerListener() {
  // / Set variables.
  global $InstLoc, $ManagerSocketDir, $ApacheUser, $CurrentUser, $RunningAsRoot, $TotalResourceBudget, $ReserveResourcePercentage, $MaxConcurrentWorkers, $Verbose, $Lol, $EnableMemoryProtection;
  $ListenerWasStarted = FALSE;
  $managerState = $chownOutput = $listenerStatus = array();
  $stateWasRead = $directoryIsReady = $listenerIsRunning = FALSE;
  $startupKey = $spawnCommand = $innerCommand = $managerRole = '';
  $chownExitCode = $listenerPid = $waitCounter = $managerPid = 0;
  $directoryIsReady = prepareManagerSocketDir();
  // / Hand the socket directory to the web server user before anything binds inside it.
  // / A root owned directory is unreachable to the workers that must connect.
  if ($directoryIsReady && $RunningAsRoot) exec('chown -R '.escapeshellarg($ApacheUser).':'.escapeshellarg($ApacheUser).' '.escapeshellarg($ManagerSocketDir).' 2>&1', $chownOutput, $chownExitCode);
  list ($stateWasRead, $managerState) = readManagerState('managers');
  if ($stateWasRead && isset($managerState['CoreManagerPid']) && file_exists('/proc/'.(int)$managerState['CoreManagerPid'])) {
    print($Lol.'The listener is already running as process '.(int)$managerState['CoreManagerPid'].'.'.$Lol);
    print('Stop it with -k before starting another.'.$Lol.$Lol); }
  else if (!$directoryIsReady) errorEntry('The Core Manager socket directory could not be prepared!', 31004, FALSE);
  else {
    // / A stale record from a listener that died leaves a pid that no longer exists.
    writeManagerState('managers', array());
    $startupKey = deriveStartupKey('start-core-manager');
    $innerCommand = 'nohup php '.escapeshellarg($InstLoc.DIRECTORY_SEPARATOR.'convertCore.php')
      .' --start-core-manager '.escapeshellarg($startupKey)
      .' > /dev/null 2>&1 &';
    // / Drop to the web server user so every socket is owned by the account that must reach it.
    if ($RunningAsRoot && $CurrentUser !== $ApacheUser) $spawnCommand = 'su -s /bin/sh '.escapeshellarg($ApacheUser).' -c '.escapeshellarg($innerCommand);
    else $spawnCommand = $innerCommand;
    if ($Verbose) logEntry('Launching the Core Manager listener as '.($RunningAsRoot ? $ApacheUser.' through su' : $CurrentUser).'.');
    shell_exec($spawnCommand);
    // / The listener records its own pid, so wait for that rather than reading one back
    // / through su. Five seconds is generous for a process that only opens a socket.
    while ($waitCounter < 20 && $listenerPid < 1) {
      usleep(250000);
      list ($stateWasRead, $managerState) = readManagerState('managers');
      if ($stateWasRead && isset($managerState['CoreManagerPid'])) $listenerPid = (int)$managerState['CoreManagerPid'];
      $waitCounter++; }
    if ($listenerPid > 0 && file_exists('/proc/'.$listenerPid)) {
      $ListenerWasStarted = TRUE;
      logEntry('The Core Manager listener was started as process '.$listenerPid.' after '.round($waitCounter * 0.25, 2).' second(s).');
      // / Give the subordinates a moment to bind before reporting what came up.
      sleep(1);
      if ($Verbose) logEntry('Stop instruction sent to the Core Manager. Confirming every manager has ended.');
      list ($listenerIsRunning, $listenerStatus) = reportListenerStatus();
      print($Lol.'Listener started.'.$Lol);
      print($Lol.'  '.str_pad('Core Manager', 24).'process '.$listenerPid.$Lol);
      if (isset($listenerStatus['Subordinates'])) {
        foreach ($listenerStatus['Subordinates'] as $managerRole => $managerPid) print('  '.str_pad($managerRole, 24).'process '.(int)$managerPid.(file_exists('/proc/'.(int)$managerPid) ? '' : '  NOT RUNNING').$Lol); }
      print($Lol.'  '.str_pad('Running as', 24).$ApacheUser.$Lol);
      print('  '.str_pad('Socket directory', 24).$ManagerSocketDir.$Lol);
      print('  '.str_pad('Total budget', 24).((int)$TotalResourceBudget < 1 ? 'AUTO, derived from the processor count' : (int)$TotalResourceBudget.' cost units').$Lol);
      print('  '.str_pad('Reserved share', 24).(int)$ReserveResourcePercentage.'%'.$Lol);
      print('  '.str_pad('Concurrent limit', 24).((int)$MaxConcurrentWorkers < 1 ? 'NONE, the budget decides alone' : (int)$MaxConcurrentWorkers).$Lol);
      print($Lol.'Check it with --status & stop it with -k.'.$Lol.$Lol);
      if (count($listenerStatus['Subordinates']) < 3) print('One or more subordinate managers did not start. See the log.'.$Lol.$Lol); }
    else {
      errorEntry('The Core Manager listener could not be started!', 31005, FALSE);
      // / The startup key is NEVER printed & never written to the log. It authorizes a
      // / manager process for the length of a key window, & anything that captures console
      // / output or a logfile would be handed a working one.
      print($Lol.'The listener did not record itself within five seconds.'.$Lol);
      print('Check the log for the reason. A manager that started records itself there.'.$Lol);
      print('Confirm '.$ApacheUser.' can read the install secret & write the logfile,'.$Lol);
      print('then run the -fp argument as root & try again.'.$Lol.$Lol); } }
  purgeSensitiveMemory($EnableMemoryProtection, $managerState, $chownOutput, $listenerStatus, $stateWasRead, $directoryIsReady, $listenerIsRunning, $startupKey, $spawnCommand, $innerCommand, $managerRole, $chownExitCode, $listenerPid, $waitCounter, $managerPid);
  return $ListenerWasStarted; }
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to stop the listener & every subordinate manager.
// / Accepts no arguments.
// / Returns TRUE when no manager process remains.
// / Every process is asked first, then signalled, then verified. A process is only reported
// / as surviving once it has been checked several times, because a process that has been
// / signalled does not disappear from the process table instantly.
// / A recorded process identifier that no longer belongs to a manager is treated as stopped
// / rather than reported as an orphan, because a stale record is not a running process.
function stopCoreManagerListener() {
  // / Set variables.
  global $Lol, $ManagerSocketTimeout, $Verbose, $EnableMemoryProtection;
  $ListenerWasStopped = TRUE;
  $managerState = $replyPayload = $survivingProcesses = $recordedProcesses = array();
  $stateWasRead = $messageWasDelivered = FALSE;
  $managerRole = $processCommand = '';
  $managerPid = $waitCounter = 0;
  list ($stateWasRead, $managerState) = readManagerState('managers');
  if (!$stateWasRead or !isset($managerState['CoreManagerPid'])) {
    print($Lol.'No listener is recorded as running.'.$Lol.$Lol);
    $ListenerWasStopped = FALSE; }
  else {
    // / Collect every recorded process. The Core Manager is stopped last so it does not
    // / restart a subordinate that has already been ended.
    if (isset($managerState['Subordinates'])) {
      foreach ($managerState['Subordinates'] as $managerRole => $managerPid) $recordedProcesses[$managerRole] = (int)$managerPid; }
    $recordedProcesses['core-manager'] = (int)$managerState['CoreManagerPid'];
    // / Ask first. The Core Manager stops its subordinates in order when it is told to.
    list ($messageWasDelivered, $replyPayload) = sendManagerMessage(buildManagerSocketPath('core-manager'), array('RequestType' => 'stop'), 'core', (int)$ManagerSocketTimeout);
    if ($Verbose) logEntry('Stop instruction '.($messageWasDelivered ? 'delivered' : 'could not be delivered').'. Confirming every manager has ended.');
    // / Give a manager that accepted the instruction a moment to unwind on its own.
    $waitCounter = 0;
    while ($waitCounter < 8) {
      usleep(250000);
      $survivingProcesses = array();
      foreach ($recordedProcesses as $managerRole => $managerPid) { if (managerProcessIsAlive($managerPid)) $survivingProcesses[$managerRole] = $managerPid; }
      if (empty($survivingProcesses)) break;
      $waitCounter++; }
    // / Signal whatever did not leave. Subordinates first, then the Core Manager.
    foreach ($recordedProcesses as $managerRole => $managerPid) {
      if (managerProcessIsAlive($managerPid)) terminateWorkerProcess($managerPid, TRUE); }
    // / Verify. A process that is still present after this is reported by name.
    $survivingProcesses = array();
    foreach ($recordedProcesses as $managerRole => $managerPid) {
      if (managerProcessIsAlive($managerPid)) $survivingProcesses[$managerRole] = $managerPid; }
    writeManagerState('managers', array());
    if (empty($survivingProcesses)) {
      logEntry('The Core Manager listener was stopped.');
      print($Lol.'Listener stopped.'.$Lol.$Lol); }
    else {
      $ListenerWasStopped = FALSE;
      warningEntry(count($survivingProcesses).' manager process(es) survived a stop instruction & a kill signal.');
      print($Lol.'The listener could not be fully stopped.'.$Lol);
      foreach ($survivingProcesses as $managerRole => $managerPid) {
        $processCommand = trim((string)@file_get_contents('/proc/'.(int)$managerPid.'/comm'));
        print('  '.str_pad($managerRole, 20).'process '.(int)$managerPid.' is still present, running '.($processCommand === '' ? 'an unknown command' : $processCommand).$Lol); }
      print($Lol.'Kill them by hand, then run -l again.'.$Lol.$Lol); } }
  purgeSensitiveMemory($EnableMemoryProtection, $managerState, $replyPayload, $survivingProcesses, $recordedProcesses, $stateWasRead, $messageWasDelivered, $managerRole, $processCommand, $managerPid, $waitCounter);
  return $ListenerWasStopped; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report whether a recorded process identifier is a live manager process.
// / Accepts the process identifier.
// / Returns TRUE only when the process exists & is not a zombie awaiting collection.
// / A killed process whose parent has not reaped it keeps its entry under /proc, so testing
// / for the directory alone reports a process that has already ended as still running.
function managerProcessIsAlive($processId) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ProcessIsAlive = FALSE;
  $cleanPid = (int)$processId;
  $processState = $stateField = '';
  $stateMatches = array();
  if ($cleanPid > 1 && file_exists('/proc/'.$cleanPid.'/stat')) {
    $processState = (string)@file_get_contents('/proc/'.$cleanPid.'/stat');
    // / The state letter follows the command name, which may itself contain spaces & brackets.
    if (preg_match('/\)\s+(\S)/', $processState, $stateMatches)) $stateField = $stateMatches[1];
    if ($stateField !== '' && $stateField !== 'Z' && $stateField !== 'X') $ProcessIsAlive = TRUE; }
  purgeSensitiveMemory($EnableMemoryProtection, $cleanPid, $processState, $stateField, $stateMatches, $processId);
  return $ProcessIsAlive; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to create & secure the socket directory.
// / Accepts no arguments.
// / Returns TRUE when the directory exists & is writable.
function prepareManagerSocketDir() {
  // / Set variables.
  global $ManagerSocketDir, $EnableMemoryProtection;
  $DirectoryIsReady = FALSE;
  if (!is_dir($ManagerSocketDir)) @mkdir($ManagerSocketDir, 0700, TRUE);
  if (is_dir($ManagerSocketDir)) {
    @chmod($ManagerSocketDir, 0700);
    if (is_writable($ManagerSocketDir)) $DirectoryIsReady = TRUE; }
  if (!$DirectoryIsReady) warningEntry('The manager socket directory at '.$ManagerSocketDir.' is not usable.');
  purgeSensitiveMemory($EnableMemoryProtection);
  return $DirectoryIsReady; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to dispatch an internal manager role after its startup key is validated.
// / Accepts the role name & the supplied startup key.
// / Returns TRUE when a role ran to completion.
// / This is the single entry point convertCore.php uses to enter a manager process.
function dispatchManagerRole($managerRole, $suppliedKey) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RoleWasDispatched = FALSE;
  $keyIsValid = FALSE;
  $cleanRole = preg_replace('/[^a-z\-]/', '', strtolower((string)$managerRole));
  $keyPurpose = '';
  if ($cleanRole === 'core-manager') $keyPurpose = 'start-core-manager';
  else $keyPurpose = 'start-manager-'.$cleanRole;
  $keyIsValid = validateStartupKey($keyPurpose, $suppliedKey);
  if (!$keyIsValid) errorEntry('A manager process was started with an invalid startup key!', 31006, TRUE);
  else {
    prepareManagerSocketDir();
    logEntry('A '.$cleanRole.' process started as '.getmypid().' & validated its startup key.');
    if ($cleanRole === 'core-manager') $RoleWasDispatched = runCoreManager();
    else if ($cleanRole === 'request-manager') $RoleWasDispatched = runRequestManager();
    else if ($cleanRole === 'resource-manager') $RoleWasDispatched = runResourceManager();
    else if ($cleanRole === 'worker-manager') $RoleWasDispatched = runWorkerManager();
    else errorEntry('An unrecognized manager role was requested!', 31007, TRUE); }
  purgeSensitiveMemory($EnableMemoryProtection, $keyIsValid, $cleanRole, $keyPurpose, $managerRole, $suppliedKey);
  return $RoleWasDispatched; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to report listener status for the command line.
// / Accepts no arguments.
// / Returns a running boolean & a status array, in that order.
function reportListenerStatus() {
  // / Set variables.
  global $ManagerSocketTimeout, $EnableMemoryProtection;
  $ListenerIsRunning = FALSE;
  $ListenerStatus = array('CoreManagerPid' => 0, 'Subordinates' => array(), 'TrackedWorkers' => 0, 'SessionLocations' => 0, 'Budget' => array(), 'Answered' => FALSE);
  $managerState = $replyPayload = array();
  $stateWasRead = $messageWasDelivered = FALSE;
  list ($stateWasRead, $managerState) = readManagerState('managers');
  if ($stateWasRead && isset($managerState['CoreManagerPid'])) {
    $ListenerStatus['CoreManagerPid'] = (int)$managerState['CoreManagerPid'];
    if (managerProcessIsAlive((int)$managerState['CoreManagerPid'])) $ListenerIsRunning = TRUE;
    if (isset($managerState['Subordinates'])) $ListenerStatus['Subordinates'] = $managerState['Subordinates']; }
  if ($ListenerIsRunning) {
    list ($messageWasDelivered, $replyPayload) = sendManagerMessage(buildManagerSocketPath('core-manager'), array('RequestType' => 'status'), 'core', (int)$ManagerSocketTimeout * 3);
    if ($messageWasDelivered && isset($replyPayload['TrackedWorkers'])) {
      $ListenerStatus['TrackedWorkers'] = (int)$replyPayload['TrackedWorkers'];
      $ListenerStatus['SessionLocations'] = (int)($replyPayload['SessionLocations'] ?? 0);
      $ListenerStatus['Budget'] = (isset($replyPayload['Budget']) && is_array($replyPayload['Budget'])) ? $replyPayload['Budget'] : array();
      $ListenerStatus['Answered'] = TRUE; } }
  purgeSensitiveMemory($EnableMemoryProtection, $managerState, $replyPayload, $stateWasRead, $messageWasDelivered);
  return array($ListenerIsRunning, $ListenerStatus); }
// / -----------------------------------------------------------------------------------


