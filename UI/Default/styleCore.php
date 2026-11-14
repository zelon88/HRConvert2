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
// / The files in this UI were submitted by Github user hernandito in Issue #85. Thank you!
// / https://github.com/hernandito
// / This file contains the dynamic stylesheets for HRConvert2.
// / This file was created by Github user hernandito as part of his forked repo, available 
// / at https://github.com/hernandito/HRConvert2/tree/master. Thank you, hernandito!
// /
// / HARDWARE REQUIREMENTS ... 
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / DEPENDENCY REQUIREMENTS ... 
// / The authoritative list of dependencies is Resources/Contract/depends.php & nothing else.
// / Run  php convertCore.php -v  to see what this installation has.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / Set dynamic CSS related variables.

// / Grey color scheme (default).
$defaultButtonCode = '
/* / The colours this scheme draws every panel with, named once.
   / HRConvert2.css draws every panel, box, menu & button from these & from nothing else, so a
   / scheme is these few lines rather than a copy of the stylesheet.
   / Each is a colour the scheme already had. The accent is its button colour, the frame its
   / button border, the tint & the ink its file list panel, & the surface what a panel sits on.
   / accent-rgb is the accent again as three numbers, so a wash of it can be drawn at any
   / strength. shade-rgb is what a line or an edge is drawn in, black on a light scheme & white
   / on the dark one, & on-accent is what is written on the accent. */
:root {
  --hrc2-accent: #5B5B5B;
  --hrc2-accent-rgb: 91, 91, 91;
  --hrc2-on-accent: #ffffff;
  --hrc2-frame: #5B5B5B;
  --hrc2-tint: #eef0f2;
  --hrc2-ink: #3a3a3a;
  --hrc2-surface: #ffffff;
  --hrc2-shade-rgb: 0, 0, 0; }
/* / THREE LAYERS, & each has a different job.
   / The page carries the scheme colour, or its pattern on blue.
   / The card is the sheet the work sits on & is WHITE in every scheme but dark, so the
   / application reads the same way whichever colour somebody picked.
   / The panel inside the card is the only layer that carries the scheme, & it is defined
   / below.
   / Tinting the card was tried & was wrong. It competed with both the page behind it & the
   / panel on top of it, & the layers stopped reading as layers. */
.file-list-card {
  background-color: var(--hrc2-surface);
  color: #555555; }
/* / The panel carries the scheme colour. It is the only one of the three layers that does.
   / It sits on the WHITE card rather than on the page, so it is a light tint rather than a
   / copy of the page colour. A panel matching the page would be right if it sat on the
   / page, & it does not.
   / Blue is the one worth saying out loud. The page of that scheme is grey with a pattern,
   / so there is no page colour to tint toward & the panel takes the blue of the scheme.
   / Every other scheme tints toward the page it belongs to. */
.file-list-panel {
  background-color: var(--hrc2-tint);
  color: var(--hrc2-ink); }
.info-button {
  -webkit-box-shadow: 3px 4px 0px 0px #f3f6f4;
  box-shadow: 3px 4px 0px 0px #f3f6f4;
  background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #eeeeee), color-stop(1, #bcbcbc));
  background:-moz-linear-gradient(top, #eeeeee 5%, #bcbcbc 100%);
  background:-webkit-linear-gradient(top, #eeeeee 5%, #bcbcbc 100%);
  background:-o-linear-gradient(top, #eeeeee 5%, #bcbcbc 100%);
  background:-ms-linear-gradient(top, #eeeeee 5%, #bcbcbc 100%);
  background:linear-gradient(to bottom, #eeeeee 5%, #bcbcbc 100%);
  background-color:#eeeeee;
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
  max-width:'.$GuiWidth.'; }
.info-button:hover {
  background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #bcbcbc), color-stop(1, #eeeeee));
  background:-moz-linear-gradient(top, #bcbcbc 5%, #eeeeee 100%);
  background:-webkit-linear-gradient(top, #bcbcbc 5%, #eeeeee 100%);
  background:-o-linear-gradient(top, #bcbcbc 5%, #eeeeee 100%);
  background:-ms-linear-gradient(top, #bcbcbc 5%, #eeeeee 100%);
  background:linear-gradient(to bottom, #bcbcbc 5%, #eeeeee 100%);
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
/* / The colours this scheme draws every panel with. The grey scheme says what each one is. */
:root {
  --hrc2-accent: #298040;
  --hrc2-accent-rgb: 41, 128, 64;
  --hrc2-on-accent: #ffffff;
  --hrc2-frame: #054d0c;
  --hrc2-tint: #e6f6e0;
  --hrc2-ink: #2f4a2f;
  --hrc2-surface: #ffffff;
  --hrc2-shade-rgb: 0, 0, 0; }
/* / THREE LAYERS, & each has a different job.
   / The page carries the scheme colour, or its pattern on blue.
   / The card is the sheet the work sits on & is WHITE in every scheme but dark, so the
   / application reads the same way whichever colour somebody picked.
   / The panel inside the card is the only layer that carries the scheme, & it is defined
   / below.
   / Tinting the card was tried & was wrong. It competed with both the page behind it & the
   / panel on top of it, & the layers stopped reading as layers. */
.file-list-card {
  background-color: var(--hrc2-surface);
  color: #555555; }
/* / The panel carries the scheme colour. It is the only one of the three layers that does.
   / It sits on the WHITE card rather than on the page, so it is a light tint rather than a
   / copy of the page colour. A panel matching the page would be right if it sat on the
   / page, & it does not.
   / Blue is the one worth saying out loud. The page of that scheme is grey with a pattern,
   / so there is no page colour to tint toward & the panel takes the blue of the scheme.
   / Every other scheme tints toward the page it belongs to. */
.file-list-panel {
  background-color: var(--hrc2-tint);
  color: var(--hrc2-ink); }
.info-button {
  -webkit-box-shadow: 3px 4px 0px 0px #b9ccb3;
  box-shadow: 3px 4px 0px 0px #b9ccb3;
  background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #50c20e), color-stop(1, #298040));
  background:-moz-linear-gradient(top, #50c20e 5%, #298040 100%);
  background:-webkit-linear-gradient(top, #50c20e 5%, #298040 100%);
  background:-o-linear-gradient(top, #50c20e 5%, #298040 100%);
  background:-ms-linear-gradient(top, #50c20e 5%, #298040 100%);
  background:linear-gradient(to bottom, #50c20e 5%, #298040 100%);
  background-color:#50c20e;
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
  max-width:'.$GuiWidth.'; }
.info-button:hover {
  background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #298040), color-stop(1, #50c20e));
  background:-moz-linear-gradient(top, #298040 5%, #50c20e 100%);
  background:-webkit-linear-gradient(top, #298040 5%, #50c20e 100%);
  background:-o-linear-gradient(top, #298040 5%, #50c20e 100%);
  background:-ms-linear-gradient(top, #298040 5%, #50c20e 100%);
  background:linear-gradient(to bottom, #298040 5%, #50c20e 100%);
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
/* / The colours this scheme draws every panel with. The grey scheme says what each one is. */
:root {
  --hrc2-accent: #2d60b2;
  --hrc2-accent-rgb: 45, 96, 178;
  --hrc2-on-accent: #ffffff;
  --hrc2-frame: #16537e;
  --hrc2-tint: #e4edf7;
  --hrc2-ink: #26384a;
  --hrc2-surface: #ffffff;
  --hrc2-shade-rgb: 0, 0, 0; }
/* / THREE LAYERS, & each has a different job.
   / The page carries the scheme colour, or its pattern on blue.
   / The card is the sheet the work sits on & is WHITE in every scheme but dark, so the
   / application reads the same way whichever colour somebody picked.
   / The panel inside the card is the only layer that carries the scheme, & it is defined
   / below.
   / Tinting the card was tried & was wrong. It competed with both the page behind it & the
   / panel on top of it, & the layers stopped reading as layers. */
.file-list-card {
  background-color: var(--hrc2-surface);
  color: #555555; }
/* / The panel carries the scheme colour. It is the only one of the three layers that does.
   / It sits on the WHITE card rather than on the page, so it is a light tint rather than a
   / copy of the page colour. A panel matching the page would be right if it sat on the
   / page, & it does not.
   / Blue is the one worth saying out loud. The page of that scheme is grey with a pattern,
   / so there is no page colour to tint toward & the panel takes the blue of the scheme.
   / Every other scheme tints toward the page it belongs to. */
.file-list-panel {
  background-color: var(--hrc2-tint);
  color: var(--hrc2-ink); }
.info-button {
  -webkit-box-shadow: 3px 4px 0px 0px #cfe2f3;
  box-shadow: 3px 4px 0px 0px #cfe2f3;
  background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #2d60b2), color-stop(1, #295680));
  background:-moz-linear-gradient(top, #2d60b2 5%, #295680 100%);
  background:-webkit-linear-gradient(top, #2d60b2 5%, #295680 100%);
  background:-o-linear-gradient(top, #2d60b2 5%, #295680 100%);
  background:-ms-linear-gradient(top, #2d60b2 5%, #295680 100%);
  background:linear-gradient(to bottom, #2d60b2 5%, #295680 100%);
  background-color:#2d60b2;
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
  max-width:'.$GuiWidth.'; }
.info-button:hover {
  background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #295680), color-stop(1, #2d60b2));
  background:-moz-linear-gradient(top, #295680 5%, #2d60b2 100%);
  background:-webkit-linear-gradient(top, #295680 5%, #2d60b2 100%);
  background:-o-linear-gradient(top, #295680 5%, #2d60b2 100%);
  background:-ms-linear-gradient(top, #295680 5%, #2d60b2 100%);
  background:linear-gradient(to bottom, #295680 5%, #2d60b2 100%);
  background-color:#295680; }
.info-button:active {
  position:relative;
  top:1px; }
select {
  background: url("'.$GuiImageDir.'darrowblue.png") 96% / 15% no-repeat #eee; }
body { 
  background-color: #888888; 
  background-image: url("'.$GuiImageDir.'grey_wash_wall.png");
  
  }';

// / Red color scheme.
$redButtonCode = '
/* / The colours this scheme draws every panel with. The grey scheme says what each one is. */
:root {
  --hrc2-accent: #c20e0e;
  --hrc2-accent-rgb: 194, 14, 14;
  --hrc2-on-accent: #ffffff;
  --hrc2-frame: #990000;
  --hrc2-tint: #fbe6df;
  --hrc2-ink: #4a2f2f;
  --hrc2-surface: #ffffff;
  --hrc2-shade-rgb: 0, 0, 0; }
/* / THREE LAYERS, & each has a different job.
   / The page carries the scheme colour, or its pattern on blue.
   / The card is the sheet the work sits on & is WHITE in every scheme but dark, so the
   / application reads the same way whichever colour somebody picked.
   / The panel inside the card is the only layer that carries the scheme, & it is defined
   / below.
   / Tinting the card was tried & was wrong. It competed with both the page behind it & the
   / panel on top of it, & the layers stopped reading as layers. */
.file-list-card {
  background-color: var(--hrc2-surface);
  color: #555555; }
/* / The panel carries the scheme colour. It is the only one of the three layers that does.
   / It sits on the WHITE card rather than on the page, so it is a light tint rather than a
   / copy of the page colour. A panel matching the page would be right if it sat on the
   / page, & it does not.
   / Blue is the one worth saying out loud. The page of that scheme is grey with a pattern,
   / so there is no page colour to tint toward & the panel takes the blue of the scheme.
   / Every other scheme tints toward the page it belongs to. */
.file-list-panel {
  background-color: var(--hrc2-tint);
  color: var(--hrc2-ink); }
.info-button {
  -webkit-box-shadow: 3px 4px 0px 0px #bcbcbc;
  box-shadow: 3px 4px 0px 0px #bcbcbc;
  background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #c20e0e), color-stop(1, #9b443b));
  background:-moz-linear-gradient(top, #c20e0e 5%, #9b443b 100%);
  background:-webkit-linear-gradient(top, #c20e0e 5%, #9b443b 100%);
  background:-o-linear-gradient(top, #c20e0e 5%, #9b443b 100%);
  background:-ms-linear-gradient(top, #c20e0e 5%, #9b443b 100%);
  background:linear-gradient(to bottom, #c20e0e 5%, #9b443b 100%);
  background-color:#c20e0e;
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
  max-width:'.$GuiWidth.'; }
.info-button:hover {
  background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #9b443b), color-stop(1, #c20e0e));
  background:-moz-linear-gradient(top, #9b443b 5%, #c20e0e 100%);
  background:-webkit-linear-gradient(top, #9b443b 5%, #c20e0e 100%);
  background:-o-linear-gradient(top, #9b443b 5%, #c20e0e 100%);
  background:-ms-linear-gradient(top, #9b443b 5%, #c20e0e 100%);
  background:linear-gradient(to bottom, #9b443b 5%, #c20e0e 100%);
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
/* / The colours this scheme draws every panel with. The grey scheme says what each one is. */
:root {
  --hrc2-accent: #b35900;
  --hrc2-accent-rgb: 179, 89, 0;
  --hrc2-on-accent: #ffffff;
  --hrc2-frame: #803300;
  --hrc2-tint: #fdefdc;
  --hrc2-ink: #4a3a20;
  --hrc2-surface: #ffffff;
  --hrc2-shade-rgb: 0, 0, 0; }
/* / THREE LAYERS, & each has a different job.
   / The page carries the scheme colour, or its pattern on blue.
   / The card is the sheet the work sits on & is WHITE in every scheme but dark, so the
   / application reads the same way whichever colour somebody picked.
   / The panel inside the card is the only layer that carries the scheme, & it is defined
   / below.
   / Tinting the card was tried & was wrong. It competed with both the page behind it & the
   / panel on top of it, & the layers stopped reading as layers. */
.file-list-card {
  background-color: var(--hrc2-surface);
  color: #555555; }
/* / The panel carries the scheme colour. It is the only one of the three layers that does.
   / It sits on the WHITE card rather than on the page, so it is a light tint rather than a
   / copy of the page colour. A panel matching the page would be right if it sat on the
   / page, & it does not.
   / Blue is the one worth saying out loud. The page of that scheme is grey with a pattern,
   / so there is no page colour to tint toward & the panel takes the blue of the scheme.
   / Every other scheme tints toward the page it belongs to. */
.file-list-panel {
  background-color: var(--hrc2-tint);
  color: var(--hrc2-ink); }
.info-button {
  -webkit-box-shadow: 3px 4px 0px 0px #ffdcb3;
  box-shadow: 3px 4px 0px 0px #ffdcb3;
  background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #ff9900), color-stop(1, #b35900));
  background:-moz-linear-gradient(top, #ff9900 5%, #b35900 100%);
  background:-webkit-linear-gradient(top, #ff9900 5%, #b35900 100%);
  background:-o-linear-gradient(top, #ff9900 5%, #b35900 100%);
  background:-ms-linear-gradient(top, #ff9900 5%, #b35900 100%);
  background:linear-gradient(to bottom, #ff9900 5%, #b35900 100%);
  background-color:#ff9900;
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
  max-width:'.$GuiWidth.'; }
.info-button:hover {
  background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #b35900), color-stop(1, #ff9900));
  background:-moz-linear-gradient(top, #b35900 5%, #ff9900 100%);
  background:-webkit-linear-gradient(top, #b35900 5%, #ff9900 100%);
  background:-o-linear-gradient(top, #b35900 5%, #ff9900 100%);
  background:-ms-linear-gradient(top, #b35900 5%, #ff9900 100%);
  background:linear-gradient(to bottom, #b35900 5%, #ff9900 100%);
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
/* / The colours this scheme draws every panel with. The grey scheme says what each one is. */
:root {
  --hrc2-accent: #8e44ad;
  --hrc2-accent-rgb: 142, 68, 173;
  --hrc2-on-accent: #ffffff;
  --hrc2-frame: #4a235a;
  --hrc2-tint: #f1e7f6;
  --hrc2-ink: #3f2f4a;
  --hrc2-surface: #ffffff;
  --hrc2-shade-rgb: 0, 0, 0; }
/* / THREE LAYERS, & each has a different job.
   / The page carries the scheme colour, or its pattern on blue.
   / The card is the sheet the work sits on & is WHITE in every scheme but dark, so the
   / application reads the same way whichever colour somebody picked.
   / The panel inside the card is the only layer that carries the scheme, & it is defined
   / below.
   / Tinting the card was tried & was wrong. It competed with both the page behind it & the
   / panel on top of it, & the layers stopped reading as layers. */
.file-list-card {
  background-color: var(--hrc2-surface);
  color: #555555; }
/* / The panel carries the scheme colour. It is the only one of the three layers that does.
   / It sits on the WHITE card rather than on the page, so it is a light tint rather than a
   / copy of the page colour. A panel matching the page would be right if it sat on the
   / page, & it does not.
   / Blue is the one worth saying out loud. The page of that scheme is grey with a pattern,
   / so there is no page colour to tint toward & the panel takes the blue of the scheme.
   / Every other scheme tints toward the page it belongs to. */
.file-list-panel {
  background-color: var(--hrc2-tint);
  color: var(--hrc2-ink); }
.info-button {
  -webkit-box-shadow: 3px 4px 0px 0px #e1cfea;
  box-shadow: 3px 4px 0px 0px #e1cfea;
  background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #8e44ad), color-stop(1, #5b2c6f));
  background:-moz-linear-gradient(top, #8e44ad 5%, #5b2c6f 100%);
  background:-webkit-linear-gradient(top, #8e44ad 5%, #5b2c6f 100%);
  background:-o-linear-gradient(top, #8e44ad 5%, #5b2c6f 100%);
  background:-ms-linear-gradient(top, #8e44ad 5%, #5b2c6f 100%);
  background:linear-gradient(to bottom, #8e44ad 5%, #5b2c6f 100%);
  background-color:#8e44ad;
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
  max-width:'.$GuiWidth.'; }
.info-button:hover {
  background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #5b2c6f), color-stop(1, #8e44ad));
  background:-moz-linear-gradient(top, #5b2c6f 5%, #8e44ad 100%);
  background:-webkit-linear-gradient(top, #5b2c6f 5%, #8e44ad 100%);
  background:-o-linear-gradient(top, #5b2c6f 5%, #8e44ad 100%);
  background:-ms-linear-gradient(top, #5b2c6f 5%, #8e44ad 100%);
  background:linear-gradient(to bottom, #5b2c6f 5%, #8e44ad 100%);
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
/* / The colours this scheme draws every panel with. The grey scheme says what each one is. */
:root {
  --hrc2-accent: #d8d8d8;
  --hrc2-accent-rgb: 216, 216, 216;
  --hrc2-on-accent: #262626;
  --hrc2-frame: #555555;
  --hrc2-tint: #3a3a3a;
  --hrc2-ink: #e4e4e4;
  --hrc2-surface: #2a2a2a;
  --hrc2-shade-rgb: 255, 255, 255; }
/* / THREE LAYERS, & each has a different job.
   / The page carries the scheme colour, or its pattern on blue.
   / The card is the sheet the work sits on & is WHITE in every scheme but dark, so the
   / application reads the same way whichever colour somebody picked.
   / The panel inside the card is the only layer that carries the scheme, & it is defined
   / below.
   / Tinting the card was tried & was wrong. It competed with both the page behind it & the
   / panel on top of it, & the layers stopped reading as layers. */
/* / The dark scheme is the one exception to the white card & it has to be.
   / A white sheet in a dark interface is a light source, & the whole point of a dark
   / scheme is that there is not one. The three layers still read as three layers,
   / just inverted, so the card is lighter than the page & the panel is lighter again.
   / This comment was not closed before v4.2.0, so it hid the rule under it & the card
   / was drawn in the colour of the page. */
.file-list-card {
  background-color: var(--hrc2-surface);
  color: #d8d8d8; }
/* / The panel carries the scheme colour. It is the only one of the three layers that does.
   / It sits on the WHITE card rather than on the page, so it is a light tint rather than a
   / copy of the page colour. A panel matching the page would be right if it sat on the
   / page, & it does not.
   / Blue is the one worth saying out loud. The page of that scheme is grey with a pattern,
   / so there is no page colour to tint toward & the panel takes the blue of the scheme.
   / Every other scheme tints toward the page it belongs to. */
.file-list-panel {
  background-color: var(--hrc2-tint);
  color: var(--hrc2-ink); }
.info-button {
  -webkit-box-shadow: 3px 4px 0px 0px #1a1a1a;
  box-shadow: 3px 4px 0px 0px #1a1a1a;
  background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #454545), color-stop(1, #262626));
  background:-moz-linear-gradient(top, #454545 5%, #262626 100%);
  background:-webkit-linear-gradient(top, #454545 5%, #262626 100%);
  background:-o-linear-gradient(top, #454545 5%, #262626 100%);
  background:-ms-linear-gradient(top, #454545 5%, #262626 100%);
  background:linear-gradient(to bottom, #454545 5%, #262626 100%);
  background-color:#454545;
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
  max-width:'.$GuiWidth.'; }
.info-button:hover {
  background:-webkit-gradient(linear, left top, left bottom, color-stop(0.05, #262626), color-stop(1, #454545));
  background:-moz-linear-gradient(top, #262626 5%, #454545 100%);
  background:-webkit-linear-gradient(top, #262626 5%, #454545 100%);
  background:-o-linear-gradient(top, #262626 5%, #454545 100%);
  background:-ms-linear-gradient(top, #262626 5%, #454545 100%);
  background:linear-gradient(to bottom, #262626 5%, #454545 100%);
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
  color: #ffffff; }
/* / A link in the browser\'s own blue or purple cannot be read on a dark page, so it is drawn light. */
a:link {
  color: #8ab4f8; }
a:visited {
  color: #c58af9; }';
// / -----------------------------------------------------------------------------------