"""Apache/PHP/MySQL checks without changing existing users or business data."""
import http.cookiejar
import os
import re
import urllib.error
import urllib.parse
import urllib.request

base = os.environ.get('TEST_BASE_URL', 'http://127.0.0.1:8088').rstrip('/')
client = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
checks = 0

def get(path, data=None, expected=(200,)):
    global checks
    request = urllib.request.Request(base + path, data=data)
    try:
        response = client.open(request, timeout=10)
    except urllib.error.HTTPError as error:
        response = error
    text = response.read().decode()
    assert response.status in expected, (path, response.status)
    assert 'Apache' in response.headers.get('Server', ''), 'A real Apache server is required'
    assert '<?php' not in text, 'PHP source must never be served as text'
    checks += 1
    return text

for path in ['/', '/campus/', '/formation/', '/connexion/', '/connexion/aide/']:
    get(path)
for path in ['/.env', '/.git/config', '/bootstrap/environment.php', '/config/database.php', '/storage/chat/']:
    get(path, expected=(403, 404))
get('/api/pedagogie/etat/', expected=(401,))
html = get('/connexion/')
csrf = re.search(r'name="csrf" value="([a-f0-9]+)"', html).group(1)
# An unknown fixture account must return an authentication error, never a database outage.
body = urllib.parse.urlencode({'csrf': csrf, 'email': 'lamp-test-inexistant@example.invalid', 'password': 'invalid-test-password'}).encode()
get('/connexion/', body, (401,))
print(f'{checks} contrôles LAMP réussis : Apache, PHP exécuté, MySQL accessible et fichiers privés protégés.')
