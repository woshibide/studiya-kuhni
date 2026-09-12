const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync(require('node:path').join(__dirname, '../assets/js/fabric-info-map.js'), 'utf8');

const boot = ({ data = {}, leaflet = true, lazy = false, fail = false, paused = false, reduced = false, motionController = true } = {}) => {
  const status = { textContent: '' };
  const element = {
    dataset: { lat: '43.72955', lng: '12.515806', zoom: '13', label: '<img src=x onerror=alert(1)>', ...data },
    closest: () => ({ querySelector: () => status }),
    contains: () => true,
    addEventListener(name, handler, capture) { calls['dom-' + name] = handler; calls.capture = capture; },
  };
  const events = {};
  const timers = new Map();
  const calls = {};
  const map = {
    setView(coords, zoom) { calls.view = { coords, zoom }; return this; },
    on(name, handler) { calls[name] = handler; return this; },
    stop() { calls.stops = (calls.stops || 0) + 1; },
    panBy(offset, options) { calls.pan = { offset, options }; return this; },
    invalidateSize(options) { calls.resize = options; },
    remove() { calls.removed = true; },
  };
  const tiles = { on(name, handler) { events[name] = handler; return this; }, addTo() { calls.tilesAdded = true; } };
  function BaseMap(target, options) {
    calls.mapOptions = options;
    if (fail) throw new Error('Leaflet failed');
    this.options = options;
    calls.mapInstance = this;
  }
  BaseMap.prototype = map;
  BaseMap.extend = (methods) => {
    class Extended extends BaseMap {}
    Object.assign(Extended.prototype, methods);
    return Extended;
  };
  const reducedQuery = { matches: reduced, addEventListener(name, handler) { calls.preferenceChange = handler; }, removeEventListener() {} };
  const motion = { paused, subscribe(handler) { calls.motionChange = handler; handler(this.paused); return () => {}; } };
  const L = {
    Map: BaseMap,
    map(target, options) { return new BaseMap(target, options); },
    control: { zoom(options) { calls.zoomOptions = options; return { addTo() {} }; } },
    tileLayer(url, options) { calls.tiles = { url, options }; return tiles; },
    divIcon(options) { calls.markerIcon = options; return options; },
    marker(coords, options) {
      calls.marker = { coords, options };
      return { addTo() { return this; }, bindPopup(node) { calls.popup = node; } };
    },
  };
  const context = {
    document: { querySelectorAll: () => [element], createElement: () => ({ textContent: '' }) },
    window: { matchMedia: () => reducedQuery, studioMotion: motionController ? motion : undefined, L: leaflet ? L : undefined, setTimeout(fn) { const id = timers.size + 1; timers.set(id, fn); return id; }, clearTimeout(id) { timers.delete(id); } },
    ResizeObserver: class { constructor(callback) { calls.resizeCallback = callback; } observe(target) { calls.resizeTarget = target; } },
  };
  if (lazy) context.IntersectionObserver = class {
    constructor(callback) { calls.intersect = callback; }
    observe(target) { calls.observed = target; }
    unobserve(target) { calls.unobserved = target; }
  };
  vm.runInNewContext(source, context);
  return { status, element, events, timers, calls, motion, reducedQuery };
};

let result = boot({ lazy: true });
assert.equal(result.calls.mapOptions, undefined, 'Offscreen maps do not fetch tiles');
result.calls.intersect([{ target: result.element, isIntersecting: false }]);
assert.equal(result.calls.mapOptions, undefined);
result.calls.intersect([{ target: result.element, isIntersecting: true }]);
assert.equal(result.element.dataset.mapReady, 'true');
assert.equal(result.calls.mapOptions.scrollWheelZoom, false);
assert.equal(result.calls.mapOptions.keyboard, true);
assert.equal(result.calls.mapOptions.attributionControl, true);
assert.equal(result.calls.mapOptions.maxZoom, 14);
assert.equal(result.calls.tiles.options.maxZoom, 14);
assert.equal(result.calls.mapOptions.inertia, true, 'Unpaused map may use drag inertia');
assert.equal(result.calls.mapOptions.zoomAnimation, false);
assert.equal(result.calls.mapOptions.fadeAnimation, false);
assert.equal(result.calls.mapOptions.markerZoomAnimation, false);
assert.equal(result.calls.zoomOptions.zoomInTitle, 'Увеличить масштаб');
assert.equal(result.calls.tiles.url, 'https://tiles.maps.eox.at/wmts/1.0.0/s2cloudless_3857/default/g/{z}/{y}/{x}.jpg');
assert.match(result.calls.tiles.options.attribution, /cloudless\.eox\.at/);
assert.equal(result.calls.tiles.options.referrerPolicy, 'strict-origin-when-cross-origin');
assert.equal(result.calls.markerIcon.iconSize[0], 44, 'Custom marker preserves touch target size');
assert.equal(result.calls.popup.textContent, '<img src=x onerror=alert(1)>', 'Labels become text nodes, never popup HTML');
assert.equal(result.calls.popup.innerHTML, undefined);
const popupCloseAttrs = {};
result.calls.popupopen({ popup: { getElement: () => ({ querySelector: () => ({ setAttribute(key, value) { popupCloseAttrs[key] = value; } }) }) } });
assert.equal(popupCloseAttrs['aria-label'], 'Закрыть описание');
result.events.tileload(); result.events.load();
assert.equal(result.status.textContent, '');
assert.equal(result.timers.size, 0);
result.events.loading(); result.events.tileload(); result.events.tileerror(); result.events.load();
assert.match(result.status.textContent, /частично/);
result.events.loading(); result.events.tileerror(); result.events.load();
assert.match(result.status.textContent, /Не удалось/);
result.events.loading(); [...result.timers.values()][0]();
assert.match(result.status.textContent, /медленно/);
result.calls.resizeCallback();
assert.equal(result.calls.resize.pan, false);

result = boot({ data: {
  tileUrl: 'https://tiles.example.test/{z}/{x}/{y}.jpg',
  attribution: '<a href="https://tiles.example.test/credits">Custom tiles</a>',
} });
assert.match(result.calls.tiles.url, /tiles\.example\.test/, 'Configured satellite tiles reach Leaflet');
assert.match(result.calls.tiles.options.attribution, /tiles\.example\.test\/credits/, 'Provider attribution follows configured imagery');

for (const data of [{ lat: '' }, { lng: '' }, { lat: '95' }, { lng: '181' }, { lat: '43junk' }, { lng: 'Infinity' }]) {
  result = boot({ data });
  assert.equal(result.calls.mapOptions, undefined);
  assert.equal(result.element.dataset.mapReady, 'error');
}
result = boot({ data: { lat: '0', lng: '0', zoom: '99' } });
assert.equal(result.calls.view.coords[0], 0);
assert.equal(result.calls.view.coords[1], 0);
assert.equal(result.calls.view.zoom, 14);
result = boot({ leaflet: false });
assert.match(result.status.textContent, /Не удалось/);
assert.equal(result.timers.size, 0);
result = boot({ fail: true });
assert.equal(result.element.dataset.mapReady, 'error');
assert.match(result.status.textContent, /Не удалось/);
result = boot();
result.calls.mapInstance.panBy([80, 0]);
assert.equal(result.calls.pan.options.animate, undefined);
result.motion.paused = true;
result.calls.motionChange(true);
assert.equal(result.calls.mapOptions.inertia, false);
assert.equal(result.calls.stops, 1, 'Pausing stops in-flight pan animations');
result.calls.mapInstance.panBy([80, 0]);
assert.equal(result.calls.pan.options.animate, false, 'Keyboard pan cannot animate while paused');
result.calls.mapInstance.panBy([80, 0], { animate: true, duration: 2 });
assert.equal(result.calls.pan.options.animate, false, 'Previously queued inertia cannot bypass pause');
assert.equal(result.calls.pan.options.duration, 2);
result.motion.paused = false;
result.calls.motionChange(false);
assert.equal(result.calls.mapOptions.inertia, true);
result.calls.mapInstance.panBy([80, 0], { animate: true });
assert.equal(result.calls.pan.options.animate, true);
result = boot({ reduced: true, motionController: false });
assert.equal(result.calls.mapOptions.inertia, false, 'System reduced motion works without global script');
result.calls.mapInstance.panBy([80, 0], { animate: true });
assert.equal(result.calls.pan.options.animate, false);
result.reducedQuery.matches = false;
result.calls.preferenceChange();
assert.equal(result.calls.mapOptions.inertia, true);

for (const [name, selector] of [
  ['zoom-in', '.leaflet-control-zoom a[role="button"]'],
  ['zoom-out', '.leaflet-control-zoom a[role="button"]'],
  ['marker', '.leaflet-marker-icon[role="button"]'],
  ['popup-close', '.leaflet-popup-close-button[role="button"]'],
]) {
  let activated = 0;
  let prevented = 0;
  let stopped = 0;
  const control = { getAttribute: () => 'false', click() { activated += 1; } };
  const event = { key: ' ', target: { closest: (selectors) => selectors.includes(selector) ? control : null }, preventDefault() { prevented += 1; }, stopPropagation() { stopped += 1; } };
  result.calls['dom-keydown'](event);
  assert.equal(activated, 1, name + ': Space activates control');
  assert.equal(prevented, 1, name + ': Space cannot scroll document');
  assert.equal(stopped, 1);
  result.calls['dom-keydown']({ ...event, repeat: true });
  assert.equal(activated, 1, 'Holding Space never repeats activation');
  control.getAttribute = () => 'true';
  result.calls['dom-keydown'](event);
  assert.equal(activated, 1, 'Disabled zoom controls cannot activate');
}
let unexpectedActivation = 0;
const irrelevantEvent = { key: ' ', target: { closest: () => null }, preventDefault() { unexpectedActivation += 1; }, stopPropagation() { unexpectedActivation += 1; } };
result.calls['dom-keydown'](irrelevantEvent);
result.calls['dom-keydown']({ ...irrelevantEvent, key: 'Enter' });
result.calls['dom-keydown']({ ...irrelevantEvent, ctrlKey: true });
assert.equal(unexpectedActivation, 0, 'Non-control Space and native Enter remain untouched');
console.log('Map frontend: lazy loading, safe coordinates/popups, attribution, keyboard controls, motion preferences and failure states passed.');
