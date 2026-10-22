"""Non-production integration suite. Requires an isolated, freshly seeded MySQL database.
TEST_PORTAL_ACCOUNTS points to the seed-demo --json output (never commit that file).
TEST_PORTAL_URL points to PHP with PORTAL_MFA=false; MFA is checked separately in PHP.
"""
import concurrent.futures
import http.cookiejar
import json
import os
import re
import urllib.error
import urllib.parse
import urllib.request
from datetime import datetime, timedelta
from pathlib import Path

BASE = os.environ.get('TEST_PORTAL_URL', 'http://127.0.0.1:8093').rstrip('/')
accounts = json.loads(Path(os.environ['TEST_PORTAL_ACCOUNTS']).read_text())
checks = 0
PASSWORD = 'Test-local-phrase-2026!'

def check(condition, message):
    global checks
    checks += 1
    if not condition:
        raise AssertionError(message)

class Client:
    def __init__(self):
        self.jar = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(self.jar))
        self.csrf = ''

    def request(self, path, data=None, json_body=False, expected=200):
        headers = {}
        body = None
        if data is not None:
            if json_body:
                body = json.dumps(data).encode()
                headers = {'Content-Type': 'application/json', 'X-CSRF-Token': self.csrf}
            else:
                body = urllib.parse.urlencode(data, doseq=True).encode()
        req = urllib.request.Request(BASE + path, data=body, headers=headers)
        try:
            response = self.opener.open(req, timeout=15)
        except urllib.error.HTTPError as error:
            response = error
        text = response.read().decode()
        check(response.status == expected, f'{path}: {response.status} != {expected}: {text[:600]}')
        token = re.search(r'name="csrf" value="([a-f0-9]+)"', text)
        if token:
            self.csrf = token.group(1)
        return response, text

    def state(self):
        _, text = self.request('/api/pedagogie/etat/')
        result = json.loads(text)
        self.csrf = result['csrf']
        return result['data']

    def action(self, action, data, expected=201):
        _, text = self.request('/api/pedagogie/' + action + '/', data, True, expected)
        return json.loads(text)

    def activate(self, account):
        self.request('/connexion/')
        response, text = self.request('/connexion/', {'csrf':self.csrf, 'email':account['email'], 'password':account['temporary_password']})
        check('/premiere-connexion/' in response.url, 'Initial password screen required')
        self.request('/api/pedagogie/etat/', expected=401)
        self.request('/connexion/premiere-connexion/', {'csrf':self.csrf, 'password':PASSWORD, 'confirmation':PASSWORD})
        check('portal-shell' in self.request('/espace-pedagogique/')[1], 'Dashboard after activation')

anonymous = Client()
anonymous.request('/api/pedagogie/etat/', expected=401)
anonymous.request('/espace-pedagogique/action/', {'action':'users'}, expected=419)
clients = {}
for key, account in accounts.items():
    client = Client()
    client.activate(account)
    clients[key] = client
student, trainer, admin = clients['student'], clients['trainer'], clients['admin']
state = student.state()
other = clients['student2'].state()
check(len(state['cohorts']) == 1 and state['cohorts'][0]['id'] != other['cohorts'][0]['id'], 'Cohort isolation')
check(len(state['sessions']) == 2, 'Student gets only own sessions')
check(all(p['role'] == 'trainer' or p['id'] == accounts['student']['id'] for p in state['people']), 'No other student profile disclosed')
for page in ['','planning/','travaux/','resultats/','ressources/','presences/','messages/','parcours/','examens/']:
    _, text = student.request('/espace-pedagogique/' + page)
    check(text.count('<h1') == 1, 'One heading for ' + page)
student.request('/espace-pedagogique/administration/', expected=403)
student.action('users', {'name':'No','email':'no@expernet.re','role':'admin'},403)
student.action('sessions', {},403)
student.action('grades', {},403)
other_session = other['sessions'][0]['id']
student.request('/espace-pedagogique/seance/?id='+str(other_session), expected=403)
trainer.request('/espace-pedagogique/seance/?id='+str(other_session), expected=403)
resource = state['resources'][0]
student.request('/espace-pedagogique/document/?id='+str(resource['id']))
clients['student2'].request('/espace-pedagogique/document/?id='+str(resource['id']),expected=403)
assignment = state['assignments'][0]
student.action('submissions',{'assignment_id':assignment['id'],'body':'Mon travail de test'})
check(len(trainer.state()['submissions']) == 1, 'Trainer sees submission')
trainer.action('grades', {'assignment_id':assignment['id'],'student_id':accounts['student2']['id'],'value':12,'comment':'Test'},403)
trainer.action('grades', {'assignment_id':assignment['id'],'student_id':accounts['student']['id'],'value':21,'comment':'Test'},422)
trainer.action('grades', {'assignment_id':assignment['id'],'student_id':accounts['student']['id'],'value':15.5,'comment':'Brouillon'})
check(student.state()['grades'] == [], 'Draft is private')
trainer.action('grades', {'assignment_id':assignment['id'],'student_id':accounts['student']['id'],'value':16,'comment':'Publié','published':1})
check(float(student.state()['grades'][0]['value']) == 16, 'Published grade is visible')
window = trainer.action('windows', {'session_id':state['sessions'][0]['id']})
clients['student2'].action('attendance', {'window_id':window['id']},403)
student.action('attendance', {'window_id':window['id']})
student.action('attendance', {'window_id':window['id']},409)
trainer.action('attendance-review', {'window_id':window['id'],'student_id':accounts['student']['id'],'status':'validated','reason':'Présence constatée pendant le cours'})
check(student.state()['attendance'][0]['status'] == 'validated','Validated presence returned')
check(any(a['action'] == 'attendance.reviewed' for a in admin.state()['audit']), 'Correction audited')
student.request('/espace-pedagogique/calendrier/')
_, report = student.request('/espace-pedagogique/attestation/')
check('pas une attestation officielle' in report,'Report clearly labelled')
cohort = state['cohorts'][0]['id']
student.action('messages',{'cohort_id':cohort,'body':'<script>alert(1)</script>'})
_, text = trainer.request('/espace-pedagogique/messages/')
check('&lt;script&gt;' in text and '<script>alert(1)</script>' not in text,'Message escaped')
student.action('journal',{'cohort_id':cohort,'body':'Activité de test'})
student.action('feedback',{'cohort_id':cohort,'rating':5,'body':'Avis identifié de test'})
check(admin.state()['feedback'][0]['total'] == 1,'Feedback summary')
now = datetime.now() + timedelta(days=2)
# Local times must be interpreted in Indian/Reunion by the server.
start = now.strftime('%Y-%m-%dT08:30')
end = now.strftime('%Y-%m-%dT12:00')
trainer.action('unavailability', {'starts_at':start,'ends_at':end,'reason':'Indisponibilité de test'})
check(len(trainer.state()['unavailability']) == 1,'Immediate unavailability')
admin.action('sessions',{'cohort_id':cohort,'trainer_id':accounts['trainer']['id'],'title':'Conflit test','starts_at':start,'ends_at':end,'mode':'onsite','location':'Salle test'},409)
exam = admin.action('exams', {'cohort_id':cohort,'title':'Examen de test','starts_at':start,'location':'Salle test','jury':[accounts['trainer']['id']],'criteria':'Concevoir une API\nSécuriser les accès'})
criterion = trainer.state()['criteria'][0]['id']
trainer.action('evaluations',{'competency_id':criterion,'student_id':accounts['student']['id'],'level':'acquired','comment':'Compétence démontrée'})
check(student.state()['evaluations'][0]['level'] == 'acquired','Student sees own evaluation')
clients['trainer2'].action('evaluations',{'competency_id':criterion,'student_id':accounts['student']['id'],'level':'acquired','comment':'Intrusion'},403)
created = admin.action('users',{'name':'Compte test','email':'compte.test@expernet.re','role':'student'})
invited = Client(); invited.request('/connexion/')
_, text = invited.request('/connexion/',{'csrf':invited.csrf,'email':'compte.test@expernet.re','password':created['temporary_password']})
check('Choisissez maintenant' in text,'New invitation activation')
renewed = admin.action('invitations', {'user_id':created['id']})
invited.request('/connexion/premiere-connexion/', {'csrf':invited.csrf,'password':PASSWORD,'confirmation':PASSWORD},expected=401)
replay = Client();replay.request('/connexion/')
replay.request('/connexion/',{'csrf':replay.csrf,'email':'compte.test@expernet.re','password':created['temporary_password']},expected=401)
admin.action('accounts', {'user_id':accounts['student2']['id']})
clients['student2'].request('/api/pedagogie/etat/',expected=401)
check(all('password_hash' not in u and 'temporary_hash' not in u and 'mfa_secret' not in u for u in admin.state()['people']), 'No credential hashes in state API')
print(f'{checks} contrôles pédagogiques HTTP réussis : activation, révocation, droits, rendus, notes, présences, examens, ressources et échappement.')
