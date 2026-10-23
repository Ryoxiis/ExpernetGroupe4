#!/usr/bin/env python3
"""ClamAV INSTREAM client over a private Unix socket (optionally forwarded by SSH).

Compatible with ChatService's --no-summary FILE invocation: 0 clean, 1 infected,
2 unavailable/error. No local or remote file is accepted without an explicit OK.
"""
import os
from pathlib import Path
import socket
import struct
import sys
import time

MAX_BYTES = 10 * 1024 * 1024


def scan(path=None):
    endpoint = os.environ.get('CHAT_AV_SOCKET') or str(
        Path(__file__).resolve().parents[1] / 'storage/antivirus/clamd.sock'
    )
    timeout = min(30.0, float(os.environ.get('CHAT_AV_TIMEOUT', '20')))
    if timeout <= 0:
        return 2
    deadline = time.monotonic() + timeout

    with socket.socket(socket.AF_UNIX, socket.SOCK_STREAM) as connection:
        def remaining():
            seconds = deadline - time.monotonic()
            if seconds <= 0:
                raise TimeoutError('scan deadline exceeded')
            connection.settimeout(seconds)

        def send(data):
            remaining()
            connection.sendall(data)

        remaining()
        connection.connect(endpoint)
        if path is None:
            send(b'zPING\0')
        else:
            with open(path, 'rb') as content:
                if not 0 < os.fstat(content.fileno()).st_size <= MAX_BYTES:
                    return 2
                send(b'zINSTREAM\0')
                total = 0
                while chunk := content.read(65536):
                    total += len(chunk)
                    if total > MAX_BYTES:
                        return 2
                    send(struct.pack('!I', len(chunk)) + chunk)
                send(b'\0\0\0\0')

        reply = bytearray()
        while len(reply) < 4096:
            remaining()
            chunk = connection.recv(min(1024, 4096 - len(reply)))
            if not chunk:
                return 2
            reply.extend(chunk)
            if b'\0' in reply:
                verdict, trailing = reply.split(b'\0', 1)
                if trailing:
                    return 2
                if path is None:
                    return 0 if verdict == b'PONG' else 2
                if verdict == b'stream: OK':
                    return 0
                if verdict.startswith(b'stream: ') and verdict.endswith(b' FOUND'):
                    return 1
                return 2
    return 2


def main():
    args = sys.argv[1:]
    if args == ['--ping']:
        return scan()
    if len(args) == 2 and args[0] == '--no-summary':
        return scan(args[1])
    if len(args) == 1 and not args[0].startswith('-'):
        return scan(args[0])
    return 2


if __name__ == '__main__':
    try:
        sys.exit(main())
    except (OSError, ValueError, TimeoutError):
        print('Analyse antivirus indisponible.', file=sys.stderr)
        sys.exit(2)
