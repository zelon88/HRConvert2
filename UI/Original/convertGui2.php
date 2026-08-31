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
// / v3.8.2.
// / This file contains language specific GUI elements for performing file conversions.
// / This file was created by Github user hernandito as part of his forked repo, available 
// / at https://github.com/hernandito/HRConvert2/tree/master. Thank you, hernandito!
// /
// / HARDWARE REQUIREMENTS ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / DEPENDENCY REQUIREMENTS ...
// / This application requires Debian Linux, Apache 2.4, PHP 8+, FFMPEG, Dia, LibreOffice, 
// / Mkisofs, 7zip, Unoconv, libgxps-utils, Tesseract, Unzip, OpenSCAD, Rar, Inkscape, Calibre,
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
$gui2AudArr = $gui2VidArr = $gui2StreamArr = $gui2DocArr = $gui2SpreadArr = $gui2PresArr = $gui2ArchArr = $gui2ImaArr = $gui2ModArr = $gui2SubArr = $gui2DraArr = $gui2OcrArr = $gui2XpsArr = $gui2ScadArr = $gui2SvgArr = $gui2ScadArr = $gui2EbookArr = array();
$selectorBase = 'convertCore.php?';
$selectorSide = ($GUIAlignment === 'left') ? 'right' : 'left';
$selectorSwatches = array(
  'red' => '#c0392b',  'green' => '#27ae60',  'blue' => '#3d71b3',  'grey' => '#7f8c8d',
  'orange' => '#e67e22', 'purple' => '#8e44ad', 'dark' => '#2c3e50');
// / -----------------------------------------------------------------------------------
// / Carry the page state so a selection returns to the page the user was already on.
// /
// / $ShowFiles, $FileListOnly & $NoGui ARRIVE FROM THE CORE. THEY ARE NOT READ HERE.
// / verifyGlobals reads every superglobal this application accepts & buildGUI hands the
// / result to an interface as an ordinary variable. An interface that read $_GET for
// / itself would be defining API surface, which is not an interface's to define, & a
// / second interface reading a different parameter is how two GUIs stop agreeing about
// / what the application accepts.
// /
// / redeclare() rather than .= on every one of these. Appending in place leaves the old
// / string in the register underneath the new one, which is the whole thing
// / ABOUT_DEFENSIVE_MEMORY_MANAGEMENT exists to prevent. redeclare shreds the old value
// / before the new one is written.
if ($ShowFiles) redeclare($selectorBase, $selectorBase.'showFiles=1&');
// / A link inside the frame must keep the frame, or a language change loads the bare list
// / into the whole window instead of back into the panel it came from.
if ($FileListOnly) redeclare($selectorBase, $selectorBase.'fileListOnly=1&');
if ($NoGui) redeclare($selectorBase, $selectorBase.'noGui=TRUE&');
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / THE CHROME BUTTONS CARRY THE SESSION. THEY DO NOT NAVIGATE THE BROWSER.
// /
// / Which page the core renders is decided by showFiles, & showFiles is read from GET
// / only. The session is carried by the token pair, & the token pair is read from POST
// / only. So a control that changes page while staying in the session is a form POST that
// / carries the tokens in its body & names the destination in its query string. That is
// / the same shape the selector bar already uses & is the only shape that works.
// /
// / window.history.back() did not do that. The page it returns to was itself the result of
// / a POST, so a browser either refuses to replay it, asks the user to confirm a
// / resubmission, or serves a cached copy carrying whatever tokens it was rendered with.
// / A user who landed on the upload page that way had no reliable way back to the files
// / they had already uploaded.
// / location.reload(true) had the same problem for the same reason.
// /
// / The current interface, language & colour ride along in the query string. A control
// / that dropped them would silently reset the user to the configured defaults, which
// / reads as the application forgetting the settings they just chose.
$sessionParams = '';
if ($NoGui) redeclare($sessionParams, $sessionParams.'noGui=TRUE&');
redeclare($sessionParams, $sessionParams.'gui='.$GuiToUse.'&language='.$LanguageToUse.'&color='.$ColorToUse);
// / No showFiles, so the core renders the upload page & the session stays alive.
$backURL = 'convertCore.php?'.$sessionParams;
// / showFiles, so the core renders this page again. Never fileListOnly. That asks for a
// / fragment, & a fragment loaded into the whole window is a page with no header, no
// / footer & no script library.
$refreshURL = 'convertCore.php?showFiles=1&'.$sessionParams;
// / -----------------------------------------------------------------------------------
?>
        <?php if (!$FileListOnly) {
          // / A FRAGMENT OPENS NO BODY & LOADS NO LIBRARY.
          // / <body> belongs to the document & footer.php is what closes it. A fragment
          // / loads neither header nor footer, so opening a body here left an unclosed one
          // / inside the div the fragment gets injected into.
          // / The script tag was worse. $JqueryPath is assigned by header.php, which a
          // / fragment never loads, so a fragment emitted src='' & an EMPTY src resolves to
          // / the CURRENT PAGE. Every list refresh therefore had the browser fetch
          // / convertCore.php again & try to parse the returned HTML as javascript, which
          // / throws Unexpected token '<' & burns a whole extra core request doing it.
          // / The page that receives a fragment has already loaded jQuery. ?>
  <body>
    <script type='text/javascript' src='<?php echo $JqueryPath; ?>'></script>
        <?php
          // / THE HELPER IS DEFINED ONCE, BY THE PAGE, & NEVER BY A FRAGMENT.
          // / A fragment is injected INTO the page that already defines this. Sending a
          // / second copy redefines the object mid flight & re-runs its ready handler,
          // / which fetches the fragment again. That is a request loop, & every pass
          // / replaced the list & destroyed the click handlers the previous pass had just
          // / registered. The per file panels below still carry their own handlers, which
          // / is correct, because those belong to the markup being injected. ?>
        <script type='text/javascript'>
          // / THE PAGE SUPPLIES VALUES. HRC2-Functions.js SUPPLIES BEHAVIOUR.
          // / Everything below is something only PHP can know. A session token, a path
          // / built from a session hash, or a string from the language pack. No
          // / behaviour is written into this page & none should be added here.
          if (typeof HRC2 === 'undefined' || typeof HRC2.configure !== 'function') {
            if (window.console && window.console.error) window.console.error('HRConvert2. HRC2-Functions.js did not load, or is an older copy without configure(). The interface cannot run. Reload with a cleared cache.');
          } else {
            HRC2.configure(<?php echo json_encode(array(
              'tokens' => array('Token1' => (string)$Token1, 'Token2' => (string)$Token2),
              'dataPath' => 'DATA/'.$SesHash3.'/',
              'failureText' => $Gui2Text71,
              'scanFailureText' => $Gui2Text72,
              'operationFailedText' => $Gui2Text74,
              'clipboardUnsupportedText' => $GuiFunctionsText1,
              // / The file list is fetched by a SEPARATE request, so it has to be told the
              // / interface, language & colour as well. Without them the core answers with
              // / the configured defaults & the list returns in the wrong language, wearing
              // / the wrong colours, inside a page that is using the ones the user chose.
              // / fileListOnly asks for the fragment. showFiles is what selects this
              // / interface rather than the upload page.
              'fileListURL' => 'convertCore.php?showFiles=1&fileListOnly=1&'.$sessionParams,
              // / The core refuses some operations BEFORE attempting them & reports that
              // / refusal by printing an alert string with no error tag on it. A reply
              // / carrying one of these is a failure even though nothing in it is tagged.
              'failureStrings' => array($Alert3))); ?>);
            $(document).ready(function () { HRC2.init(); });
          }
        </script>
        <?php } // / End of the helper, which a fragment never carries. ?>

    <?php if (!$FileListOnly) { // / Skipped when only the file list is wanted. ?>
<div id='header-text' style='max-width:1000px; margin-left:auto; margin-right:auto; text-align:center;'>
      <?php if (!$NoGui) { ?><h1><?php echo $ApplicationName; ?></h1>
      <hr /><?php } ?>
      <h3><?php echo $Gui2Text1; ?></h3>
      <p><?php echo $Gui2Text30; ?></p>
      <p><?php echo $Gui2Text31; ?></p>
    </div>
    <div>
    <div id='compressAll' name='compressAll' style='max-width:1000px; margin-left:auto; margin-right:auto; text-align:center;'>
      <?php // / The form action is the refresh destination, so the refresh button needs no
            // / formaction of its own. The back button overrides it with one.
            // / EVERY BUTTON IN HERE DECLARES ITS TYPE. A button inside a form defaults to
            // / submit, so the settings toggle would submit the form & reload the page
            // / instead of opening the panel underneath it. These three buttons carried no
            // / type at all before, which was harmless only because they were not in a
            // / form. They are now.
            // / The submit buttons carry no name, so nothing they are called reaches the
            // / core as POST input. The core reads specific keys & a stray one is noise. ?>
      <form id='sessionForm' name='sessionForm' method='post' style='display:inline;' action='<?php echo htmlspecialchars($refreshURL, ENT_QUOTES, 'UTF-8'); ?>'>
        <input type='hidden' name='Token1' value='<?php echo $Token1; ?>'>
        <input type='hidden' name='Token2' value='<?php echo $Token2; ?>'>
        <button type='submit' id='backButton' style='width:50px;' class='info-button' formaction='<?php echo htmlspecialchars($backURL, ENT_QUOTES, 'UTF-8'); ?>'>&#x2190;</button>
        <button type='button' id='userConfigButton' style='width:50px;' class='info-button' title='<?php echo $GuiSelectorText4; ?>' aria-label='<?php echo $GuiSelectorText4; ?>' onclick='toggle_visibility("uiSelector");'>&#9965;</button>
        <button type='submit' id='refreshButton' style='width:50px;' class='info-button'>&#x21BB;</button>
      </form>
      <?php // / The selector panel holds a form of its own, so it stays OUTSIDE the one
            // / above. HTML does not allow a form inside a form & a browser silently drops
            // / the inner one, which would take every selector button with it. ?>
      <div id='uiSelector' name='uiSelector' style='display:none;'>
        <form id='uiSelectorForm' name='uiSelectorForm' method='post' action='<?php echo htmlspecialchars($selectorBase, ENT_QUOTES, 'UTF-8'); ?>'>
          <input type='hidden' name='Token1' value='<?php echo $Token1; ?>'>
          <input type='hidden' name='Token2' value='<?php echo $Token2; ?>'>
          <?php if ($AllowUserSelectableLanguage) { ?>
          <p style='margin:4px 0;'><strong><?php echo $GuiSelectorText1; ?></strong></p>
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
          <p style='margin:4px 0;'><strong><?php echo $GuiSelectorText2; ?></strong></p>
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
          <p style='margin:4px 0;'><strong><?php echo $GuiSelectorText3; ?></strong></p>
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
          HRC2.bindScanAll('scanAllButton', '', 'clamscanall', 'scancoreall',
            <?php echo json_encode(array_values($Files)); ?>, <?php echo json_encode($ConsolidatedLogFileName); ?>);
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
        <input type='submit' id='archallSubmit' name='archallSubmit' class='info-button' value='<?php echo $Gui2Text19; ?>' onclick='toggle_visibility("loadingCommandDiv");'>
        <script type='text/javascript'>
          HRC2.bindArchiveAll('archallSubmit', '', <?php echo json_encode(array_values($Files)); ?>,
            'userarchallfilename', 'archallextension', 'zip');
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
    <?php } // / End of the chrome region.
      // / The chrome opens one wrapper that footer.php closes. In list only mode that
      // / wrapper is skipped, so a plain one is opened in its place below & the closing tag
      // / the footer emits still has something to pair with. Both modes emit the same
      // / number of wrappers, which is what keeps the footer valid in either.
      // /
      // / The full page shows the list through a frame, so a conversion can refresh it in
      // / place without reloading the page & losing every panel the user had open. Nothing
      // / about the list itself changes. It is the same markup, loaded through its own
      // / request. In list only mode the frame is not emitted, because that IS the frame.
      if (!$FileListOnly) { ?>
    <!-- / THE FILE LIST IS FETCHED, NOT FRAMED.
       / An iframe cannot POST for itself, so loading one meant building a second way to
       / authenticate a request, & that second way did not work. The tokens either did not
       / arrive or did not survive, every load minted a fresh session, & each fresh session
       / landed in an empty directory it had just created.
       / HRC2.post already sends these tokens on every conversion & has never failed to.
       / Fetching the list through the same call reuses the request shape that is known to
       / work rather than inventing a parallel one to debug.
       / jQuery .html() executes the script blocks in what it injects, which the per file
       / panels depend on, & innerHTML would not. -->
    <div id='hrc2FileList'></div>
    <?php } else {
      // / The else branch stays open through the entire file list & closes at the end of
      // / this file. The list belongs to list only mode. Closing here would end the branch
      // / immediately & print the list on the full page as well as inside the frame. ?>
    <div>
    <div style='max-width:1000px; margin-left:auto; margin-right:auto; background-color: #fff7e9; padding: 8px; border: 2px solid rgba(0, 0, 0, 0.3); border-radius: 10px;'>
     

      <?php
      foreach ($Files as $File) {
        $extension = getExtension($ConvertTempDir.'/'.$File);
        $FileNoExt = str_replace($extension, '', $File);
        if (!in_array(strtolower($extension), $Allowed)) continue;
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
            HRC2.bindDownload('downloadfilebutton<?php echo $ConvertGuiCounter1; ?>', '<?php echo $ConvertGuiCounter1; ?>', <?php echo json_encode($File); ?>);
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

          // / Gated on Ebook, not Document, so it matches the panel it toggles.
          // / An administrator who removed Ebook from $SupportedConversionTypes, which
          // / config.php invites, drew this button with no panel underneath it.
          if (in_array($extension, $EbookInputArray) && in_array('Ebook', $SupportedConversionTypes)) { ?>
          <a style='float:<?php echo $GUIAlignment; ?>;'>&nbsp;|&nbsp;</a>

          <img id='ebookButton<?php echo $ConvertGuiCounter1; ?>' name='ebookButton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>document.png' style='float:<?php echo $GUIAlignment; ?>; display:block;' 
           onclick='toggle_visibility("ebookOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("ebookButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("ebookXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text14.' '.$File; ?>' alt='<?php echo $Gui2Text14.' '.$File; ?>'/>
          <img id="ebookXButton<?php echo $ConvertGuiCounter1; ?>" name="ebookXButton<?php echo $ConvertGuiCounter1; ?>" src='<?php echo $GuiImageDir; ?>x.png' style="float:<?php echo $GUIAlignment; ?>; display:none;" 
           onclick="toggle_visibility('ebookOptionsDiv<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('ebookButton<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('ebookXButton<?php echo $ConvertGuiCounter1; ?>');" title='<?php echo $Gui2Text15; ?>' alt='<?php echo $Gui2Text15; ?>'/>
          <?php } 

          if (in_array($extension, $SpreadsheetArray) && in_array('Document', $SupportedConversionTypes)) { ?>
          <a style='float:<?php echo $GUIAlignment; ?>;'>&nbsp;|&nbsp;</a>

          <img id='spreadsheetButton<?php echo $ConvertGuiCounter1; ?>' name='spreadsheetButton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>spreadsheet.png' style='float:<?php echo $GUIAlignment; ?>; display:block;' 
           onclick='toggle_visibility("spreadOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("spreadsheetButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("spreadsheetXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text14.' '.$File; ?>' alt='<?php echo $Gui2Text14.' '.$File; ?>'/>
          <img id="spreadsheetXButton<?php echo $ConvertGuiCounter1; ?>" name="spreadsheetXButton<?php echo $ConvertGuiCounter1; ?>" src='<?php echo $GuiImageDir; ?>x.png' style="float:<?php echo $GUIAlignment; ?>; display:none;" 
           onclick="toggle_visibility('spreadOptionsDiv<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('spreadsheetButton<?php echo $ConvertGuiCounter1; ?>'); toggle_visibility('spreadsheetXButton<?php echo $ConvertGuiCounter1; ?>');" title='<?php echo $Gui2Text15; ?>' alt='<?php echo $Gui2Text15; ?>'/>
          <?php }

          if (in_array($extension, $XPSInputArray) && in_array('Document', $SupportedConversionTypes)) { ?>
          <a style='float:<?php echo $GUIAlignment; ?>;'>&nbsp;|&nbsp;</a>

          <img id='xpsButton<?php echo $ConvertGuiCounter1; ?>' name='xpsButton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>document.png' style='float:<?php echo $GUIAlignment; ?>; display:block;' 
           onclick='toggle_visibility("xpsOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("xpsButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("xpsXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text14.' '.$File; ?>' alt='<?php echo $Gui2Text14.' '.$File; ?>'/>
          <img id='xpsXButton<?php echo $ConvertGuiCounter1; ?>' name='xpsXButton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>x.png' style='float:<?php echo $GUIAlignment; ?>; display:none;'
           onclick='toggle_visibility("xpsOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("xpsButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("xpsXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text15; ?>' alt='<?php echo $Gui2Text15; ?>'/>
          <?php }

          if (in_array($extension, $PresentationInputArray) && in_array('Document', $SupportedConversionTypes)) { ?>
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

          if (in_array($extension, $SVGInputArray) && in_array('SVG', $SupportedConversionTypes)) { ?>
          <a style='float:<?php echo $GUIAlignment; ?>;'>&nbsp;|&nbsp;</a>

          <img id='svgButton<?php echo $ConvertGuiCounter1; ?>' name='svgButton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>convert.png' style='float:<?php echo $GUIAlignment; ?>; display:block;' 
           onclick='toggle_visibility("svgOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("svgButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("svgXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text14.' '.$File; ?>' alt='<?php echo $Gui2Text14.' '.$File; ?>'/>
          <img id='svgXButton<?php echo $ConvertGuiCounter1; ?>' name='svgXButton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>x.png' style='float:<?php echo $GUIAlignment; ?>; display:none;' 
           onclick='toggle_visibility("svgOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("svgButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("svgXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text15; ?>' alt='<?php echo $Gui2Text15; ?>'/>
          <?php } 

          if (in_array($extension, $ModelArray) && in_array('Model', $SupportedConversionTypes)) { ?>
          <a style='float:<?php echo $GUIAlignment; ?>;'>&nbsp;|&nbsp;</a>

          <img id='modelButton<?php echo $ConvertGuiCounter1; ?>' name='modelButton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>convert.png' style='float:<?php echo $GUIAlignment; ?>; display:block;' 
           onclick='toggle_visibility("modelOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("modelButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("modelXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text14.' '.$File; ?>' alt='<?php echo $Gui2Text14.' '.$File; ?>'/>
          <img id='modelXButton<?php echo $ConvertGuiCounter1; ?>' name='modelXButton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>x.png' style='float:<?php echo $GUIAlignment; ?>; display:none;'
           onclick='toggle_visibility("modelOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("modelButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("modelXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text15; ?>' alt='<?php echo $Gui2Text15; ?>'/>
          <?php } 

          if ($extension === 'scad' && in_array('Scad', $SupportedConversionTypes)) { ?>
          <a style='float:<?php echo $GUIAlignment; ?>;'>&nbsp;|&nbsp;</a>

          <img id='scadButton<?php echo $ConvertGuiCounter1; ?>' name='scadButton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>convert.png' style='float:<?php echo $GUIAlignment; ?>; display:block;'
           onclick='toggle_visibility("scadOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("scadButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("scadXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text14.' '.$File; ?>' alt='<?php echo $Gui2Text14.' '.$File; ?>'/>
          <img id='scadXButton<?php echo $ConvertGuiCounter1; ?>' name='scadXButton<?php echo $ConvertGuiCounter1; ?>' src='<?php echo $GuiImageDir; ?>x.png' style='float:<?php echo $GUIAlignment; ?>; display:none;'
           onclick='toggle_visibility("scadOptionsDiv<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("scadButton<?php echo $ConvertGuiCounter1; ?>"); toggle_visibility("scadXButton<?php echo $ConvertGuiCounter1; ?>");' title='<?php echo $Gui2Text15; ?>' alt='<?php echo $Gui2Text15; ?>'/>
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
          <p style='max-width:1000px;'></p>
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
            HRC2.bindRun('archfileSubmit<?php echo $ConvertGuiCounter1; ?>', '<?php echo $ConvertGuiCounter1; ?>',
              <?php echo json_encode(array('archive' => $File, 'filesToArchive' => $File)); ?>,
              <?php echo json_encode(array('archextension' => 'archfileextension'.$ConvertGuiCounter1, 'userfilename' => 'userarchfilefilename'.$ConvertGuiCounter1)); ?>);
          </script>
        </div>

        <?php if ($AllowUserShare) { ?>
        <div id='sharefileOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='sharefileOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"></p>
          <p><strong><?php echo $Gui2Text23; ?></strong></p>
          <p id='sharelinkStatus<?php echo $ConvertGuiCounter1; ?>' name='sharelinkStatus<?php echo $ConvertGuiCounter1; ?>'><?php echo $Gui2Text24; ?><i><?php echo $Gui2Text25; ?></i></p>
          <p id='shareclipStatus<?php echo $ConvertGuiCounter1; ?>' name='shareclipStatus<?php echo $ConvertGuiCounter1; ?>'><?php echo $Gui2Text27; ?><i><?php echo $Gui2Text25; ?></i></p>
          <p id='sharelinkURL<?php echo $ConvertGuiCounter1; ?>' name='sharelinkURL<?php echo $ConvertGuiCounter1; ?>'><?php echo $Gui2Text29; ?><i><?php echo $Gui2Text25; ?></i></p>
          <input type="submit" id="sharegeneratebutton<?php echo $ConvertGuiCounter1; ?>" name="sharegeneratebutton<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text32; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">
          <input type="submit" id="sharecopybutton<?php echo $ConvertGuiCounter1; ?>" name="sharecopybutton<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text33; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">
          <script type='text/javascript'>
            HRC2.bindShare(<?php echo json_encode(array(
              'generateId' => 'sharegeneratebutton'.$ConvertGuiCounter1,
              'copyId' => 'sharecopybutton'.$ConvertGuiCounter1,
              'suffix' => (string)$ConvertGuiCounter1,
              'fileName' => $File,
              'shareBaseURL' => $FullURL.'/DATA/'.$SesHash3.'/',
              'linkGeneratedHtml' => $Gui2Text24.'<i>'.$Gui2Text26.'</i>',
              'clipCopiedHtml' => $Gui2Text27.'<i>'.$Gui2Text28.'</i>',
              'linkUrlPrefixHtml' => $Gui2Text29,
              'copiedText' => $Gui2Text73)); ?>);
          </script>

        </div>
        <?php } ?>

        <div id='deletefileOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='deletefileOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"></p>
          <p><strong><?php echo $Gui2Text69; ?></strong></p>
          <input type='submit' id='confirmdeletefilebutton<?php echo $ConvertGuiCounter1; ?>' name='confirmdeletefilebutton<?php echo $ConvertGuiCounter1; ?>' value='<?php echo $Gui2Text70; ?>' onclick='toggle_visibility("loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>");'>
          <script type='text/javascript'>
            HRC2.bindDelete('confirmdeletefilebutton<?php echo $ConvertGuiCounter1; ?>', '<?php echo $ConvertGuiCounter1; ?>', <?php echo json_encode($File); ?>);
          </script>
        </div>

        <?php if ($AllowUserVirusScan) { ?>
        <div id='scanfileOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='scanfileOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"></p>
          <p><strong><?php echo $Gui2Text34; ?></strong></p>
          <input type="submit" id="scancorebutton<?php echo $ConvertGuiCounter1; ?>" name="scancorebutton<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text35; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">
          <input type="submit" id="clamscanbutton<?php echo $ConvertGuiCounter1; ?>" name="clamscanbutton<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text36; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">
          <input type="submit" id="scanallbutton<?php echo $ConvertGuiCounter1; ?>" name="scanallbutton<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text37; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">
          <script type='text/javascript'>
            <?php $gui2ScanLog = json_encode($ConsolidatedLogFileName); $gui2ScanFile = json_encode($File); ?>
            HRC2.bindScan('scancorebutton<?php echo $ConvertGuiCounter1; ?>', '<?php echo $ConvertGuiCounter1; ?>', 'scancore', <?php echo $gui2ScanFile; ?>, <?php echo $gui2ScanLog; ?>);
            HRC2.bindScan('clamscanbutton<?php echo $ConvertGuiCounter1; ?>', '<?php echo $ConvertGuiCounter1; ?>', 'clamav', <?php echo $gui2ScanFile; ?>, <?php echo $gui2ScanLog; ?>);
            HRC2.bindScan('scanallbutton<?php echo $ConvertGuiCounter1; ?>', '<?php echo $ConvertGuiCounter1; ?>', 'all', <?php echo $gui2ScanFile; ?>, <?php echo $gui2ScanLog; ?>);
          </script>

        </div>
        <?php }

        if (in_array($extension, $PDFWorkArr) && in_array('OCR', $SupportedConversionTypes)) { 
        ?>
        <div id='pdfOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='pdfOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"></p>
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
          <script type='text/javascript'>
            HRC2.bindRun('pdfconvertSubmit<?php echo $ConvertGuiCounter1; ?>', '<?php echo $ConvertGuiCounter1; ?>',
              <?php echo json_encode(array('pdfworkSelected' => $File)); ?>,
              <?php echo json_encode(array('method' => 'pdfmethod'.$ConvertGuiCounter1, 'pdfextension' => 'pdfextension'.$ConvertGuiCounter1, 'userpdfconvertfilename' => 'userpdffilename'.$ConvertGuiCounter1)); ?>);
          </script>
        </div>
        <?php } 

        // / THIS GATE MUST MATCH THE BUTTON THAT TOGGLES IT.
        // / The button above tests $DearchiveArray & this panel tested $ArchiveArray.
        // / config.php ships gz, bz, bz2, vhd, vdi, cbr, cbz, tar.gz & tar.bz2 in the
        // / first & not the second, so those files drew a button that toggled a panel
        // / which had never been emitted. That threw a TypeError & hid the button.
        // / $DearchiveArray is what the input can be READ from & is the correct gate.
        // / $ArchiveArray is what the output can be WRITTEN to & stays the list below.
        if (in_array($extension, $DearchiveArray) && in_array('Archive', $SupportedConversionTypes)) {
        ?>
        <div id='archiveOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='archiveOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"></p>
          <p><strong><?php echo $Gui2Text42; ?></strong></p>
          <p><?php echo $Gui2Text17; ?><input type="text" id='userarchivefilename<?php echo $ConvertGuiCounter1; ?>' name='userarchivefilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='archiveextension<?php echo $ConvertGuiCounter1; ?>' name='archiveextension<?php echo $ConvertGuiCounter1; ?>'> 
            <option value="zip"><?php echo $Gui2Text18; ?></option>
            <?php foreach ($ArchiveArray as $gui2ArchArr) { ?>
            <option value="<?php echo $gui2ArchArr; ?>"><?php echo $gui2ArchArr; ?></option>
            <?php } ?>
          </select></p>
          <input type="submit" id="archiveconvertSubmit<?php echo $ConvertGuiCounter1; ?>" name="archiveconvertSubmit<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text53; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">
          <script type='text/javascript'>
            HRC2.bindRun('archiveconvertSubmit<?php echo $ConvertGuiCounter1; ?>', '<?php echo $ConvertGuiCounter1; ?>',
              <?php echo json_encode(array('convertSelected' => $File)); ?>,
              <?php echo json_encode(array('extension' => 'archiveextension'.$ConvertGuiCounter1, 'userconvertfilename' => 'userarchivefilename'.$ConvertGuiCounter1)); ?>);
          </script>

        </div>
        <?php } 

        if (in_array($extension, $DocumentArray) && in_array('Document', $SupportedConversionTypes)) {
        ?>
        <div id='docOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='docOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"></p>
          <p><strong><?php echo $Gui2Text43; ?></strong></p>
          <p><?php echo $Gui2Text17; ?><input type="text" id='userdocfilename<?php echo $ConvertGuiCounter1; ?>' name='userdocfilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='docextension<?php echo $ConvertGuiCounter1; ?>' name='docextension<?php echo $ConvertGuiCounter1; ?>'> 
            <option value="txt"><?php echo $Gui2Text18; ?></option>
            <?php foreach ($DocumentArray as $gui2DocArr) { ?>
            <option value="<?php echo $gui2DocArr; ?>"><?php echo $gui2DocArr; ?></option>
            <?php } ?>
          </select></p>
          <input type="submit" id="docconvertSubmit<?php echo $ConvertGuiCounter1; ?>" name="docconvertSubmit<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text54; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">
          <script type='text/javascript'>
            HRC2.bindRun('docconvertSubmit<?php echo $ConvertGuiCounter1; ?>', '<?php echo $ConvertGuiCounter1; ?>',
              <?php echo json_encode(array('convertSelected' => $File)); ?>,
              <?php echo json_encode(array('extension' => 'docextension'.$ConvertGuiCounter1, 'userconvertfilename' => 'userdocfilename'.$ConvertGuiCounter1)); ?>);
          </script>
        </div>
        <?php }

        if (in_array($extension, $EbookInputArray) && in_array('Ebook', $SupportedConversionTypes)) {
        ?>
        <div id='ebookOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='ebookOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"></p>
          <p><strong><?php echo $Gui2Text81; ?></strong></p>
          <p><?php echo $Gui2Text17; ?><input type="text" id='userebookfilename<?php echo $ConvertGuiCounter1; ?>' name='userebookfilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='ebookextension<?php echo $ConvertGuiCounter1; ?>' name='ebookextension<?php echo $ConvertGuiCounter1; ?>'> 
            <option value="epub"><?php echo $Gui2Text18; ?></option>
            <?php foreach ($EbookOutputArray as $gui2EbookArr) { ?>
            <option value="<?php echo $gui2EbookArr; ?>"><?php echo $gui2EbookArr; ?></option>
            <?php } ?>
          </select></p>
          <input type="submit" id="ebookconvertSubmit<?php echo $ConvertGuiCounter1; ?>" name="ebookconvertSubmit<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text82; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">
          <script type='text/javascript'>
            HRC2.bindRun('ebookconvertSubmit<?php echo $ConvertGuiCounter1; ?>', '<?php echo $ConvertGuiCounter1; ?>',
              <?php echo json_encode(array('convertSelected' => $File)); ?>,
              <?php echo json_encode(array('extension' => 'ebookextension'.$ConvertGuiCounter1, 'userconvertfilename' => 'userebookfilename'.$ConvertGuiCounter1)); ?>);
          </script>
        </div>
        <?php }

        if (in_array($extension, $SpreadsheetArray) && in_array('Document', $SupportedConversionTypes)) {
        ?>
        <div id='spreadOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='spreadOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"></p>
          <p><strong><?php echo $Gui2Text44; ?></strong></p>
          <p><?php echo $Gui2Text17; ?><input type="text" id='userspreadfilename<?php echo $ConvertGuiCounter1; ?>' name='userspreadfilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='spreadextension<?php echo $ConvertGuiCounter1; ?>' name='spreadextension<?php echo $ConvertGuiCounter1; ?>'>
            <option value="ods"><?php echo $Gui2Text18; ?></option> 
            <?php foreach ($SpreadsheetArray as $gui2SpreadArr) { ?>
            <option value="<?php echo $gui2SpreadArr; ?>"><?php echo $gui2SpreadArr; ?></option>
            <?php } ?>
          </select></p>
          <input type="submit" id="spreadconvertSubmit<?php echo $ConvertGuiCounter1; ?>" name="spreadconvertSubmit<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text55; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">        
          <script type='text/javascript'>
            HRC2.bindRun('spreadconvertSubmit<?php echo $ConvertGuiCounter1; ?>', '<?php echo $ConvertGuiCounter1; ?>',
              <?php echo json_encode(array('convertSelected' => $File)); ?>,
              <?php echo json_encode(array('extension' => 'spreadextension'.$ConvertGuiCounter1, 'userconvertfilename' => 'userspreadfilename'.$ConvertGuiCounter1)); ?>);
          </script>

        </div>
        <?php }

        if (in_array($extension, $XPSInputArray) && in_array('Document', $SupportedConversionTypes)) {
        ?>
        <div id='xpsOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='xpsOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"></p>
          <p><strong><?php echo $Gui2Text78; ?></strong></p>
          <p><?php echo $Gui2Text17; ?><input type="text" id='userxpsfilename<?php echo $ConvertGuiCounter1; ?>' name='userxpsfilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='xpsextension<?php echo $ConvertGuiCounter1; ?>' name='xpsextension<?php echo $ConvertGuiCounter1; ?>'>
            <option value="pdf"><?php echo $Gui2Text18; ?></option>
            <?php foreach ($XPSOutputArray as $gui2XpsArr) { ?>
            <option value="<?php echo $gui2XpsArr; ?>"><?php echo $gui2XpsArr; ?></option>
            <?php } ?>
          </select></p>
          <input type="submit" id="xpsconvertSubmit<?php echo $ConvertGuiCounter1; ?>" name="xpsconvertSubmit<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text56; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">
          <script type='text/javascript'>
            HRC2.bindRun('xpsconvertSubmit<?php echo $ConvertGuiCounter1; ?>', '<?php echo $ConvertGuiCounter1; ?>',
              <?php echo json_encode(array('convertSelected' => $File)); ?>,
              <?php echo json_encode(array('extension' => 'xpsextension'.$ConvertGuiCounter1, 'userconvertfilename' => 'userxpsfilename'.$ConvertGuiCounter1)); ?>);
          </script>

        </div>
        <?php }
        if (in_array($extension, $PresentationInputArray) && in_array('Document', $SupportedConversionTypes)) {
        ?>
        <div id='presentationOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='presentationOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"></p>
          <p><strong><?php echo $Gui2Text77; ?></strong></p>
          <p><?php echo $Gui2Text17; ?><input type="text" id='userpresentationfilename<?php echo $ConvertGuiCounter1; ?>' name='userpresentationfilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='presentationextension<?php echo $ConvertGuiCounter1; ?>' name='presentationextension<?php echo $ConvertGuiCounter1; ?>'>
            <option value="odp"><?php echo $Gui2Text18; ?></option>
            <?php foreach ($PresentationOutputArray as $gui2PresArr) { ?>
            <option value="<?php echo $gui2PresArr; ?>"><?php echo $gui2PresArr; ?></option>
            <?php } ?>
          </select></p>
          <input type="submit" id="presentationconvertSubmit<?php echo $ConvertGuiCounter1; ?>" name="presentationconvertSubmit<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text56; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">
          <script type='text/javascript'>
            HRC2.bindRun('presentationconvertSubmit<?php echo $ConvertGuiCounter1; ?>', '<?php echo $ConvertGuiCounter1; ?>',
              <?php echo json_encode(array('convertSelected' => $File)); ?>,
              <?php echo json_encode(array('extension' => 'presentationextension'.$ConvertGuiCounter1, 'userconvertfilename' => 'userpresentationfilename'.$ConvertGuiCounter1)); ?>);
          </script>

        </div>
        <?php }

        if (in_array($extension, $MediaInputArray) && in_array('Audio', $SupportedConversionTypes)) {
        ?>
        <div id='audioOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='audioOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"></p>
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
          <script type='text/javascript'>
            HRC2.bindRun('audioconvertSubmit<?php echo $ConvertGuiCounter1; ?>', '<?php echo $ConvertGuiCounter1; ?>',
              <?php echo json_encode(array('convertSelected' => $File)); ?>,
              <?php echo json_encode(array('bitrate' => 'bitrate'.$ConvertGuiCounter1, 'extension' => 'audioextension'.$ConvertGuiCounter1, 'userconvertfilename' => 'useraudiofilename'.$ConvertGuiCounter1)); ?>);
          </script>

        </div>
        <?php } 

        if (in_array($extension, $VideoInputArray) && in_array('Video', $SupportedConversionTypes)) {
        ?>
        <div id='videoOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='videoOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"></p>
          <p><strong><?php echo $Gui2Text46; ?></strong></p>
          <p><?php echo $Gui2Text17; ?><input type="text" id='uservideofilename<?php echo $ConvertGuiCounter1; ?>' name='uservideofilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='videoextension<?php echo $ConvertGuiCounter1; ?>' name='videoextension<?php echo $ConvertGuiCounter1; ?>'>
            <option value="mp4"><?php echo $Gui2Text18; ?></option> 
            <?php foreach ($VideoOutputArray as $gui2VidArr) { ?>
            <option value="<?php echo $gui2VidArr; ?>"><?php echo $gui2VidArr; ?></option>
            <?php } ?>
          </select></p>
          <input type="submit" id="videoconvertSubmit<?php echo $ConvertGuiCounter1; ?>" name="videoconvertSubmit<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text58; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">
          <script type='text/javascript'>
            HRC2.bindRun('videoconvertSubmit<?php echo $ConvertGuiCounter1; ?>', '<?php echo $ConvertGuiCounter1; ?>',
              <?php echo json_encode(array('convertSelected' => $File)); ?>,
              <?php echo json_encode(array('extension' => 'videoextension'.$ConvertGuiCounter1, 'userconvertfilename' => 'uservideofilename'.$ConvertGuiCounter1)); ?>);
          </script>

        </div>
        <?php } 

        if (in_array($extension, $StreamArray) && in_array('Stream', $SupportedConversionTypes)) {
        ?>
        <div id='streamOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='streamOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"></p>
          <p><strong><?php echo $Gui2Text47; ?></strong></p>
          <p><?php echo $Gui2Text17; ?><input type="text" id='userstreamfilename<?php echo $ConvertGuiCounter1; ?>' name='userstreamfilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='streamextension<?php echo $ConvertGuiCounter1; ?>' name='streamextension<?php echo $ConvertGuiCounter1; ?>'>
            <option value="mp4"><?php echo $Gui2Text18; ?></option>
            <?php foreach ($StreamOutputArray as $gui2StreamArr) { ?>
            <option value="<?php echo $gui2StreamArr; ?>"><?php echo $gui2StreamArr; ?></option>
            <?php } ?>
          </select></p>
          <input type="submit" id="streamconvertSubmit<?php echo $ConvertGuiCounter1; ?>" name="streamconvertSubmit<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text59; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">
          <script type='text/javascript'>
            HRC2.bindRun('streamconvertSubmit<?php echo $ConvertGuiCounter1; ?>', '<?php echo $ConvertGuiCounter1; ?>',
              <?php echo json_encode(array('convertSelected' => $File)); ?>,
              <?php echo json_encode(array('extension' => 'streamextension'.$ConvertGuiCounter1, 'userconvertfilename' => 'userstreamfilename'.$ConvertGuiCounter1)); ?>);
          </script>

        </div>
        <?php } 

        if (in_array($extension, $ModelArray) && in_array('Model', $SupportedConversionTypes)) {
        ?>
        <div id='modelOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='modelOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"></p>
          <p><strong><?php echo $Gui2Text48; ?></strong></p>
          <p><?php echo $Gui2Text17; ?><input type="text" id='usermodelfilename<?php echo $ConvertGuiCounter1; ?>' name='usermodelfilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='modelextension<?php echo $ConvertGuiCounter1; ?>' name='modelextension<?php echo $ConvertGuiCounter1; ?>'>
            <option value="3ds"><?php echo $Gui2Text18; ?></option>
            <?php foreach ($ModelArray as $gui2ModArr) { ?>
            <option value="<?php echo $gui2ModArr; ?>"><?php echo $gui2ModArr; ?></option>
            <?php } ?>
          </select></p>
          <input type="submit" id="modelconvertSubmit<?php echo $ConvertGuiCounter1; ?>" name="modelconvertSubmit<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text60; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">
          <script type='text/javascript'>
            HRC2.bindRun('modelconvertSubmit<?php echo $ConvertGuiCounter1; ?>', '<?php echo $ConvertGuiCounter1; ?>',
              <?php echo json_encode(array('convertSelected' => $File)); ?>,
              <?php echo json_encode(array('extension' => 'modelextension'.$ConvertGuiCounter1, 'userconvertfilename' => 'usermodelfilename'.$ConvertGuiCounter1)); ?>);
          </script>

        </div>
        <?php } 

        if ($extension === 'scad' && in_array('Scad', $SupportedConversionTypes)) {
        ?>
        <div id='scadOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='scadOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"></p>
          <p><strong><?php echo $Gui2Text79; ?></strong></p>
          <p><?php echo $Gui2Text17; ?><input type="text" id='userscadfilename<?php echo $ConvertGuiCounter1; ?>' name='userscadfilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='scadextension<?php echo $ConvertGuiCounter1; ?>' name='scadextension<?php echo $ConvertGuiCounter1; ?>'>
            <option value=""><?php echo $Gui2Text18; ?></option>
            <?php foreach ($SCADOutputArray as $gui2ScadArr) { ?>
            <option value="<?php echo $gui2ScadArr; ?>"><?php echo $gui2ScadArr; ?></option>
            <?php } ?>
          </select></p>
          <input type="submit" id="scadconvertSubmit<?php echo $ConvertGuiCounter1; ?>" name="scadconvertSubmit<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text80; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">
          <script type='text/javascript'>
            HRC2.bindRun('scadconvertSubmit<?php echo $ConvertGuiCounter1; ?>', '<?php echo $ConvertGuiCounter1; ?>',
              <?php echo json_encode(array('convertSelected' => $File)); ?>,
              <?php echo json_encode(array('extension' => 'scadextension'.$ConvertGuiCounter1, 'userconvertfilename' => 'userscadfilename'.$ConvertGuiCounter1)); ?>);
          </script>

        </div>
        <?php }

        if (in_array($extension, $SubtitleInputArray) && in_array('Subtitle', $SupportedConversionTypes)) {
        ?>
        <div id='subtitleOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='subtitleOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"></p>
          <p><strong><?php echo $Gui2Text75; ?></strong></p>
          <p><?php echo $Gui2Text17; ?><input type="text" id='usersubtitlefilename<?php echo $ConvertGuiCounter1; ?>' name='usersubtitlefilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='subtitleextension<?php echo $ConvertGuiCounter1; ?>' name='subtitleextension<?php echo $ConvertGuiCounter1; ?>'>
            <option value=""><?php echo $Gui2Text18; ?></option>
            <?php foreach ($SubtitleOutputArray as $gui2SubArr) { ?>
            <option value="<?php echo $gui2SubArr; ?>"><?php echo $gui2SubArr; ?></option>
            <?php } ?>
          </select></p>
          <input type="submit" id="subtitleconvertSubmit<?php echo $ConvertGuiCounter1; ?>" name="subtitleconvertSubmit<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text76; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">
          <script type='text/javascript'>
            HRC2.bindRun('subtitleconvertSubmit<?php echo $ConvertGuiCounter1; ?>', '<?php echo $ConvertGuiCounter1; ?>',
              <?php echo json_encode(array('convertSelected' => $File)); ?>,
              <?php echo json_encode(array('extension' => 'subtitleextension'.$ConvertGuiCounter1, 'userconvertfilename' => 'usersubtitlefilename'.$ConvertGuiCounter1)); ?>);
          </script>

        </div>
        <?php } 

        if (in_array($extension, $DrawingArray) && in_array('Drawing', $SupportedConversionTypes)) {
        ?>
        <div id='drawingOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='drawingOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"></p>
          <p><strong><?php echo $Gui2Text49; ?></strong></p>
          <p><?php echo $Gui2Text17; ?><input type="text" id='userdrawingfilename<?php echo $ConvertGuiCounter1; ?>' name='userdrawingfilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='drawingextension<?php echo $ConvertGuiCounter1; ?>' name='drawingextension<?php echo $ConvertGuiCounter1; ?>'>
            <option value="jpg"><?php echo $Gui2Text18; ?></option>
            <?php foreach ($DrawingArray as $gui2DraArr) { ?>
            <option value="<?php echo $gui2DraArr; ?>"><?php echo $gui2DraArr; ?></option>
            <?php } ?>
          </select></p>
          <input type="submit" id="drawingconvertSubmit<?php echo $ConvertGuiCounter1; ?>" name="drawingconvertSubmit<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text61; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">     
          <script type='text/javascript'>
            HRC2.bindRun('drawingconvertSubmit<?php echo $ConvertGuiCounter1; ?>', '<?php echo $ConvertGuiCounter1; ?>',
              <?php echo json_encode(array('convertSelected' => $File)); ?>,
              <?php echo json_encode(array('extension' => 'drawingextension'.$ConvertGuiCounter1, 'userconvertfilename' => 'userdrawingfilename'.$ConvertGuiCounter1)); ?>);
          </script>

        </div>
        <?php } 

        if (in_array($extension, $SVGInputArray) && in_array('SVG', $SupportedConversionTypes)) {
        ?>
        <div id='svgOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='svgOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display:none;">
          <p style="max-width:1000px;"></p>
          <p><strong><?php echo $Gui2Text49; ?></strong></p>
          <p><?php echo $Gui2Text17; ?><input type="text" id='svgfilename<?php echo $ConvertGuiCounter1; ?>' name='usersvgfilename<?php echo $ConvertGuiCounter1; ?>' value='<?php echo str_replace('.', '', $FileNoExt); ?>'>
          <select id='svgextension<?php echo $ConvertGuiCounter1; ?>' name='svgextension<?php echo $ConvertGuiCounter1; ?>'>
            <option value="png"><?php echo $Gui2Text18; ?></option>
            <?php foreach ($SVGOutputArray as $gui2SvgArr) { ?>
            <option value="<?php echo $gui2SvgArr; ?>"><?php echo $gui2SvgArr; ?></option>
            <?php } ?>
          </select></p>
          <p><?php echo $Gui2Text64; ?></p>
          <p><input type="number" size="4" value="0" id='width<?php echo $ConvertGuiCounter1; ?>' name='width<?php echo $ConvertGuiCounter1; ?>' min="0" max="10000"> X <input type="number" size="4" value="0" id="height<?php echo $ConvertGuiCounter1; ?>" name="height<?php echo $ConvertGuiCounter1; ?>" min="0"  max="10000"></p> 
          <input type="submit" id="svgconvertSubmit<?php echo $ConvertGuiCounter1; ?>" name="svgconvertSubmit<?php echo $ConvertGuiCounter1; ?>" value='<?php echo $Gui2Text61; ?>' onclick="toggle_visibility('loadingCommandDiv<?php echo $ConvertGuiCounter1; ?>');">     
          <script type='text/javascript'>
            HRC2.bindRun('svgconvertSubmit<?php echo $ConvertGuiCounter1; ?>', '<?php echo $ConvertGuiCounter1; ?>',
              <?php echo json_encode(array('convertSelected' => $File)); ?>,
              <?php echo json_encode(array('width' => 'width'.$ConvertGuiCounter1, 'height' => 'height'.$ConvertGuiCounter1, 'extension' => 'svgextension'.$ConvertGuiCounter1, 'userconvertfilename' => 'svgfilename'.$ConvertGuiCounter1)); ?>);
          </script>

        </div>
        <?php } 

        if (in_array($extension, $ImageArray) && in_array('Image', $SupportedConversionTypes)) {
        ?>
        <div id='imageOptionsDiv<?php echo $ConvertGuiCounter1; ?>' name='imageOptionsDiv<?php echo $ConvertGuiCounter1; ?>' style="max-width:750px; display: block; padding-left: 10px; background-color: #edf9ff; padding-top: 10px;padding-bottom: 10px; margin-bottom: 10px;margin-left: 10px;border: 1px solid #bfbfbf; border-radius: 7px; margin-top: 3px; display:none;">
          
          <strong><?php echo $Gui2Text50; ?></strong>
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
          <script type='text/javascript'>
            HRC2.bindRun('convertPhotoSubmit<?php echo $ConvertGuiCounter1; ?>', '<?php echo $ConvertGuiCounter1; ?>',
              <?php echo json_encode(array('convertSelected' => $File)); ?>,
              <?php echo json_encode(array('rotate' => 'rotate'.$ConvertGuiCounter1, 'width' => 'width'.$ConvertGuiCounter1, 'height' => 'height'.$ConvertGuiCounter1, 'extension' => 'photoextension'.$ConvertGuiCounter1, 'userconvertfilename' => 'userphotofilename'.$ConvertGuiCounter1)); ?>);
          </script>

          </div>
        <?php } ?>

        <hr />
      <?php } ?>
    </div>
  </div>
    <?php } // / End of the file list. It renders only in list only mode, because the
      // / full page shows it through the frame above & would otherwise print it twice. ?>
    <?php
    // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
    $gui2AudArr = $gui2VidArr = $gui2StreamArr = $gui2DocArr = $gui2SpreadArr = $gui2XpsArr = $gui2PresArr = $gui2ArchArr = $gui2ImaArr = $gui2ModArr = $gui2SubArr = $gui2DraArr = $gui2OcrArr = $gui2ScadArr = $selectorBase = $selectorSide = $selectorSwatches = $selectorLang = $selectorLabel = $selectorCurrent = $selectorColor = $selectorSwatch = $selectorGui = $selectorURL = $gui2SvgArr = $gui2EbookArr = $sessionParams = $backURL = $refreshURL = $extension = $FileNoExt = NULL;
    unset($gui2AudArr, $gui2VidArr, $gui2StreamArr, $gui2DocArr, $gui2SpreadArr, $gui2XpsArr, $gui2PresArr, $gui2ArchArr, $gui2ImaArr, $gui2ModArr, $gui2SubArr, $gui2DraArr, $gui2OcrArr, $gui2ScadArr, $gui2SvgArr, $selectorBase, $selectorSide, $selectorSwatches, $selectorLang, $selectorLabel, $selectorCurrent, $selectorColor, $selectorSwatch, $selectorGui, $selectorURL, $gui2EbookArr, $sessionParams, $backURL, $refreshURL, $extension, $FileNoExt);