#!/bin/bash

# Script para executar migrações e testes

# Configurar para suprimir warnings de deprecação do Dotenv
export DOTENV_SUPPRESS_DEPRECATION_WARNINGS=true

echo "🚀 Iniciando processo de migração e testes..."

# Instalar dependências se necessário
if [ ! -d "vendor" ]; then
    echo "📦 Instalando dependências..."
    composer install
fi

# Verificar se o arquivo .env existe
if [ ! -f ".env" ]; then
    echo "⚠️  Arquivo .env não encontrado. Copiando .env.example..."
    cp .env.example .env
    echo "🔑 Gerando chave da aplicação..."
    php artisan key:generate
fi

# Configurar JWT_SECRET se não existir
if ! grep -q "JWT_SECRET=" .env; then
    echo "🔐 Configurando JWT secret..."
    JWT_SECRET=$(php -r "echo bin2hex(random_bytes(32));")
    echo "JWT_SECRET=$JWT_SECRET" >> .env
    echo "✅ JWT secret configurado!"
fi

# Executar migrações
echo "🗄️  Executando migrações..."
php artisan migrate --force

# Verificar se as migrações foram executadas com sucesso
if [ $? -eq 0 ]; then
    echo "✅ Migrações executadas com sucesso!"
    
    # Executar testes
    echo "🧪 Executando testes..."
    php artisan test
    
    if [ $? -eq 0 ]; then
        echo "🎉 Todos os testes passaram!"
        echo ""
        echo "📊 Resumo:"
        echo "- ✅ Migrações executadas"
        echo "- ✅ Testes aprovados"
        echo "- ✅ Sistema pronto para uso"
    else
        echo "❌ Alguns testes falharam."
        exit 1
    fi
else
    echo "❌ Erro ao executar migrações."
    exit 1
fi

echo ""
echo "🔧 Comandos úteis:"
echo "- php artisan serve (iniciar servidor)"
echo "- php artisan test (executar testes)"
echo "- php artisan migrate:rollback (reverter migrações)"
echo "- php artisan route:list (listar rotas)"
