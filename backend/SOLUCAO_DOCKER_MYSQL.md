# Solução Docker + MySQL para Laravel

## ✅ **Configurações Implementadas**

### 1. **Docker Compose Atualizado**

-   ✅ **docker-compose.yml** configurado para MySQL 8.0.32
-   ✅ **docker-compose.override.yml** criado para override
-   ✅ **Variáveis de ambiente** com valores padrão
-   ✅ **Health checks** para MySQL e Redis
-   ✅ **Volumes persistentes** para dados

### 2. **Scripts de Configuração**

-   ✅ **configure-docker.sh** - Configuração automática
-   ✅ **start-docker.bat** - Script Windows
-   ✅ **start-docker.ps1** - Script PowerShell
-   ✅ **setup-docker.sh** - Setup completo

### 3. **Configurações de Banco**

-   ✅ **MySQL 8.0.32** com autenticação nativa
-   ✅ **Redis** para cache e sessões
-   ✅ **Volumes persistentes** para dados
-   ✅ **Health checks** para verificação

## 🚀 **Como Usar**

### **Opção 1: Scripts Automáticos**

```bash
# Linux/Mac
./configure-docker.sh
docker-compose up -d

# Windows (PowerShell)
.\start-docker.ps1

# Windows (Batch)
start-docker.bat
```

### **Opção 2: Manual**

```bash
# 1. Configurar ambiente
cp .env.example .env
php artisan key:generate

# 2. Iniciar containers
docker-compose up -d

# 3. Aguardar MySQL (30 segundos)
sleep 30

# 4. Executar migrações
docker-compose exec laravel.test php artisan migrate
```

## 🔧 **Configurações do Docker**

### **docker-compose.yml**

```yaml
mysql:
    image: "mysql:8.0.32"
    ports:
        - "3306:3306"
    environment:
        MYSQL_ROOT_PASSWORD: "password"
        MYSQL_DATABASE: "laravel"
        MYSQL_USER: "sail"
        MYSQL_PASSWORD: "password"
    volumes:
        - "mysql-data:/var/lib/mysql"
    command: --default-authentication-plugin=mysql_native_password
```

### **Variáveis de Ambiente**

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
```

## 🐛 **Problemas Identificados e Soluções**

### **1. Extensão OpenSSL**

**Problema**: `You must enable the openssl extension in your php.ini`

**Soluções**:

```bash
# Opção 1: Usar Docker (recomendado)
docker-compose up -d

# Opção 2: Habilitar OpenSSL no PHP local
# Editar php.ini e descomentar:
extension=openssl
```

### **2. Composer.lock Desatualizado**

**Problema**: `The lock file is not up to date`

**Solução**:

```bash
# Remover lock file e reinstalar
rm composer.lock
composer install
```

### **3. Permissões Git no Docker**

**Problema**: `dubious ownership in repository`

**Solução**: Já configurado no docker-compose.override.yml

## 📋 **Comandos Úteis**

### **Gerenciamento de Containers**

```bash
# Ver status
docker-compose ps

# Ver logs
docker-compose logs laravel.test

# Reiniciar
docker-compose restart

# Parar tudo
docker-compose down
```

### **Laravel no Docker**

```bash
# Executar migrações
docker-compose exec laravel.test php artisan migrate

# Executar testes
docker-compose exec laravel.test php artisan test

# Acessar shell
docker-compose exec laravel.test bash

# Composer install
docker-compose exec laravel.test composer install
```

## 🌐 **Acessos**

-   **Aplicação**: http://localhost
-   **MySQL**: localhost:3306
-   **Redis**: localhost:6379
-   **Mailpit**: http://localhost:8025

## 🔍 **Verificação de Funcionamento**

### **1. Verificar Containers**

```bash
docker-compose ps
# Todos devem estar "Up" e "healthy"
```

### **2. Testar Conexão MySQL**

```bash
docker-compose exec mysql mysql -u sail -ppassword laravel
```

### **3. Testar Aplicação**

```bash
curl http://localhost
# Deve retornar resposta da aplicação
```

## 🚨 **Troubleshooting**

### **Container Laravel não inicia**

```bash
# Ver logs
docker-compose logs laravel.test

# Reconstruir
docker-compose up -d --build
```

### **MySQL não conecta**

```bash
# Verificar se está rodando
docker-compose ps mysql

# Ver logs
docker-compose logs mysql

# Reiniciar
docker-compose restart mysql
```

### **Permissões no Windows**

```bash
# Executar como administrador
# Ou usar WSL2
```

## 📊 **Status Atual**

-   ✅ **Docker Compose configurado**
-   ✅ **MySQL funcionando**
-   ✅ **Redis funcionando**
-   ✅ **Scripts criados**
-   ⚠️ **OpenSSL precisa ser habilitado** (PHP local)
-   ⚠️ **Composer.lock desatualizado**

## 🎯 **Próximos Passos**

1. **Habilitar OpenSSL** no PHP local
2. **Atualizar composer.lock**
3. **Executar migrações**
4. **Testar criação de produtos**

## 💡 **Recomendações**

1. **Use Docker** para desenvolvimento (evita problemas de extensões)
2. **Mantenha composer.lock** atualizado
3. **Use scripts automatizados** para setup
4. **Verifique logs** em caso de problemas
