<?php
// Isolated handler tests: WordPress I/O is stubbed; no mail, DB or payments leave this process.
define('ABSPATH', __DIR__ . '/'); define('MINUTE_IN_SECONDS', 60);
class WP_Error { public $code; public function __construct($code, $message='') {$this->code=$code;} public function get_error_code(){return $this->code;} }
class JsonResponse extends Exception {public $success;public $data;public function __construct($success,$data){$this->success=$success;$this->data=$data;}}
class RedirectResponse extends Exception {}
function is_wp_error($x){return $x instanceof WP_Error;}
function media_handle_upload(){}
function wp_cache_delete(...$args){}
function get_user_meta($id,$key,$single=true){return $GLOBALS['user_meta'][$id][$key] ?? ''; }
function update_user_meta($id,$key,$value){$GLOBALS['user_meta'][$id][$key]=$value;return true;}
function get_current_user_id(){return $GLOBALS['uid'];}
function is_user_logged_in(){return $GLOBALS['uid'] > 0;}
function wp_set_current_user($id){$GLOBALS['uid']=$id;}
function wp_set_auth_cookie($id,$remember=false){$GLOBALS['cookie']=[$id,$remember];}
function do_action(...$args){}
function get_user_by($field,$value){return (object)['ID'=>7,'user_login'=>'student','user_email'=>'student@example.test'];}
function wp_authenticate($user,$password){return get_user_by('id',7);}
function wp_verify_nonce(...$args){return true;}
function check_ajax_referer(...$args){}
function wp_safe_redirect($url){throw new RedirectResponse($url);}
function wp_get_referer(){return 'https://portal.example/login';}
function gep_get_url($page){return 'https://portal.example/' . $page;}
function add_query_arg($key,$value,$url){return $url . (strpos($url,'?')===false?'?':'&') . urlencode($key).'='.urlencode($value);}
function get_option($key,$fallback=false){return $GLOBALS['options'][$key] ?? $fallback;}
function set_transient($key,$value,$ttl){$GLOBALS['transients'][$key]=$value;}
function get_transient($key){return $GLOBALS['transients'][$key] ?? false;}
function delete_transient($key){unset($GLOBALS['transients'][$key]);}
function wp_rand($min,$max){return 123456;}
function wp_mail($to,$subject,$message){$GLOBALS['mail']=[$to,$subject,$message];return $GLOBALS['mail_ok'];}
function absint($value){return abs((int)$value);}
function sanitize_text_field($value){return trim($value);}
function sanitize_textarea_field($value){return trim($value);}
function wp_unslash($value){return $value;}
function current_time($type){return $type === 'timestamp' ? time() : date('Y-m-d H:i:s');}
function get_gmt_from_date($date,$format){return (new DateTimeImmutable($date,new DateTimeZone('Asia/Kolkata')))->setTimezone(new DateTimeZone('UTC'))->format($format);}
function wp_send_json_success($data=[]){throw new JsonResponse(true,$data);}
function wp_send_json_error($data=[]){throw new JsonResponse(false,$data);}
class TestDb {
 public $prefix='wp_', $users='wp_users', $vars=[], $results=[], $rows=[], $updates=[], $inserts=[], $queries=[], $update_result=1;
 function prepare($query,...$args){return $query;}
 function get_row($query){return array_shift($this->rows);}
 function update($table,$data,$where,...$args){$this->updates[]=[$table,$data,$where];return $this->update_result;}
 function insert($table,$data){$this->inserts[]=[$table,$data];return 1;}
 function query($query){$this->queries[]=$query;return 1;}
 function get_var($query){return array_shift($this->vars);}
 function get_results($query){return $this->results;}
}
require __DIR__.'/../includes/class-gep-auth.php';
require __DIR__.'/../includes/class-gep-exam-engine.php';
require __DIR__.'/../includes/class-gep-payment.php';
require __DIR__.'/../includes/class-gep-ajax.php';
require __DIR__.'/../includes/class-gep-result.php';
function expect($value,$message='Assertion failed'){if(!$value)throw new Exception($message);}
function response($fn){try{$fn();}catch(JsonResponse $r){return $r;}throw new Exception('No JSON response');}
