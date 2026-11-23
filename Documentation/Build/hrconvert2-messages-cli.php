<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRProprietary Engine, Copyright on 11/20/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / File Information ...
// / v4.3.1.
// / Holds the code & the error catalogue to each other, message by message.
// /   php Documentation/Build/hrconvert2-messages-cli.php
// / Run it from the root of an application, as the other checkers are run.
// / Rule 17 has every warning & every error the code can produce documented in Documentation/ERROR_DESCRIPTIONS.txt.
// / The code checkers hold an error to its number. Nothing held a warning to anything, or an error to its words, until this.
// / It was HRProtect's tests/check_messages.php, from its v0.0.3, & reads the whole tree rather than HRProtect's own files from v4.3.1.
// /
// / It reads every warningEntry(), errorEntry() & quickDie() in the entry point & in Resources, with PHP's own tokenizer.
// / Resources/ScanCore is a program of its own, with a catalogue of its own, & is not read.
// / The third value handed to the Engine's refuseCommandForUser() is read as well, because that function logs it as a warning.
// / The text of a call is its string literals, in order, with something unknown wherever anything else is joined to them.
// / A text handed over in trim(), rtrim() or ltrim() is read from inside it. A text handed over as one variable is every text that
// / variable is given with =, above the call & in the same function, & each is held to a line. One that list() gives a value to there
// / holds what another call handed back, & one that .= adds to is more than any of them, so neither is read.
// / An error's number is read the same way, so one chosen as the code runs is held to the lines of each number it may be.
// /
// / A call & a line of the catalogue agree where one could be the other: where the line, with each <A_PLACEHOLDER> standing for something
// / unknown, could be written by the call, or the call, with each thing joined to it standing for something unknown, could be what the line
// / describes. Something unknown on either side stands for up to two words of the other, or for words around something unknown of the
// / other, & never for a sentence the other spells out. So a line may spell out what a call leaves unknown, such as FAILED or it is,
// / & a call that leaves its start unknown does not agree with every line that ends as it does.
// /
// /   UNDOCUMENTED   A call that the catalogue has no line for.
// /   STALE          A warning the catalogue documents that no call in the tree agrees with.
// /   UNREADABLE     A call whose text, or an error whose number, could not be read as above, which this cannot hold to anything.
// /                  Write it so that it can.
// /   VAGUE          A line of the catalogue with fewer than ten characters of its own, which would agree with every call. It is held
// /                  to nothing. Write the words the message begins with.
// /
// / A line is held to a call only in a tree that holds the file its block says raises it, from v4.3.2. The kit's catalogue documents
// / the Example's convertCore.php & the Starter's app.php beside the Engine, & an application that adopts it keeps every line, because
// / an application adds to the Engine's documents & takes nothing away. So a block that names only files at the root of an application,
// / none of which this tree holds, is another application's, & is counted apart. A block naming a file beneath Resources is held as ever.
// /
// / It exits 0 where there is none of the four, 1 otherwise, & 2 where it is not run from the root of an application.
// / It reads files & prints what it found. It writes nothing, removes nothing & runs no command, so it needs no contained run.
// / It is a PHP build tool, so it is gated three ways. See --About Where They Live-- in Documentation/ABOUT_QUALITY_CONTROL.txt.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

if (PHP_SAPI !== 'cli') { if (!headers_sent()) http_response_code(404); die(); }

// / A request carries a method, a host, a gateway, a server name or a remote address, & a tool run from a terminal carries none of them.
foreach (array('REQUEST_METHOD', 'HTTP_HOST', 'GATEWAY_INTERFACE', 'SERVER_NAME', 'REMOTE_ADDR') as $requestVariable) if (getenv($requestVariable) !== FALSE or isset($_SERVER[$requestVariable])) die();

// / Root is dropped where it was given, the group before the user, because this only reads. SUDO_UID names whoever ran sudo.
if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
  $landingGroup = (getenv('SUDO_GID') !== FALSE && ctype_digit((string)getenv('SUDO_GID'))) ? (int)getenv('SUDO_GID') : 65534;
  $landingUser = (getenv('SUDO_UID') !== FALSE && ctype_digit((string)getenv('SUDO_UID'))) ? (int)getenv('SUDO_UID') : 65534;
  if (!posix_setgid($landingGroup) or !posix_setuid($landingUser)) { fwrite(STDERR, "Root could not be dropped, & nothing was read.\n"); exit(2); }
  print('Running as user '.posix_geteuid().' & group '.posix_getegid().", because root is not needed to read.\n"); }

$catalogue = 'Documentation/ERROR_DESCRIPTIONS.txt';
$contract = 'Resources/Contract/app-engine-config.php';
$entryPoint = (preg_match("/^\\\$EngineEntryPoint\\s*=\\s*'([A-Za-z0-9._-]+\\.php)'\\s*;/m", (string)@file_get_contents($contract), $entryMatch) === 1) ? $entryMatch[1] : 'app.php';
if (!is_file($catalogue) or !is_file('Resources/Kernel/kernel.php') or !is_file($entryPoint)) { fwrite(STDERR, "Run this from the root of an application, beside ".$entryPoint." & Resources.\n"); exit(2); }
$ownFiles = array($entryPoint);
$walker = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('Resources', FilesystemIterator::SKIP_DOTS));
foreach ($walker as $found) if (substr($found->getPathname(), -4) === '.php' && strpos($found->getPathname(), 'Resources/ScanCore/') !== 0) $ownFiles[] = $found->getPathname();
sort($ownFiles);

// / Whether a token is one a text is not made of.
function isBlank($token) { return is_array($token) && in_array($token[0], array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT), TRUE); }

// / Reads the values handed to the call whose opening bracket is at $open.
// / Returns a list of values, each a list of entries, & an entry is a token, the depth it is at & where it is in the file.
function argumentsAt($tokens, $open) {
  $depth = 0; $arguments = array(array()); $count = count($tokens);
  for ($k = $open + 1; $k < $count; $k++) {
    $text = is_array($tokens[$k]) ? $tokens[$k][1] : $tokens[$k];
    $opens = in_array($text, array('(', '[', '{'), TRUE); $closes = in_array($text, array(')', ']', '}'), TRUE);
    if ($depth === 0 && $text === ')') break;
    if ($depth === 0 && $text === ',') { $arguments[] = array(); continue; }
    if ($closes) $depth--;
    $arguments[count($arguments) - 1][] = array($tokens[$k], $depth, $k);
    if ($opens) $depth++; }
  return $arguments; }

// / Whether the token at $position is a name list() gives a value to.
function isListed($tokens, $position) {
  $depth = 0;
  for ($k = $position - 1; $k > 0; $k--) {
    $text = is_array($tokens[$k]) ? $tokens[$k][1] : $tokens[$k];
    if ($text === ')') $depth++;
    else if ($text === '(' && $depth > 0) $depth--;
    else if ($text === '(') { $before = $k - 1; while ($before > 0 && isBlank($tokens[$before])) $before--; return (is_array($tokens[$before]) && $tokens[$before][0] === T_LIST); }
    else if (in_array($text, array(';', '{', '}'), TRUE)) return FALSE; }
  return FALSE; }

// / Whether the statement the token at $position is in is the body of an if, an else or a loop, rather than one every way passes through.
function isConditional($tokens, $position) {
  $depth = 0;
  for ($k = $position - 1; $k >= 0; $k--) {
    $text = is_array($tokens[$k]) ? $tokens[$k][1] : $tokens[$k];
    if ($text === ')') $depth++;
    else if ($text === '(') $depth--;
    else if ($depth === 0 && in_array($text, array(';', '{', '}'), TRUE)) break; }
  for ($k = $k + 1; $k < $position && isBlank($tokens[$k]); $k++);
  return (is_array($tokens[$k]) && in_array($tokens[$k][0], array(T_IF, T_ELSE, T_ELSEIF, T_FOREACH, T_FOR, T_WHILE, T_DO), TRUE)); }

// / Finds every value a variable is given with = above the token at $position, in the same function, that can still reach it.
// / Returns the values, each a list of entries, & whether they could be read, in that order.
// / One every way to the token passes through, in the same block & not the body of an if, an else or a loop, is the last that can.
// / One that list() gives a value to holds what another call handed back, & one that .= adds to is more than any of them, so neither
// / can be read.
function valuesReaching($tokens, $position, $variableName) {
  $values = array(); $braceDepth = 0;
  for ($k = $position - 1; $k > 0; $k--) {
    if (is_array($tokens[$k]) && $tokens[$k][0] === T_FUNCTION) break;
    // / A block that closed above the token is one the way to it may not have passed through.
    if ($tokens[$k] === '}') { $braceDepth++; continue; }
    if ($tokens[$k] === '{' or (is_array($tokens[$k]) && in_array($tokens[$k][0], array(T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES), TRUE))) { if ($braceDepth > 0) $braceDepth--; continue; }
    if (!is_array($tokens[$k]) or $tokens[$k][0] !== T_VARIABLE or $tokens[$k][1] !== $variableName) continue;
    if (isListed($tokens, $k)) return array(array(), FALSE);
    $next = $k + 1; while (isBlank($tokens[$next])) $next++;
    if (is_array($tokens[$next]) && $tokens[$next][0] === T_CONCAT_EQUAL) return array(array(), FALSE);
    if ($tokens[$next] !== '=') continue;
    $assigned = array(); $depth = 0;
    for ($m = $next + 1; $m < $position; $m++) {
      $text = is_array($tokens[$m]) ? $tokens[$m][1] : $tokens[$m];
      if ($depth === 0 && $text === ';') break;
      // / Of a chain such as $one = $two = '', the value at its end is the value.
      if ($depth === 0 && $text === '=') { $assigned = array(); continue; }
      if (in_array($text, array(')', ']', '}'), TRUE)) $depth--;
      $assigned[] = array($tokens[$m], $depth, $m);
      if (in_array($text, array('(', '[', '{'), TRUE)) $depth++; }
    $values[] = $assigned;
    if ($braceDepth === 0 && !isConditional($tokens, $k)) break; }
  return array($values, TRUE); }

// / Reads the number handed to an error. Returns the numbers it may be & whether it could be read, in that order.
// / A number handed over as one variable is each number in each value valuesReaching() finds for it, where a value is numbers & the
// / choice between them alone. A value of 0 is where it is set before it is given one.
function numbersOf($entries, $tokens) {
  $entries = array_values(array_filter($entries, function ($entry) { return !isBlank($entry[0]); }));
  if (count($entries) === 1 && is_array($entries[0][0]) && $entries[0][0][0] === T_LNUMBER) return array(array((int)$entries[0][0][1]), TRUE);
  if (count($entries) !== 1 or !is_array($entries[0][0]) or $entries[0][0][0] !== T_VARIABLE) return array(array(), FALSE);
  list ($values, $NumbersAreRead) = valuesReaching($tokens, $entries[0][2], $entries[0][0][1]);
  $numbers = array();
  foreach ($values as $assigned) {
    $assignedNumbers = array();
    foreach ($assigned as $entry) {
      if (isBlank($entry[0])) continue;
      if (is_array($entry[0]) && $entry[0][0] === T_LNUMBER) $assignedNumbers[] = (int)$entry[0][1];
      else if (in_array(is_array($entry[0]) ? $entry[0][1] : $entry[0], array('+', '-', '*', '/', '%', '.'), TRUE)) $NumbersAreRead = FALSE; }
    if ($assignedNumbers === array(0)) continue;
    if (count($assignedNumbers) === 0) $NumbersAreRead = FALSE;
    $numbers = array_merge($numbers, $assignedNumbers); }
  return array(array_values(array_unique($numbers)), $NumbersAreRead && count($numbers) > 0); }

// / Reads one text. Returns the texts it may be & whether each could be read, in that order.
// / A text is a list of pieces, & a piece is a string, for a literal, or NULL, for something unknown.
function textsOf($entries, $tokens) {
  $entries = array_values(array_filter($entries, function ($entry) { return !isBlank($entry[0]); }));
  // / A text in trim(), rtrim() or ltrim() is read from inside it.
  $last = count($entries) - 1;
  if ($last >= 2 && is_array($entries[0][0]) && $entries[0][0][0] === T_STRING && in_array(strtolower($entries[0][0][1]), array('trim', 'rtrim', 'ltrim'), TRUE) && $entries[1][0] === '(' && $entries[$last][0] === ')') {
    $inner = array(); foreach (array_slice($entries, 2, $last - 2) as $entry) $inner[] = array($entry[0], $entry[1] - $entries[1][1] - 1, $entry[2]);
    $holdsOneValue = TRUE; foreach ($inner as $entry) if ($entry[1] === 0 && $entry[0] === ',') $holdsOneValue = FALSE;
    if ($holdsOneValue) return textsOf($inner, $tokens); }
  // / A text handed over as one variable is each text valuesReaching() finds for it. An empty one is where it is set before it is
  // / given one.
  if (count($entries) === 1 && is_array($entries[0][0]) && $entries[0][0][0] === T_VARIABLE) {
    list ($values, $valuesAreRead) = valuesReaching($tokens, $entries[0][2], $entries[0][0][1]);
    $texts = array(); $readable = $valuesAreRead;
    foreach ($values as $assigned) {
      list ($assignedTexts, $assignedIsReadable) = textsOf($assigned, $tokens);
      if (count($assignedTexts) === 1 && $assignedTexts[0] === array('')) continue;
      $texts = array_merge($texts, $assignedTexts);
      $readable = ($readable && $assignedIsReadable); }
    if (count($texts) === 0) return array(array(array(NULL)), FALSE);
    return array($texts, $readable); }
  $pieces = array(); $readable = TRUE;
  foreach ($entries as $entry) {
    list ($token, $depth) = $entry;
    $text = is_array($token) ? $token[1] : $token;
    if ($depth === 0 && $text === '.') continue;
    // / A choice between two texts that is not in brackets of its own cannot be read as one text.
    if ($depth === 0 && in_array($text, array('?', ':'), TRUE)) $readable = FALSE;
    if ($depth === 0 && is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING && $text[0] === "'") $pieces[] = str_replace(array("\\'", '\\\\'), array("'", '\\'), substr($text, 1, -1));
    else if (count($pieces) === 0 or end($pieces) !== NULL) $pieces[] = NULL; }
  // / A text with fewer than ten characters of its own says too little to be held to a line, & would agree with every line there is.
  return array(array($pieces), $readable && strlen(implode('', array_filter($pieces, 'is_string'))) >= 10); }

// / Reads every call that logs a warning or an error in a file.
// / Returns a list of calls, each with its file, its line, its kind, its pieces, its number & whether its text could be read.
// / A call inside one of the functions that log is the message being handed on, & says nothing of its own, so it is not read.
function callsIn($file) {
  $calls = $functionsOpen = array();
  $tokens = token_get_all((string)file_get_contents($file));
  $count = count($tokens);
  $forwarders = array('logEntry', 'warningEntry', 'errorEntry', 'quickDie', 'refuseCommandForUser');
  $depth = 0; $functionNamed = '';
  // / Which of the values handed to each function is the text it logs, counted from 0, & which is its number.
  $textIsValue = array('warningEntry' => 0, 'errorEntry' => 0, 'quickDie' => 0, 'refuseCommandForUser' => 2);
  for ($i = 0; $i < $count; $i++) {
    // / Which function each token is inside is kept, by the depth of the brace that opened it.
    if ($tokens[$i] === '{' or (is_array($tokens[$i]) && in_array($tokens[$i][0], array(T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES), TRUE))) {
      $depth++; if ($functionNamed !== '') { $functionsOpen[] = array($functionNamed, $depth); $functionNamed = ''; } continue; }
    if ($tokens[$i] === '}') { if (count($functionsOpen) > 0 && end($functionsOpen)[1] === $depth) array_pop($functionsOpen); $depth--; continue; }
    if (is_array($tokens[$i]) && $tokens[$i][0] === T_FUNCTION) { $named = $i + 1; while ($named < $count && isBlank($tokens[$named])) $named++; if ($named < $count && is_array($tokens[$named]) && $tokens[$named][0] === T_STRING) $functionNamed = $tokens[$named][1]; continue; }
    if (!is_array($tokens[$i]) or $tokens[$i][0] !== T_STRING or !isset($textIsValue[$tokens[$i][1]])) continue;
    if (count($functionsOpen) > 0 && in_array(end($functionsOpen)[0], $forwarders, TRUE)) continue;
    // / A definition of the function is not a call to it.
    $before = $i - 1; while ($before >= 0 && isBlank($tokens[$before])) $before--;
    if ($before >= 0 && is_array($tokens[$before]) && $tokens[$before][0] === T_FUNCTION) continue;
    $open = $i + 1; while ($open < $count && isBlank($tokens[$open])) $open++;
    if ($open >= $count or $tokens[$open] !== '(') continue;
    $arguments = argumentsAt($tokens, $open);
    list ($texts, $readable) = textsOf($arguments[$textIsValue[$tokens[$i][1]]] ?? array(), $tokens);
    $numbers = array(NULL); $numberIsRead = TRUE;
    $isError = in_array($tokens[$i][1], array('errorEntry', 'quickDie'), TRUE);
    if ($isError) list ($numbers, $numberIsRead) = numbersOf($arguments[1] ?? array(), $tokens);
    $calls[] = array('File' => $file, 'Line' => $tokens[$i][2], 'Kind' => $isError ? 'error' : 'warning', 'Texts' => $texts, 'Numbers' => $numbers, 'Readable' => ($readable && $numberIsRead)); }
  return $calls; }

// / Whether a call & a line of the catalogue agree, which is where one could be the other.
// / Something unknown on one side stands for up to two words of the other, or for words around something unknown of the other.
function agree($pieces, $documented) {
  $standIn = '(?:[^ ]*(?: [^ ]*)?|.*\x01.*)';
  $callPattern = $callInstance = '';
  foreach ($pieces as $piece) { $callPattern .= ($piece === NULL) ? $standIn : preg_quote($piece, '/'); $callInstance .= ($piece === NULL) ? "\x01" : $piece; }
  $linePattern = implode($standIn, array_map(function ($part) { return preg_quote($part, '/'); }, preg_split('/<[A-Z][A-Z0-9_]*>/', trim($documented))));
  $lineInstance = preg_replace('/<[A-Z][A-Z0-9_]*>/', "\x01", trim($documented));
  return preg_match('/^'.$callPattern.'$/s', $lineInstance) === 1 or preg_match('/^'.$linePattern.'$/s', $callInstance) === 1; }

// / Whether the files a block names are another application's: each is at the root of an application, & this tree holds none of them.
function isAnotherApplications($attributedFiles) {
  if (count($attributedFiles) === 0) return FALSE;
  foreach ($attributedFiles as $attributedFile) if (strpos($attributedFile, '/') !== FALSE or is_file($attributedFile)) return FALSE;
  return TRUE; }

$lines = explode("\n", (string)file_get_contents($catalogue));
$documentedWarnings = $documentedErrors = $vagueLines = $attributedFiles = array();
foreach ($lines as $index => $line) {
  // / A block says which file raises the lines below it, & a later sentence of the kind names another for the lines below that. A rule ends both.
  if (trim($line) === str_repeat('-', 160)) $attributedFiles = array();
  if (preg_match('/^  \S.* raises these, from (.+)$/', $line, $match) === 1) $attributedFiles = (preg_match_all('/[A-Za-z0-9_.\/-]+\.php\b/', $match[1], $fileMatches) > 0) ? $fileMatches[0] : array();
  if (preg_match('/^\s*WARNING!!! (.+)$/', $line, $match) === 1) $documentedWarnings[] = array($index + 1, $match[1], $attributedFiles);
  if (preg_match('/^\s*ERROR!!! <TIMESTAMP>, \S+-([0-9]+), <SESSION_IDENTIFIER>: (.+)$/', $line, $match) === 1) $documentedErrors[(int)$match[1]][] = array($index + 1, $match[2]); }
// / A line with fewer than ten characters of its own is held to nothing, as a call with as few is.
foreach ($documentedWarnings as $key => $documentedWarning) if (strlen(trim(preg_replace('/<[A-Z][A-Z0-9_]*>/', '', $documentedWarning[1]))) < 10) { $vagueLines[] = $documentedWarning; unset($documentedWarnings[$key]); }
foreach ($documentedErrors as $number => $documentedLines) foreach ($documentedLines as $key => $documentedLine) if (strlen(trim(preg_replace('/<[A-Z][A-Z0-9_]*>/', '', $documentedLine[1]))) < 10) { $vagueLines[] = $documentedLine; unset($documentedErrors[$number][$key]); }

$findings = array('UNDOCUMENTED' => 0, 'STALE' => 0, 'UNREADABLE' => 0, 'VAGUE' => 0);
$calls = array(); foreach ($ownFiles as $file) $calls = array_merge($calls, callsIn($file));
print('Checking '.count($calls).' warnings & errors in '.count($ownFiles).' files against '.$catalogue.".\n");
$held = array('warning' => 0, 'error' => 0);
foreach ($calls as $call) {
  $where = $call['File'].':'.$call['Line'];
  if (!$call['Readable']) { $findings['UNREADABLE']++; print('  UNREADABLE    '.$where.' raises '.($call['Kind'] === 'error' ? 'an error whose text or number' : 'a warning whose text')." could not be read.\n"); continue; }
  // / A call is documented where each text it may be agrees with a line, for each number it may carry, & each that does not is named.
  $callIsDocumented = TRUE;
  foreach ($call['Numbers'] as $number) {
    $candidates = array();
    if ($call['Kind'] === 'warning') foreach ($documentedWarnings as $documentedWarning) $candidates[] = $documentedWarning[1];
    else foreach ($documentedErrors[$number] ?? array() as $documentedLine) $candidates[] = $documentedLine[1];
    foreach ($call['Texts'] as $pieces) {
      $documented = FALSE; foreach ($candidates as $candidate) if (agree($pieces, $candidate)) $documented = TRUE;
      if ($documented) continue;
      $callIsDocumented = FALSE;
      $findings['UNDOCUMENTED']++; print('  UNDOCUMENTED  '.$where.' raises '.($call['Kind'] === 'error' ? 'error '.$number : 'a warning').' the catalogue has no line for: '.implode('<...>', array_map(function ($piece) { return ($piece === NULL) ? '' : $piece; }, $pieces))."\n"); } }
  if ($callIsDocumented) $held[$call['Kind']]++; }
$foreignWarnings = 0;
foreach ($documentedWarnings as $documentedWarning) {
  if (isAnotherApplications($documentedWarning[2])) { $foreignWarnings++; continue; }
  $written = FALSE;
  foreach ($calls as $call) if ($call['Kind'] === 'warning' && $call['Readable']) foreach ($call['Texts'] as $pieces) if (agree($pieces, $documentedWarning[1])) $written = TRUE;
  if (!$written) { $findings['STALE']++; print('  STALE         '.$catalogue.':'.$documentedWarning[0].' documents a warning no call in the tree agrees with: '.$documentedWarning[1]."\n"); } }
foreach ($vagueLines as $vagueLine) { $findings['VAGUE']++; print('  VAGUE         '.$catalogue.':'.$vagueLine[0].' documents a message with fewer than ten characters of its own: '.$vagueLine[1]."\n"); }
$total = array_sum($findings);
if ($total === 0) print($held['warning'].' warnings & '.$held['error'].' errors each have a line of their own, & each of the '.(count($documentedWarnings) - $foreignWarnings).' warnings the catalogue documents has a call that agrees with it.'.($foreignWarnings > 0 ? ' '.$foreignWarnings.' more are raised by a file at the root of another application, & are held to nothing here.' : '')."\nEvery check passed.\n");
else print($total." finding(s).\n");
exit($total === 0 ? 0 : 1);
