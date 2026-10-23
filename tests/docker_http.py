"""Read-only application checks against Docker; never uses the host MySQL connection."""
import http.cookiejar
import json
import os
import re
import urllib.error
import urllib.parse
import urllib.request

BASE = os.environ.get('TEST_DOCKER_URL', 'http://127.0.0.1:8080').rstrip('/')
checks = 0

class Client:
    def __init__(self, email=None):
        self.http = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        if email:
            page = self.get('/connexion/')
            token = re.search(r'name="csrf" value="([^"]+)"', page)[1]
            payload = urllib.parse.urlencode({'csrf': token, 'email': email, 'password': os.environ.get('TEST_DOCKER_PASSWORD', 'Test-local-phrase-2026!')}).encode()
            response = self.http.open(BASE + '/connexion/', payload, timeout=15)
            assert response.url.endswith('/espace-pedagogique/'), response.url
            assert 'data-csrf=' in response.read().decode()

    def get(self, path, expected=200):
        global checks
        try:
            response = self.http.open(BASE + path, timeout=15)
        except urllib.error.HTTPError as error:
            response = error
        body = response.read().decode()
        assert response.status == expected, (path, response.status, body[:150])
        checks += 1
        return body

    def api(self, path, expected=200):
        return json.loads(self.get(path, expected))

anonymous = Client()
anonymous.api('/api/pedagogie/etat/', 401)
anonymous.api('/api/discussions/inbox/', 401)
for role in ['student', 'trainer', 'admin']:
    client = Client(role + '.demo@expernet.re')
    paths = ['', 'planning/', 'ressources/', 'travaux/', 'resultats/', 'presences/', 'messages/', 'parcours/', 'examens/']
    if role != 'student':
        paths += ['classes/']
    if role == 'admin':
        paths += ['administration/']
    for path in paths:
        page = client.get('/espace-pedagogique/' + path)
        assert page.count('<h1') == 1 and 'Fatal error' not in page
    state = client.api('/api/pedagogie/etat/')['data']
    assert state['sessions'], 'Demo timetable missing'
    for session in state['sessions']:
        client.get('/espace-pedagogique/seance/?id=' + str(session['id']))
    for resource in state['resources']:
        assert client.get('/espace-pedagogique/document/?id=' + str(resource['id'])), 'Empty document'
    assert 'BEGIN:VCALENDAR' in client.get('/espace-pedagogique/calendrier/')
    for view in ['day', 'week']:
        page = client.get('/espace-pedagogique/planning/?date=2026-10-23&view=' + view)
        assert 'planning-calendar' in page
    assert client.api('/api/discussions/inbox/')['data']['cohorts'] or role == 'admin'

lina = Client('chat01.demo@expernet.re')
noe = Client('chat02.demo@expernet.re')
nora = Client('chat09.demo@expernet.re')
conversations = lina.api('/api/discussions/inbox/')['data']['conversations']
private = next(c for c in conversations if c['kind'] == 'direct' and 'Noé' in c['title'])
conversation = str(private['id'])
lina.api('/api/discussions/history/?conversation_id=' + conversation)
noe.api('/api/discussions/history/?conversation_id=' + conversation)
nora.api('/api/discussions/history/?conversation_id=' + conversation, 403)
print(f'OK : {checks} requêtes HTTP, connexions de 6 comptes, état MySQL, pages pédagogiques, calendrier, documents et confidentialité. Aucun message ni donnée métier ajouté.')
