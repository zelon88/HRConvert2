<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRConvert2, Copyright on 9/8/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / File Information ...
// / v3.9.3.
// / The Files pipeline. Operations on a file that do not care what is inside it.
// /
// / EVERY PATH ARRIVES AS AN ARGUMENT. Nothing here reads a session global, which is what
// / makes it a component rather than a piece of one application.
// / The version in convertCore.php reads $ConvertDir & $ConvertTempDir directly, so it can
// / only ever serve HRConvert2. This one is handed the directory it is allowed to touch &
// / refuses everything outside it.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-2: This file cannot be loaded directly!');
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to list the files in one directory.
// / Accepts the directory. Returns whether it could be read & the plain file names found.
// /
// / NAMES ONLY, never paths. A caller that receives a path is a caller that can be handed
// / one from somewhere else, & every guard downstream then has to re-derive which directory
// / it was supposed to be in.
// /
// / DIRECTORIES ARE OMITTED & so are the dot entries. A file operation lists files. A
// / caller wanting to walk a tree can ask for each directory it chooses to descend into,
// / which keeps the decision to recurse with whoever is allowed to make it.
// /
// / IT FILTERS NOTHING ELSE. Which formats an application refuses is that application's
// / policy & it does not belong in a component that any application can drop in.
function listFilesInDirectory($permittedDirectory) {
  // / Set variables.
  global $EnableMemoryProtection;
  $DirectoryWasRead = FALSE;
  $FileNames = array();
  $resolvedDirectory = $candidateName = '';
  $directoryEntries = array();
  $resolvedDirectory = (string)@realpath((string)$permittedDirectory);
  if ($resolvedDirectory === '' or !is_dir($resolvedDirectory)) warningEntry('A file listing was requested for a directory that does not resolve.');
  else {
    $directoryEntries = @scandir($resolvedDirectory);
    if (!is_array($directoryEntries)) warningEntry('A directory resolved & could not be read. The permissions may not allow it.');
    else {
      $DirectoryWasRead = TRUE;
      foreach ($directoryEntries as $candidateName) {
        if ($candidateName === '.' or $candidateName === '..') continue;
        if (is_dir($resolvedDirectory.DIRECTORY_SEPARATOR.$candidateName)) continue;
        $FileNames[] = $candidateName; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $resolvedDirectory, $candidateName, $directoryEntries, $permittedDirectory);
  return array($DirectoryWasRead, $FileNames); }
// / -----------------------------------------------------------------------------------
// / A function to place a file into one directory.
// / Accepts the directory that may be written, the source path, the name to give it & the
// / permissions to set. Returns whether it landed & the name it was given.
// /
// / THE NAME IS SANITIZED HERE & THE DESTINATION IS RESOLVED, exactly as a removal is.
// / A placement is a removal in reverse & is the more dangerous of the two, because a
// / caller controls the name AND the content. A name that traverses would write into a
// / directory nobody permitted rather than merely reading one.
// /
// / THE SOURCE IS NOT SANITIZED & is not meant to be. It is an uploaded temporary file or
// / a converted output whose path this application already knows. Sanitizing it would
// / mangle a legitimate path.
// /
// / An existing file of the same name is replaced, because a caller placing a name has
// / decided that name should hold this content.
function placeFileInDirectory($permittedDirectory, $sourcePath, $destinationName, $permissionLevel) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FileWasPlaced = FALSE;
  $PlacedName = '';
  $resolvedDirectory = $destinationPath = $resolvedDestination = '';
  $nameIsSanitized = FALSE;
  $resolvedDirectory = (string)@realpath((string)$permittedDirectory);
  if ($resolvedDirectory === '' or !is_dir($resolvedDirectory)) warningEntry('A file placement was requested in a directory that does not resolve.');
  else if (!is_file((string)$sourcePath) or !is_readable((string)$sourcePath)) warningEntry('A file placement named a source that is not a readable file.');
  else {
    list ($destinationName, $nameIsSanitized) = sanitize($destinationName, TRUE);
    if (!$nameIsSanitized or !is_string($destinationName) or $destinationName === '' or $destinationName === '.' or $destinationName === '..') warningEntry('A file placement named something that could not be sanitized. It was refused.');
    else {
      $destinationPath = $resolvedDirectory.DIRECTORY_SEPARATOR.$destinationName;
      // / The PARENT is resolved rather than the destination, because the destination does
      // / not exist yet & realpath on a path that is not there returns nothing.
      $resolvedDestination = (string)@realpath(dirname($destinationPath));
      if ($resolvedDestination !== $resolvedDirectory) warningEntry('A file placement resolved outside the directory it was permitted. It was refused.');
      else {
        if (file_exists($destinationPath)) @unlink($destinationPath);
        if (@copy((string)$sourcePath, $destinationPath)) {
          @chmod($destinationPath, $permissionLevel);
          $FileWasPlaced = TRUE;
          $PlacedName = $destinationName; }
        else warningEntry('A file could not be placed. The permissions or the disk may not allow it.'); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $resolvedDirectory, $destinationPath, $resolvedDestination, $nameIsSanitized, $permittedDirectory, $sourcePath, $destinationName, $permissionLevel);
  return array($FileWasPlaced, $PlacedName); }
// / -----------------------------------------------------------------------------------


// / A function to remove files from one directory.
// / Accepts the directory that may be touched & the names to remove. Returns whether every
// / removal succeeded, how many were attempted & how many went.
// /
// / THE DIRECTORY IS AN ARGUMENT & THAT IS THE WHOLE POINT. A caller says which directory
// / this may operate in, & a name that resolves outside it is refused rather than followed.
// / A pipeline that read a session global could serve exactly one application.
// /
// / Every name is sanitized & then resolved, & the resolved path is compared against the
// / resolved directory. Sanitizing alone is not enough, because a name that survives
// / sanitizing can still traverse if the directory itself is a symlink.
function removeFilesFromDirectory($permittedDirectory, $namesToRemove) {
  // / Set variables.
  global $EnableMemoryProtection;
  $RemovalComplete = TRUE;
  $NamesAttempted = $NamesRemoved = 0;
  $resolvedDirectory = $candidateName = $candidatePath = $resolvedPath = '';
  $nameIsSanitized = FALSE;
  $resolvedDirectory = (string)@realpath((string)$permittedDirectory);
  if ($resolvedDirectory === '' or !is_dir($resolvedDirectory)) {
    warningEntry('A file removal was requested in a directory that does not resolve. Nothing was removed.');
    $RemovalComplete = FALSE; }
  else {
    if (!is_array($namesToRemove)) $namesToRemove = array($namesToRemove);
    foreach ($namesToRemove as $candidateName) {
      $NamesAttempted++;
      list ($candidateName, $nameIsSanitized) = sanitize($candidateName, TRUE);
      // / A name that changed under sanitizing is a name somebody built rather than picked.
      if (!$nameIsSanitized or !is_string($candidateName) or $candidateName === '' or $candidateName === '.' or $candidateName === '..') {
        warningEntry('A file removal named something that could not be sanitized. It was refused.');
        $RemovalComplete = FALSE;
        continue; }
      $candidatePath = $resolvedDirectory.DIRECTORY_SEPARATOR.$candidateName;
      $resolvedPath = (string)@realpath($candidatePath);
      // / RESOLVED, & COMPARED AGAINST THE RESOLVED DIRECTORY. A path that does not sit
      // / inside what the caller permitted is refused whatever it looks like.
      if ($resolvedPath === '' or strpos($resolvedPath, $resolvedDirectory.DIRECTORY_SEPARATOR) !== 0) {
        warningEntry('A file removal resolved outside the directory it was permitted. It was refused.');
        $RemovalComplete = FALSE;
        continue; }
      if (!is_file($resolvedPath)) {
        warningEntry('A file removal named something that is not a file. It was refused.');
        $RemovalComplete = FALSE;
        continue; }
      if (@unlink($resolvedPath)) $NamesRemoved++;
      else {
        warningEntry('A file could not be removed. The permissions or the mount may not allow it.');
        $RemovalComplete = FALSE; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $resolvedDirectory, $candidateName, $candidatePath, $resolvedPath, $nameIsSanitized, $permittedDirectory, $namesToRemove);
  return array($RemovalComplete, $NamesAttempted, $NamesRemoved); }
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The entry point the Pipeline Core calls.
// / Accepts the operation, the directory that may be touched & the names it applies to.
// / Returns whether it completed & a short description of what happened.
// /
// / One entry point & an operation name, rather than one entry point per operation. A
// / pipeline declares a single entry point, & a file pipeline that could only delete would
// / need a second pipeline to list.
function operateOnFiles($requestedOperation, $permittedDirectory, $namesToOperateOn) {
  // / Set variables.
  global $EnableMemoryProtection;
  $OperationCompleted = FALSE;
  $OperationSummary = '';
  $namesAttempted = $namesRemoved = 0;
  $listedNames = array();
  $placedName = '';
  if ($requestedOperation === 'list') {
    list ($OperationCompleted, $listedNames) = listFilesInDirectory($permittedDirectory);
    $OperationSummary = 'Listed '.count($listedNames).' file(s).'; }
  else if ($requestedOperation === 'place') {
    // / place takes a source & a name where the others take a list, so the caller passes
    // / them as the two entries of $namesToOperateOn rather than through a wider signature
    // / that every other operation would have to ignore.
    list ($OperationCompleted, $placedName) = placeFileInDirectory($permittedDirectory, isset($namesToOperateOn[0]) ? $namesToOperateOn[0] : '', isset($namesToOperateOn[1]) ? $namesToOperateOn[1] : '', 0644);
    $OperationSummary = $OperationCompleted ? 'Placed '.$placedName.'.' : 'Nothing was placed.'; }
  else if ($requestedOperation === 'remove') {
    list ($OperationCompleted, $namesAttempted, $namesRemoved) = removeFilesFromDirectory($permittedDirectory, $namesToOperateOn);
    $OperationSummary = 'Removed '.$namesRemoved.' of '.$namesAttempted.' file(s).'; }
  else {
    warningEntry('The Files pipeline was asked for an operation it does not have: '.(string)$requestedOperation.'.');
    $OperationSummary = 'This pipeline does not have that operation.'; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $namesAttempted, $namesRemoved, $listedNames, $placedName, $requestedOperation, $permittedDirectory, $namesToOperateOn);
  return array($OperationCompleted, $OperationSummary); }
// / -----------------------------------------------------------------------------------
