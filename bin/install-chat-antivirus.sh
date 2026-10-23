#!/usr/bin/env bash
# Ubuntu/Debian: install ClamAV on the demonstration computer, never on Alwaysdata Free.
set -euo pipefail
if [[ ${EUID} -ne 0 ]]; then
    echo 'Lancer avec sudo bash bin/install-chat-antivirus.sh' >&2
    exit 1
fi
apt-get update
apt-get install -y clamav clamav-daemon
# Avoid competing with the updater started by package installation.
systemctl stop clamav-freshclam
trap 'systemctl start clamav-freshclam' EXIT
freshclam
python3 - <<'PY'
from pathlib import Path
import shutil
p = Path('/etc/clamav/clamd.conf')
backup = p.with_name('clamd.conf.before-expernet')
if not backup.exists():
    shutil.copy2(p, backup)
settings = {
    'StreamMaxLength': '12M',
    'MaxThreads': '2',
    'ConcurrentDatabaseReload': 'no',
}
lines = [line for line in p.read_text().splitlines()
         if not line.strip() or line.lstrip().startswith('#')
         or line.split()[0] not in settings]
lines += [f'{key} {value}' for key, value in settings.items()]
p.write_text('\n'.join(lines) + '\n')
PY
systemctl restart clamav-daemon
systemctl start clamav-freshclam
printf '%s\n' 'ClamAV charge ses signatures sur sa socket privée /run/clamav/clamd.ctl.'
