const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const parser = new (require('php-parser'))({parser: {php7: true, suppressErrors: false}, ast: {withPositions: true}});
function files(dir) { return fs.readdirSync(dir, {withFileTypes:true}).flatMap(e => e.name.startsWith('.') || ['node_modules','build'].includes(e.name) ? [] : e.isDirectory() ? files(path.join(dir,e.name)) : [path.join(dir,e.name)]); }
let php = 0, js = 0, inline = 0, dynamic = 0;
for (const file of files('.')) {
  if (file.endsWith('.php')) { const source = fs.readFileSync(file,'utf8'); parser.parseCode(source,file); php++;
    for (const match of source.matchAll(/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/g)) {
      if (match[1].includes('<?')) { dynamic++; continue; }
      new vm.Script(match[1], {filename: file + ':inline'}); inline++;
    } }
  if (file.endsWith('.js') && !file.startsWith('tests/')) { new vm.Script(fs.readFileSync(file,'utf8'),{filename:file}); js++; }
}
console.log(`Parsed ${php} PHP files and ${js} JavaScript files, plus ${inline} static inline scripts successfully (${dynamic} PHP-generated scripts need rendered runtime coverage).`);
