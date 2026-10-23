/** Photos officielles : aucune dépendance, contenu disponible sans JavaScript. */
export function initCarousel() {
    document.querySelectorAll('[data-carousel]').forEach((root) => {
        if (root.classList.contains('is-ready')) return;
        const slides = [...root.querySelectorAll('[data-slide]')];
        if (slides.length < 2) return;
        const controls = root.querySelector('[data-carousel-controls]');
        const counter = root.querySelector('[data-counter]');
        const status = root.querySelector('[data-carousel-status]');
        const dots = [...root.querySelectorAll('[data-go-to]')];
        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
        let index = 0;
        let playing = !reduced.matches;
        let hovered = false;
        let focusPaused = false;
        let inView = typeof IntersectionObserver === 'undefined';
        let timer;
        let pointer;

        function schedule() {
            clearTimeout(timer);
            if (playing && inView && !hovered && !focusPaused && !document.hidden) {
                timer = setTimeout(() => show(index + 1), 3000);
            }
        }

        function show(next, announce = false) {
            index = (next + slides.length) % slides.length;
            slides.forEach((slide, position) => { slide.hidden = position !== index; });
            dots.forEach((dot, position) => {
                if (position === index) dot.setAttribute('aria-current', 'true');
                else dot.removeAttribute('aria-current');
            });
            counter.textContent = `${String(index + 1).padStart(2, '0')} / ${String(slides.length).padStart(2, '0')}`;
            if (announce) status.textContent = `Photo ${index + 1} sur ${slides.length} : ${slides[index].querySelector('h3').textContent}`;
            schedule();
        }

        function select(next) {
            show(next, true);
        }

        root.querySelector('[data-previous]').addEventListener('click', () => select(index - 1));
        root.querySelector('[data-next]').addEventListener('click', () => select(index + 1));
        dots.forEach((dot) => dot.addEventListener('click', () => select(Number(dot.dataset.goTo))));
        root.addEventListener('mouseenter', () => { hovered = true; schedule(); });
        root.addEventListener('mouseleave', () => { hovered = false; schedule(); });
        root.addEventListener('focusin', (event) => {
            focusPaused = event.target.matches?.(':focus-visible') ?? true;
            schedule();
        });
        root.addEventListener('focusout', (event) => {
            if (!root.contains(event.relatedTarget)) { focusPaused = false; schedule(); }
        });
        root.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
                event.preventDefault();
                focusPaused = true;
                select(index + (event.key === 'ArrowLeft' ? -1 : 1));
            }
        });
        const imageArea = root.querySelector('.stories-slides');
        imageArea.addEventListener('pointerdown', (event) => {
            if (event.isPrimary) pointer = { x: event.clientX, y: event.clientY };
        });
        imageArea.addEventListener('pointerup', (event) => {
            if (!pointer) return;
            const dx = event.clientX - pointer.x;
            const dy = event.clientY - pointer.y;
            pointer = null;
            if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) select(index + (dx < 0 ? 1 : -1));
        });
        imageArea.addEventListener('pointercancel', () => { pointer = null; });
        reduced.addEventListener('change', () => { if (reduced.matches) { playing = false; schedule(); } });
        document.addEventListener('visibilitychange', schedule);
        if (typeof IntersectionObserver !== 'undefined') {
            const observer = new IntersectionObserver(([entry]) => {
                inView = entry.isIntersecting;
                schedule();
            }, { threshold: 0.2 });
            observer.observe(root);
        }
        root.classList.add('is-ready');
        controls.hidden = false;
        show(0);
    });
}
