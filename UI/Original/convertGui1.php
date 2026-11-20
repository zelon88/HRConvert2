<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRConvert2, Copyright on 8/17/2026 by Justin Grimes, www.github.com/zelon88
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
// / v3.8.3.
// / This file contains language specific GUI elements for accepting file uploads.
// /
// / HARDWARE REQUIREMENTS ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / DEPENDENCY REQUIREMENTS ...
// / The authoritative list of dependencies is Resources/Contract/depends.php & nothing else.
// / Run  php convertCore.php -v  to see what this installation has.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set a flag to tell that the UI has been displayed.
$UIDisplayed = TRUE;
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die('ERROR!!! HRConvert2-2, This file cannot process your request! Please submit your file to convertCore.php instead!');
// / Assign temporary variables.
$gui1AudArr = $gui1VidArr = $gui1StreamArr = $gui1DocArr = $gui1SpreadArr = $gui1PresArr = $gui1ArchArr = $gui1ImaArr = $gui1ModArr = $gui1SubArr = $gui1DraArr = $gui1OcrArr = $gui1XpsArr = $gui1ScadArr = $gui1SvgArr = $gui1EbkArr = $gui1DataArr = $gui1EmailArr = $gui1GraphArr = $gui1DocList = array();
$selectorBase = 'convertCore.php?';
// / The bar at the top of the page is taskbar.php's, & it is told which page it is on.
$taskbarPage = 'upload';
// / Carry the page state so a selection returns to the page the user was already on.
// / $ShowFiles & $NoGui arrive from the core & are not read here.
// / verifyGlobals reads every superglobal this application accepts & buildGUI hands the
// / result to an interface as an ordinary variable. An interface that read $_GET for
// / itself would be defining API surface, which is not an interface's to define. It also
// / gets the answer wrong, because noGui can arrive by POST & config.php can force it off
// / through $ShowGUI, & neither of those puts anything in $_GET for an interface to find.
if ($ShowFiles) $selectorBase .= 'showFiles=1&';
if ($NoGui) $selectorBase .= 'noGui=TRUE&';
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / The refresh button CARRIES the session. It does not reload the browser.
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
// / The sort order the file list uses rides along too, so the list comes back in the order the user left it.
// / redeclare() rather than .= on each of these, as the file list page does, so no earlier string is left behind.
$sessionParams = '';
if ($NoGui) redeclare($sessionParams, $sessionParams.'noGui=TRUE&');
redeclare($sessionParams, $sessionParams.'gui='.$GuiToUse.'&language='.$LanguageToUse.'&color='.$ColorToUse);
$sortParams = '&sort='.rawurlencode((string)$SortToUse);
$refreshURL = 'convertCore.php?'.$sessionParams.$sortParams;
// / -----------------------------------------------------------------------------------
?>
  <body>
    <div id='page' style='max-width:<?php echo $GuiWidth; ?>; margin-left:auto; margin-right:auto;'>
      <?php // / THE BAR AT THE TOP OF THE PAGE IS taskbar.php's, as the file list's is.
            // / It holds Refresh, Workflows once there is a file, the settings & Sign in, & the panels they open,
            // / so the two pages draw them alike. Start Over is in the settings, & only where there is something to clear.
            // / It posts to the addresses set above, & reads no request itself.
            // / Never write a PHP closing tag inside a line comment. A line comment ends at
            // / a closing tag as surely as it ends at a newline, so quoting one here drops
            // / straight out of PHP & prints every remaining line of the comment onto the
            // / page. Describe the markup instead of reproducing it.
            require($GuiTaskbarFile); ?>

    <?php
    if (!$NoGui) { ?>
    <div id='header-text' style='max-width:<?php echo $GuiWidth; ?>; margin-left:auto; margin-right:auto; text-align:center;'>
      <?php
      // / The logo returns to this page carrying the language, colour & interface,
      // / unless --Logo URL-- names somewhere else. This application has no navigation,
      // / so a logo that leaves takes the session & the user's files with it.
      // / verifyInputs computes the address. Nothing is decided here.
      ?>
      <h1><a href='<?php echo htmlspecialchars($ResolvedLogoURL, ENT_QUOTES, 'UTF-8'); ?>'><img src='<?php echo $GuiImageDir; ?>convert-banner.png' style='max-height:72px; margin-right: 10px;'/></a><?php //echo $ApplicationName; ?></h1>
      <h3><?php echo $Gui1Text1; ?></h3>
      <hr />
    </div>
    <div id='main' align='center'>
      <div id='overview' style='max-width:<?php echo $GuiWidth; ?>; text-align:<?php echo $GUIAlignment; ?>; margin:25px;'><?php echo $Gui1Text2; ?>
        <p id='info' style='display:block;'></p>
        <button id='more-info-button' class='info-button' onclick='toggle_visibility("more-info"); toggle_visibility("more-info-button"); toggle_visibility("supported-formats-show-button"); toggle_visibility("less-info-button");' style='text-align:center; display:block; margin-left:auto; margin-right:auto;'><?php echo $Gui1Text3; ?></button>
        <button id='less-info-button' class='info-button' onclick='toggle_visibility("more-info"); toggle_visibility("more-info-button"); toggle_visibility("supported-formats-show-button"); toggle_visibility("less-info-button");' style='text-align:center; display:none; margin-left:auto; margin-right:auto;'><?php echo $Gui1Text4; ?></button>
        <div id='more-info' style='display:none;'>
          <hr />
          <p><?php echo $Gui1Text5; ?></p>
          <p><?php echo $Gui1Text6; ?></p>
          <button id='supported-formats-show-button' class='info-button' onclick='toggle_visibility("supported-formats"); toggle_visibility("supported-formats-show-button"); toggle_visibility("supported-formats-hide-button");' style='text-align:center; display:none; margin-left:auto; margin-right:auto;'><?php echo $Gui1Text7; ?></button>
          <button id='supported-formats-hide-button' class='info-button' onclick='toggle_visibility("supported-formats"); toggle_visibility("supported-formats-show-button"); toggle_visibility("supported-formats-hide-button");' style='text-align:center; display:none; margin-left:auto; margin-right:auto;'><?php echo $Gui1Text8; ?></button>
          <br>
          <div id='supported-formats' class='supported-formats' style='margin-left:33%; display:none;'>
            <h3><?php echo $Gui1Text9; ?></h3>
               <hr />
            <?php if (in_array('Audio', $OfferedConversionTypes)) { ?>
            <strong><?php echo $Gui1Text10; ?></strong>
            <p><i><?php echo $Gui1Text11; ?></i></p>
            <ol>
              <?php foreach ($MediaInputArray as $gui1AudArr) { ?>
              <li><?php echo $gui1AudArr; ?></li>
              <?php } ?>
            </ol>
            <?php } if (in_array('Video', $OfferedConversionTypes)) { ?>
            <strong><?php echo $Gui1Text12; ?></strong>
            <ol>
              <?php foreach ($VideoInputArray as $gui1VidArr) { ?>
              <li><?php echo $gui1VidArr; ?></li>
              <?php } ?>
            </ol>
            <?php } if (in_array('Stream', $OfferedConversionTypes)) { ?>
            <strong><?php echo $Gui1Text13; ?></strong>
            <p><i><?php echo $Gui1Text30; ?></i></p>
            <ol>
              <?php foreach ($StreamArray as $gui1StreamArr) { ?>
              <li><?php echo $gui1StreamArr; ?></li>
              <?php } ?>
            </ol>
            <?php }
            // / Every family that reads documents is listed under Documents, & a format two of them read is listed once, from v4.2.5.
            // / The LaTeX & Markup families joined the e-book family here in v4.2.5.
            $gui1DocList = array();
            if (in_array('Document', $OfferedConversionTypes)) $gui1DocList = array_merge($gui1DocList, $DocumentInputArray);
            if (in_array('XPS', $OfferedConversionTypes)) $gui1DocList = array_merge($gui1DocList, $XPSInputArray);
            if (in_array('Ebook', $OfferedConversionTypes)) $gui1DocList = array_merge($gui1DocList, $EbookInputArray);
            if (in_array('LaTeX', $OfferedConversionTypes)) $gui1DocList = array_merge($gui1DocList, $LaTeXInputArray);
            if (in_array('Markup', $OfferedConversionTypes)) $gui1DocList = array_merge($gui1DocList, $MarkupInputArray);
            if (count($gui1DocList) > 0) { ?>
            <strong><?php echo $Gui1Text14; ?></strong>
            <ol>
              <?php foreach (array_unique($gui1DocList) as $gui1DocArr) { ?>
              <li><?php echo $gui1DocArr; ?></li>
              <?php } ?>
            </ol>
            <?php } if (in_array('Document', $OfferedConversionTypes)) { ?>
            <strong><?php echo $Gui1Text15; ?></strong>
            <ol>
              <?php foreach ($SpreadsheetInputArray as $gui1SpreadArr) { ?>
              <li><?php echo $gui1SpreadArr; ?></li>
              <?php } ?>
            </ol>
            <?php } if (in_array('Document', $OfferedConversionTypes)) { ?>
            <strong><?php echo $Gui1Text16; ?></strong>
            <ol>
              <?php foreach ($PresentationInputArray as $gui1PresArr) { ?>
              <li><?php echo $gui1PresArr; ?></li>
              <?php } ?>
            </ol>
            <?php } if (in_array('Archive', $OfferedConversionTypes)) { ?>
            <strong><?php echo $Gui1Text17; ?></strong>
            <p><i><?php echo $Gui1Text18; ?></i></p>
            <ol>
              <?php foreach ($DearchiveArray as $gui1ArchArr) { ?>
              <li><?php echo $gui1ArchArr; ?></li>
              <?php } ?>
            </ol>
            <?php } if (in_array('Image', $OfferedConversionTypes)) { ?>
            <strong><?php echo $Gui1Text19; ?></strong>
            <p><i><?php echo $Gui1Text21; ?></i></p>
            <?php if (in_array('Trace', $OfferedConversionTypes)) { ?>
            <p><i><?php echo $Gui1Text40; ?></i></p>
            <?php } ?>
            <ol>
              <?php foreach ($ImageInputArray as $gui1ImaArr) { ?>
              <li><?php echo $gui1ImaArr; ?></li>
              <?php } ?>
            </ol>
            <?php } if (in_array('Model', $OfferedConversionTypes)) { ?>
            <strong><?php echo $Gui1Text22; ?></strong>
            <ol>
              <?php foreach ($ModelArray as $gui1ModArr) { ?>
              <li><?php echo $gui1ModArr; ?></li>
              <?php } ?>
            </ol>
            <?php } if (in_array('Scad', $OfferedConversionTypes)) { ?>
            <strong><?php echo $Gui1Text32; ?></strong>
            <p><i><?php echo $Gui1Text33; ?></i></p>
            <p><i><?php echo $Gui1Text34; ?></i></p>
            <ol>
              <?php foreach ($SCADOutputArray as $gui1ScadArr) { ?>
              <li><?php echo $gui1ScadArr; ?></li>
              <?php } ?>
            </ol>
            <?php } if (in_array('Subtitle', $OfferedConversionTypes)) { ?>
            <strong><?php echo $Gui1Text31; ?></strong>
            <ol>
              <?php foreach ($SubtitleInputArray as $gui1SubArr) { ?>
              <li><?php echo $gui1SubArr; ?></li>
              <?php } ?>
            </ol>
            <?php } if (in_array('Drawing', $OfferedConversionTypes)) { ?>
            <strong><?php echo $Gui1Text23; ?></strong>
            <p><i><?php echo $Gui1Text24; ?></i></p>
            <ol>
              <?php foreach ($DrawingInputArray as $gui1DraArr) { ?>
              <li><?php echo $gui1DraArr; ?></li>
              <?php } ?>
            </ol>
            <?php } if (in_array('SVG', $OfferedConversionTypes)) { ?>
            <strong><?php echo $Gui1Text42; ?></strong>
            <ol>
              <?php foreach ($SVGInputArray as $gui1SvgArr) { ?>
              <li><?php echo $gui1SvgArr; ?></li>
              <?php } ?>
            </ol>
            <?php } if (in_array('Data', $OfferedConversionTypes)) { ?>
            <strong><?php echo $Gui1Text38; ?></strong>
            <ol>
              <?php foreach ($DataInputArray as $gui1DataArr) { ?>
              <li><?php echo $gui1DataArr; ?></li>
              <?php } ?>
            </ol>
            <?php } if (in_array('Email', $OfferedConversionTypes)) { ?>
            <strong><?php echo $Gui1Text39; ?></strong>
            <ol>
              <?php foreach ($EmailInputArray as $gui1EmailArr) { ?>
              <li><?php echo $gui1EmailArr; ?></li>
              <?php } ?>
            </ol>
            <?php } if (in_array('Graph', $OfferedConversionTypes)) { ?>
            <strong><?php echo $Gui1Text41; ?></strong>
            <ol>
              <?php foreach ($GraphInputArray as $gui1GraphArr) { ?>
              <li><?php echo $gui1GraphArr; ?></li>
              <?php } ?>
            </ol>
            <?php } if (in_array('OCR', $OfferedConversionTypes)) { ?>
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
    </div>
    <?php } ?>
    <div align='center'>
      <div id='call-to-action1' title='' style='max-width:<?php echo $GuiWidth; ?>; text-align:center;'>
        <p><?php echo $Gui1Text28; ?></p>
      </div>
    </div>
    <div align='center'>
      <?php
      // / The height ceiling is NOT here. It is on the .dropzone form in dropzone.css,
      // / which is the element Dropzone fills with previews & therefore the element that
      // / grows. Constraining this wrapper instead did nothing, because the child simply
      // / grew past it.
      // / A ceiling in both places would give two nested scrollbars, which is worse than
      // / the overflow it was meant to fix.
      // / The form is a panel, so it is drawn in the scheme's colours as the file list is, & each file dropped on it is a card.
      ?>
      <div id='dropzone' style='max-width:<?php echo $GuiWidth; ?>; margin:25px;'>
        <form action='convertCore.php' class='dropzone hrc2-panel' id='filesToUpload' name='filesToUpload' method='post' enctype='multipart/form-data'>
        <input type='hidden' id='token1' name='Token1' value='<?php echo htmlspecialchars($Token1, ENT_QUOTES, 'UTF-8'); ?>'>
        <input type='hidden' id='token2' name='Token2' value='<?php echo htmlspecialchars($Token2, ENT_QUOTES, 'UTF-8'); ?>'>
        </form>
      </div>
      <?php // / The dropzone marks a refused upload in this page's language, on a first visit as on any other. ?>
      <script type='text/javascript'>
        if (typeof HRC2 !== 'undefined' && typeof HRC2.configure === 'function') HRC2.configure(<?php echo json_encode(array('operationFailedText' => (isset($Gui2Text74) ? $Gui2Text74 : 'Operation Failed!'))); ?>);
      </script>
    </div>
    <div align='center'>
      <div id='continue' style='max-width:<?php echo $GuiWidth; ?>; text-align:center;'>
        <?php // / THIS BUTTON IS THE OTHER HALF OF THE SESSION. IT RETURNS THE USER TO
              // / THEIR FILES, so it is built from the same parameters as the refresh
              // / button above & differs only by carrying showFiles.
              // / The core resolves the interface, the language & the colour, & hands them over
              // / as $GuiToUse, $LanguageToUse & $ColorToUse, so nothing here reads $_GET. ?>
        <form action='convertCore.php?showFiles=1&amp;<?php echo htmlspecialchars($sessionParams.$sortParams, ENT_QUOTES, 'UTF-8'); ?>' method='post'>
          <input type='hidden' id='token1' name='Token1' value='<?php echo htmlspecialchars($Token1, ENT_QUOTES, 'UTF-8'); ?>'>
          <input type='hidden' id='token2' name='Token2' value='<?php echo htmlspecialchars($Token2, ENT_QUOTES, 'UTF-8'); ?>'>
          <input type='submit' id='continue-button' class='info-button' value='<?php echo htmlspecialchars($Gui1Text29, ENT_QUOTES, 'UTF-8'); ?>'>
        </form>
        <br />
        <?php if (!$NoGui) { ?>
		    <hr />
        <?php } ?>
      </div>
    </div>
  </div>

    <?php
    // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
    // / $gui1OcrArr & $gui1ScadArr are declared at the top of this file & were missing from
    // / the chain below, so they survived the page. $gui1ScadArr was named in the unset but
    // / never nulled, which drops the symbol without shredding what it held.
    $gui1AudArr = $gui1VidArr = $gui1StreamArr = $gui1DocArr = $gui1SpreadArr = $gui1PresArr = $gui1ArchArr = $gui1ImaArr = $gui1ModArr = $gui1SubArr = $gui1DraArr = $gui1OcrArr = $gui1XpsArr = $gui1ScadArr = $gui1SvgArr = $gui1EbkArr = $gui1DataArr = $gui1EmailArr = $gui1GraphArr = $gui1DocList = $sessionParams = $sortParams = $refreshURL = $selectorBase = $taskbarPage = NULL;
    unset($gui1AudArr, $gui1VidArr, $gui1StreamArr, $gui1DocArr, $gui1SpreadArr, $gui1PresArr, $gui1ArchArr, $gui1ImaArr, $gui1ModArr, $gui1SubArr, $gui1DraArr, $gui1OcrArr, $gui1XpsArr, $gui1ScadArr, $gui1SvgArr, $gui1EbkArr, $gui1DataArr, $gui1EmailArr, $gui1GraphArr, $gui1DocList, $sessionParams, $sortParams, $refreshURL, $selectorBase, $taskbarPage);