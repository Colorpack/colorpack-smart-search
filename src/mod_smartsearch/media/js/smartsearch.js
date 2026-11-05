/* Colorpack Smart Search module: live suggestions as an accessible combobox. No dependencies. */
(function () {
  'use strict';
  var SEARCH_ICON = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.6-3.6"/></svg>';
  var DOC_ICON = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h6"/></svg>';

  function el(tag, cls, text) {
    var n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text != null) n.textContent = text;
    return n;
  }

  function highlight(target, text, q) {
    var i = q ? text.toLowerCase().indexOf(q.toLowerCase()) : -1;
    if (i < 0) { target.textContent = text; return; }
    target.appendChild(document.createTextNode(text.slice(0, i)));
    target.appendChild(el('mark', '', text.slice(i, i + q.length)));
    target.appendChild(document.createTextNode(text.slice(i + q.length)));
  }

  function init(root) {
    var form = root.querySelector('form'), input = root.querySelector('.ssm-input'), pop = root.querySelector('.ssm-pop');
    var clearBtn = root.querySelector('.ssm-clear'), status = root.querySelector('.ssm-sr');
    var base = root.getAttribute('data-suggest'), n = parseInt(root.getAttribute('data-n'), 10) || 6;
    var live = root.getAttribute('data-live') === '1', usePopular = root.getAttribute('data-popular') === '1';
    var T = { searching: root.getAttribute('data-t-searching') || '', popular: root.getAttribute('data-t-popular'), all: root.getAttribute('data-t-all'), none: root.getAttribute('data-t-none'), count: root.getAttribute('data-t-count') };
    var opts = [], active = -1, ctrl = null, timer = null, popularCache = null, seq = 0;

    function setOpen(open) {
      pop.hidden = !open;
      input.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (!open) { active = -1; input.removeAttribute('aria-activedescendant'); }
    }
    function setActive(i) {
      if (active >= 0 && opts[active]) opts[active].setAttribute('aria-selected', 'false');
      active = i;
      if (i >= 0 && opts[i]) {
        opts[i].setAttribute('aria-selected', 'true');
        input.setAttribute('aria-activedescendant', opts[i].id);
        opts[i].scrollIntoView({ block: 'nearest' });
      } else {
        input.removeAttribute('aria-activedescendant');
      }
    }
    function submitText(text) { input.value = text; syncClear(); form.requestSubmit ? form.requestSubmit() : form.submit(); }

    function addOption(node, idx) {
      node.id = pop.id + '-o' + idx;
      node.setAttribute('role', 'option');
      node.setAttribute('aria-selected', 'false');
      node.addEventListener('mousemove', function () { if (active !== idx) setActive(idx); });
      opts.push(node);
      pop.appendChild(node);
    }

    function renderPopular(list) {
      pop.textContent = ''; opts = []; active = -1;
      if (!list.length) { setOpen(false); return; }
      pop.appendChild(el('div', 'ssm-head', T.popular));
      list.forEach(function (w, i) {
        var b = el('button', 'ssm-opt'); b.type = 'button';
        var ic = el('span', 'ssm-ico'); ic.innerHTML = SEARCH_ICON;
        b.appendChild(ic); b.appendChild(el('span', 'ssm-t', w));
        b.addEventListener('click', function () { submitText(w); });
        addOption(b, i);
      });
      setOpen(true);
      status.textContent = list.length + ' ' + T.count;
    }

    function renderResults(items, q) {
      pop.textContent = ''; opts = []; active = -1;
      if (!items.length) {
        pop.appendChild(el('div', 'ssm-empty', T.none));
      }
      items.forEach(function (d, i) {
        var a = el('a', 'ssm-opt'); a.href = d.u;
        var ic = el('span', 'ssm-ico'); ic.innerHTML = DOC_ICON;
        var t = el('span', 'ssm-t'); highlight(t, d.t, q);
        a.appendChild(ic); a.appendChild(t);
        if (d.c) a.appendChild(el('span', 'ssm-c', d.c));
        addOption(a, i);
      });
      var all = el('button', 'ssm-opt ssm-all'); all.type = 'button';
      var ic2 = el('span', 'ssm-ico'); ic2.innerHTML = SEARCH_ICON;
      all.appendChild(ic2); all.appendChild(el('span', 'ssm-t', T.all.replace('%s', q)));
      all.addEventListener('click', function () { submitText(q); });
      addOption(all, items.length);
      setOpen(true);
      status.textContent = items.length ? items.length + ' ' + T.count : T.none;
    }

    function fetchJson(url) {
      if (ctrl) ctrl.abort();
      ctrl = typeof AbortController !== 'undefined' ? new AbortController() : null;
      return fetch(url, { credentials: 'same-origin', signal: ctrl ? ctrl.signal : undefined }).then(function (r) { return r.json(); });
    }

    function showPopular() {
      if (!usePopular) { setOpen(false); return; }
      if (popularCache) { renderPopular(popularCache); return; }
      var my = ++seq;
      fetchJson(base + '&n=' + n).then(function (j) {
        popularCache = (j && j.popular) || [];
        if (my === seq && document.activeElement === input && input.value.trim().length < 2) renderPopular(popularCache);
      }).catch(function () {});
    }

    function update() {
      var q = input.value.trim();
      clearTimeout(timer);
      if (q.length < 2) { showPopular(); return; }
      if (!live) { setOpen(false); return; }
      var my = ++seq;
      timer = setTimeout(function () {
        fetchJson(base + '&n=' + n + '&q=' + encodeURIComponent(q)).then(function (j) {
          if (my !== seq || input.value.trim() !== q) return;
          renderResults((j && j.items) || [], q);
        }).catch(function () {});
      }, 180);
    }

    function syncClear() { clearBtn.hidden = input.value === ''; }

    input.addEventListener('input', function () { syncClear(); update(); });
    input.addEventListener('focus', function () { if (pop.hidden) update(); });
    input.addEventListener('keydown', function (ev) {
      var k = ev.key;
      if (k === 'ArrowDown' || k === 'ArrowUp') {
        if (pop.hidden) { update(); return; }
        if (!opts.length) return;
        ev.preventDefault();
        var d = k === 'ArrowDown' ? 1 : -1, i = active + d;
        if (i < 0) i = opts.length - 1; else if (i >= opts.length) i = 0;
        setActive(i);
      } else if (k === 'Enter') {
        if (!pop.hidden && active >= 0 && opts[active]) { ev.preventDefault(); opts[active].click(); }
      } else if (k === 'Escape') {
        if (!pop.hidden) { ev.preventDefault(); setOpen(false); }
      } else if (k === 'Tab') {
        setOpen(false);
      }
    });
    clearBtn.addEventListener('click', function () { input.value = ''; syncClear(); input.focus(); update(); });
    document.addEventListener('pointerdown', function (ev) { if (!root.contains(ev.target)) setOpen(false); });
    // a search (or a click on a suggestion) leaves the page: show that something is happening until the next page arrives
    function busy() {
      root.classList.add('ssm-busy');
      form.setAttribute('aria-busy', 'true');
      status.textContent = T.searching;
      if (!document.getElementById('ssb-bar')) {
        var b = document.createElement('div'); b.id = 'ssb-bar'; b.setAttribute('role', 'progressbar'); b.setAttribute('aria-label', T.searching);
        b.appendChild(document.createElement('i')); document.body.appendChild(b);
      }
      pop.textContent = ''; opts = []; active = -1;
      pop.appendChild(el('div', 'ssm-empty ssm-working', T.searching));
      pop.hidden = false;
    }
    form.addEventListener('submit', function () { setTimeout(function () { busy(); }, 0); });
    pop.addEventListener('click', function (ev) { var a = ev.target.closest && ev.target.closest('a.ssm-opt'); if (a) busy(); });
    window.addEventListener('pageshow', function (e) {
      if (e.persisted) { root.classList.remove('ssm-busy'); form.removeAttribute('aria-busy'); setOpen(false); var bb = document.getElementById('ssb-bar'); if (bb) bb.remove(); }
    });
    syncClear();

    // inside a YOOtheme / UIkit modal or drop (the header search): focus the box when it opens, close the list when it closes
    var host = root.closest('.uk-modal, .uk-drop');
    if (host) {
      host.addEventListener('shown', function () { input.focus(); });
      host.addEventListener('hidden', function () { setOpen(false); });
    }
  }

  function boot() { document.querySelectorAll('[data-ssm]').forEach(init); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
