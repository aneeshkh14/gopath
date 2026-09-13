const fs=require('node:fs');const vm=require('node:vm');
const scripts=JSON.parse(fs.readFileSync(0,'utf8'));
for(const item of scripts){if(item.json)JSON.parse(item.script);else new vm.Script(item.script,{filename:item.file});}
console.log(`All ${scripts.length} rendered inline script/data blocks parse successfully, including PHP-generated scripts.`);
