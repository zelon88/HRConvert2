<?php
// / -----------------------------------------------------------------------------------
// / APPLICATION INFORMATION ...
// / HRConvert2, Copyright on 5/3/2023 by Justin Grimes, www.github.com/zelon88
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
// / v3.2.8.
// / This file contains the dynamic stylesheets for HRConvert2.
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

$defaultButtonCode = '.info-button {
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
  font-family:Arial;
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
  background: url("Resources/darrowdefault.png") 96% / 15% no-repeat #eee; }
body { 
  background-color: #F8F8F8; }';

$greenButtonCode = '.info-button {
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
  font-family:Arial;
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
  background: url("Resources/darrowgreen.png") 96% / 15% no-repeat #eee; }
body { 
  background-color: #E8FFE1; }';

$blueButtonCode = '.info-button {
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
  font-family:Arial;
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
  background: url("Resources/darrowblue.png") 96% / 15% no-repeat #eee; }
body { 
  background-color: #E1F7FF; }';

$redButtonCode = '.info-button {
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
  font-family:Arial;
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
  background: url("Resources/darrowred.png") 96% / 15% no-repeat #eee; }
body { 
  background-color: #FFE9E1; }';