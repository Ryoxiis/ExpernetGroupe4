<p class="portal-note">L’accès aux modules d’administration correspond à une proposition de prototype. Les délégations fines entre agents restent à confirmer avec Expernet.
</p>
<div class="admin-forms">
    <details class="portal-card">
        <summary>Créer un compte
        </summary>
        <p>Pas d’inscription publique. Le secret temporaire s’affichera une seule fois ; l’envoi Microsoft 365 n’est pas configuré.
        </p><?php $begin('users');
        $input('name', 'Nom affiché');
        $input('email', 'Adresse Expernet', 'email');
        $select('role', 'Rôle', [['id' => 'student','name' => 'Étudiant'],['id' => 'trainer','name' => 'Formateur'],['id' => 'admin','name' => 'Administrateur']]);
        $end('Créer l’accès temporaire'); ?>
    </details>
    <details class="portal-card">
        <summary>Créer une promotion
        </summary><?php $begin('cohorts');
        $input('name', 'Nom de la promotion');
        $input('starts_on', 'Début', 'date');
        $input('ends_on', 'Fin', 'date');
        $end('Créer la promotion'); ?>
    </details>
    <details class="portal-card">
        <summary>Affecter un membre à une promotion
        </summary><?php $begin('memberships');
        $select('cohort_id', 'Promotion', $state['cohorts']);
        $select('user_id', 'Étudiant ou formateur', array_filter($state['people'], static fn ($p) => $p['role'] !== 'admin' && $p['status'] !== 'disabled'));
        $end('Enregistrer l’affectation'); ?>
    </details>
    <details class="portal-card">
        <summary>Programmer une séance
        </summary><?php $begin('sessions');
        $select('cohort_id', 'Promotion', $state['cohorts']);
        $select('trainer_id', 'Formateur affecté', $trainers);
        $input('title', 'Matière ou titre');
        $input('starts_at', 'Début', 'datetime-local');
        $input('ends_at', 'Fin', 'datetime-local');
        $select('mode', 'Modalité', [['id' => 'onsite','name' => 'Présentiel'],['id' => 'remote','name' => 'Distanciel'],['id' => 'company','name' => 'En entreprise']]);
        $input('location', 'Salle ou lieu');
        $input('meeting_url', 'Lien de réunion HTTPS (facultatif)', 'url', '', false);
        $end('Programmer la séance'); ?>
    </details>
</div>
<section class="portal-card">
    <h2>Comptes et accès
    </h2>
    <div class="portal-table-wrap">
        <table class="portal-table">
            <thead>
                <tr>
                    <th>Compte
                    </th>
                    <th>Rôle
                    </th>
                    <th>État
                    </th>
                    <th>Actions
                    </th>
                </tr>
            </thead>
            <tbody><?php foreach ($state['people'] as $person): ?>
                <tr>
                    <td><strong><?= e($person['name']) ?></strong><br><?= e($person['email']) ?>
                    </td>
                    <td><?= e(['student' => 'Étudiant','trainer' => 'Formateur','admin' => 'Administration'][$person['role']]) ?>
                    </td>
                    <td><?= e(['invited' => 'En attente d’activation','active' => 'Actif','disabled' => 'Désactivé'][$person['status']]) ?>
                    </td>
                    <td><?php if ((int)$person['id'] !== (int)$user['id'] && $person['status'] !== 'disabled'): ?><?php $begin('invitations'); ?>
                        <input type="hidden" name="user_id" value="<?= e($person['id']) ?>"><?php $end('Renouveler l’accès temporaire'); ?><?php $begin('accounts'); ?>
                        <input type="hidden" name="user_id" value="<?= e($person['id']) ?>"><?php $end('Désactiver le compte'); ?><?php else: ?><?= (int)$person['id'] === (int)$user['id'] ? 'Votre compte' : 'Compte désactivé' ?><?php endif; ?>
                    </td>
                </tr><?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<section class="portal-card">
    <h2>Historique des opérations sensibles
    </h2><?php foreach ($state['audit'] as $a): ?>
    <p><?= e($fmt($a['created_at'])) ?> · <?= e($a['actor_name']) ?> · <?= e($a['action']) ?>
    </p>
    <details>
        <summary>Détail de l’opération
        </summary>
        <pre><?= e($a['detail']) ?></pre>
    </details><?php endforeach; ?><?php if (!$state['audit']): ?>
    <p>Aucune opération enregistrée.
    </p><?php endif; ?>
</section>
<section class="portal-card">
    <h2>Satisfaction des promotions
    </h2><?php foreach ($state['feedback'] as $f): ?>
    <p><?= e(($cohortNames[(int) $f['cohort_id']] ?? 'Promotion')) ?> · <?= e(number_format((float)$f['average'], 1, ',', ' ')) ?>/5 · <?= e($f['total']) ?> réponse(s)
    </p><?php endforeach; ?><?php if (!$state['feedback']): ?>
    <p>Aucun avis reçu.
    </p><?php endif; ?>
</section>
