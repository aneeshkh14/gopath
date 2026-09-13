const {test}=require('node:test');const assert=require('node:assert/strict');const {setup,tick}=require('./helpers.cjs');
const html='<div id="gep-exam-main-container"><div><span id="gep-timer"></span></div>'+[0,1,2].map(i=>`<button class="gep-section-tab" data-cat-id="sec_${i}">Section ${i+1}</button><div class="gep-question-block" data-id="${i+1}" data-cat-id="sec_${i}"><div class="gep-options-container" data-qtype="short_answer"><input class="gep-text-ans"></div></div><button class="gep-palette-btn not-visited" data-id="${i+1}" data-index="${i}" data-cat-id="sec_${i}">${i+1}</button>`).join('')+'</div>';
async function page({elapsed=0,remaining=600,sections=[{time_limit:1},{time_limit:1},{time_limit:0}]}={}) {
 const requests=[],intervals=new Map();let now=1000000,id=0;
 const p=await setup(html,'public/js/gep-exam.js',{GEP_Exam:{ajaxurl:'/ajax',nonce:'test',attempt_id:55,elapsed_seconds:elapsed,remaining_seconds:remaining,sections_data:sections,lang:'en',saved_answers:{}}},w=>{
 w.Date.now=()=>now;w.setInterval=(fn,ms)=>{intervals.set(++id,{fn,ms});return id;};w.clearInterval=id=>intervals.delete(id);
 w.fetch=(url,o)=>new Promise((resolve,reject)=>requests.push({data:Object.fromEntries(o.body.entries()),resolve:data=>resolve({ok:true,json:async()=>data}),reject}));
 });p.fetches=requests;p.intervals=intervals;p.advance=async seconds=>{now+=seconds*1000;for(const timer of [...intervals.values()])if(timer.ms===1000)timer.fn();await tick();};p.heartbeat=()=>[...intervals.values()].find(x=>x.ms===30000).fn();return p;
}
const ok=async r=>{r.resolve({success:true,data:{}});await tick();};
test('resuming a timed attempt opens its current section and correct countdown',async t=>{
 const p=await page({elapsed:90,remaining:510});t.after(p.close);assert.equal(p.$('#gep-timer').text(),'00:00:30');
 assert.equal(p.$('.gep-question-block[data-id="2"]')[0].style.display,'block');assert.equal(p.$('.gep-section-tab.active').data('cat-id'),'sec_1');assert.deepEqual(p.errors,[]);
});
test('untimed section uses remaining exam time without subtracting elapsed twice',async t=>{
 const p=await page({elapsed:150,remaining:450});t.after(p.close);assert.equal(p.$('#gep-timer').text(),'00:07:30');assert.equal(p.$('.gep-question-block[data-id="3"]')[0].style.display,'block');assert.deepEqual(p.errors,[]);
});
test('section transition captures unblurred text and changes section without waiting for a click',async t=>{
 const p=await page();t.after(p.close);p.$('.gep-question-block[data-id="1"] input').val('draft answer');await p.advance(61);
 assert.equal(p.fetches[0].data.answer,'draft answer');assert.equal(p.$('.gep-question-block[data-id="2"]')[0].style.display,'block');assert.equal(p.$('#gep-timer').text(),'00:00:59');assert.equal([...p.intervals.values()].filter(x=>x.ms===30000).length,1);await ok(p.fetches[0]);
});
test('global heartbeat updates the clock without replacing the sectional countdown',async t=>{
 const p=await page({elapsed:90,remaining:510});t.after(p.close);const done=p.heartbeat();assert.equal(p.fetches[0].data.action,'gep_exam_heartbeat');p.fetches[0].resolve({success:true,data:{remaining_seconds:500}});await done;
 assert.equal(p.$('#gep-timer').text(),'00:00:20');assert.deepEqual(p.errors,[]);
});
test('all timed sections expired on reload submits without granting another section',async t=>{
 const p=await page({elapsed:180,remaining:420,sections:[{time_limit:1},{time_limit:1}]});t.after(p.close);assert.equal(p.$('#gep-timer').text(),'00:00:00');assert.equal(p.fetches[0].data.action,'gep_save_answer');await ok(p.fetches[0]);assert.equal(p.fetches[1].data.action,'gep_submit_exam');assert.deepEqual(p.errors,[]);
});
test('device sleep past total expiry starts one automatic submission and permits only retry on save failure',async t=>{
 const p=await page({remaining:5,sections:[]});t.after(p.close);await p.advance(10);await p.advance(10);assert.equal(p.fetches.length,1);p.fetches[0].reject(new Error('Offline'));await tick();assert.equal(p.$('[data-action="cancel"]').length,0);assert.equal(p.$('[data-action="retry"]').length,1);assert.equal(p.$('#gep-timer').text(),'00:00:00');
});
