# 🚨 AÇÃO URGENTE - ATUALIZAR RUN COMMAND NO PAINEL

## ⚠️ PROBLEMA

O Digital Ocean está AINDA usando o comando antigo `node server.js`.

Os arquivos Procfile e app.yaml estão corretos com `node .next/standalone/server.js`, 
MAS o painel do Digital Ocean está sobrescrevendo com configuração antiga.

## ✅ SOLUÇÃO IMEDIATA

### PASSO A PASSO - FAÇA AGORA:

1. **Acesse**: https://cloud.digitalocean.com/
2. **Entre em**: App Platform → moday-frontend
3. **Clique em**: Settings (no menu lateral)
4. **Procure**: "Components" ou "Resources"
5. **Clique em**: web (ou o nome do seu componente)
6. **Procure**: "Run Command" ou "Commands"
7. **MUDE DE**:
   ```
   node server.js
   ```
   **PARA**:
   ```
   node .next/standalone/server.js
   ```
8. **Clique em**: Save
9. **Clique em**: "Deploy" ou "Redeploy"

## 📋 Outras Opções

### Opção A: Limpar Run Command
Se a opção acima não funcionar, tente:
1. **DEIXE O RUN COMMAND EM BRANCO** (vazio)
2. Isso vai usar o Procfile automaticamente
3. Salve e faça redeploy

### Opção B: Editar App Spec Diretamente
1. Settings → "App Spec"
2. Procure por `run_command`
3. Mude para: `node .next/standalone/server.js`
4. Salve e faça redeploy

### Opção C: Via CLI (mais técnico)
```bash
doctl apps update YOUR_APP_ID --spec .do/app.yaml
```

## 🎯 O Que Você Deve Ver

Após atualizar e fazer redeploy, nos logs você deve ver:

```
=====> Starting web process with command `node .next/standalone/server.js`
       ▲ Next.js 15.4.7
       - Local:        http://0.0.0.0:3000
       
       ✓ Ready in 2s
```

**NÃO** deve mais aparecer:
```
Error: Cannot find module '/workspace/server.js'
```

## 🔍 Como Verificar Se Funcionou

Nos logs do deploy, procure por:
- ✅ "Starting web process with command" → deve mostrar `.next/standalone/server.js`
- ✅ "▲ Next.js" → servidor iniciou
- ✅ "Ready in Xs" → aplicação pronta
- ✅ "Health check passed" → deploy bem-sucedido

## 💡 Por Que Isso Acontece?

Digital Ocean App Platform tem 2 níveis de configuração:
1. **Arquivos do repositório** (Procfile, app.yaml)
2. **Painel Web** (configurações manuais)

O **painel web TEM PRIORIDADE** sobre os arquivos!

Quando você criou o app, pode ter definido um run command manualmente.
Esse comando fica "travado" no painel e ignora mudanças nos arquivos.

## 🎯 AÇÃO IMEDIATA

**VAI AGORA PARA O PAINEL DO DIGITAL OCEAN E MUDE O RUN COMMAND!**

Sem essa mudança manual, o deploy NUNCA vai funcionar, não importa quantos commits façamos.

---

**Tempo estimado**: 2 minutos
**Dificuldade**: Fácil
**Urgência**: CRÍTICA
**Resultado**: Deploy finalmente vai funcionar! 🚀
