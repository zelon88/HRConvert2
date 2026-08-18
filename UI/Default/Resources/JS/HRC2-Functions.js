// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRConvert2, Copyright on 8/6/2026 by Justin Grimes, www.github.com/zelon88
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
// / v3.5.7.
// / This file contains the client side javascript library that supports the HRConvert2 GUI.
// / This file was created by Github user hernandito as part of his forked repo, available 
// / at https://github.com/hernandito/HRConvert2/tree/master. Thank you, hernandito!
// /
// / HARDWARE REQUIREMENTS ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / DEPENDENCY REQUIREMENTS ...
// / This application requires Debian Linux, Apache 2.4, PHP 8+, FFMPEG, Dia, LibreOffice, 
// / Mkisofs, 7zip, Unoconv, libgxps-utils, Tesseract, Unzip, OpenSCAD, Rar, Inkscape, Calibre,
// / Unrar, ClamAV, MeshLab, PopplerUtils, PDFTOTEXT, ImageMagick, bwrap Dia & xvfb-run.
// /
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
// / A function to copy an input URL to a users clipboard.
// / The message shown when a browser has no clipboard support is passed in by the caller.
// / This file cannot read a language pack, so the calling PHP supplies that string in
// / whatever language the session is currently using.
function copy_share_link(url, unsupportedMessage) {
  if (navigator.clipboard) navigator.clipboard.writeText(url);
  else alert(unsupportedMessage); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to point the hidden download anchor at a file & trigger it.
// / The filename is encoded because ? + and = all survive the core sanitizer.
// / The basePath carries the session separators & is never encoded.
// / A core error arrives with an HTTP 200, so a failed download reaches this function
// / carrying the error text. Refusing it here stops that text becoming a URL.
function download_file(basePath, fileName) {
  var e = document.getElementById('downloadTarget');
  if (!e) return false;
  if (!fileName || fileName.indexOf('ERROR!!!') !== -1 || fileName.indexOf('\n') !== -1) return false;
  e.href = basePath + encodeURIComponent(fileName);
  e.click();
  return true; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to build a shareable absolute URL for a file without downloading it.
// / Used by the share link display & by the copy to clipboard routine.
// / The filename is encoded for the same reasons given in download_file().
// / A share link is copied as plain text & receives no browser normalization at all,
// / so encoding here is the only thing that makes a non ASCII share link work.
function share_file_url(baseURL, fileName) {
  return baseURL + encodeURIComponent(fileName); }
// / -----------------------------------------------------------------------------------