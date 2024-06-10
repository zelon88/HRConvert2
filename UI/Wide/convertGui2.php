<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRConvert2, Copyright on 6/9/2024 by Justin Grimes, www.github.com/zelon88
// /
// / LICENSE INFORMATION ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / APPLICATION INFORMATION ...
// / This application is designed to provide a web-interface for converting file formats
// / on a server for users of any web browser without authentication.
// /
// / FILE INFORMATION ...
// / v3.3.7.
// / This file contains language specific GUI elements for performing file conversions.
// /
// / HARDWARE REQUIREMENTS ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / DEPENDENCY REQUIREMENTS ...
// / This application requires Debian Linux (w/3rd Party audio license),
// / Apache 2.4, PHP 8+, LibreOffice, Unoconv, ClamAV, Tesseract, Rar, Unrar, Unzip,
// / 7zipper, FFMPEG, PDFTOTEXT, Dia, PopplerUtils, MeshLab, Mkisofs & ImageMagick.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set a flag to tell that the UI has been displayed.
$UIDisplayed = TRUE;
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die('ERROR!!! HRConvert2-2, This file cannot process your request! Please submit your file to convertCore.php instead!');
// / Assign temporary variables.
$gui2AudArr = $gui2VidArr = $gui2StreamArr = $gui2DocArr = $gui2SpreadArr = $gui2PresArr = $gui2ArchArr = $gui2ImaArr = $gui2ModArr = $gui2SubArr = $gui2DraArr = $gui2OcrArr = array();
// / -----------------------------------------------------------------------------------
?>
  <body>
    <script type='text/javascript' src='<?php echo $JqueryPath; ?>'></script>
    <div id='header-text' style='max-width:2000px; margin-left:auto; margin-right:auto; text-align:center;'>
      <?php if (!isset($_GET['noGui'])) { ?><h1><?php echo $ApplicationName; ?></h1>
      <hr /><?php } ?>
      <h3><?php echo $Gui2Text1; ?></h3>
      <p><?php echo $Gui2Text30; ?></p>
      <p><?php echo $Gui2Text31; ?></p>
    </div>

    <div id='compressAll' name='compressAll' style='max-width:2000px; margin-left:auto; margin-right: auto; text-align:center;'>
      <button id='backButton' name='backButton' style='width:50px;' class='info-button' onclick='window.history.back();'>&#x2190;</button>
      <button id='refreshButton' name='refreshButton' style='width:50px;' class='info-button' onclick='javascript:location.reload(true);'>&#x21BB;</button>
      <br /> <br />
      <button id='scandocMoreOptionsButton' name='scandocMoreOptionsButton' class='info-button' onclick='toggle_visibility("compressAllOptions");'><?php echo $Gui2Text2; ?></button>
      <div id='compressAllOptions' name='compressAllOptions' align='center' style='display:none;'>
        <?php if ($AllowUserVirusScan) { ?>
        <hr style='width: 50%;'/>
        <p><strong><?php echo $Gui2Text3; ?></strong></p>
        <p><?php echo $Gui2Text20; ?><input type='checkbox' id='clamscanall' value='clamscanall' name='clamScan' checked></p>
        <p><?php echo $Gui2Text21; ?><input type='checkbox' id='scancoreall' value='scancoreall' name='phpavScan' checked></p>
        <p><input type='submit' id='scanAllButton' name='scanAllButton' class='info-button' value='<?php echo $Gui2Text22; ?>' onclick='toggle_visibility("loadingCommandDiv");'></p>
        <script type='text/javascript'>
          $(document).ready(function () {
            $('#scanAllButton').click(function() {
              var scanfiles = <?php echo json_encode($Files); ?>;
              var scanType = 'all';
              if($('input#clamscanall').is(':checked')) {
                var scanType = 'clamav'; }
              if($('input#scancoreall').is(':checked')) {
                var scanType = 'scancore'; }
              if($('input#clamscanall').is(':checked') && $('input#scancoreall').is(':checked')) {
                var scanType = 'all'; }
              $.ajax({
                type: 'POST',
                url: 'convertCore.php',
                data: {
                  Token1:'<?php echo $Token1; ?>',
                  Token2:'<?php echo $Token2; ?>',
                  scantype:scanType,
                  filesToScan:scanfiles },
                  success: function(ReturnData) {
                    $.ajax({
                    type: 'POST',
                    url: 'convertCore.php',
                    data: {
                      Token1:'<?php echo $Token1; ?>',
                      Token2:'<?php echo $Token2; ?>',
                      download:'<?php echo $ConsolidatedLogFileName; ?>' },
                    success: function(returnFile) {
                      toggle_visibility('loadingCommandDiv');
                      toggle_visibility('victoryCommandDiv');
                      setTimeout(function() {
                        toggle_visibility('victoryCommandDiv'); }, 5000);
                      document.getElementById('downloadTarget').href = '<?php echo 'DATA/'.$SesHash3.'/'.$ConsolidatedLogFileName; ?>';
                      document.getElementById('downloadTarget').click(); } }); },
                    error: function(ReturnData) {
                      toggle_visibility('loadingCommandDiv');

                      alert('<?php echo $Gui2Text72; ?>'); } }); }); });
        </script>
      <?php } ?>
        <hr style='width: 50%;'/>
        <?php if (in_array('Archive', $SupportedConversionTypes)) { ?>
        <p><strong><?php echo $Gui2Text4; ?></strong></p>
        <p><?php echo $Gui2Text17; ?><input type='text' id='userarchallfilename' name='userarchallfilename' value='<?php echo $ApplicationName; ?>_Files-<?php echo $Date; ?>'></p>
        <select id='archallextension' name='archallextension'>
          <option value='zip'><?php echo $Gui2Text18; ?></option>
          <?php foreach ($ArchiveArray as $gui2ArchArr) { ?>
          <option value='<?php echo $gui2ArchArr; ?>'><?php echo $gui2ArchArr; ?></option>
          <?php } ?>
        </select>
        <input type='submit' id='archallSubmit' name='archallSubmit' class='info-button' value='<?php echo $Gui2Text19; ?>' onclick='toggle_visibility("loadingCommandDiv");'>';
        <script type='text/javascript'>
          $(document).ready(function () {
            $('#archallSubmit').click(function() {
              var extension = document.getElementById('archallextension').value;
              if (extension === '') {
                extension = 'zip'; }
              $.ajax({
                type: 'POST',
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
                      download:document.getElementById('userarchallfilename').value+'.'+extension },
                    success: function(returnFile) {
                      toggle_visibility('loadingCommandDiv');
                      toggle_visibility('victoryCommandDiv');
                      setTimeout(function() {
                        toggle_visibility('victoryCommandDiv'); }, 5000);
                      document.getElementById('downloadTarget').href = '<?php echo 'DATA/'.$SesHash3.'/'; ?>'+document.getElementById('userarchallfilename').value+'.'+extension;
                      document.getElementById('downloadTarget').click(); } }); },
                    error: function(ReturnData) {
                      toggle_visibility('loadingCommandDiv');
                      toggle_visibility('failureCommandDiv');
                      setTimeout(function() {
                        toggle_visibility('failureCommandDiv'); }, 5000);
                      alert('<?php echo $Gui2Text71; ?>'); } }); }); });
        </script>
        <?php } ?>
        <hr style='width: 50%;'/>
      </div>
    </div>
    <div id='utilityupper' align='center'>

      <p><img id='loadingCommandDiv' name='loadingCommandDiv' src='<?php echo $PacmanLoc; ?>' style='max-width:24px; max-height:24px; display:none;'/>
      
      <img id='victoryCommandDiv' name='victoryCommandDiv' src='<?php echo $GuiImageDir; ?>checkmark.png' style='max-width:24px; max-height:24px; display:none;'/>

      <img id='failureCommandDiv' name='failureCommandDiv' src='<?php echo $GuiImageDir; ?>xcircle.png' style='max-width:24px; max-height:24px; display:none;'/></p>

      <a id='downloadTarget' href='about:blank' style='display: none;' download></a>
    </div>
    <br />
    <div style='max-width:2000px; margin-left:auto; margin-right:auto;'>
      <hr />

      <?php
      foreach ($Files as $File) {
        $extension = getExtension($ConvertTempDir.'/'.$File);
        $FileNoExt = str_replace($extension, '', $File);
        if (!in_array($extension, $Allowed)) continue;
        $ConvertGuiCounter1++;
      ?>

      <div id='file<?php echo $ConvertGuiCounter1; ?>' name='<?php echo $ConvertGuiCounter1; ?>'>

        <a style='float:<?php echo $GUIAlignment; ?>;'><strong><?php echo $ConvertGuiCounter1; ?>.</strong> <u><?php echo $File; ?></u>&nbsp;&nbsp;</a>

          <img id='loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>' name='loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $PacmanLoc; ?>' style='float:<?php echo $GUIAlignment; ?>; max-width:24px; max-height:24px; display:none;'/>

          <img id='victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>' name='victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>checkmark.png' style='float:<?php echo $GUIAlignment; ?>; max-width:24px; max-height:24px; display:none;'/>

          <img id='failureCommandDiv<?php echo $ConvertGuiCounter1; ?>' name='failureCommandDiv<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>xcircle.png' style='float:<?php echo $GUIAlignment; ?>; max-width:24px; max-height:24px; display:none;'/>

        <br><br>

        <div id='buttonDiv<?php echo $ConvertGuiCounter1; ?>' name='buttonDiv<?php echo $ConvertGuiCounter1; ?>' style='height:25px;'>
          <a style='float:<?php echo $GUIAlignment; ?>;'>&nbsp;&nbsp;&nbsp;&nbsp;</a>

          <img id='downloadfilebutton<?php echo $ConvertGuiCounter1; ?>' name='downloadfilebutton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>download.png' style='float:<?php echo $GUIAlignment; ?>; display:block;' onclick='toggle_visibility("loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text5.' '.$File; ?>' alt='<?php echo $Gui2Text5.' '.$File; ?>'/>

          <script type='text/javascript'>
            $(document).ready(function () {
              $('#downloadfilebutton<?php echo $ConvertGuiCounter1; ?>').click(function() {
                $.ajax({
                type: 'POST',
                url: 'convertCore.php',
                data: {
                  Token1:'<?php echo $Token1; ?>',
                  Token2:'<?php echo $Token2; ?>',
                  download:'<?php echo $File; ?>' },
                success: function(returnFile) {
                  toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                  toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                  setTimeout(function() {
                    toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                  document.getElementById('downloadTarget').href = '<?php echo 'DATA/'.$SesHash3.'/'.$File; ?>';
                  document.getElementById('downloadTarget').click(); },
                error: function(ReturnData) {
                  toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                  toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                  setTimeout(function() {
                          toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                  alert("<?php echo $Gui2Text71; ?>"); } }); }); });
          </script>

          <a style='float:<?php echo $GUIAlignment; ?>;'>&nbsp;|&nbsp;</a>
          <img id='deletefilebutton<?php echo $ConvertGuiCounter1; ?>' name='deletefilebutton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>delete.png' style='float:<?php echo $GUIAlignment; ?>; display:block;'
           onclick='toggle_visibility("deletefileOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("deletefilebutton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("deleteXfilebutton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text67.' '.$File; ?>' alt='<?php echo $Gui2Text67.' '.$File; ?>'/>
          <img id='deleteXfilebutton<?php echo $ConvertGuiCounter1; ?>' name='deleteXfilebutton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>x.png' style='float:<?php echo $GUIAlignment; ?>; display:none;' 
           onclick='toggle_visibility("deletefileOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("deletefilebutton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("deleteXfilebutton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text68; ?>' alt='<?php echo $Gui2Text68; ?>'/>

          <?php if ($AllowUserShare) { ?>
          <a style='float:<?php echo $GUIAlignment; ?>;'>&nbsp;|&nbsp;</a>
          <img id='sharefilebutton<?php echo $ConvertGuiCounter1; ?>' name='sharefilebutton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>link.png' style='float:<?php echo $GUIAlignment; ?>; display:block;'
           onclick='toggle_visibility("sharefileOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("sharefilebutton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("shareXfilebutton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text6.' '.$File; ?>' alt='<?php echo $Gui2Text6.' '.$File; ?>'/>
          <img id='shareXfilebutton<?php echo $ConvertGuiCounter1; ?>' name='shareXfilebutton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>x.png' style='float:<?php echo $GUIAlignment; ?>; display:none;' 
           onclick='toggle_visibility("sharefileOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("sharefilebutton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("shareXfilebutton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text7; ?>' alt='<?php echo $Gui2Text7; ?>'/>

          <?php } if ($AllowUserVirusScan) { ?>
          <a style='float:<?php echo $GUIAlignment; ?>;'>&nbsp;|&nbsp;</a>
          <img id='scanfilebutton<?php echo $ConvertGuiCounter1; ?>' name='scanfilebutton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>scan.png' style='float:<?php echo $GUIAlignment; ?>; display:block;' 
           onclick='toggle_visibility("scanfileOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("scanfilebutton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("scanfileXbutton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text8.' '.$File; ?>' alt='<?php echo $Gui2Text8.' '.$File; ?>'/>
          <img id='scanfileXbutton<?php echo $ConvertGuiCounter1; ?>' name='scanfileXbutton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>x.png' style='float:<?php echo $GUIAlignment; ?>; display:none;' 
           onclick='toggle_visibility("scanfileOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("scanfilebutton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("scanfileXbutton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text9; ?>' alt='<?php echo $Gui2Text9; ?>'/>

          <?php } ?>
          
          <a style='float:<?php echo $GUIAlignment; ?>;'>&nbsp;|&nbsp;</a>
          <img id='archfileButton<?php echo $ConvertGuiCounter1; ?>' name='archfileButton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>archive.png' style='float:<?php echo $GUIAlignment; ?>; display:block;' 
           onclick='toggle_visibility("archfileOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("archfileButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("archfileXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text10.' '.$File; ?>' alt='<?php echo $Gui2Text10.' '.$File; ?>'/>
          <img id='archfileXButton<?php echo $ConvertGuiCounter1; ?>' name='archfileXButton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>x.png' style='float:<?php echo $GUIAlignment; ?>; display:none;' 
           onclick='toggle_visibility("archfileOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("archfileButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("archfileXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text11; ?>' alt='<?php echo $Gui2Text11; ?>'/>

          <?php if (in_array($extension, $PDFWorkArr) && in_array('OCR', $SupportedConversionTypes)) { ?>          
          <a style='float:<?php echo $GUIAlignment; ?>;'>&nbsp;|&nbsp;</a>
          
          <img id='docscanButton<?php echo $ConvertGuiCounter1; ?>' name='docscanButton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>docscan.png' style='float:<?php echo $GUIAlignment; ?>; display:block;' 
           onclick='toggle_visibility("pdfOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("docscanButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("docscanXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text12.' '.$File; ?>' alt='<?php echo $Gui2Text12.' '.$File; ?>'/>
          <img id="docscanXButton<?php echo $ConvertGuiCounter1; ?>" name="docscanXButton<?php echo $ConvertGuiCounter1; ?>" src='<?php echo $GuiImageDir; ?>x.png' style="float:<?php echo $GUIAlignment; ?>; display:none;" 
           onclick="toggle_visibility('pdfOptionsDiv<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('docscanButton<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('docscanXButton<?php echo $ConvertGuiCounter1; ?>');" title='<?php echo $Gui2Text13; ?>' alt='<?php echo $Gui2Text13; ?>'/>
          <?php } 

          if (in_array($extension, $DearchiveArray) && in_array('Archive', $SupportedConversionTypes)) { ?>
          <a style='float:<?php echo $GUIAlignment; ?>;'>&nbsp;|&nbsp;</a>

          <img id='archiveButton<?php echo $ConvertGuiCounter1; ?>' name='archiveButton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>convert.png' style='float:<?php echo $GUIAlignment; ?>; display:block;'
           onclick='toggle_visibility("archiveOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("archiveButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("archiveXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text14.' '.$File; ?>' alt='<?php echo $Gui2Text14.' '.$File; ?>'/>
          <img id="archiveXButton<?php echo $ConvertGuiCounter1; ?>" name="archiveXButton<?php echo $ConvertGuiCounter1; ?>" src='<?php echo $GuiImageDir; ?>x.png' style="float:<?php echo $GUIAlignment; ?>; display:none;" 
           onclick="toggle_visibility('archiveOptionsDiv<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('archiveButton<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('archiveXButton<?php echo $ConvertGuiCounter1; ?>');" title='<?php echo $Gui2Text15; ?>' alt='<?php echo $Gui2Text15; ?>'/>
          <?php } 

          if (in_array($extension, $DocumentArray) && in_array('Document', $SupportedConversionTypes)) { ?>
          <a style='float:<?php echo $GUIAlignment; ?>;'>&nbsp;|&nbsp;</a>

          <img id='documentButton<?php echo $ConvertGuiCounter1; ?>' name='documentButton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>document.png' style='float:<?php echo $GUIAlignment; ?>; display:block;' 
           onclick='toggle_visibility("docOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("documentButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("documentXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text14.' '.$File; ?>' alt='<?php echo $Gui2Text14.' '.$File; ?>'/>
          <img id="documentXButton<?php echo $ConvertGuiCounter1; ?>" name="documentXButton<?php echo $ConvertGuiCounter1; ?>" src='<?php echo $GuiImageDir; ?>x.png' style="float:<?php echo $GUIAlignment; ?>; display:none;" 
           onclick="toggle_visibility('docOptionsDiv<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('documentButton<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('documentXButton<?php echo $ConvertGuiCounter1; ?>');" title='<?php echo $Gui2Text15; ?>' alt='<?php echo $Gui2Text15; ?>'/>
          <?php } 

          if (in_array($extension, $SpreadsheetArray) && in_array('Document', $SupportedConversionTypes)) { ?>
          <a style='float:<?php echo $GUIAlignment; ?>;'>&nbsp;|&nbsp;</a>

          <img id='spreadsheetButton<?php echo $ConvertGuiCounter1; ?>' name='spreadsheetButton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>spreadsheet.png' style='float:<?php echo $GUIAlignment; ?>; display:block;' 
           onclick='toggle_visibility("spreadOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("spreadsheetButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("spreadsheetXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text14.' '.$File; ?>' alt='<?php echo $Gui2Text14.' '.$File; ?>'/>
          <img id="spreadsheetXButton<?php echo $ConvertGuiCounter1; ?>" name="spreadsheetXButton<?php echo $ConvertGuiCounter1; ?>" src='<?php echo $GuiImageDir; ?>x.png' style="float:<?php echo $GUIAlignment; ?>; display:none;" 
           onclick="toggle_visibility('spreadOptionsDiv<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('spreadsheetButton<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('spreadsheetXButton<?php echo $ConvertGuiCounter1; ?>');" title='<?php echo $Gui2Text15; ?>' alt='<?php echo $Gui2Text15; ?>'/>
          <?php }

          if (in_array($extension, $PresentationArray) && in_array('Document', $SupportedConversionTypes)) { ?>
          <a style='float:<?php echo $GUIAlignment; ?>;'>&nbsp;|&nbsp;</a>

          <img id='presentationButton<?php echo $ConvertGuiCounter1; ?>' name='presentationButton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>presentation.png' style='float:<?php echo $GUIAlignment; ?>; display:block;' 
           onclick='toggle_visibility("presentationOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("presentationButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("presentationXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text14.' '.$File; ?>' alt='<?php echo $Gui2Text14.' '.$File; ?>'/>
          <img id='presentationXButton<?php echo $ConvertGuiCounter1; ?>' name='presentationXButton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>x.png' style='float:<?php echo $GUIAlignment; ?>; display:none;'
           onclick='toggle_visibility("presentationOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("presentationButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("presentationXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text15; ?>' alt='<?php echo $Gui2Text15; ?>'/>
          <?php }

          if (in_array($extension, $ImageArray) && in_array('Image', $SupportedConversionTypes)) { ?>
          <a style='float:<?php echo $GUIAlignment; ?>;'>&nbsp;|&nbsp;</a>

          <img id='imageButton<?php echo $ConvertGuiCounter1; ?>' name='imageButton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>photo.png' style='float:<?php echo $GUIAlignment; ?>; display:block;' 
           onclick='toggle_visibility("imageOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("imageButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("imageXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text14.' '.$File; ?>' alt='<?php echo $Gui2Text14.' '.$File; ?>'/>
          <img id="imageXButton<?php echo $ConvertGuiCounter1; ?>" name="imageXButton<?php echo $ConvertGuiCounter1; ?>" src='<?php echo $GuiImageDir; ?>x.png' style="float:<?php echo $GUIAlignment; ?>; display:none;" 
           onclick='toggle_visibility("imageOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("imageButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("imageXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text15; ?>' alt='<?php echo $Gui2Text15; ?>'/>
          <?php }

          if (in_array($extension, $MediaInputArray) && in_array('Audio', $SupportedConversionTypes)) { ?>
          <a style='float:<?php echo $GUIAlignment; ?>;'>&nbsp;|&nbsp;</a>

          <img id='mediaButton<?php echo $ConvertGuiCounter1; ?>' name='mediaButton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>media.png' style='float:<?php echo $GUIAlignment; ?>; display:block;'
           onclick='toggle_visibility("audioOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("mediaButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("mediaXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text14.' '.$File; ?>' alt='<?php echo $Gui2Text14.' '.$File; ?>'/>
          <img id='mediaXButton<?php echo $ConvertGuiCounter1; ?>' name='mediaXButton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>x.png' style='float:<?php echo $GUIAlignment; ?>; display:none;'
           onclick='toggle_visibility("audioOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("mediaButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("mediaXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text15; ?>' alt='<?php echo $Gui2Text15; ?>'/>
          <?php } 

          if (in_array($extension, $VideoInputArray) && in_array('Video', $SupportedConversionTypes)) { ?>
          <a style='float:<?php echo $GUIAlignment; ?>;'>&nbsp;|&nbsp;</a>

          <img id='videoButton<?php echo $ConvertGuiCounter1; ?>' name='videoButton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>video.png' style='float:<?php echo $GUIAlignment; ?>; display:block;' 
           onclick='toggle_visibility("videoOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("videoButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("videoXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text14.' '.$File; ?>' alt='<?php echo $Gui2Text14.' '.$File; ?>'/>
          <img id='videoXButton<?php echo $ConvertGuiCounter1; ?>' name='videoXButton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>x.png' style='float:<?php echo $GUIAlignment; ?>; display:none;'
           onclick='toggle_visibility("videoOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("videoButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("videoXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text15; ?>' alt='<?php echo $Gui2Text15; ?>'/>
          <?php } 

          if (in_array($extension, $StreamArray) && in_array('Stream', $SupportedConversionTypes)) { ?>
          <a style='float:<?php echo $GUIAlignment; ?>;'>&nbsp;|&nbsp;</a>

          <img id='streamButton<?php echo $ConvertGuiCounter1; ?>' name='streamButton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>stream.png' style='float:<?php echo $GUIAlignment; ?>; display:block;'
           onclick='toggle_visibility("streamOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("streamButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("streamXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text14.' '.$File; ?>' alt='<?php echo $Gui2Text14.' '.$File; ?>'/>
          <img id='streamXButton<?php echo $ConvertGuiCounter1; ?>' name='streamXButton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>x.png' style='float:<?php echo $GUIAlignment; ?>; display:none;' 
           onclick='toggle_visibility("streamOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("streamButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("streamXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text15; ?>' alt='<?php echo $Gui2Text15; ?>'/>
          <?php } 

          if (in_array($extension, $DrawingArray) && in_array('Drawing', $SupportedConversionTypes)) { ?>
          <a style='float:<?php echo $GUIAlignment; ?>;'>&nbsp;|&nbsp;</a>

          <img id='drawingButton<?php echo $ConvertGuiCounter1; ?>' name='drawingButton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>convert.png' style='float:<?php echo $GUIAlignment; ?>; display:block;' 
           onclick='toggle_visibility("drawingOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("drawingButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("drawingXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text14.' '.$File; ?>' alt='<?php echo $Gui2Text14.' '.$File; ?>'/>
          <img id='drawingXButton<?php echo $ConvertGuiCounter1; ?>' name='drawingXButton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>x.png' style='float:<?php echo $GUIAlignment; ?>; display:none;' 
           onclick='toggle_visibility("drawingOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("drawingButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("drawingXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text15; ?>' alt='<?php echo $Gui2Text15; ?>'/>
          <?php } 

          if (in_array($extension, $ModelArray) && in_array('Model', $SupportedConversionTypes)) { ?>
          <a style='float:<?php echo $GUIAlignment; ?>;'>&nbsp;|&nbsp;</a>

          <img id='modelButton<?php echo $ConvertGuiCounter1; ?>' name='modelButton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>convert.png' style='float:<?php echo $GUIAlignment; ?>; display:block;' 
           onclick='toggle_visibility("modelOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("modelButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("modelXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text14.' '.$File; ?>' alt='<?php echo $Gui2Text14.' '.$File; ?>'/>
          <img id='modelXButton<?php echo $ConvertGuiCounter1; ?>' name='modelXButton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>x.png' style='float:<?php echo $GUIAlignment; ?>; display:none;'
           onclick='toggle_visibility("modelOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("modelButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("modelXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text15; ?>' alt='<?php echo $Gui2Text15; ?>'/>
          <?php } 

          if (in_array($extension, $SubtitleInputArray) && in_array('Subtitle', $SupportedConversionTypes)) { ?>
          <a style='float:<?php echo $GUIAlignment; ?>;'>&nbsp;|&nbsp;</a>

          <img id='subtitleButton<?php echo $ConvertGuiCounter1; ?>' name='subtitleButton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>subtitle.png' style='float:<?php echo $GUIAlignment; ?>; display:block;' 
           onclick='toggle_visibility("subtitleOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("subtitleButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("subtitleXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text14.' '.$File; ?>' alt='<?php echo $Gui2Text14.' '.$File; ?>'/>
          <img id='subtitleXButton<?php echo $ConvertGuiCounter1; ?>' name='subtitleXButton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>x.png' style='float:<?php echo $GUIAlignment; ?>; display:none;' 
           onclick='toggle_visibility("subtitleOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("subtitleButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("subtitleXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text15; ?>' alt='<?php echo $Gui2Text15; ?>'/>
          <?php } ?>

        </div>

        <div id='archfileOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='archfileOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style='max-width:750px; display:none;'>
          <p style='max-width:2000px;'></p>
          <p><strong><?php echo $Gui2Text16; ?></strong></p>
          <p><?php echo $Gui2Text17; ?><input type='text' id='userarchfilefilename<?php echo $ConvertGuiCounter1; ?>' name='userarchfilefilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='archfileextension<?php echo $ConvertGuiCounter1; ?>' name='archfileextension<?php echo $ConvertGuiCounter1; ?>'> 
            <option value='zip'><?php echo $Gui2Text18; ?></option>
            <?php foreach ($ArchiveArray as $gui2ArchArr) { ?>
            <option value='<?php echo $gui2ArchArr; ?>'><?php echo $gui2ArchArr; ?></option>
            <?php } ?>
          </select></p>
          
          <input type='submit' id='archfileSubmit<?php echo $ConvertGuiCounter1; ?>' name='archfileSubmit<?php echo $ConvertGuiCounter1; ?>' value='<?php echo $Gui2Text51; ?>' onclick='toggle_visibility("loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>");'>
          
          <script type='text/javascript'>
            $(document).ready(function () {
              $('#archfileSubmit<?php echo $ConvertGuiCounter1; ?>').click(function() {
                $.ajax({
                  type: 'POST',
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
                        toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        document.getElementById('downloadTarget').href = "<?php echo 'DATA/'.$SesHash3.'/'; ?>"+document.getElementById('userarchfilefilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('archfileextension<?php echo $ConvertGuiCounter1; ?>').value; 
                        toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        setTimeout(function() {
                          toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                        document.getElementById('downloadTarget').click(); } }); },
                      error: function(ReturnData) {
                        toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        setTimeout(function() {
                          toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                        alert("<?php echo $Gui2Text71; ?>"); } }); }); });
          </script>
        </div>

        <?php if ($AllowUserShare) { ?>
        <div id='sharefileOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='sharefileOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:2000px;"></p>
          <p><strong><?php echo $Gui2Text23; ?></strong></p>
          <p id='sharelinkStatus<?php echo $ConvertGuiCounter1; ?>' name='sharelinkStatus<?php echo $ConvertGuiCounter1; ?>'><?php echo $Gui2Text24; ?><i><?php echo $Gui2Text25; ?></i></p>
          <p id='shareclipStatus<?php echo $ConvertGuiCounter1; ?>' name='shareclipStatus<?php echo $ConvertGuiCounter1; ?>'>Clipboard Status: <i>Not Copied</i></p>
          <p id='sharelinkURL<?php echo $ConvertGuiCounter1; ?>' name='sharelinkURL<?php echo $ConvertGuiCounter1; ?>'>File Link: <i>Not Generated</i></p>
          <input type="submit" id="sharegeneratebutton<?php echo $ConvertGuiCounter1; ?>" name="sharegeneratebutton<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text32; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">
          <input type="submit" id="sharecopybutton<?php echo $ConvertGuiCounter1; ?>" name="sharecopybutton<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text33; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">
          <script type="text/javascript">
            $(document).ready(function () {
              $('#sharegeneratebutton<?php echo $ConvertGuiCounter1; ?>').click(function() {
                $.ajax({
                type: 'POST',
                url: 'convertCore.php',
                data: { 
                  Token1:'<?php echo $Token1; ?>',
                  Token2:'<?php echo $Token2; ?>',
                  download:'<?php echo $File; ?>' },
                success: function(returnFile) {
                  toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                  document.getElementById('sharelinkStatus<?php echo $ConvertGuiCounter1; ?>').innerHTML = '<?php echo $Gui2Text24; ?><i><?php echo $Gui2Text26; ?></i>';
                  document.getElementById('shareclipStatus<?php echo $ConvertGuiCounter1; ?>').innerHTML = '<?php echo $Gui2Text27; ?><i><?php echo $Gui2Text28; ?></i>';
                  document.getElementById('sharelinkURL<?php echo $ConvertGuiCounter1; ?>').innerHTML = '<?php echo $Gui2Text29; ?><i><?php echo $FullURL.'/DATA/'.$SesHash3.'/'.$File; ?></i>';
                  copy_share_link("<?php echo $FullURL.'/DATA/'.$SesHash3.'/'.$File; ?>");
                  alert("<?php echo $Gui2Text73; ?>");
                  toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                  setTimeout(function() {
                    toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000); },
                error: function(ReturnData) {
                  toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                  toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                  setTimeout(function() {
                    toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000); 
                  alert("<?php echo $Gui2Text74; ?>"); } }); });
              $('#sharecopybutton<?php echo $ConvertGuiCounter1; ?>').click(function() {
                $.ajax({
                type: 'POST',
                url: 'convertCore.php',
                data: { 
                  Token1:'<?php echo $Token1; ?>',
                  Token2:'<?php echo $Token2; ?>',
                  download:'<?php echo $File; ?>' },
                success: function(returnFile) {
                  toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                  toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                  setTimeout(function() {
                    toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000); 
                  document.getElementById('sharelinkStatus<?php echo $ConvertGuiCounter1; ?>').innerHTML = '<?php echo $Gui2Text24; ?><i><?php echo $Gui2Text26; ?></i>';
                  document.getElementById('sharelinkURL<?php echo $ConvertGuiCounter1; ?>').innerHTML = '<?php echo $Gui2Text29; ?><i><?php echo $FullURL.'/DATA/'.$SesHash3.'/'.$File; ?></i>'; },
                error: function(ReturnData) {
                  toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                  toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                  setTimeout(function() {
                    toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000); 
                  alert("<?php echo $Gui2Text74; ?>");} }); }); });
          </script>
        </div>
        <?php } ?>

        <div id='deletefileOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='deletefileOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:2000px;"></p>
          <p><strong><?php echo $Gui2Text69; ?></strong></p>
          <input type='submit' id='confirmdeletefilebutton<?php echo $ConvertGuiCounter1; ?>' name='confirmdeletefilebutton<?php echo $ConvertGuiCounter1; ?>' value='<?php echo $Gui2Text70; ?>' onclick='toggle_visibility("loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>");'>
          <script type="text/javascript">
            $(document).ready(function () {
              $('#confirmdeletefilebutton<?php echo $ConvertGuiCounter1; ?>').click(function() {
                $.ajax({
                type: 'POST',
                url: 'convertCore.php',
                data: { 
                  Token1:'<?php echo $Token1; ?>',
                  Token2:'<?php echo $Token2; ?>',
                  filesToDelete:'<?php echo $File; ?>' },
                success: function(returnFile) {
                  toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                  location.reload(); },
                error: function(ReturnData) {
                  toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                  alert("<?php echo $Gui2Text74; ?>"); } }); }); });
          </script>
        </div>

        <?php if ($AllowUserVirusScan) { ?>
        <div id='scanfileOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='scanfileOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:2000px;"></p>
          <p><strong><?php echo $Gui2Text34; ?></strong></p>
          <input type="submit" id="scancorebutton<?php echo $ConvertGuiCounter1; ?>" name="scancorebutton<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text35; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">
          <input type="submit" id="clamscanbutton<?php echo $ConvertGuiCounter1; ?>" name="clamscanbutton<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text36; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">
          <input type="submit" id="scanallbutton<?php echo $ConvertGuiCounter1; ?>" name="scanallbutton<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text37; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">
          <script type="text/javascript">
            $(document).ready(function () {
              $('#scancorebutton<?php echo $ConvertGuiCounter1; ?>').click(function() {
                $.ajax({
                  type: 'POST',
                  url: 'convertCore.php',
                  data: {
                    Token1:'<?php echo $Token1; ?>',
                    Token2:'<?php echo $Token2; ?>',
                    scantype:'scancore',
                    filesToScan:'<?php echo $File; ?>' },
                    success: function(ReturnData) {
                      $.ajax({
                      type: 'POST',
                      url: 'convertCore.php',
                      data: { 
                        Token1:'<?php echo $Token1; ?>',
                        Token2:'<?php echo $Token2; ?>',
                        download:'<?php echo $ConsolidatedLogFileName; ?>' },
                      success: function(returnFile) {
                        toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        setTimeout(function() {
                          toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                        document.getElementById('downloadTarget').href = "<?php echo 'DATA/'.$SesHash3.'/'.$ConsolidatedLogFileName; ?>"; 
                        document.getElementById('downloadTarget').click(); } }); },
                    error: function(ReturnData) {
                      toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                      toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                      setTimeout(function() {
                        toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                      alert("<?php echo $Gui2Text72; ?>"); } }); });
              $('#clamscanbutton<?php echo $ConvertGuiCounter1; ?>').click(function() {
                $.ajax({
                  type: 'POST',
                  url: 'convertCore.php',
                  data: {
                    Token1:'<?php echo $Token1; ?>',
                    Token2:'<?php echo $Token2; ?>',
                    scantype:'clamav',
                    filesToScan:'<?php echo $File; ?>' },
                    success: function(ReturnData) {
                      $.ajax({
                      type: 'POST',
                      url: 'convertCore.php',
                      data: { 
                        Token1:'<?php echo $Token1; ?>',
                        Token2:'<?php echo $Token2; ?>',
                        download:'<?php echo $ConsolidatedLogFileName; ?>' },
                      success: function(returnFile) {
                        toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        setTimeout(function() {
                          toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                        document.getElementById('downloadTarget').href = "<?php echo 'DATA/'.$SesHash3.'/'.$ConsolidatedLogFileName; ?>"; 
                        document.getElementById('downloadTarget').click(); } }); },
                    error: function(ReturnData) {
                      toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                      toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                      setTimeout(function() {
                        toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                      alert("<?php echo $Gui2Text72; ?>"); } }); });
              $('#scanallbutton<?php echo $ConvertGuiCounter1; ?>').click(function() {
                $.ajax({
                  type: 'POST',
                  url: 'convertCore.php',
                  data: {
                    Token1:'<?php echo $Token1; ?>',
                    Token2:'<?php echo $Token2; ?>',
                    scantype:'all',
                    filesToScan:'<?php echo $File; ?>' },
                    success: function(ReturnData) {
                      $.ajax({
                      type: 'POST',
                      url: 'convertCore.php',
                      data: { 
                        Token1:'<?php echo $Token1; ?>',
                        Token2:'<?php echo $Token2; ?>',
                        download:'<?php echo $ConsolidatedLogFileName; ?>' },
                      success: function(returnFile) {
                        toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        setTimeout(function() {
                          toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                        document.getElementById('downloadTarget').href = "<?php echo 'DATA/'.$SesHash3.'/'.$ConsolidatedLogFileName; ?>"; 
                        document.getElementById('downloadTarget').click(); } }); },
                    error: function(ReturnData) {
                      toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                      toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                      setTimeout(function() {
                        toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                      alert("<?php echo $Gui2Text72; ?>"); } }); }); });
          </script>
        </div>
        <?php }

        if (in_array($extension, $PDFWorkArr) && in_array('OCR', $SupportedConversionTypes)) { 
        ?>
        <div id='pdfOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='pdfOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:2000px;"></p>
          <p><strong><?php echo $Gui2Text38; ?></strong></p>
          <p><?php echo $Gui2Text17; ?><input type="text" id='userpdffilename<?php echo $ConvertGuiCounter1; ?>' name='userpdffilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='pdfmethod<?php echo $ConvertGuiCounter1; ?>' name='pdfmethod<?php echo $ConvertGuiCounter1; ?>'>   
            <option value="0"><?php echo $Gui2Text39; ?></option>  
            <option value="1"><?php echo $Gui2Text39; ?> 1 (<?php echo $Gui2Text40; ?>)</option>
            <option value="2"><?php echo $Gui2Text39; ?> 2 (<?php echo $Gui2Text41; ?>)</option>
          </select>
          <select id='pdfextension<?php echo $ConvertGuiCounter1; ?>' name='pdfextension<?php echo $ConvertGuiCounter1; ?>'>   
            <option value="pdf"><?php echo $Gui2Text18; ?></option>
            <?php foreach ($DocumentArray as $gui2OcrArr) { ?>
            <option value="<?php echo $gui2OcrArr; ?>"><?php echo $gui2OcrArr; ?></option>   
            <?php } ?>
          </select></p>
          <p><input type="submit" id='pdfconvertSubmit<?php echo $ConvertGuiCounter1; ?>' name='pdfconvertSubmit<?php echo $ConvertGuiCounter1; ?>' value='<?php echo $Gui2Text52; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');"></p>
          <script type="text/javascript">
            $(document).ready(function () {
              $('#pdfconvertSubmit<?php echo $ConvertGuiCounter1; ?>').click(function() {
                $.ajax({
                  type: 'POST',
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
                        toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        setTimeout(function() {
                          toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                        document.getElementById('downloadTarget').href = "<?php echo 'DATA/'.$SesHash3.'/'; ?>"+document.getElementById('userpdffilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('pdfextension<?php echo $ConvertGuiCounter1; ?>').value; 
                        document.getElementById('downloadTarget').click(); } }); },
                      error: function(ReturnData) {
                        toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        setTimeout(function() {
                          toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                        alert("<?php echo $Gui2Text71; ?>"); } }); }); });
          </script>
        </div>
        <?php } 

        if (in_array($extension, $ArchiveArray) && in_array('Archive', $SupportedConversionTypes)) {
        ?>
        <div id='archiveOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='archiveOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:2000px;"></p>
          <p><strong><?php echo $Gui2Text42; ?></strong></p>
          <p><?php echo $Gui2Text17; ?><input type="text" id='userarchivefilename<?php echo $ConvertGuiCounter1; ?>' name='userarchivefilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='archiveextension<?php echo $ConvertGuiCounter1; ?>' name='archiveextension<?php echo $ConvertGuiCounter1; ?>'> 
            <option value="zip"><?php echo $Gui2Text18; ?></option>
            <?php foreach ($ArchiveArray as $gui2ArchArr) { ?>
            <option value="<?php echo $gui2ArchArr; ?>"><?php echo $gui2ArchArr; ?></option>
            <?php } ?>
          </select></p>
          <input type="submit" id="archiveconvertSubmit<?php echo $ConvertGuiCounter1; ?>" name="archiveconvertSubmit<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text53; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">
          <script type="text/javascript">
            $(document).ready(function () {
              $('#archiveconvertSubmit<?php echo $ConvertGuiCounter1; ?>').click(function() {
                $.ajax({
                  type: 'POST',
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
                        download:document.getElementById('userarchivefilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('archiveextension<?php echo $ConvertGuiCounter1; ?>').value },
                      success: function(returnFile) {
                        toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        setTimeout(function() {
                          toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                        document.getElementById('downloadTarget').href = "<?php echo 'DATA/'.$SesHash3.'/'; ?>"+document.getElementById('userarchivefilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('archiveextension<?php echo $ConvertGuiCounter1; ?>').value; 
                        document.getElementById('downloadTarget').click(); } }); },
                      error: function(ReturnData) {
                        toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        setTimeout(function() {
                          toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                        alert("<?php echo $Gui2Text71; ?>"); } }); }); });
          </script>
        </div>
        <?php } 

        if (in_array($extension, $DocumentArray) && in_array('Document', $SupportedConversionTypes)) {
        ?>
        <div id='docOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='docOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:2000px;"></p>
          <p><strong><?php echo $Gui2Text43; ?></strong></p>
          <p><?php echo $Gui2Text17; ?><input type="text" id='userdocfilename<?php echo $ConvertGuiCounter1; ?>' name='userdocfilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='docextension<?php echo $ConvertGuiCounter1; ?>' name='docextension<?php echo $ConvertGuiCounter1; ?>'> 
            <option value="txt"><?php echo $Gui2Text18; ?></option>
            <?php foreach ($DocumentArray as $gui2DocArr) { ?>
            <option value="<?php echo $gui2DocArr; ?>"><?php echo $gui2DocArr; ?></option>
            <?php } ?>
          </select></p>
          <input type="submit" id="docconvertSubmit<?php echo $ConvertGuiCounter1; ?>" name="docconvertSubmit<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text54; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">
          <script type="text/javascript">
            $(document).ready(function () {
              $('#docconvertSubmit<?php echo $ConvertGuiCounter1; ?>').click(function() {
                $.ajax({
                  type: 'POST',
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
                        download:document.getElementById('userdocfilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('docextension<?php echo $ConvertGuiCounter1; ?>').value },
                      success: function(returnFile) {
                        toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        setTimeout(function() {
                          toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                        document.getElementById('downloadTarget').href = "<?php echo 'DATA/'.$SesHash3.'/'; ?>"+document.getElementById('userdocfilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('docextension<?php echo $ConvertGuiCounter1; ?>').value; 
                        document.getElementById('downloadTarget').click(); } }); },
                      error: function(ReturnData) {
                        toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        setTimeout(function() {
                          toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                        alert("<?php echo $Gui2Text71; ?>"); } }); }); });
          </script>
        </div>
        <?php }

        if (in_array($extension, $SpreadsheetArray) && in_array('Document', $SupportedConversionTypes)) {
        ?>
        <div id='spreadOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='spreadOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:2000px;"></p>
          <p><strong><?php echo $Gui2Text44; ?></strong></p>
          <p><?php echo $Gui2Text17; ?><input type="text" id='userspreadfilename<?php echo $ConvertGuiCounter1; ?>' name='userspreadfilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='spreadextension<?php echo $ConvertGuiCounter1; ?>' name='spreadextension<?php echo $ConvertGuiCounter1; ?>'>
            <option value="ods"><?php echo $Gui2Text18; ?></option> 
            <?php foreach ($SpreadsheetArray as $gui2SpreadArr) { ?>
            <option value="<?php echo $gui2SpreadArr; ?>"><?php echo $gui2SpreadArr; ?></option>
            <?php } ?>
          </select></p>
          <input type="submit" id="spreadconvertSubmit<?php echo $ConvertGuiCounter1; ?>" name="spreadconvertSubmit<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text55; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">        
          <script type="text/javascript">
            $(document).ready(function () {
              $('#spreadconvertSubmit<?php echo $ConvertGuiCounter1; ?>').click(function() {
                $.ajax({
                  type: 'POST',
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
                        download:document.getElementById('userspreadfilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('spreadextension<?php echo $ConvertGuiCounter1; ?>').value },
                      success: function(returnFile) {
                        toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        setTimeout(function() {
                          toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                        document.getElementById('downloadTarget').href = "<?php echo 'DATA/'.$SesHash3.'/'; ?>"+document.getElementById('userspreadfilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('spreadextension<?php echo $ConvertGuiCounter1; ?>').value; 
                        document.getElementById('downloadTarget').click(); } }); },
                      error: function(ReturnData) {
                        toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        setTimeout(function() {
                          toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                        alert("<?php echo $Gui2Text71; ?>"); } }); }); });
          </script>
        </div>
        <?php }

        if (in_array($extension, $PresentationArray) && in_array('Document', $SupportedConversionTypes)) {
        ?>
        <div id='presentationOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='presentationOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:2000px;"></p>
          <p><strong>Convert This Presentation</strong></p>
          <p><?php echo $Gui2Text17; ?><input type="text" id='userpresentationfilename<?php echo $ConvertGuiCounter1; ?>' name='userpresentationfilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='presentationextension<?php echo $ConvertGuiCounter1; ?>' name='presentationextension<?php echo $ConvertGuiCounter1; ?>'>
            <option value="odp"><?php echo $Gui2Text18; ?></option>
            <?php foreach ($PresentationArray as $gui2PresArr) { ?>
            <option value="<?php echo $gui2PresArr; ?>"><?php echo $gui2PresArr; ?></option>
            <?php } ?>
          </select></p>
          <input type="submit" id="presentationconvertSubmit<?php echo $ConvertGuiCounter1; ?>" name="presentationconvertSubmit<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text56; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">
          <script type="text/javascript">
            $(document).ready(function () {
              $('#presentationconvertSubmit<?php echo $ConvertGuiCounter1; ?>').click(function() {
                $.ajax({
                  type: 'POST',
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
                        download:document.getElementById('userpresentationfilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('presentationextension<?php echo $ConvertGuiCounter1; ?>').value },
                      success: function(returnFile) {
                        toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        setTimeout(function() {
                          toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                        document.getElementById('downloadTarget').href = "<?php echo 'DATA/'.$SesHash3.'/'; ?>"+document.getElementById('userspreadfilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('presentationextension<?php echo $ConvertGuiCounter1; ?>').value; 
                        document.getElementById('downloadTarget').click(); } }); },
                      error: function(ReturnData) {
                        toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        setTimeout(function() {
                          toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                        alert("<?php echo $Gui2Text71; ?>"); } }); }); });
          </script>
        </div>
        <?php }

        if (in_array($extension, $MediaInputArray) && in_array('Audio', $SupportedConversionTypes)) {
        ?>
        <div id='audioOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='audioOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:2000px;"></p>
          <p><strong><?php echo $Gui2Text45; ?></strong></p>
          <p><?php echo $Gui2Text17; ?><input type="text" id='useraudiofilename<?php echo $ConvertGuiCounter1; ?>' name='useraudiofilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='audioextension<?php echo $ConvertGuiCounter1; ?>' name='audioextension<?php echo $ConvertGuiCounter1; ?>'> 
            <option value="mp3"><?php echo $Gui2Text18; ?></option>
            <?php foreach ($MediaOutputArray as $gui2AudArr) { ?>
            <option value="<?php echo $gui2AudArr; ?>"><?php echo $gui2AudArr; ?></option>
            <?php } ?>
          </select></p>
          <p><?php echo $Gui2Text66; ?><input type="number" size="6" id='bitrate<?php echo $ConvertGuiCounter1; ?>' name='bitrate<?php echo $ConvertGuiCounter1; ?>' value="0" min="0" max="100000"></p>
          <input type="submit" id="audioconvertSubmit<?php echo $ConvertGuiCounter1; ?>" name="audioconvertSubmit<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text57; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">
          <script type="text/javascript">
            $(document).ready(function () {
              $('#audioconvertSubmit<?php echo $ConvertGuiCounter1; ?>').click(function() {
                $.ajax({
                  type: 'POST',
                  url: 'convertCore.php',
                  data: {
                    Token1:'<?php echo $Token1; ?>',
                    Token2:'<?php echo $Token2; ?>',
                    bitrate:$('#bitrate<?php echo $ConvertGuiCounter1; ?>').val(),
                    convertSelected:'<?php echo $File; ?>',
                    extension:document.getElementById('audioextension<?php echo $ConvertGuiCounter1; ?>').value,
                    userconvertfilename:document.getElementById('useraudiofilename<?php echo $ConvertGuiCounter1; ?>').value },
                    success: function(ReturnData) {
                      $.ajax({
                      type: 'POST',
                      url: 'convertCore.php',
                      data: { 
                        Token1:'<?php echo $Token1; ?>',
                        Token2:'<?php echo $Token2; ?>',
                        download:document.getElementById('useraudiofilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('audioextension<?php echo $ConvertGuiCounter1; ?>').value },
                      success: function(returnFile) {
                        toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        setTimeout(function() {
                          toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                        document.getElementById('downloadTarget').href = "<?php echo 'DATA/'.$SesHash3.'/'; ?>"+document.getElementById('useraudiofilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('audioextension<?php echo $ConvertGuiCounter1; ?>').value; 
                        document.getElementById('downloadTarget').click(); } }); },
                      error: function(ReturnData) {
                        toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        setTimeout(function() {
                          toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                        alert("<?php echo $Gui2Text71; ?>"); } }); }); });
          </script>
        </div>
        <?php } 

        if (in_array($extension, $VideoInputArray) && in_array('Video', $SupportedConversionTypes)) {
        ?>
        <div id='videoOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='videoOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:2000px;"></p>
          <p><strong><?php echo $Gui2Text46; ?></strong></p>
          <p><?php echo $Gui2Text17; ?><input type="text" id='uservideofilename<?php echo $ConvertGuiCounter1; ?>' name='uservideofilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='videoextension<?php echo $ConvertGuiCounter1; ?>' name='videoextension<?php echo $ConvertGuiCounter1; ?>'>
            <option value="mp4"><?php echo $Gui2Text18; ?></option> 
            <?php foreach ($VideoOutputArray as $gui2VidArr) { ?>
            <option value="<?php echo $gui2VidArr; ?>"><?php echo $gui2VidArr; ?></option>
            <?php } ?>
          </select></p>
          <input type="submit" id="videoconvertSubmit<?php echo $ConvertGuiCounter1; ?>" name="videoconvertSubmit<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text58; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">
          <script type="text/javascript">
            $(document).ready(function () {
              $('#videoconvertSubmit<?php echo $ConvertGuiCounter1; ?>').click(function() {
                $.ajax({
                  type: 'POST',
                  url: 'convertCore.php',
                  data: {
                    Token1:'<?php echo $Token1; ?>',
                    Token2:'<?php echo $Token2; ?>',
                    convertSelected:'<?php echo $File; ?>',
                    extension:document.getElementById('videoextension<?php echo $ConvertGuiCounter1; ?>').value,
                    userconvertfilename:document.getElementById('uservideofilename<?php echo $ConvertGuiCounter1; ?>').value },
                    success: function(ReturnData) {
                      $.ajax({
                      type: 'POST',
                      url: 'convertCore.php',
                      data: { 
                        Token1:'<?php echo $Token1; ?>',
                        Token2:'<?php echo $Token2; ?>',
                        download:document.getElementById('uservideofilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('videoextension<?php echo $ConvertGuiCounter1; ?>').value },
                      success: function(returnFile) {
                        toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        setTimeout(function() {
                          toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                        document.getElementById('downloadTarget').href = "<?php echo 'DATA/'.$SesHash3.'/'; ?>"+document.getElementById('uservideofilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('videoextension<?php echo $ConvertGuiCounter1; ?>').value; 
                        document.getElementById('downloadTarget').click(); } }); },
                      error: function(ReturnData) {
                        toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        setTimeout(function() {
                          toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                        alert("<?php echo $Gui2Text71; ?>"); } }); }); });
          </script>
        </div>
        <?php } 

        if (in_array($extension, $StreamArray) && in_array('Stream', $SupportedConversionTypes)) {
        ?>
        <div id='streamOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='streamOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:2000px;"></p>
          <p><strong><?php echo $Gui2Text47; ?></strong></p>
          <p><?php echo $Gui2Text17; ?><input type="text" id='userstreamfilename<?php echo $ConvertGuiCounter1; ?>' name='userstreamfilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='streamextension<?php echo $ConvertGuiCounter1; ?>' name='streamextension<?php echo $ConvertGuiCounter1; ?>'>
            <option value="mp4"><?php echo $Gui2Text18; ?></option>
            <?php foreach ($MediaOutputArray as $gui2StreamArr) { ?>
            <option value="<?php echo $gui2StreamArr; ?>"><?php echo $gui2StreamArr; ?></option>
            <?php } ?>
          </select></p>
          <input type="submit" id="streamconvertSubmit<?php echo $ConvertGuiCounter1; ?>" name="streamconvertSubmit<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text59; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">
          <script type="text/javascript">
            $(document).ready(function () {
              $('#streamconvertSubmit<?php echo $ConvertGuiCounter1; ?>').click(function() {
                $.ajax({
                  type: 'POST',
                  url: 'convertCore.php',
                  data: {
                    Token1:'<?php echo $Token1; ?>',
                    Token2:'<?php echo $Token2; ?>',
                    convertSelected:'<?php echo $File; ?>',
                    extension:document.getElementById('streamextension<?php echo $ConvertGuiCounter1; ?>').value,
                    userconvertfilename:document.getElementById('userstreamfilename<?php echo $ConvertGuiCounter1; ?>').value },
                    success: function(ReturnData) {
                      $.ajax({
                      type: 'POST',
                      url: 'convertCore.php',
                      data: { 
                        Token1:'<?php echo $Token1; ?>',
                        Token2:'<?php echo $Token2; ?>',
                        download:document.getElementById('userstreamfilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('streamextension<?php echo $ConvertGuiCounter1; ?>').value },
                      success: function(returnFile) {
                        toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        setTimeout(function() {
                          toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                        document.getElementById('downloadTarget').href = "<?php echo 'DATA/'.$SesHash3.'/'; ?>"+document.getElementById('userstreamfilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('streamextension<?php echo $ConvertGuiCounter1; ?>').value; 
                        document.getElementById('downloadTarget').click(); } }); },
                      error: function(ReturnData) {
                        toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        setTimeout(function() {
                          toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                        alert("<?php echo $Gui2Text71; ?>"); } }); }); });
          </script>
        </div>
        <?php } 

        if (in_array($extension, $ModelArray) && in_array('Model', $SupportedConversionTypes)) {
        ?>
        <div id='modelOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='modelOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:2000px;"></p>
          <p><strong><?php echo $Gui2Text48; ?></strong></p>
          <p><?php echo $Gui2Text17; ?><input type="text" id='usermodelfilename<?php echo $ConvertGuiCounter1; ?>' name='usermodelfilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='modelextension<?php echo $ConvertGuiCounter1; ?>' name='modelextension<?php echo $ConvertGuiCounter1; ?>'>
            <option value="3ds"><?php echo $Gui2Text18; ?></option>
            <?php foreach ($ModelArray as $gui2ModArr) { ?>
            <option value="<?php echo $gui2ModArr; ?>"><?php echo $gui2ModArr; ?></option>
            <?php } ?>
          </select></p>
          <input type="submit" id="modelconvertSubmit<?php echo $ConvertGuiCounter1; ?>" name="modelconvertSubmit<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text60; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">
          <script type="text/javascript">
            $(document).ready(function () {
              $('#modelconvertSubmit<?php echo $ConvertGuiCounter1; ?>').click(function() {
                $.ajax({
                  type: 'POST',
                  url: 'convertCore.php',
                  data: {
                    Token1:'<?php echo $Token1; ?>',
                    Token2:'<?php echo $Token2; ?>',
                    convertSelected:'<?php echo $File; ?>',
                    extension:document.getElementById('modelextension<?php echo $ConvertGuiCounter1; ?>').value,
                    userconvertfilename:document.getElementById('usermodelfilename<?php echo $ConvertGuiCounter1; ?>').value },
                    success: function(ReturnData) {
                      $.ajax({
                      type: 'POST',
                      url: 'convertCore.php',
                      data: { 
                        Token1:'<?php echo $Token1; ?>',
                        Token2:'<?php echo $Token2; ?>',
                        download:document.getElementById('usermodelfilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('modelextension<?php echo $ConvertGuiCounter1; ?>').value },
                      success: function(returnFile) {
                        toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        setTimeout(function() {
                          toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                        document.getElementById('downloadTarget').href = "<?php echo 'DATA/'.$SesHash3.'/'; ?>"+document.getElementById('usermodelfilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('modelextension<?php echo $ConvertGuiCounter1; ?>').value; 
                        document.getElementById('downloadTarget').click(); } }); },
                      error: function(ReturnData) {
                        toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        setTimeout(function() {
                          toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                        alert("<?php echo $Gui2Text71; ?>"); } }); }); });

          </script>
        </div>
        <?php } 

        if (in_array($extension, $SubtitleInputArray) && in_array('Subtitle', $SupportedConversionTypes)) {
        ?>
        <div id='subtitleOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='subtitleOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:2000px;"></p>
          <p><strong><?php echo $Gui2Text75; ?></strong></p>
          <p><?php echo $Gui2Text17; ?><input type="text" id='usersubtitlefilename<?php echo $ConvertGuiCounter1; ?>' name='usersubtitlefilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='subtitleextension<?php echo $ConvertGuiCounter1; ?>' name='subtitleextension<?php echo $ConvertGuiCounter1; ?>'>
            <option value=""><?php echo $Gui2Text18; ?></option>
            <?php foreach ($SubtitleOutputArray as $gui2SubArr) { ?>
            <option value="<?php echo $gui2SubArr; ?>"><?php echo $gui2SubArr; ?></option>
            <?php } ?>
          </select></p>
          <input type="submit" id="subtitleconvertSubmit<?php echo $ConvertGuiCounter1; ?>" name="subtitleconvertSubmit<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text76; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">
          <script type="text/javascript">
            $(document).ready(function () {
              $('#subtitleconvertSubmit<?php echo $ConvertGuiCounter1; ?>').click(function() {
                $.ajax({
                  type: 'POST',
                  url: 'convertCore.php',
                  data: {
                    Token1:'<?php echo $Token1; ?>',
                    Token2:'<?php echo $Token2; ?>',
                    convertSelected:'<?php echo $File; ?>',
                    extension:document.getElementById('subtitleextension<?php echo $ConvertGuiCounter1; ?>').value,
                    userconvertfilename:document.getElementById('usersubtitlefilename<?php echo $ConvertGuiCounter1; ?>').value },
                    success: function(ReturnData) {
                      $.ajax({
                      type: 'POST',
                      url: 'convertCore.php',
                      data: { 
                        Token1:'<?php echo $Token1; ?>',
                        Token2:'<?php echo $Token2; ?>',
                        download:document.getElementById('usersubtitlefilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('subtitleextension<?php echo $ConvertGuiCounter1; ?>').value },
                      success: function(returnFile) {
                        toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        setTimeout(function() {
                          toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                        document.getElementById('downloadTarget').href = "<?php echo 'DATA/'.$SesHash3.'/'; ?>"+document.getElementById('usersubtitlefilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('subtitleextension<?php echo $ConvertGuiCounter1; ?>').value; 
                        document.getElementById('downloadTarget').click(); } }); },
                      error: function(ReturnData) {
                        toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        setTimeout(function() {
                          toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                        alert("<?php echo $Gui2Text71; ?>"); } }); }); });
          </script>
        </div>
        <?php } 

        if (in_array($extension, $DrawingArray) && in_array('Drawing', $SupportedConversionTypes)) {
        ?>
        <div id='drawingOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='drawingOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:2000px;"></p>
          <p><strong><?php echo $Gui2Text49; ?></strong></p>
          <p><?php echo $Gui2Text17; ?><input type="text" id='userdrawingfilename<?php echo $ConvertGuiCounter1; ?>' name='userdrawingfilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='drawingextension<?php echo $ConvertGuiCounter1; ?>' name='drawingextension<?php echo $ConvertGuiCounter1; ?>'>
            <option value="jpg"><?php echo $Gui2Text18; ?></option>
            <?php foreach ($DrawingArray as $gui2DraArr) { ?>
            <option value="<?php echo $gui2DraArr; ?>"><?php echo $gui2DraArr; ?></option>
            <?php } ?>
          </select></p>
          <input type="submit" id="drawingconvertSubmit<?php echo $ConvertGuiCounter1; ?>" name="drawingconvertSubmit<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text61; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">     
          <script type="text/javascript">
            $(document).ready(function () {
              $('#drawingconvertSubmit<?php echo $ConvertGuiCounter1; ?>').click(function() {
                $.ajax({
                  type: 'POST',
                  url: 'convertCore.php',
                  data: {
                    Token1:'<?php echo $Token1; ?>',
                    Token2:'<?php echo $Token2; ?>',
                    convertSelected:'<?php echo $File; ?>',
                    extension:document.getElementById('drawingextension<?php echo $ConvertGuiCounter1; ?>').value,
                    userconvertfilename:document.getElementById('userdrawingfilename<?php echo $ConvertGuiCounter1; ?>').value },
                    success: function(ReturnData) {
                      $.ajax({
                      type: 'POST',
                      url: 'convertCore.php',
                      data: { 
                        Token1:'<?php echo $Token1; ?>',
                        Token2:'<?php echo $Token2; ?>',
                        download:document.getElementById('userdrawingfilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('drawingextension<?php echo $ConvertGuiCounter1; ?>').value },
                      success: function(returnFile) {
                        toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        setTimeout(function() {
                          toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                        document.getElementById('downloadTarget').href = "<?php echo 'DATA/'.$SesHash3.'/'; ?>"+document.getElementById('userdrawingfilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('drawingextension<?php echo $ConvertGuiCounter1; ?>').value; 
                        document.getElementById('downloadTarget').click(); } }); },
                      error: function(ReturnData) {
                        toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        setTimeout(function() {
                          toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                        alert("<?php echo $Gui2Text71; ?>"); } }); }); });
          </script>
        </div>
        <?php } 

        if (in_array($extension, $ImageArray) && in_array('Image', $SupportedConversionTypes)) {
        ?>
        <div id='imageOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='imageOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:2000px;"></p>
          <p><strong><?php echo $Gui2Text50; ?></strong></p>
          <p><?php echo $Gui2Text17; ?><input type="text" id='userphotofilename<?php echo $ConvertGuiCounter1; ?>' name='userphotofilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='photoextension<?php echo $ConvertGuiCounter1; ?>' name='photoextension<?php echo $ConvertGuiCounter1; ?>'>
            <option value="jpg"><?php echo $Gui2Text18; ?></option>
            <?php foreach ($ImageArray as $gui2ImaArr) { ?>
            <option value="<?php echo $gui2ImaArr; ?>"><?php echo $gui2ImaArr; ?></option>
            <?php } ?>
          </select></p>
          <p><?php echo $Gui2Text64; ?></p>
          <p><input type="number" size="4" value="0" id='width<?php echo $ConvertGuiCounter1; ?>' name='width<?php echo $ConvertGuiCounter1; ?>' min="0" max="10000"> X <input type="number" size="4" value="0" id="height<?php echo $ConvertGuiCounter1; ?>" name="height<?php echo $ConvertGuiCounter1; ?>" min="0"  max="10000"></p> 
          <p><?php echo $Gui2Text65; ?><input type="number" size="3" id='rotate<?php echo $ConvertGuiCounter1; ?>' name='rotate<?php echo $ConvertGuiCounter1; ?>' value="0" min="0" max="359"></p>
          <input type="submit" id='convertPhotoSubmit<?php echo $ConvertGuiCounter1; ?>' name='convertPhotoSubmit<?php echo $ConvertGuiCounter1; ?>' value='<?php echo $Gui2Text62; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">
          <script type="text/javascript">
            $(document).ready(function () {
              $('#convertPhotoSubmit<?php echo $ConvertGuiCounter1; ?>').click(function() {
                $.ajax({
                  type: 'POST',
                  url: 'convertCore.php',
                  data: {
                    Token1:'<?php echo $Token1; ?>',
                    Token2:'<?php echo $Token2; ?>',
                    convertSelected:'<?php echo $File; ?>',
                    rotate:$('#rotate<?php echo $ConvertGuiCounter1; ?>').val(),
                    width:$('#width<?php echo $ConvertGuiCounter1; ?>').val(),
                    height:$('#height<?php echo $ConvertGuiCounter1; ?>').val(),
                    extension:document.getElementById('photoextension<?php echo $ConvertGuiCounter1; ?>').value,
                    userconvertfilename:document.getElementById('userphotofilename<?php echo $ConvertGuiCounter1; ?>').value },
                    success: function(ReturnData) {
                      $.ajax({
                      type: 'POST',
                      url: 'convertCore.php',
                      data: { 
                        Token1:'<?php echo $Token1; ?>',
                        Token2:'<?php echo $Token2; ?>',
                        download:document.getElementById('userphotofilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('photoextension<?php echo $ConvertGuiCounter1; ?>').value },
                      success: function(returnFile) {
                        toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        setTimeout(function() {
                          toggle_visibility('victoryCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                        document.getElementById('downloadTarget').href = "<?php echo 'DATA/'.$SesHash3.'/'; ?>"+document.getElementById('userphotofilename<?php echo $ConvertGuiCounter1; ?>').value+'.'+document.getElementById('photoextension<?php echo $ConvertGuiCounter1; ?>').value; 
                        document.getElementById('downloadTarget').click(); } }); },
                      error: function(ReturnData) {
                        toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>');
                        setTimeout(function() {
                          toggle_visibility('failureCommandDiv<?php echo $ConvertGuiCounter1; ?>'); }, 5000);
                        alert("<?php echo $Gui2Text71; ?>"); } }); }); });
          </script>
        <?php } ?>
      </div>
      <hr />
      <?php } ?>
    </div>
    <?php
    // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
    $gui2AudArr = $gui2VidArr = $gui2StreamArr = $gui2DocArr = $gui2SpreadArr = $gui2PresArr = $gui2ArchArr = $gui2ImaArr = $gui2ModArr = $gui2SubArr = $gui2DraArr = $gui2OcrArr = NULL;
    unset($gui2AudArr, $gui2VidArr, $gui2StreamArr, $gui2DocArr, $gui2SpreadArr, $gui2PresArr, $gui2ArchArr, $gui2ImaArr, $gui2ModArr, $gui2SubArr, $gui2DraArr, $gui2OcrArr);