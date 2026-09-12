const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const source = fs.readFileSync(path.join(__dirname, '../assets/js/mason-gallery.js'), 'utf8');
const rowsSelector = '[data-home-fabric-row], .kuhnya-layout-grid';
const cardsSelector = '[data-home-fabric-card], [data-kuhnya-layout-card]';

function element(nodes = {}) {
  const classes = new Set();
  const styles = new Map();
  return {
    dataset: {}, attrs: {}, nodes,
    classList: {
      add: (name) => classes.add(name),
      remove: (name) => classes.delete(name),
      contains: (name) => classes.has(name),
    },
    style: {
      setProperty: (name, value) => styles.set(name, value),
      getPropertyValue: (name) => styles.get(name) || '',
      removeProperty: (name) => styles.delete(name),
    },
    setAttribute(name, value) { this.attrs[name] = value; },
    removeAttribute(name) { delete this.attrs[name]; },
    matches(selector) { return selector === rowsSelector && this.isRow; },
    querySelector(selector) { return nodes[selector] || null; },
    querySelectorAll(selector) { return nodes[selector] || []; },
    addEventListener() {},
  };
}

for (const nested of [false, true]) {
  const photo = element();
  const toggle = element();
  const card = element({
    '[data-gallery-layout-open]': photo,
    '[data-home-fabric-toggle], [data-kuhnya-layout-toggle]': toggle,
  });
  const row = element({ [cardsSelector]: [card] });
  row.isRow = true;
  const listing = nested ? element({ [rowsSelector]: [row], [cardsSelector]: [card] }) : row;
  const mobile = { matches: true, addEventListener() {} };
  vm.runInNewContext(source, {
    window: { matchMedia: (query) => query === '(max-width: 48rem)' ? mobile : { matches: true } },
    document: { readyState: 'complete', querySelectorAll: () => [listing] },
  });
  assert.equal(card.classList.contains('is-expanded'), true, 'Mobile photos must open gallery without an extra expand tap.');
  assert.equal(card.style.getPropertyValue('margin-top'), '0', 'Desktop stagger must not leave mobile gaps.');
  assert.equal(photo.attrs['aria-haspopup'], 'dialog');
  assert.equal(photo.attrs['aria-label'], 'Открыть фото кухни в галерее');
  assert.equal(toggle.attrs['aria-expanded'], 'true');
  assert.equal(toggle.attrs['aria-disabled'], 'true');
  assert.equal(toggle.attrs.tabindex, '-1');
}

console.log('Masonry: mobile gallery roots and nested rows initialize expanded, with accessible photo controls.');
