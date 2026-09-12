document.querySelectorAll('[data-callback-form]').forEach((form) => {
    const status = form.querySelector('[data-callback-status]');
    const button = form.querySelector('button[type="submit"]');
    const originalLabel = button.textContent;

    const clearErrors = () => {
        form.querySelectorAll('[aria-invalid]').forEach((input) => input.removeAttribute('aria-invalid'));
        form.querySelectorAll('[data-error-for]').forEach((error) => {
            error.textContent = '';
            error.hidden = true;
        });
    };

    if (!status.hidden && window.location.hash === `#${form.id}`) {
        (form.querySelector('[aria-invalid="true"]') || status).focus();
    }

    form.addEventListener('submit', async (event) => {
        if (!window.fetch) return;
        event.preventDefault();
        if (form.getAttribute('aria-busy') === 'true') return;

        clearErrors();
        button.disabled = true;
        button.textContent = 'Отправляем…';
        form.setAttribute('aria-busy', 'true');
        status.textContent = 'Отправляем заявку…';
        status.hidden = false;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            const result = await response.json();
            if (typeof result.message !== 'string' || typeof result.ok !== 'boolean') {
                throw new Error('Unexpected callback response');
            }
            status.textContent = result.message;
            Object.entries(result.errors || {}).forEach(([name, message]) => {
                const input = form.elements.namedItem(name);
                const error = form.querySelector(`[data-error-for="${CSS.escape(name)}"]`);
                if (input && error) {
                    input.setAttribute('aria-invalid', 'true');
                    error.textContent = message;
                    error.hidden = false;
                }
            });
            if (result.ok) form.reset();
            (form.querySelector('[aria-invalid="true"]') || status).focus();
        } catch {
            status.textContent = 'Не удалось получить ответ. Заявка могла быть отправлена. Позвоните нам, чтобы уточнить.';
            status.focus();
        } finally {
            form.removeAttribute('aria-busy');
            button.disabled = false;
            button.textContent = originalLabel;
        }
    });
});
