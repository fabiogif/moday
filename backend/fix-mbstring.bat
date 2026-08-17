@echo off
echo Reconstruindo containers Docker...
echo.

REM Para todos os containers
docker-compose down

REM Remove imagens antigas
docker-compose rm -f

REM Reconstroi as imagens sem cache
docker-compose build --no-cache

REM Inicia os containers
docker-compose up -d

echo.
echo Containers reconstruidos!
echo.
echo Verificando extensao mbstring...
docker-compose exec laravel.test php -m | findstr mbstring

if %errorlevel% equ 0 (
    echo mbstring esta habilitada!
) else (
    echo mbstring nao encontrada. Tentando instalar...
    docker-compose exec laravel.test apt-get update
    docker-compose exec laravel.test apt-get install -y php8.3-mbstring
)

echo.
echo Pronto! Agora voce pode executar: php artisan postman:generate
pause

