"""Private job lifecycle tests, without SSH, production data or third-party uploads."""
import json
import os
from pathlib import Path
import subprocess
import tempfile
import time

CLIENT = Path(__file__).resolve().parents[1] / 'bin/scan-chat-queue.py'
checks = 0
with tempfile.TemporaryDirectory(prefix='expernet-queue-') as folder:
    root = Path(folder)
    env = dict(os.environ, CHAT_AV_QUEUE=str(root / 'jobs'))
    source = root / 'attachment.txt'
    source.write_bytes(b'Document sain.\nBinary: \x00\xff')

    def call(*args):
        return subprocess.run(['python3', str(CLIENT), *args], env=env, capture_output=True, timeout=3)

    assert call('--no-summary', str(source)).returncode == 2
    checks += 1
    for verdict in (0, 1, 2):
        assert call('--jobs').returncode == 0
        request = subprocess.Popen(['python3', str(CLIENT), '--no-summary', str(source)], env=env, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        try:
            identifiers = []
            deadline = time.monotonic() + 3
            while time.monotonic() < deadline and not identifiers:
                identifiers = json.loads(call('--jobs').stdout)
                if not identifiers:
                    time.sleep(0.02)
            assert len(identifiers) == 1
            identifier = identifiers[0]
            assert call('--download', identifier).stdout == source.read_bytes()
            assert call('--result', identifier, str(verdict)).returncode == 0
            assert request.wait(timeout=3) == verdict
            assert not (root / 'jobs' / identifier).exists()
            checks += 1
        finally:
            if request.poll() is None:
                request.kill()
                request.wait()
    heartbeat = root / 'jobs/heartbeat'
    os.utime(heartbeat, (time.time() - 11, time.time() - 11))
    assert call('--no-summary', str(source)).returncode == 2
    checks += 1
    assert call('--download', '../../.env').returncode == 2
    assert call('--result', 'nonexistent', '0').returncode == 2
    checks += 2
    assert call('--jobs').returncode == 0
    for size in (0, 10 * 1024 * 1024 + 1):
        with source.open('wb') as output:
            output.truncate(size)
        assert call('--no-summary', str(source)).returncode == 2
        checks += 1
print(f'{checks} contrôles de file antivirus : transfert exact, verdicts, nettoyage, worker absent et limites vérifiés.')
