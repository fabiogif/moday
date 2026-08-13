#!/bin/bash

# Script para configurar CORS em produção
echo "Configurando CORS para produção..."

# Verificar se o arquivo .env existe
if [ ! -f .env ]; then
    echo "Arquivo .env não encontrado. Copiando do env..."
    cp env .env
fi

# Adicionar configurações CORS se não existirem
if ! grep -q "FRONTEND_URL" .env; then
    echo "" >> .env
    echo "# CORS Configuration" >> .env
    echo "FRONTEND_URL=https://your-frontend-domain.com" >> .env
    echo "ADDITIONAL_CORS_ORIGIN=http://localhost:3000" >> .env
fi

# Limpar cache de configuração
echo "Limpando cache de configuração..."
php artisan config:clear
php artisan config:cache

# Regenerar chave JWT se necessário
if ! grep -q "JWT_SECRET=" .env || [ -z "$(grep JWT_SECRET .env | cut -d '=' -f2)" ]; then
    echo "Gerando chave JWT..."
    php artisan jwt:secret --force
fi

echo "Configuração CORS concluída!"
echo ""
echo "IMPORTANTE:"
echo "1. Verifique se a variável FRONTEND_URL no .env está apontando para o domínio correto do seu frontend"
echo "2. Se estiver usando localhost:3000, certifique-se de que ADDITIONAL_CORS_ORIGIN está configurado"
echo "3. Reinicie o servidor web após as alterações"

