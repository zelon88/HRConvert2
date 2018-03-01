<?php

// / This file is meant to be included when files are needed to be cleaned.
// / To use this file in your project or App, set a $CleanFiles array of files or directories 
// / and then require() this file.

// / The following array sets some SAFE files that WILL NOT be deleted. These are skipped by the Janitor.
$SAFEArr = array('', ' ', '.', '..', '...');

if ($JanitorDeleteIndex == '1') {
  unset($defaultApps['index.html']); }

$CleanFilesRAW = $CleanFiles;
// / The following code cleans whatever variables are set for $CleanDir and $CleanFiles. 
foreach ($CleanFiles as $CleanFile) {
    if ($CleanFile == '.' or $CleanFile == '..' or $CleanFile == '' or $CleanFile == ' '
      or in_array($CleanFile, $SAFEArr) or in_array($CleanFile, $defaultApps)) continue;
        if (!is_dir($CleanDir.'/'.$CleanFile)) {
          @unlink($CleanDir.'/'.$CleanFile); 
          $txt = ('OP-Act: Janitor Cleaned '.$CleanFile.' on '.$Time.'.');
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); }
        if (is_dir($CleanDir.'/'.$CleanFile)) {
          $objects1 = scandir($CleanDir.'/'.$CleanFile); 
          foreach ($objects1 as $object1) { 
            if ($object1 == '.' or $object1 == '..' or $object1 == '' or $object1 == ' '
              or in_array($object1, $SAFEArr) or in_array($object1, $defaultApps)) continue;
            if (!is_dir($CleanDir.'/'.$CleanFile.'/'.$object1)) {
              @unlink($CleanDir.'/'.$CleanFile.'/'.$object1); 
              $txt = ('OP-Act: Janitor Cleaned '.$object1.' on '.$Time.'.');
              $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); }  
            if (is_dir($CleanDir.'/'.$CleanFile.'/'.$object1)) { 
              $objects2 = scandir($CleanDir.'/'.$CleanFile.'/'.$object1); 
              foreach ($objects2 as $object2) { 
                if ($object2 == '.' or $object2 == '..' or $object2 == '' or $object2 == ' '
                  or in_array($object2, $SAFEArr) or in_array($object2, $defaultApps)) continue;
                if (!is_dir($CleanDir.'/'.$CleanFile.'/'.$object1.'/'.$object2)) {
                  @unlink($CleanDir.'/'.$CleanFile.'/'.$object1.'/'.$object2); 
                  $txt = ('OP-Act: Janitor Cleaned '.$object2.' on '.$Time.'.');
                  $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); }  
                if (is_dir($CleanDir.'/'.$CleanFile.'/'.$object1.'/'.$object2)) {
                  $objects3 = scandir($CleanDir.'/'.$CleanFile.'/'.$object1.'/'.$object2); 
                  foreach ($objects3 as $object3) { 
                    if ($object3 == '.' or $object3 == '..' or $object3 == '' or $object3 == ' '
                      or in_array($object3, $SAFEArr) or in_array($object3, $defaultApps)) continue;
                    if (!is_dir($CleanDir.'/'.$CleanFile.'/'.$object1.'/'.$object2.'/'.$object3)) {
                      @unlink($CleanDir.'/'.$CleanFile.'/'.$object1.'/'.$object2.'/'.$object3); 
                      $txt = ('OP-Act: Janitor Cleaned '.$object3.' on '.$Time.'.');
                      $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); }  
                    if (is_dir($CleanDir.'/'.$CleanFile.'/'.$object1.'/'.$object2.'/'.$object3)) { 
                      $objects4 = scandir($CleanDir.'/'.$CleanFile.'/'.$object1.'/'.$object2.'/'.$object3); 
                      foreach ($objects4 as $object4) { 
                        if ($object4 == '.' or $object4 == '..' or $object4 == '' or $object4 == ' '
                          or in_array($object4, $SAFEArr) or in_array($object4, $defaultApps)) continue;
                        if (!is_dir($CleanDir.'/'.$CleanFile.'/'.$object1.'/'.$object2.'/'.$object3.'/'.$object4)) {
                          @unlink($CleanDir.'/'.$CleanFile.'/'.$object1.'/'.$object2.'/'.$object3.'/'.$object4); 
                          $txt = ('OP-Act: Janitor Cleaned '.$object4.' on '.$Time.'.');
                          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); }
                          if (is_dir($CleanDir.'/'.$CleanFile.'/'.$object1.'/'.$object2.'/'.$object3.'/'.$object4)) { 
                            $objects5 = scandir($CleanDir.'/'.$CleanFile.'/'.$object1.'/'.$object2.'/'.$object3.'/'.$object4); 
                            foreach ($objects5 as $object5) { 
                              if ($object5 == '.' or $object5 == '..' or $object5 == '' or $object5 == ' '
                                or in_array($object5, $SAFEArr) or in_array($object5, $defaultApps)) continue;
                              if (!is_dir($CleanDir.'/'.$CleanFile.'/'.$object1.'/'.$object2.'/'.$object3.'/'.$object4.'/'.$object5)) {
                                @unlink($CleanDir.'/'.$CleanFile.'/'.$object1.'/'.$object2.'/'.$object3.'/'.$object4.'/'.$object5); 
                                $txt = ('OP-Act: Janitor Cleaned '.$object5.' on '.$Time.'.');
                                $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); }                           
                                if (is_dir($CleanDir.'/'.$CleanFile.'/'.$object1.'/'.$object2.'/'.$object3.'/'.$object4.'/'.$object5)) { 
                                  $objects6 = scandir($CleanDir.'/'.$CleanFile.'/'.$object1.'/'.$object2.'/'.$object3.'/'.$object4.'/'.$object5); 
                                  foreach ($objects6 as $object6) { 
                                    if ($object6 == '.' or $object6 == '..' or $object6 == '' or $object6 == ' '
                                      or in_array($object6, $SAFEArr) or in_array($object6, $defaultApps)) continue;
                                    if (!is_dir($CleanDir.'/'.$CleanFile.'/'.$object1.'/'.$object2.'/'.$object3.'/'.$object4.'/'.$object5.'/'.$object6)) {
                                      @unlink($CleanDir.'/'.$CleanFile.'/'.$object1.'/'.$object2.'/'.$object3.'/'.$object4.'/'.$object5.'/'.$object6); 
                                      $txt = ('OP-Act: Janitor Cleaned '.$object6.' on '.$Time.'.');
                                      $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND);
                                      if (is_dir($CleanDir.'/'.$CleanFile.'/'.$object1.'/'.$object2.'/'.$object3.'/'.$object4.'/'.$object5.'/'.$object6)) { 
                                        $objects7 = scandir($CleanDir.'/'.$CleanFile.'/'.$object1.'/'.$object2.'/'.$object3.'/'.$object4.'/'.$object6); 
                                        foreach ($objects7 as $object7) { 
                                          if ($object7 == '.' or $object7 == '..' or $object7 == '' or $object7 == ' '
                                            or in_array($object7, $SAFEArr) or in_array($object7, $defaultApps)) continue;
                                          if (!is_dir($CleanDir.'/'.$CleanFile.'/'.$object1.'/'.$object2.'/'.$object3.'/'.$object4.'/'.$object5.'/'.$object6.'/'.$object7)) {
                                            @unlink($CleanDir.'/'.$CleanFile.'/'.$object1.'/'.$object2.'/'.$object3.'/'.$object4.'/'.$object5.'/'.$object6.'/'.$object7); 
                                            $txt = ('OP-Act: Janitor Cleaned '.$object7.' on '.$Time.'.');
                                            $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } } } } } } } } } } } } } } } }
@rmdir($CleanDir); } 

foreach ($CleanFilesRAW as $CleanFile) {
    if ($CleanFile == '.' or $CleanFile == '..' or $CleanFile == '' or $CleanFile == ' '
      or in_array($CleanFile, $SAFEArr) or in_array($CleanFile, $defaultApps)) continue;
        if (is_dir($CleanDir.'/'.$CleanFile)) {
          @unlink($CleanDir.'/'.$CleanFile.'/index.html');
          @rmdir($CleanDir.'/'.$CleanFile); 
          $txt = ('OP-Act: Janitor Cleaned directory '.$CleanDir.'/'.$CleanFile.' on '.$Time.'.');
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } 
        if (is_file($CleanDir.'/'.$CleanFile)) {
          @unlink($CleanDir.'/'.$CleanFile); 
          $txt = ('OP-Act: Janitor Cleaned '.$CleanDir.'/'.$CleanFile.' on '.$Time.'.');
          $MAKELogFile = file_put_contents($LogFile, $txt.PHP_EOL, FILE_APPEND); } }
?>