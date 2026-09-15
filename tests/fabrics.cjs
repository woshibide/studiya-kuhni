const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync(require('node:path').join(__dirname, '../assets/js/templates/fabrics.js'), 'utf8');

const boot = ({ height = 200, end = 1800, reduced = false, compact = false, headingHeight = 120 } = {}) => {
    let frame;
    let onResize;
    const events = {};
    const viewport = { matches: compact, addEventListener(name, handler) { this.change = handler; } };
    const motion = { matches: reduced, addEventListener(name, handler) { this.change = handler; } };
    const window = {
        scrollY: 0,
        innerHeight: 900,
        matchMedia: (query) => query.includes('reduced') ? motion : viewport,
        studioMotion: { paused: false, subscribe(handler) { events.motion = handler; } },
        addEventListener: (name, handler) => { events[name] = handler; },
        requestAnimationFrame: (handler) => { frame = handler; return 1; },
    };
    const body = {
        style: {},
        getBoundingClientRect: () => ({
            top: 600 - window.scrollY + (parseFloat((body.style.translate || '').split(' ')[1]) || 0),
            height,
        }),
    };
    const heading = { height: headingHeight, getBoundingClientRect: () => ({ height: heading.height }) };
    const fabric = {
        style: { setProperty(name, value) { this[name] = value; } },
        querySelector: () => heading,
    };
    const image = {
        style: { setProperty(name, value) { this[name] = value; } },
        getBoundingClientRect: () => ({ top: end - 600 - window.scrollY, bottom: end - window.scrollY, height: 600 }),
    };
    const photo = { dataset: { kitchenPhoto: 'factory/kitchen' }, querySelector: () => image };
    const lead = {
        dataset: { fabricKitchen: 'factory/kitchen' },
        querySelector: () => body,
        closest: () => fabric,
    };
    const grid = {
        querySelectorAll: (selector) => selector === '[data-kitchen-photo]' ? [photo] : selector === '[data-fabric-kitchen]' ? [lead] : selector === '.fabric-grid__image' ? [image] : selector === '.fabric-grid__fabric' ? [fabric] : [],
        addEventListener() {},
    };
    const context = {
        window,
        document: { querySelector: () => grid },
        getComputedStyle: () => ({ getPropertyValue: () => '80px', columnGap: '12px' }),
        ResizeObserver: class {
            constructor(handler) { onResize = handler; }
            observe() {}
        },
    };
    window.ResizeObserver = context.ResizeObserver;
    vm.runInNewContext(source, context);
    const flush = () => { const callback = frame; frame = null; callback?.(); };
    const scroll = (y) => { window.scrollY = y; events.scroll(); flush(); };
    flush();
    return { body, image, fabric, heading, window, viewport, scroll, resize: () => { onResize(); flush(); }, events, flush };
};

const kitchen = boot();
assert.equal(kitchen.fabric.style['--fabric-title-height'], '120px', 'Factory ending reserves its title height');
assert.equal(kitchen.body.style.translate, undefined, 'Text remains in normal flow before reaching its sticky position');
kitchen.scroll(700);
assert.equal(kitchen.body.getBoundingClientRect().top, 212, 'Description pins below factory title with a gutter');
kitchen.scroll(900);
assert.equal(kitchen.body.getBoundingClientRect().top, 212, 'Description stays fixed while kitchen photos scroll');
kitchen.resize();
assert.equal(kitchen.body.getBoundingClientRect().top, 212, 'Remeasuring pinned text does not accumulate its translation');
kitchen.scroll(1600);
assert.equal(kitchen.body.getBoundingClientRect().top + 200, 200, 'Text leaves with the final photo instead of leaking into the next kitchen');
kitchen.scroll(0);
assert.equal(kitchen.body.style.translate, '', 'Scrolling back restores the original text position');

const tall = boot({ height: 1000, end: 2600 });
tall.scroll(1000);
assert.equal(tall.body.getBoundingClientRect().top + 1000, 888, 'Tall descriptions keep their bottom readable without a nested scrollbar');

const short = boot({ end: 700 });
short.scroll(900);
assert.equal(short.body.style.translate, undefined, 'A kitchen shorter than its text never forces the text beyond its content');

const wrapped = boot({ headingHeight: 240 });
assert.equal(wrapped.fabric.style['--fabric-title-height'], '240px', 'Wrapped factory titles receive enough ending space');
wrapped.heading.height = 120;
wrapped.resize();
assert.equal(wrapped.fabric.style['--fabric-title-height'], '120px', 'Factory ending follows responsive title height');
wrapped.scroll(1600);
assert.equal(wrapped.body.getBoundingClientRect().top + 200, 200, 'Kitchen text still stops at the final photo, without using factory ending space');

const moving = boot();
const initialOffset = moving.image.style['--fabric-parallax-y'];
moving.scroll(1000);
assert.notEqual(moving.image.style['--fabric-parallax-y'], initialOffset, 'Photos move within their frames while scrolling');
assert.ok(Math.abs(parseFloat(moving.image.style['--fabric-parallax-y'])) <= 18, 'Parallax stays inside the scaled image bounds');
moving.window.studioMotion.paused = true;
moving.events.motion(); moving.flush();
assert.equal(moving.image.style['--fabric-parallax-y'], '0px');
assert.equal(moving.image.style['--fabric-parallax-scale'], '1', 'Pause restores uncropped photos');
for (const options of [{ reduced: true }, { compact: true }]) {
    const still = boot(options);
    still.scroll(1000);
    assert.equal(still.image.style['--fabric-parallax-y'], '0px');
    assert.equal(still.image.style['--fabric-parallax-scale'], '1', 'Mobile and reduced motion retain static photos');
    if (options.compact) assert.equal(still.body.style.translate, undefined, 'Mobile kitchen descriptions remain in document flow');
}

const responsive = boot();
responsive.scroll(900);
assert.equal(responsive.body.getBoundingClientRect().top, 212);
responsive.viewport.matches = true;
responsive.viewport.change(); responsive.flush();
assert.equal(responsive.body.style.translate, '', 'Switching to mobile clears the desktop sticky offset');
responsive.viewport.matches = false;
responsive.viewport.change(); responsive.flush();
assert.equal(responsive.body.getBoundingClientRect().top, 212, 'Switching back to desktop restores sticky text without drift');

console.log('Fabrics: pinning, kitchen boundaries, resize stability, reverse scrolling, and tall descriptions passed.');
