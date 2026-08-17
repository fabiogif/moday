#!/usr/bin/env bash
# Reinicia o frontend RestTec via systemd.
# Requer que a unit albatecrest-frontend.service ja esteja instalada/habilitada
# no servidor e que o sudoers do runner libere exatamente estes dois comandos
# (sem sufixo .service, pra bater com o /etc/sudoers.d/gitlab-runner-deploy):
#   systemctl restart albatecrest-frontend
#   systemctl status albatecrest-frontend
set -euo pipefail

sudo -n systemctl restart albatecrest-frontend
sleep 6
sudo -n systemctl status albatecrest-frontend
