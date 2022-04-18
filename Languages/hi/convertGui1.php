<?php 
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = 'कुछ भी कनवर्ट करें!'; 
if (!isset($CoreLoaded)) die('त्रुटि!!! '.$ApplicationName.'-2, यह फ़ाइल आपके अनुरोध को संसाधित नहीं कर सकती है! इसके बजाय कृपया अपनी फ़ाइल ConvertCore.php पर सबमिट करें!');
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
?>
  <body>
    <?php 
    if (!isset($_GET['noGui'])) { ?>
    <div id="header-text" style="max-width:1000px; margin-left:auto; margin-right:auto; text-align:center;">
      <h1><?php echo $ApplicationName; ?></h1>
      <h3>ऑनलाइन फाइल कन्वर्टर, एक्सट्रैक्टर, कंप्रेसर</h3>
      <hr />
    </div>
    <div id="main" align="center">
      <div id="overview" style="max-width:1000px; text-align:left; margin:25px;">
      	<p id="info" style="display:block;"><a href='https://github.com/zelon88/HRConvert2'>HRConvert2</a> <a href='https://github.com/zelon88'>Zelon88</a> द्वारा <?php echo $ApplicationName; ?> पर आधारित है। यह उपयोगकर्ताओं को ट्रैक किए बिना या आपकी बौद्धिक संपदा का उल्लंघन किए बिना फ़ाइलों को परिवर्तित करने के लिए बनाया गया था।</p>
        <button id="more-info-button" class="info-button" onclick="toggle_visibility('more-info'); toggle_visibility('more-info-button'); toggle_visibility('supported-formats-show-button'); 
          toggle_visibility('less-info-button');" style="text-align:center; display:block; margin-left:auto; margin-right:auto;"><i>और जानकारी ...</i></button>
        <button id="less-info-button" class="info-button" onclick="toggle_visibility('more-info'); toggle_visibility('more-info-button'); toggle_visibility('supported-formats-show-button'); 
          toggle_visibility('less-info-button');" style="text-align:center; display:none; margin-left:auto; margin-right:auto;"><i>कम जानकारी ...</i></button>
        <div id="more-info" style="display:none;">
          <hr />
          <p>सभी उपयोगकर्ता द्वारा प्रदत्त डेटा स्वचालित रूप से मिटा दिया जाता है, इसलिए आपको हमारी सेवाओं का उपयोग करते समय अपनी व्यक्तिगत जानकारी या संपत्ति को जब्त करने के बारे में चिंता करने की आवश्यकता नहीं है।</p>
          <p><?php echo $ApplicationName; ?> 75 विभिन्न फ़ाइल स्वरूपों का समर्थन करता है, जिसमें दस्तावेज़, स्प्रेडशीट, चित्र, मीडिया, तीन आयामी मॉडल, ड्राइंग फ़ाइलें, वेक्टर फ़ाइलें, संग्रह, डिस्क चित्र, और बहुत कुछ शामिल हैं।</p> 
          <button id="supported-formats-show-button" class="info-button" onclick="toggle_visibility('supported-formats'); toggle_visibility('supported-formats-show-button'); 
            toggle_visibility('supported-formats-hide-button');" style="text-align:center; display:none; margin-left:auto; margin-right:auto;"><i>समर्थित प्रारूप देखें ...</i></button>
          <button id="supported-formats-hide-button" class="info-button" onclick="toggle_visibility('supported-formats'); toggle_visibility('supported-formats-show-button'); 
            toggle_visibility('supported-formats-hide-button');" style="text-align:center; display:none; margin-left:auto; margin-right:auto;"><i>समर्थित प्रारूप छुपाएं ...</i></button>
          <br>
          <div id="supported-formats" class="supported-formats" style="margin-left:33%; display:none;">
            <h3>समर्थित प्रारूप</h3>
            <hr />
            <strong>ऑडियो प्रारूप</strong>
            <p><i>एपीआई के माध्यम से विशिष्ट बिटरेट का समर्थन करता है।</i></p>
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
            <strong>वीडियो प्रारूप</strong>
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
            <strong>दस्तावेज़ प्रारूप</strong>
            <ol>
              <li>Doc</li>
              <li>Docx</li>
              <li>Txt</li>
              <li>Rtf</li>
              <li>Odt</li>
              <li>Pdf</li>
            </ol>
            <strong>स्प्रेडशीट प्रारूप</strong>
            <ol>
              <li>Xls</li>
              <li>Xlsx</li>
              <li>Ods</li>
            </ol>
            <strong>प्रस्तुति प्रारूप</strong>
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
            <strong>पुरालेख प्रारूप</strong>
              <p><i>निम्न में से कोई भी बना, रूपांतरित और अचयनित कर सकता है...</i></p>
            <ol>
              <li>Zip</li>
              <li>Rar</li>
              <li>Tar</li>
              <li>Tar.Bz2</li>
              <li>7z</li>
            </ol>
            <strong>डिस्क छवि प्रारूप</strong>
            <p><i>निम्न में से किसी को भी निकाल सकते हैं या समर्थित संग्रह प्रारूपों में परिवर्तित कर सकते हैं...</i></p>
            <ol>
              <li>Iso</li>
              <li>Vhd</li>
              <li>Vdi</li>
            </ol>
            <strong>छवि प्रारूप</strong>
            <p><i>जीयूआई और एपीआई के माध्यम से आकार बदलने और घुमाने का समर्थन करता है।</i></p>
            <p><i>एपीआई के माध्यम से पहलू अनुपात को बनाए रखने में अक्षम का समर्थन करता है।</i></p>
            <p><i>छवि रूपांतरण संचालन निम्नलिखित इनपुट स्वरूपों का समर्थन करता है...</i></p>
            <ol>
              <li>Jpg</li>
              <li>Jpeg</li>
              <li>Png</li>
              <li>Bmp</li>
              <li>Webp</li>
              <li>Gif</li>
              <li>Cin</li>
              <li>Dds</li>
              <li>Dib</li>
              <li>Flif</li>
              <li>Avif</li>
              <li>Crw</li>
              <li>Dcr</li>
              <li>Gplt</li>
              <li>Nef</li>
              <li>Orf</li>
              <li>Ora</li>
              <li>Sct</li>
              <li>Sfw</li>
              <li>Xcf</li>
              <li>Xwd</li>
              <li>Avif</li>
              <li>Ico</li>
            </ol>   
            <p><i>छवि रूपांतरण संचालन निम्नलिखित आउटपुट स्वरूपों का समर्थन करता है...</i></p>
            <ol>
              <li>Jpg</li>
              <li>Jpeg</li>
              <li>Png</li>
              <li>Bmp</li>
              <li>Webp</li>
              <li>Cin</li>
              <li>Dds</li>
              <li>Dib</li>
              <li>Flif</li>
              <li>Avif</li>
            </ol>
            <strong>तीन आयामी मॉडल प्रारूप</strong>
            <ol>
              <li>3ds</li>
              <li>Obj</li>
              <li>Collada</li>
              <li>Off</li>
              <li>Ply</li>
              <li>Stl</li>
              <li>Ptx</li>
              <li>Dxf</li>
              <li>U3d</li>
              <li>Vrml</li>
            </ol>
            <strong>ड्राइंग प्रारूप</strong>
            <p><i>छवि प्रारूपों में ड्राइंग फ़ाइलों को आउटपुट कर सकते हैं।</i></p>
            <p><i>निम्नलिखित में से किसी के बीच कनवर्ट कर सकते हैं...</i></p>
            <ol>
              <li>Svg</li>
              <li>Dxf</li>
              <li>Fig</li>
              <li>Vdx</li>
            </ol>
            <strong>ऑप्टिकल कैरेक्टर रिकग्निशन सपोर्ट</strong>
            <p><i>ऑप्टिकल कैरेक्टर रिकग्निशन ऑपरेशंस निम्नलिखित इनपुट स्वरूपों का समर्थन करते हैं:...</i></p>
            <ol>
              <li>Jpg</li>
              <li>Jpeg</li>
              <li>Png</li>
              <li>Bmp</li>
              <li>Pdf</li>
              <li>Gif</li>
              </ol>
            <p><i>ऑप्टिकल कैरेक्टर रिकग्निशन ऑपरेशंस निम्नलिखित आउटपुट स्वरूपों का समर्थन करते हैं:...</i></p>
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
          <p>नीचे दिए गए बॉक्स में फ़ाइलों को क्लिक करके, टैप करके या छोड़ कर फ़ाइलें चुनें।</p>
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
            <input type="submit" id="continue-button" class="info-button" value="जारी रखें ...">
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