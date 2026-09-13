panel.plugin('studio/callback', {
    components: {
        'k-studio-callback-inbox': {
            props: { configured: Boolean, items: Array, filters: Array, pagination: Object, status: String },
            render(h) {
                return h('k-panel-inside', [
                    h('k-header', [
                        'Заявки',
                        h('k-button', { slot: 'buttons', props: { icon: 'refresh', text: 'Обновить', variant: 'filled' }, on: { click: () => this.$panel.view.refresh() } }),
                    ]),
                    this.configured ? h('k-tabs', { props: { tab: this.status, tabs: this.filters } }) : null,
                    !this.configured
                        ? h('k-box', { props: { theme: 'info', icon: 'info' } }, ['Приём заявок ещё не настроен. Обратитесь к администратору.'])
                        : h('k-section', { props: { headline: 'Обратные звонки' } }, [
                            h('k-collection', {
                                props: { items: this.items, layout: 'list', pagination: this.pagination, empty: { icon: 'phone', text: 'В этом разделе заявок нет.' }, help: 'Откройте заявку, чтобы изменить статус и записать результат звонка. Время указано в UTC.' },
                                on: { paginate: ({ page }) => this.$panel.open('/studio-callback?status=' + this.status + '&page=' + page) },
                            }),
                        ]),
                ]);
            },
        },
        'k-studio-callback-detail': {
            props: { id: String, revision: Number, fields: Object, values: Object },
            render(h) {
                return h('k-panel-inside', [
                    h('k-header', [
                        'Заявка',
                        h('k-buttons', { slot: 'buttons' }, [
                            h('k-button', { props: { icon: 'angle-left', text: 'К заявкам', link: '/studio-callback', variant: 'filled', responsive: true } }),
                            h('k-button', { props: { icon: 'edit', text: 'Статус и заметка', variant: 'filled' }, on: { click: () => this.$panel.dialog.open('studio-callback/' + this.id + '/update') } }),
                            h('k-button', { props: { icon: 'trash', text: 'Удалить', theme: 'negative', variant: 'filled', responsive: true }, on: { click: () => this.$panel.dialog.open('studio-callback/' + this.id + '/delete/' + this.revision) } }),
                        ]),
                    ]),
                    h('k-section', { props: { headline: 'Данные заявки' } }, [h('k-fieldset', { props: { fields: this.fields, value: this.values, disabled: true } })]),
                ]);
            },
        },
    },
});
