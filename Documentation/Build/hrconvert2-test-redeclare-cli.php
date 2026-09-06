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
// / v3.9.0.
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
