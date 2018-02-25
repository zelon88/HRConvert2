// / The following code toggles the visisbility of the selected object between block and none.
function toggle_visibility(id) {
  var e = document.getElementById(id);
  if(e.style.display == 'none')
     e.style.display = 'block';
  else
     e.style.display = 'none'; }

// / The following code gives a nice onclick function for making a back button.
function goBack() {
  window.history.back(); }


