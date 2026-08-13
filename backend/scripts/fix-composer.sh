#!/bin/bash
# Garante execução a partir de backend/ (este arquivo fica em backend/scripts/)
cd "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)" || exit 1

echo "🔧 Corrigindo problemas do Composer..."

# Configurar para suprimir warnings de deprecação do Dotenv
export DOTENV_SUPPRESS_DEPRECATION_WARNINGS=true

# Parar containers se estiverem rodando
echo "🛑 Parando containers existentes..."
docker-compose down

# Remover arquivos de lock e cache
echo "🧹 Limpando cache do Composer..."
rm -f composer.lock
rm -rf vendor/
rm -rf ~/.composer/cache/

# Reinstalar dependências
echo "📦 Reinstalando dependências..."
composer install --no-interaction --prefer-dist --optimize-autoloader

# Verificar se há problemas
if [ $? -ne 0 ]; then
    echo "❌ Erro ao instalar dependências. Tentando com --ignore-platform-reqs..."
    composer install --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-reqs
fi

# Atualizar composer.lock
echo "🔄 Atualizando composer.lock..."
composer update --no-interaction --prefer-dist --optimize-autoloader

echo "✅ Composer corrigido!"
echo ""
echo "🚀 Agora você pode iniciar o Docker:"
echo "   docker-compose up -d"
