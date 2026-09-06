<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRConvert2, Copyright on 8/17/2026 by Justin Grimes, www.github.com/zelon88
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
// / v3.8.8.
// / This file is the converter for the Archive pipeline. It is loaded by pipelineCore.php
// / ONLY when a Archive conversion is about to be dispatched to it, so a request that
// / converts something else never parses a line of it.
// / Error block 24000 through 24010 belongs to this pipeline. Those numbers came with the code when it
// / moved out of convertCore.php & they did not change, because operators have read them.
// / verifyIsoBootloaders() & generateBootableIsoCommand() MOVED HERE WITH THE CONVERTER,
// / because convertCore.php had no other caller for either of them.
// / verifyArchiveVersions() & verifyIsoHybridVersion() DID NOT MOVE. archiveFiles(),
// / deleteFiles() & showVersionInfo() all still call them, & those run whether or not this
// / pipeline is installed.
// / This is the only pipeline that rewrites its own output name.
// / An extraction hands the user something named differently from what they asked for, which
// / is what the fifth value of the six value contract exists for. Every other pipeline
// / returns the filename it was given.
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
// / Returns TRUE without checking anything when the user supplied their own boot files,
// / because a user supplied bootloader is the users own business & is not ours to pin.
// / Returns FALSE when a bundled asset is missing, corrupt, or when the requested extension
// / is not one this function knows how to validate.
// /
// / Every bootable format is validated, including the legacy MBR image.
// / An earlier version skipped iso_mbr-boot entirely & returned TRUE for it without looking
// / at anything. Isolinux.bin is a bootloader copied into a user facing image, so it is
// / exactly as worth pinning as the UEFI images are & is now checked the same way.
// /
// / A hybrid image needs two assets rather than one.
// / It carries both an isolinux record & an EFI record, so isolinux.bin & the blank EFI
// / image are BOTH validated & both must pass. Every other format needs exactly one.
// / ldlinux.c32 is deliberately NOT pinned. It is optional, its absence is handled by the
// / builder, & a missing optional file must not fail a validation that would otherwise pass.
// /
// / A hash of all zeroes in the map means the asset has not been pinned yet & the image
// / cannot be built. That is deliberate. An unpinned bootloader is worse than no bootloader.
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
// / Accepts the requested bootable extension, the intended output path, the staging folder
// / & the two binaries the operation may need.
// / Returns the mkisofs command, the normalized extension, the corrected output path & a
// / SEPARATE isohybrid command which is an empty string for every image that does not need one.
// / Returns FALSE as the first value when no usable boot configuration could be assembled.
// /
// / The two commands are returned separately & must not be joined with &&.
// / shell_exec() runs its argument through a shell, but bwrap execs a single command & does
// / not. A compound statement handed to the sandbox therefore has its && parsed by the
// / OUTER shell, which would run isohybrid outside the namespace against a path that only
// / exists inside it. Each command is sandboxed on its own by the caller.
// /
// / The required boot files are injected into the staging folder in place, so the image is
// / built from a directory that already contains everything it needs.
// / A user who supplied their own boot files keeps them. Nothing bundled overwrites them.
// / The intent suffix is stripped from the returned path, so everything downstream sees an
// / ordinary iso rather than an architecture flag it does not understand.
function generateBootableIsoCommand($extension, $newPathname, $safedir2, $mkisofsBinary, $isoHybridBinary) {
  // / Set variables.
  global $DirSep, $BootloadersDir, $EnableMemoryProtection;
  $bootData = FALSE;
  $CleanNewPathname = $HybridCommand = $baseFlags = $bootFlags = $command = $userBin = $targetIsoLinuxDir = '';
  $bundledBin = $bundledC32 = $isoRelativeEfi = $bundledImg = $targetEfiName = $targetEfiPath = '';
  $selectedGpt = $gptMap = array();
  // / Basic validation checks to ensure filesystems and binaries exist.
  if ($mkisofsBinary !== FALSE && file_exists($mkisofsBinary) && is_dir($safedir2) && !empty($BootloadersDir) && is_dir($BootloadersDir)) {
    // / Normalize the target filename right away to end in a standard .iso extension.
    $CleanNewPathname = str_replace($extension, 'iso', $newPathname);
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
    // / The bundled assets live in the isolinux subdirectory, exactly as the MBR branch
    // / expects them. An earlier version read them from the top of $BootloadersDir.
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
      // / It carries BOTH an isolinux record & an EFI record, & isohybrid writes the MBR
      // / that points at the first of those. An architecture specific UEFI image has no
      // / isolinux record to point at, so it is never passed through isohybrid.
      // / This is tested BEFORE the extension is normalized below. An earlier version
      // / tested it afterwards, by which point the extension was always iso & the isohybrid
      // / step could never fire at all.
      if ($extension === 'iso_gpt-boot' && $isoHybridBinary !== FALSE) $HybridCommand = escapeshellarg($isoHybridBinary).' --uefi '.escapeshellarg($CleanNewPathname); } }
  // / Reset the extension to iso without the bootable architecture flag so the rest of the
  // / core will use the correct filename.
  $extension = 'iso';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $newPathname, $safedir2, $mkisofsBinary, $isoHybridBinary, $baseFlags, $bootFlags, $command, $userBin, $targetIsoLinuxDir, $bundledBin, $bundledC32, $gptMap, $selectedGpt, $isoRelativeEfi, $bundledImg, $targetEfiName, $targetEfiPath);
  return array($bootData, $extension, $CleanNewPathname, $HybridCommand); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert archive & disk image formats.
// / The source is extracted into a staging folder & the staging folder is re-archived.
// / 7-Zip is the only extractor, so every INPUT format depends on it & nothing works
// / without it. Each OUTPUT format has its own creator & gates on that creator alone, so a
// / missing mkisofs does not prevent a zip conversion.
// / An extraction that produces nothing is refused rather than re-archived, because the
// / archiver would package the empty staging folder & the result would exist, which is the
// / only test the success path performs.
// / A bootable ISO carries its target architecture in the requested extension, such as
// / iso_gpt-boot-x86-64. generateBootableIsoCommand() normalizes that back to iso & returns
// / a corrected output path, so both are reassigned from what it hands back.
// / A generic hybrid image is built in TWO sandboxed steps rather than one. Mkisofs writes
// / the image & isohybrid then rewrites its MBR in place.
function convertArchives($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $ConvertDir, $Lol, $Lolol, $StopCounter, $SleepTimer, $PermissionLevels, $Minimum7zVersion, $MinimumRarVersion, $MinimumZipVersion, $MinimumTarVersion, $MinimumMkisofsVersion, $MinimumIsoHybridVersion, $AllowBootableIsoImage, $BootableIsoArray, $EnableMemoryProtection;
  // / The six value pipeline contract. $UserFilename is this converter's fifth value,
  // / because an archive extraction renames what the user is given.
  $WorkerPID = 0;
  $ConversionSuccess = $ConversionErrors = $commandMayRun = FALSE;
  $archiveToolsAreValid = $bootloadersAreValid = FALSE;
  $sevenZipBinary = $rarBinary = $zipBinary = $tarBinary = $mkisofsBinary = $isoHybridBinary = FALSE;
  $returnData = $extractCommand = $archiveCommand = $hybridCommand = $UserFilename = '';
  $stopper = 0;
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
  list ($archiveToolsAreValid, $sevenZipBinary, $rarBinary, $zipBinary, $tarBinary, $mkisofsBinary) = verifyArchiveVersions($Minimum7zVersion, $MinimumRarVersion, $MinimumZipVersion, $MinimumTarVersion, $MinimumMkisofsVersion);
  if ($sevenZipBinary === FALSE) {
    $ConversionErrors = TRUE;
    errorEntry('The installed 7-Zip version is missing, unidentifiable, or too old!', 13008, FALSE); }
  else {
    // / Create a folder to contain extracted files.
    @mkdir($safedir2, $PermissionLevels);
    if (!is_dir($safedir2)) $ConversionErrors = TRUE;
    // / Extract the source archive into the staging folder.
    if ($Verbose) logEntry('Extracting file '.$pathname.' to '.$safedir2.'.');
    if (in_array(strtolower($oldExtension), $array7zo2)) $extractCommand = escapeshellarg($sevenZipBinary).' x -y '.escapeshellarg($pathname).' -o'.escapeshellarg($safedir2);
    else if (in_array(strtolower($oldExtension), $arrayzipo) or in_array(strtolower($oldExtension), $array7zo) or in_array(strtolower($oldExtension), $arrayraro) or in_array(strtolower($oldExtension), $arraytaro)) $extractCommand = escapeshellarg($sevenZipBinary).' x -aoa '.escapeshellarg($pathname).' -o'.escapeshellarg($safedir2);
    if ($extractCommand !== '') {
      list ($commandMayRun, $extractCommand) = sandboxCommand($extractCommand, $pathname, $safedir2, FALSE, 'archive');
      if (!$commandMayRun) {
        $ConversionErrors = TRUE;
        errorEntry('Bubblewrap is missing or non functional, so this archive operation cannot be isolated!', 13006, FALSE); }
      else $returnData = shell_exec($extractCommand); }
    // / Log the output of the extract operation to the logfile, if it is not blank.
    if ($Verbose && trim($returnData) !== '') logEntry('The extractor returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
    // / An extraction that produced nothing must not be re-archived.
    if ($extractCommand !== '' && is_dir_empty($safedir2)) {
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
        $isoHybridBinary = verifyIsoHybridVersion($MinimumIsoHybridVersion);
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
          while (!file_exists($newPathname) && $stopper <= $StopCounter) {
            // / If the last attempt failed, wait a moment before trying again.
            if ($stopper !== 0) sleep($sleepTime++);
            $returnData = shell_exec($archiveCommand);
            // / Log the output of the archive operation to the logfile, if it is not blank.
            if ($Verbose && trim($returnData) !== '') logEntry('The archiver returned the following: '.$Lol.'  '.str_replace($Lol, $Lol.'  ', str_replace($Lolol, $Lol, str_replace($Lolol, $Lol, trim($returnData)))));
            // / Count the number of attempts to avoid infinite loops.
            $stopper++;
            // / Stop attempting the archive operation after $StopCounter number of attempts.
            if ($stopper === $StopCounter) {
              $ConversionErrors = TRUE;
              errorEntry('The archiver timed out!', $archiveError, FALSE); } }
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
  if (!file_exists($newPathname)) {
    $ConversionErrors = TRUE;
    errorEntry('The archiver failed to produce an archive!', 13000, FALSE); }
  else $ConversionSuccess = TRUE;
  // / An ordinary archive keeps whatever filename the caller already worked out.
  // / Only a bootable image rewrites it, because only a bootable image renames the output.
  if ($UserFilename === '') $UserFilename = basename($newPathname);
  // / Code to clean up temporary files & directories.
  // / The second argument is the list of roots this call may clean under & is required.
  cleanFiles($safedir2, array($safedir2));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $filename, $safedir2, $oldExtension, $returnData, $pathname, $array7zo, $arrayzipo, $array7zo2, $array7zo3, $arraytaro, $arrayraro, $sleepTime, $stopper, $extractCommand, $archiveCommand, $hybridCommand, $archiveError, $commandMayRun, $archiveToolsAreValid, $bootloadersAreValid, $sevenZipBinary, $rarBinary, $zipBinary, $tarBinary, $mkisofsBinary, $isoHybridBinary);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $UserFilename, $WorkerPID); }
// / -----------------------------------------------------------------------------------

?>
