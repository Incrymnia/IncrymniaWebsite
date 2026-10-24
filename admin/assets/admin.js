/* Incrymnia admin: small enhancements. The forms work without this file, except add/remove of list rows. */
(() => {
    'use strict';

    const $ = (sel, root = document) => root.querySelector(sel);
    const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

    /* ---------- Confirm before destructive actions ---------- */
    document.addEventListener('submit', (e) => {
        const msg = e.target.getAttribute && e.target.getAttribute('data-confirm');
        if (msg && !window.confirm(msg)) e.preventDefault();
    });

    /* ---------- Repeating lists ---------- */
    $$('[data-repeater]').forEach((rep) => {
        const list = $('[data-items]', rep);
        const tpl = $('template[data-template]', rep);
        const addBtn = $('[data-add]', rep);
        const max = parseInt(rep.dataset.max || '50', 10);
        const reorderOnly = rep.hasAttribute('data-reorder-only');
        let next = list.children.length;
        let uid = 0;

        const items = () => Array.from(list.children).filter((n) => n.matches('[data-item]'));

        const refresh = () => {
            const all = items();
            all.forEach((item, i) => {
                const up = $('[data-move="up"]', item);
                const down = $('[data-move="down"]', item);
                if (up) up.disabled = i === 0;
                if (down) down.disabled = i === all.length - 1;
                if (!reorderOnly) {
                    const label = $('.rep-item__label', item);
                    const base = label.dataset.base || label.textContent.replace(/\s+\d+$/, '');
                    label.dataset.base = base;
                    label.textContent = base + ' ' + (i + 1);
                }
            });
            if (addBtn) addBtn.disabled = all.length >= max;
        };

        if (addBtn && tpl) {
            addBtn.addEventListener('click', () => {
                if (items().length >= max) return;
                list.insertAdjacentHTML('beforeend', tpl.innerHTML.split('__i__').join(String(next)));
                next += 1;
                uid += 1;
                const item = items().pop();
                // ids must stay unique, so suffix them and point labels at the new ids
                $$('[id]', item).forEach((el) => {
                    const old = el.id;
                    el.id = old + '-n' + uid + '-' + next;
                    const lab = $('label[for="' + old + '"]', item);
                    if (lab) lab.htmlFor = el.id;
                });
                refresh();
                const first = $('input, textarea, select', item);
                if (first) first.focus();
            });
        }

        rep.addEventListener('click', (e) => {
            const btn = e.target.closest('button');
            if (!btn || !rep.contains(btn)) return;
            const item = btn.closest('[data-item]');
            if (!item) return;

            if (btn.hasAttribute('data-remove')) {
                const msg = btn.getAttribute('data-confirm');
                if (msg && !window.confirm(msg)) return;
                item.remove();
                refresh();
            } else if (btn.dataset.move === 'up' && item.previousElementSibling) {
                list.insertBefore(item, item.previousElementSibling);
                refresh();
                btn.focus();
            } else if (btn.dataset.move === 'down' && item.nextElementSibling) {
                list.insertBefore(item.nextElementSibling, item);
                refresh();
                btn.focus();
            }
        });

        refresh();
    });

    /* ---------- Character counters ---------- */
    $$('[data-counter]').forEach((input) => {
        const limit = parseInt(input.dataset.counter, 10);
        const out = document.createElement('div');
        out.className = 'a-counter';
        out.setAttribute('aria-hidden', 'true');
        input.insertAdjacentElement('afterend', out);
        const update = () => {
            out.textContent = input.value.length + ' / ' + limit;
            out.classList.toggle('is-near', input.value.length > limit * 0.9);
        };
        input.addEventListener('input', update);
        update();
    });

    /* ---------- Live Google preview ---------- */
    $$('[data-preview]').forEach((input) => {
        const out = $('[data-preview-out="' + input.dataset.preview + '"]');
        if (!out) return;
        const update = () => {
            const v = input.value.trim();
            out.textContent = input.dataset.preview === 'title' ? v.slice(0, 65) : v.slice(0, 160);
        };
        input.addEventListener('input', update);
    });

    /* ---------- Password strength ---------- */
    $$('[data-strength]').forEach((input) => {
        const hint = $('[data-strength-out]', input.parentElement);
        if (!hint) return;
        const bar = document.createElement('div');
        bar.className = 'a-strength';
        bar.innerHTML = '<span></span>';
        input.insertAdjacentElement('afterend', bar);
        const fill = $('span', bar);
        const original = hint.textContent;

        input.addEventListener('input', () => {
            const v = input.value;
            if (!v) { fill.style.width = '0'; hint.textContent = original; return; }
            const classes = [/[a-z]/, /[A-Z]/, /[0-9]/, /[^A-Za-z0-9]/].filter((r) => r.test(v)).length;
            const unique = new Set(v.toLowerCase()).size;
            let score = Math.min(v.length, 24) * 3 + classes * 6 + Math.min(unique, 14) * 2;
            if (v.length < 12) score = Math.min(score, 35);
            const pct = Math.max(8, Math.min(100, score));
            const label = v.length < 12 ? 'Too short' : pct < 70 ? 'Okay' : pct < 90 ? 'Strong' : 'Very strong';
            fill.style.width = pct + '%';
            fill.style.background = v.length < 12 ? '#ff6b6b' : pct < 70 ? '#ffb454' : '#4cd18a';
            hint.textContent = label + (v.length < 12 ? ' (need at least 12 characters)' : '');
        });
    });

    /* ---------- Focus the error summary after a failed save ---------- */
    const errors = $('#form-errors');
    if (errors) {
        errors.focus();
        errors.scrollIntoView({ block: 'center' });
    }
})();
