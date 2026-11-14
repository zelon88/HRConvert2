// / -----------------------------------------------------------------------------------
// / COPYRIGHT INFORMATION ...
// / HRConvert2, Copyright on 11/13/2026 by Justin Grimes, www.github.com/zelon88
// /
// / LICENSE INFORMATION ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / FILE INFORMATION ...
// / v4.2.0.
// / The page's half of signing in. The server's half is the Engine's Authentication Core.
// / A signed in page holds a key in its memory & in window.name, & nowhere a browser keeps for a site, so no cookie is ever set.
// / window.name belongs to the tab. It is never sent to a server, it survives a reload & a link followed in the same tab, & it goes
// / when the tab closes. A browser clears it when the tab goes to another site, & this file clears it when the user signs out.
// / The key rides on every request the page sends to the core, & is traded for a new one on a timer, which spends it.
// / The timer runs in a worker where the browser has them, because a browser slows a hidden tab's own timers to once a minute,
// / & a key that outlives no exchange would end the sign in of anybody who looked away.
// / A page that loads without being signed in, with a key in window.name, trades it & loads again signed in. That is a reload.
// / Every string the user reads is handed in by the page, because this file is static & cannot read a language pack.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------

var HRC2SignIn = {
  endpoint: 'convertCore.php',
  prefix: 'HRC2-SIGNIN:',
  key: '',
  life: 30,
  interval: 6,
  signedIn: false,
  waiting: null,
  ticker: null,
  worker: null,
  hooked: false,
  tokens: { Token1: '', Token2: '' },
  messages: {},
  onOutcome: null,

  // / Read the key a page before this one left in the tab, or an empty string. A revival marker rides beside it.
  stored: function () {
    var held = String(window.name || '');
    var found = held.indexOf(this.prefix) === 0 ? held.substring(this.prefix.length) : '';
    return /^[a-f0-9]{96}(:R)?$/.test(found) ? found : '';
  },

  // / Keep the key in memory & in the tab, or forget it in both.
  keep: function (key, reviving) {
    this.key = key || '';
    try { window.name = this.key ? this.prefix + this.key + (reviving ? ':R' : '') : ''; }
    catch (thrown) { /* A tab that refuses window.name loses its key on a reload, & nothing else. */ }
  },

  // / Say whether a request goes to this page's own core, which is the only place the key is ever sent.
  ownsAddress: function (address) {
    var resolver = document.createElement('a');
    resolver.href = String(address || '');
    return resolver.host === window.location.host && /convertCore\.php$/.test(resolver.pathname);
  },

  // / Find the line of JSON an answer carries under SignIn, as the Workflows library finds its own.
  readReply: function (replyText) {
    var lines = String(replyText || '').split(/\r?\n/);
    var index, parsed;
    for (index = lines.length - 1; index >= 0; index--) {
      try { parsed = JSON.parse(lines[index].trim()); }
      catch (thrown) { parsed = null; }
      if (parsed && typeof parsed === 'object' && parsed.SignIn && typeof parsed.SignIn === 'object') return parsed.SignIn;
    }
    return null;
  },

  // / Send one of the core's sign in actions & hand its answer back, or null when there is no answer this can read.
  // / It never carries the key the hook below would add, because an action names the key it means itself.
  ask: function (fields, onReply) {
    var request = new XMLHttpRequest();
    var body = 'Token1=' + encodeURIComponent(this.tokens.Token1 || '') + '&Token2=' + encodeURIComponent(this.tokens.Token2 || '');
    var self = this;
    var answered = false;
    var answer = function (reply) { if (!answered) { answered = true; onReply(reply); } };
    fields.forEach(function (field) { body += '&' + encodeURIComponent(field[0]) + '=' + encodeURIComponent(field[1]); });
    request.hrc2Internal = true;
    request.open('POST', this.endpoint, true);
    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    request.timeout = 30000;
    request.onload = function () { answer(request.status === 200 ? self.readReply(request.responseText) : null); };
    request.onerror = function () { answer(null); };
    request.ontimeout = function () { answer(null); };
    request.send(body);
  },

  // / Trade the key for a new one. Every caller waiting on a trade already under way is answered by that one.
  exchange: function (onDone) {
    var self = this;
    if (this.waiting) { if (onDone) this.waiting.push(onDone); return; }
    if (!this.key) { if (onDone) onDone(false); return; }
    this.waiting = onDone ? [onDone] : [];
    this.ask([['SignInAction', 'exchange'], ['SignInKey', this.key.replace(/:R$/, '')]], function (reply) {
      var waiters = self.waiting || [];
      var traded = !!(reply && reply.Outcome === 'exchanged' && /^[a-f0-9]{96}$/.test(String(reply.Key || '')));
      self.waiting = null;
      // / No answer at all is a network that failed for a moment, & the key is kept for the next tick while it lives.
      if (traded) self.keep(reply.Key, false);
      else if (reply) self.end(reply.Outcome);
      waiters.forEach(function (waiter) { waiter(traded); });
    });
  },

  // / The sign in is over. The key is forgotten, the timer stops & the page is told why.
  end: function (outcome) {
    this.keep('', false);
    this.signedIn = false;
    if (this.ticker) { clearInterval(this.ticker); this.ticker = null; }
    if (this.worker) { this.worker.postMessage(0); }
    if (this.onOutcome) this.onOutcome(outcome || 'signed-out');
  },

  // / Start the timer, in a worker where there is one. A tab coming back into view trades at once.
  tick: function (workerPath) {
    var self = this;
    var trade = function () { self.exchange(null); };
    try {
      if (workerPath && typeof Worker !== 'undefined') {
        this.worker = new Worker(workerPath);
        this.worker.onmessage = trade;
        this.worker.postMessage(this.interval);
      }
    }
    catch (thrown) { this.worker = null; }
    if (!this.worker) this.ticker = setInterval(trade, this.interval * 1000);
    document.addEventListener('visibilitychange', function () { if (document.visibilityState === 'visible' && self.key) trade(); });
  },

  // / Add the key to every request this page sends its core, a form posted, a request a script sends & an upload alike.
  // / A form waits for a trade already under way, so a page that is left carries the newest key.
  hook: function () {
    var self = this;
    var opener = XMLHttpRequest.prototype.open;
    var sender = XMLHttpRequest.prototype.send;
    var submitter = HTMLFormElement.prototype.submit;
    var stamp = function (form) {
      var field = form.querySelector('input[name="SignInKey"]');
      if (!self.key) { if (field) field.parentNode.removeChild(field); return; }
      if (!field) { field = document.createElement('input'); field.type = 'hidden'; field.name = 'SignInKey'; form.appendChild(field); }
      field.value = self.key.replace(/:R$/, '');
    };
    if (this.hooked) return;
    this.hooked = true;
    XMLHttpRequest.prototype.open = function (method, address) { this.hrc2Address = address; return opener.apply(this, arguments); };
    XMLHttpRequest.prototype.send = function (body) {
      var key = self.key.replace(/:R$/, '');
      if (!this.hrc2Internal && key && self.ownsAddress(this.hrc2Address)) {
        if (typeof FormData !== 'undefined' && body instanceof FormData) { if (!body.has || !body.has('SignInKey')) body.append('SignInKey', key); }
        else if (typeof body === 'string') body = body + (body === '' ? '' : '&') + 'SignInKey=' + encodeURIComponent(key);
      }
      return sender.call(this, body);
    };
    HTMLFormElement.prototype.submit = function () { if (self.ownsAddress(this.action)) stamp(this); return submitter.call(this); };
    document.addEventListener('submit', function (event) {
      var form = event.target;
      if (!form || !self.key || !self.ownsAddress(form.action)) return;
      if (self.waiting) {
        event.preventDefault();
        self.waiting.push(function () { stamp(form); submitter.call(form); });
        return;
      }
      stamp(form);
    }, true);
  },

  // / Go to a page of the core by posting to it, carrying the session, the key & any fields given.
  go: function (address, fields) {
    var form = document.createElement('form');
    var all = [['Token1', this.tokens.Token1 || ''], ['Token2', this.tokens.Token2 || '']].concat(fields || []);
    form.method = 'post';
    form.action = address || this.endpoint;
    form.style.display = 'none';
    all.forEach(function (field) {
      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = field[0];
      input.value = field[1];
      form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
  },

  // / Sign out, & go home without a key.
  signOut: function (home) {
    var self = this;
    var key = this.key.replace(/:R$/, '');
    this.ask([['SignInAction', 'signout'], ['SignInKey', key]], function () { self.end('signed-out-quietly'); self.go(home, []); });
  },

  // / Begin, once the page has said whether it is signed in. options carries signedIn, life, interval, tokens, workerPath, home & notices.
  // / The bar's buttons are bound here, & a sign in that ends says why in the bar & goes home, where the server decides what is shown.
  // / A page that is not signed in with a key in its tab trades it once & loads again, & gives up if it comes back unsigned a second time.
  start: function (options) {
    var stored = this.stored();
    var self = this;
    var home = options.home || this.endpoint;
    var notices = options.notices || {};
    var signOutButton = document.getElementById('hrc2SignOut');
    var signInButton = document.getElementById('hrc2SignInLink');
    if (signOutButton) signOutButton.addEventListener('click', function () { self.signOut(home); });
    if (signInButton) signInButton.addEventListener('click', function () { self.go(home, [['SignInAction', 'page'], ['SignInPage', 'signin']]); });
    if (!options.onOutcome) options.onOutcome = function (outcome) {
      var bar = document.getElementById('hrc2SignInBar');
      if (outcome === 'signed-out-quietly') return;
      if (bar) bar.textContent = notices[outcome] || notices['signed-out'] || '';
      setTimeout(function () { self.go(home, []); }, 1500);
    };
    this.signedIn = !!options.signedIn;
    this.life = Number(options.life) || 30;
    this.interval = Number(options.interval) || 6;
    this.tokens = options.tokens || this.tokens;
    this.onOutcome = options.onOutcome || null;
    this.key = stored;
    this.hook();
    if (this.signedIn && stored) {
      this.keep(stored.replace(/:R$/, ''), false);
      this.tick(options.workerPath);
      this.exchange(null);
    }
    else if (!this.signedIn && stored && !/:R$/.test(stored)) {
      this.keep(stored, false);
      this.exchange(function (traded) {
        if (!traded) return;
        self.keep(self.key, true);
        self.go(window.location.href, []);
      });
    }
    else if (!this.signedIn && stored) this.keep('', false);
  }
};

// / The sign in page's behaviour. engineGui.php supplies the values, & this supplies what the forms do.
// / Every form is sent by a script, so a key comes back to the page that asked for it & never travels in a page.
var HRC2SignInPage = {
  messages: {},
  home: 'convertCore.php',

  // / Show one of the forms, & carry the username already typed into it.
  show: function (page) {
    var typed = '';
    document.querySelectorAll('input[name="SignInUsername"]').forEach(function (field) { if (field.value) typed = field.value; });
    document.querySelectorAll('[data-hrc2-page]').forEach(function (section) { section.hidden = (section.getAttribute('data-hrc2-page') !== page); });
    document.querySelectorAll('[data-hrc2-page="' + page + '"] input[name="SignInUsername"]').forEach(function (field) { if (!field.value) field.value = typed; });
  },

  // / Say what happened, in the page's own language. An answer this cannot read says the server did not answer, & nothing clears it.
  say: function (outcome) {
    var box = document.getElementById('signInMessage');
    if (!box) return;
    box.textContent = (outcome === '') ? '' : (this.messages[outcome] || this.messages['no-answer'] || '');
    box.hidden = (box.textContent === '');
  },

  // / Act on what the core answered. A key goes into the tab & the page goes home with it.
  answer: function (reply) {
    var outcome = reply ? String(reply.Outcome || '') : 'no-answer';
    if (outcome === 'signed-in' && reply && /^[a-f0-9]{96}$/.test(String(reply.Key || ''))) {
      HRC2SignIn.keep(reply.Key, false);
      HRC2SignIn.go(this.home, []);
      return;
    }
    this.say(outcome);
    if (outcome === 'registered-confirm') this.show('confirm');
    else if (outcome === 'recovery-sent') this.show('reset');
    else if (outcome === 'confirmed' || outcome === 'registered-ready' || outcome === 'reset-done') this.show('signin');
  },

  // / options carries page, home, tokens & messages.
  configure: function (options) {
    var self = this;
    this.messages = options.messages || {};
    this.home = options.home || this.home;
    HRC2SignIn.tokens = options.tokens || HRC2SignIn.tokens;
    document.querySelectorAll('form[data-hrc2-action]').forEach(function (form) {
      form.addEventListener('submit', function (event) {
        var fields = [['SignInAction', form.getAttribute('data-hrc2-action')]];
        var button = form.querySelector('button[type="submit"]');
        var typed = form.parentNode.querySelector('input[type="text"][name="SignInUsername"]');
        event.preventDefault();
        event.stopPropagation();
        // / A form that only sends again carries the username typed into the form beside it.
        form.querySelectorAll('input[type="hidden"][name="SignInUsername"]').forEach(function (input) { if (typed) input.value = typed.value; });
        form.querySelectorAll('input[name^="SignIn"]').forEach(function (input) { fields.push([input.name, input.value]); });
        if (button) button.disabled = true;
        HRC2SignIn.ask(fields, function (reply) {
          if (button) button.disabled = false;
          form.querySelectorAll('input[type="password"]').forEach(function (input) { input.value = ''; });
          self.answer(reply);
        });
      });
    });
    document.querySelectorAll('[data-hrc2-show]').forEach(function (link) {
      link.addEventListener('click', function (event) { event.preventDefault(); self.say(''); self.show(link.getAttribute('data-hrc2-show')); });
    });
    document.querySelectorAll('[data-hrc2-home]').forEach(function (link) {
      link.addEventListener('click', function (event) { event.preventDefault(); HRC2SignIn.go(self.home, []); });
    });
    this.show(options.page || 'signin');
    if (options.notice) this.say(options.notice);
  }
};
