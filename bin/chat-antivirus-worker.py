#!/usr/bin/env python3
"""Scan private Alwaysdata jobs locally using ordinary SSH transfers, no forwarding."""
import argparse
import json
import os
from pathlib import Path
import shlex
import subprocess
import tempfile
import time

parser = argparse.ArgumentParser()
parser.add_argument('--control', required=True)
parser.add_argument('--known-hosts', required=True)
args = parser.parse_args()
remote_helper = '/home/expernet/expernet-app/bin/scan-chat-queue.py'
client = Path(__file__).with_name('scan-chat-stream.py')
env = dict(os.environ, CHAT_AV_SOCKET='/run/clamav/clamd.ctl')


def remote(*arguments):
    return subprocess.run([
        'ssh', '-S', args.control, '-o', 'BatchMode=yes', '-o', 'StrictHostKeyChecking=yes',
        '-o', 'UserKnownHostsFile=' + args.known_hosts,
        'expernet@ssh-expernet.alwaysdata.net',
        shlex.join(['python3', remote_helper, *arguments]),
    ], capture_output=True, timeout=30)


try:
    while True:
        listing = remote('--jobs')
        if listing.returncode != 0:
            raise RuntimeError('Lecture de la file SSH impossible.')
        identifiers = json.loads(listing.stdout)
        if not isinstance(identifiers, list):
            raise RuntimeError('Réponse de file invalide.')
        for identifier in identifiers:
            downloaded = remote('--download', identifier)
            if downloaded.returncode != 0:
                continue  # Expired request; it must not become a clean verdict.
            if not 0 < len(downloaded.stdout) <= 10 * 1024 * 1024:
                verdict = 2
            else:
                with tempfile.TemporaryDirectory(prefix='expernet-scan-') as folder:
                    file = Path(folder) / 'input.bin'
                    file.write_bytes(downloaded.stdout)
                    file.chmod(0o600)
                    try:
                        verdict = subprocess.run(['python3', str(client), '--no-summary', str(file)], env=env, timeout=22).returncode
                    except subprocess.TimeoutExpired:
                        verdict = 2
            verdict = verdict if verdict in (0, 1) else 2
            remote('--result', identifier, str(verdict))
        time.sleep(1)
except KeyboardInterrupt:
    pass
except (OSError, ValueError, RuntimeError, subprocess.TimeoutExpired) as error:
    print(str(error))
    raise SystemExit(1)
