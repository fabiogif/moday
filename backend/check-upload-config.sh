#!/bin/bash
echo "╔══════════════════════════════════════════════════════════════════╗"
echo "║           🔍 Diagnóstico de Upload de Imagem                     ║"
echo "╚══════════════════════════════════════════════════════════════════╝"
echo ""

echo "1️⃣  Configurações PHP:"
echo "─────────────────────────────────────────────────────────────────"
php -r "echo '   upload_max_filesize: ' . ini_get('upload_max_filesize') . PHP_EOL;"
php -r "echo '   post_max_size: ' . ini_get('post_max_size') . PHP_EOL;"
php -r "echo '   memory_limit: ' . ini_get('memory_limit') . PHP_EOL;"
php -r "echo '   max_file_uploads: ' . ini_get('max_file_uploads') . PHP_EOL;"
echo ""

echo "2️⃣  Permissões do Storage:"
echo "─────────────────────────────────────────────────────────────────"
ls -la storage/app/public 2>/dev/null | head -3 || echo "   ❌ Diretório não encontrado"
echo ""

echo "3️⃣  Symlink do Storage:"
echo "─────────────────────────────────────────────────────────────────"
if [ -L "public/storage" ]; then
    echo "   ✅ Symlink existe"
    ls -la public/storage | head -1
else
    echo "   ❌ Symlink NÃO existe"
    echo "   Execute: php artisan storage:link"
fi
echo ""

echo "4️⃣  Espaço em Disco:"
echo "─────────────────────────────────────────────────────────────────"
df -h storage 2>/dev/null | tail -1 || df -h . | tail -1
echo ""

echo "5️⃣  Validação Atual:"
echo "─────────────────────────────────────────────────────────────────"
grep "'image'" app/Http/Requests/StoreUpdateProductRequest.php | head -1
echo ""

echo "6️⃣  Últimos Logs (se houver):"
echo "─────────────────────────────────────────────────────────────────"
if [ -f "storage/logs/laravel.log" ]; then
    tail -3 storage/logs/laravel.log | grep -i "upload\|image" || echo "   Sem erros recentes de upload"
else
    echo "   Arquivo de log não encontrado"
fi
echo ""

echo "╔══════════════════════════════════════════════════════════════════╗"
echo "║                    ✅ Verificação Completa                       ║"
echo "╚══════════════════════════════════════════════════════════════════╝"
echo ""
echo "📋 Recomendações:"
echo "   • upload_max_filesize deve ser >= 10M"
echo "   • post_max_size deve ser >= 10M"
echo "   • Permissões do storage: 775"
echo "   • Symlink deve existir"
echo "   • Validação deve ter 'nullable'"
