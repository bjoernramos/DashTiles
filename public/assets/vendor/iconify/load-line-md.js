/*
  Registers the line-md icon set with Iconify.
  It first attempts to load a local JSON collection at
  /assets/vendor/iconify/line-md.json
  If that is not present, it tries to load from /node_modules (local runtime alias).

  Usage in HTML after this script and Iconify runtime are loaded:
    <span class="iconify" data-icon="line-md:home"></span>
*/
(function(){
  function whenIconifyReady(cb){
    if (window.Iconify && typeof window.Iconify.addCollection === 'function') return cb();
    var tries = 0, max = 40; // ~2s
    var iv = setInterval(function(){
      tries++;
      if (window.Iconify && typeof window.Iconify.addCollection === 'function') {
        clearInterval(iv); cb();
      } else if (tries >= max) {
        clearInterval(iv);
      }
    }, 50);
  }

  function loadJSON(url){
    return fetch(url, { cache: 'no-store' }).then(function(r){ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); });
  }

  whenIconifyReady(function(){
    var localUrl = (window.__TP_ASSETS_BASE__ || '') + '/assets/vendor/iconify/line-md.json';
    loadJSON(localUrl).then(function(collection){
      try { window.Iconify.addCollection(collection); } catch(e) { /* noop */ }
    }).catch(function(){
      var localNodeModules = '/node_modules/@iconify-json/line-md/icons.json';
      loadJSON(localNodeModules).then(function(collection){
        try { window.Iconify.addCollection(collection); } catch(e) { /* noop */ }
      }).catch(function(){ /* noop */ });
    });
  });
})();
