    <?php
    // / -----------------------------------------------------------------------------------
    // / APPLICATION INFORMATION ...
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
    // / This file contains language specific GUI elements to be displayed at the bottom of pages.
    // / This file was created by Github user hernandito as part of his forked repo, available 
    // / at https://github.com/hernandito/HRConvert2/tree/master. Thank you, hernandito!
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
    $FooterDisplayed = TRUE;
    // / Check if the core is loaded.
    if (!isset($CoreLoaded)) die('ERROR!!! HRConvert2-2, This file cannot process your request! Please submit your file to convertCore.php instead!');
    // / -----------------------------------------------------------------------------------

    if ($ShowFinePrint) { ?>
    <div id='footer' name='footer' style='max-width:800px; margin-left:auto; margin-right:auto; text-align:center; font-size: 8px;'>
      <p><?php echo $GuiFooterText1; ?></a></p>
    </div>
  <?php }
    // / The sign in bar, where accounts are on. It names a signed in user & signs them out, & offers a visitor the sign in page.
    // / It is in the footer so every page carries it, & it starts the sign in library, which must be running before any request is sent.
    // / The page tells the library only whether it is signed in & how often to trade. The key itself is in the tab & never in a page.
    // / A bar with nothing to say is written with nothing inside it, not even a space, so it is empty & takes no room.
    // / It is moved to the top of the page, where a reader looks for it, & HRConvert2.css says how it is drawn.
    $footerSignIn = (isset($EnginePageState) && is_array($EnginePageState)) ? $EnginePageState : array();
    if (!empty($footerSignIn['Enabled'])) {
      $footerHome = 'convertCore.php?gui='.rawurlencode((string)$GuiToUse).'&language='.rawurlencode((string)$LanguageToUse).'&color='.rawurlencode((string)$ColorToUse);
      $footerOnSignInPage = (isset($EngineAnswer) && $EngineAnswer === 'page'); ?>
    <div id='hrc2SignInBar' class='hrc2-signin-bar'><?php if (!empty($footerSignIn['SignedIn'])) { ?>
      <span class='hrc2-signin-who'><?php echo $GuiAuthText17; ?> <strong><?php echo htmlspecialchars((string)$footerSignIn['Username'], ENT_QUOTES, 'UTF-8'); ?></strong></span>
      <button type='button' id='hrc2SignOut' class='info-button'><?php echo $GuiAuthText2; ?></button>
    <?php } else if (!$footerOnSignInPage) { ?>
      <button type='button' id='hrc2SignInLink' class='info-button'><?php echo $GuiAuthText1; ?></button>
    <?php } ?></div>
    <script type='text/javascript'>
      // / The bar is written last, in the one file every page shares, & shown first.
      document.body.insertBefore(document.getElementById('hrc2SignInBar'), document.body.firstChild);
      if (typeof HRC2SignIn !== 'undefined' && typeof HRC2SignIn.start === 'function') {
        HRC2SignIn.start(<?php echo json_encode(array('signedIn' => !empty($footerSignIn['SignedIn']), 'life' => (int)($footerSignIn['KeyLifetime'] ?? 30), 'interval' => (int)($footerSignIn['ExchangeInterval'] ?? 6),
          'tokens' => array('Token1' => (string)$Token1, 'Token2' => (string)$Token2), 'workerPath' => $GuiJSDir.'HRC2-SignInTimer.js', 'home' => $footerHome,
          'notices' => array('signed-out' => $GuiAuthText40, 'ended' => $GuiAuthText41)), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>);
      }
    </script>
    <?php }
    // / Manually clean up sensitive memory. Helps to keep track of variable assignments.
    $footerSignIn = $footerHome = $footerOnSignInPage = NULL;
    unset($footerSignIn, $footerHome, $footerOnSignInPage); ?>

  </body>
</html>