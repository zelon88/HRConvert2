<?php 
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = 'Converti Qualsiasi Cosa!'; 
if (!isset($CoreLoaded)) die('ERRORE!!! '.$ApplicationName.'-2, questo file non può elaborare la tua richiesta! Invia invece il tuo file a convertCore.php!');
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
?>
  <body>
    <?php 
    if (!isset($_GET['noGui'])) { ?>
    <div id="header-text" style="max-width:1000px; margin-left:auto; margin-right:auto; text-align:center;">
      <h1><?php echo $ApplicationName; ?></h1>
      <h3>Online File Converter, Extractor, Compressor</h3>
      <hr />
    </div>
    <div id="main" align="center">
      <div id="overview" style="max-width:1000px; text-align:left; margin:25px;">
      	<p id="info" style="display:block;"><?php echo $ApplicationName; ?> è basato sull'applicazione open source <a href='https://github.com/zelon88/HRConvert2'>HRConvert2</a> di <a href='https://github.com/zelon88'>Zelon88</a>. È progettato per convertire file senza tracciare gli utenti o violare la tua proprietà intellettuale.</p>
        <button id="more-info-button" class="info-button" onclick="toggle_visibility('more-info'); toggle_visibility('more-info-button'); toggle_visibility('supported-formats-show-button'); 
          toggle_visibility('less-info-button');" style="text-align:center; display:block; margin-left:auto; margin-right:auto;"><i>Maggiori Informazioni ...</i></button>
        <button id="less-info-button" class="info-button" onclick="toggle_visibility('more-info'); toggle_visibility('more-info-button'); toggle_visibility('supported-formats-show-button'); 
          toggle_visibility('less-info-button');" style="text-align:center; display:none; margin-left:auto; margin-right:auto;"><i>Meno Informazioni ...</i></button>
        <div id="more-info" style="display:none;">
          <hr />
          <p>Tutti i dati forniti dall'utente vengono cancellati automaticamente, quindi non devi preoccuparti di perdere le tue informazioni personali o la tua proprietà durante l'utilizzo dei nostri servizi.</p>
          <p>Attualmente <?php echo $ApplicationName; ?> supporta 75 formati di file diversi, inclusi documenti, fogli di calcolo, immagini, media, modelli tridimensionali, disegni tecnici, file vettoriali, archivi, immagini disco e altro.</p> 
          <button id="supported-formats-show-button" class="info-button" onclick="toggle_visibility('supported-formats'); toggle_visibility('supported-formats-show-button'); 
            toggle_visibility('supported-formats-hide-button');" style="text-align:center; display:none; margin-left:auto; margin-right:auto;"><i>Visualizza i Formati Supportati ...</i></button>
          <button id="supported-formats-hide-button" class="info-button" onclick="toggle_visibility('supported-formats'); toggle_visibility('supported-formats-show-button'); 
            toggle_visibility('supported-formats-hide-button');" style="text-align:center; display:none; margin-left:auto; margin-right:auto;"><i>Nascondi Formati Supportati ...</i></button>
          <br>
          <div id="supported-formats" class="supported-formats" style="margin-left:33%; display:none;">
            <h3>Formati Supportati</h3>
            <hr />
            <strong>Formati di Audio</strong>
            <p><i>Supporta bitrate specifico tramite l'interfaccia di programmazione dell'applicazione.</i></p>
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
            <strong>Formati di Video</strong>
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
            <strong>Formati di Documenti</strong>
            <ol>
              <li>Doc</li>
              <li>Docx</li>
              <li>Txt</li>
              <li>Rtf</li>
              <li>Odt</li>
              <li>Pdf</li>
            </ol>
            <strong>Formati di Fogli di Calcolo</strong>
            <ol>
              <li>Xls</li>
              <li>Xlsx</li>
              <li>Ods</li>
            </ol>
            <strong>Formati di Presentazione</strong>
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
            <strong>Formati di Archivio</strong>
              <p><i>Può creare, convertire e dearchiviare qualsiasi dei seguenti elementi...</i></p>
            <ol>
              <li>Zip</li>
              <li>Rar</li>
              <li>Tar</li>
              <li>Tar.Bz2</li>
              <li>7z</li>
            </ol>
            <strong>Formati dell'Immagine del Disco</strong>
            <p><i>Può estrarre uno qualsiasi dei seguenti o convertire in formati di archivio supportati...</i></p>
            <ol>
              <li>Iso</li>
              <li>Vhd</li>
              <li>Vdi</li>
            </ol>
            <strong>Formati di Immagini</strong>
            <p><i>Supporta il ridimensionamento e la rotazione tramite l'interfaccia utente grafica e l'interfaccia di programmazione dell'applicazione.</i></p>
            <p><i>Supporta disabilitare il mantenimento delle proporzioni tramite l'interfaccia di programmazione dell'applicazione.</i></p>
            <p><i>Le operazioni di conversione delle immagini supportano i seguenti formati di input...</i></p>
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
            <p><i>Le operazioni di conversione delle immagini supportano i seguenti formati di output...</i></p>
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
            <strong>Formati di Modelli Tridimensionali</strong>
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
            <strong>Formati di Disegno</strong>
            <p><i>Can output drawing files to image formats.</i></p>
            <p><i>Can convert between any of the following...</i></p>
            <ol>
              <li>Svg</li>
              <li>Dxf</li>
              <li>Fig</li>
              <li>Vdx</li>
            </ol>
            <strong>Supporto al Riconoscimento Ottico dei Caratteri</strong>
            <p><i>Le operazioni di riconoscimento ottico dei caratteri supportano i seguenti formati di input...</i></p>
            <ol>
              <li>Jpg</li>
              <li>Jpeg</li>
              <li>Png</li>
              <li>Bmp</li>
              <li>Pdf</li>
              <li>Gif</li>
              </ol>
            <p><i>Le operazioni di riconoscimento ottico dei caratteri supportano i seguenti formati di output...</i></p>
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
          <p>Seleziona i file facendo clic, toccando o rilasciando i file nella casella sottostante.</p>
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
            <input type="submit" id="continue-button" class="info-button" value="Continua ...">
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