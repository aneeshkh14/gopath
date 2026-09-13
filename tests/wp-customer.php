<?php
// Loaded by wp-screens.php only against the disposable real WordPress database.
require_once __DIR__.'/../admin/class-gep-admin-tests.php';
function customer_assert($ok, $message) { if (!$ok) throw new RuntimeException($message); }
function customer_paper($extra = array()) {
    return array_merge(array('title'=>'Customer regression paper','slug'=>'customer-'.wp_generate_uuid4(),'type'=>'single','status'=>'publish','is_free'=>1,'price'=>0,'duration_minutes'=>10,'attempt_limit'=>0,'translated_data'=>'{}'),$extra);
}
function customer_attempt($test_id, $questions='901,902', $meta=array()) {
    global $student;
    $id=(new GEP_Exam_Engine())->start_attempt($test_id,$student,array('question_ids'=>$questions,'analytics_data'=>wp_json_encode($meta)));
    customer_assert(!is_wp_error($id) && $id > 0,'Could not start test.');return $id;
}
function customer_fork($callbacks) {
    global $wpdb;
    $wpdb->close();$children=[];
    foreach($callbacks as $fn) {
        $pid=pcntl_fork();if($pid===-1)throw new RuntimeException('Could not fork.');
        if($pid===0) {$wpdb->db_connect();try {$fn();$ok=true;}catch(Throwable $e){fwrite(STDERR,$e->getMessage()."\n");$ok=false;}while(ob_get_level())ob_end_clean();exit($ok?0:1);}
        $children[]=$pid;
    }
    foreach($children as $pid){pcntl_waitpid($pid,$status);customer_assert(pcntl_wifexited($status) && pcntl_wexitstatus($status)===0,'Concurrent worker failed.');}
    $wpdb->db_connect();
}
class CustomerJsonExit extends Error {}
function customer_ajax($method,$post) {
    if(!defined('DOING_AJAX'))define('DOING_AJAX',true);
    $_POST=array_merge($post,['nonce'=>wp_create_nonce('gep_exam_nonce')]);$_REQUEST=$_POST;
    $handler=static function(){return static function(){throw new CustomerJsonExit();};};
    add_filter('wp_die_ajax_handler',$handler);ob_start();
    try {(new GEP_AJAX())->$method();}catch(CustomerJsonExit $e){}finally{$json=ob_get_clean();remove_filter('wp_die_ajax_handler',$handler);}
    $response=json_decode($json,true);customer_assert(is_array($response),'Invalid AJAX JSON: '.$json);return $response;
}
$engine=new GEP_Exam_Engine();$admin_tests=new GEP_Admin_Tests();
if ($view==='practice-lifecycle') {
    $meta=['practice_title'=>'2025 PYQ fixture','type'=>'year','target'=>'2025','duration'=>24];
    $id=customer_attempt(999999,'901,902',$meta);
    customer_assert($engine->save_answer($id,901,'A')!==false && $engine->save_answer($id,902,'0')!==false,'Practice responses failed.');
    $heartbeat=customer_ajax('gep_exam_heartbeat',['attempt_id'=>$id]);customer_assert($heartbeat['success'] && $heartbeat['data']['remaining_seconds']>1400,'Practice timer lost its duration.');
    customer_assert($engine->submit_exam($id)===true,'Virtual practice submission failed.');
    $result=(new GEP_Result())->get_attempt_result($id);$snapshot=json_decode($result->analytics_data,true);
    customer_assert((float)$result->score===4.0 && $snapshot['practice_title']===$meta['practice_title'] && $snapshot['duration']===24,'Practice result or metadata lost.');
    customer_assert($engine->save_answer($id,901,'B')===false,'Submitted practice accepted changes.');
    $details=(new GEP_Result())->get_attempt_details($id);customer_assert($details['test']->title===$meta['practice_title'],'Review opened a different practice title.');
} elseif ($view==='practice-retry') {
    $meta=['type'=>'year','target'=>'2024','duration'=>15];$id=customer_attempt(999999,'901,902',$meta);
    $again=customer_attempt(999999,'902,901',$meta);customer_assert($again===$id,'Retry created another practice attempt.');
    customer_assert($wpdb->get_var($wpdb->prepare("SELECT question_ids FROM {$wpdb->prefix}gep_attempts WHERE id=%d",$id))==='901,902','Retry replaced the questions.');$engine->submit_exam($id);
} elseif ($view==='practice-different') {
    $id=customer_attempt(999999,'901,902',['type'=>'year','target'=>'2023']);
    $other=$engine->start_attempt(999999,$student,['question_ids'=>'902','analytics_data'=>wp_json_encode(['type'=>'topic','target'=>'901'])]);
    customer_assert(is_wp_error($other),'A different practice request replaced a running paper.');$engine->submit_exam($id);
} elseif ($view==='random-resume') {
    $test=insert_fixture('tests',customer_paper(['type'=>'random']));$id=customer_attempt($test);
    $r=customer_ajax('gep_start_exam',['test_id'=>$test,'selected_topics'=>[['topic_id'=>99999,'count'=>99]]]);
    customer_assert($r['success'] && $r['data']['attempt_id']===$id,'Running random test could not resume.');
    customer_assert($wpdb->get_var($wpdb->prepare("SELECT question_ids FROM {$wpdb->prefix}gep_attempts WHERE id=%d",$id))==='901,902','Resume randomized an existing attempt.');
} elseif ($view==='random-validation') {
    $test=insert_fixture('tests',customer_paper(['type'=>'random']));
    foreach([[],[['topic_id'=>901,'count'=>-1]],[['topic_id'=>901,'count'=>1.5]],[['topic_id'=>901,'count'=>201]],[['topic_id'=>901,'count'=>2]]] as $topics) {
        $r=customer_ajax('gep_start_exam',['test_id'=>$test,'selected_topics'=>$topics]);customer_assert(!$r['success'],'Invalid/unavailable selection created a test.');
    }
    customer_assert((int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}gep_attempts WHERE test_id=%d",$test))===0,'Invalid selection consumed an attempt.');
} elseif ($view==='concurrent-starts') {
    $test=insert_fixture('tests',customer_paper());$fn=static function()use($test){customer_attempt($test);};customer_fork([$fn,$fn]);
    customer_assert((int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}gep_attempts WHERE test_id=%d AND user_id=%d",$test,$student))===1,'Two tabs created duplicate attempts.');
} elseif ($view==='concurrent-saves') {
    $test=insert_fixture('tests',customer_paper());$id=customer_attempt($test);
    customer_fork([static function()use($id){customer_assert((new GEP_Exam_Engine())->save_answer($id,901,'A')!==false,'First save failed.');},static function()use($id){customer_assert((new GEP_Exam_Engine())->save_answer($id,902,'0')!==false,'Second save failed.');}]);
    $answers=json_decode($wpdb->get_var($wpdb->prepare("SELECT answers FROM {$wpdb->prefix}gep_attempts WHERE id=%d",$id)),true);
    customer_assert($answers[901]['answer']==='A' && $answers[902]['answer']==='0','Concurrent saves lost an answer.');
} elseif ($view==='submit-race') {
    $test=insert_fixture('tests',customer_paper());$id=customer_attempt($test);
    customer_fork([static function()use($id){(new GEP_Exam_Engine())->save_answer($id,901,'A');},static function()use($id){customer_assert((new GEP_Exam_Engine())->submit_exam($id)===true,'Submission failed.');}]);
    $attempt=(new GEP_Result())->get_attempt_result($id);$answers=json_decode($attempt->answers,true);$expected=isset($answers[901])?2.0:0.0;
    customer_assert($attempt->status==='submitted' && (float)$attempt->score===$expected,'Saved answers changed after grading.');
} elseif ($view==='admin-create-edit') {
    $data=customer_paper(['translated_data'=>wp_json_encode(['sections'=>[['name'=>'Math','ids'=>'901,902','time_limit'=>0,'marks'=>3,'negative_marks'=>1]]])]);
    $id=$admin_tests->save_test_with_links($data,[901,902]);customer_assert(!is_wp_error($id),'Could not create a valid test.');
    customer_assert((float)$wpdb->get_var($wpdb->prepare("SELECT total_marks FROM {$wpdb->prefix}gep_tests WHERE id=%d",$id))===6.0,'Section marks were not reflected in total.');
    $data['id']=$id;$data['title']='Edited title';$again=$admin_tests->save_test_with_links($data,[901,902]);customer_assert($again===$id,'Editing created another test.');
} elseif ($view==='admin-invalid-ids') {
    $result=$admin_tests->save_test_with_links(customer_paper(),[901,99999999]);customer_assert(is_wp_error($result),'Missing question ID accepted.');
} elseif ($view==='admin-rollback') {
    $id=$admin_tests->save_test_with_links(customer_paper(),[901,902]);
    $failing=new class extends GEP_Admin_Tests { public function link_questions_to_test($id,$ids){parent::link_questions_to_test($id,$ids);return false;} };
    $result=$failing->save_test_with_links(customer_paper(['id'=>$id,'title'=>'Should roll back']),[902]);
    customer_assert(is_wp_error($result),'Link failure was presented as success.');
    customer_assert((new GEP_Test())->get_test($id)->title==='Customer regression paper','Failed save changed the title.');
    customer_assert(count((new GEP_Test())->get_test_questions($id))===2,'Failed save replaced existing questions.');
} elseif ($view==='admin-sections') {
    foreach([[['ids'=>'901'],['ids'=>'901']],[['ids'=>'901,wrong']],[['ids'=>'901','time_limit'=>10],['ids'=>'902','time_limit'=>0]],[['ids'=>'901','time_limit'=>11]]] as $sections) {
        $result=$admin_tests->save_test_with_links(customer_paper(['translated_data'=>wp_json_encode(['sections'=>$sections])]),[901,902]);customer_assert(is_wp_error($result),'Invalid sections accepted.');
    }
} elseif ($view==='admin-draft') {
    $id=$admin_tests->save_test_with_links(customer_paper(['status'=>'draft']),[]);customer_assert(!is_wp_error($id),'Empty draft could not be saved.');
    customer_assert(is_wp_error($engine->start_attempt($id,$student)),'Student could start a draft.');
    customer_assert(in_array($id,array_map('intval',array_column($admin_tests->list_tests(),'id')),true),'Saved draft disappeared from admin list.');
} elseif ($view==='admin-series') {
    $child=$admin_tests->save_test_with_links(customer_paper(),[901]);
    $series=$admin_tests->save_test_with_links(customer_paper(['type'=>'series']),[],[$child]);customer_assert(!is_wp_error($series),'Valid series could not save.');
    customer_assert(is_wp_error($admin_tests->save_test_with_links(customer_paper(['id'=>$series,'type'=>'series']),[],[$series])),'Self-referencing series accepted.');
    customer_assert(is_wp_error($admin_tests->save_test_with_links(customer_paper(['type'=>'series']),[],[$series])),'Unsupported nested series accepted.');
} elseif ($view==='payment-unavailable') {
    $id=insert_fixture('tests',customer_paper(['status'=>'draft','is_free'=>0,'price'=>100]));
    customer_assert(is_wp_error((new GEP_Payment())->create_order($id)),'Draft item accepted payment.');
    customer_assert(is_wp_error((new GEP_Payment())->create_order($id,'invalid')),'Unknown item type accepted payment.');
    $id=insert_fixture('tests',customer_paper(['type'=>'random','is_free'=>0,'price'=>100]));
    customer_assert(is_wp_error((new GEP_Payment())->create_order($id)),'Random checkout allowed zero purchased attempts.');
} elseif ($view==='payment-free') {
    $id=insert_fixture('tests',customer_paper(['is_free'=>1,'price'=>100]));$order=(new GEP_Payment())->create_order($id);
    customer_assert(!is_wp_error($order) && $order['status']==='free','A test marked free was charged its old price.');
    customer_assert((int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}gep_orders WHERE item_id=%d AND amount=0 AND status='success'",$id))===1,'Free enrollment not recorded.');
} else throw new RuntimeException('Unknown customer test case.');
$html='Customer workflow '.$view.' passed.';
