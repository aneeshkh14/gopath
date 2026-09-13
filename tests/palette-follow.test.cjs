const {test}=require('node:test');const assert=require('node:assert/strict');const {setup,tick}=require('./helpers.cjs');
function markup(){return `<main class="gep-exam-fullscreen-container"><div class="gep-exam-layout"><header class="gep-exam-header"><button id="gep-palette-toggle">Grid</button></header><main class="gep-exam-main" id="gep-exam-main-container"><div id="gep-timer"></div><div id="gep-question-display">${Array.from({length:100},(_,i)=>`<div class="gep-question-block" data-id="${i+1}" data-cat-id="0"><div class="gep-options-container" data-qtype="numerical"><input class="gep-numerical-ans"></div></div>`).join('')}</div></main><aside class="gep-exam-sidebar"><button id="gep-sidebar-collapse-toggle">Expand</button><button id="gep-palette-close">Close</button><div class="gep-palette-grid-container">${Array.from({length:100},(_,i)=>`<button class="gep-palette-btn" data-id="${i+1}" data-index="${i}" data-cat-id="0">${i+1}</button>`).join('')}</div></aside><footer class="gep-exam-footer"><button id="gep-prev-btn">Previous</button><button id="gep-next-btn">Next</button></footer></div></main>`;}
async function page(width){const p=await setup(markup(),'public/js/gep-exam.js',{GEP_Exam:{ajaxurl:'/ajax',remaining_seconds:600,elapsed_seconds:0,saved_answers:{},sections_data:[],attempt_id:42}},w=>{w.innerWidth=width;w.fetch=async()=>({ok:true,json:async()=>({success:true})});});
 const grid=p.w.document.querySelector('.gep-palette-grid-container');
 Object.defineProperty(grid,'clientHeight',{value:250});grid.getBoundingClientRect=()=>({top:100,bottom:350});
 p.w.document.querySelectorAll('.gep-palette-btn').forEach((b,i)=>{b.getBoundingClientRect=()=>({top:110+Math.floor(i/5)*50-grid.scrollTop,bottom:150+Math.floor(i/5)*50-grid.scrollTop});});return {...p,grid};}
for(const width of [390,820,1366])test(`Next keeps the current number visible through a 100-question paper at ${width}px`,async t=>{
 const p=await page(width);t.after(p.close);
 for(let i=0;i<99;i++)p.w.document.getElementById('gep-next-btn').click();
 const active=p.w.document.querySelector('.gep-palette-btn[aria-current="step"]');
 assert.equal(active.textContent,'100');assert.ok(p.grid.scrollTop>600);assert.ok(active.getBoundingClientRect().bottom<=344);
 p.w.document.getElementById('gep-prev-btn').click();assert.equal(p.w.document.querySelector('[aria-current="step"]').textContent,'99');
 // Move all the way back: the grid follows upwards as well.
 for(let i=0;i<98;i++)p.w.document.getElementById('gep-prev-btn').click();
 assert.equal(p.w.document.querySelector('[aria-current="step"]').textContent,'1');assert.ok(p.grid.scrollTop<=4);
 await tick();assert.deepEqual(p.errors,[]);
});
test('a number already visible does not move the palette',async t=>{const p=await page(1366);t.after(p.close);p.w.document.getElementById('gep-next-btn').click();assert.equal(p.grid.scrollTop,0);await tick();});
test('desktop edge control opens full palette and reveals the current number without scrolling the page',async t=>{
 const p=await page(1366);t.after(p.close);for(let i=0;i<80;i++)p.w.document.getElementById('gep-next-btn').click();p.grid.scrollTop=0;
 const shell=p.w.document.querySelector('.gep-exam-fullscreen-container');shell.scrollTop=123;
 p.w.document.getElementById('gep-sidebar-collapse-toggle').click();
 assert.equal(p.w.document.querySelector('.gep-exam-sidebar').classList.contains('active'),true);assert.ok(p.grid.scrollTop>500);assert.equal(shell.scrollTop,123);
 p.w.document.getElementById('gep-palette-close').click();assert.equal(p.w.document.querySelector('.gep-exam-sidebar').classList.contains('active'),false);await tick();
});
test('poster arrows scroll by a viewport and respect reduced motion',async t=>{
 const p=await setup('<section class="gep-study-promotions"><button data-promo-direction="-1"></button><button data-promo-direction="1"></button><div class="gep-study-links"></div></section>','public/js/gep-dashboard.js',{},w=>{w.matchMedia=()=>({matches:true});});t.after(p.close);
 const track=p.w.document.querySelector('.gep-study-links'),calls=[];Object.defineProperty(track,'clientWidth',{value:600});track.scrollBy=o=>calls.push(o);
 p.$('[data-promo-direction="1"]').trigger('click');p.$('[data-promo-direction="-1"]').trigger('click');
 assert.deepEqual(calls.map(c=>[c.left,c.behavior]),[[600,'auto'],[-600,'auto']]);assert.deepEqual(p.errors,[]);
});
