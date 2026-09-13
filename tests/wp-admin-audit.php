<?php
// Admin regressions run exclusively in the disposable WordPress database.
$logic = new GEP_Admin_Coupons();
$valid = array('code'=>'ADMIN-FIXTURE','type'=>'fixed','value'=>12.50,'usage_limit'=>3,'expiry_date'=>'','status'=>'active');
if ($view === 'coupon-valid') {
    $valid['code']=' admin-fixture ';$valid['expiry_date']='2030-06-15';
    $result=$logic->save_coupon($valid);
    if($result===false || is_wp_error($result))throw new RuntimeException('Valid coupon was rejected.');
    $id=(int)$wpdb->insert_id;
    try {
        $row=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}gep_coupons WHERE id=%d",$id));
        if($row->code!=='ADMIN-FIXTURE' || (float)$row->value!==12.5 || $row->expiry_date!=='2030-06-15 23:59:59')throw new RuntimeException('Coupon code, decimals or inclusive expiry date changed.');
    } finally {$logic->delete_coupon($id);}
} elseif ($view === 'coupon-invalid') {
    $before=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gep_coupons");
    foreach(array(array('code'=>' '),array('type'=>'other'),array('type'=>'percent','value'=>101),array('value'=>-1),array('usage_limit'=>-1),array('usage_limit'=>'1.5'),array('expiry_date'=>'2030-02-30'),array('status'=>'unknown')) as $invalid) {
        if(!is_wp_error($logic->save_coupon(array_merge($valid,$invalid))))throw new RuntimeException('Invalid coupon input was accepted.');
    }
    if((int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gep_coupons")!==$before)throw new RuntimeException('Invalid coupons were written.');
} elseif ($view === 'coupon-form') {
    $_POST=array('code'=>'KEEP-ME','type'=>'percent','value'=>'101','usage_limit'=>'3','expiry_date'=>'');
    $method=new ReflectionMethod(GEP_Admin_Coupons::class,'handle_save_coupon');$method->invoke($logic);
    ob_start();include GEP_PLUGIN_DIR.'admin/views/coupons.php';$html=ob_get_clean();
    foreach(array('value="KEEP-ME"','value="101"','percentages cannot exceed 100','style="display:flex"','for="gep-coupon-code"') as $expected)if(strpos($html,$expected)===false)throw new RuntimeException('Failed coupon form lost data or error feedback: '.$expected);
} elseif ($view === 'coupon-duplicate') {
    $logic->save_coupon($valid);$id=(int)$wpdb->insert_id;
    try {if(!is_wp_error($logic->save_coupon($valid)))throw new RuntimeException('Duplicate coupon was accepted.');}
    finally {$logic->delete_coupon($id);}
} elseif ($view === 'coupon-toggle') {
    $logic->save_coupon($valid);$id=(int)$wpdb->insert_id;
    try {
        $result=$logic->save_coupon(array('id'=>$id,'status'=>'inactive'));
        $row=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}gep_coupons WHERE id=%d",$id));
        if($result===false || is_wp_error($result) || $row->status!=='inactive' || (float)$row->value!==12.5)throw new RuntimeException('Status-only change lost coupon terms.');
    } finally {$logic->delete_coupon($id);}
} elseif ($view === 'coupon-timezone') {
    $old=get_option('timezone_string');$logic->save_coupon($valid);$id=(int)$wpdb->insert_id;
    try {
        foreach(array('Asia/Kolkata','America/Los_Angeles') as $zone) {
            update_option('timezone_string',$zone);
            foreach(array(-3600,3600) as $delta) {
                $wpdb->update($wpdb->prefix.'gep_coupons',array('expiry_date'=>wp_date('Y-m-d H:i:s',time()+$delta)),array('id'=>$id));
                $result=(new GEP_Payment())->validate_coupon('ADMIN-FIXTURE',901,'test');
                if(is_wp_error($result)!==($delta<0))throw new RuntimeException('Coupon expiry does not use site time.');
            }
        }
    } finally {update_option('timezone_string',$old);$logic->delete_coupon($id);}
} elseif ($view === 'payment-records') {
    $id=insert_fixture('orders',array('user_id'=>999999,'item_id'=>2,'item_type'=>'pass','status'=>'success','amount'=>12.50,'created_at'=>current_time('mysql')));
    try {
        ob_start();include GEP_PLUGIN_DIR.'admin/views/payments.php';$html=ob_get_clean();
        foreach(array('Yearly Mock Test Pass Pro','Deleted user #999999','12.50','Order #'.$id) as $expected)if(strpos($html,$expected)===false)throw new RuntimeException('Payment history lost: '.$expected);
    } finally {$wpdb->delete($wpdb->prefix.'gep_orders',array('id'=>$id));}
} elseif ($view === 'payment-pages') {
    $ids=array();
    try {
        for($i=0;$i<51;$i++)$ids[]=insert_fixture('orders',array('user_id'=>$student,'item_id'=>901,'item_type'=>'test','status'=>'pending','amount'=>10,'razorpay_order_id'=>'PAGED-'.$i,'created_at'=>'2024-01-15 12:00:00'));
        $_GET=array('m'=>'202401','paged'=>2);
        ob_start();include GEP_PLUGIN_DIR.'admin/views/payments.php';$html=ob_get_clean();
        if(strpos($html,'51 orders · Page 2 of 2')===false || strpos($html,'PAGED-0')===false || strpos($html,'PAGED-50')!==false || strpos($html,'m=202401')===false)throw new RuntimeException('Older payments are unreachable or pagination loses its month filter.');
    } finally {foreach($ids as $id)$wpdb->delete($wpdb->prefix.'gep_orders',array('id'=>$id));}
} else throw new RuntimeException('Unknown admin audit case.');
if(!$html)$html='Admin fixture assertions passed.';
