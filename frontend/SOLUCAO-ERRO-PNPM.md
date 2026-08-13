# Solução: Erro "pnpm: command not found" na Digital Ocean

## ⚠️ Erro Atual
```
bash: line 1: pnpm: command not found
ERROR component terminated with non-zero exit code: 127
Deploy Error: Run Command Not Executable
```

## 🔍 Causa do Problema
A Digital Ocean está tentando executar o comando `pnpm`, mas:
- O Dockerfile está configurado para usar `npm` (correto)
- Não há `pnpm` instalado no container
- Provavelmente há uma configuração manual no painel da Digital Ocean

## ✅ Solução - Passo a Passo

### Passo 1: Limpar Configurações no Painel Digital Ocean

1. Acesse: https://cloud.digitalocean.com/apps
2. Selecione seu app: `moday-frontend`
3. Clique em **Settings** (Configurações)
4. Clique em **Components** → selecione `web`
5. Procure e **DELETE/LIMPE** os seguintes campos (se estiverem preenchidos):
   - **Build Command** (Comando de Build)
   - **Run Command** (Comando de Execução)
6. Deixe ambos os campos **VAZIOS** ou com o valor padrão
7. Clique em **Save** (Salvar)

### Passo 2: Verificar Tipo de Build

Ainda na configuração do componente `web`:

1. Certifique-se que **Resource Type** = `Dockerfile`
2. **Dockerfile Path** = `Dockerfile`
3. **Context Directory** = `/` (raiz do projeto)
4. **HTTP Port** = `3000`

### Passo 3: Corrigir Health Check

1. Ainda na configuração do componente
2. Procure por **Health Check**
3. Configurar:
   - **Port**: `3000` (não `8080`)
   - **Path**: `/`
   - **Initial Delay**: `20` segundos
   - **Period**: `10` segundos

### Passo 4: Fazer Redeploy

Após salvar todas as configurações:

1. Vá para a aba **Deployments**
2. Clique em **Deploy** ou **Redeploy**
3. Aguarde o build completar

## 📝 O Que Mudou

### Arquivo Criado: `.do/app.yaml`
Agora você tem um arquivo de especificação que garante a configuração correta:
- ✅ Usa Dockerfile para build
- ✅ Porta 3000 configurada
- ✅ Health check correto
- ✅ Sem comandos customizados que causam conflito

## 🔧 Deploy Alternativo: Usando app.yaml

Se o problema persistir, você pode fazer deploy via CLI:

```bash
# Instalar doctl (se necessário)
brew install doctl  # macOS
# ou
snap install doctl  # Linux

# Autenticar
doctl auth init

# Criar novo app usando o app.yaml
doctl apps create --spec .do/app.yaml

# Ou atualizar app existente
doctl apps update YOUR_APP_ID --spec .do/app.yaml
```

Para descobrir o `YOUR_APP_ID`:
```bash
doctl apps list
```

## 🧪 Testar Localmente Antes

Sempre teste o build localmente antes de fazer deploy:

```bash
# Build da imagem Docker
docker build -t moday-frontend .

# Executar container
docker run -p 3000:3000 moday-frontend

# Acessar no navegador
open http://localhost:3000
```

## 📋 Checklist de Verificação

Antes de fazer deploy, certifique-se:

- [ ] Run Command está vazio/removido no painel Digital Ocean
- [ ] Build Command está vazio/removido no painel Digital Ocean
- [ ] Resource Type está como "Dockerfile"
- [ ] HTTP Port está configurado para 3000
- [ ] Health Check está na porta 3000
- [ ] Não há arquivos pnpm-lock.yaml no repositório
- [ ] O Dockerfile existe e está correto
- [ ] O build local funciona: `docker build -t test .`

## 🆘 Se o Erro Persistir

### Opção 1: Limpar Cache de Build
1. No painel Digital Ocean
2. Settings → Destroy
3. Criar novo app do zero usando `.do/app.yaml`

### Opção 2: Verificar Logs Detalhados
1. Vá em **Runtime Logs**
2. Procure por linhas que mostram qual comando está sendo executado
3. Se ver `pnpm` em algum lugar, há configuração manual restante

### Opção 3: Criar App do Zero
```bash
# Deletar app atual (cuidado!)
doctl apps delete YOUR_APP_ID

# Criar novo com configuração correta
doctl apps create --spec .do/app.yaml
```

## 📞 Comandos Úteis para Debug

```bash
# Ver lista de apps
doctl apps list

# Ver detalhes do app
doctl apps get YOUR_APP_ID

# Ver logs em tempo real
doctl apps logs YOUR_APP_ID --type run

# Ver specs atual do app
doctl apps spec get YOUR_APP_ID
```

## ⚡ Resumo da Solução

O problema é simples: **a Digital Ocean está executando `pnpm` em algum lugar, mas deveria usar `npm`**.

**Solução em 3 passos:**
1. Limpe/delete todos os comandos customizados no painel Digital Ocean
2. Certifique-se que está usando Dockerfile para build
3. Redeploy o app

O Dockerfile já está correto e usa `npm`. Só precisa remover as configurações manuais que estão sobrescrevendo isso.

