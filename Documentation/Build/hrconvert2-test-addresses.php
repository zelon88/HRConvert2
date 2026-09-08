<?php
// / -----------------------------------------------------------------------------------
// / HRConvert2 v3.9.0. Tests isPubliclyRoutableIP() against every bypass it must refuse.
// / Run from the installation root:  php Documentation/Build/hrconvert2-test-addresses.php
// / Exits non zero when any address is judged wrongly. Add a bypass here when one is found.
// / This is a build tool. It is removed by --Delete Build Environment--.
// / -----------------------------------------------------------------------------------
$EnableMemoryProtection=FALSE; function purgeSensitiveMemory(){return TRUE;}
$src=file_get_contents("Resources/Engine/engine.php");
preg_match("/(function addressIsInRange.*?return \\\$IsInRange; \})/s",$src,$m); eval($m[1]);
preg_match("/(function isPubliclyRoutableIP.*?return \\\$Check; \})/s",$src,$m); eval($m[1]);
$cases=["8.8.8.8"=>true,"10.0.0.1"=>false,"127.0.0.1"=>false,"169.254.169.254"=>false,
 "100.64.1.1"=>false,"192.0.2.1"=>false,"224.0.0.1"=>false,"::ffff:127.0.0.1"=>false,
 "::ffff:10.0.0.1"=>false,"::ffff:8.8.8.8"=>true,"64:ff9b::7f00:1"=>false,"::ffff:7f00:1"=>false,"::ffff:a00:1"=>false,"64:ff9b::808:808"=>true,"fe80::1"=>false,
 "2001:4860:4860::8888"=>true,"2001:db8::1"=>false,"[::1]"=>false,"198.18.0.1"=>false,"255.255.255.255"=>false];
$bad=0; foreach($cases as $ip=>$want){$got=isPubliclyRoutableIP($ip); if($got!==$want){$bad++;echo "  WRONG $ip got ".var_export($got,1)."\n";}}
echo "  ".count($cases)." addresses tested, $bad wrong\n"; exit($bad > 0 ? 1 : 0);
