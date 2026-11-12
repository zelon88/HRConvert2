// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRConvert2, Copyright on 11/12/2026 by Justin Grimes, www.github.com/zelon88
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
// / v4.1.4.
// / This file contains the client side javascript library that runs workflows from an interface.
// / It is the Workflows panel's behaviour & nothing else. It needs no other library, not even jQuery.
// / It speaks only the API in Documentation/ABOUT_REST_API.md, so any interface that gives it somewhere to draw can use it.
// / An interface hands it the panel's words & the ways it reaches the interface's file list, through configure().
// /
// / A PANEL THAT CANNOT REACH THE WORKFLOW CORE SAYS SO, & SAYS NOTHING ELSE.
// / A server with workflows off, or without the Workflow Core, answers that workflows are unavailable.
// / Any answer this file cannot read is treated the same way.
// / The panel then shows the words the page gave it for that. A browser is never told why, because the reason is the administrator's.
// / Everything a server or a workflow supplies is written into the page as text, & never as markup.
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
// / THE WORKFLOWS PANEL. ONE OBJECT.
// / A page calls configure() once with its words & its callbacks, & this draws the panel & runs what the user picks.
// / A workflow runs in a worker on the server. This starts it, reads its status until it ends & then shows the result.
// / The server reports an ended workflow once & then forgets it, so whatever reads an ended status shows it.
window.HRC2Workflows = {

  // / Where the core answers. The page may name another.
  endpoint: 'convertCore.php',
  // / The session's tokens, which the page supplies. Every request carries them.
  tokens: { Token1: '', Token2: '' },
  // / Where the panel draws. These are the ids the interfaces use, & a page may name others.
  elements: {
    panel: 'hrc2Workflows', button: 'hrc2WorkflowsButton', boxes: 'hrc2WfBoxes', filesBox: 'hrc2WfFilesBox', files: 'hrc2WfFiles',
    pattern: 'hrc2WfPattern', pickBox: 'hrc2WfPickBox', select: 'hrc2WfSelect', about: 'hrc2WfAbout', runBox: 'hrc2WfRunBox',
    run: 'hrc2WfRun', stop: 'hrc2WfStop', status: 'hrc2WfStatus' },
  // / The panel's words. The page supplies them from the language pack. The English defaults are a last resort.
  text: {
    selected: 'Selected: ', pickFiles: 'Select files in the list below.', choose: 'Choose a workflow',
    usable: 'Selected files that can be used: {fit} of {count}.', step: 'Step {step} of {steps}: {operation}', file: 'File {done} of {total}',
    waiting: 'Waiting for the server.', starting: 'Starting.', finished: 'Finished.', stopping: 'Stopping.', stopped: 'Stopped.', made: 'Files made: ',
    unavailable: 'Workflows are not available on this server.', empty: 'No workflows are installed on this server.',
    refused: {
      disabled: 'Workflows are not available on this server.', unknown: 'That workflow is no longer available.',
      nothing: 'None of the selected files can be used by this workflow.', files: 'Too many files are selected for one workflow.',
      server: 'The server is busy. Try again in a few minutes.', busy: 'A workflow is already running in this session.',
      start: 'The workflow could not be started.' },
    failed: {
      operation: 'A step of the workflow failed.', threat: 'A virus was found, so the workflow stopped.', runtime: 'The workflow ran out of time.',
      files: 'The workflow made too many files.', budget: 'The server was too busy to finish the workflow.',
      unavailable: 'An operation this workflow needs is not available.', halted: 'The workflow stopped unexpectedly.', stopped: 'Stopped.' } },
  // / What each operation is called in the page's language. An operation not named here is shown by its own name.
  operations: {},
  // / What the page lets this reach. Each one is optional, & each default does nothing.
  // / listSelection returns the ticked files in the order they were ticked, & listFiles returns every file the list shows.
  // / selectNames ticks exactly the files it is given, filesChanged asks for the list to be drawn again & download fetches one file.
  listSelection: function () { return []; },
  listFiles: function () { return []; },
  selectNames: function () { return false; },
  filesChanged: function () { return false; },
  download: function () { return false; },

  // / What this knows so far.
  // / libraryState is unread, reading, ready or unavailable. jobState is idle, starting, running or stopping.
  isConfigured: false,
  isBound: false,
  libraryState: 'unread',
  library: [],
  chosenId: '',
  jobState: 'idle',
  pollTimer: 0,
  pollFailures: 0,

  // / Note something worth keeping that is not worth interrupting a user for.
  warn: function (messageText) {
    if (window.console && window.console.warn) window.console.warn('HRConvert2 Workflows. ' + messageText);
  },

  // / Accept the page's values, draw the panel & look for a workflow this session already started.
  // / elements, text & operations are merged over the defaults, so a page supplies only what it changes.
  configure: function (settings) {
    var self = this;
    var key = '';
    if (!settings) return false;
    for (key in settings) {
      if (!settings.hasOwnProperty(key)) continue;
      if ((key === 'elements' || key === 'operations') && settings[key] && typeof settings[key] === 'object') self[key] = self.merged(self[key], settings[key]);
      else if (key === 'text' && settings.text && typeof settings.text === 'object') self.text = self.mergedText(self.text, settings.text);
      else self[key] = settings[key]; }
    self.isConfigured = true;
    self.bind();
    self.drawAll();
    self.readStatus(true);
    return true;
  },

  // / A copy of one set of values with another written over it.
  merged: function (defaults, chosen) {
    var result = {};
    var key = '';
    for (key in defaults) { if (defaults.hasOwnProperty(key)) result[key] = defaults[key]; }
    for (key in chosen) { if (chosen.hasOwnProperty(key) && typeof chosen[key] === 'string') result[key] = chosen[key]; }
    return result;
  },

  // / The panel's words with the page's written over them, the reasons included.
  mergedText: function (defaults, chosen) {
    var result = this.merged(defaults, chosen);
    result.refused = this.merged(defaults.refused, (chosen.refused && typeof chosen.refused === 'object') ? chosen.refused : {});
    result.failed = this.merged(defaults.failed, (chosen.failed && typeof chosen.failed === 'object') ? chosen.failed : {});
    return result;
  },

  // / One of the panel's elements, or null.
  element: function (name) {
    return this.elements[name] ? document.getElementById(this.elements[name]) : null;
  },

  // / Put words into a sentence where its placeholders are, so each language can put them in its own order.
  fill: function (sentence, values) {
    var result = String(sentence || '');
    var key = '';
    for (key in values) { if (values.hasOwnProperty(key)) result = result.split('{' + key + '}').join(String(values[key])); }
    return result;
  },

  // / A new element holding text, & never markup.
  make: function (tagName, textContent, className) {
    var made = document.createElement(tagName);
    if (textContent !== undefined && textContent !== null) made.textContent = String(textContent);
    if (className) made.className = className;
    return made;
  },

  // / Empty an element.
  clear: function (target) {
    if (!target) return;
    while (target.firstChild) target.removeChild(target.firstChild);
  },

  // / Whether the panel is open.
  isOpen: function () {
    var panel = this.element('panel');
    return !!(panel && panel.style.display === 'block');
  },

  // / Open the panel, as its button does, & say so on the button.
  open: function () {
    var panel = this.element('panel');
    var button = this.element('button');
    if (panel) panel.style.display = 'block';
    if (button) button.setAttribute('aria-expanded', 'true');
    if (this.libraryState === 'unread') this.loadLibrary();
  },

  // / Bind the panel's controls, once.
  // / The page's button opens & closes the panel itself, so a page whose copy of this file did not load still opens it & reads that workflows are unavailable.
  // / This only notices the panel opening, & reads the library the first time it does.
  bind: function () {
    var self = this;
    var button = self.element('button');
    var select = self.element('select');
    var run = self.element('run');
    var stop = self.element('stop');
    var pattern = self.element('pattern');
    if (self.isBound) return;
    self.isBound = true;
    if (button) button.addEventListener('click', function () {
      button.setAttribute('aria-expanded', self.isOpen() ? 'true' : 'false');
      if (self.isOpen() && self.libraryState === 'unread') self.loadLibrary(); });
    if (select) select.addEventListener('change', function () {
      self.chosenId = select.value;
      self.drawWorkflow();
      self.drawControls(); });
    if (run) run.addEventListener('click', function () { self.run(); });
    if (stop) stop.addEventListener('click', function () { self.stop(); });
    if (pattern) {
      pattern.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        self.applyPattern(); });
      pattern.addEventListener('input', function () { pattern.removeAttribute('aria-invalid'); }); }
  },

  // / -------------------------------------------------------------------------------
  // / Requests.
  // / Every request is a POST carrying the session's tokens, & every answer is one line of JSON under the key Workflow.
  // / The line is looked for rather than assumed, because a warning PHP prints would come first.
  // / -------------------------------------------------------------------------------

  // / Send one request. fields is a list of name & value pairs, so a name may repeat, as workflowFiles[] does.
  // / onReply is given what the core said under Workflow, or null when there is no answer this can read.
  request: function (fields, onReply) {
    var self = this;
    var request = new XMLHttpRequest();
    var body = 'Token1=' + encodeURIComponent(self.tokens.Token1 || '') + '&Token2=' + encodeURIComponent(self.tokens.Token2 || '');
    var answered = false;
    var answer = function (reply) {
      if (answered) return;
      answered = true;
      try { onReply(reply); }
      catch (thrown) { self.warn('Failed while reading an answer from the server. ' + thrown); }
    };
    (fields || []).forEach(function (field) { body += '&' + encodeURIComponent(field[0]) + '=' + encodeURIComponent(field[1]); });
    request.open('POST', self.endpoint, true);
    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    request.timeout = 60000;
    request.onload = function () { answer((request.status === 200) ? self.readReply(request.responseText) : null); };
    request.onerror = function () { answer(null); };
    request.ontimeout = function () { answer(null); };
    request.send(body);
  },

  // / Read the line of JSON an answer carries. Returns what it holds under Workflow, or null.
  readReply: function (replyText) {
    var lines = String(replyText || '').split(/\r?\n/);
    var index = 0;
    var line = '';
    var parsed = null;
    for (index = lines.length - 1; index >= 0; index--) {
      line = lines[index].trim();
      if (line.indexOf('{"Workflow"') !== 0) continue;
      try { parsed = JSON.parse(line); }
      catch (thrown) { parsed = null; }
      if (parsed && parsed.Workflow && typeof parsed.Workflow === 'object') return parsed.Workflow; }
    return null;
  },

  // / -------------------------------------------------------------------------------
  // / The library.
  // / Only what a page needs is kept from it, & anything not shaped as the API says is left out.
  // / -------------------------------------------------------------------------------

  // / Ask for the workflows this session may run.
  loadLibrary: function () {
    var self = this;
    if (self.libraryState === 'reading') return;
    self.libraryState = 'reading';
    self.drawAll();
    self.request([['workflowLibrary', '1']], function (reply) {
      if (!reply || reply.Available !== true || Object.prototype.toString.call(reply.Library) !== '[object Array]') {
        self.libraryState = 'unavailable';
        self.library = []; }
      else {
        self.libraryState = 'ready';
        self.library = self.cleanLibrary(reply.Library); }
      self.drawAll();
    });
  },

  // / Keep each workflow's identifier, name, description, steps & what its first step reads, & nothing else.
  cleanLibrary: function (entries) {
    var isList = function (value) { return Object.prototype.toString.call(value) === '[object Array]'; };
    var cleaned = [];
    entries.forEach(function (entry) {
      var steps = [];
      if (!entry || typeof entry.Id !== 'string' || entry.Id === '' || typeof entry.Name !== 'string') return;
      if (isList(entry.Steps)) entry.Steps.forEach(function (step) {
        if (step && typeof step.Operation === 'string') steps.push({ Operation: step.Operation, Options: (step.Options && typeof step.Options === 'object') ? step.Options : {} }); });
      cleaned.push({ Id: entry.Id, Name: entry.Name, Description: (typeof entry.Description === 'string') ? entry.Description : '', Steps: steps,
        AcceptsAll: entry.AcceptsAll === true, Accepts: isList(entry.Accepts) ? entry.Accepts.filter(function (value) { return typeof value === 'string'; }) : [] }); });
    return cleaned;
  },

  // / The workflow the user chose, or null.
  chosen: function () {
    var self = this;
    var found = null;
    self.library.forEach(function (entry) { if (entry.Id === self.chosenId) found = entry; });
    return found;
  },

  // / How many of the ticked files a workflow's first step reads, going by their extensions.
  // / It is a hint, because the server applies each step's filter & decides.
  usableCount: function (entry, fileNames) {
    if (!entry) return 0;
    if (entry.AcceptsAll) return fileNames.length;
    return fileNames.filter(function (fileName) {
      var dot = String(fileName).lastIndexOf('.');
      return dot > 0 && entry.Accepts.indexOf(String(fileName).slice(dot + 1).toLowerCase()) !== -1; }).length;
  },

  // / -------------------------------------------------------------------------------
  // / Drawing.
  // / -------------------------------------------------------------------------------

  // / Draw the whole panel from what this knows.
  drawAll: function () {
    var boxes = this.element('boxes');
    var status = this.element('status');
    if (boxes) boxes.hidden = (this.libraryState === 'unavailable');
    if (status && this.jobState === 'idle') {
      if (this.libraryState === 'unavailable') this.showLines([this.text.unavailable]);
      else if (this.libraryState === 'ready' && this.library.length === 0) this.showLines([this.text.empty]);
      else if (status.getAttribute('data-hrc2-result') !== 'true') this.showLines([]); }
    this.drawFiles();
    this.drawChoices();
    this.drawWorkflow();
    this.drawControls();
  },

  // / The ticked files, in the order they were ticked, or what to do when there are none.
  drawFiles: function () {
    var self = this;
    var target = self.element('files');
    var fileNames = self.listSelection() || [];
    var list = null;
    var shown = 6;
    if (!target) return;
    self.clear(target);
    if (fileNames.length === 0) {
      target.appendChild(self.make('p', self.text.pickFiles, 'hrc2-wf-hint'));
      return; }
    target.appendChild(self.make('p', self.text.selected + fileNames.length, 'hrc2-wf-count'));
    list = self.make('ol', null, 'hrc2-wf-list');
    fileNames.slice(0, shown).forEach(function (fileName) {
      var item = self.make('li');
      item.appendChild(self.make('bdi', fileName));
      list.appendChild(item); });
    if (fileNames.length > shown) list.appendChild(self.make('li', '+' + (fileNames.length - shown), 'hrc2-wf-more'));
    target.appendChild(list);
  },

  // / The workflows to choose from. The choice is kept while the library still holds it.
  drawChoices: function () {
    var self = this;
    var select = self.element('select');
    var placeholder = null;
    if (!select) return;
    if (!self.chosen()) self.chosenId = '';
    self.clear(select);
    placeholder = self.make('option', self.text.choose);
    placeholder.value = '';
    select.appendChild(placeholder);
    self.library.forEach(function (entry) {
      var option = self.make('option', entry.Name);
      option.value = entry.Id;
      select.appendChild(option); });
    select.value = self.chosenId;
  },

  // / What the chosen workflow is & does, & how many of the ticked files it can use.
  drawWorkflow: function () {
    var self = this;
    var target = self.element('about');
    var entry = self.chosen();
    var fileNames = self.listSelection() || [];
    var steps = null;
    if (!target) return;
    self.clear(target);
    if (!entry) return;
    if (entry.Description !== '') target.appendChild(self.make('p', entry.Description, 'hrc2-wf-description'));
    steps = self.make('ol', null, 'hrc2-wf-steps');
    entry.Steps.forEach(function (step) {
      var stepName = self.operationName(step.Operation);
      var format = (typeof step.Options.Extension === 'string' && step.Options.Extension !== '') ? ' (' + step.Options.Extension + ')' : '';
      steps.appendChild(self.make('li', stepName + format)); });
    target.appendChild(steps);
    if (fileNames.length > 0) target.appendChild(self.make('p', self.fill(self.text.usable, { fit: self.usableCount(entry, fileNames), count: fileNames.length }), 'hrc2-wf-usable'));
  },

  // / Which box needs attention next, & which controls can be used.
  // / Files come first, then the workflow, then running it. Nothing is highlighted while a workflow runs or when none can.
  drawControls: function () {
    var self = this;
    var fileCount = (self.listSelection() || []).length;
    var isReady = (self.libraryState === 'ready' && self.library.length > 0);
    var isIdle = (self.jobState === 'idle');
    var attention = '';
    var select = self.element('select');
    var run = self.element('run');
    var stop = self.element('stop');
    if (isReady && isIdle) attention = (fileCount === 0) ? 'filesBox' : (!self.chosen() ? 'pickBox' : 'runBox');
    ['filesBox', 'pickBox', 'runBox'].forEach(function (name) {
      var box = self.element(name);
      if (box) box.classList.toggle('hrc2-wf-attention', name === attention); });
    if (select) select.disabled = !(isReady && isIdle);
    if (run) {
      run.disabled = !(isReady && isIdle && fileCount > 0 && !!self.chosen());
      run.hidden = !isIdle; }
    if (stop) {
      stop.hidden = isIdle;
      stop.disabled = (self.jobState === 'stopping'); }
  },

  // / What an operation is called in the page's language.
  operationName: function (operation) {
    return (this.operations && typeof this.operations[operation] === 'string') ? this.operations[operation] : String(operation);
  },

  // / Write lines into the status, each as text.
  showLines: function (lines) {
    var self = this;
    var status = self.element('status');
    if (!status) return;
    self.clear(status);
    status.removeAttribute('data-hrc2-result');
    (lines || []).forEach(function (line) { if (line !== '') status.appendChild(self.make('p', line)); });
  },

  // / Show how far a running workflow has got.
  drawProgress: function (reply) {
    var self = this;
    var status = self.element('status');
    var lines = [];
    var meter = null;
    var stepCount = Math.max(0, parseInt(reply.StepCount, 10) || 0);
    var stepNumber = Math.max(0, parseInt(reply.StepNumber, 10) || 0);
    var filesDone = Math.max(0, parseInt(reply.FilesDone, 10) || 0);
    var filesTotal = Math.max(0, parseInt(reply.FilesTotal, 10) || 0);
    if (self.jobState === 'stopping') lines.push(self.text.stopping);
    if (stepNumber > 0) lines.push(self.fill(self.text.step, { step: stepNumber, steps: stepCount, operation: self.operationName(reply.Operation) }));
    else lines.push(self.text.starting);
    if (filesTotal > 0) lines.push(self.fill(self.text.file, { done: Math.min(filesDone, filesTotal), total: filesTotal }));
    if (reply.Waiting === true) lines.push(self.text.waiting);
    self.showLines(lines);
    if (!status || stepCount === 0) return;
    meter = document.createElement('progress');
    meter.max = stepCount * 100;
    meter.value = Math.min(stepCount * 100, Math.max(0, stepNumber - 1) * 100 + (filesTotal > 0 ? Math.round(100 * Math.min(filesDone, filesTotal) / filesTotal) : 0));
    status.appendChild(meter);
  },

  // / Show how a workflow ended, & the files it made. Each file can be fetched, as a conversion's can.
  // / The result stays until the next workflow starts, because the server has already forgotten it.
  showResult: function (line, reply) {
    var self = this;
    var status = self.element('status');
    var made = (reply && Object.prototype.toString.call(reply.Produced) === '[object Array]') ? reply.Produced.filter(function (name) { return typeof name === 'string' && name !== ''; }) : [];
    var list = null;
    self.showLines([line]);
    if (!status) return;
    status.setAttribute('data-hrc2-result', 'true');
    if (made.length === 0) return;
    list = self.make('p', self.text.made, 'hrc2-wf-made-list');
    made.forEach(function (fileName, index) {
      var fetch = self.make('button', null, 'hrc2-wf-made');
      fetch.type = 'button';
      fetch.appendChild(self.make('bdi', fileName));
      fetch.addEventListener('click', function () { self.download(fileName); });
      if (index > 0) list.appendChild(document.createTextNode(' '));
      list.appendChild(fetch); });
    status.appendChild(list);
  },

  // / Words for why a workflow ended as it did.
  // / A worker that never started could not start the workflow, & any other interruption stopped it unexpectedly.
  endingLine: function (reply) {
    var reason = String(reply.Reason || '');
    if (reply.State === 'finished') return this.text.finished;
    if (reply.State === 'stopped') return this.text.stopped;
    if (reply.State === 'interrupted') return (reason === 'start') ? this.text.refused.start : this.text.failed.halted;
    return this.text.failed.hasOwnProperty(reason) ? this.text.failed[reason] : this.text.failed.halted;
  },

  // / -------------------------------------------------------------------------------
  // / Selection.
  // / -------------------------------------------------------------------------------

  // / The page calls this when the ticked files change.
  selectionChanged: function () {
    this.drawFiles();
    this.drawWorkflow();
    this.drawControls();
  },

  // / A test for a name a user typed. Text between slashes is a regular expression, & anything else matches whole names with * & ? as wildcards.
  // / Case is ignored by a wildcard, & by a regular expression that ends in i. Returns null for an expression that does not compile.
  makeMatcher: function (pattern) {
    var expression = null;
    var parts = /^\/(.+)\/(i?)$/.exec(pattern);
    var body = '';
    try {
      if (parts) expression = new RegExp(parts[1], parts[2]);
      else {
        body = pattern.replace(/[.+^${}()|[\]\\]/g, '\\$&').replace(/\*/g, '.*').replace(/\?/g, '.');
        expression = new RegExp('^' + body + '$', 'i'); } }
    catch (thrown) { expression = null; }
    return expression ? function (fileName) { return expression.test(String(fileName)); } : null;
  },

  // / Tick the files whose names match what the user typed, in the order the list shows them.
  applyPattern: function () {
    var pattern = this.element('pattern');
    var typed = pattern ? String(pattern.value || '').trim() : '';
    var matches = null;
    if (typed === '') return;
    matches = this.makeMatcher(typed);
    if (!matches) {
      pattern.setAttribute('aria-invalid', 'true');
      return; }
    pattern.removeAttribute('aria-invalid');
    this.selectNames((this.listFiles() || []).filter(matches));
  },

  // / -------------------------------------------------------------------------------
  // / Running.
  // / -------------------------------------------------------------------------------

  // / Run the chosen workflow on the ticked files, in the order they were ticked.
  run: function () {
    var self = this;
    var entry = self.chosen();
    var fileNames = self.listSelection() || [];
    var fields = [];
    if (!entry || fileNames.length === 0 || self.jobState !== 'idle') return;
    fields.push(['workflowRun', entry.Id]);
    fileNames.forEach(function (fileName) { fields.push(['workflowFiles[]', fileName]); });
    self.jobState = 'starting';
    self.drawControls();
    self.showLines([self.text.starting]);
    self.request(fields, function (reply) {
      if (!reply || reply.Available === false) {
        self.becomeUnavailable();
        return; }
      if (reply.Started === true) {
        self.pollFailures = 0;
        self.schedulePoll(800);
        return; }
      self.jobState = 'idle';
      self.showLines([self.text.refused.hasOwnProperty(String(reply.Reason)) ? self.text.refused[String(reply.Reason)] : self.text.refused.start]);
      self.drawControls();
    });
  },

  // / Ask a running workflow to stop. It stops before its next operation, because a converter stopped part way leaves a broken file.
  stop: function () {
    var self = this;
    if (self.jobState !== 'starting' && self.jobState !== 'running') return;
    self.jobState = 'stopping';
    self.drawControls();
    self.showLines([self.text.stopping]);
    self.request([['workflowStop', '1']], function () { self.schedulePoll(300); });
  },

  // / Read the status again after a while.
  schedulePoll: function (delay) {
    var self = this;
    if (self.pollTimer) window.clearTimeout(self.pollTimer);
    self.pollTimer = window.setTimeout(function () {
      self.pollTimer = 0;
      self.readStatus(false); }, delay);
  },

  // / Ask how this session's workflow is doing. The first ask comes as the page loads, & opens the panel when there is something to show.
  readStatus: function (isFirstLook) {
    var self = this;
    self.request([['workflowStatus', '1']], function (reply) { self.takeStatus(reply, isFirstLook); });
  },

  // / Act on a status.
  // / A workflow still going is shown & asked about again. One that ended is shown once, & the list is drawn again, because it made files.
  // / A server that says workflows are unavailable is believed at once.
  // / An answer that cannot be read is asked again, less often each time, & after eight in a row the panel says workflows are unavailable.
  // / The first look at a page's load is not asked again, because nothing was waiting on it.
  takeStatus: function (reply, isFirstLook) {
    var self = this;
    var state = '';
    var wasWaiting = (self.jobState !== 'idle');
    if (!reply) {
      if (isFirstLook) return;
      self.pollFailures++;
      if (self.pollFailures >= 8) self.becomeUnavailable();
      else self.schedulePoll(Math.min(10000, 1500 * Math.pow(2, self.pollFailures)));
      return; }
    self.pollFailures = 0;
    if (reply.Available !== true) {
      self.becomeUnavailable();
      return; }
    state = String(reply.State || 'none');
    if (state === 'starting' || state === 'running') {
      if (self.jobState !== 'stopping') self.jobState = 'running';
      if (isFirstLook && !self.isOpen()) self.open();
      self.drawControls();
      self.drawProgress(reply);
      self.schedulePoll(1500);
      return; }
    if (state === 'finished' || state === 'stopped' || state === 'failed' || state === 'interrupted') {
      self.jobState = 'idle';
      if (isFirstLook && !self.isOpen()) self.open();
      self.showResult(self.endingLine(reply), reply);
      self.drawControls();
      self.filesChanged();
      return; }
    // / No workflow. One this page was waiting on was cleared by something else, so the list is drawn again in case it made files.
    self.jobState = 'idle';
    if (wasWaiting) {
      self.showLines([]);
      self.filesChanged(); }
    self.drawControls();
  },

  // / Say workflows are unavailable, & stop asking.
  becomeUnavailable: function () {
    if (this.pollTimer) window.clearTimeout(this.pollTimer);
    this.pollTimer = 0;
    this.jobState = 'idle';
    this.libraryState = 'unavailable';
    this.library = [];
    this.drawAll();
  }
};
// / -----------------------------------------------------------------------------------
