#!/usr/bin/env node
import { promises as fs } from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const nm = p => path.join(root, 'node_modules', p);
const pub = p => path.join(root, 'public', p);

async function ensureDir(dir) {
  await fs.mkdir(dir, { recursive: true }).catch(() => {});
}

async function copyFile(src, dest) {
  try {
    await ensureDir(path.dirname(dest));
    await fs.copyFile(src, dest);
    console.log(`✓ Copied ${path.relative(root, src)} → ${path.relative(root, dest)}`);
    return true;
  } catch (e) {
    return false;
  }
}

async function fileExists(p) {
  try { await fs.access(p); return true; } catch { return false; }
}

async function syncIconify() {
  // Iconify runtime
  const src = nm('@iconify/iconify/dist/iconify.min.js');
  const dest = pub('assets/vendor/iconify/iconify.min.js');
  if (!(await copyFile(src, dest))) {
    console.warn('! Iconify runtime not found. Did you run `npm i`?');
  }

  // line-md icon set JSON
  const srcJson = nm('@iconify-json/line-md/icons.json');
  const destJson = pub('assets/vendor/iconify/line-md.json');
  if (!(await copyFile(srcJson, destJson))) {
    console.warn('! line-md icons.json not found in node_modules. Runtime will try local /node_modules path.');
  }
}

async function syncMaterialSymbols() {
  // Try multiple possible paths for the outlined variable font
  const tryPaths = [
    'material-symbols/variablefont/MaterialSymbolsOutlined[wght].woff2',
    'material-symbols/variablefont/MaterialSymbolsOutlined.woff2',
    'material-symbols/fonts/webfonts/MaterialSymbolsOutlined[wght].woff2',
  ];
  let found = null;
  for (const rel of tryPaths) {
    const p = nm(rel);
    if (await fileExists(p)) { found = p; break; }
  }
  const dest = pub('assets/vendor/material-symbols/fonts/material-symbols-outlined.woff2');
  if (found) {
    await copyFile(found, dest);
  } else {
    console.warn('! Material Symbols font not found in node_modules. Ensure material-symbols is installed.');
  }
}

async function syncMaterialIcons() {
  const dest = pub('assets/vendor/material-icons/fonts/MaterialIcons-Regular.woff2');
  // Try classic iconfont package first
  const tryPaths = [
    'material-design-icons-iconfont/dist/fonts/MaterialIcons-Regular.woff2',
    '@material-design-icons/font/fonts/MaterialIcons-Regular.woff2',
    '@material-design-icons/font/woff2/MaterialIcons-Regular.woff2',
    '@material-design-icons/font/fonts/material-icons/MaterialIcons-Regular.woff2',
  ];
  let copied = false;
  for (const rel of tryPaths) {
    const src = nm(rel);
    if (await fileExists(src)) {
      copied = await copyFile(src, dest);
      if (copied) break;
    }
  }
  if (!copied) {
    // As a last resort, try to find any WOFF2 under @material-design-icons/font and copy it under expected name
    const altCandidates = [
      '@material-design-icons/font/woff2/MaterialIcons-Regular.woff2',
      '@material-design-icons/font/MaterialIcons-Regular.woff2',
    ];
    for (const rel of altCandidates) {
      const src = nm(rel);
      if (await fileExists(src)) {
        copied = await copyFile(src, dest);
        if (copied) break;
      }
    }
  }
  if (!copied) {
    console.warn('! Material Icons font not copied. Ensure @material-design-icons/font or material-design-icons-iconfont is installed.');
  }
}

async function main() {
  await Promise.all([
    syncIconify(),
    syncMaterialSymbols(),
    syncMaterialIcons(),
    (async function syncBootstrap(){
      const cssSrc = nm('bootstrap/dist/css/bootstrap.min.css');
      const cssDest = pub('assets/vendor/bootstrap/bootstrap.min.css');
      const cssMapSrc = nm('bootstrap/dist/css/bootstrap.min.css.map');
      const cssMapDest = pub('assets/vendor/bootstrap/bootstrap.min.css.map');
      const jsSrc = nm('bootstrap/dist/js/bootstrap.bundle.min.js');
      const jsDest = pub('assets/vendor/bootstrap/bootstrap.bundle.min.js');
      const jsMapSrc = nm('bootstrap/dist/js/bootstrap.bundle.min.js.map');
      const jsMapDest = pub('assets/vendor/bootstrap/bootstrap.bundle.min.js.map');
      await copyFile(cssSrc, cssDest);
      await copyFile(cssMapSrc, cssMapDest);
      await copyFile(jsSrc, jsDest);
      await copyFile(jsMapSrc, jsMapDest);
    })(),
  ]);
}

main().catch(err => {
  console.error(err);
  process.exitCode = 1;
});
