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
    <div id="header-text" align="center">
      <h2>HRConvert2</h2>
      <h3>Online File Converter, Extractor, Compressor</h3>
      <hr />
    </div>
    <div id="main" align="center">
      <div id="overview" style="max-width:1000px; text-align:left;">
      	
      	<p id="info" style="display:block;">HRConvert2 is an online file conversion tool that anyone can use to securely convert their files from a 
          variety of devices. Without tracking you across the net, selling your info to advertisers, using 3rd party anaylitics, or infringing on your intellectual property.</p>
        
        <p id="more-info-button" onclick="toggle_visibility('more-info'); toggle_visibility('more-info-button'); toggle_visibility('supported-formats-show-button'); 
          toggle_visibility('less-info-button');" style="text-align:center; display:block;"><i>More Info ...</i></p>
        <p id="less-info-button" onclick="toggle_visibility('more-info'); toggle_visibility('more-info-button'); toggle_visibility('supported-formats-show-button'); 
          toggle_visibility('less-info-button');" style="text-align:center; display:none;"><i>Less Info ...</i></p>
        
        <p id="more-info" style="display:none;">The best part is that all user-supplied data is erased automatically, so you don't 
          need to worry about forfeiting your personal information or intellectual property while using our services.</p>
        <p id="more-info" style="display:none;">Currently HRConvert2 supports 59x different file formats, including documents, spreadsheets, images, media, 
          3d models, CAD drawings, vector files, archives, disk images, and more.</p> 
        
        <p id="supported-formats-show-button" onclick="toggle_visibility('supported-formats'); toggle_visibility('supported-formats-show-button'); 
          toggle_visibility('supported-formats-hide-button');" style="text-align:center; display:none;"><i>View Supported Formats ...</i></p>
        <p id="supported-formats-hide-button" onclick="toggle_visibility('supported-formats'); toggle_visibility('supported-formats-show-button'); 
          toggle_visibility('supported-formats-hide-button');" style="text-align:center; display:none;"><i>Hide Supported Formats ...</i></p>
        

          <p id="supported-formats" class="supported-formats" style="display:none;"></p>

          <p id="call-to-action" style="text-align:center;">To begin select files by clicking or dropping files into the box below.</p>
      </div>
      <div id="dropzone" style="max-height:2000px; max-width:1000px;">
        <form action="convertCore.php" class="dropzone" id="upload" name="upload"></form>
      </div>
    </div>




  </body>
</html>