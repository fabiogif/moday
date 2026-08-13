# Script PowerShell para iniciar ambiente Docker
Write-Host "🐳 Iniciando ambiente Docker..." -ForegroundColor Cyan

# Configurar variáveis de ambiente
$env:DOTENV_SUPPRESS_DEPRECATION_WARNINGS = "true"

# Verificar se Docker está rodando
try {
    docker --version | Out-Null
    Write-Host "✅ Docker encontrado" -ForegroundColor Green
}
catch {
    Write-Host "❌ Docker não está instalado ou não está rodando" -ForegroundColor Red
    Write-Host "Por favor, instale o Docker Desktop e tente novamente" -ForegroundColor Yellow
    Read-Host "Pressione Enter para sair"
    exit 1
}

# Parar containers existentes
Write-Host "🛑 Parando containers existentes..." -ForegroundColor Yellow
docker-compose down

# Construir e iniciar containers
Write-Host "🚀 Construindo e iniciando containers..." -ForegroundColor Cyan
docker-compose up -d --build

if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Erro ao iniciar containers" -ForegroundColor Red
    Read-Host "Pressione Enter para sair"
    exit 1
}

# Aguardar MySQL estar pronto
Write-Host "⏳ Aguardando MySQL estar pronto..." -ForegroundColor Yellow
Start-Sleep -Seconds 30

# Verificar se containers estão rodando
$containers = docker-compose ps --services --filter "status=running"
if ($containers -contains "mysql" -and $containers -contains "laravel.test") {
    Write-Host "✅ Containers iniciados com sucesso!" -ForegroundColor Green
}
else {
    Write-Host "⚠️  Alguns containers podem não estar rodando" -ForegroundColor Yellow
}

# Executar migrações
Write-Host "📋 Executando migrações..." -ForegroundColor Cyan
docker-compose exec laravel.test php artisan migrate --force

# Executar seeders se existirem
Write-Host "🌱 Executando seeders..." -ForegroundColor Cyan
docker-compose exec laravel.test php artisan db:seed --force

Write-Host ""
Write-Host "✅ Ambiente Docker iniciado com sucesso!" -ForegroundColor Green
Write-Host ""
Write-Host "🌐 Aplicação disponível em: http://localhost" -ForegroundColor Blue
Write-Host "🗄️  MySQL disponível em: localhost:3306" -ForegroundColor Blue
Write-Host "🔴 Redis disponível em: localhost:6379" -ForegroundColor Blue
Write-Host ""
Write-Host "📋 Comandos úteis:" -ForegroundColor Yellow
Write-Host "   docker-compose exec laravel.test php artisan migrate" -ForegroundColor White
Write-Host "   docker-compose exec laravel.test php artisan test" -ForegroundColor White
Write-Host "   docker-compose exec laravel.test php artisan tinker" -ForegroundColor White
Write-Host ""
Read-Host "Pressione Enter para continuar"
