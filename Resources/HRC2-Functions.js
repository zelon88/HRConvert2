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