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
```
