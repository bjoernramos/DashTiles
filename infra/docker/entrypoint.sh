#!/usr/bin/env sh
set -eu

# Paths
APP_ROOT="/var/www/html"
PUB_VENDOR="$APP_ROOT/public/assets/vendor"
IMAGE_VENDOR="/opt/toolpages-assets/vendor"
NODE_MODULES_DIR="$APP_ROOT/node_modules"

echo "[entrypoint] Ensuring vendor assets are available in public/ ..."

# Robust recursive seed: copy every missing file from image snapshot to public vendor (do not overwrite)
seed_missing_vendor_files() {
  SRC_DIR="$1"
  DST_DIR="$2"
  [ -d "$SRC_DIR" ] || return 0
  mkdir -p "$DST_DIR"
  # Iterate files only and copy if absent in destination, preserving relative paths
  # Using find avoids cp -n behavior that can skip merging into existing directories on some systems
  find "$SRC_DIR" -type f | while IFS= read -r src; do
    rel="${src#${SRC_DIR}/}"
    dst="$DST_DIR/$rel"
    if [ ! -f "$dst" ]; then
      mkdir -p "$(dirname "$dst")"
      cp "$src" "$dst" 2>/dev/null || true
      echo "[entrypoint] + seeded $(printf '%s' "$rel")"
    fi
  done
}

if [ -d "$IMAGE_VENDOR" ]; then
  seed_missing_vendor_files "$IMAGE_VENDOR" "$PUB_VENDOR"
else
  echo "[entrypoint] WARNING: Snapshot directory $IMAGE_VENDOR not found in image."
fi

# Extra safeguard: if specific webfonts are still missing in public (e.g., bind mounts overwrote them
# and snapshot didn't contain them), try to copy from node_modules at runtime.
echo "[entrypoint] Verifying critical webfonts ..."

# Material Icons (baseline)
MI_PUBLIC_FONT="$PUB_VENDOR/material-icons/fonts/MaterialIcons-Regular.woff2"
if [ ! -f "$MI_PUBLIC_FONT" ]; then
  # Candidate locations inside node_modules
  for CAND in \
    "$NODE_MODULES_DIR/material-design-icons-iconfont/dist/fonts/MaterialIcons-Regular.woff2" \
    "$NODE_MODULES_DIR/@material-design-icons/font/woff2/MaterialIcons-Regular.woff2" \
    "$NODE_MODULES_DIR/@material-design-icons/font/fonts/MaterialIcons-Regular.woff2" \
    "$NODE_MODULES_DIR/@material-design-icons/font/fonts/material-icons/MaterialIcons-Regular.woff2"; do
    if [ -f "$CAND" ]; then
      mkdir -p "$(dirname "$MI_PUBLIC_FONT")"
      cp -n "$CAND" "$MI_PUBLIC_FONT" && echo "[entrypoint] Restored Material Icons font from node_modules." && break
    fi
  done
fi

# Material Symbols (Outlined)
MS_PUBLIC_FONT="$PUB_VENDOR/material-symbols/fonts/material-symbols-outlined.woff2"
if [ ! -f "$MS_PUBLIC_FONT" ]; then
  for CAND in \
    "$NODE_MODULES_DIR/material-symbols/variablefont/MaterialSymbolsOutlined[wght].woff2" \
    "$NODE_MODULES_DIR/material-symbols/variablefont/MaterialSymbolsOutlined.woff2" \
    "$NODE_MODULES_DIR/material-symbols/fonts/webfonts/MaterialSymbolsOutlined[wght].woff2"; do
    if [ -f "$CAND" ]; then
      mkdir -p "$(dirname "$MS_PUBLIC_FONT")"
      cp -n "$CAND" "$MS_PUBLIC_FONT" && echo "[entrypoint] Restored Material Symbols font from node_modules." && break
    fi
  done
fi

echo "[entrypoint] Starting: $*"
exec "$@"
