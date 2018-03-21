<?php
$Files = getFiles($ConvertTempDir);
$fileCount = count($Files);
if (!is_numeric($fileCount)) $fileCount = 'an unknown number of';
include ('header.php');
?>

    <div id="header-text" style="max-width:1000px; margin-left:auto; margin-right:auto; text-align:center;">
      <h1>HRConvert2</h1>
      <hr />
      <h3>File Conversion Options</h3>
      <p>You have uploaded <?php echo $fileCount; ?> valid files to HRConvert2.</p> 
      <p>Your files are now ready to convert using the options below.</p>
      <hr />
    </div>

    <div align="center">
      <br />
      <img id='loadingCommandDiv' name='loadingCommandDiv' src='Resources/pacman.gif' style="max-width:64px; max-height:64px; display:none;"/>
      <br />
    </div>

    <div id="compressAll" name="compressAll" style="max-width:1000px; margin-left:auto; margin-right:auto; text-align:center;">
      <button id="scandocMoreOptionsButton" name="scandocMoreOptionsButton" class="info-button" onclick="toggle_visibility('compressAllOptions');">Bulk File Options</button>
      <div id="compressAllOptions" name="compressAllOptions" align="center" style="display:none;">
        <p>Compress & Download All Files:</p>
        <p>Specify Filename: <input type="text" id='userfilename' name='userfilename' value='HRConvert2_Files-<?php echo $Date; ?>'></p> 
        <select id='archextension' name='archextension'> 
          <option value="">Select Format</option>
          <option value="zip">Zip</option>
          <option value="rar">Rar</option>
          <option value="tar">Tar</option>
          <option value="7z">7z</option>
        </select>
        <input type="submit" id="archiveAllSubmit" name="archiveAllSubmit" class="info-button" value='Compress & Download' onclick="toggle_visibility('loadingCommandDiv');">
        <hr />
      </div>
    </div>

    <div style="max-width:1000px; margin-left:auto; margin-right:auto;">

      <?php
      foreach ($Files as $File) {
        $extension = getExtension($ConvertTempDir.'/'.$File);
        if (!in_array($extension, $convertArr)) continue;
        $ConvertGuiCounter1++;
      ?>

      <div id="file<?php echo $ConvertGuiCounter1; ?>" name="<?php echo $ConvertGuiCounter1; ?>">
        <p href=""><strong><?php echo $ConvertGuiCounter1; ?>.</strong> <u><?php echo $File; ?></u></p>
        
        <div id="buttonDiv<?php echo $ConvertGuiCounter1; ?>" name="buttonDiv<?php echo $ConvertGuiCounter1; ?>" style="height:25px;">
          <img id="allfileButton<?php echo $ConvertGuiCounter1; ?>" name="allfileButton<?php echo $ConvertGuiCounter1; ?>" src="Resources/archive.png" style="float:left; display:block;" 
           onclick="toggle_visibility('allfileOptionsDiv<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('allfileButton<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('allfileXButton<?php echo $ConvertGuiCounter1; ?>');"/>
          <img id="allfileXButton<?php echo $ConvertGuiCounter1; ?>" name="allfileXButton<?php echo $ConvertGuiCounter1; ?>" src="Resources/x.png" style="float:left; display:none;" 
           onclick="toggle_visibility('allfileOptionsDiv<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('allfileButton<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('allfileXButton<?php echo $ConvertGuiCounter1; ?>');"/> 
          
          <a style="float:left;">&nbsp;|&nbsp;</a>
          
          <img id="docscanButton<?php echo $ConvertGuiCounter1; ?>" name="docscanButton<?php echo $ConvertGuiCounter1; ?>" src="Resources/docscan.png" style="float:left; display:block;" 
           onclick="toggle_visibility('pdfOptionsDiv<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('docscanButton<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('docscanXButton<?php echo $ConvertGuiCounter1; ?>');"/>
          <img id="docscanXButton<?php echo $ConvertGuiCounter1; ?>" name="docscanXButton<?php echo $ConvertGuiCounter1; ?>" src="Resources/x.png" style="float:left; display:none;" 
           onclick="toggle_visibility('pdfOptionsDiv<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('docscanButton<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('docscanXButton<?php echo $ConvertGuiCounter1; ?>');"/> 

          <a style="float:left;">&nbsp;|&nbsp;</a>
        </div>


        <div id='allfileOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='allfileOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"><hr /></p>
          <p>Archive This File</p>
          <p>Specify Filename: <input type="text" id='userconvertfilename' name='userconvertfilename' value='<?php echo str_replace('.', '_', $File); ?>'>
          <select id='extension' name='extension'> 
            <option value="">Select Format</option>
            <option value="zip">Zip</option>
            <option value="rar">Rar</option>
            <option value="tar">Tar</option>
            <option value="7z">7z</option>
          </select></p>
          <input type="submit" id="convertSubmit" name="convertSubmit" value='Convert Document' onclick="toggle_visibility('loadingCommandDiv');">
        </div>
          
        <?php
        if (in_array($extension, $pdfWorkArr)) { 
        ?>

        <div id='pdfOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='pdfOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"><hr /></p>
          <p>Optical Character Recognition Options</p>
          <p>Specify Filename: <input type="text" id='userpdfconvertfilename' name='userpdfconvertfilename' value='<?php echo str_replace('.', '_', $File); ?>'>
          <select id='method1' name='method1'>   
            <option value="0">Select Method</option>  
            <option value="1">Method 1 (Simple)</option>
            <option value="2">Method 2 (Advanced)</option>
          </select>
          <select id='pdfextension' name='pdfextension'>   
            <option value="">Select Format</option> 
            <option value="pdf">Pdf</option>   
            <option value="doc">Doc</option>
            <option value="docx">Docx</option>
            <option value="rtf">Rtf</option>
            <option value="txt">Txt</option>
            <option value="odf">Odf</option>
          </select></p>
          <p><input type="submit" id='pdfwork' name='pdfwork' value='Convert Into Document' onclick="toggle_visibility('loadingCommandDiv');"></p>
        </div>

        <?php } 
        if (in_array($extension, $ArchiveArray)) {
        ?>
        
        <div id='archiveOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='archiveOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"><hr /></p>
          <p>Convert This Archive</p>
          <p>Specify Filename: <input type="text" id='userfilename' name='userfilename' value='<?php echo str_replace('.', '_', $File); ?>'>
          <select id='archextension' name='archextension'> 
            <option value="">Select Format</option>
            <option value="zip">Zip</option>
            <option value="rar">Rar</option>
            <option value="tar">Tar</option>
            <option value="7z">7z</option>
          </select></p>
          <input type="submit" id="archiveFileSubmit" name="archiveFileSubmit" value='Archive Files' onclick="toggle_visibility('loadingCommandDiv'); display:none;">
        </div>

        <?php } 
        if (in_array($extension, $DocArray)) {
        ?>
        
        <div id='documentOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='documentOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"><hr /></p>
          <p>Convert This Document</p>
          <p>Specify Filename: <input type="text" id='userconvertfilename' name='userconvertfilename' value='<?php echo str_replace('.', '_', $File); ?>'>
          <select id='extension' name='extension'> 
            <option value="">Select Format</option>
            <option value="doc">Doc</option>
            <option value="docx">Docx</option>
            <option value="rtf">Rtf</option>
            <option value="txt">Txt</option>
            <option value="odf">Odf</option>
            <option value="pdf">Pdf</option>
          </select></p>
          <input type="submit" id="convertSubmit" name="convertSubmit" value='Convert Document' onclick="toggle_visibility('loadingCommandDiv');">
        </div>

        <?php }

        if (in_array($extension, $SpreadsheetArray)) {
        ?>
        
        <div id='spreadsheetOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='spreadsheetOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"><hr /></p>
          <p>Convert This Spreadsheet</p>
          <p>Specify Filename: <input type="text" id='userconvertfilename' name='userconvertfilename' value='<?php echo str_replace('.', '_', $File); ?>'>
          <select id='extension' name='extension'>
            <option value="">Select Format</option> 
            <option value="xls">Xls</option>
            <option value="xlsx">Xlsx</option>
            <option value="ods">Ods</option>
            <option value="pdf">Pdf</option>
          </select></p>
          <input type="submit" id="convertSubmit" name="convertSubmit" value='Convert Spreadsheet' onclick="toggle_visibility('loadingCommandDiv');">
        </div>

        <?php }

        if (in_array($extension, $PresentationArray)) {
        ?>
        
        <div id='presentationOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='presentationOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"><hr /></p>
          <p>Convert This Presentation</p>
          <p>Specify Filename: <input type="text" id='userconvertfilename' name='userconvertfilename' value='<?php echo str_replace('.', '_', $File); ?>'>
          <select id='extension' name='extension'>
            <option value="">Select Format</option>
            <option value="pages">Pages</option>
            <option value="pptx">Pptx</option>
            <option value="ppt">Ppt</option>
            <option value="xps">Xps</option>
            <option value="potx">Potx</option>
            <option value="pot">Pot</option>
            <option value="ppa">Ppa</option>
            <option value="odp">Odp</option>
          </select></p>
          <input type="submit" id="convertSubmit" name="convertSubmit" value='Convert Presentation' onclick="toggle_visibility('loadingCommandDiv');">
        </div>

        <?php } 
        if (in_array($extension, $MediaArray)) {
        ?>
        
        <div id='audioOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='audioOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"><hr /></p>
          <p>Convert This Audio</p>
          <p>Specify Filename: <input type="text" id='userconvertfilename' name='userconvertfilename' value='<?php echo str_replace('.', '_', $File); ?>'>
          <select id='extension' name='extension'> 
            <option value="">Select Format</option>
            <option value="mp2">Mp2</option>  
            <option value="mp3">Mp3</option>
            <option value="wav">Wav</option>
            <option value="wma">Wma</option>
            <option value="flac">Flac</option>
            <option value="ogg">Ogg</option>
          </select></p>
          <input type="submit" id="convertSubmit" name="convertSubmit" value='Convert Audio' onclick="toggle_visibility('loadingCommandDiv');">
        </div>

        <?php } 
        if (in_array($extension, $VideoArray)) {
        ?>
        
        <div id='videoOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='videoOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"><hr /></p>
          <p>Convert This Video</p>
          <p>Specify Filename: <input type="text" id='userconvertfilename' name='userconvertfilename' value='<?php echo str_replace('.', '_', $File); ?>'>
          <select id='extension' name='extension'>
            <option value="">Select Format</option> 
            <option value="3gp">3gp</option> 
            <option value="mkv">Mkv</option> 
            <option value="avi">Avi</option>
            <option value="mp4">Mp4</option>
            <option value="flv">Flv</option>
            <option value="mpeg">Mpeg</option>
            <option value="wmv">Wmv</option>
          </select></p>
          <input type="submit" id="convertSubmit" name="convertSubmit" value='Convert Video' onclick="toggle_visibility('loadingCommandDiv');">
        </div>

        <?php } 
        if (in_array($extension, $ModelArray)) {
        ?>
        
        <div id='modelOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='modelOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"><hr /></p>
          <p>Convert This 3D Model</p>
          <p>Specify Filename: <input type="text" id='userconvertfilename' name='userconvertfilename' value='<?php echo str_replace('.', '_', $File); ?>'>
          <select id='extension' name='extension'>
            <option value="">Select Format</option>
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
          <input type="submit" id="convertSubmit" name="convertSubmit" value='Convert Model' onclick="toggle_visibility('loadingCommandDiv');">
        </div>

        <?php } 
        if (in_array($extension, $DrawingArray)) {
        ?>
        
        <div id='drawingOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='drawingOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"><hr /></p>
          <p>Convert This Technical Drawing Or Vector File</p>
          <p>Specify Filename: <input type="text" id='userconvertfilename' name='userconvertfilename' value='<?php echo str_replace('.', '_', $File); ?>'>
          <select id='extension' name='extension'>
            <option value="">Select Format</option>
            <option value="svg">Svg</option>
            <option value="dxf">Dxf</option>
            <option value="vdx">Vdx</option>
            <option value="fig">Fig</option>
          </select></p>
          <input type="submit" id="convertSubmit" name="convertSubmit" value='Convert Drawing' onclick="toggle_visibility('loadingCommandDiv');">
        </div>

        <?php } 
        if (in_array($extension, $ImageArray1)) {
        ?>
        
        <div id='photoOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='photoOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"><hr /></p>
          <p>Convert This Image</p>
          <p>Specify Filename: <input type="text" id='userphotofilename' name='userphotofilename' value='<?php echo str_replace('.', '_', $File); ?>'>
          <select id='photoextension' name='photoextension'>
            <option value="">Select Format</option>
            <option value="jpg">Jpg</option>
            <option value="bmp">Bmp</option>
            <option value="png">Png</option>
          </select></p>
          <p>Width and height: </p>
          <p><input type="number" size="4" value="0" id='width' name='width' min="0" max="10000"> X <input type="number" size="4" value="0" id="height" name="height" min="0"  max="10000"></p> 
          <p>Rotate: <input type="number" size="3" id='rotate' name='rotate' value="0" min="0" max="359"></p>
          <input type="submit" id='convertPhotoSubmit' name='convertPhotoSubmit' value='Convert Image' onclick="toggle_visibility('loadingCommandDiv');">


        <?php } ?>

      </div>
      <hr />

      <?php } ?>

    </div>

    <?php
    include ('footer.php');
    ?>