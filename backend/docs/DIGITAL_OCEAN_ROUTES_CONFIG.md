# Configuração de HTTP Routes no Digital Ocean App Platform

## Situação Atual

Você tem dois apps separados no Digital Ocean:
- **Frontend (Next.js)**: `clownfish-app-rr5rv.ondigitalocean.app`
- **Backend (Laravel)**: `orca-app-7hejo.ondigitalocean.app`

## Problema Identificado

O frontend está tentando acessar `https://clownfish-app-rr5rv.ondigitalocean.app/store/empresa-oi`, mas essa URL aponta para o app do frontend (Next.js), não para o backend (Laravel).

## Soluções Possíveis

### Solução 1: Frontend faz requisições para o backend (RECOMENDADO) ✅

Esta é a arquitetura atual e a mais simples. Mantenha os apps separados:

**No código do Frontend:**
```javascript
// Configurar a URL base do backend
const API_URL = 'https://orca-app-7hejo.ondigitalocean.app';

// Fazer requisições para o backend
fetch(`${API_URL}/store/empresa-oi/info`)
  .then(res => res.json())
  .then(data => console.log(data));
```

**Vantagens:**
- ✅ Já está funcionando
- ✅ CORS já configurado
- ✅ Sem necessidade de mudanças na infraestrutura
- ✅ Mais simples de gerenciar

---

### Solução 2: Configurar HTTP Routes para usar um único domínio

Se você **realmente precisa** que ambos os serviços respondam no mesmo domínio, você tem duas opções:

#### Opção A: Usar o Painel de Controle do Digital Ocean

1. Acesse o Digital Ocean Dashboard
2. Vá até o app do frontend (`clownfish-app-rr5rv`)
3. Clique em **Settings** → **Domains**
4. Configure **HTTP Routes** ou **Component Routes**:
   - Path `/` → Frontend (Next.js)
   - Path `/api` → Backend (Laravel)
   - Path `/store` → Backend (Laravel)

**Nota:** Para isso funcionar, você precisaria ter ambos os componentes no **mesmo App** do Digital Ocean, não em apps separados.

#### Opção B: Criar um Único App com Múltiplos Componentes

Criar um novo `app.yaml` que inclua ambos os serviços:

```yaml
name: moday-fullstack
services:
  # Frontend Component
  - name: frontend
    environment_slug: node-js
    github:
      branch: main
      repo: seu-usuario/frontend-repo
      deploy_on_push: true
    routes:
      - path: /
    build_command: npm run build
    run_command: npm start
    
  # Backend Component  
  - name: backend
    environment_slug: php
    github:
      branch: main
      repo: seu-usuario/backend-repo
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
```

**Importante sobre `preserve_path_prefix`:**
- `true`: Laravel recebe `/api/store/...` ou `/store/...` (recomendado)
- `false`: Laravel recebe apenas `/store/...` (o `/api` ou `/store` é removido)

---

### Solução 3: Usar um Proxy Reverso (Nginx/CDN)

Configure um proxy reverso que direcione:
- `/` → Frontend
- `/api/*` → Backend  
- `/store/*` → Backend

**Opções:**
- Cloudflare Workers
- Nginx (requer servidor próprio)
- API Gateway

---

## Configuração Recomendada no Painel

Se você decidir unificar os apps, no **Painel de Controle do Digital Ocean**:

1. **Settings → Components**
   - Liste todos os componentes (frontend e backend)

2. **Settings → Domains**
   - Configure o domínio principal

3. **HTTP Routes** (pode aparecer em Settings)
   - Configure os paths para cada componente:
     ```
     Path: /          → Component: frontend
     Path: /api       → Component: backend (preserve_path_prefix: true)
     Path: /store     → Component: backend (preserve_path_prefix: true)
     ```

## Minha Recomendação

**Mantenha a arquitetura atual (apps separados)** e simplesmente configure o frontend para fazer requisições para:

```
https://orca-app-7hejo.ondigitalocean.app/store/empresa-oi/info
```

**Motivos:**
1. Já está funcionando perfeitamente
2. CORS já configurado
3. Mais simples de manter
4. Melhor separação de responsabilidades
5. Escalabilidade independente

## Verificação no Painel

Para verificar se há opção de HTTP Routes no seu painel:

1. Acesse: https://cloud.digitalocean.com/apps
2. Clique no app `clownfish-app-rr5rv`
3. Vá em **Settings**
4. Procure por:
   - "Routes"
   - "HTTP Routes"
   - "Component Routes"
   - "Routing"

Se não encontrar essas opções, significa que você está usando apps separados e precisa manter a arquitetura atual com requisições cross-origin do frontend para o backend.

