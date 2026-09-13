<?php
// Targeted assertions in the isolated WordPress fixture database.
$dashboard = new GEP_Dashboard();
if ($view === 'resume') {
    $attempt = $dashboard->get_resume_attempt($student);
    if (!$attempt || (int)$attempt->test_id !== 902) throw new RuntimeException('Dashboard missed the running test.');
    $html = $shortcodes->render_dashboard();
    if (strpos($html, 'Continue test') === false || strpos($html, 'gep-dashboard-carousel') !== false) throw new RuntimeException('Study action was replaced by a promotion.');
    if (strpos($html, 'gep-hcard-action') === false) throw new RuntimeException('Test cards have no action label.');
} elseif ($view === 'counts') {
    $stats = $dashboard->get_dashboard_stats($student);
    if ($stats['total_attempts'] !== 1 || $stats['passed_exams'] !== 1) throw new RuntimeException('Unfinished attempts inflated completed counts.');
    $html = 'Completed counts exclude the fixture running attempt.';
} elseif ($view === 'practice') {
    $id = insert_fixture('attempts', ['user_id'=>$student,'test_id'=>999999,'start_time'=>current_time('mysql'),'status'=>'in_progress','answers'=>'{}','analytics_data'=>wp_json_encode(['practice_title'=>'My Sanskrit PYQ practice'])]);
    try {
        $html = $shortcodes->render_dashboard();
        if (strpos($html, 'My Sanskrit PYQ practice') === false || strpos($html, 'id=999999') === false) throw new RuntimeException('Virtual practice cannot be resumed from the dashboard.');
    } finally { $wpdb->delete($wpdb->prefix.'gep_attempts', ['id'=>$id]); }
} elseif ($view === 'review') {
    $wpdb->update($wpdb->prefix.'gep_attempts', ['analytics_data'=>wp_json_encode(['total_marks'=>8])], ['id'=>901]);
    try {
        $html = $shortcodes->render_dashboard();
        $url = esc_url(add_query_arg('id', 901, (string)gep_get_url('result')));
        if (strpos($html, $url) === false || strpos($html, '/8</small>') === false) throw new RuntimeException('Recent results lost their direct link or recorded mark total.');
    } finally { $wpdb->update($wpdb->prefix.'gep_attempts', ['analytics_data'=>null], ['id'=>901]); }
} elseif ($view === 'assets') {
    $shortcodes->register_shortcodes();
    $page=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'Fixture assets','post_content'=>'[gep_dashboard]']);
    query_posts(['page_id'=>$page]);$GLOBALS['wp']->request='dashboard';
    // Match WP::register_globals(): query_posts alone does not set global $post.
    $GLOBALS['post'] = get_post($page);setup_postdata($GLOBALS['post']);
    do_action('wp_enqueue_scripts');
    ob_start();wp_styles()->do_items();$html=ob_get_clean();
    $positions=[];
    foreach(['gep-public-css','gep-dashboard-css','gep-auth-css','gep-theme-css','gep-layout-css'] as $handle){
        $positions[$handle]=array_search($handle,wp_styles()->done,true);
        if($positions[$handle]===false)throw new RuntimeException('Missing style: '.$handle);
    }
    foreach(['gep-public-css','gep-dashboard-css','gep-auth-css'] as $handle)if($positions[$handle]>$positions['gep-theme-css'])throw new RuntimeException('Theme loaded before '.$handle);
    if($positions['gep-layout-css']<$positions['gep-theme-css'])throw new RuntimeException('Layout did not load after theme.');
} elseif ($view === 'empty') {
    $new = wp_insert_user(['user_login'=>'fixture_visual_empty','user_pass'=>'local-fixture-only','role'=>'subscriber']);
    if(is_wp_error($new))throw new RuntimeException($new->get_error_message());
    wp_set_current_user($new);$html=$shortcodes->render_dashboard();
    if(strpos($html,'Find a practice test')===false || strpos($html,'Continue test')!==false)throw new RuntimeException('New learner has no correct first action.');
} else throw new RuntimeException('Unknown visual regression case.');
