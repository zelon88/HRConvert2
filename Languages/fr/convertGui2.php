<?php
$Alert = 'Impossible de convertir ce fichier. Essayez de changer le nom.';
$Files = getFiles($ConvertDir);
$fileCount = count($Files);
$fcPlural1 = 's';
if (!is_numeric($fileCount)) $fileCount = 'un nombre inconnu de';
if ($fileCount == 1) $fcPlural1 = '';
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = 'Convertissez n\'importe quoi!'; 
if (!isset($CoreLoaded)) die('ERREUR!!! '.$ApplicationName.'-2, Ce fichier ne peut pas traiter votre demande ! Veuillez soumettre votre fichier à convertCore.php à la place !');
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
?>
  <body>
    <script type="text/javascript" src="Resources/jquery-3.6.0.min.js"></script>
    <div id="header-text" style="max-width:1000px; margin-left:auto; margin-right:auto; text-align:center;">
      <?php if (!isset($_GET['noGui'])) { ?><h1><?php echo $ApplicationName; ?></h1>
      <hr /><?php } ?>
      <h3>Options de Conversion de Fichiers</h3>
      
      <p>Vous avez téléchargé <?php echo $fileCount; ?> fichier<?php echo $fcPlural1; ?> valide<?php echo $fcPlural1; ?> sur <?php echo $ApplicationName; ?>.</p> 
      <p>Vos fichiers sont maintenant prêts à être convertis en utilisant les options ci-dessous</p>
    </div>

    <div id='utility' align="center">
      <p><img id='loadingCommandDiv' name='loadingCommandDiv' src='Resources/pacman.gif' style="max-width:64px; max-height:64px; display:none;"/></p>
      <iframe id='downloadTarget' name='downloadTarget' src='about:blank' style="visibility:  hidden; display: none; position: absolute; width:0; height:0; border:0;"></iframe>
    </div>

    <div id="compressAll" name="compressAll" style="max-width:1000px; margin-left:auto; margin-right:auto; text-align:center;">
      <button id="backButton" name="backButton" style="width:50px;" class="info-button" onclick="window.history.back();">&#x2190;</button>
      <button id="refreshButton" name="refreshButton" style="width:50px;" class="info-button" onclick="javascript:location.reload(true);">&#x21BB;</button>
      <br /> <br />
      <button id="scandocMoreOptionsButton" name="scandocMoreOptionsButton" class="info-button" onclick="toggle_visibility('compressAllOptions');">Options de Fichiers en Masse</button> 
      <div id="compressAllOptions" name="compressAllOptions" align="center" style="display:none;">
        <p>Compresser et Télécharger Tous Les Fichiers</p>
        <p>Spécifiez le nom du fichier: <input type="text" id='userarchallfilename' name='userarchallfilename' value='HRConvert2_Fichiers-<?php echo $Date; ?>'></p> 
        <select id='archallextension' name='archallextension'> 
          <option value="zip">Format</option>
          <option value="zip">Zip</option>
          <option value="rar">Rar</option>
          <option value="tar">Tar</option>
          <option value="7z">7z</option>
        </select>
        <input type="submit" id="archallSubmit" name="archallSubmit" class="info-button" value='Tout Compresser' onclick="toggle_visibility('loadingCommandDiv');">
      
        <script type="text/javascript">
        $(document).ready(function () {
          $('#archallSubmit').click(function() { 
            var extension = document.getElementById('archallextension').value;
            if (extension === "") { 
              extension = 'zip'; } 
            $.ajax({
              type: "POST",
              url: 'convertCore.php',
              data: {
                Token1:'<?php echo $Token1; ?>',
                Token2:'<?php echo $Token2; ?>',
                archive:'1',
                filesToArchive:<?php echo json_encode($Files); ?>,
                archextension:extension,
                userfilename:document.getElementById('userarchallfilename').value },
                success: function(ReturnData) {
                  $.ajax({
                  type: 'POST',
                  url: 'convertCore.php',
                  data: { 
                    Token1:'<?php echo $Token1; ?>',
                    Token2:'<?php echo $Token2; ?>',
                    document.getElementById('userarchallfilename').value },
                  success: function(returnFile) {
                    toggle_visibility('loadingCommandDiv');
                    window.location.href = "<?php echo 'DATA/'.$SesHash3.'/'; ?>"+$('input[name="userarchallfilename"]').val()+'.'+extension; }
                  }); },
                error: function(ReturnData) {
                  alert("<?php echo $Alert; ?>"); }
            }); }); });
        </script>

      </div>
    </div>
    <br />
    <div style="max-width:1000px; margin-left:auto; margin-right:auto;">
      <hr />

      <?php
      foreach ($Files as $File) {
        $extension = getExtension($ConvertTempDir.'/'.$File);
        $FileNoExt = str_replace($extension, '', $File);
        if (!in_array($extension, $ConvertArray)) continue;
        $ConvertGuiCounter1++;
      ?>

      <div id="file<?php echo $ConvertGuiCounter1; ?>" name="<?php echo $ConvertGuiCounter1; ?>">
        <p href=""><strong><?php echo $ConvertGuiCounter1; ?>.</strong> <u><?php echo $File; ?></u></p>
        
        <div id="buttonDiv<?php echo $ConvertGuiCounter1; ?>" name="buttonDiv<?php echo $ConvertGuiCounter1; ?>" style="height:25px;">
          <img id="archfileButton<?php echo $ConvertGuiCounter1; ?>" name="archfileButton<?php echo $ConvertGuiCounter1; ?>" src="Resources/archive.png" style="float:left; display:block;" 
           onclick="toggle_visibility('archfileOptionsDiv<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('archfileButton<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('archfileXButton<?php echo $ConvertGuiCounter1; ?>');"/>
          <img id="archfileXButton<?php echo $ConvertGuiCounter1; ?>" name="archfileXButton<?php echo $ConvertGuiCounter1; ?>" src="Resources/x.png" style="float:left; display:none;" 
           onclick="toggle_visibility('archfileOptionsDiv<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('archfileButton<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('archfileXButton<?php echo $ConvertGuiCounter1; ?>');"/> 
         
          <?php if (in_array($extension, $PDFWorkArr)) { ?>          
          <a style="float:left;">&nbsp;|&nbsp;</a>
          
          <img id="docscanButton<?php echo $ConvertGuiCounter1; ?>" name="docscanButton<?php echo $ConvertGuiCounter1; ?>" src="Resources/docscan.png" style="float:left; display:block;" 
           onclick="toggle_visibility('pdfOptionsDiv<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('docscanButton<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('docscanXButton<?php echo $ConvertGuiCounter1; ?>');"/>
          <img id="docscanXButton<?php echo $ConvertGuiCounter1; ?>" name="docscanXButton<?php echo $ConvertGuiCounter1; ?>" src="Resources/x.png" style="float:left; display:none;" 
           onclick="toggle_visibility('pdfOptionsDiv<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('docscanButton<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('docscanXButton<?php echo $ConvertGuiCounter1; ?>');"/> 
          <?php } 

          if (in_array($extension, $ArchiveArray)) { ?>
          <a style="float:left;">&nbsp;|&nbsp;</a>

          <img id="archiveButton<?php echo $ConvertGuiCounter1; ?>" name="archiveButton<?php echo $ConvertGuiCounter1; ?>" src="Resources/convert.png" style="float:left; display:block;" 
           onclick="toggle_visibility('archiveOptionsDiv<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('archiveButton<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('archiveXButton<?php echo $ConvertGuiCounter1; ?>');"/>
          <img id="archiveXButton<?php echo $ConvertGuiCounter1; ?>" name="archiveXButton<?php echo $ConvertGuiCounter1; ?>" src="Resources/x.png" style="float:left; display:none;" 
           onclick="toggle_visibility('archiveOptionsDiv<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('archiveButton<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('archiveXButton<?php echo $ConvertGuiCounter1; ?>');"/> 
          <?php } 

          if (in_array($extension, $DocumentArray)) { ?>
          <a style="float:left;">&nbsp;|&nbsp;</a>

          <img id="documentButton<?php echo $ConvertGuiCounter1; ?>" name="documentButton<?php echo $ConvertGuiCounter1; ?>" src="Resources/document.png" style="float:left; display:block;" 
           onclick="toggle_visibility('docOptionsDiv<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('documentButton<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('documentXButton<?php echo $ConvertGuiCounter1; ?>');"/>
          <img id="documentXButton<?php echo $ConvertGuiCounter1; ?>" name="documentXButton<?php echo $ConvertGuiCounter1; ?>" src="Resources/x.png" style="float:left; display:none;" 
           onclick="toggle_visibility('docOptionsDiv<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('documentButton<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('documentXButton<?php echo $ConvertGuiCounter1; ?>');"/> 
          <?php } 

          if (in_array($extension, $SpreadsheetArray)) { ?>
          <a style="float:left;">&nbsp;|&nbsp;</a>

          <img id="spreadsheetButton<?php echo $ConvertGuiCounter1; ?>" name="spreadsheetButton<?php echo $ConvertGuiCounter1; ?>" src="Resources/spreadsheet.png" style="float:left; display:block;" 
           onclick="toggle_visibility('spreadOptionsDiv<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('spreadsheetButton<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('spreadsheetXButton<?php echo $ConvertGuiCounter1; ?>');"/>
          <img id="spreadsheetXButton<?php echo $ConvertGuiCounter1; ?>" name="spreadsheetXButton<?php echo $ConvertGuiCounter1; ?>" src="Resources/x.png" style="float:left; display:none;" 
           onclick="toggle_visibility('spreadOptionsDiv<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('spreadsheetButton<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('spreadsheetXButton<?php echo $ConvertGuiCounter1; ?>');"/> 
          <?php }

          if (in_array($extension, $ImageArray)) { ?>
          <a style="float:left;">&nbsp;|&nbsp;</a>

          <img id="imageButton<?php echo $ConvertGuiCounter1; ?>" name="imageButton<?php echo $ConvertGuiCounter1; ?>" src="Resources/photo.png" style="float:left; display:block;" 
           onclick="toggle_visibility('imageOptionsDiv<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('imageButton<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('imageXButton<?php echo $ConvertGuiCounter1; ?>');"/>
          <img id="imageXButton<?php echo $ConvertGuiCounter1; ?>" name="imageXButton<?php echo $ConvertGuiCounter1; ?>" src="Resources/x.png" style="float:left; display:none;" 
           onclick="toggle_visibility('imageOptionsDiv<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('imageButton<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('imageXButton<?php echo $ConvertGuiCounter1; ?>');"/> 
          <?php }

          if (in_array($extension, $MediaArray)) { ?>
          <a style="float:left;">&nbsp;|&nbsp;</a>

          <img id="mediaButton<?php echo $ConvertGuiCounter1; ?>" name="mediaButton<?php echo $ConvertGuiCounter1; ?>" src="Resources/stream.png" style="float:left; display:block;" 
           onclick="toggle_visibility('audioOptionsDiv<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('mediaButton<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('mediaXButton<?php echo $ConvertGuiCounter1; ?>');"/>
          <img id="mediaXButton<?php echo $ConvertGuiCounter1; ?>" name="mediaXButton<?php echo $ConvertGuiCounter1; ?>" src="Resources/x.png" style="float:left; display:none;" 
           onclick="toggle_visibility('audioOptionsDiv<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('mediaButton<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('mediaXButton<?php echo $ConvertGuiCounter1; ?>');"/> 
          <?php } 

          if (in_array($extension, $VideoArray)) { ?>
          <a style="float:left;">&nbsp;|&nbsp;</a>

          <img id="videoButton<?php echo $ConvertGuiCounter1; ?>" name="videoButton<?php echo $ConvertGuiCounter1; ?>" src="Resources/stream.png" style="float:left; display:block;" 
           onclick="toggle_visibility('videoOptionsDiv<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('videoButton<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('videoXButton<?php echo $ConvertGuiCounter1; ?>');"/>
          <img id="videoXButton<?php echo $ConvertGuiCounter1; ?>" name="videoXButton<?php echo $ConvertGuiCounter1; ?>" src="Resources/x.png" style="float:left; display:none;" 
           onclick="toggle_visibility('videoOptionsDiv<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('videoButton<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('videoXButton<?php echo $ConvertGuiCounter1; ?>');"/> 
          <?php } 

          if (in_array($extension, $DrawingArray)) { ?>
          <a style="float:left;">&nbsp;|&nbsp;</a>

          <img id="drawingButton<?php echo $ConvertGuiCounter1; ?>" name="drawingButton<?php echo $ConvertGuiCounter1; ?>" src="Resources/convert.png" style="float:left; display:block;" 
           onclick="toggle_visibility('drawingOptionsDiv<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('drawingButton<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('drawingXButton<?php echo $ConvertGuiCounter1; ?>');"/>
          <img id="drawingXButton<?php echo $ConvertGuiCounter1; ?>" name="drawingXButton<?php echo $ConvertGuiCounter1; ?>" src="Resources/x.png" style="float:left; display:none;" 
           onclick="toggle_visibility('drawingOptionsDiv<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('drawingButton<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('drawingXButton<?php echo $ConvertGuiCounter1; ?>');"/> 
          <?php } 

          if (in_array($extension, $ModelArray)) { ?>
          <a style="float:left;">&nbsp;|&nbsp;</a>

          <img id="modelButton<?php echo $ConvertGuiCounter1; ?>" name="modelButton<?php echo $ConvertGuiCounter1; ?>" src="Resources/convert.png" style="float:left; display:block;" 
           onclick="toggle_visibility('modelOptionsDiv<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('modelButton<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('modelXButton<?php echo $ConvertGuiCounter1; ?>');"/>
          <img id="modelXButton<?php echo $ConvertGuiCounter1; ?>" name="modelXButton<?php echo $ConvertGuiCounter1; ?>" src="Resources/x.png" style="float:left; display:none;" 
           onclick="toggle_visibility('modelOptionsDiv<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('modelButton<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('modelXButton<?php echo $ConvertGuiCounter1; ?>');"/> 
          <?php } ?>

          <a style="float:left;">&nbsp;|&nbsp;</a>

        </div>

        <div id='archfileOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='archfileOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"></p>
          <p>Archiver ce fichier</p>
          <p>Spécifiez le nom du fichier: <input type="text" id='userarchfilefilename<?php echo $ConvertGuiCounter1; ?>' name='userarchfilefilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='archfileextension<?php echo $ConvertGuiCounter1; ?>' name='archfileextension<?php echo $ConvertGuiCounter1; ?>'> 
            <option value="zip">Format</option>
            <option value="zip">Zip</option>
            <option value="rar">Rar</option>
            <option value="tar">Tar</option>
            <option value="7z">7z</option>
          </select></p>
          <input type="submit" id="archfileSubmit<?php echo $ConvertGuiCounter1; ?>" name="archfileSubmit<?php echo $ConvertGuiCounter1; ?>" value='Fichier d&#39;Archive' onclick="toggle_visibility('loadingCommandDiv');">
          <script type="text/javascript">
          $(document).ready(function () {
            $('#archfileSubmit<?php echo $ConvertGuiCounter1; ?>').click(function() {
              $.ajax({
                type: "POST",
                url: 'convertCore.php',
                data: {
                  Token1:'<?php echo $Token1; ?>',
                  Token2:'<?php echo $Token2; ?>',
                  archive:'<?php echo $File; ?>',
                  filesToArchive:'<?php echo $File; ?>',
                  archextension:document.getElementById('archfileextension<?php echo $ConvertGuiCounter1; ?>').value,
                  userfilename:document.getElementById('userarchfilefilename<?php echo $ConvertGuiCounter1; ?>').value },
                  success: function(ReturnData) {
                    $.ajax({
                    type: 'POST',
                    url: 'convertCore.php',
                    data: { 
                      Token1:'<?php echo $Token1; ?>',
                      Token2:'<?php echo $Token2; ?>',
                      download:document.getElementById('userarchfilefilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('archfileextension<?php echo $ConvertGuiCounter1; ?>').value },
                    success: function(returnFile) {
                      toggle_visibility('loadingCommandDiv');
                      iframe = $('<iframe />').attr('src', "<?php echo 'DATA/'.$SesHash3.'/'; ?>"+document.getElementById('userarchfilefilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('archfileextension<?php echo $ConvertGuiCounter1; ?>').value).hide().appendTo('body'); }
                    }); },
                  error: function(ReturnData) {
                    alert("<?php echo $Alert; ?>"); }
              });
            });
          });
          </script>
        </div>
        <?php

        if (in_array($extension, $PDFWorkArr)) { 
        ?>
        <div id='pdfOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='pdfOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"></p>
          <p>Effectuer une reconnaissance optique de caractères sur ce fichier</p>
          <p>Spécifiez le nom du fichier: <input type="text" id='userpdffilename<?php echo $ConvertGuiCounter1; ?>' name='userpdffilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='pdfmethod<?php echo $ConvertGuiCounter1; ?>' name='pdfmethod<?php echo $ConvertGuiCounter1; ?>'>   
            <option value="0">Méthoded</option>  
            <option value="1">Méthode 1 (Simple)</option>
            <option value="2">Méthode 2 (Avancé)</option>
          </select>
          <select id='pdfextension<?php echo $ConvertGuiCounter1; ?>' name='pdfextension<?php echo $ConvertGuiCounter1; ?>'>   
            <option value="pdf">Format</option> 
            <option value="pdf">Pdf</option>   
            <option value="doc">Doc</option>
            <option value="docx">Docx</option>
            <option value="rtf">Rtf</option>
            <option value="txt">Txt</option>
            <option value="odt">Odt</option>
          </select></p>
          <p><input type="submit" id='pdfconvertSubmit<?php echo $ConvertGuiCounter1; ?>' name='pdfconvertSubmit<?php echo $ConvertGuiCounter1; ?>' value='Convertir en Document' onclick="toggle_visibility('loadingCommandDiv');"></p>
          <script type="text/javascript">
          $(document).ready(function () {
            $('#pdfconvertSubmit<?php echo $ConvertGuiCounter1; ?>').click(function() {
              $.ajax({
                type: "POST",
                url: 'convertCore.php',
                data: {
                  Token1:'<?php echo $Token1; ?>',
                  Token2:'<?php echo $Token2; ?>',
                  pdfworkSelected:'<?php echo $File; ?>',
                  method:document.getElementById('pdfmethod<?php echo $ConvertGuiCounter1; ?>').value,
                  pdfextension:document.getElementById('pdfextension<?php echo $ConvertGuiCounter1; ?>').value,
                  userpdfconvertfilename:document.getElementById('userpdffilename<?php echo $ConvertGuiCounter1; ?>').value },
                  success: function(ReturnData) {
                    $.ajax({
                    type: 'POST',
                    url: 'convertCore.php',
                    data: { 
                      Token1:'<?php echo $Token1; ?>',
                      Token2:'<?php echo $Token2; ?>',
                      download:document.getElementById('userpdffilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('pdfextension<?php echo $ConvertGuiCounter1; ?>').value },
                    success: function(returnFile) {
                      toggle_visibility('loadingCommandDiv');
                      iframe = $('<iframe />').attr('src', "<?php echo 'DATA/'.$SesHash3.'/'; ?>"+document.getElementById('userpdffilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('pdfextension<?php echo $ConvertGuiCounter1; ?>').value).hide().appendTo('body'); }
                    }); },
                  error: function(ReturnData) {
                    alert("<?php echo $Alert; ?>"); }
              });
            });
          });
          </script>
        </div>
        <?php } 

        if (in_array($extension, $ArchiveArray)) {
        ?>
        <div id='archiveOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='archiveOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"></p>
          <p>Convertir cette archive</p>
          <p>Spécifiez le nom du fichier: <input type="text" id='userarchivefilename<?php echo $ConvertGuiCounter1; ?>' name='userarchivefilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='archiveextension<?php echo $ConvertGuiCounter1; ?>' name='archiveextension<?php echo $ConvertGuiCounter1; ?>'> 
            <option value="zip">Format</option>
            <option value="zip">Zip</option>
            <option value="rar">Rar</option>
            <option value="tar">Tar</option>
            <option value="7z">7z</option>
          </select></p>
          <input type="submit" id="archiveconvertSubmit<?php echo $ConvertGuiCounter1; ?>" name="archiveconvertSubmit<?php echo $ConvertGuiCounter1; ?>" value='Fichiers d&#39;Archives' onclick="toggle_visibility('loadingCommandDiv'); display:none;">
          <script type="text/javascript">
          $(document).ready(function () {
            $('#archiveconvertSubmit<?php echo $ConvertGuiCounter1; ?>').click(function() {
              $.ajax({
                type: "POST",
                url: 'convertCore.php',
                data: {
                  Token1:'<?php echo $Token1; ?>',
                  Token2:'<?php echo $Token2; ?>',
                  convertSelected:'<?php echo $File; ?>',
                  extension:document.getElementById('archiveextension<?php echo $ConvertGuiCounter1; ?>').value,
                  userconvertfilename:document.getElementById('userarchivefilename<?php echo $ConvertGuiCounter1; ?>').value },
                  success: function(ReturnData) {
                    $.ajax({
                    type: 'POST',
                    url: 'convertCore.php',
                    data: { 
                      Token1:'<?php echo $Token1; ?>',
                      Token2:'<?php echo $Token2; ?>',
                      download:document.getElementById('userarchivefilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('archiveextension<?php echo $ConvertGuiCounter1; ?>').value() },
                    success: function(returnFile) {
                      toggle_visibility('loadingCommandDiv');
                      iframe = $('<iframe />').attr('src', "<?php echo 'DATA/'.$SesHash3.'/'; ?>"+document.getElementById('userarchivefilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('archiveextension<?php echo $ConvertGuiCounter1; ?>').value).hide().appendTo('body'); }
                    }); },
                  error: function(ReturnData) {
                    alert("<?php echo $Alert; ?>"); }
              });
            });
          });
          </script>
        </div>
        <?php } 

        if (in_array($extension, $DocumentArray)) {
        ?>
        <div id='docOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='docOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"></p>
          <p>Convertir ce document</p>
          <p>Spécifiez le nom du fichier: <input type="text" id='userdocfilename<?php echo $ConvertGuiCounter1; ?>' name='userdocfilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='docextension<?php echo $ConvertGuiCounter1; ?>' name='docextension<?php echo $ConvertGuiCounter1; ?>'> 
            <option value="txt">Format</option>
            <option value="doc">Doc</option>
            <option value="docx">Docx</option>
            <option value="rtf">Rtf</option>
            <option value="txt">Txt</option>
            <option value="odt">Odt</option>
            <option value="pdf">Pdf</option>
          </select></p>
          <input type="submit" id="docconvertSubmit<?php echo $ConvertGuiCounter1; ?>" name="docconvertSubmit<?php echo $ConvertGuiCounter1; ?>" value='Convertir le Document' onclick="toggle_visibility('loadingCommandDiv');">
          <script type="text/javascript">
          $(document).ready(function () {
            $('#docconvertSubmit<?php echo $ConvertGuiCounter1; ?>').click(function() {
              $.ajax({
                type: "POST",
                url: 'convertCore.php',
                data: {
                  Token1:'<?php echo $Token1; ?>',
                  Token2:'<?php echo $Token2; ?>',
                  convertSelected:'<?php echo $File; ?>',
                  extension:document.getElementById('docextension<?php echo $ConvertGuiCounter1; ?>').value,
                  userconvertfilename:document.getElementById('userdocfilename<?php echo $ConvertGuiCounter1; ?>').value },
                  success: function(ReturnData) {
                    $.ajax({
                    type: 'POST',
                    url: 'convertCore.php',
                    data: { 
                      Token1:'<?php echo $Token1; ?>',
                      Token2:'<?php echo $Token2; ?>',
                      download:document.getElementById('userdocfilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('docextension<?php echo $ConvertGuiCounter1; ?>').value() },
                    success: function(returnFile) {
                      toggle_visibility('loadingCommandDiv');
                      iframe = $('<iframe />').attr('src', "<?php echo 'DATA/'.$SesHash3.'/'; ?>"+document.getElementById('userdocfilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('docextension<?php echo $ConvertGuiCounter1; ?>').value).hide().appendTo('body'); }
                    }); },
                  error: function(ReturnData) {
                    alert("<?php echo $Alert; ?>"); }
              });
            });
          });
          </script>
        </div>
        <?php }

        if (in_array($extension, $SpreadsheetArray)) {
        ?>
        <div id='spreadOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='spreadOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"></p>
          <p>Convertir cette feuille de calcul</p>
          <p>Spécifiez le nom du fichier: <input type="text" id='userspreadfilename<?php echo $ConvertGuiCounter1; ?>' name='userspreadfilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='spreadextension<?php echo $ConvertGuiCounter1; ?>' name='spreadextension<?php echo $ConvertGuiCounter1; ?>'>
            <option value="ods">Format</option> 
            <option value="xls">Xls</option>
            <option value="xlsx">Xlsx</option>
            <option value="ods">Ods</option>
            <option value="pdf">Pdf</option>
          </select></p>
          <input type="submit" id="spreadconvertSubmit<?php echo $ConvertGuiCounter1; ?>" name="spreadconvertSubmit<?php echo $ConvertGuiCounter1; ?>" value='Convertir une Feuille de Calcul' onclick="toggle_visibility('loadingCommandDiv');">        
          <script type="text/javascript">
          $(document).ready(function () {
            $('#spreadconvertSubmit<?php echo $ConvertGuiCounter1; ?>').click(function() {
              $.ajax({
                type: "POST",
                url: 'convertCore.php',
                data: {
                  Token1:'<?php echo $Token1; ?>',
                  Token2:'<?php echo $Token2; ?>',
                  convertSelected:'<?php echo $File; ?>',
                  extension:document.getElementById('spreadextension<?php echo $ConvertGuiCounter1; ?>').value,
                  userconvertfilename:document.getElementById('userspreadfilename<?php echo $ConvertGuiCounter1; ?>').value },
                  success: function(ReturnData) {
                    $.ajax({
                    type: 'POST',
                    url: 'convertCore.php',
                    data: { 
                      Token1:'<?php echo $Token1; ?>',
                      Token2:'<?php echo $Token2; ?>',
                      download:document.getElementById('userspreadfilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('spreadextension<?php echo $ConvertGuiCounter1; ?>').value() },
                    success: function(returnFile) {
                      toggle_visibility('loadingCommandDiv');
                      iframe = $('<iframe />').attr('src', "<?php echo 'DATA/'.$SesHash3.'/'; ?>"+document.getElementById('userspreadfilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('spreadextension<?php echo $ConvertGuiCounter1; ?>').value).hide().appendTo('body'); }
                    }); },
                  error: function(ReturnData) {
                    alert("<?php echo $Alert; ?>"); }
              });
            });
          });
          </script>
        </div>
        <?php }

        if (in_array($extension, $PresentationArray)) {
        ?>
        <div id='presentationOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='presentationOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"></p>
          <p>Convertir cette présentation</p>
          <p>Spécifiez le nom du fichier: <input type="text" id='userpresentationfilename<?php echo $ConvertGuiCounter1; ?>' name='userpresentationfilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='presentationextension<?php echo $ConvertGuiCounter1; ?>' name='presentationextension<?php echo $ConvertGuiCounter1; ?>'>
            <option value="odp">Format</option>
            <option value="pages">Pages</option>
            <option value="pptx">Pptx</option>
            <option value="ppt">Ppt</option>
            <option value="xps">Xps</option>
            <option value="potx">Potx</option>
            <option value="pot">Pot</option>
            <option value="ppa">Ppa</option>
            <option value="odp">Odp</option>
          </select></p>
          <input type="submit" id="presentationconvertSubmit<?php echo $ConvertGuiCounter1; ?>" name="presentationconvertSubmit<?php echo $ConvertGuiCounter1; ?>" value='Convertir la Présentation' onclick="toggle_visibility('loadingCommandDiv');">
          <script type="text/javascript">
          $(document).ready(function () {
            $('#presentationconvertSubmit<?php echo $ConvertGuiCounter1; ?>').click(function() {
              $.ajax({
                type: "POST",
                url: 'convertCore.php',
                data: {
                  Token1:'<?php echo $Token1; ?>',
                  Token2:'<?php echo $Token2; ?>',
                  convertSelected:'<?php echo $File; ?>',
                  extension:document.getElementById('presentationextension<?php echo $ConvertGuiCounter1; ?>').value,
                  userconvertfilename:document.getElementById('userpresentationfilename<?php echo $ConvertGuiCounter1; ?>').value },
                  success: function(ReturnData) {
                    $.ajax({
                    type: 'POST',
                    url: 'convertCore.php',
                    data: { 
                      Token1:'<?php echo $Token1; ?>',
                      Token2:'<?php echo $Token2; ?>',
                      download:document.getElementById('userpresentationfilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('presentationextension<?php echo $ConvertGuiCounter1; ?>').value() },
                    success: function(returnFile) {
                      toggle_visibility('loadingCommandDiv');
                      iframe = $('<iframe />').attr('src', "<?php echo 'DATA/'.$SesHash3.'/'; ?>"+document.getElementById('userpresentationfilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('presentationextension<?php echo $ConvertGuiCounter1; ?>').value).hide().appendTo('body'); }
                    }); },
                  error: function(ReturnData) {
                    alert("<?php echo $Alert; ?>"); }
              });
            });
          });
          </script>
        </div>
        <?php } 

        if (in_array($extension, $MediaArray)) {
        ?>
        <div id='audioOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='audioOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"></p>
          <p>Convertir cet audio</p>
          <p>Spécifiez le nom du fichier: <input type="text" id='useraudiofilename<?php echo $ConvertGuiCounter1; ?>' name='useraudiofilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='audioextension<?php echo $ConvertGuiCounter1; ?>' name='audioextension<?php echo $ConvertGuiCounter1; ?>'> 
            <option value="mp3">Format</option>
            <option value="mp2">Mp2</option>  
            <option value="mp3">Mp3</option>
            <option value="wav">Wav</option>
            <option value="wma">Wma</option>
            <option value="flac">Flac</option>
            <option value="ogg">Ogg</option>
          </select></p>
          <input type="submit" id="audioconvertSubmit<?php echo $ConvertGuiCounter1; ?>" name="audioconvertSubmit<?php echo $ConvertGuiCounter1; ?>" value='Convertir l&#39;Audio' onclick="toggle_visibility('loadingCommandDiv');">
          <script type="text/javascript">
          $(document).ready(function () {
            $('#audioconvertSubmit<?php echo $ConvertGuiCounter1; ?>').click(function() {
              $.ajax({
                type: "POST",
                url: 'convertCore.php',
                data: {
                  Token1:'<?php echo $Token1; ?>',
                  Token2:'<?php echo $Token2; ?>',
                  convertSelected:'<?php echo $File; ?>',
                  extension:$('#audioextension<?php echo $ConvertGuiCounter1; ?>').val(),
                  userconvertfilename:$('input[name="useraudiofilename<?php echo $ConvertGuiCounter1; ?>"]').val() },
                  success: function(ReturnData) {
                    $.ajax({
                    type: 'POST',
                    url: 'convertCore.php',
                    data: { 
                      Token1:'<?php echo $Token1; ?>',
                      Token2:'<?php echo $Token2; ?>',
                      ownload:document.getElementById('useraudiofilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('audioextension<?php echo $ConvertGuiCounter1; ?>').value() },
                    success: function(returnFile) {
                      toggle_visibility('loadingCommandDiv');
                      iframe = $('<iframe />').attr('src', "<?php echo 'DATA/'.$SesHash3.'/'; ?>"+document.getElementById('useraudiofilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('audioextension<?php echo $ConvertGuiCounter1; ?>').value).hide().appendTo('body'); }
                    }); },
                  error: function(ReturnData) {
                    alert("<?php echo $Alert; ?>"); }
              });
            });
          });
          </script>
        </div>
        <?php } 

        if (in_array($extension, $VideoArray)) {
        ?>
        <div id='videoOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='videoOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"></p>
          <p>Convertir cette vidéo</p>
          <p>Spécifiez le nom du fichier: <input type="text" id='uservideofilename<?php echo $ConvertGuiCounter1; ?>' name='uservideofilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='videoextension<?php echo $ConvertGuiCounter1; ?>' name='videoextension<?php echo $ConvertGuiCounter1; ?>'>
            <option value="mp4">Format</option> 
            <option value="3gp">3gp</option> 
            <option value="mkv">Mkv</option> 
            <option value="avi">Avi</option>
            <option value="mp4">Mp4</option>
            <option value="flv">Flv</option>
            <option value="mpeg">Mpeg</option>
            <option value="wmv">Wmv</option>
            <option value="mov">Mov</option>
          </select></p>
          <input type="submit" id="videoconvertSubmit<?php echo $ConvertGuiCounter1; ?>" name="videoconvertSubmit<?php echo $ConvertGuiCounter1; ?>" value='Convertir la Vidéo' onclick="toggle_visibility('loadingCommandDiv');">
          <script type="text/javascript">
          $(document).ready(function () {
            $('#videoconvertSubmit<?php echo $ConvertGuiCounter1; ?>').click(function() {
              $.ajax({
                type: "POST",
                url: 'convertCore.php',
                data: {
                  Token1:'<?php echo $Token1; ?>',
                  Token2:'<?php echo $Token2; ?>',
                  convertSelected:'<?php echo $File; ?>',
                  extension:$('#videoextension<?php echo $ConvertGuiCounter1; ?>').val(),
                  userconvertfilename:$('input[name="uservideofilename<?php echo $ConvertGuiCounter1; ?>"]').val() },
                  success: function(ReturnData) {
                    $.ajax({
                    type: 'POST',
                    url: 'convertCore.php',
                    data: { 
                      Token1:'<?php echo $Token1; ?>',
                      Token2:'<?php echo $Token2; ?>',
                      download:$('input[name="uservideofilename<?php echo $ConvertGuiCounter1; ?>"]').val()+'.'+$('#videoextension<?php echo $ConvertGuiCounter1; ?>').val()},
                    success: function(returnFile) {
                      toggle_visibility('loadingCommandDiv');
                      iframe = $('<iframe />').attr('src', "<?php echo 'DATA/'.$SesHash3.'/'; ?>"+$('input[name="uservideofilename"]').val()+'.'+$('#videoextension<?php echo $ConvertGuiCounter1; ?>').val()).hide().appendTo('body'); }
                    }); },
                  error: function(ReturnData) {
                    alert("<?php echo $Alert; ?>"); }
              });
            });
          });
          </script>
        </div>
        <?php } 

        if (in_array($extension, $ModelArray)) {
        ?>
        <div id='modelOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='modelOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"></p>
          <p>Convertir ce modèle 3D</p>
          <p>Spécifiez le nom du fichier: <input type="text" id='usermodelfilename<?php echo $ConvertGuiCounter1; ?>' name='usermodelfilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='modelextension<?php echo $ConvertGuiCounter1; ?>' name='modelextension<?php echo $ConvertGuiCounter1; ?>'>
            <option value="3ds">Format</option>
            <option value="3ds">3ds</option>
            <option value="collada">Collada</option>
            <option value="obj">Obj</option>
            <option value="off">Off</option>
            <option value="ply">Ply</option>
            <option value="stl">Stl</option>
            <option value="ptx">Ptx</option>
            <option value="dxf">Dxf</option>
            <option value="u3d">U3d</option>
            <option value="vrml">Vrml</option>
          </select></p>
          <input type="submit" id="modelconvertSubmit<?php echo $ConvertGuiCounter1; ?>" name="modelconvertSubmit<?php echo $ConvertGuiCounter1; ?>" value='Convertir le Modèle' onclick="toggle_visibility('loadingCommandDiv');">
          <script type="text/javascript">
          $(document).ready(function () {
            $('#modelconvertSubmit<?php echo $ConvertGuiCounter1; ?>').click(function() {
              $.ajax({
                type: "POST",
                url: 'convertCore.php',
                data: {
                  Token1:'<?php echo $Token1; ?>',
                  Token2:'<?php echo $Token2; ?>',
                  convertSelected:'<?php echo $File; ?>',
                  extension:$('#modelextension<?php echo $ConvertGuiCounter1; ?>').val(),
                  userconvertfilename:$('input[name="usermodelfilename<?php echo $ConvertGuiCounter1; ?>"]').val() },
                  success: function(ReturnData) {
                    $.ajax({
                    type: 'POST',
                    url: 'convertCore.php',
                    data: { 
                      Token1:'<?php echo $Token1; ?>',
                      Token2:'<?php echo $Token2; ?>',
                      download:$('input[name="usermodelfilename<?php echo $ConvertGuiCounter1; ?>"]').val()+'.'+$('#modelextension<?php echo $ConvertGuiCounter1; ?>').val()},
                    success: function(returnFile) {
                      toggle_visibility('loadingCommandDiv');
                      iframe = $('<iframe />').attr('src', "<?php echo 'DATA/'.$SesHash3.'/'; ?>"+$('input[name="usermodelfilename"]').val()+'.'+$('#modelextension<?php echo $ConvertGuiCounter1; ?>').val()).hide().appendTo('body'); }
                    }); },
                  error: function(ReturnData) {
                    alert("<?php echo $Alert; ?>"); }
              });
            });
          });
          </script>
        </div>
        <?php } 

        if (in_array($extension, $DrawingArray)) {
        ?>
        <div id='drawingOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='drawingOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"></p>
          <p>Convertissez ce dessin technique ou ce fichier vectoriel</p>
          <p>Spécifiez le nom du fichier: <input type="text" id='userdrawingfilename<?php echo $ConvertGuiCounter1; ?>' name='userdrawingfilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='drawingextension<?php echo $ConvertGuiCounter1; ?>' name='drawingextension<?php echo $ConvertGuiCounter1; ?>'>
            <option value="jpg">Format</option>
            <option value="svg">Svg</option>
            <option value="dxf">Dxf</option>
            <option value="vdx">Vdx</option>
            <option value="fig">Fig</option>
            <option value="jpg">Jpg</option>
            <option value="png">Png</option>
            <option value="bmp">Bmp</option>
            <option value="pdf">Pdf</option>
          </select></p>
          <input type="submit" id="drawingconvertSubmit<?php echo $ConvertGuiCounter1; ?>" name="drawingconvertSubmit<?php echo $ConvertGuiCounter1; ?>" value='Convertir le Dessin' onclick="toggle_visibility('loadingCommandDiv');">     
          <script type="text/javascript">
          $(document).ready(function () {
            $('#drawingconvertSubmit<?php echo $ConvertGuiCounter1; ?>').click(function() {
              $.ajax({
                type: "POST",
                url: 'convertCore.php',
                data: {
                  Token1:'<?php echo $Token1; ?>',
                  Token2:'<?php echo $Token2; ?>',
                  convertSelected:'<?php echo $File; ?>',
                  extension:$('#drawingextension<?php echo $ConvertGuiCounter1; ?>').val(),
                  userconvertfilename:$('input[name="userdrawingfilename<?php echo $ConvertGuiCounter1; ?>"]').val() },
                  success: function(ReturnData) {
                    $.ajax({
                    type: 'POST',
                    url: 'convertCore.php',
                    data: { 
                      Token1:'<?php echo $Token1; ?>',
                      Token2:'<?php echo $Token2; ?>',
                      download:$('input[name="drawingfilename<?php echo $ConvertGuiCounter1; ?>"]').val()+'.'+$('#drawingextension<?php echo $ConvertGuiCounter1; ?>').val()},
                    success: function(returnFile) {
                      toggle_visibility('loadingCommandDiv');
                      iframe = $('<iframe />').attr('src', "<?php echo 'DATA/'.$SesHash3.'/'; ?>"+$('input[name="userdrawingfilename"]').val()+'.'+$('#drawingextension<?php echo $ConvertGuiCounter1; ?>').val()).hide().appendTo('body'); }
                    }); },
                  error: function(ReturnData) {
                    alert("<?php echo $Alert; ?>"); }
              });
            });
          });
          </script>
        </div>
        <?php } 

        if (in_array($extension, $ImageArray)) {
        ?>
        <div id='imageOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='imageOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"></p>
          <p>Convertir cette image</p>
          <p>Spécifiez le nom du fichier: <input type="text" id='userphotofilename<?php echo $ConvertGuiCounter1; ?>' name='userphotofilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='photoextension<?php echo $ConvertGuiCounter1; ?>' name='photoextension<?php echo $ConvertGuiCounter1; ?>'>
            <option value="jpg">Format</option>
            <option value="jpg">Jpg</option>
            <option value="bmp">Bmp</option>
            <option value="webp">Webp</option>
            <option value="png">Png</option>
            <option value="cin">Cin</option>
            <option value="dds">Dds</option>
            <option value="dib">Dib</option>
            <option value="flif">Flif</option>
            <option value="avif">Avif</option>
          </select></p>
          <p>Largeur Et Hauteur: </p>
          <p><input type="number" size="4" value="0" id='width<?php echo $ConvertGuiCounter1; ?>' name='width<?php echo $ConvertGuiCounter1; ?>' min="0" max="10000"> X <input type="number" size="4" value="0" id="height<?php echo $ConvertGuiCounter1; ?>" name="height<?php echo $ConvertGuiCounter1; ?>" min="0"  max="10000"></p> 
          <p>Tourner: <input type="number" size="3" id='rotate<?php echo $ConvertGuiCounter1; ?>' name='rotate<?php echo $ConvertGuiCounter1; ?>' value="0" min="0" max="359"></p>
          <input type="submit" id='convertPhotoSubmit<?php echo $ConvertGuiCounter1; ?>' name='convertPhotoSubmit<?php echo $ConvertGuiCounter1; ?>' value='Convertir l&#39;Image' onclick="toggle_visibility('loadingCommandDiv');">
          <script type="text/javascript">
          $(document).ready(function () {
            $('#convertPhotoSubmit<?php echo $ConvertGuiCounter1; ?>').click(function() {
              $.ajax({
                type: "POST",
                url: 'convertCore.php',
                data: {
                  Token1:'<?php echo $Token1; ?>',
                  Token2:'<?php echo $Token2; ?>',
                  convertSelected:'<?php echo $File; ?>',
                  rotate:$('#rotate<?php echo $ConvertGuiCounter1; ?>').val(),
                  width:$('#width<?php echo $ConvertGuiCounter1; ?>').val(),
                  height:$('#height<?php echo $ConvertGuiCounter1; ?>').val(),
                  extension:$('#photoextension<?php echo $ConvertGuiCounter1; ?>').val(),
                  userconvertfilename:$('input[name="userphotofilename<?php echo $ConvertGuiCounter1; ?>"]').val() },
                  success: function(ReturnData) {
                    $.ajax({
                    type: 'POST',
                    url: 'convertCore.php',
                    data: { 
                      Token1:'<?php echo $Token1; ?>',
                      Token2:'<?php echo $Token2; ?>',
                      download:$('input[name="userphotofilename<?php echo $ConvertGuiCounter1; ?>"]').val()+'.'+$('#photoextension<?php echo $ConvertGuiCounter1; ?>').val()},
                    success: function(returnFile) {
                      toggle_visibility('loadingCommandDiv');
                      iframe = $('<iframe />').attr('src', "<?php echo 'DATA/'.$SesHash3.'/'; ?>"+$('input[name="userphotofilename"]').val()+'.'+$('#photoextension<?php echo $ConvertGuiCounter1; ?>').val()).hide().appendTo('body'); }
                    }); },
                  error: function(ReturnData) {
                    alert("<?php echo $Alert; ?>"); }
              });
            });
          });
          </script>
        <?php } ?>
      </div>
      <hr />
      <?php } ?>
    </div>