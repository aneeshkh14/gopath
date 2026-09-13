const {test}=require('node:test'); const assert=require('node:assert/strict'); const {setup,inline,tick}=require('./helpers.cjs');
const otp=`<form id="gep-otp-form"><input id="gep_nonce" value="test"><input id="gep_otp_uid" value="5"><input id="gep_otp_code"><button type="submit" id="gep-verify-otp-btn">Verify & Login</button><p id="gep-otp-error" role="alert"></p></form>`;
test('OTP validation, paste formatting, duplicate prevention and network recovery',async t=>{
 const p=await setup(otp,'public/js/gep-auth.js');t.after(p.close);
 p.$('#gep_otp_code').val('ab123').trigger('input');assert.equal(p.$('#gep_otp_code').val(),'123');p.$('#gep-otp-form').trigger('submit');assert.equal(p.requests.length,0);assert.equal(p.$('#gep_otp_code').attr('aria-invalid'),'true');
 p.$('#gep_otp_code').val('123456').trigger('input');p.$('#gep-otp-form').trigger('submit').trigger('submit');assert.equal(p.requests.length,1);assert.equal(p.requests[0].options.data.otp,'123456');p.requests[0].reject();
 assert.equal(p.$('#gep-verify-otp-btn').prop('disabled'),false);assert.match(p.$('#gep-otp-error').text(),/try again/);assert.equal(p.$('#gep_otp_code').val(),'123456');assert.deepEqual(p.errors,[]);
});
test('OTP wrong-code response retains code and permits retry',async t=>{
 const p=await setup(otp,'public/js/gep-auth.js');t.after(p.close);p.$('#gep_otp_code').val('123456');p.$('#gep-otp-form').trigger('submit');p.requests[0].resolve({success:false,data:{message:'Incorrect code'}});assert.equal(p.$('#gep_otp_code').attr('aria-invalid'),'true');p.$('#gep-otp-form').trigger('submit');assert.equal(p.requests.length,2);
});
test('password visibility toggle is a button and does not submit form',async t=>{
 const p=await setup('<div class="gep-auth-card"><form><input type="password" id="pwd"></form></div>','public/js/gep-auth.js');t.after(p.close);p.$('.gep-auth-password-toggle').trigger('click');assert.equal(p.$('#pwd').attr('type'),'text');assert.equal(p.$('.gep-auth-password-toggle').attr('aria-pressed'),'true');p.$('.gep-auth-password-toggle').trigger('click');assert.equal(p.$('#pwd').attr('type'),'password');
});
const instructions=`<div id="gep-random-selector-wrap"><div id="gep-subject-tabs"></div><div id="gep-topics-container"></div><span id="gep-total-selected-q">0</span></div><input type="checkbox" id="agree-terms"><button id="start-exam-btn" disabled>Start test</button>`;
test('custom test requires topics and consent, handles string IDs and excludes empty topics',async t=>{
 const p=await setup(instructions,'public/js/gep-instructions.js',{GEP_Instructions:{ajaxurl:'/ajax',test_id:5,nonce:'test'}});t.after(p.close);
 p.requests[0].resolve({success:true,data:[{id:'2',name:'Sanskrit',topics:[{id:1,name:'Sandhi',question_count:3},{id:2,name:'Empty',question_count:0}]}]});
 assert.equal(p.$('.gep-topic-cb').length,1);p.$('#agree-terms').prop('checked',true).trigger('change');assert.equal(p.$('#start-exam-btn').prop('disabled'),true);
 p.$('.gep-topic-cb').prop('checked',true).trigger('change');assert.equal(p.$('.gep-topic-count').val(),'3');assert.equal(p.$('#start-exam-btn').prop('disabled'),false);
 p.$('#start-exam-btn').trigger('click');assert.equal(p.requests[1].options.data.action,'gep_start_exam');p.requests[1].reject();assert.equal(p.$('#start-exam-btn').prop('disabled'),false);assert.match(p.$('#gep-start-error').text(),/try again/);assert.deepEqual(p.errors,[]);
});
test('custom test config network error replaces permanent loading with retry',async t=>{
 const p=await setup(instructions,'public/js/gep-instructions.js',{GEP_Instructions:{ajaxurl:'/ajax'}});t.after(p.close);p.requests[0].reject();assert.equal(p.$('#gep-subject-tabs button').text(),'Retry loading subjects');assert.equal(p.$('#start-exam-btn').prop('disabled'),true);
});

test('custom test selections survive subject changes and all chosen topics are submitted',async t=>{
 const p=await setup(instructions,'public/js/gep-instructions.js',{GEP_Instructions:{ajaxurl:'/ajax',test_id:5,nonce:'test'}});t.after(p.close);
 p.requests[0].resolve({success:true,data:[{id:1,name:'Math',topics:[{id:10,name:'Algebra',question_count:12}]},{id:2,name:'English',topics:[{id:20,name:'Grammar',question_count:8}]}]});
 p.$('.gep-topic-cb').prop('checked',true).trigger('change');p.$('.gep-topic-count').val('10').trigger('input');p.$('.gep-sub-tab[data-id="2"]').trigger('click');p.$('.gep-topic-cb').prop('checked',true).trigger('change');assert.equal(p.$('#gep-total-selected-q').text(),'15');
 p.$('.gep-sub-tab[data-id="1"]').trigger('click');assert.equal(p.$('.gep-topic-cb').prop('checked'),true);assert.equal(p.$('.gep-topic-count').val(),'10');assert.equal(p.$('.gep-sub-tab[data-id="1"]').attr('aria-pressed'),'true');
 p.$('#agree-terms').prop('checked',true).trigger('change');p.$('#start-exam-btn').trigger('click');assert.deepEqual(JSON.parse(p.requests[1].options.data.selected_topics),[{topic_id:'10',count:10},{topic_id:'20',count:5}]);
});
test('editing a custom count allows clearing it and disables start until it is valid',async t=>{
 const p=await setup(instructions,'public/js/gep-instructions.js',{GEP_Instructions:{ajaxurl:'/ajax',test_id:5,nonce:'test'}});t.after(p.close);p.requests[0].resolve({success:true,data:[{id:1,name:'Math',topics:[{id:10,name:'Algebra',question_count:12}]}]});p.$('.gep-topic-cb').prop('checked',true).trigger('change');p.$('#agree-terms').prop('checked',true).trigger('change');
 p.$('.gep-topic-count').val('').trigger('input');assert.equal(p.$('.gep-topic-count').val(),'');assert.equal(p.$('#start-exam-btn').prop('disabled'),true);p.$('.gep-topic-count').val('10').trigger('input');assert.equal(p.$('#start-exam-btn').prop('disabled'),false);p.$('.gep-topic-count').val('2.5').trigger('input');assert.equal(p.$('#start-exam-btn').prop('disabled'),true);
});
test('incomplete subject configuration and start responses leave useful retry states',async t=>{
 const p=await setup(instructions,'public/js/gep-instructions.js',{GEP_Instructions:{ajaxurl:'/ajax'}});t.after(p.close);p.requests[0].resolve(null);assert.match(p.$('#gep-subject-tabs').text(),/No subjects/);assert.equal(p.$('#start-exam-btn').prop('disabled'),true);
 const q=await setup('<input type="checkbox" id="agree-terms" checked><button id="start-exam-btn">Start</button>','public/js/gep-instructions.js',{GEP_Instructions:{ajaxurl:'/ajax'}});t.after(q.close);q.$('#start-exam-btn').trigger('click');q.requests[0].resolve(null);assert.equal(q.$('#start-exam-btn').prop('disabled'),false);assert.match(q.$('#gep-start-error').text(),/Failed/);assert.deepEqual(q.errors,[]);
});
