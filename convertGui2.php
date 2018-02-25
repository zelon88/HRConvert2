      <form id='options-form' action="convertCore.php" method="post" enctype="multipart/form-data">
        <div id='audio-options' class='options' style="display:none;">
          <p><strong>Audio Options</strong></p>
          <hr />
          <select id='audio-options' class='options-dropdown'>
          <option value="mp2">Mp2</option>  
          <option value="mp3">Mp3</option>
          <option value="wav">Wav</option>
          <option value="wma">Wma</option>
          <option value="flac">Flac</option>  
          <option value="ogg">Ogg</option>
        </select>
        </div>
        <div id='video-options' class='options' style="display:none;">
        <p><strong>Video Options</strong></p>
        <hr />
          <select id='video-options' class='options-dropdown'>
            <option value="3gp">3gp</option> 
            <option value="mkv">Mkv</option> 
            <option value="avi">Avi</option>
            <option value="mp4">Mp4</option>
            <option value="flv">Flv</option>
            <option value="mpeg">Mpeg</option>
            <option value="wmv">Wmv</option>
          </select>
        </div>
        <div id='image-options' class='options' style="display:none;">
          <p><strong>Image Options</strong></p>
          <hr />
          <p>Select File Format: </p>
          <select id='photoextension' name='photoextension'>
            <option value="jpg">Jpg</option>
            <option value="bmp">Bmp</option>
            <option value="png">Png</option>
          </select>
          <p>Select Width and height: </p>
          <input type="number" size="4" value="0" id='width' name='width' min="0" max="1000000"> X <input type="number" size="4" value="0" id="height" name="height" min="0"  max="1000000">
          <p>Select Rotatation: <input type="number" size="3" id='rotate' name='rotate' value="0" min="0" max="359"></p>
          <input type="submit" id='convertPhotoSubmit' name='convertPhotoSubmit' value='Convert Files' onclick="toggle_visibility('loadingCommandDiv');">
        </div>
      </form>