<?php 
// / -----------------------------------------------------------------------------------
// / APPLICATION INFORMATION ...
// / HRConvert2, Copyright on 2/21/2023 by Justin Grimes, www.github.com/zelon88
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
// / v3.1.9.1.
// / This file contains language specific GUI elements for accepting file uploads.
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
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = 'Перетворюйте будь-що!'; 
if (!isset($CoreLoaded)) die('ПОМИЛКА!!! '.$ApplicationName.'-2, цей файл не може обробити ваш запит! Натомість надішліть свій файл у convertCore.php!');
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
?>
  <body>
    <?php 
    if (!isset($_GET['noGui'])) { ?>
    <div id="header-text" style="max-width:1000px; margin-left:auto; margin-right:auto; text-align:center;">
      <h1><?php echo $ApplicationName; ?></h1>
      <h3>Онлайн-конвертер файлів, екстрактор, компресор</h3>
      <hr />
    </div>
    <div id="main" align="center">
      <div id="overview" style="max-width:1000px; text-align:left; margin:25px;">
      	<p id="info" style="display:block;"><?php echo $ApplicationName; ?> заснований на веб-додатку <a href='https://github.com/zelon88/HRConvert2'>HRConvert2</a> з відкритим вихідним кодом від <a href='https://github.com/zelon88'>Zelon88</a>. Він був розроблений для перетворення файлів без відстеження користувачів в Інтернеті або порушення прав на вашу інтелектуальну власність.</p>
        <button id="more-info-button" class="info-button" onclick="toggle_visibility('more-info'); toggle_visibility('more-info-button'); toggle_visibility('supported-formats-show-button'); 
          toggle_visibility('less-info-button');" style="text-align:center; display:block; margin-left:auto; margin-right:auto;"><i>Більше інформації...</i></button>
        <button id="less-info-button" class="info-button" onclick="toggle_visibility('more-info'); toggle_visibility('more-info-button'); toggle_visibility('supported-formats-show-button'); 
          toggle_visibility('less-info-button');" style="text-align:center; display:none; margin-left:auto; margin-right:auto;"><i>Менше інформації...</i></button>
        <div id="more-info" style="display:none;">
          <hr />
          <p>Усі дані, надані користувачами, стираються автоматично, тому вам не потрібно турбуватися про втрату вашої особистої інформації чи майна під час використання наших послуг.</p>
          <p><?php echo $ApplicationName; ?> HRConvert2 підтримує 79 різних форматів файлів, включаючи документи, електронні таблиці, зображення, медіа, тривимірні моделі, файли малюнків, векторні файли, архіви, образи дисків тощо.</p> 
          <button id="supported-formats-show-button" class="info-button" onclick="toggle_visibility('supported-formats'); toggle_visibility('supported-formats-show-button'); 
            toggle_visibility('supported-formats-hide-button');" style="text-align:center; display:none; margin-left:auto; margin-right:auto;"><i>Переглянути підтримувані формати...</i></button>
          <button id="supported-formats-hide-button" class="info-button" onclick="toggle_visibility('supported-formats'); toggle_visibility('supported-formats-show-button'); 
            toggle_visibility('supported-formats-hide-button');" style="text-align:center; display:none; margin-left:auto; margin-right:auto;"><i>Приховати підтримувані формати...</i></button>
          <br>
          <div id="supported-formats" class="supported-formats" style="margin-left:33%; display:none;">
            <h3>Підтримувані формати</h3>
            <hr />
            <strong>Аудіоформати</strong>
            <p><i>Підтримує певний бітрейт.</i></p>
            <ol>
              <li>Mp2</li>
              <li>Mp3</li>
              <li>Avi</li>
              <li>Flac</li>
              <li>Ogg</li>
              <li>Wav</li>
              <li>Wma</li>
              <li>M4a</li>
              <li>M4p</li>
            </ol>
            <strong>Відео формати</strong>
            <ol>
              <li>3gp</li>
              <li>Mkv</li>
              <li>Avi</li>
              <li>Mp4</li>
              <li>Flv</li>
              <li>Mpeg</li>
              <li>Wmv</li>
              <li>Mov</li>
              <li>M4v</li>
            </ol>
            <strong>Формати потоку</strong>
            <ol>
              <li>M3u8</li>
            </ol>
            <strong>Формати документів</strong>
            <ol>
              <li>Doc</li>
              <li>Docx</li>
              <li>Txt</li>
              <li>Rtf</li>
              <li>Odt</li>
              <li>Pdf</li>
            </ol>
            <strong>Формати електронних таблиць</strong>
            <ol>
              <li>Xls</li>
              <li>Xlsx</li>
              <li>Ods</li>
              <li>Csv</li>
              <li>Pdf</li>
            </ol>
            <strong>Формати презентації</strong>
            <ol>
              <li>Pages</li>
              <li>Pptx</li>
              <li>Ppt</li>
              <li>Xps</li>
              <li>Pot</li>
              <li>Potx</li>
              <li>Ppa</li>
              <li>Ppt</li>
              <li>Pptx</li>
              <li>Odp</li>
            </ol>
            <strong>Архівні формати</strong>
            <p><i>Може конвертувати між форматами архівів і форматами образів дисків.</i></p>
            <ol>
              <li>Zip</li>
              <li>Rar</li>
              <li>Tar</li>
              <li>7z</li>
              <li>Iso</li>
            </ol>
            <strong>Формати зображень</strong>
            <p><i>Може конвертувати зображення документів у формати документів.</i></p>
            <p><i>Підтримує зміну розміру та поворот.</i></p>
            <ol>
              <li>Jpg</li>
              <li>Jpeg</li>
              <li>Png</li>
              <li>Bmp</li>
              <li>Pdf</li>
              <li>Gif</li>
              <li>Webp</li>
              <li>Cin</li>
              <li>Dds</li>
              <li>Dib</li>
              <li>Flif</li>
              <li>Avif</li>
              <li>Gplt</li>
              <li>Sct</li>
              <li>Xcf</li>
              <li>Heic</li>
              <li>Ico</li>
            </ol>
            <strong>Тривимірні формати моделей</strong>
            <ol>
              <li>3ds</li>
              <li>Obj</li>
              <li>Collada</li>
              <li>Off</li>
              <li>Ply</li>
              <li>Stl</li>
              <li>Gts</li>
              <li>Dxf</li>
              <li>U3d</li>
              <li>X3d</li>
              <li>Vrml</li>
            </ol>
            <strong>Формати малювання</strong>
            <p><i>Може конвертувати файли малюнків у формати зображень.</i></p>
            <ol>
              <li>Svg</li>
              <li>Dxf</li>
              <li>Fig</li>
              <li>Vdx</li>
              <li>Dia</li>
              <li>Wpg</li>
            </ol>
            <strong>Підтримка оптичного розпізнавання символів</strong>
            <p><i>Операції оптичного розпізнавання символів підтримують такі формати введення...</i></p>
            <ol>
              <li>Jpg</li>
              <li>Jpeg</li>
              <li>Png</li>
              <li>Bmp</li>
              <li>Pdf</li>
              <li>Gif</li>
              </ol>
            <p><i>Операції оптичного розпізнавання символів підтримують такі вихідні формати...</i></p>
            <ol>
              <li>Doc</li>
              <li>Docx</li>
              <li>Txt</li>
              <li>Rtf</li>
              <li>Odt</li>
              <li>Pdf</li>
            </ol>
          </div>
        </div>
        <hr />
      </div>
      <?php } ?>
      <div align="center">
        <div id="call-to-action1" style="max-width:1000px; text-align:center;">
          <p>Виберіть файли, клацнувши, торкнувшись або опустивши файли в поле нижче.</p>
        </div>
      </div>
      <div align="center">
        <div id="dropzone" style="max-height:1000px; max-width:1000px; margin:25px;">
          <form action="convertCore.php" class="dropzone" id="filesToUpload" name="filesToUpload" method="post" enctype="multipart/form-data">
          <input type="hidden" id="token1" name="Token1" value="<?php echo $Token1; ?>">
          <input type="hidden" id="token2" name="Token2" value="<?php echo $Token2; ?>">
          </form>
        </div>
      </div>
      <div align="center">
        <div id="continue" style="max-width:1000px; text-align:center;">
          <form action="convertCore.php?showFiles=1<?php if (isset($_GET['noGui'])) echo '&noGui=TRUE'; if (isset($_GET['language'])) echo '&language='.$_GET['language']; ?>" method="post">
            <input type="hidden" id="token1" name="Token1" value="<?php echo $Token1; ?>">
            <input type="hidden" id="token2" name="Token2" value="<?php echo $Token2; ?>">
            <input type="submit" id="continue-button" class="info-button" value="Продовжуйте...">
          </form>
          <br />
          <?php if (!isset($_GET['noGui'])) { ?>
          <hr />
          <?php } ?>
        </div>
      </div>

    <?php if (!isset($_GET['noGui'])) { ?>
    </div>
    <?php } ?>