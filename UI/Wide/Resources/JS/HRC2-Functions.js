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
// / This application requires Debian Linux, Apache 2.4, PHP 8+, FFMPEG, Dia, bwrap,
// / Mkisofs, 7zip, LibreOffice, Unoconv, libgxps-utils, Tesseract, Unzip, OpenSCAD,
// / Unrar, Rar, ClamAV, MeshLab, PopplerUtils, PDFTOTEXT, ImageMagick & xvfb-run.
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
// / A function to copy an input URL to a users clipboard and output an alert in the local language.
function copy_share_link(url) {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(url); }
  else alert('Your brower does not support copying to the clipboard!'); }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to point the hidden download anchor at a file & trigger it.
// / The core guarantees a filename free of HTML & shell metacharacters, but NOT free of
// / URL metacharacters. The characters ? + and = all survive sanitizeString() because all
// / three are legitimate in a filename, & a name such as budget?final.pdf therefore breaks
// / a link that has not been encoded. Everything after the ? becomes a query string.
// / Non ASCII filenames have the same problem for a different reason. They are valid on
// / disk but are not valid in a URI path until they are percent encoded.
// / Encoding belongs here because this is the only place that knows a URL is being built.
// / The basePath must already end with a separator & is never encoded, because it carries
// / the session path separators that a URI is required to keep literal.
function download_file(basePath, fileName) {
  var e = document.getElementById('downloadTarget');
  if (!e) return false;
  e.href = basePath + encodeURIComponent(fileName);
  e.click();
  return true; }
// / -----------------------------------------------------------------------------------

// / -----------------------------------------------------------------------------------
// / A function to copy an input URL to a users clipboard.
// / The message shown when the browser has no clipboard support is passed in by the caller.
// / This file is static javascript & cannot read a language pack, so the calling PHP
// / supplies the string in whatever language the session is using.
function copy_share_link(url, unsupportedMessage) {
  if (navigator.clipboard) navigator.clipboard.writeText(url);
  else alert(unsupportedMessage); }
// / -----------------------------------------------------------------------------------
