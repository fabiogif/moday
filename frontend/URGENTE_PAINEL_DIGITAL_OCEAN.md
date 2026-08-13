# 🚨 AÇÃO URGENTE - Atualizar Configuração no Painel Digital Ocean

## ⚠️ PROBLEMA CRÍTICO IDENTIFICADO

O Digital Ocean está **IGNORANDO** completamente o arquivo `.do/app.yaml` do repositório!

**Evidência:**
- Logs mostram "pnpm: command not found" 
- Health check tentando porta 8080
- Isso significa configuração manual no painel está sobrescrevendo tudo

## ✅ SOLUÇÃO: ATUALIZAR NO PAINEL WEB

### 🔧 PASSOS OBRIGATÓRIOS NO DIGITAL OCEAN

#### 1. Acesse o Painel Digital Ocean

1. Vá para https://cloud.digitalocean.com/
2. Entre em **App Platform**
3. Selecione a aplicação **moday-frontend**

#### 2. Vá para Settings

No menu lateral, clique em **Settings**

#### 3. Atualize as Configurações

Encontre e clique em cada seção abaixo:

---

### 📝 CONFIGURAÇÕES PARA ATUALIZAR

#### A. **Resource Settings** ou **Components**

Clique em **web** (ou o nome do seu componente)

##### **Environment Variables**
Adicione/Verifique:
```
NODE_ENV = production
PORT = 3000
HOSTNAME = 0.0.0.0
NEXT_TELEMETRY_DISABLED = 1
```

##### **HTTP Port**
```
3000
```

##### **Run Command** (IMPORTANTE!)
**REMOVA** qualquer comando customizado ou defina:
```
node server.js
```

**OU deixe em branco** para usar o Procfile

##### **Build Command** (IMPORTANTE!)
Defina como:
```
npm install && npm run build
```

**OU:**
```
npm run build
```

#### B. **Health Check**

```
HTTP Path: /
Port: 3000
Initial Delay: 60 seconds
Timeout: 5 seconds
```

#### C. **Source** (Verificar)

```
Branch: main
Auto Deploy: ✓ Enabled
```

---

### 🔴 CONFIGURAÇÕES PARA REMOVER/DESABILITAR

#### NÃO deve ter:
- ❌ Custom Dockerfile path (remova se existir)
- ❌ Custom build command com pnpm
- ❌ Porta 8080 em qualquer lugar
- ❌ Run command com pnpm

---

### 🎯 CONFIGURAÇÃO ALTERNATIVA MAIS SIMPLES

Se as configurações acima não funcionarem, tente esta abordagem:

#### No painel do Digital Ocean:

1. **Run Command**: **DEIXE EM BRANCO**
   - Digital Ocean vai usar automaticamente o Procfile: `web: node server.js`

2. **Build Command**: 
   ```
   npm install --legacy-peer-deps && npm run build
   ```

3. **HTTP Port**: `3000`

4. **Environment Variables**:
   ```
   NODE_ENV=production
   PORT=3000
   ```

---

### 🔄 DEPOIS DE ATUALIZAR

1. **Salve** todas as alterações
2. Clique em **"Deploy"** ou **"Rebuild and Deploy"**
3. Monitore os logs

---

## 📊 LOGS ESPERADOS (Sucesso)

Quando configurado corretamente, você deve ver:

```
=====> Downloading Buildpack: https://github.com/heroku/heroku-buildpack-nodejs.git
=====> Detected Framework: Node.js

-----> Creating runtime environment
       NODE_ENV=production
       
-----> Installing binaries
       engines.node (package.json):  20.x
       engines.npm (package.json):   10.x
       
       Resolving node version 20.x...
       Downloading and installing node 20.19.2...
       npm 10.9.4 installed

-----> Installing dependencies
       Installing node modules
       npm install --legacy-peer-deps
       
       added 229 packages in 45s

-----> Build
       Running build
       npm run build
       
       > build
       > next build --no-lint
       
       ✓ Creating an optimized production build
       ✓ Compiled successfully

-----> Build succeeded!
       
-----> Discovering process types
       Procfile declares types -> web

-----> Launching...
       Released
       
=====> Starting web process
       > node server.js
       
       ▲ Next.js 15.4.7
       - Local:        http://localhost:3000
       
       ✓ Ready in 2s
```

---

## 🚨 SE AINDA APARECER "pnpm: command not found"

Isso significa que há uma configuração que está forçando pnpm. Procure por:

### No Painel Digital Ocean:

1. **Settings → App Spec**
   - Verifique se há referência a pnpm
   - Se tiver, edite e remova

2. **Settings → Components → web**
   - Verifique "Buildpacks" ou "Build Configuration"
   - Se tiver buildpack customizado, remova

3. **Force Clean Rebuild**
   - Settings → "Destroy" (cuidado!)
   - Ou Settings → "Force Rebuild & Deploy"

---

## 📋 CHECKLIST FINAL

Antes de fazer deploy, verifique no painel:

- [ ] HTTP Port = 3000 (NÃO 8080)
- [ ] Run Command = vazio ou "node server.js"
- [ ] Build Command = "npm install --legacy-peer-deps && npm run build"
- [ ] Health Check Port = 3000
- [ ] Health Check Delay = 60 segundos
- [ ] Variável PORT = 3000
- [ ] Branch = main
- [ ] Sem referências a pnpm
- [ ] Sem referências a porta 8080

---

## 🎯 POR QUE O .do/app.yaml NÃO FUNCIONA

O Digital Ocean App Platform tem dois modos:

1. **Spec-based**: Usa apenas o app.yaml
2. **Console-based**: Usa configurações do painel web

Quando você cria um app pelo painel, ele fica em modo "console-based" e **ignora** o app.yaml!

### Para forçar uso do app.yaml:

1. Delete o app atual no Digital Ocean
2. Crie um novo usando "Import from GitHub"
3. Na criação, escolha "Edit App Spec" e cole o conteúdo de `.do/app.yaml`

**OU**

Configure manualmente no painel conforme instruções acima (mais rápido).

---

## 📞 SE NADA FUNCIONAR

### Última Opção: Recrear o App

1. No Digital Ocean, **Delete** o app moday-frontend
2. Crie um **novo app**
3. Escolha **"GitHub"** como source
4. Selecione o repositório `moday_frontend`
5. Branch: `main`
6. **Durante a criação**, clique em **"Edit App Spec"**
7. Cole este conteúdo:

```yaml
name: moday-frontend
region: nyc
services:
  - name: web
    environment_slug: node-js
    github:
      branch: main
      deploy_on_push: true
      repo: fabiogif/moday_frontend
    http_port: 3000
    instance_count: 1
    instance_size_slug: basic-xxs
    routes:
      - path: /
    build_command: npm install --legacy-peer-deps && npm run build
    run_command: node server.js
    envs:
      - key: NODE_ENV
        value: production
      - key: PORT
        value: "3000"
    health_check:
      http_path: /
      port: 3000
      initial_delay_seconds: 60
```

8. Clique em **"Next"** e **"Create Resources"**

---

**AÇÃO IMEDIATA**: Vá ao painel do Digital Ocean AGORA e atualize as configurações manualmente!
