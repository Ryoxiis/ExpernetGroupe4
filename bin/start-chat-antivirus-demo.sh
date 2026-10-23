#!/usr/bin/env bash
# Private demonstration: local ClamAV scans jobs transferred over ordinary SSH.
# Run as your normal user, after install-chat-antivirus.sh. Keep this terminal open.
set -euo pipefail
root=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)
remote_user=expernet@ssh-expernet.alwaysdata.net
remote_app=/home/expernet/expernet-app
python3 - <<'PY'
import socket
with socket.socket(socket.AF_UNIX) as s:
    s.settimeout(5)
    s.connect('/run/clamav/clamd.ctl')
    s.sendall(b'zPING\0')
    if s.recv(64) != b'PONG\0':
        raise SystemExit('ClamAV ne répond pas encore. Attendre le chargement des signatures.')
print('ClamAV local prêt.')
PY
control_dir=$(mktemp -d /tmp/expernet-antivirus.XXXXXXXX)
cleanup() {
    if [[ -n ${worker_pid:-} ]]; then kill "$worker_pid" 2>/dev/null || true; fi
    ssh -S "$control_dir/control" -O exit "$remote_user" >/dev/null 2>&1 || true
    # Only remove this script's own temporary control files.
    python3 - "$control_dir" <<'PY'
from pathlib import Path
import sys
folder = Path(sys.argv[1])
for name in ['control', 'known_hosts']:
    (folder / name).unlink(missing_ok=True)
folder.rmdir()
PY
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
ssh-keyscan -T 10 -t ed25519 ssh-expernet.alwaysdata.net > "$control_dir/known_hosts" 2>/dev/null
fingerprint=$(ssh-keygen -lf "$control_dir/known_hosts" | awk '{print $2}')
if [[ $fingerprint != SHA256:5i/vJYokzNsnXAeHkwzEm+3kxPQWwsRzwXFPQ7oOvNI ]]; then
    echo 'Empreinte SSH différente de celle vérifiée dans Alwaysdata : arrêt.' >&2
    exit 1
fi
ssh_options=(-S "$control_dir/control" -o "UserKnownHostsFile=$control_dir/known_hosts" -o StrictHostKeyChecking=yes)
# One password prompt. All subsequent transfers reuse this encrypted connection.
ssh "${ssh_options[@]}" -M -fNT -o ControlPersist=no -o ServerAliveInterval=30 \
    -o ServerAliveCountMax=3 "$remote_user"
ssh "${ssh_options[@]}" "$remote_user" 'umask 077; mkdir -p /home/expernet/expernet-app/storage/antivirus; chmod 700 /home/expernet/expernet-app/storage/antivirus'
scp -o "ControlPath=$control_dir/control" -o "UserKnownHostsFile=$control_dir/known_hosts" \
    -o StrictHostKeyChecking=yes "$root/bin/scan-chat-queue.py" "$remote_user:$remote_app/bin/scan-chat-queue.py"
ssh "${ssh_options[@]}" "$remote_user" 'umask 077; chmod 700 /home/expernet/expernet-app/bin/scan-chat-queue.py; mkdir -p /home/expernet/expernet-app/storage/antivirus/jobs; chmod 700 /home/expernet/expernet-app/storage/antivirus/jobs'
python3 "$root/bin/chat-antivirus-worker.py" --control "$control_dir/control" --known-hosts "$control_dir/known_hosts" &
worker_pid=$!
# Enable only after actual verdicts obtained through private file transfers.
ssh "${ssh_options[@]}" "$remote_user" 'python3 -' <<'REMOTE_PY'
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import time
app = Path('/home/expernet/expernet-app')
queue = app / 'storage/antivirus/jobs'
for attempt in range(20):
    if (queue / 'heartbeat').exists() and time.time() - (queue / 'heartbeat').stat().st_mtime < 10:
        break
    time.sleep(0.5)
else:
    raise SystemExit('Le worker prive ne repond pas. Configuration non activee.')
scanner = str(app / 'bin/scan-chat-queue.py')
env = dict(os.environ, CHAT_AV_QUEUE=str(queue))
with tempfile.TemporaryDirectory(dir=app / 'storage/antivirus') as folder:
    sample = Path(folder) / 'test.txt'
    sample.write_bytes(b'Expernet: harmless private antivirus test.\n')
    assert subprocess.run([scanner, '--no-summary', str(sample)], env=env, timeout=30).returncode == 0, 'Fichier sain refuse'
    sample.write_bytes(bytes.fromhex('58354f2150254041505b345c505a58353428505e2937434329377d2445494341522d5354414e444152442d414e544956495255532d544553542d46494c452124482b482a'))
    assert subprocess.run([scanner, '--no-summary', str(sample)], env=env, timeout=30).returncode == 1, 'EICAR non detecte'
config = app / '.env'
backup = app / '.env.before-private-antivirus'
if not backup.exists():
    shutil.copy2(config, backup)
    backup.chmod(0o600)
settings = {'CHAT_AV_BINARY': scanner, 'CHAT_AV_QUEUE': str(queue), 'CHAT_REQUIRE_SCAN': 'true'}
lines = [line for line in config.read_text().splitlines() if line.split('=', 1)[0] not in settings]
lines += [key + '=' + value for key, value in settings.items()]
fd, temporary = tempfile.mkstemp(prefix='.env-antivirus-', dir=app)
try:
    with os.fdopen(fd, 'w') as f:
        f.write('\n'.join(lines) + '\n')
    os.replace(temporary, config)
finally:
    Path(temporary).unlink(missing_ok=True)
print('Sain accepte, EICAR rejete. Antivirus prive active pour le chat.')
REMOTE_PY
printf '%s\n' 'Laissez ce terminal et cet ordinateur actifs pendant la demonstration.' \
    'Ctrl+C arrete le worker ; les nouvelles pieces jointes seront alors bloquees.'
wait "$worker_pid"
