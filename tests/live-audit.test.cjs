const {test}=require('node:test');
const assert=require('node:assert/strict');
const {setup,tick}=require('./helpers.cjs');
const markup='<div id="gep-secure-init-overlay"><div class="gep-secure-init-card"><p id="gep-fullscreen-feedback" role="alert" hidden></p><button id="gep-enter-secure-btn">Start</button></div></div>';
for(const failure of ['unsupported','rejected','thrown'])test(`fullscreen ${failure} gives feedback and preserves the exam gate`,async t=>{
 const p=await setup(markup,'public/js/gep-secure.js',{GEP_Exam:{attempt_id:1}},w=>{
  if(failure==='rejected')w.document.documentElement.requestFullscreen=()=>Promise.reject(new Error('Denied'));
  if(failure==='thrown')w.document.documentElement.requestFullscreen=()=>{throw new Error('Denied');};
 });t.after(p.close);
 p.w.document.getElementById('gep-enter-secure-btn').click();await tick();
 const feedback=p.w.document.getElementById('gep-fullscreen-feedback');
 assert.equal(feedback.hidden,false);assert.match(feedback.textContent,/timer is already running/);
 assert.notEqual(p.w.document.getElementById('gep-secure-init-overlay').style.display,'none');
 assert.deepEqual(p.errors,[]);
});
test('successful fullscreen retry clears feedback and opens the exam only on fullscreen change',async t=>{
 let denied=true;
 const p=await setup(markup,'public/js/gep-secure.js',{GEP_Exam:{attempt_id:1}},w=>{
  w.document.documentElement.requestFullscreen=()=>denied?Promise.reject(new Error('Denied')):Promise.resolve();
 });t.after(p.close);
 const button=p.w.document.getElementById('gep-enter-secure-btn');button.click();await tick();
 denied=false;button.click();await tick();
 assert.equal(p.w.document.getElementById('gep-fullscreen-feedback').hidden,true);
 assert.notEqual(p.w.document.getElementById('gep-secure-init-overlay').style.display,'none');
 Object.defineProperty(p.w.document,'fullscreenElement',{value:p.w.document.documentElement});
 p.w.document.dispatchEvent(new p.w.Event('fullscreenchange'));
 assert.equal(p.w.document.getElementById('gep-secure-init-overlay').style.display,'none');
 assert.deepEqual(p.errors,[]);
});
