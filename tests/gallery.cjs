const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync(require('node:path').join(__dirname, '../assets/js/gallery.js'), 'utf8');

class Element {
  constructor() {
    this.dataset = {}; this.style = { setProperty(name, value) { this[name] = value; } }; this.attrs = {}; this.events = {}; this.nodes = {};
    this.hidden = false; this.textContent = ''; this.offsetLeft = 0; this.offsetWidth = 100;
    this.clientWidth = 300; this.scrollLeft = 0;
    const classes = new Set();
    this.classList = {
      add: (name) => classes.add(name), remove: (name) => classes.delete(name), contains: (name) => classes.has(name),
      toggle(name) { if (classes.has(name)) { classes.delete(name); return false; } classes.add(name); return true; },
    };
  }
  querySelector(selector) { return this.nodes[selector] || null; }
  querySelectorAll(selector) { return this.nodes[selector] || []; }
  addEventListener(name, handler) { (this.events[name] ||= []).push(handler); }
  emit(name, props = {}) { return Promise.all((this.events[name] || []).map((handler) => handler({ target: this, ...props }))); }
  setAttribute(name, value) { this.attrs[name] = value; }
  decode() { return Promise.resolve(); }
  focus() { this.focused = true; }
  select() { this.selected = true; }
  matches() { return false; }
  scrollTo({ left }) { this.scrollLeft = left; }
  getBoundingClientRect() { return { left: 0, top: 0, width: 80, height: 100 }; }
}
class Dialog extends Element {
  open = false;
  showModal() { this.open = true; }
  close() { this.open = false; this.emit('close'); }
}
const settle = () => new Promise((resolve) => setTimeout(resolve, 5));
function boot({ url = 'https://studio.example/fabrics/kitchen?campaign=test#gallery', mobile = false, reduced = true, count = 3, embedded = false } = {}) {
  const root = new Element(); const dialog = new Dialog(); const strip = new Element();
  root.dataset.galleryEmbedded = String(embedded);
  const controls = Object.fromEntries(['image', 'image-buffer', 'frame', 'slot', 'error', 'expand', 'share-status', 'share-link', 'retry', 'share', 'prev', 'next'].map((key) => [key, new Element()]));
  for (const [key, element] of Object.entries(controls)) dialog.nodes[`[data-gallery-${key}]`] = element;
  controls.frame.clientWidth = 1000; controls.frame.clientHeight = 500;
  controls.image.naturalWidth = controls['image-buffer'].naturalWidth = 800;
  controls.image.naturalHeight = controls['image-buffer'].naturalHeight = 1000;
  const close = new Element();
  dialog.nodes['button[data-gallery-close]'] = close;
  dialog.nodes['[data-gallery-close]'] = [close];
  const buttons = Array.from({ length: count }, () => new Element());
  const thumbnails = buttons.map((_, i) => {
    const node = new Element(); const map = new Element(); const crop = new Element();
    node.dataset = { galleryKey: `${embedded ? 'fabrics/brand/kitchen/' : ''}photo ${i + 1}.jpg`, gallerySrc: `/photo-${i + 1}.jpg` };
    node.parentElement = strip; node.offsetLeft = i * 100;
    node.nodes['img'] = { alt: `Kitchen ${i + 1}`, src: `/preview-${i + 1}.jpg`, naturalWidth: 800, naturalHeight: 1000 };
    buttons[i].nodes['img'] = node.nodes['img'];
    node.nodes['.gallery-overlay__thumbnail-map'] = map;
    map.nodes['.gallery-overlay__crop'] = crop;
    return node;
  });
  root.nodes['[data-gallery-open]'] = embedded ? [] : buttons;
  root.nodes['[data-gallery-overlay]'] = dialog;
  root.nodes['[data-gallery-thumbnail]'] = thumbnails;
  const document = new Element(); document.documentElement = new Element(); document.title = 'Kitchen';
  document.nodes['[data-gallery]'] = [root];
  const layoutRow = new Element();
  const layoutButtons = [...thumbnails].reverse().map((thumbnail) => {
    const button = new Element(); const card = new Element();
    button.dataset.galleryLayoutOpen = thumbnail.dataset.galleryKey;
    button.nodes['img'] = { ...thumbnail.nodes.img, src: '/layout-preview.jpg' };
    button.closest = () => card;
    card.closest = () => layoutRow;
    return button;
  });
  const heroButtons = thumbnails.map((thumbnail) => {
    const button = new Element();
    button.dataset.galleryHeroOpen = thumbnail.dataset.galleryKey;
    button.nodes.img = { ...thumbnail.nodes.img, src: '/hero-preview.jpg' };
    return button;
  });
  const catalogButtons = thumbnails.map((thumbnail) => {
    const button = new Element();
    button.dataset.galleryCatalogOpen = thumbnail.dataset.galleryKey;
    button.nodes.img = { ...thumbnail.nodes.img, src: '/catalog-preview.jpg' };
    return button;
  });
  const unrelatedPhoto = new Element();
  unrelatedPhoto.dataset.galleryCatalogOpen = 'fabrics/another/kitchen/photo 1.jpg';
  document.nodes['[data-gallery-layout-open], [data-gallery-hero-open], [data-gallery-catalog-open]'] = embedded ? [...catalogButtons, unrelatedPhoto] : [...layoutButtons, ...heroButtons];
  const window = new Element(); window.location = { href: url };
  const timers = new Map(); let timerId = 0;
  window.setTimeout = (fn, duration) => {
    if (duration === 560 || duration >= 2000) { timers.set(++timerId, { fn, duration }); return timerId; }
    return setTimeout(fn, 0);
  };
  window.clearTimeout = (id) => { timers.delete(id); clearTimeout(id); };
  const media = { reduced: new Element(), explorer: new Element(), coarse: new Element() };
  media.reduced.matches = reduced; media.explorer.matches = !mobile;
  media.coarse.matches = mobile;
  window.matchMedia = (query) => query === '(pointer: coarse)' ? media.coarse : query.includes('reduced') ? media.reduced : media.explorer;
  const entries = [{ url, state: null }]; let entry = 0;
  const history = {
    get state() { return entries[entry].state; },
    pushState(state, _, url) { entries.splice(++entry); entries.push({ state, url: String(url) }); window.location.href = String(url); },
    replaceState(state, _, url) { entries[entry] = { state, url: String(url) }; window.location.href = String(url); },
    back() { if (entry) { window.location.href = entries[--entry].url; window.emit('popstate'); } },
    forward() { if (entry + 1 < entries.length) { window.location.href = entries[++entry].url; window.emit('popstate'); } },
  };
  const pending = [];
  class Image {
    constructor() { pending.push(this); this.naturalWidth = 800; this.naturalHeight = 1000; }
    decode() { return Promise.resolve(); }
  }
  const frames = new Map(); let frameId = 0;
  const navigator = { clipboard: { async writeText(value) { navigator.copied = value; } } };
  vm.runInNewContext(source, {
    document, window, history, navigator, Image, HTMLDialogElement: Dialog, URL,
    ResizeObserver: class { constructor(fn) { this.fn = fn; } observe() { this.fn(); } },
    requestAnimationFrame(fn) { frames.set(++frameId, fn); return frameId; }, cancelAnimationFrame(id) { frames.delete(id); },
  });
  const flushFrames = () => { const callbacks = [...frames.values()]; frames.clear(); callbacks.forEach((fn) => fn()); };
  const pump = async () => { for (let i = 0; i < 10; i++) { await settle(); flushFrames(); } };
  const load = async (number = pending.length - 1) => { pending[number].onload(); await pump(); };
  const runTimers = (duration) => { for (const [id, timer] of timers) { if (timer.duration === duration) { timers.delete(id); timer.fn(); } } };
  const currentImage = () => [controls.image, controls['image-buffer']].find((item) => item.classList.contains('is-current')); 
  return { root, dialog, buttons, thumbnails, close, controls, pending, window, history, navigator, frames, media, load, flushFrames, currentImage, pump, runTimers, layoutButtons, layoutRow, heroButtons, catalogButtons, unrelatedPhoto };
}

(async () => {
  for (const mobile of [false, true]) {
    const catalogue = boot({ embedded: true, mobile, url: 'https://studio.example/fabrics?campaign=test#fabric-brand' });
    await catalogue.unrelatedPhoto.emit('click');
    assert.equal(catalogue.dialog.open, false, 'Identical filenames from another kitchen cannot open this gallery');
    let prevented = false;
    const event = { preventDefault() { prevented = true; }, stopPropagation() {} };
    await catalogue.catalogButtons[1].emit('click', { ...event, metaKey: true });
    assert.equal(prevented, false, 'Modified photo links retain native new-tab navigation');
    assert.equal(catalogue.dialog.open, false);
    await catalogue.catalogButtons[1].emit('click', event);
    assert.equal(prevented, true);
    assert.equal(catalogue.dialog.open, true, 'Catalogue opens without a duplicate inline gallery');
    assert.equal(catalogue.dialog.classList.contains('is-expanded'), false, 'Catalogue photos open in the minimized gallery view');
    assert.equal(catalogue.controls.expand.attrs['aria-pressed'], 'false');
    assert.equal(catalogue.currentImage().src, '/catalog-preview.jpg');
    await catalogue.load();
    assert.equal(catalogue.currentImage().src, '/photo-2.jpg');
    assert.equal(new URL(catalogue.window.location.href).searchParams.get('gallery'), 'fabrics/brand/kitchen/photo 2.jpg');
    catalogue.history.back(); await settle();
    assert.equal(catalogue.dialog.open, false);
    assert.equal(catalogue.catalogButtons[1].focused, true, 'Close restores the clicked catalogue photo');
    catalogue.history.forward(); await catalogue.load();
    assert.equal(catalogue.currentImage().src, '/photo-2.jpg');
  }
  const directCatalogue = boot({ embedded: true, url: 'https://studio.example/fabrics?gallery=fabrics%2Fbrand%2Fkitchen%2Fphoto+3.jpg' });
  await directCatalogue.load();
  assert.equal(directCatalogue.currentImage().src, '/photo-3.jpg', 'Shared catalogue deep link opens its requested photo');
  await directCatalogue.close.emit('click');
  assert.equal(directCatalogue.window.location.href, 'https://studio.example/fabrics');
  assert.equal(directCatalogue.catalogButtons[0].focused, true);
  assert.equal(boot({ embedded: true, url: 'https://studio.example/fabrics?gallery=' }).dialog.open, false, 'An empty key must not open all embedded galleries');
  let test = boot();
  assert.equal(test.dialog.open, false);
  test.buttons[0].emit('click');
  assert.equal(test.currentImage().src, '/preview-1.jpg', 'Opening starts from cached inline photo, never an empty image');
  assert.equal(test.dialog.classList.contains('is-open'), false, 'Opening waits for a painted starting state');
  await test.load();
  assert.equal(test.dialog.open, true);
  assert.equal(test.currentImage().src, '/photo-1.jpg');
  assert.match(test.window.location.href, /campaign=test&gallery=photo\+1.jpg#gallery$/);
  assert.equal(test.thumbnails[0].attrs['aria-pressed'], 'true');
  await test.thumbnails[0].emit('pointermove', { clientX: 40, clientY: 100 });
  assert.equal(test.currentImage().style.objectPosition, '50% 100%', 'Portrait crop reaches bottom edge');
  const crop = test.thumbnails[0].nodes['.gallery-overlay__thumbnail-map'].nodes['.gallery-overlay__crop'];
  assert.equal(crop.style.width, '80px'); assert.equal(crop.style.height, '40px');
  await test.thumbnails[0].emit('pointerleave');
  assert.equal(test.currentImage().style.objectPosition, '50% 100%', 'Leaving preserves explored position');
  await test.window.emit('resize');
  assert.equal(test.currentImage().style.objectPosition, '50% 50%');
  await test.controls.expand.emit('click');
  await test.thumbnails[0].emit('pointermove', { clientX: 40, clientY: 100 });
  assert.equal(test.dialog.classList.contains('is-expanded'), true, 'Clicking photo expands behind controls');
  assert.equal(test.controls.expand.attrs['aria-label'], 'Свернуть фотографию');
  assert.equal(test.currentImage().style.objectPosition, '50% 100%', 'Expanded photo retains thumbnail exploration');
  await test.controls.expand.emit('click');
  test.thumbnails[1].emit('click'); test.thumbnails[2].emit('click');
  assert.equal(test.currentImage().src, '/photo-1.jpg', 'Current image remains visible while replacements load');
  await test.load(2); await test.load(1);
  assert.equal(test.currentImage().src, '/photo-3.jpg', 'Late response cannot replace most recent selection');
  let nativeShares = 0;
  test.navigator.share = async () => { nativeShares++; };
  await test.controls.share.emit('click');
  assert.equal(nativeShares, 0, 'Desktop sharing copies link even when native sharing exists');
  assert.match(test.navigator.copied, /gallery=photo\+3.jpg/);
  assert.equal(test.controls['share-status'].textContent, 'Ссылка скопирована.');
  assert.equal(test.controls['share-status'].classList.contains('is-visible'), true);
  test.runTimers(2400);
  assert.equal(test.controls['share-status'].classList.contains('is-visible'), false, 'Copy confirmation fades away automatically');
  test.navigator.clipboard.writeText = async () => { throw new Error('denied'); };
  await test.controls.share.emit('click');
  assert.equal(test.controls['share-link'].hidden, false);
  assert.equal(test.controls['share-link'].selected, true, 'Clipboard denial offers selectable link');
  test.history.back(); await settle();
  assert.equal(test.dialog.open, false, 'Back closes modal');
  test.history.forward(); await test.load();
  assert.equal(test.currentImage().src, '/photo-3.jpg', 'Forward restores selected photo');
  await test.close.emit('click');
  assert.equal(test.dialog.open, false); assert.equal(test.buttons[0].focused, true);
  assert.match(test.window.location.href, /\?campaign=test#gallery$/);

  test = boot({ url: 'https://studio.example/kitchen?gallery=photo+2.jpg&campaign=test' });
  await test.load();
  assert.equal(test.currentImage().src, '/photo-2.jpg', 'Deep link opens requested photo on first load');
  test.dialog.emit('keydown', { key: 'End', preventDefault() {} }); await test.load();
  assert.equal(test.currentImage().src, '/photo-3.jpg');
  test.dialog.emit('keydown', { key: 'ArrowRight', preventDefault() {} }); await test.load();
  assert.equal(test.currentImage().src, '/photo-1.jpg', 'Keyboard navigation wraps');
  await test.close.emit('click');
  assert.equal(test.window.location.href, 'https://studio.example/kitchen?campaign=test', 'Closing direct entry preserves other query parameters');

  test = boot({ mobile: true }); test.buttons[0].emit('click'); await test.load();
  await test.thumbnails[0].emit('pointermove', { clientX: 40, clientY: 100 });
  assert.equal(test.currentImage().style.objectPosition, '50% 50%', 'Mobile disables hover exploration');
  let mobileNativeShares = 0;
  test.navigator.share = async () => { mobileNativeShares++; };
  await test.controls.share.emit('click');
  assert.match(test.navigator.copied, /gallery=photo\+1.jpg/, 'Touch devices also copy the photo link');
  assert.equal(mobileNativeShares, 0, 'Copy icon never opens native sharing');
  test.thumbnails[1].emit('click'); test.pending[1].onerror(); await settle();
  assert.equal(test.controls.error.hidden, false, 'Failed photo offers retry');
  test.controls.retry.emit('click'); await test.load();
  assert.equal(test.currentImage().src, '/photo-2.jpg'); assert.equal(test.controls.error.hidden, true);

  test = boot({ reduced: false }); test.buttons[0].emit('click'); await test.load();
  await test.thumbnails[0].emit('pointermove', { clientX: 40, clientY: 100 });
  test.flushFrames();
  assert.equal(test.currentImage().style.objectPosition, '50% 53.5%', 'Exploration interpolates with factor 0.07');
  await test.thumbnails[0].emit('pointerleave');
  assert.equal(test.frames.size, 0, 'Leaving cancels animation');
  test.thumbnails[1].emit('click'); await test.close.emit('click'); await test.load();
  assert.equal(test.dialog.open, true, 'Dialog remains mounted while closing animation runs');
  assert.equal(test.dialog.classList.contains('is-open'), false);
  test.runTimers(560);
  assert.equal(test.dialog.open, false, 'Closing while loading never reopens dialog');
  assert.equal(test.currentImage().src, '/photo-1.jpg');

  test = boot({ count: 1 });
  assert.equal(test.controls.prev.disabled, true); assert.equal(test.controls.next.disabled, true);
  for (const mobile of [false, true]) {
    test = boot({ mobile });
    const heroButton = test.heroButtons[1];
    await heroButton.emit('click', { defaultPrevented: true });
    assert.equal(test.dialog.open, false, 'Canceled carousel clicks must not open gallery.');
    await heroButton.emit('click', { preventDefault() {}, stopPropagation() {} });
    assert.equal(test.dialog.classList.contains('is-expanded'), true, 'Hero entry opens expanded.');
    assert.equal(test.currentImage().src, '/hero-preview.jpg');
    await test.load();
    assert.equal(test.currentImage().src, '/photo-2.jpg', 'Hero entry matches clicked photo by filename.');
    await test.close.emit('click');
    assert.equal(heroButton.focused, true, 'Closing restores focus to hero photo.');

    test = boot({ mobile });
    const button = test.layoutButtons[0];
    let stopped = false;
    const event = { preventDefault() {}, stopPropagation() { stopped = true; } };
    await button.emit('click', event);
    assert.equal(test.dialog.open, false, 'Collapsed layout photos retain card expansion');
    assert.equal(stopped, false, 'First click reaches existing layout handler');
    button.closest().classList.add('is-expanded');
    test.layoutRow.classList.add('is-animating');
    await button.emit('click', event);
    assert.equal(test.dialog.open, false, 'Do not interrupt an ongoing card expansion');
    test.layoutRow.classList.remove('is-animating');
    await button.emit('click', event);
    assert.equal(test.dialog.classList.contains('is-expanded'), true, 'Layout entry starts at full width before first paint');
    assert.equal(test.controls.expand.attrs['aria-pressed'], 'true');
    assert.equal(test.currentImage().src, '/layout-preview.jpg', 'Opening reuses clicked photo as preview');
    assert.equal(stopped, true, 'Gallery click cannot also collapse layout card');
    await test.load();
    assert.equal(test.currentImage().src, '/photo-3.jpg', 'Match photos by filename, independently of layout order');
    await test.close.emit('click');
    assert.equal(button.focused, true, 'Closing restores focus to clicked layout photo');
    assert.equal(button.closest().classList.contains('is-expanded'), true);
    test.buttons[0].emit('click'); await test.load();
    assert.equal(test.dialog.classList.contains('is-expanded'), false, 'Normal gallery entry keeps its original inset view');
  }
  test = boot({ url: 'https://studio.example/kitchen?gallery=missing.jpg' });
  assert.equal(test.dialog.open, false, 'Unknown photo does not open unrelated image');
  console.log('Gallery: crop, motion, switching races, deep links, history, keyboard, mobile, sharing, and load recovery passed.');
})().catch((error) => { console.error(error); process.exitCode = 1; });
