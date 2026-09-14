(() => {
    const clean = (value) => String(value ?? '').replace(/\s+/gu, ' ').trim();
    const enabled = (value) => value === true || value === 'true';
    const coverText = (value) => String(value ?? '').replace(/\r\n?/g, '\n').replace(/[^\S\n]+/gu, ' ').trim();
    const autoCover = (value) => value === undefined || value === null || value === '' || enabled(value);
    const resolve = (content = {}, defaults = {}) => {
        const siteTitle = defaults.isSite
            ? clean(content.seo_title) || clean(content.title)
            : defaults.siteTitle || '';
        const title = defaults.isSite ? defaults.pageTitle : clean(content.seo_title) || clean(content.title) || defaults.pageTitle;
        const description = (defaults.isSite && defaults.homepage?.description) || clean(content.seo_description) || (defaults.isSite ? '' : defaults.description);
        const selected = content.seo_image?.[0];
        const mode = ['cover', 'custom', 'shared'].includes(content.seo_image_mode)
            ? content.seo_image_mode : autoCover(content.seo_generate_image) ? 'cover' : selected?.url ? 'custom' : 'shared';
        const shared = (!defaults.isSite && defaults.siteImage) || defaults.fallbackImage;
        const image = defaults.isSite ? defaults.homepage?.image || (selected?.url ? selected : shared)
            : mode === 'cover' ? null : mode === 'custom' && selected?.url ? selected : shared;
        const coverTitle = coverText(content.seo_og_title) || clean(content.seo_title) || defaults.coverDefault || title;
        return {
            title: title || '', coverTitle, mode, siteTitle, description: description || '',
            homepage: defaults.homepage,
            searchTitle: [title, siteTitle].filter(Boolean).join(' | '), image,
            titleInherited: !defaults.isSite && !clean(content.seo_title),
            descriptionInherited: !defaults.isSite && !clean(content.seo_description),
            imageSource: defaults.homepage?.image ? 'Изображение главной страницы' : !image ? 'Автоматическая обложка' : selected?.url === image.url ? 'Своё изображение' : image.url === defaults.fallbackImage?.url ? 'Фирменная обложка с адресом' : 'Общее изображение сайта',
            noindex: enabled(content.seo_noindex),
        };
    };
    const upstream = panel.plugins.components['k-serp-preview-section'];
    panel.plugin('studio/seo', {
        sections: {
            'studio-search-preview': {
                ...upstream,
                setup(props, context) {
                    const preview = upstream.setup(props, context);
                    const state = Vue.computed(() => resolve(preview.currentContent.value, preview.data.value.studio));
                    return {
                        ...preview,
                        data: Vue.computed(() => ({ ...preview.data.value, siteTitle: state.value.siteTitle })),
                        title: Vue.computed(() => state.value.searchTitle),
                        description: Vue.computed(() => state.value.description),
                        path: Vue.computed(() => preview.data.value.studio?.path || ''),
                        studioState: state,
                    };
                },
                render(h) {
                    const state = this._self._setupProxy.studioState;
                    const length = Array.from(state.searchTitle).length;
                    return h('div', { class: 'studio-search-preview' }, [
                        upstream.render.call(this, h),
                        h('div', { class: 'studio-seo-notes', attrs: { 'aria-live': 'polite' } }, [
                            h('p', { class: { 'studio-seo-warning': length > 60 } }, [
                                `${new Intl.NumberFormat('ru').format(length)} симв. в полном заголовке${length > 60 ? ' · Может обрезаться в поиске' : ''}`,
                            ]),
                            state.titleInherited ? h('p', ['Заголовок: название страницы.']) : null,
                            state.descriptionInherited && state.description ? h('p', ['Описание: из настроек сайта.']) : null,
                            !state.description ? h('p', ['Описание не задано. Поисковая система может выбрать фрагмент страницы.']) : null,
                        ]),
                        h('p', { class: 'studio-seo-note' }, ['Пример выдачи. Поисковые системы могут изменить текст и длину сниппета.']),
                        state.homepage ? h('p', { class: 'studio-seo-note' }, [
                            'Показана главная страница с учётом её собственных настроек.',
                        ]) : null,
                        state.homepage ? h('k-button', { props: { link: state.homepage.settingsUrl, icon: 'edit', variant: 'dimmed' }, class: 'studio-seo-settings' }, ['Изменить заголовок и описание главной']) : null,
                    ]);
                },
            },
            'studio-sharing-preview': {
                props: { parent: String, name: String, timestamp: Number },
                data: () => ({ defaults: null, error: '', generated: '', generating: false, generationError: '', timer: null, requestId: 0, imageAttempt: 0, layout: null, drag: null, placement: null }),
                computed: {
                    content() { return this.$panel.content.version('changes') || {}; },
                    state() { return resolve(this.content, this.defaults || {}); },
                    settings() {
                        const number = (value, fallback) => value !== '' && value != null && Number.isFinite(Number(value)) ? Number(value) : fallback;
                        return { size: number(this.content.seo_og_size, 112), x: number(this.content.seo_og_x, 40), y: number(this.content.seo_og_y, 72) };
                    },
                    generatedKey() { return this.defaults && !this.state.image ? JSON.stringify([this.state.coverTitle, this.settings]) : ''; },
                    editing() { return !this.defaults?.isSite && !this.state.image; },
                    dragStyle() {
                        if (!this.layout) return {};
                        const { x, y, width, height } = this.layout;
                        const position = this.drag || this.placement;
                        const dx = position ? position.x - x : 0;
                        const dy = position ? position.y - y : 0;
                        return { left: `${(x + dx) / 12}%`, top: `${(y + dy) / 6.3}%`, width: `${Math.max(width, 32) / 12}%`, height: `${Math.max(height, 32) / 6.3}%` };
                    },
                    imageUrl() { return this.state.image?.url || this.generated; },
                    indexStatus() {
                        if (!this.defaults?.publiclyVisible) return 'Страница закрыта для посетителей и поисковых систем.';
                        if (this.state.noindex) return 'Индексация запрещена настройкой этой страницы.';
                        if (!this.defaults?.environmentIndexable) return 'На этой версии сайта индексация отключена. Настройка страницы вступит в силу на основном сайте.';
                        return 'Индексация разрешена. Поисковая система сама решает, когда добавить страницу в выдачу.';
                    },
                },
                watch: {
                    generatedKey: { immediate: true, handler() { this.scheduleGeneration(); } },
                    timestamp() { this.load(); },
                },
                created() { this.load(); },
                beforeDestroy() { clearTimeout(this.timer); this.requestId++; },
                methods: {
                    changeSettings(values) {
                        this.$panel.content.updateLazy(Object.fromEntries(Object.entries(values).map(([key, value]) => [`seo_og_${key}`, value])));
                    },
                    startDrag(event) {
                        if (!this.layout || this.generating || event.button !== 0) return;
                        event.preventDefault();
                        event.currentTarget.focus();
                        event.currentTarget.setPointerCapture(event.pointerId);
                        const bounds = event.currentTarget.parentElement.getBoundingClientRect();
                        this.drag = { startX: event.clientX, startY: event.clientY, scale: 1200 / bounds.width, x: this.layout.x, y: this.layout.y };
                    },
                    moveDrag(event) {
                        if (!this.drag) return;
                        this.drag.x = Math.round(Math.max(24, Math.min(1176 - this.layout.width, this.layout.x + (event.clientX - this.drag.startX) * this.drag.scale)));
                        this.drag.y = Math.round(Math.max(24, Math.min(320 - this.layout.height, this.layout.y + (event.clientY - this.drag.startY) * this.drag.scale)));
                    },
                    endDrag(event) {
                        if (!this.drag) return;
                        const { x, y } = this.drag;
                        this.placement = { x, y };
                        this.drag = null;
                        if (event.currentTarget.hasPointerCapture(event.pointerId)) event.currentTarget.releasePointerCapture(event.pointerId);
                        this.changeSettings({ x, y });
                    },
                    moveWithKeys(event) {
                        const directions = { ArrowLeft: [-1, 0], ArrowRight: [1, 0], ArrowUp: [0, -1], ArrowDown: [0, 1] };
                        if (!directions[event.key] || !this.layout || this.generating) return;
                        event.preventDefault();
                        const step = event.shiftKey ? 10 : 1;
                        const [dx, dy] = directions[event.key];
                        this.changeSettings({
                            x: Math.max(24, Math.min(1176 - this.layout.width, this.layout.x + dx * step)),
                            y: Math.max(24, Math.min(320 - this.layout.height, this.layout.y + dy * step)),
                        });
                    },
                    retryImage() {
                        this.generationError = '';
                        if (this.state.image) this.imageAttempt++;
                        else this.scheduleGeneration();
                    },
                    async load() {
                        try {
                            const response = await this.$api.get(`${this.parent}/sections/${this.name}`);
                            this.defaults = response.studio;
                            this.error = '';
                        } catch {
                            this.error = 'Не удалось загрузить предпросмотр. Повторите попытку.';
                        }
                    },
                    scheduleGeneration() {
                        clearTimeout(this.timer);
                        const requestId = ++this.requestId;
                        this.generationError = '';
                        if (!this.generatedKey) { this.generating = false; return; }
                        this.generating = true;
                        this.timer = setTimeout(async () => {
                            try {
                                const result = await this.$api.post('studio-seo/og-preview', { title: this.state.coverTitle, settings: this.settings });
                                if (!result?.url || !result?.layout) throw new Error('Invalid image preview');
                                if (requestId === this.requestId) { this.generated = result.url; this.layout = result.layout; this.placement = null; }
                            } catch {
                                if (requestId === this.requestId) this.generationError = 'Не удалось создать обложку. Повторите попытку или выберите своё изображение.';
                            } finally {
                                if (requestId === this.requestId) this.generating = false;
                            }
                        }, 350);
                    },
                },
                render(h) {
                    if (this.error) return h('k-box', { props: { theme: 'negative' } }, [this.error, h('k-button', { on: { click: this.load } }, ['Повторить'])]);
                    if (!this.defaults) return h('p', { attrs: { role: 'status' } }, ['Загрузка предпросмотра…']);
                    const state = this.state;
                    return h('div', { class: 'studio-sharing-preview' }, [
                        h('k-section', { props: { label: this.defaults.isSite ? 'Главная в соцсетях и мессенджерах' : 'В соцсетях и мессенджерах' } }, [
                            h('figure', { class: 'studio-social-card', attrs: { 'aria-label': 'Предпросмотр ссылки в соцсетях', 'aria-busy': String(this.generating) } }, [
                                h('div', { class: 'studio-social-card-image' }, [
                                    this.imageUrl ? h('img', { key: this.imageUrl + this.imageAttempt, attrs: { src: this.editing && (this.drag || this.placement) ? this.defaults.templateImage : this.imageUrl, alt: state.image?.alt || `Обложка: ${state.title}`, width: 1200, height: 630 }, on: { error: () => { this.generationError = 'Изображение недоступно. Выберите другой файл или обновите предпросмотр.'; } } }) : null,
                                    this.editing && this.layout && (this.drag || this.placement) ? h('img', {
                                        class: 'studio-og-moving-text',
                                        attrs: { src: this.imageUrl, alt: '', 'aria-hidden': 'true' },
                                        style: {
                                            clipPath: `inset(${this.layout.y / 6.3}% ${(1200 - this.layout.x - this.layout.width - 1) / 12}% ${(630 - this.layout.y - this.layout.height - 1) / 6.3}% ${this.layout.x / 12}%)`,
                                            transform: `translate(${((this.drag || this.placement).x - this.layout.x) / 12}%, ${((this.drag || this.placement).y - this.layout.y) / 6.3}%)`,
                                        },
                                    }) : null,
                                    this.editing && this.layout && !this.generationError ? h('button', {
                                        class: ['studio-og-drag', { 'is-dragging': Boolean(this.drag) }], style: this.dragStyle,
                                        attrs: { type: 'button', 'aria-label': 'Положение текста: перетащите или используйте стрелки', 'aria-describedby': `og-help-${this.name}`, 'aria-disabled': String(this.generating) },
                                        on: { pointerdown: this.startDrag, pointermove: this.moveDrag, pointerup: this.endDrag, pointercancel: () => { this.drag = null; }, lostpointercapture: () => { this.drag = null; }, keydown: this.moveWithKeys },
                                    }, [h('span', { class: 'studio-og-drag-label', attrs: { 'aria-hidden': 'true' } }, ['Перетащите текст'])]) : null,
                                    !this.imageUrl ? h('span', [this.generating ? 'Создаём обложку…' : 'Обложка недоступна']) : null,
                                ]),
                                h('figcaption', [
                                    h('p', { class: 'studio-social-domain' }, [this.defaults.origin.replace(/^https?:\/\//, '')]),
                                    h('h3', [state.title]),
                                    state.description ? h('p', { class: 'studio-social-description' }, [state.description]) : null,
                                ]),
                            ]),
                            this.editing ? h('div', { class: 'studio-og-controls' }, [
                                h('div', { class: 'studio-og-controls-heading' }, [
                                    h('label', { attrs: { for: `og-size-${this.name}` } }, ['Размер текста']),
                                    h('output', { attrs: { for: `og-size-${this.name}` } }, [`${this.settings.size} px`]),
                                    h('k-button', { props: { icon: 'undo', size: 'xs' }, on: { click: () => this.changeSettings({ size: 112, x: 40, y: 72 }) } }, ['Сбросить']),
                                ]),
                                h('input', { class: 'studio-og-size', attrs: { id: `og-size-${this.name}`, type: 'range', min: 48, max: 144, step: 1 }, domProps: { value: this.settings.size }, on: { input: (event) => this.changeSettings({ size: Number(event.target.value) }) } }),
                                h('p', { class: 'studio-seo-note', attrs: { id: `og-help-${this.name}` } }, ['Перетащите текст на обложке. Стрелки - 1 px, Shift + стрелки - 10 px.']),
                                this.layout && this.layout.size < this.settings.size ? h('p', { class: 'studio-seo-note' }, [`Для длинного текста размер уменьшен до ${this.layout.size} px, чтобы сохранить отступ от логотипа.`]) : null,
                                this.layout?.truncated ? h('p', { class: 'studio-seo-warning' }, ['Текст не помещается целиком. Сократите текст обложки.']) : null,
                            ]) : null,
                            h('div', { class: 'studio-seo-notes', attrs: { 'aria-live': 'polite' } }, [
                                state.mode === 'custom' && !this.content.seo_image?.[0]?.url && !this.defaults.isSite ? h('p', ['Выберите файл обложки. Сейчас показана общая обложка.']) : null,
                                h('p', [this.generating ? 'Обновляем обложку…' : `Изображение: ${state.imageSource.toLowerCase()}.`]),
                                this.generationError ? h('p', { class: 'studio-seo-warning' }, [this.generationError, ' ', h('k-button', { on: { click: this.retryImage } }, ['Повторить'])]) : null,
                            ]),
                            h('p', { class: 'studio-seo-note' }, ['Пример карточки. Обрезка картинки и длина текста зависят от приложения.']),
                        ]),
                        !this.defaults.isSite ? h('k-section', { props: { label: 'Сейчас в поиске' } }, [
                            h('k-box', { props: { theme: state.noindex || !this.defaults.publiclyVisible ? 'notice' : 'info', icon: 'info' }, attrs: { 'aria-live': 'polite' } }, [this.indexStatus]),
                            h('k-button', { props: { link: this.defaults.settingsUrl, icon: 'cog', variant: 'dimmed' }, class: 'studio-seo-settings' }, ['Общие настройки сайта']),
                        ]) : h('p', { class: 'studio-seo-note' }, ['Показана главная страница. Общие настройки используются, если у неё не заданы собственные.']),
                    ]);
                },
            },
        },
    });
})();
