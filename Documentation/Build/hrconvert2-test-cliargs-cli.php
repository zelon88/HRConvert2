<?php
// / -----------------------------------------------------------------------------------
// / Copyright information ...
// / HRConvert2, Copyright on 10/31/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / Application information ...
// / This application is designed to provide a web-interface for converting file formats
// / on a server for users of any web browser without authentication.
// /
// / File information ...
// / v3.9.9.
// / This file tests the kernel's command line argument parser against the inline parsing it
// / replaced, & against a table of answers written by hand.
// /
// / WHY BOTH KINDS OF CHECK.
// / A differential test proves the move changed nothing. It cannot prove the result is
// / RIGHT, because two identical wrong answers agree perfectly. A table of hand written
// / answers proves the behaviour is what somebody intended. Neither is sufficient alone &
// / the pair is what makes this worth running.
// /
// / Run from the installation root, beside convertCore.php.
// /   php Documentation/Build/hrconvert2-test-cliargs-cli.php
// / Exits non zero on any wrong answer.
// / -----------------------------------------------------------------------------------

// / This runs as any user & needs no privilege. It reads one file & calls one function.
if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
  // / Dropping is cosmetic here rather than protective, & it keeps the habit.
  if (function_exists('posix_setgid')) @posix_setgid(65534);
  if (function_exists('posix_setuid')) @posix_setuid(65534); }
print('This test needs no root & runs as uid '.(function_exists('posix_geteuid') ? posix_geteuid() : 'unknown').'.'.PHP_EOL);

// / Stubs. The parser calls one kernel function & reads one global.
$EnableMemoryProtection = FALSE;
function purgeSensitiveMemory() { return TRUE; }

// / Lift the function out of the kernel rather than loading it, because this test defines its
// / own stub for purgeSensitiveMemory & loading the kernel would redeclare it.
// / It lived in the Engine for part of v3.9.9 & came back to the kernel, because every
// / recovery command parses its own arguments before the Engine is known to be usable.
// / The kernel became a file of its own later in the same release.
$kernelFile = 'Resources'.DIRECTORY_SEPARATOR.'Kernel'.DIRECTORY_SEPARATOR.'kernel.php';
if (!is_file($kernelFile)) {
  fwrite(STDERR, 'Run this from the installation root, beside convertCore.php. Could not read '.$kernelFile.'.'.PHP_EOL);
  exit(2); }
$kernelSource = file_get_contents($kernelFile);
$functionStart = strpos($kernelSource, "\nfunction parseArgumentVector(");
if ($functionStart === FALSE) {
  fwrite(STDERR, 'Could not find parseArgumentVector in '.$kernelFile.'.'.PHP_EOL);
  exit(2); }
$braceDepth = 0;
$extracted = '';
for ($scanIndex = $functionStart + 1; $scanIndex < strlen($kernelSource); $scanIndex++) {
  $extracted .= $kernelSource[$scanIndex];
  if ($kernelSource[$scanIndex] == '{') $braceDepth++;
  if ($kernelSource[$scanIndex] == '}') { $braceDepth--; if ($braceDepth == 0) break; } }
eval($extracted);

// / The reference parse. This is what the kernel function has to agree with. A local copy of
// / the parser in parseCommandLine had to agree with it too, until the parser came back to the
// / kernel & the copy was removed as the evidence of a wrong move.
// /
// / It was a verbatim copy of the pre v3.9.9 inline parsing, which lowercased a target after
// / an equal sign & left a target in the next argument alone. That asymmetry was decided
// / against rather than preserved, so this now lowercases both. Do not restore the old
// / behaviour to make a test pass.
function legacyParse($argumentVector) {
  $cliCommand = $cliTarget = $cliSecondTarget = '';
  $cliConfirmed = FALSE;
  $cliArguments = $argumentVector;
  array_shift($cliArguments);
  $cliArgumentCount = count($cliArguments);
  foreach ($cliArguments as $confirmCandidate) {
    $confirmCandidate = strtolower(trim((string)$confirmCandidate));
    if ($confirmCandidate === '-y' or $confirmCandidate === '--yes') $cliConfirmed = TRUE; }
  if ($cliArgumentCount > 0) {
    $rawFirstArg = trim((string)$cliArguments[0]);
    $cliParts = explode('=', $rawFirstArg, 2);
    $cliCommand = strtolower(trim($cliParts[0]));
    if (isset($cliParts[1])) $cliTarget = strtolower(trim($cliParts[1]));
    else $cliTarget = isset($cliArguments[1]) ? strtolower(trim((string)$cliArguments[1])) : '';
    $cliSecondTarget = isset($cliArguments[2]) ? strtolower(trim((string)$cliArguments[2])) : ''; }
  return array($cliArguments, $cliArgumentCount, $cliCommand, $cliTarget, $cliSecondTarget, $cliConfirmed, TRUE); }

// / Every vector worth asking about. The script name is always element zero.
// / Expected values are Count, Command, Target, SecondTarget, Confirmed.
$cases = array(
  'no argument at all'          => array(array('convertCore.php'),                                      0, '',            '',                  '',      FALSE),
  'version short'              => array(array('convertCore.php', '-v'),                                1, '-v',          '',                  '',      FALSE),
  'version long'               => array(array('convertCore.php', '--version'),                         1, '--version',   '',                  '',      FALSE),
  'command is lowercased'      => array(array('convertCore.php', '--VERSION'),                         1, '--version',   '',                  '',      FALSE),
  'whitespace is trimmed'      => array(array('convertCore.php', '  --help  '),                        1, '--help',      '',                  '',      FALSE),
  'separate target lowercased' => array(array('convertCore.php', '--config', '/Path/To/File'),          2, '--config',    '/path/to/file',     '',      FALSE),
  'equals target lowercased'   => array(array('convertCore.php', '--config=/Path/To/File'),             1, '--config',    '/path/to/file',     '',      FALSE),
  'sub option as target'       => array(array('convertCore.php', '--setup', '--install-depends'),       2, '--setup',     '--install-depends', '',      FALSE),
  'second target lowercased'   => array(array('convertCore.php', '--pipeline', '--verify', 'Scad'),     3, '--pipeline',  '--verify',          'scad',  FALSE),
  'confirm after target'       => array(array('convertCore.php', '--clean', 'now', '-y'),               3, '--clean',     'now',               '-y',    TRUE),
  'confirm long form'          => array(array('convertCore.php', '--clean', 'now', '--yes'),            3, '--clean',     'now',               '--yes', TRUE),
  'confirm upper case'         => array(array('convertCore.php', '--clean', 'now', '-Y'),               3, '--clean',     'now',               '-y',    TRUE),
  'confirm before command'     => array(array('convertCore.php', '-y', '--clean', 'now'),               3, '-y',          '--clean',           'now',   TRUE),
  'empty equals value'         => array(array('convertCore.php', '--config='),                          1, '--config',    '',                  '',      FALSE),
  'target carrying an equals'  => array(array('convertCore.php', '--config=a=b'),                        1, '--config',    'a=b',               '',      FALSE),
  'bare target no command'     => array(array('convertCore.php', 'now'),                                1, 'now',         '',                  '',      FALSE));

$fieldNames = array('Count', 'Command', 'Target', 'SecondTarget', 'Confirmed');
$wrongAnswers = 0;
$disagreements = 0;
$caseName = '';
$caseData = array();

foreach ($cases as $caseName => $caseData) {
  $vector = $caseData[0];
  $expected = array($caseData[1], $caseData[2], $caseData[3], $caseData[4], $caseData[5]);
  list ($kernelArgs, $kernelCount, $kernelCommand, $kernelTarget, $kernelSecond, $kernelConfirmed, $kernelParsed) = parseArgumentVector($vector);
  list ($legacyArgs, $legacyCount, $legacyCommand, $legacyTarget, $legacySecond, $legacyConfirmed, $legacyParsed) = legacyParse($vector);
  $kernelResult = array($kernelCount, $kernelCommand, $kernelTarget, $kernelSecond, $kernelConfirmed);
  $legacyResult = array($legacyCount, $legacyCommand, $legacyTarget, $legacySecond, $legacyConfirmed);
  // / The sanity check must be true for every well formed vector.
  if ($kernelParsed !== TRUE) {
    print('  WRONG  '.$caseName.': the parser reported it could not read a valid vector.'.PHP_EOL);
    $wrongAnswers++; }
  // / Compare against the hand written answer.
  foreach ($fieldNames as $fieldIndex => $fieldName) {
    if ($kernelResult[$fieldIndex] !== $expected[$fieldIndex]) {
      print('  WRONG  '.$caseName.': '.$fieldName.' expected '.var_export($expected[$fieldIndex], TRUE).' & got '.var_export($kernelResult[$fieldIndex], TRUE).'.'.PHP_EOL);
      $wrongAnswers++; } }
  // / Compare against what the inline code did.
  foreach ($fieldNames as $fieldIndex => $fieldName) {
    if ($kernelResult[$fieldIndex] !== $legacyResult[$fieldIndex]) {
      print('  DIFFERS '.$caseName.': '.$fieldName.' was '.var_export($legacyResult[$fieldIndex], TRUE).' inline & is '.var_export($kernelResult[$fieldIndex], TRUE).' in the kernel.'.PHP_EOL);
      $disagreements++; } }
  // / The remaining argument list is what extractCliOption reads later, so it has to match.
  if ($kernelArgs !== $legacyArgs) {
    print('  DIFFERS '.$caseName.': the remaining argument list does not match.'.PHP_EOL);
    $disagreements++; } }

// / A vector that is not an array at all is the one case that must report FALSE rather than
// / guess. The caller shows help instead.
list ($nullArgs, $nullCount, $nullCommand, $nullTarget, $nullSecond, $nullConfirmed, $nullParsed) = parseArgumentVector('not an array');
if ($nullParsed !== FALSE) { print('  WRONG  a non array vector was reported as parsed.'.PHP_EOL); $wrongAnswers++; }
if ($nullCount !== 0 or $nullCommand !== '') { print('  WRONG  a non array vector produced a command.'.PHP_EOL); $wrongAnswers++; }

// / THE COMMAND LINE IS CASE INSENSITIVE, & THIS IS THE TEST OF THAT RATHER THAN OF A TABLE.
// / Every vector above is parsed again with every element upper cased. The result must be
// / identical, field for field. A table of answers can be made to pass by editing the table.
// / A property cannot, which is why this is the check that matters for the v3.9.9 decision.
$caseFailures = 0;
foreach ($cases as $caseName => $caseData) {
  $lowerVector = $caseData[0];
  $upperVector = array();
  foreach ($lowerVector as $vectorElement) $upperVector[] = strtoupper((string)$vectorElement);
  list ($lowArgs, $lowCount, $lowCommand, $lowTarget, $lowSecond, $lowConfirmed, $lowParsed) = parseArgumentVector($lowerVector);
  list ($upArgs, $upCount, $upCommand, $upTarget, $upSecond, $upConfirmed, $upParsed) = parseArgumentVector($upperVector);
  $lowFields = array($lowCount, $lowCommand, $lowTarget, $lowSecond, $lowConfirmed);
  $upFields = array($upCount, $upCommand, $upTarget, $upSecond, $upConfirmed);
  foreach ($fieldNames as $fieldIndex => $fieldName) {
    if ($lowFields[$fieldIndex] !== $upFields[$fieldIndex]) {
      print('  NOT CASE INSENSITIVE  '.$caseName.': '.$fieldName.' is '.var_export($lowFields[$fieldIndex], TRUE).' as typed & '.var_export($upFields[$fieldIndex], TRUE).' upper cased.'.PHP_EOL);
      $caseFailures++; } } }

// / PROVE THIS TEST CAN FAIL.
// / A test that has never failed has never been shown to test anything. The comparison is
// / run once against a deliberately wrong answer & must report it.
$provingResult = array(99, '--wrong', 'wrong', 'wrong', TRUE);
list ($proveArgs, $proveCount, $proveCommand, $proveTarget, $proveSecond, $proveConfirmed, $proveParsed) = parseArgumentVector(array('convertCore.php', '-v'));
$provingCaught = 0;
$realResult = array($proveCount, $proveCommand, $proveTarget, $proveSecond, $proveConfirmed);
foreach ($fieldNames as $fieldIndex => $fieldName) {
  if ($realResult[$fieldIndex] !== $provingResult[$fieldIndex]) $provingCaught++; }
if ($provingCaught !== 5) {
  print('  WRONG  the comparison did not catch five planted wrong answers. It caught '.$provingCaught.'.'.PHP_EOL);
  $wrongAnswers++; }

print('  '.count($cases).' vectors tested, '.$wrongAnswers.' wrong'.PHP_EOL);
print('  '.count($cases).' vectors compared against the inline parser, '.$disagreements.' disagreements'.PHP_EOL);
print('  '.count($cases).' vectors re-parsed upper cased, '.$caseFailures.' case sensitivities'.PHP_EOL);
print('  the comparison caught '.$provingCaught.' of 5 planted wrong answers'.PHP_EOL);
if ($wrongAnswers === 0 and $disagreements === 0 and $caseFailures === 0) { print('  PASS'.PHP_EOL); exit(0); }
print('  FAIL'.PHP_EOL);
exit(1);
