#!/usr/bin/env python3
"""Private SSH scan jobs. ChatService compatibility: 0 clean, 1 detected, 2 error."""
import json
import os
from pathlib import Path
import re
import shutil
import sys
import tempfile
import time

MAX_BYTES = 10 * 1024 * 1024
ROOT = Path(os.environ.get('CHAT_AV_QUEUE') or Path(__file__).resolve().parents[1] / 'storage/antivirus/jobs')


def job_path(identifier):
    if not re.fullmatch(r'[a-z0-9_]{8,40}', identifier):
        raise ValueError('Invalid job')
    return ROOT / identifier


def jobs():
    ROOT.mkdir(mode=0o700, parents=True, exist_ok=True)
    if ROOT.stat().st_mode & 0o077:
        raise ValueError('Queue must be private')
    heartbeat = ROOT / 'heartbeat'
    heartbeat.touch(mode=0o600, exist_ok=True)
    found = []
    for folder in ROOT.iterdir():
        if not folder.is_dir() or folder.is_symlink():
            continue
        try:
            deadline = float((folder / 'deadline').read_text())
            if deadline < time.time():
                shutil.rmtree(folder)
            elif not (folder / 'verdict').exists():
                found.append(folder.name)
        except FileNotFoundError:
            pass
    print(json.dumps(found[:20]))


def scan(filename):
    # Fail immediately when no private worker has checked in recently.
    if time.time() - (ROOT / 'heartbeat').stat().st_mtime > 10:
        return 2
    with open(filename, 'rb') as source:
        if not 0 < os.fstat(source.fileno()).st_size <= MAX_BYTES:
            return 2
        with tempfile.TemporaryDirectory(prefix='scan_', dir=ROOT) as folder:
            folder = Path(folder)
            with (folder / 'input.bin').open('xb') as target:
                os.chmod(target.name, 0o600)
                shutil.copyfileobj(source, target, length=65536)
            deadline = time.time() + 25
            (folder / 'deadline').write_text(str(deadline))
            while time.time() < deadline:
                try:
                    verdict = (folder / 'verdict').read_text()
                    return int(verdict) if verdict in ('0', '1', '2') else 2
                except FileNotFoundError:
                    time.sleep(0.1)
    return 2


def main():
    args = sys.argv[1:]
    if args == ['--jobs']:
        jobs()
        return 0
    if len(args) == 2 and args[0] == '--download':
        folder = job_path(args[1])
        if float((folder / 'deadline').read_text()) < time.time():
            return 2
        source = folder / 'input.bin'
        if not 0 < source.stat().st_size <= MAX_BYTES:
            return 2
        with source.open('rb') as content:
            shutil.copyfileobj(content, sys.stdout.buffer, length=65536)
        return 0
    if len(args) == 3 and args[0] == '--result' and args[2] in ('0', '1', '2'):
        folder = job_path(args[1])
        if float((folder / 'deadline').read_text()) < time.time():
            return 2
        temporary = folder / 'verdict.tmp'
        temporary.write_text(args[2])
        temporary.chmod(0o600)
        os.replace(temporary, folder / 'verdict')
        return 0
    if len(args) == 2 and args[0] == '--no-summary':
        return scan(args[1])
    return 2


if __name__ == '__main__':
    try:
        sys.exit(main())
    except (OSError, ValueError):
        print('Analyse antivirus indisponible.', file=sys.stderr)
        sys.exit(2)
