# Digital Ocean Deployment Fix

## Problemas Identificados

### 1. Primeiro Erro: `pnpm: command not found`
O Digital Ocean estava tentando usar o gerenciador de pacotes `pnpm`, mas o projeto está configurado para usar `npm`.

**Causa**: A ausência do arquivo `package-lock.json` no repositório.

### 2. Segundo Erro: `npm ci` sync error
O comando `npm ci` falhou com mensagem de que package.json e package-lock.json não estavam sincronizados.

**Causa**: 
- O `package-lock.json` estava incompleto ou desatualizado
- Faltavam dependências transitivas no lock file
- Sem especificação da versão do Node.js no package.json

## Soluções Aplicadas

### 1. Adicionado e Sincronizado `package-lock.json`
- Removido node_modules e package-lock.json antigo
- Executado `npm install --legacy-peer-deps` para gerar um arquivo completo e sincronizado
- Este arquivo garante que o Digital Ocean saiba que o projeto usa npm
- Contém todas as dependências transitivas necessárias

### 2. Especificado Versão do Node.js no `package.json`
```json
"engines": {
  "node": "20.x",
  "npm": "10.x"
}
```
- Garante que o buildpack use a versão correta do Node.js
- Compatível com o Dockerfile (node:20-alpine)

### 3. Atualizado `Dockerfile`
- Mudado de `npm ci --legacy-peer-deps || npm install --legacy-peer-deps` 
- Para apenas `npm install --legacy-peer-deps`
- Mais flexível e compatível com diferentes ambientes de build

### 4. Atualizado `.do/app.yaml`
- Adicionado `build_command: ""` para indicar que o build deve usar apenas o Dockerfile
- Isso evita que o Digital Ocean tente executar comandos de build fora do container

## Arquivos Alterados

1. **package.json**
   - Adicionado seção `engines` com Node.js 20.x e npm 10.x
   - Garante compatibilidade com o ambiente de produção

2. **package-lock.json** (novo/atualizado)
   - Arquivo de lock do npm com TODAS as dependências (12,426 linhas)
   - Inclui todas as dependências transitivas
   - Sincronizado com package.json
   - **DEVE** ser mantido no controle de versão

3. **Dockerfile**
   - Mudado de `npm ci --legacy-peer-deps || npm install` para apenas `npm install --legacy-peer-deps`
   - Mais resiliente a problemas de sincronização

4. **.do/app.yaml**
   - Adicionado `build_command: ""`
   - Mantém a configuração de usar apenas o Dockerfile

## Próximos Passos para Deploy

### 1. Push das Alterações
```bash
git push origin main
```

### 2. Verificar no Digital Ocean
O deploy deve iniciar automaticamente após o push. Verifique:
- O build está usando npm (não mais pnpm)
- O Dockerfile está sendo executado corretamente
- As 3 stages do Docker são concluídas com sucesso

### 3. Monitorar os Logs
Acompanhe os logs no painel do Digital Ocean para garantir que:
- `npm ci --legacy-peer-deps` executa com sucesso
- `npm run build` completa sem erros
- O container inicia com `node server.js`

## Estrutura do Build

O projeto usa um Dockerfile multi-stage:

1. **Stage 1 (deps)**: Instala as dependências usando npm
2. **Stage 2 (builder)**: Faz o build da aplicação Next.js
3. **Stage 3 (runner)**: Executa a aplicação em produção

## Verificação de Sucesso

Após o deploy, você deve ver:
- Status: Deploy Successful
- Aplicação acessível na URL fornecida pelo Digital Ocean
- Logs mostrando "Ready started server on 0.0.0.0:3000"

## Troubleshooting

Se ainda houver problemas:

1. **Verificar variáveis de ambiente**
   - Adicione as variáveis necessárias em `.do/app.yaml`
   - Exemplo: `NEXT_PUBLIC_API_URL`

2. **Verificar health check**
   - O health check está configurado para `/` na porta 3000
   - Initial delay: 20 segundos

3. **Verificar recursos**
   - Instance size: basic-xxs
   - Pode precisar aumentar se o build falhar por falta de memória

## Notas Importantes

- **NÃO** adicione `pnpm-lock.yaml` ao projeto
- **SEMPRE** use `npm install --legacy-peer-deps` para atualizar dependências
- **MANTENHA** o `package-lock.json` no controle de versão
- O Dockerfile já está otimizado para produção com output standalone

## Commits Realizados

### Commit 1
```
fix: Add package-lock.json and update Digital Ocean config to use npm instead of pnpm
```
Resolve o erro inicial de "pnpm: command not found".

### Commit 2 (Principal)
```
fix: Sync package-lock.json and add Node.js engine specification

- Added Node.js 20.x and npm 10.x engine requirements in package.json
- Regenerated package-lock.json to include all dependencies
- Updated Dockerfile to use npm install instead of npm ci for better compatibility
- This fixes the npm ci sync errors during Digital Ocean deployment
```
Resolve o erro de sincronização do npm ci e garante build consistente.
