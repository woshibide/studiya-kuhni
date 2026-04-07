(() => {
	const root = document.documentElement;
	const enabledClass = 'is-debug-grid';

	const isEditableTarget = (target) => {
		if (!(target instanceof Element)) return false;
		if (target.closest('[contenteditable="true"]')) return true;

		const tagName = target.tagName?.toLowerCase();
		if (!tagName) return false;

		return tagName === 'input' || tagName === 'textarea' || tagName === 'select';
	};

	const toggleDebugGrid = () => {
		root.classList.toggle(enabledClass);
	};

	document.addEventListener('keydown', (event) => {
		if (event.defaultPrevented) return;
		if (event.repeat) return;
		if (event.metaKey || event.ctrlKey || event.altKey) return;
		if (isEditableTarget(event.target)) return;

		const key = (event.key || '').toLowerCase();
		if (key !== 'd') return;

		toggleDebugGrid();
	});
})();