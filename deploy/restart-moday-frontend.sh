#!/usr/bin/env bash
# Reinicia o frontend Moday via systemd.
# Requer que a unit moday-frontend.service ja esteja instalada/habilitada
# no servidor e que o sudoers do runner libere exatamente estes dois comandos
# (sem sufixo .service, pra bater com o /etc/sudoers.d/gitlab-runner-deploy):
#   systemctl restart moday-frontend
#   systemctl status moday-frontend
set -euo pipefail

sudo -n systemctl restart moday-frontend
sleep 6
sudo -n systemctl status moday-frontend
