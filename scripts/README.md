# Scripts

**Novos `.sh` não vão na raiz.** Ver [`AGENTS.md`](../AGENTS.md) e `.cursor/rules/`.

## Testes (`scripts/test/`)

Rodar a partir da raiz do repositório:

```bash
./scripts/test/test-sistema.sh
./scripts/test/final-test.sh
./scripts/test/test-metrics.sh
./scripts/test/test-dashboard-complete.sh
./scripts/test/test-client-auth.sh
./scripts/test/test-public-store-order.sh
```

Os scripts entram sozinhos na raiz do repo antes de executar.

## Backend (`backend/scripts/`)

Rodar de qualquer lugar (entram em `backend/` automaticamente):

```bash
./backend/scripts/setup-docker.sh
./backend/scripts/run-tests.sh
./backend/scripts/reverb.sh
./backend/scripts/test-cache.sh
./backend/scripts/rotate-credentials.sh
./backend/scripts/setup-cron.sh      # instala a cron do Laravel Scheduler (job das 23:00)
./backend/scripts/upgrade-gitlab.sh   # automação de backup e upgrade seguro do GitLab CE no servidor

## Segurança (`scripts/test/`)

Purga de segredos do histórico do git (chaves SSH, `.env`, senhas) — cria um
clone purgado, não altera o repo original nem faz push:

```bash
./scripts/test/purge-secrets-history.sh
```
