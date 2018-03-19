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

<?php
$Files = getFiles($ConvertTempDir);
foreach ($Files as $File) {
  $extension = getExtension($ConvertTempDir.'/'.$File);

if (in_array($extension, $pdfWordArr)) { 
  ?>
  <div align="center" id='scandocshowDiv' name='scandocshowDiv' style="display:none;">
  <input type="text" id="scandocuserfilename" name="scandocuserfilename" value='<?php echo $Udir.'Scanned-Document_'.$Date; ?>'> 
  <select id='outputtopdf' name='outputtopdf'> 
  <option value="0">Preserve Extensions</option>
  <option value="1">Create PDF's</option>
  </select>
  <input type="submit" id="scandocSubmit" name="scandocSubmit" value='Scan Document' onclick="toggle_visibility('loadingCommandDiv');">
  </div>
  <?php } 

if (in_array($extension, $archArr)) {
  ?>
  <div align="center" id='archiveOptionsDiv' name='archiveOptionsDiv' style="display:none;">
  <input type="text" id='userfilename' name='userfilename' value='<?php echo $Udir.'Archive'.'_'.$Date.'_'.$ArchInc; ?>'> 
  <select id='archextension' name='archextension'> 
  <option value="zip">Zip</option>
  <option value="rar">Rar</option>
  <option value="tar">Tar</option>
  <option value="7z">7z</option>
  </select>
  <input type="submit" id="archiveFileSubmit" name="archiveFileSubmit" value='Archive Files' onclick="toggle_visibility('loadingCommandDiv');">
  </div>
  ?> }

}

?>

  <div align="center" id='convertOptionsDiv' name='convertOptionsDiv' style="display:none;">
  <input type="text" id="userconvertfilename" name="userconvertfilename" value="<?php echo $Udir.'Convert'.'_'.$Date; ?>"> 
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
  <p>Filename: <input type="text" id='userphotofilename' name='userphotofilename' value='<?php echo $Udir.'Edited'.'_'.$Date; ?>'>
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
  </select></p>
  <p><a id='userpdfconvertfilename1'><input type="text" id='userpdfconvertfilename' name='userpdfconvertfilename' value='<?php echo $Udir.'Converted'.'_'.$Date; ?>'></a>
  <select id='pdfextension' name='pdfextension'>   
  <option value="">Select Format</option> 
  <option value="pdf">Pdf</option>   
  <option value="doc">Doc</option>
  <option value="docx">Docx</option>
  <option value="rtf">Rtf</option>
  <option value="txt">Txt</option>
  <option value="odf">Odf</option>
  </select></p>
  <p><input type="submit" id='pdfwork' name='pdfwork' value='Perform PDFWork' onclick="toggle_visibility('loadingCommandDiv');"></p>
  </div>