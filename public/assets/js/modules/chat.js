const host = document.querySelector('[data-chat]');
if (host) {
    const q = (selector) => host.querySelector(selector);
    const user = Number(host.dataset.user);
    const base = host.dataset.api;
    const full = host.dataset.full === '1';
    const panel = q('.chat-panel');
    const launcher = q('.chat-launcher');
    const thread = q('[data-messages]');
    const form = q('[data-compose]');
    const status = q('.chat-status');
    const storageKey = `expernet-chat:${user}`;
    let saved = {};
    try {
        saved = JSON.parse(sessionStorage.getItem(storageKey) || '{}');
    } catch {
        /* Storage is optional. */
    }
    let active = Number(saved.active) || null;
    let inbox = null;
    let history = [];
    let policy = {};
    let pendingKey = null;
    let busy = false;
    let refreshing = false;
    let generation = 0;
    let ws = null;
    let stopped = false;
    let reconnectTimer;
    let contactsRequest = 0;
    let admin = null;
    let renderedMessages = '';
    const draftKey = (id) => `${storageKey}:draft:${id}`;
    const node = (tag, text, cls) => {
        const el = document.createElement(tag);
        if (text !== undefined) el.textContent = text;
        if (cls) el.className = cls;
        return el;
    };
    const announce = (text) => {
        status.textContent = text;
    };
    const persist = () => {
        try {
            sessionStorage.setItem(
                storageKey,
                JSON.stringify({ active, open: !panel.hidden }),
            );
        } catch {}
    };
    const show = (open) => {
        panel.hidden = !open;
        launcher.setAttribute('aria-expanded', String(open));
        persist();
        if (open) safe(refresh)();
    };
    if (full) {
        host.classList.add('chat-full');
        document.querySelector('[data-chat-mount]')?.append(host);
    }
    panel.hidden = !(full || saved.open);
    launcher.setAttribute('aria-expanded', String(!panel.hidden));
    launcher.addEventListener('click', () => {
        const open = panel.hidden;
        show(open);
        if (open) {
            const target = active && !host.classList.contains('chat-show-list')
                ? q('[data-title]')
                : q('[data-cohort]');
            target.focus();
        }
    });
    q('[data-list]').addEventListener('click', () => {
        host.classList.add('chat-show-list');
        q('[data-cohort]').focus();
    });
    q('[data-minimize]').addEventListener('click', () => {
        if (full) return;
        show(false);
        launcher.focus();
    });
    host.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !full && !q('dialog').open) {
            show(false);
            launcher.focus();
        }
    });
    // The floating panel is non-modal: let Tab leave it without obscuring focus.
    document.addEventListener('focusin', (event) => {
        if (!full && !panel.hidden && !host.contains(event.target)) show(false);
    });
    async function api(action, data, method = 'POST') {
        const options = {
            method,
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        };
        let url = base + action + '/';
        if (method === 'GET') url += '?' + new URLSearchParams(data || {});
        else {
            options.headers['X-CSRF-Token'] = host.dataset.csrf;
            if (data instanceof FormData) options.body = data;
            else {
                options.headers['Content-Type'] = 'application/json';
                options.body = JSON.stringify(data || {});
            }
        }
        let res;
        try {
            res = await fetch(url, options);
        } catch {
            throw new Error('Connexion interrompue. Reconnexion automatique…');
        }
        if (res.status === 401) {
            stopped = true;
            ws?.close();
            q('[data-connection]').textContent = 'Session expirée';
        }
        const result = await res.json();
        if (!res.ok)
            throw new Error(result.message || 'Le service est indisponible.');
        return result.data;
    }
    const safe =
        (fn) =>
        async (...args) => {
            try {
                await fn(...args);
            } catch (e) {
                announce(e.message || 'Connexion impossible. Réessayez.');
            }
        };
    const time = (value) =>
        new Date(
            value.replace(' ', 'T') + (value.endsWith('Z') ? '' : 'Z'),
        ).toLocaleString('fr-FR', {
            timeZone: 'Indian/Reunion',
            day: '2-digit',
            month: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
        });
    function renderInbox() {
        const selected = q('[data-cohort]').value;
        q('[data-cohort]').replaceChildren(
            new Option('Choisir une formation', ''),
        );
        inbox.cohorts.forEach((c) =>
            q('[data-cohort]').add(new Option(c.name, c.id)),
        );
        q('[data-cohort]').value =
            selected ||
            String(
                inbox.conversations.find((c) => Number(c.id) === active)
                    ?.cohort_id ||
                    (inbox.cohorts.length === 1 ? inbox.cohorts[0].id : ''),
            );
        const list = q('[data-conversations]');
        const focusedConversation = list.contains(document.activeElement)
            ? document.activeElement.dataset.conversation
            : null;
        list.replaceChildren();
        let unread = 0;
        inbox.conversations.forEach((c) => {
            unread += Number(c.unread_count);
            const button = node(
                'button',
                undefined,
                Number(c.id) === active
                    ? 'chat-conversation is-active'
                    : 'chat-conversation',
            );
            button.type = 'button';
            button.dataset.conversation = String(c.id);
            button.setAttribute(
                'aria-current',
                Number(c.id) === active ? 'true' : 'false',
            );
            button.append(
                node('strong', c.title),
                node('small', c.last_message || 'Commencer la discussion'),
            );
            if (Number(c.unread_count)) {
                const badge = node('span', c.unread_count, 'chat-count');
                badge.setAttribute('aria-label', `${c.unread_count} messages non lus`);
                button.append(badge);
            }
            button.addEventListener(
                'click',
                safe(() => select(Number(c.id))),
            );
            list.append(button);
        });
        if (!inbox.conversations.length)
            list.append(
                node(
                    'p',
                    'Aucune conversation accessible. Votre administration peut vous rattacher à une formation.',
                ),
            );
        q('[data-chat-badge]').hidden = unread === 0;
        q('[data-chat-badge]').textContent = String(unread);
        q('[data-chat-badge]').setAttribute('aria-label', `${unread} messages non lus`);
        if (focusedConversation) {
            const restored = [...list.querySelectorAll('button')].find(
                (button) => button.dataset.conversation === focusedConversation,
            );
            (restored || q('[data-cohort]')).focus({ preventScroll: true });
        }
    }
    function controls(enabled) {
        host.classList.toggle('chat-has-conversation', Boolean(active));
        form.elements.content.disabled = !enabled;
        form.elements.file.disabled = !enabled || !Number(policy.files_enabled);
        form.querySelector('button').disabled = !enabled || busy;
        form.querySelector('button').textContent = busy ? 'Envoi en cours…' : 'Envoyer';
        form.setAttribute('aria-busy', String(busy));
        form.elements.content.required = !form.elements.file.files.length;
        q('[data-call]').disabled =
            !enabled ||
            !inbox?.meetings_available ||
            !Number(policy.meetings_enabled);
        q('[data-plan]').disabled = q('[data-call]').disabled;
        q('[data-call-info]').hidden = Boolean(inbox?.meetings_available);
        q('[data-call]').title = inbox?.meetings_available
            ? ''
            : 'La visioconférence doit être configurée par l’administrateur technique.';
    }
    function saveDraft() {
        if (!active) return;
        try {
            sessionStorage.setItem(draftKey(active), form.elements.content.value);
        } catch {
            /* Storage is optional. */
        }
    }
    async function select(id) {
        saveDraft();
        generation++;
        active = id;
        history = [];
        renderedMessages = '';
        pendingKey = null;
        form.reset();
        host.classList.remove('chat-show-list');
        thread.replaceChildren(node('p', 'Chargement de la conversation…'));
        try {
            form.elements.content.value =
                sessionStorage.getItem(draftKey(id)) || '';
        } catch {}
        persist();
        show(true);
        controls(false);
        q('[data-title]').textContent = 'Chargement de la conversation…';
        q('[data-title]').focus({ preventScroll: true });
        await loadHistory();
        renderInbox();
    }
    function renderMessages() {
        // Polling must not re-announce the same log or destroy text selection.
        const signature = JSON.stringify([active, policy.files_enabled, history]);
        if (signature === renderedMessages) return;
        renderedMessages = signature;
        const atBottom =
            thread.scrollHeight - thread.scrollTop - thread.clientHeight < 90;
        const oldHeight = thread.scrollHeight;
        const oldTop = thread.scrollTop;
        thread.replaceChildren();
        history.forEach((m) => {
            const item = node(
                'article',
                undefined,
                Number(m.sender_id) === user
                    ? 'chat-message own'
                    : 'chat-message',
            );
            item.append(node('strong', m.sender_name), node('p', m.content));
            if (m.file_id && Number(policy.files_enabled)) {
                const link = node(
                    'a',
                    `${m.file_name} · ${Math.ceil(Number(m.file_size) / 1024)} Ko`,
                );
                link.href = `${base}file/?id=${encodeURIComponent(m.file_id)}`;
                item.append(link);
            }
            const stamp = node('small', time(m.created_at));
            item.append(stamp);
            thread.append(item);
        });
        if (!history.length)
            thread.append(node('p', 'La conversation commence ici.'));
        thread.scrollTop = atBottom
            ? thread.scrollHeight
            : oldTop + (thread.scrollHeight - oldHeight);
    }
    async function markRead() {
        if (
            active &&
            history.length &&
            !panel.hidden &&
            document.visibilityState === 'visible' &&
            thread.scrollHeight - thread.scrollTop - thread.clientHeight < 90
        ) {
            await api('read', {
                conversation_id: active,
                message_id: history.at(-1).id,
            });
        }
    }
    async function loadHistory(before = 0) {
        if (!active) return;
        const id = active,
            version = generation;
        let data;
        const after = !before && history.length ? Number(history.at(-1).id) : 0;
        try {
            data = await api(
                'history',
                {
                    conversation_id: id,
                    ...(before ? { before } : after ? { after } : {}),
                },
                'GET',
            );
        } catch (e) {
            if (version === generation) {
                if (!history.length) {
                    renderedMessages = '';
                    thread.replaceChildren(node('p', e.message));
                }
                controls(false);
            }
            throw e;
        }
        if (id !== active || version !== generation) return;
        policy = data.policy;
        const map = new Map(
            (before
                ? [...data.messages, ...history]
                : [...history, ...data.messages]
            ).map((m) => [Number(m.id), m]),
        );
        history = [...map.values()].sort((a, b) => Number(a.id) - Number(b.id));
        if (before || history.length <= 50)
            q('[data-older]').hidden = !data.has_more;
        renderMessages();
        controls(true);
        q('[data-title]').textContent =
            inbox?.conversations.find((c) => Number(c.id) === id)?.title ||
            'Discussion';
        const meetings = q('[data-meetings]');
        meetings.replaceChildren();
        data.meetings.forEach((m) => {
            const button = node(
                'button',
                `${m.title} · ${time(m.starts_at)} · Rejoindre`,
            );
            button.type = 'button';
            button.addEventListener(
                'click',
                safe(() => join(m.id)),
            );
            meetings.append(button);
        });
        await markRead();
        if (after && data.has_more) await loadHistory();
    }
    let readTimer;
    thread.addEventListener('scroll', () => {
        clearTimeout(readTimer);
        readTimer = setTimeout(safe(markRead), 300);
    });
    async function refresh() {
        if (refreshing || stopped) return;
        refreshing = true;
        try {
            inbox = await api('inbox', {}, 'GET');
            if (
                status.textContent ===
                    'Connexion interrompue. Reconnexion automatique…' ||
                status.textContent === 'Failed to fetch'
            )
                announce('');
            if (
                active &&
                !inbox.conversations.some((c) => Number(c.id) === active)
            ) {
                active = null;
                history = [];
                generation++;
                thread.replaceChildren(
                    node('p', 'Cette conversation n’est plus accessible.'),
                );
                controls(false);
                persist();
            }
            renderInbox();
            if (!panel.hidden && active && !busy) await loadHistory();
            if (!ws || ws.readyState !== WebSocket.OPEN)
                q('[data-connection]').textContent =
                    'Actualisation automatique';
        } finally {
            refreshing = false;
        }
    }
    q('[data-channel]').addEventListener(
        'click',
        safe(async () => {
            const cohort = Number(q('[data-cohort]').value);
            if (!cohort) throw new Error('Choisissez une formation.');
            const result = await api('open', { cohort_id: cohort });
            await refresh();
            await select(Number(result.id));
        }),
    );
    let searchTimer;
    q('[data-search]').addEventListener('input', () => {
        clearTimeout(searchTimer);
        const request = ++contactsRequest;
        q('[data-contacts]').replaceChildren();
        searchTimer = setTimeout(
            safe(async () => {
                const text = q('[data-search]').value.trim(),
                    cohort = q('[data-cohort]').value;
                if (text.length < 2 || !cohort) return;
                const result = await api(
                    'contacts',
                    { cohort_id: cohort, q: text },
                    'GET',
                );
                if (request !== contactsRequest) return;
                const list = q('[data-contacts]');
                list.replaceChildren();
                result.contacts.forEach((contact) => {
                    const button = node('button', contact.name);
                    button.type = 'button';
                    button.addEventListener(
                        'click',
                        safe(async () => {
                            const c = await api('open', {
                                cohort_id: Number(cohort),
                                target_user_id: Number(contact.id),
                            });
                            await refresh();
                            await select(Number(c.id));
                            list.replaceChildren();
                        }),
                    );
                    list.append(button);
                });
                if (!result.contacts.length)
                    list.append(node('p', 'Aucun contact autorisé trouvé.'));
            }),
            300,
        );
    });
    q('[data-cohort]').addEventListener('change', () => {
        contactsRequest++;
        q('[data-contacts]').replaceChildren();
        q('[data-search]').value = '';
    });
    q('[data-older]').addEventListener(
        'click',
        safe(() => loadHistory(Number(history[0]?.id))),
    );
    form.addEventListener('input', () => {
        pendingKey = null;
        controls(Boolean(active));
        saveDraft();
    });
    form.addEventListener(
        'submit',
        safe(async (e) => {
            e.preventDefault();
            if (busy || !active) return;
            const id = active;
            const data = new FormData(form);
            if (
                !String(data.get('content')).trim() &&
                !form.elements.file.files.length
            )
                throw new Error('Écrivez un message ou joignez un fichier.');
            pendingKey ||= crypto.randomUUID();
            data.set('conversation_id', String(id));
            data.set('client_key', pendingKey);
            busy = true;
            controls(false);
            announce('Envoi en cours…');
            try {
                await api('send', data);
                try {
                    sessionStorage.removeItem(draftKey(id));
                } catch {}
                if (active === id) {
                    form.reset();
                    pendingKey = null;
                }
                announce('Message envoyé.');
            } finally {
                busy = false;
                controls(Boolean(active));
            }
            await refresh();
        }),
    );
    async function join(id) {
        const win = window.open('about:blank', '_blank');
        if (!win)
            throw new Error('Autorisez l’ouverture de la fenêtre de réunion.');
        win.opener = null;
        try {
            const data = await api('join', { meeting_id: Number(id) });
            win.location.replace(data.url);
        } catch (e) {
            win.close();
            throw e;
        }
    }
    q('[data-call]').addEventListener(
        'click',
        safe(async () => {
            const result = await api('meeting', {
                conversation_id: active,
                title: 'Appel immédiat',
                starts_at: new Date().toISOString(),
            });
            await loadHistory();
            announce(
                'Appel créé. Utilisez « Rejoindre » dans la conversation.',
            );
        }),
    );
    q('[data-plan]').addEventListener('click', () =>
        q('[data-meeting-dialog]').showModal(),
    );
    q('[data-cancel-meeting]').addEventListener('click', () =>
        q('[data-meeting-dialog]').close(),
    );
    q('[data-meeting-form]').addEventListener(
        'submit',
        safe(async (e) => {
            e.preventDefault();
            const meetingForm = e.currentTarget;
            const data = new FormData(meetingForm);
            await api('meeting', {
                conversation_id: active,
                title: data.get('title'),
                starts_at: data.get('starts_at') + ':00+04:00',
            });
            q('[data-meeting-dialog]').close();
            meetingForm.reset();
            await loadHistory();
            announce('Réunion planifiée.');
        }),
    );
    function renderPolicy() {
        const cohort = q('[data-policy]').elements.cohort_id.value;
        const p =
            admin.policies.find((p) => String(p.cohort_id) === cohort) || {};
        for (const key of [
            'enabled',
            'direct_enabled',
            'files_enabled',
            'meetings_enabled',
        ])
            q('[data-policy]').elements[key].checked =
                Number(p[key] ?? 1) === 1;
        renderAccess();
        renderGroups();
    }
    function renderAccess() {
        const cohort = q('[data-policy]').elements.cohort_id.value;
        const uid = q('[data-access]').elements.user_id.value;
        const entry = admin.access.find(
            (a) => String(a.cohort_id) === cohort && String(a.user_id) === uid,
        );
        q('[data-access]').elements.access.value = entry
            ? Number(entry.allowed)
                ? 'allow'
                : 'deny'
            : 'inherit';
        const inherited = admin.memberships.some(
            (m) => String(m.cohort_id) === cohort && String(m.user_id) === uid,
        );
        q('[data-access-summary]').textContent = entry
            ? `Accès explicite : ${Number(entry.allowed) ? 'autorisé' : 'interdit'}.`
            : `Selon l’affectation : ${inherited ? 'autorisé' : 'non autorisé'}.`;
    }
    function renderGroups() {
        const cohort = q('[data-policy]').elements.cohort_id.value;
        const groupForm = q('[data-group]');
        const id = groupForm.elements.group_id.value;
        const groups = admin.groups.filter(
            (g) => String(g.cohort_id) === cohort,
        );
        groupForm.elements.group_id.replaceChildren(
            new Option('Nouveau groupe', ''),
            ...groups.map((g) => new Option(g.title, g.id)),
        );
        if (groups.some((g) => String(g.id) === id))
            groupForm.elements.group_id.value = id;
        renderGroupMembers();
    }
    function renderGroupMembers() {
        const cohort = q('[data-policy]').elements.cohort_id.value;
        const groupForm = q('[data-group]');
        const group = admin.groups.find(
            (g) => String(g.id) === groupForm.elements.group_id.value,
        );
        groupForm.elements.title.value = group?.title || '';
        const members = admin.participants
            .filter((p) => String(p.conversation_id) === String(group?.id))
            .map((p) => String(p.user_id));
        const allowed = admin.users.filter((u) => {
            if (u.status !== 'active') return false;
            const access = admin.access.find(
                (a) =>
                    String(a.cohort_id) === cohort &&
                    Number(a.user_id) === Number(u.id),
            );
            return access
                ? Number(access.allowed) === 1
                : admin.memberships.some(
                      (m) =>
                          String(m.cohort_id) === cohort &&
                          Number(m.user_id) === Number(u.id),
                  );
        });
        groupForm.elements.user_ids.replaceChildren(
            ...allowed.map(
                (u) =>
                    new Option(
                        u.name,
                        u.id,
                        false,
                        members.includes(String(u.id)),
                    ),
            ),
        );
    }
    async function loadAdmin() {
        if (host.dataset.admin !== '1') return;
        admin = await api('administration', {}, 'GET');
        const selected = q('[data-policy]').elements.cohort_id.value;
        const uid = q('[data-access]').elements.user_id.value;
        q('[data-policy]').elements.cohort_id.replaceChildren(
            ...admin.cohorts.map((c) => new Option(c.name, c.id)),
        );
        q('[data-access]').elements.user_id.replaceChildren(
            ...admin.users.map(
                (u) => new Option(`${u.name} (${u.role})`, u.id),
            ),
        );
        if (selected) q('[data-policy]').elements.cohort_id.value = selected;
        if (uid) q('[data-access]').elements.user_id.value = uid;
        renderPolicy();
    }
    if (host.dataset.admin === '1') {
        q('[data-group]').elements.group_id.addEventListener(
            'change',
            renderGroupMembers,
        );
        q('[data-group]').addEventListener(
            'submit',
            safe(async (e) => {
                e.preventDefault();
                const groupForm = e.currentTarget;
                const data = {
                    cohort_id: Number(
                        q('[data-policy]').elements.cohort_id.value,
                    ),
                    title: groupForm.elements.title.value,
                    user_ids: [
                        ...groupForm.elements.user_ids.selectedOptions,
                    ].map((o) => Number(o.value)),
                };
                if (groupForm.elements.group_id.value)
                    data.group_id = Number(groupForm.elements.group_id.value);
                await api('group', data);
                await loadAdmin();
                await refresh();
                announce('Groupe enregistré.');
            }),
        );
        q('[data-policy]').elements.cohort_id.addEventListener(
            'change',
            renderPolicy,
        );
        q('[data-access]').elements.user_id.addEventListener(
            'change',
            renderAccess,
        );
        q('[data-policy]').addEventListener(
            'submit',
            safe(async (e) => {
                e.preventDefault();
                const data = {
                    cohort_id: Number(e.currentTarget.elements.cohort_id.value),
                };
                for (const key of [
                    'enabled',
                    'direct_enabled',
                    'files_enabled',
                    'meetings_enabled',
                ])
                    data[key] = Number(e.currentTarget.elements[key].checked);
                await api('permissions', data);
                await loadAdmin();
                await refresh();
                announce('Règles enregistrées.');
            }),
        );
        q('[data-access]').addEventListener(
            'submit',
            safe(async (e) => {
                e.preventDefault();
                const data = Object.fromEntries(new FormData(e.currentTarget));
                data.cohort_id = Number(
                    q('[data-policy]').elements.cohort_id.value,
                );
                await api('permissions', data);
                await loadAdmin();
                await refresh();
                announce('Accès enregistré.');
            }),
        );
    }
    async function connect() {
        if (!inbox?.ws_url || stopped) return;
        const { token } = await api('ticket', {});
        ws = new WebSocket(inbox.ws_url);
        ws.onopen = () => ws.send(JSON.stringify({ type: 'auth', token }));
        ws.onmessage = safe(async (e) => {
            const data = JSON.parse(e.data);
            if (data.event === 'ready')
                q('[data-connection]').textContent = 'Connecté en direct';
            if (data.event === 'refresh') await refresh();
        });
        ws.onclose = () => {
            q('[data-connection]').textContent = 'Actualisation automatique';
            if (!stopped) reconnectTimer = setTimeout(safe(connect), 10000);
        };
    }
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) safe(refresh)();
    });
    window.addEventListener('pagehide', () => {
        stopped = true;
        clearTimeout(reconnectTimer);
        ws?.close();
    });
    setInterval(() => {
        if (!document.hidden) safe(refresh)();
    }, 5000);
    safe(async () => {
        await refresh();
        await loadAdmin();
        await connect();
    })();
}
