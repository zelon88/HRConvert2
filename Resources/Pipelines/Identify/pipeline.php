<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRConvert2, Copyright on 11/11/2026 by Justin Grimes, www.github.com/zelon88
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
// / v4.1.2.
// / The Identify pipeline. pipelineCore.php loads it only to dispatch an Identify operation.
// / Error block 37000 through 37004 belongs to this pipeline.
// / Identify is an operation pipeline. It takes a selection of files & changes none of them.
// / It reports how large each one is, when it changed, what it hashes to & what it holds.
// / It returns three values, the third being the records it gathered & the name of any report it wrote.
// / A pipeline never prints. See contract 6.
// / It is dispatched by runOperationPipeline('Identify'), never through convert().
// / File, FFprobe, PDFInfo, ImageMagick & 7-Zip come from verifiedToolPath(), & every one of them is optional.
// / A missing tool costs the details it would have read & nothing else.
// / Every tool runs in the sandbox with no network, because every one of them parses whatever a user uploaded.
// / A file is hashed & measured as text by PHP alone, because counting bytes parses nothing.
// / This pipeline carries its own ImageMagick policy, in Policy/policy.xml beside this file.
// / It refuses every delegate & every coder that reaches Ghostscript, because identifying an image never needs either.
// / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt for the contracts this file obeys.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline component cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to run one sandboxed command & read what it prints, within a size & a time.
// / Accepts the command, the most bytes to read & the most seconds to wait, in that order.
// / Returns whether it ran, what it printed, whether that was cut short & its exit status, in that order.
// / A tool reading a hostile file may print without end or never finish.
// / Past either bound the tool is killed, & the caller is told what it printed is partial.
// / Standard error is read with standard output, because a tool reports trouble there.
// / Nothing is written to standard input, so a tool asking a question reads an empty answer rather than waiting.
function identifyRunCommand($commandToRun, $outputLimit, $secondsLimit) {
  // / Set variables.
  global $EnableMemoryProtection;
  $CommandRan = $OutputWasCut = FALSE;
  $CommandOutput = '';
  $ExitStatus = -1;
  $commandProcess = FALSE;
  $commandPipes = $readablePipes = array();
  $writablePipes = $exceptionalPipes = NULL;
  $outputChunk = '';
  $deadline = 0.0;
  $pipesReady = 0;
  // / Standard error is joined to standard output by the descriptor rather than by a redirection in the command, so it covers the whole command whatever its shape.
  $commandProcess = @proc_open($commandToRun, array(0 => array('file', '/dev/null', 'r'), 1 => array('pipe', 'w'), 2 => array('redirect', 1)), $commandPipes);
  if (is_resource($commandProcess) && isset($commandPipes[1])) {
    $CommandRan = TRUE;
    stream_set_blocking($commandPipes[1], FALSE);
    $deadline = microtime(TRUE) + (float)$secondsLimit;
    while (!$OutputWasCut && !feof($commandPipes[1])) {
      redeclare($readablePipes, array($commandPipes[1]));
      $pipesReady = (int)@stream_select($readablePipes, $writablePipes, $exceptionalPipes, 1, 0);
      if ($pipesReady > 0) {
        redeclare($outputChunk, (string)fread($commandPipes[1], 65536));
        redeclare($CommandOutput, $CommandOutput.$outputChunk); }
      if (strlen($CommandOutput) > (int)$outputLimit or microtime(TRUE) > $deadline) $OutputWasCut = TRUE; }
    // / The shell is killed, & bubblewrap dies with its parent & takes the tool with it.
    if ($OutputWasCut) proc_terminate($commandProcess, 9);
    fclose($commandPipes[1]);
    $ExitStatus = proc_close($commandProcess); }
  // / A command that could not be started leaves a tool unasked, & the operator is told why the details are missing.
  else warningEntry('A tool could not be started to identify a file. PHP may have proc_open disabled.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $commandProcess, $commandPipes, $readablePipes, $writablePipes, $exceptionalPipes, $outputChunk, $deadline, $pipesReady, $commandToRun, $outputLimit, $secondsLimit);
  return array($CommandRan, $CommandOutput, $OutputWasCut, $ExitStatus); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to make text a tool printed safe to show & to store.
// / Accepts the text & the most characters to keep, in that order. Returns the text.
// / A tool repeats what a file says about itself, such as a title, & a hostile file may say anything.
// / Invalid UTF-8 becomes the replacement character & every control character becomes a space.
// / A terminal reading a report therefore never receives an escape sequence a file chose.
// / htmlspecialchars substitutes the invalid sequences, & decoding its output restores everything else, so no extension beyond PHP is needed.
function identifyCleanText($toolText, $characterLimit) {
  // / Set variables.
  global $EnableMemoryProtection;
  $CleanText = '';
  $validText = $spacedText = '';
  $characterMatches = array();
  $validText = htmlspecialchars_decode(htmlspecialchars((string)$toolText, ENT_SUBSTITUTE | ENT_NOQUOTES, 'UTF-8'), ENT_NOQUOTES);
  $spacedText = trim((string)preg_replace('/[\x{0}-\x{1F}\x{7F}-\x{9F}]+/u', ' ', $validText));
  // / A character is counted rather than a byte, so a limit never splits one in two.
  if (preg_match('/^.{0,'.max(1, (int)$characterLimit).'}/su', $spacedText, $characterMatches) === 1) $CleanText = $characterMatches[0];
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $validText, $spacedText, $characterMatches, $toolText, $characterLimit);
  return $CleanText; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to separate the bytes that end part way through a UTF-8 character from the rest.
// / Accepts the bytes. Returns the complete part & the incomplete end, in that order.
// / A file read in pieces splits a character whenever a piece ends inside one.
// / The end is carried into the next piece, so a character is judged whole rather than as two halves.
// / A lead byte is looked for in the last four bytes only, because no character is longer.
function identifySplitCharacter($byteString) {
  // / Set variables.
  global $EnableMemoryProtection;
  $CompleteBytes = $TrailingBytes = '';
  $byteLength = $leadPosition = $splitPosition = $leadValue = $sequenceLength = 0;
  $byteLength = strlen((string)$byteString);
  $leadPosition = $byteLength - 1;
  $splitPosition = $byteLength;
  // / Step back over at most three continuation bytes, to the byte that began the last character.
  while ($leadPosition >= 0 && $leadPosition > $byteLength - 4 && (ord($byteString[$leadPosition]) & 0xC0) === 0x80) $leadPosition--;
  if ($leadPosition >= 0) {
    $leadValue = ord($byteString[$leadPosition]);
    $sequenceLength = ($leadValue >= 0xF0) ? 4 : (($leadValue >= 0xE0) ? 3 : (($leadValue >= 0xC0) ? 2 : 1));
    if ($leadPosition + $sequenceLength > $byteLength) $splitPosition = $leadPosition; }
  $CompleteBytes = substr((string)$byteString, 0, $splitPosition);
  $TrailingBytes = substr((string)$byteString, $splitPosition);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $byteLength, $leadPosition, $splitPosition, $leadValue, $sequenceLength, $byteString);
  return array($CompleteBytes, $TrailingBytes); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to describe what the counts of line endings in a text say about it.
// / Accepts the paired endings, the lone line feeds, the lone carriage returns & whether the text ends without one, in that order.
// / Returns how its lines end, as CRLF, LF, CR, Mixed or None, & how many lines it has, in that order.
// / A last line with no ending is still a line, so it is counted.
function identifyDescribeLines($pairedEndings, $lineFeeds, $carriageReturns, $endsWithoutEnding) {
  // / Set variables.
  global $EnableMemoryProtection;
  $LineEndings = 'None';
  $LineCount = 0;
  $kindsFound = 0;
  $kindsFound = (int)($pairedEndings > 0) + (int)($lineFeeds > 0) + (int)($carriageReturns > 0);
  if ($kindsFound > 1) $LineEndings = 'Mixed';
  else if ($pairedEndings > 0) $LineEndings = 'CRLF';
  else if ($lineFeeds > 0) $LineEndings = 'LF';
  else if ($carriageReturns > 0) $LineEndings = 'CR';
  $LineCount = (int)$pairedEndings + (int)$lineFeeds + (int)$carriageReturns + ($endsWithoutEnding ? 1 : 0);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $kindsFound, $pairedEndings, $lineFeeds, $carriageReturns, $endsWithoutEnding);
  return array($LineEndings, $LineCount); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to name the byte order mark a file opens with.
// / Accepts the first four bytes. Returns the name, or None.
// / UTF-32 is tested before UTF-16, because a UTF-32 little endian mark begins with the UTF-16 one.
function identifyByteOrderMark($openingBytes) {
  // / Set variables.
  global $EnableMemoryProtection;
  $MarkName = 'None';
  $openingText = '';
  $openingText = (string)$openingBytes;
  if (strncmp($openingText, "\xEF\xBB\xBF", 3) === 0) $MarkName = 'UTF-8';
  else if (strncmp($openingText, "\xFF\xFE\x00\x00", 4) === 0) $MarkName = 'UTF-32 LE';
  else if (strncmp($openingText, "\x00\x00\xFE\xFF", 4) === 0) $MarkName = 'UTF-32 BE';
  else if (strncmp($openingText, "\xFF\xFE", 2) === 0) $MarkName = 'UTF-16 LE';
  else if (strncmp($openingText, "\xFE\xFF", 2) === 0) $MarkName = 'UTF-16 BE';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $openingText, $openingBytes);
  return $MarkName; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to read one file once, hashing it & measuring it as text in the same pass.
// / Accepts the path. Returns whether it was read, its hashes & what it holds as text, in that order.
// / The hashes are SHA-256, SHA-1 & MD5, keyed SHA256, SHA1 & MD5.
// / The text facts are the byte order mark, how lines end, how many lines there are, whether it is valid UTF-8 & whether it holds a null byte.
// / They are measured whatever the file holds, & the caller decides whether they mean anything.
// / A file is read a megabyte at a time, so a file of any size costs a megabyte of memory.
// / A line ending or a character split between two reads is carried into the next read rather than miscounted.
// / The first value is this function's sanity check, because a file that could not be opened has no facts.
function identifyReadFacts($filePath) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FileWasRead = FALSE;
  $FileHashes = $TextFacts = array();
  $fileHandle = FALSE;
  $sha256Context = $sha1Context = $md5Context = NULL;
  $fileChunk = $openingBytes = $characterCarry = $completeBytes = $carriedBytes = $lastByte = $lineEndings = '';
  $pairedEndings = $carriageReturns = $lineFeeds = $chunkPairs = $lineCount = $bytesRead = 0;
  $previousEndedWithReturn = $nullByteWasSeen = FALSE;
  $textIsValid = TRUE;
  $fileHandle = @fopen((string)$filePath, 'rb');
  if ($fileHandle !== FALSE) {
    $sha256Context = hash_init('sha256');
    $sha1Context = hash_init('sha1');
    $md5Context = hash_init('md5');
    while (!feof($fileHandle)) {
      redeclare($fileChunk, (string)fread($fileHandle, 1048576));
      if ($fileChunk === '') continue;
      hash_update($sha256Context, $fileChunk);
      hash_update($sha1Context, $fileChunk);
      hash_update($md5Context, $fileChunk);
      if ($bytesRead === 0) redeclare($openingBytes, substr($fileChunk, 0, 4));
      $bytesRead = $bytesRead + strlen($fileChunk);
      // / A pair is counted once, & a carriage return or line feed outside one is counted alone.
      $chunkPairs = substr_count($fileChunk, "\r\n");
      $pairedEndings = $pairedEndings + $chunkPairs;
      $carriageReturns = $carriageReturns + substr_count($fileChunk, "\r") - $chunkPairs;
      $lineFeeds = $lineFeeds + substr_count($fileChunk, "\n") - $chunkPairs;
      // / A pair split between two reads was counted as one of each, so it is counted again as the pair it is.
      if ($previousEndedWithReturn && $fileChunk[0] === "\n") {
        $pairedEndings++;
        $carriageReturns--;
        $lineFeeds--; }
      $previousEndedWithReturn = (substr($fileChunk, -1) === "\r");
      if (!$nullByteWasSeen && strpos($fileChunk, "\0") !== FALSE) $nullByteWasSeen = TRUE;
      redeclare($lastByte, substr($fileChunk, -1));
      // / Once the text is known to be invalid there is nothing left to learn by checking the rest.
      if ($textIsValid) {
        // / What the read before this one carried is shredded first, as redeclare() would, because a list() cannot pass through redeclare().
        purgeSensitiveMemory($EnableMemoryProtection, $completeBytes, $carriedBytes);
        list ($completeBytes, $carriedBytes) = identifySplitCharacter($characterCarry.$fileChunk);
        if ($completeBytes !== '' && preg_match('//u', $completeBytes) !== 1) $textIsValid = FALSE;
        redeclare($characterCarry, $carriedBytes); } }
    fclose($fileHandle);
    // / A character still unfinished when the file ends was never finished.
    if ($characterCarry !== '') $textIsValid = FALSE;
    $FileHashes = array('SHA256' => hash_final($sha256Context), 'SHA1' => hash_final($sha1Context), 'MD5' => hash_final($md5Context));
    list ($lineEndings, $lineCount) = identifyDescribeLines($pairedEndings, $lineFeeds, $carriageReturns, ($bytesRead > 0 && $lastByte !== "\n" && $lastByte !== "\r"));
    $TextFacts = array('ByteOrderMark' => identifyByteOrderMark($openingBytes), 'LineEndings' => $lineEndings, 'Lines' => $lineCount, 'ValidUtf8' => $textIsValid, 'NullBytes' => $nullByteWasSeen);
    $FileWasRead = TRUE; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $fileHandle, $sha256Context, $sha1Context, $md5Context, $fileChunk, $openingBytes, $characterCarry, $completeBytes, $carriedBytes, $lastByte, $lineEndings, $pairedEndings, $carriageReturns, $lineFeeds, $chunkPairs, $lineCount, $bytesRead, $previousEndedWithReturn, $nullByteWasSeen, $textIsValid, $filePath);
  return array($FileWasRead, $FileHashes, $TextFacts); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to run one tool against one file in the sandbox.
// / Accepts the command naming the file by its path, the path, the scratch folder, the sandbox profile, the policy folder, the policy variables & the output limit, in that order.
// / Returns whether the sandbox allowed it, whether it ran, what it printed, whether that was cut short & its exit status, in that order.
// / The file is mounted read only & the scratch folder is the only place the tool may write.
// / Nothing reaches the network, because no tool here needs to.
// / Every tool is given sixty seconds, which a tool reading one file's headers never needs.
function identifyRunTool($toolCommand, $filePath, $scratchFolder, $sandboxProfile, $policyFolder, $policyVariables, $outputLimit) {
  // / Set variables.
  global $EnableMemoryProtection, $DirSep;
  $SandboxAllowed = $ToolRan = $OutputWasCut = FALSE;
  $ToolOutput = '';
  $ExitStatus = -1;
  $sandboxedCommand = '';
  // / The output named here is never written. It names the scratch folder as the one writable mount.
  list ($SandboxAllowed, $sandboxedCommand) = sandboxCommand($toolCommand, $filePath, $scratchFolder.$DirSep.'output', FALSE, $sandboxProfile, $policyFolder, $policyVariables);
  if ($SandboxAllowed) list ($ToolRan, $ToolOutput, $OutputWasCut, $ExitStatus) = identifyRunCommand($sandboxedCommand, $outputLimit, 60);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $sandboxedCommand, $toolCommand, $filePath, $scratchFolder, $sandboxProfile, $policyFolder, $policyVariables, $outputLimit);
  return array($SandboxAllowed, $ToolRan, $ToolOutput, $OutputWasCut, $ExitStatus); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to ask file what one file holds, judged by its content rather than its name.
// / Accepts the file binary, the path & the scratch folder, in that order.
// / Returns whether the sandbox allowed it, whether it answered, its description, the MIME type, the character set & the extensions it expects, in that order.
// / It is asked three times, because no single run prints all three answers.
// / An expected extension of ??? means file knows of none, so none is returned.
function identifyAskFile($fileBinary, $filePath, $scratchFolder) {
  // / Set variables.
  global $EnableMemoryProtection;
  $SandboxAllowed = $FileAnswered = FALSE;
  $Description = $MimeType = $Charset = '';
  $ExpectedExtensions = array();
  $toolRan = $outputWasCut = FALSE;
  $descriptionOutput = $mimeOutput = $extensionOutput = '';
  $exitStatus = -1;
  $mimeParts = array();
  list ($SandboxAllowed, $toolRan, $descriptionOutput, $outputWasCut, $exitStatus) = identifyRunTool(escapeshellarg($fileBinary).' -b '.escapeshellarg($filePath), $filePath, $scratchFolder, 'generic', '', array(), 65536);
  if ($SandboxAllowed && $toolRan && $exitStatus === 0) {
    $FileAnswered = TRUE;
    $Description = identifyCleanText($descriptionOutput, 300);
    list ($SandboxAllowed, $toolRan, $mimeOutput, $outputWasCut, $exitStatus) = identifyRunTool(escapeshellarg($fileBinary).' -b --mime '.escapeshellarg($filePath), $filePath, $scratchFolder, 'generic', '', array(), 65536);
    // / The answer reads like image/png; charset=binary, & the two halves are kept apart.
    if ($toolRan && $exitStatus === 0 && preg_match('/^\s*([A-Za-z0-9.+\/-]+)\s*(?:;\s*charset=([A-Za-z0-9._-]+))?/', $mimeOutput, $mimeParts) === 1) {
      $MimeType = strtolower($mimeParts[1]);
      if (isset($mimeParts[2])) $Charset = strtolower($mimeParts[2]); }
    list ($SandboxAllowed, $toolRan, $extensionOutput, $outputWasCut, $exitStatus) = identifyRunTool(escapeshellarg($fileBinary).' -b --extension '.escapeshellarg($filePath), $filePath, $scratchFolder, 'generic', '', array(), 65536);
    if ($toolRan && $exitStatus === 0 && trim($extensionOutput) !== '???') $ExpectedExtensions = array_values(array_filter(array_map('strtolower', array_map('trim', explode('/', identifyCleanText($extensionOutput, 300)))), 'strlen')); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $toolRan, $outputWasCut, $descriptionOutput, $mimeOutput, $extensionOutput, $exitStatus, $mimeParts, $fileBinary, $filePath, $scratchFolder);
  return array($SandboxAllowed, $FileAnswered, $Description, $MimeType, $Charset, $ExpectedExtensions); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to ask ImageMagick what an image is, without decoding its pixels.
// / Accepts the ImageMagick binary, the path & the scratch folder, in that order.
// / Returns whether the sandbox allowed it, the image facts & any note to make about it, in that order.
// / The facts are Format, Width, Height, Colorspace, Depth & Frames, & are empty when it could not say.
// / One line is printed for every frame, so an animation is counted by its lines.
// / The policy this pipeline carries refuses every delegate & every coder that reaches Ghostscript.
function identifyAskImageMagick($imageBinary, $filePath, $scratchFolder) {
  // / Set variables.
  global $EnableMemoryProtection, $DirSep;
  $SandboxAllowed = FALSE;
  $ImageFacts = array();
  $ImageNote = '';
  $toolRan = $outputWasCut = FALSE;
  $toolOutput = $policyFolder = '';
  $exitStatus = -1;
  $policyVariables = $frameLines = $frameFields = array();
  $policyFolder = dirname(__FILE__).$DirSep.'Policy';
  if (is_file($policyFolder.$DirSep.'policy.xml')) $policyVariables = array('MAGICK_CONFIGURE_PATH' => $policyFolder);
  else $policyFolder = '';
  // / The policy.xml the installed ImageMagick reads is covered too, wherever its build keeps it.
  if ($policyFolder !== '' && function_exists('coverResolvedImageMagickPolicy')) coverResolvedImageMagickPolicy('imagemagick');
  // / Without its own policy the host's would govern a hostile image, so ImageMagick is not asked at all.
  if ($policyFolder === '') {
    $SandboxAllowed = TRUE;
    $ImageNote = 'The ImageMagick policy this pipeline carries is missing, so the image was not examined.';
    warningEntry('The ImageMagick policy the Identify pipeline carries is missing, so no image is examined. Restore the pipeline from the release this installation came from.'); }
  else {
    list ($SandboxAllowed, $toolRan, $toolOutput, $outputWasCut, $exitStatus) = identifyRunTool(escapeshellarg($imageBinary).' identify -ping -format '.escapeshellarg('%m|%w|%h|%[colorspace]|%z\n').' '.escapeshellarg($filePath), $filePath, $scratchFolder, 'imagemagick', $policyFolder, $policyVariables, 262144);
    $frameLines = array_values(array_filter(preg_split('/\r?\n/', (string)$toolOutput), 'identifyIsFrameLine'));
    if ($toolRan && count($frameLines) > 0) {
      $frameFields = explode('|', $frameLines[0]);
      $ImageFacts = array('Format' => identifyCleanText($frameFields[0], 40), 'Width' => (int)$frameFields[1], 'Height' => (int)$frameFields[2], 'Colorspace' => identifyCleanText($frameFields[3], 40), 'Depth' => (int)$frameFields[4], 'Frames' => count($frameLines));
      if ($outputWasCut) $ImageNote = 'The image has more frames than Identify counts, so the frame count is a minimum.'; }
    else if ($SandboxAllowed) $ImageNote = 'ImageMagick could not read this image.'; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $toolRan, $outputWasCut, $toolOutput, $policyFolder, $exitStatus, $policyVariables, $frameLines, $frameFields, $imageBinary, $filePath, $scratchFolder);
  return array($SandboxAllowed, $ImageFacts, $ImageNote); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to tell a line ImageMagick printed for one frame from anything else it printed.
// / Accepts the line. Returns whether it is a frame line.
// / A frame line holds five fields, & the second, third & fifth are numbers.
// / A warning ImageMagick prints about a damaged file is therefore never counted as a frame.
function identifyIsFrameLine($outputLine) {
  // / Set variables.
  global $EnableMemoryProtection;
  $LineIsFrame = FALSE;
  $LineIsFrame = (preg_match('/^[A-Za-z0-9-]+\|\d+\|\d+\|[^|]*\|\d+$/', trim((string)$outputLine)) === 1);
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $outputLine);
  return $LineIsFrame; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to ask FFprobe what an audio or a video file holds.
// / Accepts the FFprobe binary, the path & the scratch folder, in that order.
// / Returns whether the sandbox allowed it, the media facts & any note to make about it, in that order.
// / The facts are Container, Duration, BitRate & Streams, & are empty when it could not say.
// / Only the file protocol is permitted, so a file naming another resource cannot have it opened.
function identifyAskFFprobe($probeBinary, $filePath, $scratchFolder) {
  // / Set variables.
  global $EnableMemoryProtection;
  $SandboxAllowed = FALSE;
  $MediaFacts = array();
  $MediaNote = '';
  $toolRan = $outputWasCut = FALSE;
  $toolOutput = '';
  $exitStatus = -1;
  $jsonStart = $jsonEnd = FALSE;
  $probeAnswer = $streamRecord = $streamList = array();
  list ($SandboxAllowed, $toolRan, $toolOutput, $outputWasCut, $exitStatus) = identifyRunTool(escapeshellarg($probeBinary).' -v error -hide_banner -protocol_whitelist file -show_entries '.escapeshellarg('format=format_name,format_long_name,duration,bit_rate:stream=codec_type,codec_name,width,height,sample_rate,channels').' -of json '.escapeshellarg($filePath), $filePath, $scratchFolder, 'ffmpeg', '', array(), 1048576);
  // / A warning FFprobe prints is read with its answer, so the answer is taken from its first opening brace to its last closing one.
  $jsonStart = strpos((string)$toolOutput, '{');
  $jsonEnd = strrpos((string)$toolOutput, '}');
  if ($toolRan && !$outputWasCut && $jsonStart !== FALSE && $jsonEnd !== FALSE && $jsonEnd > $jsonStart) $probeAnswer = json_decode(substr((string)$toolOutput, $jsonStart, $jsonEnd - $jsonStart + 1), TRUE);
  if (is_array($probeAnswer) && isset($probeAnswer['format']) && is_array($probeAnswer['format'])) {
    foreach ((isset($probeAnswer['streams']) && is_array($probeAnswer['streams'])) ? $probeAnswer['streams'] : array() as $streamRecord) {
      if (!is_array($streamRecord)) continue;
      $streamList[] = array('Type' => identifyCleanText(isset($streamRecord['codec_type']) ? $streamRecord['codec_type'] : '', 20), 'Codec' => identifyCleanText(isset($streamRecord['codec_name']) ? $streamRecord['codec_name'] : '', 40),
        'Width' => isset($streamRecord['width']) ? (int)$streamRecord['width'] : 0, 'Height' => isset($streamRecord['height']) ? (int)$streamRecord['height'] : 0,
        'SampleRate' => isset($streamRecord['sample_rate']) ? (int)$streamRecord['sample_rate'] : 0, 'Channels' => isset($streamRecord['channels']) ? (int)$streamRecord['channels'] : 0); }
    $MediaFacts = array('Container' => identifyCleanText(isset($probeAnswer['format']['format_long_name']) ? $probeAnswer['format']['format_long_name'] : (isset($probeAnswer['format']['format_name']) ? $probeAnswer['format']['format_name'] : ''), 80),
      'Duration' => isset($probeAnswer['format']['duration']) ? round((float)$probeAnswer['format']['duration'], 2) : 0, 'BitRate' => isset($probeAnswer['format']['bit_rate']) ? (int)$probeAnswer['format']['bit_rate'] : 0, 'Streams' => $streamList); }
  else if ($SandboxAllowed) $MediaNote = 'FFprobe could not read this file.';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $toolRan, $outputWasCut, $toolOutput, $exitStatus, $jsonStart, $jsonEnd, $probeAnswer, $streamRecord, $streamList, $probeBinary, $filePath, $scratchFolder);
  return array($SandboxAllowed, $MediaFacts, $MediaNote); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to ask PDFInfo what a PDF holds.
// / Accepts the PDFInfo binary, the path & the scratch folder, in that order.
// / Returns whether the sandbox allowed it, the PDF facts & any note to make about it, in that order.
// / The facts are Pages, Version, Encrypted, JavaScript, PageSize, Title & Producer, & are empty when it could not say.
// / It is never given -dests, because that option is where CVE-2024-6239 is reached.
function identifyAskPdfInfo($pdfBinary, $filePath, $scratchFolder) {
  // / Set variables.
  global $EnableMemoryProtection;
  $SandboxAllowed = FALSE;
  $PdfFacts = array();
  $PdfNote = '';
  $toolRan = $outputWasCut = FALSE;
  $toolOutput = $outputLine = $fieldName = '';
  $exitStatus = -1;
  $answerFields = $lineParts = array();
  list ($SandboxAllowed, $toolRan, $toolOutput, $outputWasCut, $exitStatus) = identifyRunTool(escapeshellarg($pdfBinary).' '.escapeshellarg($filePath), $filePath, $scratchFolder, 'poppler', '', array(), 65536);
  foreach (preg_split('/\r?\n/', (string)$toolOutput) as $outputLine) {
    if (preg_match('/^([A-Za-z][A-Za-z ]{0,30}):\s*(.*)$/', $outputLine, $lineParts) !== 1) continue;
    redeclare($fieldName, strtolower(trim($lineParts[1])));
    $answerFields[$fieldName] = identifyCleanText($lineParts[2], 200); }
  if ($toolRan && $exitStatus === 0 && isset($answerFields['pages'])) $PdfFacts = array('Pages' => (int)$answerFields['pages'],
    'Version' => isset($answerFields['pdf version']) ? $answerFields['pdf version'] : '', 'Encrypted' => isset($answerFields['encrypted']) ? (stripos($answerFields['encrypted'], 'yes') === 0) : FALSE,
    'JavaScript' => isset($answerFields['javascript']) ? (stripos($answerFields['javascript'], 'yes') === 0) : FALSE, 'PageSize' => isset($answerFields['page size']) ? $answerFields['page size'] : '',
    'Title' => isset($answerFields['title']) ? $answerFields['title'] : '', 'Producer' => isset($answerFields['producer']) ? $answerFields['producer'] : '');
  else if ($toolRan && stripos((string)$toolOutput, 'password') !== FALSE) $PdfNote = 'The PDF needs a password to be opened, so its details were not read.';
  else if ($SandboxAllowed) $PdfNote = 'PDFInfo could not read this PDF.';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $toolRan, $outputWasCut, $toolOutput, $outputLine, $fieldName, $exitStatus, $answerFields, $lineParts, $pdfBinary, $filePath, $scratchFolder);
  return array($SandboxAllowed, $PdfFacts, $PdfNote); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to count what an archive lists, without extracting anything.
// / Accepts the 7-Zip binary, the path & the scratch folder, in that order.
// / Returns whether the sandbox allowed it, the archive facts & any note to make about it, in that order.
// / The facts are Format, Entries, Files, Folders, UnpackedSize & Encrypted, & are empty when it could not say.
// / 7-Zip is given a password nobody chose, so an archive whose listing is encrypted fails at once rather than asking for one.
// / A folder is marked Folder = + in some formats & by a D in its attributes in others, & both are read.
function identifyAskSevenZip($sevenZipBinary, $filePath, $scratchFolder) {
  // / Set variables.
  global $EnableMemoryProtection;
  $SandboxAllowed = FALSE;
  $ArchiveFacts = array();
  $ArchiveNote = '';
  $toolRan = $outputWasCut = $listingStarted = FALSE;
  $toolOutput = $outputLine = $archiveFormat = '';
  $exitStatus = -1;
  $entryCount = $folderCount = $encryptedCount = $unpackedSize = 0;
  $lineParts = array();
  list ($SandboxAllowed, $toolRan, $toolOutput, $outputWasCut, $exitStatus) = identifyRunTool(escapeshellarg($sevenZipBinary).' l -slt -bd -pIdentifyHasNoPassword '.escapeshellarg($filePath), $filePath, $scratchFolder, 'archive', '', array(), 4194304);
  foreach (preg_split('/\r?\n/', (string)$toolOutput) as $outputLine) {
    // / Everything above the first line of dashes describes the archive, & everything below it is an entry.
    if (!$listingStarted && trim($outputLine) === '----------') $listingStarted = TRUE;
    else if (!$listingStarted && preg_match('/^Type = (.+)$/', $outputLine, $lineParts) === 1) redeclare($archiveFormat, identifyCleanText($lineParts[1], 40));
    else if ($listingStarted && strpos($outputLine, 'Path = ') === 0) $entryCount++;
    else if ($listingStarted && preg_match('/^Size = (\d+)$/', $outputLine, $lineParts) === 1) $unpackedSize = $unpackedSize + (int)$lineParts[1];
    else if ($listingStarted && ($outputLine === 'Folder = +' or preg_match('/^Attributes = D/', $outputLine) === 1)) $folderCount++;
    else if ($listingStarted && $outputLine === 'Encrypted = +') $encryptedCount++; }
  // / A status of one is a warning, such as data after the end of the archive, & the listing above it still stands.
  if ($toolRan && $archiveFormat !== '' && ($exitStatus === 0 or $exitStatus === 1 or $outputWasCut)) {
    $ArchiveFacts = array('Format' => $archiveFormat, 'Entries' => $entryCount, 'Files' => max(0, $entryCount - $folderCount), 'Folders' => $folderCount, 'UnpackedSize' => $unpackedSize, 'Encrypted' => $encryptedCount);
    if ($outputWasCut) $ArchiveNote = 'The archive lists more than Identify reads, so its counts are a minimum.'; }
  else if ($toolRan && stripos((string)$toolOutput, 'Wrong password') !== FALSE) $ArchiveNote = 'The archive encrypts its own listing, so what it holds was not counted.';
  else if ($SandboxAllowed) $ArchiveNote = '7-Zip could not read this archive.';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $toolRan, $outputWasCut, $listingStarted, $toolOutput, $outputLine, $archiveFormat, $exitStatus, $entryCount, $folderCount, $encryptedCount, $unpackedSize, $lineParts, $sevenZipBinary, $filePath, $scratchFolder);
  return array($SandboxAllowed, $ArchiveFacts, $ArchiveNote); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to decide which detail tool reads a file.
// / Accepts the MIME type file reported & the file's extension, in that order.
// / Returns Image, Media, Pdf, Archive or an empty string.
// / The MIME type decides whenever file gave one, because content outranks a name.
// / An SVG is text that may name other files, so it is never handed to ImageMagick.
// / With no MIME type the extension decides, which is weaker & is still better than nothing.
function identifyChooseDetail($mimeType, $fileExtension) {
  // / Set variables.
  global $EnableMemoryProtection;
  $DetailKind = '';
  $archiveTypes = $archiveExtensions = $imageExtensions = $mediaExtensions = array();
  $archiveTypes = array('application/zip', 'application/x-7z-compressed', 'application/x-rar', 'application/vnd.rar', 'application/x-tar', 'application/gzip', 'application/x-gzip', 'application/x-bzip2', 'application/x-xz', 'application/x-lzma', 'application/zstd', 'application/x-iso9660-image', 'application/x-cpio', 'application/vnd.ms-cab-compressed', 'application/x-archive');
  $archiveExtensions = array('zip', '7z', 'rar', 'tar', 'gz', 'tgz', 'bz2', 'xz', 'zst', 'iso', 'cab', 'cpio');
  $imageExtensions = array('png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp', 'tif', 'tiff', 'ico', 'heic', 'heif', 'avif', 'jxl', 'psd', 'tga', 'ppm', 'pgm', 'pbm');
  $mediaExtensions = array('mp3', 'wav', 'flac', 'ogg', 'oga', 'opus', 'm4a', 'aac', 'wma', 'mp4', 'm4v', 'mov', 'mkv', 'webm', 'avi', 'wmv', 'flv', 'mpg', 'mpeg', 'ts', '3gp');
  if ((string)$mimeType !== '') {
    if (strpos($mimeType, 'image/') === 0 && strpos($mimeType, 'svg') === FALSE) $DetailKind = 'Image';
    else if (strpos($mimeType, 'audio/') === 0 or strpos($mimeType, 'video/') === 0 or $mimeType === 'application/ogg') $DetailKind = 'Media';
    else if ($mimeType === 'application/pdf') $DetailKind = 'Pdf';
    else if (in_array($mimeType, $archiveTypes, TRUE)) $DetailKind = 'Archive'; }
  else if (in_array($fileExtension, $imageExtensions, TRUE)) $DetailKind = 'Image';
  else if (in_array($fileExtension, $mediaExtensions, TRUE)) $DetailKind = 'Media';
  else if ($fileExtension === 'pdf') $DetailKind = 'Pdf';
  else if (in_array($fileExtension, $archiveExtensions, TRUE)) $DetailKind = 'Archive';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $archiveTypes, $archiveExtensions, $imageExtensions, $mediaExtensions, $mimeType, $fileExtension);
  return $DetailKind; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to describe a length of time in hours, minutes & seconds.
// / Accepts a number of seconds. Returns it as H:MM:SS.SS.
function identifyDescribeDuration($durationSeconds) {
  // / Set variables.
  global $EnableMemoryProtection;
  $DurationText = '';
  $wholeSeconds = 0;
  $wholeSeconds = (int)floor(max(0, (float)$durationSeconds));
  $DurationText = sprintf('%d:%02d:%05.2f', intdiv($wholeSeconds, 3600), intdiv($wholeSeconds % 3600, 60), max(0, (float)$durationSeconds) - (intdiv($wholeSeconds, 60) * 60));
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $wholeSeconds, $durationSeconds);
  return $DurationText; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write the details a record holds as lines a person reads.
// / Accepts the record. Returns the lines.
// / The lines are what the report & the interface show, so both say the same thing in the same words.
function identifyDetailLines($fileRecord) {
  // / Set variables.
  global $EnableMemoryProtection;
  $DetailLines = array();
  $streamRecord = array();
  $streamNumber = 0;
  if (isset($fileRecord['Text'])) {
    $DetailLines[] = 'Lines: '.$fileRecord['Text']['Lines'].'.';
    $DetailLines[] = 'Line endings: '.$fileRecord['Text']['LineEndings'].'.';
    $DetailLines[] = 'Byte order mark: '.$fileRecord['Text']['ByteOrderMark'].'.';
    $DetailLines[] = 'Valid UTF-8: '.($fileRecord['Text']['ValidUtf8'] ? 'Yes' : 'No').'.'; }
  if (isset($fileRecord['Image'])) $DetailLines[] = 'Image: '.$fileRecord['Image']['Format'].', '.$fileRecord['Image']['Width'].' x '.$fileRecord['Image']['Height'].' pixels, '.$fileRecord['Image']['Colorspace'].', '.$fileRecord['Image']['Depth'].' bit, '.$fileRecord['Image']['Frames'].' frame(s).';
  if (isset($fileRecord['Media'])) {
    $DetailLines[] = 'Container: '.$fileRecord['Media']['Container'].'.';
    $DetailLines[] = 'Duration: '.identifyDescribeDuration($fileRecord['Media']['Duration']).($fileRecord['Media']['BitRate'] > 0 ? ', at '.round($fileRecord['Media']['BitRate'] / 1000).' kb/s' : '').'.';
    foreach ($fileRecord['Media']['Streams'] as $streamRecord) {
      $streamNumber++;
      $DetailLines[] = 'Stream '.$streamNumber.': '.$streamRecord['Type'].', '.$streamRecord['Codec'].($streamRecord['Width'] > 0 ? ', '.$streamRecord['Width'].' x '.$streamRecord['Height'] : '').($streamRecord['SampleRate'] > 0 ? ', '.$streamRecord['SampleRate'].' Hz' : '').($streamRecord['Channels'] > 0 ? ', '.$streamRecord['Channels'].' channel(s)' : '').'.'; } }
  if (isset($fileRecord['Pdf'])) {
    $DetailLines[] = 'PDF: version '.$fileRecord['Pdf']['Version'].', '.$fileRecord['Pdf']['Pages'].' page(s)'.($fileRecord['Pdf']['PageSize'] !== '' ? ', '.$fileRecord['Pdf']['PageSize'] : '').'.';
    $DetailLines[] = 'Encrypted: '.($fileRecord['Pdf']['Encrypted'] ? 'Yes' : 'No').'. JavaScript: '.($fileRecord['Pdf']['JavaScript'] ? 'Yes' : 'No').'.';
    if ($fileRecord['Pdf']['Title'] !== '') $DetailLines[] = 'Title: '.$fileRecord['Pdf']['Title'];
    if ($fileRecord['Pdf']['Producer'] !== '') $DetailLines[] = 'Producer: '.$fileRecord['Pdf']['Producer']; }
  if (isset($fileRecord['Archive'])) {
    $DetailLines[] = 'Archive: '.$fileRecord['Archive']['Format'].', '.$fileRecord['Archive']['Files'].' file(s) & '.$fileRecord['Archive']['Folders'].' folder(s), '.describeByteCount($fileRecord['Archive']['UnpackedSize']).' unpacked.';
    if ($fileRecord['Archive']['Encrypted'] > 0) $DetailLines[] = 'Encrypted entries: '.$fileRecord['Archive']['Encrypted'].'.'; }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $streamRecord, $streamNumber, $fileRecord);
  return $DetailLines; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to gather the details of one file from the tool able to read its format.
// / Accepts the record so far, the path, the scratch folder & the tools, in that order.
// / Returns whether the sandbox allowed every tool asked & the record with its details added, in that order.
// / A tool that is not installed adds a note saying what was not read, rather than failing the file.
// / The tools are keyed Image, Media, Pdf & Archive, & each holds a path or FALSE.
function identifyAddDetails($fileRecord, $filePath, $scratchFolder, $detailTools) {
  // / Set variables.
  global $EnableMemoryProtection;
  $SandboxAllowed = TRUE;
  $DetailedRecord = $fileRecord;
  $detailKind = $detailNote = '';
  $detailFacts = array();
  $toolNames = array('Image' => 'ImageMagick', 'Media' => 'FFprobe', 'Pdf' => 'PDFInfo', 'Archive' => '7-Zip');
  $detailKind = identifyChooseDetail($fileRecord['MimeType'], getExtension($fileRecord['Name']));
  if ($detailKind !== '' && (!isset($detailTools[$detailKind]) or $detailTools[$detailKind] === FALSE)) $DetailedRecord['Notes'][] = $toolNames[$detailKind].' is not installed, or is too old, so the details it reads were not gathered.';
  else if ($detailKind === 'Image') list ($SandboxAllowed, $detailFacts, $detailNote) = identifyAskImageMagick($detailTools['Image'], $filePath, $scratchFolder);
  else if ($detailKind === 'Media') list ($SandboxAllowed, $detailFacts, $detailNote) = identifyAskFFprobe($detailTools['Media'], $filePath, $scratchFolder);
  else if ($detailKind === 'Pdf') list ($SandboxAllowed, $detailFacts, $detailNote) = identifyAskPdfInfo($detailTools['Pdf'], $filePath, $scratchFolder);
  else if ($detailKind === 'Archive') list ($SandboxAllowed, $detailFacts, $detailNote) = identifyAskSevenZip($detailTools['Archive'], $filePath, $scratchFolder);
  if (!empty($detailFacts)) $DetailedRecord[$detailKind] = $detailFacts;
  if ($detailNote !== '') $DetailedRecord['Notes'][] = $detailNote;
  if (!empty($detailFacts) && $detailKind === 'Pdf' && $detailFacts['Encrypted']) $DetailedRecord['Notes'][] = 'The PDF is encrypted.';
  if (!empty($detailFacts) && $detailKind === 'Pdf' && $detailFacts['JavaScript']) $DetailedRecord['Notes'][] = 'The PDF carries JavaScript.';
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $detailKind, $detailNote, $detailFacts, $toolNames, $fileRecord, $filePath, $scratchFolder, $detailTools);
  return array($SandboxAllowed, $DetailedRecord); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to identify one file.
// / Accepts the file's name, its path, the scratch folder & the tools, in that order.
// / Returns whether it was read, whether the sandbox allowed every tool asked & the record, in that order.
// / The first value is this function's sanity check, because a file that could not be read has no record worth showing.
// / The record holds the name, the size, the time it changed, the hashes, what file says it is, whether its name agrees,
// / what it holds as text when it is text, the details its format's tool read, & notes in plain English.
// / Text facts are kept only for text, because counting the lines of a binary file means nothing.
function identifyOneFile($fileName, $filePath, $scratchFolder, $identifyTools) {
  // / Set variables.
  global $EnableMemoryProtection;
  $FileWasRead = FALSE;
  $SandboxAllowed = TRUE;
  $FileRecord = array();
  $factsWereRead = $fileAnswered = $detailsAllowed = FALSE;
  $fileHashes = $textFacts = $expectedExtensions = $fileFacts = array();
  $description = $mimeType = $charset = $fileExtension = $extensionVerdict = '';
  $fileFacts = @stat($filePath);
  list ($factsWereRead, $fileHashes, $textFacts) = identifyReadFacts($filePath);
  if ($factsWereRead && is_array($fileFacts)) {
    $FileWasRead = TRUE;
    if ($identifyTools['File'] !== FALSE) list ($SandboxAllowed, $fileAnswered, $description, $mimeType, $charset, $expectedExtensions) = identifyAskFile($identifyTools['File'], $filePath, $scratchFolder);
    $fileExtension = getExtension($fileName);
    // / A verdict needs both a name & an answer from file, & anything less is unknown rather than a mismatch.
    $extensionVerdict = 'unknown';
    if ($fileExtension !== '' && !empty($expectedExtensions)) $extensionVerdict = in_array($fileExtension, $expectedExtensions, TRUE) ? 'yes' : 'no';
    $FileRecord = array('Name' => $fileName, 'Size' => (int)$fileFacts['size'], 'SizeText' => describeByteCount((int)$fileFacts['size']), 'Modified' => date('Y-m-d H:i', (int)$fileFacts['mtime']),
      'Description' => $description, 'MimeType' => $mimeType, 'Charset' => $charset, 'ExtensionMatches' => $extensionVerdict, 'ExpectedExtensions' => $expectedExtensions,
      'SHA256' => $fileHashes['SHA256'], 'SHA1' => $fileHashes['SHA1'], 'MD5' => $fileHashes['MD5'], 'Details' => array(), 'Notes' => array());
    if ($identifyTools['File'] === FALSE) $FileRecord['Notes'][] = 'File is not installed, or is too old, so what this file holds was judged by its name alone.';
    else if (!$SandboxAllowed) $FileRecord['Notes'][] = 'The sandbox is unavailable, so no tool read this file.';
    else if (!$fileAnswered) $FileRecord['Notes'][] = 'File could not say what this file holds.';
    if ($extensionVerdict === 'no') $FileRecord['Notes'][] = 'The name ends in .'.$fileExtension.', & what it holds is usually named .'.$expectedExtensions[0].'.';
    // / Text is what file calls text, or what is valid UTF-8 & holds no null byte when file could not be asked.
    // / A null byte is valid UTF-8, so validity alone would call a file of zeros text.
    if (strpos($mimeType, 'text/') === 0 or ($charset !== '' && $charset !== 'binary') or ($mimeType === '' && $fileAnswered === FALSE && $textFacts['ValidUtf8'] && !$textFacts['NullBytes'])) {
      $FileRecord['Text'] = array('ByteOrderMark' => $textFacts['ByteOrderMark'], 'LineEndings' => $textFacts['LineEndings'], 'Lines' => $textFacts['Lines'], 'ValidUtf8' => $textFacts['ValidUtf8']);
      if (!$textFacts['ValidUtf8'] && ($charset === 'utf-8' or $charset === 'us-ascii')) $FileRecord['Notes'][] = 'Part of this text is not valid UTF-8.';
      if ($textFacts['LineEndings'] === 'Mixed') $FileRecord['Notes'][] = 'The text mixes more than one kind of line ending.'; }
    // / A tool is never asked about a file when the sandbox has already refused, because the answer would be the same.
    if ($SandboxAllowed) list ($detailsAllowed, $FileRecord) = identifyAddDetails($FileRecord, $filePath, $scratchFolder, $identifyTools);
    if ($SandboxAllowed && !$detailsAllowed) {
      $SandboxAllowed = FALSE;
      $FileRecord['Notes'][] = 'The sandbox is unavailable, so the details of this file were not read.'; }
    $FileRecord['Details'] = identifyDetailLines($FileRecord); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $factsWereRead, $fileAnswered, $detailsAllowed, $fileHashes, $textFacts, $expectedExtensions, $fileFacts, $description, $mimeType, $charset, $fileExtension, $extensionVerdict, $fileName, $filePath, $scratchFolder, $identifyTools);
  return array($FileWasRead, $SandboxAllowed, $FileRecord); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write the records as a report a person reads.
// / Accepts the records & the time the report describes, in that order. Returns the report.
// / Every value in a record is plain text by now, so the report needs no escaping.
// / Its lines end in CRLF, which every text editor on every system reads.
function identifyRenderReport($fileRecords, $reportTime) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ReportText = '';
  $reportLines = $fileRecord = array();
  $detailLine = $noteLine = '';
  $ruleLine = str_repeat('-', 80);
  $reportLines = array('Identify Report', 'Written '.date('Y-m-d H:i', (int)$reportTime).' for '.count($fileRecords).' file(s).', '');
  foreach ($fileRecords as $fileRecord) {
    array_push($reportLines, $ruleLine, $fileRecord['Name'], $ruleLine,
      str_pad('Size:', 22).$fileRecord['SizeText'].' ('.$fileRecord['Size'].' bytes)', str_pad('Modified:', 22).$fileRecord['Modified'],
      str_pad('Type:', 22).($fileRecord['Description'] !== '' ? $fileRecord['Description'] : 'Unknown'), str_pad('MIME type:', 22).($fileRecord['MimeType'] !== '' ? $fileRecord['MimeType'] : 'Unknown'),
      str_pad('Character set:', 22).($fileRecord['Charset'] !== '' ? $fileRecord['Charset'] : 'Unknown'), str_pad('Extension matches:', 22).ucfirst($fileRecord['ExtensionMatches']),
      str_pad('SHA-256:', 22).$fileRecord['SHA256'], str_pad('SHA-1:', 22).$fileRecord['SHA1'], str_pad('MD5:', 22).$fileRecord['MD5']);
    foreach ($fileRecord['Details'] as $detailLine) $reportLines[] = $detailLine;
    foreach ($fileRecord['Notes'] as $noteLine) $reportLines[] = 'Note: '.$noteLine;
    $reportLines[] = ''; }
  $ReportText = implode("\r\n", $reportLines)."\r\n";
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $reportLines, $fileRecord, $detailLine, $noteLine, $ruleLine, $fileRecords, $reportTime);
  return $ReportText; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to write a report into the session under the name asked for, or the next free one.
// / Accepts the report & the name asked for without its extension, in that order.
// / Returns whether it was written & the name it was given, in that order.
// / A taken name is given the next free one, as every conversion output is, so nothing a user already has is replaced.
// / The file is created exclusively, so a name taken between choosing it & writing it fails rather than being followed or replaced.
function identifyWriteReport($reportText, $requestedName) {
  // / Set variables.
  global $ConvertDir, $EnableMemoryProtection;
  $ReportWasWritten = FALSE;
  $ReportName = '';
  $baseName = $reportPath = $freePath = $freeName = '';
  $nameIsSanitized = $freeNameWasFound = FALSE;
  $reportHandle = FALSE;
  $bytesWritten = 0;
  list ($baseName, $nameIsSanitized) = sanitize((string)$requestedName, TRUE);
  // / A name that sanitizes to nothing is given the default rather than refused, because the user asked for a report.
  if (!$nameIsSanitized or trim((string)$baseName) === '') $baseName = 'User_Identify_Report';
  $reportPath = (string)$ConvertDir.$baseName.'.txt';
  clearstatcache(TRUE, $reportPath);
  if (file_exists($reportPath) or is_link($reportPath)) list ($freeNameWasFound, $freePath, $freeName) = findFreeOutputName((string)$ConvertDir, $baseName, 'txt');
  else list ($freeNameWasFound, $freePath, $freeName) = array(TRUE, $reportPath, $baseName.'.txt');
  if (!$freeNameWasFound) errorEntry('No free name was found for the Identify report, so it was not written!', 37001, FALSE);
  else {
    $reportHandle = @fopen($freePath, 'xb');
    if ($reportHandle === FALSE) errorEntry('The Identify report could not be created!', 37002, FALSE);
    else {
      $bytesWritten = (int)@fwrite($reportHandle, $reportText);
      fclose($reportHandle);
      @chmod($freePath, 0600);
      if ($bytesWritten === strlen($reportText)) {
        $ReportWasWritten = TRUE;
        $ReportName = $freeName; }
      else {
        @unlink($freePath);
        errorEntry('The Identify report could not be written in full, so it was removed!', 37003, FALSE); } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $baseName, $reportPath, $freePath, $freeName, $nameIsSanitized, $freeNameWasFound, $reportHandle, $bytesWritten, $reportText, $requestedName);
  return array($ReportWasWritten, $ReportName); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to identify a selection of files, & write a report of them when one is asked for.
// / Accepts the selected files, the name asked for the report, the extension & whether to write a report, in that order.
// / Returns an operation boolean, an errors boolean & what it produced, in that order.
// / What it produced holds Records, one for each file identified, & Report, the name of the report or an empty string.
// / The extension is accepted because every operation pipeline is called the same way, & a report is always txt.
// / A file that cannot be identified is skipped & reported, so a bad name costs that file & nothing else.
// / The operation succeeds when at least one file was identified & any report asked for was written.
// / Every tool is located once & verified once, so the version verified is the version that runs on every file.
function identifyFiles($FilesToIdentify, $UserFilename, $UserExtension, $WriteReport) {
  // / Set variables.
  global $Verbose, $ConvertDir, $DirSep, $EnableMemoryProtection;
  $OperationSuccessful = $OperationErrors = FALSE;
  $IdentifyResult = array('Records' => array(), 'Report' => '');
  $file = $pathname = $oldPathname = $oldExtension = $newPathname = $verifiedFilename = $scratchFolder = $reportName = '';
  $fileIsVerified = $fileWasRead = $sandboxAllowed = $reportWasWritten = $reportIsWanted = $sandboxWasRefused = FALSE;
  $fileRecord = $identifyTools = array();
  $reportIsWanted = ($WriteReport === TRUE or $WriteReport === 1 or $WriteReport === '1');
  // / Locate & verify every tool before anything is read. Each one is optional.
  $identifyTools = array('File' => verifiedToolPath('File'), 'Image' => verifiedToolPath('ImageMagick'), 'Media' => verifiedToolPath('FFprobe'), 'Pdf' => verifiedToolPath('PDFInfo'), 'Archive' => verifiedToolPath('7-Zip'));
  // / Every tool writes only into this folder, inside the session, & it is removed when the files have been identified.
  $scratchFolder = rtrim((string)$ConvertDir, $DirSep).$DirSep.'identify-'.bin2hex(random_bytes(6));
  if (!@mkdir($scratchFolder, 0700)) {
    $OperationErrors = TRUE;
    errorEntry('The Identify pipeline could not create a folder to work in, so no file was identified!', 37000, FALSE); }
  else {
    if (!is_array($FilesToIdentify)) $FilesToIdentify = array($FilesToIdentify);
    foreach ($FilesToIdentify as $file) {
      // / The name verifyFile returned for the file before this one is shredded first, as redeclare() would, because a list() cannot pass through redeclare().
      purgeSensitiveMemory($EnableMemoryProtection, $verifiedFilename);
      // / Nothing is staged & nothing is cleaned, because identifying a file reads it where it is.
      list ($fileIsVerified, $pathname, $oldPathname, $oldExtension, $newPathname, $verifiedFilename) = verifyFile($file, '', '', FALSE, FALSE, TRUE);
      if (!$fileIsVerified or !is_file($oldPathname) or is_link($oldPathname)) {
        $OperationErrors = TRUE;
        warningEntry('A file asked to be identified could not be verified, so it was skipped.');
        continue; }
      list ($fileWasRead, $sandboxAllowed, $fileRecord) = identifyOneFile(basename($oldPathname), $oldPathname, $scratchFolder, $identifyTools);
      if (!$sandboxAllowed) $sandboxWasRefused = TRUE;
      if (!$fileWasRead) {
        $OperationErrors = TRUE;
        warningEntry('The file '.basename($oldPathname).' could not be read, so it was not identified.');
        continue; }
      if ($Verbose) logEntry('Identified '.basename($oldPathname).'.');
      $IdentifyResult['Records'][] = $fileRecord; }
    // / A refused sandbox is reported once for the whole selection rather than once for every tool.
    if ($sandboxWasRefused) {
      $OperationErrors = TRUE;
      errorEntry('Bubblewrap is missing or non functional, so no tool could read the files being identified!', 37004, FALSE); }
    if ($reportIsWanted && count($IdentifyResult['Records']) > 0) {
      list ($reportWasWritten, $reportName) = identifyWriteReport(identifyRenderReport($IdentifyResult['Records'], time()), $UserFilename);
      if ($reportWasWritten) $IdentifyResult['Report'] = $reportName;
      else $OperationErrors = TRUE; }
    $OperationSuccessful = (count($IdentifyResult['Records']) > 0 && (!$reportIsWanted or $reportWasWritten));
    // / Nothing is meant to be in the folder, & anything a tool left there goes with it.
    if (function_exists('cleanFiles')) cleanFiles($scratchFolder, array($scratchFolder));
    @rmdir($scratchFolder); }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $file, $pathname, $oldPathname, $oldExtension, $newPathname, $verifiedFilename, $scratchFolder, $reportName, $fileIsVerified, $fileWasRead, $sandboxAllowed, $reportWasWritten, $reportIsWanted, $sandboxWasRefused, $fileRecord, $identifyTools, $FilesToIdentify, $UserFilename, $UserExtension, $WriteReport);
  return array($OperationSuccessful, $OperationErrors, $IdentifyResult); }
// / -----------------------------------------------------------------------------------
?>
