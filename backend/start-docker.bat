@echo off
echo 🐳 Iniciando ambiente Docker...

REM Configurar variáveis de ambiente
set DOTENV_SUPPRESS_DEPRECATION_WARNINGS=true

REM Verificar se Docker está rodando
docker --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Docker não está instalado ou não está rodando
    echo Por favor, instale o Docker Desktop e tente novamente
    pause
    exit /b 1
)

REM Parar containers existentes
echo 🛑 Parando containers existentes...
docker-compose down

REM Construir e iniciar containers
echo 🚀 Construindo e iniciando containers...
docker-compose up -d --build

REM Aguardar MySQL estar pronto
echo ⏳ Aguardando MySQL estar pronto...
timeout /t 30 /nobreak >nul

REM Executar migrações
echo 📋 Executando migrações...
docker-compose exec laravel.test php artisan migrate --force

REM Executar seeders se existirem
echo 🌱 Executando seeders...
docker-compose exec laravel.test php artisan db:seed --force

echo ✅ Ambiente Docker iniciado com sucesso!
echo.
echo 🌐 Aplicação disponível em: http://localhost
echo 🗄️  MySQL disponível em: localhost:3306
echo 🔴 Redis disponível em: localhost:6379
echo.
echo 📋 Comandos úteis:
echo    docker-compose exec laravel.test php artisan migrate
echo    docker-compose exec laravel.test php artisan test
echo    docker-compose exec laravel.test php artisan tinker
echo.
pause
