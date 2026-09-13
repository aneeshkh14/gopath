<?php
// Real WordPress + MySQL rendering, exclusively on the disposable CI database.
$kind = $argv[1] ?? ''; $view = $argv[2] ?? ''; $state = $argv[3] ?? 'empty';
$root = getenv('GEP_TEST_WORDPRESS_ROOT');
if (!$root || !is_file($root.'/wp-load.php')) throw new RuntimeException('Disposable WordPress root required.');
define('WP_ADMIN', $kind === 'admin' || $kind === 'admin-reply');
define('DISABLE_WP_CRON', true);
$_SERVER['HTTP_HOST']='portal.example'; $_SERVER['REQUEST_METHOD']='GET'; $_SERVER['REQUEST_URI']='/dashboard/';
ob_start();
require $root.'/wp-load.php';
if (DB_NAME !== 'gep_wordpress') throw new RuntimeException('Only the disposable gep_wordpress database is allowed.');
add_filter('pre_wp_mail', '__return_true');
add_filter('pre_http_request', function(){return new WP_Error('isolated_test','External services are disabled in rendering tests.');});
$wpdb->suppress_errors(true);
set_error_handler(function($severity,$message,$file,$line){
    if (($severity & (E_WARNING|E_NOTICE|E_USER_WARNING|E_USER_NOTICE)) && strpos($file, GEP_PLUGIN_DIR) === 0) throw new ErrorException($message,0,$severity,$file,$line);
    return false;
});
function check_db(){global $wpdb;if($wpdb->last_error)throw new RuntimeException($wpdb->last_error);}
add_filter('query', function($query){check_db();return $query;});
function insert_fixture($table,$data){global $wpdb;if($wpdb->insert($wpdb->prefix.'gep_'.$table,$data)===false)throw new RuntimeException($wpdb->last_error);return (int)$wpdb->insert_id;}
if ($kind==='seed') {
    update_option('gep_run_activation_tasks',0);
    $student=wp_insert_user(['user_login'=>'fixture_student','user_pass'=>'local-fixture-only','user_email'=>'student@example.test','role'=>'subscriber']);
    if(is_wp_error($student))throw new RuntimeException($student->get_error_message());update_option('gep_fixture_student',$student);
    insert_fixture('categories',['id'=>901,'name'=>"Math & संस्कृत 'quoted'",'slug'=>'fixture-category']);
    foreach([901=>'single',902=>'single',903=>'series',904=>'random'] as $id=>$type)insert_fixture('tests',['id'=>$id,'title'=>"Fixture $type टेस्ट",'slug'=>'fixture-'.$id,'type'=>$type,'category_id'=>901,'price'=>100,'is_free'=>1,'attempt_limit'=>0,'duration_minutes'=>10,'total_marks'=>4,'instructions'=>'Read each question.','translated_data'=>'{}']);
    insert_fixture('questions',['id'=>901,'title'=>'Choose an answer संस्कृत','question_type'=>'mcq','option_a'=>'Correct','option_b'=>'Wrong','correct_answer'=>'A','category_id'=>901,'marks'=>2,'negative_marks'=>0.5]);
    insert_fixture('questions',['id'=>902,'title'=>'Type a number','question_type'=>'numerical','correct_answer'=>'0','category_id'=>901,'marks'=>2,'negative_marks'=>0.5]);
    foreach([901,902,904] as $test)foreach([901,902] as $q)insert_fixture('test_questions',['test_id'=>$test,'question_id'=>$q]);
    insert_fixture('test_series',['series_id'=>903,'test_id'=>901]);
    insert_fixture('courses',['id'=>901,'title'=>'Fixture course संस्कृत','description'=>'Course description','price'=>100,'is_free'=>0,'category_id'=>901,'instructor'=>'Teacher','created_at'=>current_time('mysql')]);
    insert_fixture('lessons',['id'=>901,'course_id'=>901,'title'=>'Fixture lesson','video_source'=>'youtube','video_url'=>'https://www.youtube.com/watch?v=fixture','duration'=>'10:00','description'=>'Lesson notes']);
    insert_fixture('lectures',['title'=>'Lecture without video','description'=>'Coming soon','instructor'=>'Teacher','category_id'=>901,'created_at'=>current_time('mysql')]);
    insert_fixture('live_classes',['title'=>'Fixture live class','instructor'=>'Teacher','scheduled_at'=>current_time('mysql'),'category_id'=>901,'is_free'=>1]);
    insert_fixture('doubts',['id'=>901,'user_id'=>$student,'course_id'=>901,'lesson_id'=>901,'question'=>'Fixture question','created_at'=>current_time('mysql')]);
    insert_fixture('user_course_access',['user_id'=>$student,'course_id'=>901,'assigned_at'=>current_time('mysql')]);
    insert_fixture('user_test_access',['user_id'=>$student,'test_id'=>901,'assigned_at'=>current_time('mysql')]);
    insert_fixture('user_test_access',['user_id'=>$student,'test_id'=>904,'extra_attempts'=>3,'assigned_at'=>current_time('mysql')]);
    insert_fixture('notifications',['user_id'=>0,'title'=>'Fixture announcement','message'=>'Global message','created_at'=>current_time('mysql')]);
    insert_fixture('typing_attempts',['user_id'=>$student,'wpm'=>50,'accuracy'=>95,'errors'=>2,'duration'=>60,'created_at'=>current_time('mysql')]);
    insert_fixture('orders',['user_id'=>$student,'item_id'=>901,'item_type'=>'course','status'=>'success','amount'=>100,'created_at'=>current_time('mysql')]);
    insert_fixture('orders',['user_id'=>$student,'item_id'=>2,'item_type'=>'pass','status'=>'pending','amount'=>299,'created_at'=>current_time('mysql')]);
    insert_fixture('attempts',['id'=>901,'user_id'=>$student,'test_id'=>901,'start_time'=>current_time('mysql'),'end_time'=>current_time('mysql'),'status'=>'submitted','answers'=>wp_json_encode([901=>['answer'=>'A','flagged'=>false],902=>['answer'=>'0','flagged'=>false]]),'score'=>4,'percentage'=>100,'is_pass'=>1]);
    insert_fixture('attempts',['id'=>902,'user_id'=>$student,'test_id'=>902,'start_time'=>current_time('mysql'),'status'=>'in_progress','answers'=>'{}']);
    check_db();ob_end_clean();echo "PASS WordPress seeded fixtures\n";exit;
}
$student=(int)get_option('gep_fixture_student');
wp_set_current_user(in_array($kind,['admin','admin-reply'],true)?1:($kind==='auth'?0:($student?:1)));
$shortcodes=new GEP_Shortcodes();
try {
    $html=''; $_GET=[]; $_POST=[]; $_REQUEST=[];
    if ($kind==='pass-concurrent') {
        if(!function_exists('pcntl_fork'))throw new RuntimeException('pcntl is required.');
        update_option('gep_razorpay_key_secret',base64_encode('fixture-secret'));
        $before=get_user_meta($student,'gep_pass_expiry',true);
        foreach([1,2] as $n)insert_fixture('orders',['user_id'=>$student,'item_id'=>1,'item_type'=>'pass','status'=>'pending','amount'=>99,'razorpay_order_id'=>'fixture_order_'.$n,'created_at'=>current_time('mysql')]);
        $wpdb->close();$children=[];
        foreach([1,2] as $n){
            $pid=pcntl_fork();if($pid===-1)throw new RuntimeException('Could not fork.');
            if($pid===0){$wpdb->db_connect();$order='fixture_order_'.$n;$payment='fixture_payment_'.$n;$ok=(new GEP_Payment())->verify_payment($order,$payment,hash_hmac('sha256',$order.'|'.$payment,'fixture-secret'));while(ob_get_level())ob_end_clean();exit($ok?0:1);}
            $children[]=$pid;
        }
        foreach($children as $pid){pcntl_waitpid($pid,$status);if(pcntl_wexitstatus($status)!==0)throw new RuntimeException('Concurrent pass verification failed.');}
        $wpdb->db_connect();wp_cache_delete($student,'user_meta');
        $expected=date('Y-m-d H:i:s',strtotime('+60 days',strtotime($before)));
        if(get_user_meta($student,'gep_pass_expiry',true)!==$expected)throw new RuntimeException('Concurrent renewals lost time.');
        $html='Both concurrent pass renewals preserved their purchased duration.';
    } elseif ($kind==='pass-renewal') {
        $expires=date('Y-m-d H:i:s', current_time('timestamp') + 60 * DAY_IN_SECONDS);
        update_user_meta($student, 'gep_pass_expiry', $expires);
        insert_fixture('coupons',['code'=>'FIXTUREFREE','type'=>'percent','value'=>100]);
        $payment=new GEP_Payment();$order=$payment->create_order(1,'pass','FIXTUREFREE');
        if(is_wp_error($order) || $order['status']!=='free')throw new RuntimeException('Pass enrollment failed.');
        $expected=date('Y-m-d H:i:s',strtotime('+30 days',strtotime($expires)));
        if(get_user_meta($student,'gep_pass_expiry',true)!==$expected)throw new RuntimeException('Renewal removed existing time.');
        $_GET=['id'=>1,'type'=>'pass'];$html=$shortcodes->render_checkout();
        if(strpos($html,'gep-pay-button')===false)throw new RuntimeException('Previous pass purchase blocked renewal.');
    } elseif ($kind==='journey') {
        $engine=new GEP_Exam_Engine();$id=$engine->start_attempt(901,$student);
        if(is_wp_error($id) || !$id)throw new RuntimeException('Could not start fixture exam.');
        if(!$engine->save_answer($id,901,'A') || !$engine->save_answer($id,902,'0') || !$engine->submit_exam($id))throw new RuntimeException('Exam journey failed.');
        $result=(new GEP_Result())->get_attempt_result($id);$snapshot=json_decode($result->analytics_data,true);
        if($result->status!=='submitted' || (float)$result->score!==4.0 || ($snapshot['total_marks']??null)!==4)throw new RuntimeException('Grading snapshot incorrect.');
        if($engine->save_answer($id,901,'B')!==false)throw new RuntimeException('Closed attempt accepted an edit.');
        $html='Exam start, answer save, submit and closed-attempt protection passed.';
    } elseif ($kind==='layout') {
        $shortcodes->register_shortcodes();
        $_GET=['view'=>$view];if($view==='exam')$_GET['id']=902;
        $_SERVER['REQUEST_URI']=$view==='exam'?'/exam/?id=902':'/dashboard/?view='.$view;
        $page=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'Fixture portal','post_content'=>$view==='exam'?'[gep_exam]':'[gep_dashboard]']);
        query_posts(['page_id'=>$page]);$GLOBALS['wp']->request=$view==='exam'?'exam':'dashboard';
        do_action('wp_enqueue_scripts');
        ob_start();include GEP_PLUGIN_DIR.'templates/portal-layout.php';$html=ob_get_clean();
        if(strpos($html,'gep-main-content')===false)throw new RuntimeException('Portal landmark missing.');
    } elseif ($kind==='dashboard') {
        $_GET=['view'=>$view];if($view==='watch')$_GET['id']=901;
        $html=$shortcodes->render_dashboard();
        if($view==='orders' && strpos($html,'Transaction History')===false)throw new RuntimeException('Order route did not render history.');
    } elseif ($kind==='auth') {
        if($view==='otp') {$_GET=['view'=>'otp','uid'=>$student];$view='login';}
        $method='render_'.str_replace('-','_',$view);$html=$shortcodes->$method();
    } elseif ($kind==='admin' || $kind==='admin-reply') {
        require_once ABSPATH.'wp-admin/includes/admin.php';
        $_GET['page']='gep-'.$view;$GLOBALS['pagenow']='admin.php';
        if($kind==='admin-reply'){$_POST=['gep_action'=>'reply_doubt','doubt_id'=>901,'reply'=>'Fixture reply'];$_REQUEST['_wpnonce']=wp_create_nonce('gep_doubt_reply');}
        $admin=new GEP_Admin();$admin->register_settings();
        $method='display_'.str_replace('-','_',$view);
        ob_start();$admin->$method();$html=ob_get_clean();
        if($kind==='admin-reply' && $wpdb->get_var("SELECT status FROM {$wpdb->prefix}gep_doubts WHERE id=901")!=='resolved')throw new RuntimeException('Doubt reply did not persist.');
    } elseif ($kind==='checkout') {
        // A fresh student exercises checkout rather than existing access.
        if($state==='seeded'){ $other=get_user_by('login','fixture_buyer');if(!$other){$id=wp_insert_user(['user_login'=>'fixture_buyer','user_pass'=>'local-fixture-only','role'=>'subscriber']);wp_set_current_user($id);}else wp_set_current_user($other->ID);}
        $_GET=['id'=>$view==='pass'?2:901,'type'=>$view];$html=$shortcodes->render_checkout();
    } elseif ($kind==='exam') {
        $_GET=['id'=>['instructions'=>901,'window'=>902,'series'=>903,'custom'=>904,'missing'=>0][$view]];$html=$shortcodes->render_exam();
        if($state==='seeded' && $view==='window' && strpos($html,'gep-question-block')===false)throw new RuntimeException('Active exam did not render questions.');
    } elseif ($kind==='result') {$_GET=['id'=>901];$html=$shortcodes->render_result();}
    elseif ($kind==='page') {$method='render_'.str_replace('-','_',$view);$html=$shortcodes->$method();}
    else throw new RuntimeException('Unknown screen');
    check_db();
    if(strlen(trim($html))<30)throw new RuntimeException('Empty screen output.');
    ob_end_clean();echo "PASS WordPress $state $kind/$view\n";
} catch(Throwable $e) {
    while(ob_get_level())ob_end_clean();fwrite(STDERR,"FAIL WordPress $state $kind/$view: {$e->getMessage()} ({$e->getFile()}:{$e->getLine()})\n");exit(1);
}
