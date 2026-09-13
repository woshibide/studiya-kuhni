const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const [bundlePath, manifestPath, root] = process.argv.slice(2);
const bundle = fs.readFileSync(bundlePath, 'utf8');
const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
let checks = 0;
const check = (condition, message) => {
    assert.ok(condition, message);
    checks += 1;
};

async function main() {
    // Use the shipped Vue exports and Kirby registry, not a mock plugin registry.
    // DOM stubs support library initialization only; this is not a browser render test.
    const vueSource = fs.readFileSync(path.join(root, 'kirby/panel/dist/js/vue.esm.browser.min.js'), 'utf8');
    const vue = await import(`data:text/javascript;base64,${Buffer.from(vueSource).toString('base64')}`);
    const requestedImages = [];
    const element = (tagName) => {
        const node = {
            style: {},
            childNodes: [],
            setAttribute() {},
            removeAttribute() {},
            addEventListener() {},
            removeEventListener() {},
            appendChild(child) { this.childNodes.push(child); return child; },
            getContext() { return null; },
        };
        if (tagName === 'img') {
            Object.defineProperty(node, 'src', {
                get() { return this.imageUrl; },
                set(url) {
                    this.imageUrl = url;
                    requestedImages.push({ url, referrerPolicy: this.referrerPolicy });
                },
            });
        }
        return node;
    };
    const iconDefinitions = { innerHTML: '' };
    const referrerMeta = { content: 'same-origin' };
    const document = {
        documentElement: element(),
        createElement: element,
        createElementNS: element,
        querySelector(selector) {
            if (selector === 'svg defs') return iconDefinitions;
            if (selector === 'meta[name=referrer]') return referrerMeta;
            return null;
        },
        addEventListener() {},
        removeEventListener() {},
        defaultView: { getComputedStyle: () => ({}) },
    };
    const context = vm.createContext({
        ...vue,
        Vue: vue.default,
        console,
        Request,
        document,
        navigator: { userAgent: 'Panel asset unit test', platform: 'Linux', maxTouchPoints: 0 },
        screen: { deviceXDPI: 96, logicalXDPI: 96 },
        setTimeout,
        clearTimeout,
        addEventListener() {},
        removeEventListener() {},
        devicePixelRatio: 1,
    });
    context.window = context;
    context.self = context;
    let registry = fs.readFileSync(path.join(root, 'kirby/panel/dist/js/plugins.js'), 'utf8');
    const importPattern = /^import Vue,\s*\{[\s\S]*?\}\s*from "vue";/;
    check(importPattern.test(registry), 'Registry imports must match the supplied real Vue exports');
    registry = registry.replace(importPattern, '');
    new vm.Script(registry, { filename: 'kirby-panel-plugin-registry.js' }).runInContext(context);
    const registrations = [];
    const register = context.panel.plugin;
    context.panel.plugin = (name, extensions) => {
        registrations.push(name);
        register(name, extensions);
    };
    new vm.Script(bundle, { filename: 'assembled-panel-plugins.js' }).runInContext(context);

    const components = context.panel.plugins.components;
    for (const name of ['k-tiptap-field', 'k-studio-tiptap-field', 'k-studio-features-field', 'k-studio-tiptap-field-preview', 'k-studio-library-field', 'k-locator-field', manifest.guide.component]) {
        check(components[name], `Assembled bundle must register ${name}`);
    }
    check(components['k-studio-tiptap-field'].extends === 'k-tiptap-field', 'Studio editor inherits the registered official Tiptap component');
    for (const name of ['k-serp-preview-section', 'k-studio-search-preview-section', 'k-studio-sharing-preview-section']) {
        check(typeof components[name]?.render === 'function', `SEO preview registers ${name}`);
    }
    check(registrations.indexOf('johannschopplich/serp-preview') < registrations.indexOf('studio/seo'), 'Official SERP preview registers before site adapter');
    check(registrations.indexOf('medienbaecker/tiptap') < registrations.indexOf('studio/site'), 'Official editor registers before its adapter');
    check(typeof components['k-tiptap-field'].render === 'function', 'Official editor has its compiled render function');
    check(typeof components[manifest.guide.component].render === 'function', 'Guide view has a client renderer');
    const vnode = (tag, data, children) => ({ tag, data, children });
    const guide = components[manifest.guide.component].render.call(manifest.guide.props, vnode);
    check(guide.tag === 'k-panel-inside', 'Registered guide renders the actual PHP response without throwing');

    check(components['k-studio-features-field'].extends === 'k-multiselect-field', 'Feature selector uses native Kirby multiselect');
    check(components['k-studio-library-field'].extends === 'k-structure-field', 'Library uses native Kirby structure');
    check(components['k-studio-tiptap-field-preview'].extends === 'k-tiptap-field-preview', 'Structure prose uses official rich text preview');

    const locator = components['k-locator-field'];
    const originalLocator = locator.extends;
    check(typeof originalLocator?.methods?.initMap === 'function', 'Locator adapter inherits the actual official component object');
    check(registrations.indexOf('sylvainjule/locator') < registrations.indexOf('studio/locator-requests'), 'Locator registers before request configuration');
    const tileUrl = locator.computed.tileUrl.call({ tiles: 'openstreetmap' });
    check(tileUrl === 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', 'OSM uses the canonical tile server without legacy subdomains');
    const layer = context.L.tileLayer(tileUrl);
    layer._tileZoom = 4;
    const tile = layer.createTile({ x: 8, y: 5 }, () => {});
    const firstTileRequest = requestedImages.at(-1);
    check(tile.referrerPolicy === 'strict-origin-when-cross-origin', 'Actual bundled Leaflet tile has the scoped referrer policy');
    check(firstTileRequest.referrerPolicy === 'strict-origin-when-cross-origin', 'Tile referrer policy is set before assigning its src');
    check(firstTileRequest.url === 'https://tile.openstreetmap.org/4/8/5.png', 'Actual tile requests the canonical OSM URL');
    for (const provider of ['mapbox', 'mapbox.custom', 'wikimedia', 'light_all', 'voyager']) {
        const state = { tiles: provider, mapbox: { id: 'example/style', token: 'fixture' } };
        check(locator.computed.tileUrl.call(state) === originalLocator.computed.tileUrl.call(state), `${provider} keeps its original provider URL`);
    }
    const search = locator.computed.searchQuery.call({ geocoding: 'nominatim', language: false, location: 'Pesaro Italia' });
    check(search instanceof Request && search.url.startsWith('https://nominatim.openstreetmap.org/search?'), 'Nominatim search uses a native Request with its original endpoint');
    check(new Request(search, {}).referrerPolicy === 'strict-origin-when-cross-origin', 'Native fetch Request handling retains the geocoding referrer policy');
    let submittedSearch;
    let receivedCoordinates;
    context.fetch = (input, init) => {
        submittedSearch = new Request(input, init);
        return Promise.resolve({ json: () => Promise.resolve([{ lat: '43.91', lon: '12.91', address: { city: 'Pesaro', country: 'Italia' } }]) });
    };
    originalLocator.methods.getCoordinates.call({
        $refs: { dialog: { open() {} } },
        $t: (key) => key,
        $emit(event, value) { if (event === 'input') receivedCoordinates = value; },
        geocoding: 'nominatim',
        location: 'Pesaro Italia',
        searchQuery: search,
        isLatLon: () => false,
        setNominatimResponse: originalLocator.methods.setNominatimResponse,
    });
    await new Promise(setImmediate);
    check(submittedSearch?.referrerPolicy === 'strict-origin-when-cross-origin', 'Official Locator search method forwards the scoped Request to fetch');
    check(receivedCoordinates?.lat === 43.91 && receivedCoordinates?.city === 'Pesaro', 'Official geocoding response handling emits parsed location data');
    const mapboxSearchState = { geocoding: 'mapbox', language: false, location: 'Pesaro', limit: 1, mapbox: { token: 'fixture' } };
    check(locator.computed.searchQuery.call(mapboxSearchState) === originalLocator.computed.searchQuery.call(mapboxSearchState), 'Other geocoding providers keep their original request value');
    check(referrerMeta.content === 'same-origin', 'Panel global referrer policy stays unchanged');

    const scripts = Object.keys(manifest.scripts);
    check(scripts.indexOf('plugin-registry') < scripts.indexOf('plugins'), 'Document places the registry before plugin scripts');
    check(scripts.indexOf('plugins') < scripts.indexOf('index'), 'Document places plugins before Panel initialization');
    check(manifest.scripts.plugins.src.endsWith(`?${manifest.modified}`), 'Plugin JavaScript URL carries native cache revision');
    check(manifest.css.plugins.endsWith(`?${manifest.modified}`), 'Plugin CSS URL carries native cache revision');
    check(manifest.scripts.plugins.defer === true, 'Plugin bundle preserves document execution order');

    // A parser-only test would miss this failure: one broken plugin prevents all
    // later registrations in the same bundle. Prove runtime errors fail this harness.
    const count = registrations.length;
    assert.throws(() => new vm.Script('throw new Error("broken earlier plugin");\n' + bundle).runInContext(context), /broken earlier plugin/);
    check(registrations.length === count, 'An earlier plugin exception prevents later registration and is detected');
    console.log(`Panel client assets: ${checks} checks passed.`);
}

main().catch((error) => {
    console.error(`${error.name}: ${error.message}`);
    console.error(error.stack.split('\n').filter((line) => line.trim().startsWith('at ')).join('\n'));
    process.exitCode = 1;
});
