"""Repeatable, read-only HTTP regression of an already activated demo portal.
Run only on non-production fixtures. No passwords or domain data are modified.
"""
import http.cookiejar
import json
import os
import re
import urllib.error
import urllib.parse
import urllib.request

BASE = os.environ.get('TEST_PORTAL_URL', 'http://127.0.0.1:8093').rstrip('/')
PASSWORD = os.environ.get('TEST_PORTAL_PASSWORD', 'Test-local-phrase-2026!')
checks = 0

def request(opener, path, data=None, expected=200):
    global checks
    data = urllib.parse.urlencode(data).encode() if data is not None else None
    try:
        response = opener.open(urllib.request.Request(BASE + path, data=data), timeout=10)
    except urllib.error.HTTPError as error:
        response = error
    text = response.read().decode()
    assert response.status == expected, (path, response.status, text[:250])
    checks += 1
    return text, response

common = ['', 'planning/', 'ressources/', 'travaux/', 'resultats/', 'presences/', 'messages/', 'parcours/', 'examens/', 'attestation/']
for role in ('student', 'trainer', 'admin'):
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    html, _ = request(opener, '/connexion/')
    token = re.search(r'name="csrf" value="([a-f0-9]+)"', html).group(1)
    html, response = request(opener, '/connexion/', {'csrf': token, 'email': role + '.demo@expernet.re', 'password': PASSWORD})
    assert response.url.endswith('/espace-pedagogique/'), (role, response.url)
    pages = common + (['classes/'] if role != 'student' else []) + (['administration/'] if role == 'admin' else [])
    for page in pages:
        html, _ = request(opener, '/espace-pedagogique/' + page)
        assert html.count('<h1') == 1, page
        assert 'Fatal error' not in html and 'Warning:' not in html
    data, _ = request(opener, '/api/pedagogie/etat/')
    state = json.loads(data)
    for session in state['data']['sessions']:
        request(opener, '/espace-pedagogique/seance/?id=' + str(session['id']))
    for document in state['data']['resources']:
        request(opener, '/espace-pedagogique/document/?id=' + str(document['id']))
    request(opener, '/espace-pedagogique/calendrier/')
    request(opener, '/api/pedagogie/messages/', {'body': 'CSRF refusé sans écriture'}, 419)
    html, _ = request(opener, '/espace-pedagogique/')
    token = re.search(r'name="csrf" value="([a-f0-9]+)"', html).group(1)
    request(opener, '/connexion/deconnexion/', {'csrf': token})
    request(opener, '/api/pedagogie/etat/', expected=401)
print(f'{checks} contrôles HTTP de régression réussis pour étudiant, formateur et administrateur.')
