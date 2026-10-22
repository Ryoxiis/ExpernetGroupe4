"""HTTP + WebSocket checks against a dedicated fictitious QA fixture only."""
import base64
import http.cookiejar
import json
import os
import re
import socket
import struct
import urllib.error
import urllib.parse
import urllib.request
import io
import zipfile
from pathlib import Path

BASE = os.environ.get('TEST_CHAT_URL', 'http://127.0.0.1:8095')
fixture = json.loads(Path('/tmp/expernet-chat-fixture.json').read_text())
checks = 0

def check(ok, label):
    global checks
    assert ok, label
    checks += 1

class Client:
    def __init__(self, index=None):
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        self.csrf = ''
        if index is not None:
            page = self.opener.open(BASE + '/connexion/').read().decode()
            csrf = re.search(r'name="csrf" value="([^"]+)"', page)[1]
            data = urllib.parse.urlencode({'csrf': csrf, 'email': fixture['users'][index]['email'], 'password': fixture['password']}).encode()
            page = self.opener.open(BASE + '/connexion/', data=data).read().decode()
            self.csrf = re.search(r'data-csrf="([^"]+)"', page)[1]
    def api(self, action, data=None, method='POST', csrf=True):
        headers = {'X-CSRF-Token': self.csrf} if csrf else {}
        if method == 'GET':
            url = BASE + '/api/discussions/' + action + '/?' + urllib.parse.urlencode(data or {})
            body = None
        else:
            url = BASE + '/api/discussions/' + action + '/'
            body = json.dumps(data or {}).encode()
            headers['Content-Type'] = 'application/json'
        return self.request(urllib.request.Request(url, data=body, headers=headers, method=method))
    def request(self, req):
        try:
            res = self.opener.open(req)
            return res.status, res.headers, res.read()
        except urllib.error.HTTPError as e:
            return e.code, e.headers, e.read()
    def upload(self, conversation, filename='qa.txt', content=b'Fictitious upload', key=None):
        boundary = 'CHATTESTBOUNDARY'
        parts = []
        for name, val in {'conversation_id': conversation, 'content': '', 'client_key': key or 'http-test-' + os.urandom(12).hex()}.items():
            parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{name}"\r\n\r\n{val}\r\n'.encode())
        parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="file"; filename="{filename}"\r\nContent-Type: application/octet-stream\r\n\r\n'.encode() + content + b'\r\n')
        parts.append(f'--{boundary}--\r\n'.encode())
        return self.request(urllib.request.Request(BASE + '/api/discussions/send/', data=b''.join(parts), headers={'Content-Type': 'multipart/form-data; boundary=' + boundary, 'X-CSRF-Token': self.csrf}))

a, b, outsider, admin = Client(1), Client(2), Client(4), Client(0)
check(Client().api('inbox', {}, 'GET')[0] == 401, 'Anonymous API denied')
check(a.api('send', {}, csrf=False)[0] == 419, 'CSRF denied')
check(outsider.api('history', {'conversation_id': fixture['private']}, 'GET')[0] == 403, 'IDOR denied')
check(a.api('permissions', {'cohort_id': fixture['cohort']})[0] == 403, 'Permission escalation denied')
check(a.api('inbox', {}, 'DELETE')[0] == 405, 'Unsupported method')
check(a.api('send', {'conversation_id': fixture['private'], 'content': '', 'client_key': 'empty-' + os.urandom(12).hex()})[0] == 422, 'Empty message denied')
res = a.upload(fixture['channel'])
check(res[0] == 200, 'Private file upload')
mid = json.loads(res[2])['data']['id']
history = json.loads(a.api('history', {'conversation_id': fixture['channel']}, 'GET')[2])['data']['messages']
f = next(m for m in history if int(m['id']) == int(mid))
check(b.api('file', {'id': f['file_id']}, 'GET')[2] == b'Fictitious upload', 'Member file download')
check(outsider.api('file', {'id': f['file_id']}, 'GET')[0] == 403, 'File IDOR denied')
# PHP disguised as image must not pass MIME inspection.
check(a.upload(fixture['channel'], 'fake.jpg', b'<?php system("id"); ?>')[0] == 422, 'Fake image denied')
archive = io.BytesIO()
with zipfile.ZipFile(archive, 'w') as zipped:
    zipped.writestr('[Content_Types].xml', '<Types/>')
    zipped.writestr('word/document.xml', '<document/>')
check(a.upload(fixture['channel'], 'qa.docx', archive.getvalue())[0] == 200, 'Office package accepted')
with zipfile.ZipFile(archive, 'a') as zipped:
    zipped.writestr('word/vbaProject.bin', 'fake macro')
check(a.upload(fixture['channel'], 'macro.docx', archive.getvalue())[0] == 422, 'Office macros rejected')
policy = {'cohort_id': fixture['cohort'], 'enabled': 1, 'direct_enabled': 1, 'files_enabled': 0, 'meetings_enabled': 1}
check(admin.api('permissions', policy)[0] == 200, 'Admin policy write')
check(a.upload(fixture['channel'])[0] == 403, 'Upload disabled by policy')
check(b.api('file', {'id': f['file_id']}, 'GET')[0] == 403, 'Download disabled by policy')
policy['files_enabled'] = 1
admin.api('permissions', policy)
check(a.api('join', {'meeting_id': 999999999})[0] == 404, 'Unknown meeting denied')
# WebSocket implementation uses stdlib to check real wire-level handshake/auth.
def frame(data):
    payload = json.dumps(data).encode()
    mask = os.urandom(4)
    assert len(payload) < 126
    return bytes([0x81, 0x80 | len(payload)]) + mask + bytes(b ^ mask[i % 4] for i, b in enumerate(payload))
def receive(sock):
    head = sock.recv(2)
    if len(head) < 2: return None
    if head[0] & 15 == 8: return None
    length = head[1] & 127
    if length == 126: length = struct.unpack('!H', sock.recv(2))[0]
    data = b''
    while len(data) < length: data += sock.recv(length-len(data))
    return json.loads(data)
def connect(origin=BASE):
    sock = socket.create_connection(('127.0.0.1', 8094), timeout=5)
    sock.settimeout(5)
    key = base64.b64encode(os.urandom(16)).decode()
    sock.sendall(f'GET / HTTP/1.1\r\nHost: 127.0.0.1:8094\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Key: {key}\r\nSec-WebSocket-Version: 13\r\nOrigin: {origin}\r\n\r\n'.encode())
    headers = b''
    while b'\r\n\r\n' not in headers: headers += sock.recv(1)
    return sock

token = json.loads(a.api('ticket', {})[2])['data']['token']
sock = connect();sock.sendall(frame({'type': 'auth', 'token': token}))
check(receive(sock)['event'] == 'ready', 'WebSocket authentication')
replay = connect();replay.sendall(frame({'type': 'auth', 'token': token}))
check(receive(replay) is None, 'WebSocket ticket single use');replay.close()
send = a.api('send', {'conversation_id': fixture['channel'], 'content': 'Live QA message', 'client_key': 'live-' + os.urandom(12).hex()})
check(send[0] == 200, 'Send while WS connected')
check(receive(sock)['event'] == 'refresh', 'WebSocket live invalidation');sock.close()
bad = connect('https://untrusted.example.test')
check(receive(bad) is None, 'Untrusted WebSocket origin denied');bad.close()
print(f'{checks} HTTP/WebSocket collaboration checks passed.')
