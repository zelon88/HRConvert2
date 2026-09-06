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
// / This file attacks sanitizeSCAD() with every evasion shape known to it.
// /
// / The sanitizer decides which OpenSCAD statements read a file. A miss hands OpenSCAD a
// / reference the application never inspected & OpenSCAD reads whatever it points at.
// / Add a shape here whenever a new one is found. Eighteen are covered today.
// /
// / Run it from the installation root, beside convertCore.php.
// /   php Documentation/Build/hrconvert2-test-scad-cli.php
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
function purgeSensitiveMemory(){return TRUE;}
// / The source must be readable from where this was started, & saying so beats a
// / cascade of warnings ending in an undefined function.
if (!is_file('Resources/Pipelines/Scad/pipeline.php')) {
  fwrite(STDERR, 'Run this from the installation root, beside convertCore.php. Could not read Resources/Pipelines/Scad/pipeline.php.'.PHP_EOL);
  exit(2);
}
$src=file_get_contents('Resources/Pipelines/Scad/pipeline.php');
$i=strpos($src,"\nfunction sanitizeSCAD("); $d=0;$o='';
for($j=$i+1;$j<strlen($src);$j++){$o.=$src[$j]; if($src[$j]=='{')$d++; if($src[$j]=='}'){$d--; if($d==0)break;}}
eval($o);
$cases=[
 'plain'                 =>['include <evil.scad>', 1],
 'block comment bypass'  =>["/*x*/include <evil.scad>", 1],
 'unclosed block'        =>["/* include <evil.scad>", 0],
 'line comment'          =>["// include <evil.scad>", 0],
 'inside string'         =>['x="include <evil.scad>";', 0],
 'uppercase'             =>['INCLUDE <evil.scad>', 1],
 'mixed case'            =>['InClUdE <evil.scad>', 1],
 'longer identifier'     =>['myinclude <evil.scad>', 0],
 'trailing identifier'   =>['includes <evil.scad>', 0],
 'split across lines'    =>["include\n\n  <evil.scad>", 1],
 'comment between'       =>['surface/*x*/("evil.dat")', 1],
 'line comment between'  =>["surface// x\n(\"evil.dat\")", 1],
 'import_stl not import' =>['import_stl("evil.stl")', 1],
 'nested-looking comment'=>["/* /* */ include <evil.scad>", 1],
 'escaped quote string'  =>['x="a\\"include <e.scad>";', 0],
 'two calls'             =>["include <a.scad>\nuse <b.scad>", 2],
 'string then real'      =>['x="include <a>"; use <b.scad>', 1],
 'tab separated'         =>["include\t<evil.scad>", 1],
];
$fail=0;
foreach($cases as $name=>$c){
  $r=sanitizeSCAD($c[0]);
  $n=count($r);
  if($n!==$c[1]){ printf("  MISS %-24s expected %d found %d\n",$name,$c[1],$n); $fail++; }
}
echo "  ".count($cases)." evasions tested, $fail wrong\n"; exit($fail > 0 ? 1 : 0);
