# ✅ Checklist de Deploy - Digital Ocean

Use este checklist antes de fazer deploy para evitar erros comuns.

## Antes do Deploy

- [ ] Commit do Dockerfile para o repositório
- [ ] Commit do .dockerignore para o repositório  
- [ ] Commit do next.config.ts atualizado (com output: 'standalone')
- [ ] Commit do .do/app.yaml para o repositório
- [ ] Push de todos os arquivos para o branch principal

## Configuração no Digital Ocean

- [ ] App criado e conectado ao repositório Git
- [ ] Build Method: **Dockerfile** (detectado automaticamente)
- [ ] HTTP Port configurado para: **3000**
- [ ] Health Check Port configurado para: **3000**
- [ ] Run Command: **VAZIO** (deixe em branco, o Dockerfile define)

## Variáveis de Ambiente (mínimas)

- [ ] `NODE_ENV=production`
- [ ] `NEXT_TELEMETRY_DISABLED=1`
- [ ] `PORT=3000`
- [ ] `HOSTNAME=0.0.0.0`
- [ ] `NEXT_PUBLIC_API_URL=<sua-api-url>` (se aplicável)

## Após o Deploy

- [ ] Verificar logs de build para erros
- [ ] Verificar se a aplicação iniciou (logs de runtime)
- [ ] Acessar a URL da aplicação
- [ ] Testar funcionalidades principais
- [ ] Configurar domínio customizado (opcional)

## Se Der Erro

### Exit Code 127 (pnpm not found)
→ Usar o Dockerfile mais recente, limpar build cache

### Health Check Failed (porta 8080)
→ Mudar health check port para 3000

### Run Command Not Executable
→ Remover qualquer run command customizado, deixar vazio

### Build Failed
→ Verificar logs, testar `npm run build` localmente

---

**Leia mais**: [DEPLOY-DIGITAL-OCEAN.md](./DEPLOY-DIGITAL-OCEAN.md)
