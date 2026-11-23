<?php
// HRProprietary Engine v4.3.2. The unit suite of the Quarantine pipeline & the kernel's enterFolderWithoutFollowing().
// It was HRProtect's tests/test_quarantine.php, from its v0.0.7, which the Engine's library took with the pipeline in v4.3.1.
//
//   sudo bash Documentation/Build/Tests/contained.sh php Documentation/Build/Tests/test_quarantine-cli.php
// Run it from the root of the application under test. A tree named after it is tested instead, & must be that root.
//
// The pipeline moves, copies, puts back & removes files. Every one of those is its own code, lifted as the tree has it,
// & every file it touches is beneath one of the two scratch folders.
// The suite itself removes nothing. Where a check needs a name gone, or a folder begun again, what was there is set aside
// under another name by the library's setAside().
// One check mounts a tmpfs with room for two names beneath the scratch folder, & leaves it mounted.
// The suite joins names onto a scratch path itself, because it tests names scratchPath() would refuse.
// Sections 18 & 19 are from HRProtect's v0.0.6, & hold what a change by rule to every line of the pipeline showed was held only in
// part, or not at all. For section 19 the pipeline is lifted a second time, into a namespace, where a check can stand in for one call
// the pipeline makes. Section 20 is from v0.0.6 as well. For it the library has another process move a name, with mv, so that PHP
// remembers a path as leading elsewhere. Section 21 is the Engine's own, from v4.3.2.

if (PHP_SAPI !== 'cli') { if (!headers_sent()) http_response_code(404); die(); }

if (!is_file('Documentation/Build/Tests/testlib-cli.php')) { fwrite(STDERR, 'Run this from the root of the application it tests. See Documentation/Build/Tests/README.txt.'.PHP_EOL); exit(64); }
require 'Documentation/Build/Tests/testlib-cli.php';

$tree = testTree(isset($argv[1]) ? $argv[1] : (string)getcwd());
requireContainment();

// What the kernel would have set before any of these functions ran.
$EnableMemoryProtection = TRUE;
$Verbose = FALSE;
$LogFile = '';
$RunningAsRoot = TRUE;
$DirSep = DIRECTORY_SEPARATOR;
$Lol = PHP_EOL;

$base = makeFolder(scratchPath('quarantine'), 0755);
$other = makeFolder(otherPath('quarantine'), 0755);
$kernel = $tree.'/Resources/Kernel/kernel.php';
$pipeline = $tree.'/Resources/Pipelines/Quarantine/pipeline.php';
// The kernel writes a file by renaming one it finished, & removes one by unlinking it. The pipeline removes what it copied & what it purges.
// Each is what is under test, & each is handed nothing but paths beneath the scratch folders.
liftFunctions($kernel, array('getChildFunction', 'purgeSensitiveMemory', 'redeclare', 'printableText', 'pathIsTrustedByRoot', 'findAccountDatabaseEntry', 'identifyRunningAccount', 'folderIsHeldForRoot', 'enterFolderWithoutFollowing', 'writeFileWithoutFollowing', 'readFileWithoutFollowing', 'removeFileWithoutFollowing', 'changeOwnerWithoutFollowing'), array('rename', 'unlink', 'removeFileWithoutFollowing'));
liftFunctions($pipeline, array('quarantineIdIsValid', 'copyHandleToNewFile', 'readQuarantineRecord', 'listQuarantinedFiles', 'quarantineIsRootsAlone', 'quarantineFile', 'restoreQuarantinedFile', 'purgeQuarantinedFile', 'operateOnQuarantine'), array('unlink', 'removeFileWithoutFollowing'));
// The same nine a second time, in a namespace, with a stand-in for each call a check of section 19 takes the place of.
standInFor('hooked', array('fopen', 'fstat', 'lstat', 'link', 'stream_copy_to_stream', 'fflush', 'hash_file', 'posix_geteuid', 'function_exists', 'chdir', 'stat', 'folderIsHeldForRoot', 'changeOwnerWithoutFollowing', 'removeFileWithoutFollowing'));
liftFunctionsInto('hooked', $pipeline, array('quarantineIdIsValid', 'copyHandleToNewFile', 'readQuarantineRecord', 'listQuarantinedFiles', 'quarantineIsRootsAlone', 'quarantineFile', 'restoreQuarantinedFile', 'purgeQuarantinedFile', 'operateOnQuarantine'), array('unlink', 'removeFileWithoutFollowing'));

// Begins a folder again. What was there is set aside, & nothing is removed.
function freshFolder($path, $mode, $owner) { mustBeScratch($path); clearstatcache(TRUE); if (file_exists($path) or is_link($path)) setAside($path); mkdir($path, $mode, TRUE); chmod($path, $mode); chown($path, $owner); chgrp($path, $owner === 'root' ? 'root' : $owner); }
function makeFile($path, $contents, $mode, $owner) { mustBeScratch($path); file_put_contents($path, $contents); chmod($path, $mode); chown($path, $owner); chgrp($path, $owner === 'root' ? 'root' : $owner); touch($path, 1500000000); clearstatcache(TRUE); return lstat($path); }
function identityOf($status) { return array('Device' => $status['dev'], 'Inode' => $status['ino'], 'Size' => $status['size'], 'Modified' => $status['mtime']); }

check('runs as root', posix_geteuid() === 0);
$quarantine = $base.'/Quarantine';
$user = $base.'/user';
freshFolder($base, 0755, 'root');
freshFolder($quarantine, 0700, 'root');
freshFolder($user, 0755, 'www-data');
$startFolder = getcwd();

// 1. A file with one name, owned by another account, is moved, & nothing is copied.
$before = makeFile($user.'/evil.bin', 'malicious bytes', 0640, 'www-data');
list ($done, $id, $notice) = operateOnQuarantine('quarantine', $quarantine, array('Path' => $user.'/evil.bin', 'Identity' => identityOf($before), 'Details' => array('Verdict' => 'infected')));
check('quarantine succeeds', $done === TRUE && quarantineIdIsValid($id), $notice);
clearstatcache(TRUE);
check('the name is gone', !file_exists($user.'/evil.bin'));
$stored = @lstat($quarantine.'/'.$id.'.quarantined');
check('the same file is kept, not a copy', is_array($stored) && $stored['ino'] === $before['ino'] && $stored['nlink'] === 1);
check('the kept file is root\'s & 0600', is_array($stored) && $stored['uid'] === 0 && $stored['gid'] === 0 && ($stored['mode'] & 07777) === 0600);
check('the working folder is given back', getcwd() === $startFolder);
list ($read, $record, $notice) = operateOnQuarantine('read', $quarantine, array('Id' => $id));
check('the record reads back', $read && $record['Owner'] === 33 && $record['Group'] === 33 && $record['Mode'] === 0640 && $record['Size'] === 15 && $record['Modified'] === 1500000000 && $record['Method'] === 'moved' && $record['OriginalPathBytes'] === $user.'/evil.bin' && $record['Details']['Verdict'] === 'infected' && $record['StoredExists'] === TRUE, $notice);
$recordStatus = lstat($quarantine.'/'.$id.'.json');
check('the record is root\'s & 0600', $recordStatus['uid'] === 0 && ($recordStatus['mode'] & 07777) === 0600);
list ($listed, $listing, $notice) = operateOnQuarantine('list', $quarantine, array());
check('the listing holds it', $listed && count($listing['Records']) === 1 && $listing['Records'][0]['Id'] === $id && $listing['Unknown'] === array());

// 2. It goes back exactly as it left.
list ($done, $path, $notice) = operateOnQuarantine('restore', $quarantine, array('Id' => $id));
clearstatcache(TRUE);
$after = @lstat($user.'/evil.bin');
check('restore succeeds with nothing to say', $done === TRUE && $path === $user.'/evil.bin' && $notice === '', $notice);
check('the same file is back with its owner, group, mode & time', is_array($after) && $after['ino'] === $before['ino'] && $after['uid'] === 33 && $after['gid'] === 33 && ($after['mode'] & 07777) === 0640 && $after['mtime'] === 1500000000 && file_get_contents($user.'/evil.bin') === 'malicious bytes');
check('the quarantine is empty again', count(scandir($quarantine)) === 2);

// 3. A file that is not the file that was judged is left alone.
$wrong = identityOf($before); $wrong['Size'] = 99;
list ($done, $id, $notice) = quarantineFile($quarantine, $user.'/evil.bin', $wrong, array());
check('a changed file is refused', $done === FALSE && $id === '' && strpos($notice, 'changed after it was judged') !== FALSE && file_exists($user.'/evil.bin'), $notice);
$wrong = identityOf($before); $wrong['Inode'] = $before['ino'] + 1;
list ($done, $id, $notice) = quarantineFile($quarantine, $user.'/evil.bin', $wrong, array());
check('a replaced file is refused', $done === FALSE && file_exists($user.'/evil.bin') && count(scandir($quarantine)) === 2, $notice);

// 4. A link is not a file.
if (!symlink('/etc/hostname', mustBeScratch($user.'/link'))) testStop('A link could not be made.');
list ($done, $id, $notice) = quarantineFile($quarantine, $user.'/link', array(), array());
check('a link is refused', $done === FALSE && strpos($notice, 'not a plain file') !== FALSE && is_link($user.'/link') && file_exists('/etc/hostname'), $notice);
mkdir($user.'/folder');
list ($done, $id, $notice) = quarantineFile($quarantine, $user.'/folder', array(), array());
check('a folder is refused', $done === FALSE && is_dir($user.'/folder'), $notice);

// 5. A link on the way to the file is never followed.
freshFolder($base.'/elsewhere', 0755, 'root');
makeFile($base.'/elsewhere/victim', 'a file root must not be led to', 0600, 'root');
if (!symlink($base.'/elsewhere', mustBeScratch($user.'/swapped'))) testStop('A link could not be made.');
list ($done, $id, $notice) = quarantineFile($quarantine, $user.'/swapped/victim', array(), array());
check('a path through a link is refused', $done === FALSE && strpos($notice, 'is a link') !== FALSE && file_exists($base.'/elsewhere/victim'), $notice);
check('the working folder is given back after a refusal', getcwd() === $startFolder);
list ($done, $id, $notice) = quarantineFile($quarantine, 'relative/path', array(), array());
check('a relative path is refused', $done === FALSE, $notice);
list ($done, $id, $notice) = quarantineFile($quarantine, $user.'/..', array(), array());
check('a dot path is refused', $done === FALSE, $notice);
list ($done, $id, $notice) = quarantineFile($quarantine, $user.'/missing', array(), array());
check('a missing file is refused', $done === FALSE && strpos($notice, 'no longer there') !== FALSE, $notice);

// 6. A file with more than one name is copied, & its other names are left exactly as they are.
$before = makeFile($user.'/twin-a', 'two names', 0644, 'www-data');
link($user.'/twin-a', $user.'/twin-b');
clearstatcache(TRUE);
$before = lstat($user.'/twin-a');
list ($done, $id, $notice) = quarantineFile($quarantine, $user.'/twin-a', identityOf($before), array());
clearstatcache(TRUE);
$twin = @lstat($user.'/twin-b');
$stored = @lstat($quarantine.'/'.$id.'.quarantined');
check('a file with two names is quarantined by copy', $done === TRUE && strpos($notice, 'other name') !== FALSE && !file_exists($user.'/twin-a'), $notice);
check('its other name is untouched', is_array($twin) && $twin['ino'] === $before['ino'] && $twin['uid'] === 33 && ($twin['mode'] & 07777) === 0644 && $twin['nlink'] === 1);
check('the copy is another file, root\'s, 0600, with the time kept', is_array($stored) && $stored['ino'] !== $before['ino'] && $stored['uid'] === 0 && ($stored['mode'] & 07777) === 0600 && $stored['mtime'] === 1500000000 && $stored['nlink'] === 1);
list ($done, $path, $notice) = restoreQuarantinedFile($quarantine, $id, '');
clearstatcache(TRUE);
$after = @lstat($user.'/twin-a');
check('the copy goes back with owner, mode & time', $done === TRUE && is_array($after) && $after['uid'] === 33 && ($after['mode'] & 07777) === 0644 && $after['mtime'] === 1500000000 && file_get_contents($user.'/twin-a') === 'two names', $notice);

// 7. Another filesystem. The file is copied in, & copied back only into a folder nothing but root can replace.
freshFolder($other, 0755, 'root');
freshFolder($other.'/rootdir', 0755, 'root');
freshFolder($other.'/userdir', 0755, 'www-data');
freshFolder($other.'/userdir/sub', 0755, 'www-data');
$before = makeFile($other.'/rootdir/far.bin', 'far away', 0604, 'www-data');
list ($done, $id, $notice) = quarantineFile($quarantine, $other.'/rootdir/far.bin', identityOf($before), array());
list ($read, $record, $readNotice) = readQuarantineRecord($quarantine, $id);
check('a file on another filesystem is copied in', $done === TRUE && !file_exists($other.'/rootdir/far.bin') && $read && $record['Method'] === 'copied' && file_get_contents($quarantine.'/'.$id.'.quarantined') === 'far away', $notice);
list ($done, $path, $notice) = restoreQuarantinedFile($quarantine, $id, '');
clearstatcache(TRUE);
$after = @lstat($other.'/rootdir/far.bin');
check('it is copied back into a folder that is root\'s alone, as it was', $done === TRUE && $notice === '' && is_array($after) && $after['uid'] === 33 && $after['gid'] === 33 && ($after['mode'] & 07777) === 0604 && $after['mtime'] === 1500000000 && file_get_contents($other.'/rootdir/far.bin') === 'far away', $notice);
check('the quarantine is empty after the copy back', count(scandir($quarantine)) === 2);
$before = makeFile($other.'/userdir/held.bin', 'held', 0644, 'www-data');
list ($done, $id, $notice) = quarantineFile($quarantine, $other.'/userdir/held.bin', identityOf($before), array());
list ($done, $path, $notice) = restoreQuarantinedFile($quarantine, $id, '');
clearstatcache(TRUE);
$after = @lstat($other.'/userdir/held.bin');
check('into a held folder another account owns it goes back as root\'s & 0600, & says so', $done === TRUE && is_array($after) && $after['uid'] === 0 && ($after['mode'] & 07777) === 0600 && strpos($notice, 'by hand') !== FALSE && strpos($notice, '0644') !== FALSE, $notice);
setAside($other.'/userdir/held.bin');
$before = makeFile($other.'/userdir/sub/deep.bin', 'deep', 0644, 'www-data');
list ($done, $id, $notice) = quarantineFile($quarantine, $other.'/userdir/sub/deep.bin', identityOf($before), array());
check('a file is copied out of a folder nothing holds', $done === TRUE && !file_exists($other.'/userdir/sub/deep.bin'), $notice);
list ($done, $path, $notice) = restoreQuarantinedFile($quarantine, $id, '');
check('it is never created by name in a folder another account could replace', $done === FALSE && $path === '' && strpos($notice, 'never created by name') !== FALSE && !file_exists($other.'/userdir/sub/deep.bin') && file_exists($quarantine.'/'.$id.'.quarantined'), $notice);
clearstatcache(TRUE);
$stored = lstat($quarantine.'/'.$id.'.quarantined');
check('the refused file is still root\'s & 0600', $stored['uid'] === 0 && ($stored['mode'] & 07777) === 0600);
list ($done, $path, $notice) = restoreQuarantinedFile($quarantine, $id, $other.'/rootdir');
check('the same file goes back into a folder the caller names', $done === TRUE && $path === $other.'/rootdir/deep.bin' && file_get_contents($other.'/rootdir/deep.bin') === 'deep', $notice);

// 8. Nothing is written over.
$before = makeFile($user.'/taken', 'first', 0644, 'www-data');
list ($done, $id, $notice) = quarantineFile($quarantine, $user.'/taken', identityOf($before), array());
makeFile($user.'/taken', 'second', 0644, 'www-data');
list ($done, $path, $notice) = restoreQuarantinedFile($quarantine, $id, '');
check('a name that exists stops a restore', $done === FALSE && strpos($notice, 'already exists') !== FALSE && file_get_contents($user.'/taken') === 'second' && file_exists($quarantine.'/'.$id.'.quarantined'), $notice);
clearstatcache(TRUE);
$stored = lstat($quarantine.'/'.$id.'.quarantined');
check('a stopped restore leaves the kept file root\'s & 0600', $stored['uid'] === 0 && ($stored['mode'] & 07777) === 0600);

// 9. A file that changed in the quarantine still goes back, & says so.
file_put_contents($quarantine.'/'.$id.'.quarantined', 'written since');
setAside($user.'/taken');
list ($done, $path, $notice) = restoreQuarantinedFile($quarantine, $id, '');
check('a changed file goes back with a notice', $done === TRUE && strpos($notice, 'not as it was') !== FALSE && file_get_contents($user.'/taken') === 'written since', $notice);

// 10. Purge.
$before = makeFile($user.'/purged', 'gone for good', 0644, 'www-data');
list ($done, $id, $notice) = quarantineFile($quarantine, $user.'/purged', identityOf($before), array());
list ($done, $notice) = purgeQuarantinedFile($quarantine, $id);
check('purge removes the file & its record', $done === TRUE && $notice === '' && count(scandir($quarantine)) === 2, $notice);
list ($done, $notice) = purgeQuarantinedFile($quarantine, $id);
check('purging nothing says so', $done === FALSE && strpos($notice, 'holds nothing') !== FALSE, $notice);
list ($done, $notice) = purgeQuarantinedFile($quarantine, '../../etc/passwd');
check('a purge is never led by its identifier', $done === FALSE && strpos($notice, 'not the identifier') !== FALSE, $notice);
list ($done, $result, $notice) = operateOnQuarantine('restore', $quarantine, array('Id' => '../x'));
check('a restore is never led by its identifier', $done === FALSE, $notice);

// 11. A quarantine that is not root's alone is refused, whatever is asked.
$before = makeFile($user.'/stays', 'stays', 0644, 'www-data');
chmod($quarantine, 0755);
list ($done, $id, $notice) = quarantineFile($quarantine, $user.'/stays', identityOf($before), array());
check('an open quarantine is refused', $done === FALSE && strpos($notice, 'can be opened by an account other than root') !== FALSE && file_exists($user.'/stays'), $notice);
chmod($quarantine, 0700);
chown($quarantine, 'www-data');
list ($done, $id, $notice) = quarantineFile($quarantine, $user.'/stays', identityOf($before), array());
check('a quarantine another account owns is refused', $done === FALSE && file_exists($user.'/stays'), $notice);
chown($quarantine, 'root');
if (!symlink($quarantine, mustBeScratch($base.'/QuarantineLink'))) testStop('A link could not be made.');
list ($done, $id, $notice) = quarantineFile($base.'/QuarantineLink', $user.'/stays', identityOf($before), array());
check('a quarantine that is a link is refused', $done === FALSE && file_exists($user.'/stays'), $notice);
freshFolder($user.'/Quarantine', 0700, 'root');
list ($done, $id, $notice) = quarantineFile($user.'/Quarantine', $user.'/stays', identityOf($before), array());
check('a quarantine inside another account\'s folder is refused', $done === FALSE && strpos($notice, 'only root can replace') !== FALSE && file_exists($user.'/stays'), $notice);
list ($done, $notice) = purgeQuarantinedFile($user.'/Quarantine', '20261116-064500-0123456789ab');
check('a purge there is refused too', $done === FALSE, $notice);

// 12. A record is checked before it is believed.
list ($done, $id, $notice) = quarantineFile($quarantine, $user.'/stays', identityOf($before), array());
$recordFile = $quarantine.'/'.$id.'.json';
$good = file_get_contents($recordFile);
$decoded = json_decode($good, TRUE);
foreach (array('not json at all', json_encode(array_merge($decoded, array('OriginalPathEncoded' => base64_encode('relative/path')))), json_encode(array_merge($decoded, array('OriginalPathEncoded' => '***'))), json_encode(array_merge($decoded, array('Owner' => 'root'))), json_encode(array_merge($decoded, array('Id' => '20200101-000000-aaaaaaaaaaaa'))), json_encode(array_merge($decoded, array('Sha256' => 'short'))), json_encode(array_diff_key($decoded, array('Mode' => 1)))) as $index => $forged) {
  file_put_contents($recordFile, $forged);
  list ($read, $record, $notice) = readQuarantineRecord($quarantine, $id);
  check('forged record '.$index.' is refused', $read === FALSE && $record === array(), $notice);
  list ($done, $path, $notice) = restoreQuarantinedFile($quarantine, $id, '');
  check('forged record '.$index.' restores nothing', $done === FALSE && !file_exists($user.'/stays'), $notice); }
list ($listed, $records, $unknown) = listQuarantinedFiles($quarantine);
check('a file whose record cannot be read is named, not passed over', $listed && count($records) === 0 && in_array($id.'.json', $unknown, TRUE) && in_array($id.'.quarantined', $unknown, TRUE));
list ($done, $notice) = purgeQuarantinedFile($quarantine, $id);
check('it can still be purged by its identifier', $done === TRUE && count(scandir($quarantine)) === 2, $notice);

// 13. An owner with no name on this host cannot be given a file by name.
$before = makeFile($user.'/orphan', 'orphan', 04755, 'www-data');
chown($user.'/orphan', 54321); chgrp($user.'/orphan', 54321); chmod($user.'/orphan', 04755); touch($user.'/orphan', 1500000000); clearstatcache(TRUE);
$before = lstat($user.'/orphan');
list ($done, $id, $notice) = quarantineFile($quarantine, $user.'/orphan', identityOf($before), array());
list ($done, $path, $notice) = restoreQuarantinedFile($quarantine, $id, '');
clearstatcache(TRUE);
$after = lstat($user.'/orphan');
check('a file whose owner has no name goes back as root\'s & 0600, & says what it was', $done === TRUE && $after['uid'] === 0 && ($after['mode'] & 07777) === 0600 && strpos($notice, '54321') !== FALSE && strpos($notice, '4755') !== FALSE, $notice);

// 14. A mode with a setuid bit goes back whole, because the mode is set after the owner.
$before = makeFile($user.'/setuid', 'setuid', 04755, 'www-data');
chmod($user.'/setuid', 04755); touch($user.'/setuid', 1500000000); clearstatcache(TRUE);
$before = lstat($user.'/setuid');
list ($done, $id, $notice) = quarantineFile($quarantine, $user.'/setuid', identityOf($before), array());
clearstatcache(TRUE);
$stored = lstat($quarantine.'/'.$id.'.quarantined');
check('a setuid file is not setuid in the quarantine', ($stored['mode'] & 07777) === 0600 && $stored['uid'] === 0);
list ($done, $path, $notice) = restoreQuarantinedFile($quarantine, $id, '');
clearstatcache(TRUE);
$after = lstat($user.'/setuid');
check('it goes back setuid & its owner\'s', $done === TRUE && $after['uid'] === 33 && ($after['mode'] & 07777) === 04755, $notice);

// 15. A name built to forge a line goes in & comes back byte for byte, & is never printed raw.
$hostileName = "a\nInfected: etc-passwd\x1b[2J \xff end";
$before = makeFile($user.'/'.$hostileName, 'hostile name', 0644, 'www-data');
list ($done, $id, $notice) = quarantineFile($quarantine, $user.'/'.$hostileName, identityOf($before), array());
list ($read, $record, $readNotice) = readQuarantineRecord($quarantine, $id);
check('a hostile name is quarantined', $done === TRUE && $read === TRUE, $notice.$readNotice);
check('its record prints no control character', $read && preg_match('/[\x00-\x1F\x7F]/', $record['OriginalPath']) === 0 && $record['OriginalPathBytes'] === $user.'/'.$hostileName);
check('the record file is one JSON document', is_array(json_decode(file_get_contents($quarantine.'/'.$id.'.json'), TRUE)));
list ($done, $path, $notice) = restoreQuarantinedFile($quarantine, $id, '');
check('it comes back under the same bytes', $done === TRUE && file_exists($user.'/'.$hostileName) && $path === $user.'/'.$hostileName, $notice);
check('printable text keeps UTF-8 & drops controls', printableText("caf\xc3\xa9\r\n\x9b", TRUE) !== '' && preg_match('/[\x00-\x1F\x7F]/', printableText("caf\xc3\xa9\r\n", TRUE)) === 0 && strpos(printableText("caf\xc3\xa9", TRUE), "caf\xc3\xa9") === 0 && strlen(printableText(str_repeat('x', 5000), TRUE)) < 1100);

// 16. What is in the folder & is not this pipeline's is named.
file_put_contents($quarantine.'/stray.txt', 'x');
list ($listed, $records, $unknown) = listQuarantinedFiles($quarantine);
check('a stray file is named', $listed && $unknown === array('stray.txt'));
list ($listed, $records, $unknown) = listQuarantinedFiles($base.'/missing');
check('a missing quarantine is not listed as empty', $listed === FALSE);
list ($done, $result, $notice) = operateOnQuarantine('shred', $quarantine, array());
check('an unknown operation is refused', $done === FALSE && strpos($notice, 'no operation called') !== FALSE, $notice);
check('identifiers', quarantineIdIsValid('20261116-064500-0123456789ab') && !quarantineIdIsValid('20261116-064500-0123456789AB') && !quarantineIdIsValid('') && !quarantineIdIsValid(array()) && !quarantineIdIsValid("20261116-064500-0123456789ab\n"));

// 16b. A record that cannot be written leaves the file exactly where it was. The quarantine here has room for one file & no more.
freshFolder($base.'/TinyQ', 0700, 'root');
list ($mountStatus, $mountSaid) = runCommand(array('mount', '-t', 'tmpfs', '-o', 'mode=0700,nr_inodes=2', 'tmpfs', $base.'/TinyQ'));
check('a tmpfs with room for two names could be mounted beneath the scratch folder', $mountStatus === 0, $mountSaid);
$before = makeFile($user.'/norecord', 'no room for a record', 0644, 'www-data');
list ($done, $id, $notice) = quarantineFile($base.'/TinyQ', $user.'/norecord', identityOf($before), array());
clearstatcache(TRUE);
check('a file whose record cannot be written is left where it is', $done === FALSE && $id === '' && strpos($notice, 'its record could not be written') !== FALSE && file_get_contents($user.'/norecord') === 'no room for a record', $notice);
check('nothing of it is left in the quarantine', count(scandir($base.'/TinyQ')) === 2);
// The tmpfs is left mounted. It is beneath the scratch folder, & is gone with it when the contained run ends.

// 16c. A quarantine that was interrupted between giving a file its second name & removing its first leaves both, & a purge takes away only the quarantine's.
$before = makeFile($user.'/interrupted', 'both names', 0640, 'www-data');
$halfId = '20260101-010101-bbbbbbbbbbbb';
link($user.'/interrupted', $quarantine.'/'.$halfId.'.quarantined');
file_put_contents($quarantine.'/'.$halfId.'.json', json_encode(array('Id' => $halfId, 'OriginalPath' => $user.'/interrupted', 'OriginalPathEncoded' => base64_encode($user.'/interrupted'), 'Owner' => 33, 'Group' => 33, 'Mode' => 0640, 'Size' => 10, 'Modified' => 1500000000, 'Sha256' => hash('sha256', 'both names'), 'QuarantinedAt' => time(), 'Method' => 'moved', 'OtherNames' => 0, 'Details' => array())));
list ($done, $path, $notice) = restoreQuarantinedFile($quarantine, $halfId, '');
check('a restore over the name that was never removed is stopped', $done === FALSE && strpos($notice, 'already exists') !== FALSE, $notice);
list ($done, $notice) = purgeQuarantinedFile($quarantine, $halfId);
clearstatcache(TRUE);
$after = lstat($user.'/interrupted');
check('a purge of it leaves the file where it was, as it was', $done === TRUE && $after['ino'] === $before['ino'] && $after['uid'] === 33 && ($after['mode'] & 07777) === 0640 && $after['nlink'] === 1 && file_get_contents($user.'/interrupted') === 'both names', $notice);

// 17. The kernel's folder walk by itself. It was lifted a second time as well, for section 17b.
liftFunctionsInto('hooked', $kernel, array('enterFolderWithoutFollowing'), array());
list ($entered, $notice) = enterFolderWithoutFollowing($user);
check('a real folder is entered', $entered === TRUE && getcwd() === $user, $notice);
list ($entered, $notice) = enterFolderWithoutFollowing($user.'/swapped');
check('a link is not entered', $entered === FALSE && strpos($notice, 'is a link') !== FALSE, $notice);
list ($entered, $notice) = enterFolderWithoutFollowing($user.'/taken');
check('a file is not entered', $entered === FALSE && strpos($notice, 'is not a folder') !== FALSE, $notice);
list ($entered, $notice) = enterFolderWithoutFollowing($user.'/../user');
check('a path with dots is not entered', $entered === FALSE, $notice);
list ($entered, $notice) = enterFolderWithoutFollowing('user');
check('a relative path is not entered', $entered === FALSE, $notice);
list ($entered, $notice) = enterFolderWithoutFollowing('etc');
check('a relative path is not read as one from the filesystem root', $entered === FALSE && strpos($notice, 'not an absolute path') !== FALSE, $notice);
list ($entered, $notice) = enterFolderWithoutFollowing($user."/x\0y");
check('a path with a null byte is not entered', $entered === FALSE, $notice);
list ($entered, $notice) = enterFolderWithoutFollowing('/');
check('the filesystem root is entered', $entered === TRUE && getcwd() === '/', $notice);

// 17b. The walk stops at the first name it cannot enter. A folder swapped while it was being entered is caught. From v0.0.6.
list ($entered, $notice) = enterFolderWithoutFollowing($user.'/nowhere/deeper');
check('the walk names the first name that is not there, & stops in the folder above it', $entered === FALSE && $notice === $user.'/nowhere does not exist.' && getcwd() === $user, $notice);
freshFolder($base.'/walked', 0755, 'root');
freshFolder($base.'/walked/inner', 0755, 'root');
freshFolder($base.'/walked/other', 0755, 'root');
setHooks(array('chdir' => function ($arguments, $count) use ($base) { if ($arguments[0] === 'inner') { setAside($base.'/walked/inner'); rename($base.'/walked/other', $base.'/walked/inner'); } return NULL; }));
list ($entered, $notice) = \hooked\enterFolderWithoutFollowing($base.'/walked/inner');
check('a folder swapped for another between the look at it & the step into it is caught', $entered === FALSE && $notice === $base.'/walked/inner changed while it was being entered.', $notice);
setHooks(array('stat' => answer(FALSE)));
list ($entered, $notice) = \hooked\enterFolderWithoutFollowing($base.'/walked');
check('a folder that cannot be examined once it is entered is not called entered', $entered === FALSE && $notice === '/'.explode('/', $base)[1].' changed while it was being entered.', $notice);
setHooks(array());
chdir($startFolder);

// What sections 18 & 19 use. A file with an owner & a group by number, a file with a second name, what a folder holds,
// text as printableText() cuts it, & a hook that answers one value every time it is asked.
function ownedFile($path, $contents, $mode, $owner, $group) { mustBeScratch($path); file_put_contents($path, $contents); chown($path, $owner); chgrp($path, $group); chmod($path, $mode); touch($path, 1500000000); clearstatcache(TRUE); return lstat($path); }
function twoNames($path, $contents, $mode) { makeFile($path, $contents, $mode, 'www-data'); link($path, $path.'-other'); clearstatcache(TRUE); return lstat($path); }
function namesIn($folder) { clearstatcache(TRUE); $names = array_values(array_diff((array)scandir($folder), array('.', '..'))); sort($names, SORT_STRING); return $names; }
function cut($text) { return (strlen($text) > 1000) ? substr($text, 0, 1000).'...' : $text; }
function answer($value) { return function ($arguments, $count) use ($value) { return array('answer', $value); }; }

// 18. What the pipeline says & keeps, to the letter. A change by rule to every line of it showed that the checks above held
// much of this only in part: a notice by one word of it, a record by some of its fields, a refusal only by its being one.
$q2 = $base.'/Quarantine2';
$u2 = $base.'/user2';
$r2 = $base.'/root2';
freshFolder($q2, 0700, 'root');
freshFolder($u2, 0755, 'www-data');
freshFolder($r2, 0755, 'root');
$longFolder = $u2.'/'.implode('/', array_fill(0, 6, str_repeat('L', 200)));
makeFolder($longFolder, 0755);

// 18a. A file that is moved whole says nothing, & its record counts its names.
$before = makeFile($u2.'/plain', 'plain', 0644, 'www-data');
list ($done, $plainId, $notice) = quarantineFile($q2, $u2.'/plain', identityOf($before), array());
list ($read, $record, $readNotice) = readQuarantineRecord($q2, $plainId);
check('a file that is moved whole is quarantined with nothing to say', $done === TRUE && $notice === '', $notice);
check('its record says it has no other name, & that it was moved', $read === TRUE && field($record, 'OtherNames') === 0 && field($record, 'Method') === 'moved', $readNotice);
$before = twoNames($u2.'/twin', 'twin', 0645);
list ($done, $twinId, $notice) = quarantineFile($q2, $u2.'/twin', identityOf($before), array());
list ($read, $record, $readNotice) = readQuarantineRecord($q2, $twinId);
check('a file any account may run, with a second name, is copied in, & says exactly how many names it has', $done === TRUE && $notice === 'It has 1 other name(s), which were left as they are. This name was removed & a copy is kept.', $notice);
check('its record says so as well', $read === TRUE && field($record, 'OtherNames') === 1 && field($record, 'Method') === 'copied', $readNotice);

// 18b. What is not the absolute path of a file is refused as that, before any folder is entered. A file that is gone is said to be gone.
foreach (array('relative/path', DIRECTORY_SEPARATOR, $u2.'/.', $u2.'/..') as $index => $badPath) {
  list ($done, $id, $notice) = quarantineFile($q2, $badPath, array(), array());
  check('path '.$index.' is refused as no path of a file', $done === FALSE && $id === '' && $notice === $badPath.' is not the absolute path of a file.', $notice); }
list ($done, $id, $notice) = quarantineFile($q2, $u2.'/never-there', identityOf($before), array());
check('a file that is gone is said to be gone, whatever it was said to have been', $done === FALSE && $id === '' && $notice === $u2.'/never-there is no longer there.', $notice);
list ($private, $notice) = quarantineIsRootsAlone('');
check('no folder at all is refused as one root cannot hold', $private === FALSE && $notice === 'The quarantine at  is not a folder only root can replace.', $notice);

// 18c. A record is refused for each thing it must hold, one at a time, & says which kind of thing is wrong.
$before = makeFile($u2.'/forged', 'forged', 0644, 'www-data');
list ($done, $forgedId, $notice) = quarantineFile($q2, $u2.'/forged', identityOf($before), array());
$recordFile = $q2.'/'.$forgedId.'.json';
$good = json_decode((string)file_get_contents($recordFile), TRUE);
$encodedPath = base64_encode('/tmp/xyz');
$noPath = $good;
unset($noPath['OriginalPathEncoded']);
$noOrigin = 'does not say where the file came from.';
$noMeans = 'is missing what a file is put back with.';
$forgeries = array(
  'no path' => array($noPath, $noOrigin),
  'a path that is not base64 all through' => array(array_merge($good, array('OriginalPathEncoded' => substr($encodedPath, 0, 4).'!'.substr($encodedPath, 4))), $noOrigin),
  'a path that is not text' => array(array_merge($good, array('OriginalPathEncoded' => 5)), $noOrigin),
  'an empty path' => array(array_merge($good, array('OriginalPathEncoded' => '')), $noOrigin),
  'a path that does not begin at the root' => array(array_merge($good, array('OriginalPathEncoded' => base64_encode('tmp/xyz'))), $noOrigin),
  'a path with a null byte' => array(array_merge($good, array('OriginalPathEncoded' => base64_encode("/tmp/x\0yz"))), $noOrigin),
  'the filesystem root for a path' => array(array_merge($good, array('OriginalPathEncoded' => base64_encode('/'))), $noOrigin),
  'an owner that is no number' => array(array_merge($good, array('Owner' => '33')), $noMeans),
  'a group that is no number' => array(array_merge($good, array('Group' => '33')), $noMeans),
  'a mode that is no number' => array(array_merge($good, array('Mode' => '420')), $noMeans),
  'a size that is no number' => array(array_merge($good, array('Size' => '6')), $noMeans),
  'a time that is no number' => array(array_merge($good, array('Modified' => '1500000000')), $noMeans),
  'a hash that is no text' => array(array_merge($good, array('Sha256' => 5)), $noMeans),
  'a hash that is a digit short' => array(array_merge($good, array('Sha256' => substr($good['Sha256'], 0, 63))), $noMeans),
  'a hash in capitals' => array(array_merge($good, array('Sha256' => strtoupper($good['Sha256']))), $noMeans),
  'a hash with a line after it' => array(array_merge($good, array('Sha256' => $good['Sha256']."\n")), $noMeans),
  'the identifier of another file' => array(array_merge($good, array('Id' => $plainId)), 'could not be read as a record.'));
foreach ($forgeries as $label => $forgery) {
  file_put_contents($recordFile, json_encode($forgery[0]));
  list ($read, $record, $notice) = readQuarantineRecord($q2, $forgedId);
  check('a record with '.$label.' is refused, & says why', $read === FALSE && $record === array() && $notice === 'The record for '.$forgedId.' '.$forgery[1], $notice); }
$longPath = '/'.implode('/', array_fill(0, 6, str_repeat('p', 200)));
file_put_contents($recordFile, json_encode(array_merge($good, array('OriginalPathEncoded' => base64_encode($longPath)))));
list ($read, $record, $notice) = readQuarantineRecord($q2, $forgedId);
check('a record of a long path shows 1000 bytes of it & keeps every byte', $read === TRUE && field($record, 'OriginalPath') === cut($longPath) && field($record, 'OriginalPathBytes') === $longPath, $notice);
// A record is read to 1048576 bytes & no further. One that long is read whole, & one a byte longer has lost its last byte, which closes it.
$openRecord = substr((string)json_encode($good), 0, -1);
file_put_contents($recordFile, $openRecord.str_repeat(' ', 1048576 - strlen($openRecord) - 1).'}');
list ($read, $record, $notice) = readQuarantineRecord($q2, $forgedId);
check('a record of 1048576 bytes is read', filesize($recordFile) === 1048576 && $read === TRUE, $notice);
file_put_contents($recordFile, $openRecord.str_repeat(' ', 1048576 - strlen($openRecord)).'}');
clearstatcache(TRUE);
list ($read, $record, $notice) = readQuarantineRecord($q2, $forgedId);
check('a record of 1048577 bytes is not read past the 1048576th', filesize($recordFile) === 1048577 && $read === FALSE && $notice === 'The record for '.$forgedId.' could not be read as a record.', $notice);
file_put_contents($recordFile, json_encode($good));

// 18d. A listing names a file that only looks like a record's, lists each record once, & says nothing where it was read.
file_put_contents($q2.'/'.$forgedId.'.bak1', 'beside the record');
list ($listed, $records, $unknown) = listQuarantinedFiles($q2);
$idsListed = array();
foreach ($records as $listedRecord) $idsListed[] = field($listedRecord, 'Id');
check('a name that is an identifier & five more characters is named, & the record is listed once', $listed === TRUE && count(array_keys($idsListed, $forgedId, TRUE)) === 1 && $unknown === array($forgedId.'.bak1'), implode(' ', $unknown));
setAside($q2.'/'.$forgedId.'.bak1');
list ($done, $result, $notice) = operateOnQuarantine('list', $q2, array());
check('a listing that was read says nothing', $done === TRUE && $notice === '' && count(field($result, 'Records')) === 3, $notice);
list ($done, $result, $notice) = operateOnQuarantine('list', $base.'/missing', array());
check('a listing that could not be read says so', $done === FALSE && $result === array('Records' => array(), 'Unknown' => array()) && $notice === 'The quarantine at '.$base.'/missing could not be read.', $notice);
list ($done, $result, $notice) = operateOnQuarantine(str_repeat('s', 1200), $q2, array());
check('an operation with a long name is named in 1000 bytes of it', $done === FALSE && $notice === 'The Quarantine pipeline has no operation called '.str_repeat('s', 1000).'....', $notice);

// 18e. A purge takes away what is there of a file, whichever half that is.
list ($done, $notice) = purgeQuarantinedFile($q2, $plainId);
check('a purge says nothing where it removed both', $done === TRUE && $notice === '', $notice);
setAside($q2.'/'.$forgedId.'.json');
list ($done, $notice) = purgeQuarantinedFile($q2, $forgedId);
check('a kept file with no record is purged by its identifier', $done === TRUE && $notice === '' && !file_exists($q2.'/'.$forgedId.'.quarantined'), $notice);
setAside($q2.'/'.$twinId.'.quarantined');
list ($done, $notice) = purgeQuarantinedFile($q2, $twinId);
check('a record with no kept file is purged by its identifier', $done === TRUE && $notice === '' && namesIn($q2) === array(), $notice);

// 18f. A record whose file is gone, or is no longer a file, puts nothing back. A kept file is judged by what kind of thing it is.
$before = makeFile($u2.'/lost', 'lost', 0644, 'www-data');
list ($done, $lostId, $notice) = quarantineFile($q2, $u2.'/lost', identityOf($before), array());
$keptAside = setAside($q2.'/'.$lostId.'.quarantined');
list ($done, $path, $notice) = restoreQuarantinedFile($q2, $lostId, '');
check('a record whose file is gone puts nothing back, & says so', $done === FALSE && $path === '' && $notice === 'The quarantine no longer holds the file for '.$lostId.'.' && !file_exists($u2.'/lost'), $notice);
mkdir($q2.'/'.$lostId.'.quarantined', 0700);
list ($done, $path, $notice) = restoreQuarantinedFile($q2, $lostId, '');
check('a folder where the kept file was is not put back as a file', $done === FALSE && $path === '' && $notice === 'The quarantine no longer holds the file for '.$lostId.'.' && !file_exists($u2.'/lost'), $notice);
setAside($q2.'/'.$lostId.'.quarantined');
rename($keptAside, $q2.'/'.$lostId.'.quarantined');
chmod($q2.'/'.$lostId.'.quarantined', 0601);
list ($done, $path, $notice) = restoreQuarantinedFile($q2, $lostId, '');
check('a kept file is judged by what kind of thing it is, & not by its mode', $done === TRUE && $notice === '' && file_get_contents($u2.'/lost') === 'lost', $notice);

// 18g. An owner or a group with no name on this host. One of the two is enough to keep the file root's own & 0600,
// because a file given its group & its mode alone would be a setuid file of root's.
$noName = 'It belongs to root & only root can read it, because the account or the group that owned it has no name on this host. ';
foreach (array('owner' => array(54321, 33), 'group' => array(33, 54321)) as $nameless => $numbers) {
  $before = ownedFile($u2.'/nameless-'.$nameless, 'nameless', 04755, $numbers[0], $numbers[1]);
  list ($done, $id, $notice) = quarantineFile($q2, $u2.'/nameless-'.$nameless, identityOf($before), array());
  list ($done, $path, $notice) = restoreQuarantinedFile($q2, $id, '');
  clearstatcache(TRUE);
  $after = @lstat($u2.'/nameless-'.$nameless);
  check('a file whose '.$nameless.' alone has no name goes back as root\'s own & 0600', $done === TRUE && is_array($after) && $after['uid'] === 0 && $after['gid'] === 0 && ($after['mode'] & 07777) === 0600, $notice);
  check('it says which numbers & which mode to give it by hand', $notice === $noName.'Give it the owner '.$numbers[0].', the group '.$numbers[1].' & the mode 4755 by hand.', $notice); }
$before = makeFile($other.'/userdir/unsafe.bin', 'unsafe', 0644, 'www-data');
list ($done, $id, $notice) = quarantineFile($q2, $other.'/userdir/unsafe.bin', identityOf($before), array());
list ($done, $path, $notice) = restoreQuarantinedFile($q2, $id, '');
check('where a copy cannot be given back safely it says exactly that', $done === TRUE && $notice === 'It belongs to root & only root can read it, because it could not be given back safely where it is. Give it the owner 33, the group 33 & the mode 0644 by hand.', $notice);

// 18h. A path longer than 1000 bytes. Every byte of it is kept & acted on, & 1000 of them are shown.
$before = makeFile($longFolder.'/deep', 'deep', 0644, 'www-data');
list ($done, $longId, $notice) = quarantineFile($q2, $longFolder.'/deep', identityOf($before), array());
$onDisk = json_decode((string)file_get_contents($q2.'/'.$longId.'.json'), TRUE);
list ($read, $record, $readNotice) = readQuarantineRecord($q2, $longId);
check('a file at the end of a path longer than 1000 bytes is quarantined', $done === TRUE && $notice === '', $notice);
check('the record on disk shows 1000 bytes of the path', field($onDisk, 'OriginalPath') === cut($longFolder.'/deep'));
check('the record as it is read shows the same & keeps every byte', $read === TRUE && field($record, 'OriginalPath') === cut($longFolder.'/deep') && field($record, 'OriginalPathBytes') === $longFolder.'/deep', $readNotice);
list ($done, $id, $notice) = quarantineFile($q2, $longFolder.'/gone', array(), array());
check('a notice names 1000 bytes of a long path', $done === FALSE && $notice === cut($longFolder.'/gone').' is no longer there.', $notice);
list ($done, $id, $notice) = quarantineFile($q2, $longFolder.'/missing/file', array(), array());
check('a long folder that could not be entered is named in 1000 bytes, & so is the reason', $done === FALSE && $notice === cut($longFolder.'/missing/file').' was left where it is, because the folder holding it could not be held. '.cut($longFolder.'/missing does not exist.'), $notice);
list ($done, $path, $notice) = restoreQuarantinedFile($q2, $longId, $longFolder.'/missing');
check('a long folder to put a file back into is named the same way', $done === FALSE && $notice === 'The file was not put back, because '.cut($longFolder.'/missing').' could not be held. '.cut($longFolder.'/missing does not exist.'), $notice);
writeScratchFile($longFolder.'/deep', 'in the way', 0644);
list ($done, $path, $notice) = restoreQuarantinedFile($q2, $longId, '');
check('a long name that is taken is named in 1000 bytes', $done === FALSE && $notice === 'The file was not put back, because '.cut($longFolder.'/deep').' already exists & nothing is written over.', $notice);
setAside($longFolder.'/deep');
list ($done, $path, $notice) = restoreQuarantinedFile($q2, $longId, '');
check('it goes back to the end of the long path, byte for byte', $done === TRUE && $path === $longFolder.'/deep' && file_get_contents($longFolder.'/deep') === 'deep', $notice);
check('the second quarantine is empty again', namesIn($q2) === array(), implode(' ', namesIn($q2)));

// 19. What can only happen by a race or a fault, made to happen. The pipeline was lifted a second time into a namespace, where a hook
// stands in for one call at the moment it is made. Not a byte of the pipeline is changed for it. See testlib-cli.php.

// 19a. An account that is not root is refused. A file is not a quarantine, whatever the kernel's judge of folders says of it.
setHooks(array('posix_geteuid' => answer(1000)));
list ($private, $notice) = \hooked\quarantineIsRootsAlone($q2);
check('an account that is not root is refused a quarantine', $private === FALSE && $notice === 'A quarantine is kept by root, & this is not root.', $notice);
writeScratchFile($r2.'/not-a-folder', 'a file', 0600);
setHooks(array('folderIsHeldForRoot' => answer(array(TRUE, ''))));
list ($private, $notice) = \hooked\quarantineIsRootsAlone($r2.'/not-a-folder');
check('a file is not a quarantine, whatever the judge of folders says of it', $private === FALSE && $notice === 'The quarantine at '.$r2.'/not-a-folder is not a folder.', $notice);
setHooks(array());

// 19b. A copy that cannot be made is not called made. It leaves nothing behind that it made, & nothing changed that it did not make.
writeScratchFile($r2.'/source', 'copy me', 0600);
writeScratchFile($r2.'/taken', 'here first', 0600);
$takenBefore = lstat($r2.'/taken');
$sourceHandle = fopen($r2.'/source', 'r');
list ($copied, $bytes, $notice) = copyHandleToNewFile($sourceHandle, $r2.'/taken', 0600);
clearstatcache(TRUE);
check('a copy onto a name that exists is refused, & the name is left exactly as it was', $copied === FALSE && $bytes === 0 && $notice === 'A new file could not be created at '.$r2.'/taken.' && field(@lstat($r2.'/taken'), 'ino') === $takenBefore['ino'] && file_get_contents($r2.'/taken') === 'here first', $notice);
list ($copied, $bytes, $notice) = copyHandleToNewFile('not a handle', $r2.'/from-nothing', 0600);
clearstatcache(TRUE);
check('a copy from nothing that is open is refused, & the name it made is gone again', $copied === FALSE && $bytes === 0 && $notice === 'There was nothing open to copy from.' && !file_exists($r2.'/from-nothing'), $notice);
list ($copied, $bytes, $notice) = copyHandleToNewFile($sourceHandle, $r2.'/'.str_repeat('n', 1200), 0600);
check('a name too long to make is named in 1000 bytes of it', $copied === FALSE && $bytes === 0 && $notice === 'A new file could not be created at '.cut($r2.'/'.str_repeat('n', 1200)).'.', $notice);
setHooks(array('stream_copy_to_stream' => answer(FALSE)));
list ($copied, $bytes, $notice) = \hooked\copyHandleToNewFile($sourceHandle, $r2.'/unwritten', 0600);
clearstatcache(TRUE);
check('a copy that could not be written is not called made, & what was begun is removed', $copied === FALSE && $bytes === 0 && $notice === 'The copy at '.$r2.'/unwritten could not be written in full.' && !file_exists($r2.'/unwritten'), $notice);
list ($copied, $bytes, $notice) = \hooked\copyHandleToNewFile($sourceHandle, $longFolder.'/unwritten', 0600);
check('a copy that failed at the end of a long path names 1000 bytes of it', $copied === FALSE && $notice === 'The copy at '.cut($longFolder.'/unwritten').' could not be written in full.', $notice);
setHooks(array('fflush' => answer(FALSE)));
list ($copied, $bytes, $notice) = \hooked\copyHandleToNewFile($sourceHandle, $r2.'/unflushed', 0600);
clearstatcache(TRUE);
check('a copy that could not be flushed is not called made either', $copied === FALSE && $bytes === 0 && $notice === 'The copy at '.$r2.'/unflushed could not be written in full.' && !file_exists($r2.'/unflushed'), $notice);
setHooks(array());
fclose($sourceHandle);

// 19c. A file that is copied in is the file that was examined, or it is left where it is & nothing of it is kept.
$changedOpening = ' could not be moved into the quarantine, so it was left where it is. It changed while it was being opened.';
$before = twoNames($u2.'/guarded', 'guarded!', 0644);
setHooks(array('fopen' => function ($arguments, $count) { return ($count === 1) ? array('answer', FALSE) : NULL; }));
list ($done, $id, $notice) = \hooked\quarantineFile($q2, $u2.'/guarded', identityOf($before), array());
check('a file that could not be opened is left where it is', $done === FALSE && $id === '' && $notice === $u2.'/guarded'.$changedOpening && file_get_contents($u2.'/guarded') === 'guarded!' && namesIn($q2) === array(), $notice);
setHooks(array('fstat' => answer(FALSE)));
list ($done, $id, $notice) = \hooked\quarantineFile($q2, $u2.'/guarded', identityOf($before), array());
check('a file whose open handle could not be examined is left where it is', $done === FALSE && $id === '' && $notice === $u2.'/guarded'.$changedOpening && file_get_contents($u2.'/guarded') === 'guarded!' && namesIn($q2) === array(), $notice);
writeScratchFile($u2.'/decoy', 'decoy!!!', 0644);
setHooks(array('fopen' => function ($arguments, $count) use ($u2) { if ($count === 1) { setAside($u2.'/guarded'); rename($u2.'/decoy', $u2.'/guarded'); } return NULL; }));
list ($done, $id, $notice) = \hooked\quarantineFile($q2, $u2.'/guarded', identityOf($before), array());
setHooks(array());
check('a file swapped for another between the look at it & the opening of it is left where it is', $done === FALSE && $id === '' && $notice === $u2.'/guarded'.$changedOpening && file_get_contents($u2.'/guarded') === 'decoy!!!' && namesIn($q2) === array(), $notice);
$before = twoNames($u2.'/grows', 'grows', 0644);
setHooks(array('stream_copy_to_stream' => function ($arguments, $count) use ($u2) { file_put_contents($u2.'/grows', ' more', FILE_APPEND); return NULL; }));
list ($done, $id, $notice) = \hooked\quarantineFile($q2, $u2.'/grows', identityOf($before), array());
setHooks(array());
check('a file that grew while it was being copied is left where it is, & no part of it is kept', $done === FALSE && $id === '' && $notice === $u2.'/grows could not be moved into the quarantine, so it was left where it is. It changed size while it was being copied.' && file_get_contents($u2.'/grows') === 'grows more' && namesIn($q2) === array(), $notice);
$before = twoNames($u2.'/vanish', 'vanish', 0644);
setHooks(array('lstat' => function ($arguments, $count) { return (substr((string)$arguments[0], -12) === '.quarantined') ? array('answer', FALSE) : NULL; }));
list ($done, $id, $notice) = \hooked\quarantineFile($q2, $u2.'/vanish', identityOf($before), array());
setHooks(array());
check('a copy that is not in the quarantine once it was made is not called kept', $done === FALSE && $id === '' && $notice === $u2.'/vanish could not be moved into the quarantine, so it was left where it is.' && file_get_contents($u2.'/vanish') === 'vanish', $notice);
// The copy the stand-in hid is still there. It is set aside, so that the quarantine holds what it held before.
foreach (namesIn($q2) as $hiddenCopy) setAside($q2.'/'.$hiddenCopy);

// 19d. A file that is moved in is the file that was examined, or what was moved in is taken back out.
$before = makeFile($u2.'/linked', 'linked!!', 0644, 'www-data');
writeScratchFile($u2.'/decoy2', 'decoy2!!', 0644);
setHooks(array('link' => function ($arguments, $count) use ($u2) { if ($count === 1) { setAside($u2.'/linked'); rename($u2.'/decoy2', $u2.'/linked'); } return NULL; }));
list ($done, $id, $notice) = \hooked\quarantineFile($q2, $u2.'/linked', identityOf($before), array());
setHooks(array());
clearstatcache(TRUE);
check('a file swapped for another between the look at it & the moving of it is left where it is, & nothing of it is kept', $done === FALSE && $id === '' && $notice === $u2.'/linked could not be moved into the quarantine, so it was left where it is.' && file_get_contents($u2.'/linked') === 'decoy2!!' && field(@lstat($u2.'/linked'), 'nlink') === 1 && namesIn($q2) === array(), $notice);
$before = makeFile($u2.'/unhashed', 'unhashed', 0644, 'www-data');
setHooks(array('hash_file' => answer(FALSE)));
list ($done, $id, $notice) = \hooked\quarantineFile($q2, $u2.'/unhashed', identityOf($before), array());
setHooks(array());
clearstatcache(TRUE);
check('a file whose hash could not be taken has no record written, & is left where it is', $done === FALSE && $id === '' && $notice === $u2.'/unhashed was left where it is, because its record could not be written.' && file_get_contents($u2.'/unhashed') === 'unhashed' && field(@lstat($u2.'/unhashed'), 'nlink') === 1 && namesIn($q2) === array(), $notice);
$before = makeFile($u2.'/kept-owner', 'kept owner', 0644, 'www-data');
setHooks(array('changeOwnerWithoutFollowing' => answer(array(FALSE, 'It was left as it is.'))));
list ($done, $keptId, $notice) = \hooked\quarantineFile($q2, $u2.'/kept-owner', identityOf($before), array());
setHooks(array());
clearstatcache(TRUE);
$stored = @lstat($q2.'/'.$keptId.'.quarantined');
check('a quarantined file whose owner could not be changed says so, & is 0600 all the same', $done === TRUE && $notice === 'The quarantined file kept the owner it had. It was left as it is.' && field($stored, 'uid') === 33 && (field($stored, 'mode') & 07777) === 0600, $notice);
list ($done, $notice) = purgeQuarantinedFile($q2, $keptId);

// 19e. A file that is copied back is the file that was kept, whole, or the quarantine goes on keeping it as it was.
$refuseLink = answer(FALSE);
$before = makeFile($u2.'/back', 'put back', 0644, 'www-data');
list ($done, $backId, $notice) = quarantineFile($q2, $u2.'/back', identityOf($before), array());
$heldNames = namesIn($q2);
$keptAsItWas = function () use ($q2, $backId, $heldNames) { $kept = @lstat($q2.'/'.$backId.'.quarantined'); return (namesIn($q2) === $heldNames && field($kept, 'uid') === 0 && (field($kept, 'mode') & 07777) === 0600 && file_get_contents($q2.'/'.$backId.'.quarantined') === 'put back'); };
$notOpened = 'The file was not put back. The file kept could not be opened.';
setHooks(array('link' => $refuseLink, 'fopen' => function ($arguments, $count) { return ($count === 1) ? array('answer', FALSE) : NULL; }));
list ($done, $path, $notice) = \hooked\restoreQuarantinedFile($q2, $backId, $r2);
check('a kept file that could not be opened is not put back', $done === FALSE && $path === '' && $notice === $notOpened && !file_exists($r2.'/back') && $keptAsItWas(), $notice);
setHooks(array('link' => $refuseLink, 'fstat' => answer(FALSE)));
list ($done, $path, $notice) = \hooked\restoreQuarantinedFile($q2, $backId, $r2);
check('a kept file whose open handle could not be examined is not put back', $done === FALSE && $path === '' && $notice === $notOpened && !file_exists($r2.'/back') && $keptAsItWas(), $notice);
$SwappedAside = '';
setHooks(array('link' => $refuseLink, 'fopen' => function ($arguments, $count) use ($q2, $backId) { global $SwappedAside; if ($count === 1) { $SwappedAside = setAside($q2.'/'.$backId.'.quarantined'); writeScratchFile($q2.'/'.$backId.'.quarantined', 'another!', 0600); } return NULL; }));
list ($done, $path, $notice) = \hooked\restoreQuarantinedFile($q2, $backId, $r2);
setHooks(array());
check('a kept file swapped for another before it was opened is not put back', $done === FALSE && $path === '' && $notice === $notOpened && !file_exists($r2.'/back') && file_get_contents($q2.'/'.$backId.'.quarantined') === 'another!', $notice);
setAside($q2.'/'.$backId.'.quarantined');
rename($SwappedAside, $q2.'/'.$backId.'.quarantined');
setHooks(array('link' => $refuseLink, 'stream_copy_to_stream' => answer(FALSE)));
list ($done, $path, $notice) = \hooked\restoreQuarantinedFile($q2, $backId, $r2);
check('a copy back that could not be written is not called put back, & says what failed', $done === FALSE && $path === '' && $notice === 'The file was not put back. The copy at '.$r2.'/back could not be written in full.' && !file_exists($r2.'/back') && $keptAsItWas(), $notice);
setHooks(array('link' => $refuseLink, 'stream_copy_to_stream' => function ($arguments, $count) { fwrite($arguments[1], 'put'); return array('answer', 3); }));
list ($done, $path, $notice) = \hooked\restoreQuarantinedFile($q2, $backId, $r2);
check('a copy back that came out short is taken away again, & the quarantine keeps the file', $done === FALSE && $path === '' && $notice === 'The file was not put back. It could not be read whole, so the copy that was begun was taken away again.' && !file_exists($r2.'/back') && $keptAsItWas(), $notice);
setHooks(array('link' => $refuseLink, 'folderIsHeldForRoot' => function ($arguments, $count) use ($r2) { return ((string)$arguments[0] === $r2) ? array('answer', array(FALSE, str_repeat('h', 1200))) : NULL; }));
list ($done, $path, $notice) = \hooked\restoreQuarantinedFile($q2, $backId, $r2);
check('a folder that is not held is refused a copy, in 1000 bytes of the reason', $done === FALSE && $path === '' && $notice === 'The file was not put back. It could not be given a name there, & a file is never created by name in a folder another account could replace. '.str_repeat('h', 1000).'... Put it back into a folder only root can change, & move it from there by hand.' && !file_exists($r2.'/back') && $keptAsItWas(), $notice);
setHooks(array('link' => $refuseLink, 'stream_copy_to_stream' => function ($arguments, $count) use ($q2, $backId) { file_put_contents($q2.'/'.$backId.'.quarantined', ' & more', FILE_APPEND); return NULL; }));
list ($done, $path, $notice) = \hooked\restoreQuarantinedFile($q2, $backId, $r2);
setHooks(array());
clearstatcache(TRUE);
$after = @lstat($r2.'/back');
check('a copy back that came out longer is the file with what was written since, & is put back', $done === TRUE && $path === $r2.'/back' && $notice === '' && file_get_contents($r2.'/back') === 'put back & more' && field($after, 'uid') === 33 && (field($after, 'mode') & 07777) === 0644 && namesIn($q2) === array(), $notice);

// 19f. What the quarantine could not let go of is said, by a restore that put the file back & by a purge that did not finish.
$before = makeFile($u2.'/sticks', 'sticks', 0644, 'www-data');
list ($done, $sticksId, $notice) = quarantineFile($q2, $u2.'/sticks', identityOf($before), array());
setHooks(array('removeFileWithoutFollowing' => function ($arguments, $count) { return (substr((string)$arguments[0], -12) === '.quarantined') ? array('answer', array(FALSE, 'It could not be removed.')) : NULL; }));
list ($done, $path, $notice) = \hooked\restoreQuarantinedFile($q2, $sticksId, '');
setHooks(array());
check('a file put back while the quarantine could not let go of it says it is still listed', $done === TRUE && $path === $u2.'/sticks' && $notice === 'What the quarantine kept of it could not be removed, so it is still listed.' && file_get_contents($u2.'/sticks') === 'sticks' && namesIn($q2) === array($sticksId.'.quarantined'), $notice);
setHooks(array('removeFileWithoutFollowing' => function ($arguments, $count) { return (substr((string)$arguments[0], -5) === '.json') ? array('answer', array(FALSE, 'The record could not be removed.')) : NULL; }));
$before = makeFile($u2.'/half-purged', 'half purged', 0644, 'www-data');
list ($done, $halfPurgedId, $notice) = quarantineFile($q2, $u2.'/half-purged', identityOf($before), array());
list ($done, $notice) = \hooked\purgeQuarantinedFile($q2, $halfPurgedId);
setHooks(array());
check('a purge that removed the file & not its record is not called a purge, & says what is left', $done === FALSE && $notice === 'The record could not be removed.' && !file_exists($q2.'/'.$halfPurgedId.'.quarantined') && file_exists($q2.'/'.$halfPurgedId.'.json'), $notice);
list ($done, $notice) = purgeQuarantinedFile($q2, $halfPurgedId);
list ($doneToo, $noticeToo) = purgeQuarantinedFile($q2, $sticksId);
clearstatcache(TRUE);
check('what each left behind is purged by its identifier, & the file put back keeps its one name', $done === TRUE && $doneToo === TRUE && namesIn($q2) === array() && field(@lstat($u2.'/sticks'), 'nlink') === 1, $notice.$noticeToo);

// 19g. A PHP that cannot name an account gives a file to none, so the file goes back as root's own & the caller is told what it was.
$before = makeFile($u2.'/no-posix', 'no posix', 0644, 'www-data');
list ($done, $noPosixId, $notice) = quarantineFile($q2, $u2.'/no-posix', identityOf($before), array());
setHooks(array('function_exists' => function ($arguments, $count) { return in_array($arguments[0], array('posix_getpwuid', 'posix_getgrgid'), TRUE) ? array('answer', FALSE) : NULL; }));
list ($done, $path, $notice) = \hooked\restoreQuarantinedFile($q2, $noPosixId, '');
setHooks(array());
clearstatcache(TRUE);
$after = @lstat($u2.'/no-posix');
check('where PHP cannot name an account the file goes back as root\'s own & 0600, & says what it was', $done === TRUE && field($after, 'uid') === 0 && field($after, 'gid') === 0 && (field($after, 'mode') & 07777) === 0600 && $notice === $noName.'Give it the owner 33, the group 33 & the mode 0644 by hand.' && namesIn($q2) === array(), $notice);

// 20. From v0.0.6. PHP opens a file by the path it last resolved its name to, for as long as it remembers one, & only clearstatcache(TRUE)
// forgets them all. test_kernel-cli.php measures that. Here PHP remembers a file the pipeline is about to open as leading to another file,
// & the pipeline opens the file that is there. No one call of clearstatcache() in the pipeline decides either check, because another has
// forgotten the same path before it. HRProtect's tests/mutate-explained.txt says which, & these two checks are what that reasoning was held to.
$q3 = $base.'/Quarantine3';
$u3 = $base.'/user3';
freshFolder($q3, 0700, 'root');
freshFolder($u3, 0755, 'www-data');
makeFile($base.'/decoy', 'not the file', 0600, 'root');
$phpRemembers = phpRemembersPaths();
$before = makeFile($u3.'/remembered', 'the file kept', 0644, 'www-data');
list ($done, $rememberedId, $notice) = quarantineFile($q3, $u3.'/remembered', identityOf($before), array());
$ledElsewhere = ($done === TRUE) ? rememberElsewhere($q3.'/'.$rememberedId.'.quarantined', $base.'/decoy') : NULL;
list ($done, $path, $notice) = restoreQuarantinedFile($q3, $rememberedId, '');
check('a kept file PHP remembers as leading elsewhere goes back as the file it is, with nothing to say', $ledElsewhere === $phpRemembers && $done === TRUE && $notice === '' && (string)@file_get_contents($u3.'/remembered') === 'the file kept', $notice);
$before = twoNames($u3.'/twice', 'two names', 0644);
$ledElsewhere = rememberElsewhere($u3.'/twice', $base.'/decoy');
list ($done, $twiceId, $notice) = quarantineFile($q3, $u3.'/twice', identityOf($before), array());
list ($read, $record, $readNotice) = readQuarantineRecord($q3, $twiceId);
check('a file PHP remembers as leading elsewhere is copied in as the file it is', $ledElsewhere === $phpRemembers && $done === TRUE && field($record, 'Method') === 'copied' && field($record, 'Sha256') === hash('sha256', 'two names') && (string)@file_get_contents($q3.'/'.$twiceId.'.quarantined') === 'two names', $notice.$readNotice);
check('the working folder is where it was after all of it', getcwd() === $startFolder);
// 21. From the Engine's v4.3.2. Three faults planted in the pipeline survived every check above, & each check here kills one.
// An identifier is twelve letters & digits after its time, & never a dot or a slash, so none can name a path outside the quarantine.
check('an identifier with a dot or a slash where its last twelve go is no identifier', !quarantineIdIsValid('20261116-064500-../../../abc') && !quarantineIdIsValid('20261116-064500-0123456789/b') && !quarantineIdIsValid('20261116-064500-0123456789.b'));
// A quarantine is root's alone, so one its group can open is refused as one everybody can open is.
$q3 = $base.'/Quarantine3';
freshFolder($q3, 0750, 'root');
$before = makeFile($user.'/stays-grouped', 'stays', 0644, 'www-data');
list ($done, $id, $notice) = quarantineFile($q3, $user.'/stays-grouped', identityOf($before), array());
check('a quarantine its group can open is refused', $done === FALSE && strpos($notice, 'can be opened by an account other than root') !== FALSE && file_exists($user.'/stays-grouped') && namesIn($q3) === array(), $notice);
// The name is removed only where it is still the file that was moved in, once the record is written.
$q4 = $base.'/Quarantine4';
$u4 = $base.'/user4';
freshFolder($q4, 0700, 'root');
freshFolder($u4, 0755, 'www-data');
$before = makeFile($u4.'/late', 'moved in', 0644, 'www-data');
makeFile($u4.'/late-decoy', 'decoy late', 0644, 'www-data');
$SwappedLate = FALSE;
setHooks(array('lstat' => function ($arguments, $count) use ($q4, $u4) {
  global $SwappedLate;
  $recordsWritten = count(array_filter(namesIn($q4), function ($name) { return substr($name, -5) === '.json'; }));
  if (!$SwappedLate && (string)$arguments[0] === 'late' && $recordsWritten === 1) {
    $SwappedLate = TRUE;
    setAside($u4.'/late');
    if (!rename(mustBeScratch($u4.'/late-decoy'), mustBeScratch($u4.'/late'))) testStop('The decoy could not be put in the place of the file.');
    clearstatcache(TRUE); }
  return NULL; }));
list ($done, $id, $notice) = \hooked\quarantineFile($q4, $u4.'/late', identityOf($before), array());
setHooks(array());
clearstatcache(TRUE);
check('a file swapped for another once its record is written is left where it is, & nothing of it is kept', $SwappedLate === TRUE && $done === FALSE && $id === '' && $notice === $u4.'/late was left where it is, because its name could not be removed once it was in the quarantine.' && file_get_contents($u4.'/late') === 'decoy late' && namesIn($q4) === array(), $notice);

finish('test_quarantine');
