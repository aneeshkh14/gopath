<?php
// Runs only after wp-screens.php has verified the disposable database.
require_once ABSPATH.'wp-admin/includes/admin.php';
function qa_expect($ok,$message){if(!$ok)throw new RuntimeException($message);}
class QA_Redirect extends RuntimeException {}
function qa_redirect($url){throw new QA_Redirect($url);}
if ($view==='profile-structure') {
    wp_set_current_user(1);$user_id=$student;
    ob_start();include GEP_PLUGIN_DIR.'templates/dashboard/profile.php';$html=ob_get_clean();
    qa_expect(strpos($html,'class="gep-account-role">Student</span>')!==false,'Admin preview incorrectly labels the student as an administrator.');
    foreach(['personal','academic','security','telemetry'] as $tab) {
        qa_expect(strpos($html,'id="profile-tab-'.$tab.'"')!==false && strpos($html,'aria-controls="tab-'.$tab.'"')!==false && strpos($html,'aria-labelledby="profile-tab-'.$tab.'"')!==false,'Profile tab and panel are not associated.');
    }
    qa_expect(substr_count($html,'role="tab"')===4 && substr_count($html,'role="tabpanel"')===4,'Profile sections are missing.');
    qa_expect(strpos($html,'name="preferred_lang"')!==false && strpos($html,'name="student_goals[]"')!==false && strpos($html,'name="new_password"')!==false,'Profile redesign lost editable settings.');
} elseif ($view==='learning-catalog') {
    wp_set_current_user($student);
    ob_start();include GEP_PLUGIN_DIR.'templates/dashboard/supercoaching.php';$html=ob_get_clean();
    qa_expect(strpos($html,'<h1>SuperCoaching</h1>')!==false && strpos($html,'Fixture course')!==false,'Course catalogue lost its heading or courses.');
    qa_expect(strpos($html,'gep-course-placeholder')!==false && strpos($html,'images.unsplash.com')===false,'Missing thumbnails should use the local visual fallback.');
    qa_expect(strpos(html_entity_decode($html),'view=watch&id=901')!==false,'Enrolled course no longer links to its lessons.');
    qa_expect(strpos($html,'aria-pressed="true"')!==false,'The initial course filter has no selection state.');
} elseif (strpos($view,'course-')===0) {
    wp_set_current_user(1);
    $logic=new GEP_Admin_Courses();
    $_GET=['page'=>'gep-courses'];
    $_POST=wp_slash(['gep_course_save'=>1,'gep_nonce'=>wp_create_nonce('gep_save_course'),'course_id'=>901,'title'=>"Editor's course संस्कृत",'instructor'=>"O'Neil",'description'=>"Don't lose the syllabus",'price'=>'12.50','category_id'=>901,'subcategory_id'=>0,'thumbnail'=>'']);
    $before=$wpdb->get_row("SELECT * FROM {$wpdb->prefix}gep_courses WHERE id=901",ARRAY_A);
    if($view==='course-edit') {
        $_POST=[];$_GET['edit_course']=901;
        ob_start();include GEP_PLUGIN_DIR.'admin/views/courses.php';$html=ob_get_clean();
        qa_expect(strpos($html,'name="course_id" value="901"')!==false && strpos($html,'Edit Course')!==false && strpos($html,'value="Teacher"')!==false,'Edit form did not preserve course identity and values.');
    } elseif($view==='course-invalid') {
        $_POST['price']='-1';$logic->handle_actions();
        ob_start();include GEP_PLUGIN_DIR.'admin/views/courses.php';$html=ob_get_clean();
        qa_expect(strpos($html,'price of zero or more')!==false && strpos($html,'value="-1"')!==false && strpos($html,'style="display:flex"')!==false,'Invalid course lost its values or retry form.');
        qa_expect($wpdb->get_row("SELECT * FROM {$wpdb->prefix}gep_courses WHERE id=901",ARRAY_A)===$before,'Invalid price changed the course.');
    } elseif($view==='course-update') {
        add_filter('wp_redirect','qa_redirect');
        try {
            try{$logic->handle_actions();throw new RuntimeException('Save did not redirect.');}catch(QA_Redirect $r){qa_expect(strpos($r->getMessage(),'message=saved')!==false,'Wrong save feedback.');}
            $after=$wpdb->get_row("SELECT * FROM {$wpdb->prefix}gep_courses WHERE id=901");
            qa_expect($after->title==="Editor's course संस्कृत" && $after->instructor==="O'Neil" && (float)$after->price===12.5,'Course editing lost apostrophes or decimals.');
            qa_expect((int)$wpdb->get_var("SELECT course_id FROM {$wpdb->prefix}gep_lessons WHERE id=901")===901,'Course editing changed lessons.');
            qa_expect((int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}gep_user_course_access WHERE user_id=%d AND course_id=901",$student))>0,'Editing removed enrollment.');
        } finally {remove_filter('wp_redirect','qa_redirect');$wpdb->update($wpdb->prefix.'gep_courses',$before,['id'=>901]);}
    } elseif($view==='course-missing') {
        $_POST['course_id']=987654321;$logic->handle_actions();qa_expect(!empty(get_settings_errors('gep_courses')),'Missing course falsely reported success.');
    } elseif($view==='course-permissions') {
        wp_set_current_user($student);$logic->handle_actions();qa_expect($wpdb->get_row("SELECT * FROM {$wpdb->prefix}gep_courses WHERE id=901",ARRAY_A)===$before,'Subscriber edited a course.');
    }
} elseif($view==='published-counts') {
    $draft=insert_fixture('questions',['title'=>'Unpublished fixture','status'=>'draft']);
    $links=[];
    try {
        foreach([$draft,987654321,901] as $q)$links[]=insert_fixture('test_questions',['test_id'=>901,'question_id'=>$q]);
        $logic=new GEP_Test();qa_expect($logic->get_question_count(901)===2,'Single count includes draft, missing or duplicate questions.');
        qa_expect(($logic->get_question_counts([901])[901]??0)===2,'Batched count includes unavailable questions.');
    } finally {foreach($links as $id)$wpdb->delete($wpdb->prefix.'gep_test_questions',['id'=>$id]);$wpdb->delete($wpdb->prefix.'gep_questions',['id'=>$draft]);}
} elseif($view==='score-distribution' || $view==='practice-diagnostic') {
    wp_set_current_user(1);
    $id=insert_fixture('attempts',['user_id'=>$student,'test_id'=>999999,'status'=>'submitted','percentage'=>65,'score'=>13,'start_time'=>current_time('mysql')]);
    try {
        ob_start();include GEP_PLUGIN_DIR.'admin/views/reports.php';$html=ob_get_clean();
        if($view==='score-distribution') {
            $means=$wpdb->get_col("SELECT AVG(percentage) FROM {$wpdb->prefix}gep_attempts WHERE status='submitted' GROUP BY user_id");
            qa_expect(strpos($html,count($means).' students included.')!==false && strpos($html,'~')===false,'Report fabricates student counts.');
            qa_expect(strpos($html,'Student Score Distribution')!==false,'Measured score distribution missing.');
        } else qa_expect(!in_array($id,array_map('intval',wp_list_pluck($orphan_attempts,'id')),true),'Virtual practice paper flagged as an orphan.');
    } finally {$wpdb->delete($wpdb->prefix.'gep_attempts',['id'=>$id]);}
} elseif($view==='guest-support') {
    wp_set_current_user(0);
    foreach(['support','about','policies'] as $public_view){$_GET=['view'=>$public_view];$part=$shortcodes->render_dashboard();qa_expect(strpos($part,'Login Now')===false,'Public help requires a login.');$html.=$part;}
    qa_expect(strpos($html,'Need help signing in?')!==false,'Guest sign-in help missing.');
    $_GET=['view'=>'profile'];qa_expect(strpos($shortcodes->render_dashboard(),'Login Now')!==false,'Private profile exposed to guests.');
} elseif($view==='guest-layout') {
    wp_set_current_user(0);$shortcodes->register_shortcodes();$_GET=['view'=>'support'];$_SERVER['REQUEST_URI']='/dashboard/?view=support';
    $page=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'QA guest','post_content'=>'[gep_dashboard]']);
    try {
        query_posts(['page_id'=>$page]);$GLOBALS['wp']->request='dashboard';do_action('wp_enqueue_scripts');
        ob_start();include GEP_PLUGIN_DIR.'templates/portal-layout.php';$html=ob_get_clean();
        qa_expect(strpos($html,'class="gep-btn-pass gep-sign-in"')!==false,'Guest sign-in missing.');
        qa_expect(strpos($html,'id="gep-notif-trigger"')===false && strpos($html,'> My Purchases')===false,'Guest navigation shows private controls.');
    } finally {wp_delete_post($page,true);}
} elseif($view==='login-return') {
    wp_set_current_user(0);$_GET=['id'=>2,'type'=>'pass'];$_SERVER['REQUEST_URI']='/checkout/?id=2&type=pass';$html=$shortcodes->render_checkout();
    qa_expect(strpos(html_entity_decode($html),'name="redirect_to" value="/checkout/?id=2&type=pass"')!==false,'Inline checkout login loses its destination.');
    foreach(['https://outside.example/','//outside.example','/wp-admin/','/wp-login.php',gep_get_url('login')] as $url)qa_expect(GEP_Auth::login_destination($url)===gep_get_url('dashboard'),'Unsafe or looping login destination accepted.');
} elseif($view==='registration-consent') {
    wp_set_current_user(0);$_POST=['gep_nonce'=>wp_create_nonce('gep_register'),'user_login'=>'qa_no_consent','user_email'=>'no-consent@example.test','user_pass'=>'fixture-only-password'];
    add_filter('wp_redirect','qa_redirect');
    try {try{(new GEP_Auth())->handle_register();throw new RuntimeException('Registration did not reject absent consent.');}catch(QA_Redirect $r){qa_expect(strpos($r->getMessage(),'consent_required')!==false,'Missing consent not explained.');}}finally{remove_filter('wp_redirect','qa_redirect');}
    qa_expect(!username_exists('qa_no_consent'),'Registration bypassed consent.');
} else throw new RuntimeException('Unknown comprehensive QA case.');
if(!$html)$html='Comprehensive QA assertions passed.';
