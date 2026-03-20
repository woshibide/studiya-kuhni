(() => {
    const mapElements = document.querySelectorAll('[data-fabric-map]');

    if (!mapElements.length) {
        return;
    }

    const initMaps = () => {
        if (!window.L) {
            return false;
        }

        mapElements.forEach((element) => {
            if (element.dataset.mapReady === 'true') {
                return;
            }

            const lat = Number.parseFloat(element.dataset.lat || '0');
            const lng = Number.parseFloat(element.dataset.lng || '0');
            const zoom = Number.parseInt(element.dataset.zoom || '13', 5);
            const label = (element.dataset.label || '').trim();

            if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
                return;
            }

            const map = L.map(element, {
                zoomControl: false,
                scrollWheelZoom: false,
                attributionControl: true,
            }).setView([lat, lng], Number.isFinite(zoom) ? zoom : 13);

            L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                maxZoom: 40,
                subdomains: 'abcd',
                attribution: '&copy; OpenStreetMap contributors &copy; CARTO',
            }).addTo(map);

            const marker = L.marker([lat, lng]).addTo(map);
            if (label !== '') {
                marker.bindPopup(label);
            }

            element.dataset.mapReady = 'true';
            setTimeout(() => map.invalidateSize(), 120);
        });

        return true;
    };

    if (initMaps()) {
        return;
    }

    const waitId = window.setInterval(() => {
        if (initMaps()) {
            window.clearInterval(waitId);
        }
    }, 100);

    window.setTimeout(() => {
        window.clearInterval(waitId);
    }, 6000);
})();
