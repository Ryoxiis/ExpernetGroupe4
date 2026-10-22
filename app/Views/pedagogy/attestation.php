<main id="contenu" class="portal-error printable">
    <p class="portal-eyebrow">Expernet · Document de démonstration
    </p>
    <h1>Relevé des présences
    </h1>
    <p><strong>Ce document n’est pas une attestation officielle et ne comporte aucune signature électronique.</strong>
    </p>
    <p>Compte : <?= e($user['name']) ?> · Édition du <?= e(date('d/m/Y H:i')) ?>, heure de La Réunion.
    </p>
    <table class="portal-table">
        <thead>
            <tr>
                <th>Étudiant
                </th>
                <th>Séance
                </th>
                <th>Déclaration (UTC)
                </th>
                <th>Statut
                </th>
            </tr>
        </thead>
        <tbody><?php foreach ($state['attendance'] as $a): $window = array_values(array_filter($state['windows'], static fn ($w) => (int)$w['id'] === (int)$a['window_id']))[0] ?? null;
            $session = $window ? (array_values(array_filter($state['sessions'], static fn ($s) => (int)$s['id'] === (int)$window['session_id']))[0] ?? null) : null;
            $person = array_values(array_filter($state['people'], static fn ($p) => (int)$p['id'] === (int)$a['student_id']))[0] ?? []; ?>
            <tr>
                <td><?= e($person['name'] ?? 'Étudiant') ?>
                </td>
                <td><?= e($session['title'] ?? 'Séance') ?>
                </td>
                <td><?= e($a['declared_at'] ?? 'Non déclarée') ?>
                </td>
                <td><?= e(['declared' => 'Déclarée, à valider','validated' => 'Validée','late' => 'Retard','absent' => 'Absence','excused' => 'Absence justifiée'][$a['status']]) ?>
                </td>
            </tr><?php endforeach; ?>
        </tbody>
    </table><?php if (!$state['attendance']): ?>
    <p>Aucune présence renseignée.
    </p><?php endif; ?>
    <button class="button button-dark" type="button" data-print>Imprimer / enregistrer en PDF
    </button><a class="portal-link" href="<?= e(url('/espace-pedagogique/presences/')) ?>">Retour aux présences</a>
</main>
