<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRConvert2, Copyright on 9/22/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / Application Information ...
// / This application is designed to provide a web-interface for converting file formats on
// / a server for users of any web browser without authentication.
// /
// / File Information ...
// / v4.1.6.
// / The Archive pipeline's converter. pipelineCore.php loads it only to dispatch an Archive conversion.
// / Error block 13000, 13006 through 13011, 13013, 13014, 13105 through 13108 belongs to this pipeline.
// / The Archiver pipeline raises 13012, & shares 13006, 13008, 13009 & 13010 with this one.
// / verifyIsoBootloaders() & generateBootableIsoCommand() live here, because only this converter calls them.
// / Its tools come from verifiedToolPath(), as the Archiver pipeline's do & as the core's showVersionInfo() asks for them.
// / This is the only pipeline that renames its own output.
// / An extraction is named differently from what was asked for, which the fifth value of the contract carries.
// / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt for the contracts this file obeys.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline converter cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to cryptographically validate the integrity of the bundled boot assets.
// / Accepts the requested bootable extension & the staging folder the image is built from.
// / Returns TRUE when every asset this image needs is present & matches its pinned hash.
// / Returns TRUE without checking when the user supplied their own boot files, which are not ours to pin.
// / Returns FALSE when a bundled asset is missing or corrupt, or the extension is not one this validates.
// / Every bootable format is validated, the legacy MBR image included.
// / A hybrid image carries an isolinux record & an EFI record, so both assets must pass.
// / ldlinux.c32 is not pinned, because it is optional & the builder handles its absence.
// / A hash of all zeroes means the asset is not pinned yet, & the image is refused.
// / An unpinned bootloader is worse than none.
function verifyIsoBootloaders($extension, $safedir2) {
  // / Set variables.
  global $DirSep, $BootloadersDir, $EnableMemoryProtection;
  $BootloadersOK = FALSE;
  $userPathIsPresent = $allAssetsValid = FALSE;
  $userEfiPath = $bundledPath = $computedHash = $requiredAsset = '';
  $assetMap = $requiredAssets = array();
  // / Every bundled asset & the hash it must carry.
  // / isolinux.bin lives in its own subdirectory alongside the extra folder it ships with.
  $assetMap = array(
    'isolinux'   => array('asset' => 'isolinux'.$DirSep.'isolinux.bin', 'hash' => 'f0f645e52bbe18bf7a4ac07be9afa985e21d8eb8c9c57938bdaf5b868a0e0e7f'),
    'blank_efi'  => array('asset' => 'blank_efi_2880.img',              'hash' => 'fd2b9e5cd6323786193088344e4d02754431c5fdcda6e396145e42686d1f10a2'),
    'uefi_ia32'  => array('asset' => 'uefi_ia32.img',                   'hash' => 'bc6e4399eab62623c7b200f2422618d3ff3ec32f613bffbcec4ebfb07a8540cc'),
    'uefi_x64'   => array('asset' => 'uefi_x64.img',                    'hash' => 'b74ab9b632ca5d33694befc2646eaba202c0a5a8b268784bad20f1e8e1952cd2'),
    'uefi_arm'   => array('asset' => 'uefi_arm.img',                    'hash' => 'f2b5fcfac8f1bff9f58228fc449c4d795ed8c213e02346620873acc8a21abd94'),
    'uefi_arm64' => array('asset' => 'uefi_arm64.img',                  'hash' => '925ebcf62a396f6c737269fe1c5f74d03e528a5ff7ecedb1be51699a5f71581c'));
  // / Which assets each bootable format actually requires.
  // / A hybrid image needs both halves. Everything else needs exactly one.
  if ($extension === 'iso_mbr-boot') $requiredAssets = array('isolinux');
  else if ($extension === 'iso_gpt-boot') $requiredAssets = array('isolinux', 'blank_efi');
  else if ($extension === 'iso_gpt-boot-x86') $requiredAssets = array('uefi_ia32');
  else if ($extension === 'iso_gpt-boot-x86-64') $requiredAssets = array('uefi_x64');
  else if ($extension === 'iso_gpt-boot-arm32') $requiredAssets = array('uefi_arm');
  else if ($extension === 'iso_gpt-boot-arm64') $requiredAssets = array('uefi_arm64');
  // / An extension this function does not recognize is refused rather than assumed safe.
  if (empty($requiredAssets)) $BootloadersOK = FALSE;
  else {
    // / A user who supplied their own boot files is using theirs rather than ours, so there
    // / is nothing of ours to validate. The builder makes the same decision from the same
    // / path, so both functions agree on which set is in play.
    $userEfiPath = $safedir2.$DirSep.'boot'.$DirSep.'grub'.$DirSep.'efi.img';
    if ($extension !== 'iso_mbr-boot' && file_exists($userEfiPath)) $userPathIsPresent = TRUE;
    if ($extension === 'iso_mbr-boot' && file_exists($safedir2.$DirSep.'isolinux'.$DirSep.'isolinux.bin')) $userPathIsPresent = TRUE;
    if ($userPathIsPresent) $BootloadersOK = TRUE;
    else {
      // / Validate every bundled asset this format needs. All of them must pass.
      $allAssetsValid = TRUE;
      foreach ($requiredAssets as $requiredAsset) {
        $bundledPath = $BootloadersDir.$DirSep.$assetMap[$requiredAsset]['asset'];
        if (!file_exists($bundledPath)) {
          $allAssetsValid = FALSE;
          continue; }
        $computedHash = hash_file('sha256', $bundledPath);
        // / An empty or unpinned hash fails. An asset nobody has pinned is not validated.
        if (empty($assetMap[$requiredAsset]['hash'])) $allAssetsValid = FALSE;
        else if ($computedHash !== $assetMap[$requiredAsset]['hash']) $allAssetsValid = FALSE; }
      $BootloadersOK = $allAssetsValid; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $extension, $safedir2, $userEfiPath, $bundledPath, $computedHash, $requiredAsset, $assetMap, $requiredAssets, $userPathIsPresent, $allAssetsValid);
  return $BootloadersOK; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to generate a bootable ISO image configuration command.
// / Accepts the bootable extension, the output path, the staging folder & the two binaries it may need.
// / Returns the mkisofs command, the normalized extension, the corrected output path & an isohybrid command.
// / The isohybrid command is empty for every image that does not need one.
// / Returns FALSE as the first value when no usable boot configuration could be assembled.
// / The two commands must not be joined with &&, because bwrap execs one command without a shell.
// / The outer shell would run isohybrid outside the namespace, so the caller sandboxes each command alone.
// / The required boot files are injected into the staging folder, & boot files the user supplied are kept.
// / The intent suffix is stripped from the returned path, so everything downstream sees an ordinary iso.
// / That is a different name from the one verifyFile() found free, so a taken one is given the next free name too.
function generateBootableIsoCommand($extension, $newPathname, $safedir2, $mkisofsBinary, $isoHybridBinary) {
  // / Set variables.
  global $DirSep, $BootloadersDir, $EnableMemoryProtection;
  $bootData = $isoCanBeBuilt = $isoNameWasFound = FALSE;
  $CleanNewPathname = $HybridCommand = $baseFlags = $bootFlags = $command = $userBin = $targetIsoLinuxDir = '';
  $bundledBin = $bundledC32 = $isoRelativeEfi = $bundledImg = $targetEfiName = $targetEfiPath = '';
  $normalizedPathname = $freeIsoPathname = $freeIsoName = '';
  $selectedGpt = $gptMap = array();
  // / Basic validation checks to ensure filesystems and binaries exist.
  $isoCanBeBuilt = ($mkisofsBinary !== FALSE && file_exists($mkisofsBinary) && is_dir($safedir2) && !empty($BootloadersDir) && is_dir($BootloadersDir));
  if ($isoCanBeBuilt) {
    // / Normalize the target filename right away to end in a standard .iso extension.
    $normalizedPathname = str_replace($extension, 'iso', $newPathname);
    clearstatcache(TRUE, $normalizedPathname);
    if (file_exists($normalizedPathname) or is_link($normalizedPathname)) {
      list ($isoNameWasFound, $freeIsoPathname, $freeIsoName) = findFreeOutputName(dirname($normalizedPathname).$DirSep, pathinfo($normalizedPathname, PATHINFO_FILENAME), 'iso');
      if (!$isoNameWasFound) {
        $isoCanBeBuilt = FALSE;
        errorEntry('No free name was found for the bootable image '.basename($normalizedPathname).', so it was not written!', 13108, FALSE); } }
    // / The normalized name & a free one are held under names of their own, so the path handed back is set once.
    // / With no free name it is the one asked for, which holds nothing, so the image already there is never taken for this one.
    if ($isoNameWasFound) $CleanNewPathname = $freeIsoPathname;
    else if ($isoCanBeBuilt) $CleanNewPathname = $normalizedPathname;
    else $CleanNewPathname = $newPathname; }
  if ($isoCanBeBuilt) {
    // / Base compatibility flags for modern cross platform systems.
    $baseFlags = '-R -J -joliet-long';
    // / Handle the Legacy MBR Bootstrap implementation.
    if ($extension === 'iso_mbr-boot') {
      $userBin = 'isolinux'.$DirSep.'isolinux.bin';
      if (file_exists($safedir2.$DirSep.$userBin)) {
        $bootFlags = '-b '.escapeshellarg('isolinux/isolinux.bin').' -c '.escapeshellarg('isolinux/boot.cat').' -no-emul-boot -boot-load-size 4 -boot-info-table'; }
      else {
        $targetIsoLinuxDir = $safedir2.$DirSep.'isolinux';
        if (!is_dir($targetIsoLinuxDir)) @mkdir($targetIsoLinuxDir, 0755, TRUE);
        $bundledBin = $BootloadersDir.$DirSep.'isolinux'.$DirSep.'isolinux.bin';
        $bundledC32 = $BootloadersDir.$DirSep.'isolinux'.$DirSep.'extra'.$DirSep.'ldlinux.c32';
        if (file_exists($bundledBin)) {
          @copy($bundledBin, $targetIsoLinuxDir.$DirSep.'isolinux.bin');
          if (file_exists($bundledC32)) @copy($bundledC32, $targetIsoLinuxDir.$DirSep.'ldlinux.c32');
          $bootFlags = '-b '.escapeshellarg('isolinux/isolinux.bin').' -c '.escapeshellarg('isolinux/boot.cat').' -no-emul-boot -boot-load-size 4 -boot-info-table'; } } }
    // / Handle the Universal Multi-Boot Hybrid ISO implementation.
    // / The bundled assets live in the isolinux subdirectory, as the MBR branch expects them.
    else if ($extension === 'iso_gpt-boot') {
      $targetIsoLinuxDir = $safedir2.$DirSep.'isolinux';
      if (!is_dir($targetIsoLinuxDir)) @mkdir($targetIsoLinuxDir, 0755, TRUE);
      $bundledBin = $BootloadersDir.$DirSep.'isolinux'.$DirSep.'isolinux.bin';
      $bundledC32 = $BootloadersDir.$DirSep.'isolinux'.$DirSep.'extra'.$DirSep.'ldlinux.c32';
      $bundledImg = $BootloadersDir.$DirSep.'blank_efi_2880.img';
      $targetEfiName = 'efi.img';
      $targetEfiPath = $safedir2.$DirSep.$targetEfiName;
      if (file_exists($bundledBin) && file_exists($bundledImg)) {
        @copy($bundledBin, $targetIsoLinuxDir.$DirSep.'isolinux.bin');
        if (file_exists($bundledC32)) @copy($bundledC32, $targetIsoLinuxDir.$DirSep.'ldlinux.c32');
        @copy($bundledImg, $targetEfiPath);
        // / Map BOTH el torito records simultaneously so the image presents a valid hybrid topology.
        $bootFlags = '-b '.escapeshellarg('isolinux/isolinux.bin').' -c '.escapeshellarg('isolinux/boot.cat').' -no-emul-boot -boot-load-size 4 -boot-info-table -eltorito-alt-boot -e '.escapeshellarg($targetEfiName).' -no-emul-boot'; } }
    // / Handle the Explicit Target Architecture UEFI Bootstrap implementations.
    else {
      $gptMap = array(
        'iso_gpt-boot-x86'    => array('asset' => 'uefi_ia32.img',  'user_path' => 'boot'.$DirSep.'grub'.$DirSep.'efi.img'),
        'iso_gpt-boot-x86-64' => array('asset' => 'uefi_x64.img',   'user_path' => 'boot'.$DirSep.'grub'.$DirSep.'efi.img'),
        'iso_gpt-boot-arm32'  => array('asset' => 'uefi_arm.img',   'user_path' => 'boot'.$DirSep.'grub'.$DirSep.'efi.img'),
        'iso_gpt-boot-arm64'  => array('asset' => 'uefi_arm64.img', 'user_path' => 'boot'.$DirSep.'grub'.$DirSep.'efi.img') );
      if (isset($gptMap[$extension])) {
        $selectedGpt = $gptMap[$extension];
        if (file_exists($safedir2.$DirSep.$selectedGpt['user_path'])) {
          $isoRelativeEfi = str_replace($DirSep, '/', $selectedGpt['user_path']);
          $bootFlags = '-eltorito-alt-boot -e '.escapeshellarg($isoRelativeEfi).' -no-emul-boot'; }
        else {
          $bundledImg = $BootloadersDir.$DirSep.$selectedGpt['asset'];
          $targetEfiName = 'efi.img';
          $targetEfiPath = $safedir2.$DirSep.$targetEfiName;
          if (file_exists($bundledImg)) {
            @copy($bundledImg, $targetEfiPath);
            $bootFlags = '-eltorito-alt-boot -e '.escapeshellarg($targetEfiName).' -no-emul-boot'; } } } }
    // / Build the resulting instruction string only when valid parameters were injected.
    if ($bootFlags !== '') {
      $command = escapeshellarg($mkisofsBinary).' '.$baseFlags.' '.$bootFlags.' -o '.escapeshellarg($CleanNewPathname).' '.escapeshellarg($safedir2);
      $bootData = $command;
      // / Only the generic hybrid image is post processed.
      // / It carries both an isolinux record & an EFI record, & isohybrid writes the MBR that points at the first.
      // / An architecture specific UEFI image has no isolinux record, so it never passes through isohybrid.
      // / This is tested before the extension is normalized below, which leaves every extension as iso.
      if ($extension === 'iso_gpt-boot' && $isoHybridBinary !== FALSE) $HybridCommand = escapeshellarg($isoHybridBinary).' --uefi '.escapeshellarg($CleanNewPathname); } }
  // / Reset the extension to iso without the bootable architecture flag so the rest of the
  // / core will use the correct filename.
  $extension = 'iso';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $newPathname, $safedir2, $mkisofsBinary, $isoHybridBinary, $baseFlags, $bootFlags, $command, $userBin, $targetIsoLinuxDir, $bundledBin, $bundledC32, $gptMap, $selectedGpt, $isoRelativeEfi, $bundledImg, $targetEfiName, $targetEfiPath, $isoCanBeBuilt, $isoNameWasFound, $normalizedPathname, $freeIsoPathname, $freeIsoName);
  return array($bootData, $extension, $CleanNewPathname, $HybridCommand); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to remove every link in an extracted archive that does not resolve inside it.
// / Accepts the folder to walk & the approved root a link must resolve inside.
// / Returns whether the walk finished & how many links it removed.
// / A link to a file inside the folder is kept, & every other link is removed.
// / An archiver follows a link reaching outside, so /etc/passwd would be copied into the output.
// / A link resolving nowhere survives cleanup & leaves a directory that cannot be removed.
// / Every archiver here follows a link to a directory, which can cycle or repeat files until Zip gives up.
// / The walk tests for a link before descending, so it never follows one.
// / realpath resolves a whole chain, & a chain it cannot resolve returns FALSE & is removed.
function pruneEscapingLinks($stagingPath, $approvedRoot) {
  // / Set variables.
  global $DirSep, $EnableMemoryProtection;
  $WalkCompleted = TRUE;
  $LinksRemoved = 0;
  $defaultApps = array('.', '..');
  $directoryContents = array();
  $directoryEntry = $childPath = '';
  $resolvedTarget = $resolvedRoot = FALSE;
  $childIsContained = FALSE;
  $childWalkCompleted = TRUE;
  $childLinksRemoved = 0;
  $resolvedRoot = realpath($approvedRoot);
  // / A root that will not resolve is not a root this walk may act under.
  if ($resolvedRoot === FALSE or !is_dir($stagingPath)) $WalkCompleted = FALSE;
  else {
    $directoryContents = array_diff(scandir($stagingPath), $defaultApps);
    foreach ($directoryContents as $directoryEntry) {
      $childPath = $stagingPath.$DirSep.$directoryEntry;
      // / A link is judged by where it resolves & is never followed by this walk.
      if (is_link($childPath)) {
        $resolvedTarget = realpath($childPath);
        $childIsContained = ($resolvedTarget !== FALSE && ($resolvedTarget === $resolvedRoot or strpos($resolvedTarget, $resolvedRoot.$DirSep) === 0) && is_file($resolvedTarget));
        if (!$childIsContained) {
          logEntry('Removing archive member link '.$directoryEntry.' because it does not resolve to a file inside the extracted folder.');
          if (@unlink($childPath)) $LinksRemoved = $LinksRemoved + 1;
          else $WalkCompleted = FALSE; } }
      // / A real directory is descended into. A link to a directory was handled above.
      else if (is_dir($childPath)) {
        list ($childWalkCompleted, $childLinksRemoved) = pruneEscapingLinks($childPath, $approvedRoot);
        if (!$childWalkCompleted) $WalkCompleted = FALSE;
        $LinksRemoved = $LinksRemoved + $childLinksRemoved; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $stagingPath, $approvedRoot, $defaultApps, $directoryContents, $directoryEntry, $childPath, $resolvedTarget, $resolvedRoot, $childIsContained, $childWalkCompleted, $childLinksRemoved);
  return array($WalkCompleted, $LinksRemoved); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert archive & disk image formats.
// / The source is extracted into a staging folder & the staging folder is re-archived.
// / 7-Zip is the only extractor, so every input format depends on it.
// / Each output format gates on its own creator alone, so a missing mkisofs does not stop a zip conversion.
// / An extraction that produces nothing is refused, because an empty folder would re-archive & pass.
// / A bootable ISO carries its architecture in the extension, such as iso_gpt-boot-x86-64.
// / generateBootableIsoCommand() normalizes it to iso & corrects the output path.
// / A generic hybrid image takes two sandboxed steps.
// / Mkisofs writes the image & isohybrid then rewrites its MBR in place.
function convertArchives($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $ConvertDir, $DirSep, $Lol, $Lolol, $StopCounter, $SleepTimer, $PermissionLevels, $AllowBootableIsoImage, $BootableIsoArray, $EnableMemoryProtection;
  // / The six value pipeline contract. $UserFilename is this converter's fifth value,
  // / because an archive extraction renames what the user is given.
  $WorkerPID = 0;
  $ConversionSuccess = $ConversionErrors = $commandMayRun = FALSE;
  $archiveToolsAreValid = $bootloadersAreValid = FALSE;
  $sevenZipBinary = $rarBinary = $zipBinary = $tarBinary = $mkisofsBinary = $isoHybridBinary = FALSE;
  $returnData = $extractCommand = $extractTarget = $archiveCommand = $hybridCommand = $UserFilename = '';
  $stopper = 0;
  $linkWalkCompleted = TRUE;
  $linksRemoved = 0;
  $sleepTime = $SleepTimer;
  $oldExtension = getExtension($pathname);
  $archiveError = 13000;
  $filename = pathinfo($pathname, PATHINFO_FILENAME);
  $safedir2 = $ConvertDir.$filename;
  $array7zo = array('7z', 'cbz', 'cbr');
  $arrayzipo = array('zip');
  $array7zo2 = array('vhd', 'vdi', 'iso');
  $arraytaro = array('tar.gz', 'tar.bz2', 'tar');
  $arrayraro = array('rar');
  $array7zo3 = array();
  // / Only populate the list of supported bootable iso formats if config.php enables it.
  if ($AllowBootableIsoImage) $array7zo3 = $BootableIsoArray;
  // / Verify every archive utility before anything is read or written.
  // / Each tool by its manifest name. RAR is fetched & is deliberately NOT part of validity:
  // / it is optional, & an installation without it still archives. This matches the check it
  // / replaces, which required 7-Zip, zip, tar & mkisofs & nothing more.
  $sevenZipBinary = verifiedToolPath('7-Zip');
  $rarBinary = verifiedToolPath('RAR');
  $zipBinary = verifiedToolPath('Zip');
  $tarBinary = verifiedToolPath('Tar');
  $mkisofsBinary = verifiedToolPath('Genisoimage');
  $archiveToolsAreValid = ($sevenZipBinary !== FALSE && $zipBinary !== FALSE && $tarBinary !== FALSE && $mkisofsBinary !== FALSE);
  if ($sevenZipBinary === FALSE) {
    $ConversionErrors = TRUE;
    errorEntry('The installed 7-Zip version is missing, unidentifiable, or too old!', 13008, FALSE); }
  else {
    // / Create a folder to contain extracted files.
    @mkdir($safedir2, $PermissionLevels);
    if (!is_dir($safedir2)) $ConversionErrors = TRUE;
    // / Extract the source archive into the staging folder.
    // / The extractor is handed the staging folder's own entry, the folder followed by a dot, so its
    // / sandbox mounts that folder writable & nothing else. sandboxCommand() mounts the folder an output
    // / path is named in, & naming the staging folder itself mounted the whole conversion folder, where
    // / an extractor a crafted archive had fooled could write files & plant links beside everything else
    // / the session holds. The folder holding the archive is mounted read only, so everything the
    // / extractor can write is inside the folder pruneEscapingLinks() walks next.
    $extractTarget = $safedir2.$DirSep.'.';
    if ($Verbose) logEntry('Extracting file '.$pathname.' to '.$safedir2.'.');
    if (in_array(strtolower($oldExtension), $array7zo2)) $extractCommand = escapeshellarg($sevenZipBinary).' x -y '.escapeshellarg($pathname).' -o'.escapeshellarg($extractTarget);
    else if (in_array(strtolower($oldExtension), $arrayzipo) or in_array(strtolower($oldExtension), $array7zo) or in_array(strtolower($oldExtension), $arrayraro) or in_array(strtolower($oldExtension), $arraytaro)) $extractCommand = escapeshellarg($sevenZipBinary).' x -aoa '.escapeshellarg($pathname).' -o'.escapeshellarg($extractTarget);
    if ($extractCommand !== '') {
      list ($commandMayRun, $extractCommand) = sandboxCommand($extractCommand, $pathname, $extractTarget, FALSE, 'archive');
      if (!$commandMayRun) {
        $ConversionErrors = TRUE;
        errorEntry('Bubblewrap is missing or non functional, so this archive operation cannot be isolated!', 13006, FALSE); }
      else $returnData = shell_exec($extractCommand); }
    // / Log the output of the extract operation to the logfile, if it is not blank.
    if ($Verbose && trim($returnData) !== '') logEntry('The extractor returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
    // / Links are pruned before anything else reads the staging folder, because every later
    // / step follows them. The archiver copies the bytes of whatever a link points at, & the
    // / cleanup that follows this conversion cannot remove a link that resolves nowhere.
    // / This runs before the empty test below on purpose. An archive whose only member was an
    // / unsafe link leaves an empty folder & is then refused as an extraction that produced nothing.
    if ($extractCommand !== '' && is_dir($safedir2)) {
      list ($linkWalkCompleted, $linksRemoved) = pruneEscapingLinks($safedir2, $safedir2);
      if ($linksRemoved > 0) warningEntry('Removed '.$linksRemoved.' link(s) from the extracted archive because they did not resolve to a file inside it.'); }
    // / A walk that could not finish leaves links this conversion cannot account for, so the
    // / archive is refused rather than packaged while it may still reach outside the folder.
    if ($extractCommand !== '' && !$linkWalkCompleted) {
      $ConversionErrors = TRUE;
      errorEntry('The extracted archive could not be checked for unsafe links, so it was not re-archived!', 13014, FALSE); }
    // / An extraction that produced nothing must not be re-archived.
    else if ($extractCommand !== '' && is_dir_empty($safedir2)) {
      $ConversionErrors = TRUE;
      errorEntry('The extractor produced no files from the source archive!', 13007, FALSE); }
    else {
      if ($Verbose) logEntry('Archiving file '.$safedir2.' to '.$newPathname.'.');
      // / Select the archiver for the requested output format & confirm it is usable.
      if (in_array($extension, $array7zo)) {
        $archiveCommand = escapeshellarg($sevenZipBinary).' a -t'.escapeshellarg($extension).' '.escapeshellarg($newPathname).' '.escapeshellarg($safedir2);
        $archiveError = 13001; }
      // / A bootable disk image. The requested extension names the target architecture.
      else if (in_array($extension, $array7zo3)) {
        $archiveError = 13100;
        // / isohybrid is only needed by the generic hybrid image, so its absence is fatal
        // / to that one format & irrelevant to every other bootable one.
        $isoHybridBinary = verifiedToolPath('Syslinux Utils');
        if ($mkisofsBinary === FALSE) errorEntry('Mkisofs is missing, unidentifiable, or too old!', 13009, FALSE);
        else if ($extension === 'iso_gpt-boot' && $isoHybridBinary === FALSE) errorEntry('A hybrid bootable image requires the isohybrid utility from syslinux-utils, which is missing or too old!', 13107, FALSE);
        else {
          // / Confirm the bundled boot assets are intact before any of them are copied.
          $bootloadersAreValid = verifyIsoBootloaders($extension, $safedir2);
          if (!$bootloadersAreValid) errorEntry('Cryptographic validation failed for internal boot assets! Files are missing or corrupted.', 13105, FALSE);
          else {
            // / The requested extension is normalized back to iso here & the output path is
            // / corrected to match, so everything downstream sees an ordinary iso.
            list ($archiveCommand, $extension, $newPathname, $hybridCommand) = generateBootableIsoCommand($extension, $newPathname, $safedir2, $mkisofsBinary, $isoHybridBinary);
            if ($archiveCommand === FALSE) {
              $archiveCommand = '';
              errorEntry('Could not generate required bootloader assets!', 13106, FALSE); }
            else $UserFilename = str_replace($ConvertDir, '', $newPathname); } } }
      else if (in_array($extension, $array7zo2)) {
        if ($mkisofsBinary === FALSE) errorEntry('Mkisofs is missing, unidentifiable, or too old!', 13009, FALSE);
        else $archiveCommand = escapeshellarg($mkisofsBinary).' -o '.escapeshellarg($newPathname).' '.escapeshellarg($safedir2);
        $archiveError = 13002; }
      else if (in_array($extension, $arrayzipo)) {
        if ($zipBinary === FALSE) errorEntry('Zip is missing, unidentifiable, or too old!', 13010, FALSE);
        else $archiveCommand = escapeshellarg($zipBinary).' -r -j '.escapeshellarg($newPathname).' '.escapeshellarg($safedir2);
        $archiveError = 13003; }
      else if (in_array($extension, $arraytaro)) {
        if ($tarBinary === FALSE) errorEntry('Tar is missing, unidentifiable, or too old!', 13011, FALSE);
        else $archiveCommand = escapeshellarg($tarBinary).' -cjf '.escapeshellarg($newPathname).' -C '.escapeshellarg($safedir2).' .';
        $archiveError = 13004; }
      else if (in_array($extension, $arrayraro)) {
        // / 7-Zip cannot create rar archives. RAR compression is proprietary & 7-Zip reads
        // / the format without being able to write it, so there is NO fallback here.
        if ($rarBinary === FALSE) errorEntry('Rar output requires the rar utility, which is missing or too old!', 13013, FALSE);
        else $archiveCommand = escapeshellarg($rarBinary).' a -ep1 -r '.escapeshellarg($newPathname).' '.escapeshellarg($safedir2);
        $archiveError = 13005; }
      // / Perform the archive operation, retrying up to $StopCounter times.
      // / The loop exits as soon as the output exists. Without that test it always ran the
      // / full count & always reported a timeout, even on a conversion that succeeded.
      if ($archiveCommand !== '') {
        list ($commandMayRun, $archiveCommand) = sandboxCommand($archiveCommand, $safedir2, $newPathname, FALSE, 'archive');
        if (!$commandMayRun) {
          $ConversionErrors = TRUE;
          errorEntry('Bubblewrap is missing or non functional, so this archive operation cannot be isolated!', 13006, FALSE); }
        else {
          // / Attempt it as many times as --File Operation Retry Count-- allows, & stop at the first
          // / attempt that leaves an archive.
          while (pipelineAttemptIsDue($newPathname, $stopper)) {
            // / If the last attempt failed, wait a moment before trying again.
            if ($stopper !== 0) sleep($sleepTime++);
            $returnData = (string)shell_exec($archiveCommand.' 2>&1');
            // / Log the output of the archive operation to the logfile, if it is not blank.
            if ($Verbose && trim($returnData) !== '') logEntry('The archiver returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
            // / Count the number of attempts to avoid infinite loops.
            $stopper++; }
          // / Every attempt was made & none left an archive.
          // / What the archiver printed is logged above, with --Enhanced Logging Verbosity-- on.
          if (!file_exists($newPathname)) {
            $ConversionErrors = TRUE;
            errorEntry('The archiver produced no archive after '.$stopper.' attempt(s)!', $archiveError, FALSE); }
          // / Post process a hybrid image so it also boots from a USB stick presenting an MBR.
          // / This is a SEPARATE sandboxed invocation rather than a compound statement joined
          // / to the mkisofs command with &&. Bwrap execs one command & does not run a shell,
          // / so an && would be parsed by the outer shell & isohybrid would run OUTSIDE the
          // / namespace against a path that only exists inside it.
          // / isohybrid modifies the image IN PLACE, so the input & the output are the same
          // / file & sandboxCommand() mounts their shared directory once at /work.
          if ($hybridCommand !== '' && file_exists($newPathname)) {
            if ($Verbose) logEntry('Post processing the hybrid image with isohybrid.');
            list ($commandMayRun, $hybridCommand) = sandboxCommand($hybridCommand, $newPathname, $newPathname, FALSE, 'archive');
            if (!$commandMayRun) {
              $ConversionErrors = TRUE;
              errorEntry('Bubblewrap is missing or non functional, so this archive operation cannot be isolated!', 13006, FALSE); }
            else {
              $returnData = shell_exec($hybridCommand);
              // / Log the output of the operation to the logfile, if it is not blank.
              if ($Verbose && trim($returnData) !== '') logEntry('Isohybrid returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData))))); } } } } } }
  // / The output file is the only verdict on whether the conversion produced anything.
  // / An archiver that ran & left nothing has said so above, under its format's own number.
  // / This names only a failure nothing has named yet.
  if (!file_exists($newPathname)) {
    $ConversionErrors = TRUE;
    if ($stopper === 0) errorEntry('The archiver failed to produce an archive!', 13000, FALSE); }
  else $ConversionSuccess = TRUE;
  // / An ordinary archive keeps whatever filename the caller already worked out.
  // / Only a bootable image rewrites it, because only a bootable image renames the output.
  if ($UserFilename === '') $UserFilename = basename($newPathname);
  // / Code to clean up temporary files & directories.
  // / The second argument is the list of roots this call may clean under & is required.
  cleanFiles($safedir2, array($safedir2));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $linkWalkCompleted, $linksRemoved, $filename, $safedir2, $oldExtension, $returnData, $pathname, $array7zo, $arrayzipo, $array7zo2, $array7zo3, $arraytaro, $arrayraro, $sleepTime, $stopper, $extractCommand, $extractTarget, $archiveCommand, $hybridCommand, $archiveError, $commandMayRun, $archiveToolsAreValid, $bootloadersAreValid, $sevenZipBinary, $rarBinary, $zipBinary, $tarBinary, $mkisofsBinary, $isoHybridBinary);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $UserFilename, $WorkerPID); }
// / -----------------------------------------------------------------------------------

?>
