const {test}=require('node:test');const assert=require('node:assert/strict');const {setup,tick,inline}=require('./helpers.cjs');
test('profile reveals the section containing an invalid field and retains data after save failure',async t=>{
 const p=await setup(`<nav class="gep-profile-nav"><a data-tab="personal">Personal</a><a data-tab="security">Security</a></nav><form id="gep-profile-update-form"><section id="tab-personal" class="gep-form-section active"><input name="name" value="Student"></section><section id="tab-security" class="gep-form-section"><input id="gep-new-password" name="new_password" type="password"></section><button type="submit"><span class="gep-btn-text">Save</span><span class="gep-spinner"></span></button></form><div id="gep-profile-msg"></div>`,inline('templates/dashboard/profile.php','Password Toggle'));t.after(p.close);
 p.w.document.getElementById('gep-new-password').dispatchEvent(new p.w.Event('invalid'));
 assert.equal(p.$('#tab-security').hasClass('active'),true);
 p.$('#gep-profile-update-form').trigger('submit');p.$('#gep-profile-update-form').trigger('submit');assert.equal(p.requests.length,1);
 p.requests[0].resolve({success:false,data:{message:'<b>Session expired</b>'}});
 assert.equal(p.$('#gep-profile-msg b').length,0);assert.match(p.$('#gep-profile-msg').text(),/Session expired/);
 assert.equal(p.$('button[type="submit"]').prop('disabled'),false);assert.equal(p.$('[name="name"]').val(),'Student');assert.deepEqual(p.errors,[]);
});
test('admin question retrieval failure releases the retry control',async t=>{
 const p=await setup('<button id="gep_btn_fetch_questions">Inject IDs</button><select id="gep_fetch_cat"><option value="1">Math</option></select><input id="gep_fetch_count" value="5">','admin/js/gep-admin.js',{ajaxurl:'/ajax'});t.after(p.close);
 p.$('#gep_btn_fetch_questions').trigger('click');assert.equal(p.$('#gep_btn_fetch_questions').prop('disabled'),true);p.requests[0].reject();assert.equal(p.$('#gep_btn_fetch_questions').prop('disabled'),false);assert.match(p.w.lastAlert,/connection/);assert.deepEqual(p.errors,[]);
});
test('admin dialog receives a name, focus and restores its opener on close',async t=>{
 const p=await setup('<button id="open">Edit</button><div id="edit" class="gep-modal-overlay" style="display:none"><div class="gep-modal-content"><h2>Edit course</h2><button id="close">Close</button><input></div></div>','admin/js/gep-admin.js');t.after(p.close);
 p.w.document.getElementById('open').focus();p.w.document.getElementById('open').click();p.$('#edit').show();await tick();
 assert.equal(p.$('[role="dialog"]').attr('aria-labelledby'),'edit-heading');assert.equal(p.w.document.activeElement.id,'close');p.$('#edit').hide();await tick();assert.equal(p.w.document.activeElement.id,'open');assert.deepEqual(p.errors,[]);
});
test('student enrollment ignores stale student data and preserves selections on save failure',async t=>{
 const script=inline('admin/views/students.php','Assign Access Button').replace(/<\?php[\s\S]*?\?>/g,'test-nonce');
 const p=await setup('<button class="gep-assign-access-btn" data-userid="1" data-username="One"></button><button class="gep-assign-access-btn" data-userid="2" data-username="Two"></button><div id="gep-assign-access-modal"><h2 id="assign-modal-title"></h2><form id="gep-assign-access-form"><input id="assign-student-id"><div id="assign-courses-list"></div><div id="assign-tests-list"></div><button type="submit">Save</button></form></div>',script,{ajaxurl:'/ajax'});t.after(p.close);
 p.$('[data-userid="1"]').trigger('click');p.$('[data-userid="2"]').trigger('click');assert.equal(p.$('button[type="submit"]').prop('disabled'),true);
 const data={courses:[{id:7,title:'Math'}],tests:[],current_courses:['7'],current_tests:[]};
 p.requests[0].resolve({success:true,data});assert.equal(p.$('button[type="submit"]').prop('disabled'),true);
 p.requests[1].resolve({success:true,data});assert.equal(p.$('[name="course_ids[]"]').prop('checked'),true);
 p.$('#gep-assign-access-form').trigger('submit');assert.equal(p.requests[2].options.data.student_id,'2');p.requests[2].reject();assert.equal(p.$('button[type="submit"]').prop('disabled'),false);assert.equal(p.$('[name="course_ids[]"]').prop('checked'),true);assert.deepEqual(p.errors,[]);
});

test('malformed profile response retains form data and releases Save',async t=>{
 const p=await setup('<form id="gep-profile-update-form"><input name="name" value="Student"><button type="submit"><span class="gep-btn-text">Save</span><span class="gep-spinner"></span></button></form><div id="gep-profile-msg"></div>',inline('templates/dashboard/profile.php','Password Toggle'));t.after(p.close);p.$('#gep-profile-update-form').trigger('submit');p.requests[0].resolve(null);assert.equal(p.$('button[type="submit"]').prop('disabled'),false);assert.equal(p.$('[name="name"]').val(),'Student');assert.match(p.$('#gep-profile-msg').text(),/Failed/);
});
test('avatar upload prevents races, reports errors inline and lets the same file retry',async t=>{
 const p=await setup('<form id="gep-profile-update-form"></form><div class="gep-profile-avatar"><img src="old.png"></div><input id="gep-avatar-upload" type="file"><input id="gep_profile_nonce" value="test">',inline('templates/dashboard/profile.php','Password Toggle'));t.after(p.close);const input=p.w.document.getElementById('gep-avatar-upload');Object.defineProperty(input,'files',{value:[new p.w.File(['photo'],'avatar.png',{type:'image/png'})]});p.$(input).trigger('change').trigger('change');assert.equal(p.requests.length,1);assert.equal(input.disabled,true);p.requests[0].reject();assert.equal(input.disabled,false);assert.match(p.$('#gep-avatar-status').text(),/choose the file again/);assert.equal(p.w.lastAlert,undefined);p.$(input).trigger('change');p.requests[1].resolve({success:true,data:{image_url:'new.png'}});assert.equal(p.$('.gep-profile-avatar img').attr('src'),'new.png');assert.match(p.$('#gep-avatar-status').text(),/updated/);assert.deepEqual(p.errors,[]);
});
