// This file is part of Moodle.
// AMD module for block_maristtela: fetch context & insights and render cards.
define(['core/ajax', 'core/templates', 'core/notification', 'core/str'], function(Ajax, Templates, Notification, Str) {

    const SELECTORS = {
        container: '.block-maristtela',
        cards: '.bm-cards',
        ctx: '.bm-context',
        btnToggle: '.bm-togglectx',
        loading: '.bm-loading',
        popout: '.bm-popout'
    };

    const getString = (keys) => Str.get_strings(keys.map(k => ({key: k, component: 'block_maristtela'})));

    const renderCards = async(root, cardsJson) => {
        const cardsEl = root.querySelector(SELECTORS.cards);
        if (!cardsJson || !cardsJson.length) {
            const [nocards] = await getString(['nocards']);
            cardsEl.innerHTML = '<div class="bm-empty">' + nocards + '</div>';
            return;
        }
        cardsEl.innerHTML = '';
        for (const c of cardsJson) {
            let card;
            try {
                card = JSON.parse(c);
            } catch (e) { continue; }
            const html = await Templates.render('block_maristtela/insight_card', card);
            Templates.appendNodeContents(cardsEl, html);
        }
        Templates.runTemplateJS(cardsEl);
    };

    const toggleContext = async(root) => {
        const ctxEl = root.querySelector(SELECTORS.ctx);
        const btn = root.querySelector(SELECTORS.btnToggle);
        const expanded = btn.getAttribute('aria-expanded') === 'true';
        const keys = expanded ? ['showcontext'] : ['hidecontext'];
        const [label] = await getString(keys);
        btn.textContent = label;
        btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        if (expanded) {
            ctxEl.classList.add('is-hidden');
            ctxEl.setAttribute('aria-hidden', 'true');
        } else {
            ctxEl.classList.remove('is-hidden');
            ctxEl.setAttribute('aria-hidden', 'false');
        }
    };

    const fetchContext = (args) => Ajax.call([{
        methodname: 'block_maristtela_get_context',
        args: {contextid: args.contextid, blockid: args.blockid, sesskey: args.sesskey}
    }])[0];

    const fetchInsights = (args) => Ajax.call([{
        methodname: 'block_maristtela_get_insights',
        args: {contextid: args.contextid, blockid: args.blockid, sesskey: args.sesskey}
    }])[0];

    const init = async(args) => {
        try {
            const root = document.querySelector(SELECTORS.container + '[data-blockid="' + args.blockid + '"]');
            if (!root) { return; }

            // Wire buttons
            root.querySelector(SELECTORS.btnToggle).addEventListener('click', () => toggleContext(root));
            root.querySelector(SELECTORS.popout).addEventListener('click', () => {
                window.open(window.location.href, '_blank', 'noopener,noreferrer');
            });

            // Fetch in parallel
            const [ctxResp, cardsResp, strings] = await Promise.all([
                fetchContext(args),
                fetchInsights(args),
                getString(['loading'])
            ]);

            // Put context JSON
            try {
                const ctx = JSON.parse(ctxResp.context);
                const ctxEl = root.querySelector(SELECTORS.ctx);
                ctxEl.textContent = JSON.stringify(ctx, null, 2);
            } catch (e) {}

            // Render cards
            await renderCards(root, cardsResp.cards);

            // Remove loading
            const loadingEl = root.querySelector(SELECTORS.loading);
            if (loadingEl) loadingEl.remove();

        } catch (err) {
            Notification.exception(err);
        }
    };

    const ask = (args, question) => Ajax.call([{methodname:'block_maristtela_ask', args:{contextid:args.contextid, blockid:args.blockid, question: question, sesskey: args.sesskey}}])[0];
    return { init: init, ask: ask };
});
