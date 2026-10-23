"""Real two-session exchange against the local test accounts; remove only our test messages."""
import http.cookiejar
import json
import os
import re
import subprocess
import urllib.error
import urllib.parse
import urllib.request
import uuid
from pathlib import Path

BASE = os.environ.get('TEST_CHAT_URL', 'http://expernet.local').rstrip('/')
RUN = 'check-chat-' + uuid.uuid4().hex
ROOT = Path(__file__).resolve().parents[1]

class Client:
    def __init__(self, number):
        self.http = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        page = self.http.open(BASE + '/connexion/').read().decode()
        csrf = re.search(r'name="csrf" value="([^"]+)"', page)[1]
        data = urllib.parse.urlencode({'csrf': csrf, 'email': f'chat{number:02}.demo@expernet.re', 'password': 'Test-local-phrase-2026!'}).encode()
        page = self.http.open(BASE + '/connexion/', data).read().decode()
        self.csrf = re.search(r'data-csrf="([^"]+)"', page)[1]

    def api(self, action, data=None, get=False, csrf=True):
        url = BASE + '/api/discussions/' + action + '/'
        headers = {'X-CSRF-Token': self.csrf} if csrf else {}
        body = None
        if get:
            url += '?' + urllib.parse.urlencode(data or {})
        else:
            headers['Content-Type'] = 'application/json'
            body = json.dumps(data or {}).encode()
        req = urllib.request.Request(url, body, headers)
        try:
            response = self.http.open(req)
            return response.status, json.load(response)
        except urllib.error.HTTPError as error:
            return error.code, json.load(error)

try:
    a, b, outsider, admin = Client(1), Client(2), Client(9), Client(16)
    status, inbox = a.api('inbox', get=True)
    assert status == 200
    cohort = next(c['cohort_id'] for c in inbox['data']['conversations'] if c['kind'] == 'cohort')
    # Open via the real endpoint, as the UI does after selecting a contact.
    status, contacts = a.api('contacts', {'cohort_id': cohort, 'q': 'Noé'}, get=True)
    assert status == 200
    target = next(c['id'] for c in contacts['data']['contacts'] if c['name'] == 'Noé Démo')
    status, opened = a.api('open', {'cohort_id': cohort, 'target_user_id': target})
    assert status == 200, opened
    conversation = opened['data']['id']
    for sender, receiver, suffix, content in [(a, b, 'a', 'Bonjour, test réel entre deux comptes.'), (b, a, 'b', 'Bien reçu, voici ma réponse.')]:
        payload = {'conversation_id': conversation, 'content': content, 'client_key': RUN + suffix}
        status, sent = sender.api('send', payload)
        assert status == 200, sent
        status, history = receiver.api('history', {'conversation_id': conversation}, get=True)
        assert status == 200
        assert any(int(m['id']) == int(sent['data']['id']) and m['content'] == content for m in history['data']['messages'])
        assert sender.api('send', payload)[1]['data']['id'] == sent['data']['id'], 'Duplicate message'
    assert outsider.api('history', {'conversation_id': conversation}, get=True)[0] == 403
    assert admin.api('history', {'conversation_id': conversation}, get=True)[0] == 403
    denied = a.api('send', {'conversation_id': conversation}, csrf=False)
    assert denied[0] in (403, 419, 500) and denied[1].get('error') is True and 'formulaire a expiré' in denied[1].get('message', ''), denied
    # Apache may map the application's nonstandard 419 to 500. Verify refusal and no insertion.
    assert len(a.api('history', {'conversation_id': conversation}, get=True)[1]['data']['messages']) == len(history['data']['messages'])
    print('OK : connexion de 4 comptes, recherche de contact, envoi et réponse réels, persistance, antidoublon, confidentialité et CSRF.')
finally:
    # Never remove user messages or conversations. Match only this invocation's random keys.
    cleanup = '''require 'bootstrap/app.php';
    $config = require 'config/pedagogy.php';
    if (!$config['demo'] || getenv('APP_ENV') === 'production') exit(1);
    $db = App\\Support\\Database::connect(require 'config/database.php');
    $s = $db->prepare('DELETE FROM portal_chat_messages WHERE client_key IN (?,?)');
    $s->execute([$argv[1].'a',$argv[1].'b']);'''
    subprocess.run(['php', '-r', cleanup, RUN], cwd=ROOT, check=True)
