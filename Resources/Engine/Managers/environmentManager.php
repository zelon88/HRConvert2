<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRProprietary Engine, Copyright on 9/7/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / File Information ...
// / v4.0.8.
// / This file is the Environment Manager. It watches the host & repairs it only when told.
// /
// / It is not a manager in the sense the other four are.
// / The listener does not dispatch it, getAcceptedManagers() does not list it, & it opens no socket.
// / It refuses to run as anything but root, so the listener, which is the web server account, cannot start it.
// / getAcceptedRootManagers() pins it & loadEngineRootManager() loads it, for a root command.
// / A systemd timer starts it, or an administrator runs it by hand.
// / It accepts no requests, because a root process that took requests could be asked to repair a policy into something else.
// / It reads the environment itself & writes what it found where the web server account may read it & only root may write it.
// / It watches by default & repairs only when an administrator allows it, because silent writes to /etc on a timer are a liability.
// / See Documentation/ABOUT_ENVIRONMENT_MANAGER.txt for the reasoning behind all of it.
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
// / A function to run one Environment Manager pass & exit.
// / Accepts nothing. Returns whether the pass completed & the findings it recorded.
// /
// / It does not loop. It wakes, looks, writes & returns. A resident root process is a
// / resident root process no matter how careful its loop is, & a timer gives the same
// / result with nothing left running between passes.
// /
// / It refuses rather than degrades when it is not root. A pass that ran as the web server
// / account would report every policy as unreadable & log a page of failures that mean
// / nothing, which is worse than not running.
// /
// / One pass runs at a time, because two repairs at once would each correct what the other was correcting.
// / The lock is a file in Engine, root's own folder, opened through the kernel & held for the whole pass.
// / A pass that finds it held checks nothing, changes nothing & says so.
function runEnvironmentManager() {
  // / Set variables.
  global $RunningAsRoot, $CurrentUser, $EnvironmentManagerMayRepair, $EngineRepairIsUnattended, $EngineDir, $Verbose, $EnableMemoryProtection;
  $PassCompleted = FALSE;
  $PassFindings = array();
  $environmentIsReady = $repairIsPermitted = $repairSucceeded = $stateWasRead = $stateWasWritten = $repairIsDueToday = $lockIsHeld = $lockWasCreated = $logsWereExamined = FALSE;
  $findingEntry = $stateData = $previousState = array();
  $problemCount = $repairCount = $logsRemoved = 0;
  $todayStamp = $lastRepairDay = $stateStatus = $lockPath = $lockNotice = '';
  $lockHandle = FALSE;
  // / Root or nothing. This is the first thing it does & there is no fallback.
  if (!$RunningAsRoot) errorEntry('The Environment Manager was started as '.$CurrentUser.' & requires root. Nothing was checked.', 36000, FALSE);
  // / The lock lives in root's own folder, & a pass with nowhere to hold one refuses rather than
  // / risk running beside another. The folder is created by -fp.
  else if (!isset($EngineDir) or !is_string($EngineDir) or $EngineDir === '' or !is_dir($EngineDir)) warningEntry('The Environment Manager needs root\'s folder Engine in the data location & it does not exist yet. Run -fp once to create it. Nothing was checked.');
  else {
    $lockPath = rtrim($EngineDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'environment.lock';
    list ($lockHandle, $lockWasCreated, $lockNotice) = openFileWithoutFollowing($lockPath, 0600);
    if (is_resource($lockHandle)) $lockIsHeld = @flock($lockHandle, LOCK_EX | LOCK_NB);
    if (!is_resource($lockHandle)) warningEntry('The Environment Manager could not open its lock. '.$lockNotice.' Nothing was checked.');
    else if (!$lockIsHeld) warningEntry('Another Environment Manager pass is running. This one checked nothing & changed nothing.');
    else {
      $repairIsPermitted = (isset($EnvironmentManagerMayRepair) && $EnvironmentManagerMayRepair === TRUE);
      if ($Verbose) logEntry('Environment Manager pass beginning. Repair is '.($repairIsPermitted ? 'PERMITTED' : 'not permitted').'.');
      // / The same findings every other component reads. There is one definition of a healthy
      // / installation & this is not a second one. A finding is a problem by the same test the
      // / printed report uses, so the two cannot disagree about what needs doing.
      list ($environmentIsReady, $PassFindings) = validateOperatingEnvironment();
      foreach ($PassFindings as $findingEntry) {
        if (!isset($findingEntry['Status']) or findingIsBenign($findingEntry['Status'])) continue;
        $problemCount++;
        // / A drift is a warning every pass, not a log entry. An installation drifting is not
        // / normal activity & an operator scanning for warnings should find it.
        warningEntry('Environment Manager found '.(isset($findingEntry['Check']) ? $findingEntry['Check'] : 'an unnamed check').' reporting '.(string)$findingEntry['Status'].'. '.(isset($findingEntry['Detail']) ? $findingEntry['Detail'] : '')); }
      // / It repairs once a day & looks every hour.
      // / A repair walks the data tree, rewrites configuration & touches services, & a drift that survived one repair survives the next.
      // / So the first pass of a day that finds a problem repairs it, & every later pass that day only reports it.
      // / The next day it tries once more, & a state it cannot read counts as no repair today.
      $todayStamp = date('Y-m-d');
      list ($stateWasRead, $previousState, $stateStatus) = readEnvironmentState();
      if ($stateStatus === 'unreadable' or $stateStatus === 'corrupt') warningEntry('The Environment Manager could not read what its last pass recorded, so it cannot tell whether it has already repaired today. The state file is '.$stateStatus.' & is rewritten by this pass.');
      $lastRepairDay = ($stateWasRead && isset($previousState['LastRepairDay'])) ? (string)$previousState['LastRepairDay'] : '';
      $repairIsDueToday = ($lastRepairDay !== $todayStamp);
      if ($problemCount > 0 && $repairIsPermitted && $repairIsDueToday) {
        warningEntry('Environment Manager is repairing '.$problemCount.' finding(s). This is the one repair permitted today.');
        // / Repair is the application's own, reached through the provider it declares exactly as
        // / -fp reaches it, & never a second implementation of it.
        // / IT IS UNATTENDED. $EngineRepairIsUnattended tells the application's repair that nobody
        // / is watching, so it starts & enables no service & rewrites the host's configuration
        // / only when the application's own setting allows. It is set for the repair alone.
        $EngineRepairIsUnattended = TRUE;
        list ($repairSucceeded, $repairCount) = runApplicationRepair();
        $EngineRepairIsUnattended = FALSE;
        if (!$repairSucceeded) warningEntry('Environment Manager attempted a repair & it reported failure.');
        warningEntry('Environment Manager repaired once. It corrected '.$repairCount.' managed location(s). Nothing further will be repaired until tomorrow.');
        $lastRepairDay = $todayStamp; }
      else if ($problemCount > 0 && $repairIsPermitted) warningEntry('Environment Manager found '.$problemCount.' problem(s) & has already repaired once today. It will try again tomorrow. Something is undoing this repair & an operator should look.');
      else if ($problemCount > 0) warningEntry('Environment Manager found '.$problemCount.' problem(s) & repair is not permitted. Run --fix-permissions as root, or enable repair in config.php.');
      else if ($Verbose) logEntry('Environment Manager found nothing to report.');
      // / One state file, written by this & read by anything the web server account runs. It
      // / answers the question an operator actually asks, which is whether anything has been
      // / quietly wrong since the last time somebody looked.
      // / A state that could not be written has already said why & the pass itself completed,
      // / so the check is consumed & not repeated.
      $stateData = array(
        'LastPassUnix' => time(),
        'LastPassHuman' => date('F j, Y, g:i a'),
        'EnvironmentIsReady' => $environmentIsReady,
        'ProblemCount' => $problemCount,
        'RepairIsPermitted' => $repairIsPermitted,
        'RepairCount' => $repairCount,
        'RepairSucceeded' => $repairSucceeded,
        'LastRepairDay' => $lastRepairDay,
        'Findings' => $PassFindings);
      $stateWasWritten = writeEnvironmentState($stateData);
      // / Root's own logfiles older than thirty days go, every pass, watching or repairing. Root's
      // / log folder is no other account's to clean, so nothing else ever would. Removing root's
      // / own files from root's own folder changes nothing about the host. Thirty days is fixed
      // / rather than configured, & pruneRootLogs has already said anything that went wrong.
      list ($logsWereExamined, $logsRemoved) = pruneRootLogs(30);
      if ($logsRemoved > 0) logEntry('Environment Manager removed '.$logsRemoved.' of root\'s logfile(s) older than thirty days.');
      $PassCompleted = TRUE; }
    // / The lock is released & closed whatever happened, so the next pass is never held by this one.
    if (is_resource($lockHandle)) {
      if ($lockIsHeld) @flock($lockHandle, LOCK_UN);
      @fclose($lockHandle); } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $environmentIsReady, $repairIsPermitted, $repairSucceeded, $stateWasRead, $stateWasWritten, $repairIsDueToday, $lockIsHeld, $lockWasCreated, $logsWereExamined, $findingEntry, $stateData, $previousState, $problemCount, $repairCount, $logsRemoved, $todayStamp, $lastRepairDay, $stateStatus, $lockPath, $lockNotice, $lockHandle);
  return array($PassCompleted, $PassFindings); }
// / -----------------------------------------------------------------------------------
