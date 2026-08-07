<?php
// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRConvert2, Copyright on 8/5/2026 by Justin Grimes, www.github.com/zelon88
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
// / v3.5.8.
// / This file contains the dynamic stylesheets for HRConvert2.
// /
// / HARDWARE REQUIREMENTS ... 
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / DEPENDENCY REQUIREMENTS ... 
// / This application requires Debian Linux, Apache 2.4, PHP 8+, FFMPEG, Dia, LibreOffice, 
// / Mkisofs, 7zip, Unoconv, libgxps-utils, Tesseract, Unzip, OpenSCAD, Rar, Inkscape,
// / Unrar, ClamAV, MeshLab, PopplerUtils, PDFTOTEXT, ImageMagick, bwrap & xvfb-run.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set dynamic CSS related variables.

// / Grey color scheme (default).
$defaultButtonCode = '
#uiSelectorOptions { width: 300px; }
#uiSelectorOptions p { margin: 2px 0; }
#uiSelectorOptions strong { font-size: 11px; text-transform: uppercase; color: #777; }
#uiSelectorOptions button {
  display: inline-block !important;
  width: auto !important;
  min-width: 0 !important;
  height: auto !important;
  padding: 1px !important;
  margin: 1px !important;
  background: none !important;
  border: 1px solid #ccc !important;
  border-radius: 2px !important;
  box-shadow: none !important;
  cursor: pointer;
  line-height: 0; }
#uiSelectorOptions button[aria-current] { border: 2px solid #000 !important; }
#uiSelectorOptions img { width: 24px; height: 18px; display: block; }
#uiSelectorOptions .swatch { width: 24px; height: 18px; display: block; }
#uiSelectorOptions .txtbtn { line-height: 1.4; padding: 1px 5px !important; font-size: 11px; }
.info-button {
  -moz-box-shadow: 3px 4px 0px 0px #f3f6f4;
  -webkit-box-shadow: 3px 4px 0px 0px #f3f6f4;
  box-shadow: 3px 4px 0px 0px #f3f6f4;
  background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #eeeeee), color-stop(1, #bcbcbc));
  background:-moz-linear-gradient(top, #eeeeee 5%, #bcbcbc 100%);
  background:-webkit-linear-gradient(top, #eeeeee 5%, #bcbcbc 100%);
  background:-o-linear-gradient(top, #eeeeee 5%, #bcbcbc 100%);
  background:-ms-linear-gradient(top, #eeeeee 5%, #bcbcbc 100%);
  background:linear-gradient(to bottom, #eeeeee 5%, #bcbcbc 100%);
  filter:progid:DXImageTransform.Microsoft.gradient(startColorstr=\'#eeeeee\', endColorstr=\'#bcbcbc\',GradientType=0);
  background-color:#eeeeee;
  -moz-border-radius:5px;
  -webkit-border-radius:5px;
  border-radius:5px;
  border:1px solid #5B5B5B;
  display:inline-block;
  cursor:pointer;
  color:#ffffff;
  font-family:'.$Font.';
  font-size:17px;
  font-weight:bold;
  padding:12px 44px;
  text-decoration:none;
  text-shadow:0px 1px 0px #bcbcbc;
  min-width:100px; 
  width:250px;
  max-width:1000px; }
.info-button:hover {
  background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #bcbcbc), color-stop(1, #eeeeee));
  background:-moz-linear-gradient(top, #bcbcbc 5%, #eeeeee 100%);
  background:-webkit-linear-gradient(top, #bcbcbc 5%, #eeeeee 100%);
  background:-o-linear-gradient(top, #bcbcbc 5%, #eeeeee 100%);
  background:-ms-linear-gradient(top, #bcbcbc 5%, #eeeeee 100%);
  background:linear-gradient(to bottom, #bcbcbc 5%, #eeeeee 100%);
  filter:progid:DXImageTransform.Microsoft.gradient(startColorstr=\'#bcbcbc\', endColorstr=\'#eeeeee\',GradientType=0);
  background-color:#bcbcbc; }
.info-button:active {
  position:relative;
  top:1px; }
select {
  background: url("'.$GuiImageDir.'darrowdefault.png") 96% / 15% no-repeat #eee; }
body { 
  background-color: #F8F8F8; }';

// / Green color scheme.
$greenButtonCode = '
#uiSelectorOptions { width: 300px; }
#uiSelectorOptions p { margin: 2px 0; }
#uiSelectorOptions strong { font-size: 11px; text-transform: uppercase; color: #777; }
#uiSelectorOptions button {
  display: inline-block !important;
  width: auto !important;
  min-width: 0 !important;
  height: auto !important;
  padding: 1px !important;
  margin: 1px !important;
  background: none !important;
  border: 1px solid #ccc !important;
  border-radius: 2px !important;
  box-shadow: none !important;
  cursor: pointer;
  line-height: 0; }
#uiSelectorOptions button[aria-current] { border: 2px solid #000 !important; }
#uiSelectorOptions img { width: 24px; height: 18px; display: block; }
#uiSelectorOptions .swatch { width: 24px; height: 18px; display: block; }
#uiSelectorOptions .txtbtn { line-height: 1.4; padding: 1px 5px !important; font-size: 11px; }
.info-button {
  -moz-box-shadow: 3px 4px 0px 0px #b9ccb3;
  -webkit-box-shadow: 3px 4px 0px 0px #b9ccb3;
  box-shadow: 3px 4px 0px 0px #b9ccb3;
  background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #50c20e), color-stop(1, #298040));
  background:-moz-linear-gradient(top, #50c20e 5%, #298040 100%);
  background:-webkit-linear-gradient(top, #50c20e 5%, #298040 100%);
  background:-o-linear-gradient(top, #50c20e 5%, #298040 100%);
  background:-ms-linear-gradient(top, #50c20e 5%, #298040 100%);
  background:linear-gradient(to bottom, #50c20e 5%, #298040 100%);
  filter:progid:DXImageTransform.Microsoft.gradient(startColorstr=\'#50c20e\', endColorstr=\'#298040\',GradientType=0);
  background-color:#50c20e;
  -moz-border-radius:5px;
  -webkit-border-radius:5px;
  border-radius:5px;
  border:1px solid #054d0c;
  display:inline-block;
  cursor:pointer;
  color:#ffffff;
  font-family:'.$Font.';
  font-size:17px;
  font-weight:bold;
  padding:12px 44px;
  text-decoration:none;
  text-shadow:0px 1px 0px #1e9409;
  min-width:100px; 
  width:250px;
  max-width:1000px; }
.info-button:hover {
  background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #298040), color-stop(1, #50c20e));
  background:-moz-linear-gradient(top, #298040 5%, #50c20e 100%);
  background:-webkit-linear-gradient(top, #298040 5%, #50c20e 100%);
  background:-o-linear-gradient(top, #298040 5%, #50c20e 100%);
  background:-ms-linear-gradient(top, #298040 5%, #50c20e 100%);
  background:linear-gradient(to bottom, #298040 5%, #50c20e 100%);
  filter:progid:DXImageTransform.Microsoft.gradient(startColorstr=\'#298040\', endColorstr=\'#50c20e\',GradientType=0);
  background-color:#298040; }
.info-button:active {
  position:relative;
  top:1px; } 
select {
  background: url("'.$GuiImageDir.'darrowgreen.png") 96% / 15% no-repeat #eee; }
body { 
  background-color: #E8FFE1; }';

// / Blue color scheme.
$blueButtonCode = '
#uiSelectorOptions { width: 300px; }
#uiSelectorOptions p { margin: 2px 0; }
#uiSelectorOptions strong { font-size: 11px; text-transform: uppercase; color: #777; }
#uiSelectorOptions button {
  display: inline-block !important;
  width: auto !important;
  min-width: 0 !important;
  height: auto !important;
  padding: 1px !important;
  margin: 1px !important;
  background: none !important;
  border: 1px solid #ccc !important;
  border-radius: 2px !important;
  box-shadow: none !important;
  cursor: pointer;
  line-height: 0; }
#uiSelectorOptions button[aria-current] { border: 2px solid #000 !important; }
#uiSelectorOptions img { width: 24px; height: 18px; display: block; }
#uiSelectorOptions .swatch { width: 24px; height: 18px; display: block; }
#uiSelectorOptions .txtbtn { line-height: 1.4; padding: 1px 5px !important; font-size: 11px; }
.info-button {
  -moz-box-shadow: 3px 4px 0px 0px #cfe2f3;
  -webkit-box-shadow: 3px 4px 0px 0px #cfe2f3;
  box-shadow: 3px 4px 0px 0px #cfe2f3;
  background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #2d60b2), color-stop(1, #295680));
  background:-moz-linear-gradient(top, #2d60b2 5%, #295680 100%);
  background:-webkit-linear-gradient(top, #2d60b2 5%, #295680 100%);
  background:-o-linear-gradient(top, #2d60b2 5%, #295680 100%);
  background:-ms-linear-gradient(top, #2d60b2 5%, #295680 100%);
  background:linear-gradient(to bottom, #2d60b2 5%, #295680 100%);
  filter:progid:DXImageTransform.Microsoft.gradient(startColorstr=\'#2d60b2\', endColorstr=\'#295680\',GradientType=0);
  background-color:#2d60b2;
  -moz-border-radius:5px;
  -webkit-border-radius:5px;
  border-radius:5px;
  border:1px solid #16537e;
  display:inline-block;
  cursor:pointer;
  color:#ffffff;
  font-family:'.$Font.';
  font-size:17px;
  font-weight:bold;
  padding:12px 44px;
  text-decoration:none;
  text-shadow:0px 1px 0px #16537e;
  min-width:100px; 
  width:250px;
  max-width:1000px; }
.info-button:hover {
  background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #295680), color-stop(1, #2d60b2));
  background:-moz-linear-gradient(top, #295680 5%, #2d60b2 100%);
  background:-webkit-linear-gradient(top, #295680 5%, #2d60b2 100%);
  background:-o-linear-gradient(top, #295680 5%, #2d60b2 100%);
  background:-ms-linear-gradient(top, #295680 5%, #2d60b2 100%);
  background:linear-gradient(to bottom, #295680 5%, #2d60b2 100%);
  filter:progid:DXImageTransform.Microsoft.gradient(startColorstr=\'#295680\', endColorstr=\'#2d60b2\',GradientType=0);
  background-color:#295680; }
.info-button:active {
  position:relative;
  top:1px; }
select {
  background: url("'.$GuiImageDir.'darrowblue.png") 96% / 15% no-repeat #eee; }
body { 
  background-color: #E1F7FF; }';

// / Red color scheme.
$redButtonCode = '
#uiSelectorOptions { width: 300px; }
#uiSelectorOptions p { margin: 2px 0; }
#uiSelectorOptions strong { font-size: 11px; text-transform: uppercase; color: #777; }
#uiSelectorOptions button {
  display: inline-block !important;
  width: auto !important;
  min-width: 0 !important;
  height: auto !important;
  padding: 1px !important;
  margin: 1px !important;
  background: none !important;
  border: 1px solid #ccc !important;
  border-radius: 2px !important;
  box-shadow: none !important;
  cursor: pointer;
  line-height: 0; }
#uiSelectorOptions button[aria-current] { border: 2px solid #000 !important; }
#uiSelectorOptions img { width: 24px; height: 18px; display: block; }
#uiSelectorOptions .swatch { width: 24px; height: 18px; display: block; }
#uiSelectorOptions .txtbtn { line-height: 1.4; padding: 1px 5px !important; font-size: 11px; }
.info-button {
  -moz-box-shadow: 3px 4px 0px 0px #bcbcbc;
  -webkit-box-shadow: 3px 4px 0px 0px #bcbcbc;
  box-shadow: 3px 4px 0px 0px #bcbcbc;
  background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #c20e0e), color-stop(1, #9b443b));
  background:-moz-linear-gradient(top, #c20e0e 5%, #9b443b 100%);
  background:-webkit-linear-gradient(top, #c20e0e 5%, #9b443b 100%);
  background:-o-linear-gradient(top, #c20e0e 5%, #9b443b 100%);
  background:-ms-linear-gradient(top, #c20e0e 5%, #9b443b 100%);
  background:linear-gradient(to bottom, #c20e0e 5%, #9b443b 100%);
  filter:progid:DXImageTransform.Microsoft.gradient(startColorstr=\'#c20e0e\', endColorstr=\'#9b443b\',GradientType=0);
  background-color:#c20e0e;
  -moz-border-radius:5px;
  -webkit-border-radius:5px;
  border-radius:5px;
  border:1px solid #990000;
  display:inline-block;
  cursor:pointer;
  color:#ffffff;
  font-family:'.$Font.';
  font-size:17px;
  font-weight:bold;
  padding:12px 44px;
  text-decoration:none;
  text-shadow:0px 1px 0px #660000;
  min-width:100px; 
  width:250px;
  max-width:1000px; }
.info-button:hover {
  background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #9b443b), color-stop(1, #c20e0e));
  background:-moz-linear-gradient(top, #9b443b 5%, #c20e0e 100%);
  background:-webkit-linear-gradient(top, #9b443b 5%, #c20e0e 100%);
  background:-o-linear-gradient(top, #9b443b 5%, #c20e0e 100%);
  background:-ms-linear-gradient(top, #9b443b 5%, #c20e0e 100%);
  background:linear-gradient(to bottom, #9b443b 5%, #c20e0e 100%);
  filter:progid:DXImageTransform.Microsoft.gradient(startColorstr=\'#9b443b\', endColorstr=\'#c20e0e\',GradientType=0);
  background-color:#9b443b; }
.info-button:active {
  position:relative;
  top:1px; }
select {
  background: url("'.$GuiImageDir.'darrowred.png") 96% / 15% no-repeat #eee; }
body { 
  background-color: #FFE9E1; }';

// / Orange color scheme.
$orangeButtonCode = '
#uiSelectorOptions { width: 300px; }
#uiSelectorOptions p { margin: 2px 0; }
#uiSelectorOptions strong { font-size: 11px; text-transform: uppercase; color: #777; }
#uiSelectorOptions button {
  display: inline-block !important;
  width: auto !important;
  min-width: 0 !important;
  height: auto !important;
  padding: 1px !important;
  margin: 1px !important;
  background: none !important;
  border: 1px solid #ccc !important;
  border-radius: 2px !important;
  box-shadow: none !important;
  cursor: pointer;
  line-height: 0; }
#uiSelectorOptions button[aria-current] { border: 2px solid #000 !important; }
#uiSelectorOptions img { width: 24px; height: 18px; display: block; }
#uiSelectorOptions .swatch { width: 24px; height: 18px; display: block; }
#uiSelectorOptions .txtbtn { line-height: 1.4; padding: 1px 5px !important; font-size: 11px; }
.info-button {
  -moz-box-shadow: 3px 4px 0px 0px #ffdcb3;
  -webkit-box-shadow: 3px 4px 0px 0px #ffdcb3;
  box-shadow: 3px 4px 0px 0px #ffdcb3;
  background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #ff9900), color-stop(1, #b35900));
  background:-moz-linear-gradient(top, #ff9900 5%, #b35900 100%);
  background:-webkit-linear-gradient(top, #ff9900 5%, #b35900 100%);
  background:-o-linear-gradient(top, #ff9900 5%, #b35900 100%);
  background:-ms-linear-gradient(top, #ff9900 5%, #b35900 100%);
  background:linear-gradient(to bottom, #ff9900 5%, #b35900 100%);
  filter:progid:DXImageTransform.Microsoft.gradient(startColorstr=\'#ff9900\', endColorstr=\'#b35900\',GradientType=0);
  background-color:#ff9900;
  -moz-border-radius:5px;
  -webkit-border-radius:5px;
  border-radius:5px;
  border:1px solid #803300;
  display:inline-block;
  cursor:pointer;
  color:#ffffff;
  font-family:'.$Font.';
  font-size:17px;
  font-weight:bold;
  padding:12px 44px;
  text-decoration:none;
  text-shadow:0px 1px 0px #803300;
  min-width:100px; 
  width:250px;
  max-width:1000px; }
.info-button:hover {
  background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #b35900), color-stop(1, #ff9900));
  background:-moz-linear-gradient(top, #b35900 5%, #ff9900 100%);
  background:-webkit-linear-gradient(top, #b35900 5%, #ff9900 100%);
  background:-o-linear-gradient(top, #b35900 5%, #ff9900 100%);
  background:-ms-linear-gradient(top, #b35900 5%, #ff9900 100%);
  background:linear-gradient(to bottom, #b35900 5%, #ff9900 100%);
  filter:progid:DXImageTransform.Microsoft.gradient(startColorstr=\'#b35900\', endColorstr=\'#ff9900\',GradientType=0);
  background-color:#b35900; }
.info-button:active {
  position:relative;
  top:1px; }
select {
  background: url("'.$GuiImageDir.'darroworange.png") 96% / 15% no-repeat #eee; }
body { 
  background-color: #fff4e6; }';

// / Purple color scheme.
$purpleButtonCode = '
#uiSelectorOptions { width: 300px; }
#uiSelectorOptions p { margin: 2px 0; }
#uiSelectorOptions strong { font-size: 11px; text-transform: uppercase; color: #777; }
#uiSelectorOptions button {
  display: inline-block !important;
  width: auto !important;
  min-width: 0 !important;
  height: auto !important;
  padding: 1px !important;
  margin: 1px !important;
  background: none !important;
  border: 1px solid #ccc !important;
  border-radius: 2px !important;
  box-shadow: none !important;
  cursor: pointer;
  line-height: 0; }
#uiSelectorOptions button[aria-current] { border: 2px solid #000 !important; }
#uiSelectorOptions img { width: 24px; height: 18px; display: block; }
#uiSelectorOptions .swatch { width: 24px; height: 18px; display: block; }
#uiSelectorOptions .txtbtn { line-height: 1.4; padding: 1px 5px !important; font-size: 11px; }
.info-button {
  -moz-box-shadow: 3px 4px 0px 0px #e1cfea;
  -webkit-box-shadow: 3px 4px 0px 0px #e1cfea;
  box-shadow: 3px 4px 0px 0px #e1cfea;
  background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #8e44ad), color-stop(1, #5b2c6f));
  background:-moz-linear-gradient(top, #8e44ad 5%, #5b2c6f 100%);
  background:-webkit-linear-gradient(top, #8e44ad 5%, #5b2c6f 100%);
  background:-o-linear-gradient(top, #8e44ad 5%, #5b2c6f 100%);
  background:-ms-linear-gradient(top, #8e44ad 5%, #5b2c6f 100%);
  background:linear-gradient(to bottom, #8e44ad 5%, #5b2c6f 100%);
  filter:progid:DXImageTransform.Microsoft.gradient(startColorstr=\'#8e44ad\', endColorstr=\'#5b2c6f\',GradientType=0);
  background-color:#8e44ad;
  -moz-border-radius:5px;
  -webkit-border-radius:5px;
  border-radius:5px;
  border:1px solid #4a235a;
  display:inline-block;
  cursor:pointer;
  color:#ffffff;
  font-family:'.$Font.';
  font-size:17px;
  font-weight:bold;
  padding:12px 44px;
  text-decoration:none;
  text-shadow:0px 1px 0px #4a235a;
  min-width:100px; 
  width:250px;
  max-width:1000px; }
.info-button:hover {
  background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #5b2c6f), color-stop(1, #8e44ad));
  background:-moz-linear-gradient(top, #5b2c6f 5%, #8e44ad 100%);
  background:-webkit-linear-gradient(top, #5b2c6f 5%, #8e44ad 100%);
  background:-o-linear-gradient(top, #5b2c6f 5%, #8e44ad 100%);
  background:-ms-linear-gradient(top, #5b2c6f 5%, #8e44ad 100%);
  background:linear-gradient(to bottom, #5b2c6f 5%, #8e44ad 100%);
  filter:progid:DXImageTransform.Microsoft.gradient(startColorstr=\'#5b2c6f\', endColorstr=\'#8e44ad\',GradientType=0);
  background-color:#5b2c6f; }
.info-button:active {
  position:relative;
  top:1px; }
select {
  background: url("'.$GuiImageDir.'darrowpurple.png") 96% / 15% no-repeat #eee; }
body { 
  background-color: #f5eef8; }';

// / Dark / Night mode color scheme.
$darkButtonCode = '
#uiSelectorOptions { width: 300px; }
#uiSelectorOptions p { margin: 2px 0; }
#uiSelectorOptions strong { font-size: 11px; text-transform: uppercase; color: #777; }
#uiSelectorOptions button {
  display: inline-block !important;
  width: auto !important;
  min-width: 0 !important;
  height: auto !important;
  padding: 1px !important;
  margin: 1px !important;
  background: none !important;
  border: 1px solid #ccc !important;
  border-radius: 2px !important;
  box-shadow: none !important;
  cursor: pointer;
  line-height: 0; }
#uiSelectorOptions button[aria-current] { border: 2px solid #000 !important; }
#uiSelectorOptions img { width: 24px; height: 18px; display: block; }
#uiSelectorOptions .swatch { width: 24px; height: 18px; display: block; }
#uiSelectorOptions .txtbtn { line-height: 1.4; padding: 1px 5px !important; font-size: 11px; }
.info-button {
  -moz-box-shadow: 3px 4px 0px 0px #1a1a1a;
  -webkit-box-shadow: 3px 4px 0px 0px #1a1a1a;
  box-shadow: 3px 4px 0px 0px #1a1a1a;
  background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #454545), color-stop(1, #262626));
  background:-moz-linear-gradient(top, #454545 5%, #262626 100%);
  background:-webkit-linear-gradient(top, #454545 5%, #262626 100%);
  background:-o-linear-gradient(top, #454545 5%, #262626 100%);
  background:-ms-linear-gradient(top, #454545 5%, #262626 100%);
  background:linear-gradient(to bottom, #454545 5%, #262626 100%);
  filter:progid:DXImageTransform.Microsoft.gradient(startColorstr=\'#454545\', endColorstr=\'#262626\',GradientType=0);
  background-color:#454545;
  -moz-border-radius:5px;
  -webkit-border-radius:5px;
  border-radius:5px;
  border:1px solid #111111;
  display:inline-block;
  cursor:pointer;
  color:#ffffff;
  font-family:'.$Font.';
  font-size:17px;
  font-weight:bold;
  padding:12px 44px;
  text-decoration:none;
  text-shadow:0px 1px 0px #111111;
  min-width:100px; 
  width:250px;
  max-width:1000px; }
.info-button:hover {
  background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #262626), color-stop(1, #454545));
  background:-moz-linear-gradient(top, #262626 5%, #454545 100%);
  background:-webkit-linear-gradient(top, #262626 5%, #454545 100%);
  background:-o-linear-gradient(top, #262626 5%, #454545 100%);
  background:-ms-linear-gradient(top, #262626 5%, #454545 100%);
  background:linear-gradient(to bottom, #262626 5%, #454545 100%);
  filter:progid:DXImageTransform.Microsoft.gradient(startColorstr=\'#262626\', endColorstr=\'#454545\',GradientType=0);
  background-color:#262626; }
.info-button:active {
  position:relative;
  top:1px; }
select {
  background: url("'.$GuiImageDir.'darrowdark.png") 96% / 15% no-repeat #333;
  color: #ffffff;
  border: 1px solid #555; }
body { 
  background-color: #222222; 
  color: #ffffff; }';
// / -----------------------------------------------------------------------------------