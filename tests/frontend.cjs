const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const root = path.resolve(__dirname, '..');
const scriptRoot = path.join(root, 'assets/js');
const files = fs.readdirSync(scriptRoot, { recursive: true })
  .filter((name) => name.endsWith('.js') && !name.split(path.sep).includes('node_modules'));
for (const file of files) {
  new vm.Script(fs.readFileSync(path.join(scriptRoot, file), 'utf8'), { filename: file });
}
// Browser classic scripts share one global lexical scope. Parse the real shared
// scripts with each page script to detect collisions missed by individual checks.
const footer = fs.readFileSync(path.join(root, 'site/snippets/footer.php'), 'utf8');
const shared = [...footer.matchAll(/['"]assets\/js\/([^'"]+\.js)['"]/g)]
  .map((match) => match[1]).filter((name) => !name.startsWith('node_modules/') && !name.includes('{'));
for (const file of files.filter((name) => name.startsWith('templates/'))) {
  const source = [...shared, file].map((name) => fs.readFileSync(path.join(scriptRoot, name), 'utf8')).join('\n;\n');
  new vm.Script(source, { filename: 'shared + ' + file });
}
console.log(`Frontend: ${files.length} scripts and shared/page combinations parse successfully.`);
