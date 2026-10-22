<section class="collab" data-chat data-user="<?= (int)$user['id'] ?>" data-admin="<?= $user['role'] === 'admin' ? '1' : '0' ?>" data-csrf="<?= e($csrf) ?>" data-api="<?= e(url('/api/discussions/')) ?>" data-full="<?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/espace-pedagogique/messages') ? '1' : '0' ?>">
    <button class="chat-launcher" type="button" aria-expanded="false" aria-controls="chat-panel">Discussions <span data-chat-badge hidden>0</span>
    </button>
    <section class="chat-panel" id="chat-panel" aria-label="Discussions" hidden>
        <header class="chat-top">
            <div><strong>Discussions</strong><small data-connection>Connexion…</small>
            </div>
            <div><a href="<?= e(url('/espace-pedagogique/messages/')) ?>" aria-label="Ouvrir l’espace Discussions">Agrandir ↗</a>
                <button type="button" data-list aria-label="Afficher la liste des conversations">☰
                </button>
                <button type="button" data-minimize aria-label="Réduire les discussions">−
                </button>
            </div>
        </header>
        <p class="chat-status" role="status" aria-live="polite">
        </p>
        <div class="chat-workspace">
            <aside class="chat-sidebar" aria-label="Conversations">
                <label>Formation
                    <select data-cohort>
                        <option value="">Choisir une formation
                        </option>
                    </select>
                </label>
                <button type="button" data-channel>Échanges de la formation
                </button>
                <label>Rechercher un contact
                    <input data-search type="search" placeholder="Au moins 2 caractères" maxlength="100" autocomplete="off">
                </label>
                <div data-contacts>
                </div>
                <nav data-conversations aria-label="Mes discussions">
                </nav>
            </aside>
            <section class="chat-thread" aria-label="Discussion active">
                <header class="chat-thread-top">
                    <h2 data-title>Vos échanges, au même endroit
                    </h2>
                    <div>
                        <button type="button" data-call disabled>Appeler
                        </button>
                        <button type="button" data-plan disabled>Planifier une réunion
                        </button>
                    </div>
                </header>
                <p data-call-info class="chat-call-info" hidden>Les appels et réunions ne sont pas encore configurés.
                </p>
                <div data-meetings class="chat-meetings">
                </div>
                <button type="button" data-older hidden>Messages précédents
                </button>
                <div class="chat-messages" data-messages role="log" aria-label="Messages">
                    <p>Sélectionnez une formation ou une conversation pour commencer.
                    </p>
                </div>
                <form data-compose>
                    <label class="sr-only" for="chat-content">Votre message
                    </label>
                    <textarea id="chat-content" name="content" maxlength="5000" rows="2" placeholder="Écrivez votre message…" required disabled></textarea>
                    <div class="chat-compose-actions">
                        <label class="chat-file-label">Joindre un fichier
                            <input name="file" type="file" accept=".pdf,.png,.jpg,.jpeg,.txt,.docx,.xlsx,.pptx" disabled>
                        </label><small>PDF, images, texte, Office · 10 Mo maximum</small>
                        <button type="submit" disabled>Envoyer
                        </button>
                    </div>
                </form>
            </section>
        </div>
<?php if ($user['role'] === 'admin'): ?>
        <details class="chat-admin">
            <summary>Administrer les droits de discussion
            </summary>
            <p>Les membres affectés à une formation ont accès par défaut. Vous pouvez rattacher un contact administratif, retirer un accès ou rétablir l’héritage. Aucun accès automatique aux conversations privées.
            </p>
            <form data-policy>
                <label>Formation
                    <select name="cohort_id" required>
                    </select>
                </label>
                <div class="chat-flags"><?php foreach (['enabled' => 'Discussions','direct_enabled' => 'Messages privés','files_enabled' => 'Fichiers','meetings_enabled' => 'Réunions'] as $key => $label): ?>
                    <label>
                        <input type="checkbox" name="<?= e($key) ?>" checked> <?= e($label) ?>
                    </label><?php endforeach; ?>
                </div>
                <button>Enregistrer les règles
                </button>
            </form>
            <form data-access>
                <label>Personne
                    <select name="user_id" required>
                    </select>
                </label>
                <label>Accès à cette formation
                    <select name="access">
                        <option value="inherit">Selon l’affectation pédagogique
                        </option>
                        <option value="allow">Autoriser explicitement
                        </option>
                        <option value="deny">Interdire
                        </option>
                    </select>
                </label>
                <button>Enregistrer cet accès
                </button>
            </form>
            <p data-access-summary>
            </p>
            <form data-group>
                <label>Groupe
                    <select name="group_id">
                        <option value="">Nouveau groupe
                        </option>
                    </select>
                </label>
                <label>Nom du groupe
                    <input name="title" maxlength="120" required>
                </label>
                <label>Participants autorisés
                    <select name="user_ids" multiple size="4" required>
                    </select>
                </label>
                <button>Enregistrer le groupe
                </button><small>Les participants ajoutés auront accès à l’historique du groupe.</small>
            </form>
        </details>
<?php endif; ?>
<dialog data-meeting-dialog>
        <form data-meeting-form>
            <h2>Planifier une réunion
            </h2>
            <label>Titre
                <input name="title" maxlength="120" required>
            </label>
            <label>Date et heure (La Réunion)
                <input name="starts_at" type="datetime-local" required>
            </label>
            <div>
                <button type="button" data-cancel-meeting>Annuler
                </button>
                <button>Créer la réunion
                </button>
            </div>
        </form></dialog>
    </section>
</section>
