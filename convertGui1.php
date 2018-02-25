<!DOCTYPE HTML>
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
      <h2>HRConvert2</h2>
      <hr />
      <h3>Online File Converter, Extractor, Compressor</h3>
      <hr />
    </div>
    <div id="main" align="center">
      <div id="overview" style="max-width:1000px; text-align:left;">
      	<p id="info" style="display:block;">HRConvert2 is an online file conversion tool that anyone can use to securely convert their files from a 
          variety of devices. Without tracking you across the net, selling your info to advertisers, using 3rd party anaylitics, or infringing on your intellectual property.</p>
        <button id="more-info-button" class='info-button' onclick="toggle_visibility('more-info'); toggle_visibility('more-info-button'); toggle_visibility('supported-formats-show-button'); 
          toggle_visibility('less-info-button');" style="text-align:center; display:block; margin-left:auto; margin-right:auto;"><i>More Info ...</i></button>
        <button id="less-info-button" class='info-button' onclick="toggle_visibility('more-info'); toggle_visibility('more-info-button'); toggle_visibility('supported-formats-show-button'); 
          toggle_visibility('less-info-button');" style="text-align:center; display:none; margin-left:auto; margin-right:auto;"><i>Less Info ...</i></button>
        <div id="more-info" style="display:none;">
          <hr />
          <p>The best part is that all user-supplied data is erased automatically, so you don't 
            need to worry about forfeiting your personal information or property while using our services.</p>
          <p>Currently HRConvert2 supports 59x different file formats, including documents, spreadsheets, images, media, 
            3d models, CAD drawings, vector files, archives, disk images, and more.</p> 
          <button id="supported-formats-show-button" class='info-button' onclick="toggle_visibility('supported-formats'); toggle_visibility('supported-formats-show-button'); 
            toggle_visibility('supported-formats-hide-button');" style="text-align:center; display:none; margin-left:auto; margin-right:auto;"><i>View Supported Formats ...</i></button>
          <button id="supported-formats-hide-button" class='info-button' onclick="toggle_visibility('supported-formats'); toggle_visibility('supported-formats-show-button'); 
            toggle_visibility('supported-formats-hide-button');" style="text-align:center; display:none; margin-left:auto; margin-right:auto;"><i>Hide Supported Formats ...</i></button>
          <br>
          <div id="supported-formats" class="supported-formats" style="margin-left:33%; display:none;">
            <h3>Supported Formats</h3>
            <hr />
            <strong>Audio Formats</strong>
            <p><i>Supports specific bitrate through the API.</i></p>
            <ol>
              <li>Mp2</li>
              <li>Mp3</li>
              <li>Avi</li>
              <li>Flac</li>
              <li>Ogg</li>
              <li>Wav</li>
              <li>Wma</li>
            </ol>
            <strong>Video Formats</strong>
            <ol>
              <li>3gp</li>
              <li>Mkv</li>
              <li>Avi</li>
              <li>Mp4</li>
              <li>Flv</li>
              <li>Mpeg</li>
              <li>Wmv</li>
            </ol>
            <strong>Document Formats</strong>
            <ol>
              <li>Doc</li>
              <li>Docx</li>
              <li>Txt</li>
              <li>Rtf</li>
              <li>Odf</li>
              <li>Pdf</li>
            </ol>
            <strong>Spreadsheet Formats</strong>
            <ol>
              <li>Xls</li>
              <li>Xlsx</li>
              <li>Ods</li>
            </ol>
            <strong>Presentation Formats</strong>
            <ol>
              <li>Pages</li>
              <li>Pptx</li>
              <li>Ppt</li>
              <li>Xps</li>
              <li>Pot</li>
              <li>Potx</li>
              <li>Ppa</li>
              <li>Ppt</li>
              <li>Pptx</li>
              <li>Odp</li>
            </ol>
            <strong>Archive Formats</strong>
              <p><i>Can create, convert, and dearchive any of the following...</i></p>
            <ol>
              <li>Zip</li>
              <li>Rar</li>
              <li>Tar</li>
              <li>7z</li>
            </ol>
            <strong>Disk Image Formats</strong>
            <p><i>Can extract any of the following or convert to supported archive formats...</i></p>
            <ol>
              <li>Iso</li>
              <li>Vhd</li>
              <li>Vdi</li>
            </ol>
            <strong>Image Formats</strong>
            <p><i>Supports resize & rotate through the GUI and API.</i></p>
            <p><i>Supports disable maintain aspect ratio through API.</i></p>
            <ol>
              <li>Jpg</li>
              <li>Jpeg</li>
              <li>Png</li>
              <li>Bmp</li>
              <li>Pdf</li>
              <li>Gif</li>
            </ol>
            <strong>3D Model Formats</strong>
            <ol>
              <li>3ds</li>
              <li>Obj</li>
              <li>Collada</li>
              <li>Off</li>
              <li>Ply</li>
              <li>Stl</li>
              <li>Ptx</li>
              <li>Dxf</li>
              <li>U3d</li>
              <li>Vrml</li>
            </ol>
            <strong>Drawing Files</strong>
            <p><i>Can output drawing files to image formats.</i></p>
            <p><i>Can convert between any of the following...</i></p>
            <ol>
              <li>svg</li>
              <li>dxf</li>
              <li>fig</li>
              <li>vdx</li>
            </ol>
            <strong>OCR Support</strong>
            <p><i>OCR Operations support the following input formats...</i></p>
            <ol>
              <li>Jpg</li>
              <li>Jpeg</li>
              <li>Png</li>
              <li>Bmp</li>
              <li>Pdf</li>
              <li>Gif</li>
              </ol>
            <p><i>OCR Operations support the following output formats...</i></p>
            <ol>
              <li>Doc</li>
              <li>Docx</li>
              <li>Txt</li>
              <li>Rtf</li>
              <li>Odf</li>
              <li>Pdf</li>
            </ol>
          </div>
        </div>
        <br>
        <hr />
        <p id="call-to-action1" style="text-align:center;">To begin select files by clicking or dropping files into the box below.</p>
      </div>
      <div id="dropzone" style="max-height:2000px; max-width:1000px;">
        <form action="convertCore.php" class="dropzone" id="upload" name="upload" method="post" enctype="multipart/form-data">
          <input type="text" name="dirToMake" id="dirToMake" value="<?php echo $Udir; ?>" style="display:none;">
        </form>
      </div>
    </div>
  </body>
</html>