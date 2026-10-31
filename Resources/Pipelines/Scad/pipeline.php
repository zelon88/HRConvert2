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
// / v3.9.8.
// / This file is the converter for the Scad pipeline. It is loaded by pipelineCore.php
// / ONLY when a Scad conversion is about to be dispatched to it, so a request that
// / converts something else never parses a line of it.
// / Error block 28000 through 28009 belongs to this pipeline. Those numbers came with the code when it
// / moved out of convertCore.php & they did not change, because operators have read them.
// / FOUR HELPERS MOVED HERE WITH THE CONVERTER, because convertCore.php had no other
// / caller for any of them. A helper only stays behind when something outside this pipeline
// / still needs it.
// /   sanitizeSCAD          reads OpenSCAD source & reports every call it finds
// /   resolveSCADInclude    resolves an include against what the same user uploaded
// /   rectifySCAD           rewrites the source using what the Sanitizer found
// /   sanitizeAllSCADUploads  applies that to every file in the session
// / All four are knowledge about what OpenSCAD source MEANS, which is this pipeline's job.
// / The policy files did not move & must not.
// / verifyOpenScadPolicy() & openScadApparmorContents() stay in convertCore.php.
// / fixManagedPermissions() & validateOperatingEnvironment() write & check that policy
// / during installation, whether or not this pipeline is installed at all. A sandbox
// / profile is part of the operating environment rather than part of a converter.
// / This pipeline asks for OpenSCAD through verifiedToolPath(), which answers from the
// / manifest, & calls sandboxCommand(), limitCommand(), verifyBwrap(), verifyRequiredDirs(),
// / cleanFiles() & is_dir_empty(), which remain in convertCore.php because the core uses
// / them elsewhere.
// / See Documentation/ABOUT_PIPELINE_COMPONENTS.txt for the contracts this file obeys.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by the core.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! HRConvert2-34000, A pipeline converter cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A function to resolve a single OpenSCAD file reference against the users uploaded files.
// / OpenSCAD references frequently carry a directory structure that does not exist here.
// / A reference like <../lib/threads.scad> is matched on its basename alone, threads.scad.
// / Only files the user actually uploaded to this session are eligible for a match.
// / The path returned points at the SANITIZED copy in ScadTemp, never at the users original.
// / OpenSCAD resolves a relative include against the directory of the file it is reading.
// / The sanitized copy lives in ScadTemp, so every reference it holds must point there too.
// / Returns an empty string when nothing matched, & the caller must comment the reference out.
function resolveSCADInclude($scadReference, $sessionFiles) {
  // / Set variables.
  global $ScadTemp, $DirSep, $EnableMemoryProtection;
  $ResolvedFile = $sessionFile = '';
  $referenceIsUsable = FALSE;
  $referenceBase = strtolower(trim(basename(str_replace('\\', '/', trim($scadReference)))));
  // / A reference with no usable filename can never resolve to anything.
  // / Only .scad sources may ever be resolved as an include or a use.
  if ($referenceBase !== '' && $referenceBase !== '.' && $referenceBase !== '..' && getExtension($referenceBase) === 'scad') $referenceIsUsable = TRUE;
  if ($referenceIsUsable) {
    foreach ($sessionFiles as $sessionFile) {
      if (strtolower(basename($sessionFile)) === $referenceBase) {
        $ResolvedFile = $ScadTemp.$DirSep.basename($sessionFile);
        break; } } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $scadReference, $sessionFiles, $referenceBase, $sessionFile, $referenceIsUsable);
  return $ResolvedFile; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to find every file reading call in a block of OpenSCAD source.
// / The source is walked once, character by character, carrying comment & string state.
// / One pass is required rather than two, because whichever delimiter appears first wins.
// / A line comment containing the characters that open a block comment does NOT open one.
// / A block comment containing the characters that open a line comment does NOT open one.
// / Stripping one kind before the other gets both of those backwards & swallows real code.
// / Comments & strings are removed because a keyword inside either is not a call.
// / An angle bracket path is removed for the same reason. A directory name is not a call.
// / A keyword is only counted as a call when BOTH of the following hold.
// / The character before it must not be a letter, a digit or an underscore.
// / That is what stops house_width & diffuser_height from counting as a use.
// / The next meaningful character must be an opening bracket or an angle bracket.
// / That is what stops include_lid from counting as an include.
// / A comment is a token separator in OpenSCAD. Not a terminator.
// / The main loop below treats a comment as the end of live code, which is correct there.
// / The lookahead treats a comment as whitespace, which is correct there & is NOT the same rule.
// / An earlier version applied the main loop rule in the lookahead & concluded that
// / surface/**/("file") was not a call. OpenSCAD reads that file. It was reported as a bypass.
// / Whitespace & comments are therefore both skipped while looking for the bracket, & again
// / while reading the reference, because a comment may also sit between the bracket & the quote.
// / The boundary is the operating system sandbox in convertSCAD().
// / This function exists so ordinary files render predictably.
// / Returns one record per call found, carrying the keyword, the line it started on & the
// / raw text of the reference where one could be read.
function sanitizeSCAD($scadContents) {
  // / Set variables.
  global $EnableMemoryProtection;
  $ScadCalls = array();
  $keywords = array();
  $keyword = $currentChar = $nextChar = $priorChar = $lookaheadChar = $peekChar = $referenceText = '';
  $sourceLength = $charIndex = $lineNumber = $keywordLength = $lookaheadIndex = $referenceStart = 0;
  $callEnd = $scanIndex = $parenDepth = 0;
  $scanChar = $scanNext = '';
  $scanInString = $scanInLineComment = $scanInBlockComment = FALSE;
  $inLineComment = $inBlockComment = $inString = $isCall = FALSE;
  // / Every keyword that causes OpenSCAD to read a file.
  // / The longest forms are listed first so a longer match is preferred over a shorter one.
  // / import_stl would otherwise match as import & leave stl looking like an identifier.
  $keywords = array('dxf_linear_extrude', 'dxf_rotate_extrude', 'import_stl', 'import_dxf', 'import_off', 'dxf_cross', 'surface', 'include', 'import', 'dxf_dim', 'use');
  $sourceLength = strlen($scadContents);
  $lineNumber = 1;
  for ($charIndex = 0; $charIndex < $sourceLength; $charIndex++) {
    $currentChar = $scadContents[$charIndex];
    $nextChar = ($charIndex + 1 < $sourceLength) ? $scadContents[$charIndex + 1] : '';
    // / Track the line number for every newline, wherever it appears.
    // / The user has to be told which line a reference was on & a stream has no lines.
    if ($currentChar === "\n") $lineNumber++;
    // / A line comment runs to the end of the line & nothing inside it is code.
    if ($inLineComment) {
      if ($currentChar === "\n") $inLineComment = FALSE;
      continue; }
    // / A block comment runs to the first closing sequence & does not nest.
    // / The character after that sequence is live code again, even on the same line.
    // / That single property is what the reported block comment bypass depended on.
    if ($inBlockComment) {
      if ($currentChar === '*' && $nextChar === '/') {
        $inBlockComment = FALSE;
        $charIndex++; }
      continue; }
    // / A string literal may contain anything, including text that reads like a call.
    if ($inString) {
      if ($currentChar === '\\') {
        $charIndex++;
        continue; }
      if ($currentChar === '"') $inString = FALSE;
      continue; }
    // / Not inside anything, so check whether this character opens something.
    if ($currentChar === '/' && $nextChar === '/') {
      $inLineComment = TRUE;
      $charIndex++;
      continue; }
    if ($currentChar === '/' && $nextChar === '*') {
      $inBlockComment = TRUE;
      $charIndex++;
      continue; }
    if ($currentChar === '"') {
      $inString = TRUE;
      continue; }
    // / Live code. Test every keyword at this position.
    foreach ($keywords as $keyword) {
      $keywordLength = strlen($keyword);
      if (strtolower(substr($scadContents, $charIndex, $keywordLength)) !== $keyword) continue;
      // / The character before the keyword must not make this part of a longer identifier.
      $priorChar = ($charIndex > 0) ? $scadContents[$charIndex - 1] : ' ';
      if (ctype_alnum($priorChar) or $priorChar === '_') continue;
      // / Look for the bracket that would make this a call.
      // / Whitespace is skipped. Comments are skipped, because OpenSCAD separates tokens with both.
      // / A newline is whitespace here, so a call split across any number of lines is still found.
      $isCall = FALSE;
      $referenceText = '';
      for ($lookaheadIndex = $charIndex + $keywordLength; $lookaheadIndex < $sourceLength; $lookaheadIndex++) {
        $lookaheadChar = $scadContents[$lookaheadIndex];
        if ($lookaheadChar === ' ' or $lookaheadChar === "\t" or $lookaheadChar === "\n" or $lookaheadChar === "\r") continue;
        // / A comment sitting between the keyword & its bracket separates tokens.
        // / It does not end the statement & must not end this search.
        if ($lookaheadChar === '/' && ($lookaheadIndex + 1) < $sourceLength) {
          $peekChar = $scadContents[$lookaheadIndex + 1];
          if ($peekChar === '/') {
            $lookaheadIndex = $lookaheadIndex + 2;
            while ($lookaheadIndex < $sourceLength && $scadContents[$lookaheadIndex] !== "\n") $lookaheadIndex++;
            continue; }
          if ($peekChar === '*') {
            $lookaheadIndex = $lookaheadIndex + 2;
            while (($lookaheadIndex + 1) < $sourceLength && !($scadContents[$lookaheadIndex] === '*' && $scadContents[$lookaheadIndex + 1] === '/')) $lookaheadIndex++;
            $lookaheadIndex = $lookaheadIndex + 1;
            continue; } }
        if ($lookaheadChar === '(' or $lookaheadChar === '<') $isCall = TRUE;
        break; }
      if (!$isCall) continue;
      // / Read the reference itself so the caller can resolve or report it.
      // / An angle bracket form runs to the closing bracket & cannot contain a comment.
      // / OpenSCAD throws a parser error on include/**/<file>, so that form is a dead end.
      $referenceStart = $lookaheadIndex;
      if ($scadContents[$referenceStart] === '<') {
        $lookaheadIndex++;
        while ($lookaheadIndex < $sourceLength && $scadContents[$lookaheadIndex] !== '>') {
          $referenceText .= $scadContents[$lookaheadIndex];
          $lookaheadIndex++; } }
      // / A bracket form may carry a comment between the bracket & the quote.
      // / surface(/*x*/"file") is a live call & the quote must still be found.
      else {
        $lookaheadIndex++;
        while ($lookaheadIndex < $sourceLength && $scadContents[$lookaheadIndex] !== '"' && $scadContents[$lookaheadIndex] !== ')') {
          if ($scadContents[$lookaheadIndex] === '/' && ($lookaheadIndex + 1) < $sourceLength) {
            $peekChar = $scadContents[$lookaheadIndex + 1];
            if ($peekChar === '/') {
              $lookaheadIndex = $lookaheadIndex + 2;
              while ($lookaheadIndex < $sourceLength && $scadContents[$lookaheadIndex] !== "\n") $lookaheadIndex++;
              continue; }
            if ($peekChar === '*') {
              $lookaheadIndex = $lookaheadIndex + 2;
              while (($lookaheadIndex + 1) < $sourceLength && !($scadContents[$lookaheadIndex] === '*' && $scadContents[$lookaheadIndex + 1] === '/')) $lookaheadIndex++;
              $lookaheadIndex = $lookaheadIndex + 2;
              continue; } }
          $lookaheadIndex++; }
        if ($lookaheadIndex < $sourceLength && $scadContents[$lookaheadIndex] === '"') {
          $lookaheadIndex++;
          while ($lookaheadIndex < $sourceLength && $scadContents[$lookaheadIndex] !== '"') {
            $referenceText .= $scadContents[$lookaheadIndex];
            $lookaheadIndex++; } } }
      // / Measure where the whole call ends, so a caller can replace exactly its characters.
      // / A line is the wrong unit to work in. A block comment opened on an earlier line may
      // / close part way along the line holding a call, & a comment marker written at the start
      // / of such a line is swallowed by that comment while the call after the closing sequence
      // / stays live. Offsets cannot be fooled that way, because the lexer only ever reports a
      // / call it found in code state & the span it reports starts there.
      $callEnd = $lookaheadIndex;
      // / An angle bracket form ends at its closing bracket.
      if ($scadContents[$referenceStart] === '<') $callEnd = $lookaheadIndex + 1;
      // / A bracket form ends at the bracket closing the one that opened the call. Nesting,
      // / strings & comments among the arguments are all skipped on the way there.
      else {
        $scanIndex = ($lookaheadIndex < $sourceLength && $scadContents[$lookaheadIndex] === '"') ? $lookaheadIndex + 1 : $lookaheadIndex;
        $parenDepth = 1;
        $scanInString = $scanInLineComment = $scanInBlockComment = FALSE;
        $callEnd = $sourceLength;
        for (; $scanIndex < $sourceLength; $scanIndex++) {
          $scanChar = $scadContents[$scanIndex];
          $scanNext = ($scanIndex + 1 < $sourceLength) ? $scadContents[$scanIndex + 1] : '';
          if ($scanInLineComment) {
            if ($scanChar === "\n") $scanInLineComment = FALSE;
            continue; }
          if ($scanInBlockComment) {
            if ($scanChar === '*' && $scanNext === '/') {
              $scanInBlockComment = FALSE;
              $scanIndex++; }
            continue; }
          if ($scanInString) {
            if ($scanChar === '\\') {
              $scanIndex++;
              continue; }
            if ($scanChar === '"') $scanInString = FALSE;
            continue; }
          if ($scanChar === '/' && $scanNext === '/') {
            $scanInLineComment = TRUE;
            $scanIndex++;
            continue; }
          if ($scanChar === '/' && $scanNext === '*') {
            $scanInBlockComment = TRUE;
            $scanIndex++;
            continue; }
          if ($scanChar === '"') {
            $scanInString = TRUE;
            continue; }
          if ($scanChar === '(') $parenDepth++;
          if ($scanChar === ')') {
            $parenDepth--;
            if ($parenDepth === 0) {
              $callEnd = $scanIndex + 1;
              break; } } } }
      // / One record per call found. The line number is where the KEYWORD started.
      array_push($ScadCalls, array(
        'Keyword'     => $keyword,
        'Line'        => $lineNumber,
        'Start'       => $charIndex,
        'End'         => $callEnd,
        'Reference'   => trim($referenceText),
        'IsAngleForm' => ($scadContents[$referenceStart] === '<')));
      // / Advance past the keyword so its own text cannot match a shorter keyword inside it.
      $charIndex = $charIndex + $keywordLength - 1;
      break; } }
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $callEnd, $scanIndex, $parenDepth, $scanChar, $scanNext, $scanInString, $scanInLineComment, $scanInBlockComment, $scadContents, $keywords, $keyword, $currentChar, $nextChar, $priorChar, $lookaheadChar, $peekChar, $referenceText, $sourceLength, $charIndex, $lineNumber, $keywordLength, $lookaheadIndex, $referenceStart, $inLineComment, $inBlockComment, $inString, $isCall);
  return $ScadCalls; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to rewrite OpenSCAD source using what the Sanitizer found.
// / The Sanitizer reports the exact characters each call occupies. This function replaces
// / those characters & never works on whole lines.
// /
// / An earlier version commented out the line a call started on. A line is the wrong unit.
// / A block comment opened on an earlier line may close part way along the line holding the
// / call, & a comment marker written at the start of such a line is swallowed by that
// / comment while the call sitting after the closing sequence stays live. That was reported
// / as a working bypass & is the reason this function now edits by position.
// /
// / A removed call is overwritten with whitespace of exactly the same length. Every newline
// / inside the span is kept, so every line below a removal keeps the number it had & the
// / renderer reports a position the user can still find in their own file.
// / A resolved include is replaced by a reference to the staged copy, written in place.
// / Nothing is commented out, because writing a comment is what went wrong before.
// /
// / Replacements are applied from the end of the source backwards, so an earlier offset is
// / still correct after a later replacement has changed the length of the text.
// /
// / The result is handed back to the Sanitizer before it is returned. Anything the Sanitizer
// / can still see is a call that survived, & the caller refuses to render rather than trust
// / this function to have been thorough. A rewrite that cannot prove itself is a failure.
// /
// / An include or use may instead be rewritten to point at another source the same user
// / uploaded, when config.php enables that. Everything else is always removed.
// / Geometry & heightmap reads are never resolved & never rewritten.
function rectifySCAD($scadContents, $sessionFiles, $resolveIncludes) {
  // / Set variables.
  global $Verbose, $EnableMemoryProtection;
  $RectifiedSCAD = '';
  $ReferencesFound = $ReferencesResolved = $ReferencesRemoved = 0;
  $RectificationVerified = FALSE;
  $scadCalls = $plannedEdits = $plannedEdit = $survivingCalls = array();
  $scadCall = $resolvedPath = $replacementText = $spanText = '';
  $spanIndex = $spanLength = 0;
  // / Find every call in the source before touching a single character.
  $scadCalls = sanitizeSCAD($scadContents);
  $ReferencesFound = count($scadCalls);
  // / Decide what happens to each call & remember the span it occupies.
  foreach ($scadCalls as $scadCall) {
    $resolvedPath = '';
    // / Only an angle bracket include or use is ever eligible for resolution.
    // / A geometry or heightmap read is always removed, whatever config.php says.
    if ($resolveIncludes && $scadCall['IsAngleForm'] && ($scadCall['Keyword'] === 'include' or $scadCall['Keyword'] === 'use')) $resolvedPath = resolveSCADInclude($scadCall['Reference'], $sessionFiles);
    if ($resolvedPath !== '') {
      $ReferencesResolved++;
      // / The reference is written as a BARE FILENAME rather than as the absolute path that
      // / resolveSCADInclude() returned. Every sanitized copy lives in one directory, & the
      // / render runs with that directory as its working directory, so a bare name resolves.
      // / An absolute path would not, because inside the sandbox that directory is mounted
      // / somewhere else entirely & the real path does not exist at all.
      $replacementText = $scadCall['Keyword'].' <'.basename($resolvedPath).'>'; }
    else {
      $ReferencesRemoved++;
      // / Whitespace of the same length, keeping every line ending the span contained.
      $replacementText = '';
      $spanText = substr($scadContents, (int)$scadCall['Start'], (int)$scadCall['End'] - (int)$scadCall['Start']);
      $spanLength = strlen($spanText);
      for ($spanIndex = 0; $spanIndex < $spanLength; $spanIndex++) $replacementText .= ($spanText[$spanIndex] === "\n" or $spanText[$spanIndex] === "\r") ? $spanText[$spanIndex] : ' ';
      if ($Verbose) logEntry('Removing the '.$scadCall['Keyword'].' reference on line '.$scadCall['Line'].' of an uploaded OpenSCAD source.'); }
    array_push($plannedEdits, array('Start' => (int)$scadCall['Start'], 'End' => (int)$scadCall['End'], 'Text' => $replacementText)); }
  // / Apply the edits from the end backwards so every offset ahead of one stays correct.
  $RectifiedSCAD = $scadContents;
  foreach (array_reverse($plannedEdits) as $plannedEdit) $RectifiedSCAD = substr_replace($RectifiedSCAD, $plannedEdit['Text'], $plannedEdit['Start'], $plannedEdit['End'] - $plannedEdit['Start']);
  // / Prove it. Every call the Sanitizer can still find must be one this function wrote,
  // / which is a resolved include & nothing else. Anything more means a call survived.
  $survivingCalls = sanitizeSCAD($RectifiedSCAD);
  $RectificationVerified = (count($survivingCalls) === $ReferencesResolved);
  if (!$RectificationVerified) warningEntry('An OpenSCAD file reference survived sanitization. '.count($survivingCalls).' call(s) remain & '.$ReferencesResolved.' resolved include(s) were expected. The source will not be rendered.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $scadContents, $sessionFiles, $resolveIncludes, $scadCalls, $scadCall, $plannedEdits, $plannedEdit, $survivingCalls, $resolvedPath, $replacementText, $spanText, $spanIndex, $spanLength);
  return array($RectifiedSCAD, $ReferencesFound, $ReferencesResolved, $ReferencesRemoved, $RectificationVerified); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to sanitize EVERY OpenSCAD source the user uploaded, in one flat pass.
// / Nothing here follows a reference from one file to another.
// / Following references would require cycle detection, because a.scad may include b.scad
// / while b.scad includes a.scad, & both are legitimate files the user uploaded.
// / Sanitizing the whole upload set instead means every file OpenSCAD can possibly reach
// / has already been through the filter, & no traversal is ever performed.
// / The set is closed & bounded by whatever the user was willing to upload, so no depth
// / or width budget is required the way the stream inspector needs one.
// / Every sanitized copy is written to ScadTemp & the users originals are never modified.
function sanitizeAllSCADUploads() {
  // / Set variables.
  global $Verbose, $ConvertDir, $ScadTemp, $DirSep, $AllowSCADIncludeResolution, $EnableMemoryProtection;
  $AllSanitized = TRUE;
  $FilesSanitized = $ReferencesFound = $ReferencesResolved = $ReferencesRemoved = 0;
  $fileFound = $fileResolved = $fileRemoved = $bytesWritten = 0;
  $fileVerified = FALSE;
  $sessionFiles = array();
  $sessionFile = $scadContents = $sanitizedSCAD = $sanitizedPath = '';
  // / Gather every file the user uploaded to this session.
  $sessionFiles = getFiles($ConvertDir);
  foreach ($sessionFiles as $sessionFile) {
    // / Only OpenSCAD sources are sanitized. Everything else is left alone entirely.
    if (getExtension($sessionFile) !== 'scad') continue;
    $scadContents = @file_get_contents($ConvertDir.$sessionFile);
    if ($scadContents === FALSE) {
      $AllSanitized = FALSE;
      errorEntry('Could not read the OpenSCAD source file '.$sessionFile.'!', 27006, FALSE);
      continue; }
    // / Neutralize every file reading primitive in this source.
    list ($sanitizedSCAD, $fileFound, $fileResolved, $fileRemoved, $fileVerified) = rectifySCAD($scadContents, $sessionFiles, $AllowSCADIncludeResolution);
    // / A rewrite that cannot prove every reference is gone is not staged at all. Rendering
    // / from it would mean trusting the rewrite rather than checking it, which is how the
    // / block comment bypass stayed invisible. Nothing is written & the render is refused.
    if (!$fileVerified) {
      $AllSanitized = FALSE;
      errorEntry('A file reference survived sanitization of the OpenSCAD source '.$sessionFile.'!', 27009, FALSE);
      continue; }
    $ReferencesFound = $ReferencesFound + $fileFound;
    $ReferencesResolved = $ReferencesResolved + $fileResolved;
    $ReferencesRemoved = $ReferencesRemoved + $fileRemoved;
    // / Write the sanitized copy under the same basename so a resolved reference finds it.
    $sanitizedPath = $ScadTemp.$DirSep.basename($sessionFile);
    $bytesWritten = file_put_contents($sanitizedPath, $sanitizedSCAD, LOCK_EX);
    if ($bytesWritten === strlen($sanitizedSCAD)) $FilesSanitized++;
    else {
      $AllSanitized = FALSE;
      errorEntry('Could not stage the sanitized OpenSCAD source '.$sessionFile.'!', 27001, FALSE); } }
  // / A reference removed from an uploaded source is worth an operator seeing at any verbosity.
  if ($ReferencesRemoved > 0) warningEntry('OpenSCAD Sanitization removed '.$ReferencesRemoved.' file reference(s) across '.$FilesSanitized.' uploaded source file(s) in this session. Resolved: '.$ReferencesResolved.'.');
  else if ($Verbose) logEntry('OpenSCAD Sanitization Result: Files Sanitized: '.$FilesSanitized.', References Found: '.$ReferencesFound.', Resolved: '.$ReferencesResolved.', Removed: '.$ReferencesRemoved.', Resolution Enabled: '.($AllowSCADIncludeResolution ? 'TRUE' : 'FALSE').'.');
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  purgeSensitiveMemory($EnableMemoryProtection, $fileVerified, $sessionFiles, $sessionFile, $scadContents, $sanitizedSCAD, $sanitizedPath, $fileFound, $fileResolved, $fileRemoved, $bytesWritten);
  return array($AllSanitized, $FilesSanitized, $ReferencesFound, $ReferencesResolved, $ReferencesRemoved); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to convert OpenSCAD source files into a supported export format.
// / The users uploaded .scad is never modified & never replaced.
// / Every uploaded source is sanitized into ScadTemp before OpenSCAD is allowed to run.
// / The whole upload set is sanitized rather than just the requested file, because a
// / resolved include would otherwise hand OpenSCAD a source that was never filtered.
// / Sanitized copies are never retained. If they are needed again they are regenerated.
// /
// / Sanitization is not the security boundary & must never be treated as one.
// / A filter over a closed character set can be a boundary, because that question has a
// / complete answer. A filter that must interpret a grammar cannot be, because it can only
// / approximate another program's parser & every disagreement is a bypass.
// / The SCAD scanner is the second kind. Four bypasses were reported against the line
// / oriented version, & a fifth against the stateful rewrite that replaced it.
// / The boundary is the operating system sandbox below & nothing else.
// /
// / This function does NOT use sandboxCommand(). It needs a WHOLE DIRECTORY visible so that
// / include statements resolve, where every other converter needs exactly one input file.
// / Both binaries are located rather than assumed, so the OpenSCAD whose version was
// / verified & the bubblewrap that was proven functional are the ones that actually run.
// /
// / OpenSCAD does NOT fail on a missing include. It warns & renders what it can, so a model
// / can come out geometrically incomplete while reporting success. The COUNT of such
// / warnings is logged & the text is never written, because a parse warning quotes the
// / offending source line & that would turn the log into an exfiltration channel.
function convertSCAD($pathname, $newPathname, $extension) {
  // / Set variables.
  global $Verbose, $DirSep, $SCADConversionTimeout, $ScadTemp, $EnableMemoryProtection;
  // / The six value pipeline contract. Success, errors, path, extension, filename & PID.
  // / This converter produces neither of the last two itself, so it declares the defaults.
  // / $OutputFilename is the name the user is shown. $WorkerPID stays zero unless a
  // / detached process must be supervised after the connection to the user is closed.
  $OutputFilename = basename($newPathname);
  $WorkerPID = 0;
  $ConversionSuccess = $ConversionErrors = FALSE;
  $allSanitized = $readyToRender = FALSE;
  $scadBinary = $bwrapBinary = FALSE;
  $filesSanitized = $referencesFound = $referencesResolved = $referencesRemoved = 0;
  $openscadExitCode = $missingIncludes = 0;
  $sanitizedPath = $openscadCommand = $sandboxOutputName = $sandboxOutputPath = '';
  $openscadOutput = array();
  // / Confirm this server can isolate a render before anything else happens.
  // / A server that cannot isolate a render must refuse to render at all.
  $bwrapBinary = verifyBwrap();
  if ($bwrapBinary === FALSE) {
    $ConversionErrors = TRUE;
    errorEntry('Bubblewrap is missing or non functional, so OpenSCAD renders cannot be isolated!', 27007, FALSE); }
  else {
    // / Locate & verify OpenSCAD. A path is returned only when both succeeded.
    $scadBinary = verifiedToolPath('OpenSCAD');
    if ($scadBinary === FALSE) {
      $ConversionErrors = TRUE;
      errorEntry('The installed OpenSCAD version is missing or too old!', 27005, FALSE); }
    else {
      // / Sanitize every uploaded source, not just this one.
      // / A resolved include points at a sanitized copy, so every copy must already exist.
      list ($allSanitized, $filesSanitized, $referencesFound, $referencesResolved, $referencesRemoved) = sanitizeAllSCADUploads();
      // / The sanitized copy of the requested file carries the same basename as the original.
      $sanitizedPath = $ScadTemp.$DirSep.basename($pathname);
      if ($allSanitized && file_exists($sanitizedPath)) $readyToRender = TRUE;
      else {
        $ConversionErrors = TRUE;
        errorEntry('Could not prepare the OpenSCAD sources for rendering!', 27000, FALSE); } } }
  // / Render only from the sanitized copy, & only from inside the sandbox.
  // / The users original is never handed to OpenSCAD.
  if ($readyToRender) {
    if ($Verbose) logEntry('Converting OpenSCAD model to '.$extension.'.');
    // / The sandbox cannot see the real output location, so the render writes inside it.
    // / The finished model is moved out afterwards, from outside the namespace.
    $sandboxOutputName = basename($newPathname);
    $sandboxOutputPath = $ScadTemp.$DirSep.$sandboxOutputName;
    // / --unshare-all removes every namespace this render has no business holding.
    // / That includes the network, which closes any OpenSCAD build whose import() takes a URL.
    // / --die-with-parent guarantees the render cannot outlive the PHP process that started it.
    // / --new-session prevents the render from injecting into the controlling terminal.
    // / /work is the ONLY writable path & the ONLY path from the data location that exists.
    // / --chdir /work is what makes a resolved include work. rectifySCAD() rewrites an
    // / include to a bare filename, & every sanitized copy lives in this one directory.
    // / timeout enforces a wall clock limit because OpenSCAD will not stop on its own.
    // / The render is wrapped in its resource ceiling below, in place of the fixed
    // / nice -n 19 this used to carry. See the note under the command.
    $openscadCommand = 'timeout '.(int)$SCADConversionTimeout
      .' '.escapeshellarg($bwrapBinary)
      .' --unshare-all'
      .' --die-with-parent'
      .' --new-session'
      .' --ro-bind /usr /usr'
      .' --ro-bind-try /lib /lib'
      .' --ro-bind-try /lib64 /lib64'
      .' --ro-bind-try /bin /bin'
      .' --ro-bind-try /etc/fonts /etc/fonts'
      .' --ro-bind-try /etc/ld.so.cache /etc/ld.so.cache'
      .' --proc /proc'
      .' --dev /dev'
      .' --tmpfs /tmp'
      .' --tmpfs /run'
      // / A dependency resolves the running user with getpwuid() during startup & throws
      // / out of its configuration backend when the lookup fails. --unshare-all unshares
      // / the user namespace, so without these the lookup has nothing to read & LibreOffice
      // / aborts with Signal 6 before it opens a file.
      .' --ro-bind-try /etc/passwd /etc/passwd'
      .' --ro-bind-try /etc/group /etc/group'
      .' --ro-bind-try /etc/machine-id /etc/machine-id'
      .' --ro-bind-try /etc/localtime /etc/localtime'
      // / --dev builds a minimal device tree with no /dev/shm, which several dependencies
      // / require for shared memory. A tmpfs is enough & stays inside the namespace.
      .' --tmpfs /dev/shm'
      .' --setenv HOME /tmp'
      // / Every writable location a dependency reaches for is pointed at the tmpfs.
      // / Nothing tries to create state outside the namespace & fail.
      .' --setenv XDG_RUNTIME_DIR /tmp'
      .' --setenv XDG_CONFIG_HOME /tmp/.config'
      .' --setenv XDG_CACHE_HOME /tmp/.cache'
      .' --setenv XDG_DATA_HOME /tmp/.local'
      .' --bind '.escapeshellarg($ScadTemp).' /work'
      .' --chdir /work'
      .' '.escapeshellarg($scadBinary).' -o '.escapeshellarg('/work/'.$sandboxOutputName)
      .' '.escapeshellarg('/work/'.basename($sanitizedPath))
      .' 2>&1';
    // / Wrap the render in the ceiling configured for its type, exactly as every pipeline
    // / that goes through sandboxCommand() is wrapped. This one builds its own sandbox
    // / because it needs a whole directory visible for includes to resolve, & in doing so
    // / it had walked around the limiter as well as around the sandbox builder.
    // /
    // / THIS REPLACES A FIXED nice -n 19 & IS NOT STRICTLY STRONGER ON EVERY HOST.
    // / Where a scope can be created the ceiling is far stronger; a CPUQuota is enforced
    // / against a cgroup rather than requested from the scheduler. Where no scope can be
    // / created limitCommand() falls back to niceness derived from the configured share.
    // / The stock 'Scad' => '75,1024' yields less than the old hardcoded 19 did. That is the
    // / configured policy being honoured rather than overridden, which is the point, but an
    // / administrator running without systemd who wants the old behaviour back sets a
    // / smaller processor share for Scad & gets a larger niceness from it.
    $openscadCommand = limitCommand($openscadCommand, 'openscad');
    exec($openscadCommand, $openscadOutput, $openscadExitCode);
    // / An exit code of 124 is the timeout command reporting that it killed the render.
    if ($openscadExitCode === 124) {
      $ConversionErrors = TRUE;
      errorEntry('The OpenSCAD converter timed out after '.(int)$SCADConversionTimeout.' seconds!', 27002, FALSE); }
    else if ($openscadExitCode !== 0) {
      $ConversionErrors = TRUE;
      errorEntry('The OpenSCAD converter failed with exit code '.$openscadExitCode.'!', 27003, FALSE); }
    // / Count the includes OpenSCAD could not open. The COUNT is reported & the text is not.
    // / A render that silently drops geometry reports success without this.
    if (is_array($openscadOutput)) $missingIncludes = count(preg_grep('/Can\'t open include file/i', $openscadOutput));
    if ($missingIncludes > 0) warningEntry('The OpenSCAD render could not open '.$missingIncludes.' include file(s). The model may be incomplete.');
    // / Move the rendered model out of the sandbox directory to where the core expects it.
    // / This happens BEFORE the cleanup, or the cleanup would delete the model.
    if (file_exists($sandboxOutputPath)) {
      if (!@rename($sandboxOutputPath, $newPathname)) {
        $ConversionErrors = TRUE;
        errorEntry('Could not move the rendered OpenSCAD model out of the sandbox!', 27008, FALSE); } }
    // / Remove every sanitized copy immediately. None of them are retained for any reason.
    // / cleanFiles() removes the emptied directory itself, so its absence is the success case.
    // / verifyRequiredDirs() recreates it at the start of the next request.
    // / The second argument is the list of roots this call may clean under & is required.
    // / cleanFiles refuses a path outside it, so omitting it refused this call silently,
    // / the sanitized sources were never removed, & the error below reported a failure on a
    // / render that had already succeeded.
    cleanFiles($ScadTemp, array($ScadTemp));
    if (is_dir($ScadTemp) && !is_dir_empty($ScadTemp)) errorEntry('Could not remove the sanitized OpenSCAD sources!', 27004, FALSE); }
  // / The output file is the only verdict on whether the render actually produced anything.
  if (file_exists($newPathname)) $ConversionSuccess = TRUE;
  // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
  // / $openscadOutput may hold quoted source lines & is cleared like everything else.
  purgeSensitiveMemory($EnableMemoryProtection, $sanitizedPath, $openscadCommand, $openscadOutput, $openscadExitCode, $missingIncludes, $readyToRender, $scadBinary, $bwrapBinary, $allSanitized, $filesSanitized, $referencesFound, $referencesResolved, $referencesRemoved, $sandboxOutputName, $sandboxOutputPath, $pathname);
  return array($ConversionSuccess, $ConversionErrors, $newPathname, $extension, $OutputFilename, $WorkerPID); }
// / -----------------------------------------------------------------------------------

?>
