<?php
// Reproduce live-audit failures using only the disposable student fixtures.
if ($view === 'result-total') {
    $wpdb->update($wpdb->prefix.'gep_attempts', ['analytics_data'=>wp_json_encode(['total_marks'=>50]),'score'=>1,'percentage'=>2], ['id'=>901]);
    try {
        $_GET=['id'=>901];$html=$shortcodes->render_result();
        if(strpos($html,'/50</small>')===false || strpos($html,'Score %')===false)throw new RuntimeException('Result denominator or score-percent label is incorrect.');
    } finally {$wpdb->update($wpdb->prefix.'gep_attempts',['analytics_data'=>null,'score'=>4,'percentage'=>100],['id'=>901]);}
} elseif ($view === 'practice-comparison') {
    $id=insert_fixture('attempts',['user_id'=>$student,'test_id'=>999999,'start_time'=>current_time('mysql'),'end_time'=>current_time('mysql'),'status'=>'submitted','question_ids'=>'901,902','answers'=>'{}','score'=>1,'percentage'=>2,'analytics_data'=>wp_json_encode(['total_marks'=>50,'practice_title'=>'Fixture practice'])]);
    try {
        $_GET=['id'=>$id];$html=$shortcodes->render_result();
        if(strpos($html,'/50</small>')===false || strpos($html,esc_url(add_query_arg('view','pyqs',(string)gep_get_url('dashboard'))))===false || strpos($html,'gep-practice-comparison-note')===false || strpos($html,'<span class="lbl">Highest Score</span>')!==false)throw new RuntimeException('Different practice sets are compared or have the wrong mark total.');
    } finally {$wpdb->delete($wpdb->prefix.'gep_attempts',['id'=>$id]);}
} elseif ($view === 'exhausted-purchase') {
    $wpdb->update($wpdb->prefix.'gep_tests',['attempt_limit'=>1],['id'=>901]);
    try {
        $_GET=['view'=>'purchases'];$html=$shortcodes->render_dashboard();
        if(strpos($html,'No attempts remaining')===false || strpos($html,'Review Results')===false)throw new RuntimeException('Exhausted access still invites a new exam.');
    } finally {$wpdb->update($wpdb->prefix.'gep_tests',['attempt_limit'=>0],['id'=>901]);}
} elseif ($view === 'running-purchase') {
    $access=insert_fixture('user_test_access',['user_id'=>$student,'test_id'=>902,'assigned_at'=>current_time('mysql')]);
    $old=insert_fixture('attempts',['user_id'=>$student,'test_id'=>902,'start_time'=>current_time('mysql'),'status'=>'submitted','answers'=>'{}']);
    $wpdb->update($wpdb->prefix.'gep_tests',['attempt_limit'=>1],['id'=>902]);
    try {
        $_GET=['view'=>'purchases'];$html=$shortcodes->render_dashboard();
        if(strpos($html,'Test in progress')===false || strpos($html,'Continue Test')===false)throw new RuntimeException('Exhausted counter blocks an already running attempt.');
    } finally {
        $wpdb->delete($wpdb->prefix.'gep_user_test_access',['id'=>$access]);$wpdb->delete($wpdb->prefix.'gep_attempts',['id'=>$old]);
        $wpdb->update($wpdb->prefix.'gep_tests',['attempt_limit'=>0],['id'=>902]);
    }
} elseif ($view === 'custom-unavailable') {
    $wpdb->update($wpdb->prefix.'gep_tests',['status'=>'draft'],['id'=>904]);
    try {
        $html=$shortcodes->render_dashboard();
        if(strpos($html,'Custom test templates are not available yet')===false || strpos($html,'Browse PYQ practice')===false)throw new RuntimeException('Missing custom catalogue has no explanation or alternative.');
    } finally {$wpdb->update($wpdb->prefix.'gep_tests',['status'=>'publish'],['id'=>904]);}
} elseif ($view === 'pass-access') {
    // Subscriber has no purchase or manual grant for test 902.
    $wpdb->update($wpdb->prefix.'gep_tests',['is_free'=>0],['id'=>902]);
    try {
        $logic=new GEP_Test();delete_user_meta($student,'gep_pass_expiry');
        if($logic->user_has_access($student,902))throw new RuntimeException('Unpaid learner received access.');
        update_user_meta($student,'gep_pass_expiry',date('Y-m-d H:i:s',current_time('timestamp')+86400));
        if(!$logic->user_has_access($student,902))throw new RuntimeException('Active pass was denied at exam entry.');
        update_user_meta($student,'gep_pass_expiry',date('Y-m-d H:i:s',current_time('timestamp')-86400));
        if($logic->user_has_access($student,902))throw new RuntimeException('Expired pass still grants access.');
        $html='Active and expired pass access verified for a subscriber.';
    } finally {delete_user_meta($student,'gep_pass_expiry');$wpdb->update($wpdb->prefix.'gep_tests',['is_free'=>1],['id'=>902]);}
} elseif ($view === 'single-percentile') {
    if((new GEP_Analytics())->get_percentile(901,4)!==0)throw new RuntimeException('A single learner was ranked above nonexistent peers.');
    $html='Single-learner comparison does not claim 100th percentile.';
} else throw new RuntimeException('Unknown live-audit fixture.');
