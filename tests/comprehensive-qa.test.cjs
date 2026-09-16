const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const cssom=require('@acemir/cssom');
const {JSDOM}=require('jsdom');
const {setup,inline}=require('./helpers.cjs');
test('typing duration selection follows keyboard changes and stays unique',async t=>{
 const markup='<div class="gep-typing-durations">'+[60,120,300].map((n,i)=>`<label class="gep-dur-label ${i?'':'is-selected'}"><input type="radio" name="gep_typing_dur" value="${n}" ${i?'':'checked'}></label>`).join('')+'</div>';
 const p=await setup(markup,inline('templates/dashboard/typing-test.php','const paragraphs').replace(/<\?php[\s\S]*?\?>/g,'fixture'));t.after(p.close);
 for(const n of [120,300,60]){p.$(`input[value="${n}"]`).prop('checked',true).trigger('change');assert.equal(p.$('.is-selected').length,1);assert.equal(p.$('.is-selected input').val(),String(n));}
 assert.deepEqual(p.errors,[]);
});
const courseMarkup='<button data-gep-create-course="1">New</button><div id="gep-course-modal"><h2 id="gep-course-modal-title">Edit Course</h2><form id="gep-course-form"><input name="course_id" type="hidden" value="5"><input name="title" value="Old title"><textarea name="description">Old description</textarea><select id="gep_course_category_id"><option value="0">None</option><option value="1">One</option><option value="2" selected>Two</option></select><select id="gep_course_subcategory_id"><option value="5">Old child</option></select><button type="submit">Save</button></form></div>';
async function course(t){const p=await setup(courseMarkup,inline('admin/views/courses.php','categoryRevision'),{ajaxurl:'/ajax',gepAdminAjax:{nonce:'fixture'}});t.after(p.close);return p;}
test('creating after editing clears the course id and existing values',async t=>{
 const p=await course(t);p.$('[data-gep-create-course]').trigger('click');assert.equal(p.$('[name="course_id"]').val(),'0');assert.equal(p.$('[name="title"]').val(),'');assert.equal(p.$('textarea').val(),'');assert.equal(p.$('#gep-course-modal-title').text(),'New Course');assert.equal(p.$('button[type="submit"]').prop('disabled'),false);
});
test('course categories ignore stale responses and render names as text',async t=>{
 const p=await course(t);p.$('#gep_course_category_id').val('1').trigger('change');p.$('#gep_course_category_id').val('2').trigger('change');assert.equal(p.$('button[type="submit"]').prop('disabled'),true);
 p.requests[1].resolve({success:true,data:[{id:8,name:'<img src=x onerror=alert(1)>'}]});p.requests[0].resolve({success:true,data:[{id:7,name:'Stale'}]});assert.equal(p.$('#gep_course_subcategory_id option').length,2);assert.match(p.$('#gep_course_subcategory_id').text(),/<img/);assert.equal(p.$('img').length,0);assert.equal(p.$('option[value="7"]').length,0);assert.equal(p.$('button[type="submit"]').prop('disabled'),false);
});
test('course category failure restores usable controls',async t=>{
 const p=await course(t);p.$('#gep_course_category_id').val('1').trigger('change');p.requests[0].reject();assert.equal(p.$('button[type="submit"]').prop('disabled'),false);assert.equal(p.$('#gep_course_subcategory_id').prop('disabled'),false);assert.match(p.$('#gep_course_subcategory_id').text(),/Unable to load/);
});
function rulesAt(list,width){return Array.from(list).map(r=>{if(!r.media)return r.cssText;const q=r.media.mediaText;if(q.includes('prefers-'))return '';const min=q.match(/min-width:\s*(\d+)px/),max=q.match(/max-width:\s*(\d+)px/);return (min&&width<+min[1])||(max&&width>+max[1])?'':rulesAt(r.cssRules,width);}).join('\n');}
const typingCss=[...fs.readFileSync('templates/dashboard/typing-test.php','utf8').matchAll(/<style>([\s\S]*?)<\/style>/g)].map(m=>m[1]).join('\n');
const resultCss=[...fs.readFileSync('templates/exam/result-review.php','utf8').matchAll(/<style>([\s\S]*?)<\/style>/g)].map(m=>m[1]).join('\n').replace(/<\?php[\s\S]*?\?>/g,'#fff');
for(const width of [320,390,600,768,820,1024,1366,1920])test(`typing and result CSS adapt to ${width}px`,t=>{
 const css=[typingCss,resultCss].map(c=>rulesAt(cssom.parse(c).cssRules,width)).join('\n');
 const dom=new JSDOM(`<style>${css}</style><div class="gep-typing-grid"><div class="gep-typing-panel"><div class="gep-typing-durations"><label class="gep-dur-label is-selected">One minute</label></div><div class="gep-typing-summary" style="grid-template-columns:repeat(4, 1fr)"></div><div class="gep-typing-stats"></div></div></div><div class="gep-difficulty-row"></div>`);t.after(()=>dom.window.close());const style=s=>dom.window.getComputedStyle(dom.window.document.querySelector(s));
 assert.equal(style('.gep-typing-grid').gridTemplateColumns,width<=1024?'1fr':'1.8fr 1.2fr');assert.equal(style('.gep-typing-durations').flexWrap,'wrap');assert.equal(style('.gep-typing-stats').flexWrap,'wrap');assert.equal(style('.gep-difficulty-row').gridTemplateColumns,width<=480?'1fr':'repeat(3, minmax(0, 1fr))');
});
