# 🚀 DEPLOY CORS FIX - DIGITAL OCEAN APP PLATFORM

## ⚠️ IMPORTANTE
Você está usando **Digital Ocean App Platform** (Kubernetes), não um servidor tradicional!
Os arquivos precisam ser commitados no Git para serem deployados.

## 🎯 SOLUÇÃO RÁPIDA

### OPÇÃO 1: Via Git (RECOMENDADO) ✅

No seu computador local:

```bash
# 1. Verificar mudanças
cd /Users/fabiosantana/Documentos/projetos/backend
git status

# 2. Fazer commit das correções
git add app/Http/Middleware/CustomCorsMiddleware.php
git add public/.htaccess
git commit -m "Fix CORS: Remove wildcard and add production frontend URL"

# 3. Push para o repositório
git push origin main
# ou
git push origin master
```

**A Digital Ocean vai fazer redeploy automático em 1-2 minutos!**

Acompanhe em: https://cloud.digitalocean.com/apps

---

### OPÇÃO 2: Adicionar Variável de Ambiente (Rápido)

Se você não quer fazer deploy agora, pode adicionar a variável de ambiente primeiro:

1. Acesse: https://cloud.digitalocean.com/apps
2. Clique no seu app (orca-app-7hejo)
3. Vá em **Settings** → **App-Level Environment Variables**
4. Clique em **Edit**
5. Adicione:
   ```
   FRONTEND_URL=https://clownfish-app-rr5rv.ondigitalocean.app
   ```
6. Clique em **Save**
7. A app vai reiniciar automaticamente

**Mas isso sozinho NÃO resolve!** Você ainda precisa fazer o commit do código.

---

### OPÇÃO 3: Force Rebuild (Se já fez commit)

Se você já fez o commit e push, mas não deployou:

1. Acesse: https://cloud.digitalocean.com/apps
2. Clique no seu app
3. Vá em **Settings**
4. Role até **App Configuration**
5. Clique em **Force Rebuild and Deploy**

---

## 📋 CHECKLIST COMPLETO

### Passo 1: Verificar arquivos locais
```bash
cd /Users/fabiosantana/Documentos/projetos/backend

# Verificar se CustomCorsMiddleware tem a URL
grep "clownfish-app-rr5rv" app/Http/Middleware/CustomCorsMiddleware.php
# Deve retornar a linha com a URL

# Verificar se .htaccess está limpo
grep "Access-Control-Allow-Origin" public/.htaccess
# Não deve retornar nada (ou erro "not found")
```

### Passo 2: Commit e Push
```bash
git status
git add .
git commit -m "Fix CORS for production frontend"
git push origin main
```

### Passo 3: Verificar Deploy
1. Acesse: https://cloud.digitalocean.com/apps
2. Você verá "Building..." → "Deploying..." → "Active"
3. Aguarde até ficar "Active" (verde)

### Passo 4: Adicionar variável de ambiente (se não fez antes)
1. Apps → Settings → Environment Variables
2. Adicione: `FRONTEND_URL=https://clownfish-app-rr5rv.ondigitalocean.app`
3. Save

### Passo 5: Testar
Tente fazer login no frontend!

---

## 🔍 TROUBLESHOOTING

### "Ainda dá erro de CORS"

**1. Verificar se o deploy aconteceu:**
```bash
# Acesse o console do app
# Vá em Console tab
cd ~/
grep "clownfish-app-rr5rv" app/Http/Middleware/CustomCorsMiddleware.php
```

Se NÃO encontrar a URL, o deploy não aconteceu.

**2. Verificar variáveis de ambiente:**
```bash
# No console do app
printenv | grep FRONTEND_URL
```

Deve mostrar: `FRONTEND_URL=https://clownfish-app-rr5rv.ondigitalocean.app`

**3. Limpar cache (no console do app):**
```bash
cd ~/
php artisan config:clear
php artisan cache:clear
```

**4. Ver logs:**
```bash
# No painel da Digital Ocean
Apps → Logs → Runtime Logs
```

Procure por erros relacionados a CORS.

---

## 🎯 TESTE RÁPIDO

Depois do deploy, teste com curl:

```bash
curl -i -X OPTIONS \
  -H "Origin: https://clownfish-app-rr5rv.ondigitalocean.app" \
  -H "Access-Control-Request-Method: POST" \
  https://orca-app-7hejo.ondigitalocean.app/api/auth/login
```

**Deve retornar:**
```
Access-Control-Allow-Origin: https://clownfish-app-rr5rv.ondigitalocean.app
Access-Control-Allow-Credentials: true
```

**NÃO deve retornar:**
```
Access-Control-Allow-Origin: *
```

---

## 📝 RESUMO

Em App Platform / Kubernetes:
1. ✅ Commit e push das mudanças
2. ✅ Aguardar redeploy automático (1-2 min)
3. ✅ Adicionar FRONTEND_URL nas variáveis de ambiente
4. ✅ Testar no frontend

Não use `systemctl` - não funciona em containers!
O restart acontece automaticamente no redeploy.

---

## 🆘 AINDA NÃO FUNCIONA?

Execute no console do app:
```bash
cd ~/
cat app/Http/Middleware/CustomCorsMiddleware.php | grep -A 10 "allowedOrigins"
cat .env | grep FRONTEND_URL
php artisan config:clear && php artisan cache:clear
```

Me envie a saída desses comandos!
