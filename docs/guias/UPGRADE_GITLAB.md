# Guia de Operação e Upgrade do GitLab Server

Este documento descreve o procedimento de atualização do **GitLab Community Edition (CE)** hospedado no servidor de produção Oracle Cloud (`163.176.233.174`).

---

## 1. Topologia da Instalação

* **Diretório no Servidor:** `/home/ubuntu/apps/gitlab`
* **Tipo de Deploy:** Docker Compose (`gitlab/gitlab-ce`)
* **Domínio:** `https://gitlab.albatec.com.br`
* **Portas Locais:**
  - Web: `127.0.0.1:8929` (redirecionado via Nginx com SSL)
  - SSH: porta `2222` (para commits e clones via SSH)
* **Volumes Persistentes:**
  - `./config`: `/etc/gitlab` (contém `gitlab.rb` e `gitlab-secrets.json`)
  - `./logs`: `/var/log/gitlab`
  - `./data`: `/var/opt/gitlab` (repositórios git, banco PostgreSQL embutido, uploads)

---

## 2. Regras Oficiais de Upgrade do GitLab

O GitLab **não suporta pulos diretos entre versões maiores (major)** nem pulos de múltiplos releases menores que contenham migrações obrigatórias (*required upgrade stops*).

Para a linha **GitLab 19**, as paradas obrigatórias ocorrem nas versões:
$$\text{19.2.x} \longrightarrow \text{19.5.x} \longrightarrow \text{19.8.x} \longrightarrow \text{19.11.x}$$

* **Regra 1:** Sempre atualize primeiro para o último patch da versão menor atual antes de avançar para a próxima parada obrigatória.
* **Regra 2:** Antes de ir para o próximo salto, confirme que todas as *Batched Background Migrations* terminaram (fila zerada).
* **Referência:** Consulte o assistente oficial em `https://gitlab.com/gitlab-org/upgrade-path`.

---

## 3. Script Automatizado de Upgrade (`upgrade.sh`)

O script padronizado está instalado no servidor em:
`/home/ubuntu/apps/gitlab/upgrade.sh`

### O que o script faz automaticamente:
1. Valida se a versão alvo foi informada.
2. Checa se o contêiner atual está ativo e se há migrações de banco em background pendentes. Se houver, ele aborta a operação para evitar corrupção de banco.
3. Cria backup automático de `gitlab-secrets.json`, `gitlab.rb` e `docker-compose.yml` em `/home/ubuntu/gitlab-pre-upgrade-backup_YYYYMMDD_HHMMSS/`.
4. Atualiza a tag da imagem no `docker-compose.yml` e faz backup com timestamp.
5. Executa `docker compose pull gitlab`.
6. Reinicia o serviço com `docker compose up -d gitlab`.
7. Abre o streaming de logs para acompanhar a migração do banco de dados.

---

## 4. Passo a Passo para Executar o Upgrade

1. Conecte no servidor:
   ```bash
   ssh ubuntu@163.176.233.174
   ```

2. Acesse a pasta do GitLab:
   ```bash
   cd /home/ubuntu/apps/gitlab
   ```

3. Verifique a versão atual:
   ```bash
   docker exec gitlab gitlab-rake gitlab:env:info | grep Version
   ```

4. Execute o script passando a tag desejada:
   ```bash
   ./upgrade.sh 19.2.7-ce.0
   ```

5. Acompanhe os logs até o GitLab finalizar o `reconfigure` e inicializar o Puma e Sidekiq.
   Para sair do log, pressione `Ctrl + C`.

---

## 5. Procedimento de Rollback em Caso de Falha

Caso uma nova versão falhe ao iniciar ou rejeite a migração:

1. Restaure o `docker-compose.yml` anterior:
   ```bash
   cd /home/ubuntu/apps/gitlab
   cp docker-compose.yml.bak-YYYYMMDD_HHMMSS docker-compose.yml
   ```
2. Recrie o contêiner na versão anterior:
   ```bash
   docker compose up -d gitlab
   ```
3. Os arquivos de configuração salvos antes do upgrade estarão intactos em:
   `/home/ubuntu/gitlab-pre-upgrade-backup_YYYYMMDD_HHMMSS/`
