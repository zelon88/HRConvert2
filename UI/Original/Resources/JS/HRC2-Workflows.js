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
// / This file contains the client side javascript library that runs workflows from an interface, & builds them where a server allows it.
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
// / THE BUILDER ARRANGES STEPS, & THE SERVER DECIDES WHAT RUNS.
// / A workflow the builder makes is sent to the server, which checks it exactly as it checks a library's.
// / Nothing here decides whether a workflow is valid, & a problem the server names is shown as the server wrote it.
// / Nothing is kept in the browser, so a page loaded again starts with an empty builder.
// / A server without the builder, or one whose answer this cannot read, is given the panel as it was before the builder.
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
// / A page calls configure() once with its words & its callbacks, & this draws the panel & runs what the user picks or builds.
// / A workflow runs in a worker on the server. This starts it, reads its status until it ends & then shows the result.
// / The server reports an ended workflow once & then forgets it, so whatever reads an ended status shows it.
window.HRC2Workflows = {

  // / Where the core answers. The page may name another.
  endpoint: 'convertCore.php',
  // / The session's tokens, which the page supplies. Every request carries them.
  tokens: { Token1: '', Token2: '' },
  // / Where the panel draws. These are the ids the interfaces use, & a page may name others.
  // / The builder draws its steps into the boxes, before the box that runs them.
  elements: {
    panel: 'hrc2Workflows', button: 'hrc2WorkflowsButton', boxes: 'hrc2WfBoxes', filesBox: 'hrc2WfFilesBox', files: 'hrc2WfFiles',
    pattern: 'hrc2WfPattern', pickBox: 'hrc2WfPickBox', select: 'hrc2WfSelect', about: 'hrc2WfAbout', runBox: 'hrc2WfRunBox',
    run: 'hrc2WfRun', stop: 'hrc2WfStop', status: 'hrc2WfStatus',
    pipelines: 'hrc2WfPipelines', tabs: 'hrc2WfTabs', tabPanel: 'hrc2WfTabPanel', tools: 'hrc2WfTools', add: 'hrc2WfAdd', addMenu: 'hrc2WfAddMenu' },
  // / The panel's words. The page supplies them from the language pack. The English defaults are a last resort.
  text: {
    selected: 'Selected: ', pickFiles: 'Select files in the list below.', choose: 'Choose a workflow',
    usable: 'Selected files that can be used: {fit} of {count}.', step: 'Step {step} of {steps}: {operation}', file: 'File {done} of {total}',
    waiting: 'Waiting for the server.', starting: 'Starting.', finished: 'Finished.', stopping: 'Stopping.', stopped: 'Stopped.', made: 'Files made: ',
    unavailable: 'Workflows are not available on this server.', empty: 'No workflows are installed on this server.',
    library: 'Workflows', pickPipeline: 'Select a pipeline above...', options: 'Options', back: 'Back', remove: 'Delete',
    take: 'Take: ', ticked: 'the ticked files', stepFiles: 'the files step {step} made', each: 'each file on its own', together: 'all files together',
    kinds: 'Only these kinds: ', extensions: 'Only these extensions: ', byName: 'Select By Name: ', patternHint: 'Use * and ? as wildcards, or write a regular expression between slashes.',
    smallest: 'Smallest size: ', largest: 'Largest size: ', sortBy: 'Sort By: ', arrival: 'In the order they arrive', nameOrder: 'Name',
    allFiles: 'all files', allKind: 'all {kind}', name: 'Name', untitled: 'My workflow', newWorkflow: 'New', importFile: 'Import', exportFile: 'Export',
    notWorkflow: 'This workflow is not one this server can run.', unreadable: 'The file could not be read.',
    refused: {
      disabled: 'Workflows are not available on this server.', unknown: 'That workflow is no longer available.',
      nothing: 'None of the selected files can be used by this workflow.', files: 'Too many files are selected for one workflow.',
      server: 'The server is busy. Try again in a few minutes.', busy: 'A workflow is already running in this session.',
      start: 'The workflow could not be started.', builder: 'The workflow builder is not available on this server.',
      invalid: 'This workflow is not one this server can run.' },
    failed: {
      operation: 'A step of the workflow failed.', threat: 'A virus was found, so the workflow stopped.', runtime: 'The workflow ran out of time.',
      files: 'The workflow made too many files.', budget: 'The server was too busy to finish the workflow.',
      unavailable: 'An operation this workflow needs is not available.', halted: 'The workflow stopped unexpectedly.', stopped: 'Stopped.' } },
  // / What each operation, kind of file & option is called in the page's language, & what each choice an option offers is called.
  // / Anything not named here is shown as the server names it.
  operations: {},
  kinds: {},
  optionLabels: {},
  choiceLabels: {},
  // / Whether the page offers the builder & import & export. The server's answer decides whether either is drawn.
  builder: false,
  importExport: false,
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
  // / What the builder knows. buildState is off, reading, ready or unavailable, & a builder that is unavailable leaves the panel as it was before it.
  // / catalog holds the operations the server lets a page build from, & steps the workflow being built. A step with no operation is a box waiting for one.
  // / sourceId names the library's workflow the steps came from, & while nothing has been changed that workflow is run & exported by its identifier.
  buildState: 'off',
  canExport: false,
  catalog: [],
  catalogKinds: [],
  maxSteps: 16,
  steps: [],
  workflowName: '',
  workflowDescription: '',
  sourceId: '',
  isModified: false,
  isRenamed: false,
  openStep: -1,
  activeTab: '',
  menuIsOpen: false,
  importer: null,

  // / Note something worth keeping that is not worth interrupting a user for.
  warn: function (messageText) {
    if (window.console && window.console.warn) window.console.warn('HRConvert2 Workflows. ' + messageText);
  },

  // / Accept the page's values, draw the panel & look for a workflow this session already started.
  // / elements, text & the names of things are merged over the defaults, so a page supplies only what it changes.
  configure: function (settings) {
    var self = this;
    var key = '';
    if (!settings) return false;
    for (key in settings) {
      if (!settings.hasOwnProperty(key)) continue;
      if ((key === 'elements' || key === 'operations' || key === 'kinds' || key === 'optionLabels') && settings[key] && typeof settings[key] === 'object') self[key] = self.merged(self[key], settings[key]);
      else if (key === 'choiceLabels' && settings.choiceLabels && typeof settings.choiceLabels === 'object') self.choiceLabels = self.mergedChoices(self.choiceLabels, settings.choiceLabels);
      else if (key === 'text' && settings.text && typeof settings.text === 'object') self.text = self.mergedText(self.text, settings.text);
      else if (key === 'builder' || key === 'importExport') self[key] = (settings[key] === true);
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

  // / The words for each option's choices, with the page's written over them.
  mergedChoices: function (defaults, chosen) {
    var result = {};
    var key = '';
    for (key in defaults) { if (defaults.hasOwnProperty(key)) result[key] = defaults[key]; }
    for (key in chosen) { if (chosen.hasOwnProperty(key) && chosen[key] && typeof chosen[key] === 'object') result[key] = this.merged(result[key] || {}, chosen[key]); }
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

  // / A new button holding text, with what it does as its title & its name for a screen reader.
  makeButton: function (textContent, className, title) {
    var made = this.make('button', textContent, className);
    made.type = 'button';
    if (title) {
      made.title = title;
      made.setAttribute('aria-label', title); }
    return made;
  },

  // / Empty an element.
  clear: function (target) {
    if (!target) return;
    while (target.firstChild) target.removeChild(target.firstChild);
  },

  // / Whether a value is a list.
  isList: function (value) {
    return Object.prototype.toString.call(value) === '[object Array]';
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
    var add = self.element('add');
    var addMenu = self.element('addMenu');
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
    if (add) add.addEventListener('click', function (event) {
      event.stopPropagation();
      if (self.menuIsOpen) self.closeMenu();
      else self.openMenu(); });
    if (addMenu) Array.prototype.forEach.call(addMenu.querySelectorAll('[data-hrc2-add]'), function (item) {
      item.addEventListener('click', function (event) {
        event.stopPropagation();
        self.closeMenu();
        if (item.getAttribute('data-hrc2-add') === 'files') self.addSelectStep();
        else self.addEmptyStep(); }); });
    // / The menu closes when anything else is pressed, & Escape closes it & hands the focus back to its button.
    document.addEventListener('click', function () { if (self.menuIsOpen) self.closeMenu(); });
    document.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape' || !self.menuIsOpen) return;
      self.closeMenu();
      if (add) add.focus(); });
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
  // / The library & what a page may build from.
  // / Only what a page needs is kept from either, & anything not shaped as the API says is left out.
  // / -------------------------------------------------------------------------------

  // / Ask for the workflows this session may run, & then for what the builder may use.
  loadLibrary: function () {
    var self = this;
    if (self.libraryState === 'reading') return;
    self.libraryState = 'reading';
    self.drawAll();
    self.request([['workflowLibrary', '1']], function (reply) {
      if (!reply || reply.Available !== true || !self.isList(reply.Library)) {
        self.libraryState = 'unavailable';
        self.library = []; }
      else {
        self.libraryState = 'ready';
        self.library = self.cleanLibrary(reply.Library); }
      if (self.libraryState === 'ready') self.loadOperations();
      self.drawAll();
    });
  },

  // / Keep each workflow's identifier, name, description, steps, what its first step reads & whether it needs ticked files, & nothing else.
  // / A server that does not say whether a workflow needs ticked files is taken to mean that it does.
  cleanLibrary: function (entries) {
    var self = this;
    var cleaned = [];
    entries.forEach(function (entry) {
      var steps = [];
      if (!entry || typeof entry.Id !== 'string' || entry.Id === '' || typeof entry.Name !== 'string') return;
      if (self.isList(entry.Steps)) entry.Steps.forEach(function (step) {
        if (step && typeof step.Operation === 'string') steps.push({ Operation: step.Operation, Options: (step.Options && typeof step.Options === 'object') ? step.Options : {} }); });
      cleaned.push({ Id: entry.Id, Name: entry.Name, Description: (typeof entry.Description === 'string') ? entry.Description : '', Steps: steps,
        AcceptsAll: entry.AcceptsAll === true, Accepts: self.isList(entry.Accepts) ? entry.Accepts.filter(function (value) { return typeof value === 'string'; }) : [],
        NeedsSelection: entry.NeedsSelection !== false }); });
    return cleaned;
  },

  // / Ask what the builder may use, where the page offers the builder or import & export.
  // / An answer that offers no builder, or cannot be read, leaves the panel as it was before the builder, & says whether a workflow may be exported.
  loadOperations: function () {
    var self = this;
    if (self.buildState !== 'off') return;
    if (!self.builder && !self.importExport) {
      self.buildState = 'unavailable';
      return; }
    self.buildState = 'reading';
    self.request([['workflowOperations', '1']], function (reply) {
      var catalog = [];
      var isUsable = !!(reply && reply.Available === true);
      self.canExport = (isUsable && self.importExport && reply.ImportExport === true);
      if (isUsable && self.builder && reply.Builder === true && self.isList(reply.Operations)) catalog = self.cleanCatalog(reply.Operations);
      if (catalog.length > 0) {
        self.buildState = 'ready';
        self.catalog = catalog;
        self.catalogKinds = self.isList(reply.Kinds) ? reply.Kinds.filter(function (kind) { return typeof kind === 'string' && kind !== ''; }) : [];
        self.maxSteps = Math.max(1, parseInt(reply.MaxSteps, 10) || 16);
        self.startNewWorkflow(); }
      else self.buildState = 'unavailable';
      self.drawAll();
    });
  },

  // / Keep each operation's name, whether it is the server's own Select, its modes & its options, & nothing else.
  cleanCatalog: function (entries) {
    var self = this;
    var cleaned = [];
    entries.forEach(function (entry) {
      var modes = [];
      var options = [];
      if (!entry || typeof entry.Name !== 'string' || entry.Name === '') return;
      if (self.isList(entry.Modes)) modes = entry.Modes.filter(function (mode) { return mode === 'each' || mode === 'together'; });
      if (self.isList(entry.Options)) entry.Options.forEach(function (option) {
        if (!option || typeof option.Name !== 'string' || ['choice', 'name', 'integer', 'boolean'].indexOf(option.Type) === -1) return;
        options.push({ Name: option.Name, Type: option.Type, Default: option.Default, Required: option.Required === true,
          Minimum: (typeof option.Minimum === 'number') ? option.Minimum : null, Maximum: (typeof option.Maximum === 'number') ? option.Maximum : null,
          Choices: self.isList(option.Choices) ? option.Choices.filter(function (choice) { return typeof choice === 'string'; }) : [] }); });
      if (entry.BuiltIn !== true && modes.length === 0) return;
      cleaned.push({ Name: entry.Name, BuiltIn: entry.BuiltIn === true, Modes: modes, Options: options }); });
    return cleaned;
  },

  // / The workflow the user chose from the library, or null.
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
  // / The workflow being built.
  // / A step holds its Operation, the Input it takes, its Filter, Mode, Order & Options, as the format names them.
  // / -------------------------------------------------------------------------------

  // / Whether the builder is drawn.
  isBuilding: function () {
    return this.buildState === 'ready';
  },

  // / The operation the server describes by a name, or null.
  catalogEntry: function (operationName) {
    var found = null;
    this.catalog.forEach(function (entry) { if (entry.Name === operationName) found = entry; });
    return found;
  },

  // / The option an operation's tab offers the choices of, which is its first choice, or null when it has none.
  primaryOption: function (entry) {
    var found = null;
    if (entry) entry.Options.forEach(function (option) { if (!found && option.Type === 'choice') found = option; });
    return found;
  },

  // / Begin again with one box waiting for a pipeline.
  startNewWorkflow: function () {
    this.steps = [{ Operation: '' }];
    this.workflowName = '';
    this.workflowDescription = '';
    this.sourceId = '';
    this.chosenId = '';
    this.isModified = false;
    this.isRenamed = false;
    this.openStep = -1;
  },

  // / A new step for an operation, placed at an index. A step after the first takes what the step before it made.
  // / Every option begins at its default, & the choice the user picked in the tab is given to the option the tab offers.
  makeStep: function (entry, stepIndex, choiceValue, kindName) {
    var primary = this.primaryOption(entry);
    var step = { Operation: entry.Name, Input: entry.BuiltIn ? [] : [stepIndex === 0 ? 'selection' : 'step ' + stepIndex],
      Filter: { Glob: '', Regex: '', Types: kindName ? [kindName] : [], Extensions: [], MinimumSize: '', MaximumSize: '' },
      Mode: entry.BuiltIn ? '' : entry.Modes[0], Order: 'selection', Options: {} };
    entry.Options.forEach(function (option) { if (option.Default !== undefined && option.Default !== null) step.Options[option.Name] = option.Default; });
    if (primary && typeof choiceValue === 'string') step.Options[primary.Name] = choiceValue;
    return step;
  },

  // / Where the box waiting for a pipeline is, or -1.
  emptyIndex: function () {
    var found = -1;
    this.steps.forEach(function (step, index) { if (found === -1 && step.Operation === '') found = index; });
    return found;
  },

  // / Whether every box holds a step.
  isComplete: function () {
    return this.steps.length > 0 && this.emptyIndex() === -1;
  },

  // / Whether a step finds files of its own, so the workflow may run with no file ticked.
  hasSelectStep: function () {
    var self = this;
    return self.steps.some(function (step) { var entry = self.catalogEntry(step.Operation); return !!(entry && entry.BuiltIn); });
  },

  // / Whether what is built is still a library's workflow as the library holds it.
  matchesLibrary: function () {
    return this.sourceId !== '' && !this.isModified && !this.isRenamed;
  },

  // / Note that the steps were changed. The description was its author's, of the steps they wrote, so it goes.
  markModified: function () {
    this.isModified = true;
    this.workflowDescription = '';
  },

  // / Put an operation into the box waiting for one, or into a new box at the end.
  placeStep: function (entry, choiceValue, kindName) {
    var index = this.emptyIndex();
    if (this.jobState !== 'idle') return -1;
    if (index === -1) {
      if (this.steps.length >= this.maxSteps) return -1;
      index = this.steps.length;
      this.steps.push({ Operation: '' }); }
    this.steps[index] = this.makeStep(entry, index, choiceValue, kindName);
    this.markModified();
    return index;
  },

  // / Add a box waiting for a pipeline, unless one is already waiting.
  addEmptyStep: function () {
    if (this.jobState !== 'idle' || this.emptyIndex() !== -1 || this.steps.length >= this.maxSteps) {
      this.drawAll();
      return; }
    this.steps.push({ Operation: '' });
    this.openStep = -1;
    this.drawAll();
  },

  // / Add a Select step, & open its options, because what it finds is the reason it is there.
  addSelectStep: function () {
    var self = this;
    var selectEntry = null;
    var index = -1;
    self.catalog.forEach(function (entry) { if (entry.BuiltIn && !selectEntry) selectEntry = entry; });
    if (!selectEntry) return;
    index = self.placeStep(selectEntry, undefined, '');
    if (index !== -1) self.openStep = index;
    self.drawAll();
    self.focusStep(index, '.hrc2-wf-back');
  },

  // / Take a step out. A later step that took its files takes what came before it instead, & every later reference moves down by one.
  removeStep: function (stepIndex) {
    var removedNumber = stepIndex + 1;
    if (this.jobState !== 'idle' || stepIndex < 0 || stepIndex >= this.steps.length) return;
    this.steps.splice(stepIndex, 1);
    this.steps.forEach(function (step, index) {
      var moved = [];
      if (index < stepIndex || !step.Operation || !step.Input || step.Input.length === 0) return;
      step.Input.forEach(function (reference) {
        var number = (reference.indexOf('step ') === 0) ? parseInt(reference.slice(5), 10) : 0;
        var replacement = reference;
        if (number === removedNumber) replacement = (removedNumber > 1) ? 'step ' + (removedNumber - 1) : 'selection';
        else if (number > removedNumber) replacement = 'step ' + (number - 1);
        if (moved.indexOf(replacement) === -1) moved.push(replacement); });
      step.Input = moved; });
    if (this.steps.length === 0) this.steps = [{ Operation: '' }];
    if (this.openStep === stepIndex) this.openStep = -1;
    else if (this.openStep > stepIndex) this.openStep--;
    this.markModified();
    this.drawAll();
  },

  // / Read a document the server sent into the builder. It is canonical, as the server writes one.
  // / Returns whether every step's operation is one the builder may use.
  loadDocument: function (documentText, sourceId) {
    var self = this;
    var parsed = null;
    var steps = [];
    var isUsable = true;
    try { parsed = JSON.parse(String(documentText || '')); }
    catch (thrown) { parsed = null; }
    if (!parsed || typeof parsed.Name !== 'string' || !self.isList(parsed.Steps) || parsed.Steps.length === 0) return false;
    parsed.Steps.forEach(function (written, index) {
      var entry = (written && typeof written.Operation === 'string') ? self.catalogEntry(written.Operation) : null;
      var step = null;
      if (!entry) {
        isUsable = false;
        return; }
      step = self.makeStep(entry, index, undefined, '');
      if (!entry.BuiltIn && written.Input !== undefined) step.Input = (self.isList(written.Input) ? written.Input : [written.Input]).filter(function (reference) { return typeof reference === 'string'; }).map(function (reference) {
        return (reference === 'previous') ? (index === 0 ? 'selection' : 'step ' + index) : reference; });
      if (written.Filter && typeof written.Filter === 'object') {
        if (typeof written.Filter.Glob === 'string') step.Filter.Glob = written.Filter.Glob;
        if (typeof written.Filter.Regex === 'string') step.Filter.Regex = written.Filter.Regex;
        if (self.isList(written.Filter.Types)) step.Filter.Types = written.Filter.Types.filter(function (kind) { return typeof kind === 'string'; });
        if (self.isList(written.Filter.Extensions)) step.Filter.Extensions = written.Filter.Extensions.filter(function (extension) { return typeof extension === 'string'; });
        if (written.Filter.MinimumSize !== undefined) step.Filter.MinimumSize = self.sizeText(written.Filter.MinimumSize);
        if (written.Filter.MaximumSize !== undefined) step.Filter.MaximumSize = self.sizeText(written.Filter.MaximumSize); }
      if (!entry.BuiltIn && typeof written.Mode === 'string') step.Mode = written.Mode;
      if (written.Order === 'name') step.Order = 'name';
      if (written.Options && typeof written.Options === 'object') Object.keys(written.Options).forEach(function (optionName) { step.Options[optionName] = written.Options[optionName]; });
      steps.push(step); });
    if (!isUsable) return false;
    self.steps = steps;
    self.workflowName = parsed.Name;
    self.workflowDescription = (typeof parsed.Description === 'string') ? parsed.Description : '';
    self.sourceId = sourceId || '';
    self.chosenId = sourceId || '';
    self.isModified = false;
    self.isRenamed = false;
    self.openStep = -1;
    return true;
  },

  // / The workflow being built, as the text of a workflow the server reads.
  // / A filter holds only the tests it makes, & a reference is written as the builder keeps it, as selection or step N.
  documentText: function () {
    var self = this;
    var written = { Format: 'HRProprietary Workflow', Version: 1, Name: (self.workflowName.trim() !== '') ? self.workflowName.trim() : self.text.untitled, Steps: [] };
    if (self.workflowDescription !== '') written.Description = self.workflowDescription;
    self.steps.forEach(function (step) {
      var entry = self.catalogEntry(step.Operation);
      var out = { Operation: step.Operation };
      var filter = {};
      if (entry && !entry.BuiltIn) out.Input = (step.Input.length === 1) ? step.Input[0] : step.Input.slice();
      if (step.Filter.Glob !== '') filter.Glob = step.Filter.Glob;
      if (step.Filter.Regex !== '') filter.Regex = step.Filter.Regex;
      if (step.Filter.Types.length > 0) filter.Types = step.Filter.Types.slice();
      if (step.Filter.Extensions.length > 0) filter.Extensions = step.Filter.Extensions.slice();
      if (String(step.Filter.MinimumSize).trim() !== '') filter.MinimumSize = String(step.Filter.MinimumSize).trim();
      if (String(step.Filter.MaximumSize).trim() !== '') filter.MaximumSize = String(step.Filter.MaximumSize).trim();
      if (Object.keys(filter).length > 0) out.Filter = filter;
      if (entry && !entry.BuiltIn && step.Mode !== '') out.Mode = step.Mode;
      if (step.Order === 'name') out.Order = 'name';
      if (Object.keys(step.Options).length > 0) out.Options = step.Options;
      written.Steps.push(out); });
    return JSON.stringify(written);
  },

  // / A size as a person writes one. A whole number of bytes is shown in the largest unit it fills exactly.
  sizeText: function (sizeValue) {
    var units = [['G', 1073741824], ['M', 1048576], ['K', 1024]];
    var index = 0;
    if (typeof sizeValue !== 'number') return String(sizeValue === undefined || sizeValue === null ? '' : sizeValue);
    if (sizeValue < 0) return '';
    for (index = 0; index < units.length; index++) { if (sizeValue > 0 && sizeValue % units[index][1] === 0) return (sizeValue / units[index][1]) + units[index][0]; }
    return String(sizeValue);
  },

  // / The name pattern a step's filter holds, as the box it is typed into shows it. A regular expression is shown between its own delimiters.
  patternOf: function (step) {
    return (step.Filter.Regex !== '') ? step.Filter.Regex : step.Filter.Glob;
  },

  // / Keep a name pattern a user typed. Text between slashes is a regular expression, & anything else is a wildcard pattern.
  setPattern: function (step, typed) {
    var pattern = String(typed || '').trim();
    var isExpression = /^\/.+\/[a-zA-Z]*$/.test(pattern);
    step.Filter.Regex = isExpression ? pattern : '';
    step.Filter.Glob = isExpression ? '' : pattern;
  },

  // / The words a list of extensions was typed as, split at commas & spaces, in lower case & each once.
  splitList: function (typed) {
    var result = [];
    String(typed || '').split(/[\s,]+/).forEach(function (part) {
      var word = part.replace(/^\./, '').toLowerCase();
      if (word !== '' && result.indexOf(word) === -1) result.push(word); });
    return result;
  },

  // / -------------------------------------------------------------------------------
  // / Words.
  // / -------------------------------------------------------------------------------

  // / What an operation is called in the page's language.
  operationName: function (operation) {
    return (this.operations && typeof this.operations[operation] === 'string') ? this.operations[operation] : String(operation);
  },

  // / What a kind of file is called in the page's language.
  kindName: function (kind) {
    return (this.kinds && typeof this.kinds[kind] === 'string') ? this.kinds[kind] : String(kind);
  },

  // / What an option is called in the page's language.
  optionName: function (option) {
    return (this.optionLabels && typeof this.optionLabels[option] === 'string') ? this.optionLabels[option] : String(option);
  },

  // / What one choice an option offers is called. A format is called by its extension.
  choiceName: function (option, choice) {
    var labels = this.choiceLabels ? this.choiceLabels[option] : null;
    return (labels && typeof labels[choice] === 'string') ? labels[choice] : String(choice);
  },

  // / The files a Select step finds, in words.
  selectWords: function (step) {
    var self = this;
    if (step.Filter.Types.length === 0) return self.text.allFiles;
    if (step.Filter.Types.length === 1) return self.fill(self.text.allKind, { kind: self.kindName(step.Filter.Types[0]) });
    return step.Filter.Types.map(function (kind) { return self.kindName(kind); }).join(', ');
  },

  // / The files a step takes, in words.
  inputWords: function (step) {
    var self = this;
    return step.Input.map(function (reference) { return (reference === 'selection') ? self.text.ticked : self.fill(self.text.stepFiles, { step: reference.slice(5) }); }).join(', ');
  },

  // / What a closed box says about its step.
  summaryLines: function (step, stepIndex) {
    var self = this;
    var entry = self.catalogEntry(step.Operation);
    var primary = self.primaryOption(entry);
    var lines = [];
    var pattern = self.patternOf(step);
    if (!entry) return lines;
    if (entry.BuiltIn) lines.push(self.selectWords(step));
    else {
      if (primary && step.Options[primary.Name] !== undefined) lines.push(self.choiceName(primary.Name, step.Options[primary.Name]));
      lines.push(step.Mode === 'together' ? self.text.together : self.text.each);
      if (step.Filter.Types.length > 0) lines.push(step.Filter.Types.map(function (kind) { return self.kindName(kind); }).join(', ')); }
    if (pattern !== '') lines.push(pattern);
    if (!entry.BuiltIn && !(step.Input.length === 1 && step.Input[0] === (stepIndex === 0 ? 'selection' : 'step ' + stepIndex))) lines.push(self.text.take + self.inputWords(step));
    return lines;
  },

  // / -------------------------------------------------------------------------------
  // / Drawing.
  // / -------------------------------------------------------------------------------

  // / Draw the whole panel from what this knows.
  // / The boxes stay hidden while the builder is asked about, so the panel is not drawn one way & then the other.
  drawAll: function () {
    var boxes = this.element('boxes');
    var status = this.element('status');
    var pickBox = this.element('pickBox');
    var add = this.element('add');
    var building = this.isBuilding();
    if (boxes) boxes.hidden = (this.libraryState === 'unavailable' || this.buildState === 'reading');
    if (pickBox) pickBox.hidden = building;
    if (add) add.hidden = !building;
    if (!building && this.menuIsOpen) this.closeMenu();
    if (status && this.jobState === 'idle') {
      if (this.libraryState === 'unavailable') this.showLines([this.text.unavailable]);
      else if (this.libraryState === 'ready' && this.library.length === 0 && this.buildState === 'unavailable') this.showLines([this.text.empty]);
      else if (status.getAttribute('data-hrc2-result') !== 'true' && status.getAttribute('data-hrc2-notice') !== 'true') this.showLines([]); }
    this.drawFiles();
    this.drawChoices();
    this.drawWorkflow();
    this.drawPipelines();
    this.drawSteps();
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
  // / Where the server allows it, the chosen workflow can be exported from here.
  drawWorkflow: function () {
    var self = this;
    var target = self.element('about');
    var entry = self.chosen();
    var fileNames = self.listSelection() || [];
    var steps = null;
    var exportButton = null;
    if (!target) return;
    self.clear(target);
    if (!entry || self.isBuilding()) return;
    if (entry.Description !== '') target.appendChild(self.make('p', entry.Description, 'hrc2-wf-description'));
    steps = self.make('ol', null, 'hrc2-wf-steps');
    entry.Steps.forEach(function (step) {
      var stepName = self.operationName(step.Operation);
      var format = (typeof step.Options.Extension === 'string' && step.Options.Extension !== '') ? ' (' + step.Options.Extension + ')' : '';
      steps.appendChild(self.make('li', stepName + format)); });
    target.appendChild(steps);
    if (fileNames.length > 0 && entry.NeedsSelection) target.appendChild(self.make('p', self.fill(self.text.usable, { fit: self.usableCount(entry, fileNames), count: fileNames.length }), 'hrc2-wf-usable'));
    if (self.canExport) {
      exportButton = self.makeButton(self.text.exportFile, 'hrc2-wf-tool');
      exportButton.addEventListener('click', function () { self.exportLibraryWorkflow(entry.Id); });
      target.appendChild(exportButton); }
  },

  // / The Pipelines section. A tab for the library, a tab for each operation, & the workflow's name & what can be done with it.
  // / A tab lists its choices, & a choice goes into the box waiting for a pipeline, or into a new box at the end.
  drawPipelines: function () {
    var self = this;
    var section = self.element('pipelines');
    var tabs = self.element('tabs');
    var panel = self.element('tabPanel');
    var tools = self.element('tools');
    var names = [];
    var isIdle = (self.jobState === 'idle');
    if (section) section.hidden = !self.isBuilding();
    if (!self.isBuilding() || !tabs || !panel) return;
    // / The library's tab is named with a dash, which no operation's name may hold, so the two can never be mistaken.
    if (self.library.length > 0) names.push('-library');
    self.catalog.forEach(function (entry) { names.push(entry.Name); });
    if (names.indexOf(self.activeTab) === -1) self.activeTab = names[0] || '';
    self.clear(tabs);
    names.forEach(function (name, position) {
      var tab = self.makeButton((name === '-library') ? self.text.library : self.operationName(name), 'hrc2-wf-tab');
      tab.id = 'hrc2WfTab-' + name;
      tab.setAttribute('role', 'tab');
      tab.setAttribute('data-hrc2-tab', name);
      tab.setAttribute('aria-selected', (name === self.activeTab) ? 'true' : 'false');
      tab.setAttribute('aria-controls', panel.id || '');
      tab.tabIndex = (name === self.activeTab) ? 0 : -1;
      tab.addEventListener('click', function () {
        self.activeTab = name;
        self.drawPipelines();
        self.focusTab(name); });
      // / The arrow keys move between tabs, in the direction a line is read.
      tab.addEventListener('keydown', function (event) {
        var step = 0;
        var isRightToLeft = (window.getComputedStyle(tabs).direction === 'rtl');
        if (event.key === 'ArrowRight') step = isRightToLeft ? -1 : 1;
        else if (event.key === 'ArrowLeft') step = isRightToLeft ? 1 : -1;
        else if (event.key === 'Home') step = -position;
        else if (event.key === 'End') step = names.length - 1 - position;
        if (step === 0 && event.key !== 'Home') return;
        event.preventDefault();
        self.activeTab = names[(position + step + names.length) % names.length];
        self.drawPipelines();
        self.focusTab(self.activeTab); });
      tabs.appendChild(tab); });
    self.clear(panel);
    panel.setAttribute('aria-labelledby', 'hrc2WfTab-' + self.activeTab);
    if (self.activeTab === '-library') self.drawLibraryTab(panel, isIdle);
    else self.drawOperationTab(panel, self.catalogEntry(self.activeTab), isIdle);
    if (tools) self.drawTools(tools, isIdle);
  },

  // / Hand the focus to a tab.
  focusTab: function (name) {
    var tab = document.getElementById('hrc2WfTab-' + name);
    if (tab) tab.focus();
  },

  // / The library's tab. Each workflow is a choice, & choosing one opens it in the builder. The one opened & unchanged is shown as chosen, with what it does.
  drawLibraryTab: function (panel, isIdle) {
    var self = this;
    var chips = self.make('div', null, 'hrc2-wf-chips');
    self.library.forEach(function (entry) {
      var chip = self.makeButton(entry.Name, 'hrc2-wf-chip');
      chip.setAttribute('data-hrc2-workflow', entry.Id);
      chip.setAttribute('aria-pressed', (self.matchesLibrary() && self.sourceId === entry.Id) ? 'true' : 'false');
      if (entry.Description !== '') chip.title = entry.Description;
      chip.disabled = !isIdle;
      chip.addEventListener('click', function () { self.openLibraryWorkflow(entry.Id); });
      chips.appendChild(chip); });
    panel.appendChild(chips);
    if (self.workflowDescription !== '') panel.appendChild(self.make('p', self.workflowDescription, 'hrc2-wf-description'));
  },

  // / An operation's tab. Its choices are what its first choice offers, such as every format Convert writes.
  // / Select's choices are every file, or every file of one kind. An operation offering no choice is one choice of its own.
  drawOperationTab: function (panel, entry, isIdle) {
    var self = this;
    var chips = self.make('div', null, 'hrc2-wf-chips');
    var primary = self.primaryOption(entry);
    var offer = function (label, choiceValue, kindName) {
      var chip = self.makeButton(label, 'hrc2-wf-chip');
      chip.setAttribute('data-hrc2-choice', (choiceValue !== undefined) ? choiceValue : (kindName || ''));
      chip.disabled = !isIdle || (self.emptyIndex() === -1 && self.steps.length >= self.maxSteps);
      chip.addEventListener('click', function () {
        var index = self.placeStep(entry, choiceValue, kindName);
        self.drawAll();
        if (index !== -1) self.focusStep(index, '.hrc2-wf-gear'); });
      chips.appendChild(chip); };
    if (!entry) return;
    if (entry.BuiltIn) {
      offer(self.text.allFiles, undefined, '');
      self.catalogKinds.forEach(function (kind) { offer(self.fill(self.text.allKind, { kind: self.kindName(kind) }), undefined, kind); }); }
    else if (primary) primary.Choices.forEach(function (choice) { offer(self.choiceName(primary.Name, choice), choice, ''); });
    else offer(self.operationName(entry.Name), undefined, '');
    panel.appendChild(chips);
  },

  // / The workflow's name, & New, Import & Export. Import needs the builder, & both need the server to allow them.
  drawTools: function (tools, isIdle) {
    var self = this;
    var label = self.make('label', self.text.name, 'hrc2-wf-name');
    var nameField = document.createElement('input');
    var newButton = self.makeButton(self.text.newWorkflow, 'hrc2-wf-tool');
    var importButton = null;
    var exportButton = null;
    self.clear(tools);
    nameField.type = 'text';
    nameField.id = 'hrc2WfName';
    nameField.maxLength = 80;
    nameField.dir = 'auto';
    nameField.autocomplete = 'off';
    nameField.placeholder = self.text.untitled;
    nameField.value = self.workflowName;
    nameField.disabled = !isIdle;
    nameField.addEventListener('input', function () {
      self.workflowName = nameField.value;
      self.isRenamed = true;
      self.drawLibraryChips(); });
    label.appendChild(nameField);
    tools.appendChild(label);
    newButton.disabled = !isIdle;
    newButton.addEventListener('click', function () {
      self.startNewWorkflow();
      self.showLines([]);
      self.drawAll(); });
    tools.appendChild(newButton);
    if (self.canExport) {
      importButton = self.makeButton(self.text.importFile, 'hrc2-wf-tool');
      importButton.disabled = !isIdle;
      importButton.addEventListener('click', function () { self.chooseImport(); });
      tools.appendChild(importButton);
      exportButton = self.makeButton(self.text.exportFile, 'hrc2-wf-tool');
      exportButton.disabled = !self.isComplete();
      exportButton.addEventListener('click', function () { self.exportBuilt(); });
      tools.appendChild(exportButton); }
  },

  // / Mark the library's choices again after the name changed, without drawing the name's box again, so typing in it is not interrupted.
  drawLibraryChips: function () {
    var self = this;
    var panel = self.element('tabPanel');
    if (!panel) return;
    Array.prototype.forEach.call(panel.querySelectorAll('[data-hrc2-workflow]'), function (chip) {
      chip.setAttribute('aria-pressed', (self.matchesLibrary() && self.sourceId === chip.getAttribute('data-hrc2-workflow')) ? 'true' : 'false'); });
  },

  // / Draw a box for each step, before the box that runs them.
  drawSteps: function () {
    var self = this;
    var boxes = self.element('boxes');
    var runBox = self.element('runBox');
    if (!boxes) return;
    Array.prototype.slice.call(boxes.children).forEach(function (child) { if (child.classList.contains('hrc2-wf-step')) boxes.removeChild(child); });
    if (!self.isBuilding()) return;
    self.steps.forEach(function (step, index) { boxes.insertBefore(self.stepBox(step, index), (runBox && runBox.parentNode === boxes) ? runBox : null); });
  },

  // / One step's box. A box waiting for a pipeline says so. A closed box says what its step does, & an open one holds its options.
  stepBox: function (step, stepIndex) {
    var self = this;
    var entry = self.catalogEntry(step.Operation);
    var box = self.make('div', null, 'hrc2-wf-box hrc2-wf-step');
    var heading = self.make('p', null, 'hrc2-wf-label hrc2-wf-step-heading');
    var isIdle = (self.jobState === 'idle');
    var controls = self.make('span', null, 'hrc2-wf-step-controls');
    var gear = null;
    var remove = self.makeButton('✕', 'hrc2-wf-icon hrc2-wf-remove', self.text.remove);
    var back = null;
    box.setAttribute('data-hrc2-step', String(stepIndex));
    heading.appendChild(self.make('span', String(stepIndex + 1), 'hrc2-wf-step-number'));
    remove.disabled = !isIdle;
    remove.addEventListener('click', function () { self.removeStep(stepIndex); });
    if (!entry) {
      box.classList.add('hrc2-wf-step-empty');
      controls.appendChild(remove);
      heading.appendChild(controls);
      box.appendChild(heading);
      box.appendChild(self.make('p', self.text.pickPipeline, 'hrc2-wf-hint'));
      return box; }
    heading.appendChild(self.make('strong', self.operationName(step.Operation)));
    if (self.openStep === stepIndex) {
      box.classList.add('hrc2-wf-step-open');
      back = self.makeButton('←', 'hrc2-wf-icon hrc2-wf-back', self.text.back);
      back.addEventListener('click', function () {
        self.openStep = -1;
        self.drawAll();
        self.focusStep(stepIndex, '.hrc2-wf-gear'); });
      box.appendChild(back);
      box.appendChild(heading);
      box.appendChild(self.optionsForm(step, stepIndex, entry, isIdle));
      return box; }
    gear = self.makeButton('⚙︎', 'hrc2-wf-icon hrc2-wf-gear', self.text.options);
    gear.disabled = !isIdle;
    gear.addEventListener('click', function () {
      self.openStep = stepIndex;
      self.drawAll();
      self.focusStep(stepIndex, '.hrc2-wf-back'); });
    controls.appendChild(gear);
    controls.appendChild(remove);
    heading.appendChild(controls);
    box.appendChild(heading);
    self.summaryLines(step, stepIndex).forEach(function (line) {
      var paragraph = self.make('p', null, 'hrc2-wf-summary');
      paragraph.appendChild(self.make('bdi', line));
      box.appendChild(paragraph); });
    return box;
  },

  // / Hand the focus to a control in a step's box, once it is drawn.
  focusStep: function (stepIndex, selector) {
    var boxes = this.element('boxes');
    var box = boxes ? boxes.querySelector('[data-hrc2-step="' + stepIndex + '"]') : null;
    var target = box ? box.querySelector(selector) : null;
    if (target) target.focus();
  },

  // / A step's options. The nouns come first, which files it takes & how, & then the verbs, what its operation is told.
  // / Each change is kept as it is made, & the box is not drawn again until it is closed, so typing is never interrupted.
  optionsForm: function (step, stepIndex, entry, isIdle) {
    var self = this;
    var form = self.make('div', null, 'hrc2-wf-form');
    var touched = function () {
      self.markModified();
      self.drawControls(); };
    if (!entry.BuiltIn) form.appendChild(self.takeRow(step, stepIndex, touched));
    if (!entry.BuiltIn && entry.Modes.length > 1) form.appendChild(self.modeRow(step, stepIndex, entry, touched));
    if (self.catalogKinds.length > 0) form.appendChild(self.kindsRow(step, stepIndex, touched));
    form.appendChild(self.textRow(stepIndex, 'Pattern', self.text.byName, self.patternOf(step), '*.jpg', self.text.patternHint, null, function (typed) {
      self.setPattern(step, typed);
      touched(); }));
    form.appendChild(self.textRow(stepIndex, 'Extensions', self.text.extensions, step.Filter.Extensions.join(', '), 'pdf, docx', '', /^\s*(\.?[A-Za-z0-9]{1,16}([\s,]+\.?[A-Za-z0-9]{1,16})*)?[\s,]*$/, function (typed) {
      step.Filter.Extensions = self.splitList(typed);
      touched(); }));
    form.appendChild(self.textRow(stepIndex, 'MinimumSize', self.text.smallest, step.Filter.MinimumSize, '10M', '', /^\s*([0-9]{1,12}\s*[KMGkmg]?[Bb]?)?\s*$/, function (typed) {
      step.Filter.MinimumSize = typed.trim();
      touched(); }));
    form.appendChild(self.textRow(stepIndex, 'MaximumSize', self.text.largest, step.Filter.MaximumSize, '10M', '', /^\s*([0-9]{1,12}\s*[KMGkmg]?[Bb]?)?\s*$/, function (typed) {
      step.Filter.MaximumSize = typed.trim();
      touched(); }));
    form.appendChild(self.orderRow(step, stepIndex, touched));
    if (!entry.BuiltIn) entry.Options.forEach(function (option) { form.appendChild(self.optionRow(step, stepIndex, option, touched)); });
    Array.prototype.forEach.call(form.querySelectorAll('input, select'), function (control) { if (!isIdle) control.disabled = true; });
    return form;
  },

  // / A row of the options, a label & what it labels.
  makeRow: function (labelText, control, controlId) {
    var row = this.make('div', null, 'hrc2-wf-row');
    var label = this.make(controlId ? 'label' : 'span', labelText, 'hrc2-wf-row-label');
    if (controlId) label.htmlFor = controlId;
    row.appendChild(label);
    row.appendChild(control);
    return row;
  },

  // / Which files a step takes, the ticked files or what an earlier step made, & any of them together.
  // / A step takes something, so the last one ticked cannot be taken away, & it takes no more than the format's eight.
  takeRow: function (step, stepIndex, touched) {
    var self = this;
    var group = self.make('span', null, 'hrc2-wf-row-control hrc2-wf-checks');
    var sources = ['selection'];
    var number = 0;
    for (number = 1; number <= stepIndex; number++) sources.push('step ' + number);
    group.setAttribute('role', 'group');
    sources.forEach(function (source) {
      var label = self.make('label', null, 'hrc2-wf-check');
      var box = document.createElement('input');
      box.type = 'checkbox';
      box.checked = (step.Input.indexOf(source) !== -1);
      box.setAttribute('data-hrc2-input', source);
      box.addEventListener('change', function () {
        var chosen = step.Input.filter(function (reference) { return reference !== source; });
        if (box.checked) chosen.push(source);
        if (chosen.length === 0 || chosen.length > 8) {
          box.checked = !box.checked;
          return; }
        step.Input = sources.filter(function (reference) { return chosen.indexOf(reference) !== -1; }).concat(chosen.filter(function (reference) { return sources.indexOf(reference) === -1; }));
        touched(); });
      label.appendChild(box);
      label.appendChild(self.make('span', (source === 'selection') ? self.text.ticked : self.fill(self.text.stepFiles, { step: source.slice(5) })));
      group.appendChild(label); });
    return self.makeRow(self.text.take, group, '');
  },

  // / Whether an operation that runs both ways runs once for each file or once for all of them.
  modeRow: function (step, stepIndex, entry, touched) {
    var self = this;
    var group = self.make('span', null, 'hrc2-wf-row-control hrc2-wf-checks');
    group.setAttribute('role', 'radiogroup');
    entry.Modes.forEach(function (mode) {
      var label = self.make('label', null, 'hrc2-wf-check');
      var radio = document.createElement('input');
      radio.type = 'radio';
      radio.name = 'hrc2WfMode' + stepIndex;
      radio.value = mode;
      radio.checked = (step.Mode === mode);
      radio.addEventListener('change', function () {
        if (!radio.checked) return;
        step.Mode = mode;
        touched(); });
      label.appendChild(radio);
      label.appendChild(self.make('span', (mode === 'together') ? self.text.together : self.text.each));
      group.appendChild(label); });
    return self.makeRow('', group, '');
  },

  // / The kinds of file a step keeps. None ticked keeps every kind.
  kindsRow: function (step, stepIndex, touched) {
    var self = this;
    var group = self.make('span', null, 'hrc2-wf-row-control hrc2-wf-checks');
    group.setAttribute('role', 'group');
    self.catalogKinds.forEach(function (kind) {
      var label = self.make('label', null, 'hrc2-wf-check');
      var box = document.createElement('input');
      box.type = 'checkbox';
      box.checked = (step.Filter.Types.indexOf(kind) !== -1);
      box.setAttribute('data-hrc2-kind', kind);
      box.addEventListener('change', function () {
        var chosen = step.Filter.Types.filter(function (type) { return type !== kind; });
        if (box.checked) chosen.push(kind);
        step.Filter.Types = self.catalogKinds.filter(function (type) { return chosen.indexOf(type) !== -1; });
        touched(); });
      label.appendChild(box);
      label.appendChild(self.make('span', self.kindName(kind)));
      group.appendChild(label); });
    return self.makeRow(self.text.kinds, group, '');
  },

  // / A box to type a test into. One whose text is plainly not what the server reads is marked, & the server decides.
  // / What is typed here is written left to right, because a pattern, an extension & a size read that way in every language.
  textRow: function (stepIndex, fieldName, labelText, value, placeholder, hint, shape, onChange) {
    var field = document.createElement('input');
    field.type = 'text';
    field.id = 'hrc2WfStep' + stepIndex + fieldName;
    field.className = 'hrc2-wf-row-control hrc2-wf-field';
    field.dir = 'ltr';
    field.autocomplete = 'off';
    field.spellcheck = false;
    field.placeholder = placeholder;
    field.value = String(value === undefined || value === null ? '' : value);
    if (hint) field.title = hint;
    field.addEventListener('input', function () {
      if (shape && !shape.test(field.value)) field.setAttribute('aria-invalid', 'true');
      else field.removeAttribute('aria-invalid');
      onChange(field.value); });
    return this.makeRow(labelText, field, field.id);
  },

  // / Whether a step keeps the order its files arrived in, or sorts them by name.
  orderRow: function (step, stepIndex, touched) {
    var self = this;
    var select = document.createElement('select');
    select.id = 'hrc2WfStep' + stepIndex + 'Order';
    select.className = 'hrc2-wf-row-control';
    [['selection', self.text.arrival], ['name', self.text.nameOrder]].forEach(function (pair) {
      var option = self.make('option', pair[1]);
      option.value = pair[0];
      select.appendChild(option); });
    select.value = step.Order;
    select.addEventListener('change', function () {
      step.Order = select.value;
      touched(); });
    return self.makeRow(self.text.sortBy, select, select.id);
  },

  // / One option an operation is given, drawn by its type.
  // / A choice is a list, a name is text of up to 64 characters, an integer is a number within its bounds & a boolean is a tick.
  // / A number outside its bounds is not kept, & the box is marked until it is put right.
  optionRow: function (step, stepIndex, option, touched) {
    var self = this;
    var controlId = 'hrc2WfStep' + stepIndex + 'Option' + option.Name;
    var control = null;
    if (option.Type === 'choice') {
      control = document.createElement('select');
      if (step.Options[option.Name] === undefined) {
        control.appendChild(self.make('option', ''));
        control.firstChild.value = ''; }
      option.Choices.forEach(function (choice) {
        var item = self.make('option', self.choiceName(option.Name, choice));
        item.value = choice;
        control.appendChild(item); });
      control.value = (step.Options[option.Name] !== undefined) ? String(step.Options[option.Name]) : '';
      control.addEventListener('change', function () {
        step.Options[option.Name] = control.value;
        touched(); }); }
    else if (option.Type === 'boolean') {
      control = document.createElement('input');
      control.type = 'checkbox';
      control.checked = (step.Options[option.Name] === true);
      control.addEventListener('change', function () {
        step.Options[option.Name] = control.checked;
        touched(); }); }
    else {
      control = document.createElement('input');
      control.type = (option.Type === 'integer') ? 'number' : 'text';
      control.dir = (option.Type === 'integer') ? 'ltr' : 'auto';
      control.autocomplete = 'off';
      if (option.Type === 'integer') {
        control.step = '1';
        if (option.Minimum !== null) control.min = String(option.Minimum);
        if (option.Maximum !== null) control.max = String(option.Maximum); }
      else control.maxLength = 64;
      control.value = (step.Options[option.Name] !== undefined && step.Options[option.Name] !== null) ? String(step.Options[option.Name]) : '';
      control.addEventListener('input', function () {
        var number = Number(control.value);
        if (option.Type !== 'integer') step.Options[option.Name] = control.value;
        else if (control.value.trim() !== '' && Math.floor(number) === number && (option.Minimum === null || number >= option.Minimum) && (option.Maximum === null || number <= option.Maximum)) {
          step.Options[option.Name] = number;
          control.removeAttribute('aria-invalid'); }
        else {
          control.setAttribute('aria-invalid', 'true');
          return; }
        touched(); }); }
    control.id = controlId;
    control.className = 'hrc2-wf-row-control';
    return self.makeRow(self.optionName(option.Name), control, controlId);
  },

  // / Open & close the menu of what the add button adds.
  // / The menu opens under the button, from the side a line of this language starts on, & is moved back onto the screen where it would leave it.
  // / Its place is worked out against the box it is drawn in, so it stays under the button while the page scrolls.
  openMenu: function () {
    var add = this.element('add');
    var addMenu = this.element('addMenu');
    var first = addMenu ? addMenu.querySelector('[data-hrc2-add]') : null;
    var holder = addMenu ? addMenu.offsetParent : null;
    var buttonEdges = null;
    var holderEdges = null;
    var menuWidth = 0;
    var screenWidth = document.documentElement.clientWidth || window.innerWidth || 0;
    var isRightToLeft = false;
    var wantedLeft = 0;
    if (!addMenu || !add || add.disabled) return;
    addMenu.hidden = false;
    holder = addMenu.offsetParent;
    if (holder) {
      buttonEdges = add.getBoundingClientRect();
      holderEdges = holder.getBoundingClientRect();
      menuWidth = addMenu.offsetWidth;
      isRightToLeft = (window.getComputedStyle(addMenu).direction === 'rtl');
      wantedLeft = isRightToLeft ? (buttonEdges.right - menuWidth) : buttonEdges.left;
      wantedLeft = Math.max(4, Math.min(wantedLeft, screenWidth - menuWidth - 4));
      addMenu.style.left = Math.round(wantedLeft - holderEdges.left) + 'px';
      addMenu.style.top = Math.round(buttonEdges.bottom - holderEdges.top + 4) + 'px'; }
    add.setAttribute('aria-expanded', 'true');
    this.menuIsOpen = true;
    if (first) first.focus();
  },
  closeMenu: function () {
    var add = this.element('add');
    var addMenu = this.element('addMenu');
    if (addMenu) addMenu.hidden = true;
    if (add) add.setAttribute('aria-expanded', 'false');
    this.menuIsOpen = false;
  },

  // / Which box needs attention next, & which controls can be used.
  // / Files come first, then the workflow, then running it. Nothing is highlighted while a workflow runs or when none can.
  // / A workflow with a Select step finds files of its own, so it needs none ticked.
  drawControls: function () {
    var self = this;
    var fileCount = (self.listSelection() || []).length;
    var building = self.isBuilding();
    var entry = self.chosen();
    var isReady = (self.libraryState === 'ready') && (building || self.library.length > 0);
    var isIdle = (self.jobState === 'idle');
    var needsFiles = building ? !self.hasSelectStep() : !(entry && entry.NeedsSelection === false);
    var hasWorkflow = building ? self.isComplete() : !!entry;
    var attention = '';
    var select = self.element('select');
    var run = self.element('run');
    var stop = self.element('stop');
    var add = self.element('add');
    var boxes = self.element('boxes');
    if (isReady && isIdle) attention = (needsFiles && fileCount === 0) ? 'filesBox' : (!hasWorkflow ? (building ? 'step' : 'pickBox') : 'runBox');
    ['filesBox', 'pickBox', 'runBox'].forEach(function (name) {
      var box = self.element(name);
      if (box) box.classList.toggle('hrc2-wf-attention', name === attention); });
    if (boxes) Array.prototype.forEach.call(boxes.children, function (box) {
      if (box.classList.contains('hrc2-wf-step')) box.classList.toggle('hrc2-wf-attention', attention === 'step' && box.classList.contains('hrc2-wf-step-empty')); });
    if (select) select.disabled = !(isReady && isIdle);
    if (run) {
      run.disabled = !(isReady && isIdle && hasWorkflow && (fileCount > 0 || !needsFiles));
      run.hidden = !isIdle; }
    if (stop) {
      stop.hidden = isIdle;
      stop.disabled = (self.jobState === 'stopping'); }
    if (add) add.disabled = !(building && isIdle && (self.emptyIndex() !== -1 || self.steps.length < self.maxSteps));
  },

  // / Write lines into the status, each as text.
  showLines: function (lines) {
    var self = this;
    var status = self.element('status');
    if (!status) return;
    self.clear(status);
    status.removeAttribute('data-hrc2-result');
    status.removeAttribute('data-hrc2-notice');
    (lines || []).forEach(function (line) { if (line !== '') status.appendChild(self.make('p', line)); });
  },

  // / Say something that stays until the panel has something else to say, such as why a workflow was refused & every problem the server named.
  // / A problem is the server's own words, & is shown as it wrote them, as text.
  showNotice: function (headline, problems) {
    var status = this.element('status');
    var lines = [headline];
    if (this.isList(problems)) problems.slice(0, 12).forEach(function (problem) { if (typeof problem === 'string') lines.push(problem); });
    this.showLines(lines);
    if (status) status.setAttribute('data-hrc2-notice', 'true');
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
    var made = (reply && self.isList(reply.Produced)) ? reply.Produced.filter(function (name) { return typeof name === 'string' && name !== ''; }) : [];
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
  // / Import & export.
  // / A workflow is saved as the server writes it, & a file is opened only once the server has checked it.
  // / -------------------------------------------------------------------------------

  // / Hand the browser a file holding text, as a download of the name given.
  saveText: function (fileText, fileName) {
    var blob = new Blob([String(fileText)], { type: 'application/json' });
    var link = document.createElement('a');
    var address = URL.createObjectURL(blob);
    link.href = address;
    link.download = String(fileName || 'Workflow-workflow.json');
    link.hidden = true;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.setTimeout(function () { URL.revokeObjectURL(address); }, 1000);
  },

  // / Export a workflow from the library, as the library holds it.
  exportLibraryWorkflow: function (workflowId) {
    var self = this;
    self.request([['workflowExport', workflowId]], function (reply) {
      if (!reply || reply.Available !== true) {
        self.becomeUnavailable();
        return; }
      if (reply.Allowed === true && reply.Found === true && typeof reply.Document === 'string') self.saveText(reply.Document, reply.FileName);
      else self.showNotice(self.text.refused.unknown, []); });
  },

  // / Export what is built. A library's workflow left as it was is exported by its identifier, & anything else is checked by the server first.
  exportBuilt: function () {
    var self = this;
    if (!self.isComplete()) return;
    if (self.matchesLibrary()) {
      self.exportLibraryWorkflow(self.sourceId);
      return; }
    self.request([['workflowCheck', self.documentText()]], function (reply) {
      if (!reply || reply.Available !== true) {
        self.becomeUnavailable();
        return; }
      if (reply.Allowed === true && reply.Valid === true && typeof reply.Document === 'string') self.saveText(reply.Document, reply.FileName);
      else self.showNotice((reply.Allowed === true) ? self.text.notWorkflow : self.text.refused.builder, reply.Problems); });
  },

  // / Ask the browser for a workflow file. The box that asks is made once & reused.
  chooseImport: function () {
    var self = this;
    if (!self.importer) {
      self.importer = document.createElement('input');
      self.importer.type = 'file';
      self.importer.accept = '.json,application/json';
      self.importer.hidden = true;
      self.importer.id = 'hrc2WfImportFile';
      self.importer.addEventListener('change', function () {
        var chosenFile = (self.importer.files && self.importer.files.length > 0) ? self.importer.files[0] : null;
        self.importer.value = '';
        if (chosenFile) self.readImport(chosenFile); });
      (self.element('panel') || document.body).appendChild(self.importer); }
    self.importer.click();
  },

  // / Read a workflow file the user chose, & open it once the server says it can run here.
  // / A file larger than a workflow may be is refused before it is read.
  readImport: function (chosenFile) {
    var self = this;
    var reader = null;
    if (chosenFile.size > 65536 || typeof window.FileReader !== 'function') {
      self.showNotice(self.text.unreadable, []);
      return; }
    reader = new window.FileReader();
    reader.onerror = function () { self.showNotice(self.text.unreadable, []); };
    reader.onload = function () {
      self.request([['workflowCheck', String(reader.result || '')]], function (reply) {
        if (!reply || reply.Available !== true) {
          self.becomeUnavailable();
          return; }
        if (reply.Allowed !== true) self.showNotice(self.text.refused.builder, []);
        else if (reply.Valid !== true || reply.Usable !== true || !self.loadDocument(reply.Document, '')) self.showNotice(self.text.notWorkflow, reply.Problems);
        else {
          self.showLines([]);
          self.drawAll(); } }); };
    reader.readAsText(chosenFile);
  },

  // / Open a workflow from the library in the builder, from the document the server gives for it.
  openLibraryWorkflow: function (workflowId) {
    var self = this;
    if (self.jobState !== 'idle') return;
    self.request([['workflowExport', workflowId]], function (reply) {
      if (!reply || reply.Available !== true) {
        self.becomeUnavailable();
        return; }
      if (reply.Allowed === true && reply.Found === true && self.loadDocument(reply.Document, workflowId)) {
        self.showLines([]);
        self.drawAll(); }
      else self.showNotice(self.text.refused.unknown, []); });
  },

  // / -------------------------------------------------------------------------------
  // / Running.
  // / -------------------------------------------------------------------------------

  // / Run the chosen or built workflow on the ticked files, in the order they were ticked.
  // / A library's workflow left as it was is run by its identifier, & anything built is sent as a workflow for the server to check.
  run: function () {
    var self = this;
    var entry = self.chosen();
    var fileNames = self.listSelection() || [];
    var fields = [];
    if (self.jobState !== 'idle') return;
    if (self.isBuilding()) {
      if (!self.isComplete() || (fileNames.length === 0 && !self.hasSelectStep())) return;
      if (self.matchesLibrary()) fields.push(['workflowRun', self.sourceId]);
      else fields.push(['workflowDocument', self.documentText()]); }
    else {
      if (!entry || (fileNames.length === 0 && entry.NeedsSelection)) return;
      fields.push(['workflowRun', entry.Id]); }
    fileNames.forEach(function (fileName) { fields.push(['workflowFiles[]', fileName]); });
    self.jobState = 'starting';
    self.openStep = -1;
    self.closeMenu();
    self.drawAll();
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
      self.drawAll();
      if (String(reply.Reason) === 'invalid') self.showNotice(self.text.refused.invalid, reply.Problems);
      else self.showLines([self.text.refused.hasOwnProperty(String(reply.Reason)) ? self.text.refused[String(reply.Reason)] : self.text.refused.start]);
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
  // / The builder is drawn again whenever a workflow starts or ends, because its controls are locked while one runs.
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
      if (!wasWaiting) {
        self.drawPipelines();
        self.drawSteps(); }
      self.drawControls();
      self.drawProgress(reply);
      self.schedulePoll(1500);
      return; }
    if (state === 'finished' || state === 'stopped' || state === 'failed' || state === 'interrupted') {
      self.jobState = 'idle';
      if (isFirstLook && !self.isOpen()) self.open();
      self.showResult(self.endingLine(reply), reply);
      self.drawPipelines();
      self.drawSteps();
      self.drawControls();
      self.filesChanged();
      return; }
    // / No workflow. One this page was waiting on was cleared by something else, so the list is drawn again in case it made files.
    self.jobState = 'idle';
    if (wasWaiting) {
      self.showLines([]);
      self.drawPipelines();
      self.drawSteps();
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
    this.buildState = 'unavailable';
    this.catalog = [];
    this.canExport = false;
    this.drawAll();
  }
};
// / -----------------------------------------------------------------------------------
