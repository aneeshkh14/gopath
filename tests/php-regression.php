<?php
require __DIR__.'/php-fixtures.php';
$passed=0;$failed=0;
function scenario($name,$fn){
 global $passed,$failed,$wpdb,$uid,$options,$transients,$mail_ok,$cookie;
 $wpdb=new TestDb();$uid=7;$options=['gep_enable_otp'=>'yes','gep_razorpay_key_secret'=>base64_encode('test-secret')];$transients=[];$mail_ok=true;$cookie=null;$_POST=[];$_GET=[];
 try{$fn();$passed++;echo "PASS $name\n";}catch(Throwable $e){$failed++;echo "FAIL $name: {$e->getMessage()}\n";}
}
scenario('OTP is emailed, single use and has the same advertised expiry',function(){
 $auth=new GEP_Auth();expect($auth->generate_otp(7)===123456);expect(strpos($GLOBALS['mail'][2],'123456')!==false);expect(strpos($GLOBALS['mail'][2],'5 minutes')!==false);expect($auth->verify_otp(7,'123456'));expect(!$auth->verify_otp(7,'123456'));
});
scenario('OTP delivery failure removes the pending code and login',function(){
 $GLOBALS['mail_ok']=false;set_transient('gep_pending_login_7',['remember'=>false],300);expect(is_wp_error((new GEP_Auth())->generate_otp(7)));expect(get_transient('gep_otp_7')===false);expect(get_transient('gep_pending_login_7')===false);
});
scenario('five wrong OTPs invalidate the code and require password login again',function(){
 $auth=new GEP_Auth();$auth->generate_otp(7);set_transient('gep_pending_login_7',['remember'=>false],300);
 for($i=0;$i<5;$i++)expect(!$auth->verify_otp(7,'000000'));expect(!$auth->verify_otp(7,'123456'));expect(get_transient('gep_pending_login_7')===false);
});
scenario('OTP login works with Remember me unchecked',function(){
 $_POST=['gep_nonce'=>'test','log'=>'student','pwd'=>'test-fixture'];
 try{(new GEP_Auth())->handle_login();}catch(RedirectResponse $r){expect(strpos($r->getMessage(),'view=otp')!==false);}
 expect(get_transient('gep_pending_login_7')===['remember'=>false]);$_POST=['otp'=>'123456','user_id'=>7];$r=response(function(){(new GEP_AJAX())->gep_verify_otp();});expect($r->success);expect($GLOBALS['cookie']===[7,false]);
});
scenario('expired OTP session cannot create an auth cookie',function(){
 $_POST=['otp'=>'123456','user_id'=>7];$r=response(function(){(new GEP_AJAX())->gep_verify_otp();});expect(!$r->success);expect($GLOBALS['cookie']===null);
});
scenario('heartbeat converts site timezone to UTC correctly',function(){
 global $wpdb;$_POST=['attempt_id'=>1];$local=(new DateTimeImmutable('@'.(time()-90)))->setTimezone(new DateTimeZone('Asia/Kolkata'))->format('Y-m-d H:i:s');
 $wpdb->rows[]=(object)['start_time'=>$local,'duration_minutes'=>10,'user_id'=>7,'status'=>'in_progress'];$r=response(function(){(new GEP_AJAX())->gep_exam_heartbeat();});expect($r->success);expect(abs($r->data['remaining_seconds']-510)<=1);
});
scenario('heartbeat refuses another student attempt',function(){
 global $wpdb;$_POST=['attempt_id'=>1];$wpdb->rows[]=(object)['user_id'=>99,'status'=>'in_progress'];expect(!response(function(){(new GEP_AJAX())->gep_exam_heartbeat();})->success);
});
scenario('database error during answer save remains an error',function(){
 global $wpdb;$row=(object)['user_id'=>7,'status'=>'in_progress','answers'=>'{}','start_time'=>date('Y-m-d H:i:s'),'duration_minutes'=>10];$wpdb->rows=[$row,$row,$row];$wpdb->update_result=false;
 $_POST=['attempt_id'=>1,'question_id'=>2,'answer'=>'A'];expect(!response(function(){(new GEP_AJAX())->gep_save_answer();})->success);
});
scenario('zero changed rows is an acknowledged answer save',function(){
 global $wpdb;$row=(object)['user_id'=>7,'status'=>'in_progress','answers'=>'{}','start_time'=>date('Y-m-d H:i:s'),'duration_minutes'=>10];$wpdb->rows=[$row,$row,$row];$wpdb->update_result=0;
 $_POST=['attempt_id'=>1,'question_id'=>2,'answer'=>'A'];expect(response(function(){(new GEP_AJAX())->gep_save_answer();})->success);
});
scenario('submitted attempts reject further answer changes inside the engine',function(){
 global $wpdb;$wpdb->rows[]=(object)['user_id'=>7,'status'=>'submitted'];expect((new GEP_Exam_Engine())->save_answer(1,2,'B')===false);expect(!$wpdb->updates);
});
scenario('numerical grading rejects text that PHP would coerce to zero',function(){
 $q=(object)['question_type'=>'numerical','correct_answer'=>'0'];expect(!GEP_Exam_Engine::evaluate_answer($q,'abc'));expect(!GEP_Exam_Engine::evaluate_answer($q,'0foo'));expect(GEP_Exam_Engine::evaluate_answer($q,'0'));
});
scenario('short answers are not transformed into MCQ option aliases',function(){
 $q=(object)['question_type'=>'short_answer','correct_answer'=>'A'];expect(!GEP_Exam_Engine::evaluate_answer($q,'1'));expect(GEP_Exam_Engine::evaluate_answer($q,' a '));
});
scenario('MSQ option order and Devanagari aliases remain supported',function(){
 $q=(object)['question_type'=>'msq','correct_answer'=>'A,C'];expect(GEP_Exam_Engine::evaluate_answer($q,'३,१'));expect(!GEP_Exam_Engine::evaluate_answer($q,'A,B'));
});
scenario('replayed verified payment does not grant attempts or extend a pass again',function(){
 global $wpdb;$wpdb->rows[]=(object)['id'=>4,'user_id'=>7,'status'=>'success'];$signature=hash_hmac('sha256','order_test|pay_test','test-secret');expect((new GEP_Payment())->verify_payment('order_test','pay_test',$signature));expect(!$wpdb->updates && !$wpdb->inserts);expect($wpdb->queries===['START TRANSACTION','COMMIT']);
});
scenario('payment verification refuses another user order',function(){
 global $wpdb;$wpdb->rows[]=(object)['id'=>4,'user_id'=>99,'status'=>'success'];$signature=hash_hmac('sha256','order_test|pay_test','test-secret');expect(!(new GEP_Payment())->verify_payment('order_test','pay_test',$signature));expect(!$wpdb->updates);
});
scenario('invalid signature cannot modify any order',function(){
 global $wpdb;expect(!(new GEP_Payment())->verify_payment('order_test','pay_test','invalid'));expect(!$wpdb->updates && !$wpdb->inserts);
});
scenario('coupon that expired after preview blocks order creation',function(){
 global $wpdb;$wpdb->rows=[(object)['price'=>100],null];expect(is_wp_error((new GEP_Payment())->create_order(2,'test','EXPIRED')));expect(!$wpdb->inserts);
});
scenario('unmapped payment cannot be attached to a different pending order',function(){
 global $wpdb;$wpdb->rows=[null];$signature=hash_hmac('sha256','order_missing|pay_test','test-secret');expect(!(new GEP_Payment())->verify_payment('order_missing','pay_test',$signature,2,'test'));expect(!$wpdb->updates);expect(in_array('ROLLBACK',$wpdb->queries,true));
});
scenario('wrong item cannot be verified against a different purchased item',function(){
 global $wpdb;$wpdb->rows[]=(object)['id'=>4,'user_id'=>7,'item_id'=>9,'item_type'=>'test','status'=>'success'];$signature=hash_hmac('sha256','order_test|pay_test','test-secret');expect(!(new GEP_Payment())->verify_payment('order_test','pay_test',$signature,2,'test'));expect(!$wpdb->updates);
});
echo "$passed PHP scenarios passed; $failed failed.\n";exit($failed?1:0);
