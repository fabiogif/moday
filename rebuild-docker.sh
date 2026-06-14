#!/bin/bash

echo "🐳 Reconstruindo containers Docker..."
echo ""

# Para todos os containers
docker-compose down

# Remove imagens antigas
docker-compose rm -f

# Reconstrói as imagens sem cache
docker-compose build --no-cache

# Inicia os containers
docker-compose up -d

echo ""
echo "✅ Containers reconstruídos!"
echo ""
echo "Verificando extensão mbstring..."
docker-compose exec laravel.test php -m | grep mbstring

if [ $? -eq 0 ]; then
    echo "✅ mbstring está habilitada!"
else
    echo "⚠️  mbstring não encontrada. Tentando instalar..."
    docker-compose exec laravel.test apt-get update
    docker-compose exec laravel.test apt-get install -y php8.3-mbstring
    docker-compose exec laravel.test service php8.3-fpm restart || true
fi

echo ""
echo "🎉 Pronto! Agora você pode executar: php artisan postman:generate"

