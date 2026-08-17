#!/bin/bash

echo "🔧 Configurando CORS para produção..."
echo "Frontend: https://moday-nine.vercel.app"
echo "Backend: https://orca-app-7hejo.ondigitalocean.app"
echo ""

# Fazer backup dos arquivos
echo "📦 Fazendo backup dos arquivos atuais..."
cp config/cors.php config/cors.php.backup.$(date +%Y%m%d_%H%M%S)
cp app/Http/Middleware/CustomCorsMiddleware.php app/Http/Middleware/CustomCorsMiddleware.php.backup.$(date +%Y%m%d_%H%M%S)
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# Atualizar config/cors.php
echo "📝 Atualizando config/cors.php..."
sed -i "s/'allowed_origins' => array_filter(\[/'allowed_origins' => array_filter([\n        'https:\/\/moday-nine.vercel.app',/" config/cors.php

# Atualizar CustomCorsMiddleware.php
echo "📝 Atualizando CustomCorsMiddleware.php..."
sed -i "s/'https:\/\/localhost:3001',/'https:\/\/localhost:3001',\n            'https:\/\/moday-nine.vercel.app',/" app/Http/Middleware/CustomCorsMiddleware.php

# Atualizar .env
echo "📝 Atualizando .env..."
if ! grep -q "FRONTEND_URL" .env; then
    echo "" >> .env
    echo "# CORS Configuration" >> .env
    echo "FRONTEND_URL=https://moday-nine.vercel.app" >> .env
    echo "ADDITIONAL_CORS_ORIGIN=http://localhost:3000" >> .env
else
    sed -i 's/FRONTEND_URL=.*/FRONTEND_URL=https:\/\/moday-nine.vercel.app/' .env
fi

# Limpar cache
echo "🧹 Limpando cache de configuração..."
php artisan config:clear
php artisan config:cache

echo ""
echo "✅ Configuração CORS concluída!"
echo ""
echo "🔄 Reinicie o servidor web para aplicar as mudanças:"
echo "   sudo systemctl restart nginx"
echo "   # ou"
echo "   sudo systemctl restart apache2"
echo ""
echo "🧪 Para testar, execute:"
echo "   curl -X OPTIONS https://orca-app-7hejo.ondigitalocean.app/api/auth/login \\"
echo "     -H \"Origin: https://moday-nine.vercel.app\" \\"
echo "     -H \"Access-Control-Request-Method: POST\" \\"
echo "     -v"
