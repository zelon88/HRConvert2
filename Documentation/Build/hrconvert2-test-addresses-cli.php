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
// / v3.9.1.
// / This file tests isPubliclyRoutableIP() against every address bypass known to it.
// /
// / A range this refuses is a range no dependency can ever be pointed at. A miss here is an
// / SSRF that every other layer is built to assume cannot happen.
// / Add an address here whenever a new bypass is found. Twenty are covered today.
// /
// / Run it from the installation root, beside convertCore.php.
// /   php Documentation/Build/hrconvert2-test-addresses-cli.php
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

$EnableMemoryProtection=FALSE; function purgeSensitiveMemory(){return TRUE;}
// / The source must be readable from where this was started, & saying so beats a
// / cascade of warnings ending in an undefined function.
if (!is_file('Resources/Engine/engine.php')) {
  fwrite(STDERR, 'Run this from the installation root, beside convertCore.php. Could not read Resources/Engine/engine.php.'.PHP_EOL);
  exit(2);
}
$src=file_get_contents("Resources/Engine/engine.php");
preg_match("/(function addressIsInRange.*?return \\\$IsInRange; \})/s",$src,$m); eval($m[1]);
preg_match("/(function isPubliclyRoutableIP.*?return \\\$Check; \})/s",$src,$m); eval($m[1]);
$cases=["8.8.8.8"=>true,"10.0.0.1"=>false,"127.0.0.1"=>false,"169.254.169.254"=>false,
 "100.64.1.1"=>false,"192.0.2.1"=>false,"224.0.0.1"=>false,"::ffff:127.0.0.1"=>false,
 "::ffff:10.0.0.1"=>false,"::ffff:8.8.8.8"=>true,"64:ff9b::7f00:1"=>false,"::ffff:7f00:1"=>false,"::ffff:a00:1"=>false,"64:ff9b::808:808"=>true,"fe80::1"=>false,
 "2001:4860:4860::8888"=>true,"2001:db8::1"=>false,"[::1]"=>false,"198.18.0.1"=>false,"255.255.255.255"=>false];
$bad=0; foreach($cases as $ip=>$want){$got=isPubliclyRoutableIP($ip); if($got!==$want){$bad++;echo "  WRONG $ip got ".var_export($got,1)."\n";}}
echo "  ".count($cases)." addresses tested, $bad wrong\n"; exit($bad > 0 ? 1 : 0);
