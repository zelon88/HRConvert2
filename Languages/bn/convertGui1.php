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
// / v3.1.7.
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
if (!isset($ApplicationTitle)) $ApplicationTitle = 'কিছু রূপান্তর!'; 
if (!isset($CoreLoaded)) die('ত্রুটি!!! '.$ApplicationName.'-2, এই ফাইলটি আপনার অনুরোধ প্রক্রিয়া করতে পারে না! পরিবর্তে convertCore.php এ আপনার ফাইল জমা দিন!');
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
?>
  <body>
    <?php 
    if (!isset($_GET['noGui'])) { ?>
    <div id="header-text" style="max-width:1000px; margin-left:auto; margin-right:auto; text-align:center;">
      <h1><?php echo $ApplicationName; ?></h1>
      <h3>অনলাইন ফাইল কনভার্টার, এক্সট্র্যাক্টর, কম্প্রেসার</h3>
      <hr />
    </div>
    <div id="main" align="center">
      <div id="overview" style="max-width:1000px; text-align:left; margin:25px;">
      	<p id="info" style="display:block;"><a href='https://github.com/zelon88/HRConvert2'>HRConvert2</a> <a href='https://github.com/zelon88'>Zelon88</a> দ্বারা <?php echo $ApplicationName; ?> নামের ওপেন-সোর্স অ্যাপ্লিকেশনের উপর ভিত্তি করে তৈরি. এটি ব্যবহারকারীদের ট্র্যাকিং না করে বা আপনার গোপনীয়তার অধিকার লঙ্ঘন না করে ফাইলগুলিকে রূপান্তর করার জন্য ডিজাইন করা হয়েছিল৷</p>
        <button id="more-info-button" class="info-button" onclick="toggle_visibility('more-info'); toggle_visibility('more-info-button'); toggle_visibility('supported-formats-show-button'); 
          toggle_visibility('less-info-button');" style="text-align:center; display:block; margin-left:auto; margin-right:auto;"><i>অধিক তথ্য ...</i></button>
        <button id="less-info-button" class="info-button" onclick="toggle_visibility('more-info'); toggle_visibility('more-info-button'); toggle_visibility('supported-formats-show-button'); 
          toggle_visibility('less-info-button');" style="text-align:center; display:none; margin-left:auto; margin-right:auto;"><i>কম তথ্য ...</i></button>
        <div id="more-info" style="display:none;">
          <hr />
          <p>সমস্ত ব্যবহারকারীর সরবরাহকৃত ডেটা স্বয়ংক্রিয়ভাবে মুছে ফেলা হয়, তাই আমাদের পরিষেবাগুলি ব্যবহার করার সময় আপনার ব্যক্তিগত তথ্য বা সম্পত্তি বাজেয়াপ্ত করার বিষয়ে আপনাকে চিন্তা করতে হবে না।</p>
          <p>বর্তমানে <?php echo $ApplicationName; ?> নথি, স্প্রেডশীট, ছবি, মিডিয়া, ত্রিমাত্রিক মডেল, অঙ্কন ফাইল, ভেক্টর ফাইল, সংরক্ষণাগার, ডিস্ক চিত্র এবং আরও অনেক কিছু সহ 79টি ভিন্ন ফাইল ফর্ম্যাট সমর্থন করে।</p> 
          <button id="supported-formats-show-button" class="info-button" onclick="toggle_visibility('supported-formats'); toggle_visibility('supported-formats-show-button'); 
            toggle_visibility('supported-formats-hide-button');" style="text-align:center; display:none; margin-left:auto; margin-right:auto;"><i>সমর্থিত ফরম্যাট দেখুন ...</i></button>
          <button id="supported-formats-hide-button" class="info-button" onclick="toggle_visibility('supported-formats'); toggle_visibility('supported-formats-show-button'); 
            toggle_visibility('supported-formats-hide-button');" style="text-align:center; display:none; margin-left:auto; margin-right:auto;"><i>সমর্থিত বিন্যাস লুকান ...</i></button>
          <br>
          <div id="supported-formats" class="supported-formats" style="margin-left:33%; display:none;">
            <h3>সমর্থিত ফরম্যাট</h3>
            <hr />
            <strong>অডিও ফরম্যাট</strong>
            <p><i>নির্দিষ্ট বিটরেট সমর্থন করে।</i></p>
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
            <strong>ভিডিও ফরম্যাটs</strong>
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
            <strong>স্ট্রিম বিন্যাস</strong>
            <ol>
              <li>M3u8</li>
            </ol>
            <strong>নথি বিন্যাস</strong>
            <ol>
              <li>Doc</li>
              <li>Docx</li>
              <li>Txt</li>
              <li>Rtf</li>
              <li>Odt</li>
              <li>Pdf</li>
            </ol>
            <strong>স্প্রেডশীট বিন্যাস</strong>
            <ol>
              <li>Xls</li>
              <li>Xlsx</li>
              <li>Ods</li>
            </ol>
            <strong>উপস্থাপনা বিন্যাস</strong>
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
            <strong>সংরক্ষণাগার বিন্যাস</strong>
            <p><i>সংরক্ষণাগার বিন্যাস এবং ডিস্ক চিত্র বিন্যাস মধ্যে রূপান্তর করতে পারেন.</i></p>
            <ol>
              <li>Zip</li>
              <li>Rar</li>
              <li>Tar</li>
              <li>7z</li>
              <li>Iso</li>
            </ol>
            <strong>ইমেজ ফরম্যাট</strong>
            <p><i>নথির ছবি নথি বিন্যাসে রূপান্তর করতে পারেন.</i></p>
            <p><i>আকার পরিবর্তন এবং ঘোরানো সমর্থন করে।</i></p>
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
            <strong>মডেল ফরম্যাট</strong>
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
            <strong>অঙ্কন বিন্যাস</strong>
            <p><i>অঙ্কন ফাইলগুলিকে ইমেজ ফরম্যাটে রূপান্তর করতে পারে।</i></p>
            <ol>
              <li>Svg</li>
              <li>Dxf</li>
              <li>Fig</li>
              <li>Vdx</li>
              <li>Dia</li>
              <li>Wpg</li>
            </ol>
            <strong>অপটিক্যাল ক্যারেক্টার রিকগনিশন সাপোর্ট</strong>
            <p><i>অপটিক্যাল ক্যারেক্টার রিকগনিশন অপারেশন নিম্নলিখিত ইনপুট ফর্ম্যাটগুলিকে সমর্থন করে...</i></p>
            <ol>
              <li>Jpg</li>
              <li>Jpeg</li>
              <li>Png</li>
              <li>Bmp</li>
              <li>Pdf</li>
              <li>Gif</li>
              </ol>
            <p><i>অপটিক্যাল ক্যারেক্টার রিকগনিশন অপারেশন নিম্নলিখিত আউটপুট ফর্ম্যাটগুলিকে সমর্থন করে...</i></p>
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
          <p>নীচের বাক্সে ফাইলগুলিকে ক্লিক, আলতো চাপ বা ড্রপ করে ফাইলগুলি নির্বাচন করুন৷</p>
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
            <input type="submit" id="continue-button" class="info-button" value="চালিয়ে যান ...">
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