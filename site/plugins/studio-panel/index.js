(() => {
    const locator = window.panel.plugins.components['k-locator-field'];
    const leaflet = window.L;
    if (!locator || !leaflet?.TileLayer) return;

    // Configure tile requests before Locator creates its first image.
    // Panel's stricter global referrer policy remains unchanged.
    leaflet.TileLayer.mergeOptions({ referrerPolicy: 'strict-origin-when-cross-origin' });

    panel.plugin('studio/locator-requests', {
        fields: {
            locator: {
                extends: locator,
                computed: {
                    tileUrl() {
                        if (this.tiles === 'openstreetmap') {
                            return 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
                        }
                        return locator.computed.tileUrl.call(this);
                    },
                    searchQuery() {
                        const url = locator.computed.searchQuery.call(this);
                        return this.geocoding === 'nominatim'
                            ? new Request(url, { referrerPolicy: 'strict-origin-when-cross-origin' })
                            : url;
                    },
                },
            },
        },
    });
})();

panel.plugin('studio/panel', {
    components: {
        'k-studio-guide-view': {
            props: {
                title: String,
                environment: Object,
                links: Array,
                sections: Array,
            },
            render(h) {
                return h('k-panel-inside', [
                    h('k-header', [this.title]),
                    h('k-section', { props: { headline: this.environment.label } }, [
                        h('k-box', { props: { theme: this.environment.theme, icon: 'info' } }, [
                            h('k-text', [this.environment.text]),
                        ]),
                    ]),
                    h('k-section', { props: { headline: 'Быстрый переход' } }, [
                        h('k-buttons', this.links.map((link) => h('k-button', {
                            key: link.link,
                            props: { ...link, variant: 'filled' },
                        }))),
                    ]),
                    ...this.sections.map((section) => h('k-section', {
                        key: section.title,
                        props: { headline: section.title },
                    }, [h('k-text', { props: { html: section.text } })])),
                ]);
            },
        },
    },
});
