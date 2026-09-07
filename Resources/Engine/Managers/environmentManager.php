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
// / v3.9.2.
// / This file is the Environment Manager. It watches the host & repairs it only when told.
// /
// / It is not a manager in the sense the other four are, & that is the whole point.
// / It is not dispatched by the listener, it is not in getAcceptedManagers(), it opens no
// / socket & it appears in no socket directory. The listener could not start it if
// / something convinced the listener to try, because the listener is the web server
// / account & this refuses to run as anything but root.
// / A systemd timer starts it, or an administrator runs it by hand. Those are the only
// / two ways in.
// /
// / Information leaves it & nothing enters it.
// / A socket is a door, & a door on a root process is worth more to an attacker than
// / everything else in this application together. A worker that could ask a root process
// / to repair a policy could ask it to repair a policy into something else. So it accepts
// / no requests, reads the environment for itself, & writes what it found where anything
// / may read it & nothing may write it.
// /
// / It watches by default & repairs only when an administrator says so.
// / A root process that silently rewrites /etc on a timer is a liability even when every
// / write it makes is correct.
// /
// / See Documentation/ABOUT_ENVIRONMENT_MANAGER.txt for the reasoning behind all of it.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by an application.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-35000, A manager subcomponent cannot be loaded directly!');
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The version of this manager. Read by the Engine WITHOUT executing this file.
$ManagerVersion = 'v3.9.2';
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
function runEnvironmentManager() {
  // / Set variables.
  global $RunningAsRoot, $CurrentUser, $EnvironmentManagerMayRepair, $Verbose, $EnableMemoryProtection;
  $PassCompleted = FALSE;
  $PassFindings = array();
  $environmentIsReady = $repairIsPermitted = FALSE;
  $findingEntry = $stateData = array();
  $problemCount = $repairCount = 0;
  $repairSucceeded = $stateWasRead = $repairIsDueToday = FALSE;
  $todayStamp = $lastRepairDay = '';
  $previousState = array();
  $repairName = '';
  // / Root or nothing. This is the first thing it does & there is no fallback.
  if (!$RunningAsRoot) errorEntry('The Environment Manager was started as '.$CurrentUser.' & requires root. Nothing was checked.', 36000, FALSE);
  else {
  $repairIsPermitted = (isset($EnvironmentManagerMayRepair) && $EnvironmentManagerMayRepair === TRUE);
  if ($Verbose) logEntry('Environment Manager pass beginning. Repair is '.($repairIsPermitted ? 'PERMITTED' : 'not permitted').'.');
  // / The same findings every other component reads. There is one definition of a healthy
  // / installation & this is not a second one.
  list ($environmentIsReady, $PassFindings) = validateOperatingEnvironment();
  foreach ($PassFindings as $findingEntry) {
    if (!isset($findingEntry['Status'])) continue;
    if ((string)$findingEntry['Status'] === 'ok') continue;
    $problemCount++;
    // / A drift is a warning every pass, not a log entry. An installation drifting is not
    // / normal activity & an operator scanning for warnings should find it.
    warningEntry('Environment Manager found '.(isset($findingEntry['Check']) ? $findingEntry['Check'] : 'an unnamed check').' reporting '.(string)$findingEntry['Status'].'. '.(isset($findingEntry['Detail']) ? $findingEntry['Detail'] : '')); }
  // / Repair is the application's own repair & never a second implementation of it.
  // / fixManagedPermissions() is what -fp runs. Calling anything else here would mean two
  // / definitions of a correct installation that could disagree with each other.
  // /
  // / IT REPAIRS ONCE A DAY & LOOKS EVERY HOUR. That is the whole difference between this
  // / & a command an administrator typed.
  // / A repair walks the data tree, rewrites configuration & touches services. Doing that
  // / hourly on an appliance costs real work for nothing, because a drift that survived one
  // / repair will survive the next one an hour later. What it needs is an operator, & the
  // / warnings below are how one is fetched.
  // / So the first pass of a day that finds a problem repairs it. Every later pass that day
  // / reports the same problem & changes nothing. The next day it tries once more.
  // / An installation repairing every single day is telling you something a log will show &
  // / an hourly repair would hide.
  $todayStamp = date('Y-m-d');
  list ($stateWasRead, $previousState) = readManagerState('environment');
  $lastRepairDay = ($stateWasRead && isset($previousState['LastRepairDay'])) ? (string)$previousState['LastRepairDay'] : '';
  $repairIsDueToday = ($lastRepairDay !== $todayStamp);
  if ($problemCount > 0 && $repairIsPermitted && $repairIsDueToday) {
    $repairName = 'fixManagedPermissions';
    if (function_exists($repairName)) {
      warningEntry('Environment Manager is repairing '.$problemCount.' finding(s). This is the one repair permitted today.');
      // / It returns a success flag & a count, in that order. Casting the call to an int
      // / would have turned the whole array into 1 & reported one path corrected whatever
      // / actually happened.
      list ($repairSucceeded, $repairCount) = $repairName();
      if (!$repairSucceeded) warningEntry('Environment Manager attempted a repair & it reported failure.');
      warningEntry('Environment Manager repaired '.$repairCount.' path(s). Nothing further will be repaired until tomorrow.');
      $lastRepairDay = $todayStamp; }
    else warningEntry('Environment Manager was permitted to repair & '.$repairName.'() is not defined, so nothing was repaired.'); }
  else if ($problemCount > 0 && $repairIsPermitted) warningEntry('Environment Manager found '.$problemCount.' problem(s) & has already repaired once today. It will try again tomorrow. Something is undoing this repair & an operator should look.');
  else if ($problemCount > 0) warningEntry('Environment Manager found '.$problemCount.' problem(s) & repair is not permitted. Run --fix-permissions as root, or enable repair in config.php.');
  else if ($Verbose) logEntry('Environment Manager found nothing to report.');
  // / One state file, written by this & read by anything. It answers the question an
  // / operator actually asks, which is whether anything has been quietly wrong since the
  // / last time somebody looked.
  $stateData = array(
    'LastPassUnix' => time(),
    'LastPassHuman' => date('F j, Y, g:i a'),
    'EnvironmentIsReady' => $environmentIsReady,
    'ProblemCount' => $problemCount,
    'RepairIsPermitted' => $repairIsPermitted,
    'RepairCount' => $repairCount,
    'LastRepairDay' => $lastRepairDay,
    'Findings' => $PassFindings);
  if (!writeManagerState('environment', $stateData)) warningEntry('The Environment Manager could not record what it found. The pass itself completed.');
  $PassCompleted = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $environmentIsReady, $repairIsPermitted, $findingEntry, $stateData, $problemCount, $repairCount, $repairSucceeded, $repairName, $todayStamp, $stateWasRead, $previousState, $lastRepairDay, $repairIsDueToday);
  return array($PassCompleted, $PassFindings); }
// / -----------------------------------------------------------------------------------
