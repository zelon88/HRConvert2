// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRConvert2, Copyright on 5/12/2026 by Justin Grimes, www.github.com/zelon88
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
// / v3.4.3.
// / This file contains the client side javascript library that supports the HRConvert2 GUI.
// /
// / HARDWARE REQUIREMENTS ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / DEPENDENCY REQUIREMENTS ...
// / This application requires Debian Linux, Apache 2.4, PHP 8+, FFMPEG, Dia, bwrap,
// / Mkisofs, 7zip, LibreOffice, Unoconv, libgxps-utils, Tesseract, Unzip, OpenSCAD,
// / Unrar, Rar, ClamAV, MeshLab, PopplerUtils, PDFTOTEXT, ImageMagick & xvfb-run.// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to toggle the visibility of an HTML element.
// / Switches the 'Visibility' property between 'block' & 'none'.
function toggle_visibility(id) {
  var e = document.getElementById(id);
  if (e.style.display == 'block') e.style.display = 'none';
  else e.style.display = 'block'; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to trigger the browsers back functionality when called.
function goBack() {
  window.history.back(); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to copy an input URL to a users clipboard and output an alert in the local language.
function copy_share_link(url) {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(url); }
  else alert('Your brower does not support copying to the clipboard!'); }
// / -----------------------------------------------------------------------------------