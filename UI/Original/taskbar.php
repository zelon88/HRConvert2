<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRConvert2, Copyright on 11/14/2026 by Justin Grimes, www.github.com/zelon88
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
// / v4.2.1.
// / This file contains the bar at the top of the upload page & the file list, & the panels its buttons open.
// / Every control the two pages share is here, so both pages draw them alike & neither repeats them.
// / Back & Refresh are at the start of the bar. Workflows, the settings & Sign in are at its end.
// / The bar is as wide as the page's column, which --GUI Maximum Width-- bounds, so Sign in is never farther from the page than the column's own edge.
// / The settings open one panel, holding the language, the colour, the interface & Start Over, & a signed in user's name opens another, holding Sign out.
// / The page that requires it sets $taskbarPage, which is upload or files, & the addresses its buttons post to, which are $refreshURL,
// / $selectorBase, $sessionParams & $sortParams, & on the file list $backURL as well. It reads no request itself.
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
// / Check if the core is loaded.
if (!isset($CoreLoaded)) die('ERROR!!! HRConvert2-2, This file cannot process your request! Please submit your file to convertCore.php instead!');
// / Assign temporary variables.
// / What the page says it is, & what the Engine says about the visitor. A missing value reads as the most careful one.
$taskbarOnFiles = (isset($taskbarPage) && $taskbarPage === 'files');
$taskbarAccount = (isset($EnginePageState) && is_array($EnginePageState)) ? $EnginePageState : array();
$taskbarAccountsAreOn = !empty($taskbarAccount['Enabled']);
$taskbarIsSignedIn = ($taskbarAccountsAreOn && !empty($taskbarAccount['SignedIn']));
$taskbarUsername = $taskbarIsSignedIn ? (string)($taskbarAccount['Username'] ?? '') : '';
// / Workflows is always on the file list, & on the upload page once there is a file for it to run on.
$taskbarOffersWorkflows = (!empty($EnableWorkflows) && ($taskbarOnFiles or $FileCount > 0));
// / Workflows from the upload page opens the file list with its panel open, & the address after the hash is never sent to the server.
$taskbarWorkflowsURL = 'convertCore.php?showFiles=1&'.$sessionParams.$sortParams.'#hrc2Workflows';
// / The settings are offered where there is something in them to choose or to clear.
$taskbarOffersSettings = ($AllowUserSelectableLanguage or $AllowUserSelectableColor or $AllowUserSelectableGui or $FileCount > 0);
$taskbarSwatches = array(
  'red' => '#c0392b',  'green' => '#27ae60',  'blue' => '#3d71b3',  'grey' => '#7f8c8d',
  'orange' => '#e67e22', 'purple' => '#8e44ad', 'dark' => '#2c3e50');
$taskbarLanguage = $taskbarLabel = $taskbarColor = $taskbarSwatch = $taskbarGui = $taskbarURL = $taskbarWho = '';
$taskbarIsCurrent = FALSE;
$taskbarWho = $GuiAuthText17.' '.$taskbarUsername;
// / -----------------------------------------------------------------------------------
?>
      <?php // / The bar & its panels are held in a column as wide as the page's, so on a wide screen they stay over what they belong to.
            // / The bar is one panel, & each of its buttons is the scheme's button, made the size a bar's button is.
            // / Every button declares its type, because a button inside a form defaults to submit, & a settings button would reload the page instead of opening its panel.
            // / A button that is only a glyph carries a title & an aria-label, because a title alone is not announced reliably & an aria-label alone shows nothing on hover.
            // / The submit buttons carry no name, so nothing they are called reaches the core as POST input. ?>
      <div id='hrc2TaskbarColumn' class='hrc2-taskbar-column' style='max-width:<?php echo $GuiWidth; ?>;'>
      <div id='hrc2Taskbar' class='hrc2-panel hrc2-taskbar'>
        <form id='sessionForm' name='sessionForm' class='hrc2-taskbar-start' method='post' action='<?php echo htmlspecialchars($refreshURL, ENT_QUOTES, 'UTF-8'); ?>'>
          <input type='hidden' name='Token1' value='<?php echo htmlspecialchars($Token1, ENT_QUOTES, 'UTF-8'); ?>'>
          <input type='hidden' name='Token2' value='<?php echo htmlspecialchars($Token2, ENT_QUOTES, 'UTF-8'); ?>'>
          <?php if ($taskbarOnFiles) { ?>
          <button type='submit' id='backButton' class='info-button hrc2-taskbar-icon' formaction='<?php echo htmlspecialchars($backURL, ENT_QUOTES, 'UTF-8'); ?>' title='<?php echo htmlspecialchars($Gui2Text167, ENT_QUOTES, 'UTF-8'); ?>' aria-label='<?php echo htmlspecialchars($Gui2Text167, ENT_QUOTES, 'UTF-8'); ?>'><span class='hrc2-taskbar-back'>&#x2190;</span></button>
          <?php } ?>
          <button type='submit' id='refreshButton' class='info-button hrc2-taskbar-icon' title='<?php echo htmlspecialchars($Gui1Text37, ENT_QUOTES, 'UTF-8'); ?>' aria-label='<?php echo htmlspecialchars($Gui1Text37, ENT_QUOTES, 'UTF-8'); ?>'>&#x21BB;</button>
        </form>
        <div class='hrc2-taskbar-end'>
          <?php if ($taskbarOffersWorkflows && $taskbarOnFiles) { ?>
          <button type='button' id='hrc2WorkflowsButton' name='hrc2WorkflowsButton' class='info-button hrc2-taskbar-text' aria-expanded='false' aria-controls='hrc2Workflows' onclick='toggle_visibility("hrc2Workflows");'><?php echo $Gui2Text128; ?></button>
          <?php } else if ($taskbarOffersWorkflows) { ?>
          <form id='hrc2WorkflowsForm' name='hrc2WorkflowsForm' class='hrc2-taskbar-form' method='post' action='<?php echo htmlspecialchars($taskbarWorkflowsURL, ENT_QUOTES, 'UTF-8'); ?>'>
            <input type='hidden' name='Token1' value='<?php echo htmlspecialchars($Token1, ENT_QUOTES, 'UTF-8'); ?>'>
            <input type='hidden' name='Token2' value='<?php echo htmlspecialchars($Token2, ENT_QUOTES, 'UTF-8'); ?>'>
            <button type='submit' id='hrc2WorkflowsButton' class='info-button hrc2-taskbar-text'><?php echo $Gui2Text128; ?></button>
          </form>
          <?php } if ($taskbarOffersSettings) { ?>
          <button type='button' id='userConfigButton' class='info-button hrc2-taskbar-icon' data-hrc2-panel='uiSelector' aria-expanded='false' aria-controls='uiSelector' title='<?php echo htmlspecialchars($GuiSelectorText4, ENT_QUOTES, 'UTF-8'); ?>' aria-label='<?php echo htmlspecialchars($GuiSelectorText4, ENT_QUOTES, 'UTF-8'); ?>'>&#9965;</button>
          <?php }
          // / The account's place in the bar. HRC2-SignIn.js binds the buttons in it, & writes in it why a sign in ended.
          // / A visitor where accounts are off is offered nothing, & the place is left out. ?>
          <?php if ($taskbarAccountsAreOn) { ?>
          <span id='hrc2SignInBar' class='hrc2-taskbar-account'>
            <?php if ($taskbarIsSignedIn) { ?>
            <button type='button' id='hrc2AccountButton' class='info-button hrc2-taskbar-text' data-hrc2-panel='hrc2AccountPanel' aria-expanded='false' aria-controls='hrc2AccountPanel' title='<?php echo htmlspecialchars($taskbarWho, ENT_QUOTES, 'UTF-8'); ?>' aria-label='<?php echo htmlspecialchars($taskbarWho, ENT_QUOTES, 'UTF-8'); ?>'><bdi class='hrc2-taskbar-name'><?php echo htmlspecialchars($taskbarUsername, ENT_QUOTES, 'UTF-8'); ?></bdi> &#9662;</button>
            <?php } else { ?>
            <button type='button' id='hrc2SignInLink' class='info-button hrc2-taskbar-text'><?php echo $GuiAuthText1; ?></button>
            <?php } ?>
          </span>
          <?php } ?>
        </div>
      </div>
      <?php if ($taskbarOffersSettings) {
            // / The settings panel holds a form of its own, so it stays OUTSIDE the bar's. HTML does not allow a form inside a form,
            // / & a browser silently drops the inner one, which would take every selector button with it.
            // / Start Over is the last thing in it, & only where there is something to clear. It asks before it deletes, & nothing deletes on the first press. ?>
      <div id='uiSelector' name='uiSelector' class='hrc2-panel hrc2-menu hrc2-taskbar-panel' style='display:none;'>
        <form id='uiSelectorForm' name='uiSelectorForm' method='post' action='<?php echo htmlspecialchars($selectorBase, ENT_QUOTES, 'UTF-8'); ?>'>
          <input type='hidden' name='Token1' value='<?php echo htmlspecialchars($Token1, ENT_QUOTES, 'UTF-8'); ?>'>
          <input type='hidden' name='Token2' value='<?php echo htmlspecialchars($Token2, ENT_QUOTES, 'UTF-8'); ?>'>
          <?php if ($AllowUserSelectableLanguage) { ?>
          <p class='hrc2-menu-label'><strong><?php echo $GuiSelectorText1; ?></strong></p>
          <p class='hrc2-menu-choices'>
            <?php foreach ($SupportedLanguages as $taskbarLanguage => $taskbarLabel) {
              redeclare($taskbarIsCurrent, ($taskbarLanguage === $LanguageToUse));
              redeclare($taskbarURL, htmlspecialchars($selectorBase.'language='.$taskbarLanguage.'&color='.$ColorToUse.'&gui='.$GuiToUse.$sortParams, ENT_QUOTES, 'UTF-8')); ?>
            <button type='submit' lang='<?php echo htmlspecialchars($taskbarLanguage, ENT_QUOTES, 'UTF-8'); ?>' class='hrc2-menu-choice<?php if ($taskbarIsCurrent) echo ' hrc2-menu-current'; ?>'
              formaction='<?php echo $taskbarURL; ?>'
              title='<?php echo htmlspecialchars($taskbarLabel, ENT_QUOTES, 'UTF-8'); ?>'
              <?php if ($taskbarIsCurrent) echo "aria-current='true'"; ?>><img src='<?php echo $GuiDir.'Languages/'.$taskbarLanguage.'/flag.png'; ?>' alt='<?php echo htmlspecialchars($taskbarLabel, ENT_QUOTES, 'UTF-8'); ?>'/></button>
            <?php } ?>
          </p>
          <?php } if ($AllowUserSelectableColor) { ?>
          <p class='hrc2-menu-label'><strong><?php echo $GuiSelectorText2; ?></strong></p>
          <p class='hrc2-menu-choices'>
            <?php foreach ($SupportedColors as $taskbarColor) {
              redeclare($taskbarSwatch, isset($taskbarSwatches[$taskbarColor]) ? $taskbarSwatches[$taskbarColor] : '#cccccc');
              redeclare($taskbarIsCurrent, (strtolower($taskbarColor) === strtolower($ColorToUse)));
              redeclare($taskbarURL, htmlspecialchars($selectorBase.'color='.$taskbarColor.'&language='.$LanguageToUse.'&gui='.$GuiToUse.$sortParams, ENT_QUOTES, 'UTF-8')); ?>
            <button type='submit' class='hrc2-menu-choice<?php if ($taskbarIsCurrent) echo ' hrc2-menu-current'; ?>'
              formaction='<?php echo $taskbarURL; ?>'
              title='<?php echo htmlspecialchars(ucfirst($taskbarColor), ENT_QUOTES, 'UTF-8'); ?>' aria-label='<?php echo htmlspecialchars(ucfirst($taskbarColor), ENT_QUOTES, 'UTF-8'); ?>'
              <?php if ($taskbarIsCurrent) echo "aria-current='true'"; ?>><span class='swatch' style='background-color:<?php echo $taskbarSwatch; ?>;'></span></button>
            <?php } ?>
          </p>
          <?php } if ($AllowUserSelectableGui) { ?>
          <p class='hrc2-menu-label'><strong><?php echo $GuiSelectorText3; ?></strong></p>
          <p class='hrc2-menu-choices'>
            <?php foreach ($SupportedGuis as $taskbarGui) {
              redeclare($taskbarIsCurrent, ($taskbarGui === $GuiToUse));
              redeclare($taskbarURL, htmlspecialchars($selectorBase.'gui='.$taskbarGui.'&language='.$LanguageToUse.'&color='.$ColorToUse.$sortParams, ENT_QUOTES, 'UTF-8')); ?>
            <button type='submit' class='hrc2-menu-choice hrc2-menu-word<?php if ($taskbarIsCurrent) echo ' hrc2-menu-current'; ?>'
              formaction='<?php echo $taskbarURL; ?>'
              title='<?php echo htmlspecialchars($taskbarGui, ENT_QUOTES, 'UTF-8'); ?>' aria-label='<?php echo htmlspecialchars($taskbarGui, ENT_QUOTES, 'UTF-8'); ?>'
              <?php if ($taskbarIsCurrent) echo "aria-current='true'"; ?>><?php echo htmlspecialchars($taskbarGui, ENT_QUOTES, 'UTF-8'); ?></button>
            <?php } ?>
          </p>
          <?php } ?>
        </form>
        <?php if ($FileCount > 0) { ?>
        <div id='startOverOptionsDiv' name='startOverOptionsDiv' class='hrc2-menu-section'>
          <p><strong><?php echo $Gui1Text35; ?></strong></p>
          <input type='submit' id='startOverConfirm' name='startOverConfirm' class='info-button' value='<?php echo htmlspecialchars($Gui1Text36, ENT_QUOTES, 'UTF-8'); ?>'>
        </div>
        <script type='text/javascript'>
          // / THE PAGE SUPPLIES VALUES. HRC2-Functions.js SUPPLIES BEHAVIOUR.
          // / startOver() lives in the script library. Everything handed to it here is something only PHP can know.
          if (typeof HRC2 === 'undefined' || typeof HRC2.configure !== 'function') {
            if (window.console && window.console.error) window.console.error('HRConvert2. HRC2-Functions.js did not load, or is an older copy without configure(). Start Over cannot run. Reload with a cleared cache.');
          } else {
            HRC2.configure(<?php echo json_encode(array(
              'tokens' => array('Token1' => (string)$Token1, 'Token2' => (string)$Token2),
              'sessionFiles' => array_values($Files),
              // / Refresh POSTS the tokens & keeps the session. Start Over NAVIGATES with no tokens, which is what makes the core issue a new one,
              // / & the address carries the interface, language & colour across, so only the session changes. It is the upload page's.
              'newSessionURL' => 'convertCore.php?'.$sessionParams.$sortParams,
              'operationFailedText' => $Gui2Text74)); ?>);
            HRC2.bindStartOver('startOverConfirm');
          }
        </script>
        <?php } ?>
      </div>
      <?php } if ($taskbarIsSignedIn) { ?>
      <div id='hrc2AccountPanel' name='hrc2AccountPanel' class='hrc2-panel hrc2-menu hrc2-taskbar-panel' style='display:none;'>
        <p><?php echo $GuiAuthText17; ?> <strong><bdi><?php echo htmlspecialchars($taskbarUsername, ENT_QUOTES, 'UTF-8'); ?></bdi></strong></p>
        <button type='button' id='hrc2SignOut' class='info-button'><?php echo $GuiAuthText2; ?></button>
      </div>
      <?php } ?>
      </div>
      <script type='text/javascript'>
        // / The panels the bar's buttons open are opened & closed by the script library, one at a time.
        if (typeof HRC2 !== 'undefined' && typeof HRC2.bindTaskbar === 'function') HRC2.bindTaskbar('hrc2Taskbar');
      </script>
<?php
// / Manually clean up sensitive memory. Helps to keep track of variable assignments.
// / An interface page is not a function, so its temporary variables are nulled & unset, as the other pages do.
$taskbarOnFiles = $taskbarAccount = $taskbarAccountsAreOn = $taskbarIsSignedIn = $taskbarUsername = $taskbarOffersWorkflows = $taskbarWorkflowsURL = $taskbarOffersSettings = $taskbarSwatches = $taskbarLanguage = $taskbarLabel = $taskbarColor = $taskbarSwatch = $taskbarGui = $taskbarURL = $taskbarWho = $taskbarIsCurrent = NULL;
unset($taskbarOnFiles, $taskbarAccount, $taskbarAccountsAreOn, $taskbarIsSignedIn, $taskbarUsername, $taskbarOffersWorkflows, $taskbarWorkflowsURL, $taskbarOffersSettings, $taskbarSwatches, $taskbarLanguage, $taskbarLabel, $taskbarColor, $taskbarSwatch, $taskbarGui, $taskbarURL, $taskbarWho, $taskbarIsCurrent);
?>
