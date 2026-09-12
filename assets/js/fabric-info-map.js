(() => {
    const elements = document.querySelectorAll('[data-fabric-map]');
    if (!elements.length) return;

    const initMap = (element) => {
        if (element.dataset.mapReady) return;
        const status = element.closest('.fabric-info__map-wrap')?.querySelector('[data-fabric-map-status]');
        const setStatus = (message) => { if (status) status.textContent = message; };
        const latText = element.dataset.lat?.trim() ?? '';
        const lngText = element.dataset.lng?.trim() ?? '';
        const lat = Number(latText);
        const lng = Number(lngText);
        const parsedMaxZoom = Number(element.dataset.maxZoom ?? 14);
        const maxZoom = Number.isInteger(parsedMaxZoom) && parsedMaxZoom >= 0 && parsedMaxZoom <= 19 ? parsedMaxZoom : 14;
        const parsedZoom = Number(element.dataset.zoom);
        const zoom = Number.isInteger(parsedZoom) ? Math.min(maxZoom, Math.max(0, parsedZoom)) : Math.min(13, maxZoom);
        const label = element.dataset.label?.trim() || 'Фабрика';
        if (!latText || !lngText || !Number.isFinite(lat) || !Number.isFinite(lng) || Math.abs(lat) > 90 || Math.abs(lng) > 180) {
            element.dataset.mapReady = 'error';
            setStatus('Расположение фабрики пока не указано.');
            return;
        }
        if (!window.L) {
            element.dataset.mapReady = 'error';
            setStatus('Не удалось загрузить карту.');
            return;
        }

        let map;
        let timer;
        try {
            const L = window.L;
            const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
            const motionPaused = () => reducedMotion.matches || Boolean(window.studioMotion?.paused);
            // Leaflet keyboard, popup auto-pan and queued drag inertia all use panBy.
            const MotionAwareMap = L.Map.extend({
                panBy(offset, options = {}) {
                    return L.Map.prototype.panBy.call(this, offset, motionPaused() ? { ...options, animate: false } : options);
                },
            });
            map = new MotionAwareMap(element, {
                scrollWheelZoom: false,
                keyboard: true,
                inertia: !motionPaused(),
                // Leaflet caches these animation capabilities when the map is created.
                zoomAnimation: false,
                fadeAnimation: false,
                markerZoomAnimation: false,
                zoomControl: false,
                attributionControl: true,
                minZoom: 0,
                maxZoom,
            }).setView([lat, lng], zoom);
            const syncMotion = () => {
                const paused = motionPaused();
                map.options.inertia = !paused;
                if (paused) map.stop();
            };
            const unsubscribeMotion = window.studioMotion?.subscribe(syncMotion);
            reducedMotion.addEventListener('change', syncMotion);
            if (!window.studioMotion) syncMotion();

            const onControlKeyDown = (event) => {
                if (![' ', 'Spacebar'].includes(event.key) || event.altKey || event.ctrlKey || event.metaKey) return;
                const control = event.target.closest?.('.leaflet-control-zoom a[role="button"], .leaflet-marker-icon[role="button"], .leaflet-popup-close-button[role="button"]');
                if (!control || !element.contains(control)) return;
                event.preventDefault();
                event.stopPropagation();
                if (!event.repeat && control.getAttribute('aria-disabled') !== 'true') control.click();
            };
            element.addEventListener('keydown', onControlKeyDown, true);
            map.on('unload', () => {
                unsubscribeMotion?.();
                reducedMotion.removeEventListener('change', syncMotion);
                element.removeEventListener('keydown', onControlKeyDown, true);
            });
            L.control.zoom({
                zoomInTitle: 'Увеличить масштаб',
                zoomOutTitle: 'Уменьшить масштаб',
            }).addTo(map);

            const tiles = L.tileLayer(element.dataset.tileUrl || 'https://tiles.maps.eox.at/wmts/1.0.0/s2cloudless_3857/default/g/{z}/{y}/{x}.jpg', {
                minZoom: 0,
                maxZoom,
                referrerPolicy: 'strict-origin-when-cross-origin',
                attribution: element.dataset.attribution || '<a href="https://cloudless.eox.at/">EOxCloudless</a> by <a href="https://eox.at/">EOX IT Services GmbH</a> (Contains modified Copernicus Sentinel data 2016) · <a href="https://creativecommons.org/licenses/by/4.0/">CC BY 4.0</a> · <a href="https://maps.eox.at/">EOX::Maps</a>',
            });
            let failedTiles = 0;
            let loadedTiles = 0;
            const startLoading = () => {
                failedTiles = 0;
                loadedTiles = 0;
                window.clearTimeout(timer);
                setStatus('Карта загружается…');
                timer = window.setTimeout(() => {
                    setStatus('Карта загружается медленно.');
                }, 10000);
            };
            tiles.on('loading', startLoading);
            tiles.on('tileload', () => { loadedTiles += 1; });
            tiles.on('tileerror', () => { failedTiles += 1; });
            tiles.on('load', () => {
                window.clearTimeout(timer);
                if (!loadedTiles) setStatus('Не удалось загрузить карту.');
                else if (failedTiles) setStatus('Карта загрузилась частично.');
                else setStatus('');
            });
            startLoading();
            tiles.addTo(map);

            map.on('popupopen', (event) => {
                const closeButton = event.popup.getElement()?.querySelector('.leaflet-popup-close-button');
                closeButton?.setAttribute('aria-label', 'Закрыть описание');
                closeButton?.setAttribute('title', 'Закрыть описание');
            });
            const popup = document.createElement('span');
            popup.textContent = label;
            const markerIcon = L.divIcon({
                className: 'fabric-info__map-marker',
                html: '<span aria-hidden="true"></span>',
                iconSize: [44, 44],
                iconAnchor: [22, 22],
                popupAnchor: [0, -16],
            });
            L.marker([lat, lng], { icon: markerIcon, title: label, alt: label, keyboard: true }).addTo(map).bindPopup(popup);
            element.dataset.mapReady = 'true';
            if (typeof ResizeObserver === 'function') {
                new ResizeObserver(() => map.invalidateSize({ pan: false })).observe(element);
            }
        } catch (_) {
            window.clearTimeout(timer);
            map?.remove();
            element.dataset.mapReady = 'error';
            setStatus('Не удалось загрузить карту.');
        }
    };

    if (typeof IntersectionObserver === 'function') {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                observer.unobserve(entry.target);
                initMap(entry.target);
            });
        });
        elements.forEach((element) => observer.observe(element));
    } else {
        elements.forEach(initMap);
    }
})();
