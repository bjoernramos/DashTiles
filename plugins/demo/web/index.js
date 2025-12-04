// Demo plugin: registers a simple tile class that renders a message

class DemoHelloTile {
  #container; #cfg; #ctx;
  render(container, cfg = {}, ctx) {
    this.#container = container; this.#cfg = cfg; this.#ctx = ctx;
    const msg = cfg.message || 'Hallo von Plugin!';
    // Build asset URL respecting optional basePath, but avoid protocol-relative URLs ("//...")
    const bp = (!ctx || !ctx.basePath || ctx.basePath === '/') ? '' : ctx.basePath;
    container.innerHTML = `
      <div style="display:flex;align-items:center;gap:8px;height:100%;padding:8px;">
        <img src="${bp + '/plugins/demo/web/assets/hello.svg'}" alt="hello" width="24" height="24"/>
        <div style="font-weight:600;">${this.#escape(msg)}</div>
      </div>`;
  }
  onClick() {
    const msg = this.#cfg?.message || 'Hallo von Plugin!';
    if (this.#ctx?.openModal) {
      const node = document.createElement('div');
      node.textContent = msg;
      this.#ctx.openModal(node, { title: 'Demo', width: 360 });
    } else if (typeof window !== 'undefined') {
      alert(msg);
    }
  }
  update(cfg) { this.render(this.#container, cfg, this.#ctx); }
  dispose() { /* nothing */ }
  #escape(s) { return String(s).replace(/[&<>]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[c])); }
}

export function register(registrar) {
  registrar.registerTile('demo.hello', DemoHelloTile);
}
