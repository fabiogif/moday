#!/bin/bash
# Atalho: repara cache (Sail/dev), permissões de storage e dependências de produção
set -e
exec "$(dirname "$0")/scripts/fix-production-runtime.sh"
