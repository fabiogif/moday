# 🔧 Guia: Configuração HTTP Routes no Painel Digital Ocean

## Verificando a Configuração Atual

### Passo 1: Identifique sua arquitetura

Você tem **dois apps separados**:
- 🌐 Frontend: `clownfish-app-rr5rv` (Next.js)
- ⚙️ Backend: `orca-app-7hejo` (Laravel)

### Passo 2: Acesse o painel

1. Vá para: https://cloud.digitalocean.com/apps
2. Você verá dois apps listados

---

## ⚠️ Importante: Opção HTTP Routes

A opção **HTTP Routes Redirect** só está disponível quando você tem:
- **Um único App** com **múltiplos componentes/serviços**

Como você tem **dois apps separados**, essa opção **NÃO estará disponível**.

---

## 📋 O Que Verificar no Painel

### No App Frontend (`clownfish-app-rr5rv`)

1. Clique no app
2. Vá em **Settings**
3. Verifique se há:
   - ❌ "HTTP Routes" → Provavelmente NÃO aparecerá
   - ✅ "Domains" → Configure seu domínio customizado aqui
   - ✅ "Environment Variables" → Configure `NEXT_PUBLIC_API_URL`

### No App Backend (`orca-app-7hejo`)

1. Clique no app
2. Vá em **Settings**
3. Verifique:
   - ✅ "Environment Variables" → Deve ter `APP_URL` e `FRONTEND_URL`
   - ✅ "CORS Settings" → Já configurado no app.yaml

---

## 🎯 Para Usar HTTP Routes (SE NECESSÁRIO)

Se você **realmente precisa** usar HTTP Routes, você precisará:

### Opção 1: Criar um Novo App Unificado

1. No painel do Digital Ocean, clique em **"Create"** → **"App"**
2. Escolha **"Edit App Spec"**
3. Cole este YAML:

```yaml
name: moday-fullstack
services:
  # Frontend Component
  - name: frontend
    environment_slug: node-js
    github:
      branch: main
      repo: seu-usuario/frontend-moday
      deploy_on_push: true
    routes:
      - path: /
    envs:
      - key: NEXT_PUBLIC_API_URL
        value: "https://moday-fullstack.ondigitalocean.app"
    
  # Backend Component  
  - name: backend
    environment_slug: php
    github:
      branch: main
      repo: fabiogif/backend_moday
      deploy_on_push: true
    routes:
      - path: /api
        preserve_path_prefix: true
      - path: /store
        preserve_path_prefix: true
    run_command: heroku-php-apache2 public/
    envs:
      - key: APP_URL
        value: "https://moday-fullstack.ondigitalocean.app"
      - key: FRONTEND_URL
        value: "https://moday-fullstack.ondigitalocean.app"
```

4. Clique em **"Save"** e depois **"Deploy"**

### Depois de criar o app unificado:

1. Vá em **Settings** → você verá a opção **"HTTP Routes"**
2. Configure:
   - Path `/` → Component: `frontend`
   - Path `/api` → Component: `backend`
   - Path `/store` → Component: `backend`

---

## ✅ Solução Recomendada (SEM HTTP Routes)

**Mantenha os apps separados** e configure no frontend:

### No código do Frontend (Next.js):

```javascript
// .env.production
NEXT_PUBLIC_API_URL=https://orca-app-7hejo.ondigitalocean.app

// No código
const apiUrl = process.env.NEXT_PUBLIC_API_URL;
fetch(`${apiUrl}/store/empresa-oi/info`)
```

### Ou configure no painel do frontend:

1. Acesse app `clownfish-app-rr5rv`
2. **Settings** → **Environment Variables**
3. Adicione:
   ```
   Key: NEXT_PUBLIC_API_URL
   Value: https://orca-app-7hejo.ondigitalocean.app
   ```
4. Redeploy o frontend

---

## 🔍 Como Saber Se Você Tem HTTP Routes

### Indicadores de que você TEM HTTP Routes disponível:
- ✅ Um único app com múltiplos "Components" listados
- ✅ No Settings, aparece "HTTP Routes" ou "Component Routes"
- ✅ No overview, você vê vários componentes sob um único app

### Indicadores de que você NÃO TEM HTTP Routes:
- ❌ Dois apps separados na lista de apps
- ❌ Cada app tem seu próprio domínio
- ❌ Settings não mostra opção "HTTP Routes"

---

## 📊 Comparação das Abordagens

| Aspecto | Apps Separados | App Unificado |
|---------|----------------|---------------|
| Complexidade | ⭐ Simples | ⭐⭐⭐ Complexo |
| CORS | Necessário | Não necessário |
| HTTP Routes | ❌ Não disponível | ✅ Disponível |
| Escalabilidade | ⭐⭐⭐ Independente | ⭐⭐ Acoplada |
| Custo | 2 apps = mais caro | 1 app = mais barato |
| Manutenção | ⭐⭐⭐ Fácil | ⭐⭐ Média |

---

## 💡 Minha Recomendação Final

**NÃO configure HTTP Routes.** Continue com apps separados porque:

1. ✅ **Já está funcionando** - Backend respondendo corretamente
2. ✅ **CORS configurado** - Sem problemas de cross-origin
3. ✅ **Mais simples** - Menos configuração, menos problemas
4. ✅ **Melhor prática** - Separação de responsabilidades
5. ✅ **Escalável** - Frontend e backend escalam independentemente

**Apenas garanta que o frontend está fazendo requisições para:**
```
https://orca-app-7hejo.ondigitalocean.app/store/empresa-oi/info
```

