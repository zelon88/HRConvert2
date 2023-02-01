    <?php 
    // / -----------------------------------------------------------------------------------
    // / APPLICATION INFORMATION ...
    // / HRConvert2, Copyright on 1/10/2023 by Justin Grimes, www.github.com/zelon88
    // /
    // / LICENSE INFORMATION ...
    // / This project is protected by the GNU GPLv3 Open-Source license.
    // / https://www.gnu.org/licenses/gpl-3.0.html
    // /
    // / APPLICATION INFORMATION ...
    // / This application is designed to provide a web-interface for converting file formats
    // / on a server for users of any web browser without authentication.
    // /
    // / FILE INFORMATION
    // / This file contains language specific GUI elements to be displayed at the bottom of pages.
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
    if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
    if ($ShowFinePrint) { ?>
    <div id="footer" name="footer" style="max-width:1000px; margin-left:auto; margin-right:auto; text-align:center;">
      <p>Lesen Sie unsere <a href="<?php echo $TOSURL; ?>" target="_blank" rel="noopener noreferrer">Nutzungsbedingungen</a>
       und <a href="<?php echo $PPURL; ?>" target="_blank" rel="noopener noreferrer">Datenschutzrichtlinie</a></p>
    </div>
  <?php } ?>

  </body>
</html>