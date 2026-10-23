"""Tests the actual CLI and INSTREAM framing with a fake local daemon; no network upload."""
import os
from pathlib import Path
import socket
import struct
import subprocess
import tempfile
import threading
import time

CLIENT = Path(__file__).resolve().parents[1] / 'bin/scan-chat-stream.py'
checks = 0


def receive(connection, length):
    data = b''
    while len(data) < length:
        chunk = connection.recv(length - len(data))
        if not chunk:
            raise RuntimeError('Truncated client stream')
        data += chunk
    return data


def exercise(reply, expected, *, ping=False, delay=0):
    global checks
    with tempfile.TemporaryDirectory(prefix='av-', dir='/tmp') as folder:
        endpoint = folder + '/clamd.sock'
        content = b'Harmless demonstration attachment.\n' * 3000
        file = Path(folder) / 'document.txt'
        file.write_bytes(content)
        errors = []
        with socket.socket(socket.AF_UNIX) as server:
            server.bind(endpoint)
            server.listen(1)
            server.settimeout(3)

            def daemon():
                try:
                    with server.accept()[0] as connection:
                        connection.settimeout(3)
                        if ping:
                            assert receive(connection, 6) == b'zPING\0'
                        else:
                            assert receive(connection, 10) == b'zINSTREAM\0'
                            result = bytearray()
                            while size := struct.unpack('!I', receive(connection, 4))[0]:
                                result.extend(receive(connection, size))
                            assert result == content
                        time.sleep(delay)
                        if reply:
                            try:
                                connection.sendall(reply)
                            except BrokenPipeError:
                                assert delay
                except Exception as error:
                    errors.append(error)

            worker = threading.Thread(target=daemon)
            worker.start()
            env = dict(os.environ, CHAT_AV_SOCKET=endpoint, CHAT_AV_TIMEOUT='0.2' if delay else '2')
            args = ['--ping'] if ping else ['--no-summary', str(file)]
            result = subprocess.run(['python3', str(CLIENT), *args], env=env, capture_output=True, timeout=4)
            worker.join(timeout=4)
            assert not worker.is_alive()
            assert not errors, errors
            assert result.returncode == expected, (reply, result.returncode, result.stderr)
            checks += 1


exercise(b'stream: OK\0', 0)
exercise(b'stream: Eicar-Test-Signature FOUND\0', 1)
exercise(b'stream: INSTREAM size limit exceeded. ERROR\0', 2)
exercise(b'OK\0', 2)
exercise(b'stream: OK\0garbage', 2)
exercise(b'', 2)
exercise(b'x' * 4096, 2)
exercise(b'stream: OK\0', 2, delay=0.5)
exercise(b'PONG\0', 0, ping=True)
exercise(b'UNKNOWN COMMAND\0', 2, ping=True)
with tempfile.TemporaryDirectory(prefix='av-', dir='/tmp') as folder:
    env = dict(os.environ, CHAT_AV_SOCKET=folder + '/absent.sock')
    result = subprocess.run(['python3', str(CLIENT), '--ping'], env=env, capture_output=True, timeout=2)
    assert result.returncode == 2
    checks += 1
# Invalid local inputs must never send contents, even to a daemon saying OK.
for size in (0, 10 * 1024 * 1024 + 1, None):
    with tempfile.TemporaryDirectory(prefix='av-', dir='/tmp') as folder:
        file = Path(folder) / 'input.txt'
        if size is not None:
            with file.open('wb') as output:
                output.truncate(size)
        endpoint = folder + '/clamd.sock'
        with socket.socket(socket.AF_UNIX) as server:
            server.bind(endpoint)
            server.listen(1)
            server.settimeout(2)
            env = dict(os.environ, CHAT_AV_SOCKET=endpoint)
            result = subprocess.run(['python3', str(CLIENT), '--no-summary', str(file)], env=env, capture_output=True, timeout=2)
            with server.accept()[0] as connection:
                connection.settimeout(2)
                assert connection.recv(1) == b''
            assert result.returncode == 2
            checks += 1
print(f'{checks} contrôles antivirus : flux exact, verdicts, erreurs, délai et tunnel absent vérifiés.')
