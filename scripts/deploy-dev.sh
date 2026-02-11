#!/bin/bash
set -e

echo "🚀 Aplicando alterações do ZyraVision..."

# 1. Build do frontend
echo "📦 Compilando assets..."
docker exec zyravision-node npm run build

# 2. Clear caches
echo "🧹 Limpando caches do Laravel..."
docker exec zyravision-app php artisan cache:clear
docker exec zyravision-app php artisan config:clear
docker exec zyravision-app php artisan view:clear
docker exec zyravision-app php artisan route:clear

# 3. Warm up (opcional)
echo "♨️  Warming up..."
docker exec zyravision-app php artisan config:cache
docker exec zyravision-app php artisan route:cache

echo "✅ Deploy concluído com sucesso!"
echo ""
echo "⚠️  IMPORTANTE: Faça um HARD REFRESH no navegador:"
echo "   - Chrome/Edge: Ctrl+Shift+R (Windows) ou Cmd+Shift+R (Mac)"
echo "   - Firefox: Ctrl+F5 (Windows) ou Cmd+Shift+R (Mac)"
echo ""
