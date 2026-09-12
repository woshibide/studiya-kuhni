panel.plugin('studio/site', {
    components: {
        'k-studio-tiptap-field-preview': { extends: 'k-tiptap-field-preview' }
    },
    fields: {
        'studio-tiptap': {
            extends: 'k-tiptap-field',
            mounted() {
                this.labelEditor();
            },
            watch: {
                editor() {
                    this.labelEditor();
                },
                label() {
                    this.labelEditor();
                }
            },
            methods: {
                labelEditor() {
                    this.$nextTick(() => {
                        const editor = this.editor?.view?.dom || this.$el.querySelector('[contenteditable="true"]');
                        if (!editor) return;
                        editor.setAttribute('role', 'textbox');
                        editor.setAttribute('aria-multiline', 'true');
                        editor.setAttribute('aria-label', this.label || 'Текст');
                    });
                }
            }
        }
    }
});
