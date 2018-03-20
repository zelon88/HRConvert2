<html>
  <head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="Resources/favicon.ico"/>
    <link rel="stylesheet" href="Resources/dropzone.css"/>
    <script type="text/javascript" src="Resources/HRC2-Functions.js"></script>
    <script type="text/javascript" src="Resources/dropzone.js"></script>
    <title>HRConvert2-Convert anything!</title>
  </head>
  <body>
    <div id="header-text" style="max-width:1000px; margin-left:auto; margin-right:auto; text-align:center;">
      <br>
      <h2>HRConvert2</h2>
      <hr />
      <h3>File Conversion Options</h3>
      <hr />
    </div>

    <div id="compressAll" name="compressAll" style="max-width:1000px; margin-left:auto; margin-right:auto; text-align:center;">
      <button id="scandocMoreOptionsButton" name="scandocMoreOptionsButton" onclick="toggle_visibility('compressAllOptions');">Bulk File Options</button>
      <div id="compressAllOptions" name="compressAllOptions" align="center" style="display:none;">
        <p>Compress & Download All Files:</p>
        <p>Specify Filename: <input type="text" id='userfilename' name='userfilename' value='HRConvert2_Files-<?php echo $Date; ?>'></p> 
        <select id='archextension' name='archextension'> 
          <option value="zip">Zip</option>
          <option value="rar">Rar</option>
          <option value="tar">Tar</option>
          <option value="7z">7z</option>
        </select>
        <input type="submit" id="archiveAllSubmit" name="archiveAllSubmit" value='Compress & Download' onclick="toggle_visibility('loadingCommandDiv');">
        <hr />
      </div>
    </div>

    <div style="max-width:1000px; margin-left:auto; margin-right:auto;">

    <?php
    $Files = getFiles($ConvertTempDir);
    foreach ($Files as $File) {
      $extension = getExtension($ConvertTempDir.'/'.$File);
      if (!in_array($extension, $convertArr)) continue;
      $ConvertGuiCounter1++;
    ?>

    <div id="file<?php echo $ConvertGuiCounter1; ?>" name="<?php echo $ConvertGuiCounter1; ?>">
      <p><strong><?php echo $ConvertGuiCounter1; ?>.</strong> <?php echo $File; ?></p>
      <button id="fileMoreOptionsButton" name="fileMoreOptionsButton" onclick="toggle_visibility('fileOptionsDiv<?php echo $ConvertGuiCounter1; ?>');">File Options</button>
      <div id='fileOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='fileOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="margin-left:35px; display:none;">

        <?php
        if (in_array($extension, $pdfWorkArr)) { 
        ?>
        
        <hr />
        <p>Specify Filename: <a id='userpdfconvertfilename1'><input type="text" id='userpdfconvertfilename' name='userpdfconvertfilename' value='<?php echo str_replace('.', '_', $File); ?>'></a>
        <select id='pdfextension' name='pdfextension'>   
          <option value="">Select Format</option> 
          <option value="pdf">Pdf</option>   
          <option value="doc">Doc</option>
          <option value="docx">Docx</option>
          <option value="rtf">Rtf</option>
          <option value="txt">Txt</option>
          <option value="odf">Odf</option>
        </select></p>
        <p><input type="submit" id='pdfwork' name='pdfwork' value='Convert To Document' onclick="toggle_visibility('loadingCommandDiv');"></p>
      
      <?php } ?>

      </div>

      <?php
      if (in_array($extension, $archArr)) {
      ?>
      
      <hr />
      <div id='archiveOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='archiveOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="display:none;">
        <input type="text" id='userfilename' name='userfilename' value=''> 
        <select id='archextension' name='archextension'> 
          <option value="zip">Zip</option>
          <option value="rar">Rar</option>
          <option value="tar">Tar</option>
          <option value="7z">7z</option>
        </select>
        <input type="submit" id="archiveFileSubmit" name="archiveFileSubmit" value='Archive Files' onclick="toggle_visibility('loadingCommandDiv');">
      </div>

      <?php } ?>
      
      <hr />
    </div>

    <?php } /*

  <div align="center" id='convertOptionsDiv' name='convertOptionsDiv' style="display:none;">
  <input type="text" id="userconvertfilename" name="userconvertfilename" value=""> 
  <select id='extension' name='extension'> 
  <option value="">Select Format</option>
  <option value="mp3">--Audio Formats--</option>
  <option value="mp2">Mp2</option>  
  <option value="mp3">Mp3</option>
  <option value="wav">Wav</option>
  <option value="wma">Wma</option>
  <option value="flac">Flac</option>  
  <option value="ogg">Ogg</option>
  <option value="mp3">--Video Formats--</option>
  <option value="3gp">3gp</option> 
  <option value="mkv">Mkv</option> 
  <option value="avi">Avi</option>
  <option value="mp4">Mp4</option>
  <option value="flv">Flv</option>
  <option value="mpeg">Mpeg</option>
  <option value="wmv">Wmv</option>
  <option value="mp3">--Image Formats--</option>
  <option value="jpg">Jpg</option>  
  <option value="bmp">Bmp</option>
  <option value="png">Png</option>
  <option value="gif">Gif</option>
  <option value="txt">--Document Formats--</option>
  <option value="doc">Doc</option>
  <option value="docx">Docx</option>
  <option value="rtf">Rtf</option>
  <option value="txt">Txt</option>
  <option value="odf">Odf</option>
  <option value="pdf">Pdf</option>
  <option value="ods">--Spreadsheet Formats--</option>
  <option value="xls">Xls</option>
  <option value="xlsx">Xlsx</option>
  <option value="ods">Ods</option>
  <option value="pdf">Pdf</option>
  <option value="zip">--Archive Formats--</option>
  <option value="zip">Zip</option>
  <option value="rar">Rar</option>
  <option value="tar">Tar</option>
  <option value="7z">7z</option>
  <option value="zip">--Presentation Formats--</option>
  <option value="pages">Pages</option>
  <option value="pptx">Pptx</option>
  <option value="ppt">Ppt</option>
  <option value="xps">Xps</option>
  <option value="potx">Potx</option>
  <option value="pot">Pot</option>
  <option value="ppa">Ppa</option>
  <option value="odp">Odp</option>
  <option value="zip">--3D Model Formats--</option>
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
  <option value="zip">--Drawing Formats--</option>
  <option value="svg">Svg</option>
  <option value="dxf">Dxf</option>
  <option value="vdx">Vdx</option>
  <option value="fig">Fig</option>
  </select>
  <input type="submit" id="convertSubmit" name="convertSubmit" value='Convert Files' onclick="toggle_visibility('loadingCommandDiv');">
  </div>
  <div align="center" id='photoOptionsDiv' name='photoOptionsDiv' style="display:none;">
  <p>Filename: <input type="text" id='userphotofilename' name='userphotofilename' value=''>
  <select id='photoextension' name='photoextension'>
  <option value="jpg">Jpg</option>
  <option value="bmp">Bmp</option>
  <option value="png">Png</option>
  </select></p>
  <p>Width and height: </p>
  <p><input type="number" size="4" value="0" id='width' name='width' min="0" max="3000"> X <input type="number" size="4" value="0" id="height" name="height" min="0"  max="3000"></p> 
  <p>Rotate: <input type="number" size="3" id='rotate' name='rotate' value="0" min="0" max="359"></p>
  <input type="submit" id='convertPhotoSubmit' name='convertPhotoSubmit' value='Convert Files' onclick="toggle_visibility('loadingCommandDiv');">
  </div>
  <div align="center"><img src='Resources/logosmall.gif' id='loadingCommandDiv' name='loadingCommandDiv' style="display:none; max-width:64px; max-height:64px;"/></div>
  <div align="center" id='PDFOptionsDiv' name='PDFOptionsDiv' style="display:none;">
  <p><a id='makePDFbutton' name='makePDF' value='makePDF' ></a></p> 
  <p><select id='method1' name='method1'>   
  <option value="0">Select Method</option>  
  <option value="1">Automatic</option>  
  <option value="1">Method 1 (Simple)</option>
  <option value="2">Method 2 (Advanced)</option>
  </select></p> */
  ?>
  </div>
  </div>