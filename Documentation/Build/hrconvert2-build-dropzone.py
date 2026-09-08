# / -----------------------------------------------------------------------------------
# / HRConvert2 v3.9.3. Bundles the Dropzone source into the single script this application
# / loads. Run it from the installation root with the source tree beside it:
# /   python3 Documentation/Build/hrconvert2-build-dropzone.py
# / Then copy the result over UI/<pack>/Resources/JS/dropzone.js in every interface.
# /
# / NOTHING IS DOWNLOADED. It reads a source tree already on disk, which is the only way the
# / file that ships can be the file that was reviewed.
# /
# / Dropzone 6 is ES modules & this application loads one plain script tag, so the four
# / modules are inlined in dependency order & wrapped in a closure publishing one name.
# / The preview template is html the source imports as raw text, which a bundler turns into
# / a string. It becomes a string constant here for the same reason.
# /
# / The stylesheet is NOT rebuilt & does not need to be. Every class the version 6 markup
# / emits is already styled by the bundled dropzone.css, which was checked rather than
# / assumed. Version 6 ships .scss & compiling it would need a toolchain this build does not
# / have & does not want.
# / -----------------------------------------------------------------------------------
import re, os
S='/tmp/dz/Downloads/AV/dropzone-6.2.0/src'
def read(n): return open(os.path.join(S,n),encoding='utf-8').read()

def strip_modules(t):
    t=re.sub(r'^import[^\n]*\n', '', t, flags=re.M)
    t=re.sub(r'^export default (class|function)', r'\1', t, flags=re.M)
    t=re.sub(r'^export default ', 'var __default = ', t, flags=re.M)
    t=re.sub(r'^export \{[^}]*\};?\s*$', '', t, flags=re.M)
    return t

extend  = strip_modules(read('extend.js'))
emitter = strip_modules(read('emitter.js'))
options = strip_modules(read('options.js'))
core    = strip_modules(read('dropzone.js'))
tpl     = read('preview-template.html')

# / The raw html import becomes a string constant.
tpl_js = 'var defaultPreviewTemplate = ' + repr(tpl).replace("\\n","\\n") + ';'
if tpl_js.startswith('var defaultPreviewTemplate = "'):
    pass
tpl_js = 'var defaultPreviewTemplate = ' + '"' + tpl.replace('\\','\\\\').replace('"','\\"').replace('\n','\\n') + '";'

HEAD = """/* / -----------------------------------------------------------------------------------
 * / Dropzone 6.2.0, bundled for HRConvert2 v3.9.3.
 * /
 * / Assembled from the official src/ tree by Documentation/Build/hrconvert2-build-dropzone.py.
 * / It is NOT taken from a package registry & nothing was downloaded to produce it. The
 * / source that made this file is the source that was reviewed.
 * /
 * / Dropzone 6 ships as ES modules & this application loads one plain script tag, so the
 * / four modules are inlined in dependency order & the whole thing is wrapped in a closure
 * / that publishes exactly one name.
 * / The preview template is an html file the source imports as raw text, which a bundler
 * / would turn into a string. It is a string constant here for the same reason.
 * /
 * / autoDiscover WAS REMOVED IN VERSION 6 & this file does not restore it. A form carrying
 * / class='dropzone' is no longer wired up by simply existing. HRC2-Functions.js creates the
 * / instance explicitly, which is what version 6 expects & is easier to read besides.
 * /
 * / Upstream is https://github.com/dropzone/dropzone under the MIT license.
 * / ----------------------------------------------------------------------------------- */
(function (global) {
  'use strict';
"""
TAIL = """
  Dropzone.version = "6.2.0";
  Dropzone.options = {};
  Dropzone.instances = [];
  Dropzone.forElement = function (element) {
    if (typeof element === "string") element = document.querySelector(element);
    if (element === null || typeof element.dropzone === "undefined") throw new Error("No Dropzone found for given element. This is probably because you're trying to access it before Dropzone had the time to initialize.");
    return element.dropzone;
  };
  global.Dropzone = Dropzone;
  if (typeof module === "object" && typeof module.exports === "object") module.exports = Dropzone;
})(typeof window !== "undefined" ? window : this);
"""
out = HEAD + '\n' + tpl_js + '\n' + extend + '\n' + emitter + '\n' + core + '\n' + options + '\n' + TAIL
open('/tmp/dropzone.built.js','w',encoding='utf-8').write(out)
print('  built', len(out), 'bytes')
