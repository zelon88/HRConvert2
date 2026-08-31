<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRConvert2, Copyright on 8/10/2026 by Justin Grimes, www.github.com/zelon88
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
// / v3.8.2.
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
$oppositeAlignment = (strtolower($GUIAlignment) === 'left') ? 'right' : 'left';
$gui1AudArr = $gui1VidArr = $gui1StreamArr = $gui1DocArr = $gui1SpreadArr = $gui1PresArr = $gui1ArchArr = $gui1ImaArr = $gui1ModArr = $gui1SubArr = $gui1DraArr = $gui1OcrArr = $gui1XpsArr = $gui1ScadArr = $gui1SvgArr = $gui1EbkArr = array();
$selectorBase = 'convertCore.php?';
$selectorSide = ($GUIAlignment === 'left') ? 'right' : 'left';
$selectorSwatches = array(
  'red' => '#c0392b',  'green' => '#27ae60',  'blue' => '#3d71b3',  'grey' => '#7f8c8d',
  'orange' => '#e67e22', 'purple' => '#8e44ad', 'dark' => '#2c3e50');
// / Carry the page state so a selection returns to the page the user was already on.
// / $ShowFiles & $NoGui ARRIVE FROM THE CORE. THEY ARE NOT READ HERE.
// / verifyGlobals reads every superglobal this application accepts & buildGUI hands the
// / result to an interface as an ordinary variable. An interface that read $_GET for
// / itself would be defining API surface, which is not an interface's to define. It also
// / gets the answer wrong, because noGui can arrive by POST & config.php can force it off
// / through $ShowGUI, & neither of those puts anything in $_GET for an interface to find.
if ($ShowFiles) $selectorBase .= 'showFiles=1&';
if ($NoGui) $selectorBase .= 'noGui=TRUE&';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / THE REFRESH BUTTON CARRIES THE SESSION. IT DOES NOT RELOAD THE BROWSER.
// /
// / This page is reached by POST whenever the user arrived from the file list, so
// / location.reload() would ask the browser to replay that POST, & a browser will either
// / refuse it, prompt the user to confirm a resubmission, or serve a cached copy carrying
// / whatever tokens it was rendered with. Posting the tokens to the core instead renders
// / the page again from scratch, with the session intact & nothing to confirm.
// /
// / No showFiles, because refreshing the upload page must land on the upload page. The
// / continue button further down this file carries showFiles & is what returns a user to
// / files they have already uploaded.
// /
// / The interface, language & colour ride along in the query string. A refresh that
// / dropped them would reset the user to the configured defaults, which reads as the
// / application forgetting the settings they just chose.
$sessionParams = '';
if ($NoGui) $sessionParams .= 'noGui=TRUE&';
$sessionParams .= 'gui='.$GuiToUse.'&language='.$LanguageToUse.'&color='.$ColorToUse;
$refreshURL = 'convertCore.php?'.$sessionParams;
// / -----------------------------------------------------------------------------------
?>
  <body>
    <div style= "background-color: #fff; margin: 20px; width: 500px; color: #777777; margin-left:auto; margin-right:auto; padding: 20px; border-radius: 12px; -webkit-box-shadow: 1px 1px 5px 1px rgba(0,0,0,.2);box-shadow: 1px 1px 5px 5px rgba(0,0,0,.3);">
      <?php // / A CENTERED CHROME ROW, MATCHING convertGui2.php.
            // / The settings toggle previously opened a PHP tag inside its own style
            // / attribute that ASSIGNED $oppositeAlignment & echoed nothing, leaving a
            // / text-align with no value. That is one malformed declaration, so a browser
            // / discarded the whole of it & took the display:block after it away too. The
            // / button was therefore never a block & the automatic margins beside it never
            // / centred anything. Centring the row needs no such trick.
            // / $oppositeAlignment is already computed at the top of this file, so the
            // / second assignment was doing nothing & is gone.
            // / EVERY BUTTON DECLARES ITS TYPE, because a button inside a form defaults to
            // / submit, & an undeclared settings toggle would submit the form & reload the
            // / page instead of opening the panel underneath it.
            // / The submit button carries no name, so nothing it is called reaches the core
            // / as POST input.
            // / NEVER WRITE A PHP CLOSING TAG INSIDE A LINE COMMENT. A line comment ends at
            // / a closing tag as surely as it ends at a newline, so quoting one here drops
            // / straight out of PHP & prints every remaining line of the comment onto the
            // / page. Describe the markup instead of reproducing it. ?>
      <div id='guiChrome' name='guiChrome' style='text-align:center;'>
        <form id='sessionForm' name='sessionForm' method='post' style='display:inline;' action='<?php echo htmlspecialchars($refreshURL, ENT_QUOTES, 'UTF-8'); ?>'>
          <input type='hidden' name='Token1' value='<?php echo $Token1; ?>'>
          <input type='hidden' name='Token2' value='<?php echo $Token2; ?>'>
          <?php // / Refresh sits to the LEFT of the settings toggle.
                // / It carries alternate text because it is a glyph with no text of its
                // / own, so this is the only description a screen reader has & the only
                // / thing a browser shows when it cannot render the character.
                // / title & aria-label both, because a title alone is not announced
                // / reliably & an aria-label alone shows nothing on hover. ?>
          <button type='submit' id='refreshButton' style='width:50px;' class='info-button' title='<?php echo htmlspecialchars(isset($Gui1Text37) ? $Gui1Text37 : 'Refresh', ENT_QUOTES, 'UTF-8'); ?>' aria-label='<?php echo htmlspecialchars(isset($Gui1Text37) ? $Gui1Text37 : 'Refresh', ENT_QUOTES, 'UTF-8'); ?>'>&#x21BB;</button>
          <button type='button' id='userConfigButton' style='width:50px;' class='info-button' onclick='toggle_visibility("uiSelector");'>&#9965;</button>
          <?php // / START OVER IS ONLY OFFERED WHEN THERE IS SOMETHING TO CLEAR.
                // / A first time visitor has an empty session, so a control that deletes
                // / everything & issues a new one would do nothing except invite them to
                // / press it. $FileCount is zero for them & this is not rendered at all.
                // / It sits apart from the refresh button beside it on purpose. Refresh
                // / KEEPS the session. This one destroys it. Two controls that differ that
                // / much must not be told apart by their glyph alone, so this one opens a
                // / panel that says what it will do & asks for a second press.
                if ($FileCount > 0) { ?>
          <button type='button' id='startOverButton' style='width:50px;' class='info-button' title='<?php echo htmlspecialchars(isset($Gui1Text36) ? $Gui1Text36 : 'Start Over', ENT_QUOTES, 'UTF-8'); ?>' aria-label='<?php echo htmlspecialchars(isset($Gui1Text36) ? $Gui1Text36 : 'Start Over', ENT_QUOTES, 'UTF-8'); ?>' onclick='toggle_visibility("startOverOptionsDiv");'>&#x21BA;</button>
          <?php } ?>
        </form>
      </div>
      <?php if ($FileCount > 0) { ?>
      <div id='startOverOptionsDiv' name='startOverOptionsDiv' style='display:none; max-width:450px; margin-left:auto; margin-right:auto; text-align:center;'>
        <p><strong><?php echo isset($Gui1Text35) ? $Gui1Text35 : 'Delete every uploaded file &amp; start a new session?'; ?></strong></p>
        <input type='submit' id='startOverConfirm' name='startOverConfirm' class='info-button' value='<?php echo htmlspecialchars(isset($Gui1Text36) ? $Gui1Text36 : 'Start Over', ENT_QUOTES, 'UTF-8'); ?>'>
      </div>
      <script type='text/javascript'>
        // / THE PAGE SUPPLIES VALUES. HRC2-Functions.js SUPPLIES BEHAVIOUR.
        // / startOver() lives in the script library. Everything handed to it here is
        // / something only PHP can know, & nothing about how it works is written here.
        if (typeof HRC2 === 'undefined' || typeof HRC2.configure !== 'function') {
          if (window.console && window.console.error) window.console.error('HRConvert2. HRC2-Functions.js did not load, or is an older copy without configure(). Start Over cannot run. Reload with a cleared cache.');
        } else {
          HRC2.configure(<?php echo json_encode(array(
            'tokens' => array('Token1' => (string)$Token1, 'Token2' => (string)$Token2),
            'sessionFiles' => array_values($Files),
            // / The same URL the refresh button posts to. Refresh POSTS the tokens to it &
            // / keeps the session. Start Over NAVIGATES to it with no tokens, which is what
            // / makes the core issue a new one, & the query string carries the interface,
            // / language & colour across so only the session changes.
            'newSessionURL' => $refreshURL,
            'operationFailedText' => (isset($Gui2Text74) ? $Gui2Text74 : 'Operation Failed!'))); ?>);
          HRC2.bindStartOver('startOverConfirm');
        }
      </script>
      <?php } ?>
      <?php // / $GUIAlignment, not $GuiDirection. There is no $GuiDirection anywhere in the
            // / application, so this emitted an undefined variable warning into the page on
            // / every render & then wrote an empty text-align. The variable that does exist
            // / with that spelling is $GUIDirection, & it carries ltr or rtl, which are
            // / values for a dir attribute & not for text-align. $GUIAlignment carries left
            // / or right, which is what this declaration actually wants. ?>
      <div id='uiSelector' name='uiSelector' style='display:none; margin-left:auto; margin-right:auto; text-align:<?php echo $GUIAlignment; ?>'>
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

    <?php
    if (!isset($_GET['noGui'])) { ?>
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
              <?php if (in_array('XPS', $SupportedConversionTypes)) {
                foreach ($XPSInputArray as $gui1XpsArr) { ?>
              <li><?php echo $gui1XpsArr; ?></li>
              <?php } } ?>
              <?php if (in_array('Ebook', $SupportedConversionTypes)) {
                foreach ($EbookInputArray as $gui1EbookArr) { ?>
              <li><?php echo $gui1EbookArr; ?></li>
              <?php } } ?>
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
        <hr style="border: 1px solid #eeeeee;"/>
      </div>
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
        <?php // / THIS BUTTON IS THE OTHER HALF OF THE SESSION. IT RETURNS THE USER TO
              // / THEIR FILES, so it is built from the same parameters as the refresh
              // / button above & differs only by carrying showFiles.
              // / It previously assembled its own query string out of $_GET, & the second
              // / of those tests read
              // /   if (isset($_GET['language'])) echo '&gui='.$_GET['gui'];
              // / which asks about language & answers about gui. A user who had chosen a
              // / language but not an interface got an Undefined array key warning printed
              // / into this page & an empty &gui= on the button, which the core cannot
              // / match to an installed interface, so it fell back to the default & the
              // / user watched their interface change for no reason they could see.
              // / Reading $_GET here was the root of it. The core resolves all three of
              // / these & hands them over as $GuiToUse, $LanguageToUse & $ColorToUse. ?>
        <form action='convertCore.php?showFiles=1&<?php echo htmlspecialchars($sessionParams, ENT_QUOTES, 'UTF-8'); ?>' method='post'>
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
  </div>

    <?php
    // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
    // / $gui1OcrArr & $gui1ScadArr are declared at the top of this file & were missing from
    // / the chain below, so they survived the page. $gui1ScadArr was named in the unset but
    // / never nulled, which drops the symbol without shredding what it held.
    $oppositeAlignment = $gui1AudArr = $gui1VidArr = $gui1StreamArr = $gui1DocArr = $gui1SpreadArr = $gui1PresArr = $gui1ArchArr = $gui1ImaArr = $gui1ModArr = $gui1SubArr = $gui1DraArr = $gui1OcrArr = $gui1XpsArr = $gui1ScadArr = $gui1SvgArr = $gui1EbkArr = $sessionParams = $refreshURL = NULL;
    unset($oppositeAlignment, $gui1AudArr, $gui1VidArr, $gui1StreamArr, $gui1DocArr, $gui1SpreadArr, $gui1PresArr, $gui1ArchArr, $gui1ImaArr, $gui1ModArr, $gui1SubArr, $gui1DraArr, $gui1OcrArr, $gui1XpsArr, $gui1ScadArr, $gui1SvgArr, $gui1EbkArr, $sessionParams, $refreshURL);