"""Integration checks against a running PHP server, without external network calls."""
import json
import os
import urllib.error
import urllib.parse
import urllib.request
from html.parser import HTMLParser
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
BASE = os.environ.get('TEST_BASE_URL', 'http://127.0.0.1:8080').rstrip('/')
ROUTES = json.loads((ROOT / 'config/routes.json').read_text())
failures = []

class Links(HTMLParser):
    def handle_starttag(self, tag, attrs):
        for name, value in attrs:
            if name not in ('href', 'src') or not value or not value.startswith('/'):
                continue
            path = urllib.parse.unquote(urllib.parse.urlsplit(value).path)
            if path.startswith('/assets/'):
                if not (ROOT / 'public' / path.lstrip('/')).is_file():
                    failures.append(f'Ressource absente : {path}')
            elif path in ('/espace-pedagogique/', '/connexion/'):
                pass
            elif ('/' + path.strip('/') + '/' if path.strip('/') else '/') not in ROUTES:
                failures.append(f'Route absente : {path}')

for route in ROUTES:
    with urllib.request.urlopen(BASE + urllib.parse.quote(route), timeout=10) as response:
        assert response.status == 200, route
        Links().feed(response.read().decode())

for endpoint, count in [('formations', 149), ('sessions', 58), ('palettes', 4)]:
    with urllib.request.urlopen(f'{BASE}/api/{endpoint}/') as response:
        assert response.headers.get_content_type() == 'application/json'
        assert len(json.load(response)) == count

for route, code in [('/page-inconnue/', 404), ('/data/content.json', 404), ('/.env', 404), ('/config/app.php', 404), ('/app/Views/layouts/main.php', 404)]:
    try:
        urllib.request.urlopen(BASE + route)
        failures.append(f'Une ressource interne est exposée : {route}')
    except urllib.error.HTTPError as error:
        assert error.code == code, (route, error.code)

request = urllib.request.Request(BASE + '/demande-de-devis/', data=b'nom=test', method='POST')
try:
    urllib.request.urlopen(request)
    failures.append('Le prototype a accepté une écriture POST.')
except urllib.error.HTTPError as error:
    assert error.code == 405

assert not failures, '\n'.join(sorted(set(failures)))
print(f'HTTP : {len(ROUTES)} pages, 3 API, ressources/liens, 404 et refus des écritures vérifiés.')
