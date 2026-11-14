<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRConvert2, Copyright on 11/13/2026 by Justin Grimes, www.github.com/zelon88
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
// / v4.2.0.
// / This file contains language specific GUI elements for the page the Engine names, which is signing in, in the Original interface.
// / It is drawn in place of the upload page & the file list, between the same header & footer, when the Engine answers a request with a page.
// / It shows the form the Engine named & holds the others, & HRC2-SignIn.js switches between them & sends each with a script,
// / so a key comes back to the page that asked for it & never travels in a page.
// / Everything it shows comes from $EnginePageState & the language pack, & it reads no request itself.
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
// / What the Engine says about this request. A missing value reads as the most careful one.
$enginePage = (isset($EnginePageState) && is_array($EnginePageState)) ? $EnginePageState : array();
$enginePageName = (isset($enginePage['Page']) && in_array($enginePage['Page'], array('signin', 'register', 'confirm', 'recover', 'reset'), TRUE)) ? $enginePage['Page'] : 'signin';
$engineAvailable = !empty($enginePage['Available']);
$engineMayRegister = !empty($enginePage['AllowRegistration']);
$engineMayEmail = !empty($enginePage['EmailAvailable']);
$engineMayStayAnonymous = !empty($enginePage['AllowAnonymous']);
// / The page a user goes to once signed in is the upload page, in the interface, language & colour they chose.
$engineHome = 'convertCore.php?gui='.rawurlencode((string)$GuiToUse).'&language='.rawurlencode((string)$LanguageToUse).'&color='.rawurlencode((string)$ColorToUse);
// / Every outcome the Engine may answer with, & what the page says for it.
$engineMessages = array(
  'credentials-wrong' => $GuiAuthText22, 'too-many' => $GuiAuthText23, 'unconfirmed' => $GuiAuthText24, 'disabled' => $GuiAuthText25,
  'unavailable' => $GuiAuthText18, 'registration-closed' => $GuiAuthText26, 'username-invalid' => $GuiAuthText27, 'username-taken' => $GuiAuthText28,
  'email-invalid' => $GuiAuthText29, 'password-short' => $GuiAuthText30.' '.(int)($enginePage['MinimumPasswordLength'] ?? 12), 'registered-confirm' => $GuiAuthText31,
  'registered-unsent' => $GuiAuthText32, 'registered-ready' => $GuiAuthText33, 'code-wrong' => $GuiAuthText34, 'code-expired' => $GuiAuthText35, 'confirmed' => $GuiAuthText36,
  'resent' => $GuiAuthText37, 'recovery-sent' => $GuiAuthText38, 'reset-done' => $GuiAuthText39, 'signed-out' => $GuiAuthText40, 'ended' => $GuiAuthText41, 'no-answer' => $GuiAuthText42);
// / -----------------------------------------------------------------------------------
?>
  <body>
    <div class='file-list-card hrc2-signin-card'>
      <p id='signInMessage' class='hrc2-signin-message' role='status' aria-live='polite' hidden></p>
      <?php if (!$engineAvailable) { ?>
      <h2><?php echo $GuiAuthText1; ?></h2>
      <p><?php echo $GuiAuthText18; ?></p>
      <?php } else { ?>
      <section data-hrc2-page='signin'>
        <h2><?php echo $GuiAuthText1; ?></h2>
        <form data-hrc2-action='signin' action='convertCore.php' method='post' class='hrc2-signin-form'>
          <label><?php echo $GuiAuthText4; ?><input type='text' name='SignInUsername' autocomplete='username' maxlength='64' required dir='ltr'></label>
          <label><?php echo $GuiAuthText5; ?><input type='password' name='SignInPassword' autocomplete='current-password' maxlength='1024' required></label>
          <button type='submit' class='info-button'><?php echo $GuiAuthText1; ?></button>
        </form>
        <p class='hrc2-signin-links'>
          <?php if ($engineMayRegister) { ?><a href='#' data-hrc2-show='register'><?php echo $GuiAuthText3; ?></a><?php } ?>
          <?php if ($engineMayEmail) { ?><a href='#' data-hrc2-show='recover'><?php echo $GuiAuthText13; ?></a><?php } ?>
          <?php if ($engineMayRegister) { ?><a href='#' data-hrc2-show='confirm'><?php echo $GuiAuthText14; ?></a><?php } ?>
          <?php if ($engineMayStayAnonymous) { ?><a href='#' data-hrc2-home='1'><?php echo $GuiAuthText16; ?></a><?php } ?>
        </p>
      </section>
      <?php if ($engineMayRegister) { ?>
      <section data-hrc2-page='register' hidden>
        <h2><?php echo $GuiAuthText3; ?></h2>
        <form data-hrc2-action='register' action='convertCore.php' method='post' class='hrc2-signin-form'>
          <label><?php echo $GuiAuthText4; ?><input type='text' name='SignInUsername' autocomplete='username' maxlength='64' required dir='ltr'></label>
          <label><?php echo $GuiAuthText6; ?><input type='email' name='SignInEmail' autocomplete='email' maxlength='254' required dir='ltr'></label>
          <label><?php echo $GuiAuthText5; ?><input type='password' name='SignInPassword' autocomplete='new-password' minlength='<?php echo (int)($enginePage['MinimumPasswordLength'] ?? 12); ?>' maxlength='1024' required></label>
          <button type='submit' class='info-button'><?php echo $GuiAuthText3; ?></button>
        </form>
        <p class='hrc2-signin-links'><a href='#' data-hrc2-show='signin'><?php echo $GuiAuthText15; ?></a></p>
      </section>
      <section data-hrc2-page='confirm' hidden>
        <h2><?php echo $GuiAuthText9; ?></h2>
        <form data-hrc2-action='confirm' action='convertCore.php' method='post' class='hrc2-signin-form'>
          <label><?php echo $GuiAuthText4; ?><input type='text' name='SignInUsername' autocomplete='username' maxlength='64' required dir='ltr'></label>
          <label><?php echo $GuiAuthText7; ?><input type='text' name='SignInCode' autocomplete='one-time-code' maxlength='10' required dir='ltr' style='text-transform:uppercase;'></label>
          <button type='submit' class='info-button'><?php echo $GuiAuthText19; ?></button>
        </form>
        <form data-hrc2-action='resend' action='convertCore.php' method='post' class='hrc2-signin-form hrc2-signin-again'>
          <input type='hidden' name='SignInUsername'>
          <button type='submit' class='info-button'><?php echo $GuiAuthText12; ?></button>
        </form>
        <p class='hrc2-signin-links'><a href='#' data-hrc2-show='signin'><?php echo $GuiAuthText15; ?></a></p>
      </section>
      <?php } ?>
      <?php if ($engineMayEmail) { ?>
      <section data-hrc2-page='recover' hidden>
        <h2><?php echo $GuiAuthText10; ?></h2>
        <form data-hrc2-action='recover' action='convertCore.php' method='post' class='hrc2-signin-form'>
          <label><?php echo $GuiAuthText4; ?><input type='text' name='SignInUsername' autocomplete='username' maxlength='64' required dir='ltr'></label>
          <button type='submit' class='info-button'><?php echo $GuiAuthText20; ?></button>
        </form>
        <p class='hrc2-signin-links'><a href='#' data-hrc2-show='reset'><?php echo $GuiAuthText14; ?></a> <a href='#' data-hrc2-show='signin'><?php echo $GuiAuthText15; ?></a></p>
      </section>
      <section data-hrc2-page='reset' hidden>
        <h2><?php echo $GuiAuthText11; ?></h2>
        <form data-hrc2-action='reset' action='convertCore.php' method='post' class='hrc2-signin-form'>
          <label><?php echo $GuiAuthText4; ?><input type='text' name='SignInUsername' autocomplete='username' maxlength='64' required dir='ltr'></label>
          <label><?php echo $GuiAuthText7; ?><input type='text' name='SignInCode' autocomplete='one-time-code' maxlength='10' required dir='ltr' style='text-transform:uppercase;'></label>
          <label><?php echo $GuiAuthText8; ?><input type='password' name='SignInPassword' autocomplete='new-password' minlength='<?php echo (int)($enginePage['MinimumPasswordLength'] ?? 12); ?>' maxlength='1024' required></label>
          <button type='submit' class='info-button'><?php echo $GuiAuthText21; ?></button>
        </form>
        <p class='hrc2-signin-links'><a href='#' data-hrc2-show='signin'><?php echo $GuiAuthText15; ?></a></p>
      </section>
      <?php } else { ?>
      <section data-hrc2-page='recover' hidden><h2><?php echo $GuiAuthText10; ?></h2><p><?php echo $GuiAuthText43; ?></p><p class='hrc2-signin-links'><a href='#' data-hrc2-show='signin'><?php echo $GuiAuthText15; ?></a></p></section>
      <section data-hrc2-page='reset' hidden><h2><?php echo $GuiAuthText11; ?></h2><p><?php echo $GuiAuthText43; ?></p><p class='hrc2-signin-links'><a href='#' data-hrc2-show='signin'><?php echo $GuiAuthText15; ?></a></p></section>
      <?php } ?>
      <?php } ?>
    </div>
    <script type='text/javascript'>
      // / THE PAGE SUPPLIES VALUES. HRC2-SignIn.js SUPPLIES BEHAVIOUR.
      if (typeof HRC2SignInPage === 'undefined' || typeof HRC2SignInPage.configure !== 'function') {
        if (window.console && window.console.error) window.console.error('HRConvert2. HRC2-SignIn.js did not load. Signing in cannot run. Reload with a cleared cache.');
      } else {
        HRC2SignInPage.configure(<?php echo json_encode(array('page' => $enginePageName, 'home' => $engineHome, 'tokens' => array('Token1' => (string)$Token1, 'Token2' => (string)$Token2), 'messages' => $engineMessages), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>);
      }
    </script>
<?php
// / Manually clean up sensitive memory. Helps to keep track of variable assignments.
// / An interface page is not a function, so its temporary variables are nulled & unset, as the other pages do.
$enginePage = $enginePageName = $engineAvailable = $engineMayRegister = $engineMayEmail = $engineMayStayAnonymous = $engineHome = $engineMessages = NULL;
unset($enginePage, $enginePageName, $engineAvailable, $engineMayRegister, $engineMayEmail, $engineMayStayAnonymous, $engineHome, $engineMessages);
?>
