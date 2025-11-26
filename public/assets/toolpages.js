/* toolpages system-wide JavaScript */
(function(){
  'use strict';

  // Helpers
  function getMeta(name){
    var el = document.querySelector('meta[name="' + name + '"]');
    return el ? el.getAttribute('content') : '';
  }

  // Endpoints from head meta
  var PING_ENDPOINT = getMeta('tp:ping') || '/ping';
  var REORDER_ENDPOINT = getMeta('tp:reorder') || '/dashboard/reorder';

  // --- BasePath helper (for reverse proxy subpaths) -----------------------
  function getBasePath(){
    try {
      var b = document.querySelector('base');
      if (b) {
        var href = b.getAttribute('href') || '/';
        var u = new URL(href, window.location.href);
        return u.pathname.replace(/\/$/, '');
      }
    } catch(_){}
    return '';
  }
  var BASE_PATH = getBasePath();

  // --- Ping logic ---------------------------------------------------------
  var DOT_OK = 'ok', DOT_ERR = 'err';
  function doPing(url, timeoutMs){
    return new Promise(function(resolve, reject){
      try {
        var ctrl = new AbortController();
        var timer = setTimeout(function(){ try{ctrl.abort();}catch(_){} reject(new Error('timeout')); }, timeoutMs);
        var qp = new URLSearchParams({ u: url });
        fetch(PING_ENDPOINT + '?' + qp.toString(), {
          method: 'GET',
          cache: 'no-store',
          credentials: 'same-origin',
          signal: ctrl.signal,
          headers: { 'Accept': 'application/json' }
        }).then(function(r){ return r.json().catch(function(){ return {}; }); })
          .then(function(data){ clearTimeout(timer); if (data && data.ok) resolve(true); else reject(new Error('bad')); })
          .catch(function(){ clearTimeout(timer); reject(new Error('fetch')); });
      } catch(e) { reject(e); }
    });
  }

    async function runPing() {
        const nodes = [...document.querySelectorAll('[data-ping-url]')];

        const limit = 5;
        let running = 0;
        let queue = [];

        function start(el) {
            running++;
            const url = el.getAttribute('data-ping-url');
            const dot = el.querySelector('.tp-ping');

            doPing(url, 3500)
                .then(() => {
                    dot.classList.remove(DOT_ERR);
                    dot.classList.add(DOT_OK);
                })
                .catch(() => {
                    dot.classList.remove(DOT_OK);
                    dot.classList.add(DOT_ERR);
                })
                .finally(() => {
                    running--;
                    if (queue.length > 0) start(queue.shift());
                });
        }

        nodes.forEach(el => {
            if (running < limit) start(el);
            else queue.push(el);
        });
    }

  // --- Tile click navigation ---------------------------------------------
  function enableTileClicks(){
    document.querySelectorAll('.tp-tile[data-href]').forEach(function(el){
      if (!el.hasAttribute('tabindex')) el.setAttribute('tabindex', '0');
      if (!el.hasAttribute('role')) el.setAttribute('role', 'link');
    });
    function isInteractive(target){
      return !!(target && target.closest('a, button, input, select, textarea, label, [data-bs-toggle], .dropdown-menu, .modal, form'));
    }
    document.addEventListener('click', function(ev){
      var target = ev.target;
      var tile = target && target.closest('.tp-tile[data-href]');
      if (!tile) return;
      if (isInteractive(target)) return;
      var href = tile.getAttribute('data-href');
      if (!href) return;
      try { window.open(href, '_blank', 'noopener'); } catch(e) {}
      ev.preventDefault();
    });
    document.addEventListener('keydown', function(ev){
      if (ev.key !== 'Enter' && ev.key !== ' ') return;
      var tile = ev.target && ev.target.closest('.tp-tile[data-href]');
      if (!tile) return;
      var href = tile.getAttribute('data-href');
      if (!href) return;
      try { window.open(href, '_blank', 'noopener'); } catch(e) {}
      ev.preventDefault();
    });
  }

  // --- Drag & Drop reordering --------------------------------------------
  function initSortableTiles(){
    try {
      var containers = document.querySelectorAll('[data-sortable="1"][data-category]');
      if (!containers.length) return;

      var draggingTile = null;
      var draggingCol = null;
      var activeRow = null;

      function getCol(el){ return el ? el.closest('[class*="col-"]') : null; }
      function getRow(el){ return el ? el.closest('[data-sortable="1"][data-category]') : null; }

      function serializeAndSend(row){
        if (!row) return;
        var category = row.getAttribute('data-category') || '';
        var ids = [];
        row.querySelectorAll('.tp-tile[data-tile-id]').forEach(function(tile){
          var id = parseInt(tile.getAttribute('data-tile-id')||'0', 10);
          if (id > 0) ids.push(id);
        });
        if (!ids.length) return;
        var body = new URLSearchParams();
        body.set('category', category);
        ids.forEach(function(id){ body.append('ids[]', String(id)); });
        fetch(REORDER_ENDPOINT, { method: 'POST', credentials: 'same-origin', body: body })
          .then(function(r){
            if (!r.ok) { return r.json().catch(function(){ return {}; }).then(function(j){ throw new Error(j && j.error || ('HTTP ' + r.status)); }); }
            return r.json().catch(function(){ return { ok: true }; });
          })
          .catch(function(err){
            try {
              console.error('Reorder failed:', err && err.message ? err.message : err);
              // brief user feedback without blocking UX
              var alert = document.createElement('div');
              alert.className = 'alert alert-warning position-fixed top-0 end-0 m-3';
              alert.style.zIndex = 1080;
              alert.textContent = 'Saving order failed. Please try again.';
              document.body.appendChild(alert);
              setTimeout(function(){ try{document.body.removeChild(alert);}catch(_){} }, 2500);
            } catch (_) {}
          });
      }

      function onDragStart(e){
        var tile = e.currentTarget;
        draggingTile = tile;
        draggingCol = getCol(tile);
        activeRow = getRow(tile);
        try { e.dataTransfer.effectAllowed = 'move'; e.dataTransfer.setData('text/plain', tile.getAttribute('data-tile-id')||''); } catch(_){ }
        tile.classList.add('opacity-50');
      }
      function onDragEnd(e){
        var tile = e.currentTarget;
        tile.classList.remove('opacity-50');
        // After drag ends, persist order of the row where the tile ended up
        var row = getRow(tile) || activeRow;
        serializeAndSend(row);
        draggingTile = null;
        draggingCol = null;
        activeRow = null;
      }
      function onDragOver(e){
        e.preventDefault();
        var overTile = e.target.closest('.tp-tile');
        if (!overTile || !draggingCol) return;
        var overCol = getCol(overTile);
        if (!overCol || overCol === draggingCol) return;
        var row = overCol.parentElement; // .row g-3
        var overRect = overCol.getBoundingClientRect();
        var before = (e.clientY - overRect.top) < (overRect.height / 2);
        if (before) row.insertBefore(draggingCol, overCol); else row.insertBefore(draggingCol, overCol.nextSibling);
      }

      containers.forEach(function(row){
        row.addEventListener('dragover', onDragOver);
        row.addEventListener('drop', function(e){ e.preventDefault(); serializeAndSend(row); });
        row.querySelectorAll('.tp-tile[draggable="true"]').forEach(function(tile){
          tile.addEventListener('dragstart', onDragStart);
          tile.addEventListener('dragend', onDragEnd);
        });
      });
    } catch(e) { /* noop */ }
  }

  // --- Plugin Registry (Phase 2/3) ----------------------------------------
  var pluginRegistry = new Map(); // type -> constructor/factory
  var loadedPlugins = []; // manifests
  var tileDefsByType = new Map(); // type -> { plugin, tile }

  function createTileContext(){
    function openLink(url, target){
      try { window.open(url, target === 'self' ? '_self' : '_blank', 'noopener'); } catch(e) {}
    }
    function openModal(node, opts){
      try {
        var modalEl = document.createElement('div');
        modalEl.className = 'modal fade';
        modalEl.innerHTML = '<div class="modal-dialog' + (opts && opts.width ? ' modal-lg' : '') + '"><div class="modal-content">\
          <div class="modal-header">\
            <h5 class="modal-title">' + (opts && opts.title ? String(opts.title) : 'Details') + '</h5>\
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>\
          </div>\
          <div class="modal-body"></div>\
        </div></div>';
        modalEl.querySelector('.modal-body').appendChild(node);
        document.body.appendChild(modalEl);
        var Modal = window.bootstrap && window.bootstrap.Modal;
        var inst = Modal ? new Modal(modalEl) : null;
        if (inst) {
          modalEl.addEventListener('hidden.bs.modal', function(){ modalEl.remove(); });
          inst.show();
        } else {
          // Fallback ohne Bootstrap: simples Dialogfenster
          modalEl.style.display = 'block';
          modalEl.addEventListener('click', function(){ modalEl.remove(); });
        }
      } catch(e){ /* noop */ }
    }
    function wrappedFetch(input, init){
      try {
        var url = (typeof input === 'string') ? input : (input && input.url) || '';
        if (url.startsWith('/')) {
          // Prefix only when BASE_PATH is a real subpath (not '/' or empty),
          // otherwise we would generate protocol-relative URLs like //api/...
          if (BASE_PATH && BASE_PATH !== '/') {
            url = BASE_PATH + url;
          }
        }
        return fetch(url, Object.assign({ credentials: 'same-origin' }, init||{}));
      } catch(e) { return Promise.reject(e); }
    }
    return {
      basePath: BASE_PATH || '/',
      openModal: openModal,
      openLink: openLink,
      fetch: wrappedFetch,
      events: { emit: function(){}, on: function(){ return function(){}; } }
    };
  }

  function getPluginsEndpoint(){
    // Avoid protocol-relative URL when BASE_PATH is '/'
    var prefix = (BASE_PATH && BASE_PATH !== '/') ? BASE_PATH : '';
    return prefix + '/api/plugins';
  }

  function registrar(){
    return {
      registerTile: function(type, ctor){
        if (!type || !ctor) return;
        pluginRegistry.set(String(type), ctor);
      }
    };
  }

  function importEntry(entry, version){
    // entry can be "/plugins/{id}/web/index.js#Export"
    var hashIdx = entry.indexOf('#');
    var url = hashIdx >= 0 ? entry.slice(0, hashIdx) : entry;
    var exportName = hashIdx >= 0 ? entry.slice(hashIdx + 1) : '';
    // If the URL is absolute-path based (starts with '/'), prefix BASE_PATH for reverse-proxy deployments
    if (url && url.charAt(0) === '/' && BASE_PATH && BASE_PATH !== '/') {
      url = BASE_PATH + url;
    }
    var fullUrl = url + (version ? ('?v=' + encodeURIComponent(version)) : '');
    return import(fullUrl).then(function(mod){
      return exportName ? (mod[exportName] || mod.default) : (mod.register || mod.default || mod);
    });
  }

  function loadPlugins(){
    return fetch(getPluginsEndpoint(), { credentials: 'same-origin' })
      .then(function(r){ return r.json(); })
      .then(function(list){
        if (!Array.isArray(list)) return [];
        loadedPlugins = list;
        var tasks = [];
        list.forEach(function(p){
          if (!p || !Array.isArray(p.tiles)) return;
          p.tiles.forEach(function(t){
            if (!t || !t.entry) return;
            if (t.type) {
              try { tileDefsByType.set(String(t.type), { plugin: p, tile: t }); } catch(_) {}
            }
            tasks.push(
              importEntry(t.entry, p.version).then(function(reg){
                try {
                  if (typeof reg === 'function') {
                    // function may be a register(registrar)
                    if (reg.length >= 1) reg(registrar());
                    else {
                      // Treat as constructor for the tile itself under declared type
                      if (t.type) pluginRegistry.set(t.type, reg);
                    }
                  } else if (reg && typeof reg.register === 'function') {
                    reg.register(registrar());
                  }
                } catch(e){ /* ignore module errors to not break page */ }
              }).catch(function(){ /* ignore single plugin load error */ })
            );
          });
        });
        return Promise.allSettled(tasks);
      });
  }

  function getTileDefByType(type){ return tileDefsByType.get(String(type)) || null; }

  function renderPluginTile(type, container, config){
    var Ctor = pluginRegistry.get(type);
    if (!Ctor) return Promise.reject(new Error('Unknown tile type: ' + type));
    try {
      var widget = (typeof Ctor === 'function') ? new Ctor() : Ctor;
      var ctx = createTileContext();
      var p = widget && typeof widget.render === 'function' ? widget.render(container, config||{}, ctx) : null;
      return Promise.resolve(p);
    } catch(e){ return Promise.reject(e); }
  }

  // Render all placeholders for plugin tiles present on the page
  function renderAllPluginPlaceholders(){
    try {
      var nodes = document.querySelectorAll('.tp-plugin[data-plugin-type]');
      if (!nodes || !nodes.length) return;
      nodes.forEach(function(el){
        var type = el.getAttribute('data-plugin-type');
        var raw = el.getAttribute('data-plugin-cfg') || '{}';
        var cfg = {};
        try { cfg = JSON.parse(raw); } catch(_) { cfg = {}; }
        renderPluginTile(type, el, cfg).catch(function(err){
          try { el.textContent = 'Plugin konnte nicht gerendert werden: ' + (err && err.message ? err.message : String(err)); } catch(_) {}
        });
      });
    } catch(_) {}
  }

  // Populate the Plugins tab in AddTileModal (MVP)
  var _pluginTypesLoaded = false;
  function initAddTilePluginsTab(){
    if (_pluginTypesLoaded) return;
    var select = document.getElementById('pluginTypeSelect');
    var cfgField = document.getElementById('pluginConfigField');
    var cfgUI = document.getElementById('pluginConfigUI');
    if (!select || !cfgField) return;
    fetch(getPluginsEndpoint(), { credentials: 'same-origin' })
      .then(function(r){ return r.json(); })
      .then(function(list){
        var options = [];
        var defaultsByType = Object.create(null);
        (list || []).forEach(function(p){
          if (!p || !Array.isArray(p.tiles)) return;
          p.tiles.forEach(function(t){
            if (!t || !t.type) return;
            var label = (t.title || t.type) + ' · ' + (p.name || p.id || 'plugin');
            options.push({ value: t.type, label: label });
            defaultsByType[t.type] = t.defaults || {};
            try { tileDefsByType.set(String(t.type), { plugin: p, tile: t }); } catch(_) {}
          });
        });
        // Fill select
        options.sort(function(a,b){ return a.label.localeCompare(b.label); });
        options.forEach(function(opt){
          var o = document.createElement('option');
          o.value = opt.value; o.textContent = opt.label;
          select.appendChild(o);
        });
        // Change handler: set hidden config field to defaults for selected type
        var currentHandle = null;
        function teardown(){ try { currentHandle && currentHandle.destroy && currentHandle.destroy(); } catch(_) {} currentHandle = null; if (cfgUI) cfgUI.innerHTML = ''; }
        function applyDefaults(){
          var type = select.value || '';
          var def = defaultsByType[type] || {};
          try { cfgField.value = JSON.stringify(def); } catch(_) { cfgField.value = '{}'; }
          // Prefill title if empty and plugin tile has a reasonable name in label (before separator)
          try {
            var titleInput = document.querySelector('#form-add-plugin input[name="title"]');
            if (titleInput && !titleInput.value) {
              var lbl = select.options[select.selectedIndex] && select.options[select.selectedIndex].textContent || '';
              // Extract part before the divider " · "
              var idx = lbl.indexOf(' · ');
              titleInput.value = idx > 0 ? lbl.slice(0, idx) : lbl;
            }
          } catch(_) {}
          // Mount form UI based on configForm or configSchema
          try {
            teardown();
            var defRec = getTileDefByType(type);
            var tdef = defRec && defRec.tile;
            if (tdef && tdef.configForm && tdef.configForm.entry && cfgUI) {
              mountCustomForm(tdef.configForm.entry, cfgUI, def).then(function(handle){ currentHandle = handle; }).catch(function(){});
            } else if (tdef && tdef.configSchema && cfgUI) {
              currentHandle = mountSchemaForm(tdef.configSchema, cfgUI, def, function(values){ try { cfgField.value = JSON.stringify(values); } catch(_) {} });
              try { cfgField.value = JSON.stringify(currentHandle.getValues()); } catch(_) {}
            }
          } catch(_) {}
        }
        select.addEventListener('change', applyDefaults);
        // Submit: ensure plugin_config contains latest values; validate if available
        var addForm = document.getElementById('form-add-plugin');
        if (addForm) {
          addForm.addEventListener('submit', function(ev){
            try {
              if (currentHandle && typeof currentHandle.getValues === 'function') {
                if (typeof currentHandle.validate === 'function') {
                  var res = currentHandle.validate();
                  if (res && typeof res.then === 'function') {
                    ev.preventDefault();
                    res.then(function(r){
                      if (!r || r.ok !== false) {
                        try { cfgField.value = JSON.stringify(currentHandle.getValues()); } catch(_) {}
                        addForm.submit();
                      } else {
                        renderErrors(cfgUI, r.errors || {});
                      }
                    }).catch(function(){ /* ignore */ });
                    return;
                  } else if (res && res.ok === false) {
                    ev.preventDefault();
                    renderErrors(cfgUI, res.errors || {});
                    return;
                  }
                }
                try { cfgField.value = JSON.stringify(currentHandle.getValues()); } catch(_) {}
              }
            } catch(_) {}
          });
        }
        _pluginTypesLoaded = true;
      })
      .catch(function(){ /* ignore */ });
  }

  // --- Simple schema form renderer ---------------------------------------
  function mountSchemaForm(schema, container, initial, onChange){
    if (!container) return { getValues:function(){return initial||{};}, validate:function(){return {ok:true};}, destroy:function(){} };
    container.innerHTML = '';
    var wrapper = document.createElement('div');
    var values = Object.assign({}, initial || {});
    var props = (schema && schema.properties) || {};
    var required = Array.isArray(schema && schema.required) ? schema.required : [];
    function addField(key, def){
      var type = (def && def.type) || 'string';
      var group = document.createElement('div'); group.className = 'mb-2';
      var id = 'pcfg_' + key;
      var label = document.createElement('label'); label.className = 'form-label'; label.setAttribute('for', id);
      label.textContent = (def && def.title) ? String(def.title) : key; if (required.includes(key)) label.textContent += ' *';
      group.appendChild(label);
      var input;
      if (def && def.enum && Array.isArray(def.enum)){
        input = document.createElement('select'); input.className = 'form-select'; input.id = id;
        def.enum.forEach(function(opt){ var o = document.createElement('option'); o.value = String(opt); o.textContent = String(opt); input.appendChild(o); });
        input.value = values[key] != null ? String(values[key]) : String((def.default != null ? def.default : def.enum[0]));
      } else if (type === 'integer' || type === 'number'){
        input = document.createElement('input'); input.type = 'number'; input.className = 'form-control'; input.id = id;
        if (typeof def.minimum === 'number') input.min = String(def.minimum);
        if (typeof def.maximum === 'number') input.max = String(def.maximum);
        input.value = values[key] != null ? String(values[key]) : (def.default != null ? String(def.default) : '');
      } else if (type === 'boolean') {
        input = document.createElement('input'); input.type = 'checkbox'; input.className = 'form-check-input'; input.id = id; input.checked = !!(values[key] != null ? values[key] : def.default);
      } else {
        input = document.createElement('input'); input.type = (def && def.format === 'url') ? 'url' : 'text'; input.className = 'form-control'; input.id = id;
        input.placeholder = (def && def.placeholder) || '';
        input.value = values[key] != null ? String(values[key]) : (def && def.default != null ? String(def.default) : '');
      }
      input.setAttribute('data-key', key);
      group.appendChild(input);
      if (def && def.description){ var small = document.createElement('div'); small.className = 'form-text'; small.textContent = String(def.description); group.appendChild(small); }
      wrapper.appendChild(group);
      function sync(){
        if (input.type === 'checkbox') values[key] = !!input.checked;
        else if (input.type === 'number') values[key] = input.value === '' ? null : Number(input.value);
        else values[key] = input.value;
        if (onChange) try { onChange(Object.assign({}, values)); } catch(_) {}
      }
      input.addEventListener('input', sync);
      input.addEventListener('change', sync);
    }
    Object.keys(props).forEach(function(k){ addField(k, props[k]); });
    container.appendChild(wrapper);
    return {
      getValues: function(){ return Object.assign({}, values); },
      validate: function(){ var errors = {}; required.forEach(function(k){ var v = values[k]; if (v === '' || v === null || v === undefined) errors[k] = 'Pflichtfeld'; }); return { ok: Object.keys(errors).length === 0, errors: errors }; },
      destroy: function(){ try { container.innerHTML = ''; } catch(_){} }
    };
  }

  function renderErrors(container, errors){
    if (!container) return;
    try {
      // Remove previous
      Array.from(container.querySelectorAll('.alert.alert-warning')).forEach(function(n){ n.remove(); });
      var box = document.createElement('div'); box.className = 'alert alert-warning';
      var ul = document.createElement('ul'); ul.className = 'm-0 ps-3';
      Object.keys(errors||{}).forEach(function(k){ var li = document.createElement('li'); li.innerHTML = '<strong>' + String(k) + ':</strong> ' + String(errors[k]); ul.appendChild(li); });
      box.appendChild(ul); container.prepend(box);
    } catch(_) {}
  }

  function mountCustomForm(entry, container, initial){
    if (!container) return Promise.resolve({ getValues:function(){return initial||{};}, validate:function(){return {ok:true};}, destroy:function(){} });
    container.innerHTML = '';
    return importEntry(entry).then(function(factory){
      var create = factory && (factory.createForm || factory.default || factory);
      if (typeof create !== 'function') throw new Error('configForm factory not found');
      var handle = create();
      var ctx = {
        basePath: BASE_PATH || '/',
        ui: { notify: function(msg){ try { console.info('[plugin]', msg); } catch(_) {} }, openLink: function(u,t){ try{ window.open(u, t==='self'?'_self':'_blank'); } catch(_) {} }, openModal: function(node, opts){ try { createTileContext().openModal(node, opts); } catch(_) {} } },
        secrets: undefined // Phase 5
      };
      var res = handle.mount(container, initial || {}, ctx);
      return Promise.resolve(res).then(function(){ return handle; });
    });
  }

  // --- Edit flow for plugin tiles ----------------------------------------
  function initEditPluginModals(){
    try {
      document.querySelectorAll('[data-plugin-edit="1"]').forEach(function(modal){
        modal.addEventListener('shown.bs.modal', function(){
          try {
            var type = modal.getAttribute('data-plugin-type') || '';
            var raw = modal.getAttribute('data-plugin-cfg') || '{}';
            var initial = {}; try { initial = JSON.parse(raw); } catch(_) { initial = {}; }
            var ui = modal.querySelector('[data-plugin-config-ui]');
            var hidden = modal.querySelector('input[name="plugin_config"]');
            var defRec = getTileDefByType(type);
            var tdef = defRec && defRec.tile;
            var currentHandle = null;
            function teardown(){ try { currentHandle && currentHandle.destroy && currentHandle.destroy(); } catch(_) {} currentHandle = null; if (ui) ui.innerHTML=''; }
            teardown();
            if (tdef && tdef.configForm && tdef.configForm.entry) {
              mountCustomForm(tdef.configForm.entry, ui, initial).then(function(h){ currentHandle = h; }).catch(function(){});
            } else if (tdef && tdef.configSchema) {
              currentHandle = mountSchemaForm(tdef.configSchema, ui, initial, function(values){ try { hidden.value = JSON.stringify(values); } catch(_) {} });
              try { hidden.value = JSON.stringify(currentHandle.getValues()); } catch(_) {}
            }
            var form = modal.querySelector('form');
            if (form) {
              form.addEventListener('submit', function(ev){
                try {
                  if (currentHandle && typeof currentHandle.getValues === 'function') {
                    if (typeof currentHandle.validate === 'function') {
                      var vr = currentHandle.validate();
                      if (vr && typeof vr.then === 'function') {
                        ev.preventDefault();
                        vr.then(function(r){
                          if (!r || r.ok !== false) { try { hidden.value = JSON.stringify(currentHandle.getValues()); } catch(_) {} form.submit(); }
                          else { renderErrors(ui, r.errors || {}); }
                        }).catch(function(){ /* ignore */ });
                        return;
                      } else if (vr && vr.ok === false) {
                        ev.preventDefault(); renderErrors(ui, vr.errors || {}); return;
                      }
                    }
                    try { hidden.value = JSON.stringify(currentHandle.getValues()); } catch(_) {}
                  }
                } catch(_) {}
              }, { once: true });
            }
          } catch(_) {}
        });
      });
    } catch(_) {}
  }

  // Expose minimal API for later phases / debugging
  window.ToolpagesPlugins = {
    load: loadPlugins,
    registry: pluginRegistry,
    render: renderPluginTile,
    basePath: function(){ return BASE_PATH; }
  };

  // Optional demo hook: if a container with id="tp-plugin-demo" exists, try to render the first available demo tile
  function tryRenderDemo(){
    var demo = document.getElementById('tp-plugin-demo');
    if (!demo) return;
    // pick demo tile type if present
    var demoType = demo.getAttribute('data-tile-type') || 'demo.hello';
    var cfg = {};
    renderPluginTile(demoType, demo, cfg).catch(function(e){
      demo.textContent = 'Plugin-Demo konnte nicht geladen werden: ' + (e && e.message ? e.message : String(e));
    });
  }

  // Boot: initialize features and load plugins (Phase 2)
  function boot(){
    try {
      enableTileClicks();
      runPing();
      initSortableTiles();
      initAddTileModalSubmitBinding();
    } catch(_){}
    // Load plugins and (optionally) render demo container
    loadPlugins().finally(function(){
      tryRenderDemo();
      // Render any plugin placeholders present on the page (dashboard/home)
      renderAllPluginPlaceholders();
    });

    // Initialize Plugins tab when it is shown
    try {
      var pluginTabBtn = document.querySelector('#tab-plugin');
      if (pluginTabBtn) {
        pluginTabBtn.addEventListener('shown.bs.tab', function(){ initAddTilePluginsTab(); });
      }
      // If the modal is opened with plugin tab already active by default (unlikely), also init on modal show
      var modal = document.getElementById('addTileModal');
      if (modal) {
        modal.addEventListener('shown.bs.modal', function(){
          var active = modal.querySelector('.nav-link.active');
          if (active && active.id === 'tab-plugin') initAddTilePluginsTab();
        });
      }
    } catch(_){}
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  // --- Home category collapse state --------------------------------------
  function initHomeCategoryCollapse(){
    try {
      var body = document.body;
      var uid = body && body.getAttribute('data-user-id');
      if (!uid) return; // not logged in or not on home
      var storageKey = 'tp_home_collapsed_' + uid;

      function loadSet(){ try { var raw = localStorage.getItem(storageKey); return new Set(raw ? JSON.parse(raw) : []); } catch(e){ return new Set(); } }
      function saveSet(set){ try { localStorage.setItem(storageKey, JSON.stringify(Array.from(set))); } catch(e){} }

      function setIcon(catId, expanded){
        try {
          var icon = document.querySelector('[data-cat-icon="' + CSS.escape(catId) + '"]');
          if (icon) icon.textContent = expanded ? 'expand_less' : 'expand_more';
        } catch(e) {}
      }

      var collapsed = loadSet();
      document.querySelectorAll('[data-cat-id]').forEach(function(el){
        var id = el.getAttribute('data-cat-id');
        if (!id) return;
        var isCollapsed = collapsed.has(id);
        var isShown = el.classList.contains('show');
        if (isCollapsed && isShown) {
          el.classList.remove('show');
          var btn = document.querySelector('[data-bs-target="#' + CSS.escape(id) + '"]');
          if (btn) btn.setAttribute('aria-expanded', 'false');
        }
        setIcon(id, !isCollapsed);
      });

      document.querySelectorAll('[data-cat-id]').forEach(function(el){
        var id = el.getAttribute('data-cat-id');
        if (!id) return;
        el.addEventListener('hidden.bs.collapse', function(){ collapsed.add(id); saveSet(collapsed); setIcon(id, false); });
        el.addEventListener('shown.bs.collapse', function(){ collapsed.delete(id); saveSet(collapsed); setIcon(id, true); });
      });
    } catch(e) {}
  }

  // --- Add Tile modal: bind submit to active tab -------------------------
  function initAddTileModalSubmitBinding(){
    var btn = document.getElementById('submitAddTile');
    if (!btn) return;
    function setFormByTarget(target){
      switch(target){
        case '#pane-link': btn.setAttribute('form','form-add-link'); break;
        case '#pane-file': btn.setAttribute('form','form-add-file'); break;
        case '#pane-iframe': btn.setAttribute('form','form-add-iframe'); break;
        case '#pane-plugin': btn.setAttribute('form','form-add-plugin'); break;
      }
    }
    document.querySelectorAll('#addTileModal [data-bs-toggle="tab"]').forEach(function(tab){
      tab.addEventListener('shown.bs.tab', function(ev){
        var target = ev.target.getAttribute('data-bs-target');
        setFormByTarget(target);
      });
    });
  }

  // --- Delete Tile via delegated JS (preferred approach) -----------------
  function getCookie(name){
    try {
      var value = document.cookie.split('; ').find(function(row){ return row.startsWith(name + '='); });
      return value ? decodeURIComponent(value.split('=')[1]) : '';
    } catch(e) { return ''; }
  }

  function initDeleteTiles(){
    // Event delegation: catch clicks on any delete button
    document.addEventListener('click', function(ev){
      var btn = ev.target && ev.target.closest('button[data-action="delete-tile"][data-tile-id]');
      if (!btn) return;
      ev.preventDefault();

      var id = parseInt(btn.getAttribute('data-tile-id') || '0', 10);
      if (!id) return;
      var url = btn.getAttribute('data-delete-url') || ('/dashboard/tile/' + id + '/delete');
      var confirmText = btn.getAttribute('data-confirm-text') || 'Delete this tile?';

      // Confirmation
      var ok = false;
      try { ok = window.confirm(confirmText); } catch(_) { ok = true; }
      if (!ok) return;

      // Disable while processing
      var prevHtml = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

      // Prepare POST request. CodeIgniter with CSRF cookie mode accepts X-CSRF-TOKEN header
      var headers = { 'Accept': 'application/json' };
      var csrf = getCookie('csrf_cookie_name'); // matches app/Config/Security.php
      if (csrf) headers['X-CSRF-TOKEN'] = csrf; // matches headerName in Security.php

      fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: headers,
        // No body required for delete in this app; send an empty form body to please parsers
        body: new URLSearchParams()
      })
      .then(function(res){
        // Accept 200..299 as success even if HTML redirects are returned
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res;
      })
      .then(function(){
        // Remove the tile from DOM
        try {
          var tile = btn.closest('.tp-tile');
          var col = tile ? tile.closest('[class*="col-"]') : null;
          var toRemove = col || tile;
          if (toRemove) {
            toRemove.style.transition = 'opacity .15s ease-out, height .15s ease-out, margin .15s ease-out, padding .15s ease-out';
            toRemove.style.opacity = '0';
            setTimeout(function(){ try { toRemove.remove(); } catch(_){} }, 180);
          }
        } catch(_) {}
      })
      .catch(function(err){
        // Feedback on error
        try { alert('Delete failed: ' + (err && err.message ? err.message : 'unknown error')); } catch(_) {}
      })
      .finally(function(){
        btn.disabled = false;
        btn.innerHTML = prevHtml;
      });
    });
  }

    function startTime() {
        try {
            var clockEl = document.getElementById('clock');
            if (!clockEl) return; // Nur auf Seiten mit Uhr ausführen
            const today = new Date();
            let h = today.getHours();
            let m = today.getMinutes();
            let s = today.getSeconds();
            m = checkTime(m);
            s = checkTime(s);
            clockEl.textContent = h + ":" + m + ":" + s;
            setTimeout(startTime, 1000);
        } catch (_) { /* noop */ }
    }

    function checkTime(i) {
        if (i < 10) {i = "0" + i};  // add zero in front of numbers < 10
        return i;
    }

  // --- Init ---------------------------------------------------------------
  function init(){
    runPing();
    setInterval(runPing, 60 * 1000); // every minute
    enableTileClicks();
    initHomeCategoryCollapse();
    initSortableTiles();
    initAddTileModalSubmitBinding();
    initDeleteTiles();
    // Uhr nur initialisieren, wenn ein Clock-Element existiert
    if (document.getElementById('clock')) {
      startTime();
    }
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();


})();






