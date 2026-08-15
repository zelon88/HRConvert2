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
// / This file contains language specific GUI elements for accepting file uploads.
// /
// / HARDWARE REQUIREMENTS ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / DEPENDENCY REQUIREMENTS ...
// / This application requires Debian Linux, Apache 2.4, PHP 8+, FFMPEG, Dia, LibreOffice, 
// / Mkisofs, 7zip, Unoconv, libgxps-utils, Tesseract, Unzip, OpenSCAD, Rar, Inkscape,
// / Unrar, ClamAV, MeshLab, PopplerUtils, PDFTOTEXT, ImageMagick, bwrap Dia & xvfb-run.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set a flag to tell that the UI has been displayed.
$UIDisplayed = TRUE;
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die('ERROR!!! HRConvert2-2, This file cannot process your request! Please submit your file to convertCore.php instead!');
// / Assign temporary variables.
$gui2AudArr = $gui2VidArr = $gui2StreamArr = $gui2DocArr = $gui2SpreadArr = $gui2PresArr = $gui2ArchArr = $gui2ImaArr = $gui2ModArr = $gui2SubArr = $gui2DraArr = $gui2OcrArr = $gui2XpsArr = $gui2ScadArr = $gui2SvgArr = array();
$selectorBase = 'convertCore.php?';
$selectorSide = ($GUIAlignment === 'left') ? 'right' : 'left';
$selectorSwatches = array(
  'red' => '#c0392b',  'green' => '#27ae60',  'blue' => '#3d71b3',  'grey' => '#7f8c8d',
  'orange' => '#e67e22', 'purple' => '#8e44ad', 'dark' => '#2c3e50');
// / Carry the page state so a selection returns to the page the user was already on.
if (isset($_GET['showFiles'])) $selectorBase .= 'showFiles=1&';
if (isset($_GET['noGui'])) $selectorBase .= 'noGui=TRUE&';
// / -----------------------------------------------------------------------------------
?>
  <body>
    <button id='userConfigButton' name='userConfigButton' class='info-button' onclick='toggle_visibility("uiSelector");' style='width:25px; text-align:<?php echo $GuiAlignment; ?> display:block; margin-left:auto; margin-right:auto;'>&#9965;</button>
    <div id='uiSelector' name='uiSelector' style='display:none; margin-left:auto; margin-right:auto; text-align:<?php echo $GuiDirection; ?>'>
      <form id='uiSelectorForm' name='uiSelectorForm' method='post' action='<?php echo htmlspecialchars($selectorBase, ENT_QUOTES, 'UTF-8'); ?>'>
        <input type='hidden' name='Token1' value='<?php echo $Token1; ?>'>
        <input type='hidden' name='Token2' value='<?php echo $Token2; ?>'>
        <?php if ($AllowUserSelectableLanguage) { ?>
        <p style='margin:4px 0;'><strong>Language</strong></p>
        <p style='margin:4px 0;'>
          <?php foreach ($SupportedLanguages as $selectorLang => $selectorLabel) {
            $selectorCurrent = ($selectorLang === $LanguageToUse);
            $selectorURL = htmlspecialchars($selectorBase.'language='.$selectorLang.'&color='.$ColorToUse.'&gui='.$GuiToUse, ENT_QUOTES, 'UTF-8'); ?>
          <button type='submit' lang='<?php echo $selectorLang; ?>'
            style='margin:2px; padding:2px; <?php if ($selectorCurrent) echo 'outline:2px solid #000;'; ?>'
            formaction='<?php echo $selectorURL; ?>'
            title='<?php echo $selectorLabel; ?>'
            <?php if ($selectorCurrent) echo "aria-current='true'"; ?>><img src='<?php echo $GuiDir.'Languages/'.$selectorLang.'/flag.png'; ?>' alt='<?php echo $selectorLabel; ?>' style='height:16px; display:block;'/></button>
          <?php } ?>
        </p>
        <?php } if ($AllowUserSelectableColor) { ?>
        <p style='margin:4px 0;'><strong>Color</strong></p>
        <p style='margin:4px 0;'>
          <?php foreach ($SupportedColors as $selectorColor) {
            $selectorSwatch = isset($selectorSwatches[$selectorColor]) ? $selectorSwatches[$selectorColor] : '#cccccc';
            $selectorCurrent = (strtolower($selectorColor) === strtolower($ColorToUse));
            $selectorURL = htmlspecialchars($selectorBase.'color='.$selectorColor.'&language='.$LanguageToUse.'&gui='.$GuiToUse, ENT_QUOTES, 'UTF-8'); ?>
          <button type='submit'
            style='margin:2px; padding:2px; <?php if ($selectorCurrent) echo 'outline:2px solid #000;'; ?>'
            formaction='<?php echo $selectorURL; ?>'
            title='<?php echo ucfirst($selectorColor); ?>' aria-label='<?php echo ucfirst($selectorColor); ?>'
            <?php if ($selectorCurrent) echo "aria-current='true'"; ?>><span class='swatch' style='background-color:<?php echo $selectorSwatch; ?>; width:24px; height:16px; display:block;'></span></button>
          <?php } ?>
        </p>
        <?php } if ($AllowUserSelectableGui) { ?>
        <p style='margin:4px 0;'><strong>Interface</strong></p>
        <p style='margin:4px 0;'>
          <?php foreach ($SupportedGuis as $selectorGui) {
            $selectorCurrent = ($selectorGui === $GuiToUse);
            $selectorURL = htmlspecialchars($selectorBase.'gui='.$selectorGui.'&language='.$LanguageToUse.'&color='.$ColorToUse, ENT_QUOTES, 'UTF-8'); ?>
          <button type='submit' class='txtbtn'
            style='margin:2px; <?php if ($selectorCurrent) echo 'font-weight:700; text-decoration:underline;'; ?>'
            formaction='<?php echo $selectorURL; ?>'
            title='<?php echo $selectorGui; ?>' aria-label='<?php echo $selectorGui; ?>'
            <?php if ($selectorCurrent) echo "aria-current='true'"; ?>><?php echo $selectorGui; ?></button>
          <?php } ?>
        </p>
        <?php } ?>
      </form>
    </div>

    <?php if (!isset($_GET['noGui'])) { ?>
    <div id='header-text' style='max-width:2000px; margin-left:auto; margin-right:auto; text-align:center;'>
      <h1><?php echo $ApplicationName; ?></h1>
      <h3><?php echo $Gui1Text1; ?></h3>
      <hr />
    </div>
    <div id='main' align='center'>
      <div id='overview' style='max-width:2000px; text-align:<?php echo $GUIAlignment; ?>; margin:25px;'><?php echo $Gui1Text2; ?>
        <p id='info' style='display:block;'></p>
        <br />
        <button id='more-info-button' class='info-button' onclick='toggle_visibility("more-info"); toggle_visibility("more-info-button"); toggle_visibility("supported-formats-show-button"); toggle_visibility("less-info-button");' style='text-align:center; display:block; margin-left:auto; margin-right:auto;'><?php echo $Gui1Text3; ?></button>
        <button id='less-info-button' class='info-button' onclick='toggle_visibility("more-info"); toggle_visibility("more-info-button"); toggle_visibility("supported-formats-show-button"); toggle_visibility("less-info-button");' style='text-align:center; display:none; margin-left:auto; margin-right:auto;'><?php echo $Gui1Text4; ?></button>
        <div id='more-info' style='display:none;'>
          <hr />
          <p><?php echo $Gui1Text5; ?></p>
          <p><?php echo $Gui1Text6; ?></p>
          <button id='supported-formats-show-button' class='info-button' onclick='toggle_visibility("supported-formats"); toggle_visibility("supported-formats-show-button"); toggle_visibility("supported-formats-hide-button");' style='text-align:center; display:none; margin-left:auto; margin-right:auto;'><?php echo $Gui1Text7; ?></button>
          <button id='supported-formats-hide-button' class='info-button' onclick='toggle_visibility("supported-formats"); toggle_visibility("supported-formats-show-button"); toggle_visibility("supported-formats-hide-button");' style='text-align:center; display:none; margin-left:auto; margin-right:auto;'><?php echo $Gui1Text8; ?></button>
          <br />
          <div id='supported-formats' class='supported-formats' style='margin-left:33%; display:none;'>
            <h3><?php echo $Gui1Text9; ?></h3>
            <hr />
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
            <?php } if (in_array('Scad', $SupportedConversionTypes)) { ?>
            <strong><?php echo $Gui1Text32; ?></strong>
            <p><i><?php echo $Gui1Text33; ?></i></p>
            <p><i><?php echo $Gui1Text34; ?></i></p>
            <ol>
              <?php foreach ($SCADOutputArray as $gui1ScadArr) { ?>
              <li><?php echo $gui1ScadArr; ?></li>
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
        <hr />
      </div>
      <?php } ?>
      <div align='center'>
        <div id='call-to-action1' title='' style='max-width:2000px; text-align:center;'>
          <p><?php echo $Gui1Text28; ?></p>
        </div>
      </div>
      <div align='center'>
        <div id='dropzone' style='max-height:2000px; max-width:2000px; margin:25px;'>
          <form action='convertCore.php' class='dropzone' id='filesToUpload' name='filesToUpload' method='post' enctype='multipart/form-data'>
          <input type='hidden' id='token1' name='Token1' value='<?php echo $Token1; ?>'>
          <input type='hidden' id='token2' name='Token2' value='<?php echo $Token2; ?>'>
          </form>
        </div>
      </div>
      <div align='center'>
        <div id='continue' style='max-width:2000px; text-align:center;'>
          <form action='convertCore.php?showFiles=1<?php if (isset($_GET['noGui'])) echo '&noGui=TRUE'; if (isset($_GET['language'])) echo '&gui='.$_GET['gui']; if (isset($_GET['language'])) echo '&language='.$_GET['language']; if (isset($_GET['color'])) echo '&color='.$_GET['color']; ?>' method='post'>
            <input type='hidden' id='token1' name='Token1' value='<?php echo $Token1; ?>'>
            <input type='hidden' id='token2' name='Token2' value='<?php echo $Token2; ?>'>
            <input type='submit' id='continue-button' class='info-button' value='<?php echo $Gui1Text29; ?>'>
          </form>
          <br />
          <?php if (!isset($_GET['noGui'])) { ?>
          <hr />
          <?php } ?>
        </div>
      </div>

    <?php if (!isset($_GET['noGui'])) { ?>
    </div>
    <?php }
    // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
    $gui1AudArr = $gui1VidArr = $gui1StreamArr = $gui1DocArr = $gui1SpreadArr = $gui1XpsArr = $gui1PresArr = $gui1ArchArr = $gui1ImaArr = $gui1ModArr = $gui2SubArr = $gui1DraArr = $gui2XpsArr = NULL;
    unset($gui1AudArr, $gui1VidArr, $gui1StreamArr, $gui1DocArr, $gui1SpreadArr, $gui1XpsArr, $gui1PresArr, $gui1ArchArr, $gui1ImaArr, $gui1ModArr, $gui2SubArr, $gui1DraArr, $gui2XpsArr);