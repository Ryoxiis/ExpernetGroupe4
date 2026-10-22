<div class="portal-dashboard-grid">
    <section class="next-session">
        <div class="next-session-label"><span class="portal-eyebrow">Votre prochaine séance</span><span class="portal-pill"><?= $upcoming ? ($upcoming['mode'] === 'remote' ? 'À distance' : ($upcoming['mode'] === 'company' ? 'En entreprise' : 'En présentiel')) : 'À venir' ?></span>
        </div><?php if ($upcoming): ?>
        <div class="next-session-body">
            <div>
                <p class="next-date"><?= e($fmt($upcoming['starts_at'], 'd/m')) ?><span><?= e($fmt($upcoming['starts_at'], 'H:i')) ?></span>
                </p>
            </div>
            <div>
                <p class="portal-eyebrow"><?= e($upcoming['cohort_name']) ?>
                </p>
                <h2><?= e($upcoming['title']) ?>
                </h2>
                <p><?= e($upcoming['trainer_name']) ?><br><?= e($upcoming['location']) ?> · jusqu’à <?= e($fmt($upcoming['ends_at'], 'H:i')) ?>
                </p>
            </div>
        </div><a class="button button-orange" href="<?= e(url('/espace-pedagogique/seance/?id=' . $upcoming['id'])) ?>">Voir ma séance →</a><?php else: ?>
        <h2>Votre planning est à jour.
        </h2>
        <p>Aucune prochaine séance n’est programmée. Les nouvelles séances apparaîtront ici.
        </p><?php endif; ?>
    </section>
    <section class="portal-card quick-actions">
        <p class="portal-eyebrow">Pour avancer
        </p>
        <h2>Vos accès directs
        </h2><a href="<?= e(url('/espace-pedagogique/travaux/')) ?>"><span>✎</span>
        <div><strong><?= $student ? 'Mes travaux à rendre' : 'Devoirs et rendus' ?></strong><small><?= count($futureAssignments) ?> échéance(s) à venir</small>
        </div><b>↗</b></a><a href="<?= e(url('/espace-pedagogique/ressources/')) ?>"><span>▤</span>
        <div><strong>Mes ressources</strong><small><?= count($state['resources']) ?> document(s) disponible(s)</small>
        </div><b>↗</b></a><a href="<?= e(url('/espace-pedagogique/presences/')) ?>"><span>✓</span>
        <div><strong>Les présences</strong><small>Déclarations et suivi par séance</small>
        </div><b>↗</b></a>
    </section>
</div>
<div class="portal-stats">
    <div><span>Planning</span><strong><?= count($next) ?></strong><small>séance(s) à venir</small>
    </div>
    <div><span><?= $student ? 'Résultats' : 'Promotions' ?></span><strong><?= count($student ? $state['grades'] : $state['cohorts']) ?></strong><small><?= $student ? 'note(s) publiée(s)' : 'promotion(s) accessible(s)' ?></small>
    </div>
    <div><span>Ressources</span><strong><?= count($state['resources']) ?></strong><small>document(s) partagé(s)</small>
    </div>
</div>
<section class="portal-card">
    <div class="section-heading">
        <h2>À votre agenda
        </h2><a class="portal-link" href="<?= e(url('/espace-pedagogique/planning/')) ?>">Tout le planning →</a>
    </div><?php foreach (array_slice($next, 0, 4) as $s): ?><a class="agenda-row" href="<?= e(url('/espace-pedagogique/seance/?id=' . $s['id'])) ?>"><span class="agenda-date"><?= e($fmt($s['starts_at'], 'd/m')) ?><small><?= e($fmt($s['starts_at'], 'H:i')) ?></small></span>
    <div><strong><?= e($s['title']) ?></strong>
        <p><?= e($s['cohort_name']) ?> · <?= e($s['trainer_name']) ?>
        </p>
    </div><span class="agenda-location"><?= e($s['location']) ?> ↗</span></a><?php endforeach; ?><?php if (!$next): ?>
    <p class="portal-empty">Aucune séance à venir pour vos affectations actuelles.
    </p><?php endif; ?>
</section>
