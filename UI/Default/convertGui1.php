<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRConvert2, Copyright on 5/12/2026 by Justin Grimes, www.github.com/zelon88
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
// / v3.4.3.
// / The files in this UI were submitted by Github user hernandito in Issue #85. Thank you!
// / https://github.com/hernandito
// / This file contains language specific GUI elements for accepting file uploads.
// / This file was created by Github user hernandito as part of his forked repo, available 
// / at https://github.com/hernandito/HRConvert2/tree/master. Thank you, hernandito!
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
$gui2AudArr = $gui2VidArr = $gui2StreamArr = $gui2DocArr = $gui2SpreadArr = $gui2PresArr = $gui2ArchArr = $gui2ImaArr = $gui2ModArr = $gui2SubArr = $gui2DraArr = $gui2OcrArr = $gui2XpsArr = array();
// / -----------------------------------------------------------------------------------
?>
  <body>
    <?php
    if (!isset($_GET['noGui'])) { ?>
	
<div style= "background-color: #fff; margin: 20px; width: 500px; color: #777777; margin-left:auto; margin-right:auto; padding: 20px; border-radius: 12px; -webkit-box-shadow: 1px 1px 5px 1px rgba(0,0,0,.2);
box-shadow: 1px 1px 5px 5px rgba(0,0,0,.3);">
	
    <div id='header-text' style='max-width:500px; margin-left:auto; margin-right:auto; text-align:left;'>
      <h1><img src='<?php echo $GuiImageDir; ?>convert-banner.png' style='max-height:72px; margin-right: 10px;'/><?php //echo $ApplicationName; ?></h1>
      <h3><?php echo $Gui1Text1; ?></h3>
      <hr style="border: 1px solid #eeeeee;"/>
    </div>
    <div id='main' align='left'>
      <div id='overview' style='max-width:500px; text-align: left; font-size: 14px;'><?php echo $Gui1Text2; ?>
        <p id='info' style='display:block;'></p>
        <button id='more-info-button' class='info-button' onclick='toggle_visibility("more-info"); toggle_visibility("more-info-button"); toggle_visibility("supported-formats-show-button"); toggle_visibility("less-info-button");' style='text-align:center; display:block; margin-left:auto; margin-right:auto;'><?php echo $Gui1Text3; ?></button>
        <button id='less-info-button' class='info-button' onclick='toggle_visibility("more-info"); toggle_visibility("more-info-button"); toggle_visibility("supported-formats-show-button"); toggle_visibility("less-info-button");' style='text-align:center; display:none; margin-left:auto; margin-right:auto;'><?php echo $Gui1Text4; ?></button>
        <div id='more-info' style='display:none;'>
      <hr style="border: 1px solid #eeeeee;"/>
          <p><?php echo $Gui1Text5; ?></p>
          <p><?php echo $Gui1Text6; ?></p>
          <button id='supported-formats-show-button' class='info-button' onclick='toggle_visibility("supported-formats"); toggle_visibility("supported-formats-show-button"); toggle_visibility("supported-formats-hide-button");' style='text-align:center; display:none; margin-left:auto; margin-right:auto;'><?php echo $Gui1Text7; ?></button>
          <button id='supported-formats-hide-button' class='info-button' onclick='toggle_visibility("supported-formats"); toggle_visibility("supported-formats-show-button"); toggle_visibility("supported-formats-hide-button");' style='text-align:center; display:none; margin-left:auto; margin-right:auto;'><?php echo $Gui1Text8; ?></button>
          <br>
          <div id='supported-formats' class='supported-formats' style='margin-left:33%; display:none;'>
            <h3><?php echo $Gui1Text9; ?></h3>
               <hr style="border: 1px solid #eeeeee;"/>
            <?php if (in_array('Audio', $SupportedConversionTypes)) { ?>
            <strong><?php echo $Gui1Text10; ?></strong>
            <p><i><?php echo $Gui1Text11; ?></i></p>
            <ol>
              <?php foreach ($MediaInputArray as $gui1AudArr) { ?>
              <li><?php echo $gui1AudArr; ?></li>
              <?php } ?>
            </ol>
            <?php } if (in_array('Video', $SupportedConversionTypes)) { ?>
            <strong><?php echo $Gui1Text12; ?></strong>
            <ol>
              <?php foreach ($VideoInputArray as $gui1VidArr) { ?>
              <li><?php echo $gui1VidArr; ?></li>
              <?php } ?>
            </ol>
            <?php } if (in_array('Stream', $SupportedConversionTypes)) { ?>
            <strong><?php echo $Gui1Text13; ?></strong>
            <p><i><?php echo $Gui1Text30; ?></i></p>
            <ol>
              <?php foreach ($StreamArray as $gui1StreamArr) { ?>
              <li><?php echo $gui1StreamArr; ?></li>
              <?php } ?>
            </ol>
            <?php } if (in_array('Document', $SupportedConversionTypes)) { ?>
            <strong><?php echo $Gui1Text14; ?></strong>
            <ol>
              <?php foreach ($DocumentArray as $gui1DocArr) { ?>
              <li><?php echo $gui1DocArr; ?></li>
              <?php } ?>
              <?php foreach ($XPSInputArray as $gui1XpsArr) { ?>
              <li><?php echo $gui1XpsArr; ?></li>
              <?php } ?>
            </ol>
            <?php } if (in_array('Document', $SupportedConversionTypes)) { ?>
            <strong><?php echo $Gui1Text15; ?></strong>
            <ol>
              <?php foreach ($SpreadsheetArray as $gui1SpreadArr) { ?>
              <li><?php echo $gui1SpreadArr; ?></li>
              <?php } ?>
            </ol>
            <?php } if (in_array('Document', $SupportedConversionTypes)) { ?>
            <strong><?php echo $Gui1Text16; ?></strong>
            <ol>
              <?php foreach ($PresentationInputArray as $gui1PresArr) { ?>
              <li><?php echo $gui1PresArr; ?></li>
              <?php } ?>
            </ol>
            <?php } if (in_array('Archive', $SupportedConversionTypes)) { ?>
            <strong><?php echo $Gui1Text17; ?></strong>
            <p><i><?php echo $Gui1Text18; ?></i></p>
            <ol>
              <?php foreach ($DearchiveArray as $gui1ArchArr) { ?>
              <li><?php echo $gui1ArchArr; ?></li>
              <?php } ?>
            </ol>
            <?php } if (in_array('Image', $SupportedConversionTypes)) { ?>
            <strong><?php echo $Gui1Text19; ?></strong>
            <p><i><?php echo $Gui1Text21; ?></i></p>
            <ol>
              <?php foreach ($ImageArray as $gui1ImaArr) { ?>
              <li><?php echo $gui1ImaArr; ?></li>
              <?php } ?>
            </ol>
            <?php } if (in_array('Model', $SupportedConversionTypes)) { ?>
            <strong><?php echo $Gui1Text22; ?></strong>
            <ol>
              <?php foreach ($ModelArray as $gui1ModArr) { ?>
              <li><?php echo $gui1ModArr; ?></li>
              <?php } ?>
            </ol>
            <?php } if (in_array('Subtitle', $SupportedConversionTypes)) { ?>
            <strong><?php echo $Gui1Text31; ?></strong>
            <ol>
              <?php foreach ($SubtitleInputArray as $gui1SubArr) { ?>
              <li><?php echo $gui1SubArr; ?></li>
              <?php } ?>
            </ol>
            <?php } if (in_array('Drawing', $SupportedConversionTypes)) { ?>
            <strong><?php echo $Gui1Text23; ?></strong>
            <p><i><?php echo $Gui1Text24; ?></i></p>
            <ol>
              <?php foreach ($DrawingArray as $gui1DraArr) { ?>
              <li><?php echo $gui1DraArr; ?></li>
              <?php } ?>
            </ol>
            <?php } if (in_array('OCR', $SupportedConversionTypes)) { ?>
            <strong><?php echo $Gui1Text25; ?></strong>
            <p><i><?php echo $Gui1Text20; ?></i></p>
            <p><i><?php echo $Gui1Text26; ?></i></p>
            <ol>
              <li>Jpg</li>
              <li>Jpeg</li>
              <li>Png</li>
              <li>Bmp</li>
              <li>Pdf</li>
              <li>Gif</li>
              <li>Webp</li>
              </ol>
            <p><i><?php echo $Gui1Text27; ?></i></p>
            <ol>
              <li>Doc</li>
              <li>Docx</li>
              <li>Txt</li>
              <li>Rtf</li>
              <li>Odt</li>
              <li>Pdf</li>
            </ol>
            <?php } ?>
          </div>
        </div>
        <hr style="border: 1px solid #eeeeee;"/>
      </div>
      <?php } ?>
      <div align='center'>
        <div id='call-to-action1' title='' style='max-width:1000px; text-align:center;'>
          <p><?php echo $Gui1Text28; ?></p>
        </div>
      </div>
      <div align='center'>
        <div id='dropzone' style='max-height:800px; max-width:1000px; margin:25px;'>
          <form action='convertCore.php' class='dropzone' id='filesToUpload' name='filesToUpload' method='post' enctype='multipart/form-data'>
          <input type='hidden' id='token1' name='Token1' value='<?php echo $Token1; ?>'>
          <input type='hidden' id='token2' name='Token2' value='<?php echo $Token2; ?>'>
          </form>
        </div>
      </div>
      <div align='center'>
        <div id='continue' style='max-width:500px; text-align:center;'>
          <form action='convertCore.php?showFiles=1<?php if (isset($_GET['noGui'])) echo '&noGui=TRUE'; if (isset($_GET['language'])) echo '&gui='.$_GET['gui']; if (isset($_GET['language'])) echo '&language='.$_GET['language']; if (isset($_GET['color'])) echo '&color='.$_GET['color']; ?>' method='post'>
            <input type='hidden' id='token1' name='Token1' value='<?php echo $Token1; ?>'>
            <input type='hidden' id='token2' name='Token2' value='<?php echo $Token2; ?>'>
            <input type='submit' id='continue-button' class='info-button' value='<?php echo $Gui1Text29; ?>'>
          </form>
          <br />
          <?php if (!isset($_GET['noGui'])) { ?>
			<hr style="border: 1px solid #eeeeee;"/>
          <?php } ?>
        </div>
      </div>

    <?php if (!isset($_GET['noGui'])) { ?>
    </div>
<div>	
    <?php }
    // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
    $gui1AudArr = $gui1VidArr = $gui1StreamArr = $gui1DocArr = $gui1SpreadArr = $gui1PresArr = $gui1ArchArr = $gui1ImaArr = $gui1ModArr = $gui2SubArr = $gui1DraArr = $gui2XpsArr = NULL;
    unset($gui1AudArr, $gui1VidArr, $gui1StreamArr, $gui1DocArr, $gui1SpreadArr, $gui1PresArr, $gui1ArchArr, $gui1ImaArr, $gui1ModArr, $gui2SubArr, $gui1DraArr, $gui2XpsArr);