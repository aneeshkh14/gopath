const {test}=require('node:test');const assert=require('node:assert/strict');const {setup,tick}=require('./helpers.cjs');
const markup=`<main class="gep-exam-fullscreen-container"><div class="gep-exam-layout"><header class="gep-exam-header"><button id="gep-palette-toggle" aria-expanded="false">Grid</button></header><main class="gep-exam-main" id="gep-exam-main-container"><div id="gep-timer"></div><div id="gep-question-display"><div class="gep-question-block" data-id="1" data-cat-id="0"><div class="gep-options-container" data-qtype="numerical"><input class="gep-numerical-ans"></div></div><div class="gep-question-block" data-id="2" data-cat-id="0"></div></div><button id="gep-scroll-to-options-btn">Options</button><div id="gep-floating-scroll-btns"><button id="gep-float-scroll-down">Down</button><button id="gep-float-scroll-up">Up</button></div></main><aside class="gep-exam-sidebar" id="gep-exam-sidebar"><button id="gep-sidebar-collapse-toggle" style="display:none"><span class="toggle-icon"></span></button><button id="gep-palette-close">Close</button><button class="gep-palette-btn" data-id="1" data-index="0">1</button><button class="gep-palette-btn" data-id="2" data-index="1">2</button><button id="gep-submit-btn">Submit</button></aside><footer class="gep-exam-footer"><button id="gep-next-btn">Next</button></footer></div></main>`;
async function page(width){return setup(markup,'public/js/gep-exam.js',{GEP_Exam:{ajaxurl:'/ajax',remaining_seconds:600,elapsed_seconds:0,saved_answers:{},sections_data:[],attempt_id:42}},w=>{w.innerWidth=width;w.fetch=async()=>({ok:true,json:async()=>({success:true})});});}
for(const width of [320,360,390,768,820,992,1024,1366,1920])test(`palette opens, closes and restores question access at ${width}px (DOM interaction)`,async t=>{
 const p=await page(width);t.after(p.close);const sidebar=p.$('#gep-exam-sidebar')[0];
 assert.equal(sidebar.inert,width<=992);p.$('#gep-palette-toggle')[0].click();assert.equal(sidebar.classList.contains('active'),true);assert.equal(sidebar.getAttribute('aria-modal'),'true');assert.equal(p.w.document.activeElement.id,'gep-palette-close');assert.equal(p.$('.gep-exam-main')[0].inert,true);
 p.$('#gep-palette-close')[0].click();assert.equal(sidebar.classList.contains('active'),false);assert.equal(p.$('.gep-exam-main')[0].inert,undefined);assert.equal(p.w.document.activeElement.id,'gep-palette-toggle');assert.deepEqual(p.errors,[]);
});
test('palette focus stays inside and Escape returns to its opener',async t=>{
 const p=await page(1366);t.after(p.close);p.$('#gep-palette-toggle')[0].click();p.$('#gep-submit-btn')[0].focus();p.w.document.activeElement.dispatchEvent(new p.w.KeyboardEvent('keydown',{key:'Tab',bubbles:true,cancelable:true}));assert.equal(p.w.document.activeElement.id,'gep-palette-close');
 p.w.document.activeElement.dispatchEvent(new p.w.KeyboardEvent('keydown',{key:'Escape',bubbles:true,cancelable:true}));assert.equal(p.$('#gep-palette-toggle').attr('aria-expanded'),'false');assert.equal(p.w.document.activeElement.id,'gep-palette-toggle');
});
test('resizing an open palette preserves the dialog then hides it safely on mobile close',async t=>{
 const p=await page(1366);t.after(p.close);p.$('#gep-palette-toggle')[0].click();p.w.innerWidth=390;p.w.dispatchEvent(new p.w.Event('resize'));assert.equal(p.$('#gep-exam-sidebar')[0].inert,false);p.$('#gep-palette-close')[0].click();assert.equal(p.$('#gep-exam-sidebar')[0].inert,true);p.w.innerWidth=1366;p.w.dispatchEvent(new p.w.Event('resize'));assert.equal(p.$('#gep-exam-sidebar')[0].inert,false);
});
test('choosing a palette question closes the overview and keeps the typed draft',async t=>{
 const p=await page(1366);t.after(p.close);p.$('.gep-numerical-ans').val('42');p.$('#gep-palette-toggle')[0].click();p.$('[data-index="1"]')[0].click();assert.equal(p.$('.gep-question-block')[1].style.display,'block');assert.equal(p.$('#gep-exam-sidebar').hasClass('active'),false);assert.equal(p.$('.gep-exam-main')[0].inert,undefined);await tick();assert.deepEqual(p.errors,[]);
});
test('Submit from full-size palette opens confirmation without leaving exam background inert',async t=>{
 const p=await page(390);t.after(p.close);p.$('#gep-palette-toggle')[0].click();p.$('#gep-submit-btn')[0].click();assert.equal(p.$('#gep-exam-sidebar').hasClass('active'),false);assert.equal(p.$('.gep-exam-main')[0].inert,undefined);assert.equal(p.$('[role="dialog"]').length,1);assert.deepEqual(p.errors,[]);
});
for(const width of [390,820,1366])test(`scroll controls target the actual scroll container at ${width}px with a long question`,async t=>{
 const p=await page(width);t.after(p.close);const shell=p.$('.gep-exam-fullscreen-container')[0],pane=p.$('#gep-question-display')[0];const expected=width>992?pane:shell;
 Object.defineProperty(expected,'scrollHeight',{value:2400});Object.defineProperty(expected,'clientHeight',{value:500});const calls=[];expected.scrollTo=o=>calls.push(o.top);
 p.$('#gep-float-scroll-down')[0].click();p.$('#gep-float-scroll-up')[0].click();assert.deepEqual(calls,[2400,0]);expected.scrollTop=1000;p.$('#gep-next-btn')[0].click();assert.equal(expected.scrollTop,0);await tick();assert.deepEqual(p.errors,[]);
});
// Cascade checks use the actual stylesheet at selected media widths. JSDOM does
// not perform layout; these verify scroll ownership, not rendered geometry.
const fs=require('node:fs'),cssom=require('@acemir/cssom'),{JSDOM}=require('jsdom');
const sheet=cssom.parse(fs.readFileSync('public/css/gep-exam.css','utf8'));
function cssAtWidth(list,width){return Array.from(list).map(rule=>{
 if(rule.media){const query=rule.media.mediaText,min=query.match(/min-width:\s*(\d+)px/),max=query.match(/max-width:\s*(\d+)px/);return (min&&width<+min[1])||(max&&width>+max[1])?'':cssAtWidth(rule.cssRules,width);}
 return rule.cssText;
}).join('\n');}
for(const width of [320,390,768,820,992,1024,1366,1920])test(`CSS cascade permits scrolling and full-size palette at ${width}px`,t=>{
 const dom=new JSDOM('<style>'+cssAtWidth(sheet.cssRules,width)+'</style>'+markup);t.after(()=>dom.window.close());const doc=dom.window.document;
 const style=sel=>dom.window.getComputedStyle(doc.querySelector(sel));
 assert.equal(style('#gep-question-display').maxHeight,'none');
 assert.equal(style('#gep-question-display').overflow,width<=992?'visible':'auto');
 if(width<=992){assert.equal(style('.gep-exam-fullscreen-container').position,'fixed');assert.equal(style('.gep-exam-fullscreen-container').overflow,'auto');}
 const sidebar=doc.querySelector('.gep-exam-sidebar');sidebar.classList.add('collapsed','active');
 assert.equal(style('.gep-exam-sidebar').position,'fixed');assert.equal(style('.gep-exam-sidebar').width,'100%');assert.equal(style('.gep-exam-sidebar').height,'100dvh');assert.equal(style('.gep-exam-sidebar').overflow,'hidden');
});
