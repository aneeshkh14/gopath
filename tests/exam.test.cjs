const {test}=require('node:test');const assert=require('node:assert/strict');const {setup,tick}=require('./helpers.cjs');
const html=`<div id="gep-exam-main-container"><div id="gep-timer"></div><span id="gep-q-pane-marks"></span><div class="gep-question-block" data-id="1" data-cat-id="0"><div class="gep-options-container" data-qtype="mcq"><label class="gep-option-card"><input type="radio" name="q1" value="A">A</label><label class="gep-option-card"><input type="radio" name="q1" value="B">B</label></div></div><div class="gep-question-block" data-id="2" data-cat-id="0"><div class="gep-options-container" data-qtype="numerical"><input class="gep-numerical-ans"></div></div><button class="gep-palette-btn not-visited" data-id="1" data-index="0" data-cat-id="0">1</button><button class="gep-palette-btn not-visited" data-id="2" data-index="1" data-cat-id="0">2</button><button id="gep-prev-btn">Previous</button><button id="gep-next-btn">Next</button><button id="gep-review-btn">Review</button><button id="gep-submit-btn">Submit</button><button id="gep-clear-btn">Clear</button></div>`;
async function page(before) {
 const requests=[];const p=await setup(html,'public/js/gep-exam.js',{GEP_Exam:{ajaxurl:'/ajax',nonce:'test',attempt_id:55,test_id:3,remaining_seconds:3600,elapsed_seconds:0,lang:'en',saved_answers:{},sections_data:[]}},w=>{
  w.fetch=(url,options)=>new Promise((resolve,reject)=>requests.push({data:Object.fromEntries(options.body.entries()),resolve:data=>resolve({ok:true,json:async()=>data}),reject}));
  if(before)before(w);
 });p.fetches=requests;return p;
}
async function resolve(request,data={success:true,data:{}}){request.resolve(data);await tick();}
test('rapid answer changes save serially and keep the newest local value until acknowledged',async t=>{
 const p=await page();t.after(p.close);assert.deepEqual(p.errors,[]);
 p.w.document.querySelector('input[value="A"]').click();p.w.document.querySelector('input[value="B"]').click();assert.equal(p.fetches.length,1);
 assert.equal(JSON.parse(p.w.localStorage.getItem('gep_pending_55_1')).answer,'B');
 await resolve(p.fetches[0]);assert.equal(p.fetches.length,2);assert.equal(p.fetches[1].data.answer,'B');assert.ok(p.w.localStorage.getItem('gep_pending_55_1'));
 await resolve(p.fetches[1]);assert.equal(p.w.localStorage.getItem('gep_pending_55_1'),null);assert.equal(p.$('#gep-save-status').text(),'All answers saved');
});
test('offline answer remains queued and reconnect retries it',async t=>{
 const p=await page();t.after(p.close);p.w.document.querySelector('input[value="A"]').click();p.fetches[0].reject(new Error('Offline'));await tick();
 assert.match(p.$('#gep-save-status').text(),/not synced/);assert.ok(p.w.localStorage.getItem('gep_pending_55_1'));
 p.w.dispatchEvent(new p.w.Event('online'));assert.equal(p.fetches.length,2);await resolve(p.fetches[1]);assert.equal(p.w.localStorage.getItem('gep_pending_55_1'),null);
});
test('numerical answers and unsynced local responses restore on reload',async t=>{
 const p=await page(w=>{
  w.GEP_Exam.saved_answers={'1':{answer:'A',flagged:false},'2':{answer:'42',flagged:false}};
  w.localStorage.setItem('gep_pending_55_1',JSON.stringify({answer:'B',flagged:true}));
 });t.after(p.close);
 assert.equal(p.$('input[value="B"]').prop('checked'),true);assert.equal(p.$('.gep-numerical-ans').val(),'42');assert.equal(p.fetches[0].data.answer,'B');assert.deepEqual(p.errors,[]);await resolve(p.fetches[0]);
});
test('blocked local and session storage do not prevent server saving',async t=>{
 const p=await page(w=>{
  for(const key of ['localStorage','sessionStorage']) Object.defineProperty(w,key,{get(){throw new Error('Storage denied');}});
 });t.after(p.close);assert.deepEqual(p.errors,[]);p.w.document.querySelector('input[value="A"]').click();await resolve(p.fetches[0]);assert.equal(p.$('#gep-save-status').text(),'All answers saved');
});
test('submission waits for answer saves, then shows retry on network failure without redirecting',async t=>{
 const p=await page();t.after(p.close);p.w.document.querySelector('input[value="A"]').click();await resolve(p.fetches[0]);
 p.$('#gep-submit-btn').trigger('click');assert.equal(p.$('[role="dialog"]').length,1);p.w.document.querySelector('[data-action="submit"]').click();
 assert.equal(p.fetches[1].data.action,'gep_save_answer');assert.equal(p.fetches.length,2);
 await resolve(p.fetches[1]);assert.equal(p.fetches[2].data.action,'gep_submit_exam');
 p.fetches[2].reject(new Error('Offline'));await tick();assert.match(p.$('[role="dialog"]').text(),/Submission not confirmed/);assert.equal(p.w.location.pathname,'/dashboard/');assert.equal(p.$('[data-action="retry"]').length,1);
});
test('save rejection blocks submission instead of losing an answer',async t=>{
 const p=await page();t.after(p.close);p.$('#gep-submit-btn').trigger('click');p.w.document.querySelector('[data-action="submit"]').click();await resolve(p.fetches[0],{success:false,data:{message:'Session expired'}});
 assert.equal(p.fetches.length,1);assert.match(p.$('[role="dialog"]').text(),/not reached the server/);assert.deepEqual(p.errors,[]);
});
test('exam confirmation supports cancel by Escape and restores focus',async t=>{
 const p=await page();t.after(p.close);p.w.document.getElementById('gep-submit-btn').focus();p.$('#gep-submit-btn').trigger('click');
 p.w.document.activeElement.dispatchEvent(new p.w.KeyboardEvent('keydown',{key:'Escape',bubbles:true}));assert.equal(p.$('[role="dialog"]').length,0);assert.equal(p.w.document.activeElement.id,'gep-submit-btn');assert.equal(p.fetches.length,0);
});

test('lost submission response retries submission without writing to a potentially closed attempt',async t=>{
 const p=await page();t.after(p.close);p.$('#gep-submit-btn').trigger('click');p.w.document.querySelector('[data-action="submit"]').click();
 await resolve(p.fetches[0]);assert.equal(p.fetches[1].data.action,'gep_submit_exam');
 p.fetches[1].reject(new Error('Response lost'));await tick();
 assert.equal(p.$('[data-action="cancel"]').length,0);
 p.w.document.querySelector('[data-action="retry"]').click();
 assert.equal(p.fetches[2].data.action,'gep_submit_exam');
 await resolve(p.fetches[2],{success:true,data:{redirect_url:'https://portal.example/dashboard/'}});
 assert.equal(p.w.onbeforeunload,null);assert.deepEqual(p.errors,[]);
});
