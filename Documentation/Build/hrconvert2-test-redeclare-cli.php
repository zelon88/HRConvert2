<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRConvert2, Copyright on 9/6/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / Application Information ...
// / This application is designed to provide a web-interface for converting file formats
// / on a server for users of any web browser without authentication.
// /
// / File Information ...
// / v3.9.3.
// / This file tests redeclare() against every idiom the application uses on it.
// /
// / redeclare() shreds a variable & then writes a new value into it. Twenty callers outside
// / ScanCore depend on it silently, & a fault empties the variable rather than raising.
// / It once purged the value it was asked to store & the symptom appeared three files away.
// /
// / Run it from the installation root, beside convertCore.php.
// /   php Documentation/Build/hrconvert2-test-redeclare-cli.php
// /
// / It exits zero when every case is judged correctly & one when any is not.
// / This is a build tool & is removed by --Delete Build Environment-- in config.php.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A build tool may only run from the command line.
// / Documentation/Build sits inside the web root, so a php file here is EXECUTED by the
// / web server rather than served as text. This file evaluates functions taken out of
// / convertCore.php, which is what a build tool needs & what nothing reachable over HTTP
// / should ever do. The -cli in the filename says so without opening the file.
// / -----------------------------------------------------------------------------------
if (PHP_SAPI !== 'cli') {
  if (!headers_sent()) http_response_code(404);
  die();
}
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A SECOND GATE, & it is not the same question as the first.
// / PHP_SAPI says which interface php was BUILT to speak. It does not say who started this
// / process. A web server that shells out, a cgi wrapper, or a php-cli invoked from a
// / request handler all report cli & are all a web request wearing a different hat.
// / So the environment is asked as well. A request carries a method, a host, a gateway or
// / a server name, & a build tool run from a terminal carries none of them.
// / Either gate alone is a gate somebody can walk around. Both together is the point.
if (isset($_SERVER['REQUEST_METHOD']) or isset($_SERVER['HTTP_HOST']) or isset($_SERVER['GATEWAY_INTERFACE'])
  or isset($_SERVER['SERVER_SOFTWARE']) or isset($_SERVER['REMOTE_ADDR']) or getenv('APACHE_RUN_USER') !== FALSE) {
  if (!headers_sent()) http_response_code(404);
  die();
}
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / ROOT IS DROPPED IF IT WAS GIVEN, because this tool has no use for it.
// / It reads no secret, writes no file & touches nothing outside its own output. Somebody
// / typing sudo out of habit should not hand that to a test.
// /
// / THE GROUP IS DROPPED BEFORE THE USER & the order is not a style choice. After setuid
// / the process is no longer root & no longer permitted to change its group, so dropping
// / the user first leaves the group as root permanently. That is the classic version of
// / this mistake & it looks like it worked.
// /
// / SUDO_UID names whoever ran sudo & is the right place to land. Without it the target is
// / nobody, which owns nothing anywhere.
// / A host with no posix extension cannot drop & is told so rather than continuing as root
// / while appearing to have dropped.
if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
  $targetUid = (int)(getenv('SUDO_UID') !== FALSE ? getenv('SUDO_UID') : 0);
  $targetGid = (int)(getenv('SUDO_GID') !== FALSE ? getenv('SUDO_GID') : 0);
  if ($targetUid === 0 && function_exists('posix_getpwnam')) {
    $nobodyRecord = posix_getpwnam('nobody');
    if (is_array($nobodyRecord)) { $targetUid = (int)$nobodyRecord['uid']; $targetGid = (int)$nobodyRecord['gid']; } }
  if ($targetUid > 0 && function_exists('posix_setgid') && function_exists('posix_setuid')) {
    posix_setgid($targetGid > 0 ? $targetGid : $targetUid);
    posix_setuid($targetUid);
    print('This test does not need root & dropped to uid '.posix_geteuid().', gid '.posix_getegid().'.'.PHP_EOL); }
  else print('Running as root & could not drop. This test needs no privilege; run it as a normal user.'.PHP_EOL);
}
// / -----------------------------------------------------------------------------------

$EnableMemoryProtection=FALSE;
// / The source must be readable from where this was started, & saying so beats a
// / cascade of warnings ending in an undefined function.
if (!is_file('convertCore.php')) {
  fwrite(STDERR, 'Run this from the installation root, beside convertCore.php. Could not read convertCore.php.'.PHP_EOL);
  exit(2);
}
$src=file_get_contents('convertCore.php');
function grab($n,$s){ $i=strpos($s,"\nfunction $n("); $d=0; $o='';
  for($j=$i+1;$j<strlen($s);$j++){ $o.=$s[$j]; if($s[$j]=='{')$d++; if($s[$j]=='}'){$d--; if($d==0) return $o;} } return ''; }
foreach(['getChildFunction','purgeSensitiveMemory','redeclare'] as $f){ $c=grab($f,$src); if($c) eval($c); }
$fail=0;
// / 1. The GUI string idiom, three chained appends.
$b='convertCore.php?';
redeclare($b,$b.'showFiles=1&'); redeclare($b,$b.'fileListOnly=1&'); redeclare($b,$b.'noGui=TRUE&');
if($b!=='convertCore.php?showFiles=1&fileListOnly=1&noGui=TRUE&'){echo "  FAIL string chain: '$b'\n";$fail++;}
// / 2. sessionParams built from empty.
$s=''; redeclare($s,$s.'noGui=TRUE&'); redeclare($s,$s.'gui=Default&language=en&color=blue');
if($s!=='noGui=TRUE&gui=Default&language=en&color=blue'){echo "  FAIL sessionParams: '$s'\n";$fail++;}
// / 3. The array-merge idiom.
$in=array(); $in2=array('obj','stl');
redeclare($in,array_merge($in,$in2)); redeclare($in,array_merge($in,array('ply','obj')));
redeclare($in,array_values(array_unique($in)));
if($in!==array('obj','stl','ply')){echo "  FAIL array merge: ".json_encode($in)."\n";$fail++;}
// / 4. An ARRAY ELEMENT passed by reference, as CapabilityDescriptions does.
$d=array('Model'=>array('Output'=>array('stl')));
redeclare($d['Model']['Output'], array_values(array_unique(array_merge($d['Model']['Output'], array('obj','stl')))));
if($d['Model']['Output']!==array('stl','obj')){echo "  FAIL array element: ".json_encode($d['Model']['Output'])."\n";$fail++;}
echo "  4 idioms tested, $fail failing\n"; exit($fail > 0 ? 1 : 0);
