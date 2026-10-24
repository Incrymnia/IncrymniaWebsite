/* Emberforge – progressive enhancement. The page works without this file. */
(() => {
    'use strict';

    const $ = (sel, root = document) => root.querySelector(sel);
    const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ------------------------------------------------------------------
     * Header: scrolled state, mobile menu, active section
     * ---------------------------------------------------------------- */
    const header = $('#site-header');
    const toggle = $('.nav-toggle');
    const nav = $('#site-nav');

    const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 8);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });

    const setMenu = (open) => {
        toggle.setAttribute('aria-expanded', String(open));
        nav.classList.toggle('is-open', open);
    };
    toggle.addEventListener('click', () => setMenu(toggle.getAttribute('aria-expanded') !== 'true'));
    $$('a', nav).forEach((a) => a.addEventListener('click', () => setMenu(false)));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && nav.classList.contains('is-open')) {
            setMenu(false);
            toggle.focus();
        }
    });


    /* ------------------------------------------------------------------
     * "Builds" dropdown in the navigation
     * ---------------------------------------------------------------- */
    $$('.nav__sub-toggle').forEach((btn) => {
        const close = () => btn.setAttribute('aria-expanded', 'false');
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            btn.setAttribute('aria-expanded', String(btn.getAttribute('aria-expanded') !== 'true'));
        });
        document.addEventListener('click', (e) => {
            if (!btn.parentElement.contains(e.target)) close();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && btn.getAttribute('aria-expanded') === 'true') {
                close();
                btn.focus();
            }
        });
        btn.parentElement.addEventListener('focusout', (e) => {
            if (!btn.parentElement.contains(e.relatedTarget)) close();
        });
    });

    const navLinks = $$('.nav__list a');
    const spyTargets = navLinks
        .map((a) => {
            // Links may be "#builds" (home page) or "/#builds" (other pages): only track same-page anchors
            let hash = '';
            try {
                const u = new URL(a.href, window.location.href);
                hash = u.pathname === window.location.pathname ? u.hash : '';
            } catch (err) { hash = ''; }
            return { link: a, section: hash.length > 1 ? document.getElementById(hash.slice(1)) : null };
        })
        .filter((t) => t.section);

    if ('IntersectionObserver' in window && spyTargets.length) {
        const spy = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                navLinks.forEach((l) => {
                    l.classList.remove('is-active');
                    l.removeAttribute('aria-current');
                });
                const match = spyTargets.find((t) => t.section === entry.target);
                if (match) {
                    match.link.classList.add('is-active');
                    match.link.setAttribute('aria-current', 'true');
                }
            });
        }, { rootMargin: '-45% 0px -50% 0px' });
        spyTargets.forEach((t) => spy.observe(t.section));

        // Clear the highlight when back at the hero or in the quote section
        const clear = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    navLinks.forEach((l) => {
                        l.classList.remove('is-active');
                        l.removeAttribute('aria-current');
                    });
                }
            });
        }, { rootMargin: '-45% 0px -50% 0px' });
        ['#top', '#quote'].forEach((id) => { const el = $(id); if (el) clear.observe(el); });
    }

    /* ------------------------------------------------------------------
     * Hero: bars rise once on load, then build profile tabs
     * ---------------------------------------------------------------- */
    const profile = $('#profile');
    const dataEl = $('#build-data');

    if (profile) {
        requestAnimationFrame(() => requestAnimationFrame(() => profile.classList.add('is-ready')));
    }

    if (profile && dataEl) {
        let data = null;
        try { data = JSON.parse(dataEl.textContent).builds; } catch (err) { data = null; }

        if (data) {
            const tabs = $$('.seg__btn[data-build]', profile);
            const panel = $('#profile-panel', profile);
            const focusEl = $('[data-focus]', profile);
            const bars = $$('.bar', profile);
            const rowsEl = $('[data-rows]', profile);
            const cta = $('.profile__cta', profile);

            const render = (key) => {
                const b = data[key];
                if (!b) return;

                focusEl.textContent = b.focus;

                const values = b.profile.map((p) => p.value);
                const order = values.map((v, i) => i).sort((a, c) => values[c] - values[a] || a - c);
                bars.forEach((bar, i) => {
                    bar.style.setProperty('--v', values[i]);
                    bar.classList.toggle('is-top', i === order[0]);
                    bar.classList.toggle('is-second', i === order[1]);
                    $('.sr-only', bar).textContent = `${b.profile[i].label} priority: ${values[i]} out of 100`;
                });

                rowsEl.replaceChildren(...b.rows.map((r) => {
                    const level = r.priority.toLowerCase();
                    const li = document.createElement('li');
                    li.className = 'row';

                    const dot = document.createElement('span');
                    dot.className = `row__dot row__dot--${level}`;
                    dot.setAttribute('aria-hidden', 'true');

                    const text = document.createElement('span');
                    text.className = 'row__text';
                    const title = document.createElement('span');
                    title.className = 'row__title';
                    title.textContent = r.title;
                    const note = document.createElement('span');
                    note.className = 'row__note';
                    note.textContent = r.note;
                    text.append(title, note);

                    const pill = document.createElement('span');
                    pill.className = `pill pill--${level}`;
                    pill.textContent = r.priority;

                    li.append(dot, text, pill);
                    return li;
                }));

                cta.href = b.url;
                cta.textContent = `Explore ${b.label}`;
            };

            const select = (tab, focus) => {
                tabs.forEach((t) => {
                    const on = t === tab;
                    t.classList.toggle('is-active', on);
                    t.setAttribute('aria-selected', String(on));
                    t.tabIndex = on ? 0 : -1;
                });
                panel.setAttribute('aria-labelledby', tab.id);
                render(tab.dataset.build);
                if (focus) tab.focus();
            };

            tabs.forEach((tab, i) => {
                tab.addEventListener('click', () => select(tab, false));
                tab.addEventListener('keydown', (e) => {
                    let next = null;
                    if (e.key === 'ArrowRight') next = tabs[(i + 1) % tabs.length];
                    if (e.key === 'ArrowLeft') next = tabs[(i - 1 + tabs.length) % tabs.length];
                    if (e.key === 'Home') next = tabs[0];
                    if (e.key === 'End') next = tabs[tabs.length - 1];
                    if (next) { e.preventDefault(); select(next, true); }
                });
            });
        }
    }

    /* ------------------------------------------------------------------
     * Stats count up (once)
     * ---------------------------------------------------------------- */
    const counters = $$('[data-count]');
    if (counters.length && !reduceMotion && 'IntersectionObserver' in window) {
        const run = (el) => {
            const end = parseInt(el.dataset.count, 10);
            const suffix = el.dataset.suffix || '';
            const duration = 1100;
            const start = performance.now();
            const tick = (now) => {
                const t = Math.min((now - start) / duration, 1);
                const eased = 1 - Math.pow(1 - t, 3);
                el.textContent = Math.round(end * eased) + suffix;
                if (t < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        };
        const io = new IntersectionObserver((entries, obs) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) { run(entry.target); obs.unobserve(entry.target); }
            });
        }, { threshold: 0.6 });
        counters.forEach((el) => io.observe(el));
    }

    /* ------------------------------------------------------------------
     * "Quote this build" links: pre-select the build, no page reload
     * ---------------------------------------------------------------- */
    $$('[data-quote-link]').forEach((link) => {
        link.addEventListener('click', (e) => {
            const form = $('#quote-form');
            const radio = form && $(`input[name="build"][value="${link.dataset.build}"]`, form);
            if (!radio) return; // no form on screen (e.g. after sending): let the link work normally
            e.preventDefault();
            radio.checked = true;
            clearError('build');
            history.replaceState(null, '', `?build=${link.dataset.build}#quote`);
            $('#quote').scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth' });
        });
    });

    /* ------------------------------------------------------------------
     * Gallery: filter + lightbox
     * ---------------------------------------------------------------- */
    const filterBar = $('#gallery-filter');
    const items = $$('.gallery__item');
    const status = $('#gallery-status');

    if (filterBar) {
        $$('.seg__btn', filterBar).forEach((btn) => {
            btn.addEventListener('click', () => {
                const f = btn.dataset.filter;
                $$('.seg__btn', filterBar).forEach((b) => {
                    const on = b === btn;
                    b.classList.toggle('is-active', on);
                    b.setAttribute('aria-pressed', String(on));
                });
                let shown = 0;
                items.forEach((li) => {
                    const match = f === 'all' || li.dataset.type === f;
                    li.hidden = !match;
                    if (match) shown++;
                });
                status.textContent = `Showing ${shown} ${shown === 1 ? 'build' : 'builds'}`;
            });
        });
    }

    const dlg = $('#lightbox');
    if (dlg && typeof dlg.showModal === 'function') {
        const els = {
            art: $('#lightbox-art'),
            type: $('#lightbox-type'),
            title: $('#lightbox-title'),
            spec: $('#lightbox-spec'),
            desc: $('#lightbox-desc'),
            quote: $('#lightbox-quote'),
        };
        let list = [];
        let index = 0;

        const show = (i) => {
            index = (i + list.length) % list.length;
            const tile = list[index];
            const art = $('.tile__art', tile);
            els.art.replaceChildren(...Array.from(art.children, (n) => n.cloneNode(true)));
            // The lightbox shows the picture larger than the thumbnail, so ask for a bigger file
            $$('source, img', els.art).forEach((n) => {
                if (n.hasAttribute('sizes')) n.setAttribute('sizes', '(max-width: 720px) 100vw, 560px');
            });
            els.art.classList.toggle('is-contain', art.classList.contains('is-contain'));
            els.type.textContent = tile.dataset.typeLabel;
            els.title.textContent = tile.dataset.title;
            els.spec.textContent = tile.dataset.spec;
            els.desc.textContent = tile.dataset.desc;
            els.quote.dataset.build = tile.closest('.gallery__item').dataset.type;
        };

        $$('.tile').forEach((tile) => {
            tile.addEventListener('click', () => {
                list = $$('.tile').filter((t) => !t.closest('.gallery__item').hidden);
                show(list.indexOf(tile));
                dlg.showModal();
            });
        });

        $('#lightbox-prev').addEventListener('click', () => show(index - 1));
        $('#lightbox-next').addEventListener('click', () => show(index + 1));
        $('#lightbox-close').addEventListener('click', () => dlg.close());
        dlg.addEventListener('click', (e) => { if (e.target === dlg) dlg.close(); });
        dlg.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') show(index - 1);
            if (e.key === 'ArrowRight') show(index + 1);
        });

        // From the lightbox to the quote form with the build pre-selected
        els.quote.addEventListener('click', (e) => {
            const form = $('#quote-form');
            const radio = form && $(`input[name="build"][value="${els.quote.dataset.build}"]`, form);
            dlg.close();
            if (radio) {
                e.preventDefault();
                radio.checked = true;
                clearError('build');
                $('#quote').scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth' });
            }
        });
    }

    /* ------------------------------------------------------------------
     * Quote form: validation messages, counter, sending state
     * ---------------------------------------------------------------- */
    const form = $('#quote-form');

    function errorEl(field) { return $(`[data-error-for="${field}"]`); }

    function clearError(field) {
        const el = errorEl(field);
        if (el) { el.textContent = ''; el.hidden = true; }
        if (!form) return;
        $$(`[name="${field}"]`, form).forEach((input) => {
            input.removeAttribute('aria-invalid');
            input.removeAttribute('aria-describedby');
        });
    }

    function setError(field, message) {
        const el = errorEl(field);
        if (el) { el.textContent = message; el.hidden = false; }
        $$(`[name="${field}"]`, form).forEach((input) => {
            input.setAttribute('aria-invalid', 'true');
            input.setAttribute('aria-describedby', `err-${field}`);
        });
    }

    if (form) {
        const message = $('#q-message');
        const counter = $('#message-counter');
        const updateCounter = () => { counter.textContent = `${message.value.length} / 3000`; };
        message.addEventListener('input', updateCounter);
        updateCounter();

        const rules = [
            ['build', () => $('input[name="build"]:checked', form), 'Choose the kind of computer you need.'],
            ['name', () => form.elements.namedItem('name').value.trim().length >= 2, 'Enter your name.'],
            ['email', () => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.value.trim()), 'Enter a valid email address so we can send your quote.'],
            ['phone', () => !form.phone.value.trim() || /^[0-9+()\-.\s]{6,25}$/.test(form.phone.value.trim()), 'Use digits, spaces, + or - in the phone number, or leave it blank.'],
            ['budget', () => form.budget.value !== '', 'Choose a budget range. “Not sure yet” is fine.'],
            ['timeline', () => form.timeline.value !== '', 'Choose when you need it.'],
            ['message', () => form.message.value.trim().length >= 10, 'Tell us a little more about what you will use it for (at least 10 characters).'],
            ['consent', () => form.consent.checked, 'Tick the box so we know we can contact you about this request.'],
        ];

        // Clear a field's error as soon as the person edits it
        ['input', 'change'].forEach((evt) => {
            form.addEventListener(evt, (e) => {
                const name = e.target && e.target.name;
                if (name) clearError(name);
            });
        });

        form.addEventListener('submit', (e) => {
            let firstInvalid = null;
            rules.forEach(([field, test, msg]) => {
                clearError(field);
                if (!test()) {
                    setError(field, msg);
                    if (!firstInvalid) firstInvalid = field;
                }
            });

            if (firstInvalid) {
                e.preventDefault();
                const target = $(`[name="${firstInvalid}"]`, form);
                if (target) target.focus();
                return;
            }

            const btn = $('button[type="submit"]', form);
            btn.textContent = 'Sending...';
            // Disable after the submit event so the form still posts
            setTimeout(() => { btn.disabled = true; }, 0);
        });

        // Server-side errors: move focus to the summary
        const summary = $('#quote-errors');
        if (summary) summary.focus();
    }

    const success = $('#quote-success');
    if (success) success.focus();
})();
