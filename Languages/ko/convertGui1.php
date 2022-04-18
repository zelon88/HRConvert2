<?php 
if (!isset($ApplicationName)) $ApplicationName = 'HRConvert2'; 
if (!isset($ApplicationTitle)) $ApplicationTitle = '무엇이든 변환하십시오!'; 
if (!isset($CoreLoaded)) die('ERROR!!! '.$ApplicationName.'-2, This file cannot process your request! Please submit your file to convertCore.php instead!');
if (!isset($ShowFinePrint)) $ShowFinePrint = TRUE;
?>
  <body>
    <?php 
    if (!isset($_GET['noGui'])) { ?>
    <div id="header-text" style="max-width:1000px; margin-left:auto; margin-right:auto; text-align:center;">
      <h1><?php echo $ApplicationName; ?></h1>
      <h3>온라인 파일 변환기, 추출기, 압축기</h3>
      <hr />
    </div>
    <div id="main" align="center">
      <div id="overview" style="max-width:1000px; text-align:left; margin:25px;">
      	<p id="info" style="display:block;"><?php echo $ApplicationName; ?>는 <a href='https://github.com/zelon88/HRConvert2'>HRConvert2</a>라는 <a href='https://github.com/zelon88'>Zelon88</a>의 오픈 소스 애플리케이션을 기반으로 합니다. 사용자를 추적하거나 지적 재산권을 침해하지 않고 파일을 변환하도록 설계되었습니다.</p>
        <button id="more-info-button" class="info-button" onclick="toggle_visibility('more-info'); toggle_visibility('more-info-button'); toggle_visibility('supported-formats-show-button'); 
          toggle_visibility('less-info-button');" style="text-align:center; display:block; margin-left:auto; margin-right:auto;"><i>정보보기 ...</i></button>
        <button id="less-info-button" class="info-button" onclick="toggle_visibility('more-info'); toggle_visibility('more-info-button'); toggle_visibility('supported-formats-show-button'); 
          toggle_visibility('less-info-button');" style="text-align:center; display:none; margin-left:auto; margin-right:auto;"><i>정보 숨기기 ...</i></button>
        <div id="more-info" style="display:none;">
          <hr />
          <p>사용자가 제공한 모든 데이터는 자동으로 삭제되므로 서비스를 사용하는 동안 개인 정보나 재산을 잃어버릴 염려가 없습니다.</p>
          <p>현재 <?php echo $ApplicationName; ?>는 문서, 스프레드시트, 이미지, 미디어, 3D 모델, CAD 도면, 벡터 파일, 아카이브, 디스크 이미지 등을 포함한 75가지 다양한 파일 형식을 지원합니다.</p> 
          <button id="supported-formats-show-button" class="info-button" onclick="toggle_visibility('supported-formats'); toggle_visibility('supported-formats-show-button'); 
            toggle_visibility('supported-formats-hide-button');" style="text-align:center; display:none; margin-left:auto; margin-right:auto;"><i>지원되는 형식 보기 ...</i></button>
          <button id="supported-formats-hide-button" class="info-button" onclick="toggle_visibility('supported-formats'); toggle_visibility('supported-formats-show-button'); 
            toggle_visibility('supported-formats-hide-button');" style="text-align:center; display:none; margin-left:auto; margin-right:auto;"><i>지원되는 형식 숨기기 ...</i></button>
          <br>
          <div id="supported-formats" class="supported-formats" style="margin-left:33%; display:none;">
            <h3>지원되는 형식</h3>
            <hr />
            <strong>오디오 형식</strong>
            <p><i>응용 프로그래밍 인터페이스를 통해 특정 비트 전송률을 지원합니다.</i></p>
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
            <strong>비디오 형식</strong>
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
            <strong>문서 형식</strong>
            <ol>
              <li>Doc</li>
              <li>Docx</li>
              <li>Txt</li>
              <li>Rtf</li>
              <li>Odt</li>
              <li>Pdf</li>
            </ol>
            <strong>스프레드시트 형식</strong>
            <ol>
              <li>Xls</li>
              <li>Xlsx</li>
              <li>Ods</li>
            </ol>
            <strong>프레젠테이션 형식</strong>
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
            <strong>아카이브 형식</strong>
              <p><i>다음 중 하나를 생성, 변환 및 보관 해제할 수 있습니다....</i></p>
            <ol>
              <li>Zip</li>
              <li>Rar</li>
              <li>Tar</li>
              <li>Tar.Bz2</li>
              <li>7z</li>
            </ol>
            <strong>디스크 이미지 형식</strong>
            <p><i>다음 중 하나를 추출하거나 지원되는 아카이브 형식으로 변환할 수 있습니다....</i></p>
            <ol>
              <li>Iso</li>
              <li>Vhd</li>
              <li>Vdi</li>
            </ol>
            <strong>이미지 형식</strong>
            <p><i>그래픽 사용자 인터페이스 및 애플리케이션 프로그래밍 인터페이스를 통해 크기 조정 및 회전을 지원합니다.</i></p>
            <p><i>애플리케이션 프로그래밍 인터페이스를 통해 화면비 유지 비활성화를 지원합니다.</i></p>
            <p><i>이미지 변환 작업은 다음 입력 형식을 지원합니다....</i></p>
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
            <p><i>이미지 변환 작업은 다음 출력 형식을 지원합니다....</i></p>
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
            <strong>3차원 모델 형식</strong>
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
            <strong>도면 형식</strong>
            <p><i>도면 파일을 이미지 형식으로 출력할 수 있습니다.</i></p>
            <p><i>다음 중 하나로 변환 가능...</i></p>
            <ol>
              <li>Svg</li>
              <li>Dxf</li>
              <li>Fig</li>
              <li>Vdx</li>
            </ol>
            <strong>광학 문자 인식 지원</strong>
            <p><i>광학 문자 인식 작업은 다음 입력 형식을 지원합니다....</i></p>
            <ol>
              <li>Jpg</li>
              <li>Jpeg</li>
              <li>Png</li>
              <li>Bmp</li>
              <li>Pdf</li>
              <li>Gif</li>
              </ol>
            <p><i>광학 문자 인식 작업은 다음 출력 형식을 지원합니다....</i></p>
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
          <p>아래 상자에 파일을 클릭, 탭 또는 드롭하여 파일을 선택합니다.</p>
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
            <input type="submit" id="continue-button" class="info-button" value="계속하다 ...">
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