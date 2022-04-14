<?php 
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = 'تحويل أي شيء!'; 
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE; ?>
  <body>
    <?php 
    if (!isset($_GET['noGui'])) { ?>
    <div id="header-text" style="max-width:1000px; margin-left:auto; margin-right:auto; text-align:center;">
      <h1><?php echo $ApplicationName; ?></h1>
      <h3>محول الملفات على الإنترنت ، النازع ، الضاغط</h3>
      <hr />
    </div>
    <div id="main" align="center">
      <div id="overview" style="max-width:1000px; text-align:right; margin:25px;">
      	<p id="info" style="display:block;">يعتمد <?php echo $ApplicationName; ?> على تطبيق الويب مفتوح المصدر <a href='https://github.com/zelon88/HRConvert2'>HRConvert2</a> بواسطة <a href='https://github.com/zelon88'>Zelon88</a>. تم تصميمه لتحويل الملفات دون تتبع المستخدمين عبر الإنترنت أو التعدي على حقوق الملكية الفكرية الخاصة بك.</p>
        <button id="more-info-button" class="info-button" onclick="toggle_visibility('more-info'); toggle_visibility('more-info-button'); toggle_visibility('supported-formats-show-button'); 
          toggle_visibility('less-info-button');" style="text-align:center; display:block; margin-left:auto; margin-right:auto;"><i>مزيد من المعلومات...</i></button>
        <button id="less-info-button" class="info-button" onclick="toggle_visibility('more-info'); toggle_visibility('more-info-button'); toggle_visibility('supported-formats-show-button'); 
          toggle_visibility('less-info-button');" style="text-align:center; display:none; margin-left:auto; margin-right:auto;"><i>أقل معلومات...</i></button>
        <div id="more-info" style="display:none;">
          <hr />
          <p>يتم مسح جميع البيانات التي يوفرها المستخدم تلقائيًا ، لذلك لا داعي للقلق بشأن مصادرة معلوماتك الشخصية أو ممتلكاتك أثناء استخدام خدماتنا.</p>
          <p>يدعم <?php echo $ApplicationName; ?> حاليًا 75 تنسيقًا مختلفًا للملفات ، بما في ذلك المستندات وجداول البيانات والصور والوسائط والنماذج ثلاثية الأبعاد وملفات الرسم وملفات المتجهات والمحفوظات وصور القرص والمزيد.</p> 
          <button id="supported-formats-show-button" class="info-button" onclick="toggle_visibility('supported-formats'); toggle_visibility('supported-formats-show-button'); 
            toggle_visibility('supported-formats-hide-button');" style="text-align:center; display:none; margin-left:auto; margin-right:auto;"><i>عرض التنسيقات المدعومة...</i></button>
          <button id="supported-formats-hide-button" class="info-button" onclick="toggle_visibility('supported-formats'); toggle_visibility('supported-formats-show-button'); 
            toggle_visibility('supported-formats-hide-button');" style="text-align:center; display:none; margin-left:auto; margin-right:auto;"><i>إخفاء التنسيقات المدعومة...</i></button>
          <br>
          <div id="supported-formats" class="supported-formats" style="margin-left:33%; display:none;">
            <h3>التنسيقات المدعومة</h3>
            <hr />
            <strong>تنسيقات الصوت</strong>
            <p><i>يدعم معدل بت محدد من خلال واجهة برمجة التطبيق.</i></p>
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
            <strong>تنسيقات الفيديو</strong>
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
            <strong>تنسيقات المستندات</strong>
            <ol>
              <li>Doc</li>
              <li>Docx</li>
              <li>Txt</li>
              <li>Rtf</li>
              <li>Odt</li>
              <li>Pdf</li>
            </ol>
            <strong>تنسيقات جداول البيانات</strong>
            <ol>
              <li>Xls</li>
              <li>Xlsx</li>
              <li>Ods</li>
            </ol>
            <strong>تنسيقات العرض</strong>
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
            <strong>تنسيقات الأرشيف</strong>
              <p><i>يمكن إنشاء وتحويل والبحث في أي مما يلي...</i></p>
            <ol>
              <li>Zip</li>
              <li>Rar</li>
              <li>Tar</li>
              <li>Tar.Bz2</li>
              <li>7z</li>
            </ol>
            <strong>تنسيقات صورة القرص</strong>
            <p><i>يمكن استخراج أي مما يلي أو التحويل إلى تنسيقات أرشيف مدعومة...</i></p>
            <ol>
              <li>Iso</li>
              <li>Vhd</li>
              <li>Vdi</li>
            </ol>
            <strong>تنسيقات الصور</strong>
            <p><i>يدعم تغيير الحجم والتدوير عبر واجهة المستخدم الرسومية وواجهة برمجة التطبيقات.</i></p>
            <p><i>يدعم تعطيل الحفاظ على نسبة العرض إلى الارتفاع عبر واجهة برمجة التطبيق.</i></p>
            <p><i>تدعم عمليات تحويل الصور تنسيقات الإدخال التالية...</i></p>
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
            <p><i>تدعم عمليات تحويل الصور تنسيقات الإخراج التالية...</i></p>
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
            <strong>تنسيقات النماذج ثلاثية الأبعاد</strong>
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
            <strong>Drawing Formats</strong>
            <p><i>يمكن إخراج ملفات الرسم إلى تنسيقات الصور.</i></p>
            <p><i>يمكن التحويل بين أي مما يلي...</i></p>
            <ol>
              <li>Svg</li>
              <li>Dxf</li>
              <li>Fig</li>
              <li>Vdx</li>
            </ol>
            <strong>دعم التعرف البصري على الأحرف</strong>
            <p><i>تدعم عمليات التعرف الضوئي على الحروف تنسيقات الإدخال التالية...</i></p>
            <ol>
              <li>Jpg</li>
              <li>Jpeg</li>
              <li>Png</li>
              <li>Bmp</li>
              <li>Pdf</li>
              <li>Gif</li>
              </ol>
            <p><i>يدعم التعرّف البصري على الأحرف تنسيقات الإخراج التالية...</i></p>
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
          <p>حدد الملفات عن طريق النقر فوق الملفات أو الضغط عليها أو إسقاطها في المربع أدناه.</p>
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
            <input type="submit" id="continue-button" class="info-button" value="يكمل...">
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