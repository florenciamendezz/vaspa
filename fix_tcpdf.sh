#!/bin/bash
# === fix_tcpdf.sh ===
# Reemplaza sintaxis antigua de arrays con llaves { } por corchetes [ ] en PHP.
# Crea un backup por seguridad antes de modificar.

DIR="$1"

if [ -z "$DIR" ]; then
  echo "Uso: $0 /ruta/a/tcpdf"
  exit 1
fi

if [ ! -d "$DIR" ]; then
  echo "❌ La ruta $DIR no existe."
  exit 1
fi

BACKUP="${DIR}_backup_$(date +%Y%m%d_%H%M%S)"
echo "📦 Creando backup en: $BACKUP"
cp -r "$DIR" "$BACKUP"

echo "🔍 Corrigiendo archivos PHP en $DIR..."
find "$DIR" -type f -name "*.php" | while read -r FILE; do
  # Hacemos reemplazo regex de $var{…} por $var[…]
  sed -E -i 's/(\$[A-Za-z_][A-Za-z0-9_]*)\s*\{\s*([^}]+)\s*\}/\1[\2]/g' "$FILE"
done

echo "✅ Proceso completado."
echo "Backup disponible en: $BACKUP"
