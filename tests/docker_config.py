"""Static checks only; does not replace docker compose config or a real container run."""
from pathlib import Path
import os
import shutil
import stat
import subprocess
import tempfile
import yaml

ROOT = Path(__file__).resolve().parents[1]
compose = yaml.safe_load((ROOT / 'compose.yaml').read_text())
services = compose['services']
assert 'profiles' not in services['database'], 'MySQL must start by default'
assert not services['database'].get('ports'), 'Do not publish MySQL'
assert services['app']['depends_on']['database']['condition'] == 'service_healthy'
assert services['chat']['depends_on']['app']['condition'] == 'service_healthy'
assert services['app']['environment']['DB_HOST'] == 'database'
assert services['app']['environment']['DB_USERNAME'] != 'root'
assert services['app']['environment']['PORTAL_STORAGE'] == '/var/www/html/storage/pedagogy'
assert 'portal_storage:/var/www/html/storage' in services['app']['volumes']
assert 'database_data:/var/lib/mysql' in services['database']['volumes']
assert './database.sql:/docker-entrypoint-initdb.d/001-expernet.sql:ro' in services['database']['volumes']
for service in services.values():
    for port in service.get('ports', []):
        assert port.startswith('127.0.0.1:'), 'Only expose local ports'
    assert not service.get('privileged'), 'No privileged containers'
    for volume in service.get('volumes', []):
        assert 'docker.sock' not in volume and not volume.startswith('.:/'), 'Do not mount secrets or the Docker socket'
ignore = (ROOT / '.dockerignore').read_text().splitlines()
for item in ['.env', '.env.*', '.git', '.ai', 'storage', 'vendor']:
    assert item in ignore
apache = (ROOT / 'docker/php/apache.conf').read_text()
assert 'DocumentRoot /var/www/html/public' in apache
assert 'Options -Indexes' in apache
assert 'COPY . .' in (ROOT / 'docker/php/Dockerfile').read_text()
with tempfile.TemporaryDirectory(prefix='expernet-docker-check-') as directory:
    root = Path(directory)
    (root / 'bin').mkdir()
    shutil.copy(ROOT / 'bin/setup-docker.php', root / 'bin/setup-docker.php')
    shutil.copy(ROOT / '.env.docker.example', root / '.env.docker.example')
    (root / '.env').write_text('LOCAL_SENTINEL=keep-this\n')
    command = ['php', str(root / 'bin/setup-docker.php')]
    subprocess.run(command, check=True, capture_output=True)
    generated = root / '.env.docker'
    original = generated.read_bytes()
    values = dict(line.split('=', 1) for line in original.decode().splitlines() if '=' in line and not line.startswith('#'))
    secrets = [values[key] for key in ['DOCKER_DB_PASSWORD', 'DOCKER_ROOT_PASSWORD', 'APP_KEY']]
    assert len(set(secrets)) == 3
    assert all(len(value) == 64 and all(c in '0123456789abcdef' for c in value) for value in secrets)
    if os.name == 'posix':
        assert stat.S_IMODE(generated.stat().st_mode) == 0o600
    subprocess.run(command, check=True, capture_output=True)
    assert generated.read_bytes() == original, 'Never replace existing secrets'
    assert (root / '.env').read_text() == 'LOCAL_SENTINEL=keep-this\n'
print('OK : structure Compose, réseau local, volumes, exclusions et génération des secrets sans modification de LAMP. Aucun conteneur exécuté.')
