# Plano de migração MySQL → Oracle Autonomous Database

Status: **plano** (não executado). Complexidade: **High**. Não há driver Oracle no Laravel hoje; produção e CI são MySQL/SQLite.

Objetivo: mover o banco do Moday (`moday_production` no container `moday-mysql`) para o Autonomous Database **RESTTECDB** na região `sa-saopaulo-1`, usando a wallet em `db_oracle/`.

Este documento não contém senhas. Usuário/senha do schema (ADMIN ou usuário de aplicação) ficam só no `.env` do servidor e no cofre OCI — nunca na wallet versionada.

---

## 0. Decisão de go / no-go (obrigatória antes de qualquer cutover)

A migração só segue se o **spike da Fase 1** passar:

1. PHP 8.3 + Instant Client + `pdo_oci`/`oci8` no **mesmo Docker ARM64** de produção (`Dockerfile.production` hoje é `php:8.3-fpm-alpine` com `pdo_mysql` apenas).
2. Laravel 11 autenticando via TNS `resttecdb_tp` (ou `resttecdb_medium`) com a wallet.
3. Uma tabela de smoke (`SELECT 1 FROM DUAL` + insert/select com sequence/identity).

Se o Instant Client no Alpine ARM64 falhar (cenário comum), o plano B é imagem **Debian/Oracle Linux aarch64**, não Alpine. Sem esse spike verde, **não** se reescreve o schema nem se desliga o MySQL.

---

## 1. Inventário da origem e do destino

### Origem (produção atual)

| Item | Valor |
|---|---|
| Engine | MySQL 8.0 (`moday-mysql` em `docker-compose.production.yml`) |
| Database | `moday_production` (`.env.production.example`) |
| Volume | `moday_mysql_data` |
| App | Laravel 11 / PHP 8.2–8.3, `DB_CONNECTION=mysql` |
| Backup | `backend/deploy/backup-moday-db.sh` (`mysqldump`) |
| Testes CI | SQLite (`phpunit.xml`) — **permanecem SQLite** |
| Outro consumidor | Evolution API no `docker-compose.yml` de dev ainda usa MySQL próprio |

~178 migrations em `backend/database/migrations/`. Várias são **MySQL-only** (`enum` ALTER, `FULLTEXT`, `DATE_FORMAT`, `information_schema`).

SQL MySQL no código de runtime (precisa de equivalente Oracle):

- `SearchesFullText` — `MATCH ... AGAINST`
- `DashboardRepository` — `DATE_FORMAT(ordered_at, …)`
- Seeders/migrations com `if (driver === 'mysql')`

### Destino (wallet `db_oracle/`)

| Item | Valor |
|---|---|
| Serviço | Oracle Autonomous Database |
| Nome | `RESTTECDB` (`g865c65514b74c5_resttecdb`) |
| Região | `sa-saopaulo-1` |
| Endpoint TCPS | `adb.sa-saopaulo-1.oraclecloud.com:1522` |
| Aliases TNS | `resttecdb_high`, `resttecdb_medium`, `resttecdb_low`, `resttecdb_tp`, `resttecdb_tpurgent` |
| Wallet baixada | 2026-08-18 |
| Certificado SSL até | 2031-08-17 (renovar wallet antes) |
| SQL worksheet | Database Actions / ORDS do próprio ADB |

**Alias recomendado para o Laravel (OLTP):** `resttecdb_tp` (transaction processing). `high` é para batch/analytics; `low` é demais conservador para API.

Arquivos presentes na pasta:

- `tnsnames.ora`, `sqlnet.ora`, `ojdbc.properties`, `cwallet.sso`, `README`

Arquivos típicos de wallet **ausentes** nesta cópia (`ewallet.p12`, `keystore.jks`, `truststore.jks`, `ewallet.pem`). Para Instant Client com auto-login, `cwallet.sso` costuma bastar. Se a conexão falhar com “wallet not found”, baixar de novo o zip completo no console OCI.

`sqlnet.ora` veio com placeholder:

```text
DIRECTORY="?/network/admin"
```

Isso **não** aponta para `db_oracle/`. Em todo ambiente (dev, Docker, produção) o `DIRECTORY` precisa ser o caminho absoluto da pasta da wallet, e `TNS_ADMIN` o mesmo diretório.

Usuário de banco: a wallet **não traz** `ADMIN` nem senha. Criar no ADB um schema de aplicação (não usar `ADMIN` no Laravel).

---

## 2. O que não entra nesta migração

- Testes PHPUnit / CI: continuam `DB_CONNECTION=sqlite`.
- Redis, Reverb, storage de arquivos: fora do escopo.
- Evolution API: schema separado; só migrar se for requisito explícito.
- Reescrita “limpa” de todas as 178 migrations para dialeto Oracle: alto custo, baixo ganho. Schema no ADB nasce de **migração do dump**, não de `php artisan migrate` do zero (exceto ambiente vazio de homologação, se o spike do yajra funcionar).

---

## 3. Segurança da wallet

1. `db_oracle/` já está no `.gitignore` (`/db_oracle/` e `*.sso`). **Não commitar a wallet.**
2. No servidor: `/home/ubuntu/apps/moday-backend/wallet/` (ou secret mount), mode `700`, dono do processo PHP.
3. Rotação: senha do schema no OCI Vault / `.env`; wallet só quando o certificado chegar perto de 2031 (ou se comprometida).
4. Network ACL do ADB: permitir o IP público (ou VCN) do compute de produção. Sem isso o TNS recusa mesmo com wallet correta.

---

## 4. Fases

### Fase 1 — Spike de conectividade (2–4 dias)

Entregável: container que abre sessão Oracle e um script `php artisan oracle:ping`.

1. Instalar Oracle Instant Client (aarch64) + `php-oci8` / `pdo_oci` na imagem de spike (partir de Oracle Linux 8/9 aarch64, não Alpine, se o Alpine travar).
2. Copiar wallet para o container; ajustar `sqlnet.ora` `WALLET_LOCATION`.
3. Variáveis:

```bash
TNS_ADMIN=/opt/oracle/wallet
DB_CONNECTION=oracle
DB_TNS=resttecdb_tp
DB_USERNAME=<schema_app>
DB_PASSWORD=<nao_versionar>
```

4. Pacote Laravel: [`yajra/laravel-oci8`](https://github.com/yajra/laravel-oci8) (driver `oracle` no `config/database.php`). Não inventar um driver interno.
5. Critério de saída: ping + insert/select em tabela de teste.

**Se falhar no ARM64:** avaliar Instant Client x86_64 só em homologação, ou proxy de banco — mas produção atual é ARM; o spike tem de ser ARM.

### Fase 2 — Inventário de incompatibilidade (3–5 dias)

Lista viva (grep + execução em staging MySQL):

| Padrão MySQL | Ação no Oracle |
|---|---|
| `enum(...)` | `VARCHAR2` + check constraint |
| `BOOLEAN` / `tinyint(1)` | `NUMBER(1)` (yajra) |
| `AUTO_INCREMENT` | `IDENTITY` ou sequence + trigger (yajra) |
| `JSON` | `JSON` / `CLOB` (ADB 19c+) |
| `FULLTEXT` / `MATCH AGAINST` | Oracle Text (`CONTAINS`) **ou** LIKE na 1ª versão |
| `DATE_FORMAT` | `TO_CHAR` |
| `IFNULL` / `GROUP_CONCAT` | `NVL` / `LISTAGG` |
| identificadores > 30 chars (pré-12.2) | ADB moderno aceita 128; validar |
| `utf8mb4` | `AL32UTF8` (padrão ADB) |
| `ON UPDATE CURRENT_TIMESTAMP` | trigger |

Repositórios e queries raw: `DashboardRepository`, `SearchesFullText`, relatórios financeiros, `fromSub` (já usado em `ClientService`).

Decisão de produto na busca: na 1ª cutover, `SearchesFullText` pode cair no ramo LIKE (já existe para SQLite) até Oracle Text estar configurado. Isso desbloqueia a migração sem bloquear em FULLTEXT.

### Fase 3 — Schema e carga (1–2 semanas, homologação)

Estratégia recomendada (dump → transform → load), **não** replay de 178 migrations no Oracle:

1. Snapshot MySQL de homologação (cópia anonimizada ou restore do backup diário).
2. Ferramenta de schema:
   - Oracle SQL Developer Migration / SQLcl, **ou**
   - `mysqldump --no-data` + conversão (SQLines / script interno).
3. Criar tablespace/schema `MODAY` (ou `RESTTEC`) no ADB; grants mínimos.
4. Carga de dados: Data Pump não lê MySQL. Caminhos viáveis:
   - CSV/JSON por tabela via SQL Developer / `sqlldr` / ORDS;
   - ferramenta ETL (Airbyte, custom PHP) tabela a tabela, respeitando FKs;
   - Oracle Database Migration Service só se a conta OCI tiver o serviço habilitado.
5. Validação: contagem de linhas por tabela, checksums de `users`, `orders`/`sale_orders`, `products`, `tenants`; amostragem de FKs.
6. Sequences: após carga, `ALTER SEQUENCE … RESTART` (ou `IDENTITY` start) para `MAX(id)+1`.

Homologação Laravel aponta para o ADB **sem** desligar o MySQL de produção.

### Fase 4 — Adaptação da aplicação (paralelo à Fase 3)

1. `config/database.php`: connection `oracle` (yajra).
2. `.env.production.example`: bloco documentado (sem secret).
3. `Dockerfile.production`: Instant Client + oci8; remover `depends_on: mysql` quando o cutover for real.
4. Queries raw: `DATE_FORMAT` → helper por driver (`mysql` / `oracle` / `sqlite`).
5. `SearchesFullText`: ramo `oracle` (LIKE ou Oracle Text).
6. Migrations futuras: escritas de forma driver-aware (padrão já usado em `FULLTEXT` e `orders.status`).
7. Backup: novo script (`expdp` ou `DBMS_CLOUD` / backup automático do ADB). Manter `mysqldump` até D+14 do cutover.
8. Feature tests que batem em MySQL real: nenhum no CI hoje (SQLite). Smoke HTTP de produção precisa de um job novo contra ADB de homologação.

Não mudar o frontend: a API continua JSON.

### Fase 5 — Cutover (janela curta)

Pré-requisitos: Fases 1–4 verdes em homologação; backup MySQL fresco; ACL de rede ADB; wallet no servidor.

1. Manutenção: API em 503 / flag de escrita.
2. `mysqldump` final + conferência de volume.
3. Replay incremental (tabelas quentes: pedidos, pagamentos, webhooks) se o dump da Fase 3 estiver defasado.
4. Trocar `DB_CONNECTION` / TNS no `.env`, `php artisan config:clear`.
5. Smoke: login JWT, criar pedido, dashboard, PDV.
6. Monitorar 24–48 h (erros ORA-*, latência TNS 1522, wallet).
7. MySQL **não** é apagado. Volume `moday_mysql_data` permanece até D+14 com o container `profiles: [donotstart]` ou só bind interno.

Rollback (até D+14): reverter `.env` para `DB_CONNECTION=mysql`, `DB_HOST=mysql`, subir `moday-mysql`. Perda: escritas feitas só no Oracle na janela. Sem dual-write, o rollback é “voltar ao dump + binlog da janela” — por isso a janela tem de ser curta e com freeze de escrita.

### Fase 6 — Enxugamento (depois de D+14 estável)

1. Remover serviço `mysql` do compose de produção.
2. Trocar backup systemd de `mysqldump` para política ADB (auto backup OCI).
3. Documentar renovação da wallet (2031, com lembrete anual).

---

## 5. Configuração Laravel prevista (não aplicar agora)

```php
// config/database.php — após instalar yajra/laravel-oci8
'oracle' => [
    'driver'         => 'oracle',
    'tns'            => env('DB_TNS', 'resttecdb_tp'),
    'username'       => env('DB_USERNAME'),
    'password'       => env('DB_PASSWORD'),
    'charset'        => env('DB_CHARSET', 'AL32UTF8'),
    'prefix'         => '',
    'prefix_schema'  => env('DB_SCHEMA', ''),
],
```

```bash
# servidor — nunca no git
TNS_ADMIN=/opt/oracle/wallet
DB_CONNECTION=oracle
DB_TNS=resttecdb_tp
DB_USERNAME=moday_app
DB_PASSWORD=...
```

O `sqlnet.ora` no servidor deve ter `DIRECTORY` = o mesmo path de `TNS_ADMIN`.

---

## 6. Riscos

| Risco | Impacto | Mitigação |
|---|---|---|
| Instant Client + PHP no Alpine ARM64 | Bloqueia a migração | Spike Fase 1; imagem Oracle Linux |
| SQL MySQL escondido em repositórios | 500 em produção | inventário Fase 2 + smoke Fase 5 |
| FULLTEXT inexistente | busca ruim | LIKE na 1ª versão |
| Sequences dessincronizadas | ORA-00001 no insert | reset pós-carga |
| Wallet no git | vazamento | `.gitignore`; pasta só no servidor |
| Evolution API ainda MySQL | duas engines | fora do cutover Moday |
| Dual-write não implementado | rollback perde dados novos | freeze de escrita na janela |
| Latência TCPS 1522 | API mais lenta que socket Unix MySQL local | alias `tp`; connection pool; medir no spike |

---

## 7. Esforço estimado

| Fase | Calendário |
|---|---|
| 1 Spike conectividade ARM | 2–4 dias |
| 2 Inventário SQL | 3–5 dias |
| 3 Schema + carga homolog | 1–2 semanas |
| 4 App Laravel | 1–2 semanas (paralelo) |
| 5 Cutover | 1 janela (2–4 h) + 14 dias de MySQL standby |
| 6 Enxugamento | 1–2 dias |

Ordem de grandeza: **3–5 semanas** com uma pessoa full-time depois do spike verde. Sem spike verde, o projeto para.

---

## 8. Próxima ação (precisa de aprovação)

Este plano **não** instala `yajra/laravel-oci8`, **não** altera o Dockerfile e **não** toca o MySQL de produção.

Próximo passo, se aprovado: **Fase 1** — spike de conexão no Docker ARM com a wallet (cópia local, `sqlnet.ora` corrigido) e um comando artisan de ping. Para isso é necessário o **usuário/senha do schema** no ADB (não estão em `db_oracle/`).
